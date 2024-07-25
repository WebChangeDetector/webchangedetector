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
	echo wp_kses( $wcd->get_device_icon( 'help', 'white bigger' ), array( 'span' => array( 'class' => array() ) ) );
	?>
	Help
</h2>