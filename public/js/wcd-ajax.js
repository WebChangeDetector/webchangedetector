
const DAYS_PER_MONTH = 30;
const HOURS_IN_DAY = 24;
const EMPTY_GROUP_LENGTH = 0;
var checkingInterval = [];


// Create closure and inject jQuery (see last line)
(function ($) {
    $(document).ready(function () {

        // Init AJAX
        $.ajaxSetup({
            type: 'POST',
            headers: {
                "cache-control": "no-cache",
                'X-CSRF-Token': $('meta[name="csrf-token"]').attr('content')
            }
        });

        loadAjaxFunctions();
    });

    /**
     * Functions loaded at document.ready()
     */
    function loadAjaxFunctions() {

        // --------------------------------------------------------------------
        // Functions
        // --------------------------------------------------------------------

        // Function to delete all event listener before page refresh
        function clearListenersBeforeRefresh() {
            // Remove all Submit-Handler from forms
            $("#ajax_url_preview").off("submit");
            $("#ajax_screenshot_preview_signup").off("submit");
            $("#ajax_screenshot_preview").off("submit");
            $("form.ajax_get_preview_wp_urls").off("submit");
            $("#ajax_urls_preview").off("submit");
            $("form.ajax").off("submit");
            $("form.ajax-async").off("submit");
            $("form.ajax-filter").off("submit");
            $("form.ajax-reload-url-types").off("submit");
            $("form.ajax-get-wp-urltypes").off("submit");

            // Remove click handler of buttons and other clickable elements
            $(".ajax-select-all").off("click");
            $(".group-url-checkbox").off("click");
            $(".ajax-switch-account-button").off("click");
            $(".plainSelect li").off("click");
            $(".ajax_delete_url").off("click");
            $("#btn-assign-url").off("click");
            $(".delete_group_button").off("click");
            $(".accordion-ajax-group-urls").off("click");
            $(".ajax_enable_monitoring").off("click");
            $(".ajax_enable_group").off("click");

            // Remove account switch handler (if available)
            $(".ajax-switch-account").off("change");

            // Remove all intervals which are saved at checkingInterval
            for (var key in checkingInterval) {
                if (checkingInterval.hasOwnProperty(key)) {
                    clearInterval(checkingInterval[key]);
                }
            }
        }

        /**
         * Reload page via ajax
         *
         * @param {string} message
         */
        function refreshWcdPage(message, extraData = {}) {

            // Remove listeners before page refresh
            clearListenersBeforeRefresh();

            const queryString = window.location.search;
            const urlParams = new URLSearchParams(queryString);
            const tab = urlParams.get('tab');
            const subTab = urlParams.get('subtab');

            var data = {
                action: 'wcd_content',
                ajaxTab: tab,
                //ajaxSubTab: subTab
            };

            data = { ...data, ...extraData }; // merge

            $.post(ajaxurl, data, function (response) {

                // Reload entire page
                $('#wcd_content').replaceWith(response);

                // Show the success message
                if (message) {
                    show_message('success', message);
                }

                $.fn.loadJs();
                loadAjaxFunctions();
            });
        }

        function switchAccount(id) {
            const args = {
                action: 'switch_account',
                user_id: id
            }
            $('#mm_loading').show();
            $.post(ajaxurl, args, function (response) {
                refreshWcdPage(response);
            });
        }

        $('#ajax_url_preview').on("submit", function (e) {
            e.preventDefault();
            let url = $('#wp_url').val();
            if (url.indexOf('http') < 0) {
                url = "http://" + url;
            }
            if (!isUrlValid(url)) {
                alert("Invalid URL. Please try again.");
                return false;
            }

            var data = {
                action: 'get_wp_urls',
                url: url,
            };

            $("#preview_urls").html('<img src="/wp-content/plugins/app/public/img/loader.gif">');
            $.post(ajaxurl, data, function (response) {
                if (response !== 'error') {
                    $("#preview_urls").html(response);
                } else {
                    $("#preview_urls").html("<div id='preview_message'><p>We couldn't find the webpage. Please try again.</p></div>");
                }
            });
        });

        // Signup after screenshot
        $('#ajax_screenshot_preview_signup').on("submit", function (e) {
            e.preventDefault();

            let device = $("#preview_device").val();
            let hour_of_day = $("#preview_hour_of_day").val();
            let interval_in_h = $("#preview_interval_in_h").val();
            let email = $("#preview_email").val();

            document.cookie = 'wcd-device=' + device + '; expires=Thu, 01 Jan 2100 12:00:00 UTC; path=/';
            document.cookie = 'wcd-check-type=auto; expires=Thu, 01 Jan 2100 12:00:00 UTC; path=/';
            document.cookie = 'wcd-hour_of_day=' + hour_of_day + '; expires=Thu, 01 Jan 2100 12:00:00 UTC; path=/';
            document.cookie = 'wcd-interval_in_h=' + interval_in_h + '; expires=Thu, 01 Jan 2100 12:00:00 UTC; path=/';
            document.cookie = 'wcd-email=' + email + '; expires=Thu, 01 Jan 2100 12:00:00 UTC; path=/';

            location.href = '/free-trial';
        });

        // Lead magnet screenshot with monitoring
        $('#ajax_screenshot_preview').on("submit", function (e) {
            e.preventDefault();
            let previewContainer = $("#preview_screenshot")
            let url = $('#preview_url').val();

            if (url.indexOf('http') < 0) {
                url = "http://" + url;
            }
            if (!isUrlValid(url)) {
                alert("Invalid URL. Please try again.");
                return false;
            }

            $("#preview_in_progress").show();

            startSpinner();
            $('#ajax_screenshot_preview input[type="submit"]').prop('disabled', true);

            previewContainer.hide();
            $('.progress-bar-fill').css('width', '100%');

            let data = {
                action: 'preview_screenshot',
                url: url
            };
            //$("#preview_screenshot").html("test");
            jQuery.ajax({
                url: ajaxurl,
                data: data,
                type: 'POST',
                timeout: 90000,
                success: function (response) {
                    $("#preview_in_progress").hide();
                    $('#ajax_screenshot_preview input[type="submit"]').prop('disabled', false);
                    $('.progress-bar-fill').css("width", "0%");

                    previewContainer.show();
                    document.cookie = 'wcd-domain=' + url + '; expires=Thu, 01 Jan 2100 12:00:00 UTC; path=/';
                    document.cookie = 'wcd-type=general; expires=Thu, 01 Jan 2100 12:00:00 UTC; path=/';

                    $("#preview_screenshot").html("<img src='" + response + "'>");
                    $("#btn-start-change-detection").show();
                    $("#enable-btn-url").show();
                    $("#btn-url").html($('#preview_url').val());

                },
                error: function () {
                    previewContainer.html("<div id='preview_message'><p>We couldn't find the webpage. Please try again.</p><p style='display: none'>" + response + "</p></div>");
                }
            });
        });

        function isJson(str) {
            try {
                JSON.parse(str);
            } catch (e) {
                return false;
            }
            return true;
        }

        // Lead magnet WP urls
        $("form.ajax_get_preview_wp_urls").on("submit", function (e) {
            e.preventDefault();
            let form = $(this);
            let url = $('#preview_url').val();
            let device = $('#preview_device').val();
            let type = $("#preview_type").val();
            let errorText = "<div style='text-align: center;'>" +
                "<span class='dashicons dashicons-dismiss' style='font-size: 50px; margin-bottom: 50px;'></span>" +
                "<h3>Sorry, it seems like this is not a WordPress website.</h3>" +
                "<p>Please check your Domain and make sure the WP Rest api is enabled.</p>" +
                "<p style='margin-bottom: 10px;'>or</p>" +
                "<a href='' class='et_pb_button' onclick='" +
                "jQuery(\"#cta-wp-urls\").html(\"\"); " +
                "jQuery(\"#cta-preview-screenshot\").show(); " +
                //"jQuery(\"#cta-preview-screenshot #preview_url\").val(jQuery(\"#cta-wp-urls #preview_url\").val()); " +
                //"jQuery(\"#cta-preview-screenshot #ajax_screenshot_preview\").submit(); " +
                "return false;'>" +
                "Monitor a website" +
                "</a></div>";
            $("#preview_wp_urls_in_progress").show();
            $("#btn-start-change-detection").hide();
            $("#preview_wp_urls").hide();
            $.post(ajaxurl, form.serialize(), function (response) {
                let output = '';
                let amountUrls = 0;
                $("#preview_wp_urls_in_progress").hide();

                let jsonResponse = JSON.parse(response);
                if (typeof jsonResponse == 'object') {
                    var tableToSort = $(this).find("table");


                    document.cookie = "wcd-check-type=manual; expires=Thu, 01 Jan 2100 00:00:00 UTC; path=/";
                    document.cookie = 'wcd-domain=' + url + '; expires=Thu, 01 Jan 2100 12:00:00 UTC; path=/';
                    document.cookie = 'wcd-device=' + device + '; expires=Thu, 01 Jan 2100 12:00:00 UTC; path=/';
                    document.cookie = 'wcd-type=' + type + '; expires=Thu, 01 Jan 2100 12:00:00 UTC; path=/';

                    output += "<div class='enabled_switch devices'><table>" +
                        "<tr>" +
                        "   <th>Desktop / Mobile</th>" +
                        "   <th onclick='sortTable(0," + tableToSort + ")'>Title</th>" +
                        "   <th onclick='sortTable(1," + tableToSort + ")'>URL</th>" +
                        "   <th onclick='sortTable(2," + tableToSort + ")'>URL Type</th>" +
                        "</tr>";
                    $.each(jsonResponse, function (index, element) {

                        output += '<tr>' +
                            '<td>' +
                            '<label class="switch" style="width: 120px;"><input class="check-desktop" type="checkbox" data-device="desktop" data-cms_resource_id="' + element.cms_resource_id + '"><span class="slider round">Desktop</span></label>' +
                            '<label class="switch" style="width: 120px;"><input class="check-mobile" type="checkbox" data-device="desktop" data-cms_resource_id="' + element.cms_resource_id + '"><span class="slider round">Mobile</span></label>' +
                            '</td>' +

                            '<td><strong>' + element.html_title + '</strong></td>' +
                            '<td><a href="' + element.url + '" target="_blank">' + element.url + '</a></td>' +
                            '<td>' + element.url_category + '</td>' +
                            //'<td><a href="#" class="et_pb_button">Check this url</a></td>' +
                            '</tr>';
                        amountUrls++;
                    });
                    output += "</table></div>";

                    output = '<p style="font-size: 18px; margin-bottom: 20px; text-align: center;"><strong>Select the webpages and screensizes which you want to check.</strong></p>' +
                        /*'Desktop: <span id="total-desktop-active" class="totals desktop"><strong>0</strong></span> webpages | ' +
                        'Mobile: <span id="total-mobile-active" class="totals mobile">0</span> webpages | ' +
                        'Required Checks: <span id="total-required-sc" class="totals screenshots">0</span> screenshots' + */
                        output;

                    $("#btn-start-change-detection").show();
                    $("#enable-btn-url").show();
                    $("#btn-url").html($("#preview_url").val());
                } else {
                    output += errorText;
                    $("#btn-start-change-detection").hide();
                }

                $("#preview_wp_urls").show();
                $("#preview_wp_urls").html(output);

                $('#preview_wp_urls .switch input').on("click", function () {
                    let desktop = $('#preview_wp_urls input.check-desktop:checked').length;
                    let mobile = $('#preview_wp_urls input.check-mobile:checked').length;
                    $('#total-desktop-active').html(desktop);
                    $('#total-mobile-active').html(mobile);
                    $('#total-required-sc').html((mobile + desktop) * 2);
                })
            }).error(function (xhr, status, error) {
                $("#preview_wp_urls_in_progress").hide();
                $("#preview_wp_urls").show();
                $("#preview_wp_urls").html(errorText);
            })
        });

        $("#ajax_urls_preview").on("submit", function (e) {
            e.preventDefault();
            var active_ids_desktop = [];
            var active_ids_mobile = [];
            $(".check-desktop:checked").each(function (i, checkbox) {
                active_ids_desktop.push($(checkbox).data("cms_resource_id"));
            });
            $(".check-mobile:checked").each(function (i, checkbox) {
                active_ids_mobile.push($(checkbox).data("cms_resource_id"));
            });

            document.cookie = "wcd-manual-checks-mobile-ids=" + active_ids_mobile + "; expires=Thu, 01 Jan 2100 00:00:00 UTC; path=/";
            document.cookie = "wcd-manual-checks-desktop-ids=" + active_ids_desktop + "; expires=Thu, 01 Jan 2100 00:00:00 UTC; path=/";
            window.location.href = '/free-trial';
        });

        function timeToDate(countDownDate, groupId, intervalInH) {
            let output = "";

            // Get today's date and time
            var now = new Date().getTime();

            // Find the distance between now and the count down date
            var distance = countDownDate - now;

            // Time calculations for days, hours, minutes and seconds
            var days = Math.floor(distance / (1000 * 60 * 60 * 24));
            var hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
            var minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
            var seconds = Math.floor((distance % (1000 * 60)) / 1000);

            let result = ""
            // Early return if countdown is over
            if (days <= 0 && hours <= 0 && minutes <= 0 && seconds <= 0) {

                if (jQuery("#currently-processing").html().length > 0) {
                    let groupNextScDate = $("#accordion_group_" + groupId).data("auto_next_sc_date");
                    $("#accordion_group_" + groupId).data("auto_next_sc_date", groupNextScDate + intervalInH * 60 * 60);
                }
                return result + "<strong>Now</strong>";
            }

            if (days) {
                output += days + "d ";
            }
            if (hours) {
                output += hours + "h ";
            }

            return result + "<strong>" + output + minutes + "m " + seconds + "s </strong>";
        }

        /**
         * Updates the total SC needed per month
         *
         * TODO this doesn't work if only one group is expanded (either none (-> default) or all)
         * -> it should really come from another API call, not calculated locally by summing up DOM elements
         */
        function updateTotals() {
            // Find all groups/group accordions
            var groupAccordions = $('[id^="accordion_group_"]');

            // init
            var amountSelectedTotal = 0;
            var nextScDate = false;
            var nextScIn = false;
            var availableCredits = $("#available-credits").data("available_sc");
            var enabledGroupIds = [];
            var popupTakeScreenshotTotals = $("#sc-groups-and-urls");

            // Clear old data of take screenshots popup
            popupTakeScreenshotTotals.html('<table class="toggle" id="popupTakeScreenshotsTable" name="table-added-by-jquery">' +
                '<tr>' +
                '<th width="100%">Group</th> ' +
                '<th style="text-align-last: right;">Checks</th>' +
                '</tr>' +
                '</table>');

            groupAccordions.each(function (index) {
                // remember this
                var groupAccordion = $(this);
                var groupId = groupAccordion.data("group_id");
                var groupAutoEnabled = groupAccordion.data("auto_enabled");
                var groupName = $("#group_name-" + groupId).html();
                var groupInterval = groupAccordion.data("auto_sc_interval");
                var groupEnabled = groupAccordion.find('.ajax_enable_group');
                var autoGroup = groupAccordion.data("auto_group");
                let groupNextScDate = groupAccordion.data("auto_next_sc_date");
                let groupNextScIn = groupAccordion.data("auto_next_sc_in");
                let groupScPerUrlUntilRenew = groupAccordion.data("auto_sc_per_url_until_renewal"); //@TODO amount is for whole group and not per url.
                let amountGroupTotal = groupAccordion.data("amount_sc");
                var popupTakeScreenshotTable = popupTakeScreenshotTotals.find("table tr:last");
                let enabledChecked = '';
                let groupTotalUpdated = $("#accordion_group_" + groupId).data("amount_sc");
                let totalSelectedUrls = 0;
                let nextScInLive = 0;
                if (groupEnabled.is(":checked")) {
                    enabledChecked = 'checked';
                }
                let autoGroupEnabled = jQuery(groupAccordion).data("auto_enabled");
                let updateGroupEnabled = jQuery(groupAccordion).find(".ajax_enable_group").prop("checked");

                // Add groups to confirm screenshots popup
                if (amountGroupTotal > 0 && enabledChecked) {
                    popupTakeScreenshotTable.after("<tr>" +
                        "<td>" + groupName + "</td>" +
                        "<td>" + amountGroupTotal + "</td>" +
                        "</tr>"
                    );
                }

                // Start / stop interval counting time until next screenshot
                if ((groupTotalUpdated === 0 || !groupAutoEnabled)) {
                    clearInterval(checkingInterval[groupId]);
                    delete checkingInterval[groupId];

                    $("#status-next-check-group-" + groupId).html("<strong>Not checking</strong>");
                } else {
                    if (typeof (checkingInterval[groupId]) == "undefined") {
                        checkingInterval[groupId] = setInterval(function () {
                            groupTotalUpdated = $("#accordion_group_" + groupId).data("amount_sc");
                            let groupNextScDateUpdated = $("#accordion_group_" + groupId).data("auto_next_sc_date");
                            let countdown = timeToDate(groupNextScDateUpdated * 1000, groupId, groupInterval);
                            $("#status-next-check-group-" + groupId).html(countdown);
                        }, 1000);
                    }
                }


                if (amountGroupTotal > 0) {
                    $("#status-animation-group-" + groupId).html("<div class=\"animation-enabled active\" ></div>");
                    $("#status-group-" + groupId).html("<small>Monitoring is</small><br><strong>Active</strong><br>");
                } else {
                    $("#status-animation-group-" + groupId).html("<div class=\"animation-enabled\" ></div>");
                    $("#status-group-" + groupId).html("<small>Monitoring is</small><br><strong>Off</strong><br>");
                }

                // Find h3 of accordion
                let h3 = groupAccordion.closest('h3');
                // if it's folded down, we can calculate URls
                if (h3 && h3.hasClass('ui-accordion-header-active')) {
                    // Count URLs inside accordion
                    let amountUrls = groupAccordion.find('[id^="' + groupId + '"]').length;
                    $("#amount-group-urls-" + groupId).html(amountUrls);
                }

                // Calculate Auto Group
                if (autoGroup && groupAutoEnabled && amountGroupTotal > 0) {
                    amountSelectedTotal += groupScPerUrlUntilRenew * amountGroupTotal;

                    if (!nextScDate || nextScDate > groupNextScDate) {
                        nextScDate = groupNextScDate;
                        nextScIn = groupNextScIn;
                    }
                }

                // Count selected webpages in group
                let selectedUrls = 0;
                let selectedUrlIds = [];
                jQuery(groupAccordion).find(".group-url-checkbox").each(function (i, e) {
                    if (jQuery(e).prop("checked")) {
                        if (!selectedUrlIds.includes(jQuery(e).data('url_id'))) {
                            selectedUrls++;
                            selectedUrlIds.push(jQuery(e).data('url_id'));
                        }
                    }
                });

                selectedUrls = amountGroupTotal;

                // selected URLs if accordions are closed
                if ((autoGroup == 1 && autoGroupEnabled == 1) || (autoGroup == 0 && updateGroupEnabled == true)) {
                    if (typeof (jQuery("#table_group_" + groupId)).html() == "undefined") {
                        selectedUrls = jQuery(groupAccordion).data("amount_sc");
                    }
                } else {
                    selectedUrls = "0";
                }

                //jQuery("#status-amount-webpages-group-" + groupId).html(selectedUrls);

                // Calculate total update group
                if (!autoGroup && updateGroupEnabled == true) {
                    enabledGroupIds.push(groupId);
                    amountSelectedTotal += selectedUrls;
                }

                jQuery(groupAccordion).find(".live-filter-row").each(function (i, e) {
                    mmMarkRows(jQuery(e).attr("id"));
                });
            });

            // Update totals in popup take screenshots
            popupTakeScreenshotTotals.append("<br><strong>Total: <span id='ajax_amount_update_total_sc'>" + amountSelectedTotal + "</span> Checks</strong><br>")

            // Disable update detection button when credits are not enough
            if (amountSelectedTotal > availableCredits || amountSelectedTotal === 0) {
                $('#btn-start-update-detection').attr('disabled', 'disabled');
            } else {
                $('#btn-start-update-detection').removeAttr('disabled');
            }
            // Update group ids for taking screenshots in both `<input>`s (for `take-screenshots`) in popup
            $("#pre-sc-group-ids").val(enabledGroupIds.join(","));
            //$("#post-sc-group-ids").val(enabledGroupIds.join(","));

            // Set time until next screenshots
            var txtNextScIn = "No monitoring active";
            $("#auto-detection-status-container").removeClass("active");
            $("#auto-detection-status").html("Off");

            $("#txt_next_sc_in").html("Currently");
            $("#next_sc_date").html("");
            if (nextScIn) {
                let now = new Date($.now()); // summer/winter - time
                nextScIn = new Date(nextScDate * 1000); // summer/winter - time
                nextScIn = new Date(nextScIn - now); // normal time
                nextScIn.setHours(nextScIn.getHours() + (nextScIn.getTimezoneOffset() / 60)); // add timezone offset to normal time
                var minutes = nextScIn.getMinutes() == 1 ? " Minute " : " Minutes ";
                var hours = nextScIn.getHours() == 1 ? " Hour " : " Hours ";
                txtNextScIn = nextScIn.getHours() + hours + nextScIn.getMinutes() + minutes;
                $("#next_sc_date").html(getLocalDateTime(nextScDate));
                $("#txt_next_sc_in").html("Next run in ");

                $("#auto-detection-status-container").addClass("active");
                $("#auto-detection-status").html("Active");
            }
            $("#next_sc_in").html(txtNextScIn);

            if (availableCredits <= 0) {
                $("#next_sc_in").html("Not Monitoring").css("color", "#A00000");
                $("#next_sc_date").html("<span style='color: #a00000'>You ran out of checks.</span><br>" +
                    "<a href='" + upgradeUrl + "'>Upgrade plan</a>");
            }

            // Update total credits on top of page
            $("#ajax_amount_total_sc").removeClass("loading");
            $("#ajax_amount_total_sc").html(amountSelectedTotal);
            $("#sc_until_renew").removeClass("exceeding");
            $("#sc_available_until_renew").removeClass("exceeding");


            if (amountSelectedTotal > availableCredits) {
                $("#sc_until_renew").addClass("exceeding");
                $("#sc_available_until_renew").addClass("exceeding");
                $("#notice_screenshots_exceeding").show();
            }

            updatePrePostScButtons(amountSelectedTotal, availableCredits);
        }

        function updatePrePostScButtons(amountSelectedTotal, availableCredits) {
            let messageContainer = $("#message-sc-buttons");
            let preScButton = $("#button-pre-sc");
            let postScButton = $("#button-post-sc");

            if (amountSelectedTotal === 0) {
                messageContainer.show();
                messageContainer.html("Please select URLs before starting Change Detections.");
                preScButton.prop("disabled", true);
                postScButton.prop("disabled", true);
            } else if (amountSelectedTotal > availableCredits) {
                messageContainer.show();
                messageContainer.html("Not enough credit for the Change Detections. " +
                    "Please upgrade your account.");
                preScButton.prop("disabled", true);
                postScButton.prop("disabled", true);
            } else {
                messageContainer.hide();
                messageContainer.html("");
                preScButton.prop("disabled", false);
                postScButton.prop("disabled", false);
            }
        }

        // Needs to be called initially for pre/post buttons
        updateTotals();

        function saveCodeareas() {
            if (editors.length > 0) {
                $.each(editors, function (i, editor) {
                    if (editor.save) {
                        // Make sure we store changes into original textarea
                        editor.save();
                    }
                });
            }
        }



        // Load batch comparisons content and handle pagination
        function loadBatchComparisons(element, batchId, page = 1, filters = null, shouldScroll = false) {
            const batchContainer = $(".accordion-container[data-batch_id='" + batchId + "']");
            const contentContainer = batchContainer.find(".ajax_batch_comparisons_content");
            const failedCount = batchContainer.data("failed_count");

            const args = {
                action: 'get_batch_comparisons_view',
                batch_id: batchId,
                page: page,
                filters: filters,
                failed_count: failedCount
            }

            // Show loading placeholder
            contentContainer.html('<div class="ajax-loading-container"><img decoding="async" src="/wp-content/plugins/app/public/img/loading.gif"><div style="text-align: center;">Loading</div></div>');

            // Only scroll for pagination, not initial load
            if (shouldScroll) {
                $([document.documentElement, document.body]).animate({
                    scrollTop: batchContainer.offset().top
                }, 500);
            }

            $.post(ajaxurl, args, function (response) {
                contentContainer.html(response);

                // Bg color for difference
                $(".diff-tile").each(function () {
                    var diffPercent = $(this).data("diff_percent");
                    if (diffPercent > 0) {
                        var bgColor = getDifferenceBgColor($(this).data("diff_percent"), $(this).data("threshold"));
                        $(this).css("background", bgColor);
                    }
                });
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
            $(".latest_compares_content .accordion-container .accordion ").off("accordionactivate.batchLoad").on("accordionactivate.batchLoad", function (event, ui) {
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

        initBatchComparisonsPagination();
        /**
         * Load Group urls
         */
        function updateGroups(e) {
            // Disable Take screenshots buttons until the groups a loaded
            // and we know if there are screenshots selected
            $("#button-pre-sc").prop("disabled", true);
            $("#button-post-sc").prop("disabled", true);

            // remember this
            var h3 = $(e);

            // only on folding down the accordion, not up
            // TODO closest to $this class
            if (h3.closest('accordion-ajax-group-urls').hasClass('ui-accordion-header-active')) {
                return;
            }

            // only if it's not already been done for this id
            if (h3.closest(".mm_accordion_content").hasClass('already-loaded')) {
                return;
            }

            // cut groupId out from id
            let groupId = h3.data('group_id');


            // cms might come from data-cms attribute
            let cms = '';
            if ($(h3).data('cms')) {
                cms = $(h3).data('cms');
            }

            let page = 1;
            if ($(h3).data('page')) {
                page = $(h3).data('page');
            }

            let limitUrls = 50;
            if ($(h3).data('limit_urls')) {
                limitUrls = $(h3).data('limit_urls');
            }
            let offsetUrls = 0;
            if ($(h3).data('offset_urls')) {
                offsetUrls = $(h3).data('offset_urls');
            }

            let search = false;
            if ($(h3).data('search')) {
                search = $(h3).data('search');
            }
            // build json payload
            var data = {
                action: 'load_group_urls',
                group_id: groupId,
                cms: cms,
                page: page,
                limit_urls: limitUrls,
                offset_urls: offsetUrls
            };
            if (search) {
                data.search = search;
            }

            // response is the html
            $.post(ajaxurl, data, function (response) {

                // Make table fullwith in accordion
                if (response.indexOf("<table") === 0) {
                    h3.closest(".mm_accordion_content").addClass("no-padding");
                    // set flag in DOM
                    h3.closest(".mm_accordion_content").addClass("already-loaded");
                }

                // add table or msg
                var groupUrlsContainer = $("#ajax-group-urls_" + groupId);
                groupUrlsContainer.html(response);

                // whatevs
                updateTotals();
                createGroupUrlCheckboxEventHandler();
                createAjaxSelectAllEventListener();
                createFilterEventListener(groupUrlsContainer);

                // Remove and Add Event Listeners for editing CSS
                removeEventListenerFromCss('ajax-edit-css', editCss);
                $(".ajax-edit-css").on("submit", editCss);

                // Remove and Add Event Listeners for unassigning URLs from Group
                removeEventListenerFromCss('ajax_unassign_url', unassignUrl);
                $(".ajax_unassign_url").on("click", unassignUrl);

                // Do pagination
                $(".ajax_paginate_urls").click(function () {
                    $(h3).data("limit_urls", $(this).data("limit_urls"));
                    $(h3).data("offset_urls", $(this).data("offset_urls"));
                    $(h3).data("cms", $(this).data("cms"));
                    $("table#table_group_" + groupId).css('filter', 'blur(2px)')
                    // $("table#table_group_"+groupId).css('backdrop-filter', 'blur(5px)')
                    updateGroups($(this));

                    // scroll to group url start.
                    jQuery('html, body').animate({
                        scrollTop: $(groupUrlsContainer).offset().top - 100
                    }, 500);
                });

                // Do the search
                $(".search-url-table").on("submit", function (e) {

                    let data = $(this).serializeArray();
                    const searchItem = data.find(item => item.name === 'search');
                    const searchValue = searchItem ? searchItem.value : null;

                    $(h3).data("search", searchValue);
                    $("table#table_group_" + groupId).css('filter', 'blur(2px)');
                    updateGroups($(h3));
                })

            }).fail(() => console.error('AJAX call load_group_url failed'));
        }

        function getSelectedUrlsInGroup(groupId) {
            let amountGroup = $('#group-selected-' + groupId).text().trim();

            return amountGroup;
        }

        function updateGroupAndScPopup(groupId, addAmount = 1) {
            let newAmountSc = getSelectedUrlsInGroup(groupId) + parseInt(addAmount);
            let groupEnabled = jQuery("#enabled" + groupId).val();
            if (groupEnabled == true) {
                $('#group-selected-' + groupId).text(newAmountSc);
            } else {
                $('#group-selected-' + groupId).text(0);
            }
            $("#accordion_group_" + groupId).data("amount_sc", newAmountSc);
        }

        /**
         * Instead of a closure, have this as a function for being able to remove the EventListener
         */
        function handleAjaxSelectAll() {
            var selectAllCheckbox = $(this); // remember this
            var device = $(this).data("device")
            var groupId = $(this).data("group_id");
            var table = $("#table_group_" + groupId);
            var deviceRows = table.find('[id^="' + device + '-' + groupId + '-"]');
            var deviceRowsCheckmark = table.find('[id^="checkmark-' + device + '-' + groupId + '-"]');
            var totalSelectedUrls = amountAutoUrls();

            // Init data for ajax call
            var data = {
                action: 'save_group_urls',
                group_id: groupId,
            };

            var deviceChecked = 0;
            if (selectAllCheckbox.is(":checked")) {
                deviceChecked = 1;
            }

            // Abort if plan limits are hit
            if ($("#accordion_group_" + groupId).data("auto_group") && deviceChecked === 1 && deviceRows.length + totalSelectedUrls > MM_FREE_PLAN_MAX_URLS && MM_FREE_PLAN) {
                showPlanLimitationPopup();
                return false;
            }

            // Get the urls for the ajax call
            deviceRows.each(function (index) {
                // this is taken apart in `save_group_urls()`
                let urlId = $(this).data("url_id");
                data['pid-' + urlId] = urlId;
                data[device + '-' + urlId] = deviceChecked;
            });

            // Set all urls of device on loading
            deviceRowsCheckmark.each(function (index) {
                $(this).addClass("loading");
            });

            // Perform the ajax call
            $.post(ajaxurl, data, function (response) {
                response = JSON.parse(response)
                if (typeof (response.selected_urls_count) !== 'undefinded') {
                    $("#accordion_group_" + groupId).data("amount_sc", response.selected_urls_count);
                    // Check if the group is enabled
                    let groupEnabled = jQuery("#enabled" + groupId).val();

                    if (groupEnabled == true) {
                        $('#status-amount-webpages-group-' + groupId).text(response.selected_urls_count);
                    } else {
                        $('#status-amount-webpages-group-' + groupId).text(0);
                    }
                }

                // moved here from mmToggle()
                let checkboxes = document.querySelectorAll('.checkbox-' + device + '-' + groupId + ' input[type=\"checkbox\"]');

                // remember if the checkbox was checked
                let originalCheckboxes = Object.assign({}, checkboxes); // clone
                let prevChecked = {};
                for (cb in originalCheckboxes) {
                    prevChecked[originalCheckboxes[cb].id] = originalCheckboxes[cb].checked
                }

                // check all underlying checkboxes
                checkboxes.forEach((checkbox) => checkbox.checked = checked = deviceChecked);

                // Mark all rows green or red
                let rows = document.querySelectorAll('.post_id_' + groupId);
                rows.forEach((row) => mmMarkRows(row.id));

                deviceRowsCheckmark.each(function (index, checkmark) {
                    // Not sure what would've gone wrong here, jic
                    if (!checkmark.id) {
                        return;
                    }

                    // checkmark is the `<span>`, the corresponding checkbox has the same id but without "checkmark-"
                    let checkboxId = checkmark.id.replace('checkmark-', ''); // checkmark-desktop|mobile-groupId-urlId
                    let checkbox = $('#' + checkboxId);

                    // only count up or down, if this was changed, so the current `:checked` state is different than the one saved previously
                    if (checkbox.is(":checked") && !prevChecked[checkboxId]
                        || !checkbox.is(":checked") && prevChecked[checkboxId]) {
                        updateGroupAndScPopup(groupId, checkbox.is(":checked") ? 1 : -1);
                    }

                    // No matter what, we think it worked and remove the loading class. @TODO
                    $(this).removeClass("loading");
                });
                // Update Pre/Post SC Popup etc
                updateTotals();
            }).fail(() => console.error('Associating URLs to Group did not work'));
        }

        /**
         * Creates EventListener on AJAX call when URLs are loaded
         */
        function createAjaxSelectAllEventListener() {
            // Remove possible EventListeners first
            removeEventListenerFromCss("ajax-select-all", handleAjaxSelectAll);

            // Check all urls by device checkbox - add EventListener with function defined above
            // NOTE there's also another one for mmToggle() in wp-compare-public.js
            $(".ajax-select-all").on("click", handleAjaxSelectAll);
        }

        /**
         * Handler function (instead of closure) for `.group-url-checkbox`
         */
        function handleGroupUrlCheckbox() {
            var checkbox = $(this);
            var totalScContainer = $("#ajax_amount_total_sc");
            var nextScDateContainer = $("#next_sc_date");
            var nextScInContainer = $("#next_sc_in");
            var groupId = checkbox.data("group_id");
            var urlId = checkbox.data("url_id");
            var deviceName = checkbox.data("device");
            var checkmark = $("#checkmark-" + deviceName + "-" + groupId + "-" + urlId);
            var totalSelectedUrls = amountAutoUrls();
            var autoGroup = $("#accordion_group_" + groupId).data("auto_group");
            var sameUrl = false;

            // Get the checked status of the checkbox and set values
            var deviceValue = 0;
            if (checkbox.prop("checked")) {
                deviceValue = 1;
            }

            // We are allowed to activate devices from same url as one check is per url and not per device.
            if ($(checkbox).data("device") === 'mobile' && deviceValue === 1 && $("#desktop-" + groupId + "-" + urlId).is(':checked')) {
                sameUrl = true;
            }
            if ($(checkbox).data("device") === 'desktop' && deviceValue === 1 && $("#mobile-" + groupId + "-" + urlId).is(':checked')) {
                sameUrl = true;
            }

            // Check if plan allows activating url
            if (currentPlanId === MM_FREE_PLAN_ID && totalSelectedUrls >= MM_FREE_PLAN_MAX_URLS && autoGroup && deviceValue === 1 && !sameUrl) {
                checkbox.prop("checked", false);
                mmMarkRows(groupId + "-" + urlId)
                showPlanLimitationPopup();
                return false;
            }

            var data = {
                action: "select_group_url",
                group_id: groupId,
                url_id: urlId,
                device_name: deviceName,
                device_value: deviceValue
            };

            checkmark.addClass("loading");
            totalScContainer.text("");
            totalScContainer.addClass("loading");
            nextScDateContainer.text("");
            nextScDateContainer.addClass("loading");
            nextScInContainer.text("");

            $.post(ajaxurl, data, function (response) {

                totalScContainer.removeClass("loading");
                nextScDateContainer.removeClass("loading");

                checkmark.removeClass("loading");
                checkmark.attr("style", "");
                updateGroupAndScPopup(groupId, deviceValue == 0 ? -1 : deviceValue);

                response = $.parseJSON(response);
                if (response.data) {
                    if (typeof (response.meta.selected_urls_count) !== 'undefined') {
                        $("#accordion_group_" + groupId).data("amount_sc", response.meta.selected_urls_count);
                        let groupEnabled = jQuery("#enabled" + groupId).val();
                        if (groupEnabled == true) {
                            $("#status-amount-webpages-group-" + groupId).text(response.meta.selected_urls_count);
                        } else {
                            $("#status-amount-webpages-group-" + groupId).text(0);
                        }
                    }
                    updateTotals();
                } else {
                    console.error('Adding or Removing URL ' + urlId + ' to Group ' + groupId + ' failed');

                    // Revert the status of the checkbox
                    if (checkbox.prop("checked")) {
                        checkbox.prop("checked", false);
                    } else {
                        checkbox.prop("checked", true);
                    }

                    updateGroupAndScPopup(groupId, deviceValue == 0 ? deviceValue : -1); // do the opposite

                    // Send the error message
                    show_message('error', 'Oooops! Something went wrong... Please try again.');
                }
            }).fail(() => console.error('AJAX call failed for select_group_url'));
        }

        function createFilterEventListener(groupUrlsContainer) { // filter urls table

            $(".search-url-table").on("submit", function (e) {
                e.preventDefault();
            });
        }

        /**
         * Remove and then recreate EventListeners for GroupUrl Checkboxes
         */
        function createGroupUrlCheckboxEventHandler() {
            // Remove possible EventListeners first
            removeEventListenerFromCss('group-url-checkbox', handleGroupUrlCheckbox)

            // Check / uncheck devices
            $(".group-url-checkbox").on("click", handleGroupUrlCheckbox);
        }

        /**
         * Generic function to remove EventListener from all elements of a CSS Class
         *
         * @param {string} cssClass
         * @param {function} functionName
         * @param {string} eventName
         */
        function removeEventListenerFromCss(cssClass, functionName, eventName = 'click') {
            // Remove EventListeners that the element might have already
            domElements = document.getElementsByClassName(cssClass);
            if (domElements && domElements.length > 0) {
                for (let domElement of domElements) {
                    domElement.removeEventListener(eventName, functionName);
                }
            }
        }

        /**
         * Save CSS from Popup
         *
         * @param {Event} e
         */
        function editCss(e) {
            e.preventDefault();
            saveCodeareas();
            $("#mm_loading").show();
            let newUrl = $(this).find("#show_css_popup_url").val();
            let oldUrl = $(this).find("#show_css_popup_url").data("url");
            let urlId = $(this).find("input[name='url_id']").val();
            let newHtmlTitle = $(this).find("#show_css_popup_html_title").val();
            let oldHtmlTitle = $(this).find("#show_css_popup_html_title").data("html_title");
            let groupId = $(this).find("input[name='group_id']").val();



            let data = {
                action: "update_url",
                group_id: groupId,
                url: newUrl,
                url_id: urlId,
                html_title: newHtmlTitle
            }

            $.post(ajaxurl, $(this).serialize(), function (response) {

                let updateRow = $("#" + groupId + "-" + urlId);
                updateRow.find(".url").html(newUrl);
                updateRow.find(".html-title").html(newHtmlTitle);
                $("#mm_loading").hide();
                $(".ajax-popup").hide();
                show_message("success", response);

                stopScrolling(false);
            });


        }

        $("form.ajax-reload-url-types").on("submit", function (e) {
            e.preventDefault();
            var form = $(this);
            var groupId = $(this).data("group_id");
            var output = '';
            var defaultChecked = $(this).data("selected_url_types");
            var checked = '';
            var hideElement = '';
            var skipUrlTypes = ['wpml_language'];
            var skipUrlCategories = ['blocks'];

            $('#mm_loading').show();
            $.post(ajaxurl, form.serialize(), function (response) {
                let urlDetails;

                response = JSON.parse(response);
                if (typeof response !== 'object') {
                    show_message('error', 'Oooops! Something went wrong... Please try again.');
                    $('#mm_loading').hide();
                    return false;
                }

                output += '<input type="hidden" name="sync_wp_urls" value="true">';

                $.each(response, function (key, urlTypeDetails) {
                    if ($.inArray(urlTypeDetails.url_type_slug, skipUrlTypes) !== -1) {
                        hideElement = "display: none;";
                    }
                    output += '<label style="' + hideElement + '">' + urlTypeDetails.url_type_name + '</label>';
                    $.each(urlTypeDetails.url_types, function (key2, postTypes) {
                        urlDetails = {
                            url_type_slug: urlTypeDetails.url_type_slug,
                            url_type_name: urlTypeDetails.url_type_name,
                            post_type_slug: postTypes.slug,
                            post_type_name: postTypes.name
                        };

                        // Check for selected posttypes
                        checked = '';
                        if ($.inArray(postTypes.slug, defaultChecked) >= 0 || urlTypeDetails.url_type_slug === "wpml_language") {
                            checked = 'checked';
                        }

                        if ($.inArray(postTypes.slug, skipUrlCategories) !== -1) {
                            return;
                        }

                        output += '<input style="' + hideElement + '" type="checkbox" ' +
                            'name="wp_api_' + urlTypeDetails.url_type_slug + '_' + postTypes.slug + '"' +
                            'value=\'' + JSON.stringify(urlDetails) + '\' ' + checked + '>' +
                            '<span  style="' + hideElement + '">' + postTypes.name + '</span>' +
                            '<br>';
                    });

                });
                $("#ajax-wp-group-settings" + groupId).html(output);
                $("#form_popup_wp_group_settings" + groupId).show();
                $("#selected-url-types" + groupId).hide();

                $('#mm_loading').hide();
            });
        });

        $("form.ajax-get-wp-urltypes").on("submit", function (e) {
            e.preventDefault();

            var form = $(this); // init
            $('#mm_loading').show();
            $('#error_available_post_types').hide();
            $('#available_post_types').hide();
            $('#wp-group-settings').hide();
            var domain = $('#add-wp-website-domain-input').val();

            $.post(ajaxurl, form.serialize(), function (response) {
                var output = '';
                var errOutput = '';
                var defaultChecked = ['posts', 'pages', 'projects', 'products', 'categories'];
                var checked;
                let urlDetails = {};
                let isLanguageType = false;
                let languageCheckboxStyle = '';

                $('#ajax-fill-group-name').val(domain);
                $('#ajax-fill-group-name').parent().hide();
                $('#add_wp_website_domain').val(domain);

                response = JSON.parse(response);

                if (typeof response == 'object') {
                    if (response.length === 0) {
                        errOutput += 'No URL types found';
                        $('#error_available_post_types').html(errOutput);
                        $('#error_available_post_types').show();
                    } else {
                        // Set this param to tell save action to sync urls too
                        $.each(response, function (index, details) {
                            if (details.url_type_slug !== 'wpml_language') {
                                output += '<label style="margin-top: 10px !important;">' + details.url_type_name + '</label>';
                            }
                            $.each(details.url_types, function (index2, postType) {

                                // Check the selected posttypes
                                checked = '';
                                isLanguageType = 0
                                languageCheckboxStyle = ''
                                if (details.url_type_slug === 'wpml_language') {
                                    isLanguageType = 1;
                                    languageCheckboxStyle = 'display: none;';
                                }
                                if ($.inArray(postType.slug, defaultChecked) >= 0 || isLanguageType === 1) {
                                    checked = 'checked';
                                }

                                urlDetails = {
                                    url_type_slug: details.url_type_slug,
                                    url_type_name: details.url_type_name,
                                    post_type_slug: postType.slug,
                                    post_type_name: postType.name
                                };
                                output += '<div style="' + languageCheckboxStyle + '"> <input  type="checkbox" name="wp_api_' + details.url_type_slug + '_' + postType.slug + '" value=\'' + JSON.stringify(urlDetails) + '\' ' + checked + ' > <span style="' + languageCheckboxStyle + '"> ' + postType.name + '</span></div>';
                            });
                        });
                        $('#available_post_types_list').html(output);
                        $('#available_post_types').show();
                        $('#wp-group-settings').show();
                    }
                } else {
                    show_message('error', response);
                }
                $('#mm_loading').hide();
            }).fail(function (xhr, status, error) {
                $(".ajax-popup").hide();
                $("#mm_loading").hide();

                show_message('error', 'Oooops! Something went wrong... Please try again.');
                stopScrolling(false);
                console.error(JSON.parse(xhr.responseText));
            });
        });

        function show_message(type, text) {
            if (!text.trim()) {
                return;
            }
            let random = (Math.random() + 1).toString(36).substring(7);
            if (text.startsWith('<div')) {
                text = $.parseHTML(text);
                $(text).attr('id', random);
                $("#success-message").append(text);
            } else {
                $("#success-message").append('<div id="' + random + '" class="message ' + type + '">' + text + '</div>');
            }
            let successMessage = $("#" + random);
            $(successMessage).animate({ "right": '+=410px' });

            setTimeout(function () {
                $(successMessage).animate({ "right": '-=500' }, 500, function () {
                    setTimeout(function () {
                        $(successMessage).slideUp();
                    }, 500);

                });
            }, 5000);
        }

        /**
         * Unassigns a URL from a Group
         *
         * @param {Event} e
         */
        function unassignUrl(e) {
            e.preventDefault();
            var urlId = $(this).data('url_id');
            var groupId = $(this).data('group_id');
            var url = $(this).data('url');

            // Confirm deleting URL
            var confirmation = confirm('Are you sure you want to remove the URL "' + url + '" from this group? ' +
                'Compares and url settings for this group won\'t be available anymore.');
            if (confirmation === false) {
                return;
            }

            $("#show_css_popup-" + groupId + "-" + urlId + " .close-popup-button").click();

            var data = {
                action: 'unassign_group_url',
                url_id: urlId,
                group_id: groupId
            };

            $.post(ajaxurl, data, function (response) {
                if (response) {
                    let removeRow = $('#' + groupId + '-' + urlId)
                    $(removeRow).fadeOut();
                    $(removeRow).replaceWith('');
                    show_message('success', response);

                    updateTotals();
                    let amountOfRows = $('#table_group_' + groupId + ' tr').length;
                    if (amountOfRows === EMPTY_GROUP_LENGTH) {
                        $('#table_group_' + groupId + ' tr').remove(); // remove the last 2 rows

                        // Add note
                        let html = '<div style="text-align: center; display: block;margin-top: 50px; margin-bottom: 50px">' +
                            '<p class="add-url">Add URL</p>' +
                            '<div class="ajax" data-group_id="' + groupId + '" onclick="showAssignGroupUrlPopup(' + groupId + ');">' +
                            '<span class="icon-big dashicons dashicons-welcome-add-page"></span>' +
                            '</div>';
                        $('#table_group_' + groupId).after(html);
                    }
                }
            });
        }

        /**
         * Currently Processing
         */
        function currentlyProcessing() {
            var currentlyProcessing = $('#currently-processing');
            var updateCurrentlyProcessing = $('#update-currently-processing');
            var updateProcessingContainer = $('#wcd-currently-in-progress');
            var batchId = updateProcessingContainer.data('batch_id') ?? false;
            //var currentlyProcessingSpinner = $('#currently-processing-spinner');

            // Only show currently processing if there is something to process and check every 10 sec then
            const currentlyProcessingValue = currentlyProcessing.html();
            const parsedValue = parseInt(currentlyProcessingValue);
            const isString = typeof currentlyProcessingValue === 'string' && isNaN(parsedValue);

            if (isString || parsedValue > 0) {
                currentlyProcessing.show();
            }
            if (isString || parsedValue >= 0) {
                updateCurrentlyProcessing.show();

                function updateDetectionRefresh() {
                    // Updating in manual checks process
                    if (batchId) {
                        var dataUpdateProcess = {
                            action: 'get_batch_processing_status',
                            batch_id: batchId
                        };
                        console.log('sending batch get_batch_processing_status');
                        console.log(batchId);
                        $.post(ajaxurl, dataUpdateProcess, function (response) {

                            console.log('received batch get_batch_processing_status');
                            console.log(response);

                            var statusData = JSON.parse(response);
                            var openAndProcessing = statusData.open_processing || 0;
                            var processedItems = statusData.processed || 0;

                            // Update the counter with open and processing items
                            updateCurrentlyProcessing.html(openAndProcessing);

                            // Consider processing complete when:
                            // 1. No items are open or processing AND
                            // 2. We have at least some processed items (done or failed)
                            if (openAndProcessing === 0 && processedItems > 0) {

                                // Reset the html to loading for the next run
                                updateCurrentlyProcessing.html('<span style="font-size: 14px;">Loading...</span>');

                                // Reset the batch id so don't have 0 processing in the next run
                                updateProcessingContainer.data('batch_id', false);
                                $("#wcd-screenshots-done").show();
                                $("#currently-processing-loader").hide();

                                // Proceed to next step
                                $("#manual_checks_next_step").submit();
                                clearInterval(processingInterval);
                            }
                        });
                    }

                    // Updating in global checks
                    // Disabled global checks for processing queue for now.
                    // var dataGlobal = {
                    //     action: 'get_processing_queue',
                    // };

                    // $.post(ajaxurl, dataGlobal, function (response) {
                    //     currentlyProcessing.html(response);
                    //     console.log('received global get_processing_queue');
                    //     console.log(response);
                    //     // If the queue is done, show all done for 10 sec
                    //     if (parseInt(response) === 0 || !response) {
                    //         currentlyProcessing.html('All done');
                    //         currentlyProcessing.addClass('done');

                    //         setTimeout(function() {
                    //             currentlyProcessing.fadeOut();
                    //         }, 10000);

                    //         // Stop the interval when everything is done.
                    //         clearInterval(processingInterval);
                    //     }
                    // });
                }
                updateDetectionRefresh();
                var processingInterval = setInterval(function () {
                    updateDetectionRefresh();
                }, 7000, currentlyProcessing)
            }
        }

        // This needs to instantly be executed. But we need to wait for the page to load first.
        setTimeout(function () {
            currentlyProcessing();
        }, 1000);

        // --------------------------------------------------------------------
        // Event Listeners
        // --------------------------------------------------------------------

        // General ajax request for forms with class ajax
        $("form.ajax").on("submit", function (e) {
            e.preventDefault();

            var form = $(this); // init
            saveCodeareas();
            $('#mm_loading').show();

            $.post(ajaxurl, form.serialize(), function (response) {
                // Reload the content
                refreshWcdPage(response);
                stopScrolling(false);

            }).fail(function (xhr, status, error) {
                $(".ajax-popup").hide();
                $("#mm_loading").hide();
                show_message('error', 'Oooops! Something went wrong... Please try again.');

                stopScrolling(false);
                console.error(JSON.parse(xhr.responseText));
            });
        });

        // Enhanced async form handler for background processing
        $("form.ajax-async").on("submit", function (e) {
            e.preventDefault();
            $('#mm_loading').show();
            var form = $(this);
            saveCodeareas();

            $.post(ajaxurl, form.serialize(), function (response) {
                console.log('Async form response:', response);
                try {
                    var result = JSON.parse(response);
                    console.log('Async form response:', result); // Debug log

                    if (result.success) {
                        // Show success message
                        show_message('success', result.message);
                        console.log('result.job_id', result.job_id);
                        console.log('result.domain', result.domain);

                        // Open new progress popup
                        if (result.job_id && result.domain) {
                            closeAddWpGroupPopup(0);
                            $('#mm_loading').hide();
                            openSyncProgressPopup(result.job_id, result.domain);
                        } else {
                            console.error('Missing job_id or domain in response:', result);
                            show_message('error', 'Missing job information in response');
                        }
                    } else {
                        show_message('error', result.message || 'Unknown error occurred');
                    }
                } catch (e) {
                    // Handle non-JSON responses (error messages)
                    if (response.includes('success')) {
                        show_message('success', response);
                        refreshWcdPage();
                    } else {
                        show_message('error', response);
                    }
                }
            }).fail(function (xhr, status, error) {
                $(".ajax-popup").hide();
                show_message('error', 'Something went wrong... Please try again.');
                console.error('AJAX Error:', xhr.responseText);
            });
        });

        // Switch accounts
        $(".ajax-switch-account").on("change", function () {
            switchAccount($(this).val());
        });

        $(".ajax-switch-account-button").on("click", function () {
            switchAccount($(this).data('id'));
            return false;
        });

        // Filter change detections
        $(".plainSelect li").on("click", function (e) {
            // Value of option transformed to ID
            let filterValue = $(this).attr('id');
            // Get key from parent <ul>
            let parentId = $(this).parent().attr('id');

            // should'nt happen
            if (!parentId) {
                console.error('Could not find filter key');
                return;
            }

            // e.g. count_days-ul, cutting out before "-"
            let filterKey = parentId.substr(0, parentId.indexOf('-'));

            if (!filterKey) {
                console.error('Could not find filter value');
                return;
            }

            // Build payload for jQuery AJAX
            let extraData = {
                [filterKey]: filterValue // evaluate filterKey, e.g. cms
            };

            // get potentially existing filters from sessionStorage
            let existingFilters = sessionStorage["wcd-filter"];

            let filterData = {}; // init

            if (existingFilters) {
                // parse object from sessionStorage
                filterData = JSON.parse(existingFilters);

                if (typeof filterData === 'object') {
                    // if there is already a filter, merge, potentially override previous filter
                    filterData = { ...filterData, ...extraData };
                } else {
                    filterData = extraData; // just use extraData to filter
                }
            } else {
                filterData = extraData; // init for no filter
            }

            // save filter for next time
            sessionStorage["wcd-filter"] = JSON.stringify(filterData);

            refreshWcdPage('', filterData);
            $("#mm_loading").show();
        });

        // Delete url fully
        $(".ajax_delete_url").on("click", function (e) {
            e.preventDefault();

            var urlId = $(this).data('url_id');
            var url = $(this).data('url');

            // Confirm deleting URL
            var confirmDeletion = confirm('Are you sure you want to delete the URL ' + url + '? Compares won\'t be available anymore.');
            if (confirmDeletion === false) {
                return;
            }

            var data = {
                action: 'delete_url',
                url_id: urlId
            };
            $.post(ajaxurl, data, function (response) {
                if (response) {
                    $('#url-row-' + urlId).fadeOut();
                    show_message('success', response);


                } else {
                    alert(' URL could not be deleted.');
                }
            });
        });

        // Assign group urls
        $("#btn-assign-url").on('click', function (e) {
            var groupId = $(this).data("group_id");
            var data = {
                action: 'get_unassigned_group_urls',
                group_id: groupId
            };
            $("#unassigned-group-urls").addClass("loading");
            $.post(ajaxurl, data, function (response) {
                $("#unassigned-group-urls").removeClass("loading");
                $("#unassigned-group-urls").html(response);
            });
        });

        // Delete group / website
        $(".delete_group_button").on('click', function (e) {
            e.preventDefault();
            var groupId = $(this).data("group_id");
            var websiteId = $(this).data("website_id");
            var domain = $(this).data("domain");
            var cms = $(this).data("cms");
            var data = {};
            if (!websiteId) {
                if (!confirm('Are you sure you want to delete this group?')) {
                    return false;
                }
                data = {
                    action: 'delete_group',
                    group_id: groupId
                };
            } else {
                if (!confirm('Are you sure you want to delete this website?')) {
                    return false;
                }
                data = {
                    action: 'delete_website',
                    domain: domain
                };
            }

            closeGroupSettingsPopup(groupId);
            $("#mm_loading").show();
            $.post(ajaxurl, data, function (response) {
                if (response) {
                    $("#mm_loading").hide();
                    if (response.includes('success')) {
                        $("#accordion_group_" + groupId).fadeOut();
                        setTimeout(function () {
                            $("#accordion_group_" + groupId).replaceWith('');
                            updateTotals();
                        }, 1000);
                    }
                }
                show_message('success', response);
            });
        });

        // Contact form
        $("#ajax-help-contact-form").on("submit", function () {
            var form = $(this);
            $.post(ajaxurl, form.serialize(), function (response) {
                $("#ajax-help-contact-form").replaceWith(
                    '<div ' +
                    'style="background: #05b405; border: 1px solid #fff; padding: 10px; margin-bottom: 10px;">' +
                    'Thank you for your feedback! ' +
                    '</div>'
                );
            });
        });

        /*
        Add new general group
         */
        $("#form_popup_group_settings0").on("submit", function (e) {
            e.preventDefault();
            var form = $(this);
            var urls = form.find('[name="url"]').val();
            var data = {
                action: 'check_url',
                url: urls, // could be multiple
            };

            if (typeof ((urls)) !== "undefined") {
                $("#add_url_popup_check_url").show();
                $.post(ajaxurl, data, function (response) {

                    $("#add_url_popup_check_url").hide();
                    if (response === "0") {
                        show_message('error', 'Please check your URLs');
                        return false;
                    }

                    $('#mm_loading').show();

                });
            }
            $('#mm_loading').show();
            $.post(ajaxurl, form.serialize(), function (response) {
                // Reload the content
                refreshWcdPage(response);
                stopScrolling(false);
            });
        });

        // Enable / disable monitoring
        $(".ajax_enable_monitoring").on("click", function (e) {
            var accordion = $(this).closest(".accordion-container");
            var totalScContainer = $("#ajax_amount_total_sc");
            var nextScInContainer = $("#next_sc_in");
            var nextScDateContainer = $("#next_sc_date");
            var amountSelectedUrls = amountAutoUrls();
            var groupId = $(this).data('group_id');
            var groupSelectedUrls = $("#accordion_group_" + groupId).data("amount_sc");
            var enabled = 0;
            var activeAuto = $(this).children("span");

            if (activeAuto.hasClass("active-auto")) {
                activeAuto.removeClass("active-auto");
                activeAuto.addClass("paused-auto");
                activeAuto.removeClass("dashicons-controls-play");
                activeAuto.addClass("dashicons-controls-pause");
                jQuery(accordion).data("auto_enabled", 0);
                accordion.attr("data-auto_enabled", 0);  // Update HTML data attribute
                accordion.removeClass("enabled");
                //accordion.css("background","#ff000017");
            } else {
                if (groupSelectedUrls + amountSelectedUrls > MM_FREE_PLAN_MAX_URLS && currentPlanId === MM_FREE_PLAN_ID) {
                    showPlanLimitationPopup();
                    return false;
                }

                activeAuto.removeClass("paused-auto");
                activeAuto.addClass("active-auto");
                activeAuto.removeClass("dashicons-controls-pause");
                activeAuto.addClass("dashicons-controls-play");
                jQuery(accordion).data("auto_enabled", 1);
                accordion.attr("data-auto_enabled", 1);  // Update HTML data attribute
                accordion.addClass("enabled");
                enabled = 1;
                //accordion.css("background","#0094002e");
            }

            var data = {
                action: 'save_group_settings',
                group_id: groupId,
                monitoring: 1,
                enabled: enabled,
            };
            totalScContainer.text("");
            totalScContainer.addClass("loading");
            nextScDateContainer.text("");
            nextScDateContainer.addClass("loading");
            nextScInContainer.text("");

            var enableSwitch = $(".auto-enabled[data-group_id=" + groupId + "]");
            enableSwitch.find(".enabled-description").removeClass("enabled").addClass("disabled").html("Monitoring<br><strong>Off</strong>");
            enableSwitch.find("#hover-enable").html("<strong>Start</strong><br>Monitoring");
            if (enabled) {
                enableSwitch.find(".enabled-description").removeClass("disabled").addClass("enabled").html("Monitoring<br><strong>Active</strong>");
                enableSwitch.find("#hover-enable").html("<strong>Stop</strong><br>Monitoring");
            }
            $.post(ajaxurl, data, function () {
                // Update the enabled hidden field in the group  settings
                $("#enabled" + groupId).val(enabled);

                totalScContainer.removeClass("loading");
                nextScDateContainer.removeClass("loading");
                if (enabled) {
                    $("#status-amount-webpages-group-" + groupId).text(accordion.data("amount_sc"));
                } else {
                    $("#status-amount-webpages-group-" + groupId).text(0);
                }
                updateTotals();
            });
        });

        // Update Enabled Status and recalculate requried change detections
        $(".ajax_enable_group").on("click", function (e) {
            var accordion = $(this).closest(".accordion-container");
            var enabled = "0";
            var groupId = $(this).data('group_id');
            var selected_urls_count = accordion.data("amount_sc");

            if ($(this).is(":checked")) {
                enabled = 1;
                //accordion.css("background","#0094002e");
                $(accordion).data("manual_enabled", 1);
                accordion.attr("data-manual_enabled", 1);  // Update HTML data attribute
                $("#status-amount-webpages-group-" + groupId).text(selected_urls_count);
            } else {
                //accordion.css("background","#ff000017");
                $(accordion).data("manual_enabled", 0);
                accordion.attr("data-manual_enabled", 0);  // Update HTML data attribute
                $("#status-amount-webpages-group-" + groupId).text("0");
            }

            var data = {
                action: 'save_group_settings',
                group_id: groupId,
                enabled: enabled,
            };

            $("#ajax_amount_total_sc").html("");
            $("#ajax_amount_total_sc").addClass("loading");
            // Update the enabled hidden field in the group settings
            $("#enabled" + groupId).val(enabled);
            $.post(ajaxurl, data, function () {
                // Update totals
                updateTotals();
            });

        });

        $(".accordion-ajax-group-urls").on('click', function () {
            updateGroups($(this))
        });


        // Open group if only one exists

        /*if( $(".accordion.url-list").length === 1 ) {
            $(".accordion-ajax-group-urls").click();
        }*/

        // --------------------------------------------------------------------
        // TODO Commented out
        // --------------------------------------------------------------------

        $("form.ajax-filter").on("submit", function (e) {
            // Not working yet as we have to give the get params for the filters which is not supported by refreshWcdPage() yet.
            e.preventDefault();
            $("#mm_loading").show();
            let formFields = $(this).serialize();
            $.post(ajaxurl, formFields, function (response) {
                $('#change-detection-batches').replaceWith(response);
                $.fn.loadJs();
                $("#mm_loading").hide();
            });
        });

        /*$("#pre-sc-popup").on('click', function(e) {

            var groupIds = $('#pre-sc-group-ids').val();
            //var groupIdArr = groupIds.split(',');
            var groupId;
            var groupScs;
            var groupName;
            var totalSc = $("#ajax_amount_total_sc").html();

            $(".ajax_enable_group").each(function() {
                if($(this).is(":checked")) {
                    groupId = $(this).data("group_id");
                    groupScs = $(this).data("amount_sc");
                    groupName = $("#group_name-"+groupId).html();
                    $("#sc-groups-and-urls").append(groupName + ": " + groupScs + " Screenshots<br>")
                }
            });
            $("#sc-groups-and-urls").append("<br><br><strong>Total: " + totalSc + " Screenshots</strong><br>")
            var cms = $('#pre-sc-cms').val();
            var data = {
                action: 'get_sc_groups_and_urls',
                group_ids: groupIds,
                cms: cms
            };
            $("#sc-groups-and-urls").addClass("loading");
            $.post(ajaxurl, data, function(response ) {
                $("#sc-groups-and-urls").removeClass("loading");
                $("#sc-groups-and-urls").html(response);
            });
        });*/


        if ($(".accordion-ajax-group-urls").length === 1) {
            $(".accordion-ajax-group-urls").click();
        }

        /**
         * Open sync progress popup and start monitoring
         */
        function openSyncProgressPopup(jobId, domain) {
            // Show the popup (now included in PHP)
            showSyncProgressPopup();

            // Reset progress and start polling
            $('#sync-progress-bar').css('width', '0%').css('background', '#4CAF50');
            $('#sync-progress-text').text('Starting synchronization...');

            // Start polling
            const pollInterval = setInterval(() => {
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'check_sync_job_status',
                        job_id: jobId
                    },
                    success: function (response) {
                        if (response.success) {
                            const job = response.data;
                            const progress = job.progress || 0;
                            $('#sync-progress-bar').css('width', progress + '%');

                            // Use error_message field for status messages
                            let statusText = job.error_message || 'Processing...';
                            if (!job.error_message) {
                                if (progress < 10) statusText = 'Starting...';
                                else if (progress < 20) statusText = 'Setting up website...';
                                else if (progress < 50) statusText = 'Fetching URLs from WordPress...';
                                else if (progress < 90) statusText = 'Synchronizing URLs...';
                                else statusText = 'Finalizing...';
                            }
                            if (job.total_urls && job.total_urls > 0) {
                                statusText += ` (${job.total_urls} URLs found)`;
                            }
                            $('#sync-progress-text').text(statusText);

                            if (job.status === 'completed') {
                                clearInterval(pollInterval);
                                $('#sync-progress-bar').css('width', '100%');
                                $('#sync-progress-text').text('Sync completed successfully!');
                                setTimeout(() => {
                                    closeSyncProgressPopup();
                                    show_message('success', `Website ${domain} has been added and all URLs synchronized!`);
                                    refreshWcdPage();
                                }, 2000);

                            } else if (job.status === 'failed') {
                                clearInterval(pollInterval);
                                $('#sync-progress-bar').css('background', '#dc3232');
                                $('#sync-progress-text').text('Sync failed: ' + (job.error_message || 'Unknown error'));
                                setTimeout(() => {
                                    closeSyncProgressPopup();
                                    show_message('error', 'URL synchronization failed: ' + (job.error_message || 'Unknown error'));
                                }, 3000);
                            }
                        } else {
                            clearInterval(pollInterval);
                            $('#sync-progress-bar').css('background', '#dc3232');
                            $('#sync-progress-text').text('Could not check sync status');
                            setTimeout(() => {
                                closeSyncProgressPopup();
                                show_message('error', 'Could not check sync status');
                            }, 3000);
                        }
                    },
                    error: function () {
                        clearInterval(pollInterval);
                        $('#sync-progress-bar').css('background', '#dc3232');
                        $('#sync-progress-text').text('Network error while checking sync status');
                        setTimeout(() => {
                            closeSyncProgressPopup();
                            show_message('error', 'Network error while checking sync status');
                        }, 3000);
                    }
                });
            }, 2000); // Poll every 2 seconds
        }
    }
})(jQuery); // instantly executed

/**
 * Toggle failed queues accordion and load content via AJAX.
 * 
 * @param {HTMLElement} clickedElement The clicked h3 element.
 * @param {string} batchId The batch ID to load failed queues for.
 */
function toggleFailedQueues(clickedElement, batchId) {
    // Find the specific elements within this accordion
    const accordionTitle = clickedElement; // The h3 element
    const content = accordionTitle.parentElement.querySelector('.failed-queues-content');
    const arrow = accordionTitle.querySelector('.accordion-arrow');
    const tableContainer = accordionTitle.parentElement.querySelector('.failed-queues-table-container');
    const loading = accordionTitle.parentElement.querySelector('.failed-queues-loading');

    const $content = jQuery(content);

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
            jQuery.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'load_failed_queues',
                    batch_id: batchId
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
