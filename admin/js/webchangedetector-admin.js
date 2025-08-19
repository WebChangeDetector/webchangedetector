const MM_BG_COLOR_DARK_GREEN = '#006400';

function updateProcessingStep() {
    (function ($) {
        //currentlyProcessingSpinner.show();
        var currentlyProcessingTable = $('#currently-processing-table');
        var currentlyProcessingSpinner = $('#currently-processing-spinner');
        var currentlyProcessing = $('#currently-processing');

        var data = {
            action: 'get_processing_queue',
            nonce: wcdAjaxData.nonce
        };

        $.post(wcdAjaxData.ajax_url, data, function (response) {

            response = JSON.parse(response);

            // Calculate all

            let currentlyInQueueAmount = response.meta.total ?? 0;
            let currentlyProcessingSc = [];
            let actuallyProcessingSc = [];

            currentlyProcessing.html(currentlyInQueueAmount);

            // Get the processing queues in the plugin
            $('.processing_sc_row').each(function () {
                currentlyProcessingSc.push($(this).data('id'));
            });

            // Get the actually processing queues
            $(response.data).each(function (i) {
                if ($(this)[0].status === 'processing') {
                    actuallyProcessingSc.push($(this)[0].id)
                }
            });

            $("#processing_sc_row_empty").hide();
            if ($(actuallyProcessingSc).length === 0) {
                $("#processing_sc_row_empty").show();
            }

            // Hide done queues
            $('.processing_sc_row').each(function () {
                const row = $(this);
                if ($.inArray($(this).data('id'), actuallyProcessingSc) === -1) {
                    $(this).css("background", "#d5e4d5");
                    setTimeout(function () {
                        $(row).fadeOut(1000, function () {
                            $(row).remove();
                        });
                    }, 2000);
                }
            });

            // Add new queues
            $(response.data).each(function () {
                if ($(this)[0].status !== 'open' && -1 === $.inArray($(this)[0].id, currentlyProcessingSc)) {
                    const tbody = $(currentlyProcessingTable).find('tbody');
                    const item = $('<tr class="processing_sc_row" data-id="' + $(this)[0].id + '"><td><strong>' + $(this)[0].html_title + '</strong><br>Screensize: ' + $(this)[0].device + ' <br>URL: ' + $(this)[0].url_link + '</td></tr>')
                    setTimeout(function () {
                        $(tbody).append(item);
                        $(item).hide().fadeIn(1000);
                    }, 1000);
                }
            });

            // If the queue is done, show all done for 10 sec
            if (parseInt(currentlyInQueueAmount) === 0 || !response) {
                currentlyProcessingSpinner.hide(); // hide spinner

                // Replace message when everything is done
                $("#wcd-currently-in-progress").hide();
                $("#wcd-screenshots-done").show();
                // Stop the interval when everything is done.
                //clearInterval(processingInterval);
            }
        });
    })(jQuery);
}
function currentlyProcessing() {
    (function ($) {
        var currentlyProcessing = $('#currently-processing');
        let processingInterval;

        // Only show currently processing if there is something to process and check every 10 sec then
        if (currentlyProcessing && parseInt(currentlyProcessing.html()) > 0) {
            let totalSc = parseInt(currentlyProcessing.html());
            updateProcessingStep()
            processingInterval = setInterval(function () {
                updateProcessingStep(currentlyProcessing);
            }, 5000, currentlyProcessing)
        } else {
            $("#wcd-screenshots-done").show();
            if ($(processingInterval).length) {
                clearInterval(processingInterval);
            }
        }
    })(jQuery)
}

