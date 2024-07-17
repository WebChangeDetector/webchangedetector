<?php
/**
Title: WebChange Detector Auto Update Feature
Description: Check your website on auto updates visually and see what changed.
Version: 1.0
 */

new WebChangeDetector_Autoupdates();

class WebChangeDetector_Autoupdates {

	// Has to be synced with WP_Automatic_Updater::run()
	private $lock_name = 'auto_updater.lock';

	public $one_minute_in_seconds  = 60;
	public $two_minutes_in_seconds = 120;

	public $wcd;

	/**
	 * Plugin constructor
	 */
	public function __construct() {

		add_action( 'wcd_wp_maybe_auto_update', array( $this, 'wcd_wp_maybe_auto_update' ) );

		// Hooks into JetPack's remote updater (manual updates performed from the wordpress.com console)
		add_action( 'jetpack_pre_plugin_upgrade', array( $this, 'jetpack_pre_plugin_upgrade' ), 10, 3 );
		add_action( 'jetpack_pre_theme_upgrade', array( $this, 'jetpack_pre_theme_upgrade' ), 10, 2 );
		add_action( 'jetpack_pre_core_upgrade', array( $this, 'jetpack_pre_core_upgrade' ) );

		add_action( 'plugins_loaded', array( $this, 'plugins_loaded' ) );

		// Post updates
		add_action( 'upgrader_process_complete', array( $this, 'upgrader_process_complete' ), 10, 2 );
		add_action( 'wcd_cron_check_post_queues', array( $this, 'wcd_cron_check_post_queues' ), 10, 2 );

		add_action( 'admin_init', array( $this, 'admin_init' ) );

		$this->wcd = new WebChangeDetector_Admin();
	}

	/**
	 * We start here, when all plugins are loaded.
	 */
	public function plugins_loaded() {

		// Action added in WP 4.4. So we are not compatible before 4.4 (which was launched 2015).
		// error_log("Function: plugins_loaded");

		add_filter( 'auto_update_plugin', array( $this, 'auto_update_plugin' ), PHP_INT_MAX, 2 );
		add_filter( 'auto_update_theme', array( $this, 'auto_update_theme' ), PHP_INT_MAX, 2 );
		add_filter( 'auto_update_core', array( $this, 'auto_update_core' ), PHP_INT_MAX, 2 );

		// add_action('pre_auto_update', array($this, 'pre_auto_update'), 10, 3);
	}

	// Delete this one. Only for debugging
	public function admin_init() {
		/*
		// error_log("Function: admin_init");
		$core_transient = get_site_transient('update_core');
		$themes_transient = get_site_transient('update_themes');
		$plugins_transient = get_site_transient('update_plugins');

		$new_time = strtotime('-13 hours');
		$core_transient->last_checked = $new_time;
		$themes_transient->last_checked = $new_time;
		$plugins_transient->last_checked = $new_time;

		set_site_transient("update_core", $core_transient);
		set_site_transient("update_themes", $themes_transient);
		set_site_transient("update_plugins", $plugins_transient);
		//$plugins_transient = get_site_transient('update_plugins');
		//error_log("Plugins last checked: " . date("d.m.Y H:i",$plugins_transient->last_checked));
		*/
	}

	/**
	 * Fires when the update process is finished (for each plugin/theme/core).
	 */
	public function upgrader_process_complete( $upgrader, $hook_extra ) {

		error_log( 'Function: upgrader_process_complete' );
		// error_log("Upgrade complete data: " . json_encode($hook_extra));

		// We don't do anything here if we didn't start checks.
		$auto_update_settings = get_option( 'wcd_auto_update_settings' );
		if ( ! array_key_exists( 'auto_update_checks_enabled', $auto_update_settings ) ) {
			error_log( 'Skipping after update stuff as checks are disabled.' );
			return;
		}

		// If there were updates which we didn't start, we skip from here.
		if(! get_option('wcd_pre_auto_update') || ! get_option('wcd_updating_items')) {
			return;
		}

		// Add items to update to option wcd_updated_items.
		$updated_items = get_option( 'wcd_updated_items' );
		if ( ! $updated_items ) {
			$updated_items = array();
		}
		switch ( $hook_extra['type'] ) {
			case 'core':
				$updated_items[] = array( 'core' => 'core' );
				break;

			case 'plugin':
				$updated_items[] = array( 'plugin' => $hook_extra['plugin'] );
				break;

			case 'theme':
				$updated_items[] = array( 'theme' => $hook_extra['theme'] );
				break;
		}

		error_log( 'Saving updated items: ' . json_encode( $updated_items ) );
		update_option( 'wcd_updated_items', $updated_items );

		$updating_items = get_option( 'wcd_updating_items' );

		// Check if Updates are complete and start post-update sc and comparisons
		if ( count( $updating_items ) === count( $updated_items ) ) {
			error_log( 'Updates complete. Starting post-update screenshots and comparisons.' );
			$response = $this->wcd->take_screenshot_v2( $this->wcd->manual_group_uuid, 'post' );
			error_log( 'Post-Screenshot Response: ' . json_encode( $response ) );
			add_option(
				'wcd_post_auto_update',
				array(
					'status'   => 'processing',
					'batch_id' => $response['batch'],
				)
			);
			$this->wcd_cron_check_post_queues();

			$this->reschedule( $this->one_minute_in_seconds, 'wcd_cron_check_post_queues' );
		}
	}

