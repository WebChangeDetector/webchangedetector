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
 * A person saving a published post with a real change puts the post into a small
 * pending list. At the end of the same request (late `shutdown` callback) all
 * pending posts are sent in one request to POST /v2/monitoring/trigger, so a bulk
 * edit is still one API request. The browser response is flushed first where the
 * server supports it (PHP-FPM, LiteSpeed), so the save is not slowed down; without
 * that, the send uses a short timeout. The API waits a few minutes (page caches) and
 * merges further saves of the same page into one check, so the plugin does not
 * debounce on its own. Reports that fail for a temporary reason (rate limit, server
 * error, network) are retried a few times via WP-Cron (CRON_HOOK), which is only
 * used for retries: WP-Cron needs site traffic, a first send must not wait for it.
 *
 * A "real change" is a changed title, content or excerpt, or a changed page builder
 * layout / featured image (post meta, see META_KEYS). Saves without a logged-in
 * user (cron, WP-CLI, imports, scheduled jobs of other plugins) do not trigger by
 * default, so bulk syncs cannot use up checks; both are filterable.
 *
 * The save hooks make no API call. Whether the monitoring group reacts to saved
 * posts is cached in a transient; the send refreshes it when it expired and the
 * settings page refreshes it whenever the group is loaded or saved.
 *
 * Autosaves (block editor, classic editor, page builder drafts) never start a check.
 * They only push back the check of a page that is already waiting, via POST
 * /v2/monitoring/trigger/extend, so the check runs after the editing session is
 * quiet. At most one extension per post is sent per EXTEND_THROTTLE; failed
 * extensions are dropped, never retried.
 */
class WebChangeDetector_Monitoring_Trigger {

	/**
	 * Trigger type sent to the API.
	 *
	 * @var string
	 */
	const TYPE_POST_SAVE = 'post_save';

	/**
	 * Cron hook that retries pending posts after a failed send.
	 *
	 * @var string
	 */
	const CRON_HOOK = 'wcd_send_monitoring_triggers';

	/**
	 * Option holding the pending posts (post ID => array with editor user ID and send attempts).
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
	 * Transient holding the current storm window (start time and queued posts).
	 *
	 * @var string
	 */
	const TRANSIENT_STORM = 'wcd_monitoring_trigger_storm';

	/**
	 * Transient holding the posts an extension was queued for recently (post ID => time).
	 *
	 * @var string
	 */
	const TRANSIENT_EXTENDED = 'wcd_monitoring_trigger_extended';

	/**
	 * Minimum seconds between two extensions of the same post.
	 *
	 * The API adds this to its wait, so at least its regular wait stays quiet.
	 *
	 * @var int
	 */
	const EXTEND_THROTTLE = 2 * MINUTE_IN_SECONDS;

	/**
	 * Maximum posts queued per storm window (bulk edits by a person).
	 *
	 * @var int
	 */
	const STORM_MAX = 50;

	/**
	 * Length of the fixed storm window in seconds.
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
	 * How long a "group accepts saved posts" flag is trusted.
	 *
	 * @var int
	 */
	const ENABLED_TTL = 12 * HOUR_IN_SECONDS;

	/**
	 * How long a "group does not accept saved posts" flag is trusted.
	 *
	 * Short, so enabling the trigger in the web app takes effect soon.
	 *
	 * @var int
	 */
	const DISABLED_TTL = 30 * MINUTE_IN_SECONDS;

	/**
	 * Request timeout in seconds for a send that blocks the browser response (the
	 * response could not be flushed first). Same as the WordPress HTTP API default.
	 *
	 * @var int
	 */
	const BLOCKING_TIMEOUT = 5;

	/**
	 * Seconds before a failed report is sent again.
	 *
	 * @var int
	 */
	const RETRY_DELAY = 5 * MINUTE_IN_SECONDS;

	/**
	 * Send attempts per post before it is dropped.
	 *
	 * @var int
	 */
	const MAX_ATTEMPTS = 3;

