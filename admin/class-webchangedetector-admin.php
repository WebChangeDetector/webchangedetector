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
    private $version = '1.0.8';

    /**
     * Initialize the class and set its properties.
     *
     * @param string $plugin_name The name of this plugin.
     * @param string $version The version of this plugin.
     * @since    1.0.0
     */
    public function __construct($plugin_name = 'WebChangeDetector' )
    {
        $this->plugin_name = $plugin_name;
        //$this->version = $version;
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

        wp_enqueue_style($this->plugin_name, plugin_dir_url(__FILE__) . 'css/webchangedetector-admin.css', array(), $this->version, 'all');
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
            'webchangedetector_init',
            plugin_dir_url(__FILE__) . 'img/icon-wp-backend.svg'
        );
    }

    // Sync Post if permalink changed. Called by hook in class-webchangedetector.php
    public function sync_post_after_save($post_id, $post, $update)
    {

        if ($update) {
            $latest_revision = array_shift(wp_get_post_revisions($post_id));
            if ($latest_revision && get_permalink($latest_revision) !== get_permalink($post)) {
                $this->sync_posts($post);
            }
        } else {
            $this->sync_posts($post);
        }
    }

    public function get_account_details()
    {
        $args = array(
            'action' => 'account_details',
        );
        return $this->mm_api($args);
    }

    public function get_monitoring_settings($group_id)
    {
        $args = array(
            'action' => 'get_monitoring_settings',
            'group_id' => $group_id
        );
        $monitoring_group_settings = $this->mm_api($args);
        return $monitoring_group_settings;
    }

    /*public function mm_show_change_detection($token)
    {
        $args = array(
            'action' => 'show_change_detection',
            'token' => $token
        );
        return $this->mm_api($args);
    }*/

    public function mm_get_comparison_partial($token)
    {
        $args = array(
            'action' => 'get_comparison_partial',
            'token' => $token
        );
        return $this->mm_api($args);
    }

    public function update_monitoring_settings($postdata, $monitoring_group_id)
    {
        $args = array(
            'action' => 'update_monitoring_settings',
            'group_id' => $monitoring_group_id,
            'hour_of_day' => $postdata['hour_of_day'],
            'interval_in_h' => $postdata['interval_in_h'],
            'monitoring' => $postdata['monitoring'],
            'enabled' => $postdata['enabled'],
            'alert_emails' => $postdata['alert_emails'],
            'name' => $postdata['group_name']
        );
        return $this->mm_api($args);
    }

    public function get_upgrade_options($plan_id)
    {
        $args = array(
            'action' => 'get_upgrade_options',
            'plan_id' => $plan_id
        );
        return $this->mm_api($args);
    }

    public function mm_get_device_icon($device)
    {
        if ($device == 'desktop') {
            return '<span class="group_icon dashicons dashicons-laptop"></span>';
        }
        if ($device == 'mobile') {
            return '<span class="group_icon dashicons dashicons-smartphone"></span>';
        }

        return '';
    }

    public function get_compares($group_ids, $limit_latest_compares = 7)
    {
        $args = array(
            'action' => 'get_compares_by_group_ids',
            'limit_days' => $limit_latest_compares,
            'group_ids' => json_encode(array( $group_ids ))
        );
        $compares = $this->mm_api($args);

        $return = [];
        if(count( $compares ) == 0 ) {
            return $return;
        }

        foreach( $compares as $compare ) {
            // Only show change detections with a difference
            if (! $compare['difference_percent']) {
                continue;
            }

            // Make sure to only show urls from the website. Has to fixed in api.
            if( strpos( $compare['screenshot1']['url'], $_SERVER['SERVER_NAME']) === false ) {
                continue;
            }

            $return[] = $compare;
        }
        return $return;
    }

    public function compare_view($compares) {
        if(empty($compares)) {
            echo '<p>There are no change detections to show yet...</p>';
        } else {
            echo '<table><tr><th>URL</th><th>Compared Screenshots</th><th>Difference</th><th>Compare Link</th></tr>';
            $change_detection_added = false;
            foreach( $compares as $key => $compare ) {

                echo '<tr>';
                echo '<td>' . $this->mm_get_device_icon( $compare['screenshot1']['device'] ) . $compare['screenshot1']['url'] . '</td>';
                echo '<td>' . date( 'd/m/Y H:i', $compare['image1_timestamp'] ) . '<br>' . date( 'd/m/Y H:i', $compare['image2_timestamp'] ) . '</td>';
                if( $compare['difference_percent'] ) {
                    $class = 'is-difference';
                } else {
                    $class = 'no-difference';
                }
                echo '<td class="' . $class . '">' . $compare['difference_percent'] . ' %</td>';
                echo '<td><a href="?page=webchangedetector&tab=show-compare&action=show_compare&token=' . $compare['token'] . '" class="button">Show</a>';
                echo '</tr>';
                $change_detection_added = true;
            }

            echo '</table>';

            if( !$change_detection_added ) {
                echo '<p>There are no change detections to show yet...</p>';
            }
        }
    }

    public function get_queue()
    {
        $args = array(
            'action' => 'get_queue',
            'status' => json_encode(['open', 'done', 'processing', 'failed']),
        );
        return $this->mm_api($args);
    }

    public function sync_posts($post_obj = false)
    {
        if ($post_obj) {
            $save_post_types = ['post','page']; // @TODO Make this a setting
            if( in_array($post_obj->post_type, $save_post_types) && get_post_status($post_obj) === 'publish') {
                $url = get_permalink($post_obj);
                $url = substr($url, strpos($url, '//') + 2);
                $array[] = array(
                    'url' => $url,
                    'html_title' => $post_obj->post_title,
                    'cms_resource_id' => $post_obj->ID
                );
            }
        } else {
            $posttypes = array(
                'pages' => get_pages(),
                'posts' => get_posts(array( 'numberposts' => '-1' ))
            );

            foreach ($posttypes as $posts) {
                if ($posts) {
                    foreach ($posts as $post) {
                        $url = get_permalink($post);
                        $url = substr($url, strpos($url, '//') + 2);
                        $array[] = array(
                            'url' => $url,
                            'html_title' => $post->post_title,
                            'cms_resource_id' => $post->ID
                        );
                    }
                }
            }
        }

        if (! empty($array)) {
            $website_details = $this->get_website_details()[0];

            $args = array(
                'action' => 'sync_urls',
                'posts' => json_encode($array),
                'auto_detection_group_id' => $website_details['auto_detection_group_id'],
                'manual_detection_group_id' => $website_details['manual_detection_group_id']
            );

            return $this->mm_api($args);
        } else {
            return false;
        }
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

    public function create_free_account($post)
    {
        $args = array(
            'action' => 'add_free_account',
            'name_first' => $post['name_first'],
            'name_last' => $post['name_last'],
            'email' => $post['email'],
        );

        $api_token = $this->mm_api($args);
        if (isset($api_token['status']) && $api_token['status'] === 'success') {
            update_option('webchangedetector_api_token', $api_token['api_token']);

            $this->create_group();
        }
        return $api_token;
    }

    /**
     * @unused
     *
     * @return void
     */
    public function resend_verification_mail()
    {
        $args = array(
            'action' => 'resend_verification_email',
        );
        $this->mm_api($args);
    }

    public function get_api_token_form($api_token = false)
    {
        if ($api_token) {
            $output = '<form action="' . admin_url() . '/admin.php?page=webchangedetector&tab=settings" method="post"
                        onsubmit="return confirm(\'Do you really want to reset the API Token?\nYour settings will get lost.\');">
                        <input type="hidden" name="wcd_action" value="reset_api_token">
                        <h2>API Token</h2>
                        <p>Your API Token: <strong>' . $api_token . '</strong></p>
                        <input type="submit" value="Reset API Token" class="button"><br>
                        <p><strong>ATTENTION: With resetting the API Token, all settings get lost and
                        the monitoring won\'t be continued!</strong></p>';
        } else {
            $output = '<form action="' . admin_url() . '/admin.php?page=webchangedetector&tab=settings" method="post">
                        <input type="hidden" name="wcd_action" value="save_api_token">
                        <h2>2. Your API Token</h2>
                        <p>After creating your account, you get an API Token. Enter this API Token here and start your Change Detections.</p>
                        <input type="text" name="api_token" value="' . $api_token . '"
                            style="width: 550px;" >
                            <!--pattern="[a-z0-9]{20}"
                            oninvalid="this.setCustomValidity(\'Invalid format for api token.\')"
                            onchange="try{setCustomValidity(\'\')}catch(e){}"
                            oninput="setCustomValidity(\' \')"-->
                        <input type="submit" value="Save" class="button">';
        }
        $output .= '</form>';
        return $output;
    }

    public function create_group()
    {
        // Create group if it doesn't exist yet
        $args = array(
            'action' => 'add_website_groups',
            'api_token' => $_POST['api_token'],
            'cms' => 'wordpress'
            //'website_group' => 1,
        );

        return $this->mm_api($args);
    }

    public function delete_website()
    {
        $args = array(
            'action' => 'delete_website',
        );
        $this->mm_api($args);
    }

    public function get_website_details()
    {
        $args = array(
            'action' => 'get_user_websites',
        );
        return $this->mm_api($args);
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

    public function mm_get_url_settings($groups_and_urls, $monitoring_group = false)
    {

        // Sync urls - post_types defined in function @todo make settings for post_types to sync
        $synced_posts = $this->sync_posts();

        // Select URLS
        if ($monitoring_group) {
            $tab = 'auto-settings';
        } else {
            $tab = 'update-settings';
        }

        echo '<form action="' . admin_url() . 'admin.php?page=webchangedetector&tab=' . $tab . '" method="post">';
        echo '<input type="hidden" value="webchangedetector" name="page">';
        echo '<input type="hidden" value="post_urls" name="wcd_action">';
        echo '<input type="hidden" value="' . $groups_and_urls['id'] . '" name="group_id">';

        $post_types = get_post_types();

        foreach ($post_types as $post_type) {
            if ($post_type != 'post' && $post_type != 'page') {
                continue;
            }

            $posts = get_posts([
                'post_type' => $post_type,
                'post_status' => 'publish',
                'numberposts' => -1,
                'order' => 'ASC',
                'orderby' => 'title'
            ]);

            if ($posts) {
                ?>

                <div class="accordion">
                <div class="mm_accordion_title">
                    <h3>
                        <?= ucfirst($post_type) ?><br>

                    </h3>
                    <div class="mm_accordion_content">

                <table>
                    <tr><th>Desktop</th><th>Mobile</th><th>Post Name</th><th>URL</th></tr>
                <?php
                // Select all from same device
                echo '<tr style="background: none; text-align: center">
                            <td><input type="checkbox" id="select-desktop-' . $post_type . '" onclick="mmToggle( this, \'' . $post_type . '\', \'desktop\', \'' . $groups_and_urls['id'] . '\' )" /></td>
                            <td><input type="checkbox" id="select-mobile-' . $post_type . '" onclick="mmToggle( this, \'' . $post_type . '\', \'mobile\', \'' . $groups_and_urls['id'] . '\' )" /></td>
                            <td></td>
                            <td></td>
                        </tr>';
                foreach ($posts as $post) {
                    $url = get_permalink($post);
                    $url_id = false;

                    // Check if current WP post ID is in synced_posts and get the url_id
                    foreach ($synced_posts as $synced_post) {
                        if ($synced_post['cms_resource_id'] == $post->ID) {
                            $url_id = $synced_post['url_id'];
                        }
                    }

                    // If we don't have the url_id, the url is not synced and we continue
                    if (! $url_id) {
                        continue;
                    }

                    $checked = array(
                        'desktop' => '',
                        'mobile' => ''
                    );

                    if (! empty($groups_and_urls['urls'])) {
                        foreach ($groups_and_urls['urls'] as $key => $url_details) {
                            if ($url_details['pivot']['url_id'] == $url_id) {
                                $checked['active'] = 'checked';

                                if ($url_details['pivot']['desktop']) {
                                    $checked['desktop'] = 'checked';
                                }
                                if ($url_details['pivot']['mobile']) {
                                    $checked['mobile'] = 'checked';
                                }
                            }
                        }
                    }

                    echo '<tr class="post_id_' . $groups_and_urls['id'] . '" id="' . $url_id . '" >';
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

                    echo '<td style="text-align: left;">' . $post->post_title . '</td>';
                    echo '<td style="text-align: left;"><a href="' . $url . '" target="_blank">' . $url . '</a></td>';
                    echo '</tr>';

                    echo '<script> mmMarkRows(\'' . $url_id . '\'); </script>';
                }
                echo '</table>';
            }
            ?>
            </div>
            </div>
            </div>
            <?php
        }
        echo '<input class="button" type="submit" value="Save" style="margin-bottom: 30px">';
        echo '</form>';
    }

    public function get_no_account_page($api_token = '')
    {
        delete_option('webchangedetector_api_token');

        $output = '<div class="webchangedetector">
		<h1>Web Change Detector</h1>
		<hr>
		<h2>1. Free Account</h2>
		<p>Create your free account now and get <strong>50 Change Detections</strong> per month for free!<br>
		If you already have an API Token, you can enter it below and start your Change Detections.</p>
		<a href="https://www.webchangedetector.com/account/cart/?a=add&pid=57" target="_blank" class="button">Create Free Account</a>
		<hr>
		' . $this->get_api_token_form($api_token) . '
		</div>';
        return $output;
    }

    public function mm_get_restrictions()
    {
        $args = array(
            'action' => 'get_website_details',
        );

        $restrictions = $this->mm_api($args);

        if (count($restrictions) > 0) {
            return $restrictions[0];
        }
        return $restrictions;
    }

    public function mm_tabs()
    {
        if (isset($_GET['tab'])) {
            $active_tab = $_GET['tab'];
        } else {
            $active_tab = 'change-detections';
        } ?>
        <div class="wrap">
            <h2 class="nav-tab-wrapper">

                <a href="?page=webchangedetector&tab=change-detections"
                   class="nav-tab <?php echo $active_tab == 'change-detections' ? 'nav-tab-active' : ''; ?>">
                    Change Detections</a>
                <a href="?page=webchangedetector&tab=update-settings"
                   class="nav-tab <?php echo $active_tab == 'update-settings' ? 'nav-tab-active' : ''; ?>">
                    Update Settings</a>
                <a href="?page=webchangedetector&tab=auto-settings"
                   class="nav-tab <?php echo $active_tab == 'auto-settings' ? 'nav-tab-active' : ''; ?>">
                    Auto Settings</a>
                <a href="?page=webchangedetector&tab=logs"
                   class="nav-tab <?php echo $active_tab == 'logs' ? 'nav-tab-active' : ''; ?>">Logs</a>
                <a href="?page=webchangedetector&tab=settings"
                   class="nav-tab <?php echo $active_tab == 'settings' ? 'nav-tab-active' : ''; ?>">Settings</a>
                <a href="?page=webchangedetector&tab=help"
                   class="nav-tab <?php echo $active_tab == 'help' ? 'nav-tab-active' : ''; ?>">Help</a>
            </h2>
        </div>

        <?php
    }

    public function mm_api($post)
    {
        $url = mm_get_api_url();

        $url .= str_replace(['client', '_'], ['user', '-'], $post['action']);
        $action = $post['action']; // For debugging

        if (empty($post['api_token'])) {
            $api_token = get_option('webchangedetector_api_token');
        } else {
            $api_token = $post['api_token'];
        }
        unset($post['action']);
        unset($post['api_token']);

        $post['wp_plugin_version'] = $this->version;
        $post['domain'] = $_SERVER['SERVER_NAME'];

        $args = array(
            'body'  => $post,
            'headers' => [
                'Accept' => 'application/json',
                'Authorization' => 'Bearer ' . $api_token,
            ],
        );

        $response = wp_remote_post($url, $args);
        $body = wp_remote_retrieve_body($response);
        $responseCode = wp_remote_retrieve_response_code($response);

        if (!empty(json_decode($body, true)['message']) &&
            json_decode($body, true)['message'] === 'plugin_update_required') {
            echo '<div class="error notice">
                        <p>Me made major changes on the API which requires to update the plugin WebChangeDetector. Please install the update at
                        <a href="/wp-admin/plugins.php">Plugins</a>.</p>
                    </div>';
            die();
        }

        if ($responseCode == HTTP_INTERNAL_SERVER_ERROR && $action === 'account_details') {
            return 'activate account';
        }

        if (! mm_http_successful((int) $responseCode)) {
            //if (mm_dev()) {
                // dd($response, $action, $responseCode, $body);
            //}
        }

        if (is_json($body)) {
            return json_decode($body, (bool) JSON_OBJECT_AS_ARRAY);
        }

        return $body;
    }
}

if (! function_exists('is_json')) {
    function is_json($string)
    {
        json_decode($string);
        return (json_last_error() == JSON_ERROR_NONE);
    }
}

if (! function_exists('dd')) {
    function dd(... $output)
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

function mm_get_api_url() {
    if (defined('WCD_API_URL') && WCD_API_URL) {
        return WCD_API_URL;
    }
    return 'https://api.webchangedetector.com/api/v1';
}

function mm_get_app_domain() {
    if (defined('WCD_APP_DOMAIN') && WCD_APP_DOMAIN) {
        return WCD_APP_DOMAIN;
    }
    return 'https://www.webchangedetector.com';
}

if (! function_exists('mm_dev')) {
    /**
     * Set this if you wanna debug API calls with dd()
     *
     * @return bool
     */
    function mm_dev() : bool
    {
        if( defined('WCD_API_URL') && WCD_API_URL) {
            return true;
        }
        return false;
    }
}

if (! function_exists('mm_http_successful')) {
    /**
     * HTTP Response Code in between 200 (incl) and 300
     *
     * @param int $httpCode
     * @return bool
     */
    function mm_http_successful($httpCode)
    {
        return ($httpCode >= HTTP_OK) && ($httpCode < HTTP_MULTIPLE_CHOICES);
    }
}

if (! defined('HTTP_OK')) {
    define('HTTP_OK', 200);
}
if (! defined('HTTP_INTERNAL_SERVER_ERROR')) {
    define('HTTP_INTERNAL_SERVER_ERROR', 500);
}
if (! defined('HTTP_MULTIPLE_CHOICES')) {
    define('HTTP_MULTIPLE_CHOICES', 300);
}
