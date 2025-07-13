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
	 * Reference to the API manager instance.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      WebChangeDetector_API_Manager    $api_manager    The API manager instance.
	 */
	private $api_manager;

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
	 * @param    WebChangeDetector_API_Manager     $api_manager  The API manager instance.
	 * @param    WebChangeDetector_Admin_WordPress $wordpress_handler The WordPress handler instance.
	 */
	public function __construct( $admin, $api_manager, $wordpress_handler ) {
		$this->admin       = $admin;
		$this->api_manager = $api_manager;
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
					<input type="button" class="button button-primary" value="<?php echo esc_attr__( 'Start Wizard', 'webchangedetector' ); ?>" onclick="window.wcdStartWizard()">
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

				$batches = \WebChangeDetector\WebChangeDetector_API_V2::get_batches( $filter_batches );
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
	 * Show account activation screen with error handling.
	 *
	 * @since    1.0.0
	 * @param    string $error The error type to display.
	 * @return   false
	 */
	public function show_activate_account( $error ) {
		if ( 'ActivateAccount' === $error ) {
			?>
			<div class="notice notice-info">
				<p><?php echo esc_html__( 'Please activate your account.', 'webchangedetector' ); ?></p>
			</div>
			<div class="activate-account highlight-container" >
			<div class="highlight-inner">
				<h2>
					<?php echo esc_html__( 'Activate account', 'webchangedetector' ); ?>
				</h2>
				<p>
					<?php echo esc_html__( 'We just sent you an activation mail.', 'webchangedetector' ); ?>
				</p>
				<?php if ( get_option( WCD_WP_OPTION_KEY_ACCOUNT_EMAIL ) ) { ?>
					<div style="margin: 0 auto; padding: 15px; border-radius: 5px;background: #5db85c; color: #fff; max-width: 400px;">
						<span id="activation_email" style="font-weight: 700;"><?php echo esc_html( get_option( WCD_WP_OPTION_KEY_ACCOUNT_EMAIL ) ); ?></span>
					</div>
				<?php } ?>
				<p>
					<?php echo esc_html__( 'After clicking the activation link in the mail, your account is ready. Check your spam folder if you cannot find the activation mail in your inbox.', 'webchangedetector' ); ?>
				</p>
			</div>
			<div>
				<h2><?php echo esc_html__( "You didn't receive the email?", 'webchangedetector' ); ?></h2>
				<p>
					<?php
					printf(
						/* translators: %1$s: support email address, %2$s: website URL */
						esc_html__( 'Please contact us at %1$s or use our live chat at %2$s. We are happy to help.', 'webchangedetector' ),
						'<a href="mailto:support@webchangedetector.com">support@webchangedetector.com</a>',
						'<a href="https://www.webchangedetector.com">webchangedetector.com</a>'
					);
					?>
				</p>
				<p><?php echo esc_html__( 'To reset your account, please click the button below.', 'webchangedetector' ); ?></p>
				<form id="delete-account" method="post">
					<input type="hidden" name="wcd_action" value="reset_api_token">
					<?php wp_nonce_field( 'reset_api_token' ); ?>
					<input type="submit" class="button-delete" value="<?php echo esc_attr__( 'Reset Account', 'webchangedetector' ); ?>">
				</form>

			</div>
		</div>
			<?php
		}

		if ( 'unauthorized' === $error ) {
			?>
			<div class="notice notice-error">
				<p><?php echo esc_html__( 'The API token is not valid. Please reset the API token and enter a valid one.', 'webchangedetector' ); ?></p>
			</div>
			<?php
			$this->admin->account_handler->get_no_account_page();
		}

		return false;
	}

	/**
	 * Check if this is the first time the user is visiting the dashboard.
	 *
	 * @since    1.0.0
	 * @return   bool True if first time visit, false otherwise.
	 */
	public function is_first_time_dashboard_visit() {
		$user_id = get_current_user_id();
		$meta_key = 'wcd_first_time_dashboard_visit';
		
		// Debug: Allow resetting first-time visit with URL parameter
		if ( isset( $_GET['wcd_reset_first_time'] ) && current_user_can( 'manage_options' ) ) {
			delete_user_meta( $user_id, $meta_key );
			wp_redirect( remove_query_arg( 'wcd_reset_first_time' ) );
			exit;
		}
		
		// Check user meta first (per-user setting)
		$user_visited = get_user_meta( $user_id, $meta_key, true );
		
		if ( 'visited' !== $user_visited ) {
			// Mark user as visited
			update_user_meta( $user_id, $meta_key, 'visited' );
			return true;
		}
		
		// User has already visited
		return false;
	}

	/**
	 * Display comparison view accordion for batches with statistics and actions.
	 *
	 * @since    1.0.0
	 * @param    array $batches       The batches data from API.
	 * @param    mixed $failed_queues The failed queues data (optional).
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

			$amount_failed = 0;
			if ( ! empty( $failed_queues['data'] ) ) {
				foreach ( $failed_queues['data'] as $failed_queue ) {
					if ( $failed_queue['batch'] === $batch_id ) {
						++$amount_failed;
					}
				}
			}

			// Calculate needs_attention from batch statistics.
			$needs_attention = false;
			if ( isset( $batch['statistics'] ) ) {
				$stats = $batch['statistics'];
				// If there are any non-ok statuses, needs attention.
				if ( ( $stats['new'] ?? 0 ) > 0 || ( $stats['to_fix'] ?? 0 ) > 0 || $amount_failed > 0 ) {
					$needs_attention = true;
				}
			}

			// Get group from batch data - batches have group_id field.
			$batch_group = $batch['group_id'] ?? '';

			// Get created_at from batch data.
			$batch_finished_at = $batch['finished_at'] ?? __( 'processing...', 'webchangedetector' );
			?>
			<div class="accordion-container" data-batch_id="<?php echo esc_attr( $batch_id ); ?>" data-failed_count="<?php echo esc_attr( $amount_failed ); ?>" style="margin-top: 20px;">
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
									if ( $amount_failed ) {
										/* translators: %d: number of failed checks */
										echo "<div style='font-size: 14px; color: darkred'> " . esc_html( sprintf( _n( '%d check failed', '%d checks failed', $amount_failed, 'webchangedetector' ), $amount_failed ) ) . '</div>';
									}
									?>
								</div>
								<div class="accordion-batch-title-tile">
									<?php
																	if ( $batch_group === $this->admin->monitoring_group_uuid ) {
									\WebChangeDetector\WebChangeDetector_Admin_Utils::get_device_icon( 'auto-group' );
									echo ' ' . esc_html__( 'Monitoring Checks', 'webchangedetector' );
								} elseif ( is_array( $auto_update_batches ) && in_array( $batch_id, $auto_update_batches, true ) ) {
									\WebChangeDetector\WebChangeDetector_Admin_Utils::get_device_icon( 'auto-update-group' );
									echo ' ' . esc_html__( 'Auto Update Checks', 'webchangedetector' );
								} else {
									\WebChangeDetector\WebChangeDetector_Admin_Utils::get_device_icon( 'update-group' );
									echo ' ' . esc_html__( 'Manual Checks', 'webchangedetector' );
								}

								// Show browser console changes indicator (only for supported plans).
								$user_account = $this->admin->account_handler->get_account();
								$user_plan = $user_account['plan'] ?? 'free';
								$canAccessBrowserConsole = $this->admin->can_access_feature('browser_console', $user_plan);

								if ($canAccessBrowserConsole && !empty($batch['console_changes_count'])) {
									$consoleTotal = $batch['console_changes_count'];
									if ($consoleTotal > 0) {
										echo '<div class="console-indicator-badge" style="display: inline-flex; align-items: center; background: #2271b1; color: white; padding: 4px 8px; border-radius: 12px; font-size: 11px; margin: 0 5px; vertical-align: middle;">';
										echo '<span class="dashicons dashicons-editor-code" style="font-size: 14px; margin-right: 4px;"></span>';
										echo '<span class="console-count" style="font-weight: bold; margin-right: 4px;">' . $consoleTotal . '</span>';
										echo '<span class="console-text" style="font-size: 10px;">Console Change' . ($consoleTotal > 1 ? 's' : '') . '</span>';
										echo '</div>';
									}
								}
								?>
								<br>
									<small>
										<?php
										if ( ! empty( $batch_finished_at ) && 'processing...' !== $batch_finished_at ) {
											/* translators: %s: time difference */
											echo esc_html( sprintf( __( '%s ago', 'webchangedetector' ), human_time_diff( gmdate( 'U' ), gmdate( 'U', strtotime( $batch_finished_at ) ) ) ) );
											echo ' (' . esc_html( get_date_from_gmt( $batch_finished_at ) ) . ')';
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
									<img decoding="async" src="<?php echo esc_url( $this->wordpress_handler->get_wcd_plugin_url() ); ?>/admin/img/loader.gif">
									<div style="text-align: center;"><?php echo esc_html__( 'Loading', 'webchangedetector' ); ?></div>
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
	 * @return   void
	 */
	public function load_comparisons_view( $batch_id, $comparisons, $filters ) {
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
		<table class="toggle" style="width: 100%">
			<tr>
				<th style="min-width: 120px;"><?php echo esc_html__( 'Status', 'webchangedetector' ); ?></th>
				<th style="width: 100%"><?php echo esc_html__( 'URL', 'webchangedetector' ); ?></th>
				<th style="min-width: 150px"><?php echo esc_html__( 'Compared Screenshots', 'webchangedetector' ); ?></th>
				<th style="min-width: 50px"><?php echo esc_html__( 'Difference', 'webchangedetector' ); ?></th>
				<th><?php echo esc_html__( 'Show', 'webchangedetector' ); ?></th>
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
				<tr>
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
						<div><?php echo esc_html( get_date_from_gmt( $compare['screenshot_1_created_at'] ) ); ?></div>
						<div><?php echo esc_html( get_date_from_gmt( $compare['screenshot_2_created_at'] ) ); ?></div>
					</td>
					<td class="<?php echo esc_html( $class ); ?> diff-tile"
						data-diff_percent="<?php echo esc_html( $compare['difference_percent'] ); ?>">
						<?php echo esc_html( $compare['difference_percent'] ); ?>%
					</td>
					<td>
						<form action="<?php echo esc_url( admin_url( 'admin.php?page=webchangedetector-show-detection&id=' . esc_html( $compare['id'] ) ) ); ?>" method="post">
							<?php wp_nonce_field( 'show_change_detection', '_wpnonce' ); ?>
							<input type="hidden" name="all_tokens" value='<?php echo wp_json_encode( $all_tokens ); ?>'>
							<input type="submit" value="<?php echo esc_attr__( 'Show', 'webchangedetector' ); ?>" class="button">
						</form>
					</td>
				</tr>
			<?php } ?>
		</table>

		<?php
		// Add pagination if needed.
		if ( ! empty( $comparisons['meta']['links'] ) ) {
			?>
			<div class="tablenav" >
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
						<td class="table-row-diff-tile-failed"><div style="text-align: center;"><?php echo esc_html__( 'n/a', 'webchangedetector' ); ?></div></td>
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