<?php
/**
 * Data Synchronization Management for WebChange Detector
 *
 * This class handles all data synchronization functionality including post/term
 * synchronization, URL uploads, batch processing, and API communication for sync operations.
 *
 * @link       https://www.webchangedetector.com
 * @since      1.0.0
 *
 * @package    WebChangeDetector
 * @subpackage WebChangeDetector/admin
 */

namespace WebChangeDetector;

/**
 * Data synchronization management functionality.
 *
 * Defines all methods for synchronizing WordPress content with the WebChange Detector API,
 * including posts, terms, taxonomies, and batch URL uploads.
 *
 * @since      1.0.0
 * @package    WebChangeDetector
 * @subpackage WebChangeDetector/admin
 * @author     Mike Miler <mike@wp-mike.com>
 */
class WebChangeDetector_Admin_Sync {

	/**
	 * Reference to the main admin instance.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      WebChangeDetector_Admin    $admin    The admin instance.
	 */
	private $admin;

	/**
	 * Reference to the API manager instance.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      WebChangeDetector_API_Manager    $api_manager    The API manager instance.
	 */
	private $api_manager;

	/**
	 * Initialize the sync manager.
	 *
	 * @since    1.0.0
	 * @param    WebChangeDetector_Admin           $admin        The admin instance.
	 * @param    WebChangeDetector_API_Manager     $api_manager  The API manager instance.
	 */
	public function __construct( $admin, $api_manager ) {
		$this->admin       = $admin;
		$this->api_manager = $api_manager;
	}

	/**
	 * Sync posts after save hook.
	 *
	 * @since    1.0.0
	 * @return   bool  True on success.
	 */
	public function wcd_sync_post_after_save() {
		$this->sync_posts( true );
		return true;
	}

	/**
	 * Get posts by post type with WPML support.
	 *
	 * @since    1.0.0
	 * @param    string $posttype  The post type to retrieve.
	 * @return   array  Array of posts.
	 */
	public function get_posts( $posttype ) {
		$args           = array(
			'post_type'   => $posttype,
			'post_status' => array( 'publish', 'inherit' ),
			'numberposts' => -1,
			'order'       => 'ASC',
			'orderby'     => 'title',
		);
		$wpml_languages = $this->get_wpml_languages();

		if ( ! $wpml_languages ) {
			$posts = get_posts( $args );
		} else {
			$posts = array();
			foreach ( $wpml_languages['languages'] as $language ) {
				do_action( 'wpml_switch_language', $language['code'] );
				$posts = array_merge( $posts, get_posts( $args ) );
			}
			do_action( 'wpml_switch_language', $wpml_languages['current_language'] );
		}

		return $this->filter_unique_posts_by_id( $posts );
	}

	/**
	 * Filter duplicate posts by ID.
	 *
	 * @since    1.0.0
	 * @param    array $posts  Array of posts to filter.
	 * @return   array  Filtered array of unique posts.
	 */
	public function filter_unique_posts_by_id( $posts ) {
		$unique_posts = array();
		$post_ids     = array();

		foreach ( $posts as $post ) {
			// Don't need to send too much unnecessary data.
			unset( $post->post_content );
			if ( ! in_array( $post->ID, $post_ids, true ) ) {
				$post_ids[]     = $post->ID;
				$unique_posts[] = $post;
			}
		}

		return $unique_posts;
	}

	/**
	 * Filter duplicate terms by ID.
	 *
	 * @since    1.0.0
	 * @param    array $terms  Array of terms to filter.
	 * @return   array  Filtered array of unique terms.
	 */
	public function filter_unique_terms_by_id( $terms ) {
		$unique_terms = array();
		$term_ids     = array();

		foreach ( $terms as $term ) {
			if ( ! in_array( $term->term_id, $term_ids, true ) ) {
				$term_ids[]     = $term->term_id;
				$unique_terms[] = $term;
			}
		}

		return $unique_terms;
	}

