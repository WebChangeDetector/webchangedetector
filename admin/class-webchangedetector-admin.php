<?php
/**
 * The admin-specific functionality of the plugin.
 *
 * @link       wp-mike.com
 * @since      1.0.0
 *
 * @package    WebChangeDetector
 * @subpackage WebChangeDetector/admin
 */

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    WebChangeDetector
 * @subpackage WebChangeDetector/admin
 * @author     Mike Miler <mike@wp-mike.com>
 */
class WebChangeDetector_Admin {

	const API_TOKEN_LENGTH = 10;
	const PRODUCT_ID_FREE  = 57;
	const LIMIT_QUEUE_ROWS = 50;

	const VALID_WCD_ACTIONS = array(
		'reset_api_token',
		're-add-api-token',
		'save_api_token',
		'take_screenshots',
		'post_urls',
		'post_urls_update_and_auto',
		'dashboard',
		'change-detections',
		'auto-settings',
		'logs',
		'settings',
		'show-compare',
		'create_free_account',
		'update_detection_step',
		'save_update_settings_and_continue',
		'add_post_type',
	);

	const VALID_SC_TYPES = array(
		'pre',
		'post',
		'auto',
		'compare',
	);

	const VALID_GROUP_TYPES = array(
		'all', // filter.
		'generic', // filter.
		'wordpress', // filter.
		'auto',
		'update',
	);

	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string $plugin_name The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The manual checks group.
	 *
	 * @since    1.0.0
	 * @access   public
	 * @var      int $group_id The manual checks group id.
	 */
	public $group_id;

	/**
	 * The monitoring checks group uuid.
	 *
	 * @since    1.0.0
	 * @access   public
	 * @var      int $monitoring_group_uuid The manual checks group uuid.
	 */
	public $monitoring_group_uuid;

	/**
	 * The manual checks group uuid.
	 *
	 * @since    1.0.0
	 * @access   public
	 * @var      int $manual_group_uuid The manual checks group uuid.
	 */
	public $manual_group_uuid;

	/**
	 * The auto checks group id.
	 *
	 * @since    1.0.0
	 * @access   public
	 * @var      int $group_id The manual checks group id.
	 */
	public $monitoring_group_id;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string $version The current version of this plugin.
	 */
	private $version = WEBCHANGEDETECTOR_VERSION;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @param string $plugin_name The name of this plugin.
	 * @param string $version The version of this plugin.
	 * @since    1.0.0
	 */
	public function __construct( $plugin_name = 'WebChangeDetector' ) {
		$this->plugin_name = $plugin_name;
		$this->set_website_details();
		$this->monitoring_group_uuid = ! empty( $this->website_details['auto_detection_group']['uuid'] ) ? $this->website_details['auto_detection_group']['uuid'] : null;
		$this->manual_group_uuid     = ! empty( $this->website_details['manual_detection_group']['uuid'] ) ? $this->website_details['manual_detection_group']['uuid'] : null;
	}

