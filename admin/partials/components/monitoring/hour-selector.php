<?php
/**
 * Hour Selector Component
 *
 * Reusable component for selecting the hour of day for monitoring.
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
 * @var int    $current_hour    Current hour value (0-23)
 * @var string $field_name      Form field name
 * @var string $label           Label text
 * @var string $description     Description text
 * @var string $css_class       Optional CSS classes
 */

$current_hour = $current_hour ?? 0;
$field_name   = $field_name ?? 'hour_of_day';
$label        = $label ?? __( 'Hour of the day', 'webchangedetector' );
$description  = $description ?? __( 'Set the hour on which the monitoring checks should be done.', 'webchangedetector' );
$css_class    = $css_class ?? '';
?>

<div class="setting-row <?php echo esc_attr( $css_class ); ?>">
	<label for="<?php echo esc_attr( $field_name ); ?>"><?php echo esc_html( $label ); ?></label>
	<select name="<?php echo esc_attr( $field_name ); ?>" id="<?php echo esc_attr( $field_name ); ?>" class="auto-setting">
		<?php for ( $i = 0; $i < 24; $i++ ) : ?>
			<option 
				class="select-time" 
				value="<?php echo esc_attr( $i ); ?>" 
				<?php selected( $current_hour, $i ); ?>
			>
				<?php printf( '%02d:00', $i ); ?>
			</option>
		<?php endfor; ?>
	</select>
	<?php if ( $description ) : ?>
		<br><small><?php echo esc_html( $description ); ?></small>
	<?php endif; ?>
</div> 