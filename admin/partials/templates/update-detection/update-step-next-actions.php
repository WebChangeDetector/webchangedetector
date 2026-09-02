<?php
/**
 * On-Demand Checks Wizard: Final-Step Next Actions Component
 *
 * Two-card layout with an "OR" separator offering the next actions after a
 * check run: start a new check (back to settings) or check again (back to
 * the Create Checks step). Rendered by the change-detection template
 * (update-step-change-detection.php) and the post-screenshot processing
 * template (update-step-post-sc-processing.php).
 *
 * @package    WebChangeDetector
 * @subpackage WebChangeDetector/admin/partials/templates
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Expected variables:
 *
 * @var bool $actions_disabled Whether the action buttons start disabled (processing view);
 *                             JS enables them and lifts the dimming when the batch is done.
 */

$actions_disabled = $actions_disabled ?? false;
?>
<div id="change-detection-actions" class="wcd-next-actions<?php echo $actions_disabled ? ' wcd-disabled' : ''; ?>">
	<div class="wcd-settings-card wcd-next-step-card wcd-next-step-card-primary">
		<h3><span class="dashicons dashicons-plus-alt"></span> <?php esc_html_e( 'All good? Start a new check', 'webchangedetector' ); ?></h3>
		<p><?php esc_html_e( 'Back to the settings to prepare your next update with new pre-update screenshots. This run stays in your history.', 'webchangedetector' ); ?></p>
		<form method="post">
			<input type="hidden" name="wcd_action" value="update_detection_step">
			<?php wp_nonce_field( 'update_detection_step' ); ?>
			<?php \WebChangeDetector\WebChangeDetector_Multisite::render_blog_context_field(); ?>
			<input type="hidden" name="step" value="settings">
			<input class="button button-primary" type="submit" value="<?php echo esc_attr__( 'Start a new check', 'webchangedetector' ); ?>" <?php disabled( $actions_disabled ); ?>>
		</form>
	</div>
	<div class="wcd-or-separator"><?php esc_html_e( 'OR', 'webchangedetector' ); ?></div>
	<div class="wcd-settings-card wcd-next-step-card">
		<h3><span class="dashicons dashicons-update"></span> <?php esc_html_e( 'Fixed something? Check again', 'webchangedetector' ); ?></h3>
		<p><?php esc_html_e( 'Goes back to the Create Checks step: take new post-update screenshots and compare them with the same pre-update screenshots.', 'webchangedetector' ); ?></p>
		<form method="post">
			<input type="hidden" name="wcd_action" value="update_detection_step">
			<?php wp_nonce_field( 'update_detection_step' ); ?>
			<?php \WebChangeDetector\WebChangeDetector_Multisite::render_blog_context_field(); ?>
			<input type="hidden" name="step" value="post-update">
			<input class="button" type="submit" value="<?php echo esc_attr__( 'Check again', 'webchangedetector' ); ?>" <?php disabled( $actions_disabled ); ?>>
		</form>
	</div>
</div>
