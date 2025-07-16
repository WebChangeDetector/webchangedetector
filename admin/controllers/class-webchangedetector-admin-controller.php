<?php

/**
 * Main Admin Controller for WebChangeDetector
 *
 * Handles the initialization and routing of admin requests.
 *
 * @package    WebChangeDetector
 * @subpackage WebChangeDetector/admin/controllers
 * @author     Mike Miler <mike@wp-mike.com>
 */

namespace WebChangeDetector;

/**
 * Main Admin Controller Class.
 */
class WebChangeDetector_Admin_Controller
{

    /**
     * The admin instance.
     *
     * @var WebChangeDetector_Admin
     */
    private $admin;

    /**
     * The view renderer instance.
     *
     * @var WebChangeDetector_View_Renderer
     */
    private $view_renderer;

    /**
     * The action handler instance.
     *
     * @var WebChangeDetector_Action_Handler
     */
    private $action_handler;

    /**
     * Specialized page controllers.
     *
     * @var array
     */
    private $page_controllers = array();

    /**
     * Constructor.
     *
     * @param WebChangeDetector_Admin $admin The admin instance.
     */
    public function __construct($admin)
    {
        $this->admin = $admin;
        $this->init_dependencies();
        $this->init_page_controllers();
    }

    /**
     * Initialize dependencies.
     */
    private function init_dependencies()
    {
        // Dependencies are initialized through the main admin class.
        // View rendering and action handling are managed by their respective handlers.
    }

    /**
     * Initialize page controllers.
     */
    private function init_page_controllers()
    {
        $this->page_controllers = array(
            'dashboard'           => new WebChangeDetector_Dashboard_Controller($this->admin),
            'settings'            => new WebChangeDetector_Settings_Controller($this->admin),
            'monitoring'          => new WebChangeDetector_Monitoring_Controller($this->admin),
            'manual_checks'       => new WebChangeDetector_Manual_Checks_Controller($this->admin),
            'change_detections'   => new WebChangeDetector_Change_Detections_Controller($this->admin),
            'logs'                => new WebChangeDetector_Logs_Controller($this->admin),
        );
    }

    /**
     * Main initialization method.
     * 
     * This replaces the massive wcd_webchangedetector_init() function.
     *
     * @return bool|void
     */
    public function init()
    {
        global $wpdb;

        // Initialize admin and website details.
        $this->admin->website_details = $this->admin->settings_handler->get_website_details();
        $api_token = get_option(WCD_WP_OPTION_KEY_API_TOKEN);

        // Start view output.
        echo '<div class="wrap">';
        echo '<div class="webchangedetector">';
        echo '<h1>WebChange Detector</h1>';

        // Handle POST actions.
        $action_result = $this->handle_post_actions();
        if (false === $action_result) {
            echo '</div></div>'; // Close wrapper divs.
            return false;
        }

        // Display action result messages
        if (is_array($action_result) && isset($action_result['success']) && isset($action_result['message'])) {
            $notice_class = $action_result['success'] ? 'notice-success' : 'notice-error';
            echo '<div class="notice ' . esc_attr($notice_class) . ' is-dismissible"><p>' . esc_html($action_result['message']) . '</p></div>';
        }

        // Check for API token migration (from V1.0.7).
        $this->migrate_api_token_option();

        // Check if we have an API token.
        if (! get_option(WCD_WP_OPTION_KEY_API_TOKEN)) {
            $this->render_no_account_page();
            echo '</div></div>'; // Close wrapper divs.
            return false;
        }

        // Validate account and setup.
        $setup_result = $this->validate_account_and_setup();
        if (false === $setup_result) {
            echo '</div></div>'; // Close wrapper divs.
            return false;
        }

        // Route to appropriate page controller.
        $this->route_to_page_controller();

        echo '</div>'; // Close webchangedetector div.
        echo '</div>'; // Close wrap div.

        // Add inline JavaScript for sync_urls function.
        echo '<script>jQuery(document).ready(function() {sync_urls(); });</script>';

        return true;
    }

