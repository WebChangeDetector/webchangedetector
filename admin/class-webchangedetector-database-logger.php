<?php
/**
 * WebChangeDetector Database Logger
 *
 * Handles database-based logging for the WebChangeDetector plugin.
 *
 * @package    WebChangeDetector
 * @subpackage WebChangeDetector/admin
 */

namespace WebChangeDetector;

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


/**
 * WebChangeDetector Database Logger Class
 *
 * Provides database-based logging with filtering, search, and export capabilities.
 */
class WebChangeDetector_Database_Logger {

	/**
	 * Log levels.
	 */
	const LEVEL_DEBUG    = 'debug';
	const LEVEL_INFO     = 'info';
	const LEVEL_WARNING  = 'warning';
	const LEVEL_ERROR    = 'error';
	const LEVEL_CRITICAL = 'critical';

	/**
	 * The WordPress database object.
	 *
	 * @var wpdb
	 */
	private $wpdb;

	/**
	 * The logs table name.
	 *
	 * @var string
	 */
	private $table_name;

	/**
	 * Whether debug logging is enabled.
	 *
	 * @var bool
	 */
	private $debug_enabled;

	/**
	 * Maximum log entries to keep.
	 *
	 * @var int
	 */
	private $max_log_entries;

	/**
	 * Constructor.
	 */
	public function __construct() {
		global $wpdb;

		$this->wpdb            = $wpdb;
		$this->table_name      = $wpdb->prefix . 'wcd_logs';
		$this->debug_enabled   = get_option( WCD_WP_OPTION_KEY_DEBUG_LOGGING, false );
		$this->max_log_entries = apply_filters( 'wcd_max_log_entries', 10000 );
	}

	/**
	 * Log a message to the database.
	 *
	 * @param string $message         The log message.
	 * @param string $context         The log context/category.
	 * @param string $level           The log level.
	 * @param array  $additional_data Additional structured data.
	 * @return bool True on success, false on failure.
	 */
	public function log( $message, $context = 'general', $level = self::LEVEL_INFO, $additional_data = null ) {
		// Check if debug logging is enabled (always log errors and critical).
		if ( ! $this->debug_enabled ) {
			return false;
		}

		// Prepare log data.
		$log_data = array(
			'timestamp'       => current_time( 'mysql', true ), // UTC time.
			'level'           => sanitize_text_field( $level ),
			'context'         => sanitize_text_field( $context ),
			'message'         => wp_kses_post( $message ),
			'user_id'         => get_current_user_id() ? get_current_user_id() : null,
			'ip_address'      => $this->get_client_ip(),
			'request_id'      => $this->get_request_id(),
			'session_id'      => $this->get_session_id(),
			'additional_data' => $additional_data ? wp_json_encode( $additional_data ) : null,
		);

		// Insert into database.
		$result = $this->wpdb->insert(
			$this->table_name,
			$log_data,
			array(
				'%s', // timestamp.
				'%s', // level.
				'%s', // context.
				'%s', // message.
				'%d', // user_id.
				'%s', // ip_address.
				'%s', // request_id.
				'%s', // session_id.
				'%s', // additional_data.
			)
		);

		return false !== $result;
	}

