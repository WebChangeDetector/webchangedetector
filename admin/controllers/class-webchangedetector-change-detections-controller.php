<?php
/**
 * Change Detections Controller for WebChangeDetector
 *
 * Handles change detections page requests and logic.
 *
 * @package    WebChangeDetector
 * @subpackage WebChangeDetector/admin/controllers
 * @author     Mike Miler <mike@wp-mike.com>
 */

namespace WebChangeDetector;

/**
 * Change Detections Controller Class.
 */
class WebChangeDetector_Change_Detections_Controller {


	/**
	 * The admin instance.
	 *
	 * @var WebChangeDetector_Admin
	 */
	private $admin;

	/**
	 * Constructor.
	 *
	 * @param WebChangeDetector_Admin $admin The admin instance.
	 */
	public function __construct( $admin ) {
		$this->admin = $admin;
	}

	/**
	 * Handle change detections request.
	 */
	public function handle_request() {
		// Check permissions.
		if ( ! $this->admin->settings_handler->is_allowed( 'change_detections_view' ) ) {
			return;
		}

		$this->render_change_detections_page();
	}

	/**
	 * Render change detections page.
	 */
	private function render_change_detections_page() {
		// Get filter parameters.
		$from = gmdate( 'Y-m-d', strtotime( '- 7 days' ) );
		if ( isset( $_GET['from'] ) ) {
			$from = sanitize_text_field( wp_unslash( $_GET['from'] ) );
			if ( empty( $from ) ) {
				echo '<div class="error notice"><p>Wrong from date.</p></div>';
				return false;
			}
		}

		$to = current_time( 'Y-m-d' );
		if ( isset( $_GET['to'] ) ) {
			$to = sanitize_text_field( wp_unslash( $_GET['to'] ) );
			if ( empty( $to ) ) {
				echo '<div class="error notice"><p>Wrong to date.</p></div>';
				return false;
			}
		}

		$group_type = false;
		if ( isset( $_GET['group_type'] ) ) {
			$group_type = sanitize_text_field( wp_unslash( $_GET['group_type'] ) );
			if ( ! empty( $group_type ) && ! in_array( $group_type, WebChangeDetector_Admin::VALID_GROUP_TYPES, true ) ) {
				echo '<div class="error notice"><p>Invalid group_type.</p></div>';
				return false;
			}
		}

		$status = false;
		if ( isset( $_GET['status'] ) ) {
			$status = sanitize_text_field( wp_unslash( $_GET['status'] ) );
			if ( ! empty( $status ) && ! empty( array_diff( explode( ',', $status ), WebChangeDetector_Admin::VALID_COMPARISON_STATUS ) ) ) {
				echo '<div class="error notice"><p>Invalid status.</p></div>';
				return false;
			}
		}

		$difference_only = false;
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- GET parameter for filtering only.
		if ( isset( $_GET['difference_only'] ) ) {
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- GET parameter for filtering only.
			$difference_only = sanitize_text_field( wp_unslash( $_GET['difference_only'] ) );
		}

		?>
		<div class="action-container wizard-change-detections">

			<form method="get" style="margin-bottom: 20px;">
				<input type="hidden" name="page" value="webchangedetector-change-detections">

				from <input name="from" value="<?php echo esc_html( $from ); ?>" type="date">
				to <input name="to" value="<?php echo esc_html( $to ); ?>" type="date">

				<select name="group_type">
					<option value="" <?php echo ! $group_type ? 'selected' : ''; ?>><?php esc_html_e( 'All Checks', 'webchangedetector' ); ?></option>
					<option value="post" <?php echo 'post' === $group_type ? 'selected' : ''; ?>><?php esc_html_e( 'Auto Update Checks & Manual Checks', 'webchangedetector' ); ?></option>
					<option value="auto" <?php echo 'auto' === $group_type ? 'selected' : ''; ?>><?php esc_html_e( 'Monitoring Checks', 'webchangedetector' ); ?></option>
				</select>
				<select name="status" class="js-dropdown">
					<option value="" <?php echo ! $status ? 'selected' : ''; ?>><?php esc_html_e( 'All Status', 'webchangedetector' ); ?></option>
					<option value="new" <?php echo 'new' === $status ? 'selected' : ''; ?>><?php esc_html_e( 'New', 'webchangedetector' ); ?></option>
					<option value="ok" <?php echo 'ok' === $status ? 'selected' : ''; ?>><?php esc_html_e( 'Ok', 'webchangedetector' ); ?></option>
					<option value="to_fix" <?php echo 'to_fix' === $status ? 'selected' : ''; ?>><?php esc_html_e( 'To Fix', 'webchangedetector' ); ?></option>
					<option value="false_positive" <?php echo 'false_positive' === $status ? 'selected' : ''; ?>><?php esc_html_e( 'False Positive', 'webchangedetector' ); ?></option>
				</select>
				<select name="difference_only" class="js-dropdown">
					<option value="0" <?php echo ! $difference_only ? 'selected' : ''; ?>><?php esc_html_e( 'All detections', 'webchangedetector' ); ?></option>
					<option value="1" <?php echo $difference_only ? 'selected' : ''; ?>><?php esc_html_e( 'With difference', 'webchangedetector' ); ?></option>
				</select>

				<input class="button" type="submit" value="<?php esc_html_e( 'Filter', 'webchangedetector' ); ?>">
			</form>

			<?php
			// Wizard functionality temporarily removed for phase 1.
			// Will be moved to view renderer in later phases.

			$extra_filters = array();
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- GET parameter for pagination only.
			$extra_filters['paged'] = isset( $_GET['paged'] ) ? sanitize_key( wp_unslash( $_GET['paged'] ) ) : 1;

			// Show comparisons.
			$filter_batches = array(
				'page'     => $extra_filters['paged'],
				'per_page' => 20,
				'from'     => gmdate( 'Y-m-d', strtotime( $from ) ),
				'to'       => gmdate( 'Y-m-d', strtotime( $to ) ),
			);

			if ( $group_type ) {
				$extra_filters['queue_type'] = $group_type;
			} else {
				$extra_filters['queue_type'] = 'post,auto';
			}

			if ( $status ) {
				$extra_filters['status'] = $status;
			} else {
				$extra_filters['status'] = 'new,ok,to_fix,false_positive';
			}

			if ( $difference_only ) {
				$extra_filters['above_threshold'] = (bool) $difference_only;
			}

			$comparisons   = array();
			$failed_queues = array();

			// Check if we're filtering by a specific batch_id from auto-update history.
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- GET parameter for filtering only.
			if ( isset( $_GET['batch_id'] ) ) {
				// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- GET parameter for filtering only.
				$batch_id = sanitize_text_field( wp_unslash( $_GET['batch_id'] ) );
				// Get specific batch.
				$single_batch = \WebChangeDetector\WebChangeDetector_API_V2::get_batch_v2( $batch_id );
				if ( $single_batch ) {
					$batches = array(
						'data' => array( $single_batch['data'] ),
						'meta' => array(
							'total' => 1,
							'links' => array(),
						),
					);
					// Show notice that we're viewing a specific batch.
					$auto_update_batches = get_option( WCD_AUTO_UPDATE_COMPARISON_BATCHES );
					if ( is_array( $auto_update_batches ) && in_array( $batch_id, $auto_update_batches, true ) ) {
						echo '<div class="notice notice-info"><p>';
						echo esc_html__( 'Viewing Auto-Update Check visual comparisons. ', 'webchangedetector' );
						echo '<a href="?page=webchangedetector-logs&tab=auto-updates">' . esc_html__( '← Back to Auto-Update History', 'webchangedetector' ) . '</a>';
						echo '</p></div>';
					}
				} else {
					echo '<div class="notice notice-error"><p>' . esc_html__( 'Batch not found.', 'webchangedetector' ) . '</p></div>';
					$batches = array( 'data' => array() );
				}
			} else {
				$batches = \WebChangeDetector\WebChangeDetector_API_V2::get_batches_v2( array_merge( $filter_batches, $extra_filters ) );
			}

			if ( ! empty( $batches['data'] ) ) {
				$filter_batches_in_comparisons = array();
				foreach ( $batches['data'] as $batch ) {
					$filter_batches_in_comparisons[] = $batch['id'];
				}
				$filters_comparisons = array(
					'batches'  => implode( ',', $filter_batches_in_comparisons ),
					'per_page' => 999999,
				);

				$comparisons = \WebChangeDetector\WebChangeDetector_API_V2::get_comparisons_v2( array_merge( $filters_comparisons, $extra_filters ) );

				if ( ! empty( $comparisons['data'] ) ) {
					$comparisons = $comparisons['data'];
				}
			}

			$this->admin->dashboard_handler->compare_view_v2( $batches['data'] ?? array() );

			// Prepare pagination.
			unset( $extra_filters['paged'] );
			unset( $filter_batches['page'] );
			$pagination_filters = array_merge( $filter_batches, $extra_filters );
			$pagination         = $batches['meta'];
			?>
			<!-- Pagination -->
			<div class="tablenav">
				<div class="tablenav-pages">
					<span class="displaying-num"><?php echo esc_html( $pagination['total'] ); ?> items</span>
					<span class="pagination-links">
						<?php
						foreach ( $pagination['links'] as $link ) {
							$params = \WebChangeDetector\WebChangeDetector_Admin_Utils::get_params_of_url( $link['url'] )['page'] ?? '';
							$class  = ! $link['url'] || $link['active'] ? 'disabled' : '';
							?>
							<a class="tablenav-pages-navspan button <?php echo esc_html( $class ); ?>"
								href="?page=webchangedetector-change-detections&
							paged=<?php echo esc_html( $params ); ?>&
							<?php echo esc_html( build_query( $pagination_filters ) ); ?>">
								<?php echo esc_html( $link['label'] ); ?>
							</a>
							<?php
						}
						?>
					</span>
				</div>
			</div>
		</div>
		<div class="clear"></div>
		<?php
	}
}