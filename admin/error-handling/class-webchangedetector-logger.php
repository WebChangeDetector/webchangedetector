<?php
/**
 * WebChangeDetector Logger
 *
 * Advanced logging system with multiple outputs and error tracking.
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
 * WebChangeDetector Logger Class
 *
 * Provides advanced logging capabilities with multiple output formats.
 */
class WebChangeDetector_Logger {

	/**
	 * Log levels with numeric values for filtering.
	 */
	const LEVELS = array(
		'debug'    => 100,
		'info'     => 200,
		'warning'  => 300,
		'error'    => 400,
		'critical' => 500,
	);

	/**
	 * Maximum log file size in bytes (10MB).
	 *
	 * @var int
	 */
	private $max_file_size = 10485760;

	/**
	 * Log directory path.
	 *
	 * @var string
	 */
	private $log_dir;

	/**
	 * Current log level threshold.
	 *
	 * @var string
	 */
	private $log_level = 'info';

	/**
	 * Enable/disable database logging.
	 *
	 * @var bool
	 */
	private $db_logging = true;

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->log_dir = WP_CONTENT_DIR . '/webchangedetector-logs';
		$this->ensure_log_directory();
		$this->setup_log_level();
		$this->maybe_create_log_table();
	}

	/**
	 * Log a message with specified level and category.
	 *
	 * @param string $message  Log message.
	 * @param string $level    Log level.
	 * @param string $category Log category.
	 * @param array  $context  Additional context.
	 */
	public function log( $message, $level = 'info', $category = 'general', $context = array() ) {
		// Check if level meets threshold.
		if ( ! $this->should_log( $level ) ) {
			return;
		}

		$log_entry = $this->create_log_entry( $message, $level, $category, $context );

		// Log to file.
		$this->log_to_file( $log_entry );

		// Log to database if enabled.
		if ( $this->db_logging ) {
			$this->log_to_database( $log_entry );
		}

		// Log to WordPress debug.log if debug is enabled.
		if ( $level === 'error' || $level === 'critical' ) {
			$this->log_to_wp_debug( $log_entry );
		}

		// Trigger action for external logging systems.
		do_action( 'webchangedetector_log', $log_entry );
	}

	/**
	 * Log debug message.
	 *
	 * @param string $message  Log message.
	 * @param string $category Log category.
	 * @param array  $context  Additional context.
	 */
	public function debug( $message, $category = 'general', $context = array() ) {
		$this->log( $message, 'debug', $category, $context );
	}

	/**
	 * Log info message.
	 *
	 * @param string $message  Log message.
	 * @param string $category Log category.
	 * @param array  $context  Additional context.
	 */
	public function info( $message, $category = 'general', $context = array() ) {
		$this->log( $message, 'info', $category, $context );
	}

	/**
	 * Log warning message.
	 *
	 * @param string $message  Log message.
	 * @param string $category Log category.
	 * @param array  $context  Additional context.
	 */
	public function warning( $message, $category = 'general', $context = array() ) {
		$this->log( $message, 'warning', $category, $context );
	}

	/**
	 * Log error message.
	 *
	 * @param string $message  Log message.
	 * @param string $category Log category.
	 * @param array  $context  Additional context.
	 */
	public function error( $message, $category = 'general', $context = array() ) {
		$this->log( $message, 'error', $category, $context );
	}

	/**
	 * Log critical message.
	 *
	 * @param string $message  Log message.
	 * @param string $category Log category.
	 * @param array  $context  Additional context.
	 */
	public function critical( $message, $category = 'general', $context = array() ) {
		$this->log( $message, 'critical', $category, $context );
	}

	/**
	 * Create structured log entry.
	 *
	 * @param string $message  Log message.
	 * @param string $level    Log level.
	 * @param string $category Log category.
	 * @param array  $context  Additional context.
	 * @return array Log entry array.
	 */
	private function create_log_entry( $message, $level, $category, $context ) {
		return array(
			'timestamp' => current_time( 'mysql', true ),
			'level'     => $level,
			'category'  => $category,
			'message'   => $message,
			'context'   => $context,
			'user_id'   => get_current_user_id(),
			'site_url'  => get_site_url(),
			'ip'        => $this->get_client_ip(),
			'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
		);
	}

	/**
	 * Log entry to file.
	 *
	 * @param array $log_entry Log entry data.
	 */
	private function log_to_file( $log_entry ) {
		$log_file = $this->get_log_file_path( $log_entry['level'] );
		
		// Check file size and rotate if necessary.
		if ( file_exists( $log_file ) && filesize( $log_file ) > $this->max_file_size ) {
			$this->rotate_log_file( $log_file );
		}

		$formatted_entry = $this->format_log_entry_for_file( $log_entry );
		
		// Use WordPress filesystem API for better security.
		global $wp_filesystem;
		if ( ! $wp_filesystem ) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
			WP_Filesystem();
		}

		$existing_content = $wp_filesystem->exists( $log_file ) ? $wp_filesystem->get_contents( $log_file ) : '';
		$wp_filesystem->put_contents( $log_file, $existing_content . $formatted_entry . PHP_EOL, FS_CHMOD_FILE );
	}

	/**
	 * Log entry to database.
	 *
	 * @param array $log_entry Log entry data.
	 */
	private function log_to_database( $log_entry ) {
		global $wpdb;

		$table_name = $wpdb->prefix . 'webchangedetector_logs';

		$wpdb->insert(
			$table_name,
			array(
				'timestamp' => $log_entry['timestamp'],
				'level'     => $log_entry['level'],
				'category'  => $log_entry['category'],
				'message'   => $log_entry['message'],
				'context'   => wp_json_encode( $log_entry['context'] ),
				'user_id'   => $log_entry['user_id'],
				'site_url'  => $log_entry['site_url'],
				'ip'        => $log_entry['ip'],
				'user_agent' => $log_entry['user_agent'],
			),
			array( '%s', '%s', '%s', '%s', '%s', '%d', '%s', '%s', '%s' )
		);
	}

	/**
	 * Log to WordPress debug.log.
	 *
	 * @param array $log_entry Log entry data.
	 */
	private function log_to_wp_debug( $log_entry ) {
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			$message = sprintf(
				'[WebChangeDetector][%s][%s] %s',
				strtoupper( $log_entry['level'] ),
				$log_entry['category'],
				$log_entry['message']
			);
			error_log( $message );
		}
	}

	/**
	 * Format log entry for file output.
	 *
	 * @param array $log_entry Log entry data.
	 * @return string Formatted log entry.
	 */
	private function format_log_entry_for_file( $log_entry ) {
		$formatted = sprintf(
			'[%s] [%s] [%s] %s',
			$log_entry['timestamp'],
			strtoupper( $log_entry['level'] ),
			$log_entry['category'],
			$log_entry['message']
		);

		// Add context if present.
		if ( ! empty( $log_entry['context'] ) ) {
			$formatted .= ' | Context: ' . wp_json_encode( $log_entry['context'] );
		}

		return $formatted;
	}

	/**
	 * Get log file path for specific level.
	 *
	 * @param string $level Log level.
	 * @return string Log file path.
	 */
	private function get_log_file_path( $level ) {
		return $this->log_dir . '/webchangedetector-' . $level . '.log';
	}

	/**
	 * Rotate log file when it gets too large.
	 *
	 * @param string $log_file Log file path.
	 */
	private function rotate_log_file( $log_file ) {
		$backup_file = $log_file . '.old';
		
		global $wp_filesystem;
		if ( ! $wp_filesystem ) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
			WP_Filesystem();
		}

		// Remove old backup if exists.
		if ( $wp_filesystem->exists( $backup_file ) ) {
			$wp_filesystem->delete( $backup_file );
		}

		// Move current log to backup.
		$wp_filesystem->move( $log_file, $backup_file );
	}

	/**
	 * Check if message should be logged based on level.
	 *
	 * @param string $level Message level.
	 * @return bool Whether to log the message.
	 */
	private function should_log( $level ) {
		$current_level_value = self::LEVELS[ $this->log_level ] ?? 200;
		$message_level_value = self::LEVELS[ $level ] ?? 200;
		
		return $message_level_value >= $current_level_value;
	}

	/**
	 * Ensure log directory exists.
	 */
	private function ensure_log_directory() {
		if ( ! is_dir( $this->log_dir ) ) {
			wp_mkdir_p( $this->log_dir );
			
			// Create .htaccess to prevent direct access.
			$htaccess_content = "Order deny,allow\nDeny from all\n";
			file_put_contents( $this->log_dir . '/.htaccess', $htaccess_content );
			
			// Create index.php for additional security.
			file_put_contents( $this->log_dir . '/index.php', '<?php // Silence is golden.' );
		}
	}

	/**
	 * Setup log level from WordPress configuration.
	 */
	private function setup_log_level() {
		$this->log_level = get_option( 'webchangedetector_log_level', 'info' );
		
		// Use debug level if WP_DEBUG is enabled.
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			$this->log_level = 'debug';
		}
	}

	/**
	 * Create database table for logs if it doesn't exist.
	 */
	private function maybe_create_log_table() {
		global $wpdb;

		$table_name = $wpdb->prefix . 'webchangedetector_logs';
		
		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE $table_name (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			timestamp datetime NOT NULL,
			level varchar(20) NOT NULL,
			category varchar(50) NOT NULL,
			message text NOT NULL,
			context longtext,
			user_id bigint(20) unsigned,
			site_url varchar(255),
			ip varchar(45),
			user_agent text,
			PRIMARY KEY (id),
			KEY level (level),
			KEY category (category),
			KEY timestamp (timestamp)
		) $charset_collate;";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );
	}

	/**
	 * Get client IP address.
	 *
	 * @return string Client IP address.
	 */
	private function get_client_ip() {
		$ip_keys = array( 'HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP', 'HTTP_CLIENT_IP', 'REMOTE_ADDR' );
		
		foreach ( $ip_keys as $key ) {
			if ( array_key_exists( $key, $_SERVER ) === true ) {
				$ip = $_SERVER[ $key ];
				if ( strpos( $ip, ',' ) !== false ) {
					$ip = explode( ',', $ip )[0];
				}
				$ip = trim( $ip );
				if ( filter_var( $ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE ) ) {
					return $ip;
				}
			}
		}
		
		return $_SERVER['REMOTE_ADDR'] ?? 'unknown';
	}

	/**
	 * Get error statistics for dashboard.
	 *
	 * @param string $timeframe Timeframe (hour, day, week, month).
	 * @return array Statistics array.
	 */
	public function get_statistics( $timeframe = 'day' ) {
		global $wpdb;

		$table_name = $wpdb->prefix . 'webchangedetector_logs';
		
		$intervals = array(
			'hour'  => '-1 hour',
			'day'   => '-1 day',
			'week'  => '-1 week',
			'month' => '-1 month',
		);

		$interval = $intervals[ $timeframe ] ?? '-1 day';
		$since = gmdate( 'Y-m-d H:i:s', strtotime( $interval ) );

		$stats = $wpdb->get_results( $wpdb->prepare(
			"SELECT level, COUNT(*) as count 
			FROM $table_name 
			WHERE timestamp >= %s 
			GROUP BY level 
			ORDER BY count DESC",
			$since
		), ARRAY_A );

		$formatted_stats = array();
		foreach ( $stats as $stat ) {
			$formatted_stats[ $stat['level'] ] = (int) $stat['count'];
		}

		return $formatted_stats;
	}

	/**
	 * Get recent log entries.
	 *
	 * @param int    $limit     Number of entries to retrieve.
	 * @param string $level     Filter by log level.
	 * @param string $category  Filter by category.
	 * @return array Log entries.
	 */
	public function get_recent_logs( $limit = 50, $level = '', $category = '' ) {
		global $wpdb;

		$table_name = $wpdb->prefix . 'webchangedetector_logs';
		
		$where_conditions = array();
		$placeholders = array();

		if ( ! empty( $level ) ) {
			$where_conditions[] = 'level = %s';
			$placeholders[] = $level;
		}

		if ( ! empty( $category ) ) {
			$where_conditions[] = 'category = %s';
			$placeholders[] = $category;
		}

		$where_clause = ! empty( $where_conditions ) ? 'WHERE ' . implode( ' AND ', $where_conditions ) : '';
		$placeholders[] = (int) $limit;

		$query = "SELECT * FROM $table_name $where_clause ORDER BY timestamp DESC LIMIT %d";

		return $wpdb->get_results( $wpdb->prepare( $query, $placeholders ), ARRAY_A );
	}

	/**
	 * Clean up old log entries.
	 *
	 * @param int $days Number of days to keep.
	 */
	public function cleanup_old_logs( $days = 30 ) {
		global $wpdb;

		$table_name = $wpdb->prefix . 'webchangedetector_logs';
		$cutoff_date = gmdate( 'Y-m-d H:i:s', strtotime( "-{$days} days" ) );

		$wpdb->delete(
			$table_name,
			array( 'timestamp' => $cutoff_date ),
			array( '%s' )
		);

		// Clean up old log files.
		$this->cleanup_old_log_files( $days );
	}

	/**
	 * Clean up old log files.
	 *
	 * @param int $days Number of days to keep.
	 */
	private function cleanup_old_log_files( $days ) {
		$files = glob( $this->log_dir . '/*.log.old' );
		$cutoff_time = time() - ( $days * DAY_IN_SECONDS );

		foreach ( $files as $file ) {
			if ( filemtime( $file ) < $cutoff_time ) {
				unlink( $file );
			}
		}
	}
} 