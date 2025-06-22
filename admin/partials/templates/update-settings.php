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

	<form class="wcd-frm-settings box-plain" action="admin.php?page=webchangedetector-update-settings" method="post">
		<input type="hidden" name="wcd_action" value="save_group_settings">
		<input type="hidden" name="step" value="pre-update">
		<input type="hidden" name="group_id" value="<?php echo esc_html( $group_id ); ?>">
		<?php wp_nonce_field( 'save_group_settings' ); ?>
		
		<h2>Settings</h2>
		<p style="text-align: center;">Make all settings for auto-update checks and for manual checks.</p>
		
		<div class="setting-container-column">
			<?php
			// Threshold Setting Component.
			$threshold = $group_and_urls['threshold'] ?? 0.0;
			include WP_PLUGIN_DIR . '/webchangedetector/admin/partials/components/forms/threshold-setting.php';
			?>

			<?php
			// Auto Update Checks Toggle with Content.
			$toggle_name = 'auto_update_checks_enabled';
			$is_enabled = $auto_update_checks_enabled;
			$toggle_label = 'Checks at WP auto updates';
			$toggle_description = 'WP auto updates have to be enabled. This option only enables checks during auto updates.';
			$section_id = 'auto_update_checks_settings';
			
			// Build the content for the toggle section.
			ob_start();
			?>
			
			<?php
			// Auto Update Information Component.
			include WP_PLUGIN_DIR . '/webchangedetector/admin/partials/components/settings/auto-update-info.php';
			?>

			<?php
			// Time Range Selector Component.
			$from_time = $auto_update_settings['auto_update_checks_from'] ?? gmdate( 'H:i' );
			$to_time = $auto_update_settings['auto_update_checks_to'] ?? gmdate( 'H:i', strtotime( '+2 hours' ) );
			$from_name = 'auto_update_checks_from';
			$to_name = 'auto_update_checks_to';
			$label = 'Auto update times';
			$description = 'Set the time frame in which you want to allow WP auto updates.';
			include WP_PLUGIN_DIR . '/webchangedetector/admin/partials/components/forms/time-range-selector.php';
			?>

			<?php
			// Weekday Selector Component.
			$selected_days = $weekdays_data;
			$name_prefix = 'auto_update_checks_';
			$label = 'On days';
			$description = 'Set the weekdays in which you want to allow WP auto updates.';
			$show_validation = true;
			include WP_PLUGIN_DIR . '/webchangedetector/admin/partials/components/forms/weekday-selector.php';
			?>

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
			
			<?php
			$content = ob_get_clean();
			
			// Include Toggle Section Component.
			include WP_PLUGIN_DIR . '/webchangedetector/admin/partials/components/ui-elements/toggle-section.php';
			?>

			<input type="hidden" name="group_name" value="<?php echo esc_html( $group_and_urls['name'] ?? '' ); ?>">
		</div>
		
		<div class="setting-container-column last">
			<?php require 'css-settings.php'; ?>
		</div>
		
		<div class="clear"></div>
		
		<button 
			class="button button-primary wizard-save-group-settings"
			style="margin-top: 20px;"
			type="submit"
			onclick="return wcdValidateFormGroupSettings()"
		>
			Save
		</button>
	</form>

	<?php if ( $this->is_allowed( 'manual_checks_start' ) && $group_and_urls['selected_urls_count'] > 0 ) { ?>
	<form action="admin.php?page=webchangedetector-update-settings" method="post" style="margin-top: 20px;">
		<input type="hidden" name="wcd_action" value="start_manual_checks">
		<?php wp_nonce_field( 'start_manual_checks' ); ?>
		<button type="submit" class="button button-secondary">
			<?php \WebChangeDetector\WebChangeDetector_Admin_Utils::get_device_icon( 'update-group' ); ?>
			Start Manual Checks
		</button>
		<p style="margin-top: 10px; color: #666; font-style: italic;">
			Click to start the manual checks workflow for taking pre- and post-update screenshots.
		</p>
	</form>
	<?php } ?>

	<script type="text/javascript">
	function wcdValidateFormGroupSettings() {
		// Validate weekdays.
		var weekdayCheckboxes = document.querySelectorAll('input[name*="auto_update_checks_"]:checked');
		if (weekdayCheckboxes.length === 0) {
			document.getElementById('error-on-days-validation').style.display = 'block';
			return false;
		}
		
		// Validate email if present.
		if (typeof window['validate_auto_update_checks_emails'] === 'function') {
			if (!window['validate_auto_update_checks_emails']()) {
				return false;
			}
		}
		
		return true;
	}
	</script>
	<?php
}
?> 