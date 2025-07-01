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
	 * @param      string $plugin_name       The name of the plugin.
	 * @param      string $version    The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {
		$this->plugin_name = $plugin_name;
		$this->version     = $version;
	}

	/**
	 * Register the stylesheets for the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles() {
		wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/wp-compare-public.css', array(), $this->version, 'all' );
		wp_enqueue_style( 'twentytwenty-css', plugin_dir_url( __FILE__ ) . 'css/twentytwenty.css', array(), $this->version, 'all' );
		wp_enqueue_style( 'dashicons' );
		wp_enqueue_style( 'wp-codemirror' );
		wp_enqueue_style( 'codemirror-darcula', plugin_dir_url( __FILE__ ) . 'css/darcula.css', array(), $this->version, 'all' );
        //wp_enqueue_style( 'bulma', plugin_dir_url( __FILE__ ) . 'css/bulma.css', array(), $this->version, 'all' );
	}



	/**
	 * Register the JavaScript for the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts() {
		wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/wp-compare-public.js', array( 'jquery' ), $this->version, false );
		wp_enqueue_script( 'twentytwenty-js', plugin_dir_url( __FILE__ ) . 'js/jquery.twentytwenty.js', array( 'jquery' ), $this->version, false );
		wp_enqueue_script( 'twentytwenty-move-js', plugin_dir_url( __FILE__ ) . 'js/jquery.event.move.js', array( 'jquery' ), $this->version, false );
		wp_enqueue_script( 'wcd_ajax', plugin_dir_url( __FILE__ ) . 'js/wcd-ajax.js', array( 'jquery' ), $this->version, false );
		wp_enqueue_script( 'jquery-ui-accordion' );
		wp_enqueue_script( 'code-editor', '/wp-admin/js/code-editor.min.js', array( 'jquery' ), $this->version, false );

		// Load WP codemirror
		$css_settings              = array(
			'codemirror' => array( 'theme' => 'darcula' ),
		);
		$cm_settings['codeEditor'] = wp_enqueue_code_editor( $css_settings );
		wp_localize_script( 'jquery', 'cm_settings', $cm_settings );
		wp_enqueue_script( 'wp-theme-plugin-editor' );
	}
}

// Enable shortcodes in navigation
add_filter( 'wp_nav_menu_items', 'do_shortcode' );

add_action( 'user_registration_after_register_user_action', 'create_api_account', 20, 3 );
function create_api_account( $form_data, $form_id, $user_id ) {
	$user = get_user_by( 'id', $user_id );

	$args      = array(
		'action'     => 'add_paid_account',
		'email'      => $user->user_email,
		'name_first' => $user->first_name ? $user->first_name : 'n/a',
		'name_last'  => $user->last_name ? $user->last_name : 'n/a',
		'plan_id'    => TRIAL_ACCOUNT_ID,
		'secret'     => 'beGaFWUCYnveZU0os9Pd7vWA07KeDmIB',
	);
	$api_token = mm_api( $args );
	add_user_meta( $user_id, 'wpcompare_api_token', $api_token, true );
}

// Shortcode for upgrade url
add_shortcode( 'mm_get_upgrade_url', 'get_upgrade_url' );
function get_upgrade_url() {
	$protocol = ! empty( $_SERVER['HTTPS'] ) ? 'https://' : 'http://';
	return $protocol . $_SERVER['SERVER_NAME'] . '/webchangedetector/?tab=upgrade';
}

add_shortcode( 'wcd_generate_new_api_token', 'wcd_generate_new_api_token' );
function wcd_generate_new_api_token() {
	if (!current_user_can('manage_options')) {
		return 'Insufficient permissions';
	}
	$api_token = bin2hex(random_bytes(40 / 2));
	$db_token = hash('sha256', $api_token);
	return "Api Token: " . $api_token . "<br>DB Token: " . $db_token . "<br>";
}

add_shortcode( 'mm_url_preview', 'mm_url_preview' );
function mm_url_preview( $atts = array() ) {
	ob_start();
	$display = 'block';
	if ( ! empty( $atts['display'] ) ) {
		$display = $atts['display'];
	} ?>

	<div id="cta-preview-screenshot" style="display: <?php echo $display; ?>">

		<form id="ajax_screenshot_preview" method="get" style="width: 100%;">
			<input type="hidden" name="action" value="preview_screenshot">
			<input id="preview_url" type="text" name="url"  placeholder="Your Website (e.g. www.google.com)">

			<input id="preview_type" type="hidden" name="type" value="general">
			<input type="submit" value="Go" class="et_pb_button" style="width: 200px; background: #f0f0ff;">
		</form>
		<div id="preview_in_progress" style="display:none; text-align: center; padding-top: 50px;">
			<div><canvas id="spinner" width="300" height="300"></div>
		</div>

		<div id="preview_screenshot">
			<span class="dashicons dashicons-welcome-widgets-menus" ></span><br>
			<p>Your preview screenshot will appear here.</p>
		</div>
		<form id="ajax_screenshot_preview_signup" method="post" style="width: 100%;">
			<div style="width: 100%; display: inline-block">
				<label for="email" >Your email address</label>
				<input id="preview_email" name="email" type="email" placeholder="Your alert email address (e.g. alert@gmail.com)" required>
			</div>
			<input type="hidden" name="interval_in_h" value="24">
			<div style="width: calc(25% - 5px); display: none;">
				<label for="device" >Screen size</label>
				<select name="device" id="preview_device">
					<option value="desktop">Desktop</option>
					<option value="mobile">Mobile</option>
				</select>
			</div>
			<input id="preview_hour_of_day" type="hidden" name="hour_of_day" value='<?php echo (int) date( 'H' ) + 1; ?>'>

			<div id="btn-start-change-detection" >
				<p id="frm_error_msg"></p>
				<button type="submit" class="et_pb_button cta-button">Start Free Monitoring<br>
				<span id="enable-btn-url" style="display: none;">for : <span id="btn-url"></span></span></button>
			</div>
		</form>
	</div>
	<?php
	return ob_get_clean();
}

add_shortcode( 'mm_wp_urls_preview', 'mm_wp_urls_preview' );
function mm_wp_urls_preview() {
	ob_start();
	?>
	<div id="cta-wp-urls">
		<!-- <form id="" class="ajax_get_wp_post_types" method="post" style="width: 100%;"> -->
		<div style="width: 100%; text-align: center">
			<img src="/wp-content/plugins/app/public/img/manual-check-steps.png" style="max-width: 700px;">
		</div>
		<form id="" class="ajax_get_preview_wp_urls" method="post" style="width: 100%;">
			<input type="hidden" name="action" value="get_preview_wp_urls">
			<!-- <input type="hidden" name="action" value="get_wp_post_types">-->
			<input id="preview_device" type="hidden" name="device" value="desktop">
			<input id="preview_type" type="hidden" name="type" value="wp">
			<input id="preview_url" type="text" name="domain" style="width: calc(100% - 204px); height: 50px; padding-left: 10px;" placeholder="Domain of your WordPress website (e.g. www.wp-mike.com)" >
			<input type="submit" value="Go" class="et_pb_button" style="width: 200px;">
		</form>

		<div id="preview_wp_urls_in_progress" style="display:none; text-align: center; padding-top: 50px;">
		<img src='/wp-content/plugins/app/public/img/loader.gif'>
			<h3>Collecting URLs...</h3>
		</div>
		<form id="ajax_urls_preview" method="get" style="width: 100%;">
			<div id="preview_wp_urls" style=""></div>

			<div id="btn-start-change-detection" style="display: none;">
				<input type="submit" value="Start Checks" href="/free-trial/" class="et_pb_button cta-button">
			</div>
		</form>
	</div>

	<?php
	return ob_get_clean();
}

add_shortcode( 'mm_redirect_logged_in_user', 'mm_redirect_logged_in_user' );
function mm_redirect_logged_in_user() {
	$user_id = get_current_user_id();
	if ( $user_id && ! is_admin() && $_SERVER['REQUEST_URI'] == '/webchangedetector' ) {
		exit;
	}
}

if ( isset( $_GET['check-url'] ) ) {
	return mm_check_url( $_GET['check_url'] );
}

// Redirect to app if user login page was called and we are loggedin already.
function wcd_redirect_logged_in_users()
{
    if(trim($_SERVER['REQUEST_URI'], "/") === 'login' && is_user_logged_in()) {
         wp_redirect('/webchangedetector/');
         exit;
    }
}
add_action('wp', 'wcd_redirect_logged_in_users');

/** We don't have a public v2 comparison route by api token (yet). So we get v1 param and return v2 params.
* @param $compare_v1
* @return array
 */
