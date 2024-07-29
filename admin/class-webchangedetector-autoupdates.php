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

	/** Two minutes in seconds
	 *
	 * @var int
	 */
	public int $two_minutes_in_seconds = 120;

	/** One minute in seconds.
	 *
	 * @var int
	 */

	public int $one_minute_in_seconds = 60;

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

		// When auto-update magic happens.
		add_action( 'plugins_loaded', array( $this, 'plugins_loaded' ) );

		// Post updates.
		add_action( 'wcd_cron_check_post_queues', array( $this, 'wcd_cron_check_post_queues' ), 10, 2 );

		// Saving settings.
		add_action( 'wcd_save_update_group_settings', array( $this, 'wcd_save_update_group_settings' ) );

		// Cron jobs.
		add_action( 'wcd_release_lock', array( $this, 'wcd_release_lock' ) );
		add_action( 'wcd_wp_maybe_auto_update', array( $this, 'wcd_wp_maybe_auto_update' ) );

		$wcd_groups = get_option( WCD_WEBSITE_GROUPS );
		if ( ! $wcd_groups ) {
			return;
		}
		$this->manual_group_id     = $wcd_groups[ WCD_MANUAL_DETECTION_GROUP ] ?? false;
		$this->monitoring_group_id = $wcd_groups[ WCD_AUTO_DETECTION_GROUP ] ?? false;
	}

	/**
	 * We start here, when plugins are loaded.
	 */
	public function plugins_loaded() {
		add_filter( 'auto_update_plugin', array( $this, 'auto_update_plugin' ), PHP_INT_MAX, 2 );
		add_filter( 'auto_update_theme', array( $this, 'auto_update_theme' ), PHP_INT_MAX, 2 );
		add_filter( 'auto_update_core', array( $this, 'auto_update_core' ), PHP_INT_MAX, 2 );
		add_action( 'automatic_updates_complete', array( $this, 'automatic_updates_complete' ), 10, 1 );
	}

	/**
	 * Fires when wp auto updates are done.
	 *
	 * @return void
	 */
	public function automatic_updates_complete() {
		WebChangeDetector_Admin::error_log( 'Function: Automatic Updates Complete' );

		// We don't do anything here if wcd checks are disabled, or we don't have pre_auto_update option.
		$auto_update_settings = get_option( WCD_AUTO_UPDATE_SETTINGS );
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
		$comparison_batches = get_option( 'wcd_comparison_batches' );
		if ( ! $comparison_batches ) {
			$comparison_batches = array();
		}
		$comparison_batches[] = $response['batch'];
		set_transient( 'wcd_comparison_batches', $comparison_batches, WCD_HOUR_IN_SECONDS );

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
			$this->reschedule( $this->one_minute_in_seconds, 'wcd_cron_check_post_queues' );
		} else {
			// If we don't have open or processing queues of the batch anymore, we can check for comparisons.
			$comparisons = WebChangeDetector_API_V2::get_comparisons_v2( array( 'batch' => $post_sc_option['batch_id'] ) );
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

			$auto_update_settings = get_option( WCD_AUTO_UPDATE_SETTINGS );
			$to                   = get_bloginfo( 'admin_email' );
			if ( array_key_exists( 'auto_update_checks_emails', $auto_update_settings ) || ! empty( $auto_update_settings['auto_update_checks_emails'] ) ) {
				$to = $auto_update_settings['auto_update_checks_emails'];
			}
			$subject = '[' . get_bloginfo( 'name' ) . '] Auto Update Checks by WebChange Detector';
			$headers = array( 'Content-Type: text/html; charset=UTF-8' );
			WebChangeDetector_Admin::error_log( 'Sending Mail with differences' );
			wp_mail( $to, $subject, $mail_body, $headers );

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
		$response      = WebChangeDetector_API_V2::get_queue_v2( $pre_sc_transient['batch_id'], 'open,processing' );

		WebChangeDetector_Admin::error_log( 'Queue: ' . wp_json_encode( $response ) );
		// If we don't have open or processing queues of the batch anymore, we can do auto-updates.
		if ( count( $response['data'] ) === 0 ) {
			$pre_sc_transient['status'] = 'done';
			set_transient( WCD_PRE_AUTO_UPDATE, $pre_sc_transient, WCD_HOUR_IN_SECONDS );
		}

		// If the queues are not done yet, we reschedule and exit.
		if ( 'done' !== $pre_sc_transient['status'] ) {
			WebChangeDetector_Admin::error_log( 'Rescheduling updates as sc are not ready yet.' );
			$this->reschedule( $this->one_minute_in_seconds, 'wcd_wp_maybe_auto_update' );
			return;
		}

		// Remove the lock, just in case it is still locked somehow.
		delete_option( $this->lock_name );
		delete_option( WCD_AUTO_UPDATE_LOCK );

		// Start the auto-updates.
		wp_maybe_auto_update();
	}

	/**
	 * Set lock to prevent wp from updating
	 *
	 * @return void
	 */
	public function set_lock() {
		WebChangeDetector_Admin::error_log( 'Setting Lock' );
		$time = time();

		// If there was no lock yet, we add it.
		$lock_added = add_option( $this->lock_name, $time );
		if ( $lock_added ) {
			// To know that it was ours, we create a second option with the same timestamp.
			update_option( WCD_AUTO_UPDATE_LOCK, $time );
			// We only want to prevent  auto-updates for this run. So we release the lock in 1 min.
			wp_schedule_single_event( strtotime( '+1 minute' ), 'wcd_release_lock' );
		}
	}

	/**
	 * Release lock to allow wp to update
	 *
	 * @return void
	 */
	public function wcd_release_lock() {
		$wcd_lock = get_option( WCD_AUTO_UPDATE_LOCK );
		$wp_lock  = get_option( $this->lock_name );
		// We delete the lock only if it was from us. So we check the timestamp against our lock option.
		if ( $wcd_lock && $wp_lock && $wcd_lock === $wp_lock ) {
			WebChangeDetector_Admin::error_log( 'Releasing Lock' );
			delete_option( $this->lock_name );
		}
		// We delete our lock value anyway.
		delete_option( WCD_AUTO_UPDATE_LOCK );
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
			$auto_update_settings = get_option( WCD_AUTO_UPDATE_SETTINGS );
			if ( ! $auto_update_settings ) {
				return;
			}
			$auto_update_checks_from = $auto_update_settings['auto_update_checks_from'];
		}

		// Convert the local time into gmt time.
		$should_next_run = gmdate( 'U', strtotime( $auto_update_checks_from ) );

		$should_next_run_gmt = get_gmt_from_date( gmdate( 'Y-m-d H:i:s', $should_next_run ), 'U' );

		$now_gmt = get_gmt_from_date( current_time( 'Y-m-d H:i:s' ), 'U' );

		// Add a day if we passed the auto_update_checks_from time already.
		if ( $now_gmt > $should_next_run_gmt ) {
			$should_next_run_gmt = strtotime( '+1 day', $should_next_run_gmt );
		}

		// Finally clear current hook and create a new one.
		wp_clear_scheduled_hook( 'wp_version_check' );
		wp_clear_scheduled_hook( 'wp_update_plugins' );
		wp_clear_scheduled_hook( 'wp_update_themes' );
		wp_schedule_event( $should_next_run_gmt, 'twicedaily', 'wp_version_check' );
		wp_schedule_event( $should_next_run_gmt, 'twicedaily', 'wp_update_plugins' );
		wp_schedule_event( $should_next_run_gmt, 'twicedaily', 'wp_update_themes' );
	}

	/**
	 * Note - with the addition of support for JetPack remote updates (via manual action in a user's wordpress.com dashboard),
	 * this is now more accurately a method to handle *background* updates, rather than "automatic" ones.
	 *
	 * @param bool   $update Whether item is to update or not.
	 * @param string $item The item to update.
	 * @param string $type The type of the item.
	 * @return string
	 */
	public function auto_update( $update, $item, $type ) {
		WebChangeDetector_Admin::error_log( 'Function: auto_update ' . $update );
		$this->wcd_release_lock();

		$auto_update_settings = get_option( WCD_AUTO_UPDATE_SETTINGS );

		// We don't have auto-update settings yet or the manual checks group is not set. So, go the wp way.
		if ( ! $auto_update_settings || ! $this->manual_group_id ) {
			WebChangeDetector_Admin::error_log( 'Don\'t have  an group_id. Exiting' );
			return $update;
		}

		// Check if check on auto updates are enabled.
		if ( ! array_key_exists( 'auto_update_checks_enabled', $auto_update_settings ) ) {
			WebChangeDetector_Admin::error_log( 'Running auto updates without checks because they are disabled in WCD.' );
			return $update;
		}

		// Check if we do updates on today's weekday.
		if ( ! array_key_exists( 'auto_update_checks_' . strtolower( current_time( 'l' ) ), $auto_update_settings ) ) {
			WebChangeDetector_Admin::error_log( 'Canceling auto updates: ' . strtolower( current_time( 'l' ) ) . ' is disabled.' );
			$this->set_lock();
			return $update;
		}

		// Check if we do updates at current times.
		if ( current_time( 'H:i' ) < $auto_update_settings['auto_update_checks_from'] ||
			current_time( 'H:i' ) > $auto_update_settings['auto_update_checks_to'] ) {
			WebChangeDetector_Admin::error_log(
				'Canceling auto updates: ' . current_time( 'H:i' ) .
						' is not between ' . $auto_update_settings['auto_update_checks_from'] .
						' and ' . $auto_update_settings['auto_update_checks_to']
			);
			$this->set_lock();
			return $update;
		}

		// Early returns.
		if (
			! $update ||
			(
				! doing_filter( 'wp_maybe_auto_update' ) &&
				! doing_filter( 'jetpack_pre_plugin_upgrade' ) &&
				! doing_filter( 'jetpack_pre_theme_upgrade' ) &&
				! doing_filter( 'jetpack_pre_core_upgrade' )
			)
		) {
			return $update;
		}

		// This has to be copied from WP_Automatic_Updater::should_update() because it's another reason why the eventual decision may be false.
		// If it's a core update, are we actually compatible with its requirements?
		if ( 'core' === $type ) {
			global $wpdb;
			$php_compat = version_compare( phpversion(), $item->php_version, '>=' );
			if ( file_exists( WP_CONTENT_DIR . '/db.php' ) && empty( $wpdb->is_mysql ) ) {
				$mysql_compat = true;
			} else {
				$mysql_compat = version_compare( $wpdb->db_version(), $item->mysql_version, '>=' );
			}
			if ( ! $php_compat || ! $mysql_compat ) {
				return false;
			}
		}

		WebChangeDetector_Admin::error_log( 'Checking status of Screenshots' );
		WebChangeDetector_Admin::error_log( 'Update type: ' . wp_json_encode( $type ) );

		// Create external cron at wcd api to make sure the wp cron is triggered every minute.
		if ( false === get_option( WCD_WORDPRESS_CRON ) ) {
			$result = WebChangeDetector_API_V2::add_webhook_v2( get_site_url(), 'wordpress_cron' );
			WebChangeDetector_Admin::error_log( 'Webhook result: ' . wp_json_encode( $result ) );
			if ( is_array( $result ) && array_key_exists( 'data', $result ) ) {
				add_option( WCD_WORDPRESS_CRON, $result['data']['id'] );
			}
		}

		// Do the WCD Magic and start pre-update screenshots.
		$wcd_pre_update_data = get_transient( WCD_PRE_AUTO_UPDATE );
		if ( false === $wcd_pre_update_data ) { // We don't have a transient yet. So we start screenshots.
			WebChangeDetector_Admin::error_log( 'Manual Group UUID: ' . $this->manual_group_id );
			$sc_response = WebChangeDetector_API_V2::take_screenshot_v2( $this->manual_group_id, 'pre' );
			WebChangeDetector_Admin::error_log( 'Pre update SC data: ' . wp_json_encode( $sc_response ) );
			$transient_data = array(
				'status'   => 'processing',
				'batch_id' => esc_html( $sc_response['batch'] ),
			);

			WebChangeDetector_Admin::error_log( 'Started taking screenshots and setting transients' );
			set_transient( WCD_PRE_AUTO_UPDATE, $transient_data , HOUR_IN_SECONDS);
			$this->reschedule( $this->one_minute_in_seconds, 'wcd_wp_maybe_auto_update' );
			return false;

		} elseif ( 'done' !== $wcd_pre_update_data['status'] ) { // SC are not done yet. Reschedule updates.
			WebChangeDetector_Admin::error_log( "Rescheduling cron 'wcd_wp_maybe_auto_update'..." );
			$this->reschedule( $this->one_minute_in_seconds, 'wcd_wp_maybe_auto_update' );
			return false;
		}

		// shouldn't get here, but to be safe...
		return $update;
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
	 * Hooking into auto-updates for plugins.
	 *
	 * @param bool|null $update Whether to update. The value of null is internally used
	 *                           to detect whether nothing has hooked into this filter.
	 *  @param object    $item   The update offer.
	 * @return string
	 */
	public function auto_update_plugin( $update, $item ) {
		return $this->auto_update( $update, $item, 'plugins' );
	}

	/**
	 * Hooking into auto-updates for themes.
	 *
	 * @param bool|null $update Whether to update. The value of null is internally used
	 *                           to detect whether nothing has hooked into this filter.
	 *  @param object    $item   The update offer.
	 * @return string
	 */
	public function auto_update_theme( $update, $item ) {
		return $this->auto_update( $update, $item, 'themes' );
	}

	/**
	 * Hooking into auto-updates for core.
	 *
	 * @param bool|null $update Whether to update. The value of null is internally used
	 *                           to detect whether nothing has hooked into this filter.
	 * @param object    $item   The update offer.
	 * @return string
	 */
	public function auto_update_core( $update, $item ) {
		return $this->auto_update( $update, $item, 'core' );
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
		if ( ! defined( 'WCD_AUTO_UPDATE_LOCK' ) ) {
			define( 'WCD_AUTO_UPDATE_LOCK', 'wcd_auto_update_lock' );
		}
		if ( ! defined( 'WCD_HOUR_IN_SECONDS' ) ) {
			define( 'WCD_HOUR_IN_SECONDS', 3600 );
		}
	}
}