(function ($) {
    'use strict';

    /**
     * All of the code for your admin-facing JavaScript source
     * should reside in this file.
     *
     * Note: It has been assumed you will write jQuery code here, so the
     * $ function reference has been prepared for usage within the scope
     * of this function.
     *
     * This enables you to define handlers, for when the DOM is ready:
     *
     * $(function() {
     *
     * });
     *
     * When the window is loaded:
     *
     * $( window ).load(function() {
     *
     * });
     *
     * ...and/or other possibilities.
     *
     * Ideally, it is not considered best practise to attach more than a
     * single DOM-ready or window-load handler for a particular page.
     * Although scripts in the WordPress core, Plugins and Themes may be
     * practising this, we should strive to set a better example in our own work.
     */

    function getLocalDateTime(date) {
        let options = {
            year: 'numeric',
            month: '2-digit',
            day: '2-digit',
            hour: '2-digit',
            minute: '2-digit',
        };
        return new Date(date * 1000).toLocaleString(navigator.language, options);
    }

    function getLocalDate(date) {
        let options = {
            year: 'numeric',
            month: '2-digit',
            day: '2-digit',
        };
        return new Date(date * 1000).toLocaleString(navigator.language, options);
    }

    function getDifferenceBgColor(percent) {
        // early return if no difference in percent
        if (parseFloat(percent) === 0.0) {
            // Dark green
            return MM_BG_COLOR_DARK_GREEN;
        }
        var pct = 1 - (percent / 100);

        var percentColors = [
            // #8C0000 - dark red
            { pct: 0.0, color: { r: 0x8c, g: 0x00, b: 0 } },
            // #E5A025 - orange
            { pct: 1.0, color: { r: 0xe5, g: 0xa0, b: 0x25 } }
        ];

        for (var i = 1; i < percentColors.length - 1; i++) {
            if (pct < percentColors[i].pct) {
                break;
            }
        }
        var lower = percentColors[i - 1];
        var upper = percentColors[i];
        var range = upper.pct - lower.pct;
        var rangePct = (pct - lower.pct) / range;
        var pctLower = 1 - rangePct;
        var pctUpper = rangePct;
        var color = {
            r: Math.floor(lower.color.r * pctLower + upper.color.r * pctUpper),
            g: Math.floor(lower.color.g * pctLower + upper.color.g * pctUpper),
            b: Math.floor(lower.color.b * pctLower + upper.color.b * pctUpper)
        };

        return 'rgb(' + [color.r, color.g, color.b].join(',') + ')';
    }

    $(document).ready(function () {

        // Filter URL tables
        let filterTables = $(".group_urls_container");
        $.each(filterTables, function (i, e) {
            $(e).find(".filter-url-table").on("keyup", function () {
                var value = $(this).val().toLowerCase();
                $(e).find('table tr.live-filter-row').filter(function () {
                    $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1)
                });
            });
        });



        $(".codearea").each(function (index, item) {
            // Skip CSS textareas that are inside closed accordions - they will be initialized when accordion opens
            if ($(item).hasClass('wcd-css-textarea')) {
                var accordionContent = $(item).closest('.accordion-content');
                if (accordionContent.length && accordionContent.is(':hidden')) {
                    return; // Skip this textarea, it will be initialized when accordion opens
                }
            }
            wp.codeEditor.initialize(item);
        });

        // Init accordions
        $(".accordion").each(function (index, item) {
            $(item).accordion({
                heightStyle: "content",
                header: "h3",
                collapsible: true,
                active: false,
                animate: 200,
                icons: {
                    "header": "dashicons dashicons-plus",
                    "activeHeader": "dashicons dashicons-minus"
                },
            });
        });

        // Confirm message on leaving without saving form
        let formModified = 0;
        $('form.wcd-frm-settings').change(function () {
            formModified = 1;
        });
        window.onbeforeunload = confirmExit;

        function confirmExit() {
            if (formModified === 1) {
                return "Changes were not save. Do you wish to leave the page without saving?";
            }
        }

        $("button[type='submit']").click(function () {
            formModified = 0;
        });

        // Confirm deleting account
        $('#delete-account').submit(function () {
            return confirm("Are you sure you want to reset your account? This cannot be undone.");
        });

        // Confirm copy url settings
        $("#copy-url-settings").submit(function () {
            let type = $("#copy-url-settings").data("to_group_type");
            return confirm("Are you sure you want to overwrite the " + type + " detection settings? This cannot be undone.");
        });

        // Confirm taking pre screenshots
        $('#frm-take-pre-sc').submit(function () {
            return true;
        });

        // Confirm taking post screenshots
        $('#frm-take-post-sc').submit(function () {
            return true;
        });

        // Confirm cancel manual checks
        $('#frm-cancel-update-detection').submit(function () {
            return confirm(wcdL10n.confirmCancelChecks);
        });

        // Change bg color of comparison percentages
        var diffTile = $(".comparison-diff-tile");
        var bgColor = getDifferenceBgColor(diffTile.data("diff_percent"));
        diffTile.css("background", bgColor);

        // Background color differences
        $(".diff-tile").each(function () {
            var diffPercent = $(this).data("diff_percent");
            if (diffPercent > 0) {
                var bgColor = getDifferenceBgColor($(this).data("diff_percent"));
                $(this).css("background", bgColor);
            }
        });

        $("#diff-container").twentytwenty();

        $("#diff-container .comp-img").load(function () {
            $("#diff-container").twentytwenty();
        });

        $(".selected-urls").each(function (index, item) {
            var postType = $(item).data("post_type");
            var selectedDesktop = ($(item).data("amount_selected_desktop"));
            var selectedMobile = ($(item).data("amount_selected_mobile"));
            $("#selected-desktop-" + postType).html(selectedDesktop);
            $("#selected-mobile-" + postType).html(selectedMobile);
        });

        // Show local time in dropdowns
        var localDate = new Date();
        var timeDiff = localDate.getTimezoneOffset() / 60;

        $(".select-time").each(function (i, e) {
            let utcHour = parseInt($(this).val());
            let newDate = localDate.setHours(utcHour - timeDiff, 0);
            let localHour = new Date(newDate);
            let options = {
                hour: '2-digit',
                minute: '2-digit'
            };
            $(this).html(localHour.toLocaleString(navigator.language, options));
        });

        

        // Set time until next screenshots
        let autoEnabled = false;
        if ($("#auto-enabled").is(':checked') || $('input[name="enabled"]').is(':checked')) {
            autoEnabled = true;
        }
        let txtNextScIn = wcdL10n.noTrackingsActive;
        let nextScIn;
        let nextScDate = $("#next_sc_date").data("date");
        let amountSelectedTotal = $("#sc_available_until_renew").data("amount_selected_urls");

        $("#txt_next_sc_in").html(wcdL10n.currently);
        $("#next_sc_date").html("");

        if (nextScDate && autoEnabled && amountSelectedTotal > 0) {
            let now = new Date($.now()); // summer/winter - time
            nextScIn = new Date(nextScDate * 1000); // format time
            nextScIn = new Date(nextScIn - now); // normal time
            nextScIn.setHours(nextScIn.getHours() + (nextScIn.getTimezoneOffset() / 60)); // add timezone offset to normal time
            var minutes = nextScIn.getMinutes() == 1 ? " " + wcdL10n.minute + " " : " " + wcdL10n.minutes + " ";
            var hours = nextScIn.getHours() == 1 ? " " + wcdL10n.hour + " " : " " + wcdL10n.hours + " ";
            txtNextScIn = nextScIn.getHours() + hours + nextScIn.getMinutes() + minutes;
            $("#next_sc_date").html(getLocalDateTime(nextScDate));
            $("#txt_next_sc_in").html(wcdL10n.nextMonitoringChecks);
        }
        $("#next_sc_in").html(txtNextScIn);

        var scUsage = $("#wcd_account_details").data("sc_usage");
        var scLimit = $("#wcd_account_details").data("sc_limit");
        var availableCredits = scLimit - scUsage;
        var scPerUrlUntilRenew = $("#sc_available_until_renew").data("auto_sc_per_url_until_renewal");

        if (availableCredits <= 0) {
            $("#next_sc_in").html("Not Tracking").css("color", "#A00000");
            $("#next_sc_date").html("<span style='color: #a00000'>You ran out of screenshots.</span><br>");
        }

        // Calculate total auto sc until renewal
        amountSelectedTotal += amountSelectedTotal * scPerUrlUntilRenew;

        // Update total credits on top of page
        $("#ajax_amount_total_sc").html("0");
        if (amountSelectedTotal && autoEnabled) {
            $("#ajax_amount_total_sc").html(amountSelectedTotal);
        }

        if (amountSelectedTotal > availableCredits) {
            $("#sc_until_renew").addClass("exceeding");
            $("#sc_available_until_renew").addClass("exceeding");
        }

        /**********
         * AJAX
         *********/

        // This needs to instantly be executed
        currentlyProcessing();

        // Function to get current filters from the filter form
        function getCurrentFilters() {
            const filterForm = $('#form-filter-change-detections');
            if (filterForm.length === 0) {
                return null;
            }

            const formData = {};
            filterForm.find('input, select').each(function () {
                const name = $(this).attr('name');
                const value = $(this).val();
                if (name && value !== '' && name !== 'action' && name !== 'pagination') {
                    formData[name] = value;
                }
            });

            return Object.keys(formData).length > 0 ? formData : null;
        }

        // Function to initialize comparison status change buttons
        function initComparisonStatusButtons(container) {
            container.find(".ajax_update_comparison_status").off("click").on("click", function () {
                let e = $(this);
                let status = $(this).data('status');
                let statusElement = $(e).parent().parent().find(".current_comparison_status");
                var data = {
                    action: 'update_comparison_status',
                    nonce: $(this).data('nonce'),
                    id: $(this).data('id'),
                    status: status
                };

                // Replace content with loading img.
                let initialStatusContent = $(statusElement).html();
                $(statusElement).html("<img src='/wp-content/plugins/webchangedetector/admin/img/loader.gif' style='height: 12px; line-height: 12px;'>");

                $.post(wcdAjaxData.ajax_url, data, function (response) {
                    if ('failed' === response) {
                        $(statusElement).html(initialStatusContent);
                        alert(wcdL10n.somethingWentWrong);
                        return false;
                    }

                    let status_nice_name;
                    if ('ok' === response) {
                        status_nice_name = wcdL10n.statusOk;
                    } else if ('to_fix' === response) {
                        status_nice_name = wcdL10n.statusToFix;
                    } else if ('false_positive' === response) {
                        status_nice_name = wcdL10n.statusFalsePositive;
                    } else if ('failed' === response) {
                        status_nice_name = wcdL10n.statusFailed;
                    } else if ('new' === response) {
                        status_nice_name = wcdL10n.statusNew;
                    } else {
                        // Unexpected response - log it and show generic error
                        console.error('WebChangeDetector: Unexpected status response:', response);
                        $(statusElement).html(initialStatusContent);
                        alert(wcdL10n.unexpectedResponse);
                        return false;
                    }

                    $(e).parent().parent().find(".current_comparison_status").html(status_nice_name);
                    $(e).parent().parent().find(".current_comparison_status").removeClass("comparison_status_new");
                    $(e).parent().parent().find(".current_comparison_status").removeClass("comparison_status_ok");
                    $(e).parent().parent().find(".current_comparison_status").removeClass("comparison_status_to_fix");
                    $(e).parent().parent().find(".current_comparison_status").removeClass("comparison_status_false_positive");
                    $(e).parent().parent().find(".current_comparison_status").addClass("comparison_status_" + response);
                });
            });
        }

        // Function to re-initialize all components after AJAX content loads
        function reinitializeAfterAjax(container) {
            // Re-initialize accordion widgets for new content
            container.find(".accordion").each(function () {
                // Destroy existing accordion if it exists
                if ($(this).hasClass('ui-accordion')) {
                    $(this).accordion("destroy");
                }
                // Initialize accordion
                $(this).accordion({
                    heightStyle: "content",
                    header: "h3",
                    collapsible: true,
                    active: false, // Don't auto-open on load
                    animate: 200
                });
            });

            // Re-apply background colors for difference tiles
            container.find(".diff-tile").each(function () {
                var diffPercent = $(this).data("diff_percent");
                if (diffPercent > 0) {
                    var bgColor = getDifferenceBgColor($(this).data("diff_percent"));
                    $(this).css("background", bgColor);
                }
            });

            // Re-initialize comparison row click handlers
            container.find(".comparison_row").off("click").on("click", function () {
                const token = $(this).data("token");
                const currentKey = $(this).index();
                const maxKey = $(this).closest("tbody").find(".comparison_row").length;

                if (token) {
                    // Use the global function if available
                    if (typeof ajaxShowChangeDetectionPopup === 'function') {
                        ajaxShowChangeDetectionPopup(token, currentKey, maxKey);
                    }
                }
            });

            // Re-initialize comparison status change buttons
            initComparisonStatusButtons(container);

            // Re-initialize any other event handlers that might be needed
            container.find(".ajax_paginate_batch_comparisons").off("click").on("click", function () {
                const batchContainer = $(this).closest(".accordion-container");
                const batchId = batchContainer.data("batch_id");
                const page = $(this).data("page");
                const filters = $(this).data("filters");
                loadBatchComparisons($(this), batchId, page, filters, true);
            });
        }

        // Load batch comparisons content and handle pagination
        function loadBatchComparisons(element, batchId, page = 1, filters = null, shouldScroll = false) {
            const batchContainer = $(".accordion-container[data-batch_id='" + batchId + "']");
            const contentContainer = batchContainer.find(".ajax_batch_comparisons_content");
            const failedCount = batchContainer.data("failed_count");
            const consoleChangesCount = batchContainer.data("console_changes_count") || 0;

            // If filters are not provided, get them from the current filter form
            if (filters === null) {
                filters = getCurrentFilters();
            }

            const args = {
                action: 'get_batch_comparisons_view',
                batch_id: batchId,
                page: page,
                filters: filters,
                failed_count: failedCount,
                console_changes_count: consoleChangesCount,
                nonce: wcdAjaxData.nonce
            }

            // Only scroll for pagination, not initial load
            if (shouldScroll) {
                $([document.documentElement, document.body]).animate({
                    scrollTop: batchContainer.offset().top
                }, 500);
            }

            $.post(wcdAjaxData.ajax_url, args, function (response) {
                contentContainer.html(response);

                // Re-initialize all components for the new content
                reinitializeAfterAjax(contentContainer);

                initBatchComparisonsPagination();
            });
        }

        // Initialize batch comparisons loading and pagination
        function initBatchComparisonsPagination() {
            // Handle pagination clicks
            $(".ajax_paginate_batch_comparisons").off("click").on("click", function () {
                const batchContainer = $(this).closest(".accordion-container");
                const batchId = batchContainer.data("batch_id");
                const page = $(this).data("page");
                const filters = $(this).data("filters");

                loadBatchComparisons($(this), batchId, page, filters, true);
            });

            // Handle initial accordion loading using jQuery UI accordion activate event
            $(".accordion-container .accordion").off("accordionactivate.batchLoad").on("accordionactivate.batchLoad", function (event, ui) {
                if (ui.newHeader.length > 0) {
                    const batchContainer = ui.newHeader.closest(".accordion-container");
                    const batchId = batchContainer.data("batch_id");
                    const contentContainer = batchContainer.find(".ajax_batch_comparisons_content");
                    const currentContent = contentContainer.html().trim();

                    // Only load if content is empty or contains only loading placeholder (initial load)
                    if (contentContainer.is(':empty') ||
                        currentContent === '' ||
                        contentContainer.find('.ajax-loading-container').length > 0) {
                        loadBatchComparisons(ui.newHeader, batchId, 1, null, false);
                    }
                }
            });
        }

        // Toggle failed queues accordion and load content via AJAX.
        window.toggleFailedQueues = function (clickedElement, batchId) {
            // Find the specific elements within this accordion
            const accordionTitle = clickedElement; // The h3 element
            const content = accordionTitle.parentElement.querySelector('.failed-queues-content');
            const arrow = accordionTitle.querySelector('.accordion-arrow');
            const tableContainer = accordionTitle.parentElement.querySelector('.failed-queues-table-container');
            const loading = accordionTitle.parentElement.querySelector('.failed-queues-loading');

            const $content = $(content);

            if (!$content.is(':visible')) {
                // Show accordion with slide down animation
                $content.slideDown(300, function () {
                    // Animation complete
                });
                // Rotate arrow 90 degrees to match parent accordion behavior
                arrow.classList.remove('dashicons-arrow-right-alt2');
                arrow.classList.add('dashicons-arrow-down-alt2');

                // Check if content is already loaded
                if (tableContainer.innerHTML === '') {
                    // Show loading
                    loading.style.display = 'block';

                    // Load content via AJAX
                    $.ajax({
                        url: wcdAjaxData.ajax_url,
                        type: 'POST',
                        data: {
                            action: 'load_failed_queues',
                            batch_id: batchId,
                            nonce: wcdAjaxData.nonce
                        },
                        success: function (response) {
                            loading.style.display = 'none';
                            tableContainer.innerHTML = response;
                        },
                        error: function () {
                            loading.style.display = 'none';
                            tableContainer.innerHTML = '<div style="padding: 20px; text-align: center; color: #666;">Error loading failed URLs.</div>';
                        }
                    });
                }
            } else {
                // Hide accordion with slide up animation
                $content.slideUp(300);
                // Reset arrow to pointing right
                arrow.classList.remove('dashicons-arrow-down-alt2');
                arrow.classList.add('dashicons-arrow-right-alt2');
            }
        }

        // Initialize comparison status buttons for initial page load
        initComparisonStatusButtons($(document));

        initBatchComparisonsPagination();

        // Load dashboard usage statistics asynchronously
        loadDashboardUsageStats();
    });

    // Function to load dashboard usage statistics via AJAX
    function loadDashboardUsageStats() {
        // Only load if we're on the dashboard page and the elements exist
        if ($('#wcd-monitoring-stats, #wcd-auto-update-stats').length === 0) {
            return;
        }

        $.ajax({
            url: wcdAjaxData.ajax_url,
            type: 'POST',
            data: {
                action: 'get_dashboard_usage_stats',
                nonce: wcdAjaxData.nonce
            },
            success: function (response) {
                
                if (response.success && response.data) {
                    const data = response.data.data;

                    // Update monitoring stats
                    const monitoringElement = $('#wcd-monitoring-stats');
                    if (monitoringElement.length > 0) {
                        if (data.amount_auto_detection > 0) {
                            monitoringElement.html('<strong>Monitoring: </strong><span style="color: green; font-weight: 900;">On</span> (≈ ' + data.amount_auto_detection + ' checks / month)');
                        } else {
                            monitoringElement.html('<strong>Monitoring: </strong><span style="color: red; font-weight: 900">Off</span>');
                        }
                    }

                    // Update auto-update stats
                    const autoUpdateElement = $('#wcd-auto-update-stats');
                    if (autoUpdateElement.length > 0) {
                        if (data.max_auto_update_checks > 0 && data.auto_update_settings.auto_update_checks_enabled) {
                            autoUpdateElement.html('<strong>Auto update checks: </strong><span style="color: green; font-weight: 900;">On</span> (≈ ' + data.max_auto_update_checks + ' checks / month)');
                        } else {
                            autoUpdateElement.html('<strong>Auto update checks: </strong><span style="color: red; font-weight: 900">Off</span>');
                        }
                    }

                    // Update usage warning
                    const warningElement = $('#wcd-usage-warning');
                    if (warningElement.length > 0 && data.checks_needed > data.checks_available) {
                        const shortfall = Math.round(data.checks_needed - data.checks_available);
                        let warningHtml = '<span class="notice notice-warning" style="display:block; padding: 10px;">' +
                            '<span class="dashicons dashicons-warning"></span>' +
                            '<strong>You might run out of checks before renewal day. </strong><br>' +
                            'Current settings require up to ' + shortfall + ' more checks. <br>';

                        // Add upgrade link if not a subaccount (we'll assume it's available)
                        // Note: We can't access PHP variables here, so this would need to be passed differently
                        // For now, we'll include it and it will only show if the upgrade URL is available
                        warningHtml += '</span>';
                        warningElement.html(warningHtml);
                    }
                }
            },
            error: function () {
                // Show error state
                $('#wcd-monitoring-stats').html('<strong>Monitoring: </strong><span style="color: #666;">Error loading stats</span>');
                $('#wcd-auto-update-stats').html('<strong>Auto update checks: </strong><span style="color: #666;">Error loading stats</span>');
            }
        });
    }
})(jQuery);

