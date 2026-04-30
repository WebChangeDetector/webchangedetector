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

// Include the multisite helper so we can share the canonical network options list.
require_once plugin_dir_path( __FILE__ ) . 'admin/class-webchangedetector-multisite.php';

// Per-site options to delete.
$wcd_site_options_to_delete = array(
	'webchangedetector_website_id',
	'webchangedetector_debug_logging',
	'webchangedetector_health_status',
	'wcd_website_groups',
	'wcd_wizard',
	'wcd_initial_setup_needed',
	'wcd_allowances',
	'wcd_auto_update_history',
	'wcd_manual_checks_batch',
	'wcd_manual_checks_pre_batch',
	'wcd_manual_checks_post_batch',
	'wcd_manual_checks_status',
	'wcd_manual_checks_started_at',
	'webchangedetector_update_detection_step',
	'wcd_pre_auto_update',
	'wcd_post_auto_update',
	'wcd_auto_updates_running',
	'wcd_wordpress_cron',
	'auto_updater.lock',
	'wcd_disable_admin_bar_menu',
);

// Network-wide options to delete (single source of truth: multisite helper).
$wcd_network_options_to_delete = \WebChangeDetector\WebChangeDetector_Multisite::NETWORK_OPTIONS;

/**
 * Clean up a single site's data.
 */
function wcd_uninstall_site_cleanup() {
	global $wcd_site_options_to_delete;

	// Drop database tables.
	\WebChangeDetector\WebChangeDetector_Database_Logger::drop_table();

	// Delete per-site options.
	foreach ( $wcd_site_options_to_delete as $wcd_option ) {
		delete_option( $wcd_option );
	}

	// Also delete legacy options that may exist on single-site installs.
	delete_option( 'webchangedetector_api_token' );
	delete_option( 'webchangedetector_account_email' );
	delete_option( 'wcd_upgrade_url' );

	// Clean up cron jobs.
	wp_clear_scheduled_hook( 'wcd_sync_auto_update_schedule' );
	wp_clear_scheduled_hook( 'wcd_wp_version_check' );
	wp_clear_scheduled_hook( 'wp_maybe_auto_update' );
	wp_clear_scheduled_hook( 'wcd_cron_check_post_queues' );
	wp_clear_scheduled_hook( 'wcd_daily_sync_event' );
}

// Handle multisite cleanup.
if ( is_multisite() ) {
	// Clean up each site in the network. Include archived/deleted/spam sites so
	// their plugin data does not remain as orphan rows in wp_options.
	$wcd_sites = get_sites(
		array(
			'number'   => 0,
			'archived' => null,
			'deleted'  => null,
			'spam'     => null,
			'public'   => null,
		)
	);
	foreach ( $wcd_sites as $wcd_site ) {
		switch_to_blog( $wcd_site->blog_id );
		wcd_uninstall_site_cleanup();
		restore_current_blog();
	}

	// Clean up network-wide options.
	foreach ( $wcd_network_options_to_delete as $wcd_option ) {
		delete_site_option( $wcd_option );
	}
} else {
	// Single-site cleanup.
	wcd_uninstall_site_cleanup();
}
