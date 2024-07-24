<?php require 'update-step-tiles.php'; ?>
<form method="post" style="display: inline; margin-right: 10px;">
	<input type="hidden" name="wcd_action" value="update_detection_step">
	<?php wp_nonce_field( 'update_detection_step' ); ?>
	<input type="hidden" name="step" value="settings">
	<input class="button button-primary" type="submit" value="Start new Update Change Detection">
</form>
<form method="post" style="display: inline">
	<input type="hidden" name="wcd_action" value="update_detection_step">
	<?php wp_nonce_field( 'update_detection_step' ); ?>
	<input type="hidden" name="step" value="post-update">
	<input class="button" type="submit" value="Create Change Detection again">
</form>

<?php
$change_detections = $wcd->get_compares( $group_id );
$wcd->compare_view( $change_detections, true );
?>
