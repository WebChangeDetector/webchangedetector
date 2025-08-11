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

	/** This just calls the version check from a backup cron.
	 * We need to be careful not to interfere with an already running update process.
	 *
	 * @return void
	 */
	public function wcd_wp_version_check() {
		// Check if updates are already in progress
		if ( get_option( WCD_AUTO_UPDATES_RUNNING ) ) {
			\WebChangeDetector\WebChangeDetector_Admin_Utils::log_error(
				'Skipping backup version check - auto updates already running',
				'wcd_wp_version_check',
				'debug'
			);
			return;
		}

		// Check if pre-update screenshots are in progress
		$pre_update_data = get_option( WCD_PRE_AUTO_UPDATE );
		if ( $pre_update_data && isset( $pre_update_data['status'] ) && $pre_update_data['status'] === 'processing' ) {
			\WebChangeDetector\WebChangeDetector_Admin_Utils::log_error(
				'Skipping backup version check - pre-update screenshots in progress',
				'wcd_wp_version_check',
				'debug'
			);
			return;
		}

		// Check if post-update screenshots are in progress
		if ( get_option( WCD_POST_AUTO_UPDATE ) ) {
			\WebChangeDetector\WebChangeDetector_Admin_Utils::log_error(
				'Skipping backup version check - post-update screenshots in progress',
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

		// Set transient for automatic timeout (2 hours) - hybrid approach
		set_transient( 'wcd_post_auto_update_timeout', time(), 2 * HOUR_IN_SECONDS );

		// IMPORTANT: Schedule the cron to check post-update queue status
		// This was missing and causing the process to get stuck!
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
		$timeout_active = get_transient( 'wcd_post_auto_update_timeout' );

		// Check for expired state using hybrid approach
		if ( $post_sc_option && ! $timeout_active ) {
			// State exists but timeout expired - automatic recovery
			\WebChangeDetector\WebChangeDetector_Admin_Utils::log_error(
				'Post-update state found but timeout expired. Initiating automatic recovery.',
				'wcd_cron_check_post_queues',
				'warning'
			);
			delete_option( WCD_POST_AUTO_UPDATE );
			delete_option( WCD_WORDPRESS_CRON );
			$post_sc_option = false;
		} elseif ( $post_sc_option && isset( $post_sc_option['timestamp'] ) ) {
			// Fallback: timestamp-based check for compatibility
			$age_in_seconds = time() - $post_sc_option['timestamp'];
			if ( $age_in_seconds > 7200 ) { // 2 hour fallback timeout
				\WebChangeDetector\WebChangeDetector_Admin_Utils::log_error(
					'Found stuck post-update process from ' . $age_in_seconds . ' seconds ago (fallback timeout). Cleaning up.',
					'wcd_cron_check_post_queues',
					'warning'
				);
				delete_option( WCD_POST_AUTO_UPDATE );
				delete_option( WCD_WORDPRESS_CRON );
				delete_transient( 'wcd_post_auto_update_timeout' );
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
			delete_transient( 'wcd_post_auto_update_timeout' );
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

			// Cleanup wp_options, transients, and cron webhook.
			delete_option( WCD_WORDPRESS_CRON );
			delete_option( WCD_PRE_AUTO_UPDATE );
			delete_option( WCD_POST_AUTO_UPDATE );
			delete_option( WCD_AUTO_UPDATES_RUNNING );

			// Clean up hybrid timeout transients
			delete_transient( 'wcd_pre_auto_update_timeout' );
			delete_transient( 'wcd_post_auto_update_timeout' );
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

		// Clean up hybrid timeout transients
		delete_transient( 'wcd_pre_auto_update_timeout' );
		delete_transient( 'wcd_post_auto_update_timeout' );

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
		$should_next_run_gmt    = strtotime( $scheduled_datetime_utc . ' UTC' );

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

		// Backup cron in case something else changes the wp_version_check cron
		wp_clear_scheduled_hook( 'wcd_wp_version_check' );
		wp_schedule_event( $should_next_run_gmt, 'daily', 'wcd_wp_version_check' );
	}

	/** Starting the pre-update screenshots before auto-updates are started.
	 * We set the lock to delay WP from starting the auto updates.
	 * Auto updates are delayed when they are not in the selected timeframe.
	 *
	 * @return void
	 */
	public function wp_maybe_auto_update() {
		// Prevent concurrent executions using a transient lock
		$execution_lock = get_transient( 'wcd_update_check_running' );
		if ( $execution_lock && ( time() - $execution_lock ) < 30 ) {
			// Another instance is running (started less than 30 seconds ago)
			\WebChangeDetector\WebChangeDetector_Admin_Utils::log_error( 
				'Skipping - another update check is already running (started ' . ( time() - $execution_lock ) . ' seconds ago).', 
				'wp_maybe_auto_update', 
				'debug' 
			);
            // We don't need to set the lock here. We just need to return.
			return;
		}
		
		// Set our execution lock
		set_transient( 'wcd_update_check_running', time(), 60 );

		// Check if the auto updates are already started. Then we skip the auto updates.
		$auto_updates_running = get_option( WCD_AUTO_UPDATES_RUNNING );
		if ( $auto_updates_running ) {
			// Check if this is a stuck flag (WordPress updates shouldn't take more than 1 hour)
			// WCD_AUTO_UPDATES_RUNNING is set to true (boolean), but we can check when PRE_AUTO_UPDATE was set
			$pre_update_data = get_option( WCD_PRE_AUTO_UPDATE );
			if ( $pre_update_data && isset( $pre_update_data['timestamp'] ) ) {
				$age_in_seconds = time() - $pre_update_data['timestamp'];
				if ( $age_in_seconds > 3600 ) { // 1 hour timeout
					\WebChangeDetector\WebChangeDetector_Admin_Utils::log_error(
						'Auto updates flag is stuck (running for ' . $age_in_seconds . ' seconds). Clearing flag.',
						'wp_maybe_auto_update',
						'warning'
					);
					delete_option( WCD_AUTO_UPDATES_RUNNING );
					// Continue with normal flow
				} else {
					\WebChangeDetector\WebChangeDetector_Admin_Utils::log_error( 'Auto updates are already started. Skipping auto updates.', 'wp_maybe_auto_update', 'debug' );
					return;
				}
			} else {
				// No timestamp available, but flag is set - could be stuck from old version
				\WebChangeDetector\WebChangeDetector_Admin_Utils::log_error(
					'Auto updates flag found without timestamp context. Clearing potentially stuck flag.',
					'wp_maybe_auto_update',
					'warning'
				);
				delete_option( WCD_AUTO_UPDATES_RUNNING );
				// Continue with normal flow
			}
		}

		// Check if post-update screenshots are already done. Then we skip the auto updates.
		$post_update_data    = get_option( WCD_POST_AUTO_UPDATE );
		$post_timeout_active = get_transient( 'wcd_post_auto_update_timeout' );

		if ( $post_update_data && ! $post_timeout_active ) {
			// State exists but timeout expired - automatic recovery
			\WebChangeDetector\WebChangeDetector_Admin_Utils::log_error(
				'Post-update state found but timeout expired. Cleaning up and continuing.',
				'wp_maybe_auto_update',
				'warning'
			);
			delete_option( WCD_POST_AUTO_UPDATE );
			delete_option( WCD_WORDPRESS_CRON );
		} elseif ( $post_update_data && isset( $post_update_data['timestamp'] ) ) {
			// Check if this is recent enough to still be valid
			$age_in_seconds = time() - $post_update_data['timestamp'];
			if ( $age_in_seconds > 7200 ) { // 2 hour fallback timeout
				\WebChangeDetector\WebChangeDetector_Admin_Utils::log_error(
					'Found stuck post-update process from ' . $age_in_seconds . ' seconds ago (fallback timeout). Cleaning up and continuing.',
					'wp_maybe_auto_update',
					'warning'
				);
				delete_option( WCD_POST_AUTO_UPDATE );
				delete_option( WCD_WORDPRESS_CRON );
				delete_transient( 'wcd_post_auto_update_timeout' );
			} else {
				\WebChangeDetector\WebChangeDetector_Admin_Utils::log_error( 'Post-update screenshots already processed. Skipping auto updates.', 'wp_maybe_auto_update', 'debug' );
				$this->set_lock();
				return;
			}
		} elseif ( $post_update_data && ! isset( $post_update_data['timestamp'] ) ) {
			// Old format without timestamp - assume it's stuck from a previous version
			\WebChangeDetector\WebChangeDetector_Admin_Utils::log_error(
				'Found old post-update process without timestamp (likely from version 3.x). Cleaning up and continuing.',
				'wp_maybe_auto_update',
				'warning'
			);
			delete_option( WCD_POST_AUTO_UPDATE );
			delete_option( WCD_WORDPRESS_CRON );
			delete_transient( 'wcd_post_auto_update_timeout' );
		}

		// Make auto-updates only once every 24h. Otherwise we skip the auto updates if debug logging is disabled.
		// Only once in 12 hours are auto updates allowed. We shouldn't do every 24 hours as the successful time is at the end of the auto updates.
		// And when we start the auto updates on the next day, 24 hours might not be over yet.
        $last_successfull_auto_updates = get_option( WCD_LAST_SUCCESSFULL_AUTO_UPDATES );
        if ( !get_option( WCD_WP_OPTION_KEY_DEBUG_LOGGING ) ) {    
		    if ( $last_successfull_auto_updates && $last_successfull_auto_updates + 12 * HOUR_IN_SECONDS > time() ) {
			// We already did auto-updates in the last 24h. Skipping this one now.
			\WebChangeDetector\WebChangeDetector_Admin_Utils::log_error( 'Auto updates already done at ' . gmdate( 'Y-m-d H:i:s', get_option( WCD_LAST_SUCCESSFULL_AUTO_UPDATES ) ) . '. We only do them once per day. Skipping auto updates.', 'wp_maybe_auto_update', 'debug' );
			$this->set_lock();
			return;
            }
		}

		// Check if lock is stuck (WordPress uses 1 hour as the lock timeout)
		$lock = get_option( $this->lock_name );
		if ( $lock && $lock < ( time() - HOUR_IN_SECONDS ) ) {
			\WebChangeDetector\WebChangeDetector_Admin_Utils::log_error(
				'Found stuck auto_updater.lock (age: ' . ( time() - $lock - HOUR_IN_SECONDS ) . ' seconds). Removing it.',
				'wp_maybe_auto_update',
				'warning'
			);
		}

		// Remove the lock to start the updates.
		delete_option( $this->lock_name );

		// Get the auto-update settings.
		$auto_update_settings = self::get_auto_update_settings();

		// We don't have auto-update settings yet or the manual checks group is not set. So, go the wp way.
		if ( ! $auto_update_settings || ! $this->manual_group_id ) {
			\WebChangeDetector\WebChangeDetector_Admin_Utils::log_error( 'Running auto updates without checks. Don\'t have an group_id or auto update settings. ', 'wp_maybe_auto_update', 'debug' );
			return;
		}

		// Check if our auto update checks are enabled.
		if (
			! array_key_exists( 'auto_update_checks_enabled', $auto_update_settings ) ||
			empty( $auto_update_settings['auto_update_checks_enabled'] )
		) {
			\WebChangeDetector\WebChangeDetector_Admin_Utils::log_error( 'Running auto updates without checks. They are disabled in WCD.', 'wp_maybe_auto_update', 'debug' );
			return;
		}

		// Check if we do updates on today's weekday.
		$todays_weekday = strtolower( current_time( 'l' ) );
		if (
			! array_key_exists( 'auto_update_checks_' . $todays_weekday, $auto_update_settings ) ||
			empty( $auto_update_settings[ 'auto_update_checks_' . $todays_weekday ] )
		) {
			\WebChangeDetector\WebChangeDetector_Admin_Utils::log_error( 'Canceling auto updates: ' . $todays_weekday . ' is disabled.', 'wp_maybe_auto_update', 'debug' );
			$this->set_lock();
			return;
		}

		// Get the current time in the same format (HH:MM).
		$current_time = current_time( 'H:i' );

		// Load timezone helper for time conversion.
		require_once WP_PLUGIN_DIR . '/webchangedetector/admin/class-webchangedetector-timezone-helper.php';

		// Convert UTC times from API to site timezone for comparison.
		$from_time_site = \WebChangeDetector\WebChangeDetector_Timezone_Helper::utc_to_site_time( $auto_update_settings['auto_update_checks_from'] );
		$to_time_site   = \WebChangeDetector\WebChangeDetector_Timezone_Helper::utc_to_site_time( $auto_update_settings['auto_update_checks_to'] );

		// Use WordPress timezone-aware datetime for accurate comparison
		$wp_timezone = wp_timezone();
		$now_wp = new \DateTime( 'now', $wp_timezone );
		
		// Create DateTime objects for from and to times in WordPress timezone
		$from_datetime = new \DateTime( $now_wp->format( 'Y-m-d' ) . ' ' . $from_time_site, $wp_timezone );
		$to_datetime   = new \DateTime( $now_wp->format( 'Y-m-d' ) . ' ' . $to_time_site, $wp_timezone );
		
		// Get timestamps for comparison (these will be UTC timestamps)
		$from_timestamp    = $from_datetime->getTimestamp();
		$to_timestamp      = $to_datetime->getTimestamp();
		$current_timestamp = $now_wp->getTimestamp();

		// Check if current time is between from_time and to_time.
		if ( $from_timestamp < $to_timestamp ) {
			// Case 1: Time range is on the same day.
			if ( $current_timestamp < $from_timestamp || $current_timestamp > $to_timestamp ) {
				\WebChangeDetector\WebChangeDetector_Admin_Utils::log_error(
					'Canceling auto updates: ' . current_time( 'H:i' ) .
						' is not between ' . $from_time_site .
						' and ' . $to_time_site .
						' (site timezone)',
					'wp_maybe_auto_update',
					'debug'
				);
				$this->set_lock();
				return;
			}
		} else {
			// Case 2: Time range spans midnight.
			$to_datetime->modify( '+1 day' );
			$to_timestamp = $to_datetime->getTimestamp();
			if ( ! ( $current_timestamp >= $from_timestamp || $current_timestamp <= $to_timestamp ) ) {
				\WebChangeDetector\WebChangeDetector_Admin_Utils::log_error(
					'Canceling auto updates: ' . current_time( 'H:i' ) .
						' is not between ' . $from_time_site .
						' and ' . $to_time_site .
						' (site timezone, spans midnight)',
					'wp_maybe_auto_update',
					'debug'
				);
				$this->set_lock();
				return;
			}
		}

		// Check if we are called from one of the known filters.
		if (
			! doing_filter( 'wp_maybe_auto_update' ) &&
			! doing_filter( 'jetpack_pre_plugin_upgrade' ) &&
			! doing_filter( 'jetpack_pre_theme_upgrade' ) &&
			! doing_filter( 'jetpack_pre_core_upgrade' )
		) {
			\WebChangeDetector\WebChangeDetector_Admin_Utils::log_error( 'Not called from one of the known filters. Continuing anyway.', 'wp_maybe_auto_update', 'debug' );
		}

		// Start pre-update screenshots and do the WCD Magic.
		// Use hybrid approach: option for state reliability + transient for timeout
		$wcd_pre_update_data = get_option( WCD_PRE_AUTO_UPDATE );
		$timeout_active      = get_transient( 'wcd_pre_auto_update_timeout' );

		// Check for expired state (hybrid timeout mechanism)
		if ( $wcd_pre_update_data && ! $timeout_active ) {
			// State exists but timeout expired - automatic recovery
			\WebChangeDetector\WebChangeDetector_Admin_Utils::log_error(
				'Pre-update state found but timeout expired. Initiating automatic recovery.',
				'wp_maybe_auto_update',
				'warning'
			);
			delete_option( WCD_PRE_AUTO_UPDATE );
			delete_option( WCD_AUTO_UPDATES_RUNNING );
			$wcd_pre_update_data = false;
		} elseif ( $wcd_pre_update_data && isset( $wcd_pre_update_data['timestamp'] ) ) {
			// Fallback: timestamp-based check for older implementation compatibility
			$age_in_seconds = time() - $wcd_pre_update_data['timestamp'];
			if ( $age_in_seconds > 7200 ) { // 2 hour fallback timeout
				\WebChangeDetector\WebChangeDetector_Admin_Utils::log_error(
					'Found stuck pre-update process from ' . $age_in_seconds . ' seconds ago (fallback timeout). Cleaning up.',
					'wp_maybe_auto_update',
					'warning'
				);
				delete_option( WCD_PRE_AUTO_UPDATE );
				delete_option( WCD_AUTO_UPDATES_RUNNING );
				delete_transient( 'wcd_pre_auto_update_timeout' );
				$wcd_pre_update_data = false;
			}
		} elseif ( $wcd_pre_update_data && ! isset( $wcd_pre_update_data['timestamp'] ) ) {
			// Old format without timestamp - assume it's stuck from a previous version
			\WebChangeDetector\WebChangeDetector_Admin_Utils::log_error(
				'Found old pre-update process without timestamp (likely from version 3.x). Cleaning up.',
				'wp_maybe_auto_update',
				'warning'
			);
			delete_option( WCD_PRE_AUTO_UPDATE );
			delete_option( WCD_AUTO_UPDATES_RUNNING );
			delete_transient( 'wcd_pre_auto_update_timeout' );
			$wcd_pre_update_data = false;
		}

		if ( false === $wcd_pre_update_data ) { // We don't have an option yet. So we start screenshots.

			// Create scheduled wp_maybe_auto_update check and external cron at wcd api to make sure the scheduler is triggered every minute.
			$this->reschedule( 'wp_maybe_auto_update' );

			// Clear all caches before taking pre-update screenshots.
			$this->clear_wordpress_caches();

			// Take the screenshots and set the status to processing.
			try {
				$sc_response = \WebChangeDetector\WebChangeDetector_API_V2::take_screenshot_v2( $this->manual_group_id, 'pre' );
				\WebChangeDetector\WebChangeDetector_Admin_Utils::log_error( 'Pre update SC data: ' . wp_json_encode( $sc_response ), 'wp_maybe_auto_update', 'debug' );

				// Validate response structure
				if ( empty( $sc_response ) || ! isset( $sc_response['batch'] ) ) {
					throw new \Exception( 'Invalid API response: missing batch ID' );
				}

				$option_data = array(
					'status'    => 'processing',
					'batch_id'  => esc_html( $sc_response['batch'] ),
					'timestamp' => time(), // Add timestamp for timeout detection
				);
			} catch ( \Exception $e ) {
				\WebChangeDetector\WebChangeDetector_Admin_Utils::log_error(
					'Failed to start pre-update screenshots: ' . $e->getMessage(),
					'wp_maybe_auto_update',
					'error'
				);
				// Clean up and allow retry on next run
				delete_option( WCD_AUTO_UPDATES_RUNNING );
				return;
			}

			// Save the data using hybrid approach: option + transient
			\WebChangeDetector\WebChangeDetector_Admin_Utils::log_error( 'Started taking screenshots and setting options with hybrid timeout', 'wp_maybe_auto_update', 'debug' );
			update_option( WCD_PRE_AUTO_UPDATE, $option_data, false );

			// Set transient for automatic timeout (2 hours)
			set_transient( 'wcd_pre_auto_update_timeout', time(), 2 * HOUR_IN_SECONDS );

			// Set the lock to prevent WP from starting the auto updates.
			$this->set_lock();
		} else {
			// Screenshots were already started. Now we check if they are done.
			\WebChangeDetector\WebChangeDetector_Admin_Utils::log_error( 'Checking if screenshots are ready' );

			try {
				$response = \WebChangeDetector\WebChangeDetector_API_V2::get_queues_v2( $wcd_pre_update_data['batch_id'], 'open,processing' );

				// Validate response structure
				if ( ! is_array( $response ) || ! isset( $response['data'] ) ) {
					throw new \Exception( 'Invalid queue response structure' );
				}

				\WebChangeDetector\WebChangeDetector_Admin_Utils::log_error( 'Queue: ' . wp_json_encode( $response ), 'wp_maybe_auto_update', 'debug' );
			} catch ( \Exception $e ) {
				\WebChangeDetector\WebChangeDetector_Admin_Utils::log_error(
					'Failed to check queue status: ' . $e->getMessage() . '. Will retry on next run.',
					'wp_maybe_auto_update',
					'warning'
				);
				// Don't update status, keep lock, retry on next cron
				$this->reschedule( 'wp_maybe_auto_update' );
				$this->set_lock();
				return;
			}

			// We check if the queues are done. If so, we update the status.
			if ( count( $response['data'] ) === 0 ) {
				$wcd_pre_update_data['status'] = 'done';
				// Preserve timestamp for timeout detection
				if ( ! isset( $wcd_pre_update_data['timestamp'] ) ) {
					$wcd_pre_update_data['timestamp'] = time();
				}
				update_option( WCD_PRE_AUTO_UPDATE, $wcd_pre_update_data, false );

				// Extend timeout slightly for final processing (30 minutes)
				set_transient( 'wcd_pre_auto_update_timeout', time(), 30 * MINUTE_IN_SECONDS );
			}

			// If the queues are not done yet, we set the lock. So the WP auto updates are delayed.
			if ( 'done' !== $wcd_pre_update_data['status'] ) {
				\WebChangeDetector\WebChangeDetector_Admin_Utils::log_error( 'SCs are not ready yet. Waiting for next cron run.', 'wp_maybe_auto_update', 'debug' );
				$this->reschedule( 'wp_maybe_auto_update' );
				$this->set_lock();
			} else {
				\WebChangeDetector\WebChangeDetector_Admin_Utils::log_error( 'SCs are ready. Continuing with the updates.', 'wp_maybe_auto_update', 'debug' );
				update_option( WCD_AUTO_UPDATES_RUNNING, true );

				// IMPORTANT: Remove the lock so WordPress can actually run the updates!
				delete_option( $this->lock_name );
				\WebChangeDetector\WebChangeDetector_Admin_Utils::log_error( 'Removed auto_updater.lock to allow WordPress to proceed with updates.', 'wp_maybe_auto_update', 'debug' );

                // Remove cached update data.
                delete_option( '_site_transient_update_core' );
                delete_option( '_site_transient_update_plugins' );
                delete_option( '_site_transient_update_themes' );

				// We need to trigger WordPress to actually run the updates.
				// Since we're currently in the wp_maybe_auto_update action at priority 5,
				// WordPress's update logic (usually at priority 10) has already been blocked by our lock.
				// We must trigger the update process directly.
				
				// Check if WordPress is in the middle of an install
				if ( ! wp_installing() ) {
                    // We can just return here. Then the wp hook of wp_maybe_auto_update will be triggered.
                    \WebChangeDetector\WebChangeDetector_Admin_Utils::log_error( 'WordPress is not installing. Returning to trigger the wp hook of wp_maybe_auto_update.', 'wp_maybe_auto_update', 'debug' );
					
				} else {
					\WebChangeDetector\WebChangeDetector_Admin_Utils::log_error( 'Cannot run updates: WordPress is currently installing.', 'wp_maybe_auto_update', 'debug' );
					
					// Clean up our state since updates won't run
					delete_option( WCD_AUTO_UPDATES_RUNNING );
				}
				
				
			}
		}
		
		// Clear execution lock at the end if we haven't already
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
		$how_long = 60; // 60 seconds.
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
