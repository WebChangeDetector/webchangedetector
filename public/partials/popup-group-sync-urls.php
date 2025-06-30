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

<div id="group_wp_sync_urls_popup<?php echo $group['id']; ?>" class="ajax-popup" style="display: none;">
	<div class="popup">
		<div class="popup-inner">
			<h2>Synchronize WP webpages</h2>
			<button class="et_pb_button close-popup-button"  onclick="return closeSyncWpGroupUrlsPopup('<?php echo $group['id']; ?>')">X<small>ESC</small></button>

			<div id="selected-url-types<?php echo $group['id']; ?>"  style="position: relative; margin-bottom: 30px;">
				<?php
				// Get selected url_types
				$selected_url_types = $wp_comp->get_selected_wp_url_types( $group['id'] );

				// Set defaults if no settings for selected url_types are saved
				if ( empty( $selected_url_types ) ) {
					$selected_url_types = array(
						array(
							'url_type_slug'  => 'types',
							'url_type_name'  => 'Post Types',
							'post_type_slug' => 'posts',
							'post_type_name' => 'Posts',
						),
						array(
							'url_type_slug'  => 'types',
							'url_type_name'  => 'Post Types',
							'post_type_slug' => 'pages',
							'post_type_name' => 'Pages',
						),
					);
				}

				// Prepare pre-selected url_types for sending to jquery for pre-selecting settings
				$pre_select = array();
				foreach ( $selected_url_types as $selected_url_type ) {
					$pre_select[] = $selected_url_type['post_type_slug'];
				}
				?>

				<!-- Button get url_types from WP API -->
				<form method="post" class="ajax-reload-url-types form-row" style="display: inline;"
						data-group_id="<?php echo $group['id']; ?>"
						data-selected_url_types='<?php echo json_encode( $pre_select ); ?>'>
					<input type="hidden" name="action" value="get_wp_post_types">
					<input type="hidden" name="domain" value="<?php echo $wp_comp->get_domain_by_group_id( $group['id'] ); ?>">
					<input type="hidden" name="group_id" value="<?php echo $group['id']; ?>">
					<input class="et_pb_button" type="submit" value="Change URL Types">
				</form>

				<!-- Currently set url_types for sync -->
				<form class="ajax form-row ">
					<input type="hidden" name="action" value="save_wp_group_settings">
					<input type="hidden" name="group_id" value="<?php echo $group['id']; ?>">
					<input type="hidden" name="delete_missing_urls" value="true">
					<div class="form-container">
						<div class="form-row">
							<div id="ajax-wp-selected-group-settings<?php echo $group['id']; ?>">
								<h4>Selected URL Types</h4>
								<?php
								$current_url_type = false;
								$skip_post_types  = array(
									'wpml_language',
								);
								foreach ( $selected_url_types as $post_type ) {
									if ( in_array( $post_type['url_type_slug'], $skip_post_types ) ) {
										continue;
									}
									if ( ! $current_url_type || $current_url_type !== $post_type['url_type_name'] ) {
										echo '<hr><label>' . $post_type['url_type_name'] . '</label>';
										$current_url_type = $post_type['url_type_name'];
									}
									echo '<input type="hidden" name="wp_api_' . $post_type['url_type_slug'] . '_' . $post_type['post_type_slug'] . '" value=\'' . json_encode( $post_type, JSON_UNESCAPED_UNICODE ) . '\'>';
									echo '- ' . $post_type['post_type_name'] . '<br>';
								}
								?>
							</div>
						</div>
					</div>
					<input type="submit" class="et_pb_button" value="Sync URLs">
				</form>
			</div>

			<!-- Available url_types after wp api call -->
			<form id="form_popup_wp_group_settings<?php echo $group['id']; ?>" method="post"  class="ajax" style="display: none" onsubmit="return false">
				<div  class="form-container">
					<div id="ajax-wp-group-settings<?php echo $group['id']; ?>" class="form-row bg">
					<?php // Filled via ajax ?>
					</div>
				</div>

				<div style="display: none;">
					<?php $wp_comp->get_monitoring_settings( $group, false, $group['monitoring'], 'wordpress' ); ?>
				</div>

				<input type="hidden" name="group_id" value="<?php echo $group['id']; ?>">
				<input type="hidden" name="delete_missing_urls" value="true">
				<input type="hidden" name="action" value="save_wp_group_settings">
				<button id="save-group-and-sync-url-btn<?php echo $group['id']; ?>"
						class="et_pb_button"
						type="submit"
						>
					Save & Sync URLs
				</button>
			</form>
		</div>
	</div>
</div>