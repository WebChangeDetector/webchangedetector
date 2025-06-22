<?php
/**
 * Enhanced WebChangeDetector Admin Class with Error Handling
 *
 * Integrates comprehensive error handling, logging, and user feedback.
 *
 * @package    WebChangeDetector
 * @subpackage WebChangeDetector/admin
 */

namespace WebChangeDetector;

/**
 * Enhanced WebChangeDetector Admin Class.
 *
 * Extends the base admin class with advanced error handling capabilities.
 */
class WebChangeDetector_Admin_Enhanced extends WebChangeDetector_Admin {

	/**
	 * Error handler instance.
	 *
	 * @var WebChangeDetector_Error_Handler
	 */
	public $error_handler;

	/**
	 * Logger instance.
	 *
	 * @var WebChangeDetector_Logger
	 */
	public $logger;

	/**
	 * Error recovery instance.
	 *
	 * @var WebChangeDetector_Error_Recovery
	 */
	public $error_recovery;

	/**
	 * User feedback instance.
	 *
	 * @var WebChangeDetector_User_Feedback
	 */
	public $user_feedback;

	/**
	 * Constructor.
	 *
	 * @param string $plugin_name The name of this plugin.
	 */
	public function __construct( $plugin_name = 'WebChangeDetector' ) {
		// Initialize error handling components first.
		$this->logger = new WebChangeDetector_Logger();
		$this->error_handler = new WebChangeDetector_Error_Handler( $this->logger );
		$this->error_recovery = new WebChangeDetector_Error_Recovery( $this->logger );
		$this->user_feedback = new WebChangeDetector_User_Feedback();

		// Call parent constructor with error handling.
		$this->execute_with_error_handling( function() use ( $plugin_name ) {
			parent::__construct( $plugin_name );
		}, array(), array(
			'context' => 'Admin Initialization',
			'user_message' => 'Failed to initialize WebChangeDetector admin.',
		) );

		// Set up error handling hooks.
		$this->setup_error_handling_hooks();
	}

	/**
	 * Execute operation with comprehensive error handling.
	 *
	 * @param callable $operation Operation to execute.
	 * @param array    $args     Arguments for operation.
	 * @param array    $options  Error handling options.
	 * @return array Result array.
	 */
	public function execute_with_error_handling( $operation, $args = array(), $options = array() ) {
		$result = $this->error_handler->execute_with_error_handling( $operation, $args, $options );

		// Add user feedback for errors.
		if ( ! $result['success'] ) {
			$this->user_feedback->add_error( $result['message'], array(
				'error_code' => $result['error'] ?? '',
				'context'    => $options['context'] ?? '',
			) );

			// Attempt recovery if configured.
			if ( isset( $options['attempt_recovery'] ) && $options['attempt_recovery'] ) {
				$recovery_result = $this->error_recovery->attempt_recovery( 
					new \Exception( $result['error'] ?? $result['message'] )
				);
				
				if ( $recovery_result['success'] ) {
					$this->user_feedback->add_info(
						'System automatically recovered: ' . $recovery_result['message']
					);
				}
			}
		} else {
			// Add success feedback if requested.
			if ( isset( $options['success_message'] ) ) {
				$this->user_feedback->add_success( $options['success_message'] );
			}
		}

		return $result;
	}

	/**
	 * Enhanced API call with error handling.
	 *
	 * @param callable $api_call API call function.
	 * @param array    $args     Arguments for API call.
	 * @param array    $options  Additional options.
	 * @return array Result array.
	 */
	public function safe_api_call( $api_call, $args = array(), $options = array() ) {
		$options = wp_parse_args( $options, array(
			'attempt_recovery' => true,
			'user_message'     => 'API operation failed. Please try again.',
			'context'          => 'API Call',
		) );

		return $this->error_handler->handle_api_error( $api_call, $args, $options );
	}

	/**
	 * Enhanced database operation with error handling.
	 *
	 * @param callable $db_operation Database operation.
	 * @param array    $args         Arguments for operation.
	 * @param array    $options      Additional options.
	 * @return array Result array.
	 */
	public function safe_db_operation( $db_operation, $args = array(), $options = array() ) {
		$options = wp_parse_args( $options, array(
			'attempt_recovery' => true,
			'user_message'     => 'Database operation failed. Please try again.',
			'context'          => 'Database Operation',
		) );

		return $this->error_handler->handle_database_error( $db_operation, $args, $options );
	}

