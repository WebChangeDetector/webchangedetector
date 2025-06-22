<?php
/**
 * WebChangeDetector User Feedback
 *
 * Enhanced error notifications and user communication system.
 *
 * @package    WebChangeDetector
 * @subpackage WebChangeDetector/admin/error-handling
 */

namespace WebChangeDetector;

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WebChangeDetector User Feedback Class
 *
 * Provides enhanced user feedback for errors and operations.
 */
class WebChangeDetector_User_Feedback {

	/**
	 * Notification types.
	 */
	const TYPE_SUCCESS = 'success';
	const TYPE_INFO = 'info';
	const TYPE_WARNING = 'warning';
	const TYPE_ERROR = 'error';

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'admin_notices', array( $this, 'display_admin_notices' ) );
		add_action( 'wp_ajax_webchangedetector_dismiss_notice', array( $this, 'dismiss_notice' ) );
	}

	/**
	 * Add user feedback message.
	 *
	 * @param string $message     Message text.
	 * @param string $type        Message type.
	 * @param array  $options     Additional options.
	 */
	public function add_feedback( $message, $type = self::TYPE_INFO, $options = array() ) {
		$options = wp_parse_args( $options, array(
			'dismissible' => true,
			'persistent'  => false,
			'actions'     => array(),
			'context'     => '',
			'error_code'  => '',
		) );

		$feedback = array(
			'message'     => $message,
			'type'        => $type,
			'timestamp'   => time(),
			'dismissible' => $options['dismissible'],
			'persistent'  => $options['persistent'],
			'actions'     => $options['actions'],
			'context'     => $options['context'],
			'error_code'  => $options['error_code'],
			'id'          => uniqid( 'wcd_feedback_' ),
		);

		// Store in transients for non-persistent or options for persistent.
		if ( $options['persistent'] ) {
			$persistent_messages = get_option( 'webchangedetector_persistent_feedback', array() );
			$persistent_messages[ $feedback['id'] ] = $feedback;
			update_option( 'webchangedetector_persistent_feedback', $persistent_messages );
		} else {
			$transient_messages = get_transient( 'webchangedetector_feedback_' . get_current_user_id() );
			if ( ! is_array( $transient_messages ) ) {
				$transient_messages = array();
			}
			$transient_messages[ $feedback['id'] ] = $feedback;
			set_transient( 'webchangedetector_feedback_' . get_current_user_id(), $transient_messages, 300 ); // 5 minutes.
		}
	}

	/**
	 * Add success feedback.
	 *
	 * @param string $message Message text.
	 * @param array  $options Additional options.
	 */
	public function add_success( $message, $options = array() ) {
		$this->add_feedback( $message, self::TYPE_SUCCESS, $options );
	}

	/**
	 * Add info feedback.
	 *
	 * @param string $message Message text.
	 * @param array  $options Additional options.
	 */
	public function add_info( $message, $options = array() ) {
		$this->add_feedback( $message, self::TYPE_INFO, $options );
	}

	/**
	 * Add warning feedback.
	 *
	 * @param string $message Message text.
	 * @param array  $options Additional options.
	 */
	public function add_warning( $message, $options = array() ) {
		$this->add_feedback( $message, self::TYPE_WARNING, $options );
	}

	/**
	 * Add error feedback.
	 *
	 * @param string $message Message text.
	 * @param array  $options Additional options.
	 */
	public function add_error( $message, $options = array() ) {
		$this->add_feedback( $message, self::TYPE_ERROR, $options );
	}

	/**
	 * Add feedback from exception.
	 *
	 * @param \Exception $exception Exception to create feedback from.
	 * @param array      $options   Additional options.
	 */
	public function add_exception_feedback( $exception, $options = array() ) {
		$message = $exception instanceof WebChangeDetector_Exception
			? $exception->get_user_message()
			: 'An unexpected error occurred. Please try again.';

		$options = wp_parse_args( $options, array(
			'error_code' => $exception->getCode(),
			'context'    => get_class( $exception ),
			'actions'    => $this->get_exception_actions( $exception ),
		) );

		$this->add_error( $message, $options );
	}

	/**
	 * Display admin notices.
	 */
	public function display_admin_notices() {
		// Only show on WebChangeDetector pages.
		if ( ! $this->is_webchangedetector_page() ) {
			return;
		}

		$messages = $this->get_all_feedback_messages();

		foreach ( $messages as $message ) {
			$this->render_notice( $message );
		}
	}

	/**
	 * Get all feedback messages.
	 *
	 * @return array All feedback messages.
	 */
	private function get_all_feedback_messages() {
		$messages = array();

		// Get transient messages.
		$transient_messages = get_transient( 'webchangedetector_feedback_' . get_current_user_id() );
		if ( is_array( $transient_messages ) ) {
			$messages = array_merge( $messages, $transient_messages );
		}

		// Get persistent messages.
		$persistent_messages = get_option( 'webchangedetector_persistent_feedback', array() );
		if ( is_array( $persistent_messages ) ) {
			// Filter out dismissed messages.
			$dismissed = get_user_meta( get_current_user_id(), 'webchangedetector_dismissed_notices', true );
			if ( ! is_array( $dismissed ) ) {
				$dismissed = array();
			}

			foreach ( $persistent_messages as $id => $message ) {
				if ( ! in_array( $id, $dismissed, true ) ) {
					$messages[ $id ] = $message;
				}
			}
		}

		return $messages;
	}

	/**
	 * Render a single notice.
	 *
	 * @param array $message Message data.
	 */
	private function render_notice( $message ) {
		$css_class = 'notice notice-' . $message['type'];
		if ( $message['dismissible'] ) {
			$css_class .= ' is-dismissible';
		}

		echo '<div class="' . esc_attr( $css_class ) . '" data-notice-id="' . esc_attr( $message['id'] ) . '">';
		echo '<p>' . wp_kses_post( $message['message'] ) . '</p>';

		// Render actions if present.
		if ( ! empty( $message['actions'] ) ) {
			echo '<p>';
			foreach ( $message['actions'] as $action ) {
				printf(
					'<a href="%s" class="button button-%s"%s>%s</a> ',
					esc_url( $action['url'] ),
					esc_attr( $action['type'] ?? 'secondary' ),
					isset( $action['target'] ) ? ' target="' . esc_attr( $action['target'] ) . '"' : '',
					esc_html( $action['label'] )
				);
			}
			echo '</p>';
		}

		// Show error details for debugging (admin only).
		if ( current_user_can( 'manage_options' ) && ! empty( $message['error_code'] ) ) {
			echo '<details style="margin-top: 10px;">';
			echo '<summary>Error Details (for debugging)</summary>';
			echo '<p><strong>Error Code:</strong> ' . esc_html( $message['error_code'] ) . '</p>';
			if ( ! empty( $message['context'] ) ) {
				echo '<p><strong>Context:</strong> ' . esc_html( $message['context'] ) . '</p>';
			}
			echo '<p><strong>Timestamp:</strong> ' . esc_html( gmdate( 'Y-m-d H:i:s', $message['timestamp'] ) ) . ' UTC</p>';
			echo '</details>';
		}

		echo '</div>';
	}

	/**
	 * Dismiss a notice via AJAX.
	 */
	public function dismiss_notice() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( 'Insufficient permissions' );
		}

		if ( ! check_ajax_referer( 'webchangedetector_dismiss_notice', 'nonce', false ) ) {
			wp_die( 'Invalid nonce' );
		}

		$notice_id = sanitize_text_field( $_POST['notice_id'] ?? '' );
		if ( empty( $notice_id ) ) {
			wp_die( 'Invalid notice ID' );
		}

		// Add to dismissed notices for this user.
		$dismissed = get_user_meta( get_current_user_id(), 'webchangedetector_dismissed_notices', true );
		if ( ! is_array( $dismissed ) ) {
			$dismissed = array();
		}

		if ( ! in_array( $notice_id, $dismissed, true ) ) {
			$dismissed[] = $notice_id;
			update_user_meta( get_current_user_id(), 'webchangedetector_dismissed_notices', $dismissed );
		}

		wp_send_json_success();
	}

	/**
	 * Get suggested actions for exceptions.
	 *
	 * @param \Exception $exception Exception instance.
	 * @return array Suggested actions.
	 */
	private function get_exception_actions( $exception ) {
		$actions = array();

		if ( $exception instanceof WebChangeDetector_API_Exception ) {
			$actions[] = array(
				'label' => 'Check API Status',
				'url'   => admin_url( 'admin.php?page=webchangedetector-logs' ),
				'type'  => 'primary',
			);

			if ( $exception->get_http_code() === 401 ) {
				$actions[] = array(
					'label' => 'Re-authenticate',
					'url'   => admin_url( 'admin.php?page=webchangedetector-settings' ),
					'type'  => 'secondary',
				);
			}
		}

		if ( $exception instanceof WebChangeDetector_Database_Exception ) {
			$actions[] = array(
				'label' => 'Database Health Check',
				'url'   => admin_url( 'admin.php?page=webchangedetector-logs&tab=health' ),
				'type'  => 'primary',
			);
		}

		if ( $exception instanceof WebChangeDetector_Permission_Exception ) {
			$actions[] = array(
				'label' => 'Check User Permissions',
				'url'   => admin_url( 'admin.php?page=webchangedetector-settings' ),
				'type'  => 'primary',
			);
		}

		// Always add support action.
		$actions[] = array(
			'label'  => 'Get Support',
			'url'    => 'https://webchangedetector.com/support',
			'type'   => 'secondary',
			'target' => '_blank',
		);

		return $actions;
	}

	/**
	 * Check if current page is a WebChangeDetector admin page.
	 *
	 * @return bool True if on WebChangeDetector page.
	 */
	private function is_webchangedetector_page() {
		$current_screen = get_current_screen();
		
		if ( ! $current_screen ) {
			return false;
		}

		$wcd_pages = array(
			'toplevel_page_webchangedetector',
			'webchangedetector_page_webchangedetector-update-settings',
			'webchangedetector_page_webchangedetector-auto-settings',
			'webchangedetector_page_webchangedetector-change-detections',
			'webchangedetector_page_webchangedetector-logs',
			'webchangedetector_page_webchangedetector-settings',
		);

		return in_array( $current_screen->id, $wcd_pages, true );
	}

	/**
	 * Clear all feedback messages.
	 *
	 * @param bool $persistent Whether to clear persistent messages too.
	 */
	public function clear_feedback( $persistent = false ) {
		// Clear transient messages.
		delete_transient( 'webchangedetector_feedback_' . get_current_user_id() );

		if ( $persistent ) {
			// Clear persistent messages.
			delete_option( 'webchangedetector_persistent_feedback' );
			
			// Clear dismissed notices.
			delete_user_meta( get_current_user_id(), 'webchangedetector_dismissed_notices' );
		}
	}

	/**
	 * Get feedback statistics.
	 *
	 * @param string $timeframe Timeframe for statistics.
	 * @return array Feedback statistics.
	 */
	public function get_feedback_statistics( $timeframe = 'day' ) {
		// This would integrate with the logger to get feedback/error statistics.
		$logger = new WebChangeDetector_Logger();
		return $logger->get_statistics( $timeframe );
	}

	/**
	 * Add JavaScript for enhanced notice functionality.
	 */
	public function enqueue_feedback_scripts() {
		if ( ! $this->is_webchangedetector_page() ) {
			return;
		}

		wp_enqueue_script(
			'webchangedetector-feedback',
			plugin_dir_url( __FILE__ ) . '../js/feedback.js',
			array( 'jquery' ),
			WEBCHANGEDETECTOR_VERSION,
			true
		);

		wp_localize_script( 'webchangedetector-feedback', 'wcdFeedback', array(
			'ajax_url' => admin_url( 'admin-ajax.php' ),
			'nonce'    => wp_create_nonce( 'webchangedetector_dismiss_notice' ),
		) );
	}
} 