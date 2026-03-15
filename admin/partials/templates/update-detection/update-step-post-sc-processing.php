<?php
/**
 * Manual checks: Post-update screenshots processing display.
 *
 * Shows real-time progress with status tiles, progress bar,
 * time estimate, and live change detections table.
 *
 * @package    WebChangeDetector
 */

$batch_id   = get_option( 'wcd_manual_checks_post_batch' ) ?: get_option( 'wcd_manual_checks_batch' );
$started_at = get_option( 'wcd_manual_checks_started_at', '' );
?>

<div id="wcd-currently-in-progress"
	class="wcd-step-container wcd-post-processing"
	data-batch_id="<?php echo esc_attr( $batch_id ); ?>"
	data-phase="post"
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
							<p id="update-currently-processing-description"><strong id="processing-title"><?php esc_html_e( 'Checks in progress', 'webchangedetector' ); ?></strong></p>
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

	<!-- Navigation Buttons -->
	<div id="change-detection-actions" style="text-align: center; margin-top: 20px;">
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
			<input class="button" type="submit" value="Re-run checks" disabled>
		</form>
	</div>

	<!-- Change Detections -->
	<div class="wcd-card" id="change-detections-container" style="margin-top: 20px;">
		<div class="wcd-card-header">
			<h3><?php esc_html_e( 'Change Detections', 'webchangedetector' ); ?></h3>
		</div>
		<div class="wcd-card-content">
			<!-- Filter Toggle -->
			<div style="max-width: 500px; margin: 0 auto 20px; padding: 15px; background: #f8f9fa; border-radius: 5px; border: 1px solid #e0e0e0; text-align: center;">
				<div class="comparison-filter-toggle">
					<span class="toggle-label"><?php esc_html_e( 'Only with changes', 'webchangedetector' ); ?></span>
					<label class="switch">
						<input type="checkbox" id="show-all-comparisons-post" class="wcd-comparison-filter" />
						<span class="slider round"></span>
					</label>
					<span class="toggle-label"><?php esc_html_e( 'All comparisons', 'webchangedetector' ); ?></span>
				</div>
				<span id="detections-count" style="display: block; margin-top: 12px; color: #666; font-size: 13px;"></span>
			</div>

			<!-- Table -->
			<div id="change-detections-table">
				<div id="empty-state" style="text-align: center; padding: 30px 20px; color: #666;">
					<span class="dashicons dashicons-clock" style="font-size: 48px; width: 48px; height: 48px; opacity: 0.3; margin: 0 auto 15px; display: block;"></span>
					<p style="font-size: 14px; font-weight: 500; margin-bottom: 5px;"><?php esc_html_e( 'No changes detected yet.', 'webchangedetector' ); ?></p>
					<p style="font-size: 13px; color: #999;"><?php esc_html_e( 'Change detections will appear here as comparisons complete.', 'webchangedetector' ); ?></p>
				</div>
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
