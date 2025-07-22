/**
 * WebChangeDetector Feedback JavaScript
 *
 * Enhanced user feedback and error handling UI functionality.
 */

(function ($) {
    'use strict';

    // Initialize feedback system.
    var WCDFeedback = {

        /**
         * Initialize feedback functionality.
         */
        init: function () {
            this.setupDismissibleNotices();
            this.setupHealthCheckButton();
            this.setupErrorRecoveryButtons();
            this.setupLogClearingButton();
            this.setupAutoRefresh();
        },

        /**
         * Setup dismissible notices with AJAX.
         */
        setupDismissibleNotices: function () {
            $(document).on('click', '.notice-dismiss', function () {
                var $notice = $(this).closest('.notice');
                var noticeId = $notice.data('notice-id');

                if (noticeId) {
                    $.ajax({
                        url: wcdFeedback.ajax_url,
                        type: 'POST',
                        data: {
                            action: 'webchangedetector_dismiss_notice',
                            notice_id: noticeId,
                            nonce: wcdFeedback.nonce
                        },
                        success: function (response) {
                            if (response.success) {
                                $notice.fadeOut();
                            }
                        }
                    });
                }
            });
        },

        /**
         * Setup health check button.
         */
        setupHealthCheckButton: function () {
            $(document).on('click', '.wcd-health-check-btn', function (e) {
                e.preventDefault();

                var $button = $(this);
                var $spinner = $button.find('.spinner');
                var $statusContainer = $('.wcd-health-status');

                $button.prop('disabled', true);
                $spinner.addClass('is-active');

                $.ajax({
                    url: wcdFeedback.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'webchangedetector_run_health_check',
                        nonce: wcdFeedback.nonce
                    },
                    success: function (response) {
                        if (response.success) {
                            WCDFeedback.updateHealthStatus(response.data);
                            WCDFeedback.showNotice('Health check completed successfully.', 'success');
                        } else {
                            WCDFeedback.showNotice('Health check failed: ' + response.data, 'error');
                        }
                    },
                    error: function () {
                        WCDFeedback.showNotice('Health check request failed.', 'error');
                    },
                    complete: function () {
                        $button.prop('disabled', false);
                        $spinner.removeClass('is-active');
                    }
                });
            });
        },

        /**
         * Setup error recovery buttons.
         */
        setupErrorRecoveryButtons: function () {
            $(document).on('click', '.wcd-recovery-btn', function (e) {
                e.preventDefault();

                var $button = $(this);
                var errorType = $button.data('error-type');
                var errorMessage = $button.data('error-message') || 'Manual recovery attempt';

                $button.prop('disabled', true);
                $button.text('Attempting Recovery...');

                $.ajax({
                    url: wcdFeedback.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'webchangedetector_attempt_recovery',
                        error_type: errorType,
                        error_message: errorMessage,
                        nonce: wcdFeedback.nonce
                    },
                    success: function (response) {
                        if (response.success) {
                            WCDFeedback.showNotice('Recovery successful: ' + response.data.message, 'success');
                            $button.text('Recovery Successful');
                            $button.addClass('button-primary');
                        } else {
                            WCDFeedback.showNotice('Recovery failed: ' + response.data.message, 'error');
                            $button.text('Recovery Failed');
                        }
                    },
                    error: function () {
                        WCDFeedback.showNotice('Recovery request failed.', 'error');
                        $button.text('Request Failed');
                    },
                    complete: function () {
                        setTimeout(function () {
                            $button.prop('disabled', false);
                            $button.text('Attempt Recovery');
                            $button.removeClass('button-primary');
                        }, 3000);
                    }
                });
            });
        },

        /**
         * Setup log clearing button.
         */
        setupLogClearingButton: function () {
            $(document).on('click', '.wcd-clear-logs-btn', function (e) {
                e.preventDefault();

                if (!confirm('Are you sure you want to clear the logs? This action cannot be undone.')) {
                    return;
                }

                var $button = $(this);
                var days = $('#wcd-log-retention-days').val() || 30;

                $button.prop('disabled', true);
                $button.text('Clearing Logs...');

                $.ajax({
                    url: wcdFeedback.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'webchangedetector_clear_logs',
                        days: days,
                        nonce: wcdFeedback.nonce
                    },
                    success: function (response) {
                        if (response.success) {
                            WCDFeedback.showNotice('Logs cleared successfully.', 'success');
                            // Refresh logs table if present.
                            $('.wcd-logs-table').fadeOut(function () {
                                $(this).html('<tr><td colspan="5">No recent logs found.</td></tr>').fadeIn();
                            });
                        } else {
                            WCDFeedback.showNotice('Failed to clear logs: ' + response.data, 'error');
                        }
                    },
                    error: function () {
                        WCDFeedback.showNotice('Log clearing request failed.', 'error');
                    },
                    complete: function () {
                        $button.prop('disabled', false);
                        $button.text('Clear Logs');
                    }
                });
            });
        },

        /**
         * Setup auto-refresh for status pages.
         */
        setupAutoRefresh: function () {
            var $autoRefreshToggle = $('#wcd-auto-refresh');
            var refreshInterval;

            if ($autoRefreshToggle.length) {
                $autoRefreshToggle.on('change', function () {
                    if ($(this).is(':checked')) {
                        refreshInterval = setInterval(function () {
                            WCDFeedback.refreshPageData();
                        }, 30000); // Refresh every 30 seconds.
                        WCDFeedback.showNotice('Auto-refresh enabled (30 seconds).', 'info');
                    } else {
                        clearInterval(refreshInterval);
                        WCDFeedback.showNotice('Auto-refresh disabled.', 'info');
                    }
                });
            }
        },

        /**
         * Refresh page data via AJAX.
         */
        refreshPageData: function () {
            var $refreshContainer = $('.wcd-refresh-container');

            if ($refreshContainer.length) {
                $refreshContainer.fadeTo(500, 0.5);

                // Refresh health status if present.
                if ($('.wcd-health-status').length) {
                    $('.wcd-health-check-btn').trigger('click');
                }

                // Refresh logs if present.
                if ($('.wcd-logs-table').length) {
                    this.refreshLogs();
                }

                $refreshContainer.fadeTo(500, 1);
            }
        },

        /**
         * Refresh logs table.
         */
        refreshLogs: function () {
            var $logsTable = $('.wcd-logs-table tbody');

            $.ajax({
                url: wcdFeedback.ajax_url,
                type: 'POST',
                data: {
                    action: 'webchangedetector_get_recent_logs',
                    nonce: wcdFeedback.nonce
                },
                success: function (response) {
                    if (response.success && response.data.logs) {
                        WCDFeedback.updateLogsTable(response.data.logs);
                    }
                }
            });
        },

        /**
         * Update logs table with new data.
         *
         * @param {Array} logs Array of log entries.
         */
        updateLogsTable: function (logs) {
            var $tbody = $('.wcd-logs-table tbody');
            $tbody.empty();

            if (logs.length === 0) {
                $tbody.append('<tr><td colspan="5">No recent logs found.</td></tr>');
                return;
            }

            logs.forEach(function (log) {
                var levelClass = 'log-level-' + log.level;
                var row = '<tr class="' + levelClass + '">' +
                    '<td>' + log.timestamp + '</td>' +
                    '<td><span class="log-level ' + levelClass + '">' + log.level.toUpperCase() + '</span></td>' +
                    '<td>' + log.category + '</td>' +
                    '<td>' + WCDFeedback.escapeHtml(log.message) + '</td>' +
                    '<td>' +
                    '<button class="button button-small wcd-view-log-details" data-log-id="' + log.id + '">Details</button>' +
                    '</td>' +
                    '</tr>';
                $tbody.append(row);
            });
        },

        /**
         * Update health status display.
         *
         * @param {Object} healthData Health check data.
         */
        updateHealthStatus: function (healthData) {
            var $statusContainer = $('.wcd-health-status');

            if (!$statusContainer.length) {
                return;
            }

            $statusContainer.empty();

            var statusClass = healthData.overall_status === 'healthy' ? 'status-healthy' : 'status-unhealthy';
            var statusIcon = healthData.overall_status === 'healthy' ? '✓' : '✗';

            var html = '<div class="health-overall ' + statusClass + '">' +
                '<h3>' + statusIcon + ' System Status: ' + healthData.overall_status.toUpperCase() + '</h3>' +
                '<p>Last checked: ' + healthData.timestamp + '</p>' +
                '</div>';

            html += '<div class="health-checks">';

            Object.keys(healthData.checks).forEach(function (checkName) {
                var check = healthData.checks[checkName];
                var checkClass = check.status ? 'check-pass' : 'check-fail';
                var checkIcon = check.status ? '✓' : '✗';

                html += '<div class="health-check ' + checkClass + '">' +
                    '<h4>' + checkIcon + ' ' + checkName.charAt(0).toUpperCase() + checkName.slice(1) + '</h4>' +
                    '<p>' + check.message + '</p>';

                if (check.details) {
                    html += '<details><summary>Details</summary><pre>' +
                        JSON.stringify(check.details, null, 2) + '</pre></details>';
                }

                html += '</div>';
            });

            html += '</div>';

            $statusContainer.html(html);
        },

        /**
         * Show a notification message.
         *
         * @param {string} message Notification message.
         * @param {string} type    Notification type (success, error, warning, info).
         */
        showNotice: function (message, type) {
            type = type || 'info';

            var $notice = $('<div class="notice notice-' + type + ' is-dismissible">' +
                '<p>' + message + '</p>' +
                '<button type="button" class="notice-dismiss"><span class="screen-reader-text">Dismiss this notice.</span></button>' +
                '</div>');

            $('.wrap h1').after($notice);

            // Auto-dismiss success and info notices after 5 seconds.
            if (type === 'success' || type === 'info') {
                setTimeout(function () {
                    $notice.fadeOut();
                }, 5000);
            }
        },

        /**
         * Escape HTML for safe display.
         *
         * @param {string} text Text to escape.
         * @return {string} Escaped text.
         */
        escapeHtml: function (text) {
            var div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
    };

    // Initialize when document is ready.
    $(document).ready(function () {
        WCDFeedback.init();
    });

    // Make available globally.
    window.WCDFeedback = WCDFeedback;

})(jQuery); 