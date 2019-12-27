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
 * @package    Wp_Compare
 * @subpackage Wp_Compare/includes
 */

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
 * @package    Wp_Compare
 * @subpackage Wp_Compare/includes
 * @author     Mike Miler <mike@wp-mike.com>
 */
class Wp_Compare {

	/**
	 * The loader that's responsible for maintaining and registering all hooks that power
	 * the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      Wp_Compare_Loader    $loader    Maintains and registers all hooks for the plugin.
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
		if ( defined( 'WP_COMPARE_VERSION' ) ) {
			$this->version = WP_COMPARE_VERSION;
		} else {
			$this->version = '1.0.0';
		}
		$this->plugin_name = 'wp-compare';

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
	 * - Wp_Compare_Loader. Orchestrates the hooks of the plugin.
	 * - Wp_Compare_i18n. Defines internationalization functionality.
	 * - Wp_Compare_Admin. Defines all hooks for the admin area.
	 * - Wp_Compare_Public. Defines all hooks for the public side of the site.
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
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-wp-compare-loader.php';

		/**
		 * The class responsible for defining internationalization functionality
		 * of the plugin.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-wp-compare-i18n.php';

		/**
		 * The class responsible for defining all actions that occur in the admin area.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/class-wp-compare-admin.php';

		/**
		 * The class responsible for defining all actions that occur in the public-facing
		 * side of the site.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'public/class-wp-compare-public.php';

		$this->loader = new Wp_Compare_Loader();

	}

	/**
	 * Define the locale for this plugin for internationalization.
	 *
	 * Uses the Wp_Compare_i18n class in order to set the domain and to register the hook
	 * with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function set_locale() {

		$plugin_i18n = new Wp_Compare_i18n();

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

		$plugin_admin = new Wp_Compare_Admin( $this->get_plugin_name(), $this->get_version() );

		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_styles' );
		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts' );

	}

	/**
	 * Register all of the hooks related to the public-facing functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_public_hooks() {

		$plugin_public = new Wp_Compare_Public( $this->get_plugin_name(), $this->get_version() );

		$this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_styles' );
		$this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_scripts' );

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
	 * The reference to the class that orchestrates the hooks with the plugin.
	 *
	 * @since     1.0.0
	 * @return    Wp_Compare_Loader    Orchestrates the hooks of the plugin.
	 */
	public function get_loader() {
		return $this->loader;
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

	public function get_account_details( $api_key ) {

		$args = array(
			'action'		=> 'account_details',
			'api_key'		=> $api_key
		);
		return mm_api( $args );
	}

	public function get_usage( $api_key ) {
		$args = array(
			'action'	=> 'get_usage',
			'api_key'	=> $api_key
		);

		return mm_api( $args );
	}

	public function take_screenshot( $group_id, $api_key ) {
		$args = array(
			'action'		=> 'take_screenshots',
			'group_id'		=> $group_id
		);

		return mm_api( $args );
	}

	function create_free_account() {
		$args = array(
			'action'		=> 'add_free_account',
			'domain'		=> $_SERVER['SERVER_NAME']
		);

		$api_key = mm_api( $args );

		update_option( 'wpcompare_api_key', $api_key );
		delete_option( 'wpcompare_group_id' );

		$this->create_group( $api_key );

		return $api_key;
	}

	function get_api_key() {

		$api_key = get_option( 'wpcompare_api_key' );
		if( $api_key && $this->verify_api_key( $api_key ) ) {
			// Verify User
			return $api_key;
		} else
			return false;

	}

	function verify_api_key( $api_key ) {
		$args = array(
			'action'		=> 'check_api_key',
			'api_key'		=> $api_key
		);

		return mm_api( $args );
	}

	function create_group( $api_key ) {
		// Create group if it doesn't exist yet
		$args = array(
			'action'	=> 'add_group',
			'domain'	=> $_SERVER['SERVER_NAME'],
			'api_key'	=> $api_key
		);

		$group = mm_api( $args );

		$group_id = $group[0]['id'];

		update_option( 'wpcompare_group_id', $group_id );
	}

	function get_no_account_page() {
		$output = '<form action="/wp-admin/admin.php?page=wp-compare&tab=settings" method="post">';
		$output .= '<input type="hidden" name="action" value="create_free_account">';
		$output .= '<input type="submit" value="Create free account" class="button">';

		return $output;
	}
}

function mm_api( $args ) {

	$url = 'https://compare.wp-mike.com/v1/api.php';

	$api_key = get_option( 'wpcompare_api_key' );
	//if( !$api_key )
	//	return 'No API Key';

	if( $api_key )
		$args['api_key'] = $api_key;

	$ch = curl_init( $url );
	curl_setopt($ch, CURLOPT_USERPWD, "wpmike:letmein");
	curl_setopt($ch, CURLOPT_POSTFIELDS, $args);
	curl_setopt($ch, CURLOPT_TIMEOUT, 300);

	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

	$result = curl_exec($ch);

	if( isJson( $result ) )
		return json_decode( $result, true );
	else
		return $result;
}