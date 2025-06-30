<?php

?>
<div id="add_subaccount_popup" class="ajax-popup" style="display: none;">
	<div class="popup">
		<div class="popup-inner">
			<h2>Add Subaccount</h2>
			<button class="et_pb_button close-popup-button" onclick="return closeAddSubaccountPopup()">X<small>ESC</small></button>
            <form class="ajax">
                <div class="form-container">
                    <input type="hidden" name="action" value="add_subaccount">
                    <div class="form-row bg">
                        <label>First Name</label>
                        <input type="text" name="name_first">
                    </div>
                    <div class="form-row ">
                        <label>Last Name</label>
                        <input type="text" name="name_last">
                    </div>
                    <div class="form-row bg">
                        <label>Email</label>
                        <input type="text" name="email"><br>
                    </div>
                    <div class="form-row ">
                        <label>Limit Checks</label>
                        <input type="number" step="1" name="limit_checks"><br>
                    </div>
                </div>
                <input type="submit" class="et_pb_button" value="Create Subaccount">
            </form>
        </div>
	</div>
</div>
