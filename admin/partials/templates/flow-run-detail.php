<?php
/**
 * Interaction Flow run detail page.
 *
 * Shows per-step results of a single flow run: status, error, duration,
 * assertion results and checkpoint comparisons. Checkpoint comparisons link
 * into the existing change detection view. While the run is processing,
 * the page polls every 10 seconds via the wcd_get_flow_run AJAX action.
 *
 * Expected variables (set by WebChangeDetector_Flows_Controller):
 * - $flow_run            (array)  Run detail from the API including `steps`.
 * - $flow_id             (string) The flow UUID (for the back link, may be empty).
 * - $can_view_detections (bool)   Whether change detection links may be shown.
 *
 * @package    WebChangeDetector
 * @subpackage WebChangeDetector/admin/partials/templates
 * @since      4.6.0
 */

namespace WebChangeDetector;

defined( 'ABSPATH' ) || exit;

$wcd_run_back_url = add_query_arg(
	array_filter(
		array(
			'page'        => 'webchangedetector-flows',
			'wcd_flow_id' => $flow_id ? rawurlencode( $flow_id ) : '',
		)
	),
	admin_url( 'admin.php' )
);

$wcd_run_status = $flow_run['status'] ?? '';
$wcd_run_steps  = isset( $flow_run['steps'] ) && is_array( $flow_run['steps'] ) ? $flow_run['steps'] : array();

$wcd_flow_sc_type_labels = array(
	'pre'  => __( 'Pre-update', 'webchangedetector' ),
	'post' => __( 'Post-update', 'webchangedetector' ),
	'auto' => __( 'Monitoring', 'webchangedetector' ),
);

$wcd_detection_base  = admin_url( 'admin.php?page=webchangedetector-show-detection' );
$wcd_detection_nonce = wp_create_nonce( 'show_change_detection' );

$wcd_run_when = $flow_run['finished_at'] ?? ( $flow_run['created_at'] ?? '' );
$wcd_run_ts   = $wcd_run_when ? strtotime( $wcd_run_when ) : 0;
?>

<p class="wcd-flows-back">
	<a href="<?php echo esc_url( $wcd_run_back_url ); ?>">&larr; <?php echo $flow_id ? esc_html__( 'Back to Flow', 'webchangedetector' ) : esc_html__( 'Back to Flows', 'webchangedetector' ); ?></a>
</p>

