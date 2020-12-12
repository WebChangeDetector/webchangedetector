<form class="wcd-frm-settings" action="<?= admin_url() ?>/admin.php?page=webchangedetector&tab=auto-settings" method="post" onsubmit="return mmValidateForm()">
    <p>
        <input type="hidden" name="wcd_action" value="update_monitoring_settings">
        <input type="hidden" name="monitoring" value="1">
        <input type="hidden" name="group_name" value="<?= $groups_and_urls['name'] ?>">

        <label for="enabled">Enabled</label>
        <select name="enabled" id="auto-enabled">
            <option value="1" <?= isset($groups_and_urls['enabled']) && $groups_and_urls['enabled'] == '1' ? 'selected' : ''; ?>>
                Yes
            </option>
            <option value="0" <?= isset($groups_and_urls['enabled']) && $groups_and_urls['enabled'] == '0' ? 'selected' : ''; ?>>
                No
            </option>
        </select>
    </p>
    <p class="auto-setting">
        <label for="hour_of_day" class="auto-setting">Hour of the day</label>
        <select name="hour_of_day" class="auto-setting">
            <?php
            for ($i = 0; $i < MM_WCD_HOURS_IN_DAY; $i++) {
                if (isset($groups_and_urls['hour_of_day']) && $groups_and_urls['hour_of_day'] == $i) {
                    $selected = 'selected';
                } else {
                    $selected = '';
                }
                echo '<option class="select-time" value="' . $i . '" ' . $selected . '>' . $i . ':00</option>';
            }
            ?>
        </select>
    </p>
    <p class="auto-setting">
        <label for="interval_in_h" class="auto-setting">Interval in hours</label>
        <select name="interval_in_h" class="auto-setting">
            <option value="1" <?= isset($groups_and_urls['interval_in_h']) && $groups_and_urls['interval_in_h'] == 1 ? 'selected' : ''; ?>>
                Every 1 hour
            </option>
            <option value="3" <?= isset($groups_and_urls['interval_in_h']) && $groups_and_urls['interval_in_h'] == 3 ? 'selected' : ''; ?>>
                Every 3 hours
            </option>
            <option value="6" <?= isset($groups_and_urls['interval_in_h']) && $groups_and_urls['interval_in_h'] == 6 ? 'selected' : ''; ?>>
                Every 6 hours
            </option>
            <option value="12" <?= isset($groups_and_urls['interval_in_h']) && $groups_and_urls['interval_in_h'] == 12 ? 'selected' : ''; ?>>
                Every 12 hours
            </option>
            <option value="24" <?= isset($groups_and_urls['interval_in_h']) && $groups_and_urls['interval_in_h'] == 24 ? 'selected' : ''; ?>>
                Every 24 hours
            </option>
        </select>
    </p>
    <p class="auto-setting">
        <label for="alert_emails" class="auto-setting">
            Alert email addresses
        </label>
        <input type="text" name="alert_emails" id="alert_emails" style="width: 500px;" class="auto-setting"
               value="<?= isset($groups_and_urls['alert_emails']) ? esc_attr(implode(',', $groups_and_urls['alert_emails'])) : '' ?>">
        <br>
        <label for="alert_emails" class="auto-setting">
            (Separate more email addresses with ",")
        </label>
    </p>
    <input type="submit" class="button" value="Save Settings">
</form>