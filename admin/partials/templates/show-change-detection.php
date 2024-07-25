<?php
/**
 * Help - show change detection
 *
 *  @package    webchangedetector
 */

?>
<div class="comparison-tiles">
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
		data-token="<?php echo isset( $_GET['token'] ) ? esc_html( wp_unslash( sanitize_key( $_GET['token'] ) ) ) : esc_html( $compare['token'] ); ?>"
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
