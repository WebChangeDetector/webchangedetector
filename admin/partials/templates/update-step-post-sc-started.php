<?php include('update-step-processing-sc.php'); ?>

<!-- Pre-Update started / finished -->
<div id="wcd-screenshots-done"
     style="max-width: 500px; margin: 20px auto; text-align: center; display: <?= $sc_processing ? 'none' : 'block'?>;">
    <div class="wcd-highlight-bg">
        <h2><?= $wcd->get_device_icon("check", "screenshots-done-icon") ?>All Change detections are created.</h2>
        <p>Now you can check the <a href="<?= admin_url() . $wcd::TAB_CHANGE_DETECTION ?>">Change Detections</a></p>
    </div>
    <form method="post">
        <input type="hidden" name="wcd_action" value="update_detection_step">
        <input type="hidden" name="step" value="change-detection">
        <input class="button button-primary" type="submit" value="Check Change Detection >">
    </form>
</div>