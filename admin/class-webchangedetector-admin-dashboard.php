<?php
/**
 * Dashboard and Views Management for WebChange Detector
 *
 * This class handles all dashboard display functionality, comparison views,
 * account activation screens, and view rendering for the WebChange Detector plugin.
 *
 * @link       https://www.webchangedetector.com
 * @since      1.0.0
 *
 * @package    WebChangeDetector
 * @subpackage WebChangeDetector/admin
 */

namespace WebChangeDetector;

/**
 * Dashboard and views management functionality.
 *
 * Defines all methods for displaying dashboard views, comparison tables,
 * account activation screens, and managing view state.
 *
 * @since      1.0.0
 * @package    WebChangeDetector
 * @subpackage WebChangeDetector/admin
 * @author     Mike Miler <mike@wp-mike.com>
 */
class WebChangeDetector_Admin_Dashboard {


	/**
	 * Reference to the main admin instance.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      WebChangeDetector_Admin    $admin    The admin instance.
	 */
	private $admin;


	/**
	 * Reference to the WordPress handler instance.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      WebChangeDetector_Admin_WordPress    $wordpress_handler    The WordPress handler instance.
	 */
	private $wordpress_handler;

	/**
	 * Initialize the dashboard manager.
	 *
	 * @since    1.0.0
	 * @param    WebChangeDetector_Admin           $admin        The admin instance.
	 * @param    WebChangeDetector_Admin_WordPress $wordpress_handler The WordPress handler instance.
	 */
	public function __construct( $admin, $wordpress_handler ) {
		$this->admin             = $admin;
		$this->wordpress_handler = $wordpress_handler;
	}

	/**
	 * Get the main dashboard view with usage statistics and latest change detections.
	 *
	 * @since    1.0.0
	 * @param    array $client_account The client account data from API.
	 * @return   void
	 */
	public function get_dashboard_view( $client_account ) {
		// Usage statistics will be loaded via AJAX to avoid blocking dashboard load.
		$amount_auto_detection  = 0; // Will be loaded via AJAX.
		$max_auto_update_checks = 0; // Will be loaded via AJAX.

		// Check if this is the first time visiting the dashboard.
		$first_time_visit = $this->is_first_time_dashboard_visit();

		?>
		<div class="dashboard">
			<div class="wcd-settings-card">
				<div class="box-half no-border">
					<p>
						<img src="<?php echo esc_html( $this->wordpress_handler->get_wcd_plugin_url() ); ?>/admin/img/logo-webchangedetector.png" style="max-width: 200px">
					</p>
					<hr>
					<p>
						<?php echo esc_html__( 'Perform visual checks (visual regression tests) on your WordPress website to find unwanted visual changes on your web pages before anyone else sees them.', 'webchangedetector' ); ?>
					</p>
					<?php if ( $this->admin->settings_handler->is_allowed( 'wizard_start' ) ) { ?>
						<p>
							<?php echo esc_html__( 'Start the Wizard to see what you can do with WebChange Detector.', 'webchangedetector' ); ?>
						</p>
						<input type="button" class="button button-primary" value="<?php echo esc_attr__( 'Start Tour', 'webchangedetector' ); ?>" onclick="window.wcdStartWizard()">
					<?php } ?>
				</div>
				<div class="box-half credit">
					<?php if ( empty( $client_account['is_subaccount'] ) ) { ?>
						<p style="margin-top: 20px;">
							<strong><?php echo esc_html__( 'Your Plan:', 'webchangedetector' ); ?></strong>
							<?php echo esc_html( $client_account['plan_name'] ); ?>
							(<?php echo esc_html__( 'renews on:', 'webchangedetector' ); ?> <?php echo esc_html( gmdate( 'd/m/Y', strtotime( $client_account['renewal_at'] ) ) ); ?>)
						</p>
					<?php } ?>
					<p style="margin-top:10px;">
						<strong><?php echo esc_html__( 'Used checks:', 'webchangedetector' ); ?></strong>
						<?php
						$usage_percent = 0;
						if ( ! empty( $client_account['checks_limit'] ) ) {
							$usage_percent = number_format( $client_account['checks_done'] / $client_account['checks_limit'] * 100, 1 );
						}
						?>
						<?php echo esc_html( $client_account['checks_done'] ); ?> /
						<?php echo esc_html( $client_account['checks_limit'] ); ?>
					</p>
					<div style="width: 100%; background: #aaa; height: 20px; display: inline-block; position: relative; text-align: center;">
						<span style="z-index: 5; position: absolute; color: #fff;"><?php echo esc_html( $usage_percent ); ?> %</span>
						<div style="width: <?php echo esc_html( $usage_percent ); ?>%; background: #266ECC; height: 20px; text-align: center; position: absolute"></div>
					</div>
					<?php if ( $this->admin->settings_handler->is_allowed( 'monitoring_checks_view' ) ) { ?>
						<p id="wcd-monitoring-stats">
							<strong><?php echo esc_html__( 'Monitoring:', 'webchangedetector' ); ?> </strong>
							<img src="<?php echo esc_html( $this->wordpress_handler->get_wcd_plugin_url() ); ?>/admin/img/loader.gif" style="height: 12px; margin-left: 5px;">
						</p>
					<?php } ?>

					<?php if ( $this->admin->settings_handler->is_allowed( 'manual_checks_view' ) || ( defined( 'WCD_AUTO_UPDATES_ENABLED' ) && true === WCD_AUTO_UPDATES_ENABLED ) ) { ?>
						<p id="wcd-auto-update-stats">
							<strong><?php echo esc_html__( 'Auto update checks:', 'webchangedetector' ); ?> </strong>
							<img src="<?php echo esc_html( $this->wordpress_handler->get_wcd_plugin_url() ); ?>/admin/img/loader.gif" style="height: 12px; margin-left: 5px;">
						</p>
					<?php } ?>

					<!-- Usage warning will be loaded via AJAX -->
					<div id="wcd-usage-warning"></div>

				</div>
				<div class="clear"></div>
			</div>

			<div class="wizard-dashboard-latest-change-detections">
				<h2><?php echo esc_html__( 'Latest Change Detections', 'webchangedetector' ); ?></h2>
				<?php

				$filter_batches = array(
					'queue_type' => 'post,auto',
					'per_page'   => 5,
				);

				$batches = \WebChangeDetector\WebChangeDetector_API_V2::get_batches_v2( $filter_batches );
				// Pass only batch data to create accordion containers, content will be loaded via AJAX.
				$this->compare_view_v2( $batches['data'] ?? array() );

				if ( ! empty( $batches['data'] ) ) {
					?>
					<p><a class="button" href="?page=webchangedetector-change-detections"><?php echo esc_html__( 'Show All Change Detections', 'webchangedetector' ); ?></a></p>
				<?php } ?>
			</div>

			<div class="clear"></div>
		</div>

		<?php if ( $first_time_visit && $this->admin->settings_handler->is_allowed( 'wizard_start' ) ) { ?>
			<script type="text/javascript">
				jQuery(document).ready(function($) {
					// Auto-start wizard for first-time users after a short delay.
					setTimeout(function() {
						if (typeof window.wcdStartWizard === 'function') {
							window.wcdStartWizard();
						}
					}, 1000); // 1 second delay to ensure page is fully loaded.
				});
			</script>
		<?php } ?>
		<?php
	}


