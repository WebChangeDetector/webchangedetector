<?php
/**
Title: WebChange Detector Auto Update Feature
Description: Check your website on auto updates visually and see what changed.
Version: 1.0
 *
 * @package    WebChangeDetector
 */

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

		// Post updates.
		add_action( 'wcd_cron_check_post_queues', array( $this, 'wcd_cron_check_post_queues' ), 10, 2 );

		// Saving settings.
		add_action( 'wcd_save_update_group_settings', array( $this, 'wcd_save_update_group_settings' ) );

		// Backup cron job for checking for updates.
		add_action( 'wcd_wp_version_check', array( $this, 'wcd_wp_version_check' ) );

		// Scheduled tasks checking for post-sc to be finished.
		add_action( 'wcd_wp_maybe_auto_update', array( $this, 'wcd_wp_maybe_auto_update' ) );

		// Hooking into the update process.
		add_action( 'wp_maybe_auto_update', array( $this, 'wp_maybe_auto_update' ), 5 );

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
		WebChangeDetector_Admin::error_log( 'Function: Automatic Updates Complete' );

		// We don't do anything here if wcd checks are disabled, or we don't have pre_auto_update option.
		$auto_update_settings = self::get_auto_update_settings();
		if ( ! array_key_exists( 'auto_update_checks_enabled', $auto_update_settings ) || ! get_transient( WCD_PRE_AUTO_UPDATE ) ) {
			WebChangeDetector_Admin::error_log( 'Skipping after update stuff as checks are disabled or we don\'t have pre-update checks.' );
			return;
		}

		// Start the post-update screenshots.
		WebChangeDetector_Admin::error_log( 'Updates complete. Starting post-update screenshots and comparisons.' );
		$response = WebChangeDetector_API_V2::take_screenshot_v2( $this->manual_group_id, 'post' );
		WebChangeDetector_Admin::error_log( 'Post-Screenshot Response: ' . wp_json_encode( $response ) );
		set_transient(
			WCD_POST_AUTO_UPDATE,
			array(
				'status'   => 'processing',
				'batch_id' => $response['batch'],
			),
			WCD_HOUR_IN_SECONDS
		);

		// Save the auto update batch id.
		$comparison_batches = get_option( WCD_AUTO_UPDATE_COMPARISON_BATCHES );
		if ( ! $comparison_batches ) {
			$comparison_batches = array();
		}
		$comparison_batches[] = $response['batch'];
		update_option( WCD_AUTO_UPDATE_COMPARISON_BATCHES, $comparison_batches );
		WebChangeDetector_API_V2::update_batch_v2( $response['batch'], 'Auto Update Checks' );

		$this->wcd_cron_check_post_queues();
	}

	/**
	 * Cron for checking post_sc to be finished
	 *
	 * @return void
	 */
	public function wcd_cron_check_post_queues() {
		$post_sc_option = get_transient( WCD_POST_AUTO_UPDATE );
		$response       = WebChangeDetector_API_V2::get_queue_v2( $post_sc_option['batch_id'], 'open,processing' );

		// Check if the batch is done.
		if ( count( $response['data'] ) > 0 ) {
			// There are still open or processing queues. So we check again in a minute.
			$this->reschedule( MINUTE_IN_SECONDS, 'wcd_cron_check_post_queues' );
		} else {
			$this->send_change_detection_mail( $post_sc_option );

			// We don't need the webhook anymore.
			WebChangeDetector_API_V2::delete_webhook_v2( get_option( WCD_WORDPRESS_CRON ) );

			// Cleanup wp_options and cron webhook.
			delete_option( WCD_WORDPRESS_CRON );
			delete_transient( WCD_PRE_AUTO_UPDATE );
			delete_transient( WCD_POST_AUTO_UPDATE );
		}
	}

	/**
	 * Proceed with wp auto updates when pre_sc are done
	 *
	 * @return void
	 */
	public function wcd_wp_maybe_auto_update() {

		WebChangeDetector_Admin::error_log( 'Checking if sc are ready' );
		$pre_sc_transient = get_transient( WCD_PRE_AUTO_UPDATE );
		$response         = WebChangeDetector_API_V2::get_queue_v2( $pre_sc_transient['batch_id'], 'open,processing' );

		WebChangeDetector_Admin::error_log( 'Queue: ' . wp_json_encode( $response ) );
		// If we don't have open or processing queues of the batch anymore, we can do auto-updates.
		if ( count( $response['data'] ) === 0 ) {
			$pre_sc_transient['status'] = 'done';
			set_transient( WCD_PRE_AUTO_UPDATE, $pre_sc_transient, WCD_HOUR_IN_SECONDS );
		}

		// If the queues are not done yet, we reschedule and exit.
		if ( 'done' !== $pre_sc_transient['status'] ) {
			WebChangeDetector_Admin::error_log( 'Rescheduling updates as sc are not ready yet.' );
			$this->reschedule( MINUTE_IN_SECONDS, 'wcd_wp_maybe_auto_update' );
			return;
		}

		// Remove the lock to start the updates.
		delete_option( $this->lock_name );

		// Actally start the auto-updates.
		wp_maybe_auto_update();
	}

	/**
	 * Set lock to prevent wp from updating
	 *
	 * @return void
	 */
	public function set_lock() {
		WebChangeDetector_Admin::error_log( 'Setting Lock' );
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
	 * Auto updates are delayed when they are not in the selected timeframe.
	 *
	 * @return void
	 */
	public function wp_maybe_auto_update() {

		// Register the complete hook.
		add_action( 'automatic_updates_complete', array( $this, 'automatic_updates_complete' ), 10, 1 );

		// Get the auto-update settings.
		$auto_update_settings = self::get_auto_update_settings();

		// We don't have auto-update settings yet or the manual checks group is not set. So, go the wp way.
		if ( ! $auto_update_settings || ! $this->manual_group_id ) {
			WebChangeDetector_Admin::error_log( 'Running auto updates without checks. Don\'t have an group_id or auto update settings. ' );
			return;
		}

		// Check if auto update checks are enabled.
		if ( ! array_key_exists( 'auto_update_checks_enabled', $auto_update_settings ) ||
			empty( $auto_update_settings['auto_update_checks_enabled'] ) ) {
			WebChangeDetector_Admin::error_log( 'Running auto updates without checks. They are disabled in WCD.' );
			return;
		}

		// Check if we do updates on today's weekday.
		$todays_weekday = strtolower( current_time( 'l' ) );
		if ( ! array_key_exists( 'auto_update_checks_' . $todays_weekday, $auto_update_settings ) ||
			empty( $auto_update_settings[ 'auto_update_checks_' . $todays_weekday ] ) ) {
			WebChangeDetector_Admin::error_log( 'Canceling auto updates: ' . $todays_weekday . ' is disabled.' );
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
				WebChangeDetector_Admin::error_log(
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
				WebChangeDetector_Admin::error_log(
					'Canceling auto updates: ' . current_time( 'H:i' ) .
					' is not between ' . $auto_update_settings['auto_update_checks_from'] .
					' and ' . $auto_update_settings['auto_update_checks_to']
				);
				$this->set_lock();
				return;
			}
		}

		// Other early returns.
		if (
			! doing_filter( 'wp_maybe_auto_update' ) &&
			! doing_filter( 'jetpack_pre_plugin_upgrade' ) &&
			! doing_filter( 'jetpack_pre_theme_upgrade' ) &&
			! doing_filter( 'jetpack_pre_core_upgrade' )
		) {
			WebChangeDetector_Admin::error_log( 'Not called from one of the allowed filters. Exiting.' );
			return;
		}

		WebChangeDetector_Admin::error_log( 'Checking status of Screenshots' );

		// Create external cron at wcd api to make sure the wp cron is triggered every minute.
		if ( false === get_option( WCD_WORDPRESS_CRON ) ) {
			$result = WebChangeDetector_API_V2::add_webhook_v2( get_site_url(), 'wordpress_cron' );
			WebChangeDetector_Admin::error_log( 'Webhook result: ' . wp_json_encode( $result ) );
			if ( is_array( $result ) && array_key_exists( 'data', $result ) ) {
				add_option( WCD_WORDPRESS_CRON, $result['data']['id'] );
			}
		}

		// Start pre-update screenshots and do the WCD Magic.
		$wcd_pre_update_data = get_transient( WCD_PRE_AUTO_UPDATE );
		if ( false === $wcd_pre_update_data ) { // We don't have a transient yet. So we start screenshots.
			$sc_response = WebChangeDetector_API_V2::take_screenshot_v2( $this->manual_group_id, 'pre' );
			WebChangeDetector_Admin::error_log( 'Pre update SC data: ' . wp_json_encode( $sc_response ) );
			$transient_data = array(
				'status'   => 'processing',
				'batch_id' => esc_html( $sc_response['batch'] ),
			);

			WebChangeDetector_Admin::error_log( 'Started taking screenshots and setting transients' );
			set_transient( WCD_PRE_AUTO_UPDATE, $transient_data, HOUR_IN_SECONDS );
			$this->set_lock();
			$this->reschedule( MINUTE_IN_SECONDS, 'wcd_wp_maybe_auto_update' );

			// SC are not done yet. Reschedule updates.
		} elseif ( 'done' !== $wcd_pre_update_data['status'] ) {
			WebChangeDetector_Admin::error_log( "Rescheduling cron 'wcd_wp_maybe_auto_update'..." );
			$this->set_lock();
			$this->reschedule( MINUTE_IN_SECONDS, 'wcd_wp_maybe_auto_update' );
		}
	}

	/** Send the change detection mail.
	 *
	 * @param array $post_sc_option Data about the post sc.
	 * @return void
	 */
	public function send_change_detection_mail( $post_sc_option ) {
		// If we don't have open or processing queues of the batch anymore, we can check for comparisons.
		$comparisons = WebChangeDetector_API_V2::get_comparisons_v2( array( 'batches' => $post_sc_option['batch_id'] ) );
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
		$mail_body  .= '<p>Howdy again, we checked your website for visual changes during the WP auto updates with WebChange Detector. Here are the results:</p>';
		if ( count( $comparisons['data'] ) ) {
			$no_difference_rows   = '';
			$with_difference_rows = '';

			foreach ( $comparisons['data'] as $comparison ) {
				$row =
					'<tr>
						<td>' . $comparison['url'] . '</td>
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
			$mail_body .= '<table><tr><th>URL</th><th>Change in %</th><th>Change Detection Page</th></tr>';
			if ( ! empty( $with_difference_rows ) ) {
				$mail_body .= $with_difference_rows;
			} else {
				$mail_body .= '<tr><td colspan="3" style="text-align: center;">No change detections to show here</td>';
			}
			$mail_body .= '</table>';

			$mail_body .= '<div style="margin: 20px 0 10px 0"><strong>Checks without differences</strong></div>';
			$mail_body .= '<table><tr><th>URL</th><th>Change in %</th><th>Change Detection Page</th></tr>';
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
		WebChangeDetector_Admin::error_log( 'Sending Mail with differences' );
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
		$auto_update_settings = $wcd->get_website_details()['auto_update_settings'];

		// Enable auto-update checks if the defines are set.
		if ( defined( 'WCD_AUTO_UPDATES_ENABLED' ) && true === WCD_AUTO_UPDATES_ENABLED ) {
			$auto_update_settings['auto_update_checks_enabled'] = '1';
		}
		return $auto_update_settings;
	}

	/**
	 * Reschedule a single event
	 *
	 * @param int    $how_long Seconds to reschedule the event in.
	 * @param string $hook Hook name.
	 * @return void
	 */
	private function reschedule( $how_long, $hook ) {
		wp_clear_scheduled_hook( $hook );
		if ( ! $how_long ) {
			return;
		}
		wp_schedule_single_event( time() + $how_long, $hook );
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
		if ( ! defined( 'WCD_PRE_AUTO_UPDATE' ) ) {
			define( 'WCD_PRE_AUTO_UPDATE', 'wcd_pre_auto_update' );
		}
		if ( ! defined( 'WCD_POST_AUTO_UPDATE' ) ) {
			define( 'WCD_POST_AUTO_UPDATE', 'wcd_post_auto_update' );
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
	}
}
