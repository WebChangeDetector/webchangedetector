<?php require 'update-step-tiles.php'; ?>

<!-- Settings -->
<div class="wcd-update-step settings" <?php echo $step != WCD_OPTION_UPDATE_STEP_SETTINGS ? 'style="display: none"' : ''; ?>>
	<?php $wp_comp->get_action_view( $groups_and_urls, $filters, $updateView = true ); ?>
</div>

