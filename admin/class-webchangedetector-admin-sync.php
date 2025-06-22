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
				$this->admin->update_website_details( $website_details );
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
			$this->admin->update_website_details( $website_details );
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
}
