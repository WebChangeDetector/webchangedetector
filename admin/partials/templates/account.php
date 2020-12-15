<h2>
    <span class="dashicons dashicons-admin-users"></span>
    Your Account
</h2>
<p id="wcd_account_details"
   data-sc_usage="<?= $comp_usage?>"
   data-sc_limit="<?= $limit ?>"
>
<?php if (! $website_details['enable_limits']) { ?>
    Your plan: <strong><?= $account_details['plan']['name'] ?></strong><br>
<?php } ?>
Used screenshots: <strong><?= $comp_usage ?> / <?= $limit ?></strong><br>
Next renew: <strong><?= gmdate('d/m/Y', $renew_date) ?></strong>
</p>


