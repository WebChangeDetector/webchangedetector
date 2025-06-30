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
	 * @var      Wp_Compare_Loader $loader Maintains and registers all hooks for the plugin.
	 */
	protected $loader;

	/**
	 * The unique identifier of this plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string $plugin_name The string used to uniquely identify this plugin.
	 */
	protected $plugin_name;

	/**
	 * The current version of the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string $version The current version of the plugin.
	 */
	protected $version;

    public $account_details;

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
		require_once plugin_dir_path( __DIR__ ) . 'includes/class-wp-compare-loader.php';

		/**
		 * The class responsible for defining internationalization functionality
		 * of the plugin.
		 */
		require_once plugin_dir_path( __DIR__ ) . 'includes/class-wp-compare-i18n.php';

		/**
		 * The class responsible for ajax calls of the plugin.
		 */
		require_once plugin_dir_path( __DIR__ ) . 'includes/class-wp-compare-ajax.php';

		/**
		 * The Rest Routes come from an additional plugin
		 */
		require_once plugin_dir_path(__DIR__) . 'includes/class-wp-compare-rest-api-endpoints.php';

		/**
		 * The class responsible for defining all actions that occur in the admin area.
		 */
		require_once plugin_dir_path( __DIR__ ) . 'admin/class-wp-compare-admin.php';
		require_once plugin_dir_path( __DIR__ ) . 'admin/class-wp-compare-api-v2.php';

		/**
		 * The class responsible for defining all actions that occur in the public-facing
		 * side of the site.
		 */
		require_once plugin_dir_path( __DIR__ ) . 'public/class-wp-compare-public.php';

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

		// Ajax calls
		$plugin_ajax = new Wp_Compare_Ajax();
		$this->loader->add_action( 'wp_ajax_update_status_group', $plugin_ajax, 'ajax_update_status_group' );
		$this->loader->add_action( 'wp_ajax_take_screenshots', $plugin_ajax, 'ajax_take_screenshots' );
		$this->loader->add_action( 'wp_ajax_save_group_urls', $plugin_ajax, 'ajax_save_group_urls' );
		$this->loader->add_action( 'wp_ajax_save_group_settings', $plugin_ajax, 'ajax_save_group_settings' );
		$this->loader->add_action( 'wp_ajax_save_group_css', $plugin_ajax, 'ajax_save_group_css' );
		$this->loader->add_action( 'wp_ajax_wcd_content', $plugin_ajax, 'ajax_wcd_content' );
		$this->loader->add_action( 'wp_ajax_save_url', $plugin_ajax, 'ajax_save_url' );
		$this->loader->add_action( 'wp_ajax_save_group', $plugin_ajax, 'ajax_save_group' );
		$this->loader->add_action( 'wp_ajax_delete_url', $plugin_ajax, 'ajax_delete_url' );
		$this->loader->add_action( 'wp_ajax_delete_group', $plugin_ajax, 'ajax_delete_group' );
		$this->loader->add_action( 'wp_ajax_save_user_website', $plugin_ajax, 'ajax_save_user_website' );
		$this->loader->add_action( 'wp_ajax_select_group_url', $plugin_ajax, 'ajax_select_group_url' );
		$this->loader->add_action( 'wp_ajax_assign_group_urls', $plugin_ajax, 'ajax_assign_group_urls' );
		$this->loader->add_action( 'wp_ajax_unassign_group_url', $plugin_ajax, 'ajax_unassign_group_url' );
		$this->loader->add_action( 'wp_ajax_get_unassigned_group_urls', $plugin_ajax, 'ajax_get_unassigned_group_urls' );
		$this->loader->add_action( 'wp_ajax_check_url', $plugin_ajax, 'ajax_check_url' );
		$this->loader->add_action( 'wp_ajax_update_url', $plugin_ajax, 'ajax_update_url' );
		$this->loader->add_action( 'wp_ajax_switch_account', $plugin_ajax, 'ajax_switch_account' );
		$this->loader->add_action( 'wp_ajax_load_group_urls', $plugin_ajax, 'ajax_load_group_urls' );
		$this->loader->add_action( 'wp_ajax_send_feedback_mail', $plugin_ajax, 'ajax_send_feedback_mail' );
		$this->loader->add_action( 'wp_ajax_filter_change_detections', $plugin_ajax, 'ajax_filter_change_detections' );
		$this->loader->add_action( 'wp_ajax_get_processing_queue', $plugin_ajax, 'ajax_get_processing_queue' );
		$this->loader->add_action( 'wp_ajax_get_batch_processing_status', $plugin_ajax, 'ajax_get_batch_processing_status' );
		$this->loader->add_action( 'wp_ajax_update_detection_step', $plugin_ajax, 'ajax_update_detection_step' );
		$this->loader->add_action( 'wp_ajax_get_wp_post_types', $plugin_ajax, 'ajax_get_wp_post_types' );
		$this->loader->add_action( 'wp_ajax_save_wp_group_settings', $plugin_ajax, 'ajax_save_wp_group_settings' );
		$this->loader->add_action( 'wp_ajax_save_wp_group_settings_async', $plugin_ajax, 'ajax_save_wp_group_settings_async' );
		$this->loader->add_action( 'wp_ajax_check_sync_job_status', $plugin_ajax, 'ajax_check_sync_job_status' );
		$this->loader->add_action( 'wp_ajax_delete_website', $plugin_ajax, 'ajax_delete_website' );
		$this->loader->add_action( 'wp_ajax_get_preview_wp_urls', $plugin_ajax, 'ajax_get_preview_wp_urls' );
		$this->loader->add_action( 'wp_ajax_update_comparison_status', $plugin_ajax, 'ajax_update_comparison_status' );
		$this->loader->add_action( 'wp_ajax_get_comparison_status_by_token', $plugin_ajax, 'ajax_get_comparison_status_by_token' );
		$this->loader->add_action( 'wp_ajax_get_change_detection_popup', $plugin_ajax, 'ajax_get_change_detection_popup' );
		$this->loader->add_action( 'wp_ajax_add_subaccount', $plugin_ajax, 'ajax_add_subaccount' );
		$this->loader->add_action( 'wp_ajax_update_subaccount', $plugin_ajax, 'ajax_update_subaccount' );
		$this->loader->add_action( 'wp_ajax_add_api_token', $plugin_ajax, 'ajax_add_api_token' );
        		$this->loader->add_action( 'wp_ajax_get_batch_comparisons_view', $plugin_ajax, 'ajax_get_batch_comparisons_view' );
		$this->loader->add_action( 'wp_ajax_load_failed_queues', $plugin_ajax, 'ajax_load_failed_queues' );


				$this->loader->add_action( 'wp_ajax_preview_screenshot', $plugin_ajax, 'ajax_preview_screenshot' );
		$this->loader->add_action( 'wp_ajax_preview_urls', $plugin_ajax, 'ajax_preview_urls' );

		// Public available without login (with "nopriv_")
		$this->loader->add_action( 'wp_ajax_nopriv_preview_screenshot', $plugin_ajax, 'ajax_preview_screenshot' );
		$this->loader->add_action( 'wp_ajax_nopriv_get_preview_wp_urls', $plugin_ajax, 'ajax_get_preview_wp_urls' );

		// Cron hook for background URL sync processing
		$this->loader->add_action( 'wcd_process_url_sync', $this, 'process_url_sync_job' );

		// Safe table creation check for live systems
		$this->loader->add_action( 'admin_init', $this, 'ensure_sync_jobs_table_exists' );

		// $this->loader->add_action('wp_ajax_get_sc_groups_and_urls', $plugin_ajax, 'ajax_get_sc_groups_and_urls');

		// Load admin-ajax.php in frontend
		add_action(
			'wp_head',
			function () {
				echo '<script type="text/javascript">
                        var ajaxurl = "' . admin_url( 'admin-ajax.php' ) . '";
                    </script>';
			}
		);

		// Add CSRF token to meta tag for ajax.setup in JS
		add_action(
			'wp_head',
			function () {
				// NOTE only works as a closure
				$nonce = wp_create_nonce( 'webchangedetector_nonce' );
				echo "<meta name='csrf-token' content='$nonce'>";
			}
		);

		// Disable WP backend for non-admins
		add_action(
			'admin_init',
			function () {
				if ( ! current_user_can( 'editor' ) && ! current_user_can( 'administrator' ) && ! wp_doing_ajax() ) {
					$protocol = $_SERVER['HTTPS'] ? 'https://' : 'http://';
					wp_safe_redirect( $protocol . $_SERVER['HTTP_HOST'] );
					exit;
				}
			}
		);
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
	 * @return    string    The name of the plugin.
	 * @since     1.0.0
	 */
	public function get_plugin_name() {
		return $this->plugin_name;
	}

	/**
	 * The reference to the class that orchestrates the hooks with the plugin.
	 *
	 * @return    Wp_Compare_Loader    Orchestrates the hooks of the plugin.
	 * @since     1.0.0
	 */
	public function get_loader() {
		return $this->loader;
	}

	/**
	 * Retrieve the version number of the plugin.
	 *
	 * @return    string    The version number of the plugin.
	 * @since     1.0.0
	 */
	public function get_version() {
		return $this->version;
	}

	/** Get account details.
	 *
	 * @param bool $force Force account data from api.
	 * @return array|string|bool
	 */
	public function get_account_details_v2( $api_token = null ) {

        if(!empty($this->account_details) && empty($api_token)) {
            return $this->account_details;
        }

		$account_details = Wp_Compare_API_V2::get_account_v2($api_token);

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

	public function show_activate_account() {
		global $current_user;
		// The account is not activated yet, but the api_token is there already
		if ( isset( $_POST['action'] ) && $_POST['action'] === 'resend_verification_email' ) {
			$this->resend_verification_email();
			echo '<div style="max-width: 600px; margin: 0 auto;">
                    <div class="mm_message_container" style="margin-left: 0; margin-right: 0;">
                        <div class="message success" style="padding: 10px;">Email sent successfully.</div>
                    </div>
                </div>';
		}

		echo '<div class="mm_wp_compare mm_no_account" style="max-width: 600px; margin: 0 auto; padding: 40px 20px;">
                <div class="mm_activation_card">
                    <h1 class="mm_activation_title">Activate Your Account</h1>
                    <p class="mm_activation_text">
                        We\'ve sent a confirmation email to activate your account. Click the link in the email to get started.
                    </p>
                    <div class="mm_email_highlight">
                        <div class="mm_email_address">' . $current_user->user_email . '</div>
                    </div>
                    <p class="mm_activation_text">
                        Can\'t find the email? Check your spam folder or request a new one below.
                    </p>
                    
                    <div class="mm_resend_section">
                        <form action="' . MM_APP_PATH . '?tab=dashboard" method="post">
                            <input type="hidden" name="action" value="resend_verification_email">
                            <input type="submit" id="resend-email-btn" value="Send activation email again" class="et_pb_button" disabled>
                        </form>
                        <p class="mm_help_text">You can request a new email in <span id="countdown-display" class="mm_countdown_timer">30</span> seconds</p>
                    </div>
                </div>
                <script>
                (function() {
                    let countdown = 30;
                    const button = document.getElementById("resend-email-btn");
                    const countdownDisplay = document.getElementById("countdown-display");
                    const originalText = button.value;
                    const timer = setInterval(function() {
                        countdown--;
                        if (countdown > 0) {
                            //button.value = originalText + " (" + countdown + ")";
                            countdownDisplay.textContent = countdown;
                        } else {
                            button.value = originalText;
                            countdownDisplay.parentElement.style.display = "none";
                            button.disabled = false;
                            clearInterval(timer);
                        }
                    }, 1000);
                })();
                </script>
            </div>';
		return true;
	}


	/**
	 * Get queue.
	 *
	 * @param int $limit The limit.
	 * @param int $offset The offset.
	 * @return array|string
	 */
	public function get_queue( $page ) {
		return Wp_Compare_API_V2::get_queue_v2(false, 'open,processing,done,failed', array( 'page' => $page ));
	}

	/**
	 * Is only called internally from `get_view_unassigned_group_urls()`
	 *
	 * @param int $group_id
	 * @return void
	 */
	private function get_unassigned_urls( $group_id ) {
		$args = array(
			'action'   => 'get_unassigned_urls',
			'group_id' => $group_id,
		);
		return mm_api( $args );
	}

	public function get_view_unassigned_group_urls( $postdata ) {
		$client_urls = $this->get_unassigned_urls( $postdata['group_id'] );
		if ( is_iterable( $client_urls ) && count( $client_urls ) === 0 ) {
			echo 'There are currently no urls which can be assigned. Please add a new URL to assign it to this group.';
		} elseif ( is_iterable( $client_urls ) ) {
			?>
		<div class="url-table">
			<table style="overflow: scroll">
				<thead>
				<tr>
					<th><?php echo get_device_icon( 'desktop' ); ?></th>
					<th><?php echo get_device_icon( 'mobile' ); ?></th>
					<th width="100%"> URL</th>
				</tr>
				</thead>
				<tbody>
				<?php
				foreach ( $client_urls as $client_url ) {
					$url_id     = $client_url['id'];
					$element_id = $postdata['group_id'] . '-' . $url_id;
					?>
					<input type="hidden" name="url_id-<?php echo $url_id; ?>" value="<?php echo $url_id; ?>">
					<tr>
						<td>
							<label id="container-desktop-<?php echo $element_id; ?>" class="checkbox_container"
									style="float: left">
								<input type="hidden" name="desktop-<?php echo $url_id; ?>" value="0">
								<input type="checkbox"
										id="checkbox-desktop-<?php echo $element_id; ?>"
										name="desktop-<?php echo $url_id; ?>"
										value="1">
								<span class="checkmark"></span>
							</label>
						</td>
						<td>
							<label id="container-mobile-<?php echo $element_id; ?>" class="checkbox_container"
									style="float: left">
								<input type="hidden" name="mobile-<?php echo $url_id; ?>" value="0">
								<input type="checkbox"
										id="checkbox-mobile-<?php echo $element_id; ?>"
										name="mobile-<?php echo $url_id; ?>"
										value="1">
								<span class="checkmark"></span>
							</label>
						</td>
						<td>
							<?php echo '<div class="html-title">' . $client_url['html_title'] . '</div>' . $client_url['url']; ?>
						</td>
					</tr>
					<script>mmMarkRows(<?php echo $url_id; ?>);</script>
					<?php
				}
				?>
				</tbody>
			</table>
		</div>
			<?php
		} else {
			echo 'Something went wrong, please try again.';
		}
	}

	public function assign_urls( $postdata ) {
		// Get active posts from post data
		$active_posts = array();

		foreach ( $postdata as $key => $url_id ) {
			if ( strpos( $key, 'url_id' ) === 0
			// && $postdata['assign-' . $url_id] == 1)
				&& ( $postdata[ 'desktop-' . $url_id ] == 1
				|| $postdata[ 'mobile-' . $url_id ] == 1 ) ) {
				$active_posts[] = array(
					'url_id'  => $url_id,
					'desktop' => ! empty( $postdata[ 'desktop-' . $url_id ] ) ? $postdata[ 'desktop-' . $url_id ] : 0,
					'mobile'  => ! empty( $postdata[ 'mobile-' . $url_id ] ) ? $postdata[ 'mobile-' . $url_id ] : 0,
					'css'     => ! empty( $postdata[ 'css-' . $url_id ] ) ? urlencode( $postdata[ 'css-' . $url_id ] ) : '',
				);
			}
		}

		// Update API URLs
		$args = array(
			'action'   => 'update_urls',
			'group_id' => $postdata['group_id'],
			'posts'    => json_encode( $active_posts ),
		);
		return mm_api( $args );
	}

	public function unassign_group_url( $postdata ) {
		$args = array(
			'action'   => 'unassign_url',
			'group_id' => $postdata['group_id'],
			'url_id'   => $postdata['url_id'],
		);

		return mm_api( $args );
	}

    public function unassign_group_urls_v2($postdata) {
        if(!empty($postdata['url_id'])) {
            $postdata['urls'][] = $postdata['url_id'];
        }
        return Wp_Compare_API_V2::remove_urls_from_group_v2($postdata['group_id'], $postdata['urls']);
    }


	public function update_group_url_v2( $postdata ) {
		$result_group_url = Wp_Compare_API_V2::update_url_in_group_v2( $postdata['group_id'], $postdata['url_id'], $postdata );

		$result_url = Wp_Compare_API_V2::update_url( $postdata );

		if ( ! empty( $result_group_url['data'] ) && ! empty( $result_url['data'] ) ) {
			return true;
		}
		return false;
	}

	public function save_url( $postdata ) {
		$group_id = false;
		foreach ( $postdata as $key => $post ) {
			if ( strpos( $key, 'group_id-' ) === 0 )  {
                $group_id = $post;
            }
		}
		$urls = $this->textfield_to_array( $postdata['url'] );

		$urls_arr = array();
		foreach ( $urls as $url ) {
			$new_url = Wp_Compare_API_V2::add_url_v2($url);
			$urls_arr[] = [
                'id' => $new_url['data']['id'],
                'desktop'  => ! empty( $postdata[ 'desktop-' . $group_id ] ) ? $postdata[ 'desktop-' . $group_id ] : 0,
                'mobile'   => ! empty( $postdata[ 'mobile-' . $group_id ] ) ? $postdata[ 'mobile-' . $group_id ] : 0,
                'css'      => ! empty( $postdata[ 'css-' . $group_id ] ) ? $postdata[ 'css-' . $group_id ] : '',
                'js'       => ! empty( $postdata[ 'js-' . $group_id ] ) ? $postdata[ 'js-' . $group_id ] : '',
            ];
		}
        return Wp_Compare_API_V2::add_urls_to_group_v2($group_id, $urls_arr);
	}

	/**
	 * Action: `update_urls`
	 */
	public function save_group_urls( $postdata ) {
		$active_posts = array(); // init

		// only the ones with `pid-`
		$pidData = array_filter(
			$postdata,
			function ( $value, $key ) {
				return str_contains( $key, 'pid' );
			},
			ARRAY_FILTER_USE_BOTH
		);

		// Get active posts from post data
		foreach ( $pidData as $post_id ) {
			$tmp = array(); // init

			if ( isset( $postdata[ 'desktop-' . $post_id ] ) ) {
				$tmp['desktop'] = $postdata[ 'desktop-' . $post_id ];
			}
			if ( isset( $postdata[ 'mobile-' . $post_id ] ) ) {
				$tmp['mobile'] = $postdata[ 'mobile-' . $post_id ];
			}
			if ( isset( $postdata[ 'css-' . $post_id ] ) ) {
				$tmp['css'] = $postdata[ 'css-' . $post_id ];
			}

			if ( isset( $postdata[ 'js-' . $post_id ] ) ) {
				$tmp['js'] = $postdata[ 'js-' . $post_id ];
			}

			if ( ! empty( $tmp ) ) {
				$tmp['id']  = $post_id;
				$active_posts[] = $tmp; // this is given to API
			}
		}

        return Wp_Compare_API_V2::update_urls_in_group_v2($postdata['group_id'],$active_posts);

	}

	/**
	 * @param $postdata
	 *
	 * @return array|string|null
	 * @deprecated
	 */
	public function select_group_url( $postdata ) {
		$args = array(
			'action'                => 'update_group_url',
			'group_id'              => $postdata['groupId'],
			'url_id'                => $postdata['urlId'],
			$postdata['deviceName'] => $postdata['deviceValue'],
		);
		return mm_api( $args );
	}

	public function select_group_url_v2( $postdata ) {

		if ( ! empty( $postdata['device_name'] ) ) {
			$args = array(
				$postdata['device_name'] => $postdata['device_value'],
			);
		} else {
			$args = array(
				'desktop' => $postdata['desktop'] ?? 0,
				'mobile'  => $postdata['mobile'] ?? 0,
			);
		}

		if ( ! empty( $postdata['css'] ) ) {
			$args['css'] = $postdata['css'];
		}
		if ( ! empty( $postdata['js'] ) ) {
			$args['js'] = $postdata['js'];
		}
		if ( ! empty( $postdata['threshold'] ) ) {
			$args['threshold'] = $postdata['threshold'];
		}

		return Wp_Compare_Api_V2::update_url_in_group_v2( $postdata['group_id'], $postdata['url_id'], $args );
	}

	public function update_monitoring_settings( $postdata ) {
		$args = array(
			'action'     => 'update_group',
			'group_id'   => $postdata['group_id'],
			'monitoring' => empty( $postdata['monitoring'] ) ? 0 : $postdata['monitoring'],
		);
		return mm_api( $args );
	}

	public function save_group_settings( $postdata ) {

		$args = array(
			'enabled'    => empty( $postdata['enabled'] ) ? 0 : $postdata['enabled'],
			'monitoring' => empty( $postdata['monitoring'] ) ? 0 : $postdata['monitoring'],
			'cms'        => $postdata['cms'] ?? null,

			// 'groups' => json_encode( $assigned_groups )
		);
        if ( isset( $postdata['group_name'] ) ) {
            $args['name'] = $postdata['group_name'];
        }
        if ( isset( $postdata['threshold'] ) ) {
            $args['threshold'] = $postdata['threshold'];
        }
		if ( isset( $postdata['css'] ) ) {
			$args['css'] = $postdata['css'];
		}
        if( isset($postdata['js'])) {
	        $args['js']         = $postdata['js'];
        }

		// Send monitoring settings if it is a monitoring group
		if ( (int) $postdata['monitoring'] === 1 ) {
			if( isset($postdata['hour_of_day'])) {
				$args['hour_of_day']         = $postdata['hour_of_day'];
			}
			if( isset($postdata['interval_in_h'])) {
				$args['interval_in_h']         = $postdata['interval_in_h'];
			}
			if( isset($postdata['alert_emails'])) {
				$args['alert_emails']         = explode(",",$postdata['alert_emails']);
			}

		}
        if($postdata['group_id']) {
	        $group = Wp_Compare_API_V2::update_group( $postdata['group_id'], $args );
        } else {
            $group = Wp_Compare_API_V2::create_group_v2($args);
        }
		if ( empty( $group['data']) ) {
			return $group['message'];
		}
        $group = $group['data'];
		if ( isset( $postdata['url'] ) ) {
			$postdata[ 'group_id-' . $group['id'] ] = $group['id'];
			$postdata[ 'desktop-' . $group['id'] ]  = $postdata['desktop'];
			unset( $postdata['desktop'] );

			$postdata[ 'mobile-' . $group['id'] ] = $postdata['mobile'];
			unset( $postdata['mobile'] );

			$postdata['css'] = '';

			$this->save_url( $postdata );
		}

		/*
		if(!empty($postdata['sync_wp_urls']) && $postdata['sync_wp_urls'] == true) {
			$result = $this->save_wp_group_settings($postdata);
		}*/

		return $group;
	}

	/*
	public function get_wp_group_post_types($postdata) {
		$response = wp_remote_get(('https://www.wp-mike.com/wp-json/wp/v2/types'));
		$result = wp_remote_retrieve_headers($response);
		return ($result);
		$domain = $this->check_url($postdata['domain']); // check the domain and get the correct schema

		return $this->get_wp_post_types($domain);
	}*/

	public function save_wp_group_settings( $postdata ) {
		if ( ! empty( $postdata['group_id'] ) && $postdata['group_id'] !== 0 ) {
			$domain = $this->get_domain_by_group_id( $postdata['group_id'] );
		} elseif ( ! empty( $postdata['domain'] ) ) {
			$domain = $this->check_url( $postdata['domain'] )[0];
			$domain = rtrim( $domain, '/' );
		} else {
			return false;
		}
		// $domain = $this->check_url($postdata['domain']); // check the domain and get the correct schema
		// Get the urls
		$post_types = array();

		foreach ( $postdata as $key => $value ) {
			if ( strpos( $key, 'wp_api_' ) === 0 && $postdata[ $key ] ) {
				$value        = json_decode( str_replace( '\\"', '"', $value ), true );
				$post_types[] = $value;
			}
		}

		$urls = $this->get_wp_urls( $domain, $post_types );

		if ( ! count( $urls ) ) {
			return 'no posts';
		}

		// Add new website if group_id = 0
		if ( isset( $postdata['group_id'] ) && (int) $postdata['group_id'] === 0 ) {

			// Add website and both group types if they don't exist yet
			$args    = array(
				'action'    => 'add_website_groups',
				'domain'    => $domain,
				'cms'       => $postdata['cms'] ?? null,
				'threshold' => $postdata['threshold'] ?? 0,
			);
			$success = mm_api( $args );

			// Early return
			if ( ! $success ) {
				return 'couln\'t create website groups';
			}
		}

		// Get the group ids
		$website_details = $this->get_website_details( $domain )[0];

		// Get current post_type settings
		/*
		$selected_group_post_types = get_user_meta(get_current_user_id(), USER_META_WP_GROUP_POST_TYPES, true);

		if(!is_array($selected_group_post_types)) {
			$selected_group_post_types = [];
		}
		// Add new group_ids and settings
		$selected_group_post_types[$website_details['manual_detection_group_id']] = $post_types;
		$selected_group_post_types[$website_details['auto_detection_group_id']] = $post_types;
		*/
		$website_details['sync_url_types'] = $post_types;
		$args                              = array(
			'action' => 'save_user_website',
		);
		$args                              = array_merge( $args, $website_details );
		$success                           = mm_api( $args );

		// Update selected post_types for group
		// update_user_meta(get_current_user_id(), USER_META_WP_GROUP_POST_TYPES, $selected_group_post_types);

		// Sync the urls
		/*
		$args = [
			'action' => 'sync_urls',
			'domain' => $domain,
			'delete_missing_urls' => true,
			'posts' => json_encode($urls),
		];
		mm_api($args); // receiving sync data might increase memory. Count result didn't work.
		*/
		Wp_Compare_API_V2::sync_urls( $urls, $domain );
		Wp_Compare_API_V2::start_url_sync( $domain, true );

		// $this->log($synced_urls);
		// $amount = count($urls);
		return array( 'urls_added' => true );
	}

	/**
	 * Asynchronous version of save_wp_group_settings that creates empty group and queues background sync.
	 *
	 * @param array $postdata Form data from the frontend.
	 * @return array|string Result array with job info or error string.
	 */
	public function save_wp_group_settings_async( $postdata ) {
		error_log( 'WCD: save_wp_group_settings_async called with data: ' . print_r( $postdata, true ) );
		
		// Validate domain
		if ( ! empty( $postdata['group_id'] ) && $postdata['group_id'] !== 0 ) {
			$domain = $this->get_domain_by_group_id( $postdata['group_id'] );
		} elseif ( ! empty( $postdata['domain'] ) ) {
			$domain = $this->check_url( $postdata['domain'] )[0];
			$domain = rtrim( $domain, '/' );
		} else {
			return false;
		}

		// Process post types
		$post_types = array();
		foreach ( $postdata as $key => $value ) {
			if ( strpos( $key, 'wp_api_' ) === 0 && $postdata[ $key ] ) {
				$value        = json_decode( str_replace( '\\"', '"', $value ), true );
				$post_types[] = $value;
			}
		}
		
		error_log( 'WCD: Processed post_types: ' . print_r( $post_types, true ) );

		try {
			// Step 1: Create groups immediately using existing V2 API
			$manual_group_data = array(
				'name' => $domain . ' Manual Checks',
				'monitoring' => false,
				'enabled' => true,
				'threshold' => floatval( $postdata['threshold'] ?? 0 ),
				'cms' => 'wordpress',
			);

			$monitoring_group_data = array(
				'name' => $domain . ' Monitoring',
				'monitoring' => true,
				'enabled' => true,
				'threshold' => floatval( $postdata['threshold'] ?? 0 ),
				'cms' => 'wordpress',

			);

			// Create groups via existing V2 API methods
			$manual_group = Wp_Compare_API_V2::create_group_v2( $manual_group_data );
			$monitoring_group = Wp_Compare_API_V2::create_group_v2( $monitoring_group_data );

			if ( ! $manual_group || ! $monitoring_group ) {
				return array( 'error' => 'Failed to create groups' );
			}

			// Step 2: Create website immediately using V2 API
			$website_data = array(
				'domain' => $domain,
				'manual_detection_group_id' => $manual_group['data']['id'],
				'auto_detection_group_id' => $monitoring_group['data']['id'],
				'sync_url_types' => $this->format_sync_url_types( $post_types ),
				'auto_update_settings' => array(
					'auto_update_checks_enabled' => false,
					'auto_update_checks_from' => '13:37',
					'auto_update_checks_to' => '13:42',
					'auto_update_checks_monday' => true,
					'auto_update_checks_tuesday' => true,
					'auto_update_checks_wednesday' => true,
					'auto_update_checks_thursday' => true,
					'auto_update_checks_friday' => true,
					'auto_update_checks_saturday' => false,
					'auto_update_checks_sunday' => false,
					'auto_update_checks_emails' => wp_get_current_user()->user_email,
				),
			);
error_log( 'WCD: website_data: ' . print_r( $website_data, true ) );
			$website = Wp_Compare_API_V2::create_website_v2( $website_data );
error_log( 'WCD: website: ' . print_r( $website, true ) );
			if ( ! $website ) {
				return array( 'error' => 'Failed to create website' );
			}

			// Step 3: Queue only the URL sync job for background processing
			$sync_data = array(
				'domain' => $domain,
				'manual_detection_group_id' => $manual_group['data']['id'],
				'auto_detection_group_id' => $monitoring_group['data']['id'],
				'post_types' => $post_types,
				'website_id' => $website['data']['id'],
			);

			$job_id = $this->queue_url_sync_job( $domain, $post_types, $sync_data );

			$result = array( 
				'job_queued' => true,
				'job_id' => $job_id,
				'status' => 'syncing',
				'domain' => $domain,
				'manual_group_id' => $manual_group['data']['id'],
				'monitoring_group_id' => $monitoring_group['data']['id'],
				'website_id' => $website['data']['id']
			);
			
			error_log( 'WCD: save_wp_group_settings_async returning: ' . print_r( $result, true ) );
			return $result;

		} catch ( Exception $e ) {
			error_log( "WCD Async Setup Error: " . $e->getMessage() );
			return array( 'error' => $e->getMessage() );
		}
	}



	/**
	 * Format post types for sync_url_types field.
	 *
	 * @param array $post_types Post types from form.
	 * @return array Formatted sync URL types.
	 */
	private function format_sync_url_types( $post_types ) {
		$sync_url_types = array();
		
		error_log( 'WCD: format_sync_url_types input: ' . print_r( $post_types, true ) );

		foreach ( $post_types as $post_type ) {
			$sync_url_types[] = array(
				'url_type_slug' => 'types',
				'url_type_name' => 'Post Types',
				'post_type_slug' => $post_type['post_type_slug'],
				'post_type_name' => $post_type['post_type_name'] ?? ucfirst( $post_type['post_type'] ),
			);
		}
		
		error_log( 'WCD: format_sync_url_types output: ' . print_r( $sync_url_types, true ) );

		return $sync_url_types;
	}

	/**
	 * Queue URL sync job for background processing.
	 *
	 * @param string $domain Website domain.
	 * @param array $post_types Selected post types to sync.
	 * @param array $website_details Website details from API.
	 * @return string Job ID.
	 */
	private function queue_url_sync_job( $domain, $post_types, $website_data ) {
		global $wpdb;
		
		// Ensure table exists before trying to use it
		$this->ensure_sync_jobs_table_exists();
		
		$job_id = uniqid( 'wcd_sync_' . time() . '_' );
		
		// Get current user's API token to use in background job
		$current_user_id = get_current_user_id();
		$selected_api_token = get_user_meta( $current_user_id, "wcd_active_api_token", true );
		if ( ! $selected_api_token ) {
			$selected_api_token = mm_api_token(); // Fallback to main account token
		}
		
		// Store complete website data, post types, and API token in the job record
		$job_data = array(
			'post_types' => $post_types,
			'website_data' => $website_data,
			'user_id' => $current_user_id,
			'api_token' => $selected_api_token
		);
		
		// Insert sync job record
		$wpdb->insert(
			$wpdb->prefix . 'wcd_sync_jobs',
			array(
				'job_id' => $job_id,
				'domain' => $domain,
				'manual_group_id' => $website_data['manual_detection_group_id'] ?? null,
				'monitoring_group_id' => $website_data['auto_detection_group_id'] ?? null,
				'post_types' => json_encode( $job_data ), // Store all data here
				'status' => 'queued',
				'progress' => 0
			),
			array( '%s', '%s', '%s', '%s', '%s', '%s', '%d' )
		);
		
		// Schedule WordPress cron job immediately 
		wp_schedule_single_event( time() + 5, 'wcd_process_url_sync', array( $job_id ) );
		
		return $job_id;
	}

	/**
	 * Background processor for URL sync.
	 *
	 * @param string $job_id Unique job identifier.
	 */
	public function process_url_sync_job( $job_id ) {
		global $wpdb;
		
		// Get job details
		$job = $wpdb->get_row( $wpdb->prepare(
			"SELECT * FROM {$wpdb->prefix}wcd_sync_jobs WHERE job_id = %s",
			$job_id
		), ARRAY_A );
		
		if ( ! $job ) {
			error_log( "WCD Sync Job not found: {$job_id}" );
			return;
		}
		
		try {
			// Update job status to processing
			$this->update_sync_job_status( $job_id, 'processing', 10, 'Starting URL sync...' );
			
			// Increase execution limits
			if ( ! wp_doing_ajax() ) {
				set_time_limit( 600 ); // 10 minutes
				ini_set( 'memory_limit', '1024M' );
			}
			
			// Decode job data
			$job_data = json_decode( $job['post_types'], true );
			$post_types = $job_data['post_types'] ?? array();
			$website_data = $job_data['website_data'] ?? array();
			$api_token = $job_data['api_token'] ?? mm_api_token(); // Use stored token or fallback
			
			// Groups and website are already created - start URL processing
			$this->update_sync_job_status( $job_id, 'processing', 20, 'Fetching URLs from WordPress...' );
			
			// Use the original get_wp_urls function to maintain compatibility
			$urls = $this->get_wp_urls( $job['domain'], $post_types );
			
			if ( ! $urls || ! count( $urls ) ) {
				$this->update_sync_job_status( $job_id, 'failed', 0, 'No URLs found' );
				return;
			}

			error_log( 'WCD: urls: ' . print_r( $urls, true ) );
			// Flatten the chunked URLs array for API compatibility
			$total_url_count = 0;
			foreach ( $urls as $chunk ) {
				foreach ( $chunk as $url_type_category => $url_list ) {
					$total_url_count += count( $url_list );
				}
			}

			$total_url_count = count( $urls );
			
			$this->update_sync_job_status( $job_id, 'processing', 40, 'Synchronizing URLs...', $total_url_count );
			
			// Use the exact same sync process as the original function with flattened URLs
			// Pass the API token to ensure authentication works in background
			error_log( 'WCD: flattened_urls: ' . print_r( $urls, true ) );
			$this->sync_urls_with_token( $urls, $job['domain'], $api_token );
			$this->update_sync_job_status( $job_id, 'processing', 80, 'Starting final sync...' );
			$this->start_url_sync_with_token( $job['domain'], $api_token, true );
			
			// Complete
			$this->update_sync_job_status( $job_id, 'completed', 100, 'Sync completed successfully!' );
			
			// Cleanup old jobs
			$this->cleanup_old_sync_jobs();
			
		} catch ( Exception $e ) {
			error_log( "WCD Sync Job failed: {$job_id} - " . $e->getMessage() );
			$this->update_sync_job_status( $job_id, 'failed', 0, $e->getMessage() );
			$this->update_website_sync_status( $job['domain'], 'failed' );
		}
	}






	/**
	 * Sync URLs with specific API token for background jobs.
	 *
	 * @param array $urls URLs to sync.
	 * @param string $domain Website domain.
	 * @param string $api_token API token to use.
	 */
	private function sync_urls_with_token( $urls, $domain, $api_token ) {
		return Wp_Compare_API_V2::sync_urls_with_token( $urls, $domain, $api_token );
	}
	
	/**
	 * Start URL sync with specific API token for background jobs.
	 *
	 * @param string $domain Website domain.
	 * @param string $api_token API token to use.
	 * @param bool $delete_missing_urls Delete missing URLs.
	 */
	private function start_url_sync_with_token( $domain, $api_token, $delete_missing_urls = true ) {
		return Wp_Compare_API_V2::start_url_sync_with_token( $domain, $api_token, $delete_missing_urls );
	}

	/**
	 * Update sync job status and progress.
	 *
	 * @param string $job_id Job ID.
	 * @param string $status Job status.
	 * @param int|null $progress Progress percentage.
	 * @param string $error_message Error message if any.
	 * @param int|null $total_urls Total URLs found.
	 */
	private function update_sync_job_status( $job_id, $status, $progress = null, $status_message = '', $total_urls = null ) {
		global $wpdb;
		
		$data = array( 'status' => $status );
		$format = array( '%s' );
		
		if ( $progress !== null ) {
			$data['progress'] = $progress;
			$format[] = '%d';
		}
		
		if ( $status_message ) {
			$data['error_message'] = $status_message; // Reuse error_message field for status messages
			$format[] = '%s';
		}
		
		if ( $total_urls !== null ) {
			$data['total_urls'] = $total_urls;
			$format[] = '%d';
		}
		
		$wpdb->update(
			$wpdb->prefix . 'wcd_sync_jobs',
			$data,
			array( 'job_id' => $job_id ),
			$format,
			array( '%s' )
		);
		
		// Log progress for debugging
		error_log( "WCD Job {$job_id}: {$status} - {$progress}% - {$status_message}" );
	}

	/**
	 * Update website sync status.
	 *
	 * @param string $domain Website domain.
	 * @param string $status Sync status.
	 */
	private function update_website_sync_status( $domain, $status ) {
		$website_details = $this->get_website_details( $domain )[0];
		$website_details['sync_status'] = $status;
		
		$args = array( 'action' => 'save_user_website' );
		$args = array_merge( $args, $website_details );
		mm_api( $args );
	}

	/**
	 * Get sync job status.
	 *
	 * @param string $job_id Job ID.
	 * @return array|null Job data or null if not found.
	 */
	public function get_sync_job_status( $job_id ) {
		global $wpdb;
		
		return $wpdb->get_row( $wpdb->prepare(
			"SELECT * FROM {$wpdb->prefix}wcd_sync_jobs WHERE job_id = %s",
			$job_id
		), ARRAY_A );
	}

	/**
	 * Cleanup old sync jobs (older than 24 hours).
	 */
	private function cleanup_old_sync_jobs() {
		global $wpdb;
		
		$wpdb->query( $wpdb->prepare(
			"DELETE FROM {$wpdb->prefix}wcd_sync_jobs WHERE created_at < %s",
			date( 'Y-m-d H:i:s', strtotime( '-24 hours' ) )
		) );
	}

	/**
	 * Ensure sync jobs table exists - safe for live systems.
	 * Checks once and sets option to avoid future checks.
	 */
	public function ensure_sync_jobs_table_exists() {
		// If we've already confirmed the table exists, skip check
		if ( get_option( 'wcd_sync_table_created', false ) ) {
			return;
		}

		// Skip check for non-admin users unless they're using the sync functionality
		if ( ! is_admin() && ! current_user_can( 'edit_posts' ) ) {
			return;
		}

		global $wpdb;
		$table_name = $wpdb->prefix . 'wcd_sync_jobs';
		
		// Check if table exists
		$table_exists = $wpdb->get_var( $wpdb->prepare(
			"SHOW TABLES LIKE %s",
			$table_name
		) );

		if ( $table_exists !== $table_name ) {
			$this->create_sync_jobs_table();
		}

		// Set option to indicate table exists - no need to check again
		update_option( 'wcd_sync_table_created', true );
	}

	/**
	 * Create sync jobs table for background URL synchronization.
	 * Safe method that can be called at runtime.
	 */
	private function create_sync_jobs_table() {
		global $wpdb;
		
		$table_name = $wpdb->prefix . 'wcd_sync_jobs';
		$charset_collate = $wpdb->get_charset_collate();
		
		$sql = "CREATE TABLE $table_name (
			id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			job_id varchar(50) NOT NULL UNIQUE,
			domain varchar(255) NOT NULL,
			manual_group_id varchar(50) DEFAULT NULL,
			monitoring_group_id varchar(50) DEFAULT NULL,
			post_types longtext,
			status enum('queued','processing','completed','failed') DEFAULT 'queued',
				progress int(3) DEFAULT 0,
				total_urls int(10) DEFAULT 0,
				processed_urls int(10) DEFAULT 0,
				error_message text,
				created_at timestamp DEFAULT CURRENT_TIMESTAMP,
				updated_at timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
				PRIMARY KEY (id),
				KEY idx_job_id (job_id),
				KEY idx_status (status),
				KEY idx_created_at (created_at)
			) $charset_collate;";
		
		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		dbDelta( $sql );
		
		// Log table creation for debugging
		error_log( 'WCD: Sync jobs table created successfully' );
		
		// Set option to indicate table was created successfully
		update_option( 'wcd_sync_table_created', true );
	}

	public function get_domain_by_group_id( $group_id ) {
		$website = $this->get_website_by_group_id( $group_id );
		return $website['domain'] ?? false;
	}

	public function get_website_by_group_id( $group_id ) {
		static $websites;

		// Only fetch websites once and cache them in static variable.
		if ( $websites === null ) {
			$websites = Wp_Compare_API_V2::get_websites_v2()['data'] ?? [];
		}
		//dd($websites);
		foreach ( $websites as $website ) {
			if ( in_array( $group_id, array( $website['manual_detection_group'], $website['auto_detection_group'] ) ) ) {
				return $website;
			}
		}
		return false;
	}

	public function save_group_css( $postdata ) {
		$args = array(
			'action'   => 'update_group',
			'group_id' => $postdata['group_id'],
			'css'      => $postdata['css'],
			'js'       => $postdata['js'],
		);
		return mm_api( $args );
	}

	public function save_user_website( $postdata ) {
		$allowances = array();
		foreach ( $postdata as $post_key => $post_value ) {
			if ( starts_with( $post_key, 'allowances_' ) ) {
				$allowances[ substr( $post_key, strlen( 'allowances_' ) ) ] = $post_value;
			}
		}

		$auto_update_settings = array();
		foreach ( $postdata as $post_key => $post_value ) {
			if ( starts_with( $post_key, 'auto_update_settings_' ) ) {
				$auto_update_settings[ substr( $post_key, strlen( 'auto_update_settings_' ) ) ] = $post_value;
			}
		}

		$args = array(
			'action'                     => 'save_user_website',
			'id'                         => $postdata['id'],
			'allowances'                 => $allowances,
			'auto_update_settings'       => $auto_update_settings,
			/*'enable_limits'              => 1, // backwards compatibility
			'allow_manual_detection'     => 1, // backwards compatibility
			'url_limit_manual_detection' => 1, // backwards compatibility
			'allow_auto_detection'       => 1, // backwards compatibility
			'sc_limit'                   => 1,// backwards compatibility */
		);

        return Wp_Compare_API_V2::update_website_v2($args, $postdata['api_token'] ?? null);

		//return mm_api( $args );
	}

	public function get_website_details( $domain, $api_token = null ) {
		$args = array(
			'action' => 'get_website_details',
			'domain' => $domain,
		);

		if ( ! empty( $api_token ) ) {
			$args['api_token'] = $api_token;
		}

		return mm_api( $args );
	}

	public function delete_user_website( $postdata, $delete_groups = false ) {

		$domain = mm_get_domain( $postdata['domain'] );
		$website_details   = $this->get_website_details( $domain );
		if ( $delete_groups && $website_details) {
			$group['group_id'] = $website_details[0]['manual_detection_group_id'];
			$this->delete_group( $group['group_id'] );

			$group['group_id'] = $website_details[0]['auto_detection_group_id'];
			$this->delete_group( $group['group_id'] );
		}
        if($website_details) {
	        $args = array(
		        'action' => 'delete_website',
		        'domain' => $domain,
	        );
	        return mm_api( $args );
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
	public function get_group_and_urls_v2( $group_uuid, $url_filter = array() ) {

		$group_and_urls = Wp_Compare_API_V2::get_group_v2( $group_uuid )['data'];
		$urls           = Wp_Compare_API_V2::get_group_urls_v2( $group_uuid, $url_filter );

		if ( empty( $urls['data'] ) ) {
			// $this->sync_posts( true );
			// $urls = Wp_Compare_API_V2::get_group_urls_v2( $group_uuid, $url_filter );
		}

		$group_and_urls['urls']                = $urls['data'];
		$group_and_urls['meta']                = $urls['meta'];
		$group_and_urls['selected_urls_count'] = $urls['meta']['selected_urls_count'];

		//dd($group_and_urls);
		return $group_and_urls;
	}

	// public function get_user_groups_and_urls( $cms = null, $group_type = 'all', $group_id = false, $limit_groups = false, $offset_groups = 0, $limit_urls = false, $offset_urls = 0 ) {
	// 	$args = array(
	// 		'action'        => 'get_user_groups_and_urls',
	// 		'group_type'    => $group_type,
	// 		'offset_groups' => $offset_groups,
	// 	);

	// 	if ( $group_id ) {
	// 		$args['group_id'] = $group_id;
	// 	}

	// 	if ( $limit_groups ) {
	// 		$args['limit_groups'] = $limit_groups;
	// 	}

	// 	if ( $limit_urls ) {
	// 		$args['limit_urls'] = $limit_urls;
	// 	}
	// 	if ( $offset_urls ) {
	// 		$args['offset_urls'] = $offset_urls;
	// 	}

	// 	if ( ! is_null( $cms ) ) { // TODO validate this!
	// 		$args['cms'] = strtolower( $cms );
	// 	}

	// 	$group_and_urls = mm_api( $args );

	// 	foreach ( $group_and_urls as $group_key => $group ) {
	// 		$selected_urls   = array();
	// 		$unselected_urls = array();
	// 		foreach ( $group['urls'] as $url_key => $url ) {
	// 			if ( isset( $url['pivot']['desktop'] ) && isset( $url['pivot']['mobile'] ) ) {
	// 				if ( $url['pivot']['desktop'] || $url['pivot']['mobile'] ) {
	// 					$selected_urls[] = $url;
	// 				} else {
	// 					$unselected_urls[] = $url;
	// 				}
	// 			}
	// 		}
	// 		$group_and_urls[ $group_key ]['urls'] = array_merge( $selected_urls, $unselected_urls );
	// 	}

	// 	return $group_and_urls;
	// }

	public function get_url_settings( $group ) {
		if ( ! empty( $group['urls'] ) || !empty($_POST['search'])) {

			// Check top checkbox if ALL checkboxes are checked
			$checkedAllDesktop = array_reduce(
				$group['urls'],
				function ( $carry, $url ) {
					return $carry += $url['desktop'];
				}
			) === count( $group['urls'] ) ? 'checked' : '';
			$checkedAllMobile  = array_reduce(
				$group['urls'],
				function ( $carry, $url ) {
					return $carry += $url['mobile'];
				}
			) === count( $group['urls'] ) ? 'checked' : '';

		        // Search for urls.
                ?>
            <div class="responsive-table">
                <form class="ajax search-url-table" >
                    <input type="hidden" name="action" value="load_group_urls">
                    <input type="hidden" name="group_id" value="<?= $group['id'] ?>">
                    <input type="hidden" name="action" value="load_group_urls">
                    <input name="search" type="text" placeholder="Search" value="<?= $_POST['search'] ?? '' ?>">
                </form>
                <table id="table_group_<?= $group['id']; ?>">
			<?php
			if ( count( $group['urls'] ) >= 1 ) { ?>
				<div class="enabled_switch devices" >
                        <label class="switch">
                            <input type="checkbox"
                                <?= $checkedAllDesktop ?>
                                class="ajax-select-all"
                                data-device="desktop"
                                data-group_id="<?= $group['id'] ?>"
                                id="select-desktop-<?= $group['id'] ?>" />
                            <span class="slider round" style="font-size: 12px; line-height: 12px; text-align: center;">
                                All<br>Desktop
                            </span>
                         </label>
                    </div>
                    <div class="enabled_switch devices" >
                        <label class="switch">
                            <input type="checkbox"
                                <?= $checkedAllMobile ?>
					            class="ajax-select-all"
                                data-device="mobile"
                                data-group_id="<?= $group['id'] ?>"
                                id="select-mobile-<?= $group['id'] ?>" />
                            <span class="slider round" style="font-size: 12px; line-height: 12px;  text-align: center;">
                                All<br>Mobile
                            </span>
                        </label>
                    </div>
					<div class="clear"></div>
                <?php
			}


			// Get the urls to show. We have this in a separate function as it is called with ajax for load more urls
			echo $this->get_url_view( $group );


			?>
			</table>
			</div>

			<div class='pagination_container' style='margin-top: 30px;'>
            <?php
			foreach ($group['meta']['links'] as $link) {
				if (!empty($link['active'])) {
					$activeLabel = $link['label'];
					break;
				}
			}

			foreach ( $group['meta']['links'] as $link ) {
				// Parse the URL to get the query part
				$parsedUrl = parse_url($link['url'], PHP_URL_QUERY);

                // Parse the query string into an array
				parse_str($parsedUrl, $queryParams);

                // Get the 'page' parameter
				$page = isset($queryParams['page']) ? $queryParams['page'] : null;

				?>
					<button class="ajax_paginate_urls et_pb_button"
							style="padding-left: 15px !important; padding-right: 15px !important;"
							data-group_id="<?php echo $group['id']; ?>"
							data-page="<?php echo $page; ?>"
							<?php echo $link['active'] || is_null($page) ? ' disabled' : ''; ?>
							onclick="return false;"
					>
						<?php echo $link['label']; ?>
					</button>
				<?php

			}
            echo "Total: " . $group['meta']['total'] . " items";
			echo '</div>';
		} else {
			?>
			<div style="text-align: center; display: block;margin-top: 50px; margin-bottom: 50px">
				<p class="add-url">Add Webpage</p>
				<div class="ajax"
					data-group_id="<?php echo $group['id']; ?>"
					onclick="showAssignGroupUrlPopup('<?php echo $group['id']; ?>');">

					<?php echo get_device_icon( 'add-url', 'icon-big' ); ?>
				</div>
			</div>
			<?php
		}
	}

	public function get_loading_icon() {
		return plugin_dir_url( __FILE__ ) . './../public/img/loader.gif';
	}

	public function get_small_loading_icon() {
		return plugin_dir_url( __FILE__ ) . './../public/img/loading.gif';
	}

	public function timeAgo( $date ) {
		$timestamp = strtotime( $date );

		$strTime = array( 'second', 'minute', 'hour', 'day', 'month', 'year' );
		$length  = array( '60', '60', '24', '30', '12', '10' );

		$currentTime = time();
		if ( $currentTime >= $timestamp ) {
			$diff = time() - $timestamp;
			for ( $i = 0; $diff >= $length[ $i ] && $i < count( $length ) - 1; $i++ ) {
				$diff = $diff / $length[ $i ];
			}

			$diff = round( $diff );
			return $diff . ' ' . $strTime[ $i ] . '(s) ago ';
		}
	}

	public function get_url_view( $group ) {
		$output            = '';

        /* Disabled as this request just take way to long
        $comp_args = [
                'group_id' => $group['id'],
                'from' => date("Y-m-d", strtotime("-30 days")),
                'to' => date("Y-m-d", strtotime("+1 day")),
                'above_threshold' => true,
                'per_page' => 1000
                ];
        $comparisons = Wp_Compare_API_V2::get_comparisons_v2($comp_args)['data'] ?? null;
*/
        if(count($group['urls']) <= 0 ) {
            echo "<h2 style='text-align: center;'>No URLs to show</h2>";
            if(!empty($_POST['search'])) { ?>
                <p style="text-align: center;">
                    <button class="et_pb_button"
                            onclick='reset_search(); return false;'>
                        Reset search
                    </button>
                </p>
                <script>
                    function reset_search() {
                        let searchForm = jQuery("#ajax-group-urls_<?= $group['id'] ?>").find("form.search-url-table");
                        jQuery(searchForm).find("input[name='search']").val('');
                        searchForm.submit()
                    }
                </script>
            <?php }
        }

		foreach ( $group['urls'] as $key => $url_details ) {
			$timeAgoDefaultText      = 'None in last 30 days';
			$timeLastChangeDetection = $timeAgoDefaultText;
            $compare_token = false;

			/* Disabled for performance reasons.
			 foreach ( $comparisons as $compare ) {
				if ( $compare['url_id'] == $url_details['id'] && $timeLastChangeDetection == $timeAgoDefaultText ) {
					$compare_token           = $compare['token'];
					$timeLastChangeDetection = strtotime( $compare['screenshot_2_updated_at'] );
				}
			}*/

			// Check Desktop
			if ( isset( $url_details['desktop'] ) && $url_details['desktop'] == 1 ) {
				$checked_desktop = 'checked';
			} else {
				$checked_desktop = '';
			}

			// Check Mobile
			if ( isset( $url_details['mobile'] ) && $url_details['mobile'] == 1 ) {
				$checked_mobile = 'checked';
			} else {
				$checked_mobile = '';
			}

			$output .= '<tr data-url_id="' . $url_details['id'] . '" data-group_id="' . $group['id'] . '" class="post_id_' . $group['id'] . ' live-filter-row" id="' . $group['id'] . '-' . $url_details['id'] . '">';
			$output .= '<input type="hidden"  type="checkbox" name="pid-' . $url_details['id'] . '" value="' . $url_details['id'] . '">';

			// Monitoring animation
			if ( $group['monitoring'] ) {
				$output .= '<td style="text-align: center; width: 120px; order: 2" class="url-monitoring-status-container">
                                <div class="animation-enabled"></div>
                                <div class="monitoring-status" style="line-height: 1.0em;"></div>
                            </td>';
			}

			// Switch desktop
			$output .= '<td style="width: 180px; order: 3;" ' . ( $group['monitoring'] ? 'class="url-monitoring-status-container "' : '' ) . '>
                        <div class="checkbox-desktop-' . $group['id'] . '"  style="text-align: center;display:block;">
                           <div class="enabled_switch devices" >
                                <label class="switch">
                                    <input type="hidden" value="0" name="desktop-' . $url_details['id'] . '">
                                    <input type="checkbox"
                                    name="desktop-' . $url_details['id'] . '"
                                    value="1" ' . $checked_desktop . '
                                    id="desktop-' . $group['id'] . '-' . $url_details['id'] . '"
                                    class="group-url-checkbox"
                                    data-device="desktop"
                                    data-group_id="' . $group['id'] . '"
                                    data-url_id="' . $url_details['id'] . '"
                                    onclick="mmMarkRows(\'' . $group['id'] . '%' . $url_details['id'] . '\')">
                                    
                                    <span class="slider round">Desktop</span>
                                </label>
                            </div>
                        </div>';

			// Switch mobile
			$output .= '<div class="checkbox-mobile-' . $group['id'] . '"  style="text-align: center;">
                            <div class="enabled_switch devices" >
                                <label class="switch">
                                    <input type="hidden" value="0" name="mobile-' . $url_details['id'] . '">
                                    <input type="checkbox"
                                    name="mobile-' . $url_details['id'] . '"
                                    value="1" ' . $checked_mobile . '
                                    class="group-url-checkbox"
                                    data-device="mobile"
                                    data-group_id="' . $group['id'] . '"
                                    data-url_id="' . $url_details['id'] . '"
                                    id="mobile-' . $group['id'] . '-' . $url_details['id'] . '"
                                    onclick="mmMarkRows(\'' . $group['id'] . '%' . $url_details['id'] . '\')">
                            
                                    <span class="slider round">Mobile</span>
                                </label>
                            </div>
                        </div>
						</td>';
			// $output .= '<td class="hide-mobile" style="text-align: center;">' . $thumbnail . '</td>';
			$output .= '<input type="hidden" value="' . $url_details['url'] . '" name="url-' . $url_details['id'] . '">';

			// URL and html title
			$output .= '<td class="table-row-url" style="order: 1">
						<div style="font-weight: 400; font-size: 12px; margin-top: 10px;"> ' . ucfirst( $url_details['category'] ) . '</div>
                        <div class="html-title">' . $url_details['html_title'] . '</div>
                        <!-- Disabled because of too many requests-->
                        <!--<img src="https://www.google.com/s2/favicons?sz=18&domain_url=' . $url_details['url'] . '" style="vertical-align: middle;">-->   
                        <a class="url" href="http://' . $url_details['url'] . '" target="_blank">' . $url_details['url'] . '</a>';
			$output .= '</td>';

			// Latest Change Detections - Disabled for performance reasons.
			/*$output .= '<td style="width: 300px; text-align: center;"><strong>Latest Change Detection</strong><br><span class="time-ago-last-change-detection" data-time_ago="' . $timeLastChangeDetection . '"></span>';
            //$output .= '<td></td>';
			if ( ! empty( $compare_token ) ) {
				$output .= '<br><button onclick="ajaxShowChangeDetectionPopup(\'' . $compare_token . '\')"
                        class="et_pb_button">
                    Show
                </button></td>';
			}*/
			$output .= '<td style="width: 220px; order: 4">';
            /*<a class="et_pb_button show-change-detection" style="text-align: center; width: 100%;" onclick="showChangeDetectionOverviewPopup( \'' . $group['id'] . '\' , \'' . $url_details['id'] . '\' , \'' . $url_details['url'] . '\')">
                ' . get_device_icon( 'change-detections', 'row-icon' ) . ' Change Detections 
            </a>';*/

			$output .= '<a style="text-align: center; width: 100%; margin-top: 10px;" onclick="showCssPopup(\'' . $group['id'] . '\',\'' . $url_details['id'] . '\')" class="edit-css-button et_pb_button">';
			$output .= get_device_icon( 'settings', 'row-icon' ) . 'Webpage Settings';
			$output .= '</a>

                <div id="show_css_popup-' . $group['id'] . '-' . $url_details['id'] . '" class="ajax-popup" style="display: none;">
                    <div class="popup">
                        <div class="popup-inner">
                            <h2>Webpage Settings</h2>
                            <button onclick="return closeCssPopup(\'' . $group['id'] . '\', \'' . $url_details['id'] . '\')" class="et_pb_button close-popup-button">X<small>ESC</small></button>

                            <form class="ajax-edit-css" method="post" onsubmit="return false">
                                <input type="hidden" name="action" value="update_url">
                                <input type="hidden" name="url_id" value="' . $url_details['id'] . '">
                                <input type="hidden" name="group_id" value="' . $group['id'] . '">
                                <div class="form-container">
                                <div class="form-row bg">
                                
                                <label>Title (We update this one for you)</label>
                                <span id="show_css_popup_html_title" type="text" name="html_title" data-title="' . $url_details['html_title'] . '" value="' . $url_details['html_title'] . '" disabled>' . $url_details['html_title'] . '</span><br>
                                </div>
                                <div class="form-row">
                                <label>Webpage</label>
                                <input id="show_css_popup_url" type="text" name="url" data-url="' . $url_details['url'] . '" value="' . $url_details['url'] . '"><br>';

			$output .= '</div>
                            <div class="form-row bg">
                                <label for="threshold" >
                                    Threshold for Change Detections<br>
                                    <input type="number" name="threshold" step="0.1" placeholder="' . $group['threshold'] . '" value="' . $url_details['threshold'] . '" style="width: 50px; max-width: 80px; min-width: 80px;"> %
                                </label>
                                </div>
                                </div>
                                
                                <div class="simple-accordion" style="margin-top: 30px;"> 
									<div class="simple-accordion-title" onclick="mm_show_more_link(\'show_css_popup-' . $group['id'] . '-' . $url_details['id'] . '\');">
										<span class="text-simple-accordion">
											Advanced settings
										</span>
									</div>
                                    <div class="show-more" style="display: none;">
                                            <h3>CSS Injection</h3>
                                            <div class="code-tags">&lt;style&gt;</div>
                                            <textarea
                                            name="css"
                                            style="width: 50%; height: 250px;"
                                            class="codearea css"
                                            >' . stripcslashes( $url_details['css'] ?? '' ) . '</textarea>
                                            <div class="code-tags">&lt;/style&gt;</div>
                                        
                                            <h3>JS Injection</h3>                                          
                                            <div class="code-tags default-bg">&lt;script&gt;</div>
                                            <textarea 
                                            name="js" 
                                            class="codearea js" 
                                            style="width: 100%; height: 250px;"
                                            >' . stripcslashes( $url_details['js'] ?? '' ) . '</textarea>
                                            <div class="code-tags default-bg">&lt;/script&gt;</div>
                                       
                                        <div class="clear"></div>
                                    </div>
                                </div>
                                <br>
                                <input type="submit" class="et_pb_button" value="Save">
                                
                                <button class="ajax_unassign_url et_pb_button delete_button delete_url_button"
                                    data-url_id="' . $url_details['id'] . '"
                                    data-group_id="' . $group['id'] . '"
                                    data-url="' . $url_details['url'] . '"
                                    >
                                    Delete webpage
                                </button>
                            </form>
                        </div>
                    </div>
                </div>';
			$output .= '</td>';

			// Show delete button only for non-wp-website groups
			$disable_delete_button = '';
			if ( $group['cms'] ?? false ) {
				$disable_delete_button = 'disabled="disabled"';
			}

			$output .= '</tr>';
			$output .= '<script>
                jQuery(".simple-accordion .show-more").hide();
                mmMarkRows(\'' . $group['id'] . '-' . $url_details['id'] . '\'); 

                jQuery(".time-ago-last-change-detection").each(function(i,e) {
                     let localTime = jQuery(e).data("time_ago");
                    if(isNaN(localTime)) {
                        jQuery(e).html(localTime);
                    } else {
                        jQuery(e).html(time_ago(localTime * 1000));
                    }
                });
                
                </script>';
		}
		return $output;
	}

	public function get_monitoring_settings( array $group, $cancel_button = false, $monitoring_group = 0, $cms = null ) {
		if ( ! ( $group['id'] ) ) {
			$group['id']      = 0;
			$group['cms']     = $cms;
			$group['enabled'] = 1;
			$group['name']    = '';
		}

		if ( $cancel_button ) {
			?>
		<form action="" method="post">
			<input type="hidden" name="action" value="save_group_settings">
			<input type="hidden" name="group_id" value="<?php echo $group['id']; ?>">
			<input type="hidden" name="cms" value="<?php echo $cms ?? $group['cms']; ?>">

		<?php } ?>
		<div class="form-container">
			<input type="hidden" name="monitoring" value="<?php echo $monitoring_group; ?>">
			<input type="hidden" name="enabled" id="enabled<?php echo $group['uuid'] ?? 1; ?>" value="<?php echo isset( $group['enabled'] ) ? $group['enabled'] : '1'; ?>">

			<?php
			// if(!$cms || $group['id']) {
			$group_name_id = '';
			if ( $group['id'] === 0 && $group['cms'] ) {
				$group_name_id = 'ajax-fill-group-name';
			}
			?>

			<div class="form-row bg">
				<label for="name">Group name</label>
				<input id="<?php echo $group_name_id; ?>" type="text" name="group_name" value="<?php echo $group['name'] ?? $_POST['domain'] ?? ''; ?>" width="100%">
			</div>

			<?php if ( $monitoring_group ) { ?>
				<div id="monitoring-settings-<?php echo $group['id']; ?>" class="monitoring-settings">

					<div class="form-row">
						<?php
						$account_details                    = $this->get_account_details_v2();
						$exclude_plans_for_minute_intervals = array(
                            'trial',
                            'free',
                            'personal',
                            'personal_pro',

						);
						$show_minute_intervals              = false;
						if ( ! in_array( $account_details['plan'], $exclude_plans_for_minute_intervals ) ) {
							$show_minute_intervals = true;
						}
						?>
					<label for="interval_in_h">Monitoring Interval</label>
						<select class="interval_in_h" name="interval_in_h">
							<option value="0.25"
								<?php echo ! $show_minute_intervals ? 'disabled ' : ''; ?>
								<?php echo isset( $group['interval_in_h'] ) && $group['interval_in_h'] == '0.25' ? 'selected' : ''; ?>
								<?php echo ! isset( $group['interval_in_h'] ) ? 'selected' : ''; ?>>
								Every 15 minutes <?php echo ! $show_minute_intervals ? '("Freelancer" plan or higher)' : ''; ?>
							</option>
							<option value="0.5"
								<?php echo ! $show_minute_intervals ? 'disabled ' : ''; ?>
								<?php echo isset( $group['interval_in_h'] ) && $group['interval_in_h'] == '0.5' ? 'selected' : ''; ?>
								<?php echo ! isset( $group['interval_in_h'] ) ? 'selected' : ''; ?>>
								Every 30 minutes <?php echo ! $show_minute_intervals ? '("Freelancer" plan or higher)' : ''; ?>
							</option>
							<option value="1"
								<?php echo isset( $group['interval_in_h'] ) && $group['interval_in_h'] == '1' ? 'selected' : ''; ?>>
								Every 1 hour
							</option>
							<option value="3"
								<?php echo isset( $group['interval_in_h'] ) && $group['interval_in_h'] == '3' ? 'selected' : ''; ?>>
								Every 3 hours
							</option>
							<option value="6"
								<?php echo isset( $group['interval_in_h'] ) && $group['interval_in_h'] == '6' ? 'selected' : ''; ?>>
								Every 6 hours
							</option>
							<option value="12"
								<?php echo isset( $group['interval_in_h'] ) && $group['interval_in_h'] == '12' ? 'selected' : ''; ?>>
								Every 12 hours
							</option>
							<option value="24"
								<?php echo isset( $group['interval_in_h'] ) && $group['interval_in_h'] == '24' ? 'selected' : ''; ?>
								<?php echo ! isset( $group['interval_in_h'] ) ? 'selected' : ''; ?>>
								Every 24 hours
							</option>
						</select>
						
						<div id="wcd-monitoring-group-time-settings">
						from
							<select name="hour_of_day">
								<?php
								for ( $i = 0; $i < HOURS_IN_DAY; $i++ ) {
									if ( isset( $group['hour_of_day'] ) && $group['hour_of_day'] == $i ) {
										$selected = 'selected';
									} elseif ( ! isset( $group['hour_of_day'] ) && gmdate( 'H' ) == $i ) {
										$selected = 'selected';
									} else {
										$selected = '';
									}
									echo '<option class="select-time" value="' . $i . '" ' . $selected . '></option>';
								}
								?>
							</select>
							o'clock
						</div>
					</div>

					<div class="form-row bg">
						<label for="alert_emails">Email addresses for alerts (comma separated for multiple email addresses)</label>
						<input type="text" name="alert_emails"
                            <?php
                            echo "value='";
                            echo ! empty( $group['alert_emails'] ) ?
                                $group['alert_emails']:
                                $this->get_account_details_v2()['email'];
                            echo "'";
                            ?>
                            >
					</div>
				</div>

			<?php } ?>
			<div class="form-row">
				<label for="name">Threshold for difference in change detections</label>
				<input class="threshold" type="number" step='0.1' name="threshold" value="<?php echo $group['threshold'] ?? '0.00'; ?>" min="0" max="100"> %
			</div>
		</div>
		<?php

		if ( $cancel_button ) {
			?>
			<p>
				<input class="et_pb_button" type="submit" value="Save group settings">
				<button class="et_pb_button" onclick="return closeAddGroupPopup()">Cancel</button>
			</p>
			</form>
			<?php
		}
	}

	public function add_website( $postdata ) {
		$args = array(
			'action' => 'add-website-groups',
			'domain' => $postdata['domain'],
		);

		$result = mm_api( $args );
	}

	public function textfield_to_array( $urls ) {
		$urls    = trim( $urls );
		$urls    = str_replace( array( ' ', '\\' ), "\n", $urls );
		$urls    = str_replace( "\r", '', $urls );
		$urls    = explode( "\n", $urls );
		$url_arr = array();
		foreach ( $urls as $url ) {
			if ( empty( $url ) ) {
				continue;
			}
			$url_arr[] = trim( $url );
		}
		return $url_arr;
	}

	public function check_url( $urls ) {
		// init
		$urls = $this->textfield_to_array( $urls );

		/*
		if(is_json($urls)) {
			$urls = json_decode($urls);
		} else {
			$urls = [$urls];
		}
		if (strpos($url, ',')) {
			$urls = explode(',', $url);
			// TODO this is only testing the first URL
			$url = $urls[0];
		}*/

		// Check if http url exists.
		// Most likely we get a 301 answer (redirect to https). But then we get the final location and can check headers again.
		$return_urls = array();

		foreach ( $urls as $url ) {
			//$response = wp_remote_get($url);

			// Remove protocol if exists for proper testing
			/*
			if (strpos($url, 'http') === 0) {
				$url = substr($url, strpos($url, '//') + strlen('//'));
			}*/
            if( urlExists($url)) {
	            $return_urls[] = $url;
            }
            /*
			$headers = mm_get_headers( $url );

			// Only checking for 404 errors as many websites didn't work with the entire logic
			if ( $headers && false === strpos( $headers[0], '404' ) ) {
				$return_urls[] = $url;
			}
			continue;

			if ( strpos( $headers[0], strval( HTTP_OK ) ) ) {
				$return_urls[] = $url;
				continue;
			}

			// If the url is redirected, we check again. If there are more redirects, we take the url of the final redirect.
			if ( strpos( $headers[0], strval( HTTP_MOVED_PERMANENTLY ) ) || strpos( $headers[0], strval( HTTP_FOUND ) ) ) {
				$headers = array_change_key_case( $headers, CASE_LOWER );

				if ( is_array( $headers['location'] ) ) {
					$array_id_last_redirect = max( array_keys( $headers['location'] ) );
					$redirect_url           = $headers['location'][ $array_id_last_redirect ];
				} else {
					$redirect_url = $headers['location'];
				}

				// Second check of the url to make sure we get http code 200 back.
				$headers = mm_get_headers( $redirect_url );
				if ( strpos( $headers[0], strval( HTTP_OK ) ) ) {
					// Remove the protocol and return the url instead of true or false to double check in frontend if it is correct.
					$redirect_url = substr( $redirect_url, strpos( $redirect_url, 'http' ) );
					$redirect_url = substr( $redirect_url, strpos( $redirect_url, '//' ) + strlen( '//' ) );

					// If the redirect was only for trailing slash, we return true. We don't want to nag the client for that.

					$return_urls[] = $redirect_url;
				} else {
					return false;
				}
			} else {
				return false;
			}*/
		}

		return $return_urls;
	}

	public function is_website_https( $url ) {

		static $scheme;

		if ( isset( $scheme ) ) {
			return $scheme;
		}

		$url      = str_replace( array( 'http://', 'https://' ), '', $url );
		$response = wp_remote_get( 'https://' . $url );
		$status   = wp_remote_retrieve_response_code( $response );

		if ( $status === 200 ) {
			$scheme = true;
		} else {
			$scheme = false;
		}
		return $scheme;
	}

	public function get_wp_post_types( $domain ) {
		error_log('Starting get_wp_post_types');
		$scheme = $this->is_website_https( $domain ) ? 'https://' : 'http://';
		error_log('Scheme: ' . $scheme);
		// Check for WPML & if api is reachable
		$response = wp_remote_get( $scheme . $domain . '/wp-json/wp/v2/' );
		error_log('Response wp/v2: ' . print_r($response, true));
		$status   = wp_remote_retrieve_response_code( $response );
		if ( $status !== 200 ) {
            return 'We couldn\'t reach the WP Api. Please make sure it is enabled on the WP website';;
		}
		$body       = wp_remote_retrieve_body( $response );
		$api_routes = json_decode( $body, true );

		$return = array(); // init

		// Get Post Types
		$response     = wp_remote_get( $scheme . $domain . '/wp-json/wp/v2/types' );
		//error_log('Response types: ' . print_r($response, true));
		$status_types = wp_remote_retrieve_response_code( $response );
		if ( $status_types !== 200 ) {
			return 'We couldn\'t reach the WP Api. Please make sure it is enabled on the WP website';
		}
		$body       = wp_remote_retrieve_body( $response );
		$post_types = json_decode( $body, true );

		// Get Taxonomies
		$response          = wp_remote_get( $scheme . $domain . '/wp-json/wp/v2/taxonomies' );
		error_log('Response taxonomies: ' . print_r($response, true));
		$status_taxonomies = wp_remote_retrieve_response_code( $response );
		if ( $status_taxonomies !== 200 ) {
			return 'We couldn\'t reach the WP Api. Please make sure it is enabled on the WP website';
		}
		$body       = wp_remote_retrieve_body( $response );
		$taxonomies = json_decode( $body, true );

		$return_post_types = array();
		$return_taxonomies = array();

		// Prepare return post_types
		foreach ( $post_types as $post_type ) {
            // $response = wp_remote_get( $scheme . $domain . '/wp-json/wp/v2/' . $post_type['rest_base'] );
			// error_log('Response post_type' . $post_type['rest_base'] . ': ' . print_r($response, true));
			// if(200 !== wp_remote_retrieve_response_code( $response )) {
            //     continue;
			// };

			$return_post_types[] = array(
				'name' => $post_type['name'],
				'slug' => $post_type['rest_base'],
			);
		}

		// Prepare return taxonomies
		foreach ( $taxonomies as $taxonomy ) {
			// $response = wp_remote_get( $scheme . $domain . '/wp-json/wp/v2/' . $taxonomy['rest_base'] );
			// error_log('Response taxonomy' . $taxonomy['rest_base'] . ': ' . print_r($response, true));
			// if(200 !== wp_remote_retrieve_response_code( $response )) {
			// 	continue;
			// };

			$return_taxonomies[] = array(
				'name' => $taxonomy['name'],
				'slug' => $taxonomy['rest_base'],
			);
		}

		// Get it together
		$return[] = array(
			'url_type_slug' => 'types',
			'url_type_name' => 'Post Types',
			'url_types'     => $return_post_types,
		);

		$return[] = array(
			'url_type_slug' => 'taxonomies',
			'url_type_name' => 'Taxonomies',
			'url_types'     => $return_taxonomies,
		);

		// Check for WPML languages
		$wpml_language_codes = array();
		if ( ! empty( $api_routes['routes']['/wp/v2']['endpoints'][0]['args']['wpml_language']['enum'] ) ) {
			$wpml_language_codes = $api_routes['routes']['/wp/v2']['endpoints'][0]['args']['wpml_language']['enum'];
		}

		// Prepare languages
		if ( $wpml_language_codes ) {
			foreach ( $wpml_language_codes as $wpml_language_code ) {
				$return_wpml_languages[] = array(
					'name' => strtoupper( $wpml_language_code ),
					'slug' => $wpml_language_code,
				);
			}

			if ( ! empty( $return_wpml_languages ) ) {
				$return[] = array(
					'url_type_slug' => 'wpml_language',
					'url_type_name' => 'Languages',
					'url_types'     => $return_wpml_languages,
				);
			}
		}
		return $return;
	}

	public function log( $log ) {
		if ( is_array( $log ) || is_object($log) ) {
			$log = json_encode( $log );
		}
		file_put_contents( wcd_get_plugin_dir() . '/logs.txt', $log . PHP_EOL, FILE_APPEND );
	}

	public function get_wp_urls( $domain, $url_types = false ) {

		if ( ! $domain ) {
			return 'domain invalid';
		}

		$scheme = $this->is_website_https( $domain ) ? 'https://' : 'http://';

		if ( ! $url_types ) {
			$url_types = array(
				array(
					'url_type_slug'  => 'types',
					'url_type_name'  => 'Post Types',
					'post_type_slug' => 'pages',
					'post_type_name' => 'Pages',
				),
				array(
					'url_type_slug'  => 'types',
					'url_type_name'  => 'Post Types',
					'post_type_slug' => 'posts',
					'post_type_name' => 'Posts',
				),
			);
		}

		// Check if we have different languages with wpml
		$languages = array();
		foreach ( $url_types as $key => $url_type ) {
			if ( $url_type['url_type_slug'] === 'wpml_language' ) {
				$languages[] = $url_type['post_type_slug'];
				unset( $url_types[ $key ] );
			}
		}
		if ( empty( $languages ) ) {
			$languages = array( false );
		}

		$urls             = array();
		$frontpage_has_id = false;

		// Loop for every language
		foreach ( $languages as $language ) {
			// Loop for url_types like post_type or taxonomy
			foreach ( $url_types as $url_type ) {
				$pages_added = 0;
				$offset      = 0;

				// Loop through the post_types / taxonomies
				switch ( $url_type['url_type_slug'] ) {
					case 'taxonomies':
						$is_taxonomie = true;
						$args         = array(
							'per_page' => '100',
							'_fields'  => 'id,link,name',
							'order'    => 'asc',
						);
						break;

					default:
						$is_taxonomie = false;
						$args         = array(
							'per_page' => '100',
							'_fields'  => 'id,link,title',
							'orderby'  => 'parent',
							'order'    => 'asc',
						);
				}
				// add wmpl language to the args
				if ( $language ) {
					$args['wpml_language'] = $language;
				}

				do {
					$args['offset'] = $offset;
					$response       = wp_remote_get( $scheme . $domain . '/wp-json/wp/v2/' . $url_type['post_type_slug'] . '/?' . http_build_query( $args ) );
					$status_code    = wp_remote_retrieve_response_code( $response );
					$type_urls      = wp_remote_retrieve_body( $response );
					$type_urls      = json_decode( $type_urls );

					if ( $status_code === 200 ) {
						foreach ( $type_urls as $type_url ) {
							$clean_link = str_replace( array( 'http://', 'https://' ), '', $type_url->link );

							// Old logic
							/*
							$urls[] = [
								'cms_resource_id' => $type_url->id,
								'url' => $clean_link,
								'html_title' => $is_taxonomie ? $type_url->name : $type_url->title->rendered ,
								'url_type' => $url_type['url_type_slug'],
								'url_category' => $url_type['post_type_name'],
							];*/

							$chunk_key = (int) ( $pages_added / 1000 );

							$urls[ $chunk_key ][ $url_type['url_type_slug'] . '%%' . $url_type['post_type_name'] ][] = array(
								'url'        => $clean_link,
								'html_title' => $is_taxonomie ? $type_url->name : $type_url->title->rendered,
							);

							if ( in_array( $clean_link, array( $domain, $domain . '/', 'www.' . $domain, 'www.' . $domain . '/' ) ) ) {
								$frontpage_has_id = true;
							}
						}
					}

					if ( is_iterable( $type_urls ) ) {
						$pages_added += count( $type_urls ) ?? 0;
					}

					$offset += 100;
				} while ( $pages_added == $offset );
			}
		}

		if ( ! $frontpage_has_id && count( $urls ) ) {
			$urls[]['frontpage%%Frontpage'][] = array(
				'url'        => str_replace( array( 'http://', 'https://' ), '', $domain . '/' ),
				'html_title' => 'Home',
			);
		}

		if ( count( $urls ) ) {
			/*
			usort($urls, function($a, $b) {
				return $a['url'] <=> $b['url'];
			});*/
			return $urls;
		}
		return false;
	}

	public function get_title( $url ) {
		// not working(?!)
		$str = file_get_contents( $url );
		if ( strlen( $str ) > 0 ) {
			$str = trim( preg_replace( '/\s+/', ' ', $str ) ); // supports line breaks inside <title>
			preg_match( '/\<title\>(.*)\<\/title\>/i', $str, $title ); // ignore case
			return $title[1];
		}
		return false;
	}

	public function get_selected_wp_url_types( $group_id ) {
		$website = $this->get_website_by_group_id( $group_id );
		if ( empty( $website['sync_url_types'] ) ) {
			return false;
		}
		return ( $website['sync_url_types'] );
	}

	public function update_comparison_status( $postdata ) {
		$response = Wp_Compare_API_V2::update_comparison_v2( $postdata['comparison_id'], $postdata['status'] );
		if ( ! $response ) {
			return false;
		}
		$output['currentComparison'] = prettyPrintComparisonStatus( $postdata['status'], 'mm_inline_block' );

		$batchStatuses = $this->get_comparison_status_by_batch_id( $postdata );

		$printedStatuses = array();
		foreach ( $batchStatuses as $batchStatus ) {
			if ( ! in_array( $batchStatus, $printedStatuses ) ) {
				$output['batchStatuses'] .= prettyPrintComparisonStatus( $batchStatus, 'mm_inline_block mm_small_status' ) . '<br>';
				$printedStatuses[]        = $batchStatus;
			}
		}
        
		return json_encode( $output );
	}

	public function get_comparison_status_by_batch_id( $postdata ) {
		$args     = array(
			'action'   => 'get-compares-by-group-ids',
			'batch_id' => $postdata['batch_id'],
		);
		$response = mm_api( $args );
		$status   = array();
		foreach ( $response as $comparison ) {
			$status[] = $comparison['status'];
		}
		return ( $status );
	}

	public function get_comparison_status_by_token( $postdata ) {
		$comparison = $this->get_comparison_by_token( $postdata['token'] );
		return prettyPrintComparisonStatus( $comparison['status'], 'mm_inline_block' );
	}

	public function preview_screenshot( $postdata ) {

		$width = 1920;
		if ( ! empty( $postdata['device'] ) && $postdata['device'] == 'mobile' ) {
			$width = 375;
		}

		if ( strpos( $postdata['url'], 'http://' ) === 0 || strpos( $postdata['url'], 'https://' ) === 0 ) {
			$url = $postdata['url'];
		} else {
			$url = 'http://' . $postdata['url'];
		}

		$curl = curl_init();

		$params = array(
			'url'    => $url,
			'width'  => $width,
			'fastSc' => false, // currently not working in sc api
			'output' => true,
		);

		$screenshoter_url = 'https://us-east1-webchangedetector.cloudfunctions.net/wcd-sc-us-dev';
		if ( mm_env() == 'production' ) {
			$screenshoter_url = 'https://us-east1-webchangedetector.cloudfunctions.net/wcd-sc-us';
		} elseif (mm_env() == 'local') {
            $screenshoter_url = 'http://localhost:8081';
		}

		curl_setopt_array(
			$curl,
			array(
				CURLOPT_URL            => $screenshoter_url,
				CURLOPT_RETURNTRANSFER => true,
				CURLOPT_CUSTOMREQUEST  => 'POST',
                CURLOPT_FOLLOWLOCATION => true,
				CURLOPT_POSTFIELDS     => json_encode( $params ),
                CURLOPT_TIMEOUT => 120, //timeout in seconds
				CURLOPT_HTTPHEADER     => array(
					'Access-Control-Allow-Origin: *',
					'Content-Type: application/json',
				),
			)
		);

		$response = curl_exec( $curl );
		$status   = curl_getinfo( $curl, CURLINFO_HTTP_CODE );

		curl_close( $curl );

		if ( $status != 200 && $status != 201 ) {
			return json_encode( $response );
		} else {
            return json_decode($response,1)[0]['link_compressed'];
		}
	}


	public function take_screenshot( $postdata ) {
		$group_ids = explode( ',', $postdata['group_ids'] );

		$result = Wp_Compare_API_V2::take_screenshot_v2( $group_ids, $postdata['sc_type'] );

        if(!empty($result['batch'])) {
            update_user_meta(get_current_user_id(), 'wcd_manual_checks_batch', $result['batch']);
        }
        echo !empty($result['batch']) ? mm_message(['success','Taking screenshots']) : mm_message(['error','Sorry, something went wrong...']);
	}

	public function take_thumbnail( $postdata ) {
		$url_id = $postdata['url_id'];

		$args = array(
			'action' => 'take_thumbnail',
			'url_id' => $url_id,
		);
		echo mm_message( mm_api( $args ) );
	}

	public function get_url_html( $postdata ) {
		$url_id = $postdata['url_id'];

		$args = array(
			'action' => 'get_url_html',
			'url_id' => $url_id,
		);
		echo mm_message( mm_api( $args ) );
	}

	public function resend_verification_email() {
		$args = array(
			'action' => 'resend_verification_email',
		);

		// $api_token = mm_api_token();

		// if (! empty($api_token)) {
		$wp_user = wp_get_current_user();
		if ( ! empty( $wp_user ) && ! empty( $wp_user->user_email ) ) {
			$args['email'] = $wp_user->user_email;
		}
		// }
		mm_api( $args );
	}

	public function get_user_urls() {
		$args = array(
			'action' => 'get_user_urls',
			// this is technically correct but cms_resource_id is ignored anyway for this route
			// 'cms_resource_id' => null,
		);

		return mm_api( $args );
	}

	public function get_user_urls_view( $user_urls, $groups_and_urls ) {
		?>
		<div class="responsive-table">
			<table class="toggle">
				<tr>
					<th width="70%">URL</th>
					<th width="30%">Assigned in groups</th>
					<th>Edit</th>
					<th>Delete</th>
				</tr>
			<?php

			// Go through all urls of client
			if ( count( $user_urls ) === 0 ) {
				?>
				<td colspan="4" style="text-align: center;">
					<strong>There are no URLs yet. You can manage URLs here after adding to Update- or Monitoring groups.</strong>
				</td>
				<?php
			} else {
				foreach ( $user_urls as $user_url ) {
					$assigned_in_group = array();

					// Get the groups this url is assigned to
					if ( is_iterable( $groups_and_urls ) ) {
						foreach ( $groups_and_urls as $group_and_urls ) {
							if ( count( $group_and_urls['urls'] ) > 0 ) {
								foreach ( $group_and_urls['urls'] as $group_url ) {
									if ( $group_url['id'] == $user_url['id'] ) {
										$assigned_in_group[] = get_group_icon( $group_and_urls ) . $group_and_urls['name'];
									}
								}
							}
						}
					}
					?>
					<tr id="url-row-<?php echo $user_url['id']; ?>">
						<td><strong><?php echo $user_url['html_title']; ?></strong><br><?php echo $user_url['url']; ?></td>
						<td><?php echo ! empty( $assigned_in_group ) ? implode( '<br>', $assigned_in_group ) : ''; ?></td>
						<td>
							<a onclick="showAddUrlPopup(<?php echo $user_url['id']; ?>, '<?php echo $user_url['url']; ?>', '<?php echo $user_url['html_title']; ?>')"
								class="ajax_save_url "
								data-url_id="<?php echo $user_url['id']; ?>"
								data-url="<?php echo $user_url['url']; ?>">
								<?php echo get_device_icon( 'edit', 'row-icon' ); ?>
							</a>
						</td>
						<td>
							<a class="ajax_delete_url"
								data-url_id="<?php echo $user_url['id']; ?>"
								data-url="<?php echo $user_url['url']; ?>">
								<?php echo get_device_icon( 'remove', 'row-icon' ); ?>
							</a>
						</td>
					</tr>
					<?php
				}
			}
			?>
			</table>
		</div>
		<?php
	}

	public function get_no_account_page() {

		$current_user = wp_get_current_user();

		if ( ! get_user_meta( $current_user->ID, 'wcd_missing_api_token' ) ) {
			update_user_meta( $current_user->ID, 'wcd_missing_api_token', 1 );
			wp_mail(
				'mike@webchangedetector.com',
				'ALERT: Client API Token missing',
				'Create manually an Api Token for email ' . wp_get_current_user()->user_email
			);
		}

		return '<div style="margin: 100px auto; padding: 20px; font-size: 18px; max-width: 600px; text-align: center">
                    <p><strong>Ooops, it seems like the API token is missing in the WebApp. </strong></p>
                    <p>If you signed up via our plugin, please copy the api token from the settings and paste it here:</p>
                    <form class="ajax">
                        <input type="hidden" name="action" value="add_api_token">
                        <input style="width: 400px; font-size: 16px; padding: 10px;" placeholder="Paste api token here." type="text" name="api_token">
                        <input class="et_pb_button" type="submit" value="Save">
                    </form>
</form>
                    <p style="margin-top: 20px;">If you still have trouble, please contact us at <a href="mailto:support@webchangedetector.com">support@webchangedetector.com</a></p>';
	}

	public function get_dashboard_view( $client_account ) {
		$renewal_label = in_array($client_account['plan'], WCD_ONE_TIME_PLANS) ? 'expire' : 'renewal';
		$groups = Wp_Compare_API_V2::get_groups_v2(['monitoring' => true])['data'];
		$amount_auto_detection = 0;

		// TODO make this PHP 7.4 closure with fn() =>
		foreach ( array_filter(
			$groups,
			function ( $group ) {
				return $group['enabled'];
			}
		) as $group ) {
			$amount_auto_detection += HOURS_IN_DAY / $group['interval_in_h'] * $group['selected_urls_count'] * days_in_month();
		}
		?>

		<div class="wcd-modern-dashboard">
			<!-- Main Dashboard Grid -->
			<div class="wcd-dashboard-grid">
				<!-- Features Card -->
				<div class="wcd-card">
					<div class="wcd-card-header">
						<h2>
							<span class="dashicons dashicons-admin-tools" style="color: #266ECC;"></span>
							How to use WebChange Detector
						</h2>
					</div>
					<div class="wcd-card-content">
						<div class="wcd-features-list">
							<a href="?tab=auto-change-detection" class="wcd-feature-item">
								<div class="wcd-feature-title">
									<span class="<?php echo get_group_class(["monitoring"=>true]); ?>"></span>
									Monitoring Checks
								</div>
								<p class="wcd-feature-description">Get email alerts for automated visual webpage monitoring</p>
							</a>
							<a href="?tab=update-change-detection" class="wcd-feature-item">
								<div class="wcd-feature-title">
									<span class="<?php echo get_group_class(["monitoring"=>false]); ?>"></span>
									Manual Checks
								</div>
								<p class="wcd-feature-description">Compare websites, e.g., before and after updates</p>
							</a>
							<a href="?tab=change-detections" class="wcd-feature-item">
								<div class="wcd-feature-title">
									<?php echo get_device_icon('change-detections'); ?>
									Change Detections
								</div>
								<p class="wcd-feature-description">Review changes in both manual and monitoring checks</p>
							</a>
							<a href="?tab=website-settings" class="wcd-feature-item">
								<div class="wcd-feature-title">
									<?php echo get_device_icon('code'); ?>
									WP & Subaccounts
								</div>
								<p class="wcd-feature-description">Manage your WP websites and your sub-accounts</p>
							</a>
						</div>
					</div>
				</div>

				<!-- Account Card -->
				<div class="wcd-card">
					<div class="wcd-card-header">
						<h2>
							<span class="dashicons dashicons-admin-users" style="color: #266ECC;"></span>
							Your Account
						</h2>
					</div>
					<div class="wcd-card-content">
						<div class="wcd-account-section">
							<div class="wcd-account-item">
								<span class="wcd-account-label">Account Email</span>
								<span class="wcd-account-value"><?php echo esc_html($client_account['email'] ?? ''); ?></span>
							</div>

							<div class="wcd-account-item">
								<span class="wcd-account-label">Usage Overview</span>
								<span class="wcd-account-value"><?php echo esc_html(($client_account['checks_done'] . ' / ' . $client_account['checks_limit']) ?? ''); ?> checks</span>
								<div class="wcd-progress-container">
									<div class="wcd-progress-bar" style="width: <?php echo esc_attr(number_format($client_account['checks_done'] / $client_account['checks_limit'] * 100, 1)); ?>%;"></div>
									<span class="wcd-progress-text"><?php echo esc_html(number_format($client_account['checks_done'] / $client_account['checks_limit'] * 100, 1)); ?>%</span>
								</div>
							</div>

							<div class="wcd-account-item">
								<span class="wcd-account-label">Estimated Monthly Usage</span>
								<span class="wcd-account-value"><?php echo esc_html($amount_auto_detection); ?> monitoring checks</span>
							</div>

							<?php
							$until_renewal = $amount_auto_detection / MONTH_IN_SECONDS * ( gmdate( 'U', strtotime( $client_account['renewal_at'] ) ) - gmdate( 'U' ) );
							if ( $client_account['checks_limit'] - $client_account['checks_done'] < $until_renewal ) {
								$missing_checks = $until_renewal - ( $client_account['checks_limit'] - $client_account['checks_done'] );
								?>
								<div class="wcd-warning-card">
									<span class="dashicons dashicons-warning wcd-warning-icon"></span>
									<div class="wcd-warning-content">
										You'll run out of checks by <strong>around <?php echo number_format($missing_checks, 0); ?> checks</strong> until renewal.<br>
										<a href="?tab=upgrade">Upgrade now for more checks</a>
									</div>
								</div>
								<?php
							}
							?>

							<div class="wcd-account-item">
								<span class="wcd-account-label">Renewal Date</span>
								<span class="wcd-account-value"><?php echo esc_html(gmdate('F j, Y', strtotime($client_account['renewal_at']))); ?></span>
							</div>

							<div class="wcd-account-item">
								<span class="wcd-account-label">Current Plan</span>
								<span class="wcd-account-value">
									<?php echo esc_html($client_account['plan_name']); ?>
									<span class="wcd-plan-badge">Active</span>
									<a href="<?php echo esc_url(get_upgrade_url()); ?>" class="wcd-upgrade-link">Upgrade</a>
								</span>
							</div>

							<div class="wcd-account-item">
								<span class="wcd-account-label">API Token</span>
								<div class="wcd-api-token-section">
									<div class="wcd-api-token-controls">
										<span id="api-token-display" style="display: none; font-family: monospace; font-size: 13px;"><?php echo esc_html(get_user_meta(get_current_user_id(),'wcd_active_api_token', 1) ?? ''); ?></span>
										<span id="api-token-hidden" style="font-family: monospace; letter-spacing: 2px;">••••••••••••••••••••••••••••••••••••••••••••••••••</span>
										<button type="button" id="toggle-api-token" class="wcd-token-toggle" title="Show/Hide API Token">
											<span class="dashicons dashicons-visibility"></span>
										</button>
									</div>
									<div class="wcd-security-notice">
										<span class="dashicons dashicons-shield-alt"></span>
										Keep your API token secure and never share it publicly
									</div>
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>

			<!-- CTA Cards -->
			<div class="wcd-cta-grid">
				<div class="wcd-cta-card">
					<h3>WordPress Plugin</h3>
					<p class="wcd-cta-description">Get our full-featured WordPress plugin with all monitoring capabilities. Manage all your WordPress websites from this WebApp.</p>
					<a href="<?php echo esc_url(MM_APP_URL_PRODUCTION); ?>/download-wp-plugin" class="wcd-cta-button" target="_blank">Download Plugin</a>
				</div>
				<div class="wcd-cta-card">
					<h3>Developer API</h3>
					<p class="wcd-cta-description">Integrate WebChange Detector directly into your system with our comprehensive API. We're here to help with your integration.</p>
					<a href="https://api.webchangedetector.com/docs" class="wcd-cta-button" target="_blank">View API Docs</a>
				</div>
			</div>
		</div>

		<script>
		document.addEventListener('DOMContentLoaded', function() {
			const toggleBtn = document.getElementById('toggle-api-token');
			const tokenDisplay = document.getElementById('api-token-display');
			const tokenHidden = document.getElementById('api-token-hidden');
			const icon = toggleBtn.querySelector('.dashicons');
			
			toggleBtn.addEventListener('click', function() {
				if (tokenDisplay.style.display === 'none') {
					tokenDisplay.style.display = 'inline';
					tokenHidden.style.display = 'none';
					icon.classList.remove('dashicons-visibility');
					icon.classList.add('dashicons-hidden');
					toggleBtn.title = 'Hide API Token';
				} else {
					tokenDisplay.style.display = 'none';
					tokenHidden.style.display = 'inline';
					icon.classList.remove('dashicons-hidden');
					icon.classList.add('dashicons-visibility');
					toggleBtn.title = 'Show API Token';
				}
			});
		});
		</script>
		<?php
	}

	public function get_account_view( $account_details ) {
		// could be null
		if ( ! is_array( $account_details ) ) {
			// fail gracefully
			return false;
		}
		$available_percent = $account_details['checks_left'] / $account_details['checks_limit'] * 100;
		$available_class   = '';
		if ( $available_percent <= 10 ) {
			$available_class = 'low-credit';
		}
		if ( $account_details['checks_left'] <= 0 ) {
			$available_class = 'no-credit';
		}

		$renewal_label = 'Next Renew:';

		return '<div class="side_nav_content">
                <h3>Your Account</h3>
                <p class="' . $available_class . ' available-screenshots"><strong>Available Checks:</strong><br>
                <span id="available-credits" data-available_sc="' . $account_details['checks_left'] . '" >' . $account_details['checks_left'] . '</span> / ' . $account_details['checks_limit'] . '</strong></p>
                <p id="current-plan" data-plan_id="' . $account_details['plan'] . '" ><strong>Current Plan:</strong><br> ' . $account_details['plan_name'] . '</p>
                <p><strong>' . $renewal_label . '</strong><br>
                    <span class="local-date" data-date="' . strtotime( $account_details['renewal_at'] ) . '">
                    ' . gmdate( 'd/m/Y', strtotime( $account_details['renewal_at'] ) ) . '
                    </span>
                </p>
                
                </div>';
	}

	public function get_action_view( $groups_and_urls, $filters, $update_view = true ) {
		include wcd_get_plugin_dir() . '/public/partials/auto-update-settings.php';
	}

	public function get_loading_icon_url_path() {
		return plugin_dir_url( __FILE__ ) . '../public/img/loading.gif';
	}

	public function get_loading_transparent_bg_icon_url_path() {
		return plugin_dir_url( __FILE__ ) . '../public/img/loading-transparent-bg.gif';
	}

	public function print_websites( $websites_obj, $subaccount_api_token = null ) {
        if(empty($websites_obj['data'])) {
            echo '<div style="text-align: center; display: block;margin-top: 50px; margin-bottom: 50px">
				<p class="add-url">There are no WordPress Websites yet</p>
			</div>';
            return;
        }
        $websites = $websites_obj['data'];
        $websites_meta = $websites_obj['meta'];

        foreach ( $websites as $website ) {
            $website_name = empty( $website['domain'] ) ? 'Default settings for newly added websites' : $website['domain'];
            $class        = empty( $website['domain'] ) ? 'mm_default_website_settings' : '';
            ?>
            <div class="accordion-container">
                <div class="accordion">
                    <div class="mm_accordion_title <?php echo $class; ?>" >
                        <h3 style="position: relative">
                            <div class="accordion-state-icon-position" >
                                <span class="accordion-state-icon dashicons dashicons-arrow-right-alt2" ></span>
                            </div>
                            <div style="display: inline-block">
                                <div style="margin-left: 30px;">
                                    <?php if ( ! empty( $website['domain'] ) ) { ?>
                                        <img src="http://www.google.com/s2/favicons?domain=<?php echo $website_name; ?>">
                                        <?php
                                    }
                                    echo $website_name;
                                    ?>
                                </div>
                            </div>
                        </h3>
                        <div class='mm_accordion_content'>
                            <form method='post' class='ajax website-form' onsubmit='return false'>
                                <div class="wcd-website-settings-column">
                                    <input type="hidden" name="action" value="save_user_website">
                                    <input type="hidden" name="api_token" value="<?php echo $subaccount_api_token ?>">
                                    <input type="hidden" name="id" value="<?php echo $website['id']; ?>">
                                    <input type="hidden" name="domain" value="<?php echo $website['domain'] ?? 'Default settings for newly added websites'; ?>">

                                    <h4 class="website-setting" style=" margin-bottom: 10px;">
                                        Tabs in WP Plugin<br><small>Select which tabs should be enabled at the WP website</small>
                                    </h4>
                                    <label class="website-setting">
                                        <input type="hidden" name="allowances_manual_checks_view" value="0">
                                        <input type="checkbox" name='allowances_manual_checks_view' class="website-setting" value="1"
                                            <?php echo ! empty( $website['allowances']['manual_checks_view'] ) ? 'checked' : ''; ?>>
                                        Manual checks view
                                    </label>

                                    <label class="website-setting">
                                        <input type="hidden" name="allowances_monitoring_checks_view" value="0">
                                        <input type="checkbox" name='allowances_monitoring_checks_view' class="website-setting" value="1"
                                            <?php echo ! empty( $website['allowances']['monitoring_checks_view'] ) ? 'checked' : ''; ?>>
                                        Monitoring checks view
                                    </label>

                                    <label class="website-setting">
                                        <input type="hidden" name="allowances_change_detections_view" value="0">
                                        <input type="checkbox" name='allowances_change_detections_view' class="website-setting" value="1"
                                            <?php echo ! empty( $website['allowances']['change_detections_view'] ) ? 'checked' : ''; ?>>
                                        Change Detections view
                                    </label>

                                    <label class="website-setting">
                                        <input type="hidden" name="allowances_settings_view" value="0">
                                        <input type="checkbox" name='allowances_settings_view' class="website-setting" value="1"
                                            <?php echo ! empty( $website['allowances']['settings_view'] ) ? 'checked' : ''; ?>>
                                        Settings view
                                    </label>

                                    <label class="website-setting">
                                        <input type="hidden" name="allowances_logs_view" value="0">
                                        <input type="checkbox" name='allowances_logs_view' class="website-setting" value="1"
                                            <?php echo ! empty( $website['allowances']['logs_view'] ) ? 'checked' : ''; ?>>
                                        Queue view
                                    </label>

                                    <h4 class="website-setting" style="margin-top: 20px; margin-bottom: 10px;">
                                        Manual checks<br><small>The "Manual checks view" must be enabled</small>
                                    </h4>
                                    <label class="website-setting">
                                        <input type="hidden" name="allowances_manual_checks_start" value="0">
                                        <input type="checkbox" name='allowances_manual_checks_start' class="website-setting" value="1"
                                            <?php echo ! empty( $website['allowances']['manual_checks_start'] ) ? 'checked' : ''; ?>>
                                        Allow start manual checks
                                    </label>

                                    <label class="website-setting">
                                        <input type="hidden" name="allowances_manual_checks_settings" value="0">
                                        <input type="checkbox" name='allowances_manual_checks_settings' class="website-setting" value="1"
                                            <?php echo ! empty( $website['allowances']['manual_checks_settings'] ) ? 'checked' : ''; ?>>
                                        Show manual checks settings
                                    </label>
                                    <label class="website-setting">
                                        <input type="hidden" name="allowances_manual_checks_urls" value="0">
                                        <input type="checkbox" name='allowances_manual_checks_urls' class="website-setting" value="1"
                                            <?php echo ! empty( $website['allowances']['manual_checks_urls'] ) ? 'checked' : ''; ?>>
                                        Show manual checks urls
                                    </label>

                                    <h4 class="website-setting" style="margin-top: 20px; margin-bottom: 10px;">
                                        Monitoring checks <br><small>The "Monitoring checks view" must be enabled</small></h4>
                                    <label class="website-setting">
                                        <input type="hidden" name="allowances_monitoring_checks_settings" value="0">
                                        <input type="checkbox" name='allowances_monitoring_checks_settings' class="website-setting" value="1"
                                            <?php echo ! empty( $website['allowances']['monitoring_checks_settings'] ) ? 'checked' : ''; ?>>
                                        Show monitoring checks settings
                                    </label>
                                    <label class="website-setting">
                                        <input type="hidden" name="allowances_monitoring_checks_urls" value="0">
                                        <input type="checkbox" name='allowances_monitoring_checks_urls' class="website-setting" value="1"
                                            <?php echo ! empty( $website['allowances']['monitoring_checks_urls'] ) ? 'checked' : ''; ?>>
                                        Show monitoring checks urls
                                    </label>

                                    <h4 class="website-setting" style="margin-top: 20px; margin-bottom: 10px;">
                                        Other settings<br><small>Some more restrictions you can set for the WP website</small>
                                    </h4>
                                    <label class="website-setting">
                                        <input type="hidden" name="allowances_settings_add_urls" value="0">
                                        <input type="checkbox" name='allowances_settings_add_urls' class="website-setting" value="1"
                                            <?php echo ! empty( $website['allowances']['settings_add_urls'] ) ? 'checked' : ''; ?>>
                                        Show add url types in settings
                                    </label>
                                    <label class="website-setting">
                                        <input type="hidden" name="allowances_settings_account_settings" value="0">
                                        <input type="checkbox" name='allowances_settings_account_settings' class="website-setting" value="1"
                                            <?php echo ! empty( $website['allowances']['settings_account_settings'] ) ? 'checked' : ''; ?>>
                                        Show account settings
                                    </label>

                                    <label class="website-setting">
                                        <input type="hidden" name="allowances_upgrade_account" value="0">
                                        <input type="checkbox" name='allowances_upgrade_account' class="website-setting" value="1"
                                            <?php echo ! empty( $website['allowances']['upgrade_account'] ) ? 'checked' : ''; ?>>
                                        Allow upgrading account
                                    </label>
                                    <label class="website-setting">
                                        <input type="hidden" name="allowances_wizard_start" value="0">
                                        <input type="checkbox" name='allowances_wizard_start' class="website-setting" value="1"
                                            <?php echo ! empty( $website['allowances']['wizard_start'] ) ? 'checked' : ''; ?>>
                                        Start the wizard
                                    </label>
                                    <label class="website-setting">
                                        <input type="hidden" name="allowances_only_frontpage" value="0">
                                        <input type="checkbox" name='allowances_only_frontpage' class="website-setting" value="1"
                                            <?php echo ! empty( $website['allowances']['only_frontpage'] ) ? 'checked' : ''; ?>>
                                        Allow only checks for frontpage
                                    </label>
                                </div>

                                <div class="wcd-website-settings-column">
                                    <h4 class="website-settings">
                                        Auto Update Settings<br><small>Make the settings for WP auto update checks</small>
                                    </h4>
                                    <label class="website-setting">
                                        <input type="hidden" name="auto_update_settings_auto_update_checks_enabled" value="0">
                                        <input type="checkbox" name='auto_update_settings_auto_update_checks_enabled' class="website-setting" value="1"
                                            <?php echo ! empty( $website['auto_update_settings']['auto_update_checks_enabled'] ) ? 'checked' : ''; ?>>
                                        Auto Updates Checks enabled
                                    </label>
                                    <p style="margin-top: 20px; margin-bottom: 10px; font-size: 16px;">Do WP auto-updates only on these weekdays:</p>
                                    <label class="website-setting">
                                        <input type="hidden" name="auto_update_settings_auto_update_checks_monday" value="0">
                                        <input type="checkbox" name='auto_update_settings_auto_update_checks_monday' class="website-setting" value="1"
                                            <?php echo ! empty( $website['auto_update_settings']['auto_update_checks_monday'] ) ? 'checked' : ''; ?>>
                                        Monday
                                    </label>
                                    <label class="website-setting">
                                        <input type="hidden" name="auto_update_settings_auto_update_checks_tuesday" value="0">
                                        <input type="checkbox" name='auto_update_settings_auto_update_checks_tuesday' class="website-setting" value="1"
                                            <?php echo ! empty( $website['auto_update_settings']['auto_update_checks_tuesday'] ) ? 'checked' : ''; ?>>
                                        Tuesday
                                    </label>
                                    <label class="website-setting">
                                        <input type="hidden" name="auto_update_settings_auto_update_checks_wednesday" value="0">
                                        <input type="checkbox" name='auto_update_settings_auto_update_checks_wednesday' class="website-setting" value="1"
                                            <?php echo ! empty( $website['auto_update_settings']['auto_update_checks_wednesday'] ) ? 'checked' : ''; ?>>
                                        Wednesday
                                    </label>
                                    <label class="website-setting">
                                        <input type="hidden" name="auto_update_settings_auto_update_checks_thursday" value="0">
                                        <input type="checkbox" name='auto_update_settings_auto_update_checks_thursday' class="website-setting" value="1"
                                            <?php echo ! empty( $website['auto_update_settings']['auto_update_checks_thursday'] ) ? 'checked' : ''; ?>>
                                        Thursday
                                    </label>
                                    <label class="website-setting">
                                        <input type="hidden" name="auto_update_settings_auto_update_checks_friday" value="0">
                                        <input type="checkbox" name='auto_update_settings_auto_update_checks_friday' class="website-setting" value="1"
                                            <?php echo ! empty( $website['auto_update_settings']['auto_update_checks_friday'] ) ? 'checked' : ''; ?>>
                                        Friday
                                    </label>
                                    <label class="website-setting">
                                        <input type="hidden" name="auto_update_settings_auto_update_checks_saturday" value="0">
                                        <input type="checkbox" name='auto_update_settings_auto_update_checks_saturday' class="website-setting" value="1"
                                            <?php echo ! empty( $website['auto_update_settings']['auto_update_checks_saturday'] ) ? 'checked' : ''; ?>>
                                        Saturday
                                    </label>
                                    <label class="website-setting">
                                        <input type="hidden" name="auto_update_settings_auto_update_checks_sunday" value="0">
                                        <input type="checkbox" name='auto_update_settings_auto_update_checks_sunday' class="website-setting" value="1"
                                            <?php echo ! empty( $website['auto_update_settings']['auto_update_checks_sunday'] ) ? 'checked' : ''; ?>>
                                        Sunday
                                    </label>
                                    <label class="website-setting">
                                        Auto update times from
                                        <input type="time" name='auto_update_settings_auto_update_checks_from' class="website-setting "
                                                style="border: 1px solid #888"
                                                value="<?php echo $website['auto_update_settings']['auto_update_checks_from'] ?? ''; ?>">
                                        to

                                        <input type="time" name='auto_update_settings_auto_update_checks_to' class="website-setting "
                                                style="border: 1px solid #888"
                                                value="<?php echo $website['auto_update_settings']['auto_update_checks_to'] ?? ''; ?>"
                                        >
                                    </label>
                                    <label class="website-setting">
                                        Alert emails (multiple emails: comma seperated)
                                        <input type="text" name='auto_update_settings_auto_update_checks_emails' class="website-setting"
                                                style="width: 100%"
                                                value="<?php echo $website['auto_update_settings']['auto_update_checks_emails'] ?? ''; ?>">

                                    </label>

                                    <?php
                                    /*
                                    Debugging: available auto update vars and the plugin defaults:
                                    'auto_update_checks_enabled'   => '1',
                                    'auto_update_checks_from'      => gmdate( 'H:i' ),
                                    'auto_update_checks_to'        => gmdate( 'H:i', strtotime( '+12 hours' ) ),
                                    'auto_update_checks_monday'    => '1',
                                    'auto_update_checks_tuesday'   => '1',
                                    'auto_update_checks_wednesday' => '1',
                                    'auto_update_checks_thursday'  => '1',
                                    'auto_update_checks_friday'    => '1',
                                    'auto_update_checks_saturday'  => '',
                                    'auto_update_checks_sunday'    => '',
                                    'auto_update_checks_emails'    => get_option( 'admin_email' ),
                                    */
                                    ?>
                                </div>
                                <div class="clear"></div>
                                <input class='button et_pb_button' type='submit' value='Save'>
                        </form>

                        <?php if ( ! empty( $website['domain'] ) ) { ?>
                            <form class="ajax" style="float: right; position: absolute; right: 20px; bottom: 20px">
                                <input type="hidden" name="action" value="delete_website">
                                <input type="hidden" name="domain" value="<?php echo $website['domain']; ?>">
                                <input type="submit" class="et_pb_button delete_button" value="Delete Group" onclick="return confirm('Are your sure your want to delete the website including its groups and urls?')">
                            </form>
                            <div class="clear"></div>
                        <?php } ?>
                        </div>
                    </div>
                </div>
            </div>
            <?php
        } ?>

        <!-- Pagination -->
        <div class="tablenav">
            <div class="tablenav-pages">
                <span class="pagination-links">
                    <?php
                    foreach ( $websites_meta['links'] as $link ) {
                        $url_params = $this->get_params_of_url( $link['url'] );
                        $class  = ! $link['url'] || $link['active'] ? 'disabled' : '';
                        ?>
                        <a class="tablenav-pages-navspan et_pb_button <?php echo esc_html( $class ); ?>"
                           href="?tab=website-settings&pagination=<?php
                           echo esc_html( $url_params['page'] ?? 1 ); ?>">
                                <?php echo esc_html( $link['label'] ); ?>
                        </a>
                        <?php
                    }
                    ?>
                </span>
                <span class="displaying-num"><?php echo esc_html( $websites_meta['total'] ); ?> items</span>
            </div>
        </div>
        <?php
	}

        public function is_main_account() {
            $current_api_token = get_user_meta(get_current_user_id(), 'wcd_active_api_token', 1) ?? mm_api_token();
            if(!$current_api_token) {
                $current_api_token = mm_api_token();
            }
            $main_api_token = mm_api_token();

            return $current_api_token === $main_api_token;
        }

        public function get_wp_website_settings() { ?>
            <div class="wcd-modern-dashboard">
                <!-- Main Dashboard Grid -->
                <div class="wcd-dashboard-grid">
                    <!-- WordPress Plugin Card -->
                    <div class="wcd-card">
                        <div class="wcd-card-header">
                            <h2>
                                <span class="dashicons dashicons-wordpress" style="color: #266ECC;"></span>
                                Plugin for WordPress
                            </h2>
                        </div>
                        <div class="wcd-card-content">
                            <p>Connect your WP website to the WebChange Detector WebApp using your API token.</p>
                            <p><a href="/webchangedetector/?tab=subaccounts" target="_blank">Create sub-accounts</a> to limit checks per WP website.</p>
                            <a class="et_pb_button" href="https://wordpress.org/plugins/webchangedetector/">Download the plugin</a>
                        </div>
                    </div>

                    <!-- API Token Card -->
                    <div class="wcd-card">
                        <div class="wcd-card-header">
                            <h2>
                                <span class="dashicons dashicons-admin-network" style="color: #266ECC;"></span>
                                Your API Token
                            </h2>
                        </div>
                        <div class="wcd-card-content">
                            <p>Use your API token in our WordPress plugin to link your sites to your WebChangeDetector account, configure settings, and manage restrictions. Websites using your API token will appear here.</p>
                            
                            <div class="wcd-api-token-section" data-token="<?php echo esc_attr(mm_api_token()); ?>">
                                <div class="wcd-api-token-controls">
                                    <span id="api-token-display" style="display: none; font-family: monospace; font-size: 13px;"><?php echo esc_html(mm_api_token()); ?></span>
                                    <span id="api-token-hidden" style="font-family: monospace; letter-spacing: 2px;">••••••••••••••••••••••••••••••••••••••••••••••••••</span>
                                    <button type="button" id="toggle-api-token" class="wcd-token-toggle" title="Show/Hide API Token">
                                        <span class="dashicons dashicons-visibility"></span>
                                    </button>
                                </div>
                                <div class="wcd-security-notice">
                                    <span class="dashicons dashicons-shield-alt"></span>
                                    Keep your API token secure and never share it publicly
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Linked WP Sites Section -->
                <div class="wcd-card" style="margin-top: 20px;">
                    <div class="wcd-card-header">
                        <h2>
                            <span class="dashicons dashicons-admin-links" style="color: #266ECC;"></span>
                            Your Linked WP Sites
                        </h2>
                    </div>
                    <div class="wcd-card-content">
                        <?php
                        $filter = [
                            'per_page' => 25,
                            'page' => $_GET['pagination'] ?? 1
                        ];
                        $websites = Wp_Compare_API_V2::get_websites_v2($filter);
                        $this->print_websites( $websites );
                        ?>
                    </div>
                </div>
            </div>

            <script>
            document.addEventListener('DOMContentLoaded', function() {
                const toggleBtn = document.getElementById('toggle-api-token');
                const tokenDisplay = document.getElementById('api-token-display');
                const tokenHidden = document.getElementById('api-token-hidden');
                const icon = toggleBtn.querySelector('.dashicons');
                
                toggleBtn.addEventListener('click', function() {
                    if (tokenDisplay.style.display === 'none') {
                        tokenDisplay.style.display = 'inline';
                        tokenHidden.style.display = 'none';
                        icon.classList.remove('dashicons-visibility');
                        icon.classList.add('dashicons-hidden');
                        toggleBtn.title = 'Hide API Token';
                    } else {
                        tokenDisplay.style.display = 'none';
                        tokenHidden.style.display = 'inline';
                        icon.classList.remove('dashicons-hidden');
                        icon.classList.add('dashicons-visibility');
                        toggleBtn.title = 'Show API Token';
                    }
                });
            });
            </script>

        <?php
        }

		/**
		 * Sub-accounts view
		 */
		public function get_subaccount_view() {
			if($this->is_main_account()) {
				include wcd_get_plugin_dir() . 'public/partials/popup-add-subaccount.php'; ?>
				<div class="action_container wcd-modern-dashboard">
					<div class="website-settings wcd-card">
						<div class="wcd-card-header">
							<h2>
								<span class="dashicons dashicons-admin-users" style="color: #266ECC;"></span>
								Subaccount Management
							</h2>
						</div>
						<div class="wcd-card-content">
							<p>Create and manage subaccounts to delegate access and limit checks per account.</p>
							
							<div style="margin-bottom: 20px;">
								<button class='et_pb_button' onclick='showAddSubaccountPopup(); return false;'>
									<span class="dashicons dashicons-plus-alt"></span>
									Create Subaccount
								</button>
							</div>

							<div id="ajax_subaccounts_container">
								<?php
								$subaccounts           = Wp_Compare_API_V2::get_subaccounts()['data'];
								$subaccount_api_tokens = get_user_meta( get_current_user_id(), 'wcd_subaccount_api_tokens', true );

								if ( count( $subaccounts ) ) {
									foreach ( $subaccounts as $subaccount ) {
										?>
										<div class="box-half wcd-card" style="margin-bottom: 20px;">
											<div class="wcd-card-content">
												<div class="wcd-account-section">
													<div class="wcd-account-item">
														<span class="wcd-account-label">Subaccount</span>
														<span class="wcd-account-value"><?php echo esc_html($subaccount['name_first'] . ' ' . $subaccount['name_last']); ?></span>
													</div>

													<div class="wcd-account-item">
														<span class="wcd-account-label">Email</span>
														<span class="wcd-account-value"><?php echo esc_html($subaccount['email']); ?></span>
													</div>

													<div class="wcd-account-item">
														<span class="wcd-account-label">Usage</span>
														<span class="wcd-account-value"><?php echo esc_html($subaccount['checks_left'] . ' / ' . $subaccount['checks_limit']); ?> checks</span>
														<?php 
														$usage_percent = $subaccount['checks_limit'] > 0 ? (($subaccount['checks_limit'] - $subaccount['checks_left']) / $subaccount['checks_limit']) * 100 : 0;
														?>
														<div class="wcd-progress-container">
															<div class="wcd-progress-bar" style="width: <?php echo esc_attr(number_format($usage_percent, 1)); ?>%;"></div>
															<span class="wcd-progress-text"><?php echo esc_html(number_format($usage_percent, 1)); ?>%</span>
														</div>
													</div>

													<div class="wcd-account-item">
														<span class="wcd-account-label">API Token</span>
														<?php $subaccount_api_token = $subaccount_api_tokens[ $subaccount['id'] ] ?? false; ?>
														<div class="wcd-api-token-section" data-token="<?php echo esc_attr($subaccount_api_token); ?>">
															<div class="wcd-api-token-controls">
																<span class="api-token-display" style="display: none; font-family: monospace; font-size: 13px;"><?php echo esc_html($subaccount_api_token); ?></span>
																<span class="api-token-hidden" style="font-family: monospace; letter-spacing: 2px;">••••••••••••••••••••••••••••••••••••••••••••••••••</span>
																<button type="button" class="wcd-token-toggle" title="Show/Hide API Token" onclick="return revealApiToken(this);">
																	<span class="dashicons dashicons-visibility"></span>
																</button>
															</div>
														</div>
													</div>

													<div class="wcd-account-item">
														<span class="wcd-account-label">Actions</span>
														<div style="display: flex; gap: 10px; flex-wrap: wrap;">
															<button class='et_pb_button' onclick="showUpdateSubaccountPopup('<?php echo $subaccount['id'] ?>')">
																<span class="dashicons dashicons-edit"></span>
																Edit
															</button>
															<button class='et_pb_button ajax-switch-account-button' data-id="<?php echo $subaccount['id'] ?>">
																<span class="dashicons dashicons-admin-users"></span>
																Switch to Account
															</button>
														</div>
													</div>
												</div>
											</div>
										</div>
										<?php
									}
								} else { ?>
									<div class="box-half wcd-card" style="text-align: center; padding: 40px;">
										<span class="dashicons dashicons-welcome-add-page" style="font-size: 48px; color: #64748b; margin-bottom: 16px;"></span>
										<h3 style="color: #64748b; margin-bottom: 8px;">No Subaccounts Yet</h3>
										<p style="color: #64748b; margin-bottom: 20px;">Create your first subaccount to delegate access and manage checks.</p>
										<button class='et_pb_button' onclick="showAddSubaccountPopup(); return false;">
											<span class="dashicons dashicons-plus-alt"></span>
											Create First Subaccount
										</button>
									</div>
								<?php } ?>
							</div>
						</div>
					</div>
				</div>
				<?php } else { ?>
				<div class="action_container wcd-modern-dashboard">
					<div class="website-settings wcd-card" style="text-align: center;">
						<div class="wcd-card-content">
							<span class="dashicons dashicons-admin-users" style="font-size: 48px; color: #64748b; margin-bottom: 16px;"></span>
							<h3 style="color: #1e293b; margin-bottom: 16px;">Main Account Required</h3>
							<p style="margin-bottom: 20px;">Subaccounts can only be managed from your main account.</p>
							<a href="" class="et_pb_button ajax-switch-account-button" data-id="<?= $this->get_account_details_v2(mm_api_token())['id']; ?>">
								<span class="dashicons dashicons-admin-users"></span>
								Switch to Main Account
							</a>
						</div>
					</div>
				</div>
				<?php } ?>
		<?php
		}

    public function delete_group($group_id) {
        return Wp_Compare_API_V2::delete_group_v2($group_id);
    }

	public function delete_url( $postdata ) {
		$args = array(
			'action' => 'delete_url',
			'url_id' => $postdata['url_id'],
		);
		return mm_api( $args );
	}

	/**
	 * This one returns HTML
	 */
	public function get_comparison_partial( $token ) {
		$args = array(
			'action' => 'get_comparison_partial',
			'token'  => $token,
		);
		return mm_api( $args );
	}

	/**
	 * This one returns JSON
	 */
	public function get_comparison_by_token( $token ) {
		$args = array(
			'action' => 'get_comparison_by_token',
			'token'  => $token,
		);
		return mm_api( $args );
	}

	public function get_processing_queue($batch_id = false) {
        if(!$batch_id && isset($_POST['batch_id'])){
            $batch_id = $_POST['batch_id'];
        }

        $batches = Wp_Compare_API_V2::get_queue_v2($batch_id ?? false, 'open,processing');
        return $batches['meta']['total'] ?? 0;
	}

	public function get_compares_view( $postdata, $show_filters = true ) {

        // Get batches.
        $filter_batches = array(
            'page'     => $postdata['pagination'] ?? 1,
            'per_page' => 20,
        );
        if ( ! empty( $postdata['from'] ) ) {
            $filter_batches['from'] = gmdate( 'Y-m-d', strtotime( $postdata['from'] ) );
        }
        if ( ! empty( $postdata['to'] ) ) {
            $filter_batches['to'] = gmdate( 'Y-m-d', strtotime( $postdata['to'] ) );
        }

        if ( ! empty( $postdata['group_type'] ) ) {
            $extra_filters['queue_type'] = $postdata['group_type'];
        } else {
            $extra_filters['queue_type'] = 'post,auto';
        }

        if ( ! empty( $postdata['status'] ) ) {
            $extra_filters['status'] = $postdata['status'];
        } else {
            $extra_filters['status'] = 'new,ok,to_fix,false_positive';
        }

        if ( ! empty( $postdata['difference_only'] ) ) {
            $extra_filters['above_threshold'] = (bool) $postdata['difference_only'];
        }

        if ( ! empty( $postdata['group_id'] ) ) {
            $extra_filters['group_ids'] = $postdata['group_id'];
        }

		$filter_batches_in_comparisons = array();
		if(!empty($postdata['batch_id'])) {
			$filter_batches_in_comparisons[] = $postdata['batch_id'];
            $batch =  Wp_Compare_API_V2::get_batch( $postdata['batch_id']);
            $batches['data'][0] = $batch['data']; // Prepare for foreach-loop.
		} else {
	        $batches = Wp_Compare_API_V2::get_batches( array_merge( $filter_batches, $extra_filters ) );

	        foreach ( $batches['data'] as $batch ) {
		        $filter_batches_in_comparisons[] = $batch['id'];
	        }
        }

		$filters = array(
			'from'      => $postdata['from'] ?? null,
			'to' => $postdata['to'] ?? null,
			'status'          => $postdata['status'] ?? '',
			'group_id'        => $postdata['group_id'] ?? '',
			'difference_only' => $postdata['difference_only'] ?? false,
			'group_type'      => $postdata['group_type'] ?? '',
			//'limit_domain'    => $postdata['limit_domain'] ?? '',
		);

		if ( isset( $postdata['show_filters'] ) && ! (int) $postdata['show_filters'] ) {
			$show_filters = false;
		}

		if ( isset( $postdata['latest_batch'] ) ) {
			$filters['latest_batch'] = $postdata['latest_batch'];
		}

		if ( isset( $postdata['limit_domain'] ) ) {
			$filters['limit_domain'] = $postdata['limit_domain'];
		}

		// Show Change detection by get parameter
		if ( isset( $_GET['show-change-detection-token'] ) ) {
			$token = $_GET['show-change-detection-token'];
			echo '<script>
                    jQuery(document).ready(function() {
                        ajaxShowChangeDetectionPopup("' . $token . '");
                    });
                </script>';
		}

		?>

		<div class="action_container wcd-modern-dashboard">
			<div class="latest_compares_content">
		    <?php if($show_filters ) { ?>
			<div class="wcd-card wcd-filters-card">
				<div class="wcd-card-header">
					<h3>
						<span class="dashicons dashicons-filter" style="color: #266ECC;"></span>
						Filter Change Detections
					</h3>
				</div>
				<div class="wcd-card-content">
                	<form id="form-filter-change-detections" method="post" class="ajax-filter wcd-filter-form" style="overflow: visible; margin-bottom: 30px; " >
                        <div class="filter-row" style="margin: 0;">
                        <input type="hidden" name="action" value="filter_change_detections">
                        <input type="hidden" name="pagination" value="1">
                        <div class="change_detection_amount" style="display: none; "><?php echo $compares['meta']['total'] ?? '0'; ?> Change Detections</div>

                        <div class="filter-dropdowns wcd-filter-grid">
                            <div class="dropdown-container from wcd-filter-item">
                                <div class="change-detection-filter-label wcd-filter-label">
									<span class="dashicons dashicons-calendar-alt"></span>
									From
								</div>
                                <input type="date" name="from" class="js-dropdown-style wcd-filter-input" value="<?= $filters['from'] ?? gmdate( 'Y-m-d', strtotime( '- 30 days' ) ); ?>">
                            </div>

                            <div class="dropdown-container to wcd-filter-item">
								<div class="change-detection-filter-label wcd-filter-label">
									<span class="dashicons dashicons-calendar-alt"></span>
									To
								</div>
                                <input type="date" name="to" class="js-dropdown-style wcd-filter-input" value="<?= $filters['to'] ?? gmdate( 'Y-m-d' ); ?>">
                            </div>

                            <div class="dropdown-container difference_only wcd-filter-item">
								<div class="change-detection-filter-label wcd-filter-label">
									<span class="dashicons dashicons-search"></span>
									Changes
								</div>
                                <select name="difference_only" class="js-dropdown-style wcd-filter-input">
                                    <option value="0" <?php echo empty( $filters['difference_only'] ) ? 'selected' : ''; ?>>All detections</option>
                                    <option value="1" <?php echo $filters['difference_only'] ? 'selected' : ''; ?>>With changes only</option>
                                </select>
                            </div>

                            <div class="dropdown-container group_type wcd-filter-item">
								<div class="change-detection-filter-label wcd-filter-label">
									<span class="dashicons dashicons-category"></span>
									Type
								</div>
                                <select name="group_type" class="js-dropdown-style wcd-filter-input">
                                    <option value="" <?php echo ! $filters['group_type'] ? 'selected' : ''; ?>>All types</option>
                                    <option value="post" <?php echo $filters['group_type'] == 'post' ? 'selected' : ''; ?>>Manual Checks</option>
                                    <option value="auto" <?php echo $filters['group_type'] == 'auto' ? 'selected' : ''; ?>>Monitoring</option>
                                </select>
                            </div>
                            <?php

                            $availableStatus = array(
                                'none'           => 'None',
                                'new'            => 'New',
                                'ok'             => 'Ok',
                                'to_fix'         => 'To fix',
                                'false_positive' => 'False positive',
                            );
                            ?>
                            <div class="dropdown-container status wcd-filter-item">
								<div class="change-detection-filter-label wcd-filter-label">
									<span class="dashicons dashicons-flag"></span>
									Status
								</div>
                                <select name="status" class="js-dropdown-style wcd-filter-input">
                                    <option value="" <?php echo ! $filters['status'] ? 'selected' : ''; ?>>All status</option>
                                    <?php foreach ( $availableStatus as $statusKey => $statusName ) { ?>
                                        <option value="<?php echo $statusKey; ?>" <?php echo $filters['status'] == $statusKey ? 'selected' : ''; ?>><?php echo $statusName; ?></option>
                                    <?php } ?>
                                </select>
                            </div>
                            
                            <div class="dropdown-container submit wcd-filter-actions">
                                <input type="submit" class="et_pb_button" value="Filter" style="margin-top: 0;">
                                <a href="?tab=change-detections" class="wcd-reset-link">Reset Filter</a>
                            </div>
                            <div class="clear"></div>
                        </div>
                        </div>
                	</form>
				</div>
			</div>
			<?php
		} ?>


        <div id="change-detection-batches">
            <div class="responsive-table">
            <?php

			if(empty($batches['data'])) {
				?>
				<div class="box-plain bg" style="text-align: center; padding: 0px 0; margin-top: 20px; color: #333;">
					<strong>No Change Detections (yet)</strong>
					<p>
						Start monitoring webpages or start Manual Checks or try different filters if there should be Change Detections.<br>
					</p>
				</div>
				<?php
			} else {

            foreach($batches['data'] as $batch) {
                
				// Set sort filters 
				// TODO Make this easier to handle
				$extra_filters['orderBy'] = 'difference_percent';
				$filters['orderBy'] = 'difference_percent';
				$extra_filters['orderDirection'] = 'desc';
				$filters['orderDirection'] = 'desc';

                

                // We don't have any change detections to show.
                if(empty($batch)) {
                    if ( isset( $filters['latest_batch'] ) ) {
                        ?>
                        <div style="text-align: center; margin-top: 50px;">
                            <div class="wcd-highlight-bg done">
                                <div><?php echo get_device_icon( 'check', 'icon-big' ); ?></div>
                                <h3>All Good</h3>
                                <p>No changes were detected. <br><a href="?tab=change-detections">Check all change detections</a></p>
                            </div>
                        </div>
                    <?php } else { ?>
                        <div class="website-settings">
                            <div class="box-plain bg" style="text-align: center; padding: 50px 0; margin-top: 20px; color: #333">
                                <h3>No Change Detections yet</h3>
                                <p>
                                    Start monitoring webpages or start Manual Checks. Change Detections will appear here.<br>
                                    Try different filters if there should be Change Detections.
                                </p>

                            </div>
                        </div>
                    <?php }
                } else {

                // // Get Status, amount of compares and amount of mismatches.
                // if(!empty($compares['data'])) {
                //     foreach($compares['data'] as $compare) {
                //         if(!$compare['batch'] === $batch['id']) {
                //             continue;
                //         }

                //         if ( is_null( $compare['status'] ) ) {
                //             $compare['status'] = 'none';
                //         }

                //         if ( ! isset( $status[ $compare['status'] ] ) ) {
                //             $status[ $compare['status'] ] = 0;
                //         }
                //         $status[ $compare['status'] ] = $status[ $compare['status'] ] + 1;
                //     }
                // }

				$amount_failed = $batch['queues_count']['failed'] ?? 0;
                
                ?>
                <div class="accordion-container" data-batch_id="<?php echo $batch['id']; ?>" data-failed_count="<?php echo $amount_failed; ?>">
                    <div class="accordion">
                        <div class="mm_accordion_title">
							
                            <h3 style="position: relative; text-align: center" class="ajax_comparisons_container">
                                <div class="accordion-state-icon-position" style="">
                                    <span class="accordion-state-icon dashicons dashicons-arrow-right-alt2" ></span>
                                </div>
                                <div class="box fourth center status">
                                    <div class="status_container">
                                        <small>Status</small>
                                        <strong>
                                            <div class="status_buttons">
                                            <?php
                                            
                                            foreach ( $batch['comparisons_count'] as $singleStatus => $amountStatus ) {
                                                if($amountStatus > 0 && $singleStatus !== 'above_threshold') {
                                                    echo prettyPrintComparisonStatus( $singleStatus, 'mm_inline_block mm_small_status', $amountStatus ) . '<br>';
                                                }
                                            }

											// Show browser console errors
											if(!empty($_COOKIE['show_browser_console_errors']) && $_COOKIE['show_browser_console_errors'] == 'true') {
												if(!empty($batch['browser_console_count']['added'] || !empty($batch['browser_console_count']['mixed']))) {
													echo '<span style="color: darkred; font-size: 14px; font-weight: 700;">New browser console errors: ' . $batch['browser_console_count']['added'] + $batch['browser_console_count']['mixed'] . '</span><br>';
												}
											}

											// Show failed checks
                                            if($amount_failed) {
                                                echo "<span style='color: darkred; font-size: 14px; font-weight: 700;'>{$amount_failed} " . ($amount_failed > 1 ? "checks" : "check") . " failed</span>";
                                            }

                                            ?>
                                            </div>
                                        </strong>
                                    </div>
                                </div>
                                <div class="box fourth">
                                    <small>Change Detection</small>
                                    <strong><?php echo $batch['name']; ?></strong>
                                </div>
                                <div class="box fourth">
                                    <small>Created </small>
                                    <div class="big">
                                        <strong>
                                            <?php
                                            if($batch['finished_at']) {
                                                echo $this->timeAgo( $batch['finished_at'] ) . " <br>";
                                                echo "<span class='local-time' data-date='" .  date( 'U', strtotime( $batch['finished_at'] ) ) . "'>" .
                                                    date( 'd/m/Y H:i', strtotime( $batch['finished_at'] ) ) .
                                                '</span>';
                                            } else {
                                                echo "Processing";
                                            }
                                            ?>
                                        </strong>
                                    </div>
                                </div>
                                <div class="box fourth">
                                    <small>Change(s) found</small>
                                    <strong> On <?php echo $batch['comparisons_count']['above_threshold']; ?>  checks <br><?php echo ($batch['comparisons_count']['new'] + $batch['comparisons_count']['ok'] + $batch['comparisons_count']['to_fix'] + $batch['comparisons_count']['false_positive']); ?> URLs checked</strong>
                                </div>
                                <div class="clear"></div>
                            </h3>

                            <div class='mm_accordion_content no-padding'>
								<div class="ajax_batch_comparisons_content">
									<div class="ajax-loading-container">
										<img decoding="async" src="/wp-content/plugins/app/public/img/loading.gif">
										<div style="text-align: center;">Loading</div>
									</div>
								</div>
                                <?php
                                // We call this in an extra function to be able to use it for pagination.
                                //$this->load_comparisons_view($batch['id'], $compares, $filters);
                                ?>
                            </div>
                        </div>
                    </div>
                </div>
            <?php }
            }
		}



			// Latest Detections
			/*if ( (is_iterable( $compares['data'] ) && count( $compares['data'] ) > 0) ||
                 is_iterable($failed_queues['data']) && count( $failed_queues['data'] ) > 0  ) {
				?>

				<?php
				$lastBatchId = false;
				$status      = array();
				foreach ( $compares['data'] as $key => $compare ) {
					$group_name          = false;
					$assigned_group_type = '';
					$assigned_group_name = '';
					foreach ( $groups as $group ) {
						if ( $group['uuid'] == $compare['group'] ) {
							$assigned_group_type  = get_group_icon( $group ) .
							( $group['monitoring'] ? 'Monitoring' : 'Manual Checks' );
							$assigned_group_name  = $group['cms'] ? get_device_icon( $group['cms'] ) : get_device_icon( 'general' );
							$assigned_group_name .= $group['name'];
						}
						$group_name = $group['name'];
					}

					if ( $lastBatchId !== $compare['batch'] ) {
						$amountCompares   = 0;
						$amountMismatches = 0;
						$status           = array();
						foreach ( $compares['data'] as $compare_details ) {
							if ( $compare['batch'] != $compare_details['batch'] ) {
								continue;
							}

							++$amountCompares;

							if ( $compare_details['difference_percent'] > 0 ) {
								++$amountMismatches;
							}

							if ( is_null( $compare_details['status'] ) ) {
								$compare_details['status'] = 'none';
							}
							if ( ! isset( $status[ $compare_details['status'] ] ) ) {
								$status[ $compare_details['status'] ] = 0;
							}
							$status[ $compare_details['status'] ] = $status[ $compare_details['status'] ] + 1;

						}
						if ( $lastBatchId ) {
							?>
									</table>
								</div>
							</div>
						</div>
					</div>
					<?php } ?>

            <?php
*/
            // Prepare pagination.
            unset( $extra_filters['paged'] );
            unset( $filter_batches['page'] );

            // If we have meta, we show pagination. Change Detetion view at manual checks doesn't have pagination.
            if(!empty($batches['meta'])) {
                $pagination         = $batches['meta'];
                ?>
                <!-- Pagination -->
                <div class="tablenav">
                    <div class="tablenav-pages">
                        <span class="pagination-links">
                            <?php
                            foreach ( $pagination['links'] as $link ) {
                                $url_params = $this->get_params_of_url( $link['url'] );
                                $class  = ! $link['url'] || $link['active'] ? 'disabled' : '';
                                ?>
                                <a class="tablenav-pages-navspan et_pb_button <?php echo esc_html( $class ); ?>"
                                   href="?tab=change-detections&pagination=<?php
                                   echo esc_html( $url_params['page'] ?? 1 );
                                   echo "&" . esc_html(build_query($filters)); ?>">
                                        <?php echo esc_html( $link['label'] ); ?>
                                </a>
                                <?php
                            }
                            ?>
                        </span>
                        <span class="displaying-num"><?php echo esc_html( $pagination['total'] ); ?> items</span>
                    </div>
                </div>
            <?php             } else {
                // We only have one batch. So we open it. ?>
                <script>jQuery(document).ready(function() {jQuery(".mm_accordion_title h3").click()});</script>
            <?php }
			?>
			</div>
		</div>
		<?php

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

	public function get_status_for_batch( $compares ) {
		$status = array();
		foreach ( $compares as $compare_details ) {
			if ( is_null( $compare_details['status'] ) ) {
				$compare_details['status'] = 'none';
			}
			if ( ! isset( $status[ $compare_details['status'] ] ) ) {
				$status[ $compare_details['status'] ] = 0;
			}
			$status[ $compare_details['status'] ] = $status[ $compare_details['status'] ] + 1;
		}

		$output = '';
		foreach ( $status as $singleStatusSlug => $singleStatusAmount ) {
			$output .= prettyPrintComparisonStatus( $singleStatusSlug );
		}
		return $output;
	}

	public function get_compares_by_ids( $args ) {
		/*
		$args = array(
			'action' => 'get_compares_by_group_ids',
			'group_ids' => $filters['group_id'] ? json_encode([$filters['group_id']]) : null,
			'limit_days' => $filters['limit_days'],
			'limit_compares' => $filters['limit_compares'],
			'offset_compares' => $filters['offset_compares'],
			'difference_only' => $filters['difference_only'],
			//'group_type' => $filters['group_type'],

			'limit_domain' => $filters['limit_domain'] ?? null,
			'latest_batch' => $filters['latest_batch'] ?? 0,
		);
		if ($filters['cms'] !== '') {
			$args['cms'] = $filters['cms'];
		}*/

		$args['group_ids']  = isset( $args['group_id'] ) ? json_encode( array( $args['group_id'] ) ) : null;
		$args['limit_days'] = 30;
		$args['action']     = 'get_compares_by_group_ids';

		return mm_api( $args );
	}
    public function load_comparisons_view($batch_id, $compares, $filters, $failed_count) { 
		
		?>
        <div class="ajax_batch_comparisons_content">
            <!-- Failed Queues Accordion -->
			 <?php if($failed_count > 0) { ?>
				<div class="accordion-container failed-queues-accordion" style="margin: 20px">
					<div class="accordion">
						<div class="mm_accordion_title">
								<h3 onclick="toggleFailedQueues(this, '<?php echo $batch_id; ?>')" style="position: relative; cursor: pointer;">
								<div class="accordion-state-icon-position">
									<span class="accordion-state-icon accordion-arrow dashicons dashicons-arrow-right-alt2" style="transition: transform 0.3s;"></span>
								</div>
								<div style="display: inline-block">
									<div style="margin-left: 30px;">
										Failed checks
									</div>
								</div>
							</h3>
							<div class="failed-queues-content mm_accordion_content" style="display: none;">
                            <div class="failed-queues-loading" style="padding: 20px; text-align: center; display: none;">
                                Loading failed URLs...
                            </div>
                            <div class="failed-queues-table-container"></div>
                        </div>
						</div>
					</div>
				</div>
			<?php } ?>
            <table class="toggle">
                <tr class="table-headline-row">
                    <th style="width: 170px">Status</th>
                    <th style="width:auto">URL</th>
                    <th style="width:220px">Compared Screenshots</th>
                    <th style="width:100px">Difference</th>
                </tr>
                <?php
                // Change detections
                foreach ( $compares['data'] as $key => $compare ) {
                $next_comparison_token = false; // used in template
                if ( isset( $compares[ $key + 1 ] ) ) {
                    $next_comparison_token = $compares[ $key + 1 ]['token'];
                }
                $previous_comparison_token = false; // used in template
                if ( isset( $compares[ $key - 1 ] ) ) {
                    $previous_comparison_token = $compares[ $key - 1 ]['token'];
                }
                ?>
                <tr class="comparison_row"
                    data-url_id="<?php echo $compare['url']; ?>"
                    data-comparison_id="<?php echo $compare['id']; ?>"
                    onclick="ajaxShowChangeDetectionPopup('<?php echo $compare['token']; ?>','<?php echo $key; ?>', '<?php echo ( $compares['meta']['total'] ); ?>')"
                >
                    <td data-label="Status" style="order: 2">
                        <div class="status_container">
                            <span class="current_status"><?php echo prettyPrintComparisonStatus( $compare['status'], 'mm_inline_block' ); ?></span>
                        </div>
                    </td>
                    <td data-label="URL" style="order: 1">
                        <?php
                        if ( ! empty( $compare['html_title'] ) ) {
                            echo '<strong>' . $compare['html_title'] . '</strong><br>';
                        }
                        echo get_device_icon( $compare['device'] ) . $compare['url']; ?>
                    </td>

                    <td data-label="Compared Screenshots" style="order: 3">
                        <div class="screenshot-date local-time" data-date="<?php echo strtotime($compare['screenshot_1_created_at']); ?>">
                            <?php echo gmdate( 'd/m/Y H:i', strtotime($compare['screenshot_1_created_at'] )); ?>
                        </div>
                        <div class="screenshot-date local-time" data-date="<?php echo strtotime($compare['screenshot_2_created_at']); ?>">
                            <?php echo gmdate( 'd/m/Y H:i', strtotime($compare['screenshot_2_created_at']) ); ?>
                        </div>
                    </td>

                    <?php
                    $class = 'no-difference';
                    if ( $compare['difference_percent'] ) {
                        $class = 'is-difference';
                    }
                    ?>

                    <td class="diff-tile <?php echo $class; ?>"
                        data-diff_percent="<?php echo $compare['difference_percent']; ?>"
                        data-threshold="<?php echo $compare['threshold']; ?>"
						style="order: 4"
                    >
						<div class="mobile-label-difference" style="display: none" >Difference</div>
                        <?php echo $compare['difference_percent']; ?> %
                        <?php echo $compare['threshold'] > $compare['difference_percent'] ? '<div style="font-size: 10px">Threshold: ' . $compare['threshold'] . '%</div>' : ''; ?>
                    </td>

					<td style="order: 5; display: none;">
                        <button id="show-compare-<?php echo $key; ?>" onclick="jQuery(this).closest('.comparison_row').click();" class="et_pb_button">
                            Show
                        </button>
                    </td>
                </tr>
                <?php
                }
				

                ?>
            </table>

            <div class='pagination_container' style='margin-top: 30px; margin-left: 20px;'>
                <?php
                

                foreach ( $compares['meta']['links'] as $link ) {
                    // Parse the URL to get the query part
                    $parsedUrl = wp_parse_url($link['url'], PHP_URL_QUERY);

                    // Parse the query string into an array
                    wp_parse_str($parsedUrl, $queryParams);

                    // Get the 'page' parameter
                    $page = isset($queryParams['page']) ? $queryParams['page'] : null;

                    ?>
                    <button class="ajax_paginate_batch_comparisons et_pb_button"
                            style="padding-left: 15px !important; padding-right: 15px !important;"
                            data-filters='<?php echo json_encode($filters) ?>'
                            data-page="<?php echo $page; ?>"
                        <?php echo $link['active'] || is_null($page) ? ' disabled' : ''; ?>
                            onclick="return false;"
                    >
                        <?php echo $link['label']; ?>
                    </button>
                    <?php

                }
                echo "Total: " . $compares['meta']['total'] . " items"; ?>
            </div>
        </div>
        <?php
    }

    /**
     * Load failed queues view via AJAX.
     *
     * @param int $batch_id The batch ID to get failed queues for.
     * @return void
     */
    public function load_failed_queues_view($batch_id) {
        $failed_queues = Wp_Compare_API_V2::get_queues_v2([$batch_id], 'failed', ['per_page' => 100]);
        
        // Handle pagination for failed queues if needed.
        if(!empty($failed_queues['meta']['last_page']) && $failed_queues['meta']['last_page'] > 1) {
            for($i = 2; $i <= $failed_queues['meta']['last_page']; $i++) {
                $failed_queues_data = Wp_Compare_API_V2::get_queues_v2($batch_id, 'failed', ['per_page' => 100, 'page' => $i]);
                $failed_queues['data'] = array_merge($failed_queues['data'], $failed_queues_data['data']);
            }
        }

        if (empty($failed_queues['data'])) {
            echo '<div style="padding: 20px; text-align: center; color: #666;">No failed URLs found for this batch.</div>';
            return;
        }
        ?>
        <table class="toggle" style="margin: 0;">
            <tr class="table-headline-row">
                <th>Status</th>
                <th style="width:auto">URL</th>
                <th style="width:auto">Error Message</th>
            </tr>
            <?php
			
            foreach($failed_queues['data'] as $failed_queue) {
                if($batch_id === $failed_queue['batch']) { ?>
                    <tr style="background-color: rgba(220, 50, 50, 0.1);">
                        <td style="order: 2">
                            <div class="status_container">
                                <span class="current_status">
                                    <?php echo prettyPrintComparisonStatus( 'failed', 'mm_inline_block' ); ?>
                                </span>
                            </div>
                        </td>
                        <td style="order: 1;">
                            <?php
                            if ( ! empty( $failed_queue['html_title'] ) ) {
                                echo '<strong>' . $failed_queue['html_title'] . '</strong><br>';
                            }
                            echo get_device_icon( $failed_queue['device'] ) . $failed_queue['url_link'];
                            ?>
                        </td>
                        <td style="order: 3">
                            <?php echo $failed_queue['error_msg'] ?? 'Unknown error'; ?>
                        </td>
                    </tr>
                <?php }
            }
            ?>
        </table>
        <?php
    }
}

/**
 * Calling the API
 *
 * @param array args
 * @return string|null|array
 */
function mm_api( array $args, $api_token = null ) {
	$url = mm_get_api_domain() . str_replace( array( '_', 'client' ), array( '-', 'user' ), $args['action'] );

    // Get the right api-token
    $selected_api_token = get_user_meta(get_current_user_id(), "wcd_active_api_token", true);
    if(!$selected_api_token) {
        $selected_api_token = null;
    }
	$api_token = $api_token ?? $selected_api_token ?? mm_api_token();

    $action    = $args['action']; // save here for debugging. will not be used otherwise

	unset( $args['action'] );
	unset( $args['api_token'] ); // just in case

	// init
	$args = array(
		'timeout' => 30,
		'body'    => $args,
		'headers' => array(
			'Accept' => 'application/json',
		),
	);

	// add auth, if there's an api_token
	if ( ! empty( $api_token ) ) {
		$args['headers']['Authorization'] = 'Bearer ' . $api_token;
	}

	error_log( 'V1 request: ' . $url . ' | Args: ' . json_encode( $args['body'], 1 ) );
	$response = wp_remote_post( $url, $args );

	$responseCode = (int) wp_remote_retrieve_response_code( $response );
	error_log( 'Response Code: ' . $responseCode );
	$body = wp_remote_retrieve_body( $response );

	if ( ! mm_http_successful( $responseCode ) ) {
		// Don't post to Slack on local
		if ( in_array( mm_env(), array( 'staging', 'production' ) ) ) {
			$who = $_SERVER['REMOTE_ADDR']; // request IP

			if ( ! empty( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) {
				$who .= '/' . $_SERVER['HTTP_X_FORWARDED_FOR'];
			}

			$wpUserId = get_current_user_id();
			if ( ! empty( $wpUserId ) ) {
				$who .= ' (User #' . $wpUserId . ')';
			}

			// TODO make this sexy with blocks/attachments
			$slackArgs = array(
				'headers' => array(
					'Content-type' => 'application/json',
				),
				'body'    => json_encode(
					array(
						'text' => 'API Call by ' . $who . ' failed (' . $responseCode . '): ' . $url . ': ' . substr( $body, 0, $firstNChars = 200 ),
					)
				),
			);

			if ( ! ( $responseCode === HTTP_INTERNAL_SERVER_ERROR && $action === 'account_details' ) ) {
				@wp_remote_post( SLACK_WEBHOOK_URL, $slackArgs );
			}
		}

		if ( $responseCode === HTTP_INTERNAL_SERVER_ERROR && $action === 'account_details' ) {
			return 'activate account';
		}

		return null; // convention: if http response code not succesful, return null
	}

	if ( $action === 'get_groups_and_urls' ) {
		dd( $response );
	}

	if ( is_json( $body ) ) {
		return json_decode( $body, (bool) JSON_OBJECT_AS_ARRAY );
	}

	return $body;
}

if ( ! function_exists( 'mm_get_current_domain' ) ) {
	function mm_get_current_domain() {
		switch ( mm_env() ) {
			case 'production':
				return 'https://www.webchangedetector.com';
				break;
			case 'staging':
				return 'https://dev.webchangedetector.com';
				break;
			case 'local':
			default:
				return 'http://webchangedetector.test';
		}
	}
}

if ( ! function_exists( 'mm_app_domain' ) ) {
	function mm_app_domain() {
		return str_replace( 'www.', '', $_SERVER['SERVER_NAME'] );
	}
}
if ( ! function_exists( 'mm_get_billing_domain' ) ) {
	function mm_get_billing_domain() {
		switch ( mm_env() ) {
			case 'production':
				return 'https://billing.webchangedetector.com/';
				break;
			case 'staging':
				return 'https://dev.billing.webchangedetector.com/';
				break;
			case 'local':
			default:
				return 'http://dev.billing.webchangedetector.test/';
		}
	}
}
if ( ! function_exists( 'mm_get_api_domain' ) ) {
	function mm_get_api_domain() {
		switch ( mm_env() ) {
			case 'production':
				return 'https://api.webchangedetector.com/api/v1/';
				break;
			case 'staging':
				return 'https://dev.api.webchangedetector.com/api/v1/';
				break;
			case 'local':
			default:
				return 'http://api.webchangedetector.test/api/v1/';
		}
	}
}

if ( ! function_exists( 'mm_nonce' ) ) {
	/**
	 * Add a general nonce to requests
	 *
	 * @link    https://laternastudio.com?p=2110
	 * @return  void
	 */
	function mm_nonce() {
		$nonce = wp_create_nonce( 'webchangedetector_nonce' );
		echo "<meta name='csrf-token' content='$nonce'>";
	}
}

if ( ! function_exists( 'mm_verify_nonce' ) ) {
	/**
	 * Verify the submitted nonce
	 *
	 * @return  void
	 */
	function mm_verify_nonce() {
		$nonce = isset( $_SERVER['HTTP_X_CSRF_TOKEN'] )
			? $_SERVER['HTTP_X_CSRF_TOKEN']
			: '';

		if ( get_current_user_id() && ! wp_verify_nonce( $nonce, 'webchangedetector_nonce' ) ) {
			echo mm_message(
				array(
					'error',
					'Ooops! Something went wrong. Please reload the page and try again.
                <a href="' . $_SERVER['REQUEST_URI'] . '">Reload page</a><br>
                Error code: csrf-token could not be verified',
				)
			);
			die();
		}
	}
}

if ( ! function_exists( 'mm_env' ) ) {
	/**
	 * Returns environment
	 *
	 * @return string
	 */
	function mm_env() {
		switch ( $_SERVER['SERVER_NAME'] ) {
			case 'dev.webchangedetector.com':
			case 'www.dev.webchangedetector.com':
			case 'staging.webchangedetector.com':
			case 'www.staging.webchangedetector.com':
				return 'staging';

			case 'webchangedetector.com':
			case 'www.webchangedetector.com':
				return 'production';

			default:
			case 'webchangedetector.test':
				return 'local';
		}
	}
}

if ( ! function_exists( 'mm_http_successful' ) ) {
	/**
	 * HTTP Response Code in between 200 (incl) and 300
	 *
	 * @param int $httpCode
	 * @return bool
	 */
	function mm_http_successful( int $httpCode ): bool {
		return ( $httpCode >= HTTP_OK ) && ( $httpCode < HTTP_MULTIPLE_CHOICES );
	}
}

if ( ! function_exists( 'mm_message' ) ) {
	/**
	 * Prints out a given message as HTML (if array) or string (if string)
	 *
	 * @param string|array $message
	 */
	function mm_message( $message ) {
		if ( is_array( $message ) && isset( $message[0] ) && isset( $message[1] ) ) {
			return '<div class="message ' . $message[0] . '">' . $message[1] . '</div>';
		}
		return $message;
	}
}

if ( ! function_exists( 'mm_check_url' ) ) {
	/**
	 * Checks if a URL is accessible
	 */
	function mm_check_url( $url ) {
		$url = urldecode( $url );
		$ch  = curl_init( $url );
		curl_setopt( $ch, CURLOPT_TIMEOUT, 300 );
		curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );

		$result = curl_exec( $ch );

		if ( ! $result ) {
			return false;
		}
		return true;
	}
}

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

if ( ! function_exists( 'mm_get_domain' ) ) {
	function mm_get_domain( string $url ) {
		$url = str_replace( array( 'http://', 'https://' ), '', $url );
		if ( strpos( $url, '/' ) !== false ) {
			$url = substr( $url, 0, strpos( $url, '/' ) );
		}
		if ( strpos( $url, '?' ) !== false ) {
			$url = substr( $url, 0, strpos( $url, '?' ) );
		}
		return $url;
	}
}

if ( ! function_exists( 'is_json' ) ) {
	function is_json( $string ) {
		json_decode( $string );
		return ( json_last_error() == JSON_ERROR_NONE );
	}
}

function urlExists($url) {
	$ch = curl_init($url);
	//curl_setopt($ch, CURLOPT_NOBODY, true); // Only check the status, don't fetch content
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_TIMEOUT, 20); // Timeout after 10 seconds
	curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true); // Follow redirects
	curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (iPhone; CPU iPhone OS 14_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/14.0 Mobile/15E148 Safari/604.1');
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // For HTTPS requests, ignore SSL certificate verification
	curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0); // Ignore SSL host verification
	curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_2); // Force HTTP/2
	curl_setopt($ch, CURLOPT_HTTPHEADER, [
		'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
		'Accept-Language: en-US,en;q=0.5',
		'Accept-Encoding: gzip, deflate, br'
	]);

	curl_exec($ch);
	$httpCode = curl_getinfo($ch, CURLINFO_RESPONSE_CODE);

	curl_close($ch);

    if(!($httpCode >= 200 && $httpCode < 500)) {
        error_log("Error adding URL. Error code: " . $httpCode);
    }
	return ($httpCode >= 200 && $httpCode < 500); // Return true if the status code is 2xx or 3xx
}

