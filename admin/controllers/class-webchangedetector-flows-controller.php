<?php
/**
 * Flows Controller for WebChangeDetector
 *
 * Handles the Interaction Flows page requests: flow list, read-only flow
 * detail (steps) and flow run results. Flows are recorded with the browser
 * extension and managed in the webapp; the plugin only views and toggles.
 *
 * @package    WebChangeDetector
 * @subpackage WebChangeDetector/admin/controllers
 * @author     Mike Miler <mike@wp-mike.com>
 */

namespace WebChangeDetector;

defined( 'ABSPATH' ) || exit;

/**
 * Flows Controller Class.
 *
 * @since 4.6.0
 */
class WebChangeDetector_Flows_Controller {

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
	 * Handle flows page request.
	 */
	public function handle_request() {
		// Check permissions.
		if ( ! $this->admin->settings_handler->is_allowed( 'flows_view' ) ) {
			return;
		}

		// Flows are per-site: not available in "All Websites" mode.
		if ( ! empty( $this->admin->is_all_sites_mode ) ) {
			?>
			<div class="notice notice-info inline">
				<p><?php esc_html_e( 'Flows are managed per site. Please select a specific site to view its flows.', 'webchangedetector' ); ?></p>
			</div>
			<?php
			return;
		}

		// Website UUID guard: without it a flows call would list ALL account flows.
		$website_id = get_option( WCD_WP_OPTION_KEY_WEBSITE_ID );
		if ( empty( $website_id ) ) {
			?>
			<div class="notice notice-info inline">
				<p><?php esc_html_e( 'This website is not registered with WebChange Detector yet. Please finish the setup on the Dashboard first.', 'webchangedetector' ); ?></p>
			</div>
			<?php
			return;
		}

		// Read navigation GET params.
		// phpcs:disable WordPress.Security.NonceVerification.Recommended -- GET parameters for read-only navigation.
		$flow_id = isset( $_GET['wcd_flow_id'] ) ? sanitize_text_field( wp_unslash( $_GET['wcd_flow_id'] ) ) : '';
		$run_id  = isset( $_GET['wcd_run_id'] ) ? sanitize_text_field( wp_unslash( $_GET['wcd_run_id'] ) ) : '';
		$paged   = isset( $_GET['paged'] ) ? max( 1, absint( $_GET['paged'] ) ) : 1;
		// phpcs:enable WordPress.Security.NonceVerification.Recommended

		// Plan entitlement from the cached account (no 403 string matching).
		$account           = $this->admin->account_handler->get_account();
		$has_flows_feature = $this->admin->can_access_feature( 'interaction_flows', is_array( $account ) ? $account : null );

		// Upgrade link only for admins who may manage the (network) account.
		$upgrade_url = '';
		if (
			! $has_flows_feature
			&& $this->admin->settings_handler->is_allowed( 'upgrade_account' )
			&& WebChangeDetector_Multisite::can_manage_account()
		) {
			$upgrade_url = $this->admin->account_handler->get_upgrade_url();
		}

		if ( ! empty( $run_id ) ) {
			$this->render_run_detail( $run_id, $flow_id );
			return;
		}

		if ( ! empty( $flow_id ) ) {
			$this->render_flow_detail( $flow_id, $has_flows_feature, $upgrade_url );
			return;
		}

		$this->render_flows_list( $website_id, $paged, $has_flows_feature, $upgrade_url );
	}

	/**
	 * Render the flows list.
	 *
	 * @param string $website_id        The website UUID.
	 * @param int    $paged             Current page number.
	 * @param bool   $has_flows_feature Whether the plan includes interaction flows.
	 * @param string $upgrade_url       Upgrade URL for the upsell notice (empty to hide the link).
	 */
	private function render_flows_list( $website_id, $paged, $has_flows_feature, $upgrade_url ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter -- Used inside the included template.
		$flows_response = WebChangeDetector_API_V2::get_flows_v2( $website_id, 50, $paged );

		if ( ! is_array( $flows_response ) || ! isset( $flows_response['data'] ) ) {
			$this->render_api_error( $flows_response );
			return;
		}

		$flows      = is_array( $flows_response['data'] ) ? $flows_response['data'] : array();
		$flows_meta = isset( $flows_response['meta'] ) && is_array( $flows_response['meta'] ) ? $flows_response['meta'] : array();

		include WCD_PLUGIN_DIR . 'admin/partials/templates/flows.php';
	}

	/**
	 * Render the read-only flow detail (steps + recent runs).
	 *
	 * @param string $flow_id           The flow UUID.
	 * @param bool   $has_flows_feature Whether the plan includes interaction flows.
	 * @param string $upgrade_url       Upgrade URL for the upsell notice (empty to hide the link).
	 */
	private function render_flow_detail( $flow_id, $has_flows_feature, $upgrade_url ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter -- Used inside the included template.
		$flow_response = WebChangeDetector_API_V2::get_flow_v2( $flow_id );

		if ( ! is_array( $flow_response ) || empty( $flow_response['data']['id'] ) ) {
			$this->render_api_error( $flow_response );
			return;
		}

		$flow = $flow_response['data'];

		$runs_response = WebChangeDetector_API_V2::get_flow_runs_v2( $flow_id, 20 );
		$flow_runs     = is_array( $runs_response ) && isset( $runs_response['data'] ) && is_array( $runs_response['data'] )
			? $runs_response['data']
			: array();

		include WCD_PLUGIN_DIR . 'admin/partials/templates/flow-detail.php';
	}

	/**
	 * Render the flow run detail (per-step results with checkpoints).
	 *
	 * @param string $run_id  The flow run UUID.
	 * @param string $flow_id The flow UUID (for the back link).
	 */
	private function render_run_detail( $run_id, $flow_id ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter -- Used inside the included template.
		$run_response = WebChangeDetector_API_V2::get_flow_run_v2( $run_id );

		if ( ! is_array( $run_response ) || empty( $run_response['data']['id'] ) ) {
			$this->render_api_error( $run_response );
			return;
		}

		$flow_run            = $run_response['data'];
		$can_view_detections = (bool) $this->admin->settings_handler->is_allowed( 'change_detections_view' );

		include WCD_PLUGIN_DIR . 'admin/partials/templates/flow-run-detail.php';
	}

	/**
	 * Render an API error notice.
	 *
	 * @param mixed $response The raw API response.
	 */
	private function render_api_error( $response ) {
		$message = __( 'Sorry, we could not load your flows. Please try again or contact support if this issue persists.', 'webchangedetector' );
		if ( is_array( $response ) && ! empty( $response['message'] ) && is_string( $response['message'] ) ) {
			$message = $response['message'];
		}
		?>
		<div class="notice notice-error inline">
			<p><?php echo esc_html( $message ); ?></p>
		</div>
		<?php
	}
}
