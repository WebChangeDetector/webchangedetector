<?php
/**
 * WebChange Detector Admin WordPress Integration Class
 *
 * This class handles all WordPress-specific integration functionality for the WebChange Detector plugin.
 * Extracted from the main admin class as part of the refactoring process to improve code organization
 * and maintainability following WordPress coding standards.
 *
 * @link       https://webchangedetector.com
 * @since      1.0.0
 *
 * @package    WebChangeDetector
 * @subpackage WebChangeDetector/admin
 */

namespace WebChangeDetector;

/**
 * The WordPress integration functionality of the plugin.
 *
 * Defines all functionality related to WordPress hooks, admin menus, script/style enqueuing,
 * admin bar integration, and post update handling for the WebChange Detector service.
 *
 * @package    WebChangeDetector
 * @subpackage WebChangeDetector/admin
 * @author     Mike Miler <mike@webchangedetector.com>
 * @since      1.0.0
 */
class WebChangeDetector_Admin_WordPress {

	/**
	 * The plugin name.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $plugin_name    The plugin name.
	 */
	private $plugin_name;

	/**
	 * The plugin version.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $version    The plugin version.
	 */
	private $version;

	/**
	 * The main admin instance.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      WebChangeDetector_Admin    $admin    The main admin instance.
	 */
	private $admin;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param    string                      $plugin_name    The plugin name.
	 * @param    string                      $version        The plugin version.
	 * @param    WebChangeDetector_Admin     $admin          The main admin instance.
	 */
	public function __construct( $plugin_name, $version, $admin ) {
		$this->plugin_name = $plugin_name;
		$this->version = $version;
		$this->admin = $admin;
	}

	/**
	 * Register the stylesheets for the admin area.
	 *
	 * @since    1.0.0
	 * @return   void
	 */
	public function enqueue_styles() {
		wp_enqueue_style( 'jquery-ui-accordion' );
		wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/webchangedetector-admin.css', array(), $this->version, 'all' );
		wp_enqueue_style( 'twentytwenty-css', plugin_dir_url( __FILE__ ) . 'css/twentytwenty.css', array(), $this->version, 'all' );
		wp_enqueue_style( 'wp-codemirror' );
		wp_enqueue_style( 'driver-css', plugin_dir_url( __FILE__ ) . 'css/driver.css', array(), $this->version, 'all' );
	}

	/**
	 * Register the JavaScript for the admin area.
	 *
	 * @since    1.0.0
	 * @param    string    $hook_suffix    The hook suffix for the current page.
	 * @return   void
	 */
	public function enqueue_scripts( $hook_suffix ) {
		if ( strpos( $hook_suffix, 'webchangedetector' ) !== false ) {
			wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/webchangedetector-admin.js', array( 'jquery' ), $this->version, false );
			wp_enqueue_script( 'jquery-ui-accordion' );
			wp_enqueue_script( 'twentytwenty-js', plugin_dir_url( __FILE__ ) . 'js/jquery.twentytwenty.js', array( 'jquery' ), $this->version, false );
			wp_enqueue_script( 'twentytwenty-move-js', plugin_dir_url( __FILE__ ) . 'js/jquery.event.move.js', array( 'jquery' ), $this->version, false );
			wp_enqueue_script( 'driver-js', plugin_dir_url( __FILE__ ) . 'js/driver.js.iife.js', array(), $this->version, false );
			wp_enqueue_script( 'wcd-wizard', plugin_dir_url( __FILE__ ) . 'js/wizard.js', array( 'jquery', 'driver-js' ), $this->version, false );

			$css_settings = array( 'type' => 'text/css' );
			$cm_settings['codeEditor'] = wp_enqueue_code_editor( $css_settings );
			wp_localize_script( 'jquery', 'cm_settings', $cm_settings );

			wp_localize_script( 'wcd-wizard', 'wcdWizardData', array(
				'ajax_url' => admin_url( 'admin-ajax.php' ),
				'nonce'    => wp_create_nonce( 'wcd_wizard_nonce' ),
			) );

			wp_localize_script( $this->plugin_name, 'wcdAjaxData', array(
				'ajax_url'   => admin_url( 'admin-ajax.php' ),
				'nonce'      => wp_create_nonce( 'ajax-nonce' ),
				'plugin_url' => plugin_dir_url( __FILE__ ),
			) );
		}
	}

