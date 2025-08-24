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
<div class="wcd-step-container">
	<div class="wcd-settings-section">
		<div class="wcd-settings-card wcd-success-card">
			<div class="wcd-form-row">
				<div class="wcd-form-label-wrapper">
					<label class="wcd-form-label">
						<span class="dashicons dashicons-yes-alt"></span> URLs Selected
					</label>
					<div class="wcd-description">URLs have been successfully selected for manual checks.</div>
				</div>
				<div class="wcd-form-control wcd-update-check-urls">
					<div class="wcd-status-info">
						<strong><?php echo esc_html( $group_urls['meta']['selected_urls_count'] ); ?></strong> <?php esc_html_e( 'URLs selected for checking', 'webchangedetector' ); ?>
					</div>
				</div>
			</div>
		</div>
	</div>

	<?php
	$insufficient_screenshots = false;
	if ( $group_urls['meta']['selected_urls_count'] > $account_details['checks_left'] ) {
		$insufficient_screenshots = true;
	}

	$disabled = $insufficient_screenshots ? 'disabled' : '';
	?>

	<div class="wcd-settings-section">
		<div class="wcd-settings-card <?php echo $insufficient_screenshots ? 'wcd-warning-card' : 'wcd-action-card'; ?>">
			<div class="wcd-form-row">
				<div class="wcd-form-label-wrapper">
					<label class="wcd-form-label">
						<span class="dashicons dashicons-camera"></span> Pre-Update Screenshots
					</label>
					<div class="wcd-description">Start the Manual Checks by taking screenshots before making updates or other changes on your website.</div>
				</div>
				<div class="wcd-form-control wcd-update-check-button">
					<?php if ( $insufficient_screenshots ) { ?>
						<div class="wcd-error-message">
							<p><strong>Insufficient Screenshots Available</strong></p>
							<p>Sorry, you don't have enough screenshots available. Please upgrade your account or select fewer URLs.</p>
							<?php if ( ! $account_details['is_subaccount'] ) { ?>
								<a href="<?php echo esc_url( $wcd->account_handler->get_upgrade_url() ); ?>" class="button button-primary">Upgrade Account</a>
							<?php } ?>
						</div>
					<?php } else { ?>
						<form id="frm-take-pre-sc" action="<?php echo esc_url( admin_url() . WCD_TAB_UPDATE ); ?>" method="post">
							<input type="hidden" value="take_screenshots" name="wcd_action">
							<?php wp_nonce_field( 'take_screenshots' ); ?>
							<input type="hidden" name="sc_type" value="pre">
							<button type="submit" class="button button-primary wcd-action-btn">
								<span class="dashicons dashicons-camera"></span> Take Pre-Update Screenshots
							</button>
						</form>
					<?php } ?>
				</div>
			</div>
		</div>
	</div>

	<?php
	require 'update-step-cancel.php';
	?>

</div>