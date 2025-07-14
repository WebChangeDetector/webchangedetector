<?php

namespace WebChangeDetector;

/**
 * Account AJAX handler.
 *
 * Handles all account and dashboard-related AJAX operations including
 * account activation checking and usage statistics retrieval.
 *
 * @link       https://www.webchangedetector.com
 * @since      4.0.0
 * @package    WebChangeDetector
 * @subpackage WebChangeDetector/admin/ajax
 */

/**
 * Account AJAX handler.
 *
 * Handles all account and dashboard-related AJAX operations.
 *
 * @since      4.0.0
 * @package    WebChangeDetector
 * @subpackage WebChangeDetector/admin/ajax
 * @author     Mike Miler <mike@webchangedetector.com>
 */
class WebChangeDetector_Account_Ajax_Handler extends WebChangeDetector_Ajax_Handler_Base {

	/**
	 * The account handler instance.
	 *
	 * @since    4.0.0
	 * @access   private
	 * @var      WebChangeDetector_Admin_Account    $account_handler    The account handler instance.
	 */
	private $account_handler;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    4.0.0
	 * @param    WebChangeDetector_Admin            $admin           The main admin class instance.
	 * @param    WebChangeDetector_API_Manager      $api_manager     The API manager instance.
	 * @param    WebChangeDetector_Admin_Account    $account_handler The account handler instance.
	 */
	public function __construct( $admin, $api_manager, $account_handler ) {
		parent::__construct( $admin, $api_manager );
		
		$this->account_handler = $account_handler;
	}

	/**
	 * Register AJAX hooks for account operations.
	 *
	 * Registers all WordPress AJAX hooks for account-related operations.
	 *
	 * @since    4.0.0
	 */
	public function register_hooks() {
		add_action( 'wp_ajax_wcd_check_activation_status', array( $this, 'ajax_check_activation_status' ) );
		add_action( 'wp_ajax_get_dashboard_usage_stats', array( $this, 'ajax_get_dashboard_usage_stats' ) );
	}

	/**
	 * Handle check activation status AJAX request.
	 *
	 * Checks if the account is activated and returns the status.
	 *
	 * @since    4.0.0
	 */
	public function ajax_check_activation_status() {
		if ( ! $this->security_check() ) {
			return;
		}

		try {
			if ( ! $this->account_handler ) {
				$this->send_error_response( 
					__( 'Account handler not available.', 'webchangedetector' ),
					'Account handler missing'
				);
				return;
			}

			$token = get_option( 'wcd_api_token' );
			
			if ( empty( $token ) ) {
				$this->send_success_response( 
					array( 'activated' => false ),
					__( 'No API token found.', 'webchangedetector' )
				);
				return;
			}
			
			$account_details = $this->account_handler->get_account();
			
			if ( is_array( $account_details ) && ! empty( $account_details ) ) {
				$this->send_success_response( 
					array( 
						'activated' => true,
						'account_details' => $account_details,
					),
					__( 'Account is activated.', 'webchangedetector' )
				);
			} else {
				$this->send_success_response( 
					array( 'activated' => false ),
					__( 'Account activation pending.', 'webchangedetector' )
				);
			}
			
		} catch ( \Exception $e ) {
			$this->admin->log_error( 'Account activation check failed: ' . $e->getMessage() );
			$this->send_success_response( 
				array( 'activated' => false ),
				__( 'Account activation pending.', 'webchangedetector' )
			);
		}
	}

	/**
	 * Handle get dashboard usage stats AJAX request.
	 *
	 * Retrieves usage statistics for the dashboard display.
	 *
	 * @since    4.0.0
	 */
	public function ajax_get_dashboard_usage_stats() {
		if ( ! $this->security_check() ) {
			return;
		}

		try {
			$website_details = $this->admin->website_details;
			
			if ( empty( $website_details ) || ! isset( $website_details['uuid'] ) ) {
				$this->send_error_response( 
					__( 'Website details not available.', 'webchangedetector' ),
					'Website details missing'
				);
				return;
			}

			$uuid = $website_details['uuid'];
			
			// Get usage statistics from API.
			$usage_stats = $this->api_manager->get_usage_stats( $uuid );
			
			if ( is_wp_error( $usage_stats ) ) {
				$this->send_error_response( 
					__( 'Failed to get usage statistics.', 'webchangedetector' ),
					'API error: ' . $usage_stats->get_error_message()
				);
				return;
			}
			
			// Get account details for context.
			$account_details = $this->account_handler->get_account();
			
			$response_data = array(
				'usage_stats' => $usage_stats,
				'account_details' => $account_details,
				'website_details' => $website_details,
			);
			
			$this->send_success_response( 
				$response_data,
				__( 'Usage statistics retrieved successfully.', 'webchangedetector' )
			);
			
		} catch ( \Exception $e ) {
			$this->send_error_response( 
				__( 'An error occurred while getting usage statistics.', 'webchangedetector' ),
				'Exception: ' . $e->getMessage()
			);
		}
	}

}