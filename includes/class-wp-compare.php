<?php

/**
 * The file that defines the core plugin class
 *
 * A class definition that includes attributes and functions used across both the
 * public-facing side of the site and the admin area.
 *
 * @link       wp-mike.com
 * @since      1.0.0
 *
 * @package    Wp_Compare
 * @subpackage Wp_Compare/includes
 */

/**
 * The core plugin class.
 *
 * This is used to define internationalization, admin-specific hooks, and
 * public-facing site hooks.
 *
 * Also maintains the unique identifier of this plugin as well as the current
 * version of the plugin.
 *
 * @since      1.0.0
 * @package    Wp_Compare
 * @subpackage Wp_Compare/includes
 * @author     Mike Miler <mike@wp-mike.com>
 */
class Wp_Compare {

	/**
	 * The loader that's responsible for maintaining and registering all hooks that power
	 * the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      Wp_Compare_Loader    $loader    Maintains and registers all hooks for the plugin.
	 */
	protected $loader;

	/**
	 * The unique identifier of this plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $plugin_name    The string used to uniquely identify this plugin.
	 */
	protected $plugin_name;

	/**
	 * The current version of the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $version    The current version of the plugin.
	 */
	protected $version;

	/**
	 * Define the core functionality of the plugin.
	 *
	 * Set the plugin name and the plugin version that can be used throughout the plugin.
	 * Load the dependencies, define the locale, and set the hooks for the admin area and
	 * the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function __construct() {
		if ( defined( 'WP_COMPARE_VERSION' ) ) {
			$this->version = WP_COMPARE_VERSION;
		} else {
			$this->version = '1.0.0';
		}
		$this->plugin_name = 'wp-compare';

		$this->load_dependencies();
		$this->set_locale();
		$this->define_admin_hooks();
		$this->define_public_hooks();

	}

	/**
	 * Load the required dependencies for this plugin.
	 *
	 * Include the following files that make up the plugin:
	 *
	 * - Wp_Compare_Loader. Orchestrates the hooks of the plugin.
	 * - Wp_Compare_i18n. Defines internationalization functionality.
	 * - Wp_Compare_Admin. Defines all hooks for the admin area.
	 * - Wp_Compare_Public. Defines all hooks for the public side of the site.
	 *
	 * Create an instance of the loader which will be used to register the hooks
	 * with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function load_dependencies() {

		/**
		 * The class responsible for orchestrating the actions and filters of the
		 * core plugin.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-wp-compare-loader.php';

		/**
		 * The class responsible for defining internationalization functionality
		 * of the plugin.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-wp-compare-i18n.php';

		/**
		 * The class responsible for defining all actions that occur in the admin area.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/class-wp-compare-admin.php';

		/**
		 * The class responsible for defining all actions that occur in the public-facing
		 * side of the site.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'public/class-wp-compare-public.php';

		$this->loader = new Wp_Compare_Loader();

	}

	/**
	 * Define the locale for this plugin for internationalization.
	 *
	 * Uses the Wp_Compare_i18n class in order to set the domain and to register the hook
	 * with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function set_locale() {

		$plugin_i18n = new Wp_Compare_i18n();

		$this->loader->add_action( 'plugins_loaded', $plugin_i18n, 'load_plugin_textdomain' );

	}

	/**
	 * Register all of the hooks related to the admin area functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_admin_hooks() {

		$plugin_admin = new Wp_Compare_Admin( $this->get_plugin_name(), $this->get_version() );

		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_styles' );
		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts' );

	}

	/**
	 * Register all of the hooks related to the public-facing functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_public_hooks() {

		$plugin_public = new Wp_Compare_Public( $this->get_plugin_name(), $this->get_version() );

		$this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_styles' );
		$this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_scripts' );

	}

	/**
	 * Run the loader to execute all of the hooks with WordPress.
	 *
	 * @since    1.0.0
	 */
	public function run() {
		$this->loader->run();
	}

	/**
	 * The name of the plugin used to uniquely identify it within the context of
	 * WordPress and to define internationalization functionality.
	 *
	 * @since     1.0.0
	 * @return    string    The name of the plugin.
	 */
	public function get_plugin_name() {
		return $this->plugin_name;
	}

