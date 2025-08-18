<?php
/**
 * WebChangeDetector Exception Classes
 *
 * Custom exception classes for different error scenarios.
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
 * Base WebChangeDetector Exception
 */
class WebChangeDetector_Exception extends \Exception {

	/**
	 * Error category.
	 *
	 * @var string
	 */
	protected $category = 'general';

	/**
	 * User-friendly message.
	 *
	 * @var string
	 */
	protected $user_message = 'An error occurred. Please try again.';

	/**
	 * Additional context data.
	 *
	 * @var array
	 */
	protected $context = array();

	/**
	 * Constructor.
	 *
	 * @param string     $message      Technical error message.
	 * @param string     $user_message User-friendly message.
	 * @param array      $context      Additional context.
	 * @param int        $code         Error code.
	 * @param \Throwable $previous     Previous exception.
	 */
	public function __construct( $message = '', $user_message = '', $context = array(), $code = 0, \Throwable $previous = null ) {
		parent::__construct( $message, $code, $previous );

		if ( ! empty( $user_message ) ) {
			$this->user_message = $user_message;
		}

		$this->context = $context;
	}

	/**
	 * Get error category.
	 *
	 * @return string Error category.
	 */
	public function get_category() {
		return $this->category;
	}

	/**
	 * Get user-friendly message.
	 *
	 * @return string User-friendly message.
	 */
	public function get_user_message() {
		return $this->user_message;
	}

	/**
	 * Get additional context.
	 *
	 * @return array Context data.
	 */
	public function get_context() {
		return $this->context;
	}

	/**
	 * Set additional context.
	 *
	 * @param array $context Context data.
	 */
	public function set_context( $context ) {
		$this->context = $context;
	}
}

/**
 * API Exception for API-related errors.
 */
class WebChangeDetector_API_Exception extends WebChangeDetector_Exception {

	protected $category     = 'api';
	protected $user_message = 'Failed to communicate with WebChangeDetector service. Please check your connection and try again.';

	/**
	 * HTTP response code.
	 *
	 * @var int
	 */
	protected $http_code = 0;

	/**
	 * API response data.
	 *
	 * @var array
	 */
	protected $response_data = array();

	/**
	 * Constructor.
	 *
	 * @param string     $message       Technical error message.
	 * @param int        $http_code     HTTP response code.
	 * @param array      $response_data API response data.
	 * @param string     $user_message  User-friendly message.
	 * @param \Throwable $previous      Previous exception.
	 */
	public function __construct( $message = '', $http_code = 0, $response_data = array(), $user_message = '', \Throwable $previous = null ) {
		$this->http_code     = $http_code;
		$this->response_data = $response_data;

		$context = array(
			'http_code'     => $http_code,
			'response_data' => $response_data,
		);

		parent::__construct( $message, $user_message, $context, $http_code, $previous );
	}

	/**
	 * Get HTTP response code.
	 *
	 * @return int HTTP code.
	 */
	public function get_http_code() {
		return $this->http_code;
	}

	/**
	 * Get API response data.
	 *
	 * @return array Response data.
	 */
	public function get_response_data() {
		return $this->response_data;
	}
}


/**
 * Validation Exception for validation errors.
 */
class WebChangeDetector_Validation_Exception extends WebChangeDetector_Exception {

	protected $category     = 'validation';
	protected $user_message = 'Please correct the validation errors and try again.';

	/**
	 * Validation errors array.
	 *
	 * @var array
	 */
	protected $validation_errors = array();

	/**
	 * Constructor.
	 *
	 * @param array      $validation_errors Validation errors.
	 * @param string     $message           Technical error message.
	 * @param string     $user_message      User-friendly message.
	 * @param \Throwable $previous          Previous exception.
	 */
	public function __construct( $validation_errors = array(), $message = '', $user_message = '', \Throwable $previous = null ) {
		$this->validation_errors = $validation_errors;

		if ( empty( $message ) ) {
			$message = 'Validation failed: ' . wp_json_encode( $validation_errors );
		}

		$context = array(
			'validation_errors' => $validation_errors,
		);

		parent::__construct( $message, $user_message, $context, 0, $previous );
	}

	/**
	 * Get validation errors.
	 *
	 * @return array Validation errors.
	 */
	public function get_validation_errors() {
		return $this->validation_errors;
	}

	/**
	 * Add validation error.
	 *
	 * @param string $field Field name.
	 * @param string $error Error message.
	 */
	public function add_validation_error( $field, $error ) {
		$this->validation_errors[ $field ]  = $error;
		$this->context['validation_errors'] = $this->validation_errors;
	}
}

