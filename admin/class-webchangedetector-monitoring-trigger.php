<?php
/**
 * Trigger-based monitoring: report saved posts to the API.
 *
 * @package    WebChangeDetector
 * @subpackage WebChangeDetector/admin
 */

namespace WebChangeDetector;

defined( 'ABSPATH' ) || exit;

/**
 * Starts a monitoring check for a post after it was saved (FEAT-111).
 *
 * Saving a published post with a real content change puts the post into a small
 * pending list and schedules one cron event. The cron event sends all pending posts
 * in one request to POST /v2/monitoring/trigger. The API waits a few minutes
 * (page caches) and merges further saves of the same page into one check, so the
 * plugin never delays or debounces on its own: a late wp-cron tick only means a
 * later report, never a lost check.
 *
 * The save path makes no API call. Whether the monitoring group reacts to saved
 * posts is cached in a transient; the cron event refreshes it when it expired and
 * the settings page refreshes it whenever the group is loaded or saved.
 */
class WebChangeDetector_Monitoring_Trigger {

	/**
	 * Trigger type sent to the API.
	 *
	 * @var string
	 */
	const TYPE_POST_SAVE = 'post_save';

	/**
	 * Cron hook that sends the pending posts.
	 *
	 * @var string
	 */
	const CRON_HOOK = 'wcd_send_monitoring_triggers';

	/**
	 * Option holding the pending posts (post ID => editor user ID).
	 *
	 * @var string
	 */
	const OPTION_PENDING = 'wcd_pending_monitoring_triggers';

	/**
	 * Transient caching whether the monitoring group accepts saved posts ('1' / '0').
	 *
	 * @var string
	 */
	const TRANSIENT_ENABLED = 'wcd_monitoring_trigger_post_save';

	/**
	 * Transient counting the posts queued in the current storm window.
	 *
	 * @var string
	 */
	const TRANSIENT_STORM = 'wcd_monitoring_trigger_storm';

	/**
	 * Maximum posts queued per storm window (bulk edits, imports, migrations).
	 *
	 * @var int
	 */
	const STORM_MAX = 50;

	/**
	 * Length of the storm window in seconds.
	 *
	 * @var int
	 */
	const STORM_WINDOW = 5 * MINUTE_IN_SECONDS;

	/**
	 * Maximum URLs per API request (matches the API limit).
	 *
	 * @var int
	 */
	const MAX_URLS_PER_REQUEST = 50;

	/**
	 * How long the "group accepts saved posts" flag is trusted.
	 *
	 * @var int
	 */
	const ENABLED_TTL = 12 * HOUR_IN_SECONDS;

	/**
	 * Seconds between the save and the cron event, so a burst of saves is sent in one request.
	 *
	 * @var int
	 */
	const SEND_DELAY = 10;

	/**
	 * The admin instance (for the monitoring group UUID).
	 *
	 * @var WebChangeDetector_Admin
	 */
	private $admin;

	/**
	 * Constructor.
	 *
	 * @param WebChangeDetector_Admin $admin The admin instance.
	 */
	public function __construct( $admin ) {
		$this->admin = $admin;
	}

	/**
	 * Hook: wp_after_insert_post. Queue the post when a published post really changed.
	 *
	 * @param int           $post_id     Post ID.
	 * @param \WP_Post      $post        Post object after the save.
	 * @param bool          $update      Whether this is an existing post being updated.
	 * @param \WP_Post|null $post_before Post object before the save, null for new posts.
	 * @return void
	 */
	public function on_after_insert_post( $post_id, $post, $update, $post_before ) {
		if ( ! $this->should_trigger( $post_id, $post, $update, $post_before ) ) {
			return;
		}

		$pending = get_option( self::OPTION_PENDING, array() );
		$pending = is_array( $pending ) ? $pending : array();

		// Already waiting: the API merges repeated saves anyway, nothing to add.
		if ( isset( $pending[ $post_id ] ) ) {
			return;
		}

		// Storm cap: a bulk edit or import must not queue thousands of posts.
		$queued_in_window = (int) get_transient( self::TRANSIENT_STORM );
		if ( $queued_in_window >= self::STORM_MAX ) {
			return;
		}
		set_transient( self::TRANSIENT_STORM, $queued_in_window + 1, self::STORM_WINDOW );

		$pending[ $post_id ] = get_current_user_id();
		update_option( self::OPTION_PENDING, $pending, false );

		if ( ! wp_next_scheduled( self::CRON_HOOK ) ) {
			wp_schedule_single_event( time() + self::SEND_DELAY, self::CRON_HOOK );
		}
	}

