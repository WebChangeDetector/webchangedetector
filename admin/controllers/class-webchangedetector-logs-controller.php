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
	 * The database logger instance.
	 *
	 * @var WebChangeDetector_Database_Logger
	 */
	private $database_logger;

	/**
	 * Constructor.
	 *
	 * @param WebChangeDetector_Admin $admin The admin instance.
	 */
	public function __construct( $admin ) {
		$this->admin           = $admin;
		$this->database_logger = new WebChangeDetector_Database_Logger();
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
		$active_tab = isset( $_GET['tab'] ) ? sanitize_text_field( wp_unslash( $_GET['tab'] ) ) : 'debug-logs';

		// Display tabs.
		?>
		<h2 class="nav-tab-wrapper">
			
			<a href="?page=webchangedetector-logs&tab=queue" class="nav-tab <?php echo 'queue' === $active_tab ? 'nav-tab-active' : ''; ?>">
				<?php esc_html_e( 'Queue', 'webchangedetector' ); ?>
			</a>
			<a href="?page=webchangedetector-logs&tab=auto-updates" class="nav-tab <?php echo 'auto-updates' === $active_tab ? 'nav-tab-active' : ''; ?>">
				<?php esc_html_e( 'Auto-Update History', 'webchangedetector' ); ?>
			</a>
			<a href="?page=webchangedetector-logs&tab=debug-logs" class="nav-tab <?php echo 'debug-logs' === $active_tab ? 'nav-tab-active' : ''; ?>">
				<?php esc_html_e( 'Debug Logs', 'webchangedetector' ); ?>
			</a>
		</h2>
		<?php

		// Render the appropriate tab content.
		if ( 'debug-logs' === $active_tab ) {
			$this->render_debug_logs();
			return;
		} elseif ( 'auto-updates' === $active_tab ) {
			$this->render_auto_update_history();
			return;
		}

		// Continue with regular queue view.
		// Wizard functionality temporarily removed for phase 1.
		// Will be moved to view renderer in later phases.

		$paged = 1;
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- GET parameter for pagination only.
		if ( isset( $_GET['paged'] ) ) {
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- GET parameter for pagination only.
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
						// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- GET parameter for pagination only.
						if ( ! isset( $_GET['paged'] ) ) {
							$_GET['paged'] = 1;
						}
						foreach ( $queues_meta['links'] as $link ) {
							// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- GET parameter for pagination only.
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
	 * Render debug logs tab with database-based logging.
	 */
	private function render_debug_logs() {
		// Get filters from URL parameters.
		// phpcs:disable WordPress.Security.NonceVerification.Recommended -- GET parameters for filtering only.
		$filters = array(
			'level'     => isset( $_GET['level'] ) ? sanitize_text_field( wp_unslash( $_GET['level'] ) ) : '',
			'context'   => isset( $_GET['context'] ) ? sanitize_text_field( wp_unslash( $_GET['context'] ) ) : '',
			'search'    => isset( $_GET['search'] ) ? sanitize_text_field( wp_unslash( $_GET['search'] ) ) : '',
			'date_from' => isset( $_GET['date_from'] ) ? sanitize_text_field( wp_unslash( $_GET['date_from'] ) ) : '',
			'date_to'   => isset( $_GET['date_to'] ) ? sanitize_text_field( wp_unslash( $_GET['date_to'] ) ) : '',
			'per_page'  => isset( $_GET['per_page'] ) ? max( 10, min( 100, absint( $_GET['per_page'] ) ) ) : 50,
			'page'      => isset( $_GET['paged'] ) ? max( 1, absint( $_GET['paged'] ) ) : 1,
		);
		// phpcs:enable WordPress.Security.NonceVerification.Recommended

		// Get logs from database.
		$result = $this->database_logger->get_logs( $filters );
		$logs   = $result['logs'];

		// Get available contexts for filter dropdown.
		$contexts = $this->database_logger->get_contexts();

		// Get log statistics.
		$stats = $this->database_logger->get_statistics();

		?>
		<div class="wrap webchangedetector">
			<div class="action-container">
				
				<!-- Log Statistics -->
				<div class="wcd-settings-card" style="margin-bottom: 20px;">
					<h3><?php esc_html_e( 'Log Statistics', 'webchangedetector' ); ?></h3>
					<div style="display: flex; gap: 20px; flex-wrap: wrap;">
						<div style="background: #f9f9f9; padding: 10px; border-radius: 5px;">
							<strong><?php echo esc_html( number_format_i18n( $stats['total_count'] ) ); ?></strong><br>
							<?php esc_html_e( 'Total Logs', 'webchangedetector' ); ?>
						</div>
						<div style="background: #f9f9f9; padding: 10px; border-radius: 5px;">
							<strong><?php echo esc_html( number_format_i18n( $stats['recent_count'] ) ); ?></strong><br>
							<?php esc_html_e( 'Last 24 Hours', 'webchangedetector' ); ?>
						</div>
						<?php foreach ( $stats['by_level'] as $level => $count ) : ?>
							<div style="background: #f9f9f9; padding: 10px; border-radius: 5px;">
								<strong><?php echo esc_html( number_format_i18n( $count ) ); ?></strong><br>
								<?php echo esc_html( ucfirst( $level ) ); ?>
							</div>
						<?php endforeach; ?>
					</div>
				</div>

				<!-- Filters -->
				<form method="get" style="background: #fff; padding: 15px; margin-bottom: 20px; border: 1px solid #ddd;">
					<input type="hidden" name="page" value="webchangedetector-logs">
					<input type="hidden" name="tab" value="debug-logs">
					
					<div style="display: flex; gap: 10px; flex-wrap: wrap; align-items: end;">
						<div>
							<label for="level"><?php esc_html_e( 'Level', 'webchangedetector' ); ?></label><br>
							<select name="level" id="level">
								<option value=""><?php esc_html_e( 'All Levels', 'webchangedetector' ); ?></option>
								<option value="debug" <?php selected( $filters['level'], 'debug' ); ?>><?php esc_html_e( 'Debug', 'webchangedetector' ); ?></option>
								<option value="info" <?php selected( $filters['level'], 'info' ); ?>><?php esc_html_e( 'Info', 'webchangedetector' ); ?></option>
								<option value="warning" <?php selected( $filters['level'], 'warning' ); ?>><?php esc_html_e( 'Warning', 'webchangedetector' ); ?></option>
								<option value="error" <?php selected( $filters['level'], 'error' ); ?>><?php esc_html_e( 'Error', 'webchangedetector' ); ?></option>
								<option value="critical" <?php selected( $filters['level'], 'critical' ); ?>><?php esc_html_e( 'Critical', 'webchangedetector' ); ?></option>
							</select>
						</div>
						
						<div>
							<label for="context"><?php esc_html_e( 'Context', 'webchangedetector' ); ?></label><br>
							<select name="context" id="context">
								<option value=""><?php esc_html_e( 'All Contexts', 'webchangedetector' ); ?></option>
								<?php foreach ( $contexts as $context ) : ?>
									<option value="<?php echo esc_attr( $context ); ?>" <?php selected( $filters['context'], $context ); ?>>
										<?php echo esc_html( $context ); ?>
									</option>
								<?php endforeach; ?>
							</select>
						</div>
						
						<div>
							<label for="search"><?php esc_html_e( 'Search Message', 'webchangedetector' ); ?></label><br>
							<input type="text" name="search" id="search" value="<?php echo esc_attr( $filters['search'] ); ?>" placeholder="<?php esc_attr_e( 'Search in messages...', 'webchangedetector' ); ?>">
						</div>
						
						<div>
							<label for="date_from"><?php esc_html_e( 'From Date', 'webchangedetector' ); ?></label><br>
							<?php
							// Set default value to now - 7 days if not set.
							$date_from_value = ! empty( $filters['date_from'] ) ? $filters['date_from'] : gmdate( 'Y-m-d', strtotime( '-7 days' ) );
							?>
							<input type="date" name="date_from" id="date_from" value="<?php echo esc_attr( $date_from_value ); ?>">
						</div>
						
						<div>
							<label for="date_to"><?php esc_html_e( 'To Date', 'webchangedetector' ); ?></label><br>
							<?php
							// Set default value to now if not set.
							$date_to_value = ! empty( $filters['date_to'] ) ? $filters['date_to'] : gmdate( 'Y-m-d' );
							?>
							<input type="date" name="date_to" id="date_to" value="<?php echo esc_attr( $date_to_value ); ?>">
						</div>
						
						<div>
							<label for="per_page"><?php esc_html_e( 'Per Page', 'webchangedetector' ); ?></label><br>
							<select name="per_page" id="per_page">
								<option value="25" <?php selected( $filters['per_page'], 25 ); ?>>25</option>
								<option value="50" <?php selected( $filters['per_page'], 50 ); ?>>50</option>
								<option value="100" <?php selected( $filters['per_page'], 100 ); ?>>100</option>
							</select>
						</div>
						
						<div>
							<input type="submit" class="button" value="<?php esc_attr_e( 'Filter', 'webchangedetector' ); ?>">
							<a href="?page=webchangedetector-logs&tab=debug-logs" class="button"><?php esc_html_e( 'Clear', 'webchangedetector' ); ?></a>
						</div>
					</div>
				</form>

				<!-- Export and Clear Actions -->
				<div style="margin-bottom: 15px;">
					<button type="button" 
							id="wcd-export-logs-btn" 
							class="button" 
							data-filters="
							<?php
							echo esc_attr(
								wp_json_encode(
									array_filter(
										$filters,
										function ( $value, $key ) {
											return ! empty( $value ) && 'page' !== $key;
										},
										ARRAY_FILTER_USE_BOTH
									)
								)
							);
							?>
											"
							style="margin-right: 10px;">
						<?php esc_html_e( 'Export to CSV', 'webchangedetector' ); ?>
					</button>
					
					<?php if ( current_user_can( 'manage_options' ) ) : ?>
						<form method="post" style="display: inline-block;" onsubmit="return confirm('<?php esc_attr_e( 'Are you sure you want to clear all logs? This action cannot be undone.', 'webchangedetector' ); ?>');">
							<?php wp_nonce_field( 'clear_logs' ); ?>
							<input type="hidden" name="wcd_action" value="clear_logs">
							<button type="submit" class="button button-secondary"><?php esc_html_e( 'Clear All Logs', 'webchangedetector' ); ?></button>
						</form>
					<?php endif; ?>
				</div>

				<!-- Logs Table -->
				<?php if ( empty( $logs ) ) : ?>
					<div style="background: #fff; padding: 20px; text-align: center; border: 1px solid #ddd;">
						<strong><?php esc_html_e( 'No logs found.', 'webchangedetector' ); ?></strong><br>
						<?php esc_html_e( 'Try adjusting your filters or enable debug logging in settings.', 'webchangedetector' ); ?>
					</div>
				<?php else : ?>
					<table class="widefat striped" style="margin-bottom: 20px;">
						<thead>
							<tr>
								<th><?php esc_html_e( 'Timestamp', 'webchangedetector' ); ?></th>
								<th><?php esc_html_e( 'Level', 'webchangedetector' ); ?></th>
								<th><?php esc_html_e( 'Context', 'webchangedetector' ); ?></th>
								<th><?php esc_html_e( 'Message', 'webchangedetector' ); ?></th>
								<th><?php esc_html_e( 'User', 'webchangedetector' ); ?></th>
								<th><?php esc_html_e( 'IP', 'webchangedetector' ); ?></th>
							</tr>
						</thead>
						<tbody>
							<?php foreach ( $logs as $log ) : ?>
								<?php
								$level_class = 'log-level-' . $log['level'];
								$user_info   = '';
								if ( $log['user_id'] ) {
									$user      = get_userdata( $log['user_id'] );
									$user_info = $user ? $user->display_name : '#' . $log['user_id'];
								}
								?>
								<tr class="<?php echo esc_attr( $level_class ); ?>">
									<td style="white-space: nowrap;">
										<?php echo esc_html( get_date_from_gmt( $log['timestamp'], get_option( 'date_format' ) . ' ' . get_option( 'time_format' ) ) ); ?>
										<?php if ( $log['request_id'] ) : ?>
											<br><small style="color: #666;"><?php echo esc_html( $log['request_id'] ); ?></small>
										<?php endif; ?>
									</td>
									<td>
										<span class="log-level-badge <?php echo esc_attr( $level_class ); ?>" style="
											padding: 2px 8px; 
											border-radius: 3px; 
											font-size: 11px; 
											font-weight: bold; 
											text-transform: uppercase;
											<?php
											switch ( $log['level'] ) {
												case 'debug':
													echo 'background: #f0f0f0; color: #666;';
													break;
												case 'info':
													echo 'background: #dbeafe; color: #1e40af;';
													break;
												case 'warning':
													echo 'background: #fef3c7; color: #d97706;';
													break;
												case 'error':
													echo 'background: #fecaca; color: #dc2626;';
													break;
												case 'critical':
													echo 'background: #dc2626; color: white;';
													break;
											}
											?>
										">
											<?php echo esc_html( strtoupper( $log['level'] ) ); ?>
										</span>
									</td>
									<td style="font-family: monospace; font-size: 12px;">
										<?php echo esc_html( $log['context'] ); ?>
									</td>
									<td style="word-break: break-word; max-width: 300px;">
										<?php echo esc_html( $log['message'] ); ?>
										<?php if ( $log['additional_data'] ) : ?>
											<details style="margin-top: 5px;">
												<summary style="cursor: pointer; color: #0073aa;"><?php esc_html_e( 'Additional Data', 'webchangedetector' ); ?></summary>
												<pre style="background: #f9f9f9; padding: 10px; margin-top: 5px; font-size: 11px; overflow-x: auto;"><?php echo esc_html( wp_json_encode( $log['additional_data'], JSON_PRETTY_PRINT ) ); ?></pre>
											</details>
										<?php endif; ?>
									</td>
									<td><?php echo esc_html( $user_info ); ?></td>
									<td style="font-family: monospace; font-size: 12px;">
										<?php echo esc_html( $log['ip_address'] ); ?>
									</td>
								</tr>
							<?php endforeach; ?>
						</tbody>
					</table>

					<!-- Pagination -->
					<?php if ( $result['total_pages'] > 1 ) : ?>
						<div class="tablenav">
							<div class="tablenav-pages">
								<span class="displaying-num">
									<?php
									/* translators: %s: Number of items */
									printf( esc_html( _n( '%s item', '%s items', $result['total_count'], 'webchangedetector' ) ), esc_html( number_format_i18n( $result['total_count'] ) ) );
									?>
								</span>
								<span class="pagination-links">
									<?php
									$current_page = $result['current_page'];
									$total_pages  = $result['total_pages'];

									// Build base URL with current filters.
									$base_url = add_query_arg(
										array_filter(
											array_merge(
												$filters,
												array(
													'page' => 'webchangedetector-logs',
													'tab'  => 'debug-logs',
												)
											)
										),
										admin_url( 'admin.php' )
									);

									// Previous page.
									if ( $current_page > 1 ) {
										$prev_url = add_query_arg( 'paged', $current_page - 1, $base_url );
										echo '<a class="prev-page button" href="' . esc_url( $prev_url ) . '">' . esc_html__( '‹ Previous', 'webchangedetector' ) . '</a>';
									} else {
										echo '<span class="tablenav-pages-navspan button disabled">‹ ' . esc_html__( 'Previous', 'webchangedetector' ) . '</span>';
									}

									// Page numbers.
									echo ' <span class="paging-input">';
									echo '<span class="tablenav-paging-text">';
									/* translators: 1: Current page number, 2: Total number of pages */
									printf( esc_html__( '%1$s of %2$s', 'webchangedetector' ), esc_html( number_format_i18n( $current_page ) ), esc_html( number_format_i18n( $total_pages ) ) );
									echo '</span>';
									echo '</span>';

									// Next page.
									if ( $current_page < $total_pages ) {
										$next_url = add_query_arg( 'paged', $current_page + 1, $base_url );
										echo '<a class="next-page button" href="' . esc_url( $next_url ) . '">' . esc_html__( 'Next ›', 'webchangedetector' ) . '</a>';
									} else {
										echo '<span class="tablenav-pages-navspan button disabled">' . esc_html__( 'Next', 'webchangedetector' ) . ' ›</span>';
									}
									?>
								</span>
							</div>
						</div>
					<?php endif; ?>
				<?php endif; ?>
			</div>
		</div>
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
						<strong><?php esc_html_e( 'No auto-update history yet.', 'webchangedetector' ); ?></strong><br>
						<?php esc_html_e( 'Auto-update results will appear here after WordPress performs automatic updates.', 'webchangedetector' ); ?>
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
												<strong><?php echo esc_html( get_date_from_gmt( gmdate( 'Y-m-d H:i:s', $entry['timestamp'] ), get_option( 'date_format' ) . ' ' . get_option( 'time_format' ) ) ); ?></strong>
											</div>
											<div class="accordion-batch-title-tile">
												<?php
												echo esc_html(
													sprintf(
														/* translators: 1: number of successful updates, 2: total attempted updates */
														esc_html__( '%1$d of %2$d updates successful', 'webchangedetector' ),
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
													<?php esc_html_e( 'View Visual Comparisons', 'webchangedetector' ); ?> →
													</a>
												</p>
											<?php endif; ?>
										<?php if ( isset( $entry['updates']['core'] ) && $entry['updates']['core'] ) : ?>
											<div class="update-section">
												<h4><?php esc_html_e( 'WordPress Core', 'webchangedetector' ); ?></h4>
												<div class="update-item">
													<?php if ( $entry['updates']['core']['success'] ) : ?>
														<span style="color: #46b450;">✓</span>
													<?php else : ?>
														<span style="color: #dc3232;">✗</span>
													<?php endif; ?>
													<?php
													echo esc_html(
														sprintf(
															/* translators: 1: from version, 2: to version */
															esc_html__( 'Version %1$s → %2$s', 'webchangedetector' ),
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
												<h4><?php esc_html_e( 'Plugins', 'webchangedetector' ); ?></h4>
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
																/* translators: 1: from version, 2: to version */
																esc_html__( 'Version %1$s → %2$s', 'webchangedetector' ),
																$plugin['from_version'] ?? '?',
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
												<h4><?php esc_html_e( 'Themes', 'webchangedetector' ); ?></h4>
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
																/* translators: 1: from version, 2: to version */
																esc_html__( 'Version %1$s → %2$s', 'webchangedetector' ),
																$theme['from_version'] ?? '?',
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
