<?php
/**
 * WebChange Detector AJAX Coordinator
 *
 * Coordinates the initialization of all AJAX handlers for the WebChange Detector plugin.
 * This class replaces the deprecated WebChangeDetector_Admin_AJAX class.
 *
 * @link       https://www.webchangedetector.com
 * @since      4.0.0
 *
 * @package    WebChangeDetector
 * @subpackage WebChangeDetector/admin
 */

namespace WebChangeDetector;

/**
 * WebChange Detector AJAX Coordinator Class
 *
 * This class coordinates the initialization of all focused AJAX handlers.
 * It ensures all handlers are properly instantiated and their hooks registered.
 *
 * @package    WebChangeDetector
 * @subpackage WebChangeDetector/admin
 * @author     WebChange Detector <support@webchangedetector.com>
 * @since      4.0.0
 */
class WebChangeDetector_Ajax_Coordinator {

	/**
	 * The main admin class instance.
	 *
	 * @since 4.0.0
	 * @var WebChangeDetector_Admin
	 */
	private $admin;

	/**
	 * Constructor.
	 *
	 * Initializes all AJAX handlers and registers their hooks.
	 *
	 * @since 4.0.0
	 * @param WebChangeDetector_Admin $admin The main admin class instance.
	 */
	public function __construct( $admin ) {
		$this->admin = $admin;
		$this->init_ajax_handlers();
	}

	/**
	 * Initialize focused AJAX handlers.
	 *
	 * Creates instances of the focused AJAX handlers and registers their hooks.
	 * Each handler is responsible for a specific domain of functionality.
	 *
	 * @since 4.0.0
	 */
	private function init_ajax_handlers() {
		// Initialize required dependencies.
		$account_handler     = new WebChangeDetector_Admin_Account();
		$wordpress_handler   = new WebChangeDetector_Admin_WordPress( 'webchangedetector', WEBCHANGEDETECTOR_VERSION, $this->admin );
		$screenshots_handler = new WebChangeDetector_Admin_Screenshots( $this->admin );
		$settings_handler    = $this->admin->settings_handler ?? null;

		// Initialize and register Screenshots AJAX handler.
		$screenshots_ajax = new WebChangeDetector_Screenshots_Ajax_Handler(
			$this->admin,
			$screenshots_handler,
			$account_handler
		);
		$screenshots_ajax->register_hooks();

		// Initialize and register Settings AJAX handler.
		$settings_ajax = new WebChangeDetector_Settings_Ajax_Handler(
			$this->admin,
			$settings_handler
		);
		$settings_ajax->register_hooks();

		// Initialize and register WordPress AJAX handler.
		$wordpress_ajax = new WebChangeDetector_WordPress_Ajax_Handler(
			$this->admin,
			$wordpress_handler,
			$settings_handler
		);
		$wordpress_ajax->register_hooks();

		// Initialize and register Account AJAX handler.
		$account_ajax = new WebChangeDetector_Account_Ajax_Handler(
			$this->admin,
			$account_handler
		);
		$account_ajax->register_hooks();
	}
}