<?php
/**
 * Help - headline
 *
 *   @package    webchangedetector
 */

?>
<h2>
	<?php
	$wcd = new WebChangeDetector_Admin();
	$wcd->get_device_icon( 'help', 'white bigger' );
	?>
	Help
</h2>
<form method="post" action="">
	<input type="hidden" name="wcd_action" value="enable_wizard">
	<?php wp_nonce_field( 'enable_wizard' ); ?>
	<input type="submit" class="button" value="Start Wizard">
</form>