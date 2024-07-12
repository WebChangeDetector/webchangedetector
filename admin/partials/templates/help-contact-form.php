	<h3>
		<span class="dashicons dashicons-arrow-right-alt2"></span>Help us to get better
	</h3>
		<p>
			Did you find a bug, need help, or have feedback on improvements? Let us know!
		</p>

		<form id="ajax-help-contact-form" onsubmit="return false;">
			<input type="hidden" name="email" value="<?php echo wp_get_current_user()->user_email; ?>">
			<input type="hidden" name="action" value="send_feedback_mail">
			<textarea style="width:100%; height: 200px;" name="message" placeholder="Your feedback"></textarea>
			<input type="submit" class="et_pb_button" value="Send">
		</form>
