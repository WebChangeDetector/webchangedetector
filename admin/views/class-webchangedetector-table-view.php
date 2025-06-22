<?php
/**
 * Table View Component for WebChangeDetector
 *
 * Handles rendering of tables and data grids.
 *
 * @package    WebChangeDetector
 * @subpackage WebChangeDetector/admin/views
 * @author     Mike Miler <mike@wp-mike.com>
 */

namespace WebChangeDetector;

/**
 * Table View Component Class.
 */
class WebChangeDetector_Table_View {

	/**
	 * The admin instance.
	 *
	 * @var WebChangeDetector_Admin
	 */
	private $admin;

	/**
	 * Constructor.
	 *
	 * @param WebChangeDetector_Admin $admin The admin instance.
	 */
	public function __construct( $admin ) {
		$this->admin = $admin;
	}

	/**
	 * Render queue table.
	 *
	 * @param array $queues The queue data.
	 */
	public function render_queue_table( $queues ) {
		$type_nice_name = array(
			'pre'     => 'Pre-update screenshot',
			'post'    => 'Post-update screenshot',
			'auto'    => 'Monitoring screenshot',
			'compare' => 'Change detection',
		);

		?>
		<table class="queue">
			<tr>
				<th></th>
				<th style="width: 100%">Page & URL</th>
				<th style="min-width: 150px;">Type</th>
				<th>Status</th>
				<th style="min-width: 120px;">Time added /<br> Time updated</th>
				<th>Show</th>
			</tr>
			<?php if ( ! empty( $queues ) && is_iterable( $queues ) ) : ?>
				<?php foreach ( $queues as $queue ) : ?>
					<?php $group_type = $queue['monitoring'] ? 'Monitoring' : 'Manual Checks'; ?>
					<tr class="queue-status-<?php echo esc_attr( $queue['status'] ); ?>">
						<td>
							<?php \WebChangeDetector\WebChangeDetector_Admin_Utils::get_device_icon( $queue['device'] ); ?>
						</td>
						<td>
							<span class="html-title queue"><?php echo esc_html( $queue['html_title'] ); ?></span><br>
							<span class="url queue">URL: <?php echo esc_url( $queue['url_link'] ); ?></span><br>
							<?php echo esc_html( $group_type ); ?>
						</td>
						<td><?php echo esc_html( $type_nice_name[ $queue['sc_type'] ] ); ?></td>
						<td><?php echo esc_html( ucfirst( $queue['status'] ) ); ?></td>
						<td>
							<span class="local-time" data-date="<?php echo esc_attr( strtotime( $queue['created_at'] ) ); ?>">
								<?php echo esc_html( gmdate( 'd/m/Y H:i:s', strtotime( $queue['created_at'] ) ) ); ?>
							</span><br>
							<span class="local-time" data-date="<?php echo esc_attr( strtotime( $queue['updated_at'] ) ); ?>">
								<?php echo esc_html( gmdate( 'd/m/Y H:i:s', strtotime( $queue['updated_at'] ) ) ); ?>
							</span>
						</td>
						<td>
							<?php if ( in_array( $queue['sc_type'], array( 'pre', 'post', 'auto', 'compare' ), true ) && 'done' === $queue['status'] && ! empty( $queue['image_link'] ) ) : ?>
								<form method="post" action="?page=webchangedetector-show-screenshot">
									<button class="button" type="submit" name="img_url" value="<?php echo esc_url( $queue['image_link'] ); ?>">Show</button>
								</form>
							<?php endif; ?>
						</td>
					</tr>
				<?php endforeach; ?>
			<?php else : ?>
				<tr>
					<td colspan="7" style="text-align: center; font-weight: 700; background-color: #fff;">
						Nothing to show yet.
					</td>
				</tr>
			<?php endif; ?>
		</table>
		<?php
	}

	/**
	 * Render pagination.
	 *
	 * @param array  $pagination The pagination data.
	 * @param string $page_param The page parameter name.
	 * @param array  $filters    Additional filters for pagination links.
	 */
	public function render_pagination( $pagination, $page_param = 'webchangedetector-logs', $filters = array() ) {
		?>
		<div class="tablenav">
			<div class="tablenav-pages">
				<span class="displaying-num"><?php echo esc_html( $pagination['total'] ); ?> items</span>
				<span class="pagination-links">
					<?php foreach ( $pagination['links'] as $link ) : ?>
						<?php
						$url_params = \WebChangeDetector\WebChangeDetector_Admin_Utils::get_params_of_url( $link['url'] );
						$page_num   = $url_params['page'] ?? 1;
						$class      = ! $link['url'] || $link['active'] ? 'disabled' : '';
						$query_string = ! empty( $filters ) ? '&' . http_build_query( $filters ) : '';
						?>
						<?php if ( ! $link['url'] || $link['active'] ) : ?>
							<span class="tablenav-pages-navspan button disabled"><?php echo esc_html( $link['label'] ); ?></span>
						<?php else : ?>
							<a class="tablenav-pages-navspan button" 
							   href="?page=<?php echo esc_attr( $page_param ); ?>&paged=<?php echo esc_attr( $page_num ); ?><?php echo esc_html( $query_string ); ?>">
								<?php echo esc_html( $link['label'] ); ?>
							</a>
						<?php endif; ?>
					<?php endforeach; ?>
				</span>
			</div>
		</div>
		<?php
	}

