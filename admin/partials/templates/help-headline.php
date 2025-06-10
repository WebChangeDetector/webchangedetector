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
<input type="button" class="button" value="Start Wizard" onclick="window.wcdStartWizard()">