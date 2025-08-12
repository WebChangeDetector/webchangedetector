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
class WebChangeDetector_Admin
{

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
        'save_debug_logging_setting',
        'download_log_file',
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
     * View renderer instance.
     *
     * @since    1.0.0
     * @access   public
     * @var      WebChangeDetector_View_Renderer $view_renderer View rendering handler.
     */
    public $view_renderer;

    /**
     * Screenshot action handler instance.
     *
     * @since    1.0.0
     * @access   public
     * @var      WebChangeDetector_Screenshot_Action_Handler $screenshot_action_handler Screenshot actions.
     */
    public $screenshot_action_handler;

    /**
     * Settings action handler instance.
     *
     * @since    1.0.0
     * @access   public
     * @var      WebChangeDetector_Settings_Action_Handler $settings_action_handler Settings actions.
     */
    public $settings_action_handler;

    /**
     * Account action handler instance.
     *
     * @since    1.0.0
     * @access   public
     * @var      WebChangeDetector_Account_Action_Handler $account_action_handler Account actions.
     */
    public $account_action_handler;

    /**
     * WordPress action handler instance.
     *
     * @since    1.0.0
     * @access   public
     * @var      WebChangeDetector_WordPress_Action_Handler $wordpress_action_handler WordPress actions.
     */
    public $wordpress_action_handler;

    /**
     * Comparison action handler instance.
     *
     * @since    1.0.0
     * @access   public
     * @var      WebChangeDetector_Comparison_Action_Handler $comparison_action_handler Comparison actions.
     */
    public $comparison_action_handler;

    /**
     * Component manager instance.
     *
     * @since    1.0.0
     * @access   public
     * @var      WebChangeDetector_Component_Manager $component_manager Component management.
     */
    public $component_manager;

    /**
     * Error handler instance.
     *
     * @since    1.0.0
     * @access   public
     * @var      WebChangeDetector_Error_Handler $error_handler Error handling.
     */
    public $error_handler;

    /**
     * Logger instance.
     *
     * @since    1.0.0
     * @access   public
     * @var      WebChangeDetector_Logger $logger Logging system.
     */
    public $logger;

    /**
     * Error recovery instance.
     *
     * @since    1.0.0
     * @access   public
     * @var      WebChangeDetector_Error_Recovery $error_recovery Error recovery.
     */
    public $error_recovery;

    /**
     * User feedback instance.
     *
     * @since    1.0.0
     * @access   public
     * @var      WebChangeDetector_User_Feedback $user_feedback User feedback.
     */
    public $user_feedback;
    /**
     * Initialize the class and set its properties.
     *
     * @param string $plugin_name The name of this plugin.
     * @since    1.0.0
     */
    public function __construct($plugin_name = 'WebChangeDetector')
    {
        $this->plugin_name = $plugin_name;

        // Ensure required constants are defined before accessing them.
        $this->ensure_required_constants();

        // Set the group uuids.
        $this->monitoring_group_uuid = get_option(WCD_WEBSITE_GROUPS)[WCD_AUTO_DETECTION_GROUP] ?? false;
        $this->manual_group_uuid     = get_option(WCD_WEBSITE_GROUPS)[WCD_MANUAL_DETECTION_GROUP] ?? false;

        // Initialize sync_urls array.
        $this->sync_urls = array();

        // Initialize specialized handlers.
        $this->account_handler = new WebChangeDetector_Admin_Account();
        $this->wordpress_handler = new WebChangeDetector_Admin_WordPress($this->plugin_name, $this->version, $this);
        $this->screenshots_handler = new WebChangeDetector_Admin_Screenshots($this);
        $this->settings_handler = new WebChangeDetector_Admin_Settings($this);
        $this->dashboard_handler = new WebChangeDetector_Admin_Dashboard($this, $this->wordpress_handler);
        $this->view_renderer = new WebChangeDetector_View_Renderer($this);

        // Initialize action handlers.
        $this->screenshot_action_handler = new WebChangeDetector_Screenshot_Action_Handler($this);
        $this->settings_action_handler = new WebChangeDetector_Settings_Action_Handler($this);
        $this->account_action_handler = new WebChangeDetector_Account_Action_Handler($this);
        $this->wordpress_action_handler = new WebChangeDetector_WordPress_Action_Handler($this);
        $this->comparison_action_handler = new WebChangeDetector_Comparison_Action_Handler($this);
        $this->component_manager = new WebChangeDetector_Component_Manager();

        // Initialize error handling components.
        $this->logger = new WebChangeDetector_Logger();
        $this->error_handler = new WebChangeDetector_Error_Handler($this->logger);
        $this->error_recovery = new WebChangeDetector_Error_Recovery($this->logger);
        $this->user_feedback = new WebChangeDetector_User_Feedback();

        // Add cron job for daily sync (after WordPress handler is initialized).
        add_action('wcd_daily_sync_event', array($this->wordpress_handler, 'daily_sync_posts_cron_job'));
        if (! wp_next_scheduled('wcd_daily_sync_event')) {
            wp_schedule_event(time(), 'daily', 'wcd_daily_sync_event');
        }
    }