	/**
	 * Get logs from the database.
	 *
	 * @param array $filters Filters for the query.
	 * @return array Array of log entries with pagination info.
	 */
	public function get_logs( $filters = array() ) {
		$filters = wp_parse_args(
			$filters,
			array(
				'level'     => '',
				'context'   => '',
				'search'    => '',
				'date_from' => '',
				'date_to'   => '',
				'user_id'   => '',
				'per_page'  => 50,
				'page'      => 1,
				'order_by'  => 'timestamp',
				'order'     => 'DESC',
			)
		);

		// Build WHERE clause.
		$where_conditions = array( '1=1' );
		$where_values     = array();

		if ( ! empty( $filters['level'] ) ) {
			$where_conditions[] = 'level = %s';
			$where_values[]     = $filters['level'];
		}

		if ( ! empty( $filters['context'] ) ) {
			$where_conditions[] = 'context = %s';
			$where_values[]     = $filters['context'];
		}

		if ( ! empty( $filters['search'] ) ) {
			$where_conditions[] = 'message LIKE %s';
			$where_values[]     = '%' . $this->wpdb->esc_like( $filters['search'] ) . '%';
		}

		if ( ! empty( $filters['date_from'] ) ) {
			$where_conditions[] = 'timestamp >= %s';
			$where_values[]     = $filters['date_from'];
		}

		if ( ! empty( $filters['date_to'] ) ) {
			$where_conditions[] = 'timestamp <= %s';
			$where_values[]     = $filters['date_to'] . ' 23:59:59';
		}

		if ( ! empty( $filters['user_id'] ) ) {
			$where_conditions[] = 'user_id = %d';
			$where_values[]     = $filters['user_id'];
		}

		$where_clause = implode( ' AND ', $where_conditions );

		// Get total count.
		$base_query = "SELECT COUNT(*) FROM `{$this->table_name}` WHERE ";
		if ( ! empty( $where_values ) ) {
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table name is escaped, placeholders added separately.
			$count_query = $this->wpdb->prepare(
				$base_query . $where_clause, // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
				...$where_values
			);
		} else {
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQL.NotPrepared -- Table name is escaped, no user input.
			$count_query = $base_query . $where_clause;
		}
		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Query is prepared above when needed.
		$total_count = (int) $this->wpdb->get_var( $count_query );

		// Calculate pagination.
		$per_page    = max( 1, intval( $filters['per_page'] ) );
		$page        = max( 1, intval( $filters['page'] ) );
		$offset      = ( $page - 1 ) * $per_page;
		$total_pages = ceil( $total_count / $per_page );

		// Build main query.
		$order_by = in_array( $filters['order_by'], array( 'timestamp', 'level', 'context' ), true ) ? $filters['order_by'] : 'timestamp';
		$order    = 'ASC' === strtoupper( $filters['order'] ) ? 'ASC' : 'DESC';

		$base_select_query = "SELECT * FROM `{$this->table_name}` WHERE ";
		$order_clause      = " ORDER BY `{$order_by}` {$order} LIMIT %d OFFSET %d";

		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table name, order by, and order are escaped/validated.
		$query = $this->wpdb->prepare(
			$base_select_query . $where_clause . $order_clause, // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			...array_merge( $where_values, array( $per_page, $offset ) )
		);

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Query is prepared above.
		$logs = $this->wpdb->get_results( $query, ARRAY_A );

		// Decode additional_data for each log entry.
		foreach ( $logs as &$log ) {
			if ( ! empty( $log['additional_data'] ) ) {
				$log['additional_data'] = json_decode( $log['additional_data'], true );
			}
		}

		return array(
			'logs'         => $logs,
			'total_count'  => $total_count,
			'total_pages'  => $total_pages,
			'current_page' => $page,
			'per_page'     => $per_page,
		);
	}

	/**
	 * Get unique contexts from logs.
	 *
	 * @return array Array of unique contexts.
	 */
	public function get_contexts() {
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table name is escaped, no user input.
		$query = "SELECT DISTINCT context FROM `{$this->table_name}` ORDER BY context ASC";
		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- No user input in query.
		return $this->wpdb->get_col( $query );
	}

