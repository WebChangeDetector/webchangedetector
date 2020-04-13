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

function mmValidateEmail(email) {
	var re = /^(([^<>()[\]\\.,;:\s@\"]+(\.[^<>()[\]\\.,;:\s@\"]+)*)|(\".+\"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/;
	return re.test(email);
}

function mmValidateForm() {

	var firstName = document.forms["new_account"]["first_name"].value;
	var lastName = document.forms["new_account"]["last_name"].value;
	var email = document.forms["new_account"]["email"].value;
	var message = "";

	if (firstName == "") {
		document.getElementById("form_first_name").style.border='1px solid red';
		message = message + "First Name must be filled \n";
	} else
		document.getElementById("form_first_name").style.border='1px solid green';
	if (lastName == "") {
		document.getElementById("form_last_name").style.border='1px solid red';
		message = message + "Last Name must be filled \n";
	} else
		document.getElementById("form_last_name").style.border='1px solid green';

	if (email == "") {
		document.getElementById("form_email").style.border='1px solid red';
		message = message + "Email must be filled \n";
	} else {
		if( !mmValidateEmail( email ) ) {
			document.getElementById("form_email").style.border='1px solid red';
			message = message + "Please check your email address."
		} else
			document.getElementById("form_email").style.border='1px solid green';
	}

	if( message != "" ) {
		alert( message );
		return false;
	}
}