// CSV Export functionality for logs page.
(function($) {
    'use strict';

    $(document).ready(function() {
        // Handle CSV export button click.
        $('#wcd-export-logs-btn').on('click', function(e) {
            e.preventDefault();
            
            const $button = $(this);
            const originalText = $button.text();
            const filters = $button.data('filters') || {};
            const nonce = wcdAjaxData.nonce;
            
            // Show loading state.
            $button.text('Exporting...').prop('disabled', true);
            
            // Prepare data for AJAX request.
            const ajaxData = {
                action: 'wcd_export_logs',
                nonce: nonce
            };
            
            // Add filters to the request.
            Object.keys(filters).forEach(function(key) {
                if (filters[key]) {
                    ajaxData[key] = filters[key];
                }
            });
            
            // Send AJAX request.
            $.ajax({
                url: wcdAjaxData.ajax_url,
                type: 'POST',
                data: ajaxData,
                success: function(response) {

                    if (response.success && response.data && response.data.data.csv_content) {
                        // Create and trigger download.
                        downloadCSV(response.data.data.csv_content, response.data.data.filename);
                    } else {                        
                        alert('Failed to export logs: ' + (response.data && response.data.message ? response.data.message : 'Invalid response structure'));
                    }
                },
                error: function() {
                    alert('Error occurred while exporting logs. Please try again.');
                },
                complete: function() {
                    // Restore button state.
                    $button.text(originalText).prop('disabled', false);
                }
            });
        });
        
        /**
         * Download CSV file from base64 content.
         * 
         * @param {string} csvContent Base64 encoded CSV content.
         * @param {string} filename Filename for the download.
         */
        function downloadCSV(csvContent, filename) {
            try {
                if (!csvContent || !filename) {
                    throw new Error('Missing CSV content or filename');
                }
                
                // Decode base64 content.
                let csvData;
                try {
                    csvData = atob(csvContent);
                } catch (decodeError) {
                    throw new Error('Failed to decode CSV content');
                }
                
                // Create blob and download link.
                const blob = new Blob([csvData], { type: 'text/csv;charset=utf-8;' });
                
                const link = document.createElement('a');
                
                // Use modern browser download method
                if (window.navigator && window.navigator.msSaveOrOpenBlob) {
                    // IE10+ specific method
                    window.navigator.msSaveOrOpenBlob(blob, filename);
                } else if (link.download !== undefined) {
                    // Modern browsers with download attribute support
                    const url = URL.createObjectURL(blob);
                    link.href = url;
                    link.download = filename;
                    link.style.display = 'none';
                    document.body.appendChild(link);
                    
                    link.click();
                    
                    // Clean up after a short delay
                    setTimeout(() => {
                        try {
                            document.body.removeChild(link);
                            URL.revokeObjectURL(url);
                        } catch (cleanupError) {
                            // Ignore.
                        }
                    }, 1000);
                } else {
                    // Fallback for older browsers
                    const url = URL.createObjectURL(blob);
                    const newWindow = window.open(url, '_blank');
                    if (!newWindow) {
                        // Try alternative method
                        const dataUrl = 'data:text/csv;charset=utf-8,' + encodeURIComponent(csvData);
                        const link2 = document.createElement('a');
                        link2.href = dataUrl;
                        link2.download = filename;
                        link2.click();
                    }
                    setTimeout(() => URL.revokeObjectURL(url), 1000);
                }
            } catch (error) {
                alert('Error downloading CSV file: ' + error.message);
            }
        }
    });
})(jQuery);

