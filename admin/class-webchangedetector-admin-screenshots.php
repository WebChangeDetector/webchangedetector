<?php
/**
 * WebChange Detector Admin Screenshots Management Class
 *
 * Handles all screenshot and comparison related functionality including
 * screenshot display, comparison status management, and view rendering.
 *
 * @since      1.0.0
 * @package    WebChangeDetector
 * @subpackage WebChangeDetector/admin
 * @author     Mike Miler <mike@wp-mike.com>
 */

namespace WebChangeDetector;

/**
 * Screenshot and comparison management class.
 *
 * Manages screenshot display, comparison status updates, comparison views,
 * failed queue views, and comparison token handling.
 *
 * @since      1.0.0
 * @package    WebChangeDetector
 * @subpackage WebChangeDetector/admin
 * @author     Mike Miler <mike@wp-mike.com>
 */
class WebChangeDetector_Admin_Screenshots {

	/**
	 * Reference to the main admin class.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      WebChangeDetector_Admin    $admin    The main admin class instance.
	 */
	private $admin;

	/**
	 * Initialize the screenshots class.
	 *
	 * @since    1.0.0
	 * @param    WebChangeDetector_Admin $admin    The main admin class instance.
	 */
	public function __construct( $admin ) {
		$this->admin = $admin;
	}

	/**
	 * Update comparison status.
	 *
	 * Updates the status of a comparison using the API.
	 *
	 * @since    1.0.0
	 * @param    string $id     The comparison ID.
	 * @param    string $status The new status.
	 * @return   mixed          The API response.
	 */
	public function update_comparison_status( $id, $status ) {
		return \WebChangeDetector\WebChangeDetector_API_V2::update_comparison_v2( $id, $status );
	}

	/**
	 * Get nice display name for comparison status.
	 *
	 * Converts comparison status codes to user-friendly display names.
	 *
	 * @since    1.0.0
	 * @param    string $status The comparison status code.
	 * @return   string         The nice display name for the status.
	 */
	public function get_comparison_status_nice_name( $status ) {
		switch ( $status ) {
			case 'ok':
				return __( 'Ok', 'webchangedetector' );
			case 'to_fix':
				return __( 'To Fix', 'webchangedetector' );
			case 'false_positive':
				return __( 'False Positive', 'webchangedetector' );
			case 'failed':
				return __( 'Failed', 'webchangedetector' );
			default:
				return __( 'New', 'webchangedetector' );
		}
	}

	/**
	 * Load and display comparisons view for batch.
	 *
	 * Renders the comparisons table view for a specific batch with filters,
	 * pagination, and status management functionality.
	 *
	 * @since    1.0.0
	 * @param    string $batch_id     The batch ID.
	 * @param    array  $comparisons  The comparisons data from API.
	 * @param    array  $filters      Applied filters for the view.
	 * @return   void
	 */
	public function load_comparisons_view( $batch_id, $comparisons, $filters ) {
		if ( empty( $comparisons['data'] ) ) {
			?>
			<table style="width: 100%">
				<tr>
					<td colspan="5" style="text-align: center; background: #fff; height: 50px;">
						<strong><?php esc_html_e( 'No comparisons found for this batch.', 'webchangedetector' ); ?></strong>
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
				<th style="min-width: 120px;"><?php esc_html_e( 'Status', 'webchangedetector' ); ?></th>
				<th style="width: 100%"><?php esc_html_e( 'URL', 'webchangedetector' ); ?></th>
				<th style="min-width: 150px"><?php esc_html_e( 'Compared Screenshots', 'webchangedetector' ); ?></th>
				<th style="min-width: 50px"><?php esc_html_e( 'Difference', 'webchangedetector' ); ?></th>
				<th><?php esc_html_e( 'Show', 'webchangedetector' ); ?></th>
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
								<strong><?php esc_html_e( 'Change Status to:', 'webchangedetector' ); ?></strong><br>
								<?php $nonce = \WebChangeDetector\WebChangeDetector_Admin_Utils::create_nonce( 'ajax-nonce' ); ?>
								<button name="status"
										data-id="<?php echo esc_html( $compare['id'] ); ?>"
										data-status="ok"
										data-nonce="<?php echo esc_html( $nonce ); ?>"
										value="ok"
										class="ajax_update_comparison_status comparison_status comparison_status_ok"
										onclick="return false;"><?php esc_html_e( 'Ok', 'webchangedetector' ); ?></button>
								<button name="status"
										data-id="<?php echo esc_html( $compare['id'] ); ?>"
										data-status="to_fix"
										data-nonce="<?php echo esc_html( $nonce ); ?>"
										value="to_fix"
										class="ajax_update_comparison_status comparison_status comparison_status_to_fix"
										onclick="return false;"><?php esc_html_e( 'To Fix', 'webchangedetector' ); ?></button>
								<button name="status"
										data-id="<?php echo esc_html( $compare['id'] ); ?>"
										data-status="false_positive"
										data-nonce="<?php echo esc_html( $nonce ); ?>"
										value="false_positive"
										class="ajax_update_comparison_status comparison_status comparison_status_false_positive"
										onclick="return false;"><?php esc_html_e( 'False Positive', 'webchangedetector' ); ?></button>
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
						echo \WebChangeDetector\WebChangeDetector_Admin_Utils::get_device_icon( $compare['device'] );
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
						<form action="<?php echo esc_html( wp_nonce_url( '?page=webchangedetector-show-detection&id=' . esc_html( $compare['id'] ) ) ); ?>" method="post">
							<input type="hidden" name="all_tokens" value='<?php echo wp_json_encode( $all_tokens ); ?>'>
							<input type="submit" value="<?php esc_attr_e( 'Show', 'webchangedetector' ); ?>" class="button">
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
							$url_params = $this->admin->get_params_of_url( $link['url'] );
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
					<span class="displaying-num"><?php echo esc_html( $comparisons['meta']['total'] ?? 0 ); ?> <?php esc_html_e( 'items', 'webchangedetector' ); ?></span>
				</div>
			</div>
			<?php
		}
	}

