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
	 * Clean up scheduled cron jobs on deactivation.
	 *
	 * Removes all scheduled cron jobs created by the plugin.
	 *
	 * @since    1.0.0
	 */
	public static function deactivate() {
		// Clear the auto-update sync cron
		wp_clear_scheduled_hook( 'wcd_sync_auto_update_schedule' );
		
		// Clear other plugin crons
		wp_clear_scheduled_hook( 'wcd_wp_version_check' );
	}
}
