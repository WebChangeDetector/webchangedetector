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
	<form class="wcd-frm-settings box-plain" action="admin.php?page=webchangedetector-auto-settings" method="post">
		<input type="hidden" name="wcd_action" value="save_group_settings">
		<input type="hidden" name="group_id" value="<?php echo esc_html( $group_id ); ?>">
		<?php wp_nonce_field( 'save_group_settings' ); ?>

		<h2>Settings</h2>
		<p style="text-align: center;">Monitor your website and receive alert emails when something changes.</p>
		
		<div class="setting-container-column">
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
			// Hour Selector Component.
			$current_hour = $group_and_urls['hour_of_day'] ?? 0;
			include WP_PLUGIN_DIR . '/webchangedetector/admin/partials/components/monitoring/hour-selector.php';
			?>

			<?php
			// Interval Selector Component.
			$current_interval = $group_and_urls['interval_in_h'] ?? 24;
			$account_details = $this->account_handler->get_account();
			$show_minute_intervals = false;
			if ( ! $account_details['is_subaccount'] && ! in_array( $account_details['plan'], array( 'trial', 'free', 'personal', 'personal_pro' ), true ) ) {
				$show_minute_intervals = true;
			}
			include WP_PLUGIN_DIR . '/webchangedetector/admin/partials/components/monitoring/interval-selector.php';
			?>

			<?php
			// Threshold Setting Component.
			$threshold = $group_and_urls['threshold'] ?? 0.0;
			include WP_PLUGIN_DIR . '/webchangedetector/admin/partials/components/forms/threshold-setting.php';
			?>

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
			
			<?php
			$content = ob_get_clean();
			
			// Include Toggle Section Component.
			include WP_PLUGIN_DIR . '/webchangedetector/admin/partials/components/ui-elements/toggle-section.php';
			?>
		</div>
		
		<div class="setting-container-column last">
			<?php require 'css-settings.php'; ?>
		</div>
		
		<div class="clear"></div>
		
		<button
			class="button button-primary wizard-save-auto-settings"
			style="margin-top: 20px;"
			type="submit"
			onclick="return wcdValidateFormAutoSettings()"
		>
			Save
		</button>
	</form>

	<script type="text/javascript">
	function wcdValidateFormAutoSettings() {
		// Validate email if present.
		if (typeof window['validate_alert_emails'] === 'function') {
			if (!window['validate_alert_emails']()) {
				return false;
			}
		}
		
		return true;
	}
	</script>
	<?php
}
?> 