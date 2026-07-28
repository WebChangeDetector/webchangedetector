<?php
/**
 * Account Action Handler for WebChangeDetector
 *
 * Handles trial account creation.
 *
 * @package    WebChangeDetector
 * @subpackage WebChangeDetector/admin/actions
 * @author     Mike Miler <mike@wp-mike.com>
 */

namespace WebChangeDetector;

/**
 * Account Action Handler Class.
 *
 * Focused handler for trial account creation. Website and group provisioning
 * happens post-activation in the admin controller.
 */
class WebChangeDetector_Account_Action_Handler {

	/**
	 * The admin instance.
	 *
	 * @var WebChangeDetector_Admin
	 */
	private $admin;

	/**
	 * Constructor.
	 *
	 * @param WebChangeDetector_Admin $admin The admin instance.
	 */
	public function __construct( $admin ) {
		$this->admin = $admin;
	}

	/**
	 * Handle trial account creation.
	 *
	 * @param array $data The trial account data.
	 * @return array Result with success status and message.
	 */
	public function handle_create_trial_account( $data ) {
		try {
			$email      = sanitize_email( $data['email'] ?? '' );
			$name_first = sanitize_text_field( $data['name_first'] ?? '' );
			$name_last  = sanitize_text_field( $data['name_last'] ?? '' );
			$password   = $data['password'] ?? '';

			if ( empty( $email ) || ! is_email( $email ) ) {
				return array(
					'success' => false,
					'message' => __( 'Valid email address is required.', 'webchangedetector' ),
				);
			}

			if ( empty( $name_first ) || empty( $name_last ) ) {
				return array(
					'success' => false,
					'message' => __( 'First and last name are required.', 'webchangedetector' ),
				);
			}

			if ( empty( $password ) || strlen( $password ) < 6 ) {
				return array(
					'success' => false,
					'message' => __( 'Password must be at least 6 characters long.', 'webchangedetector' ),
				);
			}

			// Prepare data array for account creation.
			$account_data = array(
				'email'      => $email,
				'name_first' => $name_first,
				'name_last'  => $name_last,
				'password'   => $password,
			);

			// Create trial account via API.
			$result = $this->admin->account_handler->create_trial_account( $account_data );

			// Normalize first: api_v1() hands back the raw response body, so a single whitespace
			// byte added by a proxy, WAF or output buffer would fail the length check below and
			// discard a token the API already created and mailed out. The user cannot retry then,
			// because the second attempt is rejected with "email already exists".
			$result = is_string( $result ) ? trim( $result ) : $result;

			// Success: the API returns the bare API token (40-char alphanumeric string) as the
			// response body. Anything else (error strings, arrays, HTML) must never be stored.
			if ( is_string( $result ) && WebChangeDetector_Admin::API_TOKEN_LENGTH === strlen( $result ) && ctype_alnum( $result ) ) {
				// Store account email.
				WebChangeDetector_Multisite::set_shared_option( WCD_WP_OPTION_KEY_ACCOUNT_EMAIL, $email );

				// Save the API token.
				WebChangeDetector_Multisite::set_api_token( $result );

				// Set flag that initial setup is needed.
				update_option( WCD_WP_OPTION_KEY_INITIAL_SETUP_NEEDED, true );

				// Website and groups are provisioned after email activation on the first
				// authenticated page load (admin controller); pre-activation API calls would 403.

				return array(
					'success' => true,
					'message' => __( 'Trial account created successfully! Check your email for activation.', 'webchangedetector' ),
				);
			}

			// Handle error responses.
			$error_message = __( 'Failed to create trial account.', 'webchangedetector' );

			if ( is_array( $result ) && isset( $result[0], $result[1] ) && 'error' === $result[0] && is_string( $result[1] ) ) {
				// API error shape: ["error", "<message>"], e.g. email already exists.
				$error_message = $result[1];
			} elseif ( is_array( $result ) && ! empty( $result['message'] ) && is_string( $result['message'] ) ) {
				$error_message = $result['message'];
			} elseif ( is_array( $result ) && ! empty( $result['error'] ) && is_string( $result['error'] ) ) {
				$error_message = $result['error'];
			} elseif ( is_string( $result ) && '' !== $result ) {
				$error_message = $result;
			}

			return array(
				'success' => false,
				'message' => $error_message,
			);
		} catch ( \Exception $e ) {
			return array(
				'success' => false,
				'message' => sprintf(
					/* translators: %s: error message. */
					__( 'Error creating trial account: %s', 'webchangedetector' ),
					$e->getMessage()
				),
			);
		}
	}
}
