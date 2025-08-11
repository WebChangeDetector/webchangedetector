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
        <h2><?php _e('Monitoring Settings', 'webchangedetector'); ?></h2>
        <p><?php _e('Configure automatic monitoring settings for your selected URLs and get notified about changes.', 'webchangedetector'); ?></p>

        <form action="admin.php?page=webchangedetector-auto-settings" method="post">
            <input type="hidden" name="wcd_action" value="save_group_settings">
            <input type="hidden" name="group_id" value="<?php echo esc_html($group_id); ?>">
            <?php wp_nonce_field('save_group_settings'); ?>
            <input type="hidden" name="monitoring" value="1">
            <input type="hidden" name="group_name" value="<?php echo esc_html($group_and_urls['name'] ?? ''); ?>">

            <div class="wcd-form-row wcd-monitoring-enabled">
                <div class="wcd-form-label-wrapper">
                    <label class="wcd-form-label"><?php _e('Enable Monitoring', 'webchangedetector'); ?></label>
                    <div class="wcd-description"><?php _e('Enable or disable the monitoring for your selected URLs.', 'webchangedetector'); ?></div>
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

            <div class="wcd-form-row monitoring-setting wcd-monitoring-interval" style="<?php echo $enabled ? '' : 'display: none;'; ?>">
                <div class="wcd-form-label-wrapper">
                    <label class="wcd-form-label"><?php _e('Interval in Hours', 'webchangedetector'); ?></label>
                    <div class="wcd-description"><?php _e('This is the interval in which the checks are done.', 'webchangedetector'); ?></div>
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

            <div class="wcd-form-row monitoring-setting wcd-monitoring-hour-of-day" style="<?php echo $enabled ? '' : 'display: none;'; ?>">
                <div class="wcd-form-label-wrapper">
                    <label class="wcd-form-label"><?php _e('Hour of the Day', 'webchangedetector'); ?></label>
                    <div class="wcd-description"><?php _e('Set the hour on which the monitoring checks should be done.', 'webchangedetector'); ?></div>
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

            <div class="wcd-form-row monitoring-setting wcd-monitoring-threshold" style="<?php echo $enabled ? '' : 'display: none;'; ?>">
                <div class="wcd-form-label-wrapper">
                    <label class="wcd-form-label"><?php _e('Change Detection Threshold', 'webchangedetector'); ?></label>
                    <div class="wcd-description"><?php _e('Ignore changes in Change Detections below the threshold. Use this carefully. If you set it too low, you might miss changes that are important.', 'webchangedetector'); ?></div>
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

            <div class="wcd-form-row monitoring-setting wcd-monitoring-alert-emails" style="<?php echo $enabled ? '' : 'display: none;'; ?>">
                <div class="wcd-form-label-wrapper">
                    <label class="wcd-form-label"><?php _e('Alerts', 'webchangedetector'); ?></label>
                    <div class="wcd-description">
                        <?php _e('Enter the email address(es) which should get notified about monitoring alerts.', 'webchangedetector'); ?><br>
                        <?php _e('You can also connect <a href="https://zapier.com/apps/webchange-detector/integrations" target="_blank">Zapier</a> to get alerts directly in 6000+ apps.', 'webchangedetector'); ?>    
                    </div>
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

            <div class="wcd-form-row monitoring-setting wcd-monitoring-css" style="<?php echo $enabled ? '' : 'display: none;'; ?>">
                <div class="wcd-form-label-wrapper">
                    <label class="wcd-form-label"><?php _e('CSS Settings', 'webchangedetector'); ?></label>
                    <div class="wcd-description"><?php _e('Hide or modify elements via CSS before taking screenshots (e.g. dynamic content).', 'webchangedetector'); ?></div>
                </div>
                <div class="wcd-form-control">
                    <div style="margin-top: 10px; width: 100%;">
                        <div class="code-tags default-bg">&lt;style&gt;</div>
                        <textarea name="css" class="codearea wcd-css-textarea" rows="15" cols="80"><?php echo esc_textarea($group_and_urls['css'] ?? ''); ?></textarea>
                        <div class="code-tags default-bg">&lt;/style&gt;</div>
                    </div>
                </div>
            </div>

            <?php submit_button(__('Save Settings', 'webchangedetector'), 'primary wizard-save-auto-settings', 'submit', true, array('onclick' => 'return wcdValidateFormAutoSettings()')); ?>
        </form>
    </div>

    <script type="text/javascript">
        // Toggle monitoring settings visibility with slide animation
        jQuery(document).ready(function($) {
            // Listen for changes on the toggle switch
            $(document).on('change', 'input[name="enabled"]', function() {
                if ($(this).is(':checked')) {
                    $('.monitoring-setting').slideDown(400, function() {
                        // Initialize CodeMirror for CSS textarea when monitoring is enabled
                        var cssTextarea = $('.wcd-monitoring-css .wcd-css-textarea')[0];
                        if (cssTextarea && window.wp && window.wp.codeEditor) {
                            // Check if CodeMirror is already initialized
                            var existingEditor = null;
                            if (cssTextarea.nextElementSibling && cssTextarea.nextElementSibling.classList.contains('CodeMirror')) {
                                // CodeMirror already exists, just refresh it
                                var cmInstance = cssTextarea.nextElementSibling.CodeMirror;
                                if (cmInstance) {
                                    // Refresh after a small delay to ensure the element is fully visible
                                    setTimeout(function() {
                                        cmInstance.refresh();
                                    }, 100);
                                }
                            } else {
                                // Initialize new CodeMirror instance
                                var editorSettings = {};
                                if (typeof cm_settings !== 'undefined' && cm_settings.codeEditor) {
                                    editorSettings = cm_settings.codeEditor;
                                }
                                var editor = wp.codeEditor.initialize(cssTextarea, editorSettings);
                                
                                // Refresh the editor after initialization to fix line numbers
                                if (editor && editor.codemirror) {
                                    setTimeout(function() {
                                        editor.codemirror.refresh();
                                    }, 100);
                                }
                            }
                        }
                    });
                } else {
                    $('.monitoring-setting').slideUp();
                }
            });
        });

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