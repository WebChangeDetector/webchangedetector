<?php
/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              webchangedetector.com
 * @since             0.1
 * @package           WebChangeDetector
 * @author            Mike Miler
 *
 * @wordpress-plugin
 * Plugin Name:       Web Change Detector
 * Text Domain:       webchangedetector
 * Plugin URI:        https://www.webchangedetector.com
 * Description:       Detect changes on your website visually before and after updating your website. You can also run automatic change detections and get notified on changes of your website.
 * Version:           4.3.1
 * GitHub Plugin URI: https://github.com/webchangedetector/webchangedetector
 * Primary Branch:    main
 * Author:            Mike Miler
 * Author URI:        https://www.webchangedetector.com
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       webchangedetector
 * Domain Path:       /languages
 */

namespace WebChangeDetector;

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Dynamically derive the current version from the plugin header so we only maintain it in one place.
 */
if ( ! defined( 'WEBCHANGEDETECTOR_VERSION' ) ) {
	if ( ! function_exists( 'get_file_data' ) ) {
		// Ensure get_file_data is available on the front-end.
		require_once ABSPATH . 'wp-admin/includes/plugin.php';
	}

	$wcd_plugin_data = get_file_data(
		__FILE__,
		array( 'Version' => 'Version' ),
		false
	);

	define( 'WEBCHANGEDETECTOR_VERSION', isset( $wcd_plugin_data['Version'] ) ? $wcd_plugin_data['Version'] : '0.0.0' );
}

/**
 * Define plugin path constants for consistent file inclusion.
 * These constants provide a secure and reliable way to reference plugin files.
 */
if ( ! defined( 'WCD_PLUGIN_FILE' ) ) {
	define( 'WCD_PLUGIN_FILE', __FILE__ );
}

