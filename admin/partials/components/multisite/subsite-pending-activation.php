<?php
/**
 * Sub-Site "Pending Activation" Notice (Site-Admin context).
 *
 * Rendered when a sub-site admin (or a super-admin browsing the site admin
 * directly) opens the WebChange Detector plugin on a sub-site that has not
 * been registered yet. Registration is reserved for the super-admin in the
 * network admin, so this notice instructs the user to contact them.
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
<div class="notice notice-info wcd-subsite-pending-activation">
	<h2><?php esc_html_e( 'Activation pending', 'webchangedetector' ); ?></h2>
	<p>
		<?php
		esc_html_e(
			'This sub-site has not been activated for WebChange Detector yet. Please ask the network administrator to register this site from the network admin.',
			'webchangedetector'
		);
		?>
	</p>
	<?php if ( current_user_can( 'manage_network_options' ) ) : ?>
		<p>
			<a href="<?php echo esc_url( network_admin_url( 'admin.php?page=webchangedetector-sites' ) ); ?>" class="button button-primary">
				<?php esc_html_e( 'Go to Network Sites', 'webchangedetector' ); ?>
			</a>
		</p>
	<?php endif; ?>
</div>
