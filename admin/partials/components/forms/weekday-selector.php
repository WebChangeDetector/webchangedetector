<?php
/**
 * Weekday Selector Component
 *
 * Reusable component for selecting weekdays with checkboxes.
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
 * @var array  $selected_days    Array of selected weekdays
 * @var string $name_prefix      Prefix for form field names (e.g., 'auto_update_checks_')
 * @var string $label            Label text
 * @var string $description      Description text
 * @var string $css_class        Optional CSS classes
 * @var bool   $show_validation  Whether to show validation error message
 */

$selected_days = $selected_days ?? array();
$name_prefix = $name_prefix ?? 'weekday_';
$label = $label ?? 'On days';
$description = $description ?? 'Select the days for the operation.';
$css_class = $css_class ?? '';
$show_validation = $show_validation ?? true;

$weekdays = array(
	'monday'    => 'Monday',
	'tuesday'   => 'Tuesday',
	'wednesday' => 'Wednesday',
	'thursday'  => 'Thursday',
	'friday'    => 'Friday',
	'saturday'  => 'Saturday',
	'sunday'    => 'Sunday',
);
?>

<div class="setting-row <?php echo esc_attr( $css_class ); ?>">
	<label for="<?php echo esc_attr( $name_prefix ); ?>weekdays" style="vertical-align:top;">
		<?php echo esc_html( $label ); ?>
	</label>
	<div id="<?php echo esc_attr( $name_prefix ); ?>weekday_container" style="display: inline-block">
		<?php foreach ( $weekdays as $day_key => $day_name ) : ?>
			<?php
			$field_name = $name_prefix . $day_key;
			$is_checked = isset( $selected_days[ $day_key ] ) && $selected_days[ $day_key ];
			?>
			<input name="<?php echo esc_attr( $field_name ); ?>" value="0" type="hidden">
			<input 
				name="<?php echo esc_attr( $field_name ); ?>" 
				value="1" 
				type="checkbox" 
				<?php checked( $is_checked ); ?>
				class="<?php echo esc_attr( $field_name ); ?>"
				id="<?php echo esc_attr( $field_name ); ?>"
			>
			<label for="<?php echo esc_attr( $field_name ); ?>" style="min-width: inherit">
				<?php echo esc_html( $day_name ); ?>
			</label><br>
		<?php endforeach; ?>
	</div>
	<?php if ( $description ) : ?>
		<br><small><?php echo esc_html( $description ); ?></small>
	<?php endif; ?>
	
	<?php if ( $show_validation ) : ?>
		<span class="notice notice-error" id="error-on-days-validation" style="display: none;">
			<span style="padding: 10px; display: block;" class="default-bg">
				At least one weekday has to be selected.
			</span>
		</span>
	<?php endif; ?>
</div>

<?php if ( $show_validation ) : ?>
<script type="text/javascript">
document.addEventListener('DOMContentLoaded', function() {
	// Weekday validation for <?php echo esc_js( $name_prefix ); ?>
	function validateWeekdays() {
		var checkboxes = document.querySelectorAll('input[name*="<?php echo esc_js( $name_prefix ); ?>"]:checked');
		var errorElement = document.getElementById('error-on-days-validation');
		
		if (checkboxes.length === 0) {
			if (errorElement) errorElement.style.display = 'block';
			return false;
		} else {
			if (errorElement) errorElement.style.display = 'none';
			return true;
		}
	}
	
	// Add event listeners to all weekday checkboxes
	var weekdayCheckboxes = document.querySelectorAll('input[name*="<?php echo esc_js( $name_prefix ); ?>"][type="checkbox"]');
	weekdayCheckboxes.forEach(function(checkbox) {
		checkbox.addEventListener('change', validateWeekdays);
	});
});
</script>
<?php endif; ?> 