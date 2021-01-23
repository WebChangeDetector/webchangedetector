
<!-- Pre-Update started / finished -->
<div id="wcd-screenshots-done"
     style="max-width: 500px; margin: 20px auto; text-align: center; display: <?= $sc_processing ? 'none' : 'block'?>;">
    <div class="wcd-highlight-bg">
        <h2>Change Detections</h2>
    </div>
    <form method="post">
        <input type="hidden" name="wcd_action" value="update_detection_step">
        <input type="hidden" name="step" value="change-detections">
        <input class="button button-primary" type="submit" value="Start new Update Change Detection">
    </form>
</div>