<?php
/**
 * Css area
 *
 *   @package    webchangedetector
 */

?>
<p class="default-bg">
	CSS injection
	<small>Hide or modify elements via CSS before taking screenshots (e.g. dynamic content).</small>
</p>
<p class="code-tags default-bg">&lt;style&gt;</p>
<textarea name="css" class="codearea" style="height:300px; width: 100%;"
><?php echo esc_textarea( $group_and_urls['css'] ) ?? ''; ?></textarea>
<p class="code-tags default-bg">&lt;/style&gt;</p>
