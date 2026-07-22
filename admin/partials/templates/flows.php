<?php
/**
 * Interaction Flows list page.
 *
 * Read-only flow list with two lifecycle toggles (On-Demand Checks and
 * Monitoring). Flows are recorded with the browser extension and managed
 * in the webapp.
 *
 * Expected variables (set by WebChangeDetector_Flows_Controller):
 * - $flows             (array)  Flow rows from the API (lean list shape).
 * - $flows_meta        (array)  Pagination meta from the API response.
 * - $has_flows_feature (bool)   Whether the plan includes interaction flows.
 * - $upgrade_url       (string) Upgrade URL for the upsell notice (empty to hide the link).
 * - $paged             (int)    Current page number.
 *
 * @package    WebChangeDetector
 * @subpackage WebChangeDetector/admin/partials/templates
 * @since      4.6.0
 */

namespace WebChangeDetector;

defined( 'ABSPATH' ) || exit;
?>

<div class="wcd-flows-info">
	<span class="dashicons dashicons-info-outline"></span>
	<p><?php esc_html_e( 'Flows replay recorded interactions (like logins or checkout steps) during your checks. Record new flows with the WebChange Detector browser extension and edit them in the webapp.', 'webchangedetector' ); ?></p>
</div>

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

<?php if ( empty( $flows ) ) : ?>

	<div class="wcd-flows-empty">
		<span class="dashicons dashicons-controls-repeat"></span>
		<h3><?php esc_html_e( 'No flows yet', 'webchangedetector' ); ?></h3>
		<p><?php esc_html_e( 'Record a flow for this website with the WebChange Detector browser extension. It will show up here automatically.', 'webchangedetector' ); ?></p>
	</div>

