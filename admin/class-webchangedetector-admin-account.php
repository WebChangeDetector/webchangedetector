<?php
/**
 * WebChange Detector Admin Account Management Class
 *
 * This class handles all account and API management functionality for the WebChange Detector plugin.
 * Extracted from the main admin class as part of the refactoring process to improve code organization
 * and maintainability following WordPress coding standards.
 *
 * @link       https://webchangedetector.com
 * @since      1.0.0
 *
 * @package    WebChangeDetector
 * @subpackage WebChangeDetector/admin
 */

namespace WebChangeDetector;

/**
 * The account management functionality of the plugin.
 *
 * Defines all functionality related to user accounts, API tokens, billing,
 * and authentication for the WebChange Detector service.
 *
 * @package    WebChangeDetector
 * @subpackage WebChangeDetector/admin
 * @author     Mike Miler <mike@webchangedetector.com>
 * @since      1.0.0
 */
class WebChangeDetector_Admin_Account {

	/**
	 * The API manager instance.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      WebChangeDetector_API_Manager    $api_manager    API communication handler.
	 */
	private $api_manager;

	/**
	 * Cached account details.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      array|null    $account_details    Cached account details from API.
	 */
	private $account_details;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param    WebChangeDetector_API_Manager    $api_manager    The API manager instance.
	 */
	public function __construct( $api_manager ) {
		$this->api_manager = $api_manager;
	}

