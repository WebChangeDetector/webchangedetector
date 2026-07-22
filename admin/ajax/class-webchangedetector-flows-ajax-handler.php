<?php
/**
 * Flows AJAX handler.
 *
 * Handles Interaction Flow AJAX operations: toggling a flow's lifecycle
 * flags (enabled_manual / enabled_monitoring) and fetching a flow run
 * for the run-detail polling.
 *
 * @link       https://www.webchangedetector.com
 * @since      4.6.0
 * @package    WebChangeDetector
 * @subpackage WebChangeDetector/admin/ajax
 */

namespace WebChangeDetector;

defined( 'ABSPATH' ) || exit;

/**
 * Flows AJAX handler.
 *
 * @since      4.6.0
 * @package    WebChangeDetector
 * @subpackage WebChangeDetector/admin/ajax
 */
class WebChangeDetector_Flows_Ajax_Handler extends WebChangeDetector_Ajax_Handler_Base {

	/**
	 * Register AJAX hooks for flows.
	 *
	 * @since    4.6.0
	 */
	public function register_hooks() {
		add_action( 'wp_ajax_wcd_toggle_flow', array( $this, 'ajax_toggle_flow' ) );
		add_action( 'wp_ajax_wcd_get_flow_run', array( $this, 'ajax_get_flow_run' ) );
	}

	/**
	 * Toggle a flow lifecycle flag via AJAX.
	 *
	 * Expects: flow_id (uuid), lifecycle (manual|monitoring), enabled (0|1).
	 *
	 * @since    4.6.0
	 */
	public function ajax_toggle_flow() {
		if ( ! $this->security_check() ) {
			return;
		}

		$post_data = $this->validate_post_data( array( 'flow_id', 'lifecycle', 'enabled' ) );

		if ( false === $post_data ) {
			$this->send_error_response( __( 'Flow ID, lifecycle and enabled state are required.', 'webchangedetector' ), 'toggle_flow' );
			return;
		}

		$lifecycle = $post_data['lifecycle'];
		if ( ! in_array( $lifecycle, array( 'manual', 'monitoring' ), true ) ) {
			$this->send_error_response( __( 'Invalid lifecycle.', 'webchangedetector' ), 'toggle_flow' );
			return;
		}

		// Plan-gate re-check (defense in depth against DOM tampering).
		if ( ! $this->admin->can_access_feature( 'interaction_flows' ) ) {
			$this->send_error_response( __( 'Interaction Flows are not included in your current plan. Please upgrade to enable flows.', 'webchangedetector' ), 'toggle_flow', 403 );
			return;
		}

		$field   = 'manual' === $lifecycle ? 'enabled_manual' : 'enabled_monitoring';
		$enabled = filter_var( $post_data['enabled'], FILTER_VALIDATE_BOOLEAN ) ? 1 : 0;

		$result = WebChangeDetector_API_V2::update_flow_v2( $post_data['flow_id'], array( $field => $enabled ) );

		// Detect success structurally: FlowResource always returns { data: { id, ... } }.
		if ( is_array( $result ) && isset( $result['data']['id'] ) ) {
			$this->send_success_response(
				array(
					'id'                 => $result['data']['id'],
					'enabled_manual'     => ! empty( $result['data']['enabled_manual'] ),
					'enabled_monitoring' => ! empty( $result['data']['enabled_monitoring'] ),
				)
			);
			return;
		}

		$message = is_array( $result ) && ! empty( $result['message'] ) && is_string( $result['message'] )
			? $result['message']
			: __( 'Failed to update the flow. Please try again.', 'webchangedetector' );
		$this->send_error_response( $message, 'toggle_flow' );
	}

	/**
	 * Get a flow run with per-step results via AJAX (polling endpoint).
	 *
	 * Expects: flow_run_id (uuid).
	 *
	 * @since    4.6.0
	 */
	public function ajax_get_flow_run() {
		if ( ! $this->security_check() ) {
			return;
		}

		$post_data = $this->validate_post_data( array( 'flow_run_id' ) );

		if ( false === $post_data ) {
			$this->send_error_response( __( 'Flow run ID is required.', 'webchangedetector' ), 'get_flow_run' );
			return;
		}

		$result = WebChangeDetector_API_V2::get_flow_run_v2( $post_data['flow_run_id'] );

		if ( is_array( $result ) && isset( $result['data']['id'] ) ) {
			$this->send_success_response( $result['data'] );
			return;
		}

		$message = is_array( $result ) && ! empty( $result['message'] ) && is_string( $result['message'] )
			? $result['message']
			: __( 'Failed to load the flow run.', 'webchangedetector' );
		$this->send_error_response( $message, 'get_flow_run' );
	}
}
