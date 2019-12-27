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

	if( isset( $postdata['api-key'] ) ){
		update_option( 'wpcompare_api_key', $postdata['api-key'] );
		delete_option( 'wpcompare_group_id' );
	}

	if( isset( $postdata['action'] ) ) {
		switch( $postdata['action'] ) {
			case 'create_free_account':
				$api_key = $wp_comp->create_free_account();
				break;
		}
	}

	if( !isset( $api_key ) )
		$api_key = $wp_comp->get_api_key();

	//$verified = $wp_comp->verify_api_key( $api_key );

	if( !$api_key ){

		echo $wp_comp->get_no_account_page();

		return;
		//$api_key = $wp_comp->create_free_account();
	}


	$group_id = get_option( 'wpcompare_group_id' );

	if( !$group_id )
		$wp_comp->create_group( $api_key );

	$active_posts = array();

	// Get active posts from post data
	if( isset( $postdata['post-urls'] ) ) {
		
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
			'group_id'		=> $group_id,
			'posts'			=> json_encode( $active_posts ),
		);

		$results = mm_api( $args );
	}

	// Add group to screenshot queue
	if( isset( $postdata['take-screenshots'] ) ) {

		$results = $wp_comp->take_screenshot( $group_id, $api_key );

		//var_dump( $results );
		if( isset( $results['error'] ) )
			echo '<div class="error notice">
    				<p>' . $results['error'] . '</p>
				</div>';
		if( isset( $results['success'] ) )
			echo '<div class="updated notice">
				<p>' . $results['success'] . '</p>
			</div>';
	}

	// Get urls of group
	$args = array(
		'action'		=> 'get_group_urls',
		'group_id'		=> $group_id
	);
	$group_urls = mm_api( $args );

	$check_posts = array();
	foreach( $group_urls as $group_url ) {
		$check_posts[] = (int)$group_url['wp_post_id'];
		$check_desktop[$group_url['wp_post_id']] = $group_url['desktop'];
		$check_mobile[$group_url['wp_post_id']] = $group_url['mobile'];
	}

	$post_types = get_post_types();
	echo '<div class="mm_wp_compare">';
	echo '<h1>WP Compare</h1>';


	mm_tabs();

	echo '<div style="margin-top: 30px;"></div>';
	if( isset( $get['tab'] ) )
		$tab = $get['tab'];
	else
		$tab = 'take-screenshots';


	$client_details = $wp_comp->get_account_details( $api_key );

	$comp_usage = $wp_comp->get_usage( $api_key );

	$limit = $client_details[0]['comp_limit'];
	$available_compares = $limit - (int)$comp_usage;

	switch( $tab ) {

		case 'take-screenshots':
			// Take Screenshot

			echo '<h2>Do the magic</h2>';
			$args = array(
				'action'		=> 'get_amount_sc',
				'group_id'		=> $group_id
			);

			$amount_sc = mm_api( $args );
			echo '<p>Please select the URLs to take a screenshot for at <a href="/wp-admin/admin.php?page=wp-compare&tab=url-settings">Select URLs</a><br>
				Your available balance is ' . $available_compares . ' / ' . $limit . '<br>
				<strong>Currently selected amount of compares: ' . $amount_sc . '</strong></p>';

			echo '<form action="/wp-admin/admin.php?page=wp-compare&tab=take-screenshots" method="post">';
			echo '<input type="hidden" value="true" name="take-screenshots">';
			echo '<input type="submit" value="Take & Compare Screenshots" class="button">';
			echo '</form>';


			echo '<hr>';
			// Show queued urls
			echo '<h2>Currently Processing</h2>';
			$args = array(
				'action'	=> 'get_queue',
				'group_id'	=> $group_id
			);
			$queue = mm_api( $args );

			if( empty( $queue) )
				echo 'There are currently no urls to process.';
			else {
				echo '<table><tr><th>URL</th><th>Device</th><th>Status</th></tr>';

				foreach( $queue as $url ) {
					echo '<tr><td>' . $url['url'] . '</td><td>' . ucfirst( $url['device'] ) . '</td><td>Processing...</td></tr>';
				}
				echo '</table>';
			}
			echo '<hr>';

			// Compare overview
			echo '<h2>Latest compares</h2>';
			$args = array(
				'action'	=> 'get_compares',
				'domain'	=> $_SERVER['SERVER_NAME']
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

		case 'url-settings':

			// Select URLS
			echo '<form action="/wp-admin/admin.php?page=wp-compare&tab=url-settings" method="post">';
			echo '<input type="hidden" value="wp-compare" name="page">';
			echo '<input type="hidden" value="true" name="post-urls">';

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
						if( in_array( $post->ID, $check_posts ) )
							$checked = 'checked';
						else
							$checked = '';

						// Check Desktop
						if( isset( $check_desktop[$post->ID] ) && $check_desktop[$post->ID] == 1 )
							$checked_desktop = 'checked';
						else
							$checked_desktop = '';

						// Check Mobile
						if( isset( $check_mobile[$post->ID] ) && $check_mobile[$post->ID] == 1 )
							$checked_mobile = 'checked';
						else
							$checked_mobile = '';


						echo '<tr id="post_id_' . $post->ID . '">';
						echo '<td><input id="active-' . $post->ID . '" onclick="mmMarkRows(' . $post->ID . ')" type="checkbox" name="pid-' . $post->ID . '" value="' . $post->ID . '" ' . $checked . '></td>';
						echo '<td><input type="hidden" value="0" name="desktop-' . $post->ID . '"><input type="checkbox" name="desktop-' . $post->ID . '" value="1" ' . $checked_desktop . '></td>';
						echo '<td><input type="hidden" value="0" name="mobile-' . $post->ID . '"><input type="checkbox" name="mobile-' . $post->ID . '" value="1" ' . $checked_mobile . '></td>';
						echo '<td>' . $post->post_title . '</td>';
						echo '<td><a href="' . $url . '" target="_blank">' . $url . '</a></td>';
						echo '</tr>';

						echo '<script>mmMarkRows(' . $post->ID . '); </script>';


					}
					echo '</table>';

				}
			}
			echo '<input class="button" type="submit" value="Save" style="margin-top: 30px">';
			echo '</form>';
			break;

		case 'settings':

			//$api_key = get_option( 'wpcompare_api_key' );

			if( !$api_key ) {
				echo '<div class="error notice">
    				<p>Please enter a valid API Key.</p>
				</div>';
			} else {


				echo '<p>Monthly compares: ' . $limit . '</p>';

				echo '<p>Used compares: ' . $comp_usage . '</p>';
				//var_dump( $client_details );

				echo '<p>Available compares for this month: ' . $available_compares . '</p>';
			}

			echo '<form action="/wp-admin/admin.php?page=wp-compare&tab=settings" method="post">';
			echo 'API Key <input type="text" name="api-key" value="' . $api_key . '">';
			echo '<input type="submit" value="Save" class="button">';
			echo '</form>';
			break;

		case 'help':
			echo '<h2>How it works:</h2>';
			echo '<p>
					<ol><li>Select the urls you want to take a screenshot for and do a compare at <a href="/wp-admin/admin.php?page=wp-compare&tab=url-settings"> Select URLs</a></li>
					<li>Hit the Button "Take & Compare Screenshots". The URLs will be added to our queue. Below you can see all URLs in the queue</li>
					<li>When the screenshots and compares are finished, you can see the compare at "Latest Compares"</li></ol>
					At the Tab "Settings" you have an overview of your usage and limits. </p>';
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
?>
    <!-- Create a header in the default WordPress 'wrap' container -->
    <div class="wrap">

        <?php settings_errors(); ?>

        <?php
            if( isset( $_GET[ 'tab' ] ) ) {
                $active_tab = $_GET[ 'tab' ];
            } // end if
        ?>

        <h2 class="nav-tab-wrapper">
            <a href="?page=wp-compare&tab=take-screenshots" class="nav-tab <?php echo $active_tab == 'take-screenshots' ? 'nav-tab-active' : ''; ?>">Take and Compare Screenshots</a>
	        <a href="?page=wp-compare&tab=url-settings" class="nav-tab <?php echo $active_tab == 'url-settings' ? 'nav-tab-active' : ''; ?>">Select URLs</a>
            <a href="?page=wp-compare&tab=settings" class="nav-tab <?php echo $active_tab == 'settings' ? 'nav-tab-active' : ''; ?>">Settings</a>
            <a href="?page=wp-compare&tab=help" class="nav-tab <?php echo $active_tab == 'help' ? 'nav-tab-active' : ''; ?>">Help</a>
        </h2>

    </div>

	<!-- /.wrap -->
<?php
} // end sandbox_theme_display