	/**
	 * Whether this save should start a monitoring check.
	 *
	 * @param int           $post_id     Post ID.
	 * @param \WP_Post      $post        Post object after the save.
	 * @param bool          $update      Whether this is an existing post being updated.
	 * @param \WP_Post|null $post_before Post object before the save.
	 * @return bool
	 */
	private function should_trigger( $post_id, $post, $update, $post_before ) {
		if ( ! $update || ! $post instanceof \WP_Post || ! $post_before instanceof \WP_Post ) {
			return false; // A new post has no URL in the monitoring group yet.
		}

		if ( wp_is_post_revision( $post_id ) || wp_is_post_autosave( $post_id ) ) {
			return false;
		}

		if ( defined( 'WP_IMPORTING' ) && WP_IMPORTING ) {
			return false;
		}

		// Only a page that was and still is live can be compared with its last screenshot.
		// Trashing or unpublishing would produce a 404 screenshot and a 100% diff.
		if ( 'publish' !== $post->post_status || 'publish' !== $post_before->post_status ) {
			return false;
		}

		if ( ! is_post_type_viewable( $post->post_type ) ) {
			return false;
		}

		if ( false === self::is_enabled_cached() ) {
			return false;
		}

		$should = self::has_content_changed( $post, $post_before );

		/**
		 * Filters whether saving this post starts a monitoring check.
		 *
		 * Page builders that save their layout only in post meta can return true here.
		 *
		 * @param bool     $should      Whether a check is started.
		 * @param \WP_Post $post        Post object after the save.
		 * @param \WP_Post $post_before Post object before the save.
		 */
		return (bool) apply_filters( 'wcd_should_trigger_monitoring_check', $should, $post, $post_before );
	}

