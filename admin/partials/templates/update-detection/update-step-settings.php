<?php require 'update-step-tiles.php'; ?>

<!-- Settings -->
<div class="wcd-update-step settings" <?php echo $step != 'settings' ? 'style="display: none"' : ''; ?>>
	<?php $wcd->get_url_settings( $groups_and_urls ); ?>
</div>