	/**
	 * Get terms by taxonomy with WPML support.
	 *
	 * @since    1.0.0
	 * @param    string $taxonomy  The taxonomy to retrieve terms from.
	 * @return   array|int[]|string|string[]|WP_Error|WP_Term[]  Array of terms.
	 */
	public function get_terms( $taxonomy ) {
		$args = array(
			'number'        => '0',
			'taxonomy'      => $taxonomy,
			'hide_empty'    => false,
			'wpml_language' => 'de',
		);

		// Get terms for all languages if WPML is enabled.
		$wpml_languages = $this->get_wpml_languages();

		// If we don't have languages, we can return the terms.
		if ( ! $wpml_languages ) {
			$terms = get_terms( $args );
		} else {
			// With languages, we loop through them and return all of them.
			$terms = array();
			foreach ( $wpml_languages['languages'] as $language ) {
				do_action( 'wpml_switch_language', $language['code'] );
				$terms = array_merge( $terms, get_terms( $args ) );
			}
			do_action( 'wpml_switch_language', $wpml_languages['current_language'] );
		}

		return $this->filter_unique_terms_by_id( $terms );
	}

	/**
	 * Check if WPML is active and return all languages and the active one.
	 *
	 * @since    1.0.0
	 * @return   array|false  Array of language data or false if WPML not active.
	 */
	public function get_wpml_languages() {
		if ( ! class_exists( 'SitePress' ) ) {
			return false;
		}

		$languages        = apply_filters( 'wpml_active_languages', null );
		$current_language = apply_filters( 'wpml_current_language', null );

		return array_merge(
			array(
				'current_language' => $current_language,
				'languages'        => $languages,
			)
		);
	}

	/**
	 * Get all posts data in batches and upload to API.
	 *
	 * @since    1.0.0
	 * @param    array $post_types  Array of post types to retrieve.
	 * @return   void
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
					'post_type'      => $single_post_type,
					'post_status'    => 'publish',
					'posts_per_page' => $posts_per_batch,
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

	/**
	 * Get all terms data in batches and upload to API.
	 *
	 * @since    1.0.0
	 * @param    array $taxonomies  Array of taxonomies to retrieve terms from.
	 * @return   void
	 */
	public function get_all_terms_data( $taxonomies ) {
		// Array to store all terms data.
		$all_terms_data = array();

		if ( empty( $taxonomies ) ) {
			return;
		}

		foreach ( $taxonomies as $single_taxonomy ) {
			// Set the batch size for both retrieving and uploading.
			$offset           = 0;
			$terms_per_batch  = 1000;  // Number of terms to retrieve per query.

			do {
				\WebChangeDetector\WebChangeDetector_Admin_Utils::log_error( 'Getting next chunk of terms. Offset: ' . $offset );

				// Set up get_terms arguments.
				$args = array(
					'taxonomy'   => $single_taxonomy,
					'hide_empty' => false,
					'number'     => $terms_per_batch,
					'offset'     => $offset,
				);

				// Get terms for the current batch.
				$terms = get_terms( $args );

				// If no terms or error, break the loop.
				if ( empty( $terms ) || is_wp_error( $terms ) ) {
					break;
				}

				// Process each term in the current batch.
				foreach ( $terms as $term ) {
					$term_link = get_term_link( $term );

					// Skip if term link is an error.
					if ( is_wp_error( $term_link ) ) {
						continue;
					}

					// Get the taxonomy label.
					$taxonomy_object = get_taxonomy( $single_taxonomy );
					$taxonomy_label  = $taxonomy_object ? $taxonomy_object->labels->name : $single_taxonomy;

					// Add the data to the main array.
					$all_terms_data[ 'types%%' . $taxonomy_label ][] = array(
						'url'        => \WebChangeDetector\WebChangeDetector_Admin_Utils::remove_url_protocol( $term_link ),
						'html_title' => $term->name,
					);
				}

				// Increment the offset for the next batch.
				$offset += $terms_per_batch;
				\WebChangeDetector\WebChangeDetector_Admin_Utils::log_error( 'Sending Terms.' );

				// Call uploadUrls after every batch.
				$this->upload_urls_in_batches( $all_terms_data );

				// Clear the data array after each batch to free memory.
				$all_terms_data = array();

				// Get the count of the results.
				$results_count = count( $terms );
			} while ( $results_count === $terms_per_batch );
		}
	}

