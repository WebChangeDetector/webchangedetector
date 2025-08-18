<?php
/**
 * Fired during plugin deactivation
 *
 * @link       wp-mike.com
 * @since      1.0.0
 *
 * @package    WebChangeDetector
 * @subpackage WebChangeDetector/includes
 */

namespace WebChangeDetector;

/**
 * Fired during plugin deactivation.
 *
 * This class defines all code necessary to run during the plugin's deactivation.
 *
 * @since      1.0.0
 * @package    WebChangeDetector
 * @subpackage WebChangeDetector/includes
 * @author     Mike Miler <mike@wp-mike.com>
 */
class WebChangeDetector_Deactivator {

	/**
	 * Clean up scheduled cron jobs and auto-update state on deactivation.
	 *
	 * Removes all scheduled cron jobs and clears stuck auto-update options
	 * to ensure clean state when plugin is reactivated.
	 *
	 * @since    1.0.0
	 */
	public static function deactivate() {
		// Clear all plugin cron jobs.
		wp_clear_scheduled_hook( 'wcd_sync_auto_update_schedule' );
		wp_clear_scheduled_hook( 'wcd_wp_version_check' );
		wp_clear_scheduled_hook( 'wp_maybe_auto_update' );
		wp_clear_scheduled_hook( 'wcd_cron_check_post_queues' );

		// Clean up auto-update state options to prevent stuck state on reactivation.
		// These constants might not be defined, so we use the actual option names.
		delete_option( 'wcd_pre_auto_update' );
		delete_option( 'wcd_post_auto_update' );
		delete_option( 'wcd_auto_updates_running' );
		delete_option( 'wcd_wordpress_cron' );

		// IMPORTANT: Clean up WordPress auto-updater lock if it exists.
		// This prevents WordPress auto-updates from being permanently blocked.
		$lock = get_option( 'auto_updater.lock' );
		if ( $lock ) {
			delete_option( 'auto_updater.lock' );
			if ( class_exists( '\WebChangeDetector\WebChangeDetector_Admin_Utils' ) ) {
				\WebChangeDetector\WebChangeDetector_Admin_Utils::log_error(
					'Removed auto_updater.lock during deactivation to prevent stuck WordPress updates.',
					'deactivator',
					'info'
				);
			}
		}

		// Note: We don't drop the logs table on deactivation to preserve log history.
		// Database tables are only cleaned up during uninstall.

		// Log the cleanup for debugging.
		if ( class_exists( '\WebChangeDetector\WebChangeDetector_Admin_Utils' ) ) {
			\WebChangeDetector\WebChangeDetector_Admin_Utils::log_error(
				'Plugin deactivated. Cleared all cron jobs and auto-update state.',
				'deactivator',
				'info'
			);
		}
	}
}
