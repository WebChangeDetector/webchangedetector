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
 *
 * @wordpress-plugin
 * Plugin Name:       WebChange Detector
 * Plugin URI:        webchangedetector.com
 * Description:       Detect changes on your website visually before and after updating your website. You can also run automatic change detections and get notified on changes of your website.
 * Version:           3.1.8.8
 * GitHub Plugin URI: https://github.com/webchangedetector/webchangedetector
 * Primary Branch:    main
 * Author:            Mike Miler
 * Author URI:        webchangedetector.com
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       webchangedetector
 * Domain Path:       /languages
 */

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
 * Set default branch preference for beta updates.
 * Only enabled if Git Updater plugin is active.
 * Can be overridden in wp-config.php by defining WCD_USE_DEV_BRANCH.
 */
if ( ! defined( 'WCD_USE_DEV_BRANCH' ) ) {
	// Check if Git Updater plugin is active.
	$git_updater_active = false;
	if ( function_exists( 'is_plugin_active' ) ) {
		$git_updater_active = is_plugin_active( 'git-updater/git-updater.php' );
	}

	define( 'WCD_USE_DEV_BRANCH', $git_updater_active );
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
	add_filter( 'gu_primary_branch', 'wcd_set_git_updater_branch', 10, 2 );
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

	printf(
		'<div class="notice notice-warning"><p><strong>WebChange Detector:</strong> ⚠️ Currently running in <strong>Beta (dev branch)</strong> update mode. You will receive beta updates.</p></div>'
	);
}
add_action( 'admin_notices', 'wcd_update_mode_admin_notice' );

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-webchangedetector-activator.php
 */
function activate_webchangedetector() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-webchangedetector-activator.php';
	WebChangeDetector_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-webchangedetector-deactivator.php
 */
function deactivate_webchangedetector() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-webchangedetector-deactivator.php';
	WebChangeDetector_Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'activate_webchangedetector' );
register_deactivation_hook( __FILE__, 'deactivate_webchangedetector' );

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path( __FILE__ ) . 'includes/class-webchangedetector.php';

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


if ( ! function_exists( 'dd' ) ) {
	/**
	 * Dump and die function.
	 *
	 * @param mixed ...$output The output.
	 *
	 * @return void
	 */
	function dd( ...$output ) {
		// this is PHP 5.6+.
		echo '<pre>';
		foreach ( $output as $o ) {
			if ( is_array( $o ) || is_object( $o ) ) {
				// phpcs:disable WordPress.PHP.DevelopmentFunctions
				print_r( $o );
				// phpcs:enable
				continue;
			}
			echo esc_html( $o );
		}
		echo '</pre>';
		die();
	}
}

run_webchangedetector();
