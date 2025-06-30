<?php
// Check if we have wp websites to show the filter or not
$cms_filter = false;
$cms        = 'all';
//dd($groups_and_urls);
if ( ! empty( $_POST['cms'] ) ) {
	$cms_filter = true;
} else {
	foreach ( $groups_and_urls as $group ) {
		if ( $group['cms'] ) {
			$cms_filter = true;
		}
	}
}

if ( ! empty( $_POST['cms'] ) && $_POST['cms'] !== 'all' ) {
	$cms             = $_POST['cms'] === 'general' ? null : $_POST['cms'];
	$groups_and_urls = array_filter(
		$groups_and_urls,
		function ( $group ) use ( $cms ) {
			return $group['cms'] === $cms;
		}
	);
}

update_user_meta( get_current_user_id(), WCD_OPTION_UPDATE_CMS_FILTER, $cms ?? 'all' );

$account = $wp_comp->get_account_details_v2();

if ( $update_view ) {
	include 'update-detection/update-step-tiles.php';
	?>

	<!--<div class="required-credits" style="margin-top: 30px; width: 100%; ">
		<div class="box full">
			Current settings require
			<div id="sc_until_renew" class="big ">
				<span id="ajax_amount_total_sc"></span> <span style="font-weight: 500">screenshots</span>
			</div>
		</div>

	</div>-->
	<div class="clear"></div>
	<form id="form-take-pre-sc" class="ajax" action="" method="post" style="width: 100%; text-align: center; margin: 50px 0;" onsubmit="return false">
		<input type="hidden" name="action" value="update_detection_step">
		<input type="hidden" name="sc_type" value="pre">
		<input type="hidden" name="step" value="<?php echo WCD_OPTION_UPDATE_STEP_PRE; ?>">
		<input type="hidden" name="cms" value="<?php echo $filters['cms']; ?>">
		<input id="pre-sc-group-ids" type="hidden" name="group_ids"> <!-- value filled by ajax -->
		<input id="btn-start-update-detection" type="submit" value="Start Manual Checks >" class="et_pb_button primary" style="font-size: 18px; padding: 5px 20px !important;">
		<div style="height: 30px;">Selected <span id="ajax_amount_total_sc"  style="font-weight: 900"></span> <span> webpage checks</span></div>
	</form>

	<?php
}

// Auto detection credits
if ( ! empty( $groups_and_urls ) && ! $update_view ) {
	?>
<div class="required-credits" id="auto-detection-status-container" style="display:none">
	<div class="box third">
		<div id="">Monitoring is</div>
		<div id="auto-detection-status" class="big"></div>
	</div>
	<div class="box third">
		<div id="txt_next_sc_in">Next run in</div>
		<div id="next_sc_in" class="big"></div>
		<div id="next_sc_date" class="local-time" data-date=""></div>
	</div>
	<div class="box third">
		Current settings require
		<div id="sc_until_renew" class="big ">
			<span id="ajax_amount_total_sc"></span> <span style="font-weight: 500">checks</span>
		</div>
		<div id="sc_available_until_renew"> until renewal</div>
	</div>

</div>
<div class="clear"></div>
<?php } ?>

    <div class="add-group et_pb_button" onclick="showGroupSettingsPopup(0)">
		<?php echo get_device_icon( 'general', 'row-icon' ); ?>Add Webpage Group
    </div>
    <div class="add-group et_pb_button" onclick="showWpGroupSettingsPopup(0)">
		<?php echo get_device_icon( 'wordpress', 'row-icon' ); ?>Add WP website<br>
    </div>

<div class="setting-row" style="">

	</div>
