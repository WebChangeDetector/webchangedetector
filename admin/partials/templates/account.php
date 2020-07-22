<h2>
    <span class="dashicons dashicons-admin-users"></span>
    Your Account
    <a class="button account"
        href="<?= WCD_APP_DOMAIN . '/account/upgrade/?type=package&id=' . $account_details['whmcs_service_id'] ?>"
        target="_blank">
        Upgrade
    </a>
</h2>
Your plan: <strong><?= $account_details['plan']['name'] ?></strong><br>
Used credits: <strong><?= $comp_usage ?> / <?= $limit ?></strong><br>
Next renew: <strong><?= date('d/m/Y', $renew_date) ?></strong>
<p>


