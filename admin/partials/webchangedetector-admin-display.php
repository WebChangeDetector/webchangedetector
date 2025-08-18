<?php
/**
 * Provide a admin area view for the plugin
 *
 * This file is used to markup the admin-facing aspects of the plugin.
 * REFACTORED VERSION - Uses controller-based architecture.
 *
 * @link       wp-mike.com
 * @since      1.0.0
 *
 * @package    WebChangeDetector
 * @subpackage WebChangeDetector/admin/partials
 */

if ( ! function_exists( 'wcd_webchangedetector_init' ) ) {

	/**
	 * Init for plugin view
	 *
	 * This function has been refactored to use the new controller-based architecture.
	 * The massive 1000+ line function has been broken down into specialized controllers.
	 * All required classes are now loaded in the main plugin loading process.
	 *
	 * @return bool|void
	 */
	function wcd_webchangedetector_init() {
		// Create admin instance.
		$admin = new \WebChangeDetector\WebChangeDetector_Admin();

		// Create and initialize the main controller.
		$controller = new \WebChangeDetector\WebChangeDetector_Admin_Controller( $admin );

		// Add loading overlay for account creation.
		$admin->view_renderer->render_loading_overlay( 'We\'re getting your account ready' );
		?>
		
		<script type="text/javascript">
			document.addEventListener('DOMContentLoaded', function() {
				// AJAX account creation functionality.
				if (typeof createAccountViaAjax !== 'undefined' && createAccountViaAjax) {
					// Show loading overlay immediately.
					document.getElementById('wcd-loading-overlay').style.display = 'flex';
					
					// Make AJAX request to create website and groups.
					var xhr = new XMLHttpRequest();
					xhr.open('POST', ajaxurl, true);
					xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
					
					xhr.onreadystatechange = function() {
						if (xhr.readyState === 4) {
							if (xhr.status === 200) {
								try {
									var response = JSON.parse(xhr.responseText);
									if (response.success) {
										// Account created successfully, reload the page.
										window.location.reload();
									} else {
										// Hide loading and show error.
										document.getElementById('wcd-loading-overlay').style.display = 'none';
										alert('Error creating account: ' + (response.data.message || 'Unknown error'));
									}
								} catch (e) {
									document.getElementById('wcd-loading-overlay').style.display = 'none';
									alert('Error processing response');
								}
							} else {
								document.getElementById('wcd-loading-overlay').style.display = 'none';
								alert('Network error creating account');
							}
						}
					};
					
					xhr.send('action=wcd_create_website_and_groups&nonce=' + encodeURIComponent(wcdNonce));
				}
			});
		</script>
		<?php

		// Initialize and run the controller.
		return $controller->init();
	}
}
