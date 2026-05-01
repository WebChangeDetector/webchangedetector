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

<?php
// Allowances management section.
// Show when a specific registered site is selected or when "All Websites" is selected.
// phpcs:ignore WordPress.Security.NonceVerification.Recommended
$raw_blog_id       = isset( $_GET['wcd_blog_id'] ) ? sanitize_text_field( wp_unslash( $_GET['wcd_blog_id'] ) ) : '';
$is_all_sites_mode = ( 'all' === $raw_blog_id );
$show_allowances   = false;
$allowances        = array();
$website_uuid      = '';
$wcd_blog_id       = 0;

if ( $is_all_sites_mode ) {
	// In "All Websites" mode, show allowances with main site's values as defaults.
	$main_site_id = get_main_site_id();
	$main_website_uuid = WebChangeDetector_Multisite::with_blog(
		$main_site_id,
		function () {
			return get_option( 'webchangedetector_website_id', '' );
		}
	);

	if ( ! empty( $main_website_uuid ) ) {
		$website_response = WebChangeDetector_API_V2::get_website_v2( $main_website_uuid );
		if ( is_array( $website_response ) && ! empty( $website_response['data']['allowances'] ) ) {
			$allowances = $website_response['data']['allowances'];
		}
	}
	$show_allowances = true;

} elseif ( ! empty( $raw_blog_id ) ) {
	// Specific site selected.
	$selected_blog_id = absint( $raw_blog_id );
	if ( $selected_blog_id > 0 ) {
		$site_website_uuid = WebChangeDetector_Multisite::with_blog(
			$selected_blog_id,
			function () {
				return get_option( 'webchangedetector_website_id', '' );
			}
		);

		if ( ! empty( $site_website_uuid ) ) {
			$website_response = WebChangeDetector_API_V2::get_website_v2( $site_website_uuid );
			if ( is_array( $website_response ) && ! empty( $website_response['data']['allowances'] ) ) {
				$allowances      = $website_response['data']['allowances'];
				$website_uuid    = $site_website_uuid;
				$wcd_blog_id     = $selected_blog_id;
				$show_allowances = true;
			}
		}
	}
}

if ( $show_allowances ) :
	include WCD_PLUGIN_DIR . 'admin/partials/components/multisite/allowances-manager.php';
endif;
