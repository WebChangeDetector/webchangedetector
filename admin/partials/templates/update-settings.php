<?php

/**
 * Manual checks settings - Refactored with Components
 *
 * @package    webchangedetector
 */

// Prevent direct access.
if (! defined('ABSPATH')) {
    exit;
}

// Are we allowed to see the settings?
if (! empty($this->admin->website_details['allowances']['manual_checks_settings']) && $this->admin->website_details['allowances']['manual_checks_settings']) {

    $auto_update_settings = $this->admin->website_details['auto_update_settings'];

    // Prepare weekday data for component.
    $weekdays_data = array();
    $weekdays = array('monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday');
    foreach ($weekdays as $weekday) {
        $weekdays_data[$weekday] = ! empty($auto_update_settings['auto_update_checks_' . $weekday]);
    }

    $auto_update_checks_enabled = ! empty($auto_update_settings['auto_update_checks_enabled']) && ($auto_update_settings['auto_update_checks_enabled'] === true || $auto_update_settings['auto_update_checks_enabled'] === '1' || $auto_update_settings['auto_update_checks_enabled'] === 1);
?>

    <div class="wcd-settings-card">
        <h2><?php _e('WP Auto Update & Manual Checks Settings', 'webchangedetector'); ?></h2>
        <form action="admin.php?page=webchangedetector-update-settings" method="post">
            <input type="hidden" name="wcd_action" value="save_group_settings">
            <input type="hidden" name="step" value="pre-update">
            <input type="hidden" name="group_id" value="<?php echo esc_html($group_id); ?>">
            <?php wp_nonce_field('save_group_settings'); ?>

            <div class="wcd-form-row wcd-auto-update-setting-enabled">
                <div class="wcd-form-label-wrapper">
                    <label class="wcd-form-label"><?php _e('Auto Update Checks', 'webchangedetector'); ?></label>
                    <div class="wcd-description"><?php _e('WP auto updates have to be enabled. This option only enables checks during auto updates.', 'webchangedetector'); ?></div>
                </div>
                <div class="wcd-form-control">
                    <?php
                    // Auto Update Checks Toggle (without content).
                    $toggle_name = 'auto_update_checks_enabled';
                    $is_enabled = $auto_update_checks_enabled;
                    $toggle_label = '';
                    $toggle_description = '';
                    $section_id = 'auto_update_checks_settings';
                    $content = ''; // Empty content for the toggle

                    // Include Toggle Section Component.
                    include WP_PLUGIN_DIR . '/webchangedetector/admin/partials/components/ui-elements/toggle-section.php';
                    ?>
                </div>
                <!-- Auto Update Information Accordion - Always visible underneath the toggle -->
                <div class="wcd-form-row wcd-auto-update-setting-enabled-auto-updates">
                    <div class="wcd-form-control" style="grid-column: 1 / -1;">
                        <?php
                        // Auto Update Information Component.
                        include WP_PLUGIN_DIR . '/webchangedetector/admin/partials/components/settings/auto-update-info.php';
                        ?>
                    </div>
                </div>
            </div>

            <div class="wcd-form-row auto-update-setting wcd-auto-update-setting-from" style="<?php echo $auto_update_checks_enabled ? '' : 'display: none;'; ?>">
                <div class="wcd-form-label-wrapper">
                    <label class="wcd-form-label"><?php _e('Auto Update Timeframe', 'webchangedetector'); ?></label>
                    <div class="wcd-description"><?php _e('Set the time frame in which you want to allow WP auto updates.', 'webchangedetector'); ?></div>
                </div>
                <div class="wcd-form-control">
                    <?php
                    // Time Range Selector Component.
                    // Convert UTC times from API to site timezone for display.
                    require_once WP_PLUGIN_DIR . '/webchangedetector/admin/class-webchangedetector-timezone-helper.php';
                    $utc_from_time = $auto_update_settings['auto_update_checks_from'] ?? gmdate('H:i');
                    $utc_to_time = $auto_update_settings['auto_update_checks_to'] ?? gmdate('H:i', strtotime('+2 hours'));
                    $from_time = \WebChangeDetector\WebChangeDetector_Timezone_Helper::utc_to_site_time($utc_from_time);
                    $to_time = \WebChangeDetector\WebChangeDetector_Timezone_Helper::utc_to_site_time($utc_to_time);
                    $from_name = 'auto_update_checks_from';
                    $to_name = 'auto_update_checks_to';
                    $label = __('Only', 'webchangedetector');
                    $timezone_display = \WebChangeDetector\WebChangeDetector_Timezone_Helper::get_timezone_display_string();
                    $current_time = current_time('H:i');
                    $description = sprintf(__('Times are displayed in your website timezone: %s | Current website time: %s', 'webchangedetector'), $timezone_display, $current_time);
                    include WP_PLUGIN_DIR . '/webchangedetector/admin/partials/components/forms/time-range-selector.php';
                    ?>
                </div>
            </div>

            <div class="wcd-form-row auto-update-setting wcd-auto-update-setting-weekday" style="<?php echo $auto_update_checks_enabled ? '' : 'display: none;'; ?>">
                <div class="wcd-form-label-wrapper">
                    <label class="wcd-form-label"><?php _e('Weekdays', 'webchangedetector'); ?></label>
                    <div class="wcd-description"><?php _e('Set the weekdays in which you want to allow WP auto updates.', 'webchangedetector'); ?></div>
                </div>
                <div class="wcd-form-control">
                    <?php
                    // Weekday Selector Component.
                    $selected_days = $weekdays_data;
                    $name_prefix = 'auto_update_checks_';
                    $label = '';
                    $description = '';
                    $show_validation = true;
                    include WP_PLUGIN_DIR . '/webchangedetector/admin/partials/components/forms/weekday-selector.php';
                    ?>
                </div>
            </div>

            <div class="wcd-form-row auto-update-setting wcd-auto-update-setting-emails" style="<?php echo $auto_update_checks_enabled ? '' : 'display: none;'; ?>">
                <div class="wcd-form-label-wrapper">
                    <label class="wcd-form-label"><?php _e('Notifications', 'webchangedetector'); ?></label>
                    <div class="wcd-description">
                        <?php _e('Enter the email address(es) which should get notified about auto update checks.', 'webchangedetector'); ?><br>
                        <?php _e('You can also connect <a href="https://zapier.com/apps/webchange-detector/integrations" target="_blank">Zapier</a> to get alerts directly in 6000+ apps.', 'webchangedetector'); ?>    
                    </div>
                </div>
                <div class="wcd-form-control">
                    <?php
                    // Email Input Component.
                    $email_value = $auto_update_settings['auto_update_checks_emails'] ?? get_option('admin_email');
                    $field_name = 'auto_update_checks_emails';
                    $label = __('Notification email to', 'webchangedetector');
                    $description = '';
                    $multiple = true;
                    $show_validation = true;
                    include WP_PLUGIN_DIR . '/webchangedetector/admin/partials/components/forms/email-input.php';
                    ?>
                    
                </div>
            </div>

            <div class="wcd-form-row wcd-auto-update-setting-threshold">
                <div class="wcd-form-label-wrapper">
                    <label class="wcd-form-label"><?php _e('Change Detection Threshold', 'webchangedetector'); ?></label>
                    <div class="wcd-description"><?php _e('Ignore changes in Change Detections below the threshold. Use this carefully. If you set it too low, you might miss changes that are important.', 'webchangedetector'); ?></div>
                </div>
                <div class="wcd-form-control">
                    <?php
                    // Threshold Setting Component.
                    $label = '';
                    $description = '';
                    $threshold = $group_and_urls['threshold'] ?? 0.0;
                    include WP_PLUGIN_DIR . '/webchangedetector/admin/partials/components/forms/threshold-setting.php';
                    ?>
                </div>
            </div>

            <div class="wcd-form-row wcd-auto-update-setting-css">
                <div class="wcd-form-label-wrapper">
                    <label class="wcd-form-label"><?php _e('CSS Settings', 'webchangedetector'); ?></label>
                    <div class="wcd-description"><?php _e('Hide or modify elements via CSS before taking screenshots (e.g. dynamic content).', 'webchangedetector'); ?></div>
                </div>
                <div class="wcd-form-control">
                    <?php
                    // CSS Injection using Accordion Component.
                    $header_text = __('CSS Injection', 'webchangedetector');
                    $accordion_id = 'css-injection-manual';
                    $open = false;

                    // Build content.
                    ?>
                    <div style="margin-top: 10px; width: 100%;">
                        <div class="code-tags default-bg">&lt;style&gt;</div>
                        <textarea name="css" class="codearea wcd-css-textarea" rows="15" cols="80"><?php echo esc_textarea($group_and_urls['css'] ?? ''); ?></textarea>
                        <div class="code-tags default-bg">&lt;/style&gt;</div>
                    </div>
                </div>
            </div>

            <input type="hidden" name="group_name" value="<?php echo esc_html($group_and_urls['name'] ?? ''); ?>">

            <?php submit_button(__('Save Settings', 'webchangedetector'), 'primary', 'submit', true, array('onclick' => 'return wcdValidateFormGroupSettings()')); ?>
        </form>
    </div>


    <script type="text/javascript">
        // Toggle auto update checks settings visibility with slide animation
        jQuery(document).ready(function($) {
            // Listen for changes on the toggle switch
            $(document).on('change', 'input[name="auto_update_checks_enabled"]', function() {
                if ($(this).is(':checked')) {
                    $('.auto-update-setting').slideDown();
                } else {
                    $('.auto-update-setting').slideUp();
                }
            });
        });

        function wcdValidateFormGroupSettings() {
            // Only validate if auto update checks are enabled
            var autoUpdateEnabled = document.querySelector('input[name="auto_update_checks_enabled"]');
            if (autoUpdateEnabled && autoUpdateEnabled.checked) {
                // Validate weekdays using the component's validation function
                if (typeof window['validate_weekdays_auto_update_checks'] === 'function') {
                    if (!window['validate_weekdays_auto_update_checks']()) {
                        return false;
                    }
                }

                // Validate email if present.
                if (typeof window['validate_auto_update_checks_emails'] === 'function') {
                    if (!window['validate_auto_update_checks_emails']()) {
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