	/**
	 * Upload URLs in batches to the API.
	 *
	 * @since    1.0.0
	 * @param    array $upload_array  Array of URLs to upload.
	 * @return   void
	 */
	public function upload_urls_in_batches( $upload_array ) {
		if ( empty( $upload_array ) ) {
			return;
		}

		// Add to sync_urls array for batch processing.
		if ( ! isset( $this->admin->sync_urls ) ) {
			$this->admin->sync_urls = array();
		}

		$this->admin->sync_urls[] = $upload_array;

		\WebChangeDetector\WebChangeDetector_Admin_Utils::log_error( 'URLs added to sync batch: ' . wp_json_encode( $upload_array ) );
	}

	/**
	 * Daily sync posts cron job.
	 *
	 * @since    1.0.0
	 * @return   void
	 */
	public function daily_sync_posts_cron_job() {
		// Setting force to true to ensure it runs regardless of last sync time.
		$this->sync_posts();
	}

	/**
	 * Sync single post with API.
	 *
	 * @since    1.0.0
	 * @param    array $single_post  The sync array.
	 * @return   bool  True on success.
	 */
	public function sync_single_post( $single_post ) {
		if ( ! empty( $single_post ) ) {
			\WebChangeDetector\WebChangeDetector_Admin_Utils::log_error( 'Start single url sync' );
			$response_sync_urls      = \WebChangeDetector\WebChangeDetector_API_V2::sync_urls( $single_post );
			$response_start_url_sync = \WebChangeDetector\WebChangeDetector_API_V2::start_url_sync( false );
			
			return true;
		}
		
		return false;
	}

