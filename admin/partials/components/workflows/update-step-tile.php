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
.update-step-tile {
	display: flex;
	align-items: flex-start;
	padding: 20px;
	margin: 10px 0;
	border: 2px solid #ddd;
	border-radius: 8px;
	background: #fff;
	transition: all 0.3s ease;
}

.update-step-tile.step-current {
	border-color: #0073aa;
	background: #f0f8ff;
}

.update-step-tile.step-completed {
	border-color: #00a32a;
	background: #f0fff0;
}

.update-step-tile.step-pending {
	opacity: 0.7;
}

.step-icon {
	position: relative;
	margin-right: 15px;
	font-size: 24px;
}

.step-icon .dashicons {
	color: #666;
}

.step-current .step-icon .dashicons {
	color: #0073aa;
}

.step-completed .step-icon .dashicons {
	color: #00a32a;
}

.step-status-icon {
	position: absolute;
	top: -5px;
	right: -5px;
	font-size: 16px !important;
	background: white;
	border-radius: 50%;
}

.step-content {
	flex: 1;
}

.step-title {
	margin: 0 0 10px 0;
	font-size: 16px;
	font-weight: 600;
}

.step-description {
	margin: 0 0 15px 0;
	color: #666;
}

.step-action {
	margin-top: 10px;
}
</style> 