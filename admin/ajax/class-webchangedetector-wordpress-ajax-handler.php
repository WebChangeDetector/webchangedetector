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
		add_action( 'wp_ajax_wcd_register_multisite', array( $this, 'ajax_register_multisite' ) );
	}

	/**
	 * Handle multisite site registration AJAX request.
	 *
	 * Registers a sub-site with the WCD API by creating a website and groups.
	 *
	 * @since 4.3.0
	 */
	public function ajax_register_multisite() {
		// Use explicit nonce + capability checks instead of security_check()
		// to avoid the automatic maybe_switch_to_blog() call, which would
		// create a duplicate switch_to_blog() and corrupt the WP switch stack.
		if ( ! $this->verify_nonce( 'ajax-nonce' ) ) {
			$this->send_error_response(
				__( 'Security check failed. Please refresh the page and try again.', 'webchangedetector' ),
				'Nonce verification failed',
				403
			);
			return;
		}

		if ( ! $this->check_capability( 'manage_network_options' ) ) {
			$this->send_error_response(
				__( 'You do not have permission to perform this action.', 'webchangedetector' ),
				'Capability check failed',
				403
			);
			return;
		}

		if ( ! WebChangeDetector_Multisite::is_multisite_active() ) {
			$this->send_error_response( __( 'This action is only available on multisite installations.', 'webchangedetector' ) );
			return;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Already verified above.
		$blog_id = isset( $_POST['wcd_blog_id'] ) ? absint( $_POST['wcd_blog_id'] ) : 0;
		if ( ! $blog_id ) {
			$this->send_error_response( __( 'Invalid site ID.', 'webchangedetector' ) );
			return;
		}

		// Validate that the target blog exists.
		$blog_details = get_blog_details( $blog_id );
		if ( ! $blog_details ) {
			$this->send_error_response( __( 'The specified site does not exist.', 'webchangedetector' ) );
			return;
		}

		// Sub-site registration requires the main site's WCD Website UUID so the
		// API can link the sub-site via parent_multisite_website_id. Bail with a
		// clear message if the main hasn't registered yet — this prevents a
		// silently-orphaned sub-site that would otherwise get no inheritance.
		if ( get_main_site_id() !== (int) $blog_id && '' === WebChangeDetector_Multisite::get_main_website_id() ) {
			$this->send_error_response(
				__( 'Please register the main site first. Sub-sites inherit their auto-update schedule from the main site.', 'webchangedetector' )
			);
			return;
		}

		// Single, clean switch to the target blog.
		switch_to_blog( $blog_id );

		try {
			// Check if already registered.
			$existing_website_id = get_option( 'webchangedetector_website_id', '' );
			if ( ! empty( $existing_website_id ) ) {
				restore_current_blog();
				$this->send_success_response(
					array(
						'message'    => __( 'Site is already registered.', 'webchangedetector' ),
						'manage_url' => WebChangeDetector_Multisite::get_admin_url( 'webchangedetector', $blog_id ),
					)
				);
				return;
			}

			// Create website and groups via the existing method.
			$result = $this->admin->create_website_and_groups();

			if ( is_array( $result ) && ! isset( $result['error'] ) ) {
				// Sync posts for the new site.
				$this->wordpress_handler->sync_posts( true );

				// Clear multisite cache so subsequent queries see the new site.
				WebChangeDetector_Multisite::clear_cache();

				restore_current_blog();
				$this->send_success_response(
					array(
						'message'    => __( 'Site registered successfully.', 'webchangedetector' ),
						'manage_url' => WebChangeDetector_Multisite::get_admin_url( 'webchangedetector', $blog_id ),
					)
				);
			} else {
				$error_message = is_array( $result ) && ! empty( $result['error'] )
					? $result['error']
					: __( 'Unknown error', 'webchangedetector' );

				restore_current_blog();
				$this->send_error_response(
					/* translators: %s: Error details from the API */
					sprintf( __( 'Failed to register site: %s', 'webchangedetector' ), $error_message )
				);
			}
		} catch ( \Throwable $e ) {
			restore_current_blog();
			$this->send_error_response(
				__( 'An error occurred while registering the site. Please try again.', 'webchangedetector' ),
				'Exception: ' . $e->getMessage()
			);
		}
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
		// Delegate to the WordPress handler which has the correct implementation.
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
