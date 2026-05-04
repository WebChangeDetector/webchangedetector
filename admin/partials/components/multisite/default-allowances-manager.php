<?php
/**
 * Default Allowances Manager Component.
 *
 * Renders an accordion containing toggle switches for the network-wide default
 * allowances that are applied to newly registered sub-sites.
 * Used by the super admin in the network admin Sites page (All Websites mode only).
 *
 * Expected variables:
 * - $default_allowances_values (array) Current default allowance values, falling back
 *                                      to the main site's API allowances when no
 *                                      network-wide default is configured yet.
 *
 * @package    WebChangeDetector
 * @subpackage WebChangeDetector/admin/partials/components/multisite
 * @since      4.3.0
 */

namespace WebChangeDetector;

defined( 'ABSPATH' ) || exit;

$default_allowances_values = isset( $default_allowances_values ) && is_array( $default_allowances_values )
	? $default_allowances_values
	: array();

$default_sections = WebChangeDetector_Allowances_Ajax_Handler::get_sections();
?>

<div class="wcd-default-allowances-manager">
	<div class="accordion">
		<div class="mm_accordion_title">
			<h3>
				<span class="dashicons dashicons-plus-alt"></span>
				<?php esc_html_e( 'Defaults for new sites', 'webchangedetector' ); ?>
			</h3>
		</div>
		<div class="mm_accordion_content">
			<div class="wcd-settings-card">
				<p><?php esc_html_e( 'These settings are applied automatically when a new sub-site is registered with WebChange Detector. Existing sites are not affected.', 'webchangedetector' ); ?></p>

				<?php foreach ( $default_sections as $section ) : ?>
					<div class="wcd-allowances-section">
						<h4><?php echo esc_html( $section['title'] ); ?></h4>
						<p class="wcd-description"><?php echo esc_html( $section['description'] ); ?></p>

						<?php foreach ( $section['fields'] as $field => $label ) : ?>
							<label class="wcd-toggle-label">
								<input type="hidden" name="default_allowances_<?php echo esc_attr( $field ); ?>" value="0">
								<span class="wcd-toggle-switch">
									<input type="checkbox" name="default_allowances_<?php echo esc_attr( $field ); ?>" value="1" <?php checked( ! empty( $default_allowances_values[ $field ] ) ); ?>>
									<span class="wcd-toggle-slider"></span>
								</span>
								<?php echo esc_html( $label ); ?>
							</label>
						<?php endforeach; ?>
					</div>
				<?php endforeach; ?>

				<div class="wcd-allowances-actions">
					<button type="button" class="button button-primary" id="wcd-save-default-allowances">
						<?php esc_html_e( 'Save defaults for new sites', 'webchangedetector' ); ?>
					</button>
					<span id="wcd-save-default-allowances-status"></span>
				</div>
			</div>
		</div>
	</div>
</div>