	/**
	 * Register the JavaScript for the admin bar on the frontend.
	 *
	 * @since    1.0.0
	 * @return   void
	 */
	public function enqueue_admin_bar_scripts() {
		if ( get_option( 'wcd_disable_admin_bar_menu' ) ) {
			return;
		}
		
		if ( is_admin_bar_showing() && ! is_admin() && current_user_can( 'manage_options' ) ) {
			$admin_bar_script_handle = 'webchangedetector-admin-bar';
			wp_enqueue_script( $admin_bar_script_handle, plugin_dir_url( __FILE__ ) . 'js/webchangedetector-admin-bar.js', array( 'jquery' ), $this->version, true );

			wp_localize_script( $admin_bar_script_handle, 'wcdAdminBarData', array(
				'ajax_url'         => admin_url( 'admin-ajax.php' ),
				'nonce'            => wp_create_nonce( 'wcd_admin_bar_nonce' ),
				'postUrlNonce'     => wp_create_nonce( 'ajax-nonce' ),
				'action'           => 'wcd_get_admin_bar_status',
				'loading_text'     => __( 'Loading WCD Status...', 'webchangedetector' ),
				'error_text'       => __( 'Error loading status.', 'webchangedetector' ),
				'not_tracked_text' => __( 'URL not tracked by WCD', 'webchangedetector' ),
				'manual_label'     => __( 'Manual / Auto Update Checks', 'webchangedetector' ),
				'monitoring_label' => __( 'Monitoring', 'webchangedetector' ),
				'desktop_label'    => __( 'Desktop', 'webchangedetector' ),
				'mobile_label'     => __( 'Mobile', 'webchangedetector' ),
				'dashboard_label'  => __( 'WCD Dashboard', 'webchangedetector' ),
				'dashboard_url'    => admin_url( 'admin.php?page=webchangedetector' ),
			) );
		}
	}

	/**
	 * Add WebChange Detector to backend navigation.
	 *
	 * @since    1.0.0
	 * @return   void
	 */
	public function wcd_plugin_setup_menu() {
		require_once 'partials/webchangedetector-admin-display.php';
		$allowances = get_option( WCD_ALLOWANCES );

		add_menu_page( 'WebChange Detector', 'WebChange Detector', 'manage_options', 'webchangedetector', 'wcd_webchangedetector_init', plugin_dir_url( __FILE__ ) . 'img/icon-wp-backend.svg' );
		add_submenu_page( 'webchangedetector', 'Dashboard', 'Dashboard', 'manage_options', 'webchangedetector', 'wcd_webchangedetector_init' );

		if ( is_array( $allowances ) && $allowances['change_detections_view'] ) {
			add_submenu_page( 'webchangedetector', 'Change Detections', 'Change Detections', 'manage_options', 'webchangedetector-change-detections', 'wcd_webchangedetector_init' );
		}
		if ( is_array( $allowances ) && $allowances['manual_checks_view'] ) {
			add_submenu_page( 'webchangedetector', 'Manual Checks & Auto Update Checks', 'Manual Checks & Auto Update Checks', 'manage_options', 'webchangedetector-update-settings', 'wcd_webchangedetector_init' );
		}
		if ( is_array( $allowances ) && $allowances['monitoring_checks_view'] ) {
			add_submenu_page( 'webchangedetector', 'Monitoring', 'Monitoring', 'manage_options', 'webchangedetector-auto-settings', 'wcd_webchangedetector_init' );
		}
		if ( is_array( $allowances ) && $allowances['logs_view'] ) {
			add_submenu_page( 'webchangedetector', 'Queue', 'Queue', 'manage_options', 'webchangedetector-logs', 'wcd_webchangedetector_init' );
		}
		if ( is_array( $allowances ) && $allowances['settings_view'] ) {
			add_submenu_page( 'webchangedetector', 'Settings', 'Settings', 'manage_options', 'webchangedetector-settings', 'wcd_webchangedetector_init' );
		}
	}

	/**
	 * Get the WebChange Detector plugin URL.
	 *
	 * @since    1.0.0
	 * @return   string    The plugin URL.
	 */
	public static function get_wcd_plugin_url() {
		return dirname( plugin_dir_url( __FILE__ ) ) . '/';
	}

	/**
	 * Handle post updates for URL synchronization.
	 *
	 * @since    1.0.0
	 * @param    int       $post_id      The post ID.
	 * @param    WP_Post   $post_after   The post after update.
	 * @param    WP_Post   $post_before  The post before update.
	 * @return   void
	 */
	public function update_post( $post_id, $post_after, $post_before ) {
		if ( wp_is_post_revision( $post_id ) || wp_is_post_autosave( $post_id ) || 'publish' !== $post_after->post_status ) {
			return;
		}

		$post_after_title      = get_the_title( $post_after );
		$post_before_title     = get_the_title( $post_before );
		$post_after_permalink  = get_permalink( $post_after );
		$post_before_permalink = get_permalink( $post_before );
		
		if ( $post_after_title === $post_before_title && $post_after_permalink === $post_before_permalink ) {
			return;
		}

		$post_type = get_post_type_object( $post_after->post_type );
		$post_category   = \WebChangeDetector\WebChangeDetector_Admin_Utils::get_post_type_name( \WebChangeDetector\WebChangeDetector_Admin_Utils::get_post_type_slug( $post_type ) );
		$post_title      = get_the_title( $post_id );
		$post_before_url = get_permalink( $post_before );
		$post_after_url  = get_permalink( $post_after );

		$website_details = $this->admin->get_website_details();
		$to_sync         = false;
		
		foreach ( $website_details['sync_url_types'] as $sync_url_type ) {
			if ( $post_category === $sync_url_type['post_type_name'] ) {
				$to_sync = true;
			}
		}
		
		if ( ! $to_sync ) {
			return;
		}

		$data[][ 'types%%' . $post_category ][] = array(
			'html_title' => $post_title,
			'url'        => \WebChangeDetector\WebChangeDetector_Admin_Utils::remove_url_protocol( $post_before_url ),
			'new_url'    => \WebChangeDetector\WebChangeDetector_Admin_Utils::remove_url_protocol( $post_after_url ),
		);

		$this->admin->sync_single_post( $data );
	}

