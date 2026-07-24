<?php
/**
 * Auto Update Checks result mail body.
 *
 * Rendered via output buffering in
 * WebChangeDetector_Autoupdates::send_change_detection_mail() and sent through
 * wp_mail(). Inline styles are intentional here: external stylesheets are not
 * available in email clients.
 *
 * @package    WebChangeDetector
 * @subpackage WebChangeDetector/admin/partials
 *
 * @var array  $comparison_rows  Comparison rows from WebChangeDetector_API_V2::get_comparisons_v2()['data'].
 * @var string $batch_ai_summary AI summary for the batch, may be empty.
 */

defined( 'ABSPATH' ) || exit;
?>
<style>
	table {
		border: 1px solid #ccc;
		width: 100%;
	}
	th, td {
		padding: 10px;
		border-top: 1px solid #aaa;
	}
	tr:nth-child(odd),
	{
		background: #F0F0F1;
	}
	th {
		background: #DCE3ED;
	}
</style>
<div style="width: 800px; margin: 0 auto;">
	<p><?php esc_html_e( 'Howdy again, we checked your website for visual changes during the WP auto updates with WebChange Detector. Here are the results:', 'webchangedetector' ); ?></p>

	<?php if ( ! empty( $batch_ai_summary ) ) : ?>
		<div style="background: #f0f4ff; border-left: 4px solid #4a6cf7; padding: 15px; margin: 15px 0;">
			<strong><?php esc_html_e( 'AI Summary:', 'webchangedetector' ); ?></strong><br>
			<?php echo esc_html( $batch_ai_summary ); ?>
		</div>
	<?php endif; ?>

	<?php
	if ( count( $comparison_rows ) ) {
		$no_difference_rows   = '';
		$with_difference_rows = '';

		foreach ( $comparison_rows as $comparison ) {
			$ai_status = $comparison['ai_verification_status'] ?? '';
			$ai_result = $comparison['ai_verification_result'] ?? array();
			$ai_cell   = '<td></td>';

			if ( ! $comparison['difference_percent'] ) {
				$ai_cell = '<td style="color: #888;">' . esc_html__( 'No difference', 'webchangedetector' ) . '</td>';
			} elseif ( 'verified' === $ai_status && ! empty( $ai_result['summary'] ) ) {
				$console_cat = $ai_result['console_analysis']['category'] ?? null;
				$has_alert   = ! empty( $ai_result['alerts'] ) || 'alert' === $console_cat;
				$has_unsure  = ! empty( $ai_result['not_sure'] ) || 'not_sure' === $console_cat;
				$overall     = $has_alert ? 'alert' : ( $has_unsure ? 'not_sure' : 'all_good' );
				$badge_map   = array(
					'alert'    => array(
						'label' => 'Alert',
						'color' => '#c0392b',
					),
					'not_sure' => array(
						'label' => 'Unsure',
						'color' => '#e67e22',
					),
					'all_good' => array(
						'label' => 'OK',
						'color' => '#27ae60',
					),
				);
				$badge       = $badge_map[ $overall ];
				$summary_raw = $ai_result['summary'];
				if ( mb_strlen( $summary_raw ) > 120 ) {
					$summary_raw = mb_substr( $summary_raw, 0, 120 ) . '...';
				}
				$ai_cell = '<td>
					<span style="background:' . esc_attr( $badge['color'] ) . '; color:#fff; padding: 2px 8px; border-radius: 3px; font-size: 12px; font-weight: bold;">' . esc_html( $badge['label'] ) . '</span>
					<span style="font-size: 13px; margin-left: 6px;">' . esc_html( $summary_raw ) . '</span>
				</td>';
			}

			$row =
				'<tr>
					<td>' . esc_html( $comparison['url'] ) . '</td>
					<td>' . esc_html( $comparison['device'] ) . '</td>
					<td>' . esc_html( $comparison['difference_percent'] ) . ' %</td>
					<td><a href="' . esc_url( $comparison['public_link'] ) . '">' . esc_html__( 'See changes', 'webchangedetector' ) . '</a></td>
					' . $ai_cell . '
				</tr>';
			if ( ! $comparison['difference_percent'] ) {
				$no_difference_rows .= $row;
			} else {
				$with_difference_rows .= $row;
			}
		}
		?>
		<div style="width: 300px; margin: 20px auto; text-align: center; padding: 30px; background: #DCE3ED;">
			<?php if ( empty( $with_difference_rows ) ) : ?>
				<div style="padding: 10px;background: green; color: #fff; border-radius: 20px; font-size: 14px; width: 20px; height: 20px; display: inline-block; font-weight: 900; transform: scaleX(-1) rotate(-35deg);">L</div>
				<div style="font-size: 18px; padding-top: 20px;"><?php esc_html_e( 'Checks Passed', 'webchangedetector' ); ?></div>
			<?php else : ?>
				<div style="padding: 10px;background: red; color: #fff; border-radius: 20px;  font-size: 14px; width: 20px; height: 20px; display: inline-block; font-weight: 900; ">X</div>
				<div style="font-size: 18px; padding-top: 20px;"><?php esc_html_e( 'We found changes', 'webchangedetector' ); ?><br><?php esc_html_e( 'Please review the checks.', 'webchangedetector' ); ?></div>
			<?php endif; ?>
		</div>

		<div style="margin: 20px 0 10px 0"><strong><?php esc_html_e( 'Checks with differences', 'webchangedetector' ); ?></strong></div>
		<table>
			<tr><th><?php esc_html_e( 'URL', 'webchangedetector' ); ?></th><th><?php esc_html_e( 'Device', 'webchangedetector' ); ?></th><th><?php esc_html_e( 'Change in %', 'webchangedetector' ); ?></th><th><?php esc_html_e( 'Check', 'webchangedetector' ); ?></th><th><?php esc_html_e( 'AI Analysis', 'webchangedetector' ); ?></th></tr>
			<?php
			if ( ! empty( $with_difference_rows ) ) {
				echo $with_difference_rows; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Rows are built above with escaped values.
			} else {
				echo '<tr><td colspan="5" style="text-align: center;">' . esc_html__( 'No checks to show here', 'webchangedetector' ) . '</td>';
			}
			?>
		</table>

		<div style="margin: 20px 0 10px 0"><strong><?php esc_html_e( 'Checks without differences', 'webchangedetector' ); ?></strong></div>
		<table>
			<tr><th><?php esc_html_e( 'URL', 'webchangedetector' ); ?></th><th><?php esc_html_e( 'Device', 'webchangedetector' ); ?></th><th><?php esc_html_e( 'Change in %', 'webchangedetector' ); ?></th><th><?php esc_html_e( 'Check', 'webchangedetector' ); ?></th><th><?php esc_html_e( 'AI Analysis', 'webchangedetector' ); ?></th></tr>
			<?php
			if ( ! empty( $no_difference_rows ) ) {
				echo $no_difference_rows; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Rows are built above with escaped values.
			} else {
				echo '<tr><td colspan="5" style="text-align: center;">' . esc_html__( 'No checks to show here', 'webchangedetector' ) . '</td>';
			}
			?>
		</table>
		<?php
	} else {
		esc_html_e( 'Sorry, there were no comparisons. Please check your settings in your WebChange Detector Plugin.', 'webchangedetector' );
	}
	?>

	<div style="margin: 20px 0"><?php esc_html_e( 'You can find all checks and settings in your wp-admin dashboard of your website.', 'webchangedetector' ); ?><br><br><?php esc_html_e( 'Your WebChange Detector team', 'webchangedetector' ); ?></div>
</div>
