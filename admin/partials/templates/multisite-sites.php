<?php
/**
 * Multisite Sites Management Page.
 *
 * Lists all sites in the network with their WCD registration status.
 * Allows registering sites with the WCD API.
 *
 * @package    WebChangeDetector
 * @subpackage WebChangeDetector/admin/partials/templates
 * @since      4.3.0
 */

namespace WebChangeDetector;

defined( 'ABSPATH' ) || exit;

$sites            = WebChangeDetector_Multisite::get_all_sites_with_status();
$registered_count = 0;
foreach ( $sites as $site ) {
	if ( $site['registered'] ) {
		++$registered_count;
	}
}
$total_count = count( $sites );
?>

<div class="wcd-multisite-sites-management">
	<h2><?php esc_html_e( 'Network Sites', 'webchangedetector' ); ?></h2>
	<p>
		<?php
		printf(
			/* translators: 1: registered count, 2: total count */
			esc_html__( '%1$d of %2$d sites registered with WebChange Detector.', 'webchangedetector' ),
			(int) $registered_count,
			(int) $total_count
		);
		?>
	</p>

	<?php if ( $registered_count < $total_count ) : ?>
		<div class="wcd-multisite-actions">
			<button type="button" class="button button-primary" id="wcd-register-all-sites">
				<?php esc_html_e( 'Register All Unregistered Sites', 'webchangedetector' ); ?>
			</button>
			<span id="wcd-register-all-status"></span>
		</div>
	<?php endif; ?>

	<table class="wcd-multisite-sites-table widefat">
		<thead>
			<tr>
				<th><?php esc_html_e( 'Site', 'webchangedetector' ); ?></th>
				<th><?php esc_html_e( 'URL', 'webchangedetector' ); ?></th>
				<th><?php esc_html_e( 'Status', 'webchangedetector' ); ?></th>
				<th><?php esc_html_e( 'Actions', 'webchangedetector' ); ?></th>
			</tr>
		</thead>
		<tbody>
			<?php foreach ( $sites as $site ) : ?>
				<tr data-blog-id="<?php echo esc_attr( $site['blog_id'] ); ?>">
					<td>
						<strong><?php echo esc_html( $site['domain'] . $site['path'] ); ?></strong>
						<br>
						<small>
							<?php
							printf(
								/* translators: %d: blog ID */
								esc_html__( 'Blog ID: %d', 'webchangedetector' ),
								(int) $site['blog_id']
							);
							?>
						</small>
					</td>
					<td>
						<a href="<?php echo esc_url( $site['url'] ); ?>" target="_blank">
							<?php echo esc_html( $site['url'] ); ?>
						</a>
					</td>
					<td class="wcd-site-status">
						<?php if ( $site['registered'] ) : ?>
							<span class="wcd-site-status-registered">
								<?php esc_html_e( 'Registered', 'webchangedetector' ); ?>
							</span>
						<?php else : ?>
							<span class="wcd-site-status-not-registered">
								<?php esc_html_e( 'Not Registered', 'webchangedetector' ); ?>
							</span>
						<?php endif; ?>
					</td>
					<td>
						<?php if ( ! $site['registered'] ) : ?>
							<button type="button" class="button button-primary button-small wcd-register-site" data-blog-id="<?php echo esc_attr( $site['blog_id'] ); ?>">
								<?php esc_html_e( 'Register', 'webchangedetector' ); ?>
							</button>
						<?php endif; ?>
					</td>
				</tr>
			<?php endforeach; ?>
		</tbody>
	</table>
</div>

