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
	 * Constructor.
	 *
	 * @since 1.0.0
	 * @param WebChangeDetector_Admin $admin The main admin class instance.
	 */
	public function __construct( $admin = null ) {
		$this->admin       = $admin;
		$this->api_manager = new WebChangeDetector_API_Manager();
        $this->account_handler = new WebChangeDetector_Admin_Account( $this->admin );
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

		// Delegate to main admin class for now (will be refactored later)
		if ( $this->admin && method_exists( $this->admin, 'sync_posts' ) && method_exists( $this->admin->settings_handler, 'get_website_details' ) ) {
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

		// Delegate to main admin class for now (will be refactored later)
		if ( $this->admin && method_exists( $this->admin, 'update_comparison_status' ) ) {
			$result = $this->admin->update_comparison_status( 
				esc_html( sanitize_text_field( wp_unslash( $_POST['id'] ) ) ), 
				esc_html( sanitize_text_field( wp_unslash( $_POST['status'] ) ) ) 
			);
			echo esc_html( $result['data']['status'] ) ?? 'failed';
		} else {
			echo 'Method not available';
		}
		
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

		// Delegate to main admin class for now
		if ( $this->admin && method_exists( $this->admin, 'load_comparisons_view' ) ) {
			// Get and sanitize the POST data
			$batch_id   = isset( $_POST['batch_id'] ) ? sanitize_text_field( wp_unslash( $_POST['batch_id'] ) ) : '';
			$per_page   = isset( $_POST['per_page'] ) ? absint( $_POST['per_page'] ) : 30;
			$offset     = isset( $_POST['offset'] ) ? absint( $_POST['offset'] ) : 0;
			$status     = isset( $_POST['status'] ) ? sanitize_text_field( wp_unslash( $_POST['status'] ) ) : '';
			$group_id   = isset( $_POST['group_id'] ) ? sanitize_text_field( wp_unslash( $_POST['group_id'] ) ) : '';
			$url_search = isset( $_POST['url_search'] ) ? sanitize_text_field( wp_unslash( $_POST['url_search'] ) ) : '';

			// Build filters array
			$filters = array();
			if ( ! empty( $status ) ) {
				$filters['status'] = $status;
			}
			if ( ! empty( $group_id ) ) {
				$filters['group_id'] = $group_id;
			}
			if ( ! empty( $url_search ) ) {
				$filters['url_search'] = $url_search;
			}

			// This would normally get comparisons from API, but for now delegate to admin
			$comparisons = array(); // Placeholder - would come from API
			
			ob_start();
			$this->admin->load_comparisons_view( $batch_id, $comparisons, $filters );
			$html = ob_get_clean();

			wp_send_json_success( array( 'html' => $html ) );
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
			$this->admin->load_failed_queues_view( $batch_id );
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
			} catch ( Exception $e ) {
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
				// Debug info.
				'debug'                  => array(
					'auto_group_enabled'  => $auto_group['enabled'] ?? 'not set',
					'auto_group_interval' => $auto_group['interval_in_h'] ?? 'not set',
					'auto_group_urls'     => $auto_group['selected_urls_count'] ?? 'not set',
					'update_group_urls'   => $update_group['selected_urls_count'] ?? 'not set',
				),
			)
		);
	}

	/**
	 * AJAX handler to get WCD status for the admin bar.
	 *
	 * @since 1.0.0
	 */
	public function ajax_get_wcd_admin_bar_status() {
		// Delegate to main admin class for now (this is a complex method)
		if ( $this->admin && method_exists( $this->admin, 'ajax_get_wcd_admin_bar_status' ) ) {
			$this->admin->ajax_get_wcd_admin_bar_status();
		} else {
			wp_send_json_error( array( 'message' => __( 'Method not available.', 'webchangedetector' ) ) );
		}
	}
} 