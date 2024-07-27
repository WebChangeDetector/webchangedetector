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

use JetBrains\PhpStorm\NoReturn;

/** WCD Admin Class
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
		update_option( 'webchangedetector_verify_secret', $validation_string, false );

		$args = array_merge(
			array(
				'action'            => 'add_free_account',
				'ip'                => isset( $_SERVER['SERVER_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['SERVER_ADDR'] ) ) : '',
				'domain'            => isset( $_SERVER['SERVER_NAME'] ) ? sanitize_text_field( wp_unslash( $_SERVER['SERVER_NAME'] ) ) : '',
				'validation_string' => $validation_string,
				'cms'               => 'wp',
			),
			$postdata
		);
		return $this->api_v1( $args, true );
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

	/** Sync Post if permalink changed. Currently deactivated.
	 *
	 * @param int    $post_id The post id.
	 * @param object $post The post.
	 * @param bool   $update if post was updated.
	 *
	 * @return array|false|mixed|string
	 */
	public function sync_post_after_save( $post_id, $post, $update ) {
		// Only sync posts and pages @TODO make setting to sync other posttypes.
		if ( ! empty( $post->post_type ) && ! in_array( $post->post_type, array( 'page', 'post' ), true ) ) {
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

	/** Get the account details.
	 *
	 * @return array|mixed|string
	 */
	public function account_details() {
		static $account_details;
		if ( $account_details && 'unauthorized' !== $account_details && 'activate account' !== $account_details ) {
			return $account_details;
		}

		$args            = array(
			'action' => 'account_details',
		);
		$account_details = $this->api_v1( $args );
		$upgrade_url     = $this->billing_url() . '?secret=' . $account_details['magic_login_secret'];
		update_option( 'wcd_upgrade_url', $upgrade_url, false );
		return $account_details;
	}

	/** Ajax get processing queue
	 *
	 * @return void
	 */
	public function ajax_get_processing_queue() {
		echo esc_html( $this->get_processing_queue() );
		die();
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

		if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'ajax-nonce' ) ) {
			echo 'Nonce verify failed';
			die( 'Busted!' );
		}

		echo esc_html( $this->update_comparison_status( sanitize_key( wp_unslash( $_POST['id'] ) ), sanitize_text_field( wp_unslash( $_POST['status'] ) ) ) );
		die();
	}

	/** Get processing queue.
	 *
	 * @return array|string
	 */
	public function get_processing_queue() {
		return $this->api_v1( array( 'action' => 'get_not_closed_queue' ) );
	}

	/** Get monitoring settings.
	 *
	 * @param int $group_id The monitoring group id.
	 *
	 * @return array|string
	 */
	public function get_monitoring_settings( $group_id ) {
		// Deprecated.
		$args = array(
			'action'   => 'get_monitoring_settings',
			'group_id' => $group_id,
		);

		return $this->api_v1( $args );
	}

	/** Update monitoring group settings.
	 *
	 * @param array $postdata The postdata.
	 * @param int   $monitoring_group_id The monitoring group id.
	 *
	 * @return array|string
	 */
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
			'name'          => ! isset( $postdata['group_name_auto'] ) ? $monitoring_settings['name'] : sanitize_text_field( $postdata['group_name_auto'] ),
			'css'           => ! isset( $postdata['css'] ) ? $monitoring_settings['css'] : sanitize_textarea_field( $postdata['css'] ),
			'threshold'     => ! isset( $postdata['threshold'] ) ? $monitoring_settings['threshold'] : sanitize_text_field( $postdata['threshold'] ),
		);
		return $this->api_v1( $args );
	}

	/** Update group settings
	 *
	 * @param array $postdata The postdata.
	 * @param int   $group_id The group id.
	 *
	 * @return array|string
	 */
	public function update_settings( $postdata, $group_id ) {

		// Saving auto update settings.
		self::error_log( 'Saving Manual Checks settings: ' . wp_json_encode( $postdata ) );
		$auto_update_settings = array();
		foreach ( $postdata as $key => $value ) {
			if ( 0 === strpos( $key, 'auto_update_checks_' ) ) {
				$auto_update_settings[ $key ] = $value;
			}
		}

		update_option( 'wcd_auto_update_settings', $auto_update_settings );
		do_action( 'wcd_save_update_group_settings', $postdata );

		// Update group settings in api.
		$args = array(
			'action'    => 'update_group',
			'group_id'  => $group_id,
			'name'      => $postdata['group_name'],
			'css'       => sanitize_textarea_field( $postdata['css'] ), // there is no css sanitation.
			'threshold' => sanitize_text_field( $postdata['threshold'] ),
		);

		return $this->api_v1( $args );
	}

	/** Get the upgrade url
	 *
	 * @return false|mixed|string|null
	 */
	public function get_upgrade_url() {
		$upgrade_url = get_option( 'wcd_upgrade_url' );
		if ( ! $upgrade_url ) {
			$account_details = $this->account_details();
			if ( ! is_array( $account_details ) ) {
				return false;
			}
			$upgrade_url = $this->billing_url() . '?secret=' . $account_details['magic_login_secret'];
			update_option( 'wcd_upgrade_url', $upgrade_url );
		}
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
		if ( 'trash' === $icon ) {
			$output = '<span class="group_icon ' . $css_class . ' dashicons dashicons-trash"></span>';
		}
		if ( 'check' === $icon ) {
			$output = '<span class="group_icon ' . $css_class . ' dashicons dashicons-yes-alt"></span>';
		}
		if ( 'fail' === $icon ) {
			$output = '<span class="group_icon ' . $css_class . ' dashicons dashicons-dismiss"></span>';
		}
		if ( 'upgrade' === $icon ) {
			$output = '<span class="group_icon ' . $css_class . ' dashicons dashicons-cart"></span>';
		}

		echo wp_kses( $output, array( 'span' => array( 'class' => array() ) ) );
	}

	/** Get comparisons from api.
	 *
	 * @param array  $group_ids The group ids.
	 * @param int    $limit_days Comparisons of the last days.
	 * @param string $group_type The group type.
	 * @param bool   $difference_only Only with differences.
	 * @param int    $limit_compares Limit number of comparisons.
	 * @param int    $batch_id A specific batch.
	 *
	 * @return array
	 */
	public function get_compares( $group_ids, $limit_days = null, $group_type = null, $difference_only = null, $limit_compares = null, $batch_id = null ) {
		$args     = array(
			'action'          => 'get_compares_by_group_ids',
			'limit_days'      => $limit_days,
			'group_type'      => $group_type,
			'difference_only' => $difference_only,
			'limit_compares'  => $limit_compares,
			'group_ids'       => wp_json_encode( array( $group_ids ) ),
			'batch_id'        => $batch_id,
		);
		$compares = $this->api_v1( $args );

		$return = array();
		if ( ! array_key_exists( 0, $compares ) ) {
			return $return;
		}

		foreach ( array_filter(
			$compares,
			function ( $compare ) {
				// Make sure to only show urls from the website. Shouldn't come from the API anyway.
				$server_name = isset( $_SERVER['SERVER_NAME'] ) ? sanitize_text_field( wp_unslash( $_SERVER['SERVER_NAME'] ) ) : '';
				return strpos( $compare['screenshot1']['url'], $server_name ) !== false;
			}
		) as $compare ) {
			$return[] = $compare;
		}
		return $return;
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
			default:
				return 'new';
		}
	}

	/** Comparison overview.
	 *
	 * @param array $compares The comparisons.
	 * @param bool  $latest_batch If only latest batch is viewed.
	 *
	 * @return void
	 */
	public function compare_view( $compares, $latest_batch = false ) {
		if ( empty( $compares ) ) { ?>
			<table class="toggle" style="width: 100%">
				<tr>
					<th style="width: 120px;">Status</th>
					<th style="width: auto">URL</th>
					<th style="width: 150px">Compared Screenshots</th>
					<th style="width: 50px">Difference</th>
					<th>Show</th>
				</tr>
				<tr>
					<td colspan="4" style="text-align: center">
						<strong>There are no change detections yet.</strong
					</td>
				</tr>
			</table>
			<?php
			return;
		}

		$all_tokens        = array();
		$compares_by_batch = array();

		foreach ( $compares as $compare ) {

			$all_tokens[] = $compare['token'];

			// Sort comparisons by batches.
			$compares_by_batch[ $compare['screenshot2']['queue']['batch_id'] ][] = $compare;
			if ( 'ok' !== $compare['status'] ) {
				$compares_by_batch[ $compare['screenshot2']['queue']['batch_id'] ]['needs_attention'] = true;
			}
		}

		$auto_update_batches = get_option( 'wcd_comparison_batches' );
		$latest_batch_id     = false;
		foreach ( $compares_by_batch as $batch_id => $compares ) {
			?>
			<div class="accordion accordion-batch">
				<div class="mm_accordion_title">
					<h3>
						<div>
							<div class="accordion-batch-title-tile accordion-batch-title-tile-status">
							<?php
							if ( array_key_exists( 'needs_attention', $compares_by_batch[ $batch_id ] ) ) {
								$this->get_device_icon( 'fail', 'batch_needs_attention' );
								echo '<small>Needs Attention</small>';
							} else {
								$this->get_device_icon( 'check', 'batch_is_ok' );
								echo '<small>All Good</small>';
							}
							?>
							</div>
							<div class="accordion-batch-title-tile">
							<?php

							if ( $compares[0]['screenshot2']['queue']['monitoring'] ) {
								echo 'Monitoring Checks';
							} elseif ( is_array( $auto_update_batches ) && in_array( $compares[0]['uuid'], $auto_update_batches, true ) ) {
								echo 'Auto Update Checks';
							} else {
								echo 'Manual Checks';
							}
							?>
							<br>
							<small>
								<?php echo esc_html( human_time_diff( gmdate( 'U' ), gmdate( 'U', strtotime( $compares[0]['screenshot1']['queue']['created_at'] ) ) ) ); ?> ago
							</small>
							</div>
							<div class="clear"></div>
						</div>
					</h3>
					<div class="mm_accordion_content">
						<table class="toggle" style="width: 100%">
							<tr>
								<th style="width: 120px;">Status</th>
								<th style="width: auto">URL</th>
								<th style="width: 150px">Compared Screenshots</th>
								<th style="width: 50px">Difference</th>
								<th>Show</th>
							</tr>

			<?php
			foreach ( $compares as $compare ) {
				if ( $latest_batch && $compare['screenshot2']['queue']['batch_id'] !== $latest_batch_id ) {
					continue;
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
									data-id="<?php echo esc_html( $compare['uuid'] ); ?>"
									data-status="ok"
									data-nonce="<?php echo esc_html( $nonce ); ?>"
									value="ok"
									class=" ajax_update_comparison_status comparison_status comparison_status_ok"
									onclick="return false;">Ok</button>
							<button name="status"
									data-id="<?php echo esc_html( $compare['uuid'] ); ?>"
									data-status="to_fix"
									data-nonce="<?php echo esc_html( $nonce ); ?>"
									value="to_fix"
									class=" ajax_update_comparison_status comparison_status comparison_status_to_fix"
									onclick="return false;">To Fix</button>
							<button name="status"
									data-id="<?php echo esc_html( $compare['uuid'] ); ?>"
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
					if ( ! empty( $compare['screenshot1']['queue']['url']['html_title'] ) ) {
						echo esc_html( $compare['screenshot1']['queue']['url']['html_title'] ) . '<br>';
					}
					?>
					</strong>
					<?php
					$this->get_device_icon( $compare['screenshot1']['device'] );
					echo esc_url( $compare['screenshot1']['url'] );
					echo '<br>';
					if ( 'auto' === $compare['screenshot2']['sc_type'] ) {
						$this->get_device_icon( 'auto-group' );
						echo 'Monitoring';
					} else {
						$this->get_device_icon( 'update-group' );
						echo 'Manual Checks';
					}
					?>
				</td>
				<td>
					<div class="local-time" data-date="<?php echo esc_html( $compare['image1_timestamp'] ); ?>"></div>
					<div class="local-time" data-date="<?php echo esc_html( $compare['image2_timestamp'] ); ?>"></div>
				</td>
				<td class="<?php echo esc_html( $class ); ?> diff-tile"
					data-diff_percent="<?php echo esc_html( $compare['difference_percent'] ); ?>">
					<?php echo esc_html( $compare['difference_percent'] ); ?>%
				</td>
				<td>
					<form action="?page=webchangedetector-show-detection" method="post">
						<input type="hidden" name="token" value="<?php echo esc_html( $compare['token'] ); ?>">
						<input type="hidden" name="all_tokens" value='<?php echo wp_json_encode( $all_tokens ); ?>'>
						<input type="submit" value="Show" class="button">
					</form>
				</td>
			</tr>
				<?php
				$latest_batch_id = $batch_id;
			}
			?>
						</table>
					</div>
				</div>
			</div>
			<?php
		}
	}

	/** View of comparison overview.
	 *
	 * @param array $compares the compares.
	 * @return void
	 */
	public function compare_view_v2( $compares ) {
		if ( empty( $compares ) ) {
			?>
			<table class="toggle" style="width: 100%">
				<tr>
					<th style="width: 120px;">Status</th>
					<th style="width: auto">URL</th>
					<th style="width: 150px">Compared Screenshots</th>
					<th style="width: 50px">Difference</th>
					<th>Show</th>
				</tr>
				<tr>
					<td colspan="4" style="text-align: center">
						<strong>There are no change detections yet.</strong
					</td>
				</tr>
			</table>
			<?php
			return;
		}

		$all_tokens          = array();
		$compares_in_batches = array();

		foreach ( $compares['data'] as $key => $compare ) {

			$all_tokens[] = $compare['token'];

			// Sort comparisons by batches.
			$compares_in_batches[ $compare['batch'] ][] = $compare;
			if ( 'ok' !== $compare['status'] ) {
				$compares_in_batches[ $compare['batch'] ]['needs_attention'] = true;
			}
		}

		$auto_update_batches = get_option( 'wcd_comparison_batches' );
		foreach ( $compares_in_batches as $batch_id => $compares_in_batch ) {
			?>
			<div class="accordion accordion-batch">
				<div class="mm_accordion_title">
					<h3>
						<div>
							<div class="accordion-batch-title-tile accordion-batch-title-tile-status">
								<?php
								if ( array_key_exists( 'needs_attention', $compares_in_batches[ $batch_id ] ) ) {
									$this->get_device_icon( 'fail', 'batch_needs_attention' );
									echo '<small>Needs Attention</small>';
								} else {
									$this->get_device_icon( 'check', 'batch_is_ok' );
									echo '<small>All Good</small>';
								}
								?>
							</div>
							<div class="accordion-batch-title-tile">
								<?php
								if ( $compares_in_batches[0]['group'] === $this->monitoring_group_uuid ) {
									echo 'Monitoring Checks';
								} elseif ( is_array( $auto_update_batches ) && in_array( $batch_id, $auto_update_batches, true ) ) {
									echo 'Auto Update Checks';
								} else {
									echo 'Manual Checks';
								}
								?>
								<br>
								<small>
									<?php echo esc_html( human_time_diff( gmdate( 'U' ), gmdate( 'U', strtotime( $compares_in_batch[0]['created_at'] ) ) ) ); ?> ago
								</small>
							</div>
							<div class="clear"></div>
						</div>
					</h3>
					<div class="mm_accordion_content">
						<table class="toggle" style="width: 100%">
							<tr>
								<th style="width: 120px;">Status</th>
								<th style="width: auto">URL</th>
								<th style="width: 150px">Compared Screenshots</th>
								<th style="width: 50px">Difference</th>
								<th>Show</th>
							</tr>

							<?php

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
														class=" ajax_update_comparison_status comparison_status comparison_status_ok"
														onclick="return false;">Ok</button>
												<button name="status"
														data-id="<?php echo esc_html( $compare['id'] ); ?>"
														data-status="to_fix"
														data-nonce="<?php echo esc_html( $nonce ); ?>"
														value="to_fix"
														class=" ajax_update_comparison_status comparison_status comparison_status_to_fix"
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

										$this->get_device_icon( $compare['device'] );
										echo esc_url( $compare['url'] );
										echo '<br>';
										if ( 'auto' === $compare['sc_type'] ) {
											$this->get_device_icon( 'auto-group' );
											echo 'Monitoring';
										} else {
											$this->get_device_icon( 'update-group' );
											echo 'Manual Checks';
										}
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
										<form action="?page=webchangedetector-show-detection" method="post">
											<input type="hidden" name="token" value="<?php echo esc_html( $compare['token'] ); ?>">
											<input type="hidden" name="all_tokens" value='<?php echo wp_json_encode( $all_tokens ); ?>'>
											<input type="submit" value="Show" class="button">
										</form>
									</td>
								</tr>
								<?php
								$latest_batch_id = $batch_id;
							}
							?>
						</table>
					</div>
				</div>
			</div>
			<?php
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

		if ( ! $token && ! empty( $_GET['token'] ) ) {
			$token = sanitize_text_field( wp_unslash( $_GET['token'] ) );
		}
		if ( isset( $token ) ) {
			$args    = array(
				'action' => 'get_comparison_by_token',
				'token'  => $token,
			);
			$compare = $this->api_v1( $args ); // used in template.

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
					<input type="hidden" name="all_tokens" value='<?php echo wp_json_encode( $all_tokens ); ?>'>
					<button class="button" type="submit" name="token"
							value="<?php echo esc_html( $before_token ) ?? null; ?>" <?php echo ! $before_token ? 'disabled' : ''; ?>> < Previous </button>
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

	/** Get Queue
	 *
	 * @return array|string
	 */
	public function get_queue() {
		$args = array(
			'action' => 'get_queue',
			'status' => wp_json_encode( array( 'open', 'done', 'processing', 'failed' ) ),
			'limit'  => isset( $_GET['limit'] ) ? sanitize_key( (int) $_GET['limit'] ) : $this::LIMIT_QUEUE_ROWS,
			'offset' => isset( $_GET['offset'] ) ? sanitize_key( (int) $_GET['offset'] ) : 0,
		);
		return $this->api_v1( $args );
	}

	/** Add Post type to website
	 *
	 * @param array $postdata The postdata.
	 *
	 * @return void
	 */
	public function add_post_type( $postdata ) {
		$post_type                               = json_decode( stripslashes( $postdata['post_type'] ), true );
		$existing_post_types                     = $this->website_details['sync_url_types'];
		$this->website_details['sync_url_types'] = array_merge( $post_type, $existing_post_types );

		$this->api_v1( array_merge( array( 'action' => 'save_user_website' ), $this->website_details ) );
	}

	/** Get posts
	 *
	 * @param string $posttype The posttype.
	 *
	 * @return int[]|WP_Post[]
	 */
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

	/** Sync posts with api.
	 *
	 * @return array|false|mixed|string
	 */
	public function sync_posts() {

		// Return synced_posts from transient if available.
		$synced_posts = get_transient( 'wcd_synced_posts' );
		if ( $synced_posts ) {
			return $synced_posts;
		}

		$array     = array(); // init.
		$url_types = array();

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
				'posts'               => wp_json_encode( $array ),
			);

			$synced_posts = $this->api_v1( $args );
			set_transient( 'wcd_synced_posts', $synced_posts, 3600 );
			return $synced_posts;
		}
		return false;
	}

	/** Update urls.
	 *
	 * @param int   $group_id The group id.
	 * @param array $active_posts All active posts.
	 *
	 * @return array|string
	 */
	public function update_urls( $group_id, $active_posts = array() ) {
		$args = array(
			'action'   => 'update_urls',
			'group_id' => $group_id,
			'posts'    => wp_json_encode( $active_posts ),
		);
		return $this->api_v1( $args );
	}

	/** Take screenshot.
	 *
	 * @param int    $group_id The group id.
	 * @param string $sc_type Is 'pre' or 'post'.
	 *
	 * @return array|string
	 */
	public function take_screenshot( $group_id, $sc_type ) {
		$args = array(
			'action'   => 'take_screenshots',
			'sc_type'  => $sc_type,
			'group_id' => $group_id,
		);
		return $this->api_v1( $args );
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
			<form action="<?php echo esc_url( admin_url() . '/admin.php?page=webchangedetector' ); ?>" method="post"
				onsubmit="return confirm('Are sure you want to reset the API token?');">
				<input type="hidden" name="wcd_action" value="reset_api_token">
				<?php wp_nonce_field( 'reset_api_token' ); ?>
				<hr>
				<h2> Account</h2>
				<p>
					Your email address: <strong><?php echo esc_html( $this->account_details()['email'] ); ?></strong><br>
					Your API Token: <strong><?php echo esc_html( $api_token ); ?></strong>
				</p>
				<p>
					With resetting the API Token, auto detections still continue and your settings will
					be still available when you use the same api token with this website again.
				</p>
				<input type="submit" value="Reset API Token" class="button button-delete"><br>
				<hr>
				<h2>Delete Account</h2>
				<p>To delete your account completely, please login to your account at
					<a href="https://www.webchangedetector.com" target="_blank">webchangedetector.com</a>.
				</p>
			</form>
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

	/** Get urls of a group.
	 *
	 * @param int $group_id The group id.
	 *
	 * @return array|mixed|string
	 */
	public function get_urls_of_group( $group_id ) {
		$args = array(
			'action'   => 'get_user_groups_and_urls',
			'cms'      => 'wordpress',
			'group_id' => $group_id,
		);

		// We only get one group as we send the group_id.

		$response = $this->api_v1( $args );

		if ( array_key_exists( 0, $response ) ) {
			return $response[0];
		}

		return $response;
	}

	/** Set default sync types.
	 *
	 * @return void
	 */
	public function set_default_sync_types() {
		if ( empty( $this->website_details['sync_url_types'] ) ) {
			$this->website_details['sync_url_types'] = wp_json_encode(
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
			$this->website_details                   = $this->api_v1( array_merge( array( 'action' => 'save_user_website' ), $this->website_details ) );
		}
	}

	/** Group url view.
	 *
	 * @param array $groups_and_urls The urls of a group.
	 * @param bool  $monitoring_group Is it a monitoring group.
	 *
	 * @return void
	 */
	public function get_url_settings( $groups_and_urls, $monitoring_group = false ) {
		// Sync urls - post_types defined in function @TODO make settings for post_types to sync.
		$wcd_posts = $this->sync_posts();

		// Select URLS.
		$tab            = 'update-settings'; // init.
		$detection_type = 'update';
		if ( $monitoring_group ) {
			$tab            = 'auto-settings';
			$detection_type = 'auto';
		}
		?>
		<div class="wcd-select-urls-container">
			<form class="wcd-frm-settings" action="<?php echo esc_url( admin_url() . 'admin.php?page=webchangedetector-' . $tab ); ?>" method="post">
				<input type="hidden" name="wcd_action" value="save_group_settings">
				<?php
				wp_nonce_field( 'save_group_settings' );

				if ( ! $monitoring_group ) {
					$auto_update_settings       = get_option( 'wcd_auto_update_settings' );
					$auto_update_checks_enabled = true;
					if ( ! $auto_update_settings || ! array_key_exists( 'auto_update_checks_enabled', $auto_update_settings ) ) {
						$auto_update_checks_enabled = false;
					}
					?>
				<input type="hidden" name="step" value="pre-update">
				<h2>Settings</h2>
				<div class="accordion">
					<div class="mm_accordion_title">
						<h3>
							Manual Checks Settings<br>
							<small>
								Auto update checks:
								<strong>
									<span style="color: <?php echo $auto_update_checks_enabled ? 'green' : 'red'; ?>">
										<?php echo $auto_update_checks_enabled ? 'On' : 'Off'; ?>
									</span>
								</strong>
								| Threshold: <strong><?php echo esc_html( $groups_and_urls['threshold'] ); ?> %</strong>
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
										<?php echo esc_html( $groups_and_urls['interval_in_h'] ); ?>
										<?php echo 1 === $groups_and_urls['interval_in_h'] ? ' hour' : ' hours'; ?>
									</strong>

									| Threshold: <strong><?php echo esc_html( $groups_and_urls['threshold'] ); ?> %</strong>
									| CSS injection: <strong><?php echo $groups_and_urls['css'] ? 'yes' : 'no'; ?></strong>
									<br>
									Notifications to:
									<strong>
										<?php echo ! empty( $groups_and_urls['alert_emails'] ) ? esc_html( implode( ', ', $groups_and_urls['alert_emails'] ) ) : 'no email address set'; ?>
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

				// Check if current WP wp_post ID is in wcd_posts and get the url_id.
				foreach ( $wcd_posts as $wcd_post ) {
					if ( isset( $wcd_post['cms_resource_id'] )
						&& 0 === $wcd_post['cms_resource_id']
						&& 'frontpage' === $wcd_post['url_type'] ) {
						$url_id = $wcd_post['url_id'];
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
									<?php $this->get_device_icon( 'desktop' ); ?>
									<strong><span id="selected-desktop-Frontpage"></span></strong> |
									<?php $this->get_device_icon( 'mobile' ); ?>
									<strong><span id="selected-mobile-Frontpage"></span></strong>
								</div>
								<div class="clear"></div>

							</span>

						</h3>
						<div class="mm_accordion_content">
							<div class="group_urls_container">
								<table>
									<tr>
										<th><?php $this->get_device_icon( 'desktop' ); ?></th>
										<th><?php $this->get_device_icon( 'mobile' ); ?></th>
										<th width="100%">URL</th>
									</tr>
									<tr class="live-filter-row even-tr-white post_id_<?php echo esc_html( $groups_and_urls['id'] ); ?>" id="<?php echo esc_url( $url_id ); ?>" >
										<input type="hidden" name="post_id-<?php echo esc_html( $url_id ); ?>" value="<?php echo esc_html( $url_id ); ?>">
										<input type="hidden" name="url_id-<?php echo esc_html( $url_id ); ?>" value="<?php echo esc_html( $url_id ); ?>">
										<input type="hidden" name="active-<?php echo esc_html( $url_id ); ?>" value="1">

										<td class="checkbox-desktop-Frontpage" style="text-align: center;">
											<input type="hidden" value="0" name="desktop-<?php echo esc_html( $url_id ); ?>">
											<input type="checkbox" name="desktop-<?php echo esc_html( $url_id ); ?>" value="1" <?php echo esc_html( $checked['desktop'] ); ?>
													id="desktop-<?php echo esc_html( $url_id ); ?>" onclick="mmMarkRows('<?php echo esc_html( $url_id ); ?>')" ></td>

										<td class="checkbox-mobile-Frontpage" style="text-align: center;">
											<input type="hidden" value="0" name="mobile-<?php echo esc_html( $url_id ); ?>">
											<input type="checkbox" name="mobile-<?php echo esc_html( $url_id ); ?>" value="1" <?php echo esc_html( $checked['mobile'] ); ?>
											id="mobile-<?php echo esc_html( $url_id ); ?>" onclick="mmMarkRows('<?php echo esc_html( $url_id ); ?>')" ></td>

										<td style="text-align: left;"><strong><?php echo esc_html( get_option( 'blogname' ) ) . ' - ' . esc_html( get_option( 'blogdescription' ) ); ?></strong><br>
										<a href="<?php echo esc_html( get_option( 'home' ) ); ?>" target="_blank"><?php echo esc_html( get_option( 'home' ) ); ?></a></td>
									</tr>
								</table>
							</div>
						</div>
					</div>
				</div>

				<script> mmMarkRows('<?php echo esc_html( $url_id ); ?>'); </script>
				<?php
				echo '<div class="selected-urls" style="display: none;" 
                            data-amount_selected="1" 
                            data-amount_selected_desktop="' . esc_html( $selected_desktop ) . '"
                            data-amount_selected_mobile="' . esc_html( $selected_mobile ) . '"
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
							$wp_posts = $this->get_posts( $url_category->name );
							break;

						case 'taxonomies':
							$wp_posts = $this->get_terms( $url_category->name );
							break;

						default:
							$wp_posts = false;
					}

					if ( is_iterable( $wp_posts ) ) {
						?>
						<div class="accordion">
						<div class="mm_accordion_title">
						<h3>
							<span class="accordion-title">
								<?php echo esc_html( ucfirst( $url_category->label ) ); ?>
								<div class="accordion-post-types-url-amount">
									<?php $this->get_device_icon( 'desktop' ); ?>
									<strong><span id="selected-desktop-<?php echo esc_html( $url_category->label ); ?>"></span></strong> |

									<?php $this->get_device_icon( 'mobile' ); ?>
									<strong><span id="selected-mobile-<?php echo esc_html( $url_category->label ); ?>"></span></strong>
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
							<th><?php $this->get_device_icon( 'desktop' ); ?></th>
							<th><?php $this->get_device_icon( 'mobile' ); ?></th>
							<th width="100%">URL</th>
						</tr>
						<?php
						// Select all from same device.
						echo '<tr class="live-filter-row even-tr-white" style="background: none; text-align: center">
                                    <td><input type="checkbox" id="select-desktop-' . esc_html( $url_category->label ) . '" onclick="mmToggle( this, \'' . esc_html( $url_category->label ) . '\', \'desktop\', \'' . esc_html( $groups_and_urls['id'] ) . '\' )" /></td>
                                    <td><input type="checkbox" id="select-mobile-' . esc_html( $url_category->label ) . '" onclick="mmToggle( this, \'' . esc_html( $url_category->label ) . '\', \'mobile\', \'' . esc_html( $groups_and_urls['id'] ) . '\' )" /></td>
                                    <td></td>
                                </tr>';
						$amount_active_posts = 0;
						$selected_mobile     = 0;
						$selected_desktop    = 0;

						if ( is_iterable( $wp_posts ) && count( $wp_posts ) > 0 ) {

							// Re-order posts to have active ones on top.
							$inactive_posts = array();
							$active_posts   = array();
							foreach ( $wp_posts as $wp_post ) {
								foreach ( $groups_and_urls['urls'] as $group_url ) {
									if ( ! empty( $group_url['cms_resource_id'] ) &&
										$group_url['url_type'] === $url_type &&
										$wp_post->ID === $group_url['cms_resource_id']
									) {
										if ( $group_url['pivot']['desktop'] || $group_url['pivot']['mobile'] ) {
											$active_posts[] = $wp_post;
										} else {
											$inactive_posts[] = $wp_post;
										}
									}
								}
							}

							// This way the wp_posts should be sorted the right way.
							$wp_posts = array_merge( $active_posts, $inactive_posts );

							foreach ( $wp_posts as $wp_post ) {
								switch ( $url_type ) {
									case 'types':
										$url        = get_permalink( $wp_post );
										$post_title = $wp_post->post_title;
										$post_id    = $wp_post->ID;
										break;

									case 'taxonomies':
										$url        = get_term_link( $wp_post );
										$post_title = $wp_post->name;
										$post_id    = $wp_post->term_id;
										break;

									default:
										$url        = false;
										$post_title = false;
										$post_id    = false;
								}

								$url_id = false;

								// Check if current WP wp_post ID is in wcd_posts and get the url_id.
								foreach ( $wcd_posts as $wcd_post ) {
									if ( ! empty( $wcd_post['cms_resource_id'] )
										&& $wcd_post['cms_resource_id'] === $post_id
										&& $wcd_post['url_type'] === $url_type ) {
										$url_id = $wcd_post['url_id'];
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

								echo '<tr class="live-filter-row even-tr-white post_id_' . esc_html( $groups_and_urls['id'] ) . '" id="' . esc_html( $url_id ) . '" >';
								echo '<input type="hidden" name="post_id-' . esc_html( $url_id ) . '" value="' . esc_html( $post_id ) . '">';
								echo '<input type="hidden" name="url_id-' . esc_html( $url_id ) . '" value="' . esc_html( $url_id ) . '">';
								echo '<input type="hidden" name="active-' . esc_html( $url_id ) . ' value="1">';

								echo '<td class="checkbox-desktop-' . esc_html( $url_category->label ) . '" style="text-align: center;">
                                            <input type="hidden" value="0" name="desktop-' . esc_html( $url_id ) . '">
                                            <input type="checkbox" name="desktop-' . esc_html( $url_id ) . '" value="1" ' . esc_html( $checked['desktop'] ) . '
                                            id="desktop-' . esc_html( $url_id ) . '" onclick="mmMarkRows(\'' . esc_html( $url_id ) . '\')" ></td>';

								echo '<td class="checkbox-mobile-' . esc_html( $url_category->label ) . '" style="text-align: center;">
                                            <input type="hidden" value="0" name="mobile-' . esc_html( $url_id ) . '">
                                            <input type="checkbox" name="mobile-' . esc_html( $url_id ) . '" value="1" ' . esc_html( $checked['mobile'] ) . '
                                            id="mobile-' . esc_html( $url_id ) . '" onclick="mmMarkRows(\'' . esc_html( $url_id ) . '\')" ></td>';

								echo '<td style="text-align: left;"><strong>' . esc_html( $post_title ) . '</strong><br>';
								echo '<a href="' . esc_html( $url ) . '" target="_blank">' . esc_html( $url ) . '</a></td>';
								echo '</tr>';

								echo '<script> mmMarkRows(\'' . esc_html( $url_id ) . '\'); </script>';
							}
						}

						echo '</table>';
						if ( ! count( $wp_posts ) ) {
							?>
							<div style="text-align: center; font-weight: 700; padding: 20px 0;">
								No Posts in this post type
							</div>
							<?php
						}

						echo '<div class="selected-urls" style="display: none;" 
                                    data-amount_selected="' . esc_html( $amount_active_posts ) . '" 
                                    data-amount_selected_desktop="' . esc_html( $selected_desktop ) . '"
                                    data-amount_selected_mobile="' . esc_html( $selected_mobile ) . '"
                                    data-post_type="' . esc_html( $url_category->label ) . '"
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
							name="save_settings"
							value="post_urls"
							onclick="return wcdValidateFormAutoSettings()">
						Save
					</button>
					<button class="button"
							type="submit"
							name="save_settings"
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
							name="save_settings"
							value="save_update_settings_and_continue" >
							Save and continue >
						</button>
					<?php } ?>
					<button
							class="button"
							type="submit"
							name="save_settings"
							value="post_urls"
							style="margin-left: 10px;">
						Only save
					</button>
					<button class="button"
							type="submit"
							name="save_settings"
							value="post_urls_update_and_auto"
							style="margin-left: 10px;">
						Save & copy to monitoring
					</button>
				<?php } ?>
			</form>
		</div>
		<?php
	}

	/** Save url settings
	 *
	 * @param array $postdata The postdata.
	 * @param array $website_details The website details.
	 * @param bool  $save_both_groups Save monitoring or manual group or both.
	 *
	 * @return void
	 */
	public function post_urls( $postdata, $website_details, $save_both_groups ) {
		// Get active posts from post data.
		$active_posts   = array();
		$count_selected = 0;
		foreach ( $postdata as $key => $post_id ) {
			if ( strpos( $key, 'url_id' ) === 0 ) {

				// sanitize before.
				$wp_post_id = sanitize_text_field( $postdata[ 'post_id-' . $post_id ] ); // should be numeric.
				if ( ! is_numeric( $wp_post_id ) ) {
					continue; // just skip it.
				}
				$permalink = get_permalink( $wp_post_id ); // should return the whole link.
				$desktop   = array_key_exists( 'desktop-' . $post_id, $postdata ) ? sanitize_text_field( $postdata[ 'desktop-' . $post_id ] ) : 0;
				$mobile    = array_key_exists( 'mobile-' . $post_id, $postdata ) ? sanitize_text_field( $postdata[ 'mobile-' . $post_id ] ) : 0;

				$active_posts[] = array(
					'url_id'  => $post_id,
					'url'     => $permalink,
					'desktop' => $desktop,
					'mobile'  => $mobile,
				);
				if ( isset( $postdata[ 'desktop-' . $post_id ] ) && 1 === $postdata[ 'desktop-' . $post_id ] ) {
					++$count_selected;
				}

				if ( isset( $postdata[ 'mobile-' . $post_id ] ) && 1 === $postdata[ 'mobile-' . $post_id ] ) {
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
                        You selected ' . esc_html( $count_selected ) . ' URLs. The settings were not saved.</p></div>';
		} elseif ( $website_details['enable_limits'] &&
			isset( $monitoring_group_settings ) &&
			$website_details['sc_limit'] < $count_selected * ( WCD_HOURS_IN_DAY / $monitoring_group_settings['interval_in_h'] ) * WCD_DAYS_PER_MONTH &&
			$website_details['auto_detection_group_id'] === $group_id_website_details ) {
			echo '<div class="error notice"><p>The limit for monitorings is ' .
				esc_html( $website_details['sc_limit'] ) . '. per month. You selected ' .
				esc_html( $count_selected * ( WCD_HOURS_IN_DAY / $monitoring_group_settings['interval_in_h'] ) * WCD_DAYS_PER_MONTH ) .
				' change detections. The settings were not saved.
                 </p></div>';
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

	/**
	 * No-account page.
	 *
	 * @param string $api_token The api token.
	 */
	public function get_no_account_page( $api_token = '' ) {

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
							<?php wp_create_nonce( 'create_free_account' ); ?>
							<input type="text" name="name_first" placeholder="First Name" value="<?php echo esc_html( $first_name ); ?>" required>
							<input type="text" name="name_last" placeholder="Last Name" value="<?php echo esc_html( $last_name ); ?>" required>
							<input type="email" name="email" placeholder="Email" value="<?php echo esc_html( $email ); ?>" required>
							<input type="password" name="password" placeholder="Password" required>
							<input type="checkbox" name="marketingoptin" checked style="width: 10px; display: inline-block;"> Send me news about WebChangeDetector

							<input type="submit" class="button-primary" value="Create Free Account">
						</form>
					</div>
				</div>

				<?php $this->get_api_token_form( $api_token ); ?>
			</div>
		</div>
		<?php
	}

	/** Get the website details and set the class vars.
	 *
	 * @return void
	 */
	public function set_website_details() {
		$args = array(
			'action' => 'get_website_details',
			// domain sent at mm_api.
		);
		$this->website_details = $this->api_v1( $args );

		// If we don't have websites details yet, we create them. This happens after account activation.
		if ( empty( $this->website_details ) ) {
			$this->create_website_and_groups();
			$this->website_details = $this->api_v1( $args );
		}
		// Take the first website details or return error string.
		if ( is_array( $this->website_details ) && ! empty( $this->website_details ) ) {
			$this->website_details = $this->website_details[0];

			// Set default sync types if they are empty.
			$this->set_default_sync_types();
			$this->website_details['sync_url_types'] = json_decode( $this->website_details['sync_url_types'], true );
		}

		// Save group uuids. If website_details request fails, we have at least those.
		if ( ! empty( $this->website_details['auto_detection_group']['uuid'] ) &&
			! empty( $this->website_details['manual_detection_group']['uuid'] )
		) {
			$groups = array(
				'auto_detection_group'   => $this->website_details['auto_detection_group']['uuid'],
				'manual_detection_group' => $this->website_details['manual_detection_group']['uuid'],
			);
			update_option( 'wcd_website_groups', $groups );
		}
	}

	/** View of tabs
	 *
	 * @return void
	 */
	public function tabs() {
		$active_tab = 'webchangedetector'; // init.

		if ( isset( $_GET['page'] ) ) {
			// sanitize: lower-case with "-".
			$active_tab = sanitize_text_field( wp_unslash( $_GET['page'] ) );
		}
		?>
		<div class="wrap">
			<h2 class="nav-tab-wrapper">
				<a href="?page=webchangedetector"
					class="nav-tab <?php echo 'webchangedetector' === $active_tab ? 'nav-tab-active' : ''; ?>">
					<?php $this->get_device_icon( 'dashboard' ); ?> Dashboard
				</a>
				<a href="?page=webchangedetector-update-settings"
					class="nav-tab <?php echo 'webchangedetector-update-settings' === $active_tab ? 'nav-tab-active' : ''; ?>">
					<?php $this->get_device_icon( 'update-group' ); ?> Manual Checks
				</a>
				<a href="?page=webchangedetector-auto-settings"
					class="nav-tab <?php echo 'webchangedetector-auto-settings' === $active_tab ? 'nav-tab-active' : ''; ?>">
					<?php $this->get_device_icon( 'auto-group' ); ?> Monitoring
				</a>
				<a href="?page=webchangedetector-change-detections"
					class="nav-tab <?php echo 'webchangedetector-change-detections' === $active_tab ? 'nav-tab-active' : ''; ?>">
					<?php $this->get_device_icon( 'change-detections' ); ?> Change Detections
				</a>
				<a href="?page=webchangedetector-logs"
					class="nav-tab <?php echo 'webchangedetector-logs' === $active_tab ? 'nav-tab-active' : ''; ?>">
					<?php $this->get_device_icon( 'logs' ); ?> Logs
				</a>
				<a href="?page=webchangedetector-settings"
					class="nav-tab <?php echo 'webchangedetector-settings' === $active_tab ? 'nav-tab-active' : ''; ?>">
					<?php $this->get_device_icon( 'settings' ); ?> Settings
				</a>
				<a href="<?php echo esc_url( $this->get_upgrade_url() ); ?>" target="_blank"
					class="nav-tab upgrade">
					<?php $this->get_device_icon( 'upgrade' ); ?> Upgrade Account
				</a>
			</h2>
		</div>

		<?php
	}

	/** Get the dashboard view.
	 *
	 * @param array $client_account Account data.
	 * @param int   $update_group_id Manual checks group id.
	 * @param int   $auto_group_id Monitoring checks group id.
	 *
	 * @return void
	 */
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
							<?php $this->get_device_icon( 'update-group' ); ?>
						</div>
						<div style="float: left; max-width: 350px;">
							<strong>Manual Checks</strong><br>
							Create change detections manually
						</div>
						<div class="clear"></div>
					</a>
					<a class="box" href="?page=webchangedetector-auto-settings">
						<div style="padding-top:10px; font-size: 60px; width: 50px; float: left;">
							<?php $this->get_device_icon( 'auto-group' ); ?>
						</div>
						<div style="float: left; max-width: 350px;">
							<strong>Monitoring</strong><br>
							Create automatic change detections
						</div>
						<div class="clear"></div>
					</a>
					<a class="box" href="?page=webchangedetector-change-detections">
						<div style="padding-top:10px; font-size: 60px; width: 50px; float: left;">
							<?php $this->get_device_icon( 'change-detections' ); ?>
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
								echo number_format( esc_html( $client_account['usage'] / $client_account['sc_limit'] * 100 ), 1 );
							} else {
								echo 0;
							}
							?>
							% credits used
						</strong>
					</h2>
					<hr>
					<p style="margin-top: 20px;"><strong>Used checks:</strong>
						<?php echo esc_html( $client_account['usage'] ); ?> /
						<?php echo esc_html( $client_account['sc_limit'] ); ?>
					</p>

					<p><strong>Active monitoring checks / month:</strong> <?php echo esc_html( $amount_auto_detection ); ?></p>

					<p><strong>Active monitoring checks until renewal:</strong>
						<?php
						echo number_format(
							$amount_auto_detection / WCD_SECONDS_IN_MONTH *
									( gmdate( 'U', strtotime( $client_account['renewal_at'] ) ) - gmdate( 'U' ) ),
							0
						);
						?>
					</p>

					<p><strong>Renewal on:</strong> <?php echo esc_html( gmdate( 'd/m/Y', strtotime( $client_account['renewal_at'] ) ) ); ?></p>
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

	/** Show activate account.
	 *
	 * @param string $error The error.
	 * @return false
	 */
	public function show_activate_account( $error ) {

		if ( 'activate account' === $error ) {
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
				<p>
					The API token is not valid. Please reset the API token and enter a valid one.
				</p>
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
		$post['domain'] = isset( $_SERVER['SERVER_NAME'] ) ? sanitize_text_field( wp_unslash( $_SERVER['SERVER_NAME'] ) ) : '';
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
		if ( defined( WCD_DEV ) && WCD_DEV ) {
			error_log( $log );
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





