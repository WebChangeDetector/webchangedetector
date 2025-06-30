<?php require 'help-headline.php'; ?>

<div class="wcd-modern-dashboard">
	<div class="wcd-card">
		<div class="wcd-card-header">
			<h2>
				<span class="dashicons dashicons-list-view" style="color: #266ECC;"></span>
				Logs & Status Overview
			</h2>
		</div>
		<div class="wcd-card-content">
			<p>Here you can see the status of all your requested change detections and performed screenshots.</p>
		</div>
	</div>

	<div class="wcd-card">
		<div class="wcd-card-header">
			<h3>
				<span class="dashicons dashicons-info-outline" style="color: #266ECC;"></span>
				Status Types
			</h3>
		</div>
		<div class="wcd-card-content">
			<div class="wcd-status-grid">
				<div class="wcd-status-item">
					<div class="wcd-status-badge wcd-status-open">
						<span class="dashicons dashicons-clock"></span>
						Open
					</div>
					<div class="wcd-status-description">
						The change detection for this webpage is waiting in the queue.
					</div>
				</div>

				<div class="wcd-status-item">
					<div class="wcd-status-badge wcd-status-processing">
						<span class="dashicons dashicons-update"></span>
						Processing
					</div>
					<div class="wcd-status-description">
						The change detection is currently processing.
					</div>
				</div>

				<div class="wcd-status-item">
					<div class="wcd-status-badge wcd-status-done">
						<span class="dashicons dashicons-yes-alt"></span>
						Done
					</div>
					<div class="wcd-status-description">
						The change detection is done and can be viewed in Show Change Detections.
					</div>
				</div>

				<div class="wcd-status-item">
					<div class="wcd-status-badge wcd-status-failed">
						<span class="dashicons dashicons-warning"></span>
						Failed
					</div>
					<div class="wcd-status-description">
						The change detection failed. Please contact us for more details.
					</div>
				</div>
			</div>
		</div>
	</div>

	<div class="wcd-card">
		<div class="wcd-card-header">
			<h3>
				<span class="dashicons dashicons-camera-alt" style="color: #266ECC;"></span>
				Detection Types
			</h3>
		</div>
		<div class="wcd-card-content">
			<div class="wcd-type-grid">
				<div class="wcd-type-item">
					<div class="wcd-type-icon">
						<span class="dashicons dashicons-format-image"></span>
					</div>
					<div class="wcd-type-content">
						<h4>Reference Screenshot</h4>
						<p>This screenshot is made as a reference for future change detections.</p>
					</div>
				</div>

				<div class="wcd-type-item">
					<div class="wcd-type-icon">
						<span class="dashicons dashicons-image-flip-horizontal"></span>
					</div>
					<div class="wcd-type-content">
						<h4>Compare Screenshot</h4>
						<p>This screenshot is made to identify changes after updates.</p>
					</div>
				</div>

				<div class="wcd-type-item">
					<div class="wcd-type-icon">
						<span class="dashicons dashicons-visibility"></span>
					</div>
					<div class="wcd-type-content">
						<h4>Monitoring</h4>
						<p>This screenshot is made from monitoring.</p>
					</div>
				</div>

				<div class="wcd-type-item">
					<div class="wcd-type-icon">
						<span class="dashicons dashicons-image-filter"></span>
					</div>
					<div class="wcd-type-content">
						<h4>Change Detection</h4>
						<p>This is the comparison between the Reference- and Compare screenshot. The change detection is started automatically after every Compare Screenshot and every Monitoring.</p>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>

<?php require 'help-contact-form.php'; ?>