	/**
	 * Load and display failed queues view for batch.
	 *
	 * Renders the failed URLs view for a specific batch showing all
	 * failed screenshot queue items.
	 *
	 * @since    1.0.0
	 * @param    string $batch_id The batch ID to load failed queues for.
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
		<table class="toggle" style="width: 100%">
			<tr>
				<th style="width: 100%"><?php esc_html_e( 'Failed URLs', 'webchangedetector' ); ?></th>
				<th style="min-width: 120px;"><?php esc_html_e( 'Device', 'webchangedetector' ); ?></th>
				<th style="min-width: 150px"><?php esc_html_e( 'Error Message', 'webchangedetector' ); ?></th>
			</tr>

			<?php
			foreach ( $failed_queues['data'] as $failed_queue ) {
				?>
				<tr>
					<td>
						<strong>
							<?php
							if ( ! empty( $failed_queue['html_title'] ) ) {
								echo esc_html( $failed_queue['html_title'] ) . '<br>';
							}
							?>
						</strong>
						<?php echo esc_url( $failed_queue['url'] ); ?>
					</td>
					<td>
						<?php
						echo \WebChangeDetector\WebChangeDetector_Admin_Utils::get_device_icon( $failed_queue['device'] );
						echo esc_html( ucfirst( $failed_queue['device'] ) );
						?>
					</td>
					<td>
						<?php echo esc_html( $failed_queue['error_message'] ?? __( 'Unknown error occurred', 'webchangedetector' ) ); ?>
					</td>
				</tr>
				<?php
			}
			?>
		</table>
		<?php
	}

	/**
	 * Get and display comparison by token.
	 *
	 * Displays a specific comparison by its token with navigation controls
	 * and comparison details view.
	 *
	 * @since    1.0.0
	 * @param    array $postdata    The POST data containing token and navigation data.
	 * @param    bool  $hide_switch Whether to hide the comparison switch controls.
	 * @param    bool  $whitelabel  Whether to show in whitelabel mode.
	 * @return   void
	 */
	public function get_comparison_by_token( $postdata, $hide_switch = false, $whitelabel = false ) {
		$token = $postdata['token'] ?? null;

		// Verify nonce for security.
		if ( empty( $_GET['_wpnonce'] ) || ! \WebChangeDetector\WebChangeDetector_Admin_Utils::verify_nonce( wp_unslash( sanitize_key( $_GET['_wpnonce'] ) ), 'ajax-nonce' ) ) {
			echo esc_html__( 'Something went wrong. Please try again.', 'webchangedetector' );
			wp_die();
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
				<form action="<?php echo esc_html( wp_nonce_url( '?page=webchangedetector-show-detection&id=' . ( esc_html( $before_token ) ?? null ) ) ); ?>" method="post" style="display:inline-block;">
					<input type="hidden" name="all_tokens" value='<?php echo wp_json_encode( $all_tokens ); ?>'>
					<button class="button" type="submit" name="token"
							value="<?php echo esc_html( $before_token ) ?? null; ?>" <?php echo ! $before_token ? 'disabled' : ''; ?>>
						< <?php esc_html_e( 'Previous', 'webchangedetector' ); ?>
					</button>
				</form>
				<form action="<?php echo esc_html( wp_nonce_url( '?page=webchangedetector-show-detection&id=' . ( esc_html( $after_token ) ?? null ) ) ); ?>" method="post" style="display:inline-block;">
					<input type="hidden" name="all_tokens" value='<?php echo wp_json_encode( $all_tokens ); ?>'>
					<button class="button" type="submit" name="token"
							value="<?php echo esc_html( $after_token ) ?? null; ?>" <?php echo ! $after_token ? 'disabled' : ''; ?>>
						<?php esc_html_e( 'Next', 'webchangedetector' ); ?> >
					</button>
				</form>
			</div>
			<?php
			include 'partials/templates/show-change-detection.php';
			echo '</div>';

		} else {
			echo '<p class="notice notice-error" style="padding: 10px;">' . 
				esc_html__( 'Ooops! There was no change detection selected. Please go to', 'webchangedetector' ) . ' ' .
				'<a href="?page=webchangedetector-change-detections">' . esc_html__( 'Change Detections', 'webchangedetector' ) . '</a> ' .
				esc_html__( 'and select a change detection to show.', 'webchangedetector' ) . '</p>';
		}
	}

	/**
	 * Display screenshot image.
	 *
	 * Renders a screenshot image from the provided URL with proper
	 * error handling and responsive styling.
	 *
	 * @since    1.0.0
	 * @param    array $postdata The POST data containing image URL.
	 * @return   void
	 */
	public function get_screenshot( $postdata = false ) {
		if ( ! isset( $postdata['img_url'] ) ) {
			echo '<p class="notice notice-error" style="padding: 10px;">' .
				esc_html__( 'Sorry, we couldn\'t find the screenshot. Please try again.', 'webchangedetector' ) . '</p>';
			return;
		}

		echo '<div style="width: 100%; text-align: center;"><img style="max-width: 100%" src="' . esc_url( $postdata['img_url'] ) . '"></div>';
	}
} 