function compare_v1_to_v2($compare_v1) {
    $compare_v2 = [
            "id" => $compare_v1['uuid'],
            "screenshot_1" => $compare_v1['screenshot1']['uuid'],
            "screenshot_2" => $compare_v1['screenshot2']['uuid'],
            "group" => $compare_v1['group']['uuid'],
            "url" => $compare_v1['screenshot1']['url'],
            "html_title" => $compare_v1['screenshot1']['queue']['url']['html_title'],
            "batch" => false,
            "batch_name" => $compare_v1['screenshot1']['monitoring'] ? 'Monitoring' : 'Manual Checks',
            "screenshot_1_updated_at" => $compare_v1['screenshot1']['updated_at'],
            "screenshot_2_updated_at" => $compare_v1['screenshot2']['updated_at'],
            "device" => $compare_v1['screenshot1']['device'],
            "monitoring" => $compare_v1['screenshot1']['monitoring'],
            "cms" => $compare_v1['group']['cms'],
            "group_name" => $compare_v1['group']['name'],
            "screenshot_1_link" => $compare_v1['screenshot1']['link'],
            "screenshot_2_link" => $compare_v1['screenshot2']['link'],
            "link" => $compare_v1['link'],
            "public_link" => "https =>//www.webchangedetector.com/show-change-detection?token=" . $compare_v1['token'],
            "difference_percent" => $compare_v1['difference_percent'],
            "difference_dimension" => false,
            "threshold" => $compare_v1['threshold'],
            "status" => $compare_v1['status'],
    ];
    return $compare_v2;
}

