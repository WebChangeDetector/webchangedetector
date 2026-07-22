<?php
/**
 * Interaction Flow detail page (read-only).
 *
 * Shows the flow's steps (sensitive values stay masked) and its recent runs.
 * Editing flows happens in the webapp; recording in the browser extension.
 *
 * Expected variables (set by WebChangeDetector_Flows_Controller):
 * - $flow              (array)  Flow detail from the API including redacted `steps`.
 * - $flow_runs         (array)  Recent runs of this flow (lean list shape).
 * - $flow_id           (string) The flow UUID.
 * - $has_flows_feature (bool)   Whether the plan includes interaction flows.
 * - $upgrade_url       (string) Upgrade URL for the upsell notice (empty to hide the link).
 *
 * @package    WebChangeDetector
 * @subpackage WebChangeDetector/admin/partials/templates
 * @since      4.6.0
 */

namespace WebChangeDetector;

defined( 'ABSPATH' ) || exit;

$wcd_flows_list_url = add_query_arg( array( 'page' => 'webchangedetector-flows' ), admin_url( 'admin.php' ) );
$wcd_flow_steps     = isset( $flow['steps'] ) && is_array( $flow['steps'] ) ? $flow['steps'] : array();

$wcd_flow_sc_type_labels = array(
	'pre'  => __( 'Pre-update', 'webchangedetector' ),
	'post' => __( 'Post-update', 'webchangedetector' ),
	'auto' => __( 'Monitoring', 'webchangedetector' ),
);
?>

<p class="wcd-flows-back">
	<a href="<?php echo esc_url( $wcd_flows_list_url ); ?>">&larr; <?php esc_html_e( 'Back to Flows', 'webchangedetector' ); ?></a>
</p>

<h2 class="wcd-flows-headline"><?php echo esc_html( $flow['name'] ?? '' ); ?></h2>
<?php if ( ! empty( $flow['url'] ) ) : ?>
	<p class="wcd-flows-start-path">
		<?php
		/* translators: %s: URL where the flow starts. */
		echo esc_html( sprintf( __( 'Starts at %s', 'webchangedetector' ), $flow['url'] ) );
		?>
	</p>
<?php endif; ?>

<?php if ( ! $has_flows_feature ) : ?>
	<div class="notice notice-warning inline wcd-flows-upsell">
		<p>
			<?php esc_html_e( 'Interaction Flows are not included in your current plan. You can view your flows, but enabling them requires an upgrade.', 'webchangedetector' ); ?>
			<?php if ( ! empty( $upgrade_url ) ) : ?>
				<a href="<?php echo esc_url( $upgrade_url ); ?>" target="_blank"><?php esc_html_e( 'Upgrade Account', 'webchangedetector' ); ?></a>
			<?php endif; ?>
		</p>
	</div>
<?php endif; ?>

