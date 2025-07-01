<div class="mm_change_detection_popup" data-comparison_id="<?php echo $compare['id']; ?>">

    <?php if(!isset($compare['id'])) {
        echo "<h1>Sorry, something went wrong. Please try again later.</h1>\n";
    }

    // Hide header if whitelabled is set.
    if(isset($_GET['whitelabled']) && $_GET['whitelabled'] == true ) {
        echo "<style> header { display: none !important } </style>";
    }

    // Hide Navigation from public page. 
    if ( ! isset( $public_page ) ) { ?>
        <div class="change_detection_navigation">
            <h2 class="popup-mobile-change-detection-headline" style="display: none;">Change Detection</h2>
            <div style="position:absolute; right: 0; top: 10px;">
                <button class="et_pb_button close-popup-button" style="z-index: 3" onclick="closeChangeDetectionPopup()">X<small>ESC</small></button>
            </div>
            <?php
            $prev_disabled   = 'disabled';
            $next_disabled   = 'disabled';
            $disabled_string = 'disabled';

            // echo $navigation ?? '<div style="width: 100%; text-align: center"></div>';
            $currentNavigationKey = 0;
            if ( isset( $_POST['currentNavigationKey'] ) && 'false' !== $_POST['currentNavigationKey'] ) {
                $currentNavigationKey = $_POST['currentNavigationKey'];
            }
            $maxNavigationKey = 0;
            if ( isset( $_POST['maxNavigationKey'] ) && 'false' !== $_POST['maxNavigationKey'] ) {
                $maxNavigationKey = $_POST['maxNavigationKey'];
            }

            //echo $currentNavigationKey ." - " . $maxNavigationKey;

            $prev_id = $currentNavigationKey - 1 >= 0 ? ".accordion-container[data-batch_id=\'" . $compare['batch'] . "\'] #show-compare-" . $currentNavigationKey - 1 : false;
            $next_id = $currentNavigationKey + 1 < $maxNavigationKey ? ".accordion-container[data-batch_id=\'" . $compare['batch'] . "\'] #show-compare-" . $currentNavigationKey + 1 : false;

            if ( $prev_id || $next_id ) { ?>
                <div class="change_detection_navigation_container" >
                    <button id="change_detection_prev_button" class="et_pb_button" <?php echo $prev_id !== false ? ' onclick="jQuery(\'' . $prev_id . '\').click()"' : 'disabled'; ?> >Previous</button>
                    <?php echo $currentNavigationKey + 1; ?> / <?php echo $maxNavigationKey; ?>
                    <button id="change_detection_next_button" class="et_pb_button" <?php echo $next_id !== false ? ' onclick="jQuery(\'' . $next_id . '\').click()"' : 'disabled'; ?>>Next</button>
                </div>
            <?php } ?>
        </div>
        <div class="change_detection_placeholder_margin <?php echo $prev_id || $next_id ? "change_detection_placeholder_margin_with_navigation" : "" ?>"></div>
	<?php 
    } 

    // Hide details if get-param is set.
    if(!isset($_GET['hide-details']) && !$_GET['hide-details']) {
    ?>
        <div id="change_detection_details_container"> 
            <div class="comparison-tiles-toggle"><a >Show / Hide Details</a></div>
            <script>jQuery("comparison-tiles-toggle").click();</script>
            <div class="comparison-tiles-container">

            <div class="comparison-tiles" style="width: calc(100% - 610px);">
            <?php

            if ( ! empty( $compare['html_title'] ) ) {
                echo '<strong>' . $compare['html_title'] . '</strong><br>';
            }
            ?>

                Webpage: <a href="http://<?php echo $compare['url']; ?>" target="_blank" >
                <?php echo $compare['url']; ?>
                </a>
                <?php if ( ! isset( $public_page ) ) { ?>
                    <br>

                    <?php
                    $token       = $_GET['token'] ?? $compare['token'];
                    $public_link = mm_get_current_domain() . '/show-change-detection/?token=' . $token;
                    ?>
                    <a href="<?php echo $public_link; ?>" target="_blank">
                    Public Change Detection link
                    </a>
                <?php } ?>
            </div>

            <div class="comparison-tiles" style="width: 240px;">
                <strong>Screenshots taken</strong><br>
                <strong>Before:</strong> <div class="screenshot-date" style="text-align: right; display: inline;" data-date="<?php echo strtotime( $compare['screenshot_1_updated_at'] ); ?>"><?php echo gmdate( 'd/m/Y H:i.s', strtotime( $compare['screenshot_1_updated_at'] ) ); ?></div><br>
                <strong>After:</strong> <div class="screenshot-date" style="text-align: right; display: inline;" data-date="<?php echo strtotime( $compare['screenshot_2_updated_at'] ); ?>"><?php echo gmdate( 'd/m/Y H:i.s', strtotime( $compare['screenshot_2_updated_at'] ) ); ?></div>
            </div>
            <?php 
            // Check user plan access for browser console feature
            $wp_comp = new Wp_Compare();
            $user_account = $wp_comp->get_account_details_v2();
            $user_plan = $user_account['plan'] ?? 'free';
            $canAccessBrowserConsole = wcd_can_access_feature('browser_console', $user_plan);
            
            if(!empty($_COOKIE['wcd-show-browser-console'])) { 
                if ($canAccessBrowserConsole) { ?>
                    <div class="comparison-tiles" style="width: 240px;">
                        <strong>Browser console changes</strong><br>
                        <strong>Added:</strong> <?= is_array($compare['browser_console_added']) ? implode("<br>", array_map(function($log) { return esc_html(is_array($log) && isset($log['text']) ? $log['text'] : $log); }, $compare['browser_console_added'])) : 'none' ?><br>
                        <strong>Removed:</strong> <?= is_array($compare['browser_console_removed']) ? implode("<br>", array_map(function($log) { return esc_html(is_array($log) && isset($log['text']) ? $log['text'] : $log); }, $compare['browser_console_removed'])) : 'none' ?><br>
                        <strong>Changed:</strong> <?= is_array($compare['browser_console_change']) ? implode("<br>", array_map('esc_html', $compare['browser_console_change'])) : 'none' ?><br>
                    </div>
                <?php } else { 
                    // Generate dummy preview content for plans that don't have access
                    $dummyConsoleContent = '
                        <div class="comparison-tiles" style="width: 240px;">
                            <strong>Browser console changes</strong><br>
                            <strong>Added:</strong> Failed to load resource: net::ERR_CONNECTION_REFUSED<br>Uncaught TypeError: Cannot read property \'style\' of null<br>
                            <strong>Removed:</strong> jQuery is loaded and ready<br>
                            <strong>Changed:</strong> Console behavior modified<br>
                        </div>';
                    
                    echo wcd_generate_feature_preview('browser_console', $dummyConsoleContent, 'wcd-console-tiles-restricted');
                } 
            } ?>
            <div class="comparison-tiles" style="width: 375px; margin-right: 0;">
                <strong>URL Details</strong><br>
                <!--<?php echo get_device_icon( $compare['device'] ); ?>
                <?php echo ucfirst( $compare['device'] );?><br>-->
                <?php if ( $compare['monitoring'] ) { ?>
                    <?php echo get_group_icon( ['monitoring' =>  $compare['monitoring']] ); ?>
                <?php } else { ?>
                    <?php echo get_group_icon( ['monitoring' =>  $compare['monitoring']] ); ?>
                    <?php } ?>
                    <?php echo $compare['batch_name'] ?>
                    <?php echo $assigned_group_type ?? ''; ?><br>
                    <?php if ( isset( $compare['group'] ) && ! isset($public_page) ) { ?>

                    <a href='?tab=<?php echo ( $compare['monitoring'] ? 'auto-change-detection' : 'update-change-detection' ); ?>#group_<?php echo $compare['group']; ?>'>
                        <?php echo $compare['cms'] === 'wordpress' ? get_device_icon( 'wordpress' ) : get_device_icon( 'general' ); ?>
                        <?php echo $compare['group_name']; ?>
                    </a>
                        <?php
                }
                ?>
            </div>
            <div class="clear"></div>
        </div>
    <?php 
    } 

    // Hide difference in percent if get-param is set.
    if(!isset($_GET['hide-difference']) && !$_GET['hide-difference']) {
    ?>
        <div class="comparison-main-bar" style="display: flex; align-items: center; gap: 20px; margin: 15px auto 25px auto; justify-content: center;">
            <div class="comparison-diff-tile" style="flex: 1; max-width: 600px; min-height: 40px; display: block; text-align: center;"
                 data-diff_percent="<?php echo $compare['difference_percent']; ?>"
                 data-threshold="<?php echo $compare['threshold']; ?>"
                 data-token="<?php echo $token; ?>"
                 data-batch_id="<?php echo $compare['batch']; ?>">
                <?php if($compare['difference_percent'] > 0) { ?>
                    Difference detected: <span><?php echo $compare['difference_percent']; ?> %</span>
                <?php } else { ?>
                    No Difference detected
                <?php } ?>

                <?php echo $compare['threshold'] > $compare['difference_percent'] ? '<div style="font-size: 10px">Threshold: ' . $compare['threshold'] . '%</div>' : ''; ?>
            </div>
            
            <div class="status_container modern-status">
                <div class="status-badge">
                    <span class="current_status">
                        <?php
                        echo prettyPrintComparisonStatus( $compare['status'], 'mm_inline_block' );
                        ?>
                    </span>
                </div>
                <?php if ( ! isset( $public_page ) ) { ?>
                <div class="change_status" style="display: none; position: absolute; background: #fff; padding: 20px; box-shadow: 0 0 5px #aaa; z-index: 100;">
                    Change Status to:<br>
                    <a href="#" data-comparison_id="<?php echo $compare['id']; ?>" data-status="new" class="ajax_change_status <?php echo ( $compare['status'] ) == 'new' ? 'hide' : ''; ?>"><?php echo prettyPrintComparisonStatus( 'new' ); ?></a>
                    <a href="#" data-comparison_id="<?php echo $compare['id']; ?>" data-status="ok" class="ajax_change_status <?php echo ( $compare['status'] ) == 'ok' ? 'hide' : ''; ?>"><?php echo prettyPrintComparisonStatus( 'ok' ); ?></a>
                    <a href="#" data-comparison_id="<?php echo $compare['id']; ?>" data-status="to_fix" class="ajax_change_status <?php echo ( $compare['status'] ) == 'to_fix' ? 'hide' : ''; ?>"><?php echo prettyPrintComparisonStatus( 'to_fix' ); ?></a>
                    <a href="#" data-comparison_id="<?php echo $compare['id']; ?>" data-status="false_positive" class="ajax_change_status <?php echo ( $compare['status'] ) == 'false_positive' ? 'hide' : ''; ?>"><?php echo prettyPrintComparisonStatus( 'false_positive' ); ?></a>
                </div>
                <?php } else { ?>
                <a class="status-login-link" href="/webchangedetector">Login to change status</a>
                <?php } ?>
            </div>
        </div>
    <?php
    }

    if(str_contains($compare['screenshot_1_link'], '_dev_.png')) {
        $sc_1_compressed = str_replace("_dev_.png","_dev_compressed.jpeg", $compare['screenshot_1_link']);
        $sc_2_compressed = str_replace("_dev_.png","_dev_compressed.jpeg", $compare['screenshot_2_link']);
        $sc_comparison_compressed = str_replace("_dev_.png","_dev_compressed.jpeg", $compare['link']);
    } else {
        $sc_1_compressed = str_replace(".png","_compressed.jpeg", $compare['screenshot_1_link']);
        $sc_2_compressed = str_replace(".png","_compressed.jpeg", $compare['screenshot_2_link']);
        $sc_comparison_compressed = str_replace(".png","_compressed.jpeg", $compare['link']);
    }
    $sc_1_raw = $compare['screenshot_1_link'];
    $sc_2_raw = $compare['screenshot_2_link'];
    $sc_comparison_raw = $compare['link'];
    ?>

    <?php $styles_sc = $compare['device'] === 'mobile' ? ' max-width: 377px; ' : 'max-width: 100%;'; ?>
    <?php $styles_comp = $compare['device'] === 'mobile' ? ' max-width: 375px' : 'max-width: 100%;'; ?>

    <h2 id="headline-screenshots" style="width: calc(50% - 10px); display:inline-block;">Screenshots</h2>
    <h2 id="headline-change-detection" style="width: calc(50% - 10px); display:inline-block;">Change Detection</h2>

    <div class="comp-container" style="display: flex; align-items: stretch; gap: 0; ">
        <!-- Left Column: Right-aligned -->
        <div id="comp-slider" style="flex: 1; text-align: right; flex-direction: column;">
            <div class="comp-wrapper" style="margin-left: auto; margin-right: 0; <?php echo $styles_sc ?>">
                <div id="diff-container" data-token="<?php echo $_GET['token'] ?? $compare['token']; ?>" style="padding: 0; <?php echo $styles_comp ?>">
                    <img class="comp-img skip-lazy" style="display: block; padding: 0;" src="<?php echo $sc_1_compressed; ?>" onerror="loadFallbackImg(this, '<?php echo $sc_1_raw ?>')">
                    <img style="padding: 0; display: block;" class="skip-lazy" src="<?php echo $sc_2_compressed; ?>" onerror="loadFallbackImg(this, '<?php echo $sc_2_raw ?>')">
                </div>
            </div>
        </div>

        <!-- Middle Column: Fixed width 30px, centered -->
        <div id="middle-column" style="flex: 0 0 20px;  text-align: center; flex-direction: column; padding-left:10px; padding-right: 10px;
                background: url('<?php echo str_replace('.png','_diffbar.jpeg',$compare['link'])?>') repeat-x;
                background-size: 100% 100%;">
        </div>

        <!-- Right Column: Left-aligned -->
        <div id="comp_image" class="comp_image" style="flex: 1; text-align: left; flex-direction: column;">
            <div id="comp_image_container" style="<?php echo $styles_comp ?> ">
                <img id="comp_image_image" style="display: block; width: 100%; padding: 0; border: none;" class="skip-lazy" src="<?php echo $sc_comparison_compressed; ?>" onerror="loadFallbackImg(this, '<?php echo $sc_comparison_raw ?>')">
            </div>
        </div>
    </div>

    <div class="clear"></div>
</div>

<div id="comp-switch" style="display: none;">
    <button class="show-screenshots et_pb_button active">Screenshots</button>
    <button class="show-comparison et_pb_button">Change Detection</button>
</div>

<script>
	showChangeDetectionPopup();
</script>

<style>
    <?php if(!empty($_GET['hide_details'])) { ?>
        .comparison-tiles{display:none;}
    <?php } ?>
</style>
