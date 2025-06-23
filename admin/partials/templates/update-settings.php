<?php
/**
 * Manual checks settings - Refactored with Components
 *
 * @package    webchangedetector
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Are we allowed to see the settings?
if ( ! empty( $this->admin->website_details['allowances']['manual_checks_settings'] ) && $this->admin->website_details['allowances']['manual_checks_settings'] ) {

	$auto_update_settings = $this->admin->website_details['auto_update_settings'];

	// Prepare weekday data for component.
	$weekdays_data = array();
	$weekdays = array( 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday' );
	foreach ( $weekdays as $weekday ) {
		$weekdays_data[ $weekday ] = ! empty( $auto_update_settings[ 'auto_update_checks_' . $weekday ] );
	}

	$auto_update_checks_enabled = ! empty( $auto_update_settings['auto_update_checks_enabled'] ) && '1' === $auto_update_settings['auto_update_checks_enabled'];
	?>

	<div class="box-plain no-border">
		<h2>WP Auto Update & Manual Checks Settings</h2>
		<form action="admin.php?page=webchangedetector-update-settings" method="post">
			<input type="hidden" name="wcd_action" value="save_group_settings">
			<input type="hidden" name="step" value="pre-update">
			<input type="hidden" name="group_id" value="<?php echo esc_html( $group_id ); ?>">
			<?php wp_nonce_field( 'save_group_settings' ); ?>
			
			<table class="form-table">
				<tr valign="top">
					<th scope="row">
						<label>Auto Update Checks</label>
					</th>
					<td>
						<?php
						// Auto Update Checks Toggle.
						$toggle_name = 'auto_update_checks_enabled';
						$is_enabled = $auto_update_checks_enabled;
						$toggle_label = 'Auto Update Checks';
						$toggle_description = 'WP auto updates have to be enabled. This option only enables checks during auto updates.';
						$section_id = 'auto_update_checks_settings';
						
						// Build the content for the auto update settings.
						ob_start();
						// Auto Update Information Component.
						include WP_PLUGIN_DIR . '/webchangedetector/admin/partials/components/settings/auto-update-info.php';
						$content = ob_get_clean();
						
						// Include Toggle Section Component.
						include WP_PLUGIN_DIR . '/webchangedetector/admin/partials/components/ui-elements/toggle-section.php';
						?>
					</td>
				</tr>
				<tr valign="top" class="auto-update-setting" style="<?php echo $auto_update_checks_enabled ? '' : 'display: none;'; ?>">
					<th scope="row">
						<label>Auto Update Timeframe</label>
					</th>
					<td>
						<?php
						// Time Range Selector Component.
						$from_time = $auto_update_settings['auto_update_checks_from'] ?? gmdate( 'H:i' );
						$to_time = $auto_update_settings['auto_update_checks_to'] ?? gmdate( 'H:i', strtotime( '+2 hours' ) );
						$from_name = 'auto_update_checks_from';
						$to_name = 'auto_update_checks_to';
						$label = 'Only';
						$description = 'Set the time frame in which you want to allow WP auto updates.';
						include WP_PLUGIN_DIR . '/webchangedetector/admin/partials/components/forms/time-range-selector.php';
						?>
					</td>
				</tr>
				<tr valign="top" class="auto-update-setting" style="<?php echo $auto_update_checks_enabled ? '' : 'display: none;'; ?>">
					<th scope="row">
						<label>Weekdays</label>
					</th>
					<td>
						<?php
						// Weekday Selector Component.
						$selected_days = $weekdays_data;
						$name_prefix = 'auto_update_checks_';
						$label = 'Only on these weekdays';
						$description = 'Set the weekdays in which you want to allow WP auto updates.';
						$show_validation = true;
						include WP_PLUGIN_DIR . '/webchangedetector/admin/partials/components/forms/weekday-selector.php';
						?>
					</td>
				</tr>
				<tr valign="top" class="auto-update-setting" style="<?php echo $auto_update_checks_enabled ? '' : 'display: none;'; ?>">
					<th scope="row">
						<label>Notification Email</label>
					</th>
					<td>
						<?php
						// Email Input Component.
						$email_value = $auto_update_settings['auto_update_checks_emails'] ?? get_option( 'admin_email' );
						$field_name = 'auto_update_checks_emails';
						$label = 'Notification email to';
						$description = 'Enter the email address(es) which should get notified about auto update checks.';
						$multiple = true;
						$show_validation = true;
						include WP_PLUGIN_DIR . '/webchangedetector/admin/partials/components/forms/email-input.php';
						?>
					</td>
				</tr>
                <tr valign="top">
					<th scope="row">
						<label>Change Detection Threshold</label>
					</th>
					<td>
						<?php
						// Threshold Setting Component.
                        $label = 'Threshold';
						$description = 'Ignore changes in Change Detections below the threshold. Use this carefully. If you set it too low, you might miss changes that are important.';
						$threshold = $group_and_urls['threshold'] ?? 0.0;
						include WP_PLUGIN_DIR . '/webchangedetector/admin/partials/components/forms/threshold-setting.php';
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
						$accordion_id = 'css-injection-manual';
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
			
			<input type="hidden" name="group_name" value="<?php echo esc_html( $group_and_urls['name'] ?? '' ); ?>">
			
			<?php submit_button( 'Save Settings', 'primary', 'submit', true, array( 'onclick' => 'return wcdValidateFormGroupSettings()' ) ); ?>
		</form>
	</div>

    <hr>
    

	<script type="text/javascript">
	// Toggle auto update checks settings visibility with slide animation
	jQuery(document).ready(function($) {
		// Listen for changes on the toggle switch
		$(document).on('change', 'input[name="auto_update_checks_enabled"]', function() {
			if ($(this).is(':checked')) {
				$('.auto-update-setting').slideDown();
			} else {
				$('.auto-update-setting').slideUp();
			}
		});
	});
	
	function wcdValidateFormGroupSettings() {
		// Only validate if auto update checks are enabled
		var autoUpdateEnabled = document.querySelector('input[name="auto_update_checks_enabled"]');
		if (autoUpdateEnabled && autoUpdateEnabled.checked) {
			// Validate weekdays.
			var weekdayCheckboxes = document.querySelectorAll('input[name*="auto_update_checks_"]:checked');
			if (weekdayCheckboxes.length === 0) {
				var errorElement = document.getElementById('error-on-days-validation');
				if (errorElement) {
					errorElement.style.display = 'block';
				}
				return false;
			}
			
			// Validate email if present.
			if (typeof window['validate_auto_update_checks_emails'] === 'function') {
				if (!window['validate_auto_update_checks_emails']()) {
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