	public function wcd_cron_check_post_queues() {
		$post_sc_option = get_option( 'wcd_post_auto_update' );
		$response       = $this->wcd->get_queue_v2( $post_sc_option['batch_id'], 'open,processing' );

		// If we don't have open or processing queues of the batch anymore, we can check for comparisons.
		if ( count( $response['data'] ) === 0 ) {
			$comparisons = $this->wcd->get_comparisons_v2( array( 'batch' => $post_sc_option['batch_id'] ) );
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

			$auto_update_settings = get_option( 'wcd_auto_update_settings' );
			$to                   = get_bloginfo( 'admin_email' );
			if ( array_key_exists( 'auto_update_checks_emails', $auto_update_settings ) || ! empty( $auto_update_settings['auto_update_checks_emails'] ) ) {
				$to = $auto_update_settings['auto_update_checks_emails'];
			}
			$subject = '[' . get_bloginfo( 'name' ) . '] Auto Update Checks by WebChange Detector';
			$headers = array( 'Content-Type: text/html; charset=UTF-8' );
			error_log( 'Sending Mail with differences' );
			wp_mail( $to, $subject, $mail_body, $headers );

			// We don't need the webhook anymore
			$this->wcd->delete_webhook( get_option( 'wcd_wordpress_cron' ) );

			// Cleanup wp_options and cron webhook.
			delete_option( 'wcd_wordpress_cron' );
			delete_option( 'wcd_updated_items' );
			delete_option( 'wcd_updating_items' );
			delete_option( 'wcd_pre_auto_update' );
			delete_option( 'wcd_post_auto_update' );

		} else { // check in a minute again
			$this->reschedule( $this->one_minute_in_seconds, 'wcd_cron_check_post_queues' );
		}
	}

	public function wcd_wp_maybe_auto_update() {
		error_log( 'Checking if sc are ready' );
		$pre_sc_option = get_option( 'wcd_pre_auto_update' );
		$response      = $this->wcd->get_queue_v2( $pre_sc_option['batch_id'], 'open,processing' );

		error_log( 'Queue: ' . json_encode( $response ) );
		// If we don't have open or processing queues of the batch anymore, we can do auto-updates.
		if ( count( $response['data'] ) === 0 ) {
			$pre_sc_option['status'] = 'done';
			update_option( 'wcd_pre_auto_update', $pre_sc_option );
		}

		// If the queues are not done yet, we reschedule and exit.
		if ( 'done' !== $pre_sc_option['status'] ) {
			error_log( 'Rescheduling updates as sc are not ready yet.' );
			$this->reschedule( $this->one_minute_in_seconds, 'wcd_wp_maybe_auto_update' );
			return;
		}

		// Remove the lock, to allow the WP updater to claim it and proceed.
		// delete_option($this->lock_name);

		// Start the auto-updates.
		wp_maybe_auto_update();
	}

