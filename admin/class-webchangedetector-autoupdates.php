<?php

/**
Title: WebChange Detector Auto Update Feature
Description: Check your website on auto updates visually and see what changed.
Version: 1.0
 *
 * @package    WebChangeDetector
 */

namespace WebChangeDetector;

new WebChangeDetector_Autoupdates();

/**
 * Checks on wp auto updates
 *
 * @package    WebChangeDetector
 */
class WebChangeDetector_Autoupdates
{

    /** Wp auto update lock name.
     *
     * @var string
     */
    private string $lock_name = 'auto_updater.lock';

    /** Group ID for manual checks.
     *
     * @var string
     */
    public string $manual_group_id;

    /** Group ID for monitoring checks.
     *
     * @var string
     */
    public string $monitoring_group_id;

    /**
     * Plugin constructor.
     */
    public function __construct()
    {

        $this->set_defines();

        // Register the complete hook in constructor to ensure it's always registered.
        add_action('automatic_updates_complete', array($this, 'automatic_updates_complete'), 10, 1);

        // Post updates.
        add_action('wcd_cron_check_post_queues', array($this, 'wcd_cron_check_post_queues'), 10, 2);

        // Saving settings.
        add_action('wcd_save_update_group_settings', array($this, 'wcd_save_update_group_settings'));

        // Backup cron job for checking for updates.
        add_action('wcd_wp_version_check', array($this, 'wcd_wp_version_check'));

        // Hooking into the update process.
        add_action('wp_maybe_auto_update', array($this, 'wp_maybe_auto_update'), 5);

        // Add webhook endpoint for triggering cron jobs.
        add_action('init', array($this, 'handle_webhook_trigger'), 5);

        $wcd_groups = get_option(WCD_WEBSITE_GROUPS);
        if (! $wcd_groups) {
            return;
        }
        $this->manual_group_id     = $wcd_groups[WCD_MANUAL_DETECTION_GROUP] ?? false;
        $this->monitoring_group_id = $wcd_groups[WCD_AUTO_DETECTION_GROUP] ?? false;
    }

    /** This just calls the version check from a backup cron.
     *
     * @return void
     */
    public function wcd_wp_version_check()
    {
        wp_version_check();
    }


    /**
     * Fires when wp auto updates are done.
     *
     * @return void
     */
    public function automatic_updates_complete()
    {
        \WebChangeDetector\WebChangeDetector_Admin_Utils::log_error('Automatic Updates Complete. Running post-update stuff.');

        // Auto updates are done. So we remove the option.
        delete_option(WCD_AUTO_UPDATES_RUNNING);

        // We don't do anything here if wcd checks are disabled, or we don't have pre_auto_update option.
        $auto_update_settings = self::get_auto_update_settings();
        if (! array_key_exists('auto_update_checks_enabled', $auto_update_settings) || ! get_option(WCD_PRE_AUTO_UPDATE)) {
            \WebChangeDetector\WebChangeDetector_Admin_Utils::log_error('Skipping after update stuff as checks are disabled or we don\'t have pre-update checks.');
            return;
        }

        // Clear all caches before taking post-update screenshots.
        $this->clear_wordpress_caches();

        // Start the post-update screenshots.
        \WebChangeDetector\WebChangeDetector_Admin_Utils::log_error('Starting post-update screenshots and comparisons.');
        $response = \WebChangeDetector\WebChangeDetector_API_V2::take_screenshot_v2($this->manual_group_id, 'post');
        \WebChangeDetector\WebChangeDetector_Admin_Utils::log_error('Post-Screenshot Response: ' . wp_json_encode($response));
        update_option(
            WCD_POST_AUTO_UPDATE,
            array(
                'status'   => 'processing',
                'batch_id' => $response['batch'],
            ),
            false
        );

        // Add the batch id to the comparison batches. This is used to send the mail and for showing "Auto Update Checks" in the change detection page.
        $comparison_batches = get_option(WCD_AUTO_UPDATE_COMPARISON_BATCHES);
        if (! $comparison_batches) {
            $comparison_batches = array();
        }
        $comparison_batches[] = $response['batch'];
        update_option(WCD_AUTO_UPDATE_COMPARISON_BATCHES, $comparison_batches);
        \WebChangeDetector\WebChangeDetector_API_V2::update_batch_v2($response['batch'], 'Auto Update Checks - ' . WebChangeDetector_Admin_Utils::get_domain_from_site_url());

        $this->wcd_cron_check_post_queues();
    }

