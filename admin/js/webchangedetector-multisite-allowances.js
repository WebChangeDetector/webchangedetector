/**
 * Multisite Allowances Management JavaScript.
 *
 * Handles saving allowances for sub-websites via AJAX.
 * Used by the super admin on the network admin Sites page.
 *
 * @package    WebChangeDetector
 * @subpackage WebChangeDetector/admin/js
 * @since      4.3.0
 */

(function($) {
	'use strict';

	$('#wcd-save-allowances').on('click', function() {
		var $button = $(this);
		var $status = $('#wcd-save-allowances-status');
		var websiteUuid = $('#wcd-allowances-website-uuid').val();
		var blogId = $('#wcd-allowances-blog-id').val();

		// Collect all allowance checkbox values.
		var data = {
			action: 'wcd_save_allowances',
			nonce: wcdAjaxData.nonce,
			wcd_blog_id: blogId,
			website_uuid: websiteUuid
		};

		// Gather allowance fields using the hidden + checkbox pattern.
		$('.wcd-allowances-manager input[name^="allowances_"]').each(function() {
			var $input = $(this);
			var name = $input.attr('name');

			// For checkboxes, only use the checkbox value (not the hidden input).
			if ($input.attr('type') === 'checkbox') {
				data[name] = $input.is(':checked') ? '1' : '0';
			}
		});

		$button.prop('disabled', true);
		$status.text(wcdAllowancesData.i18n.saving).removeClass('wcd-status-error wcd-status-success');

		$.post(wcdAjaxData.ajax_url, data)
			.done(function(response) {
				if (response.success) {
					$status.text(response.data.message).addClass('wcd-status-success');
				} else {
					var msg = (response.data && response.data.message) || wcdAllowancesData.i18n.saveFailed;
					$status.text(msg).addClass('wcd-status-error');
				}
			})
			.fail(function() {
				$status.text(wcdAllowancesData.i18n.requestFailed).addClass('wcd-status-error');
			})
			.always(function() {
				$button.prop('disabled', false);
			});
	});
})(jQuery);
