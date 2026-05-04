/**
 * Multisite Default Allowances Management JavaScript.
 *
 * Saves the network-wide default allowances applied to newly registered
 * sub-sites. Only present in the network admin Sites page (All Websites mode).
 *
 * @package    WebChangeDetector
 * @subpackage WebChangeDetector/admin/js
 * @since      4.3.0
 */

(function($) {
	'use strict';

	$('#wcd-save-default-allowances').on('click', function() {
		var $button = $(this);
		var $status = $('#wcd-save-default-allowances-status');

		var data = {
			action: 'wcd_save_default_allowances',
			nonce: wcdAjaxData.nonce
		};

		$('.wcd-default-allowances-manager input[name^="default_allowances_"]').each(function() {
			var $input = $(this);
			var name = $input.attr('name');
			if ($input.attr('type') === 'checkbox') {
				data[name] = $input.is(':checked') ? '1' : '0';
			}
		});

		$button.prop('disabled', true);
		$status.text(wcdDefaultAllowancesData.i18n.saving).removeClass('wcd-status-error wcd-status-success');

		$.post(wcdAjaxData.ajax_url, data)
			.done(function(response) {
				if (response.success) {
					$status.text(response.data.message).addClass('wcd-status-success');
				} else {
					var msg = (response.data && response.data.message) || wcdDefaultAllowancesData.i18n.saveFailed;
					$status.text(msg).addClass('wcd-status-error');
				}
			})
			.fail(function() {
				$status.text(wcdDefaultAllowancesData.i18n.requestFailed).addClass('wcd-status-error');
			})
			.always(function() {
				$button.prop('disabled', false);
			});
	});
})(jQuery);
