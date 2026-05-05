<?php
/**
 * Multisite Sub-Site Allowances Page.
 *
 * Renders the allowances editor for the currently selected sub-site, or the
 * bulk allowances editor + "defaults for new sites" accordion when the global
 * website-selector is in "All Websites" mode.
 *
 * @package    WebChangeDetector
 * @subpackage WebChangeDetector/admin/partials/templates
 * @since      4.5.0
 */

namespace WebChangeDetector;

defined( 'ABSPATH' ) || exit;

$raw_blog_id       = WebChangeDetector_Multisite::get_persisted_blog_context();
$is_all_sites_mode = ( 'all' === $raw_blog_id );
$show_allowances   = false;
$allowances        = array();
$website_uuid      = '';
$wcd_blog_id       = 0;

if ( $is_all_sites_mode ) {
	// In "All Websites" mode, show allowances with main site's values as defaults.
	$main_site_id      = get_main_site_id();
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

} else {
	// Specific site selected, or initial page load (no parameter) → fall back to
	// the site shown selected in the dropdown above (defaults to main site).
	$selected_blog_id = ! empty( $raw_blog_id )
		? absint( $raw_blog_id )
		: WebChangeDetector_Multisite::get_current_managed_blog_id();

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
?>

<div class="wcd-multisite-allowances">
	<h2><?php esc_html_e( 'Sub-Site Allowances', 'webchangedetector' ); ?></h2>

	<?php if ( $show_allowances ) : ?>
		<?php include WCD_PLUGIN_DIR . 'admin/partials/components/multisite/allowances-manager.php'; ?>

		<?php
		// In "All Websites" mode, also render the "Defaults for new sites" accordion.
		// These defaults are applied when a new sub-site is manually registered.
		if ( $is_all_sites_mode ) :
			$default_allowances_values = WebChangeDetector_Multisite::get_shared_option( 'wcd_default_allowances', $allowances );
			include WCD_PLUGIN_DIR . 'admin/partials/components/multisite/default-allowances-manager.php';
		endif;
		?>
	<?php else : ?>
		<div class="notice notice-info inline">
			<p>
				<?php
				printf(
					/* translators: %s: link to the Sites tab */
					wp_kses(
						__( 'No registered sub-site is selected. Register a sub-site on the %s tab, then return here to manage its allowances.', 'webchangedetector' ),
						array( 'a' => array( 'href' => array() ) )
					),
					'<a href="?page=webchangedetector-sites">' . esc_html__( 'Sites', 'webchangedetector' ) . '</a>'
				);
				?>
			</p>
		</div>
	<?php endif; ?>
</div>
