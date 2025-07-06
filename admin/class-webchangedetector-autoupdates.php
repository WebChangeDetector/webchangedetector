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

		$wcd_groups = get_option( WCD_WEBSITE_GROUPS );
		if ( ! $wcd_groups ) {
			return;
		}
		$this->manual_group_id     = $wcd_groups[ WCD_MANUAL_DETECTION_GROUP ] ?? false;
		$this->monitoring_group_id = $wcd_groups[ WCD_AUTO_DETECTION_GROUP ] ?? false;
	}

	/** This just calls the version check from a backup cron.
	 *
	 * @return void
	 */
	public function wcd_wp_version_check() {
		wp_version_check();
	}


	/**
	 * Fires when wp auto updates are done.
	 *
	 * @return void
	 */
	public function automatic_updates_complete() {
		\WebChangeDetector\WebChangeDetector_Admin_Utils::log_error( 'Automatic Updates Complete. Running post-update stuff.' );

		// Auto updates are done. So we remove the option.
		delete_option( WCD_AUTO_UPDATES_RUNNING );

		// We don't do anything here if wcd checks are disabled, or we don't have pre_auto_update option.
		$auto_update_settings = self::get_auto_update_settings();
		if ( ! array_key_exists( 'auto_update_checks_enabled', $auto_update_settings ) || ! get_option( WCD_PRE_AUTO_UPDATE ) ) {
			\WebChangeDetector\WebChangeDetector_Admin_Utils::log_error( 'Skipping after update stuff as checks are disabled or we don\'t have pre-update checks.' );
			return;
		}

		// Start the post-update screenshots.
		\WebChangeDetector\WebChangeDetector_Admin_Utils::log_error( 'Starting post-update screenshots and comparisons.' );
		$response = \WebChangeDetector\WebChangeDetector_API_V2::take_screenshot_v2( $this->manual_group_id, 'post' );
		\WebChangeDetector\WebChangeDetector_Admin_Utils::log_error( 'Post-Screenshot Response: ' . wp_json_encode( $response ) );
		update_option(
			WCD_POST_AUTO_UPDATE,
			array(
				'status'   => 'processing',
				'batch_id' => $response['batch'],
			),
			false
		);

		// Add the batch id to the comparison batches. This is used to send the mail and for showing "Auto Update Checks" in the change detection page.
		$comparison_batches = get_option( WCD_AUTO_UPDATE_COMPARISON_BATCHES );
		if ( ! $comparison_batches ) {
			$comparison_batches = array();
		}
		$comparison_batches[] = $response['batch'];
		update_option( WCD_AUTO_UPDATE_COMPARISON_BATCHES, $comparison_batches );
		\WebChangeDetector\WebChangeDetector_API_V2::update_batch_v2( $response['batch'], 'Auto Update Checks - ' . WebChangeDetector_Admin::get_domain_from_site_url() );

		$this->wcd_cron_check_post_queues();
	}

	/**
	 * Cron for checking post_sc to be finished
	 *
	 * @return void
	 */
	public function wcd_cron_check_post_queues() {
		$post_sc_option = get_option( WCD_POST_AUTO_UPDATE );

		// Check if we still have the post_sc_option. If not, we already sent the mail.
		if ( ! $post_sc_option ) {
			\WebChangeDetector\WebChangeDetector_Admin_Utils::log_error( 'No post_sc_option found. So we already sent the mail.' );
			return;
		}
		\WebChangeDetector\WebChangeDetector_Admin_Utils::log_error( 'Checking if post-update screenshots are done: ' . wp_json_encode( $post_sc_option ) );
		$response = \WebChangeDetector\WebChangeDetector_API_V2::get_queues_v2( $post_sc_option['batch_id'], 'open,processing' );
		\WebChangeDetector\WebChangeDetector_Admin_Utils::log_error( 'Response: ' . wp_json_encode( $response ) );

		// Check if the batch is done.
		if ( count( $response['data'] ) > 0 ) {
			// There are still open or processing queues. So we check again in a minute.
			\WebChangeDetector\WebChangeDetector_Admin_Utils::log_error( 'There are still open or processing queues. So we check again in a minute.' );
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
		}
	}

	/**
	 * Set lock to prevent wp from updating
	 *
	 * @return void
	 */
	public function set_lock() {
		\WebChangeDetector\WebChangeDetector_Admin_Utils::log_error( 'Setting Lock' );
		update_option( $this->lock_name, time() - HOUR_IN_SECONDS + MINUTE_IN_SECONDS );
	}

	/** Reset next cron run of wp_version_check to our auto_update_checks_from.
	 *
	 * @param array $group_settings Array of group settings.
	 * @return void
	 */
	public function wcd_save_update_group_settings( $group_settings ) {
		// Get the new time in local time zone.
		if ( isset( $group_settings['auto_update_checks_from'] ) ) {
			$auto_update_checks_from = $group_settings['auto_update_checks_from'];
		} else {
			$auto_update_settings = self::get_auto_update_settings();
			if ( ! $auto_update_settings ) {
				return;
			}
			$auto_update_checks_from = $auto_update_settings['auto_update_checks_from'];
		}

		// Convert the local time into gmt time.
		$should_next_run     = gmdate( 'U', strtotime( $auto_update_checks_from ) );
		$should_next_run_gmt = get_gmt_from_date( gmdate( 'Y-m-d H:i:s', $should_next_run ), 'U' );

		$now_gmt = get_gmt_from_date( current_time( 'Y-m-d H:i:s' ), 'U' );

		// Add a day if we passed the auto_update_checks_from time already.
		if ( $now_gmt > $should_next_run_gmt ) {
			$should_next_run_gmt = strtotime( '+1 day', $should_next_run_gmt );
		}

		// Reschedule the wp_version_check cron to our "from" time.
		wp_clear_scheduled_hook( 'wp_version_check' );
		wp_schedule_event( $should_next_run_gmt, 'twicedaily', 'wp_version_check' );

		// Backup cron in case something else changes the wp_version_check cron.
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

		// Check if the auto updates are already started. Then we skip the auto updates.
		if ( get_option( WCD_AUTO_UPDATES_RUNNING ) ) {
			\WebChangeDetector\WebChangeDetector_Admin_Utils::log_error( 'Auto updates are already started. Skipping auto updates.' );
			return;
		}

		// Check if post-update screenshots are already done. Then we skip the auto updates.
		if ( get_option( WCD_POST_AUTO_UPDATE ) ) {
			\WebChangeDetector\WebChangeDetector_Admin_Utils::log_error( 'Post-update screenshots already processed. Skipping auto updates.' );
			$this->set_lock();
			return;
		}

		// Make auto-updates only once every 24h. Otherwise we skip the auto updates.
		$last_successfull_auto_updates = get_option( WCD_LAST_SUCCESSFULL_AUTO_UPDATES );
		// Only once in 12 hours are auto updates allowed. We shouldn't do every 24 hours as the successful time is at the end of the auto updates.
		// And when we start the auto updates on the next day, 24 hours might not be over yet.
		if ( $last_successfull_auto_updates && $last_successfull_auto_updates + 12 * HOUR_IN_SECONDS > time() ) {
			// We already did auto-updates in the last 24h. Skipping this one now.
			\WebChangeDetector\WebChangeDetector_Admin_Utils::log_error( 'Auto updates already done at ' . gmdate( 'Y-m-d H:i:s', get_option( WCD_LAST_SUCCESSFULL_AUTO_UPDATES ) ) . '. We only do them once per day. Skipping auto updates.' );
			$this->set_lock();
			return;
		}

		// Remove the lock to start the updates.
		delete_option( $this->lock_name );

		// Get the auto-update settings.
		$auto_update_settings = self::get_auto_update_settings();

		// We don't have auto-update settings yet or the manual checks group is not set. So, go the wp way.
		if ( ! $auto_update_settings || ! $this->manual_group_id ) {
			\WebChangeDetector\WebChangeDetector_Admin_Utils::log_error( 'Running auto updates without checks. Don\'t have an group_id or auto update settings. ' );
			return;
		}

		// Check if our auto update checks are enabled.
		if (
			! array_key_exists( 'auto_update_checks_enabled', $auto_update_settings ) ||
			empty( $auto_update_settings['auto_update_checks_enabled'] )
		) {
			\WebChangeDetector\WebChangeDetector_Admin_Utils::log_error( 'Running auto updates without checks. They are disabled in WCD.' );
			return;
		}

		// Check if we do updates on today's weekday.
		$todays_weekday = strtolower( current_time( 'l' ) );
		if (
			! array_key_exists( 'auto_update_checks_' . $todays_weekday, $auto_update_settings ) ||
			empty( $auto_update_settings[ 'auto_update_checks_' . $todays_weekday ] )
		) {
			\WebChangeDetector\WebChangeDetector_Admin_Utils::log_error( 'Canceling auto updates: ' . $todays_weekday . ' is disabled.' );
			$this->set_lock();
			return;
		}

		// Get the current time in the same format (HH:MM).
		$current_time = current_time( 'H:i' );

		// Convert the times to timestamps for comparison.
		$from_timestamp    = strtotime( $auto_update_settings['auto_update_checks_from'] );
		$to_timestamp      = strtotime( $auto_update_settings['auto_update_checks_to'] );
		$current_timestamp = strtotime( $current_time );

		// Check if current time is between from_time and to_time.
		if ( $from_timestamp < $to_timestamp ) {
			// Case 1: Time range is on the same day.
			if ( $current_timestamp < $from_timestamp || $current_timestamp > $to_timestamp ) {
				\WebChangeDetector\WebChangeDetector_Admin_Utils::log_error(
					'Canceling auto updates: ' . current_time( 'H:i' ) .
						' is not between ' . $auto_update_settings['auto_update_checks_from'] .
						' and ' . $auto_update_settings['auto_update_checks_to']
				);
				$this->set_lock();
				return;
			}
		} else {
			// Case 2: Time range spans midnight.
			$to_timestamp = strtotime( $auto_update_settings['auto_update_checks_to'] . ' +1 day' );
			if ( ! ( $current_timestamp >= $from_timestamp || $current_timestamp <= $to_timestamp ) ) {
				\WebChangeDetector\WebChangeDetector_Admin_Utils::log_error(
					'Canceling auto updates: ' . current_time( 'H:i' ) .
						' is not between ' . $auto_update_settings['auto_update_checks_from'] .
						' and ' . $auto_update_settings['auto_update_checks_to']
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
			\WebChangeDetector\WebChangeDetector_Admin_Utils::log_error( 'Not called from one of the known filters. Continuing anyway.' );
		}

		// Start pre-update screenshots and do the WCD Magic.
		$wcd_pre_update_data = get_option( WCD_PRE_AUTO_UPDATE );
		if ( false === $wcd_pre_update_data ) { // We don't have an option yet. So we start screenshots.

			// Create scheduled wp_maybe_auto_update check and external cron at wcd api to make sure the scheduler is triggered every minute.
			$this->reschedule( 'wp_maybe_auto_update' );

			// Take the screenshots and set the status to processing.
			$sc_response = \WebChangeDetector\WebChangeDetector_API_V2::take_screenshot_v2( $this->manual_group_id, 'pre' );
			\WebChangeDetector\WebChangeDetector_Admin_Utils::log_error( 'Pre update SC data: ' . wp_json_encode( $sc_response ) );
			$option_data = array(
				'status'   => 'processing',
				'batch_id' => esc_html( $sc_response['batch'] ),
			);

			// Save the data to the option.
			\WebChangeDetector\WebChangeDetector_Admin_Utils::log_error( 'Started taking screenshots and setting options' );
			update_option( WCD_PRE_AUTO_UPDATE, $option_data, false );
			// Set the lock to prevent WP from starting the auto updates.
			$this->set_lock();
		} else {
			// Screenshots were already started. Now we check if they are done.
			\WebChangeDetector\WebChangeDetector_Admin_Utils::log_error( 'Checking if screenshots are ready' );
			$response = \WebChangeDetector\WebChangeDetector_API_V2::get_queues_v2( $wcd_pre_update_data['batch_id'], 'open,processing' );

			\WebChangeDetector\WebChangeDetector_Admin_Utils::log_error( 'Queue: ' . wp_json_encode( $response ) );
			// We check if the queues are done. If so, we update the status.
			if ( count( $response['data'] ) === 0 ) {
				$wcd_pre_update_data['status'] = 'done';
				update_option( WCD_PRE_AUTO_UPDATE, $wcd_pre_update_data, false );
			}

			// If the queues are not done yet, we set the lock. So the WP auto updates are delayed.
			if ( 'done' !== $wcd_pre_update_data['status'] ) {
				\WebChangeDetector\WebChangeDetector_Admin_Utils::log_error( 'SCs are not ready yet. Waiting for next cron run.' );
				$this->reschedule( 'wp_maybe_auto_update' );
				$this->set_lock();
			} else {
				\WebChangeDetector\WebChangeDetector_Admin_Utils::log_error( 'SCs are ready. Continuing with the updates.' );
				update_option( WCD_AUTO_UPDATES_RUNNING, true );
			}
		}
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
		\WebChangeDetector\WebChangeDetector_Admin_Utils::log_error( 'Sending Mail with differences' );
		wp_mail( $to, $subject, $mail_body, $headers );
	}

	/** Get the auto-update settings.
	 *
	 * @return false|mixed|null
	 */
	public static function get_auto_update_settings() {
		static $auto_update_settings;
		if ( $auto_update_settings ) {
			return $auto_update_settings;
		}

		$wcd                  = new WebChangeDetector_Admin();
		$auto_update_settings = $wcd->settings_handler->get_website_details()['auto_update_settings'];

		// Enable auto-update checks if the defines are set.
		if ( defined( 'WCD_AUTO_UPDATES_ENABLED' ) && true === WCD_AUTO_UPDATES_ENABLED ) {
			$auto_update_settings['auto_update_checks_enabled'] = true;
		}
		return $auto_update_settings;
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
			\WebChangeDetector\WebChangeDetector_Admin_Utils::log_error( 'We already have a webhook for this hook. Skipping...' );
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

		\WebChangeDetector\WebChangeDetector_Admin_Utils::log_error( 'Creating webhook to trigger ' . $hook );

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

		// Method 1: Check for key-based auth (new style).
		// phpcs:disable WordPress.Security.NonceVerification.Recommended
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
		} elseif ( ! get_transient( 'wcd_updated_legacy_cron' ) ) {
			// Method 2: Fallback for old webhooks - verify request comes from our API server.
			// phpcs:disable WordPress.Security.ValidatedSanitizedInput.InputNotValidated
			// We're validating the IP address against known API server IPs as a secondary authentication mechanism.

			// Get the remote host/IP.
			$remote_host = '';
			if ( isset( $_SERVER['REMOTE_ADDR'] ) ) {
				$remote_host = sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) );
			}
			\WebChangeDetector\WebChangeDetector_Admin_Utils::log_error( 'Remote host: ' . $remote_host );
			// Our API server IP.
			$api_server_ip    = '138.68.83.218';
			$api_server_ip_v6 = '2a03:b0c0:3:d0::f94:7001';

			if ( defined( 'WCD_API_SERVER_IP' ) && WCD_API_SERVER_IP ) {
				$api_server_ip = WCD_API_SERVER_IP;
			}
			if ( defined( 'WCD_API_SERVER_IP_V6' ) && WCD_API_SERVER_IP_V6 ) {
				$api_server_ip_v6 = WCD_API_SERVER_IP_V6;
			}

			// Check if request is from one of our API servers.
			if ( gethostbyname( $remote_host ) === gethostbyname( $api_server_ip ) || gethostbyname( $remote_host ) === gethostbyname( $api_server_ip_v6 ) ) {

				// Get the webhook ID from our local option.
				$webhook_id = get_option( WCD_WORDPRESS_CRON );

				// Check if we have a webhook ID.
				if ( $webhook_id ) {
					\WebChangeDetector\WebChangeDetector_Admin_Utils::log_error( 'Legacy webhook request validated by host: ' . $remote_host );

					// Create our external webhook url.
					$webhook_url = add_query_arg(
						array(
							'wcd_action' => WCD_TRIGGER_AUTO_UPDATE_CRON,
							'key'        => $this->get_or_create_webhook_key(),
						),
						site_url()
					);

					// Update the webhook.
					$result = \WebChangeDetector\WebChangeDetector_API_V2::update_webhook_v2( $webhook_id, $webhook_url );

					// Check if the webhook was updated successfully.
					if ( ! empty( $result['data'] ) ) {
						// We have a valid webhook, so we can authorize the request.
						$is_authorized = true;
						set_transient( 'wcd_updated_legacy_cron', true, 60 * 60 * 24 );
						\WebChangeDetector\WebChangeDetector_Admin_Utils::log_error( 'Updated webhook: ' . wp_json_encode( $result ) );
					} else {
						// We have an invalid webhook, so we can't authorize the request and delete our local webhook.
						\WebChangeDetector\WebChangeDetector_Admin_Utils::log_error( 'Failed to update webhook: ' . wp_json_encode( $result ) );
						delete_option( WCD_WORDPRESS_CRON );
					}
				}
			}
			// phpcs:enable WordPress.Security.ValidatedSanitizedInput.InputNotValidated
		}
		// phpcs:enable WordPress.Security.NonceVerification.Recommended

		if ( $is_authorized ) {
			\WebChangeDetector\WebChangeDetector_Admin_Utils::log_error( 'Processing authorized webhook trigger' );

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
}
