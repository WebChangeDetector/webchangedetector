<?php
/**
 * Manual checks - step tiles
 *
 *   @package    webchangedetector
 */

?>
<div class="wcd-workflow-steps">
	<div class="wcd-step-item wcd-step-<?php echo esc_attr( $progress_pre ); ?>">
		<div class="wcd-step-icon">
			<?php if ( $progress_pre === 'done' ): ?>
				<span class="dashicons dashicons-yes-alt"></span>
			<?php elseif ( $progress_pre === 'active' ): ?>
				<span class="dashicons dashicons-controls-play"></span>
			<?php else: ?>
				<span class="wcd-step-number">1</span>
			<?php endif; ?>
		</div>
		<div class="wcd-step-content">
			<h3>Pre-Update Screenshots</h3>
			<p>Take screenshots before making changes</p>
		</div>
	</div>
	
	<div class="wcd-step-connector wcd-connector-<?php echo esc_attr( $progress_pre === 'done' ? 'active' : 'inactive' ); ?>"></div>
	
	<div class="wcd-step-item wcd-step-<?php echo esc_attr( $progress_post ); ?>">
		<div class="wcd-step-icon">
			<?php if ( $progress_post === 'done' ): ?>
				<span class="dashicons dashicons-yes-alt"></span>
			<?php elseif ( $progress_post === 'active' ): ?>
				<span class="dashicons dashicons-controls-play"></span>
			<?php else: ?>
				<span class="wcd-step-number">2</span>
			<?php endif; ?>
		</div>
		<div class="wcd-step-content">
			<h3>Post-Update Screenshots</h3>
			<p>Take screenshots after making changes</p>
		</div>
	</div>
	
	<div class="wcd-step-connector wcd-connector-<?php echo esc_attr( $progress_post === 'done' ? 'active' : 'inactive' ); ?>"></div>
	
	<div class="wcd-step-item wcd-step-<?php echo esc_attr( $progress_change_detection ); ?>">
		<div class="wcd-step-icon">
			<?php if ( $progress_change_detection === 'done' ): ?>
				<span class="dashicons dashicons-yes-alt"></span>
			<?php elseif ( $progress_change_detection === 'active' ): ?>
				<span class="dashicons dashicons-controls-play"></span>
			<?php else: ?>
				<span class="wcd-step-number">3</span>
			<?php endif; ?>
		</div>
		<div class="wcd-step-content">
			<h3>Change Detections</h3>
			<p>Review detected changes and differences</p>
		</div>
	</div>
</div>