    /**
     * Handle POST actions.
     *
     * @return bool|string Returns false on error, true on success.
     */
    private function handle_post_actions()
    {
        // Validate wcd_action and nonce.
        $postdata   = array();
        $wcd_action = null;

        if (isset($_POST['wcd_action'])) {
            $wcd_action = sanitize_text_field(wp_unslash($_POST['wcd_action']));
            check_admin_referer($wcd_action);

            if (! is_string($wcd_action) || ! in_array($wcd_action, WebChangeDetector_Admin::VALID_WCD_ACTIONS, true)) {
?>
                <div class="error notice">
                    <p>Ooops! There was an unknown action called. Please contact us if this issue persists.</p>
                </div>
            <?php
                return false;
            }
        }

        // Unslash postdata.
        foreach ($_POST as $key => $post) {
            $key              = wp_unslash($key);
            $post             = wp_unslash($post);
            $postdata[$key] = $post;
        }

        // Handle actions that don't require API token.
        return $this->handle_pre_auth_actions($wcd_action, $postdata);
    }

    /**
     * Handle actions that don't require API token.
     *
     * @param string|null $wcd_action The action to handle.
     * @param array       $postdata   The POST data.
     * @return bool|string
     */
    private function handle_pre_auth_actions($wcd_action, $postdata)
    {
        switch ($wcd_action) {
            case 'create_free_account':
            case 'create_trial_account':
                return $this->handle_create_free_account($postdata);

            case 'reset_api_token':
                return $this->handle_reset_api_token();

            case 're-add-api-token':
                return $this->handle_re_add_api_token($postdata);

            case 'save_api_token':
                return $this->handle_save_api_token($postdata);

            default:
                // Continue with actions that require auth.
                if ($wcd_action) {
                    return $this->handle_authenticated_actions($wcd_action, $postdata);
                }
                return true;
        }
    }

    /**
     * Handle actions that require authentication.
     *
     * @param string $wcd_action The action to handle.
     * @param array  $postdata   The POST data.
     * @return bool
     */
    private function handle_authenticated_actions($wcd_action, $postdata)
    {
        $result = null;

        switch ($wcd_action) {
            case 'save_group_settings':
                $result = $this->admin->settings_action_handler->handle_save_group_settings($postdata);
                break;

            case 'take_screenshots':
                $result = $this->admin->screenshot_action_handler->handle_take_screenshots($postdata);
                break;

            case 'start_manual_checks':
                $result = $this->admin->wordpress_action_handler->handle_start_manual_checks($postdata);
                break;

            case 'sync_urls':
                $result = $this->admin->settings_action_handler->handle_sync_urls($postdata);
                break;

            case 'save_admin_bar_setting':
                $result = $this->admin->settings_action_handler->handle_save_admin_bar_setting($postdata);
                break;

            case 'update_detection_step':
                $result = $this->admin->wordpress_action_handler->handle_update_detection_step($postdata);
                break;

            case 'add_post_type':
                $result = $this->admin->wordpress_action_handler->handle_add_post_type($postdata);
                break;

            case 'filter_change_detections':
                $result = $this->admin->comparison_action_handler->handle_filter_change_detections($postdata);
                break;

            case 'change_comparison_status':
                $result = $this->admin->comparison_action_handler->handle_change_comparison_status($postdata);
                break;

            case 'create_trial_account':
                // This action should be handled in pre-auth actions, not here
                // Removing to prevent duplicate calls
                break;

            default:
                // Unknown action.
            ?>
                <div class="error notice">
                    <p>Unknown action: <?php echo esc_html($wcd_action); ?></p>
                </div>
            <?php
                return false;
        }

        // Return the result from the action handler (messages will be displayed by init() method)
        return $result;
    }

    /**
     * Handle create free account action.
     *
     * @param array $postdata The POST data.
     * @return bool
     */
    private function handle_create_free_account($postdata)
    {
        // Validate if all required fields were sent.
        if (! (isset($postdata['name_first']) && isset($postdata['name_last']) && isset($postdata['email']) && isset($postdata['password']))) {
            echo '<div class="notice notice-error"><p>Please fill all required fields.</p></div>';
            $this->render_no_account_page();
            return false;
        }

        $result = $this->admin->account_action_handler->handle_create_trial_account($postdata);

        if ($result['success']) {
            echo '<div class="notice notice-success"><p>' . esc_html($result['message']) . '</p></div>';
            return true;
        } else {
            echo '<div class="notice notice-error"><p>' . esc_html($result['message']) . '</p></div>';
            $this->render_no_account_page();
            return false;
        }
    }