<div class="wcd-settings-card">
	<h3><?php esc_html_e( 'Steps', 'webchangedetector' ); ?></h3>
	<p class="wcd-description"><?php esc_html_e( 'Steps are read-only here. Edit this flow in the WebChange Detector webapp.', 'webchangedetector' ); ?></p>

	<?php if ( empty( $wcd_flow_steps ) ) : ?>
		<p><?php esc_html_e( 'This flow has no steps.', 'webchangedetector' ); ?></p>
	<?php else : ?>
		<ol class="wcd-flow-steps">
			<?php
			foreach ( $wcd_flow_steps as $wcd_step ) :
				$wcd_step_type      = $wcd_step['type'] ?? '';
				$wcd_step_label     = $wcd_step['label'] ?? '';
				$wcd_step_selector  = $wcd_step['selector'] ?? '';
				$wcd_step_value     = $wcd_step['value'] ?? null;
				$wcd_step_masked    = ! empty( $wcd_step['sensitive'] ) || ( null === $wcd_step_value && ! empty( $wcd_step['value_set'] ) );
				$wcd_step_assertion = isset( $wcd_step['assertion'] ) && is_array( $wcd_step['assertion'] ) ? $wcd_step['assertion'] : null;
				?>
				<li class="wcd-flow-step">
					<span class="wcd-flow-step-type"><?php echo esc_html( $wcd_step_type ); ?></span>
					<?php if ( $wcd_step_label ) : ?>
						<strong class="wcd-flow-step-label"><?php echo esc_html( $wcd_step_label ); ?></strong>
					<?php endif; ?>
					<?php if ( $wcd_step_selector ) : ?>
						<code class="wcd-flow-step-selector"><?php echo esc_html( $wcd_step_selector ); ?></code>
					<?php endif; ?>
					<?php if ( $wcd_step_masked ) : ?>
						<span class="wcd-flow-step-value wcd-flow-step-value-masked" title="<?php esc_attr_e( 'This value is stored securely and never shown.', 'webchangedetector' ); ?>">&bull;&bull;&bull;&bull;&bull;&bull;&bull;&bull;</span>
					<?php elseif ( null !== $wcd_step_value && '' !== $wcd_step_value ) : ?>
						<span class="wcd-flow-step-value"><?php echo esc_html( $wcd_step_value ); ?></span>
					<?php endif; ?>
					<?php if ( $wcd_step_assertion ) : ?>
						<span class="wcd-flow-step-assertion">
							<?php
							echo esc_html(
								sprintf(
									/* translators: 1: assertion kind, 2: assertion operator, 3: expected value. */
									__( 'Assert: %1$s %2$s %3$s', 'webchangedetector' ),
									$wcd_step_assertion['kind'] ?? '',
									$wcd_step_assertion['operator'] ?? '',
									$wcd_step_assertion['expected'] ?? ''
								)
							);
							?>
						</span>
					<?php endif; ?>
				</li>
			<?php endforeach; ?>
		</ol>
	<?php endif; ?>
</div>

<div class="wcd-settings-card">
	<h3><?php esc_html_e( 'Recent Runs', 'webchangedetector' ); ?></h3>

	<?php if ( empty( $flow_runs ) ) : ?>
		<p><?php esc_html_e( 'This flow has not run yet. It runs with the next check of an enabled type.', 'webchangedetector' ); ?></p>
	<?php else : ?>
		<table class="wcd-flows-table wcd-flow-runs-table">
			<thead>
				<tr>
					<th><?php esc_html_e( 'Type', 'webchangedetector' ); ?></th>
					<th><?php esc_html_e( 'Device', 'webchangedetector' ); ?></th>
					<th><?php esc_html_e( 'Status', 'webchangedetector' ); ?></th>
					<th><?php esc_html_e( 'Finished', 'webchangedetector' ); ?></th>
					<th></th>
				</tr>
			</thead>
			<tbody>
				<?php
				foreach ( $flow_runs as $wcd_run ) :
					$wcd_run_id     = $wcd_run['id'] ?? '';
					$wcd_run_status = $wcd_run['status'] ?? '';
					$wcd_run_when   = $wcd_run['finished_at'] ?? '';
					$wcd_run_ts     = $wcd_run_when ? strtotime( $wcd_run_when ) : 0;

					$wcd_run_link = add_query_arg(
						array(
							'page'        => 'webchangedetector-flows',
							'wcd_flow_id' => rawurlencode( $flow_id ),
							'wcd_run_id'  => rawurlencode( $wcd_run_id ),
						),
						admin_url( 'admin.php' )
					);
					?>
					<tr>
						<td><?php echo esc_html( $wcd_flow_sc_type_labels[ $wcd_run['sc_type'] ?? '' ] ?? ( $wcd_run['sc_type'] ?? '' ) ); ?></td>
						<td><?php echo esc_html( ucfirst( $wcd_run['device'] ?? '' ) ); ?></td>
						<td>
							<span class="wcd-status-badge wcd-flow-status-<?php echo esc_attr( sanitize_html_class( $wcd_run_status, 'unknown' ) ); ?>">
								<?php echo esc_html( ucfirst( $wcd_run_status ) ); ?>
							</span>
						</td>
						<td>
							<?php
							if ( $wcd_run_ts ) {
								/* translators: %s: human-readable time difference. */
								echo esc_html( sprintf( __( '%s ago', 'webchangedetector' ), human_time_diff( $wcd_run_ts, time() ) ) );
							} else {
								echo esc_html( '-' );
							}
							?>
						</td>
						<td>
							<a href="<?php echo esc_url( $wcd_run_link ); ?>" class="button button-small"><?php esc_html_e( 'View Results', 'webchangedetector' ); ?></a>
						</td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
	<?php endif; ?>
</div>