	/**
	 * Create a trial account for the user.
	 *
	 * Handles the creation of a new trial account by generating a validation string,
	 * hashing the password, and sending the request to the WebChange Detector API.
	 *
	 * @since    1.0.0
	 * @param    array    $postdata    The form data containing user information.
	 * @return   string|array          The API response or error message.
	 */
	public function create_trial_account( $postdata ) {
		// Generate validation string.
		$validation_string = wp_generate_password( 40 );
		update_option( WCD_VERIFY_SECRET, $validation_string, false );
		$postdata['password'] = wp_hash_password( $postdata['password'] );
		
		$args = array_merge(
			array(
				'action'            => 'add_trial_account',
				'ip'                => isset( $_SERVER['SERVER_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['SERVER_ADDR'] ) ) : '',
				'domain'            => \WebChangeDetector\WebChangeDetector_Admin_Utils::get_domain_from_site_url(),
				'validation_string' => $validation_string,
				'cms'               => 'wordpress',
			),
			$postdata
		);

		return $this->api_v1( $args, true );
	}

	/**
	 * Save the API token to the WordPress database.
	 *
	 * Validates the API token and saves it along with the user's email address
	 * for account activation purposes.
	 *
	 * @since    1.0.0
	 * @param    array     $postdata     The form data containing user information.
	 * @param    string    $api_token    The API token to save.
	 * @return   bool                    True if saved successfully, false otherwise.
	 */
	public function save_api_token( $postdata, $api_token ) {
		if ( ! is_string( $api_token ) || strlen( $api_token ) < 10 ) { // API_TOKEN_LENGTH constant from original
			if ( is_array( $api_token ) && 'error' === $api_token[0] && ! empty( $api_token[1] ) ) {
				echo '<div class="notice notice-error"><p>' . esc_html( $api_token[1] ) . '</p></div>';
			} else {
				echo '<div class="notice notice-error">
                        <p>' . esc_html__( 'The API Token is invalid. Please try again or contact us if the error persists', 'webchangedetector' ) . '</p>
                        </div>';
			}
			$this->get_no_account_page();
			return false;
		}

		// Save email address on account creation for showing on activate account page.
		if ( ! empty( $postdata['email'] ) ) {
			update_option( WCD_WP_OPTION_KEY_ACCOUNT_EMAIL, sanitize_email( wp_unslash( $postdata['email'] ) ), false );
		}
		update_option( WCD_WP_OPTION_KEY_API_TOKEN, sanitize_text_field( $api_token ), false );

		return true;
	}

	/**
	 * Get account details from the API.
	 *
	 * Retrieves account information from the WebChange Detector API,
	 * with caching support to avoid unnecessary API calls.
	 *
	 * @since    1.0.0
	 * @param    bool    $force    Force fresh data from API, bypassing cache.
	 * @return   array|string|bool Account data array, error message string, or false on failure.
	 */
	public function get_account( $force = false ) {
		static $account_details;
		if ( $account_details && ! $force ) {
			return $account_details;
		}

		$account_details = \WebChangeDetector\WebChangeDetector_API_V2::get_account_v2();

		if ( ! empty( $account_details['data'] ) ) {
			$account_details                 = $account_details['data'];
			$account_details['checks_limit'] = $account_details['checks_done'] + $account_details['checks_left'];
			return $account_details;
		}
		if ( ! empty( $account_details['message'] ) ) {
			return $account_details['message'];
		}

		return false;
	}

	/**
	 * Get the upgrade URL for the user's account.
	 *
	 * Constructs the billing upgrade URL using the user's magic login secret
	 * for seamless authentication to the billing system.
	 *
	 * @since    1.0.0
	 * @return   string    The upgrade URL or no-billing-account page reference.
	 */
	public function get_upgrade_url() {
		static $upgrade_url;
		if ( $upgrade_url ) {
			return $upgrade_url;
		}

		$account_details = $this->get_account();

		$allowances = get_option( WCD_ALLOWANCES, array() );
		$upgrade_allowed = isset( $allowances['upgrade_account'] ) ? $allowances['upgrade_account'] : 1;
		
		// Disable upgrade account for subaccounts.
		if ( ! empty( $account_details['is_subaccount'] ) && $account_details['is_subaccount'] ) {
			$upgrade_allowed = 0;
		}
		
		if ( ! $upgrade_allowed || ! is_array( $account_details ) || empty( $account_details['magic_login_secret'] ) ) {
			return '?page=webchangedetector-no-billing-account';
		}

		$upgrade_url = $this->get_billing_url() . '?secret=' . $account_details['magic_login_secret'];
		update_option( WCD_WP_OPTION_KEY_UPGRADE_URL, $upgrade_url );

		return $upgrade_url;
	}

	/**
	 * Get the main application URL.
	 *
	 * Returns the WebChange Detector application URL, with support for
	 * custom domains in development environments.
	 *
	 * @since    1.0.0
	 * @return   string    The application URL.
	 */
	public function get_app_url() {
		if ( defined( 'WCD_APP_DOMAIN' ) && is_string( WCD_APP_DOMAIN ) && ! empty( WCD_APP_DOMAIN ) ) {
			return WCD_APP_DOMAIN;
		}
		return 'https://www.webchangedetector.com/';
	}

	/**
	 * Get the billing system URL.
	 *
	 * Returns the URL for the billing system, with support for custom
	 * billing domains in development environments.
	 *
	 * @since    1.0.0
	 * @return   string    The billing URL.
	 */
	public function get_billing_url() {
		if ( defined( 'WCD_BILLING_DOMAIN' ) && is_string( WCD_BILLING_DOMAIN ) && ! empty( WCD_BILLING_DOMAIN ) ) {
			return WCD_BILLING_DOMAIN;
		}
		return $this->get_app_url() . 'billing/';
	}

	/**
	 * Generate the API token form HTML.
	 *
	 * Creates the form interface for users to enter their API token
	 * for connecting their WordPress site to WebChange Detector.
	 *
	 * @since    1.0.0
	 * @param    string|bool    $api_token    Existing API token or false.
	 * @return   void                         Outputs the form HTML.
	 */
	public function get_api_token_form( $api_token = false ) {
		if ( $api_token ) {
			?>
			<div class="wcd-settings-section">
				<div class="wcd-settings-card">
					<h2><span class="dashicons dashicons-admin-users"></span> Account</h2>
					<form action="<?php echo esc_url( admin_url() . '/admin.php?page=webchangedetector' ); ?>" method="post"
						onsubmit="return confirm('Are sure you want to reset the API token?');">
						<input type="hidden" name="wcd_action" value="reset_api_token">
						<?php wp_nonce_field( 'reset_api_token' ); ?>

						<div class="wcd-form-row">
							<div class="wcd-form-label-wrapper">
								<label class="wcd-form-label">Account Information</label>
								<div class="wcd-description">Your account details and API token for WebChange Detector service.</div>
							</div>
							<div class="wcd-form-control">
								<div class="wcd-account-info">
									<p><strong>Email:</strong> <?php echo esc_html( $this->get_account()['email'] ); ?></p>
									<p><strong>API Token:</strong></p>
									<div class="wcd-api-token-section">
										<div class="wcd-api-token-controls">
											<span id="api-token-display" style="display: none; font-family: monospace; font-size: 13px;"><?php echo esc_html( $api_token ); ?></span>
											<span id="api-token-hidden" style="font-family: monospace; letter-spacing: 2px;">••••••••••••••••••••••••••••••••••••••••••••••••••</span>
											<button type="button" id="toggle-api-token" class="wcd-token-toggle" title="Show/Hide API Token" style="background: none; border: none; cursor: pointer; margin-left: 10px; color: #0073aa;">
												<span class="dashicons dashicons-hidden" id="toggle-icon"></span>
											</button>
										</div>
										<p class="wcd-security-note" style="font-size: 12px; color: #666; margin-top: 8px;">
											Keep your API token secure and never share it publicly
										</p>
									</div>
									
									<script>
									document.addEventListener('DOMContentLoaded', function() {
										const toggleBtn = document.getElementById('toggle-api-token');
										const tokenDisplay = document.getElementById('api-token-display');
										const tokenHidden = document.getElementById('api-token-hidden');
										const toggleIcon = document.getElementById('toggle-icon');
										let isVisible = false;

										toggleBtn.addEventListener('click', function() {
											isVisible = !isVisible;
											
											if (isVisible) {
												tokenDisplay.style.display = 'inline';
												tokenHidden.style.display = 'none';
												toggleIcon.className = 'dashicons dashicons-visibility';
												toggleBtn.title = 'Hide API Token';
											} else {
												tokenDisplay.style.display = 'none';
												tokenHidden.style.display = 'inline';
												toggleIcon.className = 'dashicons dashicons-hidden';
												toggleBtn.title = 'Show API Token';
											}
										});
									});
									</script>
								</div>
							</div>
						</div>
						
						<div class="wcd-form-row">
							<div class="wcd-form-label-wrapper">
								<label class="wcd-form-label">Reset API Token</label>
								<div class="wcd-description">With resetting the API Token, auto detections still continue and your settings will be still available when you use the same api token with this website again.</div>
							</div>
							<div class="wcd-form-control">
								<input type="submit" value="Reset API Token" class="button button-delete">
							</div>
						</div>
					</form>
				</div>
			</div>

			<div class="wcd-settings-section">
				<div class="wcd-settings-card">
					<h2><span class="dashicons dashicons-trash"></span> Delete Account</h2>
					<div class="wcd-form-row">
						<div class="wcd-form-label-wrapper">
							<label class="wcd-form-label">Account Deletion</label>
							<div class="wcd-description">To completely remove your account and all associated data.</div>
						</div>
						<div class="wcd-form-control">
							<p>To delete your account completely, please login to your account at
								<a href="https://www.webchangedetector.com" target="_blank">webchangedetector.com</a>.
							</p>
						</div>
					</div>
				</div>
			</div>
			<?php
		} else {
			if ( isset( $_POST['wcd_action'] ) && 'save_api_token' === sanitize_text_field( wp_unslash( $_POST['wcd_action'] ) ) ) {
				check_admin_referer( 'save_api_token' );
			}
			$api_token_after_reset = isset( $_POST['api_token'] ) ? sanitize_text_field( wp_unslash( $_POST['api_token'] ) ) : false;
			?>
			<div class="highlight-container">
				<form class="frm_use_api_token highlight-inner" action="<?php echo esc_url( admin_url() ); ?>/admin.php?page=webchangedetector" method="post">
					<input type="hidden" name="wcd_action" value="save_api_token">
					<?php wp_nonce_field( 'save_api_token' ); ?>
					<h2>Use Existing API Token</h2>
					<p>
						Use the API token of your existing account. To get your API token, please login to your account at
						<a href="<?php echo esc_url( $this->get_app_url() ); ?>login" target="_blank">webchangedetector.com</a>
					</p>
					<input type="text" name="api_token" value="<?php echo esc_html( $api_token_after_reset ); ?>" required>
					<input type="submit" value="Save" class="button button-primary">
				</form>
			</div>
			<?php
		}
	}

	/**
	 * Display the no account page.
	 *
	 * Shows the registration/login interface when users don't have
	 * a valid account or API token configured.
	 *
	 * @since    1.0.0
	 * @param    string    $api_token    Optional API token for pre-filling forms.
	 * @return   void                    Outputs the no account page HTML.
	 */
	public function get_no_account_page( $api_token = '' ) {
		// Set initial setup needed flag for new users (only if no API token exists).
		if ( empty( get_option( WCD_WP_OPTION_KEY_API_TOKEN, '' ) ) ) {
			update_option( WCD_WP_OPTION_KEY_INITIAL_SETUP_NEEDED, true );
		}
		
		$user = wp_get_current_user();
		$first_name = $user->user_firstname;
		$last_name = $user->user_lastname;
		$email = $user->user_email;
		?>
		<div class="no-account-page">
			<div class="no-account">
				<img src="<?php echo esc_url( plugin_dir_url( dirname( __FILE__ ) ) . 'admin/img/logo-webchangedetector.png' ); ?>" alt="<?php echo esc_attr__( 'WebChangeDetector Logo', 'webchangedetector' ); ?>" class="wcd-logo">
				<h2><?php echo esc_html__( 'See what changed before your users do.', 'webchangedetector' ); ?></h2>
			</div>
			<div class="highlight-wrapper">
				<div class="highlight-container">
					<div class="highlight-inner">
						<h2><?php echo esc_html__( 'Create Free Account', 'webchangedetector' ); ?></h2>
						<p>
							<?php echo esc_html__( 'Create your free account with', 'webchangedetector' ); ?><br><strong><?php echo esc_html__( '1000 checks', 'webchangedetector' ); ?></strong> <?php echo esc_html__( 'in the first month and', 'webchangedetector' ); ?> <strong><?php echo esc_html__( '50 checks', 'webchangedetector' ); ?></strong> <?php echo esc_html__( 'after.', 'webchangedetector' ); ?><br>
						</p>
						<form class="frm_new_account" method="post">
							<input type="hidden" name="wcd_action" value="create_trial_account">
							<?php wp_nonce_field( 'create_trial_account' ); ?>
							<input type="text" name="name_first" placeholder="<?php echo esc_attr__( 'First Name', 'webchangedetector' ); ?>" value="<?php echo esc_html( $first_name ); ?>" required>
							<input type="text" name="name_last" placeholder="<?php echo esc_attr__( 'Last Name', 'webchangedetector' ); ?>" value="<?php echo esc_html( $last_name ); ?>" required>
							<input type="email" name="email" placeholder="<?php echo esc_attr__( 'Email', 'webchangedetector' ); ?>" value="<?php echo esc_html( $email ); ?>" required>
							<input type="password" name="password" placeholder="<?php echo esc_attr__( 'Password', 'webchangedetector' ); ?>" required>

							<input type="submit" class="button-primary" value="<?php echo esc_attr__( 'Create Free Account', 'webchangedetector' ); ?>">
						</form>
					</div>
				</div>

				<?php $this->get_api_token_form( $api_token ); ?>
			</div>
		</div>
		<?php
	}

	/**
	 * Display the account activation page.
	 *
	 * Shows instructions and interface for users to activate their
	 * newly created accounts.
	 *
	 * @since    1.0.0
	 * @param    string    $error    Optional error message to display.
	 * @return   void               Outputs the activation page HTML.
	 */
	public function show_activate_account( $error ) {
		$account_email = get_option( WCD_WP_OPTION_KEY_ACCOUNT_EMAIL, '' );
		?>
		<div class="wcd-activate-account-modern">
			<div class="wcd-activation-container">
				<div class="wcd-activation-header">
					<div class="wcd-activation-icon">
						<span class="dashicons dashicons-email-alt2"></span>
					</div>
					<h2><?php esc_html_e( 'Account Activation Required', 'webchangedetector' ); ?></h2>
				</div>
				
				<?php if ( ! empty( $error ) && $error !== 'ActivateAccount' && $error !== 'activate account' ) : ?>
					<div class="wcd-error-card">
						<span class="dashicons dashicons-warning"></span>
						<p><?php echo esc_html( $error ); ?></p>
					</div>
				<?php endif; ?>
				
				<div class="wcd-activation-card">
					<div class="wcd-activation-content">
						<h3><?php esc_html_e( 'Check Your Email', 'webchangedetector' ); ?></h3>
						<p class="wcd-activation-description">
							<?php esc_html_e( 'We\'ve sent an activation link to your email address. Please click the link to complete your account setup.', 'webchangedetector' ); ?>
						</p>
						
						<?php if ( $account_email ) : ?>
							<div class="wcd-email-display">
								<span class="dashicons dashicons-email"></span>
								<strong><?php echo esc_html( $account_email ); ?></strong>
							</div>
						<?php endif; ?>
						
						<div class="wcd-activation-steps">
							<div class="wcd-step">
								<span class="wcd-step-number">1</span>
								<span class="wcd-step-text"><?php esc_html_e( 'Check your email inbox (and spam folder)', 'webchangedetector' ); ?></span>
							</div>
							<div class="wcd-step">
								<span class="wcd-step-number">2</span>
								<span class="wcd-step-text"><?php esc_html_e( 'Click the activation link in the email', 'webchangedetector' ); ?></span>
							</div>
							<div class="wcd-step">
								<span class="wcd-step-number">3</span>
								<span class="wcd-step-text"><?php esc_html_e( 'Return here and refresh to access your dashboard', 'webchangedetector' ); ?></span>
							</div>
						</div>
						
						<div class="wcd-activation-note">
							<span class="dashicons dashicons-info"></span>
							<p><?php esc_html_e( 'The activation email usually arrives within a few minutes. If you don\'t see it, please check your spam folder.', 'webchangedetector' ); ?></p>
						</div>
						
						<div class="wcd-reset-section">
							<form method="post" style="margin: 0;">
								<input type="hidden" name="wcd_action" value="reset_api_token">
								<?php wp_nonce_field( 'reset_api_token' ); ?>
								<button type="submit" class="wcd-reset-button" onclick="return confirmReset()">
									<span class="dashicons dashicons-trash"></span>
									<?php esc_html_e( 'Reset & Start Over', 'webchangedetector' ); ?>
								</button>
							</form>
						</div>
					</div>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Check if development mode is enabled.
	 *
	 * Determines if the plugin is running in development mode based on
	 * configuration constants or URL patterns.
	 *
	 * @since    1.0.0
	 * @return   bool    True if in development mode, false otherwise.
	 */
	public function is_dev_mode() {
		// If either .test or dev. can be found in the URL, we're developing - wouldn't work if plugin client domain matches these criteria.
		if ( defined( 'WCD_DEV' ) && WCD_DEV === true ) {
			return true;
		}
		return false;
	}

	/**
	 * Check if this is the user's first time visiting the dashboard.
	 *
	 * Determines whether to show the setup wizard based on user visit history
	 * and account activity level.
	 *
	 * @since    1.0.0
	 * @return   bool    True if first visit, false otherwise.
	 */
	public function is_first_time_dashboard_visit() {
		$user_id = get_current_user_id();
		$option_key = 'wcd_first_time_visit_' . $user_id;
		
		// Check if the user has visited before.
		$has_visited = get_option( $option_key, false );
		
		if ( ! $has_visited ) {
			// Additional check: Only show wizard if user doesn't have significant activity yet.
			// This prevents wizard from showing for users who might have reset their settings.
			$client_account = $this->get_account();
			$has_activity = ! empty( $client_account['checks_done'] ) && $client_account['checks_done'] > 0;
			
			if ( ! $has_activity ) {
				// Mark as visited for future requests.
				update_option( $option_key, true );
				return true;
			}
		}
		
		return false;
	}

	/**
	 * Call to V1 API.
	 *
	 * Makes authenticated requests to the WebChange Detector V1 API
	 * with proper error handling and response processing.
	 *
	 * @since    1.0.0
	 * @param    array    $post     Request data.
	 * @param    bool     $is_web   Is web request.
	 * @return   string|array       API response or error message.
	 */
	public function api_v1( $post, $is_web = false ) {
		$url     = 'https://api.webchangedetector.com/api/v1/'; // init for production.
		$url_web = 'https://api.webchangedetector.com/';

		// This is where it can be changed to a local/dev address.
		if ( defined( 'WCD_API_URL' ) && is_string( WCD_API_URL ) && ! empty( WCD_API_URL ) ) {
			$url = WCD_API_URL;
		}

		// Overwrite $url if it is a get request.
		if ( $is_web && defined( 'WCD_API_URL_WEB' ) && is_string( WCD_API_URL_WEB ) && ! empty( WCD_API_URL_WEB ) ) {
			$url_web = WCD_API_URL_WEB;
		}

		$url     .= str_replace( '_', '-', $post['action'] ); // add kebab action to url.
		$url_web .= str_replace( '_', '-', $post['action'] ); // add kebab action to url.
		$action   = $post['action']; // For debugging.

		// Get API Token from WP DB.
		$api_token = $post['api_token'] ?? get_option( WCD_WP_OPTION_KEY_API_TOKEN ) ?? null;

		unset( $post['action'] ); // don't need to send as action as it's now the url.
		unset( $post['api_token'] ); // just in case.

		$post['wp_plugin_version'] = WEBCHANGEDETECTOR_VERSION; // API will check this to check compatability.
		// there's checks in place on the API side, you can't just send a different domain here, you sneaky little hacker ;).
		$post['domain'] = \WebChangeDetector\WebChangeDetector_Admin_Utils::get_domain_from_site_url();
		$post['wp_id']  = get_current_user_id();

		$args = array(
			'timeout' => WCD_REQUEST_TIMEOUT,
			'body'    => $post,
			'headers' => array(
				'Accept'        => 'application/json',
				'Authorization' => 'Bearer ' . $api_token,
				'x-wcd-domain'  => \WebChangeDetector\WebChangeDetector_Admin_Utils::get_domain_from_site_url(),
				'x-wcd-wp-id'   => get_current_user_id(),
				'x-wcd-plugin'  => 'webchangedetector-official/' . WEBCHANGEDETECTOR_VERSION,
			),
		);

		\WebChangeDetector\WebChangeDetector_Admin_Utils::log_error( 'API V1 request: ' . $url . ' | Args: ' . wp_json_encode( $args ) );
		if ( $is_web ) {
			$response = wp_remote_post( $url_web, $args );
		} else {
			$response = wp_remote_post( $url, $args );
		}

		$body          = wp_remote_retrieve_body( $response );
		$response_code = (int) wp_remote_retrieve_response_code( $response );

		$decoded_body = json_decode( $body, (bool) JSON_OBJECT_AS_ARRAY );

		// `message` is part of the Laravel Stacktrace.
		if ( 400 === $response_code &&
			is_array( $decoded_body ) &&
			array_key_exists( 'message', $decoded_body ) &&
			'plugin_update_required' === $decoded_body['message'] ) {
			return 'update plugin';
		}

		if ( 500 === $response_code && 'account_details' === $action ) {
			return 'activate account';
		}

		if ( 401 === $response_code ) {
			return 'unauthorized';
		}

		// if parsing JSON into $decoded_body was without error.
		if ( JSON_ERROR_NONE === json_last_error() ) {
			return $decoded_body;
		}

		return $body;
	}


	/**
	 * Generate the create account form HTML.
	 *
	 * Private helper method to render the account creation form
	 * with proper validation and security measures.
	 *
	 * @since    1.0.0
	 * @return   void    Outputs the create account form HTML.
	 */
	private function get_create_account_form() {
		$nonce = \WebChangeDetector\WebChangeDetector_Admin_Utils::create_nonce( 'wcd_create_account_nonce' );
		?>
		<div class="wcd-create-account-form">
			<h3><?php esc_html_e( 'Create Your Free Account', 'webchangedetector' ); ?></h3>
			<form method="post" action="">
				<?php wp_nonce_field( 'wcd_create_account_nonce', 'wcd_create_account_nonce' ); ?>
				<table class="form-table">
					<tr>
						<th scope="row">
							<label for="email"><?php esc_html_e( 'Email Address', 'webchangedetector' ); ?></label>
						</th>
						<td>
							<input name="email" type="email" id="email" class="regular-text" required />
						</td>
					</tr>
					<tr>
						<th scope="row">
							<label for="password"><?php esc_html_e( 'Password', 'webchangedetector' ); ?></label>
						</th>
						<td>
							<input name="password" type="password" id="password" class="regular-text" required />
						</td>
					</tr>
					<tr>
						<th scope="row">
							<label for="first_name"><?php esc_html_e( 'First Name', 'webchangedetector' ); ?></label>
						</th>
						<td>
							<input name="first_name" type="text" id="first_name" class="regular-text" />
						</td>
					</tr>
					<tr>
						<th scope="row">
							<label for="last_name"><?php esc_html_e( 'Last Name', 'webchangedetector' ); ?></label>
						</th>
						<td>
							<input name="last_name" type="text" id="last_name" class="regular-text" />
						</td>
					</tr>
				</table>
				<p class="wcd-terms">
					<label>
						<input type="checkbox" name="accept_terms" required />
						<?php 
						printf( 
							esc_html__( 'I agree to the %1$sTerms of Service%2$s and %3$sPrivacy Policy%4$s', 'webchangedetector' ),
							'<a href="' . esc_url( $this->get_app_url() . 'terms' ) . '" target="_blank">',
							'</a>',
							'<a href="' . esc_url( $this->get_app_url() . 'privacy' ) . '" target="_blank">',
							'</a>'
						);
						?>
					</label>
				</p>
				<?php submit_button( __( 'Create Free Account', 'webchangedetector' ) ); ?>
			</form>
		</div>
		<?php
	}

	/**
	 * Get account details v2.
	 *
	 * Fetches account details from the API with caching support.
	 * Migrated from legacy Wp_Compare class.
	 *
	 * @since    1.0.0
	 * @param    string|null $api_token Optional API token to use for the request.
	 * @return   array|string|false     Account details array, error message, or false on failure.
	 */
	public function get_account_details_v2( $api_token = null ) {
		// Use cached account details if available and no specific API token is provided.
		if ( ! empty( $this->account_details ) && empty( $api_token ) ) {
			return $this->account_details;
		}

		$account_details = \WebChangeDetector\WebChangeDetector_API_V2::get_account_v2( $api_token );

		if ( ! empty( $account_details['data'] ) ) {
			$account_details                 = $account_details['data'];
			$account_details['checks_limit'] = $account_details['checks_done'] + $account_details['checks_left'];
			
			// Cache the account details if no specific token was used.
			if ( empty( $api_token ) ) {
				$this->account_details = $account_details;
			}
			
			return $account_details;
		}
		
		if ( ! empty( $account_details['message'] ) ) {
			return $account_details['message'];
		}

		return false;
	}

	// Note: Overlay rendering methods removed - initial setup now handled in dashboard controller
} 