	/**
	 * Register the stylesheets for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles() {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in WebChangeDetector_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The WebChangeDetector_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */
		wp_enqueue_style( 'jquery-ui-accordion' );
		wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/webchangedetector-admin.css', array(), $this->version, 'all' );
		wp_enqueue_style( 'twentytwenty-css', plugin_dir_url( __FILE__ ) . 'css/twentytwenty.css', array(), $this->version, 'all' );
		wp_enqueue_style( 'wp-codemirror' );
		// wp_enqueue_style('codemirror-darcula', plugin_dir_url(__FILE__) . 'css/darcula.css', array(), $this->version, 'all');
	}

	/**
	 * Register the JavaScript for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts() {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in WebChangeDetector_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The WebChangeDetector_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/webchangedetector-admin.js', array( 'jquery' ), $this->version, false );
		wp_enqueue_script( 'jquery-ui-accordion' );
		wp_enqueue_script( 'twentytwenty-js', plugin_dir_url( __FILE__ ) . 'js/jquery.twentytwenty.js', array( 'jquery' ), $this->version, false );
		wp_enqueue_script( 'twentytwenty-move-js', plugin_dir_url( __FILE__ ) . 'js/jquery.event.move.js', array( 'jquery' ), $this->version, false );
		// Load WP codemirror.
		$css_settings = array(
			'type' => 'text/css',
						// 'codemirror' => array('theme' =>'darcula')
		);
		$cm_settings['codeEditor'] = wp_enqueue_code_editor( $css_settings );
		wp_localize_script( 'jquery', 'cm_settings', $cm_settings );
		// wp_enqueue_script('wp-theme-plugin-editor');
	}

	public $website_details;

	// Add WCD to backend navigation (called by hook in includes/class-webchangedetector.php).
	public function wcd_plugin_setup_menu() {
		require_once 'partials/webchangedetector-admin-display.php';
		add_menu_page(
			'WebChangeDetector',
			'WCD',
			'manage_options',
			'webchangedetector',
			'wcd_webchangedetector_init',
			plugin_dir_url( __FILE__ ) . 'img/icon-wp-backend.svg'
		);

		add_submenu_page(
			'webchangedetector',
			'Dashboard',
			'Dashboard',
			'manage_options',
			'webchangedetector',
			'wcd_webchangedetector_init'
		);
		add_submenu_page(
			'webchangedetector',
			'Change Detections',
			'Change Detections',
			'manage_options',
			'webchangedetector-change-detections',
			'wcd_webchangedetector_init'
		);
		add_submenu_page(
			'webchangedetector',
			'Manual Checks',
			'Manual Checks',
			'manage_options',
			'webchangedetector-update-settings',
			'wcd_webchangedetector_init'
		);
		add_submenu_page(
			'webchangedetector',
			'Monitoring',
			'Monitoring',
			'manage_options',
			'webchangedetector-auto-settings',
			'wcd_webchangedetector_init'
		);
		add_submenu_page(
			'webchangedetector',
			'Logs',
			'Logs',
			'manage_options',
			'webchangedetector-logs',
			'wcd_webchangedetector_init'
		);
		add_submenu_page(
			'webchangedetector',
			'Settings',
			'Settings',
			'manage_options',
			'webchangedetector-settings',
			'wcd_webchangedetector_init'
		);
		add_submenu_page(
			'webchangedetector',
			'Upgrade Account',
			'Upgrade Account',
			'manage_options',
			$this->get_upgrade_url()
		);
		add_submenu_page(
			null,
			'Show Change Detection',
			'Show Change Detection',
			'manage_options',
			'webchangedetector-show-detection',
			'wcd_webchangedetector_init'
		);
		add_submenu_page(
			null,
			'Show Screenshot',
			'Show Screenshot',
			'manage_options',
			'webchangedetector-show-screenshot',
			'wcd_webchangedetector_init'
		);
	}

	public function get_wcd_plugin_url() {
		return plugin_dir_url( __FILE__ ) . '../';
	}

	public function create_free_account( $postdata ) {

		// Generate validation string.
		$validation_string = wp_generate_password( 40 );
		update_option( 'webchangedetector_verify_secret', $validation_string, false );

		$args = array_merge(
			array(
				'action'            => 'add_free_account',
				'ip'                => $_SERVER['SERVER_ADDR'],
				'domain'            => $_SERVER['SERVER_NAME'],
				'validation_string' => $validation_string,
				'cms'               => 'wp',
			),
			$postdata
		);
		return $this->mm_api( $args, true );
	}

	public function save_api_token( $api_token ) {

		if ( ! is_string( $api_token ) || strlen( $api_token ) < self::API_TOKEN_LENGTH ) {
			if ( is_array( $api_token ) && $api_token[0] === 'error' && ! empty( $api_token[1] ) ) {
				echo '<div class="notice notice-error"><p>' . $api_token[1] . '</p></div>';
			} else {
				echo '<div class="notice notice-error">
                        <p>The API Token is invalid. Please try again or contact us if the error persists</p>
                        </div>';
			}
			echo $this->get_no_account_page();
			return false;
		}

		// Save email address on account creation for showing on activate account page.
		if ( ! empty( $_POST['email'] ) ) {
			update_option( WCD_WP_OPTION_KEY_ACCOUNT_EMAIL, sanitize_email( $_POST['email'] ), false );
		}
		update_option( WCD_WP_OPTION_KEY_API_TOKEN, sanitize_text_field( $api_token ), false );

		return true;
	}

	// Sync Post if permalink changed. Called by hook in class-webchangedetector.php.
	public function sync_post_after_save( $post_id, $post, $update ) {
		// Only sync posts and pages @TODO make setting to sync other posttypes.
		if ( ! empty( $post->post_type ) && ! in_array( $post->post_type, array( 'page', 'post' ) ) ) {
			return false;
		}

		if ( $update ) {
			$latest_revision = array_shift( wp_get_post_revisions( $post_id ) );
			if ( $latest_revision && get_permalink( $latest_revision ) !== get_permalink( $post ) ) {
				return $this->sync_posts( $post );
			}
		} else {
			return $this->sync_posts( $post );
		}
		return false;
	}

	public function account_details() {
		static $account_details;
		if ( $account_details && $account_details !== 'unauthorized' && $account_details !== 'activate account' ) {
			return $account_details;
		}

		$args            = array(
			'action' => 'account_details',
		);
		$account_details = $this->mm_api( $args );
		return $account_details;
	}

	public function ajax_get_processing_queue() {
		echo $this->get_processing_queue();
		die();
	}

	public function get_processing_queue() {
		return $this->mm_api( array( 'action' => 'get_not_closed_queue' ) );
	}

	public function get_monitoring_settings( $group_id ) {
		// Deprecated.
		$args = array(
			'action'   => 'get_monitoring_settings',
			'group_id' => $group_id,
		);

		return $this->mm_api( $args );
	}

	public function get_comparison_partial( $token ) {
		// Deprecated.
		$args = array(
			'action' => 'get_comparison_partial',
			'token'  => $token, // token for comparison partial, not api_token.
		);
		return $this->mm_api( $args );
	}

	public function update_monitoring_settings( $postdata, $monitoring_group_id ) {

		$monitoring_settings = $this->get_monitoring_settings( $monitoring_group_id );

		$args = array(
			'action'        => 'update_group',
			'group_id'      => sanitize_key( $monitoring_group_id ),
			'hour_of_day'   => ! isset( $postdata['hour_of_day'] ) ? $monitoring_settings['hour_of_day'] : sanitize_key( $postdata['hour_of_day'] ),
			'interval_in_h' => ! isset( $postdata['interval_in_h'] ) ? $monitoring_settings['interval_in_h'] : sanitize_text_field( $postdata['interval_in_h'] ),
			'monitoring'    => 1,
			'enabled'       => ! isset( $postdata['enabled'] ) ? $monitoring_settings['enabled'] : sanitize_key( $postdata['enabled'] ),
			'alert_emails'  => ! isset( $postdata['alert_emails'] ) ? $monitoring_settings['alert_emails'] : sanitize_textarea_field( $postdata['alert_emails'] ),
			'name'          => ! isset( $postdata['name'] ) ? $monitoring_settings['name'] : sanitize_text_field( $postdata['name'] ),
			'css'           => ! isset( $postdata['css'] ) ? $monitoring_settings['css'] : sanitize_textarea_field( $postdata['css'] ),
			'threshold'     => ! isset( $postdata['threshold'] ) ? $monitoring_settings['threshold'] : sanitize_text_field( $postdata['threshold'] ),
		);
		return $this->mm_api( $args );
	}

	public function update_settings( $postdata, $group_id ) {
		$args = array(
			'action'    => 'update_group',
			'group_id'  => $group_id,
			'name'      => $postdata['group_name'],
			'css'       => sanitize_textarea_field( $postdata['css'] ), // there is no css sanitation.
			'threshold' => sanitize_text_field( $postdata['threshold'] ),
		);

		error_log( json_encode( $postdata ) );
		$auto_update_settings = array();
		foreach ( $postdata as $key => $value ) {
			if ( 0 === strpos( $key, 'auto_update_checks_' ) ) {
				$auto_update_settings[ $key ] = $value;
			}
		}
		update_option( 'wcd_auto_update_settings', $auto_update_settings );
		return $this->mm_api( $args );
	}

	public function get_upgrade_url() {
		$account_details = $this->account_details();
		if ( ! is_array( $account_details ) ) {
			return false;
		}
		return $this->billing_url() . '?secret=' . $account_details['magic_login_secret'];
	}

	/**
	 * `<span>` with icon
	 *
	 * TODO make switch-case
	 */
	public function get_device_icon( $icon, $class = '' ) {
		if ( 'thumbnail' === $icon ) {
			return '<span class="dashicons dashicons-camera-alt"></span>';
		}
		if ( 'desktop' === $icon ) {
			return '<span class="group_icon ' . $class . ' dashicons dashicons-laptop"></span>';
		}
		if ( 'mobile' === $icon ) {
			return '<span class="group_icon ' . $class . ' dashicons dashicons-smartphone"></span>';
		}
		if ( 'page' === $icon ) {
			return '<span class="group_icon ' . $class . ' dashicons dashicons-media-default"></span>';
		}
		if ( 'change-detections' === $icon ) {
			return '<span class="group_icon ' . $class . ' dashicons dashicons-welcome-view-site"></span>';
		}
		if ( 'dashboard' === $icon ) {
			return '<span class="group_icon ' . $class . ' dashicons dashicons-admin-home"></span>';
		}
		if ( 'logs' === $icon ) {
			return '<span class="group_icon ' . $class . ' dashicons dashicons-menu-alt"></span>';
		}
		if ( 'settings' === $icon ) {
			return '<span class="group_icon ' . $class . ' dashicons dashicons-admin-generic"></span>';
		}
		if ( 'website-settings' === $icon ) {
			return '<span class="group_icon ' . $class . ' dashicons dashicons-welcome-widgets-menus"></span>';
		}
		if ( 'help' === $icon ) {
			return '<span class="group_icon ' . $class . ' dashicons dashicons-editor-help"></span>';
		}
		if ( 'auto-group' === $icon ) {
			return '<span class="group_icon ' . $class . ' dashicons dashicons-clock"></span>';
		}
		if ( 'update-group' === $icon ) {
			return '<span class="group_icon ' . $class . ' dashicons dashicons-admin-page"></span>';
		}
		if ( 'trash' === $icon ) {
			return '<span class="group_icon ' . $class . ' dashicons dashicons-trash"></span>';
		}
		if ( 'check' === $icon ) {
			return '<span class="group_icon ' . $class . ' dashicons dashicons-yes-alt"></span>';
		}
		if ( 'upgrade' === $icon ) {
			return '<span class="group_icon ' . $class . ' dashicons dashicons-cart"></span>';
		}

		return '';
	}

	public function get_compares( $group_ids, $limit_days = null, $group_type = null, $difference_only = null, $limit_compares = null, $batch_id = null ) {
		$args     = array(
			'action'          => 'get_compares_by_group_ids',
			'limit_days'      => $limit_days,
			'group_type'      => $group_type,
			'difference_only' => $difference_only,
			'limit_compares'  => $limit_compares,
			'group_ids'       => json_encode( array( $group_ids ) ),
			'batch_id'        => $batch_id,
		);
		$compares = $this->mm_api( $args );

		$return = array();
		if ( ! array_key_exists( 0, $compares ) ) {
			return $return;
		}

		foreach ( array_filter(
			$compares,
			function ( $compare ) {
				// Make sure to only show urls from the website. Shouldn't come from the API anyway.
				return strpos( $compare['screenshot1']['url'], $_SERVER['SERVER_NAME'] ) !== false;
			}
		) as $compare ) {
			$return[] = $compare;
		}
		return $return;
	}

	public function compare_view( $compares, $latest_batch = false ) {
		?>
		<table class="toggle" style="width: 100%">
			<tr>
				<th width="auto">URL</th>
				<th width="150px">Compared Screenshots</th>
				<th width="50px">Difference</th>
				<th>Show</th>
			</tr>
		<?php if ( empty( $compares ) ) { ?>
			<tr>
				<td colspan="4" style="text-align: center">
					<strong>There are no change detections yet.</strong
				</td>
			</tr>
			<?php
		} else {
			$all_tokens = array();
			foreach ( $compares as $compare ) {
				$all_tokens[] = $compare['token'];

			}
			$latest_batch_id = $compares[0]['screenshot1']['queue']['batch_id'];
			foreach ( $compares as $compare ) {
				if ( $latest_batch && $compare['screenshot1']['queue']['batch_id'] !== $latest_batch_id ) {
					continue;
				}
				$class = 'no-difference'; // init.
				if ( $compare['difference_percent'] ) {
					$class = 'is-difference';
				}
				?>
			<tr>
				<td>
					<strong>
					<?php
					if ( ! empty( $compare['screenshot1']['queue']['url']['html_title'] ) ) {
						echo esc_html( $compare['screenshot1']['queue']['url']['html_title'] ) . '<br>';
					}
					?>
					</strong>
					<?php echo $this->get_device_icon( $compare['screenshot1']['device'] ) . $compare['screenshot1']['url']; ?><br>
					<?php echo $compare['screenshot2']['sc_type'] === 'auto' ? $this->get_device_icon( 'auto-group' ) . 'Monitoring' : $this->get_device_icon( 'update-group' ) . 'Manual Checks'; ?>
				</td>
				<td>
					<div class="local-time" data-date="<?php echo $compare['image1_timestamp']; ?>"></div>
					<div class="local-time" data-date="<?php echo $compare['image2_timestamp']; ?>"></div>
				</td>
				<td class="<?php echo $class; ?> diff-tile" data-diff_percent="<?php echo $compare['difference_percent']; ?>"><?php echo $compare['difference_percent']; ?>%</td>
				<td>
					<form action="?page=webchangedetector-show-detection" method="post">
						<input type="hidden" name="token" value="<?php echo $compare['token']; ?>">
						<input type="hidden" name="all_tokens" value='<?php echo json_encode( $all_tokens ); ?>'>
						<input type="submit" value="Show" class="button">
					</form>
				</td>
			</tr>
				<?php
			}
		}
		?>
		</table>
		<?php
	}

	function get_comparison_by_token( $postdata, $hide_switch = false, $whitelabel = false ) {
		$token = $postdata['token'] ?? null;
		if ( ! $token && ! empty( $_GET['token'] ) ) {
			$token = $_GET['token'];
		}
		if ( isset( $token ) ) {
			$args       = array(
				'action' => 'get_comparison_by_token',
				'token'  => $token,
			);
			$compare    = $this->mm_api( $args ); // used in template.
			$all_tokens = array();
			if ( ! empty( $postdata['all_tokens'] ) ) {
				$all_tokens = ( json_decode( stripslashes( $postdata['all_tokens'] ), true ) );

				$before_current_token = array();
				$after_current_token  = array();
				$is_after             = false;
				foreach ( $all_tokens as $current_token ) {
					if ( $current_token !== $token ) {
						if ( $is_after ) {
							$after_current_token[] = $current_token;
						} else {
							$before_current_token[] = $current_token;
						}
					} else {
						$is_after = true;
					}
				}
			}
			ob_start();
			if ( ! $hide_switch ) {
				echo '<style>#comp-switch {display: none !important;}</style>';
			}
			echo '<div style="padding: 0 20px;">';
			if ( ! $whitelabel ) {
				echo '<style>.public-detection-logo {display: none;}</style>';
			}
			$before_token = ! empty( $before_current_token ) ? $before_current_token[ max( array_keys( $before_current_token ) ) ] : null;
			$after_token  = $after_current_token[0] ?? null;
			?>
			<!-- Previous and next buttons -->
			<div style="width: 100%; margin-bottom: 20px; text-align: center">
				<form method="post" >
					<input type="hidden" name="all_tokens" value='<?php echo json_encode( $all_tokens ); ?>'>
					<button class="button" type="submit" name="token"
							value="<?php echo $before_token ?? null; ?>" <?php echo ! $before_token ? 'disabled' : ''; ?>> < Previous </button>
					<button class="button" type="submit" name="token"
							value="<?php echo $after_token ?? null; ?>" <?php echo ! $after_token ? 'disabled' : ''; ?>> Next > </button>
				</form>
			</div>
			<?php
			include 'partials/templates/show-change-detection.php';
			echo '</div>';
			return ob_get_clean();
		}
		return '<p class="notice notice-error" style="padding: 10px;">Ooops! There was no change detection selected. Please go to 
                <a href="?page=webchangedetector-change-detections">Change Detections</a> and select a change detection
                to show.</p>';
	}

	function get_screenshot( $postdata = false ) {
		if ( ! isset( $postdata['img_url'] ) ) {
			return '<p class="notice notice-error" style="padding: 10px;">
                    Sorry, we couldn\'t find the screenshot. Please try again.</p>';
		}
		return '<div style="width: 100%; text-align: center;"><img style="max-width: 100%" src="' . $postdata['img_url'] . '"></div>';
	}

	public function get_queue() {
		$args = array(
			'action' => 'get_queue',
			'status' => json_encode( array( 'open', 'done', 'processing', 'failed' ) ),
			'limit'  => $_GET['limit'] ?? $this::LIMIT_QUEUE_ROWS,
			'offset' => $_GET['offset'] ?? 0,
		);
		return $this->mm_api( $args );
	}

	public function add_post_type( $postdata ) {
		$post_type                               = json_decode( stripslashes( $postdata['post_type'] ), true );
		$existing_post_types                     = $this->website_details['sync_url_types'];
		$this->website_details['sync_url_types'] = array_merge( $post_type, $existing_post_types );

		// $website_details['action'] = 'save_user_website';
		$this->mm_api( array_merge( array( 'action' => 'save_user_website' ), $this->website_details ) );
	}

	public function get_posts( $posttype ) {
		$args = array(
			'post_type'   => $posttype,
			'post_status' => array( 'publish', 'inherit' ),
			'numberposts' => -1,
			'order'       => 'ASC',
			'orderby'     => 'title',
		);
		return get_posts( $args );
	}
	public function get_terms( $taxonomy ) {
		$args = array(
			'number'        => '0',
			'taxonomy'      => $taxonomy,
			'hide_empty'    => false,
			'wpml_language' => 'de',
		);

		// Get terms for all languages if WPML is enabled.
		if ( class_exists( 'SitePress' ) ) {
			$languages        = apply_filters( 'wpml_active_languages', null );
			$terms            = array();
			$current_language = apply_filters( 'wpml_current_language', null );
			foreach ( $languages as $language ) {
				do_action( 'wpml_switch_language', $language['code'] );
				$terms = array_merge( $terms, get_terms( $args ) );
			}
			do_action( 'wpml_switch_language', $current_language );
		} else {
			$terms = get_terms( $args );
		}

		return $terms;
	}

	public function sync_posts( $post_obj = false ) {
		$array     = array(); // init.
		$url_types = array();

		// Sync single post.
		if ( $post_obj ) {
			$save_post_types = array( 'post', 'page' ); // @TODO Make this a setting.
			if ( in_array( $post_obj->post_type, $save_post_types ) && get_post_status( $post_obj ) === 'publish' ) {
				$url           = get_permalink( $post_obj );
				$start         = strpos( $url, '//' ) + strlen( '//' );
				$url           = substr( $url, $start ); // remove evertyhing after http[s]://.
				$post_type_obj = get_post_type_object( $post_obj->post_type );

				$array[] = array(
					'url'             => $url,
					'html_title'      => $post_obj->post_title,
					'cms_resource_id' => $post_obj->ID,
					'url_type'        => 'types',
					'url_category'    => $post_obj->label,
				);
			}
			if ( ! empty( $array ) ) {
				$args = array(
					'action'              => 'sync_urls',
					'delete_missing_urls' => false,
					'posts'               => json_encode( $array ),
				);
				return $this->mm_api( $args );
			}
		} else {
			// Get Post Types.
			$post_types = get_post_types( array( 'public' => true ), 'objects' );
			foreach ( $post_types as $post_type ) {

				// if rest_base is not set we use post_name (wp default).
				if ( ! $post_type->rest_base ) {
					$post_type->rest_base = $post_type->name;
				}

				foreach ( $this->website_details['sync_url_types'] as $sync_url_type ) {
					if ( $post_type->rest_base && $sync_url_type['post_type_slug'] === $post_type->rest_base ) {
						$url_types['types'][ $post_type->rest_base ] = $this->get_posts( $post_type->name );
					}
				}
			}

			// Get Taxonomies.
			$taxonomies = get_taxonomies( array(), 'objects' );

			foreach ( $taxonomies as $taxonomy ) {

				// if rest_base is not set we use post_name (wp default).
				if ( ! $taxonomy->rest_base ) {
					$taxonomy->rest_base = $taxonomy->name;
				}

				foreach ( $this->website_details['sync_url_types'] as $sync_url_type ) {
					if ( $sync_url_type['post_type_slug'] === $taxonomy->rest_base ) {
						$url_types['taxonomies'][ $taxonomy->rest_base ] = $this->get_terms( $taxonomy->name );
					}
				}
			}
			// Sync all posts.
			/*
			$url_types = array(
				'types' => array (
					'pages' => get_pages(),
					'posts' => get_posts(array('numberposts' => '-1' )),
				)
			);*/
		}

		if ( is_iterable( $url_types ) ) {
			foreach ( $url_types as $url_type => $url_categories ) {
				foreach ( $url_categories as $url_category_name => $url_category_posts ) {
					if ( ! empty( $url_category_posts ) && is_iterable( $url_category_posts ) ) {
						foreach ( $url_category_posts as $post ) {

							switch ( $url_type ) {
								case 'types':
									$url           = get_permalink( $post );
									$url           = substr( $url, strpos( $url, '//' ) + 2 );
									$post_type_obj = get_post_type_object( $post->post_type );
									$array[]       = array(
										'url'             => $url,
										'html_title'      => $post->post_title,
										'cms_resource_id' => $post->ID,
										'url_type'        => $url_type,
										'url_category'    => $post_type_obj->labels->name,
									);
									break;

								case 'taxonomies':
									$url     = get_term_link( $post );
									$url     = substr( $url, strpos( $url, '//' ) + 2 );
									$array[] = array(
										'url'             => $url,
										'html_title'      => $post->name,
										'cms_resource_id' => $post->term_id,
										'url_type'        => $url_type,
										'url_category'    => $url_category_name,
									);

									break;

								default:
							}
						}
					}
				}
			}
			if ( ! get_option( 'page_on_front' ) ) {
				$url = get_option( 'home' );
				$url = substr( $url, strpos( $url, '//' ) + 2 );

				$array[] = array(
					'url'             => $url,
					'html_title'      => get_option( 'blogname' ) . ' - ' . get_option( 'blogdescription' ),
					'cms_resource_id' => 0,
					'url_type'        => 'frontpage',
					'url_category'    => 'Frontpage',
				);
			}
		}

		if ( ! empty( $array ) ) {
			$args = array(
				'action'              => 'sync_urls',
				'delete_missing_urls' => true,
				'posts'               => json_encode( $array ),
			);

			return $this->mm_api( $args );
		}
		return false;
	}

	public function update_urls( $group_id, $active_posts = array() ) {
		$args = array(
			'action'   => 'update_urls',
			'group_id' => $group_id,
			'posts'    => json_encode( $active_posts ),
		);
		return $this->mm_api( $args );
	}

	public function take_screenshot( $group_id, $sc_type ) {
		$args = array(
			'action'   => 'take_screenshots',
			'sc_type'  => $sc_type,
			'group_id' => $group_id,
		);
		return $this->mm_api( $args );
	}

	public function get_api_token_form( $api_token = false ) {
		$api_token_after_reset = isset( $_POST['api_token'] ) ? sanitize_text_field( $_POST['api_token'] ) : false;
		if ( $api_token ) {
			$output = '<form action="' . admin_url() . '/admin.php?page=webchangedetector" method="post"
                        onsubmit="return confirm(\'Are sure you want to reset the API token?\');">
                        <input type="hidden" name="wcd_action" value="reset_api_token">
                        <hr>
                        <h2> Account</h2>
                        <p>Your email address: <strong>' . $this->account_details()['email'] . '</strong><br>
                        Your API Token: <strong>' . $api_token . '</strong></p>
                        <p>With resetting the API Token, auto detections still continue and your settings will 
                        be still available when you use the same api token with this website again.</p>
                        <input type="submit" value="Reset API Token" class="button button-delete"><br>
                        
                        <hr>
                        <h2>Delete Account</h2>
                        <p>To delete your account completely, please login to your account at 
                        <a href="https://www.webchangedetector.com" target="_blank">webchangedetector.com</a>.</p>';

		} else {
			$output = '<div class="highlight-container">
                            <form class="frm_use_api_token highlight-inner no-bg" action="' . admin_url() . '/admin.php?page=webchangedetector" method="post">
                                <input type="hidden" name="wcd_action" value="save_api_token">
                                <h2>Use Existing API Token</h2>
                                <p>
                                    Use the API token of your existing account. To get your API token, please login to your account at
                                    <a href="' . $this->app_url() . 'login" target="_blank">webchangedetector.com</a>
                                </p>
                                <input type="text" name="api_token" value="' . $api_token_after_reset . '" required>
                                <input type="submit" value="Save" class="button button-primary">
                            </form>
                        </div>';
		}
		$output .= '</form>';
		return $output;
	}

	/**
	 * Creates Websites and Groups
	 *
	 * NOTE API Token needs to be sent here because it's not in the options yet
	 * at Website creation
	 *
	 * @param string $api_token
	 * @return array
	 */
	public function create_website_and_groups() {
		// Create group if it doesn't exist yet.
		$args = array(
			'action' => 'add_website_groups',
			'cms'    => 'wordpress',
			// domain sent at mm_api.
		);
		return $this->mm_api( $args );
	}

	public function delete_website() {
		// deprecated.
		$args = array(
			'action' => 'delete_website',
			// domain sent at mm_api.
		);
		$this->mm_api( $args );
	}

	public function get_urls_of_group( $group_id ) {
		$args = array(
			'action'   => 'get_user_groups_and_urls',
			'cms'      => 'wordpress',
			'group_id' => $group_id,
		);

		// We only get one group as we send the group_id.

		$response = $this->mm_api( $args );

		if ( array_key_exists( 0, $response ) ) {
			return $response[0];
		}

		return $response;
	}

	public function set_default_sync_types() {
		if ( empty( $this->website_details['sync_url_types'] ) ) {
			$this->website_details['sync_url_types'] = json_encode(
				array(
					array(
						'url_type_slug'  => 'types',
						'url_type_name'  => 'Post Types',
						'post_type_slug' => 'posts',
						'post_type_name' => 'Posts',
					),
					array(
						'url_type_slug'  => 'types',
						'url_type_name'  => 'Post Types',
						'post_type_slug' => 'pages',
						'post_type_name' => 'Pages',
					),
				)
			);
			$this->website_details                   = $this->mm_api( array_merge( array( 'action' => 'save_user_website' ), $this->website_details ) );
		}
	}

	public function get_url_settings( $groups_and_urls, $monitoring_group = false ) {
		// Sync urls - post_types defined in function @TODO make settings for post_types to sync.
		$synced_posts = $this->sync_posts();

		// Select URLS.
		$tab            = 'update-settings'; // init.
		$detection_type = 'update';
		if ( $monitoring_group ) {
			$tab            = 'auto-settings';
			$detection_type = 'auto';
		}
		?>
		<div class="wcd-select-urls-container">
			<form class="wcd-frm-settings" action="<?php echo admin_url() . 'admin.php?page=webchangedetector-' . $tab; ?>" method="post">

			<?php
			if ( ! $monitoring_group ) {
				$auto_update_settings       = get_option( 'wcd_auto_update_settings' );
				$auto_update_checks_enabled = '<span style="color: green">On</span>';
				if ( ! $auto_update_settings || ! array_key_exists( 'auto_update_checks_enabled', $auto_update_settings ) ) {
					$auto_update_checks_enabled = '<span style="color: red">Off</span>';
				}
				?>
				<input type="hidden" name="step" value="pre-update">
				<h2>Settings</h2>
				<div class="accordion">
					<div class="mm_accordion_title">
						<h3>
							Manual Checks Settings<br>
							<small>
								Auto update checks: <strong><?php echo $auto_update_checks_enabled; ?></strong>
								| Threshold: <strong><?php echo $groups_and_urls['threshold']; ?> %</strong>
								| CSS injection: <strong><?php echo $groups_and_urls['css'] ? 'yes' : 'no'; ?></strong>
							</small>
						</h3>
						<div class="mm_accordion_content">
							<input type="hidden" name="wcd-update-settings" value="true">
							<?php include 'partials/templates/update-settings.php'; ?>
						</div>
					</div>
				</div>

			<?php } else { ?>
				<!-- Monitoring Settings -->
				<h2>Settings</h2>
				<div class="accordion" style="margin-bottom: 40px;">
					<div class="mm_accordion_title" id="accordion-auto-detection-settings">
						<h3>
							Monitoring Settings<br>
							<small>
								<?php
								$enabled = $groups_and_urls['enabled'];
								if ( $enabled ) {
									?>
									Monitoring: <strong style="color: green;">Enabled</strong>
									| Interval: <strong>
										every
										<?php echo $groups_and_urls['interval_in_h']; ?>
										<?php echo $groups_and_urls['interval_in_h'] === 1 ? ' hour' : ' hours'; ?>
									</strong>

									| Threshold: <strong><?php echo $groups_and_urls['threshold']; ?> %</strong>
									| CSS injection: <strong><?php echo $groups_and_urls['css'] ? 'yes' : 'no'; ?></strong>
									<br>
									Notifications to:
									<strong>
										<?php echo ! empty( $groups_and_urls['alert_emails'] ) ? implode( ', ', $groups_and_urls['alert_emails'] ) : 'no email address set'; ?>
									</strong>
									<?php
								} else {
									?>
									Monitoring: <strong style="color: red">Disabled</strong>
								<?php } ?>
							</small>
						</h3>
						<div class="mm_accordion_content padding" style="background: #fff;">
							<?php include 'partials/templates/auto-settings.php'; ?>
						</div>
					</div>
				</div>
			<?php } ?>

			<h2 style="margin-top: 50px;">Select URLs</h2>
			<p style="text-align: center;">Add other post types and taxonomies at <a href="?page=webchangedetector-settings">Settings</a></p>
			<input type="hidden" value="webchangedetector" name="page">
			<input type="hidden" value="<?php echo esc_html( $groups_and_urls['id'] ); ?>" name="group_id">
			<?php

			// Get WP types and taxonomomies.
			$url_types['types']      = get_post_types( array( 'public' => true ), 'objects' );
			$url_types['taxonomies'] = get_taxonomies( array( 'public' => true ), 'objects' );

			if ( ! get_option( 'page_on_front' ) ) {

				// Check if current WP post ID is in synced_posts and get the url_id.
				foreach ( $synced_posts as $synced_post ) {
					if ( isset( $synced_post['cms_resource_id'] )
						&& $synced_post['cms_resource_id'] === 0
						&& $synced_post['url_type'] === 'frontpage' ) {
						$url_id = $synced_post['url_id'];
					}
				}
				$checked['desktop'] = false;
				$checked['mobile']  = false;
				$selected_desktop   = 0;
				$selected_mobile    = 0;
				if ( ! empty( $groups_and_urls['urls'] ) ) {
					foreach ( $groups_and_urls['urls'] as $url_details ) {
						if ( $url_details['pivot']['url_id'] === $url_id ) {
							if ( $url_details['pivot']['desktop'] ) {
								$checked['desktop'] = 'checked';
								++$selected_desktop;
							}
							if ( $url_details['pivot']['mobile'] ) {
								$checked['mobile'] = 'checked';
								++$selected_mobile;
							}
						}
					}
				}
				?>
				<div class="accordion post-type-accordion">
					<div class="mm_accordion_title">
						<h3>
							<span class="accordion-title">
								Frontpage
								<div class="accordion-post-types-url-amount">
									<?php echo $this->get_device_icon( 'desktop' ); ?>
									<strong><span id="selected-desktop-Frontpage"></span></strong> |
									<?php echo $this->get_device_icon( 'mobile' ); ?>
									<strong><span id="selected-mobile-Frontpage"></span></strong>
								</div>
								<div class="clear"></div>

							</span>

						</h3>
						<div class="mm_accordion_content">
							<div class="group_urls_container">
								<table>
									<tr>
										<th><?php echo $this->get_device_icon( 'desktop' ); ?></th>
										<th><?php echo $this->get_device_icon( 'mobile' ); ?></th>
										<th width="100%">URL</th>
									</tr>
									<tr class="live-filter-row even-tr-white post_id_<?php echo $groups_and_urls['id']; ?>" id="<?php echo $url_id; ?>" >
										<input type="hidden" name="post_id-<?php echo $url_id; ?>" value="<?php echo $url_id; ?>">
										<input type="hidden" name="url_id-<?php echo $url_id; ?>" value="<?php echo $url_id; ?>">
										<input type="hidden" name="active-<?php echo $url_id; ?>" value="1">

										<td class="checkbox-desktop-Frontpage" style="text-align: center;">
											<input type="hidden" value="0" name="desktop-<?php echo $url_id; ?>">
											<input type="checkbox" name="desktop-<?php echo $url_id; ?>" value="1" <?php echo $checked['desktop']; ?>
											id="desktop-<?php echo $url_id; ?>" onclick="mmMarkRows('<?php echo $url_id; ?>')" ></td>

										<td class="checkbox-mobile-Frontpage" style="text-align: center;">
											<input type="hidden" value="0" name="mobile-<?php echo $url_id; ?>">
											<input type="checkbox" name="mobile-<?php echo $url_id; ?>" value="1" <?php echo $checked['mobile']; ?>
											id="mobile-<?php echo $url_id; ?>" onclick="mmMarkRows('<?php echo $url_id; ?>')" ></td>

										<td style="text-align: left;"><strong><?php echo get_option( 'blogname' ) . ' - ' . get_option( 'blogdescription' ); ?></strong><br>
										<a href="<?php echo get_option( 'home' ); ?>" target="_blank"><?php echo get_option( 'home' ); ?></a></td>
									</tr>
								</table>
							</div>
						</div>
					</div>
				</div>

				<script> mmMarkRows('<?php echo $url_id; ?>'); </script>
				<?php
				echo '<div class="selected-urls" style="display: none;" 
                            data-amount_selected="1" 
                            data-amount_selected_desktop="' . $selected_desktop . '"
                            data-amount_selected_mobile="' . $selected_mobile . '"
                            data-post_type="Frontpage"
                            ></div>';
			}

			foreach ( $url_types as $url_type => $url_categories ) {
				foreach ( $url_categories as $url_category ) {

					// if rest_base is not set we use post_name (wp default).
					if ( ! $url_category->rest_base ) {
						$url_category->rest_base = $url_category->name;
					}

					$show_type = false;
					foreach ( $this->website_details['sync_url_types'] as $sync_url_type ) {
						if ( $url_category->rest_base && $sync_url_type['post_type_slug'] === $url_category->rest_base ) {
							$show_type = true;
						}
					}
					if ( ! $show_type ) {
						continue;
					}
					switch ( $url_type ) {
						case 'types':
							$posts = $this->get_posts( $url_category->name );
							break;

						case 'taxonomies':
							$posts = $this->get_terms( $url_category->name );
							break;

						default:
							$posts = false;
					}

					if ( is_iterable( $posts ) ) {
						?>
						<div class="accordion">
						<div class="mm_accordion_title">
						<h3>
							<span class="accordion-title">
								<?php echo ucfirst( $url_category->label ); ?>
								<div class="accordion-post-types-url-amount">
									<?php echo $this->get_device_icon( 'desktop' ); ?>
									<strong><span id="selected-desktop-<?php echo $url_category->label; ?>"></span></strong> |
									<?php echo $this->get_device_icon( 'mobile' ); ?>
									<strong><span id="selected-mobile-<?php echo $url_category->label; ?>"></span></strong>
								</div>
								<div class="clear"></div>
							</span>

						</h3>
						<div class="mm_accordion_content">
						<div class="group_urls_container">
						<input class="filter-url-table" type="text" placeholder="Filter">
						<div class="clear"></div>
						<table class="no-margin filter-table">
						<tr>
							<th><?php echo $this->get_device_icon( 'desktop' ); ?></th>
							<th><?php echo $this->get_device_icon( 'mobile' ); ?></th>
							<th width="100%">URL</th>
						</tr>
						<?php
						// Select all from same device.
						echo '<tr class="live-filter-row even-tr-white" style="background: none; text-align: center">
                                    <td><input type="checkbox" id="select-desktop-' . $url_category->label . '" onclick="mmToggle( this, \'' . $url_category->label . '\', \'desktop\', \'' . $groups_and_urls['id'] . '\' )" /></td>
                                    <td><input type="checkbox" id="select-mobile-' . $url_category->label . '" onclick="mmToggle( this, \'' . $url_category->label . '\', \'mobile\', \'' . $groups_and_urls['id'] . '\' )" /></td>
                                    <td></td>
                                </tr>';
						$amount_active_posts = 0;
						$selected_mobile     = 0;
						$selected_desktop    = 0;
						$append_rows         = '';

						if ( is_iterable( $posts ) && count( $posts ) > 0 ) {

							foreach ( $posts as $post ) {
								switch ( $url_type ) {
									case 'types':
										$url        = get_permalink( $post );
										$post_title = $post->post_title;
										$post_id    = $post->ID;
										break;

									case 'taxonomies':
										$url        = get_term_link( $post );
										$post_title = $post->name;
										$post_id    = $post->term_id;
										break;

									default:
										$url        = false;
										$post_title = false;
										$post_id    = false;
								}

								$url_id = false;

								// Check if current WP post ID is in synced_posts and get the url_id.
								foreach ( $synced_posts as $synced_post ) {
									if ( ! empty( $synced_post['cms_resource_id'] )
										&& $synced_post['cms_resource_id'] === $post_id
										&& $synced_post['url_type'] === $url_type ) {
										$url_id = $synced_post['url_id'];
									}
								}

								// If we don't have the url_id, the url is not synced and we continue.
								if ( ! $url_id ) {
									continue;
								}

								// init.
								$checked = array(
									'desktop' => '',
									'mobile'  => '',
								);

								if ( ! empty( $groups_and_urls['urls'] ) ) {
									foreach ( $groups_and_urls['urls'] as $url_details ) {
										if ( $url_details['pivot']['url_id'] === $url_id ) {
											$checked['active'] = 'checked';

											if ( $url_details['pivot']['desktop'] ) {
												$checked['desktop'] = 'checked';
												++$selected_desktop;
												++$amount_active_posts;
											}
											if ( $url_details['pivot']['mobile'] ) {
												$checked['mobile'] = 'checked';
												++$selected_mobile;
												++$amount_active_posts;
											}
										}
									}
								}

								$row  = '<tr class="live-filter-row even-tr-white post_id_' . $groups_and_urls['id'] . '" id="' . $url_id . '" >';
								$row .= '<input type="hidden" name="post_id-' . $url_id . '" value="' . $post_id . '">';
								$row .= '<input type="hidden" name="url_id-' . $url_id . '" value="' . $url_id . '">';
								$row .= '<input type="hidden" name="active-' . $url_id . ' value="1">';

								$row .= '<td class="checkbox-desktop-' . $url_category->label . '" style="text-align: center;">
                                            <input type="hidden" value="0" name="desktop-' . $url_id . '">
                                            <input type="checkbox" name="desktop-' . $url_id . '" value="1" ' . $checked['desktop'] . '
                                            id="desktop-' . $url_id . '" onclick="mmMarkRows(\'' . $url_id . '\')" ></td>';

								$row .= '<td class="checkbox-mobile-' . $url_category->label . '" style="text-align: center;">
                                            <input type="hidden" value="0" name="mobile-' . $url_id . '">
                                            <input type="checkbox" name="mobile-' . $url_id . '" value="1" ' . $checked['mobile'] . '
                                            id="mobile-' . $url_id . '" onclick="mmMarkRows(\'' . $url_id . '\')" ></td>';

								$row .= '<td style="text-align: left;"><strong>' . $post_title . '</strong><br>';
								$row .= '<a href="' . $url . '" target="_blank">' . $url . '</a></td>';
								$row .= '</tr>';

								$row .= '<script> mmMarkRows(\'' . $url_id . '\'); </script>';

								if ( $checked['desktop'] || $checked['mobile'] ) {
									echo $row;
								} else {
									$append_rows .= $row;
								}
							}
						}
						echo $append_rows;
						echo '</table>';
						if ( ! count( $posts ) ) {
							?>
							<div style="text-align: center; font-weight: 700; padding: 20px 0;">
								No Posts in this post type
							</div>
							<?php
						}

						echo '<div class="selected-urls" style="display: none;" 
                                    data-amount_selected="' . $amount_active_posts . '" 
                                    data-amount_selected_desktop="' . $selected_desktop . '"
                                    data-amount_selected_mobile="' . $selected_mobile . '"
                                    data-post_type="' . $url_category->label . '"
                                    ></div>';
					}
					?>
					</div>
					</div>
					</div>
					</div>
					<?php
				}
			}
			if ( $monitoring_group ) {
				?>
					<button
							class="button button-primary"
							type="submit"
							name="wcd_action"
							value="post_urls"
							onclick="return wcdValidateFormAutoSettings()">
						Save
					</button>
					<button class="button"
							type="submit"
							name="wcd_action"
							value="post_urls_update_and_auto"
							style="margin-left: 10px;"
							onclick="return wcdValidateFormAutoSettings()">
						Save & copy to manual checks
					</button>
				<?php
			} else {

				if ( $this->website_details['allow_manual_detection'] ) {
					?>
						<button
							class="button button-primary"
							type="submit"
							name="wcd_action"
							value="save_update_settings_and_continue" >
							Save and continue >
						</button>
					<?php } ?>
					<button
							class="button"
							type="submit"
							name="wcd_action"
							value="post_urls"
							style="margin-left: 10px;">
						Only save
					</button>
					<button class="button"
							type="submit"
							name="wcd_action"
							value="post_urls_update_and_auto"
							style="margin-left: 10px;">
						Save & copy to monitoring
					</button>
				<?php } ?>
			</form>
		</div>
		<?php
	}

	public function post_urls( $postdata, $website_details, $save_both_groups ) {
		// Get active posts from post data.
		$active_posts   = array();
		$count_selected = 0;
		foreach ( $postdata as $key => $post_id ) {
			if ( strpos( $key, 'url_id' ) === 0 ) {

				// sanitize before.
				$wpPostId = sanitize_key( $postdata[ 'post_id-' . $post_id ] ); // should be numeric.
				if ( ! is_numeric( $wpPostId ) ) {
					continue; // just skip it.
				}
				$permalink = get_permalink( $wpPostId ); // should return the whole link.
				$desktop   = array_key_exists( 'desktop-' . $post_id, $postdata ) ? sanitize_key( $postdata[ 'desktop-' . $post_id ] ) : 0;
				$mobile    = array_key_exists( 'mobile-' . $post_id, $postdata ) ? sanitize_key( $postdata[ 'mobile-' . $post_id ] ) : 0;

				$active_posts[] = array(
					'url_id'  => $post_id,
					'url'     => $permalink,
					'desktop' => $desktop,
					'mobile'  => $mobile,
				);
				if ( isset( $postdata[ 'desktop-' . $post_id ] ) && $postdata[ 'desktop-' . $post_id ] === 1 ) {
					++$count_selected;
				}

				if ( isset( $postdata[ 'mobile-' . $post_id ] ) && $postdata[ 'mobile-' . $post_id ] === 1 ) {
					++$count_selected;
				}
			}
		}

		$group_id_website_details = sanitize_key( $postdata['group_id'] );

		// Check if there is a limit for selecting URLs.
		if ( $website_details['enable_limits'] &&
			$website_details['url_limit_manual_detection'] < $count_selected &&
			$website_details['manual_detection_group_id'] === $group_id_website_details ) {
			echo '<div class="error notice"><p>The limit for selecting URLs is ' .
				esc_html( $website_details['url_limit_manual_detection'] ) . '.
                        You selected ' . $count_selected . ' URLs. The settings were not saved.</p></div>';
		} elseif ( $website_details['enable_limits'] &&
			isset( $monitoring_group_settings ) &&
			$website_details['sc_limit'] < $count_selected * ( WCD_HOURS_IN_DAY / $monitoring_group_settings['interval_in_h'] ) * WCD_DAYS_PER_MONTH &&
			$website_details['auto_detection_group_id'] === $group_id_website_details ) {
			echo '<div class="error notice"><p>The limit for monitorings is ' .
				esc_html( $website_details['sc_limit'] ) . '. per month.
                            You selected ' . $count_selected * ( WCD_HOURS_IN_DAY / $monitoring_group_settings['interval_in_h'] ) * WCD_DAYS_PER_MONTH . ' change detections. The settings were not saved.</p></div>';
		} else {
			if ( $save_both_groups ) {
				$this->update_urls( $website_details['auto_detection_group_id'], $active_posts );
				$this->update_urls( $website_details['manual_detection_group_id'], $active_posts );
			} else {
				$this->update_urls( $group_id_website_details, $active_posts );
			}
			echo '<div class="updated notice"><p>Settings saved.</p></div>';
		}
	}

	public function get_no_account_page( $api_token = '' ) {
		ob_start();
		?>
		<div class="no-account-page">
			<div class="status_bar no-account" >
				<h2>Get Started</h2>
				With WebChangeDetector you can check your website before after installing updates. See all changes
				highlighted in a screenshot and fix issues before anyone else see them. You can also monitor changes
				on your website automatically and get notified when something changed.
			</div>
			<div class="highlight-wrapper">
				<div class="highlight-container">
				<div class="highlight-inner">
					<h2>Create Free Account</h2>
					<p>
						Create your free account now and use WebChangeDetector with <br><strong>50 checks</strong> per month for free.<br>
					</p>
					<form class="frm_new_account" method="post">
						<input type="hidden" name="wcd_action" value="create_free_account">
						<input type="text" name="name_first" placeholder="First Name" value="<?php echo $_POST['name_first'] ?? wp_get_current_user()->user_firstname; ?>" required>
						<input type="text" name="name_last" placeholder="Last Name" value="<?php echo $_POST['name_last'] ?? wp_get_current_user()->user_lastname; ?>" required>
						<input type="email" name="email" placeholder="Email" value="<?php echo $_POST['email'] ?? wp_get_current_user()->user_email; ?>" required>
						<input type="password" name="password" placeholder="Password" required>
						<input type="checkbox" name="marketingoptin" checked style="width: 10px; display: inline-block;"> Send me news about WebChangeDetector

						<input type="submit" class="button-primary" value="Create Free Account">
					</form>
				</div>
				</div>

				<?php echo $this->get_api_token_form( $api_token ); ?>

				</div>
			</div>
		</div>

		<?php
		return ob_get_clean();
	}

	public function set_website_details() {
		$args = array(
			'action' => 'get_website_details',
			// domain sent at mm_api.
		);
		$this->website_details = $this->mm_api( $args );

		// If we don't have websites details yet, we create them. This happens after account activation.
		if ( empty( $this->website_details ) ) {
			$this->create_website_and_groups();
			$this->website_details = $this->mm_api( $args );
		}

		// Take the first website details or return error string.
		if ( is_array( $this->website_details ) && count( $this->website_details ) > 0 ) {
			$this->website_details = $this->website_details[0];

			// Set default sync types if they are empty.
			$this->set_default_sync_types();
			$this->website_details['sync_url_types'] = json_decode( $this->website_details['sync_url_types'], true );
		}
	}

	public function tabs() {
		$active_tab = 'webchangedetector'; // init.

		if ( isset( $_GET['page'] ) ) {
			// sanitize: lower-case with "-".
			$active_tab = sanitize_key( $_GET['page'] );
		}
		?>
		<div class="wrap">
			<h2 class="nav-tab-wrapper">
				<a href="?page=webchangedetector"
					class="nav-tab <?php echo $active_tab === 'webchangedetector' ? 'nav-tab-active' : ''; ?>">
					<?php echo $this->get_device_icon( 'dashboard' ); ?> Dashboard
				</a>
				<a href="?page=webchangedetector-update-settings"
					class="nav-tab <?php echo $active_tab === 'webchangedetector-update-settings' ? 'nav-tab-active' : ''; ?>">
					<?php echo $this->get_device_icon( 'update-group' ); ?> Manual Checks
				</a>
				<a href="?page=webchangedetector-auto-settings"
					class="nav-tab <?php echo $active_tab === 'webchangedetector-auto-settings' ? 'nav-tab-active' : ''; ?>">
					<?php echo $this->get_device_icon( 'auto-group' ); ?> Monitoring
				</a>
				<a href="?page=webchangedetector-change-detections"
					class="nav-tab <?php echo $active_tab === 'webchangedetector-change-detections' ? 'nav-tab-active' : ''; ?>">
					<?php echo $this->get_device_icon( 'change-detections' ); ?> Change Detections
				</a>
				<a href="?page=webchangedetector-logs"
					class="nav-tab <?php echo $active_tab === 'webchangedetector-logs' ? 'nav-tab-active' : ''; ?>">
					<?php echo $this->get_device_icon( 'logs' ); ?> Logs
				</a>
				<a href="?page=webchangedetector-settings"
					class="nav-tab <?php echo $active_tab === 'webchangedetector-settings' ? 'nav-tab-active' : ''; ?>">
					<?php echo $this->get_device_icon( 'settings' ); ?> Settings
				</a>
				<a href="<?php echo $this->get_upgrade_url(); ?>" target="_blank"
					class="nav-tab upgrade">
					<?php echo $this->get_device_icon( 'upgrade' ); ?> Upgrade Account
				</a>
			</h2>
		</div>

		<?php
	}

	public function get_dashboard_view( $client_account, $update_group_id, $auto_group_id ) {
		$recent_comparisons = $this->get_compares( array( $update_group_id, $auto_group_id ), null, null, false, 10 );

		$auto_group            = $this->get_urls_of_group( $auto_group_id );
		$amount_auto_detection = 0;
		if ( $auto_group['enabled'] ) {
			$amount_auto_detection += WCD_HOURS_IN_DAY / $auto_group['interval_in_h'] * $auto_group['amount_selected_urls'] * WCD_DAYS_PER_MONTH;
		}
		?>
		<div class="dashboard">
			<div>
				<div class="box-half no-border">
					<a class="box" href="?page=webchangedetector-update-settings">
						<div style="padding-top:10px; font-size: 60px; width: 50px; float: left;">
							<?php echo $this->get_device_icon( 'update-group' ); ?>
						</div>
						<div style="float: left; max-width: 350px;">
							<strong>Manual Checks</strong><br>
							Create change detections manually
						</div>
						<div class="clear"></div>
					</a>
					<a class="box" href="?page=webchangedetector-auto-settings">
						<div style="padding-top:10px; font-size: 60px; width: 50px; float: left;">
							<?php echo $this->get_device_icon( 'auto-group' ); ?>
						</div>
						<div style="float: left; max-width: 350px;">
							<strong>Monitoring</strong><br>
							Create automatic change detections
						</div>
						<div class="clear"></div>
					</a>
					<a class="box" href="?page=webchangedetector-change-detections">
						<div style="padding-top:10px; font-size: 60px; width: 50px; float: left;">
							<?php echo $this->get_device_icon( 'change-detections' ); ?>
						</div>
						<div style="float: left; max-width: 350px;">
							<strong>Show Change Detections</strong><br>
							Check all your change detections
						</div>
						<div class="clear"></div>
					</a>

				</div>

				<div class="box-half box-plain">
					<h2>
						<strong>
							<?php
							if ( ! empty( $client_account['sc_limit'] ) ) {
								echo number_format( $client_account['usage'] / $client_account['sc_limit'] * 100, 1 );
							} else {
								echo 0;
							}
							?>
							% credits used
						</strong>
					</h2>
					<hr>
					<p style="margin-top: 20px;"><strong>Used checks:</strong> <?php echo $client_account['usage']; ?> / <?php echo $client_account['sc_limit']; ?></p>

					<p><strong>Active monitoring checks / month:</strong> <?php echo $amount_auto_detection; ?></p>

					<p><strong>Active monitoring checks until renewal:</strong>
						<?php echo number_format( $amount_auto_detection / WCD_SECONDS_IN_MONTH * ( gmdate( 'U', strtotime( $client_account['renewal_at'] ) ) - gmdate( 'U' ) ), 0 ); ?></p>

					<p><strong>Renewal on:</strong> <?php echo gmdate( 'd/m/Y', strtotime( $client_account['renewal_at'] ) ); ?></p>
				</div>
				<div class="clear"></div>
			</div>


			<div>
				<h2>Latest Change Detections</h2>
				<?php
				$this->compare_view( $recent_comparisons );
				if ( ! empty( $recent_comparisons ) ) {
					?>
					<p><a class="button" href="?page=webchangedetector-change-detections">Show All Change Detections</a></p>
						<?php } ?>
			</div>

			<div class="clear"></div>
		</div>
		<?php
	}

	public function show_activate_account( $error ) {

		if ( $error === 'activate account' ) {
			?>
			<div class="notice notice-info"></span>
				<p>Please <strong>activate</strong> your account.</p>
			</div>
			<div class="activate-account highlight-container" >
			<div class="highlight-inner">
				<h2>
					Activate account
				</h2>
				<p>
					We just sent you an activation mail.
				</p>
				<?php if ( get_option( WCD_WP_OPTION_KEY_ACCOUNT_EMAIL ) ) { ?>
					<div style="margin: 0 auto; padding: 15px; border-radius: 5px;background: #5db85c; color: #fff; max-width: 400px;">
						<span id="activation_email" style="font-weight: 700;"><?php echo sanitize_email( get_option( WCD_WP_OPTION_KEY_ACCOUNT_EMAIL ) ); ?></span>
					</div>
				<?php } ?>
				<p>
					After clicking the activation link in the mail, your account is ready.
					Check your spam folder if you cannot find the activation mail in your inbox.
				</p>
			</div>
			<div>
				<h2>You didn't receive the email?</h2>
				<p>
					Please contact us at <a href="mailto:support@webchangedetector.com">support@webchangedetector.com</a>
					or use our live chat at <a href="https://www.webchangedetector.com">webchangedetector.com</a>.
					We are happy to help.
				</p>
				<p>To reset your account, please click the button below. </p>
				<form id="delete-account" method="post">
					<input type="hidden" name="wcd_action" value="reset_api_token">
					<input type="submit" class="button-delete" value="Reset Account">
				</form>

			</div>
		</div>
			<?php
		}

		if ( $error === 'unauthorized' ) {
			?>
			<div class="notice notice-error">
				<p>
					The API token is not valid. Please reset the API token and enter a valid one.
				</p>
			</div>
			<?php
			echo $this->get_no_account_page();
		}

		return false;
	}

	/**
	 * App Domain can be set outside this plugin for development
	 *
	 * @return string
	 */
	public function app_url() {
		if ( defined( 'WCD_APP_DOMAIN' ) && is_string( WCD_APP_DOMAIN ) && ! empty( WCD_APP_DOMAIN ) ) {
			return WCD_APP_DOMAIN;
		}
		return 'https://www.webchangedetector.com/';
	}

	/**
	 * App Domain can be set outside this plugin for development
	 *
	 * @return string
	 */
	public function billing_url() {
		if ( defined( 'WCD_BILLING_DOMAIN' ) && is_string( WCD_BILLING_DOMAIN ) && ! empty( WCD_BILLING_DOMAIN ) ) {
			return WCD_BILLING_DOMAIN;
		}
		return $this->app_url() . 'billing/';
	}

	/**
	 * If in development mode
	 *
	 * @return bool
	 */
	public function dev() {
		// if either .test or dev. can be found in the URL, we're developing -  wouldn't work if plugin client domain matches these criteria.
		if ( defined( 'WCD_DEV' ) && WCD_DEV === true ) {
			return true;
		}
		return false;
	}

	/**
	 * Call to API
	 *
	 * This is the only method left with mm_ for historical reasons
	 *
	 * @param array $post
	 * @param bool  $isGet
	 * @return string|array
	 */
	public function mm_api( $post, $isWeb = false, $version = 1 ) {
		$url    = "https://api.webchangedetector.com/api/v$version/"; // init for production.
		$urlWeb = 'https://api.webchangedetector.com/';

		// This is where it can be changed to a local/dev address.
		if ( defined( 'WCD_API_URL' ) && is_string( WCD_API_URL ) && ! empty( WCD_API_URL ) ) {
			$url = WCD_API_URL;
		}

		// Overwrite $url if it is a get request.
		if ( $isWeb && defined( 'WCD_API_URL_WEB' ) && is_string( WCD_API_URL_WEB ) && ! empty( WCD_API_URL_WEB ) ) {
			$urlWeb = WCD_API_URL_WEB;
		}

		$url    .= str_replace( '_', '-', $post['action'] ); // add kebab action to url.
		$urlWeb .= str_replace( '_', '-', $post['action'] ); // add kebab action to url.
		$action  = $post['action']; // For debugging.

		// Get API Token from WP DB.
		$api_token = $post['api_token'] ?? get_option( WCD_WP_OPTION_KEY_API_TOKEN ) ?? null;

		unset( $post['action'] ); // don't need to send as action as it's now the url.
		unset( $post['api_token'] ); // just in case.

		$post['wp_plugin_version'] = WEBCHANGEDETECTOR_VERSION; // API will check this to check compatability.
		// there's checks in place on the API side, you can't just send a different domain here, you sneaky little hacker ;).
		$post['domain'] = $_SERVER['SERVER_NAME'];
		$post['wp_id']  = get_current_user_id();

		// Increase timeout for php.ini

		if ( ! ini_get( 'safe_mode' ) ) {
			set_time_limit( WCD_REQUEST_TIMEOUT + 10 );
		}

		$args = array(
			'timeout' => WCD_REQUEST_TIMEOUT,
			'body'    => $post,
			'headers' => array(
				'Accept'        => 'application/json',
				'Authorization' => 'Bearer ' . $api_token,
			),
		);

		if ( $isWeb ) {
			$response = wp_remote_post( $urlWeb, $args );
		} else {
			$response = wp_remote_post( $url, $args );
		}

		$body         = wp_remote_retrieve_body( $response );
		$responseCode = (int) wp_remote_retrieve_response_code( $response );

		$decodedBody = json_decode( $body, (bool) JSON_OBJECT_AS_ARRAY );

		// `message` is part of the Laravel Stacktrace.
		if ( $responseCode === WCD_HTTP_BAD_REQUEST &&
			is_array( $decodedBody ) &&
			array_key_exists( 'message', $decodedBody ) &&
			$decodedBody['message'] === 'plugin_update_required' ) {
			return 'update plugin';
		}

		if ( $responseCode === WCD_HTTP_INTERNAL_SERVER_ERROR && $action === 'account_details' ) {
			return 'activate account';
		}

		if ( $responseCode === WCD_HTTP_UNAUTHORIZED ) {
			return 'unauthorized';
		}

		// if parsing JSON into $decodedBody was without error.
		if ( json_last_error() === JSON_ERROR_NONE ) {
			return $decodedBody;
		}

		return $body;
	}


	public function mm_api_v2( $post, $method = 'POST', $isWeb = false, ) {
		$url    = 'https://api.webchangedetector.com/api/v2/'; // init for production.
		$urlWeb = 'https://api.webchangedetector.com/';

		// This is where it can be changed to a local/dev address.
		if ( defined( 'WCD_API_URL_V2' ) && is_string( WCD_API_URL_V2 ) && ! empty( WCD_API_URL_V2 ) ) {
			$url = WCD_API_URL_V2;
		}

		// Overwrite $url if it is a get request.
		if ( $isWeb && defined( 'WCD_API_URL_WEB' ) && is_string( WCD_API_URL_WEB ) && ! empty( WCD_API_URL_WEB ) ) {
			$urlWeb = WCD_API_URL_WEB;
		}

		$url    .= str_replace( '_', '-', $post['action'] ); // add kebab action to url.
		$urlWeb .= str_replace( '_', '-', $post['action'] ); // add kebab action to url.
		$action  = $post['action']; // For debugging.

		// Get API Token from WP DB.
		$api_token = $post['api_token'] ?? get_option( WCD_WP_OPTION_KEY_API_TOKEN ) ?? null;

		unset( $post['action'] ); // don't need to send as action as it's now the url.
		unset( $post['api_token'] ); // just in case.

		$post['wp_plugin_version'] = WEBCHANGEDETECTOR_VERSION; // API will check this to check compatability.
		// there's checks in place on the API side, you can't just send a different domain here, you sneaky little hacker ;).
		$post['domain'] = $_SERVER['SERVER_NAME'];
		$post['wp_id']  = get_current_user_id();

		// Increase timeout for php.ini

		if ( ! ini_get( 'safe_mode' ) ) {
			set_time_limit( WCD_REQUEST_TIMEOUT + 10 );
		}

		$args = array(
			'timeout' => WCD_REQUEST_TIMEOUT,
			'body'    => $post,
			'method'  => $method,
			'headers' => array(
				'Accept'        => 'application/json',
				'Authorization' => 'Bearer ' . $api_token,
			),
		);

		error_log( 'Sending API V2 request: ' . $url . ' | args: ' . json_encode( $args ) );

		if ( $isWeb ) {
			$response = wp_remote_request( $urlWeb, $args );
		} else {
			$response = wp_remote_request( $url, $args );
		}

		$body         = wp_remote_retrieve_body( $response );
		$responseCode = (int) wp_remote_retrieve_response_code( $response );

		$decodedBody = json_decode( $body, (bool) JSON_OBJECT_AS_ARRAY );

		// `message` is part of the Laravel Stacktrace.
		if ( $responseCode === WCD_HTTP_BAD_REQUEST &&
			is_array( $decodedBody ) &&
			array_key_exists( 'message', $decodedBody ) &&
			$decodedBody['message'] === 'plugin_update_required' ) {
			return 'update plugin';
		}

		if ( $responseCode === WCD_HTTP_INTERNAL_SERVER_ERROR && $action === 'account_details' ) {
			return 'activate account';
		}

		if ( $responseCode === WCD_HTTP_UNAUTHORIZED ) {
			return 'unauthorized';
		}

		// if parsing JSON into $decodedBody was without error.
		if ( json_last_error() === JSON_ERROR_NONE ) {
			return $decodedBody;
		}

		return $body;
	}

	public function take_screenshot_v2( $group_ids, $sc_type ) {
		if ( ! is_array( $group_ids ) ) {
			$group_ids = array( $group_ids );
		}
		$args = array(
			'action'    => 'screenshots/take',
			'sc_type'   => $sc_type,
			'group_ids' => $group_ids,
		);

		return $this->mm_api_v2( $args );
	}

	public function get_comparisons_v2( $filters = array() ) {
		$url = 'comparisons';
		if ( ! empty( $filters ) ) {
			$url = $url . '?' . build_query( $filters );
		}

		$args = array(
			'action' => $url,
		);

		return $this->mm_api_v2( $args, 'GET' );
	}

	public function get_queue_v2( $batch_id = false, $status = false ) {
		$args = array();
		if ( $batch_id ) {
			$args['batch'] = $batch_id;
		}
		if ( $status ) {
			$args['status'] = $status;
		}

		$args = array(
			'action' => 'queues?' . build_query( $args ),
		);

		return $this->mm_api_v2( $args, 'GET' );
	}


	public function add_webhook_v2( $url, $event ) {
		$args = array(
			'action' => 'webhooks',
			'url'    => $url,
			'event'  => $event,
		);
		return $this->mm_api_v2( $args );
	}

	public function delete_webhook( $id ) {
		if ( $id ) {
			return false;
		}
		$args = array(
			'action' => 'webhooks/' . $id,
		);
		return $this->mm_api_v2( $args, 'DELETE' );
	}
}

