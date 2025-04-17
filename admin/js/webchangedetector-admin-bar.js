(function ($) {
    'use strict';

    $(function () {
        // Ensure admin bar data is available.
        if (typeof wcdAdminBarData === 'undefined') {
            console.error('WCD Admin Bar Error: Localized data not found.');
            return;
        }

        // Event listener for the toggle switches.
        $('#wp-admin-bar-wcd-admin-bar').on('change', 'input.wcd-admin-bar-toggle', function (e) {
            console.log('[WCD Admin Bar] Slider change event triggered!'); // DEBUG: Check if handler fires
            e.preventDefault(); // Prevent default checkbox behavior just in case.

            var $checkbox = $(this);
            var type = $checkbox.data('type'); // 'manual' or 'monitoring'
            var device = $checkbox.data('device'); // 'desktop' or 'mobile'
            var urlId = $checkbox.data('url-id');
            var groupId = $checkbox.data('group-id');
            var isChecked = $checkbox.is(':checked');

            console.log('Toggle:', { type: type, device: device, urlId: urlId, groupId: groupId, isChecked: isChecked });

            // Determine which group ID and URL ID to use based on the type.
            var targetUrlId = null;
            var targetGroupId = null;

            if (type === 'manual') {
                targetGroupId = wcdAdminBarData.manual_group_uuid;
                // Need to potentially find the URL ID specific to this group if not already on the element.
                // For now, we assume the ID passed in data-url-id is correct for the respective group.
                targetUrlId = urlId;
            } else if (type === 'monitoring') {
                targetGroupId = wcdAdminBarData.monitoring_group_uuid;
                // As above, assume the passed urlId is correct for this group.
                targetUrlId = urlId;
            }

            if (!targetUrlId || !targetGroupId) {
                console.error('WCD Admin Bar Error: Missing URL ID or Group ID for AJAX call.', { urlId: targetUrlId, groupId: targetGroupId });
                // Optionally provide user feedback here.
                // Revert checkbox state?
                // $checkbox.prop('checked', !isChecked);
                return;
            }

            // Prepare AJAX data - mirror structure potentially used by ajax_update_url / post_urls.
            // Keys should be dynamic: desktop-<url_id> and mobile-<url_id>.
            var ajaxData = {
                action: 'post_url', // Use 'post_url' action based on get_url_settings onclick
                _ajax_nonce: wcdAdminBarData.nonce,
                group_id: targetGroupId,
                // Add url_id separately as it might be needed directly by the handler
                url_id: targetUrlId
                // Dynamic keys for desktop/mobile status will be added below
            };

            // Find the sibling checkbox for the other device within the same type section.
            var $otherCheckbox = null;
            if (device === 'desktop') {
                ajaxData['desktop-' + targetUrlId] = isChecked ? 1 : 0;
                $otherCheckbox = $checkbox.closest('.wcd-slider-node').siblings().find('input.wcd-admin-bar-toggle[data-device="mobile"]');
                if ($otherCheckbox.length) {
                    ajaxData['mobile-' + targetUrlId] = $otherCheckbox.is(':checked') ? 1 : 0;
                } else {
                    // If sibling doesn't exist for some reason, assume 0 for its value
                    ajaxData['mobile-' + targetUrlId] = 0;
                }
            } else { // device === 'mobile'
                ajaxData['mobile-' + targetUrlId] = isChecked ? 1 : 0;
                $otherCheckbox = $checkbox.closest('.wcd-slider-node').siblings().find('input.wcd-admin-bar-toggle[data-device="desktop"]');
                if ($otherCheckbox.length) {
                    ajaxData['desktop-' + targetUrlId] = $otherCheckbox.is(':checked') ? 1 : 0;
                } else {
                    // If sibling doesn't exist, assume 0
                    ajaxData['desktop-' + targetUrlId] = 0;
                }
            }

            console.log('AJAX Data:', ajaxData);

            // Disable checkbox temporarily to prevent rapid clicks.
            $checkbox.prop('disabled', true);
            if ($otherCheckbox && $otherCheckbox.length) {
                $otherCheckbox.prop('disabled', true);
            }

            // Perform AJAX request.
            $.ajax({
                url: wcdAdminBarData.ajax_url,
                type: 'POST',
                data: ajaxData,
                //dataType: 'json', // We are getting html back, not json.
                success: function (response) {
                    console.log('WCD Admin Bar AJAX Success:', response);
                    // Optimistically assume success if the request didn't error.
                    // The checked state is already visually set by the user click.
                    // We only need to revert it on explicit failure (in the error callback).
                    console.log('Settings update assumed successful based on request completion.');
                },
                error: function (jqXHR, textStatus, errorThrown) {
                    console.error('WCD Admin Bar AJAX Error:', textStatus, errorThrown);
                    // Revert checkbox state visually ONLY on explicit error.
                    $checkbox.prop('checked', !isChecked);
                    alert('Failed to update setting. Please try again.'); // Basic user feedback.
                },
                complete: function () {
                    // Re-enable checkbox after request completes.
                    $checkbox.prop('disabled', false);
                    if ($otherCheckbox && $otherCheckbox.length) {
                        $otherCheckbox.prop('disabled', false);
                    }
                }
            });
        });
    });

})(jQuery); 