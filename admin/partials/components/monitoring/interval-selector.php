<?php
/**
 * Monitoring Interval Selector Component
 *
 * Reusable component for selecting monitoring intervals.
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
 * @var float  $current_interval    Current interval value
 * @var bool   $show_minute_intervals Whether to show minute-based intervals
 * @var string $field_name          Form field name
 * @var string $label               Label text
 * @var string $description         Description text
 * @var string $css_class           Optional CSS classes
 */

$current_interval = $current_interval ?? 24;
$show_minute_intervals = $show_minute_intervals ?? false;
$field_name = $field_name ?? 'interval_in_h';
$label = $label ?? 'Interval in hours';
$description = $description ?? 'This is the interval in which the checks are done.';
$css_class = $css_class ?? '';

$intervals = array(
	array(
		'value' => '0.25',
		'label' => 'Every 15 minutes',
		'requires_premium' => true,
	),
	array(
		'value' => '0.5',
		'label' => 'Every 30 minutes',
		'requires_premium' => true,
	),
	array(
		'value' => '1',
		'label' => 'Every 1 hour',
		'requires_premium' => false,
	),
	array(
		'value' => '3',
		'label' => 'Every 3 hours',
		'requires_premium' => false,
	),
	array(
		'value' => '6',
		'label' => 'Every 6 hours',
		'requires_premium' => false,
	),
	array(
		'value' => '12',
		'label' => 'Every 12 hours',
		'requires_premium' => false,
	),
	array(
		'value' => '24',
		'label' => 'Every 24 hours',
		'requires_premium' => false,
	),
);
?>

<div class="setting-row toggle <?php echo esc_attr( $css_class ); ?>">
	<label for="<?php echo esc_attr( $field_name ); ?>"><?php echo esc_html( $label ); ?></label>
	<select name="<?php echo esc_attr( $field_name ); ?>" id="<?php echo esc_attr( $field_name ); ?>" class="auto-setting">
		<?php foreach ( $intervals as $interval ) : ?>
			<?php
			$is_disabled = $interval['requires_premium'] && ! $show_minute_intervals;
			$is_selected = ( (string) $current_interval === $interval['value'] );
			?>
			<option 
				value="<?php echo esc_attr( $interval['value'] ); ?>"
				<?php echo $is_disabled ? 'disabled ' : ''; ?>
				<?php selected( $is_selected ); ?>
			>
				<?php echo esc_html( $interval['label'] ); ?>
				<?php if ( $is_disabled ) : ?>
					("Freelancer" plan or higher)
				<?php endif; ?>
			</option>
		<?php endforeach; ?>
	</select>
	<?php if ( $description ) : ?>
		<br><small><?php echo esc_html( $description ); ?></small>
	<?php endif; ?>
</div> 