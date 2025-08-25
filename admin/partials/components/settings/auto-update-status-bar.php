<?php
/**
 * Auto Update Status Bar Component
 * Shows when the next auto updates are scheduled to run
 *
 * @package webchangedetector
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Get auto update settings.
$auto_update_settings       = $this->admin->website_details['auto_update_settings'] ?? array();
$auto_update_checks_enabled = ! empty( $auto_update_settings['auto_update_checks_enabled'] ) &&
	( true === $auto_update_settings['auto_update_checks_enabled'] ||
	'1' === $auto_update_settings['auto_update_checks_enabled'] ||
	1 === $auto_update_settings['auto_update_checks_enabled'] );

// Check if there are selected URLs.
$selected_urls_count = $group_and_urls['selected_urls_count'] ?? 0;

// Get the next scheduled auto-update time.
$next_auto_update = wp_next_scheduled( 'wp_version_check' );

// Check enabled weekdays.
$enabled_weekdays = array();
$weekdays         = array( 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday' );
foreach ( $weekdays as $weekday ) {
	if ( ! empty( $auto_update_settings[ 'auto_update_checks_' . $weekday ] ) ) {
		$enabled_weekdays[] = ucfirst( $weekday );
	}
}

// Get timeframe settings (these are in UTC from API).
require_once plugin_dir_path( __FILE__ ) . '../../../class-webchangedetector-timezone-helper.php';
$utc_from_time = $auto_update_settings['auto_update_checks_from'] ?? '00:00';
$utc_to_time   = $auto_update_settings['auto_update_checks_to'] ?? '23:59';

// Convert to site timezone for display.
$site_from_time = \WebChangeDetector\WebChangeDetector_Timezone_Helper::utc_to_site_time( $utc_from_time );
$site_to_time   = \WebChangeDetector\WebChangeDetector_Timezone_Helper::utc_to_site_time( $utc_to_time );

// Check if there's an auto-update currently running.
$auto_updates_running = get_option( 'wcd_auto_updates_running' );
$pre_update_data      = get_option( 'wcd_pre_auto_update' );

// Determine status.
$status_class    = '';
$status_icon     = '';
$status_title    = '';
$status_message  = '';
$next_check_time = '';
$next_check_date = '';

if ( $auto_update_checks_enabled && $selected_urls_count > 0 ) {
	if ( $auto_updates_running || $pre_update_data ) {
		// Auto-updates currently running.
		$status_class    = 'wcd-status-running';
		$status_icon     = 'update spin';
		$status_title    = __( 'Auto-Update Checks', 'webchangedetector' );
		$status_message  = __( 'Checks in progress', 'webchangedetector' );
		$next_check_time = __( 'Running now...', 'webchangedetector' );
	} elseif ( $next_auto_update ) {
		// Auto-updates scheduled.
		$status_class   = 'wcd-status-scheduled';
		$status_icon    = 'clock';
		$status_title   = __( 'Auto-Update Checks', 'webchangedetector' );
		$status_message = __( 'Next check in', 'webchangedetector' );

		// Calculate time until next run.
		// Use time() instead of current_time('timestamp') to avoid discouraged usage and get the current Unix timestamp.
		$time_until      = human_time_diff( time(), $next_auto_update );
		$next_check_time = $time_until;
		$next_check_date = wp_date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $next_auto_update );
	} else {
		// No auto-updates scheduled.
		$status_class    = 'wcd-status-inactive';
		$status_icon     = 'warning';
		$status_title    = __( 'Auto-Update Checks', 'webchangedetector' );
		$status_message  = __( 'Not scheduled', 'webchangedetector' );
		$next_check_time = __( 'WordPress auto-updates disabled', 'webchangedetector' );
	}
} elseif ( ! $auto_update_checks_enabled ) {
	// Auto-update checks disabled.
	$status_class    = 'wcd-status-disabled';
	$status_icon     = 'dismiss';
	$status_title    = __( 'Auto-Update Checks', 'webchangedetector' );
	$status_message  = __( 'Disabled', 'webchangedetector' );
	$next_check_time = __( 'Enable in settings below', 'webchangedetector' );
} else {
	// No URLs selected.
	$status_class    = 'wcd-status-no-urls';
	$status_icon     = 'info';
	$status_title    = __( 'Auto-Update Checks', 'webchangedetector' );
	$status_message  = __( 'No URLs selected', 'webchangedetector' );
	$next_check_time = __( 'Select URLs below', 'webchangedetector' );
}

?>
<div class="wcd-settings-card wcd-monitoring-status-card <?php echo esc_attr( $status_class ); ?>">
	<div class="wcd-monitoring-status-header">
		<h3><span class="dashicons dashicons-<?php echo esc_attr( $status_icon ); ?>"></span> <?php echo esc_html( $status_title ); ?></h3>
	</div>
	<div class="wcd-monitoring-status-content">
		<div class="wcd-next-check-container">
			<div class="wcd-status-label"><?php echo esc_html( $status_message ); ?></div>
			<div class="wcd-status-value"><?php echo esc_html( $next_check_time ); ?></div>
			<?php if ( $next_check_date ) : ?>
				<div class="wcd-status-date"><?php echo esc_html( $next_check_date ); ?></div>
			<?php endif; ?>
		</div>
		<?php if ( $auto_update_checks_enabled && $selected_urls_count > 0 ) : ?>
		<div class="wcd-monitoring-stats">
			<div class="wcd-stat-item">
				<span class="wcd-stat-label"><?php esc_html_e( 'Selected URLs', 'webchangedetector' ); ?></span>
				<span class="wcd-stat-value"><?php echo esc_html( $selected_urls_count ); ?></span>
			</div>
			<div class="wcd-stat-item">
				<span class="wcd-stat-label"><?php esc_html_e( 'Active Days', 'webchangedetector' ); ?></span>
				<span class="wcd-stat-value"><?php echo count( $enabled_weekdays ); ?>/7</span>
			</div>
			<div class="wcd-stat-item">
				<span class="wcd-stat-label"><?php esc_html_e( 'Time Window', 'webchangedetector' ); ?></span>
				<span class="wcd-stat-value"><?php echo esc_html( $site_from_time . ' - ' . $site_to_time ); ?></span>
			</div>
		</div>
		<?php endif; ?>
	</div>
</div>