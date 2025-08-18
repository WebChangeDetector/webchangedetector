<?php
/**
 * WebChangeDetector Error Recovery
 *
 * Automatic error recovery and system health monitoring.
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
 * WebChangeDetector Error Recovery Class
 *
 * Provides automatic error recovery and system health monitoring.
 */
class WebChangeDetector_Error_Recovery {

	/**
	 * Recovery strategies for different error types.
	 *
	 * @var array
	 */
	private $recovery_strategies = array();

	/**
	 * Logger instance.
	 *
	 * @var WebChangeDetector_Logger
	 */
	private $logger;

	/**
	 * Constructor.
	 *
	 * @param WebChangeDetector_Logger $logger Logger instance.
	 */
	public function __construct( $logger = null ) {
		$this->logger = $logger ?: new WebChangeDetector_Logger();
		$this->register_default_recovery_strategies();
		// Health checks are now handled by the hourly sync in WebChangeDetector_Autoupdates
	}

	/**
	 * Attempt to recover from an error.
	 *
	 * @param \Exception $exception The exception to recover from.
	 * @return array Recovery result.
	 */
	public function attempt_recovery( $exception ) {
		$recovery_result = array(
			'success'  => false,
			'message'  => 'No recovery strategy available.',
			'strategy' => null,
		);

		// Determine error category.
		$category = $this->determine_error_category( $exception );

		// Try recovery strategies for this category.
		if ( isset( $this->recovery_strategies[ $category ] ) ) {
			foreach ( $this->recovery_strategies[ $category ] as $strategy ) {
				$this->logger->info(
					"Attempting recovery strategy: {$strategy['name']}",
					'recovery',
					array( 'error' => $exception->getMessage() )
				);

				try {
					$result = call_user_func( $strategy['callback'], $exception );

					if ( $result['success'] ) {
						$recovery_result             = array_merge( $recovery_result, $result );
						$recovery_result['strategy'] = $strategy['name'];

						$this->logger->info(
							"Recovery successful using strategy: {$strategy['name']}",
							'recovery'
						);

						break; // Stop trying other strategies.
					}
				} catch ( \Exception $recovery_exception ) {
					$this->logger->warning(
						"Recovery strategy failed: {$strategy['name']} - {$recovery_exception->getMessage()}",
						'recovery'
					);
				}
			}
		}

		return $recovery_result;
	}

	/**
	 * Get current health status.
	 * Health checks are now performed via the hourly sync in WebChangeDetector_Autoupdates.
	 * This method simply retrieves the stored status.
	 *
	 * @return array Current health status.
	 */
	public function get_health_status() {
		$health_status = get_option( WCD_WP_OPTION_KEY_HEALTH_STATUS );

		if ( empty( $health_status ) ) {
			// Return a default healthy status if none exists
			$health_status = array(
				'overall_status' => 'healthy',
				'checks'         => array(
					'api'           => array(
						'status'  => true,
						'message' => 'Awaiting first sync',
					),
					'configuration' => array(
						'status'  => true,
						'message' => 'Awaiting first sync',
					),
				),
				'timestamp'      => current_time( 'mysql' ),
			);
		}

		return $health_status;
	}

	/**
	 * Register a recovery strategy.
	 *
	 * @param string   $category Category of error.
	 * @param string   $name     Strategy name.
	 * @param callable $callback Recovery callback.
	 * @param int      $priority Priority (lower = higher priority).
	 */
	public function register_recovery_strategy( $category, $name, $callback, $priority = 10 ) {
		if ( ! isset( $this->recovery_strategies[ $category ] ) ) {
			$this->recovery_strategies[ $category ] = array();
		}

		$this->recovery_strategies[ $category ][] = array(
			'name'     => $name,
			'callback' => $callback,
			'priority' => $priority,
		);

		// Sort by priority.
		usort(
			$this->recovery_strategies[ $category ],
			function ( $a, $b ) {
				return $a['priority'] - $b['priority'];
			}
		);
	}

