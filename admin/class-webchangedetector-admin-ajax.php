<?php
/**
 * WebChange Detector Admin AJAX Handlers
 *
 * Handles all AJAX requests for the WebChange Detector plugin.
 * Follows WordPress coding standards and Model-View-Presenter pattern.
 *
 * @link       https://www.webchangedetector.com
 * @since      1.0.0
 *
 * @package    WebChangeDetector
 * @subpackage WebChangeDetector/admin
 */

namespace WebChangeDetector;

/**
 * WebChange Detector Admin AJAX Handlers Class
 *
 * This class handles all AJAX requests for the WebChange Detector plugin.
 * It provides secure, nonce-verified AJAX endpoints with proper capability checks.
 *
 * @package    WebChangeDetector
 * @subpackage WebChangeDetector/admin
 * @author     WebChange Detector <support@webchangedetector.com>
 */
class WebChangeDetector_Admin_AJAX {

	/**
	 * The API Manager instance.
	 *
	 * @since 1.0.0
	 * @var WebChangeDetector_API_Manager
	 */
	private $api_manager;

	/**
	 * The main admin class instance for access to existing methods.
	 *
	 * @since 1.0.0
	 * @var WebChangeDetector_Admin
	 */
	private $admin;

    /**
     * The account handler instance.
     *
     * @since 1.0.0
     * @var WebChangeDetector_Admin_Account
     */
    private $account_handler;

    /**
     * The dashboard handler instance.
     *
     * @since 1.0.0
     * @var WebChangeDetector_Admin_Dashboard
     */
    private $dashboard_handler;

    /**
     * The wordpress handler instance.
     *
     * @since 1.0.0
     * @var WebChangeDetector_Admin_WordPress
     */
    private $wordpress_handler;

    /**
     * The screenshots handler instance.
     *
     * @since 1.0.0
     * @var WebChangeDetector_Admin_Screenshots
     */
    private $screenshots_handler;

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 * @param WebChangeDetector_Admin $admin The main admin class instance.
	 */
	public function __construct( $admin = null ) {
		$this->admin       = $admin;
		$this->api_manager = new WebChangeDetector_API_Manager();
        $this->account_handler = new WebChangeDetector_Admin_Account( $this->admin );
        $this->dashboard_handler = new WebChangeDetector_Admin_Dashboard( $this->admin, $this->api_manager, $this->account_handler );
        $this->wordpress_handler = new WebChangeDetector_Admin_WordPress( 'webchangedetector', WEBCHANGEDETECTOR_VERSION, $this->admin );
        $this->screenshots_handler = new WebChangeDetector_Admin_Screenshots( $this->admin, $this->api_manager, $this->account_handler );
	}

	/**
	 * Register AJAX hooks.
	 *
	 * @since 1.0.0
	 */
	public function register_ajax_hooks() {
		// Register all AJAX actions
		add_action( 'wp_ajax_get_processing_queue', array( $this, 'ajax_get_processing_queue' ) );
		add_action( 'wp_ajax_post_url', array( $this, 'ajax_post_url' ) );
		add_action( 'wp_ajax_sync_urls', array( $this, 'ajax_sync_urls' ) );
		add_action( 'wp_ajax_update_comparison_status', array( $this, 'ajax_update_comparison_status' ) );
		add_action( 'wp_ajax_wcd_disable_wizard', array( $this, 'ajax_disable_wizard' ) );
		add_action( 'wp_ajax_get_batch_comparisons_view', array( $this, 'ajax_get_batch_comparisons_view' ) );
		add_action( 'wp_ajax_load_failed_queues', array( $this, 'ajax_load_failed_queues' ) );
		add_action( 'wp_ajax_create_website_and_groups_ajax', array( $this, 'ajax_create_website_and_groups' ) );
		add_action( 'wp_ajax_get_dashboard_usage_stats', array( $this, 'ajax_get_dashboard_usage_stats' ) );
		add_action( 'wp_ajax_wcd_get_admin_bar_status', array( $this, 'ajax_get_wcd_admin_bar_status' ) );
		add_action( 'wp_ajax_wcd_check_activation_status', array( $this, 'ajax_check_activation_status' ) );
		add_action( 'wp_ajax_wcd_get_initial_setup', array( $this, 'ajax_get_initial_setup' ) );
		add_action( 'wp_ajax_wcd_save_initial_setup', array( $this, 'ajax_save_initial_setup' ) );
		add_action( 'wp_ajax_wcd_sync_posts', array( $this, 'ajax_sync_posts' ) );
		add_action( 'wp_ajax_wcd_update_sync_types_with_local_labels', array( $this, 'ajax_update_sync_types_with_local_labels' ) );
		add_action( 'wp_ajax_wcd_complete_initial_setup', array( $this, 'ajax_complete_initial_setup' ) );
	}

