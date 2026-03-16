<?php
/**
 * AI Feedback Rules management page.
 *
 * Lists all AI feedback rules with toggle, scope change, and delete actions.
 *
 * @package    WebChangeDetector
 * @subpackage WebChangeDetector/admin/partials/templates
 * @since      4.1.0
 */

namespace WebChangeDetector;

defined( 'ABSPATH' ) || exit;

$website_id     = get_option( WCD_WP_OPTION_KEY_WEBSITE_ID );
$rules_response = WebChangeDetector_API_V2::get_ai_feedback_rules(
	$website_id ? array( 'website_id' => $website_id ) : array()
);
$rules          = isset( $rules_response['data'] ) ? $rules_response['data'] : array();
?>

<div class="wcd-ai-rules-info">
	<span class="dashicons dashicons-info-outline"></span>
	<p>
		<?php
		echo wp_kses(
			__( 'Rules are created from within change detection views. When reviewing AI-detected changes, click <strong>"Ignore in future"</strong> on any region to create a rule for that type of change.', 'webchangedetector' ),
			array( 'strong' => array() )
		);
		?>
	</p>
</div>

<?php if ( empty( $rules ) ) : ?>

	<div class="wcd-ai-rules-empty">
		<span class="dashicons dashicons-shield"></span>
		<h3><?php esc_html_e( 'No rules yet', 'webchangedetector' ); ?></h3>
		<p><?php esc_html_e( 'Rules help the AI learn which changes are safe to ignore on future comparisons.', 'webchangedetector' ); ?></p>
	</div>

<?php else : ?>

	<div class="wcd-ai-rules-table-wrap">
		<table class="wcd-ai-rules-table">
			<thead>
				<tr>
					<th><?php esc_html_e( 'Status', 'webchangedetector' ); ?></th>
					<th><?php esc_html_e( 'Description', 'webchangedetector' ); ?></th>
					<th><?php esc_html_e( 'Scope', 'webchangedetector' ); ?></th>
					<th><?php esc_html_e( 'URL', 'webchangedetector' ); ?></th>
					<th><?php esc_html_e( 'Group / Website', 'webchangedetector' ); ?></th>
					<th><?php esc_html_e( 'Created', 'webchangedetector' ); ?></th>
					<th><?php esc_html_e( 'Matches', 'webchangedetector' ); ?></th>
					<th><?php esc_html_e( 'Actions', 'webchangedetector' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php
				foreach ( $rules as $rule ) :
					$rule_id        = esc_attr( $rule['id'] ?? '' );
					$description    = esc_html( $rule['description'] ?? '' );
					$scope          = $rule['scope'] ?? 'url';
					$is_broad_scope = in_array( $scope, array( 'group', 'website', 'group_or_website' ), true );
					$is_active      = ! empty( $rule['is_active'] );
					$match_count    = intval( $rule['match_count'] ?? 0 );
					$created_at     = $rule['created_at'] ?? '';
					$last_matched   = $rule['last_matched_at'] ?? '';

					$created_display = '';
					if ( ! empty( $created_at ) ) {
						$timestamp = strtotime( $created_at );
						if ( $timestamp ) {
							$created_display = gmdate( 'M j, Y', $timestamp );
						}
					}

					$matched_display = '';
					if ( ! empty( $last_matched ) ) {
						$timestamp = strtotime( $last_matched );
						if ( $timestamp ) {
							$matched_display = gmdate( 'M j, Y', $timestamp );
						}
					}

					$page_url = '';
					if ( ! empty( $rule['region_context']['page_url'] ) ) {
						$page_url = esc_html( $rule['region_context']['page_url'] );
					}

					$association_type = '';
					$association_name = '';
					if ( ! empty( $rule['website_id'] ) ) {
						$association_type = 'website';
						$association_name = esc_html( $rule['website_name'] ?? '' );
					} elseif ( ! empty( $rule['group_id'] ) ) {
						$association_type = 'group';
						$association_name = esc_html( $rule['group_name'] ?? '' );
					}

					$broad_label = __( 'All URLs', 'webchangedetector' );
					if ( 'website' === $association_type ) {
						$broad_label = __( 'All URLs of website', 'webchangedetector' );
					} elseif ( 'group' === $association_type ) {
						$broad_label = __( 'All URLs of group', 'webchangedetector' );
					}
					?>
				<tr class="wcd-ai-rules-row" data-rule-id="<?php echo esc_attr( $rule_id ); ?>">
					<td class="wcd-ai-rules-cell-status">
						<span class="wcd-toggle-switch">
							<input
								type="checkbox"
								class="wcd-ai-rules-toggle-input"
								data-rule-id="<?php echo esc_attr( $rule_id ); ?>"
								<?php checked( $is_active ); ?>
							>
							<span class="wcd-toggle-slider"></span>
						</span>
					</td>
					<td class="wcd-ai-rules-cell-description">
						<?php echo esc_html( $description ); ?>
					</td>
					<td class="wcd-ai-rules-cell-scope">
						<div
							class="wcd-ai-rules-scope-toggle"
							data-rule-id="<?php echo esc_attr( $rule_id ); ?>"
							data-current-scope="<?php echo esc_attr( $is_broad_scope ? 'group_or_website' : 'url' ); ?>"
						>
							<button type="button" class="wcd-ai-rules-scope-option<?php echo ! $is_broad_scope ? ' is-selected' : ''; ?>" data-scope="url">
								<?php esc_html_e( 'This URL', 'webchangedetector' ); ?>
							</button>
							<button type="button" class="wcd-ai-rules-scope-option<?php echo $is_broad_scope ? ' is-selected' : ''; ?>" data-scope="group_or_website">
								<?php echo esc_html( $broad_label ); ?>
							</button>
						</div>
					</td>
					<td class="wcd-ai-rules-cell-url" title="<?php echo esc_url( $page_url ); ?>">
						<?php echo esc_url( $page_url ); ?>
					</td>
					<td class="wcd-ai-rules-cell-association" title="<?php echo esc_attr( $association_name ); ?>">
						<?php echo esc_html( $association_name ); ?>
					</td>
					<td class="wcd-ai-rules-cell-created">
						<?php echo esc_html( $created_display ); ?>
					</td>
					<td class="wcd-ai-rules-cell-matches">
						<span class="wcd-ai-rules-match-count"><?php echo esc_html( $match_count ); ?></span>
						<?php if ( ! empty( $matched_display ) ) : ?>
							<span class="wcd-ai-rules-match-date">
								<?php
								/* translators: %s: date of last match */
								echo esc_html( sprintf( __( 'Last: %s', 'webchangedetector' ), $matched_display ) );
								?>
							</span>
						<?php endif; ?>
					</td>
					<td class="wcd-ai-rules-cell-actions">
						<button
							type="button"
							class="wcd-ai-rules-delete-btn"
							data-rule-id="<?php echo esc_attr( $rule_id ); ?>"
							title="<?php esc_attr_e( 'Delete rule', 'webchangedetector' ); ?>"
						>
							<span class="dashicons dashicons-trash"></span>
						</button>
					</td>
				</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
	</div>

<?php endif; ?>
