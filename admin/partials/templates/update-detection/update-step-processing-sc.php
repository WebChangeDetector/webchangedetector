<?php
/**
 * Manual checks - processing-sc
 *
 *   @package    webchangedetector
 */

?>
<div id="wcd-currently-in-progress"
	class="wcd-highlight-bg wcd-step-container "
	style="display: <?php echo esc_html( $sc_processing['meta']['total'] ) ? 'block' : 'none'; ?>">

	<div id="currently-processing-container" class="wcd-section" >
		<div id="currently-processing" style="font-size: 50px; line-height: 50px; font-weight: 700;"><?php echo esc_html( $sc_processing['meta']['total'] ); ?></div>
		<p><strong>Screenshot(s) in progress.</strong></p>
		<p>
			<img src="<?php echo esc_url( $wcd->get_wcd_plugin_url() . 'admin/img/loading-bar.gif' ); ?>" style="height: 15px;">
		</p>
		<p>You can leave this page and return later. <br>The screenshots are taken in the background.</p>
	</div>

	<div class="wcd-step-container wcd-section">

		<p><strong>Currently processing:</strong></p>
		<table id="currently-processing-table" style="text-align: left; border: none; margin 0 auto; width: 100%;">
			<tbody>
			<tr id="processing_sc_row_empty" ><td style="text-align: center;">Loading processing screenshots... </td></tr>
			</tbody>
		</table>
	</div>

</div>

