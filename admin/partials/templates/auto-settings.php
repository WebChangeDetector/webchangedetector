<div style="width: 50%; float: left;">
	<div style=" padding: 10px;">
		<p class="auto-settings">Make the settings for automatic change detections here.</p>
		<p class="toggle">
			<input type="hidden" name="monitoring" value="1">
			<input type="hidden" name="group_name" value="<?php echo $groups_and_urls['name']; ?>">

			<label for="enabled">Monitoring </label>
			<select name="enabled" id="auto-enabled">
				<option value="1" <?php echo isset( $groups_and_urls['enabled'] ) && '1' === $groups_and_urls['enabled'] ? 'selected' : ''; ?>>
					Enabled
				</option>
				<option value="0" <?php echo isset( $groups_and_urls['enabled'] ) && '0' === $groups_and_urls['enabled'] ? 'selected' : ''; ?>>
					Disabled
				</option>
			</select>
		</p>
		<p class="auto-setting toggle">
			<label for="hour_of_day" class="auto-setting">Hour of the day</label>
			<select name="hour_of_day" class="auto-setting">
				<?php
				for ( $i = 0; $i < WCD_HOURS_IN_DAY; $i++ ) {
					if ( isset( $groups_and_urls['hour_of_day'] ) && $groups_and_urls['hour_of_day'] === $i ) {
						$selected = 'selected';
					} else {
						$selected = '';
					}
					echo '<option class="select-time" value="' . $i . '" ' . $selected . '>' . $i . ':00</option>';
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
					<?php echo isset( $groups_and_urls['interval_in_h'] ) && '0.25' === $groups_and_urls['interval_in_h'] ? 'selected' : ''; ?>
					<?php echo ! isset( $groups_and_urls['interval_in_h'] ) ? 'selected' : ''; ?>>
					Every 15 minutes <?php echo ! $show_minute_intervals ? '("Freelancer" plan or higher)' : ''; ?>
				</option>
				<option value="0.5"
					<?php echo ! $show_minute_intervals ? 'disabled ' : ''; ?>
					<?php echo isset( $groups_and_urls['interval_in_h'] ) && '0.5' === $groups_and_urls['interval_in_h'] ? 'selected' : ''; ?>
					<?php echo ! isset( $groups_and_urls['interval_in_h'] ) ? 'selected' : ''; ?>>
					Every 30 minutes <?php echo ! $show_minute_intervals ? '("Freelancer" plan or higher)' : ''; ?>
				</option>
				<option value="1" <?php echo isset( $groups_and_urls['interval_in_h'] ) && 1 === $groups_and_urls['interval_in_h'] ? 'selected' : ''; ?>>
					Every 1 hour
				</option>
				<option value="3" <?php echo isset( $groups_and_urls['interval_in_h'] ) && 3 === $groups_and_urls['interval_in_h'] ? 'selected' : ''; ?>>
					Every 3 hours
				</option>
				<option value="6" <?php echo isset( $groups_and_urls['interval_in_h'] ) && 6 === $groups_and_urls['interval_in_h'] ? 'selected' : ''; ?>>
					Every 6 hours
				</option>
				<option value="12" <?php echo isset( $groups_and_urls['interval_in_h'] ) && 12 === $groups_and_urls['interval_in_h'] ? 'selected' : ''; ?>>
					Every 12 hours
				</option>
				<option value="24" <?php echo isset( $groups_and_urls['interval_in_h'] ) && 24 === $groups_and_urls['interval_in_h'] ? 'selected' : ''; ?>>
					Every 24 hours
				</option>
			</select>
		</p>
		<p class="auto-setting toggle">
			<label for="threshold" class="auto-setting">Threshold</label>
			<input name="threshold" class="threshold" type="number" step="0.1" min="0" max="100" value="<?php echo $groups_and_urls['threshold']; ?>"> %
		</p>
		<div class="auto-setting " style="margin-top: 20px;">
			<label for="alert_emails" class="auto-setting">
				Alert email addresses (One per line)
			</label>
			<textarea name="alert_emails" id="alert_emails" style="width: 100%; height: 100px; " class="auto-setting"
			><?php echo isset( $groups_and_urls['alert_emails'] ) ? esc_attr( implode( "\n", $groups_and_urls['alert_emails'] ) ) : ''; ?></textarea>

		</div>
		<span class="notice notice-error" id="error-email-validation" style="display: none;">
			<span style="padding: 10px; display: block;" class="default-bg">Please check your email address(es).</span>
		</span>
	</div>
</div>
<div style="width: 50% ; float: left; ">
	<div style="border-left: 1px solid #aaa; padding: 10px;">
		<?php require 'css-settings.php'; ?>
	</div>
</div>
<div class="clear"></div>