	/**
	 * Check if this is the first time the user is visiting the dashboard.
	 *
	 * @since    1.0.0
	 * @return   bool True if first time visit, false otherwise.
	 */
	public function is_first_time_dashboard_visit() {
		$user_id  = get_current_user_id();
		$meta_key = 'wcd_first_time_dashboard_visit';

		// Debug: Allow resetting first-time visit with URL parameter.
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Debug feature, admin only, no data modification.
		if ( isset( $_GET['wcd_reset_first_time'] ) && current_user_can( 'manage_options' ) ) {
			delete_user_meta( $user_id, $meta_key );
			wp_safe_redirect( remove_query_arg( 'wcd_reset_first_time' ) );
			exit;
		}

		// Check user meta first (per-user setting).
		$user_visited = get_user_meta( $user_id, $meta_key, true );

		if ( 'visited' !== $user_visited ) {
			// Mark user as visited.
			update_user_meta( $user_id, $meta_key, 'visited' );
			return true;
		}

		// User has already visited.
		return false;
	}

	/**
	 * Get auto-update entry for a specific batch ID.
	 *
	 * @since    1.0.0
	 * @param    string $batch_id The batch ID to check.
	 * @return   array|false The auto-update entry if found, false otherwise.
	 */
	private function get_auto_update_for_batch( $batch_id ) {
		// Check if batch is from auto-update.
		$auto_update_batches = get_option( WCD_AUTO_UPDATE_COMPARISON_BATCHES, array() );
		if ( ! in_array( $batch_id, $auto_update_batches, true ) ) {
			return false;
		}

		// Find corresponding auto-update entry.
		$update_history = get_option( 'wcd_auto_update_history', array() );
		foreach ( $update_history as $entry ) {
			if ( isset( $entry['batch_id'] ) && $entry['batch_id'] === $batch_id ) {
				return $entry;
			}
		}
		return false;
	}