	/**
	 * Post meta keys whose change counts as a content change (page builder layouts,
	 * featured image). Filterable via `wcd_monitoring_trigger_meta_keys`.
	 *
	 * @var string[]
	 */
	const META_KEYS = array(
		'_thumbnail_id',
		'_elementor_data',
		'_elementor_page_settings',
		'_bricks_page_content_2',
		'_bricks_page_header_2',
		'_bricks_page_footer_2',
		'_bricks_page_settings',
		'_fl_builder_data',
		'_fl_builder_data_settings',
		'ct_builder_json',
		'ct_builder_shortcodes',
	);

	/**
	 * Post meta keys of page builder drafts (an unpublished edit of a live page). A change
	 * counts as an autosave, never as a save, so these keys must never go into META_KEYS.
	 * Filterable via `wcd_monitoring_trigger_draft_meta_keys`.
	 *
	 * @var string[]
	 */
	const DRAFT_META_KEYS = array(
		'_fl_builder_draft',
		'_fl_builder_draft_settings',
	);

	/**
	 * The admin instance (for the monitoring group UUID).
	 *
	 * @var WebChangeDetector_Admin
	 */
	private $admin;

	/**
	 * Whether the shutdown send is registered for this request.
	 *
	 * @var bool
	 */
	private $send_registered = false;

	/**
	 * Constructor.
	 *
	 * @param WebChangeDetector_Admin $admin The admin instance.
	 */
	public function __construct( $admin ) {
		$this->admin = $admin;
	}

	/**
	 * Hook: wp_after_insert_post. Queue the post when a published post really changed,
	 * or an extension when this is an autosave of a published post.
	 *
	 * @param int           $post_id     Post ID.
	 * @param \WP_Post      $post        Post object after the save.
	 * @param bool          $update      Whether this is an existing post being updated.
	 * @param \WP_Post|null $post_before Post object before the save, null for new posts.
	 * @return void
	 */
	public function on_after_insert_post( $post_id, $post, $update, $post_before ) {
		// Every autosave passes here (also the first REST autosave, which is a new post).
		$parent_id = $post instanceof \WP_Post ? wp_is_post_autosave( $post ) : false;
		if ( $parent_id ) {
			$this->on_autosave( $parent_id );
			return;
		}

		if ( ! $update || ! $post instanceof \WP_Post || ! $post_before instanceof \WP_Post ) {
			return; // A new post has no URL in the monitoring group yet.
		}

		// Only a page that was and still is live can be compared with its last screenshot.
		// Trashing or unpublishing would produce a 404 screenshot and a 100% diff.
		if ( 'publish' !== $post_before->post_status || ! $this->is_monitorable( $post_id, $post ) ) {
			return;
		}

		$should = ! self::is_automated_save() && self::has_content_changed( $post, $post_before );

		/**
		 * Filters whether saving this post starts a monitoring check.
		 *
		 * Default: true for a save by a logged-in user that changed the title, content or
		 * excerpt. Saves from cron, WP-CLI or without a user are false by default.
		 *
		 * @param bool     $should      Whether a check is started.
		 * @param \WP_Post $post        Post object after the save.
		 * @param \WP_Post $post_before Post object before the save.
		 */
		if ( apply_filters( 'wcd_should_trigger_monitoring_check', $should, $post, $post_before ) ) {
			$this->enqueue( $post_id );
		}
	}

	/**
	 * Hooks: added_post_meta / updated_post_meta. Queue the post when a page builder
	 * layout or the featured image of a published post changed.
	 *
	 * WordPress only fires updated_post_meta when the value really changed.
	 *
	 * @param int    $meta_id   Meta ID.
	 * @param int    $post_id   Post ID.
	 * @param string $meta_key  Meta key.
	 * @return void
	 */
	public function on_post_meta_change( $meta_id, $post_id, $meta_key ) {
		/**
		 * Filters the post meta keys whose change starts a monitoring check.
		 *
		 * @param string[] $keys Meta keys.
		 */
		$keys = (array) apply_filters( 'wcd_monitoring_trigger_meta_keys', self::META_KEYS );
		if ( ! in_array( $meta_key, $keys, true ) ) {
			return;
		}

		$post = get_post( $post_id );
		if ( ! $post instanceof \WP_Post || ! $this->is_monitorable( $post_id, $post ) || self::is_automated_save() ) {
			return;
		}

		$this->enqueue( $post_id );
	}