// HTTP Status Codes.
if ( ! defined( 'WCD_HTTP_BAD_REQUEST' ) ) {
	define( 'WCD_HTTP_BAD_REQUEST', 400 );
}

if ( ! defined( 'WCD_HTTP_UNAUTHORIZED' ) ) {
	define( 'WCD_HTTP_UNAUTHORIZED', 401 );
}

if ( ! defined( 'WCD_HTTP_INTERNAL_SERVER_ERROR' ) ) {
	define( 'WCD_HTTP_INTERNAL_SERVER_ERROR', 500 );
}

// Time/Date Related.
if ( ! defined( 'WCD_DAYS_PER_MONTH' ) ) {
	define( 'WCD_DAYS_PER_MONTH', 30 );
}

if ( ! defined( 'WCD_HOURS_IN_DAY' ) ) {
	define( 'WCD_HOURS_IN_DAY', 24 );
}

if ( ! defined( 'WCD_SECONDS_IN_MONTH' ) ) {
	// 60 * 60 * 24 * 30.
	define( 'WCD_SECONDS_IN_MONTH', 2592000 );
}

// Option / UserMeta keys.
if ( ! defined( 'WCD_WP_OPTION_KEY_API_TOKEN' ) ) {
	define( 'WCD_WP_OPTION_KEY_API_TOKEN', 'webchangedetector_api_token' );
}

