<?php
/**
 * The public-facing functionality of the plugin.
 *
 * @link       wp-mike.com
 * @since      1.0.0
 *
 * @package    WebChangeDetector
 * @subpackage WebChangeDetector/public
 */

/**
 * The public-facing functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the public-facing stylesheet and JavaScript.
 *
 * @package    WebChangeDetector
 * @subpackage WebChangeDetector/public
 * @author     Mike Miler <mike@wp-mike.com>
 */
class WebChangeDetector_Public {


	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 */
	public function __construct() {
		if ( ! get_option( WCD_WP_OPTION_KEY_API_TOKEN ) ) {
			// Verify website.
			$this->verify_website();
		}
	}

	/**
	 * Register the stylesheets for the public-facing side of the site.
	 *
	 * @since    3.1.8
	 */
	public function enqueue_styles() {
		// Check if admin bar menu is disabled in settings.
		if ( get_option( 'wcd_disable_admin_bar_menu' ) ) {
			return;
		}
		// Only enqueue if the admin bar is showing for a logged-in user who can manage options.
		if ( is_admin_bar_showing() && is_user_logged_in() && current_user_can('manage_options') ) {
			wp_enqueue_style( 'webchangedetector-public', plugin_dir_url( __FILE__ ) . 'css/webchangedetector-public.css', array(), WEBCHANGEDETECTOR_VERSION, 'all' );
		}
	}

	/** Verify the website if we do.
	 *
	 * @return void
	 */
	public function verify_website() {

		$verify_string = get_option( WCD_VERIFY_SECRET );
		if ( ! empty( $_GET['wcd-verify'] ) &&
			( empty( $_GET['_wpnonce'] ) || wp_verify_nonce( wp_unslash( sanitize_key( $_GET['_wpnonce'] ) ) ) ) &&
			! empty( $verify_string )
		) {
			echo wp_json_encode( $verify_string );
			die();
		}
	}
}