	/**
	 * Sync posts with API.
	 *
	 * @since    1.0.0
	 * @param    bool       $force_sync        Skip cache and force sync.
	 * @param    array|bool $website_details   The website details or false.
	 * @return   bool|string  Date of sync or false on failure.
	 */
	public function sync_posts( $force_sync = false, $website_details = false ) {
		$last_sync     = get_option( 'wcd_last_urls_sync' );
		$sync_interval = '+1 hour';

		// Skip sync if last sync is less than sync interval.
		if ( $last_sync && ! $force_sync && strtotime( $sync_interval, $last_sync ) >= date_i18n( 'U' ) ) {
			// Returning last sync datetime.
			return date_i18n( 'd.m.Y H:i', $last_sync );
		}

		\WebChangeDetector\WebChangeDetector_Admin_Utils::log_error( 'Starting Sync' );
		update_option( 'wcd_last_urls_sync', date_i18n( 'U' ) );

		// Check if we got website_details or if we use the ones from the class.
		$array = array(); // init.
		if ( ! $website_details ) {
			$website_details = $this->admin->website_details;
		}

		// We only sync the frontpage.
		if ( ! empty( $website_details['allowances']['only_frontpage'] ) ) {
			\WebChangeDetector\WebChangeDetector_Admin_Utils::log_error( "only frontpage: " . wp_json_encode( $website_details['allowances']['only_frontpage'] ) );
			$array['frontpage%%Frontpage'][] = array(
				'url'        => \WebChangeDetector\WebChangeDetector_Admin_Utils::get_domain_from_site_url(),
				'html_title' => get_bloginfo( 'name' ),
			);
			$this->upload_urls_in_batches( $array );
			return true;
		}

		// Initialize sync_urls if not already done
		if ( ! isset( $this->admin->sync_urls ) ) {
			$this->admin->sync_urls = array();
		}

		// Init sync urls if we don't have them yet.
		if ( ! empty( $website_details['sync_url_types'] ) ) {
			// Get all WP post_types.
			$post_types = get_post_types( array( 'public' => true ), 'objects' );
			$post_type_names = array();
			
			foreach ( $post_types as $post_type ) {
				$wp_post_type_slug = \WebChangeDetector\WebChangeDetector_Admin_Utils::get_post_type_slug( $post_type );

				// Get the right name for the request.
				foreach ( $website_details['sync_url_types'] as $sync_url_type ) {
					if ( $sync_url_type['post_type_slug'] === $wp_post_type_slug ) {
						// The 'get_posts' function needs 'name' instead of 'rest_base'.
						$post_type_names[] = $post_type->name;
					}
				}
			}

			if ( ! empty( $post_type_names ) ) {
				$this->get_all_posts_data( $post_type_names );
			}

			// Get all WP taxonomies.
			$taxonomies = get_taxonomies( array( 'public' => true ), 'objects' );
			$taxonomy_post_names = array();
			
			foreach ( $taxonomies as $taxonomy ) {
				// Depending on if we have 'rest_base' name we use this one or the 'name'.
				$wp_taxonomy_slug = \WebChangeDetector\WebChangeDetector_Admin_Utils::get_taxonomy_slug( $taxonomy );

				// Get the terms names.
				foreach ( $website_details['sync_url_types'] as $sync_url_type ) {
					if ( $sync_url_type['post_type_slug'] === $wp_taxonomy_slug ) {
						$taxonomy_post_names[] = $taxonomy->name;
					}
				}
			}

			if ( ! empty( $taxonomy_post_names ) ) {
				$this->get_all_terms_data( $taxonomy_post_names );
			}
		}

		$active_plugins = get_option( 'active_plugins' );

		// Check if frontpage is already in the sync settings.
		$frontpage_exists = array_filter(
			$website_details['sync_url_types'] ?? array(),
			function ( $item ) {
				return isset( $item['post_type_slug'] ) && 'frontpage' === $item['post_type_slug'];
			}
		);

		// If blog is set as home page.
		if ( ! get_option( 'page_on_front' ) ) {
			// WPML fix.
			if ( $active_plugins && in_array( WCD_WPML_PLUGIN_FILE, $active_plugins, true ) ) {
				$languages = icl_get_languages( 'skip_missing=0' ); // Get all active languages.

				if ( ! empty( $languages ) ) {
					// Store the current language to switch back later.
					$current_lang = apply_filters( 'wpml_current_language', null );
					foreach ( $languages as $lang_code => $lang_info ) {
						// Switch to each language.
						do_action( 'wpml_switch_language', $lang_code );

						// Store the title in the array with the language code as the key.
						$array['frontpage%%Frontpage'][] = array(
							'url'        => \WebChangeDetector\WebChangeDetector_Admin_Utils::remove_url_protocol( apply_filters( 'wpml_home_url', get_home_url(), $lang_code ) ),
							'html_title' => get_bloginfo( 'name' ),
						);
					}

					// Switch back to the original language.
					do_action( 'wpml_switch_language', $current_lang );
				}
			} elseif ( $active_plugins && in_array( WCD_POLYLANG_PLUGIN_FILE, $active_plugins, true ) ) {
				// Polylang fix.
				if ( isset( $GLOBALS['polylang'] ) ) {
					$languages = $GLOBALS['polylang']->model->get_languages_list();

					foreach ( $languages as $language ) {
						// Check if home_url is available in the language info.
						if ( ! empty( $language->home_url ) ) {
							$array['frontpage%%Frontpage'][] = array(
								'url'        => \WebChangeDetector\WebChangeDetector_Admin_Utils::remove_url_protocol( $language->home_url ),
								'html_title' => get_bloginfo( 'name' ),
							);
						}
					}
				}
			} else {
				$array['frontpage%%Frontpage'][] = array(
					'url'        => \WebChangeDetector\WebChangeDetector_Admin_Utils::remove_url_protocol( get_option( 'home' ) ),
					'html_title' => get_bloginfo( 'name' ),
				);
			}

			// Add frontpage if it's not yet in the sync_url_types array.
			if ( empty( $frontpage_exists ) ) {
				$website_details['sync_url_types'][] = array(
					'url_type_slug'  => 'types',
					'url_type_name'  => 'frontpage',
					'post_type_slug' => 'frontpage',
					'post_type_name' => 'Frontpage',
				);
				$this->admin->settings_handler->update_website_details( $website_details );
			}

			if ( ! empty( $array ) ) {
				$this->upload_urls_in_batches( $array );
			}
		} elseif ( $frontpage_exists ) {
			foreach ( $website_details['sync_url_types'] as $key => $sync_types_values ) {
				if ( 'frontpage' === $sync_types_values['post_type_slug'] ) {
					unset( $website_details['sync_url_types'][ $key ] );
				}
			}
			$this->admin->settings_handler->update_website_details( $website_details );
		}

		// Create uuid for sync urls.
		$collection_uuid = wp_generate_uuid4();

		// Sync urls.
		$response_sync_urls      = \WebChangeDetector\WebChangeDetector_API_V2::sync_urls( $this->admin->sync_urls, $collection_uuid );
		$response_start_url_sync = \WebChangeDetector\WebChangeDetector_API_V2::start_url_sync( true, $collection_uuid );
		\WebChangeDetector\WebChangeDetector_Admin_Utils::log_error( 'Response upload URLs: ' . $response_sync_urls );
		\WebChangeDetector\WebChangeDetector_Admin_Utils::log_error( 'Response Start URL sync: ' . $response_start_url_sync );

		return date_i18n( 'd/m/Y H:i' );
	}