    /**
     * Ensure required constants are defined for group operations.
     *
     * @since    1.0.0
     * @return   void
     */
    private function ensure_required_constants()
    {
        if (! defined('WCD_WEBSITE_GROUPS')) {
            define('WCD_WEBSITE_GROUPS', 'wcd_website_groups');
        }
        if (! defined('WCD_MANUAL_DETECTION_GROUP')) {
            define('WCD_MANUAL_DETECTION_GROUP', 'manual_detection_group');
        }
        if (! defined('WCD_AUTO_DETECTION_GROUP')) {
            define('WCD_AUTO_DETECTION_GROUP', 'auto_detection_group');
        }
        if (! defined('WCD_VERIFY_SECRET')) {
            define('WCD_VERIFY_SECRET', 'webchangedetector_verify_secret');
        }
        if (! defined('WCD_WP_OPTION_KEY_API_TOKEN')) {
            define('WCD_WP_OPTION_KEY_API_TOKEN', 'webchangedetector_api_token');
        }
    }

    /** Website details.
     *
     * @var array $website_details Array with website details.
     */
    public $website_details;




    /** Get queues for status processing and open.
     *
     * @param string $batch_id The batch id.
     * @param int    $per_page Rows per page.
     * @return array
     */
    public function get_processing_queue_v2($batch_id = false, $per_page = 30)
    {
        return \WebChangeDetector\WebChangeDetector_API_V2::get_queues_v2($batch_id, 'processing,open', false, array('per_page' => $per_page));
    }






