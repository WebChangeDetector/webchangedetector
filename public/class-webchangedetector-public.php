<?php

/**
 * The public-facing functionality of the plugin.
 *
 * @link       wp-mike.com
 * @since      1.0.0
 *
 * @package    WebChangeDetector
 * @subpackage WebChangeDetector/public
 */

/**
 * The public-facing functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the public-facing stylesheet and JavaScript.
 *
 * @package    WebChangeDetector
 * @subpackage WebChangeDetector/public
 * @author     Mike Miler <mike@wp-mike.com>
 */
class WebChangeDetector_Public {

	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $plugin_name    The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	private $version;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param      string    $plugin_name       The name of the plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {

		$this->plugin_name = $plugin_name;
		$this->version = $version;

	}

	/**
	 * Register the stylesheets for the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles() {

		wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/webchangedetector-public.css', array(), $this->version, 'all' );

	}



	/**
	 * Register the JavaScript for the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts() {

		wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/webchangedetector-public.js', array( 'jquery' ), $this->version, false );

	}

}

add_action('admin_menu', 'WebChangeDetector_plugin_setup_menu');

add_action( 'admin_enqueue_scripts', 'mm_admin_css' );

function mm_admin_css() {
   /* if ( 'post.php' == $hook ) {
        return;
    }*/

	wp_register_style( 'webchangedetector', plugin_dir_url( __FILE__ ) . '/css/style.css', false, '1.0.0' );
	wp_enqueue_style( 'custom_css' );
}

function WebChangeDetector_plugin_setup_menu(){
        add_menu_page( 'Web Change Detector',
                    'Web Change Detector',
                    'manage_options',
                    'webchangedetector',
                    'webchangedetector_init',
                    plugin_dir_url( __FILE__ ) . 'img/icon-wp-backend.svg');
}
 
