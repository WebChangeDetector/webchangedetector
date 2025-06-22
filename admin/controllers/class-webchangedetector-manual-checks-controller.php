<?php
/**
 * Manual Checks Controller for WebChangeDetector
 *
 * Handles manual checks page requests and logic.
 *
 * @package    WebChangeDetector
 * @subpackage WebChangeDetector/admin/controllers
 * @author     Mike Miler <mike@wp-mike.com>
 */

namespace WebChangeDetector;

/**
 * Manual Checks Controller Class.
 */
class WebChangeDetector_Manual_Checks_Controller {

	/**
	 * The admin instance.
	 *
	 * @var WebChangeDetector_Admin
	 */
	private $admin;

	/**
	 * Constructor.
	 *
	 * @param WebChangeDetector_Admin $admin The admin instance.
	 */
	public function __construct( $admin ) {
		$this->admin = $admin;
	}

	/**
	 * Handle manual checks request.
	 */
	public function handle_request() {
		// Check permissions.
		if ( ! $this->admin->settings_handler->is_allowed( 'manual_checks_view' ) ) {
			return;
		}

		$this->render_manual_checks_page();
	}

	/**
	 * Render manual checks page.
	 */
	private function render_manual_checks_page() {
		// Check if we have a step in the db.
		$step = get_option( WCD_OPTION_UPDATE_STEP_KEY );
		if ( ! $step ) {
			$step = WCD_OPTION_UPDATE_STEP_SETTINGS;
		}
		update_option( WCD_OPTION_UPDATE_STEP_KEY, sanitize_text_field( $step ), false );

		?>
		<div class="action-container">
			<?php

			switch ( $step ) {
				case WCD_OPTION_UPDATE_STEP_SETTINGS:
					$progress_setting          = 'active';
					$progress_pre              = 'disabled';
					$progress_make_update      = 'disabled';
					$progress_post             = 'disabled';
					$progress_change_detection = 'disabled';
					$this->admin->settings_handler->get_url_settings( false );
					break;

				case WCD_OPTION_UPDATE_STEP_PRE:
					$progress_setting          = 'done';
					$progress_pre              = 'active';
					$progress_make_update      = 'disabled';
					$progress_post             = 'disabled';
					$progress_change_detection = 'disabled';
					include plugin_dir_path( __FILE__ ) . '../partials/templates/update-detection/update-step-pre-sc.php';
					break;

				case WCD_OPTION_UPDATE_STEP_PRE_STARTED:
					$progress_setting          = 'done';
					$progress_pre              = 'active';
					$progress_make_update      = 'disabled';
					$progress_post             = 'disabled';
					$progress_change_detection = 'disabled';
					$sc_processing             = $this->admin->get_processing_queue_v2(); // used in template.
					include plugin_dir_path( __FILE__ ) . '../partials/templates/update-detection/update-step-pre-sc-started.php';
					break;

				case WCD_OPTION_UPDATE_STEP_POST:
					$progress_setting          = 'done';
					$progress_pre              = 'done';
					$progress_make_update      = 'done';
					$progress_post             = 'active';
					$progress_change_detection = 'disabled';
					include plugin_dir_path( __FILE__ ) . '../partials/templates/update-detection/update-step-post-sc.php';
					break;

				case WCD_OPTION_UPDATE_STEP_POST_STARTED:
					$progress_setting          = 'done';
					$progress_pre              = 'done';
					$progress_make_update      = 'done';
					$progress_post             = 'active';
					$progress_change_detection = 'disabled';
					$sc_processing             = $this->admin->get_processing_queue_v2(); // used in template.
					include plugin_dir_path( __FILE__ ) . '../partials/templates/update-detection/update-step-post-sc-started.php';
					break;

				case WCD_OPTION_UPDATE_STEP_CHANGE_DETECTION:
					$progress_setting          = 'done';
					$progress_pre              = 'done';
					$progress_make_update      = 'done';
					$progress_post             = 'done';
					$progress_change_detection = 'active';
					include plugin_dir_path( __FILE__ ) . '../partials/templates/update-detection/update-step-change-detection.php';
					break;
			}
			?>
		</div>

		<div class="clear"></div>
		<?php
	}
} 