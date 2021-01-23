<div id="wcd-screenshots-done"
     style="max-width: 500px; margin: 20px auto; text-align: center;">
    <div class="wcd-highlight-bg">

        <h2><?= $wcd->get_device_icon("check", "screenshots-done-icon") ?>Pre-Update Screenshots Are Taken</h2>
    </div>

    <div class="wcd-highlight-bg">
        <h2><?= $wcd->get_device_icon("check", "screenshots-done-icon") ?>Updates and Changes Are done</h2>
    </div>

    <div class="wcd-highlight-bg">
        <h2>Create Change Detections</h2>
        <p>Take screenshots again and compare them to the Pre-Update screenshots.</p>
        <div style="width: 300px; margin: 0 auto;">
            <form id="frm-take-post-sc" action="<?= admin_url() . $wcd::TAB_SETTINGS?>" method="post" >
                <input type="hidden" value="take_screenshots" name="wcd_action">
                <input type="hidden" name="sc_type" value="post">
                <button type="submit" class="button-primary" style="width: 100%;" >
                    <span class="button_headline">Create Change Detections </span>

                </button>
            </form>
        </div>
    </div>
    <form method="post">
        <input type="hidden" name="wcd_action" value="update_detection_step">
        <input type="hidden" name="step" value="make-update">
        <input class="button" type="submit" value="< Back">
    </form>
    <?php include('update-step-cancel.php'); ?>

</div>

