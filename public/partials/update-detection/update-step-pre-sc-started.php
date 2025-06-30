<?php require 'update-step-tiles.php'; ?>
<?php require 'update-step-processing-sc.php'; ?>

<!-- Pre-Update started / finished -->
<div id="wcd-screenshots-done" class="wcd-step-container" style="display: none; width: 100%;">
	<!--<div id="wcd-make-updates" class="wcd-step-container"
		style="max-width: 500px; margin: 20px auto; text-align: center; ">
		<div class="wcd-highlight-bg done">
			<h2><?php echo get_device_icon( 'check-circle', 'update-step-done-icon' ); ?>Pre-Update Screenshots</h2>
		</div>
		<div class="wcd-highlight-bg">
			<h2>Time For Updates</h2>
			<p>
				You can leave this page and make updates or other changes on your website. When your are done, come back and
				continue with the button below.
			</p>-->
			<form id="manual_checks_next_step" method="post" class="ajax">
				<input type="hidden" name="action" value="update_detection_step">
				<input type="hidden" name="step" value="post-update">
				<input class="et_pb_button" type="submit" value="Next >">
			</form>
		<!--</div>-->
		<?php require 'update-step-cancel.php'; ?>
	</div>