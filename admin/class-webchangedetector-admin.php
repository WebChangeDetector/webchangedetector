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
class WebChangeDetector_Admin
{
    const API_TOKEN_LENGTH = 20;
    const PRODUCT_ID_FREE = 57;
    const LIMIT_QUEUE_ROWS = 50;

    const VALID_WCD_ACTIONS = [
        'reset_api_token',
        're-add-api-token',
        'save_api_token',
        'take_screenshots',
        'update_monitoring_settings',
        'update_monitoring_and_update_settings',
        'post_urls',
        'post_urls_update_and_auto',
        'dashboard',
        'change-detections',
        'update-settings',
        'auto-settings',
        'logs',
        'settings',
        'show-compare',
        'copy_url_settings',
        'create_free_account'
    ];

    const VALID_SC_TYPES = [
        'pre',
        'post',
        'auto',
        'compare',
    ];

    const VALID_GROUP_TYPES = [
        'all', // filter
        'generic', // filter
        'wordpress', // filter
        'auto',
        'update',
    ];

    /**
     * The ID of this plugin.
     *
     * @since    1.0.0
     * @access   private
     * @var      string $plugin_name The ID of this plugin.
     */
    private $plugin_name;

    /**
     * The version of this plugin.
     *
     * @since    1.0.0
     * @access   private
     * @var      string $version The current version of this plugin.
     */
    private $version = '2.0.0';

    /**
     * Initialize the class and set its properties.
     *
     * @param string $plugin_name The name of this plugin.
     * @param string $version The version of this plugin.
     * @since    1.0.0
     */
    public function __construct($plugin_name = 'WebChangeDetector')
    {
        $this->plugin_name = $plugin_name;
    }

    /**
     * Register the stylesheets for the admin area.
     *
     * @since    1.0.0
     */
    public function enqueue_styles()
    {

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
        wp_enqueue_style('jquery-ui-accordion');
        wp_enqueue_style($this->plugin_name, plugin_dir_url(__FILE__) . 'css/webchangedetector-admin.css', array(), $this->version, 'all');
        wp_enqueue_style('twentytwenty-css', plugin_dir_url(__FILE__) . 'css/twentytwenty.css', array(), $this->version, 'all');
        wp_enqueue_style('wp-codemirror');
        //wp_enqueue_style('codemirror-darcula', plugin_dir_url(__FILE__) . 'css/darcula.css', array(), $this->version, 'all');
    }

    /**
     * Register the JavaScript for the admin area.
     *
     * @since    1.0.0
     */
    public function enqueue_scripts()
    {

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

        wp_enqueue_script($this->plugin_name, plugin_dir_url(__FILE__) . 'js/webchangedetector-admin.js', array( 'jquery' ), $this->version, false);
        wp_enqueue_script('jquery-ui-accordion');
        wp_enqueue_script('twentytwenty-js', plugin_dir_url(__FILE__) . 'js/jquery.twentytwenty.js', array( 'jquery' ), $this->version, false);
        wp_enqueue_script('twentytwenty-move-js', plugin_dir_url(__FILE__) . 'js/jquery.event.move.js', array( 'jquery' ), $this->version, false);
        // Load WP codemirror
        $css_settings = array(
                        'type' => 'text/css',
                        //'codemirror' => array('theme' =>'darcula')
                    );
        $cm_settings['codeEditor'] = wp_enqueue_code_editor($css_settings);
        wp_localize_script('jquery', 'cm_settings', $cm_settings);
        wp_enqueue_script('wp-theme-plugin-editor');
    }