// We got jpeg images and png. So we load jpeg for faster page load.
// If jpeg is not available, we load pngs. To not be stuck in the onerror-loop, we do this.
function loadFallbackImg(img, fallbackSrc) {
    if (!img.dataset.fallbackAttempted) {
        // Mark that we've tried the fallback already.
        img.dataset.fallbackAttempted = "true";
        // Set the fallback image source.
        img.src = fallbackSrc;
    } else {
        // Fallback already attempted—stop further error handling.
        img.onerror = null;
        // TODO Add a placeholder img
        // img.src = 'path/to/placeholder.jpg';
    }
}

function sync_urls(force = 0) {
    var data = {
        action: 'sync_urls',
        nonce: jQuery('#ajax_sync_urls_status').data('nonce'),
        force: force
    };

    // Loading icon to show we are checking if we have to sync.
    jQuery('#ajax_sync_urls_status').append(" <img style='width: 10px' src='" + wcdAjaxData.plugin_url + "img/loader.gif'>");

    // Show the button as disabled.
    jQuery('.button-sync-urls').prop('disabled', true);

    jQuery.post(wcdAjaxData.ajax_url, data, function (response) {
        // We get the last sync date as response.
        jQuery('#ajax_sync_urls_status').html(response);
        jQuery('.button-sync-urls').prop('disabled', false);
        return response;
    });
}

