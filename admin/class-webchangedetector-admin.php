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

namespace WebChangeDetector;

/** WCD Admin Class.
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
		'create_trial_account',
		'update_detection_step',
		'add_post_type',
		'filter_change_detections',
		'change_comparison_status',
		'disable_wizard',
		'start_manual_checks',
		'sync_urls',
		'save_admin_bar_setting',
	);

	const VALID_SC_TYPES = array(
		'pre',
		'post',
		'auto',
		'compare',
	);

	const VALID_GROUP_TYPES = array(
		'all', // Filter.
		'generic', // Filter.
		'wordpress', // Filter.
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
	public $sync_urls;

	/**
	 * Screenshots handler instance.
	 *
	 * @since    1.0.0
	 * @access   public
	 * @var      WebChangeDetector_Admin_Screenshots $screenshots_handler Screenshots management.
	 */
	public $screenshots_handler;

	/**
	 * API Manager instance.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      WebChangeDetector_API_Manager $api_manager API communication handler.
	 */
	private $api_manager;

	/**
	 * Account handler instance.
	 *
	 * @since    1.0.0
	 * @access   public
	 * @var      WebChangeDetector_Admin_Account $account_handler Account management.
	 */
	public $account_handler;

	/**
	 * WordPress integration handler instance.
	 *
	 * @since    1.0.0
	 * @access   public
	 * @var      WebChangeDetector_Admin_WordPress $wordpress_handler WordPress integration.
	 */
	public $wordpress_handler;

	/**
	 * Settings handler instance.
	 *
	 * @since    1.0.0
	 * @access   public
	 * @var      WebChangeDetector_Admin_Settings $settings_handler Settings management.
	 */
	public $settings_handler;

	/**
	 * Dashboard handler instance.
	 *
	 * @since    1.0.0
	 * @access   public
	 * @var      WebChangeDetector_Admin_Dashboard $dashboard_handler Dashboard management.
	 */
	public $dashboard_handler;
	/**
	 * Initialize the class and set its properties.
	 *
	 * @param string $plugin_name The name of this plugin.
	 * @since    1.0.0
	 */
	public function __construct( $plugin_name = 'WebChangeDetector' ) {
		$this->plugin_name = $plugin_name;
		
		// Set the group uuids.
		$this->monitoring_group_uuid = get_option( WCD_WEBSITE_GROUPS )[ WCD_AUTO_DETECTION_GROUP ] ?? false;
		$this->manual_group_uuid     = get_option( WCD_WEBSITE_GROUPS )[ WCD_MANUAL_DETECTION_GROUP ] ?? false;

		// Initialize sync_urls array.
		$this->sync_urls = array();

		// Initialize specialized handlers.
		$this->api_manager = new WebChangeDetector_API_Manager();
		$this->account_handler = new WebChangeDetector_Admin_Account( $this->api_manager );
		$this->wordpress_handler = new WebChangeDetector_Admin_WordPress( $this->plugin_name, $this->version, $this );
		$this->screenshots_handler = new WebChangeDetector_Admin_Screenshots( $this );
		$this->settings_handler = new WebChangeDetector_Admin_Settings( $this );
		$this->dashboard_handler = new WebChangeDetector_Admin_Dashboard( $this, $this->api_manager, $this->wordpress_handler );
		
		// Add cron job for daily sync (after WordPress handler is initialized).
		add_action( 'wcd_daily_sync_event', array( $this->wordpress_handler, 'daily_sync_posts_cron_job' ) );
		if ( ! wp_next_scheduled( 'wcd_daily_sync_event' ) ) {
			wp_schedule_event( time(), 'daily', 'wcd_daily_sync_event' );
		}
	}



	/** Website details.
	 *
	 * @var array $website_details Array with website details.
	 */
	public $website_details;



	/**
	 * Get the domain from wp site_url.
	 *
	 * @return string
	 */
	public static function get_domain_from_site_url() {
		return rtrim( preg_replace( '(^https?://)', '', get_site_url() ?? '' ), '/' ); // Site might be in subdir.
	}
	

	/** Get queues for status processing and open.
	 *
	 * @param string $batch_id The batch id.
	 * @param int    $per_page Rows per page.
	 * @return array
	 */
	public function get_processing_queue_v2( $batch_id = false, $per_page = 30 ) {
		return \WebChangeDetector\WebChangeDetector_API_V2::get_queues_v2( $batch_id, 'processing,open', false, array( 'per_page' => $per_page ) );
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
		$domain = self::get_domain_from_site_url();
		
		// Create monitoring group.
		$monitoring_group_args = array(
			'name'        => $domain,
			'monitoring'  => true,
			'enabled'     => true,
		);
		
		$monitoring_group_response = \WebChangeDetector\WebChangeDetector_API_V2::create_group_v2( $monitoring_group_args );
		
		// Create manual checks group.
		$manual_group_args = array(
			'name'       => $domain,
			'monitoring' => false,
			'enabled'    => true,
		);
		
		$manual_group_response = \WebChangeDetector\WebChangeDetector_API_V2::create_group_v2( $manual_group_args );
		
		// Check if both groups were created successfully.
		if ( ! empty( $monitoring_group_response['data']['id'] ) && ! empty( $manual_group_response['data']['id'] ) ) {
			// Create the website with the group IDs.
			$website_response = \WebChangeDetector\WebChangeDetector_API_V2::create_website_v2(
				$domain,
				$manual_group_response['data']['id'],
				$monitoring_group_response['data']['id']
			);
			
			// Check if website was created successfully.
            if ( ! empty( $website_response['data']['id'] ) ) {
                // Save group IDs to wp_options.
                $groups = array(
                    WCD_AUTO_DETECTION_GROUP   => $monitoring_group_response['data']['id'],
                    WCD_MANUAL_DETECTION_GROUP => $manual_group_response['data']['id'],
                );
                
                update_option( WCD_WEBSITE_GROUPS, $groups, false );

                // Directly set the group IDs to the class properties.
                $this->monitoring_group_uuid = $monitoring_group_response['data']['id'];
                $this->manual_group_uuid = $manual_group_response['data']['id'];
                
                // Ensure website details include default settings to avoid unnecessary API calls later.
                $website_data = $website_response['data'];
                
                // Set default sync types if not present.
                if ( empty( $website_data['sync_url_types'] ) ) {
                    $website_data['sync_url_types'] = array(
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
                    );
                }
                
                // Set default auto update settings if not present.
                if ( empty( $website_data['auto_update_settings'] ) ) {
                    $website_data['auto_update_settings'] = array(
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
                }

                // Return success response with complete website data.
                return array(
                    'website'          => $website_data,
                    'monitoring_group' => $monitoring_group_response['data'],
                    'manual_group'     => $manual_group_response['data'],
                );
            } else {
                // Return error if website couldn't be created.
                return array(
                    'error'            => 'Failed to create website',
                    'website_response' => $website_response,
                );
            }
        }
        
		
        // Return error if groups couldn't be created.
        return array(
            'error'              => 'Failed to create groups',
            'monitoring_response' => $monitoring_group_response,
            'manual_response'     => $manual_group_response,
        );
	}

	/** Get params of an url.
	 *
	 * @param string $url The url.
	 * @return array|false
	 */


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
		$days_until_renewal = gmdate( 'd', gmdate( 'U', strtotime( $this->account_handler->get_account()['renewal_at'] ) ) - gmdate( 'U' ) );

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
		\WebChangeDetector\WebChangeDetector_API_V2::update_urls_in_group_v2( $group_id_website_details, $active_posts );

		// TODO Make return to show the result.
		echo '<div class="updated notice"><p>Settings saved.</p></div>';
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
				<?php if ( $this->settings_handler->is_allowed( 'dashboard_view' ) ) { ?>
				<a href="?page=webchangedetector"
					class="nav-tab <?php echo 'webchangedetector' === $active_tab ? 'nav-tab-active' : ''; ?>">
					<?php \WebChangeDetector\WebChangeDetector_Admin_Utils::get_device_icon( 'dashboard' ); ?> Dashboard
				</a>
				<?php } ?>
				<?php if ( $this->settings_handler->is_allowed( 'manual_checks_view' ) ) { ?>
				<a href="?page=webchangedetector-update-settings"
					class="nav-tab <?php echo 'webchangedetector-update-settings' === $active_tab ? 'nav-tab-active' : ''; ?>">
					<?php \WebChangeDetector\WebChangeDetector_Admin_Utils::get_device_icon( 'update-group' ); ?> Manual Checks & Auto Update Checks
				</a>
				<?php } ?>
				<?php if ( $this->settings_handler->is_allowed( 'monitoring_checks_view' ) ) { ?>
					<a href="?page=webchangedetector-auto-settings"
					class="nav-tab <?php echo 'webchangedetector-auto-settings' === $active_tab ? 'nav-tab-active' : ''; ?>">
					<?php \WebChangeDetector\WebChangeDetector_Admin_Utils::get_device_icon( 'auto-group' ); ?> Monitoring
				</a>
				<?php } ?>
				<?php if ( $this->settings_handler->is_allowed( 'change_detections_view' ) ) { ?>
					<a href="?page=webchangedetector-change-detections"
					class="nav-tab <?php echo 'webchangedetector-change-detections' === $active_tab ? 'nav-tab-active' : ''; ?>">
					<?php \WebChangeDetector\WebChangeDetector_Admin_Utils::get_device_icon( 'change-detections' ); ?> Change Detections
				</a>
				<?php } ?>
				<?php if ( $this->settings_handler->is_allowed( 'logs_view' ) ) { ?>
				<a href="?page=webchangedetector-logs"
					class="nav-tab <?php echo 'webchangedetector-logs' === $active_tab ? 'nav-tab-active' : ''; ?>">
					<?php \WebChangeDetector\WebChangeDetector_Admin_Utils::get_device_icon( 'logs' ); ?> Queue
				</a>
				<?php } ?>
				<?php if ( $this->settings_handler->is_allowed( 'settings_view' ) ) { ?>
				<a href="?page=webchangedetector-settings"
					class="nav-tab <?php echo 'webchangedetector-settings' === $active_tab ? 'nav-tab-active' : ''; ?>">
					<?php \WebChangeDetector\WebChangeDetector_Admin_Utils::get_device_icon( 'settings' ); ?> Settings
				</a>
				<?php } ?>
				<?php if ( $this->settings_handler->is_allowed( 'upgrade_account' ) ) { ?>
				<a href="<?php echo esc_url( $this->account_handler->get_upgrade_url() ); ?>" target="_blank"
					class="nav-tab upgrade">
					<?php \WebChangeDetector\WebChangeDetector_Admin_Utils::get_device_icon( 'upgrade' ); ?> Upgrade Account
				</a>
				<?php } ?>
			</h2>
		</div>

		<?php
	}

	/** Get group details and its urls.
	 *
	 * @param string $group_uuid The group id.
	 * @param array  $url_filter Filters for the urls.
	 *
	 * @return mixed
	 */
	public function get_group_and_urls( $group_uuid, $url_filter = array() ) {

		$group_and_urls = \WebChangeDetector\WebChangeDetector_API_V2::get_group_v2( $group_uuid )['data'];
		$urls           = \WebChangeDetector\WebChangeDetector_API_V2::get_group_urls_v2( $group_uuid, $url_filter );

		if ( empty( $urls['data'] ) ) {
			$this->wordpress_handler->sync_posts( true );
			$urls = \WebChangeDetector\WebChangeDetector_API_V2::get_group_urls_v2( $group_uuid, $url_filter );
		}

		$group_and_urls['urls']                = $urls['data'];
		$group_and_urls['meta']                = $urls['meta'];
		$group_and_urls['selected_urls_count'] = $urls['meta']['selected_urls_count'];

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

		\WebChangeDetector\WebChangeDetector_Admin_Utils::log_error( 'API V1 request: ' . $url . ' | Args: ' . wp_json_encode( $args ) );
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



} // End class WebChangeDetector_Admin.

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



