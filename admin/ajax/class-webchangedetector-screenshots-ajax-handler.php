<?php
/**
 * Screenshots AJAX handler.
 *
 * Handles all screenshot and comparison-related AJAX operations including
 * processing queue management, status updates, and batch comparison views.
 *
 * @link       https://www.webchangedetector.com
 * @since      4.0.0
 * @package    WebChangeDetector
 * @subpackage WebChangeDetector/admin/ajax
 */

namespace WebChangeDetector;

/**
 * Screenshots AJAX handler.
 *
 * Handles all screenshot and comparison-related AJAX operations.
 *
 * @since      4.0.0
 * @package    WebChangeDetector
 * @subpackage WebChangeDetector/admin/ajax
 * @author     Mike Miler <mike@webchangedetector.com>
 */
class WebChangeDetector_Screenshots_Ajax_Handler extends WebChangeDetector_Ajax_Handler_Base {

	/**
	 * The screenshots handler instance.
	 *
	 * @since    4.0.0
	 * @access   private
	 * @var      WebChangeDetector_Admin_Screenshots    $screenshots_handler    The screenshots handler instance.
	 */
	private $screenshots_handler;

	/**
	 * The account handler instance.
	 *
	 * @since    4.0.0
	 * @access   private
	 * @var      WebChangeDetector_Admin_Account    $account_handler    The account handler instance.
	 */
	private $account_handler;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    4.0.0
	 * @param    WebChangeDetector_Admin             $admin               The main admin class instance.
	 * @param    WebChangeDetector_Admin_Screenshots $screenshots_handler The screenshots handler instance.
	 * @param    WebChangeDetector_Admin_Account     $account_handler     The account handler instance.
	 */
	public function __construct( $admin, $screenshots_handler, $account_handler ) {
		parent::__construct( $admin );

		$this->screenshots_handler = $screenshots_handler;
		$this->account_handler     = $account_handler;
	}

	/**
	 * Register AJAX hooks for screenshots operations.
	 *
	 * Registers all WordPress AJAX hooks for screenshot-related operations.
	 *
	 * @since    4.0.0
	 */
	public function register_hooks() {
		add_action( 'wp_ajax_get_processing_queue', array( $this, 'ajax_get_processing_queue' ) );
		add_action( 'wp_ajax_update_comparison_status', array( $this, 'ajax_update_comparison_status' ) );
		add_action( 'wp_ajax_get_batch_comparisons_view', array( $this, 'ajax_get_batch_comparisons_view' ) );
		add_action( 'wp_ajax_load_failed_queues', array( $this, 'ajax_load_failed_queues' ) );
		add_action( 'wp_ajax_get_batch_processing_status', array( $this, 'ajax_get_batch_processing_status' ) );
		add_action( 'wp_ajax_get_new_change_detections', array( $this, 'ajax_get_new_change_detections' ) );
		add_action( 'wp_ajax_get_completed_pre_screenshots', array( $this, 'ajax_get_completed_pre_screenshots' ) );
		add_action( 'wp_ajax_get_failed_queues_json', array( $this, 'ajax_get_failed_queues_json' ) );
	}

	/**
	 * Handle get processing queue AJAX request.
	 *
	 * Retrieves the current processing queue status and returns it as JSON.
	 * Used for real-time updates of screenshot processing status.
	 *
	 * @since    4.0.0
	 */
	public function ajax_get_processing_queue() {
		if ( ! $this->security_check() ) {
			return;
		}

		try {
			$queue_data = \WebChangeDetector\WebChangeDetector_API_V2::get_queues_v2( false, 'processing,open', false, array( 'per_page' => 30 ) );

			if ( is_wp_error( $queue_data ) ) {
				$this->send_error_response(
					__( 'Failed to get processing queue.', 'webchangedetector' ),
					'API error: ' . $queue_data->get_error_message()
				);
				return;
			}

			wp_send_json( $queue_data );

		} catch ( \Exception $e ) {
			$this->send_error_response(
				__( 'An error occurred while getting processing queue.', 'webchangedetector' ),
				'Exception: ' . $e->getMessage()
			);
		}
	}