	/**
	 * Save WordPress group settings asynchronously.
	 * Migrated from legacy class-wp-compare.php.
	 * 
	 * Creates groups and website immediately, then queues URL sync for background processing.
	 *
	 * @param array $postdata Form data containing group and website settings.
	 * @return array Result with job information or error.
	 */
	public static function save_wp_group_settings_async( $postdata ) {
		error_log( 'WCD: save_wp_group_settings_async called with data: ' . print_r( $postdata, true ) );
		
		// Validate domain
		if ( ! empty( $postdata['group_id'] ) && $postdata['group_id'] !== 0 ) {
			$domain = \WebChangeDetector\WebChangeDetector_Admin_Utils::get_domain_by_group_id( $postdata['group_id'] );
		} elseif ( ! empty( $postdata['domain'] ) ) {
			$domain = \WebChangeDetector\WebChangeDetector_Admin_Utils::check_url( $postdata['domain'] )[0];
			$domain = rtrim( $domain, '/' );
		} else {
			return false;
		}

		// Process post types
		$post_types = array();
		foreach ( $postdata as $key => $value ) {
			if ( strpos( $key, 'wp_api_' ) === 0 && $postdata[ $key ] ) {
				$value        = json_decode( str_replace( '\\"', '"', $value ), true );
				$post_types[] = $value;
			}
		}
		
		error_log( 'WCD: Processed post_types: ' . print_r( $post_types, true ) );

		try {
			// Step 1: Create groups immediately using existing V2 API
			$manual_group_data = array(
				'name' => $domain . ' Manual Checks',
				'monitoring' => false,
				'enabled' => true,
				'threshold' => floatval( $postdata['threshold'] ?? 0 ),
				'cms' => 'wordpress',
			);

			$monitoring_group_data = array(
				'name' => $domain . ' Monitoring',
				'monitoring' => true,
				'enabled' => true,
				'threshold' => floatval( $postdata['threshold'] ?? 0 ),
				'cms' => 'wordpress',

			);

			// Create groups via existing V2 API methods
			$manual_group = Wp_Compare_API_V2::create_group_v2( $manual_group_data );
			$monitoring_group = Wp_Compare_API_V2::create_group_v2( $monitoring_group_data );

			if ( ! $manual_group || ! $monitoring_group ) {
				return array( 'error' => 'Failed to create groups' );
			}

			// Step 2: Create website immediately using V2 API
			$website_data = array(
				'domain' => $domain,
				'manual_detection_group_id' => $manual_group['data']['id'],
				'auto_detection_group_id' => $monitoring_group['data']['id'],
				'sync_url_types' => self::format_sync_url_types( $post_types ),
				'auto_update_settings' => array(
					'auto_update_checks_enabled' => false,
					'auto_update_checks_from' => '13:37',
					'auto_update_checks_to' => '13:42',
					'auto_update_checks_monday' => true,
					'auto_update_checks_tuesday' => true,
					'auto_update_checks_wednesday' => true,
					'auto_update_checks_thursday' => true,
					'auto_update_checks_friday' => true,
					'auto_update_checks_saturday' => false,
					'auto_update_checks_sunday' => false,
					'auto_update_checks_emails' => wp_get_current_user()->user_email,
				),
			);
			error_log( 'WCD: website_data: ' . print_r( $website_data, true ) );
			$website = Wp_Compare_API_V2::create_website_v2( $website_data );
			error_log( 'WCD: website: ' . print_r( $website, true ) );
			if ( ! $website ) {
				return array( 'error' => 'Failed to create website' );
			}

			// Step 3: Queue only the URL sync job for background processing
			$sync_data = array(
				'domain' => $domain,
				'manual_detection_group_id' => $manual_group['data']['id'],
				'auto_detection_group_id' => $monitoring_group['data']['id'],
				'post_types' => $post_types,
				'website_id' => $website['data']['id'],
			);

			$job_id = self::queue_url_sync_job( $domain, $post_types, $sync_data );

			$result = array( 
				'job_queued' => true,
				'job_id' => $job_id,
				'status' => 'syncing',
				'domain' => $domain,
				'manual_group_id' => $manual_group['data']['id'],
				'monitoring_group_id' => $monitoring_group['data']['id'],
				'website_id' => $website['data']['id']
			);
			
			error_log( 'WCD: save_wp_group_settings_async returning: ' . print_r( $result, true ) );
			return $result;

		} catch ( \Exception $e ) {
			error_log( "WCD Async Setup Error: " . $e->getMessage() );
			return array( 'error' => $e->getMessage() );
		}
	}

