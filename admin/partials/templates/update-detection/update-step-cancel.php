<?php
/**
 * Manual checks - cancel
 */

?>
<div style="margin-top: 50px;">
	<form id="frm-cancel-update-detection" method="post" style="margin-top: 50px;">
		<input type="hidden" name="wcd_action" value="update_detection_step">
		<?php wp_nonce_field( 'update_detection_step' ); ?>
		<input type="hidden" name="step" value="settings">
		<input class="button button-delete" type="submit" value="Cancel Manual Checks">
	</form>
</div>
