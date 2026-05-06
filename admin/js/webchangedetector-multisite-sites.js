/**
 * Multisite Sites Management Page JavaScript.
 *
 * Handles site registration and bulk registration for the
 * multisite sites management page in the network admin.
 *
 * @package    WebChangeDetector
 * @subpackage WebChangeDetector/admin/js
 * @since      4.3.0
 */

(function($) {
	'use strict';

	/**
	 * Register a single site via AJAX.
	 *
	 * @param {number} blogId  The blog ID to register.
	 * @param {jQuery} $button The button element that was clicked.
	 * @return {jqXHR} The AJAX promise.
	 */
	function registerSite(blogId, $button) {
		var originalText = $button.text();
		$button.prop('disabled', true).text(wcdMultisiteData.i18n.registering);

		return $.post(wcdAjaxData.ajax_url, {
			action: 'wcd_register_multisite',
			nonce: wcdAjaxData.nonce,
			wcd_blog_id: blogId
		}).done(function(response) {
			if (response.success) {
				var $row = $button.closest('tr');
				$row.find('.wcd-site-status').html(
					'<span class="wcd-site-status-registered">' + wcdMultisiteData.i18n.registered + '</span>'
				);
				$button.remove();
			} else {
				$button.prop('disabled', false).text(originalText);
				alert(response.data.message || wcdMultisiteData.i18n.registrationFailed);
			}
		}).fail(function() {
			$button.prop('disabled', false).text(originalText);
			alert(wcdMultisiteData.i18n.requestFailed);
		});
	}

	// Single site registration.
	$(document).on('click', '.wcd-register-site', function() {
		var $button = $(this);
		var blogId = $button.data('blog-id');
		registerSite(blogId, $button);
	});

	// Register all unregistered sites.
	$('#wcd-register-all-sites').on('click', function() {
		var $button = $(this);
		var $status = $('#wcd-register-all-status');
		var $unregistered = $('.wcd-register-site');
		var total = $unregistered.length;
		var current = 0;
		var succeeded = 0;
		var failed = 0;

		if (!confirm(wcdMultisiteData.i18n.confirmRegisterAll.replace('%d', total))) {
			return;
		}

		$button.prop('disabled', true);
		$status.text('0 / ' + total);

		// Register sites sequentially to avoid overwhelming the API.
		function registerNext() {
			var $next = $('.wcd-register-site').first();
			if ($next.length === 0) {
				var summary = wcdMultisiteData.i18n.allRegistered;
				if (failed > 0) {
					summary = wcdMultisiteData.i18n.registrationSummary
						.replace('%1$d', succeeded)
						.replace('%2$d', failed);
				}
				$status.text(summary);
				if (failed === 0) {
					$button.hide();
				} else {
					$button.prop('disabled', false);
				}
				return;
			}
			current++;
			$status.text(current + ' / ' + total);
			registerSite($next.data('blog-id'), $next)
				.done(function(response) {
					if (response.success) {
						succeeded++;
					} else {
						failed++;
					}
				})
				.fail(function() {
					failed++;
				})
				.always(registerNext);
		}

		registerNext();
	});
})(jQuery);