	/**
	 * Note - with the addition of support for JetPack remote updates (via manual action in a user's wordpress.com dashboard), this is now more accurately a method to handle *background* updates, rather than "automatic" ones.
	 *
	 * @param  string $update
	 * @param  object $item
	 * @param  array  $type
	 * @return string
	 */
	public function auto_update( $update, $item, $type ) {

		error_log( 'Function: auto_update ' . $update );

		$auto_update_settings = get_option( 'wcd_auto_update_settings' );

		// We don't have auto-update settings yet. So, go the wp way.
		if ( ! $auto_update_settings ) {
			return $update;
		}

		// Check if check on auto updates are enabled.
		if ( ! array_key_exists( 'auto_update_checks_enabled', $auto_update_settings ) ) {
			error_log( 'Running auto updates without checks because they are disabled in WCD.' );
			return $update;
		}

		// Check if we do updates on today's weekday.
		if ( ! array_key_exists( 'auto_update_checks_' . strtolower( current_time( 'l' ) ), $auto_update_settings ) ) {
			error_log( 'Canceling auto updates: ' . strtolower( current_time( 'l' ) ) . ' is disabled.' );
			return false;
		}

		// Check if we do updates at current times.
		if ( current_time( 'H:i' ) < $auto_update_settings['auto_update_checks_from'] ||
			current_time( 'H:i' ) > $auto_update_settings['auto_update_checks_to'] ) {
			error_log(
				'Canceling auto updates: ' . current_time( 'H:i' ) .
						' is not between ' . $auto_update_settings['auto_update_checks_from'] .
						' and ' . $auto_update_settings['auto_update_checks_to']
			);
			return false;
		}

		// Early returns
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
		if ( 'core' == $type ) {
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

		// Add items to update to option wcd_updating_items.
		// error_log("Item: " . json_encode($item));
		$updating_items = get_option( 'wcd_updating_items' );
		if ( ! $updating_items ) {
			$updating_items = array();
		}
		if ( isset( $item->theme ) ) {
			$updating_items[] = array( 'theme' => $item->theme );
		} elseif ( isset( $item->plugin ) ) {
			$updating_items[] = array( 'plugin' => $item->plugin );
		} elseif ( isset( $item->response ) && 'autoupdate' === $item->response ) {
			$updating_items[] = array( 'core' => 'core' );
		}
		error_log( 'Saving updating items: ' . json_encode( $updating_items ) );
		update_option( 'wcd_updating_items', $updating_items );

		error_log( 'Checking status of Screenshots' );
		error_log( 'Update type: ' . json_encode( $type ) );

		// Do the WCD Magic and start pre-update screenshots
		$wcd_update_option = get_option( 'wcd_pre_auto_update' );

		if ( false === get_option( 'wcd_wordpress_cron' ) ) {
			$result = $this->wcd->add_webhook_v2( get_site_url(), 'wordpress_cron' );
			if ( array_key_exists( 'data', $result ) ) {
				add_option( 'wcd_wordpress_cron', $result['data']['id'] );
			}
		}
		if ( false === $wcd_update_option ) { // We don't have an wp_option yet. So we start screenshots
			$sc_response = $this->wcd->take_screenshot_v2( $this->wcd->manual_group_uuid, 'pre' );
			error_log( 'Pre update SC data: ' . json_encode( $sc_response ) );
			$transientData = array(
				'status'   => 'processing',
				'batch_id' => esc_html( $sc_response['batch'] ),
			);

			error_log( 'Started taking screenshots and setting transients' );
			add_option( 'wcd_pre_auto_update', ( $transientData ) );
			$this->reschedule( $this->one_minute_in_seconds, 'wcd_wp_maybe_auto_update' );
			return false;
		} elseif ( 'done' !== $wcd_update_option['status'] ) { // SC are not done yet. Reschedule updates
			error_log( "Rescheduling cron 'wcd_wp_maybe_auto_update'..." );
			$this->reschedule( $this->one_minute_in_seconds, 'wcd_wp_maybe_auto_update' );
			return false;
		}

		// shouldn't get here, but to be safe...
		return $update;
	}

	/**
	 * Reschedule the automatic update check event
	 *
	 * @param Integer $how_long - how many seconds in the future from now to reschedule for
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
	 * Hooks for jetpack.
	 *
	 * @param  array $plugin
	 * @param  array $plugins
	 * @param  array $update_attempted
	 * @return void
	 */
	public function jetpack_pre_plugin_upgrade( $plugin, $plugins, $update_attempted ) {// phpcs:ignore VariableAnalysis.CodeAnalysis.VariableAnalysis.UnusedVariable -- Filter use
		$this->auto_update( true, $plugin, 'plugins' );
	}

	public function jetpack_pre_theme_upgrade( $theme, $themes ) {// phpcs:ignore VariableAnalysis.CodeAnalysis.VariableAnalysis.UnusedVariable -- Filter use
		$this->auto_update( true, $theme, 'themes' );
	}

	public function jetpack_pre_core_upgrade( $update ) {
		$this->auto_update( true, $update, 'core' );
	}

	/**
	 * Hooking into auto-updates.
	 *
	 * @param  string $update
	 * @param  object $item
	 * @return string
	 */
	public function auto_update_plugin( $update, $item ) {
		return $this->auto_update( $update, $item, 'plugins' );
	}

	public function auto_update_theme( $update, $item ) {
		return $this->auto_update( $update, $item, 'themes' );
	}

	public function auto_update_core( $update, $item ) {
		return $this->auto_update( $update, $item, 'core' );
	}
}
