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
	 * The database logger instance.
	 *
	 * @var WebChangeDetector_Database_Logger
	 */
	private $database_logger;

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->debug_enabled   = get_option( WCD_WP_OPTION_KEY_DEBUG_LOGGING, false );
		$this->database_logger = new \WebChangeDetector\WebChangeDetector_Database_Logger();
	}

	/**
	 * Main logging function using database logging.
	 *
	 * @param string $message  Log message.
	 * @param string $context  Log context/category.
	 * @param string $level    Log level.
	 * @return bool True on success, false on failure.
	 */
	public function log( $message, $context = 'general', $level = 'info' ) {
		// Use the database logger for all logging.
		return $this->database_logger->log( $message, $context, $level );
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
	 * Update debug logging status.
	 *
	 * @param bool $enabled Whether to enable debug logging.
	 */
	public function set_debug_enabled( $enabled ) {
		$this->debug_enabled = (bool) $enabled;
		$this->database_logger->set_debug_enabled( $enabled );
	}
}
