<?php require 'update-step-tiles.php'; ?>
<form method="post" style="display: inline; margin-right: 10px;" class="ajax">
	<input type="hidden" name="action" value="update_detection_step">
	<input type="hidden" name="step" value="settings">
	<input class="et_pb_button primary" type="submit" value="Back to settings">
</form>
<form method="post" style="display: inline" class="ajax">
	<input type="hidden" name="action" value="update_detection_step">
	<input type="hidden" name="step" value="post-update">
	<input class="et_pb_button" type="submit" value="Re-run checks">
</form>

<?php
// $change_detections = $wp_comp->get_compares_by_ids(['group_id' => get_user_meta(get_current_user_id(), WCD_OPTION_UPDATE_GROUP_IDS)]);

$filter_options                    = $_POST;
$filter_options['group_type']      = 'update';
$filter_options['latest_batch']    = 1;
$filter_options['batch_id'] = get_user_meta(get_current_user_id(),'wcd_manual_checks_batch', true);
//$filter_options['difference_only'] = $_POST['difference_only'] ?? 1;

$wp_comp->get_compares_view( $filter_options, false );
?>
