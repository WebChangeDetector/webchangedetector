<?php
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

namespace WebChangeDetector;

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
	 * @param    WebChangeDetector_Admin         $admin           The main admin class instance.
	 * @param    WebChangeDetector_Admin_Account $account_handler The account handler instance.
	 */
	public function __construct( $admin, $account_handler ) {
		parent::__construct( $admin );

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
						'activated'       => true,
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
	 * Retrieves usage statistics for the dashboard including auto-detection,
	 * auto-update checks, and account limits.
	 *
	 * @since    4.0.0
	 */
	public function ajax_get_dashboard_usage_stats() {
		if ( ! $this->security_check() ) {
			return;
		}

		try {
			// Get group data for usage calculations.
			$monitoring_group_uuid = $this->admin->monitoring_group_uuid ?? '';
			$manual_group_uuid     = $this->admin->manual_group_uuid ?? '';

			$auto_group   = array();
			$update_group = array();

			if ( ! empty( $monitoring_group_uuid ) ) {
				$auto_group_response = \WebChangeDetector\WebChangeDetector_API_V2::get_group_v2( $monitoring_group_uuid );
				$auto_group          = isset( $auto_group_response['data'] ) ? $auto_group_response['data'] : array();
			}

			if ( ! empty( $manual_group_uuid ) ) {
				$update_group_response = \WebChangeDetector\WebChangeDetector_API_V2::get_group_v2( $manual_group_uuid );
				$update_group          = isset( $update_group_response['data'] ) ? $update_group_response['data'] : array();
			}

			// Calculate auto detection amount.
			$amount_auto_detection = 0;
			if ( ! empty( $auto_group['enabled'] ) && ! empty( $auto_group['interval_in_h'] ) && ! empty( $auto_group['selected_urls_count'] ) ) {
				$amount_auto_detection = ( WCD_HOURS_IN_DAY / $auto_group['interval_in_h'] ) * $auto_group['selected_urls_count'] * WCD_DAYS_PER_MONTH;
			}

			// Get auto update settings.
			$auto_update_settings    = \WebChangeDetector\WebChangeDetector_Autoupdates::get_auto_update_settings();
			$max_auto_update_checks  = 0;
			$amount_auto_update_days = 0;

			if ( ! empty( $auto_update_settings['auto_update_checks_enabled'] ) ) {
				// Count enabled weekdays.
				$weekdays = array( 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday' );
				foreach ( $weekdays as $weekday ) {
					if ( isset( $auto_update_settings[ 'auto_update_checks_' . $weekday ] ) &&
						! empty( $auto_update_settings[ 'auto_update_checks_' . $weekday ] ) ) {
						++$amount_auto_update_days;
					}
				}

				// Calculate max auto update checks.
				$update_urls_count      = isset( $update_group['selected_urls_count'] ) ? $update_group['selected_urls_count'] : 0;
				$max_auto_update_checks = $update_urls_count * $amount_auto_update_days * 4; // multiplied by weeks in a month.
			}

			// Get account data for renewal calculations.
			$client_account = $this->account_handler->get_account();

			if ( empty( $client_account ) || ! is_array( $client_account ) ) {
				$this->send_error_response(
					__( 'Unable to retrieve account information.', 'webchangedetector' ),
					'Account data not available'
				);
				return;
			}

			// Calculate checks until renewal.
			$checks_until_renewal = 0;
			if ( ! empty( $client_account['renewal_at'] ) && $amount_auto_detection > 0 ) {
				$renewal_timestamp     = strtotime( $client_account['renewal_at'] );
				$current_timestamp     = time();
				$seconds_until_renewal = $renewal_timestamp - $current_timestamp;

				if ( $seconds_until_renewal > 0 && defined( 'WCD_SECONDS_IN_MONTH' ) ) {
					$checks_until_renewal = ( $amount_auto_detection / WCD_SECONDS_IN_MONTH ) * $seconds_until_renewal;
				}
			}

			// Calculate checks needed and available.
			$checks_needed    = $checks_until_renewal + $max_auto_update_checks;
			$checks_available = 0;

			if ( isset( $client_account['checks_limit'] ) && isset( $client_account['checks_done'] ) ) {
				$checks_available = $client_account['checks_limit'] - $client_account['checks_done'];
			}

			// Prepare response data.
			$response_data = array(
				'amount_auto_detection'  => $amount_auto_detection,
				'max_auto_update_checks' => $max_auto_update_checks,
				'checks_needed'          => $checks_needed,
				'checks_available'       => $checks_available,
				'checks_until_renewal'   => $checks_until_renewal,
				'auto_group_enabled'     => isset( $auto_group['enabled'] ) ? $auto_group['enabled'] : 'not set',
				'auto_group_interval'    => isset( $auto_group['interval_in_h'] ) ? $auto_group['interval_in_h'] : 'not set',
				'auto_group_urls'        => isset( $auto_group['selected_urls_count'] ) ? $auto_group['selected_urls_count'] : 'not set',
				'update_group_urls'      => isset( $update_group['selected_urls_count'] ) ? $update_group['selected_urls_count'] : 'not set',
				'auto_update_settings'   => $auto_update_settings,
			);

			$this->send_success_response( $response_data );

		} catch ( \Exception $e ) {
			$this->send_error_response(
				__( 'An error occurred while getting dashboard usage stats.', 'webchangedetector' ),
				'Exception: ' . $e->getMessage()
			);
		}
	}
}