// TODO rename shortcode to get_comparison_by_token
add_shortcode( 'get_comparison_partial', 'get_comparison_by_token' );
function get_comparison_by_token( $atts ) {
	$att = shortcode_atts(
		array(
			'token'       => false,
			'hide-switch' => false,
		),
		$atts
	);

	if ( $att['token'] ) {
		$token = $att['token'];
	} elseif ( ! empty( $_GET['token'] ) ) {
		$token = $_GET['token'];
	}
	if ( isset( $token ) ) {
		$wcd         = new Wp_Compare();
       
        // We don't have a route for public comparison in api v2 yet.
        $compare = $wcd->get_comparison_by_token($token);
        $compare = compare_v1_to_v2($compare);

		$public_page = true;

		ob_start();
		
		include wcd_get_plugin_dir() . 'public/partials/change-detection-content.php';
	
		return ob_get_clean();
	}
	return 'Ooops! We didn\'t understand the request. Please contact us if the issue persists.';
}

// Add plan as cookie. This is used to redirect to the plan page.
add_action('init', function() {
    if (isset($_GET['plan']) ) {
        $plan = sanitize_text_field($_GET['plan']);
        setcookie('wcd-plan', $plan, time() + 30 * DAY_IN_SECONDS, COOKIEPATH, COOKIE_DOMAIN);
        $_COOKIE['wcd-plan'] = $plan;
    }
});

