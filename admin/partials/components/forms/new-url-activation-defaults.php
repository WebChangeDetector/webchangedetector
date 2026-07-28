<?php
/**
 * New-URL Activation Defaults Component
 *
 * Reusable label + checkbox pair for the per-group "Activate newly synced URLs by default"
 * setting (default_desktop / default_mobile). Rendered by the monitoring settings template
 * (auto-settings.php) and the on-demand / auto-update settings template (update-settings.php).
 * The including template provides the outer .wcd-form-row wrapper, which differs per group.
 *
 * Uses the hidden-0 + checkbox-1 pattern so an unchecked box still submits a value.
 *
 * @package    WebChangeDetector
 * @subpackage WebChangeDetector/admin/partials/components
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Expected variables:
 *
 * @var array $group_and_urls The current group data, providing default_desktop / default_mobile.
 */
?>
<div class="wcd-form-label-wrapper">
	<label class="wcd-form-label"><?php esc_html_e( 'Activate newly synced URLs by default', 'webchangedetector' ); ?></label>
	<div class="wcd-description"><?php esc_html_e( 'When new URLs are added (e.g. on sync), activate them automatically for these screenshot types in this group.', 'webchangedetector' ); ?></div>
</div>
<div class="wcd-form-control">
	<label>
		<input type="hidden" name="default_desktop" value="0" />
		<input type="checkbox" name="default_desktop" value="1" <?php checked( ! empty( $group_and_urls['default_desktop'] ) ); ?> />
		<?php esc_html_e( 'Desktop', 'webchangedetector' ); ?>
	</label>
	<label>
		<input type="hidden" name="default_mobile" value="0" />
		<input type="checkbox" name="default_mobile" value="1" <?php checked( ! empty( $group_and_urls['default_mobile'] ) ); ?> />
		<?php esc_html_e( 'Mobile', 'webchangedetector' ); ?>
	</label>
</div>
