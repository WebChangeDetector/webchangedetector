const MM_BG_COLOR_DARK_GREEN = '#006400';

function updateProcessingStep() {
    (function($) {
        //currentlyProcessingSpinner.show();
        var currentlyProcessingTable = $('#currently-processing-table');
        var currentlyProcessingSpinner = $('#currently-processing-spinner');
        var currentlyProcessing = $('#currently-processing');

        var data = {
            action: 'get_processing_queue'
        };

        $.post(ajaxurl, data, function (response) {

            response = JSON.parse(response);

            // Calculate all
            let currentlyInQueueAmount = response.open.meta.total + response.processing.meta.total;
            let currentlyProcessingSc = [];
            let actuallyProcessingSc = [];

            currentlyProcessing.html(currentlyInQueueAmount);

            // Get the processing queues in the plugin
            $('.processing_sc_row').each(function () {
                currentlyProcessingSc.push($(this).data('id'));
            });

            // Get the actually processing queues
            $(response.processing.data).each(function (i) {
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
            $(response.processing.data).each(function () {
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
    (function($) {
        var currentlyProcessing = $('#currently-processing');
        let processingInterval;

        // Only show currently processing if there is something to process and check every 10 sec then
        if (currentlyProcessing && parseInt(currentlyProcessing.html()) > 0) {
            let totalSc = parseInt(currentlyProcessing.html());
            updateProcessingStep()
            processingInterval = setInterval(function() {
                updateProcessingStep(currentlyProcessing);
            }, 5000, currentlyProcessing)
        } else {
            $("#wcd-screenshots-done").show();
            if($(processingInterval).length) {
                clearInterval(processingInterval);
            }
        }
    })(jQuery)
}

(function( $ ) {
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
        if(parseFloat(percent) === 0.0) {
            // Dark green
            return MM_BG_COLOR_DARK_GREEN;
        }
        var pct =  1 - (percent / 100);

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

    $( document ).ready(function() {

        // Filter URL tables
        let filterTables = $(".group_urls_container");
        $.each(filterTables, function(i,e) {
            $(e).find(".filter-url-table").on("keyup", function () {
                var value = $(this).val().toLowerCase();
                $(e).find('table tr.live-filter-row').filter(function () {
                    $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1)
                });
            });
        });

        $(".codearea").each(function(index, item) {
            wp.codeEditor.initialize(item);
        });

        // Init accordions
        $(".accordion").each(function(index, item) {
            $(item).accordion({
                header: "h3",
                collapsible: true,
                active: false,
                icons: {
                    "header": "dashicons dashicons-plus",
                    "activeHeader": "dashicons dashicons-minus"
                },
            });
        });

        // Confirm message on leaving without saving form
        let formModified = 0;
        $('form.wcd-frm-settings').change(function(){
            formModified=1;
        });
        window.onbeforeunload = confirmExit;

        function confirmExit() {
            if (formModified === 1) {
                return "Changes were not save. Do you wish to leave the page without saving?";
            }
        }

        $("button[type='submit']").click(function() {
            formModified = 0;
        });

        // Confirm deleting account
        $('#delete-account').submit(function(){
            return confirm( "Are you sure you want to reset your account? This cannot be undone.");
        });

        // Confirm copy url settings
        $("#copy-url-settings").submit(function() {
            let type = $("#copy-url-settings").data("to_group_type");
            return confirm( "Are you sure you want to overwrite the " + type + " detection settings? This cannot be undone.");
        });

        // Confirm taking pre screenshots
        $('#frm-take-pre-sc').submit(function() {
            return confirm( "Please confirm taking pre-update screenshots.");
        });

        // Confirm taking post screenshots
        $('#frm-take-post-sc').submit(function() {
            return confirm( "Please confirm to create change detections.");
        });

        // Confirm cancel manual checks
        $('#frm-cancel-update-detection').submit(function() {
            return confirm( "Are you sure you want to cancel the manual checks?");
        });

        // Change bg color of comparison percentages
        var diffTile = $(".comparison-diff-tile");
        var bgColor = getDifferenceBgColor(diffTile.data("diff_percent"));
        diffTile.css("background", bgColor);

        // Background color differences
        $(".diff-tile").each( function() {
            var diffPercent = $(this).data("diff_percent");
            if( diffPercent > 0 ) {
                var bgColor = getDifferenceBgColor($(this).data("diff_percent"));
                $(this).css("background", bgColor);
            }
        });

        $("#diff-container").twentytwenty();

        $("#diff-container .comp-img").load( function() {
            $("#diff-container").twentytwenty();
        });

        $(".selected-urls").each(function(index, item) {
            var postType = $(item).data("post_type");
            var selectedDesktop = ($(item).data("amount_selected_desktop"));
            var selectedMobile = ($(item).data("amount_selected_mobile"));
            $("#selected-desktop-"+postType).html(selectedDesktop);
            $("#selected-mobile-"+postType).html(selectedMobile);
        });

        // Show local time in dropdowns
        var localDate = new Date();
        var timeDiff = localDate.getTimezoneOffset() / 60;

        $(".select-time").each( function(i, e) {
            let utcHour = parseInt($(this).val());
            let newDate = localDate.setHours(utcHour - timeDiff, 0);
            let localHour = new Date(newDate);
            let options = {
                hour: '2-digit',
                minute: '2-digit'
            };
            $(this).html(localHour.toLocaleString(navigator.language, options));
        });

        // Replace time with local time
        $(".local-time").each( function(i,e) {
            if($(this).data("date")) {
                $(this).text(getLocalDateTime($(this).data("date")));
            }
        });

        // Replace date with local date
        $(".local-date").each( function(i,e) {
            if($(this).data("date")) {
                $(this).text(getLocalDate($(this).data("date")));
            }
        });

        // Set time until next screenshots
        let autoEnabled = false;
        if($("#auto-enabled").is(':checked')) {
            autoEnabled = true;
        }
        let txtNextScIn = "No trackings active";
        let nextScIn;
        let nextScDate = $("#next_sc_date").data("date");
        let amountSelectedTotal = $("#sc_available_until_renew").data("amount_selected_urls");

        $("#txt_next_sc_in").html("Currently");
        $("#next_sc_date").html("");

        if(nextScDate && autoEnabled && amountSelectedTotal > 0) {
            let now = new Date($.now()); // summer/winter - time
            nextScIn = new Date(nextScDate * 1000); // format time
            nextScIn = new Date(nextScIn - now); // normal time
            nextScIn.setHours(nextScIn.getHours() + (nextScIn.getTimezoneOffset() / 60)); // add timezone offset to normal time
            var minutes = nextScIn.getMinutes() == 1 ? " Minute " : " Minutes ";
            var hours = nextScIn.getHours() == 1 ? " Hour " : " Hours ";
            txtNextScIn = nextScIn.getHours() + hours + nextScIn.getMinutes() + minutes;
            $("#next_sc_date").html(getLocalDateTime(nextScDate));
            $("#txt_next_sc_in").html("Next monitoring checks in ");
        }
        $("#next_sc_in").html(txtNextScIn);

        var scUsage = $("#wcd_account_details").data("sc_usage");
        var scLimit = $("#wcd_account_details").data("sc_limit");
        var availableCredits = scLimit - scUsage;
        var scPerUrlUntilRenew = $("#sc_available_until_renew").data("auto_sc_per_url_until_renewal");

        if(availableCredits <= 0) {
            $("#next_sc_in").html("Not Tracking").css("color","#A00000");
            $("#next_sc_date").html("<span style='color: #a00000'>You ran out of screenshots.</span><br>");
        }

        // Calculate total auto sc until renewal
        amountSelectedTotal += amountSelectedTotal * scPerUrlUntilRenew;

        // Update total credits on top of page
        $("#ajax_amount_total_sc").html("0");
        if(amountSelectedTotal && autoEnabled) {
            $("#ajax_amount_total_sc").html(amountSelectedTotal);
        }

        if( amountSelectedTotal > availableCredits) {
            $("#sc_until_renew").addClass("exceeding");
            $("#sc_available_until_renew").addClass("exceeding");
        }

        /**********
         * AJAX
         *********/

        // This needs to instantly be executed
        currentlyProcessing();

        $(".ajax_update_comparison_status").click(function() {
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

            $.post(ajaxurl, data, function (response) {
                if('failed' === response) {
                    $(statusElement).html(initialStatusContent);
                    alert('Something went wrong. Please try again.');
                    return false;
                }

                let status_nice_name;
                if( 'ok' === response) {
                    status_nice_name = 'Ok';
                } else if ('to_fix' === response) {
                    status_nice_name = 'To Fix';
                } else if ('false_positive' === response) {
                    status_nice_name = 'False Positive';
                }
                $(e).parent().parent().find(".current_comparison_status").html(status_nice_name);
                $(e).parent().parent().find(".current_comparison_status").removeClass("comparison_status_new");
                $(e).parent().parent().find(".current_comparison_status").removeClass("comparison_status_ok");
                $(e).parent().parent().find(".current_comparison_status").removeClass("comparison_status_to_fix");
                $(e).parent().parent().find(".current_comparison_status").removeClass("comparison_status_false_positive");
                $(e).parent().parent().find(".current_comparison_status").addClass("comparison_status_"+response);
            });

        })
    });
})( jQuery );

function postUrl(postId) {
    let groupId = document.getElementsByName('group_id')[0]
    let data;
    if(postId.startsWith('select')) {
        const selectAllCheckbox = jQuery('#'+postId);
        const type = selectAllCheckbox.data('type');
        const screensize = selectAllCheckbox.data('screensize');

         data = {
            action: 'post_url',
            nonce: jQuery(selectAllCheckbox).data('nonce'),
            group_id:  groupId.value,
        }

        let posts = jQuery("td.checkbox-"+screensize+"-"+type+" input[type='checkbox']");

        jQuery(posts).each(function() {
            data = { ...data, [screensize+"-"+jQuery(this).data('url_id')]: this.checked ? 1 : 0 };
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

    jQuery.post(ajaxurl, data, function (response) {
      // nothing to return.
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
function mmToggle(source, postType, column, groupId) {
    var checkboxes = document.querySelectorAll('.checkbox-' + column + '-' + postType + ' input[type=\"checkbox\"]');
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
function wcdValidateFormManualSettings() {

    // Early return if auto update checks are disabled.
    var autoUpdateChecksEnabled = document.getElementById("auto_update_checks_enabled").checked;
    if(!autoUpdateChecksEnabled) {
        return true;
    }

    // Validation from and to time.
    var from = document.getElementById("auto_update_checks_from");
    var to = document.getElementById("auto_update_checks_to");

    if(to.value < from.value) {
        jQuery(from).css("border", "2px solid #d63638");
        jQuery(to).css("border", "2px solid #d63638");
        jQuery("#error-from-to-validation").css("display", "block");
        from.scrollIntoView({
            behavior: 'smooth'
        });
        return false;
    }

    // Validation weekday.
    var weekdayContainer = document.getElementById('auto_update_checks_weekday_container');
    if(
        ! document.getElementsByName("auto_update_checks_monday")[0].checked &&
        ! document.getElementsByName("auto_update_checks_tuesday")[0].checked &&
        ! document.getElementsByName("auto_update_checks_wednesday")[0].checked &&
        ! document.getElementsByName("auto_update_checks_thursday")[0].checked &&
        ! document.getElementsByName("auto_update_checks_friday")[0].checked &&
        ! document.getElementsByName("auto_update_checks_saturday")[0].checked &&
        ! document.getElementsByName("auto_update_checks_sunday")[0].checked
    ) {
        jQuery(weekdayContainer).css("border", "2px solid #d63638");
        jQuery("#error-on-days-validation").css("display", "block");
        weekdayContainer.scrollIntoView({
            behavior: 'smooth'
        });
        return false;
    }

    // Validation Notification emails

    // get all emails.
    var emailsElement = document.getElementsByName("auto_update_checks_emails")[0];
    if(emailsElement.value !== "" ) {
        // split by comma.
        let emails = emailsElement.value.split(",");

        // Validation failed.
        if (false === validateEmail(emails)) {
            jQuery(emailsElement).css("border", "2px solid red");
            jQuery("#manual_checks_settings_accordion").css("border", "2px solid red");
            jQuery("#error-email-validation").css("display", "block");
            emailsElement.scrollIntoView({behavior: "smooth"});
            return false;
        }

        // Validation succeeded.
        jQuery("#error-email-validation").css("display", "none");
        jQuery("#accordion-auto-detection-settings").css("border", "1px solid #276ECC");
        jQuery(emailsElement).css("border", "2px solid green");
    }
    return true;

}

/**
 * Validates comma separated emails in a form
 * Called `onsubmit=` in HTML
 */
function wcdValidateFormAutoSettings() {

    // Check if monitoring is enabled.
    if( 'on' !== document.getElementById("auto-enabled").value ) {
        return true;
    }

    // get all emails.
    var emailsElement = document.getElementById("alert_emails");

    // split by comma.
    let emails = emailsElement.value.split(",");

    // Validate emails if it's filled.
    if(emailsElement.value !== "" ) {
        if (false === validateEmail(emails)) {
            // Validation failed.
            jQuery(emailsElement).css("border", "2px solid red");
            jQuery("#accordion-auto-detection-settings").css("border", "2px solid red");
            jQuery("#error-email-validation").css("display", "block");
            emailsElement.scrollIntoView({behavior: "smooth"});
            return false;

        }
        // Validation succeeded.
        jQuery("#error-email-validation").css("display", "none");
        jQuery("#accordion-auto-detection-settings").css("border", "1px solid #276ECC");
        jQuery(emailsElement).css("border", "2px solid green");
    }
    return true;
}

function validateEmail(emails) {
    // init email regex
    var emailRegex = /^(([^<>()[\]\\.,;:\s@\"]+(\.[^<>()[\]\\.,;:\s@\"]+)*)|(\".+\"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/;

    for (var i = 0; i < emails.length; i++) {
        emails[i] = emails[i].trim();

        // Validation failed
        if(emails[i] === "" || ! emailRegex.test(emails[i]) ){
            return false;
        }
    }
    return true;
}

function showUpdates() {
    jQuery("#updates").toggle("slow");
}

