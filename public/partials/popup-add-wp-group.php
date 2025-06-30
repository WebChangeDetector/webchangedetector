<?php
if ( empty( $groups_and_urls ) ) {
	$groups_and_urls = array(
		'id'         => 0,
		'monitoring' => $monitoring,
		'cms'        => 'wordpress',
		'name'       => '',
	);
}
?>

<div id="add_wp_group_popup<?php echo $groups_and_urls['id']; ?>" class="ajax-popup" style="display: none;">
	<div class="popup">
		<div class="popup-inner">

			<h2>Add WordPress Website</h2>
			<button class="et_pb_button close-popup-button" onclick="return closeAddWpGroupPopup('<?php echo $groups_and_urls['id']; ?>')">X<small>ESC</small></button>

			<div id="error_available_post_types" style="display: none;"><!-- filled via ajax --></div>

			<form id="form_popup_wp_group_settings<?php echo $groups_and_urls['id']; ?>" method="post"  class="ajax-get-wp-urltypes form-container form-row" onsubmit="return false">
				<label for="domain">Domain of WordPress website</label>
				<input id="add-wp-website-domain-input" type="text" name="domain" placeholder="e.g. example.com">
				<input type="hidden" name="action" value="get_wp_post_types">
				<input type="hidden" name="group_id" value="<?php echo $groups_and_urls['id']; ?>">
				<input type="hidden" name="cms" value="wordpress">
				<input  class="et_pb_button" type="submit" value="Check WP Pages">
			</form>

			<form id="available_post_types" class="ajax-async" style="display: none; margin-top: 20px;">
				<h4>Select webpage types</h4>
				<div id="available_post_types_list" class="form-row bg form-container" style=" ">
					<!-- filled by ajax -->
				</div>

				<div id="wp-group-settings<?php echo $groups_and_urls['id']; ?>" style=" margin-top: 20px;">
					<?php $wp_comp->get_monitoring_settings( $groups_and_urls, $cancel_button = false, $groups_and_urls['monitoring'], 'wordpress' ); ?>
				</div>
				<input type="hidden" name="action" value="save_wp_group_settings_async">
				<input id="add_wp_website_domain" type="hidden" name="domain" value=""> <!-- filled via ajax -->
				<input type="hidden" name="group_id" value="<?php echo $groups_and_urls['id']; ?>">
				<input type="hidden" name="cms" value="wordpress">

				<input class="et_pb_button" type="submit" value="Add Website & Start Sync">
			</form>
		</div>
	</div>
</div>