<?php
/**
 * Settings AJAX handler.
 *
 * Handles all settings and configuration-related AJAX operations including
 * URL management, website/group creation, and initial setup processes.
 *
 * @link       https://www.webchangedetector.com
 * @since      4.0.0
 * @package    WebChangeDetector
 * @subpackage WebChangeDetector/admin/ajax
 */

namespace WebChangeDetector;

/**
 * Settings AJAX handler.
 *
 * Handles all settings and configuration-related AJAX operations.
 *
 * @since      4.0.0
 * @package    WebChangeDetector
 * @subpackage WebChangeDetector/admin/ajax
 * @author     Mike Miler <mike@webchangedetector.com>
 */
class WebChangeDetector_Settings_Ajax_Handler extends WebChangeDetector_Ajax_Handler_Base {

	/**
	 * The settings handler instance.
	 *
	 * @since    4.0.0
	 * @access   private
	 * @var      WebChangeDetector_Admin_Settings    $settings_handler    The settings handler instance.
	 */
	private $settings_handler;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    4.0.0
	 * @param    WebChangeDetector_Admin          $admin            The main admin class instance.
	 * @param    WebChangeDetector_Admin_Settings $settings_handler The settings handler instance.
	 */
	public function __construct( $admin, $settings_handler ) {
		parent::__construct( $admin );

		$this->settings_handler = $settings_handler;
	}

	/**
	 * Register AJAX hooks for settings operations.
	 *
	 * Registers all WordPress AJAX hooks for settings-related operations.
	 *
	 * @since    4.0.0
	 */
	public function register_hooks() {
		add_action( 'wp_ajax_post_url', array( $this, 'ajax_post_url' ) );
		add_action( 'wp_ajax_wcd_disable_wizard', array( $this, 'ajax_disable_wizard' ) );
		add_action( 'wp_ajax_create_website_and_groups_ajax', array( $this, 'ajax_create_website_and_groups' ) );
		add_action( 'wp_ajax_wcd_get_initial_setup', array( $this, 'ajax_get_initial_setup' ) );
		add_action( 'wp_ajax_wcd_save_initial_setup', array( $this, 'ajax_save_initial_setup' ) );
		add_action( 'wp_ajax_wcd_update_sync_types_with_local_labels', array( $this, 'ajax_update_sync_types_with_local_labels' ) );
		add_action( 'wp_ajax_wcd_complete_initial_setup', array( $this, 'ajax_complete_initial_setup' ) );
	}

	/**
	 * Handle post URL AJAX request.
	 *
	 * Processes URL settings and saves them to the database.
	 * Delegates to the main admin class for backwards compatibility.
	 *
	 * @since    4.0.0
	 */
	public function ajax_post_url() {
		if ( ! $this->security_check() ) {
			return;
		}

		try {
			// Delegate to main admin class for now (will be refactored later).
			if ( $this->admin && method_exists( $this->admin, 'post_urls' ) ) {
				// Capture any output from the post_urls method.
				ob_start();
				$this->admin->post_urls( $_POST );
				ob_get_clean();

				$this->send_success_response(
					null,
					__( 'Settings saved successfully.', 'webchangedetector' )
				);
			} else {
				$this->send_error_response(
					__( 'Method not available.', 'webchangedetector' ),
					'post_urls method missing'
				);
			}
		} catch ( \Exception $e ) {
			$this->send_error_response(
				__( 'An error occurred while saving settings.', 'webchangedetector' ),
				'Exception: ' . $e->getMessage()
			);
		}
	}

	/**
	 * Handle disable wizard AJAX request.
	 *
	 * Disables the setup wizard and updates the user preference.
	 *
	 * @since    4.0.0
	 */
	public function ajax_disable_wizard() {
		if ( ! $this->security_check() ) {
			return;
		}

		try {
			$result = update_option( 'wcd_wizard_disabled', true );

			if ( $result ) {
				$this->send_success_response(
					array( 'wizard_disabled' => true ),
					__( 'Wizard disabled successfully.', 'webchangedetector' )
				);
			} else {
				$this->send_error_response(
					__( 'Failed to disable wizard.', 'webchangedetector' ),
					'Option update failed'
				);
			}
		} catch ( \Exception $e ) {
			$this->send_error_response(
				__( 'An error occurred while disabling wizard.', 'webchangedetector' ),
				'Exception: ' . $e->getMessage()
			);
		}
	}

