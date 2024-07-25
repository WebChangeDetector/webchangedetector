<?php
/**
 * Manual checks - processing-sc
 */

?>
<div id="wcd-currently-in-progress"
	class="wcd-highlight-bg wcd-step-container"
	style=" display: <?php echo $sc_processing ? 'block' : 'none'; ?>">

	<div id="currently-processing-container" >
		<div id="currently-processing" style="font-size: 50px; line-height: 50px; font-weight: 700;"><?php echo $sc_processing; ?></div>
		<p><strong>Screenshot(s) in progress.</strong></p>
		<p>
			<img src="<?php echo $wcd->get_wcd_plugin_url() . 'admin/img/loading-bar.gif'; ?>" style="height: 15px;">
		</p>
		<p>You can leave this page and return later. <br>The screenshots are taken in the background.</p>
	</div>
</div>

