<?php

/**
Title: WebChange Detector Auto Update Feature
Description: Check your website on auto updates visually and see what changed.
Version: 1.0
 *
 * @package    WebChangeDetector
 */

namespace WebChangeDetector;

new WebChangeDetector_Autoupdates();

/**
 * Checks on wp auto updates
 *
 * @package    WebChangeDetector
 */
class WebChangeDetector_Autoupdates {


	/** Wp auto update lock name.
	 *
	 * @var string
	 */
	private string $lock_name = 'auto_updater.lock';

	/** Group ID for manual checks.
	 *
	 * @var string
	 */
	public string $manual_group_id;

	/** Group ID for monitoring checks.
	 *
	 * @var string
	 */
	public string $monitoring_group_id;

	/**
	 * Plugin constructor.
	 */
	public function __construct() {

		$this->set_defines();

		// Register the complete hook in constructor to ensure it's always registered.
		add_action( 'automatic_updates_complete', array( $this, 'automatic_updates_complete' ), 10, 1 );

		// Fallback for when no updates are available.
		add_action( 'wcd_check_update_completion', array( $this, 'check_update_completion' ) );

		// Post updates.
		add_action( 'wcd_cron_check_post_queues', array( $this, 'wcd_cron_check_post_queues' ), 10, 2 );

		// Saving settings.
		add_action( 'wcd_save_update_group_settings', array( $this, 'wcd_save_update_group_settings' ) );

		// Backup cron job for checking for updates.
		add_action( 'wcd_wp_version_check', array( $this, 'wcd_wp_version_check' ) );

		// Hooking into the update process.
		add_action( 'wp_maybe_auto_update', array( $this, 'wp_maybe_auto_update' ), 5 );

		// Add webhook endpoint for triggering cron jobs.
		add_action( 'init', array( $this, 'handle_webhook_trigger' ), 5 );

		// Add hourly sync check for auto-update settings from API.
		add_action( 'wcd_sync_auto_update_schedule', array( $this, 'sync_auto_update_schedule_from_api' ) );
		if ( ! wp_next_scheduled( 'wcd_sync_auto_update_schedule' ) ) {
			wp_schedule_event( time(), 'hourly', 'wcd_sync_auto_update_schedule' );
		}

		$wcd_groups = get_option( WCD_WEBSITE_GROUPS );
		if ( ! $wcd_groups ) {
			return;
		}
		$this->manual_group_id     = $wcd_groups[ WCD_MANUAL_DETECTION_GROUP ] ?? false;
		$this->monitoring_group_id = $wcd_groups[ WCD_AUTO_DETECTION_GROUP ] ?? false;
	}

    /**
     * This is a backup cron job for checking for updates.
     * We need to be careful not to interfere with an already running update process.
     *
     * @return void
     */
    public function wcd_wp_version_check() {
        // Check if pre-update screenshots are in progress. If so, we skip the version check.
		$pre_update_data = get_option( WCD_PRE_AUTO_UPDATE );
        $this->check_and_clean_stuck_pre_update( $pre_update_data );
		if ( $pre_update_data && isset( $pre_update_data['status'] ) ) {
			\WebChangeDetector\WebChangeDetector_Admin_Utils::log_error(
				'Skipping backup version check - pre-update screenshots in progress',
				'wcd_wp_version_check',
				'debug'
			);
			return;
		}

        \WebChangeDetector\WebChangeDetector_Admin_Utils::log_error(
			'Running backup wp_version_check',
			'wcd_wp_version_check',
			'debug'
		);
        wp_version_check();
    }


	/**
	 * Fires when wp auto updates are done.
	 *
	 * @return void
	 */
	public function automatic_updates_complete() {
		\WebChangeDetector\WebChangeDetector_Admin_Utils::log_error( 'Automatic Updates Complete. Running post-update stuff.', 'automatic_updates_complete', 'debug' );

		// Auto updates are done. So we ALWAYS remove the option, regardless of other conditions.
		delete_option( WCD_AUTO_UPDATES_RUNNING );

		// Also ensure lock is removed in case it got stuck
		delete_option( $this->lock_name );

		// We don't do anything here if wcd checks are disabled, or we don't have pre_auto_update option.
		$auto_update_settings = self::get_auto_update_settings();
		if ( ! array_key_exists( 'auto_update_checks_enabled', $auto_update_settings ) ) {
			\WebChangeDetector\WebChangeDetector_Admin_Utils::log_error( 'Skipping after update stuff as checks are disabled.', 'automatic_updates_complete', 'debug' );
			return;
		}

		$pre_update_data = get_option( WCD_PRE_AUTO_UPDATE );
		if ( ! $pre_update_data ) {
			\WebChangeDetector\WebChangeDetector_Admin_Utils::log_error( 'Skipping after update stuff as we don\'t have pre-update checks. This could happen if they were cleaned up due to timeout.', 'automatic_updates_complete', 'debug' );
			return;
		}

		// Clear all caches before taking post-update screenshots.
		$this->clear_wordpress_caches();

		// Start the post-update screenshots.
		\WebChangeDetector\WebChangeDetector_Admin_Utils::log_error( 'Starting post-update screenshots and comparisons.', 'automatic_updates_complete', 'debug' );

		try {
			$response = \WebChangeDetector\WebChangeDetector_API_V2::take_screenshot_v2( $this->manual_group_id, 'post' );
			\WebChangeDetector\WebChangeDetector_Admin_Utils::log_error( 'Post-Screenshot Response: ' . wp_json_encode( $response ), 'automatic_updates_complete', 'debug' );

			// Validate response structure
			if ( empty( $response ) || ! isset( $response['batch'] ) ) {
				throw new \Exception( 'Invalid API response for post-update screenshots: missing batch ID' );
			}

			$post_update_data = array(
				'status'    => 'processing',
				'batch_id'  => $response['batch'],
				'timestamp' => time(), // Add timestamp for timeout detection
			);
		} catch ( \Exception $e ) {
			\WebChangeDetector\WebChangeDetector_Admin_Utils::log_error(
				'Failed to start post-update screenshots: ' . $e->getMessage() . '. Cleaning up.',
				'automatic_updates_complete',
				'error'
			);
			// Clean up pre-update data since we can't complete the comparison
			delete_option( WCD_PRE_AUTO_UPDATE );
			return;
		}

		update_option( WCD_POST_AUTO_UPDATE, $post_update_data, false );

		// Schedule the cron to check post-update queue status
		$this->reschedule( 'wcd_cron_check_post_queues' );
		\WebChangeDetector\WebChangeDetector_Admin_Utils::log_error(
			'Scheduled wcd_cron_check_post_queues to check post-update screenshot status',
			'automatic_updates_complete',
			'debug'
		);

		// Add the batch id to the comparison batches. This is used to send the mail and for showing "Auto Update Checks" in the change detection page.
		$comparison_batches = get_option( WCD_AUTO_UPDATE_COMPARISON_BATCHES );
		if ( ! $comparison_batches ) {
			$comparison_batches = array();
		}
		$comparison_batches[] = $response['batch'];
		update_option( WCD_AUTO_UPDATE_COMPARISON_BATCHES, $comparison_batches );
		\WebChangeDetector\WebChangeDetector_API_V2::update_batch_v2( $response['batch'], 'Auto Update Checks - ' . WebChangeDetector_Admin_Utils::get_domain_from_site_url() );

		$this->wcd_cron_check_post_queues();
	}

	/**
	 * Cron for checking post_sc to be finished
	 *
	 * @return void
	 */
	public function wcd_cron_check_post_queues() {
		$post_sc_option = get_option( WCD_POST_AUTO_UPDATE );

		// Check for expired state using timestamp.
		if ( $post_sc_option && isset( $post_sc_option['timestamp'] ) ) {
			$age_in_seconds = time() - $post_sc_option['timestamp'];
			if ( $age_in_seconds > 7200 ) { // 2 hour timeout
				\WebChangeDetector\WebChangeDetector_Admin_Utils::log_error(
					'Found stuck post-update process from ' . $age_in_seconds . ' seconds ago. Cleaning up.',
					'wcd_cron_check_post_queues',
					'warning'
				);
				delete_option( WCD_POST_AUTO_UPDATE );
				delete_option( WCD_WORDPRESS_CRON );
				$post_sc_option = false;
			}
		} elseif ( $post_sc_option && ! isset( $post_sc_option['timestamp'] ) ) {
			// Old format without timestamp - assume it's stuck from a previous version
			\WebChangeDetector\WebChangeDetector_Admin_Utils::log_error(
				'Found old post-update process without timestamp (likely from version 3.x). Cleaning up.',
				'wcd_cron_check_post_queues',
				'warning'
			);
			delete_option( WCD_POST_AUTO_UPDATE );
			delete_option( WCD_WORDPRESS_CRON );
			$post_sc_option = false;
		}

		// Check if we still have the post_sc_option. If not, we already sent the mail.
		if ( ! $post_sc_option ) {
			\WebChangeDetector\WebChangeDetector_Admin_Utils::log_error( 'No post_sc_option found. So we already sent the mail.', 'wcd_cron_check_post_queues', 'debug' );
			return;
		}
		\WebChangeDetector\WebChangeDetector_Admin_Utils::log_error( 'Checking if post-update screenshots are done: ' . wp_json_encode( $post_sc_option ), 'wcd_cron_check_post_queues', 'debug' );
		$response = \WebChangeDetector\WebChangeDetector_API_V2::get_queues_v2( $post_sc_option['batch_id'], 'open,processing' );
		\WebChangeDetector\WebChangeDetector_Admin_Utils::log_error( 'Response: ' . wp_json_encode( $response ), 'wcd_cron_check_post_queues', 'debug' );

		// Check if the batch is done.
		if ( count( $response['data'] ) > 0 ) {
			// There are still open or processing queues. So we check again in a minute.
			\WebChangeDetector\WebChangeDetector_Admin_Utils::log_error( 'There are still open or processing queues. So we check again in a minute.', 'wcd_cron_check_post_queues', 'debug' );
			$this->reschedule( 'wcd_cron_check_post_queues' );
		} else {

			// Send the mail and update the last successful auto updates.
			$this->send_change_detection_mail( $post_sc_option );
			update_option( WCD_LAST_SUCCESSFULL_AUTO_UPDATES, time() );

			// We don't need the webhook anymore.
			\WebChangeDetector\WebChangeDetector_API_V2::delete_webhook_v2( get_option( WCD_WORDPRESS_CRON ) );

			// Cleanup wp_options and cron webhook.
			delete_option( WCD_WORDPRESS_CRON );
			delete_option( WCD_PRE_AUTO_UPDATE );
			delete_option( WCD_POST_AUTO_UPDATE );
			delete_option( WCD_AUTO_UPDATES_RUNNING );
			delete_option( WCD_AUTO_UPDATE_TRIGGERED_TIME );

			// Clean up scheduled fallback check
			wp_clear_scheduled_hook( 'wcd_check_update_completion' );
		}
	}

