<?php
/**
 * Fired during plugin activation
 *
 * @link       wp-mike.com
 * @since      1.0.0
 *
 * @package    WebChangeDetector
 * @subpackage WebChangeDetector/includes
 */

namespace WebChangeDetector;

/**
 * Fired during plugin activation.
 *
 * This class defines all code necessary to run during the plugin's activation.
 *
 * @since      1.0.0
 * @package    WebChangeDetector
 * @subpackage WebChangeDetector/includes
 * @author     Mike Miler <mike@wp-mike.com>
 */
class WebChangeDetector_Activator {

	/**
	 * Plugin activation handler.
	 *
	 * Sets up initial options, creates database tables, and cleans up any stuck auto-update state
	 * from previous installations.
	 *
	 * @since    1.0.0
	 */
	public static function activate() {
		// Create database tables.
		self::create_database_tables();
		// Set up wizard option for first-time users.
		add_option( 'wcd_wizard', true, '', false );

		// Clean up any stuck auto-update state from previous installations.
		// Check if these options exist and if they're old (no timestamp or old timestamp).
		$pre_update   = get_option( 'wcd_pre_auto_update' );
		$post_update  = get_option( 'wcd_post_auto_update' );
		$auto_running = get_option( 'wcd_auto_updates_running' );

		$cleaned = false;

		// Clean up pre-update if it exists and is old.
		if ( $pre_update ) {
			if ( ! isset( $pre_update['timestamp'] ) || ( time() - $pre_update['timestamp'] ) > 3600 ) {
				delete_option( 'wcd_pre_auto_update' );
				$cleaned = true;
			}
		}

		// Clean up post-update if it exists and is old.
		if ( $post_update ) {
			if ( ! isset( $post_update['timestamp'] ) || ( time() - $post_update['timestamp'] ) > 3600 ) {
				delete_option( 'wcd_post_auto_update' );
				delete_option( 'wcd_wordpress_cron' );
				$cleaned = true;
			}
		}

		// Clean up running flag if other options were cleaned.
		if ( $cleaned && $auto_running ) {
			delete_option( 'wcd_auto_updates_running' );
		}

		// Check for stuck WordPress auto-updater lock.
		$lock = get_option( 'auto_updater.lock' );
		if ( $lock && $lock < ( time() - HOUR_IN_SECONDS ) ) {
			delete_option( 'auto_updater.lock' );
			$cleaned = true;
			if ( class_exists( '\WebChangeDetector\WebChangeDetector_Admin_Utils' ) ) {
				\WebChangeDetector\WebChangeDetector_Admin_Utils::log_error(
					'Removed stuck auto_updater.lock (age: ' . ( time() - $lock - HOUR_IN_SECONDS ) . ' seconds) during activation.',
					'activator',
					'warning'
				);
			}
		}

		// Log activation and any cleanup.
		if ( class_exists( '\WebChangeDetector\WebChangeDetector_Admin_Utils' ) ) {
			if ( $cleaned ) {
				\WebChangeDetector\WebChangeDetector_Admin_Utils::log_error(
					'Plugin activated. Cleaned up stuck auto-update state from previous installation.',
					'activator',
					'info'
				);
			} else {
				\WebChangeDetector\WebChangeDetector_Admin_Utils::log_error(
					'Plugin activated. No stuck auto-update state found.',
					'activator',
					'info'
				);
			}
		}
	}

	/**
	 * Create database tables required by the plugin.
	 *
	 * @since 4.0.0
	 */
	private static function create_database_tables() {
		// Ensure the WebChangeDetector_Database_Logger class is available.
		if ( ! class_exists( '\WebChangeDetector\WebChangeDetector_Database_Logger' ) ) {
			require_once plugin_dir_path( __DIR__ ) . 'admin/class-webchangedetector-database-logger.php';
		}

		// Create the logs table.
		$result = \WebChangeDetector\WebChangeDetector_Database_Logger::create_table();

		// Log the table creation result.
		if ( class_exists( '\WebChangeDetector\WebChangeDetector_Admin_Utils' ) ) {
			if ( $result ) {
				\WebChangeDetector\WebChangeDetector_Admin_Utils::log_error(
					'Database tables created successfully during activation.',
					'activator',
					'info'
				);
			} else {
				\WebChangeDetector\WebChangeDetector_Admin_Utils::log_error(
					'Failed to create database tables during activation.',
					'activator',
					'error'
				);
			}
		}
	}
}
