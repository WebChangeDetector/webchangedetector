<?php
/**
 * Card View Component for WebChangeDetector
 *
 * Handles rendering of card layouts and status boxes.
 *
 * @package    WebChangeDetector
 * @subpackage WebChangeDetector/admin/views
 * @author     Mike Miler <mike@wp-mike.com>
 */

namespace WebChangeDetector;

/**
 * Card View Component Class.
 */
class WebChangeDetector_Card_View {

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
	 * Render account status card.
	 *
	 * @param array $account_details The account details.
	 */
	public function render_account_status_card( $account_details ) {
		?>
		<div class="account-status-card">
			<h3>Account Status</h3>
			<div class="status-info">
				<p><strong>Email:</strong> <?php echo esc_html( $account_details['email'] ?? 'N/A' ); ?></p>
				<p><strong>Plan:</strong> <?php echo esc_html( $account_details['plan_name'] ?? 'N/A' ); ?></p>
				<p><strong>Status:</strong> 
					<span class="status-badge status-<?php echo esc_attr( $account_details['status'] ?? 'inactive' ); ?>">
						<?php echo esc_html( ucfirst( $account_details['status'] ?? 'Inactive' ) ); ?>
					</span>
				</p>
				<p><strong>Checks Used:</strong> 
					<?php echo esc_html( $account_details['checks_done'] ?? 0 ); ?> / 
					<?php echo esc_html( $account_details['checks_limit'] ?? 0 ); ?>
				</p>
				<?php if ( ! empty( $account_details['renewal_at'] ) ) : ?>
					<p><strong>Renewal Date:</strong> 
						<span class="local-time" data-date="<?php echo esc_attr( strtotime( $account_details['renewal_at'] ) ); ?>">
							<?php echo esc_html( gmdate( 'd/m/Y', strtotime( $account_details['renewal_at'] ) ) ); ?>
						</span>
					</p>
				<?php endif; ?>
			</div>
		</div>
		<?php
	}

	/**
	 * Render monitoring status card.
	 *
	 * @param array $group The group details.
	 */
	public function render_monitoring_status_card( $group ) {
		// Calculate monitoring details.
		$date_next_sc      = false;
		$amount_sc_per_day = 0;

		// Check for intervals >= 1h.
		if ( $group['interval_in_h'] >= 1 ) {
			$next_possible_sc  = gmmktime( gmdate( 'H' ) + 1, 0, 0, gmdate( 'm' ), gmdate( 'd' ), gmdate( 'Y' ) );
			$amount_sc_per_day = ( 24 / $group['interval_in_h'] );
			$possible_hours    = array();

			// Get possible tracking hours.
			for ( $i = 0; $i <= $amount_sc_per_day * 2; $i++ ) {
				$possible_hour    = $group['hour_of_day'] + $i * $group['interval_in_h'];
				$possible_hours[] = $possible_hour >= 24 ? $possible_hour - 24 : $possible_hour;
			}
			sort( $possible_hours );

			// Check for today and tomorrow.
			for ( $ii = 0; $ii <= 1; $ii++ ) {
				for ( $i = 0; $i <= $amount_sc_per_day * 2; $i++ ) {
					$possible_time = gmmktime( $possible_hours[ $i ], 0, 0, gmdate( 'm' ), gmdate( 'd' ) + $ii, gmdate( 'Y' ) );

					if ( $possible_time >= $next_possible_sc ) {
						$date_next_sc = $possible_time;
						break;
					}
				}

				if ( $date_next_sc ) {
					break;
				}
			}
		}

		// Check for 30 min intervals.
		if ( 0.5 === $group['interval_in_h'] ) {
			$amount_sc_per_day = 48;
			if ( gmdate( 'i' ) < 30 ) {
				$date_next_sc = gmmktime( gmdate( 'H' ), 30, 0, gmdate( 'm' ), gmdate( 'd' ), gmdate( 'Y' ) );
			} else {
				$date_next_sc = gmmktime( gmdate( 'H' ) + 1, 0, 0, gmdate( 'm' ), gmdate( 'd' ), gmdate( 'Y' ) );
			}
		}

		// Check for 15 min intervals.
		if ( 0.25 === $group['interval_in_h'] ) {
			$amount_sc_per_day = 96;
			if ( gmdate( 'i' ) < 15 ) {
				$date_next_sc = gmmktime( gmdate( 'H' ), 15, 0, gmdate( 'm' ), gmdate( 'd' ), gmdate( 'Y' ) );
			} elseif ( gmdate( 'i' ) < 30 ) {
				$date_next_sc = gmmktime( gmdate( 'H' ), 30, 0, gmdate( 'm' ), gmdate( 'd' ), gmdate( 'Y' ) );
			} elseif ( gmdate( 'i' ) < 45 ) {
				$date_next_sc = gmmktime( gmdate( 'H' ), 45, 0, gmdate( 'm' ), gmdate( 'd' ), gmdate( 'Y' ) );
			} else {
				$date_next_sc = gmmktime( gmdate( 'H' ) + 1, 0, 0, gmdate( 'm' ), gmdate( 'd' ), gmdate( 'Y' ) );
			}
		}

		?>
		<div class="status_bar">
			<div class="box full">
				<div id="txt_next_sc_in"><?php esc_html_e( 'Next monitoring checks in ', 'webchangedetector' ); ?></div>
				<div id="next_sc_in" class="big"></div>
				<div id="next_sc_date" class="local-time" data-date="<?php echo esc_html( $date_next_sc ); ?>"></div>
				<div id="sc_available_until_renew"
					data-amount_selected_urls="<?php echo esc_html( $group['selected_urls_count'] ); ?>"
					data-auto_sc_per_url_until_renewal="<?php echo esc_html( $amount_sc_per_day ); ?>"></div>
			</div>
		</div>
		<?php
	}

