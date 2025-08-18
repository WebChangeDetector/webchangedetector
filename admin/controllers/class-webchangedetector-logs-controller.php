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
		// Check if we're viewing a specific tab.
		$active_tab = isset( $_GET['tab'] ) ? sanitize_text_field( wp_unslash( $_GET['tab'] ) ) : 'queue';

		// Display tabs.
		?>
		<h2 class="nav-tab-wrapper">
			<a href="?page=webchangedetector-logs&tab=queue" class="nav-tab <?php echo 'queue' === $active_tab ? 'nav-tab-active' : ''; ?>">
				<?php _e( 'Queue', 'webchangedetector' ); ?>
			</a>
			<a href="?page=webchangedetector-logs&tab=auto-updates" class="nav-tab <?php echo 'auto-updates' === $active_tab ? 'nav-tab-active' : ''; ?>">
				<?php _e( 'Auto-Update History', 'webchangedetector' ); ?>
			</a>
		</h2>
		<?php

		// Render the appropriate tab content.
		if ( 'auto-updates' === $active_tab ) {
			$this->render_auto_update_history();
			return;
		}

		// Continue with regular queue view.
		// Wizard functionality temporarily removed for phase 1.
		// Will be moved to view renderer in later phases.

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

		// Wizard functionality temporarily removed for phase 1.
		// Will be moved to view renderer in later phases.

		?>

		<div class="action-container wizard-logs">
			<table class="queue">
				<tr>
					<th></th>
					<th style="width: 100%">Page & URL</th>
					<th style="min-width: 150px;">Type</th>
					<th>Status</th>
					<th style="min-width: 200px;">Time added /<br> Time updated</th>
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
							esc_html( get_date_from_gmt( $queue['created_at'], get_option( 'date_format' ) . ' ' . get_option( 'time_format' ) ) ) . '</span><br>';
						echo '<span class="local-time" data-date="' . esc_html( strtotime( $queue['updated_at'] ) ) . '">' .
							esc_html( get_date_from_gmt( $queue['updated_at'], get_option( 'date_format' ) . ' ' . get_option( 'time_format' ) ) ) . '</span></td>';
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

	/**
	 * Render auto-update history tab.
	 */
	private function render_auto_update_history() {
		// Get auto-update history from options.
		$update_history = get_option( 'wcd_auto_update_history', array() );

		?>
		<div class="wrap webchangedetector">
			<div class="action-container">
				<?php if ( empty( $update_history ) ) : ?>
					<div style="background: #fff; padding: 20px; text-align: center; margin: 20px 0;">
						<strong><?php _e( 'No auto-update history yet.', 'webchangedetector' ); ?></strong><br>
						<?php _e( 'Auto-update results will appear here after WordPress performs automatic updates.', 'webchangedetector' ); ?>
					</div>
				<?php else : ?>
					<?php foreach ( $update_history as $index => $entry ) : ?>
						<div class="accordion-container" style="margin-top: 20px;">
							<div class="accordion accordion-batch accordion-auto-update">
								<div class="mm_accordion_title">
									<h3>
										<div style="display: inline-block;">
											<div class="accordion-batch-title-tile accordion-batch-title-tile-status">
												<?php
												$status_class = 'status-' . str_replace( '_', '', $entry['summary']['status'] );
												$status_text  = 'completed' === $entry['summary']['status'] ? __( 'Completed', 'webchangedetector' ) :
													( 'completed_with_errors' === $entry['summary']['status'] ? __( 'Completed with Errors', 'webchangedetector' ) :
													__( 'Failed', 'webchangedetector' ) );

												$status_icon = 'completed' === $entry['summary']['status'] ? '✓' :
													( 'completed_with_errors' === $entry['summary']['status'] ? '⚠' : '✗' );

												$status_color = 'completed' === $entry['summary']['status'] ? '#46b450' :
													( 'completed_with_errors' === $entry['summary']['status'] ? '#ffb900' : '#dc3232' );
												?>
												<span style="color: <?php echo esc_attr( $status_color ); ?>; font-weight: bold;">
													<?php echo esc_html( $status_icon . ' ' . $status_text ); ?>
												</span>
											</div>
											<div class="accordion-batch-title-tile" style="width: 250px;">
												<strong><?php echo esc_html( get_date_from_gmt( date( 'Y-m-d H:i:s', $entry['timestamp'] ), get_option( 'date_format' ) . ' ' . get_option( 'time_format' ) ) ); ?></strong>
											</div>
											<div class="accordion-batch-title-tile">
												<?php
												/* translators: 1: number of successful updates, 2: total attempted updates */
												echo esc_html(
													sprintf(
														__( '%1$d of %2$d updates successful', 'webchangedetector' ),
														$entry['summary']['successful'],
														$entry['summary']['total_attempted']
													)
												);
												?>
											</div>
											
										</div>
										<div style="clear: both;"></div>
									</h3>
									<div class="mm_accordion_content" style="padding: 20px;">
										<style>
											.update-section {
												margin-bottom: 20px;
											}
											.update-section h4 {
												margin: 0 0 10px 0;
												color: #23282d;
												font-size: 14px;
												font-weight: 600;
											}
											.update-item {
												padding: 5px 0;
												margin-left: 20px;
												line-height: 1.6;
											}
										</style>
										<?php
											// Use post-update batch_id for comparisons (the main batch_id field).
											// This will be the post-update batch_id once it's available.
										if ( isset( $entry['batch_id'] ) && $entry['batch_id'] ) :
											?>
												<p>
													<a href="?page=webchangedetector-change-detections&batch_id=<?php echo esc_attr( $entry['batch_id'] ); ?>" class="button button-small">
													<?php _e( 'View Visual Comparisons', 'webchangedetector' ); ?> →
													</a>
												</p>
											<?php endif; ?>
										<?php if ( isset( $entry['updates']['core'] ) && $entry['updates']['core'] ) : ?>
											<div class="update-section">
												<h4><?php _e( 'WordPress Core', 'webchangedetector' ); ?></h4>
												<div class="update-item">
													<?php if ( $entry['updates']['core']['success'] ) : ?>
														<span style="color: #46b450;">✓</span>
													<?php else : ?>
														<span style="color: #dc3232;">✗</span>
													<?php endif; ?>
													<?php
													echo esc_html(
														sprintf(
															__( 'Version %1$s → %2$s', 'webchangedetector' ),
															$entry['updates']['core']['from_version'],
															$entry['updates']['core']['to_version']
														)
													);
													?>
													<?php if ( isset( $entry['updates']['core']['error'] ) && $entry['updates']['core']['error'] ) : ?>
														<br><span style="color: #dc3232;"><?php echo esc_html( $entry['updates']['core']['error'] ); ?></span>
													<?php endif; ?>
												</div>
											</div>
										<?php endif; ?>

										<?php if ( isset( $entry['updates']['plugins'] ) && ! empty( $entry['updates']['plugins'] ) ) : ?>
											<div class="update-section">
												<h4><?php _e( 'Plugins', 'webchangedetector' ); ?></h4>
												<?php foreach ( $entry['updates']['plugins'] as $plugin ) : ?>
													<div class="update-item">
														<?php if ( $plugin['success'] ) : ?>
															<span style="color: #46b450;">✓</span>
														<?php else : ?>
															<span style="color: #dc3232;">✗</span>
														<?php endif; ?>
														<strong><?php echo esc_html( $plugin['name'] ); ?></strong>:
														<?php
														echo esc_html(
															sprintf(
																__( 'Version %1$s → %2$s', 'webchangedetector' ),
																$plugin['from_version'] ?: '?',
																$plugin['to_version']
															)
														);
														?>
														<?php if ( isset( $plugin['error'] ) && $plugin['error'] ) : ?>
															<br><span style="color: #dc3232; margin-left: 20px;"><?php echo esc_html( $plugin['error'] ); ?></span>
														<?php endif; ?>
													</div>
												<?php endforeach; ?>
											</div>
										<?php endif; ?>

										<?php if ( isset( $entry['updates']['themes'] ) && ! empty( $entry['updates']['themes'] ) ) : ?>
											<div class="update-section">
												<h4><?php _e( 'Themes', 'webchangedetector' ); ?></h4>
												<?php foreach ( $entry['updates']['themes'] as $theme ) : ?>
													<div class="update-item">
														<?php if ( $theme['success'] ) : ?>
															<span style="color: #46b450;">✓</span>
														<?php else : ?>
															<span style="color: #dc3232;">✗</span>
														<?php endif; ?>
														<strong><?php echo esc_html( $theme['name'] ); ?></strong>:
														<?php
														echo esc_html(
															sprintf(
																__( 'Version %1$s → %2$s', 'webchangedetector' ),
																$theme['from_version'] ?: '?',
																$theme['to_version']
															)
														);
														?>
														<?php if ( isset( $theme['error'] ) && $theme['error'] ) : ?>
															<br><span style="color: #dc3232; margin-left: 20px;"><?php echo esc_html( $theme['error'] ); ?></span>
														<?php endif; ?>
													</div>
												<?php endforeach; ?>
											</div>
										<?php endif; ?>
									</div>
								</div>
							</div>
						</div>
					<?php endforeach; ?>

					<script>
						jQuery(document).ready(function($) {
							// Initialize accordion for auto-update history.
							$('.accordion-auto-update').accordion({
								heightStyle: "content",
								header: "h3",
								collapsible: true,
								active: false, // Don't auto-open on load.
								animate: 200
							});
						});
					</script>
				<?php endif; ?>
			</div>
		</div>
		<?php
	}
}
