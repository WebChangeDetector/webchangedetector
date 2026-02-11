<?php
/**
 * Advanced Screenshot Settings Component
 *
 * Shared component for Basic Authentication, Static IP Proxy,
 * and Screenshot Delay settings. Used in both monitoring and
 * manual check settings templates.
 *
 * @package    WebChangeDetector
 * @subpackage WebChangeDetector/admin/partials/components/settings
 *
 * Expected variables in scope:
 * @var array  $group_and_urls          Group data from the API.
 * @var string $advanced_settings_class Optional extra CSS class for each row.
 * @var string $advanced_settings_style Optional inline style for each row.
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$has_basic_auth               = ! empty( $group_and_urls['has_basic_auth'] );
$advanced_settings_class      = $advanced_settings_class ?? '';
$advanced_settings_style      = $advanced_settings_style ?? '';
$advanced_settings_style_attr = $advanced_settings_style ? ' style="' . esc_attr( $advanced_settings_style ) . '"' : '';
?>

<!-- Basic Authentication -->
<div class="wcd-form-row wcd-setting-basic-auth <?php echo esc_attr( $advanced_settings_class ); ?>"<?php echo $advanced_settings_style_attr; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Pre-escaped on assignment. ?>>
	<div class="wcd-form-label-wrapper">
		<label class="wcd-form-label"><?php esc_html_e( 'Basic Authentication', 'webchangedetector' ); ?></label>
		<div class="wcd-description"><?php esc_html_e( 'Provide credentials for password-protected pages that require authentication.', 'webchangedetector' ); ?></div>
	</div>
	<div class="wcd-form-control">
		<div class="wcd-advanced-setting-fields">
			<div class="wcd-field-group">
				<label for="basic_auth_user"><?php esc_html_e( 'Username', 'webchangedetector' ); ?></label>
				<input type="text"
					id="basic_auth_user"
					name="basic_auth_user"
					value="<?php echo esc_attr( $group_and_urls['basic_auth_user'] ?? '' ); ?>"
					autocomplete="off">
			</div>
			<div class="wcd-field-group">
				<label for="basic_auth_password"><?php esc_html_e( 'Password', 'webchangedetector' ); ?></label>
				<div class="wcd-password-wrapper">
					<input type="password"
						id="basic_auth_password"
						name="basic_auth_password"
						class="wcd-password-field"
						value="<?php echo $has_basic_auth ? '••••••••' : ''; ?>"
						placeholder="<?php echo $has_basic_auth ? esc_attr( '••••••••' ) : ''; ?>"
						data-has-password="<?php echo $has_basic_auth ? '1' : '0'; ?>"
						data-original-value="<?php echo $has_basic_auth ? '••••••••' : ''; ?>"
						autocomplete="new-password">
					<input type="hidden"
						name="basic_auth_password_action"
						id="basic_auth_password_action"
						value="">
					<button type="button"
						class="wcd-toggle-password-btn"
						data-target="basic_auth_password"
						title="<?php esc_attr_e( 'Show/Hide password', 'webchangedetector' ); ?>">
						<span class="dashicons dashicons-visibility"></span>
					</button>
				</div>
				<small class="wcd-field-help">
					<?php
					if ( $has_basic_auth ) {
						esc_html_e( 'Clear the field to delete the password, or enter a new password to update it.', 'webchangedetector' );
					} else {
						esc_html_e( 'Enter a password to enable Basic Authentication.', 'webchangedetector' );
					}
					?>
				</small>
			</div>
		</div>
	</div>
</div>

<!-- Static IP Proxy -->
<div class="wcd-form-row wcd-setting-proxy <?php echo esc_attr( $advanced_settings_class ); ?>"<?php echo $advanced_settings_style_attr; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Pre-escaped on assignment. ?>>
	<div class="wcd-form-label-wrapper">
		<label class="wcd-form-label"><?php esc_html_e( 'Static IP Proxy', 'webchangedetector' ); ?></label>
		<div class="wcd-description"><?php esc_html_e( 'Route screenshot requests through a static IP address for servers that require IP whitelisting.', 'webchangedetector' ); ?></div>
	</div>
	<div class="wcd-form-control">
		<div class="wcd-proxy-toggle-wrapper">
			<input type="hidden" name="proxy_type" value="">
			<label class="wcd-modern-switch">
				<input type="checkbox"
					name="proxy_type"
					id="proxy_type"
					value="static"
					<?php checked( ! empty( $group_and_urls['proxy_type'] ) && 'static' === $group_and_urls['proxy_type'] ); ?>>
				<span class="wcd-modern-slider"></span>
			</label>
			<span class="wcd-toggle-label"><?php esc_html_e( 'Enable Static IP Proxy', 'webchangedetector' ); ?></span>
		</div>
		<div class="wcd-proxy-info-box">
			<span class="dashicons dashicons-shield-alt"></span>
			<span><?php esc_html_e( 'Static IP:', 'webchangedetector' ); ?></span>
			<code class="wcd-proxy-ip">34.139.186.198</code>
			<button type="button"
				class="wcd-copy-ip-btn"
				data-ip="34.139.186.198"
				title="<?php esc_attr_e( 'Copy IP address', 'webchangedetector' ); ?>">
				<span class="dashicons dashicons-admin-page"></span>
			</button>
		</div>
	</div>
</div>

<!-- Screenshot Delay -->
<div class="wcd-form-row wcd-setting-screenshot-delay <?php echo esc_attr( $advanced_settings_class ); ?>"<?php echo $advanced_settings_style_attr; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Pre-escaped on assignment. ?>>
	<div class="wcd-form-label-wrapper">
		<label class="wcd-form-label"><?php esc_html_e( 'Screenshot Delay', 'webchangedetector' ); ?></label>
		<div class="wcd-description"><?php esc_html_e( 'Adjust the delay between screenshot starts to reduce server load.', 'webchangedetector' ); ?></div>
	</div>
	<div class="wcd-form-control">
		<div class="wcd-inline">
			<input type="number"
				id="screenshot_delay"
				name="screenshot_delay"
				min="7"
				max="60"
				step="1"
				placeholder="<?php esc_attr_e( '7 (default)', 'webchangedetector' ); ?>"
				value="<?php echo esc_attr( $group_and_urls['screenshot_delay'] ?? '' ); ?>">
			<span><?php esc_html_e( 'Seconds', 'webchangedetector' ); ?></span>
		</div>
		<small class="wcd-field-help">
			<?php esc_html_e( 'Minimum 7 seconds, maximum 60 seconds. Leave empty for default (7 seconds).', 'webchangedetector' ); ?>
		</small>
	</div>
</div>
