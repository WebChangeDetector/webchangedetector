<!-- Group CSS Popup -->
<div id="show_group_css_popup-<?php echo $group['id']; ?>" class="ajax-popup" style="display: none;">
	<div class="popup">
		<div class="popup-inner">
			<h2>Edit CSS For Group <br><?php echo $group['name']; ?></h2>
			<button onclick="return closeGroupCssPopup('<?php echo $group['id']; ?>')" class="et_pb_button close-popup-button">X<small>ESC</small></button>

			<div class="code-tags">&lt;style&gt;</div>
			<form class="ajax ajax-edit-css" method="post" onsubmit="return false">
				<input type="hidden" name="action" value="save_group_css">
				<input type="hidden" name="group_id" value="<?php echo $group['id']; ?>">
				<textarea class="codearea css"
							style="width: 100%; height: 250px;"
							name="css" ><?php echo $group['css']; ?></textarea>
				<div class="code-tags">&lt;/style&gt;</div>
				<br>
				<input type="submit" class="et_pb_button" value="Save">
			</form>
		</div>
	</div>
</div>