	/**
	 * Handle update comparison status AJAX request.
	 *
	 * Updates the status of a comparison (e.g., ok, to_fix, false_positive).
	 *
	 * @since    4.0.0
	 */
	public function ajax_update_comparison_status() {
		// Custom security check to return plain text 'failed' instead of JSON.
		if ( ! $this->verify_nonce( 'ajax-nonce' ) ) {
			echo 'failed';
			wp_die();
		}

		if ( ! $this->check_capability( 'manage_options' ) ) {
			echo 'failed';
			wp_die();
		}

		$post_data = $this->validate_post_data( array( 'id', 'status' ) );

		if ( false === $post_data ) {
			// Return plain text 'failed' to match JavaScript expectations.
			echo 'failed';
			wp_die();
		}

		try {
			$comparison_id = $post_data['id'];
			$new_status    = $post_data['status'];

			// Validate status value.
			$valid_statuses = array( 'ok', 'to_fix', 'false_positive', 'new' );
			if ( ! in_array( $new_status, $valid_statuses, true ) ) {
				echo 'failed';
				wp_die();
			}

			$result = $this->screenshots_handler->update_comparison_status( $comparison_id, $new_status );

			// Handle the response based on the result type.
			if ( true === $result ) {
				// API call successful.
				echo esc_html( $new_status );
				wp_die();
			}

			if ( is_array( $result ) ) {
				// Check for explicit success indicators.
				if ( isset( $result['success'] ) && $result['success'] ) {
					echo esc_html( $new_status );
					wp_die();
				}

				// Check for status in data.
				if ( isset( $result['data']['status'] ) ) {
					echo esc_html( $result['data']['status'] );
					wp_die();
				}

				// Check for Laravel-style success (no error key).
				if ( ! isset( $result['error'] ) && ! isset( $result['errors'] ) && ! isset( $result['message'] ) ) {
					echo esc_html( $new_status );
					wp_die();
				}

				// Array contains error information.
				echo 'failed';
				wp_die();
			}

			if ( is_wp_error( $result ) ) {
				echo 'failed';
				wp_die();
			}

			// Unknown result type - assume success.
			echo esc_html( $new_status );
			wp_die();

		} catch ( \Exception $e ) {
			// Log the error for debugging.
			if ( $this->admin && method_exists( $this->admin, 'log_error' ) ) {
				$this->admin->log_error( 'Status update exception: ' . $e->getMessage(), 'ajax' );
			}
			echo 'failed';
			wp_die();
		}
	}

	/**
	 * Handle get batch comparisons view AJAX request.
	 *
	 * Retrieves and renders the batch comparisons view with optional filters.
	 *
	 * @since    4.0.0
	 */
	public function ajax_get_batch_comparisons_view() {
		if ( ! $this->security_check() ) {
			return;
		}

		try {
			$post_data = $this->validate_post_data( array( 'batch_id' ) );

			if ( false === $post_data ) {
				$this->send_error_response(
					__( 'Missing batch ID.', 'webchangedetector' ),
					'Missing batch_id'
				);
				return;
			}

			$batch_id = $post_data['batch_id'];

			// Get and sanitize the POST data.
			$per_page              = isset( $post_data['per_page'] ) ? absint( $post_data['per_page'] ) : 30;
			$page                  = isset( $post_data['page'] ) ? absint( $post_data['page'] ) : 1;
			$status                = isset( $post_data['status'] ) ? sanitize_text_field( $post_data['status'] ) : '';
			$group_id              = isset( $post_data['group_id'] ) ? sanitize_text_field( $post_data['group_id'] ) : '';
			$url_search            = isset( $post_data['url_search'] ) ? sanitize_text_field( $post_data['url_search'] ) : '';
			$console_changes_count = isset( $post_data['console_changes_count'] ) ? absint( $post_data['console_changes_count'] ) : 0;

			// Build filters array for API call.
			$api_filters = array(
				'per_page' => $per_page,
				'page'     => $page,
				'batches'  => $batch_id,
			);
			if ( ! empty( $status ) ) {
				$api_filters['status'] = $status;
			}
			if ( ! empty( $group_id ) ) {
				$api_filters['group_id'] = $group_id;
			}
			if ( ! empty( $url_search ) ) {
				$api_filters['url_search'] = $url_search;
			}

			// Get comparisons from API using the same method as the original handler.
			$comparisons = \WebChangeDetector\WebChangeDetector_API_V2::get_comparisons_v2( $api_filters );

			// Build display filters array.
			$display_filters = array();
			if ( ! empty( $status ) ) {
				$display_filters['status'] = $status;
			}
			if ( ! empty( $group_id ) ) {
				$display_filters['group_id'] = $group_id;
			}
			if ( ! empty( $url_search ) ) {
				$display_filters['url_search'] = $url_search;
			}

			// Delegate to admin dashboard for view loading.
			if ( $this->admin && $this->admin->dashboard_handler && method_exists( $this->admin->dashboard_handler, 'load_comparisons_view' ) ) {
				// Output HTML directly (not JSON) to match JavaScript expectations.
				$this->admin->dashboard_handler->load_comparisons_view( $batch_id, $comparisons, $display_filters, $console_changes_count );
				wp_die(); // Prevent WordPress from adding extra output.
			} else {
				$this->send_error_response(
					__( 'Dashboard handler not available.', 'webchangedetector' ),
					'Dashboard handler missing'
				);
			}
		} catch ( \Exception $e ) {
			$this->send_error_response(
				__( 'An error occurred while loading batch comparisons.', 'webchangedetector' ),
				'Exception: ' . $e->getMessage()
			);
		}
	}

