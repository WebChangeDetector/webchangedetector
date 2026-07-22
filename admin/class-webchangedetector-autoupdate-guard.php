<?php
/**
 * Guard for externally disabled WP automatic updates.
 *
 * Hosting tools sometimes disable WP automatic updates globally via the
 * 'automatic_updater_disabled' filter or the AUTOMATIC_UPDATER_DISABLED
 * constant. Auto Update Checks depend on WP auto updates actually running,
 * so this class detects that state and, when the user opts in, overrides it.
 *
 * @package    WebChangeDetector
 * @subpackage WebChangeDetector/admin
 */

namespace WebChangeDetector;

defined( 'ABSPATH' ) || exit;

/**
 * Detects a disabled WP automatic updater and provides an opt-in override.
 */
class WebChangeDetector_Autoupdate_Guard {

	/**
	 * Option name for the opt-in override (per-site wp_option, boolean).
	 *
	 * @var string
	 */
	const OPTION = 'wcd_auto_update_force_enable';

	/**
	 * Register the override filter when the option is enabled.
	 *
	 * Must run on every request (including wp-cron) so the filter is live
	 * when core's automatic updater evaluates is_disabled().
	 *
	 * @return void
	 */
	public static function register_override() {
		self::sync_override( self::is_override_enabled() );
	}

	/**
	 * Add or remove the override filter to match the given state.
	 *
	 * Single place for the add/remove logic: used by register_override() at
	 * plugin load and by the settings save path, which must sync the filter
	 * within the same request (the settings page renders right after the POST
	 * without a redirect, so the load-time registration is stale by then).
	 * Multisite subsites never orchestrate updates (the network main site
	 * does, and WP core's updater exits on non-main sites anyway), so they
	 * are skipped.
	 *
	 * @param bool $enabled Whether the override should be active.
	 * @return void
	 */
	public static function sync_override( $enabled ) {
		if ( WebChangeDetector_Multisite::is_multisite_subsite() ) {
			return;
		}

		if ( $enabled ) {
			add_filter( 'automatic_updater_disabled', array( __CLASS__, 'filter_force_enable' ), PHP_INT_MAX );
		} else {
			remove_filter( 'automatic_updater_disabled', array( __CLASS__, 'filter_force_enable' ), PHP_INT_MAX );
		}
	}

	/**
	 * Filter callback forcing the WP automatic updater to stay enabled.
	 *
	 * Named callback (instead of __return_false) so get_status() can remove
	 * and re-add exactly this filter when evaluating the raw disabled state.
	 * Registered at PHP_INT_MAX, it runs last and its return value wins over
	 * both the AUTOMATIC_UPDATER_DISABLED constant and earlier filters.
	 *
	 * @return bool Always false (updater not disabled).
	 */
	public static function filter_force_enable() {
		return false;
	}

	/**
	 * Whether the user enabled the override option.
	 *
	 * @return bool True when the override option is on.
	 */
	public static function is_override_enabled() {
		return (bool) get_option( self::OPTION );
	}

	/**
	 * Get the disabled status of the WP automatic updater.
	 *
	 * @return array {
	 *     Status of the WP automatic updater.
	 *
	 *     @type bool   $file_mods_blocked  File modifications are blocked (e.g. DISALLOW_FILE_MODS); never overridable.
	 *     @type bool   $raw_disabled       Disabled state WITHOUT our override filter (replicates core's evaluation).
	 *     @type bool   $effective_disabled Real WP_Automatic_Updater::is_disabled() result WITH our filter in place.
	 *     @type string $cause              Internal cause for logging: 'file_mods', 'constant', 'filter' or '' when not disabled.
	 * }
	 */
	public static function get_status() {
		$file_mods_blocked = ! wp_is_file_mod_allowed( 'automatic_updater' );

		// Evaluate the raw state exactly like core does, but without our override filter.
		$had_override      = remove_filter( 'automatic_updater_disabled', array( __CLASS__, 'filter_force_enable' ), PHP_INT_MAX );
		$constant_disabled = defined( 'AUTOMATIC_UPDATER_DISABLED' ) && AUTOMATIC_UPDATER_DISABLED;
		$raw_disabled      = (bool) apply_filters( 'automatic_updater_disabled', $constant_disabled ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- Core hook, evaluated read-only like WP_Automatic_Updater::is_disabled() does.
		if ( $had_override ) {
			add_filter( 'automatic_updater_disabled', array( __CLASS__, 'filter_force_enable' ), PHP_INT_MAX );
		}

		// Effective state via the real core check, with our filter (if registered) back in place.
		$effective_disabled = $raw_disabled || $file_mods_blocked;
		if ( ! class_exists( '\WP_Automatic_Updater' ) ) {
			require_once ABSPATH . 'wp-admin/includes/class-wp-automatic-updater.php';
		}
		if ( class_exists( '\WP_Automatic_Updater' ) ) {
			$updater            = new \WP_Automatic_Updater();
			$effective_disabled = (bool) $updater->is_disabled();
		}

		$cause = '';
		if ( $file_mods_blocked ) {
			$cause = 'file_mods';
		} elseif ( $raw_disabled ) {
			$cause = $constant_disabled ? 'constant' : 'filter';
		}

		return array(
			'file_mods_blocked'  => $file_mods_blocked,
			'raw_disabled'       => $raw_disabled,
			'effective_disabled' => $effective_disabled,
			'cause'              => $cause,
		);
	}
}
