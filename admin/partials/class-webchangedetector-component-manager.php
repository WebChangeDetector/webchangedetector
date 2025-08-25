<?php
/**
 * Component Manager Class
 *
 * Manages loading and rendering of reusable template components.
 *
 * @package    WebChangeDetector
 * @subpackage WebChangeDetector/admin/partials
 */

namespace WebChangeDetector;

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WebChangeDetector Component Manager
 *
 * Handles the loading and rendering of reusable template components.
 */
class WebChangeDetector_Component_Manager {


	/**
	 * Base path for components.
	 *
	 * @var string
	 */
	private $components_path;

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->components_path = WCD_PLUGIN_DIR . 'admin/partials/components';
	}

	/**
	 * Render a component.
	 *
	 * @param string $component_path Relative path to component from components directory.
	 * @param array  $variables      Variables to pass to the component.
	 * @param bool   $output         Whether to return output instead of echoing.
	 * @return string|void
	 */
	public function render_component( $component_path, $variables = array(), $output = false ) {
		$full_path = $this->components_path . '/' . ltrim( $component_path, '/' );

		if ( ! file_exists( $full_path ) ) {
			if ( WP_DEBUG ) {
				\WebChangeDetector\WebChangeDetector_Admin_Utils::log_error( "Component not found: {$full_path}", 'component_manager', 'error' );
			}
			return $output ? '' : null;
		}

		// Extract variables for the component.
		// phpcs:ignore WordPress.PHP.DontExtract.extract_extract -- Variables are sanitized and used only for template rendering.
		extract( $variables );

		if ( $output ) {
			ob_start();
			include $full_path;
			return ob_get_clean();
		} else {
			include $full_path;
		}
	}

	/**
	 * Render threshold setting component.
	 *
	 * @param float  $threshold   Current threshold value.
	 * @param string $label       Optional custom label.
	 * @param string $description Optional custom description.
	 * @param string $css_class   Optional CSS classes.
	 * @param bool   $output      Whether to return output.
	 * @return string|void
	 */
	public function threshold_setting( $threshold = 0.0, $label = '', $description = '', $css_class = '', $output = false ) {
		return $this->render_component(
			'forms/threshold-setting.php',
			compact( 'threshold', 'label', 'description', 'css_class' ),
			$output
		);
	}

	/**
	 * Render time range selector component.
	 *
	 * @param string $from_time    Current "from" time value.
	 * @param string $to_time      Current "to" time value.
	 * @param string $from_name    Form field name for "from" time.
	 * @param string $to_name      Form field name for "to" time.
	 * @param string $label        Label text.
	 * @param string $description  Description text.
	 * @param string $css_class    Optional CSS classes.
	 * @param bool   $output       Whether to return output.
	 * @return string|void
	 */
	public function time_range_selector( $from_time = '', $to_time = '', $from_name = 'time_from', $to_name = 'time_to', $label = 'Time range', $description = '', $css_class = '', $output = false ) {
		return $this->render_component(
			'forms/time-range-selector.php',
			compact( 'from_time', 'to_time', 'from_name', 'to_name', 'label', 'description', 'css_class' ),
			$output
		);
	}

	/**
	 * Render weekday selector component.
	 *
	 * @param array  $selected_days   Array of selected weekdays.
	 * @param string $name_prefix     Prefix for form field names.
	 * @param string $label           Label text.
	 * @param string $description     Description text.
	 * @param string $css_class       Optional CSS classes.
	 * @param bool   $show_validation Whether to show validation.
	 * @param bool   $output          Whether to return output.
	 * @return string|void
	 */
	public function weekday_selector( $selected_days = array(), $name_prefix = 'weekday_', $label = 'On days', $description = '', $css_class = '', $show_validation = true, $output = false ) {
		return $this->render_component(
			'forms/weekday-selector.php',
			compact( 'selected_days', 'name_prefix', 'label', 'description', 'css_class', 'show_validation' ),
			$output
		);
	}

	/**
	 * Render email input component.
	 *
	 * @param string $email_value     Current email value.
	 * @param string $field_name      Form field name.
	 * @param string $label           Label text.
	 * @param string $description     Description text.
	 * @param string $css_class       Optional CSS classes.
	 * @param bool   $multiple        Whether multiple emails are allowed.
	 * @param bool   $show_validation Whether to show validation.
	 * @param string $placeholder     Placeholder text.
	 * @param bool   $output          Whether to return output.
	 * @return string|void
	 */
	public function email_input( $email_value = '', $field_name = 'email', $label = 'Email address', $description = '', $css_class = '', $multiple = false, $show_validation = true, $placeholder = '', $output = false ) {
		return $this->render_component(
			'forms/email-input.php',
			compact( 'email_value', 'field_name', 'label', 'description', 'css_class', 'multiple', 'show_validation', 'placeholder' ),
			$output
		);
	}

	/**
	 * Render monitoring interval selector component.
	 *
	 * @param float  $current_interval       Current interval value.
	 * @param bool   $show_minute_intervals  Whether to show minute-based intervals.
	 * @param string $field_name             Form field name.
	 * @param string $label                  Label text.
	 * @param string $description            Description text.
	 * @param string $css_class              Optional CSS classes.
	 * @param bool   $output                 Whether to return output.
	 * @return string|void
	 */
	public function interval_selector( $current_interval = 24, $show_minute_intervals = false, $field_name = 'interval_in_h', $label = 'Interval in hours', $description = '', $css_class = '', $output = false ) {
		return $this->render_component(
			'monitoring/interval-selector.php',
			compact( 'current_interval', 'show_minute_intervals', 'field_name', 'label', 'description', 'css_class' ),
			$output
		);
	}

	/**
	 * Render hour selector component.
	 *
	 * @param int    $current_hour Current hour value (0-23).
	 * @param string $field_name   Form field name.
	 * @param string $label        Label text.
	 * @param string $description  Description text.
	 * @param string $css_class    Optional CSS classes.
	 * @param bool   $output       Whether to return output.
	 * @return string|void
	 */
	public function hour_selector( $current_hour = 0, $field_name = 'hour_of_day', $label = 'Hour of the day', $description = '', $css_class = '', $output = false ) {
		return $this->render_component(
			'monitoring/hour-selector.php',
			compact( 'current_hour', 'field_name', 'label', 'description', 'css_class' ),
			$output
		);
	}

	/**
	 * Render accordion component.
	 *
	 * @param string $header_text Header text for the accordion.
	 * @param string $content     Content HTML.
	 * @param bool   $open        Whether accordion starts open.
	 * @param string $css_class   Optional CSS classes.
	 * @param string $accordion_id Unique ID for the accordion.
	 * @param bool   $output      Whether to return output.
	 * @return string|void
	 */
	public function accordion( $header_text = 'Click to expand', $content = '', $open = false, $css_class = '', $accordion_id = '', $output = false ) {
		if ( empty( $accordion_id ) ) {
			$accordion_id = 'accordion-' . wp_rand( 1000, 9999 );
		}
		return $this->render_component(
			'ui-elements/accordion.php',
			compact( 'header_text', 'content', 'open', 'css_class', 'accordion_id' ),
			$output
		);
	}

	/**
	 * Render toggle section component.
	 *
	 * @param string $toggle_name        Name for the toggle checkbox.
	 * @param bool   $is_enabled         Whether the toggle is currently enabled.
	 * @param string $toggle_label       Label for the toggle.
	 * @param string $toggle_description Description for the toggle.
	 * @param string $content            Content HTML to show/hide.
	 * @param string $css_class          Optional CSS classes.
	 * @param string $section_id         Unique ID for the toggle section.
	 * @param bool   $output             Whether to return output.
	 * @return string|void
	 */
	public function toggle_section( $toggle_name = 'toggle_enabled', $is_enabled = false, $toggle_label = 'Enable', $toggle_description = '', $content = '', $css_class = '', $section_id = '', $output = false ) {
		if ( empty( $section_id ) ) {
			$section_id = 'toggle-section-' . wp_rand( 1000, 9999 );
		}
		return $this->render_component(
			'ui-elements/toggle-section.php',
			compact( 'toggle_name', 'is_enabled', 'toggle_label', 'toggle_description', 'content', 'css_class', 'section_id' ),
			$output
		);
	}

	/**
	 * Render update step tile component.
	 *
	 * @param string $step_key         Step identifier.
	 * @param string $step_title       Display title for the step.
	 * @param string $step_description Description of what this step does.
	 * @param bool   $is_current       Whether this is the current active step.
	 * @param bool   $is_completed     Whether this step is completed.
	 * @param string $icon_class       CSS class for the step icon.
	 * @param string $button_text      Text for the action button.
	 * @param string $button_action    Action for the button.
	 * @param string $css_class        Optional CSS classes.
	 * @param bool   $output           Whether to return output.
	 * @return string|void
	 */
	public function update_step_tile( $step_key = '', $step_title = 'Step', $step_description = '', $is_current = false, $is_completed = false, $icon_class = 'dashicons-admin-generic', $button_text = '', $button_action = '', $css_class = '', $output = false ) {
		return $this->render_component(
			'workflows/update-step-tile.php',
			compact( 'step_key', 'step_title', 'step_description', 'is_current', 'is_completed', 'icon_class', 'button_text', 'button_action', 'css_class' ),
			$output
		);
	}

	/**
	 * Render auto-update information component.
	 *
	 * @param string $css_class Optional CSS classes.
	 * @param bool   $output    Whether to return output.
	 * @return string|void
	 */
	public function auto_update_info( $css_class = '', $output = false ) {
		return $this->render_component(
			'settings/auto-update-info.php',
			compact( 'css_class' ),
			$output
		);
	}

	/**
	 * Get all available components.
	 *
	 * @return array List of available component paths.
	 */
	public function get_available_components() {
		$components = array();
		if ( ! is_dir( $this->components_path ) ) {
			return $components;
		}

		$iterator = new \RecursiveIteratorIterator(
			new \RecursiveDirectoryIterator( $this->components_path )
		);

		foreach ( $iterator as $file ) {
			if ( $file->isFile() && $file->getExtension() === 'php' ) {
				$relative_path = str_replace( $this->components_path . '/', '', $file->getPathname() );
				$components[]  = $relative_path;
			}
		}

		return $components;
	}
}
