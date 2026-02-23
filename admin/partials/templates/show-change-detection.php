<?php
/**
 * Show change detection comparison view.
 *
 * Layout: Metadata tiles at top, then 2/3 image area + 1/3 sidebar.
 * Sidebar contains AI analysis, visual changes, and browser console.
 *
 * @package    webchangedetector
 */

// --- Data Preparation ---

// AI Change Analysis data.
$has_ai_data = ! empty( $compare['ai_verification_status'] )
	&& 'skipped' !== $compare['ai_verification_status'];

if ( $has_ai_data ) {
	$ai_status  = $compare['ai_verification_status'];
	$ai_result  = $compare['ai_verification_result'] ?? array();
	$ai_regions = $compare['ai_regions'] ?? array();
}

// Browser Console data.
$browser_console_added   = isset( $compare['browser_console_added'] ) && is_array( $compare['browser_console_added'] ) ? $compare['browser_console_added'] : array();
$browser_console_removed = isset( $compare['browser_console_removed'] ) && is_array( $compare['browser_console_removed'] ) ? $compare['browser_console_removed'] : array();
$browser_console_change  = isset( $compare['browser_console_change'] ) ? $compare['browser_console_change'] : 'unchanged';

$has_browser_console_data = ! empty( $browser_console_added ) ||
	! empty( $browser_console_removed ) ||
	( $browser_console_change && 'unchanged' !== $browser_console_change );

// Check user plan access for browser console feature.
$user_account               = null;
$user_plan                  = 'free';
$can_access_browser_console = false;

try {
	if ( isset( $this->account_handler ) && method_exists( $this->account_handler, 'get_account' ) ) {
		$user_account = $this->account_handler->get_account();
		$user_plan    = isset( $user_account['plan'] ) && is_string( $user_account['plan'] ) ? $user_account['plan'] : 'free';
	}

	if ( isset( $this->admin ) && method_exists( $this->admin, 'can_access_feature' ) ) {
		$can_access_browser_console = $this->admin->can_access_feature( 'browser_console', $user_plan );
	}
} catch ( Exception $e ) {
	\WebChangeDetector\WebChangeDetector_Admin_Utils::log_error( 'Console feature access check failed: ' . $e->getMessage(), 'template_error', 'error' );
	$can_access_browser_console = false;
}

// Helper function to safely extract console message content.
if ( ! function_exists( 'safe_extract_console_message' ) ) {
	/**
	 * Safely extract console message content.
	 *
	 * @param mixed $log The console log data.
	 * @return string The extracted console message content.
	 */
	function safe_extract_console_message( $log ) {
		$text_content = '';
		if ( is_array( $log ) ) {
			$text_content = $log['text'] ?? $log['message'] ?? $log['content'] ?? __( 'Unknown console message', 'webchangedetector' );
		} elseif ( is_string( $log ) ) {
			$decoded = json_decode( $log, true );
			if ( json_last_error() === JSON_ERROR_NONE && is_array( $decoded ) ) {
				$text_content = $decoded['text'] ?? $decoded['message'] ?? $decoded['content'] ?? 'Console message (JSON)';
			} else {
				$text_content = strlen( $log ) > 200 ? substr( $log, 0, 200 ) . '...' : $log;
			}
		} else {
			$text_content = __( 'Invalid console message format', 'webchangedetector' );
		}

		$text_content = wp_strip_all_tags( $text_content );
		return htmlspecialchars( $text_content, ENT_QUOTES, 'UTF-8' );
	}
}

// Prepare screenshot URLs.
if ( strpos( $compare['screenshot_1_link'], '_dev_.png' ) !== false ) {
	$sc_1_compressed          = str_replace( '_dev_.png', '_dev_compressed.jpeg', $compare['screenshot_1_link'] );
	$sc_2_compressed          = str_replace( '_dev_.png', '_dev_compressed.jpeg', $compare['screenshot_2_link'] );
	$sc_comparison_compressed = str_replace( '_dev_.png', '_dev_compressed.jpeg', $compare['link'] );
} else {
	$sc_1_compressed          = str_replace( '.png', '_compressed.jpeg', $compare['screenshot_1_link'] );
	$sc_2_compressed          = str_replace( '.png', '_compressed.jpeg', $compare['screenshot_2_link'] );
	$sc_comparison_compressed = str_replace( '.png', '_compressed.jpeg', $compare['link'] );
}
$sc_1_raw          = $compare['screenshot_1_link'];
$sc_2_raw          = $compare['screenshot_2_link'];
$sc_comparison_raw = $compare['link'];

