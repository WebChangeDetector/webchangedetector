<?php
/**
 * Account Action Handler for WebChangeDetector
 *
 * Handles trial account creation and related setup tasks.
 *
 * @package    WebChangeDetector
 * @subpackage WebChangeDetector/admin/actions
 * @author     Mike Miler <mike@wp-mike.com>
 */

namespace WebChangeDetector;

/**
 * Account Action Handler Class.
 *
 * Focused handler for trial account creation and initial website setup.
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
					'message' => 'Valid email address is required.',
				);
			}

			if ( empty( $name_first ) || empty( $name_last ) ) {
				return array(
					'success' => false,
					'message' => 'First and last name are required.',
				);
			}

			if ( empty( $password ) || strlen( $password ) < 6 ) {
				return array(
					'success' => false,
					'message' => 'Password must be at least 6 characters long.',
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

			// Check if API call was successful.
			// API can return either an array with data or error strings like 'unauthorized', 'activate account', etc.
			if ( is_string( $result ) ) {
				// Store account email.
				update_option( WCD_WP_OPTION_KEY_ACCOUNT_EMAIL, $email );

				// Save the API token.
				update_option( WCD_WP_OPTION_KEY_API_TOKEN, $result );

				// Set flag that initial setup is needed.
				update_option( WCD_WP_OPTION_KEY_INITIAL_SETUP_NEEDED, true );

				// Setup website and groups.
				$setup_result = $this->setup_website_and_groups();

				return array(
					'success'      => true,
					'message'      => 'Trial account created successfully! Check your email for activation.',
					'result'       => $result,
					'setup_result' => $setup_result,
				);
			} else {
				// Handle error responses.
				$error_message = 'Failed to create trial account.';

				if ( is_string( $result ) ) {
					switch ( $result ) {
						case 'unauthorized':
							$error_message = 'Unauthorized request. Please try again.';
							break;
						case 'activate account':
						case 'ActivateAccount':
							$error_message = 'Account created but needs activation. Check your email.';
							break;
						case 'update plugin':
							$error_message = 'Plugin update required. Please update the plugin.';
							break;
						default:
							$error_message = $result;
							break;
					}
				} elseif ( is_array( $result ) && ! empty( $result['message'] ) ) {
					$error_message = $result['message'];
				} elseif ( is_array( $result ) && ! empty( $result['error'] ) ) {
					$error_message = $result['error'];
				}

				return array(
					'success' => false,
					'message' => $error_message,
				);
			}
		} catch ( \Exception $e ) {
			return array(
				'success' => false,
				'message' => 'Error creating trial account: ' . $e->getMessage(),
			);
		}
	}

	/**
	 * Setup website and groups after successful token validation.
	 *
	 * @return array Setup result.
	 */
	private function setup_website_and_groups() {
		try {
			// Check if groups already exist.
			$existing_groups = get_option( WCD_WEBSITE_GROUPS );

			if ( ! empty( $existing_groups ) &&
			! empty( $existing_groups[ WCD_AUTO_DETECTION_GROUP ] ) &&
			! empty( $existing_groups[ WCD_MANUAL_DETECTION_GROUP ] ) ) {
				return array(
					'success' => true,
					'message' => 'Website and groups already configured.',
				);
			}

			// Create website and groups.
			$creation_result = $this->admin->create_website_and_groups();

			if ( empty( $creation_result['error'] ) ) {
				return array(
					'success' => true,
					'message' => 'Website and groups created successfully.',
					'result'  => $creation_result,
				);
			} else {
				return array(
					'success' => false,
					'message' => 'Failed to create website and groups: ' . $creation_result['error'],
					'result'  => $creation_result,
				);
			}
		} catch ( \Exception $e ) {
			return array(
				'success' => false,
				'message' => 'Error setting up website and groups: ' . $e->getMessage(),
			);
		}
	}
}
