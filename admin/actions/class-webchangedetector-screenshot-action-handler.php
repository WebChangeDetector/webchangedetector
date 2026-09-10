<?php
/**
 * Screenshot Action Handler for WebChangeDetector
 *
 * Handles all screenshot-related actions and business logic.
 *
 * @package    WebChangeDetector
 * @subpackage WebChangeDetector/admin/actions
 * @author     Mike Miler <mike@wp-mike.com>
 */

namespace WebChangeDetector;

/**
 * Screenshot Action Handler Class.
 */
class WebChangeDetector_Screenshot_Action_Handler {

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
	 * Handle take screenshots action.
	 *
	 * @param array $data The action data.
	 * @return array Result with success status and message.
	 */
	public function handle_take_screenshots( $data ) {
		try {
			// Validate screenshot type.
			$sc_type = sanitize_text_field( $data['sc_type'] ?? '' );

			if ( ! in_array( $sc_type, WebChangeDetector_Admin::VALID_SC_TYPES, true ) ) {
				return array(
					'success' => false,
					'message' => 'Invalid screenshot type.',
				);
			}

			// Determine group UUID based on context.
			$group_uuid = $this->get_group_uuid_for_screenshot_type( $sc_type, $data );

			if ( ! $group_uuid ) {
				return array(
					'success' => false,
					'message' => 'Group UUID not found.',
				);
			}

			// Take screenshots via API.
			$results = \WebChangeDetector\WebChangeDetector_API_V2::take_screenshot_v2( $group_uuid, $sc_type );

			if ( isset( $results['batch'] ) ) {
				// Store batch ID for tracking.
				update_option( 'wcd_manual_checks_batch', $results['batch'] );

				// Store batch ID by screenshot type for phase-aware tracking.
				$batch_type_key = ( 'pre' === $sc_type ) ? 'wcd_manual_checks_pre_batch' : 'wcd_manual_checks_post_batch';
				update_option( $batch_type_key, $results['batch'] );

				// Store workflow status and start time.
				update_option( 'wcd_manual_checks_status', $sc_type );
				update_option( 'wcd_manual_checks_started_at', time() );

				// Update step tracking for on-demand checks.
				$this->update_step_tracking( $sc_type );

				// Snapshot resp. report the installed versions of this on-demand check.
				$this->handle_update_results( $sc_type, $results['batch'] );

				return array(
					'success'  => true,
					'message'  => 'Screenshots initiated successfully.',
					'batch_id' => $results['batch'],
				);
			} else {
				return array(
					'success' => false,
					'message' => $results['message'] ?? 'Failed to initiate screenshots.',
				);
			}
		} catch ( \Exception $e ) {
			return array(
				'success' => false,
				'message' => 'Error taking screenshots: ' . $e->getMessage(),
			);
		}
	}

	/**
	 * Get appropriate group UUID for screenshot type.
	 *
	 * @param string $sc_type The screenshot type.
	 * @param array  $data    Additional data that might contain group info.
	 * @return string|false The group UUID or false if not found.
	 */
	private function get_group_uuid_for_screenshot_type( $sc_type, $data ) {
		// Check if specific group is provided in data.
		if ( ! empty( $data['group_id'] ) ) {
			return sanitize_text_field( $data['group_id'] );
		}

		// Default logic based on screenshot type.
		switch ( $sc_type ) {
			case 'auto':
				return $this->admin->monitoring_group_uuid;

			case 'pre':
			case 'post':
			case 'compare':
			default:
				return $this->admin->manual_group_uuid;
		}
	}

	/**
	 * Capture the installed versions of an On-Demand Check and report what changed.
	 *
	 * The pre batch snapshots the installed core, plugin and theme versions; the post batch
	 * diffs that snapshot against the versions on disk and sends the result to the API, which
	 * stores it with the batch (On-Demand Check batches never trigger a mail). Fire once: this
	 * runs in an admin request, there is no cron to retry in, so a failed send is only logged.
	 *
	 * @param string $sc_type  The screenshot type. Only 'pre' and 'post' are relevant.
	 * @param string $batch_id The batch uuid returned by the API.
	 * @return void
	 */
	private function handle_update_results( $sc_type, $batch_id ) {
		if ( 'pre' === $sc_type ) {
			update_option( 'wcd_manual_checks_versions', WebChangeDetector_Update_Results::capture_current_versions(), false );
			return;
		}

		if ( 'post' !== $sc_type ) {
			return;
		}

		// The snapshot is kept on purpose: "Fixed something? Check again" sends the user back
		// to the post-update step and takes another post batch against the SAME pre batch, so
		// every repeat has to diff against the same snapshot. It is cleared when a new run
		// starts (handle_start_manual_checks()), overwritten by the next pre batch, and
		// removed on uninstall.
		$pre_versions = get_option( 'wcd_manual_checks_versions' );

		if ( empty( $pre_versions ) || ! is_array( $pre_versions ) ) {
			\WebChangeDetector\WebChangeDetector_Admin_Utils::log_error( 'No pre-check version snapshot found. Skipping the update results for batch ' . $batch_id . '.', 'handle_update_results', 'debug' );
			return;
		}

		$updates = WebChangeDetector_Update_Results::diff_captured_versions(
			$pre_versions,
			WebChangeDetector_Update_Results::capture_current_versions()
		);

		WebChangeDetector_API_V2::update_batch_update_results_v2(
			$batch_id,
			WebChangeDetector_Update_Results::build_api_payload(
				WebChangeDetector_Update_Results::build_entry_from_diff( $updates )
			)
		);
	}

	/**
	 * Update step tracking based on screenshot type.
	 *
	 * @param string $sc_type The screenshot type.
	 */
	private function update_step_tracking( $sc_type ) {
		switch ( $sc_type ) {
			case 'pre':
				update_option( WCD_OPTION_UPDATE_STEP_KEY, WCD_OPTION_UPDATE_STEP_PRE_STARTED );
				break;

			case 'post':
				update_option( WCD_OPTION_UPDATE_STEP_KEY, WCD_OPTION_UPDATE_STEP_POST_STARTED );
				break;
		}
	}
}