add_shortcode( 'wp_compare_agency', 'wcd_content' );
function wcd_content( $tab = false, $subtab = false ) {
	$postdata = $_POST;
	$user_id  = get_current_user_id();
	$wp_comp  = new Wp_Compare();

	ob_start();
	// Show error message if login to account failed
	if ( ! $user_id && mm_env() !== 'local' ) {
		echo '<div style="text-align: center; border: 1px solid #aaa; padding: 20px; margin: 100px auto; max-width: 600px; font-size: 18px; line-height: 22px;">
                <p style="margin-bottom: 20px;">You are not logged in.</p>
            <a class="et_pb_button" href="' . MM_APP_URL_PRODUCTION . '/login">Login</a>';
        wp_redirect('/login');
		header('Location: /login');
		exit;
	}
    echo '<div id="wcd_content">';
    echo '<div id="mm_loading"><div class="spinner"></div></div>';
    echo '<div id="success-message"></div>';

    $api_token = mm_api_token();

	// Show error when there is no api token for the user
	if ( ! $api_token ) {
		echo $wp_comp->get_no_account_page();
		return ob_get_clean();
	}

	// Check if the account is activated already
	$client_account = $wp_comp->get_account_details_v2();
    $wp_comp->account_details = $client_account;

	// Failed api request
	/*if ( ! $client_account ) {
		echo mm_message( array( 'error', 'Something went wrong. Please try again.<span style="display: none">' . print_r( $client_account, 1 ) . '</span>' ) );
		return false;
	}*/

	// Show activate account page
	if ( $client_account === 'activate account' || $client_account === 'ActivateAccount' ) {
		$wp_comp->show_activate_account();
		return false;
	}

	// Redirect after login
	if ( isset( $_COOKIE['wcd-redirect'] ) ) {
		$redirectTo = $_COOKIE['wcd-redirect'];
		?>
		<script>
			document.cookie = "wcd-redirect=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/";
			window.location.href = '<?php echo $redirectTo; ?>';
		</script>
		<?php
	}

	// TODO: Currently not used. This was for the preview screenshot cta thing.
	// Add url if the cookies are set and delete the cookies
	// if ( isset( $_COOKIE['wcd-domain'] ) && isset( $_COOKIE['wcd-device'] ) && isset( $_COOKIE['wcd-type'] ) ) {
	// 	if ( ! empty( $_COOKIE['wcd-domain'] ) && $_COOKIE['wcd-check-type'] !== 'manual' ) {
	// 		$auto_group = $wp_comp->get_user_groups_and_urls( null, 'auto', false, 1, 0, 1 )[0];

	// 		if ( $auto_group && count( $auto_group['urls'] ) < 1 ) {
	// 			if ( $_COOKIE['wcd-type'] == 'general' ) {
	// 				$post = array(
	// 					'url_id'                        => 0,
	// 					'url'                           => $_COOKIE['wcd-domain'],
	// 					'group_id-' . $auto_group['id'] => $auto_group['id'],
	// 					'desktop-' . $auto_group['id']  => $_COOKIE['wcd-device'] == 'desktop' ? 1 : 0,
	// 					'mobile-' . $auto_group['id']   => $_COOKIE['wcd-device'] == 'mobile' ? 1 : 0,
	// 				);
	// 				$wp_comp->save_url( $post );
	// 			} elseif ( $_COOKIE['wcd-type'] == 'wp' ) {

	// 				$args = array(
	// 					'domain'    => $_COOKIE['wcd-domain'],
	// 					'group_id'  => 0,
	// 					'cms'       => 'wordpress',
	// 					'threshold' => 0,

	// 				);
	// 				$wp_comp->save_wp_group_settings( $args );
	// 			}
	// 		}

	// 		?>
	<!-- // 	<script>
	// 		document.cookie = "wcd-domain=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/";
	// 		document.cookie = "wcd-device=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/";
	// 		document.cookie = "wcd-type=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/";
	// 		document.cookie = "wcd-interval_in_h=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/";
	// 		document.cookie = "wcd-email=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/";
	// 		document.cookie = "wcd-hour_of_day=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/";
	// 	</script> -->
	 		<?php
	// 	} else {
	// 		$postTypePages = array(
	// 			'url_type_slug'  => 'types',
	// 			'url_type_name'  => 'Post Types',
	// 			'post_type_slug' => 'pages',
	// 			'post_type_name' => 'Pages',
	// 		);
	// 		$postTypePosts = array(
	// 			'url_type_slug'  => 'types',
	// 			'url_type_name'  => 'Post Types',
	// 			'post_type_slug' => 'posts',
	// 			'post_type_name' => 'Posts',
	// 		);

	// 		// add and sync urls for posts and pages
	// 		$result = $wp_comp->save_wp_group_settings(
	// 			array(
	// 				'wp_api_types_posts' => wp_json_encode( $postTypePosts, JSON_UNESCAPED_UNICODE ),
	// 				'wp_api_types_pages' => wp_json_encode( $postTypePages, JSON_UNESCAPED_UNICODE ),
	// 				'domain'             => $_COOKIE['wcd-domain'],
	// 				'cms'                => 'wordpress',
	// 				'group_id'           => 0,
	// 			)
	// 		);

	// 		$website            = $wp_comp->get_website_details( $_COOKIE['wcd-domain'] )[0];
	// 		$manualCheckGroupId = $website['manual_detection_group_id'];

	// 		$group_urls = $wp_comp->get_user_groups_and_urls( 'wordpress', 'update', $manualCheckGroupId )[0]['urls'];

	// 		$desktop_url_ids = explode( ',', $_COOKIE['wcd-manual-checks-desktop-ids'] );
	// 		$mobile_url_ids  = explode( ',', $_COOKIE['wcd-manual-checks-mobile-ids'] );

	// 		foreach ( $group_urls as $group_url ) {

	// 			if ( in_array( $group_url['cms_resource_id'], $desktop_url_ids ) ) {
	// 				$args = array(
	// 					'groupId'     => $manualCheckGroupId,
	// 					'urlId'       => $group_url['id'],
	// 					'deviceName'  => 'desktop',
	// 					'deviceValue' => 1,
	// 				);

	// 				$wp_comp->select_group_url( $args );
	// 			}

	// 			if ( in_array( $group_url['cms_resource_id'], $mobile_url_ids ) ) {
	// 				$args = array(
	// 					'groupId'     => $manualCheckGroupId,
	// 					'urlId'       => $group_url['id'],
	// 					'deviceName'  => 'mobile',
	// 					'deviceValue' => 1,
	// 				);
	// 				$wp_comp->select_group_url( $args );
	// 			}
	// 		}
	// 		update_user_meta( get_current_user_id(), 'wcd_default_tab', 'update-change-detection' );
	// 		update_user_meta( get_current_user_id(), WCD_OPTION_UPDATE_STEP_KEY, WCD_OPTION_UPDATE_STEP_PRE );
	// 		?>

	<!-- // 		<script>
	// 			document.cookie = "wcd-domain=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/";
	// 			document.cookie = "wcd-check-type=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/";
	// 			document.cookie = "wcd-manual-checks-desktop-ids=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/";
	// 			document.cookie = "wcd-manual-checks-mobile-ids=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/";
	// 			document.cookie = "wcd-device=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/";
	// 			document.cookie = "wcd-type=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/";
	// 			document.cookie = "wcd-interval_in_h=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/";
	// 			document.cookie = "wcd-email=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/";
	// 			document.cookie = "wcd-hour_of_day=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/";
	// 		</script> -->

	 		<?php
	// 	}
	// }

	
	// If we have the cookie wcd-plan, prepare the redirect to the checkout
	$checkoutPlan = '';
	if ( ! empty( $_COOKIE['wcd-plan'] ) ) {
		$checkoutPlan = '&checkout-plan=' . $_COOKIE['wcd-plan'];
		// If we already have a paid plan we don't need a redirect. So we delete all cookies.
		if ( $client_account['plan'] !== WCD_FREE_PLAN  && $client_account['plan'] !== WCD_TRIAL_PLAN) {
			unset( $_COOKIE['wcd-plan'] );
			unset( $_COOKIE['wcd-forceplan'] );
			setcookie( 'wcd-plan', '', time() - 3600, '/' ); // empty value and old timestamp
			setcookie( 'wcd-forceplan', '', time() - 3600, '/' ); // empty value and old timestamp
			$checkoutPlan = '';

			// We redirect to the plan once. But if he doesn't buy we don't redirect again and delete all cookies
		} elseif ( empty( $_COOKIE['wcd-forceplan'] ) ) {
			$tab = 'upgrade';
			unset( $_COOKIE['wcd-plan'] );
			unset( $_COOKIE['wcd-forceplan'] );
			setcookie( 'wcd-plan', '', time() - 3600, '/' ); // empty value and old timestamp
			setcookie( 'wcd-forceplan', '', time() - 3600, '/' ); // empty value and old timestamp

			// We force him to buy a plan. Until he doesn't, he will be redirected to the plan page.
		} elseif ( $_COOKIE['wcd-forceplan'] ) {
			$tab = 'upgrade';
		}
	}
	?>


	<div class="mm_side_navigation">
		
		<style>
			header .site-logo {
				display: none;
			}
			@media (max-width: 768px) {
				.main-navigation {
					display: none !important;
				}
				#wcd_content #mobile-nav {
					position: absolute;
					top: 30px;
					right: 20px;
				}
			}
		</style>
		<button id="close-mobile-nav" class="et_pb_button" style="display:none">X</button>
		<img src="/wp-content/uploads/logo-webchangedetector-app.png" alt="WebChangeDetector" style="max-width:90%; margin-left: 5%;">
		<?php

		$default_tab    = 'dashboard';
		$default_subtab = 'dashboard';

		if ( ! $tab ) {
			$tab = $_GET['tab'] ?? $default_tab;
		}

		if ( ! $subtab ) {
			$subtab = $_GET['subtab'] ?? $default_subtab;
		}

		mm_tabs( $tab, $subtab );
		//echo $account_view;
		?>
	</div>

	<div class="mm_wp_compare" style="background: #f8fafc">
		<div class="mm_content">
			<div class="mm_main_content">

			<?php
			// Check if account is active. Otherwise stop here with error message.

			if ( $client_account['status'] !== 'active' && $tab != 'upgrade' ) { ?>

				<div class="mm_content_error" style="background: #ffa5001c; border: 1px solid #e49d1d; box-shadow: 0 0 10px 0 rgba(0, 0, 0, 0.1); text-align: center; padding: 40px; margin: 100px auto; max-width: 700px; font-size: 18px; line-height: 22px;">
					<h3>Your Account Took a Nap</h3>
					<p>
					Bring it back to life by re-activating your account.<br>
					Your account email: <?= $client_account['email'] ?><br>
					
					</p>
					<a class="gbp-button--primary upgrade-button" href="/webchangedetector/?tab=upgrade">Re-Activate Account</a>
				</div>
				<?php
				return;
				
				$message = '<h3>Your account was ' . ($client_account['status'] ?? 'paused') . '.</h3>
                <p>Please <a href="' . get_upgrade_url() . '">Upgrade</a> your account to re-activate your account.</p>';
				return mm_message( array( 'error', $message ) );
			}
			?>

	<button id="mobile-nav" class="et_pb_button" >
		<span style="font-size: 22px;" class="dashicons dashicons-menu-alt"></span>
	</button>
	<?php
	// Show low / no credit warnings or trial expired
	if ( $client_account['checks_left'] <= 0 ) {
		?>
		<div class="global-low-credits no">
			<span>
			<?php
			if ( strtotime( $client_account['renewal_at'] ) < gmdate( 'U' ) ) {
				echo 'Your account expired. Please upgrade to continue change detecting.';
			} else {
				echo 'You have no checks left';
			}
			?>
			</span>
			<a class="et_pb_button upgrade-button" href="/webchangedetector/?tab=upgrade">Upgrade Account</a>
		</div>
	<?php } elseif ( $client_account['checks_left'] / $client_account['checks_limit'] * 100 <= 10 ) { ?>
		<div class="global-low-credits low">
			<span>You are running low on checks</span><br>
			<a class="et_pb_button upgrade-button" href="/webchangedetector/?tab=upgrade">Upgrade Account</a>
		</div>
		<?php
	}

	if ( $tab !== 'dashboard' && $tab !== 'upgrade' ) {
		echo '<button id="help-button" class="et_pb_button">?</button>';
	}

	// Show success message when account was upgraded
	if ( ! empty( $_GET['account-upgraded'] ) && $_GET['account-upgraded'] == 1 ) {
		echo mm_message( array( 'success', 'Thank you for your upgrade! It might take few minutes until the upgrade is completed.' ) );
	}

	include wcd_get_plugin_dir() . 'public/partials/popup-plan-limitation.php';
	include wcd_get_plugin_dir() . '/public/partials/popup-show-change-detection.php';

    ?>
    <!-- <div id="wcd-switch-account">
        <?php
        // $subaccounts = Wp_Compare_API_V2::get_subaccounts();
        // $subaccount_api_tokens = get_user_meta(get_current_user_id(), 'wcd_subaccount_api_tokens', 1);
        // $current_api_token = get_user_meta(get_current_user_id(), 'wcd_active_api_token', 1);
        // $current_user_id = get_user_meta(get_current_user_id(), 'wcd_active_user_id', 1);

        // if($current_api_token !== mm_api_token()) {
        //     $main_account = $wp_comp->get_account_details_v2(mm_api_token());
        // } else {
        //     $main_account = $wp_comp->account_details ?? $wp_comp->get_account_details_v2(mm_api_token());
        // }

        ?>
        <select class="ajax-switch-account" >
            <option value="<?= $main_account['id'] ?>" <?= $main_account['id'] === $current_user_id ? 'selected' : '' ?>>Main Account</option>
            <?php
            // echo "Main account | API Token: " . mm_api_token();

            // if(!empty($subaccounts['data']) && !empty($subaccount_api_tokens)) {
            //     foreach($subaccounts['data'] as $subaccount) {
            //         if(array_key_exists($subaccount['id'], $subaccount_api_tokens)) {
            //             $selected = $subaccount['id'] === $current_user_id ? 'selected' : '';
            //             echo "<option value='{$subaccount['id']}' {$selected}> {$subaccount['name_first']} {$subaccount['name_last']} | {$subaccount['email']} </option>";
            //         }
            //     }
            // }
            ?>
        </select>
    </div> -->
    <?php
	switch ( $tab ) {

		/************************
		 * Update Detections
		 * */
		case 'update-change-detection':
			echo '<h1>
                    Manual Checks
                    <small>
                        Check webpages before and after updates (or other changes on websites) and see what changed. 
                        <!--<span class="link" onclick="showHelp()">More Informations</span>-->
                    </small>
                    </h1>';

			// Add Group Popup
			$monitoring = 0; // Used in includes
			include wcd_get_plugin_dir() . 'public/partials/popup-add-group.php';
			include wcd_get_plugin_dir() . 'public/partials/popup-add-wp-group.php';
			include wcd_get_plugin_dir() . 'public/partials/popup-assign-group-urls.php';
			include wcd_get_plugin_dir() . 'public/partials/popup-show-change-detections-overview.php';
			include wcd_get_plugin_dir() . 'public/partials/popup-sync-progress.php';

			$filters = array(
				'cms' => $_POST['cms'] ?? 'all',
			);

			$groups = Wp_Compare_API_V2::get_groups_v2(['monitoring' => false, 'page' => $_GET['pagination'] ?? 1]);
			$groups_and_urls = $groups['data']; // used in template TODO: rename $groups_and_urls to $group_data

			// Calculate total urls to check
			$sc_processing = 0;
			foreach($groups_and_urls as $group	) {
				if($group['selected_urls_count'] > 0 && $group['enabled'] == true) {
					$sc_processing += $group['selected_urls_count'];
				}
			}
			
			// Update Change Detection steps
			$step = get_user_meta( get_current_user_id(), WCD_OPTION_UPDATE_STEP_KEY, true ) ?? WCD_OPTION_UPDATE_STEP_SETTINGS;
			if ( empty( $step ) ) {
				$step = WCD_OPTION_UPDATE_STEP_SETTINGS;
			}
			?>

			<div class="action_container">
				<div class="manual_compare">

				<?php
				switch ( $step ) {
					case WCD_OPTION_UPDATE_STEP_SETTINGS:
						$progress_setting          = 'active';
						$progress_pre              = 'disabled';
						$progress_make_update      = 'disabled';
						$progress_post             = 'disabled';
						$progress_change_detection = 'disabled';
						$update_view               = true;
						include wcd_get_plugin_dir() . '/public/partials/auto-update-settings.php';
						break;

					case WCD_OPTION_UPDATE_STEP_PRE:
						$progress_setting          = 'done';
						$progress_pre              = 'active';
						$progress_make_update      = 'disabled';
						$progress_post             = 'disabled';
						$progress_change_detection = 'disabled';

						include wcd_get_plugin_dir() . '/public/partials/update-detection/update-step-pre-sc.php';
						break;

					case WCD_OPTION_UPDATE_STEP_PRE_STARTED:
						$progress_setting          = 'done';
						$progress_pre              = 'active';
						$progress_make_update      = 'disabled';
						$progress_post             = 'disabled';
						$progress_change_detection = 'disabled';
						if ( ! empty( $_POST['group_ids'] ) && ! empty( $_POST['sc_type'] ) ) {
							$wp_comp->take_screenshot( $_POST );
						}
                        $batch_id = get_user_meta(get_current_user_id(), 'wcd_manual_checks_batch', true);
						//$sc_processing = 0; // JavaScript will update this via AJAX
						include wcd_get_plugin_dir() . '/public/partials/update-detection/update-step-pre-sc-started.php';
						break;

					case WCD_OPTION_UPDATE_STEP_POST:
						$progress_setting          = 'done';
						$progress_pre              = 'done';
						$progress_make_update      = 'done';
						$progress_post             = 'active';
						$progress_change_detection = 'disabled';
						include wcd_get_plugin_dir() . '/public/partials/update-detection/update-step-post-sc.php';
						break;

					case WCD_OPTION_UPDATE_STEP_POST_STARTED:
						$progress_setting          = 'done';
						$progress_pre              = 'done';
						$progress_make_update      = 'done';
						$progress_post             = 'active';
						$progress_change_detection = 'disabled';
                        $batch_id = get_user_meta(get_current_user_id(), 'wcd_manual_checks_batch', true);
						//$sc_processing             = 0; // JavaScript will update this via AJAX
						include wcd_get_plugin_dir() . '/public/partials/update-detection/update-step-post-sc-started.php';
						break;

					case WCD_OPTION_UPDATE_STEP_CHANGE_DETECTION:
						$progress_setting          = 'done';
						$progress_pre              = 'done';
						$progress_make_update      = 'done';
						$progress_post             = 'done';
						$progress_change_detection = 'active';

						include wcd_get_plugin_dir() . '/public/partials/update-detection/update-step-change-detection.php';
						break;
				}
				?>

				</div>
			</div>


			<?php
			break;

		/************************
		 * Dashboard
		 * */

		case 'dashboard':
            echo '<style>select.ajax-switch-account {display: none;}</style>';
			$wp_comp->get_dashboard_view( $client_account );
			break;

		/************************
		 * Auto Detection
		 * */

		case 'auto-change-detection':
			?>
			<h1>
				Monitoring
				<small>
					Monitor webpages automatically in intervals and get email notifications when something changes.
					<!--<span class="link" onclick="showHelp(this)">More informations</span>-->
				</small>
			</h1>

			<?php

			$monitoring = 1; // Used in includes
			include wcd_get_plugin_dir() . 'public/partials/popup-add-group.php';
			include wcd_get_plugin_dir() . 'public/partials/popup-add-wp-group.php';
			include wcd_get_plugin_dir() . 'public/partials/popup-assign-group-urls.php';
			include wcd_get_plugin_dir() . 'public/partials/popup-show-change-detections-overview.php';
			include wcd_get_plugin_dir() . 'public/partials/popup-sync-progress.php';
			
			$groups = Wp_Compare_API_V2::get_groups_v2(['monitoring' => true, 'page' => $_GET['pagination'] ?? 1]);
			$groups_and_urls = $groups['data']; // used in template TODO: rename $groups_and_urls to $group_data

			// Filter out groups where monitoring is true, keeping only those where monitoring is false.
			?>

			<div class="action_container">

			
				<div class="manual_compare">
				<?php
				$update_view = false; // used in template
				include wcd_get_plugin_dir() . '/public/partials/auto-update-settings.php';
				// $wp_comp->get_action_view($groups_and_urls, $filters, $updateView = false);
				?>
				</div>
			</div>

			
			<?php
			break;

		/************************
		 * Website settings
		 * */
		case 'website-settings':
			?>
			<h1>
				WordPress Websites
				<small>
					Your API token for direct integrations and for using our WordPress plugin.
				</small>
			</h1>

			<?php
			$wp_comp->get_wp_website_settings();
			break;


		/********************
		 * Subaccounts
		 */
			case 'subaccounts':
				?>
				<h1>
                    Subaccounts
                    <small>
                        Create subaccounts and limit the number of checks for each subaccount. Use the newly generated API token for integrations.
						Subaccounts can't login at the webapp.
                    </small>
				</h1>
				<?php
				$wp_comp->get_subaccount_view();
				break;

		/********************
		 * Change Detections
		 */

		case 'change-detections':
			echo '<h1>
                    Change Detections
                    <small>
                        See what changed on all your change detections.
                       <!-- <span class="link" onclick="showHelp()"></span>-->
                    </small>
                    </h1>';

			$postdata = array_merge( $_POST, $_GET );
			$wp_comp->get_compares_view( $postdata );
			break;

		/********************
		 * Logs
		 */

		case 'queue':
			?>
			<h1>
				Logs
				<!--<small>
					All screenshots we have taken and those who are still in progress are listed here.
					<span class="link" onclick="showHelp()">More Information</span>
				</small>-->
			</h1>
			<div class="action_container">
				<div class="manual_compare">
			<?php
			
			$queues_data = $wp_comp->get_queue( $_GET['pagination'] ?? 1 );
			
			$queues_meta = $queues_data['meta'] ?? [];
			$queues = $queues_data['data'] ?? [];

			$type_nice_name = array(
				'pre'     => 'Reference Screenshot',
				'post'    => 'Compare Screenshot',
				'auto'    => 'Monitoring',
				'compare' => 'Change Detection',
			);

			if ( ! empty( $queues ) && is_iterable( $queues ) ) {
				echo '<div class="mm_processing_container">';
				echo '<div class="responsive-table">';
				echo '<table class="queue">';
				echo '<tr>
                        <th></th>
                        <th width="auto">Page & URL</th>
                        <th>Type</th>
                        <th>Status</th>
                        <th>Added</th>
                        <th>Last changed</th>
                    </tr>';
				foreach ( $queues as $queue ) {
					echo '<tr class="queue-status ' . $queue['status'] . '">';
					echo '<td data-label="Device & Group">' .
						'<div class="log-table-device-icon">' . get_device_icon( $queue['device'] ) . '</div>' .
						'</td>';
					echo '<td data-label="Webpage Details">
                                <span class="html-title queue"> ' . $queue['html_title'] . '</span><br>
                                <span class="url queue">URL: ' . $queue['url_link'] . '</span>
                                
                          </td>';

					echo '<td data-label="Type">' . $type_nice_name[ $queue['sc_type'] ] . '</td>';
					echo '<td data-label="Status">' . ucfirst( $queue['status'] ) . '</td>';
					echo '<td data-label="Created at" class="local-time" data-date="' . strtotime( $queue['created_at'] ) . '">' . gmdate( 'd/m/Y H:i:s', strtotime( $queue['created_at'] ) ) . '</td>';
					echo '<td data-label="Last changed" class="local-time" data-date="' . strtotime( $queue['updated_at'] ) . '">' . gmdate( 'd/m/Y H:i:s', strtotime( $queue['updated_at'] ) ) . '</td>';
					
					echo '</tr>';
				}
				echo '</table>';
				echo '</div>';

				if(!empty($queues_meta)) {
					$pagination         = $queues_meta;
					?>
					<!-- Pagination -->
					<div class="tablenav">
						<div class="tablenav-pages">
							<span class="pagination-links">
								<?php
								foreach ( $pagination['links'] as $link ) {
									$url_params = $wp_comp->get_params_of_url( $link['url'] );
									$class  = ! $link['url'] || $link['active'] ? 'disabled' : '';
									?>
									<a class="tablenav-pages-navspan et_pb_button <?php echo esc_html( $class ); ?>"
									   href="?tab=<?= $tab ?>&pagination=<?php
									   echo esc_html( $url_params['page'] ?? 1 );?>
									   ">
										<?php echo esc_html( $link['label'] ); ?>
									</a>
									<?php
								}
								?>
							</span>
							<span class="displaying-num"><?php echo esc_html( $pagination['total'] ); ?> items</span>
						</div>
					</div>
				<?php } else {
					// We only have one batch. So we open it. ?>
					<script>jQuery(document).ready(function() {jQuery(".mm_accordion_title h3").click()});</script>
				<?php }

				echo '</div>';
			} else {
				echo '<p>Nothing to show yet. Start change detections and you will see all checks here.</p>';

				// Show newer button
				if ( $offset >= 50 ) {
					$offset_newer = $offset - 50;
					echo '<a class="et_pb_button" href="' . MM_APP_PATH . '?tab=' . $tab . '&limit=' . $limit . '&offset=' . $offset_newer . '">< Newer</a>';
				}
			}
			echo '</div>';
			echo '</div>';

			break;

		/*******************
		 * Help
		 */

		case 'help':
			wcd_get_plugin_dir() . 'public/partials/help.php';

			break;

		case 'show-compare': // deprecated
			?>
			<script>
				window.location.replace("?tab=change-detections&show-change-detection-token=<?php echo $_GET['token']; ?>");
			</script>

			<?php
			break;

		/*******************
		 * Upgrade
		 */

		case 'upgrade':
			$url = mm_get_billing_domain() . "select-plan?secret={$main_account['magic_login_secret']}" . $checkoutPlan ?? '';
			ob_clean();
			ob_start();
			wp_redirect( $url );

			// failsave as the wp_redirect didn't work local
			echo "<script>window.location.href = '{$url}';</script>";
			exit();

	}
	echo '</div>'; // closing from div mm_main_content
	echo '</div>'; // closing from div mm_content
	echo '</div>'; // closing from div mm_main_content
	echo '</div>'; // closing from div mm_wp_compare

	echo '</div>'; // closing id wcd_content
	return ob_get_clean();
}

