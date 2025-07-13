<?php
/**
 * Show change detection
 *
 *  @package    webchangedetector
 */

?>
<div class="comparison-tiles wcd-settings-card">
	<div class="comparison_status_container comparison-tile comparison-status-tile">
		<strong>Status</strong>
		<span id="current_comparison_status" class="current_comparison_status comparison_status comparison_status_<?php echo esc_html( $compare['status'] ); ?>">
			<?php echo esc_html( $this->comparison_status_nice_name( $compare['status'] ) ); ?>
		</span>
		<div class="change_status" style="display: none; position: absolute; background: #fff; padding: 20px; box-shadow: 0 0 5px #aaa;">
			<strong>Change Status to:</strong><br>
			<?php $nonce = \WebChangeDetector\WebChangeDetector_Admin_Utils::create_nonce( 'ajax-nonce' ); ?>
			<button name="status"
					data-id="<?php echo esc_html( $compare['id'] ); ?>"
					data-status="ok"
					data-nonce="<?php echo esc_html( $nonce ); ?>"
					value="ok"
					class="ajax_update_comparison_status comparison_status comparison_status_ok"
					onclick="return false;">Ok</button>
			<button name="status"
					data-id="<?php echo esc_html( $compare['id'] ); ?>"
					data-status="to_fix"
					data-nonce="<?php echo esc_html( $nonce ); ?>"
					value="to_fix"
					class="ajax_update_comparison_status comparison_status comparison_status_to_fix"
					onclick="return false;">To Fix</button>
			<button name="status"
					data-id="<?php echo esc_html( $compare['id'] ); ?>"
					data-status="false_positive"
					data-nonce="<?php echo esc_html( $nonce ); ?>"
					value="false_positive"
					class="ajax_update_comparison_status comparison_status comparison_status_false_positive"
					onclick="return false;">False Positive</button>
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
		Public link: <a href="<?php echo esc_url( $public_link ); ?>" target="_blank">
			<?php echo esc_url( $public_link ); ?>
		</a>
	</div>

	

	<div class="comparison-tile comparison-date-tile">
		<strong>Screenshots</strong><br>
		<div class="screenshot-date" style="text-align: right; display: inline;" data-date="<?php echo esc_html( strtotime( $compare['screenshot_1_created_at'] ) ); ?>">
			<?php echo esc_html( gmdate( 'd/m/Y H:i.s', strtotime( $compare['screenshot_1_created_at'] ) ) ); ?>
		</div>
		<div class="screenshot-date" style="text-align: right; display: inline;" data-date="<?php echo esc_html( strtotime( $compare['screenshot_2_created_at'] ) ); ?>">
			<?php echo esc_html( gmdate( 'd/m/Y H:i.s', strtotime( $compare['screenshot_1_created_at'] ) ) ); ?>
		</div>
	</div>
</div>
<?php
// Browser Console Changes Section - with safety checks
$browser_console_added = isset($compare['browser_console_added']) && is_array($compare['browser_console_added']) ? $compare['browser_console_added'] : [];
$browser_console_removed = isset($compare['browser_console_removed']) && is_array($compare['browser_console_removed']) ? $compare['browser_console_removed'] : [];
$browser_console_change = isset($compare['browser_console_change']) ? $compare['browser_console_change'] : 'unchanged';

$hasBrowserConsoleData = !empty($browser_console_added) || 
                        !empty($browser_console_removed) ||
                        ($browser_console_change && $browser_console_change !== 'unchanged');

// Check user plan access for browser console feature - with safety checks
$user_account = null;
$user_plan = 'free'; // Default to free plan
$canAccessBrowserConsole = false; // Default to no access

try {
    if (isset($this->account_handler) && method_exists($this->account_handler, 'get_account')) {
        $user_account = $this->account_handler->get_account();
        $user_plan = isset($user_account['plan']) && is_string($user_account['plan']) ? $user_account['plan'] : 'free';
    }
    
    if (isset($this->admin) && method_exists($this->admin, 'can_access_feature')) {
        $canAccessBrowserConsole = $this->admin->can_access_feature('browser_console', $user_plan);
    }
} catch (Exception $e) {
    // Log error if needed, but fail safely
    error_log('WebChangeDetector: Console feature access check failed: ' . $e->getMessage());
    $canAccessBrowserConsole = false;
}

