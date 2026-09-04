<?php
/**
 * Update results captured for a check batch.
 *
 * @package    WebChangeDetector
 * @subpackage WebChangeDetector/admin
 */

namespace WebChangeDetector;

defined( 'ABSPATH' ) || exit;

/**
 * Builds the "what was updated" payload the API stores per batch.
 *
 * Two producers feed the same contract shape:
 * - Auto Update Checks parse WordPress' own update results (see
 *   WebChangeDetector_Autoupdates::parse_update_results()).
 * - On-Demand Checks have no update results at all, so the installed versions are
 *   snapshotted with the pre batch and diffed against the post batch here.
 *
 * build_api_payload() is the single place that normalizes an entry into the shape the
 * API validates: the `core`, `plugins` and `themes` keys are always present (an idle run
 * sends `null` plus two empty arrays), `success` stays a real boolean, and version/name
 * strings are truncated to the API's limits instead of risking a 422.
 */
class WebChangeDetector_Update_Results {

	/**
	 * Statuses the API accepts for the summary.
	 *
	 * @var string[]
	 */
	const VALID_STATUS = array( 'completed', 'completed_with_errors', 'failed' );

	/**
	 * Maximum number of plugin resp. theme entries the API accepts.
	 *
	 * @var int
	 */
	const MAX_ITEMS = 300;

	/**
	 * Maximum length of a version string accepted by the API.
	 *
	 * @var int
	 */
	const MAX_VERSION_LENGTH = 50;

	/**
	 * Maximum length of a slug resp. name accepted by the API.
	 *
	 * @var int
	 */
	const MAX_NAME_LENGTH = 255;

	/**
	 * Maximum length of a single upgrader message.
	 *
	 * @var int
	 */
	const MAX_MESSAGE_LENGTH = 300;

	/**
	 * Maximum number of upgrader messages the API accepts per item.
	 *
	 * @var int
	 */
	const MAX_MESSAGES = 20;

