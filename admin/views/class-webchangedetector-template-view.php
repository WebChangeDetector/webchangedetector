<?php
/**
 * Template View Component for WebChangeDetector
 *
 * Handles rendering of template-based views that were previously included directly.
 *
 * @package    WebChangeDetector
 * @subpackage WebChangeDetector/admin/views
 * @author     Mike Miler <mike@wp-mike.com>
 */

namespace WebChangeDetector;

/**
 * Template View Component Class.
 */
class WebChangeDetector_Template_View {

	/**
	 * The admin instance.
	 *
	 * @var WebChangeDetector_Admin
	 */
	private $admin;

	/**
	 * Account handler for template compatibility.
	 *
	 * @var WebChangeDetector_Admin_Account
	 */
	public $account_handler;

	/**
	 * Constructor.
	 *
	 * @param WebChangeDetector_Admin $admin The admin instance.
	 */
	public function __construct( $admin ) {
		$this->admin = $admin;
		// Set up template compatibility properties.
		$this->account_handler = $admin->account_handler;
	}

	/**
	 * Render the auto settings template.
	 *
	 * @param array  $group_and_urls The group and URLs data.
	 * @param string $group_id The group ID.
	 */
	public function render_auto_settings( $group_and_urls, $group_id ) { // Used in template.
		// Set up template variables that the template expects.
		// The template expects $this->admin and $this->account_handler to be available.

		// Temporarily include the existing template file.
		// This will be the bridge during migration.
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_include_once_file_path
		include WP_PLUGIN_DIR . '/webchangedetector/admin/partials/templates/auto-settings.php';
	}

	/**
	 * Render the update settings template.
	 *
	 * @param array  $group_and_urls The group and URLs data.
	 * @param string $group_id The group ID.
	 */
	public function render_update_settings( $group_and_urls, $group_id ) { // Used in template.
		// Set up template variables that the template expects.
		// The template expects $this->admin and $this->account_handler to be available.

		// Temporarily include the existing template file.
		// This will be the bridge during migration.
		include WP_PLUGIN_DIR . '/webchangedetector/admin/partials/templates/update-settings.php';
	}

	/**
	 * Render the show change detection template.
	 *
	 * @param array $comparison The comparison data.
	 */
	public function render_show_change_detection( $comparison ) {
		// Set up template variables.
		$compare      = $comparison;
		$token        = $comparison['token'] ?? '';
		$public_token = $comparison['token'] ?? '';

		// Temporarily include the existing template file.
		// This will be the bridge during migration.
		include WP_PLUGIN_DIR . '/webchangedetector/admin/partials/templates/show-change-detection.php';
	}


	/**
	 * Get app URL.
	 * Delegates to the account handler.
	 *
	 * @return string The app URL.
	 */
	public function app_url() {
		return $this->admin->account_handler->get_app_url();
	}

	/**
	 * Check if current account is allowed for specific view or action.
	 * Delegates to the settings handler.
	 *
	 * @param string $allowed The allowance string to check.
	 * @return bool|int True if allowed, false if not, or integer value for specific allowances.
	 */
	public function is_allowed( $allowed ) {
		return $this->admin->settings_handler->is_allowed( $allowed );
	}

	/**
	 * Render update detection step templates.
	 *
	 * @param string $step The current step.
	 * @param array  $data Additional data for the template.
	 */
	public function render_update_detection_step( $step, $data = array() ) {
		// Set up variables that templates expect.
		$wcd             = $this->admin;
		$utils_handler   = \WebChangeDetector\WebChangeDetector_Admin_Utils::class;
		$account_details = $data['account_details'] ?? $wcd->account_handler->get_account();

		// Extract progress variables for tiles background.
		$progress_setting          = $data['progress_setting'] ?? '';
		$progress_pre              = $data['progress_pre'] ?? '';
		$progress_make_update      = $data['progress_make_update'] ?? '';
		$progress_post             = $data['progress_post'] ?? '';
		$progress_change_detection = $data['progress_change_detection'] ?? '';

		// Extract other template variables.
		$sc_processing = $data['sc_processing'] ?? null;

		// Map step names to template files.
		$template_map = array(
			'pre'              => 'update-step-pre-sc.php',
			'pre_started'      => 'update-step-pre-sc-started.php',
			'post'             => 'update-step-post-sc.php',
			'post_started'     => 'update-step-post-sc-started.php',
			'change_detection' => 'update-step-change-detection.php',
			'processing'       => 'update-step-processing-sc.php',
			'settings'         => 'update-step-settings.php',
			'cancel'           => 'update-step-cancel.php',
			'tiles'            => 'update-step-tiles.php',
		);

		if ( isset( $template_map[ $step ] ) ) {
			$template_file = WP_PLUGIN_DIR . '/webchangedetector/admin/partials/templates/update-detection/' . $template_map[ $step ];
			if ( file_exists( $template_file ) ) {
				include $template_file;
			} else {
				echo '<p>Template file not found: ' . esc_html( $template_map[ $step ] ) . '</p>';
			}
		} else {
			echo '<p>Unknown step: ' . esc_html( $step ) . '</p>';
		}
	}
}