	/**
	 * Whether the visible content of the post changed.
	 *
	 * A changed slug is ignored on purpose: the new URL is not in the monitoring
	 * group yet, the API would ignore it.
	 *
	 * @param \WP_Post $post        Post object after the save.
	 * @param \WP_Post $post_before Post object before the save.
	 * @return bool
	 */
	public static function has_content_changed( $post, $post_before ) {
		foreach ( array( 'post_content', 'post_title', 'post_excerpt' ) as $field ) {
			if ( $post->$field !== $post_before->$field ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Cron: send the pending posts to the API.
	 *
	 * @return void
	 */
	public function send_pending() {
		$pending = get_option( self::OPTION_PENDING, array() );
		delete_option( self::OPTION_PENDING );

		if ( empty( $pending ) || ! is_array( $pending ) ) {
			return;
		}

		$group_uuid = $this->admin->monitoring_group_uuid;
		if ( empty( $group_uuid ) || ! $this->is_enabled( $group_uuid ) ) {
			return;
		}

		$urls    = array();
		$editors = array();
		foreach ( $pending as $post_id => $editor_id ) {
			$post = get_post( $post_id );
			if ( ! $post || 'publish' !== $post->post_status ) {
				continue;
			}

			$permalink = get_permalink( $post );
			if ( ! $permalink ) {
				continue;
			}

			$urls[] = array(
				'url'     => $permalink,
				'title'   => mb_substr( wp_strip_all_tags( get_the_title( $post ) ), 0, 255 ),
				'post_id' => (int) $post_id,
			);

			$editor = $editor_id ? get_userdata( $editor_id ) : false;
			if ( $editor ) {
				$editors[ $editor->display_name ] = true;
			}
		}

		if ( empty( $urls ) ) {
			return;
		}

		// One editor is shown by name; several are not attributed to anyone.
		$editor_name = 1 === count( $editors ) ? mb_substr( (string) array_key_first( $editors ), 0, 100 ) : null;

		foreach ( array_chunk( $urls, self::MAX_URLS_PER_REQUEST ) as $chunk ) {
			$response = WebChangeDetector_API_V2::trigger_monitoring_check_v2( $group_uuid, self::TYPE_POST_SAVE, $chunk, $editor_name );

			if ( is_array( $response ) && isset( $response['reason'] ) && 'trigger_not_enabled' === $response['reason'] ) {
				// Switched off elsewhere (e.g. in the web app): stop sending until the settings change.
				set_transient( self::TRANSIENT_ENABLED, '0', self::ENABLED_TTL );
				return;
			}

			WebChangeDetector_Admin_Utils::log_error( 'Monitoring trigger response: ' . wp_json_encode( $response ), 'monitoring_trigger', 'debug' );
		}
	}

	/**
	 * Whether the monitoring group accepts saved posts, refreshing the cached flag from the API if needed.
	 *
	 * @param string $group_uuid The monitoring group UUID.
	 * @return bool
	 */
	private function is_enabled( $group_uuid ) {
		$cached = self::is_enabled_cached();
		if ( null !== $cached ) {
			return $cached;
		}

		$response = WebChangeDetector_API_V2::get_group_v2( $group_uuid );
		if ( ! is_array( $response ) || empty( $response['data'] ) || ! is_array( $response['data'] ) ) {
			return false; // Unknown state: do not send, the next save retries.
		}

		return self::remember_group_settings( $response['data'] );
	}

	/**
	 * The cached flag: true / false, or null when unknown or expired.
	 *
	 * @return bool|null
	 */
	public static function is_enabled_cached() {
		$cached = get_transient( self::TRANSIENT_ENABLED );

		if ( false === $cached ) {
			return null;
		}

		return '1' === $cached;
	}

	/**
	 * Cache whether the given monitoring group accepts saved posts.
	 *
	 * Called with every monitoring group the plugin loads or saves.
	 *
	 * @param array $group Group data as returned by the API.
	 * @return bool The cached value.
	 */
	public static function remember_group_settings( $group ) {
		$enabled = self::group_accepts_post_save( $group );
		set_transient( self::TRANSIENT_ENABLED, $enabled ? '1' : '0', self::ENABLED_TTL );

		return $enabled;
	}

	/**
	 * The `triggers` value for a group update.
	 *
	 * Group updates are sent form-encoded, which drops empty arrays, so "no triggers" is
	 * sent as an empty string (the API treats it as "remove all triggers").
	 *
	 * @param bool $post_save Whether the post_save trigger is on.
	 * @return array|string
	 */
	public static function triggers_for_api( $post_save ) {
		return $post_save ? array( array( 'type' => self::TYPE_POST_SAVE ) ) : '';
	}

	/**
	 * Whether the group data has the post_save trigger and is an enabled monitoring group.
	 *
	 * @param array $group Group data as returned by the API.
	 * @return bool
	 */
	public static function group_accepts_post_save( $group ) {
		if ( ! is_array( $group ) || empty( $group['monitoring'] ) || empty( $group['enabled'] ) ) {
			return false;
		}

		return self::has_post_save_trigger( $group );
	}

	/**
	 * Whether the group data contains the post_save trigger, independent of the enabled state.
	 *
	 * @param array $group Group data as returned by the API.
	 * @return bool
	 */
	public static function has_post_save_trigger( $group ) {
		$triggers = ( is_array( $group ) && isset( $group['triggers'] ) && is_array( $group['triggers'] ) ) ? $group['triggers'] : array();

		foreach ( $triggers as $trigger ) {
			if ( is_array( $trigger ) && isset( $trigger['type'] ) && self::TYPE_POST_SAVE === $trigger['type'] ) {
				return true;
			}
		}

		return false;
	}
}