	/**
	 * Register default recovery strategies.
	 */
	private function register_default_recovery_strategies() {
		// API error recovery strategies.
		$this->register_recovery_strategy(
			'api',
			'Clear API Cache',
			function ( $exception ) {
				wp_cache_delete( 'wcd_api_response', 'webchangedetector' );
				delete_transient( 'webchangedetector_api_status' );

				return array(
					'success' => true,
					'message' => 'API cache cleared.',
				);
			},
			1
		);

		$this->register_recovery_strategy(
			'api',
			'Reset API Token',
			function ( $exception ) {
				// Only reset if we have authentication errors.
				if ( strpos( $exception->getMessage(), 'authentication' ) !== false ||
				strpos( $exception->getMessage(), 'unauthorized' ) !== false ) {

					delete_option( WCD_WP_OPTION_KEY_API_TOKEN );
					delete_option( WCD_WP_OPTION_KEY_WEBSITE_ID );
					delete_option( WCD_WP_OPTION_KEY_HEALTH_STATUS );

					return array(
						'success' => true,
						'message' => 'API token reset. Please re-authenticate.',
					);
				}

				return array( 'success' => false );
			},
			5
		);

		// Database error recovery strategies.
		$this->register_recovery_strategy(
			'database',
			'Recreate Database Tables',
			function ( $exception ) {
				// Attempt to recreate plugin tables.
				// Creating a new logger instance will automatically create the log table via constructor.
				$logger = new WebChangeDetector_Logger();

				return array(
					'success' => true,
					'message' => 'Database tables recreated.',
				);
			},
			1
		);

		// Filesystem error recovery strategies.
		$this->register_recovery_strategy(
			'filesystem',
			'Fix Directory Permissions',
			function ( $exception ) {
				$upload_dir = wp_upload_dir();
				$plugin_dir = WP_CONTENT_DIR . '/webchangedetector-logs';

				$fixed = false;

				// Try to fix log directory permissions.
				if ( is_dir( $plugin_dir ) && ! is_writable( $plugin_dir ) ) {
					if ( chmod( $plugin_dir, 0755 ) ) {
						$fixed = true;
					}
				}

				return array(
					'success' => $fixed,
					'message' => $fixed ? 'Directory permissions fixed.' : 'Could not fix permissions.',
				);
			},
			1
		);

		// General error recovery strategies.
		$this->register_recovery_strategy(
			'general',
			'Clear All Caches',
			function ( $exception ) {
				// Clear WordPress object cache.
				wp_cache_flush();

				// Clear plugin-specific caches.
				delete_transient( 'webchangedetector_api_status' );
				delete_transient( 'webchangedetector_account_details' );

				return array(
					'success' => true,
					'message' => 'All caches cleared.',
				);
			},
			10
		);
	}

	/**
	 * Determine error category from exception.
	 *
	 * @param \Exception $exception The exception.
	 * @return string Error category.
	 */
	private function determine_error_category( $exception ) {
		if ( $exception instanceof WebChangeDetector_API_Exception ) {
			return 'api';
		}

		if ( $exception instanceof WebChangeDetector_Filesystem_Exception ) {
			return 'filesystem';
		}

		if ( $exception instanceof WebChangeDetector_Network_Exception ) {
			return 'network';
		}

		if ( $exception instanceof WebChangeDetector_Authentication_Exception ) {
			return 'authentication';
		}

		if ( $exception instanceof WebChangeDetector_Permission_Exception ) {
			return 'permission';
		}

		if ( $exception instanceof WebChangeDetector_Validation_Exception ) {
			return 'validation';
		}

		// Check message for known patterns.
		$message = strtolower( $exception->getMessage() );

		if ( strpos( $message, 'api' ) !== false || strpos( $message, 'request' ) !== false ) {
			return 'api';
		}

		if ( strpos( $message, 'database' ) !== false || strpos( $message, 'sql' ) !== false ) {
			return 'database';
		}

		if ( strpos( $message, 'file' ) !== false || strpos( $message, 'permission' ) !== false ) {
			return 'filesystem';
		}

		return 'general';
	}

	/**
	 * Force health status refresh.
	 * Triggers the sync which will update the health status.
	 *
	 * @return array Fresh health status.
	 */
	public function refresh_health_status() {
		// Trigger the sync to get fresh health status
		if ( class_exists( '\WebChangeDetector\WebChangeDetector_Autoupdates' ) ) {
			$autoupdates = new \WebChangeDetector\WebChangeDetector_Autoupdates();
			$autoupdates->sync_auto_update_schedule_from_api();
		}

		return $this->get_health_status();
	}
}
