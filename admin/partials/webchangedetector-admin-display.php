<?php

/**
 * Provide a admin area view for the plugin
 *
 * This file is used to markup the admin-facing aspects of the plugin.
 *
 * @link       wp-mike.com
 * @since      1.0.0
 *
 * @package    WebChangeDetector
 * @subpackage WebChangeDetector/admin/partials
 */


if ( ! function_exists( 'wcd_webchangedetector_init' ) ) {
	function wcd_webchangedetector_init() {
		// Add a nonce for security and authentication.
		$nonce = wp_create_nonce( 'wcd_nonce_field' );

		// Start view
		echo '<div class="wrap">';
		echo '<div class="webchangedetector">';
		echo '<h1>WebChangeDetector</h1>';

		$wcd = new WebChangeDetector_Admin();

		// Validate action
		$wcd_action = null;
		if ( isset( $_POST['wcd_action'] ) ) {
			$wcd_action = sanitize_key( $_POST['wcd_action'] );
			if ( ! is_string( $wcd_action ) || ! in_array( $wcd_action, WebChangeDetector_Admin::VALID_WCD_ACTIONS ) ) {
				echo '<div class="error notice"><p>Ooops! There was an unknown action called. Please contact us.</p></div>';
				return false;
			}
		}

		// Actions without API Token needed
		switch ( $wcd_action ) {
			case 'create_free_account':
				// Validate if all required fields were sent
				if ( ! ( $_POST['name_first'] && $_POST['name_last'] && $_POST['email'] && $_POST['password'] ) ) {
					echo '<div class="notice notice-error"><p>Please fill all required fields.</p></div>';
					echo $wcd->get_no_account_page();
					return false;
				}

				$api_token = $wcd->create_free_account( $_POST );
				$success   = $wcd->save_api_token( $api_token );

				if ( ! $success ) {
					return false;
				}
				break;

			case 'reset_api_token':
				delete_option(WCD_WP_OPTION_KEY_API_TOKEN);
				break;

			case 're-add-api-token':
				if ( empty( $_POST['api_token'] ) ) {
					echo $wcd->get_no_account_page();
					return true;
				}

				$wcd->save_api_token( $_POST['api_token'] );
				break;

			case 'save_api_token':
				if ( empty( $_POST['api_token'] ) ) {
					echo '<div class="notice notice-error"><p>No API Token given.</p></div>';
					echo $wcd->get_no_account_page();
					return false;
				}

				$wcd->save_api_token( $_POST['api_token'] );
				break;
		}

		// Change api token option name from V1.0.7
		if ( ! get_option( WCD_WP_OPTION_KEY_API_TOKEN ) && get_option( 'webchangedetector_api_key' ) ) {
			add_option( WCD_WP_OPTION_KEY_API_TOKEN, get_option( 'webchangedetector_api_key' ), '', false );
			delete_option( 'webchangedetector_api_key' );
		}

		// We still don't have an api_token
		if ( ! get_option( WCD_WP_OPTION_KEY_API_TOKEN ) ) {
			echo $wcd->get_no_account_page();
			return false;
		}

		// Get the account details
		$account_details = $wcd->account_details();

		// Check if plugin has to be updated
		if ( $account_details === 'update plugin' ) {
			echo '<div class="notice notice-error"><p>There are major updates in our system which requires to update the plugin 
            WebChangeDetector. Please install the update at <a href="/wp-admin/plugins.php">Plugins</a>.</p></div>';
			wp_die();
		}

		// Check if account is activated and if the api key is authorized
		if ( ! is_array( $account_details ) && ( $account_details === 'activate account' || $account_details === 'unauthorized' ) ) {
			$wcd->show_activate_account( $account_details );
			return false;
		}

		// Show error message if we didn't get response from API
		if ( empty( $account_details ) ) {
			echo "<div style='margin: 0 auto; text-align: center;  width: 400px; padding: 20px; border: 1px solid #aaa'>
                    <h1>Oooops!</h1>
                    <p>Something went wrong. We're already working on the issue. <br>
                    Please check again later.</p>
                  </div>";
			exit;
		}

		// Show low credits
		$usage_percent = (int) ( $account_details['usage'] / $account_details['sc_limit'] * 100 );

		if ( $usage_percent >= 100 ) {
			if ( $account_details['plan']['one_time'] ) { // Check for trial account ?>
				<div class="notice notice-error">
					<p>You ran out of checks. Please upgrade your account to continue.</p>
				</div>
			<?php } else { ?>
				<div class="notice notice-error">
					<p>You ran out of checks. Please upgrade your account to continue or wait for renewal.</p>
				</div>
				<?php
			}
		} elseif ( $usage_percent > 70 ) {
			?>
			<div class="notice notice-warning"><p>You used <?php echo $usage_percent; ?>% of your checks.</p></div>
			<?php
		}

		// Set the website details class object
		$wcd->set_website_details();

		// If we don't have the website for any reason we show an error message.
		if ( empty( $wcd->website_details ) ) {
			?>
			<div class="notice notice-error">
				<br>Ooops! We couldn't find your settings. Please try reloading the page. <br>
				If the issue persists, please contact us.</p>
				<p>
					<form method="post">
						<input type="hidden" name="wcd_action" value="re-add-api-token">
						<input type="submit" value="Re-add website" class="button-primary">
					</form>
				</p>
			</div>
			<?php
			return false;
		}

		$group_id            = ! empty( $wcd->website_details['manual_detection_group_id'] ) ? $wcd->website_details['manual_detection_group_id'] : null;
		$monitoring_group_id = ! empty( $wcd->website_details['auto_detection_group_id'] ) ? $wcd->website_details['auto_detection_group_id'] : null;

		$monitoring_group_settings = null; // @TODO Can be deleted?

		// Perform actions
		switch ( $wcd_action ) {

			case 'add_post_type':
				$wcd->add_post_type( $_POST );
				$wcd->sync_posts();
				echo '<div class="notice notice-success"><p>' . json_decode( stripslashes( $_POST['post_type'] ), true )[0]['post_type_name'] . ' added.</p></div>';
				break;

			case 'update_detection_step':
				update_option( 'webchangedetector_update_detection_step', sanitize_key( $_POST['step'] ) );
				break;

			case 'take_screenshots':
				$scType = sanitize_key( $_POST['sc_type'] );

				if ( ! in_array( $scType, WebChangeDetector_Admin::VALID_SC_TYPES ) ) {
					echo '<div class="error notice"><p>Wrong Screenshot type.</p></div>';
					return false;
				}

				$results = $wcd->take_screenshot( $group_id, $scType );

				if ( $results && is_array( $results ) && count( $results ) > 1 && $results[0] === 'error' ) {
					echo '<div class="error notice"><p>' . $results[1] . '</p></div>';
				} elseif ( $scType === 'pre' ) {
						update_option( WCD_OPTION_UPDATE_STEP_KEY, WCD_OPTION_UPDATE_STEP_PRE_STARTED );
				} elseif ( $scType === 'post' ) {
					update_option( WCD_OPTION_UPDATE_STEP_KEY, WCD_OPTION_UPDATE_STEP_POST_STARTED );
				}
				break;

			case 'post_urls_update_and_auto':
				$wcd->post_urls( $_POST, $wcd->website_details, true );

				// Get the depending group names before saving to avoid group name changes in webapp
				$manual_group_name   = $wcd->get_urls_of_group( $wcd->website_details['manual_detection_group_id'] )['name'];
				$_POST['group_name'] = $manual_group_name;
				$wcd->update_settings( $_POST, $group_id );

				$auto_group_name     = $wcd->get_urls_of_group( $wcd->website_details['auto_detection_group_id'] )['name'];
				$_POST['group_name'] = $auto_group_name;
				$wcd->update_monitoring_settings( $_POST, $monitoring_group_id );
				break;

			case 'post_urls':
				$wcd->post_urls( $_POST, $wcd->website_details, false );
				if ( ! empty( $_POST['monitoring'] ) && $_POST['monitoring'] ) {
					$wcd->update_monitoring_settings( $_POST, $monitoring_group_id );
				} else {
					$wcd->update_settings( $_POST, $group_id );
				}
				break;

			case 'save_update_settings_and_continue':
				$wcd->post_urls( $_POST, $wcd->website_details, false );
				$wcd->update_settings( $_POST, $group_id );

				// Update step in update detection
				if ( ! empty( $_POST['step'] ) ) {
					update_option( WCD_OPTION_UPDATE_STEP_KEY, sanitize_key( $_POST['step'] ) );
				}
				break;
		}

		// Get updated account and website data
		$account_details = $wcd->account_details();

		// Error message if api didn't return account details.
		if ( empty( $account_details['status'] ) ) {
			?>
			<div class="error notice">
				<p>Ooops! Something went wrong. Please try again.</p>
				<p>If the issue persists, please contact us.</p>
			</div>
			<?php
			return false;
		}

		// Check for account status
		if ( $account_details['status'] !== 'active' ) {

			// Set error message
			$err_msg = 'cancelled';
			if ( ! empty( $account_details['status'] ) ) {
				$err_msg = $account_details['status'];
			}
			echo '
            <div class="error notice">
                <h3>Your account was ' . $err_msg . '.</h3>
                <p>Please <a href="' . $wcd->get_upgrade_url() . '">Upgrade</a> your account to re-activate your account.</p>
                <p>To use a different account, please reset the API token.
                    <form method="post">
                        <input type="hidden" name="wcd_action" value="reset_api_token">
                        <input type="submit" value="Reset API token" class="button button-delete">
                    </form>
                </p>
            </div>';
			return false;
		}

		// Get page to view
		$tab = 'webchangedetector-dashboard'; // init
		if ( isset( $_GET['page'] ) ) {
			// sanitize: lower-case with "-"
			$tab = sanitize_key( $_GET['page'] );
		}

		// Check if website details are available.
		if ( empty( $wcd->website_details ) ) {
			echo '<div class="error notice"><p>
                    We couldn\'t find your website settings. Please reset the API token in 
                    settings and re-add your website with your API Token.
                    </p><p>
                    Your current API token is: <strong>' . get_option( WCD_WP_OPTION_KEY_API_TOKEN ) . '</strong>.
                    </p>
                     <form method="post">
                        <input type="hidden" name="wcd_action" value="reset_api_token">
                        <input type="hidden" name="api_token" value="' . get_option( WCD_WP_OPTION_KEY_API_TOKEN ) . '">
                        <input type="submit" class="button" value="Reset API token" class="button button-delete">
                    </form>
                    </p>
                   </div>';
			return false;
		}

		$wcd->tabs();

		echo '<div style="margin-top: 30px;"></div>';

		// Account credits
		$comp_usage         = $account_details['usage'];
		$limit              = $account_details['sc_limit'];
		$available_compares = $account_details['available_compares'];

		if ( $wcd->website_details['enable_limits'] ) {
			$account_details['usage']            = $comp_usage; // used in dashboard
			$account_details['plan']['sc_limit'] = $limit; // used in dashboard
		}

		// Renew date (used in template)
		$renew_date = strtotime( $account_details['renewal_at'] ); // used in account template

		switch ( $tab ) {

			/********************
			 * Dashboard
			 */

			case 'webchangedetector':
				$wcd->get_dashboard_view( $account_details, $group_id, $monitoring_group_id );
				break;

			/********************
			 * Change Detections
			 */

			case 'webchangedetector-change-detections':
				echo '<h2>Latest Change Detections</h2>';

				$limit_days = null;
				if ( isset( $_POST['limit_days'] ) ) {
					$limit_days = sanitize_key( $_POST['limit_days'] );
					if ( ! empty( $limit_days ) && ! is_numeric( $limit_days ) ) {
						echo '<div class="error notice"><p>Wrong limit_days.</p></div>';
						return false;
					}
				}
				$group_type = null;
				if ( isset( $_POST['group_type'] ) ) {
					$group_type = sanitize_key( $_POST['group_type'] );
					if ( ! empty( $group_type ) && ! in_array( $group_type, WebChangeDetector_Admin::VALID_GROUP_TYPES ) ) {
						echo '<div class="error notice"><p>Invalid group_type.</p></div>';
						return false;
					}
				}

				$difference_only = null;
				if ( isset( $_POST['difference_only'] ) ) {
					$difference_only = sanitize_key( $_POST['difference_only'] );
				}

				$compares = $wcd->get_compares( array( $group_id, $monitoring_group_id ), $limit_days, $group_type, $difference_only );
				?>
				<div class="action-container">
					<form method="post">
						<select name="limit_days">
							<option value="" <?php echo $limit_days === null ? 'selected' : ''; ?>> Show all</option>
							<option value="3" <?php echo $limit_days === 3 ? 'selected' : ''; ?>>Last 3 days</option>
							<option value="7" <?php echo $limit_days === 7 ? 'selected' : ''; ?>>Last 7 days</option>
							<option value="14" <?php echo $limit_days === 14 ? 'selected' : ''; ?>>Last 14 days</option>
							<option value="30"<?php echo $limit_days === 30 ? 'selected' : ''; ?>>Last 30 days</option>
							<option value="60"<?php echo $limit_days === 60 ? 'selected' : ''; ?>>Last 60 days</option>
						</select>

						<select name="group_type" >
							<option value="" <?php echo ! $group_type ? 'selected' : ''; ?>>Monitoring & Manual Checks</option>
							<option value="update" <?php echo $group_type === 'update' ? 'selected' : ''; ?>>Only Manual Checks</option>
							<option value="auto" <?php echo $group_type === 'auto' ? 'selected' : ''; ?>>Only Monitoring</option>
						</select>

						<select name="difference_only" class="js-dropdown">
							<option value="0" <?php echo ! $difference_only ? 'selected' : ''; ?>>All detections</option>
							<option value="1" <?php echo $difference_only ? 'selected' : ''; ?>>With difference</option>
						</select>

						<input class="button" type="submit" value="Filter">
					</form>
					<?php

					$wcd->compare_view( $compares );
					?>
				</div>
				<div class="sidebar">
					<div class="account-box">
						<?php include 'templates/account.php'; ?>
					</div>
					<div class="help-box">
						<?php include 'templates/help-change-detection.php'; ?>
					</div>
				</div>
				<div class="clear"></div>

				<?php
				break;

			/***************************
			 * Update Change Detections
			*/

			case 'webchangedetector-update-settings':
				if ( $wcd->website_details['enable_limits'] && ! $wcd->website_details['allow_manual_detection'] ) {
					echo 'Settings for Update Change detections are disabled by your API Token.';
					break;
				}

				// Get selected urls
				$groups_and_urls = $wcd->get_urls_of_group( $group_id );

				$step = false;
				// Show message if no urls are selected
				if ( ! $groups_and_urls['amount_selected_urls'] ) {
					$step = WCD_OPTION_UPDATE_STEP_SETTINGS
					?>
					<div class="notice notice-warning"><p>Select URLs for manual checks to get started.</p></div>
				<?php } ?>

				<div class="action-container">

				<?php
				// Check if we have a step in the db
				if ( ! $step ) {
					$step = get_option( WCD_OPTION_UPDATE_STEP_KEY );
				}

				// Default step
				if ( ! $step ) {
					$step = WCD_OPTION_UPDATE_STEP_SETTINGS;
				}
				update_option( WCD_OPTION_UPDATE_STEP_KEY, sanitize_text_field( $step ), false );

				switch ( $step ) {
					case WCD_OPTION_UPDATE_STEP_SETTINGS:
						$progress_setting          = 'active';
						$progress_pre              = 'disabled';
						$progress_make_update      = 'disabled';
						$progress_post             = 'disabled';
						$progress_change_detection = 'disabled';
						include 'templates/update-detection/update-step-settings.php';
						break;

					case WCD_OPTION_UPDATE_STEP_PRE:
						$progress_setting          = 'done';
						$progress_pre              = 'active';
						$progress_make_update      = 'disabled';
						$progress_post             = 'disabled';
						$progress_change_detection = 'disabled';
						include 'templates/update-detection/update-step-pre-sc.php';
						break;

					case WCD_OPTION_UPDATE_STEP_PRE_STARTED:
						$progress_setting          = 'done';
						$progress_pre              = 'active';
						$progress_make_update      = 'disabled';
						$progress_post             = 'disabled';
						$progress_change_detection = 'disabled';
						$sc_processing             = $wcd->get_processing_queue(); // used in template
						include 'templates/update-detection/update-step-pre-sc-started.php';
						break;

					case WCD_OPTION_UPDATE_STEP_POST:
						$progress_setting          = 'done';
						$progress_pre              = 'done';
						$progress_make_update      = 'done';
						$progress_post             = 'active';
						$progress_change_detection = 'disabled';
						include 'templates/update-detection/update-step-post-sc.php';
						break;

					case WCD_OPTION_UPDATE_STEP_POST_STARTED:
						$progress_setting          = 'done';
						$progress_pre              = 'done';
						$progress_make_update      = 'done';
						$progress_post             = 'active';
						$progress_change_detection = 'disabled';
						$sc_processing             = $wcd->get_processing_queue(); // used in template
						include 'templates/update-detection/update-step-post-sc-started.php';
						break;

					case WCD_OPTION_UPDATE_STEP_CHANGE_DETECTION:
						$progress_setting          = 'done';
						$progress_pre              = 'done';
						$progress_make_update      = 'done';
						$progress_post             = 'done';
						$progress_change_detection = 'active';
						include 'templates/update-detection/update-step-change-detection.php';
						break;
				}
				?>
				</div>

				<div class="sidebar">
					<div class="account-box">
						<?php include 'templates/account.php'; ?>
					</div>
					<div class="help-box">
						<?php include 'templates/help-update.php'; ?>
					</div>
				</div>
				<div class="clear"></div>
				<?php
				break;

			/**************************
			 * Auto Change Detections
			 */

			case 'webchangedetector-auto-settings':
				if ( $wcd->website_details['enable_limits'] && ! $wcd->website_details['allow_auto_detection'] ) {
					echo 'Settings for Update Change detections are disabled by your API Token.';
					break;
				}

				$groups_and_urls = $wcd->get_urls_of_group( $monitoring_group_id );

				// Calculation for monitoring
				$date_next_sc = false;
				$next_sc_in   = false;
				if ( $groups_and_urls['monitoring'] ) {

					$amount_sc_per_day = 0;
					// Check for intervals >= 1h
					if ( $groups_and_urls['interval_in_h'] >= 1 ) {
						$next_possible_sc  = gmmktime( gmdate( 'H' ) + 1, 0, 0, gmdate( 'm' ), gmdate( 'd' ), gmdate( 'Y' ) );
						$amount_sc_per_day = ( 24 / $groups_and_urls['interval_in_h'] );
						$possible_hours    = array();
						// Get possible tracking hours
						for ( $i = 0; $i <= $amount_sc_per_day * 2; $i++ ) {
							$possible_hour    = $groups_and_urls['hour_of_day'] + $i * $groups_and_urls['interval_in_h'];
							$possible_hours[] = $possible_hour >= 24 ? $possible_hour - 24 : $possible_hour;
						}
						sort( $possible_hours );

						// Check for today and tomorrow
						for ( $ii = 0; $ii <= 1; $ii++ ) { // Do 2 loops for today and tomorrow
							for ( $i = 0; $i <= $amount_sc_per_day * 2; $i++ ) {
								$possible_time = gmmktime( $possible_hours[ $i ], 0, 0, gmdate( 'm' ), gmdate( 'd' ) + $ii, gmdate( 'Y' ) );

								if ( $possible_time >= $next_possible_sc ) {
									$date_next_sc = $possible_time; // This is the next possible time. So we break here.
									break;
								}
							}
							// Dont check for tomorrow if we found the next date today
							if ( $date_next_sc ) {
								break;
							}
						}
					}

					// Check for 30 min intervals
					if ( $groups_and_urls['interval_in_h'] === 0.5 ) {
						$amount_sc_per_day = 48;
						if ( gmdate( 'i' ) < 30 ) {
							$date_next_sc = gmmktime( gmdate( 'H' ), 30, 0, gmdate( 'm' ), gmdate( 'd' ), gmdate( 'Y' ) );
						} else {
							$date_next_sc = gmmktime( gmdate( 'H' ) + 1, 0, 0, gmdate( 'm' ), gmdate( 'd' ), gmdate( 'Y' ) );
						}
					}
					// Check for 15 min intervals
					if ( $groups_and_urls['interval_in_h'] === 0.25 ) {
						$amount_sc_per_day = 96;
						if ( gmdate( 'i' ) < 15 ) {
							$date_next_sc = gmmktime( gmdate( 'H' ), 15, 0, gmdate( 'm' ), gmdate( 'd' ), gmdate( 'Y' ) );
						} elseif ( gmdate( 'i' ) < 30 ) {
							$date_next_sc = gmmktime( gmdate( 'H' ), 30, 0, gmdate( 'm' ), gmdate( 'd' ), gmdate( 'Y' ) );
						} elseif ( gmdate( 'i' ) < 45 ) {
							$date_next_sc = gmmktime( gmdate( 'H' ), 45, 0, gmdate( 'm' ), gmdate( 'd' ), gmdate( 'Y' ) );
						} else {
							$date_next_sc = gmmktime( gmdate( 'H' ) + 1, 0, 0, gmdate( 'm' ), gmdate( 'd' ), gmdate( 'Y' ) );

						}
					}

					// Calculate screenshots until renewal
					$account = $wcd->account_details();

					$days_until_renewal      = date( 'd', date( 'U', strtotime( $account['renewal_at'] ) ) - date( 'U' ) );
					$amount_group_sc_per_day = $groups_and_urls['amount_selected_urls'] * $amount_sc_per_day * $days_until_renewal;

					// Get first detection hour
					$first_hour_of_interval = $groups_and_urls['hour_of_day'];
					while ( $first_hour_of_interval - $groups_and_urls['interval_in_h'] >= 0 ) {
						$first_hour_of_interval = $first_hour_of_interval - $groups_and_urls['interval_in_h'];
					}

					// Count up in interval_in_h to current hour
					$skip_sc_count_today = 0;
					while ( $first_hour_of_interval + $groups_and_urls['interval_in_h'] <= date( 'H' ) ) {
						$first_hour_of_interval = $first_hour_of_interval + $groups_and_urls['interval_in_h'];
						++$skip_sc_count_today;
					}

					// Subtract screenshots already taken today
					$total_sc_current_period = $amount_group_sc_per_day - $skip_sc_count_today * $groups_and_urls['amount_selected_urls'];
				}
				?>

				<div class="action-container">
					<div class="status_bar">
						<div class="box full">
							<div id="txt_next_sc_in">Next change detections in</div>
							<div id="next_sc_in" class="big"></div>
							<div id="next_sc_date" class="local-time" data-date="<?php echo $date_next_sc; ?>"></div>
						</div>

						<!-- @TODO: Calculation is wrong and hidden. Replace this with the one from the dashboard -->
						<div class="box half" style="display: none;">
							Current settings require
							<div id="sc_until_renew" class="big">
								<span id="ajax_amount_total_sc"></span> Checks
							</div>
							<div id="sc_available_until_renew"
								data-amount_selected_urls="<?php echo $groups_and_urls['amount_selected_urls']; ?>"
								data-auto_sc_per_url_until_renewal="<?php echo $total_sc_current_period; ?>"
							>
								<?php echo $account_details['available_compares']; ?> available until renewal
							</div>
						</div>
						<div class="clear"></div>
					</div>

					<?php $wcd->get_url_settings( $groups_and_urls, true ); ?>

				</div>

				<div class="sidebar">
					<div class="account-box">
						<?php include 'templates/account.php'; ?>
					</div>
					<div class="help-box">
						<?php include 'templates/help-auto.php'; ?>
					</div>
				</div>
				<div class="clear"></div>
				<?php
				break;

			/*********
			 * Logs
			 */

			case 'webchangedetector-logs':
				// Show queued urls
				$queues = $wcd->get_queue();

				$type_nice_name = array(
					'pre'     => 'Reference Screenshot',
					'post'    => 'Compare Screenshot',
					'auto'    => 'Monitoring',
					'compare' => 'Change Detection',
				);
				?>
				<div class="action-container">
				<?php
				echo '<table class="queue">';
				echo '<tr>
                        <th></th>
                        <th width="100%">Page & URL</th>
                        <th>Type</th>
                        <th>Status</th>
                        <th>Added</th>
                        <th>Last changed</th>
                        <th>Show</th>
                        </tr>';
				if ( ! empty( $queues ) && is_iterable( $queues ) ) {

					foreach ( $queues as $queue ) {
						$group_type = $queue['monitoring'] ? 'Monitoring' : 'Manual Checks';
						echo '<tr class="queue-status-' . $queue['status'] . '">';
						echo '<td>' . $wcd->get_device_icon( $queue['device'] ) . '</td>';
						echo '<td>
                                        <span class="html-title queue"> ' . $queue['url']['html_title'] . '</span><br>
                                        <span class="url queue">URL: ' . $queue['url']['url'] . '</span><br>
                                        ' . $group_type . '
                                </td>';
						echo '<td>' . $type_nice_name[ $queue['sc_type'] ] . '</td>';
						echo '<td>' . ucfirst( $queue['status'] ) . '</td>';
						echo '<td class="local-time" data-date="' . strtotime( $queue['created_at'] ) . '">' . gmdate( 'd/m/Y H:i:s', strtotime( $queue['created_at'] ) ) . '</td>';
						echo '<td class="local-time" data-date="' . strtotime( $queue['updated_at'] ) . '">' . gmdate( 'd/m/Y H:i:s', strtotime( $queue['updated_at'] ) ) . '</td>';
						echo '<td>';

						// Show screenshot button
						if ( in_array( $queue['sc_type'], array( 'pre', 'post', 'auto' ) ) &&
							$queue['status'] === 'done' &&
							! empty( $queue['screenshots'][0]['link'] ) ) {
							?>

									<form method="post" action="?page=webchangedetector-show-screenshot">
										<button class="button" type="submit" name="img_url" value="<?php echo $queue['screenshots'][0]['link']; ?>">Show</button>
									</form>

							<?php
						}
						// Show comparison
						elseif ( $queue['sc_type'] === 'compare' &&
							$queue['status'] === 'done' &&
							! empty( $queue['comparisons'][0]['token'] ) ) {
							?>

									<form method="post" action="?page=webchangedetector-show-detection">
										<button class="button" type="submit" name="token" value="<?php echo $queue['comparisons'][0]['token']; ?>">Show</button>
									</form>

								<?php
						}

						echo '</td>';
						echo '</tr>';
					}
				} else {
					echo '<tr><td colspan="7" style="text-align: center; font-weight: 700; background-color: #fff;">Nothing to show yet.</td></tr>';
				}
				?>

					</table>
					<?php
					$offset = $_GET['offset'] ?? 0;
					$limit  = $_GET['limit'] ?? $wcd::LIMIT_QUEUE_ROWS;
					?>
						<a class="button <?php echo ! $offset ? 'disabled' : ''; ?>"
							href="/wp-admin/admin.php?page=webchangedetector-logs&offset=<?php echo $offset - $limit; ?>&limit=<?php echo $limit; ?>"
						> < Newer
						</a>
						<a class="button <?php echo count( $queues ) !== $limit ? 'disabled' : ''; ?>"
							href="/wp-admin/admin.php?page=webchangedetector-logs&offset=<?php echo $offset + $limit; ?>&limit=<?php echo $limit; ?>"
						> Older >
						</a>
				</div>
				<div class="sidebar">
					<div class="account-box">
						<?php include 'templates/account.php'; ?>
					</div>
					<div class="help-box">
						<?php include 'templates/help-logs.php'; ?>
					</div>
				</div>
				<div class="clear"></div>
				<?php
				break;

			/***********
			 * Settings
			 */

			case 'webchangedetector-settings':
				?>
				<div class="action-container" style="text-align: center">
					<h2>Add Post Types</h2>
					<?php

					// Add post types
					$post_types = get_post_types( array( 'public' => true ), 'objects' );

					$available_post_types = array();
					foreach ( $post_types as $post_type ) {

						// if rest_base is not set we use post_name (wp default)
						if ( ! $post_type->rest_base ) {
							$post_type->rest_base = $post_type->name;
						}
						$show_type = false;
						foreach ( $wcd->website_details['sync_url_types'] as $sync_url_type ) {
							if ( $post_type->rest_base && $sync_url_type['post_type_slug'] === $post_type->rest_base ) {
								$show_type = true;
							}
						}
						if ( $post_type->rest_base && ! $show_type ) {
							$available_post_types[] = $post_type;
						}
					}
					if ( ! empty( $available_post_types ) ) {
						?>
						<form method="post">
							<input type="hidden" name="wcd_action" value="add_post_type">
							<select name="post_type">
						<?php
						foreach ( $available_post_types as $available_post_type ) {
							$add_post_type = json_encode(
								array(
									array(
										'url_type_slug'  => 'types',
										'url_type_name'  => 'Post Types',
										'post_type_slug' => $available_post_type->rest_base,
										'post_type_name' => $available_post_type->label,
									),
								)
							);
							?>
							<option value='<?php echo $add_post_type; ?>'><?php echo $available_post_type->label; ?></option>
						<?php } ?>
							</select>
							<input type="submit" class="button" value="Add">
						</form>
						<?php
					} else {
						?>
						<p>No more post types found</p>
					<?php } ?>

					<hr>

					<h2>Add Taxonomies</h2>
					<?php

					// Add Taxonomies
					$taxonomies = get_taxonomies( array( 'public' => true ), 'objects' );
					foreach ( $taxonomies as $taxonomy ) {

						// if rest_base is not set we use post_name (wp default)
						if ( ! $taxonomy->rest_base ) {
							$taxonomy->rest_base = $taxonomy->name;
						}
						$show_taxonomy = false;
						foreach ( $wcd->website_details['sync_url_types'] as $sync_url_type ) {
							if ( $taxonomy->rest_base && $sync_url_type['post_type_slug'] === $taxonomy->rest_base ) {
								$show_taxonomy = true;
							}
						}
						if ( $taxonomy->rest_base && ! $show_taxonomy ) {
							$available_taxonomies[] = $taxonomy;
						}
					}
					if ( ! empty( $available_taxonomies ) ) {
						?>
						<form method="post">
							<input type="hidden" name="wcd_action" value="add_post_type">
							<select name="post_type">
								<?php
								foreach ( $available_taxonomies as $available_taxonomy ) {
									$add_post_type = json_encode(
										array(
											array(
												'url_type_slug' => 'taxonomies',
												'url_type_name' => 'Taxonomies',
												'post_type_slug' => $available_taxonomy->rest_base,
												'post_type_name' => $available_taxonomy->label,
											),
										)
									);
									?>
									<option value='<?php echo $add_post_type; ?>'><?php echo $available_taxonomy->label; ?></option>
								<?php } ?>
							</select>
							<input type="submit" class="button" value="Add">
						</form>
						<?php
					} else {
						?>
						<p>No more taxonomies found</p>
					<?php } ?>
					<hr>
					<?php

					if ( ! get_option( WCD_WP_OPTION_KEY_API_TOKEN ) ) {
						echo '<div class="error notice">
                        <p>Please enter a valid API Token.</p>
                    </div>';
					} elseif ( ! $wcd->website_details['enable_limits'] ) {
						echo '<h2>Need more screenshots?</h2>';
						echo '<p>If you need more screenshots, please upgrade your account with the button below.</p>';
						echo '<a class="button" href="' . $wcd->get_upgrade_url() . '">Upgrade</a>';
					}
					echo $wcd->get_api_token_form( get_option( WCD_WP_OPTION_KEY_API_TOKEN ) );
					?>
				</div>
				<div class="sidebar">
					<div class="account-box">
						<?php include 'templates/account.php'; ?>
					</div>
				</div>
				<div class="clear"></div>
				<?php
				break;

			/***************
			 * Show compare
			 */
			case 'webchangedetector-show-detection':
				echo $wcd->get_comparison_by_token( $_POST );
				break;

			/***************
			 * Show screenshot
			 */
			case 'webchangedetector-show-screenshot':
				echo $wcd->get_screenshot( $_POST );
				break;

			default:
				// Should already be validated by VALID_WCD_ACTIONS
				break;

		} // switch

		echo '</div>'; // closing from div webchangedetector
		echo '</div>'; // closing wrap
	} // wcd_webchangedetector_init
} // function_exists