    /**
     * Cron for checking post_sc to be finished
     *
     * @return void
     */
    public function wcd_cron_check_post_queues()
    {
        $post_sc_option = get_option(WCD_POST_AUTO_UPDATE);

        // Check if we still have the post_sc_option. If not, we already sent the mail.
        if (! $post_sc_option) {
            \WebChangeDetector\WebChangeDetector_Admin_Utils::log_error('No post_sc_option found. So we already sent the mail.');
            return;
        }
        \WebChangeDetector\WebChangeDetector_Admin_Utils::log_error('Checking if post-update screenshots are done: ' . wp_json_encode($post_sc_option));
        $response = \WebChangeDetector\WebChangeDetector_API_V2::get_queues_v2($post_sc_option['batch_id'], 'open,processing');
        \WebChangeDetector\WebChangeDetector_Admin_Utils::log_error('Response: ' . wp_json_encode($response));

        // Check if the batch is done.
        if (count($response['data']) > 0) {
            // There are still open or processing queues. So we check again in a minute.
            \WebChangeDetector\WebChangeDetector_Admin_Utils::log_error('There are still open or processing queues. So we check again in a minute.');
            $this->reschedule('wcd_cron_check_post_queues');
        } else {

            // Send the mail and update the last successful auto updates.
            $this->send_change_detection_mail($post_sc_option);
            update_option(WCD_LAST_SUCCESSFULL_AUTO_UPDATES, time());

            // We don't need the webhook anymore.
            \WebChangeDetector\WebChangeDetector_API_V2::delete_webhook_v2(get_option(WCD_WORDPRESS_CRON));

            // Cleanup wp_options and cron webhook.
            delete_option(WCD_WORDPRESS_CRON);
            delete_option(WCD_PRE_AUTO_UPDATE);
            delete_option(WCD_POST_AUTO_UPDATE);
            delete_option(WCD_AUTO_UPDATES_RUNNING);
        }
    }

    /**
     * Set lock to prevent wp from updating
     *
     * @return void
     */
    public function set_lock()
    {
        \WebChangeDetector\WebChangeDetector_Admin_Utils::log_error('Setting Lock');
        update_option($this->lock_name, time() - HOUR_IN_SECONDS + MINUTE_IN_SECONDS);
    }

    /** Reset next cron run of wp_version_check to our auto_update_checks_from.
     *
     * @param array $group_settings Array of group settings.
     * @return void
     */
    public function wcd_save_update_group_settings($group_settings)
    {
        // Get the new time in local time zone.
        if (isset($group_settings['auto_update_checks_from'])) {
            $auto_update_checks_from = $group_settings['auto_update_checks_from'];
        } else {
            $auto_update_settings = self::get_auto_update_settings();
            if (! $auto_update_settings) {
                return;
            }
            $auto_update_checks_from = $auto_update_settings['auto_update_checks_from'];
        }

        // Convert the local time into gmt time.
        $should_next_run     = gmdate('U', strtotime($auto_update_checks_from));
        $should_next_run_gmt = get_gmt_from_date(gmdate('Y-m-d H:i:s', $should_next_run), 'U');

        $now_gmt = get_gmt_from_date(current_time('Y-m-d H:i:s'), 'U');

        // Add a day if we passed the auto_update_checks_from time already.
        if ($now_gmt > $should_next_run_gmt) {
            $should_next_run_gmt = strtotime('+1 day', $should_next_run_gmt);
        }

        // Reschedule the wp_version_check cron to our "from" time.
        wp_clear_scheduled_hook('wp_version_check');
        wp_schedule_event($should_next_run_gmt, 'twicedaily', 'wp_version_check');

        // Backup cron in case something else changes the wp_version_check cron.
        wp_clear_scheduled_hook('wcd_wp_version_check');
        wp_schedule_event($should_next_run_gmt, 'daily', 'wcd_wp_version_check');
    }

