<?php include('update-step-processing-sc.php'); ?>

<!-- Pre-Update started / finished -->
<div id="wcd-screenshots-done"
     style="max-width: 500px; margin: 20px auto; text-align: center; display: <?= $sc_processing ? 'none' : 'block'?>;">
    <div class="wcd-highlight-bg">

        <h2><?= $wcd->get_device_icon("check", "screenshots-done-icon") ?>Pre-Update Screenshots Are Taken</h2>
        <p>You can check the screenshots in the <a href="<?= admin_url() . $wcd::TAB_LOGS ?>">Logs</a></p>

    </div>

    <form method="post" style="margin-bottom: 30px;">
        <input type="hidden" name="wcd_action" value="update_detection_step">
        <input type="hidden" name="step" value="make-update">
        <input class="button button-primary" type="submit" value="Next >">
    </form>
    <?php include('update-step-cancel.php'); ?>

</div>