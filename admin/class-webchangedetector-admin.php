<?php
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

/** WCD Admin Class
 */
class WebChangeDetector_Admin {

	const API_TOKEN_LENGTH = 10;

	const VALID_WCD_ACTIONS = array(
		'reset_api_token',
		're-add-api-token',
		'save_api_token',
		'take_screenshots',
		'save_group_settings',
		'dashboard',
		'change-detections',
		'auto-settings',
		'logs',
		'settings',
		'show-compare',
		'create_free_account',
		'update_detection_step',
		'add_post_type',
		'filter_change_detections',
		'change_comparison_status',
		'enable_wizard',
		'disable_wizard',
		'start_manual_checks',
		'sync_urls',
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
		'post',
		'update',
		'auto-update',
	);

	const VALID_COMPARISON_STATUS = array(
		'new',
		'ok',
		'to_fix',
		'false_positive',

	);

	const WEEKDAYS = array(
		'monday',
		'tuesday',
		'wednesday',
		'thursday',
		'friday',
		'saturday',
		'sunday',
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
	 * The monitoring checks group uuid.
	 *
	 * @since    1.0.0
	 * @access   public
	 * @var      string $monitoring_group_uuid The manual checks group uuid.
	 */
	public $monitoring_group_uuid;

	/**
	 * The manual checks group uuid.
	 *
	 * @since    1.0.0
	 * @access   public
	 * @var      string $manual_group_uuid The manual checks group uuid.
	 */
	public $manual_group_uuid;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string $version The current version of this plugin.
	 */
	private $version = WEBCHANGEDETECTOR_VERSION;

	/**
	 * Where the urls to sync are cached before they are sent.
	 *
	 * @var array Urls to sync.
	 */
	private $sync_urls;
	/**
	 * Initialize the class and set its properties.
	 *
	 * @param string $plugin_name The name of this plugin.
	 * @since    1.0.0
	 */
	public function __construct( $plugin_name = 'WebChangeDetector' ) {
		$this->plugin_name = $plugin_name;
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
		$css_settings              = array(
			'type' => 'text/css',
		);
		$cm_settings['codeEditor'] = wp_enqueue_code_editor( $css_settings );
		wp_localize_script( 'jquery', 'cm_settings', $cm_settings );
	}

	/** Website details.
	 *
	 * @var array $website_details Array with website details.
	 */
	public $website_details;

	/** Add WCD to backend navigation (called by hook in includes/class-webchangedetector.php).
	 *
	 * @return void
	 */
	public function wcd_plugin_setup_menu() {

		require_once 'partials/webchangedetector-admin-display.php';

		// We get the allowances from the options as the website_details are not there yet.
		$allowances = get_option( WCD_ALLOWANCES );

		add_menu_page(
			'WebChange Detector',
			'WebChange Detector',
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

		if ( is_array( $allowances ) && $allowances['change_detections_view'] ) {
			add_submenu_page(
				'webchangedetector',
				'Change Detections',
				'Change Detections',
				'manage_options',
				'webchangedetector-change-detections',
				'wcd_webchangedetector_init'
			);
		}
		if ( is_array( $allowances ) && $allowances['manual_checks_view'] ) {
			add_submenu_page(
				'webchangedetector',
				'Manual Checks & Auto Update Checks',
				'Manual Checks & Auto Update Checks',
				'manage_options',
				'webchangedetector-update-settings',
				'wcd_webchangedetector_init'
			);
		}
		if ( is_array( $allowances ) && $allowances['monitoring_checks_view'] ) {
			add_submenu_page(
				'webchangedetector',
				'Monitoring',
				'Monitoring',
				'manage_options',
				'webchangedetector-auto-settings',
				'wcd_webchangedetector_init'
			);
		}
		if ( is_array( $allowances ) && $allowances['logs_view'] ) {
			add_submenu_page(
				'webchangedetector',
				'Queue',
				'Queue',
				'manage_options',
				'webchangedetector-logs',
				'wcd_webchangedetector_init'
			);
		}
		if ( is_array( $allowances ) && $allowances['settings_view'] ) {
			add_submenu_page(
				'webchangedetector',
				'Settings',
				'Settings',
				'manage_options',
				'webchangedetector-settings',
				'wcd_webchangedetector_init'
			);
		}
		if ( is_array( $allowances ) && $allowances['upgrade_account'] ) {
			add_submenu_page(
				'webchangedetector',
				'Upgrade Account',
				'Upgrade Account',
				'manage_options',
				$this->get_upgrade_url()
			);
		}
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
		add_submenu_page(
			null,
			'No billing account found',
			'No billing account found',
			'manage_options',
			'webchangedetector-no-billing-account',
			'wcd_webchangedetector_init'
		);
	}

	/** Get wcd plugin dir.
	 *
	 * @return string
	 */
	public function get_wcd_plugin_url() {
		return plugin_dir_url( __FILE__ ) . '../';
	}

	/** Create a new free account.
	 *
	 * @param array $postdata the postdata.
	 *
	 * @return array|string
	 */
	public function create_free_account( $postdata ) {

		// Generate validation string.
		$validation_string = wp_generate_password( 40 );
		update_option( WCD_VERIFY_SECRET, $validation_string, false );
		$postdata['password'] = wp_hash_password( $postdata['password'] );
		$args                 = array_merge(
			array(
				'action'            => 'add_free_account',
				'ip'                => isset( $_SERVER['SERVER_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['SERVER_ADDR'] ) ) : '',
				'domain'            => $this->get_domain_from_site_url(),
				'validation_string' => $validation_string,
				'cms'               => 'wp',
			),
			$postdata
		);

		return $this->api_v1( $args, true );
	}

	/** Sync url after update.
	 *
	 * @param int    $post_id The post_id.
	 * @param object $post_after The post after update.
	 * @param object $post_before The post before update.
	 * @return void
	 */
	public function update_post( $post_id, $post_after, $post_before ) {
		// Check if it's a revision or an autosave to avoid unnecessary requests and if the post is publish.
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

		// Get the post type, title, and URL.
		$post_type = get_post_type_object( $post_after->post_type );

		$post_category   = $this->get_post_type_name( $this->get_post_type_slug( $post_type ) );
		$post_title      = get_the_title( $post_id );
		$post_before_url = get_permalink( $post_before );
		$post_after_url  = get_permalink( $post_after );

		// Check if we sync this post_type.
		$website_details = $this->get_website_details();
		$to_sync         = false;
		foreach ( $website_details['sync_url_types'] as $sync_url_type ) {
			if ( $post_category === $sync_url_type['post_type_name'] ) {
				$to_sync = true;
			}
		}
		if ( ! $to_sync ) {
			return;
		}

		// Prepare the data to send.
		$data[][ 'types%%' . $post_category ][] = array(
			'html_title' => $post_title,
			'url'        => $this->remove_url_protocol( $post_before_url ),
			'new_url'    => $this->remove_url_protocol( $post_after_url ),
		);

		$this->sync_single_post( $data );
	}

		/**
		 * Get the domain from wp site_url.
		 *
		 * @return string
		 */
	public static function get_domain_from_site_url() {
		return rtrim( preg_replace( '(^https?://)', '', get_site_url() ), '/' ); // site might be in subdir.
	}

	/** Save the api token.
	 *
	 * @param array  $postdata The postdata.
	 * @param string $api_token The api token.
	 *
	 * @return bool
	 */
	public function save_api_token( $postdata, $api_token ) {
		if ( ! is_string( $api_token ) || strlen( $api_token ) < self::API_TOKEN_LENGTH ) {
			if ( is_array( $api_token ) && 'error' === $api_token[0] && ! empty( $api_token[1] ) ) {
				echo '<div class="notice notice-error"><p>' . esc_html( $api_token[1] ) . '</p></div>';
			} else {
				echo '<div class="notice notice-error">
                        <p>The API Token is invalid. Please try again or contact us if the error persists</p>
                        </div>';
			}
			$this->get_no_account_page();
			return false;
		}

		// Save email address on account creation for showing on activate account page.
		if ( ! empty( $postdata['email'] ) ) {
			update_option( WCD_WP_OPTION_KEY_ACCOUNT_EMAIL, sanitize_email( wp_unslash( $postdata['email'] ) ), false );
		}
		update_option( WCD_WP_OPTION_KEY_API_TOKEN, sanitize_text_field( $api_token ), false );

		return true;
	}

	/** Get account details.
	 *
	 * @param bool $force Force account data from api.
	 * @return array|string|bool
	 */
	public function get_account( $force = false ) {

		static $account_details;
		if ( $account_details && ! $force ) {
			return $account_details;
		}

		$account_details = WebChangeDetector_API_V2::get_account_v2();

		if ( ! empty( $account_details['data'] ) ) {
			$account_details                 = $account_details['data'];
			$account_details['checks_limit'] = $account_details['checks_done'] + $account_details['checks_left'];
			return $account_details;
		}
		if ( ! empty( $account_details['message'] ) ) {
			return $account_details['message'];
		}

		return false;
	}

	/** Sync Post if permalink changed. Currently deactivated.
	 *
	 * @return array|false|mixed|string
	 */
	public function wcd_sync_post_after_save() {
		$this->sync_posts( true );
		return true;
	}

	/** Ajax get processing queue
	 *
	 * @return void
	 */
	public function ajax_get_processing_queue() {
		echo wp_json_encode( $this->get_processing_queue_v2( get_option( 'wcd_manual_checks_batch' ) ) );
		die();
	}

	/** Update selected url
	 *
	 * @return void
	 */
	public function ajax_post_url() {
		if ( ! isset( $_POST['nonce'] ) ) {
			echo 'POST Params missing';
			die();
		}

		// Verify nonce.
		if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'ajax-nonce' ) ) {
			echo 'Nonce verify failed';
			die( 'Busted!' );
		}

		$this->post_urls( $_POST );
		die();
	}

	/** Sync posts via ajax.
	 *
	 * @return void
	 */
	public function ajax_sync_urls() {
		if ( ! isset( $_POST['nonce'] ) ) {
			echo 'POST Params missing';
			die();
		}

		// Verify nonce.
		if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'ajax-nonce' ) ) {
			echo 'Nonce verify failed';
			die( 'Busted!' );
		}

		$force = isset( $_POST['force'] ) ? sanitize_text_field( wp_unslash( $_POST['force'] ) ) : 0;
		self::error_log( 'Force? ' . (bool) $force );
		$response = $this->sync_posts( (bool) $force, $this->get_website_details() );
		if ( $response ) {
			echo esc_html( $response );
		}
		wp_die();
	}

