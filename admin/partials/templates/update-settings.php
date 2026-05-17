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
	$weekdays      = array( 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday' );
	foreach ( $weekdays as $weekday ) {
		$weekdays_data[ $weekday ] = ! empty( $auto_update_settings[ 'auto_update_checks_' . $weekday ] );
	}

	$auto_update_checks_enabled = ! empty( $auto_update_settings['auto_update_checks_enabled'] ) && ( true === $auto_update_settings['auto_update_checks_enabled'] || '1' === $auto_update_settings['auto_update_checks_enabled'] || 1 === $auto_update_settings['auto_update_checks_enabled'] );

	// Multisite Subsites inherit schedule + emails from the main site. Only the
	// auto_update_checks_enabled toggle stays editable per subsite — it controls
	// whether THIS subsite's URLs are included in the network-wide checks.
	$is_multisite_subsite = \WebChangeDetector\WebChangeDetector_Multisite::is_multisite_subsite();
	$is_multisite_main    = \WebChangeDetector\WebChangeDetector_Multisite::is_multisite_active() && is_main_site();
	$parent_domain        = '';
	if ( $is_multisite_subsite && ! empty( $this->admin->website_details['parent_multisite_website']['domain'] ) ) {
		$parent_domain = $this->admin->website_details['parent_multisite_website']['domain'];
	}

	// On a multisite-network main site the schedule + emails govern the whole
	// network — they must stay visible even when main's own toggle is OFF, so a
	// super-admin can still configure timing for participating subsites.
	$schedule_hidden_style = ( $auto_update_checks_enabled || $is_multisite_main ) ? '' : 'display: none;';
	?>

	<div class="wcd-settings-card">
		<h2><?php esc_html_e( 'WP Auto Update & On-Demand Checks Settings', 'webchangedetector' ); ?></h2>
		<form action="<?php echo esc_url( \WebChangeDetector\WebChangeDetector_Multisite::get_form_action_url( 'webchangedetector-update-settings' ) ); ?>" method="post"<?php echo $is_multisite_main ? ' class="wcd-multisite-main"' : ''; ?>>
			<input type="hidden" name="wcd_action" value="save_group_settings">
			<input type="hidden" name="step" value="pre-update">
			<input type="hidden" name="group_id" value="<?php echo esc_html( $group_id ); ?>">
			<?php wp_nonce_field( 'save_group_settings' ); ?>
			<?php \WebChangeDetector\WebChangeDetector_Multisite::render_blog_context_field(); ?>

			<div class="notice notice-info inline wcd-auto-updates-precondition-notice">
				<p>
					<span class="dashicons dashicons-info"></span>
					<?php esc_html_e( 'WP auto updates have to be enabled. This option only enables checks during auto updates.', 'webchangedetector' ); ?>
				</p>
			</div>

			<?php if ( $is_multisite_subsite ) : ?>
				<div class="notice notice-warning inline wcd-multisite-inherited-notice">
					<p>
						<span class="dashicons dashicons-info"></span>
						<?php
						if ( '' !== $parent_domain ) {
							echo wp_kses(
								sprintf(
									/* translators: %s: domain of the multisite main site. */
									__( 'Schedule and notification emails for auto-update checks are managed network-wide on the multisite main site (<strong>%s</strong>). The toggle below decides whether this subsite participates in the network-wide checks.', 'webchangedetector' ),
									esc_html( $parent_domain )
								),
								array( 'strong' => array() )
							);
						} else {
							esc_html_e( 'Schedule and notification emails for auto-update checks are managed network-wide on the multisite main site. The toggle below decides whether this subsite participates in the network-wide checks.', 'webchangedetector' );
						}
						?>
					</p>
				</div>
			<?php elseif ( $is_multisite_main ) : ?>
				<div class="notice notice-warning inline wcd-multisite-main-notice">
					<p>
						<span class="dashicons dashicons-info"></span>
						<?php esc_html_e( 'The schedule and notification email settings on this page apply network-wide — they are inherited by every subsite. Each subsite admin can still decide via their own auto-update toggle whether the subsite participates in the network-wide checks.', 'webchangedetector' ); ?>
					</p>
				</div>
			<?php endif; ?>

			<div class="wcd-form-row wcd-auto-update-setting-enabled">
				<div class="wcd-form-label-wrapper">
					<label class="wcd-form-label"><?php esc_html_e( 'Auto Update Checks', 'webchangedetector' ); ?></label>
				</div>
				<div class="wcd-form-control">
					<?php
					// Auto Update Checks Toggle (without content).
					$toggle_name        = 'auto_update_checks_enabled';
					$is_enabled         = $auto_update_checks_enabled;
					$toggle_label       = '';
					$toggle_description = '';
					$section_id         = 'auto_update_checks_settings';
					$content            = ''; // Empty content for the toggle.

					// Include Toggle Section Component.
					include WCD_PLUGIN_DIR . 'admin/partials/components/ui-elements/toggle-section.php';
					?>
				</div>
				<!-- Auto Update Information Accordion - Always visible underneath the toggle -->
				<div class="wcd-form-row wcd-auto-update-setting-enabled-auto-updates">
					<div class="wcd-form-control" style="grid-column: 1 / -1;">
						<?php
						// Auto Update Information Component.
						include WCD_PLUGIN_DIR . 'admin/partials/components/settings/auto-update-info.php';
						?>
					</div>
				</div>
			</div>

			<?php if ( $is_multisite_subsite ) : ?>
				<fieldset class="wcd-multisite-inherited-fieldset" disabled>
			<?php endif; ?>

			<div class="wcd-form-row auto-update-setting wcd-auto-update-setting-from" style="<?php echo esc_attr( $schedule_hidden_style ); ?>">
				<div class="wcd-form-label-wrapper">
					<label class="wcd-form-label"><?php esc_html_e( 'Auto Update Timeframe', 'webchangedetector' ); ?></label>
					<div class="wcd-description"><?php esc_html_e( 'Set the time frame in which you want to allow WP auto updates.', 'webchangedetector' ); ?></div>
				</div>
				<div class="wcd-form-control wcd-inline">
					<?php
					// Time Range Selector Component.
					// Convert UTC times from API to site timezone for display.
					require_once WCD_PLUGIN_DIR . 'admin/class-webchangedetector-timezone-helper.php';
					$utc_from_time = $auto_update_settings['auto_update_checks_from'] ?? gmdate( 'H:i' );
					$utc_to_time   = $auto_update_settings['auto_update_checks_to'] ?? gmdate( 'H:i', strtotime( '+2 hours' ) );
					$from_time     = \WebChangeDetector\WebChangeDetector_Timezone_Helper::utc_to_site_time( $utc_from_time );
					$to_time       = \WebChangeDetector\WebChangeDetector_Timezone_Helper::utc_to_site_time( $utc_to_time );
					$from_name     = 'auto_update_checks_from';
					$to_name       = 'auto_update_checks_to';
					$label         = __( 'Only', 'webchangedetector' );
					$description   = '';
					include WCD_PLUGIN_DIR . 'admin/partials/components/forms/time-range-selector.php';
					?>
				</div>
				<div class="local-timezone"></div>
			</div>

			<div class="wcd-form-row auto-update-setting wcd-auto-update-setting-weekday" style="<?php echo esc_attr( $schedule_hidden_style ); ?>">
				<div class="wcd-form-label-wrapper">
					<label class="wcd-form-label"><?php esc_html_e( 'Weekdays', 'webchangedetector' ); ?></label>
					<div class="wcd-description"><?php esc_html_e( 'Set the weekdays in which you want to allow WP auto updates.', 'webchangedetector' ); ?></div>
				</div>
				<div class="wcd-form-control">
					<?php
					// Weekday Selector Component.
					$selected_days   = $weekdays_data;
					$name_prefix     = 'auto_update_checks_';
					$label           = '';
					$description     = '';
					$show_validation = true;
					include WCD_PLUGIN_DIR . 'admin/partials/components/forms/weekday-selector.php';
					?>
				</div>
			</div>

			<div class="wcd-form-row auto-update-setting wcd-auto-update-setting-emails" style="<?php echo esc_attr( $schedule_hidden_style ); ?>">
				<div class="wcd-form-label-wrapper">
					<label class="wcd-form-label"><?php esc_html_e( 'Notifications', 'webchangedetector' ); ?></label>
					<div class="wcd-description">
						<?php esc_html_e( 'Enter the email address(es) which should get notified about auto update checks.', 'webchangedetector' ); ?><br>
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
					$emails_raw      = $auto_update_settings['auto_update_checks_emails'] ?? '';
					$email_value     = is_array( $emails_raw ) ? implode( ', ', $emails_raw ) : $emails_raw;
					$field_name      = 'auto_update_checks_emails';
					$label           = __( 'Notification email to', 'webchangedetector' );
					$description     = '';
					$multiple        = true;
					$show_validation = true;
					include WCD_PLUGIN_DIR . 'admin/partials/components/forms/email-input.php';
					?>
					
				</div>
			</div>

			<?php if ( $is_multisite_subsite ) : ?>
				</fieldset>
			<?php endif; ?>

			<div class="wcd-form-row wcd-auto-update-setting-threshold">
				<div class="wcd-form-label-wrapper">
					<label class="wcd-form-label"><?php esc_html_e( 'Change Detection Threshold', 'webchangedetector' ); ?></label>
					<div class="wcd-description"><?php esc_html_e( 'Ignore changes in Change Detections below the threshold. Use this carefully. If you set it too low, you might miss changes that are important.', 'webchangedetector' ); ?></div>
				</div>
				<div class="wcd-form-control wcd-inline">
					<?php
					// Threshold Setting Component.
					$label       = '';
					$description = '';
					$threshold   = $group_and_urls['threshold'] ?? 0.0;
					include WCD_PLUGIN_DIR . 'admin/partials/components/forms/threshold-setting.php';
					?>
				</div>
			</div>

			<?php include WCD_PLUGIN_DIR . 'admin/partials/components/settings/advanced-screenshot-settings.php'; ?>

			<div class="wcd-form-row wcd-auto-update-setting-css">
				<div class="wcd-form-label-wrapper">
					<label class="wcd-form-label"><?php esc_html_e( 'CSS Settings', 'webchangedetector' ); ?></label>
					<div class="wcd-description"><?php esc_html_e( 'Hide or modify elements via CSS before taking screenshots (e.g. dynamic content).', 'webchangedetector' ); ?></div>
				</div>
				<div class="wcd-form-control">
					<div class="wcd-code-injection-wrapper">
						<div class="code-tags default-bg">&lt;style&gt;</div>
						<textarea name="css" class="codearea wcd-css-textarea" rows="15" cols="80"><?php echo esc_textarea( $group_and_urls['css'] ?? '' ); ?></textarea>
						<div class="code-tags default-bg">&lt;/style&gt;</div>
					</div>
				</div>
			</div>

			<div class="wcd-form-row wcd-auto-update-setting-js">
				<div class="wcd-form-label-wrapper">
					<label class="wcd-form-label"><?php esc_html_e( 'JS Settings', 'webchangedetector' ); ?></label>
					<div class="wcd-description"><?php esc_html_e( 'Run custom JavaScript before taking screenshots (e.g. close popups, trigger interactions).', 'webchangedetector' ); ?></div>
				</div>
				<div class="wcd-form-control">
					<div class="wcd-code-injection-wrapper">
						<div class="code-tags default-bg">&lt;script&gt;</div>
						<textarea name="js" class="codearea wcd-js-textarea" rows="15" cols="80"><?php echo esc_textarea( $group_and_urls['js'] ?? '' ); ?></textarea>
						<div class="code-tags default-bg">&lt;/script&gt;</div>
					</div>
				</div>
			</div>

			<input type="hidden" name="group_name" value="<?php echo esc_html( $group_and_urls['name'] ?? '' ); ?>">

			<?php submit_button( __( 'Save Settings', 'webchangedetector' ), 'primary', 'submit', true, array( 'onclick' => 'return wcdValidateFormGroupSettings()' ) ); ?>
		</form>
	</div>
	<?php
	// Toggle behavior + wcdValidateFormGroupSettings() live in
	// admin/js/webchangedetector-admin.js (extracted to comply with the
	// "no inline JS" project rule).
}
?>