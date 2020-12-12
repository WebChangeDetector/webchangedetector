<h2>
    <span class="dashicons dashicons-admin-users"></span>
    Your Account
    <?php if (! $website_details['enable_limits']) { ?>
        <a class="button account"
            href="<?= $wcd->app_url() . '/upgrade/?id=' . $account_details['whmcs_service_id'] ?>"
            target="_blank">
            Upgrade
        </a>
    <?php } ?>
</h2>
<p id="wcd_account_details"
   data-sc_usage="<?= $comp_usage?>"
   data-sc_limit="<?= $limit ?>"
>
<?php if (! $website_details['enable_limits']) { ?>
    Your plan: <strong><?= $account_details['plan']['name'] ?></strong><br>
<?php } ?>
Used credits: <strong><?= $comp_usage ?> / <?= $limit ?></strong><br>
Next renew: <strong><?= date('d/m/Y', $renew_date) ?></strong>
</p>