	/**
	 * Handle load failed queues AJAX request.
	 *
	 * Loads and displays failed queue items for review and retry.
	 *
	 * @since    4.0.0
	 */
	public function ajax_load_failed_queues() {
		if ( ! $this->security_check() ) {
			return;
		}

		try {
			$post_data = $this->validate_post_data( array( 'batch_id' ) );

			if ( false === $post_data ) {
				$this->send_error_response(
					__( 'Missing batch ID.', 'webchangedetector' ),
					'Missing batch_id'
				);
				return;
			}

			$batch_id = $post_data['batch_id'];

			// Get failed queue items.
			$failed_queues = \WebChangeDetector\WebChangeDetector_API_V2::get_queues_v2( $batch_id, 'failed', false, array() );

			if ( is_wp_error( $failed_queues ) ) {
				$this->send_error_response(
					__( 'Failed to get failed queue items.', 'webchangedetector' ),
					'API error: ' . $failed_queues->get_error_message()
				);
				return;
			}

			// Load the failed queues view - output HTML directly.
			$this->screenshots_handler->load_failed_queues_view( $batch_id, $failed_queues );
			wp_die(); // Prevent WordPress from adding extra output.

		} catch ( \Exception $e ) {
			$this->send_error_response(
				__( 'An error occurred while loading failed queues.', 'webchangedetector' ),
				'Exception: ' . $e->getMessage()
			);
		}
	}

	/**
	 * Handle get batch processing status AJAX request.
	 *
	 * Retrieves processing status counts for a batch, including breakdowns
	 * by queue status (open, processing, done, failed) and optionally by type.
	 *
	 * @since    4.0.0
	 */
	public function ajax_get_batch_processing_status() {
		if ( ! $this->security_check() ) {
			return;
		}

		try {
			$post_data = $this->validate_post_data( array( 'batch_id' ) );

			if ( false === $post_data ) {
				wp_send_json( array( 'error' => 'Missing batch_id' ) );
				return;
			}

			$batch_id = sanitize_text_field( $post_data['batch_id'] );

			// Single API call to get queue data with status counts.
			$queue_response = \WebChangeDetector\WebChangeDetector_API_V2::get_queues_v2( $batch_id, false, false, array( 'per_page' => 1 ) );

			// Extract status counts from meta field.
			$status_counts         = $queue_response['meta']['status_counts'] ?? null;
			$status_counts_by_type = $queue_response['meta']['status_counts_by_type'] ?? null;
			$by_type               = null;

			if ( $status_counts ) {
				$open_count       = $status_counts['open'] ?? 0;
				$processing_count = $status_counts['processing'] ?? 0;
				$done_count       = $status_counts['done'] ?? 0;
				$failed_count     = $status_counts['failed'] ?? 0;
				$by_type          = $status_counts['by_type'] ?? null;
			} else {
				// Fallback for backward compatibility.
				$queue_open       = \WebChangeDetector\WebChangeDetector_API_V2::get_queues_v2( $batch_id, 'open', false, array( 'per_page' => 1 ) );
				$queue_processing = \WebChangeDetector\WebChangeDetector_API_V2::get_queues_v2( $batch_id, 'processing', false, array( 'per_page' => 1 ) );
				$queue_done       = \WebChangeDetector\WebChangeDetector_API_V2::get_queues_v2( $batch_id, 'done', false, array( 'per_page' => 1 ) );
				$queue_failed     = \WebChangeDetector\WebChangeDetector_API_V2::get_queues_v2( $batch_id, 'failed', false, array( 'per_page' => 1 ) );

				$open_count       = $queue_open['meta']['total'] ?? 0;
				$processing_count = $queue_processing['meta']['total'] ?? 0;
				$done_count       = $queue_done['meta']['total'] ?? 0;
				$failed_count     = $queue_failed['meta']['total'] ?? 0;
			}

			$response = array(
				'open'            => $open_count,
				'processing'      => $processing_count,
				'done'            => $done_count,
				'failed'          => $failed_count,
				'total'           => $open_count + $processing_count + $done_count + $failed_count,
				'open_processing' => $open_count + $processing_count,
				'processed'       => $done_count + $failed_count,
				'by_type'         => $by_type,
			);

			// Include by_type counts from status_counts_by_type if available.
			if ( $status_counts_by_type ) {
				$response['by_type'] = $status_counts_by_type;
			}

			wp_send_json( $response );

		} catch ( \Exception $e ) {
			$this->send_error_response(
				__( 'An error occurred while getting batch processing status.', 'webchangedetector' ),
				'Exception: ' . $e->getMessage()
			);
		}
	}

