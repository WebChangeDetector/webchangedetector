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
					'success' => true,
					'message' => 'Screenshots initiated successfully.',
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
	 * Handle screenshot comparison request.
	 *
	 * @param array $data The comparison data.
	 * @return array Result with comparison data.
	 */
	public function handle_screenshot_comparison( $data ) {
		try {
			$batch_id = sanitize_text_field( $data['batch_id'] ?? '' );
			
			if ( empty( $batch_id ) ) {
				return array(
					'success' => false,
					'message' => 'Batch ID is required for comparison.',
				);
			}

			// Get comparison results from API.
			$comparisons = \WebChangeDetector\WebChangeDetector_API_V2::get_comparisons_v2( array(
				'batch_id' => $batch_id,
			) );
			
			if ( empty( $comparisons['data'] ) ) {
				return array(
					'success' => false,
					'message' => 'No comparisons found for this batch.',
				);
			}

			return array(
				'success' => true,
				'message' => 'Comparisons retrieved successfully.',
				'comparisons' => $comparisons['data'],
				'meta' => $comparisons['meta'] ?? array(),
			);
		} catch ( \Exception $e ) {
			return array(
				'success' => false,
				'message' => 'Error retrieving comparisons: ' . $e->getMessage(),
			);
		}
	}

	/**
	 * Handle screenshot queue status check.
	 *
	 * @param array $data The queue check data.
	 * @return array Result with queue status.
	 */
	public function handle_queue_status_check( $data ) {
		try {
			$batch_id = sanitize_text_field( $data['batch_id'] ?? '' );
			$per_page = intval( $data['per_page'] ?? 30 );
			
			// Get processing queue status.
			$queue_data = $this->admin->get_processing_queue_v2( $batch_id, $per_page );
			
			if ( empty( $queue_data['data'] ) ) {
				return array(
					'success' => true,
					'message' => 'No items in queue.',
					'queue' => array(),
					'status' => 'empty',
				);
			}

			// Analyze queue status.
			$status_counts = array_count_values( array_column( $queue_data['data'], 'status' ) );
			$total_items = count( $queue_data['data'] );
			$completed_items = $status_counts['done'] ?? 0;
			$processing_items = $status_counts['processing'] ?? 0;
			$open_items = $status_counts['open'] ?? 0;

			$overall_status = 'processing';
			if ( $completed_items === $total_items ) {
				$overall_status = 'completed';
			} elseif ( $open_items === $total_items ) {
				$overall_status = 'pending';
			}

			return array(
				'success' => true,
				'message' => sprintf( 
					'Queue status: %d completed, %d processing, %d pending of %d total.',
					$completed_items,
					$processing_items,
					$open_items,
					$total_items
				),
				'queue' => $queue_data['data'],
				'meta' => $queue_data['meta'] ?? array(),
				'status' => $overall_status,
				'stats' => array(
					'total' => $total_items,
					'completed' => $completed_items,
					'processing' => $processing_items,
					'pending' => $open_items,
				),
			);
		} catch ( \Exception $e ) {
			return array(
				'success' => false,
				'message' => 'Error checking queue status: ' . $e->getMessage(),
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

	/**
	 * Validate screenshot action data.
	 *
	 * @param array  $data   The data to validate.
	 * @param string $action The action being performed.
	 * @return array Validation result with success status and errors.
	 */
	public function validate_action_data( $data, $action ) {
		$errors = array();

		switch ( $action ) {
			case 'take_screenshots':
				if ( empty( $data['sc_type'] ) ) {
					$errors[] = 'Screenshot type is required.';
				} elseif ( ! in_array( $data['sc_type'], WebChangeDetector_Admin::VALID_SC_TYPES, true ) ) {
					$errors[] = 'Invalid screenshot type.';
				}
				break;

			case 'bulk_screenshots':
				if ( empty( $data['urls'] ) || ! is_array( $data['urls'] ) ) {
					$errors[] = 'URLs array is required for bulk screenshots.';
				}
				break;

			case 'screenshot_comparison':
				if ( empty( $data['batch_id'] ) ) {
					$errors[] = 'Batch ID is required for comparison.';
				}
				break;
		}

		return array(
			'success' => empty( $errors ),
			'errors' => $errors,
		);
	}
} 