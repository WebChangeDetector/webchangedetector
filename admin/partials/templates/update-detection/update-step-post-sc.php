<?php
/**
 * Manual checks - step tiles
 *
 *   @package    webchangedetector
 */

/**
 * Include manual check tiles
 */
require 'update-step-tiles.php';
?>

<div class="wcd-step-container wcd-section">
	<div class="wcd-card">
		<h2>Create Change Detections</h2>
		<p>Done with updates or other changes? <br>Create change detections to see what changed.</p>
		<div style="width: 300px; margin: 0 auto;">
			<form id="frm-take-post-sc" action="<?php echo esc_url( admin_url() . WCD_TAB_UPDATE ); ?>" method="post">
				<input type="hidden" value="take_screenshots" name="wcd_action">
				<?php wp_nonce_field( 'take_screenshots' ); ?>
				<input type="hidden" name="sc_type" value="post">
				<button type="submit" class="button" style="width: 100%;">
					<span class="button_headline">Create Change Detections </span>
				</button>
			</form>
		</div>
	</div>

	<?php require 'update-step-cancel.php'; ?>

</div>