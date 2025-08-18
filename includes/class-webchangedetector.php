<?php
/**
 * The file that defines the core plugin class
 *
 * A class definition that includes attributes and functions used across both the
 * public-facing side of the site and the admin area.
 *
 * @link       wp-mike.com
 * @since      1.0.0
 *
 * @package    WebChangeDetector
 * @subpackage WebChangeDetector/includes
 */

namespace WebChangeDetector;

/**
 * The core plugin class.
 *
 * This is used to define internationalization, admin-specific hooks, and
 * public-facing site hooks.
 *
 * Also maintains the unique identifier of this plugin as well as the current
 * version of the plugin.
 *
 * @since      1.0.0
 * @package    webchangedetector
 * @subpackage webchangedetector/includes
 * @author     Mike Miler <mike@wp-mike.com>
 */
class WebChangeDetector {


	/**
	 * The loader that's responsible for maintaining and registering all hooks that power
	 * the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      WebChangeDetector_Loader    $loader    Maintains and registers all hooks for the plugin.
	 */
	protected $loader;

	/**
	 * The unique identifier of this plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $plugin_name    The string used to uniquely identify this plugin.
	 */
	protected $plugin_name;

	/**
	 * The current version of the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $version    The current version of the plugin.
	 */
	protected $version;

	/**
	 * Define the core functionality of the plugin.
	 *
	 * Set the plugin name and the plugin version that can be used throughout the plugin.
	 * Load the dependencies, define the locale, and set the hooks for the admin area and
	 * the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function __construct() {
		if ( defined( 'WEBCHANGEDETECTOR_VERSION' ) ) {
			$this->version = WEBCHANGEDETECTOR_VERSION;
		} else {
			$this->version = '1.0.0';
		}
		$this->plugin_name = 'webchangedetector';

		$this->load_dependencies();
		$this->set_locale();
		$this->define_admin_hooks();
		$this->define_public_hooks();
	}

	/**
	 * Load the required dependencies for this plugin.
	 *
	 * Include the following files that make up the plugin:
	 *
	 * - WebChangeDetector_Loader. Orchestrates the hooks of the plugin.
	 * - WebChangeDetector_i18n. Defines internationalization functionality.
	 * - WebChangeDetector_Admin. Defines all hooks for the admin area.
	 * - WebChangeDetector_Public. Defines all hooks for the public side of the site.
	 *
	 * Create an instance of the loader which will be used to register the hooks
	 * with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function load_dependencies() {

		/**
		 * The class responsible for orchestrating the actions and filters of the
		 * core plugin.
		 */
		require_once plugin_dir_path( __DIR__ ) . 'includes/class-webchangedetector-loader.php';

		/**
		 * The class responsible for defining internationalization functionality
		 * of the plugin.
		 */
		require_once plugin_dir_path( __DIR__ ) . 'includes/class-webchangedetector-i18n.php';

		/**
		 * The utility class for admin functions following WordPress standards.
		 */
		require_once plugin_dir_path( __DIR__ ) . 'admin/class-webchangedetector-admin-utils.php';

		/**
		 * The settings management class for all WebChangeDetector configuration.
		 */
		require_once plugin_dir_path( __DIR__ ) . 'admin/class-webchangedetector-admin-settings.php';

		/**
		 * The AJAX handlers class for all WebChangeDetector AJAX requests.
		 */
		require_once plugin_dir_path( __DIR__ ) . 'admin/class-webchangedetector-admin-ajax.php';

		/**
		 * The dashboard and views management class for all WebChangeDetector display functionality.
		 */
		require_once plugin_dir_path( __DIR__ ) . 'admin/class-webchangedetector-admin-dashboard.php';

		/**
		 * The screenshots and comparisons management class for all WebChangeDetector screenshot functionality.
		 */
		require_once plugin_dir_path( __DIR__ ) . 'admin/class-webchangedetector-admin-screenshots.php';

		/**
		 * The account and API management class for all WebChangeDetector account functionality.
		 */
		require_once plugin_dir_path( __DIR__ ) . 'admin/class-webchangedetector-admin-account.php';

		/**
		 * The WordPress integration class for all WebChangeDetector WordPress functionality.
		 */
		require_once plugin_dir_path( __DIR__ ) . 'admin/class-webchangedetector-admin-wordpress.php';

		/**
		 * The focused AJAX handler classes for better organization and maintainability.
		 */
		require_once plugin_dir_path( __DIR__ ) . 'admin/ajax/class-webchangedetector-ajax-handler-base.php';
		require_once plugin_dir_path( __DIR__ ) . 'admin/ajax/class-webchangedetector-screenshots-ajax-handler.php';
		require_once plugin_dir_path( __DIR__ ) . 'admin/ajax/class-webchangedetector-settings-ajax-handler.php';
		require_once plugin_dir_path( __DIR__ ) . 'admin/ajax/class-webchangedetector-wordpress-ajax-handler.php';
		require_once plugin_dir_path( __DIR__ ) . 'admin/ajax/class-webchangedetector-account-ajax-handler.php';

