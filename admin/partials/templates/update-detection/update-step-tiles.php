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
			<?php if ( 'done' === $progress_pre ) : ?>
				<span class="dashicons dashicons-yes-alt"></span>
			<?php elseif ( 'active' === $progress_pre ) : ?>
				<span class="dashicons dashicons-controls-play"></span>
			<?php else : ?>
				<span class="wcd-step-number">1</span>
			<?php endif; ?>
		</div>
		<div class="wcd-step-content">
			<h3>Pre-Update Screenshots</h3>
			<p>Take screenshots before making changes</p>
		</div>
	</div>
	
	<div class="wcd-step-connector wcd-connector-<?php echo esc_attr( 'done' === $progress_pre ? 'active' : 'inactive' ); ?>"></div>
	
	<div class="wcd-step-item wcd-step-<?php echo esc_attr( $progress_post ); ?>">
		<div class="wcd-step-icon">
			<?php if ( 'done' === $progress_post ) : ?>
				<span class="dashicons dashicons-yes-alt"></span>
			<?php elseif ( 'active' === $progress_post ) : ?>
				<span class="dashicons dashicons-controls-play"></span>
			<?php else : ?>
				<span class="wcd-step-number">2</span>
			<?php endif; ?>
		</div>
		<div class="wcd-step-content">
			<h3>Post-Update Screenshots</h3>
			<p>Take screenshots after making changes</p>
		</div>
	</div>
	
	<div class="wcd-step-connector wcd-connector-<?php echo esc_attr( 'done' === $progress_post ? 'active' : 'inactive' ); ?>"></div>
	
	<div class="wcd-step-item wcd-step-<?php echo esc_attr( $progress_change_detection ); ?>">
		<div class="wcd-step-icon">
			<?php if ( 'done' === $progress_change_detection ) : ?>
				<span class="dashicons dashicons-yes-alt"></span>
			<?php elseif ( 'active' === $progress_change_detection ) : ?>
				<span class="dashicons dashicons-controls-play"></span>
			<?php else : ?>
				<span class="wcd-step-number">3</span>
			<?php endif; ?>
		</div>
		<div class="wcd-step-content">
			<h3>Change Detections</h3>
			<p>Review detected changes and differences</p>
		</div>
	</div>
</div>