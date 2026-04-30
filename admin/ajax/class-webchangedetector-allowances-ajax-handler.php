<?php
/**
 * Allowances AJAX handler.
 *
 * Handles saving allowances for multisite sub-websites.
 * Only accessible by super admins in the network admin context.
 *
 * @link       https://www.webchangedetector.com
 * @since      4.3.0
 * @package    WebChangeDetector
 * @subpackage WebChangeDetector/admin/ajax
 */

namespace WebChangeDetector;

/**
 * Allowances AJAX handler.
 *
 * @since      4.3.0
 * @package    WebChangeDetector
 * @subpackage WebChangeDetector/admin/ajax
 * @author     Mike Miler <mike@webchangedetector.com>
 */
class WebChangeDetector_Allowances_Ajax_Handler extends WebChangeDetector_Ajax_Handler_Base {

	/**
	 * All allowance field keys.
	 *
	 * @var array
	 */
	const ALLOWANCE_FIELDS = array(
		'manual_checks_view',
		'monitoring_checks_view',
		'change_detections_view',
		'ai_rules_view',
		'settings_view',
		'logs_view',
		'manual_checks_start',
		'manual_checks_settings',
		'manual_checks_urls',
		'monitoring_checks_settings',
		'monitoring_checks_urls',
		'settings_add_urls',
		'settings_account_settings',
		'upgrade_account',
		'wizard_start',
		'only_frontpage',
	);

	/**
	 * Register AJAX hooks.
	 *
	 * @since 4.3.0
	 */
	public function register_hooks() {
		add_action( 'wp_ajax_wcd_save_allowances', array( $this, 'ajax_save_allowances' ) );
	}

	/**
	 * Handle save allowances AJAX request.
	 *
	 * Saves allowances for a single sub-website or all sub-websites.
	 *
	 * @since 4.3.0
	 */
	public function ajax_save_allowances() {
		if ( ! $this->security_check( 'ajax-nonce', 'manage_network_options' ) ) {
			return;
		}

		$allowances = $this->extract_allowances_from_post();
		if ( empty( $allowances ) ) {
			$this->send_error_response( __( 'No allowance data received.', 'webchangedetector' ) );
			return;
		}

		if ( WebChangeDetector_Multisite::is_all_sites_mode() ) {
			$this->save_allowances_all_sites( $allowances );
		} else {
			$this->save_allowances_single_site( $allowances );
		}
	}

	/**
	 * Extract and sanitize allowance values from POST data.
	 *
	 * @since 4.3.0
	 * @return array Sanitized allowances array.
	 */
	private function extract_allowances_from_post() {
		$allowances = array();

		foreach ( self::ALLOWANCE_FIELDS as $field ) {
			$post_key = 'allowances_' . $field;
			// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Already verified in security_check.
			if ( isset( $_POST[ $post_key ] ) ) {
				// phpcs:ignore WordPress.Security.NonceVerification.Missing
				$allowances[ $field ] = '1' === sanitize_text_field( wp_unslash( $_POST[ $post_key ] ) );
			}
		}

		return $allowances;
	}

	/**
	 * Save allowances for a single sub-website.
	 *
	 * @since 4.3.0
	 * @param array $allowances The allowances to save.
	 */
	private function save_allowances_single_site( $allowances ) {
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Already verified in security_check.
		$website_uuid = isset( $_POST['website_uuid'] ) ? sanitize_text_field( wp_unslash( $_POST['website_uuid'] ) ) : '';

		if ( empty( $website_uuid ) ) {
			$this->send_error_response( __( 'No website UUID provided.', 'webchangedetector' ) );
			return;
		}

		$result = WebChangeDetector_API_V2::update_website_v2(
			$website_uuid,
			array( 'allowances' => $allowances )
		);

		if ( is_string( $result ) || ( is_array( $result ) && ! empty( $result['error'] ) ) ) {
			$error_msg = is_string( $result ) ? sanitize_text_field( $result ) : sanitize_text_field( $result['error'] );
			$this->send_error_response(
				sprintf(
					/* translators: %s: error message */
					__( 'Failed to update allowances: %s', 'webchangedetector' ),
					$error_msg
				)
			);
			return;
		}

		// Update local allowances option on the target blog.
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Already verified in security_check.
		$blog_id = isset( $_POST['wcd_blog_id'] ) ? absint( $_POST['wcd_blog_id'] ) : 0;
		if ( $blog_id > 0 && get_blog_details( $blog_id ) ) {
			switch_to_blog( $blog_id );
			update_option( 'wcd_allowances', $allowances );
			restore_current_blog();
		}

		$this->send_success_response(
			null,
			__( 'Allowances saved successfully.', 'webchangedetector' )
		);
	}

	/**
	 * Save allowances for all registered sub-websites.
	 *
	 * @since 4.3.0
	 * @param array $allowances The allowances to save.
	 */
	private function save_allowances_all_sites( $allowances ) {
		$sites = WebChangeDetector_Multisite::get_all_sites_with_status();

		// Build bulk requests for all registered sites.
		// Both arrays are built in sync: same continue condition, same append order.
		$requests = array();
		$blog_ids = array();

		foreach ( $sites as $site ) {
			if ( ! $site['registered'] || empty( $site['website_id'] ) ) {
				continue;
			}

			$requests[] = array(
				'action'     => 'websites/' . $site['website_id'],
				'allowances' => $allowances,
			);
			$blog_ids[] = $site['blog_id'];
		}

		if ( empty( $requests ) ) {
			$this->send_error_response( __( 'No registered sites found.', 'webchangedetector' ) );
			return;
		}

		$results       = WebChangeDetector_API_V2::api_v2_bulk( $requests, 'PUT' );
		$success_count = 0;
		$fail_count    = 0;

		foreach ( $results as $index => $result ) {
			if ( ! empty( $result['success'] ) ) {
				++$success_count;

				// Update local allowances option on this blog.
				if ( isset( $blog_ids[ $index ] ) ) {
					switch_to_blog( $blog_ids[ $index ] );
					update_option( 'wcd_allowances', $allowances );
					restore_current_blog();
				}
			} else {
				++$fail_count;
			}
		}

		if ( $fail_count > 0 ) {
			$this->send_success_response(
				array(
					'success_count' => $success_count,
					'fail_count'    => $fail_count,
				),
				sprintf(
					/* translators: 1: success count, 2: fail count */
					__( 'Allowances updated for %1$d sites. %2$d failed.', 'webchangedetector' ),
					$success_count,
					$fail_count
				)
			);
		} else {
			$this->send_success_response(
				array( 'success_count' => $success_count ),
				sprintf(
					/* translators: %d: number of sites */
					__( 'Allowances updated for all %d sites.', 'webchangedetector' ),
					$success_count
				)
			);
		}
	}
}
