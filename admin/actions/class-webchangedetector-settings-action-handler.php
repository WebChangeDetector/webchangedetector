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
			// In all-sites mode, save to all groups across all sites.
			if ( $this->admin->is_all_sites_mode ) {
				return $this->handle_bulk_save_group_settings( $data );
			}

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
	 * Handle bulk save of group settings across all multisite sub-sites.
	 *
	 * Saves the same settings to all monitoring or manual groups using
	 * parallel API requests via api_v2_bulk().
	 *
	 * @since 4.3.0
	 * @param array $data The settings data from the form.
	 * @return array Result with success status and message.
	 */
	private function handle_bulk_save_group_settings( $data ) {
		// Only super admins may bulk-update all sites.
		if ( ! current_user_can( 'manage_network_options' ) ) {
			return array(
				'success' => false,
				'message' => __( 'You do not have permission to modify settings for all websites.', 'webchangedetector' ),
			);
		}

		$is_monitoring = ! empty( $data['monitoring'] ) && 1 === (int) $data['monitoring'];
		$all_groups    = \WebChangeDetector\WebChangeDetector_Multisite::get_all_group_ids();
		$group_key     = $is_monitoring ? 'monitoring' : 'manual';
		$group_ids     = $all_groups[ $group_key ];

		if ( empty( $group_ids ) ) {
			return array(
				'success' => false,
				'message' => __( 'No registered groups found.', 'webchangedetector' ),
			);
		}

		// First, save to the current site's group normally (this handles auto-update website settings too).
		if ( $is_monitoring ) {
			$single_result = $this->handle_monitoring_settings( $data );
		} else {
			$single_result = $this->handle_manual_check_settings( $data );
		}

		// Build the group settings args (same logic as update_monitoring_settings / update_manual_check_group_settings).
		$args = array();

		if ( $is_monitoring ) {
			if ( isset( $data['hour_of_day'] ) ) {
				$args['hour_of_day'] = sanitize_key( $data['hour_of_day'] );
			}
			if ( isset( $data['interval_in_h'] ) ) {
				$args['interval_in_h'] = sanitize_text_field( $data['interval_in_h'] );
			}
			$args['enabled']    = isset( $data['enabled'] ) && ( 'on' === $data['enabled'] || '1' === $data['enabled'] );
			$args['monitoring'] = true;
			if ( isset( $data['alert_emails'] ) ) {
				$args['alert_emails'] = explode( ',', sanitize_textarea_field( $data['alert_emails'] ) );
			}
			if ( isset( $data['schedule_type'] ) ) {
				$args['schedule_type'] = sanitize_text_field( $data['schedule_type'] );
			}
			if ( isset( $data['schedule_days'] ) && is_array( $data['schedule_days'] ) ) {
				$args['schedule_days'] = array_map(
					function ( $day ) {
						$day = sanitize_text_field( $day );
						return 'last' === $day ? $day : intval( $day );
					},
					$data['schedule_days']
				);
			}
			if ( isset( $data['quiet_hours_start'] ) && '' !== $data['quiet_hours_start'] ) {
				$args['quiet_hours_start'] = intval( $data['quiet_hours_start'] );
			}
			if ( isset( $data['quiet_hours_end'] ) && '' !== $data['quiet_hours_end'] ) {
				$args['quiet_hours_end'] = intval( $data['quiet_hours_end'] );
			}
		}

		// Common settings for both group types.
		if ( isset( $data['threshold'] ) ) {
			$args['threshold'] = sanitize_text_field( $data['threshold'] );
		}
		if ( isset( $data['css'] ) ) {
			$args['css'] = sanitize_textarea_field( $data['css'] );
		}
		if ( isset( $data['group_name'] ) ) {
			$args['name'] = sanitize_text_field( $data['group_name'] );
		}

		// Merge advanced settings (basic auth, proxy, screenshot delay).
		$advanced_settings = $this->admin->settings_handler->extract_advanced_settings( $data );
		$args              = array_merge( $args, $advanced_settings );

		// Build bulk requests for all OTHER groups (main site was already saved above).
		$main_group_id = $is_monitoring ? $this->admin->monitoring_group_uuid : $this->admin->manual_group_uuid;
		$requests      = array();

		foreach ( $group_ids as $gid ) {
			if ( $gid === $main_group_id ) {
				continue; // Already saved above.
			}
			$request           = $args;
			$request['action'] = 'groups/' . $gid;
			$requests[]        = $request;
		}

		// Send bulk update for remaining groups.
		$success_count = 1; // Main site already succeeded.
		$fail_count    = 0;

		if ( ! empty( $requests ) ) {
			$results = \WebChangeDetector\WebChangeDetector_API_V2::api_v2_bulk( $requests, 'PUT' );

			foreach ( $results as $result ) {
				if ( ! empty( $result['success'] ) ) {
					++$success_count;
				} else {
					++$fail_count;
				}
			}
		}

		// For manual checks: also propagate auto-update website settings to all other websites.
		if ( ! $is_monitoring ) {
			$auto_update_settings = array();
			foreach ( $data as $key => $value ) {
				if ( 0 === strpos( $key, 'auto_update_checks_' ) ) {
					$auto_update_settings[ $key ] = $value;
				}
			}

			if ( ! empty( $auto_update_settings ) ) {
				$this->bulk_update_auto_update_settings( $auto_update_settings, $all_groups['by_site'] );
			}
		}

		if ( $fail_count > 0 ) {
			return array(
				'success' => true,
				'message' => sprintf(
					/* translators: 1: success count, 2: fail count */
					__( 'Settings updated for %1$d groups. %2$d failed.', 'webchangedetector' ),
					$success_count,
					$fail_count
				),
			);
		}

		return array(
			'success' => true,
			'message' => sprintf(
				/* translators: %d: number of groups updated */
				__( 'Settings saved for all %d websites.', 'webchangedetector' ),
				$success_count
			),
		);
	}

	/**
	 * Propagate auto-update settings to all registered websites.
	 *
	 * @since 4.3.0
	 * @param array $auto_update_settings The auto-update settings to propagate.
	 * @param array $sites_data           Array of site data from get_all_group_ids()['by_site'].
	 */
	private function bulk_update_auto_update_settings( $auto_update_settings, $sites_data ) {
		require_once WCD_PLUGIN_DIR . 'admin/class-webchangedetector-timezone-helper.php';

		// Prepare the settings (same logic as update_manual_check_group_settings).
		$prepared = array();
		foreach ( $auto_update_settings as $key => $value ) {
			if (
				'auto_update_checks_enabled' === $key ||
				in_array(
					$key,
					array(
						'auto_update_checks_monday',
						'auto_update_checks_tuesday',
						'auto_update_checks_wednesday',
						'auto_update_checks_thursday',
						'auto_update_checks_friday',
						'auto_update_checks_saturday',
						'auto_update_checks_sunday',
					),
					true
				)
			) {
				$prepared[ $key ] = ( '1' === $value || 1 === $value || true === $value );
			} elseif ( 'auto_update_checks_from' === $key || 'auto_update_checks_to' === $key ) {
				$prepared[ $key ] = \WebChangeDetector\WebChangeDetector_Timezone_Helper::site_time_to_utc( $value );
			} elseif ( 'auto_update_checks_emails' === $key ) {
				$prepared[ $key ] = array_values( array_filter( array_map( 'trim', explode( ',', sanitize_textarea_field( $value ) ) ) ) );
			} else {
				$prepared[ $key ] = $value;
			}
		}

		if ( ! isset( $prepared['auto_update_checks_enabled'] ) ) {
			$prepared['auto_update_checks_enabled'] = false;
		}

		// Build website update requests (skip main site, already saved).
		$main_website_id = get_option( 'webchangedetector_website_id', '' );
		$requests        = array();

		foreach ( $sites_data as $site_data ) {
			$website_id = \WebChangeDetector\WebChangeDetector_Multisite::with_blog(
				$site_data['blog_id'],
				function () {
					return get_option( 'webchangedetector_website_id', '' );
				}
			);

			if ( empty( $website_id ) || $website_id === $main_website_id ) {
				continue;
			}

			$request                         = array();
			$request['action']               = 'websites/' . $website_id;
			$request['auto_update_settings'] = $prepared;
			$requests[]                      = $request;
		}

		if ( ! empty( $requests ) ) {
			\WebChangeDetector\WebChangeDetector_API_V2::api_v2_bulk( $requests, 'PUT' );
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
		// The $data parameter is kept for interface consistency.
		unset( $data ); // Mark as intentionally unused.
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
		// The $data parameter is kept for interface consistency.
		unset( $data ); // Mark as intentionally unused.
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
			$valid_intervals = array( 0.25, 0.5, 1.0, 3.0, 6.0, 12.0, 24.0 );

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

		// Validate schedule type.
		if ( ! empty( $data['schedule_type'] ) ) {
			$valid_types = array( 'interval', 'weekly', 'monthly' );
			if ( ! in_array( $data['schedule_type'], $valid_types, true ) ) {
				$errors[] = 'Invalid schedule type.';
			}
		}

		// Validate schedule days.
		if ( ! empty( $data['schedule_days'] ) && is_array( $data['schedule_days'] ) ) {
			$schedule_type = $data['schedule_type'] ?? 'interval';
			foreach ( $data['schedule_days'] as $day ) {
				if ( 'weekly' === $schedule_type ) {
					if ( 'last' !== $day && ( intval( $day ) < 1 || intval( $day ) > 7 ) ) {
						$errors[] = 'Weekly schedule days must be between 1 and 7.';
						break;
					}
				} elseif ( 'monthly' === $schedule_type ) {
					if ( 'last' !== $day && ( intval( $day ) < 1 || intval( $day ) > 30 ) ) {
						$errors[] = 'Monthly schedule days must be between 1 and 30 or "last".';
						break;
					}
				}
			}
		}

		// Validate quiet hours.
		$has_start = isset( $data['quiet_hours_start'] ) && '' !== $data['quiet_hours_start'];
		$has_end   = isset( $data['quiet_hours_end'] ) && '' !== $data['quiet_hours_end'];
		if ( $has_start !== $has_end ) {
			$errors[] = 'Both quiet hours start and end must be set together, or both must be empty.';
		}
		if ( $has_start ) {
			$start = intval( $data['quiet_hours_start'] );
			if ( $start < 0 || $start > 23 ) {
				$errors[] = 'Quiet hours start must be between 0 and 23.';
			}
		}
		if ( $has_end ) {
			$end = intval( $data['quiet_hours_end'] );
			if ( $end < 0 || $end > 23 ) {
				$errors[] = 'Quiet hours end must be between 0 and 23.';
			}
		}

		return array(
			'success' => empty( $errors ),
			'errors'  => $errors,
		);
	}
}
