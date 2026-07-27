<?php
/**
 * Provide a admin area view for the plugin
 *
 * This file is used to markup the admin-facing aspects of the plugin.
 * REFACTORED VERSION - Uses controller-based architecture.
 *
 * @link       wp-mike.com
 * @since      1.0.0
 *
 * @package    WebChangeDetector
 * @subpackage WebChangeDetector/admin/partials
 */

if ( ! function_exists( 'wcd_webchangedetector_init' ) ) {

	/**
	 * Init for plugin view
	 *
	 * This function has been refactored to use the new controller-based architecture.
	 * The massive 1000+ line function has been broken down into specialized controllers.
	 * All required classes are now loaded in the main plugin loading process.
	 *
	 * @return bool|void
	 */
	function wcd_webchangedetector_init() {
		// Create admin instance.
		$admin = new \WebChangeDetector\WebChangeDetector_Admin();

		// Create and initialize the main controller.
		$controller = new \WebChangeDetector\WebChangeDetector_Admin_Controller( $admin );

		// Initialize and run the controller.
		return $controller->init();
	}
}