<div id="wcd-flow-run-detail" data-run-id="<?php echo esc_attr( $flow_run['id'] ?? '' ); ?>" data-status="<?php echo esc_attr( $wcd_run_status ); ?>">

	<h2 class="wcd-flows-headline"><?php esc_html_e( 'Flow Run Results', 'webchangedetector' ); ?></h2>

	<p class="wcd-flow-run-meta">
		<span class="wcd-status-badge wcd-flow-status-<?php echo esc_attr( sanitize_html_class( $wcd_run_status, 'unknown' ) ); ?> wcd-flow-run-status-badge">
			<?php echo esc_html( ucfirst( $wcd_run_status ) ); ?>
		</span>
		<span><?php echo esc_html( $wcd_flow_sc_type_labels[ $flow_run['sc_type'] ?? '' ] ?? ( $flow_run['sc_type'] ?? '' ) ); ?></span>
		<span><?php echo esc_html( ucfirst( $flow_run['device'] ?? '' ) ); ?></span>
		<?php if ( $wcd_run_ts ) : ?>
			<span>
				<?php
				/* translators: %s: human-readable time difference. */
				echo esc_html( sprintf( __( '%s ago', 'webchangedetector' ), human_time_diff( $wcd_run_ts, time() ) ) );
				?>
			</span>
		<?php endif; ?>
	</p>

	<?php if ( 'processing' === $wcd_run_status ) : ?>
		<div class="notice notice-info inline wcd-flow-run-processing-note">
			<p><?php esc_html_e( 'This run is still processing. Results update automatically every 10 seconds.', 'webchangedetector' ); ?></p>
		</div>
	<?php endif; ?>

	<?php if ( empty( $wcd_run_steps ) ) : ?>
		<p><?php esc_html_e( 'No step results are available for this run yet.', 'webchangedetector' ); ?></p>
	<?php else : ?>
		<ol class="wcd-flow-steps wcd-flow-run-steps">
			<?php
			foreach ( $wcd_run_steps as $wcd_step ) :
				$wcd_step_id        = $wcd_step['id'] ?? '';
				$wcd_step_status    = $wcd_step['status'] ?? '';
				$wcd_step_error     = $wcd_step['error'] ?? '';
				$wcd_step_duration  = $wcd_step['duration_ms'] ?? null;
				$wcd_step_assertion = isset( $wcd_step['assertion'] ) && is_array( $wcd_step['assertion'] ) ? $wcd_step['assertion'] : null;
				$wcd_checkpoint     = isset( $wcd_step['checkpoint'] ) && is_array( $wcd_step['checkpoint'] ) ? $wcd_step['checkpoint'] : null;
				?>
				<li class="wcd-flow-step" data-step-id="<?php echo esc_attr( $wcd_step_id ); ?>">
					<span class="wcd-flow-step-type"><?php echo esc_html( $wcd_step['type'] ?? '' ); ?></span>
					<?php if ( ! empty( $wcd_step['label'] ) ) : ?>
						<strong class="wcd-flow-step-label"><?php echo esc_html( $wcd_step['label'] ); ?></strong>
					<?php endif; ?>

					<span class="wcd-status-badge wcd-flow-step-status wcd-flow-status-<?php echo esc_attr( sanitize_html_class( $wcd_step_status ? $wcd_step_status : 'pending', 'pending' ) ); ?>">
						<?php echo esc_html( $wcd_step_status ? ucfirst( $wcd_step_status ) : __( 'Pending', 'webchangedetector' ) ); ?>
					</span>

					<span class="wcd-flow-step-duration">
						<?php
						if ( null !== $wcd_step_duration && '' !== $wcd_step_duration ) {
							/* translators: %s: duration in milliseconds. */
							echo esc_html( sprintf( __( '%s ms', 'webchangedetector' ), number_format_i18n( intval( $wcd_step_duration ) ) ) );
						}
						?>
					</span>

					<div class="wcd-flow-step-error<?php echo $wcd_step_error ? '' : ' wcd-flows-hidden'; ?>">
						<?php echo esc_html( $wcd_step_error ); ?>
					</div>

					<?php if ( $wcd_step_assertion ) : ?>
						<div class="wcd-flow-step-assertion">
							<?php
							$wcd_assert_passed = $wcd_step_assertion['passed'] ?? null;
							if ( true === $wcd_assert_passed ) {
								echo '<span class="wcd-status-badge wcd-flow-status-done">' . esc_html__( 'Assertion passed', 'webchangedetector' ) . '</span> ';
							} elseif ( false === $wcd_assert_passed ) {
								echo '<span class="wcd-status-badge wcd-flow-status-failed">' . esc_html__( 'Assertion failed', 'webchangedetector' ) . '</span> ';
							}
							echo esc_html(
								sprintf(
									/* translators: 1: assertion kind, 2: assertion operator, 3: expected value. */
									__( 'Assert: %1$s %2$s %3$s', 'webchangedetector' ),
									$wcd_step_assertion['kind'] ?? '',
									$wcd_step_assertion['operator'] ?? '',
									$wcd_step_assertion['expected'] ?? ''
								)
							);
							if ( isset( $wcd_step_assertion['actual'] ) && null !== $wcd_step_assertion['actual'] ) {
								echo ' ';
								/* translators: %s: actual value found by the assertion. */
								echo esc_html( sprintf( __( '(actual: %s)', 'webchangedetector' ), $wcd_step_assertion['actual'] ) );
							}
							?>
						</div>
					<?php endif; ?>

					<?php if ( $wcd_checkpoint ) : ?>
						<div class="wcd-flow-checkpoint">
							<?php
							$wcd_cp_screenshot = isset( $wcd_checkpoint['screenshot'] ) && is_array( $wcd_checkpoint['screenshot'] ) ? $wcd_checkpoint['screenshot'] : null;
							$wcd_cp_comparison = isset( $wcd_checkpoint['comparison'] ) && is_array( $wcd_checkpoint['comparison'] ) ? $wcd_checkpoint['comparison'] : null;
							?>

							<?php if ( $wcd_cp_screenshot && ! empty( $wcd_cp_screenshot['link'] ) ) : ?>
								<a href="<?php echo esc_url( $wcd_cp_screenshot['link'] ); ?>" target="_blank" rel="noopener" class="wcd-flow-checkpoint-screenshot">
									<img src="<?php echo esc_url( $wcd_cp_screenshot['link'] ); ?>" alt="<?php esc_attr_e( 'Checkpoint screenshot', 'webchangedetector' ); ?>">
								</a>
							<?php endif; ?>

							<div class="wcd-flow-checkpoint-result">
								<?php if ( $wcd_cp_comparison ) : ?>
									<?php if ( isset( $wcd_cp_comparison['difference_percent'] ) ) : ?>
										<span class="wcd-flow-checkpoint-diff">
											<?php
											/* translators: %s: visual difference percentage. */
											echo esc_html( sprintf( __( '%s%% difference', 'webchangedetector' ), number_format_i18n( floatval( $wcd_cp_comparison['difference_percent'] ), 2 ) ) );
											?>
										</span>
									<?php endif; ?>
									<?php if ( $can_view_detections && ! empty( $wcd_cp_comparison['id'] ) ) : ?>
										<?php
										$wcd_detection_link = add_query_arg(
											array(
												'id'       => rawurlencode( $wcd_cp_comparison['id'] ),
												'_wpnonce' => $wcd_detection_nonce,
											),
											$wcd_detection_base
										);
										?>
										<a href="<?php echo esc_url( $wcd_detection_link ); ?>" class="button button-small"><?php esc_html_e( 'View Change Detection', 'webchangedetector' ); ?></a>
									<?php endif; ?>
								<?php elseif ( ! empty( $wcd_checkpoint['baseline'] ) ) : ?>
									<span class="wcd-status-badge wcd-flow-status-baseline"><?php esc_html_e( 'Baseline', 'webchangedetector' ); ?></span>
									<span class="wcd-flow-checkpoint-note"><?php esc_html_e( 'First capture of this checkpoint. Future runs compare against it.', 'webchangedetector' ); ?></span>
								<?php endif; ?>
							</div>
						</div>
					<?php endif; ?>
				</li>
			<?php endforeach; ?>
		</ol>
	<?php endif; ?>
</div>
