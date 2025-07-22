<?php
/**
 * Account Action Handler for WebChangeDetector
 *
 * Handles all account-related actions and business logic.
 *
 * @package    WebChangeDetector
 * @subpackage WebChangeDetector/admin/actions
 * @author     Mike Miler <mike@wp-mike.com>
 */

namespace WebChangeDetector;

/**
 * Account Action Handler Class.
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
	 * Handle API token save action.
	 *
	 * @param array $data The token data.
	 * @return array Result with success status and message.
	 */
	public function handle_save_api_token( $data ) {
		try {
			$api_token = sanitize_text_field( $data['api_token'] ?? '' );
			
			// Validate token format.
			$validation = $this->validate_api_token( $api_token );
			if ( ! $validation['success'] ) {
				return $validation;
			}

			// Test token validity by trying to get account details.
			$account_test = $this->test_api_token( $api_token );
			if ( ! $account_test['success'] ) {
				return $account_test;
			}

			// Save token to database.
			update_option( WCD_WP_OPTION_KEY_API_TOKEN, $api_token );
			
			// Only set initial setup flag if this is a completely new setup.
			// Check if there are existing sync_url_types configured.
			$website_details = $this->admin->settings_handler->get_website_details( true );
			if ( empty( $website_details['sync_url_types'] ) ) {
				// Set flag that initial setup is needed.
				update_option( WCD_WP_OPTION_KEY_INITIAL_SETUP_NEEDED, true );
			}
			
			// Try to create website and groups if they don't exist.
			$setup_result = $this->setup_website_and_groups();
			
			return array(
				'success' => true,
				'message' => 'API token saved successfully.',
				'setup_result' => $setup_result,
			);
		} catch ( \Exception $e ) {
			return array(
				'success' => false,
				'message' => 'Error saving API token: ' . $e->getMessage(),
			);
		}
	}

	/**
	 * Handle API token reset action.
	 *
	 * @param array $data The reset data.
	 * @return array Result with success status and message.
	 */
	public function handle_reset_api_token( $data ) {
		try {
			// Clear the stored API token.
			delete_option( WCD_WP_OPTION_KEY_API_TOKEN );
			delete_option( WCD_WP_OPTION_KEY_ACCOUNT_EMAIL );
			delete_option( WCD_WP_OPTION_KEY_WEBSITE_ID );
			delete_option( WCD_WEBSITE_GROUPS );
			
			// Clear wizard tracking.
			delete_option( 'wcd_wizard_disabled' );
			
			// Reset activation flag so user can go through account creation process again.
			delete_option( 'wcd_account_activated' );
			delete_option( 'wcd_activation_status' );
			
			// Set flag that initial setup is needed.
			update_option( WCD_WP_OPTION_KEY_INITIAL_SETUP_NEEDED, true );
			
			// Clear any cached account and website details.
			delete_option( WCD_ALLOWANCES );
			delete_option( 'wcd_last_urls_sync' );
			
			return array(
				'success' => true,
				'message' => 'API token reset successfully. You can now enter a new token or create a new account.',
			);
		} catch ( \Exception $e ) {
			return array(
				'success' => false,
				'message' => 'Error resetting API token: ' . $e->getMessage(),
			);
		}
	}

	/**
	 * Handle trial account creation.
	 *
	 * @param array $data The trial account data.
	 * @return array Result with success status and message.
	 */
	public function handle_create_trial_account( $data ) {
		try {
			$email = sanitize_email( $data['email'] ?? '' );
			$name_first = sanitize_text_field( $data['name_first'] ?? '' );
			$name_last = sanitize_text_field( $data['name_last'] ?? '' );
			$password = $data['password'] ?? '';
			
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
				'email' => $email,
				'name_first' => $name_first,
				'name_last' => $name_last,
				'password' => $password,
			);

			// Create trial account via API.
			$result = $this->admin->account_handler->create_trial_account( $account_data );
			
			// Check if API call was successful
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
					'success' => true,
					'message' => 'Trial account created successfully! Check your email for activation.',
					'result' => $result,
					'setup_result' => $setup_result,
				);
			} else {
				// Handle error responses
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
	 * Handle account validation.
	 *
	 * @param array $data The validation data.
	 * @return array Result with success status and message.
	 */
	public function handle_validate_account( $data ) {
		try {
			$current_token = get_option( WCD_WP_OPTION_KEY_API_TOKEN );
			
			if ( empty( $current_token ) ) {
				return array(
					'success' => false,
					'message' => 'No API token found. Please enter your API token.',
				);
			}

			// Test current token.
			$validation = $this->test_api_token( $current_token );
			
			if ( $validation['success'] ) {
				return array(
					'success' => true,
					'message' => 'Account is valid and active.',
					'account' => $validation['account'],
				);
			} else {
				return array(
					'success' => false,
					'message' => 'Account validation failed: ' . $validation['message'],
				);
			}
		} catch ( \Exception $e ) {
			return array(
				'success' => false,
				'message' => 'Error validating account: ' . $e->getMessage(),
			);
		}
	}

	/**
	 * Handle account upgrade action.
	 *
	 * @param array $data The upgrade data.
	 * @return array Result with success status and message.
	 */
	public function handle_account_upgrade( $data ) {
		try {
			$upgrade_url = $this->admin->account_handler->get_upgrade_url();
			
			if ( empty( $upgrade_url ) ) {
				return array(
					'success' => false,
					'message' => 'Unable to get upgrade URL. Please try again later.',
				);
			}

			return array(
				'success' => true,
				'message' => 'Upgrade URL retrieved successfully.',
				'upgrade_url' => $upgrade_url,
			);
		} catch ( \Exception $e ) {
			return array(
				'success' => false,
				'message' => 'Error getting upgrade URL: ' . $e->getMessage(),
			);
		}
	}

	/**
	 * Validate API token format.
	 *
	 * @param string $token The token to validate.
	 * @return array Validation result with success status and errors.
	 */
	private function validate_api_token( $token ) {
		if ( empty( $token ) ) {
			return array(
				'success' => false,
				'message' => 'API token is required.',
			);
		}

		// Check token length.
		if ( strlen( $token ) < WebChangeDetector_Admin::API_TOKEN_LENGTH ) {
			return array(
				'success' => false,
				'message' => 'API token must be at least ' . WebChangeDetector_Admin::API_TOKEN_LENGTH . ' characters long.',
			);
		}

		// Check for valid characters (alphanumeric and some special chars).
		if ( ! preg_match( '/^[a-zA-Z0-9\-_\.]+$/', $token ) ) {
			return array(
				'success' => false,
				'message' => 'API token contains invalid characters.',
			);
		}

		return array(
			'success' => true,
			'message' => 'Token format is valid.',
		);
	}

	/**
	 * Test API token by making an account details request.
	 *
	 * @param string $token The token to test.
	 * @return array Test result with success status and account data.
	 */
	private function test_api_token( $token ) {
		// Temporarily store the token for testing.
		$original_token = get_option( WCD_WP_OPTION_KEY_API_TOKEN );
		update_option( WCD_WP_OPTION_KEY_API_TOKEN, $token );

		try {
			// Try to get account details.
			$account = $this->admin->account_handler->get_account();
			
			if ( ! empty( $account ) && is_array( $account ) ) {
				return array(
					'success' => true,
					'message' => 'Token is valid.',
					'account' => $account,
				);
			} else {
				return array(
					'success' => false,
					'message' => 'Invalid token or account not accessible.',
				);
			}
		} catch ( \Exception $e ) {
			return array(
				'success' => false,
				'message' => 'Token validation failed: ' . $e->getMessage(),
			);
		} finally {
			// Restore original token.
			if ( $original_token ) {
				update_option( WCD_WP_OPTION_KEY_API_TOKEN, $original_token );
			} else {
				delete_option( WCD_WP_OPTION_KEY_API_TOKEN );
			}
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
					'result' => $creation_result,
				);
			} else {
				return array(
					'success' => false,
					'message' => 'Failed to create website and groups: ' . $creation_result['error'],
					'result' => $creation_result,
				);
			}
		} catch ( \Exception $e ) {
			return array(
				'success' => false,
				'message' => 'Error setting up website and groups: ' . $e->getMessage(),
			);
		}
	}

	/**
	 * Get current account status.
	 *
	 * @return array Account status information.
	 */
	public function get_account_status() {
		try {
			$token = get_option( WCD_WP_OPTION_KEY_API_TOKEN );
			
			if ( empty( $token ) ) {
				return array(
					'status' => 'no_token',
					'message' => 'No API token configured.',
				);
			}

			$account = $this->admin->account_handler->get_account();
			
			if ( empty( $account ) ) {
				return array(
					'status' => 'invalid_token',
					'message' => 'API token is invalid or account not accessible.',
				);
			}

			// Check account plan and limits.
			$status = 'active';
			$message = 'Account is active.';
			
			if ( ! empty( $account['plan'] ) && 'trial' === $account['plan'] ) {
				$status = 'trial';
				$message = 'Trial account active.';
			}

			if ( ! empty( $account['renewal_at'] ) ) {
				$renewal_time = strtotime( $account['renewal_at'] );
				$days_until_renewal = floor( ( $renewal_time - time() ) / 86400 );
				
				if ( $days_until_renewal <= 7 ) {
					$status = 'expiring_soon';
					$message = sprintf( 'Account expires in %d days.', $days_until_renewal );
				}
			}

			return array(
				'status' => $status,
				'message' => $message,
				'account' => $account,
			);
		} catch ( \Exception $e ) {
			return array(
				'status' => 'error',
				'message' => 'Error checking account status: ' . $e->getMessage(),
			);
		}
	}

	/**
	 * Handle disable wizard action.
	 *
	 * @param array $data The disable wizard data.
	 * @return array Result with success status and message.
	 */
	public function handle_disable_wizard( $data ) {
		try {
			update_option( 'wcd_wizard_disabled', true );
			
			return array(
				'success' => true,
				'message' => 'Setup wizard disabled successfully.',
			);
		} catch ( \Exception $e ) {
			return array(
				'success' => false,
				'message' => 'Error disabling wizard: ' . $e->getMessage(),
			);
		}
	}
} 