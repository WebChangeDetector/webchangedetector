<?php require 'update-step-tiles.php'; ?>

<!-- Pre Update -->
<div class="wcd-step-container">
	<div class="wcd-highlight-bg done">
		<h2><?php echo get_device_icon( 'check-circle', 'update-step-done-icon' ); ?>
			Selected:
			<?php echo $sc_processing; 	?>
			URLs
		</h2>
	</div>

	<?php
	$insufficient_screenshots = false;
	if ( $amount_selected_urls * 2 > $client_account['checks_left'] ) {
		$insufficient_screenshots = true;
	}

	$disabled = $insufficient_screenshots ? 'disabled' : '';
	?>
	<div class="wcd-highlight-bg">
		<h2>Pre-Update Screenshots</h2>
		<p>Take screenshots before making updates or other changes on your website.</p>

		<form id="form-take-pre-sc" class="ajax" action="" method="post" onsubmit="return false">
			<input type="hidden" name="action" value="take_screenshots">
			<input type="hidden" name="sc_type" value="pre">
			<input type="hidden" name="step" value="<?php echo WCD_OPTION_UPDATE_STEP_PRE_STARTED; ?>" >
			<input type="hidden" name="cms" value="<?php echo get_user_meta( get_current_user_id(), WCD_OPTION_UPDATE_CMS_FILTER, true ); ?>">
			<input type="hidden" name="group_ids" value="<?php echo get_user_meta( get_current_user_id(), WCD_OPTION_UPDATE_GROUP_IDS, true ); ?>"> <!-- value filled by ajax -->
			<input type="submit" value="Take pre-update screenshots" class="et_pb_button">
		</form>
	</div>
	<?php require 'update-step-cancel.php'; ?>
</div>