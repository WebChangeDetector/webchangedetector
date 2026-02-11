<?php
/**
 * Auto Checks - Refactored with Components
 *
 * @package    webchangedetector
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Are we allowed to see the settings?
if ( ! empty( $this->admin->website_details['allowances']['monitoring_checks_settings'] ) && $this->admin->website_details['allowances']['monitoring_checks_settings'] ) {

	$enabled = $group_and_urls['enabled'] ?? false;

	?>
	<div class="wcd-settings-card">
		<h2><?php esc_html_e( 'Monitoring Settings', 'webchangedetector' ); ?></h2>
		<p><?php esc_html_e( 'Configure automatic monitoring settings for your selected URLs and get notified about changes.', 'webchangedetector' ); ?></p>

		<form action="admin.php?page=webchangedetector-auto-settings" method="post">
			<input type="hidden" name="wcd_action" value="save_group_settings">
			<input type="hidden" name="group_id" value="<?php echo esc_html( $group_id ); ?>">
			<?php wp_nonce_field( 'save_group_settings' ); ?>
			<input type="hidden" name="monitoring" value="1">
			<input type="hidden" name="group_name" value="<?php echo esc_html( $group_and_urls['name'] ?? '' ); ?>">

			<div class="wcd-form-row wcd-monitoring-enabled">
				<div class="wcd-form-label-wrapper">
					<label class="wcd-form-label"><?php esc_html_e( 'Enable Monitoring', 'webchangedetector' ); ?></label>
					<div class="wcd-description"><?php esc_html_e( 'Enable or disable the monitoring for your selected URLs.', 'webchangedetector' ); ?></div>
				</div>
				<div class="wcd-form-control">
					<?php
					// Enable Monitoring Toggle (without content).
					$toggle_name        = 'enabled';
					$is_enabled         = $enabled;
					$toggle_label       = '';
					$toggle_description = '';
					$section_id         = 'monitoring-settings-content';
					$content            = ''; // Empty content for the toggle.

					// Include Toggle Section Component.
					include WCD_PLUGIN_DIR . 'admin/partials/components/ui-elements/toggle-section.php';
					?>
				</div>
			</div>

			<div class="wcd-form-row monitoring-setting wcd-monitoring-interval" style="<?php echo $enabled ? '' : 'display: none;'; ?>">
				<div class="wcd-form-label-wrapper">
					<label class="wcd-form-label"><?php esc_html_e( 'Interval in Hours', 'webchangedetector' ); ?></label>
					<div class="wcd-description"><?php esc_html_e( 'This is the interval in which the checks are done.', 'webchangedetector' ); ?></div>
				</div>
				<div class="wcd-form-control">
					<?php
					// Interval Selector Component.
					$current_interval      = $group_and_urls['interval_in_h'] ?? 24;
					$account_details       = $this->account_handler->get_account();
					$show_minute_intervals = false;
					if ( ! $account_details['is_subaccount'] && ! in_array( $account_details['plan'], array( 'trial', 'free', 'personal', 'personal_pro' ), true ) ) {
						$show_minute_intervals = true;
					}
					$field_name  = 'interval_in_h';
					$label       = ''; // Empty label since it's already in the form structure.
					$description = ''; // Empty description since it's already in the form structure.
					include WCD_PLUGIN_DIR . 'admin/partials/components/monitoring/interval-selector.php';
					?>
				</div>
			</div>

			<div class="wcd-form-row monitoring-setting wcd-monitoring-hour-of-day" style="<?php echo $enabled ? '' : 'display: none;'; ?>">
				<div class="wcd-form-label-wrapper">
					<label class="wcd-form-label"><?php esc_html_e( 'Hour of the Day', 'webchangedetector' ); ?></label>
					<div class="wcd-description"><?php esc_html_e( 'Set the hour on which the monitoring checks should be done.', 'webchangedetector' ); ?></div>
				</div>
				<div class="wcd-form-control">
					<?php
					// Hour Selector Component.
					$current_hour = $group_and_urls['hour_of_day'] ?? 0;
					$field_name   = 'hour_of_day';
					$label        = ''; // Empty label since it's already in the form structure.
					$description  = ''; // Empty description since it's already in the form structure.
					include WCD_PLUGIN_DIR . 'admin/partials/components/monitoring/hour-selector.php';
					?>
					<div class="local-timezone"></div>
				</div>
			</div>

			<?php
			// Prepare schedule data.
			$current_schedule_type = $group_and_urls['schedule_type'] ?? 'interval';
			$current_schedule_days = $group_and_urls['schedule_days'] ?? array();
			if ( is_string( $current_schedule_days ) ) {
				$current_schedule_days = json_decode( $current_schedule_days, true ) ?? array();
			}
			if ( ! is_array( $current_schedule_days ) ) {
				$current_schedule_days = array();
			}
			?>

			<div class="wcd-form-row monitoring-setting wcd-monitoring-schedule-type" style="<?php echo $enabled ? '' : 'display: none;'; ?>">
				<div class="wcd-form-label-wrapper">
					<label class="wcd-form-label"><?php esc_html_e( 'Run on', 'webchangedetector' ); ?></label>
					<div class="wcd-description"><?php esc_html_e( 'Choose when monitoring checks should run.', 'webchangedetector' ); ?></div>
				</div>
				<div class="wcd-form-control">
					<div class="wcd-schedule-type-radios">
						<label class="wcd-schedule-type-option">
							<input type="radio" name="schedule_type" value="interval" class="wcd-schedule-type" <?php checked( $current_schedule_type, 'interval' ); ?>>
							<?php esc_html_e( 'Every day', 'webchangedetector' ); ?>
						</label>
						<label class="wcd-schedule-type-option">
							<input type="radio" name="schedule_type" value="weekly" class="wcd-schedule-type" <?php checked( $current_schedule_type, 'weekly' ); ?>>
							<?php esc_html_e( 'Specific weekdays', 'webchangedetector' ); ?>
						</label>
						<label class="wcd-schedule-type-option">
							<input type="radio" name="schedule_type" value="monthly" class="wcd-schedule-type" <?php checked( $current_schedule_type, 'monthly' ); ?>>
							<?php esc_html_e( 'Specific days in month', 'webchangedetector' ); ?>
						</label>
					</div>
				</div>
			</div>

			<div class="wcd-form-row monitoring-setting wcd-schedule-weekly-fields" style="<?php echo ( $enabled && 'weekly' === $current_schedule_type ) ? '' : 'display: none;'; ?>">
				<div class="wcd-form-label-wrapper">
					<label class="wcd-form-label"><?php esc_html_e( 'Select Days', 'webchangedetector' ); ?></label>
				</div>
				<div class="wcd-form-control">
					<div class="wcd-day-checkboxes">
						<?php
						$weekdays = array(
							1 => __( 'Mon', 'webchangedetector' ),
							2 => __( 'Tue', 'webchangedetector' ),
							3 => __( 'Wed', 'webchangedetector' ),
							4 => __( 'Thu', 'webchangedetector' ),
							5 => __( 'Fri', 'webchangedetector' ),
							6 => __( 'Sat', 'webchangedetector' ),
							7 => __( 'Sun', 'webchangedetector' ),
						);
						foreach ( $weekdays as $day_num => $day_name ) {
							$checked = ( 'weekly' === $current_schedule_type && in_array( $day_num, $current_schedule_days, true ) ) ? 'checked' : '';
							echo '<label class="wcd-day-checkbox">';
							echo '<input type="checkbox" name="schedule_days[]" value="' . esc_attr( $day_num ) . '" ' . esc_attr( $checked ) . '>';
							echo esc_html( $day_name );
							echo '</label>';
						}
						?>
					</div>
				</div>
			</div>

			<div class="wcd-form-row monitoring-setting wcd-schedule-monthly-fields" style="<?php echo ( $enabled && 'monthly' === $current_schedule_type ) ? '' : 'display: none;'; ?>">
				<div class="wcd-form-label-wrapper">
					<label class="wcd-form-label"><?php esc_html_e( 'Select Days of Month', 'webchangedetector' ); ?></label>
				</div>
				<div class="wcd-form-control">
					<div class="wcd-day-checkboxes wcd-monthly-days">
						<?php
						for ( $d = 1; $d <= 30; $d++ ) {
							$checked = ( 'monthly' === $current_schedule_type && in_array( $d, $current_schedule_days, true ) ) ? 'checked' : '';
							echo '<label class="wcd-day-checkbox">';
							echo '<input type="checkbox" name="schedule_days[]" value="' . esc_attr( $d ) . '" ' . esc_attr( $checked ) . '>';
							echo esc_html( $d );
							echo '</label>';
						}
						$checked_last = ( 'monthly' === $current_schedule_type && in_array( 'last', $current_schedule_days, true ) ) ? 'checked' : '';
						echo '<label class="wcd-day-checkbox wcd-day-last">';
						echo '<input type="checkbox" name="schedule_days[]" value="last" ' . esc_attr( $checked_last ) . '>';
						esc_html_e( 'Last day', 'webchangedetector' );
						echo '</label>';
						?>
					</div>
				</div>
			</div>

			<div class="wcd-form-row monitoring-setting wcd-monitoring-quiet-hours" style="<?php echo $enabled ? '' : 'display: none;'; ?>">
				<div class="wcd-form-label-wrapper">
					<label class="wcd-form-label"><?php esc_html_e( 'Quiet Hours', 'webchangedetector' ); ?></label>
					<div class="wcd-description"><?php esc_html_e( 'No checks will be performed during this time.', 'webchangedetector' ); ?></div>
				</div>
				<div class="wcd-form-control">
					<div class="wcd-quiet-hours">
						<select name="quiet_hours_start">
							<option value=""><?php esc_html_e( 'None', 'webchangedetector' ); ?></option>
							<?php
							for ( $i = 0; $i < 24; $i++ ) {
								$selected = isset( $group_and_urls['quiet_hours_start'] ) && '' !== $group_and_urls['quiet_hours_start'] && (int) $group_and_urls['quiet_hours_start'] === $i ? 'selected' : '';
								echo '<option class="select-time" value="' . esc_attr( $i ) . '" ' . esc_attr( $selected ) . '></option>';
							}
							?>
						</select>
						<?php esc_html_e( 'to', 'webchangedetector' ); ?>
						<select name="quiet_hours_end">
							<option value=""><?php esc_html_e( 'None', 'webchangedetector' ); ?></option>
							<?php
							for ( $i = 0; $i < 24; $i++ ) {
								$selected = isset( $group_and_urls['quiet_hours_end'] ) && '' !== $group_and_urls['quiet_hours_end'] && (int) $group_and_urls['quiet_hours_end'] === $i ? 'selected' : '';
								echo '<option class="select-time" value="' . esc_attr( $i ) . '" ' . esc_attr( $selected ) . '></option>';
							}
							?>
						</select>
					</div>
					<div class="local-timezone"></div>
				</div>
			</div>

			<div class="wcd-form-row monitoring-setting wcd-monitoring-threshold" style="<?php echo $enabled ? '' : 'display: none;'; ?>">
				<div class="wcd-form-label-wrapper">
					<label class="wcd-form-label"><?php esc_html_e( 'Change Detection Threshold', 'webchangedetector' ); ?></label>
					<div class="wcd-description"><?php esc_html_e( 'Ignore changes in Change Detections below the threshold. Use this carefully. If you set it too low, you might miss changes that are important.', 'webchangedetector' ); ?></div>
				</div>
				<div class="wcd-form-control wcd-inline">
					<?php
					// Threshold Setting Component.
					$threshold   = $group_and_urls['threshold'] ?? 0.0;
					$label       = ''; // Empty label since it's already in the form structure.
					$description = ''; // Empty description since it's already in the form structure.
					include WCD_PLUGIN_DIR . 'admin/partials/components/forms/threshold-setting.php';
					?>
				</div>
			</div>

			<div class="wcd-form-row monitoring-setting wcd-monitoring-alert-emails" style="<?php echo $enabled ? '' : 'display: none;'; ?>">
				<div class="wcd-form-label-wrapper">
					<label class="wcd-form-label"><?php esc_html_e( 'Alerts', 'webchangedetector' ); ?></label>
					<div class="wcd-description">
						<?php esc_html_e( 'Enter the email address(es) which should get notified about monitoring alerts.', 'webchangedetector' ); ?><br>
						<?php
						echo wp_kses(
							__( 'You can also connect <a href="https://zapier.com/apps/webchange-detector/integrations" target="_blank">Zapier</a> to get alerts directly in 6000+ apps.', 'webchangedetector' ),
							array(
								'a' => array(
									'href'   => array(),
									'target' => array(),
								),
							)
						);
						?>
					</div>
				</div>
				<div class="wcd-form-control">
					<?php
					// Email Input Component.
					$email_value     = $group_and_urls['alert_emails'] ?? '';
					$field_name      = 'alert_emails';
					$label           = ''; // Empty label since it's already in the form structure.
					$description     = ''; // Empty description since it's already in the form structure.
					$multiple        = true;
					$show_validation = true;
					include WCD_PLUGIN_DIR . 'admin/partials/components/forms/email-input.php';
					?>
				</div>
			</div>

			<?php
			$advanced_settings_class = 'monitoring-setting';
			$advanced_settings_style = $enabled ? '' : 'display: none;';
			include WCD_PLUGIN_DIR . 'admin/partials/components/settings/advanced-screenshot-settings.php';
			$advanced_settings_class = '';
			$advanced_settings_style = '';
			?>

			<div class="wcd-form-row monitoring-setting wcd-monitoring-css" style="<?php echo $enabled ? '' : 'display: none;'; ?>">
				<div class="wcd-form-label-wrapper">
					<label class="wcd-form-label"><?php esc_html_e( 'CSS Settings', 'webchangedetector' ); ?></label>
					<div class="wcd-description"><?php esc_html_e( 'Hide or modify elements via CSS before taking screenshots (e.g. dynamic content).', 'webchangedetector' ); ?></div>
				</div>
				<div class="wcd-form-control">
					<div style="margin-top: 10px; width: 100%;">
						<div class="code-tags default-bg">&lt;style&gt;</div>
						<textarea name="css" class="codearea wcd-css-textarea" rows="15" cols="80"><?php echo esc_textarea( $group_and_urls['css'] ?? '' ); ?></textarea>
						<div class="code-tags default-bg">&lt;/style&gt;</div>
					</div>
				</div>
			</div>

			<?php submit_button( __( 'Save Settings', 'webchangedetector' ), 'primary wizard-save-auto-settings', 'submit', true, array( 'onclick' => 'return wcdValidateFormAutoSettings()' ) ); ?>
		</form>
	</div>

	<script type="text/javascript">
		// Toggle monitoring settings visibility with slide animation.
		jQuery(document).ready(function($) {
			// Listen for changes on the toggle switch.
			$(document).on('change', 'input[name="enabled"]', function() {
				if ($(this).is(':checked')) {
					$('.monitoring-setting').slideDown(400, function() {
						// Initialize CodeMirror for CSS textarea when monitoring is enabled.
						var cssTextarea = $('.wcd-monitoring-css .wcd-css-textarea')[0];
						if (cssTextarea && window.wp && window.wp.codeEditor) {
							// Check if CodeMirror is already initialized.
							var existingEditor = null;
							if (cssTextarea.nextElementSibling && cssTextarea.nextElementSibling.classList.contains('CodeMirror')) {
								// CodeMirror already exists, just refresh it.
								var cmInstance = cssTextarea.nextElementSibling.CodeMirror;
								if (cmInstance) {
									// Refresh after a small delay to ensure the element is fully visible.
									setTimeout(function() {
										cmInstance.refresh();
									}, 100);
								}
							} else {
								// Initialize new CodeMirror instance.
								var editorSettings = {};
								if (typeof cm_settings !== 'undefined' && cm_settings.codeEditor) {
									editorSettings = cm_settings.codeEditor;
								}
								var editor = wp.codeEditor.initialize(cssTextarea, editorSettings);

								// Refresh the editor after initialization to fix line numbers.
								if (editor && editor.codemirror) {
									setTimeout(function() {
										editor.codemirror.refresh();
									}, 100);
								}
							}
						}
					});

					// Respect schedule type visibility when enabling monitoring.
					var checkedType = $('input[name="schedule_type"]:checked').val() || 'interval';
					$('.wcd-schedule-weekly-fields').toggle(checkedType === 'weekly');
					$('.wcd-schedule-monthly-fields').toggle(checkedType === 'monthly');
					// Disable hidden checkboxes so they don't submit.
					$('.wcd-schedule-weekly-fields input[name="schedule_days[]"]').prop('disabled', checkedType !== 'weekly');
					$('.wcd-schedule-monthly-fields input[name="schedule_days[]"]').prop('disabled', checkedType !== 'monthly');
				} else {
					$('.monitoring-setting').slideUp();
				}
			});

			// Schedule type toggle.
			function toggleScheduleFields(radioEl) {
				var type = $(radioEl).val();
				$('.wcd-schedule-weekly-fields').toggle(type === 'weekly');
				$('.wcd-schedule-monthly-fields').toggle(type === 'monthly');
				// Disable hidden checkboxes so they don't submit duplicate schedule_days[].
				$('.wcd-schedule-weekly-fields input[name="schedule_days[]"]').prop('disabled', type !== 'weekly');
				$('.wcd-schedule-monthly-fields input[name="schedule_days[]"]').prop('disabled', type !== 'monthly');
			}

			$(document).on('change', '.wcd-schedule-type', function() {
				toggleScheduleFields(this);
			});

			// On page load, apply to checked radio button.
			$('.wcd-schedule-type:checked').each(function() {
				toggleScheduleFields(this);
			});
		});

		function wcdValidateFormAutoSettings() {
			// Only validate if monitoring is enabled.
			var monitoringEnabled = document.querySelector('input[name="enabled"]');
			if (monitoringEnabled && monitoringEnabled.checked) {
				// Validate email if present.
				if (typeof window['validate_alert_emails'] === 'function') {
					if (!window['validate_alert_emails']()) {
						return false;
					}
				}
			}

			return true;
		}
	</script>
	<?php
}
?>
