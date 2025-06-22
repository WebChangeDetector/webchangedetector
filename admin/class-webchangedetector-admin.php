<?php
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

namespace WebChangeDetector;

/** WCD Admin Class.
 */
class WebChangeDetector_Admin {

	const API_TOKEN_LENGTH = 10;

	const VALID_WCD_ACTIONS = array(
		'reset_api_token',
		're-add-api-token',
		'save_api_token',
		'take_screenshots',
		'save_group_settings',
		'dashboard',
		'change-detections',
		'auto-settings',
		'logs',
		'settings',
		'show-compare',
		'create_trial_account',
		'update_detection_step',
		'add_post_type',
		'filter_change_detections',
		'change_comparison_status',
		'disable_wizard',
		'start_manual_checks',
		'sync_urls',
		'save_admin_bar_setting',
	);

	const VALID_SC_TYPES = array(
		'pre',
		'post',
		'auto',
		'compare',
	);

	const VALID_GROUP_TYPES = array(
		'all', // Filter.
		'generic', // Filter.
		'wordpress', // Filter.
		'auto',
		'post',
		'update',
		'auto-update',
	);

	const VALID_COMPARISON_STATUS = array(
		'new',
		'ok',
		'to_fix',
		'false_positive',

	);

	const WEEKDAYS = array(
		'monday',
		'tuesday',
		'wednesday',
		'thursday',
		'friday',
		'saturday',
		'sunday',
	);

	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string $plugin_name The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The monitoring checks group uuid.
	 *
	 * @since    1.0.0
	 * @access   public
	 * @var      string $monitoring_group_uuid The manual checks group uuid.
	 */
	public $monitoring_group_uuid;

	/**
	 * The manual checks group uuid.
	 *
	 * @since    1.0.0
	 * @access   public
	 * @var      string $manual_group_uuid The manual checks group uuid.
	 */
	public $manual_group_uuid;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string $version The current version of this plugin.
	 */
	private $version = WEBCHANGEDETECTOR_VERSION;

	/**
	 * Where the urls to sync are cached before they are sent.
	 *
	 * @var array Urls to sync.
	 */
	public $sync_urls;

	/**
	 * Screenshots handler instance.
	 *
	 * @since    1.0.0
	 * @access   public
	 * @var      WebChangeDetector_Admin_Screenshots $screenshots_handler Screenshots management.
	 */
	public $screenshots_handler;

	/**
	 * API Manager instance.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      WebChangeDetector_API_Manager $api_manager API communication handler.
	 */
	private $api_manager;

	/**
	 * Account handler instance.
	 *
	 * @since    1.0.0
	 * @access   public
	 * @var      WebChangeDetector_Admin_Account $account_handler Account management.
	 */
	public $account_handler;

	/**
	 * WordPress integration handler instance.
	 *
	 * @since    1.0.0
	 * @access   public
	 * @var      WebChangeDetector_Admin_WordPress $wordpress_handler WordPress integration.
	 */
	public $wordpress_handler;

	/**
	 * Settings handler instance.
	 *
	 * @since    1.0.0
	 * @access   public
	 * @var      WebChangeDetector_Admin_Settings $settings_handler Settings management.
	 */
	public $settings_handler;

	/**
	 * Dashboard handler instance.
	 *
	 * @since    1.0.0
	 * @access   public
	 * @var      WebChangeDetector_Admin_Dashboard $dashboard_handler Dashboard management.
	 */
	public $dashboard_handler;
	/**
	 * Initialize the class and set its properties.
	 *
	 * @param string $plugin_name The name of this plugin.
	 * @since    1.0.0
	 */
	public function __construct( $plugin_name = 'WebChangeDetector' ) {
		$this->plugin_name = $plugin_name;
		
		// Set the group uuids.
		$this->monitoring_group_uuid = get_option( WCD_WEBSITE_GROUPS )[ WCD_AUTO_DETECTION_GROUP ] ?? false;
		$this->manual_group_uuid     = get_option( WCD_WEBSITE_GROUPS )[ WCD_MANUAL_DETECTION_GROUP ] ?? false;

		// Initialize sync_urls array.
		$this->sync_urls = array();

		// Initialize specialized handlers.
		$this->api_manager = new WebChangeDetector_API_Manager();
		$this->account_handler = new WebChangeDetector_Admin_Account( $this->api_manager );
		$this->wordpress_handler = new WebChangeDetector_Admin_WordPress( $this->plugin_name, $this->version, $this );
		$this->screenshots_handler = new WebChangeDetector_Admin_Screenshots( $this );
		$this->settings_handler = new WebChangeDetector_Admin_Settings( $this );
		$this->dashboard_handler = new WebChangeDetector_Admin_Dashboard( $this, $this->api_manager, $this->wordpress_handler );
		
		// Add cron job for daily sync (after WordPress handler is initialized).
		add_action( 'wcd_daily_sync_event', array( $this->wordpress_handler, 'daily_sync_posts_cron_job' ) );
		if ( ! wp_next_scheduled( 'wcd_daily_sync_event' ) ) {
			wp_schedule_event( time(), 'daily', 'wcd_daily_sync_event' );
		}
	}



	/** Website details.
	 *
	 * @var array $website_details Array with website details.
	 */
	public $website_details;



	/**
	 * Get the domain from wp site_url.
	 *
	 * @return string
	 */
	public static function get_domain_from_site_url() {
		return rtrim( preg_replace( '(^https?://)', '', get_site_url() ?? '' ), '/' ); // Site might be in subdir.
	}
	

	/** Get queues for status processing and open.
	 *
	 * @param string $batch_id The batch id.
	 * @param int    $per_page Rows per page.
	 * @return array
	 */
	public function get_processing_queue_v2( $batch_id = false, $per_page = 30 ) {
		return \WebChangeDetector\WebChangeDetector_API_V2::get_queues_v2( $batch_id, 'processing,open', false, array( 'per_page' => $per_page ) );
	}











	// Moved to Dashboard handler

	



	/** Add Post type to website.
	 *
	 * @param array $postdata The postdata.
	 *
	 * @return void
	 */
// Moved to WordPress handler

	// Moved to WordPress handler

	// Moved to WordPress handler

	// Moved to WordPress handler

	// Moved to WordPress handler

	// Moved to WordPress handler