	/**
	 * Hook: updated_post_meta. A changed page builder draft counts as an autosave.
	 *
	 * Not on added_post_meta: opening the builder fills an empty draft with the live
	 * layout, which is no edit.
	 *
	 * @param int    $meta_id   Meta ID.
	 * @param int    $post_id   Post ID.
	 * @param string $meta_key  Meta key.
	 * @return void
	 */
	public function on_draft_meta_change( $meta_id, $post_id, $meta_key ) {
		/**
		 * Filters the page builder draft meta keys whose change counts as an autosave.
		 *
		 * @param string[] $keys Meta keys.
		 */
		$keys = (array) apply_filters( 'wcd_monitoring_trigger_draft_meta_keys', self::DRAFT_META_KEYS );
		if ( in_array( $meta_key, $keys, true ) ) {
			$this->on_autosave( (int) $post_id );
		}
	}

	/**
	 * An autosave of a published post: queue an extension of its waiting check.
	 *
	 * Never starts a check. Sent at most once per EXTEND_THROTTLE per post.
	 *
	 * @param int $post_id ID of the autosaved (parent) post.
	 * @return void
	 */
	private function on_autosave( $post_id ) {
		$post = get_post( $post_id );
		if ( ! $post instanceof \WP_Post || ! $this->is_monitorable( $post_id, $post ) || self::is_automated_save() ) {
			return;
		}

		if ( self::take_extend_slot( $post_id ) ) {
			$this->enqueue( $post_id, true );
		}
	}

	/**
	 * Whether an extension may be queued for the post now; if so, remembers it.
	 *
	 * @param int $post_id Post ID.
	 * @return bool
	 */
	private static function take_extend_slot( $post_id ) {
		$now      = time();
		$extended = get_transient( self::TRANSIENT_EXTENDED );
		$extended = is_array( $extended ) ? $extended : array();

		// The transient lives as long as its newest entry, so older entries expire here.
		$extended = array_filter(
			$extended,
			function ( $time ) use ( $now ) {
				return $now - (int) $time < self::EXTEND_THROTTLE;
			}
		);
		if ( isset( $extended[ $post_id ] ) ) {
			return false;
		}

		$extended[ $post_id ] = $now;
		set_transient( self::TRANSIENT_EXTENDED, $extended, self::EXTEND_THROTTLE );

		return true;
	}

	/**
	 * Checks shared by both save paths: a live, public, non-revision post while the
	 * group is not known to reject saved posts.
	 *
	 * @param int      $post_id Post ID.
	 * @param \WP_Post $post    Post object.
	 * @return bool
	 */
	private function is_monitorable( $post_id, $post ) {
		if ( wp_is_post_revision( $post_id ) || wp_is_post_autosave( $post_id ) ) {
			return false;
		}

		if ( defined( 'WP_IMPORTING' ) && WP_IMPORTING ) {
			return false;
		}

		if ( 'publish' !== $post->post_status || ! is_post_type_viewable( $post->post_type ) ) {
			return false;
		}

		return false !== self::is_enabled_cached();
	}

	/**
	 * Whether the current save was not made by a person: cron, WP-CLI, or no logged-in user.
	 *
	 * @return bool
	 */
	public static function is_automated_save() {
		return wp_doing_cron()
			|| ( defined( 'WP_CLI' ) && WP_CLI )
			|| 0 === get_current_user_id();
	}

