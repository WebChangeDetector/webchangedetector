<?php

/**
 * Auto Checks - Refactored with Components
 *
 * @package    webchangedetector
 */

// Prevent direct access.
if (! defined('ABSPATH')) {
    exit;
}

// Are we allowed to see the settings?
if (! empty($this->admin->website_details['allowances']['monitoring_checks_settings']) && $this->admin->website_details['allowances']['monitoring_checks_settings']) {

    $enabled = $group_and_urls['enabled'] ?? false;

?>
    <div class="wcd-settings-card">
        <h2>Monitoring Settings</h2>
        <p>Configure automatic monitoring settings for your selected URLs and get notified about changes.</p>

        <form action="admin.php?page=webchangedetector-auto-settings" method="post">
            <input type="hidden" name="wcd_action" value="save_group_settings">
            <input type="hidden" name="group_id" value="<?php echo esc_html($group_id); ?>">
            <?php wp_nonce_field('save_group_settings'); ?>
            <input type="hidden" name="monitoring" value="1">
            <input type="hidden" name="group_name" value="<?php echo esc_html($group_and_urls['name'] ?? ''); ?>">

            <div class="wcd-form-row wcd-monitoring-enabled">
                <div class="wcd-form-label-wrapper">
                    <label class="wcd-form-label">Enable Monitoring</label>
                    <div class="wcd-description">Enable or disable the monitoring for your selected URLs.</div>
                </div>
                <div class="wcd-form-control">
                    <?php
                    // Enable Monitoring Toggle (without content).
                    $toggle_name        = 'enabled';
                    $is_enabled         = $enabled;
                    $toggle_label       = '';
                    $toggle_description = '';
                    $section_id         = 'monitoring-settings-content';
                    $content            = ''; // Empty content for the toggle.

                    // Include Toggle Section Component.
                    include WP_PLUGIN_DIR . '/webchangedetector/admin/partials/components/ui-elements/toggle-section.php';
                    ?>
                </div>
            </div>

            <div id="monitoring-settings-content" style="<?php echo $enabled ? '' : 'display: none;'; ?>">
                <div class="wcd-form-row monitoring-setting wcd-monitoring-interval">
                    <div class="wcd-form-label-wrapper">
                        <label class="wcd-form-label">Interval in Hours</label>
                        <div class="wcd-description">This is the interval in which the checks are done.</div>
                    </div>
                    <div class="wcd-form-control">
                        <?php
                        // Interval Selector Component.
                        $current_interval      = $group_and_urls['interval_in_h'] ?? 24;
                        $account_details       = $this->account_handler->get_account();
                        $show_minute_intervals = false;
                        if (! $account_details['is_subaccount'] && ! in_array($account_details['plan'], array('trial', 'free', 'personal', 'personal_pro'), true)) {
                            $show_minute_intervals = true;
                        }
                        $field_name  = 'interval_in_h';
                        $label       = ''; // Empty label since it's already in the form structure.
                        $description = ''; // Empty description since it's already in the form structure.
                        include WP_PLUGIN_DIR . '/webchangedetector/admin/partials/components/monitoring/interval-selector.php';
                        ?>
                    </div>
                </div>

                <div class="wcd-form-row monitoring-setting wcd-monitoring-hour-of-day">
                    <div class="wcd-form-label-wrapper">
                        <label class="wcd-form-label">Hour of the Day</label>
                        <div class="wcd-description">Set the hour on which the monitoring checks should be done.</div>
                    </div>
                    <div class="wcd-form-control">
                        <?php
                        // Hour Selector Component.
                        $current_hour = $group_and_urls['hour_of_day'] ?? 0;
                        $field_name   = 'hour_of_day';
                        $label        = ''; // Empty label since it's already in the form structure.
                        $description  = ''; // Empty description since it's already in the form structure.
                        include WP_PLUGIN_DIR . '/webchangedetector/admin/partials/components/monitoring/hour-selector.php';
                        ?>
                    </div>
                </div>

                <div class="wcd-form-row monitoring-setting wcd-monitoring-threshold">
                    <div class="wcd-form-label-wrapper">
                        <label class="wcd-form-label">Change Detection Threshold</label>
                        <div class="wcd-description">Ignore changes in Change Detections below the threshold. Use this carefully. If you set it too low, you might miss changes that are important.</div>
                    </div>
                    <div class="wcd-form-control">
                        <?php
                        // Threshold Setting Component.
                        $threshold   = $group_and_urls['threshold'] ?? 0.0;
                        $label       = ''; // Empty label since it's already in the form structure.
                        $description = ''; // Empty description since it's already in the form structure.
                        include WP_PLUGIN_DIR . '/webchangedetector/admin/partials/components/forms/threshold-setting.php';
                        ?>
                    </div>
                </div>

                <div class="wcd-form-row monitoring-setting wcd-monitoring-alert-emails">
                    <div class="wcd-form-label-wrapper">
                        <label class="wcd-form-label">Alert Email Addresses</label>
                        <div class="wcd-description">Enter the email address(es) which should get notified about monitoring alerts.</div>
                    </div>
                    <div class="wcd-form-control">
                        <?php
                        // Email Input Component.
                        $email_value     = $group_and_urls['alert_emails'] ?? '';
                        $field_name      = 'alert_emails';
                        $label           = ''; // Empty label since it's already in the form structure.
                        $description     = ''; // Empty description since it's already in the form structure.
                        $multiple        = true;
                        $show_validation = true;
                        include WP_PLUGIN_DIR . '/webchangedetector/admin/partials/components/forms/email-input.php';
                        ?>
                    </div>
                </div>
            </div>

            <div class="wcd-form-row monitoring-setting wcd-monitoring-alert-zapier" style="<?php echo $auto_update_checks_enabled ? '' : 'display: none;'; ?>">
                <label class="wcd-form-label">Notification to Zapier</label>
                <p>Conntect Zapier with your WebChange Detector account and get alerts directly in 6000+ apps.</p>
                <p><a class="button" href="https://zapier.com/apps/webchange-detector/integrations" target="_blank">Zapier</a></p>
            </div>

            <div class="wcd-form-row css-injection wcd-monitoring-css">
                <div class="wcd-form-label-wrapper">
                    <label class="wcd-form-label">CSS Settings</label>
                    <div class="wcd-description">Hide or modify elements via CSS before taking screenshots (e.g. dynamic content).</div>
                </div>
                <div class="wcd-form-control">
                    <div style="margin-top: 10px; width: 100%;">
                        <div class="code-tags default-bg">&lt;style&gt;</div>
                        <textarea name="css" class="codearea wcd-css-textarea" rows="15" cols="80"><?php echo esc_textarea($group_and_urls['css'] ?? ''); ?></textarea>
                        <div class="code-tags default-bg">&lt;/style&gt;</div>
                    </div>
                </div>
            </div>

            <?php submit_button('Save Settings', 'primary wizard-save-auto-settings', 'submit', true, array('onclick' => 'return wcdValidateFormAutoSettings()')); ?>
        </form>
    </div>

    <script type="text/javascript">
        function wcdValidateFormAutoSettings() {
            // Only validate if monitoring is enabled
            var monitoringEnabled = document.querySelector('input[name="enabled"]');
            if (monitoringEnabled && monitoringEnabled.checked) {
                // Validate email if present.
                if (typeof window['validate_alert_emails'] === 'function') {
                    if (!window['validate_alert_emails']()) {
                        return false;
                    }
                }
            }

            return true;
        }
    </script>
<?php
}
?>