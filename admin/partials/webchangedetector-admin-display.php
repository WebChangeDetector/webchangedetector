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


if (! function_exists('wcd_webchangedetector_init')) {
    function wcd_webchangedetector_init()
    {
        // Start view
        echo '<div class="wrap">';
        echo '<div class="webchangedetector">';
        echo '<h1>WebChangeDetector</h1>';

        $wcd = new WebChangeDetector_Admin();

        // Validate action
        $wcd_action = null;
        if (isset($_POST['wcd_action'])) {
            $wcd_action = sanitize_key($_POST['wcd_action']);
            if (! is_string($wcd_action) || ! in_array($wcd_action, WebChangeDetector_Admin::VALID_WCD_ACTIONS)) {
                echo '<div class="error notice"><p>Ooops! There was an unknown action called. Please contact us.</p></div>';
                return false;
            }
        }

        // Actions without API Token needed
        switch ($wcd_action) {
            case 'create_free_account':

                // Validate if all required fields were sent
                if(! ($_POST['name_first'] && $_POST['name_last'] && $_POST['email'] && $_POST['password'])) {
                    echo '<div class="notice notice-error"><p>Please fill all required fields.</p></div>';
                    echo $wcd->get_no_account_page();
                    return false;
                }

                $api_token = $wcd->create_free_account($_POST);

                // if we get an array it is an error message
                if(is_array($api_token)) {
                    if(!empty($api_token[0]) && $api_token[0] === 'error' && !empty($api_token[1])) {
                        echo '<div class="notice notice-error"><p>' . $api_token[1] . '</p></div>';

                    } else {
                        echo '<div class="notice notice-error">
                                <p>Something went wrong. Please try again. If the issue persists please contact us.</p>
                                </div>';
                    }
                    echo $wcd->get_no_account_page();
                    return false;
                }

                $wcd->save_api_token($api_token);
                break;

            case 'reset_api_token':
                delete_option(MM_WCD_WP_OPTION_KEY_API_TOKEN);
                break;

            case 're-add-api-token':
                if(empty($_POST['api_token'])) {
                    echo $wcd->get_no_account_page();
                    return true;
                }
                $api_token = $_POST['api_token'];
                $wcd->save_api_token($api_token);
                break;

            case 'save_api_token':
                if (empty($_POST['api_token'])) {
                    echo '<div class="notice notice-error"><p>No API Token given.</p></div>';
                    echo $wcd->get_no_account_page();
                    return false;
                }
                $api_token = $_POST['api_token'];
                $wcd->save_api_token($_POST['api_token']);
                break;
        }

        // If we didn't get the api token from an action we take it from options
        if(empty($api_token)) {
            $api_token = get_option( MM_WCD_WP_OPTION_KEY_API_TOKEN );
        }

        // Change api token option name from V1.0.7
        if (! $api_token) {
            $api_token = get_option('webchangedetector_api_key');
            if ($api_token) {
                delete_option('webchangedetector_api_key');
                add_option(MM_WCD_WP_OPTION_KEY_API_TOKEN, $api_token, '', false);
            }
        }

        // We still don't have an api_token
        if (! $api_token) {
            echo $wcd->get_no_account_page();
            return false;
        }

        // Get the account details
        $account_details = $wcd->account_details($api_token);

        // Check if account is activated and if the api key is authorized
        if (! is_array($account_details) && ($account_details === 'activate account' || $account_details === 'unauthorized')) {
            $wcd->show_activate_account($account_details);
            return false;
        }

        // Show low credits
        $usage_percent = (int)($account_details['usage'] / $account_details['sc_limit'] * 100);
        //dd($usage_percent);
        if($usage_percent >= 100) {
            if( $account_details['plan']['one_time'] ) { // Check for trial account ?>
                <div class="notice notice-error">
                    <p>You ran out of screenshots. Please upgrade your account to continue.</p>
                </div>
            <?php } else { ?>
                <div class="notice notice-error">
                    <p>You ran out of screenshots. Please upgrade your account to continue or wait for renewal.</p>
                </div>
            <?php }
        } elseif($usage_percent > 70) { ?>
            <div class="notice notice-warning"><p>You used <?= $usage_percent ?>% of your screenshots.</p></div>
        <?php }

        // Get the website details
        $website_details = $wcd->get_website_details();

        // If we don't have websites details yet, we create them. This happens after account activation
        if (! $website_details) {
            $website_details = $wcd->create_website_and_groups($api_token);
        }

        // If we don't have the website for any reason we show an error message.
        if(empty($website_details)) { ?>
            <div class="notice notice-error">
                <br>Ooops! We couldn't find your settings. Please try reloading the page. <br>
                If the issue persists, please contact us.</p>
                <p>
                    <form method="post">
                        <input type="hidden" name="wcd_action" value="re-add-api-token">
                        <input type="submit" value="Re-add website" class="button-primary">
                    </form>
                </p>
            </div>
            <?php
            return false;
        }

        $group_id = ! empty($website_details['manual_detection_group_id']) ? $website_details['manual_detection_group_id'] : null;
        $monitoring_group_id = ! empty($website_details['auto_detection_group_id']) ? $website_details['auto_detection_group_id'] : null;

        $monitoring_group_settings = null; // @TODO Can be deleted?

        // Perform actions
        switch ($wcd_action) {
            case 'take_screenshots':

                $scType = sanitize_key($_POST['sc_type']);

                if (! in_array($scType, WebChangeDetector_Admin::VALID_SC_TYPES)) {
                    echo '<div class="error notice"><p>Wrong Screenshot type.</p></div>';
                    return false;
                }

                $results = $wcd->take_screenshot($group_id, $scType);

                if ($results && is_array($results) && count($results) > 1) {
                    if ($results[0] === 'error') {
                        echo '<div class="error notice"><p>' . $results[1] . '</p></div>';
                    }
                }
                break;

            case 'update_monitoring_settings':
                $wcd->update_monitoring_settings($_POST, $monitoring_group_id);
                break;

            case 'update-settings':
                $wcd->update_settings($_POST, $group_id);
                break;

            case 'update_monitoring_and_update_settings':
                if(! empty($_POST['wcd-update-settings'])) {
                    $wcd->update_settings( $_POST, $monitoring_group_id ); // only saves css for monitoring group
                } else {
                    $wcd->update_monitoring_settings( $_POST, $monitoring_group_id ); // saves all monitoring settings
                }
                $wcd->update_settings( $_POST, $group_id ); // saves update settings (currently only css)
                break;

            case 'copy_url_settings':
                $wcd->copy_url_settings($_POST['copy_from_group_id'],$_POST['copy_to_group_id']);
                break;

            case 'post_urls_update_and_auto':
                $wcd->post_urls($_POST, $website_details, true);
                break;

            case 'post_urls':
                $wcd->post_urls($_POST, $website_details, false);
                break;
        }

        // Get updated account and website data
        $account_details = $wcd->account_details();

        // Error message if api didn't return account details.
        if(empty($account_details['status'])) {
            ?>
            <div class="error notice">
                <p>Ooops! Something went wrong. Please try again.</p>
                <p>If the issue persists, please contact us.</p>
            </div>
            <?php
            return false;
        }

        // Check for account status
        if($account_details['status'] !== "active"){

            // Set error message
            $err_msg = "cancelled";
            if(! empty($account_details['status'])) {
                $err_msg = $account_details['status'];
            }
            echo '
            <div class="error notice">
                <h3>Your account was ' . $err_msg . '.</h3>
                <p>Please <a href="' . $wcd->get_upgrade_url() . '">Upgrade</a> your account to re-activate your account.</p>
                <p>To use a different account, please reset the API token.
                    <form method="post">
                        <input type="hidden" name="wcd_action" value="reset_api_token">
                        <input type="submit" value="Reset API token">
                    </form>
                </p>
            </div>';
            return false;
        }

        // Get page to view
        $tab = 'webchangedetector-dashboard'; // init
        if (isset($_GET['page'])) {
            // sanitize: lower-case with "-"
            $tab = sanitize_key($_GET['page']);
        }

        $website_details = $wcd->get_website_details();

        // Check if website details are available.
        if( empty($website_details)) {
            echo '<div class="error notice"><p>
                    We couldn\'t find your website settings. Please reset the API token in 
                    settings and re-add your website with your API Token.
                    </p><p>
                    Your current API token is: <strong>' . $api_token . '</strong>.
                    </p>
                     <form method="post">
                        <input type="hidden" name="wcd_action" value="reset_api_token">
                        <input type="hidden" name="api_token" value="' . $api_token . '">
                        <input type="submit" class="button" value="Reset API token">
                    </form>
                    </p>
                   </div>';
            return false;
        }

        $sc_processing = $wcd->get_processing_queue();
        if($sc_processing) {
            echo '<div id="wcd-currently-in-progress" class="notice-info notice">
                    <p id="currently-processing-container">
                    <span id="currently-processing-spinner" class="spinner"></span>
                        Currently <strong>
                        <span id="currently-processing">' . $sc_processing . '</span> screenshots / change detections </strong> 
                        are in progress. Check the Logs for more details.
                    </p>
                </div>';
        }

        $wcd->tabs();

        echo '<div style="margin-top: 30px;"></div>';

        // Account credits
        $comp_usage = $account_details['usage'];
        $limit = $account_details['sc_limit'];
        $available_compares = $account_details['available_compares'];

        if ($website_details['enable_limits']) {
            $account_details['usage'] = $comp_usage; // used in dashboard
            $account_details['plan']['sc_limit'] = $limit; // used in dashboard
        }

        // Renew date (used in template)
        $renew_date = strtotime($account_details['renewal_at']);

        switch ($tab) {

            /********************
             * Dashboard
             ********************/

            case 'webchangedetector':
            $wcd->get_dashboard_view($account_details, $group_id, $monitoring_group_id);
            break;

            /********************
             * Change Detections
             ********************/

            case 'webchangedetector-change-detections':
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
                            <option value="" <?= ! $group_type ? 'selected' : '' ?>>Auto & Update Detections</option>
                            <option value="update" <?= $group_type === 'update' ? 'selected' : '' ?>>Only Update Change Detections</option>
                            <option value="auto" <?= $group_type === 'auto' ? 'selected' : '' ?>>Only Auto Change Detections</option>
                        </select>

                        <select name="difference_only" class="js-dropdown">
                            <option value="0" <?= ! $difference_only ? 'selected' : '' ?>>All detections</option>
                            <option value="1" <?= $difference_only ? 'selected' : '' ?>>With difference</option>
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

            case 'webchangedetector-update-settings':
                if ($website_details['enable_limits'] && ! $website_details['allow_manual_detection']) {
                    echo 'Settings for Update Change detections are disabled by your API Token.';
                    break;
                }

                // Get amount selected Screenshots
                $groups_and_urls = $wcd->get_urls_of_group($group_id);

                // Show message if no urls are selected
                if(! $groups_and_urls['amount_selected_urls']) {?>
                    <div class="notice notice-warning"><p>Select URLs for update detection to get started.</p></div>
                <?php } ?>

                <div class="action-container">
                    <div class="status_bar">Currently selected<br>
                        <span class="big">
                            <?= $groups_and_urls['amount_selected_urls'] ?>
                            Screenshots
                        </span><br>
                        <?= $account_details['available_compares'] ?> available until renewal
                    </div>

                    <?php $disabled =  $groups_and_urls['amount_selected_urls'] ? '' : 'disabled'; ?>
                    <div style="margin: 40px 0; overflow: hidden;">

                        <div class="sc_button">
                            <form id="frm-take-pre-sc" action="<?= admin_url() ?>/admin.php?page=webchangedetector-update-settings" method="post">
                                <input type="hidden" value="take_screenshots" name="wcd_action">
                                <input type="hidden" name="sc_type" value="pre">
                                <button type="submit" class="button-primary" style="width: 100%;" <?= $disabled ?> >
                                    <span class="button_headline">1. Take Pre-Update Screenshots</span><br>
                                    <span>Take screenshots <strong>before</strong> you install updates.</span>

                                </button>
                            </form>
                        </div>

                        <div class="sc_button no-click">
                            <div class="sc_button_inner" style="width: 100%;" <?= $disabled ?>>
                                <span class="button_headline">2. Update your website</span><br>
                                <span>
                                    Install <a href="<?= get_admin_url() ?>update-core.php">updates</a> or make changes on your website.
                                </span>
                            </div>
                        </div>

                        <div class="sc_button last">
                            <form id="frm-take-post-sc" action="<?= admin_url() ?>/admin.php?page=webchangedetector-update-settings" method="post" >
                                <input type="hidden" value="take_screenshots" name="wcd_action">
                                <input type="hidden" name="sc_type" value="post">
                                <button type="submit" class="button-primary" style="width: 100%;" <?= $disabled ?>>
                                    <span class="button_headline">3. Create Change Detections </span><br>
                                    <span>Take & compare screenshots <strong>after</strong> the updates.</span>
                                </button>
                            </form>
                        </div>
                    </div>
                    <div class="clear"></div>
                    <div class="wcd-settings-container">
                        <h2 style="text-align: center;">General settings for Update Detection</h2>
                        <div class="accordion">
                            <div class="mm_accordion_title">
                                <h3>
                                    <span class="accordion-title">
                                        Update Detection Settings
                                    </span>
                                </h3>
                                <div class="mm_accordion_content">
                                    <form method="post" style="padding: 20px;">
                                        <input type="hidden" name="wcd-update-settings" value="true">
                                        <?php include("templates/css-settings.php"); ?>

                                        <button
                                            type="submit"
                                            name="wcd_action"
                                            value="update-settings"
                                            class="button button-primary">
                                                Save Settings
                                        </button>
                                        <button
                                            type="submit"
                                            name="wcd_action"
                                            value="update_monitoring_and_update_settings"
                                            class="button"
                                            style="margin-left: 10px;">
                                            Save Settings to auto detection too
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php $wcd->get_url_settings($groups_and_urls); ?>
                    <!-- Copy settings to auto detection -->
                    <p>

                    </p>
                    <div class="clear"></div>

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

            case 'webchangedetector-auto-settings':
                if ($website_details['enable_limits'] && ! $website_details['allow_auto_detection']) {
                    echo 'Settings for Update Change detections are disabled by your API Token.';
                    break;
                }

                $groups_and_urls = $wcd->get_urls_of_group($monitoring_group_id);

                $hour_of_day = $groups_and_urls['hour_of_day'];
                $interval = $groups_and_urls['interval_in_h'];

                for($i=0; $i < 24/$interval; $i++) {
                    $current_hour = $hour_of_day + $i * $interval;
                    $sc_hours[] = $current_hour > 23 ? $current_hour - 24 : $current_hour;
                }

                // Calculation for auto detections
                $date_next_sc = false;

                $next_possible_sc = gmmktime(gmdate("H") + 1,0,0, gmdate("m"), gmdate("d"), gmdate("Y"));
                $amount_sc_per_day = (24 / $interval);

                // We check all dates from selected start hour yesterday until tomorrow (amount_sc_per_day * 3)
                for( $i = 0; $i <= $amount_sc_per_day * 3; $i++ ) {
                    $time_take_sc = gmmktime( $groups_and_urls['hour_of_day'] + $i * $groups_and_urls['interval_in_h'], 0, 0, gmdate( "m" ), gmdate( "d" ) - 1, gmdate( "Y" ) );

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
                $next_possible_sc = gmmktime( gmdate( "H" ) + 1, 0, 0, gmdate( "m" ), gmdate( "d" ), gmdate( "Y" ) );
                $amount_sc_per_day = ( 24 / $groups_and_urls['interval_in_h'] );

                // We check all dates from selected start hour yesterday until tomorrow (amount_sc_per_day * 3)
                for( $i = 0; $i <= $amount_sc_per_day * 3; $i++ ) {
                    $time_take_sc = gmmktime( $groups_and_urls['hour_of_day'] + $i * $groups_and_urls['interval_in_h'], 0, 0, gmdate( "m" ), gmdate( "d" ) - 1, gmdate( "Y" ) );

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
                                Auto Detection Settings<br>
                                <small>
                                    <?php
                                    $enabled = $groups_and_urls['enabled'];
                                    if($enabled) {
                                        ?>
                                        Auto Detection: <strong style="color: green;">Enabled</strong> |
                                        Interval: <strong>
                                            every
                                            <?= $groups_and_urls['interval_in_h'] ?>
                                            <?= $groups_and_urls['interval_in_h'] === 1 ? " hour" : " hours"?>
                                        </strong> |
                                        Notifications to:
                                        <strong>
                                            <?= ! empty($groups_and_urls['alert_emails']) ? implode(", ", $groups_and_urls['alert_emails']) : "no email address set" ?>
                                        </strong>
                                        <?php
                                    } else { ?>
                                        Auto Detection: <strong style="color: red">Disabled</strong>
                                    <?php } ?>
                                </small>
                            </h3>
                            <div class="mm_accordion_content padding">
                                <?php include 'templates/auto-settings.php'; ?>
                            </div>
                        </div>
                    </div>

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

            case 'webchangedetector-logs':
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
                echo '<table class="queue">';
                echo '<tr>
                        <th></th>
                        <th width="100%">Page & URL</th>
                        <th>Type</th>
                        <th>Status</th>
                        <th>Added</th>
                        <th>Last changed</th>
                        <th>Show</th>
                        </tr>';
                    if (! empty($queues) && is_iterable($queues)) {

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
                            echo '<td class="local-time" data-date="' . strtotime($queue['created_at']) . '">' .  gmdate('d/m/Y H:i:s', strtotime($queue['created_at'])) . '</td>';
                            echo '<td class="local-time" data-date="' . strtotime($queue['updated_at']) . '">' .  gmdate('d/m/Y H:i:s', strtotime($queue['updated_at'])) . '</td>';
                            echo '<td>';

                            // Show screenshot button
                            if(in_array($queue['sc_type'], ['pre', 'post', 'auto']) &&
                                $queue['status'] === 'done' &&
                                ! empty($queue['screenshots'][0]['link'])) { ?>

                                    <form method="post" action="?page=webchangedetector-show-screenshot">
                                        <button class="button" type="submit" name="img_url" value="<?= $queue['screenshots'][0]['link'] ?>">Show</button>
                                    </form>

                            <?php }
                            // Show comparison
                            elseif($queue['sc_type'] === 'compare' &&
                                $queue['status'] === 'done' &&
                                ! empty($queue['comparisons'][0]['token'])) { ?>

                                    <form method="post" action="?page=webchangedetector-show-detection">
                                        <button class="button" type="submit" name="token" value="<?= $queue['comparisons'][0]['token'] ?>">Show</button>
                                    </form>

                                <?php }

                            echo '</td>';
                            echo '</tr>';
                        }

                    } else {
                        echo 'Nothing to show yet.';
                    } ?>

                    </table>
                    <?php
                    $offset = $_GET['offset'] ?? 0;
                    $limit = $_GET['limit'] ?? $wcd::LIMIT_QUEUE_ROWS;
                    ?>
                        <a class="button <?= ! $offset ? 'disabled' : ''?>"
                            href="/wp-admin/admin.php?page=webchangedetector-logs&offset=<?= $offset - $limit ?>&limit=<?= $limit ?>"
                        > < Newer
                        </a>
                        <a class="button <?= count($queues) != $limit ? 'disabled' : ''?>"
                            href="/wp-admin/admin.php?page=webchangedetector-logs&offset=<?= $offset + $limit ?>&limit=<?= $limit ?>"
                        > Older >
                        </a>
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

            case 'webchangedetector-settings':
                ?>
                <div class="action-container">
                    <?php
                    if (! $api_token) {
                        echo '<div class="error notice">
                        <p>Please enter a valid API Token.</p>
                    </div>';
                    } elseif (! $website_details['enable_limits']) {
                        echo '<h2>Need more screenshots?</h2>';
                        echo '<p>If you need more screenshots, please upgrade your account with the button below.</p>';
                        echo '<a class="button" href="' . $wcd->app_url() . '/upgrade/?id=' . $account_details['whmcs_service_id'] . '">Upgrade</a>';
                    }
                    echo $wcd->get_api_token_form($api_token);
                    ?>
                </div>
                <div class="sidebar">
                    <div class="account-box">
                        <?php include 'templates/account.php'; ?>
                </div>

                </div>
                <div class="clear"></div>
                <?php
                break;

            /***************
             * Show compare
             ***************/
            case 'webchangedetector-show-detection':
                echo $wcd->get_comparison_by_token($_POST);
                break;

            /***************
             * Show screenshot
             ***************/
            case 'webchangedetector-show-screenshot':
                echo $wcd->get_screenshot($_POST);
                break;

            default:
                // Should already be validated by VALID_WCD_ACTIONS
            break;

        } // switch

        echo '</div>'; // closing from div webchangedetector
        echo '</div>'; // closing wrap
    } // wcd_webchangedetector_init
} // function_exists
