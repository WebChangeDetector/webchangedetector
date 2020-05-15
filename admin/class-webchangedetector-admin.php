<?php

/**
 * The admin-specific functionality of the plugin.
 *
 * @link       wp-mike.com
 * @since      1.0.0
 *
 * @package    WebChangeDetector
 * @subpackage WebChangeDetector/admin
 */

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    WebChangeDetector
 * @subpackage WebChangeDetector/admin
 * @author     Mike Miler <mike@wp-mike.com>
 */
class WebChangeDetector_Admin {

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
	private $version = '1.0.3';

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param      string    $plugin_name       The name of this plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct( $plugin_name = "WebChangeDetector", $version = '1.0.3' ) {

		$this->plugin_name = $plugin_name;
		$this->version = $version;

	}

	/**
	 * Register the stylesheets for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles() {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in WebChangeDetector_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The WebChangeDetector_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/webchangedetector-admin.css', array(), $this->version, 'all' );

	}

	/**
	 * Register the JavaScript for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts() {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in WebChangeDetector_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The WebChangeDetector_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/webchangedetector-admin.js', array( 'jquery' ), $this->version, false );
        wp_enqueue_script('jquery-ui-accordion');

	}

	// Add WCD to backend navigation (called by hook in includes/class-webchangedetector.php)
    public function wcd_plugin_setup_menu(){
	    require_once "partials/webchangedetector-admin-display.php";
        add_menu_page( 'Web Change Detector',
            'Web Change Detector',
            'manage_options',
            'webchangedetector',
            'webchangedetector_init',
            plugin_dir_url( __FILE__ ) . 'img/icon-wp-backend.svg');
    }

    // Sync urls on publishing (called by hook in includes/class-webchangedetector.php)
    public function wcd_sync_urls_on_publish( $new_status, $old_status, $post ) {
        if('publish' === $new_status && 'publish' !== $old_status && in_array( $post->post_type, array( 'post', 'page') ) ) {
            $wcd = new WebChangeDetector();
            $website_details = $wcd->get_website_details( get_option( 'webchangedetector_api_key' ) );
            $wcd->sync_posts( $website_details['auto_detection_group_id'], $website_details['manual_detection_group_id'] );
        }
    }

    public function get_account_details( $api_key ) {

        $args = array(
            'action'		=> 'account_details',
            'api_key'		=> $api_key
        );
        return $this->mm_api( $args );
    }

    public function get_monitoring_settings( $group_id ) {
        $args = array(
            'action'	=> 'get_monitoring_settings',
            'group_id'	=> $group_id
        );
        $monitoring_group_settings = $this->mm_api( $args );
        return $monitoring_group_settings[0];
    }

    public function get_amount_sc ( $group_id ) {
        $args = array(
            'action' => 'get_amount_sc',
            'group_id' => $group_id
        );
        return $this->mm_api($args);
    }

    public function show_compare( $compare_id ) {
        $args = array(
            'action'        => 'show_compare',
            'compare_id'    => $compare_id
        );
        return $this->mm_api($args);
    }

    public function update_monitoring_settings( $postdata, $monitoring_group_id ) {
        $args = array(
            'action' => 'update_monitoring_settings',
            'group_id' => $monitoring_group_id,
            'hour_of_day' => $postdata['hour_of_day'],
            'interval_in_h' => $postdata['interval_in_h'],
            'monitoring' => $postdata['monitoring'],
            'enabled' => $postdata['enabled'],
            'alert_email' => $postdata['alert_email'],
            'group_name' => $postdata['group_name']
        );
        $this->mm_api($args);
    }

    public function get_upgrade_options( $plan_id ) {
        $args = array(
            'action' => 'get_upgrade_options',
            'plan_id' => $plan_id
        );
        return $this->mm_api( $args );
    }

    public function get_compares( $group_id ) {
        $args = array(
            'action' => 'get_compares',
            'domain' => $_SERVER['SERVER_NAME'],
            'group_id' => $group_id
        );
        return $this->mm_api($args);
    }
    public function get_queue( $group_id ) {
        $args = array(
            'action' => 'get_queue',
            'group_id' => $group_id
        );
        return $this->mm_api($args);
    }

    public function sync_posts( $auto_detection_group = false, $manual_detection_group = false ) {

        $posttypes = array(
            'pages' => get_pages(),
            'posts' => get_posts( array( 'numberposts' => '-1' ) )
        );

        $array = array();
        foreach( $posttypes as $posts) {
            if ($posts) {
                foreach ($posts as $post) {
                    $url = get_permalink($post);
                    $url = substr($url, strpos($url, '//') + 2);
                    $array[] = array(
                        'url' => $url,
                        'wp_post_id' => $post->ID
                    );
                }
            }
        }

        if( $array ) {
            $args = array(
                'action'    => 'sync_urls',
                'posts'     => json_encode( $array ),
                'auto_detection_group_id' => $auto_detection_group,
                'manual_detection_group_id' => $manual_detection_group
            );

            return $this->mm_api( $args );
        } else
            return false;
    }

    public function update_urls( $group_id, $active_posts = array() ) {
        $args = array(
            'action'		=> 'update_urls',
            'group_id'		=> $group_id,
            'posts'			=> json_encode( $active_posts ),
        );
        $results = $this->mm_api( $args );
    }

    public function take_screenshot( $group_id, $api_key ) {
        $args = array(
            'action'		=> 'take_screenshots',
            'group_id'		=> $group_id,
            'api_key'		=> $api_key
        );
        return $this->mm_api( $args );
    }

    function create_free_account( $post ) {
        $args = array(
            'action'		=> 'add_free_account',
            'domain'		=> $_SERVER['SERVER_NAME'],
            'first_name'	=> $post['first_name'],
            'last_name'		=> $post['last_name'],
            'email'			=> $post['email'],
        );

        $api_key = $this->mm_api( $args );
        if( isset( $api_key['status'] ) && $api_key['status'] == 'success' ) {

            update_option('webchangedetector_api_key', $api_key['api_key']);

            $this->create_group($api_key['api_key']);
        }
        return $api_key;
    }

    function verify_account() { //Replaces get_api_key and verify_api_key
        $api_key = get_option( 'webchangedetector_api_key' );
        if( $api_key ) {
            $args = array(
                'action'		=> 'verify_account',
                'api_key'		=> $api_key
            );
            return $this->mm_api( $args );
        } else
            return false;
    }

    function resend_confirmation_mail( $api_key ) {
        $args = array(
            'action'	=> 'resend_verification_email',
            'api_key'	=> $api_key
        );
        $this->mm_api( $args );
    }

    function get_api_key_form( $api_key = false ) {

        if( $api_key ) {
            $output = '<form action="/wp-admin/admin.php?page=webchangedetector&tab=settings" method="post" 
                        onsubmit="return confirm(\'Do you really want to reset the API key?\nYour settings will get lost.\');">
                        <input type="hidden" name="wcd_action" value="reset_api_key">
                        <h2>API Key</h2>
                        <p>Your API key: <strong>' . $api_key . '</strong></p>
                        <input type="submit" value="Reset API key" class="button"><br>
                        <p><strong>ATTENTION: With resetting the API key, all settings get lost and 
                        the monitoring won\'t be continued!</strong></p>';
        } else {
            $output = '<form action="/wp-admin/admin.php?page=webchangedetector&tab=settings" method="post">
                        <input type="hidden" name="wcd_action" value="save_api_key">
                        <h2>You already have an API key?</h2>
                        <p>Enter your API key here and start comparing.</p>
                        <input type="text" name="api-key" value="' . $api_key .  '">
                        <input type="submit" value="Save" class="button">';
        }
        $output .= '</form>';
        return  $output;
    }

    function create_group( $api_key ) {
        // Create group if it doesn't exist yet
        $args = array(
            'action'	    => 'add_website_groups',
            'domain'	    => $_SERVER['SERVER_NAME'],
            'website_group' => 1,
            'api_key'	    => $api_key
        );

        return $this->mm_api( $args );
    }

    function delete_website( $api_key ) {
        $args = array(
            'action'    => 'delete_website',
            'domain'    =>  $_SERVER['SERVER_NAME'],
            'api_key'   => $api_key
        );
        $this->mm_api( $args );
    }

    function get_website_details( $api_key ) {
        $args = array(
            'action'    => 'get_client_website_details',
            'domain'    => $_SERVER['SERVER_NAME'],
            'api_key'   => $api_key
        );

        $website_details = $this->mm_api( $args );
        return $website_details[0];
    }

    function get_urls_of_group( $group_id ) {
        $args = array(
            'action'		=> 'get_group_urls',
            'group_id'		=> $group_id
        );
        $group_urls = $this->mm_api( $args );

        $check_posts = array();
        $amount_sc = 0;

        foreach( $group_urls as $group_url ) {
            // Create array with all active urls of group
            $check_posts['urls'][] = array(
                'wp_post_id'    => (int)$group_url['wp_post_id'],
                'sc_id'         => (int)$group_url['id'],
                //'active'        => $group_url['active'],
                'desktop'       => $group_url['desktop'],
                'mobile'        => $group_url['mobile']
            );

            // Count amount of sc
            if( $group_url['desktop'] )
                $amount_sc++;
            if( $group_url['mobile'] )
                $amount_sc++;
        }

        $check_posts['amount_sc'] = $amount_sc;
        return $check_posts;
    }

    function mm_get_url_settings( $group_id, $monitoring_group = false ) {

        if( $monitoring_group ) {
            $auto_detection_group_id = $group_id;
            $update_detection_group_id = false;
        } else {
            $auto_detection_group_id = false;
            $update_detection_group_id = $group_id;
        }

        // Sync urls - post_types defined in function @todo make settings for post_types to sync
        $synced_posts = $this->sync_posts( $auto_detection_group_id, $update_detection_group_id );
        $checks = $this->get_urls_of_group( $group_id );

        // Select URLS
        if( $monitoring_group )
            $tab = "monitoring-screenshots";
        else
            $tab = "take-screenshots";

        echo '<form action="/wp-admin/admin.php?page=webchangedetector&tab=' . $tab . '" method="post">';
        echo '<input type="hidden" value="webchangedetector" name="page">';
        echo '<input type="hidden" value="post_urls" name="wcd_action">';
        echo '<input type="hidden" value="' . $group_id . '" name="group_id">';

        $post_types = get_post_types();

        foreach( $post_types as $post_type ) {

            if( $post_type != 'post' && $post_type != 'page' )
                continue;

            $posts = get_posts([
                'post_type'     => $post_type,
                'post_status'   => 'publish',
                'numberposts'   => -1,
                'order'         => 'ASC',
                'orderby'      => 'title'
            ]);

            if( $posts ) {
                echo '<h2>' . ucfirst( $post_type ) . '</h2>';
                echo '<table><tr><th>Desktop</th><th>Mobile</th><th>Post Name</th><th>URL</th></tr>';

                echo '<tr style="background: none; text-align: center">
                            <td><input type="checkbox" id="select-desktop-' . $post_type . '" onclick="mmToggle( this, \'' . $post_type . '\', \'desktop\', \'' . $group_id . '\' )" /></td>
                            <td><input type="checkbox" id="select-mobile-' . $post_type . '" onclick="mmToggle( this, \'' . $post_type . '\', \'mobile\', \'' . $group_id . '\' )" /></td>
                        </tr>';
                foreach( $posts as $post ) {
                    $url = get_permalink( $post );
                    $sc_id = false;

                    foreach( $synced_posts as $synced_post ) {
                        if( $synced_post['wp_post_id'] == $post->ID) {
                            $sc_id = $synced_post['sc_id'];
                        }
                    }
                    if( !$sc_id )
                        continue;

                    $checked = array(
                        'active' => 1,
                        'desktop' => '',
                        'mobile' => ''
                    );
                    if( isset( $checks['urls'] ) ) {
                        foreach ( $checks['urls'] as $key => $check ) {
                            if ( $check['sc_id'] == $sc_id ) {
                                $checked['active'] = 'checked';

                                if ( $check['desktop'] )
                                    $checked['desktop'] = 'checked';
                                if ( $check['mobile'] )
                                    $checked['mobile'] = 'checked';

                            }
                        }
                    }

                    echo '<tr class="post_id_' . $group_id . '" id="' . $sc_id . '" >';
                    echo '<input type="hidden" name="sc_id-' . $sc_id . '" value="' . $sc_id . '">';
                    echo '<input type="hidden" name="active-' . $sc_id . ' value="1">';

                    echo '<td class="checkbox-desktop-' . $post_type . '" style="text-align: center;">
                            <input type="hidden" value="0" name="desktop-' . $sc_id . '">
                            <input type="checkbox" name="desktop-' . $sc_id . '" value="1" ' . $checked['desktop'] . ' 
                            id="desktop-' . $sc_id . '" onclick="mmMarkRows(\'' . $sc_id . '\')" ></td>';

                    echo '<td class="checkbox-mobile-' . $post_type . '" style="text-align: center;">
                            <input type="hidden" value="0" name="mobile-' . $sc_id . '">
                            <input type="checkbox" name="mobile-' . $sc_id . '" value="1" ' . $checked['mobile'] . ' 
                            id="mobile-' . $sc_id . '" onclick="mmMarkRows(\'' . $sc_id . '\')" ></td>';

                    echo '<td style="text-align: left;">' . $post->post_title . '</td>';
                    echo '<td style="text-align: left;"><a href="' . $url . '" target="_blank">' . $url . '</a></td>';
                    echo '</tr>';

                    echo '<script> mmMarkRows(\'' . $sc_id . '\'); </script>';
                }
                echo '</table>';
            }
        }
        echo '<input class="button" type="submit" value="Save" style="margin-top: 30px">';
        echo '</form>';
    }

    function get_no_account_page() {
        delete_option( 'webchangedetector_api_key' );

        $output =  '<div class="webchangedetector">
		<h1>Web Change Detector</h1>
		<h2>Create Free Account</h2>
		<p>Create now your free account and <strong>100 compares</strong> for one month for free!</p>
		<form id="frm_new_account" name="new_account" action="/wp-admin/admin.php?page=webchangedetector&tab=settings" onsubmit="return mmValidateForm()" method="post">
			<input type="hidden" name="wcd_action" value="create_free_account"><br>
			<p><label>First Name</label><input id="form_first_name" type="text" name="first_name"></p>
			<p><label>Last Name</label><input type="text" id="form_last_name" name="last_name"></p>
			<p><label>Email</label><input type="text" id="form_email" name="email"></p>
			<input type="submit" value="Create free account" class="button">
		</form>
		<hr>
		' . $this->get_api_key_form() . '
		</div>';
        return $output;
    }

    function mm_get_restrictions() {
        $args = array(
            'action'    => 'get_client_website_details',
            'domain'    => $_SERVER['HTTP_HOST']
        );

        $restrictions = $this->mm_api( $args );
        return $restrictions[0];
    }

    function mm_tabs() {

        if( isset( $_GET[ 'tab' ] ) ) {
            $active_tab = $_GET[ 'tab' ];
        } else
            $active_tab = 'take-screenshots';

        ?>
        <div class="wrap">
            <h2 class="nav-tab-wrapper">
                <a href="?page=webchangedetector&tab=take-screenshots" class="nav-tab <?php echo $active_tab == 'take-screenshots' ? 'nav-tab-active' : ''; ?>">Update Change Detection</a>
                <a href="?page=webchangedetector&tab=monitoring-screenshots" class="nav-tab <?php echo $active_tab == 'monitoring-screenshots' ? 'nav-tab-active' : ''; ?>">Auto Change Detection</a>
                <a href="?page=webchangedetector&tab=settings" class="nav-tab <?php echo $active_tab == 'settings' ? 'nav-tab-active' : ''; ?>">Settings</a>
                <a href="?page=webchangedetector&tab=help" class="nav-tab <?php echo $active_tab == 'help' ? 'nav-tab-active' : ''; ?>">Help</a>
            </h2>
        </div>

        <?php
    }

    function isJson($string) {
        json_decode($string);
        return (json_last_error() == JSON_ERROR_NONE);
    }
    function mm_api( $args ) {

	    if ( defined('WCD_DEV_API') && WCD_DEV_API )
            $url = 'https://www.dev.api.webchangedetector.com/v1/api.php';
        else
            $url = 'https://api.webchangedetector.com/v1/api.php';

        if( !isset( $args['api_key'] ) )
            $args['api_key'] = get_option( 'webchangedetector_api_key' );

        $ch = curl_init( $url );
        curl_setopt($ch, CURLOPT_POSTFIELDS, $args);
        curl_setopt($ch, CURLOPT_TIMEOUT, 300);

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $result = curl_exec($ch);

        if( !$result )
            return curl_error($ch);

        if( $result )
            if( $this->isJson( $result ) )
                $result = json_decode( $result, true );


        /*if( !is_array( $result ) && ( strpos( $result, 'Fatal error' ) === 0  || strpos( $result, 'Warning:' ) === 0 ) ) {
            echo "An error occured. <br>We sent an error report to the developer. Don't worry, nothing bad happened.";
            mail( 'mike@webchangedetector.com', 'Fatal error occured', 'Website: ' . $_SERVER['SERVER_NAME'] . '<br> Error message: ' . $result );
            die();
        }*/
        return $result;
    }
}
