<?php
/**
 * WebChangeDetector Error Handler
 *
 * Comprehensive error handling, logging, and recovery system.
 *
 * @package    WebChangeDetector
 * @subpackage WebChangeDetector/admin/error-handling
 */

namespace WebChangeDetector;

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WebChangeDetector Error Handler Class
 *
 * Provides centralized error handling, logging, and user feedback.
 */
class WebChangeDetector_Error_Handler {

	/**
	 * Error severity levels.
	 */
	const SEVERITY_DEBUG    = 'debug';
	const SEVERITY_INFO     = 'info';
	const SEVERITY_WARNING  = 'warning';
	const SEVERITY_ERROR    = 'error';
	const SEVERITY_CRITICAL = 'critical';

	/**
	 * Error categories.
	 */
	const CATEGORY_API        = 'api';
	const CATEGORY_FILESYSTEM = 'filesystem';
	const CATEGORY_NETWORK    = 'network';
	const CATEGORY_VALIDATION = 'validation';
	const CATEGORY_AUTH       = 'authentication';
	const CATEGORY_PERMISSION = 'permission';
	const CATEGORY_GENERAL    = 'general';

	/**
	 * Maximum number of retry attempts.
	 *
	 * @var int
	 */
	private $max_retries = 3;

	/**
	 * Logger instance.
	 *
	 * @var WebChangeDetector_Logger
	 */
	private $logger;

	/**
	 * Error callbacks for different scenarios.
	 *
	 * @var array
	 */
	private $error_callbacks = array();

	/**
	 * Constructor.
	 *
	 * @param WebChangeDetector_Logger $logger Logger instance.
	 */
	public function __construct( $logger = null ) {
		$this->logger = $logger ?: new WebChangeDetector_Logger();
		$this->register_default_callbacks();
	}

	/**
	 * Execute operation with comprehensive error handling.
	 *
	 * @param callable $operation     Operation to execute.
	 * @param array    $args         Arguments for the operation.
	 * @param array    $options      Error handling options.
	 * @return array Result array with success/error status.
	 */
	public function execute_with_error_handling( $operation, $args = array(), $options = array() ) {
		$options = wp_parse_args(
			$options,
			array(
				'retries'         => $this->max_retries,
				'category'        => self::CATEGORY_GENERAL,
				'context'         => '',
				'user_message'    => 'An error occurred. Please try again.',
				'log_errors'      => true,
				'return_wp_error' => false,
				'timeout'         => 30,
			)
		);

		$attempts   = 0;
		$last_error = null;

		while ( $attempts <= $options['retries'] ) {
			try {
				// Execute the operation.
				$result = call_user_func_array( $operation, $args );

				// Log successful execution on retry.
				if ( $attempts > 0 && $options['log_errors'] ) {
					$this->logger->log(
						"Operation succeeded after {$attempts} retries",
						self::SEVERITY_INFO,
						$options['category'],
						array( 'context' => $options['context'] )
					);
				}

				return array(
					'success' => true,
					'data'    => $result,
					'message' => 'Operation completed successfully.',
					'retries' => $attempts,
				);

			} catch ( \Exception $e ) {
				++$attempts;
				$last_error = $e;

				// Log the error.
				if ( $options['log_errors'] ) {
					$this->logger->log(
						"Operation failed (attempt {$attempts}): " . $e->getMessage(),
						$attempts > $options['retries'] ? self::SEVERITY_ERROR : self::SEVERITY_WARNING,
						$options['category'],
						array(
							'context'   => $options['context'],
							'exception' => $e,
							'attempt'   => $attempts,
						)
					);
				}

				// Execute error callback if registered.
				$this->execute_error_callback( $e, $options['category'], $attempts );

				// If we've exhausted retries, break.
				if ( $attempts > $options['retries'] ) {
					break;
				}

				// Exponential backoff for retries.
				if ( $attempts < $options['retries'] ) {
					$delay = min( pow( 2, $attempts ), 10 ); // Max 10 seconds delay.
					sleep( $delay );
				}
			}
		}

		// Handle final failure.
		$error_message = $last_error ? $last_error->getMessage() : 'Unknown error occurred';

		if ( $options['return_wp_error'] ) {
			return new \WP_Error(
				'operation_failed',
				$options['user_message'],
				array(
					'error'    => $error_message,
					'category' => $options['category'],
					'retries'  => $attempts - 1,
				)
			);
		}

		return array(
			'success'  => false,
			'message'  => $options['user_message'],
			'error'    => $error_message,
			'category' => $options['category'],
			'retries'  => $attempts - 1,
		);
	}

	/**
	 * Handle API errors specifically.
	 *
	 * @param callable $api_call    API call function.
	 * @param array    $args        Arguments for API call.
	 * @param array    $options     Options for error handling.
	 * @return array Result array.
	 */
	public function handle_api_error( $api_call, $args = array(), $options = array() ) {
		$options = wp_parse_args(
			$options,
			array(
				'category'     => self::CATEGORY_API,
				'user_message' => 'Failed to communicate with WebChangeDetector service. Please check your connection and try again.',
				'context'      => 'API Operation',
			)
		);

		return $this->execute_with_error_handling( $api_call, $args, $options );
	}