	/**
	 * Set lock to prevent wp from updating
	 *
	 * @return void
	 */
	public function set_lock() {
		\WebChangeDetector\WebChangeDetector_Admin_Utils::log_error( 'Setting Lock', 'set_lock', 'debug' );
		update_option( $this->lock_name, time() - HOUR_IN_SECONDS + MINUTE_IN_SECONDS );
	}

	/**
	 * Clear the execution lock transient
	 * Helper method to ensure consistent cleanup
	 *
	 * @return void
	 */
	private function clear_execution_lock() {
		delete_transient( 'wcd_update_check_running' );
	}


	/**
	 * Clean up stuck auto-update state (helper method for recovery)
	 *
	 * This method provides a centralized way to clean up all auto-update related
	 * options and transients when the system gets stuck or needs to be reset.
	 *
	 * @return void
	 */
	public function cleanup_stuck_auto_update_state() {
		\WebChangeDetector\WebChangeDetector_Admin_Utils::log_error( 'Cleaning up stuck auto-update state', 'cleanup_stuck_auto_update_state', 'warning' );

		// Clean up all auto update related options
		delete_option( WCD_PRE_AUTO_UPDATE );
		delete_option( WCD_POST_AUTO_UPDATE );
		delete_option( WCD_AUTO_UPDATES_RUNNING );
		delete_option( WCD_AUTO_UPDATE_TRIGGERED_TIME );

		// Delete webhook if exists
		$webhook_id = get_option( WCD_WORDPRESS_CRON );
		if ( $webhook_id ) {
			\WebChangeDetector\WebChangeDetector_API_V2::delete_webhook_v2( $webhook_id );
			delete_option( WCD_WORDPRESS_CRON );
		}

		// Clear WordPress auto updater locks
		delete_option( $this->lock_name );

		\WebChangeDetector\WebChangeDetector_Admin_Utils::log_error( 'Auto-update state cleanup completed', 'cleanup_stuck_auto_update_state', 'info' );
	}

	/**
	 * Check if concurrent execution should be prevented.
	 *
	 * @return bool True if should skip execution, false otherwise.
	 */
	private function should_skip_concurrent_execution() {
		$execution_lock = get_transient( 'wcd_update_check_running' );
		if ( $execution_lock && ( time() - $execution_lock ) < 30 ) {
			\WebChangeDetector\WebChangeDetector_Admin_Utils::log_error(
				'Skipping - another update check is already running (started ' . ( time() - $execution_lock ) . ' seconds ago).',
				'wp_maybe_auto_update',
				'debug'
			);
			return true;
		}
		return false;
	}

	/**
	 * Check and clean stuck auto-update running flag.
	 *
	 * @return bool True if updates are currently running, false otherwise.
	 */
	private function check_and_clean_stuck_running_flag() {
		$auto_updates_running = get_option( WCD_AUTO_UPDATES_RUNNING );
		if ( ! $auto_updates_running ) {
			return false;
		}

		// Check if this is a stuck flag
		$pre_update_data = get_option( WCD_PRE_AUTO_UPDATE );
		if ( $pre_update_data && isset( $pre_update_data['timestamp'] ) ) {
			$age_in_seconds = time() - $pre_update_data['timestamp'];
			if ( $age_in_seconds > HOUR_IN_SECONDS ) { // 1 hour timeout
				\WebChangeDetector\WebChangeDetector_Admin_Utils::log_error(
					'Auto updates flag is stuck (running for ' . $age_in_seconds . ' seconds). Clearing flag.',
					'wp_maybe_auto_update',
					'warning'
				);
				delete_option( WCD_AUTO_UPDATES_RUNNING );
				return false;
			}
		} else {
			// No timestamp available - could be stuck from old version
			\WebChangeDetector\WebChangeDetector_Admin_Utils::log_error(
				'Auto updates flag found without timestamp context. Clearing potentially stuck flag.',
				'wp_maybe_auto_update',
				'warning'
			);
			delete_option( WCD_AUTO_UPDATES_RUNNING );
			return false;
		}

		\WebChangeDetector\WebChangeDetector_Admin_Utils::log_error(
			'Auto updates are already started. Skipping auto updates.',
			'wp_maybe_auto_update',
			'debug'
		);
		return true;
	}

	/**
	 * Check and clean stuck post-update state.
	 *
	 * @return bool True if post-updates are in progress, false otherwise.
	 */
	private function check_and_clean_stuck_post_update() {
		$post_update_data = get_option( WCD_POST_AUTO_UPDATE );

		if ( ! $post_update_data ) {
			return false;
		}

		if ( isset( $post_update_data['timestamp'] ) ) {
			$age_in_seconds = time() - $post_update_data['timestamp'];
			if ( $age_in_seconds > 7200 ) { // 2 hour timeout
				\WebChangeDetector\WebChangeDetector_Admin_Utils::log_error(
					'Found stuck post-update process from ' . $age_in_seconds . ' seconds ago. Cleaning up.',
					'wp_maybe_auto_update',
					'warning'
				);
				delete_option( WCD_POST_AUTO_UPDATE );
				delete_option( WCD_WORDPRESS_CRON );
				return false;
			}
			\WebChangeDetector\WebChangeDetector_Admin_Utils::log_error(
				'Post-update screenshots already processed. Skipping auto updates.',
				'wp_maybe_auto_update',
				'debug'
			);
			return true;
		}

		// Old format without timestamp
		\WebChangeDetector\WebChangeDetector_Admin_Utils::log_error(
			'Found old post-update process without timestamp. Cleaning up.',
			'wp_maybe_auto_update',
			'warning'
		);
		delete_option( WCD_POST_AUTO_UPDATE );
		delete_option( WCD_WORDPRESS_CRON );
		return false;
	}

	/**
	 * Check if auto-updates were already run recently.
	 *
	 * @return bool True if should skip due to cooldown, false otherwise.
	 */
	private function is_within_cooldown_period() {
		// Skip cooldown check if debug logging is enabled
		if ( get_option( WCD_WP_OPTION_KEY_DEBUG_LOGGING ) ) {
			return false;
		}

		$last_successful = get_option( WCD_LAST_SUCCESSFULL_AUTO_UPDATES );
		if ( $last_successful && $last_successful + 12 * HOUR_IN_SECONDS > time() ) {
			\WebChangeDetector\WebChangeDetector_Admin_Utils::log_error(
				'Auto updates already done at ' . gmdate( 'Y-m-d H:i:s', $last_successful ) .
				'. We only do them once per day. Skipping auto updates.',
				'wp_maybe_auto_update',
				'debug'
			);
			return true;
		}
		return false;
	}

	/**
	 * Check and clean stuck WordPress auto-updater lock.
	 */
	private function check_and_clean_stuck_wordpress_lock() {
		$lock = get_option( $this->lock_name );
		if ( $lock && $lock < ( time() - HOUR_IN_SECONDS ) ) {
			\WebChangeDetector\WebChangeDetector_Admin_Utils::log_error(
				'Found stuck auto_updater.lock (age: ' . ( time() - $lock - HOUR_IN_SECONDS ) . ' seconds). Removing it.',
				'wp_maybe_auto_update',
				'warning'
			);
		}
		// Always remove the lock to start updates
		delete_option( $this->lock_name );
	}

