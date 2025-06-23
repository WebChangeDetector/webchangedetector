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
	<div class="box-plain no-border">
		<h2>Monitoring Settings</h2>
		<form action="admin.php?page=webchangedetector-auto-settings" method="post">
			<input type="hidden" name="wcd_action" value="save_group_settings">
			<input type="hidden" name="group_id" value="<?php echo esc_html( $group_id ); ?>">
			<?php wp_nonce_field( 'save_group_settings' ); ?>
			
			<table class="form-table">
				<tr valign="top">
					<th scope="row">
						<label>Enable Monitoring</label>
					</th>
					<td>
						<?php
						// Monitoring Enable/Disable Toggle with Content.
						$toggle_name = 'enabled';
						$is_enabled = isset( $group_and_urls['enabled'] ) && $group_and_urls['enabled'];
						$toggle_label = 'Monitoring';
						$toggle_description = 'Enable or disable the monitoring for your selected URLs.';
						$section_id = 'auto_settings';
						
						// Build the content for the monitoring settings.
						ob_start();
						?>
						<input type="hidden" name="monitoring" value="1">
						<input type="hidden" name="group_name" value="<?php echo esc_html( $group_and_urls['name'] ?? '' ); ?>">
						<?php
						$content = ob_get_clean();
						
						// Include Toggle Section Component.
						include WP_PLUGIN_DIR . '/webchangedetector/admin/partials/components/ui-elements/toggle-section.php';
						?>
					</td>
				</tr>
				<tr valign="top" class="monitoring-setting" style="<?php echo $enabled ? '' : 'display: none;'; ?>">
					<th scope="row">
						<label>Interval in Hours</label>
					</th>
					<td>
						<?php
						// Interval Selector Component.
						$current_interval = $group_and_urls['interval_in_h'] ?? 24;
						$account_details = $this->account_handler->get_account();
						$show_minute_intervals = false;
						if ( ! $account_details['is_subaccount'] && ! in_array( $account_details['plan'], array( 'trial', 'free', 'personal', 'personal_pro' ), true ) ) {
							$show_minute_intervals = true;
						}
						$field_name = 'interval_in_h';
						$label = 'Interval in hours';
						$description = 'This is the interval in which the checks are done.';
						include WP_PLUGIN_DIR . '/webchangedetector/admin/partials/components/monitoring/interval-selector.php';
						?>
					</td>
				</tr>
				<tr valign="top" class="monitoring-setting" style="<?php echo $enabled ? '' : 'display: none;'; ?>">
					<th scope="row">
						<label>Hour of the Day</label>
					</th>
					<td>
						<?php
						// Hour Selector Component.
						$current_hour = $group_and_urls['hour_of_day'] ?? 0;
						$field_name = 'hour_of_day';
						$label = 'Hour of the day';
						$description = 'Set the hour on which the monitoring checks should be done.';
						include WP_PLUGIN_DIR . '/webchangedetector/admin/partials/components/monitoring/hour-selector.php';
						?>
					</td>
				</tr>
				<tr valign="top" class="monitoring-setting" style="<?php echo $enabled ? '' : 'display: none;'; ?>">
					<th scope="row">
						<label>Change Detection Threshold</label>
					</th>
					<td>
						<?php
						// Threshold Setting Component.
						$threshold = $group_and_urls['threshold'] ?? 0.0;
						$label = 'Threshold';
						$description = 'Ignore changes in Change Detections below the threshold. Use this carefully. If you set it too low, you might miss changes that are important.';
						include WP_PLUGIN_DIR . '/webchangedetector/admin/partials/components/forms/threshold-setting.php';
						?>
					</td>
				</tr>
				<tr valign="top" class="monitoring-setting" style="<?php echo $enabled ? '' : 'display: none;'; ?>">
					<th scope="row">
						<label>Alert Email Addresses</label>
					</th>
					<td>
						<?php
						// Email Input Component.
						$email_value = $group_and_urls['alert_emails'] ?? '';
						$field_name = 'alert_emails';
						$label = 'Alert email addresses';
						$description = 'Enter the email address(es) which should get notified about monitoring alerts.';
						$multiple = true;
						$show_validation = true;
						include WP_PLUGIN_DIR . '/webchangedetector/admin/partials/components/forms/email-input.php';
						?>
					</td>
				</tr>
				<tr valign="top">
					<th scope="row">
						<label>CSS Settings</label>
					</th>
					<td>
						<?php
						// CSS Injection using Accordion Component.
						$header_text = 'CSS Injection';
						$accordion_id = 'css-injection-monitoring';
						$open = false;
						
						// Build content.
						ob_start();
						?>
						<p class="description" style="margin-bottom: 10px;">Hide or modify elements via CSS before taking screenshots (e.g. dynamic content).</p>
						<div class="code-tags default-bg">&lt;style&gt;</div>
						<textarea name="css" class="codearea" style="height:300px; width: 100%;"><?php echo esc_textarea( $group_and_urls['css'] ?? '' ); ?></textarea>
						<div class="code-tags default-bg">&lt;/style&gt;</div>
						<?php
						$content = ob_get_clean();
						
						// Include accordion component.
						include WP_PLUGIN_DIR . '/webchangedetector/admin/partials/components/ui-elements/accordion.php';
						?>
					</td>
				</tr>
			</table>
			
			<?php submit_button( 'Save Settings', 'primary', 'submit', true, array( 'onclick' => 'return wcdValidateFormAutoSettings()' ) ); ?>
		</form>
	</div>

	<script type="text/javascript">
	// Toggle monitoring settings visibility with slide animation
	jQuery(document).ready(function($) {
		// Listen for changes on the toggle switch
		$(document).on('change', 'input[name="enabled"]', function() {
			if ($(this).is(':checked')) {
				$('.monitoring-setting').slideDown();
			} else {
				$('.monitoring-setting').slideUp();
			}
		});
	});
	
	function wcdValidateFormAutoSettings() {
		// Only validate if monitoring is enabled
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