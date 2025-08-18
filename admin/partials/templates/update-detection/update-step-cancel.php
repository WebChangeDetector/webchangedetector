<?php

/**
 * Manual checks - cancel
 *
 *   @package    webchangedetector
 */

?>
<div class="wcd-cancel-section">
	<div class="wcd-settings-card wcd-cancel-card">
		<div class="wcd-form-row">
			<div class="wcd-form-label-wrapper">
				<label class="wcd-form-label"><span class="dashicons dashicons-no-alt"></span> Cancel Manual Checks</label>
				<div class="wcd-description">Stop the current manual checks workflow and return to settings.</div>
			</div>
			<div class="wcd-form-control wcd-update-check-button">
				<form id="frm-cancel-update-detection" method="post">
					<input type="hidden" name="wcd_action" value="update_detection_step">
					<?php wp_nonce_field( 'update_detection_step' ); ?>
					<input type="hidden" name="step" value="settings">
					<button class="button wcd-cancel-btn" type="submit">
						<span class="dashicons dashicons-no-alt"></span> Cancel Manual Checks
					</button>
				</form>
			</div>
		</div>
	</div>
</div>