	/** Get the posts.
	 *
	 * @param array $post_types the post_types to get.
	 * @return void
	 */
	public function get_all_posts_data( $post_types ) {
		// Array to store all posts data.
		$all_posts_data = array();

		if ( empty( $post_types ) ) {
			return;
		}

		foreach ( $post_types as $single_post_type ) {
			// Set the batch size for both retrieving and uploading.
			$offset          = 0;
			$posts_per_batch = 1000;  // Number of posts to retrieve per query.

			do {
				\WebChangeDetector\WebChangeDetector_Admin_Utils::log_error( 'Getting next chunk. Offset: ' . $offset );
				// Set up WP_Query arguments.
				$args = array(
					'post_type'      => $single_post_type,  // Pass the array of post types.
					'post_status'    => 'publish',
					'posts_per_page' => $posts_per_batch,  // Fetch 500 posts at a time.
					'offset'         => $offset,
				);

				// Create a new query.
				$query = new \WP_Query( $args );

				// If no posts, break the loop.
				if ( ! $query->have_posts() ) {
					break;
				}

				// Process each post in the current batch.
				while ( $query->have_posts() ) {
					$query->the_post();

					$post_id    = get_the_ID();
					$post_title = get_the_title();
					$post_type  = get_post_type();
					$url        = get_permalink( $post_id );

					// Get the post type label.
					$post_type_object = get_post_type_object( $post_type );
					$post_type_label  = $post_type_object ? $post_type_object->labels->name : $post_type;

					// Add the data to the main array.
					$all_posts_data[ 'types%%' . $post_type_label ][] = array(
						'url'        => \WebChangeDetector\WebChangeDetector_Admin_Utils::remove_url_protocol( $url ),
						'html_title' => $post_title,
					);
				}

				// Reset post data to avoid conflicts in global post state.
				wp_reset_postdata();

				// Increment the offset for the next batch.
				$offset += $posts_per_batch;
				\WebChangeDetector\WebChangeDetector_Admin_Utils::log_error( 'Sending Posts.' );

				// Call uploadUrls after every batch.
				$this->upload_urls_in_batches( $all_posts_data );

				// Clear the data array after each batch to free memory.
				$all_posts_data = array();

				// Get the count of the results.
				$results_count = $query->post_count;
			} while ( $results_count === $posts_per_batch );
		}
	}

	/** Get the taxonomies.
	 *
	 * @param array $taxonomies The taxonomies.
	 * @return void
	 */
	public function get_all_terms_data( $taxonomies ) {

		// Array to store all terms data.
		$all_terms_data = array();

		if ( empty( $taxonomies ) ) {
			return;
		}

		$batch_size  = 500;  // Limit each batch to 500 terms.
		$offset      = 0;        // Initial offset to start from.
		$total_terms = true; // Placeholder to control loop.

		// Continue fetching terms until no more terms are found.
		while ( $total_terms ) {
			// Get terms in batches of 500 with an offset.
			$terms = get_terms(
				array(
					'taxonomy'   => $taxonomies, // Pass the taxonomies as an array.
					'hide_empty' => false,       // Show all terms, including those with no posts.
					'fields'     => 'all',       // Retrieve all term fields (term_id, name, slug, etc.).
					'number'     => $batch_size, // Fetch only 500 terms at a time.
					'offset'     => $offset,     // Offset to start from for each batch.
				)
			);

			// Check for errors or empty result.
			if ( is_wp_error( $terms ) || empty( $terms ) ) {
				// Stop the loop if no terms are found.
				$total_terms = false;
				continue;
			}

			// Process each term in the current batch.
			foreach ( $terms as $term ) {
				// Retrieve the term link (URL).
				$url = get_term_link( (int) $term->term_id, $term->taxonomy );

				// Retrieve the taxonomy object to get the label.
				$taxonomy_object = get_taxonomy( $term->taxonomy );
				$taxonomy_label  = $taxonomy_object ? $taxonomy_object->labels->name : $term->taxonomy;

				// Add the data to the main array.
				$all_terms_data[ 'taxonomy%%' . $taxonomy_label ][] = array(
					'url'        => \WebChangeDetector\WebChangeDetector_Admin_Utils::remove_url_protocol( $url ),
					'html_title' => $term->name,
				);
			}

			// Increment the offset for the next batch.
			$offset += $batch_size;

			// Call uploadUrls in batches of 500 elements.
			// Pass the entire $all_terms_data for each batch.
			$this->upload_urls_in_batches( $all_terms_data );

			// Reset the all_terms_data array after each batch to avoid memory overflow.
			$all_terms_data = array();
		}
	}

	/**
	 * Prepare urls for upload.
	 *
	 * @param array $upload_array The urls to upload.
	 */
	public function upload_urls_in_batches( $upload_array ) {
		if ( ! empty( $upload_array ) ) {
			$this->sync_urls[] = $upload_array;
		}
	}

	// Moved to WordPress handler

	// Moved to WordPress handler





	/**
	 * Creates Websites and Groups
	 *
	 * NOTE API Token needs to be sent here because it's not in the options yet
	 * at Website creation
	 *
	 * @return array
	 */
	public function create_website_and_groups() {
		$domain = self::get_domain_from_site_url();
		
		// Create monitoring group.
		$monitoring_group_args = array(
			'name'        => $domain,
			'monitoring'  => true,
			'enabled'     => true,
		);
		
		$monitoring_group_response = \WebChangeDetector\WebChangeDetector_API_V2::create_group_v2( $monitoring_group_args );
		
		// Create manual checks group.
		$manual_group_args = array(
			'name'       => $domain,
			'monitoring' => false,
			'enabled'    => true,
		);
		
		$manual_group_response = \WebChangeDetector\WebChangeDetector_API_V2::create_group_v2( $manual_group_args );
		
		// Check if both groups were created successfully.
		if ( ! empty( $monitoring_group_response['data']['id'] ) && ! empty( $manual_group_response['data']['id'] ) ) {
			// Create the website with the group IDs.
			$website_response = \WebChangeDetector\WebChangeDetector_API_V2::create_website_v2(
				$domain,
				$manual_group_response['data']['id'],
				$monitoring_group_response['data']['id']
			);
			
			// Check if website was created successfully.
            if ( ! empty( $website_response['data']['id'] ) ) {
                // Save group IDs to wp_options.
                $groups = array(
                    WCD_AUTO_DETECTION_GROUP   => $monitoring_group_response['data']['id'],
                    WCD_MANUAL_DETECTION_GROUP => $manual_group_response['data']['id'],
                );
                
                update_option( WCD_WEBSITE_GROUPS, $groups, false );

                // Directly set the group IDs to the class properties.
                $this->monitoring_group_uuid = $monitoring_group_response['data']['id'];
                $this->manual_group_uuid = $manual_group_response['data']['id'];
                
                // Ensure website details include default settings to avoid unnecessary API calls later.
                $website_data = $website_response['data'];
                
                // Set default sync types if not present.
                if ( empty( $website_data['sync_url_types'] ) ) {
                    $website_data['sync_url_types'] = array(
                        array(
                            'url_type_slug'  => 'types',
                            'url_type_name'  => 'Post Types',
                            'post_type_slug' => 'posts',
                            'post_type_name' => 'Posts',
                        ),
                        array(
                            'url_type_slug'  => 'types',
                            'url_type_name'  => 'Post Types',
                            'post_type_slug' => 'pages',
                            'post_type_name' => 'Pages',
                        ),
                    );
                }
                
                // Set default auto update settings if not present.
                if ( empty( $website_data['auto_update_settings'] ) ) {
                    $website_data['auto_update_settings'] = array(
                        'auto_update_checks_enabled'   => '0',
                        'auto_update_checks_from'      => gmdate( 'H:i' ),
                        'auto_update_checks_to'        => gmdate( 'H:i', strtotime( '+12 hours' ) ),
                        'auto_update_checks_monday'    => '1',
                        'auto_update_checks_tuesday'   => '1',
                        'auto_update_checks_wednesday' => '1',
                        'auto_update_checks_thursday'  => '1',
                        'auto_update_checks_friday'    => '1',
                        'auto_update_checks_saturday'  => '0',
                        'auto_update_checks_sunday'    => '0',
                        'auto_update_checks_emails'    => get_option( 'admin_email' ),
                    );
                }

                // Return success response with complete website data.
                return array(
                    'website'          => $website_data,
                    'monitoring_group' => $monitoring_group_response['data'],
                    'manual_group'     => $manual_group_response['data'],
                );
            } else {
                // Return error if website couldn't be created.
                return array(
                    'error'            => 'Failed to create website',
                    'website_response' => $website_response,
                );
            }
        }
        
		
        // Return error if groups couldn't be created.
        return array(
            'error'              => 'Failed to create groups',
            'monitoring_response' => $monitoring_group_response,
            'manual_response'     => $manual_group_response,
        );
	}

