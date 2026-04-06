const MM_BG_COLOR_DARK_GREEN = '#006400';

// Global state for time estimation
window.wcdTotalItems = null;
window.wcdEstimatedRemaining = null;
window.wcdRemainingUpdatedAt = null;
window.wcdProcessingDone = false;
window.wcdFinishingSoon = false;

/**
 * Initialize processing polling on page load.
 * Supports phase-aware manual checks (pre/post screenshots).
 */
function currentlyProcessing() {
    (function($) {
        var updateProcessingContainer = $('#wcd-currently-in-progress');
        var updateCurrentlyProcessing = $('#update-currently-processing');
        var currentlyProcessing = $('#currently-processing');
        var processingInterval = null;
        var timeInterval = null;

        var batchId = updateProcessingContainer.data('batch_id') || false;

        if (!batchId) {
            // No batch ID: show done state if it exists
            $('#wcd-screenshots-done').show();
            return;
        }

        // Disable cancel button while processing
        $('#frm-cancel-update-detection .cancel_button, .wcd-cancel-button').prop('disabled', true);

        /**
         * Format milliseconds to human-readable time string.
         */
        function formatTime(ms) {
            var totalSeconds = Math.floor(ms / 1000);
            if (totalSeconds < 60) return totalSeconds + 's';
            var minutes = Math.floor(totalSeconds / 60);
            var seconds = totalSeconds % 60;
            if (minutes < 60) return minutes + 'm ' + seconds + 's';
            var hours = Math.floor(minutes / 60);
            minutes = minutes % 60;
            return hours + 'h ' + minutes + 'm';
        }

        /**
         * Start 1-second interval for elapsed time and remaining countdown.
         */
        function startTimeInterval() {
            if (timeInterval) return; // Already running
            var startedAt = updateProcessingContainer.data('started_at');
            if (!startedAt) return;

            timeInterval = setInterval(function() {
                var elapsed = Date.now() - (startedAt * 1000);
                $('#wcd-elapsed-time').text(formatTime(elapsed));

                if (window.wcdProcessingDone) {
                    $('#wcd-estimated-remaining').text(wcdL10n.done || 'Done');
                    clearInterval(timeInterval);
                    return;
                }

                if (window.wcdEstimatedRemaining !== null && window.wcdRemainingUpdatedAt !== null) {
                    var timeSinceUpdate = Date.now() - window.wcdRemainingUpdatedAt;
                    var remaining = window.wcdEstimatedRemaining - timeSinceUpdate;
                    if (remaining <= 0) {
                        window.wcdFinishingSoon = true;
                        $('#wcd-estimated-remaining').text(wcdL10n.finishingSoon || 'Finishing soon...');
                    } else {
                        $('#wcd-estimated-remaining').text(formatTime(remaining));
                    }
                }
            }, 1000);
        }

        /**
         * Main polling function: fetch batch processing status.
         */
        function updateDetectionRefresh() {
            if (!batchId) return;

            var data = {
                action: 'get_batch_processing_status',
                nonce: wcdAjaxData.nonce,
                batch_id: batchId
            };

            $.post(wcdAjaxData.ajax_url, data, function(statusData) {
                console.log('WCD Status Update:', statusData);

                // Get phase from template data attribute
                var phase = updateProcessingContainer.data('phase'); // 'pre' or 'post'
                console.log('Current phase:', phase);

                var openCount = statusData.open || 0;
                var processingCount = statusData.processing || 0;
                var doneCount = statusData.done || 0;
                var failedCount = statusData.failed || 0;

                // Phase-specific counting logic
                if (phase === 'post') {
                    $('#processing-title').text(wcdL10n.checksInProgress || 'Checks in progress');

                    if (statusData.by_type && statusData.by_type.post) {
                        var postStatus = statusData.by_type.post;
                        var compareStatus = statusData.by_type.compare || {open: 0, processing: 0, done: 0, failed: 0};

                        var totalPostScreenshots = (postStatus.open || 0) + (postStatus.processing || 0) + (postStatus.done || 0) + (postStatus.failed || 0);

                        doneCount = compareStatus.done || 0;
                        failedCount = (postStatus.failed || 0) + (compareStatus.failed || 0);
                        openCount = postStatus.open || 0;
                        processingCount = Math.max(0, totalPostScreenshots - doneCount - failedCount - openCount);
                    }
                } else if (phase === 'pre') {
                    $('#processing-title').text(wcdL10n.screenshotsInProgress || 'Screenshots in progress');

                    if (statusData.by_type && statusData.by_type.pre) {
                        var preStatus = statusData.by_type.pre;
                        openCount = preStatus.open || 0;
                        processingCount = preStatus.processing || 0;
                        doneCount = preStatus.done || 0;
                        failedCount = preStatus.failed || 0;
                    }
                } else {
                    $('#processing-title').text(wcdL10n.screenshotsInProgress || 'Screenshots in progress');
                }

                var openAndProcessing = openCount + processingCount;
                var processedItems = doneCount + failedCount;
                var totalItems = openAndProcessing + processedItems;

                // Cache total on first valid response
                if (totalItems > 0 && window.wcdTotalItems === null) {
                    window.wcdTotalItems = totalItems;
                }
                if (window.wcdTotalItems !== null) {
                    totalItems = window.wcdTotalItems;
                    openAndProcessing = totalItems - processedItems;
                }

                // Update main counter
                updateCurrentlyProcessing.html(openAndProcessing);

                // Update detailed status counts
                $('#queue-open-count').text(openCount);
                $('#queue-processing-count').text(processingCount);
                $('#queue-done-count').text(doneCount);
                $('#queue-failed-count').text(failedCount);

                // Update progress bar
                if (totalItems > 0) {
                    var progressPercent = (processedItems / totalItems) * 100;
                    $('#wcd-progress-bar-fill').css('width', progressPercent + '%');
                    $('#wcd-progress-text').text(processedItems + ' / ' + totalItems);
                } else {
                    $('#wcd-progress-bar-fill').css('width', '0%');
                    $('#wcd-progress-text').text('0 / 0');
                }

                // Time estimation
                var startedAt = updateProcessingContainer.data('started_at');
                if (startedAt && totalItems > 0 && !window.wcdFinishingSoon) {
                    var elapsed = Date.now() - (startedAt * 1000);

                    if (processedItems >= totalItems) {
                        window.wcdProcessingDone = true;
                    } else if (doneCount > 0) {
                        var timePerItem = elapsed / doneCount;
                        window.wcdEstimatedRemaining = timePerItem * (totalItems - processedItems);
                        window.wcdRemainingUpdatedAt = Date.now();
                    } else {
                        var estimatedTotal;
                        if (phase === 'post') {
                            estimatedTotal = ((totalItems - 1) * 5 + 45 + 5) * 1000;
                        } else {
                            estimatedTotal = ((totalItems - 1) * 5 + 45) * 1000;
                        }
                        window.wcdEstimatedRemaining = Math.max(0, estimatedTotal - elapsed);
                        window.wcdRemainingUpdatedAt = Date.now();
                    }
                }

                if (startedAt && totalItems > 0) {
                    startTimeInterval();
                }

                // Show/hide failed count
                var $failedItem = $('.status-item.failed-status');
                if (failedCount > 0) {
                    $failedItem.show();
                    $('#queue-failed-count').addClass('has-failures');
                    $failedItem.addClass('has-failures');
                    if (!$failedItem.data('click-bound')) {
                        $failedItem.on('click', function() {
                            showFailedQueues(batchId);
                        });
                        $failedItem.data('click-bound', true);
                    }
                } else {
                    $failedItem.hide();
                    $('#queue-failed-count').removeClass('has-failures');
                    $failedItem.removeClass('has-failures');
                }

                // Pre-Screenshot URL Tracking
                if (phase === 'pre') {
                    if (typeof window.wcdLastPreDone === 'undefined') {
                        window.wcdLastPreDone = 0;
                    }
                    if (doneCount > window.wcdLastPreDone) {
                        fetchCompletedPreScreenshots(batchId);
                        window.wcdLastPreDone = doneCount;
                    }
                }

                // Change Detections Polling (post phase only)
                if (phase === 'post') {
                    if (typeof window.wcdLastComparisonDone === 'undefined') {
                        window.wcdLastComparisonDone = 0;
                    }

                    if (statusData.by_type && statusData.by_type.compare) {
                        var currentComparisonDone = statusData.by_type.compare.done || 0;
                        if (currentComparisonDone > window.wcdLastComparisonDone) {
                            fetchNewChangeDetections(batchId);
                            window.wcdLastComparisonDone = currentComparisonDone;
                        }
                    }
                }

                // Use actual API values as authoritative completion signal.
                // Derived counts can mismatch when post screenshots and comparisons
                // have different failure counts (e.g., post succeeds but comparison is never created).
                var apiOpen = statusData.open || 0;
                var apiProcessing = statusData.processing || 0;
                var apiAllDone = (apiOpen === 0 && apiProcessing === 0 && (statusData.done + statusData.failed) > 0);

                if (apiAllDone && openAndProcessing > 0) {
                    console.log('WCD: API reports all done, correcting derived counts', {
                        derivedOpenAndProcessing: openAndProcessing,
                        apiOpen: apiOpen,
                        apiProcessing: apiProcessing
                    });
                    openAndProcessing = 0;
                    processedItems = doneCount + failedCount;
                    totalItems = processedItems;

                    // Correct the UI to reflect actual state
                    updateCurrentlyProcessing.html(0);
                    $('#queue-open-count').text(0);
                    $('#queue-processing-count').text(0);
                    $('#wcd-progress-bar-fill').css('width', '100%');
                    $('#wcd-progress-text').text(processedItems + ' / ' + processedItems);
                }

                // Processing complete check
                if (openAndProcessing === 0 && processedItems > 0) {
                    updateCurrentlyProcessing.html(wcdL10n.done || 'Done');
                    $('#currently-processing-loader .spinner').removeClass('is-active');
                    $('#update-currently-processing-description').html('<strong>' + (wcdL10n.finishedProcessing || 'Finished processing') + '</strong>');

                    // Re-enable cancel button
                    $('#frm-cancel-update-detection .cancel_button, .wcd-cancel-button').prop('disabled', false);

                    // Enable navigation buttons for both phases
                    $('#change-detection-actions .button, #change-detection-actions .et_pb_button').prop('disabled', false);
                    $('#pre-sc-navigation-actions .button').prop('disabled', false);
                    $('#pre-sc-navigation-actions').show();

                    // Stop polling
                    clearInterval(processingInterval);
                    window.wcdTotalItems = null;
                    if (timeInterval) {
                        clearInterval(timeInterval);
                        window.wcdProcessingDone = true;
                        $('#wcd-estimated-remaining').text(wcdL10n.done || 'Done');
                    } else {
                        $('.wcd-time-info').hide();
                    }
                }

                // Handle empty batch: all counts are 0
                var totalItems = openAndProcessing + processedItems;
                if (totalItems === 0) {
                    updateCurrentlyProcessing.html('0');
                    $('#currently-processing-loader .spinner').removeClass('is-active');
                    $('#update-currently-processing-description').html('<strong>No items to process</strong>');

                    $('#frm-cancel-update-detection .cancel_button, .wcd-cancel-button').prop('disabled', false);
                    $('#change-detection-actions .button, #change-detection-actions .et_pb_button').prop('disabled', false);
                    $('#pre-sc-navigation-actions .button').prop('disabled', false);
                    $('#pre-sc-navigation-actions').show();

                    clearInterval(processingInterval);
                    window.wcdTotalItems = null;
                    if (timeInterval) {
                        clearInterval(timeInterval);
                    }
                    $('.wcd-time-info').hide();
                }
            }, 'json');
        }

        // Start polling
        updateDetectionRefresh();
        processingInterval = setInterval(function() {
            updateDetectionRefresh();
        }, 10000);

        /**
         * Fetch new change detections for live display (post phase).
         */
        function fetchNewChangeDetections(batchId) {
            if (!batchId) return;

            var showAll = $('.wcd-comparison-filter').is(':checked');

            $.post(wcdAjaxData.ajax_url, {
                action: 'get_new_change_detections',
                nonce: wcdAjaxData.nonce,
                batch_id: batchId,
                above_threshold: !showAll ? 1 : 0
            }, function(data) {
                // Update count
                var countText = data.total_count + ' ' + (data.total_count !== 1 ? (wcdL10n.detections || 'detections') : (wcdL10n.detection || 'detection'));
                countText += ' (' + (showAll ? (wcdL10n.showingAll || 'showing all') : (wcdL10n.withChangesOnly || 'with changes only')) + ')';
                $('#detections-count').text(countText);

                if (data.comparisons && data.comparisons.length > 0) {
                    var processedComparisons = data.comparisons.filter(function(comp) {
                        return comp.status && comp.status !== '';
                    });

                    if (processedComparisons.length > 0) {
                        renderChangeDetectionsTable(processedComparisons);
                    }
                }
            }, 'json').fail(function(xhr, status, error) {
                console.error('Failed to fetch change detections:', error);
            });
        }

        /**
         * Fetch completed pre-screenshots for live URL list.
         */
        function fetchCompletedPreScreenshots(batchId) {
            if (!batchId) return;

            $.post(wcdAjaxData.ajax_url, {
                action: 'get_completed_pre_screenshots',
                nonce: wcdAjaxData.nonce,
                batch_id: batchId
            }, function(data) {
                if (!data.queues || data.queues.length === 0) return;

                $('#pre-sc-completed-count').text('(' + data.total_done + ')');
                $('#pre-sc-empty-state').remove();

                if (!$('#pre-sc-completed-table table').length) {
                    var tableHtml = '<table class="widefat striped">' +
                        '<thead><tr><th>' + 'URL' + '</th></tr></thead>' +
                        '<tbody id="pre-sc-completed-tbody"></tbody>' +
                        '</table>';
                    $('#pre-sc-completed-table').html(tableHtml);
                }

                // Add new rows (prepend, newest first)
                data.queues.slice().reverse().forEach(function(queue) {
                    if (!$('#pre-sc-row-' + queue.id).length) {
                        var deviceIcon = queue.device === 'mobile'
                            ? '<span class="dashicons dashicons-smartphone"></span>'
                            : '<span class="dashicons dashicons-desktop"></span>';

                        var rowStyle = 'background-color: rgba(50, 220, 50, 0.1);';
                        if (queue.image_link) {
                            rowStyle += ' cursor: pointer;';
                        }

                        var row = '<tr id="pre-sc-row-' + queue.id + '" style="' + rowStyle + '"' +
                            (queue.image_link ? ' data-image-link="' + queue.image_link.replace(/"/g, '&quot;') + '"' : '') +
                            ' data-html-title="' + (queue.html_title || '').replace(/"/g, '&quot;') + '"' +
                            ' data-url-link="' + (queue.url_link || '').replace(/"/g, '&quot;') + '"' +
                            '>' +
                            '<td>' +
                            '<div class="wcd-slide-in" style="display:none;">' +
                            (queue.html_title ? '<strong>' + queue.html_title + '</strong><br>' : '') +
                            deviceIcon + ' ' + queue.url_link +
                            '</div>' +
                            '</td>' +
                            '</tr>';

                        var $row = $(row);
                        $('#pre-sc-completed-tbody').prepend($row);
                        $row.find('.wcd-slide-in').slideDown(300);
                    }
                });
            }, 'json').fail(function(xhr, status, error) {
                console.error('Failed to fetch completed pre-screenshots:', error);
            });
        }

        /**
         * Show pre-screenshot in popup on row click.
         */
        $(document).on('click', '#pre-sc-completed-tbody tr[data-image-link]', function() {
            var imageLink = $(this).data('image-link');
            var htmlTitle = $(this).data('html-title') || '';
            var urlLink = $(this).data('url-link') || '';
            if (imageLink) {
                var fullUrl = urlLink.match(/^https?:\/\//) ? urlLink : 'https://' + urlLink;
                $('#pre-sc-popup-title').text(htmlTitle);
                $('#pre-sc-popup-url').attr('href', fullUrl).text(urlLink);
                $('#pre-sc-popup-image').hide();
                $('#pre-sc-popup-spinner').show();
                $('#pre-sc-popup-image').off('load').on('load', function() {
                    $('#pre-sc-popup-spinner').hide();
                    $(this).show();
                }).attr('src', imageLink);
                $('#pre-sc-screenshot-popup').show();
            }
        });

        /**
         * Show failed queue items in popup.
         */
        function showFailedQueues(batchId) {
            $('#failed-queues-content').html('<p style="text-align: center; color: #666;">Loading...</p>');
            $('#failed-queues-popup').show();

            $.post(wcdAjaxData.ajax_url, {
                action: 'get_failed_queues_json',
                nonce: wcdAjaxData.nonce,
                batch_id: batchId
            }, function(data) {
                if (!data.queues || data.queues.length === 0) {
                    $('#failed-queues-content').html('<p style="text-align: center; color: #666;">No failed items found.</p>');
                    return;
                }

                var html = '<table class="widefat striped" style="margin: 0; width: 100%;">' +
                    '<thead><tr><th>URL</th></tr></thead><tbody>';

                data.queues.forEach(function(q) {
                    var deviceIcon = q.device === 'mobile'
                        ? '<span class="dashicons dashicons-smartphone"></span>'
                        : '<span class="dashicons dashicons-desktop"></span>';
                    var title = q.html_title ? '<strong>' + $('<span>').text(q.html_title).html() + '</strong><br>' : '';
                    var urlText = $('<span>').text(q.url_link).html();
                    var errorMsg = $('<span>').text(q.error_msg).html();

                    html += '<tr style="background-color: rgba(220, 50, 50, 0.1);">' +
                        '<td>' + title + deviceIcon + ' ' + urlText +
                        '<br><span style="color: #dc2626; font-size: 13px; margin-top: 5px; display: inline-block;">Warning: ' + errorMsg + '</span>' +
                        '</td></tr>';
                });

                html += '</tbody></table>';
                $('#failed-queues-content').html(html);
            }, 'json').fail(function() {
                $('#failed-queues-content').html('<p style="text-align: center; color: #dc2626;">Failed to load error details.</p>');
            });
        }

        /**
         * Render change detections table.
         */
        function renderChangeDetectionsTable(comparisons) {
            $('#empty-state').remove();

            if (!$('#change-detections-table table').length) {
                var tableHtml = '<table class="wcd-comparison-table toggle" style="width: 100%">' +
                    '<tr>' +
                    '<th style="min-width: 140px; text-align: center;">Status</th>' +
                    '<th style="width: 100%">URL</th>' +
                    '<th style="min-width: 50px">Difference</th>' +
                    '</tr>' +
                    '<tbody id="change-detections-tbody"></tbody>' +
                    '</table>';
                $('#change-detections-table').html(tableHtml);
            }

            comparisons.slice().reverse().forEach(function(comp) {
                if (!$('#comparison-row-' + comp.id).length) {
                    prependComparisonRow(comp);
                }
            });
        }

        /**
         * Format difference percentage.
         */
        function formatDifferencePercent(percent) {
            if (percent > 0 && percent < 0.005) {
                return '< 0.01';
            }
            return percent.toFixed(2);
        }

        /**
         * Prepend single comparison row to table.
         */
        function prependComparisonRow(comparison) {
            if (!comparison.status || comparison.status === '') return;

            var changePercent = parseFloat(comparison.difference_percent || 0);
            var threshold = parseFloat(comparison.threshold || 0) * 100;
            var hasChanges = changePercent > threshold;

            var statusLabel = comparison.status.replace('_', ' ');
            statusLabel = statusLabel.charAt(0).toUpperCase() + statusLabel.slice(1);

            var deviceIcon = comparison.device === 'mobile'
                ? '<span class="dashicons dashicons-smartphone"></span>'
                : '<span class="dashicons dashicons-desktop"></span>';

            var diffClass = hasChanges ? 'is-difference' : 'no-difference';

            var detectionUrl = '';
            if (wcdAjaxData.show_detection_url && wcdAjaxData.show_detection_nonce && comparison.id) {
                detectionUrl = wcdAjaxData.show_detection_url +
                    '&id=' + encodeURIComponent(comparison.id) +
                    '&_wpnonce=' + encodeURIComponent(wcdAjaxData.show_detection_nonce);
            }

            var row = '<tr id="comparison-row-' + comparison.id + '" class="comparison_row"' +
                ' data-token="' + (comparison.id || '') + '"' +
                (detectionUrl ? ' data-detection-url="' + detectionUrl.replace(/"/g, '&quot;') + '"' : '') +
                '>';

            // Status column
            row += '<td><div class="wcd-slide-in" style="display:none;">' +
                '<span class="comparison_status comparison_status_' + comparison.status + '">' + statusLabel + '</span>' +
                '</div></td>';

            // URL column
            row += '<td><div class="wcd-slide-in" style="display:none;">';
            if (comparison.html_title) {
                row += '<strong>' + comparison.html_title + '</strong><br>';
            }
            row += deviceIcon + ' ' + (comparison.url || 'N/A');
            row += '</div></td>';

            // Difference column
            row += '<td class="' + diffClass + ' diff-tile"><div class="wcd-slide-in" style="display:none;">' +
                formatDifferencePercent(changePercent) + '%' +
                '</div></td>';

            row += '</tr>';

            var $row = $(row);
            $('#change-detections-tbody').prepend($row);
            $row.find('.wcd-slide-in').slideDown(300);
        }

        /**
         * Handle filter toggle for change detections.
         */
        $(document).off('change', '.wcd-comparison-filter').on('change', '.wcd-comparison-filter', function() {
            if (batchId) {
                fetchNewChangeDetections(batchId);
            }
        });

        /**
         * Handle click on comparison rows to navigate to change detection view.
         */
        $(document).on('click', '#change-detections-tbody .comparison_row[data-detection-url]', function() {
            var url = $(this).data('detection-url');
            if (url) {
                window.location.href = url;
            }
        });

        /**
         * Close popups on ESC key.
         */
        $(document).on('keydown', function(e) {
            if (e.key === 'Escape') {
                $('.wcd-popup-overlay').hide();
            }
        });

    })(jQuery);
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
        var wpOffsetMs = (wcdL10n.wpUtcOffsetSeconds || 0) * 1000;
        var wpDate = new Date((date * 1000) + wpOffsetMs);
        let options = {
            year: 'numeric',
            month: '2-digit',
            day: '2-digit',
            hour: '2-digit',
            minute: '2-digit',
            timeZone: 'UTC'
        };
        return wpDate.toLocaleString(navigator.language, options);
    }

    function getLocalDate(date) {
        var wpOffsetMs = (wcdL10n.wpUtcOffsetSeconds || 0) * 1000;
        var wpDate = new Date((date * 1000) + wpOffsetMs);
        let options = {
            year: 'numeric',
            month: '2-digit',
            day: '2-digit',
            timeZone: 'UTC'
        };
        return wpDate.toLocaleString(navigator.language, options);
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
                return wcdL10n.unsavedChanges || "Changes were not saved. Do you wish to leave the page without saving?";
            }
        }

        $("button[type='submit']").click(function () {
            formModified = 0;
        });

        // Confirm deleting account
        $('#delete-account').submit(function () {
            return confirm(wcdL10n.confirmResetAccount || "Are you sure you want to reset your account? This cannot be undone.");
        });

        // Confirm copy url settings
        $("#copy-url-settings").submit(function () {
            let type = $("#copy-url-settings").data("to_group_type");
            var msg = wcdL10n.confirmOverwriteSettings ? wcdL10n.confirmOverwriteSettings.replace('%s', type) : "Are you sure you want to overwrite the " + type + " detection settings? This cannot be undone.";
            return confirm(msg);
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

        // Show WordPress timezone time in dropdowns.
        $(".select-time").not("select[name='hour_of_day'] .select-time").each(function (i, e) {
            let utcHour = parseInt($(this).val());
            $(this).html(utcHourToWpString(utcHour));
        });

        // Show WordPress timezone name with UTC offset.
        var wpTimezone = wcdL10n.wpTimezone || "UTC";
        var wpUtcLabel = wcdL10n.wpUtcLabel || "UTC+0";
        var wpTzDisplay = /^[+-]/.test(wpTimezone) ? wpUtcLabel : wpTimezone + " " + wpUtcLabel;
        $(".local-timezone").text("Timezone: " + wpTzDisplay);

        // Format all .wcd-local-date elements with WP timezone date/time.
        $(".wcd-local-date").each(function () {
            var ts = $(this).data("date");
            if (ts) {
                $(this).text(getLocalDateTime(ts) + " (" + wpTzDisplay + ")");
            }
        });

        // Convert a UTC hour (0-23) to a WP timezone time string like "14:00".
        function utcHourToWpString(utcHour) {
            var wpOffsetMs = (wcdL10n.wpUtcOffsetSeconds || 0) * 1000;
            var d = new Date(Date.UTC(2000, 0, 1, utcHour, 0, 0, 0) + wpOffsetMs);
            return d.toLocaleString(navigator.language, { hour: '2-digit', minute: '2-digit', timeZone: 'UTC' });
        }

        // Update hour_of_day dropdown based on the selected interval.
        // Shows combined time slots (e.g. "8:00, 20:00" for 12h interval).
        function updateHourOfDayDropdown() {
            var intervalSelect = $("select[name='interval_in_h']");
            var hourSelect = $("select[name='hour_of_day']");
            var hourRow = $(".wcd-monitoring-hour-of-day");

            if (!intervalSelect.length || !hourSelect.length) {
                return;
            }

            var interval = parseFloat(intervalSelect.val());
            var monitoringEnabled = $("input[name='enabled']").is(":checked");

            // For intervals <= 1h, show the interval text instead of the dropdown.
            if (interval <= 1) {
                hourSelect.hide();
                var intervalLabel = intervalSelect.find("option:selected").text().trim();
                if (!hourRow.find(".wcd-interval-label").length) {
                    hourSelect.after('<strong class="wcd-interval-label"></strong>');
                }
                hourRow.find(".wcd-interval-label").text(intervalLabel).show();
                if (monitoringEnabled) {
                    hourRow.show();
                }
                return;
            }

            // Only show the hour row if monitoring is enabled.
            if (monitoringEnabled) {
                hourRow.show();
            }

            // Remove interval label and show dropdown for intervals > 1h.
            hourRow.find(".wcd-interval-label").hide();
            hourSelect.show();

            var currentVal = parseInt(hourSelect.val()) || 0;
            var intInterval = Math.round(interval);
            var numOptions = intInterval;

            // Map existing hour value to valid dropdown value via modulo.
            var mappedValue = currentVal % intInterval;

            hourSelect.empty();

            for (var i = 0; i < numOptions; i++) {
                var times = [];
                for (var h = i; h < 24; h += intInterval) {
                    times.push(utcHourToWpString(h));
                }
                var label = times.join(", ");
                var option = $("<option>").val(i).text(label);
                if (i === mappedValue) {
                    option.prop("selected", true);
                }
                hourSelect.append(option);
            }
        }

        // Initialize hour_of_day dropdown on page load.
        updateHourOfDayDropdown();

        // Update hour_of_day dropdown when interval changes.
        $(document).on("change", "select[name='interval_in_h']", function () {
            updateHourOfDayDropdown();
        });

        // Re-evaluate hour dropdown when monitoring toggle changes.
        $(document).on("change", "input[name='enabled']", function () {
            setTimeout(updateHourOfDayDropdown, 50);
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
            var diffMs = (nextScDate * 1000) - Date.now();
            var totalMinutes = Math.max(0, Math.floor(diffMs / 60000));
            var d = Math.floor(totalMinutes / 1440);
            var h = Math.floor((totalMinutes % 1440) / 60);
            var m = totalMinutes % 60;

            var dayLabel = d === 1 ? " " + wcdL10n.day + " " : " " + wcdL10n.days + " ";
            var hourLabel = h === 1 ? " " + wcdL10n.hour + " " : " " + wcdL10n.hours + " ";
            var minuteLabel = m === 1 ? " " + wcdL10n.minute + " " : " " + wcdL10n.minutes + " ";

            txtNextScIn = "";
            if (d > 0) {
                txtNextScIn += d + dayLabel;
            }
            txtNextScIn += h + hourLabel + m + minuteLabel;

            $("#next_sc_date").html(getLocalDateTime(nextScDate) + " (" + wpTzDisplay + ")");
            $("#txt_next_sc_in").html(wcdL10n.nextMonitoringChecks);
        }
        $("#next_sc_in").html(txtNextScIn);

        var scUsage = $("#wcd_account_details").data("sc_usage");
        var scLimit = $("#wcd_account_details").data("sc_limit");
        var availableCredits = scLimit - scUsage;
        var scPerUrlUntilRenew = $("#sc_available_until_renew").data("auto_sc_per_url_until_renewal");

        if (availableCredits <= 0) {
            $("#next_sc_in").html(wcdL10n.notTracking || "Not Tracking").css("color", "#A00000");
            $("#next_sc_date").html("<span style='color: #a00000'>" + (wcdL10n.ranOutScreenshots || "You ran out of screenshots.") + "</span><br>");
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

        // Initialize processing polling (delayed to let DOM fully render)
        if ($('#wcd-currently-in-progress').length) {
            setTimeout(function() {
                currentlyProcessing();
            }, 1000);
        }

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
            $button.text(wcdL10n.exporting || 'Exporting...').prop('disabled', true);
            
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
                        alert((wcdL10n.exportFailed || 'Failed to export logs') + ': ' + (response.data && response.data.message ? response.data.message : ''));
                    }
                },
                error: function() {
                    alert(wcdL10n.exportError || 'Error occurred while exporting logs. Please try again.');
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

    wcdUpdateManualChecksUI();

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
 * Updates the manual checks card, warning notice, and selected URLs counter
 * based on the current state of checkboxes on the page.
 *
 * Uses row-level counting: a URL counts as "selected" if at least one
 * device (desktop or mobile) checkbox is checked for that URL row.
 * Accounts for pagination by adjusting the server-provided initial count.
 */
function wcdUpdateManualChecksUI() {
    var card = jQuery('.wcd-manual-checks-card');
    if (!card.length) {
        return;
    }

    var initialServerCount = parseInt(card.data('initial-count')) || 0;

    // Count selected rows on page at load time (captured once, stored on card).
    if (typeof card.data('initial-page-rows') === 'undefined') {
        var initialPageRows = 0;
        jQuery('.live-filter-row').each(function () {
            var rowId = jQuery(this).attr('id');
            var desktopInitial = jQuery('#desktop-' + rowId).prop('defaultChecked');
            var mobileInitial = jQuery('#mobile-' + rowId).prop('defaultChecked');
            if (desktopInitial || mobileInitial) {
                initialPageRows++;
            }
        });
        card.data('initial-page-rows', initialPageRows);
    }

    // Count currently selected rows on page.
    var currentPageRows = 0;
    jQuery('.live-filter-row').each(function () {
        var rowId = jQuery(this).attr('id');
        var desktopChecked = jQuery('#desktop-' + rowId).is(':checked');
        var mobileChecked = jQuery('#mobile-' + rowId).is(':checked');
        if (desktopChecked || mobileChecked) {
            currentPageRows++;
        }
    });

    var currentTotal = initialServerCount - card.data('initial-page-rows') + currentPageRows;
    if (currentTotal < 0) {
        currentTotal = 0;
    }
    var hasUrls = currentTotal > 0;

    // Update warning notice.
    jQuery('#wcd-no-urls-notice').toggle(!hasUrls);

    // Update card CSS class.
    card.toggleClass('wcd-status-no-urls', !hasUrls)
        .toggleClass('wcd-status-ready', hasUrls);

    // Update card icon.
    card.find('.wcd-mc-icon')
        .toggleClass('dashicons-info', !hasUrls)
        .toggleClass('dashicons-controls-play', hasUrls);

    // Update labels and value.
    card.find('.wcd-mc-ready-label').toggle(hasUrls);
    card.find('.wcd-mc-no-urls-label').toggle(!hasUrls);
    card.find('.wcd-mc-no-urls-value').toggle(!hasUrls);

    // Update button and stats.
    card.find('.wcd-mc-start-btn').toggle(hasUrls);
    card.find('.wcd-mc-stats').toggle(hasUrls);

    // Update count values.
    card.find('.wcd-mc-selected-count').text(currentTotal);
    jQuery('.wcd-selected-urls-total').text(currentTotal);
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

/**
 * Advanced Screenshot Settings: Password toggle, Copy IP, Password action tracking.
 */
jQuery(document).ready(function($) {

    // Toggle password field visibility.
    $(document).on('click', '.wcd-toggle-password-btn', function() {
        var targetId = $(this).data('target');
        var input = document.getElementById(targetId);
        var icon = $(this).find('.dashicons');

        if (input.type === 'password') {
            input.type = 'text';
            icon.removeClass('dashicons-visibility').addClass('dashicons-hidden');
        } else {
            input.type = 'password';
            icon.removeClass('dashicons-hidden').addClass('dashicons-visibility');
        }
    });

    // Track password field changes for delete logic.
    $(document).on('input', '.wcd-password-field', function() {
        var $input = $(this);
        var actionField = document.getElementById('basic_auth_password_action');
        if (!actionField) {
            return;
        }

        var hasPassword = $input.data('has-password') === 1 || $input.data('has-password') === '1';
        var originalValue = $input.data('original-value');

        if (hasPassword && $input.val() === '') {
            actionField.value = 'delete';
        } else if ($input.val() !== originalValue) {
            actionField.value = '';
        }
    });

    // Copy proxy IP address to clipboard.
    $(document).on('click', '.wcd-copy-ip-btn', function() {
        var ip = $(this).data('ip');
        var $button = $(this);

        if (!navigator.clipboard) {
            var textarea = document.createElement('textarea');
            textarea.value = ip;
            textarea.style.position = 'fixed';
            textarea.style.opacity = '0';
            document.body.appendChild(textarea);
            textarea.select();
            try {
                document.execCommand('copy');
                wcdShowCopyFeedback($button);
            } catch (err) {
                // Silently fail.
            }
            document.body.removeChild(textarea);
            return;
        }

        navigator.clipboard.writeText(ip).then(function() {
            wcdShowCopyFeedback($button);
        });
    });

    // Show copy success feedback on the button.
    function wcdShowCopyFeedback($button) {
        var $icon = $button.find('.dashicons');
        $icon.removeClass('dashicons-admin-page').addClass('dashicons-yes');
        $button.css('color', '#10b981');

        setTimeout(function() {
            $icon.removeClass('dashicons-yes').addClass('dashicons-admin-page');
            $button.css('color', '#0073aa');
        }, 2000);
    }
});

/**
 * AI Change Analysis: view toggle, overlay positioning, feedback modal, rules page.
 *
 * @since 4.1.0
 */
(function ($) {
    'use strict';

    var DEVICE_SCALE_FACTOR = 2;

    /**
     * Swap the right-hand image in the TwentyTwenty slider.
     *
     * @param {string} mode 'diff' for Change Detection image, 'after' for the After screenshot.
     */
    function wcdSwapSliderImage(mode) {
        var $container = $('#diff-container');
        var $afterImg = $container.find('.twentytwenty-after');
        var $overlayLayer = $('#wcd_ai_overlay_layer');

        if (!$afterImg.length) {
            return;
        }

        if (mode === 'diff') {
            $afterImg.attr('src', $container.attr('data-diff-src'));
            $afterImg[0].onerror = function () {
                loadFallbackImg(this, $container.attr('data-diff-fallback'));
            };
            $overlayLayer.addClass('wcd-ai-overlays-visible');
            wcdPositionAiOverlays();
        } else {
            $afterImg.attr('src', $container.attr('data-after-src'));
            $afterImg[0].onerror = function () {
                loadFallbackImg(this, $container.attr('data-after-fallback'));
            };
            $overlayLayer.removeClass('wcd-ai-overlays-visible');
        }
    }

    /**
     * Create and position AI overlay boxes based on region bounding-box data.
     *
     * Reads data-ai-regions JSON from the overlay layer, scales coordinates from
     * the original 2x-resolution screenshot to the displayed image size, then
     * renders absolutely-positioned coloured boxes.
     */
    function wcdPositionAiOverlays() {
        var $container = $('#wcd_ai_overlay_layer');
        var regionsJson = $container.attr('data-ai-regions');

        if (!regionsJson) {
            return;
        }

        var regions;
        try {
            regions = JSON.parse(regionsJson);
        } catch (e) {
            return;
        }

        if (!regions || !regions.length) {
            return;
        }

        var $img = $('#diff-container img.comp-img');
        var imgElement = $img[0];
        if (!imgElement || !imgElement.naturalWidth) {
            return;
        }

        var displayWidth = imgElement.clientWidth;
        var displayHeight = imgElement.clientHeight;
        var naturalWidth = imgElement.naturalWidth;
        var naturalHeight = imgElement.naturalHeight;

        var scaleX = displayWidth / (naturalWidth * DEVICE_SCALE_FACTOR);
        var scaleY = displayHeight / (naturalHeight * DEVICE_SCALE_FACTOR);

        $container.find('.wcd-ai-overlay').remove();

        for (var i = 0; i < regions.length; i++) {
            var region = regions[i];
            var bbox = region.bbox;
            if (!bbox) {
                continue;
            }

            var regionId = region.id !== undefined ? region.id : i;
            var pad = 10;
            var left = Math.round(bbox.x * scaleX) - pad;
            var top = Math.round(bbox.y * scaleY) - pad;
            var width = Math.round(bbox.w * scaleX) + (pad * 2);
            var height = Math.round(bbox.h * scaleY) + (pad * 2);

            if (left < 0) { left = 0; }
            if (top < 0) { top = 0; }
            if (left + width > displayWidth) { width = displayWidth - left; }
            if (top + height > displayHeight) { height = displayHeight - top; }

            var category = 'not_sure';
            var $card = $('.wcd-ai-region-card[data-region-id="' + regionId + '"]');
            if ($card.hasClass('wcd-ai-category-all_good')) {
                category = 'all_good';
            } else if ($card.hasClass('wcd-ai-category-alert')) {
                category = 'alert';
            }

            var $overlay = $('<div>')
                .addClass('wcd-ai-overlay wcd-ai-overlay-' + category)
                .attr('data-region-id', regionId)
                .css({
                    left: left + 'px',
                    top: top + 'px',
                    width: width + 'px',
                    height: height + 'px'
                });

            var $badge = $('<span>')
                .addClass('wcd-ai-overlay-label')
                .text(i + 1);

            $overlay.append($badge);
            $container.append($overlay);
        }
    }

    /**
     * Initialise AI overlay interactions: create overlays, bind resize/hover/click.
     */
    function wcdInitAiOverlays() {
        var $container = $('#wcd_ai_overlay_layer');
        if (!$container.length || !$container.attr('data-ai-regions')) {
            return;
        }

        var $img = $('#diff-container img.comp-img');
        if ($img[0] && $img[0].naturalWidth) {
            wcdPositionAiOverlays();
        } else {
            $img.on('load', function () {
                wcdPositionAiOverlays();
            });
        }

        $(window).on('resize.wcdAiOverlays', function () {
            wcdPositionAiOverlays();
        });

        // Hover region card: highlight overlay.
        $(document).on('mouseenter.wcdAiOverlays', '.wcd-ai-region-card', function () {
            var regionId = $(this).data('region-id');
            $('.wcd-ai-overlay[data-region-id="' + regionId + '"]').addClass('wcd-ai-overlay-active');
        });
        $(document).on('mouseleave.wcdAiOverlays', '.wcd-ai-region-card', function () {
            var regionId = $(this).data('region-id');
            $('.wcd-ai-overlay[data-region-id="' + regionId + '"]').removeClass('wcd-ai-overlay-active');
        });

        // Click region card: switch to diff view, scroll to overlay, pulse.
        $(document).on('click.wcdAiOverlays', '.wcd-ai-region-card', function () {
            var regionId = $(this).data('region-id');
            var $overlay = $('.wcd-ai-overlay[data-region-id="' + regionId + '"]');
            if (!$overlay.length) {
                return;
            }

            if (!$('.wcd-view-diff').hasClass('active')) {
                $('.wcd-view-diff').trigger('click');
            }

            var headerOffset = 120;
            var overlayTop = $overlay.offset().top - headerOffset;
            $('html, body').animate({ scrollTop: overlayTop }, 400);

            $overlay.addClass('wcd-ai-overlay-pulse');
            setTimeout(function () {
                $overlay.removeClass('wcd-ai-overlay-pulse');
            }, 1500);
        });
    }

    $(document).ready(function () {

        // --- View Toggle ---

        $(document).on('click', '.wcd-view-diff', function () {
            if ($(this).hasClass('active')) {
                return;
            }
            $('.wcd-view-btn').removeClass('active');
            $(this).addClass('active');
            wcdSwapSliderImage('diff');
        });

        $(document).on('click', '.wcd-view-after', function () {
            if ($(this).hasClass('active')) {
                return;
            }
            $('.wcd-view-btn').removeClass('active');
            $(this).addClass('active');
            wcdSwapSliderImage('after');
        });

        // --- AI Overlays ---

        wcdInitAiOverlays();

        // --- AI Feedback Modal ---

        $(document).on('click', '.wcd-ai-feedback-btn', function (e) {
            e.stopPropagation();
            var comparisonId = $(this).data('comparison-id');
            var regionId = $(this).data('region-id');
            $('#wcd-ai-feedback-comparison-id').val(comparisonId);
            $('#wcd-ai-feedback-region-id').val(regionId);
            $("input[name='wcd_ai_feedback_scope'][value='url']").prop('checked', true);
            $('#wcd-ai-feedback-modal').fadeIn(200);
        });

        $(document).on('click', '.wcd-ai-feedback-cancel', function () {
            $('#wcd-ai-feedback-modal').fadeOut(200);
        });

        $(document).on('click', '#wcd-ai-feedback-modal', function (e) {
            if ($(e.target).is('#wcd-ai-feedback-modal')) {
                $('#wcd-ai-feedback-modal').fadeOut(200);
            }
        });

        $(document).on('click', '.wcd-ai-feedback-submit', function () {
            var $btn = $(this);
            var comparisonId = $('#wcd-ai-feedback-comparison-id').val();
            var regionId = $('#wcd-ai-feedback-region-id').val();
            var scope = $("input[name='wcd_ai_feedback_scope']:checked").val();

            $btn.prop('disabled', true).text(wcdL10n.creatingRule || 'Creating rule...');

            $.ajax({
                url: wcdAjaxData.ajax_url,
                type: 'POST',
                data: {
                    action: 'wcd_create_ai_feedback_rule',
                    nonce: wcdAjaxData.nonce,
                    comparison_id: comparisonId,
                    region_id: regionId,
                    scope: scope
                },
                success: function (response) {
                    if (response.success) {
                        $('#wcd-ai-feedback-modal').fadeOut(200);
                        var $originBtn = $('.wcd-ai-feedback-btn[data-comparison-id="' + comparisonId + '"][data-region-id="' + regionId + '"]');
                        $originBtn
                            .text(wcdL10n.ruleCreated || 'Rule created')
                            .prop('disabled', true)
                            .addClass('wcd-ai-feedback-btn-done');
                    } else {
                        alert(response.data && response.data.message ? response.data.message : (wcdL10n.failedCreateRule || 'Failed to create rule.'));
                    }
                },
                error: function () {
                    alert(wcdL10n.somethingWentWrong || 'Something went wrong. Please try again.');
                },
                complete: function () {
                    $btn.prop('disabled', false).text(wcdL10n.confirm || 'Confirm');
                }
            });
        });

        // --- Console Ignore Modal ---

        $(document).on('click', '.wcd-console-ignore-btn', function (e) {
            e.stopPropagation();
            var comparisonId = $(this).data('comparison-id');
            var consoleEntry = $(this).data('console-entry');
            var consoleSource = $(this).data('console-source') || '';
            $('#wcd-console-feedback-comparison-id').val(comparisonId);
            $('#wcd-console-feedback-entry').val(consoleEntry);
            $('#wcd-console-feedback-source').val(consoleSource);
            $("input[name='wcd_console_feedback_scope'][value='url']").prop('checked', true);
            $('#wcd-console-feedback-modal').fadeIn(200);
        });

        $(document).on('click', '.wcd-console-feedback-cancel', function () {
            $('#wcd-console-feedback-modal').fadeOut(200);
        });

        $(document).on('click', '#wcd-console-feedback-modal', function (e) {
            if ($(e.target).is('#wcd-console-feedback-modal')) {
                $('#wcd-console-feedback-modal').fadeOut(200);
            }
        });

        $(document).on('click', '.wcd-console-feedback-submit', function () {
            var $btn = $(this);
            var comparisonId = $('#wcd-console-feedback-comparison-id').val();
            var consoleEntry = $('#wcd-console-feedback-entry').val();
            var consoleSource = $('#wcd-console-feedback-source').val();
            var scope = $("input[name='wcd_console_feedback_scope']:checked").val();

            // Strip query params from source URL
            if (consoleSource) {
                try { consoleSource = consoleSource.split('?')[0]; } catch (e) { /* keep as-is */ }
            }

            $btn.prop('disabled', true).text(wcdL10n.creatingRule || 'Creating rule...');

            $.ajax({
                url: wcdAjaxData.ajax_url,
                type: 'POST',
                data: {
                    action: 'wcd_create_ai_feedback_rule',
                    nonce: wcdAjaxData.nonce,
                    comparison_id: comparisonId,
                    type: 'console',
                    console_entry: consoleEntry,
                    console_source: consoleSource,
                    scope: scope
                },
                success: function (response) {
                    if (response.success) {
                        $('#wcd-console-feedback-modal').fadeOut(200);
                        var $originBtn = $('.wcd-console-ignore-btn[data-comparison-id="' + comparisonId + '"]').filter(function () {
                            return $(this).data('console-entry') === consoleEntry;
                        });
                        $originBtn
                            .text(wcdL10n.ruleCreated || 'Ignored')
                            .prop('disabled', true)
                            .addClass('ignored');
                    } else {
                        alert(response.data && response.data.message ? response.data.message : (wcdL10n.failedCreateRule || 'Failed to create rule.'));
                    }
                },
                error: function () {
                    alert(wcdL10n.somethingWentWrong || 'Something went wrong. Please try again.');
                },
                complete: function () {
                    $btn.prop('disabled', false).text(wcdL10n.confirm || 'Confirm');
                }
            });
        });

        // --- AI Rules Page ---

        // Toggle rule active/inactive.
        $(document).on('change', '.wcd-ai-rules-toggle-input', function () {
            var $input = $(this);
            var ruleId = $input.data('rule-id');
            var newActive = $input.is(':checked') ? 1 : 0;

            $input.prop('disabled', true);

            $.ajax({
                url: wcdAjaxData.ajax_url,
                type: 'POST',
                data: {
                    action: 'wcd_toggle_ai_feedback_rule',
                    nonce: wcdAjaxData.nonce,
                    rule_id: ruleId,
                    active: newActive
                },
                success: function (response) {
                    if (!response.success) {
                        $input.prop('checked', !newActive);
                        alert(response.data && response.data.message ? response.data.message : (wcdL10n.failedUpdateRule || 'Failed to update rule.'));
                    }
                },
                error: function () {
                    $input.prop('checked', !newActive);
                    alert(wcdL10n.somethingWentWrong || 'Something went wrong. Please try again.');
                },
                complete: function () {
                    $input.prop('disabled', false);
                }
            });
        });

        // Delete rule.
        $(document).on('click', '.wcd-ai-rules-delete-btn', function () {
            var $btn = $(this);
            var ruleId = $btn.data('rule-id');
            var $row = $btn.closest('.wcd-ai-rules-row');

            if (!confirm(wcdL10n.confirmDeleteRule || 'Are you sure you want to delete this rule? This cannot be undone.')) {
                return;
            }

            $btn.prop('disabled', true);
            $row.css('opacity', '0.5');

            $.ajax({
                url: wcdAjaxData.ajax_url,
                type: 'POST',
                data: {
                    action: 'wcd_delete_ai_feedback_rule',
                    nonce: wcdAjaxData.nonce,
                    rule_id: ruleId
                },
                success: function (response) {
                    if (response.success) {
                        $row.fadeOut(300, function () {
                            $(this).remove();
                            if ($('.wcd-ai-rules-row').length === 0) {
                                $('.wcd-ai-rules-table-wrap').replaceWith(
                                    '<div class="wcd-ai-rules-empty">' +
                                    '<span class="dashicons dashicons-shield"></span>' +
                                    '<h3>No rules yet</h3>' +
                                    '<p>Rules help the AI learn which changes are safe to ignore on future comparisons.</p>' +
                                    '</div>'
                                );
                            }
                        });
                    } else {
                        $row.css('opacity', '1');
                        alert(response.data && response.data.message ? response.data.message : (wcdL10n.failedDeleteRule || 'Failed to delete rule.'));
                    }
                },
                error: function () {
                    $row.css('opacity', '1');
                    alert(wcdL10n.somethingWentWrong || 'Something went wrong. Please try again.');
                },
                complete: function () {
                    $btn.prop('disabled', false);
                }
            });
        });

        // Change rule scope (segmented control).
        $(document).on('click', '.wcd-ai-rules-scope-option', function () {
            var $btn = $(this);
            var $toggle = $btn.closest('.wcd-ai-rules-scope-toggle');
            var ruleId = $toggle.data('rule-id');
            var newScope = $btn.data('scope');
            var previousScope = $toggle.data('current-scope');

            if (newScope === previousScope) {
                return;
            }

            $toggle.find('.wcd-ai-rules-scope-option').prop('disabled', true);

            $.ajax({
                url: wcdAjaxData.ajax_url,
                type: 'POST',
                data: {
                    action: 'wcd_update_ai_feedback_rule_scope',
                    nonce: wcdAjaxData.nonce,
                    rule_id: ruleId,
                    scope: newScope
                },
                success: function (response) {
                    if (response.success) {
                        $toggle.data('current-scope', newScope);
                        $toggle.find('.wcd-ai-rules-scope-option').removeClass('is-selected');
                        $btn.addClass('is-selected');
                    } else {
                        alert(response.data && response.data.message ? response.data.message : (wcdL10n.failedUpdateScope || 'Failed to update scope.'));
                    }
                },
                error: function () {
                    alert(wcdL10n.somethingWentWrong || 'Something went wrong. Please try again.');
                },
                complete: function () {
                    $toggle.find('.wcd-ai-rules-scope-option').prop('disabled', false);
                }
            });
        });
    });

    /**
     * Initialize Flatpickr inline date range picker.
     */
    $(document).ready(function () {
        var el = document.getElementById('wcd-daterange-inline');
        if (!el || typeof flatpickr === 'undefined') {
            return;
        }

        var fromVal = $('#wcd-date-from').val();
        var toVal = $('#wcd-date-to').val();
        var defaultDates = [];
        if (fromVal) {
            defaultDates.push(fromVal);
        }
        if (toVal) {
            defaultDates.push(toVal);
        }

        flatpickr(el, {
            mode: 'range',
            inline: true,
            appendTo: el,
            dateFormat: 'Y-m-d',
            defaultDate: defaultDates,
            maxDate: new Date(),
            onChange: function (selectedDates) {
                if (selectedDates.length === 2) {
                    $('#wcd-date-from').val(wcdFormatDateISO(selectedDates[0]));
                    $('#wcd-date-to').val(wcdFormatDateISO(selectedDates[1]));
                    wcdHighlightActiveDatePreset();
                }
            }
        });

        // Highlight active preset on load.
        wcdHighlightActiveDatePreset();
    });

    /**
     * Format a Date object as YYYY-MM-DD.
     */
    function wcdFormatDateISO(date) {
        var y = date.getFullYear();
        var m = String(date.getMonth() + 1).padStart(2, '0');
        var d = String(date.getDate()).padStart(2, '0');
        return y + '-' + m + '-' + d;
    }

    /**
     * Highlight the active date preset based on current from/to values.
     */
    function wcdHighlightActiveDatePreset() {
        var fromVal = $('#wcd-date-from').val();
        var toVal = $('#wcd-date-to').val();

        $('.wcd-date-preset').removeClass('active');

        if (!fromVal && !toVal) {
            $('.wcd-date-preset[data-days="all"]').addClass('active');
            return;
        }

        var today = new Date();
        today.setHours(0, 0, 0, 0);
        var toDate = new Date(toVal);
        toDate.setHours(0, 0, 0, 0);

        if (toDate.getTime() !== today.getTime()) {
            return;
        }

        var from = new Date(fromVal);
        var diffDays = Math.round((today - from) / (1000 * 60 * 60 * 24));
        var presets = [7, 30, 90];
        for (var i = 0; i < presets.length; i++) {
            if (Math.abs(diffDays - presets[i]) <= 1) {
                $('.wcd-date-preset[data-days="' + presets[i] + '"]').addClass('active');
                return;
            }
        }
    }

    /**
     * Date preset buttons for change detection filters.
     */
    $(document).on('click', '.wcd-date-preset', function () {
        var days = $(this).data('days');
        var fp = document.getElementById('wcd-daterange-inline');

        $('.wcd-date-preset').removeClass('active');
        $(this).addClass('active');

        if (days === 'all') {
            if (fp && fp._flatpickr) {
                fp._flatpickr.clear();
            }
            $('#wcd-date-from').val('');
            $('#wcd-date-to').val('');
        } else {
            var to = new Date();
            var from = new Date();
            from.setDate(from.getDate() - parseInt(days, 10));

            var fromStr = wcdFormatDateISO(from);
            var toStr = wcdFormatDateISO(to);

            $('#wcd-date-from').val(fromStr);
            $('#wcd-date-to').val(toStr);

            if (fp && fp._flatpickr) {
                fp._flatpickr.setDate([fromStr, toStr], true);
            }
        }
    });

    /**
     * Searchable Checkbox Dropdown Component.
     * Provides search filtering, tag display, and multi-select via checkboxes.
     */

    // Open dropdown on search input focus.
    $(document).on('focus', '.wcd-checkbox-dropdown-search', function () {
        var $dropdown = $(this).closest('.wcd-checkbox-dropdown');
        $('.wcd-checkbox-dropdown').not($dropdown).removeClass('open');
        $dropdown.addClass('open');
    });

    // Focus search input when clicking the trigger area.
    $(document).on('click', '.wcd-checkbox-dropdown-trigger', function (e) {
        if (!$(e.target).hasClass('dashicons') && !$(e.target).hasClass('wcd-tag-remove')) {
            $(this).find('.wcd-checkbox-dropdown-search').focus();
        }
    });

    // Toggle dropdown on arrow click.
    $(document).on('click', '.wcd-checkbox-dropdown-trigger .dashicons', function (e) {
        e.stopPropagation();
        var $dropdown = $(this).closest('.wcd-checkbox-dropdown');
        var wasOpen = $dropdown.hasClass('open');

        $('.wcd-checkbox-dropdown').removeClass('open');

        if (!wasOpen) {
            $dropdown.addClass('open');
            $dropdown.find('.wcd-checkbox-dropdown-search').focus();
        }
    });

    // Filter options on search input.
    $(document).on('input', '.wcd-checkbox-dropdown-search', function () {
        var query = $(this).val().trim().toLowerCase();
        var $dropdown = $(this).closest('.wcd-checkbox-dropdown');

        $dropdown.find('.wcd-checkbox-dropdown-option').each(function () {
            var label = $(this).find('span').text().trim().toLowerCase();
            $(this).toggleClass('wcd-hidden', query !== '' && label.indexOf(query) === -1);
        });
    });

    // Close dropdown when clicking outside.
    $(document).on('click', function (e) {
        if (!$(e.target).closest('.wcd-checkbox-dropdown').length) {
            $('.wcd-checkbox-dropdown').each(function () {
                $(this).removeClass('open');
                var $search = $(this).find('.wcd-checkbox-dropdown-search');
                $search.val('');
                $(this).find('.wcd-checkbox-dropdown-option').removeClass('wcd-hidden');
            });
        }
    });

    // Prevent dropdown list clicks from closing.
    $(document).on('click', '.wcd-checkbox-dropdown-list', function (e) {
        e.stopPropagation();
    });

    // Handle checkbox change: render tags and reset search.
    $(document).on('change', '.wcd-checkbox-dropdown-option input[type="checkbox"]', function () {
        var $dropdown = $(this).closest('.wcd-checkbox-dropdown');
        renderDropdownTags($dropdown);
        var $search = $dropdown.find('.wcd-checkbox-dropdown-search');
        $search.val('');
        $dropdown.find('.wcd-checkbox-dropdown-option').removeClass('wcd-hidden');
        $search.focus();
    });

    // Remove tag on x click.
    $(document).on('click', '.wcd-tag-remove', function (e) {
        e.stopPropagation();
        var $tag = $(this).closest('.wcd-tag');
        var value = $tag.data('value');
        var $dropdown = $tag.closest('.wcd-checkbox-dropdown');
        $dropdown.find('input[type="checkbox"][value="' + value + '"]').prop('checked', false);
        renderDropdownTags($dropdown);
    });

    // Escape key closes dropdown.
    $(document).on('keydown', '.wcd-checkbox-dropdown-search', function (e) {
        if (e.key === 'Escape') {
            var $dropdown = $(this).closest('.wcd-checkbox-dropdown');
            $dropdown.removeClass('open');
            $(this).val('');
            $dropdown.find('.wcd-checkbox-dropdown-option').removeClass('wcd-hidden');
            $(this).blur();
        }
    });

    /**
     * Render tags for selected checkboxes in a dropdown.
     */
    function renderDropdownTags($dropdown) {
        var $tagsContainer = $dropdown.find('.wcd-checkbox-dropdown-tags');
        var $search = $dropdown.find('.wcd-checkbox-dropdown-search');
        var placeholder = $dropdown.data('placeholder') || 'All';
        var $checked = $dropdown.find('input[type="checkbox"]:checked');

        $tagsContainer.empty();

        if ($checked.length === 0) {
            $search.attr('placeholder', placeholder);
        } else {
            $search.attr('placeholder', '');
            $checked.each(function () {
                var label = $(this).next('span').text().trim();
                var value = $(this).val();
                var $tag = $('<span class="wcd-tag"></span>')
                    .attr('data-value', value)
                    .attr('title', label)
                    .append($('<span class="wcd-tag-label"></span>').text(label))
                    .append('<span class="wcd-tag-remove">&times;</span>');
                $tagsContainer.append($tag);
            });
        }
    }

    // Initialize tags on page load.
    $(document).ready(function () {
        $('.wcd-checkbox-dropdown').each(function () {
            renderDropdownTags($(this));
        });
    });
})(jQuery);
