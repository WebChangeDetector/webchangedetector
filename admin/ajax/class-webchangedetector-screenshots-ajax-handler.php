<?php

namespace WebChangeDetector;

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
			// Get website details for processing queue.
			$website_details = $this->admin->website_details;

			if ( empty( $website_details ) || ! isset( $website_details['uuid'] ) ) {
				$this->send_error_response(
					__( 'Website details not available.', 'webchangedetector' ),
					'Website details missing'
				);
				return;
			}

			$uuid       = $website_details['uuid'];
			$queue_data = \WebChangeDetector\WebChangeDetector_API_V2::get_queues_v2( $uuid, 'processing,open', false, array( 'per_page' => 30 ) );

			if ( is_wp_error( $queue_data ) ) {
				$this->send_error_response(
					__( 'Failed to get processing queue.', 'webchangedetector' ),
					'API error: ' . $queue_data->get_error_message()
				);
				return;
			}

			$this->send_success_response( $queue_data );

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
		if ( ! $this->security_check() ) {
			return;
		}

		$post_data = $this->validate_post_data( array( 'id', 'status' ) );

		if ( false === $post_data ) {
			$this->send_error_response(
				__( 'Missing required fields.', 'webchangedetector' ),
				'Missing id or status'
			);
			return;
		}

		try {
			$comparison_id = $post_data['id'];
			$new_status    = $post_data['status'];

			// Validate status value.
			$valid_statuses = array( 'ok', 'to_fix', 'false_positive', 'new' );
			if ( ! in_array( $new_status, $valid_statuses, true ) ) {
				$this->send_error_response(
					__( 'Invalid status value.', 'webchangedetector' ),
					'Invalid status: ' . $new_status
				);
				return;
			}

			$result = $this->screenshots_handler->update_comparison_status( $comparison_id, $new_status );

			if ( is_wp_error( $result ) ) {
				$this->send_error_response(
					__( 'Failed to update comparison status.', 'webchangedetector' ),
					'API error: ' . $result->get_error_message()
				);
				return;
			}

			$this->send_success_response(
				$new_status,
				__( 'Comparison status updated successfully.', 'webchangedetector' )
			);

		} catch ( \Exception $e ) {
			$this->send_error_response(
				__( 'An error occurred while updating comparison status.', 'webchangedetector' ),
				'Exception: ' . $e->getMessage()
			);
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

			// Get optional filters.
			$filters = isset( $post_data['filters'] ) ? $post_data['filters'] : array();

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
}
