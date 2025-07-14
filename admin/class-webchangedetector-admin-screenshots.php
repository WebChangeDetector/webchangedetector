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
		if ( empty( $_GET['_wpnonce'] ) || ! wp_verify_nonce( wp_unslash( sanitize_key( $_GET['_wpnonce'] ) ) ) ) {
			echo esc_html__( 'Something went wrong. Please try again.', 'webchangedetector' );
			wp_die();
		}

		if ( ! $token && ! empty( $_GET['id'] ) ) {
			$token = sanitize_text_field( wp_unslash( $_GET['id'] ) );
		}

		if ( isset( $token ) ) {
			$api_response = \WebChangeDetector\WebChangeDetector_API_V2::get_comparison_v2( $token );
			
			// Check if API response is valid
			if ( empty( $api_response ) || ! isset( $api_response['data'] ) ) {
				echo '<p class="notice notice-error" style="padding: 10px;">' .
					esc_html__( 'Sorry, we couldn\'t find this change detection or you don\'t have permission to view it.', 'webchangedetector' ) .
					' <a href="?page=webchangedetector-change-detections">' . esc_html__( 'Go back to Change Detections', 'webchangedetector' ) . '</a></p>';
				return;
			}
			
			$compare = $api_response['data'];

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
			$this->admin->view_renderer->get_component( 'templates' )->render_show_change_detection( $compare );
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