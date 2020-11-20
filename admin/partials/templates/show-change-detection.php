<div class="comparison-tiles">
    <div class="comparison-tile comparison-url-tile">
        <?php
        if (! empty($compare['screenshot1']['queue']['url']['html_title'])) {
            echo '<strong>' . $compare['screenshot1']['queue']['url']['html_title'] . '</strong><br>';
        } ?>

        <a href="http://<?= $compare['screenshot1']['url'] ?>" target="_blank" >
            <?= $compare['screenshot1']['url'] ?>
        </a>

        <br>

        <?php $public_link = $this->app_url() . 'show-change-detection/?token=' . $token; ?>
        Public link: <a href="<?= $public_link ?>" target="_blank">
            <?= $public_link ?>
        </a>

    </div>

    <div class="comparison-tile comparison-diff-tile" data-diff_percent="<?= $compare['difference_percent'] ?>">
        <strong>Difference </strong><br>
        <span><?= $compare['difference_percent'] ?> %</span>
    </div>
    <!--<div style="min-height: 102px; float: left; width: 30%; padding: 10px; background: #eee; border: 1px solid #aaa; border-right: none;" >
        <strong><?= $compare['screenshot1']['queue']['url']['html_title'] ?></strong><br>
        <a href="http://<?= $compare['screenshot1']['url'] ?>" target="_blank" >
            <?= $compare['screenshot1']['url'] ?></a><br>

    </div>-->

    <div class="comparison-tile comparison-date-tile">
        <strong>Screenshots</strong><br>
        <div class="screenshot-date" style="text-align: right; display: inline;" data-date="<?= strtotime($compare['screenshot1']['updated_at']) ?>"><?= date('d/m/Y H:i.s', strtotime($compare['screenshot1']['updated_at']))?></div>
        <div class="screenshot-date" style="text-align: right; display: inline;" data-date="<?= strtotime($compare['screenshot2']['updated_at']) ?>"><?= date('d/m/Y H:i.s', strtotime($compare['screenshot2']['updated_at']))?></div>
    </div>
</div>
<div class="clear"></div>

<div id="comp-slider" style="width: 49%; float: left;">
    <h2>Screenshots</h2>
    <div id="diff-container" data-token="<?= $_GET['token'] ?? $compare['token'] ?>" style="width: 100%; ">
        <img class="comp-img" style="padding: 0;" src="<?= $compare['screenshot1']['link'] ?>">
        <img style="padding: 0;" src="<?= $compare['screenshot2']['link'] ?>">
    </div>
</div>

<div id="comp_image" class="comp_image" style="width: 49%; float: right; margin-right: 0;">
    <h2>Change Detection</h2>
    <img style="padding: 0;" src="<?= $compare['link'] ?>">
</div>
<div class="clear"></div>
<?= $navigation ?? '<div style="width: 100%; text-align: center"></div>' ?>
<div id="comp-switch" style="display: none;">
    <button id="show-screenshots" class="et_pb_button">Screenshots</button>
    <button id="show-comparison" class="et_pb_button">Change Detection</button>
</div>