    /** Starting the pre-update screenshots before auto-updates are started.
     * We set the lock to delay WP from starting the auto updates.
     * Auto updates are delayed when they are not in the selected timeframe.
     *
     * @return void
     */
    public function wp_maybe_auto_update()
    {

        // Check if the auto updates are already started. Then we skip the auto updates.
        if (get_option(WCD_AUTO_UPDATES_RUNNING)) {
            \WebChangeDetector\WebChangeDetector_Admin_Utils::log_error('Auto updates are already started. Skipping auto updates.');
            return;
        }

        // Check if post-update screenshots are already done. Then we skip the auto updates.
        if (get_option(WCD_POST_AUTO_UPDATE)) {
            \WebChangeDetector\WebChangeDetector_Admin_Utils::log_error('Post-update screenshots already processed. Skipping auto updates.');
            $this->set_lock();
            return;
        }

        // Make auto-updates only once every 24h. Otherwise we skip the auto updates.
        $last_successfull_auto_updates = get_option(WCD_LAST_SUCCESSFULL_AUTO_UPDATES);
        // Only once in 12 hours are auto updates allowed. We shouldn't do every 24 hours as the successful time is at the end of the auto updates.
        // And when we start the auto updates on the next day, 24 hours might not be over yet.
        if ($last_successfull_auto_updates && $last_successfull_auto_updates + 12 * HOUR_IN_SECONDS > time()) {
            // We already did auto-updates in the last 24h. Skipping this one now.
            \WebChangeDetector\WebChangeDetector_Admin_Utils::log_error('Auto updates already done at ' . gmdate('Y-m-d H:i:s', get_option(WCD_LAST_SUCCESSFULL_AUTO_UPDATES)) . '. We only do them once per day. Skipping auto updates.');
            $this->set_lock();
            return;
        }

        // Remove the lock to start the updates.
        delete_option($this->lock_name);

        // Get the auto-update settings.
        $auto_update_settings = self::get_auto_update_settings();

        // We don't have auto-update settings yet or the manual checks group is not set. So, go the wp way.
        if (! $auto_update_settings || ! $this->manual_group_id) {
            \WebChangeDetector\WebChangeDetector_Admin_Utils::log_error('Running auto updates without checks. Don\'t have an group_id or auto update settings. ');
            return;
        }

        // Check if our auto update checks are enabled.
        if (
            ! array_key_exists('auto_update_checks_enabled', $auto_update_settings) ||
            empty($auto_update_settings['auto_update_checks_enabled'])
        ) {
            \WebChangeDetector\WebChangeDetector_Admin_Utils::log_error('Running auto updates without checks. They are disabled in WCD.');
            return;
        }

        // Check if we do updates on today's weekday.
        $todays_weekday = strtolower(current_time('l'));
        if (
            ! array_key_exists('auto_update_checks_' . $todays_weekday, $auto_update_settings) ||
            empty($auto_update_settings['auto_update_checks_' . $todays_weekday])
        ) {
            \WebChangeDetector\WebChangeDetector_Admin_Utils::log_error('Canceling auto updates: ' . $todays_weekday . ' is disabled.');
            $this->set_lock();
            return;
        }

        // Get the current time in the same format (HH:MM).
        $current_time = current_time('H:i');

        // Convert the times to timestamps for comparison.
        $from_timestamp    = strtotime($auto_update_settings['auto_update_checks_from']);
        $to_timestamp      = strtotime($auto_update_settings['auto_update_checks_to']);
        $current_timestamp = strtotime($current_time);

        // Check if current time is between from_time and to_time.
        if ($from_timestamp < $to_timestamp) {
            // Case 1: Time range is on the same day.
            if ($current_timestamp < $from_timestamp || $current_timestamp > $to_timestamp) {
                \WebChangeDetector\WebChangeDetector_Admin_Utils::log_error(
                    'Canceling auto updates: ' . current_time('H:i') .
                        ' is not between ' . $auto_update_settings['auto_update_checks_from'] .
                        ' and ' . $auto_update_settings['auto_update_checks_to']
                );
                $this->set_lock();
                return;
            }
        } else {
            // Case 2: Time range spans midnight.
            $to_timestamp = strtotime($auto_update_settings['auto_update_checks_to'] . ' +1 day');
            if (! ($current_timestamp >= $from_timestamp || $current_timestamp <= $to_timestamp)) {
                \WebChangeDetector\WebChangeDetector_Admin_Utils::log_error(
                    'Canceling auto updates: ' . current_time('H:i') .
                        ' is not between ' . $auto_update_settings['auto_update_checks_from'] .
                        ' and ' . $auto_update_settings['auto_update_checks_to']
                );
                $this->set_lock();
                return;
            }
        }

        // Check if we are called from one of the known filters.
        if (
            ! doing_filter('wp_maybe_auto_update') &&
            ! doing_filter('jetpack_pre_plugin_upgrade') &&
            ! doing_filter('jetpack_pre_theme_upgrade') &&
            ! doing_filter('jetpack_pre_core_upgrade')
        ) {
            \WebChangeDetector\WebChangeDetector_Admin_Utils::log_error('Not called from one of the known filters. Continuing anyway.');
        }

        // Start pre-update screenshots and do the WCD Magic.
        $wcd_pre_update_data = get_option(WCD_PRE_AUTO_UPDATE);
        if (false === $wcd_pre_update_data) { // We don't have an option yet. So we start screenshots.

            // Create scheduled wp_maybe_auto_update check and external cron at wcd api to make sure the scheduler is triggered every minute.
            $this->reschedule('wp_maybe_auto_update');

            // Clear all caches before taking pre-update screenshots.
            $this->clear_wordpress_caches();

            // Take the screenshots and set the status to processing.
            $sc_response = \WebChangeDetector\WebChangeDetector_API_V2::take_screenshot_v2($this->manual_group_id, 'pre');
            \WebChangeDetector\WebChangeDetector_Admin_Utils::log_error('Pre update SC data: ' . wp_json_encode($sc_response));
            $option_data = array(
                'status'   => 'processing',
                'batch_id' => esc_html($sc_response['batch']),
            );

            // Save the data to the option.
            \WebChangeDetector\WebChangeDetector_Admin_Utils::log_error('Started taking screenshots and setting options');
            update_option(WCD_PRE_AUTO_UPDATE, $option_data, false);
            // Set the lock to prevent WP from starting the auto updates.
            $this->set_lock();
        } else {
            // Screenshots were already started. Now we check if they are done.
            \WebChangeDetector\WebChangeDetector_Admin_Utils::log_error('Checking if screenshots are ready');
            $response = \WebChangeDetector\WebChangeDetector_API_V2::get_queues_v2($wcd_pre_update_data['batch_id'], 'open,processing');

            \WebChangeDetector\WebChangeDetector_Admin_Utils::log_error('Queue: ' . wp_json_encode($response));
            // We check if the queues are done. If so, we update the status.
            if (count($response['data']) === 0) {
                $wcd_pre_update_data['status'] = 'done';
                update_option(WCD_PRE_AUTO_UPDATE, $wcd_pre_update_data, false);
            }

            // If the queues are not done yet, we set the lock. So the WP auto updates are delayed.
            if ('done' !== $wcd_pre_update_data['status']) {
                \WebChangeDetector\WebChangeDetector_Admin_Utils::log_error('SCs are not ready yet. Waiting for next cron run.');
                $this->reschedule('wp_maybe_auto_update');
                $this->set_lock();
            } else {
                \WebChangeDetector\WebChangeDetector_Admin_Utils::log_error('SCs are ready. Continuing with the updates.');
                update_option(WCD_AUTO_UPDATES_RUNNING, true);
            }
        }
    }

