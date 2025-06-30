<div id="take_screenshots_popup" class="ajax-popup" style="display: none;">
	<div class="popup">
		<div class="popup-inner">

			<h2 id="headline-take-pre-sc">Create Reference Screenshots</h2>
			<h2 id="headline-take-post-sc">Run Checks</h2>

			<!-- groups and urls filled by ajax -->
			<div id="sc-groups-and-urls">
				<table class="toggle" id="popupTakeScreenshotsTable">
					<tr>
						<th>Group</th>
						<th style="text-align-last: right;">Selected Checks</th>
					</tr>
				</table>
			</div>

			<!-- Pre sc's -->
			<form id="form-take-pre-sc" class="ajax" action="" method="post" style="float: left;" onsubmit="return false">
				<input type="hidden" name="action" value="take_screenshots">
				<input type="hidden" name="sc_type" value="pre">
				<input type="hidden" name="step" value="<?php echo WCD_OPTION_UPDATE_STEP_PRE_STARTED; ?>">
				<input type="hidden" name="cms" value="<?php echo $filters['cms']; ?>">
				<input id="pre-sc-group-ids" type="hidden" name="group_ids" value=""> <!-- value filled by ajax -->
				<input type="submit" value="Create reference screenshots" class="et_pb_button">
			</form>

			<!-- Post sc's -->
			<form id="form-take-post-sc" action="" method="post" style="float: left;" class="ajax" onsubmit="return false">
				<input type="hidden" name="action" value="take_screenshots">
				<input id="post-sc-cms" type="hidden" name="sc_type" value="post">
				<input type="hidden" name="cms" value="<?php echo $cms == 0 ? null : $cms; ?>">
				<input id="post-sc-group-ids" type="hidden" name="group_ids" value=""> <!-- value filled by ajax -->
				<input  type="submit" value="Create change detections" class="et_pb_button">
			</form>
			<button class="et_pb_button" onclick="return closeTakeScreenshotsPopup()">Cancel</button>
			<div class="clear"></div>
			<div id="post_sc_note">
				<em>If you've not taken a reference screenshot first, one will be taken before the change detection is created.</em>
			</div>
		</div>
	</div>
</div>