	/**
	 * Format post types for sync_url_types field.
	 * Migrated from legacy class-wp-compare.php.
	 *
	 * @param array $post_types Post types from form.
	 * @return array Formatted sync URL types.
	 */
	private static function format_sync_url_types( $post_types ) {
		$sync_url_types = array();
		
		error_log( 'WCD: format_sync_url_types input: ' . print_r( $post_types, true ) );

		foreach ( $post_types as $post_type ) {
			$sync_url_types[] = array(
				'url_type_slug' => 'types',
				'url_type_name' => 'Post Types',
				'post_type_slug' => $post_type['post_type_slug'],
				'post_type_name' => $post_type['post_type_name'] ?? ucfirst( $post_type['post_type'] ),
			);
		}
		
		error_log( 'WCD: format_sync_url_types output: ' . print_r( $sync_url_types, true ) );

		return $sync_url_types;
	}

	/**
	 * Queue URL sync job for background processing.
	 * Migrated from legacy class-wp-compare.php.
	 *
	 * @param string $domain Website domain.
	 * @param array $post_types Selected post types to sync.
	 * @param array $website_data Website details from API.
	 * @return string Job ID.
	 */
	private static function queue_url_sync_job( $domain, $post_types, $website_data ) {
		global $wpdb;
		
		// Ensure table exists before trying to use it
		self::ensure_sync_jobs_table_exists();
		
		$job_id = uniqid( 'wcd_sync_' . time() . '_' );
		
		// Get current user's API token to use in background job
		$current_user_id = get_current_user_id();
		$selected_api_token = get_user_meta( $current_user_id, "wcd_active_api_token", true );
		if ( ! $selected_api_token ) {
			$selected_api_token = mm_api_token(); // Fallback to main account token
		}
		
		// Store complete website data, post types, and API token in the job record
		$job_data = array(
			'post_types' => $post_types,
			'website_data' => $website_data,
			'user_id' => $current_user_id,
			'api_token' => $selected_api_token
		);
		
		// Insert sync job record
		$wpdb->insert(
			$wpdb->prefix . 'wcd_sync_jobs',
			array(
				'job_id' => $job_id,
				'domain' => $domain,
				'manual_group_id' => $website_data['manual_detection_group_id'] ?? null,
				'monitoring_group_id' => $website_data['auto_detection_group_id'] ?? null,
				'post_types' => json_encode( $job_data ), // Store all data here
				'status' => 'queued',
				'progress' => 0
			),
			array( '%s', '%s', '%s', '%s', '%s', '%s', '%d' )
		);
		
		// Schedule WordPress cron job immediately 
		wp_schedule_single_event( time() + 5, 'wcd_process_url_sync', array( $job_id ) );
		
		return $job_id;
	}

