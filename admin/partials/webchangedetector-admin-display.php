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


function webchangedetector_init()
{
    $postdata = $_POST;
    $get = $_GET;

    $wcd = new WebChangeDetector_Admin();

    // Actions without API Token needed
    if (isset($postdata['wcd_action'])) {
        switch ($postdata['wcd_action']) {
            case 'create_free_account':
                $api_token = $wcd->create_free_account($postdata);

                // If we didn't get an API Token, put the error message out there and show the no-account-page
                if (isset($api_token['status']) && $api_token['status'] == 'error') {
                    echo '<div class="error notice">
                            <p>' . $api_token['reason'] . '</p>
                        </div>';
                    echo $wcd->get_no_account_page();
                    return;
                }
                break;

            case 'reset_api_token':
                $wcd->delete_website();
                delete_option('webchangedetector_api_token');
                break;

            case 'save_api_token':

                $website = $wcd->create_group();

                if (empty($website)) {
                    echo '<div class="error notice"><p>The API Token is invalid. Please try again.</p></div>';
                    return false;
                } else {
                    $wcd->sync_posts();
                    update_option('webchangedetector_api_token', $postdata['api_token']);
                }
                break;
        }
    }

    $api_token = get_option('webchangedetector_api_token');

    // The account doesn't have an api_token or activation_key
    if (! $api_token) {
        echo $wcd->get_no_account_page();
        return false;
    }

    $website_details = $wcd->get_website_details();

    // Call is giving back an array on purpose, in the plugin, there should be only one result
    if (count($website_details) > 0) {
        $website_details = $website_details[0];
    }

    $group_id = $website_details['manual_detection_group_id'] ?? null;
    $monitoring_group_id = $website_details['auto_detection_group_id'] ?? null;

    $monitoring_group_settings = null;

    if ($monitoring_group_id) {
        $wcd->get_monitoring_settings($monitoring_group_id);
    }

    // Perform actions
    if (isset($postdata['wcd_action'])) {
        switch ($postdata['wcd_action']) {
            case 'take_screenshots':
                $results = $wcd->take_screenshot($group_id, $postdata['sc_type']);

                if ($results[0] == 'error') {
                    echo '<div class="error notice"><p>' . $results[1] . '</p></div>';
                }

                if ($results[0] == 'success') {
                    echo '<div class="updated notice"><p>' . $results[1] . '</p></div>';
                }
                break;

            case 'update_monitoring_settings':
                $group = $wcd->update_monitoring_settings($postdata, $monitoring_group_id);
                break;

            case 'post_urls':
                // Get active posts from post data
                $active_posts = array();
                $count_selected = 0;
                foreach ($postdata as $key => $post_id) {
                    if (strpos($key, 'url_id') === 0) {
                        $active_posts[] = array(
                            'url_id' => $post_id,
                            'url' => get_permalink($postdata['post_id-'. $post_id]),
                            //'active' => 1,
                            'desktop' => $postdata['desktop-' . $post_id],
                            'mobile' => $postdata['mobile-' . $post_id]
                        );
                        if ($postdata['desktop-' . $post_id]) {
                            $count_selected++;
                        }

                        if ($postdata['mobile-' . $post_id]) {
                            $count_selected++;
                        }
                    }
                }

                // Check if there is a limit for selecting URLs
                if ($website_details['enable_limits'] &&
                    $website_details['url_limit_manual_detection'] < $count_selected &&
                    $website_details['manual_detection_group_id'] == $postdata['group_id']) {
                    echo '<div class="error notice"><p>The limit for selecting URLs is ' .
                        $website_details['url_limit_manual_detection'] . '.
                        You selected ' . $count_selected . ' URLs. The settings were not saved.</p></div>';
                } elseif ($website_details['enable_limits'] &&
                    isset($monitoring_group_settings) &&
                    $website_details['sc_limit'] < $count_selected * (24 / $monitoring_group_settings['interval_in_h']) * 30 &&
                    $website_details['auto_detection_group_id'] == $postdata['group_id']) {
                    echo '<div class="error notice"><p>The limit for auto change detection is ' .
                        $website_details['sc_limit'] . '. per month.
                            You selected ' . $count_selected * (24 / $monitoring_group_settings['interval_in_h']) * 30 . ' change detections. The settings were not saved.</p></div>';
                } else {
                    // Update API URLs
                    $result = $wcd->update_urls($postdata['group_id'], $active_posts);
                    echo '<div class="updated notice"><p>Settings saved.</p></div>';
                }
                break;
        }
    }

    // Start view
    echo '<div class="wrap">';
    echo '<div class="webchangedetector">';
    echo '<h1>Web Change Detector</h1>';

    $wcd->mm_tabs();

    echo '<div style="margin-top: 30px;"></div>';
    if (isset($get['tab'])) {
        $tab = $get['tab'];
    } else {
        $tab = 'take-screenshots';
    }

    $client_details = $wcd->get_account_details($api_token);

    $comp_usage = $client_details['usage'];
    if ($client_details['plan']['one_time']) {
        if (strtotime('+1 month', strtotime($client_details['subscription_started_at'])) > date('U')) {
            $limit = $client_details['sc_limit'];
        } else {
            $limit = 0;
        }
    } else {
        $limit = $client_details['plan']['sc_limit'];
    }

    $available_compares = $limit - (int) $comp_usage;

    $restrictions = $wcd->mm_get_restrictions();

    switch ($tab) {

        /********************
         * Take Screenshot
         ********************/

        case 'take-screenshots':

            if ($restrictions['enable_limits'] && ! $restrictions['allow_manual_detection']) {
                echo 'Settings for Update Change detections are disabled by your API Token.';
                break;
            }

            // Get amount selected Screenshots
            $groups_and_urls = $wcd->get_urls_of_group($group_id);

            ?>
            <h2>Select URLs</h2>
            <div class="accordion">
                <div class="mm_accordion_title">
                    <h3>
                        Update Change Detection URLs<br>
                        <small>Currently selected:
                            <strong><?= $groups_and_urls['amount_selected_urls'] ?><?= $website_details['enable_limits'] ? ' / ' . $website_details['url_limit_manual_detection'] : '' ?> </strong>
                            URLs</small>
                    </h3>
                    <div class="mm_accordion_content">
                        <?php $wcd->mm_get_url_settings($groups_and_urls) ?>
                    </div>
                </div>
            </div>
            <?php
            if (! $website_details['enable_limits']) {
                echo '<h2>Do the magic</h2>';
                echo '<p>
                Your available balance is ' . $available_compares . ' / ' . $limit . '<br>
            <strong>Currently selected amount of change detections: ' . $groups_and_urls['amount_sc'] . '</strong></p>';

                echo '<form action="' . admin_url() . '/admin.php?page=webchangedetector&tab=take-screenshots" method="post" style="float:left; margin-right: 10px;">';
                echo '<input type="hidden" value="take_screenshots" name="wcd_action">';
                echo '<input type="hidden" name="sc_type" value="pre">';
                echo '<input type="submit" value="Pre Update Change Detection" class="button">';
                echo '</form>';

                echo '<form action="' . admin_url() . '/admin.php?page=webchangedetector&tab=take-screenshots" method="post" style="float:left;">';
                echo '<input type="hidden" value="take_screenshots" name="wcd_action">';
                echo '<input type="hidden" name="sc_type" value="post">';
                echo '<input type="submit" value="Post Update Change Detection" class="button">';
                echo '</form>';

                echo '<div class="clear"></div>';
            }
            echo '<hr>';

            // Compare overview
            echo '<h2>Latest Change Detections</h2>';

            if (isset($postdata['limit_days'])) {
                $limit_days = $postdata['limit_days'];
            } else {
                $limit_days = 7;
            }
            $compares = $wcd->get_compares($group_id, $limit_days);
            ?>
            <form method="post">
                <select name="limit_days">
                    <option value="7" <?= $limit_days == 7 ? 'selected' : '' ?>>Last 7 days</option>
                    <option value="14" <?= $limit_days == 14 ? 'selected' : '' ?>>Last 14 days</option>
                    <option value="30"<?= $limit_days == 30 ? 'selected' : '' ?>>Last 30 days</option>
                    <option value="60"<?= $limit_days == 60 ? 'selected' : '' ?>>Last 60 days</option>
                </select>
                <input class="button" type="submit" value="Filter">
            </form>
            <?php

            echo '<table><tr><th>URL</th><th>Compared Screenshots</th><th>Difference</th><th>Compare Link</th></tr>';
            $change_detection_added = false;
            foreach ($compares as $key => $compare) {
                if (! $compare['difference_percent']) {
                    continue;
                }

                echo '<tr>';
                echo '<td>' . $wcd->mm_get_device_icon($compare['device']) . $compare['url'] . '</td>';
                echo '<td>' . date('d/m/Y H:i', $compare['image1_timestamp']) . '<br>' . date('d/m/Y H:i', $compare['image2_timestamp']) . '</td>';
                if ($compare['difference_percent']) {
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
            if (! $change_detection_added) {
                echo 'There are no change detections to show yet...';
            }

            break;

        /************************
         * Monitoring Screenshots
         * **********************/

        case 'monitoring-screenshots':
            if ($restrictions['enable_limits'] && ! $restrictions['allow_auto_detection']) {
                echo 'Settings for Update Change detections are disabled by your API Token.';
                break;
            }

            $groups_and_urls = $wcd->get_urls_of_group($monitoring_group_id);

            ?>
            <h2>Select URLs</h2>
            <div class="accordion">
                <div class="mm_accordion_title">
                    <h3>
                        Auto Change Detection URLs<br>
                        <small>Currently selected: <strong><?= $groups_and_urls['amount_selected_urls'] ?></strong> URLs</small>
                    </h3>
                    <div class="mm_accordion_content">
                        <?php $wcd->mm_get_url_settings($groups_and_urls, true); ?>
                    </div>
                </div>
            </div>

            <h2>Settings for Auto Change Detection</h2>
            <p>
                The current settings require
                <strong><?php
                if(! empty($groups_and_urls['interval_in_h'])) {
                    echo $groups_and_urls['amount_selected_urls'] * (24 / $groups_and_urls['interval_in_h']) * 30;
                }
                ?></strong>
                change detections per month.<br>
                Your available change detections are <strong>
                    <?php
                    if ($website_details['enable_limits']) {
                        echo $website_details['sc_limit'] . ' / month';
                    } else {
                        echo $available_compares . ' / ' . $limit;
                    }
                    ?>
                </strong>.
            </p>

            <form action="<?= admin_url() ?>/admin.php?page=webchangedetector&tab=monitoring-screenshots" method="post" onsubmit="return mmValidateForm()">
            <p>
                <input type="hidden" name="wcd_action" value="update_monitoring_settings">
                <input type="hidden" name="monitoring" value="1">
                <input type="hidden" name="group_name" value="<?= $groups_and_urls['name'] ?>">

            <label for="enabled">Enabled</label>
            <select name="enabled">
                <option value="1" <?= isset($groups_and_urls['enabled']) && $groups_and_urls['enabled'] == '1' ? 'selected' : ''; ?>>
                    Yes
                </option>
                <option value="0" <?= isset($groups_and_urls['enabled']) && $groups_and_urls['enabled'] == '0' ? 'selected' : ''; ?>>
                    No
                </option>
            </select>
            </p>
            <p>
                <label for="hour_of_day">Hour of the day</label>
                <select name="hour_of_day">
                    <?php
                    for ($i = 0; $i < 24; $i++) {
                        if (isset($groups_and_urls['hour_of_day']) && $groups_and_urls['hour_of_day'] == $i) {
                            $selected = 'selected';
                        } else {
                            $selected = '';
                        }
                        echo '<option value="' . $i . '" ' . $selected . '>' . $i . ':00</option>';
                    }
                    ?>
                </select>
            </p>
            <p>
                <label for="interval_in_h">Interval in hours</label>
                <select name="interval_in_h">
                    <option value="1" <?= isset($groups_and_urls['interval_in_h']) && $groups_and_urls['interval_in_h'] == '1' ? 'selected' : ''; ?>>
                        Every 1 hour (720 Change Detections / URL / month)
                    </option>
                    <option value="3" <?= isset($groups_and_urls['interval_in_h']) && $groups_and_urls['interval_in_h'] == '3' ? 'selected' : ''; ?>>
                        Every 3 hours (240 Change Detections / URL / month)
                    </option>
                    <option value="6" <?= isset($groups_and_urls['interval_in_h']) && $groups_and_urls['interval_in_h'] == '6' ? 'selected' : ''; ?>>
                        Every 6 hours (120 Change Detections / URL / month)
                    </option>
                    <option value="12" <?= isset($groups_and_urls['interval_in_h']) && $groups_and_urls['interval_in_h'] == '12' ? 'selected' : ''; ?>>
                        Every 12 hours (60 Change Detections / URL / month)
                    </option>
                    <option value="24" <?= isset($groups_and_urls['interval_in_h']) && $groups_and_urls['interval_in_h'] == '24' ? 'selected' : ''; ?>>
                        Every 24 hours (30 Change Detections / URL / month)
                    </option>
                </select>
            </p>
            <p>
                <label for="alert_emails">Email addresses for alerts</label>
                <input type="text" name="alert_emails" id="alert_emails" style="width: 500px;"
                       value="<?= isset($groups_and_urls['alert_emails']) ? implode(',', $groups_and_urls['alert_emails']) : '' ?>">
                       <!--pattern="[a-z0-9._%+-]+@[a-z0-9.-]+\.[a-z]{2,}$"
                       oninvalid="this.setCustomValidity('Invalid email address.')"
                       onchange="try{setCustomValidity('')}catch(e){}"
                       oninput="setCustomValidity(' ')"-->
            </p>
                <input type="submit" class="button" value="Save" >
            </form>

            <?php

            // Compare overview
            echo '<h2>Latest Change Detections</h2>';

            if (isset($postdata['limit_days'])) {
                $limit_days = $postdata['limit_days'];
            } else {
                $limit_days = 7;
            }
            $compares = $wcd->get_compares($monitoring_group_id, $limit_days);
            ?>
            <form method="post">
                <select name="limit_days">
                    <option value="7" <?= $limit_days == 7 ? 'selected' : '' ?>>Last 7 days</option>
                    <option value="14" <?= $limit_days == 14 ? 'selected' : '' ?>>Last 14 days</option>
                    <option value="30"<?= $limit_days == 30 ? 'selected' : '' ?>>Last 30 days</option>
                    <option value="60"<?= $limit_days == 60 ? 'selected' : '' ?>>Last 60 days</option>
                </select>
                <input class="button" type="submit" value="Filter">
            </form>
            <?php

            echo '<table><tr><th>URL</th><th>Compared Screenshots</th><th>Difference</th><th>Compare Link</th></tr>';
            $change_detection_added = false;

            foreach ($compares as $key => $compare) {
                if (! $compare['difference_percent']) {
                    continue;
                }

                echo '<tr>';
                echo '<td>' . $wcd->mm_get_device_icon($compare['device']) . $compare['url'] . '</td>';
                echo '<td>' . date('d/m/Y H:i', $compare['image1_timestamp']) . '<br>' . date('d/m/Y H:i', $compare['image2_timestamp']) . '</td>';
                if ($compare['difference_percent']) {
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
            if (! $change_detection_added) {
                echo 'There are no change detections to show yet...';
            }

            break;

        /********************
         * Queue
         ********************/

        case 'queue':
            // Show queued urls
            $queues = $wcd->get_queue();
            $type_nice_name = [
                'pre' => 'Reference Screenshot',
                'post' => 'Compare Screenshot',
                'auto' => 'Auto Detection',
                'compare' => 'Change Detection'
            ];
            if (! empty($queues) && is_iterable($queues)) {
                echo '<table class="queue">';
                echo '<tr><th></th><th width="100%">Page & URL</th><th>Type</th><th>Status</th><th>Added</th><th>Last changed</th></tr>';
                foreach ($queues as $queue) {
                    $group_type = $queue['monitoring'] ? 'Auto Change Detection' : 'Update Change Detection';
                    echo '<tr class="queue-status ' . $queue['status'] . '">';
                    echo '<td>' . $wcd->mm_get_device_icon($queue['device']) . '</td>';
                    echo '<td>
                                <span class="html-title queue"> ' . $queue['url']['html_title'] . '</span><br>
                                <span class="url queue">URL: '.$queue['url']['url'] . '</span><br>
                                ' . $group_type . '
                          </td>';


                    echo '<td>' . $type_nice_name[$queue['sc_type']] . '</td>';
                    echo '<td>' . ucfirst($queue['status']) . '</td>';
                    echo '<td>' .  date('d/m/Y H:i:s', strtotime($queue['created_at'])) . '</td>';
                    echo '<td>' .  date('d/m/Y H:i:s', strtotime($queue['updated_at'])) . '</td>';
                    echo '</tr>';
                }
                echo '</table>';
            } else {
                echo 'All done';
            }
            break;
        /********************
         * Settings
         ********************/

        case 'settings':

            if (! $api_token) {
                echo '<div class="error notice">
                <p>Please enter a valid API Token.</p>
            </div>';
            } elseif (! $website_details['enable_limits']) {
                echo '<h2>Your credits</h2>';
                echo 'Your current plan: <strong>' . $client_details['plan']['name'] . '</strong><br>';

                $subscription_started_at = strtotime($client_details['subscription_started_at']);

                // Calculate end of one-time plans
                if ($client_details['plan']['one_time']) {
                    $end_of_trial = strtotime('+1 month ', $subscription_started_at);
                    echo 'Your change detections are valid until <strong>' . date('d/m/Y', $end_of_trial) . '</strong>.<br>Please upgrade your account to renew your balance afterwards.';
                } else {
                    // Calculate next renew date
                    $renew_current_month = mktime(0, 0, 0, date('m'), date('d', $subscription_started_at), date('Y'));
                    $today = date('U');

                    if ($today > $renew_current_month) {
                        $renew_date = strtotime('+1 month', $renew_current_month);
                    } else {
                        $renew_date = $renew_current_month;
                    }

                    echo 'Next renew: ' . date('d/m/Y', $renew_date);
                }
                echo '<p>Change detections in this period: ' . $limit . '<br>';
                echo 'Used change detections: ' . $comp_usage . '<br>';
                echo 'Available change detections in this period: ' . $available_compares . '</p>';

                echo $wcd->get_upgrade_options($client_details['plan_id']);
            }
            echo $wcd->get_api_token_form($api_token);
            break;

        /*******************
         * Help
         *******************/
        case 'help':

            echo '<h2>How it works:</h2>';
            echo '<p>
                <strong>Update Change Detection</strong><br>
                Here you can select the pages of your website and manually take the screenshots.
                Use the Update Change Detection when you perform updates on your website. Run a Pre Update Change Detection
                before and a Post Update Change Detection after the update and you will see if there are differences on the selected pages.
                The Post Update Change Detection automatically compares the screenshots with the latest Pre Change Detection Screenshots.

                <ol>
                    <li>Select the urls and the devices (desktop and / or mobile) you want to take a screenshot.</li>
                    <li>Hit the Button "Pre Update Change Detection". The Screenshots might take couple of minutes.
                        You can see the current status in the tab "Queue".</li>
                    <li>After updating your website, press the button "Post Update Change Detection. </li>
                    <li>When the Update Change Detections are finished, you can see the results below the settings at "Latest Change Detections"</li>
                </ol>
                </p>
                <p>
                <strong>Auto Change Detection</strong><br>
                Use the Auto Change Detection to automatically do a change detection in a specific interval.
                When there are differences in a change detection, you will automatically receive an alert email.
                <ol>
                    <li>Select the urls you want to auto detect.</li>
                    <li>Select the interval and the hour of day for the first screenshot to be taken. Please be aware
                     that change detections will be only performed when you have enough credit available.</li>
                    <li>You find all auto detections below the settings</li>
                </ol>
                At the Tab "Settings" you have an overview of your usage and limits. You can also up- or downgrade your package.
                </p>';
            break;

        case 'show-compare':
            echo '<h1>The Change Detection Images</h1>';
            if (defined('WCD_DEV_API') && WCD_DEV_API) {
                $wcd_domain = 'https://www.dev.webchangedetector.com';
            } else {
                $wcd_domain = 'https://www.webchangedetector.com';
            }

            $public_link = $wcd_domain . '/change-detection/?action=show_change_detection&token=' . urlencode($_GET['token']);
            echo '<p>Public link: <a href="' . $public_link . '"target="_blank">' . $public_link . '</a></p>';

            $back_button = '<a href="' . $_SERVER['HTTP_REFERER'] . '" class="button" style="margin: 10px 0;">Back</a><br>';
            echo $back_button;
            echo  $wcd->mm_show_change_detection($_GET['token']);
            echo '<div class="clear"></div>';
            echo $back_button;

    }
    echo '</div>'; // closing from div webchangedetector
    echo '</div>'; // closing wrap
}
