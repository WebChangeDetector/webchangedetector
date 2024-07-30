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

	/**
	 * Init for plugin view
	 *
	 * @return bool|void
	 */
	function wcd_webchangedetector_init() {
		// Add a nonce for security and authentication.

		// Start view.
		echo '<div class="wrap">';
		echo '<div class="webchangedetector">';
		echo '<h1>WebChangeDetector</h1>';

		// Validate wcd_action and nonce.
		$wcd_action = null;
		$postdata   = array();
		if ( isset( $_POST['wcd_action'] ) ) {
			$wcd_action = sanitize_text_field( wp_unslash( $_POST['wcd_action'] ) );
			check_admin_referer( $wcd_action );
			if ( ! is_string( $wcd_action ) || ! in_array( $wcd_action, WebChangeDetector_Admin::VALID_WCD_ACTIONS, true ) ) {
				echo '<div class="error notice"><p>Ooops! There was an unknown action called. Please contact us.</p></div>';
				return;
			}
		}

		$wcd = new WebChangeDetector_Admin();

		// Unslash postdata.
		foreach ( $_POST as $key => $post ) {
			$key              = wp_unslash( $key );
			$post             = wp_unslash( $post );
			$postdata[ $key ] = $post;
		}

		// Actions without API Token needed.
		switch ( $wcd_action ) {
			case 'create_free_account':
				// Validate if all required fields were sent.
				if ( ! ( isset( $postdata['name_first'] ) && isset( $postdata['name_last'] ) && isset( $postdata['email'] ) && isset( $postdata['password'] ) ) ) {
					echo '<div class="notice notice-error"><p>Please fill all required fields.</p></div>';
					$wcd->get_no_account_page();
					return false;
				}

				$api_token = $wcd->create_free_account( $postdata );

				$success = $wcd->save_api_token( $postdata, $api_token );

				if ( ! $success ) {
					return false;
				}
				break;

			case 'reset_api_token':
				delete_option(WCD_WP_OPTION_KEY_API_TOKEN);
				break;

			case 're-add-api-token':
				if ( empty( $postdata['api_token'] ) ) {
					$wcd->get_no_account_page();
					return true;
				}

				$wcd->save_api_token( $postdata, $postdata['api_token'] );
				break;

			case 'save_api_token':
				if ( empty( $postdata['api_token'] ) ) {
					echo '<div class="notice notice-error"><p>No API Token given.</p></div>';
					$wcd->get_no_account_page();
					return false;
				}

				$wcd->save_api_token( $postdata, $postdata['api_token'] );
				break;
		}

		// Change api token option name from V1.0.7.
		if ( ! get_option( WCD_WP_OPTION_KEY_API_TOKEN ) && get_option( 'webchangedetector_api_key' ) ) {
			add_option( WCD_WP_OPTION_KEY_API_TOKEN, get_option( 'webchangedetector_api_key' ), '', false );
			delete_option( 'webchangedetector_api_key' );
		}

		// We still don't have an api_token.
		if ( ! get_option( WCD_WP_OPTION_KEY_API_TOKEN ) ) {
			$wcd->get_no_account_page();
			return false;
		}

		// Get the account details.
		$account_details = $wcd->account_details();

		// Check if plugin has to be updated.
		if ( 'update plugin' === $account_details ) {
			echo '<div class="notice notice-error"><p>There are major updates in our system which requires to update the plugin 
            WebChangeDetector. Please install the update at <a href="/wp-admin/plugins.php">Plugins</a>.</p></div>';
			wp_die();
		}

		// Check if account is activated and if the api key is authorized.
		if ( ! is_array( $account_details ) && ( 'activate account' === $account_details || 'unauthorized' === $account_details ) ) {
			$wcd->show_activate_account( $account_details );
			return false;
		}

		// Show error message if we didn't get response from API.
		if ( empty( $account_details ) ) {
			echo "<div style='margin: 0 auto; text-align: center;  width: 400px; padding: 20px; border: 1px solid #aaa'>
                    <h1>Oooops!</h1>
                    <p>Something went wrong. We're already working on the issue. <br>
                    Please check again later.</p>
                  </div>";
			exit;
		}

		// Show low credits.
		$usage_percent = (int) ( $account_details['usage'] / $account_details['sc_limit'] * 100 );

		if ( $usage_percent >= 100 ) {
			if ( $account_details['plan']['one_time'] ) { // Check for trial account.
				?>
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
			<div class="notice notice-warning"><p>You used <?php echo esc_html( $usage_percent ); ?>% of your checks.</p></div>
			<?php
		}

		// Set the website details class object.
		$wcd->set_website_details();

		$wcd->monitoring_group_uuid = ! empty( $wcd->website_details['auto_detection_group']['uuid'] ) ? $wcd->website_details['auto_detection_group']['uuid'] : null;
		$wcd->manual_group_uuid     = ! empty( $wcd->website_details['manual_detection_group']['uuid'] ) ? $wcd->website_details['manual_detection_group']['uuid'] : null;

		// If we (for whatever reason) don't get the uuids, take them from wp_option.
		if ( is_null( $wcd->monitoring_group_uuid ) || is_null( $wcd->manual_group_uuid ) ) {
			$group_uuids = get_option( 'wcd_website_groups' );
			if ( $group_uuids && $group_uuids['auto_detection_group'] && $group_uuids['manual_detection_group'] ) {
				$wcd->monitoring_group_uuid = $group_uuids['auto_detection_group'];
				$wcd->manual_group_uuid     = $group_uuids['manual_detection_group'];
			}
		}

		// TODO Replace those with V2.
		$wcd->group_id            = ! empty( $wcd->website_details['manual_detection_group_id'] ) ? $wcd->website_details['manual_detection_group_id'] : null;
		$wcd->monitoring_group_id = ! empty( $wcd->website_details['auto_detection_group_id'] ) ? $wcd->website_details['auto_detection_group_id'] : null;

		// If we don't have the website for any reason we show an error message.
		if ( empty( $wcd->website_details ) ) {
			?>
			<div class="notice notice-error">
				<br>Ooops! We couldn't find your settings. Please try reloading the page. <br>
				If the issue persists, please contact us.</p>
				<p>
					<form method="post">
						<input type="hidden" name="wcd_action" value="re-add-api-token">
						<?php wp_nonce_field( 're-add-api-token' ); ?>
						<input type="submit" value="Re-add website" class="button-primary">
					</form>
				</p>
			</div>
			<?php
			return false;
		}

		$monitoring_group_settings = null; // @TODO Can be deleted?

		// Perform actions.
		switch ( $wcd_action ) {
			case 'change_comparison_status':
				WebChangeDetector_API_V2::update_comparison_v2( $postdata['comparison_uuid'], $postdata['status'] );
				break;

			case 'add_post_type':
				$wcd->add_post_type( $postdata );
				$wcd->sync_posts();
				$post_type_name = json_decode( stripslashes( $postdata['post_type'] ), true )[0]['post_type_name'];
				echo '<div class="notice notice-success"><p>' . esc_html( $post_type_name ) . ' added.</p></div>';
				break;

			case 'update_detection_step':
				update_option( 'webchangedetector_update_detection_step', sanitize_text_field( $postdata['step'] ) );
				break;

			case 'take_screenshots':
				$sc_type = sanitize_text_field( $postdata['sc_type'] );

				if ( ! in_array( $sc_type, WebChangeDetector_Admin::VALID_SC_TYPES, true ) ) {
					echo '<div class="error notice"><p>Wrong Screenshot type.</p></div>';
					return false;
				}

				$results = $wcd->take_screenshot( $wcd->group_id, $sc_type );

				if ( $results && is_array( $results ) && 1 < count( $results ) && 'error' === $results[0] ) {
					echo '<div class="error notice"><p>' . esc_html( $results[1] ) . '</p></div>';
				} elseif ( 'pre' === $sc_type ) {
						update_option( WCD_OPTION_UPDATE_STEP_KEY, WCD_OPTION_UPDATE_STEP_PRE_STARTED );
				} elseif ( 'post' === $sc_type ) {
					update_option( WCD_OPTION_UPDATE_STEP_KEY, WCD_OPTION_UPDATE_STEP_POST_STARTED );
				}
				break;

			case 'save_group_settings':
				switch ( $postdata['save_settings'] ) {
					case 'post_urls_update_and_auto':
						$wcd->post_urls( $postdata, $wcd->website_details, true );

						// Get the depending group names before saving to avoid group name changes in webapp.
						$manual_group_name      = $wcd->get_urls_of_group( $wcd->website_details['manual_detection_group_id'] )['name'];
						$postdata['group_name'] = $manual_group_name;
						$wcd->update_settings( $postdata, $wcd->group_id );

						$auto_group_name             = $wcd->get_urls_of_group( $wcd->website_details['auto_detection_group_id'] )['name'];
						$postdata['group_name_auto'] = $auto_group_name;
						$wcd->update_monitoring_settings( $postdata, $wcd->monitoring_group_id );
						break;

					case 'post_urls':
						$wcd->post_urls( $postdata, $wcd->website_details, false );
						if ( ! empty( $postdata['monitoring'] ) && $postdata['monitoring'] ) {
							$wcd->update_monitoring_settings( $postdata, $wcd->monitoring_group_id );
						} else {
							$wcd->update_settings( $postdata, $wcd->group_id );
						}
						break;

					case 'save_update_settings_and_continue':
						$wcd->post_urls( $_POST, $wcd->website_details, false );
						$wcd->update_settings( $_POST, $wcd->group_id );

						// Update step in update detection.
						if ( ! empty( $_POST['step'] ) ) {
							update_option( WCD_OPTION_UPDATE_STEP_KEY, sanitize_text_field( wp_unslash( $_POST['step'] ) ) );
						}
						break;
				}
		}

		// Get updated account and website data.
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

		// Check for account status.
		if ( 'active' !== $account_details['status'] ) {

			// Set error message.
			$err_msg = 'cancelled';
			if ( ! empty( $account_details['status'] ) ) {
				$err_msg = $account_details['status'];
			}
			?>
			<div class="error notice">
				<h3>Your account was <?php echo esc_html( $err_msg ); ?></h3>
				<p>Please <a href="<?php echo esc_url( $wcd->get_upgrade_url() ); ?>">Upgrade</a> to re-activate your account.</p>
				<p>To use a different account, please reset the API token.
					<form method="post">
						<input type="hidden" name="wcd_action" value="reset_api_token">
						<?php wp_nonce_field( 'reset_api_token' ); ?>
						<input type="submit" value="Reset API token" class="button button-delete">
					</form>
				</p>
			</div>
			<?php
			return false;
		}

		// Get page to view.
		$tab = 'webchangedetector-dashboard'; // init.
		if ( isset( $_GET['page'] ) ) {
			// sanitize: lower-case with "-".
			$tab = sanitize_text_field( wp_unslash( $_GET['page'] ) );
		}

		// Check if website details are available.
		if ( empty( $wcd->website_details ) ) {
			?>
			<div class="error notice">
				<p>
					We couldn't find your website settings. Please reset the API token in
					settings and re-add your website with your API Token.
				</p><p>
					Your current API token is: <strong><?php echo esc_html( get_option( WCD_WP_OPTION_KEY_API_TOKEN ) ); ?></strong>.
				</p>
				<p>
					<form method="post">
						<input type="hidden" name="wcd_action" value="reset_api_token">
						<?php wp_nonce_field( 'reset_api_token' ); ?>
						<input type="hidden" name="api_token" value="<?php echo esc_html( get_option( WCD_WP_OPTION_KEY_API_TOKEN ) ); ?>">
						<input type="submit" value="Reset API token" class="button button-delete">
					</form>
				</p>
			</div>
			<?php
			return false;
		}

		$wcd->tabs();

		echo '<div style="margin-top: 30px;"></div>';

		// Account credits.
		$comp_usage         = $account_details['usage'];
		$limit              = $account_details['sc_limit'];
		$available_compares = $account_details['available_compares'];

		if ( $wcd->website_details['enable_limits'] ) {
			$account_details['usage']            = $comp_usage; // used in dashboard.
			$account_details['plan']['sc_limit'] = $limit; // used in dashboard.
		}

		// Renew date (used in template).
		$renew_date = strtotime( $account_details['renewal_at'] ); // used in account template.

		switch ( $tab ) {

			/********************
			 * Dashboard
			 */

			case 'webchangedetector':
				$wcd->get_dashboard_view( $account_details, $wcd->group_id, $wcd->monitoring_group_id );
				break;

			/********************
			 * Change Detections
			 */

			case 'webchangedetector-change-detections':
				$limit_days = null;
				if ( isset( $_POST['limit_days'] ) ) {
					$limit_days = sanitize_text_field( wp_unslash( $_POST['limit_days'] ) );
					if ( ! empty( $limit_days ) && ! is_numeric( $limit_days ) ) {
						echo '<div class="error notice"><p>Wrong limit_days.</p></div>';
						return false;
					}
				}
				$from = gmdate( 'Y-m-d', strtotime( '- 7 days' ) );
				if ( isset( $_POST['from'] ) ) {
					$from = sanitize_text_field( wp_unslash( $_POST['from'] ) );

					if ( empty( $from ) ) {
						echo '<div class="error notice"><p>Wrong limit_days.</p></div>';
						return false;
					}
				}
				$to = current_time( 'Y-m-d' );
				if ( isset( $_POST['to'] ) ) {
					$to = sanitize_text_field( wp_unslash( $_POST['to'] ) );
					if ( empty( $to ) ) {
						echo '<div class="error notice"><p>Wrong limit_days.</p></div>';
						return false;
					}
				}
				$group_type = null;
				if ( isset( $_POST['group_type'] ) ) {
					$group_type = sanitize_text_field( wp_unslash( $_POST['group_type'] ) );
					if ( ! empty( $group_type ) && ! in_array( $group_type, WebChangeDetector_Admin::VALID_GROUP_TYPES, true ) ) {
						echo '<div class="error notice"><p>Invalid group_type.</p></div>';
						return false;
					}
				}

				$difference_only = null;
				if ( isset( $_POST['difference_only'] ) ) {
					$difference_only = sanitize_text_field( wp_unslash( $_POST['difference_only'] ) );
				}

				?>
				<div class="action-container">
					<form method="post" style="margin-bottom: 20px;">
						<input type="hidden" name="wcd_action" value="filter_change_detections">
						<?php wp_nonce_field( 'filter_change_detections' ); ?>

						from <input name="from" value="<?php echo esc_html( $from ); ?>" type="date">
						to <input name="to" value="<?php echo esc_html( $to ); ?>" type="date">
						<select name="limit_days">
							<option value="" <?php echo null === $limit_days ? 'selected' : ''; ?>> Show all</option>
							<option value="3" <?php echo 3 === $limit_days ? 'selected' : ''; ?>>Last 3 days</option>
							<option value="7" <?php echo 7 === $limit_days ? 'selected' : ''; ?>>Last 7 days</option>
							<option value="14" <?php echo 14 === $limit_days ? 'selected' : ''; ?>>Last 14 days</option>
							<option value="30" <?php echo 30 === $limit_days ? 'selected' : ''; ?>>Last 30 days</option>
							<option value="60" <?php echo 60 === $limit_days ? 'selected' : ''; ?>>Last 60 days</option>
						</select>

						<select name="group_type" >
							<option value="" <?php echo ! $group_type ? 'selected' : ''; ?>>Monitoring & Manual Checks</option>
							<option value="update" <?php echo 'update' === $group_type ? 'selected' : ''; ?>>Only Manual Checks</option>
							<option value="auto" <?php echo 'auto' === $group_type ? 'selected' : ''; ?>>Only Monitoring</option>
						</select>

						<select name="difference_only" class="js-dropdown">
							<option value="0" <?php echo ! $difference_only ? 'selected' : ''; ?>>All detections</option>
							<option value="1" <?php echo $difference_only ? 'selected' : ''; ?>>With difference</option>
						</select>

						<input class="button" type="submit" value="Filter">
					</form>
					<?php

					// Show comparisons.

					$batches     = WebChangeDetector_API_V2::get_batches();
					$batches     = array_slice( $batches['data'], 0, 10 );
					$comparisons = array();

					// TODO Limit to X batches.
					foreach ( $batches as $batch ) {
						$filters = array(
							'from'            => gmdate( 'Y-m-d H:i:s', strtotime( $from ) ),
							'to'              => gmdate( 'Y-m-d H:i:s', strtotime( $to ) + 86400 - 1 ), // + 1 day - 1 second.
							'above_threshold' => $difference_only,
							'batch'           => $batch['id'],
							// TODO group_type.
						);

						$comparisons_of_batch = WebChangeDetector_API_V2::get_comparisons_v2( $filters )['data'];
						if ( ! empty( $comparisons_of_batch ) ) {
							$comparisons = array_merge( $comparisons, $comparisons_of_batch );
						}
					}
					$wcd->compare_view_v2( $comparisons );

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
			 * Manual Checks
			*/

			case 'webchangedetector-update-settings':
				if ( $wcd->website_details['enable_limits'] && ! $wcd->website_details['allow_manual_detection'] ) {
					echo 'Settings for Manual Checks are disabled by your API Token.';
					break;
				}

				// Get selected urls.
				$group_and_urls = $wcd->get_urls_of_group( $wcd->group_id );

				$step = false;
				// Show message if no urls are selected.
				if ( ! $group_and_urls['amount_selected_urls'] ) {
					$step = WCD_OPTION_UPDATE_STEP_SETTINGS
					?>
					<div class="notice notice-warning"><p>Select URLs for manual checks to get started.</p></div>
				<?php } ?>

				<div class="action-container">

				<?php
				// Check if we have a step in the db.
				if ( ! $step ) {
					$step = get_option( WCD_OPTION_UPDATE_STEP_KEY );
				}

				// Default step.
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
						$sc_processing             = $wcd->get_processing_queue(); // used in template.
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
						$sc_processing             = $wcd->get_processing_queue(); // used in template.
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
			 * Monitoring
			 */

			case 'webchangedetector-auto-settings':
				if ( $wcd->website_details['enable_limits'] && ! $wcd->website_details['allow_auto_detection'] ) {
					echo 'Settings for Manual Checks are disabled by your API Token.';
					break;
				}

				$group_and_urls = $wcd->get_urls_of_group( $wcd->monitoring_group_id );

				// Calculation for monitoring.
				$date_next_sc = false;
				$next_sc_in   = false;
				if ( $group_and_urls['monitoring'] ) {

					$amount_sc_per_day = 0;
					// Check for intervals >= 1h.
					if ( $group_and_urls['interval_in_h'] >= 1 ) {
						$next_possible_sc  = gmmktime( gmdate( 'H' ) + 1, 0, 0, gmdate( 'm' ), gmdate( 'd' ), gmdate( 'Y' ) );
						$amount_sc_per_day = ( 24 / $group_and_urls['interval_in_h'] );
						$possible_hours    = array();
						// Get possible tracking hours.
						for ( $i = 0; $i <= $amount_sc_per_day * 2; $i++ ) {
							$possible_hour    = $group_and_urls['hour_of_day'] + $i * $group_and_urls['interval_in_h'];
							$possible_hours[] = $possible_hour >= 24 ? $possible_hour - 24 : $possible_hour;
						}
						sort( $possible_hours );

						// Check for today and tomorrow.
						for ( $ii = 0; $ii <= 1; $ii++ ) { // Do 2 loops for today and tomorrow.
							for ( $i = 0; $i <= $amount_sc_per_day * 2; $i++ ) {
								$possible_time = gmmktime( $possible_hours[ $i ], 0, 0, gmdate( 'm' ), gmdate( 'd' ) + $ii, gmdate( 'Y' ) );

								if ( $possible_time >= $next_possible_sc ) {
									$date_next_sc = $possible_time; // This is the next possible time. So we break here.
									break;
								}
							}
							// Dont check for tomorrow if we found the next date today.
							if ( $date_next_sc ) {
								break;
							}
						}
					}

					// Check for 30 min intervals.
					if ( 0.5 === $group_and_urls['interval_in_h'] ) {
						$amount_sc_per_day = 48;
						if ( gmdate( 'i' ) < 30 ) {
							$date_next_sc = gmmktime( gmdate( 'H' ), 30, 0, gmdate( 'm' ), gmdate( 'd' ), gmdate( 'Y' ) );
						} else {
							$date_next_sc = gmmktime( gmdate( 'H' ) + 1, 0, 0, gmdate( 'm' ), gmdate( 'd' ), gmdate( 'Y' ) );
						}
					}
					// Check for 15 min intervals.
					if ( 0.25 === $group_and_urls['interval_in_h'] ) {
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

					// Calculate screenshots until renewal.
					$account = $wcd->account_details();

					$days_until_renewal      = gmdate( 'd', gmdate( 'U', strtotime( $account['renewal_at'] ) ) - gmdate( 'U' ) );
					$amount_group_sc_per_day = $group_and_urls['amount_selected_urls'] * $amount_sc_per_day * $days_until_renewal;

					// Get first detection hour.
					$first_hour_of_interval = $group_and_urls['hour_of_day'];
					while ( $first_hour_of_interval - $group_and_urls['interval_in_h'] >= 0 ) {
						$first_hour_of_interval = $first_hour_of_interval - $group_and_urls['interval_in_h'];
					}

					// Count up in interval_in_h to current hour.
					$skip_sc_count_today = 0;
					while ( $first_hour_of_interval + $group_and_urls['interval_in_h'] <= gmdate( 'H' ) ) {
						$first_hour_of_interval = $first_hour_of_interval + $group_and_urls['interval_in_h'];
						++$skip_sc_count_today;
					}

					// Subtract screenshots already taken today.
					$total_sc_current_period = $amount_group_sc_per_day - $skip_sc_count_today * $group_and_urls['amount_selected_urls'];
				}
				?>

				<div class="action-container">
					<div class="status_bar">
						<div class="box full">
							<div id="txt_next_sc_in">Next change detections in</div>
							<div id="next_sc_in" class="big"></div>
							<div id="next_sc_date" class="local-time" data-date="<?php echo esc_html( $date_next_sc ); ?>"></div>
						</div>

						<!-- @TODO: Calculation is wrong and hidden. Replace this with the one from the dashboard -->
						<div class="box half" style="display: none;">
							Current settings require
							<div id="sc_until_renew" class="big">
								<span id="ajax_amount_total_sc"></span> Checks
							</div>
							<div id="sc_available_until_renew"
								data-amount_selected_urls="<?php echo esc_html( $group_and_urls['amount_selected_urls'] ); ?>"
								data-auto_sc_per_url_until_renewal="<?php echo esc_html( $total_sc_current_period ); ?>"
							>
								<?php echo esc_html( $account_details['available_compares'] ); ?> available until renewal
							</div>
						</div>
						<div class="clear"></div>
					</div>

					<?php $wcd->get_url_settings( $group_and_urls, true ); ?>

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
				// Show queued urls.
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
                        <th style="width: 100%">Page & URL</th>
                        <th>Type</th>
                        <th>Status</th>
                        <th>Added</th>
                        <th>Last changed</th>
                        <th>Show</th>
                        </tr>';
				if ( ! empty( $queues ) && is_iterable( $queues ) ) {

					foreach ( $queues as $queue ) {
						$group_type = $queue['monitoring'] ? 'Monitoring' : 'Manual Checks';
						echo '<tr class="queue-status-' . esc_html( $queue['status'] ) . '">';
						echo '<td>';
						$wcd->get_device_icon( $queue['device'] );
						echo '</td>';
						echo '<td>
                                        <span class="html-title queue"> ' . esc_html( $queue['url']['html_title'] ) . '</span><br>
                                        <span class="url queue">URL: ' . esc_url( $queue['url']['url'] ) . '</span><br>
                                        ' . esc_html( $group_type ) . '
                                </td>';
						echo '<td>' . esc_html( $type_nice_name[ $queue['sc_type'] ] ) . '</td>';
						echo '<td>' . esc_html( ucfirst( $queue['status'] ) ) . '</td>';
						echo '<td class="local-time" data-date="' . esc_html( strtotime( $queue['created_at'] ) ) . '">' .
							esc_html( gmdate( 'd/m/Y H:i:s', strtotime( $queue['created_at'] ) ) ) . '</td>';
						echo '<td class="local-time" data-date="' . esc_html( strtotime( $queue['updated_at'] ) ) . '">' .
							esc_html( gmdate( 'd/m/Y H:i:s', strtotime( $queue['updated_at'] ) ) ) . '</td>';
						echo '<td>';

						// Show screenshot button.
						if ( in_array( $queue['sc_type'], array( 'pre', 'post', 'auto' ), true ) &&
							'done' === $queue['status'] &&
							! empty( $queue['screenshots'][0]['link'] ) ) {
							?>
							<form method="post" action="?page=webchangedetector-show-screenshot">
								<button class="button" type="submit" name="img_url" value="<?php echo esc_url( $queue['screenshots'][0]['link'] ); ?>">Show</button>
							</form>
							<?php

							// Show comparison.
						} elseif ( 'compare' === $queue['sc_type'] &&
								'done' === $queue['status'] &&
								! empty( $queue['comparisons'][0]['token'] ) ) {
							?>
							<form method="post" action="?page=webchangedetector-show-detection">
								<button class="button" type="submit" name="token" value="<?php echo esc_html( $queue['comparisons'][0]['token'] ); ?>">Show</button>
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
					$offset = isset( $_GET['offset'] ) ? wp_unslash( sanitize_key( $_GET['offset'] ) ) : 0;
					$limit  = isset( $_GET['limit'] ) ? wp_unslash( sanitize_key( $_GET['limit'] ) ) : $wcd::LIMIT_QUEUE_ROWS;
					?>
					<a class="button <?php echo ! $offset ? 'disabled' : ''; ?>"
						href="/wp-admin/admin.php?page=webchangedetector-logs
						&offset=<?php echo esc_html( $offset - $limit ); ?>
						&limit=<?php echo esc_html( $limit ); ?>"
					> < Newer
					</a>
					<a class="button <?php echo count( $queues ) !== $limit ? 'disabled' : ''; ?>"
						href="/wp-admin/admin.php?page=webchangedetector-logs
						&offset=<?php echo esc_html( $offset + $limit ); ?>
						&limit=<?php echo esc_html( $limit ); ?>"
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

					// Add post types.
					$post_types = get_post_types( array( 'public' => true ), 'objects' );

					$available_post_types = array();
					foreach ( $post_types as $post_type ) {

						// if rest_base is not set we use post_name (wp default).
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
							<?php wp_nonce_field( 'add_post_type' ); ?>
							<select name="post_type">
						<?php
						foreach ( $available_post_types as $available_post_type ) {
							$add_post_type = wp_json_encode(
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
							<option value='<?php echo esc_html( $add_post_type ); ?>'><?php echo esc_html( $available_post_type->label ); ?></option>
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

					// Add Taxonomies.
					$taxonomies = get_taxonomies( array( 'public' => true ), 'objects' );
					foreach ( $taxonomies as $taxonomy ) {

						// if rest_base is not set we use post_name (wp default).
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
							<?php wp_nonce_field( 'add_post_type' ); ?>
							<select name="post_type">
								<?php
								foreach ( $available_taxonomies as $available_taxonomy ) {
									$add_post_type = wp_json_encode(
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
									<option value='<?php echo esc_html( $add_post_type ); ?>'><?php echo esc_html( $available_taxonomy->label ); ?></option>
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
						echo '<a class="button" href="' . esc_url( $wcd->get_upgrade_url() ) . '">Upgrade</a>';
					}
					$wcd->get_api_token_form( get_option( WCD_WP_OPTION_KEY_API_TOKEN ) );
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
				$wcd->get_comparison_by_token( $postdata );
				break;

			/***************
			 * Show screenshot
			 */
			case 'webchangedetector-show-screenshot':
				$wcd->get_screenshot( $postdata );
				break;

			default:
				// Should already be validated by VALID_WCD_ACTIONS.
				break;

		} // switch

		echo '</div>'; // closing from div webchangedetector.
		echo '</div>'; // closing wrap.
	} // wcd_webchangedetector_init.
} // function_exists.