	/**
	 * Render settings table.
	 *
	 * @param array $settings The settings data.
	 */
	public function render_settings_table( $settings ) {
		?>
		<table class="form-table">
			<?php foreach ( $settings as $setting ) : ?>
				<tr valign="top">
					<th scope="row">
						<label><?php echo esc_html( $setting['label'] ); ?></label>
					</th>
					<td>
						<?php if ( ! empty( $setting['description'] ) ) : ?>
							<p class="description" style="margin-bottom: 10px;"><?php echo wp_kses_post( $setting['description'] ); ?></p>
						<?php endif; ?>
						<?php echo $setting['content']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					</td>
				</tr>
			<?php endforeach; ?>
		</table>
		<?php
	}

	/**
	 * Render comparison table.
	 *
	 * @param array $comparisons The comparison data.
	 */
	public function render_comparison_table( $comparisons ) {
		if ( empty( $comparisons ) ) {
			echo '<p style="text-align: center; font-weight: 700;">No comparisons found for the selected filters.</p>';
			return;
		}

		?>
		<div class="comparisons-table">
			<?php foreach ( $comparisons as $comparison ) : ?>
				<div class="comparison-row">
					<div class="comparison-details">
						<h3><?php echo esc_html( $comparison['title'] ?? 'Comparison' ); ?></h3>
						<p>
							<strong>URL:</strong> <?php echo esc_url( $comparison['url'] ?? '' ); ?><br>
							<strong>Device:</strong> <?php echo esc_html( $comparison['device'] ?? '' ); ?><br>
							<strong>Status:</strong> <?php echo esc_html( ucfirst( $comparison['status'] ?? '' ) ); ?><br>
							<strong>Date:</strong> <?php echo esc_html( $comparison['created_at'] ?? '' ); ?>
						</p>
					</div>
					
					<?php if ( ! empty( $comparison['image_before'] ) && ! empty( $comparison['image_after'] ) ) : ?>
						<div class="comparison-images">
							<div class="image-before">
								<h4>Before</h4>
								<img src="<?php echo esc_url( $comparison['image_before'] ); ?>" alt="Before" style="max-width: 300px;">
							</div>
							<div class="image-after">
								<h4>After</h4>
								<img src="<?php echo esc_url( $comparison['image_after'] ); ?>" alt="After" style="max-width: 300px;">
							</div>
							<?php if ( ! empty( $comparison['image_diff'] ) ) : ?>
								<div class="image-diff">
									<h4>Difference</h4>
									<img src="<?php echo esc_url( $comparison['image_diff'] ); ?>" alt="Difference" style="max-width: 300px;">
								</div>
							<?php endif; ?>
						</div>
					<?php endif; ?>
				</div>
				<hr>
			<?php endforeach; ?>
		</div>
		<?php
	}

	/**
	 * Render simple data table.
	 *
	 * @param array $headers Table headers.
	 * @param array $rows    Table rows.
	 * @param array $attributes Table attributes.
	 */
	public function render_data_table( $headers, $rows, $attributes = array() ) {
		$attr_string = '';
		foreach ( $attributes as $key => $value ) {
			$attr_string .= ' ' . esc_attr( $key ) . '="' . esc_attr( $value ) . '"';
		}

		?>
		<table<?php echo $attr_string; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
			<?php if ( ! empty( $headers ) ) : ?>
				<thead>
					<tr>
						<?php foreach ( $headers as $header ) : ?>
							<th><?php echo esc_html( $header ); ?></th>
						<?php endforeach; ?>
					</tr>
				</thead>
			<?php endif; ?>
			<tbody>
				<?php if ( ! empty( $rows ) ) : ?>
					<?php foreach ( $rows as $row ) : ?>
						<tr>
							<?php foreach ( $row as $cell ) : ?>
								<td><?php echo wp_kses_post( $cell ); ?></td>
							<?php endforeach; ?>
						</tr>
					<?php endforeach; ?>
				<?php else : ?>
					<tr>
						<td colspan="<?php echo count( $headers ); ?>" style="text-align: center;">
							No data available.
						</td>
					</tr>
				<?php endif; ?>
			</tbody>
		</table>
		<?php
	}
} 