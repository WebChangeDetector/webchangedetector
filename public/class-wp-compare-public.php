<?php

/**
 * The public-facing functionality of the plugin.
 *
 * @link       wp-mike.com
 * @since      1.0.0
 *
 * @package    Wp_Compare
 * @subpackage Wp_Compare/public
 */

/**
 * The public-facing functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the public-facing stylesheet and JavaScript.
 *
 * @package    Wp_Compare
 * @subpackage Wp_Compare/public
 * @author     Mike Miler <mike@wp-mike.com>
 */
class Wp_Compare_Public {

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

		wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/wp-compare-public.css', array(), $this->version, 'all' );

	}



	/**
	 * Register the JavaScript for the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts() {

		wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/wp-compare-public.js', array( 'jquery' ), $this->version, false );

	}

}

add_action('admin_menu', 'wp_compare_plugin_setup_menu');

add_action( 'admin_enqueue_scripts', 'mm_admin_css' );

function mm_admin_css() {
   /* if ( 'post.php' == $hook ) {
        return;
    }*/

	wp_register_style( 'wp-compare', plugin_dir_url( __FILE__ ) . '/css/style.css', false, '1.0.0' );
	wp_enqueue_style( 'custom_css' );
}

function wp_compare_plugin_setup_menu(){
        add_menu_page( 'WP Compare', 'WP Compare', 'manage_options', 'wp-compare', 'wp_compare_init' );
}
 