	/**
	 * Render dashboard statistics card.
	 *
	 * @param array $stats The statistics data.
	 */
	public function render_stats_card( $stats ) {
		?>
		<div class="stats-card">
			<h3>Statistics</h3>
			<div class="stats-grid">
				<div class="stat-item">
					<span class="stat-number"><?php echo esc_html( $stats['total_screenshots'] ?? 0 ); ?></span>
					<span class="stat-label">Total Screenshots</span>
				</div>
				<div class="stat-item">
					<span class="stat-number"><?php echo esc_html( $stats['total_comparisons'] ?? 0 ); ?></span>
					<span class="stat-label">Total Comparisons</span>
				</div>
				<div class="stat-item">
					<span class="stat-number"><?php echo esc_html( $stats['urls_monitored'] ?? 0 ); ?></span>
					<span class="stat-label">URLs Monitored</span>
				</div>
				<div class="stat-item">
					<span class="stat-number"><?php echo esc_html( $stats['changes_detected'] ?? 0 ); ?></span>
					<span class="stat-label">Changes Detected</span>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Render update step card.
	 *
	 * @param string $current_step The current step.
	 * @param array  $step_data    The step data.
	 */
	public function render_update_step_card( $current_step, $step_data ) {
		$steps = array(
			WCD_OPTION_UPDATE_STEP_SETTINGS         => 'Configure Settings',
			WCD_OPTION_UPDATE_STEP_PRE              => 'Take Pre-Update Screenshots',
			WCD_OPTION_UPDATE_STEP_MAKE_UPDATES     => 'Make Your Updates',
			WCD_OPTION_UPDATE_STEP_POST             => 'Take Post-Update Screenshots',
			WCD_OPTION_UPDATE_STEP_CHANGE_DETECTION => 'View Change Detections',
		);

		?>
		<div class="update-step-card">
			<h3>Update Process</h3>
			<div class="step-progress">
				<?php foreach ( $steps as $step_key => $step_label ) : ?>
					<?php
					$is_current   = $current_step === $step_key;
					$is_completed = array_search( $step_key, array_keys( $steps ), true ) < array_search( $current_step, array_keys( $steps ), true );
					$step_class   = $is_current ? 'current' : ( $is_completed ? 'completed' : 'pending' );
					?>
					<div class="step-item <?php echo esc_attr( $step_class ); ?>">
						<div class="step-indicator">
							<?php if ( $is_completed ) : ?>
								✓
							<?php else : ?>
								<?php echo esc_html( array_search( $step_key, array_keys( $steps ), true ) + 1 ); ?>
							<?php endif; ?>
						</div>
						<div class="step-label"><?php echo esc_html( $step_label ); ?></div>
					</div>
				<?php endforeach; ?>
			</div>
			
			<?php if ( ! empty( $step_data['message'] ) ) : ?>
				<div class="step-message">
					<p><?php echo wp_kses_post( $step_data['message'] ); ?></p>
				</div>
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * Render generic information card.
	 *
	 * @param string $title   The card title.
	 * @param string $content The card content.
	 * @param array  $actions Optional action buttons.
	 */
	public function render_info_card( $title, $content, $actions = array() ) {
		?>
		<div class="info-card">
			<h3><?php echo esc_html( $title ); ?></h3>
			<div class="card-content">
				<?php echo wp_kses_post( $content ); ?>
			</div>
			
			<?php if ( ! empty( $actions ) ) : ?>
				<div class="card-actions">
					<?php foreach ( $actions as $action ) : ?>
						<?php if ( 'link' === $action['type'] ) : ?>
							<a href="<?php echo esc_url( $action['url'] ); ?>" 
								class="button <?php echo esc_attr( $action['class'] ?? '' ); ?>">
								<?php echo esc_html( $action['label'] ); ?>
							</a>
						<?php elseif ( 'form' === $action['type'] ) : ?>
							<form method="post" style="display: inline-block;">
								<input type="hidden" name="wcd_action" value="<?php echo esc_attr( $action['action'] ); ?>">
								<?php wp_nonce_field( $action['action'] ); ?>
								<?php if ( ! empty( $action['hidden_fields'] ) ) : ?>
									<?php foreach ( $action['hidden_fields'] as $field => $value ) : ?>
										<input type="hidden" name="<?php echo esc_attr( $field ); ?>" value="<?php echo esc_attr( $value ); ?>">
									<?php endforeach; ?>
								<?php endif; ?>
								<input type="submit" value="<?php echo esc_attr( $action['label'] ); ?>" 
										class="button <?php echo esc_attr( $action['class'] ?? '' ); ?>">
							</form>
						<?php endif; ?>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * Render error card.
	 *
	 * @param string $title   The error title.
	 * @param string $message The error message.
	 * @param array  $actions Optional action buttons.
	 */
	public function render_error_card( $title, $message, $actions = array() ) {
		?>
		<div class="error-card">
			<h3 class="error-title"><?php echo esc_html( $title ); ?></h3>
			<div class="error-message">
				<p><?php echo wp_kses_post( $message ); ?></p>
			</div>
			
			<?php if ( ! empty( $actions ) ) : ?>
				<div class="error-actions">
					<?php foreach ( $actions as $action ) : ?>
						<form method="post" style="display: inline-block; margin-right: 10px;">
							<input type="hidden" name="wcd_action" value="<?php echo esc_attr( $action['action'] ); ?>">
							<?php wp_nonce_field( $action['action'] ); ?>
							<?php if ( ! empty( $action['hidden_fields'] ) ) : ?>
								<?php foreach ( $action['hidden_fields'] as $field => $value ) : ?>
									<input type="hidden" name="<?php echo esc_attr( $field ); ?>" value="<?php echo esc_attr( $value ); ?>">
								<?php endforeach; ?>
							<?php endif; ?>
							<input type="submit" value="<?php echo esc_attr( $action['label'] ); ?>" 
									class="button <?php echo esc_attr( $action['class'] ?? '' ); ?>">
						</form>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>
		</div>
		<?php
	}
}