	/**
	 * Handle validation errors.
	 *
	 * @param array $validation_errors Array of validation errors.
	 * @param array $options          Options for error handling.
	 * @return array Result array.
	 */
	public function handle_validation_errors( $validation_errors, $options = array() ) {
		$options = wp_parse_args(
			$options,
			array(
				'category' => self::CATEGORY_VALIDATION,
				'context'  => 'Validation',
			)
		);

		if ( empty( $validation_errors ) ) {
			return array(
				'success' => true,
				'message' => 'Validation passed.',
			);
		}

		// Log validation errors.
		$this->logger->log(
			'Validation failed: ' . wp_json_encode( $validation_errors ),
			self::SEVERITY_WARNING,
			$options['category'],
			array( 'context' => $options['context'] )
		);

		return array(
			'success'           => false,
			'message'           => 'Validation failed. Please correct the errors and try again.',
			'validation_errors' => $validation_errors,
			'category'          => $options['category'],
		);
	}

	/**
	 * Register error callback for specific scenarios.
	 *
	 * @param string   $category Category of error.
	 * @param callable $callback Callback function.
	 */
	public function register_error_callback( $category, $callback ) {
		if ( ! isset( $this->error_callbacks[ $category ] ) ) {
			$this->error_callbacks[ $category ] = array();
		}
		$this->error_callbacks[ $category ][] = $callback;
	}

	/**
	 * Execute error callbacks for category.
	 *
	 * @param \Exception $exception Error exception.
	 * @param string     $category  Error category.
	 * @param int        $attempt   Attempt number.
	 */
	private function execute_error_callback( $exception, $category, $attempt ) {
		if ( isset( $this->error_callbacks[ $category ] ) ) {
			foreach ( $this->error_callbacks[ $category ] as $callback ) {
				if ( is_callable( $callback ) ) {
					try {
						call_user_func( $callback, $exception, $category, $attempt );
					} catch ( \Exception $e ) {
						// Log callback errors but don't let them interfere.
						$this->logger->log(
							'Error callback failed: ' . $e->getMessage(),
							self::SEVERITY_WARNING,
							self::CATEGORY_GENERAL
						);
					}
				}
			}
		}
	}

	/**
	 * Register default error callbacks.
	 */
	private function register_default_callbacks() {
		// API error callback.
		$this->register_error_callback(
			self::CATEGORY_API,
			function ( $exception, $category, $attempt ) {
				// Clear any cached API responses on API errors.
				wp_cache_delete( 'wcd_api_response', 'webchangedetector' );
			}
		);

		// Critical error callback.
		$this->register_error_callback(
			self::SEVERITY_CRITICAL,
			function ( $exception, $category, $attempt ) {
				// Send admin notification for critical errors.
				$this->send_admin_notification( $exception, $category );
			}
		);
	}

	/**
	 * Send admin notification for critical errors.
	 *
	 * @param \Exception $exception Error exception.
	 * @param string     $category  Error category.
	 */
	private function send_admin_notification( $exception, $category ) {
		// Prevent spam by checking if we've already sent notification recently.
		$notification_key = 'wcd_error_notification_' . md5( $exception->getMessage() );
		$last_sent        = get_transient( $notification_key );

		if ( $last_sent ) {
			return; // Already sent notification for this error recently.
		}

		// Set transient to prevent spam (1 hour).
		set_transient( $notification_key, time(), HOUR_IN_SECONDS );

		$subject  = 'WebChangeDetector Critical Error on ' . get_bloginfo( 'name' );
		$message  = "A critical error occurred in WebChangeDetector:\n\n";
		$message .= 'Error: ' . $exception->getMessage() . "\n";
		$message .= 'Category: ' . $category . "\n";
		$message .= 'Site: ' . get_site_url() . "\n";
		$message .= 'Time: ' . current_time( 'mysql' ) . "\n\n";
		$message .= 'Please check the plugin logs for more details.';

		wp_mail( get_option( 'admin_email' ), $subject, $message );
	}

	/**
	 * Create user-friendly error message.
	 *
	 * @param \Exception $exception Error exception.
	 * @param string     $category  Error category.
	 * @return string User-friendly error message.
	 */
	public function create_user_friendly_message( $exception, $category ) {
		$messages = array(
			self::CATEGORY_API        => 'Unable to connect to WebChangeDetector service. Please check your internet connection and try again.',
			self::CATEGORY_FILESYSTEM => 'File system error occurred. Please check file permissions.',
			self::CATEGORY_NETWORK    => 'Network error occurred. Please check your connection and try again.',
			self::CATEGORY_VALIDATION => 'Please check your input and try again.',
			self::CATEGORY_AUTH       => 'Authentication failed. Please check your API credentials.',
			self::CATEGORY_PERMISSION => 'You do not have permission to perform this action.',
		);

		return isset( $messages[ $category ] ) ? $messages[ $category ] : 'An unexpected error occurred. Please try again.';
	}

	/**
	 * Clear old error logs.
	 *
	 * @param int $days Number of days to keep logs.
	 */
	public function cleanup_old_logs( $days = 30 ) {
		$this->logger->cleanup_old_logs( $days );
	}
}
