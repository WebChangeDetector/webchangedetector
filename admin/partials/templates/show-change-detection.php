<?php
/**
 * Show change detection
 *
 *  @package    webchangedetector
 */

?>
<div class="comparison-tiles">
	<div class="comparison_status_container comparison-tile comparison-status-tile">
		<strong>Status</strong>
		<span id="current_comparison_status" class="current_comparison_status comparison_status comparison_status_<?php echo esc_html( $compare['status'] ); ?>">
			<?php echo esc_html( $this->comparison_status_nice_name( $compare['status'] ) ); ?>
		</span>
		<div class="change_status" style="display: none; position: absolute; background: #fff; padding: 20px; box-shadow: 0 0 5px #aaa;">
			<strong>Change Status to:</strong><br>
			<?php $nonce = wp_create_nonce( 'ajax-nonce' ); ?>
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

	<div class="comparison-tile comparison-diff-tile" data-diff_percent="<?php echo esc_html( $compare['difference_percent'] ); ?>">
		<strong>Difference </strong><br>
		<span><?php echo esc_html( $compare['difference_percent'] ); ?> %</span>
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
<div class="clear"></div>
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

// Helper function to safely extract console message content
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
?>

<div class="clear"></div>
<div id="console-section" style="margin-top: 20px;">
    <h3>🔧 Browser Console Changes</h3>
    
    <?php if ($canAccessBrowserConsole) { ?>
        <?php if($hasBrowserConsoleData) { ?>
            <!-- Display actual console data -->
            <div class="console-changes-container">
                <?php 
                $hasAdded = !empty($browser_console_added);
                $hasRemoved = !empty($browser_console_removed);
                ?>
                
                <?php if($hasAdded) { ?>
                    <div class="console-added">
                        <h4>🔴 New Console Messages (<?php echo count($browser_console_added); ?>)</h4>
                        <?php foreach(array_slice($browser_console_added, 0, 3) as $log) {
                            $textContent = safe_extract_console_message($log);
                        ?>
                            <div class="console-entry added">
                                <span class="console-message"><?php echo $textContent; ?></span>
                            </div>
                        <?php } ?>
                        
                        <?php if(count($browser_console_added) > 3) { ?>
                            <div class="console-more">... and <?php echo count($browser_console_added) - 3; ?> more entries</div>
                        <?php } ?>
                    </div>
                <?php } ?>
                
                <?php if($hasRemoved) { ?>
                    <div class="console-removed">
                        <h4>🟢 Resolved Console Messages (<?php echo count($browser_console_removed); ?>)</h4>
                        <?php foreach(array_slice($browser_console_removed, 0, 2) as $log) {
                            $textContent = safe_extract_console_message($log);
                        ?>
                            <div class="console-entry removed">
                                <span class="console-message"><?php echo $textContent; ?></span>
                            </div>
                        <?php } ?>
                        
                        <?php if(count($browser_console_removed) > 2) { ?>
                            <div class="console-more">... and <?php echo count($browser_console_removed) - 2; ?> more removed</div>
                        <?php } ?>
                    </div>
                <?php } ?>
            </div>
        <?php } else { ?>
            <div class="console-no-changes">
                <span class="console-status">No Browser Console Changes</span>
                <div class="console-entry">
                    <span class="console-message">✓ No new browser console errors detected</span>
                </div>
            </div>
        <?php } ?>
    <?php } else { ?>
        <!-- Show preview for restricted plans -->
        <div class="console-restricted">
            <p>🔒 Browser Console monitoring available on <strong>Personal Pro+</strong> plans</p>
            <div class="console-preview">
                <div class="console-entry added">
                    <span class="console-message">Failed to load resource: net::ERR_CONNECTION_REFUSED</span>
                </div>
                <div class="console-entry added">
                    <span class="console-message">Uncaught TypeError: Cannot read property 'style' of null</span>
                </div>
                <div class="console-entry removed">
                    <span class="console-message">jQuery is loaded and ready</span>
                </div>
            </div>
            <a href="<?php echo esc_url(method_exists($this, 'billing_url') ? $this->billing_url() : '#'); ?>" target="_blank" class="button">Upgrade Plan</a>
        </div>
    <?php } ?>
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


