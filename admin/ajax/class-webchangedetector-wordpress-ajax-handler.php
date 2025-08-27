<?php
/**
 * WordPress AJAX handler.
 *
 * Handles all WordPress-specific AJAX operations including
 * post synchronization, URL management, and admin bar integration.
 *
 * @link       https://www.webchangedetector.com
 * @since      4.0.0
 * @package    WebChangeDetector
 * @subpackage WebChangeDetector/admin/ajax
 */

namespace WebChangeDetector;

/**
 * WordPress AJAX handler.
 *
 * Handles all WordPress-specific AJAX operations.
 *
 * @since      4.0.0
 * @package    WebChangeDetector
 * @subpackage WebChangeDetector/admin/ajax
 * @author     Mike Miler <mike@webchangedetector.com>
 */
class WebChangeDetector_WordPress_Ajax_Handler extends WebChangeDetector_Ajax_Handler_Base {

	/**
	 * The WordPress handler instance.
	 *
	 * @since    4.0.0
	 * @access   private
	 * @var      WebChangeDetector_Admin_WordPress    $wordpress_handler    The WordPress handler instance.
	 */
	private $wordpress_handler;

	/**
	 * The settings handler instance.
	 *
	 * @since    4.0.0
	 * @access   private
	 * @var      WebChangeDetector_Admin_Settings    $settings_handler    The settings handler instance.
	 */
	private $settings_handler;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    4.0.0
	 * @param    WebChangeDetector_Admin           $admin             The main admin class instance.
	 * @param    WebChangeDetector_Admin_WordPress $wordpress_handler The WordPress handler instance.
	 * @param    WebChangeDetector_Admin_Settings  $settings_handler  The settings handler instance.
	 */
	public function __construct( $admin, $wordpress_handler, $settings_handler ) {
		parent::__construct( $admin );

		$this->wordpress_handler = $wordpress_handler;
		$this->settings_handler  = $settings_handler;
	}

	/**
	 * Register AJAX hooks for WordPress operations.
	 *
	 * Registers all WordPress AJAX hooks for WordPress-specific operations.
	 *
	 * @since    4.0.0
	 */
	public function register_hooks() {
		add_action( 'wp_ajax_sync_urls', array( $this, 'ajax_sync_urls' ) );
		add_action( 'wp_ajax_wcd_get_admin_bar_status', array( $this, 'ajax_get_wcd_admin_bar_status' ) );
		add_action( 'wp_ajax_wcd_sync_posts', array( $this, 'ajax_sync_posts' ) );
	}

	/**
	 * Handle sync URLs AJAX request.
	 *
	 * Synchronizes URLs from WordPress posts and pages based on configured types.
	 *
	 * @since    4.0.0
	 */
	public function ajax_sync_urls() {
		if ( ! $this->security_check() ) {
			return;
		}

		try {
			$post_data = $this->validate_post_data( array( 'sync_types' ) );

			if ( false === $post_data ) {
				$this->send_error_response(
					__( 'Missing sync types data.', 'webchangedetector' ),
					'Missing sync_types'
				);
				return;
			}

			$sync_types = $post_data['sync_types'];

			// Validate sync types.
			$available_types = $this->settings_handler->get_available_sync_types();
			$valid_types     = array();

			foreach ( $sync_types as $type ) {
				if ( isset( $available_types[ $type ] ) ) {
					$valid_types[] = $type;
				}
			}

			if ( empty( $valid_types ) ) {
				$this->send_error_response(
					__( 'No valid sync types provided.', 'webchangedetector' ),
					'No valid sync types'
				);
				return;
			}

			// Perform URL synchronization.
			$sync_result = $this->wordpress_handler->sync_urls( $valid_types );

			if ( is_wp_error( $sync_result ) ) {
				$this->send_error_response(
					__( 'Failed to synchronize URLs.', 'webchangedetector' ),
					'Sync error: ' . $sync_result->get_error_message()
				);
				return;
			}

			// Update stored sync types.
			update_option( 'wcd_sync_url_types', $valid_types );

			$this->send_success_response(
				array(
					'synced_urls' => $sync_result,
					'sync_types'  => $valid_types,
				),
				__( 'URLs synchronized successfully.', 'webchangedetector' )
			);

		} catch ( \Exception $e ) {
			$this->send_error_response(
				__( 'An error occurred while synchronizing URLs.', 'webchangedetector' ),
				'Exception: ' . $e->getMessage()
			);
		}
	}

	/**
	 * Handle get admin bar status AJAX request.
	 *
	 * Retrieves the current status for the WordPress admin bar integration.
	 * Delegates to the WordPress handler which has the correct implementation.
	 *
	 * @since    4.0.0
	 */
	public function ajax_get_wcd_admin_bar_status() {
		// Delegate to the WordPress handler which has the correct implementation
		if ( $this->wordpress_handler && method_exists( $this->wordpress_handler, 'ajax_get_wcd_admin_bar_status' ) ) {
			$this->wordpress_handler->ajax_get_wcd_admin_bar_status();
		} else {
			$this->send_error_response(
				__( 'Admin bar status handler not available.', 'webchangedetector' ),
				'Handler method missing'
			);
		}
	}

	/**
	 * Handle sync posts AJAX request.
	 *
	 * Synchronizes WordPress posts and pages with the WebChangeDetector service.
	 *
	 * @since    4.0.0
	 */
	public function ajax_sync_posts() {
		if ( ! $this->security_check() ) {
			return;
		}

		try {

			// Perform post synchronization.
			$sync_result = $this->wordpress_handler->sync_posts();

			if ( is_wp_error( $sync_result ) ) {
				$this->send_error_response(
					__( 'Failed to synchronize posts.', 'webchangedetector' ),
					'Sync error'
				);
				return;
			}

			$this->send_success_response(
				array(
					'synced_posts' => $sync_result,
				),
				__( 'Posts synchronized successfully.', 'webchangedetector' )
			);

		} catch ( \Exception $e ) {
			$this->send_error_response(
				__( 'An error occurred while synchronizing posts.', 'webchangedetector' ),
				'Exception: ' . $e->getMessage()
			);
		}
	}
}
