<?php
/**
 * Logs Controller for WebChangeDetector
 *
 * Handles logs/queue page requests and logic.
 *
 * @package    WebChangeDetector
 * @subpackage WebChangeDetector/admin/controllers
 * @author     Mike Miler <mike@wp-mike.com>
 */

namespace WebChangeDetector;

/**
 * Logs Controller Class.
 */
class WebChangeDetector_Logs_Controller {

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
	 * Handle logs request.
	 */
	public function handle_request() {
		// Check permissions.
		if ( ! $this->admin->settings_handler->is_allowed( 'logs_view' ) ) {
			return;
		}

		$this->render_logs_page();
	}

	/**
	 * Render logs page.
	 */
	private function render_logs_page() {
		// Wizard functionality temporarily removed for phase 1
		// Will be moved to view renderer in later phases

		$paged = 1;
		if ( isset( $_GET['paged'] ) ) {
			$paged = sanitize_key( wp_unslash( $_GET['paged'] ) );
		}

		$groups        = get_option( WCD_WEBSITE_GROUPS );
		$filter_groups = false;
		if ( $groups ) {
			$filter_groups = implode( ',', $groups );
		}

		$queues = \WebChangeDetector\WebChangeDetector_API_V2::get_queues_v2( false, false, $filter_groups, array( 'page' => $paged ) );

		$queues_meta = $queues['meta'];
		$queues      = $queues['data'];

		$type_nice_name = array(
			'pre'     => 'Pre-update screenshot',
			'post'    => 'Post-update screenshot',
			'auto'    => 'Monitoring screenshot',
			'compare' => 'Change detection',
		);

		// Wizard functionality temporarily removed for phase 1
		// Will be moved to view renderer in later phases

		?>

		<div class="action-container wizard-logs">
			<table class="queue">
				<tr>
					<th></th>
					<th style="width: 100%">Page & URL</th>
					<th style="min-width: 150px;">Type</th>
					<th>Status</th>
					<th style="min-width: 120px;">Time added /<br> Time updated</th>
					<th>Show</th>
				</tr>
				<?php

				if ( ! empty( $queues ) && is_iterable( $queues ) ) {
					foreach ( $queues as $queue ) {
						$group_type = $queue['monitoring'] ? 'Monitoring' : 'Manual Checks';
						echo '<tr class="queue-status-' . esc_html( $queue['status'] ) . '">';
						echo '<td>';
						\WebChangeDetector\WebChangeDetector_Admin_Utils::get_device_icon( $queue['device'] );
						echo '</td>';
						echo '<td>
                                <span class="html-title queue"> ' . esc_html( $queue['html_title'] ) . '</span><br>
                                <span class="url queue">URL: ' . esc_url( $queue['url_link'] ) . '</span><br>
                                ' . esc_html( $group_type ) . '
                        </td>';
						echo '<td>' . esc_html( $type_nice_name[ $queue['sc_type'] ] ) . '</td>';
						echo '<td>' . esc_html( ucfirst( $queue['status'] ) ) . '</td>';
						echo '<td><span class="local-time" data-date="' . esc_html( strtotime( $queue['created_at'] ) ) . '">' .
							esc_html( gmdate( 'd/m/Y H:i:s', strtotime( $queue['created_at'] ) ) ) . '</span><br>';
						echo '<span class="local-time" data-date="' . esc_html( strtotime( $queue['updated_at'] ) ) . '">' .
							esc_html( gmdate( 'd/m/Y H:i:s', strtotime( $queue['updated_at'] ) ) ) . '</span></td>';
						echo '<td>';

						// Show screenshot button.
						if (
							in_array( $queue['sc_type'], array( 'pre', 'post', 'auto', 'compare' ), true ) &&
							'done' === $queue['status'] &&
							! empty( $queue['image_link'] )
						) {
							?>
							<form method="post" action="?page=webchangedetector-show-screenshot">
								<button class="button" type="submit" name="img_url" value="<?php echo esc_url( $queue['image_link'] ); ?>">Show</button>
							</form>
							<?php
						}
						echo '</td>';
						echo '</tr>';
					}
				} else {
					echo '<tr><td colspan="7" style="text-align: center; font-weight: 700; background-color: #fff;">Nothing to show yet.</td></tr>';
				}
				?>
			</table>
			<!-- Pagination -->
			<div class="tablenav">
				<div class="tablenav-pages">
					<span class="displaying-num"><?php echo esc_html( $queues_meta['total'] ); ?> items</span>
					<span class="pagination-links">
						<?php
						if ( ! isset( $_GET['paged'] ) ) {
							$_GET['paged'] = 1;
						}
						foreach ( $queues_meta['links'] as $link ) {
							$url_params = \WebChangeDetector\WebChangeDetector_Admin_Utils::get_params_of_url( $link['url'] );

							if ( $url_params && ! empty( $url_params['page'] ) && sanitize_key( wp_unslash( $_GET['paged'] ) ) !== $url_params['page'] ) {
								?>
								<a class="tablenav-pages-navspan button" href="?page=webchangedetector-logs&paged=<?php echo esc_html( $url_params['page'] ); ?>">
									<?php echo esc_html( $link['label'] ); ?>
								</a>
							<?php } else { ?>
								<span class="tablenav-pages-navspan button" disabled=""><?php echo esc_html( $link['label'] ); ?></span>
								<?php
							}
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
