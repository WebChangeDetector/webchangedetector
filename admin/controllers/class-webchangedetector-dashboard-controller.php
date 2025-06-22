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
				\WebChangeDetector\WebChangeDetector_API_V2::update_comparison_v2( $postdata['comparison_id'], $postdata['status'] );
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
		// For now, delegate to the existing dashboard handler.
		// In later phases, this will be moved to a view renderer.
		$this->admin->dashboard_handler->get_dashboard_view( $account_details );
	}
} 