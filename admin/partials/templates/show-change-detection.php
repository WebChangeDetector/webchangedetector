<?php
/**
 * Show change detection
 *
 *  @package    webchangedetector
 */

?>
<div class="comparison-tiles wcd-settings-card">
	<div class="comparison_status_container comparison-tile comparison-status-tile">
		<strong><?php esc_html_e( 'Status', 'webchangedetector' ); ?></strong>
		<span id="current_comparison_status" class="current_comparison_status comparison_status comparison_status_<?php echo esc_html( $compare['status'] ); ?>">
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
   
	<div class="comparison-tile comparison-url-tile">
		<?php

		// Show the html title.
		if ( ! empty( $compare['html_title'] ) ) {
			echo '<strong>' . esc_html( $compare['html_title'] ) . '</strong><br>';
		}

		// Replace the protocol of the url with the protocol of the home url.
		$protocol          = wp_parse_url( get_option( 'home' ), PHP_URL_SCHEME );
		$url_with_protocol = esc_url( $protocol . '://' . preg_replace( '/^https?:\/\//', '', $compare['url'] ) );
		?>

		<a href="<?php echo esc_url( $url_with_protocol ); ?>" target="_blank" >
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
		<div class="screenshot-date" style="text-align: right; display: inline;" data-date="<?php echo esc_html( strtotime( $compare['screenshot_1_created_at'] ) ); ?>">
			<?php echo esc_html( gmdate( 'd/m/Y H:i.s', strtotime( $compare['screenshot_1_created_at'] ) ) ); ?>
		</div>
		<div class="screenshot-date" style="text-align: right; display: inline;" data-date="<?php echo esc_html( strtotime( $compare['screenshot_2_created_at'] ) ); ?>">
			<?php echo esc_html( gmdate( 'd/m/Y H:i.s', strtotime( $compare['screenshot_1_created_at'] ) ) ); ?>
		</div>
	</div>
</div>
<?php
// Browser Console Changes Section - with safety checks.
$browser_console_added   = isset( $compare['browser_console_added'] ) && is_array( $compare['browser_console_added'] ) ? $compare['browser_console_added'] : array();
$browser_console_removed = isset( $compare['browser_console_removed'] ) && is_array( $compare['browser_console_removed'] ) ? $compare['browser_console_removed'] : array();
$browser_console_change  = isset( $compare['browser_console_change'] ) ? $compare['browser_console_change'] : 'unchanged';

$has_browser_console_data = ! empty( $browser_console_added ) ||
						! empty( $browser_console_removed ) ||
						( $browser_console_change && 'unchanged' !== $browser_console_change );

// Check user plan access for browser console feature - with safety checks.
$user_account               = null;
$user_plan                  = 'free'; // Default to free plan.
$can_access_browser_console = false; // Default to no access.

try {
	if ( isset( $this->account_handler ) && method_exists( $this->account_handler, 'get_account' ) ) {
		$user_account = $this->account_handler->get_account();
		$user_plan    = isset( $user_account['plan'] ) && is_string( $user_account['plan'] ) ? $user_account['plan'] : 'free';
	}

	if ( isset( $this->admin ) && method_exists( $this->admin, 'can_access_feature' ) ) {
		$can_access_browser_console = $this->admin->can_access_feature( 'browser_console', $user_plan );
	}
} catch ( Exception $e ) {
	// Log error if needed, but fail safely.
	\WebChangeDetector\WebChangeDetector_Admin_Utils::log_error( 'Console feature access check failed: ' . $e->getMessage(), 'template_error', 'error' );
	$can_access_browser_console = false;
}

// Helper function to safely extract console message content (for compatibility).
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
			// Try to decode JSON, with error handling.
			$decoded = json_decode( $log, true );
			if ( json_last_error() === JSON_ERROR_NONE && is_array( $decoded ) ) {
				$text_content = $decoded['text'] ?? $decoded['message'] ?? $decoded['content'] ?? 'Console message (JSON)';
			} else {
				// If not valid JSON, treat as plain text (but limit length for security).
				$text_content = strlen( $log ) > 200 ? substr( $log, 0, 200 ) . '...' : $log;
			}
		} else {
			$text_content = __( 'Invalid console message format', 'webchangedetector' );
		}

		// Additional security: sanitize the content.
		$text_content = wp_strip_all_tags( $text_content );
		return htmlspecialchars( $text_content, ENT_QUOTES, 'UTF-8' );
	}
}
?>

