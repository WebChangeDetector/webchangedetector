<?php
/**
 * Fired when the plugin is uninstalled.
 *
 * When populating this file, consider the following flow
 * of control:
 *
 * - This method should be static
 * - Check if the $_REQUEST content actually is the plugin name
 * - Run an admin referrer check to make sure it goes through authentication
 * - Verify the output of $_GET makes sense
 * - Repeat with other user roles. Best directly by using the links/query string parameters.
 * - Repeat things for multisite. Once for a single site in the network, once sitewide.
 *
 * This file may be updated more in future version of the Boilerplate; however, this is the
 * general skeleton and outline for how the file should work.
 *
 * For more information, see the following discussion:
 * https://github.com/tommcfarlin/WordPress-Plugin-Boilerplate/pull/123#issuecomment-28541913
 *
 * @link       wp-mike.com
 * @since      1.0.0
 *
 * @package    WebChangeDetector
 */

// If uninstall not called from WordPress, then exit.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

// Include the database logger class for cleanup.
require_once plugin_dir_path( __FILE__ ) . 'admin/class-webchangedetector-database-logger.php';

// Clean up database tables.
\WebChangeDetector\WebChangeDetector_Database_Logger::drop_table();

// Clean up plugin options.
$options_to_delete = array(
	'webchangedetector_api_token',
	'webchangedetector_account_email',
	'webchangedetector_website_id',
	'webchangedetector_debug_logging',
	'webchangedetector_health_status',
	'wcd_website_groups',
	'wcd_wizard',
	'wcd_initial_setup_needed',
	'wcd_upgrade_url',
	'wcd_auto_update_history',
	'wcd_manual_checks_batch',
	'webchangedetector_update_detection_step',
);

foreach ( $options_to_delete as $option ) {
	delete_option( $option );
}

// Clean up any remaining cron jobs.
wp_clear_scheduled_hook( 'wcd_sync_auto_update_schedule' );
wp_clear_scheduled_hook( 'wcd_wp_version_check' );
wp_clear_scheduled_hook( 'wp_maybe_auto_update' );
wp_clear_scheduled_hook( 'wcd_cron_check_post_queues' );
wp_clear_scheduled_hook( 'wcd_daily_sync_event' );

// Clean up any remaining auto-update state.
delete_option( 'wcd_pre_auto_update' );
delete_option( 'wcd_post_auto_update' );
delete_option( 'wcd_auto_updates_running' );
delete_option( 'wcd_wordpress_cron' );
delete_option( 'auto_updater.lock' );
