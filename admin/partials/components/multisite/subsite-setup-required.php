<?php
/**
 * Sub-Site "Setup Required" Notice.
 *
 * Rendered on a sub-site of a network-activated multisite install when no
 * API token exists yet. Account creation is centralized in the network admin,
 * so the sub-site shows a contact-admin notice instead of the sign-up form.
 * If the current user happens to be a super-admin, an extra link points them
 * to the network admin where they can complete the sign-up.
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
?>
<div class="notice notice-info wcd-subsite-setup-required">
	<h2><?php esc_html_e( 'Setup required', 'webchangedetector' ); ?></h2>
	<p>
		<?php
		esc_html_e(
			'WebChange Detector has not been set up for this network yet. Please contact your network administrator to create an account.',
			'webchangedetector'
		);
		?>
	</p>
	<?php if ( current_user_can( 'manage_network_options' ) ) : ?>
		<p>
			<a href="<?php echo esc_url( network_admin_url( 'admin.php?page=webchangedetector' ) ); ?>" class="button button-primary">
				<?php esc_html_e( 'Go to Network Admin', 'webchangedetector' ); ?>
			</a>
		</p>
	<?php endif; ?>
</div>