	/**
	 * Check if any WordPress updates are available.
	 *
	 * @return array|false Array with update info if updates available, false otherwise.
	 */
	private function check_for_available_updates() {
		// Force a fresh check for updates
		wp_version_check();
		wp_update_plugins();
		wp_update_themes();

		$has_updates = array(
			'core'    => false,
			'plugins' => false,
			'themes'  => false,
			'total'   => 0,
		);

		// Check for core updates
		$core_updates = get_site_transient( 'update_core' );
		if ( $core_updates && ! empty( $core_updates->updates ) ) {
			foreach ( $core_updates->updates as $update ) {
				if ( 'upgrade' === $update->response || 'development' === $update->response ) {
					$has_updates['core'] = true;
					++$has_updates['total'];
					\WebChangeDetector\WebChangeDetector_Admin_Utils::log_error(
						'Core update available: ' . $update->version,
						'check_for_available_updates',
						'debug'
					);
					break;
				}
			}
		}

		// Check for plugin updates
		$plugin_updates = get_site_transient( 'update_plugins' );
		if ( $plugin_updates && ! empty( $plugin_updates->response ) ) {
			$update_count = count( $plugin_updates->response );
			if ( $update_count > 0 ) {
				$has_updates['plugins'] = true;
				$has_updates['total']  += $update_count;
				\WebChangeDetector\WebChangeDetector_Admin_Utils::log_error(
					'Plugin updates available: ' . $update_count,
					'check_for_available_updates',
					'debug'
				);
			}
		}

		// Check for theme updates
		$theme_updates = get_site_transient( 'update_themes' );
		if ( $theme_updates && ! empty( $theme_updates->response ) ) {
			$update_count = count( $theme_updates->response );
			if ( $update_count > 0 ) {
				$has_updates['themes'] = true;
				$has_updates['total'] += $update_count;
				\WebChangeDetector\WebChangeDetector_Admin_Utils::log_error(
					'Theme updates available: ' . $update_count,
					'check_for_available_updates',
					'debug'
				);
			}
		}

		// Also check if auto-updates are enabled for any of these
		if ( $has_updates['total'] > 0 ) {
			\WebChangeDetector\WebChangeDetector_Admin_Utils::log_error(
				'Total updates available: ' . $has_updates['total'],
				'check_for_available_updates',
				'info'
			);
			return $has_updates;
		}

		return false;
	}

	/**
	 * Check if WCD auto-update checks are properly configured and enabled.
	 *
	 * @return array|false Auto-update settings or false if not configured.
	 */
	private function validate_wcd_configuration() {
		$auto_update_settings = self::get_auto_update_settings();

		// Check if we have settings and group ID
		if ( ! $auto_update_settings || ! $this->manual_group_id ) {
			\WebChangeDetector\WebChangeDetector_Admin_Utils::log_error(
				'Running auto updates without checks. Don\'t have a group_id or auto update settings.',
				'wp_maybe_auto_update',
				'debug'
			);
			return false;
		}

		// Check if auto-update checks are enabled
		if ( ! array_key_exists( 'auto_update_checks_enabled', $auto_update_settings ) ||
			empty( $auto_update_settings['auto_update_checks_enabled'] ) ) {
			\WebChangeDetector\WebChangeDetector_Admin_Utils::log_error(
				'Running auto updates without checks. They are disabled in WCD.',
				'wp_maybe_auto_update',
				'debug'
			);
			return false;
		}

		return $auto_update_settings;
	}

	/**
	 * Check if auto-updates are allowed for today's weekday.
	 *
	 * @param array $auto_update_settings Auto-update settings.
	 * @return bool True if allowed today, false otherwise.
	 */
	private function is_allowed_today( $auto_update_settings ) {
		$todays_weekday = strtolower( current_time( 'l' ) );
		$weekday_key    = 'auto_update_checks_' . $todays_weekday;

		if ( ! array_key_exists( $weekday_key, $auto_update_settings ) ||
			empty( $auto_update_settings[ $weekday_key ] ) ) {
			\WebChangeDetector\WebChangeDetector_Admin_Utils::log_error(
				'Canceling auto updates: ' . $todays_weekday . ' is disabled.',
				'wp_maybe_auto_update',
				'debug'
			);
			return false;
		}
		return true;
	}

	/**
	 * Check if current time is within the allowed time window.
	 *
	 * @param array $auto_update_settings Auto-update settings.
	 * @return bool True if within time window, false otherwise.
	 */
	private function is_within_time_window( $auto_update_settings ) {
		// Load timezone helper
		require_once WP_PLUGIN_DIR . '/webchangedetector/admin/class-webchangedetector-timezone-helper.php';

		// Convert UTC times from API to site timezone
		$from_time_site = \WebChangeDetector\WebChangeDetector_Timezone_Helper::utc_to_site_time(
			$auto_update_settings['auto_update_checks_from']
		);
		$to_time_site   = \WebChangeDetector\WebChangeDetector_Timezone_Helper::utc_to_site_time(
			$auto_update_settings['auto_update_checks_to']
		);

		// Use WordPress timezone-aware datetime for accurate comparison
		$wp_timezone = wp_timezone();
		$now_wp      = new \DateTime( 'now', $wp_timezone );

		// Create DateTime objects for from and to times
		$from_datetime = new \DateTime( $now_wp->format( 'Y-m-d' ) . ' ' . $from_time_site, $wp_timezone );
		$to_datetime   = new \DateTime( $now_wp->format( 'Y-m-d' ) . ' ' . $to_time_site, $wp_timezone );

		// Get timestamps for comparison
		$from_timestamp    = $from_datetime->getTimestamp();
		$to_timestamp      = $to_datetime->getTimestamp();
		$current_timestamp = $now_wp->getTimestamp();

		// Check if current time is between from_time and to_time
		if ( $from_timestamp < $to_timestamp ) {
			// Case 1: Time range is on the same day
			if ( $current_timestamp < $from_timestamp || $current_timestamp > $to_timestamp ) {
				$this->log_time_window_violation( $from_time_site, $to_time_site, false );
				return false;
			}
		} else {
			// Case 2: Time range spans midnight
			$to_datetime->modify( '+1 day' );
			$to_timestamp = $to_datetime->getTimestamp();
			if ( ! ( $current_timestamp >= $from_timestamp || $current_timestamp <= $to_timestamp ) ) {
				$this->log_time_window_violation( $from_time_site, $to_time_site, true );
				return false;
			}
		}

		return true;
	}

	/**
	 * Log time window violation.
	 *
	 * @param string $from_time From time in site timezone.
	 * @param string $to_time To time in site timezone.
	 * @param bool   $spans_midnight Whether the time range spans midnight.
	 */
	private function log_time_window_violation( $from_time, $to_time, $spans_midnight ) {
		$message = sprintf(
			'Canceling auto updates: %s is not between %s and %s (site timezone%s)',
			current_time( 'H:i' ),
			$from_time,
			$to_time,
			$spans_midnight ? ', spans midnight' : ''
		);
		\WebChangeDetector\WebChangeDetector_Admin_Utils::log_error(
			$message,
			'wp_maybe_auto_update',
			'debug'
		);
	}

	/**
	 * Log the filter context for debugging.
	 */
	private function log_filter_context() {
		if ( ! doing_filter( 'wp_maybe_auto_update' ) &&
			! doing_filter( 'jetpack_pre_plugin_upgrade' ) &&
			! doing_filter( 'jetpack_pre_theme_upgrade' ) &&
			! doing_filter( 'jetpack_pre_core_upgrade' ) ) {
			\WebChangeDetector\WebChangeDetector_Admin_Utils::log_error(
				'Not called from one of the known filters. Continuing anyway.',
				'wp_maybe_auto_update',
				'debug'
			);
		}
	}

	/**
	 * Check and clean stuck pre-update state.
	 *
	 * @param array|false $pre_update_data Current pre-update data.
	 * @return array|false Cleaned pre-update data or false if none.
	 */
	private function check_and_clean_stuck_pre_update( $pre_update_data ) {
		if ( ! $pre_update_data ) {
			return false;
		}

		// Check for expired state using timestamp.
		if ( isset( $pre_update_data['timestamp'] ) ) {
			$age_in_seconds = time() - $pre_update_data['timestamp'];
			// Use 30 minute timeout (same as the transient was using).
			if ( $age_in_seconds > 1800 ) { // 30 minutes
				\WebChangeDetector\WebChangeDetector_Admin_Utils::log_error(
					'Pre-update state found but timeout expired. Initiating automatic recovery.',
					'wp_maybe_auto_update',
					'warning'
				);
				delete_option( WCD_PRE_AUTO_UPDATE );
				delete_option( WCD_AUTO_UPDATES_RUNNING );
				return false;
			}
		}

        // Check if pre-update screenshots are in progress.
		if ( isset( $pre_update_data['timestamp'] ) ) {
			$age_in_seconds = time() - $pre_update_data['timestamp'];
			if ( $age_in_seconds > 2 * HOUR_IN_SECONDS ) { // 2 hour timeout
				\WebChangeDetector\WebChangeDetector_Admin_Utils::log_error(
					'Found stuck pre-update process from ' . $age_in_seconds . ' seconds ago. Cleaning up.',
					'wp_maybe_auto_update',
					'warning'
				);
				delete_option( WCD_PRE_AUTO_UPDATE );
				delete_option( WCD_AUTO_UPDATES_RUNNING );
				return false;
			}
			return $pre_update_data;
		}

		// Old format without timestamp
		\WebChangeDetector\WebChangeDetector_Admin_Utils::log_error(
			'Found old pre-update process without timestamp. Cleaning up.',
			'wp_maybe_auto_update',
			'warning'
		);
		delete_option( WCD_PRE_AUTO_UPDATE );
		delete_option( WCD_AUTO_UPDATES_RUNNING );
		return false;
	}

