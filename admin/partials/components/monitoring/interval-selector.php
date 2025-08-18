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
 *
 * @var float  $current_interval    Current interval value
 * @var bool   $show_minute_intervals Whether to show minute-based intervals
 * @var string $field_name          Form field name
 * @var string $label               Label text
 * @var string $description         Description text
 * @var string $css_class           Optional CSS classes
 */

$current_interval      = $current_interval ?? 24;
$show_minute_intervals = $show_minute_intervals ?? false;
$field_name            = $field_name ?? 'interval_in_h';
$label                 = $label ?? __( 'Interval in hours', 'webchangedetector' );
$description           = $description ?? __( 'This is the interval in which the checks are done.', 'webchangedetector' );
$css_class             = $css_class ?? '';

$intervals = array(
	array(
		'value'            => '0.25',
		'label'            => __( 'Every 15 minutes', 'webchangedetector' ),
		'requires_premium' => true,
	),
	array(
		'value'            => '0.5',
		'label'            => __( 'Every 30 minutes', 'webchangedetector' ),
		'requires_premium' => true,
	),
	array(
		'value'            => '1',
		'label'            => __( 'Every 1 hour', 'webchangedetector' ),
		'requires_premium' => false,
	),
	array(
		'value'            => '3',
		'label'            => __( 'Every 3 hours', 'webchangedetector' ),
		'requires_premium' => false,
	),
	array(
		'value'            => '6',
		'label'            => __( 'Every 6 hours', 'webchangedetector' ),
		'requires_premium' => false,
	),
	array(
		'value'            => '12',
		'label'            => __( 'Every 12 hours', 'webchangedetector' ),
		'requires_premium' => false,
	),
	array(
		'value'            => '24',
		'label'            => __( 'Every 24 hours', 'webchangedetector' ),
		'requires_premium' => false,
	),
);
?>

<div class="setting-row <?php echo esc_attr( $css_class ); ?>">
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
					<?php _e( '("Freelancer" plan or higher)', 'webchangedetector' ); ?>
				<?php endif; ?>
			</option>
		<?php endforeach; ?>
	</select>
	<?php if ( $description ) : ?>
		<br><small><?php echo esc_html( $description ); ?></small>
	<?php endif; ?>
</div> 