function postUrl(postId) {
    let groupId = document.getElementsByName('group_id')[0]
    let data;
    if (postId.startsWith('select')) {
        const selectAllCheckbox = jQuery('#' + postId);
        //const type = selectAllCheckbox.data('type');
        const screensize = selectAllCheckbox.data('screensize');

        data = {
            action: 'post_url',
            nonce: jQuery(selectAllCheckbox).data('nonce'),
            group_id: groupId.value,
        }

        let posts = jQuery("td.checkbox-" + screensize + " input[type='checkbox']");

        jQuery(posts).each(function () {
            data = { ...data, [screensize + "-" + jQuery(this).data('url_id')]: this.checked ? 1 : 0 };
        });

    } else {
        let desktop = document.getElementById("desktop-" + postId);
        let mobile = document.getElementById("mobile-" + postId);

        data = {
            action: 'post_url',
            nonce: jQuery(desktop).data('nonce'),
            group_id: groupId.value,
            ['desktop-' + postId]: desktop.checked ? 1 : 0,
            ['mobile-' + postId]: mobile.checked ? 1 : 0,
        }
    }

    jQuery.post(wcdAjaxData.ajax_url, data, function (response) {
        // TODO confirm saving.
    });
}

/**
 * Marks rows as green or red, depending on if a checkbox is checked
 *
 * @param {int} postId
 */