	/**
	 * Handle get new change detections AJAX request.
	 *
	 * Retrieves comparisons for a batch, optionally filtered to only those
	 * above the change detection threshold.
	 *
	 * @since    4.0.0
	 */
	public function ajax_get_new_change_detections() {
		if ( ! $this->security_check() ) {
			return;
		}

		try {
			$post_data = $this->validate_post_data( array( 'batch_id' ) );

			if ( false === $post_data ) {
				wp_send_json(
					array(
						'comparisons' => array(),
						'total_count' => 0,
					)
				);
				return;
			}

			$batch_id = sanitize_text_field( $post_data['batch_id'] );
			// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce verified in security_check().
			$above_threshold = isset( $_POST['above_threshold'] ) ? filter_var( wp_unslash( $_POST['above_threshold'] ), FILTER_VALIDATE_BOOLEAN ) : false;

			$filter_options = array(
				'batches'         => $batch_id,
				'above_threshold' => $above_threshold ? 1 : 0,
				'per_page'        => 20,
				'page'            => 1,
			);

			$response = \WebChangeDetector\WebChangeDetector_API_V2::get_comparisons_v2( $filter_options );

			wp_send_json(
				array(
					'comparisons' => $response['data'] ?? array(),
					'total_count' => $response['meta']['total'] ?? 0,
				)
			);

		} catch ( \Exception $e ) {
			$this->send_error_response(
				__( 'An error occurred while getting change detections.', 'webchangedetector' ),
				'Exception: ' . $e->getMessage()
			);
		}
	}

	/**
	 * Handle get completed pre-screenshots AJAX request.
	 *
	 * Retrieves completed (done) queue items for a batch, returning
	 * a simplified JSON structure with screenshot details.
	 *
	 * @since    4.0.0
	 */
	public function ajax_get_completed_pre_screenshots() {
		if ( ! $this->security_check() ) {
			return;
		}

		try {
			$post_data = $this->validate_post_data( array( 'batch_id' ) );

			if ( false === $post_data ) {
				wp_send_json(
					array(
						'queues'     => array(),
						'total_done' => 0,
					)
				);
				return;
			}

			$batch_id = sanitize_text_field( $post_data['batch_id'] );

			$completed_queues = \WebChangeDetector\WebChangeDetector_API_V2::get_queues_v2(
				array( $batch_id ),
				'done',
				false,
				array( 'per_page' => 20 )
			);

			$queues = array();
			if ( ! empty( $completed_queues['data'] ) ) {
				foreach ( $completed_queues['data'] as $queue ) {
					if ( ( $queue['batch'] ?? '' ) !== $batch_id ) {
						continue;
					}
					$queues[] = array(
						'id'         => $queue['id'],
						'url_link'   => $queue['url_link'] ?? '',
						'html_title' => $queue['html_title'] ?? '',
						'device'     => $queue['device'] ?? 'desktop',
						'image_link' => $queue['image_link'] ?? '',
					);
				}
			}

			wp_send_json(
				array(
					'queues'     => $queues,
					'total_done' => count( $queues ),
				)
			);

		} catch ( \Exception $e ) {
			$this->send_error_response(
				__( 'An error occurred while getting completed pre-screenshots.', 'webchangedetector' ),
				'Exception: ' . $e->getMessage()
			);
		}
	}

	/**
	 * Handle get failed queues as JSON AJAX request.
	 *
	 * Returns failed queue items as structured JSON data, as opposed to
	 * ajax_load_failed_queues() which returns rendered HTML.
	 *
	 * @since    4.0.0
	 */
	public function ajax_get_failed_queues_json() {
		if ( ! $this->security_check() ) {
			return;
		}

		try {
			$post_data = $this->validate_post_data( array( 'batch_id' ) );

			if ( false === $post_data ) {
				wp_send_json(
					array(
						'queues'       => array(),
						'total_failed' => 0,
					)
				);
				return;
			}

			$batch_id = sanitize_text_field( $post_data['batch_id'] );

			$failed_queues = \WebChangeDetector\WebChangeDetector_API_V2::get_queues_v2( $batch_id, 'failed', false, array() );

			$queues = array();
			if ( ! empty( $failed_queues['data'] ) ) {
				foreach ( $failed_queues['data'] as $queue ) {
					$queues[] = array(
						'id'         => $queue['id'],
						'url_link'   => $queue['url_link'] ?? '',
						'html_title' => $queue['html_title'] ?? '',
						'device'     => $queue['device'] ?? 'desktop',
						'error_msg'  => $queue['error_msg'] ?? '',
					);
				}
			}

			wp_send_json(
				array(
					'queues'       => $queues,
					'total_failed' => count( $queues ),
				)
			);

		} catch ( \Exception $e ) {
			$this->send_error_response(
				__( 'An error occurred while getting failed queues.', 'webchangedetector' ),
				'Exception: ' . $e->getMessage()
			);
		}
	}
}
