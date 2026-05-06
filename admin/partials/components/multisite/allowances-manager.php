<?php
/**
 * Allowances Manager Component.
 *
 * Renders toggle switches for managing website allowances.
 * Used by the super admin in the network admin Sites page.
 *
 * Expected variables:
 * - $allowances       (array)  Current allowance values.
 * - $is_all_sites_mode (bool)  Whether "All Websites" is selected.
 * - $website_uuid     (string) The website UUID (single-site mode only).
 * - $wcd_blog_id      (int)    The blog ID (single-site mode only).
 *
 * @package    WebChangeDetector
 * @subpackage WebChangeDetector/admin/partials/components/multisite
 * @since      4.3.0
 */

namespace WebChangeDetector;

defined( 'ABSPATH' ) || exit;

$allowances        = $allowances ?? array();
$is_all_sites_mode = $is_all_sites_mode ?? false;
$website_uuid      = $website_uuid ?? '';
$wcd_blog_id       = $wcd_blog_id ?? 0;

$sections = WebChangeDetector_Allowances_Ajax_Handler::get_sections();
?>

<div class="wcd-allowances-manager">
	<div class="wcd-settings-card">
		<h2>
			<span class="dashicons dashicons-admin-network"></span>
			<?php esc_html_e( 'Allowances', 'webchangedetector' ); ?>
		</h2>

		<?php if ( $is_all_sites_mode ) : ?>
			<p><?php esc_html_e( 'Set which features are enabled for all sub-websites. Changes will apply to all registered sites.', 'webchangedetector' ); ?></p>
			<div class="notice notice-info inline" style="margin: 0 0 16px 0;">
				<p><?php esc_html_e( 'Saving will overwrite the allowances on all registered sub-sites.', 'webchangedetector' ); ?></p>
			</div>
		<?php else : ?>
			<p><?php esc_html_e( 'Set which features are enabled for this sub-website.', 'webchangedetector' ); ?></p>
		<?php endif; ?>

		<?php foreach ( $sections as $section ) : ?>
			<div class="wcd-allowances-section">
				<h4><?php echo esc_html( $section['title'] ); ?></h4>
				<p class="wcd-description"><?php echo esc_html( $section['description'] ); ?></p>

				<?php foreach ( $section['fields'] as $field => $label ) : ?>
					<label class="wcd-toggle-label">
						<input type="hidden" name="allowances_<?php echo esc_attr( $field ); ?>" value="0">
						<span class="wcd-toggle-switch">
							<input type="checkbox" name="allowances_<?php echo esc_attr( $field ); ?>" value="1" <?php checked( ! empty( $allowances[ $field ] ) ); ?>>
							<span class="wcd-toggle-slider"></span>
						</span>
						<?php echo esc_html( $label ); ?>
					</label>
				<?php endforeach; ?>
			</div>
		<?php endforeach; ?>

		<div class="wcd-allowances-actions">
			<input type="hidden" id="wcd-allowances-website-uuid" value="<?php echo esc_attr( $website_uuid ); ?>">
			<input type="hidden" id="wcd-allowances-blog-id" value="<?php echo esc_attr( $is_all_sites_mode ? 'all' : $wcd_blog_id ); ?>">
			<button type="button" class="button button-primary" id="wcd-save-allowances">
				<?php esc_html_e( 'Save Allowances', 'webchangedetector' ); ?>
			</button>
			<span id="wcd-save-allowances-status"></span>
		</div>
	</div>
</div>