function mmMarkRows(postId) {
    var desktop = document.getElementById("desktop-" + postId);
    var mobile = document.getElementById("mobile-" + postId);
    var row = document.getElementById(postId);

    if (desktop.checked == true || mobile.checked == true) {
        // green
        row.style.background = "#17b33147";
        return;
    }
    // red
    row.style.background = "#dc323247";
}

/**
 * Checks checkboxes for select-all checkbox
 * Called from `onclick=` in HTML
 * Calls mmMarkRows
 */
function mmToggle(source, column, groupId) {
    var checkboxes = document.querySelectorAll('.checkbox-' + column + ' input[type=\"checkbox\"]');
    for (var i = 0; i < checkboxes.length; i++) {
        if (checkboxes[i] != source) {
            checkboxes[i].checked = source.checked;
        }
    }

    var rows = document.querySelectorAll('.post_id_' + groupId);
    for (var i = 0; i < rows.length; i++) {
        var id = rows[i].id;
        mmMarkRows(id);
    }
}

/**
* Validates comma separated emails in a form
* Called `onsubmit=` in HTML
*/
/**
 * Legacy validation function removed - now handled by modern component-based system
 * This function was using legacy element selectors that don't match the modern component structure
 */

/**
 * Legacy validation function removed - now handled by modern component-based system
 * This function was conflicting with the modern wcdValidateFormAutoSettings() in templates
 */