	/**
	 * Start pre-update screenshots.
	 *
	 * @return bool True if started successfully, false on error.
	 */
	private function start_pre_update_screenshots() {
		// Schedule re-check
		$this->reschedule( 'wp_maybe_auto_update' );

		// Clear caches
		$this->clear_wordpress_caches();

		// Take screenshots
		try {
			$sc_response = \WebChangeDetector\WebChangeDetector_API_V2::take_screenshot_v2(
				$this->manual_group_id,
				'pre'
			);
			\WebChangeDetector\WebChangeDetector_Admin_Utils::log_error(
				'Pre update SC data: ' . wp_json_encode( $sc_response ),
				'wp_maybe_auto_update',
				'debug'
			);

			// Validate response
			if ( empty( $sc_response ) || ! isset( $sc_response['batch'] ) ) {
				throw new \Exception( 'Invalid API response: missing batch ID' );
			}

			$option_data = array(
				'status'    => 'processing',
				'batch_id'  => esc_html( $sc_response['batch'] ),
				'timestamp' => time(),
			);

			// Save state
			\WebChangeDetector\WebChangeDetector_Admin_Utils::log_error(
				'Started taking screenshots and setting options',
				'wp_maybe_auto_update',
				'debug'
			);
			update_option( WCD_PRE_AUTO_UPDATE, $option_data, false );

			// Set lock to prevent WordPress updates
			$this->set_lock();
			return true;

		} catch ( \Exception $e ) {
			\WebChangeDetector\WebChangeDetector_Admin_Utils::log_error(
				'Failed to start pre-update screenshots: ' . $e->getMessage(),
				'wp_maybe_auto_update',
				'error'
			);
			delete_option( WCD_AUTO_UPDATES_RUNNING );
			return false;
		}
	}

	/**
	 * Check if pre-update screenshots are ready.
	 *
	 * @param array $pre_update_data Pre-update data with batch ID.
	 * @return bool True if ready, false if still processing.
	 */
	private function check_pre_update_screenshots_status( $pre_update_data ) {
		\WebChangeDetector\WebChangeDetector_Admin_Utils::log_error(
			'Checking if screenshots are ready',
			'wp_maybe_auto_update',
			'debug'
		);

		try {
			$response = \WebChangeDetector\WebChangeDetector_API_V2::get_queues_v2(
				$pre_update_data['batch_id'],
				'open,processing'
			);

			// Validate response
			if ( ! is_array( $response ) || ! isset( $response['data'] ) ) {
				throw new \Exception( 'Invalid queue response structure' );
			}

			\WebChangeDetector\WebChangeDetector_Admin_Utils::log_error(
				'Queue: ' . wp_json_encode( $response ),
				'wp_maybe_auto_update',
				'debug'
			);

			// Check if queues are done.
			if ( count( $response['data'] ) === 0 ) {
                // Queues are done.
				return true;
			}

			// Still processing
			\WebChangeDetector\WebChangeDetector_Admin_Utils::log_error(
				'SCs are not ready yet. Waiting for next cron run.',
				'wp_maybe_auto_update',
				'debug'
			);
			$this->reschedule( 'wp_maybe_auto_update' );
			$this->set_lock();
			return false;

		} catch ( \Exception $e ) {
			\WebChangeDetector\WebChangeDetector_Admin_Utils::log_error(
				'Failed to check queue status: ' . $e->getMessage() . '. Will retry on next run.',
				'wp_maybe_auto_update',
				'warning'
			);
			$this->reschedule( 'wp_maybe_auto_update' );
			$this->set_lock();
			return false;
		}
	}

	/**
	 * Trigger WordPress auto-updates after pre-update screenshots are ready.
	 */
	private function trigger_wordpress_updates() {
		\WebChangeDetector\WebChangeDetector_Admin_Utils::log_error(
			'SCs are ready. Continuing with the updates.',
			'wp_maybe_auto_update',
			'debug'
		);

		// Mark auto-updates as running
		update_option( WCD_AUTO_UPDATES_RUNNING, true );

		// Store timestamp when we triggered updates (for fallback check)
		update_option( WCD_AUTO_UPDATE_TRIGGERED_TIME, time() );

		// Remove the lock so WordPress can run updates
		delete_option( $this->lock_name );
		\WebChangeDetector\WebChangeDetector_Admin_Utils::log_error(
			'Removed auto_updater.lock to allow WordPress to proceed with updates.',
			'wp_maybe_auto_update',
			'debug'
		);

		// Check if WordPress is installing
		if ( wp_installing() ) {
			\WebChangeDetector\WebChangeDetector_Admin_Utils::log_error(
				'Cannot run updates: WordPress is currently installing.',
				'wp_maybe_auto_update',
				'debug'
			);
			delete_option( WCD_AUTO_UPDATES_RUNNING );
			delete_option( WCD_AUTO_UPDATE_TRIGGERED_TIME );
			return;
		}

		// Schedule a fallback check for when no updates are available
		$this->schedule_update_completion_check();

		// Let WordPress handle the updates naturally
		\WebChangeDetector\WebChangeDetector_Admin_Utils::log_error(
			'WordPress is not installing. Returning to trigger the wp hook of wp_maybe_auto_update.',
			'wp_maybe_auto_update',
			'debug'
		);
	}

	/**
	 * Schedule a check to see if WordPress updates have completed.
	 * This is a fallback for when automatic_updates_complete doesn't fire (no updates available).
	 */
	private function schedule_update_completion_check() {
		// Schedule check in 2 minutes to see if updates are done
		wp_clear_scheduled_hook( 'wcd_check_update_completion' );
		wp_schedule_single_event( time() + 120, 'wcd_check_update_completion' );

		\WebChangeDetector\WebChangeDetector_Admin_Utils::log_error(
			'Scheduled fallback check for update completion in 2 minutes.',
			'schedule_update_completion_check',
			'debug'
		);
	}

	/**
	 * Check if WordPress updates have completed (with or without actual updates).
	 * This is called by cron as a fallback when automatic_updates_complete doesn't fire.
	 */
	public function check_update_completion() {
		// Check if we're still waiting for updates
		if ( ! get_option( WCD_AUTO_UPDATES_RUNNING ) ) {
			\WebChangeDetector\WebChangeDetector_Admin_Utils::log_error(
				'Update completion check: Updates are not marked as running. Nothing to do.',
				'check_update_completion',
				'debug'
			);
			return;
		}

		// Logging for Checking for update completion
		\WebChangeDetector\WebChangeDetector_Admin_Utils::log_error(
			'Checking for update completion',
			'check_update_completion',
			'debug'
		);
		

		// Check if the auto_updater.lock still exists
		$lock = get_option( $this->lock_name );

		if ( $lock ) {
			// Lock still exists, WordPress might still be checking/updating
			$lock_age = time() - $lock;

			// WordPress uses 1 hour as lock timeout, so if it's older, it's stuck
			if ( $lock_age > HOUR_IN_SECONDS ) {
				\WebChangeDetector\WebChangeDetector_Admin_Utils::log_error(
					'Update completion check: Lock is stuck (age: ' . $lock_age . ' seconds). Treating as completed.',
					'check_update_completion',
					'warning'
				);
				// Treat as completed and clean up
				delete_option( $this->lock_name );
				$this->handle_no_updates_scenario();
			} else {
				\WebChangeDetector\WebChangeDetector_Admin_Utils::log_error(
					'Update completion check: Lock still exists (age: ' . $lock_age . ' seconds). Updates might still be running.',
					'check_update_completion',
					'debug'
				);
				// Check again in 1 minute
				wp_schedule_single_event( time() + 60, 'wcd_check_update_completion' );
			}
		} else {
			// No lock means WordPress finished checking (with or without updates)
			\WebChangeDetector\WebChangeDetector_Admin_Utils::log_error(
				'Update completion check: No lock found. WordPress finished checking for updates.',
				'check_update_completion',
				'debug'
			);

			// Check how long ago we triggered the updates
			$triggered_time = get_option( WCD_AUTO_UPDATE_TRIGGERED_TIME );
			if ( $triggered_time ) {
				$elapsed = time() - $triggered_time;
				\WebChangeDetector\WebChangeDetector_Admin_Utils::log_error(
					'Updates were triggered ' . $elapsed . ' seconds ago.',
					'check_update_completion',
					'debug'
				);
			}

			// Handle the case where no updates were available
			$this->handle_no_updates_scenario();
		}
	}

	/**
	 * Handle the scenario where WordPress checked but found no updates.
	 * This mimics what automatic_updates_complete would do.
	 */
	private function handle_no_updates_scenario() {
		\WebChangeDetector\WebChangeDetector_Admin_Utils::log_error(
			'No updates were available. Running post-update workflow anyway.',
			'handle_no_updates_scenario',
			'info'
		);

		// Clean up the triggered time
		delete_option( WCD_AUTO_UPDATE_TRIGGERED_TIME );

		// Call the same logic as automatic_updates_complete
		// This ensures we complete the workflow even when no updates happened
		$this->automatic_updates_complete();
	}

