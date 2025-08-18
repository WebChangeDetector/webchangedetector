<?php
/**
 * Settings Action Handler for WebChangeDetector
 *
 * Handles all settings-related actions and business logic.
 *
 * @package    WebChangeDetector
 * @subpackage WebChangeDetector/admin/actions
 * @author     Mike Miler <mike@wp-mike.com>
 */

namespace WebChangeDetector;

/**
 * Settings Action Handler Class.
 */
class WebChangeDetector_Settings_Action_Handler {

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
	 * Handle save group settings action.
	 *
	 * @param array $data The settings data.
	 * @return array Result with success status and message.
	 */
	public function handle_save_group_settings( $data ) {
		try {
			$this->admin->error_handler->debug( 'Monitoring settings data: ' . wp_json_encode( $data ) );
			if ( ! empty( $data['monitoring'] ) && 1 === (int) $data['monitoring'] ) {
				return $this->handle_monitoring_settings( $data );
			} else {
				return $this->handle_manual_check_settings( $data );
			}
		} catch ( \Exception $e ) {
			return array(
				'success' => false,
				'message' => 'Error saving group settings: ' . $e->getMessage(),
			);
		}
	}

	/**
	 * Handle monitoring settings update.
	 *
	 * @param array $data The monitoring settings data.
	 * @return array Result with success status and message.
	 */
	private function handle_monitoring_settings( $data ) {
		// Validate monitoring settings.
		$validation = $this->validate_monitoring_settings( $data );
		if ( ! $validation['success'] ) {
			$this->admin->error_handler->debug( 'Monitoring settings validation failed: ' . wp_json_encode( $validation ) );
			return $validation;
		}

		// Update monitoring settings via API.
		$result = $this->admin->settings_handler->update_monitoring_settings( $data );

		// Debug: Log the result from update_monitoring_settings.
		$this->admin->error_handler->debug( 'Monitoring settings update result: ' . wp_json_encode( $result ) );

		// The settings handler now returns a standardized response format.
		return $result;
	}

	/**
	 * Handle manual check group settings update.
	 *
	 * @param array $data The manual check settings data.
	 * @return array Result with success status and message.
	 */
	private function handle_manual_check_settings( $data ) {
		// Update manual check group settings.
		$result = $this->admin->settings_handler->update_manual_check_group_settings( $data );

		if ( $result ) {
			return array(
				'success' => true,
				'message' => 'Auto update checks & manual check settings saved successfully.',
			);
		} else {
			return array(
				'success' => false,
				'message' => 'Failed to save manual check settings.',
			);
		}
	}

	/**
	 * Handle admin bar setting save.
	 *
	 * @param array $data The admin bar setting data.
	 * @return array Result with success status and message.
	 */
	public function handle_save_admin_bar_setting( $data ) {
		try {
			$disable_admin_bar = isset( $data['wcd_disable_admin_bar_menu'] ) ? 1 : 0;
			update_option( 'wcd_disable_admin_bar_menu', $disable_admin_bar );

			return array(
				'success' => true,
				'message' => 'Admin bar setting saved successfully.',
			);
		} catch ( \Exception $e ) {
			return array(
				'success' => false,
				'message' => 'Error saving admin bar setting: ' . $e->getMessage(),
			);
		}
	}

	/**
	 * Handle debug logging setting save.
	 *
	 * @param array $data The debug logging setting data.
	 * @return array Result with success status and message.
	 */
	public function handle_save_debug_logging_setting( $data ) {
		try {
			$enable_debug_logging = isset( $data['wcd_debug_logging'] ) ? 1 : 0;
			update_option( WCD_WP_OPTION_KEY_DEBUG_LOGGING, $enable_debug_logging );

			// Update the error handler instance if it exists.
			if ( isset( $this->admin->error_handler ) && method_exists( $this->admin->error_handler, 'set_debug_enabled' ) ) {
				$this->admin->error_handler->set_debug_enabled( $enable_debug_logging );
			}

			return array(
				'success' => true,
				'message' => 'Debug logging setting saved successfully.',
			);
		} catch ( \Exception $e ) {
			return array(
				'success' => false,
				'message' => 'Error saving debug logging setting: ' . $e->getMessage(),
			);
		}
	}

	/**
	 * Handle download log file action.
	 *
	 * @param array $data The download data containing filename.
	 * @return array Result with success status and message.
	 */
	public function handle_download_log_file( $data ) {
		// Log file downloads are no longer supported with the database logging.
		// Users should use the export functionality instead.
		return array(
			'success' => false,
			'message' => 'Log file downloads are no longer available. Please use the Export to CSV functionality in the Debug Logs tab.',
		);
	}

	/**
	 * Handle clear logs action.
	 *
	 * @param array $data The action data.
	 * @return array Result with success status and message.
	 */
	public function handle_clear_logs( $data ) {
		// Verify user capabilities.
		if ( ! current_user_can( 'manage_options' ) ) {
			return array(
				'success' => false,
				'message' => 'Insufficient permissions to clear logs.',
			);
		}

		try {
			// Initialize database logger.
			$logger = new \WebChangeDetector\WebChangeDetector_Database_Logger();

			// Clear all logs.
			$result = $logger->clear_all_logs();

			if ( $result ) {
				// Log the action.
				$logger->log( 'All logs cleared by user', 'admin_action', 'info' );

				return array(
					'success' => true,
					'message' => 'All logs have been cleared successfully.',
				);
			} else {
				return array(
					'success' => false,
					'message' => 'Failed to clear logs. Please try again.',
				);
			}
		} catch ( \Exception $e ) {
			return array(
				'success' => false,
				'message' => 'Error clearing logs: ' . $e->getMessage(),
			);
		}
	}

	/**
	 * Handle URL sync action.
	 *
	 * @param array $data The sync data.
	 * @return array Result with success status and message.
	 */
	public function handle_sync_urls( $data ) {
		try {
			$force = ! empty( $data['force'] );

			// Perform URL sync.
			$result = $this->admin->wordpress_handler->sync_posts( $force );

			if ( $result ) {
				return array(
					'success' => true,
					'message' => 'URLs synchronized successfully.',
				);
			} else {
				return array(
					'success' => false,
					'message' => 'Failed to synchronize URLs.',
				);
			}
		} catch ( \Exception $e ) {
			return array(
				'success' => false,
				'message' => 'Error synchronizing URLs: ' . $e->getMessage(),
			);
		}
	}

	/**
	 * Validate monitoring settings.
	 *
	 * @param array $data The settings data to validate.
	 * @return array Validation result with success status and errors.
	 */
	private function validate_monitoring_settings( $data ) {
		$errors = array();

		// Validate interval.
		if ( ! empty( $data['interval_in_h'] ) ) {
			$interval        = floatval( $data['interval_in_h'] );
			$valid_intervals = array( 0.25, 0.5, 1.0, 2.0, 4.0, 6.0, 8.0, 12.0, 24.0 );

			if ( ! in_array( $interval, $valid_intervals, true ) ) {
				$errors[] = 'Invalid monitoring interval selected.';
			}
		}

		// Validate hour of day.
		if ( ! empty( $data['hour_of_day'] ) ) {
			$hour = intval( $data['hour_of_day'] );
			if ( $hour < 0 || $hour > 23 ) {
				$errors[] = 'Hour of day must be between 0 and 23.';
			}
		}

		return array(
			'success' => empty( $errors ),
			'errors'  => $errors,
		);
	}
}
