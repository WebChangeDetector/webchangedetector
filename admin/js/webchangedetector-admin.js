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
    $( document ).ready(function() {
        $(".accordion").accordion({header: "h3", collapsible: true, active: false});
        $(".accordion").last().accordion("option", "icons", true);

        // Enable / disable settings for auto change detection
        showAutoSettings();

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

