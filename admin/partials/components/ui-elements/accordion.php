<?php
/**
 * Accordion Component
 *
 * Reusable component for collapsible content sections.
 *
 * @package    WebChangeDetector
 * @subpackage WebChangeDetector/admin/partials/components
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Expected variables:
 *
 * @var string $header_text      Header text for the accordion
 * @var string $content          Content HTML (should be escaped by caller)
 * @var bool   $open             Whether accordion starts open
 * @var string $css_class        Optional CSS classes
 * @var string $accordion_id     Unique ID for the accordion
 */

$header_text  = $header_text ?? 'Click to expand';
$content      = $content ?? '';
$open         = $open ?? false;
$css_class    = $css_class ?? '';
$accordion_id = $accordion_id ?? 'accordion-' . wp_rand( 1000, 9999 );
?>

<div class="accordion <?php echo esc_attr( $css_class ); ?> accordion-css-injection" style="margin-bottom: 20px; border: 1px solid #ddd;">
	<div 
		class="accordion-header" 
		style="cursor:pointer; padding:10px;" 
		onclick="toggleAccordion('<?php echo esc_js( $accordion_id ); ?>')"
	>
		<span class="accordion-icon dashicons <?php echo $open ? 'dashicons-arrow-down-alt2' : 'dashicons-arrow-right-alt2'; ?>"></span> 
		<?php echo esc_html( $header_text ); ?>
	</div>
	<div 
		class="accordion-content" 
		id="<?php echo esc_attr( $accordion_id ); ?>-content"
		style="<?php echo $open ? '' : 'display:none;'; ?> border:1px solid #ddd; padding:10px;"
	>
		<?php echo $content; // Content should be escaped by caller ?>.
	</div>
</div>

<script type="text/javascript">
function toggleAccordion(accordionId) {
	var content = document.getElementById(accordionId + '-content');
	var header = content.previousElementSibling;
	var icon = header.querySelector('.accordion-icon');
	
	if (content.style.display === 'none' || content.style.display === '') {
		content.style.display = 'block';
		icon.classList.remove('dashicons-arrow-right-alt2');
		icon.classList.add('dashicons-arrow-down-alt2');
		
		// Initialize CodeMirror for CSS textareas when accordion opens for the first time.
		if (accordionId === 'css-injection-manual' || accordionId === 'css-injection-auto' || accordionId === 'css-injection-monitoring') {
			var cssTextareas = content.querySelectorAll('.wcd-css-textarea');
			cssTextareas.forEach(function(textarea) {
				// Only initialize if not already initialized.
				if (!textarea.nextElementSibling || !textarea.nextElementSibling.classList.contains('CodeMirror')) {
					// Use WordPress CodeMirror initialization if available.
					if (window.wp && window.wp.codeEditor) {
						// Get settings from the localized script.
						var editorSettings = {};
						if (typeof cm_settings !== 'undefined' && cm_settings.codeEditor) {
							editorSettings = cm_settings.codeEditor;
						}
						wp.codeEditor.initialize(textarea, editorSettings);
					}
				}
			});
		}
	} else {
		content.style.display = 'none';
		icon.classList.remove('dashicons-arrow-down-alt2');
		icon.classList.add('dashicons-arrow-right-alt2');
	}
}
</script> 