	/** Reset next cron run of wp_version_check to our auto_update_checks_from.
	 *
	 * @param array $group_settings Array of group settings.
	 * @return void
	 */
	public function wcd_save_update_group_settings( $group_settings ) {
		// Get the time in UTC from API
		if ( isset( $group_settings['auto_update_checks_from'] ) ) {
			$auto_update_checks_from_utc = $group_settings['auto_update_checks_from'];
		} else {
			$auto_update_settings = self::get_auto_update_settings();
			if ( ! $auto_update_settings ) {
				return;
			}
			$auto_update_checks_from_utc = $auto_update_settings['auto_update_checks_from'];
		}

		// IMPORTANT: The time from API is in UTC and represents when the user wants
		// the check to run IN THEIR LOCAL TIME.
		// Example: User wants checks at 09:00 local time (EST)
		// - User enters: 09:00
		// - We convert and save to API: 14:00 UTC (09:00 + 5 hours)
		// - API returns: 14:00 UTC
		// - We schedule cron for: 14:00 UTC
		// - Cron runs at: 14:00 UTC which is 09:00 EST (correct!)

		// Create DateTime for today at the scheduled UTC time
		\WebChangeDetector\WebChangeDetector_Admin_Utils::log_error( 'Auto update checks "from" time from API (UTC): ' . $auto_update_checks_from_utc, 'wcd_save_update_group_settings', 'debug' );
		$today_utc              = gmdate( 'Y-m-d' );
		$scheduled_datetime_utc = $today_utc . ' ' . $auto_update_checks_from_utc . ':00';
		// Use strtotime with explicit UTC timezone to ensure correct parsing
		$should_next_run_gmt = strtotime( $scheduled_datetime_utc . ' UTC' );

		// We skip delaying to next day if current time passed "from" time.
		// The cron will run now if the time is already passed.
		// If the end time of our time window is still higher than now, we do the updates now this way. Otherwise it will skip.

		// Log for debugging
		require_once WP_PLUGIN_DIR . '/webchangedetector/admin/class-webchangedetector-timezone-helper.php';
		$site_time = \WebChangeDetector\WebChangeDetector_Timezone_Helper::utc_to_site_time( $auto_update_checks_from_utc );

		\WebChangeDetector\WebChangeDetector_Admin_Utils::log_error(
			sprintf(
				'Scheduling wp_version_check - API stores as %s UTC, Scheduling cron for %s UTC (%s local)',
				$auto_update_checks_from_utc,
				gmdate( 'Y-m-d H:i:s', $should_next_run_gmt ),
				$site_time
			),
			'wcd_save_update_group_settings',
			'debug'
		);

		\WebChangeDetector\WebChangeDetector_Admin_Utils::log_error( 'Scheduling wp_version_check for ' . gmdate( 'Y-m-d H:i:s', $should_next_run_gmt ), 'wcd_save_update_group_settings', 'debug' );

		// Clear and reschedule the WordPress update check crons
		wp_clear_scheduled_hook( 'wp_version_check' );
		wp_schedule_event( $should_next_run_gmt, 'twicedaily', 'wp_version_check' );

		// Backup cron in case something else changes the wp_version_check cron. We add 1 second to let it run 2nd.
		wp_clear_scheduled_hook( 'wcd_wp_version_check' );
		wp_schedule_event( $should_next_run_gmt + 1, 'daily', 'wcd_wp_version_check' );
	}

	/** Starting the pre-update screenshots before auto-updates are started.
	 * We set the lock to delay WP from starting the auto updates.
	 * Auto updates are delayed when they are not in the selected timeframe.
	 *
	 * This method has been refactored into smaller, focused methods for better
	 * readability and maintainability. Each step is now clearly separated.
	 *
	 * @return void
	 */
	public function wp_maybe_auto_update() {
		// Step 1: Check for concurrent execution
		if ( $this->should_skip_concurrent_execution() ) {
			return;
		}

		// Set execution lock for this function.
		set_transient( 'wcd_update_check_running', time(), 60 );

		// Step 2: Check if auto-updates are already running
		if ( $this->check_and_clean_stuck_running_flag() ) {
            $this->set_lock();
			return;
		}

		// Step 4: Check if post-update screenshots are in progress
		if ( $this->check_and_clean_stuck_post_update() ) {
			$this->set_lock();
			return;
		}

		// Step 5: Check cooldown period
		if ( $this->is_within_cooldown_period() ) {
			$this->set_lock();
			return;
		}

		// Step 6: Clean stuck WordPress lock if needed
		$this->check_and_clean_stuck_wordpress_lock();

		// Step 7: Validate WCD configuration
		$auto_update_settings = $this->validate_wcd_configuration();
		if ( ! $auto_update_settings ) {
			return;
		}

		// Step 8: Check if updates are allowed today
		if ( ! $this->is_allowed_today( $auto_update_settings ) ) {
			$this->set_lock();
			return;
		}

		// Step 9: Check if current time is within allowed window
		if ( ! $this->is_within_time_window( $auto_update_settings ) ) {
			$this->set_lock();
			return;
		}

		// Step 11: Log filter context (informational only)
		$this->log_filter_context();

		// Step 12: Handle pre-update screenshots.
		$wcd_pre_update_data = get_option( WCD_PRE_AUTO_UPDATE );
		$wcd_pre_update_data = $this->check_and_clean_stuck_pre_update( $wcd_pre_update_data );

		if ( false === $wcd_pre_update_data ) {

            // Step 10: Check if there are actually updates available. 
            $available_updates = $this->check_for_available_updates();

            // If we don't have updates to install, we remove all options and set the lock. 
            if ( ! $available_updates ) {
                \WebChangeDetector\WebChangeDetector_Admin_Utils::log_error(
                    'No updates available. Skipping auto-update process and screenshots.',
                    'wp_maybe_auto_update',
                    'info'
                );

                // Clear any stuck state since there's nothing to update
                delete_option( WCD_PRE_AUTO_UPDATE );
                delete_option( WCD_POST_AUTO_UPDATE );
                delete_option( WCD_AUTO_UPDATES_RUNNING );
                delete_option( WCD_AUTO_UPDATE_TRIGGERED_TIME );

                // Set lock to prevent checking again too soon
                $this->set_lock();
                return;
            } 
            
            \WebChangeDetector\WebChangeDetector_Admin_Utils::log_error(
                'Updates available - Core: ' . $available_updates['core'] .
                ', Plugins: ' . $available_updates['plugins'] .
                ', Themes: ' . $available_updates['themes'] .
                '. Proceeding with auto-update process.',
                'wp_maybe_auto_update',
                'info'
            );
            
			// Start new pre-update screenshots and reschedule wp_maybe_auto_update.
			$this->start_pre_update_screenshots();
				
		} else {
			// Check existing pre-update screenshots status
			$is_ready = $this->check_pre_update_screenshots_status( $wcd_pre_update_data );

			if ( $is_ready ) {
                // Update status to done
				$pre_update_data['status'] = 'done';
				if ( ! isset( $pre_update_data['timestamp'] ) ) {
					$pre_update_data['timestamp'] = time();
				}
				update_option( WCD_PRE_AUTO_UPDATE, $pre_update_data, false );

				// Screenshots are ready, trigger WordPress updates
				$this->trigger_wordpress_updates();
			}
			// If not ready, the check_pre_update_screenshots_status method
			// has already rescheduled and set the lock
		}

		// Clear execution lock
		delete_transient( 'wcd_update_check_running' );
	}