	/**
	 * Capture the currently installed core, plugin and theme versions.
	 *
	 * Plugins are keyed by their plugin file, themes by their stylesheet, which is what
	 * WebChangeDetector_Autoupdates::parse_update_results() looks up. The additional
	 * `core` key is ignored there and only used by diff_captured_versions().
	 *
	 * @return array {
	 *     @type string $core    The WordPress core version.
	 *     @type array  $plugins Version per plugin file.
	 *     @type array  $themes  Version per stylesheet.
	 * }
	 */
	public static function capture_current_versions() {
		$versions = array(
			'core'    => get_bloginfo( 'version' ),
			'plugins' => array(),
			'themes'  => array(),
		);

		// Ensure get_plugins function is available.
		if ( ! function_exists( 'get_plugins' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		// Capture all plugin versions.
		if ( function_exists( 'get_plugins' ) ) {
			$all_plugins = get_plugins();
			foreach ( $all_plugins as $plugin_file => $plugin_data ) {
				if ( isset( $plugin_data['Version'] ) ) {
					$versions['plugins'][ $plugin_file ] = $plugin_data['Version'];
				}
			}
		}

		// Capture all theme versions.
		$all_themes = wp_get_themes();
		foreach ( $all_themes as $stylesheet => $theme ) {
			$versions['themes'][ $stylesheet ] = $theme->get( 'Version' );
		}

		\WebChangeDetector\WebChangeDetector_Admin_Utils::log_error(
			'Captured ' . count( $versions['plugins'] ) . ' plugin versions and ' . count( $versions['themes'] ) . ' theme versions',
			'capture_current_versions',
			'debug'
		);

		return $versions;
	}

	/**
	 * Diff two version snapshots taken by capture_current_versions().
	 *
	 * Only items present in BOTH snapshots with a differing version are reported: an item
	 * that was installed or deleted between the snapshots is not an update. Names are read
	 * at post time because that is when the new version is on disk.
	 *
	 * @param array $pre  Snapshot taken before the changes.
	 * @param array $post Snapshot taken after the changes.
	 * @return array Updates in the API contract shape: core (array|null), plugins, themes.
	 */
	public static function diff_captured_versions( $pre, $post ) {
		$updates = array(
			'core'    => null,
			'plugins' => array(),
			'themes'  => array(),
		);

		if ( ! is_array( $pre ) || ! is_array( $post ) ) {
			return $updates;
		}

		if ( ! empty( $pre['core'] ) && ! empty( $post['core'] ) && $pre['core'] !== $post['core'] ) {
			$updates['core'] = array(
				'attempted'    => true,
				'success'      => true,
				'from_version' => $pre['core'],
				'to_version'   => $post['core'],
				'error'        => null,
				'messages'     => array(),
			);
		}

		if ( ! function_exists( 'get_plugins' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}
		$all_plugins = function_exists( 'get_plugins' ) ? get_plugins() : array();

		foreach ( self::changed_versions( $pre['plugins'] ?? array(), $post['plugins'] ?? array() ) as $plugin_file => $version ) {
			$updates['plugins'][] = array(
				'slug'         => self::plugin_slug( $plugin_file ),
				'name'         => $all_plugins[ $plugin_file ]['Name'] ?? $plugin_file,
				'from_version' => $version['from'],
				'to_version'   => $version['to'],
				'success'      => true,
				'error'        => null,
				'messages'     => array(),
			);
		}

		$all_themes = wp_get_themes();

		foreach ( self::changed_versions( $pre['themes'] ?? array(), $post['themes'] ?? array() ) as $stylesheet => $version ) {
			$updates['themes'][] = array(
				'slug'         => $stylesheet,
				'name'         => isset( $all_themes[ $stylesheet ] ) ? $all_themes[ $stylesheet ]->get( 'Name' ) : $stylesheet,
				'from_version' => $version['from'],
				'to_version'   => $version['to'],
				'success'      => true,
				'error'        => null,
				'messages'     => array(),
			);
		}

		return $updates;
	}

	/**
	 * Build a full update-results entry from a diff.
	 *
	 * A version diff only ever shows what actually changed, so every entry counts as a
	 * successful update and the run status is always "completed".
	 *
	 * @param array $updates Updates as returned by diff_captured_versions().
	 * @return array Entry with timestamp, updates and summary.
	 */
	public static function build_entry_from_diff( $updates ) {
		$total = ( null === $updates['core'] ? 0 : 1 ) + count( $updates['plugins'] ) + count( $updates['themes'] );

		return array(
			'timestamp' => time(),
			'updates'   => $updates,
			'summary'   => array(
				'total_attempted' => $total,
				'successful'      => $total,
				'failed'          => 0,
				'status'          => 'completed',
			),
		);
	}

	/**
	 * Normalize an entry into the exact body the API validates.
	 *
	 * Drops the local-only keys (`batch_id`, `context`), guarantees every required key is
	 * present, keeps booleans as booleans and truncates strings to the API's limits.
	 *
	 * @param array $entry Update-results entry (history entry or diff entry).
	 * @return array The request body.
	 */
	public static function build_api_payload( array $entry ) {
		$updates = isset( $entry['updates'] ) && is_array( $entry['updates'] ) ? $entry['updates'] : array();
		$summary = isset( $entry['summary'] ) && is_array( $entry['summary'] ) ? $entry['summary'] : array();
		$status  = isset( $summary['status'] ) ? (string) $summary['status'] : '';

		return array(
			'timestamp' => isset( $entry['timestamp'] ) ? (int) $entry['timestamp'] : time(),
			'updates'   => array(
				'core'    => empty( $updates['core'] ) || ! is_array( $updates['core'] ) ? null : self::sanitize_core( $updates['core'] ),
				'plugins' => self::sanitize_items( $updates['plugins'] ?? array() ),
				'themes'  => self::sanitize_items( $updates['themes'] ?? array() ),
			),
			'summary'   => array(
				'total_attempted' => max( 0, (int) ( $summary['total_attempted'] ?? 0 ) ),
				'successful'      => max( 0, (int) ( $summary['successful'] ?? 0 ) ),
				'failed'          => max( 0, (int) ( $summary['failed'] ?? 0 ) ),
				'status'          => in_array( $status, self::VALID_STATUS, true ) ? $status : 'completed',
			),
		);
	}

	/**
	 * Find entries present in both snapshots whose version changed.
	 *
	 * @param array $pre  Version per key before.
	 * @param array $post Version per key after.
	 * @return array Key => array with `from` and `to` version.
	 */
	private static function changed_versions( $pre, $post ) {
		$changed = array();

		if ( ! is_array( $pre ) || ! is_array( $post ) ) {
			return $changed;
		}

		foreach ( $pre as $key => $from_version ) {
			if ( ! isset( $post[ $key ] ) || (string) $post[ $key ] === (string) $from_version ) {
				continue;
			}
			$changed[ $key ] = array(
				'from' => (string) $from_version,
				'to'   => (string) $post[ $key ],
			);
		}

		return $changed;
	}

	/**
	 * Derive the plugin slug from a plugin file.
	 *
	 * Single-file plugins (e.g. "hello.php") have no directory, so the file name is used.
	 *
	 * @param string $plugin_file The plugin file relative to the plugins directory.
	 * @return string
	 */
	private static function plugin_slug( $plugin_file ) {
		$dir = dirname( $plugin_file );

		return '.' === $dir ? basename( $plugin_file, '.php' ) : $dir;
	}

	/**
	 * Normalize the core entry.
	 *
	 * @param array $core Raw core entry.
	 * @return array
	 */
	private static function sanitize_core( $core ) {
		return array(
			'attempted'    => ! empty( $core['attempted'] ),
			'success'      => ! empty( $core['success'] ),
			'from_version' => self::sanitize_string( $core['from_version'] ?? null, self::MAX_VERSION_LENGTH ),
			'to_version'   => self::sanitize_string( $core['to_version'] ?? null, self::MAX_VERSION_LENGTH ),
			'error'        => self::sanitize_string( $core['error'] ?? null, self::MAX_MESSAGE_LENGTH ),
			'messages'     => self::sanitize_messages( $core['messages'] ?? array() ),
		);
	}

	/**
	 * Normalize a list of plugin resp. theme entries.
	 *
	 * Entries without a slug are dropped: the API requires it per item.
	 *
	 * @param mixed $items Raw entries.
	 * @return array
	 */
	private static function sanitize_items( $items ) {
		if ( ! is_array( $items ) ) {
			return array();
		}

		$sanitized = array();
		foreach ( array_slice( $items, 0, self::MAX_ITEMS ) as $item ) {
			if ( ! is_array( $item ) || empty( $item['slug'] ) ) {
				continue;
			}
			$slug = self::sanitize_string( $item['slug'], self::MAX_NAME_LENGTH );
			if ( null === $slug ) {
				continue;
			}
			$sanitized[] = array(
				'slug'         => $slug,
				'name'         => self::sanitize_string( $item['name'] ?? null, self::MAX_NAME_LENGTH ),
				'from_version' => self::sanitize_string( $item['from_version'] ?? null, self::MAX_VERSION_LENGTH ),
				'to_version'   => self::sanitize_string( $item['to_version'] ?? null, self::MAX_VERSION_LENGTH ),
				'success'      => ! empty( $item['success'] ),
				'error'        => self::sanitize_string( $item['error'] ?? null, self::MAX_MESSAGE_LENGTH ),
				'messages'     => self::sanitize_messages( $item['messages'] ?? array() ),
			);
		}

		return $sanitized;
	}

	/**
	 * Normalize the upgrader messages.
	 *
	 * They may carry the restricted HTML WordPress' upgrader skin produces (a, br, em,
	 * strong); the API escapes them on render, so the markup is kept as is here.
	 *
	 * sanitize_text_field() is deliberately NOT applied (do not "fix" this later): it would
	 * strip that markup out of the messages the API renders. The only transport risk left is
	 * invalid UTF-8, and wp_json_encode() already guards the request body against that.
	 *
	 * @param mixed $messages Raw messages.
	 * @return string[] At most MAX_MESSAGES entries, each truncated to MAX_MESSAGE_LENGTH.
	 */
	private static function sanitize_messages( $messages ) {
		if ( ! is_array( $messages ) ) {
			return array();
		}

		$sanitized = array();
		foreach ( $messages as $message ) {
			if ( ! is_scalar( $message ) || '' === trim( (string) $message ) ) {
				continue;
			}
			$sanitized[] = mb_substr( (string) $message, 0, self::MAX_MESSAGE_LENGTH );
		}

		// The LAST messages survive the cap, like WebChangeDetector_Autoupdates::extract_failure_messages()
		// already does upstream: the upgrader skin appends its progress feedback first and the
		// actual failure reason last, so cutting from the front would drop the useful part.
		return array_slice( $sanitized, -self::MAX_MESSAGES );
	}

	/**
	 * Sanitize and truncate an optional string.
	 *
	 * @param mixed $value  Raw value.
	 * @param int   $length Maximum length.
	 * @return string|null
	 */
	private static function sanitize_string( $value, $length ) {
		if ( null === $value || ! is_scalar( $value ) ) {
			return null;
		}

		$value = sanitize_text_field( (string) $value );

		return '' === $value ? null : mb_substr( $value, 0, $length );
	}
}
