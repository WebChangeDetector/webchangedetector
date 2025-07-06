<?php
/**
 * Dashboard Controller for WebChangeDetector
 *
 * Handles dashboard view requests and logic.
 *
 * @package    WebChangeDetector
 * @subpackage WebChangeDetector/admin/controllers
 * @author     Mike Miler <mike@wp-mike.com>
 */

namespace WebChangeDetector;

/**
 * Dashboard Controller Class.
 */
class WebChangeDetector_Dashboard_Controller {

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
	 * Handle dashboard request.
	 */
	public function handle_request() {
		// Check permissions.
		if ( ! $this->admin->settings_handler->is_allowed( 'dashboard_view' ) ) {
			return;
		}

		// Check if initial setup is needed and show setup page instead of dashboard.
		if ( get_option( WCD_WP_OPTION_KEY_INITIAL_SETUP_NEEDED, false ) ) {
			$this->handle_initial_setup();
			return;
		}

		// Handle dashboard-specific actions.
		$this->handle_dashboard_actions();

		// Get account details for the dashboard.
		$account_details = $this->admin->account_handler->get_account();

		// Error message if api didn't return account details.
		if ( empty( $account_details['status'] ) ) {
			$this->admin->view_renderer->get_component( 'notifications' )->render_notice(
				'Ooops! Something went wrong. Please try again.<br>If the issue persists, please contact us.',
				'error'
			);
			return false;
		}

		// Check for account status.
		if ( 'active' !== $account_details['status'] ) {
			$this->render_inactive_account_message( $account_details['status'] );
			return;
		}

		// Render the dashboard.
		$this->render_dashboard_view( $account_details );
	}

	/**
	 * Handle dashboard-specific actions.
	 */
	private function handle_dashboard_actions() {
		if ( ! isset( $_POST['wcd_action'] ) ) {
			return;
		}

		$wcd_action = sanitize_text_field( wp_unslash( $_POST['wcd_action'] ) );
		$postdata   = array();

		// Unslash postdata.
		foreach ( $_POST as $key => $post ) {
			$key              = wp_unslash( $key );
			$post             = wp_unslash( $post );
			$postdata[ $key ] = $post;
		}

		switch ( $wcd_action ) {
			case 'enable_wizard':
				add_option( 'wcd_wizard', 'true', '', false );
				break;

			case 'disable_wizard':
				delete_option( 'wcd_wizard' );
				break;

			case 'change_comparison_status':
				$result = $this->admin->comparison_action_handler->handle_change_comparison_status( $postdata );
				if ( $result['success'] ) {
					echo '<div class="notice notice-success"><p><strong>WebChange Detector:</strong> ' . esc_html( $result['message'] ) . '</p></div>';
				} else {
					echo '<div class="notice notice-error"><p><strong>WebChange Detector:</strong> ' . esc_html( $result['message'] ) . '</p></div>';
				}
				break;

			case 'add_post_type':
				$this->admin->wordpress_handler->add_post_type( $postdata );
				$post_type_name = json_decode( stripslashes( $postdata['post_type'] ), true )[0]['post_type_name'];
				echo '<div class="notice notice-success"><p><strong>WebChange Detector: </strong>' . esc_html( $post_type_name ) . ' added.</p></div>';
				break;

			case 'update_detection_step':
				update_option( WCD_OPTION_UPDATE_STEP_KEY, sanitize_text_field( $postdata['step'] ) );
				break;

			case 'take_screenshots':
				$this->handle_take_screenshots_action( $postdata );
				break;

			case 'save_group_settings':
				$this->handle_save_group_settings_action( $postdata );
				break;

			case 'start_manual_checks':
				$this->handle_start_manual_checks_action( $postdata );
				break;

			case 'save_admin_bar_setting':
				$this->handle_save_admin_bar_setting();
				break;
		}
	}

	/**
	 * Handle take screenshots action.
	 *
	 * @param array $postdata The POST data.
	 */
	private function handle_take_screenshots_action( $postdata ) {
		$sc_type = sanitize_text_field( $postdata['sc_type'] );

		if ( ! in_array( $sc_type, WebChangeDetector_Admin::VALID_SC_TYPES, true ) ) {
			echo '<div class="error notice"><p>Wrong Screenshot type.</p></div>';
			return false;
		}

		$results = \WebChangeDetector\WebChangeDetector_API_V2::take_screenshot_v2( $this->admin->manual_group_uuid, $sc_type );
		if ( isset( $results['batch'] ) ) {
			update_option( 'wcd_manual_checks_batch', $results['batch'] );
			if ( 'pre' === $sc_type ) {
				update_option( WCD_OPTION_UPDATE_STEP_KEY, WCD_OPTION_UPDATE_STEP_PRE_STARTED );
			} elseif ( 'post' === $sc_type ) {
				update_option( WCD_OPTION_UPDATE_STEP_KEY, WCD_OPTION_UPDATE_STEP_POST_STARTED );
			}
		} else {
			echo '<div class="error notice"><p>' . esc_html( $results['message'] ) . '</p></div>';
		}
	}