if ( ! defined( 'WCD_PLUGIN_DIR' ) ) {
	define( 'WCD_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
}

if ( ! defined( 'WCD_PLUGIN_URL' ) ) {
	define( 'WCD_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
}

if ( ! defined( 'WCD_PLUGIN_BASENAME' ) ) {
	define( 'WCD_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );
}

/**
 * Set default branch preference for beta updates.
 * Only enabled if Git Updater plugin is active.
 * Can be overridden in wp-config.php by defining WCD_USE_DEV_BRANCH.
 */
if ( ! defined( 'WCD_USE_DEV_BRANCH' ) ) {
	// Check if Git Updater plugin is active.
	$wcd_git_updater_active = false;
	if ( function_exists( 'is_plugin_active' ) ) {
		$wcd_git_updater_active = is_plugin_active( 'git-updater/git-updater.php' );
	}

	define( 'WCD_USE_DEV_BRANCH', $wcd_git_updater_active );
}

/**
 * Git Updater filter to set the primary branch based on WCD_USE_DEV_BRANCH setting.
 * Only applies to this plugin (webchangedetector) and only when Git Updater is active.
 *
 * @param string $branch   The default branch.
 * @param string $slug     The plugin slug.
 * @return string The branch to use for updates.
 */
function wcd_set_git_updater_branch( $branch, $slug ) {
	// Only apply to our plugin.
	if ( 'webchangedetector' !== $slug ) {
		return $branch;
	}

	// Return dev branch for beta updates, main for stable.
	return WCD_USE_DEV_BRANCH ? 'dev' : 'main';
}

// Only add filter if Git Updater is available.
if ( WCD_USE_DEV_BRANCH ) {
	add_filter( 'gu_primary_branch', __NAMESPACE__ . '\wcd_set_git_updater_branch', 10, 2 );
}

/**
 * Display admin notice about current update mode.
 * Only shown to administrators and only when in beta mode.
 */
function wcd_update_mode_admin_notice() {
	// Only show when in beta mode.
	if ( ! WCD_USE_DEV_BRANCH ) {
		return;
	}

	// Only show to administrators.
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	// Only show on plugin pages.
	$screen = get_current_screen();
	if ( ! $screen || ( false === strpos( $screen->id, 'webchangedetector' ) && 'plugins' !== $screen->id ) ) {
		return;
	}

	$notice = sprintf(
		'<div class="notice notice-warning"><p><strong>%1$s:</strong> ⚠️ %2$s <strong>%3$s</strong> %4$s</p></div>',
		esc_html__( 'WebChange Detector', 'webchangedetector' ),
		esc_html__( 'Currently running in', 'webchangedetector' ),
		esc_html__( 'Beta (dev branch)', 'webchangedetector' ),
		esc_html__( 'update mode. You will receive beta updates.', 'webchangedetector' )
	);
	echo wp_kses_post( $notice );
}
add_action( 'admin_notices', __NAMESPACE__ . '\wcd_update_mode_admin_notice' );

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-webchangedetector-activator.php
 */
/**
 * Plugin activation handler.
 *
 * Supports both single-site and network-wide activation on multisite.
 *
 * @param bool $network_wide Whether the plugin is being activated network-wide.
 */
function activate_webchangedetector( $network_wide = false ) {
	require_once WCD_PLUGIN_DIR . 'includes/class-webchangedetector-activator.php';

	if ( $network_wide && is_multisite() ) {
		// Migrate shared options from main site's wp_options to wp_sitemeta.
		// This handles the case where a single-site install is converted to multisite.
		migrate_options_to_network();

		$sites = get_sites( array( 'number' => 0 ) );
		foreach ( $sites as $site ) {
			// Use try/finally pattern: if WebChangeDetector_Activator::activate()
			// throws on one site, the WP $switched_stack would otherwise stay
			// corrupted and contaminate option writes on remaining sites.
			switch_to_blog( $site->blog_id );
			try {
				WebChangeDetector_Activator::activate();
			} finally {
				restore_current_blog();
			}
		}
	} else {
		WebChangeDetector_Activator::activate();
	}
}

/**
 * Migrate shared options from wp_options (main site) to wp_sitemeta.
 *
 * When a single-site install is converted to multisite, the API token and
 * other shared options remain in wp_options. This function copies them to
 * the network-level wp_sitemeta table so the plugin can find them.
 *
 * Only copies if the network option does not already exist (no overwrite).
 * The original wp_options values are kept as fallback.
 *
 * @since 4.3.0
 */
function migrate_options_to_network() {
	$network_options = array(
		'webchangedetector_api_token',
		'webchangedetector_account_email',
		'wcd_upgrade_url',
	);

	// Read all options from the main site in one switch (less stack churn than
	// switching back and forth per option). try/finally ensures the original
	// blog context is restored even if an error fires inside the loop.
	$main_site_id = get_main_site_id();
	$site_values  = array();
	switch_to_blog( $main_site_id );
	try {
		foreach ( $network_options as $option_key ) {
			$site_values[ $option_key ] = get_option( $option_key, false );
		}
	} finally {
		restore_current_blog();
	}

	// Now write to network-level (no blog switch needed) — only if not already set.
	foreach ( $site_values as $option_key => $site_value ) {
		if ( false === $site_value || '' === $site_value ) {
			continue;
		}
		$network_value = get_site_option( $option_key, false );
		if ( false === $network_value || '' === $network_value ) {
			update_site_option( $option_key, $site_value );
		}
	}
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-webchangedetector-deactivator.php
 *
 * @param bool $network_wide Whether the plugin is being deactivated network-wide.
 */
function deactivate_webchangedetector( $network_wide = false ) {
	require_once WCD_PLUGIN_DIR . 'includes/class-webchangedetector-deactivator.php';

	if ( $network_wide && is_multisite() ) {
		$sites = get_sites( array( 'number' => 0 ) );
		foreach ( $sites as $site ) {
			// try/finally so a deactivation error on one site doesn't corrupt
			// the WP $switched_stack and contaminate remaining sites.
			switch_to_blog( $site->blog_id );
			try {
				WebChangeDetector_Deactivator::deactivate();
			} finally {
				restore_current_blog();
			}
		}
	} else {
		WebChangeDetector_Deactivator::deactivate();
	}
}

register_activation_hook( __FILE__, __NAMESPACE__ . '\activate_webchangedetector' );
register_deactivation_hook( __FILE__, __NAMESPACE__ . '\deactivate_webchangedetector' );

/**
 * Activate plugin for newly created sites on multisite networks.
 *
 * @param \WP_Site $new_site The new site object.
 */
function wcd_on_new_site( $new_site ) {
	if ( ! function_exists( 'is_plugin_active_for_network' ) ) {
		require_once ABSPATH . 'wp-admin/includes/plugin.php';
	}

	if ( is_plugin_active_for_network( WCD_PLUGIN_BASENAME ) ) {
		require_once WCD_PLUGIN_DIR . 'includes/class-webchangedetector-activator.php';
		// try/finally so an activation error doesn't leave the $switched_stack
		// corrupted (this hook fires inside wpmu_create_blog, contaminating that
		// flow's option writes would be hard to debug).
		switch_to_blog( $new_site->blog_id );
		try {
			WebChangeDetector_Activator::activate();
		} finally {
			restore_current_blog();
		}
	}
}
add_action( 'wp_initialize_site', __NAMESPACE__ . '\wcd_on_new_site', 200 );

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require WCD_PLUGIN_DIR . 'includes/class-webchangedetector.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function run_webchangedetector() {
	$plugin = new WebChangeDetector();
	$plugin->run();
}



/**
 * Get the plugin directory path.
 * This function is used by various partial files to include other files.
 *
 * @return string The plugin directory path.
 */
function wcd_get_plugin_dir() {
	return WCD_PLUGIN_DIR;
}

run_webchangedetector();
