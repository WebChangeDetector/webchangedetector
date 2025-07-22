(function ($) {
    'use strict';

    $(function () { // Alias for jQuery(document).ready(function($) { ... });
        // Ensure admin bar data is available
        if (typeof wcdAdminBarData === 'undefined') {
            return;
        }

        const wcdAdminBarNode = $('#wp-admin-bar-wcd-admin-bar');
        const placeholderNode = $('#wp-admin-bar-wcd-status-placeholder'); // Target for replacement
        let wcdAdminBarLoaded = false; // Flag to prevent multiple loads

        // Add logging to check if the elements are found
        if (!wcdAdminBarNode.length) {
            console.error('WCD Admin Bar Error: Top-level node #wp-admin-bar-wcd-admin-bar not found.');
            // Attempt alternative selector if needed, e.g. $('#wp-admin-bar-wcd-admin-bar-default')?
        } else {
            console.log('WCD Admin Bar: Found admin bar node.');
        }
        if (!placeholderNode.length) {
            // This might be expected if the user isn't an admin or menu is disabled,
            // but if wcdAdminBarData *was* found, this node *should* exist initially.
            console.warn('WCD Admin Bar Warning: Placeholder node #wp-admin-bar-wcd-status-placeholder not found.');
        } else {
            console.log('WCD Admin Bar: Found placeholder node.');
        }

        // --- Function to generate slider HTML (based on PHP function) ---
        // We replicate the PHP function's output here to avoid another AJAX call
        function generateSliderHtml(type, device, isEnabled, url, urlId, groupId) {
            const checked = isEnabled ? 'checked' : '';
            // Use localized labels passed from PHP
            const label = (device === 'desktop') ? wcdAdminBarData.desktop_label : wcdAdminBarData.mobile_label;
            const uniqueSuffix = (urlId || Math.random().toString(36).substring(7)); // Ensure unique ID
            const id = `wcd-slider-${type}-${device}-${uniqueSuffix.replace(/[^a-zA-Z0-9-_]/g, '-')}`; // Sanitize ID

            // Escape attributes for security (basic JS escaping)
            const escAttr = (str) => {
                if (!str) return '';
                return String(str)
                    .replace(/&/g, '&amp;')
                    .replace(/</g, '&lt;')
                    .replace(/>/g, '&gt;')
                    .replace(/"/g, '&quot;')
                    .replace(/'/g, '&#039;');
            }

            const dataAttrs = `data-type="${escAttr(type)}" data-device="${escAttr(device)}" data-url="${escAttr(url)}" data-url-id="${escAttr(urlId || '')}" data-group-id="${escAttr(groupId || '')}"`;

            return `
                <div class="wcd-admin-bar-slider">
                    <label for="${escAttr(id)}" class="wcd-slider-label">${escAttr(label)}:</label>
                    <label class="wcd-switch">
                        <input type="checkbox" id="${escAttr(id)}" class="wcd-admin-bar-toggle" ${checked} ${dataAttrs}> 
                        <span class="wcd-slider-round"></span>
                    </label>
                </div>
            `;
        }

        // --- Function to build the admin bar menu content ---
        function buildAdminBarMenu(data) {
            let menuItemsHtml = ''; // Build list items <li>...</li>

            if (!data.tracked) {
                menuItemsHtml = `
                    <li id="wcd-status-not-tracked">
                        <div class="ab-item ab-empty-item" aria-hidden="true">${wcdAdminBarData.not_tracked_text}</div>
                    </li>
                `;
            } else {

                const manualDesktopHtml = generateSliderHtml('manual', 'desktop', data.manual_status.desktop, data.current_url, data.wcd_url_id, data.manual_group_uuid);
                const manualMobileHtml = generateSliderHtml('manual', 'mobile', data.manual_status.mobile, data.current_url, data.wcd_url_id, data.manual_group_uuid);
                const monitoringDesktopHtml = generateSliderHtml('monitoring', 'desktop', data.monitoring_status.desktop, data.current_url, data.wcd_url_id, data.monitoring_group_uuid);
                const monitoringMobileHtml = generateSliderHtml('monitoring', 'mobile', data.monitoring_status.mobile, data.current_url, data.wcd_url_id, data.monitoring_group_uuid);

                // Structure needs to be <li> elements for the submenu
                menuItemsHtml = `<hr>
                     <li id="wp-admin-bar-wcd-manual-checks-title" class="wcd-admin-bar-subitem wcd-admin-bar-title">
                         <div class="ab-item ab-empty-item" aria-hidden="true">${wcdAdminBarData.manual_label}</div>
                     </li>
                     <li id="wp-admin-bar-wcd-manual-desktop" class="wcd-admin-bar-subitem wcd-slider-node">
                         <div class="ab-item ab-empty-item" aria-hidden="true">${manualDesktopHtml}</div>
                     </li>
                      <li id="wp-admin-bar-wcd-manual-mobile" class="wcd-admin-bar-subitem wcd-slider-node">
                         <div class="ab-item ab-empty-item" aria-hidden="true">${manualMobileHtml}</div>
                     </li>
                     <hr>
                      <li id="wp-admin-bar-wcd-monitoring-title" class="wcd-admin-bar-subitem wcd-admin-bar-title">
                          <div class="ab-item ab-empty-item" aria-hidden="true">${wcdAdminBarData.monitoring_label}</div>
                      </li>
                      <li id="wp-admin-bar-wcd-monitoring-desktop" class="wcd-admin-bar-subitem wcd-slider-node">
                         <div class="ab-item ab-empty-item" aria-hidden="true">${monitoringDesktopHtml}</div>
                     </li>
                      <li id="wp-admin-bar-wcd-monitoring-mobile" class="wcd-admin-bar-subitem wcd-slider-node">
                         <div class="ab-item ab-empty-item" aria-hidden="true">${monitoringMobileHtml}</div>
                     </li>
                `;
            }
            // Return only the list items, they will be wrapped later
            return menuItemsHtml;
        }

        // --- AJAX Loading Event Listener ---
        wcdAdminBarNode.on('mouseenter', function () {
            console.log('WCD Admin Bar: Mouse entered admin bar node.');

            if (wcdAdminBarLoaded) {
                console.log('WCD Admin Bar: Already loaded, skipping.');
                return; // Already loaded
            }
            if (!placeholderNode.length) {
                console.error('WCD Admin Bar: Placeholder node not found, aborting.');
                return; // Don't proceed if placeholder isn't there
            }

            console.log('WCD Admin Bar: Loading URL status...');
            wcdAdminBarLoaded = true; // Set flag

            placeholderNode.find('.ab-item').text(wcdAdminBarData.loading_text);

            $.ajax({
                url: wcdAdminBarData.ajax_url,
                type: 'POST',
                data: {
                    action: wcdAdminBarData.action, // 'wcd_get_admin_bar_status'
                    nonce: wcdAdminBarData.nonce,
                    current_url: window.location.href
                },
                dataType: 'json',
                success: function (response) {
                    console.log('WCD Admin Bar: AJAX response received:', response);

                    // Find the placeholder LI element
                    const placeholderLi = $('#wp-admin-bar-wcd-status-placeholder');

                    if (!placeholderLi.length) {
                        console.error('WCD Admin Bar JS: Placeholder LI #wp-admin-bar-wcd-status-placeholder disappeared before update!');
                        return;
                    }

                    // Clear the initial "Loading..." text
                    placeholderLi.empty();

                    if (response.success && response.data) {
                        console.log('WCD Admin Bar: Processing successful response with data:', response.data);
                        const menuItemsHtml = buildAdminBarMenu(response.data);

                        if (response.data.tracked === false) {
                            // If not tracked, just put the message inside the placeholder's inner div
                            placeholderLi.html(`<div class="ab-item ab-empty-item" >${wcdAdminBarData.not_tracked_text}</div>`);
                            // Ensure it doesn't act like a submenu parent
                            placeholderLi.removeClass('menupop');
                        } else {
                            // Construct the standard WP Admin Bar submenu structure
                            const submenuHtml = `
                                <ul id="wp-admin-bar-wcd-admin-bar-default" class="ab-submenu">
                                    ${menuItemsHtml} 
                                </ul>`;

                            // Append the submenu structure inside the placeholder LI
                            placeholderLi.append(submenuHtml);
                            // Add the necessary class for hover display
                            placeholderLi.addClass('menupop');
                        }

                        // Trigger custom event for other scripts
                        $(document).trigger('wcd_admin_bar_loaded', response.data);

                    } else {
                        const errorMsg = response.data?.message || wcdAdminBarData.error_text;
                        console.error('WCD Admin Bar JS: Load Status AJAX Error (Success false or no data)', response);
                        // Put error message inside the placeholder's inner div
                        placeholderLi.html(`<div class="ab-item ab-empty-item" aria-hidden="true">${errorMsg}</div>`);
                        placeholderLi.removeClass('menupop');
                    }
                },
                error: function (jqXHR, textStatus, errorThrown) {
                    const placeholderLi = $('#wp-admin-bar-wcd-status-placeholder');
                    console.error('WCD Admin Bar JS: Load Status AJAX Request Failed:', textStatus, errorThrown, jqXHR.responseText);
                    // Replace placeholder LI's content with error message
                    if (placeholderLi.length) {
                        placeholderLi.html(`<div class="ab-item ab-empty-item">${wcdAdminBarData.error_text}</div>`);
                    }
                }
            });
        });

        // --- Existing Toggle Slider Event Listener ---
        // Uses event delegation, so it *should* work with dynamically added sliders.
        $('#wp-admin-bar-wcd-admin-bar').on('change', 'input.wcd-admin-bar-toggle', function (e) {
            e.preventDefault();

            var $checkbox = $(this);
            var type = $checkbox.data('type');
            var device = $checkbox.data('device');
            var urlId = $checkbox.data('url-id');
            var groupId = $checkbox.data('group-id');
            var isChecked = $checkbox.is(':checked');
            var currentUrl = $checkbox.data('url'); // Get URL from data attribute now


            if (!urlId || !groupId) {
                console.error('WCD Admin Bar Error: Missing URL ID or Group ID for toggle AJAX call.', { urlId: urlId, groupId: groupId });
                $checkbox.prop('checked', !isChecked); // Revert change
                alert(wcdAdminBarData.error_missing_data);
                return;
            }
            if (!wcdAdminBarData || !wcdAdminBarData.nonce || !wcdAdminBarData.ajax_url) {
                console.error('WCD Admin Bar Error: Missing nonce or AJAX URL for toggle.');
                $checkbox.prop('checked', !isChecked); // Revert change
                alert(wcdAdminBarData.error_config_missing);
                return;
            }


            // Prepare AJAX data - Needs to match what ajax_post_url expects
            // Based on get_url_settings code, it uses dynamic keys like 'desktop-<url_id>'
            var ajaxData = {
                action: 'post_url', // Action for the WordPress AJAX handler
                nonce: wcdAdminBarData.postUrlNonce, // Use the dedicated nonce for post_url action
                group_id: groupId,
            };
            // Only send the toggled device for the correct group
            ajaxData[device + '-' + urlId] = isChecked ? 1 : 0;

            // Disable checkbox temporarily
            $checkbox.prop('disabled', true);
            // Find and disable sibling checkbox if applicable
            var $siblingCheckbox = $checkbox.closest('.wcd-admin-bar-subitem')
                .siblings('.wcd-slider-node')
                .find('input.wcd-admin-bar-toggle');
            if ($siblingCheckbox.length) {
                $siblingCheckbox.prop('disabled', true);
            }


            // Perform AJAX request
            $.ajax({
                url: wcdAdminBarData.ajax_url,
                type: 'POST',
                data: ajaxData,
                success: function (response) {
                    // Check response? Assume okay for now.
                },
                error: function (jqXHR, textStatus, errorThrown) {
                    console.error('WCD Admin Bar Toggle AJAX Error:', textStatus, errorThrown, jqXHR.responseText);
                    // Revert checkbox state visually ONLY on explicit error
                    $checkbox.prop('checked', !isChecked);
                    alert(wcdAdminBarData.failed_update_setting);
                },
                complete: function () {
                    // Re-enable checkbox(es) after request completes
                    $checkbox.prop('disabled', false);
                    if ($siblingCheckbox.length) {
                        $siblingCheckbox.prop('disabled', false);
                    }
                }
            });
        });

    }); // End document ready

})(jQuery); 