    /**
     * Creates Websites and Groups
     *
     * NOTE API Token needs to be sent here because it's not in the options yet
     * at Website creation
     *
     * @return array
     */
    public function create_website_and_groups()
    {
        $domain = WebChangeDetector_Admin_Utils::get_domain_from_site_url();

        // Create monitoring group.
        $monitoring_group_args = array(
            'name'        => $domain,
            'monitoring'  => true,
            'enabled'     => true,
        );

        $monitoring_group_response = \WebChangeDetector\WebChangeDetector_API_V2::create_group_v2($monitoring_group_args);

        // Create manual checks group.
        $manual_group_args = array(
            'name'       => $domain,
            'monitoring' => false,
            'enabled'    => true,
        );

        $manual_group_response = \WebChangeDetector\WebChangeDetector_API_V2::create_group_v2($manual_group_args);

        // Check if both groups were created successfully.
        if (! empty($monitoring_group_response['data']['id']) && ! empty($manual_group_response['data']['id'])) {
            // Create the website with the group IDs.
            $website_response = \WebChangeDetector\WebChangeDetector_API_V2::create_website_v2(
                $domain,
                $manual_group_response['data']['id'],
                $monitoring_group_response['data']['id']
            );

            // Check if website was created successfully.
            if (! empty($website_response['data']['id'])) {
                // Save group IDs to wp_options.
                $groups = array(
                    WCD_AUTO_DETECTION_GROUP   => $monitoring_group_response['data']['id'],
                    WCD_MANUAL_DETECTION_GROUP => $manual_group_response['data']['id'],
                );

                update_option(WCD_WEBSITE_GROUPS, $groups, false);

                // Directly set the group IDs to the class properties.
                $this->monitoring_group_uuid = $monitoring_group_response['data']['id'];
                $this->manual_group_uuid = $manual_group_response['data']['id'];

                // Ensure website details include default settings to avoid unnecessary API calls later.
                $website_data = $website_response['data'];

                // Set default sync types if not present.
                if (empty($website_data['sync_url_types'])) {
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
                if (empty($website_data['auto_update_settings'])) {
                    $website_data['auto_update_settings'] = array(
                        'auto_update_checks_enabled'   => true,
                        'auto_update_checks_from'      => gmdate('H:i'),
                        'auto_update_checks_to'        => gmdate('H:i', strtotime('+12 hours')),
                        'auto_update_checks_monday'    => true,
                        'auto_update_checks_tuesday'   => true,
                        'auto_update_checks_wednesday' => true,
                        'auto_update_checks_thursday'  => true,
                        'auto_update_checks_friday'    => true,
                        'auto_update_checks_saturday'  => false,
                        'auto_update_checks_sunday'    => false,
                        'auto_update_checks_emails'    => get_option('admin_email'),
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

    /** Print the monitoring status bar.
     *
     * @param array $group The group details.
     * @return void
     */
    public function print_monitoring_status_bar($group)
    {
        // Ensure we have group data with default values.
        $group = array_merge(array(
            'interval_in_h' => 24,
            'hour_of_day' => 0,
            'selected_urls_count' => 0,
        ), $group ?? array());

        // Calculation for monitoring.
        $date_next_sc = false;

        $amount_sc_per_day = 0;

        // Check for intervals >= 1h.
        if ($group['interval_in_h'] >= 1) {
            $next_possible_sc  = gmmktime(gmdate('H') + 1, 0, 0, gmdate('m'), gmdate('d'), gmdate('Y'));
            $amount_sc_per_day = (24 / $group['interval_in_h']);
            $possible_hours    = array();

            // Get possible tracking hours.
            for ($i = 0; $i <= $amount_sc_per_day * 2; $i++) {
                $possible_hour    = $group['hour_of_day'] + $i * $group['interval_in_h'];
                $possible_hours[] = $possible_hour >= 24 ? $possible_hour - 24 : $possible_hour;
            }
            sort($possible_hours);

            // Check for today and tomorrow.
            for ($ii = 0; $ii <= 1; $ii++) { // Do 2 loops for today and tomorrow.
                for ($i = 0; $i <= $amount_sc_per_day * 2; $i++) {
                    $possible_time = gmmktime($possible_hours[$i], 0, 0, gmdate('m'), gmdate('d') + $ii, gmdate('Y'));

                    if ($possible_time >= $next_possible_sc) {
                        $date_next_sc = $possible_time; // This is the next possible time. So we break here.
                        break;
                    }
                }

                // Don't check for tomorrow if we found the next date today.
                if ($date_next_sc) {
                    break;
                }
            }
        }

        // Check for 30 min intervals.
        if (0.5 === $group['interval_in_h']) {
            $amount_sc_per_day = 48;
            if (gmdate('i') < 30) {
                $date_next_sc = gmmktime(gmdate('H'), 30, 0, gmdate('m'), gmdate('d'), gmdate('Y'));
            } else {
                $date_next_sc = gmmktime(gmdate('H') + 1, 0, 0, gmdate('m'), gmdate('d'), gmdate('Y'));
            }
        }
        // Check for 15 min intervals.
        if (0.25 === $group['interval_in_h']) {
            $amount_sc_per_day = 96;
            if (gmdate('i') < 15) {
                $date_next_sc = gmmktime(gmdate('H'), 15, 0, gmdate('m'), gmdate('d'), gmdate('Y'));
            } elseif (gmdate('i') < 30) {
                $date_next_sc = gmmktime(gmdate('H'), 30, 0, gmdate('m'), gmdate('d'), gmdate('Y'));
            } elseif (gmdate('i') < 45) {
                $date_next_sc = gmmktime(gmdate('H'), 45, 0, gmdate('m'), gmdate('d'), gmdate('Y'));
            } else {
                $date_next_sc = gmmktime(gmdate('H') + 1, 0, 0, gmdate('m'), gmdate('d'), gmdate('Y'));
            }
        }

        // Calculate screenshots until renewal.
        $days_until_renewal = gmdate('d', gmdate('U', strtotime($this->account_handler->get_account()['renewal_at'])) - gmdate('U'));

        $amount_group_sc_per_day = $group['selected_urls_count'] * $amount_sc_per_day * $days_until_renewal;

        // Get first detection hour.
        $first_hour_of_interval = $group['hour_of_day'];
        while ($first_hour_of_interval - $group['interval_in_h'] >= 0) {
            $first_hour_of_interval = $first_hour_of_interval - $group['interval_in_h'];
        }

        // Count up in interval_in_h to current hour.
        $skip_sc_count_today = 0;
        while ($first_hour_of_interval + $group['interval_in_h'] <= gmdate('H')) {
            $first_hour_of_interval = $first_hour_of_interval + $group['interval_in_h'];
            ++$skip_sc_count_today;
        }

        // Subtract screenshots already taken today.
        $total_sc_current_period = $amount_group_sc_per_day - $skip_sc_count_today * $group['selected_urls_count'];
?>

        <div class="wcd-settings-card wcd-monitoring-status-card">
            <div class="wcd-monitoring-status-header">
                <h3><span class="dashicons dashicons-clock"></span> Monitoring Status</h3>
            </div>
            <div class="wcd-monitoring-status-content">
                <div class="wcd-next-check-container">
                    <div id="txt_next_sc_in" class="wcd-status-label">Next monitoring checks in</div>
                    <div id="next_sc_in" class="wcd-status-value"></div>
                    <div id="next_sc_date" class="wcd-status-date local-time" data-date="<?php echo esc_html($date_next_sc); ?>"></div>
                </div>
                <div class="wcd-monitoring-stats">
                    <div class="wcd-stat-item">
                        <span class="wcd-stat-label"><?php _e('Selected URLs', 'webchangedetector'); ?></span>
                        <span class="wcd-stat-value"><?php echo esc_html($group['selected_urls_count']); ?></span>
                    </div>
                    <div class="wcd-stat-item">
                        <span class="wcd-stat-label">Check Interval</span>
                        <span class="wcd-stat-value"><?php echo esc_html($group['interval_in_h']); ?>h</span>
                    </div>
                </div>
                <div id="sc_available_until_renew"
                    data-amount_selected_urls="<?php echo esc_html($group['selected_urls_count']); ?>"
                    data-auto_sc_per_url_until_renewal="<?php echo esc_html($total_sc_current_period); ?>" style="display: none;"></div>
            </div>
        </div>
<?php
    }



    /** Save url settings.
     *
     * Handles saving URL selections for groups (monitoring or manual checks).
     * Updates the selected URLs for desktop and mobile views through the API.
     *
     * @param array $postdata The postdata containing URL selections.
     *
     * @return void
     */
    public function post_urls($postdata)
    {
        $active_posts   = array();
        $count_selected = 0;

        foreach ($postdata as $key => $post) {
            $already_processed_ids = array();
            if (0 === strpos($key, 'desktop-') || 0 === strpos($key, 'mobile-')) {

                $post_id = 0 === strpos($key, 'desktop-') ? substr($key, strlen('desktop-')) : substr($key, strlen('mobile-'));

                // Make sure to not process same post_id twice.
                if (in_array($post_id, $already_processed_ids, true)) {
                    continue;
                }
                $already_processed_ids[] = $post_id;

                $desktop = array_key_exists('desktop-' . $post_id, $postdata) ? ($postdata['desktop-' . $post_id]) : null;
                $mobile  = array_key_exists('mobile-' . $post_id, $postdata) ? ($postdata['mobile-' . $post_id]) : null;

                $new_post = array('id' => $post_id);
                if (! is_null($desktop)) {
                    $new_post['desktop'] = $desktop;
                }
                if (! is_null($mobile)) {
                    $new_post['mobile'] = $mobile;
                }
                $active_posts[] = $new_post;

                if (isset($postdata['desktop-' . $post_id]) && 1 === $postdata['desktop-' . $post_id]) {
                    ++$count_selected;
                }

                if (isset($postdata['mobile-' . $post_id]) && 1 === $postdata['mobile-' . $post_id]) {
                    ++$count_selected;
                }
            }
        }

        $group_id_website_details = sanitize_text_field($postdata['group_id']);
        \WebChangeDetector\WebChangeDetector_API_V2::update_urls_in_group_v2($group_id_website_details, $active_posts);

        // TODO Make return to show the result.
        echo '<div class="updated notice"><p>Settings saved.</p></div>';
    }



    /** Get group details and its urls.
     *
     * @param string $group_uuid The group id.
     * @param array  $url_filter Filters for the urls.
     *
     * @return mixed
     */
    public function get_group_and_urls($group_uuid, $url_filter = array())
    {

        $group_response = \WebChangeDetector\WebChangeDetector_API_V2::get_group_v2($group_uuid);
        $group_and_urls = $group_response['data'] ?? array();
        $urls           = \WebChangeDetector\WebChangeDetector_API_V2::get_group_urls_v2($group_uuid, $url_filter);

        // Check if URLs response is valid and has expected structure.
        if (empty($urls) || ! isset($urls['data'])) {
            $this->wordpress_handler->sync_posts(true);
            $urls = \WebChangeDetector\WebChangeDetector_API_V2::get_group_urls_v2($group_uuid, $url_filter);
        }

        // Ensure we have a valid response structure before accessing keys.
        if (! is_array($urls)) {
            $urls = array('data' => array(), 'meta' => array('selected_urls_count' => 0));
        }

        $group_and_urls['urls']                = $urls['data'] ?? array();
        $group_and_urls['meta']                = $urls['meta'] ?? array();
        $group_and_urls['selected_urls_count'] = $urls['meta']['selected_urls_count'] ?? 0;

        return $group_and_urls;
    }

    /**
     * Check if user can access specific feature based on plan.
     *
     * @param string $feature Feature name.
     * @param string|null $user_plan User plan level.
     * @return bool
     */
    public function can_access_feature($feature, $user_plan = null)
    {
        if (! $user_plan) {
            $account = $this->account_handler->get_account();
            $user_plan = $account['plan'] ?? 'free';
        }

        $feature_plans = array(
            'browser_console' => array('trial', 'personal_pro', 'freelancer', 'agency'),
            // Add other features as needed.
        );

        if (! isset($feature_plans[$feature])) {
            return true; // Feature not restricted.
        }

        return in_array($user_plan, $feature_plans[$feature], true);
    }

    /**
     * Convenience method for error logging.
     *
     * Provides a simple interface to log errors with proper context and severity levels.
     * Uses the integrated logger instance for consistent error handling.
     *
     * @since 4.0.0
     * @param string $message The error message to log.
     * @param string $context Optional. The context or category for the error. Default 'admin'.
     * @param string $severity Optional. The severity level. Default 'error'.
     * @return bool True on success, false on failure.
     */
    public function log_error($message, $context = 'admin', $severity = 'error')
    {
        if (! $this->logger) {
            return false;
        }

        return $this->logger->log($message, $severity, $context);
    }

    /**
     * Convenience method for safe API call execution.
     *
     * Wraps API operations with comprehensive error handling, retry logic, and recovery.
     * Uses the integrated error handler for consistent error management.
     *
     * @since 4.0.0
     * @param callable $operation The API operation to execute.
     * @param array $options Optional. Configuration options for error handling.
     * @return array Response array with success status and data.
     */
    public function safe_execute($operation, $options = array())
    {
        if (! $this->error_handler) {
            return array(
                'success' => false,
                'message' => 'Error handler not available.',
            );
        }

        $default_options = array(
            'category'     => 'admin',
            'user_message' => 'An error occurred while processing your request.',
            'context'      => 'Admin Operation',
        );

        $options = wp_parse_args($options, $default_options);

        return $this->error_handler->execute_with_error_handling($operation, array(), $options);
    }

    /**
     * Convenience method for handling API errors.
     *
     * Provides a simple interface to handle API errors with proper logging and recovery.
     * Uses the integrated error handler for consistent error management.
     *
     * @since 4.0.0
     * @param callable $api_call The API call to execute.
     * @param array $options Optional. Configuration options for error handling.
     * @return array Response array with success status and data.
     */
    public function handle_api_error($api_call, $options = array())
    {
        if (! $this->error_handler) {
            return array(
                'success' => false,
                'message' => 'Error handler not available.',
            );
        }

        $default_options = array(
            'category'     => 'api',
            'user_message' => 'Failed to communicate with WebChangeDetector service. Please try again.',
            'context'      => 'API Operation',
        );

        $options = wp_parse_args($options, $default_options);

        return $this->error_handler->handle_api_error($api_call, array(), $options);
    }
} // End class WebChangeDetector_Admin.

// HTTP Status Codes.
if (! defined('WCD_HTTP_BAD_REQUEST')) {
    define('WCD_HTTP_BAD_REQUEST', 400);
}

if (! defined('WCD_HTTP_UNAUTHORIZED')) {
    define('WCD_HTTP_UNAUTHORIZED', 401);
}

if (! defined('WCD_HTTP_INTERNAL_SERVER_ERROR')) {
    define('WCD_HTTP_INTERNAL_SERVER_ERROR', 500);
}

// Time/Date Related.
if (! defined('WCD_DAYS_PER_MONTH')) {
    define('WCD_DAYS_PER_MONTH', 30);
}

if (! defined('WCD_HOURS_IN_DAY')) {
    define('WCD_HOURS_IN_DAY', 24);
}

if (! defined('WCD_SECONDS_IN_MONTH')) {
    // 60 * 60 * 24 * 30.
    define('WCD_SECONDS_IN_MONTH', 2592000);
}

// Option secret for domain verification.
if (! defined('WCD_VERIFY_SECRET')) {
    define('WCD_VERIFY_SECRET', 'webchangedetector_verify_secret');
}

// Option / UserMeta keys.
if (! defined('WCD_WP_OPTION_KEY_API_TOKEN')) {
    define('WCD_WP_OPTION_KEY_API_TOKEN', 'webchangedetector_api_token');
}

// Account email address.
if (! defined('WCD_WP_OPTION_KEY_ACCOUNT_EMAIL')) {
    define('WCD_WP_OPTION_KEY_ACCOUNT_EMAIL', 'webchangedetector_account_email');
}

if (! defined('WCD_WP_OPTION_KEY_UPGRADE_URL')) {
    define('WCD_WP_OPTION_KEY_UPGRADE_URL', 'wcd_upgrade_url');
}

if (! defined('WCD_WP_OPTION_KEY_INITIAL_SETUP_NEEDED')) {
    define('WCD_WP_OPTION_KEY_INITIAL_SETUP_NEEDED', 'wcd_initial_setup_needed');
}

// Website ID for API calls.
if (! defined('WCD_WP_OPTION_KEY_WEBSITE_ID')) {
    define('WCD_WP_OPTION_KEY_WEBSITE_ID', 'webchangedetector_website_id');
}

// Debug logging enable/disable.
if (! defined('WCD_WP_OPTION_KEY_DEBUG_LOGGING')) {
    define('WCD_WP_OPTION_KEY_DEBUG_LOGGING', 'webchangedetector_debug_logging');
}

// Health status option.
if (! defined('WCD_WP_OPTION_KEY_HEALTH_STATUS')) {
    define('WCD_WP_OPTION_KEY_HEALTH_STATUS', 'webchangedetector_health_status');
}

// Steps in update change detection.
if (! defined('WCD_OPTION_UPDATE_STEP_KEY')) {
    define('WCD_OPTION_UPDATE_STEP_KEY', 'webchangedetector_update_detection_step');
}
if (! defined('WCD_OPTION_UPDATE_STEP_SETTINGS')) {
    define('WCD_OPTION_UPDATE_STEP_SETTINGS', 'settings');
}
if (! defined('WCD_OPTION_UPDATE_STEP_PRE')) {
    define('WCD_OPTION_UPDATE_STEP_PRE', 'pre-update');
}
if (! defined('WCD_OPTION_UPDATE_STEP_PRE_STARTED')) {
    define('WCD_OPTION_UPDATE_STEP_PRE_STARTED', 'pre-update-started');
}
if (! defined('WCD_OPTION_UPDATE_STEP_MAKE_UPDATES')) {
    define('WCD_OPTION_UPDATE_STEP_MAKE_UPDATES', 'make-update');
}
if (! defined('WCD_OPTION_UPDATE_STEP_POST')) {
    define('WCD_OPTION_UPDATE_STEP_POST', 'post-update');
}
if (! defined('WCD_OPTION_UPDATE_STEP_POST_STARTED')) {
    define('WCD_OPTION_UPDATE_STEP_POST_STARTED', 'post-update-started');
}
if (! defined('WCD_OPTION_UPDATE_STEP_CHANGE_DETECTION')) {
    define('WCD_OPTION_UPDATE_STEP_CHANGE_DETECTION', 'change-detection');
}

// WCD tabs.
if (! defined('WCD_TAB_DASHBOARD')) {
    define('WCD_TAB_DASHBOARD', '/admin.php?page=webchangedetector');
}
if (! defined('WCD_TAB_UPDATE')) {
    define('WCD_TAB_UPDATE', '/admin.php?page=webchangedetector-update-settings');
}
if (! defined('WCD_TAB_AUTO')) {
    define('WCD_TAB_AUTO', '/admin.php?page=webchangedetector-auto-settings');
}
if (! defined('WCD_TAB_CHANGE_DETECTION')) {
    define('WCD_TAB_CHANGE_DETECTION', '/admin.php?page=webchangedetector-change-detections');
}
if (! defined('WCD_TAB_LOGS')) {
    define('WCD_TAB_LOGS', '/admin.php?page=webchangedetector-logs');
}
if (! defined('WCD_TAB_SETTINGS')) {
    define('WCD_TAB_SETTINGS', '/admin.php?page=webchangedetector-settings');
}

if (! defined('WCD_REQUEST_TIMEOUT')) {
    define('WCD_REQUEST_TIMEOUT', 30);
}
