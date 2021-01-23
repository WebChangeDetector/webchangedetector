<!-- Show processing -->
<div id="wcd-currently-in-progress" class="wcd-highlight-bg wcd-step-container"
     style="max-width: 500px; margin: 20px auto; padding: 20px; text-align: center; display: <?= $sc_processing ? 'block' : 'none'?>">
    <!--<span id="currently-processing-spinner" class="spinner"></span>-->
    <div id="currently-processing-container" >
        <div id="currently-processing" style="font-size: 50px; line-height: 50px; font-weight: 700;"><?= $sc_processing ?></div>
        <p>Screenshot(s) in progress.</p>
        <p>
            <img src="<?= $wcd->get_wcd_plugin_url() . 'admin/img/loading-bar.gif' ?>" style="height: 15px;">
        </p>
        <p>You can leave this page and return later. The screenshots are taken in the background.</p>
    </div>
</div>

