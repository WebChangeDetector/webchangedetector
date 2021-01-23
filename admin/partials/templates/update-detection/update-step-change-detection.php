<form method="post">
    <input type="hidden" name="wcd_action" value="update_detection_step">
    <input type="hidden" name="step" value="settings">
    <input class="button button-primary" type="submit" value="Start new Update Change Detection">
</form>

<?php
$change_detections = $wcd->get_compares($group_id);
$wcd->compare_view($change_detections, true);
?>
