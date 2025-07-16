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
	 * Health check intervals in seconds.
	 *
	 * @var array
	 */
	private $health_check_intervals = array(
		'api'        => 300,  // 5 minutes.
		'database'   => 600,  // 10 minutes.
		'filesystem' => 1800, // 30 minutes.
	);

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
		$this->schedule_health_checks();
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
						$recovery_result = array_merge( $recovery_result, $result );
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
	 * Perform system health check.
	 *
	 * @return array Health check results.
	 */
	public function perform_health_check() {
		$health_results = array(
			'overall_status' => 'healthy',
			'checks'         => array(),
			'timestamp'      => current_time( 'mysql' ),
		);

		// API connectivity check.
		$api_check = $this->check_api_connectivity();
		$health_results['checks']['api'] = $api_check;

		// Database connectivity check.
		$db_check = $this->check_database_connectivity();
		$health_results['checks']['database'] = $db_check;

		// Filesystem permissions check.
		$fs_check = $this->check_filesystem_permissions();
		$health_results['checks']['filesystem'] = $fs_check;

		// Plugin configuration check.
		$config_check = $this->check_plugin_configuration();
		$health_results['checks']['configuration'] = $config_check;

		// Determine overall status.
		$failed_checks = array_filter( $health_results['checks'], function( $check ) {
			return ! $check['status'];
		} );

		if ( ! empty( $failed_checks ) ) {
			$health_results['overall_status'] = 'unhealthy';
		}

		// Log health check results.
		$this->logger->info(
			"Health check completed: {$health_results['overall_status']}",
			'health_check',
			$health_results
		);

		// Store health check results.
		update_option( WCD_WP_OPTION_KEY_HEALTH_STATUS, $health_results );

		return $health_results;
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
		usort( $this->recovery_strategies[ $category ], function( $a, $b ) {
			return $a['priority'] - $b['priority'];
		} );
	}

	/**
	 * Register default recovery strategies.
	 */
	private function register_default_recovery_strategies() {
		// API error recovery strategies.
		$this->register_recovery_strategy( 'api', 'Clear API Cache', function( $exception ) {
			wp_cache_delete( 'wcd_api_response', 'webchangedetector' );
			delete_transient( 'webchangedetector_api_status' );
			
			return array(
				'success' => true,
				'message' => 'API cache cleared.',
			);
		}, 1 );

		$this->register_recovery_strategy( 'api', 'Reset API Token', function( $exception ) {
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
		}, 5 );

		// Database error recovery strategies.
		$this->register_recovery_strategy( 'database', 'Recreate Database Tables', function( $exception ) {
			// Attempt to recreate plugin tables.
			// Creating a new logger instance will automatically create the log table via constructor.
			$logger = new WebChangeDetector_Logger();
			
			return array(
				'success' => true,
				'message' => 'Database tables recreated.',
			);
		}, 1 );

		// Filesystem error recovery strategies.
		$this->register_recovery_strategy( 'filesystem', 'Fix Directory Permissions', function( $exception ) {
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
		}, 1 );

		// General error recovery strategies.
		$this->register_recovery_strategy( 'general', 'Clear All Caches', function( $exception ) {
			// Clear WordPress object cache.
			wp_cache_flush();
			
			// Clear plugin-specific caches.
			delete_transient( 'webchangedetector_api_status' );
			delete_transient( 'webchangedetector_account_details' );
			
			return array(
				'success' => true,
				'message' => 'All caches cleared.',
			);
		}, 10 );
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
		
		if ( $exception instanceof WebChangeDetector_Database_Exception ) {
			return 'database';
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
	 * Check API connectivity.
	 *
	 * @return array Check result.
	 */
	private function check_api_connectivity() {
		try {
			// Simple API health check using test_connection method.
			$api_manager = new WebChangeDetector_API_Manager();
			$result = $api_manager->test_connection();
			
			if ( is_wp_error( $result ) ) {
				return array(
					'status'  => false,
					'message' => 'API connectivity failed: ' . $result->get_error_message(),
					'details' => array( 'error' => $result->get_error_message() ),
				);
			}
			
			return array(
				'status'  => true,
				'message' => 'API connectivity OK',
			);
		} catch ( \Exception $e ) {
			return array(
				'status'  => false,
				'message' => 'API connectivity failed: ' . $e->getMessage(),
				'details' => array( 'error' => $e->getMessage() ),
			);
		}
	}

	/**
	 * Check database connectivity.
	 *
	 * @return array Check result.
	 */
	private function check_database_connectivity() {
		global $wpdb;

		try {
			$result = $wpdb->get_var( 'SELECT 1' );
			
			if ( $result == 1 ) {
				return array(
					'status'  => true,
					'message' => 'Database connectivity OK',
				);
			} else {
				return array(
					'status'  => false,
					'message' => 'Database query failed',
				);
			}
		} catch ( \Exception $e ) {
			return array(
				'status'  => false,
				'message' => 'Database connectivity failed: ' . $e->getMessage(),
				'details' => array( 'error' => $e->getMessage() ),
			);
		}
	}

	/**
	 * Check filesystem permissions.
	 *
	 * @return array Check result.
	 */
	private function check_filesystem_permissions() {
		$upload_dir = wp_upload_dir();
		$log_dir = WP_CONTENT_DIR . '/webchangedetector-logs';
		
		$issues = array();
		
		// Check if upload directory is writable.
		if ( ! is_writable( $upload_dir['basedir'] ) ) {
			$issues[] = 'Upload directory not writable: ' . $upload_dir['basedir'];
		}
		
		// Check if log directory exists and is writable.
		if ( ! is_dir( $log_dir ) ) {
			$issues[] = 'Log directory does not exist: ' . $log_dir;
		} elseif ( ! is_writable( $log_dir ) ) {
			$issues[] = 'Log directory not writable: ' . $log_dir;
		}
		
		if ( empty( $issues ) ) {
			return array(
				'status'  => true,
				'message' => 'Filesystem permissions OK',
			);
		} else {
			return array(
				'status'  => false,
				'message' => 'Filesystem permission issues found',
				'details' => array( 'issues' => $issues ),
			);
		}
	}

	/**
	 * Check plugin configuration.
	 *
	 * @return array Check result.
	 */
	private function check_plugin_configuration() {
		$issues = array();
		
		// Check if API token is set.
		$api_token = get_option( WCD_WP_OPTION_KEY_API_TOKEN );
		if ( empty( $api_token ) ) {
			$issues[] = 'API token not configured';
		}
		
		// Check if website groups are set.
		$groups = get_option( WCD_WEBSITE_GROUPS );
		if ( empty( $groups ) ) {
			$issues[] = 'Website groups not configured';
		}
		
		if ( empty( $issues ) ) {
			return array(
				'status'  => true,
				'message' => 'Plugin configuration OK',
			);
		} else {
			return array(
				'status'  => false,
				'message' => 'Plugin configuration issues found',
				'details' => array( 'issues' => $issues ),
			);
		}
	}

	/**
	 * Schedule health checks.
	 */
	private function schedule_health_checks() {
		// Schedule regular health checks.
		if ( ! wp_next_scheduled( 'webchangedetector_health_check' ) ) {
			wp_schedule_event( time(), 'hourly', 'webchangedetector_health_check' );
		}
		
		add_action( 'webchangedetector_health_check', array( $this, 'perform_health_check' ) );
	}

	/**
	 * Get current health status.
	 *
	 * @return array Current health status.
	 */
	public function get_health_status() {
		$health_status = get_option( WCD_WP_OPTION_KEY_HEALTH_STATUS );
		
		if ( empty( $health_status ) ) {
			// Perform initial health check if none exists.
			$health_status = $this->perform_health_check();
		}
		
		return $health_status;
	}

	/**
	 * Force health check refresh.
	 *
	 * @return array Fresh health status.
	 */
	public function refresh_health_status() {
		delete_option( WCD_WP_OPTION_KEY_HEALTH_STATUS );
		return $this->perform_health_check();
	}
} 