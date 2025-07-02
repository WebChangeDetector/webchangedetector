<?php
/**
 * Update Step Tile Component
 *
 * Reusable component for displaying update detection step tiles.
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
 * @var string $step_key         Step identifier (e.g., 'pre-update', 'make-update')
 * @var string $step_title       Display title for the step
 * @var string $step_description Description of what this step does
 * @var bool   $is_current       Whether this is the current active step
 * @var bool   $is_completed     Whether this step is completed
 * @var string $icon_class       CSS class for the step icon
 * @var string $button_text      Text for the action button (if applicable)
 * @var string $button_action    Action for the button (if applicable)
 * @var string $css_class        Optional CSS classes
 */

$step_key = $step_key ?? '';
$step_title = $step_title ?? 'Step';
$step_description = $step_description ?? '';
$is_current = $is_current ?? false;
$is_completed = $is_completed ?? false;
$icon_class = $icon_class ?? 'dashicons-admin-generic';
$button_text = $button_text ?? '';
$button_action = $button_action ?? '';
$css_class = $css_class ?? '';

// Determine tile state class.
$state_class = '';
if ( $is_completed ) {
	$state_class = 'step-completed';
} elseif ( $is_current ) {
	$state_class = 'step-current';
} else {
	$state_class = 'step-pending';
}
?>

<div class="update-step-tile <?php echo esc_attr( $state_class ); ?> <?php echo esc_attr( $css_class ); ?>" 
     data-step="<?php echo esc_attr( $step_key ); ?>">
	<div class="step-icon">
		<span class="dashicons <?php echo esc_attr( $icon_class ); ?>"></span>
		<?php if ( $is_completed ) : ?>
			<span class="step-status-icon dashicons dashicons-yes-alt"></span>
		<?php elseif ( $is_current ) : ?>
			<span class="step-status-icon dashicons dashicons-clock"></span>
		<?php endif; ?>
	</div>
	
	<div class="step-content">
		<h3 class="step-title"><?php echo esc_html( $step_title ); ?></h3>
		<?php if ( $step_description ) : ?>
			<p class="step-description"><?php echo esc_html( $step_description ); ?></p>
		<?php endif; ?>
		
		<?php if ( $button_text && $button_action && $is_current ) : ?>
			<div class="step-action">
				<button 
					type="button" 
					class="button button-primary step-action-button"
					onclick="<?php echo esc_attr( $button_action ); ?>"
				>
					<?php echo esc_html( $button_text ); ?>
				</button>
			</div>
		<?php endif; ?>
	</div>
</div>

<style>

</style> 