<?php require 'update-step-tiles.php'; ?>

<!-- Settings -->
<div class="wcd-update-step settings" <?php echo 'settings' !== $step ? 'style="display: none"' : ''; ?>>
	<?php $wcd->get_url_settings( $groups_and_urls ); ?>
</div>