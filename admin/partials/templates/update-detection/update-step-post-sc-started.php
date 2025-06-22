<?php
/**
 * Manual checks - post sc started
 *
 *   @package    webchangedetector
 */

/**
 * Include manual check tiles
 */
require 'update-step-tiles.php';
?>

<?php require 'update-step-processing-sc.php'; ?>

<!-- Pre-Update started / finished -->
<div id="wcd-screenshots-done" class="wcd-step-container wcd-section" style="display: <?php echo $sc_processing ? 'none' : 'block'; ?>;">
	<div class="wcd-highlight-bg done">
		<h2><?php $utils_handler->get_device_icon( 'check', 'screenshots-done-icon' ); ?>Change detections</h2>
	</div>
	<div class="wcd-highlight-bg">

		<h2>Finished</h2>
		<p>Your change detections are ready. See what changed and fix things if they need to be fixed.</p>
		<form method="post">
			<input type="hidden" name="wcd_action" value="update_detection_step">
			<?php wp_nonce_field( 'update_detection_step' ); ?>
			<input type="hidden" name="step" value="change-detection">
			<input class="button button-primary" type="submit" value="Check Change Detections >">
		</form>
	</div>

</div>