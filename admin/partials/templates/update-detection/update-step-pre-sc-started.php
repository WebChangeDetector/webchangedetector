<?php require 'update-step-tiles.php'; ?>

<?php require 'update-step-processing-sc.php'; ?>

<!-- Pre-Update started / finished -->
<div id="wcd-screenshots-done" class="wcd-step-container"
		style="max-width: 500px; margin: 20px auto; text-align: center; display: none;">
	<div class="wcd-highlight-bg done">
		<h2><?php echo $wcd->get_device_icon( 'check', 'screenshots-done-icon' ); ?>Pre-Update Screenshots</h2>
	</div>
	<div class="wcd-highlight-bg">
		<h2>Time For Updates</h2>
		<p>
			You can leave this page and make <a href="<?php echo admin_url(); ?>update-core.php" >Updates</a> or other changes on your website. When your are done, come back and
			continue with the button below. <br>

		</p>
		<form method="post" >
			<input type="hidden" name="wcd_action" value="update_detection_step">
			<input type="hidden" name="step" value="post-update">
			<input class="button button-primary" type="submit" value="Next >">
		</form>
	</div>


	<?php require 'update-step-cancel.php'; ?>
</div>