	/**
	 * Handle save group settings action.
	 *
	 * @param array $postdata The POST data.
	 */
	private function handle_save_group_settings_action( $postdata ) {
		if ( ! empty( $postdata['monitoring'] ) ) {
			$this->admin->settings_handler->update_monitoring_settings( $postdata );
		} else {
			$this->admin->settings_handler->update_manual_check_group_settings( $postdata );
		}
	}

	/**
	 * Handle start manual checks action.
	 *
	 * @param array $postdata The POST data.
	 */
	private function handle_start_manual_checks_action( $postdata ) {
		// Update step in update detection.
		if ( ! empty( $postdata['step'] ) ) {
			update_option( WCD_OPTION_UPDATE_STEP_KEY, sanitize_text_field( $postdata['step'] ) );
		}
	}

	/**
	 * Handle save admin bar setting action.
	 */
	private function handle_save_admin_bar_setting() {
		$disable_admin_bar = isset( $_POST['wcd_disable_admin_bar_menu'] ) ? 1 : 0;
		update_option( 'wcd_disable_admin_bar_menu', $disable_admin_bar );
		// Add an admin notice for success.
		echo '<div class="notice notice-success"><p><strong>WebChange Detector: </strong>Admin bar setting saved.</p></div>';
	}

	/**
	 * Render inactive account message.
	 *
	 * @param string $status The account status.
	 */
	private function render_inactive_account_message( $status ) {
		$this->admin->view_renderer->get_component( 'notifications' )->render_inactive_account_notice( $status );
	}

	/**
	 * Render dashboard view.
	 *
	 * @param array $account_details The account details.
	 */
	private function render_dashboard_view( $account_details ) {
		// Delegate to the existing dashboard handler.
		$this->admin->dashboard_handler->get_dashboard_view( $account_details );
	}

	/**
	 * Handle initial setup when flag is set.
	 * Updates sync_url_types with local labels, shows overlay during sync, then starts wizard.
	 */
	private function handle_initial_setup() {
		// Check if account is activated.
		$account_details = $this->admin->account_handler->get_account();
		
		if ( empty( $account_details ) || ! is_array( $account_details ) ) {
			// Account not activated yet, redirect to account page.
			wp_safe_redirect( admin_url( 'admin.php?page=webchangedetector' ) );
			exit;
		}
		
		// Account is activated, proceed with setup.
		$this->render_initial_setup_overlay();
	}
	
