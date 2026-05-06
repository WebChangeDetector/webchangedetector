<?php
/**
 * Sub-Site "Not Registered" Notice (Super-Admin in Network Admin).
 *
 * Rendered when a super-admin selects a sub-site in the network-admin
 * dropdown that has not been registered with the WebChange Detector API.
 * Replaces the previous silent auto-registration so admins make a deliberate
 * choice before allocating an account slot.
 *
 * @package    WebChangeDetector
 * @subpackage WebChangeDetector/admin/partials/components/multisite
 */

namespace WebChangeDetector;

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="notice notice-warning wcd-subsite-unregistered">
	<h2><?php esc_html_e( 'Sub-site not registered', 'webchangedetector' ); ?></h2>
	<p>
		<?php
		esc_html_e(
			'This sub-site has not been registered with WebChange Detector yet. Open the Network Sites page to register it (individually or in bulk).',
			'webchangedetector'
		);
		?>
	</p>
	<p>
		<a href="<?php echo esc_url( network_admin_url( 'admin.php?page=webchangedetector-sites' ) ); ?>" class="button button-primary">
			<?php esc_html_e( 'Manage Network Sites', 'webchangedetector' ); ?>
		</a>
	</p>
</div>