	/**
	 * Handle create website and groups AJAX request.
	 *
	 * Creates a website and associated groups via the API.
	 *
	 * @since    4.0.0
	 */
	public function ajax_create_website_and_groups() {
		if ( ! $this->security_check() ) {
			return;
		}

		try {
			$result = $this->admin->create_website_and_groups();

			if ( isset( $result['error'] ) ) {
				$this->send_error_response(
					$result['error'],
					'Website creation failed'
				);
			} else {
				$this->send_success_response( $result );
			}
		} catch ( \Exception $e ) {
			$this->admin->log_error( 'Exception during website creation: ' . $e->getMessage() );
			$this->send_error_response(
				$e->getMessage(),
				'Exception during website creation'
			);
		}
	}

	/**
	 * Handle get initial setup AJAX request.
	 *
	 * Retrieves initial setup data for the setup wizard.
	 *
	 * @since    4.0.0
	 */
	public function ajax_get_initial_setup() {
		if ( ! $this->security_check() ) {
			return;
		}

		try {
			$setup_data = array(
				'website_details'      => $this->admin->website_details,
				'available_sync_types' => $this->settings_handler->get_available_sync_types(),
				'current_sync_types'   => get_option( 'wcd_sync_url_types', array() ),
			);

			$this->send_success_response( $setup_data );

		} catch ( \Exception $e ) {
			$this->send_error_response(
				__( 'Failed to get initial setup data.', 'webchangedetector' ),
				'Exception: ' . $e->getMessage()
			);
		}
	}

	/**
	 * Handle save initial setup AJAX request.
	 *
	 * Saves initial setup configuration data.
	 *
	 * @since    4.0.0
	 */
	public function ajax_save_initial_setup() {
		if ( ! $this->security_check() ) {
			return;
		}

		try {
			$post_data = $this->validate_post_data( array( 'sync_types' ) );

			if ( false === $post_data ) {
				$this->send_error_response(
					__( 'Missing sync types data.', 'webchangedetector' ),
					'Missing sync_types'
				);
				return;
			}

			$sync_types = $post_data['sync_types'];

			// Validate sync types.
			$available_types = $this->settings_handler->get_available_sync_types();
			$valid_types     = array();

			foreach ( $sync_types as $type ) {
				if ( isset( $available_types[ $type ] ) ) {
					$valid_types[] = $type;
				}
			}

			update_option( 'wcd_sync_url_types', $valid_types );

			$this->send_success_response(
				array( 'sync_types' => $valid_types ),
				__( 'Initial setup saved successfully.', 'webchangedetector' )
			);

		} catch ( \Exception $e ) {
			$this->send_error_response(
				__( 'An error occurred while saving initial setup.', 'webchangedetector' ),
				'Exception: ' . $e->getMessage()
			);
		}
	}

	/**
	 * Handle update sync types with local labels AJAX request.
	 *
	 * Updates sync types configuration with localized labels.
	 *
	 * @since    4.0.0
	 */
	public function ajax_update_sync_types_with_local_labels() {
		if ( ! $this->security_check() ) {
			return;
		}

		try {
			$post_data = $this->validate_post_data( array( 'sync_types' ) );

			if ( false === $post_data ) {
				$this->send_error_response(
					__( 'Missing sync types data.', 'webchangedetector' ),
					'Missing sync_types'
				);
				return;
			}

			$sync_types      = $post_data['sync_types'];
			$available_types = $this->settings_handler->get_available_sync_types();

			// Validate and update sync types.
			$updated_sync_types = array();
			foreach ( $sync_types as $type ) {
				if ( isset( $available_types[ $type ] ) ) {
					$updated_sync_types[] = $type;
				}
			}

			update_option( 'wcd_sync_url_types', $updated_sync_types );

			$this->send_success_response(
				array( 'sync_url_types' => $updated_sync_types ),
				__( 'Sync types updated successfully.', 'webchangedetector' )
			);

		} catch ( \Exception $e ) {
			$this->send_error_response(
				__( 'An error occurred while updating sync types.', 'webchangedetector' ),
				'Exception: ' . $e->getMessage()
			);
		}
	}

	/**
	 * Handle complete initial setup AJAX request.
	 *
	 * Completes the initial setup process and marks it as finished.
	 *
	 * @since    4.0.0
	 */
	public function ajax_complete_initial_setup() {
		if ( ! $this->security_check() ) {
			return;
		}

		try {
			// Mark setup as completed.
			update_option( 'wcd_initial_setup_completed', true );
			update_option( 'wcd_wizard_disabled', true );

			$this->send_success_response(
				array(
					'setup_completed' => true,
					'wizard_disabled' => true,
				),
				__( 'Initial setup completed successfully.', 'webchangedetector' )
			);

		} catch ( \Exception $e ) {
			$this->send_error_response(
				__( 'An error occurred while completing initial setup.', 'webchangedetector' ),
				'Exception: ' . $e->getMessage()
			);
		}
	}
}