	/**
	 * The reference to the class that orchestrates the hooks with the plugin.
	 *
	 * @since     1.0.0
	 * @return    Wp_Compare_Loader    Orchestrates the hooks of the plugin.
	 */
	public function get_loader() {
		return $this->loader;
	}

	/**
	 * Retrieve the version number of the plugin.
	 *
	 * @since     1.0.0
	 * @return    string    The version number of the plugin.
	 */
	public function get_version() {
		return $this->version;
	}

	public function get_account_details( $api_key ) {

		$args = array(
			'action'		=> 'account_details',
			'api_key'		=> $api_key
		);
		return mm_api( $args );
	}

	public function get_usage( $api_key ) {
		$args = array(
			'action'	=> 'get_usage',
			'api_key'	=> $api_key
		);

		return mm_api( $args );
	}

	public function take_screenshot( $group_id, $api_key ) {
		$args = array(
			'action'		=> 'take_screenshots',
			'group_id'		=> $group_id,
			'api_key'		=> $api_key
		);
		return mm_api( $args );
	}

	function create_free_account( $post ) {
		$args = array(
			'action'		=> 'add_free_account',
			'domain'		=> $_SERVER['SERVER_NAME'],
			'first_name'	=> $post['first_name'],
			'last_name'		=> $post['last_name'],
			'email'			=> $post['email'],
		);

		$api_key = mm_api( $args );
		if( isset( $api_key['status'] ) && $api_key['status'] == 'success' ) {

            update_option('wpcompare_api_key', $api_key['api_key']);
            delete_option('wpcompare_group_id');
            delete_option('wpcompare_monitoring_group_id');

            $this->create_group($api_key['api_key']);
        }
		return $api_key;
	}

	function verify_account() { //Replaces get_api_key and verify_api_key
		$api_key = get_option( 'wpcompare_api_key' );
		if( $api_key ) {
			$args = array(
				'action'		=> 'verify_account',
				'api_key'		=> $api_key
			);
			return mm_api( $args );
		} else
			return false;
	}

	function resend_confirmation_mail( $api_key ) {
		$args = array(
			'action'	=> 'resend_verification_email',
			'api_key'	=> $api_key
		);
		mm_api( $args );
	}

	function get_api_key() {

		$api_key = get_option( 'wpcompare_api_key' );
		if( $api_key && $this->verify_api_key( $api_key ) ) {
			// Verify User
			return $api_key;
		} else
			return false;
	}

	function get_api_key_form( $api_key = false ) {


	    if( $api_key ) {
            $output = '<form action="/wp-admin/admin.php?page=wp-compare&tab=settings" method="post" 
                        onsubmit="return confirm(\'Do you really want to reset the API key?\nYour settings will get lost.\');">
                        <input type="hidden" name="action" value="reset_api_key">
                        <h2>API Key</h2>
                        <p>Your API key: <strong>' . $api_key . '</strong></p>
                        <input type="submit" value="Reset API key" class="button"><br>
                        <p><strong>ATTENTION: With resetting the API key, all settings get lost and 
                        the monitoring won\'t be continued!</strong></p>';
        } else {
	        $output = '<form action="/wp-admin/admin.php?page=wp-compare&tab=settings" method="post">
                        <input type="hidden" name="action" value="save_api_key">
                        <h2>You already have an API key?</h2>
                        <p>Enter your API key here and start comparing.</p>
                        <input type="text" name="api-key" value="' . $api_key .  '">
                        <input type="submit" value="Save" class="button">';
        }
	    $output .= '</form>';
		return  $output;
	}

	function verify_api_key( $api_key ) {
		$args = array(
			'action'		=> 'check_api_key',
			'api_key'		=> $api_key
		);

		return mm_api( $args );
	}

	function check_activated_account( $api_key ) {
		$args = array(
			'action'	=> 'check_account_activated',
			'api_key'	=> $api_key
		);

		return mm_api( $args );
	}

