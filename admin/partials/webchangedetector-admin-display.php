<?php

/**
 * Provide a admin area view for the plugin
 *
 * This file is used to markup the admin-facing aspects of the plugin.
 *
 * @link       wp-mike.com
 * @since      1.0.0
 *
 * @package    WebChangeDetector
 * @subpackage WebChangeDetector/admin/partials
 */


if (! function_exists('mm_wcd_webchangedetector_init')) {
    function mm_wcd_webchangedetector_init()
    {
        $wcd = new WebChangeDetector_Admin();

        $wcd_action = null;
        if (isset($_POST['wcd_action'])) {
            $wcd_action = sanitize_key($_POST['wcd_action']);
            if (! is_string($wcd_action) || ! in_array($wcd_action, WebChangeDetector_Admin::VALID_WCD_ACTIONS)) {
                echo '<div class="error notice"><p>Wrong wcd_action. Please contact developer.</p></div>';
                return false;
            }
        }

        // Actions without API Token needed
        switch ($wcd_action) {
            case 'reset_api_token':
                $wcd->delete_website();
                delete_option(MM_WCD_WP_OPTION_KEY_API_TOKEN);
            break;

            case 'save_api_token':

                if (! isset($_POST['api_token'])) {
                    echo '<div class="error notice"><p>No API Token given.</p></div>';
                    return false;
                }

                $api_token = sanitize_textarea_field($_POST['api_token']);

                if ($wcd->dev()) {
                    // using emails as api_token to develop on localhost
                    $api_token = sanitize_email($_POST['api_token']);
                }

                if (! is_string($api_token) || (! $wcd->dev() && strlen($api_token) < WebChangeDetector_Admin::API_TOKEN_LENGTH)) {
                    echo '<div class="error notice"><p>The API Token is invalid. Please try again.</p></div>';
                    echo $wcd->get_no_account_page();
                    return false;
                }

                $website = $wcd->create_group($api_token);

                if (empty($website)) {
                    echo '<div class="error notice"><p>The API Token is invalid. Please try again.</p></div>';
                    echo $wcd->get_no_account_page();
                    return false;
                }

                update_option(MM_WCD_WP_OPTION_KEY_API_TOKEN, $api_token);
                $wcd->sync_posts();

            break;
        }

        $api_token = get_option(MM_WCD_WP_OPTION_KEY_API_TOKEN);

        // Change api token option name from V1.0.7
        if (! $api_token) {
            $api_token = get_option('webchangedetector_api_key');
            if ($api_token) {
                delete_option('webchangedetector_api_key');
                add_option(MM_WCD_WP_OPTION_KEY_API_TOKEN, $api_token, '', false);
            }
        }

        // The account doesn't have an api_token
        if (! $api_token) {
            echo $wcd->get_no_account_page();
            return false;
        }

        $account_details = $wcd->account_details();

        // Check if account is activated and if the api key is authorized
        if ($account_details === 'activate account' || $account_details === 'unauthorized') {
            $wcd->show_activate_account($account_details);
            return false;
        }

        $website_details = $wcd->get_website_details();

        $group_id = ! empty($website_details['manual_detection_group_id']) ? $website_details['manual_detection_group_id'] : null;
        $monitoring_group_id = ! empty($website_details['auto_detection_group_id']) ? $website_details['auto_detection_group_id'] : null;

        $monitoring_group_settings = null;

        if ($monitoring_group_id) {
            $wcd->get_monitoring_settings($monitoring_group_id);
        }

        // Perform actions
        switch ($wcd_action) {
            case 'take_screenshots':

                $scType = sanitize_key($_POST['sc_type']);

                if (! in_array($scType, WebChangeDetector_Admin::VALID_SC_TYPES)) {
                    echo '<div class="error notice"><p>Wrong Screenshot type.</p></div>';
                    return false;
                }

                $results = $wcd->take_screenshot($group_id, $scType);

                if (is_array($results) && count($results) > 1) {
                    if ($results[0] === 'error') {
                        echo '<div class="error notice"><p>' . $results[1] . '</p></div>';
                    }

                    if ($results[0] === 'success') {
                        echo '<div class="updated notice"><p>' . $results[1] . '</p></div>';
                    }
                }
                break;

            case 'update_monitoring_settings':
                $wcd->update_monitoring_settings($_POST, $monitoring_group_id);
                break;

            case 'post_urls':
                // Get active posts from post data
                $active_posts = array();
                $count_selected = 0;
                foreach ($_POST as $key => $post_id) {
                    if (strpos($key, 'url_id') === 0) {

                        // sanitize before
                        $wpPostId = sanitize_key($_POST['post_id-'. $post_id]); // should be numeric
                        if (! is_numeric($wpPostId)) {
                            continue; // just skip it
                        }
                        $permalink = get_permalink($wpPostId); // should return the whole link
                        $desktop = array_key_exists('desktop-'. $post_id, $_POST) ? sanitize_key($_POST['desktop-' . $post_id]) : 0;
                        $mobile = array_key_exists('mobile-'. $post_id, $_POST) ? sanitize_key($_POST['mobile-' . $post_id]) : 0;

                        $active_posts[] = array(
                            'url_id' => $post_id,
                            'url' => $permalink,
                            'desktop' => $desktop,
                            'mobile' => $mobile
                        );
                        if (isset($_POST['desktop-' . $post_id])) {
                            $count_selected++;
                        }

                        if (isset($_POST['mobile-' . $post_id])) {
                            $count_selected++;
                        }
                    }
                }

                $group_id_website_details = sanitize_key($_POST['group_id']);

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
                    // Update API URLs
                    $wcd->update_urls($group_id_website_details, $active_posts);
                    echo '<div class="updated notice"><p>Settings saved.</p></div>';
                }
                break;
        }

        // Get updated account and website data
        $account_details = $wcd->account_details();
        $website_details = $wcd->get_website_details();

        // Start view
        echo '<div class="wrap">';
        echo '<div class="webchangedetector">';
        echo '<h1>WebChangeDetector</h1>';

        $wcd->tabs();

        echo '<div style="margin-top: 30px;"></div>';

        $tab = 'dashboard'; // init
        if (isset($_GET['tab'])) {
            // sanitize: lower-case with "-"
            $tab = sanitize_key($_GET['tab']);
        }

        // Account credits
        $comp_usage = $account_details['usage'];
        $limit = $account_details['sc_limit'];
        $available_compares = $account_details['available_compares'];

        if ($website_details['enable_limits']) {
            $account_details['usage'] = $comp_usage; // used in dashboard
            $account_details['plan']['sc_limit'] = $limit; // used in dashboard
        }

        // Renew date
        $renew_date = strtotime($account_details['renewal_at']);

        switch ($tab) {
            case 'dashboard':
            $wcd->get_dashboard_view($account_details, $group_id, $monitoring_group_id);
            break;

            /********************
             * Change Detections
             ********************/

            case 'change-detections':
                echo '<h2>Latest Change Detections</h2>';

                $limit_days = null;
                if (isset($_POST['limit_days'])) {
                    $limit_days = sanitize_key($_POST['limit_days']);
                    if (! empty($limit_days) && ! is_numeric($limit_days)) {
                        echo '<div class="error notice"><p>Wrong limit_days.</p></div>';
                        return false;
                    }
                }
                $group_type = null;
                if (isset($_POST['group_type'])) {
                    $group_type = sanitize_key($_POST['group_type']);
                    if (! empty($group_type) && ! in_array($group_type, WebChangeDetector_Admin::VALID_GROUP_TYPES)) {
                        echo '<div class="error notice"><p>Invalid group_type.</p></div>';
                        return false;
                    }
                }

                $difference_only = null;
                if (isset($_POST['difference_only'])) {
                    $difference_only = sanitize_key($_POST['difference_only']);

                    // difference_only can be any string/number
                }

                $compares = $wcd->get_compares([$group_id, $monitoring_group_id], $limit_days, $group_type, $difference_only);
                ?>
                <div class="action-container">
                    <form method="post">
                        <select name="limit_days">
                            <option value="" <?= $limit_days == null ? 'selected' : '' ?>> Show all</option>
                            <option value="3" <?= $limit_days == 3 ? 'selected' : '' ?>>Last 3 days</option>
                            <option value="7" <?= $limit_days == 7 ? 'selected' : '' ?>>Last 7 days</option>
                            <option value="14" <?= $limit_days == 14 ? 'selected' : '' ?>>Last 14 days</option>
                            <option value="30"<?= $limit_days == 30 ? 'selected' : '' ?>>Last 30 days</option>
                            <option value="60"<?= $limit_days == 60 ? 'selected' : '' ?>>Last 60 days</option>
                        </select>

                        <select name="group_type" >
                            <option value="" <?= ! $group_type ? 'selected' : '' ?>>All Change Detections</option>
                            <option value="update" <?= $group_type === 'update' ? 'selected' : '' ?>>Only Update Change Detections</option>
                            <option value="auto" <?= $group_type === 'auto' ? 'selected' : '' ?>>Only Auto Change Detections</option>
                        </select>

                        <select name="difference_only" class="js-dropdown">
                            <option value="1" <?= $difference_only ? 'selected' : '' ?>>With difference</option>
                            <option value="0" <?= ! $difference_only ? 'selected' : '' ?>>All detections</option>
                        </select>

                        <input class="button" type="submit" value="Filter">
                    </form>
                    <?php

                    $wcd->compare_view($compares);
                    ?>
                </div>
                <div class="sidebar">
                    <div class="account-box">
                        <?php include 'templates/account.php'; ?>
                    </div>
                    <div class="help-box">
                        <?php include 'templates/help-change-detection.php'; ?>
                    </div>
                </div>
                <div class="clear"></div>

                <?php
                break;

            /***************************
             * Update Change Detections
            ****************************/

            case 'update-settings':
                if ($website_details['enable_limits'] && ! $website_details['allow_manual_detection']) {
                    echo 'Settings for Update Change detections are disabled by your API Token.';
                    break;
                }

                // Get amount selected Screenshots
                $groups_and_urls = $wcd->get_urls_of_group($group_id);
                ?>

                <div class="action-container">
                    <div class="status_bar">Current settings require<br>
                        <span class="big">
                            <?= $groups_and_urls['amount_selected_urls'] ?>
                            Screenshots
                        </span><br>
                        <?= $account_details['available_compares'] ?> available until renewal
                    </div>
                    <form action="<?= admin_url() ?>/admin.php?page=webchangedetector&tab=update-settings" method="post" class="sc_button">
                        <input type="hidden" value="take_screenshots" name="wcd_action">
                        <input type="hidden" name="sc_type" value="pre">
                        <button type="submit" class="button">
                            <span class="button_headline">Create Reference Screenshots</span><br>
                            <span>Take screenshots <strong>before</strong> you do updates. The screenshots after the update will be compared with these screenshots.</span>
                        </button>
                    </form>

                    <form action="<?= admin_url() ?>/admin.php?page=webchangedetector&tab=update-settings" method="post" class="sc_button last">
                        <input type="hidden" value="take_screenshots" name="wcd_action">
                        <input type="hidden" name="sc_type" value="post">
                        <button type="submit" class="button">
                            <span class="button_headline">Create Change Detections </span><br>
                            <span>Take screenshots <strong>after</strong> you finished the updates and compare them with the reference screenshots.</span>
                        </button>
                    </form>
                    <div class="clear" style="margin-bottom: 30px;"></div>

                    <h2>Select Update Change Detection URLs</h2>
                    <?php $wcd->get_url_settings($groups_and_urls); ?>

                </div>

                <div class="sidebar">
                    <div class="account-box">
                        <?php include 'templates/account.php'; ?>
                    </div>
                    <div class="help-box">
                        <?php include 'templates/help-update.php'; ?>
                    </div>
                </div>
                <div class="clear"></div>
                <?php
                break;

            /**************************
             * Auto Change Detections
             **************************/

            case 'auto-settings':
                if ($website_details['enable_limits'] && ! $website_details['allow_auto_detection']) {
                    echo 'Settings for Update Change detections are disabled by your API Token.';
                    break;
                }

                $groups_and_urls = $wcd->get_urls_of_group($monitoring_group_id);

                //var_dump($groups_and_urls);
                $hour_of_day = $groups_and_urls['hour_of_day'];
                $interval = $groups_and_urls['interval_in_h'];

                for($i=0; $i < 24/$interval; $i++) {
                    $current_hour = $hour_of_day + $i * $interval;
                    $sc_hours[] = $current_hour > 23 ? $current_hour - 24 : $current_hour;
                }

                // Calculation for auto detections
                $date_next_sc = false;

                $next_possible_sc = mktime(date("H") + 1,0,0,date("m"),date("d"),date("Y"));
                $amount_sc_per_day = (24 / $groups_and_urls['interval_in_h']);

                // We check all dates from selected start hour yesterday until tomorrow (amount_sc_per_day * 3)
                for( $i = 0; $i <= $amount_sc_per_day * 3; $i++ ) {
                    $time_take_sc = mktime( $groups_and_urls['hour_of_day'] + $i * $groups_and_urls['interval_in_h'], 0, 0, date( "m" ), date( "d" ) - 1, date( "Y" ) );

                    // If we don't have a date yet take the first which is in the future
                    // If we have a date we check if the current one is closer in the future
                    if( ( !$date_next_sc && $time_take_sc >= $next_possible_sc ) || ( $date_next_sc > $time_take_sc && $time_take_sc >= $next_possible_sc ) ) {
                        $date_next_sc = $time_take_sc;
                    }
                }

                // Calculate total change detections
                $date_next_renewal = strtotime( $account_details['renewal_at'] );
                $total_sc_current_period = 0;
                $date_next_sc = false;
                $next_possible_sc = mktime( date( "H" ) + 1, 0, 0, date( "m" ), date( "d" ), date( "Y" ) );
                $amount_sc_per_day = ( 24 / $groups_and_urls['interval_in_h'] );

                // We check all dates from selected start hour yesterday until tomorrow (amount_sc_per_day * 3)
                for( $i = 0; $i <= $amount_sc_per_day * 3; $i++ ) {
                    $time_take_sc = mktime( $groups_and_urls['hour_of_day'] + $i * $groups_and_urls['interval_in_h'], 0, 0, date( "m" ), date( "d" ) - 1, date( "Y" ) );

                    // If we don't have a date yet take the first which is in the future
                    // If we have a date we check if the current one is closer in the future
                    if( ( !$date_next_sc && $time_take_sc >= $next_possible_sc ) ) {
                        $date_next_sc = $time_take_sc;
                    }
                }

                // Calculate total screenshots until renewal
                $total_date_next_sc = $date_next_sc;
                while( $date_next_renewal >= $total_date_next_sc ) {
                    $total_sc_current_period++;
                    $total_date_next_sc = $total_date_next_sc + $groups_and_urls['interval_in_h'] * 3600;
                }
                ?>

                <div class="action-container">
                    <div class="status_bar">
                        <div class="box half">
                            <div id="txt_next_sc_in">Next change detections in</div>
                            <div id="next_sc_in" class="big"></div>
                            <div id="next_sc_date" class="local-time" data-date="<?= $date_next_sc ?>"></div>
                        </div>
                        <div class="box half">
                            Current settings require
                            <div id="sc_until_renew" class="big">
                                <span id="ajax_amount_total_sc"></span> Screenshots
                            </div>
                            <div id="sc_available_until_renew"
                                 data-amount_selected_urls="<?= $groups_and_urls['amount_selected_urls'] ?>"
                                 data-auto_sc_per_url_until_renewal="<?= $total_sc_current_period ?>"
                            >
                                <?= $account_details['available_compares'] ?> available until renewal
                            </div>
                        </div>
                        <div class="clear"></div>
                    </div>

                    <!-- Auto Detection Settings -->
                    <div class="accordion" style="margin-bottom: 40px;">
                        <div class="mm_accordion_title">
                            <h3>
                                Settings for Auto Change Detection<br>
                                <small>
                                    <?php
                                    $enabled = $groups_and_urls['enabled'];
                                    if($enabled) {
                                        ?>
                                        Currently: <strong>Tracking</strong> |
                                        Interval: <strong>
                                            every
                                            <?= $groups_and_urls['interval_in_h'] ?>
                                            <?= $groups_and_urls['interval_in_h'] === 1 ? " hour" : " hours"?>
                                        </strong> |
                                        Notifications to: <strong><?= implode(", ", $groups_and_urls['alert_emails']) ?></strong>
                                        <?php
                                    } else { ?>
                                        Currently: <strong>Not tracking</strong>
                                    <?php } ?>
                                </small>
                            </h3>
                            <div class="mm_accordion_content padding">
                                <?php include 'templates/auto-settings.php'; ?>
                            </div>
                        </div>
                    </div>

                    <h2>Select Auto Change Detection URLs</h2>
                    <p class="status_bar">
                        Currently selected:
                        <strong>
                            <?= $groups_and_urls['amount_selected_urls'] ?>
                            Change Detections
                        </strong>
                    </p>
                    <?php $wcd->get_url_settings($groups_and_urls, true); ?>

                </div>

                <div class="sidebar">
                    <div class="account-box">
                        <?php include 'templates/account.php'; ?>
                    </div>
                    <div class="help-box">
                        <?php include 'templates/help-auto.php'; ?>
                    </div>
                </div>
                <div class="clear"></div>
                <?php
                break;

            /*********
             * Logs
             *********/

            case 'logs':
                // Show queued urls
                $queues = $wcd->get_queue();

                $type_nice_name = array(
                    'pre' => 'Reference Screenshot',
                    'post' => 'Compare Screenshot',
                    'auto' => 'Auto Detection',
                    'compare' => 'Change Detection',
                );
                ?>
                <div class="action-container">
                <?php
                    if (! empty($queues) && is_iterable($queues)) {
                        echo '<table class="queue">';
                        echo '<tr><th></th><th width="100%">Page & URL</th><th>Type</th><th>Status</th><th>Added</th><th>Last changed</th></tr>';
                        foreach ($queues as $queue) {
                            $group_type = $queue['monitoring'] ? 'Auto Change Detection' : 'Update Change Detection';
                            echo '<tr class="queue-status-' . $queue['status'] . '">';
                            echo '<td>' . $wcd->get_device_icon($queue['device']) . '</td>';
                            echo '<td>
                                        <span class="html-title queue"> ' . $queue['url']['html_title'] . '</span><br>
                                        <span class="url queue">URL: '.$queue['url']['url'] . '</span><br>
                                        ' . $group_type . '
                                </td>';


                            echo '<td>' . $type_nice_name[$queue['sc_type']] . '</td>';
                            echo '<td>' . ucfirst($queue['status']) . '</td>';
                            echo '<td class="local-time" data-date="' . strtotime($queue['created_at']) . '">' .  date('d/m/Y H:i:s', strtotime($queue['created_at'])) . '</td>';
                            echo '<td class="local-time" data-date="' . strtotime($queue['updated_at']) . '">' .  date('d/m/Y H:i:s', strtotime($queue['updated_at'])) . '</td>';
                            echo '</tr>';
                        }
                        echo '</table>';
                    } else {
                        echo 'Nothing to show yet.';
                    }
                    ?>
                </div>
                <div class="sidebar">
                    <div class="account-box">
                        <?php include 'templates/account.php'; ?>
                    </div>
                    <div class="help-box">
                        <?php include 'templates/help-logs.php'; ?>
                    </div>
                </div>
                <div class="clear"></div>
                <?php
                break;

            /***********
             * Settings
             ***********/

            case 'settings':

                if (! $api_token) {
                    echo '<div class="error notice">
                    <p>Please enter a valid API Token.</p>
                </div>';
                } elseif (! $website_details['enable_limits']) {
                    echo '<h2>Your credits</h2>';
                    echo 'Your current plan: <strong>' . esc_html($account_details['plan']['name']) . '</strong><br>';
                    echo 'Next renew: ' . date('d/m/Y', $renew_date);
                    echo '<p>Change detections in this period: ' . esc_html($limit) . '<br>';
                    echo 'Used change detections: ' . esc_html($comp_usage) . '<br>';
                    echo 'Available change detections in this period: ' . esc_html($available_compares) . '</p>';
                    echo '<h2>Need more screenshots?</h2>';
                    echo '<a class="button" href="' . $wcd->app_url() . 'account/upgrade/?type=package&id=' . $account_details['whmcs_service_id'] . '">Upgrade</a>';
                    echo '<p>The new amount of compares will be available immediately. The renew date will not change with an upgrade.</p>';
                    //echo( $wcd->get_upgrade_options($account_details['plan_id']));
                }
                echo $wcd->get_api_token_form($api_token);
                break;

            /***************
             * Show compare
             ***************/
            case 'show-compare':
                echo $wcd->get_comparison_by_token($_GET['token']);
            /*
                include 'templates/show-change-detection.php';
                echo '<h1>The Change Detection Images</h1>';
                // [sic], see https://developer.wordpress.org/reference/functions/sanitize_textarea_field
                $public_link = $wcd->app_url() . 'show-change-detection/?token=' . sanitize_textarea_field($_GET['token']);
                echo '<p>Public link: <a href="' . $public_link . '" target="_blank">' . $public_link . '</a></p>';
                $back_button = '<a href="' . $_SERVER['HTTP_REFERER'] . '" class="button" style="margin: 10px 0;">Back</a><br>';
                echo $back_button;
                // [sic], see https://developer.wordpress.org/reference/functions/sanitize_textarea_field
                echo $wcd->get_comparison_partial(sanitize_textarea_field($_GET['token']));
                echo '<div class="clear"></div>';
                echo $back_button;*/
            break;
            default:
                // Should already be validated by VALID_WCD_ACTIONS
            break;
            echo '</div>'; // closing from div webchangedetector
            echo '</div>'; // closing wrap
        } // switch
    } // mm_wcd_webchangedetector_init
} // function_exists
