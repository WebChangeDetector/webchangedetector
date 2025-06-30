<?php require 'update-step-tiles.php'; ?>

<div class="wcd-step-container">
	<div class="wcd-highlight-bg done">
		<h2><?php echo get_device_icon( 'check-circle', 'update-step-done-icon' ); ?>Pre-Update Screenshots</h2>
	</div>
	<div class="wcd-highlight-bg done" style="display: none;">
		<h2><?php echo get_device_icon( 'check-circle', 'update-step-done-icon' ); ?>Updates and Changes</h2>
	</div>

	<div class="wcd-highlight-bg">
		<h2>Time for Updates</h2>
		<p>You can leave this page and make updates or other changes on your website. When you are done, come back and
            continue with creating the change detections.</p>
			<!--
			<form id="frm-take-post-sc"  method="post" >
				<input type="hidden" value="take_screenshots" name="action">
				<input type="hidden" name="sc_type" value="post">
				<button type="submit" class="et_pb_button" style="width: 100%;" >
					<span class="button_headline">Create Change Detections </span>
				</button>
			</form>
			-->
			<form id="form-take-post-sc" method="post" class="ajax" onsubmit="return false">
				<input type="hidden" name="action" value="take_screenshots">
				<input type="hidden" name="step" value="<?php echo WCD_OPTION_UPDATE_STEP_POST_STARTED; ?>">
				<input id="post-sc-cms" type="hidden" name="sc_type" value="post">
				<input type="hidden" name="cms" value="<?php echo get_user_meta( get_current_user_id(), WCD_OPTION_UPDATE_CMS_FILTER, true ); ?>">
				<input id="post-sc-group-ids" type="hidden" name="group_ids" value="<?php echo get_user_meta( get_current_user_id(), WCD_OPTION_UPDATE_GROUP_IDS, true ); ?>"> <!-- value filled by ajax -->
				<input type="submit" value="Create change detections" class="et_pb_button">
			</form>

	</div>

	<?php require 'update-step-cancel.php'; ?>

</div>