	function create_group( $api_key ) {
		// Create group if it doesn't exist yet
		$args = array(
			'action'	    => 'add_group',
			'domain'	    => $_SERVER['SERVER_NAME'],
			'website_group' => 1,
			'api_key'	    => $api_key
		);

		$group = mm_api( $args );

		$manual_group_id = $group['manual_group']['id'];
		$monitoring_group_id = $group['monitoring_group']['id'];

		update_option( 'wpcompare_group_id', $manual_group_id );
		update_option( 'wpcompare_monitoring_group_id', $monitoring_group_id );
	}

	function delete_group( $group_id, $api_key ) {
	    $args = array(
	        'action'    => 'delete_group',
            'group_id'  => $group_id,
            'api_key'   => $api_key
        );
	    mm_api( $args );
    }

    function get_urls_of_group( $group_id ) {
        $args = array(
            'action'		=> 'get_group_urls',
            'group_id'		=> $group_id
        );
        $group_urls = mm_api( $args );

        $check_posts = array();
        $amount_sc = 0;

        foreach( $group_urls as $group_url ) {
            // Create array with all active urls of group
            $check_posts[] = (int)$group_url['wp_post_id'];
            $check_desktop[$group_url['wp_post_id']] = $group_url['desktop'];
            $check_mobile[$group_url['wp_post_id']] = $group_url['mobile'];

            // Count amount of sc
            if( $group_url['desktop'] )
                $amount_sc++;
            if( $group_url['mobile'] )
                $amount_sc++;
        }

        return array(
            'check_posts'   => $check_posts,
            'check_desktop' => $check_desktop,
            'check_mobile'  => $check_mobile,
            'amount_sc'     => $amount_sc
        );
    }

    function mm_get_url_settings( $group_id, $monitoring_group = false ) {

        global $api_key;

        $checks = $this->get_urls_of_group( $group_id );
        // Get manual urls of group
        /*$args = array(
            'action'		=> 'get_group_urls',
            'group_id'		=> $group_id,
            'api_key'       => $api_key
        );
        $group_urls = mm_api( $args );

        $check_posts = array();
        foreach( $group_urls as $group_url ) {
            $check_posts[] = (int)$group_url['wp_post_id'];
            $check_desktop[$group_url['wp_post_id']] = $group_url['desktop'];
            $check_mobile[$group_url['wp_post_id']] = $group_url['mobile'];
        }*/

        // Select URLS
        if( $monitoring_group )
            $tab = "monitoring-screenshots";
        else
            $tab = "take-screenshots";

        echo '<form action="/wp-admin/admin.php?page=wp-compare&tab=' . $tab . '" method="post">';
        echo '<input type="hidden" value="wp-compare" name="page">';
        echo '<input type="hidden" value="post_urls" name="action">';
        echo '<input type="hidden" value="' . $group_id . '" name="group_id">';

        echo '<script>
		function mmMarkRows( postId ) {
			var checkBox = document.getElementById("active-" + postId);
			var row = document.getElementById("post_id_" + postId );

			if (checkBox.checked == true){
				row.style.background = "#17b33147";
			} else {
				row.style.background = "#dc323247";
			}
		}
	</script>';

        $post_types = get_post_types();

        foreach( $post_types as $post_type ) {

            $posts = get_posts([
                'post_type' => $post_type,
                'post_status' => 'publish',
                'numberposts' => -1,
                'order'    => 'ASC'
            ]);

            if( $posts ) {
                echo '<h2>' . ucfirst( $post_type ) . '</h2>';
                echo '<table><tr><th>Active</th><th>Desktop</th><th>Mobile</th><th>Post Name</th><th>URL</th></tr>';
                foreach( $posts as $post ) {
                    $url = get_permalink( $post );

                    // Check posts
                    if( in_array( $post->ID, $checks['check_posts'] ) )
                        $checked = 'checked';
                    else
                        $checked = '';

                    // Check Desktop
                    if( isset( $checks['check_desktop'][$post->ID] ) && $checks['check_desktop'][$post->ID] == 1 )
                        $checked_desktop = 'checked';
                    else
                        $checked_desktop = '';

                    // Check Mobile
                    if( isset( $checks['check_mobile'][$post->ID] ) && $checks['check_mobile'][$post->ID] == 1 )
                        $checked_mobile = 'checked';
                    else
                        $checked_mobile = '';

                    echo '<tr id="post_id_' . $group_id . '-' . $post->ID . '">';
                    echo '<td><input id="active-' . $group_id . '-' . $post->ID . '" onclick="mmMarkRows(\'' . $group_id . '-' . $post->ID . '\')" type="checkbox" name="pid-' . $post->ID . '" value="' . $post->ID . '" ' . $checked . '></td>';
                    echo '<td><input type="hidden" value="0" name="desktop-' . $post->ID . '"><input type="checkbox" name="desktop-' . $post->ID . '" value="1" ' . $checked_desktop . '></td>';
                    echo '<td><input type="hidden" value="0" name="mobile-' . $post->ID . '"><input type="checkbox" name="mobile-' . $post->ID . '" value="1" ' . $checked_mobile . '></td>';
                    echo '<td>' . $post->post_title . '</td>';
                    echo '<td><a href="' . $url . '" target="_blank">' . $url . '</a></td>';
                    echo '</tr>';

                    echo '<script>mmMarkRows(\'' . $group_id . '-' . $post->ID . '\'); </script>';


                }
                echo '</table>';

            }
        }
        echo '<input class="button" type="submit" value="Save" style="margin-top: 30px">';
        echo '</form>';
    }

