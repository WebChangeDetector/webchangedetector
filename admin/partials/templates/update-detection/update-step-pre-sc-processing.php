<?php
/**
 * Manual checks: Pre-update screenshots processing display.
 *
 * Shows real-time progress with status tiles, progress bar,
 * time estimate, and completed screenshots list.
 *
 * @package    WebChangeDetector
 */

$batch_id = get_option( 'wcd_manual_checks_pre_batch' );
if ( ! $batch_id ) {
	$batch_id = get_option( 'wcd_manual_checks_batch' );
}
$started_at = get_option( 'wcd_manual_checks_started_at', '' );
?>

<div id="wcd-currently-in-progress"
	class="wcd-step-container wcd-pre-processing"
	data-batch_id="<?php echo esc_attr( $batch_id ); ?>"
	data-phase="pre"
	data-started_at="<?php echo esc_attr( $started_at ); ?>"
	style="display: <?php echo $batch_id ? 'block' : 'none'; ?>;">

	<div class="wcd-card">
		<div id="currently-processing-container">
			<div class="check-status-grid">
				<!-- Left: Processing info -->
				<div class="check-status-left">
					<div class="processing-content">
						<div id="currently-processing-loader">
							<span class="spinner is-active" style="float: none; margin: 0;"></span>
						</div>
						<div class="processing-text">
							<div id="update-currently-processing">
								<span>Loading...</span>
							</div>
							<p id="update-currently-processing-description"><strong id="processing-title"><?php esc_html_e( 'Screenshots in progress', 'webchangedetector' ); ?></strong></p>
						</div>
					</div>
				</div>

				<!-- Right: Status Tiles -->
				<div class="check-status-right">
					<div id="processing-details">
						<div>
							<div class="status-item queue-status">
								<div class="status-content">
									<div class="status-label"><?php esc_html_e( 'In queue', 'webchangedetector' ); ?></div>
									<div class="status-count" id="queue-open-count">-</div>
								</div>
							</div>
							<div class="status-item processing-status">
								<div class="status-content">
									<div class="status-label"><?php esc_html_e( 'Processing', 'webchangedetector' ); ?></div>
									<div class="status-count" id="queue-processing-count">-</div>
								</div>
							</div>
							<div class="status-item done-status">
								<div class="status-content">
									<div class="status-label"><?php esc_html_e( 'Completed', 'webchangedetector' ); ?></div>
									<div class="status-count" id="queue-done-count">-</div>
								</div>
							</div>
							<div class="status-item failed-status" style="display: none;">
								<div class="status-content">
									<div class="status-label"><?php esc_html_e( 'Failed', 'webchangedetector' ); ?></div>
									<div class="status-count" id="queue-failed-count">-</div>
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>

			<!-- Progress Bar -->
			<div class="wcd-progress-bar-container">
				<div class="wcd-progress-bar">
					<div class="wcd-progress-bar-fill" id="wcd-progress-bar-fill" style="width: 0%;">
						<span class="wcd-progress-text" id="wcd-progress-text">0 / 0</span>
					</div>
				</div>
			</div>

			<!-- Time Estimate -->
			<div class="wcd-time-info">
				<span class="wcd-time-item"><?php esc_html_e( 'Elapsed:', 'webchangedetector' ); ?> <strong id="wcd-elapsed-time">0s</strong></span>
				<span class="wcd-time-item"><?php esc_html_e( 'Remaining:', 'webchangedetector' ); ?> <strong id="wcd-estimated-remaining"><?php esc_html_e( 'Calculating...', 'webchangedetector' ); ?></strong></span>
			</div>
		</div>
	</div>

	<!-- Navigation (shown when processing is done) -->
	<div id="pre-sc-navigation-actions" class="wcd-card" style="display: none; margin-top: 20px; text-align: center; padding: 20px;">
		<h3><?php esc_html_e( 'Time For Updates', 'webchangedetector' ); ?></h3>
		<p>
			<?php
			printf(
				/* translators: %s: link to updates page */
				esc_html__( 'You can leave this page and make %s or other changes on your website. When you are done, come back and continue with the button below.', 'webchangedetector' ),
				'<a href="' . esc_url( admin_url( 'update-core.php' ) ) . '">' . esc_html__( 'Updates', 'webchangedetector' ) . '</a>'
			);
			?>
		</p>
		<form method="post" style="display: inline; margin-right: 10px;">
			<input type="hidden" name="wcd_action" value="update_detection_step">
			<?php wp_nonce_field( 'update_detection_step' ); ?>
			<input type="hidden" name="step" value="settings">
			<input class="button" type="submit" value="< Back to settings" disabled>
		</form>
		<form method="post" style="display: inline;">
			<input type="hidden" name="wcd_action" value="update_detection_step">
			<?php wp_nonce_field( 'update_detection_step' ); ?>
			<input type="hidden" name="step" value="post-update">
			<input class="button button-primary" type="submit" value="Next >" disabled>
		</form>
	</div>

	<!-- Completed Screenshots -->
	<div class="wcd-card" id="pre-sc-completed-container" style="margin-top: 20px;">
		<div class="wcd-card-header">
			<h3><?php esc_html_e( 'Completed Screenshots', 'webchangedetector' ); ?> <span id="pre-sc-completed-count" style="color: #666; font-size: 14px; font-weight: normal;"></span></h3>
		</div>
		<div class="wcd-card-content">
			<div id="pre-sc-completed-table">
				<div id="pre-sc-empty-state" style="text-align: center; padding: 0 20px; color: #666;">
					<span class="dashicons dashicons-clock" style="font-size: 48px; opacity: 0.3; margin-bottom: 15px; display: block;"></span>
					<p style="font-size: 14px;"><?php esc_html_e( 'Screenshots will appear here as they complete.', 'webchangedetector' ); ?></p>
				</div>
			</div>
		</div>
	</div>

	<!-- Screenshot Preview Popup -->
	<div id="pre-sc-screenshot-popup" class="wcd-popup-overlay" style="display: none;">
		<div class="wcd-popup">
			<div class="wcd-popup-inner" style="max-width: 1200px; overflow-y: auto; max-height: 95vh; text-align: left;">
				<button onclick="document.getElementById('pre-sc-screenshot-popup').style.display='none'" class="button wcd-close-popup-button">X</button>
				<h2><?php esc_html_e( 'Pre-Update Screenshot', 'webchangedetector' ); ?></h2>
				<div class="wcd-card" style="margin-bottom: 20px;">
					<div class="wcd-card-content">
						<strong id="pre-sc-popup-title"></strong><br>
						<?php esc_html_e( 'Webpage:', 'webchangedetector' ); ?> <a id="pre-sc-popup-url" href="#" target="_blank"></a>
					</div>
				</div>
				<div id="pre-sc-popup-spinner" style="text-align: center; padding: 40px 0;">
					<span class="spinner is-active" style="float: none;"></span>
				</div>
				<img id="pre-sc-popup-image" src="" alt="Screenshot" style="width: 100%; border: 1px solid #eee; padding: 2px; display: none;" />
			</div>
		</div>
	</div>

	<!-- Failed Queues Popup -->
	<div id="failed-queues-popup" class="wcd-popup-overlay" style="display: none;">
		<div class="wcd-popup">
			<div class="wcd-popup-inner" style="max-width: 800px; overflow-y: auto; max-height: 95vh; text-align: left;">
				<button onclick="document.getElementById('failed-queues-popup').style.display='none'" class="button wcd-close-popup-button">X</button>
				<h2><?php esc_html_e( 'Failed Items', 'webchangedetector' ); ?></h2>
				<div id="failed-queues-content">
					<p style="text-align: center; color: #666;"><?php esc_html_e( 'Loading...', 'webchangedetector' ); ?></p>
				</div>
			</div>
		</div>
	</div>
</div>
