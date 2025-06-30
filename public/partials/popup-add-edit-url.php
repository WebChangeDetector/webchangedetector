<?php require wcd_get_plugin_dir() . 'public/partials/popup-add-url-check.php'; ?>

<div id="add_url_popup" class="ajax-popup" style="display: none">
	<div class="popup">
		<div class="popup-inner">
			<h2 id="add-url-headline">New URL</h2>
			<button class="et_pb_button close-popup-button" onclick="return closeAddUrlPopup()">X<small>ESC</small></button>

			<form id="form_add_url" class="ajax" name="website-list" action="" method="post" onsubmit="return false;">
				<input id="save-update-url-action" type="hidden" name="action" value="save_url">
				<input type="hidden" name="cms">
				<input id="url-id" type="hidden" name="url_id" value="0">
				<div class="form-container">
					<div class="form-row">
						<label for="html_title">Title<br>
							<input id="html_title" name="html_title" type="text" placeholder="Title (e.g. Home)" style="width: 100%">
						</label>
					</div>
					<div class="form-row bg">
						<label for="url">Webpage<br>
							<input id="url" name="url" type="text" placeholder="Webpage (e.g. google.com)" style="width: 100%">
						</label>
					</div>
				</div>

				<br>
				<div id="add-url-groups">
					<?php
					if ( isset( $groups_and_urls ) ) {
						?>
					<h4>Activate in groups</h4>
					<table>
						<thead>
						<tr>
							<th><?php echo get_device_icon( 'desktop' ); ?></th>
							<th><?php echo get_device_icon( 'mobile' ); ?></th>
							<th width="100%"> Group Name</th>
						</tr>
						</thead>
						<tbody>
						<?php
						// might not be set in manage-urls
						if ( is_iterable( $groups_and_urls ) ) {
							foreach ( $groups_and_urls as $group_and_url ) {
								if ( $group_and_url['cms'] ) {
									continue;
								}

								echo '
                                    <input type="hidden" name="group_id-' . $group_and_url['id'] . '" value="' . $group_and_url['id'] . '">
                                    <tr>
                                        <td>
                                            <label class="checkbox_container" style="float: left">
                                                <input type="hidden" name="desktop-' . $group_and_url['id'] . '" ' . ' value="0">
                                                <input type="checkbox" name="desktop-' . $group_and_url['id'] . '" ' . ' value="1" checked>
                                                <span class="checkmark"></span>
                                            </label>
                                        </td>
                                        <td>
                                            <label class="checkbox_container" style="float: left">
                                                <input type="hidden" name="mobile-' . $group_and_url['id'] . '" ' . ' value="0">
                                                <input type="checkbox" name="mobile-' . $group_and_url['id'] . '" ' . ' value="1">
                                                <span class="checkmark"></span>
                                            </label>
                                        </td>
                                        <td>
                                            ' . $group_and_url['name'] . '
                                        </td>
                                    </tr>';
							}
						}
						?>
						</tbody>
					</table>
					<?php } ?>
				</div>

				<input id="submit_form_add_url" class="et_pb_button ajax" type="submit" value="Save"  onsubmit="checkUrlAndSubmitForm(this);">
			</form>
		</div>
	</div>
</div>