	/**
	 * Ensure sync jobs table exists.
	 * Migrated from legacy class-wp-compare.php.
	 */
	private static function ensure_sync_jobs_table_exists() {
		// If we've already confirmed the table exists, skip check
		if ( get_option( 'wcd_sync_table_created', false ) ) {
			return;
		}

		// Skip check for non-admin users unless they're using the sync functionality
		if ( ! is_admin() && ! current_user_can( 'edit_posts' ) ) {
			return;
		}

		global $wpdb;
		$table_name = $wpdb->prefix . 'wcd_sync_jobs';
		
		// Check if table exists
		$table_exists = $wpdb->get_var( $wpdb->prepare(
			"SHOW TABLES LIKE %s",
			$table_name
		) );

		if ( $table_exists !== $table_name ) {
			self::create_sync_jobs_table();
		}

		// Set option to indicate table exists - no need to check again
		update_option( 'wcd_sync_table_created', true );
	}

	/**
	 * Create sync jobs table for background URL synchronization.
	 * Migrated from legacy class-wp-compare.php.
	 */
	private static function create_sync_jobs_table() {
		global $wpdb;
		
		$table_name = $wpdb->prefix . 'wcd_sync_jobs';
		$charset_collate = $wpdb->get_charset_collate();
		
		$sql = "CREATE TABLE $table_name (
			id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			job_id varchar(50) NOT NULL UNIQUE,
			domain varchar(255) NOT NULL,
			manual_group_id varchar(50) DEFAULT NULL,
			monitoring_group_id varchar(50) DEFAULT NULL,
			post_types longtext,
			status enum('queued','processing','completed','failed') DEFAULT 'queued',
				progress int(3) DEFAULT 0,
				total_urls int(10) DEFAULT 0,
				processed_urls int(10) DEFAULT 0,
				error_message text,
				created_at timestamp DEFAULT CURRENT_TIMESTAMP,
				updated_at timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
				PRIMARY KEY (id),
				KEY idx_job_id (job_id),
				KEY idx_status (status),
				KEY idx_created_at (created_at)
			) $charset_collate;";
		
		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		dbDelta( $sql );
		
		// Log table creation for debugging
		error_log( 'WCD: Sync jobs table created successfully' );
		
