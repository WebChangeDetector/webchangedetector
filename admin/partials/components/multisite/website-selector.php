<?php
/**
 * Website Selector Component for Multisite Network Admin.
 *
 * Renders a dropdown to switch between sub-sites in the network admin.
 * Includes an "All Websites" option for bulk management.
 *
 * @package    WebChangeDetector
 * @subpackage WebChangeDetector/admin/partials/components/multisite
 * @since      4.3.0
 */

namespace WebChangeDetector;

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$sites        = WebChangeDetector_Multisite::get_all_sites_with_status();
$current_page = isset( $_GET['page'] ) ? sanitize_text_field( wp_unslash( $_GET['page'] ) ) : 'webchangedetector'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

// Read raw wcd_blog_id to detect "all" mode (absint would turn 'all' into 0).
// phpcs:ignore WordPress.Security.NonceVerification.Recommended
$raw_blog_id     = isset( $_GET['wcd_blog_id'] ) ? sanitize_text_field( wp_unslash( $_GET['wcd_blog_id'] ) ) : '';
$is_all_selected = ( 'all' === $raw_blog_id );
$current_blog_id = $is_all_selected ? 0 : WebChangeDetector_Multisite::get_current_managed_blog_id();

// Count registered sites for the "All Websites" label.
$registered_count = 0;
foreach ( $sites as $site ) {
	if ( $site['registered'] ) {
		++$registered_count;
	}
}
?>
<div class="wcd-multisite-selector">
	<form method="get" action="<?php echo esc_url( network_admin_url( 'admin.php' ) ); ?>">
		<input type="hidden" name="page" value="<?php echo esc_attr( $current_page ); ?>">
		<label for="wcd-blog-selector">
			<strong><?php esc_html_e( 'Site:', 'webchangedetector' ); ?></strong>
		</label>
		<select name="wcd_blog_id" id="wcd-blog-selector" onchange="this.form.submit()">
			<option value="all" <?php selected( $is_all_selected ); ?>>
				<?php
				printf(
					/* translators: %d: number of registered sites */
					esc_html__( 'All Websites (%d)', 'webchangedetector' ),
					(int) $registered_count
				);
				?>
			</option>
			<?php foreach ( $sites as $site ) : ?>
				<option value="<?php echo esc_attr( $site['blog_id'] ); ?>"
					<?php selected( ! $is_all_selected && (int) $site['blog_id'] === $current_blog_id ); ?>
				>
					<?php echo esc_html( $site['url'] ); ?>
					<?php if ( ! $site['registered'] ) : ?>
						<?php echo esc_html__( '(not registered)', 'webchangedetector' ); ?>
					<?php endif; ?>
				</option>
			<?php endforeach; ?>
		</select>
		<noscript>
			<input type="submit" value="<?php esc_attr_e( 'Switch', 'webchangedetector' ); ?>" class="button button-secondary">
		</noscript>
	</form>
</div>
<?php
