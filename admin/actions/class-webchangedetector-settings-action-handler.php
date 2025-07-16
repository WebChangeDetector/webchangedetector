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
            $this->admin->logger->debug( 'Monitoring settings data: ' . print_r( $data, true ) );
			if ( ! empty( $data['monitoring'] ) && (int)$data['monitoring'] === 1 ) {
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
            $this->admin->logger->debug( 'Monitoring settings validation failed: ' . print_r( $validation, true ) );
			return $validation;
		}

		// Update monitoring settings via API.
		$result = $this->admin->settings_handler->update_monitoring_settings( $data );
		
		// Debug: Log the result from update_monitoring_settings
		$this->admin->logger->debug( 'Monitoring settings update result: ' . print_r( $result, true ) );
		
		// The settings handler now returns a standardized response format
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
			
			// Update the logger instance if it exists
			if ( isset( $this->admin->logger ) && method_exists( $this->admin->logger, 'set_debug_enabled' ) ) {
				$this->admin->logger->set_debug_enabled( $enable_debug_logging );
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
	 * Handle auto update settings save.
	 *
	 * @param array $data The auto update settings data.
	 * @return array Result with success status and message.
	 */
	public function handle_save_auto_update_settings( $data ) {
		try {
			// Validate auto update settings.
			$validation = $this->validate_auto_update_settings( $data );
			if ( ! $validation['success'] ) {
				return $validation;
			}

			// Build settings array.
			$settings = array(
				'auto_update_checks_enabled'   => !empty( $data['auto_update_checks_enabled'] ) && $data['auto_update_checks_enabled'] === '1',
				'auto_update_checks_from'      => sanitize_text_field( $data['auto_update_checks_from'] ?? '' ),
				'auto_update_checks_to'        => sanitize_text_field( $data['auto_update_checks_to'] ?? '' ),
				'auto_update_checks_emails'    => sanitize_textarea_field( $data['auto_update_checks_emails'] ?? '' ),
			);

			// Add weekday settings.
			foreach ( WebChangeDetector_Admin::WEEKDAYS as $day ) {
				$settings[ 'auto_update_checks_' . $day ] = isset( $data[ 'auto_update_checks_' . $day ] ) && $data[ 'auto_update_checks_' . $day ] === '1';
			}

			// Save via API.
			$result = \WebChangeDetector\WebChangeDetector_API_V2::update_website_v2( $this->admin->website_details['id'], array( 'auto_update_settings' => $settings ) );
			
			if ( $result['success'] ?? false ) {
				return array(
					'success' => true,
					'message' => 'Auto update settings saved successfully.',
				);
			} else {
				return array(
					'success' => false,
					'message' => $result['message'] ?? 'Failed to save auto update settings.',
				);
			}
		} catch ( \Exception $e ) {
			return array(
				'success' => false,
				'message' => 'Error saving auto update settings: ' . $e->getMessage(),
			);
		}
	}

	/**
	 * Handle URL selection save.
	 *
	 * @param array $data The URL selection data.
	 * @return array Result with success status and message.
	 */
	public function handle_save_url_selection( $data ) {
		try {
			$active_posts = array();
			$count_selected = 0;
			$already_processed_ids = array();

			// Process URL selection data.
			foreach ( $data as $key => $post ) {
				if ( 0 === strpos( $key, 'desktop-' ) || 0 === strpos( $key, 'mobile-' ) ) {
					$post_id = 0 === strpos( $key, 'desktop-' ) ? substr( $key, strlen( 'desktop-' ) ) : substr( $key, strlen( 'mobile-' ) );

					// Avoid processing same post_id twice.
					if ( in_array( $post_id, $already_processed_ids, true ) ) {
						continue;
					}
					$already_processed_ids[] = $post_id;

					$desktop = array_key_exists( 'desktop-' . $post_id, $data ) ? ( $data[ 'desktop-' . $post_id ] ) : null;
					$mobile  = array_key_exists( 'mobile-' . $post_id, $data ) ? ( $data[ 'mobile-' . $post_id ] ) : null;

					$new_post = array( 'id' => $post_id );
					if ( ! is_null( $desktop ) ) {
						$new_post['desktop'] = $desktop;
					}
					if ( ! is_null( $mobile ) ) {
						$new_post['mobile'] = $mobile;
					}
					$active_posts[] = $new_post;

					if ( isset( $data[ 'desktop-' . $post_id ] ) && 1 === $data[ 'desktop-' . $post_id ] ) {
						++$count_selected;
					}

					if ( isset( $data[ 'mobile-' . $post_id ] ) && 1 === $data[ 'mobile-' . $post_id ] ) {
						++$count_selected;
					}
				}
			}

			$group_id = sanitize_text_field( $data['group_id'] ?? '' );
			
			if ( empty( $group_id ) ) {
				return array(
					'success' => false,
					'message' => 'Group ID is required.',
				);
			}

			// Update URLs in group via API.
			$result = \WebChangeDetector\WebChangeDetector_API_V2::update_urls_in_group_v2( $group_id, $active_posts );
			
			if ( $result ) {
				return array(
					'success' => true,
					'message' => sprintf( 'URL selection saved. %d URLs selected for monitoring.', $count_selected ),
					'selected_count' => $count_selected,
				);
			} else {
				return array(
					'success' => false,
					'message' => 'Failed to save URL selection.',
				);
			}
		} catch ( \Exception $e ) {
			return array(
				'success' => false,
				'message' => 'Error saving URL selection: ' . $e->getMessage(),
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
			$interval = floatval( $data['interval_in_h'] );
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
			'errors' => $errors,
		);
	}

	/**
	 * Validate auto update settings.
	 *
	 * @param array $data The settings data to validate.
	 * @return array Validation result with success status and errors.
	 */
	private function validate_auto_update_settings( $data ) {
		$errors = array();

		// Validate time format.
		if ( ! empty( $data['auto_update_checks_from'] ) ) {
			if ( ! preg_match( '/^([01]?[0-9]|2[0-3]):[0-5][0-9]$/', $data['auto_update_checks_from'] ) ) {
				$errors[] = 'Invalid "from" time format. Use HH:MM format.';
			}
		}

		if ( ! empty( $data['auto_update_checks_to'] ) ) {
			if ( ! preg_match( '/^([01]?[0-9]|2[0-3]):[0-5][0-9]$/', $data['auto_update_checks_to'] ) ) {
				$errors[] = 'Invalid "to" time format. Use HH:MM format.';
			}
		}

		// Validate email addresses.
		if ( ! empty( $data['auto_update_checks_emails'] ) ) {
			$emails = explode( ',', $data['auto_update_checks_emails'] );
			foreach ( $emails as $email ) {
				$email = trim( $email );
				if ( ! empty( $email ) && ! is_email( $email ) ) {
					$errors[] = 'Invalid email address: ' . $email;
				}
			}
		}

		return array(
			'success' => empty( $errors ),
			'errors' => $errors,
		);
	}

	/**
	 * Get current settings for display.
	 *
	 * @param string $settings_type The type of settings to retrieve.
	 * @return array The current settings.
	 */
	public function get_current_settings( $settings_type ) {
		switch ( $settings_type ) {
			case 'monitoring':
				return $this->get_monitoring_settings();
			
			case 'auto_update':
				return $this->get_auto_update_settings();
			
			case 'admin_bar':
				return array(
					'wcd_disable_admin_bar_menu' => get_option( 'wcd_disable_admin_bar_menu', 0 ),
				);
			
			default:
				return array();
		}
	}

	/**
	 * Get monitoring settings.
	 *
	 * @return array The monitoring settings.
	 */
	private function get_monitoring_settings() {
		$group_and_urls = $this->admin->get_group_and_urls( $this->admin->monitoring_group_uuid );
		
		return array(
			'interval_in_h' => $group_and_urls['interval_in_h'] ?? 24,
			'hour_of_day' => $group_and_urls['hour_of_day'] ?? 0,
			'enabled' => $group_and_urls['enabled'] ?? false,
			'selected_urls_count' => $group_and_urls['selected_urls_count'] ?? 0,
		);
	}

	/**
	 * Get auto update settings.
	 *
	 * @return array The auto update settings.
	 */
	private function get_auto_update_settings() {
		$website_details = $this->admin->website_details ?? array();
		
		return $website_details['auto_update_settings'] ?? array(
			'auto_update_checks_enabled' => false,
			'auto_update_checks_from' => '09:00',
			'auto_update_checks_to' => '17:00',
			'auto_update_checks_emails' => get_option( 'admin_email' ),
			'auto_update_checks_monday' => true,
			'auto_update_checks_tuesday' => true,
			'auto_update_checks_wednesday' => true,
			'auto_update_checks_thursday' => true,
			'auto_update_checks_friday' => true,
			'auto_update_checks_saturday' => false,
			'auto_update_checks_sunday' => false,
		);
	}
} 