<?php
/**
 * Comparison Action Handler for WebChangeDetector
 *
 * Handles all comparison-related actions and business logic.
 *
 * @package    WebChangeDetector
 * @subpackage WebChangeDetector/admin/actions
 * @author     Mike Miler <mike@wp-mike.com>
 */

namespace WebChangeDetector;

/**
 * Comparison Action Handler Class.
 */
class WebChangeDetector_Comparison_Action_Handler {

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
	 * Handle change comparison status action.
	 *
	 * @param array $data The comparison status data.
	 * @return array Result with success status and message.
	 */
	public function handle_change_comparison_status( $data ) {
		try {
			$comparison_id = sanitize_text_field( $data['comparison_id'] ?? '' );
			$status        = sanitize_text_field( $data['status'] ?? '' );

			// Validate inputs.
			$validation = $this->validate_status_change( $comparison_id, $status );
			if ( ! $validation['success'] ) {
				return $validation;
			}

			// Update comparison status via API.
			$result = \WebChangeDetector\WebChangeDetector_API_V2::update_comparison_v2( $comparison_id, $status );

			if ( $result['success'] ?? false ) {
				return array(
					'success'    => true,
					'message'    => 'Comparison status updated successfully.',
					'new_status' => $status,
				);
			} else {
				return array(
					'success' => false,
					'message' => $result['message'] ?? 'Failed to update comparison status.',
				);
			}
		} catch ( \Exception $e ) {
			return array(
				'success' => false,
				'message' => 'Error updating comparison status: ' . $e->getMessage(),
			);
		}
	}

	/**
	 * Handle filter change detections action.
	 *
	 * @param array $data The filter data.
	 * @return array Result with filtered comparisons.
	 */
	public function handle_filter_change_detections( $data ) {
		try {
			// Build filter parameters.
			$filters = $this->build_filter_parameters( $data );

			// Get filtered comparisons from API.
			$comparisons = \WebChangeDetector\WebChangeDetector_API_V2::get_comparisons_v2( $filters );

			if ( ! isset( $comparisons['data'] ) ) {
				return array(
					'success' => false,
					'message' => 'Failed to retrieve filtered comparisons.',
				);
			}

			return array(
				'success'     => true,
				'message'     => 'Comparisons filtered successfully.',
				'comparisons' => $comparisons['data'],
				'meta'        => $comparisons['meta'] ?? array(),
				'filters'     => $filters,
			);
		} catch ( \Exception $e ) {
			return array(
				'success' => false,
				'message' => 'Error filtering comparisons: ' . $e->getMessage(),
			);
		}
	}

	/**
	 * Validate comparison status change.
	 *
	 * @param string $comparison_id The comparison ID.
	 * @param string $status        The new status.
	 * @return array Validation result.
	 */
	private function validate_status_change( $comparison_id, $status ) {
		if ( empty( $comparison_id ) ) {
			return array(
				'success' => false,
				'message' => 'Comparison ID is required.',
			);
		}

		if ( empty( $status ) ) {
			return array(
				'success' => false,
				'message' => 'Status is required.',
			);
		}

		if ( ! in_array( $status, WebChangeDetector_Admin::VALID_COMPARISON_STATUS, true ) ) {
			return array(
				'success' => false,
				'message' => 'Invalid comparison status.',
			);
		}

		return array(
			'success' => true,
			'message' => 'Validation passed.',
		);
	}

	/**
	 * Build filter parameters from request data.
	 *
	 * @param array $data The filter data.
	 * @return array Filter parameters.
	 */
	private function build_filter_parameters( $data ) {
		$filters = array();

		// Status filter.
		if ( ! empty( $data['status'] ) && 'all' !== $data['status'] ) {
			$filters['status'] = sanitize_text_field( $data['status'] );
		}

		// Group filter.
		if ( ! empty( $data['group'] ) && 'all' !== $data['group'] ) {
			$filters['group'] = sanitize_text_field( $data['group'] );
		}

		// Date range filter.
		if ( ! empty( $data['date_from'] ) ) {
			$filters['date_from'] = sanitize_text_field( $data['date_from'] );
		}

		if ( ! empty( $data['date_to'] ) ) {
			$filters['date_to'] = sanitize_text_field( $data['date_to'] );
		}

		// URL filter.
		if ( ! empty( $data['url'] ) ) {
			$filters['url'] = sanitize_text_field( $data['url'] );
		}

		// Device filter.
		if ( ! empty( $data['device'] ) && 'all' !== $data['device'] ) {
			$filters['device'] = sanitize_text_field( $data['device'] );
		}

		// Pagination.
		if ( ! empty( $data['page'] ) ) {
			$filters['page'] = intval( $data['page'] );
		}

		if ( ! empty( $data['per_page'] ) ) {
			$filters['per_page'] = intval( $data['per_page'] );
		}

		// Sorting.
		if ( ! empty( $data['order_by'] ) ) {
			$filters['order_by'] = sanitize_text_field( $data['order_by'] );
		}

		if ( ! empty( $data['order_direction'] ) ) {
			$filters['order_direction'] = sanitize_text_field( $data['order_direction'] );
		}

		return $filters;
	}
}
