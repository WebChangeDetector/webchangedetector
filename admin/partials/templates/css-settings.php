<?php
/**
 * Css area
 *
 *   @package    webchangedetector
 */

?>
<div>
	<p class="setting-row toggle">
		CSS injection
		<small>Hide or modify elements via CSS before taking screenshots (e.g. dynamic content).</small>
	</p>
	<div class="code-tags default-bg">&lt;style&gt;</div>
	<textarea name="css" class="codearea" style="height:300px; width: 100%;"
	><?php echo esc_textarea( $group_and_urls['css'] ) ?? ''; ?></textarea>
	<div class="code-tags default-bg">&lt;/style&gt;</div>
</div>