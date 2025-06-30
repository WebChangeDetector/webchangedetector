<?php
if ( empty( $groups_and_urls ) ) {
	$groups_and_urls = array(
		'id'         => 0,
		'monitoring' => $monitoring,
		'cms'        => null,
		'name'       => '',
	);
}

?>
<div id="group_settings_popup<?php echo $groups_and_urls['id']; ?>" class="ajax-popup" style="display: none;">
	<div class="popup">
		<div class="popup-inner">

			<h2>Add Webpage Group</h2>
			<button class="et_pb_button close-popup-button" onclick="return closeGroupSettingsPopup('<?php echo $groups_and_urls['id']; ?>')">X<small>ESC</small></button>

			<form id="form_popup_group_settings<?php echo $groups_and_urls['id']; ?>" class="popup_group_settings" method="post" onsubmit="return false">
				<?php $wp_comp->get_monitoring_settings( $groups_and_urls, $cancel_button = false, $groups_and_urls['monitoring'] ); ?>

				<input type="hidden" name="action" value="save_group_settings">
				<input type="hidden" name="group_id" value="<?php echo $groups_and_urls['id']; ?>">
				<input type="hidden" name="cms" value="<?php echo $groups_and_urls['cms']; ?>">
				<input  class="et_pb_button" type="submit" value="Save">
			</form>

		</div>
	</div>
</div>