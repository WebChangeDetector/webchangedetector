const MM_BG_COLOR_GREEN = 'rgba(23, 179, 49, 0.15)';
const MM_BG_COLOR_RED = 'rgba(220, 50, 50, 0.15)';
const MM_BG_COLOR_DARK_GREEN = '#006400';
const MM_BG_COLOR_LIGHT_GREEN = '#008900';
const MM_FREE_PLAN_MAX_URLS = 1;
const MM_FREE_PLAN_ID = 1;
const MM_TRIAL_PLAN_ID = 8;
const MM_FREE_PLAN = false;
let currentPlanId;
let upgradeUrl = "https://" + window.location.hostname + "/webchangedetector/?tab=upgrade";
const loadingIcon = "/wp-content/plugins/app/public/img/loading.gif";

(function ($) {
    'use strict';

    /**
     * All of the code for your public-facing JavaScript source
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

    $(document).ready(function () {
        currentPlanId = parseInt(jQuery("#current-plan").data("plan_id"));
        upgradeUrl += jQuery("#current-plan").data("service_id");
    });

    $('#box_action').click(function (event) {
        event.preventDefault();
    });
    /*$('#box_urls').click(function(event) {
        event.preventDefault();
    });*/
    $('#box_compares').click(function (event) {
        event.preventDefault();
    });

    $.fn.loadJs = function () {
        /*$(".img-magnifier-container.img-magnifier-comp-image").hover(function() {
            $('.img-magnifier-glass').show();
        }, function() {
            $('.img-magnifier-glass').hide();
        });*/

        // Accordion arrows
        jQuery(".mm_accordion_title h3").click(function() {
            let accordionTitle = jQuery(this);
            let degrees = 90;
            let icon = jQuery(accordionTitle).find(".accordion-state-icon");

            if(jQuery(accordionTitle).hasClass("ui-accordion-header-collapsed")) {
                jQuery(icon).css({'transform' : 'rotate('+ degrees +'deg)'});
            } else {
                jQuery(icon).css({'transform' : 'rotate('+ 0 +'deg)'});
            }
        });

        // Check for free account
        $(".interval_in_h").change(function () {
            if (currentPlanId === MM_FREE_PLAN_ID && $(this).val() < 24) {
                $(this).val(24);
                showPlanLimitationPopup();
            }
        });

        $("#mobile-nav").click(function () {
            $(".mm_side_navigation").animate({'left': '0'}, 200);
        });

        $("#close-mobile-nav").click(function () {
            $(".mm_side_navigation").animate({'left': '-100%'}, 200);
        });


        if ($(".accordion").length) {
            $(".accordion").accordion({
                heightStyle: "content",
                header: "h3",
                collapsible: true,
                active: true,

            });
            $(".accordion").last().accordion("option", "icons", true);
        }

        $("body").keydown(function (e) {
            //alert( e.which);
            if (e.which == 27) {
                $(".ajax-popup").fadeOut();
                stopScrolling(false);
            }
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

        // Replace time with local time
        $(".local-time").each(function (i, e) {
            if ($(this).data("date")) {
                $(this).text(getLocalDateTime($(this).data("date")));
            }
        });

        // Replace date with local date
        $(".local-date").each(function () {
            if ($(this).data("date")) {
                $(this).text(getLocalDate($(this).data("date")));
            }
        });

        $("#menu-item-general.inactive").hover(function () {
            $('#submenu-general').show(200, 'linear');
        }, function () {
            $('#submenu-general').hide(200, 'linear');
        });

        $("#menu-item-wordpress.inactive").hover(function () {
            $('#submenu-wordpress').show(200, 'linear');
        }, function () {
            $('#submenu-wordpress').hide(200, 'linear')
        });

        // Enable / disable settings for auto change detection
        if ($(".website-form")[0]) {
            $(".website-form").each(function () {
                showAutoSettings($(this));
            });
            $(".website-form").on("change", function () {
                showAutoSettings($(this));
            });
        }

        function showAutoSettings(form) {
            const limits = form.find(".enable_limits");
            if (limits.val() === "0") {
                form.find(".website-setting").hide();
            } else {
                form.find(".website-setting").show();
            }
        }

        // Make `<select>` to `<ul>`
        $.fn.selectUl = function () {
            var $origSelect = $(this);
            var newId = $(this).attr('name') + '-ul';
            var numOptions = $(this).children().length;

            $('<ul id="' + newId + '" class="plainSelect" />')
                .insertAfter($(this));

            var values = [];

            for (var i = 0; i < numOptions; i++) {
                var el = $(this).find('option').eq(i);
                var text = el.text();
                values.push(el.val());
                $('<li />').text(text).attr('id', el.val()).appendTo('#' + newId);
            }

            if ($(this).find('option:selected')) {
                var selected = $(this).find('option:selected').index();
                $('#' + newId)
                    .find('li')
                    .not(':eq(' + selected + ')')
                    .addClass('unselected');
            }

            $('#' + newId).hover(
                function () {
                    $(this).addClass('hover')
                },
                function () {
                    $(this).removeClass('hover')
                }
            );
            $('#' + newId + ' li')
                .hover(
                    function () {
                        $(this).click(
                            function () {
                                var newSelect = $(this).index();
                                $(this)
                                    .parent()
                                    .find('.unselected')
                                    .removeClass('unselected');
                                $(this)
                                    .parent()
                                    .find('li')
                                    .not(this)
                                    .addClass('unselected');
                                $($origSelect)
                                    .find('option:selected')
                                    .removeAttr('selected');
                                $($origSelect)
                                    .find('option:eq(' + newSelect + ')')
                                    .attr('selected', true);
                            });
                    },
                    function () {
                    });
            // assuming that you don't want the 'select' visible:
            $(this).hide();

            return $(this);
        };

        /*
        $('.js-dropdown').each(function () {
            e$(this).selectUl();
        });
         */

        // Background color differences
        $(".diff-tile").each(function () {
            var diffPercent = $(this).data("diff_percent");
            if (diffPercent > 0) {
                var bgColor = getDifferenceBgColor($(this).data("diff_percent"), $(this).data("threshold"));
                $(this).css("background", bgColor);
            }
        });

        //$("#diff-container .comp-img").load( function() {
        $("#diff-container").twentytwenty();
        // });
    };

    $(document).ready(function () {
        $.fn.loadJs();

    });
})(jQuery);

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

var editors = [];
function addEditorInstance(codeEditor, $element, config) {
    if (!$element || $element.length === 0) {
        return;
    }
    var instance = codeEditor.initialize($element, {
        codemirror: config
    });
    if (instance && instance.codemirror) {
        editors.push(instance.codemirror);
    }
}

var codeEditor = window.wp && window.wp.codeEditor;
var CodeAreaSettingsCss = {
    lineWrapping: true,
    mode: 'css',
    theme: "darcula",
    lineNumbers: true,
    codemirror: {
        theme: 'darcula',
        lineNumbers: true,
        type: 'text/css',
        lint: true,
        gutters: ["CodeMirror-lint-markers"],
    }
};
var CodeAreaSettingsJs = {
    lineWrapping: true,
    mode: 'javascript',
    theme: "darcula",
    lineNumbers: true,
    codemirror: {
        theme: 'darcula',
        lineNumbers: true,
        type: 'text/javascript',
        lint: true,
        gutters: ["CodeMirror-lint-markers"],
    }
};


function makeCodearea(popup) {
    var codeEditor = window.wp && window.wp.codeEditor;
    if (!popup.getElementsByClassName("CodeMirror").length) {
        let elements = popup.getElementsByClassName("codearea");
        for (var i = 0; i < elements.length; i++) {

            if (jQuery(elements[i]).hasClass('css')) {
                addEditorInstance(codeEditor, elements[i], CodeAreaSettingsCss);
            }
            if (jQuery(elements[i]).hasClass('js')) {
                addEditorInstance(codeEditor, elements[i], CodeAreaSettingsJs);
            }
        }
    }
}

function showHelp() {
    jQuery("#help-button").click();
}

function isEmailValid(email) {
    var regex = /^([a-zA-Z0-9_.+-])+\@(([a-zA-Z0-9-])+\.)+([a-zA-Z0-9]{2,4})+$/;
    return regex.test(email);
}

function isUrlValid(url) {
    var pattern = new RegExp('^(https?:\\/\\/)?' + // protocol
        '((([a-z\\d]([a-z\\d-]*[a-z\\d])*)\\.)+[a-z]{2,}|' + // domain name
        '((\\d{1,3}\\.){3}\\d{1,3}))' + // OR ip (v4) address
        '(\\:\\d+)?(\\/[-a-z\\d%_.~+]*)*' + // port and path
        '(\\?[;&a-z\\d%_.~+=-]*)?' + // query string
        '(\\#[-a-z\\d_]*)?$', 'i'); // fragment locator
    return !!pattern.test(url);
}

function amountAutoUrls() {
    var groupSelectedUrls = jQuery('span[id^="group-selected-"]');
    var totalSelectedUrls = 0;
    jQuery(groupSelectedUrls).each(function () {
        if (jQuery(this).closest(".accordion-container").data("auto_enabled") === 1) {
            totalSelectedUrls += parseInt(jQuery(this).text());
        }
    });
    return totalSelectedUrls;
}

function time_ago(time) {

    switch (typeof time) {
        case 'number':
            break;
        case 'string':
            time = +new Date(time);
            break;
        case 'object':
            if (time.constructor === Date) time = time.getTime();
            break;
        default:
            time = +new Date();
    }
    let time_formats = [
        [60, 'seconds', 1], // 60
        [120, '1 minute ago', '1 minute from now'], // 60*2
        [3600, 'minutes', 60], // 60*60, 60
        [7200, '1 hour ago', '1 hour from now'], // 60*60*2
        [86400, 'hours', 3600], // 60*60*24, 60*60
        [172800, 'Yesterday', 'Tomorrow'], // 60*60*24*2
        [604800, 'days', 86400], // 60*60*24*7, 60*60*24
        [1209600, 'Last week', 'Next week'], // 60*60*24*7*4*2
        [2419200, 'weeks', 604800], // 60*60*24*7*4, 60*60*24*7
        [4838400, 'Last month', 'Next month'], // 60*60*24*7*4*2
        [29030400, 'months', 2419200], // 60*60*24*7*4*12, 60*60*24*7*4
        [58060800, 'Last year', 'Next year'], // 60*60*24*7*4*12*2
        [2903040000, 'years', 29030400], // 60*60*24*7*4*12*100, 60*60*24*7*4*12
        [5806080000, 'Last century', 'Next century'], // 60*60*24*7*4*12*100*2
        [58060800000, 'centuries', 2903040000] // 60*60*24*7*4*12*100*20, 60*60*24*7*4*12*100
    ];
    let seconds = (+new Date() - time) / 1000,
        token = 'ago',
        list_choice = 1;

    if (seconds === 0) {
        return 'Just now'
    }
    if (seconds < 0) {
        seconds = Math.abs(seconds);
        token = 'from now';
        list_choice = 2;
    }
    let i = 0,
        format;
    while (format = time_formats[i++])
        if (seconds < format[0]) {
            if (typeof format[2] == 'string')
                return format[list_choice];
            else
                return Math.floor(seconds / format[2]) + ' ' + format[1] + ' ' + token;
        }
    return time;
}

/**
 * Check URL before adding - called by HTML in partial
 */
function checkUrlAndSubmitForm(e) {
    e.preventDefault();

    var totalSelectedUrls = amountAutoUrls();
    var groupId = jQuery("#add_url_group_id").val();
    var autoGroup = jQuery("#accordion_group_" + groupId).data("auto_group");
    var selectedUrls = 0;

    if (jQuery("#add_url_desktop").is(":checked")) {
        selectedUrls++;
    }
    if (jQuery("#add_url_mobile").is(":checked")) {
        selectedUrls++;
    }
    if (currentPlanId === MM_FREE_PLAN_ID && totalSelectedUrls + selectedUrls > MM_FREE_PLAN_MAX_URLS && autoGroup) {
        showPlanLimitationPopup();
        return false;
    }

    var urls = jQuery('#url').val();

    // Validate URL
    var delimiterPattern = /[,;\s\n]+/;
    urls = urls.split(delimiterPattern);

    // Filter out empty strings and trim URLs
    let urlsPreChecked = urls.filter(url => url.trim() !== "").map(url => url.trim());

    // Filter out empty array elements
    urlsPreChecked = urlsPreChecked.filter(item => item);

    // Validate each URL
    //var validUrlsFormatted = checkedUrls.filter(url => isValidUrl(url));

    // Display the valid URLs
    //$('#output').html("Valid URLs:<br>" + validUrlsFormatted.join("<br>"));

    /*
    var urlCheck = urls.replace("\r", '');

    // Remove empty lines
    urlCheck = urlCheck.split(/\r?\n/) // Split input text into an array of lines
        .filter(line => line.trim() !== "") // Filter out lines that are empty or contain only whitespace
        .join("\n");

    urlCheck = urlCheck.replace(",","\n");
    urlCheck = urlCheck.replace(";","\n");
    urlCheck = urlCheck.replace(" ","\n");

    //urlCheck = urlCheck.split("\n");
     urlCheck = jQuery.map(urlCheck.split("\n"), jQuery.trim);

    if(urlCheck[0] === '') {
        urlCheck = false;
    }*/

    //jQuery('#add_url_popup').hide();
    //jQuery('#add_url_popup_check_url').show();
    let checkPassed = true;
    let validUrls = [];
    let invalidUrls = [];
    let completedRequests = 0;
    let totalUrls = urlsPreChecked.length;

    let urlTextareaContainer = jQuery("#add-urls-textarea");
    let urlTextarea = jQuery("#add-urls-textarea textarea");
    let invalidUrlsTextarea = jQuery("#invalid_urls_textarea");
    let invalidUrlsContainer = jQuery("#invalid_urls");
    let validUrlsContainer = jQuery("#valid_urls");
    let validUrlsTextarea = jQuery("#valid_urls_textarea");
    let validUrlsContent = false;
    jQuery("#check_urls").html("");

    jQuery(urlTextarea).val('');
    jQuery(urlTextareaContainer).hide();
    jQuery("#check_urls_container").show();

    jQuery("#submit_form_add_url").attr("disabled", "disabled");

    if (urlsPreChecked.length === 0 && validUrlsTextarea.val()) {
        jQuery("#submit_form_add_url").attr("disabled", false);
        jQuery("textarea[name='url']").val(jQuery("textarea[name='valid_url']").val());
        jQuery('#form_add_url').submit();
        return;
    }

    let i = 0
    jQuery.each(urlsPreChecked, function (index, value) {
        i++;
        //jQuery('#assign_group_urls_popup #loading-check-urls').show();
        jQuery('#assign_group_urls_popup #check_urls').append("<div id='check-url-index-" + index + "'><span id='status-" + index + "' ><img src='" + loadingIcon + "'></span> " + value + " </div>");

        if (!value) {
            return; // equivaltent with continue
        }

        var data = {
            action: 'check_url',
            url: value,
        };

        // Regex URL Check
        if (!isUrlValid(value)) {
            jQuery("#invalid_urls_container").show();
            completedRequests++;
            invalidUrls.push(value);
            checkPassed = false;
            jQuery(invalidUrlsTextarea).val(jQuery(invalidUrlsTextarea).val() + value + "\n");
            //jQuery(invalidUrlsTextarea).append(jQuery(invalidUrlsTextarea).val() + value + "\n");
            //jQuery("#status-"+index).html('<span class="dashicons dashicons-no" style="color: red"></span> ');
            jQuery("#check-url-index-" + index).html("");
            jQuery("#invalid_urls").append('<div><span class="dashicons dashicons-no" style="color: red"></span>' + value + '</div>');

            if (completedRequests === totalUrls) {
                jQuery("#submit_form_add_url").attr("disabled", false);
                jQuery("#check_urls_container").hide();
                if (invalidUrls.length > 0) {
                    jQuery("#edit_failed_urls").css("display", "inline-block");
                }
            }
            return true; // like continue

        } else {
            // Actual header check
            setTimeout(function () {
                jQuery.ajax({
                    type: 'POST',
                    url: ajaxurl,
                    data: data,
                    timeout: 20000,
                    success: function (response) {

                        if (response !== "0") {
                            jQuery("#valid_urls_container").show();
                            jQuery("#valid_urls").append("<div>" + jQuery("#check-url-index-" + index).html() + "</div>"); // copy html to valid_urls
                            jQuery("#check_urls #check-url-index-" + index).html(""); // remove html from check-urls
                            jQuery("#valid_urls #status-" + index).html('<span class="dashicons dashicons-yes" style="color: green"></span>'); // set to done
                            validUrls.push(value);
                            validUrlsContent = jQuery(validUrlsTextarea).val();
                            if (validUrlsContent) {
                                validUrlsContent = validUrlsContent + "\n";
                            }
                            jQuery(validUrlsTextarea).val(validUrlsContent + value);
                            //jQuery('#add_url_popup_check_url').hide();
                        } else {
                            this.error(response);
                        }
                    },
                    error: function (err) {
                        jQuery("#invalid_urls_container").show();
                        //jQuery("#status-" + index).html('<span class="dashicons dashicons-no" style="color: red"></span> ');
                        jQuery("#check_urls #check-url-index-" + index).html(""); // remove html from check-urls
                        jQuery("#invalid_urls").append('<div><span class="dashicons dashicons-no" style="color: red"></span><a href="' + value + '" target="_blank">' + value + '</a></div>');
                        jQuery(invalidUrlsTextarea).val(jQuery(invalidUrlsTextarea).val() + value + "\n");
                        checkPassed = false;
                        invalidUrls.push(value);

                        if (completedRequests === totalUrls) {
                            if (invalidUrls.length > 0) {
                                jQuery("#edit_failed_urls").show();
                            }
                        }
                    },
                    complete: function () {
                        completedRequests++;

                        // After processing all URLs
                        if (completedRequests === totalUrls) {

                            jQuery("#submit_form_add_url").attr("disabled", false);
                            jQuery("#check_urls_container").hide();
                            if (invalidUrls.length > 0) {
                                jQuery("#edit_failed_urls").css("display", "inline-block");
                                jQuery("#invalid_urls_container").show();
                            } else if (checkPassed) {
                                jQuery("textarea[name='url']").val(jQuery("textarea[name='valid_url']").val());
                                jQuery('#form_add_url').submit();
                                stopScrolling(false);
                            } else {
                                jQuery('#add_url_popup').show();
                            }
                        }
                    }
                });
            }, 500 * i);
        }
    });
}

function contentTabs(e) {
    var contentAction = document.getElementById("content_action");
    //var contentUrls = document.getElementById( "content_urls" );
    var contentCompares = document.getElementById("content_compares");
    var contentActive = document.getElementById("content_" + e);

    var boxAction = document.getElementById("box_action");
    //var boxUrls = document.getElementById( "box_urls" );
    var boxCompares = document.getElementById("box_compares");
    var boxActive = document.getElementById("box_" + e);

    contentAction.style.display = "none";
    boxAction.classList.remove("active");

    contentCompares.style.display = "none";
    boxCompares.classList.remove("active");

    contentActive.style.display = "block";
    boxActive.classList.add("active");
}

function showEditUrlPopup(id, url) {
    document.getElementById("edit_url_popup").style.display = "block";
    document.getElementById("url-id").value = id;
    document.getElementById("url").value = url;
}

function closeEditUrlPopup() {
    document.getElementById("edit_url_popup").style.display = "none";
    return false;
}

function showPlanLimitationPopup() {
    stopScrolling(true);
    document.getElementById("plan_limitation_popup").style.display = "block";
    return false;
}

function closePlanLimitationPopup() {
    stopScrolling(false);
    document.getElementById("plan_limitation_popup").style.display = "none";
    return false;
}

function showAddUrlPopup(id = false, url = false, htmlTitle = false) {
    stopScrolling(true);
    if (id && url) {
        document.getElementById("url-id").value = id;
        document.getElementById("url").value = url;
        document.getElementById("html_title").value = htmlTitle;
        document.getElementById("add-url-headline").innerHTML = "Edit URL";
        closePopup("add-url-groups");
    } else {
        document.getElementById("url-id").value = 0;
        document.getElementById("url").value = '';
        document.getElementById("html_title").value = '';
        document.getElementById("add-url-headline").innerHTML = "Add Webpage";
        openPopup("add-url-groups");
    }

    document.getElementById("add_url_popup").style.display = "block";
}

function closeAddUrlPopup() {
    stopScrolling(false);
    document.getElementById("add_url_popup").style.display = "none";
    return false;
}

function showAssignGroupUrlPopup(id) {
    openPopup("assign_group_urls_popup");

    jQuery('#add_url_group_id').val(id);
    jQuery('#add_url_group_id').attr('name', 'group_id-' + id);
    jQuery('#add_url_desktop').attr('name', 'desktop-' + id);
    jQuery('#add_url_mobile').attr('name', 'mobile-' + id);
    jQuery('#add_url_desktop_hidden').attr('name', 'desktop-' + id);
    jQuery('#add_url_mobile_hidden').attr('name', 'mobile-' + id);
    jQuery("#btn-assign-url").attr('data-group_id', id);
    jQuery("#assign_url_group_id").val(id);
}

function showSyncWpGroupUrlsPopup(id) {
    openPopup("group_wp_sync_urls_popup" + id);
}

function showUpdateSubaccountPopup(id) {
    openPopup("update_subaccount_popup" + id);
}

function closeSyncWpGroupUrlsPopup(id) {
    closePopup("group_wp_sync_urls_popup" + id);
}

function closeAssignGroupUrlsPopup() {
    stopScrolling(false);
    jQuery('#form_add_url').attr('data-group_id', '');
    jQuery('#invalid_urls_textarea').val('');
    jQuery('#valid_urls_container').hide();
    jQuery('#valid_url').val('');
    jQuery('#valid_urls').html('');
    jQuery('#invalid_urls').html('');
    jQuery('#check_urls').html('');
    jQuery('#invalid_urls_container').hide();
    jQuery('#add-urls-textarea').show();

    closePopup("assign_group_urls_popup");
    return false;
}

function showTakeScreenshotsPopup(scType) {
    // Check for credits
    var requiredSc = parseInt(jQuery("#ajax_amount_total_sc").html());
    var availableSc = parseInt(jQuery("#available-credits").data("available_sc"));
    if (requiredSc > availableSc) {
        showPlanLimitationPopup();
        return;
    }

    // Show Popup
    var preButton = jQuery("#take-pre-screenshots");
    var postButton = jQuery("#create-change-detections");
    if (preButton.attr("disabled") == "disabled" || postButton.attr("disabled") == "disabled") {
        return;
    }
    stopScrolling(true);
    document.getElementById("take_screenshots_popup").style.display = "block";

    switch (scType) {
        case 'pre':
            // make pre visible
            document.getElementById("form-take-pre-sc").style.display = "block";
            document.getElementById("headline-take-pre-sc").style.display = "block";

            // make post invisible
            document.getElementById("form-take-post-sc").style.display = "none";
            document.getElementById("headline-take-post-sc").style.display = "none";
            document.getElementById("post_sc_note").style.display = "none";
            break;
        case 'post':
            // make pre invisible
            document.getElementById("form-take-pre-sc").style.display = "none";
            document.getElementById("headline-take-pre-sc").style.display = "none";

            // make post visible
            document.getElementById("form-take-post-sc").style.display = "block";
            document.getElementById("headline-take-post-sc").style.display = "block";
            document.getElementById("post_sc_note").style.display = "block";
            break;
    }
}

function closeTakeScreenshotsPopup() {
    stopScrolling(false);
    document.getElementById("take_screenshots_popup").style.display = "none";
    return false;
}

function showAddSubaccountPopup() {
    stopScrolling(true);
    document.getElementById("add_subaccount_popup").style.display = "block";
}

function closeAddSubaccountPopup() {
    stopScrolling(false);
    document.getElementById("add_subaccount_popup").style.display = "none";
    return false;
}

function closeUpdateSubaccountPopup(id) {
    closePopup("update_subaccount_popup"+id)
    return false;
}

function showUpdateSelectionPopup() {
    stopScrolling(true);
    document.getElementById("update_change_detection_popup").style.display = "block";
}

function closeUpdateSelectionPopup() {
    stopScrolling(false);
    document.getElementById("update_change_detection_popup").style.display = "none";
}

function showChangeDetectionOverviewPopup(group_id, url_id, url = false) {

    // This is a temporarly solution until we find something better.
    window.location.replace('?tab=change-detections&limit_domain=' + url);
    return;


    var data = {
        action: 'filter_change_detections',
        url_id: url_id,
        limit_domain: url,
        group_id: group_id,
        show_filters: 0
    };

    jQuery.post(ajaxurl, data, function (response) {
        jQuery("#change-detections-by-url-id").html(response);
        jQuery("#loading-change-detections").hide();
        if (url) {
            jQuery("#change-detection-url").html(url);
        }
    });

    stopScrolling(true);
    document.getElementById("change_detection_overview_popup").style.display = "block";
}

function closeChangeDetectionOverviewPopup() {
    stopScrolling(false);
    jQuery("#change-detections-by-url-id").html("");
    jQuery("#loading-change-detections").show();
    document.getElementById("change_detection_overview_popup").style.display = "none";
}

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

function toBinary(str) {
    // first we use encodeURIComponent to get percent-encoded Unicode,
    // then we convert the percent encodings into raw bytes which
    // can be fed into btoa.
    return btoa(encodeURIComponent(str).replace(/%([0-9A-F]{2})/g,
        function toSolidBytes(match, p1) {
            return String.fromCharCode('0x' + p1);
        }));
}

function fromBinary(str) {
    // Going backwards: from bytestream, to percent-encoding, to original string.
    return decodeURIComponent(atob(str).split('').map(function (c) {
        return '%' + ('00' + c.charCodeAt(0).toString(16)).slice(-2);
    }).join(''));
}

function ajaxShowChangeDetectionPopup(token, currentNavigationKey = 0, maxNavigationKey = 0) {

    var data = {
        action: 'get_change_detection_popup', //TODO make this ajax function
        token: token,
        currentNavigationKey: currentNavigationKey,
        maxNavigationKey: maxNavigationKey
    };

    // Show loading icon until the change detections is here.
    let popup = document.getElementById("change_detection_popup");
    let changeDetectionDetails = document.getElementById("change_detection_details_container");

    changeDetectionDetails.innerHTML =
        '<div style="width: 100%; text-align: center; margin: 30px;"><img src="' + loadingIcon + '"></div>';
    jQuery(popup).fadeIn();

    jQuery("#change_detection_prev_button").attr("disabled", "disabled");
    jQuery("#change_detection_next_button").attr("disabled", "disabled");
    jQuery.post(ajaxurl, data, function (response) {
        // Shouldn't be necessary on success as the navigation gets replaced anyways. But in case something goes wrong.
        jQuery("#change_detection_prev_button").removeAttr("disabled");
        jQuery("#change_detection_next_button").removeAttr("disabled");
        if (response) {
            showChangeDetectionPopup(response);

        } else {
            alert('Something went wrong. Please try again.');
            closeChangeDetectionPopup();
        }
    });
}

function showChangeDetectionPopup(comparePage) {
    if (comparePage) {
        stopScrolling(true);
        var popup = document.getElementById("change_detection_popup");
        popup.style.display = "block";

        popup.getElementsByClassName("popup-inner")[0].innerHTML = comparePage;
        
        // Move comp-switch outside popup container to fix mobile fixed positioning
        var compSwitch = document.getElementById("comp-switch");
        if (compSwitch) {
            document.body.appendChild(compSwitch);
        }
    }
    (function ($) {

        // update comparison status
        var diffTile = $(".comparison-diff-tile");
        var batchId = diffTile.data("batch_id");

        var bgColor = getDifferenceBgColor(diffTile.data("diff_percent"), diffTile.data("threshold"));
        diffTile.css("background", bgColor);

        // Show local time in comparisons
        $(".screenshot-date").each(function () {
            let date = $(this).data("date");
            if (date) {
                let localDateTime = getLocalDateTime(date);
                $(this).text(localDateTime);
            }
        });

        // Load image slider
        $("#diff-container .comp-img").on("load", function () {

            $("#diff-container").twentytwenty();
            $("#diff-container .comp-img").off("load");

            // Show screenshots for mobile
            $(".show-screenshots").on("click", function () {
                //$(this).css("background", "rgba(12,113,195,0.1)");
                $("#comp_image").hide();
                $("#comp-slider").show();
                $(this).addClass("active");
                $(".show-comparison").removeClass("active");
            });

            // Show comparison for mobile
            $(".show-comparison").on("click", function () {
                //$(this).css("background", "rgba(12,113,195,0.1)");
                $("#comp-slider").hide();
                $("#comp_image").show();
                $(this).addClass("active");
                $(".show-screenshots").removeClass("active");
            });
            
            if ($("#comp-switch").css('display') != 'none') {
                $(".show-screenshots").click();
            }
        });

        $(".current_status .status_box").on("click", function(e) {
            $(this).closest(".status_container").find(".change_status").slideToggle(200);
        });

        $(".comparison-tiles-toggle").on("click", function() {
            $(this).parent().find(".comparison-tiles-container").slideToggle();
        })

        $(".ajax_change_status").on("click", function (e) {
            e.preventDefault();
            var currentStatusElement = $(this).closest('.status_container').find('.current_status');
            currentStatusElement.html("<img src='" + loadingIcon + "'>");
            var comparisonId = $(this).data('comparison_id');
            var status = $(this).data('status');

            var data = {
                action: 'update_comparison_status',
                comparison_id: comparisonId,
                batch_id: batchId,
                status: status,
            };
            $(this).closest(".status_container").find(".change_status").slideToggle(200);
            $.post(ajaxurl, data, function (response) {
                if (response) {
                    response = JSON.parse(response);

                    currentStatusElement.html(response['currentComparison']);
                    $("tr[data-comparison_id='" + comparisonId + "'] .current_status").html(response['currentComparison']);
                    $(".accordion-container[data-batch_id='" + batchId + "'] .status_container .status_buttons").html(response['batchStatuses']);

                    $(".current_status .status_box").on("click", function(e) {
                        $(this).closest(".status_container").find(".change_status").slideToggle(200);
                    });
                } else {
                    alert('Something went wrong...');
                }
            });
        });
    })(jQuery);
}
/*
function navigateChangeDetection(btnId) {
    closeChangeDetectionPopup();
    var nextBtn = document.getElementById("show-compare-" + btnId);
    //nextBtn.click();
}
*/
/* Show compare view switch
function showCompSlider() {
    var slider = document.getElementById("comp-slider");
    var img = document.getElementById("comp_image");
    var btnSlider = document.getElementById("btn-slider");
    var btnImg = document.getElementById("btn-image");
    slider.style.display = "block";
    img.style.display = "none";
    btnSlider.style.background = "#276ECC";
    btnSlider.style.color = "#fff";
    btnImg.style.background = "#fff";
    btnImg.style.color = "#276ECC";
}

function showCompImage() {
    var slider = document.getElementById("comp-slider");
    var img = document.getElementById("comp_image");
    var btnSlider = document.getElementById("btn-slider");
    var btnImg = document.getElementById("btn-image");
    slider.style.display = "none";
    img.style.display = "block";
    btnSlider.style.background = "#fff";
    btnSlider.style.color = "#276ECC";
    btnImg.style.background = "#276ECC";
    btnImg.style.color = "#fff";
}*/
function closePopup(id) {
    stopScrolling(false);
    jQuery("#"+id).fadeOut();
}

function openPopup(id) {
    stopScrolling(true);
    jQuery("#"+id).fadeIn();

}
function closeChangeDetectionPopup() {
    // Clean up comp-switch that was moved to body
    var compSwitch = document.getElementById("comp-switch");
    if (compSwitch && compSwitch.parentNode === document.body) {
        compSwitch.remove();
    }
    
    closePopup("change_detection_popup");
    /*stopScrolling(false);
    //document.getElementById("change_detection_popup").style.display = "none";
    jQuery("#change_detection_popup").fadeOut();*/
    return false;
}

/*function showAddGroupPopup() {
    stopScrolling(true);
    document.getElementById("add_group_popup" ).style.display = "block";
}*/

function closeAddGroupPopup() {
    closePopup("add_group_popup");
    /*stopScrolling(false);
    document.getElementById("add_group_popup").style.display = "none";*/
    return false;
}

function showGroupSettingsPopup(id) {
    openPopup("group_settings_popup" + id);
}

function closeGroupSettingsPopup(id = '') {
    closePopup("group_settings_popup" + id);
    return false;
}

function showWpGroupSettingsPopup(id) {
    openPopup("add_wp_group_popup" + id);
}

function closeAddWpGroupPopup(id = '') {
    closePopup("add_wp_group_popup"+id);
    return false;
}

function showSyncProgressPopup() {
    stopScrolling(true);
    openPopup("sync-progress-popup");
}

function closeSyncProgressPopup() {
    stopScrolling(false);
    closePopup("sync-progress-popup");
    return false;
}

function showCssPopup(groupId, urlId) {
    stopScrolling(true);
    openPopup("show_css_popup-" + groupId + "-" + urlId);
}

function closeCssPopup(groupId, urlId) {
    closePopup("show_css_popup-" + groupId + "-" + urlId);
    return false;
}

function stopScrolling(active = true) {
    if (active) {
        document.body.style.overflow = "hidden";
    } else {
        document.body.style.overflow = "auto";
    }
}


/*function showGroupCssPopup(groupId) {
    stopScrolling(true);
    let popup = document.getElementById("show_group_css_popup-" + groupId);
    popup.style.display = "block";
    makeCodearea(popup);
}*/

function closeGroupCssPopup(groupId) {
    closePopup("show_group_css_popup-" + groupId);
    return false;
}

// Simple accordion for hiding code fields
function mm_show_more_link(popupId) {
    let popup = document.getElementById(popupId);
    jQuery(popup).find('.show-more').slideToggle();
    makeCodearea(popup);
}

/**
 * Marks Rows
 *
 * @param {string} groupIdAndurlId
 */
function mmMarkRows(groupIdAndurlId) {

    var desktop = document.getElementById("desktop-" + groupIdAndurlId);

    if (!desktop) {
        desktop = document.getElementsByName("desktop-" + groupIdAndurlId);
    }

    var mobile = document.getElementById("mobile-" + groupIdAndurlId);
    if (!mobile) {
        mobile = document.getElementsByName("mobile-" + groupIdAndurlId);
    }

    var accordion = jQuery(desktop).closest(".accordion-container");

    var groupEnabled;
    var row = document.getElementById(groupIdAndurlId);
    var groupId = jQuery(row).data("group_id");
    var autoGroup = jQuery(accordion).data("auto_group");

    // Manual checks
    if (!autoGroup) {
        groupEnabled = jQuery(accordion).data('manual_enabled');
    } else {
        groupEnabled = jQuery(accordion).data('auto_enabled');
    }

    if (row && ((desktop && desktop.checked == true) || (mobile && mobile.checked == true)) && groupEnabled == true) {
        // one or the other checkbox is checked
        row.style.background = MM_BG_COLOR_GREEN;

        if (autoGroup) {
            jQuery(row).find(".animation-enabled").addClass("active");
            jQuery(row).find(".monitoring-status").html("<small>Monitoring<br>Active</small>")
        }
    } else if (row) {
        // none of the checkboxes are checked
        row.style.background = MM_BG_COLOR_RED;

        if (autoGroup) {
            jQuery(row).find(".animation-enabled").removeClass("active");
            jQuery(row).find(".monitoring-status").html("<small>Monitoring<br>Off</small>")
        }
    } else {
        // can't even find the row
        // console.error('Could not find row by urlId ' + urlId)
    }
}

/**
 * Returns a background color based on the percent difference in change detection
 *
 * @param {int} percent
 */
function getDifferenceBgColor(percent, threshold = 0.00) {
    // early return if no difference in percent
    if (parseFloat(percent) === 0.0 && !threshold) {
        // Dark green
        return MM_BG_COLOR_DARK_GREEN;
    } else if (parseFloat(percent) < threshold) {
        return MM_BG_COLOR_LIGHT_GREEN;
    }
    var pct = 1 - (percent / 100);

    var percentColors = [
        // #8C0000 - dark red
        {pct: 0.0, color: {r: 0x8c, g: 0x00, b: 0}},
        // #E5A025 - orange
        {pct: 1.0, color: {r: 0xe5, g: 0xa0, b: 0x25}}
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

// ----------------------------------------------------------------------------
// @deprecated / unused
// ----------------------------------------------------------------------------

function magnify(imgID, zoom) {
    var img, glass, w, h, bw;
    img = document.getElementById(imgID);

    /* Create magnifier glass: */
    glass = document.createElement("DIV");
    glass.setAttribute("class", "img-magnifier-glass");

    /* Insert magnifier glass: */
    img.parentElement.insertBefore(glass, img);

    /* Set background properties for the magnifier glass: */
    glass.style.backgroundImage = "url('" + img.src + "')";
    glass.style.backgroundRepeat = "no-repeat";
    glass.style.backgroundSize = (img.width * zoom) + "px " + (img.height * zoom) + "px";
    bw = 3;
    w = glass.offsetWidth / 2;
    h = glass.offsetHeight / 2;

    /* Execute a function when someone moves the magnifier glass over the image: */
    glass.addEventListener("mousemove", moveMagnifier);
    img.addEventListener("mousemove", moveMagnifier);

    /*and also for touch screens:*/
    glass.addEventListener("touchmove", moveMagnifier);
    img.addEventListener("touchmove", moveMagnifier);

    function moveMagnifier(e) {
        var pos, x, y;
        /* Prevent any other actions that may occur when moving over the image */
        e.preventDefault();
        /* Get the cursor's x and y positions: */
        pos = getCursorPos(e);
        x = pos.x;
        y = pos.y;
        /* Prevent the magnifier glass from being positioned outside the image: */
        if (x > img.width - (w / zoom)) {
            x = img.width - (w / zoom);
        }
        if (x < w / zoom) {
            x = w / zoom;
        }
        if (y > img.height - (h / zoom)) {
            y = img.height - (h / zoom);
        }
        if (y < h / zoom) {
            y = h / zoom;
        }
        /* Set the position of the magnifier glass: */
        glass.style.left = (x - w) + "px";
        glass.style.top = (y - h) + "px";
        /* Display what the magnifier glass "sees": */
        glass.style.backgroundPosition = "-" + ((x * zoom) - w + bw) + "px -" + ((y * zoom) - h + bw) + "px";
    }

    function getCursorPos(e) {
        var a, x = 0, y = 0;
        e = e || window.event;
        /* Get the x and y positions of the image: */
        a = img.getBoundingClientRect();
        /* Calculate the cursor's x and y coordinates, relative to the image: */
        x = e.pageX - a.left;
        y = e.pageY - a.top;
        /* Consider any page scrolling: */
        x = x - window.pageXOffset;
        y = y - window.pageYOffset;
        return {x: x, y: y};
    }
}

// ----------------------------------------------------------------------------
// External Classes
// ----------------------------------------------------------------------------

/**
 *  Base64 encode / decode when encoded / decoded in PHP
 *  @source http://www.webtoolkit.info/javascript_base64.html#.V_jOG5N96YU
 **/
var Base64 = {

    // private property
    _keyStr: "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+/=",

    // public method for encoding
    encode: function (input) {
        var output = "";
        var chr1, chr2, chr3, enc1, enc2, enc3, enc4;
        var i = 0;

        input = Base64._utf8_encode(input);

        while (i < input.length) {

            chr1 = input.charCodeAt(i++);
            chr2 = input.charCodeAt(i++);
            chr3 = input.charCodeAt(i++);

            enc1 = chr1 >> 2;
            enc2 = ((chr1 & 3) << 4) | (chr2 >> 4);
            enc3 = ((chr2 & 15) << 2) | (chr3 >> 6);
            enc4 = chr3 & 63;

            if (isNaN(chr2)) {
                enc3 = enc4 = 64;
            } else if (isNaN(chr3)) {
                enc4 = 64;
            }

            output = output +
                this._keyStr.charAt(enc1) + this._keyStr.charAt(enc2) +
                this._keyStr.charAt(enc3) + this._keyStr.charAt(enc4);

        }

        return output;
    },

    // public method for decoding
    decode: function (input) {
        var output = "";
        var chr1, chr2, chr3;
        var enc1, enc2, enc3, enc4;
        var i = 0;

        input = input.replace(/[^A-Za-z0-9\+\/\=]/g, "");

        while (i < input.length) {

            enc1 = this._keyStr.indexOf(input.charAt(i++));
            enc2 = this._keyStr.indexOf(input.charAt(i++));
            enc3 = this._keyStr.indexOf(input.charAt(i++));
            enc4 = this._keyStr.indexOf(input.charAt(i++));

            chr1 = (enc1 << 2) | (enc2 >> 4);
            chr2 = ((enc2 & 15) << 4) | (enc3 >> 2);
            chr3 = ((enc3 & 3) << 6) | enc4;

            output = output + String.fromCharCode(chr1);

            if (enc3 != 64) {
                output = output + String.fromCharCode(chr2);
            }
            if (enc4 != 64) {
                output = output + String.fromCharCode(chr3);
            }

        }

        output = Base64._utf8_decode(output);

        return output;

    },

    // private method for UTF-8 encoding
    _utf8_encode: function (string) {
        string = string.replace(/\r\n/g, "\n");
        var utftext = "";

        for (var n = 0; n < string.length; n++) {

            var c = string.charCodeAt(n);

            if (c < 128) {
                utftext += String.fromCharCode(c);
            } else if ((c > 127) && (c < 2048)) {
                utftext += String.fromCharCode((c >> 6) | 192);
                utftext += String.fromCharCode((c & 63) | 128);
            } else {
                utftext += String.fromCharCode((c >> 12) | 224);
                utftext += String.fromCharCode(((c >> 6) & 63) | 128);
                utftext += String.fromCharCode((c & 63) | 128);
            }

        }

        return utftext;
    },

    // private method for UTF-8 decoding
    _utf8_decode: function (utftext) {
        var string = "";
        var i = 0;
        var c = c1 = c2 = 0;

        while (i < utftext.length) {

            c = utftext.charCodeAt(i);

            if (c < 128) {
                string += String.fromCharCode(c);
                i++;
            } else if ((c > 191) && (c < 224)) {
                c2 = utftext.charCodeAt(i + 1);
                string += String.fromCharCode(((c & 31) << 6) | (c2 & 63));
                i += 2;
            } else {
                c2 = utftext.charCodeAt(i + 1);
                c3 = utftext.charCodeAt(i + 2);
                string += String.fromCharCode(((c & 15) << 12) | ((c2 & 63) << 6) | (c3 & 63));
                i += 3;
            }

        }

        return string;
    }

}

/* Percentage spinner */
let animation_loop;

function startSpinner() {
    let spinner = document.getElementById("spinner");
    let ctx = spinner.getContext("2d");
    let width = spinner.width;
    let height = spinner.height;
    let degrees = 0;
    let new_degrees = 0;
    let difference = 0;
    let color = "#2C70C9";
    let bgcolor = "#222";
    let text;
    let redraw_loop;

    clearInterval(animation_loop);

    function init() {
        ctx.clearRect(0, 0, width, height);

        ctx.beginPath();
        ctx.strokeStyle = bgcolor;
        ctx.lineWidth = 30;
        ctx.arc(width / 2, width / 2, 100, 0, Math.PI * 2, false);
        ctx.stroke();
        let radians = degrees * Math.PI / 180;

        ctx.beginPath();
        ctx.strokeStyle = color;
        ctx.lineWidth = 30;
        ctx.arc(width / 2, height / 2, 100, 0 - 90 * Math.PI / 180, radians - 90 * Math.PI / 180, false);
        ctx.stroke();
        ctx.fillStyle = color;
        ctx.font = "50px arial";
        text = Math.floor(degrees / 360 * 100) + "%";
        text_width = ctx.measureText(text).width;
        ctx.fillText(text, width / 2 - text_width / 2, height / 2 + 15);
    }

    function draw() {
        if (typeof animation_loop != undefined) clearInterval(animation_loop);
        new_degrees = 360;
        difference = new_degrees - degrees;
        animation_loop = setInterval(animate_to, 20000 / difference);

    }

    function animate_to() {
        if (degrees == 90) {
            clearInterval(animation_loop);
            animation_loop = setInterval(animate_to, 30000 / difference);
            degrees++;
        } else if (degrees == 180) {
            clearInterval(animation_loop);
            animation_loop = setInterval(animate_to, 45000 / difference);
            degrees++;
        } else if (degrees == 270) {
            clearInterval(animation_loop);
            animation_loop = setInterval(animate_to, 60000 / difference);
            degrees++;
        } else if (degrees == 320) {
            clearInterval(animation_loop);
            animation_loop = setInterval(animate_to, 80000 / difference);
            degrees++;
        } else if (degrees == 350) {
            clearInterval(animation_loop);
            animation_loop = setInterval(animate_to, 200000 / difference);
            degrees++;
        } else if (degrees == new_degrees)
            clearInterval(animation_loop);

        else if (degrees < new_degrees)
            degrees++;
        else
            degrees--;
        init();
    }

    draw();
}
