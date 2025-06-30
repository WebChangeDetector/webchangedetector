<?php require 'update-step-tiles.php'; ?>
<?php require 'update-step-processing-sc.php'; ?>

<!-- Pre-Update started / finished -->
<div id="wcd-screenshots-done" class="wcd-step-container" style=" width: 100%; display: none">
	<!--<div class="wcd-highlight-bg done">
		<h2><?php echo get_device_icon( 'check-circle', 'update-step-done-icon' ); ?>Pre-Update Screenshots</h2>
	</div>
	<div class="wcd-highlight-bg done">
		<h2><?php echo get_device_icon( 'check-circle', 'update-step-done-icon' ); ?>Updates and Changes</h2>
	</div>
	<div class="wcd-highlight-bg done">
		<h2><?php echo get_device_icon( 'check-circle', 'update-step-done-icon' ); ?>Post-Update Screenshots & Checks</h2>
	</div>
	-->

	<form id="manual_checks_next_step" method="post" class="ajax" >
		<input type="hidden" name="action" value="update_detection_step">
		<input type="hidden" name="step" value="change-detection">
		<input class="et_pb_button" type="submit" value="Continue >">
	</form>

</div>