// Helper function to safely extract console message content (for compatibility)
if (!function_exists('safe_extract_console_message')) {
    function safe_extract_console_message($log) {
        $textContent = '';
        if (is_array($log)) {
            $textContent = $log['text'] ?? $log['message'] ?? $log['content'] ?? 'Unknown console message';
        } elseif (is_string($log)) {
            // Try to decode JSON, with error handling
            $decoded = json_decode($log, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                $textContent = $decoded['text'] ?? $decoded['message'] ?? $decoded['content'] ?? 'Console message (JSON)';
            } else {
                // If not valid JSON, treat as plain text (but limit length for security)
                $textContent = strlen($log) > 200 ? substr($log, 0, 200) . '...' : $log;
            }
        } else {
            $textContent = 'Invalid console message format';
        }
        
        // Additional security: sanitize the content
        $textContent = strip_tags($textContent);
        return htmlspecialchars($textContent, ENT_QUOTES, 'UTF-8');
    }
}
?>

<div class="wcd-detection-summary-container" style="display: flex; gap: 20px; margin: 15px auto 25px auto; align-items: stretch;">
    <!-- Visual Changes Section -->
    <div class="wcd-visual-changes-section" style="flex: 1;">
        <h3 class="wcd-section-headline">🖼️ Visual Changes</h3>
        <div class="comparison-tiles comparison-diff-tile wcd-visual-diff-display" 
             data-diff_percent="<?php echo esc_attr($compare['difference_percent']); ?>"
             data-threshold="<?php echo esc_attr($compare['threshold'] ?? 0); ?>">
            <?php if($compare['difference_percent'] > 0) { ?>
                <div class="wcd-diff-indicator">
                    <span class="wcd-diff-percentage"><?php echo esc_html($compare['difference_percent']); ?>%</span>
                    <span class="wcd-diff-label">Screenshot Difference</span>
                </div>
            <?php } else { ?>
                <div class="wcd-diff-indicator wcd-no-diff">
                    <span class="wcd-diff-percentage">0%</span>
                    <span class="wcd-diff-label">No Visual Changes</span>
                </div>
            <?php } ?>
            
            <?php if(isset($compare['threshold']) && $compare['threshold'] > $compare['difference_percent']) { ?>
                <div class="wcd-threshold-note">Threshold: <?php echo esc_html($compare['threshold']); ?>%</div>
            <?php } ?>
        </div>
    </div>

    <!-- Browser Console Changes Section -->
    <div class="wcd-console-changes-section" style="flex: 1;">
        <h3 class="wcd-section-headline">🔧 Browser Console Changes</h3>
        <?php if ($canAccessBrowserConsole) { ?>
            <div class="wcd-console-display">
                <?php if($hasBrowserConsoleData) { 
                    $hasAdded = !empty($browser_console_added);
                    $hasRemoved = !empty($browser_console_removed);
                    $changeStatus = $browser_console_change ?? 'unchanged';
                ?>
                    <div class="wcd-console-indicator wcd-console-changed">
                        <span class="wcd-console-status"><?php 
                            if($changeStatus === 'mixed') echo 'Console Changes Detected';
                            elseif($changeStatus === 'added') echo 'New Error Console Entries';
                            elseif($changeStatus === 'removed') echo 'Error Console Entries Removed';
                            else echo 'Console Changed';
                        ?></span>
                    </div>
                    
                    <div class="wcd-console-logs">
                        <?php if($hasAdded && is_array($browser_console_added)) { 
                            foreach(array_slice($browser_console_added, 0, 3) as $log) { 
                                $textContent = safe_extract_console_message($log);
                                ?>
                                <div class="wcd-console-entry wcd-console-added">
                                    <span class="wcd-console-prefix">+</span>
                                    <span class="wcd-console-message"><?php echo $textContent; ?></span>
                                </div>
                        <?php } 
                            if(count($browser_console_added) > 3) { ?>
                                <div class="wcd-console-more">... and <?php echo count($browser_console_added) - 3; ?> more entries</div>
                        <?php } 
                        }
                        
                        if($hasRemoved && is_array($browser_console_removed)) { 
                            foreach(array_slice($browser_console_removed, 0, 2) as $log) { 
                                $textContent = safe_extract_console_message($log);
                                ?>
                                <div class="wcd-console-entry wcd-console-removed">
                                    <span class="wcd-console-prefix">-</span>
                                    <span class="wcd-console-message"><?php echo $textContent; ?></span>
                                </div>
                        <?php } 
                            if(count($browser_console_removed) > 2) { ?>
                                <div class="wcd-console-more">... and <?php echo count($browser_console_removed) - 2; ?> more removed</div>
                        <?php } 
                        } ?>
                    </div>
                <?php } else { ?>
                    <div class="wcd-console-indicator wcd-console-unchanged">
                        <span class="wcd-console-status">No Browser Console Changes</span>
                    </div>
                    <div class="wcd-console-logs">
                        <div class="wcd-console-entry wcd-console-info">
                            <span class="wcd-console-message">✓ No new browser console errors detected</span>
                        </div>
                    </div>
                <?php } ?>
            </div>
        <?php } else { 
            // Generate dummy preview content for plans that don't have access
            ?>
            <div class="wcd-console-display" style="position: relative;">
                <div class="wcd-console-indicator wcd-console-changed">
                    <span class="wcd-console-status">Console Changes Detected</span>
                </div>
                <div class="wcd-console-logs">
                    <div class="wcd-console-entry wcd-console-added">
                        <span class="wcd-console-prefix">+</span>
                        <span class="wcd-console-message">Failed to load resource: net::ERR_CONNECTION_REFUSED</span>
                    </div>
                    <div class="wcd-console-entry wcd-console-added">
                        <span class="wcd-console-prefix">+</span>
                        <span class="wcd-console-message">Uncaught TypeError: Cannot read property 'style' of null</span>
                    </div>
                    <div class="wcd-console-entry wcd-console-removed">
                        <span class="wcd-console-prefix">-</span>
                        <span class="wcd-console-message">jQuery is loaded and ready</span>
                    </div>
                    <div class="wcd-console-more">... and 5 more entries</div>
                </div>
                <!-- Overlay for restricted access -->
                <div style="position: absolute; top: 0; left: 0; right: 0; bottom: 0; background: rgba(255, 255, 255, 0.8); display: flex; flex-direction: column; justify-content: center; align-items: center; border-radius: 8px; z-index: 10;">
                    <div style="text-align: center; padding: 20px;">
                        <p style="margin: 0 0 10px 0; font-weight: 600; color: #333;">🔒 Browser Console monitoring</p>
                        <p style="margin: 0 0 15px 0; color: #666;">Available on <strong>Personal Pro+</strong> plans</p>
                        <a href="<?php echo esc_url(method_exists($this, 'billing_url') ? $this->billing_url() : 'https://www.webchangedetector.com/pricing/'); ?>" target="_blank" style="background: #0073aa; color: white; padding: 8px 16px; text-decoration: none; border-radius: 4px; font-weight: 500;">
                            Upgrade Plan
                        </a>
                    </div>
                </div>
            </div>
        <?php } ?>
    </div>
</div>
<div id="comp-headlines">
	<div style="display:inline-block; width: calc(50% - 20px); text-align: center;">
		<h2>Screenshots</h2>
	</div>
	<div style="display:inline-block; width: calc(50% - 20px); text-align: center;">
		<h2>Change Detection</h2>
	</div>
</div>

<?php
if ( str_contains( $compare['screenshot_1_link'], '_dev_.png' ) ) {
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


