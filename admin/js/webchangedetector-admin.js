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

        // this.style.background = 'rgb(' + [color.r, color.g, color.b].join(',');
        return 'rgb(' + [color.r, color.g, color.b].join(',') + ')';
    }

    $( document ).ready(function() {
        if($(".accordion").length) {
            $(".accordion").accordion({header: "h3", collapsible: true, active: false});
            $(".accordion").last().accordion("option", "icons", true);
        }

        // Confirm message on leaving without saving form
        let formModified=0;
        $('form.wcd-frm-settings').change(function(){
            formModified=1;
        });
        window.onbeforeunload = confirmExit;

        function confirmExit() {
            if (formModified === 1) {
                return "Changes were not save. Do you wish to leave the page without saving?";
            }
        }

        $("input[type='submit']").click(function() {
            formModified = 0;
        });

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

        // Enable / disable settings for auto change detection
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

        var timeDiff = localDate.getTimezoneOffset();
        timeDiff = (timeDiff / 60) ;
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
        $(".local-date").each( function() {
            if($(this).data("date")) {
                $(this).text(getLocalDate($(this).data("date")));
            }
        });

        // Set time until next screenshots
        var autoEnabled = parseInt($("#auto-enabled").val());
        var txtNextScIn = "No trackings active";
        var nextScIn = false;
        var nextScDate = $("#next_sc_date").data("date");
        $("#txt_next_sc_in").html("Currently");
        $("#next_sc_date").html("");

        if(nextScDate && autoEnabled) {
            nextScIn = new Date(nextScDate * 1000);
            nextScIn.setHours(nextScIn.getHours() + (nextScIn.getTimezoneOffset() / 60));
            nextScIn = new Date(nextScIn - $.now());
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
        var amountSelectedTotal = $("#sc_available_until_renew").data("amount_selected_urls");
        if(availableCredits <= 0) {
            $("#next_sc_in").html("Not Tracking").css("color","#A00000");
            $("#next_sc_date").html("<span style='color: #a00000'>You ran out of screenshots.</span><br>" +
                "<a href='" + upgradeUrl + "'>Upgrade plan</a> or wait for renewal");
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
function mmValidateForm() {
    // get all emails
    var emailsElement = document.getElementById("alert_emails");
    // split by comma
    let emails = emailsElement.value.replace(/\s/g,'').split(",");
    // init email regex
    var emailRegex = /^(([^<>()[\]\\.,;:\s@\"]+(\.[^<>()[\]\\.,;:\s@\"]+)*)|(\".+\"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/;
    // init border
    let border = '2px solid green';

    for (var i = 0; i < emails.length; i++) {
        if(emails[i] === "" || ! emailRegex.test(emails[i])){
            border = "2px solid red";
        }
    }
    // set border of `#alert_emails` element
    emailsElement.style.border = border;
    // see if border is still initialValue
    return border === '2px solid green';
}

function showUpdates() {
    jQuery("#updates").toggle("slow");
}

