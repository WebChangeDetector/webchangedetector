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
	 * Reference to the WordPress handler instance.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      WebChangeDetector_Admin_WordPress    $wordpress_handler    The WordPress handler instance.
	 */
	private $wordpress_handler;

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
		$this->wordpress_handler = $admin->wordpress_handler;
	}

	/**
	 * Sync posts after save hook.
	 *
	 * @since    1.0.0
	 * @return   bool  True on success.
	 */
	public function wcd_sync_post_after_save() {
		$this->wordpress_handler->sync_posts( true );
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
		$this->wordpress_handler->sync_posts();
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
}