		/**
		 * The class responsible for defining all actions that occur in the admin area.
		 */
		require_once plugin_dir_path( __DIR__ ) . 'admin/class-webchangedetector-admin.php';

		/**
		 * Controller classes for request handling and routing.
		 */
		require_once plugin_dir_path( __DIR__ ) . 'admin/controllers/class-webchangedetector-admin-controller.php';
		require_once plugin_dir_path( __DIR__ ) . 'admin/controllers/class-webchangedetector-dashboard-controller.php';
		require_once plugin_dir_path( __DIR__ ) . 'admin/controllers/class-webchangedetector-settings-controller.php';
		require_once plugin_dir_path( __DIR__ ) . 'admin/controllers/class-webchangedetector-monitoring-controller.php';
		require_once plugin_dir_path( __DIR__ ) . 'admin/controllers/class-webchangedetector-manual-checks-controller.php';
		require_once plugin_dir_path( __DIR__ ) . 'admin/controllers/class-webchangedetector-change-detections-controller.php';
		require_once plugin_dir_path( __DIR__ ) . 'admin/controllers/class-webchangedetector-logs-controller.php';

		/**
		 * Action handler classes for business logic separation.
		 */
		require_once plugin_dir_path( __DIR__ ) . 'admin/actions/class-webchangedetector-screenshot-action-handler.php';
		require_once plugin_dir_path( __DIR__ ) . 'admin/actions/class-webchangedetector-settings-action-handler.php';
		require_once plugin_dir_path( __DIR__ ) . 'admin/actions/class-webchangedetector-account-action-handler.php';
		require_once plugin_dir_path( __DIR__ ) . 'admin/actions/class-webchangedetector-wordpress-action-handler.php';
		require_once plugin_dir_path( __DIR__ ) . 'admin/actions/class-webchangedetector-comparison-action-handler.php';

		/**
		 * View renderer classes for presentation layer.
		 */
		require_once plugin_dir_path( __DIR__ ) . 'admin/views/class-webchangedetector-view-renderer.php';
		require_once plugin_dir_path( __DIR__ ) . 'admin/views/class-webchangedetector-notification-view.php';
		require_once plugin_dir_path( __DIR__ ) . 'admin/views/class-webchangedetector-form-view.php';
		require_once plugin_dir_path( __DIR__ ) . 'admin/views/class-webchangedetector-card-view.php';
		require_once plugin_dir_path( __DIR__ ) . 'admin/views/class-webchangedetector-modal-view.php';
		require_once plugin_dir_path( __DIR__ ) . 'admin/views/class-webchangedetector-template-view.php';

		/**
		 * Component manager for reusable UI components.
		 */
		require_once plugin_dir_path( __DIR__ ) . 'admin/partials/class-webchangedetector-component-manager.php';

		/**
		 * Error handling and logging classes.
		 */
		require_once plugin_dir_path( __DIR__ ) . 'admin/error-handling/class-webchangedetector-logger.php';
		require_once plugin_dir_path( __DIR__ ) . 'admin/error-handling/class-webchangedetector-exceptions.php';
		require_once plugin_dir_path( __DIR__ ) . 'admin/error-handling/class-webchangedetector-error-handler.php';
		require_once plugin_dir_path( __DIR__ ) . 'admin/error-handling/class-webchangedetector-error-recovery.php';
		require_once plugin_dir_path( __DIR__ ) . 'admin/error-handling/class-webchangedetector-user-feedback.php';

		/**
		 * The class responsible API calls.
		 */
		require_once plugin_dir_path( __DIR__ ) . 'admin/class-webchangedetector-api-v2.php';

		/**
		 * The class responsible for auto-update-checks
		 */
		require_once plugin_dir_path( __DIR__ ) . 'admin/class-webchangedetector-autoupdates.php';

		/**
		 * The class responsible for defining all actions that occur in the public-facing
		 * side of the site.
		 */
		require_once plugin_dir_path( __DIR__ ) . 'public/class-webchangedetector-public.php';

