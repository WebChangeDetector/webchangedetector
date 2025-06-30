<?php
if ( empty( $group['id'] ) ) {
	$group = array(
		'id'         => 0,
		'enabled'    => 1,
		'monitoring' => 1,
		'cms'        => null,
	);
}
?>

<div id="group_wp_settings_popup<?php echo $group['id']; ?>" class="ajax-popup" style="display: none;">
	<div class="popup">
		<div class="popup-inner">
			<h2>Group Settings</h2>
			<form id="form_popup_wp_group_settings<?php echo $group['id']; ?>" method="post"  class="ajax" onsubmit="return false">
				<?php
				$wp_comp->get_monitoring_settings( $group, false, $group['monitoring'] );
				$selected_post_types = get_user_meta( get_current_user_id(), USER_META_WP_GROUP_POST_TYPES, true );
				?>
				<input type="hidden" name="action" value="save_group_settings">
				<input type="hidden" name="group_id" value="<?php echo $group['id']; ?>">
				<input type="hidden" name="cms" value="<?php echo $group['cms']; ?>">
				<input  class="et_pb_button" type="submit" value="Save" >
				<button class="et_pb_button " onclick="return closeGroupSettingsPopup('<?php echo $group['id']; ?>')">Cancel</button>
				<span style="float: right; margin-top:10px">Delete website at <a href="?tab=website-settings" class="" >Website settings</a></span>
				<button class="et_pb_button delete_button delete_group_button"
						data-group_id="<?php echo $group['id']; ?>"
						data-cms="<?php echo $group['cms']; ?>"
						value="Delete Group">Delete Group
				</button>
			</form>
		</div>
	</div>
</div>


<div id="group_wp_settings_popup<?php echo $group['id']; ?>" class="ajax-popup" style="display: none;">
	<div class="popup">
		<div class="popup-inner">

			<h2>WP Group Settings</h2>

			<form id="form_popup_wp_group_settings<?php echo $group['id']; ?>" method="post"  class="ajax" onsubmit="return false">
				<?php $wp_comp->get_monitoring_settings( $group, false, $group['monitoring'], 'wordpress' ); ?>

				<input type="hidden" name="action" value="save_group_settings">
				<input type="hidden" name="group_id" value="<?php echo $group['id']; ?>">
				<input  class="et_pb_button" type="submit" value="Save" >
				<button class="et_pb_button " onclick="return closeWpGroupSettingsPopup('<?php echo $group['id']; ?>')">Cancel</button>
				<button class="et_pb_button delete_group_button delete_button"
						data-group_id="<?php echo $group['id']; ?>"
						data-cms="<?php echo $group['cms']; ?>"
						value="Delete Group">Delete Group
				</button>
			</form>
		</div>
	</div>
</div>