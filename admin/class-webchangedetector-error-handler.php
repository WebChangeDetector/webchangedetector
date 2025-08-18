<?php
/**
 * WebChangeDetector Error Handler - Simplified
 *
 * Simplified error handling using WordPress patterns.
 *
 * @package    WebChangeDetector
 * @subpackage WebChangeDetector/admin
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WebChangeDetector Error Handler Class
 *
 * Provides simplified error handling, logging, and user feedback using WordPress patterns.
 */
class WebChangeDetector_Error_Handler {

	/**
	 * Error categories.
	 */
	const CATEGORY_API        = 'api';
	const CATEGORY_VALIDATION = 'validation';
	const CATEGORY_GENERAL    = 'general';

	/**
	 * Log levels.
	 */
	const LEVEL_DEBUG    = 'debug';
	const LEVEL_INFO     = 'info';
	const LEVEL_WARNING  = 'warning';
	const LEVEL_ERROR    = 'error';
	const LEVEL_CRITICAL = 'critical';

	/**
	 * Whether debug logging is enabled.
	 *
	 * @var bool
	 */
	private $debug_enabled;

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->debug_enabled = get_option( WCD_WP_OPTION_KEY_DEBUG_LOGGING, false );
	}

	/**
	 * Execute operation with error handling and retry logic.
	 *
	 * @param callable $operation Operation to execute.
	 * @param array    $args     Arguments for the operation.
	 * @param array    $options  Error handling options.
	 * @return array|WP_Error Result array or WP_Error on failure.
	 */
	public function execute_with_error_handling( $operation, $args = array(), $options = array() ) {
		$options = wp_parse_args(
			$options,
			array(
				'retries'         => 3,
				'category'        => self::CATEGORY_GENERAL,
				'context'         => '',
				'user_message'    => 'An error occurred. Please try again.',
				'log_errors'      => true,
				'return_wp_error' => false,
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
					$this->info( "Operation succeeded after {$attempts} retries", $options['category'] );
				}

				return array(
					'success' => true,
					'data'    => $result,
					'message' => 'Operation completed successfully.',
					'retries' => $attempts,
				);

			} catch ( Exception $e ) {
				++$attempts;
				$last_error = $e;

				// Log the error.
				if ( $options['log_errors'] ) {
					$level = $attempts > $options['retries'] ? self::LEVEL_ERROR : self::LEVEL_WARNING;
					$this->log( "Operation failed (attempt {$attempts}): " . $e->getMessage(), $options['category'], $level );
				}

				// If we've exhausted retries, break.
				if ( $attempts > $options['retries'] ) {
					break;
				}

				// Simple backoff for retries (1, 2, 3 seconds).
				if ( $attempts < $options['retries'] ) {
					sleep( $attempts );
				}
			}
		}

		// Handle final failure.
		$error_message = $last_error ? $last_error->getMessage() : 'Unknown error occurred';

		if ( $options['return_wp_error'] ) {
			return new WP_Error(
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
	 * @param callable $api_call API call function.
	 * @param array    $args     Arguments for API call.
	 * @param array    $options  Options for error handling.
	 * @return array|WP_Error Result array or WP_Error.
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

		$result = $this->execute_with_error_handling( $api_call, $args, $options );

		// Clear API cache on errors.
		if ( is_wp_error( $result ) || ( is_array( $result ) && ! $result['success'] ) ) {
			wp_cache_delete( 'wcd_api_response', 'webchangedetector' );
			delete_transient( 'webchangedetector_api_status' );
		}

		return $result;
	}

	/**
	 * Main logging function using WordPress debug logging.
	 *
	 * @param string $message  Log message.
	 * @param string $context  Log context/category.
	 * @param string $level    Log level.
	 * @return bool True on success, false on failure.
	 */
	public function log( $message, $context = 'general', $level = 'info' ) {
		// Check if debug logging is enabled (always log errors and critical).
		if ( ! $this->debug_enabled && ! in_array( $level, array( 'error', 'critical' ), true ) ) {
			return false;
		}

		// Format log entry.
		$log_entry = sprintf(
			'[WCD] [%s] [%s] %s',
			strtoupper( $level ),
			$context,
			$message
		);

		// Use WordPress error logging.
		return error_log( $log_entry );
	}

	/**
	 * Log error message (always logs regardless of debug setting).
	 *
	 * @param string $message Log message.
	 * @param string $context Log context/category.
	 * @return bool True on success, false on failure.
	 */
	public function error( $message, $context = 'general' ) {
		return $this->log( $message, $context, self::LEVEL_ERROR );
	}

	/**
	 * Log debug message.
	 *
	 * @param string $message Log message.
	 * @param string $context Log context/category.
	 * @return bool True on success, false on failure.
	 */
	public function debug( $message, $context = 'general' ) {
		return $this->log( $message, $context, self::LEVEL_DEBUG );
	}

	/**
	 * Log info message.
	 *
	 * @param string $message Log message.
	 * @param string $context Log context/category.
	 * @return bool True on success, false on failure.
	 */
	public function info( $message, $context = 'general' ) {
		return $this->log( $message, $context, self::LEVEL_INFO );
	}

	/**
	 * Log warning message.
	 *
	 * @param string $message Log message.
	 * @param string $context Log context/category.
	 * @return bool True on success, false on failure.
	 */
	public function warning( $message, $context = 'general' ) {
		return $this->log( $message, $context, self::LEVEL_WARNING );
	}

	/**
	 * Log critical message.
	 *
	 * @param string $message Log message.
	 * @param string $context Log context/category.
	 * @return bool True on success, false on failure.
	 */
	public function critical( $message, $context = 'general' ) {
		return $this->log( $message, $context, self::LEVEL_CRITICAL );
	}

	/**
	 * Create user-friendly error message based on category.
	 *
	 * @param string $category Error category.
	 * @param string $error    Technical error message.
	 * @return string User-friendly error message.
	 */
	public function get_user_friendly_message( $category, $error = '' ) {
		$messages = array(
			self::CATEGORY_API        => 'Unable to connect to WebChangeDetector service. Please check your internet connection and try again.',
			self::CATEGORY_VALIDATION => 'Please check your input and try again.',
			self::CATEGORY_GENERAL    => 'An unexpected error occurred. Please try again.',
		);

		return isset( $messages[ $category ] ) ? $messages[ $category ] : $messages[ self::CATEGORY_GENERAL ];
	}

	/**
	 * Get current debug logging status.
	 *
	 * @return bool Whether debug logging is enabled.
	 */
	public function is_debug_enabled() {
		return $this->debug_enabled;
	}

	/**
	 * Update debug logging status.
	 *
	 * @param bool $enabled Whether to enable debug logging.
	 */
	public function set_debug_enabled( $enabled ) {
		$this->debug_enabled = (bool) $enabled;
		update_option( WCD_WP_OPTION_KEY_DEBUG_LOGGING, $this->debug_enabled );
	}
}
