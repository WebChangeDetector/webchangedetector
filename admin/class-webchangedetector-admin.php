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

	const LIMIT_QUEUE_ROWS  = 50;
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
			'Manual Checks & Auto Update Checks',
			'Manual Checks & Auto Update Checks',
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
			'Queue',
			'Queue',
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

	/** Get account details.
	 *
	 * @return array|string|bool
	 */
	public function get_account() {
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

		$this->post_urls( $_POST, $this->website_details, false );
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
		$upgrade_url = get_option( 'wcd_upgrade_url' );
		if ( ! $upgrade_url ) {
			$account_details = $this->get_account();

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
			default:
				return 'new';
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
			<table style="width: 100%">
				<tr>
					<td colspan="5" style="text-align: center; background: #fff; height: 50px;">
						<strong>No change detections (yet)</strong
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
										<form action="?page=webchangedetector-show-detection&id=<?php echo esc_html( $compare['id'] ); ?>" method="post">
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
				<form action="?page=webchangedetector-show-detection&id=<?php echo esc_html( $before_token ) ?? null; ?>" method="post" style="display:inline-block;">
					<input type="hidden" name="all_tokens" value='<?php echo wp_json_encode( $all_tokens ); ?>'>
					<button class="button" type="submit" name="token"
							value="<?php echo esc_html( $before_token ) ?? null; ?>" <?php echo ! $before_token ? 'disabled' : ''; ?>> < Previous </button>
				</form>
				<form action="?page=webchangedetector-show-detection&id=<?php echo esc_html( $after_token ) ?? null; ?>" method="post" style="display:inline-block;">
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
		$existing_post_types                     = $this->website_details['sync_url_types'];
		$this->website_details['sync_url_types'] = array_merge( $post_type, $existing_post_types );

		$this->api_v1( array_merge( array( 'action' => 'save_user_website' ), $this->website_details ) );
		$this->sync_posts();
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
		$posts          = array();
		if ( ! $wpml_languages ) {
			$posts = get_posts( $args );
		} else {
			foreach ( $wpml_languages['languages'] as $language ) {
				do_action( 'wpml_switch_language', $language['code'] );
				$posts = array_merge( $posts, get_posts( $args ) );
			}
			do_action( 'wpml_switch_language', $wpml_languages['current_language'] );
		}
		return $posts;
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
		$terms          = array();

		// If we don't have languages, we can return the terms.
		if ( ! $wpml_languages ) {
			$terms = get_terms( $args );

			// With languages, we loop through them and return all of them.
		} else {
			foreach ( $wpml_languages['languages'] as $language ) {
				do_action( 'wpml_switch_language', $language['code'] );
				$terms = array_merge( $terms, get_terms( $args ) );
			}
			do_action( 'wpml_switch_language', $wpml_languages['current_language'] );
		}
		return $terms;
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

	/** Sync posts with api.
	 *
	 * @param bool $force_sync Skip cache and force sync.
	 * @return array|false|mixed|string
	 */
	public function sync_posts( $force_sync = false ) {

		// Return synced_posts from transient if available unless we have forced_sync and we are not in DEV mode.
		if ( ! ( defined( 'WCD_DEV' ) && WCD_DEV ) && false === $force_sync ) {
			$synced_posts = get_transient( 'wcd_synced_posts' );
			if ( $synced_posts ) {
				return $synced_posts;
			}
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
			if ( ! empty( $this->website_details['sync_url_types'] ) ) {
				foreach ( $this->website_details['sync_url_types'] as $sync_url_type ) {
					if ( $post_type->rest_base && $sync_url_type['post_type_slug'] === $post_type->rest_base ) {
						$url_types['types'][ $post_type->rest_base ] = $this->get_posts( $post_type->name );
					}
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
			if ( ! empty( $this->website_details['sync_url_types'] ) ) {
				foreach ( $this->website_details['sync_url_types'] as $sync_url_type ) {
					if ( $sync_url_type['post_type_slug'] === $taxonomy->rest_base ) {
						$url_types['taxonomies'][ $taxonomy->rest_base ] = $this->get_terms( $taxonomy->name );
					}
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

			// If we don't have posts here, we can exit.
			if ( empty( $array ) ) {
				return false;
			}

			// If blog is set as home page.
			if ( ! get_option( 'page_on_front' ) ) {

				// WPML fix.
				if ( function_exists( 'icl_get_languages' ) ) {
					$languages = icl_get_languages( 'skip_missing=0' ); // Get all active languages.

					if ( ! empty( $languages ) ) {
						foreach ( $languages as $lang_code => $lang ) {
							// Store the home URL for each language.
							$array[] = array(
								'url'             => rtrim( self::remove_url_protocol( $lang['url'] ), '/' ),
								'html_title'      => get_option( 'blogname' ) . ' - ' . get_option( 'blogdescription' ),
								'cms_resource_id' => 0,
								'url_type'        => 'frontpage',
								'url_category'    => 'Frontpage',
							);
						}
					}

					// Polylang fix.
				} elseif ( function_exists( 'pll_the_languages' ) ) {

					$translations = pll_the_languages( array( 'raw' => 1 ) );
					foreach ( $translations as $lang_code => $translation ) {
						$array[] = array(
							'url'             => rtrim( self::remove_url_protocol( pll_home_url( $lang_code ) ), '/' ),
							'html_title'      => get_option( 'blogname' ) . ' - ' . get_option( 'blogdescription' ),
							'cms_resource_id' => 0,
							'url_type'        => 'frontpage',
							'url_category'    => 'Frontpage',
						);
					}
				} else {
					$array[] = array(
						'url'             => rtrim( self::remove_url_protocol( get_option( 'home' ) ), '/' ),
						'html_title'      => get_option( 'blogname' ) . ' - ' . get_option( 'blogdescription' ),
						'cms_resource_id' => 0,
						'url_type'        => 'frontpage',
						'url_category'    => 'Frontpage',
					);
				}
			}
		}

		if ( ! empty( $array ) ) {
			$synced_posts = WebChangeDetector_API_V2::sync_urls( $array );
			set_transient( 'wcd_synced_posts', $synced_posts, 3600 );

			return $synced_posts;
		}
		return false;
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

	/** Group url view.
	 *
	 * @param array $group_and_urls The groups and their urls.
	 * @param bool  $monitoring_group Is it a monitoring group.
	 *
	 * @return void
	 */
	public function get_url_settings($monitoring_group = false ) {
		// Sync urls - post_types defined in function @TODO make settings for post_types to sync.

		$wcd_website_urls = $this->sync_posts();

		if ( $monitoring_group ) {
			$group_id = $this->monitoring_group_uuid;
		} else {
			$group_id = $this->manual_group_uuid;
		}

		$filters = array(
			'per_page' => 20,
			'sorted'   => 'selected',
			'page'     => sanitize_key( wp_unslash( $_GET['paged'] ) ) ?? 1,
		);
		if ( ! empty( $_GET['post_type'] ) ) {
			$filters['category'] = sanitize_text_field( wp_unslash( $_GET['post_type'] ) );
		}

		if ( ! empty( $_GET['search'] ) ) {
			$filters['search'] = sanitize_text_field( wp_unslash( $_GET['search'] ) );
		}

		// Get the urls.
		$group_and_urls      = $this->get_group_and_urls( $group_id, $filters );
		$urls = $group_and_urls['urls'];
		$urls_meta = $group_and_urls['meta'];

		// Set filters for pagination
		$filters['post_type'] = $filters['category'];

		// Unset filters for pagination
		unset( $filters['category'] );
		unset( $filters['page'] );

		$nonce              = wp_create_nonce( 'ajax-nonce' );
		/*$merged_url_details = array();
		$added_urls         = array(); // if we have for whatever reason the same url twice in the url group, we only show it one time.
		foreach ( $group_and_urls['urls'] as $wcd_group_url ) {
			foreach ( $wcd_website_urls as $wcd_website_url ) {
				if ( $wcd_website_url['url_id'] === $wcd_group_url['id'] && ! in_array( $wcd_group_url['id'], $added_urls, true ) ) {
					$merged_url_details[ $wcd_website_url['url_category'] ][] = array_merge( $wcd_group_url, $wcd_website_url );
					$added_urls[] = $wcd_group_url['id'];
				}
			}
		}*/

		// Select URLS.
		$tab            = 'update-settings'; // init.
		$detection_type = 'update';
		if ( $monitoring_group ) {
			$tab            = 'auto-settings';
			$detection_type = 'auto';
		}
		?>

		<div class="wcd-select-urls-container">
			<form class="wcd-frm-settings box-plain" action="<?php echo esc_url( admin_url() . 'admin.php?page=webchangedetector-' . $tab ); ?>" method="post">
				<input type="hidden" name="wcd_action" value="save_group_settings">
				<?php
				wp_nonce_field( 'save_group_settings' );

				// Manual check settings.
				if ( ! $monitoring_group ) {
					$auto_update_settings       = get_option( 'wcd_auto_update_settings' );
					$auto_update_checks_enabled = true;
					if ( ! $auto_update_settings || ! array_key_exists( 'auto_update_checks_enabled', $auto_update_settings ) ) {
						$auto_update_checks_enabled = false;
					}

					$wizard_text = '<h2>Manual Checks & Auto Update Checks</h2>In this tab, you can make all settings for auto update checks and start manual checks.';
					$this->print_wizard(
						$wizard_text,
						'wizard_manual_checks_tab',
						'wizard_manual_checks_settings',
						false,
						true,
						'top left-plus-200'
					);

					$wizard_text = '<h2>Settings</h2>If you want to check your Website during WP auto updates, you can enable this here. <br>';
					$this->print_wizard(
						$wizard_text,
						'wizard_manual_checks_settings',
						'wizard_manual_checks_urls',
						false,
						false,
						'bottom  top-minus-150 left-plus-400'
					);
					?>
					<h2>Settings</h2>
					<p style="text-align: center;">Make all settings for auto-update checks and for manual checks. </p>

					<input type="hidden" name="step" value="pre-update">
					<input type="hidden" name="wcd-update-settings" value="true">

					<?php include 'partials/templates/update-settings.php'; ?>

					<button
							class="button button-primary"
							type="submit"
							name="save_settings"
							value="post_urls"
							onclick="return wcdValidateFormManualSettings()"
							style="margin-top: 20px ;"
					>
						Save
					</button>

					<?php
				} else {
					$wizard_text = '<h2>Monitoring Settings</h2>Do all settings for the monitoring. 
                                Set the interval of the monitoring checks and the hour of when the checks should start.';

					$wizard_text = '<h2>Settings</h2>If you want to check your Website during WP auto updates, you can enable this here. <br>';
					$this->print_wizard(
						$wizard_text,
						'wizard_monitoring_settings',
						'wizard_monitoring_urls',
						false,
						false,
						'bottom  top-minus-100 left-plus-200'
					);

					// Monitoring settings
					?>
					<h2>Settings</h2>
					<p style="text-align: center;">Monitor your website and receive alert emails when something changes. </p>
					<?php
					$enabled = $group_and_urls['enabled'];
					include 'partials/templates/auto-settings.php';
					?>

					<button
							class="button button-primary"
							style="margin-top: 20px;"
							type="submit"
							name="save_settings"
							value="post_urls"
							onclick="return wcdValidateFormAutoSettings()">
						Save
					</button>


					<?php
				}

                // Select URLs section.
				$wizard_text = '<h2>Select URLs</h2>In these accordions you find all URLs of your website. 
                                Here you can select the URLs you want to check.<br>
                                These settings are taken for manual checks and for auto update checks.';
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
		?>
			</form>

			<div class="wcd-frm-settings box-plain">
			<h2>Select URLs</h2>

			<p style="text-align: center;">Currently selected URLs: <?= $group_and_urls['selected_urls_count'] ?> <br>Missing URLs? Select them from other post types and taxonomies by enabling them in the
				<a href="?page=webchangedetector-settings">Settings</a>
			</p>
			<input type="hidden" value="webchangedetector" name="page">
			<input type="hidden" value="<?php echo esc_html( $group_and_urls['id'] ); ?>" name="group_id">

			<?php if ( is_iterable( $urls ) ) { ?>
				<div class="group_urls_container">
					<form method="get" style="float: left;">
						<input type="hidden" name="page" value="webchangedetector-<?php echo esc_html( $tab ); ?>">
						Post Types
						<select name="post_type">
							<option value="0">All</option>
							<?php
							foreach ( $this->website_details['sync_url_types'] as $url_type ) {
								$selected = $url_type['post_type_slug'] === sanitize_text_field( wp_unslash( $_GET['post_type'] ) ) ? 'selected' : '';
								?>
								<option value="<?php echo esc_html( $url_type['post_type_slug'] ); ?>" <?php echo esc_html( $selected ); ?>>
									<?php echo esc_html( $url_type['post_type_name'] ); ?>
								</option>
							<?php } ?>
						</select>
						<button class="button button-secondary">Filter</button>
					</form>
					<form method="get" style="float: right;">
						<input type="hidden" name="page" value="webchangedetector-<?php echo esc_html( $tab ); ?>">
						<button type="submit" style="float: right" class="button button-secondary">Go</button>
						<input style="margin: 0" class="filter-url-table" name="search" type="text" placeholder="Search" value="<?php echo sanitize_text_field( wp_unslash( $_GET['search'] ) ?? '' ); ?>">
					</form>
					<div class="clear" style="margin-bottom: 20px;"></div>
					<table class="no-margin filter-table">
						<tr>
							<th style="min-width: 50px; text-align: center;"><?php $this->get_device_icon( 'desktop' ); ?><br>Desktop</th>
							<th style="min-width: 50px; text-align: center;"><?php $this->get_device_icon( 'mobile' ); ?> Mobile</th>
							<th style="width: 100%">URL</th>
							<th style="min-width: 90px">Post type</th>
						</tr>

						<?php //Select all from same device. ?>
						<tr class=" even-tr-white" style="background: none; text-align: center">
                            <td>
                                <label class="switch">
                                    <input type="checkbox" 
                                    id="select-desktop" 
                                    data-nonce="<?= esc_html( $nonce ) ?>"
                                    data-screensize="desktop"
                                    onclick="mmToggle( this, 'desktop', '<?= esc_html( $group_and_urls['id'] ) ?>' ); postUrl('select-desktop');"/>
                                    <span class="slider round"></span>
                                </label>
                            </td>
                            
                            <td>
                                <label class="switch">
                                    <input type="checkbox" 
                                    id="select-mobile" 
                                    data-nonce="<?= esc_html( $nonce ) ?>"
                                    data-screensize="mobile"
                                    onclick="mmToggle( this, 'mobile', '<?= esc_html( $group_and_urls['id'] ) ?>' ); postUrl('select-mobile');" />
                                    <span class="slider round"></span>
                                </label>
                            </td>
                            <td></td>
                            <td></td>
                        </tr>
                        <?php
						$amount_active_posts = 0;
						$selected_mobile     = 0;
						$selected_desktop    = 0;

						foreach ( $urls as $url ) {
							// init.
							$checked = array(
								'desktop' => $url['desktop'] ? 'checked' : '',
								'mobile'  => $url['mobile'] ? 'checked' : '',
							);
                        ?>
                        <tr class="live-filter-row even-tr-white post_id_<?= esc_html( $group_and_urls['id'] ) ?>" id="<?= esc_html( $url['id'] ) ?>" >
							<td class="checkbox-desktop" style="text-align: center;">
                                <input type="hidden" value="0" name="desktop-<?= esc_html( $url['id'] ) ?>">
                                <label class="switch">
                                    <input type="checkbox"
                                    data-nonce="<?php echo esc_html( $nonce ) ?>"
                                    data-type="<?php echo esc_html( lcfirst( $url['category'] ) ) ?>"
                                    data-screensize="desktop"
                                    data-url_id="<?php echo esc_html( $url['id'] ) ?>"
                                    name="desktop-<?php echo esc_html( $url['id'] ) ?>"
                                    value="1" <?php echo esc_html( $checked['desktop'] ) ?>
                                    id="desktop-<?php echo esc_html( $url['id'] ) ?>"
                                    onclick="mmMarkRows('<?php echo esc_html( $url['id'] ) ?>'); postUrl('<?php echo esc_html( $url['id'] ) ?>');" >
                                    <span class="slider round"></span>
                                </label>
                            </td>

							<td class="checkbox-mobile" style="text-align: center;">
                            <input type="hidden" value="0" name="mobile-<?php echo esc_html( $url['id'] ) ?>">
                            <label class="switch">
                                <input type="checkbox" 
                                data-nonce="<?php echo esc_html( $nonce ) ?>"
                                data-type="<?php echo esc_html( lcfirst( $url['category'] ) ) ?>"
                                data-screensize="mobile"
                                data-url_id="<?php echo esc_html( $url['id'] ) ?>"
                                name="mobile-<?php echo esc_html( $url['id'] ) ?>"
                                value="1" <?php echo esc_html( $checked['mobile'] ) ?>
                                id="mobile-<?php echo esc_html( $url['id'] ) ?>"
                                onclick="mmMarkRows('<?php echo esc_html( $url['id'] ) ?>'); postUrl('<?php echo esc_html( $url['id'] ) ?>');" >
                                <span class="slider round"></span>
                            </label>
                            </td>

							<td style="text-align: left;"><strong><?= esc_html( $url['html_title'] ) ?></strong><br>
							<a href="<?php echo ( is_ssl() ? 'https://' : 'http://' ) . esc_html( $url['url'] ) ?>" target="_blank"><?= esc_html( $url['url'] ) ?></a></td>
							<td><?= $url['category'] ?></td>
							</tr>

							<script> mmMarkRows('<?= esc_html( $url['id'] ) ?>'); </script>
						<?php }
			} ?>
			</table>

				</div>
			<!-- Pagination -->
			<div class="tablenav">
				<div class="tablenav-pages">
					<span class="displaying-num"><?php echo esc_html( $urls_meta['total'] ); ?> items</span>
					<span class="pagination-links">
					<?php
					$paged = sanitize_key( wp_unslash( $_GET['paged'] ) );
					if ( empty( $paged ) ) {
						$paged = 1;

					}

					foreach ( $urls_meta['links'] as $link ) {
                        $pagination_page = $this->get_params_of_url( $link['url'] )['page'];
						if ( !$link['active'] ) {
							?>
							<a class="tablenav-pages-navspan button"
								href="?page=webchangedetector-<?php echo $tab; ?>&paged=<?php echo esc_html( $pagination_page ); ?>&<?php echo build_query( $filters ); ?>">
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
				if(<?php echo isset( $_GET['paged'] ) ? 1 : 0; ?> ) {
					const scrollToEl = jQuery('.group_urls_container');
					jQuery('html').animate(
						{
							scrollTop: scrollToEl.offset().top,
						},
						0 //speed
					);
				}
			</script>
				</div>
			<?php

			if ( ! count( $urls ) ) {
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
                        data-post_type="' . esc_html( lcfirst( $url['category'] ) ) . '"
                        ></div>';
			?>
		</div>

		<?php
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
			?>

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
			$wizard_text = '<h2>Start Manual Checks</h2>When you want to do updates or other changes and check your selected websites, start the wizard here.<br>
                            The wizard guides you through the process.';
			$this->print_wizard(
				$wizard_text,
				'wizard_manual_checks_start',
				false,
				'?page=webchangedetector-auto-settings',
				false,
				'bottom bottom-plus-100 right-minus-100'
			);
			?>

			<button class="button"
					type="submit"
					name="save_settings"
					value="post_urls_update_and_auto"
					style="margin-left: 10px;"
					onclick="return wcdValidateFormManualSettings()"
			>
				Save & copy settings to monitoring
			</button>
			<?php
			if ( $this->website_details['allow_manual_detection'] ) {
				?>
				<button
						class="button button-primary"
						style="float: right;"
						type="submit"
						name="save_settings"
						value="save_update_settings_and_continue"
						onclick="return wcdValidateFormManualSettings()"
				>
					Start manual checks >
				</button>
				<?php
			}
		}
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
		$this->sync_posts( true );
		$active_posts   = array();
		$count_selected = 0;

		if ( empty( $website_details ) ) {
			$this->set_website_details();
			$website_details = $this->website_details;
		}

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
				WebChangeDetector_API_V2::update_urls_in_group_v2( $website_details['auto_detection_group_id'], $active_posts );
				WebChangeDetector_API_V2::update_urls_in_group_v2( $website_details['manual_detection_group_id'], $active_posts );
			} else {
				WebChangeDetector_API_V2::update_urls_in_group_v2( $group_id_website_details, $active_posts );
			}
			echo '<div class="updated notice"><p>Settings saved.</p></div>';
		}
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
		if ( get_option( 'wcd_wizard' ) ) {
			?>
			<div id="<?php echo esc_html( $this_id ); ?>" class="wcd-wizard  <?php echo esc_html( $extra_classes ); ?>">
				<?php
				echo wp_kses(
					$text,
					array(
						'h2' => true,
						'br' => true,
						'p'  => true,
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
					<?php $this->get_device_icon( 'update-group' ); ?> Manual Checks & Auto Update Checks
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
					<?php $this->get_device_icon( 'logs' ); ?> Queue
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
		$auto_update_settings    = get_option( 'wcd_auto_update_settings' );
		$max_auto_update_checks  = 0;
		$amount_auto_update_days = 0;

		if ( ! empty( $auto_update_settings['auto_update_checks_enabled'] ) && 'on' === $auto_update_settings['auto_update_checks_enabled'] ) {
			foreach ( self::WEEKDAYS as $weekday ) {
				if ( ! empty( $auto_update_settings['auto_update_checks_enabled'] ) && isset( $auto_update_settings[ 'auto_update_checks_' . $weekday ] ) && 'on' === $auto_update_settings[ 'auto_update_checks_' . $weekday ] ) {
					++$amount_auto_update_days;
				}
			}
			$max_auto_update_checks = $update_group['selected_urls_count'] * $amount_auto_update_days * 4; // multiplied by weekdays in a month.
		}

		$wizard_text = '<h2>Welcome to WebChange Detector</h2>This Wizard helps you to get started with your website Checks.<br>
                        You can exit the wizard any time and restart it from the dashboard.';
		$this->print_wizard(
			$wizard_text,
			'wizard_dashboard_welcome',
			'wizard_dashboard_change_detections',
			false,
			true,
			' top-plus-200 left-plus-400'
		);
		?>
		<div class="dashboard">
			<div class="no-border box-plain">
				<div class="box-half no-border">
					<h1>Welcome to WebChange Detector</h1>
					<hr>
					<p>
						Perform visual checks (visual regression tests) on your WordPress website to find
						unwanted visual changes on your web pages before anyone else sees them.
					</p>
					<p>
						Start the Wizard to see what you can do with WebChange Detector.
					</p>
					<form method="post" action="?page=webchangedetector">
						<input type="hidden" name="wcd_action" value="enable_wizard">
						<?php wp_nonce_field( 'enable_wizard' ); ?>
						<input type="submit" class="button button-primary" value="Start Wizard">
					</form>
				</div>

				<div class="box-half right ">
					<p style="margin-top: 20px;"><strong>Your Plan:</strong>  <?php echo esc_html( $client_account['plan_name'] ); ?> (renews on: <?php echo esc_html( gmdate( 'd/m/Y', strtotime( $client_account['renewal_at'] ) ) ); ?>)</p>
					<p style="margin-top:10px;"><strong>Used checks:</strong>
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
					$checks_needed    = $checks_until_renewal + $max_auto_update_checks;
					$checks_available = $client_account['checks_limit'] - $client_account['checks_done'];
					if ( $checks_needed > $checks_available ) {
						?>
						<span class="notice notice-warning" style="display:block; padding: 10px;">
							<?php $this->get_device_icon( 'warning' ); ?>
							<strong>You might run out of checks before renewal day. </strong><br>
							Current settings require up to <?php echo esc_html( number_format( $checks_needed - $checks_available, 0 ) ); ?> more checks. <br>
							<a href="<?php echo esc_html( $this->get_upgrade_url() ); ?>">Upgrade your account now.</a>
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
					'per_page'   => 3,
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

	/** Get group details and its urls.
	 *
	 * @param string $group_id The group id.
	 * @param array  $url_filter Filters for the urls.
	 * @return mixed
	 */
	public function get_group_and_urls( $group_id, $url_filter = array() ) {

		$group_and_urls         = WebChangeDetector_API_V2::get_group_v2( $group_id )['data'];
		$urls                   = WebChangeDetector_API_V2::get_group_urls_v2( $group_id, $url_filter );

		if ( empty( $urls['data'] ) ) {
			$this->sync_posts();
			$urls = WebChangeDetector_API_V2::get_group_urls_v2( $group_id, $url_filter )['data'];
		}

		$group_and_urls['urls'] = $urls['data'];
		$group_and_urls['meta'] = $urls['meta'];
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