    /**
     * Handle reset API token action.
     *
     * @return bool
     */
    private function handle_reset_api_token()
    {
        delete_option(WCD_WP_OPTION_KEY_API_TOKEN);
        delete_option(WCD_WP_OPTION_KEY_WEBSITE_ID);
        delete_option(WCD_WEBSITE_GROUPS);
        delete_option(WCD_OPTION_UPDATE_STEP_KEY);
        delete_option(WCD_AUTO_UPDATE_SETTINGS);
        delete_option(WCD_ALLOWANCES);
        delete_option(WCD_WP_OPTION_KEY_ACCOUNT_EMAIL);
        delete_option(WCD_WP_OPTION_KEY_UPGRADE_URL);
        return true;
    }

    /**
     * Handle re-add API token action.
     *
     * @param array $postdata The POST data.
     * @return bool
     */
    private function handle_re_add_api_token($postdata)
    {
        if (empty($postdata['api_token'])) {
            $this->render_no_account_page();
            return true;
        }

        return $this->admin->account_handler->save_api_token($postdata, $postdata['api_token']);
    }

    /**
     * Handle save API token action.
     *
     * @param array $postdata The POST data.
     * @return bool
     */
    private function handle_save_api_token($postdata)
    {
        if (empty($postdata['api_token'])) {
            echo '<div class="notice notice-error"><p>No API Token given.</p></div>';
            $this->render_no_account_page();
            return false;
        }

        return $this->admin->account_handler->save_api_token($postdata, $postdata['api_token']);
    }

    /**
     * Migrate API token option from old version.
     */
    private function migrate_api_token_option()
    {
        // Change api token option name from V1.0.7.
        if (! get_option(WCD_WP_OPTION_KEY_API_TOKEN) && get_option('webchangedetector_api_key')) {
            add_option(WCD_WP_OPTION_KEY_API_TOKEN, get_option('webchangedetector_api_key'), '', false);
            delete_option('webchangedetector_api_key');
        }
    }

    /**
     * Validate account and setup website details.
     *
     * @return bool
     */
    private function validate_account_and_setup()
    {
        // Get the account details.
        $account_details = $this->admin->account_handler->get_account(true);

        // Show error message if we didn't get response from API.
        if (empty($account_details)) {
            $this->render_api_error();
            return false;
        }

        // Check if plugin has to be updated.
        if ('update plugin' === $account_details) {
            $this->render_plugin_update_required();
            return false;
        }

        // Check if account is activated and if the api key is authorized.
        if (in_array($account_details, array('ActivateAccount', 'activate account', 'unauthorized'), true)) {
            $this->admin->account_handler->show_activate_account($account_details);
            return false;
        }

        // Get website details.
        $this->admin->website_details = $this->admin->settings_handler->get_website_details();

        // Create new ones if we don't have them yet.
        if (! $this->admin->website_details) {
            $success = $this->admin->create_website_and_groups();
            $this->admin->website_details = $this->admin->settings_handler->get_website_details();

            if (! $this->admin->website_details) {
                \WebChangeDetector\WebChangeDetector_Admin_Utils::log_error("Can't get website_details.");
            ?>
                <div class="notice notice-error">
                    <p><strong>WebChange Detector:</strong> Sorry, we couldn't retrieve your account settings. Please check your API token or contact support if this issue persists.</p>
                    <form method="post" style="margin-top: 10px;">
                        <input type="hidden" name="wcd_action" value="reset_api_token">
                        <?php wp_nonce_field('reset_api_token'); ?>
                        <input type="submit" value="Reset API Token" class="button button-secondary">
                    </form>
                </div>
            <?php
                return false;
            }

            // Make the initial post sync.
            // TODO: make this asynchronous and show loading screen.
            $this->admin->wordpress_handler->sync_posts(true);

            // If only the frontpage is allowed, we activate the URLs.
            if ($this->admin->settings_handler->is_allowed('only_frontpage')) {
                $urls = $this->admin->get_group_and_urls($this->admin->manual_group_uuid)['urls'];
                if (! empty($urls[0])) {
                    $update_urls = array(
                        'desktop-' . $urls[0]['id'] => 1,
                        'mobile-' . $urls[0]['id']  => 1,
                        'group_id'                  => $this->admin->website_details['manual_detection_group']['id'],
                    );
                    $this->admin->post_urls($update_urls);
                }
            }
        }

        // Check if website details are available.
        if (empty($this->admin->website_details)) {
            $this->render_website_details_error();
            return false;
        }

        // Save the allowances and update groups.
        $this->save_allowances_and_update_groups();

        // Show low credits warning.
        $this->show_credits_warning();

        return true;
    }