		// Set option to indicate table was created successfully
		update_option( 'wcd_sync_table_created', true );
	}

	/**
	 * Process URL sync job for background processing.
	 *
	 * @param string $job_id Job ID to process.
	 * @return void
	 */
	public static function process_url_sync_job( $job_id ) {
		global $wpdb;
		
		// Get job details
		$job = $wpdb->get_row( $wpdb->prepare(
			"SELECT * FROM {$wpdb->prefix}wcd_sync_jobs WHERE job_id = %s",
			$job_id
		), ARRAY_A );
		
		if ( ! $job ) {
			error_log( "WCD Sync Job not found: {$job_id}" );
			return;
		}
		
		try {
			// Update job status to processing
			self::update_sync_job_status( $job_id, 'processing', 10, 'Starting URL sync...' );
			
			// Increase execution limits
			if ( ! wp_doing_ajax() ) {
				set_time_limit( 600 ); // 10 minutes
				ini_set( 'memory_limit', '1024M' );
			}
			
			// Decode job data
			$job_data = json_decode( $job['post_types'], true );
			$post_types = $job_data['post_types'] ?? array();
			$website_data = $job_data['website_data'] ?? array();
			$api_token = $job_data['api_token'] ?? mm_api_token(); // Use stored token or fallback
			
			// Groups and website are already created - start URL processing
			self::update_sync_job_status( $job_id, 'processing', 20, 'Fetching URLs from WordPress...' );
			
			// Use WordPress handler directly for URL fetching
			$urls = \WebChangeDetector\WebChangeDetector_Admin_WordPress::get_wp_urls( $job['domain'], $post_types );
			
			if ( ! $urls || ! count( $urls ) ) {
				self::update_sync_job_status( $job_id, 'failed', 0, 'No URLs found' );
				return;
			}
			
			error_log( 'WCD: urls: ' . print_r( $urls, true ) );
			// Flatten the chunked URLs array for API compatibility
			$total_url_count = 0;
			foreach ( $urls as $chunk ) {
				foreach ( $chunk as $url_type_category => $url_list ) {
					$total_url_count += count( $url_list );
				}
			}
			
			$total_url_count = count( $urls );
			
			self::update_sync_job_status( $job_id, 'processing', 40, 'Synchronizing URLs...', $total_url_count );
			
			// Use the exact same sync process as the original function with flattened URLs
			// Pass the API token to ensure authentication works in background
			error_log( 'WCD: flattened_urls: ' . print_r( $urls, true ) );
			self::sync_urls_with_token( $urls, $job['domain'], $api_token );
			self::update_sync_job_status( $job_id, 'processing', 80, 'Starting final sync...' );
			self::start_url_sync_with_token( $job['domain'], $api_token, true );
			
			// Complete
			self::update_sync_job_status( $job_id, 'completed', 100, 'Sync completed successfully!' );
			
			// Cleanup old jobs
			self::cleanup_old_sync_jobs();
			
		} catch ( Exception $e ) {
			error_log( "WCD Sync Job failed: {$job_id} - " . $e->getMessage() );
			self::update_sync_job_status( $job_id, 'failed', 0, $e->getMessage() );
			self::update_website_sync_status( $job['domain'], 'failed' );
		}
	}

	/**
	 * Sync URLs with specific API token for background jobs.
	 *
	 * @param array $urls URLs to sync.
	 * @param string $domain Website domain.
	 * @param string $api_token API token to use.
	 * @return mixed
	 */
	private static function sync_urls_with_token( $urls, $domain, $api_token ) {
		return Wp_Compare_API_V2::sync_urls_with_token( $urls, $domain, $api_token );
	}

	/**
	 * Start URL sync with specific API token for background jobs.
	 *
	 * @param string $domain Website domain.
	 * @param string $api_token API token to use.
	 * @param bool $delete_missing_urls Delete missing URLs.
	 * @return mixed
	 */
	private static function start_url_sync_with_token( $domain, $api_token, $delete_missing_urls = true ) {
		return Wp_Compare_API_V2::start_url_sync_with_token( $domain, $api_token, $delete_missing_urls );
	}

	/**
	 * Update sync job status and progress.
	 *
	 * @param string $job_id Job ID.
	 * @param string $status Job status.
	 * @param int|null $progress Progress percentage.
	 * @param string $status_message Status message.
	 * @param int|null $total_urls Total URLs found.
	 * @return void
	 */
	private static function update_sync_job_status( $job_id, $status, $progress = null, $status_message = '', $total_urls = null ) {
		global $wpdb;
		
		$data = array( 'status' => $status );
		$format = array( '%s' );
		
		if ( $progress !== null ) {
			$data['progress'] = $progress;
			$format[] = '%d';
		}
		
		if ( $status_message ) {
			$data['error_message'] = $status_message; // Reuse error_message field for status messages
			$format[] = '%s';
		}
		
		if ( $total_urls !== null ) {
			$data['total_urls'] = $total_urls;
			$format[] = '%d';
		}
		
		$wpdb->update(
			$wpdb->prefix . 'wcd_sync_jobs',
			$data,
			array( 'job_id' => $job_id ),
			$format,
			array( '%s' )
		);
		
		// Log progress for debugging
		error_log( "WCD Job {$job_id}: {$status} - {$progress}% - {$status_message}" );
	}

	/**
	 * Update website sync status.
	 *
	 * @param string $domain Website domain.
	 * @param string $status Sync status.
	 * @return void
	 */
	private static function update_website_sync_status( $domain, $status ) {
		$website_details = \WebChangeDetector\WebChangeDetector_Admin_Utils::get_website_details_by_domain( $domain )[0];
		$website_details['sync_status'] = $status;
		
		$args = array( 'action' => 'save_user_website' );
		$args = array_merge( $args, $website_details );
		mm_api( $args );
	}

	/**
	 * Cleanup old sync jobs (older than 24 hours).
	 *
	 * @return void
	 */
	private static function cleanup_old_sync_jobs() {
		global $wpdb;
		
		$wpdb->query( $wpdb->prepare(
			"DELETE FROM {$wpdb->prefix}wcd_sync_jobs WHERE created_at < %s",
			date( 'Y-m-d H:i:s', strtotime( '-24 hours' ) )
		) );
	}
}