	/** Get params of an url.
	 *
	 * @param string $url The url.
	 * @return array|false
	 */
	public function get_params_of_url( $url ) {
		if ( empty( $url ) ) {
			return false;
		}

		$url_components = wp_parse_url( $url );
		parse_str( $url_components['query'], $params );
		return $params;
	}

	/** Print the monitoring status bar.
	 *
	 * @param array $group The group details.
	 * @return void
	 */
	public function print_monitoring_status_bar( $group ) {
		// Calculation for monitoring.
		$date_next_sc = false;

		$amount_sc_per_day = 0;

		// Check for intervals >= 1h.
		if ( $group['interval_in_h'] >= 1 ) {
			$next_possible_sc  = gmmktime( gmdate( 'H' ) + 1, 0, 0, gmdate( 'm' ), gmdate( 'd' ), gmdate( 'Y' ) );
			$amount_sc_per_day = ( 24 / $group['interval_in_h'] );
			$possible_hours    = array();

			// Get possible tracking hours.
			for ( $i = 0; $i <= $amount_sc_per_day * 2; $i++ ) {
				$possible_hour    = $group['hour_of_day'] + $i * $group['interval_in_h'];
				$possible_hours[] = $possible_hour >= 24 ? $possible_hour - 24 : $possible_hour;
			}
			sort( $possible_hours );

			// Check for today and tomorrow.
			for ( $ii = 0; $ii <= 1; $ii++ ) { // Do 2 loops for today and tomorrow.
				for ( $i = 0; $i <= $amount_sc_per_day * 2; $i++ ) {
					$possible_time = gmmktime( $possible_hours[ $i ], 0, 0, gmdate( 'm' ), gmdate( 'd' ) + $ii, gmdate( 'Y' ) );

					if ( $possible_time >= $next_possible_sc ) {
						$date_next_sc = $possible_time; // This is the next possible time. So we break here.
						break;
					}
				}

				// Don't check for tomorrow if we found the next date today.
				if ( $date_next_sc ) {
					break;
				}
			}
		}

		// Check for 30 min intervals.
		if ( 0.5 === $group['interval_in_h'] ) {
			$amount_sc_per_day = 48;
			if ( gmdate( 'i' ) < 30 ) {
				$date_next_sc = gmmktime( gmdate( 'H' ), 30, 0, gmdate( 'm' ), gmdate( 'd' ), gmdate( 'Y' ) );
			} else {
				$date_next_sc = gmmktime( gmdate( 'H' ) + 1, 0, 0, gmdate( 'm' ), gmdate( 'd' ), gmdate( 'Y' ) );
			}
		}
		// Check for 15 min intervals.
		if ( 0.25 === $group['interval_in_h'] ) {
			$amount_sc_per_day = 96;
			if ( gmdate( 'i' ) < 15 ) {
				$date_next_sc = gmmktime( gmdate( 'H' ), 15, 0, gmdate( 'm' ), gmdate( 'd' ), gmdate( 'Y' ) );
			} elseif ( gmdate( 'i' ) < 30 ) {
				$date_next_sc = gmmktime( gmdate( 'H' ), 30, 0, gmdate( 'm' ), gmdate( 'd' ), gmdate( 'Y' ) );
			} elseif ( gmdate( 'i' ) < 45 ) {
				$date_next_sc = gmmktime( gmdate( 'H' ), 45, 0, gmdate( 'm' ), gmdate( 'd' ), gmdate( 'Y' ) );
			} else {
				$date_next_sc = gmmktime( gmdate( 'H' ) + 1, 0, 0, gmdate( 'm' ), gmdate( 'd' ), gmdate( 'Y' ) );
			}
		}

		// Calculate screenshots until renewal.
		$days_until_renewal = gmdate( 'd', gmdate( 'U', strtotime( $this->account_handler->get_account()['renewal_at'] ) ) - gmdate( 'U' ) );

		$amount_group_sc_per_day = $group['selected_urls_count'] * $amount_sc_per_day * $days_until_renewal;

		// Get first detection hour.
		$first_hour_of_interval = $group['hour_of_day'];
		while ( $first_hour_of_interval - $group['interval_in_h'] >= 0 ) {
			$first_hour_of_interval = $first_hour_of_interval - $group['interval_in_h'];
		}

		// Count up in interval_in_h to current hour.
		$skip_sc_count_today = 0;
		while ( $first_hour_of_interval + $group['interval_in_h'] <= gmdate( 'H' ) ) {
			$first_hour_of_interval = $first_hour_of_interval + $group['interval_in_h'];
			++$skip_sc_count_today;
		}

		// Subtract screenshots already taken today.
		$total_sc_current_period = $amount_group_sc_per_day - $skip_sc_count_today * $group['selected_urls_count'];
		?>

		<div class="status_bar">
			<div class="box full">
				<div id="txt_next_sc_in">Next monitoring checks in</div>
				<div id="next_sc_in" class="big"></div>
				<div id="next_sc_date" class="local-time" data-date="<?php echo esc_html( $date_next_sc ); ?>"></div>
				<div id="sc_available_until_renew"
					data-amount_selected_urls="<?php echo esc_html( $group['selected_urls_count'] ); ?>"
					data-auto_sc_per_url_until_renewal="<?php echo esc_html( $total_sc_current_period ); ?>"></div>
			</div>
		</div>
		<?php
	}

