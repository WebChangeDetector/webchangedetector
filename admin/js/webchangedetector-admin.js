const MM_BG_COLOR_DARK_GREEN = '#006400';

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

        // Enable / disable settings for monitoring
        showAutoSettings();
        $("#diff-container").twentytwenty();

        $("#diff-container .comp-img").load( function() {
            $("#diff-container").twentytwenty();
        });


        $("#auto-enabled").change(function() {
            showAutoSettings();
        });

        function showAutoSettings() {
            const enabledSelect = $("#auto-enabled");
            if(enabledSelect.val() === "0") {
                $(".auto-setting").hide();
                return;
            }
            $(".auto-setting").show();
        }
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
        var autoEnabled = parseInt($("#auto-enabled").val());
        var txtNextScIn = "No trackings active";
        var nextScIn;
        var nextScDate = $("#next_sc_date").data("date");
        var amountSelectedTotal = $("#sc_available_until_renew").data("amount_selected_urls");

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
            $("#txt_next_sc_in").html("Next change detections in ");
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
        function currentlyProcessing() {
            var currentlyProcessing = $('#currently-processing');
            var currentlyProcessingContainer = $('#currently-processing-container');
            var currentlyProcessingSpinner = $('#currently-processing-spinner');

            // Only show currently processing if there is something to process and check every 10 sec then
            if (currentlyProcessing && parseInt(currentlyProcessing.html()) > 0) {
                let totalSc = parseInt(currentlyProcessing.html());
                var processingInterval = setInterval(function() {

                    //currentlyProcessingSpinner.show();
                    var data = {
                        action: 'get_processing_queue'
                    };

                    $.post(ajaxurl, data, function (response) {
                        currentlyProcessing.html(response);

                        // If the queue is done, show all done for 10 sec
                        if (parseInt(response) === 0 || !response) {
                            currentlyProcessingSpinner.hide(); // hide spinner

                            // Replace message when everything is done
                            $("#wcd-currently-in-progress").hide();
                            $("#wcd-screenshots-done").show();
                            // Stop the interval when everything is done.
                            clearInterval(processingInterval);
                        }
                    });
                }, 3000, currentlyProcessing)
            } else {
                $("#wcd-screenshots-done").show();
            }
        }
        // This needs to instantly be executed
        currentlyProcessing();


        $(".ajax_update_comparison_status").click(function() {
            let e = $(this);
            let status = $(this).data('status');
            var data = {
                action: 'update_comparison_status',
                nonce: $(this).data('nonce'),
                id: $(this).data('id'),
                status: status
            };

            $.post(ajaxurl, data, function (response) {

                    console.log("ajax response: "+response)

               let status_nice_name;
                if( 'ok' === status) {
                    status_nice_name = 'Ok';
                } else if ('to_fix' === status) {
                    status_nice_name = 'To Fix';
                } else if ('false_positive' === status) {
                    status_nice_name = 'False Positive';
                }
                $(e).parent().parent().find(".current_comparison_status").html(status_nice_name);
                $(e).parent().parent().find(".current_comparison_status").removeClass("comparison_status_new");
                $(e).parent().parent().find(".current_comparison_status").removeClass("comparison_status_ok");
                $(e).parent().parent().find(".current_comparison_status").removeClass("comparison_status_to_fix");
                $(e).parent().parent().find(".current_comparison_status").removeClass("comparison_status_false_positive");
                $(e).parent().parent().find(".current_comparison_status").addClass("comparison_status_"+status);
            });

        })
    });

})( jQuery );

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
function wcdValidateFormAutoSettings() {

    // Check if monitoring is enabled.
    var autoDetectionEnabled = document.getElementById("auto-enabled").value;

    if(! parseInt(autoDetectionEnabled)) {
        return true;
    }
    // get all emails
    var emailsElement = document.getElementById("alert_emails");
    // split by new line
    let emails = emailsElement.value.replace(/\r\n/g,"\n").split("\n");
    // init email regex
    var emailRegex = /^(([^<>()[\]\\.,;:\s@\"]+(\.[^<>()[\]\\.,;:\s@\"]+)*)|(\".+\"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/;

    for (var i = 0; i < emails.length; i++) {
        emails[i] = emails[i].trim();

        // Validation failed
        if(emails[i] === "" || ! emailRegex.test(emails[i]) ){
            jQuery(emailsElement).css("border", "2px solid red");
            jQuery("#accordion-auto-detection-settings").css("border", "2px solid red");
            jQuery("#error-email-validation").css("display", "block");
            return false;
        }
    }
    jQuery("#error-email-validation").css("display", "none");
    jQuery("#accordion-auto-detection-settings").css("border", "1px solid #276ECC");
    jQuery(emailsElement).css("border", "2px solid green");
    return true;
}

function showUpdates() {
    jQuery("#updates").toggle("slow");
}