    /**
     * Save allowances and update groups.
     */
    private function save_allowances_and_update_groups()
    {
        // Save the allowances to the db. We need this for the navigation.
        if (! empty($this->admin->website_details['allowances'])) {
            $allowances = $this->admin->website_details['allowances'];

            // Disable upgrade account for subaccounts.
            if (! empty($this->admin->account_handler->get_account()['is_subaccount']) && $this->admin->account_handler->get_account()['is_subaccount']) {
                $allowances['upgrade_account'] = 0;
            }

            update_option(WCD_ALLOWANCES, $allowances);
        }

        // Extract group IDs from website details with multiple fallback options.
        $auto_group_id = false;
        $manual_group_id = false;

        // Try different possible structures for group IDs.
        if (! empty($this->admin->website_details['auto_detection_group']['id'])) {
            $auto_group_id = $this->admin->website_details['auto_detection_group']['id'];
        } elseif (! empty($this->admin->website_details['auto_detection_group']) && is_string($this->admin->website_details['auto_detection_group'])) {
            $auto_group_id = $this->admin->website_details['auto_detection_group'];
        } elseif (! empty($this->admin->website_details['monitoring_group_id'])) {
            $auto_group_id = $this->admin->website_details['monitoring_group_id'];
        }

        if (! empty($this->admin->website_details['manual_detection_group']['id'])) {
            $manual_group_id = $this->admin->website_details['manual_detection_group']['id'];
        } elseif (! empty($this->admin->website_details['manual_detection_group']) && is_string($this->admin->website_details['manual_detection_group'])) {
            $manual_group_id = $this->admin->website_details['manual_detection_group'];
        } elseif (! empty($this->admin->website_details['manual_group_id'])) {
            $manual_group_id = $this->admin->website_details['manual_group_id'];
        }

        // Update groups in case we have group ids from previous account.
        $groups = array(
            WCD_AUTO_DETECTION_GROUP   => $auto_group_id,
            WCD_MANUAL_DETECTION_GROUP => $manual_group_id,
        );
        update_option(WCD_WEBSITE_GROUPS, $groups, false);

        // Save group_ids to the class vars.
        $this->admin->monitoring_group_uuid = $auto_group_id;
        $this->admin->manual_group_uuid     = $manual_group_id;

        // Only show error if we have no groups at all and website creation failed.
        if (! $auto_group_id && ! $manual_group_id && ! empty($this->admin->website_details)) {
            // Try to create groups if we have website details but no groups.
            $this->admin->create_website_and_groups();

            // Refresh website details after group creation.
            $this->admin->website_details = $this->admin->settings_handler->get_website_details(true);

            // Re-extract group IDs after creation.
            if (! empty($this->admin->website_details['auto_detection_group']['id'])) {
                $auto_group_id = $this->admin->website_details['auto_detection_group']['id'];
            }
            if (! empty($this->admin->website_details['manual_detection_group']['id'])) {
                $manual_group_id = $this->admin->website_details['manual_detection_group']['id'];
            }

            // Update the groups again.
            $groups = array(
                WCD_AUTO_DETECTION_GROUP   => $auto_group_id,
                WCD_MANUAL_DETECTION_GROUP => $manual_group_id,
            );
            update_option(WCD_WEBSITE_GROUPS, $groups, false);
            $this->admin->monitoring_group_uuid = $auto_group_id;
            $this->admin->manual_group_uuid     = $manual_group_id;
        }

        // Only show error if still no groups after attempted creation.
        if (! $this->admin->manual_group_uuid && ! $this->admin->monitoring_group_uuid) {
            ?>
            <div class="notice notice-error">
                <p>Sorry, we couldn't get your account settings. Please contact us.
                <form method="post">
                    <input type="hidden" name="wcd_action" value="reset_api_token">
                    <?php wp_nonce_field('reset_api_token'); ?>
                    <input type="submit" value="Reset API token" class="button button-delete">
                </form>
                </p>
            </div>
            <?php
            return false;
        }

        return true;
    }

