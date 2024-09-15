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
		echo '<h1>WebChange Detector</h1>';

		// Validate wcd_action and nonce.
		$postdata   = array();
		$wcd_action = null;
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
				$success   = $wcd->save_api_token( $postdata, $api_token );

				if ( ! $success ) {
					return false;
				}
				break;

			case 'reset_api_token':
				delete_option( WCD_WP_OPTION_KEY_API_TOKEN );
				delete_option( WCD_WEBSITE_GROUPS );
				delete_option( WCD_OPTION_UPDATE_STEP_SETTINGS );
				delete_option( WCD_AUTO_UPDATE_SETTINGS );
                delete_option( WCD_ALLOWANCES );
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
		$account_details = $wcd->get_account( true );

		// Show error message if we didn't get response from API.
		if ( empty( $account_details ) ) { ?>
            <div class="notice notice-error">
                <p>
                    Something went wrong. Please try to re-add your api token.
                    <form method="post">
                        <input type="hidden" name="wcd_action" value="reset_api_token">
                        <?php wp_nonce_field( 'reset_api_token' ); ?>
                        <input type="submit" value="Reset API token" class="button button-delete">
                    </form>
                </p>
            </div>
			<?php
			return;
		}

		// Check if plugin has to be updated.
		if ( 'update plugin' === $account_details ) { ?>
			<div class="notice notice-error">
                <p>
                    There are major updates in our system which requires to update the plugin
                    WebChangeDetector. Please install the update at <a href="/wp-admin/plugins.php">Plugins</a>.
                </p>
            </div>
            <?php
			return;
		}

		// Check if account is activated and if the api key is authorized.
		if ( 'ActivateAccount' === $account_details || 'unauthorized' === $account_details ) {
			$wcd->show_activate_account( $account_details );
			return false;
		}

		// Get website details and create them if we don't have them yet.
		$wcd->website_details = $wcd->get_website_details()[0] ?? false;
		if ( ! $wcd->website_details ) {
			$success              = $wcd->create_website_and_groups();
			$wcd->website_details = $wcd->get_website_details()[0] ?? false;

            // Check if we are only allowed to sync the frontpage.
            if($wcd->is_allowed('only_frontpage')) {
	            $wcd->set_default_sync_types(true);
            } else {
			    $wcd->set_default_sync_types();
            }

            // Make the inital post sync
			$urls = $wcd->sync_posts( true );

            // If only the frontpage is allowed, we activate the URLs.
			if($wcd->is_allowed('only_frontpage')) {
                if(!empty($urls[0])) {
                    $update_urls = [
                            'desktop-'.$urls[0]['url_id'] => 1,
                            'mobile-'.$urls[0]['url_id'] => 1,
                            'group_id' => $wcd->website_details['manual_detection_group']['uuid'],
                    ];
	                $wcd->post_urls($update_urls);
                }
			}

            // Set default auto-update settings
            $auto_update_settings = get_option(WCD_AUTO_UPDATE_SETTINGS);

			if ( ! $auto_update_settings ) { // Set defaults.
				$auto_update_settings['auto_update_checks_enabled']   = '';
				$auto_update_settings['auto_update_checks_from']      = '08:00';
				$auto_update_settings['auto_update_checks_to']        = '20:00';
				$auto_update_settings['auto_update_checks_monday']    = 'on';
				$auto_update_settings['auto_update_checks_tuesday']   = 'on';
				$auto_update_settings['auto_update_checks_wednesday'] = 'on';
				$auto_update_settings['auto_update_checks_thursday']  = 'on';
				$auto_update_settings['auto_update_checks_friday']    = 'on';
				$auto_update_settings['auto_update_checks_saturday']  = '';
				$auto_update_settings['auto_update_checks_sunday']    = '';
				$auto_update_settings['auto_update_checks_emails']    = get_option( 'admin_email' );
                update_option(WCD_AUTO_UPDATE_SETTINGS, $auto_update_settings);
			}
		}

		// We can't get the website. So we exit with an error.
		if ( empty( $wcd->website_details ) ) { ?>
			<div class="notice notice-error">
                <p>
                    Sorry, we couldn't find your website details. Please contact us at
                    <a href="mailto:support@webchangedetector.com">support@webchangedetector.com</a>.
                </p>
            </div>
            <?php
			return;
		}

		// Save the allowances to the db. We need this for the navigation.
		if ( ! empty( $wcd->website_details['allowances'] ) ) {
            update_option( WCD_ALLOWANCES, $wcd->website_details['allowances']);
		}

		// Get the groups.
		$groups = get_option( WCD_WEBSITE_GROUPS );

		if ( empty( $groups ) || empty( $groups['auto_detection_group'] ) || empty( $groups['manual_detection_group'] ) ) {
			$groups = array(
				'auto_detection_group'   => $wcd->website_details['auto_detection_group']['uuid'] ?? false,
				'manual_detection_group' => $wcd->website_details['manual_detection_group']['uuid'] ?? false,
			);
			update_option( WCD_WEBSITE_GROUPS, $groups, false );

		}

		$wcd->monitoring_group_uuid = $groups['auto_detection_group'] ?? false;
		$wcd->manual_group_uuid     = $groups['manual_detection_group'] ?? false;

		if ( ! $wcd->manual_group_uuid || ! $wcd->monitoring_group_uuid ) {
			echo '<div class="notice notice-error"><p>Sorry, we couldn\'t get your URL settings. Please contact us.</p></div>';
			return;
		}

		// Show low credits.
		$usage_percent = 0;
		if ( $account_details['checks_limit'] > 0 ) {
			$usage_percent = (int) ( $account_details['checks_done'] / $account_details['checks_limit'] * 100 );
		}
		if ( $usage_percent >= 100 ) {
			?>
			<div class="notice notice-error">
				<p><strong>WebChange Detector:</strong> You ran out of checks. Please upgrade your account to continue.</p>
			</div>
		<?php } elseif ( $usage_percent > 70 ) { ?>
			<div class="notice notice-warning"><p><strong>WebChange Detector:</strong> You used <?php echo esc_html( $usage_percent ); ?>% of your checks.</p></div>
			<?php
		}

		// If we don't have the website for any reason we show an error message.
		if ( empty( $wcd->website_details ) ) {

			?>
			<div class="notice notice-error">
				<p>Ooops! We couldn't find your settings. Please try reloading the page.
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

		// If we hit the max input vars, show error message.
		$php_max_input_vars = ini_get( 'max_input_vars' );
		if ( count( $postdata ) >= $php_max_input_vars ) {
			?>
			<div class="notice notice-error">
				<p><strong>ERROR:</strong> Increase max_input_vars in your PHP settings. Current value: <?php echo esc_html( $php_max_input_vars ); ?></p>
			</div>
			<?php
		}

		// Perform actions.
		switch ( $wcd_action ) {
			case 'enable_wizard':
				add_option( 'wcd_wizard', 'true', '', false );
				break;

			case 'disable_wizard':
				delete_option( 'wcd_wizard' );
				break;

			case 'change_comparison_status':
				WebChangeDetector_API_V2::update_comparison_v2( $postdata['comparison_id'], $postdata['status'] );
				break;

			case 'add_post_type':
				$wcd->add_post_type( $postdata );
				$post_type_name = json_decode( stripslashes( $postdata['post_type'] ), true )[0]['post_type_name'];
				echo '<div class="notice notice-success"><p>' . esc_html( $post_type_name ) . ' added.</p></div>';
				break;

			case 'update_detection_step':
				update_option( WCD_OPTION_UPDATE_STEP_KEY, sanitize_text_field( $postdata['step'] ) );
				break;

			case 'take_screenshots':
				$sc_type = sanitize_text_field( $postdata['sc_type'] );

				if ( ! in_array( $sc_type, WebChangeDetector_Admin::VALID_SC_TYPES, true ) ) {
					echo '<div class="error notice"><p>Wrong Screenshot type.</p></div>';
					return false;
				}

				$results = WebChangeDetector_API_V2::take_screenshot_v2( $wcd->manual_group_uuid, $sc_type );
				if ( isset( $results['batch'] ) ) {
					update_option( 'wcd_manual_checks_batch', $results['batch'] );
					if ( 'pre' === $sc_type ) {
						update_option( WCD_OPTION_UPDATE_STEP_KEY, WCD_OPTION_UPDATE_STEP_PRE_STARTED );
					} elseif ( 'post' === $sc_type ) {
						update_option( WCD_OPTION_UPDATE_STEP_KEY, WCD_OPTION_UPDATE_STEP_POST_STARTED );
					}
				} else {
					echo '<div class="error notice"><p>' . $results['message'] . '</p></div>';
				}
				break;

			case 'save_group_settings':
				if ( ! empty( $postdata['monitoring'] ) ) {
					$wcd->update_monitoring_settings( $postdata );
				} else {
					$wcd->update_manual_check_group_settings( $postdata );
				}
				break;

			case 'start_manual_checks':
				// Update step in update detection.
				if ( ! empty( $postdata['step'] ) ) {
					update_option( WCD_OPTION_UPDATE_STEP_KEY, ( $postdata['step'] ) );
				}
				break;
		}

		// Get updated account and website data.
		$account_details = $wcd->get_account();

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

		// Account credits.
		$comp_usage         = $account_details['checks_done'];
		$limit              = $account_details['checks_limit'];
		$available_compares = $account_details['checks_left'];

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
                $wcd->get_dashboard_view( $account_details );
				break;

			/********************
			 * Change Detections
			 */

			case 'webchangedetector-change-detections':
				if ( ! $wcd->is_allowed( 'change_detections_view' ) ) {
					break;
				}

				$from = gmdate( 'Y-m-d', strtotime( '- 7 days' ) );
				if ( isset( $_GET['from'] ) ) {
					$from = sanitize_text_field( wp_unslash( $_GET['from'] ) );
					if ( empty( $from ) ) {
						echo '<div class="error notice"><p>Wrong limit_days.</p></div>';
						return false;
					}
				}

				$to = current_time( 'Y-m-d' );
				if ( isset( $_GET['to'] ) ) {
					$to = sanitize_text_field( wp_unslash( $_GET['to'] ) );
					if ( empty( $to ) ) {
						echo '<div class="error notice"><p>Wrong limit_days.</p></div>';
						return false;
					}
				}

				$group_type = false;
				if ( isset( $_GET['group_type'] ) ) {
					$group_type = sanitize_text_field( wp_unslash( $_GET['group_type'] ) );
					if ( ! empty( $group_type ) && ! in_array( $group_type, WebChangeDetector_Admin::VALID_GROUP_TYPES, true ) ) {
						echo '<div class="error   notice"><p>Invalid group_type.</p></div>';
						return false;
					}
				}

				$status = false;
				if ( isset( $_GET['status'] ) ) {
					$status = sanitize_text_field( wp_unslash( $_GET['status'] ) );
					if ( ! empty( $status ) && ! empty( array_diff( explode( ',', $status ), WebChangeDetector_Admin::VALID_COMPARISON_STATUS ) ) ) {
						echo '<div class="error notice"><p>Invalid status.</p></div>';
						return false;
					}
				}

				$difference_only = false;
				if ( isset( $_GET['difference_only'] ) ) {
					$difference_only = sanitize_text_field( wp_unslash( $_GET['difference_only'] ) );
				}
				?>

				<div class="action-container">

					<form method="get" style="margin-bottom: 20px;">
						<input type="hidden" name="page" value="webchangedetector-change-detections">

						from <input name="from" value="<?php echo esc_html( $from ); ?>" type="date">
						to <input name="to" value="<?php echo esc_html( $to ); ?>" type="date">

						<select name="group_type" >
							<option value="" <?php echo ! $group_type ? 'selected' : ''; ?>>All Checks</option>
							<option value="post" <?php echo 'post' === $group_type ? 'selected' : ''; ?>>Manual Checks & Auto Update Checks</option>
							<option value="auto" <?php echo 'auto' === $group_type ? 'selected' : ''; ?>>Monitoring Checks</option>
						</select>
						<select name="status" class="js-dropdown">
							<option value="" <?php echo ! $status ? 'selected' : ''; ?>>All Status</option>
							<option value="new" <?php echo 'new' === $status ? 'selected' : ''; ?>>New</option>
							<option value="ok" <?php echo 'ok' === $status ? 'selected' : ''; ?>>Ok</option>
							<option value="to_fix" <?php echo 'to_fix' === $status ? 'selected' : ''; ?>>To Fix</option>
							<option value="false_positive" <?php echo 'false_positive' === $status ? 'selected' : ''; ?>>False Positive</option>
						</select>
						<select name="difference_only" class="js-dropdown">
							<option value="0" <?php echo ! $difference_only ? 'selected' : ''; ?>>All detections</option>
							<option value="1" <?php echo $difference_only ? 'selected' : ''; ?>>With difference</option>
						</select>

						<input class="button" type="submit" value="Filter">
					</form>

					<?php
					$wizard_text = '<h2>Change Detections</h2>In this tab, you will see all your change detections.';
					$wcd->print_wizard(
						$wizard_text,
						'wizard_change_detection_tab',
						'wizard_change_detection_batches',
						false,
						true,
						'top top-minus-50 left-plus-500'
					);

					$extra_filters          = array();
					$extra_filters['paged'] = isset( $_GET['paged'] ) ? sanitize_key( wp_unslash( $_GET['paged'] ) ) : 1;

					// Show comparisons.
					$filter_batches = array(
						'page'     => $extra_filters['paged'],
						'per_page' => 5,
						'from'     => gmdate( 'Y-m-d', strtotime( $from ) ),
						'to'       => gmdate( 'Y-m-d', strtotime( $to ) ),
					);

					if ( $group_type ) {
						$extra_filters['queue_type'] = $group_type;
					} else {
						$extra_filters['queue_type'] = 'post,auto';
					}

					if ( $status ) {
						$extra_filters['status'] = $status;
					} else {
						$extra_filters['status'] = 'new,ok,to_fix,false_positive';
					}
					if ( $difference_only ) {
						$extra_filters['above_threshold'] = (bool) $difference_only;
					}

					$batches                       = WebChangeDetector_API_V2::get_batches( array_merge( $filter_batches, $extra_filters ) );
					$filter_batches_in_comparisons = array();
					foreach ( $batches['data'] as $batch ) {
						$filter_batches_in_comparisons[] = $batch['id'];
					}

					$filters_comparisons = array(
						'batches'  => implode( ',', $filter_batches_in_comparisons ),
						'per_page' => 999999,
					);

					$comparisons = WebChangeDetector_API_V2::get_comparisons_v2( array_merge( $filters_comparisons, $extra_filters ) );

					$wizard_text = '<h2>The Change Detections</h2>You see all change detections in these accordions. 
			                They are grouped by the type: Monitoring, Manual Checks or Auto Update Checks';
					$wcd->print_wizard(
						$wizard_text,
						'wizard_change_detection_batches',
						false,
						'?page=webchangedetector-logs',
						false,
						'top top-plus-100 left-plus-300'
					);
					$wcd->compare_view_v2( $comparisons['data'] );

					// Prepare pagination.
					unset( $extra_filters['paged'] );
					unset( $filter_batches['page'] );
					$pagination_filters = array_merge( $filter_batches, $extra_filters );
					$pagination         = $batches['meta'];
					?>
					<!-- Pagination -->
					<div class="tablenav">
						<div class="tablenav-pages">
							<span class="displaying-num"><?php echo esc_html( $pagination['total'] ); ?> items</span>
							<span class="pagination-links">
								<?php
								foreach ( $pagination['links'] as $link ) {
									$params = $wcd->get_params_of_url( $link['url'] );
									$class  = ! $link['url'] || $link['active'] ? 'disabled' : '';
									?>
									<a class="tablenav-pages-navspan button <?php echo esc_html( $class ); ?>"
										href="?page=webchangedetector-change-detections&
										paged=<?php echo esc_html( $params['page'] ?? 1 ); ?>&
										<?php echo esc_html( build_query( $pagination_filters ) ); ?>" >
											<?php echo esc_html( $link['label'] ); ?>
									</a>
									<?php
								}
								?>
							</span>
						</div>
					</div>
				</div>
				<div class="clear"></div>

				<?php
				break;

			/***************************
			 * Manual Checks
			*/

			case 'webchangedetector-update-settings':
				if ( ! $wcd->is_allowed( 'manual_checks_view' ) ) {
					break;
				}
				// Disable settings if this website has no permissions.
				if ( $wcd->website_details['enable_limits'] && ! $wcd->website_details['allow_manual_detection'] ) {
					echo 'Settings for Manual Checks are disabled by your API Token.';
					break;
				}

				// Check if we have a step in the db.
				$step = get_option( WCD_OPTION_UPDATE_STEP_KEY );
				if ( ! $step ) {
					$step = WCD_OPTION_UPDATE_STEP_SETTINGS;
				}
				update_option( WCD_OPTION_UPDATE_STEP_KEY, sanitize_text_field( $step ), false );

				?>
				<div class="action-container">
				<?php

				switch ( $step ) {
					case WCD_OPTION_UPDATE_STEP_SETTINGS:
						$progress_setting          = 'active';
						$progress_pre              = 'disabled';
						$progress_make_update      = 'disabled';
						$progress_post             = 'disabled';
						$progress_change_detection = 'disabled';
						$wcd->get_url_settings( false );
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
						$sc_processing             = $wcd->get_processing_queue_v2(); // used in template.
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
						$sc_processing             = $wcd->get_processing_queue_v2(); // used in template.
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

				<div class="clear"></div>
				<?php
				break;

			/**************************
			 * Monitoring
			 */

			case 'webchangedetector-auto-settings':
				$wizard_text = '<h2>Monitoring</h2>The monitoring checks your webpages automatically in intervals.';
				$wcd->print_wizard(
					$wizard_text,
					'wizard_monitoring_tab',
					'wizard_monitoring_settings',
					false,
					true,
					'top left-plus-300'
				);
				if ( $wcd->website_details['enable_limits'] && ! $wcd->website_details['allow_auto_detection'] ) {
					echo 'Settings for Manual Checks are disabled by your API Token.';
					break;
				}
				?>
				<div class="action-container">
				<?php
				$wcd->get_url_settings( true );
				?>
				</div>
				<div class="clear"></div>
				<?php
				break;

			/*********
			 * Queue
			 */

			case 'webchangedetector-logs':
				$wizard_text = '<h2>Queue</h2>In the queue you can see all the action which happened.';
				$wcd->print_wizard(
					$wizard_text,
					'wizard_logs_tab',
					'wizard_logs_log',
					false,
					true,
					'top left-plus-650'
				);

				$paged = 1;
				if ( isset( $_GET['paged'] ) ) {
					$paged = sanitize_key( wp_unslash( $_GET['paged'] ) );
				}

				$queues      = WebChangeDetector_API_V2::get_queue_v2( false, false, array( 'page' => $paged ) );
				$queues_meta = $queues['meta'];
				$queues      = $queues['data'];

				$type_nice_name = array(
					'pre'     => 'Pre-update screenshot',
					'post'    => 'Post-update screenshot',
					'auto'    => 'Monitoring screenshot',
					'compare' => 'Change detection',
				);

				$wizard_text = '<h2>Queue</h2>Every Screenshot and every comparison is listed here. 
                                If something failed, you can see it here too.';
				$wcd->print_wizard(
					$wizard_text,
					'wizard_logs_log',
					false,
					'?page=webchangedetector-settings',
					false,
					'bottom top-minus-50 left-plus-500'
				);
				?>

				<div class="action-container">
					<table class="queue">
						<tr>
							<th></th>
							<th style="width: 100%">Page & URL</th>
							<th style="min-width: 150px;">Type</th>
							<th>Status</th>
							<th style="min-width: 120px;">Time added /<br> Time updated</th>
							<th>Show</th>
						</tr>
					<?php
					if ( ! empty( $queues ) && is_iterable( $queues ) ) {

						foreach ( $queues as $queue ) {
							$group_type = $queue['monitoring'] ? 'Monitoring' : 'Manual Checks';
							echo '<tr class="queue-status-' . esc_html( $queue['status'] ) . '">';
							echo '<td>';
							$wcd->get_device_icon( $queue['device'] );
							echo '</td>';
							echo '<td>
                                            <span class="html-title queue"> ' . esc_html( $queue['html_title'] ) . '</span><br>
                                            <span class="url queue">URL: ' . esc_url( $queue['url_link'] ) . '</span><br>
                                            ' . esc_html( $group_type ) . '
                                    </td>';
							echo '<td>' . esc_html( $type_nice_name[ $queue['sc_type'] ] ) . '</td>';
							echo '<td>' . esc_html( ucfirst( $queue['status'] ) ) . '</td>';
							echo '<td><span class="local-time" data-date="' . esc_html( strtotime( $queue['created_at'] ) ) . '">' .
								esc_html( gmdate( 'd/m/Y H:i:s', strtotime( $queue['created_at'] ) ) ) . '</span><br>';
							echo '<span class="local-time" data-date="' . esc_html( strtotime( $queue['updated_at'] ) ) . '">' .
								esc_html( gmdate( 'd/m/Y H:i:s', strtotime( $queue['updated_at'] ) ) ) . '</span></td>';
							echo '<td>';

							// Show screenshot button.
							if ( in_array( $queue['sc_type'], array( 'pre', 'post', 'auto', 'compare' ), true ) &&
								'done' === $queue['status'] &&
								! empty( $queue['image_link'] ) ) {
								?>
								<form method="post" action="?page=webchangedetector-show-screenshot">
									<button class="button" type="submit" name="img_url" value="<?php echo esc_url( $queue['image_link'] ); ?>">Show</button>
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
					<!-- Pagination -->
					<div class="tablenav">
						<div class="tablenav-pages">
							<span class="displaying-num"><?php echo esc_html( $queues_meta['total'] ); ?> items</span>
							<span class="pagination-links">
							<?php
							if ( ! isset( $_GET['paged'] ) ) {
								$_GET['paged'] = 1;
							}
							foreach ( $queues_meta['links'] as $link ) {
								$url_params = $wcd->get_params_of_url( $link['url'] );

								if ( $url_params && ! empty( $url_params['page'] ) && sanitize_key( wp_unslash( $_GET['paged'] ) ) !== $url_params['page'] ) {
									?>
									<a class="tablenav-pages-navspan button" href="?page=webchangedetector-logs&paged=<?php echo esc_html( $url_params['page'] ); ?>">
										<?php echo esc_html( $link['label'] ); ?>
									</a>
								<?php } else { ?>
									<span class="tablenav-pages-navspan button" disabled=""><?php echo esc_html( $link['label'] ); ?></span>
									<?php
								}
							}
							?>
							</span>
						</div>
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
				<div class="action-container">

					<div class="box-plain no-border">
					<?php
					$wizard_text = '<h2>Settings</h2>In this tab, you can find some more settings.';
						$wcd->print_wizard(
							$wizard_text,
							'wizard_settings_tab',
							'wizard_settings_add_post_type',
							false,
							true,
							'top left-plus-700'
						);

						$wizard_text = '<h2>Upgrade for more checks</h2><p>If you run out of checks, you can upgrade your account here.</p>
                                        Plans with 1000 checks / month start already at $7 per month.</p>';
						$wcd->print_wizard(
							$wizard_text,
							'wizard_settings_upgrade',
							'wizard_settings_finished',
							false,
							false,
							'top left-plus-800'
						);
					?>
					<h2>Show URLs from post types</h2>
						<p>Missing URLs to switch on for checking? Show additional post types in the URL list here.</p>
					<?php
					$wizard_text = '<h2>Questions?</h2><p>We hope this wizard was helpful to understand how WebChange Detector works.</p><p>
                                    If you have any questions, please write us an email to <a href="mailto:support@webchangedetector.com">support@webchangedetector.com</a> or create a ticket 
                                    at our plugin site at <a href="https://wordpress.org/plugins/webchangedetector" target="_blank">wordpress.org</a>.</p>';
						$wcd->print_wizard(
							$wizard_text,
							'wizard_settings_finished',
							false,
							false,
							false,
							' left-plus-400'
						);

					// Add post types.
					$post_types = get_post_types( array( 'public' => true ), 'objects' );

					$available_post_types = array();
					foreach ( $post_types as $post_type ) {

						$wp_post_type_slug = $wcd->get_post_type_slug( $post_type );

						$show_type = false;
						foreach ( $wcd->website_details['sync_url_types'] as $sync_url_type ) {
							if ( $wp_post_type_slug && $sync_url_type['post_type_slug'] === $wp_post_type_slug ) {
								$show_type = true;
							}
						}
						if ( $wp_post_type_slug && ! $show_type ) {
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
							$current_post_type_slug = $wcd->get_post_type_slug( $available_post_type );
							$current_post_type_name = $wcd->get_post_type_name( $current_post_type_slug );
							$add_post_type          = wp_json_encode(
								array(
									array(
										'url_type_slug'  => 'types',
										'url_type_name'  => 'Post Types',
										'post_type_slug' => $current_post_type_slug,
										'post_type_name' => $current_post_type_name,
									),
								)
							);
							?>
							<option value='<?php echo esc_html( $add_post_type ); ?>'><?php echo esc_html( $available_post_type->label ); ?></option>
						<?php } ?>
							</select>
							<input type="submit" class="button" value="Show">
						</form>
						<?php

					} else {
						?>
						<p>No more post types found</p>
						<?php
					}

					$wizard_text = '<h2>Show more URLs</h2>If you are missing URLs to select for the checks, you can show them here.
                                        They will appear in the URL settings in the \'Manual Checks\' and the \' Monitoring\' tab.';
					$wcd->print_wizard(
						$wizard_text,
						'wizard_settings_add_post_type',
						'wizard_settings_account_details',
						false,
						false,
						'left top-minus-100 left-plus-400'
					);
				?>
					</div>

					<div class="box-plain no-border">
						<h2>Show URLs from taxonomies</h2>
						<p>Missing taxonomies like categories or tags? Select them here and they appear in the URL list to select for the checks.</p>
						<?php

						// Add Taxonomies.
						$taxonomies = get_taxonomies( array( 'public' => true ), 'objects' );
						foreach ( $taxonomies as $taxonomy ) {
							$wp_taxonomy_slug = $wcd->get_taxonomy_slug( $taxonomy );
							$show_taxonomy    = false;
							foreach ( $wcd->website_details['sync_url_types'] as $sync_url_type ) {
								if ( $wp_taxonomy_slug && $sync_url_type['post_type_slug'] === $wp_taxonomy_slug ) {
									$show_taxonomy = true;
								}
							}
							if ( $wp_taxonomy_slug && ! $show_taxonomy ) {
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
										$current_taxonomy_slug = $wcd->get_post_type_slug( $available_taxonomy );
										$current_taxonomy_name = $wcd->get_taxonomy_name( $current_taxonomy_slug );
										$add_post_type         = wp_json_encode(
											array(
												array(
													'url_type_slug' => 'taxonomies',
													'url_type_name' => 'Taxonomies',
													'post_type_slug' => $current_taxonomy_slug,
													'post_type_name' => $current_taxonomy_name,
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
					</div>

					<?php

					if ( ! get_option( WCD_WP_OPTION_KEY_API_TOKEN ) ) {
						echo '<div class="error notice">
                        <p>Please enter a valid API Token.</p>
                    </div>';
					} elseif ( ! $wcd->website_details['enable_limits'] && $wcd->is_allowed('upgrade_account')) {
						?>
						<div class="box-plain no-border">
							<h2>Need more checks?</h2>
							<p>If you need more checks, please upgrade your account with the button below.</p>
							<a class="button" href="<?php echo esc_url( $wcd->get_upgrade_url() ); ?>">Upgrade</a>
						</div>
						<?php
					}
					$wcd->get_api_token_form( get_option( WCD_WP_OPTION_KEY_API_TOKEN ) );
					$wizard_text = '<h2>Your account details</h2><p>You can see your WebChange Detector accout here.
                                                Please don\'t share your API token with anyone. </p><p>
                                                Resetting your API Token will allow you to switch accounts. Keep in mind to
                                                save your API Token before the reset! </p><p>
                                                When you login with your API token after the reset, all your settings will be still there.</p>';
					$wcd->print_wizard(
						$wizard_text,
						'wizard_settings_account_details',
						'wizard_settings_upgrade',
						false,
						false,
						'left top-minus-400 left-plus-400'
					);
				?>

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