function validateEmail(emails) {
    // init email regex
    var emailRegex = /^(([^<>()[\]\\.,;:\s@\"]+(\.[^<>()[\]\\.,;:\s@\"]+)*)|(\".+\"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/;

    for (var i = 0; i < emails.length; i++) {
        emails[i] = emails[i].trim();

        // Validation failed
        if (emails[i] === "" || !emailRegex.test(emails[i])) {
            return false;
        }
    }
    return true;
}

function showUpdates() {
    jQuery("#updates").toggle("slow");
}

/**
 * Start manual checks by advancing to the pre-screenshot step
 * @param {string} groupId - The group ID for manual checks
 */
function startManualChecks(groupId) {
    // Create a form and submit it to start manual checks
    var form = document.createElement('form');
    form.method = 'POST';
    form.action = '/wp-admin/admin.php?page=webchangedetector-update-settings';

    // Add the action to advance to next step
    var actionInput = document.createElement('input');
    actionInput.type = 'hidden';
    actionInput.name = 'wcd_action';
    actionInput.value = 'start_manual_checks';
    form.appendChild(actionInput);

    // Add the step parameter to advance to pre-screenshot step
    var stepInput = document.createElement('input');
    stepInput.type = 'hidden';
    stepInput.name = 'step';
    stepInput.value = 'pre-update';
    form.appendChild(stepInput);

    // Add nonce for security - WordPress expects a nonce field that matches the action name
    var nonceInput = document.createElement('input');
    nonceInput.type = 'hidden';
    nonceInput.name = '_wpnonce';
    // Use the correct nonce for the start_manual_checks action
    nonceInput.value = wcdAjaxData.start_manual_checks_nonce;
    form.appendChild(nonceInput);

    // Add form to body and submit
    document.body.appendChild(form);
    form.submit();
}

