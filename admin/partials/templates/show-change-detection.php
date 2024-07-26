<?php
/**
 * Show change detection
 *
 *  @package    webchangedetector
 */

?>
<div class="comparison-tiles">
	<div class="comparison-tile comparison-status-tile">
		<strong>Status</strong>
		<span class="comparison_status comparison_status_<?php echo esc_html( $compare['status'] ); ?>">
			<?php echo esc_html( $this->comparison_status_nice_name( $compare['status'] ) ); ?>
		</span>
		<div class="change_status" style="display: none; position: absolute; background: #fff; padding: 20px; box-shadow: 0 0 5px #aaa;">
			Change Status to:<br>
			<form action="?page=webchangedetector-show-detection" method="post">
				<input type="hidden" name="wcd_action" value="change_comparison_status">
				<input type="hidden" name="comparison_uuid" value="<?php echo esc_html( $compare['uuid'] ); ?>">
				<input type="hidden" name="token" value="<?php echo esc_html( $compare['token'] ); ?>">
				<input type="hidden" name="all_tokens" value="<?php echo esc_html( $postdata['all_tokens'] ?? null ); ?>">

				<?php wp_nonce_field( 'change_comparison_status' ); ?>
				<button name="status" value="ok" class="comparison_status comparison_status_ok" style="<?php echo 'ok' === $compare['status'] ? 'display: none;' : ''; ?>">Ok</button>
				<button name="status" value="to_fix" class="comparison_status comparison_status_to_fix" style="<?php echo 'to_fix' === $compare['status'] ? 'display: none;' : ''; ?>">To Fix</button>
				<button name="status" value="false_positive" class="comparison_status comparison_status_false_positive" style="<?php echo 'false_positive' === $compare['status'] ? 'display: none;' : ''; ?>">False Positive</button>
			</form>
		</div>
	</div>
	<div class="comparison-tile comparison-url-tile">
		<?php
		if ( ! empty( $compare['screenshot1']['queue']['url']['html_title'] ) ) {
			echo '<strong>' . esc_html( $compare['screenshot1']['queue']['url']['html_title'] ) . '</strong><br>';
		}
		?>

		<a href="http://<?php echo esc_url( $compare['screenshot1']['url'] ); ?>" target="_blank" >
			<?php echo esc_url( $compare['screenshot1']['url'] ); ?>
		</a>
		<br>
		<?php $public_link = $this->app_url() . '/show-change-detection/?token=' . $token; ?>
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
		<div class="screenshot-date" style="text-align: right; display: inline;" data-date="<?php echo esc_html( strtotime( $compare['screenshot1']['updated_at'] ) ); ?>">
			<?php echo esc_html( gmdate( 'd/m/Y H:i.s', strtotime( $compare['screenshot1']['updated_at'] ) ) ); ?>
		</div>
		<div class="screenshot-date" style="text-align: right; display: inline;" data-date="<?php echo esc_html( strtotime( $compare['screenshot2']['updated_at'] ) ); ?>">
			<?php echo esc_html( gmdate( 'd/m/Y H:i.s', strtotime( $compare['screenshot2']['updated_at'] ) ) ); ?>
		</div>
	</div>
</div>
<div class="clear"></div>

<div id="comp-slider" style="width: 49%; float: left;">
	<h2>Screenshots</h2>
	<div id="diff-container"
		data-token="<?php echo esc_html( $token ); ?>"
		style="width: 100%; ">

		<img class="comp-img" style="padding: 0;" src="<?php echo esc_url( $compare['screenshot1']['link'] ); ?>">
		<img style="padding: 0;" src="<?php echo esc_url( $compare['screenshot2']['link'] ); ?>">
	</div>
</div>

<div id="comp_image" class="comp_image" style="width: 49%; float: right; margin-right: 0;">
	<h2>Change Detection</h2>
	<img style="padding: 0;" src="<?php echo esc_url( $compare['link'] ); ?>">
</div>
<div class="clear"></div>
