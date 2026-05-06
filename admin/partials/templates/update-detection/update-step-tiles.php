<?php
/**
 * Manual checks - step tiles
 *
 *   @package    webchangedetector
 */

?>
<div class="wcd-workflow-steps">
	<div class="wcd-step-item wcd-step-<?php echo esc_attr( $progress_setting ); ?>">
		<div class="wcd-step-icon">
			<?php if ( 'done' === $progress_setting ) : ?>
				<span class="dashicons dashicons-yes-alt"></span>
			<?php elseif ( 'active' === $progress_setting ) : ?>
				<span class="dashicons dashicons-controls-play"></span>
			<?php else : ?>
				<span class="wcd-step-number">1</span>
			<?php endif; ?>
		</div>
		<div class="wcd-step-content">
			<h3><?php esc_html_e( 'Settings', 'webchangedetector' ); ?></h3>
		</div>
	</div>

	<div class="wcd-step-connector wcd-connector-<?php echo esc_attr( 'done' === $progress_setting ? 'active' : 'inactive' ); ?>"></div>

	<div class="wcd-step-item wcd-step-<?php echo esc_attr( $progress_pre ); ?>">
		<div class="wcd-step-icon">
			<?php if ( 'done' === $progress_pre ) : ?>
				<span class="dashicons dashicons-yes-alt"></span>
			<?php elseif ( 'active' === $progress_pre ) : ?>
				<span class="dashicons dashicons-controls-play"></span>
			<?php else : ?>
				<span class="wcd-step-number">2</span>
			<?php endif; ?>
		</div>
		<div class="wcd-step-content">
			<h3><?php esc_html_e( 'Pre-Update', 'webchangedetector' ); ?></h3>
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
				<span class="wcd-step-number">3</span>
			<?php endif; ?>
		</div>
		<div class="wcd-step-content">
			<h3><?php esc_html_e( 'Post-Update', 'webchangedetector' ); ?></h3>
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
				<span class="wcd-step-number">4</span>
			<?php endif; ?>
		</div>
		<div class="wcd-step-content">
			<h3><?php esc_html_e( 'Checks', 'webchangedetector' ); ?></h3>
		</div>
	</div>
</div>