	/**
	 * Display comparison view accordion for batches with statistics and actions.
	 *
	 * @since    1.0.0
	 * @param    array $batches       The batches data from API.
	 * @param    mixed $failed_queues The failed queues data (optional, deprecated - use batch data instead).
	 * @return   void
	 */
	public function compare_view_v2( $batches, $failed_queues = false ) {
		if ( empty( $batches ) ) {
			?>
			<table style="width: 100%">
				<tr>
					<td colspan="5" style="text-align: center; background: #fff; height: 50px;">
						<strong><?php echo esc_html__( 'No change detections (yet).', 'webchangedetector' ); ?></strong><br>
						<?php echo esc_html__( 'Try different filters to show change detections.', 'webchangedetector' ); ?>
					</td>
				</tr>
			</table>
			<?php
			return;
		}

		$auto_update_batches = get_option( WCD_AUTO_UPDATE_COMPARISON_BATCHES );

		foreach ( $batches as $batch ) {
			$batch_id = $batch['id'];

			// Get failed count directly from batch data.
			$amount_failed = $batch['queues_count']['failed'] ?? 0;

			// Calculate console changes count (added + mixed).
			$console_changes_count = 0;
			if ( ! empty( $batch['browser_console_count'] ) ) {
				$console_changes_count = ( $batch['browser_console_count']['added'] ?? 0 ) + ( $batch['browser_console_count']['mixed'] ?? 0 );
			}

			// Calculate needs_attention from batch comparisons_count and console changes.
			$needs_attention = false;
			if ( isset( $batch['comparisons_count'] ) ) {
				$stats = $batch['comparisons_count'];
				// If there are any non-ok statuses, failed checks, or console changes, needs attention.
				if ( ( $stats['new'] ?? 0 ) > 0 || ( $stats['to_fix'] ?? 0 ) > 0 || $amount_failed > 0 || $console_changes_count > 0 ) {
					$needs_attention = true;
				}
			}

			// Get group from batch data - batches have group_id field.
			$batch_group = $batch['group_id'] ?? '';

			// Get created_at from batch data.
			$batch_finished_at = $batch['finished_at'] ?? __( 'processing...', 'webchangedetector' );

			// Get auto-update data if this batch is from an auto-update.
			$auto_update_data = $this->get_auto_update_for_batch( $batch_id );
			?>
			<div class="accordion-container" data-batch_id="<?php echo esc_attr( $batch_id ); ?>" data-failed_count="<?php echo esc_attr( $amount_failed ); ?>" data-console_changes_count="<?php echo esc_attr( $console_changes_count ); ?>" style="margin-top: 20px;">
				<div class="accordion accordion-batch">
					<div class="mm_accordion_title">
						<h3>
							<div style="display: inline-block;">
								<div class="accordion-batch-title-tile accordion-batch-title-tile-status">
									<?php
									if ( $needs_attention ) {
										\WebChangeDetector\WebChangeDetector_Admin_Utils::get_device_icon( 'warning', 'batch_needs_attention' );
										echo '<small>' . esc_html__( 'Needs Attention', 'webchangedetector' ) . '</small>';
									} else {
										\WebChangeDetector\WebChangeDetector_Admin_Utils::get_device_icon( 'check', 'batch_is_ok' );
										echo '<small>' . esc_html__( 'Looks Good', 'webchangedetector' ) . '</small>';
									}

									// Show browser console changes indicator (only for supported plans).
									$user_account               = $this->admin->account_handler->get_account();
									$user_plan                  = $user_account['plan'] ?? 'free';
									$can_access_browser_console = $this->admin->can_access_feature( 'browser_console', $user_plan );

									if ( $can_access_browser_console && $console_changes_count > 0 ) {
										$console_total = $console_changes_count;
										if ( $console_total > 0 ) {
											echo '<div class="wcd-console-badge-batch">';
											echo '<span class="dashicons dashicons-editor-code"></span>';
											echo '<span class="wcd-console-count">' . esc_html( $console_total ) . '</span>';
											echo '<span class="wcd-console-text">Console Change' . ( $console_total > 1 ? 's' : '' ) . '</span>';
											echo '</div>';
										}
									}

									if ( $amount_failed ) {
										/* translators: %d: number of failed checks */
										echo "<div style='font-size: 14px; color: darkred'> " . esc_html( sprintf( _n( '%d check failed', '%d checks failed', $amount_failed, 'webchangedetector' ), $amount_failed ) ) . '</div>';
									}
									?>
								</div>
								<div class="accordion-batch-title-tile">
									<?php

									if ( strpos( $batch['name'], 'Monitoring' ) === 0 ) {
										\WebChangeDetector\WebChangeDetector_Admin_Utils::get_device_icon( 'auto-group' );
										echo ' ' . esc_html__( 'Monitoring Checks', 'webchangedetector' );
									} elseif ( is_array( $auto_update_batches ) && in_array( $batch_id, $auto_update_batches, true ) ) {
										\WebChangeDetector\WebChangeDetector_Admin_Utils::get_device_icon( 'auto-update-group' );
										echo ' ' . esc_html__( 'Auto Update Checks', 'webchangedetector' );

										// Show auto-update summary if available.
										if ( $auto_update_data ) {
											$update_summary = array();
											if ( isset( $auto_update_data['updates']['core'] ) && $auto_update_data['updates']['core'] ) {
												$update_summary[] = __( 'Core', 'webchangedetector' );
											}
											$plugin_count = isset( $auto_update_data['updates']['plugins'] ) ? count( $auto_update_data['updates']['plugins'] ) : 0;
											if ( $plugin_count > 0 ) {
												// translators: %d: number of plugins.
												$update_summary[] = sprintf( _n( '%d plugin', '%d plugins', $plugin_count, 'webchangedetector' ), $plugin_count );
											}
											$theme_count = isset( $auto_update_data['updates']['themes'] ) ? count( $auto_update_data['updates']['themes'] ) : 0;
											if ( $theme_count > 0 ) {
												// translators: %d: number of themes.
												$update_summary[] = sprintf( _n( '%d theme', '%d themes', $theme_count, 'webchangedetector' ), $theme_count );
											}

											if ( ! empty( $update_summary ) ) {
												echo '<span style="color: #666; font-size: 12px; margin-left: 10px;">(' . esc_html( implode( ', ', $update_summary ) ) . ')</span>';
											}
										}
									} else {
										\WebChangeDetector\WebChangeDetector_Admin_Utils::get_device_icon( 'update-group' );
										echo ' ' . esc_html__( 'Manual Checks', 'webchangedetector' );
									}

									?>
									<br>
									<small>
										<?php
										if ( ! empty( $batch_finished_at ) && 'processing...' !== $batch_finished_at ) {
											/* translators: %s: time difference */
											echo esc_html( sprintf( __( '%s ago', 'webchangedetector' ), human_time_diff( gmdate( 'U' ), gmdate( 'U', strtotime( $batch_finished_at ) ) ) ) );
											echo ' (' . esc_html( get_date_from_gmt( $batch_finished_at, get_option( 'date_format' ) . ' ' . get_option( 'time_format' ) ) ) . ')';
										} else {
											echo esc_html__( 'processing...', 'webchangedetector' );
										}
										?>
									</small>
								</div>
								<div class="clear"></div>
							</div>
						</h3>
						<div class="mm_accordion_content">
							<div class="ajax_batch_comparisons_content">
								<div class="ajax-loading-container">
									<img src="<?php echo esc_url( $this->wordpress_handler->get_wcd_plugin_url() ); ?>/admin/img/loader.gif">
									<div><?php echo esc_html__( 'Loading', 'webchangedetector' ); ?></div>
								</div>

							</div>
						</div>
					</div>
				</div>
			</div>
			<?php
		}

		// Auto-click first accordion if only one batch.
		if ( 1 === count( $batches ) ) {
			echo '<script>
				jQuery(document).ready(function() {
					setTimeout(function() {
						jQuery(".accordion h3").first().click();
					}, 200);
				});
			</script>';
		}
	}