	/** Send the change detection mail.
	 *
	 * @param array $post_sc_option Data about the post sc.
	 * @return void
	 */
	public function send_change_detection_mail( $post_sc_option ) {
		// If we don't have open or processing queues of the batch anymore, we can check for comparisons.
		$comparisons = \WebChangeDetector\WebChangeDetector_API_V2::get_comparisons_v2( array( 'batches' => $post_sc_option['batch_id'] ) );
		$mail_body   = '<style>
								table {
									border: 1px solid #ccc;
									width: 100%;
								}
								th, td {
								  padding: 10px;
								  border-top: 1px solid #aaa;
								}
								tr:nth-child(odd),
								 {
									background: #F0F0F1;
								}
								th {
									background: #DCE3ED;
								}
								</style>
								<div style="width: 800px; margin: 0 auto;">';

		$mail_body .= '<p>Howdy again, we checked your website for visual changes during the WP auto updates with WebChange Detector. Here are the results:</p>';
		if ( count( $comparisons['data'] ) ) {
			$no_difference_rows   = '';
			$with_difference_rows = '';

			foreach ( $comparisons['data'] as $comparison ) {
				$row =
					'<tr>
						<td>' . $comparison['url'] . '</td>
						<td>' . $comparison['device'] . '</td>
						<td>' . $comparison['difference_percent'] . ' %</td>
		                <td><a href="' . $comparison['public_link'] . '">See changes</a></td>
					</tr>';
				if ( ! $comparison['difference_percent'] ) {
					$no_difference_rows .= $row;
				} else {
					$with_difference_rows .= $row;
				}
			}
			$mail_body .= '<div style="width: 300px; margin: 20px auto; text-align: center; padding: 30px; background: #DCE3ED;">';
			if ( empty( $with_difference_rows ) ) {
				$mail_body .= '<div style="padding: 10px;background: green; color: #fff; border-radius: 20px; font-size: 14px; width: 20px; height: 20px; display: inline-block; font-weight: 900; transform: scaleX(-1) rotate(-35deg);">L</div>
									<div style="font-size: 18px; padding-top: 20px;">Checks Passed</div>';
			} else {
				$mail_body .= '<div style="padding: 10px;background: red; color: #fff; border-radius: 20px;  font-size: 14px; width: 20px; height: 20px; display: inline-block; font-weight: 900; ">X</div>
									<div style="font-size: 18px; padding-top: 20px;">We found changes<br>Please check the change detections.</div>';
			}
			$mail_body .= '</div>';

			$mail_body .= '<div style="margin: 20px 0 10px 0"><strong>Checks with differences</strong></div>';
			$mail_body .= '<table><tr><th>URL</th><th>Device</th><th>Change in %</th><th>Change Detection Page</th></tr>';
			if ( ! empty( $with_difference_rows ) ) {
				$mail_body .= $with_difference_rows;
			} else {
				$mail_body .= '<tr><td colspan="3" style="text-align: center;">No change detections to show here</td>';
			}
			$mail_body .= '</table>';

			$mail_body .= '<div style="margin: 20px 0 10px 0"><strong>Checks without differences</strong></div>';
			$mail_body .= '<table><tr><th>URL</th><th>Device</th><th>Change in %</th><th>Change Detection Page</th></tr>';
			if ( ! empty( $no_difference_rows ) ) {
				$mail_body .= $no_difference_rows;
			} else {
				$mail_body .= '<tr><td colspan="3" style="text-align: center;">No change detections to show here</td>';
			}
			$mail_body .= '</table>';
		} else {
			$mail_body .= 'Sorry, there were no comparisons. Please check your settings in your WebChange Detector Plugin.';
		}

		$mail_body .= '<div style="margin: 20px 0">You can find all change detections and settings for the checks 
								in your wp-admin dashboard of your website.<br><br>
								Your WebChange Detector team</div>';

		$auto_update_settings = self::get_auto_update_settings();
		$to                   = get_bloginfo( 'admin_email' );
		if ( array_key_exists( 'auto_update_checks_emails', $auto_update_settings ) || ! empty( $auto_update_settings['auto_update_checks_emails'] ) ) {
			$to = $auto_update_settings['auto_update_checks_emails'];
		}
		$subject = '[' . get_bloginfo( 'name' ) . '] Auto Update Checks by WebChange Detector';
		$headers = array( 'Content-Type: text/html; charset=UTF-8' );
		\WebChangeDetector\WebChangeDetector_Admin_Utils::log_error( 'Sending Mail with differences', 'send_change_detection_mail', 'debug' );
		wp_mail( $to, $subject, $mail_body, $headers );
	}

	/** Get the auto-update settings.
	 *
	 * @param bool $force_refresh Force refresh from API, bypassing static cache.
	 * @return false|mixed|null
	 */
	public static function get_auto_update_settings( $force_refresh = false ) {
		static $auto_update_settings;

		// Return cached version unless force refresh is requested
		if ( $auto_update_settings && ! $force_refresh ) {
			return $auto_update_settings;
		}

		$wcd                  = new WebChangeDetector_Admin();
		$auto_update_settings = $wcd->settings_handler->get_website_details( $force_refresh )['auto_update_settings'] ?? array();

		// Enable auto-update checks if the defines are set.
		if ( defined( 'WCD_AUTO_UPDATES_ENABLED' ) && true === \WCD_AUTO_UPDATES_ENABLED ) {
			$auto_update_settings['auto_update_checks_enabled'] = true;
		}
		return $auto_update_settings;
	}

	/**
	 * Sync auto-update schedule from API settings.
	 * This runs hourly to ensure local schedulers match API settings.
	 * Also performs basic health checks since API call validates connectivity.
	 *
	 * @return void
	 */
	public function sync_auto_update_schedule_from_api() {
		\WebChangeDetector\WebChangeDetector_Admin_Utils::log_error( 'Starting auto-update schedule sync with API', 'sync_auto_update_schedule_from_api', 'debug' );

		// Perform basic health status update
		$health_status = array(
			'overall_status' => 'healthy',
			'checks'         => array(),
			'timestamp'      => current_time( 'mysql' ),
		);

		try {
			// Get fresh settings from API (force refresh)
			// This call effectively validates API connectivity and authentication
			$wcd                   = new WebChangeDetector_Admin();
			$fresh_website_details = $wcd->settings_handler->get_website_details( true ); // Force refresh from API

			// API call succeeded - mark as healthy
			$health_status['checks']['api'] = array(
				'status'  => true,
				'message' => 'API connectivity OK',
			);

			// Check configuration while we have the data
			$api_token                                = get_option( WCD_WP_OPTION_KEY_API_TOKEN );
			$groups                                   = get_option( WCD_WEBSITE_GROUPS );
			$health_status['checks']['configuration'] = array(
				'status'  => ! empty( $api_token ) && ! empty( $groups ),
				'message' => ( ! empty( $api_token ) && ! empty( $groups ) ) ? 'Configuration OK' : 'Configuration incomplete',
			);

			$api_auto_update_settings = $fresh_website_details['auto_update_settings'] ?? array();

			if ( empty( $api_auto_update_settings ) ) {
				\WebChangeDetector\WebChangeDetector_Admin_Utils::log_error( 'No auto-update settings from API, skipping sync', 'sync_auto_update_schedule_from_api', 'debug' );
			} else {
				// Clear the static cache in get_auto_update_settings
				self::get_auto_update_settings( true );

				// Update the schedule using existing method (this reschedules the crons)
				// The wcd_save_update_group_settings method already handles everything:
				// - Reschedules wp_version_check
				// - Reschedules wcd_wp_version_check
				// - Sets the correct timeframe
				$this->wcd_save_update_group_settings( $api_auto_update_settings ); // true = skip API save

				\WebChangeDetector\WebChangeDetector_Admin_Utils::log_error(
					'Auto-update schedule synced with API settings',
					'sync_auto_update_schedule_from_api',
					'debug'
				);
			}
		} catch ( \Exception $e ) {
			// API call failed - mark as unhealthy
			$health_status['checks']['api']  = array(
				'status'  => false,
				'message' => 'API connectivity failed: ' . $e->getMessage(),
			);
			$health_status['overall_status'] = 'unhealthy';

			\WebChangeDetector\WebChangeDetector_Admin_Utils::log_error(
				'Failed to sync auto-update schedule: ' . $e->getMessage(),
				'sync_auto_update_schedule_from_api',
				'error'
			);
		}

		// Update health status
		update_option( WCD_WP_OPTION_KEY_HEALTH_STATUS, $health_status );
	}

	/**
	 * Get or create webhook key for API authentication
	 *
	 * @return string The webhook key
	 */
	private function get_or_create_webhook_key() {
		$webhook_key = get_option( 'wcd_webhook_key', '' );
		if ( empty( $webhook_key ) ) {
			// Create a new webhook key if we don't have one.
			$webhook_key = wp_generate_password( 32, false );
			update_option( 'wcd_webhook_key', $webhook_key );
		}
		return $webhook_key;
	}

	/**
	 * Create a cron at our api to trigger a hook after a certain time.
	 *
	 * @param string $hook Hook name.
	 * @return void
	 */
	private function reschedule( $hook ) {
		// Our cron method for the hook.
		$how_long = 50; // 50 seconds.
		wp_clear_scheduled_hook( $hook );
		wp_schedule_single_event( time() + $how_long, $hook );

		// Store the webhook ID to avoid duplication.
		$webhook_id = get_option( WCD_WORDPRESS_CRON, false );
		if ( $webhook_id ) {
			\WebChangeDetector\WebChangeDetector_Admin_Utils::log_error( 'We already have a webhook for this hook. Skipping...', 'reschedule', 'debug' );
			return;
		}

		// Create our external webhook url.
		$webhook_url = add_query_arg(
			array(
				'wcd_action' => WCD_TRIGGER_AUTO_UPDATE_CRON,
				'key'        => $this->get_or_create_webhook_key(),
			),
			site_url()
		);

		\WebChangeDetector\WebChangeDetector_Admin_Utils::log_error( 'Creating webhook to trigger ' . $hook, 'reschedule', 'debug' );

		// Create a new WordPress cron webhook.
		$result = \WebChangeDetector\WebChangeDetector_API_V2::add_webhook_v2( $webhook_url, 'wordpress_cron', gmdate( 'Y-m-d H:i:s', time() + HOUR_IN_SECONDS * 3 ) );

		if ( is_array( $result ) && isset( $result['data'] ) && isset( $result['data']['id'] ) ) {
			// Store the webhook ID for later reference.
			update_option( WCD_WORDPRESS_CRON, $result['data']['id'] );
		}
	}