// Account email address.
if ( ! defined( 'WCD_WP_OPTION_KEY_ACCOUNT_EMAIL' ) ) {
	define( 'WCD_WP_OPTION_KEY_ACCOUNT_EMAIL', 'webchangedetector_account_email' );
}

// Steps in update change detection.
if ( ! defined( 'WCD_OPTION_UPDATE_STEP_KEY' ) ) {
	define( 'WCD_OPTION_UPDATE_STEP_KEY', 'webchangedetector_update_detection_step' );
}
if ( ! defined( 'WCD_OPTION_UPDATE_STEP_SETTINGS' ) ) {
	define( 'WCD_OPTION_UPDATE_STEP_SETTINGS', 'settings' );
}
if ( ! defined( 'WCD_OPTION_UPDATE_STEP_PRE' ) ) {
	define( 'WCD_OPTION_UPDATE_STEP_PRE', 'pre-update' );
}
if ( ! defined( 'WCD_OPTION_UPDATE_STEP_PRE_STARTED' ) ) {
	define( 'WCD_OPTION_UPDATE_STEP_PRE_STARTED', 'pre-update-started' );
}
if ( ! defined( 'WCD_OPTION_UPDATE_STEP_MAKE_UPDATES' ) ) {
	define( 'WCD_OPTION_UPDATE_STEP_MAKE_UPDATES', 'make-update' );
}
if ( ! defined( 'WCD_OPTION_UPDATE_STEP_POST' ) ) {
	define( 'WCD_OPTION_UPDATE_STEP_POST', 'post-update' );
}
if ( ! defined( 'WCD_OPTION_UPDATE_STEP_POST_STARTED' ) ) {
	define( 'WCD_OPTION_UPDATE_STEP_POST_STARTED', 'post-update-started' );
}
if ( ! defined( 'WCD_OPTION_UPDATE_STEP_CHANGE_DETECTION' ) ) {
	define( 'WCD_OPTION_UPDATE_STEP_CHANGE_DETECTION', 'change-detection' );
}