    /** Send the change detection mail.
     *
     * @param array $post_sc_option Data about the post sc.
     * @return void
     */
    public function send_change_detection_mail($post_sc_option)
    {
        // If we don't have open or processing queues of the batch anymore, we can check for comparisons.
        $comparisons = \WebChangeDetector\WebChangeDetector_API_V2::get_comparisons_v2(array('batches' => $post_sc_option['batch_id']));
        $mail_body   = '<style>
								table {
									border: 1px solid #ccc;
									width: 100%;
								}
								th, td {
								  padding: 10px;
								  border-top: 1px solid #aaa;
								}
								tr:nth-child(odd),
								 {
									background: #F0F0F1;
								}
								th {
									background: #DCE3ED;
								}
								</style>
								<div style="width: 800px; margin: 0 auto;">';

        $mail_body .= '<p>Howdy again, we checked your website for visual changes during the WP auto updates with WebChange Detector. Here are the results:</p>';
        if (count($comparisons['data'])) {
            $no_difference_rows   = '';
            $with_difference_rows = '';

            foreach ($comparisons['data'] as $comparison) {
                $row =
                    '<tr>
						<td>' . $comparison['url'] . '</td>
						<td>' . $comparison['device'] . '</td>
						<td>' . $comparison['difference_percent'] . ' %</td>
		                <td><a href="' . $comparison['public_link'] . '">See changes</a></td>
					</tr>';
                if (! $comparison['difference_percent']) {
                    $no_difference_rows .= $row;
                } else {
                    $with_difference_rows .= $row;
                }
            }
            $mail_body .= '<div style="width: 300px; margin: 20px auto; text-align: center; padding: 30px; background: #DCE3ED;">';
            if (empty($with_difference_rows)) {
                $mail_body .= '<div style="padding: 10px;background: green; color: #fff; border-radius: 20px; font-size: 14px; width: 20px; height: 20px; display: inline-block; font-weight: 900; transform: scaleX(-1) rotate(-35deg);">L</div>
									<div style="font-size: 18px; padding-top: 20px;">Checks Passed</div>';
            } else {
                $mail_body .= '<div style="padding: 10px;background: red; color: #fff; border-radius: 20px;  font-size: 14px; width: 20px; height: 20px; display: inline-block; font-weight: 900; ">X</div>
									<div style="font-size: 18px; padding-top: 20px;">We found changes<br>Please check the change detections.</div>';
            }
            $mail_body .= '</div>';

            $mail_body .= '<div style="margin: 20px 0 10px 0"><strong>Checks with differences</strong></div>';
            $mail_body .= '<table><tr><th>URL</th><th>Device</th><th>Change in %</th><th>Change Detection Page</th></tr>';
            if (! empty($with_difference_rows)) {
                $mail_body .= $with_difference_rows;
            } else {
                $mail_body .= '<tr><td colspan="3" style="text-align: center;">No change detections to show here</td>';
            }
            $mail_body .= '</table>';

            $mail_body .= '<div style="margin: 20px 0 10px 0"><strong>Checks without differences</strong></div>';
            $mail_body .= '<table><tr><th>URL</th><th>Device</th><th>Change in %</th><th>Change Detection Page</th></tr>';
            if (! empty($no_difference_rows)) {
                $mail_body .= $no_difference_rows;
            } else {
                $mail_body .= '<tr><td colspan="3" style="text-align: center;">No change detections to show here</td>';
            }
            $mail_body .= '</table>';
        } else {
            $mail_body .= 'Sorry, there were no comparisons. Please check your settings in your WebChange Detector Plugin.';
        }

        $mail_body .= '<div style="margin: 20px 0">You can find all change detections and settings for the checks 
								in your wp-admin dashboard of your website.<br><br>
								Your WebChange Detector team</div>';

        $auto_update_settings = self::get_auto_update_settings();
        $to                   = get_bloginfo('admin_email');
        if (array_key_exists('auto_update_checks_emails', $auto_update_settings) || ! empty($auto_update_settings['auto_update_checks_emails'])) {
            $to = $auto_update_settings['auto_update_checks_emails'];
        }
        $subject = '[' . get_bloginfo('name') . '] Auto Update Checks by WebChange Detector';
        $headers = array('Content-Type: text/html; charset=UTF-8');
        \WebChangeDetector\WebChangeDetector_Admin_Utils::log_error('Sending Mail with differences');
        wp_mail($to, $subject, $mail_body, $headers);
    }

    /** Get the auto-update settings.
     *
     * @return false|mixed|null
     */
    public static function get_auto_update_settings()
    {
        static $auto_update_settings;
        if ($auto_update_settings) {
            return $auto_update_settings;
        }

        $wcd                  = new WebChangeDetector_Admin();
        $auto_update_settings = $wcd->settings_handler->get_website_details()['auto_update_settings'] ?? [];

        // Enable auto-update checks if the defines are set.
        if (defined('WCD_AUTO_UPDATES_ENABLED') && true === \WCD_AUTO_UPDATES_ENABLED) {
            $auto_update_settings['auto_update_checks_enabled'] = true;
        }
        return $auto_update_settings;
    }

