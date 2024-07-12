<h2>
	<span class="dashicons dashicons-admin-users"></span>
	Your Account
</h2>
<p id="wcd_account_details"
	data-sc_usage="<?php echo $comp_usage; ?>"
	data-sc_limit="<?php echo $limit; ?>"
>
<?php if ( ! $wcd->website_details['enable_limits'] ) { ?>
	Your plan: <strong><?php echo $account_details['plan']['name']; ?></strong><br>
<?php } ?>
Used screenshots: <strong><?php echo $comp_usage; ?> / <?php echo $limit; ?></strong><br>
Next renew: <strong><?php echo gmdate( 'd/m/Y', $renew_date ); ?></strong>
</p>