    // Add WCD to backend navigation (called by hook in includes/class-webchangedetector.php)
    public function wcd_plugin_setup_menu()
    {
        require_once 'partials/webchangedetector-admin-display.php';
        add_menu_page(
            'WebChangeDetector',
            'WCD',
            'manage_options',
            'webchangedetector',
            'wcd_webchangedetector_init',
            plugin_dir_url(__FILE__) . 'img/icon-wp-backend.svg'
        );
        /*if(! get_option(MM_WCD_WP_OPTION_KEY_API_TOKEN) &&
            (! empty($_POST['wcd_action']) && $_POST['wcd_action'] === 'reset_api_token' && $_POST['wcd_action'] !== 'save_api_token')) {
            return;
        }*/

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
            'Update Detection',
            'Update Detection',
            'manage_options',
            'webchangedetector-update-settings',
            'wcd_webchangedetector_init'
        );
        add_submenu_page(
            'webchangedetector',
            'Auto Detection',
            'Auto Detection',
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

    public function create_free_account($postdata) {

        // Generate validation string
        $validation_string = wp_generate_password(40);
        update_option('webchangedetector_verify_secret', $validation_string, false);

        $args = array_merge([
            'action' => 'add_free_account',
            'ip'=> $_SERVER['SERVER_ADDR'],
            'domain' => $_SERVER['SERVER_NAME'],
            'validation_string' => $validation_string,
            'cms' => 'wp',
            ], $postdata);
        return $this->mm_api($args, true);
    }

    public function save_api_token($api_token) {

        if (! is_string($api_token) || strlen($api_token) < WebChangeDetector_Admin::API_TOKEN_LENGTH) {
            echo '<div class="error notice"><p>The API Token is invalid. Please try again.</p></div>';
            echo $this->get_no_account_page();
            return false;
        }

        // Save email address on account creation for showing on activate account page
        if(! empty($_POST['email'])) {
            update_option( WCD_WP_OPTION_KEY_ACCOUNT_EMAIL, sanitize_email( $_POST['email'] ), false );
        }
        update_option(MM_WCD_WP_OPTION_KEY_API_TOKEN, sanitize_text_field($api_token), false);

        return true;
    }

    // Sync Post if permalink changed. Called by hook in class-webchangedetector.php
    public function sync_post_after_save($post_id, $post, $update)
    {
        // Only sync posts and pages @TODO make setting to sync other posttypes
        if(!empty($post->post_type) && !in_array($post->post_type, ['page','post'])) {
            return false;
        }

        if ($update) {
            $latest_revision = array_shift(wp_get_post_revisions($post_id));
            if ($latest_revision && get_permalink($latest_revision) !== get_permalink($post)) {
               return $this->sync_posts($post);
            }
        } else {
            return $this->sync_posts($post);
        }
        return false;
    }

    public function account_details($api_token = false)
    {
        static $account_details;
        if ($account_details && $account_details !== "unauthorized" && $account_details !== "activate account") {
            return $account_details;
        }

        if(! $api_token) {
            $api_token = get_option(MM_WCD_WP_OPTION_KEY_API_TOKEN);
        }

        $args = array(
            'action' => 'account_details',
            'api_token' => $api_token,
        );
        $account_details = $this->mm_api($args);
        return $account_details;
    }

    public function ajax_get_processing_queue() {
        echo $this->get_processing_queue();
        die();
    }

    public function get_processing_queue()
    {
        return $this->mm_api(['action' => 'get_not_closed_queue']);
    }

    public function get_monitoring_settings($group_id) // Deprecated
    {
        $args = array(
            'action' => 'get_monitoring_settings',
            'group_id' => $group_id,
        );

        return $this->mm_api($args);
    }

    public function get_comparison_partial($token) // Deprecated
    {
        $args = array(
            'action' => 'get_comparison_partial',
            'token' => $token, // token for comparison partial, not api_token
        );
        return $this->mm_api($args);
    }

    public function update_monitoring_settings($postdata, $monitoring_group_id)
    {
        $args = array(
            'action' => 'update_monitoring_settings',
            'group_id' => sanitize_key($monitoring_group_id),
            'hour_of_day' => sanitize_key($postdata['hour_of_day']),
            'interval_in_h' => sanitize_key($postdata['interval_in_h']),
            'monitoring' => sanitize_key($postdata['monitoring']),
            'enabled' => sanitize_key($postdata['enabled']),
            'alert_emails' => sanitize_textarea_field(str_replace("\n\r",",",$postdata['alert_emails'])),
            'name' => sanitize_textarea_field($postdata['group_name']),
            'css' => sanitize_textarea_field($postdata['css']), // there is no css sanitation
        );
        return $this->mm_api($args);
    }

    public function update_settings($postdata, $group_id)
    {

        $args = array(
            'action' => 'update_group',
            'group_id' => $group_id,
            'css' => sanitize_textarea_field($postdata['css']), // there is no css sanitation
        );
        return $this->mm_api($args);
    }

    public function get_upgrade_url() {
        $account_details = $this->account_details();
        if(! empty($account_details['whmcs_service_id'])) {
            return $this->app_url() . '/upgrade/?id=' . $account_details['whmcs_service_id'] ;
        }
        return false;
    }

    /**
     * `<span>` with icon
     *
     * TODO make switch-case
     */
    public function get_device_icon($icon, $class = '')
    {
        if ($icon == 'thumbnail') {
            return '<span class="dashicons dashicons-camera-alt"></span>';
        }
        if ($icon == 'desktop') {
            return '<span class="group_icon ' . $class . ' dashicons dashicons-laptop"></span>';
        }
        if ($icon == 'mobile') {
            return '<span class="group_icon ' . $class . ' dashicons dashicons-smartphone"></span>';
        }
        if ($icon == 'page') {
            return '<span class="group_icon ' . $class . ' dashicons dashicons-media-default"></span>';
        }
        if ($icon == 'change-detections') {
            return '<span class="group_icon ' . $class . ' dashicons dashicons-welcome-view-site"></span>';
        }
        if ($icon == 'dashboard') {
            return '<span class="group_icon ' . $class . ' dashicons dashicons-admin-home"></span>';
        }
        if ($icon == 'logs') {
            return '<span class="group_icon ' . $class . ' dashicons dashicons-menu-alt"></span>';
        }
        if ($icon == 'settings') {
            return '<span class="group_icon ' . $class . ' dashicons dashicons-admin-generic"></span>';
        }
        if ($icon == 'website-settings') {
            return '<span class="group_icon ' . $class . ' dashicons dashicons-welcome-widgets-menus"></span>';
        }
        if ($icon == 'help') {
            return '<span class="group_icon ' . $class . ' dashicons dashicons-editor-help"></span>';
        }
        if ($icon == 'auto-group') {
            return '<span class="group_icon ' . $class . ' dashicons dashicons-video-alt"></span>';
        }
        if ($icon == 'update-group') {
            return '<span class="group_icon ' . $class . ' dashicons dashicons-camera"></span>';
        }
        if ($icon == 'trash') {
            return '<span class="group_icon ' . $class . ' dashicons dashicons-trash"></span>';
        }

        return '';
    }

    public function get_compares($group_ids, $limit_days = null, $group_type = null, $difference_only = null, $limit_compares = null)
    {
        $args = array(
            'action' => 'get_compares_by_group_ids',
            'limit_days' => $limit_days,
            'group_type' => $group_type,
            'difference_only' => $difference_only,
            'limit_compares' => $limit_compares,
            'group_ids' => json_encode(array($group_ids))
        );
        $compares = $this->mm_api($args);

        $return = array();
        if (! array_key_exists(0, $compares)) {
            return $return;
        }

        foreach (array_filter($compares, function ($compare) {
            // Make sure to only show urls from the website. Shouldn't come from the API anyway.
            return strpos($compare['screenshot1']['url'], $_SERVER['SERVER_NAME']) !== false;
        }) as $compare) {
            $return[] = $compare;
        }
        return $return;
    }

    public function compare_view($compares)
    { ?>
        <table class="toggle" style="width: 100%">
            <tr>
                <th width="auto">URL</th>
                <th width="150px">Compared Screenshots</th>
                <th width="50px">Difference</th>
                <th>Show</th>
            </tr>
        <?php if (empty($compares)) { ?>
            <tr>
                <td colspan="4" style="text-align: center">
                    <strong>There are no change detections yet.</strong
                </td>
            </tr>
        <?php } else {
            $all_tokens = [];
            foreach($compares as $compare) {
                $all_tokens[] = $compare['token'];

            }
            foreach ($compares as $compare) {
                $class = 'no-difference'; // init
                if ($compare['difference_percent']) {
                    $class = 'is-difference';
                } ?>
            <tr>
                <td>
                    <strong>
                    <?php
                    if (! empty($compare['screenshot1']['queue']['url']['html_title'])) {
                        echo esc_html($compare['screenshot1']['queue']['url']['html_title']) . '<br>';
                    } ?>
                    </strong>
                    <?= $this->get_device_icon($compare['screenshot1']['device']) . $compare['screenshot1']['url'] ?><br>
                    <?= $compare['screenshot2']['sc_type'] === 'auto' ? $this->get_device_icon('auto-group') . 'Auto Detection' : $this->get_device_icon('update-group') . 'Update Detection'?>
                </td>
                <td>
                    <div class="local-time" data-date="<?= $compare['image1_timestamp'] ?>"></div>
                    <div class="local-time" data-date="<?= $compare['image2_timestamp'] ?>"></div>
                </td>
                <td class="<?= $class ?> diff-tile" data-diff_percent="<?= $compare['difference_percent'] ?>"><?= $compare['difference_percent'] ?>%</td>
                <td>
                    <form action="?page=webchangedetector-show-detection" method="post">
                        <input type="hidden" name="token" value="<?= $compare['token'] ?>">
                        <input type="hidden" name="all_tokens" value='<?= json_encode($all_tokens) ?>'>
                        <input type="submit" value="Show" class="button">
                    </form>
                </td>
            </tr>
            <?php
            }
        } ?>
        </table>
        <?php
    }

    function get_comparison_by_token($postdata, $hide_switch = false, $whitelabel = false)
    {
        $token = $postdata['token'] ?? null;
        if (! $token && ! empty($_GET['token'])) {
            $token = $_GET['token'];
        }
        if (isset($token)) {
            $args = array(
                'action' => 'get_comparison_by_token',
                'token' => $token
            );
            $compare = $this->mm_api($args); // used in template
            $all_tokens = [];
            if(! empty($postdata['all_tokens'])) {
                $all_tokens = (json_decode(stripslashes($postdata['all_tokens']), true));

                $before_current_token = [];
                $after_current_token = [];
                $is_after = false;
                foreach($all_tokens as $current_token) {
                    if($current_token !== $token) {
                        if($is_after) {
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
            if(!$hide_switch) {
                echo '<style>#comp-switch {display: none !important;}</style>';
            }
            echo '<div style="padding: 0 20px;">';
            if (! $whitelabel) {
                echo '<style>.public-detection-logo {display: none;}</style>';
            }
            $before_token = ! empty($before_current_token) ? $before_current_token[max(array_keys($before_current_token))] : null;
            $after_token = $after_current_token[0] ?? null;
            ?>
            <!-- Previous and next buttons -->
            <div style="width: 100%; margin-bottom: 20px; text-align: center">
                <form method="post" >
                    <input type="hidden" name="all_tokens" value='<?= json_encode($all_tokens) ?>'>
                    <button class="button" type="submit" name="token"
                            value="<?= $before_token ?? null ?>" <?= ! $before_token ? 'disabled' : ''?>> < Previous </button>
                    <button class="button" type="submit" name="token"
                            value="<?= $after_token ?? null ?>" <?= ! $after_token ? 'disabled' : ''?>> Next > </button>
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

    function get_screenshot($postdata = false)
    {
        if (! isset($postdata['img_url'])) {
            return '<p class="notice notice-error" style="padding: 10px;">
                    Sorry, we couldn\'t find the screenshot. Please try again.</p>';
        }
        return '<div style="width: 100%; text-align: center;"><img style="max-width: 100%" src="' .  $postdata['img_url'] . '"></div>';
    }

    public function get_queue()
    {
        $args = array(
            'action' => 'get_queue',
            'status' => json_encode(['open', 'done', 'processing', 'failed']),
            'limit' => $_GET['limit'] ?? $this::LIMIT_QUEUE_ROWS,
            'offset' => $_GET['offset'] ?? 0,
        );
        return $this->mm_api($args);
    }

    public function sync_posts($post_obj = false)
    {
        $array = array(); // init

        // Sync single post
        if ($post_obj) {
            $save_post_types = ['post', 'page']; // @TODO Make this a setting
            if (in_array($post_obj->post_type, $save_post_types) && get_post_status($post_obj) === 'publish') {
                $url = get_permalink($post_obj);
                $start = strpos($url, '//') + strlen('//');
                $url = substr($url, $start); // remove evertyhing after http[s]://
                $array[] = array(
                    'url' => $url,
                    'html_title' => $post_obj->post_title,
                    'cms_resource_id' => $post_obj->ID,
                );
            }
        } else {
            // Sync all posts
            $posttypes = array(
                'pages' => get_pages(),
                'posts' => get_posts(array('numberposts' => '-1' )),
            );

            foreach ($posttypes as $posts) {
                if (! empty($posts) && is_iterable($posts)) {
                    foreach ($posts as $post) {
                        $url = get_permalink($post);
                        $url = substr($url, strpos($url, '//') + 2);
                        $array[] = array(
                            'url' => $url,
                            'html_title' => $post->post_title,
                            'cms_resource_id' => $post->ID,
                        );
                    }
                }
            }
        }

        if (! empty($array)) {
            $args = array(
                'action' => 'sync_urls',
                'posts' => json_encode($array),
            );

            return $this->mm_api($args);
        }
        return false;
    }

    public function update_urls($group_id, $active_posts = array())
    {
        $args = array(
            'action' => 'update_urls',
            'group_id' => $group_id,
            'posts' => json_encode($active_posts),
        );
        return $this->mm_api($args);
    }

    public function take_screenshot($group_id, $sc_type)
    {
        $args = array(
            'action'    => 'take_screenshots',
            'sc_type'   => $sc_type,
            'group_id'  => $group_id,
        );
        return $this->mm_api($args);
    }

    public function get_api_token_form($api_token = false)
    {
        $api_token_after_reset = isset($_POST['api_token']) ? sanitize_text_field($_POST['api_token']) : false;
        if ($api_token) {
            $output = '<form action="' . admin_url() . '/admin.php?page=webchangedetector" method="post"
                        onsubmit="return confirm(\'Are sure you want to reset the API token?\');">
                        <input type="hidden" name="wcd_action" value="reset_api_token">
                        <hr>
                        <h2>API Token</h2>
                        <p>Your API Token: <strong>' . $api_token . '</strong></p>
                        <p>With resetting the API Token, auto detections still continue and your settings will 
                        be still available when you use the same api token with this website again.</p>
                        <input type="submit" value="Reset API Token" class="button"><br>
                        
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
    public function create_website_and_groups($api_token)
    {
        // Create group if it doesn't exist yet
        $args = array(
            'action' => 'add_website_groups',
            'cms' => 'wordpress',
            'api_token' => $api_token
            // domain sent at mm_api
        );

        return $this->mm_api($args);
    }

    public function delete_website() // deprecated
    {
        $args = array(
            'action' => 'delete_website',
            // domain sent at mm_api
        );
        $this->mm_api($args);
    }

    public function get_urls_of_group($group_id)
    {
        $args = array(
            'action' => 'get_user_groups_and_urls',
            'cms' => 'wordpress',
            'group_id' => $group_id,
        );

        // We only get one group as we send the group_id
        $response = $this->mm_api($args);

        if (array_key_exists(0, $response)) {
            return $response[0];
        }

        return $response;
    }

    public function get_url_settings($groups_and_urls, $monitoring_group = false)
    {
        // Sync urls - post_types defined in function @TODO make settings for post_types to sync
        $synced_posts = $this->sync_posts();

        // Select URLS
        $tab = 'update-settings'; // init
        $detection_type = "update";
        if ($monitoring_group) {
            $tab = 'auto-settings';
            $detection_type = "auto";
        }
        echo '<div class="wcd-select-urls-container">';
        echo '<form class="wcd-frm-settings" action="' . admin_url() . 'admin.php?page=webchangedetector-' . $tab . '" method="post" >';
        echo '<h2>Select ' . ucfirst($detection_type) . ' Change Detection URLs</h2>';
        ?>
        <p style="text-align: center">
            Currently selected:
            <strong>
                <?= $groups_and_urls['amount_selected_urls'] ?>
                URLs
            </strong>
        </p>
        <?php
        echo '<input type="hidden" value="webchangedetector" name="page">';
        echo '<input type="hidden" value="' . esc_html($groups_and_urls['id']) . '" name="group_id">';

        $post_types = get_post_types();
        foreach ($post_types as $post_type) {
            if (! in_array($post_type, array('post', 'page'))) {
                continue;
            }

            $posts = get_posts(array(
                'post_type' => $post_type,
                'post_status' => 'publish',
                'numberposts' => -1,
                'order' => 'ASC',
                'orderby' => 'title'
            ));

            if ($posts) { ?>
                <div class="accordion">
                    <div class="mm_accordion_title">
                        <h3>
                            <span class="accordion-title">
                            <?= ucfirst($post_type) ?>s
                            <small>
                                Selected URLs desktop: <strong><span id="selected-desktop-<?= $post_type ?>"></span></strong> |
                                Selected URLs mobile: <strong><span id="selected-mobile-<?= $post_type ?>"></span></strong>
                            </small>
                            </span>

                        </h3>
                        <div class="mm_accordion_content">

                            <table class="no-margin">
                            <tr>
                                <th><?= $this->get_device_icon('desktop') ?></th>
                                <th><?= $this->get_device_icon('mobile') ?></th>
                                <th width="100%">URL</th>
                            </tr>
                            <?php
                            // Select all from same device
                            echo '<tr class="even-tr-white" style="background: none; text-align: center">
                                        <td><input type="checkbox" id="select-desktop-' . $post_type . '" onclick="mmToggle( this, \'' . $post_type . '\', \'desktop\', \'' . $groups_and_urls['id'] . '\' )" /></td>
                                        <td><input type="checkbox" id="select-mobile-' . $post_type . '" onclick="mmToggle( this, \'' . $post_type . '\', \'mobile\', \'' . $groups_and_urls['id'] . '\' )" /></td>
                                        <td></td>
                                    </tr>';
                            $amount_active_posts = 0;
                            $selected_mobile = 0;
                            $selected_desktop = 0;

                            foreach ($posts as $post) {
                                $url = get_permalink($post);
                                $url_id = false;

                                // Check if current WP post ID is in synced_posts and get the url_id
                                foreach ($synced_posts as $synced_post) {
                                    if (! empty($synced_post['cms_resource_id']) && $synced_post['cms_resource_id'] == $post->ID) {
                                        $url_id = $synced_post['url_id'];
                                    }
                                }

                                // If we don't have the url_id, the url is not synced and we continue
                                if (! $url_id) {
                                    continue;
                                }

                                // init
                                $checked = array(
                                    'desktop' => '',
                                    'mobile' => ''
                                );

                                if (! empty($groups_and_urls['urls'])) {
                                    foreach ($groups_and_urls['urls'] as $url_details) {
                                        if ($url_details['pivot']['url_id'] == $url_id) {
                                            $checked['active'] = 'checked';

                                            if ($url_details['pivot']['desktop']) {
                                                $checked['desktop'] = 'checked';
                                                $selected_desktop++;
                                                $amount_active_posts++;
                                            }
                                            if ($url_details['pivot']['mobile']) {
                                                $checked['mobile'] = 'checked';
                                                $selected_mobile++;
                                                $amount_active_posts++;
                                            }
                                        }
                                    }
                                }

                                echo '<tr class="even-tr-white post_id_' . $groups_and_urls['id'] . '" id="' . $url_id . '" >';
                                echo '<input type="hidden" name="post_id-' . $url_id . '" value="' . $post->ID . '">';
                                echo '<input type="hidden" name="url_id-' . $url_id . '" value="' . $url_id . '">';
                                echo '<input type="hidden" name="active-' . $url_id . ' value="1">';

                                echo '<td class="checkbox-desktop-' . $post_type . '" style="text-align: center;">
                                        <input type="hidden" value="0" name="desktop-' . $url_id . '">
                                        <input type="checkbox" name="desktop-' . $url_id . '" value="1" ' . $checked['desktop'] . '
                                        id="desktop-' . $url_id . '" onclick="mmMarkRows(\'' . $url_id . '\')" ></td>';

                                echo '<td class="checkbox-mobile-' . $post_type . '" style="text-align: center;">
                                        <input type="hidden" value="0" name="mobile-' . $url_id . '">
                                        <input type="checkbox" name="mobile-' . $url_id . '" value="1" ' . $checked['mobile'] . '
                                        id="mobile-' . $url_id . '" onclick="mmMarkRows(\'' . $url_id . '\')" ></td>';

                                echo '<td style="text-align: left;"><strong>' . $post->post_title . '</strong><br>';
                                echo '<a href="' . $url . '" target="_blank">' . $url . '</a></td>';
                                echo '</tr>';

                                echo '<script> mmMarkRows(\'' . $url_id . '\'); </script>';
                            }
                            echo '</table>';

                            echo '<div class="selected-urls" style="display: none;" 
                                    data-amount_selected="' . $amount_active_posts . '" 
                                    data-amount_selected_desktop="' . $selected_desktop . '"
                                    data-amount_selected_mobile="' . $selected_mobile . '"
                                    data-post_type="' . $post_type . '"
                                    ></div>';
                            } ?>
                        </div>
                    </div>
                </div>
            <?php
        }
        $other_group_type = $monitoring_group ? "update" : "auto";
        echo '<button 
                class="button button-primary" 
                type="submit" 
                name="wcd_action" 
                value="post_urls" >
                    Save Settings
                </button>';
        echo '<button class="button" 
                type="submit" 
                name="wcd_action" 
                value="post_urls_update_and_auto"
                style="margin-left: 10px;">
                    Save settings for ' . $other_group_type . ' detections too
                </button>';
        echo '</form>';

        echo '</div>';
    }

    public function post_urls($postdata, $website_details, $save_both_groups) {
        // Get active posts from post data
        $active_posts = array();
        $count_selected = 0;
        foreach ($postdata as $key => $post_id) {
            if (strpos($key, 'url_id') === 0) {

                // sanitize before
                $wpPostId = sanitize_key($postdata['post_id-'. $post_id]); // should be numeric
                if (! is_numeric($wpPostId)) {
                    continue; // just skip it
                }
                $permalink = get_permalink($wpPostId); // should return the whole link
                $desktop = array_key_exists('desktop-'. $post_id, $postdata) ? sanitize_key($postdata['desktop-' . $post_id]) : 0;
                $mobile = array_key_exists('mobile-'. $post_id, $postdata) ? sanitize_key($postdata['mobile-' . $post_id]) : 0;

                $active_posts[] = array(
                    'url_id' => $post_id,
                    'url' => $permalink,
                    'desktop' => $desktop,
                    'mobile' => $mobile
                );
                if (isset($postdata['desktop-' . $post_id])) {
                    $count_selected++;
                }

                if (isset($postdata['mobile-' . $post_id])) {
                    $count_selected++;
                }
            }
        }

        $group_id_website_details = sanitize_key($postdata['group_id']);

        // Check if there is a limit for selecting URLs
        if ($website_details['enable_limits'] &&
            $website_details['url_limit_manual_detection'] < $count_selected &&
            $website_details['manual_detection_group_id'] == $group_id_website_details) {
            echo '<div class="error notice"><p>The limit for selecting URLs is ' .
                esc_html($website_details['url_limit_manual_detection']) . '.
                        You selected ' . $count_selected . ' URLs. The settings were not saved.</p></div>';
        } elseif ($website_details['enable_limits'] &&
            isset($monitoring_group_settings) &&
            $website_details['sc_limit'] < $count_selected * (MM_WCD_HOURS_IN_DAY / $monitoring_group_settings['interval_in_h']) * MM_WCD_DAYS_PER_MONTH &&
            $website_details['auto_detection_group_id'] == $group_id_website_details) {
            echo '<div class="error notice"><p>The limit for auto change detection is ' .
                esc_html($website_details['sc_limit']) . '. per month.
                            You selected ' . $count_selected * (MM_WCD_HOURS_IN_DAY / $monitoring_group_settings['interval_in_h']) * MM_WCD_DAYS_PER_MONTH . ' change detections. The settings were not saved.</p></div>';
        } else {
            if($save_both_groups) {
                $this->update_urls($website_details['auto_detection_group_id'], $active_posts);
                $this->update_urls($website_details['manual_detection_group_id'], $active_posts);
            } else {
                $this->update_urls($group_id_website_details, $active_posts);
            }
            echo '<div class="updated notice"><p>Settings saved.</p></div>';
        }
    }

    public function copy_url_settings($from_group_id, $to_group_id)
    {
        $urls = $this->get_urls_of_group($from_group_id);
        $url_settings = [];
        foreach($urls['urls'] as $url) {

           $url_settings[] = [
                'url_id' => $url['pivot']['url_id'],
                'url' => $url['url'],
                'desktop' => $url['pivot']['desktop'],
                'mobile' => $url['pivot']['mobile'],
            ];
        }
        $this->update_urls($to_group_id, $url_settings);
    }

    public function get_no_account_page($api_token = '')
    {
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
                        Create your free account now and use WebChangeDetector with <br><strong>50 screenshots</strong> per month for free.<br>
                    </p>
                    <form class="frm_new_account" method="post">
                        <input type="hidden" name="wcd_action" value="create_free_account">
                        <input type="text" name="name_first" placeholder="First Name" value="<?= $_POST['name_first'] ?? wp_get_current_user()->user_firstname ?>" required>
                        <input type="text" name="name_last" placeholder="Last Name" value="<?= $_POST['name_last'] ?? wp_get_current_user()->user_lastname ?>" required>
                        <input type="email" name="email" placeholder="Email" value="<?= $_POST['email'] ?? wp_get_current_user()->user_email ?>" required>
                        <input type="password" name="password" placeholder="Password" required>

                        <input type="submit" class="button-primary" value="Create Free Account">
                    </form>
                </div>
                </div>

                 <?= $this->get_api_token_form($api_token) ?>

                </div>
            </div>
		</div>

        <?php
        return ob_get_clean();
    }

    public function get_website_details()
    {
        static $website_details;
        if($website_details) {
            return $website_details;
        }

        $args = array(
            'action' => 'get_website_details',
            // domain sent at mm_api
        );

        $website_details = $this->mm_api($args);

        // Take the first website details or return error string
        if (is_array($website_details) && count($website_details) > 0) {
            $website_details = $website_details[0];
        }
        return $website_details;
    }

    public function tabs()
    {
        $active_tab = 'webchangedetector'; // init

        if (isset($_GET['page'])) {
            // sanitize: lower-case with "-"
            $active_tab = sanitize_key($_GET['page']);
        } ?>
        <div class="wrap">
            <h2 class="nav-tab-wrapper">
                <a href="?page=webchangedetector"
                   class="nav-tab <?php echo $active_tab == 'webchangedetector' ? 'nav-tab-active' : ''; ?>">
                   Dashboard</a>
                <a href="?page=webchangedetector-change-detections"
                   class="nav-tab <?php echo $active_tab == 'webchangedetector-change-detections' ? 'nav-tab-active' : ''; ?>">
                    Change Detections</a>
                <a href="?page=webchangedetector-update-settings"
                   class="nav-tab <?php echo $active_tab == 'webchangedetector-update-settings' ? 'nav-tab-active' : ''; ?>">
                    Update Detection</a>
                <a href="?page=webchangedetector-auto-settings"
                   class="nav-tab <?php echo $active_tab == 'webchangedetector-auto-settings' ? 'nav-tab-active' : ''; ?>">
                    Auto Detection</a>
                <a href="?page=webchangedetector-logs"
                   class="nav-tab <?php echo $active_tab == 'webchangedetector-logs' ? 'nav-tab-active' : ''; ?>">Logs</a>
                <a href="?page=webchangedetector-settings"
                   class="nav-tab <?php echo $active_tab == 'webchangedetector-settings' ? 'nav-tab-active' : ''; ?>">Settings</a>
               <a href="<?= $this->get_upgrade_url() ?>" target="_blank"
                   class="nav-tab upgrade">Upgrade Account</a>
            </h2>
        </div>

        <?php
    }

    public function get_dashboard_view($client_account, $update_group_id, $auto_group_id)
    {
        $recent_comparisons = $this->get_compares([$update_group_id, $auto_group_id], null, null, false, 10);

        $auto_group = $this->get_urls_of_group($auto_group_id);
        $amount_auto_detection = 0;
        if ($auto_group['enabled']) {
            $amount_auto_detection += MM_WCD_HOURS_IN_DAY / $auto_group['interval_in_h'] * $auto_group['amount_selected_urls'] * MM_WCD_DAYS_PER_MONTH;
        } ?>
        <div class="dashboard">
            <div>
                <div class="box-half no-border">
                    <a class="box" href="?page=webchangedetector-update-settings">
                        <div style="padding-top:10px; font-size: 60px; width: 50px; float: left;">
                            <?= $this->get_device_icon('update-group') ?>
                        </div>
                        <div style="float: left; max-width: 350px;">
                            <strong>Update Detection</strong><br>
                            Create change detections manually
                        </div>
                        <div class="clear"></div>
                    </a>
                    <a class="box" href="?page=webchangedetector-auto-settings">
                        <div style="padding-top:10px; font-size: 60px; width: 50px; float: left;">
                            <?= $this->get_device_icon('auto-group') ?>
                        </div>
                        <div style="float: left; max-width: 350px;">
                            <strong>Auto Detection</strong><br>
                            Create automatic change detections
                        </div>
                        <div class="clear"></div>
                    </a>
                    <a class="box" href="?page=webchangedetector-change-detections">
                        <div style="padding-top:10px; font-size: 60px; width: 50px; float: left;">
                            <?= $this->get_device_icon('change-detections') ?>
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
                            if (! empty($client_account['sc_limit'])) {
                                echo number_format($client_account['usage'] / $client_account['sc_limit'] * 100, 1);
                            } else {
                                echo 0;
                            } ?>
                             % credits used
                        </strong>
                    </h2>
                    <hr>
                    <p style="margin-top: 20px;"><strong>Used credits:</strong> <?= $client_account['usage'] ?> / <?= $client_account['sc_limit'] ?></p>

                    <p><strong>Auto change detections / month:</strong> <?= $amount_auto_detection ?></p>

                    <p><strong>Auto change detections until renewal:</strong>
                        <?= number_format($amount_auto_detection / MM_WCD_SECONDS_IN_MONTH * (gmdate('U', strtotime($client_account['renewal_at'])) - gmdate('U')), 0) ?></p>

                    <p><strong>Renewal on:</strong> <?= gmdate('d/m/Y', strtotime($client_account['renewal_at'])) ?></p>
                </div>
                <div class="clear"></div>
            </div>


            <div>
                <h2>Latest Change Detections</h2>
                <?php
                $this->compare_view($recent_comparisons);
        if (! empty($recent_comparisons)) { ?>
                    <a class="button" href="?page=webchangedetector-change-detections">Show Change Detections</a>
                <?php } ?>
            </div>

            <div class="clear"></div>
        </div>
        <?php
    }

    public function show_activate_account($error)
    {
        if ($error === 'activate account') { ?>
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
                <?php if(get_option(WCD_WP_OPTION_KEY_ACCOUNT_EMAIL)) { ?>
                    <div style="margin: 0 auto; padding: 15px; border-radius: 5px;background: #5db85c; color: #fff; max-width: 400px;">
                        <span id="activation_email" style="font-weight: 700;"><?= sanitize_email(get_option(WCD_WP_OPTION_KEY_ACCOUNT_EMAIL)) ?></span>
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

        if ($error === 'unauthorized') { ?>
            <div class="notice notice-error">
                <p>
                    The API token is not valid. Please reset the API token and enter a valid one.
                </p>
            </div>
            <?php
            echo $this->get_no_account_page();
        }

        return true;
    }

    /**
     * App Domain can be set outside this plugin for development
     *
     * @return string
     */
    public function app_url()
    {
        if (defined('WCD_APP_DOMAIN') && is_string(WCD_APP_DOMAIN) && ! empty(WCD_APP_DOMAIN)) {
            return WCD_APP_DOMAIN;
        }
        return 'https://www.webchangedetector.com/';
    }

    /**
     * If in development mode
     *
     * @return bool
     */
    public function dev()
    {
        // if either .test or dev. can be found in the URL, we're developing -  wouldn't work if plugin client domain matches these criteria
        if(defined('WCD_DEV') && WCD_DEV === true) {
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
     * @param bool $isGet
     * @return string|array
     */
    public function mm_api($post, $isWeb = false)
    {
        $url = 'https://api.webchangedetector.com/api/v1/'; // init for production
        $urlWeb = 'https://api.webchangedetector.com/';

        // This is where it can be changed to a local/dev address
        if (defined('WCD_API_URL') && is_string(WCD_API_URL) && ! empty(WCD_API_URL)) {
            $url = WCD_API_URL;
        }

        // Overwrite $url if it is a get request
        if ($isWeb && defined('WCD_API_URL_WEB') && is_string(WCD_API_URL_WEB) && ! empty(WCD_API_URL_WEB)) {
            $urlWeb = WCD_API_URL_WEB;
        }

        $url .= str_replace('_', '-', $post['action']); // add kebab action to url
        $urlWeb .= str_replace('_', '-', $post['action']); // add kebab action to url
        $action = $post['action']; // For debugging

        // Get API Token from WP DB
        $api_token = $post['api_token'] ?? get_option(MM_WCD_WP_OPTION_KEY_API_TOKEN) ?? null;

        unset($post['action']); // don't need to send as action as it's now the url
        unset($post['api_token']); // just in case

        $post['wp_plugin_version'] = $this->version; // API will check this to check compatability
        // there's checks in place on the API side, you can't just send a different domain here, you sneaky little hacker ;)
        $post['domain'] = $_SERVER['SERVER_NAME'];
        $post['domain'] = $_SERVER['SERVER_NAME'];
        $post['wp_id'] = get_current_user_id();

        $args = array(
            'body'  => $post,
            'headers' => array(
                'Accept' => 'application/json',
                'Authorization' => 'Bearer ' . $api_token,
            ),
        );

        if($isWeb) {
            $response = wp_remote_post($urlWeb, $args);
        } else {
            $response = wp_remote_post($url, $args);
        }

        $body = wp_remote_retrieve_body($response);
        $responseCode = (int) wp_remote_retrieve_response_code($response);

        $decodedBody = json_decode($body, (bool) JSON_OBJECT_AS_ARRAY);

        // `message` is part of the Laravel Stacktrace
        if ($responseCode === MM_WCD_HTTP_BAD_REQUEST &&
            is_array($decodedBody) &&
            array_key_exists('message', $decodedBody) &&
            $decodedBody['message'] === 'plugin_update_required') {
            echo '<div class="error notice">
                        <p>Me made major changes on the API which requires to update the plugin WebChangeDetector. Please install the update at
                        <a href="/wp-admin/plugins.php">Plugins</a>.</p>
                    </div>';
            die();
        }

        if ($responseCode === MM_WCD_HTTP_INTERNAL_SERVER_ERROR && $action === 'account_details') {
            return 'activate account';
        }

        if ($responseCode === MM_WCD_HTTP_UNAUTHORIZED) {
            return 'unauthorized';
        }

        // if (! mm_wcd_http_successful($responseCode)) {
        //     if ($this->dev()) {
        //         dd($response, $action, $responseCode, $body);
        //     }
        // }

        // if parsing JSON into $decodedBody was without error
        if (json_last_error() === JSON_ERROR_NONE) {
            return $decodedBody;
        }

        return $body;
    }
}

// HTTP Status Codes
if (! defined('MM_WCD_HTTP_BAD_REQUEST')) {
    define('MM_WCD_HTTP_BAD_REQUEST', 400);
}

if (! defined('MM_WCD_HTTP_UNAUTHORIZED')) {
    define('MM_WCD_HTTP_UNAUTHORIZED', 401);
}

if (! defined('MM_WCD_HTTP_INTERNAL_SERVER_ERROR')) {
    define('MM_WCD_HTTP_INTERNAL_SERVER_ERROR', 500);
}

// Time/Date Related
if (! defined('MM_WCD_DAYS_PER_MONTH')) {
    define('MM_WCD_DAYS_PER_MONTH', 30);
}

if (! defined('MM_WCD_HOURS_IN_DAY')) {
    define('MM_WCD_HOURS_IN_DAY', 24);
}

if (! defined('MM_WCD_SECONDS_IN_MONTH')) {
    // 60 * 60 * 24 * 30
    define('MM_WCD_SECONDS_IN_MONTH', 2592000);
}

// Option / UserMeta keys
if (! defined('MM_WCD_WP_OPTION_KEY_API_TOKEN')) {
    define('MM_WCD_WP_OPTION_KEY_API_TOKEN', 'webchangedetector_api_token');
}

// Option / UserMeta keys
if (! defined('WCD_WP_OPTION_KEY_ACCOUNT_EMAIL')) {
    define('WCD_WP_OPTION_KEY_ACCOUNT_EMAIL', 'webchangedetector_account_email');
}

// // Uncommented defines()
// if (! defined('MM_WCD_HTTP_OK')) {
//     define('MM_WCD_HTTP_OK', 200);
// }
// if (! defined('MM_WCD_HTTP_MULTIPLE_CHOICES')) {
//     define('MM_WCD_HTTP_MULTIPLE_CHOICES', 300);
// }

 // Uncommented functions()
 if (! function_exists('dd') ) {
     /**
      * Dump and Die
      */
     function dd(... $output) // this is PHP 5.6+
     {
         echo '<pre>';
         foreach ($output as $o) {
             if (is_array($o) || is_object($o)) {
                 print_r($o);
                 continue;
             }
             echo $o;
         }
         echo '</pre>';
         die();
     }
 }

// if (! function_exists('mm_wcd_http_successful')) {
//     /**
//      * HTTP Response Code in between 200 (incl) and 300
//      *
//      * @param int $httpCode
//      * @return bool
//      */
//     function mm_wcd_http_successful($httpCode)
//     {
//         return ($httpCode >= MM_WCD_HTTP_OK) && ($httpCode < MM_WCD_HTTP_MULTIPLE_CHOICES);
//     }
// }
