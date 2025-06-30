<?php
//dd($subaccount);
?>
<div id="update_subaccount_popup<?php echo $subaccount['id'] ?>" class="ajax-popup" style="display: none;">
    <div class="popup">
        <div class="popup-inner">
            <h2>Edit Subaccount</h2>
            <button class="et_pb_button close-popup-button" onclick="return closeUpdateSubaccountPopup('<?php echo $subaccount['id'] ?>')">X<small>ESC</small></button>
            <form class="ajax">
                <div class="form-container">
                    <input type="hidden" name="action" value="update_subaccount">
                    <input type="hidden" name="id" value="<?php echo $subaccount['id'] ?>">
                    <div class="form-row bg">
                        <label>First Name</label>
                        <input type="text" name="name_first" value="<?php echo $subaccount['name_first'];?>" >
                    </div>
                    <div class="form-row ">
                        <label>Last Name</label>
                        <input type="text" name="name_last" value="<?php echo $subaccount['name_last'];?>">
                    </div>
                    <div class="form-row bg">
                        <label>Email</label>
                        <input type="text" name="email" value="<?php echo $subaccount['email'];?>"><br>
                    </div>
                    <div class="form-row ">
                        <label>Limit Checks</label>
                        <input type="number" step="1" name="limit_checks" value="<?php echo $subaccount['checks_limit'];?>"><br>
                    </div>
                </div>
                <input type="submit" class="et_pb_button" value="Save">
            </form>
        </div>
    </div>
</div>