	/**
	 * AJAX handler to get processing queue.
	 *
	 * @since 1.0.0
	 */
	public function ajax_get_processing_queue() {
		// Verify nonce for security
		check_ajax_referer( 'ajax-nonce', 'nonce' );

		// Verify user capabilities
		if ( ! \WebChangeDetector\WebChangeDetector_Admin_Utils::current_user_can_manage_webchangedetector() ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'webchangedetector' ) ), 403 );
		}

		// Delegate to admin class for now (will be fully extracted later)
		if ( $this->admin && method_exists( $this->admin, 'get_processing_queue_v2' ) ) {
			$batch_id = get_option( 'wcd_manual_checks_batch' );
			echo wp_json_encode( $this->admin->get_processing_queue_v2( $batch_id ) );
		} else {
			wp_send_json_error( array( 'message' => __( 'Method not available.', 'webchangedetector' ) ) );
		}
		
		wp_die();
	}

	/**
	 * AJAX handler to update selected URL.
	 *
	 * @since 1.0.0
	 */
	public function ajax_post_url() {
		// Check for our specific nonce key sent from the URL settings toggles
		if ( ! isset( $_POST['nonce'] ) ) {
			wp_send_json_error( array( 'message' => __( 'Nonce missing.', 'webchangedetector' ) ) );
		}

		$nonce = sanitize_text_field( wp_unslash( $_POST['nonce'] ) );

		// Verify nonce using the action name 'ajax-nonce' used during its creation
		if ( ! wp_verify_nonce( $nonce, 'ajax-nonce' ) ) {
			wp_send_json_error( array( 'message' => __( 'Nonce verification failed.', 'webchangedetector' ) ) );
		}

		// Verify user capabilities
		if ( ! \WebChangeDetector\WebChangeDetector_Admin_Utils::current_user_can_manage_webchangedetector() ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'webchangedetector' ) ), 403 );
		}

		// Delegate to main admin class for now (will be refactored later)
		if ( $this->admin && method_exists( $this->admin, 'post_urls' ) ) {
			$this->admin->post_urls( $_POST );
		} else {
			wp_send_json_error( array( 'message' => __( 'Method not available.', 'webchangedetector' ) ) );
		}

		wp_die();
	}

	/**
	 * AJAX handler to sync posts/URLs.
	 *
	 * @since 1.0.0
	 */
	public function ajax_sync_urls() {
		if ( ! isset( $_POST['nonce'] ) ) {
			echo 'POST Params missing';
			wp_die();
		}

		// Verify nonce
		if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'ajax-nonce' ) ) {
			echo 'Nonce verify failed';
			wp_die( 'Busted!' );
		}

		// Verify user capabilities
		if ( ! \WebChangeDetector\WebChangeDetector_Admin_Utils::current_user_can_manage_webchangedetector() ) {
			echo 'Permission denied';
			wp_die();
		}

		// Delegate to WordPress handler
		if ( $this->admin && $this->admin->wordpress_handler && method_exists( $this->admin->wordpress_handler, 'sync_posts' ) && method_exists( $this->admin->settings_handler, 'get_website_details' ) ) {
			$force = isset( $_POST['force'] ) ? sanitize_text_field( wp_unslash( $_POST['force'] ) ) : 0;
			\WebChangeDetector\WebChangeDetector_Admin_Utils::log_error( 'Force? ' . (bool) $force );
			$response = $this->admin->wordpress_handler->sync_posts( (bool) $force, $this->admin->settings_handler->get_website_details() );
			if ( $response ) {
				echo esc_html( $response );
			}
		} else {
			echo 'Sync method not available';
		}
		
		wp_die();
	}

	/**
	 * AJAX handler to update comparison status.
	 *
	 * @since 1.0.0
	 */
	public function ajax_update_comparison_status() {
		if ( ! isset( $_POST['id'] ) || ! isset( $_POST['status'] ) || ! isset( $_POST['nonce'] ) ) {
			echo 'failed: missing post fields';
			wp_die();
		}

		// Verify nonce
		if ( ! \WebChangeDetector\WebChangeDetector_Admin_Utils::verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'ajax-nonce' ) ) {
			echo 'failed: nonce missing';
			wp_die();
		}

		// Verify user capabilities
		if ( ! \WebChangeDetector\WebChangeDetector_Admin_Utils::current_user_can_manage_webchangedetector() ) {
			echo 'failed: capabilities';
			wp_die();
		}

		$comparison_id = esc_html( sanitize_text_field( wp_unslash( $_POST['id'] ) ) );
		$status = esc_html( sanitize_text_field( wp_unslash( $_POST['status'] ) ) );

		// Update comparison status via screenshots handler
		$result = $this->screenshots_handler->update_comparison_status( $comparison_id, $status );
		
		// Handle the most common success scenarios first
		if ( $result === true ) {
			// API call successful (common case for PUT requests)
			echo esc_html( $status );
			wp_die();
		}
		
		if ( is_array( $result ) ) {
			// Check for explicit success indicators
			if ( isset( $result['success'] ) && $result['success'] ) {
				echo esc_html( $status );
				wp_die();
			}
			
			// Check for status in data
			if ( isset( $result['data']['status'] ) ) {
				echo esc_html( $result['data']['status'] );
				wp_die();
			}
			
			// Check for Laravel-style success (no error key)
			if ( ! isset( $result['error'] ) && ! isset( $result['errors'] ) && ! isset( $result['message'] ) ) {
				echo esc_html( $status );
				wp_die();
			}
			
			// Array contains error information
			echo 'failed';
			wp_die();
		}
		
		if ( is_string( $result ) ) {
			// Check for known error strings
			$error_strings = array( 'Id is missing.', 'Wrong status.', 'No API token found', 'unauthorized', 'update plugin', 'activate account' );
			if ( in_array( $result, $error_strings, true ) ) {
				echo 'failed';
				wp_die();
			}
			
			// Unknown string response - could be success
			echo esc_html( $status );
			wp_die();
		}
		
		// Fallback for any other case
		echo 'failed';
		
		wp_die();
	}

	/**
	 * AJAX handler to disable wizard.
	 *
	 * @since 1.0.0
	 */
	public function ajax_disable_wizard() {
		// Verify nonce for security
		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'wcd_wizard_nonce' ) ) {
			wp_send_json_error( 'Invalid nonce' );
		}

		// Check user capabilities
		if ( ! \WebChangeDetector\WebChangeDetector_Admin_Utils::current_user_can_manage_webchangedetector() ) {
			wp_send_json_error( 'Insufficient permissions' );
		}

		// Disable wizard by deleting the option
		delete_option( 'wcd_wizard' );

		wp_send_json_success( 'Wizard disabled' );
	}

	/**
	 * AJAX handler for loading batch comparisons view.
	 *
	 * @since 1.0.0
	 */
	public function ajax_get_batch_comparisons_view() {
		// Verify nonce for security
		check_ajax_referer( 'ajax-nonce', 'nonce' );

		// Verify user capabilities
		if ( ! \WebChangeDetector\WebChangeDetector_Admin_Utils::current_user_can_manage_webchangedetector() ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'webchangedetector' ) ), 403 );
		}

		// Delegate to dashboard handler
		if ( $this->admin && $this->admin->dashboard_handler && method_exists( $this->admin->dashboard_handler, 'load_comparisons_view' ) ) {
			// Get and sanitize the POST data
			$batch_id   = isset( $_POST['batch_id'] ) ? sanitize_text_field( wp_unslash( $_POST['batch_id'] ) ) : '';
			$per_page   = isset( $_POST['per_page'] ) ? absint( $_POST['per_page'] ) : 30;
			$page       = isset( $_POST['page'] ) ? absint( $_POST['page'] ) : 1;
			$status     = isset( $_POST['status'] ) ? sanitize_text_field( wp_unslash( $_POST['status'] ) ) : '';
			$group_id   = isset( $_POST['group_id'] ) ? sanitize_text_field( wp_unslash( $_POST['group_id'] ) ) : '';
			$url_search = isset( $_POST['url_search'] ) ? sanitize_text_field( wp_unslash( $_POST['url_search'] ) ) : '';

			// Build filters array for API call
			$api_filters = array(
				'per_page' => $per_page,
				'page'     => $page,
			);
			if ( ! empty( $status ) ) {
				$api_filters['status'] = $status;
			}
			if ( ! empty( $group_id ) ) {
				$api_filters['group_id'] = $group_id;
			}
			if ( ! empty( $url_search ) ) {
				$api_filters['url_search'] = $url_search;
			}

			// Get comparisons from API
			$api_filters['batches'] = $batch_id;
			$comparisons = \WebChangeDetector\WebChangeDetector_API_V2::get_comparisons_v2( $api_filters );
			
			// Build display filters array
			$display_filters = array();
			if ( ! empty( $status ) ) {
				$display_filters['status'] = $status;
			}
			if ( ! empty( $group_id ) ) {
				$display_filters['group_id'] = $group_id;
			}
			if ( ! empty( $url_search ) ) {
				$display_filters['url_search'] = $url_search;
			}
			
			// Output HTML directly (JavaScript expects raw HTML, not JSON)
			$this->admin->dashboard_handler->load_comparisons_view( $batch_id, $comparisons, $display_filters );
			wp_die();
		} else {
			wp_send_json_error( array( 'message' => __( 'Method not available.', 'webchangedetector' ) ) );
		}
	}

	/**
	 * AJAX handler for loading failed queues view.
	 *
	 * @since 1.0.0
	 */
	public function ajax_load_failed_queues() {
		// Verify nonce for security
		check_ajax_referer( 'ajax-nonce', 'nonce' );

		// Verify user capabilities
		if ( ! \WebChangeDetector\WebChangeDetector_Admin_Utils::current_user_can_manage_webchangedetector() ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'webchangedetector' ) ), 403 );
		}

		// Delegate to main admin class for now
		if ( $this->admin && method_exists( $this->admin, 'load_failed_queues_view' ) ) {
			$batch_id = isset( $_POST['batch_id'] ) ? sanitize_text_field( wp_unslash( $_POST['batch_id'] ) ) : '';

			ob_start();
			$this->screenshots_handler->load_failed_queues_view( $batch_id );
			$html = ob_get_clean();

			wp_send_json_success( array( 'html' => $html ) );
		} else {
			wp_send_json_error( array( 'message' => __( 'Method not available.', 'webchangedetector' ) ) );
		}
	}

	/**
	 * AJAX handler to create website and groups.
	 *
	 * @since 1.0.0
	 */
	public function ajax_create_website_and_groups() {
		// Verify nonce for security
		check_ajax_referer( 'ajax-nonce', 'nonce' );

		// Verify user capabilities
		if ( ! \WebChangeDetector\WebChangeDetector_Admin_Utils::current_user_can_manage_webchangedetector() ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'webchangedetector' ) ), 403 );
		}

		// Delegate to main admin class for now
		if ( $this->admin && method_exists( $this->admin, 'create_website_and_groups' ) ) {
			try {
				$result = $this->admin->create_website_and_groups();
				
				if ( isset( $result['error'] ) ) {
					\WebChangeDetector\WebChangeDetector_Admin_Utils::log_error( "Can't create website and groups. Response: " . wp_json_encode( $result ) );
					wp_send_json_error( array( 'message' => $result['error'] ) );
				} else {
					wp_send_json_success( $result );
				}
			} catch ( \Exception $e ) {
				\WebChangeDetector\WebChangeDetector_Admin_Utils::log_error( "Exception during website creation: " . $e->getMessage() );
				wp_send_json_error( array( 'message' => $e->getMessage() ) );
			}
		} else {
			wp_send_json_error( array( 'message' => __( 'Method not available.', 'webchangedetector' ) ) );
		}
	}

	/**
	 * AJAX handler to get dashboard usage statistics.
	 *
	 * @since 1.0.0
	 */
	public function ajax_get_dashboard_usage_stats() {
		// Verify nonce for security.
		check_ajax_referer( 'ajax-nonce', 'nonce' );

		// Verify user capabilities.
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'webchangedetector' ) ), 403 );
		}

		// Get group data for usage calculations.
		$auto_group   = \WebChangeDetector\WebChangeDetector_API_V2::get_group_v2( $this->admin->monitoring_group_uuid )['data'] ?? array();
		$update_group = \WebChangeDetector\WebChangeDetector_API_V2::get_group_v2( $this->admin->manual_group_uuid )['data'] ?? array();

		$amount_auto_detection = 0;
		if ( ! empty( $auto_group['enabled'] ) ) {
			$amount_auto_detection += WCD_HOURS_IN_DAY / $auto_group['interval_in_h'] * $auto_group['selected_urls_count'] * WCD_DAYS_PER_MONTH;
		}

		$auto_update_settings    = WebChangeDetector_Autoupdates::get_auto_update_settings();
		$max_auto_update_checks  = 0;
		$amount_auto_update_days = 0;

		if ( ! empty( $auto_update_settings['auto_update_checks_enabled'] ) ) {
			foreach ( \WebChangeDetector\WebChangeDetector_Admin::WEEKDAYS as $weekday ) {
				if ( isset( $auto_update_settings[ 'auto_update_checks_' . $weekday ] ) && ! empty( $auto_update_settings[ 'auto_update_checks_' . $weekday ] ) ) {
					++$amount_auto_update_days;
				}
			}
			$max_auto_update_checks = $update_group['selected_urls_count'] * $amount_auto_update_days * 4; // multiplied by weekdays in a month.
		}

		// Get account data for renewal calculations.
		$client_account       = $this->account_handler->get_account();
		$checks_until_renewal = $amount_auto_detection / WCD_SECONDS_IN_MONTH *
					( gmdate( 'U', strtotime( $client_account['renewal_at'] ) ) - gmdate( 'U' ) );

		$checks_needed    = $checks_until_renewal + $max_auto_update_checks;
		$checks_available = $client_account['checks_limit'] - $client_account['checks_done'];

		wp_send_json_success(
			array(
				'amount_auto_detection'  => $amount_auto_detection,
				'max_auto_update_checks' => $max_auto_update_checks,
				'checks_needed'          => $checks_needed,
				'checks_available'       => $checks_available,
				'checks_until_renewal'   => $checks_until_renewal,	
                'auto_group_enabled'  => $auto_group['enabled'] ?? 'not set',
                'auto_group_interval' => $auto_group['interval_in_h'] ?? 'not set',
                'auto_group_urls'     => $auto_group['selected_urls_count'] ?? 'not set',
                'update_group_urls'   => $update_group['selected_urls_count'] ?? 'not set',
                'auto_update_settings' => $auto_update_settings,
				
			)
		);
	}

	/**
	 * AJAX handler to get WCD status for the admin bar.
	 *
	 * @since 1.0.0
	 */
	public function ajax_get_wcd_admin_bar_status() {
		// Delegate to WordPress handler (use local instance)
		if ( $this->wordpress_handler && method_exists( $this->wordpress_handler, 'ajax_get_wcd_admin_bar_status' ) ) {
			$this->wordpress_handler->ajax_get_wcd_admin_bar_status();
		} else {
			wp_send_json_error( array( 'message' => __( 'Method not available.', 'webchangedetector' ) ) );
		}
	}

	/**
	 * AJAX handler to check account activation status.
	 *
	 * @since 1.0.0
	 */
	public function ajax_check_activation_status() {
		// Verify nonce for security
		check_ajax_referer( 'wcd_ajax_nonce', 'nonce' );

		// Verify user capabilities
		if ( ! \WebChangeDetector\WebChangeDetector_Admin_Utils::current_user_can_manage_webchangedetector() ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'webchangedetector' ) ), 403 );
		}

		// Check if we have both email and API token
		$account_email = get_option( WCD_WP_OPTION_KEY_ACCOUNT_EMAIL, '' );
		$api_token = get_option( WCD_WP_OPTION_KEY_API_TOKEN, '' );

		if ( empty( $account_email ) ) {
			wp_send_json_error( array( 'message' => __( 'No account email found.', 'webchangedetector' ) ) );
			return;
		}

		if ( empty( $api_token ) ) {
			wp_send_json_success( array( 'activated' => false, 'message' => __( 'Waiting for account activation.', 'webchangedetector' ) ) );
			return;
		}

		// Try to get account details to verify the token works
		if ( $this->admin && $this->admin->account_handler && method_exists( $this->admin->account_handler, 'get_account' ) ) {
			try {
				$account = $this->admin->account_handler->get_account();
				
				if ( ! empty( $account ) && is_array( $account ) ) {
					// Account is activated and accessible
					wp_send_json_success( array( 
						'activated' => true, 
						'message' => __( 'Account activated successfully.', 'webchangedetector' ),
						'account' => $account
					) );
				} else {
					// Token exists but account not accessible - still waiting for activation
					wp_send_json_success( array( 'activated' => false, 'message' => __( 'Account activation pending.', 'webchangedetector' ) ) );
				}
			} catch ( \Exception $e ) {
				// Error accessing account - still waiting for activation
				wp_send_json_success( array( 'activated' => false, 'message' => __( 'Account activation pending.', 'webchangedetector' ) ) );
			}
		} else {
			wp_send_json_error( array( 'message' => __( 'Account handler not available.', 'webchangedetector' ) ) );
		}
	}

	/**
	 * AJAX handler to get initial setup data.
	 *
	 * @since 1.0.0
	 */
	public function ajax_get_initial_setup() {
		// Verify nonce for security
		check_ajax_referer( 'wcd_ajax_nonce', 'nonce' );

		// Verify user capabilities
		if ( ! \WebChangeDetector\WebChangeDetector_Admin_Utils::current_user_can_manage_webchangedetector() ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'webchangedetector' ) ), 403 );
		}

		try {
			$available_types = $this->admin->settings_handler->get_available_sync_types();
			wp_send_json_success( array( 'available_types' => $available_types ) );
		} catch ( \Exception $e ) {
			wp_send_json_error( array( 'message' => $e->getMessage() ) );
		}
	}

	/**
	 * AJAX handler to save initial setup selections.
	 *
	 * @since 1.0.0
	 */
	public function ajax_save_initial_setup() {
		// Verify nonce for security
		check_ajax_referer( 'wcd_initial_setup', 'wcd_initial_setup_nonce' );

		// Verify user capabilities
		if ( ! \WebChangeDetector\WebChangeDetector_Admin_Utils::current_user_can_manage_webchangedetector() ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'webchangedetector' ) ), 403 );
		}

		try {
			// Get selected types from form
			$selected_post_types = isset( $_POST['post_types'] ) ? array_map( 'sanitize_text_field', wp_unslash( $_POST['post_types'] ) ) : array();
			$selected_taxonomies = isset( $_POST['taxonomies'] ) ? array_map( 'sanitize_text_field', wp_unslash( $_POST['taxonomies'] ) ) : array();
			$selected_special = isset( $_POST['special'] ) ? array_map( 'sanitize_text_field', wp_unslash( $_POST['special'] ) ) : array();

			// Build new sync_url_types array
			$new_sync_url_types = array();

			// Add selected post types
			foreach ( $selected_post_types as $post_type_slug ) {
				$post_type_object = get_post_type_object( $post_type_slug );
				if ( $post_type_object ) {
					$new_sync_url_types[] = array(
						'url_type_slug'  => 'types',
						'url_type_name'  => __( 'Post Types', 'webchangedetector' ),
						'post_type_slug' => $post_type_slug,
						'post_type_name' => $post_type_object->label,
					);
				}
			}

			// Add selected taxonomies
			foreach ( $selected_taxonomies as $taxonomy_slug ) {
				$taxonomy_object = get_taxonomy( $taxonomy_slug );
				if ( $taxonomy_object ) {
					$new_sync_url_types[] = array(
						'url_type_slug'  => 'types',
						'url_type_name'  => __( 'Taxonomies', 'webchangedetector' ),
						'post_type_slug' => $taxonomy_slug,
						'post_type_name' => $taxonomy_object->label,
					);
				}
			}

			// Add selected special pages
			foreach ( $selected_special as $special_slug ) {
				if ( 'frontpage' === $special_slug ) {
					$new_sync_url_types[] = array(
						'url_type_slug'  => 'types',
						'url_type_name'  => __( 'Special Pages', 'webchangedetector' ),
						'post_type_slug' => 'frontpage',
						'post_type_name' => __( 'Frontpage', 'webchangedetector' ),
					);
				}
			}

			// Update website details with new sync types
			$website_details = $this->admin->settings_handler->get_website_details( true );
			if ( ! empty( $website_details ) ) {
				$website_details['sync_url_types'] = $new_sync_url_types;
				$this->admin->settings_handler->update_website_details( $website_details );
			}

			// Mark initial setup as completed
			update_option( 'wcd_initial_setup_completed', true );

			wp_send_json_success( array( 
				'message' => __( 'Initial setup saved successfully.', 'webchangedetector' ),
				'sync_url_types' => $new_sync_url_types 
			) );

		} catch ( \Exception $e ) {
			wp_send_json_error( array( 'message' => $e->getMessage() ) );
		}
	}

	/**
	 * AJAX handler to trigger post sync after initial setup.
	 *
	 * @since 1.0.0
	 */
	public function ajax_sync_posts() {
		// Verify nonce for security
		check_ajax_referer( 'wcd_ajax_nonce', 'nonce' );

		// Verify user capabilities
		if ( ! \WebChangeDetector\WebChangeDetector_Admin_Utils::current_user_can_manage_webchangedetector() ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'webchangedetector' ) ), 403 );
		}

		try {
			// Delegate to WordPress handler for sync
			if ( $this->admin && $this->admin->wordpress_handler && method_exists( $this->admin->wordpress_handler, 'sync_posts' ) ) {
				$website_details = $this->admin->settings_handler->get_website_details( true );
				$response = $this->admin->wordpress_handler->sync_posts( true, $website_details );
				
				wp_send_json_success( array( 
					'message' => __( 'Posts synced successfully.', 'webchangedetector' ),
					'response' => $response 
				) );
			} else {
				wp_send_json_error( array( 'message' => __( 'Sync method not available.', 'webchangedetector' ) ) );
			}

		} catch ( \Exception $e ) {
			wp_send_json_error( array( 'message' => $e->getMessage() ) );
		}
	}

	/**
	 * AJAX handler to update sync types with local labels.
	 *
	 * @since 1.0.0
	 */
	public function ajax_update_sync_types_with_local_labels() {
		\WebChangeDetector\WebChangeDetector_Admin_Utils::log_error( 'WCD AJAX: update_sync_types_with_local_labels called' );
		
		// Verify nonce for security
		check_ajax_referer( 'wcd_ajax_nonce', 'nonce' );

		// Verify user capabilities
		if ( ! \WebChangeDetector\WebChangeDetector_Admin_Utils::current_user_can_manage_webchangedetector() ) {
			\WebChangeDetector\WebChangeDetector_Admin_Utils::log_error( 'WCD AJAX: User capabilities check failed' );
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'webchangedetector' ) ), 403 );
		}

		try {
			\WebChangeDetector\WebChangeDetector_Admin_Utils::log_error( 'WCD AJAX: Starting sync types update with local labels' );
			
			// Get current website details with existing sync_url_types
			$website_details = $this->admin->settings_handler->get_website_details( true );
			
			if ( empty( $website_details ) ) {
				\WebChangeDetector\WebChangeDetector_Admin_Utils::log_error( 'WCD AJAX: Website details are empty' );
				wp_send_json_error( array( 'message' => __( 'Unable to load website details.', 'webchangedetector' ) ) );
			}

			\WebChangeDetector\WebChangeDetector_Admin_Utils::log_error( 'WCD AJAX: Website details loaded, sync_url_types count: ' . ( ! empty( $website_details['sync_url_types'] ) ? count( $website_details['sync_url_types'] ) : '0' ) );

			// If no sync_url_types exist, create default ones (Posts and Pages as per API defaults)
			if ( empty( $website_details['sync_url_types'] ) ) {
				\WebChangeDetector\WebChangeDetector_Admin_Utils::log_error( 'WCD AJAX: No sync_url_types found, creating defaults' );
				
				// Get available post types the same way as settings tab
				$available_types = $this->admin->settings_handler->get_available_sync_types();
				$default_sync_types = array();
				
				// Add Posts and Pages as defaults (same as API defaults)
				if ( ! empty( $available_types['post_types'] ) ) {
					foreach ( $available_types['post_types'] as $post_type ) {
						if ( in_array( $post_type['slug'], array( 'posts', 'pages' ), true ) ) {
							$default_sync_types[] = array(
								'url_type_slug'  => 'types',
								'url_type_name'  => __( 'Post Types', 'webchangedetector' ),
								'post_type_slug' => $available_types['post_types']['slug'],
								'post_type_name' => $available_types['post_types']['name'],
							);
						}
					}
				}

                // Add Posts and Pages as defaults (same as API defaults)
				if ( ! empty( $available_types['taxonomies'] ) ) {
					foreach ( $available_types['taxonomies'] as $taxonomy ) {
						if ( in_array( $taxonomy['slug'], array( 'category' ), true ) ) {
							$default_sync_types[] = array(
								'url_type_slug'  => 'taxonomies',
								'url_type_name'  => __( 'Taxonomies', 'webchangedetector' ),
								'post_type_slug' => $available_types['taxonomies']['slug'],
								'post_type_name' => $available_types['taxonomies']['name'],
							);
						}
					}
				}
				
				// If we couldn't find posts/pages, fall back to hardcoded defaults
				if ( empty( $default_sync_types ) ) {
					$default_sync_types = array(
						array(
							'url_type_slug'  => 'types',
							'url_type_name'  => 'Post Types',
							'post_type_slug' => 'posts',
							'post_type_name' => get_post_type_object('post')->labels->name, // post because we use the slug here, not rest_base
						),
						array(
							'url_type_slug'  => 'types',
							'url_type_name'  => 'Post Types',
							'post_type_slug' => 'pages',
							'post_type_name' => get_post_type_object('page')->labels->name,// page because we use the slug here, not rest_base
						),
                        array(
							'url_type_slug'  => 'taxonomies',
							'url_type_name'  => 'Taxonomies',
							'post_type_slug' => 'category',
							'post_type_name' => get_taxonomy('category')->labels->name,// page because we use the slug here, not rest_base
						),
					);
				}
				
				$website_details['sync_url_types'] = $default_sync_types;
				\WebChangeDetector\WebChangeDetector_Admin_Utils::log_error( 'WCD AJAX: Created default sync types: ' . print_r( $default_sync_types, true ) );
			}

			// Update sync_url_types with local labels using existing method
			\WebChangeDetector\WebChangeDetector_Admin_Utils::log_error( 'WCD AJAX: Updating sync types with local names' );
			
			// Check if the method exists
			if ( ! method_exists( $this->admin->settings_handler, 'update_sync_url_types_with_local_names' ) ) {
				\WebChangeDetector\WebChangeDetector_Admin_Utils::log_error( 'WCD AJAX: Method update_sync_url_types_with_local_names does not exist' );
				wp_send_json_error( array( 'message' => __( 'Update method not available.', 'webchangedetector' ) ) );
			}
			
			$updated_sync_types = $this->admin->settings_handler->update_sync_url_types_with_local_names( $website_details['sync_url_types'] );
			
			if ( empty( $updated_sync_types ) ) {
				\WebChangeDetector\WebChangeDetector_Admin_Utils::log_error( 'WCD AJAX: Updated sync types is empty' );
				wp_send_json_error( array( 'message' => __( 'Failed to update sync types.', 'webchangedetector' ) ) );
			}
			
			$website_details['sync_url_types'] = $updated_sync_types;
			
			\WebChangeDetector\WebChangeDetector_Admin_Utils::log_error( 'WCD AJAX: Saving updated website details' );
			
			// Save the updated website details
			$update_result = $this->admin->settings_handler->update_website_details( $website_details );
			
			\WebChangeDetector\WebChangeDetector_Admin_Utils::log_error( 'WCD AJAX: Update result: ' . print_r( $update_result, true ) );
			
			wp_send_json_success( array( 
				'message' => __( 'Sync types updated with local labels.', 'webchangedetector' ),
				'sync_url_types' => $updated_sync_types 
			) );

		} catch ( \Exception $e ) {
			\WebChangeDetector\WebChangeDetector_Admin_Utils::log_error( 'WCD AJAX: Exception in sync types update: ' . $e->getMessage() );
			wp_send_json_error( array( 'message' => 'Error: ' . $e->getMessage() ) );
		}
	}

	/**
	 * AJAX handler to complete initial setup.
	 *
	 * @since 1.0.0
	 */
	public function ajax_complete_initial_setup() {
		// Verify nonce for security
		check_ajax_referer( 'wcd_ajax_nonce', 'nonce' );

		// Verify user capabilities
		if ( ! \WebChangeDetector\WebChangeDetector_Admin_Utils::current_user_can_manage_webchangedetector() ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'webchangedetector' ) ), 403 );
		}

		try {
			// Clear the initial setup needed flag
			delete_option( WCD_WP_OPTION_KEY_INITIAL_SETUP_NEEDED );
			
			// Enable the wizard for the user
			add_option( 'wcd_wizard', 'true', '', false );
			
			wp_send_json_success( array( 
				'message' => __( 'Initial setup completed successfully.', 'webchangedetector' ),
				'wizard_enabled' => true
			) );

		} catch ( \Exception $e ) {
			wp_send_json_error( array( 'message' => $e->getMessage() ) );
		}
	}
} 