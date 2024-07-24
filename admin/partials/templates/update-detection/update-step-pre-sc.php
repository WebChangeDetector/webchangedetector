<?php require 'update-step-tiles.php'; ?>

<!-- Pre Update -->
<div class="wcd-step-container">
	<div class="wcd-highlight-bg done">
		<h2>
			<?php echo wp_kses( $wcd->get_device_icon( 'check', 'screenshots-done-icon' ), array( 'span' => array( 'class' => array() ) ) ); ?>
			<strong><?php echo esc_html( $groups_and_urls['amount_selected_urls'] ); ?></strong> URL(s) selected
		</h2>
	</div>

	<?php
	$insufficient_screenshots = false;
	if ( $groups_and_urls['amount_selected_urls'] * 2 > $account_details['available_compares'] ) {
		$insufficient_screenshots = true;
	}

	$disabled = $insufficient_screenshots ? 'disabled' : '';
	?>
		<div class="wcd-highlight-bg">
			<h2>Pre-Update Screenshots</h2>
			<p>Start the Manual Checks by taking screenshots before making updates or other changes on your website.</p>
			<form id="frm-take-pre-sc" action="<?php echo admin_url() . WCD_TAB_UPDATE; ?>" method="post">
				<input type="hidden" value="take_screenshots" name="wcd_action">
				<input type="hidden" name="sc_type" value="pre">
				<button type="submit" class="button-primary" <?php echo $disabled; ?> >
					Take Pre-Update Screenshots
				</button>
				<?php if ( $insufficient_screenshots ) { ?>
				<p style="color: #A00000; font-weight: 500;">
					Sorry, you don't have enough screenshots available.<br>
					Please upgrade your account or select less URLs.
				</p>
					<a href="<?php echo $wcd->get_upgrade_url(); ?>" class="button button-primary">Upgrade</a>
				<?php } ?>
			</form>
		</div>

	<?php
	require 'update-step-cancel.php';
	?>

</div>