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
    const API_TOKEN_LENGTH = 40;
    const PRODUCT_ID_FREE = 57;

    const VALID_WCD_ACTIONS = [
        'reset_api_token',
        'save_api_token',
        'take_screenshots',
        'update_monitoring_settings',
        'post_urls',
        'dashboard',
        'change-detections',
        'update-settings',
        'auto-settings',
        'logs',
        'settings',
        'show-compare',
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
    private $version = '1.1.1';

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
            'mm_wcd_webchangedetector_init',
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

    public function account_details()
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
            'group_id' => $group_id,
        );

        return $this->mm_api($args);
    }

    public function get_comparison_partial($token)
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
            'group_id' => $monitoring_group_id,
            'hour_of_day' => sanitize_key($postdata['hour_of_day']),
            'interval_in_h' => sanitize_key($postdata['interval_in_h']),
            'monitoring' => sanitize_key($postdata['monitoring']),
            'enabled' => sanitize_key($postdata['enabled']),
            'alert_emails' => sanitize_textarea_field($postdata['alert_emails']),
            'name' => sanitize_textarea_field($postdata['group_name']),
        );
        return $this->mm_api($args);
    }

    public function get_upgrade_options($plan_id)
    {
        $args = array(
            'action' => 'get_upgrade_options',
            'plan_id' => $plan_id,
        );
        return $this->mm_api($args);
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

        foreach ($compares as $compare) {
            // Make sure to only show urls from the website. Has to fixed in api.
            if (strpos($compare['screenshot1']['url'], $_SERVER['SERVER_NAME']) === false) {
                continue;
            }

            $return[] = $compare;
        }
        return $return;
    }

    public function compare_view($compares)
    {
        ?>
            <table class="toggle" style="width: 100%">
                <tr>
                    <th width="auto">URL</th>
                    <th width="150px">Compared Screenshots</th>
                    <th width="50px">Difference</th>
                    <th>Show</th>
                </tr>
            <?php
        if (empty($compares)) {
            ?>
            <tr>
                <td colspan="4" style="text-align: center">
                    <strong>There are no change detections yet.</strong
                </td>
            </tr>
            <?php
        } else {
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
                        <?= $compare['screenshot2']['monitoring'] ? $this->get_device_icon('auto-group') : $this->get_device_icon('update-group')?>
                        <?= $compare['screenshot2']['monitoring'] ? 'Auto Detection' : 'Update Detection' ?>
                    </td>
                    <td>
                        <?= date('d/m/Y H:i', $compare['image1_timestamp']) . '<br>' .
                            date('d/m/Y H:i', $compare['image2_timestamp']) ?>
                    </td>
                    <td class="<?= $class ?>"><?= $compare['difference_percent'] ?>%</td>
                    <td>
                        <a href="?page=webchangedetector&tab=show-compare&action=show_compare&token=<?= $compare['token'] ?>"
                           class="button">
                            Show
                        </a>
                    </td>
                </tr>
                <?php
            }
        }
        echo '</table>';
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

    /**
     * Creates Websites and Groups
     *
     * NOTE API Token needs to be sent here because it's not in the options yet
     * at Website creation
     *
     * @param string $api_token
     */
    public function create_group($api_token)
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

    public function delete_website()
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
        if ($monitoring_group) {
            $tab = 'auto-settings';
        }

        echo '<form action="' . admin_url() . 'admin.php?page=webchangedetector&tab=' . $tab . '" method="post">';
        echo '<input type="hidden" value="webchangedetector" name="page">';
        echo '<input type="hidden" value="post_urls" name="wcd_action">';
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

            if ($posts) {
                ?>

                <div class="accordion">
                <div class="mm_accordion_title">
                    <h3>
                        <?= ucfirst($post_type) ?><br>
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
                                }
                                if ($url_details['pivot']['mobile']) {
                                    $checked['mobile'] = 'checked';
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
            } ?>
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
        delete_option(MM_WCD_WP_OPTION_KEY_API_TOKEN);

        $output = '<div class="webchangedetector">
		<h1>Web Change Detector</h1>
		<hr>
		<h2>1. Free Account</h2>
		<p>Create your free account now and get <strong>50 Change Detections</strong> per month for free!<br>
		If you already have an API Token, you can enter it below and start your Change Detections.</p>
		<a href="https://www.webchangedetector.com/account/cart/?a=add&pid=' . self::PRODUCT_ID_FREE . '" target="_blank" class="button">Create Free Account</a>
		<hr>
		' . $this->get_api_token_form($api_token) . '
		</div>';
        return $output;
    }

    public function get_website_details()
    {
        $args = array(
            'action' => 'get_website_details',
            // domain sent at mm_api
        );

        $website_details = $this->mm_api($args);

        if (count($website_details) > 0) {
            return $website_details[0];
        }
        return $website_details;
    }

    public function tabs()
    {
        $active_tab = 'dashboard'; // init

        if (isset($_GET['tab'])) {
            // sanitize: lower-case with "-"
            $active_tab = sanitize_key($_GET['tab']);
        } ?>
        <div class="wrap">
            <h2 class="nav-tab-wrapper">
                <a href="?page=webchangedetector&tab=dashboard"
                   class="nav-tab <?php echo $active_tab == 'dashboard' ? 'nav-tab-active' : ''; ?>">
                   Dashboard</a>
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
            </h2>
        </div>

        <?php
    }

    public function get_dashboard_view($client_account, $update_group_id, $auto_group_id)
    {
        $recent_comparisons = $this->get_compares([$update_group_id, $auto_group_id], null, null, true, 10);

        $auto_group = $this->get_urls_of_group($auto_group_id);
        $amount_auto_detection = 0;
        if ($auto_group['enabled']) {
            $amount_auto_detection += MM_WCD_HOURS_IN_DAY / $auto_group['interval_in_h'] * $auto_group['amount_selected_urls'] * MM_WCD_DAYS_PER_MONTH;
        } ?>
        <div class="dashboard">
            <div>
                <div class="box-half no-border">
                    <a class="box" href="?page=webchangedetector&tab=update-settings">
                        <div style="padding-top:10px; font-size: 60px; width: 50px; float: left;">
                            <?= $this->get_device_icon('update-group') ?>
                        </div>
                        <div style="float: left; max-width: 350px;">
                            <strong>Update Change Detection</strong><br>
                            Create change detections manually
                        </div>
                        <div class="clear"></div>
                    </a>
                    <a class="box" href="?page=webchangedetector&tab=auto-settings">
                        <div style="padding-top:10px; font-size: 60px; width: 50px; float: left;">
                            <?= $this->get_device_icon('auto-group') ?>
                        </div>
                        <div style="float: left; max-width: 350px;">
                            <strong>Auto Change Detection</strong><br>
                            Create automatic change detections
                        </div>
                        <div class="clear"></div>
                    </a>
                    <a class="box" href="?page=webchangedetector&tab=change-detections">
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
                        <?= number_format($amount_auto_detection / MM_WCD_SECONDS_IN_MONTH * (date('U', strtotime($client_account['renewal_at'])) - date('U')), 0) ?></p>

                    <p><strong>Renewal on:</strong> <?= date('d/m/Y', strtotime($client_account['renewal_at'])) ?></p>
                </div>
                <div class="clear"></div>
            </div>


            <div>
                <h2>Latest Change Detections</h2>
                <?php
                $this->compare_view($recent_comparisons);
        if (! empty($recent_comparisons)) { ?>
                    <a class="button" href="?page=webchangedetector&tab=change-detections">Show Change Detections</a>
                <?php } ?>
            </div>

            <div class="clear"></div>
        </div>
        <?php
    }

    public function show_activate_account($error)
    {
        if ($error === 'activate account') { ?>
            <div class="error notice"></span>
                Please <strong>activate</strong> your account by clicking the confirmation link in the email we sent you.
                <p>You cannot find the email? Please also check your spam folder.</p>
            </div>
        <?php
        }

        if ($error === 'unauthorized') { ?>
            <div class="error notice"><span class="dashicons dashicons-warning"></span>
                The API token is not valid. Please reset the API token and enter a valid one.
            </div>
        <?php
        }

        echo $this->get_api_token_form(get_option(MM_WCD_WP_OPTION_KEY_API_TOKEN, true));
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
        // if either .test or dev. can be found in the URL, we're developing
        return strpos($this->app_url(), '.test') !== false || strpos($this->app_url(), 'dev.') !== false;
    }

    /**
     * Call to API
     *
     * This is the only method left with mm_ for historical reasons
     *
     * @param array $post
     * @return string|array
     */
    public function mm_api($post)
    {
        $url = 'https://api.webchangedetector.com/api/v1/'; // init for production

        // This is where it can be changed to a local/dev address
        if (defined('WCD_API_URL') && is_string(WCD_API_URL) && ! empty(WCD_API_URL)) {
            $url = WCD_API_URL;
        }

        $url .= str_replace('_', '-', $post['action']); // add kebab action to url
        $action = $post['action']; // For debugging

        // Get API Token from WP DB
        $api_token = $post['api_token'] ?? get_option(MM_WCD_WP_OPTION_KEY_API_TOKEN);

        unset($post['action']); // don't need to send as action as it's now the url
        unset($post['api_token']); // just in case

        $post['wp_plugin_version'] = $this->version; // API will check this to check compatability
        // there's checks in place on the API side, you can't just send a different domain here, you sneaky little hacker ;)
        $post['domain'] = $_SERVER['SERVER_NAME'];
        $post['wp_id'] = get_current_user_id();

        $args = array(
            'body'  => $post,
            'headers' => array(
                'Accept' => 'application/json',
                'Authorization' => 'Bearer ' . $api_token,
            ),
        );

        $response = wp_remote_post($url, $args);
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

// // Uncommented defines()
// if (! defined('MM_WCD_HTTP_OK')) {
//     define('MM_WCD_HTTP_OK', 200);
// }
// if (! defined('MM_WCD_HTTP_MULTIPLE_CHOICES')) {
//     define('MM_WCD_HTTP_MULTIPLE_CHOICES', 300);
// }

// // Uncommented functions()
// if (! function_exists('dd')) {
//     /**
//      * Dump and Die
//      */
//     function dd(... $output) // this is PHP 5.6+
//     {
//         echo '<pre>';
//         foreach ($output as $o) {
//             if (is_array($o) || is_object($o)) {
//                 print_r($o);
//                 continue;
//             }
//             echo $o;
//         }
//         echo '</pre>';
//         die();
//     }
// }

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