	/**
	 * Get log statistics.
	 *
	 * @return array Array of statistics.
	 */
	public function get_statistics() {
		$stats = array();

		// Count by level.
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table name is escaped, no user input.
		$query = "SELECT level, COUNT(*) as count FROM `{$this->table_name}` GROUP BY level";
		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- No user input in query.
		$level_counts = $this->wpdb->get_results( $query, ARRAY_A );

		$stats['by_level'] = array();
		foreach ( $level_counts as $row ) {
			$stats['by_level'][ $row['level'] ] = (int) $row['count'];
		}

		// Count by context (top 10).
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table name is escaped, no user input.
		$query = "SELECT context, COUNT(*) as count FROM `{$this->table_name}` GROUP BY context ORDER BY count DESC LIMIT 10";
		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- No user input in query.
		$context_counts = $this->wpdb->get_results( $query, ARRAY_A );

		$stats['by_context'] = array();
		foreach ( $context_counts as $row ) {
			$stats['by_context'][ $row['context'] ] = (int) $row['count'];
		}

		// Total count.
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Table name is escaped, no user input.
		$stats['total_count'] = (int) $this->wpdb->get_var( "SELECT COUNT(*) FROM `{$this->table_name}`" );

		// Recent activity (last 24 hours).
		$yesterday = gmdate( 'Y-m-d H:i:s', time() - DAY_IN_SECONDS );
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Table name is escaped.
		$recent_query          = $this->wpdb->prepare(
			"SELECT COUNT(*) FROM `{$this->table_name}` WHERE timestamp >= %s", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$yesterday
		);
		$stats['recent_count'] = (int) $this->wpdb->get_var( $recent_query ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

		return $stats;
	}

	/**
	 * Export logs to CSV format.
	 *
	 * @param array $filters Filters for the export.
	 * @return string CSV content.
	 */
	public function export_to_csv( $filters = array() ) {
		$filters['per_page'] = 9999; // Large number for export.
		$result              = $this->get_logs( $filters );

		$output = array();

		// CSV header.
		$output[] = 'Timestamp,Level,Context,Message,User ID,IP Address,Request ID,Session ID';

		// CSV rows.
		foreach ( $result['logs'] as $log ) {
			$row = array(
				$log['timestamp'],
				$log['level'],
				$log['context'],
				str_replace( array( "\r", "\n" ), ' ', $log['message'] ), // Remove line breaks.
				$log['user_id'] ? $log['user_id'] : '',
				$log['ip_address'] ? $log['ip_address'] : '',
				$log['request_id'] ? $log['request_id'] : '',
				$log['session_id'] ? $log['session_id'] : '',
			);

			// Escape CSV values.
			$escaped_row = array_map(
				function ( $value ) {
					return '"' . str_replace( '"', '""', $value ) . '"';
				},
				$row
			);

			$output[] = implode( ',', $escaped_row );
		}

		return implode( "\n", $output );
	}

	/**
	 * Cleanup old log entries.
	 *
	 * @param int $days Number of days to keep logs.
	 * @return int Number of deleted entries.
	 */
	public function cleanup_old_logs( $days = 30 ) {
		$cutoff_date = gmdate( 'Y-m-d H:i:s', time() - ( $days * DAY_IN_SECONDS ) );

		$deleted = $this->wpdb->delete(
			$this->table_name,
			array(
				'timestamp' => $cutoff_date,
			),
			array( '%s' )
		);

		// Also cleanup if we exceed max entries.
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Table name is escaped, no user input.
		$total_count = (int) $this->wpdb->get_var( "SELECT COUNT(*) FROM `{$this->table_name}`" );

		if ( $total_count > $this->max_log_entries ) {
			$excess = $total_count - $this->max_log_entries;
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Table name is escaped.
			$delete_query = $this->wpdb->prepare(
				"DELETE FROM `{$this->table_name}` ORDER BY timestamp ASC LIMIT %d", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$excess
			);
			$this->wpdb->query( $delete_query ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			$deleted += $excess;
		}

		return $deleted ? $deleted : 0;
	}

	/**
	 * Clear all logs.
	 *
	 * @return bool True on success, false on failure.
	 */
	public function clear_all_logs() {
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange -- Table name is escaped, no user input.
		$result = $this->wpdb->query( "TRUNCATE TABLE `{$this->table_name}`" );
		return false !== $result;
	}

	/**
	 * Set debug logging status.
	 *
	 * @param bool $enabled Whether to enable debug logging.
	 */
	public function set_debug_enabled( $enabled ) {
		$this->debug_enabled = (bool) $enabled;
		update_option( WCD_WP_OPTION_KEY_DEBUG_LOGGING, $this->debug_enabled );
	}

	/**
	 * Check if debug logging is enabled.
	 *
	 * @return bool Whether debug logging is enabled.
	 */
	public function is_debug_enabled() {
		return $this->debug_enabled;
	}

	/**
	 * Get client IP address.
	 *
	 * @return string Client IP address.
	 */
	private function get_client_ip() {
		$ip_keys = array( 'HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP', 'HTTP_CLIENT_IP', 'REMOTE_ADDR' );

		foreach ( $ip_keys as $key ) {
			if ( ! empty( $_SERVER[ $key ] ) ) {
				$ip = sanitize_text_field( wp_unslash( $_SERVER[ $key ] ) );

				// Handle comma-separated IPs (X-Forwarded-For).
				if ( strpos( $ip, ',' ) !== false ) {
					$ip = trim( explode( ',', $ip )[0] );
				}

				if ( filter_var( $ip, FILTER_VALIDATE_IP ) ) {
					return $ip;
				}
			}
		}

		return '';
	}

	/**
	 * Get unique request ID for this request.
	 *
	 * @return string Request ID.
	 */
	private function get_request_id() {
		static $request_id = null;

		if ( null === $request_id ) {
			$request_id = substr( uniqid(), -8 );
		}

		return $request_id;
	}

	/**
	 * Get session ID.
	 *
	 * @return string Session ID.
	 */
	private function get_session_id() {
		if ( session_id() ) {
			return substr( session_id(), -8 );
		}

		// Fallback to user-based session.
		$user_id = get_current_user_id();
		if ( $user_id ) {
			return 'user_' . $user_id;
		}

		return '';
	}

	/**
	 * Create the logs table.
	 *
	 * @return bool True on success, false on failure.
	 */
	public static function create_table() {
		global $wpdb;

		$table_name = $wpdb->prefix . 'wcd_logs';

		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE $table_name (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			timestamp datetime NOT NULL,
			level varchar(20) NOT NULL,
			context varchar(100) NOT NULL,
			message text NOT NULL,
			user_id bigint(20) unsigned NULL,
			ip_address varchar(45) NULL,
			request_id varchar(50) NULL,
			session_id varchar(50) NULL,
			additional_data longtext NULL,
			created_at timestamp DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			KEY idx_timestamp (timestamp),
			KEY idx_level (level),
			KEY idx_context (context),
			KEY idx_level_context (level, context),
			KEY idx_timestamp_level (timestamp, level)
		) $charset_collate;";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		$result = dbDelta( $sql );

		return ! empty( $result );
	}

	/**
	 * Drop the logs table.
	 *
	 * @return bool True on success, false on failure.
	 */
	public static function drop_table() {
		global $wpdb;

		$table_name = $wpdb->prefix . 'wcd_logs';
		// Table drop is intentional for uninstall.
		$result = $wpdb->query( "DROP TABLE IF EXISTS `{$table_name}`" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange, WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		return false !== $result;
	}
}
