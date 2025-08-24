<?php
/**
 * Threshold Setting Component
 *
 * Reusable component for threshold input field.
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
 * @var float  $threshold     The current threshold value
 * @var string $label         Optional custom label (defaults to "Threshold")
 * @var string $description   Optional custom description
 * @var string $css_class     Optional additional CSS classes
 */

$threshold   = $threshold ?? 0.0;
$label       = $label ?? __( 'Threshold', 'webchangedetector' );
$description = $description ?? __( 'Only flag changes above this threshold percentage as significant.', 'webchangedetector' );
$css_class   = $css_class ?? '';
?>

<div class="setting-row <?php echo esc_attr( $css_class ); ?>">
	<label for="threshold"><?php echo esc_html( $label ); ?></label>
	<input 
		name="threshold" 
		class="threshold" 
		type="number" 
		step="0.1" 
		min="0" 
		max="100" 
		value="<?php echo esc_attr( $threshold ); ?>"
		id="threshold"
	> %
	<?php if ( $description ) : ?>
		<br><small><?php echo esc_html( $description ); ?></small>
	<?php endif; ?>
</div> 