<?php else : ?>

	<div class="wcd-flows-table-wrap">
		<table class="wcd-flows-table">
			<thead>
				<tr>
					<th><?php esc_html_e( 'Flow', 'webchangedetector' ); ?></th>
					<th><?php esc_html_e( 'Steps', 'webchangedetector' ); ?></th>
					<th><?php esc_html_e( 'Checkpoints', 'webchangedetector' ); ?></th>
					<th><?php esc_html_e( 'Assertions', 'webchangedetector' ); ?></th>
					<th><?php esc_html_e( 'Last Run', 'webchangedetector' ); ?></th>
					<th><?php esc_html_e( 'On-Demand Checks', 'webchangedetector' ); ?></th>
					<th><?php esc_html_e( 'Monitoring', 'webchangedetector' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php
				foreach ( $flows as $flow ) :
					$flow_id   = $flow['id'] ?? '';
					$flow_name = $flow['name'] ?? '';
					$flow_url  = $flow['url'] ?? '';
					$flow_path = $flow_url ? wp_parse_url( $flow_url, PHP_URL_PATH ) : '';
					$flow_path = $flow_path ? $flow_path : '/';

					$last_run        = isset( $flow['last_run'] ) && is_array( $flow['last_run'] ) ? $flow['last_run'] : null;
					$last_run_status = $last_run['status'] ?? '';
					$last_run_when   = $last_run['finished_at'] ?? ( $last_run['created_at'] ?? '' );
					$last_run_ts     = $last_run_when ? strtotime( $last_run_when ) : 0;

					$detail_link = add_query_arg(
						array(
							'page'        => 'webchangedetector-flows',
							'wcd_flow_id' => rawurlencode( $flow_id ),
						),
						admin_url( 'admin.php' )
					);
					?>
				<tr class="wcd-flows-row" data-flow-id="<?php echo esc_attr( $flow_id ); ?>">
					<td class="wcd-flows-cell-name">
						<a href="<?php echo esc_url( $detail_link ); ?>"><strong><?php echo esc_html( $flow_name ); ?></strong></a>
						<span class="wcd-flows-start-path">
							<?php
							/* translators: %s: URL path where the flow starts. */
							echo esc_html( sprintf( __( 'starts at %s', 'webchangedetector' ), $flow_path ) );
							?>
						</span>
					</td>
					<td><?php echo esc_html( intval( $flow['steps_count'] ?? 0 ) ); ?></td>
					<td><?php echo esc_html( intval( $flow['checkpoints_count'] ?? 0 ) ); ?></td>
					<td><?php echo esc_html( intval( $flow['asserts_count'] ?? 0 ) ); ?></td>
					<td class="wcd-flows-cell-lastrun">
						<?php if ( $last_run ) : ?>
							<span class="wcd-status-badge wcd-flow-status-<?php echo esc_attr( sanitize_html_class( $last_run_status, 'unknown' ) ); ?>">
								<?php echo esc_html( ucfirst( $last_run_status ) ); ?>
							</span>
							<?php if ( $last_run_ts ) : ?>
								<span class="wcd-flows-lastrun-when">
									<?php
									/* translators: %s: human-readable time difference. */
									echo esc_html( sprintf( __( '%s ago', 'webchangedetector' ), human_time_diff( $last_run_ts, time() ) ) );
									?>
								</span>
							<?php endif; ?>
						<?php else : ?>
							<span class="wcd-flows-lastrun-when"><?php esc_html_e( 'No runs yet', 'webchangedetector' ); ?></span>
						<?php endif; ?>
					</td>
					<td class="wcd-flows-cell-toggle">
						<span class="wcd-toggle-switch">
							<input
								type="checkbox"
								class="wcd-flow-toggle-input"
								data-flow-id="<?php echo esc_attr( $flow_id ); ?>"
								data-lifecycle="manual"
								<?php checked( ! empty( $flow['enabled_manual'] ) ); ?>
								<?php disabled( ! $has_flows_feature ); ?>
							>
							<span class="wcd-toggle-slider"></span>
						</span>
					</td>
					<td class="wcd-flows-cell-toggle">
						<span class="wcd-toggle-switch">
							<input
								type="checkbox"
								class="wcd-flow-toggle-input"
								data-flow-id="<?php echo esc_attr( $flow_id ); ?>"
								data-lifecycle="monitoring"
								<?php checked( ! empty( $flow['enabled_monitoring'] ) ); ?>
								<?php disabled( ! $has_flows_feature ); ?>
							>
							<span class="wcd-toggle-slider"></span>
						</span>
					</td>
				</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
	</div>

	<p class="wcd-flows-billing-note">
		<?php esc_html_e( 'Enabled flows run with the next check of their type and count towards the checks of your plan.', 'webchangedetector' ); ?>
	</p>

	<?php
	$current_page = intval( $flows_meta['current_page'] ?? $paged );
	$last_page    = intval( $flows_meta['last_page'] ?? 1 );
	if ( $last_page > 1 ) :
		$pagination_base = add_query_arg( array( 'page' => 'webchangedetector-flows' ), admin_url( 'admin.php' ) );
		?>
		<div class="tablenav">
			<div class="tablenav-pages">
				<span class="pagination-links">
					<?php if ( $current_page > 1 ) : ?>
						<a class="prev-page button" href="<?php echo esc_url( add_query_arg( 'paged', $current_page - 1, $pagination_base ) ); ?>">&lsaquo; <?php esc_html_e( 'Previous', 'webchangedetector' ); ?></a>
					<?php endif; ?>
					<span class="tablenav-paging-text">
						<?php
						/* translators: 1: Current page number, 2: Total number of pages. */
						printf( esc_html__( '%1$s of %2$s', 'webchangedetector' ), esc_html( number_format_i18n( $current_page ) ), esc_html( number_format_i18n( $last_page ) ) );
						?>
					</span>
					<?php if ( $current_page < $last_page ) : ?>
						<a class="next-page button" href="<?php echo esc_url( add_query_arg( 'paged', $current_page + 1, $pagination_base ) ); ?>"><?php esc_html_e( 'Next', 'webchangedetector' ); ?> &rsaquo;</a>
					<?php endif; ?>
				</span>
			</div>
		</div>
	<?php endif; ?>

<?php endif; ?>