$nonce = \WebChangeDetector\WebChangeDetector_Admin_Utils::create_nonce( 'ajax-nonce' );
?>

<!-- Metadata Tiles -->
<div class="comparison-tiles wcd-settings-card">
	<div class="comparison_status_container comparison-tile comparison-status-tile">
		<strong><?php esc_html_e( 'Status', 'webchangedetector' ); ?></strong>
		<span id="current_comparison_status" class="current_comparison_status comparison_status comparison_status_<?php echo esc_attr( $compare['status'] ); ?>">
			<?php echo esc_html( \WebChangeDetector\WebChangeDetector_Admin_Utils::get_comparison_status_name( $compare['status'] ) ); ?>
		</span>
		<div class="change_status" style="display: none; position: absolute; background: #fff; padding: 20px; box-shadow: 0 0 5px #aaa;">
			<strong><?php esc_html_e( 'Change Status to:', 'webchangedetector' ); ?></strong><br>
			<button name="status"
					data-id="<?php echo esc_attr( $compare['id'] ); ?>"
					data-status="ok"
					data-nonce="<?php echo esc_attr( $nonce ); ?>"
					value="ok"
					class="ajax_update_comparison_status comparison_status comparison_status_ok"
					onclick="return false;"><?php esc_html_e( 'Ok', 'webchangedetector' ); ?></button>
			<button name="status"
					data-id="<?php echo esc_attr( $compare['id'] ); ?>"
					data-status="to_fix"
					data-nonce="<?php echo esc_attr( $nonce ); ?>"
					value="to_fix"
					class="ajax_update_comparison_status comparison_status comparison_status_to_fix"
					onclick="return false;"><?php esc_html_e( 'To Fix', 'webchangedetector' ); ?></button>
			<button name="status"
					data-id="<?php echo esc_attr( $compare['id'] ); ?>"
					data-status="false_positive"
					data-nonce="<?php echo esc_attr( $nonce ); ?>"
					value="false_positive"
					class="ajax_update_comparison_status comparison_status comparison_status_false_positive"
					onclick="return false;"><?php esc_html_e( 'False Positive', 'webchangedetector' ); ?></button>
		</div>
	</div>

	<div class="comparison-tile comparison-url-tile">
		<?php
		if ( ! empty( $compare['html_title'] ) ) {
			echo '<strong>' . esc_html( $compare['html_title'] ) . '</strong><br>';
		}
		$protocol          = wp_parse_url( get_option( 'home' ), PHP_URL_SCHEME );
		$url_with_protocol = esc_url( $protocol . '://' . preg_replace( '/^https?:\/\//', '', $compare['url'] ) );
		?>
		<a href="<?php echo esc_url( $url_with_protocol ); ?>" target="_blank">
			<?php echo esc_url( $url_with_protocol ); ?>
		</a>
		<br>
		<?php $public_link = $this->app_url() . 'show-change-detection/?token=' . $public_token; ?>
		<?php esc_html_e( 'Public link:', 'webchangedetector' ); ?> <a href="<?php echo esc_url( $public_link ); ?>" target="_blank">
			<?php echo esc_url( $public_link ); ?>
		</a>
	</div>

	<div class="comparison-tile comparison-date-tile">
		<strong><?php esc_html_e( 'Screenshots', 'webchangedetector' ); ?></strong><br>
		<div class="screenshot-date" style="text-align: right; display: inline;" data-date="<?php echo esc_attr( strtotime( $compare['screenshot_1_created_at'] ) ); ?>">
			<?php echo esc_html( gmdate( 'd/m/Y H:i.s', strtotime( $compare['screenshot_1_created_at'] ) ) ); ?>
		</div>
		<div class="screenshot-date" style="text-align: right; display: inline;" data-date="<?php echo esc_attr( strtotime( $compare['screenshot_2_created_at'] ) ); ?>">
			<?php echo esc_html( gmdate( 'd/m/Y H:i.s', strtotime( $compare['screenshot_2_created_at'] ) ) ); ?>
		</div>
	</div>
</div>