	/**
	 * Put the post into the pending list and register the send at the end of the request.
	 *
	 * A pending save wins over an extension; a save replaces a pending extension.
	 * Only saves count against the storm cap.
	 *
	 * @param int  $post_id Post ID.
	 * @param bool $extend  True for an extension (autosave), false for a save.
	 * @return void
	 */
	private function enqueue( $post_id, $extend = false ) {
		// Registered first, so a save of an already pending post (stale entry or waiting
		// retry) still sends the pending list at the end of this request.
		if ( ! $this->send_registered ) {
			// Late priority: other shutdown output (e.g. Query Monitor) is done before the flush.
			add_action( 'shutdown', array( $this, 'send_on_shutdown' ), PHP_INT_MAX );
			$this->send_registered = true;
		}

		$pending = self::get_pending();

		// Already waiting: the API merges repeated saves anyway, nothing to add to the list.
		// Only a save replaces a pending extension.
		if ( isset( $pending[ $post_id ] ) && ( $extend || ! $pending[ $post_id ]['extend'] ) ) {
			return;
		}

		if ( ! $extend && ! self::take_storm_slot() ) {
			return;
		}

		$pending[ $post_id ] = array(
			'editor'   => get_current_user_id(),
			'attempts' => 0,
			'extend'   => (bool) $extend,
		);
		update_option( self::OPTION_PENDING, $pending, false );
	}

	/**
	 * Storm cap in a fixed window: a bulk edit must not queue hundreds of posts.
	 *
	 * @return bool Whether the save may be queued (counted when true).
	 */
	private static function take_storm_slot() {
		$storm = get_transient( self::TRANSIENT_STORM );
		if ( ! is_array( $storm ) || ! isset( $storm['start'], $storm['count'] ) || time() - (int) $storm['start'] >= self::STORM_WINDOW ) {
			$storm = array(
				'start' => time(),
				'count' => 0,
			);
		}
		if ( $storm['count'] >= self::STORM_MAX ) {
			return false;
		}
		++$storm['count'];
		set_transient( self::TRANSIENT_STORM, $storm, self::STORM_WINDOW );

		return true;
	}

	/**
	 * Hook: shutdown. Finish the browser response where possible, then send the pending posts.
	 *
	 * @return void
	 */
	public function send_on_shutdown() {
		$flushed = false;
		if ( function_exists( 'fastcgi_finish_request' ) ) {
			$flushed = fastcgi_finish_request();
		} elseif ( function_exists( 'litespeed_finish_request' ) ) {
			$flushed = litespeed_finish_request();
		}

		// Without a flush the browser waits for this request: keep it short.
		$this->send_pending( $flushed ? null : self::BLOCKING_TIMEOUT );
	}

