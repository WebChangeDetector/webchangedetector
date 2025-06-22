<?php
/**
 * Time Range Selector Component
 *
 * Reusable component for selecting time ranges (from/to times).
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
 * @var string $from_time        Current "from" time value
 * @var string $to_time          Current "to" time value  
 * @var string $from_name        Form field name for "from" time
 * @var string $to_name          Form field name for "to" time
 * @var string $label            Label text
 * @var string $description      Description text
 * @var string $css_class        Optional CSS classes
 */

$from_time = $from_time ?? gmdate( 'H:i' );
$to_time = $to_time ?? gmdate( 'H:i', strtotime( '+2 hours' ) );
$from_name = $from_name ?? 'time_from';
$to_name = $to_name ?? 'time_to';
$label = $label ?? 'Time range';
$description = $description ?? 'Set the time frame for the operation.';
$css_class = $css_class ?? '';
?>

<div class="setting-row toggle <?php echo esc_attr( $css_class ); ?>">
	<label for="<?php echo esc_attr( $from_name ); ?>"><?php echo esc_html( $label ); ?> from</label>
	<input 
		id="<?php echo esc_attr( $from_name ); ?>" 
		name="<?php echo esc_attr( $from_name ); ?>" 
		value="<?php echo esc_attr( $from_time ); ?>" 
		type="time" 
		class="<?php echo esc_attr( $from_name ); ?>"
	>
	<label for="<?php echo esc_attr( $to_name ); ?>" style="min-width: inherit"> to </label>
	<input 
		id="<?php echo esc_attr( $to_name ); ?>" 
		name="<?php echo esc_attr( $to_name ); ?>" 
		value="<?php echo esc_attr( $to_time ); ?>" 
		type="time" 
		class="<?php echo esc_attr( $to_name ); ?>"
	>
	<?php if ( $description ) : ?>
		<br><small><?php echo esc_html( $description ); ?></small>
	<?php endif; ?>
</div> 