/**
 * Authentication Exception for authentication errors.
 */
class WebChangeDetector_Authentication_Exception extends WebChangeDetector_Exception {

	protected $category     = 'authentication';
	protected $user_message = 'Authentication failed. Please check your API credentials.';
}

/**
 * Permission Exception for permission errors.
 */
class WebChangeDetector_Permission_Exception extends WebChangeDetector_Exception {

	protected $category     = 'permission';
	protected $user_message = 'You do not have permission to perform this action.';

	/**
	 * Required capability.
	 *
	 * @var string
	 */
	protected $required_capability = '';

	/**
	 * Constructor.
	 *
	 * @param string     $required_capability Required capability.
	 * @param string     $message             Technical error message.
	 * @param string     $user_message        User-friendly message.
	 * @param \Throwable $previous            Previous exception.
	 */
	public function __construct( $required_capability = '', $message = '', $user_message = '', \Throwable $previous = null ) {
		$this->required_capability = $required_capability;

		if ( empty( $message ) ) {
			$message = "Permission denied. Required capability: {$required_capability}";
		}

		$context = array(
			'required_capability' => $required_capability,
		);

		parent::__construct( $message, $user_message, $context, 0, $previous );
	}

	/**
	 * Get required capability.
	 *
	 * @return string Required capability.
	 */
	public function get_required_capability() {
		return $this->required_capability;
	}
}

/**
 * Filesystem Exception for file system errors.
 */
class WebChangeDetector_Filesystem_Exception extends WebChangeDetector_Exception {

	protected $category     = 'filesystem';
	protected $user_message = 'File system error occurred. Please check file permissions.';

	/**
	 * File path that caused the error.
	 *
	 * @var string
	 */
	protected $file_path = '';

	/**
	 * File operation that failed.
	 *
	 * @var string
	 */
	protected $operation = '';

	/**
	 * Constructor.
	 *
	 * @param string     $file_path    File path.
	 * @param string     $operation    File operation.
	 * @param string     $message      Technical error message.
	 * @param string     $user_message User-friendly message.
	 * @param \Throwable $previous     Previous exception.
	 */
	public function __construct( $file_path = '', $operation = '', $message = '', $user_message = '', \Throwable $previous = null ) {
		$this->file_path = $file_path;
		$this->operation = $operation;

		if ( empty( $message ) ) {
			$message = "Filesystem error: {$operation} failed on {$file_path}";
		}

		$context = array(
			'file_path' => $file_path,
			'operation' => $operation,
		);

		parent::__construct( $message, $user_message, $context, 0, $previous );
	}

	/**
	 * Get file path.
	 *
	 * @return string File path.
	 */
	public function get_file_path() {
		return $this->file_path;
	}

	/**
	 * Get operation.
	 *
	 * @return string Operation.
	 */
	public function get_operation() {
		return $this->operation;
	}
}

/**
 * Network Exception for network-related errors.
 */
class WebChangeDetector_Network_Exception extends WebChangeDetector_Exception {

	protected $category     = 'network';
	protected $user_message = 'Network error occurred. Please check your connection and try again.';

	/**
	 * Request URL that failed.
	 *
	 * @var string
	 */
	protected $request_url = '';

	/**
	 * Request method.
	 *
	 * @var string
	 */
	protected $request_method = '';

	/**
	 * Constructor.
	 *
	 * @param string     $request_url    Request URL.
	 * @param string     $request_method Request method.
	 * @param string     $message        Technical error message.
	 * @param string     $user_message   User-friendly message.
	 * @param \Throwable $previous       Previous exception.
	 */
	public function __construct( $request_url = '', $request_method = '', $message = '', $user_message = '', \Throwable $previous = null ) {
		$this->request_url    = $request_url;
		$this->request_method = $request_method;

		if ( empty( $message ) ) {
			$message = "Network error: {$request_method} request to {$request_url} failed";
		}

		$context = array(
			'request_url'    => $request_url,
			'request_method' => $request_method,
		);

		parent::__construct( $message, $user_message, $context, 0, $previous );
	}

	/**
	 * Get request URL.
	 *
	 * @return string Request URL.
	 */
	public function get_request_url() {
		return $this->request_url;
	}

	/**
	 * Get request method.
	 *
	 * @return string Request method.
	 */
	public function get_request_method() {
		return $this->request_method;
	}
}