	/**
	 * Process webhook trigger by executing the appropriate WordPress cron event
	 */
	public function handle_webhook_trigger() {
		$is_authorized = false;

		// We're using a custom API key verification approach instead of nonces since this is an external webhook
		// that needs to remain valid for several hours. The 'key' parameter contains a random 32-character string
		// that's verified against our stored option.
		if ( isset( $_GET['wcd_action'] ) && isset( $_GET['key'] ) ) {
			$wcd_action = sanitize_text_field( wp_unslash( $_GET['wcd_action'] ) );
			$key        = sanitize_text_field( wp_unslash( $_GET['key'] ) );

			if ( WCD_TRIGGER_AUTO_UPDATE_CRON === $wcd_action && ! empty( $key ) ) {
				$webhook_key = $this->get_or_create_webhook_key();
				if ( ! empty( $webhook_key ) && $key === $webhook_key ) {
					$is_authorized = true;
				}
			}
		}

		if ( $is_authorized ) {
			\WebChangeDetector\WebChangeDetector_Admin_Utils::log_error( 'Processing authorized webhook trigger', 'handle_webhook_trigger', 'debug' );

			// Also trigger our fallback check for update completion
			if ( get_option( WCD_AUTO_UPDATES_RUNNING ) ) {
				\WebChangeDetector\WebChangeDetector_Admin_Utils::log_error(
					'Webhook trigger: Also checking update completion status',
					'handle_webhook_trigger',
					'debug'
				);
				$this->check_update_completion();
			}

			// Force WordPress to process all pending cron events.
			spawn_cron();

			echo 'OK';
			exit;
		}
	}

	/**
	 * Defines.
	 *
	 * @return void
	 */
	private function set_defines() {

		if ( ! defined( 'WCD_WEBSITE_GROUPS' ) ) {
			define( 'WCD_WEBSITE_GROUPS', 'wcd_website_groups' );
		}
		if ( ! defined( 'WCD_MANUAL_DETECTION_GROUP' ) ) {
			define( 'WCD_MANUAL_DETECTION_GROUP', 'manual_detection_group' );
		}
		if ( ! defined( 'WCD_AUTO_DETECTION_GROUP' ) ) {
			define( 'WCD_AUTO_DETECTION_GROUP', 'auto_detection_group' );
		}
		if ( ! defined( 'WCD_WORDPRESS_CRON' ) ) {
			define( 'WCD_WORDPRESS_CRON', 'wcd_wordpress_cron' );
		}
		if ( ! defined( 'WCD_LAST_SUCCESSFULL_AUTO_UPDATES' ) ) {
			define( 'WCD_LAST_SUCCESSFULL_AUTO_UPDATES', 'wcd_last_successfull_auto_updates' );
		}
		if ( ! defined( 'WCD_PRE_AUTO_UPDATE' ) ) {
			define( 'WCD_PRE_AUTO_UPDATE', 'wcd_pre_auto_update' );
		}
		if ( ! defined( 'WCD_WP_MAYBE_AUTO_UPDATE' ) ) {
			define( 'WCD_WP_MAYBE_AUTO_UPDATE', 'wp_maybe_auto_update' );
		}
		if ( ! defined( 'WCD_POST_AUTO_UPDATE' ) ) {
			define( 'WCD_POST_AUTO_UPDATE', 'wcd_post_auto_update' );
		}
		if ( ! defined( 'WCD_AUTO_UPDATES_RUNNING' ) ) {
			define( 'WCD_AUTO_UPDATES_RUNNING', 'wcd_auto_updates_running' );
		}
		if ( ! defined( 'WCD_AUTO_UPDATE_SETTINGS' ) ) {
			define( 'WCD_AUTO_UPDATE_SETTINGS', 'wcd_auto_update_settings' );
		}
		if ( ! defined( 'WCD_ALLOWANCES' ) ) {
			define( 'WCD_ALLOWANCES', 'wcd_allowances' );
		}
		if ( ! defined( 'WCD_HOUR_IN_SECONDS' ) ) {
			define( 'WCD_HOUR_IN_SECONDS', 3600 );
		}
		if ( ! defined( 'WCD_AUTO_UPDATE_COMPARISON_BATCHES' ) ) {
			define( 'WCD_AUTO_UPDATE_COMPARISON_BATCHES', 'wcd_auto_update_comparison_batches' );
		}
		if ( ! defined( 'WCD_TRIGGER_AUTO_UPDATE_CRON' ) ) {
			define( 'WCD_TRIGGER_AUTO_UPDATE_CRON', 'trigger_auto_update_cron' );
		}
		if ( ! defined( 'WCD_AUTO_UPDATE_TRIGGERED_TIME' ) ) {
			define( 'WCD_AUTO_UPDATE_TRIGGERED_TIME', 'wcd_auto_update_triggered_time' );
		}
		if ( ! defined( 'WCD_WP_OPTION_KEY_DEBUG_LOGGING' ) ) {
			define( 'WCD_WP_OPTION_KEY_DEBUG_LOGGING', 'webchangedetector_debug_logging' );
		}
		if ( ! defined( 'WCD_WP_OPTION_KEY_API_TOKEN' ) ) {
			define( 'WCD_WP_OPTION_KEY_API_TOKEN', 'webchangedetector_api_token' );
		}
		if ( ! defined( 'WCD_WP_OPTION_KEY_HEALTH_STATUS' ) ) {
			define( 'WCD_WP_OPTION_KEY_HEALTH_STATUS', 'webchangedetector_health_status' );
		}
	}