function wp_compare_init(){
	$postdata = $_POST;
	$get = $_GET;

	$wp_comp = new WP_COMPARE;

	// Actions without API key needed
	if( isset( $postdata['action'] ) ) {
		switch( $postdata['action'] ) {
			case 'create_free_account':
				$api_key = $wp_comp->create_free_account( $postdata );

				// If we didn't get an api key, put the error message out there and show the no-account-page
           
				if( isset( $api_key['status'] ) && $api_key['status'] == 'error') {
				    echo '<div class="error notice">
                                <p>' . $api_key['reason'] . '</p>
                            </div>';
                    echo $wp_comp->get_no_account_page();
                    return;
                }
				break;

            case 'reset_api_key':
                $api_key = get_option( 'wpcompare_api_key' );
                $group_id = get_option( 'wpcompare_group_id' );
                $monitoring_group_id = get_option( 'wpcompare_monitoring_group_id' );

                $wp_comp->delete_group( $group_id, $api_key );
                $wp_comp->delete_group( $monitoring_group_id, $api_key );

                delete_option( 'wpcompare_api_key' );
                delete_option( 'wpcompare_group_id' );
                delete_option( 'wpcompare_monitoring_group_id' );
                break;

            case 'save_api_key':
                update_option( 'wpcompare_api_key', $postdata['api-key'] );
                delete_option( 'wpcompare_group_id' );
                delete_option( 'wpcompare_monitoring_group_id' );
                break;
		}
	}

	// Check for the account
	$account_keys = $wp_comp->verify_account();

	// The account doesn't have an api_key or activation_key
	if( !$account_keys ) {
		echo $wp_comp->get_no_account_page();
		return;
	}

	// The account is not activated yet, but the api_key is there already
	if( isset( $account_keys['api_key'] ) && isset( $account_keys['activation_key'] ) ) {

		if( isset( $postdata['action'] ) && $postdata['action'] == 'resend_confirmation_mail' ) {
			$wp_comp->resend_confirmation_mail( $account_keys['api_key'] );
			echo '<div class="updated notice">
   					<p>Email sent successfully.</p>
				</div>';

		}

		echo '<div class="error notice">
   					<p>Please <strong>activate</strong> your account by clicking the confirmation link in the email we sent you.</p>
				</div>
				<p>You didn\'t receive the email? Please also check your spam folder. To send the email again, please click the button below</p>
				<form action="/wp-admin/admin.php?page=wp-compare&tab=take-screenshots" method="post">
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

	// Create group if not exists yet
	$group_id = get_option( 'wpcompare_group_id' );
	$monitoring_group_id = get_option( 'wpcompare_monitoring_group_id' );

	if( !$group_id || !$monitoring_group_id )
		$wp_comp->create_group( $api_key );

	if( isset( $postdata['action'] ) ) {
	    switch( $postdata['action'] ) {
            case 'take_screenshots':
                $results = $wp_comp->take_screenshot( $group_id, $api_key );

                if( isset( $results['error'] ) )
                    echo '<div class="error notice"><p>' . $results['error'] . '</p></div>';

                if( isset( $results['success'] ) )
                    echo '<div class="updated notice"><p>' . $results['success'] . '</p></div>';
                break;

            case 'update_monitoring_settings':
                $args = array(
                    'action'		=> 'update_monitoring_settings',
                    'group_id'		=> $monitoring_group_id,
                    'hour'			=> $postdata['hour'],
                    'interval_in_h'	=> $postdata['interval_in_h'],
                    'alert_email'	=> $postdata['alert_email']
                );
                $updated_monitoring_settings = mm_api( $args );
                break;

            case 'post_urls':
                // Get active posts from post data
                $active_posts = array();
                foreach( $postdata as $key => $post_id ) {
                    if( strpos( $key, 'pid' ) === 0 )
                        $active_posts[] = array(
                            'wp_post_id'	=> $post_id,
                            'url'			=> get_permalink( $post_id ),
                            'desktop'		=> $postdata['desktop-' . $post_id],
                            'mobile'		=> $postdata['mobile-' . $post_id]
                        );
                }

                // Update API URLs
                $args = array(
                    'action'		=> 'update_urls',
                    'group_id'		=> $postdata['group_id'],
                    'posts'			=> json_encode( $active_posts ),
                );
                $results = mm_api( $args );
                break;
        }
    }

	echo '<div class="mm_wp_compare">';
	echo '<h1>WP Compare</h1>';

	mm_tabs();

	echo '<div style="margin-top: 30px;"></div>';
	if( isset( $get['tab'] ) )
		$tab = $get['tab'];
	else
		$tab = 'take-screenshots';

	$client_details = $wp_comp->get_account_details( $api_key );
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

	switch( $tab ) {

        /********************
         * Take Screenshot
         ********************/

		case 'take-screenshots':

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
						<small>Currently selected: <strong><?= $amount_sc ?></strong> URLs</small>
					</h3>
					<div class="mm_accordion_content">
						<?php $wp_comp->mm_get_url_settings( $group_id ) ?>
					</div>
				</div>
			</div>
			<?php

			echo '<h2>Do the magic</h2>';
			echo '<p>
					Your available balance is ' . $available_compares . ' / ' . $limit . '<br>
				<strong>Currently selected amount of compares: ' . $amount_sc . '</strong></p>';

			echo '<form action="/wp-admin/admin.php?page=wp-compare&tab=take-screenshots" method="post">';
			echo '<input type="hidden" value="take_screenshots" name="action">';
			//echo '<input type="hidden" value="' . $api_key . '" name="api_key">';
			echo '<input type="submit" value="Take & Compare Screenshots" class="button">';
			echo '</form>';
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
					echo '<td><form action="/wp-admin/admin.php?page=wp-compare&tab=show-compare" method="post">';
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

			$args = array(
				'action'	=> 'get_monitoring_settings',
				'group_id'	=> $monitoring_group_id
			);
			$group_settings = mm_api( $args );
			$group_settings = $group_settings[0];
			echo '<h2>Select URLs</h2>';

			?>
			<div class="accordion">
				<div class="mm_accordion_title">
					<h3>
						Monitoring Compare URLs<br>
						<small>Currently selected: <strong><?= $amount_sc_monitoring ?></strong> URLs</small>
					</h3>
					<div class="mm_accordion_content">
						<?php $wp_comp->mm_get_url_settings( $monitoring_group_id, true ) ?>
					</div>
				</div>
			</div>

			<h2>Settings for Monitoring</h2>
			<p>
				The current settings require <strong><?= $amount_sc_monitoring * ( 24 / $group_settings['interval_in_h'] ) * 30 ?></strong> compares per month.<br>
				Your available compares are <strong><?= $available_compares . ' / ' . $limit ?></strong>.
			<p>
			<form action="/wp-admin/admin.php?page=wp-compare&tab=monitoring-screenshots" method="post">
			<input type="hidden" name="action" value="update_monitoring_settings">
				<p>
					<label for="monitoring">Enable Monitoring</label>
					<select name="monitoring">
						<option value="1" <?= isset( $group_settings['monitoring'] ) && $group_settings['monitoring'] == '1' ? 'selected' : ''; ?>>Yes</option>
						<option value="0" <?= isset( $group_settings['monitoring'] ) && $group_settings['monitoring'] == '0' ? 'selected' : ''; ?>>No</option>
					</select>
				</p>
				<p>
				<label for="hour">Hour of the day</label>
				<select name="hour" >
					<?php
					for( $i=0; $i < 24; $i++ ) {
						if( isset( $group_settings['hour'] ) && $group_settings['hour'] == $i )
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
					echo '<td><form action="/wp-admin/admin.php?page=wp-compare&tab=show-compare" method="post">';
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
			} else {

				echo '<h2>Your credits</h2>';
				echo 'Your current plan: <strong>' . $client_details['name'] . '</strong><br>';

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

			}
            $args = array(
                'action'	=> 'get_upgrade_options',
                'plan_id'	=> (int)$client_details['plan_id']
            );

            echo mm_api( $args );

			echo $wp_comp->get_api_key_form( $api_key );
			break;

        /*******************
         * Help
         *******************/
		case 'help':

			echo '<h2>How it works:</h2>';
			echo '<p>
					<strong>Manual Change Detection</strong><br>
					Here you can select the pages of your website and manually take the screenshots.
					Use this Method, when you want to perform updates on your website. Take and compare screenshots
					before and after the update and you will see if there are differences on the selected pages.
					<ol>
						<li>Select the urls you want to take a screenshot.</li>
						<li>Hit the Button "Take & Compare Screenshots". The URLs will be added to our queue. Below you can see all URLs in the queue</li>
						<li>When the screenshots and compares are finished, you can see the compare at "Latest Compares"</li>
					</ol>
					</p>
					<p>
					<strong>Monitoring Change Detection</strong><br>
					Use the monitoring to automatically take and compare screenshots in a specific interval.
					When there are differences in a compare, you will automatically receive an alert email.
					<ol>
						<li>Select the urls you want monitor.</li>
						<li>Select the interval and the hour of day for the first screenshot to be taken. Please be aware
						 that compares will be only performed when you have enough credit available.</li>
						<li>You find all monitoring compares "Latest Compares"</li>
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
	echo '</div>'; // closing from div mm_wp_compare
}

function isJson($string) {
 json_decode($string);
 return (json_last_error() == JSON_ERROR_NONE);
}

function mm_tabs() {
    settings_errors();
    if( isset( $_GET[ 'tab' ] ) ) {
        $active_tab = $_GET[ 'tab' ];
    }
    ?>
    <div class="wrap">
        <h2 class="nav-tab-wrapper">
            <a href="?page=wp-compare&tab=take-screenshots" class="nav-tab <?php echo $active_tab == 'take-screenshots' ? 'nav-tab-active' : ''; ?>">Manual Change Detection</a>
            <a href="?page=wp-compare&tab=monitoring-screenshots" class="nav-tab <?php echo $active_tab == 'monitoring-screenshots' ? 'nav-tab-active' : ''; ?>">Monitoring Change Detection</a>
            <a href="?page=wp-compare&tab=settings" class="nav-tab <?php echo $active_tab == 'settings' ? 'nav-tab-active' : ''; ?>">Settings</a>
            <a href="?page=wp-compare&tab=help" class="nav-tab <?php echo $active_tab == 'help' ? 'nav-tab-active' : ''; ?>">Help</a>
        </h2>
    </div>

<?php
}