	/**
	 * The pending list, normalized to post ID => array( 'editor' => int, 'attempts' => int, 'extend' => bool ).
	 *
	 * Entries without 'extend' (older builds) are saves.
	 *
	 * @return array
	 */
	private static function get_pending() {
		$pending = get_option( self::OPTION_PENDING, array() );
		if ( ! is_array( $pending ) ) {
			return array();
		}

		$normalized = array();
		foreach ( $pending as $post_id => $entry ) {
			$normalized[ (int) $post_id ] = is_array( $entry )
				? array(
					'editor'   => (int) ( $entry['editor'] ?? 0 ),
					'attempts' => (int) ( $entry['attempts'] ?? 0 ),
					'extend'   => ! empty( $entry['extend'] ),
				)
				: array(
					'editor'   => (int) $entry,
					'attempts' => 0,
					'extend'   => false,
				);
		}

		return $normalized;
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
	 * Send the pending posts to the API (end of the saving request, or a cron retry).
	 *
	 * @param int|null $timeout Request timeout in seconds, null for the default.
	 * @return void
	 */
	public function send_pending( $timeout = null ) {
		$pending = self::get_pending();
		delete_option( self::OPTION_PENDING );

		if ( empty( $pending ) ) {
			return;
		}

		$group_uuid = $this->admin->monitoring_group_uuid;
		if ( empty( $group_uuid ) ) {
			return;
		}

		$saves      = array_filter(
			$pending,
			function ( $entry ) {
				return ! $entry['extend'];
			}
		);
		$extensions = array_diff_key( $pending, $saves );

		$enabled = $this->is_enabled( $group_uuid );
		if ( null === $enabled ) {
			$this->retry( $saves ); // The group could not be loaded: try again later. Extensions are dropped.
			return;
		}
		if ( ! $enabled ) {
			return;
		}

		if ( $this->send_saves( $group_uuid, $saves, $timeout ) ) {
			$this->send_extensions( $group_uuid, $extensions, $timeout );
		}
	}

	/**
	 * The request items for pending posts that are still live: post ID => url, title, post ID.
	 *
	 * @param array $entries Pending entries (post ID => entry).
	 * @return array
	 */
	private function build_items( $entries ) {
		$items = array();
		foreach ( array_keys( $entries ) as $post_id ) {
			$post = get_post( $post_id );
			if ( ! $post || 'publish' !== $post->post_status ) {
				continue;
			}

			$permalink = get_permalink( $post );
			if ( ! $permalink ) {
				continue;
			}

			$title             = html_entity_decode( get_the_title( $post ), ENT_QUOTES, 'UTF-8' );
			$items[ $post_id ] = array(
				'url'     => $permalink,
				'title'   => mb_substr( wp_strip_all_tags( $title ), 0, 255 ),
				'post_id' => (int) $post_id,
			);
		}

		return $items;
	}

	/**
	 * Report saved posts (POST /v2/monitoring/trigger); temporary failures are retried.
	 *
	 * @param string   $group_uuid The monitoring group UUID.
	 * @param array    $entries    Pending save entries (post ID => entry).
	 * @param int|null $timeout    Request timeout in seconds, null for the default.
	 * @return bool False when the group no longer accepts saved posts.
	 */
	private function send_saves( $group_uuid, $entries, $timeout ) {
		foreach ( array_chunk( $this->build_items( $entries ), self::MAX_URLS_PER_REQUEST, true ) as $chunk ) {
			$chunk_pending = array_intersect_key( $entries, $chunk );
			$response      = WebChangeDetector_API_V2::trigger_monitoring_check_v2( $group_uuid, self::TYPE_POST_SAVE, array_values( $chunk ), self::editor_name( $chunk_pending ), $timeout );

			if ( is_array( $response ) && isset( $response['data']['accepted'] ) ) {
				WebChangeDetector_Admin_Utils::log_error( 'Monitoring trigger response: ' . wp_json_encode( $response ), 'monitoring_trigger', 'debug' );
				continue;
			}

			if ( self::handle_trigger_not_enabled( $response ) ) {
				return false;
			}

			WebChangeDetector_Admin_Utils::log_error( 'Monitoring trigger failed: ' . wp_json_encode( $response ), 'monitoring_trigger', 'error' );

			// Permanent failures (group gone, token invalid, rejected payload) are not retried.
			$permanent = in_array( $response, array( 'not found', 'unauthorized', 'update plugin' ), true )
				|| ( is_array( $response ) && isset( $response['errors'] ) );
			if ( ! $permanent ) {
				$this->retry( $chunk_pending );
			}
		}

		return true;
	}

	/**
	 * Push back the waiting checks of autosaved posts (POST /v2/monitoring/trigger/extend).
	 *
	 * Best effort: every failure (page not waiting, older API without the route, rate
	 * limit, network) is logged at debug level and dropped, never retried.
	 *
	 * @param string   $group_uuid The monitoring group UUID.
	 * @param array    $entries    Pending extension entries (post ID => entry).
	 * @param int|null $timeout    Request timeout in seconds, null for the default.
	 * @return void
	 */
	private function send_extensions( $group_uuid, $entries, $timeout ) {
		foreach ( array_chunk( $this->build_items( $entries ), self::MAX_URLS_PER_REQUEST ) as $chunk ) {
			$urls = array();
			foreach ( $chunk as $item ) {
				$urls[] = array( 'url' => $item['url'] );
			}

			$response = WebChangeDetector_API_V2::extend_monitoring_trigger_v2( $group_uuid, $urls, $timeout );
			if ( self::handle_trigger_not_enabled( $response ) ) {
				return;
			}

			WebChangeDetector_Admin_Utils::log_error( 'Monitoring trigger extension response: ' . wp_json_encode( $response ), 'monitoring_trigger', 'debug' );
		}
	}

	/**
	 * When the API says the group does not accept saved posts (switched off elsewhere, e.g. in
	 * the web app), stop sending until the settings change.
	 *
	 * @param mixed $response API response.
	 * @return bool Whether the response was that rejection.
	 */
	private static function handle_trigger_not_enabled( $response ) {
		if ( ! is_array( $response ) || ! isset( $response['reason'] ) || 'trigger_not_enabled' !== $response['reason'] ) {
			return false;
		}

		set_transient( self::TRANSIENT_ENABLED, '0', self::DISABLED_TTL );

		return true;
	}

	/**
	 * Put saved posts back into the pending list for another attempt, unless they failed too often.
	 *
	 * A retried save replaces an extension queued for the same post in the meantime.
	 *
	 * @param array $entries Pending entries (post ID => editor and attempts).
	 * @return void
	 */
	private function retry( $entries ) {
		$pending = self::get_pending();
		foreach ( $entries as $post_id => $entry ) {
			if ( ( isset( $pending[ $post_id ] ) && ! $pending[ $post_id ]['extend'] ) || $entry['attempts'] + 1 >= self::MAX_ATTEMPTS ) {
				continue;
			}
			$pending[ $post_id ] = array(
				'editor'   => $entry['editor'],
				'attempts' => $entry['attempts'] + 1,
				'extend'   => false,
			);
		}

		if ( empty( $pending ) ) {
			return;
		}

		update_option( self::OPTION_PENDING, $pending, false );
		if ( ! wp_next_scheduled( self::CRON_HOOK ) ) {
			wp_schedule_single_event( time() + self::RETRY_DELAY, self::CRON_HOOK );
		}
	}

	/**
	 * Display name of the editor when all posts were saved by the same person, else null.
	 *
	 * Never an email address (WordPress may use the login, often an email, as display name).
	 *
	 * @param array $entries Pending entries (post ID => editor and attempts).
	 * @return string|null
	 */
	private static function editor_name( $entries ) {
		$editor_ids = array_unique( array_filter( array_column( $entries, 'editor' ) ) );
		if ( 1 !== count( $editor_ids ) ) {
			return null;
		}

		$editor = get_userdata( reset( $editor_ids ) );
		if ( ! $editor || '' === $editor->display_name || is_email( $editor->display_name ) ) {
			return null;
		}

		return mb_substr( $editor->display_name, 0, 100 );
	}

	/**
	 * Whether the monitoring group accepts saved posts, refreshing the cached flag from the API if needed.
	 *
	 * @param string $group_uuid The monitoring group UUID.
	 * @return bool|null Null when the group could not be loaded.
	 */
	private function is_enabled( $group_uuid ) {
		$cached = self::is_enabled_cached();
		if ( null !== $cached ) {
			return $cached;
		}

		$response = WebChangeDetector_API_V2::get_group_v2( $group_uuid );
		if ( 'not found' === $response ) {
			return false;
		}
		if ( ! is_array( $response ) || empty( $response['data'] ) || ! is_array( $response['data'] ) ) {
			return null;
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
		set_transient( self::TRANSIENT_ENABLED, $enabled ? '1' : '0', $enabled ? self::ENABLED_TTL : self::DISABLED_TTL );

		return $enabled;
	}

	/**
	 * Remove everything this class stores (deactivation / uninstall).
	 *
	 * @return void
	 */
	public static function clear() {
		wp_clear_scheduled_hook( self::CRON_HOOK );
		delete_option( self::OPTION_PENDING );
		delete_transient( self::TRANSIENT_ENABLED );
		delete_transient( self::TRANSIENT_STORM );
		delete_transient( self::TRANSIENT_EXTENDED );
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