	/** Group settings and url selection view.
	 *
	 * @param bool $monitoring_group Is it a monitoring group.
	 *
	 * @return void
	 */
	public function get_url_settings( $monitoring_group = false ) {
		// Sync urls - post_types defined in function @TODO make settings for post_types to sync.

		if ( ! empty( $_GET['_wpnonce'] ) && ! wp_verify_nonce( wp_unslash( sanitize_key( $_GET['_wpnonce'] ) ) ) ) {
			echo 'Something went wrong. Try again.';
			wp_die();
		}

		if ( $monitoring_group ) {
			$group_id = $this->monitoring_group_uuid;
		} else {
			$group_id = $this->manual_group_uuid;
		}

		// Setting pagination page.
		$page = 1;
		if ( ! empty( $_GET['paged'] ) ) {
			$page = sanitize_key( wp_unslash( $_GET['paged'] ) );
		}

		// Set filters for urls.
		$filters = array(
			'per_page' => 20,
			'sorted'   => 'selected',
			'page'     => $page,
		);

		$pagination_params = array();
		if ( ! empty( $_GET['post-type'] ) ) {
								$filters['category']            = \WebChangeDetector\WebChangeDetector_Admin_Utils::get_post_type_name( sanitize_text_field( wp_unslash( $_GET['post-type'] ) ) );
			$pagination_params['post-type'] = sanitize_text_field( wp_unslash( $_GET['post-type'] ) );
		}
		if ( ! empty( $_GET['taxonomy'] ) ) {
							$filters['category']           = \WebChangeDetector\WebChangeDetector_Admin_Utils::get_taxonomy_name( sanitize_text_field( wp_unslash( $_GET['taxonomy'] ) ) );
			$pagination_params['taxonomy'] = sanitize_text_field( wp_unslash( $_GET['post-type'] ) );

		}
		if ( ! empty( $_GET['search'] ) ) {
			$filters['search']           = sanitize_text_field( wp_unslash( $_GET['search'] ) );
			$pagination_params['search'] = sanitize_text_field( wp_unslash( $_GET['search'] ) );
		}

		// Get the urls.
		$group_and_urls = $this->get_group_and_urls( $group_id, $filters );
		$urls           = $group_and_urls['urls'];
		$urls_meta      = $group_and_urls['meta'];

		// Set tab for the right url.
		$tab = 'update-settings'; // init.
		if ( $monitoring_group ) {
			$tab = 'auto-settings';
		}

		// Show message if no urls are selected.
		if ( ! $group_and_urls['selected_urls_count'] ) {
			?>
			<div class="notice notice-warning"><p><strong>WebChange Detector:</strong> Select URLs for manual checks to get started.</p></div>
			<?php
		}

		$nonce = wp_create_nonce( 'ajax-nonce' );
		?>

		<div class="wcd-select-urls-container">
			<?php

			// Include the group settings.
			if ( ! $monitoring_group ) {
				include 'partials/templates/update-settings.php';
			} else {

				// Print the status bar.
				$this->print_monitoring_status_bar( $group_and_urls );

				// Monitoring settings.
				include 'partials/templates/auto-settings.php';
			}

			// Select URLs section.
			if ( ( ! $monitoring_group && $this->settings_handler->is_allowed( 'manual_checks_urls' ) ) || ( $monitoring_group && $this->settings_handler->is_allowed( 'monitoring_checks_urls' ) ) ) {
				?>

			<div class="wcd-frm-settings box-plain">
				<h2>Select URLs to Check<br><small></small></h2>
				<p style="text-align: center;">
					<strong>Currently selected URLs: <?php echo esc_html( $group_and_urls['selected_urls_count'] ); ?></strong><br>
					Missing URLs? Select them from other post types and taxonomies by enabling them in the
					<a href="?page=webchangedetector-settings">Settings</a><br>
					
				</p>
				<input type="hidden" value="webchangedetector" name="page">
				<input type="hidden" value="<?php echo esc_html( $group_and_urls['id'] ); ?>" name="group_id">

					<div class="group_urls_container">
						<form method="get" style="float: left;">
							<input type="hidden" name="page" value="webchangedetector-<?php echo esc_html( $tab ); ?>">

							Post types
							<select id="filter-post-type" name="post-type">
								<option value="0">All</option>
								<?php
								$selected_post_type = isset( $_GET['post-type'] ) ? sanitize_text_field( wp_unslash( $_GET['post-type'] ) ) : array();

								// Fix for old sync_url_types.
								$sync_url_types = $this->website_details['sync_url_types'];
								if ( is_string( $this->website_details['sync_url_types'] ) ) {
									$sync_url_types = json_decode( $this->website_details['sync_url_types'], true );
								}

								if ( ! get_option( 'page_on_front' ) && ! in_array( 'frontpage', array_column( $sync_url_types, 'post_type_slug' ), true ) ) {
									?>
									<option value="frontpage" <?php echo 'frontpage' === $selected_post_type ? 'selected' : ''; ?> >Frontpage</option>
									<?php
								}

								foreach ( $sync_url_types as $url_type ) {
									if ( 'types' !== $url_type['url_type_slug'] ) {
										continue;
									}
									$selected = $url_type['post_type_slug'] === $selected_post_type ? 'selected' : '';
									?>
									<option value="<?php echo esc_html( $url_type['post_type_slug'] ); ?>" <?php echo esc_html( $selected ); ?>>
										<?php echo esc_html( \WebChangeDetector\WebChangeDetector_Admin_Utils::get_post_type_name( $url_type['post_type_slug'] ) ); ?>
									</option>
								<?php } ?>
							</select>

							Taxonomies
							<select id="filter-taxonomy" name="taxonomy">
								<option value="0">All</option>
								<?php
								$selected_post_type = isset( $_GET['taxonomy'] ) ? sanitize_text_field( wp_unslash( $_GET['taxonomy'] ) ) : '';

								foreach ( $sync_url_types as $url_type ) {
									if ( 'types' === $url_type['url_type_slug'] ) {
										continue;
									}
									$selected = $url_type['post_type_slug'] === $selected_post_type ? 'selected' : '';
									?>
									<option value="<?php echo esc_html( $url_type['post_type_slug'] ); ?>" <?php echo esc_html( $selected ); ?>>
										<?php echo esc_html( \WebChangeDetector\WebChangeDetector_Admin_Utils::get_taxonomy_name( $url_type['post_type_slug'] ) ); ?>
									</option>
								<?php } ?>
							</select>
							<button class="button button-secondary">Filter</button>
						</form>

						<script>
							jQuery("#filter-post-type").change(function() {
								if(jQuery(this).val() !== '0') {
									jQuery('#filter-taxonomy').val(0);
								}
							});

							jQuery("#filter-taxonomy").change(function() {
								if(jQuery(this).val() !== '0') {
									jQuery('#filter-post-type').val(0);
								}
							});
						</script>

						<form method="get" style="float: right;">
							<input type="hidden" name="page" value="webchangedetector-<?php echo esc_html( $tab ); ?>">
							<button type="submit" style="float: right" class="button button-secondary">Search</button>
							<input style="margin: 0" name="search" type="text" placeholder="Search" value="<?php echo esc_html( sanitize_text_field( wp_unslash( $_GET['search'] ?? '' ) ) ); ?>">
						</form>
						<div class="clear" style="margin-bottom: 20px;"></div>

						<table class="no-margin filter-table">
							<tr>
												<th style="min-width: 50px; text-align: center;"><?php \WebChangeDetector\WebChangeDetector_Admin_Utils::get_device_icon( 'desktop' ); ?><br>Desktop</th>
				<th style="min-width: 50px; text-align: center;"><?php \WebChangeDetector\WebChangeDetector_Admin_Utils::get_device_icon( 'mobile' ); ?> Mobile</th>
								<th style="width: 100%">URL</th>
								<th style="min-width: 90px">Post type</th>
							</tr>
							<?php if ( count( $urls ) ) { ?>
								<?php // Select all from same device. ?>
								<tr class=" even-tr-white" style="background: none; text-align: center">
									<td>
										<label class="switch">
											<input type="checkbox"
											id="select-desktop"
											data-nonce="<?php echo esc_html( $nonce ); ?>"
											data-screensize="desktop"
											onclick="mmToggle( this, 'desktop', '<?php echo esc_html( $group_and_urls['id'] ); ?>' ); postUrl('select-desktop');"/>
											<span class="slider round"></span>
										</label>
									</td>

									<td>
										<label class="switch">
											<input type="checkbox"
											id="select-mobile"
											data-nonce="<?php echo esc_html( $nonce ); ?>"
											data-screensize="mobile"
											onclick="mmToggle( this, 'mobile', '<?php echo esc_html( $group_and_urls['id'] ); ?>' ); postUrl('select-mobile');" />
											<span class="slider round"></span>
										</label>
									</td>
									<td></td>
									<td></td>
								</tr>
								<?php

								foreach ( $urls as $url ) {
									// init.
									$checked = array(
										'desktop' => $url['desktop'] ? 'checked' : '',
										'mobile'  => $url['mobile'] ? 'checked' : '',
									);
									?>
									<tr class="live-filter-row even-tr-white post_id_<?php echo esc_html( $group_and_urls['id'] ); ?>" id="<?php echo esc_html( $url['id'] ); ?>" >
										<td class="checkbox-desktop" style="text-align: center;">
											<input type="hidden" value="0" name="desktop-<?php echo esc_html( $url['id'] ); ?>">
											<label class="switch">
												<input type="checkbox"
												data-nonce="<?php echo esc_html( $nonce ); ?>"
												data-type="<?php echo esc_html( lcfirst( $url['category'] ) ); ?>"
												data-screensize="desktop"
												data-url_id="<?php echo esc_html( $url['id'] ); ?>"
												name="desktop-<?php echo esc_html( $url['id'] ); ?>"
												value="1" <?php echo esc_html( $checked['desktop'] ); ?>
												id="desktop-<?php echo esc_html( $url['id'] ); ?>"
												onclick="mmMarkRows('<?php echo esc_html( $url['id'] ); ?>'); postUrl('<?php echo esc_html( $url['id'] ); ?>');" >
												<span class="slider round"></span>
											</label>
										</td>

										<td class="checkbox-mobile" style="text-align: center;">
										<input type="hidden" value="0" name="mobile-<?php echo esc_html( $url['id'] ); ?>">
										<label class="switch">
											<input type="checkbox"
											data-nonce="<?php echo esc_html( $nonce ); ?>"
											data-type="<?php echo esc_html( lcfirst( $url['category'] ) ); ?>"
											data-screensize="mobile"
											data-url_id="<?php echo esc_html( $url['id'] ); ?>"
											name="mobile-<?php echo esc_html( $url['id'] ); ?>"
											value="1" <?php echo esc_html( $checked['mobile'] ); ?>
											id="mobile-<?php echo esc_html( $url['id'] ); ?>"
											onclick="mmMarkRows('<?php echo esc_html( $url['id'] ); ?>'); postUrl('<?php echo esc_html( $url['id'] ); ?>');" >
											<span class="slider round"></span>
										</label>
										</td>

										<td style="text-align: left;">
											<strong><?php echo esc_html( $url['html_title'] ); ?></strong><br>
											<a href="<?php echo ( is_ssl() ? 'https://' : 'http://' ) . esc_html( $url['url'] ); ?>" target="_blank"><?php echo esc_html( $url['url'] ); ?></a>
										</td>
										<td><?php echo esc_html( $url['category'] ); ?></td>
									</tr>

									<script> mmMarkRows('<?php echo esc_html( $url['id'] ); ?>'); </script>

									<?php
								}
							} else {
								?>
								<tr>
									<td colspan="4" style="text-align: center; font-weight: 700; padding: 20px 0;">
										No Urls to show.
									</td>
								</tr>
							<?php } ?>
						</table>
					</div>

					<!-- Pagination -->
					<?php if ( ! empty( $urls_meta['total'] ) ) { ?>
					<div class="tablenav">
						<div class="tablenav-pages">
							<span class="displaying-num"><?php echo esc_html( $urls_meta['total'] ); ?> items</span>
							<span class="pagination-links">
							<?php
							foreach ( $urls_meta['links'] as $link ) {
								$pagination_page = $this->get_params_of_url( $link['url'] )['page'] ?? '';
								if ( ! $link['active'] && $pagination_page ) {
									$pagination_link = '?page=webchangedetector-' . esc_html( $tab ) . '&paged=' . esc_html( $pagination_page ) . '&' . esc_html( build_query( $pagination_params ) );
									$pagination_link = wp_nonce_url( $pagination_link );
									?>
									<a class="tablenav-pages-navspan button"
										href="<?php echo esc_html( $pagination_link ); ?>">
										<?php echo esc_html( $link['label'] ); ?>
									</a>
								<?php } else { ?>
									<span class="tablenav-pages-navspan button" disabled=""><?php echo esc_html( $link['label'] ); ?></span>
									<?php
								}
							}
							?>
							</span>
						</div>
					</div>

					<script>
						if(<?php echo isset( $_GET['paged'] ) ? 1 : 0; ?> ||
						<?php echo isset( $_GET['search'] ) ? 1 : 0; ?> ||
						<?php echo isset( $_GET['post-type'] ) ? 1 : 0; ?> ||
						<?php echo isset( $_GET['taxonomy'] ) ? 1 : 0; ?> ) {
							const scrollToEl = jQuery('.group_urls_container');
							jQuery('html').animate(
								{
									scrollTop: scrollToEl.offset().top,
								},
								0 //speed
							);
						}
					</script>
				<?php } ?>
			</div>
			<?php } ?>
		</div>

		<?php
		if ( ! $monitoring_group ) {
			// Start change detection button.
			if ( $this->settings_handler->is_allowed( 'manual_checks_start' ) ) {
				?>
					<form method="post">
						<?php wp_nonce_field( 'start_manual_checks' ); ?>
						<input type="hidden" name="wcd_action" value="start_manual_checks">
						<input type="hidden" name="step" value="<?php echo esc_html( WCD_OPTION_UPDATE_STEP_PRE ); ?>">

						<button
								class="button button-primary wizard-start-manual-checks"
								style="float: right;"
								type="submit"
						>
							Start manual checks >
						</button>
					</form>
				<?php
			}
		}
	}