<?php
if ( is_iterable( $groups_and_urls ) ) {

	$total_sc_current_period = 0;

	foreach ( $groups_and_urls as $group ) {

		// Calculation for auto detections
		$date_next_sc = false;
		$next_sc_in   = false;

		$amount_group_sc_per_day = 0;
		if ( $group['monitoring'] ) {
			$amount_sc_per_day = 0;

			// Check for intervals >= 1h
			if ( $group['interval_in_h'] >= 1 ) {
				$next_possible_sc  = gmmktime( gmdate( 'H' ) + 1, 0, 0, gmdate( 'm' ), gmdate( 'd' ), gmdate( 'Y' ) );
				$amount_sc_per_day = ( 24 / $group['interval_in_h'] );

				// Get possible tracking hours
				$possible_hours = array();
				for ( $i = 0; $i <= $amount_sc_per_day * 2; $i++ ) {
					// $possible_time = gmmktime($possible_hours[$i], 0, 0, gmdate("m"), gmdate("d") + $ii, gmdate("Y"));
					$possible_hour    = $group['hour_of_day'] + $i * $group['interval_in_h'];
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
			if ( $group['interval_in_h'] === 0.5 ) {
				$amount_sc_per_day = 48;
				if ( gmdate( 'i' ) < 30 ) {
					$date_next_sc = gmmktime( gmdate( 'H' ), 30, 0, gmdate( 'm' ), gmdate( 'd' ), gmdate( 'Y' ) );
				} else {
					$date_next_sc = gmmktime( gmdate( 'H' ) + 1, 0, 0, gmdate( 'm' ), gmdate( 'd' ), gmdate( 'Y' ) );
				}
			}
			// Check for 15 min intervals
			if ( $group['interval_in_h'] === 0.25 ) {
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
			$next_sc_in = false;
			if ( $date_next_sc ) {
				$next_sc_in = ( $date_next_sc - gmdate( 'U' ) );
			}

			// Calculate screenshots until renewal
			$days_until_renewal      = date( 'd', date( 'U', strtotime( $account['renewal_at'] ) ) - date( 'U' ) );
			$amount_group_sc_per_day = $amount_sc_per_day * $days_until_renewal;

			// Get first detection hour
			$first_hour_of_interval = $group['hour_of_day'];
			while ( $first_hour_of_interval - $group['interval_in_h'] >= 0 ) {
				$first_hour_of_interval = $first_hour_of_interval - $group['interval_in_h'];
			}

			// Count up in interval_in_h to current hour
			$skip_sc_count_today = 0;
			while ( $first_hour_of_interval + $group['interval_in_h'] <= date( 'H' ) ) {
				$first_hour_of_interval = $first_hour_of_interval + $group['interval_in_h'];
				++$skip_sc_count_today;
			}

			// Subtract screenshots already taken today
			$amount_group_sc_per_day = $amount_group_sc_per_day - $skip_sc_count_today * $group['selected_urls_count'];
		}
		
        $group_id = $group['id'];
		?>
		<div class="clear"></div>

		<?php // include wcd_get_plugin_dir() . '/public/partials/popup-group-css.php'; ?>

		<!-- Start accordion -->
		<div class="accordion-container"
			id="accordion_group_<?php echo $group_id; ?>"
			data-group_id="<?php echo $group_id; ?>"
			data-auto_enabled="<?php echo $group['enabled']; ?>"
            data-manual_enabled="<?php echo $group['enabled']; ?>"
			data-auto_sc_per_url_until_renewal="<?php echo $amount_group_sc_per_day; ?>"
			data-auto_sc_interval="<?php echo $group['interval_in_h']; ?>"
			data-auto_hour_of_day="<?php echo $group['hour_of_day']; ?>"
			data-auto_next_sc_date="<?php echo $date_next_sc; ?>"
			data-auto_next_sc_in="<?php echo $next_sc_in; ?>"
			data-amount_sc="<?php echo $group['selected_urls_count']; ?>"
			data-auto_group="<?php echo $group['monitoring']; ?>"
		>

			<?php include wcd_get_plugin_dir() . 'public/partials/popup-group-settings.php'; ?>
			<?php include wcd_get_plugin_dir() . 'public/partials/popup-group-sync-urls.php'; ?>

			<!-- Accordion title -->
			<div class="accordion url-list" id="group_<?php echo $group_id; ?>">
				<div class="mm_accordion_title">
				<?php if ( ! $group['monitoring'] ) { ?>
					<div  class="enabled_switch" style="position: absolute; margin-left: 50px; top: 20px;">
						<label class="switch">
							<input id="enabled<?= $group_id ?>" type="checkbox" class="ajax_enable_group"
									data-amount_sc="<?php echo $group['selected_urls_count']; ?>"
									data-group_id="<?php echo $group_id; ?>"
								<?php echo $group['enabled'] ? 'checked' : ''; ?> >
							<span class="slider round"></span>

						</label>
					</div>
				<?php } else { ?>

					<!-- Enabled / disabled switch - Currently disabled -->
					<div class="enabled-switch auto-enabled ajax_enable_monitoring"
						data-group_id="<?php echo $group_id; ?>"
						data-enabled="<?php echo $group['enabled']; ?>"
						style="position: absolute; display: inline-block; left: 50px;">

						<?php
						if ( $group['enabled'] ) {
							echo get_device_icon( 'play', 'active-auto ' );
							echo "<div class='enabled-description enabled'>Monitoring<br><strong>Active</strong></div>";
							echo "<div id='hover-enable' class='stop-tracking'><strong>Stop</strong><br>Monitoring</div>";
						} else {
							echo get_device_icon( 'pause', 'paused-auto' );
							echo "<div class='enabled-description disabled'>Monitoring<br><strong>Off</strong></div>";
							echo "<div id='hover-enable' class='start-tracking'><strong>Start</strong><br>Monitoring</div>";
						}
						
						?>
					</div>
				<?php } ?>

					<h3 style="position: relative; text-align: left" class="accordion-ajax-group-urls" data-search="" data-group_id="<?php echo $group_id; ?>" data-cms=" <?php echo $group['cms']; ?>">
						<div class="accordion-state-icon-position">
							<span class="accordion-state-icon dashicons dashicons-arrow-right-alt2" ></span>
						</div>

						<?php if ( $group['monitoring'] ) { ?>
							<div class="box third accordion-group-name" >
								<small>Webpage Group</small>
								<?php echo $group['cms'] ? get_device_icon( $group['cms'], 'no-space' ) : get_device_icon( 'general', 'no-space' ); ?>
								<strong><span id="group_name-<?php echo $group_id; ?>"><?php echo $group['name']; ?></strong></span>
							</div>

							<div class="box fifth">
								<!--<div style="float: left;" id="status-animation-group-<?php echo $group_id; ?>"></div>-->

								<small>Monitoring</small>
								<div>
									
									<strong><span id="status-amount-webpages-group-<?php echo $group_id; ?>"><?= $group['enabled'] ? $group['selected_urls_count'] : 0 ?></span> webpages</strong>
								</div>
							</div>

							<div class="box fifth">
								<small>Next check in:</small>
								<div>
									<span id="status-next-check-group-<?php echo $group_id; ?>"><img src="<?php echo $wp_comp->get_loading_icon_url_path(); ?>"></span>
								</div>
							</div>

							<div class="box fifth" >
								<small>Interval: </small>
								<strong>every
									<span id="group-interval-<?php echo $group_id; ?>">
									<?php
									if ( $group['interval_in_h'] == 1 ) {
										$time_label = 'hour';
									} elseif ( $group['interval_in_h'] > 1 ) {
										$time_label = 'hours';
									} else {
										$time_label = 'minutes';
									}
									echo $group['interval_in_h'] < 1 ? $group['interval_in_h'] * 60 : $group['interval_in_h']
									?>
									<?php echo $time_label; ?>
									</span>
								</strong>
								<br>

								<!--Alerts sent to: <strong><?php echo $group['alert_emails'][0]; ?>
								<?php
								$alert_emails = explode(',', $group['alert_emails']);
								if ( count( $alert_emails ) > 1 ) {
									echo ' and ' . count( $alert_emails ) - 1 . ' more';
								}
								?>
								</strong>-->

							</div>

							<!-- required for calculation -->
							<span id="group-selected-<?php echo $group_id; ?>" style="display: none;">
										<?php echo $group['selected_urls_count']; ?>
									</span>
						<?php } else { ?>

						<div class="box third accordion-group-name">

							<div style="">
								<small>Webpage Group</small>
								<?php echo $group['cms'] ? get_device_icon( $group['cms'], 'no-space') : get_device_icon( 'general', 'no-space' ); ?>
								<strong><span id="group_name-<?php echo $group_id; ?>"><?php echo $group['name']; ?></strong></span>
							</div>
						</div>

						<div class="box third" >
							<div>
								<small>Checking at next run</small>
								<strong>
									<span id="status-amount-webpages-group-<?php echo $group_id; ?>" >
										<?php echo $group['enabled'] ? $group['selected_urls_count'] : 0; ?>
									</span> Webpages
								</strong>
							</div>
						</div>
						<?php } ?>
						<div class="clear"></div>

					</h3>


					<!-- Accordion content-->
					<div class="mm_accordion_content">

						<!-- Button for add webpage or sync urls -->
						<?php if ( ! $group['cms'] ) { ?>
							<a class="et_pb_button" onclick="showAssignGroupUrlPopup('<?php echo $group_id; ?>');">
								<span class="dashicons dashicons-welcome-add-page"></span> Add Webpages
							</a>
						<?php } else { ?>
							<a class="et_pb_button"
								data-group_id="<?php echo $group_id; ?>"
								onclick="showSyncWpGroupUrlsPopup('<?php echo $group_id; ?>');">
								<div class="dashicons dashicons-update-alt"></div>
							</a>
						<?php } ?>

						<a class="et_pb_button group_settings_button" style="float: right;" onclick="showGroupSettingsPopup('<?php echo $group_id; ?>');">
							<span class="dashicons dashicons-admin-generic"></span>
							Group Settings
						</a>
                        <a class="et_pb_button change_detections_button" style="float: right" href="?tab=change-detections&group_id=<?= $group_id ?>">
                            <?php echo get_device_icon('change-detections', 'row-icon') ?>
                            Change Detections
                        </a>

						<div class="clear"></div>


						<input type="hidden" name="action" value="save_group_urls" >
						<input type="hidden" name="group_id" value="<?php echo $group_id; ?>">
						<input type="hidden" name="cms" value="<?php echo $group['cms']; ?>">
						<!-- get the urls -->
						<div class="ajax-group-urls" id="ajax-group-urls_<?php echo $group_id; ?>" data-group_id="<?php echo $group_id; ?>" data-cms="<?php echo $group['cms'] ?? null; ?>">
							<img src="<?php echo $wp_comp->get_loading_icon_url_path(); ?>">
                            <div style="text-align: center;">Loading</div>
						</div>

						<div class="clear"></div>
					</div>
				</div>
			</div>
		</div>

		<?php

		
	}
	// If we have meta, we show pagination. Change Detetion view at manual checks doesn't have pagination.
	if(!empty($groups['meta'])) {
		$pagination         = $groups['meta'];
		?>
		<!-- Pagination -->
		<div class="tablenav">
			<div class="tablenav-pages">
				<span class="pagination-links">
					<?php
					foreach ( $pagination['links'] as $link ) {
						$url_params = $wp_comp->get_params_of_url( $link['url'] );
						$class  = ! $link['url'] || $link['active'] ? 'disabled' : '';
						?>
						<a class="tablenav-pages-navspan et_pb_button <?php echo esc_html( $class ); ?>"
						   href="?tab=<?= $tab ?>&pagination=<?php
						   echo esc_html( $url_params['page'] ?? 1 );?>
						   ">
							<?php echo esc_html( $link['label'] ); ?>
						</a>
						<?php
					}
					?>
				</span>
				<span class="displaying-num"><?php echo esc_html( $pagination['total'] ); ?> items</span>
			</div>
		</div>
	<?php } else {
		// We only have one batch. So we open it. ?>
		<script>jQuery(document).ready(function() {jQuery(".mm_accordion_title h3").click()});</script>
	<?php }
	?>

	<?php
} else {
	?>
	<h2>No groups yet. Create one to get started.</h2>
	<?php
}


