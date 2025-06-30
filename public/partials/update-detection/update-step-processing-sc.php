<!-- Show processing -->

<div id="wcd-currently-in-progress"
	class="wcd-highlight-bg wcd-step-container"
    data-batch_id="<?php echo  get_user_meta(get_current_user_id(), 'wcd_manual_checks_batch', true) ?>"
     >

	<div id="currently-processing-container" >
		<div id="update-currently-processing" style="font-size: 50px; line-height: 50px; font-weight: 700; min-height: 60px;">
			<span style="font-size: 14px;">Loading...</span>
		</div>
		<p><strong>Screenshots in progress</strong></p>
		<p id="currently-processing-loader">
			<img src="<?php echo '/wp-content/plugins/app/public/img/loader.gif'; ?>" >
		</p>
		<p>You can leave this page and return later. <br>The screenshots are taken in the background.</p>
	</div>
</div>

