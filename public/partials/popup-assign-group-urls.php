<?php require wcd_get_plugin_dir() . 'public/partials/popup-add-url-check.php'; ?>

<div id="assign_group_urls_popup" class="ajax-popup" style="display: none;">
	<div class="popup">
		<div class="popup-inner">
			<h2>Add Webpages</h2>
			<button class="et_pb_button close-popup-button" onclick="return closeAssignGroupUrlsPopup()">X<small>ESC</small></button>

			<!-- Disable assign existing urls for now
			<button id="btn-new-url" style=" float: left;" class="et_pb_button" onclick="showNewUrl();">Add new webpage</button>
			<button id="btn-assign-url" style=" float: left;" class="et_pb_button" onclick="showAssignUrl();">Add existing webpage</button>
			<div class="clear"></div>
			-->
			<div id="new-url">
				<form id="form_add_url" data-group_id="" class="ajax" name="website-list" action="" method="post" onsubmit="return false;">
					<input type="hidden" name="action" value="save_url">
					<input type="hidden" name="cms">
					<input id="add_url_group_id" type="hidden" name="group_id">
					<input type="hidden" name="url_id" value="0">

					<label id="add-urls-textarea" for="url">
						<p><strong>Add your webpages </strong><br>(multiple webpages: one per line)</p>
						<textarea style="width: 100%" rows="6" id="url" name="url"  placeholder="e.g. google.com" ></textarea>
					</label>

					<div id="check_urls_container" style="word-break: break-word; display: none; border: 1px solid #aaa; background: #E8EDF1; padding: 10px; margin-top: 10px;">
						<strong>Checking webpages:</strong>
						<div id="check_urls"></div>
					</div>
					<textarea id="invalid_urls_textarea" style="display: none;"></textarea>

					<div id="invalid_urls_container" style="word-break: break-word; display: none; border: 1px solid #aaa; background: rgba(220, 50, 50, 0.1); padding: 10px; margin-top: 10px;">
						<strong>Webpages will not be added on saving:</strong>
						<div id="invalid_urls"></div>
						<div id="edit_failed_urls" style="display: none">
							<button class="et_pb_button secondary" style="margin-bottom: 0;" onclick="return editFailedUrls(this)">Edit failed webpages</button>
						</div>
					</div>

					<div id="valid_urls_container" style="word-break: break-word; display: none; border: 1px solid #aaa; background: rgba(23, 179, 49, 0.1); padding: 10px; margin-top: 10px;">
						<strong>Webpages will be added on saving:</strong>
						<div id="valid_urls"></div>
					</div>
					<textarea id="valid_urls_textarea" name="valid_url"  style="display: none;"></textarea>

					<script>
						function editFailedUrls(e) {
							e.preventDefault;
							jQuery("#edit_failed_urls").hide();
							jQuery("#invalid_urls").html("");
							jQuery("#invalid_urls_container").hide();
							jQuery("#add-urls-textarea textarea").val(jQuery("#invalid_urls_textarea").val());
							jQuery("#invalid_urls_textarea").val("");
							jQuery("#add-urls-textarea").show();
							jQuery("#check_urls").html("");
							return false;
						}
					</script>

					<br>

					<div id="add-urls-select-screensizes">
						<div class="enabled_switch devices">
							<p><strong>Select the screen sizes to check.</strong></p>
							<label class="switch" style="display: inline-block; margin-top: 0;">
								<input id="add_url_desktop_hidden" type="hidden" name="desktop-" value="0">
								<input id="add_url_desktop" type="checkbox" name="desktop-"  value="1" checked>
								<span class="slider round">Desktop</span>
							</label>
							<br>
							<label class="switch" style="display: inline-block; margin-top: 0;">
								<input id="add_url_mobile_hidden" type="hidden" name="mobile-"  value="0">
								<input id="add_url_mobile" type="checkbox" name="mobile-"  value="1">
								<span class="slider round">Mobile</span>
							</label>

						<!--<label class="checkbox_container" style="float: left">
							<input id="add_url_desktop_hidden" type="hidden" name="desktop-" value="0">
							<input id="add_url_desktop" type="checkbox" name="desktop-"  value="1" checked>
							<span class="checkmark"></span>

						</label>
						<label class="checkbox_container" style="float: left">

							<input id="add_url_mobile_hidden" type="hidden" name="mobile-"  value="0">
							<input id="add_url_mobile" type="checkbox" name="mobile-"  value="1">
							<span class="checkmark"></span>
							<div style="display: inline-block; vertical-align: top;"><?php echo get_device_icon( 'mobile' ); ?> Mobile</div>
						</label>
						-->
						</div>

						<div class="clear"></div>

						<input id="submit_form_add_url" class="et_pb_button ajax" type="submit" value="Check Webpage(s) & Save" onclick="checkUrlAndSubmitForm(event);">

					</div>
				</form>
			</div>

			<div id="assign-url">
				<form id="form_popup_assign_group_urls" method="post" class="ajax" onsubmit="return false">
					<input type="hidden" name="action" value="assign_group_urls">
					<input id="assign_url_group_id" type="hidden" name="group_id" value="">
					<input type="hidden" name="cms" value="">

					<!-- Div where the urls show up via js trigger click add urls button -->
					<div id="unassigned-group-urls">
						<!-- filled via ajax -->
					</div>
					<input type="submit" class="et_pb_button" value="Save">

				</form>
			</div>
		</div>
	</div>
</div>

<script>
		function showNewUrl() {
		jQuery("#new-url").show();
		jQuery("#assign-url").hide();
		jQuery("#btn-new-url").css("background", "rgba(12,113,195,0.1)");
		jQuery("#btn-assign-url").css("background","initial");
	}
		function showAssignUrl() {
		jQuery("#new-url").hide();
		jQuery("#assign-url").show();
		jQuery("#btn-new-url").css("background", "initial");
		jQuery("#btn-assign-url").css("background","rgba(12,113,195,0.1)");
	}

	showNewUrl();
</script>