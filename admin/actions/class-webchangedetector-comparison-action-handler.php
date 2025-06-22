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
			$status = sanitize_text_field( $data['status'] ?? '' );
			
			// Validate inputs.
			$validation = $this->validate_status_change( $comparison_id, $status );
			if ( ! $validation['success'] ) {
				return $validation;
			}

			// Update comparison status via API.
			$result = \WebChangeDetector\WebChangeDetector_API_V2::update_comparison_status_v2( $comparison_id, $status );
			
			if ( $result['success'] ?? false ) {
				return array(
					'success' => true,
					'message' => 'Comparison status updated successfully.',
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
	 * Handle show comparison action.
	 *
	 * @param array $data The comparison view data.
	 * @return array Result with comparison data.
	 */
	public function handle_show_comparison( $data ) {
		try {
			$comparison_id = sanitize_text_field( $data['comparison_id'] ?? '' );
			
			if ( empty( $comparison_id ) ) {
				return array(
					'success' => false,
					'message' => 'Comparison ID is required.',
				);
			}

			// Get comparison details from API.
			$comparison = \WebChangeDetector\WebChangeDetector_API_V2::get_comparison_v2( $comparison_id );
			
			if ( empty( $comparison['data'] ) ) {
				return array(
					'success' => false,
					'message' => 'Comparison not found.',
				);
			}

			return array(
				'success' => true,
				'message' => 'Comparison retrieved successfully.',
				'comparison' => $comparison['data'],
			);
		} catch ( \Exception $e ) {
			return array(
				'success' => false,
				'message' => 'Error retrieving comparison: ' . $e->getMessage(),
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
				'success' => true,
				'message' => 'Comparisons filtered successfully.',
				'comparisons' => $comparisons['data'],
				'meta' => $comparisons['meta'] ?? array(),
				'filters' => $filters,
			);
		} catch ( \Exception $e ) {
			return array(
				'success' => false,
				'message' => 'Error filtering comparisons: ' . $e->getMessage(),
			);
		}
	}

	/**
	 * Handle bulk comparison actions.
	 *
	 * @param array $data The bulk action data.
	 * @return array Result with success status and message.
	 */
	public function handle_bulk_comparison_actions( $data ) {
		try {
			$comparison_ids = $data['comparison_ids'] ?? array();
			$action = sanitize_text_field( $data['bulk_action'] ?? '' );
			
			if ( empty( $comparison_ids ) || ! is_array( $comparison_ids ) ) {
				return array(
					'success' => false,
					'message' => 'No comparisons selected for bulk action.',
				);
			}

			if ( empty( $action ) ) {
				return array(
					'success' => false,
					'message' => 'No bulk action specified.',
				);
			}

			$results = array();
			$successful_count = 0;
			$errors = array();

			foreach ( $comparison_ids as $comparison_id ) {
				$comparison_id = sanitize_text_field( $comparison_id );
				
				switch ( $action ) {
					case 'mark_ok':
						$result = $this->handle_change_comparison_status( array(
							'comparison_id' => $comparison_id,
							'status' => 'ok',
						) );
						break;
					
					case 'mark_false_positive':
						$result = $this->handle_change_comparison_status( array(
							'comparison_id' => $comparison_id,
							'status' => 'false_positive',
						) );
						break;
					
					case 'mark_to_fix':
						$result = $this->handle_change_comparison_status( array(
							'comparison_id' => $comparison_id,
							'status' => 'to_fix',
						) );
						break;
					
					default:
						$result = array(
							'success' => false,
							'message' => 'Invalid bulk action.',
						);
				}

				$results[ $comparison_id ] = $result;
				
				if ( $result['success'] ) {
					$successful_count++;
				} else {
					$errors[] = 'ID ' . $comparison_id . ': ' . $result['message'];
				}
			}

			$message = sprintf( 
				'Bulk action completed: %d of %d comparisons updated successfully.', 
				$successful_count, 
				count( $comparison_ids ) 
			);
			
			if ( ! empty( $errors ) ) {
				$message .= ' Errors: ' . implode( ', ', array_slice( $errors, 0, 3 ) );
				if ( count( $errors ) > 3 ) {
					$message .= ' and ' . ( count( $errors ) - 3 ) . ' more.';
				}
			}

			return array(
				'success' => $successful_count > 0,
				'message' => $message,
				'successful_count' => $successful_count,
				'total_count' => count( $comparison_ids ),
				'errors' => $errors,
				'results' => $results,
			);
		} catch ( \Exception $e ) {
			return array(
				'success' => false,
				'message' => 'Error processing bulk actions: ' . $e->getMessage(),
			);
		}
	}

	/**
	 * Get comparison statistics.
	 *
	 * @param array $filters Optional filters for the statistics.
	 * @return array Statistics data.
	 */
	public function get_comparison_statistics( $filters = array() ) {
		try {
			// Get comparisons with filters.
			$comparisons = \WebChangeDetector\WebChangeDetector_API_V2::get_comparisons_v2( $filters );
			
			if ( empty( $comparisons['data'] ) ) {
				return array(
					'total' => 0,
					'new' => 0,
					'ok' => 0,
					'to_fix' => 0,
					'false_positive' => 0,
				);
			}

			// Count by status.
			$stats = array(
				'total' => count( $comparisons['data'] ),
				'new' => 0,
				'ok' => 0,
				'to_fix' => 0,
				'false_positive' => 0,
			);

			foreach ( $comparisons['data'] as $comparison ) {
				$status = $comparison['status'] ?? 'new';
				if ( isset( $stats[ $status ] ) ) {
					$stats[ $status ]++;
				}
			}

			return $stats;
		} catch ( \Exception $e ) {
			return array(
				'error' => 'Error retrieving statistics: ' . $e->getMessage(),
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

	/**
	 * Export comparison data.
	 *
	 * @param array $data Export parameters.
	 * @return array Export result.
	 */
	public function handle_export_comparisons( $data ) {
		try {
			$format = sanitize_text_field( $data['format'] ?? 'csv' );
			$filters = $this->build_filter_parameters( $data );
			
			// Get comparisons for export.
			$comparisons = \WebChangeDetector\WebChangeDetector_API_V2::get_comparisons_v2( $filters );
			
			if ( empty( $comparisons['data'] ) ) {
				return array(
					'success' => false,
					'message' => 'No comparisons found for export.',
				);
			}

			// Format data for export.
			$export_data = $this->format_export_data( $comparisons['data'], $format );
			
			return array(
				'success' => true,
				'message' => 'Export data prepared successfully.',
				'data' => $export_data,
				'format' => $format,
				'count' => count( $comparisons['data'] ),
			);
		} catch ( \Exception $e ) {
			return array(
				'success' => false,
				'message' => 'Error exporting comparisons: ' . $e->getMessage(),
			);
		}
	}

	/**
	 * Format comparison data for export.
	 *
	 * @param array  $comparisons The comparison data.
	 * @param string $format      The export format.
	 * @return mixed Formatted export data.
	 */
	private function format_export_data( $comparisons, $format ) {
		switch ( $format ) {
			case 'csv':
				return $this->format_csv_export( $comparisons );
			
			case 'json':
				return wp_json_encode( $comparisons, JSON_PRETTY_PRINT );
			
			default:
				return $comparisons;
		}
	}

	/**
	 * Format comparisons as CSV data.
	 *
	 * @param array $comparisons The comparison data.
	 * @return string CSV formatted data.
	 */
	private function format_csv_export( $comparisons ) {
		$csv_data = "ID,URL,Device,Status,Date,Difference,Notes\n";
		
		foreach ( $comparisons as $comparison ) {
			$csv_data .= sprintf(
				"%s,%s,%s,%s,%s,%s,%s\n",
				$comparison['id'] ?? '',
				'"' . str_replace( '"', '""', $comparison['url'] ?? '' ) . '"',
				$comparison['device'] ?? '',
				$comparison['status'] ?? '',
				$comparison['created_at'] ?? '',
				$comparison['difference_percent'] ?? '',
				'"' . str_replace( '"', '""', $comparison['notes'] ?? '' ) . '"'
			);
		}
		
		return $csv_data;
	}
} 