if ( ! function_exists( 'mm_tabs' ) ) {
	function mm_tabs( $active_tab, $subtab ) {
		?>
		<!-- Create a header in the default WordPress 'wrap' container -->
		<div class="wrap">
		
		
		<?php
		$wp_compare       = new Wp_Compare();
		$queue_processing = 0; // JavaScript will update this via AJAX
		?>

		<ul class="side_nav_content no_margin">
			<li>
				<a href="<?php echo MM_APP_PATH; ?>?tab=dashboard"
					class="nav-tab <?php echo $active_tab == 'dashboard' ? 'nav-tab-active' : ''; ?>">
					<?php echo get_device_icon( 'dashboard' ); ?>
					Dashboard
				</a>
			</li>

			<li>
				<a href="<?php echo MM_APP_PATH; ?>?tab=auto-change-detection"
					class="nav-tab <?php echo $active_tab == 'auto-change-detection' ? 'nav-tab-active' : ''; ?>">
					<?php echo get_group_icon( array( 'monitoring' => 1 ) ); ?>
					Monitoring
				</a>
			</li>
			<li>
				<a href="<?php echo MM_APP_PATH; ?>?tab=update-change-detection"
					class="nav-tab <?php echo $active_tab == 'update-change-detection' ? 'nav-tab-active' : ''; ?>">
					<?php echo get_group_icon( array( 'monitoring' => 0 ) ); ?>
					Manual Checks
				</a>
			</li>
			<li>
				<a href="<?php echo MM_APP_PATH; ?>?tab=change-detections"
					class="nav-tab <?php echo $active_tab == 'change-detections' ? 'nav-tab-active' : ''; ?>">
					<?php echo get_device_icon( 'change-detections' ); ?>
					Change Detections
				</a>
			</li>

			<li>
				<a href="<?php echo MM_APP_PATH; ?>?tab=website-settings"
					class="nav-tab <?php echo $active_tab == 'website-settings' ? 'nav-tab-active' : ''; ?>">
					<?php echo get_device_icon( 'wordpress' ); ?>
					WordPress Websites
				</a>
			</li>
			<li>
				<a href="<?php echo MM_APP_PATH; ?>?tab=subaccounts"
					class="nav-tab <?php echo $active_tab == 'subaccounts' ? 'nav-tab-active' : ''; ?>">
					<?php echo get_device_icon( 'subaccounts' ); ?>
					Subaccounts
				</a>
			</li>

			<li>
				<a href="<?php echo MM_APP_PATH; ?>?tab=queue"
					class="nav-tab <?php echo $active_tab == 'queue' ? 'nav-tab-active' : ''; ?>">
					<?php echo get_device_icon( 'logs' ); ?>
					Logs
					<span id="currently-processing" style="display: none;"><?php echo $queue_processing; ?></span>
					<img src="<?php echo $wp_compare->get_loading_transparent_bg_icon_url_path(); ?>" id="currently-processing-spinner" style="display: none">
				</a>
			</li>

			<li>
				<a href="<?php echo MM_APP_PATH; ?>?tab=upgrade"
					class="nav-tab">
					<?php echo get_device_icon( 'users' ); ?>
					Account
				</a>
			</li>
		</ul>

		</div>
		<style>
			.subnav li {
				background: #07528f;
				border-bottom: none;
			}
		</style>
		<!-- /.wrap -->
		<?php
	} // end sandbox_theme_display
}
