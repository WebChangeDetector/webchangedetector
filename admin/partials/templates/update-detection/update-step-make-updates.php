<?php include ('update-step-tiles.php'); ?>

<!-- Pre-Update started / finished -->
<div id="wcd-make-updates" class="wcd-step-container"
     style="max-width: 500px; margin: 20px auto; text-align: center; ">
    <div class="wcd-highlight-bg done">
        <h2><?= $wcd->get_device_icon("check", "screenshots-done-icon") ?>Pre-Update Screenshots</h2>
    </div>
    <div class="wcd-highlight-bg">
        <h2>Time For Updates</h2>
        <p>
            You can leave this page and make updates or other changes on your website. When your are done, come back and
            continue with the button below. <br>
            <a href="<?= admin_url() ?>update-core.php" class="button button-secondary">Updates</a>
        </p>
    </div>

    <form method="post" style="margin-bottom: 30px;">
        <input type="hidden" name="wcd_action" value="update_detection_step">
        <input type="hidden" name="step" value="post-update">
        <input class="button button-primary" type="submit" value="Next >">
    </form>
    <?php include('update-step-cancel.php'); ?>
</div>