    /**
     * Get or create webhook key for API authentication
     *
     * @return string The webhook key
     */
    private function get_or_create_webhook_key()
    {
        $webhook_key = get_option('wcd_webhook_key', '');
        if (empty($webhook_key)) {
            // Create a new webhook key if we don't have one.
            $webhook_key = wp_generate_password(32, false);
            update_option('wcd_webhook_key', $webhook_key);
        }
        return $webhook_key;
    }

    /**
     * Create a cron at our api to trigger a hook after a certain time.
     *
     * @param string $hook Hook name.
     * @return void
     */
    private function reschedule($hook)
    {
        // Our cron method for the hook.
        $how_long = 60; // 60 seconds.
        wp_clear_scheduled_hook($hook);
        wp_schedule_single_event(time() + $how_long, $hook);

        // Store the webhook ID to avoid duplication.
        $webhook_id = get_option(WCD_WORDPRESS_CRON, false);
        if ($webhook_id) {
            \WebChangeDetector\WebChangeDetector_Admin_Utils::log_error('We already have a webhook for this hook. Skipping...');
            return;
        }

        // Create our external webhook url.
        $webhook_url = add_query_arg(
            array(
                'wcd_action' => WCD_TRIGGER_AUTO_UPDATE_CRON,
                'key'        => $this->get_or_create_webhook_key(),
            ),
            site_url()
        );

        \WebChangeDetector\WebChangeDetector_Admin_Utils::log_error('Creating webhook to trigger ' . $hook);

        // Create a new WordPress cron webhook.
        $result = \WebChangeDetector\WebChangeDetector_API_V2::add_webhook_v2($webhook_url, 'wordpress_cron', gmdate('Y-m-d H:i:s', time() + HOUR_IN_SECONDS * 3));

        if (is_array($result) && isset($result['data']) && isset($result['data']['id'])) {
            // Store the webhook ID for later reference.
            update_option(WCD_WORDPRESS_CRON, $result['data']['id']);
        }
    }

    /**
     * Process webhook trigger by executing the appropriate WordPress cron event
     */
    public function handle_webhook_trigger()
    {
        $is_authorized = false;

        // Method 1: Check for key-based auth (new style).
        // phpcs:disable WordPress.Security.NonceVerification.Recommended
        // We're using a custom API key verification approach instead of nonces since this is an external webhook
        // that needs to remain valid for several hours. The 'key' parameter contains a random 32-character string
        // that's verified against our stored option.
        if (isset($_GET['wcd_action']) && isset($_GET['key'])) {
            $wcd_action = sanitize_text_field(wp_unslash($_GET['wcd_action']));
            $key        = sanitize_text_field(wp_unslash($_GET['key']));

            if (WCD_TRIGGER_AUTO_UPDATE_CRON === $wcd_action && ! empty($key)) {
                $webhook_key = $this->get_or_create_webhook_key();
                if (! empty($webhook_key) && $key === $webhook_key) {
                    $is_authorized = true;
                }
            }
        } elseif (! get_transient('wcd_updated_legacy_cron')) {
            // Method 2: Fallback for old webhooks - verify request comes from our API server.
            // phpcs:disable WordPress.Security.ValidatedSanitizedInput.InputNotValidated
            // We're validating the IP address against known API server IPs as a secondary authentication mechanism.

            // Get the remote host/IP.
            $remote_host = '';
            if (isset($_SERVER['REMOTE_ADDR'])) {
                $remote_host = sanitize_text_field(wp_unslash($_SERVER['REMOTE_ADDR']));
            }
            \WebChangeDetector\WebChangeDetector_Admin_Utils::log_error('Remote host: ' . $remote_host);
            // Our API server IP.
            $api_server_ip    = '138.68.83.218';
            $api_server_ip_v6 = '2a03:b0c0:3:d0::f94:7001';

            if (defined('WCD_API_SERVER_IP') && WCD_API_SERVER_IP) {
                $api_server_ip = WCD_API_SERVER_IP;
            }
            if (defined('WCD_API_SERVER_IP_V6') && WCD_API_SERVER_IP_V6) {
                $api_server_ip_v6 = WCD_API_SERVER_IP_V6;
            }

            // Check if request is from one of our API servers.
            if (gethostbyname($remote_host) === gethostbyname($api_server_ip) || gethostbyname($remote_host) === gethostbyname($api_server_ip_v6)) {

                // Get the webhook ID from our local option.
                $webhook_id = get_option(WCD_WORDPRESS_CRON);

                // Check if we have a webhook ID.
                if ($webhook_id) {
                    \WebChangeDetector\WebChangeDetector_Admin_Utils::log_error('Legacy webhook request validated by host: ' . $remote_host);

                    // Create our external webhook url.
                    $webhook_url = add_query_arg(
                        array(
                            'wcd_action' => WCD_TRIGGER_AUTO_UPDATE_CRON,
                            'key'        => $this->get_or_create_webhook_key(),
                        ),
                        site_url()
                    );

                    // Update the webhook.
                    $result = \WebChangeDetector\WebChangeDetector_API_V2::update_webhook_v2($webhook_id, $webhook_url);

                    // Check if the webhook was updated successfully.
                    if (! empty($result['data'])) {
                        // We have a valid webhook, so we can authorize the request.
                        $is_authorized = true;
                        set_transient('wcd_updated_legacy_cron', true, 60 * 60 * 24);
                        \WebChangeDetector\WebChangeDetector_Admin_Utils::log_error('Updated webhook: ' . wp_json_encode($result));
                    } else {
                        // We have an invalid webhook, so we can't authorize the request and delete our local webhook.
                        \WebChangeDetector\WebChangeDetector_Admin_Utils::log_error('Failed to update webhook: ' . wp_json_encode($result));
                        delete_option(WCD_WORDPRESS_CRON);
                    }
                }
            }
            // phpcs:enable WordPress.Security.ValidatedSanitizedInput.InputNotValidated
        }
        // phpcs:enable WordPress.Security.NonceVerification.Recommended

        if ($is_authorized) {
            \WebChangeDetector\WebChangeDetector_Admin_Utils::log_error('Processing authorized webhook trigger');

            // Force WordPress to process all pending cron events.
            spawn_cron();

            echo 'OK';
            exit;
        }
    }

