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

<div id="group_settings_popup<?php echo $group_id; ?>" class="ajax-popup" style="display: none;">
	<div class="popup">
		<div class="popup-inner">

			<h2>Group Settings</h2>
			<button class="et_pb_button close-popup-button" onclick="return closeGroupSettingsPopup('<?php echo $group_id; ?>')">X<small>ESC</small></button>

			<form id="form_popup_group_settings<?php echo $group_id; ?>" method="post"  class="ajax" onsubmit="return false">
				<?php $wp_comp->get_monitoring_settings( $group, false, $group['monitoring'], $group['cms'] ); ?>
                <div class="form-row">
                    <div class="simple-accordion" style="margin-top: 30px;" >
						<div class="simple-accordion-title" onclick="mm_show_more_link('group_settings_popup<?php echo $group_id; ?>');">
							<span class="text-simple-accordion" >
									Advanced settings
							</span>
						</div>
                        <div class="show-more" style="display:none">
                            <h3 style="margin-top: 30px;">CSS Injection</h3>
                            <div class="code-tags">&lt;style&gt;</div>
                            <textarea class="codearea css"
                                        style="width: 100%; height: 250px;"
                                        name="css" ><?php echo $group['css']; ?></textarea>
                            <div class="code-tags">&lt;/style&gt;</div>
                        </div>
                    </div>
                </div>
				<input type="hidden" name="action" value="save_group_settings">
				<input type="hidden" name="group_id" value="<?php echo $group_id; ?>">
				<input type="hidden" name="cms" value="<?php echo $group['cms']; ?>">
				<input class="et_pb_button" type="submit" value="Save">

				<!-- Delete button -->
				<?php if ( ! $group['cms'] ) { // if the group is a cms, we have a website which has to be deleted first. ?>
					<button class="et_pb_button delete_button delete_group_button"
							data-group_id="<?php echo $group_id; ?>"
							data-cms="<?php echo $group['cms']; ?>">
						Delete Group
					</button>

					<?php
				} else { 
					 $website = ($wp_comp->get_website_by_group_id($group['id'])); 
					 
					 if (!empty($website)) {
						?>
							<div class="" style="font-size: 10px; float:right; border: 1px solid #aaa; padding: 10px; margin-top: 10px;">
								Delete website first to <br>delete this group.</div><br>
						<?php
					 } else { ?>
						<button class="et_pb_button delete_button delete_group_button"
						data-group_id="<?php echo $group_id; ?>"
						data-cms="<?php echo $group['cms']; ?>">
					Delete Group
						</button>
						<?php
					 }
					?>
				<?php
                } ?>
			</form>
		</div>
	</div>
</div>

