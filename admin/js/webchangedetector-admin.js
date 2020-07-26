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
			} else {
				$(".auto-setting").show();
			}
		}
	});

})( jQuery );

function mmMarkRows( postId ) {
	//var active = document.getElementById("active-" + postId);
	var desktop = document.getElementById("desktop-" + postId);
	var mobile = document.getElementById("mobile-" + postId);
	var row = document.getElementById( postId );

	if (  desktop.checked == true || mobile.checked == true ){
		row.style.background = "#17b33147";
	} else {
		row.style.background = "#dc323247";
	}
}

function mmToggle(source, postType, column, groupId) {
	var checkboxes = document.querySelectorAll('.checkbox-' + column + '-' + postType + ' input[type=\"checkbox\"]');
	for (var i = 0; i < checkboxes.length; i++) {
		if (checkboxes[i] != source) {
			checkboxes[i].checked = source.checked;
		}
	}

	var rows = document.querySelectorAll('.post_id_' + groupId );
	for (var i = 0; i < rows.length; i++) {

		var id = rows[i].id;
		mmMarkRows( id );
	}
}

function mmValidateForm() {

	var emailsElement = document.getElementById("alert_emails");

	emails = emailsElement.value.replace(/\s/g,'').split(",");

	var valid = true;
	var regex = /^(([^<>()[\]\\.,;:\s@\"]+(\.[^<>()[\]\\.,;:\s@\"]+)*)|(\".+\"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/;

	for (var i = 0; i < emails.length; i++) {
		if( emails[i] == "" || ! regex.test(emails[i])){
			valid = false;
			emailsElement.style.border = "2px solid red";
		}
	}
	if( valid ) {
		emailsElement.style.border = "2px solid green";
	}
	return valid;
}