<div class="wcd-detection-summary-container" style="display: flex; gap: 20px; margin: 15px auto 25px auto; align-items: stretch;">
	<!-- Visual Changes Section -->
	<div class="wcd-visual-changes-section" style="flex: 1;">
		<h3 class="wcd-section-headline"><?php esc_html_e( '🖼️ Visual Changes', 'webchangedetector' ); ?></h3>
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
	<div class="wcd-console-changes-section" style="flex: 1;">
		<h3 class="wcd-section-headline"><?php esc_html_e( '🔧 Browser Console Changes', 'webchangedetector' ); ?></h3>
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
							<span class="wcd-console-message"><?php esc_html_e( '✓ No new browser console errors detected', 'webchangedetector' ); ?></span>
						</div>
					</div>
				<?php } ?>
			</div>
			<?php
		} else {
			// Generate dummy preview content for plans that don't have access.
			?>
			<div class="wcd-console-display" style="position: relative;">
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
						<span class="wcd-console-message"><?php esc_html_e( 'Uncaught TypeError: Cannot read property \'style\' of null', 'webchangedetector' ); ?></span>
					</div>
					<div class="wcd-console-entry wcd-console-removed">
						<span class="wcd-console-prefix">-</span>
						<span class="wcd-console-message"><?php esc_html_e( 'jQuery is loaded and ready', 'webchangedetector' ); ?></span>
					</div>
					<div class="wcd-console-more"><?php esc_html_e( '... and 5 more entries', 'webchangedetector' ); ?></div>
				</div>
				<!-- Overlay for restricted access -->
				<div style="position: absolute; top: 0; left: 0; right: 0; bottom: 0; background: rgba(255, 255, 255, 0.8); display: flex; flex-direction: column; justify-content: center; align-items: center; border-radius: 8px; z-index: 10;">
					<div style="text-align: center; padding: 20px;">
						<p style="margin: 0 0 10px 0; font-weight: 600; color: #333;"><?php esc_html_e( '🔒 Browser Console monitoring', 'webchangedetector' ); ?></p>
						<p style="margin: 0 0 15px 0; color: #666;"><?php echo wp_kses( __( 'Available on <strong>Personal Pro+</strong> plans', 'webchangedetector' ), array( 'strong' => array() ) ); ?></p>
						<a href="<?php echo esc_url( $this->account_handler->get_upgrade_url() ?? 'https://www.webchangedetector.com/pricing/' ); ?>" target="_blank" style="background: #0073aa; color: white; padding: 8px 16px; text-decoration: none; border-radius: 4px; font-weight: 500;">
							<?php esc_html_e( 'Upgrade Plan', 'webchangedetector' ); ?>
						</a>
					</div>
				</div>
			</div>
		<?php } ?>
	</div>
</div>
<div id="comp-headlines">
	<div style="display:inline-block; width: calc(50% - 20px); text-align: center;">
		<h2><?php esc_html_e( 'Screenshots', 'webchangedetector' ); ?></h2>
	</div>
	<div style="display:inline-block; width: calc(50% - 20px); text-align: center;">
		<h2><?php esc_html_e( 'Change Detection', 'webchangedetector' ); ?></h2>
	</div>
</div>

<?php
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
?>
<div id="comp-container" style="display:flex;  align-items: stretch; gap: 0; ">
	<div id="comp-slider" style="width: calc(50% - 20px); float: left; flex: 1; border:1px solid #aaa; margin-right: 10px;">
		<div id="diff-container"
			data-token="<?php echo esc_html( $token ); ?>"
			style="width: 100%; ">

			<img class="comp-img" style="display: block; padding: 0;" src="<?php echo esc_url( $sc_1_compressed ); ?>" onerror="loadFallbackImg(this, '<?php echo esc_url( $sc_1_raw ); ?>')">
			<img style="display: block; padding: 0;" src="<?php echo esc_url( $sc_2_compressed ); ?>" onerror="loadFallbackImg(this, '<?php echo esc_url( $sc_2_raw ); ?>'")>
		</div>
	</div>

	<div id="diff-bar" style="flex: 0 0 10px; padding-left:10px; padding-right: 10px;
			background: url('<?php echo esc_url( str_replace( '.png', '_diffbar.jpeg', $compare['link'] ) ); ?>') repeat-x;
			background-size: 100% 100%;">
	</div>

	<div id="comp_image" class="comp_image" style="border:1px solid #aaa; width: calc(50% - 20px); float: right; margin-right: 0; margin-left: 10px;flex: 1;">
		<img style="display: block; padding: 0;" src="<?php echo esc_url( $sc_comparison_compressed ); ?>" onerror="loadFallbackImg(this, '<?php echo esc_url( $sc_comparison_raw ); ?>')">
	</div>
</div>
<div class="clear"></div>