	/** Save url settings.
	 *  TODO: Optional save settings for monitoring and manual checks.
	 *
	 * @param array $postdata The postdata.
	 *
	 * @return void
	 */
	public function post_urls( $postdata ) {
		$active_posts   = array();
		$count_selected = 0;

		foreach ( $postdata as $key => $post ) {
			$already_processed_ids = array();
			if ( 0 === strpos( $key, 'desktop-' ) || 0 === strpos( $key, 'mobile-' ) ) {

				$post_id = 0 === strpos( $key, 'desktop-' ) ? substr( $key, strlen( 'desktop-' ) ) : substr( $key, strlen( 'mobile-' ) );

				// Make sure to not process same post_id twice.
				if ( in_array( $post_id, $already_processed_ids, true ) ) {
					continue;
				}
				$already_processed_ids[] = $post_id;

				$desktop = array_key_exists( 'desktop-' . $post_id, $postdata ) ? ( $postdata[ 'desktop-' . $post_id ] ) : null;
				$mobile  = array_key_exists( 'mobile-' . $post_id, $postdata ) ? ( $postdata[ 'mobile-' . $post_id ] ) : null;

				$new_post = array( 'id' => $post_id );
				if ( ! is_null( $desktop ) ) {
					$new_post['desktop'] = $desktop;
				}
				if ( ! is_null( $mobile ) ) {
					$new_post['mobile'] = $mobile;
				}
				$active_posts[] = $new_post;

				if ( isset( $postdata[ 'desktop-' . $post_id ] ) && 1 === $postdata[ 'desktop-' . $post_id ] ) {
					++$count_selected;
				}

				if ( isset( $postdata[ 'mobile-' . $post_id ] ) && 1 === $postdata[ 'mobile-' . $post_id ] ) {
					++$count_selected;
				}
			}
		}

		$group_id_website_details = sanitize_text_field( $postdata['group_id'] );
		\WebChangeDetector\WebChangeDetector_API_V2::update_urls_in_group_v2( $group_id_website_details, $active_posts );

		// TODO Make return to show the result.
		echo '<div class="updated notice"><p>Settings saved.</p></div>';
	}