<!-- Main Layout: 2/3 Image + 1/3 Sidebar -->
<div class="wcd-popup-main-layout">

	<!-- LEFT: Image area -->
	<div class="wcd-popup-image-area">

		<!-- View toggle buttons -->
		<div class="wcd-view-toggle">
			<button type="button" class="wcd-view-btn wcd-view-diff active"><?php esc_html_e( 'Before <-> Change Detection', 'webchangedetector' ); ?></button>
			<button type="button" class="wcd-view-btn wcd-view-after"><?php esc_html_e( 'Before <-> After', 'webchangedetector' ); ?></button>
		</div>

		<!-- Single slider with swappable second image -->
		<div class="wcd-slider-wrapper">
			<div id="comp-slider">
				<div id="diff-container"
					 data-token="<?php echo esc_attr( $token ); ?>"
					 data-after-src="<?php echo esc_url( $sc_2_compressed ); ?>"
					 data-after-fallback="<?php echo esc_url( $sc_2_raw ); ?>"
					 data-diff-src="<?php echo esc_url( $sc_comparison_compressed ); ?>"
					 data-diff-fallback="<?php echo esc_url( $sc_comparison_raw ); ?>">
					<img class="comp-img" src="<?php echo esc_url( $sc_1_compressed ); ?>" onerror="loadFallbackImg(this, '<?php echo esc_url( $sc_1_raw ); ?>')">
					<img src="<?php echo esc_url( $sc_comparison_compressed ); ?>" onerror="loadFallbackImg(this, '<?php echo esc_url( $sc_comparison_raw ); ?>')">
				</div>
			</div>

			<?php if ( $has_ai_data ) { ?>
			<!-- AI overlay layer (positioned on top of slider, visible in Change Detection mode) -->
			<div id="wcd_ai_overlay_layer" class="wcd-ai-overlay-layer wcd-ai-overlays-visible"
				<?php if ( 'verified' === $ai_status && ! empty( $ai_regions ) ) { ?>
					data-ai-regions="<?php echo esc_attr( wp_json_encode( $ai_regions ) ); ?>"
				<?php } ?>
			></div>
			<?php } ?>
		</div>

	</div>

	<!-- RIGHT: Results sidebar -->
	<div class="wcd-popup-sidebar">
		<div class="wcd-popup-sidebar-inner">

			<?php if ( $has_ai_data ) { ?>
			<!-- AI Change Analysis Section -->
			<div class="wcd-ai-analysis-section">
				<h3 class="wcd-section-headline">
					<?php esc_html_e( 'AI Change Analysis', 'webchangedetector' ); ?>
					<?php if ( 'verified' === $ai_status && ! empty( $ai_result ) ) { ?>
						<span class="wcd-ai-summary-badges">
							<?php if ( ! empty( $ai_result['alerts'] ) ) { ?>
								<span class="wcd-ai-badge wcd-ai-badge-alert">
									<?php echo intval( $ai_result['alerts'] ); ?> Alert<?php echo intval( $ai_result['alerts'] ) > 1 ? 's' : ''; ?>
								</span>
							<?php } ?>
							<?php if ( ! empty( $ai_result['not_sure'] ) ) { ?>
								<span class="wcd-ai-badge wcd-ai-badge-unsure">
									<?php echo intval( $ai_result['not_sure'] ); ?> Unsure
								</span>
							<?php } ?>
							<?php if ( ! empty( $ai_result['all_good'] ) ) { ?>
								<span class="wcd-ai-badge wcd-ai-badge-good">
									<?php echo intval( $ai_result['all_good'] ); ?> OK
								</span>
							<?php } ?>
							<?php
							$console_cat_badge = $ai_result['console_analysis']['category'] ?? null;
							if ( $console_cat_badge && 'all_good' !== $console_cat_badge ) {
								$badge_class = 'alert' === $console_cat_badge ? 'wcd-ai-badge-alert' : 'wcd-ai-badge-unsure';
								$badge_label = 'alert' === $console_cat_badge ? 'Console Alert' : 'Console Unsure';
								?>
								<span class="wcd-ai-badge <?php echo esc_attr( $badge_class ); ?>">
									<?php echo esc_html( $badge_label ); ?>
								</span>
							<?php } ?>
						</span>
					<?php } ?>
				</h3>

				<?php if ( 'verified' === $ai_status && ! empty( $ai_result ) ) {
					$console_cat     = $ai_result['console_analysis']['category'] ?? null;
					$has_alert       = ! empty( $ai_result['alerts'] ) || 'alert' === $console_cat;
					$has_not_sure    = ! empty( $ai_result['not_sure'] ) || 'not_sure' === $console_cat;
					$overall_category = $has_alert ? 'alert' : ( $has_not_sure ? 'not_sure' : 'all_good' );
					?>
					<div class="wcd-ai-overall wcd-ai-overall-<?php echo esc_attr( $overall_category ); ?>">
						<span class="wcd-ai-overall-badge wcd-ai-cat-<?php echo esc_attr( $overall_category ); ?>">
							<?php
							if ( 'alert' === $overall_category ) {
								esc_html_e( 'Alert', 'webchangedetector' );
							} elseif ( 'not_sure' === $overall_category ) {
								esc_html_e( 'Unsure', 'webchangedetector' );
							} else {
								esc_html_e( 'OK', 'webchangedetector' );
							}
							?>
						</span>
						<?php if ( ! empty( $ai_result['summary'] ) ) { ?>
							<p class="wcd-ai-summary-text"><?php echo esc_html( $ai_result['summary'] ); ?></p>
						<?php } ?>
					</div>
				<?php } ?>

				<?php if ( 'pending' === $ai_status ) { ?>
					<div class="wcd-ai-pending" data-token="<?php echo esc_attr( $token ); ?>">
						<img src="<?php echo esc_url( WCD_PLUGIN_URL . 'admin/img/loader.gif' ); ?>" alt="Loading" class="wcd-ai-loading-icon">
						<?php esc_html_e( 'AI analysis in progress...', 'webchangedetector' ); ?>
					</div>

				<?php } elseif ( 'failed' === $ai_status ) { ?>
					<div class="wcd-ai-failed">
						<?php esc_html_e( 'AI analysis could not be completed for this comparison.', 'webchangedetector' ); ?>
					</div>

				<?php } elseif ( 'verified' === $ai_status && ! empty( $ai_result['regions'] ) ) { ?>
					<div class="wcd-ai-regions-list">
						<?php foreach ( $ai_result['regions'] as $index => $region ) {
							$region_num  = $index + 1;
							$category    = esc_attr( $region['category'] ?? 'not_sure' );
							$description = esc_html( $region['description'] ?? '' );
							$reason      = esc_html( $region['reason'] ?? '' );
							$region_id   = isset( $region['region_id'] ) ? intval( $region['region_id'] ) : $index;
							$matched_rule = ! empty( $region['matched_feedback_rule'] ) ? $region['matched_feedback_rule'] : '';
							?>
							<div class="wcd-ai-region-card wcd-ai-category-<?php echo $category; ?>"
								 data-region-id="<?php echo esc_attr( $region_id ); ?>">
								<span class="wcd-ai-region-number"><?php echo intval( $region_num ); ?></span>
								<span class="wcd-ai-region-category-badge wcd-ai-cat-<?php echo $category; ?>">
									<?php
									if ( 'all_good' === $category ) {
										esc_html_e( 'OK', 'webchangedetector' );
									} elseif ( 'alert' === $category ) {
										esc_html_e( 'Alert', 'webchangedetector' );
									} else {
										esc_html_e( 'Unsure', 'webchangedetector' );
									}
									?>
								</span>
								<?php if ( $matched_rule ) { ?>
									<span class="wcd-ai-feedback-matched"><?php esc_html_e( 'Auto-ignored by your rule', 'webchangedetector' ); ?></span>
								<?php } ?>
								<span class="wcd-ai-region-description"><?php echo $description; ?></span>
								<?php if ( $reason ) { ?>
									<div class="wcd-ai-region-reason"><?php echo $reason; ?></div>
								<?php } ?>
								<?php if ( in_array( $category, array( 'alert', 'not_sure' ), true ) && empty( $matched_rule ) ) { ?>
									<button type="button"
										class="wcd-ai-feedback-btn"
										data-comparison-id="<?php echo esc_attr( $compare['id'] ); ?>"
										data-region-id="<?php echo esc_attr( $region_id ); ?>">
										<?php esc_html_e( 'Ignore in future', 'webchangedetector' ); ?>
									</button>
								<?php } ?>
							</div>
						<?php } ?>
					</div>

				<?php } elseif ( 'verified' === $ai_status && ! empty( $ai_result['console_analysis'] ) ) { ?>
					<div class="wcd-ai-console-only">
						<?php echo esc_html( $ai_result['console_analysis']['description'] ?? '' ); ?>
					</div>

				<?php } elseif ( 'verified' === $ai_status ) { ?>
					<div class="wcd-ai-no-regions">
						<?php esc_html_e( 'No distinct change regions detected by AI.', 'webchangedetector' ); ?>
					</div>
				<?php } ?>
			</div>
			<?php } ?>

			<!-- Visual Changes Section -->
			<div class="wcd-visual-changes-section">
				<h3 class="wcd-section-headline"><?php esc_html_e( 'Visual Changes', 'webchangedetector' ); ?></h3>
				<div class="comparison-tiles comparison-diff-tile wcd-visual-diff-display"
					data-diff_percent="<?php echo esc_attr( $compare['difference_percent'] ); ?>"
					data-threshold="<?php echo esc_attr( $compare['threshold'] ?? 0 ); ?>">
					<?php if ( $compare['difference_percent'] > 0 ) { ?>
						<div class="wcd-diff-indicator">
							<span class="wcd-diff-percentage"><?php echo esc_html( \WebChangeDetector\WebChangeDetector_Admin_Utils::format_difference_percent( $compare['difference_percent'] ) ); ?>%</span>
							<span class="wcd-diff-label"><?php esc_html_e( 'Screenshot Difference', 'webchangedetector' ); ?></span>
						</div>
					<?php } else { ?>
						<div class="wcd-diff-indicator wcd-no-diff">
							<span class="wcd-diff-percentage">0%</span>
							<span class="wcd-diff-label"><?php esc_html_e( 'No Visual Changes', 'webchangedetector' ); ?></span>
						</div>
					<?php } ?>

					<?php if ( isset( $compare['threshold'] ) && $compare['threshold'] > $compare['difference_percent'] ) { ?>
						<div class="wcd-threshold-note"><?php esc_html_e( 'Threshold:', 'webchangedetector' ); ?> <?php echo esc_html( $compare['threshold'] ); ?>%</div>
					<?php } ?>
				</div>
			</div>

			<!-- Browser Console Changes Section -->
			<div class="wcd-console-changes-section">
				<h3 class="wcd-section-headline"><?php esc_html_e( 'Browser Console', 'webchangedetector' ); ?></h3>
				<?php if ( $can_access_browser_console ) { ?>
					<div class="wcd-console-display">
						<?php
						if ( $has_browser_console_data ) {
							$has_added     = ! empty( $browser_console_added );
							$has_removed   = ! empty( $browser_console_removed );
							$change_status = $browser_console_change ?? 'unchanged';
							?>
							<div class="wcd-console-indicator wcd-console-changed">
								<span class="wcd-console-status">
								<?php
								if ( 'mixed' === $change_status ) {
									esc_html_e( 'Console Changes Detected', 'webchangedetector' );
								} elseif ( 'added' === $change_status ) {
									esc_html_e( 'New Error Console Entries', 'webchangedetector' );
								} elseif ( 'removed' === $change_status ) {
									esc_html_e( 'Error Console Entries Removed', 'webchangedetector' );
								} else {
									esc_html_e( 'Console Changed', 'webchangedetector' );
								}
								?>
								</span>
							</div>

							<div class="wcd-console-logs">
								<?php
								if ( $has_added && is_array( $browser_console_added ) ) {
									foreach ( array_slice( $browser_console_added, 0, 3 ) as $log ) {
										$text_content = safe_extract_console_message( $log );
										?>
										<div class="wcd-console-entry wcd-console-added">
											<span class="wcd-console-prefix">+</span>
											<span class="wcd-console-message"><?php echo esc_html( $text_content ); ?></span>
										</div>
										<?php
									}
									if ( count( $browser_console_added ) > 3 ) {
										?>
										<div class="wcd-console-more">
											<?php
											// translators: %d is the number of additional entries.
											printf( esc_html__( '... and %d more entries', 'webchangedetector' ), count( $browser_console_added ) - 3 );
											?>
										</div>
										<?php
									}
								}

								if ( $has_removed && is_array( $browser_console_removed ) ) {
									foreach ( array_slice( $browser_console_removed, 0, 2 ) as $log ) {
										$text_content = safe_extract_console_message( $log );
										?>
										<div class="wcd-console-entry wcd-console-removed">
											<span class="wcd-console-prefix">-</span>
											<span class="wcd-console-message"><?php echo esc_html( $text_content ); ?></span>
										</div>
										<?php
									}
									if ( count( $browser_console_removed ) > 2 ) {
										?>
										<div class="wcd-console-more">
											<?php
											// translators: %d is the number of additional removed entries.
											printf( esc_html__( '... and %d more removed', 'webchangedetector' ), count( $browser_console_removed ) - 2 );
											?>
										</div>
										<?php
									}
								}
								?>
							</div>
						<?php } else { ?>
							<div class="wcd-console-indicator wcd-console-unchanged">
								<span class="wcd-console-status"><?php esc_html_e( 'No Browser Console Changes', 'webchangedetector' ); ?></span>
							</div>
							<div class="wcd-console-logs">
								<div class="wcd-console-entry wcd-console-info">
									<span class="wcd-console-message"><?php esc_html_e( 'No new browser console errors detected', 'webchangedetector' ); ?></span>
								</div>
							</div>
						<?php } ?>
					</div>
				<?php } else { ?>
					<div class="wcd-console-display wcd-console-restricted">
						<div class="wcd-console-indicator wcd-console-changed">
							<span class="wcd-console-status"><?php esc_html_e( 'Console Changes Detected', 'webchangedetector' ); ?></span>
						</div>
						<div class="wcd-console-logs">
							<div class="wcd-console-entry wcd-console-added">
								<span class="wcd-console-prefix">+</span>
								<span class="wcd-console-message"><?php esc_html_e( 'Failed to load resource: net::ERR_CONNECTION_REFUSED', 'webchangedetector' ); ?></span>
							</div>
							<div class="wcd-console-entry wcd-console-added">
								<span class="wcd-console-prefix">+</span>
								<span class="wcd-console-message"><?php esc_html_e( 'Uncaught TypeError: Cannot read property of null', 'webchangedetector' ); ?></span>
							</div>
							<div class="wcd-console-entry wcd-console-removed">
								<span class="wcd-console-prefix">-</span>
								<span class="wcd-console-message"><?php esc_html_e( 'jQuery is loaded and ready', 'webchangedetector' ); ?></span>
							</div>
							<div class="wcd-console-more"><?php esc_html_e( '... and 5 more entries', 'webchangedetector' ); ?></div>
						</div>
						<div class="wcd-console-upgrade-overlay">
							<p class="wcd-restricted-lock"><?php esc_html_e( 'Browser Console monitoring', 'webchangedetector' ); ?></p>
							<p class="wcd-restricted-description">
								<?php echo wp_kses( __( 'Available on <strong>Personal Pro+</strong> plans', 'webchangedetector' ), array( 'strong' => array() ) ); ?>
							</p>
							<a href="<?php echo esc_url( $this->account_handler->get_upgrade_url() ?? 'https://www.webchangedetector.com/pricing/' ); ?>" target="_blank" class="wcd-upgrade-button">
								<?php esc_html_e( 'Upgrade Plan', 'webchangedetector' ); ?>
							</a>
						</div>
					</div>
				<?php } ?>
			</div>

		</div>
	</div>