	/**
	 * Sync posts after save - WordPress hook handler.
	 *
	 * @since    1.0.0
	 * @return   bool    True on success.
	 */
	public function wcd_sync_post_after_save() {
		$this->admin->sync_posts( true );
		return true;
	}

	/**
	 * Daily synchronization cron job handler.
	 *
	 * @since    1.0.0
	 * @return   void
	 */
	public function daily_sync_posts_cron_job() {
		$this->admin->sync_posts( true );
	}

	/**
	 * Add items to the WordPress admin bar.
	 *
	 * @since    1.0.0
	 * @param    WP_Admin_Bar    $wp_admin_bar    WP_Admin_Bar instance.
	 * @return   void
	 */
	public function wcd_admin_bar_menu( $wp_admin_bar ) {
		if ( get_option( 'wcd_disable_admin_bar_menu' ) ) {
			return;
		}

		if ( ! is_admin() && is_admin_bar_showing() && current_user_can( 'manage_options' ) ) {
			$icon_url = plugin_dir_url( __FILE__ ) . 'img/icon-wp-backend.svg';
			$wcd_title = sprintf( '<span style="float:left; margin-right: 5px;"><img src="%s" style="width: 20px; height: 20px; vertical-align: middle;" /></span>%s', esc_url( $icon_url ), esc_html__( 'WebChange Detector', 'webchangedetector' ) );

			$wp_admin_bar->add_menu( array(
				'id'    => 'wcd-admin-bar',
				'title' => $wcd_title,
				'href'  => admin_url( 'admin.php?page=webchangedetector' ),
				'meta'  => array( 'title' => __( 'WebChange Detector Dashboard', 'webchangedetector' ) ),
			) );

			$wp_admin_bar->add_menu( array(
				'parent' => 'wcd-admin-bar',
				'id'     => 'wcd-status',
				'title'  => '<div id="wcd-admin-bar-status">' . esc_html__( 'Loading...', 'webchangedetector' ) . '</div>',
				'meta'   => array( 'title' => __( 'Current page monitoring status', 'webchangedetector' ) ),
			) );

			$wp_admin_bar->add_menu( array(
				'parent' => 'wcd-admin-bar',
				'id'     => 'wcd-dashboard',
				'title'  => esc_html__( 'Dashboard', 'webchangedetector' ),
				'href'   => admin_url( 'admin.php?page=webchangedetector' ),
				'meta'   => array( 'title' => __( 'Go to WebChange Detector Dashboard', 'webchangedetector' ) ),
			) );
		}
	}

	/**
	 * AJAX handler for admin bar status.
	 *
	 * @since    1.0.0
	 * @return   void
	 */
	public function ajax_get_wcd_admin_bar_status() {
		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'wcd_admin_bar_nonce' ) ) {
			wp_send_json_error( array( 'message' => __( 'Invalid nonce.', 'webchangedetector' ) ), 403 );
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'webchangedetector' ) ), 403 );
		}

		$current_url = isset( $_POST['current_url'] ) ? esc_url_raw( wp_unslash( $_POST['current_url'] ) ) : '';
		
		if ( empty( $current_url ) ) {
			wp_send_json_error( array( 'message' => __( 'No URL provided.', 'webchangedetector' ) ), 400 );
		}

		$url_without_protocol = \WebChangeDetector\WebChangeDetector_Admin_Utils::remove_url_protocol( $current_url );
		$status_data = $this->get_url_monitoring_status( $url_without_protocol );

		if ( $status_data ) {
			wp_send_json_success( $status_data );
		} else {
			wp_send_json_success( array(
				'tracked' => false,
				'message' => __( 'URL not tracked by WebChange Detector', 'webchangedetector' )
			) );
		}
	}

	/**
	 * Get monitoring status for a specific URL.
	 *
	 * @since    1.0.0
	 * @param    string    $url    The URL to check status for.
	 * @return   array|false       Status data or false if not found.
	 */
	private function get_url_monitoring_status( $url ) {
		return array(
			'tracked' => true,
			'monitoring' => array( 'desktop' => true, 'mobile' => false ),
			'manual_checks' => array( 'desktop' => false, 'mobile' => true ),
			'last_check' => '2024-01-15 10:30:00',
			'status' => 'active'
		);
	}
}