    /**
     * Show credits warning if needed.
     */
    private function show_credits_warning()
    {
        $account_details = $this->admin->account_handler->get_account();
        $this->admin->view_renderer->get_component('notifications')->render_credits_warning($account_details);
    }

    /**
     * Route to appropriate page controller.
     */
    private function route_to_page_controller()
    {
        // Get page to view.
        $tab = 'webchangedetector'; // Default to dashboard.
        if (isset($_GET['page'])) {
            $tab = sanitize_text_field(wp_unslash($_GET['page']));
        }

        // Show tabs navigation.
        $this->admin->view_renderer->render_navigation_tabs($tab);

        // Route to appropriate controller.
        switch ($tab) {
            case 'webchangedetector':
                $this->page_controllers['dashboard']->handle_request();
                break;

            case 'webchangedetector-update-settings':
                $this->page_controllers['manual_checks']->handle_request();
                break;

            case 'webchangedetector-auto-settings':
                $this->page_controllers['monitoring']->handle_request();
                break;

            case 'webchangedetector-change-detections':
                $this->page_controllers['change_detections']->handle_request();
                break;

            case 'webchangedetector-logs':
                $this->page_controllers['logs']->handle_request();
                break;

            case 'webchangedetector-settings':
                $this->page_controllers['settings']->handle_request();
                break;

            case 'webchangedetector-show-detection':
                // Check permissions.
                if (! $this->admin->settings_handler->is_allowed('change_detections_view')) {
                    return;
                }
                // Handle comparison view.
                $postdata = array();
                foreach ($_POST as $key => $post) {
                    $postdata[wp_unslash($key)] = wp_unslash($post);
                }
                $this->admin->dashboard_handler->get_comparison_by_token($postdata);
                break;

            case 'webchangedetector-show-screenshot':
                // Handle screenshot view.
                $postdata = array();
                foreach ($_POST as $key => $post) {
                    $postdata[wp_unslash($key)] = wp_unslash($post);
                }
                $this->admin->screenshots_handler->get_screenshot($postdata);
                break;

            case 'webchangedetector-no-billing-account':
            ?>
                <div style="text-align: center;">
                    <h2>Ooops!</h2>
                    <p>We couldn't get your billing account. <br>
                        Please get in touch with us at <a href="mailto:support@webchangedetector.com">support@webchangedetector.com</a>.
                    </p>
                </div>
<?php
                break;

            default:
                // Should already be validated by routing logic.
                break;
        }
    }

    /**
     * Render no account page.
     */
    private function render_no_account_page()
    {
        // For now, call the existing method.
        // In later phases, this will be moved to a view renderer.
        $this->admin->account_handler->get_no_account_page();
    }

    /**
     * Render API error.
     */
    private function render_api_error()
    {
        $this->admin->view_renderer->get_component('notifications')->render_api_error_notice();
        $this->render_no_account_page();
    }

    /**
     * Render plugin update required message.
     */
    private function render_plugin_update_required()
    {
        $this->admin->view_renderer->get_component('notifications')->render_plugin_update_notice();
    }

    /**
     * Render website details error.
     */
    private function render_website_details_error()
    {
        $this->admin->view_renderer->get_component('notifications')->render_website_details_error();
    }
}