	/**
	 * Clear all known WordPress cache plugins and systems.
	 *
	 * This method clears caches from various popular caching plugins and systems
	 * to ensure fresh screenshots are taken during the auto-update process.
	 *
	 * @return void
	 */
	private function clear_wordpress_caches() {
		\WebChangeDetector\WebChangeDetector_Admin_Utils::log_error( 'Clearing all WordPress caches before taking screenshots.', 'clear_wordpress_caches', 'debug' );

		$cleared_caches = array();
		$failed_caches  = array();

		// WP Rocket
		try {
			if ( function_exists( '\rocket_clean_domain' ) ) {
				rocket_clean_domain();
				if ( function_exists( '\rocket_clean_minify' ) ) {
					rocket_clean_minify();
				}
				$cleared_caches[] = 'WP Rocket';
			}
		} catch ( \Exception $e ) {
			$failed_caches[] = 'WP Rocket: ' . $e->getMessage();
		}

		// W3 Total Cache
		try {
			if ( function_exists( '\w3tc_flush_all' ) ) {
				w3tc_flush_all();
				$cleared_caches[] = 'W3 Total Cache';
			}
		} catch ( \Exception $e ) {
			$failed_caches[] = 'W3 Total Cache: ' . $e->getMessage();
		}

		// LiteSpeed Cache
		try {
			if ( defined( 'LSCWP_VERSION' ) ) {
				do_action( 'litespeed_purge_all' );
				do_action( 'litespeed_purge_cssjs' );
				do_action( 'litespeed_purge_object' );
				$cleared_caches[] = 'LiteSpeed Cache';
			}
			if ( class_exists( '\LiteSpeed_Cache_API' ) && method_exists( '\LiteSpeed_Cache_API', 'purge_all' ) ) {
				\LiteSpeed_Cache_API::purge_all();
				if ( ! in_array( 'LiteSpeed Cache', $cleared_caches, true ) ) {
					$cleared_caches[] = 'LiteSpeed Cache';
				}
			}
		} catch ( \Exception $e ) {
			$failed_caches[] = 'LiteSpeed Cache: ' . $e->getMessage();
		}

		// WP Super Cache
		try {
			if ( function_exists( '\wp_cache_clear_cache' ) ) {
				@wp_cache_clear_cache( true );
				$cleared_caches[] = 'WP Super Cache';
			} elseif ( function_exists( '\wp_cache_post_change' ) ) {
				@wp_cache_post_change( '' );
				$cleared_caches[] = 'WP Super Cache';
			}
		} catch ( \Exception $e ) {
			$failed_caches[] = 'WP Super Cache: ' . $e->getMessage();
		}

		// WP Fastest Cache
		try {
			if ( function_exists( '\wpfc_clear_all_cache' ) ) {
				wpfc_clear_all_cache( true );
				$cleared_caches[] = 'WP Fastest Cache';
			}
		} catch ( \Exception $e ) {
			$failed_caches[] = 'WP Fastest Cache: ' . $e->getMessage();
		}

		// Cache Enabler
		try {
			if ( class_exists( '\Cache_Enabler' ) && method_exists( '\Cache_Enabler', 'clear_total_cache' ) ) {
				\Cache_Enabler::clear_total_cache();
				$cleared_caches[] = 'Cache Enabler';
			}
			// New Cache Enabler (v1.5.0+)
			if ( class_exists( '\Cache_Enabler_Engine' ) && method_exists( '\Cache_Enabler_Engine', 'clear_cache' ) ) {
				\Cache_Enabler_Engine::clear_cache();
				if ( ! in_array( 'Cache Enabler', $cleared_caches, true ) ) {
					$cleared_caches[] = 'Cache Enabler';
				}
			}
		} catch ( \Exception $e ) {
			$failed_caches[] = 'Cache Enabler: ' . $e->getMessage();
		}

		// Comet Cache
		try {
			if ( class_exists( '\comet_cache' ) && method_exists( '\comet_cache', 'clear' ) ) {
				\comet_cache::clear();
				$cleared_caches[] = 'Comet Cache';
			}
		} catch ( \Exception $e ) {
			$failed_caches[] = 'Comet Cache: ' . $e->getMessage();
		}

		// Swift Performance
		try {
			if ( class_exists( '\Swift_Performance_Cache' ) && method_exists( '\Swift_Performance_Cache', 'clear_all_cache' ) ) {
				\Swift_Performance_Cache::clear_all_cache();
				$cleared_caches[] = 'Swift Performance';
			}
		} catch ( \Exception $e ) {
			$failed_caches[] = 'Swift Performance: ' . $e->getMessage();
		}

		// Borlabs Cache
		try {
			if ( function_exists( '\borlabsCacheClearCache' ) ) {
				borlabsCacheClearCache();
				$cleared_caches[] = 'Borlabs Cache';
			}
			if ( has_action( 'borlabsCookie/thirdPartyCacheClearer/shouldClearCache' ) ) {
				do_action( 'borlabsCookie/thirdPartyCacheClearer/shouldClearCache', true );
			}
		} catch ( \Exception $e ) {
			$failed_caches[] = 'Borlabs Cache: ' . $e->getMessage();
		}

		// NitroPack
		try {
			if ( function_exists( '\nitropack_reset_cache' ) ) {
				nitropack_reset_cache();
				$cleared_caches[] = 'NitroPack';
			} elseif ( function_exists( '\nitropack_purge_cache' ) ) {
				nitropack_purge_cache();
				$cleared_caches[] = 'NitroPack';
			}
		} catch ( \Exception $e ) {
			$failed_caches[] = 'NitroPack: ' . $e->getMessage();
		}

		// Redis Object Cache
		try {
			global $wp_object_cache;
			if ( $wp_object_cache && method_exists( $wp_object_cache, 'flush' ) ) {
				$wp_object_cache->flush();
				$cleared_caches[] = 'Redis Object Cache';
			}
		} catch ( \Exception $e ) {
			$failed_caches[] = 'Redis Object Cache: ' . $e->getMessage();
		}

		// Object Cache Pro
		try {
			if ( class_exists( '\Object_Cache_Pro' ) ) {
				global $wp_object_cache;
				if ( method_exists( $wp_object_cache, 'flushRuntime' ) ) {
					$wp_object_cache->flushRuntime();
				}
				if ( method_exists( $wp_object_cache, 'flushBlog' ) ) {
					$wp_object_cache->flushBlog();
				}
				$cleared_caches[] = 'Object Cache Pro';
			}
		} catch ( \Exception $e ) {
			$failed_caches[] = 'Object Cache Pro: ' . $e->getMessage();
		}

		// SG Optimizer
		try {
			if ( function_exists( '\sg_cachepress_purge_cache' ) ) {
				sg_cachepress_purge_cache();
				$cleared_caches[] = 'SG Optimizer';
			}
			if ( has_action( 'siteground_optimizer_flush_cache' ) ) {
				do_action( 'siteground_optimizer_flush_cache' );
				if ( ! in_array( 'SG Optimizer', $cleared_caches, true ) ) {
					$cleared_caches[] = 'SG Optimizer';
				}
			}
		} catch ( \Exception $e ) {
			$failed_caches[] = 'SG Optimizer: ' . $e->getMessage();
		}

		// WP-Optimize
		try {
			if ( function_exists( '\wpo_cache_flush' ) ) {
				wpo_cache_flush();
				$cleared_caches[] = 'WP-Optimize';
			}
		} catch ( \Exception $e ) {
			$failed_caches[] = 'WP-Optimize: ' . $e->getMessage();
		}

		// Autoptimize
		try {
			if ( class_exists( '\autoptimizeCache' ) && method_exists( '\autoptimizeCache', 'clearall' ) ) {
				\autoptimizeCache::clearall();
				$cleared_caches[] = 'Autoptimize';
			}
		} catch ( \Exception $e ) {
			$failed_caches[] = 'Autoptimize: ' . $e->getMessage();
		}

		// Hummingbird
		try {
			if ( did_action( 'plugins_loaded' ) ) {
				do_action( 'wphb_clear_page_cache' );
				$cleared_caches[] = 'Hummingbird';
			}
		} catch ( \Exception $e ) {
			$failed_caches[] = 'Hummingbird: ' . $e->getMessage();
		}

		// Breeze (Cloudways)
		try {
			do_action( 'breeze_clear_all_cache' );
			$cleared_caches[] = 'Breeze';
		} catch ( \Exception $e ) {
			$failed_caches[] = 'Breeze: ' . $e->getMessage();
		}

		// Kinsta Cache
		try {
			if ( class_exists( '\Kinsta\Cache' ) && ! empty( $kinsta_cache ) ) {
				$kinsta_cache->kinsta_cache_purge->purge_complete_caches();
				$cleared_caches[] = 'Kinsta Cache';
			}
		} catch ( \Exception $e ) {
			$failed_caches[] = 'Kinsta Cache: ' . $e->getMessage();
		}

		// Pagely Cache
		try {
			if ( class_exists( '\PagelyCachePurge' ) && method_exists( '\PagelyCachePurge', 'purgeAll' ) ) {
				\PagelyCachePurge::purgeAll();
				$cleared_caches[] = 'Pagely Cache';
			}
		} catch ( \Exception $e ) {
			$failed_caches[] = 'Pagely Cache: ' . $e->getMessage();
		}

		// WP Engine System
		try {
			if ( class_exists( '\WpeCommon' ) && method_exists( '\WpeCommon', 'purge_memcached' ) ) {
				\WpeCommon::purge_memcached();
				$cleared_caches[] = 'WP Engine Memcached';
			}
			if ( class_exists( '\WpeCommon' ) && method_exists( '\WpeCommon', 'purge_varnish_cache' ) ) {
				\WpeCommon::purge_varnish_cache();
				$cleared_caches[] = 'WP Engine Varnish';
			}
		} catch ( \Exception $e ) {
			$failed_caches[] = 'WP Engine: ' . $e->getMessage();
		}

		// Cloudflare
		try {
			if ( class_exists( '\CF\WordPress\Hooks' ) ) {
				$cloudflare = new \CF\WordPress\Hooks();
				if ( method_exists( $cloudflare, 'purgeCacheEverything' ) ) {
					$cloudflare->purgeCacheEverything();
					$cleared_caches[] = 'Cloudflare';
				}
			}
		} catch ( \Exception $e ) {
			$failed_caches[] = 'Cloudflare: ' . $e->getMessage();
		}

		// Flying Press
		try {
			if ( class_exists( '\FlyingPress' ) && method_exists( '\FlyingPress', 'purge_cached_pages' ) ) {
				\FlyingPress::purge_cached_pages();
				$cleared_caches[] = 'Flying Press';
			}
		} catch ( \Exception $e ) {
			$failed_caches[] = 'Flying Press: ' . $e->getMessage();
		}

		// WP Cloudflare Super Page Cache
		try {
			if ( class_exists( '\SW_CLOUDFLARE_PAGECACHE' ) && method_exists( '\SW_CLOUDFLARE_PAGECACHE', 'cloudflare_purge_cache' ) ) {
				$cf_cache = new \SW_CLOUDFLARE_PAGECACHE();
				$cf_cache->cloudflare_purge_cache();
				$cleared_caches[] = 'WP Cloudflare Super Page Cache';
			}
		} catch ( \Exception $e ) {
			$failed_caches[] = 'WP Cloudflare Super Page Cache: ' . $e->getMessage();
		}

		// Perfmatters
		try {
			if ( function_exists( '\perfmatters_clear_page_cache' ) ) {
				perfmatters_clear_page_cache();
				$cleared_caches[] = 'Perfmatters';
			}
		} catch ( \Exception $e ) {
			$failed_caches[] = 'Perfmatters: ' . $e->getMessage();
		}

		// WP-Rocket Cloudflare Add-on
		try {
			if ( function_exists( '\rocket_cloudflare_purge_cache' ) ) {
				rocket_cloudflare_purge_cache();
				$cleared_caches[] = 'WP-Rocket Cloudflare Add-on';
			}
		} catch ( \Exception $e ) {
			$failed_caches[] = 'WP-Rocket Cloudflare Add-on: ' . $e->getMessage();
		}

		// WordPress Core Object Cache
		try {
			if ( function_exists( '\wp_cache_flush' ) ) {
				wp_cache_flush();
				$cleared_caches[] = 'WordPress Core Object Cache';
			}
		} catch ( \Exception $e ) {
			$failed_caches[] = 'WordPress Core Object Cache: ' . $e->getMessage();
		}

		// WordPress Transients
		try {
			if ( function_exists( '\wc_delete_product_transients' ) ) {
				wc_delete_product_transients();
				$cleared_caches[] = 'WooCommerce Transients';
			}
			if ( function_exists( '\delete_expired_transients' ) ) {
				delete_expired_transients( true );
				$cleared_caches[] = 'Expired Transients';
			}
		} catch ( \Exception $e ) {
			$failed_caches[] = 'WordPress Transients: ' . $e->getMessage();
		}

		// Log summary
		if ( ! empty( $cleared_caches ) ) {
			\WebChangeDetector\WebChangeDetector_Admin_Utils::log_error( 'Successfully cleared caches: ' . implode( ', ', $cleared_caches ), 'clear_wordpress_caches', 'debug' );
		}
		if ( ! empty( $failed_caches ) ) {
			\WebChangeDetector\WebChangeDetector_Admin_Utils::log_error( 'Failed to clear some caches: ' . implode( '; ', $failed_caches ), 'clear_wordpress_caches', 'debug' );
		}
		if ( empty( $cleared_caches ) && empty( $failed_caches ) ) {
			\WebChangeDetector\WebChangeDetector_Admin_Utils::log_error( 'No cache plugins detected or cleared.', 'clear_wordpress_caches', 'debug' );
		}
	}
}
