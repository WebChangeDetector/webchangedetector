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

				// Update step tracking for manual checks.
				$this->update_step_tracking( $sc_type );

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