		$this->loader = new WebChangeDetector_Loader();
	}

	/**
	 * Define the locale for this plugin for internationalization.
	 *
	 * Uses the WebChangeDetector_i18n class in order to set the domain and to register the hook
	 * with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function set_locale() {
		$plugin_i18n = new WebChangeDetector_I18n();

		$this->loader->add_action( 'plugins_loaded', $plugin_i18n, 'load_plugin_textdomain' );
	}

	/**
	 * Register all of the hooks related to the admin area functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_admin_hooks() {
		$plugin_admin     = new WebChangeDetector_Admin( $this->get_plugin_name() );
		$plugin_ajax      = new WebChangeDetector_Admin_AJAX( $plugin_admin );
		$plugin_wordpress = new WebChangeDetector_Admin_WordPress( $this->get_plugin_name(), $this->get_version(), $plugin_admin );

		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_wordpress, 'enqueue_styles' );
		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_wordpress, 'enqueue_scripts' );
		$this->loader->add_action( 'admin_menu', $plugin_wordpress, 'wcd_plugin_setup_menu' );

		// AJAX handlers now managed by dedicated AJAX class.
		$this->loader->add_action( 'wp_ajax_get_processing_queue', $plugin_ajax, 'ajax_get_processing_queue' );
		$this->loader->add_action( 'wp_ajax_post_url', $plugin_ajax, 'ajax_post_url' );
		$this->loader->add_action( 'wp_ajax_update_comparison_status', $plugin_ajax, 'ajax_update_comparison_status' );
		$this->loader->add_action( 'wp_ajax_sync_urls', $plugin_ajax, 'ajax_sync_urls' );
		$this->loader->add_action( 'wp_ajax_wcd_disable_wizard', $plugin_ajax, 'ajax_disable_wizard' );
		$this->loader->add_action( 'wp_ajax_get_batch_comparisons_view', $plugin_ajax, 'ajax_get_batch_comparisons_view' );
		$this->loader->add_action( 'wp_ajax_load_failed_queues', $plugin_ajax, 'ajax_load_failed_queues' );
		$this->loader->add_action( 'wp_ajax_create_website_and_groups_ajax', $plugin_ajax, 'ajax_create_website_and_groups' );
		$this->loader->add_action( 'wp_ajax_get_dashboard_usage_stats', $plugin_ajax, 'ajax_get_dashboard_usage_stats' );
		$this->loader->add_action( 'wp_ajax_wcd_get_admin_bar_status', $plugin_ajax, 'ajax_get_wcd_admin_bar_status' );
		$this->loader->add_action( 'wp_ajax_wcd_check_activation_status', $plugin_ajax, 'ajax_check_activation_status' );
		$this->loader->add_action( 'wp_ajax_wcd_get_initial_setup', $plugin_ajax, 'ajax_get_initial_setup' );
		$this->loader->add_action( 'wp_ajax_wcd_save_initial_setup', $plugin_ajax, 'ajax_save_initial_setup' );
		$this->loader->add_action( 'wp_ajax_wcd_sync_posts', $plugin_ajax, 'ajax_sync_posts' );
		$this->loader->add_action( 'wp_ajax_wcd_update_sync_types_with_local_labels', $plugin_ajax, 'ajax_update_sync_types_with_local_labels' );
		$this->loader->add_action( 'wp_ajax_wcd_complete_initial_setup', $plugin_ajax, 'ajax_complete_initial_setup' );

		$this->loader->add_action( 'post_updated', $plugin_wordpress, 'update_post', 9999, 3 );
		$this->loader->add_action( 'save_post', $plugin_wordpress, 'wcd_sync_post_after_save', 10, 3 );

		// Add async sync cron handlers.
		$this->loader->add_action( 'wcd_async_single_post_sync', $plugin_wordpress, 'async_single_post_sync_handler', 10, 1 );
		$this->loader->add_action( 'wcd_async_full_sync', $plugin_wordpress, 'async_full_sync_handler', 10, 1 );

		// Add hook for admin bar menu rendering.
		$this->loader->add_action( 'admin_bar_menu', $plugin_wordpress, 'wcd_admin_bar_menu', 999 );
		// Add hook for frontend admin bar script enqueueing.
		$this->loader->add_action( 'wp_enqueue_scripts', $plugin_wordpress, 'enqueue_admin_bar_scripts' );
	}

	/**
	 * Run the loader to execute all of the hooks with WordPress.
	 *
	 * @since    1.0.0
	 */
	public function run() {
		$this->loader->run();
	}

	/**
	 * The name of the plugin used to uniquely identify it within the context of
	 * WordPress and to define internationalization functionality.
	 *
	 * @since     1.0.0
	 * @return    string    The name of the plugin.
	 */
	public function get_plugin_name() {
		return $this->plugin_name;
	}

	/**
	 * Register all of the hooks related to the public-facing functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_public_hooks() {
		$plugin_public = new WebChangeDetector_Public();

		// Add hook for frontend styles (including admin bar slider styles).
		$this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_styles' );
		$this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_scripts' );
	}
	/**
	 * Retrieve the version number of the plugin.
	 *
	 * @since     1.0.0
	 * @return    string    The version number of the plugin.
	 */
	public function get_version() {
		return $this->version;
	}
}
