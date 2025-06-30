<div id="plan_limitation_popup" class="ajax-popup" style="display: none; z-index: 20;">
	<div class="popup">
		<div class="popup-inner" style="text-align: center;">

			<h2 style="margin-bottom: 20px;">Your Account Needs An Upgrade</h2>
			<div class="plan-container">
				<div class="plan-box free">
					<h3>Current Limits</h3>
					<ul>
						<?php if ( $client_account['plan'] === WCD_FREE_PLAN ) { ?>
							<li><?php echo get_device_icon( 'check', 'plan-check' ); ?>Track 1 webpage</li>
							<li><?php echo get_device_icon( 'check', 'plan-check' ); ?>Interval every 24h</li>
						<?php } ?>
						<li><?php echo get_device_icon( 'check', 'plan-check' ); ?>
							<?php echo $client_account['checks_limit']; ?>
							checks / month</li>
					</ul>

					<a class="et_pb_button" onclick="closePlanLimitationPopup()">Keep current plan</a>
				</div>

				<div class="plan-box upgrade">
					<h3>Upgrade Benefits</h3>
					<ul>
						<?php if ( $client_account['plan'] === WCD_FREE_PLAN ) { ?>
							<li><?php echo get_device_icon( 'check', 'plan-check green' ); ?>Track up to 333 webpages</li>
							<li><?php echo get_device_icon( 'check', 'plan-check green' ); ?>Intervals from 1h to 24h</li>
						<?php } ?>
						<li><?php echo get_device_icon( 'check', 'plan-check green' ); ?>Up to 33,000 checks / month</li>
					</ul>

					<a style="margin-right: 10px;"
						href="<?php echo get_upgrade_url(); ?>" class="et_pb_button green-button">
						Upgrade Account
					</a><br>
					<strong>Plans starting from $7 per month.</strong>
				</div>
			</div>
		</div>
	</div>
</div>