function webchangedetector_init(){
	$postdata = $_POST;
	$get = $_GET;

	$wcd = new WebChangeDetector;

	// Actions without API key needed
	if( isset( $postdata['action'] ) ) {
		switch( $postdata['action'] ) {
			case 'create_free_account':
				$api_key = $wcd->create_free_account( $postdata );

				// If we didn't get an api key, put the error message out there and show the no-account-page

				if( isset( $api_key['status'] ) && $api_key['status'] == 'error') {
				    echo '<div class="error notice">
                                <p>' . $api_key['reason'] . '</p>
                            </div>';
                    echo $wcd->get_no_account_page();
                    return;
                }
				break;

            case 'reset_api_key':
                $api_key = get_option( 'webchangedetector_api_key' );
                $wcd->delete_website( $api_key );

                delete_option( 'webchangedetector_api_key' );
                break;

            case 'save_api_key':
                update_option( 'webchangedetector_api_key', $postdata['api-key'] );
                break;
		}
	}

	// Check for the account
	$account_keys = $wcd->verify_account();

	// The account doesn't have an api_key or activation_key
	if( !$account_keys ) {
		echo $wcd->get_no_account_page();
		return;
	}

	// The account is not activated yet, but the api_key is there already
	if( isset( $account_keys['api_key'] ) && isset( $account_keys['activation_key'] ) ) {

		if( isset( $postdata['action'] ) && $postdata['action'] == 'resend_confirmation_mail' ) {
			$wcd->resend_confirmation_mail( $account_keys['api_key'] );
			echo '<div class="updated notice">
   					<p>Email sent successfully.</p>
				</div>';

		}

		echo '<div class="error notice">
   					<p>Please <strong>activate</strong> your account by clicking the confirmation link in the email we sent you.</p>
				</div>
				<p>You didn\'t receive the email? Please also check your spam folder. To send the email again, please click the button below</p>
				<form action="/wp-admin/admin.php?page=webchangedetector&tab=take-screenshots" method="post">
					<input type="hidden" name="action" value="resend_confirmation_mail">
					<input type="submit" value="Send confirmation mail again" class="button">
				</form>';
		return;
	}

	// Set the api_key
	if( isset( $account_keys['api_key'] ) )
		$api_key = $account_keys['api_key'];
	else
		$api_key = false;

	$website_details = $wcd->get_website_details( $api_key );

	// Create website and groups if not exists yet
	if( !$website_details ) {
        $website_details = $wcd->create_group($api_key);
        $wcd->sync_posts( $website_details['auto_detection_group_id'], $website_details['manual_detection_group_id'] );

    }

	$group_id = $website_details['manual_detection_group_id'];
	$monitoring_group_id = $website_details['auto_detection_group_id'];

    $monitoring_group_settings = $wcd->get_monitoring_settings( $monitoring_group_id );

	// Perform actions
	if( isset( $postdata['action'] ) ) {
	    switch( $postdata['action'] ) {
            case 'take_screenshots':
                $results = $wcd->take_screenshot( $group_id, $api_key );

                if( isset( $results['error'] ) )
                    echo '<div class="error notice"><p>' . $results['error'] . '</p></div>';

                if( isset( $results['success'] ) )
                    echo '<div class="updated notice"><p>' . $results['success'] . '</p></div>';
                break;

            case 'update_monitoring_settings':
                $args = array(
                    'action'		=> 'update_monitoring_settings',
                    'group_id'		=> $monitoring_group_id,
                    'hour_of_day'	=> $postdata['hour_of_day'],
                    'interval_in_h'	=> $postdata['interval_in_h'],
                    'monitoring'    => $postdata['monitoring'],
                    'enabled'       => $postdata['enabled'],
                    'alert_email'	=> $postdata['alert_email'],
                    'group_name'    => $postdata['group_name']
                );
                $updated_monitoring_settings = mm_api( $args );
                break;

            case 'post_urls':
                // Get active posts from post data
                $active_posts = array();
                $count_selected = 0;
                foreach( $postdata as $key => $post_id ) {
                    if( strpos( $key, 'sc_id' ) === 0 ) {
                        $active_posts[] = array(
                            'sc_id'     => $post_id,
                            'url'       => get_permalink($post_id),
                            'active'    => $postdata['active-' . $post_id],
                            'desktop'   => $postdata['desktop-' . $post_id],
                            'mobile'    => $postdata['mobile-' . $post_id]
                        );
                        if( $postdata['active-' . $post_id] &&  $postdata['desktop-' . $post_id] )
                            $count_selected ++;

                        if( $postdata['active-' . $post_id] &&  $postdata['mobile-' . $post_id] )
                            $count_selected ++;
                    }
                }

                // Check if there is a limit for selecting URLs
                if( $website_details['enable_limits'] &&
                    $website_details['url_limit_manual_detection'] < $count_selected &&
                    $website_details['manual_detection_group_id'] == $postdata['group_id']) {
                    echo '<div class="error notice"><p>The limit for selecting URLs is ' .
                            $website_details['url_limit_manual_detection'] . '. 
                            You selected ' . $count_selected . ' URLs. The settings were not saved.</p></div>';

                } else if( $website_details['enable_limits'] &&
                            $website_details['sc_limit'] < $count_selected * ( 24 / $monitoring_group_settings['interval_in_h'] ) * 30 &&
                            $website_details['auto_detection_group_id'] == $postdata['group_id'] ) {

                             echo '<div class="error notice"><p>The limit for auto change detection is ' .
                                $website_details['sc_limit'] . '. per month. 
                                You selected ' . $count_selected * ( 24 / $monitoring_group_settings['interval_in_h'] ) * 30 . ' change detections. The settings were not saved.</p></div>';
                } else {
                    // Update API URLs
                    $wcd->update_urls($postdata['group_id'], $active_posts);
                    echo '<div class="updated notice"><p>Settings saved.</p></div>';
                }

                break;
        }
    }

	// Start view
	echo '<div class="webchangedetector">';
	echo '<h1>Web Change Detector</h1>';

	mm_tabs();

	echo '<div style="margin-top: 30px;"></div>';
	if( isset( $get['tab'] ) )
		$tab = $get['tab'];
	else
		$tab = 'take-screenshots';

	$client_details = $wcd->get_account_details( $api_key );
	$client_details = $client_details[0];

	$comp_usage = $client_details['usage'];
	if( $client_details['one_time'] ) {
		if( strtotime( "+1 month", strtotime( $client_details['start_date'] ) ) > date("U" ) )
			$limit = $client_details['comp_limit'];
		else
			$limit = 0;
	} else
		$limit = $client_details['comp_limit'];

	$available_compares = $limit - (int)$comp_usage;


	wp_enqueue_script( 'jquery-ui-accordion' );
	?>
	<script type="text/javascript">
	jQuery(function($){
		$(".accordion").accordion({ header: "h3", collapsible: true, active: false });
		$(".accordion").last().accordion("option", "icons", true);
	});
	</script>

	<?php

    // Show queued urls
    $args = array(
        'action'	=> 'get_queue',
        'group_id'	=> $group_id
    );
    $queue = mm_api( $args );

    if( !empty( $queue ) ) {
        echo '<div class="mm_processing_container">';
        echo '<h2>Currently Processing</h2>';

        echo '<table><tr><th>URL</th><th>Device</th><th>Status</th></tr>';

        foreach( $queue as $url ) {
            echo '<tr><td>' . $url['url'] . '</td><td>' . ucfirst( $url['device'] ) . '</td><td>Processing...</td></tr>';
        }
        echo '</table>';
        echo '</div>';
        echo '<hr>';
    }

	switch( $tab ) {

        /********************
         * Take Screenshot
         ********************/

		case 'take-screenshots':

            // Get amount selected Screenshots
            $args = array(
                'action'		=> 'get_amount_sc',
                'group_id'		=> $group_id
            );
            $amount_sc = mm_api( $args );

            if( !$amount_sc )
                $amount_sc = '0';

			echo '<h2>Select URLs</h2>';
			?>
			<div class="accordion">
				<div class="mm_accordion_title">
					<h3>
						Manual Compare URLs<br>
						<small>Currently selected: <strong><?= $amount_sc ?><?= $website_details['enable_limits'] ? " / " .  $website_details['url_limit_manual_detection'] : '' ?> </strong> URLs</small>
					</h3>
					<div class="mm_accordion_content">
						<?php $wcd->mm_get_url_settings( $group_id ) ?>
					</div>
				</div>
			</div>
			<?php
            if( !$website_details['enable_limits'] ) {
                echo '<h2>Do the magic</h2>';
                echo '<p>
					Your available balance is ' . $available_compares . ' / ' . $limit . '<br>
				<strong>Currently selected amount of compares: ' . $amount_sc . '</strong></p>';

                echo '<form action="/wp-admin/admin.php?page=webchangedetector&tab=take-screenshots" method="post">';
                echo '<input type="hidden" value="take_screenshots" name="action">';
                //echo '<input type="hidden" value="' . $api_key . '" name="api_key">';
                echo '<input type="submit" value="Start Manual Change Detection" class="button">';
                echo '</form>';
            }
			echo '<hr>';

			// Compare overview
			echo '<h2>Latest compares</h2>';
			$args = array(
				'action'	=> 'get_compares',
				'domain'	=> $_SERVER['SERVER_NAME'],
				'group_id'	=> $group_id

			);
			$compares = mm_api( $args );

			if( count( $compares ) == 0 )
				echo "There are no compares to show yet...";
			else {
				echo '<table><tr><th>URL</th><th>Device</th><th>Compare Date</th><th>Compared Screenshots</th><th>Difference</th><th>Compare Link</th></tr>';
				foreach( $compares as $key => $compare) {
					echo '<tr>';
					echo '<td>' . $compare['url'] . '</td>';
					echo '<td>' . ucfirst( $compare['device'] ) . '</td>';
					echo '<td>' . date( "d/m/Y H:i", $compare['timestamp'] ) . '</td>';
					echo '<td>' . date( "d/m/Y H:i", $compare['image1_timestamp'] ) . '<br>'. date( "d/m/Y H:i", $compare['image2_timestamp'] ) . '</td>';
					if( $compare['difference_percent'] )
						$class = 'is-difference';
					else
						$class = 'no-difference';
					echo '<td class="' . $class . '">' . $compare['difference_percent'] . ' %</td>';
					//echo '<td><a href="' . $compare['link'] . '" target="_blank">Show compare image</a></td>';
					echo '<td><form action="/wp-admin/admin.php?page=webchangedetector&tab=show-compare" method="post">';
					echo '<input type="hidden" name="action" value="show_compare">';
					echo '<input type="hidden" name="compare_id" value="' . $compare['ID'] . '">';
					echo '<input class="button" type="submit" value="Show Compare">';
					echo '</form></td>';
					echo '</tr>';
				}
				echo '</table>';
			}
			break;

		/************************
         * Monitoring Screenshots
         * **********************/

		case 'monitoring-screenshots':


            //Amount selected Monitoring Screenshots
            $args = array(
                'action'		=> 'get_amount_sc',
                'group_id'		=> $monitoring_group_id
            );
            $amount_sc_monitoring = mm_api( $args );

            if( !$amount_sc_monitoring )
                $amount_sc_monitoring = '0';

            $group_settings = $wcd->get_monitoring_settings( $monitoring_group_id );

			echo '<h2>Select URLs</h2>';

			?>
			<div class="accordion">
				<div class="mm_accordion_title">
					<h3>
						Monitoring Compare URLs<br>
						<small>Currently selected: <strong><?= $amount_sc_monitoring ?></strong> URLs</small>
					</h3>
					<div class="mm_accordion_content">
						<?php $wcd->mm_get_url_settings( $monitoring_group_id, true ) ?>
					</div>
				</div>
			</div>

			<h2>Settings for Monitoring</h2>
			<p>
				The current settings require <strong><?= $amount_sc_monitoring * ( 24 / $group_settings['interval_in_h'] ) * 30 ?></strong> change detections per month.<br>
				Your available change detections are <strong>
                    <?php
                    if( $website_details['enable_limits'] )
                        echo $website_details['sc_limit'] . " / month";
                    else
                        echo $available_compares . ' / ' . $limit;
                    ?>
                </strong>.
			<p>
			<form action="/wp-admin/admin.php?page=webchangedetector&tab=monitoring-screenshots" method="post">
                <input type="hidden" name="action" value="update_monitoring_settings">
                <input type="hidden" name="monitoring" value="1">
                <input type="hidden" name="group_name" value="<?= $group_settings['group_name'] ?>">
				<p>
					<label for="enabled">Enabled</label>
					<select name="enabled">
						<option value="1" <?= isset( $group_settings['enabled'] ) && $group_settings['enabled'] == '1' ? 'selected' : ''; ?>>Yes</option>
						<option value="0" <?= isset( $group_settings['enabled'] ) && $group_settings['enabled'] == '0' ? 'selected' : ''; ?>>No</option>
					</select>
				</p>
				<p>
				<label for="hour_of_day">Hour of the day</label>
				<select name="hour_of_day" >
					<?php
					for( $i=0; $i < 24; $i++ ) {
						if( isset( $group_settings['hour_of_day'] ) && $group_settings['hour_of_day'] == $i )
							$selected = 'selected';
						else
							$selected = '';
						echo '<option value="' . $i . '" ' . $selected . '>' . $i . ':00</option>';
					}
					?>

				</select>
				</p>
				<p>
				<label for="interval_in_h">Enable Monitoring</label>
				<select name="interval_in_h">
					<option value="1" <?= isset( $group_settings['interval_in_h'] ) && $group_settings['interval_in_h'] == '1' ? 'selected' : ''; ?>>
						Every 1 hour (720 Compares / URL / month)
					</option>
					<option value="3" <?= isset( $group_settings['interval_in_h'] ) && $group_settings['interval_in_h'] == '3' ? 'selected' : ''; ?>>
						Every 3 hours (240 Compares / URL / month)
						</option>
					<option value="6" <?= isset( $group_settings['interval_in_h'] ) && $group_settings['interval_in_h'] == '6' ? 'selected' : ''; ?>>
						Every 6 hours (120 Compares / URL /  month)
						</option>
					<option value="12" <?= isset( $group_settings['interval_in_h'] ) && $group_settings['interval_in_h'] == '12' ? 'selected' : ''; ?>>
						Every 12 hours (60 Compares / URL /  month)
						</option>
					<option value="24" <?= isset( $group_settings['interval_in_h'] ) && $group_settings['interval_in_h'] == '24' ? 'selected' : ''; ?>>
						Every 24 hours (30 Compares / URL /  month)
					</option>
				</select>
				</p>
				<p>
				<label for="alert_email">Email address for alerts</label>
				<input type="text" name="alert_email" value="<?= isset( $group_settings['alert_email'] ) ? $group_settings['alert_email'] : '' ?> ">
				</p>
				<input class="button" type="submit" value="Save" >
			</form>

			<?php

			// Compare overview
			echo '<h2>Latest compares</h2>';
			$args = array(
				'action'	=> 'get_compares',
				'domain'	=> $_SERVER['SERVER_NAME'],
				'group_id'	=> $monitoring_group_id
			);
			$compares = mm_api( $args );

			if( count( $compares ) == 0 )
				echo "There are no compares to show yet...";
			else {
				echo '<table><tr><th>URL</th><th>Device</th><th>Compare Date</th><th>Compared Screenshots</th><th>Difference</th><th>Compare Link</th></tr>';
				foreach( $compares as $key => $compare) {
					echo '<tr>';
					echo '<td>' . $compare['url'] . '</td>';
					echo '<td>' . $compare['device'] . '</td>';
					echo '<td>' . date( "d/m/Y H:i", $compare['timestamp'] ) . '</td>';
					echo '<td>' . date( "d/m/Y H:i", $compare['image1_timestamp'] ) . '<br>'. date( "d/m/Y H:i", $compare['image2_timestamp'] ) . '</td>';
					if( $compare['difference_percent'] )
						$class = 'is-difference';
					else
						$class = 'no-difference';
					echo '<td class="' . $class . '">' . $compare['difference_percent'] . ' %</td>';
					//echo '<td><a href="' . $compare['link'] . '" target="_blank">Show compare image</a></td>';
					echo '<td><form action="/wp-admin/admin.php?page=webchangedetector&tab=show-compare" method="post">';
					echo '<input type="hidden" name="action" value="show_compare">';
					echo '<input type="hidden" name="compare_id" value="' . $compare['ID'] . '">';
					echo '<input class="button" type="submit" value="Show Compare">';
					echo '</form></td>';
					echo '</tr>';
				}
				echo '</table>';
			}
			break;

        /********************
         * Settings
         ********************/

		case 'settings':

			if( !$api_key ) {
				echo '<div class="error notice">
    				<p>Please enter a valid API Key.</p>
				</div>';
			} else if( !$website_details['enable_limits'] ) {

				echo '<h2>Your credits</h2>';
				echo 'Your current plan: <strong>' . $client_details['plan_name'] . '</strong><br>';

				$start_date = strtotime( $client_details['start_date'] );

				// Calculate end of one-time plans
				if( $client_details['one_time'] ) {

					$end_of_trial = strtotime( "+1 month ", $start_date );
					echo 'Your compares are valid until <strong>' . date( "d/m/Y" , $end_of_trial ) . '</strong>.<br>Please upgrade your account to renew your balance afterwards.';

				} else {
					// Calculate next renew date
					$renew_current_month = mktime( 0,0,0, date("m"), date("d", $start_date), date("Y" ) );
					$today = date("U");

					if( $today > $renew_current_month )
						$renew_date = strtotime( "+1 month", $renew_current_month );
					else
						$renew_date = $renew_current_month;

					echo 'Next renew: ' . date( "d/m/Y" , $renew_date );
					//if( !$client_details['one_time'] ) {

				}
				echo '<p>Compares in this period: ' . $limit . '<br>';
				echo 'Used compares: ' . $comp_usage . '<br>';
				echo 'Available compares in this period: ' . $available_compares . '</p>';


                $args = array(
                    'action'	=> 'get_upgrade_options',
                    'plan_id'	=> (int)$client_details['plan_id']
                );
                echo mm_api( $args );
            }
			echo $wcd->get_api_key_form( $api_key );
			break;

        /*******************
         * Help
         *******************/
		case 'help':

			echo '<h2>How it works:</h2>';
			echo '<p>
					<strong>Manual Change Detection</strong><br>
					Here you can select the pages of your website and manually take the screenshots.
					Use this Manual Change Detection for e.g. when you perform updates on your website. Run a change detection
					before and after the update and you will see if there are differences on the selected pages.
					<ol>
						<li>Select the urls and the devices (desktop and / or mobile) you want to take a screenshot.</li>
						<li>Hit the Button "Start Manual Detection". The Detection might take couple of minutes. </li>
						<li>When the manual detections are finished, you can see the results below the settings at "Latest compares"</li>
					</ol>
					</p>
					<p>
					<strong>Auto Change Detection</strong><br>
					Use the monitoring to automatically take and compare screenshots in a specific interval.
					When there are differences in a compare, you will automatically receive an alert email.
					<ol>
						<li>Select the urls you want to auto detect.</li>
						<li>Select the interval and the hour of day for the first screenshot to be taken. Please be aware
						 that compares will be only performed when you have enough credit available.</li>
						<li>You find all auto detections below the settings</li>
					</ol>
					At the Tab "Settings" you have an overview of your usage and limits. You can also up- or downgrade your package.
					</p>';
			break;

		case 'show-compare':
			$args = array(
				'action'		=> 'show_compare',
				'compare_id'	=> $_POST['compare_id']
			);

			echo mm_api( $args );
	}
	echo '</div>'; // closing from div webchangedetector
}

