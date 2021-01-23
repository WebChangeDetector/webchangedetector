<!-- Pre Update -->
<div class="wcd-update-step pre-update">
    <div class="status_bar">Currently selected<br>
        <span class="big">
            <?= $groups_and_urls['amount_selected_urls'] ?>
            Screenshots
        </span><br>
        <?= $account_details['available_compares'] ?> available until renewal
    </div>

    <?php $disabled =  $groups_and_urls['amount_selected_urls'] ? '' : 'disabled'; ?>
    <div style="width: 300px; margin: 0 auto;">
        <form id="frm-take-pre-sc" action="<?= admin_url() . $wcd::TAB_UPDATE ?>" method="post">
            <input type="hidden" value="take_screenshots" name="wcd_action">
            <input type="hidden" name="sc_type" value="pre">
            <button type="submit" class="button-primary" style="width: 100%;" <?= $disabled ?> >
                <h3 style="color: #fff;">Take Pre-Update Screenshots</h3>
                <span>Take screenshots <strong>before</strong> you install updates.</span>
            </button>
        </form>
    </div>
</div>