	/**
	 * Override website creation with enhanced error handling.
	 *
	 * @return array Website creation result.
	 */
	public function create_website_and_groups() {
		return $this->safe_api_call( function() {
			return parent::create_website_and_groups();
		}, array(), array(
			'context'          => 'Website and Groups Creation',
			'user_message'     => 'Failed to create website and groups. Please check your API credentials.',
			'success_message'  => 'Website and groups created successfully.',
			'attempt_recovery' => true,
		) );
	}

	/**
	 * Enhanced screenshot taking with error handling.
	 *
	 * @param string $group_uuid Group UUID.
	 * @param string $sc_type    Screenshot type.
	 * @param array  $options    Additional options.
	 * @return array Screenshot result.
	 */
	public function take_screenshots_safely( $group_uuid, $sc_type, $options = array() ) {
		return $this->safe_api_call( function() use ( $group_uuid, $sc_type ) {
			return \WebChangeDetector\WebChangeDetector_API_V2::take_screenshot_v2( $group_uuid, $sc_type );
		}, array(), array(
			'context'          => 'Screenshot Operation',
			'user_message'     => 'Failed to take screenshots. Please check your connection and try again.',
			'success_message'  => 'Screenshots initiated successfully.',
			'attempt_recovery' => true,
		) );
	}

	/**
	 * Enhanced URL sync with error handling.
	 *
	 * @param bool $force Force sync.
	 * @return array Sync result.
	 */
	public function sync_urls_safely( $force = false ) {
		return $this->execute_with_error_handling( function() use ( $force ) {
			return $this->wordpress_handler->sync_posts( $force );
		}, array(), array(
			'context'          => 'URL Synchronization',
			'user_message'     => 'Failed to synchronize URLs. Please try again.',
			'success_message'  => 'URLs synchronized successfully.',
			'attempt_recovery' => true,
		) );
	}

	/**
	 * Get system health status.
	 *
	 * @return array Health status.
	 */
	public function get_system_health() {
		return $this->error_recovery->get_health_status();
	}

	/**
	 * Refresh system health status.
	 *
	 * @return array Fresh health status.
	 */
	public function refresh_system_health() {
		return $this->error_recovery->refresh_health_status();
	}

	/**
	 * Get error statistics for dashboard.
	 *
	 * @param string $timeframe Timeframe for statistics.
	 * @return array Error statistics.
	 */
	public function get_error_statistics( $timeframe = 'day' ) {
		return $this->logger->get_statistics( $timeframe );
	}

	/**
	 * Get recent error logs.
	 *
	 * @param int    $limit     Number of entries.
	 * @param string $level     Log level filter.
	 * @param string $category  Category filter.
	 * @return array Recent logs.
	 */
	public function get_recent_logs( $limit = 50, $level = '', $category = '' ) {
		return $this->logger->get_recent_logs( $limit, $level, $category );
	}

	/**
	 * Clear old logs and errors.
	 *
	 * @param int $days Number of days to keep.
	 */
	public function cleanup_old_data( $days = 30 ) {
		$this->execute_with_error_handling( function() use ( $days ) {
			$this->logger->cleanup_old_logs( $days );
			$this->user_feedback->clear_feedback( false ); // Clear non-persistent feedback.
		}, array(), array(
			'context'         => 'Data Cleanup',
			'user_message'    => 'Failed to clean up old data.',
			'success_message' => 'Old data cleaned up successfully.',
		) );
	}

	/**
	 * Setup error handling hooks.
	 */
	private function setup_error_handling_hooks() {
		// Hook into WordPress error handling.
		add_action( 'wp_die_handler', array( $this, 'handle_wp_die' ), 10, 1 );
		
		// Setup custom error handlers.
		add_action( 'webchangedetector_api_error', array( $this, 'handle_api_error' ), 10, 2 );
		add_action( 'webchangedetector_database_error', array( $this, 'handle_database_error' ), 10, 2 );
		
		// Setup admin hooks.
		add_action( 'admin_init', array( $this->user_feedback, 'enqueue_feedback_scripts' ) );
		
		// Setup AJAX handlers for error recovery.
		add_action( 'wp_ajax_webchangedetector_run_health_check', array( $this, 'ajax_run_health_check' ) );
		add_action( 'wp_ajax_webchangedetector_clear_logs', array( $this, 'ajax_clear_logs' ) );
		add_action( 'wp_ajax_webchangedetector_attempt_recovery', array( $this, 'ajax_attempt_recovery' ) );
	}