	/**
	 * Render the initial setup overlay that shows "creating account" during sync.
	 */
	private function render_initial_setup_overlay() {
		?>
		
        <div id="wcd-initial-setup-overlay">
            <style>.webchangedetector .nav-tab-wrapper {display: none;}</style>
            <div class="wcd-overlay-content">
                <div class="wcd-setup-header">
                    <img src="<?php echo esc_url( plugin_dir_url( dirname( __FILE__ ) ) . 'img/logo-webchangedetector.png' ); ?>" alt="WebChangeDetector Logo" class="wcd-setup-logo">
                    <h2><?php esc_html_e( 'Setting up your account...', 'webchangedetector' ); ?></h2>
                    <p><?php esc_html_e( 'We are creating your account. This will only take a moment.', 'webchangedetector' ); ?></p>
                </div>
                
                <div class="wcd-setup-progress">
                    <div class="wcd-spinner"></div>
                    <p class="wcd-status-text"><?php esc_html_e( 'Syncing website content...', 'webchangedetector' ); ?></p>
                </div>
            </div>
        </div>
        
        <script type="text/javascript">
        jQuery(document).ready(function($) {
            console.log('WCD Initial Setup: Starting automatic setup');
            
            // Start the setup process immediately
            setupAccountAndSync();
            
            function setupAccountAndSync() {
                // Step 1: Update sync_url_types with local labels
                console.log('WCD Initial Setup: Updating sync_url_types with local labels');
                $('.wcd-status-text').text('<?php esc_html_e( 'Updating content types...', 'webchangedetector' ); ?>');
                
                $.ajax({
                    url: '<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>',
                    type: 'POST',
                    data: {
                        action: 'wcd_update_sync_types_with_local_labels',
                        nonce: '<?php echo esc_js( wp_create_nonce( 'wcd_ajax_nonce' ) ); ?>'
                    },
                    dataType: 'json',
                    success: function(response) {
                        console.log('WCD Initial Setup: Sync types updated', response);
                        
                        if (response.success) {
                            // Step 2: Start URL sync
                            startUrlSync();
                        } else {
                            console.error('WCD Initial Setup: Failed to update sync types', response);
                            showError('<?php esc_html_e( 'Failed to update content types. Please try again.', 'webchangedetector' ); ?>');
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('WCD Initial Setup: AJAX error updating sync types', error);
                        showError('<?php esc_html_e( 'Failed to update content types. Please try again.', 'webchangedetector' ); ?>');
                    }
                });
            }
            
            function startUrlSync() {
                console.log('WCD Initial Setup: Starting URL sync');
                $('.wcd-status-text').text('<?php esc_html_e( 'Syncing website content...', 'webchangedetector' ); ?>');
                
                $.ajax({
                    url: '<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>',
                    type: 'POST',
                    data: {
                        action: 'wcd_sync_posts',
                        nonce: '<?php echo esc_js( wp_create_nonce( 'wcd_ajax_nonce' ) ); ?>'
                    },
                    dataType: 'json',
                    success: function(response) {
                        console.log('WCD Initial Setup: Sync completed', response);
                        
                        // Step 3: Complete setup and start wizard
                        completeSetup();
                    },
                    error: function(xhr, status, error) {
                        console.error('WCD Initial Setup: Sync error', error);
                        
                        // Even if sync fails, we can still complete setup
                        console.log('WCD Initial Setup: Continuing despite sync error');
                        completeSetup();
                    }
                });
            }
            
            function completeSetup() {
                console.log('WCD Initial Setup: Completing setup');
                $('.wcd-status-text').text('<?php esc_html_e( 'Finalizing setup...', 'webchangedetector' ); ?>');
                
                // Clear the initial setup needed flag and enable wizard
                $.ajax({
                    url: '<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>',
                    type: 'POST',
                    data: {
                        action: 'wcd_complete_initial_setup',
                        nonce: '<?php echo esc_js( wp_create_nonce( 'wcd_ajax_nonce' ) ); ?>'
                    },
                    dataType: 'json',
                    success: function(response) {
                        console.log('WCD Initial Setup: Setup completed', response);
                        
                        // Redirect to dashboard with wizard enabled
                        $('.wcd-status-text').text('<?php esc_html_e( 'Setup complete! Starting wizard...', 'webchangedetector' ); ?>');
                        
                        setTimeout(function() {
                            window.location.href = '<?php echo esc_url( admin_url( 'admin.php?page=webchangedetector' ) ); ?>';
                        }, 1000);
                    },
                    error: function(xhr, status, error) {
                        console.error('WCD Initial Setup: Error completing setup', error);
                        showError('<?php esc_html_e( 'Setup encountered an error. Please refresh the page.', 'webchangedetector' ); ?>');
                    }
                });
            }
            
            function showError(message) {
                $('.wcd-spinner').hide();
                $('.wcd-status-text').text(message).css('color', '#d63638');
            }
        });
        </script>
        
        <style>
        .webchangedetector {
            position: relative; /* Ensure the container is relatively positioned for absolute overlay */
        }
        
        #wcd-initial-setup-overlay {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(255, 255, 255, 0.95);
            z-index: 999;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 500px; /* Ensure minimum height for proper centering */
        }
        
        .wcd-overlay-content {
            background: white;
            padding: 40px;
            border-radius: 8px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            text-align: center;
            max-width: 500px;
            width: 90%;
            margin: 20px; /* Add margin to prevent edge touching */
        }
        
        .wcd-setup-logo {
            max-width: 200px;
            margin-bottom: 20px;
        }
        
        .wcd-setup-header h2 {
            margin: 0 0 10px 0;
            color: #333;
        }
        
        .wcd-setup-header p {
            margin: 0 0 30px 0;
            color: #666;
            font-size: 16px;
        }
        
        .wcd-setup-progress {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 15px;
        }
        
        .wcd-spinner {
            width: 40px;
            height: 40px;
            border: 4px solid #f3f3f3;
            border-top: 4px solid #2271b1;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        .wcd-status-text {
            margin: 0;
            font-size: 16px;
            color: #333;
        }
        </style>
		
		<?php
		exit; // Important: Exit to prevent any additional output
	}
} 