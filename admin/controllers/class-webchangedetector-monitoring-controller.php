<?php
/**
 * Monitoring Controller for WebChangeDetector
 *
 * Handles monitoring/auto-settings page requests and logic.
 *
 * @package    WebChangeDetector
 * @subpackage WebChangeDetector/admin/controllers
 * @author     Mike Miler <mike@wp-mike.com>
 */

namespace WebChangeDetector;

/**
 * Monitoring Controller Class.
 */
class WebChangeDetector_Monitoring_Controller {

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
	 * Handle monitoring request.
	 */
	public function handle_request() {
		// Check permissions.
		if ( ! $this->admin->settings_handler->is_allowed( 'monitoring_checks_view' ) ) {
			return;
		}

		$this->render_monitoring_page();
	}

	/**
	 * Render monitoring page.
	 */
	private function render_monitoring_page() {
		// Wizard functionality temporarily removed for phase 1
		// Will be moved to view renderer in later phases

		?>
		<div class="action-container">
			<?php
			$this->admin->settings_handler->get_url_settings( true );
			?>
		</div>
		<div class="clear"></div>
		<?php
	}
} 