	/** View of tabs
	 *
	 * @return void
	 */
	public function tabs() {
		$active_tab = 'webchangedetector'; // init.

		if ( ! empty( $_GET['_wpnonce'] ) && ! wp_verify_nonce( wp_unslash( sanitize_key( $_GET['_wpnonce'] ) ) ) ) {
			echo 'Something went wrong. Please try again.';
		}

		if ( isset( $_GET['page'] ) ) {
			$active_tab = sanitize_text_field( wp_unslash( $_GET['page'] ) );
		}
		?>
		<div class="wrap">
			<h2 class="nav-tab-wrapper">
				<?php if ( $this->settings_handler->is_allowed( 'dashboard_view' ) ) { ?>
				<a href="?page=webchangedetector"
					class="nav-tab <?php echo 'webchangedetector' === $active_tab ? 'nav-tab-active' : ''; ?>">
					<?php \WebChangeDetector\WebChangeDetector_Admin_Utils::get_device_icon( 'dashboard' ); ?> Dashboard
				</a>
				<?php } ?>
				<?php if ( $this->settings_handler->is_allowed( 'manual_checks_view' ) ) { ?>
				<a href="?page=webchangedetector-update-settings"
					class="nav-tab <?php echo 'webchangedetector-update-settings' === $active_tab ? 'nav-tab-active' : ''; ?>">
					<?php \WebChangeDetector\WebChangeDetector_Admin_Utils::get_device_icon( 'update-group' ); ?> Manual Checks & Auto Update Checks
				</a>
				<?php } ?>
				<?php if ( $this->settings_handler->is_allowed( 'monitoring_checks_view' ) ) { ?>
					<a href="?page=webchangedetector-auto-settings"
					class="nav-tab <?php echo 'webchangedetector-auto-settings' === $active_tab ? 'nav-tab-active' : ''; ?>">
					<?php \WebChangeDetector\WebChangeDetector_Admin_Utils::get_device_icon( 'auto-group' ); ?> Monitoring
				</a>
				<?php } ?>
				<?php if ( $this->settings_handler->is_allowed( 'change_detections_view' ) ) { ?>
					<a href="?page=webchangedetector-change-detections"
					class="nav-tab <?php echo 'webchangedetector-change-detections' === $active_tab ? 'nav-tab-active' : ''; ?>">
					<?php \WebChangeDetector\WebChangeDetector_Admin_Utils::get_device_icon( 'change-detections' ); ?> Change Detections
				</a>
				<?php } ?>
				<?php if ( $this->settings_handler->is_allowed( 'logs_view' ) ) { ?>
				<a href="?page=webchangedetector-logs"
					class="nav-tab <?php echo 'webchangedetector-logs' === $active_tab ? 'nav-tab-active' : ''; ?>">
					<?php \WebChangeDetector\WebChangeDetector_Admin_Utils::get_device_icon( 'logs' ); ?> Queue
				</a>
				<?php } ?>
				<?php if ( $this->settings_handler->is_allowed( 'settings_view' ) ) { ?>
				<a href="?page=webchangedetector-settings"
					class="nav-tab <?php echo 'webchangedetector-settings' === $active_tab ? 'nav-tab-active' : ''; ?>">
					<?php \WebChangeDetector\WebChangeDetector_Admin_Utils::get_device_icon( 'settings' ); ?> Settings
				</a>
				<?php } ?>
				<?php if ( $this->settings_handler->is_allowed( 'upgrade_account' ) ) { ?>
				<a href="<?php echo esc_url( $this->account_handler->get_upgrade_url() ); ?>" target="_blank"
					class="nav-tab upgrade">
					<?php \WebChangeDetector\WebChangeDetector_Admin_Utils::get_device_icon( 'upgrade' ); ?> Upgrade Account
				</a>
				<?php } ?>
			</h2>
		</div>

		<?php
	}

