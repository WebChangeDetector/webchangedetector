<?php
/**
 * Auto Checks
 *
 * @package    webchangedetector
 */

?>
<div style="width: 50%; float: left;">
	<div style=" padding: 10px;">

		<p class="auto-setting toggle">
			<input type="hidden" name="monitoring" value="1">
			<input type="hidden" name="group_name" value="<?php echo esc_html( $group_and_urls['name'] ); ?>">

			<label for="enabled">Monitoring </label>
			<input type="checkbox" name="enabled" id="auto-enabled" <?php echo isset( $group_and_urls['enabled'] ) && $group_and_urls['enabled'] ? 'checked' : ''; ?>>

		</p>
		<div id="auto_settings">
			<p class="toggle" style="display:none"></p>
			<p class="auto-setting toggle">
				<label for="hour_of_day" class="auto-setting">Hour of the day</label>
				<select name="hour_of_day" class="auto-setting">
					<?php
					for ( $i = 0; $i < WCD_HOURS_IN_DAY; $i++ ) {
						if ( isset( $group_and_urls['hour_of_day'] ) && $group_and_urls['hour_of_day'] === $i ) {
							$selected = 'selected';
						} else {
							$selected = '';
						}
						echo '<option class="select-time" value="' . esc_html( $i ) . '" ' . esc_html( $selected ) . '>' . esc_html( $i ) . ':00</option>';
					}
					?>
				</select>
			</p>
			<p class="auto-setting toggle">
				<?php
				$account_details       = $this->account_details();
				$show_minute_intervals = false;
				if ( 2 < $account_details['plan']['id'] && 8 !== $account_details['plan_id'] ) {
					$show_minute_intervals = true;
				}
				?>
				<label for="interval_in_h" class="auto-setting">Interval in hours</label>
				<select name="interval_in_h" class="auto-setting">
					<option value="0.25"
						<?php echo ! $show_minute_intervals ? 'disabled ' : ''; ?>
						<?php echo isset( $group_and_urls['interval_in_h'] ) && '0.25' === $group_and_urls['interval_in_h'] ? 'selected' : ''; ?>
						<?php echo ! isset( $group_and_urls['interval_in_h'] ) ? 'selected' : ''; ?>>
						Every 15 minutes <?php echo ! $show_minute_intervals ? '("Freelancer" plan or higher)' : ''; ?>
					</option>
					<option value="0.5"
						<?php echo ! $show_minute_intervals ? 'disabled ' : ''; ?>
						<?php echo isset( $group_and_urls['interval_in_h'] ) && '0.5' === $group_and_urls['interval_in_h'] ? 'selected' : ''; ?>
						<?php echo ! isset( $group_and_urls['interval_in_h'] ) ? 'selected' : ''; ?>>
						Every 30 minutes <?php echo ! $show_minute_intervals ? '("Freelancer" plan or higher)' : ''; ?>
					</option>
					<option value="1" <?php echo isset( $group_and_urls['interval_in_h'] ) && 1 === $group_and_urls['interval_in_h'] ? 'selected' : ''; ?>>
						Every 1 hour
					</option>
					<option value="3" <?php echo isset( $group_and_urls['interval_in_h'] ) && 3 === $group_and_urls['interval_in_h'] ? 'selected' : ''; ?>>
						Every 3 hours
					</option>
					<option value="6" <?php echo isset( $group_and_urls['interval_in_h'] ) && 6 === $group_and_urls['interval_in_h'] ? 'selected' : ''; ?>>
						Every 6 hours
					</option>
					<option value="12" <?php echo isset( $group_and_urls['interval_in_h'] ) && 12 === $group_and_urls['interval_in_h'] ? 'selected' : ''; ?>>
						Every 12 hours
					</option>
					<option value="24" <?php echo isset( $group_and_urls['interval_in_h'] ) && 24 === $group_and_urls['interval_in_h'] ? 'selected' : ''; ?>>
						Every 24 hours
					</option>
				</select>
			</p>
			<p class="auto-setting toggle">
				<label for="threshold" class="auto-setting">Threshold</label>
				<input name="threshold" class="threshold" type="number" step="0.1" min="0" max="100" value="<?php echo esc_html( $group_and_urls['threshold'] ); ?>"> %
			</p>
			<p class="auto-setting toggle">
				<label for="alert_emails" class="auto-setting">
					Alert email addresses (comma separated)
				</label>
				<input type="text" name="alert_emails" id="alert_emails" style="width: 100%;" class="auto-setting"
				value="<?php echo isset( $group_and_urls['alert_emails'] ) ? esc_attr( $group_and_urls['alert_emails'] ) : ''; ?>">
			</p>
			<span class="notice notice-error" id="error-email-validation" style="display: none;">
				<span style="padding: 10px; display: block;" class="default-bg">Please check your email address(es).</span>
			</span>
		</div>

		<script>
			function show_monitoring_settings() {
				if(jQuery("#auto-enabled:checked").length) {
					jQuery("#auto_settings").slideDown();
				} else {
					jQuery("#auto_settings").slideUp();
				}
				return true;
			}
			jQuery("#auto-enabled").on( "click", show_monitoring_settings);
			show_monitoring_settings();
		</script>
	</div>
</div>
<div style="width: 50% ; float: left; ">
	<div style="border-left: 1px solid #aaa; padding: 10px;">
		<?php require 'css-settings.php'; ?>
	</div>
</div>
<div class="clear"></div>