</div>

<?php if ( $has_ai_data && 'verified' === $ai_status ) { ?>
<!-- AI Feedback Rule: Scope Selection Modal -->
<div id="wcd-ai-feedback-modal" class="wcd-ai-feedback-modal">
	<div class="wcd-ai-feedback-modal-content">
		<h3><?php esc_html_e( 'Ignore this type of change in future?', 'webchangedetector' ); ?></h3>
		<p><?php esc_html_e( 'Choose where this rule should apply:', 'webchangedetector' ); ?></p>
		<div class="wcd-ai-feedback-modal-options">
			<label class="wcd-ai-feedback-modal-option">
				<input type="radio" name="wcd_ai_feedback_scope" value="url" checked>
				<span><?php esc_html_e( 'Only this URL', 'webchangedetector' ); ?></span>
			</label>
			<label class="wcd-ai-feedback-modal-option">
				<input type="radio" name="wcd_ai_feedback_scope" value="group_or_website">
				<span><?php printf( esc_html__( 'All URLs in %s', 'webchangedetector' ), esc_html( $compare['group_name'] ?? __( 'this group', 'webchangedetector' ) ) ); ?></span>
			</label>
		</div>
		<input type="hidden" id="wcd-ai-feedback-comparison-id" value="">
		<input type="hidden" id="wcd-ai-feedback-region-id" value="">
		<div class="wcd-ai-feedback-modal-actions">
			<button type="button" class="button button-primary wcd-ai-feedback-submit"><?php esc_html_e( 'Confirm', 'webchangedetector' ); ?></button>
			<button type="button" class="button wcd-ai-feedback-cancel"><?php esc_html_e( 'Cancel', 'webchangedetector' ); ?></button>
		</div>
	</div>
</div>
<?php } ?>

<div class="clear"></div>
