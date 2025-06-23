<?php
/**
 * Manual checks - pre update sc
 *
 *   @package    webchangedetector
 */

/**
 * Include manual check tiles
 */
require 'update-step-tiles.php';
$group_urls = \WebChangeDetector\WebChangeDetector_API_V2::get_group_urls_v2( $wcd->manual_group_uuid );
?>

<!-- Pre Update -->
<div class="wcd-step-container wcd-section">
	<div class="wcd-highlight-bg done">
		<h2>
			<?php \WebChangeDetector\WebChangeDetector_Admin_Utils::get_device_icon( 'check', 'screenshots-done-icon' ); ?>
			<strong><?php echo esc_html( $group_urls['meta']['selected_urls_count'] ); ?></strong> URL(s) selected
		</h2>
	</div>

	<?php
	$insufficient_screenshots = false;
	if ( $group_urls['meta']['selected_urls_count'] > $account_details['checks_left'] ) {
		$insufficient_screenshots = true;
	}

	$disabled = $insufficient_screenshots ? 'disabled' : '';
	?>
		<div class="wcd-highlight-bg">
			<h2>Pre-Update Screenshots</h2>
			<p>Start the Manual Checks by taking screenshots before making updates or other changes on your website.</p>
			<form id="frm-take-pre-sc" action="<?php echo esc_url( admin_url() . WCD_TAB_UPDATE ); ?>" method="post">
				<input type="hidden" value="take_screenshots" name="wcd_action">
				<?php wp_nonce_field( 'take_screenshots' ); ?>
				<input type="hidden" name="sc_type" value="pre">
				<button type="submit" class="button-primary" <?php echo esc_html( $disabled ); ?> >
					Take Pre-Update Screenshots
				</button>
				<?php if ( $insufficient_screenshots ) { ?>
					<p style="color: #A00000; font-weight: 500;">
						Sorry, you don't have enough screenshots available.<br>
						Please upgrade your account or select fewer URLs.
					</p>
					<?php if ( ! $account_details['is_subaccount'] ) { ?>
						<a href="<?php echo esc_url( $wcd->account_handler->get_upgrade_url() ); ?>" class="button button-primary">Upgrade</a>
					<?php } ?>
				<?php } ?>
			</form>
		</div>

	<?php
	require 'update-step-cancel.php';
	?>

</div>