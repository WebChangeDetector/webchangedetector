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
		<h2><?php esc_html_e( 'Create Change Detections', 'webchangedetector' ); ?></h2>
		<p><?php esc_html_e( 'Done with updates or other changes?', 'webchangedetector' ); ?> <br><?php esc_html_e( 'Create change detections to see what changed.', 'webchangedetector' ); ?></p>
		<div style="width: 300px; margin: 0 auto;">
			<form id="frm-take-post-sc" action="<?php echo esc_url( admin_url() . WCD_TAB_UPDATE ); ?>" method="post">
				<input type="hidden" value="take_screenshots" name="wcd_action">
				<?php wp_nonce_field( 'take_screenshots' ); ?>
				<input type="hidden" name="sc_type" value="post">
				<button type="submit" class="button" style="width: 100%;">
					<span class="button_headline"><?php esc_html_e( 'Create Change Detections', 'webchangedetector' ); ?> </span>
				</button>
			</form>
		</div>
	</div>

	<?php require 'update-step-cancel.php'; ?>

</div>