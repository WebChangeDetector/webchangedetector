<div style="width: 50%; float: left;">
    <div style="padding: 10px; margin-top: 20px;" class="auto-setting toggle">
        <label for="threshold" >Threshold</label>
        <input name="threshold" class="threshold" type="number" step="0.1" min="0" max="100" value="<?= $groups_and_urls['threshold'] ?>"> %
        <input type="hidden" name="group_name" value="<?= $groups_and_urls['name'] ?>">
    </div>
</div>
<div style="width: 50% ; float: left; ">
    <div style="border-left: 1px solid #aaa; padding: 10px;">
        <?php include("css-settings.php"); ?>
    </div>
</div>
<div class="clear"></div>
