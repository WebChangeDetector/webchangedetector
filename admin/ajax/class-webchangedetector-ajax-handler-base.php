<?php

namespace WebChangeDetector;

/**
 * Base class for AJAX handlers.
 *
 * Provides common functionality for all AJAX handlers including
 * security verification, error handling, and logging utilities.
 * Follows WordPress coding standards and security best practices.
 *
 * @link       https://www.webchangedetector.com
 * @since      4.0.0
 * @package    WebChangeDetector
 * @subpackage WebChangeDetector/admin/ajax
 */

/**
 * Base class for AJAX handlers.
 *
 * Provides common functionality for all AJAX handlers including
 * security verification, error handling, and logging utilities.
 *
 * @since      4.0.0
 * @package    WebChangeDetector
 * @subpackage WebChangeDetector/admin/ajax
 * @author     Mike Miler <mike@webchangedetector.com>
 */
abstract class WebChangeDetector_Ajax_Handler_Base {

	/**
	 * The main admin class instance.
	 *
	 * @since    4.0.0
	 * @access   protected
	 * @var      WebChangeDetector_Admin    $admin    The main admin class instance.
	 */
	protected $admin;

	/**
	 * The API manager instance.
	 *
	 * @since    4.0.0
	 * @access   protected
	 * @var      WebChangeDetector_API_Manager    $api_manager    The API manager instance.
	 */
	protected $api_manager;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    4.0.0
	 * @param    WebChangeDetector_Admin           $admin         The main admin class instance.
	 * @param    WebChangeDetector_API_Manager     $api_manager   The API manager instance.
	 */
	public function __construct( $admin, $api_manager ) {
		$this->admin = $admin;
		$this->api_manager = $api_manager;
	}

	/**
	 * Register AJAX hooks for this handler.
	 *
	 * Each handler must implement this method to register its specific hooks.
	 * This method is called during plugin initialization.
	 *
	 * @since    4.0.0
	 */
	abstract public function register_hooks();

	/**
	 * Verify AJAX nonce for security.
	 *
	 * Checks the nonce value against the expected action to prevent CSRF attacks.
	 * Follows WordPress security best practices.
	 *
	 * @since    4.0.0
	 * @param    string $action The action name for nonce verification.
	 * @return   bool True if nonce is valid, false otherwise.
	 */
	protected function verify_nonce( $action = 'ajax-nonce' ) {
		$nonce = isset( $_POST['nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['nonce'] ) ) : '';
		
		if ( empty( $nonce ) ) {
			return false;
		}

		return wp_verify_nonce( $nonce, $action );
	}

	/**
	 * Check if current user has required capability.
	 *
	 * Verifies that the current user has the required capability to perform
	 * the requested action. Defaults to 'manage_options' for admin operations.
	 *
	 * @since    4.0.0
	 * @param    string $capability The capability to check. Default 'manage_options'.
	 * @return   bool True if user has capability, false otherwise.
	 */
	protected function check_capability( $capability = 'manage_options' ) {
		return current_user_can( $capability );
	}

	/**
	 * Send standardized error response.
	 *
	 * Provides a consistent error response format across all AJAX handlers.
	 * Includes proper logging and user-friendly error messages.
	 *
	 * @since    4.0.0
	 * @param    string $message The error message to send.
	 * @param    string $context Optional context for logging.
	 * @param    int    $code    Optional error code.
	 */
	protected function send_error_response( $message, $context = '', $code = 400 ) {
		// Log the error for debugging.
		if ( $this->admin && method_exists( $this->admin, 'log_error' ) ) {
			$log_message = $context ? "{$context}: {$message}" : $message;
			$this->admin->log_error( $log_message, 'ajax' );
		}

		wp_send_json_error( 
			array( 
				'message' => $message,
				'code' => $code,
			)
		);
	}

	/**
	 * Send standardized success response.
	 *
	 * Provides a consistent success response format across all AJAX handlers.
	 * Supports both simple messages and complex data structures.
	 *
	 * @since    4.0.0
	 * @param    mixed  $data    The data to send in the response.
	 * @param    string $message Optional success message.
	 */
	protected function send_success_response( $data = null, $message = '' ) {
		$response = array();
		
		if ( ! empty( $message ) ) {
			$response['message'] = $message;
		}
		
		if ( $data !== null ) {
			$response['data'] = $data;
		}

		wp_send_json_success( $response );
	}

	/**
	 * Validate and sanitize POST data.
	 *
	 * Validates that required POST fields are present and sanitizes the data
	 * according to expected types. Follows WordPress security best practices.
	 *
	 * @since    4.0.0
	 * @param    array $required_fields Array of required field names.
	 * @param    array $sanitization_map Optional array mapping field names to sanitization functions.
	 * @return   array|false Sanitized POST data or false if validation fails.
	 */
	protected function validate_post_data( $required_fields = array(), $sanitization_map = array() ) {
		$post_data = array();
		
		// Check that all required fields are present.
		foreach ( $required_fields as $field ) {
			if ( ! isset( $_POST[ $field ] ) ) {
				return false;
			}
		}

		// Sanitize all POST data.
		foreach ( $_POST as $key => $value ) {
			if ( isset( $sanitization_map[ $key ] ) ) {
				// Use custom sanitization function if specified.
				$sanitization_func = $sanitization_map[ $key ];
				if ( is_callable( $sanitization_func ) ) {
					$post_data[ $key ] = call_user_func( $sanitization_func, $value );
				} else {
					$post_data[ $key ] = sanitize_text_field( wp_unslash( $value ) );
				}
			} else {
				// Default sanitization.
				if ( is_array( $value ) ) {
					$post_data[ $key ] = array_map( 'sanitize_text_field', wp_unslash( $value ) );
				} else {
					$post_data[ $key ] = sanitize_text_field( wp_unslash( $value ) );
				}
			}
		}

		return $post_data;
	}

	/**
	 * Perform standard AJAX security checks.
	 *
	 * Combines nonce verification and capability checking into a single method.
	 * Use this at the beginning of AJAX handlers for consistent security.
	 *
	 * @since    4.0.0
	 * @param    string $action     The nonce action to verify.
	 * @param    string $capability The capability to check.
	 * @return   bool True if all checks pass, false otherwise.
	 */
	protected function security_check( $action = 'ajax-nonce', $capability = 'manage_options' ) {
		if ( ! $this->verify_nonce( $action ) ) {
			$this->send_error_response( 
				__( 'Security check failed. Please refresh the page and try again.', 'webchangedetector' ),
				'Nonce verification failed',
				403
			);
			return false;
		}

		if ( ! $this->check_capability( $capability ) ) {
			$this->send_error_response( 
				__( 'You do not have permission to perform this action.', 'webchangedetector' ),
				'Capability check failed',
				403
			);
			return false;
		}

		return true;
	}

}