// WCD tabs.
if ( ! defined( 'WCD_TAB_DASHBOARD' ) ) {
	define( 'WCD_TAB_DASHBOARD', '/admin.php?page=webchangedetector-dashboard' );
}
if ( ! defined( 'WCD_TAB_UPDATE' ) ) {
	define( 'WCD_TAB_UPDATE', '/admin.php?page=webchangedetector-update-settings' );
}
if ( ! defined( 'WCD_TAB_AUTO' ) ) {
	define( 'WCD_TAB_AUTO', '/admin.php?page=webchangedetector-auto-settings' );
}
if ( ! defined( 'WCD_TAB_CHANGE_DETECTION' ) ) {
	define( 'WCD_TAB_CHANGE_DETECTION', '/admin.php?page=webchangedetector-change-detections' );
}
if ( ! defined( 'WCD_TAB_LOGS' ) ) {
	define( 'WCD_TAB_LOGS', '/admin.php?page=webchangedetector-logs' );
}
if ( ! defined( 'WCD_TAB_SETTINGS' ) ) {
	define( 'WCD_TAB_SETTINGS', '/admin.php?page=webchangedetector-settings' );
}

if ( ! defined( 'WCD_REQUEST_TIMEOUT' ) ) {
	define( 'WCD_REQUEST_TIMEOUT', 30 );
}

// Uncommented functions().
if ( ! function_exists( 'dd' ) ) {
	/**
	 * Dump and Die
	 */
	function dd( ...$output ) {
		// this is PHP 5.6+
		echo '<pre>';
		foreach ( $output as $o ) {
			if ( is_array( $o ) || is_object( $o ) ) {
				print_r( $o );
				continue;
			}
			echo $o;
		}
		echo '</pre>';
		die();
	}
}