	/** Get the dashboard view.
	// Moved to Dashboard handler

	// Moved to Dashboard handler



	/**
	 * If in development mode
	 *
	 * @return bool
	 */




	/** Get group details and its urls.
	 *
	 * @param string $group_uuid The group id.
	 * @param array  $url_filter Filters for the urls.
	 *
	 * @return mixed
	 */
	public function get_group_and_urls( $group_uuid, $url_filter = array() ) {

		$group_and_urls = \WebChangeDetector\WebChangeDetector_API_V2::get_group_v2( $group_uuid )['data'];
		$urls           = \WebChangeDetector\WebChangeDetector_API_V2::get_group_urls_v2( $group_uuid, $url_filter );

		if ( empty( $urls['data'] ) ) {
			$this->wordpress_handler->sync_posts( true );
			$urls = \WebChangeDetector\WebChangeDetector_API_V2::get_group_urls_v2( $group_uuid, $url_filter );
		}

		$group_and_urls['urls']                = $urls['data'];
		$group_and_urls['meta']                = $urls['meta'];
		$group_and_urls['selected_urls_count'] = $urls['meta']['selected_urls_count'];

		return $group_and_urls;
	}

	/**
	 * Call to V1 API.
	 *
	 * @param array $post Request data.
	 * @param bool  $is_web Is web request.
	 * @return string|array
	 */
	public function api_v1( $post, $is_web = false ) {
		$url     = 'https://api.webchangedetector.com/api/v1/'; // init for production.
		$url_web = 'https://api.webchangedetector.com/';

		// This is where it can be changed to a local/dev address.
		if ( defined( 'WCD_API_URL' ) && is_string( WCD_API_URL ) && ! empty( WCD_API_URL ) ) {
			$url = WCD_API_URL;
		}

		// Overwrite $url if it is a get request.
		if ( $is_web && defined( 'WCD_API_URL_WEB' ) && is_string( WCD_API_URL_WEB ) && ! empty( WCD_API_URL_WEB ) ) {
			$url_web = WCD_API_URL_WEB;
		}

		$url     .= str_replace( '_', '-', $post['action'] ); // add kebab action to url.
		$url_web .= str_replace( '_', '-', $post['action'] ); // add kebab action to url.
		$action   = $post['action']; // For debugging.

		// Get API Token from WP DB.
		$api_token = $post['api_token'] ?? get_option( WCD_WP_OPTION_KEY_API_TOKEN ) ?? null;

		unset( $post['action'] ); // don't need to send as action as it's now the url.
		unset( $post['api_token'] ); // just in case.

		$post['wp_plugin_version'] = WEBCHANGEDETECTOR_VERSION; // API will check this to check compatability.
		// there's checks in place on the API side, you can't just send a different domain here, you sneaky little hacker ;).
		$post['domain'] = self::get_domain_from_site_url();
		$post['wp_id']  = get_current_user_id();

		$args = array(
			'timeout' => WCD_REQUEST_TIMEOUT,
			'body'    => $post,
			'headers' => array(
				'Accept'        => 'application/json',
				'Authorization' => 'Bearer ' . $api_token,
				'x-wcd-domain'  => self::get_domain_from_site_url(),
				'x-wcd-wp-id'   => get_current_user_id(),
				'x-wcd-plugin'  => 'webchangedetector-official/' . WEBCHANGEDETECTOR_VERSION,
			),
		);

		\WebChangeDetector\WebChangeDetector_Admin_Utils::log_error( 'API V1 request: ' . $url . ' | Args: ' . wp_json_encode( $args ) );
		if ( $is_web ) {
			$response = wp_remote_post( $url_web, $args );
		} else {
			$response = wp_remote_post( $url, $args );
		}

		$body          = wp_remote_retrieve_body( $response );
		$response_code = (int) wp_remote_retrieve_response_code( $response );

		$decoded_body = json_decode( $body, (bool) JSON_OBJECT_AS_ARRAY );

		// `message` is part of the Laravel Stacktrace.
		if ( WCD_HTTP_BAD_REQUEST === $response_code &&
			is_array( $decoded_body ) &&
			array_key_exists( 'message', $decoded_body ) &&
			'plugin_update_required' === $decoded_body['message'] ) {
			return 'update plugin';
		}

		if ( WCD_HTTP_INTERNAL_SERVER_ERROR === $response_code && 'account_details' === $action ) {
			return 'activate account';
		}

		if ( WCD_HTTP_UNAUTHORIZED === $response_code ) {
			return 'unauthorized';
		}

		// if parsing JSON into $decoded_body was without error.
		if ( JSON_ERROR_NONE === json_last_error() ) {
			return $decoded_body;
		}

		return $body;
	}



