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


function webchangedetector_init() {
    $postdata = $_POST;
    $get = $_GET;

    $wcd = new WebChangeDetector_Admin();

    // Actions without API key needed
    if (isset($postdata['wcd_action'])) {
        switch ($postdata['wcd_action']) {
            case 'create_free_account':
                $api_key = $wcd->create_free_account($postdata);

                // If we didn't get an api key, put the error message out there and show the no-account-page
                if (isset($api_key['status']) && $api_key['status'] == 'error') {
                    echo '<div class="error notice">
                            <p>' . $api_key['reason'] . '</p>
                        </div>';
                    echo $wcd->get_no_account_page();
                    return;
                }
                break;

            case 'reset_api_key':
                $api_key = get_option('webchangedetector_api_key');
                $wcd->delete_website($api_key);
                delete_option('webchangedetector_api_key');
                break;

            case 'save_api_key':
                update_option('webchangedetector_api_key', $postdata['api-key']);
                $website = $wcd->create_group($postdata['api-key']);
                $wcd->sync_posts($website['auto_detection_group_id'], $website['manual_detection_group_id']);
                break;
        }
    }

    // Check for the account
    $account_keys = $wcd->verify_account();

    // The account doesn't have an api_key or activation_key
    if (!$account_keys) {
        echo $wcd->get_no_account_page();
        return;
    }

    // The account is not activated yet, but the api_key is there already
    if (isset($account_keys['api_key']) && isset($account_keys['activation_key'])) {

        if (isset($postdata['wcd_action']) && $postdata['wcd_action'] == 'resend_confirmation_mail') {
            $wcd->resend_confirmation_mail($account_keys['api_key']);
            echo '<div class="updated notice">
                <p>Email sent successfully to your email address ' . $account_keys['email'] . '.</p>
            </div>';

        }

        echo '<div class="error notice">
                <p>Please <strong>activate</strong> your account by clicking the confirmation link in the email we sent you.</p>
            </div>
            <p>You didn\'t receive the email? Please also check your spam folder. To send the email again, please click the button below</p>
            <form action="/wp-admin/admin.php?page=webchangedetector&tab=take-screenshots" method="post">
                <input type="hidden" name="wcd_action" value="resend_confirmation_mail">
                <input type="submit" value="Send confirmation mail again" class="button">
            </form>';
        return;
    }

    // Set the api_key
    if (isset($account_keys['api_key']))
        $api_key = $account_keys['api_key'];
    else
        $api_key = false;

    $website_details = $wcd->get_website_details($api_key);

    // Create website and groups if not exists yet
    if (!$website_details) {
        $website_details = $wcd->create_group($api_key);
        $wcd->sync_posts($website_details['auto_detection_group_id'], $website_details['manual_detection_group_id']);
    }

    $group_id = $website_details['manual_detection_group_id'];
    $monitoring_group_id = $website_details['auto_detection_group_id'];

    $monitoring_group_settings = $wcd->get_monitoring_settings($monitoring_group_id);

    // Perform actions
    if (isset($postdata['wcd_action'])) {
        switch ($postdata['wcd_action']) {
            case 'take_screenshots':
                $results = $wcd->take_screenshot($group_id, $api_key);

                if ( $results[0] == 'error' )
                    echo '<div class="error notice"><p>' . $results[1] . '</p></div>';

                if ( $results[0] == 'success' )
                    echo '<div class="updated notice"><p>' . $results[1] . '</p></div>';
                break;

            case 'update_monitoring_settings':
                $wcd->update_monitoring_settings( $postdata, $monitoring_group_id );
                break;

            case 'post_urls':
                // Get active posts from post data
                $active_posts = array();
                $count_selected = 0;
                foreach ($postdata as $key => $post_id) {
                    if (strpos($key, 'sc_id') === 0) {
                        $active_posts[] = array(
                            'sc_id' => $post_id,
                            'url' => get_permalink($post_id),
                            'active' => 1,
                            'desktop' => $postdata['desktop-' . $post_id],
                            'mobile' => $postdata['mobile-' . $post_id]
                        );
                        if ($postdata['desktop-' . $post_id])
                            $count_selected++;

                        if ($postdata['mobile-' . $post_id])
                            $count_selected++;
                    }
                }

                // Check if there is a limit for selecting URLs
                if ($website_details['enable_limits'] &&
                    $website_details['url_limit_manual_detection'] < $count_selected &&
                    $website_details['manual_detection_group_id'] == $postdata['group_id']) {
                    echo '<div class="error notice"><p>The limit for selecting URLs is ' .
                        $website_details['url_limit_manual_detection'] . '. 
                        You selected ' . $count_selected . ' URLs. The settings were not saved.</p></div>';

                } else if ($website_details['enable_limits'] &&
                    $website_details['sc_limit'] < $count_selected * (24 / $monitoring_group_settings['interval_in_h']) * 30 &&
                    $website_details['auto_detection_group_id'] == $postdata['group_id']) {

                    echo '<div class="error notice"><p>The limit for auto change detection is ' .
                        $website_details['sc_limit'] . '. per month. 
                            You selected ' . $count_selected * (24 / $monitoring_group_settings['interval_in_h']) * 30 . ' change detections. The settings were not saved.</p></div>';
                } else {
                    // Update API URLs
                    $wcd->update_urls($postdata['group_id'], $active_posts);
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
    if (isset($get['tab']))
        $tab = $get['tab'];
    else
        $tab = 'take-screenshots';

    $client_details = $wcd->get_account_details($api_key);
    $client_details = $client_details[0];

    $comp_usage = $client_details['usage'];
    if ($client_details['one_time']) {
        if (strtotime("+1 month", strtotime($client_details['start_date'])) > date("U"))
            $limit = $client_details['comp_limit'];
        else
            $limit = 0;
    } else
        $limit = $client_details['comp_limit'];

    $available_compares = $limit - (int)$comp_usage;

    /*$queue = $wcd->get_queue( $group_id );

    if (!empty($queue)) {
        echo '<div class="mm_processing_container">';
        echo '<h2>Currently Processing</h2>';
        echo '<table><tr><th>URL</th><th>Device</th><th>Status</th></tr>';

        foreach ($queue as $url) {
            echo '<tr><td>' . $url['url'] . '</td><td>' . ucfirst($url['device']) . '</td><td>Processing...</td></tr>';
        }

        echo '</table>';
        echo '</div>';
        echo '<hr>';
    }*/

    $restrictions = $wcd->mm_get_restrictions();

    switch ($tab) {

        /********************
         * Take Screenshot
         ********************/

        case 'take-screenshots':

            if ($restrictions['enable_limits'] && !$restrictions['allow_manual_detection']) {
                echo 'Settings for Update Change detections are disabled by your API Key.';
                break;
            }
            // Get amount selected Screenshots
            $amount_sc = $wcd->get_amount_sc( $group_id );

            // Because of change in API we need to check this
            if( is_array( $amount_sc ) )
                $amount_sc = $amount_sc['selected'];

            if (!$amount_sc)
                $amount_sc = '0';

            echo '<h2>Select URLs</h2>';
            ?>
            <div class="accordion">
                <div class="mm_accordion_title">
                    <h3>
                        Update Change Detection URLs<br>
                        <small>Currently selected:
                            <strong><?= $amount_sc ?><?= $website_details['enable_limits'] ? " / " . $website_details['url_limit_manual_detection'] : '' ?> </strong>
                            URLs</small>
                    </h3>
                    <div class="mm_accordion_content">
                        <?php $wcd->mm_get_url_settings($group_id) ?>
                    </div>
                </div>
            </div>
            <?php
            if (!$website_details['enable_limits']) {
                echo '<h2>Do the magic</h2>';
                echo '<p>
                Your available balance is ' . $available_compares . ' / ' . $limit . '<br>
            <strong>Currently selected amount of change detections: ' . $amount_sc . '</strong></p>';

                echo '<form action="/wp-admin/admin.php?page=webchangedetector&tab=take-screenshots" method="post">';
                echo '<input type="hidden" value="take_screenshots" name="wcd_action">';
                echo '<input type="submit" value="Start Update Change Detection" class="button">';
                echo '</form>';
            }
            echo '<hr>';

            // Compare overview
            echo '<h2>Latest Change Detections</h2>';

            $compares = $wcd->get_compares( $group_id );

            if (count($compares) == 0)
                echo "There are no compares to show yet...";
            else {
                echo '<table><tr><th>URL</th><th>Device</th><th>Compared Screenshots</th><th>Difference</th><th>Compare Link</th></tr>';
                foreach ($compares as $key => $compare) {
                    echo '<tr>';
                    echo '<td>' . $compare['url'] . '</td>';
                    echo '<td>' . ucfirst($compare['device']) . '</td>';
                    echo '<td>' . date("d/m/Y H:i", $compare['image1_timestamp']) . '<br>' . date("d/m/Y H:i", $compare['image2_timestamp']) . '</td>';
                    if ($compare['difference_percent'])
                        $class = 'is-difference';
                    else
                        $class = 'no-difference';
                    echo '<td class="' . $class . '">' . $compare['difference_percent'] . ' %</td>';
                    //echo '<td><a href="' . $compare['link'] . '" target="_blank">Show compare image</a></td>';
                    echo '<td><form action="/wp-admin/admin.php?page=webchangedetector&tab=show-compare" method="post">';
                    echo '<input type="hidden" name="wcd_action" value="show_compare">';
                    echo '<input type="hidden" name="compare_id" value="' . $compare['ID'] . '">';
                    echo '<input class="button" type="submit" value="Show Compare">';
                    echo '</form></td>';
                    echo '</tr>';
                }
                echo '</table>';
            }
            break;

        /************************
         * Monitoring Screenshots
         * **********************/

        case 'monitoring-screenshots':
            if ($restrictions['enable_limits'] && !$restrictions['allow_auto_detection']) {
                echo 'Settings for Update Change detections are disabled by your API Key.';
                break;
            }

            //Amount selected Monitoring Screenshots
            $amount_sc_monitoring = $wcd->get_amount_sc( $monitoring_group_id );

            // Because of change in API we need to check this
            if( is_array( $amount_sc_monitoring ) )
                $amount_sc_monitoring = $amount_sc_monitoring['selected'];

            if ( !$amount_sc_monitoring )
                $amount_sc_monitoring = '0';

            $group_settings = $wcd->get_monitoring_settings($monitoring_group_id);

            echo '<h2>Select URLs</h2>';

            ?>
            <div class="accordion">
                <div class="mm_accordion_title">
                    <h3>
                        Auto Change Detection URLs<br>
                        <small>Currently selected: <strong><?= $amount_sc_monitoring ?></strong> URLs</small>
                    </h3>
                    <div class="mm_accordion_content">
                        <?php $wcd->mm_get_url_settings($monitoring_group_id, true) ?>
                    </div>
                </div>
            </div>

            <h2>Settings for Auto Change Detection</h2>
            <p>
                The current settings require
                <strong><?= $amount_sc_monitoring * (24 / $group_settings['interval_in_h']) * 30 ?></strong> change
                detections per month.<br>
                Your available change detections are <strong>
                    <?php
                    if ($website_details['enable_limits'])
                        echo $website_details['sc_limit'] . " / month";
                    else
                        echo $available_compares . ' / ' . $limit;
                    ?>
                </strong>.
            </p>

            <form action="/wp-admin/admin.php?page=webchangedetector&tab=monitoring-screenshots" method="post">
            <p>
                <input type="hidden" name="wcd_action" value="update_monitoring_settings">
                <input type="hidden" name="monitoring" value="1">
                <input type="hidden" name="group_name" value="<?= $group_settings['group_name'] ?>">

            <label for="enabled">Enabled</label>
            <select name="enabled">
                <option value="1" <?= isset($group_settings['enabled']) && $group_settings['enabled'] == '1' ? 'selected' : ''; ?>>
                    Yes
                </option>
                <option value="0" <?= isset($group_settings['enabled']) && $group_settings['enabled'] == '0' ? 'selected' : ''; ?>>
                    No
                </option>
            </select>
            </p>
            <p>
                <label for="hour_of_day">Hour of the day</label>
                <select name="hour_of_day">
                    <?php
                    for ($i = 0; $i < 24; $i++) {
                        if (isset($group_settings['hour_of_day']) && $group_settings['hour_of_day'] == $i)
                            $selected = 'selected';
                        else
                            $selected = '';
                        echo '<option value="' . $i . '" ' . $selected . '>' . $i . ':00</option>';
                    }
                    ?>
                </select>
            </p>
            <p>
                <label for="interval_in_h">Interval in hours</label>
                <select name="interval_in_h">
                    <option value="1" <?= isset($group_settings['interval_in_h']) && $group_settings['interval_in_h'] == '1' ? 'selected' : ''; ?>>
                        Every 1 hour (720 Change Detections / URL / month)
                    </option>
                    <option value="3" <?= isset($group_settings['interval_in_h']) && $group_settings['interval_in_h'] == '3' ? 'selected' : ''; ?>>
                        Every 3 hours (240 Change Detections / URL / month)
                    </option>
                    <option value="6" <?= isset($group_settings['interval_in_h']) && $group_settings['interval_in_h'] == '6' ? 'selected' : ''; ?>>
                        Every 6 hours (120 Change Detections / URL / month)
                    </option>
                    <option value="12" <?= isset($group_settings['interval_in_h']) && $group_settings['interval_in_h'] == '12' ? 'selected' : ''; ?>>
                        Every 12 hours (60 Change Detections / URL / month)
                    </option>
                    <option value="24" <?= isset($group_settings['interval_in_h']) && $group_settings['interval_in_h'] == '24' ? 'selected' : ''; ?>>
                        Every 24 hours (30 Change Detections / URL / month)
                    </option>
                </select>
            </p>
            <p>
                <label for="alert_email">Email address for alerts</label>
                <input type="text" name="alert_email"
                       value="<?= isset($group_settings['alert_email']) ? $group_settings['alert_email'] : '' ?> ">
            </p>
            <input class="button" type="submit" value="Save">
            </form>

            <?php
            // Compare overview
            echo '<h2>Latest Change Detections</h2>';
            $compares = $wcd->get_compares( $monitoring_group_id );

            if (count($compares) == 0)
                echo "There are no compares to show yet...";
            else {
                echo '<table><tr><th>URL</th><th>Device</th><th>Compared Screenshots</th><th>Difference</th><th>Compare Link</th></tr>';
                foreach ($compares as $key => $compare) {
                    echo '<tr>';
                    echo '<td>' . $compare['url'] . '</td>';
                    echo '<td>' . $compare['device'] . '</td>';
                    echo '<td>' . date("d/m/Y H:i", $compare['image1_timestamp']) . '<br>' . date("d/m/Y H:i", $compare['image2_timestamp']) . '</td>';
                    if ($compare['difference_percent'])
                        $class = 'is-difference';
                    else
                        $class = 'no-difference';
                    echo '<td class="' . $class . '">' . $compare['difference_percent'] . ' %</td>';
                    echo '<td><form action="/wp-admin/admin.php?page=webchangedetector&tab=show-compare" method="post">';
                    echo '<input type="hidden" name="wcd_action" value="show_compare">';
                    echo '<input type="hidden" name="compare_id" value="' . $compare['ID'] . '">';
                    echo '<input class="button" type="submit" value="Show Compare">';
                    echo '</form></td>';
                    echo '</tr>';
                }
                echo '</table>';
            }
            break;

        /********************
         * Queue
         ********************/

        case 'queue':
            // Show queued urls
            $args = array(
                'action'	=> 'get_queue',
                'status'    => json_encode( array( 'open', 'processing', 'done' ) )
            );
            $queue = $wcd->mm_api( $args );
            if( $queue ) {
                echo '<h2>Currently processing and already processed URLs</h2>';
                echo '<table>';
                echo '<tr><th>URL</th><th>Status</th><th>Added to queue</th><th>Last changed</th></tr>';
                foreach( $queue as $url ) {
                    switch( $url['status'] ) {
                        case 'done':
                            $background = '#eee';
                            break;

                        case 'processing':
                            $background = 'rgba( 254, 204, 48, 0.3)';
                            break;

                        case 'open':
                            $background = 'rgba(23, 179, 49, 0.3);';
                            break;
                    }
                    echo '<tr style="background: ' . $background . ';">';
                    echo '<td style="border-bottom: 1px solid #cecece;">' . $wcd->mm_get_device_icon( $url['device'] ) . $url['url'] . '</td>';
                    echo '<td style="border-bottom: 1px solid #cecece;">' . ucfirst($url['status'] ) . '</td>';
                    echo '<td style="border-bottom: 1px solid #cecece;">' .  date("d/m/Y H:i:s", strtotime($url['timestamp_added'] ) ) . '</td>';
                    echo '<td style="border-bottom: 1px solid #cecece;">' .  date("d/m/Y H:i:s", strtotime($url['timestamp_last_change'] ) ) . '</td>';
                    echo '</tr>';
                }
                echo '</table>';

            } else
                echo 'All done';
            break;
        /********************
         * Settings
         ********************/

        case 'settings':

            if (!$api_key) {
                echo '<div class="error notice">
                <p>Please enter a valid API Key.</p>
            </div>';
            } else if (!$website_details['enable_limits']) {

                echo '<h2>Your credits</h2>';
                echo 'Your current plan: <strong>' . $client_details['plan_name'] . '</strong><br>';

                $start_date = strtotime($client_details['start_date']);

                // Calculate end of one-time plans
                if ($client_details['one_time']) {

                    $end_of_trial = strtotime("+1 month ", $start_date);
                    echo 'Your change detections are valid until <strong>' . date("d/m/Y", $end_of_trial) . '</strong>.<br>Please upgrade your account to renew your balance afterwards.';

                } else {
                    // Calculate next renew date
                    $renew_current_month = mktime(0, 0, 0, date("m"), date("d", $start_date), date("Y"));
                    $today = date("U");

                    if ($today > $renew_current_month)
                        $renew_date = strtotime("+1 month", $renew_current_month);
                    else
                        $renew_date = $renew_current_month;

                    echo 'Next renew: ' . date("d/m/Y", $renew_date);

                }
                echo '<p>Change detections in this period: ' . $limit . '<br>';
                echo 'Used change detections: ' . $comp_usage . '<br>';
                echo 'Available change detections in this period: ' . $available_compares . '</p>';

                echo $wcd->get_upgrade_options($client_details['plan_id'] );
            }
            echo $wcd->get_api_key_form($api_key);
            break;

        /*******************
         * Help
         *******************/
        case 'help':

            echo '<h2>How it works:</h2>';
            echo '<p>
                <strong>Update Change Detection</strong><br>
                Here you can select the pages of your website and manually take the screenshots.
                Use the Update Change Detection when you perform updates on your website. Run a change detection
                before and after the update and you will see if there are differences on the selected pages.
                <ol>
                    <li>Select the urls and the devices (desktop and / or mobile) you want to take a screenshot.</li>
                    <li>Hit the Button "Start Update Detection". The Detection might take couple of minutes. </li>
                    <li>When the update detections are finished, you can see the results below the settings at "Latest Change Detections"</li>
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
            echo $wcd->show_compare( $_POST['compare_id'] );

    }
    echo '</div>'; // closing from div webchangedetector
    echo '</div>'; // closing wrap
}



