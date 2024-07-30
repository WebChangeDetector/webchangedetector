<?php
/**
 * Manual checks - step settings
 *
 *   @package    webchangedetector
 */

/**
 * Include manual check tiles
 */

?>

<!-- Settings -->
<div class="wcd-update-step settings" <?php echo 'settings' !== $step ? 'style="display: none"' : ''; ?>>
	<?php $wcd->get_url_settings( $group_and_urls ); ?>
</div>
