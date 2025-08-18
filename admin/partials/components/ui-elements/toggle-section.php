<?php
/**
 * Toggle Section Component
 *
 * Reusable component for sections that can be toggled on/off with a checkbox.
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
 * @var string $toggle_name      Name for the toggle checkbox
 * @var bool   $is_enabled       Whether the toggle is currently enabled
 * @var string $toggle_label     Label for the toggle
 * @var string $toggle_description Description for the toggle
 * @var string $content          Content HTML to show/hide (should be escaped by caller)
 * @var string $css_class        Optional CSS classes
 * @var string $section_id       Unique ID for the toggle section
 */

$toggle_name        = $toggle_name ?? 'toggle_enabled';
$is_enabled         = $is_enabled ?? false;
$toggle_label       = $toggle_label ?? __( 'Enable', 'webchangedetector' );
$toggle_description = $toggle_description ?? '';
$content            = $content ?? '';
$css_class          = $css_class ?? '';
$section_id         = $section_id ?? 'toggle-section-' . wp_rand( 1000, 9999 );
?>

<div class="setting-row <?php echo esc_attr( $css_class ); ?>">
	<label for="<?php echo esc_attr( $toggle_name ); ?>"><?php echo esc_html( $toggle_label ); ?></label>
	<label class="wcd-modern-switch">
		<input 
			type="checkbox" 
			name="<?php echo esc_attr( $toggle_name ); ?>" 
			id="<?php echo esc_attr( $toggle_name ); ?>" 
			value="1"
			<?php checked( $is_enabled ); ?>
			onchange="toggleSection('<?php echo esc_js( $section_id ); ?>', this.checked)"
		>
		<span class="wcd-modern-slider"></span>
	</label>
	<?php if ( $toggle_description ) : ?>
		<br><small><?php echo esc_html( $toggle_description ); ?></small>
	<?php endif; ?>
</div>

<div 
	id="<?php echo esc_attr( $section_id ); ?>" 
	style="<?php echo $is_enabled ? '' : 'display: none;'; ?>"
>
	<?php echo $content; // Content should be escaped by caller. ?>.
</div>

<script type="text/javascript">
function toggleSection(sectionId, isEnabled) {
	var section = document.getElementById(sectionId);
	if (section) {
		if (isEnabled) {
			section.style.display = '';
			// Use slideDown effect if jQuery is available.
			if (typeof jQuery !== 'undefined') {
				jQuery('#' + sectionId).slideDown();
			}
		} else {
			// Use slideUp effect if jQuery is available.
			if (typeof jQuery !== 'undefined') {
				jQuery('#' + sectionId).slideUp();
			} else {
				section.style.display = 'none';
			}
		}
	}
}

// Initialize on page load.
document.addEventListener('DOMContentLoaded', function() {
	var checkbox = document.getElementById('<?php echo esc_js( $toggle_name ); ?>');
	if (checkbox) {
		toggleSection('<?php echo esc_js( $section_id ); ?>', checkbox.checked);
	}
});
</script> 