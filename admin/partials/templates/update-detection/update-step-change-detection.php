<?php
/**
 * Manual checks - change detection
 *
 *   @package    webchangedetector
 */

/**
 * Include manual check tiles
 */
require 'update-step-tiles.php';
?>
<form method="post" style="display: inline; margin-right: 10px;">
	<input type="hidden" name="wcd_action" value="update_detection_step">
	<?php wp_nonce_field( 'update_detection_step' ); ?>
	<input type="hidden" name="step" value="settings">
	<input class="button button-primary" type="submit" value="< Back to settings">
</form>
<form method="post" style="display: inline">
	<input type="hidden" name="wcd_action" value="update_detection_step">
	<?php wp_nonce_field( 'update_detection_step' ); ?>
	<input type="hidden" name="step" value="post-update">
	<input class="button" type="submit" value="Create Change Detection again">
</form>

<?php
	$batches = \WebChangeDetector\WebChangeDetector_API_V2::get_batches();
	$batch   = array_slice( $batches['data'], 0, 1 );

	// Pass only batch data to create accordion containers, content will be loaded via AJAX.
	$wcd->compare_view_v2( $batch );
?>