	/**
	 * Load comparisons view for a specific batch with pagination and filtering.
	 *
	 * @since    1.0.0
	 * @param    string $batch_id     The batch ID to load comparisons for.
	 * @param    array  $comparisons  The comparisons data from API.
	 * @param    array  $filters      The applied filters.
	 * @param    int    $console_changes_count  The console changes count for the batch (added + mixed).
	 * @return   void
	 */
	public function load_comparisons_view( $batch_id, $comparisons, $filters, $console_changes_count = 0 ) {
		// Check if this batch is from an auto-update and display details if so.
		$auto_update_data = $this->get_auto_update_for_batch( $batch_id );
		if ( $auto_update_data ) {
			?>
			<div class="wcd-auto-update-details" style="background: #f0f8ff; border: 1px solid #2271b1; border-radius: 4px; padding: 15px; margin-bottom: 20px;">
				<div style="display: flex; align-items: center; margin-bottom: 10px;">
					<span class="dashicons dashicons-update" style="color: #2271b1; margin-right: 8px; font-size: 20px;"></span>
					<strong style="color: #2271b1; font-size: 14px;"><?php esc_html_e( 'Related Auto-Update', 'webchangedetector' ); ?></strong>
					<span style="margin-left: auto; color: #666; font-size: 12px;">
						<?php echo esc_html( date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $auto_update_data['timestamp'] ) ); ?>
					</span>
				</div>
				
				<div style="margin-left: 28px;">
					<?php
					// Show summary of what was updated.
					$update_items = array();

					// Core update.
					if ( isset( $auto_update_data['updates']['core'] ) && $auto_update_data['updates']['core'] ) {
						$core           = $auto_update_data['updates']['core'];
						$status_icon    = $core['success'] ? '✓' : '✗';
						$status_color   = $core['success'] ? '#46b450' : '#dc3232';
						$update_items[] = sprintf(
							'<span style="color: %s;">%s</span> WordPress Core: %s → %s',
							$status_color,
							$status_icon,
							esc_html( $core['from_version'] ),
							esc_html( $core['to_version'] )
						);
					}

					// Plugin updates.
					if ( isset( $auto_update_data['updates']['plugins'] ) && ! empty( $auto_update_data['updates']['plugins'] ) ) {
						$successful_plugins = array_filter(
							$auto_update_data['updates']['plugins'],
							function ( $p ) {
								return $p['success'];
							}
						);
						$failed_plugins     = array_filter(
							$auto_update_data['updates']['plugins'],
							function ( $p ) {
								return ! $p['success'];
							}
						);

						if ( count( $successful_plugins ) > 0 ) {
							$update_items[] = sprintf(
								'<span style="color: #46b450;">✓</span> %s',
								// translators: %d: number of plugins.
								sprintf( _n( '%d plugin updated successfully', '%d plugins updated successfully', count( $successful_plugins ), 'webchangedetector' ), count( $successful_plugins ) )
							);
						}
						if ( count( $failed_plugins ) > 0 ) {
							$update_items[] = sprintf(
								'<span style="color: #dc3232;">✗</span> %s',
								// translators: %d: number of plugins.
								sprintf( _n( '%d plugin update failed', '%d plugin updates failed', count( $failed_plugins ), 'webchangedetector' ), count( $failed_plugins ) )
							);
						}
					}

					// Theme updates.
					if ( isset( $auto_update_data['updates']['themes'] ) && ! empty( $auto_update_data['updates']['themes'] ) ) {
						$successful_themes = array_filter(
							$auto_update_data['updates']['themes'],
							function ( $t ) {
								return $t['success'];
							}
						);
						$failed_themes     = array_filter(
							$auto_update_data['updates']['themes'],
							function ( $t ) {
								return ! $t['success'];
							}
						);

						if ( count( $successful_themes ) > 0 ) {
							$update_items[] = sprintf(
								'<span style="color: #46b450;">✓</span> %s',
								// translators: %d: number of themes.
								sprintf( _n( '%d theme updated successfully', '%d themes updated successfully', count( $successful_themes ), 'webchangedetector' ), count( $successful_themes ) )
							);
						}
						if ( count( $failed_themes ) > 0 ) {
							$update_items[] = sprintf(
								'<span style="color: #dc3232;">✗</span> %s',
								// translators: %d: number of themes.
								sprintf( _n( '%d theme update failed', '%d theme updates failed', count( $failed_themes ), 'webchangedetector' ), count( $failed_themes ) )
							);
						}
					}

					if ( ! empty( $update_items ) ) {
						echo '<ul style="margin: 10px 0; padding-left: 20px;">';
						foreach ( $update_items as $item ) {
							echo '<li style="margin: 5px 0;">' . esc_html( $item ) . '</li>';
						}
						echo '</ul>';
					}
					?>
					
					<div style="margin-top: 10px;">
						<a href="?page=webchangedetector-logs&tab=auto-updates" class="button button-small">
							<?php esc_html_e( 'View Full Update Details', 'webchangedetector' ); ?> →
						</a>
					</div>
				</div>
			</div>
			<?php
		}