	/** Generate HTML for a single slider.
	 * (This function might be used by JS via another AJAX call, or JS might replicate the HTML.
	 * Keep it available for now, but ensure JS doesn't rely on PHP rendering context unavailable in AJAX)
	 *
	 * @param string      $type 'monitoring' or 'manual'.
	 * @param string      $device 'desktop' or 'mobile'.
	 * @param bool        $is_enabled Current state.
	 * @param string      $url Current page URL (received via AJAX).
	 * @param string|null $url_id WCD URL ID if known.
	 * @param string|null $group_id WCD Group ID.
	 * @return string HTML for the slider.
	 */
	public function generate_slider_html( $type, $device, $is_enabled, $url, $url_id, $group_id ) {
		$checked = $is_enabled ? 'checked' : '';
		$label   = ucfirst( $device ); // Consider using localized labels passed to JS.
		$id      = sprintf( 'wcd-slider-%s-%s-%s', $type, $device, str_replace( array( '.', ':', '/' ), '-', $url_id ?? wp_generate_password( 5, false ) ) ); // Make ID more unique for AJAX.

		// Data attributes for JS.
		$data_attrs = sprintf(
			'data-type="%s" data-device="%s" data-url="%s" data-url-id="%s" data-group-id="%s"',
			esc_attr( $type ),
			esc_attr( $device ),
			esc_attr( $url ), // Use the URL passed to the function.
			esc_attr( $url_id ?? '' ),
			esc_attr( $group_id ?? '' )
		);

		// Simple switch HTML structure. Use localized labels if possible.
		$html = sprintf(
			'<div class="wcd-admin-bar-slider">' .
			'<label for="%s" class="wcd-slider-label">%s:</label>' . // TODO: Use localized label from wcdAdminBarData.
			'<label class="wcd-switch">' .
			'<input type="checkbox" id="%s" class="wcd-admin-bar-toggle" %s %s> ' . // Class for existing JS?.
			'<span class="wcd-slider-round"></span>' .
			'</label>' .
			'</div>',
			esc_attr( $id ),
			esc_html( $label ), // TODO: Use localized label.
			esc_attr( $id ),
			$checked,
			$data_attrs
		);

		return $html;
	}
} // End class WebChangeDetector_Admin.

// HTTP Status Codes.
if ( ! defined( 'WCD_HTTP_BAD_REQUEST' ) ) {
	define( 'WCD_HTTP_BAD_REQUEST', 400 );
}

if ( ! defined( 'WCD_HTTP_UNAUTHORIZED' ) ) {
	define( 'WCD_HTTP_UNAUTHORIZED', 401 );
}

if ( ! defined( 'WCD_HTTP_INTERNAL_SERVER_ERROR' ) ) {
	define( 'WCD_HTTP_INTERNAL_SERVER_ERROR', 500 );
}

// Time/Date Related.
if ( ! defined( 'WCD_DAYS_PER_MONTH' ) ) {
	define( 'WCD_DAYS_PER_MONTH', 30 );
}

if ( ! defined( 'WCD_HOURS_IN_DAY' ) ) {
	define( 'WCD_HOURS_IN_DAY', 24 );
}

if ( ! defined( 'WCD_SECONDS_IN_MONTH' ) ) {
	// 60 * 60 * 24 * 30.
	define( 'WCD_SECONDS_IN_MONTH', 2592000 );
}

// Option secret for domain verification.
if ( ! defined( 'WCD_VERIFY_SECRET' ) ) {
	define( 'WCD_VERIFY_SECRET', 'webchangedetector_verify_secret' );
}

// Option / UserMeta keys.
if ( ! defined( 'WCD_WP_OPTION_KEY_API_TOKEN' ) ) {
	define( 'WCD_WP_OPTION_KEY_API_TOKEN', 'webchangedetector_api_token' );
}

// Account email address.
if ( ! defined( 'WCD_WP_OPTION_KEY_ACCOUNT_EMAIL' ) ) {
	define( 'WCD_WP_OPTION_KEY_ACCOUNT_EMAIL', 'webchangedetector_account_email' );
}

if ( ! defined( 'WCD_WP_OPTION_KEY_UPGRADE_URL' ) ) {
	define( 'WCD_WP_OPTION_KEY_UPGRADE_URL', 'wcd_upgrade_url' );
}


// Steps in update change detection.
if ( ! defined( 'WCD_OPTION_UPDATE_STEP_KEY' ) ) {
	define( 'WCD_OPTION_UPDATE_STEP_KEY', 'webchangedetector_update_detection_step' );
}
if ( ! defined( 'WCD_OPTION_UPDATE_STEP_SETTINGS' ) ) {
	define( 'WCD_OPTION_UPDATE_STEP_SETTINGS', 'settings' );
}
if ( ! defined( 'WCD_OPTION_UPDATE_STEP_PRE' ) ) {
	define( 'WCD_OPTION_UPDATE_STEP_PRE', 'pre-update' );
}
if ( ! defined( 'WCD_OPTION_UPDATE_STEP_PRE_STARTED' ) ) {
	define( 'WCD_OPTION_UPDATE_STEP_PRE_STARTED', 'pre-update-started' );
}
if ( ! defined( 'WCD_OPTION_UPDATE_STEP_MAKE_UPDATES' ) ) {
	define( 'WCD_OPTION_UPDATE_STEP_MAKE_UPDATES', 'make-update' );
}
if ( ! defined( 'WCD_OPTION_UPDATE_STEP_POST' ) ) {
	define( 'WCD_OPTION_UPDATE_STEP_POST', 'post-update' );
}
if ( ! defined( 'WCD_OPTION_UPDATE_STEP_POST_STARTED' ) ) {
	define( 'WCD_OPTION_UPDATE_STEP_POST_STARTED', 'post-update-started' );
}
if ( ! defined( 'WCD_OPTION_UPDATE_STEP_CHANGE_DETECTION' ) ) {
	define( 'WCD_OPTION_UPDATE_STEP_CHANGE_DETECTION', 'change-detection' );
}

// WCD tabs.
if ( ! defined( 'WCD_TAB_DASHBOARD' ) ) {
	define( 'WCD_TAB_DASHBOARD', '/admin.php?page=webchangedetector-dashboard' );
}
if ( ! defined( 'WCD_TAB_UPDATE' ) ) {
	define( 'WCD_TAB_UPDATE', '/admin.php?page=webchangedetector-update-settings' );
}
if ( ! defined( 'WCD_TAB_AUTO' ) ) {
	define( 'WCD_TAB_AUTO', '/admin.php?page=webchangedetector-auto-settings' );
}
if ( ! defined( 'WCD_TAB_CHANGE_DETECTION' ) ) {
	define( 'WCD_TAB_CHANGE_DETECTION', '/admin.php?page=webchangedetector-change-detections' );
}
if ( ! defined( 'WCD_TAB_LOGS' ) ) {
	define( 'WCD_TAB_LOGS', '/admin.php?page=webchangedetector-logs' );
}
if ( ! defined( 'WCD_TAB_SETTINGS' ) ) {
	define( 'WCD_TAB_SETTINGS', '/admin.php?page=webchangedetector-settings' );
}

if ( ! defined( 'WCD_REQUEST_TIMEOUT' ) ) {
	define( 'WCD_REQUEST_TIMEOUT', 30 );
}
if ( ! defined( 'WCD_POLYLANG_PLUGIN_FILE' ) ) {
	define( 'WCD_POLYLANG_PLUGIN_FILE', 'polylang/polylang.php' );
}

if ( ! defined( 'WCD_WPML_PLUGIN_FILE' ) ) {
	define( 'WCD_WPML_PLUGIN_FILE', 'sitepress-multilingual-cms/sitepress.php' );
}