function isJson($string) {
 json_decode($string);
 return (json_last_error() == JSON_ERROR_NONE);
}

function mm_tabs() {
    //settings_errors();

    $args = array(
        'action'    => 'get_client_website_details',
        'domain'    => $_SERVER['HTTP_HOST']
    );

    $restrictions = mm_api( $args );

    $restrictions = $restrictions[0];
    //var_dump( $restrictions );

    if( isset( $_GET[ 'tab' ] ) ) {
        $active_tab = $_GET[ 'tab' ];
    } else
        $active_tab = 'take-screenshots';



    ?>
    <div class="wrap">
        <h2 class="nav-tab-wrapper">
            <?php if( !$restrictions['enable_limits'] || $restrictions['allow_manual_detection'] ) { ?>
            <a href="?page=webchangedetector&tab=take-screenshots" class="nav-tab <?php echo $active_tab == 'take-screenshots' ? 'nav-tab-active' : ''; ?>">Manual Change Detection</a>
            <?php }

            if( !$restrictions['enable_limits'] || $restrictions['allow_auto_detection'] ) { ?>
            <a href="?page=webchangedetector&tab=monitoring-screenshots" class="nav-tab <?php echo $active_tab == 'monitoring-screenshots' ? 'nav-tab-active' : ''; ?>">Auto Change Detection</a>
            <?php } ?>
            <a href="?page=webchangedetector&tab=settings" class="nav-tab <?php echo $active_tab == 'settings' ? 'nav-tab-active' : ''; ?>">Settings</a>
            <a href="?page=webchangedetector&tab=help" class="nav-tab <?php echo $active_tab == 'help' ? 'nav-tab-active' : ''; ?>">Help</a>
        </h2>
    </div>

<?php
}