		// Get failed queues for this batch.
		$failed_queues = \WebChangeDetector\WebChangeDetector_API_V2::get_queues_v2( $batch_id, 'failed', false, array( 'per_page' => 100 ) );

		// Handle pagination for failed queues if needed.
		if ( ! empty( $failed_queues['meta']['last_page'] ) && $failed_queues['meta']['last_page'] > 1 ) {
			for ( $i = 2; $i <= $failed_queues['meta']['pages']; $i++ ) {
				$failed_queues_data    = \WebChangeDetector\WebChangeDetector_API_V2::get_queues_v2(
					array( $batch_id ),
					'failed',
					array(
						'per_page' => 100,
						'page'     => $i,
					)
				);
				$failed_queues['data'] = array_merge( $failed_queues['data'], $failed_queues_data['data'] );
			}
		}

		// Display failed checks accordion if there are failed checks.
		if ( ! empty( $failed_queues['data'] ) ) {
			?>
			<div class="wcd-failed-checks-accordion" style="margin-bottom: 20px;">
				<div class="wcd-accordion-header">
					<div style="display: flex; align-items: center;">
						<span class="dashicons dashicons-warning" style="color: #d63638; margin-right: 8px;"></span>
						<strong><?php echo esc_html__( 'Failed Checks', 'webchangedetector' ); ?></strong>
						<span style="margin-left: 10px; color: #666;">
							<?php
							/* translators: %d: number of failed checks */
							echo esc_html( sprintf( _n( '%d failed check', '%d failed checks', count( $failed_queues['data'] ), 'webchangedetector' ), count( $failed_queues['data'] ) ) );
							?>
						</span>
					</div>
					<span class="dashicons dashicons-arrow-down-alt2 wcd-accordion-icon"></span>
				</div>
				<div class="wcd-accordion-content">
					<table class="widefat striped" style="margin: 0;">
						<thead>
							<tr>
								<th style="width: 70%;"><?php echo esc_html__( 'URL', 'webchangedetector' ); ?></th>
								<th style="width: 30%;"><?php echo esc_html__( 'Error Message', 'webchangedetector' ); ?></th>
							</tr>
						</thead>
						<tbody>
							<?php foreach ( $failed_queues['data'] as $failed_queue ) : ?>
								<tr>
									<td>
										<strong>
										<?php
												echo esc_url( $failed_queue['url_link'] );
										?>
										</strong>
										<?php if ( ! empty( $failed_queue['device'] ) ) : ?>
											<br><small style="color: #666;">
												<?php
												// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- get_device_icon already escapes output.
												echo \WebChangeDetector\WebChangeDetector_Admin_Utils::get_device_icon( $failed_queue['device'] );
												echo ' ' . esc_html( ucfirst( $failed_queue['device'] ) );
												?>
											</small>
										<?php endif; ?>
									</td>
									<td>
										<span style="color: #d63638;">
											<?php echo esc_html( $failed_queue['error_msg'] ?? __( 'Unknown error', 'webchangedetector' ) ); ?>
										</span>
									</td>
								</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
				</div>
			</div>

			<script>
				jQuery(document).ready(function($) {
					$('.wcd-failed-checks-accordion .wcd-accordion-header').on('click', function() {
						var $content = $(this).next('.wcd-accordion-content');
						var $icon = $(this).find('.wcd-accordion-icon');

						if ($content.is(':visible')) {
							$content.slideUp(300);
							$icon.removeClass('dashicons-arrow-up-alt2').addClass('dashicons-arrow-down-alt2');
						} else {
							$content.slideDown(300);
							$icon.removeClass('dashicons-arrow-down-alt2').addClass('dashicons-arrow-up-alt2');
						}
					});
				});
			</script>
			<?php
		}

