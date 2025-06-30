<!-- Steps -->
<div class="update-status-container">
	<div class="stepper-wrapper">
		<div class="stepper-item <?php echo $progress_setting; ?>">
			<div class="step-counter">1</div>
			<div class="step-name">Settings</div>
		</div>
		<div class="stepper-item <?php echo $progress_pre; ?>">
			<div class="step-counter">2</div>
			<div class="step-name">Pre-Update</div>
		</div>
		<!-- <div class="update-status <?php echo $progress_make_update; ?>">3. Updates</div> -->
		<div class="stepper-item <?php echo $progress_post; ?>">
			<div class="step-counter">3</div>
			<div class="step-name">Post-Update</div>
		</div>
		<div class="stepper-item <?php echo $progress_change_detection; ?>">
			<div class="step-counter">4</div>
			<div class="step-name">Change Detection</div>
		</div>
	</div>
</div>
<div class="clear"></div>

<script>
	jQuery(document).ready(function() {

		jQuery('html, body').animate({
			scrollTop: jQuery('body')
		}, 'slow');
		jQuery(this).scrollTop();
	})
</script>