	/** Ajax update comparison status
	 *
	 * @return void
	 */
	public function ajax_update_comparison_status() {
		if ( ! isset( $_POST['id'] ) || ! isset( $_POST['status'] ) || ! isset( $_POST['nonce'] ) ) {
			echo 'POST Params missing';
			die();
		}

		// Verify nonce.
		if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'ajax-nonce' ) ) {
			echo 'Nonce verify failed';
			die( 'Busted!' );
		}

		$result = $this->update_comparison_status( esc_html( sanitize_text_field( wp_unslash( $_POST['id'] ) ) ), esc_html( sanitize_text_field( wp_unslash( $_POST['status'] ) ) ) );
		echo esc_html( $result['data']['status'] ) ?? 'failed';
		die();
	}

	/** Get queues for status processing and open
	 *
	 * @param string $batch_id The batch id.
	 * @param int    $per_page Rows per page.
	 * @return array
	 */
	public function get_processing_queue_v2( $batch_id = false, $per_page = 30 ) {
		$processing = WebChangeDetector_API_V2::get_queue_v2( $batch_id, 'processing', array( 'per_page' => $per_page ) );
		$open       = WebChangeDetector_API_V2::get_queue_v2( $batch_id, 'open', array( 'per_page' => $per_page ) );
		return array(
			'open'       => $open,
			'processing' => $processing,
		);
	}

	/** Update monitoring group settings.
	 *
	 * @param array $group_data The postdata.
	 *
	 * @return array|string
	 */
	public function update_monitoring_settings( $group_data ) {
		$monitoring_settings = WebChangeDetector_API_V2::get_group_v2( $this->monitoring_group_uuid )['data'];

		$args = array(
			'monitoring'    => true,
			'hour_of_day'   => ! isset( $group_data['hour_of_day'] ) ? $monitoring_settings['hour_of_day'] : sanitize_key( $group_data['hour_of_day'] ),
			'interval_in_h' => ! isset( $group_data['interval_in_h'] ) ? $monitoring_settings['interval_in_h'] : sanitize_text_field( $group_data['interval_in_h'] ),
			'enabled'       => ( isset( $group_data['enabled'] ) && 'on' === $group_data['enabled'] ) ? 1 : 0,
			'alert_emails'  => ! isset( $group_data['alert_emails'] ) ? $monitoring_settings['alert_emails'] : explode( ',', sanitize_textarea_field( $group_data['alert_emails'] ) ),
			'name'          => ! isset( $group_data['group_name'] ) ? $monitoring_settings['name'] : sanitize_text_field( $group_data['group_name'] ),
			'threshold'     => ! isset( $group_data['threshold'] ) ? $monitoring_settings['threshold'] : sanitize_text_field( $group_data['threshold'] ),
		);

		if ( ! empty( $group_data['css'] ) ) {
			$args['css'] = sanitize_textarea_field( $group_data['css'] );
		}

		return WebChangeDetector_API_V2::update_group( $this->monitoring_group_uuid, $args );
	}

	/** Update group settings
	 *
	 * @param array $postdata The postdata.
	 *
	 * @return array|string
	 */
	public function update_manual_check_group_settings( $postdata ) {

		// Saving auto update settings.
		$auto_update_settings = array();
		foreach ( $postdata as $key => $value ) {
			if ( 0 === strpos( $key, 'auto_update_checks_' ) ) {
				$auto_update_settings[ $key ] = $value;
			}
		}
		$this->website_details['auto_update_settings'] = $auto_update_settings;
		$this->update_website_details( $this->website_details );

		do_action( 'wcd_save_update_group_settings', $postdata );

		// Update group settings in api.
		$args = array(
			'name'      => $postdata['group_name'],
			'threshold' => sanitize_text_field( $postdata['threshold'] ),
		);

		if ( ! empty( $postdata['css'] ) ) {
			$args['css'] = sanitize_textarea_field( $postdata['css'] ); // there is no css sanitation.
		}

		return ( WebChangeDetector_API_V2::update_group( $this->manual_group_uuid, $args ) );
	}

	/** Get the upgrade url
	 *
	 * @return false|mixed|string|null
	 */
	public function get_upgrade_url() {

		static $upgrade_url;
		if ( $upgrade_url ) {
			return $upgrade_url;
		}

		$account_details = $this->get_account();

		if ( ! $this->is_allowed( 'upgrade_account' ) || ! is_array( $account_details ) || empty( $account_details['magic_login_secret'] ) ) {
			return '?page=webchangedetector-no-billing-account';
		}

		$upgrade_url = $this->billing_url() . '?secret=' . $account_details['magic_login_secret'];
		update_option( WCD_WP_OPTION_KEY_UPGRADE_URL, $upgrade_url );

		return $upgrade_url;
	}

	/** Output a wp icon.
	 *
	 * @param string $icon Name of the icon.
	 * @param string $css_class Additional css classes.
	 *
	 * @return void
	 */
	public function get_device_icon( $icon, $css_class = '' ) {
		$output = '';
		if ( 'thumbnail' === $icon ) {
			$output = '<span class="dashicons dashicons-camera-alt"></span>';
		}
		if ( 'desktop' === $icon ) {
			$output = '<span class="group_icon ' . $css_class . ' dashicons dashicons-laptop"></span>';
		}
		if ( 'mobile' === $icon ) {
			$output = '<span class="group_icon ' . $css_class . ' dashicons dashicons-smartphone"></span>';
		}
		if ( 'page' === $icon ) {
			$output = '<span class="group_icon ' . $css_class . ' dashicons dashicons-media-default"></span>';
		}
		if ( 'change-detections' === $icon ) {
			$output = '<span class="group_icon ' . $css_class . ' dashicons dashicons-welcome-view-site"></span>';
		}
		if ( 'dashboard' === $icon ) {
			$output = '<span class="group_icon ' . $css_class . ' dashicons dashicons-admin-home"></span>';
		}
		if ( 'logs' === $icon ) {
			$output = '<span class="group_icon ' . $css_class . ' dashicons dashicons-menu-alt"></span>';
		}
		if ( 'settings' === $icon ) {
			$output = '<span class="group_icon ' . $css_class . ' dashicons dashicons-admin-generic"></span>';
		}
		if ( 'website-settings' === $icon ) {
			$output = '<span class="group_icon ' . $css_class . ' dashicons dashicons-welcome-widgets-menus"></span>';
		}
		if ( 'help' === $icon ) {
			$output = '<span class="group_icon ' . $css_class . ' dashicons dashicons-editor-help"></span>';
		}
		if ( 'auto-group' === $icon ) {
			$output = '<span class="group_icon ' . $css_class . ' dashicons dashicons-clock"></span>';
		}
		if ( 'update-group' === $icon ) {
			$output = '<span class="group_icon ' . $css_class . ' dashicons dashicons-admin-page"></span>';
		}
		if ( 'auto-update-group' === $icon ) {
			$output = '<span class="group_icon ' . $css_class . ' dashicons dashicons-update"></span>';
		}
		if ( 'trash' === $icon ) {
			$output = '<span class="group_icon ' . $css_class . ' dashicons dashicons-trash"></span>';
		}
		if ( 'check' === $icon ) {
			$output = '<span class="group_icon ' . $css_class . ' dashicons dashicons-yes-alt"></span>';
		}
		if ( 'fail' === $icon ) {
			$output = '<span class="group_icon ' . $css_class . ' dashicons dashicons-dismiss"></span>';
		}
		if ( 'warning' === $icon ) {
			$output = '<span class="group_icon ' . $css_class . ' dashicons dashicons-warning"></span>';
		}
		if ( 'upgrade' === $icon ) {
			$output = '<span class="group_icon ' . $css_class . ' dashicons dashicons-cart"></span>';
		}

		echo wp_kses( $output, array( 'span' => array( 'class' => array() ) ) );
	}

	/** Update comparison status.
	 *
	 * @param string $id The comparison uuid.
	 * @param string $status One of new, ok, to_fix or false_positive.
	 * @return false|mixed|string
	 */
	public function update_comparison_status( $id, $status ) {
		return WebChangeDetector_API_V2::update_comparison_v2( $id, $status );
	}

	/** Nice names for comparison status.
	 *
	 * @param string $status The status.
	 * @return string
	 */
	public function comparison_status_nice_name( $status ) {
		switch ( $status ) {
			case 'ok':
				return 'Ok';
			case 'to_fix':
				return 'To Fix';
			case 'false_positive':
				return 'False Positive';
			case 'failed':
				return 'Failed';
			default:
				return 'new';
		}
	}

	/** View of comparison overview.
	 *
	 * @param array $compares The compares.
	 * @param array $failed_queues Array with failed queues.
	 * @return void
	 */
	public function compare_view_v2( $compares, $failed_queues = false ) {
		if ( empty( $compares ) ) {
			?>
			<table style="width: 100%">
				<tr>
					<td colspan="5" style="text-align: center; background: #fff; height: 50px;">
						<strong>No change detections (yet).</strong><br>
						Try different filters to show change detections.
					</td>
				</tr>
			</table>
			<?php
			return;
		}

		$all_tokens          = array();
		$compares_in_batches = array();

		foreach ( $compares as $compare ) {

			$all_tokens[] = $compare['id'];

			// Sort comparisons by batches.
			$compares_in_batches[ $compare['batch'] ][] = $compare;
			if ( 'ok' !== $compare['status'] ) {
				$compares_in_batches[ $compare['batch'] ]['needs_attention'] = true;
			}
		}
		$auto_update_batches = get_option( WCD_AUTO_UPDATE_COMPARISON_BATCHES );

		foreach ( $compares_in_batches as $batch_id => $compares_in_batch ) {

			$amount_failed = 0;
            if(!empty($failed_queues['data'])) {
	            foreach ( $failed_queues['data'] as $failed_queue ) {
		            if ( $failed_queue['batch'] === $batch_id ) {
			            ++ $amount_failed;
		            }
	            }
            }
			?>
			<div class="accordion accordion-batch" style="margin-top: 20px;">
				<div class="mm_accordion_title">
					<h3>
						<div style="display: inline-block;">
							<div class="accordion-batch-title-tile accordion-batch-title-tile-status">
								<?php
								if ( array_key_exists( 'needs_attention', $compares_in_batch ) ) {
									$this->get_device_icon( 'warning', 'batch_needs_attention' );
									echo '<small>Needs Attention</small>';
								} else {
									$this->get_device_icon( 'check', 'batch_is_ok' );
									echo '<small>Looks Good</small>';
								}
								if ( $amount_failed ) {
									echo "<div style='font-size: 14px; color: darkred'> " . esc_html( $amount_failed ) . ( $amount_failed > 1 ? ' checks' : ' check' ) . ' failed</div>';
								}
								?>
							</div>
							<div class="accordion-batch-title-tile">
								<?php
								if ( $compares_in_batch[0]['group'] === $this->monitoring_group_uuid ) {
									$this->get_device_icon( 'auto-group' );
									echo ' Monitoring Checks';
								} elseif ( is_array( $auto_update_batches ) && in_array( $batch_id, $auto_update_batches, true ) ) {
									$this->get_device_icon( 'auto-update-group' );
									echo ' Auto Update Checks';
								} else {
									$this->get_device_icon( 'update-group' );
									echo ' Manual Checks';
								}
								?>
								<br>
								<small>
									<?php echo esc_html( human_time_diff( gmdate( 'U' ), gmdate( 'U', strtotime( $compares_in_batch[0]['created_at'] ) ) ) ); ?> ago
									(<?php echo esc_html( get_date_from_gmt( ( $compares_in_batch[0]['created_at'] ) ) ); ?> )
								</small>
							</div>
							<div class="clear"></div>
						</div>
					</h3>
					<div class="mm_accordion_content">
						<table class="toggle" style="width: 100%">
							<tr>
								<th style="min-width: 120px;">Status</th>
								<th style="width: 100%">URL</th>
								<th style="min-width: 150px">Compared Screenshots</th>
								<th style="min-width: 50px">Difference</th>
								<th>Show</th>
							</tr>

							<?php
							if ( $failed_queues ) {
								foreach ( $failed_queues['data'] as $failed_queue ) {
									if ( $batch_id === $failed_queue['batch'] ) {
										?>
										<tr style="background-color: rgba(220, 50, 50, 0.28)">
											<td>
												<div class="comparison_status_container">
													<span class="current_comparison_status comparison_status comparison_status_failed"><?php echo esc_html( $this->comparison_status_nice_name( 'failed' ) ); ?></span>
												</div>
											</td>
											<td>
											<?php
											if ( ! empty( $failed_queue['html_title'] ) ) {
												echo '<strong>' . esc_html( $failed_queue['html_title'] ) . '</strong><br>';
											}
												echo esc_html( $this->get_device_icon( $failed_queue['device'] ) . $failed_queue['url_link'] );
											?>
											</td>
											<td colspan="3">
												<strong>Creating Change Detection failed.</strong><br> Please check the URL.
											</td>

										</tr>

										<?php
									}
								}
							}

							foreach ( $compares_in_batch as $key => $compare ) {
								if ( 'needs_attention' === $key ) {
									continue;
								}
								if ( empty( $compare['status'] ) ) {
									$compare['status'] = 'new';
								}

								$class = 'no-difference'; // init.
								if ( $compare['difference_percent'] ) {
									$class = 'is-difference';
								}

								?>
								<tr>
									<td>
										<div class="comparison_status_container">
											<span class="current_comparison_status comparison_status comparison_status_<?php echo esc_html( $compare['status'] ); ?>">
												<?php echo esc_html( $this->comparison_status_nice_name( $compare['status'] ) ); ?>
											</span>
											<div class="change_status" style="display: none; position: absolute; background: #fff; padding: 20px; box-shadow: 0 0 5px #aaa;">
												<strong>Change Status to:</strong><br>
												<?php $nonce = wp_create_nonce( 'ajax-nonce' ); ?>
												<button name="status"
														data-id="<?php echo esc_html( $compare['id'] ); ?>"
														data-status="ok"
														data-nonce="<?php echo esc_html( $nonce ); ?>"
														value="ok"
														class="ajax_update_comparison_status comparison_status comparison_status_ok"
														onclick="return false;">Ok</button>
												<button name="status"
														data-id="<?php echo esc_html( $compare['id'] ); ?>"
														data-status="to_fix"
														data-nonce="<?php echo esc_html( $nonce ); ?>"
														value="to_fix"
														class="ajax_update_comparison_status comparison_status comparison_status_to_fix"
														onclick="return false;">To Fix</button>
												<button name="status"
														data-id="<?php echo esc_html( $compare['id'] ); ?>"
														data-status="false_positive"
														data-nonce="<?php echo esc_html( $nonce ); ?>"
														value="false_positive"
														class="ajax_update_comparison_status comparison_status comparison_status_false_positive"
														onclick="return false;">False Positive</button>
											</div>
										</div>
									</td>
									<td>
										<strong>
											<?php
											if ( ! empty( $compare['html_title'] ) ) {
												echo esc_html( $compare['html_title'] ) . '<br>';
											}
											?>
										</strong>
										<?php
										echo esc_url( $compare['url'] ) . '<br>';
										$this->get_device_icon( $compare['device'] );
										echo esc_html( ucfirst( $compare['device'] ) );
										?>
									</td>
									<td>
										<div  ><?php echo esc_html( get_date_from_gmt( $compare['screenshot_1_created_at'] ) ); ?></div>
										<div  ><?php echo esc_html( get_date_from_gmt( $compare['screenshot_2_created_at'] ) ); ?></div>
									</td>
									<td class="<?php echo esc_html( $class ); ?> diff-tile"
										data-diff_percent="<?php echo esc_html( $compare['difference_percent'] ); ?>">
										<?php echo esc_html( $compare['difference_percent'] ); ?>%
									</td>
									<td>
										<form action="<?php echo esc_html( wp_nonce_url( '?page=webchangedetector-show-detection&id=' . esc_html( $compare['id'] ) ) ); ?>" method="post">
											<input type="hidden" name="all_tokens" value='<?php echo wp_json_encode( $all_tokens ); ?>'>
											<input type="submit" value="Show" class="button">
										</form>
									</td>
								</tr>
							<?php } ?>
						</table>
					</div>
				</div>
			</div>
			<?php
			if ( 1 === count( $compares_in_batches ) ) {
				echo '<script>jQuery(document).ready(function() {jQuery(".accordion h3").click();});</script>';
			}
		}
	}

	/** Get comparison.
	 *
	 * @param array $postdata The postdata.
	 * @param bool  $hide_switch Is deprecated.
	 * @param bool  $whitelabel Is deprecated.
	 *
	 * @return void
	 */
	public function get_comparison_by_token( $postdata, $hide_switch = false, $whitelabel = false ) {
		$token = $postdata['token'] ?? null;

		if ( empty( $_GET['_wpnonce'] ) || ! wp_verify_nonce( wp_unslash( sanitize_key( $_GET['_wpnonce'] ) ) ) ) {
			echo 'Something went wrong. Please try again.';
			wp_die();
		}

		if ( ! $token && ! empty( $_GET['id'] ) ) {
			$token = sanitize_text_field( wp_unslash( $_GET['id'] ) );
		}
		if ( isset( $token ) ) {
			$compare = WebChangeDetector_API_V2::get_comparison_v2( $token )['data'];

			$public_token = $compare['token'];
			$all_tokens   = array();
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
			<div style="width: 100%; margin-bottom: 20px; text-align: center; margin-left: auto; margin-right: auto">
				<form action="<?php echo esc_html( wp_nonce_url( '?page=webchangedetector-show-detection&id=' . ( esc_html( $before_token ) ?? null ) ) ); ?>" method="post" style="display:inline-block;">
					<input type="hidden" name="all_tokens" value='<?php echo wp_json_encode( $all_tokens ); ?>'>
					<button class="button" type="submit" name="token"
							value="<?php echo esc_html( $before_token ) ?? null; ?>" <?php echo ! $before_token ? 'disabled' : ''; ?>> < Previous </button>
				</form>
				<form action="<?php echo esc_html( wp_nonce_url( '?page=webchangedetector-show-detection&id=' . ( esc_html( $after_token ) ?? null ) ) ); ?>" method="post" style="display:inline-block;">
					<input type="hidden" name="all_tokens" value='<?php echo wp_json_encode( $all_tokens ); ?>'>
					<button class="button" type="submit" name="token"
							value="<?php echo esc_html( $after_token ) ?? null; ?>" <?php echo ! $after_token ? 'disabled' : ''; ?>> Next > </button>
				</form>
			</div>
			<?php
			include 'partials/templates/show-change-detection.php';
			echo '</div>';

		} else {
			echo '<p class="notice notice-error" style="padding: 10px;">Ooops! There was no change detection selected. Please go to 
                <a href="?page=webchangedetector-change-detections">Change Detections</a> and select a change detection
                to show.</p>';
		}
	}

	/** Get screenshot
	 *
	 * @param array $postdata The postdata.
	 * @return void
	 */
	public function get_screenshot( $postdata = false ) {
		if ( ! isset( $postdata['img_url'] ) ) {
			echo '<p class="notice notice-error" style="padding: 10px;">
                    Sorry, we couldn\'t find the screenshot. Please try again.</p>';
		}
		echo '<div style="width: 100%; text-align: center;"><img style="max-width: 100%" src="' . esc_url( $postdata['img_url'] ) . '"></div>';
	}

	/** Add Post type to website
	 *
	 * @param array $postdata The postdata.
	 *
	 * @return void
	 */
	public function add_post_type( $postdata ) {
		$post_type                               = json_decode( stripslashes( $postdata['post_type'] ), true );
		$this->website_details['sync_url_types'] = array_merge( $post_type, $this->website_details['sync_url_types'] );

		$this->update_website_details();
		$this->sync_posts( true );
	}

	/** Get posts
	 *
	 * @param string $posttype The posttype.
	 *
	 * @return int[]|WP_Post[]
	 */
	public function get_posts( $posttype ) {
		$args           = array(
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

	/** Filter duplicate post_ids.
	 *
	 * @param array $posts The posts.
	 * @return array
	 */
	public function filter_unique_posts_by_id( $posts ) {
		$unique_posts = array();
		$post_ids     = array();

		foreach ( $posts as $post ) {
			unset( $post->post_content ); // Don't need to send to much unnessesary data.
			if ( ! in_array( $post->ID, $post_ids, true ) ) {
				$post_ids[]     = $post->ID;
				$unique_posts[] = $post;
			}
		}

		return $unique_posts;
	}

	/** Filter duplicate terms.
	 *
	 * @param array $terms The terms.
	 * @return array
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

	/** Get terms.
	 *
	 * @param string $taxonomy the taxonomy.
	 *
	 * @return array|int[]|string|string[]|WP_Error|WP_Term[]
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

			// With languages, we loop through them and return all of them.
		} else {
			$terms = array();
			foreach ( $wpml_languages['languages'] as $language ) {
				do_action( 'wpml_switch_language', $language['code'] );
				$terms = array_merge( $terms, get_terms( $args ) );
			}
			do_action( 'wpml_switch_language', $wpml_languages['current_language'] );
		}
		return $this->filter_unique_terms_by_id( $terms );
	}

	/** Check if wpml is active and return all languages and the active one.
	 *
	 * @return array|false|void[]
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

	/** Get the posts.
	 *
	 * @param array $post_types the post_types to get.
	 * @return void
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
				self::error_log( 'Getting next chunk. Offset: ' . $offset );
				// Set up WP_Query arguments.
				$args = array(
					'post_type'      => $single_post_type,  // Pass the array of post types.
					'post_status'    => 'publish',
					'posts_per_page' => $posts_per_batch,  // Fetch 500 posts at a time.
					'offset'         => $offset,
				);

				// Create a new query.
				$query = new WP_Query( $args );

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
						'url'        => $this->remove_url_protocol( $url ),
						'html_title' => $post_title,
					);
				}

				// Reset post data to avoid conflicts in global post state.
				wp_reset_postdata();

				// Increment the offset for the next batch.
				$offset += $posts_per_batch;
				self::error_log( 'Sending Posts.' );

				// Call uploadUrls after every batch.
				$this->upload_urls_in_batches( $all_posts_data );

				// Clear the data array after each batch to free memory.
				$all_posts_data = array();

				// Get the count of the results.
				$results_count = $query->post_count;
			} while ( $results_count === $posts_per_batch );
		}
	}

	/** Get the taxonomies.
	 *
	 * @param array $taxonomies The taxonomies.
	 * @return void
	 */
	public function get_all_terms_data( $taxonomies ) {

		// Array to store all terms data.
		$all_terms_data = array();

		if ( empty( $taxonomies ) ) {
			return;
		}

		$batch_size  = 500;  // Limit each batch to 500 terms.
		$offset      = 0;        // Initial offset to start from.
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
					'url'        => $this->remove_url_protocol( $url ),
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
	 * Prepare urls for upload.
	 *
	 * @param array $upload_array The urls to upload.
	 */
	public function upload_urls_in_batches( $upload_array ) {
		if ( ! empty( $upload_array ) ) {
			$this->sync_urls[] = $upload_array;
		}
	}

	/** Sync single post.
	 *
	 * @param array $single_post The sync array.
	 * @return true|void
	 */
	public function sync_single_post( $single_post ) {
		if ( ! empty( $single_post ) ) {
			self::error_log( 'Start single url sync' );
			$response_sync_urls      = WebChangeDetector_API_V2::sync_urls( $single_post );
			$response_start_url_sync = WebChangeDetector_API_V2::start_url_sync( false );

		}
	}

	/** Sync posts with api.
	 *
	 * @param bool       $force_sync Skip cache and force sync.
	 * @param array|bool $website_details The website_details or false.
	 * @return bool
	 */
	public function sync_posts( $force_sync = false, $website_details = false ) {

		$last_sync     = get_option( 'wcd_last_urls_sync' );
		$sync_interval = '+1 hour';

		// Skip sync if last sync is less than sync interval.
		if ( $last_sync && ! $force_sync && strtotime( $sync_interval, $last_sync ) > gmdate( 'U' ) ) {
			// Returning last sync datetime.
			return date_i18n( 'd.m.Y H:i', $last_sync );
		}

		self::error_log( 'Starting Sync' );

		// Check if we got website_details or if we use the ones from the class.
		$array = array(); // init.
		if ( ! $website_details ) {
			$website_details = $this->website_details;
		}

		// We only sync the frontpage.
		if ( ! empty( $website_details['allowances']['only_frontpage'] ) ) {
			$array['frontpage%%Frontpage'][] = array(
				'url'        => $this::get_domain_from_site_url(),
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
				$wp_post_type_slug = $this->get_post_type_slug( $post_type );

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
				$wp_taxonomy_slug = $this->get_taxonomy_slug( $taxonomy );

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
			$website_details['sync_url_types'],
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
							'url'        => self::remove_url_protocol( apply_filters( 'wpml_home_url', get_home_url(), $lang_code ) ),
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
								'url'        => self::remove_url_protocol( $language->home_url ),
								'html_title' => get_bloginfo( 'name' ),
							);
						}
					}
				}
			} else {
				$array['frontpage%%Frontpage'][] = array(
					'url'        => self::remove_url_protocol( get_option( 'home' ) ),
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
				$this->update_website_details( $website_details );
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
			$this->update_website_details( $website_details );
		}

		$response_sync_urls      = WebChangeDetector_API_V2::sync_urls( $this->sync_urls );
		$response_start_url_sync = WebChangeDetector_API_V2::start_url_sync( true );
		self::error_log( 'Response upload URLs: ' . $response_sync_urls );
		self::error_log( 'Response Start URL sync: ' . $response_start_url_sync );
		update_option( 'wcd_last_urls_sync', date_i18n( 'U' ) );

		return date_i18n( 'd/m/Y H:i' );
	}

	/** Remove the protocol from an url.
	 *
	 * @param string $url The URL.
	 * @return string
	 */
	public function remove_url_protocol( $url ) {
		return substr( $url, strpos( $url, '//' ) + 2 );
	}

	/** Get api token form.
	 *
	 * @param string $api_token The api token.
	 *
	 * @return void
	 */
	public function get_api_token_form( $api_token = false ) {

		if ( $api_token ) {
			?>
			<div class="box-plain no-border">
				<form action="<?php echo esc_url( admin_url() . '/admin.php?page=webchangedetector' ); ?>" method="post"
					onsubmit="return confirm('Are sure you want to reset the API token?');">
					<input type="hidden" name="wcd_action" value="reset_api_token">
					<?php wp_nonce_field( 'reset_api_token' ); ?>

					<h2> Account</h2>
					<p>
						Your email address: <strong><?php echo esc_html( $this->get_account()['email'] ); ?></strong><br>
						Your API Token: <strong><?php echo esc_html( $api_token ); ?></strong>
					</p>
					<p>
						With resetting the API Token, auto detections still continue and your settings will
						be still available when you use the same api token with this website again.
					</p>
					<input type="submit" value="Reset API Token" class="button button-delete"><br>
				</form>
			</div>
			<div class="box-plain no-border">
				<h2>Delete Account</h2>
				<p>To delete your account completely, please login to your account at
					<a href="https://www.webchangedetector.com" target="_blank">webchangedetector.com</a>.
				</p>
			</div>
			<?php
		} else {
			if ( isset( $_POST['wcd_action'] ) && 'save_api_token' === sanitize_text_field( wp_unslash( $_POST['wcd_action'] ) ) ) {
				check_admin_referer( 'save_api_token' );
			}
			$api_token_after_reset = isset( $_POST['api_token'] ) ? sanitize_text_field( wp_unslash( $_POST['api_token'] ) ) : false;
			?>
			<div class="highlight-container">
				<form class="frm_use_api_token highlight-inner no-bg" action="<?php echo esc_url( admin_url() ); ?>/admin.php?page=webchangedetector" method="post">
					<input type="hidden" name="wcd_action" value="save_api_token">
					<?php wp_nonce_field( 'save_api_token' ); ?>
					<h2>Use Existing API Token</h2>
					<p>
						Use the API token of your existing account. To get your API token, please login to your account at
						<a href="<?php echo esc_url( $this->app_url() ); ?>login" target="_blank">webchangedetector.com</a>
					</p>
					<input type="text" name="api_token" value="<?php echo esc_html( $api_token_after_reset ); ?>" required>
					<input type="submit" value="Save" class="button button-primary">
				</form>
			</div>
			<?php
		}
	}

	/**
	 * Creates Websites and Groups
	 *
	 * NOTE API Token needs to be sent here because it's not in the options yet
	 * at Website creation
	 *
	 * @return array
	 */
	public function create_website_and_groups() {
		// Create group if it doesn't exist yet.
		$args = array(
			'action' => 'add_website_groups',
			'cms'    => 'wordpress',
			// domain sent at mm_api.
		);
		return $this->api_v1( $args );
	}

	/** Get params of an url.
	 *
	 * @param string $url The url.
	 * @return array|false
	 */
	public function get_params_of_url( $url ) {
		if ( empty( $url ) ) {
			return false;
		}

		$url_components = wp_parse_url( $url );
		parse_str( $url_components['query'], $params );
		return $params;
	}

	/** Print the monitoring status bar.
	 *
	 * @param array $group The group details.
	 * @return void
	 */
	public function print_monitoring_status_bar( $group ) {
		// Calculation for monitoring.
		$date_next_sc = false;

		$amount_sc_per_day = 0;

		// Check for intervals >= 1h.
		if ( $group['interval_in_h'] >= 1 ) {
			$next_possible_sc  = gmmktime( gmdate( 'H' ) + 1, 0, 0, gmdate( 'm' ), gmdate( 'd' ), gmdate( 'Y' ) );
			$amount_sc_per_day = ( 24 / $group['interval_in_h'] );
			$possible_hours    = array();

			// Get possible tracking hours.
			for ( $i = 0; $i <= $amount_sc_per_day * 2; $i++ ) {
				$possible_hour    = $group['hour_of_day'] + $i * $group['interval_in_h'];
				$possible_hours[] = $possible_hour >= 24 ? $possible_hour - 24 : $possible_hour;
			}
			sort( $possible_hours );

			// Check for today and tomorrow.
			for ( $ii = 0; $ii <= 1; $ii++ ) { // Do 2 loops for today and tomorrow.
				for ( $i = 0; $i <= $amount_sc_per_day * 2; $i++ ) {
					$possible_time = gmmktime( $possible_hours[ $i ], 0, 0, gmdate( 'm' ), gmdate( 'd' ) + $ii, gmdate( 'Y' ) );

					if ( $possible_time >= $next_possible_sc ) {
						$date_next_sc = $possible_time; // This is the next possible time. So we break here.
						break;
					}
				}

				// Don't check for tomorrow if we found the next date today.
				if ( $date_next_sc ) {
					break;
				}
			}
		}

		// Check for 30 min intervals.
		if ( 0.5 === $group['interval_in_h'] ) {
			$amount_sc_per_day = 48;
			if ( gmdate( 'i' ) < 30 ) {
				$date_next_sc = gmmktime( gmdate( 'H' ), 30, 0, gmdate( 'm' ), gmdate( 'd' ), gmdate( 'Y' ) );
			} else {
				$date_next_sc = gmmktime( gmdate( 'H' ) + 1, 0, 0, gmdate( 'm' ), gmdate( 'd' ), gmdate( 'Y' ) );
			}
		}
		// Check for 15 min intervals.
		if ( 0.25 === $group['interval_in_h'] ) {
			$amount_sc_per_day = 96;
			if ( gmdate( 'i' ) < 15 ) {
				$date_next_sc = gmmktime( gmdate( 'H' ), 15, 0, gmdate( 'm' ), gmdate( 'd' ), gmdate( 'Y' ) );
			} elseif ( gmdate( 'i' ) < 30 ) {
				$date_next_sc = gmmktime( gmdate( 'H' ), 30, 0, gmdate( 'm' ), gmdate( 'd' ), gmdate( 'Y' ) );
			} elseif ( gmdate( 'i' ) < 45 ) {
				$date_next_sc = gmmktime( gmdate( 'H' ), 45, 0, gmdate( 'm' ), gmdate( 'd' ), gmdate( 'Y' ) );
			} else {
				$date_next_sc = gmmktime( gmdate( 'H' ) + 1, 0, 0, gmdate( 'm' ), gmdate( 'd' ), gmdate( 'Y' ) );
			}
		}

		// Calculate screenshots until renewal.
		$days_until_renewal = gmdate( 'd', gmdate( 'U', strtotime( $this->get_account()['renewal_at'] ) ) - gmdate( 'U' ) );

		$amount_group_sc_per_day = $group['selected_urls_count'] * $amount_sc_per_day * $days_until_renewal;

		// Get first detection hour.
		$first_hour_of_interval = $group['hour_of_day'];
		while ( $first_hour_of_interval - $group['interval_in_h'] >= 0 ) {
			$first_hour_of_interval = $first_hour_of_interval - $group['interval_in_h'];
		}

		// Count up in interval_in_h to current hour.
		$skip_sc_count_today = 0;
		while ( $first_hour_of_interval + $group['interval_in_h'] <= gmdate( 'H' ) ) {
			$first_hour_of_interval = $first_hour_of_interval + $group['interval_in_h'];
			++$skip_sc_count_today;
		}

		// Subtract screenshots already taken today.
		$total_sc_current_period = $amount_group_sc_per_day - $skip_sc_count_today * $group['selected_urls_count'];
		?>

		<div class="status_bar">
			<div class="box full">
				<div id="txt_next_sc_in">Next monitoring checks in</div>
				<div id="next_sc_in" class="big"></div>
				<div id="next_sc_date" class="local-time" data-date="<?php echo esc_html( $date_next_sc ); ?>"></div>
				<div id="sc_available_until_renew"
					data-amount_selected_urls="<?php echo esc_html( $group['selected_urls_count'] ); ?>"
					data-auto_sc_per_url_until_renewal="<?php echo esc_html( $total_sc_current_period ); ?>"></div>
			</div>
		</div>
		<?php
	}

	/** Group settings and url selection view.
	 *
	 * @param bool $monitoring_group Is it a monitoring group.
	 *
	 * @return void
	 */
	public function get_url_settings( $monitoring_group = false ) {
		// Sync urls - post_types defined in function @TODO make settings for post_types to sync.

		if ( ! empty( $_GET['_wpnonce'] ) && ! wp_verify_nonce( wp_unslash( sanitize_key( $_GET['_wpnonce'] ) ) ) ) {
			echo 'Something went wrong. Try again.';
			wp_die();
		}

		if ( $monitoring_group ) {
			$group_id = $this->monitoring_group_uuid;
		} else {
			$group_id = $this->manual_group_uuid;
		}

		// Setting pagination page.
		$page = 1;
		if ( ! empty( $_GET['paged'] ) ) {
			$page = sanitize_key( wp_unslash( $_GET['paged'] ) );
		}

		// Set filters for urls.
		$filters = array(
			'per_page' => 20,
			'sorted'   => 'selected',
			'page'     => $page,
		);

		$pagination_params = array();
		if ( ! empty( $_GET['post-type'] ) ) {
			$filters['category']            = $this->get_post_type_name( sanitize_text_field( wp_unslash( $_GET['post-type'] ) ) );
			$pagination_params['post-type'] = sanitize_text_field( wp_unslash( $_GET['post-type'] ) );
		}
		if ( ! empty( $_GET['taxonomy'] ) ) {
			$filters['category']           = $this->get_taxonomy_name( sanitize_text_field( wp_unslash( $_GET['taxonomy'] ) ) );
			$pagination_params['taxonomy'] = sanitize_text_field( wp_unslash( $_GET['post-type'] ) );

		}
		if ( ! empty( $_GET['search'] ) ) {
			$filters['search']           = sanitize_text_field( wp_unslash( $_GET['search'] ) );
			$pagination_params['search'] = sanitize_text_field( wp_unslash( $_GET['search'] ) );
		}

		// Get the urls.
		$group_and_urls = $this->get_group_and_urls( $group_id, $filters );
		$urls           = $group_and_urls['urls'];
		$urls_meta      = $group_and_urls['meta'];

		// Set tab for the right url.
		$tab = 'update-settings'; // init.
		if ( $monitoring_group ) {
			$tab = 'auto-settings';
		}

		// Show message if no urls are selected.
		if ( ! $group_and_urls['selected_urls_count'] ) {
			?>
			<div class="notice notice-warning"><p><strong>WebChange Detector:</strong> Select URLs for manual checks to get started.</p></div>
			<?php
		}

		$nonce = wp_create_nonce( 'ajax-nonce' );
		?>

		<div class="wcd-select-urls-container">
			<?php

			// Include the group settings.
			if ( ! $monitoring_group ) {
				include 'partials/templates/update-settings.php';
			} else {

				// Print the status bar.
				$this->print_monitoring_status_bar( $group_and_urls );

				// Monitoring settings.
				include 'partials/templates/auto-settings.php';
			}

			// Select URLs section.
			$wizard_text = '<h2>Select URLs</h2><p>In these accordions you find all URLs of your website. 
                            Here you can select the URLs you want to check.</p><p>
                            These settings are taken for manual checks and for auto update checks.</p><p>
                            Your don\'t have to hit the \'save\' button here. Enabling and disabling URLs are saved automatically.</p>';
			$this->print_wizard(
				$wizard_text,
				'wizard_manual_checks_urls',
				'wizard_manual_checks_start',
				false,
				false,
				'bottom top-minus-200 left-plus-400'
			);

			$wizard_text = '<h2>Select URLs</h2>All URLs which you select here will be monitored with settings before.';
			$this->print_wizard(
				$wizard_text,
				'wizard_monitoring_urls',
				false,
				'?page=webchangedetector-change-detections',
				false,
				'bottom top-minus-100 left-plus-100'
			);

			if ( ( ! $monitoring_group && $this->is_allowed( 'manual_checks_urls' ) ) || ( $monitoring_group && $this->is_allowed( 'monitoring_checks_urls' ) ) ) {
				?>

			<div class="wcd-frm-settings box-plain">
				<h2>Select URLs<br><small></small></h2>
				<p style="text-align: center;">
					<strong>Currently selected URLs: <?php echo esc_html( $group_and_urls['selected_urls_count'] ); ?></strong><br>
					Missing URLs? Select them from other post types and taxonomies by enabling them in the
					<a href="?page=webchangedetector-settings">Settings</a><br>
					Last url sync:
					<span data-nonce='<?php echo esc_html( wp_create_nonce( 'ajax-nonce' ) ); ?>' id='ajax_sync_urls_status' >
						<?php echo esc_html( date_i18n( 'd.m.Y H:i', get_option( 'wcd_last_urls_sync' ) ) ); ?>
					</span>
					<script>jQuery(document).ready(function() {sync_urls(); });</script>
				</p>
				<input type="hidden" value="webchangedetector" name="page">
				<input type="hidden" value="<?php echo esc_html( $group_and_urls['id'] ); ?>" name="group_id">

					<div class="group_urls_container">
						<form method="get" style="float: left;">
							<input type="hidden" name="page" value="webchangedetector-<?php echo esc_html( $tab ); ?>">

							Post types
							<select id="filter-post-type" name="post-type">
								<option value="0">All</option>
								<?php
								$selected_post_type = isset( $_GET['post-type'] ) ? sanitize_text_field( wp_unslash( $_GET['post-type'] ) ) : array();

								if ( ! get_option( 'page_on_front' ) && ! in_array( 'frontpage', array_column( $this->website_details['sync_url_types'], 'post_type_slug' ), true ) ) {
									?>
									<option value="frontpage" <?php echo 'frontpage' === $selected_post_type ? 'selected' : ''; ?> >Frontpage</option>
									<?php
								}

								foreach ( $this->website_details['sync_url_types'] as $url_type ) {
									if ( 'types' !== $url_type['url_type_slug'] ) {
										continue;
									}
									$selected = $url_type['post_type_slug'] === $selected_post_type ? 'selected' : '';
									?>
									<option value="<?php echo esc_html( $url_type['post_type_slug'] ); ?>" <?php echo esc_html( $selected ); ?>>
										<?php echo esc_html( $this->get_post_type_name( $url_type['post_type_slug'] ) ); ?>
									</option>
								<?php } ?>
							</select>

							Taxonomies
							<select id="filter-taxonomy" name="taxonomy">
								<option value="0">All</option>
								<?php
								$selected_post_type = isset( $_GET['taxonomy'] ) ? sanitize_text_field( wp_unslash( $_GET['taxonomy'] ) ) : '';

								foreach ( $this->website_details['sync_url_types'] as $url_type ) {
									if ( 'types' === $url_type['url_type_slug'] ) {
										continue;
									}
									$selected = $url_type['post_type_slug'] === $selected_post_type ? 'selected' : '';
									?>
									<option value="<?php echo esc_html( $url_type['post_type_slug'] ); ?>" <?php echo esc_html( $selected ); ?>>
										<?php echo esc_html( $this->get_taxonomy_name( $url_type['post_type_slug'] ) ); ?>
									</option>
								<?php } ?>
							</select>
							<button class="button button-secondary">Filter</button>
						</form>

						<script>
							jQuery("#filter-post-type").change(function() {
								if(jQuery(this).val() !== '0') {
									jQuery('#filter-taxonomy').val(0);
								}
							});

							jQuery("#filter-taxonomy").change(function() {
								if(jQuery(this).val() !== '0') {
									jQuery('#filter-post-type').val(0);
								}
							});
						</script>

						<form method="get" style="float: right;">
							<input type="hidden" name="page" value="webchangedetector-<?php echo esc_html( $tab ); ?>">
							<button type="submit" style="float: right" class="button button-secondary">Search</button>
							<input style="margin: 0" name="search" type="text" placeholder="Search" value="<?php echo esc_html( sanitize_text_field( wp_unslash( $_GET['search'] ?? '' ) ) ); ?>">
						</form>
						<div class="clear" style="margin-bottom: 20px;"></div>

						<table class="no-margin filter-table">
							<tr>
								<th style="min-width: 50px; text-align: center;"><?php $this->get_device_icon( 'desktop' ); ?><br>Desktop</th>
								<th style="min-width: 50px; text-align: center;"><?php $this->get_device_icon( 'mobile' ); ?> Mobile</th>
								<th style="width: 100%">URL</th>
								<th style="min-width: 90px">Post type</th>
							</tr>
							<?php if ( count( $urls ) ) { ?>
								<?php // Select all from same device. ?>
								<tr class=" even-tr-white" style="background: none; text-align: center">
									<td>
										<label class="switch">
											<input type="checkbox"
											id="select-desktop"
											data-nonce="<?php echo esc_html( $nonce ); ?>"
											data-screensize="desktop"
											onclick="mmToggle( this, 'desktop', '<?php echo esc_html( $group_and_urls['id'] ); ?>' ); postUrl('select-desktop');"/>
											<span class="slider round"></span>
										</label>
									</td>

									<td>
										<label class="switch">
											<input type="checkbox"
											id="select-mobile"
											data-nonce="<?php echo esc_html( $nonce ); ?>"
											data-screensize="mobile"
											onclick="mmToggle( this, 'mobile', '<?php echo esc_html( $group_and_urls['id'] ); ?>' ); postUrl('select-mobile');" />
											<span class="slider round"></span>
										</label>
									</td>
									<td></td>
									<td></td>
								</tr>
								<?php

								foreach ( $urls as $url ) {
									// init.
									$checked = array(
										'desktop' => $url['desktop'] ? 'checked' : '',
										'mobile'  => $url['mobile'] ? 'checked' : '',
									);
									?>
									<tr class="live-filter-row even-tr-white post_id_<?php echo esc_html( $group_and_urls['id'] ); ?>" id="<?php echo esc_html( $url['id'] ); ?>" >
										<td class="checkbox-desktop" style="text-align: center;">
											<input type="hidden" value="0" name="desktop-<?php echo esc_html( $url['id'] ); ?>">
											<label class="switch">
												<input type="checkbox"
												data-nonce="<?php echo esc_html( $nonce ); ?>"
												data-type="<?php echo esc_html( lcfirst( $url['category'] ) ); ?>"
												data-screensize="desktop"
												data-url_id="<?php echo esc_html( $url['id'] ); ?>"
												name="desktop-<?php echo esc_html( $url['id'] ); ?>"
												value="1" <?php echo esc_html( $checked['desktop'] ); ?>
												id="desktop-<?php echo esc_html( $url['id'] ); ?>"
												onclick="mmMarkRows('<?php echo esc_html( $url['id'] ); ?>'); postUrl('<?php echo esc_html( $url['id'] ); ?>');" >
												<span class="slider round"></span>
											</label>
										</td>

										<td class="checkbox-mobile" style="text-align: center;">
										<input type="hidden" value="0" name="mobile-<?php echo esc_html( $url['id'] ); ?>">
										<label class="switch">
											<input type="checkbox"
											data-nonce="<?php echo esc_html( $nonce ); ?>"
											data-type="<?php echo esc_html( lcfirst( $url['category'] ) ); ?>"
											data-screensize="mobile"
											data-url_id="<?php echo esc_html( $url['id'] ); ?>"
											name="mobile-<?php echo esc_html( $url['id'] ); ?>"
											value="1" <?php echo esc_html( $checked['mobile'] ); ?>
											id="mobile-<?php echo esc_html( $url['id'] ); ?>"
											onclick="mmMarkRows('<?php echo esc_html( $url['id'] ); ?>'); postUrl('<?php echo esc_html( $url['id'] ); ?>');" >
											<span class="slider round"></span>
										</label>
										</td>

										<td style="text-align: left;">
											<strong><?php echo esc_html( $url['html_title'] ); ?></strong><br>
											<a href="<?php echo ( is_ssl() ? 'https://' : 'http://' ) . esc_html( $url['url'] ); ?>" target="_blank"><?php echo esc_html( $url['url'] ); ?></a>
										</td>
										<td><?php echo esc_html( $url['category'] ); ?></td>
									</tr>

									<script> mmMarkRows('<?php echo esc_html( $url['id'] ); ?>'); </script>

									<?php
								}
							} else {
								?>
								<tr>
									<td colspan="4" style="text-align: center; font-weight: 700; padding: 20px 0;">
										No Urls to show.
									</td>
								</tr>
							<?php } ?>
						</table>
					</div>

					<!-- Pagination -->
					<?php if ( ! empty( $urls_meta['total'] ) ) { ?>
					<div class="tablenav">
						<div class="tablenav-pages">
							<span class="displaying-num"><?php echo esc_html( $urls_meta['total'] ); ?> items</span>
							<span class="pagination-links">
							<?php
							foreach ( $urls_meta['links'] as $link ) {
								$pagination_page = $this->get_params_of_url( $link['url'] )['page'] ?? '';
								if ( ! $link['active'] && $pagination_page ) {
									$pagination_link = '?page=webchangedetector-' . esc_html( $tab ) . '&paged=' . esc_html( $pagination_page ) . '&' . esc_html( build_query( $pagination_params ) );
									$pagination_link = wp_nonce_url( $pagination_link );
									?>
									<a class="tablenav-pages-navspan button"
										href="<?php echo esc_html( $pagination_link ); ?>">
										<?php echo esc_html( $link['label'] ); ?>
									</a>
								<?php } else { ?>
									<span class="tablenav-pages-navspan button" disabled=""><?php echo esc_html( $link['label'] ); ?></span>
									<?php
								}
							}
							?>
							</span>
						</div>
					</div>

					<script>
						if(<?php echo isset( $_GET['paged'] ) ? 1 : 0; ?> ||
						<?php echo isset( $_GET['search'] ) ? 1 : 0; ?> ||
						<?php echo isset( $_GET['post-type'] ) ? 1 : 0; ?> ||
						<?php echo isset( $_GET['taxonomy'] ) ? 1 : 0; ?> ) {
							const scrollToEl = jQuery('.group_urls_container');
							jQuery('html').animate(
								{
									scrollTop: scrollToEl.offset().top,
								},
								0 //speed
							);
						}
					</script>
				<?php } ?>
			</div>
			<?php } ?>
		</div>

		<?php
		// Some more wizards windows.
		if ( $monitoring_group ) {
			$wizard_text = "<h2>Save</h2>Don't forget to save the settings.";
			$this->print_wizard(
				$wizard_text,
				'wizard_save_monitoring',
				false,
				'?page=webchangedetector-change-detections',
				false,
				'bottom bottom-plus-100 left-minus-100'
			);

		} else {
			$wizard_text = '<h2>Start Manual Checks</h2><p>When you want to do updates or other changes and check your selected websites, start the wizard here.</p><p>
                            The wizard guides you through the process.</p>';
			$this->print_wizard(
				$wizard_text,
				'wizard_manual_checks_start',
				false,
				'?page=webchangedetector-auto-settings',
				false,
				'bottom bottom-plus-50 right-minus-30'
			);

			// Start change detection button.
			if ( $this->is_allowed( 'manual_checks_start' ) ) {
				?>
					<form method="post">
						<?php wp_nonce_field( 'start_manual_checks' ); ?>
						<input type="hidden" name="wcd_action" value="start_manual_checks">
						<input type="hidden" name="step" value="<?php echo esc_html( WCD_OPTION_UPDATE_STEP_PRE ); ?>">

						<button
								class="button button-primary"
								style="float: right;"
								type="submit"
						>
							Start manual checks >
						</button>
					</form>
				<?php
			}
		}
	}

	/** Get available post_types. We use 'rest_base' names if available as this is used by WP REST API too.
	But it's not always available. So we take 'name' as fallback name.
	 *
	 * @param object $post_type The post_type object.
	 *
	 * @return string|bool
	 */
	public function get_post_type_slug( $post_type ) {

		$wp_post_type_slug = $post_type->rest_base;
		if ( ! $wp_post_type_slug ) {
			$wp_post_type_slug = $post_type->name;
		}
		return $wp_post_type_slug ?? false;
	}

	/**
	 * Get name of the post_type
	 *
	 * @param string $post_type_slug The post_type slug.
	 * @return string|bool
	 */
	public function get_post_type_name( $post_type_slug ) {
		if ( 'frontpage' === $post_type_slug ) {
			return 'Frontpage';
		}

		$post_types = get_post_types( array( 'public' => true ), 'objects' );

		foreach ( $post_types as $post_type ) {
			$wp_post_type_slug = $this->get_post_type_slug( $post_type );

			if ( $wp_post_type_slug === $post_type_slug ) {
				return $post_type->labels->name;
			}
		}
		return false;
	}

	/** Get the right taxonomy slug which we use in WCD.
	 *
	 * @param object $taxonomy The taxonomy object.
	 *
	 * @return string|bool
	 */
	public function get_taxonomy_slug( $taxonomy ) {
		$wp_taxonomy_slug = $taxonomy->rest_base;
		if ( ! $wp_taxonomy_slug ) {
			$wp_taxonomy_slug = $taxonomy->name;
		}
		return $wp_taxonomy_slug ?? false;
	}

	/**
	 * Get name of the taxonomy
	 *
	 * @param string $taxonomy_slug The taxonomy slug.
	 * @return string|bool
	 */
	public function get_taxonomy_name( $taxonomy_slug ) {
		static $taxonomies;
		if ( ! $taxonomies ) {
			$taxonomies = get_taxonomies( array( 'public' => true ), 'objects' );
		}

		foreach ( $taxonomies as $post_type ) {
			$wp_taxonomy_slug = $this->get_taxonomy_slug( $post_type );

			if ( $wp_taxonomy_slug === $taxonomy_slug ) {
				return $post_type->labels->name;
			}
		}
		return false;
	}

	/** Save url settings.
	 *  TODO: Optional save settings for monitoring and manual checks.
	 *
	 * @param array $postdata The postdata.
	 *
	 * @return void
	 */
	public function post_urls( $postdata ) {
		$active_posts   = array();
		$count_selected = 0;

		foreach ( $postdata as $key => $post ) {
			$already_processed_ids = array();
			if ( 0 === strpos( $key, 'desktop-' ) || 0 === strpos( $key, 'mobile-' ) ) {

				$post_id = 0 === strpos( $key, 'desktop-' ) ? substr( $key, strlen( 'desktop-' ) ) : substr( $key, strlen( 'mobile-' ) );

				// Make sure to not process same post_id twice.
				if ( in_array( $post_id, $already_processed_ids, true ) ) {
					continue;
				}
				$already_processed_ids[] = $post_id;

				$desktop = array_key_exists( 'desktop-' . $post_id, $postdata ) ? ( $postdata[ 'desktop-' . $post_id ] ) : null;
				$mobile  = array_key_exists( 'mobile-' . $post_id, $postdata ) ? ( $postdata[ 'mobile-' . $post_id ] ) : null;

				$new_post = array( 'id' => $post_id );
				if ( ! is_null( $desktop ) ) {
					$new_post['desktop'] = $desktop;
				}
				if ( ! is_null( $mobile ) ) {
					$new_post['mobile'] = $mobile;
				}
				$active_posts[] = $new_post;

				if ( isset( $postdata[ 'desktop-' . $post_id ] ) && 1 === $postdata[ 'desktop-' . $post_id ] ) {
					++$count_selected;
				}

				if ( isset( $postdata[ 'mobile-' . $post_id ] ) && 1 === $postdata[ 'mobile-' . $post_id ] ) {
					++$count_selected;
				}
			}
		}

		$group_id_website_details = sanitize_text_field( $postdata['group_id'] );
		WebChangeDetector_API_V2::update_urls_in_group_v2( $group_id_website_details, $active_posts );

		// TODO Make return to show the result.
		echo '<div class="updated notice"><p>Settings saved.</p></div>';
	}

	/** Print the wizard.
	 *
	 * @param string $text The wizard element text.
	 * @param string $this_id Current wizard element id.
	 * @param string $next_id Next wizard element id.
	 * @param string $next_link Next wizard element url.
	 * @param bool   $visible Is the wizard element visible by default.
	 * @param string $extra_classes Extra css classes.
	 * @return void
	 */
	public function print_wizard( $text, $this_id, $next_id = false, $next_link = false, $visible = false, $extra_classes = false ) {
		if ( get_option( 'wcd_wizard' ) && $this->is_allowed( 'wizard_start' ) ) {
			?>
			<div id="<?php echo esc_html( $this_id ); ?>" class="wcd-wizard  <?php echo esc_html( $extra_classes ); ?>">
				<?php
				echo wp_kses(
					$text,
					array(
						'h2' => true,
						'br' => true,
						'p'  => true,
						'a'  => array(
							'href'   => true,
							'target' => true,
						),
					)
				);
				?>
				<div style="margin-top: 20px; ">
					<div style="float: left;">
						<form method="post">
							<input type="hidden" name="wcd_action" value="disable_wizard">
							<?php wp_nonce_field( 'disable_wizard' ); ?>
							<input type="submit" class="button button-danger"style="color: darkred;" href="" value="Exit wizard">
						</form>

					</div>
					<?php if ( $next_id ) { ?>
					<div style="float:right;">
						<a style=" margin-left: auto; margin-right: 0;" class="button" href="" onclick="
								jQuery('#<?php echo esc_html( $this_id ); ?>').fadeOut();
								jQuery('#<?php echo esc_html( $next_id ); ?>').fadeIn();
								document.getElementById('<?php echo esc_html( $next_id ); ?>').scrollIntoView({behavior: 'smooth'});
								return false;">
							Next
						</a>
					</div>
					<?php } ?>
					<?php if ( $next_link ) { ?>
					<div style="float:right;">
						<a style=" margin-left: auto; margin-right: 0;" class="button" href="<?php echo esc_html( $next_link ); ?>" >Next</a>
					</div>
						<div class="clear"></div>
					<?php } ?>
				</div>
			</div>
			<?php if ( $visible ) { ?>
				<script>
					jQuery("#<?php echo esc_html( $this_id ); ?>").show();
				</script>
				<?php
			}
		}
	}

	/**
	 * No-account page.
	 *
	 * @param string $api_token The api token.
	 */
	public function get_no_account_page( $api_token = '' ) {
		$user      = wp_get_current_user();
		$user_meta = get_user_meta( $user->ID );

		// If we have a reseller url, we get the api token from there.
		if ( defined( 'WCD_RESELLER_URL' ) && WCD_RESELLER_URL ) {
			$body_args = array(
				'name_first' => ! empty( $user_meta['first_name'][0] ) ? $user_meta['first_name'][0] : 'n/a',
				'name_last'  => ! empty( $user_meta['last_name'][0] ) ? $user_meta['last_name'][0] : 'n/a',
				'email'      => $user->user_email,
				'domain'     => self::get_domain_from_site_url(),
			);

			$response   = wp_remote_post( WCD_RESELLER_URL, array( 'body' => $body_args ) );
			$subaccount = json_decode( wp_remote_retrieve_body( $response ), true );
			if ( ! empty( $subaccount['api_token'] ) ) {
				$this->save_api_token( $subaccount, $subaccount['api_token'] );

				wp_safe_redirect( '/wp-admin/admin.php?page=webchangedetector' );
				exit;
			} else {
				echo '<div class="notice notice-error"><p>' . esc_html( wp_remote_retrieve_body( $response ) ) . '</p></div>';
				return;
			}
		}

		if ( isset( $_POST['wcd_action'] ) && 'create_free_account' === sanitize_text_field( wp_unslash( $_POST['wcd_action'] ) ) ) {
			check_admin_referer( 'create_free_account' );
		}

		$first_name = isset( $_POST['name_first'] ) ? sanitize_text_field( wp_unslash( $_POST['name_first'] ) ) : wp_get_current_user()->user_firstname;
		$last_name  = isset( $_POST['name_last'] ) ? sanitize_text_field( wp_unslash( $_POST['name_last'] ) ) : wp_get_current_user()->user_lastname;
		$email      = isset( $_POST['email'] ) ? sanitize_text_field( wp_unslash( $_POST['email'] ) ) : wp_get_current_user()->user_email;
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
							<?php wp_nonce_field( 'create_free_account' ); ?>
							<input type="text" name="name_first" placeholder="First Name" value="<?php echo esc_html( $first_name ); ?>" required>
							<input type="text" name="name_last" placeholder="Last Name" value="<?php echo esc_html( $last_name ); ?>" required>
							<input type="email" name="email" placeholder="Email" value="<?php echo esc_html( $email ); ?>" required>
							<input type="password" name="password" placeholder="Password" required>

							<input type="submit" class="button-primary" value="Create Free Account">
						</form>
					</div>
				</div>

				<?php $this->get_api_token_form( $api_token ); ?>
			</div>
		</div>
		<?php
	}

	/**
	 * Get Website details.
	 *
	 * @return array|bool The website details.
	 */
	public function get_website_details() {
		$args = array(
			'action' => 'get_website_details',
			// domain sent at mm_api.
		);
		$website_details = $this->api_v1( $args );

		if ( ! empty( $website_details[0] ) ) {
			$website_details = $website_details[0];
		}
		if ( isset( $website_details['sync_url_types'] ) ) {
			$website_details['sync_url_types'] = json_decode( $website_details['sync_url_types'], 1 );
		}

		$update = false;

		// Set default sync types.
		if ( ! empty( $website_details ) && empty( $website_details['sync_url_types'] ) ) {
			$update = true;
			if ( $this->is_allowed( 'only_frontpage' ) ) {
				$website_details['sync_url_types'] = array(
					array(
						'url_type_slug'  => 'types',
						'url_type_name'  => 'frontpage',
						'post_type_slug' => 'frontpage',
						'post_type_name' => 'Frontpage',
					),
				);
			} else {
				$website_details['sync_url_types'] = array(
					array(
						'url_type_slug'  => 'types',
						'url_type_name'  => 'Post Types',
						'post_type_slug' => 'posts',
						'post_type_name' => $this->get_post_type_name( 'posts' ),
					),
					array(
						'url_type_slug'  => 'types',
						'url_type_name'  => 'Post Types',
						'post_type_slug' => 'pages',
						'post_type_name' => $this->get_post_type_name( 'pages' ),
					),
				);
			}
		}

		// Set default auto update settings.
		if ( ! empty( $website_details ) && empty( $website_details['auto_update_settings'] ) ) {
			$update                                  = true;
			$website_details['auto_update_settings'] = array(
				'auto_update_checks_enabled'   => '0',
				'auto_update_checks_from'      => gmdate( 'H:i' ),
				'auto_update_checks_to'        => gmdate( 'H:i', strtotime( '+12 hours' ) ),
				'auto_update_checks_monday'    => '1',
				'auto_update_checks_tuesday'   => '1',
				'auto_update_checks_wednesday' => '1',
				'auto_update_checks_thursday'  => '1',
				'auto_update_checks_friday'    => '1',
				'auto_update_checks_saturday'  => '0',
				'auto_update_checks_sunday'    => '0',
				'auto_update_checks_emails'    => get_option( 'admin_email' ),
			);
			$local_auto_update_settings              = get_option( WCD_AUTO_UPDATE_SETTINGS );
			if ( $local_auto_update_settings && is_array( $local_auto_update_settings ) ) {
				delete_option( WCD_AUTO_UPDATE_SETTINGS );
				$website_details['auto_update_settings'] = array_merge( $website_details['auto_update_settings'], $local_auto_update_settings );
			}
		}

		if ( $update ) {
			$this->update_website_details( $website_details );
		}

		return $website_details ?? false;
	}

	/** Check if string is json.
	 *
	 * @param string $data The string to check.
	 * @return bool
	 */
	public function is_json( $data ) {
		if ( ! empty( $data ) ) {
			return is_string( $data ) && is_array( json_decode( $data, true ) );
		}
		return false;
	}

	/** Update website_details with current settings
	 *
	 * @param bool $update_website_details Website details to update.
	 *
	 * @return void
	 */
	public function update_website_details( $update_website_details = false ) {
		if ( ! $update_website_details ) {
			$update_website_details = $this->website_details;
		}

		$this->api_v1(
			array_merge(
				array( 'action' => 'save_user_website' ),
				$update_website_details
			)
		);
	}

	/** Check if current account is allowed for view.
	 *
	 * @param string $allowed The allowance string.
	 * @return mixed|true
	 */
	public function is_allowed( $allowed ) {
		$allowances = $this->website_details['allowances'] ?? false;

		// Set default allowances if we don't have any yet. Shouldn't happen as they come from the api.
		if ( empty( $allowances ) ) {
			$allowances = array(
				'change_detections_view'     => 1,
				'manual_checks_view'         => 1,
				'manual_checks_start'        => 1,
				'manual_checks_settings'     => 1,
				'manual_checks_urls'         => 1,
				'monitoring_checks_view'     => 1,
				'monitoring_checks_settings' => 1,
				'monitoring_checks_urls'     => 1,
				'logs_view'                  => 1,
				'settings_view'              => 1,
				'settings_add_urls'          => 1,
				'settings_account_settings'  => 1,
				'upgrade_account'            => 1,
				'wizard_start'               => 1,
				'only_frontpage'             => 0,
			);
		}

		// Disable upgrade account for subaccounts.
		if ( ! empty( $this->get_account()['is_subaccount'] ) && $this->get_account()['is_subaccount'] ) {
			$allowances['upgrade_account'] = 0;
		}

		// need them as option for the admin menu.
		update_option( WCD_ALLOWANCES, ( $allowances ) );

		// Return allowance value if exists.
		if ( array_key_exists( $allowed, $allowances ) ) {
			return $allowances[ $allowed ];
		}

		// Shouldn't get here. But if so, we allow.
		return true;
	}

	/** View of tabs
	 *
	 * @return void
	 */
	public function tabs() {
		$active_tab = 'webchangedetector'; // init.

		if ( ! empty( $_GET['_wpnonce'] ) && ! wp_verify_nonce( wp_unslash( sanitize_key( $_GET['_wpnonce'] ) ) ) ) {
			echo 'Something went wrong. Please try again.';
		}

		if ( isset( $_GET['page'] ) ) {
			$active_tab = sanitize_text_field( wp_unslash( $_GET['page'] ) );
		}
		?>
		<div class="wrap">
			<h2 class="nav-tab-wrapper">
				<?php if ( $this->is_allowed( 'dashboard_view' ) ) { ?>
				<a href="?page=webchangedetector"
					class="nav-tab <?php echo 'webchangedetector' === $active_tab ? 'nav-tab-active' : ''; ?>">
					<?php $this->get_device_icon( 'dashboard' ); ?> Dashboard
				</a>
				<?php } ?>
				<?php if ( $this->is_allowed( 'manual_checks_view' ) ) { ?>
				<a href="?page=webchangedetector-update-settings"
					class="nav-tab <?php echo 'webchangedetector-update-settings' === $active_tab ? 'nav-tab-active' : ''; ?>">
					<?php $this->get_device_icon( 'update-group' ); ?> Manual Checks & Auto Update Checks
				</a>
				<?php } ?>
				<?php if ( $this->is_allowed( 'monitoring_checks_view' ) ) { ?>
					<a href="?page=webchangedetector-auto-settings"
					class="nav-tab <?php echo 'webchangedetector-auto-settings' === $active_tab ? 'nav-tab-active' : ''; ?>">
					<?php $this->get_device_icon( 'auto-group' ); ?> Monitoring
				</a>
				<?php } ?>
				<?php if ( $this->is_allowed( 'change_detections_view' ) ) { ?>
					<a href="?page=webchangedetector-change-detections"
					class="nav-tab <?php echo 'webchangedetector-change-detections' === $active_tab ? 'nav-tab-active' : ''; ?>">
					<?php $this->get_device_icon( 'change-detections' ); ?> Change Detections
				</a>
				<?php } ?>
				<?php if ( $this->is_allowed( 'logs_view' ) ) { ?>
				<a href="?page=webchangedetector-logs"
					class="nav-tab <?php echo 'webchangedetector-logs' === $active_tab ? 'nav-tab-active' : ''; ?>">
					<?php $this->get_device_icon( 'logs' ); ?> Queue
				</a>
				<?php } ?>
				<?php if ( $this->is_allowed( 'settings_view' ) ) { ?>
				<a href="?page=webchangedetector-settings"
					class="nav-tab <?php echo 'webchangedetector-settings' === $active_tab ? 'nav-tab-active' : ''; ?>">
					<?php $this->get_device_icon( 'settings' ); ?> Settings
				</a>
				<?php } ?>
				<?php if ( $this->is_allowed( 'upgrade_account' ) ) { ?>
				<a href="<?php echo esc_url( $this->get_upgrade_url() ); ?>" target="_blank"
					class="nav-tab upgrade">
					<?php $this->get_device_icon( 'upgrade' ); ?> Upgrade Account
				</a>
				<?php } ?>
			</h2>
		</div>

		<?php
	}

	/** Get the dashboard view.
	 *
	 * @param array $client_account Account data.
	 *
	 * @return void
	 */
	public function get_dashboard_view( $client_account ) {

		$auto_group   = $this->get_group_and_urls( $this->monitoring_group_uuid, array( 'per_page' => 1 ) );
		$update_group = $this->get_group_and_urls( $this->manual_group_uuid, array( 'per_page' => 1 ) );

		$amount_auto_detection = 0;
		if ( $auto_group['enabled'] ) {
			$amount_auto_detection += WCD_HOURS_IN_DAY / $auto_group['interval_in_h'] * $auto_group['selected_urls_count'] * WCD_DAYS_PER_MONTH;
		}

		$auto_update_settings    = WebChangeDetector_Autoupdates::get_auto_update_settings();
		$max_auto_update_checks  = 0;
		$amount_auto_update_days = 0;

		if ( ! empty( $auto_update_settings['auto_update_checks_enabled'] ) ) {
			foreach ( self::WEEKDAYS as $weekday ) {
				if ( isset( $auto_update_settings[ 'auto_update_checks_' . $weekday ] ) && ! empty( $auto_update_settings[ 'auto_update_checks_' . $weekday ] ) ) {
					++$amount_auto_update_days;
				}
			}
			$max_auto_update_checks = $update_group['selected_urls_count'] * $amount_auto_update_days * 4; // multiplied by weekdays in a month.
		}

		$wizard_text = '<h2>Welcome to WebChange Detector</h2><p>This Wizard helps you to get started with your website Checks.</p><p>
                        You can exit the wizard any time and restart it from the dashboard.</p>';
		$this->print_wizard(
			$wizard_text,
			'wizard_dashboard_welcome',
			'wizard_dashboard_account',
			false,
			true,
			' top-plus-200 left-plus-400'
		);
		?>
		<div class="dashboard">
			<div class="no-border box-plain">
				<div class="box-half no-border">
					<p>
						<img src="<?php echo esc_html( $this->get_wcd_plugin_url() ); ?>/admin/img/logo-webchangedetector.png" style="max-width: 200px">
					</p>
					<hr>
					<p>
						Perform visual checks (visual regression tests) on your WordPress website to find
						unwanted visual changes on your web pages before anyone else sees them.
					</p>
					<?php if ( $this->is_allowed( 'wizard_start' ) ) { ?>
					<p>
						Start the Wizard to see what you can do with WebChange Detector.
					</p>
					<form method="post" action="?page=webchangedetector">
						<input type="hidden" name="wcd_action" value="enable_wizard">
						<?php wp_nonce_field( 'enable_wizard' ); ?>
						<input type="submit" class="button button-primary" value="Start Wizard">
					</form>
				<?php } ?>
				</div>
				<?php
				$wizard_text = '<h2>Your Account</h2>See how many checks you have left and how many checks are used with your current settings until renewal.';
				$this->print_wizard(
					$wizard_text,
					'wizard_dashboard_account',
					'wizard_dashboard_change_detections',
					false,
					false,
					'right top-plus-200 left-plus-100'
				);

				?>
				<div class="box-half credit">
					<?php if ( empty( $client_account['is_subaccount'] ) ) { ?>
						<p style="margin-top: 20px;">
							<strong>Your Plan:</strong>
							<?php echo esc_html( $client_account['plan_name'] ); ?>
							(renews on: <?php echo esc_html( gmdate( 'd/m/Y', strtotime( $client_account['renewal_at'] ) ) ); ?>)
						</p>
					<?php } ?>
					<p style="margin-top:10px;">
						<strong>Used checks:</strong>
						<?php
						$usage_percent = 0;
						if ( ! empty( $client_account['checks_limit'] ) ) {
							$usage_percent = number_format( $client_account['checks_done'] / $client_account['checks_limit'] * 100, 1 );
						}
						?>
						<?php echo esc_html( $client_account['checks_done'] ); ?> /
						<?php echo esc_html( $client_account['checks_limit'] ); ?>
					</p>
					<div style="width: 100%; background: #aaa; height: 20px; display: inline-block; position: relative; text-align: center;">
						<span style="z-index: 5; position: absolute; color: #fff;"><?php echo esc_html( $usage_percent ); ?> %</span>
						<div style="width: <?php echo esc_html( $usage_percent ); ?>%; background: #266ECC; height: 20px; text-align: center; position: absolute"></div>
					</div>
					<?php if ( $this->is_allowed( 'monitoring_checks_view' ) ) { ?>
					<p>
						<strong>Monitoring: </strong>
						<?php
						if ( $amount_auto_detection > 0 ) {
							?>
							<span style="color: green; font-weight: 900;">On</span> (≈ <?php echo esc_html( $amount_auto_detection ) . ' checks / month)'; ?>
						<?php } else { ?>
							<span style="color: red; font-weight: 900">Off</span>
							<?php
						}
						$checks_until_renewal = $amount_auto_detection / WCD_SECONDS_IN_MONTH *
									( gmdate( 'U', strtotime( $client_account['renewal_at'] ) ) - gmdate( 'U' ) );

						?>
					</p>
					<?php } ?>

					<?php if ( $this->is_allowed( 'manual_checks_view' ) || ( defined( 'WCD_AUTO_UPDATES_ENABLED' ) && true === WCD_AUTO_UPDATES_ENABLED ) ) { ?>
					<p>
						<strong>Auto update checks: </strong>
						<?php
						if ( $max_auto_update_checks > 0 ) {
							?>
							<span style="color: green; font-weight: 900;">On</span> (≈ <?php echo esc_html( $max_auto_update_checks ) . ' checks / month)'; ?>
						<?php } else { ?>
							<span style="color: red; font-weight: 900">Off</span>
						<?php } ?>
					</p>
						<?php
					}
					$checks_needed    = $checks_until_renewal + $max_auto_update_checks;
					$checks_available = $client_account['checks_limit'] - $client_account['checks_done'];
					if ( $checks_needed > $checks_available ) {
						?>
						<span class="notice notice-warning" style="display:block; padding: 10px;">
							<?php $this->get_device_icon( 'warning' ); ?>
							<strong>You might run out of checks before renewal day. </strong><br>
							Current settings require up to <?php echo esc_html( number_format( $checks_needed - $checks_available, 0 ) ); ?> more checks. <br>
							<?php if ( ! $client_account['is_subaccount'] ) { ?>
								<a href="<?php echo esc_html( $this->get_upgrade_url() ); ?>">Upgrade your account now.</a>
							<?php } ?>
						</span>
					<?php } ?>


				</div>
				<div class="clear"></div>
			</div>

			<div>
				<h2>Latest Change Detections</h2>
				<?php
				$filter_batches = array(
					'queue_type' => 'post,auto',
					'per_page'   => 5,
				);

				$batches = WebChangeDetector_API_V2::get_batches( $filter_batches )['data'];

				$filter_batches = array();
				foreach ( $batches as $batch ) {
					$filter_batches[] = $batch['id'];
				}

				$recent_comparisons = WebChangeDetector_API_V2::get_comparisons_v2( array( 'batches' => implode( ',', $filter_batches ) ) );

				$wizard_text = "<h2>Change Detections</h2>Your latest change detections will appear here. But first, let's do some checks and create some change detections.";
				$this->print_wizard(
					$wizard_text,
					'wizard_dashboard_change_detections',
					false,
					'?page=webchangedetector-update-settings&wcd-wizard=true',
					false,
					'bottom top-minus-200 left-plus-300'
				);
				$this->compare_view_v2( $recent_comparisons['data'] );

				if ( ! empty( $recent_comparisons ) ) {
					?>
					<p><a class="button" href="?page=webchangedetector-change-detections">Show All Change Detections</a></p>
				<?php } ?>
			</div>

			<div class="clear"></div>
		</div>
		<?php
	}

	/** Show activate account.
	 *
	 * @param string $error The error.
	 * @return false
	 */
	public function show_activate_account( $error ) {

		if ( 'ActivateAccount' === $error ) {
			?>
			<div class="notice notice-info">
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
						<span id="activation_email" style="font-weight: 700;"><?php echo esc_html( get_option( WCD_WP_OPTION_KEY_ACCOUNT_EMAIL ) ); ?></span>
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
					<?php wp_nonce_field( 'reset_api_token' ); ?>
					<input type="submit" class="button-delete" value="Reset Account">
				</form>

			</div>
		</div>
			<?php
		}

		if ( 'unauthorized' === $error ) {
			?>
			<div class="notice notice-error">
				<p>The API token is not valid. Please reset the API token and enter a valid one.</p>
			</div>
			<?php
			$this->get_no_account_page();
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

	/** Get group details and its urls.
	 *
	 * @param string $group_uuid The group id.
	 * @param array  $url_filter Filters for the urls.
	 *
	 * @return mixed
	 */
	public function get_group_and_urls( $group_uuid, $url_filter = array() ) {

		$group_and_urls = WebChangeDetector_API_V2::get_group_v2( $group_uuid )['data'];
		$urls           = WebChangeDetector_API_V2::get_group_urls_v2( $group_uuid, $url_filter );

		if ( empty( $urls['data'] ) ) {
			$this->sync_posts( true );
			$urls = WebChangeDetector_API_V2::get_group_urls_v2( $group_uuid, $url_filter );
		}

		$group_and_urls['urls']                = $urls['data'];
		$group_and_urls['meta']                = $urls['meta'];
		$group_and_urls['selected_urls_count'] = $urls['selected_urls_count'];

		return $group_and_urls;
	}

	/**
	 * Call to V1 API.
	 *
	 * @param array $post Request data.
	 * @param bool  $is_web Is web request.
	 * @return string|array
	 */
	public function api_v1( $post, $is_web = false ) {
		$url     = 'https://api.webchangedetector.com/api/v1/'; // init for production.
		$url_web = 'https://api.webchangedetector.com/';

		// This is where it can be changed to a local/dev address.
		if ( defined( 'WCD_API_URL' ) && is_string( WCD_API_URL ) && ! empty( WCD_API_URL ) ) {
			$url = WCD_API_URL;
		}

		// Overwrite $url if it is a get request.
		if ( $is_web && defined( 'WCD_API_URL_WEB' ) && is_string( WCD_API_URL_WEB ) && ! empty( WCD_API_URL_WEB ) ) {
			$url_web = WCD_API_URL_WEB;
		}

		$url     .= str_replace( '_', '-', $post['action'] ); // add kebab action to url.
		$url_web .= str_replace( '_', '-', $post['action'] ); // add kebab action to url.
		$action   = $post['action']; // For debugging.

		// Get API Token from WP DB.
		$api_token = $post['api_token'] ?? get_option( WCD_WP_OPTION_KEY_API_TOKEN ) ?? null;

		unset( $post['action'] ); // don't need to send as action as it's now the url.
		unset( $post['api_token'] ); // just in case.

		$post['wp_plugin_version'] = WEBCHANGEDETECTOR_VERSION; // API will check this to check compatability.
		// there's checks in place on the API side, you can't just send a different domain here, you sneaky little hacker ;).
		$post['domain'] = self::get_domain_from_site_url();
		$post['wp_id']  = get_current_user_id();

		// Increase timeout for php.ini.
		if ( ! ini_get( 'safe_mode' ) ) {
			set_time_limit( WCD_REQUEST_TIMEOUT + 10 );
		}

		$args = array(
			'timeout' => WCD_REQUEST_TIMEOUT,
			'body'    => $post,
			'headers' => array(
				'Accept'        => 'application/json',
				'Authorization' => 'Bearer ' . $api_token,
				'x-wcd-domain'  => self::get_domain_from_site_url(),
				'x-wcd-wp-id'   => get_current_user_id(),
				'x-wcd-plugin'  => 'webchangedetector-official/' . WEBCHANGEDETECTOR_VERSION,
			),
		);

		self::error_log( 'API V1 request: ' . $url . ' | Args: ' . wp_json_encode( $args ) );
		if ( $is_web ) {
			$response = wp_remote_post( $url_web, $args );
		} else {
			$response = wp_remote_post( $url, $args );
		}

		$body          = wp_remote_retrieve_body( $response );
		$response_code = (int) wp_remote_retrieve_response_code( $response );

		$decoded_body = json_decode( $body, (bool) JSON_OBJECT_AS_ARRAY );

		// `message` is part of the Laravel Stacktrace.
		if ( WCD_HTTP_BAD_REQUEST === $response_code &&
			is_array( $decoded_body ) &&
			array_key_exists( 'message', $decoded_body ) &&
			'plugin_update_required' === $decoded_body['message'] ) {
			return 'update plugin';
		}

		if ( WCD_HTTP_INTERNAL_SERVER_ERROR === $response_code && 'account_details' === $action ) {
			return 'activate account';
		}

		if ( WCD_HTTP_UNAUTHORIZED === $response_code ) {
			return 'unauthorized';
		}

		// if parsing JSON into $decoded_body was without error.
		if ( JSON_ERROR_NONE === json_last_error() ) {
			return $decoded_body;
		}

		return $body;
	}

	/**
	 * Debug logging for dev
	 *
	 * @param string $log The log message.
	 */
	public static function error_log( $log ) {
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG === true && defined( 'WCD_DEV' ) && WCD_DEV ) {
			// phpcs:disable WordPress.PHP.DevelopmentFunctions

			error_log( $log );
			// phpcs:enable
		}
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

// Option secret for domain verification.
if ( ! defined( 'WCD_VERIFY_SECRET' ) ) {
	define( 'WCD_VERIFY_SECRET', 'webchangedetector_verify_secret' );
}

// Option / UserMeta keys.
if ( ! defined( 'WCD_WP_OPTION_KEY_API_TOKEN' ) ) {
	define( 'WCD_WP_OPTION_KEY_API_TOKEN', 'webchangedetector_api_token' );
}

// Account email address.
if ( ! defined( 'WCD_WP_OPTION_KEY_ACCOUNT_EMAIL' ) ) {
	define( 'WCD_WP_OPTION_KEY_ACCOUNT_EMAIL', 'webchangedetector_account_email' );
}

if ( ! defined( 'WCD_WP_OPTION_KEY_UPGRADE_URL' ) ) {
	define( 'WCD_WP_OPTION_KEY_UPGRADE_URL', 'wcd_upgrade_url' );
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
if ( ! defined( 'WCD_POLYLANG_PLUGIN_FILE' ) ) {
	define( 'WCD_POLYLANG_PLUGIN_FILE', 'polylang/polylang.php' );
}

if ( ! defined( 'WCD_WPML_PLUGIN_FILE' ) ) {
	define( 'WCD_WPML_PLUGIN_FILE', 'sitepress-multilingual-cms/sitepress.php' );
}



