<?php
/**
 * Account overview
 *
 *   @package    webchangedetector
 */

?>
<form method="post" action="?page=webchangedetector">
	<input type="hidden" name="wcd_action" value="enable_wizard">
	<?php wp_nonce_field( 'enable_wizard' ); ?>
	<input type="submit" class="button" value="Start Wizard">
</form>
<h2>
	<span class="dashicons dashicons-admin-users"></span>
	Your Account
</h2>
<p id="wcd_account_details"
	data-sc_usage="<?php echo esc_html( $comp_usage ); ?>"
	data-sc_limit="<?php echo esc_html( $limit ); ?>"
>
<?php if ( ! $wcd->website_details['enable_limits'] ) { ?>
	Your plan: <strong><?php echo esc_html( $account_details['plan_name'] ); ?></strong><br>
<?php } ?>
Used checks: <strong><?php echo esc_html( $comp_usage ); ?> / <?php echo esc_html( $limit ); ?></strong><br>
Next renew: <strong><?php echo esc_html( gmdate( 'd/m/Y', $renew_date ) ); ?></strong>
</p>


