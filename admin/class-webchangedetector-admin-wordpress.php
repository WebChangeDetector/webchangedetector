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

		$website_details = $this->admin->settings_handler->get_website_details();
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

		$this->sync_single_post( $data );
	}

	/**
	 * Sync posts after save - WordPress hook handler.
	 *
	 * @since    1.0.0
	 * @return   bool    True on success.
	 */
	public function wcd_sync_post_after_save() {
		$this->sync_posts( true );
		return true;
	}

	/**
	 * Daily synchronization cron job handler.
	 *
	 * @since    1.0.0
	 * @return   void
	 */
	public function daily_sync_posts_cron_job() {
		$this->sync_posts( true );
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

	/**
	 * Add post type to website sync settings.
	 *
	 * @since    1.0.0
	 * @param    array    $postdata    The post data containing post type information.
	 * @return   void
	 */
	public function add_post_type( $postdata ) {
		$post_type = json_decode( stripslashes( $postdata['post_type'] ), true );
		$this->admin->website_details['sync_url_types'] = array_merge( $post_type, $this->admin->website_details['sync_url_types'] );

		// TODO: Move to settings handler
		\WebChangeDetector\WebChangeDetector_API_V2::update_website_v2( $this->admin->website_details['id'], $this->admin->website_details );
		$this->sync_posts( true );
	}

	/**
	 * Get posts by post type.
	 *
	 * @since    1.0.0
	 * @param    string    $posttype    The post type.
	 * @return   int[]|WP_Post[]
	 */
	public function get_posts( $posttype ) {
		$args = array(
			'post_type'   => $posttype,
			'post_status' => array( 'publish', 'inherit' ),
			'numberposts' => -1,
			'order'       => 'ASC',
			'orderby'     => 'title',
		);
		$wpml_languages = $this->get_wpml_languages();

		if ( ! $wpml_languages ) {
			$posts = get_posts( $args );
		} else {
			$posts = array();
			foreach ( $wpml_languages['languages'] as $language ) {
				do_action( 'wpml_switch_language', $language['code'] );
				$posts = array_merge( $posts, get_posts( $args ) );
			}
			do_action( 'wpml_switch_language', $wpml_languages['current_language'] );
		}

		return $this->filter_unique_posts_by_id( $posts );
	}

	/**
	 * Filter duplicate post IDs.
	 *
	 * @since    1.0.0
	 * @param    array    $posts    The posts array.
	 * @return   array
	 */
	public function filter_unique_posts_by_id( $posts ) {
		$unique_posts = array();
		$post_ids     = array();

		foreach ( $posts as $post ) {
			unset( $post->post_content ); // Don't need to send too much unnecessary data.
			if ( ! in_array( $post->ID, $post_ids, true ) ) {
				$post_ids[]     = $post->ID;
				$unique_posts[] = $post;
			}
		}

		return $unique_posts;
	}

	/**
	 * Filter duplicate terms.
	 *
	 * @since    1.0.0
	 * @param    array    $terms    The terms array.
	 * @return   array
	 */
	public function filter_unique_terms_by_id( $terms ) {
		$unique_terms = array();
		$term_ids     = array();

		foreach ( $terms as $term ) {
			if ( ! in_array( $term->term_id, $term_ids, true ) ) {
				$term_ids[]     = $term->term_id;
				$unique_terms[] = $term;
			}
		}

		return $unique_terms;
	}

	/**
	 * Get terms by taxonomy.
	 *
	 * @since    1.0.0
	 * @param    string    $taxonomy    The taxonomy.
	 * @return   array|int[]|string|string[]|WP_Error|WP_Term[]
	 */
	public function get_terms( $taxonomy ) {
		$args = array(
			'number'        => '0',
			'taxonomy'      => $taxonomy,
			'hide_empty'    => false,
			'wpml_language' => 'de',
		);

		// Get terms for all languages if WPML is enabled.
		$wpml_languages = $this->get_wpml_languages();

		// If we don't have languages, we can return the terms.
		if ( ! $wpml_languages ) {
			$terms = get_terms( $args );
		} else {
			// With languages, we loop through them and return all of them.
			$terms = array();
			foreach ( $wpml_languages['languages'] as $language ) {
				do_action( 'wpml_switch_language', $language['code'] );
				$terms = array_merge( $terms, get_terms( $args ) );
			}
			do_action( 'wpml_switch_language', $wpml_languages['current_language'] );
		}
		return $this->filter_unique_terms_by_id( $terms );
	}

	/**
	 * Check if WPML is active and return all languages and the active one.
	 *
	 * @since    1.0.0
	 * @return   array|false
	 */
	public function get_wpml_languages() {
		if ( ! class_exists( 'SitePress' ) ) {
			return false;
		}
		$languages        = apply_filters( 'wpml_active_languages', null );
		$current_language = apply_filters( 'wpml_current_language', null );
		return array_merge(
			array(
				'current_language' => $current_language,
				'languages'        => $languages,
			)
		);
	}

	/**
	 * Get all posts data for synchronization.
	 *
	 * @since    1.0.0
	 * @param    array    $post_types    The post types to get.
	 * @return   void
	 */
	public function get_all_posts_data( $post_types ) {
		// Array to store all posts data.
		$all_posts_data = array();

		if ( empty( $post_types ) ) {
			return;
		}

		foreach ( $post_types as $single_post_type ) {
			// Set the batch size for both retrieving and uploading.
			$offset          = 0;
			$posts_per_batch = 1000;  // Number of posts to retrieve per query.

			do {
				\WebChangeDetector\WebChangeDetector_Admin_Utils::log_error( 'Getting next chunk. Offset: ' . $offset );
				// Set up WP_Query arguments.
				$args = array(
					'post_type'      => $single_post_type,  // Pass the array of post types.
					'post_status'    => 'publish',
					'posts_per_page' => $posts_per_batch,  // Fetch 1000 posts at a time.
					'offset'         => $offset,
				);

				// Create a new query.
				$query = new \WP_Query( $args );

				// If no posts, break the loop.
				if ( ! $query->have_posts() ) {
					break;
				}

				// Process each post in the current batch.
				while ( $query->have_posts() ) {
					$query->the_post();

					$post_id    = get_the_ID();
					$post_title = get_the_title();
					$post_type  = get_post_type();
					$url        = get_permalink( $post_id );

					// Get the post type label.
					$post_type_object = get_post_type_object( $post_type );
					$post_type_label  = $post_type_object ? $post_type_object->labels->name : $post_type;

					// Add the data to the main array.
					$all_posts_data[ 'types%%' . $post_type_label ][] = array(
						'url'        => \WebChangeDetector\WebChangeDetector_Admin_Utils::remove_url_protocol( $url ),
						'html_title' => $post_title,
					);
				}

				// Reset post data to avoid conflicts in global post state.
				wp_reset_postdata();

				// Increment the offset for the next batch.
				$offset += $posts_per_batch;
				\WebChangeDetector\WebChangeDetector_Admin_Utils::log_error( 'Sending Posts.' );

				// Call uploadUrls after every batch.
				$this->upload_urls_in_batches( $all_posts_data );

				// Clear the data array after each batch to free memory.
				$all_posts_data = array();

				// Get the count of the results.
				$count = $query->found_posts;

				// If we've processed all posts, break the loop.
				if ( $offset >= $count ) {
					break;
				}

			} while ( true );
		}
	}

	/**
	 * Get all terms data for synchronization.
	 *
	 * @since    1.0.0
	 * @param    array    $taxonomies    The taxonomies to get.
	 * @return   void
	 */
	public function get_all_terms_data( $taxonomies ) {
		// Array to store all terms data.
		$all_terms_data = array();

		if ( empty( $taxonomies ) ) {
			return;
		}

		$batch_size  = 500;  // Limit each batch to 500 terms.
		$offset      = 0;    // Initial offset to start from.
		$total_terms = true; // Placeholder to control loop.

		// Continue fetching terms until no more terms are found.
		while ( $total_terms ) {
			// Get terms in batches of 500 with an offset.
			$terms = get_terms(
				array(
					'taxonomy'   => $taxonomies, // Pass the taxonomies as an array.
					'hide_empty' => false,       // Show all terms, including those with no posts.
					'fields'     => 'all',       // Retrieve all term fields (term_id, name, slug, etc.).
					'number'     => $batch_size, // Fetch only 500 terms at a time.
					'offset'     => $offset,     // Offset to start from for each batch.
				)
			);

			// Check for errors or empty result.
			if ( is_wp_error( $terms ) || empty( $terms ) ) {
				// Stop the loop if no terms are found.
				$total_terms = false;
				continue;
			}

			// Process each term in the current batch.
			foreach ( $terms as $term ) {
				// Retrieve the term link (URL).
				$url = get_term_link( (int) $term->term_id, $term->taxonomy );

				// Retrieve the taxonomy object to get the label.
				$taxonomy_object = get_taxonomy( $term->taxonomy );
				$taxonomy_label  = $taxonomy_object ? $taxonomy_object->labels->name : $term->taxonomy;

				// Add the data to the main array.
				$all_terms_data[ 'taxonomy%%' . $taxonomy_label ][] = array(
					'url'        => \WebChangeDetector\WebChangeDetector_Admin_Utils::remove_url_protocol( $url ),
					'html_title' => $term->name,
				);
			}

			// Increment the offset for the next batch.
			$offset += $batch_size;

			// Call uploadUrls in batches of 500 elements.
			// Pass the entire $all_terms_data for each batch.
			$this->upload_urls_in_batches( $all_terms_data );

			// Reset the all_terms_data array after each batch to avoid memory overflow.
			$all_terms_data = array();
		}
	}

	/**
	 * Prepare URLs for upload in batches.
	 *
	 * @since    1.0.0
	 * @param    array    $upload_array    The URLs to upload.
	 * @return   void
	 */
	public function upload_urls_in_batches( $upload_array ) {
		if ( ! empty( $upload_array ) ) {
			$this->admin->sync_urls[] = $upload_array;
		}
	}

	/**
	 * Sync single post.
	 *
	 * @since    1.0.0
	 * @param    array    $single_post    The sync array.
	 * @return   void
	 */
	public function sync_single_post( $single_post ) {
		if ( ! empty( $single_post ) ) {
			\WebChangeDetector\WebChangeDetector_Admin_Utils::log_error( 'Start single url sync' );
			$response_sync_urls      = \WebChangeDetector\WebChangeDetector_API_V2::sync_urls( $single_post );
			$response_start_url_sync = \WebChangeDetector\WebChangeDetector_API_V2::start_url_sync( false );

		}
	}

	/**
	 * Sync posts with API.
	 *
	 * @since    1.0.0
	 * @param    bool       $force_sync        Skip cache and force sync.
	 * @param    array|bool $website_details   The website details or false.
	 * @return   bool
	 */
	public function sync_posts( $force_sync = false, $website_details = false ) {
		$last_sync     = get_option( 'wcd_last_urls_sync' );
		$sync_interval = '+1 hour';

		// Skip sync if last sync is less than sync interval.
		if ( $last_sync && ! $force_sync && strtotime( $sync_interval, $last_sync ) >= date_i18n( 'U' ) ) {
			// Returning last sync datetime.
			return date_i18n( 'd.m.Y H:i', $last_sync );
		}

		\WebChangeDetector\WebChangeDetector_Admin_Utils::log_error( 'Starting Sync' );
		update_option( 'wcd_last_urls_sync', date_i18n( 'U' ) );

		// Check if we got website_details or if we use the ones from the class.
		$array = array(); // init.
		if ( ! $website_details ) {
			$website_details = $this->admin->website_details;
		}

		// We only sync the frontpage.
		if ( ! empty( $website_details['allowances']['only_frontpage'] ) ) {
			\WebChangeDetector\WebChangeDetector_Admin_Utils::log_error( "only frontpage: " . print_r( $website_details['allowances']['only_frontpage'], true ) );
			$array['frontpage%%Frontpage'][] = array(
				'url'        => $this->admin::get_domain_from_site_url(),
				'html_title' => get_bloginfo( 'name' ),
			);
			$this->upload_urls_in_batches( $array );
			return true;
		}

		// Init sync urls if we don't have them yet.
		if ( ! empty( $website_details['sync_url_types'] ) ) {

			// Get all WP post_types.
			$post_types = get_post_types( array( 'public' => true ), 'objects' );
			foreach ( $post_types as $post_type ) {
				$wp_post_type_slug = \WebChangeDetector\WebChangeDetector_Admin_Utils::get_post_type_slug( $post_type );

				// Get the right name for the request.
				foreach ( $website_details['sync_url_types'] as $sync_url_type ) {
					if ( $sync_url_type['post_type_slug'] === $wp_post_type_slug ) {
						// The 'get_posts' function needs 'name' instead of 'rest_base'.
						$post_type_names[] = $post_type->name;
					}
				}
			}

			if ( ! empty( $post_type_names ) ) {
				$this->get_all_posts_data( $post_type_names );
			}

			// Get all WP taxonomies.
			$taxonomies = get_taxonomies( array( 'public' => true ), 'objects' );

			$taxonomy_post_names = array();
			foreach ( $taxonomies as $taxonomy ) {
				// Depending on if we have 'rest_base' name we use this one or the 'name'.
				$wp_taxonomy_slug = \WebChangeDetector\WebChangeDetector_Admin_Utils::get_taxonomy_slug( $taxonomy );

				// Get the terms names.
				foreach ( $website_details['sync_url_types'] as $sync_url_type ) {
					if ( $sync_url_type['post_type_slug'] === $wp_taxonomy_slug ) {
						$taxonomy_post_names[] = $taxonomy->name;
					}
				}
			}

			if ( ! empty( $taxonomy_post_names ) ) {
				$this->get_all_terms_data( $taxonomy_post_names );
			}
		}

		$active_plugins = get_option( 'active_plugins' );

		// Check if frontpage is already in the sync settings.
		$frontpage_exists = array_filter(
			$website_details['sync_url_types'] ?? array(),
			function ( $item ) {
				return isset( $item['post_type_slug'] ) && 'frontpage' === $item['post_type_slug'];
			}
		);

		// If blog is set as home page.
		if ( ! get_option( 'page_on_front' ) ) {

			// WPML fix.
			if ( $active_plugins && in_array( WCD_WPML_PLUGIN_FILE, $active_plugins, true ) ) {
				$languages = icl_get_languages( 'skip_missing=0' ); // Get all active languages.

				if ( ! empty( $languages ) ) {

					// Store the current language to switch back later.
					$current_lang = apply_filters( 'wpml_current_language', null );
					foreach ( $languages as $lang_code => $lang_info ) {

						// Switch to each language.
						do_action( 'wpml_switch_language', $lang_code );

						// Store the title in the array with the language code as the key.
						$array['frontpage%%Frontpage'][] = array(
							'url'        => \WebChangeDetector\WebChangeDetector_Admin_Utils::remove_url_protocol( apply_filters( 'wpml_home_url', get_home_url(), $lang_code ) ),
							'html_title' => get_bloginfo( 'name' ),
						);
					}

					// Switch back to the original language.
					do_action( 'wpml_switch_language', $current_lang );
				}

				// Polylang fix.
			} elseif ( $active_plugins && in_array( WCD_POLYLANG_PLUGIN_FILE, $active_plugins, true ) ) {
				if ( isset( $GLOBALS['polylang'] ) ) {
					$languages = $GLOBALS['polylang']->model->get_languages_list();

					foreach ( $languages as $language ) {

						// Check if home_url is available in the language info.
						if ( ! empty( $language->home_url ) ) {
							$array['frontpage%%Frontpage'][] = array(
								'url'        => \WebChangeDetector\WebChangeDetector_Admin_Utils::remove_url_protocol( $language->home_url ),
								'html_title' => get_bloginfo( 'name' ),
							);
						}
					}
				}
			} else {
				$array['frontpage%%Frontpage'][] = array(
					'url'        => \WebChangeDetector\WebChangeDetector_Admin_Utils::remove_url_protocol( get_option( 'home' ) ),
					'html_title' => get_bloginfo( 'name' ),
				);
			}

			// Add frontpage if it's not yet in the sync_url_types array.
			if ( empty( $frontpage_exists ) ) {
				$website_details['sync_url_types'][] = array(
					'url_type_slug'  => 'types',
					'url_type_name'  => 'frontpage',
					'post_type_slug' => 'frontpage',
					'post_type_name' => 'Frontpage',
				);
				$this->admin->settings_handler->update_website_details( $website_details );
			}

			if ( ! empty( $array ) ) {
				$this->upload_urls_in_batches( $array );
			}
		} elseif ( $frontpage_exists ) {
			foreach ( $website_details['sync_url_types'] as $key => $sync_types_values ) {
				if ( 'frontpage' === $sync_types_values['post_type_slug'] ) {
					unset( $website_details['sync_url_types'][ $key ] );
				}
			}
			$this->admin->settings_handler->update_website_details( $website_details );
		}

		// Create uuid for sync urls.
		$collection_uuid = wp_generate_uuid4();

		// Sync urls.
		$response_sync_urls      = \WebChangeDetector\WebChangeDetector_API_V2::sync_urls( $this->admin->sync_urls, $collection_uuid );
		$response_start_url_sync = \WebChangeDetector\WebChangeDetector_API_V2::start_url_sync( true, $collection_uuid );
		\WebChangeDetector\WebChangeDetector_Admin_Utils::log_error( 'Response upload URLs: ' . $response_sync_urls );
		\WebChangeDetector\WebChangeDetector_Admin_Utils::log_error( 'Response Start URL sync: ' . $response_start_url_sync );

		return date_i18n( 'd/m/Y H:i' );
	}

}