	function get_no_account_page() {

		?>
		<script>
			function mmValidateEmail(email) {
				var re = /^(([^<>()[\]\\.,;:\s@\"]+(\.[^<>()[\]\\.,;:\s@\"]+)*)|(\".+\"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/;
				return re.test(email);
			}

			function mmValidateForm() {

				var firstName = document.forms["new_account"]["first_name"].value;
				var lastName = document.forms["new_account"]["last_name"].value;
				var email = document.forms["new_account"]["email"].value;
				var message = "";

				if (firstName == "") {
					document.getElementById("form_first_name").style.border='1px solid red';
					message = message + "First Name must be filled \n";
				} else
					document.getElementById("form_first_name").style.border='1px solid green';
				if (lastName == "") {
					document.getElementById("form_last_name").style.border='1px solid red';
					message = message + "Last Name must be filled \n";
				} else
					document.getElementById("form_last_name").style.border='1px solid green';

				if (email == "") {
					document.getElementById("form_email").style.border='1px solid red';
					  message = message + "Email must be filled \n";
				} else {
					if( !mmValidateEmail( email ) ) {
						document.getElementById("form_email").style.border='1px solid red';
						message = message + "Please check your email address."
					} else
						document.getElementById("form_email").style.border='1px solid green';
				}

				if( message != "" ) {
					alert( message );
					return false;
				}
			}

		</script>
		<?php

		delete_option( 'wpcompare_api_key' );
		delete_option( 'wpcompare_group_id');
		delete_option( 'wpcompare_monitoring_group_id' );

		$output =  '<div class="mm_wp_compare">
		<h1>WP Compare</h1>
		<h2>Create Free Account</h2>
		<p>Create now your free account and <strong>100 compares</strong> for one month for free!</p>
		<form id="frm_new_account" name="new_account" action="/wp-admin/admin.php?page=wp-compare&tab=settings" onsubmit="return mmValidateForm()" method="post">
			<input type="hidden" name="action" value="create_free_account"><br>
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
}

function mm_api( $args ) {

	$url = 'https://app.wpmike.com/v1/api.php';

    if( !isset( $args['api_key'] ) )
        $args['api_key'] = get_option( 'wpcompare_api_key' );

	$ch = curl_init( $url );
	//curl_setopt($ch, CURLOPT_USERPWD, "wpmike:letmein");
	curl_setopt($ch, CURLOPT_POSTFIELDS, $args);
	curl_setopt($ch, CURLOPT_TIMEOUT, 300);
	//curl_setopt($ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4 );

	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

	$result = curl_exec($ch);

	if( !$result )
		return curl_error($ch);

	if( isJson( $result ) )
		return json_decode( $result, true );
	else
		return $result;
}