	/**
	 * Handle WordPress die errors.
	 *
	 * @param callable $handler Original handler.
	 * @return callable Modified handler.
	 */
	public function handle_wp_die( $handler ) {
		return function( $message, $title = '', $args = array() ) use ( $handler ) {
			// Log the wp_die error.
			$this->logger->error(
				"WordPress die: {$message}",
				'wp_core',
				array(
					'title' => $title,
					'args'  => $args,
				)
			);
			
			// Call original handler.
			return call_user_func( $handler, $message, $title, $args );
		};
	}

	/**
	 * Handle API errors.
	 *
	 * @param \Exception $exception API exception.
	 * @param array      $context   Error context.
	 */
	public function handle_api_error( $exception, $context = array() ) {
		$this->logger->error(
			"API Error: {$exception->getMessage()}",
			'api',
			array_merge( $context, array( 'exception' => $exception ) )
		);

		// Add user feedback.
		$this->user_feedback->add_exception_feedback( $exception );

		// Attempt automatic recovery.
		$recovery_result = $this->error_recovery->attempt_recovery( $exception );
		if ( $recovery_result['success'] ) {
			$this->user_feedback->add_info( 'System recovered: ' . $recovery_result['message'] );
		}
	}

	/**
	 * Handle database errors.
	 *
	 * @param \Exception $exception Database exception.
	 * @param array      $context   Error context.
	 */
	public function handle_database_error( $exception, $context = array() ) {
		$this->logger->error(
			"Database Error: {$exception->getMessage()}",
			'database',
			array_merge( $context, array( 'exception' => $exception ) )
		);

		// Add user feedback.
		$this->user_feedback->add_exception_feedback( $exception );
	}

	/**
	 * AJAX handler for health check.
	 */
	public function ajax_run_health_check() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( 'Insufficient permissions' );
		}

		if ( ! wp_verify_nonce( $_POST['nonce'] ?? '', 'webchangedetector_health_check' ) ) {
			wp_send_json_error( 'Invalid nonce' );
		}

		$health_status = $this->refresh_system_health();
		wp_send_json_success( $health_status );
	}

	/**
	 * AJAX handler for clearing logs.
	 */
	public function ajax_clear_logs() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( 'Insufficient permissions' );
		}

		if ( ! wp_verify_nonce( $_POST['nonce'] ?? '', 'webchangedetector_clear_logs' ) ) {
			wp_send_json_error( 'Invalid nonce' );
		}

		$days = intval( $_POST['days'] ?? 30 );
		$this->cleanup_old_data( $days );
		
		wp_send_json_success( array( 'message' => 'Logs cleared successfully' ) );
	}

	/**
	 * AJAX handler for manual error recovery.
	 */
	public function ajax_attempt_recovery() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( 'Insufficient permissions' );
		}

		if ( ! wp_verify_nonce( $_POST['nonce'] ?? '', 'webchangedetector_recovery' ) ) {
			wp_send_json_error( 'Invalid nonce' );
		}

		$error_type = sanitize_text_field( $_POST['error_type'] ?? '' );
		$error_message = sanitize_text_field( $_POST['error_message'] ?? 'Manual recovery attempt' );

		// Create exception based on type.
		$exception = $this->create_exception_for_recovery( $error_type, $error_message );
		
		$recovery_result = $this->error_recovery->attempt_recovery( $exception );
		
		if ( $recovery_result['success'] ) {
			wp_send_json_success( $recovery_result );
		} else {
			wp_send_json_error( $recovery_result );
		}
	}

	/**
	 * Create appropriate exception for recovery testing.
	 *
	 * @param string $error_type    Error type.
	 * @param string $error_message Error message.
	 * @return \Exception Appropriate exception.
	 */
	private function create_exception_for_recovery( $error_type, $error_message ) {
		switch ( $error_type ) {
			case 'api':
				return new WebChangeDetector_API_Exception( $error_message );
			case 'database':
				return new WebChangeDetector_Database_Exception( $error_message );
			case 'filesystem':
				return new WebChangeDetector_Filesystem_Exception( '', '', $error_message );
			case 'authentication':
				return new WebChangeDetector_Authentication_Exception( $error_message );
			default:
				return new WebChangeDetector_Exception( $error_message );
		}
	}

	/**
	 * Enhanced error logging method.
	 *
	 * @param string $message  Log message.
	 * @param string $level    Log level.
	 * @param string $category Log category.
	 * @param array  $context  Additional context.
	 */
	public function log( $message, $level = 'info', $category = 'general', $context = array() ) {
		$this->logger->log( $message, $level, $category, $context );
	}
} 