    /**
     * Defines.
     *
     * @return void
     */
    private function set_defines()
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
        if (! defined('WCD_WORDPRESS_CRON')) {
            define('WCD_WORDPRESS_CRON', 'wcd_wordpress_cron');
        }
        if (! defined('WCD_LAST_SUCCESSFULL_AUTO_UPDATES')) {
            define('WCD_LAST_SUCCESSFULL_AUTO_UPDATES', 'wcd_last_successfull_auto_updates');
        }
        if (! defined('WCD_PRE_AUTO_UPDATE')) {
            define('WCD_PRE_AUTO_UPDATE', 'wcd_pre_auto_update');
        }
        if (! defined('WCD_WP_MAYBE_AUTO_UPDATE')) {
            define('WCD_WP_MAYBE_AUTO_UPDATE', 'wp_maybe_auto_update');
        }
        if (! defined('WCD_POST_AUTO_UPDATE')) {
            define('WCD_POST_AUTO_UPDATE', 'wcd_post_auto_update');
        }
        if (! defined('WCD_AUTO_UPDATES_RUNNING')) {
            define('WCD_AUTO_UPDATES_RUNNING', 'wcd_auto_updates_running');
        }
        if (! defined('WCD_AUTO_UPDATE_SETTINGS')) {
            define('WCD_AUTO_UPDATE_SETTINGS', 'wcd_auto_update_settings');
        }
        if (! defined('WCD_ALLOWANCES')) {
            define('WCD_ALLOWANCES', 'wcd_allowances');
        }
        if (! defined('WCD_HOUR_IN_SECONDS')) {
            define('WCD_HOUR_IN_SECONDS', 3600);
        }
        if (! defined('WCD_AUTO_UPDATE_COMPARISON_BATCHES')) {
            define('WCD_AUTO_UPDATE_COMPARISON_BATCHES', 'wcd_auto_update_comparison_batches');
        }
        if (! defined('WCD_TRIGGER_AUTO_UPDATE_CRON')) {
            define('WCD_TRIGGER_AUTO_UPDATE_CRON', 'trigger_auto_update_cron');
        }
    }

    /**
     * Clear all known WordPress cache plugins and systems.
     *
     * This method clears caches from various popular caching plugins and systems
     * to ensure fresh screenshots are taken during the auto-update process.
     *
     * @return void
     */
    private function clear_wordpress_caches()
    {
        \WebChangeDetector\WebChangeDetector_Admin_Utils::log_error('Clearing all WordPress caches before taking screenshots.');

        $cleared_caches = array();
        $failed_caches  = array();

        // WP Rocket
        try {
            if (function_exists('\rocket_clean_domain')) {
                rocket_clean_domain();
                if (function_exists('\rocket_clean_minify')) {
                    rocket_clean_minify();
                }
                $cleared_caches[] = 'WP Rocket';
            }
        } catch (\Exception $e) {
            $failed_caches[] = 'WP Rocket: ' . $e->getMessage();
        }

        // W3 Total Cache
        try {
            if (function_exists('\w3tc_flush_all')) {
                w3tc_flush_all();
                $cleared_caches[] = 'W3 Total Cache';
            }
        } catch (\Exception $e) {
            $failed_caches[] = 'W3 Total Cache: ' . $e->getMessage();
        }

        // LiteSpeed Cache
        try {
            if (defined('LSCWP_VERSION')) {
                do_action('litespeed_purge_all');
                do_action('litespeed_purge_cssjs');
                do_action('litespeed_purge_object');
                $cleared_caches[] = 'LiteSpeed Cache';
            }
            if (class_exists('\LiteSpeed_Cache_API') && method_exists('\LiteSpeed_Cache_API', 'purge_all')) {
                \LiteSpeed_Cache_API::purge_all();
                if (! in_array('LiteSpeed Cache', $cleared_caches, true)) {
                    $cleared_caches[] = 'LiteSpeed Cache';
                }
            }
        } catch (\Exception $e) {
            $failed_caches[] = 'LiteSpeed Cache: ' . $e->getMessage();
        }

        // WP Super Cache
        try {
            if (function_exists('\wp_cache_clear_cache')) {
                @wp_cache_clear_cache(true);
                $cleared_caches[] = 'WP Super Cache';
            } elseif (function_exists('\wp_cache_post_change')) {
                @wp_cache_post_change('');
                $cleared_caches[] = 'WP Super Cache';
            }
        } catch (\Exception $e) {
            $failed_caches[] = 'WP Super Cache: ' . $e->getMessage();
        }

        // WP Fastest Cache
        try {
            if (function_exists('\wpfc_clear_all_cache')) {
                wpfc_clear_all_cache(true);
                $cleared_caches[] = 'WP Fastest Cache';
            }
        } catch (\Exception $e) {
            $failed_caches[] = 'WP Fastest Cache: ' . $e->getMessage();
        }

        // Cache Enabler
        try {
            if (class_exists('\Cache_Enabler') && method_exists('\Cache_Enabler', 'clear_total_cache')) {
                \Cache_Enabler::clear_total_cache();
                $cleared_caches[] = 'Cache Enabler';
            }
            // New Cache Enabler (v1.5.0+)
            if (class_exists('\Cache_Enabler_Engine') && method_exists('\Cache_Enabler_Engine', 'clear_cache')) {
                \Cache_Enabler_Engine::clear_cache();
                if (! in_array('Cache Enabler', $cleared_caches, true)) {
                    $cleared_caches[] = 'Cache Enabler';
                }
            }
        } catch (\Exception $e) {
            $failed_caches[] = 'Cache Enabler: ' . $e->getMessage();
        }

        // Comet Cache
        try {
            if (class_exists('\comet_cache') && method_exists('\comet_cache', 'clear')) {
                \comet_cache::clear();
                $cleared_caches[] = 'Comet Cache';
            }
        } catch (\Exception $e) {
            $failed_caches[] = 'Comet Cache: ' . $e->getMessage();
        }

        // Swift Performance
        try {
            if (class_exists('\Swift_Performance_Cache') && method_exists('\Swift_Performance_Cache', 'clear_all_cache')) {
                \Swift_Performance_Cache::clear_all_cache();
                $cleared_caches[] = 'Swift Performance';
            }
        } catch (\Exception $e) {
            $failed_caches[] = 'Swift Performance: ' . $e->getMessage();
        }

        // Borlabs Cache
        try {
            if (function_exists('\borlabsCacheClearCache')) {
                borlabsCacheClearCache();
                $cleared_caches[] = 'Borlabs Cache';
            }
            if (has_action('borlabsCookie/thirdPartyCacheClearer/shouldClearCache')) {
                do_action('borlabsCookie/thirdPartyCacheClearer/shouldClearCache', true);
            }
        } catch (\Exception $e) {
            $failed_caches[] = 'Borlabs Cache: ' . $e->getMessage();
        }

        // NitroPack
        try {
            if (function_exists('\nitropack_reset_cache')) {
                nitropack_reset_cache();
                $cleared_caches[] = 'NitroPack';
            } elseif (function_exists('\nitropack_purge_cache')) {
                nitropack_purge_cache();
                $cleared_caches[] = 'NitroPack';
            }
        } catch (\Exception $e) {
            $failed_caches[] = 'NitroPack: ' . $e->getMessage();
        }

        // Redis Object Cache
        try {
            global $wp_object_cache;
            if ($wp_object_cache && method_exists($wp_object_cache, 'flush')) {
                $wp_object_cache->flush();
                $cleared_caches[] = 'Redis Object Cache';
            }
        } catch (\Exception $e) {
            $failed_caches[] = 'Redis Object Cache: ' . $e->getMessage();
        }

        // Object Cache Pro
        try {
            if (class_exists('\Object_Cache_Pro')) {
                global $wp_object_cache;
                if (method_exists($wp_object_cache, 'flushRuntime')) {
                    $wp_object_cache->flushRuntime();
                }
                if (method_exists($wp_object_cache, 'flushBlog')) {
                    $wp_object_cache->flushBlog();
                }
                $cleared_caches[] = 'Object Cache Pro';
            }
        } catch (\Exception $e) {
            $failed_caches[] = 'Object Cache Pro: ' . $e->getMessage();
        }

        // SG Optimizer
        try {
            if (function_exists('\sg_cachepress_purge_cache')) {
                sg_cachepress_purge_cache();
                $cleared_caches[] = 'SG Optimizer';
            }
            if (has_action('siteground_optimizer_flush_cache')) {
                do_action('siteground_optimizer_flush_cache');
                if (! in_array('SG Optimizer', $cleared_caches, true)) {
                    $cleared_caches[] = 'SG Optimizer';
                }
            }
        } catch (\Exception $e) {
            $failed_caches[] = 'SG Optimizer: ' . $e->getMessage();
        }

        // WP-Optimize
        try {
            if (function_exists('\wpo_cache_flush')) {
                wpo_cache_flush();
                $cleared_caches[] = 'WP-Optimize';
            }
        } catch (\Exception $e) {
            $failed_caches[] = 'WP-Optimize: ' . $e->getMessage();
        }

        // Autoptimize
        try {
            if (class_exists('\autoptimizeCache') && method_exists('\autoptimizeCache', 'clearall')) {
                \autoptimizeCache::clearall();
                $cleared_caches[] = 'Autoptimize';
            }
        } catch (\Exception $e) {
            $failed_caches[] = 'Autoptimize: ' . $e->getMessage();
        }

        // Hummingbird
        try {
            if (did_action('plugins_loaded')) {
                do_action('wphb_clear_page_cache');
                $cleared_caches[] = 'Hummingbird';
            }
        } catch (\Exception $e) {
            $failed_caches[] = 'Hummingbird: ' . $e->getMessage();
        }

        // Breeze (Cloudways)
        try {
            do_action('breeze_clear_all_cache');
            $cleared_caches[] = 'Breeze';
        } catch (\Exception $e) {
            $failed_caches[] = 'Breeze: ' . $e->getMessage();
        }

        // Kinsta Cache
        try {
            if (class_exists('\Kinsta\Cache') && ! empty($kinsta_cache)) {
                $kinsta_cache->kinsta_cache_purge->purge_complete_caches();
                $cleared_caches[] = 'Kinsta Cache';
            }
        } catch (\Exception $e) {
            $failed_caches[] = 'Kinsta Cache: ' . $e->getMessage();
        }

        // Pagely Cache
        try {
            if (class_exists('\PagelyCachePurge') && method_exists('\PagelyCachePurge', 'purgeAll')) {
                \PagelyCachePurge::purgeAll();
                $cleared_caches[] = 'Pagely Cache';
            }
        } catch (\Exception $e) {
            $failed_caches[] = 'Pagely Cache: ' . $e->getMessage();
        }

        // WP Engine System
        try {
            if (class_exists('\WpeCommon') && method_exists('\WpeCommon', 'purge_memcached')) {
                \WpeCommon::purge_memcached();
                $cleared_caches[] = 'WP Engine Memcached';
            }
            if (class_exists('\WpeCommon') && method_exists('\WpeCommon', 'purge_varnish_cache')) {
                \WpeCommon::purge_varnish_cache();
                $cleared_caches[] = 'WP Engine Varnish';
            }
        } catch (\Exception $e) {
            $failed_caches[] = 'WP Engine: ' . $e->getMessage();
        }

        // Cloudflare
        try {
            if (class_exists('\CF\WordPress\Hooks')) {
                $cloudflare = new \CF\WordPress\Hooks();
                if (method_exists($cloudflare, 'purgeCacheEverything')) {
                    $cloudflare->purgeCacheEverything();
                    $cleared_caches[] = 'Cloudflare';
                }
            }
        } catch (\Exception $e) {
            $failed_caches[] = 'Cloudflare: ' . $e->getMessage();
        }

        // Flying Press
        try {
            if (class_exists('\FlyingPress') && method_exists('\FlyingPress', 'purge_cached_pages')) {
                \FlyingPress::purge_cached_pages();
                $cleared_caches[] = 'Flying Press';
            }
        } catch (\Exception $e) {
            $failed_caches[] = 'Flying Press: ' . $e->getMessage();
        }

        // WP Cloudflare Super Page Cache
        try {
            if (class_exists('\SW_CLOUDFLARE_PAGECACHE') && method_exists('\SW_CLOUDFLARE_PAGECACHE', 'cloudflare_purge_cache')) {
                $cf_cache = new \SW_CLOUDFLARE_PAGECACHE();
                $cf_cache->cloudflare_purge_cache();
                $cleared_caches[] = 'WP Cloudflare Super Page Cache';
            }
        } catch (\Exception $e) {
            $failed_caches[] = 'WP Cloudflare Super Page Cache: ' . $e->getMessage();
        }

        // Perfmatters
        try {
            if (function_exists('\perfmatters_clear_page_cache')) {
                perfmatters_clear_page_cache();
                $cleared_caches[] = 'Perfmatters';
            }
        } catch (\Exception $e) {
            $failed_caches[] = 'Perfmatters: ' . $e->getMessage();
        }

        // WP-Rocket Cloudflare Add-on
        try {
            if (function_exists('\rocket_cloudflare_purge_cache')) {
                rocket_cloudflare_purge_cache();
                $cleared_caches[] = 'WP-Rocket Cloudflare Add-on';
            }
        } catch (\Exception $e) {
            $failed_caches[] = 'WP-Rocket Cloudflare Add-on: ' . $e->getMessage();
        }

        // WordPress Core Object Cache
        try {
            if (function_exists('\wp_cache_flush')) {
                wp_cache_flush();
                $cleared_caches[] = 'WordPress Core Object Cache';
            }
        } catch (\Exception $e) {
            $failed_caches[] = 'WordPress Core Object Cache: ' . $e->getMessage();
        }

        // WordPress Transients
        try {
            if (function_exists('\wc_delete_product_transients')) {
                wc_delete_product_transients();
                $cleared_caches[] = 'WooCommerce Transients';
            }
            if (function_exists('\delete_expired_transients')) {
                delete_expired_transients(true);
                $cleared_caches[] = 'Expired Transients';
            }
        } catch (\Exception $e) {
            $failed_caches[] = 'WordPress Transients: ' . $e->getMessage();
        }

        // Log summary
        if (! empty($cleared_caches)) {
            \WebChangeDetector\WebChangeDetector_Admin_Utils::log_error('Successfully cleared caches: ' . implode(', ', $cleared_caches));
        }
        if (! empty($failed_caches)) {
            \WebChangeDetector\WebChangeDetector_Admin_Utils::log_error('Failed to clear some caches: ' . implode('; ', $failed_caches), 'error');
        }
        if (empty($cleared_caches) && empty($failed_caches)) {
            \WebChangeDetector\WebChangeDetector_Admin_Utils::log_error('No cache plugins detected or cleared.');
        }
    }
}
