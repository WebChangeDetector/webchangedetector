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
		if ( ! empty( $compare['html_title'] ) ) {
			echo '<strong>' . esc_html( $compare['html_title'] ) . '</strong><br>';
		}
		?>

		<a href="http://<?php echo esc_url( $compare['url'] ); ?>" target="_blank" >
			<?php echo esc_url( $compare['url'] ); ?>
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
<div id="comp-headlines">
	<div style="display:inline-block; width: calc(50% - 20px); text-align: center;">
		<h2>Screenshots</h2>
	</div>
	<div style="display:inline-block; width: calc(50% - 20px); text-align: center;">
		<h2>Change Detection</h2>
	</div>
</div>

<?php
if(str_contains($compare['screenshot_1_link'], '_dev_.png')) {
	$sc_1_compressed = str_replace("_dev_.png","_dev_compressed.jpeg", $compare['screenshot_1_link']);
	$sc_2_compressed = str_replace("_dev_.png","_dev_compressed.jpeg", $compare['screenshot_2_link']);
	$sc_comparison_compressed = str_replace("_dev_.png","_dev_compressed.jpeg", $compare['link']);
} else {
	$sc_1_compressed = str_replace(".png","_compressed.jpeg", $compare['screenshot_1_link']);
	$sc_2_compressed = str_replace(".png","_compressed.jpeg", $compare['screenshot_2_link']);
	$sc_comparison_compressed = str_replace(".png","_compressed.jpeg", $compare['link']);
}
$sc_1_raw = $compare['screenshot_1_link'];
$sc_2_raw = $compare['screenshot_2_link'];
$sc_comparison_raw = $compare['link'];
?>
<div id="comp-container" style="display:flex;  align-items: stretch; gap: 0; ">
	<div id="comp-slider" style="width: calc(50% - 20px); float: left; flex: 1; border:1px solid #aaa;">
		<div id="diff-container"
			data-token="<?php echo esc_html( $token ); ?>"
			style="width: 100%; ">

			<img class="comp-img" style="display: block; padding: 0;" src="<?php echo $sc_1_compressed; ?>" onerror="this.src = '<?php echo $sc_1_raw ?>'">
			<img style="display: block; padding: 0;" src="<?php echo $sc_2_compressed; ?>" onerror="this.src = '<?php echo $sc_2_raw ?>'">
		</div>
	</div>

	<div id="diff-bar" style="flex: 0 0 30px; padding-left:10px; padding-right: 10px;
			background: url('<?php echo esc_url( str_replace( '.png', '_diffbar.jpeg', $compare['link'] ) ); ?>') repeat-x;
			background-size: 100% 100%;">
	</div>

	<div id="comp_image" class="comp_image" style="border:1px solid #aaa; width: calc(50% - 20px); float: right; margin-right: 0; flex: 1;">
		<img style="display: block; padding: 0;" src="<?php echo $sc_comparison_compressed; ?>" onerror="this.src = '<?php echo $sc_comparison_raw ?>'">
	</div>
</div>
<div class="clear"></div>