if ( ! function_exists( 'mm_get_headers' ) ) {
	/**
	 * Gets headers from $url
	 *
	 * @param string $url
	 * @return array|bool
	 */
	function mm_get_headers( $url ) {
		$original_user_agent = ini_get( 'user_agent' );
		ini_set( 'user_agent', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/129.0.0.0 Safari/537.36' );
		if ( strpos( $url, 'http' ) === false ) {
			$url = 'http://' . $url;
		}

		stream_context_set_default( [
			'ssl' => [
				'verify_peer' => false,
				'verify_peer_name' => false,
			],
		]);
		$headers = get_headers( $url, 1 );

		ini_set( 'user_agent', $original_user_agent );

		return $headers;
	}
}

if ( ! function_exists( 'mm_api_token' ) ) {
	/**
	 * Returns API token string
	 *
	 * @return string|null
	 */
	function mm_api_token() {
        static $api_token;
        if($api_token) {
            return $api_token;
        }
		$api_token = get_user_meta( get_current_user_id(), USER_META_KEY_API_TOKEN, $singleValue = true );
        return $api_token;
	}
}

if ( ! function_exists( 'days_in_month' ) ) {
	/**
	 * How many days are in given month, defaults to current
	 *
	 * @source https://github.com/repat/php-helper/blob/master/src/date_helpers.php#L3
	 *
	 * @return int
	 */
	function days_in_month( $month = null, $year = null ): int {
		$month = $month ?? intval( gmdate( 'm' ) );
		$year  = $year ?? intval( gmdate( 'Y' ) );
		return cal_days_in_month( CAL_GREGORIAN, $month, $year );
	}
}

if ( ! function_exists( 'str_contains' ) ) {
	/**
	 * If string (`$haystack`) contains `$needle`
	 *
	 * @param string $haystack
	 * @param string $needle
	 * @return void
	 */
	function str_contains( $haystack, $needle ) {
		return strpos( strval( $haystack ), $needle ) !== false;
	}
}

if ( ! function_exists( 'starts_with' ) ) {
	/**
	 * Determine if a given string starts with a given substring.
	 *
	 * @source vendor/laravel/framework/src/Illuminate/Support/Str.php
	 *
	 * @param  string          $haystack
	 * @param  string|string[] $needles
	 * @return bool
	 */
	function starts_with( string $haystack, $needles ) {
		foreach ( (array) $needles as $needle ) {
			if ( (string) $needle !== '' && strncmp( $haystack, $needle, strlen( $needle ) ) === 0 ) {
				return true;
			}
		}

		return false;
	}
}

function get_small_loading_icon() {
	return plugin_dir_url( __FILE__ ) . './../public/img/loading.gif';
}

function prettyPrintComparisonStatus( $status, $addClass = '', $amountStatus = false ) {

	switch ( $status ) {
		case 'new':
			return '<div class="status_box new ' . $addClass . '">New ' . ($amountStatus ? ' | ' . $amountStatus . ' ' : '') . '</div>';
		case 'ok':
			return '<div class="status_box ok ' . $addClass . '">Ok ' . ($amountStatus ? ' | ' . $amountStatus . ' ' : '') . '</div>';
		case 'to_fix':
			return '<div class="status_box to_fix ' . $addClass . '">To fix ' . ($amountStatus ? ' | ' . $amountStatus . ' ' : '') . '</div>';
		case 'false_positive':
			return '<div class="status_box false_positive ' . $addClass . '">False positive ' . ($amountStatus ? ' | ' . $amountStatus . ' ' : '') . '</div>';
        case 'failed':
			return '<div class="status_box failed ' . $addClass . '">Failed ' . ($amountStatus ? ' | ' . $amountStatus . ' ' : '') . '</div>';
		default:
			return '<div class="status_box none ' . $addClass . '">None ' . ($amountStatus ? ' | ' . $amountStatus . ' ' : '') . '</div>';
	}
}

if ( ! function_exists( 'get_device_icon' ) ) {
	/**
	 * Returns `<span>` with CSS icon and potentially another class
	 *
	 * TODO Make this switch-case
	 *
	 * @param string $icon
	 * @param string $class
	 * @return string
	 */
	function get_device_icon( $icon, $class = '' ) {
		$icons = array(
			'thumbnail'         => 'dashicons dashicons-admin-page',
			'desktop'           => 'dashicons dashicons-desktop',
			'mobile'            => 'dashicons dashicons-smartphone',
			'assign'            => 'dashicons dashicons-yes',
			'page'              => 'dashicons dashicons-media-default',
			'wordpress'         => 'dashicons dashicons-wordpress',
			'general'           => 'dashicons dashicons-admin-site-alt3',
			'change-detections' => 'dashicons dashicons-welcome-view-site',
			'dashboard'         => 'dashicons dashicons-admin-home',
			'logs'              => 'dashicons dashicons-menu-alt',
			'settings'          => 'dashicons dashicons-admin-generic',
			'website-settings'  => 'dashicons dashicons-welcome-widgets-menus',
			'help'              => 'dashicons dashicons-editor-help',
			'add'               => 'dashicons dashicons-plus',
			'edit'              => 'dashicons dashicons-edit-large',
			'remove'            => 'dashicons dashicons-trash',
			'play'              => 'dashicons dashicons-controls-play',
			'pause'             => 'dashicons dashicons-controls-pause',
			'add-url'           => 'dashicons dashicons-welcome-add-page',
			'check'             => 'dashicons dashicons-yes',
			'check-circle'      => 'dashicons dashicons-yes-alt',
			'users'             => 'dashicons dashicons-admin-users',
			'code'              => 'dashicons dashicons-editor-code',
			'warning'           => 'dashicons dashicons-warning',
			'subaccounts'       => 'dashicons dashicons-groups',
		);

		$iconClass = $icons[ strtolower($icon) ] ?? ''; // Use nullish coalescing operator for default
		return '<span class="group_icon ' . $iconClass . ' ' . $class . '"></span>';
	}
}

if ( ! function_exists( 'get_group_class' ) ) {
	/**
	 * Returns CSS class depending on `monitoring`
	 *
	 * NOTE Only called by `get_group_icon`
	 */
	function get_group_class( $group ) {
		$class = 'group_icon dashicons '; // init
		if ( $group['monitoring'] ) {
			// $class .= 'monitoring dashicons-visibility';
			return $class . 'monitoring dashicons-clock';
		}

		// $class .= 'manual dashicons-format-gallery';
		return $class . 'manual dashicons-admin-page';
	}
}

if ( ! function_exists( 'get_group_icon' ) ) {
	function get_group_icon( $group ) {
		return '<span class="' . get_group_class( $group ) . '"></span>';
	}
}

if ( ! defined( 'HTTP_OK' ) ) {
	define( 'HTTP_OK', 200 );
}
if ( ! defined( 'MM_APP_PATH' ) ) {
	define( 'MM_APP_PATH', '/webchangedetector/' );
}
if ( ! defined( 'MM_APP_URL_PRODUCTION' ) ) {
	define( 'MM_APP_URL_PRODUCTION', 'https://www.webchangedetector.com' );
}
if ( ! defined( 'MM_CHANGE_DETECTION_URL' ) ) {
	define( 'MM_CHANGE_DETECTION_URL', '/show-change-detection' );
}
if ( ! defined( 'MM_EMAIL_FEEDBACK' ) ) {
	define( 'MM_EMAIL_FEEDBACK', 'mike@webchangedetector.com' );
}
if ( ! defined( 'HTTP_INTERNAL_SERVER_ERROR' ) ) {
	define( 'HTTP_INTERNAL_SERVER_ERROR', 500 );
}
if ( ! defined( 'HTTP_MULTIPLE_CHOICES' ) ) {
	define( 'HTTP_MULTIPLE_CHOICES', 300 );
}
if ( ! defined( 'SLACK_WEBHOOK_URL' ) ) {
	define( 'SLACK_WEBHOOK_URL', 'https://hooks.slack.com/services/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' );
}
if ( ! defined( 'SLACK_WEBHOOK_URL_NOTIFICATION_DUMP' ) ) {
	define( 'SLACK_WEBHOOK_URL_NOTIFICATION_DUMP', 'https://hooks.slack.com/services/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' );
}
if ( ! defined( 'PERCENT_MULTIPLIER' ) ) {
	define( 'PERCENT_MULTIPLIER', 100 );
}
if ( ! defined( 'HOURS_IN_DAY' ) ) {
	define( 'HOURS_IN_DAY', 24 );
}
if ( ! defined( 'DAYS_PER_MONTH' ) ) {
	define( 'DAYS_PER_MONTH', 30 );
}
if ( ! defined( 'TRIAL_ACCOUNT_ID' ) ) {
	define( 'TRIAL_ACCOUNT_ID', 8 );
}
if ( ! defined( 'MONTH_IN_SECONDS' ) ) {
	/**
	 * 60*60*24*30, so technically not every month
	 *
	 * @source https://github.com/stevegrunwell/time-constants/blob/develop/constants.php#L43
	 */
	define( 'MONTH_IN_SECONDS', 2592000 );
}
if ( ! defined( 'CMS_WORDPRESS' ) ) {
	define( 'CMS_WORDPRESS', 'wordpress' );
}
if ( ! defined( 'USER_META_KEY_API_TOKEN' ) ) {
	define( 'USER_META_KEY_API_TOKEN', 'wpcompare_api_token' );
}

/*
if (! defined('USER_META_WP_GROUP_POST_TYPES')) {
	define('USER_META_WP_GROUP_POST_TYPES', 'webchangedetector_sync_wp_post_types');
}*/

// Plan IDs
if ( ! defined( 'WCD_FREE_PLAN_ID' ) ) {
	define( 'WCD_FREE_PLAN_ID', '1' );
}
if ( ! defined( 'WCD_FREE_PLAN' ) ) {
	define( 'WCD_FREE_PLAN', 'free' );
}
if ( ! defined( 'WCD_PERSONAL_PLAN_ID' ) ) {
	define( 'WCD_PERSONAL_PLAN_ID', '2' );
}
if ( ! defined( 'WCD_TRIAL_PLAN_ID' ) ) {
	define( 'WCD_TRIAL_PLAN_ID', '8' );
}
if ( ! defined( 'WCD_TRIAL_PLAN' ) ) {
	define( 'WCD_TRIAL_PLAN', 'trial' );
}

// Steps in update change detection
if ( ! defined( 'WCD_OPTION_UPDATE_GROUP_IDS' ) ) {
	define( 'WCD_OPTION_UPDATE_GROUP_IDS', 'webchangedetector_update_group_ids' );
}
if ( ! defined( 'WCD_OPTION_UPDATE_SC_TYPE' ) ) {
	define( 'WCD_OPTION_UPDATE_SC_TYPE', 'webchangedetector_update_sc_type' );
}
if ( ! defined( 'WCD_OPTION_UPDATE_CMS_FILTER' ) ) {
	define( 'WCD_OPTION_UPDATE_CMS_FILTER', 'webchangedetector_update_cms_filter' );
}
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

if ( ! defined( 'WCD_ONE_TIME_PLANS' ) ) {
	define( 'WCD_ONE_TIME_PLANS', [
            'trial',
    ] );
}

if ( ! defined( 'WCD_REQUEST_TIMEOUT' ) ) {
	define( 'WCD_REQUEST_TIMEOUT', '300' );
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