		if ( empty( $comparisons['data'] ) ) {
			?>
			<table style="width: 100%">
				<tr>
					<td colspan="5" style="text-align: center; background: #fff; height: 50px;">
						<strong><?php echo esc_html__( 'No comparisons found for this batch.', 'webchangedetector' ); ?></strong>
					</td>
				</tr>
			</table>
			<?php
			return;
		}

		$compares   = $comparisons['data'];
		$all_tokens = array();

		foreach ( $compares as $compare ) {
			$all_tokens[] = $compare['id'];
		}

		?>
		<table class="wcd-comparison-table toggle" style="width: 100%">
			<tr>
				<th style="min-width: 140px; text-align: center;"><?php echo esc_html__( 'Status', 'webchangedetector' ); ?></th>
				<th style="width: 100%"><?php echo esc_html__( 'URL', 'webchangedetector' ); ?></th>
				<th style="min-width: 200px"><?php echo esc_html__( 'Compared Screenshots', 'webchangedetector' ); ?></th>
				<th style="min-width: 50px"><?php echo esc_html__( 'Difference', 'webchangedetector' ); ?></th>
			</tr>

			<?php
			// Show comparisons.
			foreach ( $compares as $compare ) {
				if ( empty( $compare['status'] ) ) {
					$compare['status'] = 'new';
				}

				$class = 'no-difference'; // init.
				if ( $compare['difference_percent'] ) {
					$class = 'is-difference';
				}

				?>
				<tr id="comparison-<?php echo esc_html( $compare['id'] ); ?>">
					<td>
						<div class="comparison_status_container">
							<span class="current_comparison_status comparison_status comparison_status_<?php echo esc_html( $compare['status'] ); ?>">
								<?php echo esc_html( \WebChangeDetector\WebChangeDetector_Admin_Utils::get_comparison_status_name( $compare['status'] ) ); ?>
							</span>

							<div class="change_status" style="display: none; position: absolute; background: #fff; padding: 20px; box-shadow: 0 0 5px #aaa;">
								<strong><?php echo esc_html__( 'Change Status to:', 'webchangedetector' ); ?></strong><br>
								<?php $nonce = \WebChangeDetector\WebChangeDetector_Admin_Utils::create_nonce( 'ajax-nonce' ); ?>
								<button name="status"
									data-id="<?php echo esc_html( $compare['id'] ); ?>"
									data-status="ok"
									data-nonce="<?php echo esc_html( $nonce ); ?>"
									value="ok"
									class="ajax_update_comparison_status comparison_status comparison_status_ok"
									onclick="return false;"><?php echo esc_html__( 'Ok', 'webchangedetector' ); ?></button>
								<button name="status"
									data-id="<?php echo esc_html( $compare['id'] ); ?>"
									data-status="to_fix"
									data-nonce="<?php echo esc_html( $nonce ); ?>"
									value="to_fix"
									class="ajax_update_comparison_status comparison_status comparison_status_to_fix"
									onclick="return false;"><?php echo esc_html__( 'To Fix', 'webchangedetector' ); ?></button>
								<button name="status"
									data-id="<?php echo esc_html( $compare['id'] ); ?>"
									data-status="false_positive"
									data-nonce="<?php echo esc_html( $nonce ); ?>"
									value="false_positive"
									class="ajax_update_comparison_status comparison_status comparison_status_false_positive"
									onclick="return false;"><?php echo esc_html__( 'False Positive', 'webchangedetector' ); ?></button>
							</div>
							<?php
							// Show console changes badge if present.
							$user_account               = $this->admin->account_handler->get_account();
							$user_plan                  = $user_account['plan'] ?? 'free';
							$can_access_browser_console = $this->admin->can_access_feature( 'browser_console', $user_plan );

							if ( $can_access_browser_console && ! empty( $compare['browser_console_change'] ) && 'unchanged' !== $compare['browser_console_change'] ) {
								$console_added   = count( $compare['browser_console_added'] ?? array() );
								$console_removed = count( $compare['browser_console_removed'] ?? array() );
								$console_total   = $console_added + $console_removed;

								if ( $console_total > 0 ) {
									echo '<div class="wcd-console-badge-comparison">';
									echo '<span class="dashicons dashicons-editor-code"></span>';
									echo '<span class="wcd-console-count">' . esc_html( $console_total ) . '</span>';
									echo '<span class="wcd-console-text">Console Change' . ( $console_total > 1 ? 's' : '' ) . '</span>';
									echo '</div>';
								}
							}
							?>
						</div>
					</td>
					<td>
						<strong>
							<?php
							if ( ! empty( $compare['html_title'] ) ) {
								echo esc_html( $compare['html_title'] ) . '<br>';
							}
							?>
						</strong>
						<?php
						echo esc_url( $compare['url'] ) . '<br>';
						\WebChangeDetector\WebChangeDetector_Admin_Utils::get_device_icon( $compare['device'] );
						echo esc_html( ucfirst( $compare['device'] ) );
						?>
					</td>
					<td>
						<div><?php echo esc_html( get_date_from_gmt( $compare['screenshot_1_created_at'], get_option( 'date_format' ) . ' ' . get_option( 'time_format' ) ) ); ?></div>
						<div><?php echo esc_html( get_date_from_gmt( $compare['screenshot_2_created_at'], get_option( 'date_format' ) . ' ' . get_option( 'time_format' ) ) ); ?></div>
					</td>
					<td class="<?php echo esc_html( $class ); ?> diff-tile"
						data-diff_percent="<?php echo esc_html( $compare['difference_percent'] ); ?>">
						<?php echo esc_html( \WebChangeDetector\WebChangeDetector_Admin_Utils::format_difference_percent( $compare['difference_percent'] ) ); ?>%
					</td>
					<td data-comparison_id="<?php echo esc_html( $compare['id'] ); ?>" style="display: none;">
						<form action="<?php echo esc_url( admin_url( 'admin.php?page=webchangedetector-show-detection&id=' . esc_html( $compare['id'] ) ) ); ?>" method="post">
							<?php wp_nonce_field( 'show_change_detection', '_wpnonce' ); ?>
							<input type="hidden" name="all_tokens" value='<?php echo wp_json_encode( $all_tokens ); ?>'>
							<input type="submit" value="<?php echo esc_attr__( 'Show', 'webchangedetector' ); ?>" class="button">
						</form>
					</td>
				</tr>
			<?php } ?>
		</table>
		<script>
			jQuery("tr").click(function(e) {
				// Don't trigger row click if clicking on status-related elements.
				if (jQuery(e.target).closest('.comparison_status_container, .current_comparison_status, .comparison_status, .ajax_update_comparison_status, .change_status').length > 0) {
					return;
				}
				jQuery(this).find("td[data-comparison_id='" + jQuery(this).attr("id").replace("comparison-", "") + "']").find("form").submit();
			});
		</script>
		<?php
		// Add pagination if needed.
		if ( ! empty( $comparisons['meta']['links'] ) ) {
			?>
			<div class="tablenav">
				<div class="tablenav-pages">
					<span class="pagination-links">
						<?php
						foreach ( $comparisons['meta']['links'] as $link ) {
							$url_params = \WebChangeDetector\WebChangeDetector_Admin_Utils::get_params_of_url( $link['url'] );
							$class      = ! $link['url'] || $link['active'] ? 'disabled' : '';
							$page       = $url_params['page'] ?? 1;
							?>
							<button class="ajax_paginate_batch_comparisons tablenav-pages-navspan button <?php echo esc_html( $class ); ?>"
								data-page="<?php echo esc_html( $page ); ?>"
								data-filters="<?php echo esc_attr( wp_json_encode( $filters ) ); ?>"
								<?php echo ( 'disabled' === $class ) ? 'disabled' : ''; ?>>
								<?php echo esc_html( $link['label'] ); ?>
							</button>
							<?php
						}
						?>
					</span>
					<span class="displaying-num"><?php echo esc_html( $comparisons['meta']['total'] ?? 0 ); ?> <?php echo esc_html__( 'items', 'webchangedetector' ); ?></span>
				</div>
			</div>
			<?php
		}
	}

	/**
	 * Load failed queues view for a specific batch via AJAX.
	 *
	 * @since    1.0.0
	 * @param    string $batch_id The batch ID.
	 * @return   void
	 */
	public function load_failed_queues_view( $batch_id ) {
		$failed_queues = \WebChangeDetector\WebChangeDetector_API_V2::get_queues_v2( array( $batch_id ), 'failed', array( 'per_page' => 100 ) );

		// Handle pagination for failed queues if needed.
		if ( ! empty( $failed_queues['meta']['last_page'] ) && $failed_queues['meta']['last_page'] > 1 ) {
			for ( $i = 2; $i <= $failed_queues['meta']['pages']; $i++ ) {
				$failed_queues_data    = \WebChangeDetector\WebChangeDetector_API_V2::get_queues_v2(
					$batch_id,
					'failed',
					array(
						'per_page' => 100,
						'page'     => $i,
					)
				);
				$failed_queues['data'] = array_merge( $failed_queues['data'], $failed_queues_data['data'] );
			}
		}

		if ( empty( $failed_queues['data'] ) ) {
			echo '<div style="padding: 20px; text-align: center; color: #666;">' . esc_html__( 'No failed URLs found for this batch.', 'webchangedetector' ) . '</div>';
			return;
		}
		?>
		<table class="toggle" style="margin: 0;">
			<tr class="table-headline-row">
				<th><?php echo esc_html__( 'Status', 'webchangedetector' ); ?></th>
				<th style="width:auto"><?php echo esc_html__( 'URL', 'webchangedetector' ); ?></th>
				<th style="width:250px"><?php echo esc_html__( 'Compared Screenshots', 'webchangedetector' ); ?></th>
				<th style="width:100px"><?php echo esc_html__( 'Difference', 'webchangedetector' ); ?></th>
			</tr>
			<?php
			foreach ( $failed_queues['data'] as $failed_queue ) {
				if ( $batch_id === $failed_queue['batch'] ) {
					?>
					<tr style="background-color: rgba(220, 50, 50, 0.1);">
						<td>
							<div class="comparison_status_container">
								<span class="current_comparison_status comparison_status comparison_status_failed">
									<?php echo esc_html( \WebChangeDetector\WebChangeDetector_Admin_Utils::get_comparison_status_name( 'failed' ) ); ?>
								</span>
							</div>
						</td>
						<td>
							<?php
							if ( ! empty( $failed_queue['html_title'] ) ) {
								echo '<strong>' . esc_html( $failed_queue['html_title'] ) . '</strong><br>';
							}
							\WebChangeDetector\WebChangeDetector_Admin_Utils::get_device_icon( $failed_queue['device'] );
							echo esc_html( $failed_queue['url_link'] );
							?>
						</td>
						<td class="table-row-compared-screenshots-failed"><?php echo esc_html__( 'n/a', 'webchangedetector' ); ?></td>
						<td class="table-row-diff-tile-failed">
							<div style="text-align: center;"><?php echo esc_html__( 'n/a', 'webchangedetector' ); ?></div>
						</td>
					</tr>
					<?php
				}
			}
			?>
		</table>
		<?php
	}

	/**
	 * Get detailed comparison view by token with navigation controls.
	 *
	 * @since    1.0.0
	 * @param    array $postdata    The posted data containing token and navigation info.
	 * @param    bool  $hide_switch Deprecated parameter.
	 * @param    bool  $whitelabel  Deprecated parameter.
	 * @return   void
	 */
	public function get_comparison_by_token( $postdata, $hide_switch = false, $whitelabel = false ) {
		$token = $postdata['token'] ?? null;

		if ( empty( $_POST['_wpnonce'] ) || ! wp_verify_nonce( wp_unslash( sanitize_key( $_POST['_wpnonce'] ) ), 'show_change_detection' ) ) {
			if ( empty( $_GET['_wpnonce'] ) || ! wp_verify_nonce( wp_unslash( sanitize_key( $_GET['_wpnonce'] ) ), 'show_change_detection' ) ) {
				echo esc_html__( 'Something went wrong. Please try again.', 'webchangedetector' );
				wp_die();
			}
		}

		if ( ! $token && ! empty( $_GET['id'] ) ) {
			$token = sanitize_text_field( wp_unslash( $_GET['id'] ) );
		}

		if ( isset( $token ) ) {
			$compare = \WebChangeDetector\WebChangeDetector_API_V2::get_comparison_v2( $token )['data'];

			$public_token = $compare['token'];
			$all_tokens   = array();
			if ( ! empty( $postdata['all_tokens'] ) ) {
				$all_tokens = ( json_decode( stripslashes( $postdata['all_tokens'] ), true ) );

				$before_current_token = array();
				$after_current_token  = array();
				$is_after             = false;
				foreach ( $all_tokens as $current_token ) {
					if ( $current_token !== $token ) {
						if ( $is_after ) {
							$after_current_token[] = $current_token;
						} else {
							$before_current_token[] = $current_token;
						}
					} else {
						$is_after = true;
					}
				}
			}

			if ( ! $hide_switch ) {
				echo '<style>#comp-switch {display: none !important;}</style>';
			}
			echo '<div style="padding: 0 20px;">';
			if ( ! $whitelabel ) {
				echo '<style>.public-detection-logo {display: none;}</style>';
			}
			$before_token = ! empty( $before_current_token ) ? $before_current_token[ max( array_keys( $before_current_token ) ) ] : null;
			$after_token  = $after_current_token[0] ?? null;
			?>
			<!-- Previous and next buttons -->
			<div style="width: 100%; margin-bottom: 20px; text-align: center; margin-left: auto; margin-right: auto">
				<form action="<?php echo esc_url( admin_url( 'admin.php?page=webchangedetector-show-detection&id=' . ( esc_html( $before_token ) ?? '' ) ) ); ?>" method="post" style="display:inline-block;">
					<?php wp_nonce_field( 'show_change_detection', '_wpnonce' ); ?>
					<input type="hidden" name="all_tokens" value='<?php echo wp_json_encode( $all_tokens ); ?>'>
					<button class="button" type="submit" name="token"
						value="<?php echo esc_html( $before_token ) ?? null; ?>" <?php echo ! $before_token ? 'disabled' : ''; ?>> &lt; <?php echo esc_html__( 'Previous', 'webchangedetector' ); ?> </button>
				</form>
				<form action="<?php echo esc_url( admin_url( 'admin.php?page=webchangedetector-show-detection&id=' . ( esc_html( $after_token ) ?? '' ) ) ); ?>" method="post" style="display:inline-block;">
					<?php wp_nonce_field( 'show_change_detection', '_wpnonce' ); ?>
					<input type="hidden" name="all_tokens" value='<?php echo wp_json_encode( $all_tokens ); ?>'>
					<button class="button" type="submit" name="token"
						value="<?php echo esc_html( $after_token ) ?? null; ?>" <?php echo ! $after_token ? 'disabled' : ''; ?>> <?php echo esc_html__( 'Next', 'webchangedetector' ); ?> &gt; </button>
				</form>
			</div>
			<?php
			$this->admin->view_renderer->get_component( 'templates' )->render_show_change_detection( $compare );
			echo '</div>';
		} else {
			?>
			<p class="notice notice-error" style="padding: 10px;">
				<?php
				printf(
					/* translators: %s: link to change detections page */
					esc_html__( 'Ooops! There was no change detection selected. Please go to %s and select a change detection to show.', 'webchangedetector' ),
					'<a href="?page=webchangedetector-change-detections">' . esc_html__( 'Change Detections', 'webchangedetector' ) . '</a>'
				);
				?>
			</p>
			<?php
		}
	}
}
