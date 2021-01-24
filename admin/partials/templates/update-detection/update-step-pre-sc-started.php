<?php include ('update-step-tiles.php'); ?>

<?php include('update-step-processing-sc.php'); ?>

<!-- Pre-Update started / finished -->
<div id="wcd-screenshots-done" class="wcd-step-container" style="display: <?= $sc_processing ? 'none' : 'block'?>;">
    <div class="wcd-highlight-bg done">
        <h2><?= $wcd->get_device_icon("check", "screenshots-done-icon") ?>Pre-Update Screenshots</h2>
    </div>

    <form method="post" style="margin-bottom: 30px;">
        <input type="hidden" name="wcd_action" value="update_detection_step">
        <input type="hidden" name="step" value="make-update">
        <input class="button button-primary" type="submit" value="Next >">
    </form>
    <?php include('update-step-cancel.php'); ?>

</div>