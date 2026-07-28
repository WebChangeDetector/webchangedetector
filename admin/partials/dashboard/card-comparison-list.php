<?php
/**
 * Shared comparison-list card body — used by both the Latest Detected Changes
 * card (status=new) and the Recently AI-Cleared card (status=ok + above_threshold).
 *
 * Renders the most recent comparisons for THIS website with domain, relative
 * timestamp, and the per-comparison AI summary when available. Falls back to the
 * visual-diff percentage when no AI summary is present.
 *
 * @package    WebChangeDetector
 * @subpackage WebChangeDetector/admin/partials
 *
 * @var array  $comparisons  Raw response from WebChangeDetector_API_V2::get_comparisons_v2().
 * @var string $card_title   Card heading.
 * @var string $card_intro   One-line description under the heading.
 * @var string $card_icon    Dashicon name (without the "dashicons-" prefix).
 * @var string $card_icon_modifier CSS modifier class for the icon color.
 * @var string $empty_state  Message shown when there are no rows.
 * @var string $view_all_url Target for the "View all" link.
 */

defined( 'ABSPATH' ) || exit;

$rows            = isset( $comparisons['data'] ) && is_array( $comparisons['data'] ) ? $comparisons['data'] : array();
$detection_base  = admin_url( 'admin.php?page=webchangedetector-show-detection' );
$detection_nonce = wp_create_nonce( 'show_change_detection' );
?>
<div class="wcd-card-header">
	<h2>
		<span class="dashicons dashicons-<?php echo esc_attr( $card_icon ); ?> <?php echo esc_attr( $card_icon_modifier ); ?>"></span>
		<?php echo esc_html( $card_title ); ?>
	</h2>
</div>
<div class="wcd-card-content">
	<p class="wcd-card-intro"><?php echo esc_html( $card_intro ); ?></p>

	<?php if ( empty( $rows ) ) : ?>
		<p class="wcd-stat-empty"><?php echo esc_html( $empty_state ); ?></p>
	<?php else : ?>
		<ul class="wcd-comparison-list">
			<?php foreach ( $rows as $comparison ) : ?>
				<?php
				$url            = $comparison['url'] ?? '';
				$host           = $url ? wp_parse_url( $url, PHP_URL_HOST ) : '';
				$when           = $comparison['screenshot_2_created_at'] ?? ( $comparison['screenshot_1_created_at'] ?? '' );
				$ts             = $when ? strtotime( $when ) : 0;
				$ago            = $ts ? sprintf( /* translators: %s: human-readable time difference. */ __( '%s ago', 'webchangedetector' ), human_time_diff( $ts, time() ) ) : '';
				$summary        = $comparison['ai_verification_result']['summary'] ?? '';
				$diff           = isset( $comparison['difference_percent'] ) ? floatval( $comparison['difference_percent'] ) : 0;
				$comparison_id  = $comparison['id'] ?? '';
				$detection_link = $comparison_id ? add_query_arg(
					array(
						'id'       => rawurlencode( $comparison_id ),
						'_wpnonce' => $detection_nonce,
					),
					$detection_base
				) : '';
				?>
				<li class="wcd-comparison-list-item">
					<div class="wcd-comparison-list-meta">
						<?php if ( $detection_link ) : ?>
							<a href="<?php echo esc_url( $detection_link ); ?>" class="wcd-comparison-list-domain">
								<?php echo esc_html( $host ? $host : $url ); ?>
							</a>
						<?php else : ?>
							<span class="wcd-comparison-list-domain"><?php echo esc_html( $host ? $host : $url ); ?></span>
						<?php endif; ?>
						<?php if ( $ago ) : ?>
							<span class="wcd-comparison-list-when"><?php echo esc_html( $ago ); ?></span>
						<?php endif; ?>
					</div>
					<?php if ( $summary ) : ?>
						<p class="wcd-comparison-list-summary"><?php echo esc_html( $summary ); ?></p>
					<?php elseif ( $diff > 0 ) : ?>
						<p class="wcd-comparison-list-summary wcd-comparison-list-summary-fallback">
							<?php
							/* translators: %s: visual difference percentage. */
							echo esc_html( sprintf( __( '%s%% visual diff', 'webchangedetector' ), number_format_i18n( $diff, 2 ) ) );
							?>
						</p>
					<?php endif; ?>
				</li>
			<?php endforeach; ?>
		</ul>
		<a href="<?php echo esc_url( $view_all_url ); ?>" class="wcd-stat-link"><?php echo esc_html__( 'View all checks →', 'webchangedetector' ); ?></a>
	<?php endif; ?>
</div>
