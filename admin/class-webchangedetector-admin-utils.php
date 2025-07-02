<?php
/**
 * WebChange Detector Admin Utilities
 *
 * Handles utility functions and helper methods for the WebChange Detector plugin.
 * Follows WordPress coding standards and Model-View-Presenter pattern.
 *
 * @link       https://www.webchangedetector.com
 * @since      1.0.0
 *
 * @package    WebChangeDetector
 * @subpackage WebChangeDetector/admin
 */

namespace WebChangeDetector;

/**
 * WebChange Detector Admin Utilities Class
 *
 * This class contains utility methods and helper functions used throughout
 * the WebChange Detector plugin admin interface.
 *
 * @package    WebChangeDetector
 * @subpackage WebChangeDetector/admin
 * @author     WebChange Detector <support@webchangedetector.com>
 */
class WebChangeDetector_Admin_Utils {

	/**
	 * Remove URL protocol from a given URL.
	 *
	 * Removes http:// or https:// protocol from URLs for display purposes
	 * or when protocol-agnostic URLs are needed.
	 *
	 * @since 1.0.0
	 * @param string $url The URL to process.
	 * @return string The URL without protocol.
	 */
	public static function remove_url_protocol( $url ) {
		if ( empty( $url ) ) {
			return '';
		}

		// Remove http:// or https:// protocol
		$url = preg_replace( '/^https?:\/\//', '', $url );

		return $url;
	}

    public static function dd( $data ) {
        echo '<pre>';
        print_r( $data );
        echo '</pre>';
        die();
    }

	/**
	 * Check if a string is valid JSON.
	 *
	 * Validates whether a given string contains valid JSON data.
	 * Uses WordPress coding standards for validation.
	 *
	 * @since 1.0.0
	 * @param string $string The string to validate.
	 * @return bool True if valid JSON, false otherwise.
	 */
	public static function is_json( $string ) {
		if ( empty( $string ) || ! is_string( $string ) ) {
			return false;
		}

		// Attempt to decode JSON
		json_decode( $string );

		// Check if JSON decoding was successful
		return json_last_error() === JSON_ERROR_NONE;
	}

	/**
	 * Get device icon HTML for display.
	 *
	 * Returns appropriate icon HTML for desktop or mobile devices
	 * following WordPress admin interface standards.
	 *
	 * @since 1.0.0
	 * @param string $device The device type ('desktop' or 'mobile').
	 * @return string HTML icon element.
	 */
	 public static function get_device_icon( $icon, $css_class = '' ) {
		
		$output = '';
		if ( 'thumbnail' === $icon ) {
			$output = '<span class="dashicons dashicons-camera-alt"></span>';
		}
		if ( 'desktop' === $icon ) {
			$output = '<span class="group_icon ' . $css_class . ' dashicons dashicons-laptop"></span>';
		}
		if ( 'mobile' === $icon ) {
			$output = '<span class="group_icon ' . $css_class . ' dashicons dashicons-smartphone"></span>';
		}
		if ( 'page' === $icon ) {
			$output = '<span class="group_icon ' . $css_class . ' dashicons dashicons-media-default"></span>';
		}
		if ( 'change-detections' === $icon ) {
			$output = '<span class="group_icon ' . $css_class . ' dashicons dashicons-welcome-view-site"></span>';
		}
		if ( 'dashboard' === $icon ) {
			$output = '<span class="group_icon ' . $css_class . ' dashicons dashicons-admin-home"></span>';
		}
		if ( 'logs' === $icon ) {
			$output = '<span class="group_icon ' . $css_class . ' dashicons dashicons-menu-alt"></span>';
		}
		if ( 'settings' === $icon ) {
			$output = '<span class="group_icon ' . $css_class . ' dashicons dashicons-admin-generic"></span>';
		}
		if ( 'website-settings' === $icon ) {
			$output = '<span class="group_icon ' . $css_class . ' dashicons dashicons-welcome-widgets-menus"></span>';
		}
		if ( 'help' === $icon ) {
			$output = '<span class="group_icon ' . $css_class . ' dashicons dashicons-editor-help"></span>';
		}
		if ( 'auto-group' === $icon ) {
			$output = '<span class="group_icon ' . $css_class . ' dashicons dashicons-clock"></span>';
		}
		if ( 'update-group' === $icon ) {
			$output = '<span class="group_icon ' . $css_class . ' dashicons dashicons-admin-page"></span>';
		}
		if ( 'auto-update-group' === $icon ) {
			$output = '<span class="group_icon ' . $css_class . ' dashicons dashicons-update"></span>';
		}
		if ( 'trash' === $icon ) {
			$output = '<span class="group_icon ' . $css_class . ' dashicons dashicons-trash"></span>';
		}
		if ( 'check' === $icon ) {
			$output = '<span class="group_icon ' . $css_class . ' dashicons dashicons-yes-alt"></span>';
		}
		if ( 'fail' === $icon ) {
			$output = '<span class="group_icon ' . $css_class . ' dashicons dashicons-dismiss"></span>';
		}
		if ( 'warning' === $icon ) {
			$output = '<span class="group_icon ' . $css_class . ' dashicons dashicons-warning"></span>';
		}
		if ( 'upgrade' === $icon ) {
			$output = '<span class="group_icon ' . $css_class . ' dashicons dashicons-cart"></span>';
		}

		echo wp_kses( $output, array( 'span' => array( 'class' => array() ) ) );
    }

	/**
	 * Get user-friendly comparison status name.
	 *
	 * Converts internal comparison status codes to user-readable names
	 * with proper internationalization support.
	 *
	 * @since 1.0.0
	 * @param string $status The comparison status code.
	 * @return string User-friendly status name.
	 */
	public static function get_comparison_status_name( $status ) {
		$status_names = array(
			'new'            => __( 'New', 'webchangedetector' ),
			'ok'             => __( 'OK', 'webchangedetector' ),
			'to_fix'         => __( 'To Fix', 'webchangedetector' ),
			'false_positive' => __( 'False Positive', 'webchangedetector' ),
		);

		return isset( $status_names[ $status ] ) ? $status_names[ $status ] : ucfirst( $status );
	}

	/**
	 * Log error messages for debugging.
	 *
	 * Logs error messages using WordPress debugging standards.
	 * Only logs when WP_DEBUG is enabled.
	 *
	 * @since 1.0.0
	 * @param string $message The error message to log.
	 * @param string $context Optional context for the error.
	 */
	public static function log_error( $message, $context = '' ) {
		if ( ! defined( 'WP_DEBUG' ) || ! WP_DEBUG ) {
			return;
		}

		$log_message = '[WebChangeDetector] ' . $message;
		if ( ! empty( $context ) ) {
			$log_message .= ' [Context: ' . $context . ']';
		}

		// Use WordPress error logging if available
		if ( function_exists( 'error_log' ) ) {
			error_log( $log_message );
		}
	}

	/**
	 * Extract domain from site URL.
	 *
	 * Extracts the domain name from a complete site URL,
	 * removing protocol, www, and path components.
	 *
	 * @since 1.0.0
	 * @param string $site_url The complete site URL.
	 * @return string The extracted domain name.
	 */
	public static function get_domain_from_site_url( $site_url = '' ) {
		if ( empty( $site_url ) ) {
			$site_url = get_site_url();
		}

		// Parse URL components
		$parsed_url = wp_parse_url( $site_url );
		
		if ( ! isset( $parsed_url['host'] ) ) {
			return '';
		}

		$domain = $parsed_url['host'];

		// Remove www prefix if present
		$domain = preg_replace( '/^www\./', '', $domain );

		return $domain;
	}

	/**
	 * Get post type slug.
	 *
	 * Retrieves the slug for a given post type object or name.
	 *
	 * @since 1.0.0
	 * @param string|WP_Post_Type $post_type Post type object or name.
	 * @return string Post type slug.
	 */
	public static function get_post_type_slug( $post_type ) {
		if ( is_object( $post_type ) && isset( $post_type->name ) ) {
			return $post_type->name;
		}

		if ( is_string( $post_type ) ) {
			return $post_type;
		}

		return '';
	}

	/**
	 * Get post type display name.
	 *
	 * Retrieves the human-readable name for a given post type.
	 *
	 * @since 1.0.0
	 * @param string|WP_Post_Type $post_type Post type object or name.
	 * @return string Post type display name.
	 */
	public static function get_post_type_name( $post_type ) {
		if ( is_object( $post_type ) && isset( $post_type->label ) ) {
			return $post_type->label;
		}

		if ( is_string( $post_type ) ) {
			$post_type_object = get_post_type_object( $post_type );
			if ( $post_type_object && isset( $post_type_object->label ) ) {
				return $post_type_object->label;
			}
		}

		return ucfirst( $post_type );
	}

	/**
	 * Get taxonomy slug.
	 *
	 * Retrieves the slug for a given taxonomy object or name.
	 *
	 * @since 1.0.0
	 * @param string|WP_Taxonomy $taxonomy Taxonomy object or name.
	 * @return string Taxonomy slug.
	 */
	public static function get_taxonomy_slug( $taxonomy ) {
		if ( is_object( $taxonomy ) && isset( $taxonomy->name ) ) {
			return $taxonomy->name;
		}

		if ( is_string( $taxonomy ) ) {
			return $taxonomy;
		}

		return '';
	}

	/**
	 * Get taxonomy display name.
	 *
	 * Retrieves the human-readable name for a given taxonomy.
	 *
	 * @since 1.0.0
	 * @param string|WP_Taxonomy $taxonomy Taxonomy object or name.
	 * @return string Taxonomy display name.
	 */
	public static function get_taxonomy_name( $taxonomy ) {
		if ( is_object( $taxonomy ) && isset( $taxonomy->label ) ) {
			return $taxonomy->label;
		}

		if ( is_string( $taxonomy ) ) {
			$taxonomy_object = get_taxonomy( $taxonomy );
			if ( $taxonomy_object && isset( $taxonomy_object->label ) ) {
				return $taxonomy_object->label;
			}
		}

		return ucfirst( $taxonomy );
	}

	/**
	 * Sanitize and validate comparison status.
	 *
	 * Ensures comparison status is valid and safe to use.
	 *
	 * @since 1.0.0
	 * @param string $status The status to validate.
	 * @return string Valid status or empty string if invalid.
	 */
	public static function sanitize_comparison_status( $status ) {
		$valid_statuses = array( 'new', 'ok', 'to_fix', 'false_positive' );
		
		$status = sanitize_text_field( $status );
		
		return in_array( $status, $valid_statuses, true ) ? $status : '';
	}

	/**
	 * Format file size for display.
	 *
	 * Converts bytes to human-readable format following WordPress standards.
	 *
	 * @since 1.0.0
	 * @param int $bytes File size in bytes.
	 * @param int $precision Number of decimal places.
	 * @return string Formatted file size.
	 */
	public static function format_file_size( $bytes, $precision = 2 ) {
		if ( $bytes <= 0 ) {
			return '0 B';
		}

		$units = array( 'B', 'KB', 'MB', 'GB', 'TB' );
		$base  = log( $bytes, 1024 );
		$index = floor( $base );

		if ( $index >= count( $units ) ) {
			$index = count( $units ) - 1;
		}

		$size = round( pow( 1024, $base - $index ), $precision );

		return $size . ' ' . $units[ $index ];
	}

	/**
	 * Check if current user has required capabilities.
	 *
	 * Validates user permissions for WebChangeDetector operations.
	 *
	 * @since 1.0.0
	 * @param string $capability Required capability.
	 * @return bool True if user has capability, false otherwise.
	 */
	public static function current_user_can_manage_webchangedetector( $capability = 'manage_options' ) {
		return current_user_can( $capability );
	}

	/**
	 * Generate nonce for WebChangeDetector actions.
	 *
	 * Creates secure nonces following WordPress security standards.
	 *
	 * @since 1.0.0
	 * @param string $action The action for which to create nonce.
	 * @return string Generated nonce.
	 */
	public static function create_nonce( $action ) {
		return wp_create_nonce( 'webchangedetector_' . $action );
	}

	/**
	 * Verify nonce for WebChangeDetector actions.
	 *
	 * Validates nonces following WordPress security standards.
	 *
	 * @since 1.0.0
	 * @param string $nonce  The nonce to verify.
	 * @param string $action The action for which to verify nonce.
	 * @return bool True if nonce is valid, false otherwise.
	 */
	public static function verify_nonce( $nonce, $action ) {
		return wp_verify_nonce( $nonce, 'webchangedetector_' . $action );
	}

	/**
	 * Get parameters from URL.
	 *
	 * Extracts URL parameters and returns them as an array.
	 * Handles various URL formats following WordPress standards.
	 *
	 * @since 1.0.0
	 * @param string $url The URL to extract parameters from.
	 * @return array Array of URL parameters.
	 */
	public static function get_params_of_url( $url ) {
		if ( empty( $url ) ) {
			return false;
		}

		$url_components = wp_parse_url( $url );
		if ( ! isset( $url_components['query'] ) ) {
			return array();
		}

		parse_str( $url_components['query'], $params );
		return $params;
	}

	/**
	 * Get time ago string from date.
	 *
	 * Converts a date into a human-readable "time ago" format.
	 * Returns strings like "2 minutes ago" or "1 hour ago".
	 *
	 * @since 1.0.0
	 * @param string $date The date to convert.
	 * @return string Human-readable time ago string.
	 */
	public static function timeAgo( $date ) {
		$timestamp = strtotime( $date );

		$strTime = array( 'second', 'minute', 'hour', 'day', 'month', 'year' );
		$length  = array( '60', '60', '24', '30', '12', '10' );

		$currentTime = time();
		if ( $currentTime >= $timestamp ) {
			$diff = time() - $timestamp;
			for ( $i = 0; $diff >= $length[ $i ] && $i < count( $length ) - 1; $i++ ) {
				$diff = $diff / $length[ $i ];
			}

			$diff = round( $diff );
			return $diff . ' ' . $strTime[ $i ] . '(s) ago ';
		}
		return '';
	}

	/**
	 * Log messages to file.
	 *
	 * Writes log messages to the plugin's log file for debugging purposes.
	 * Handles arrays and objects by converting them to JSON.
	 *
	 * @since 1.0.0
	 * @param mixed $log The data to log.
	 * @return void
	 */
	public static function log( $log ) {
		if ( is_array( $log ) || is_object( $log ) ) {
			$log = json_encode( $log );
		}
		$plugin_dir = plugin_dir_path( dirname( __FILE__ ) );
		file_put_contents( $plugin_dir . '/logs.txt', $log . PHP_EOL, FILE_APPEND );
	}

	/**
	 * Convert textarea field to array.
	 *
	 * Converts a multi-line text input (from textarea) into an array of URLs.
	 * Handles various separators and cleans up whitespace.
	 *
	 * @since 1.0.0
	 * @param string $urls Multi-line text input with URLs.
	 * @return array Array of clean URLs.
	 */
	public static function textfield_to_array( $urls ) {
		$urls    = trim( $urls );
		$urls    = str_replace( array( ' ', '\\' ), "\n", $urls );
		$urls    = str_replace( "\r", '', $urls );
		$urls    = explode( "\n", $urls );
		$url_arr = array();
		foreach ( $urls as $url ) {
			if ( empty( $url ) ) {
				continue;
			}
			$url_arr[] = trim( $url );
		}
		return $url_arr;
	}

	/**
	 * Check if URLs are valid and accessible.
	 *
	 * Validates an array of URLs to ensure they are accessible.
	 * Returns only the URLs that are valid and reachable.
	 *
	 * @since 1.0.0
	 * @param string|array $urls URLs to check (can be string or array).
	 * @return array Array of valid URLs.
	 */
	public static function check_url( $urls ) {
		// Convert to array if string input.
		if ( is_string( $urls ) ) {
			$urls = self::textfield_to_array( $urls );
		}

		$return_urls = array();

		foreach ( $urls as $url ) {
			// Use WordPress function to check if URL exists.
			$response = wp_remote_head( $url, array( 'timeout' => 10 ) );
			
			if ( ! is_wp_error( $response ) ) {
				$response_code = wp_remote_retrieve_response_code( $response );
				
				// Accept 200 (OK) and 3xx (redirects) as valid.
				if ( $response_code >= 200 && $response_code < 400 ) {
					$return_urls[] = $url;
				}
			}
		}

		return $return_urls;
	}

	/**
	 * Get title from URL.
	 *
	 * Fetches the HTML title tag content from a given URL.
	 * Returns false if the title cannot be retrieved.
	 *
	 * @since 1.0.0
	 * @param string $url The URL to get title from.
	 * @return string|false The page title or false on failure.
	 */
	public static function get_title( $url ) {
		$response = wp_remote_get( $url, array( 'timeout' => 10 ) );
		
		if ( is_wp_error( $response ) ) {
			return false;
		}

		$body = wp_remote_retrieve_body( $response );
		
		if ( strlen( $body ) > 0 ) {
			$body = trim( preg_replace( '/\s+/', ' ', $body ) ); // supports line breaks inside <title>.
			preg_match( '/\<title\>(.*)\<\/title\>/i', $body, $title ); // ignore case.
			
			if ( isset( $title[1] ) ) {
				return $title[1];
			}
		}
		return false;
	}

	/**
	 * Get loading icon URL.
	 *
	 * Returns the URL to the main loading icon image.
	 *
	 * @since 1.0.0
	 * @return string URL to loading icon.
	 */
	public static function get_loading_icon() {
		return plugin_dir_url( __FILE__ ) . '../public/img/loader.gif';
	}

	/**
	 * Get small loading icon URL.
	 *
	 * Returns the URL to the small loading icon image.
	 *
	 * @since 1.0.0
	 * @return string URL to small loading icon.
	 */
	public static function get_small_loading_icon() {
		return plugin_dir_url( __FILE__ ) . '../public/img/loading.gif';
	}

	/**
	 * Generate slider HTML for device toggles.
	 *
	 * Creates HTML for toggle switches used in the WordPress admin bar
	 * for controlling monitoring settings on individual pages.
	 *
	 * @since 1.0.0
	 * @param string      $type       'monitoring' or 'manual'.
	 * @param string      $device     'desktop' or 'mobile'.
	 * @param bool        $is_enabled Current state.
	 * @param string      $url        Current page URL.
	 * @param string|null $url_id     WCD URL ID if known.
	 * @param string|null $group_id   WCD Group ID.
	 * @return string HTML for the slider.
	 */
	public static function generate_slider_html( $type, $device, $is_enabled, $url, $url_id, $group_id ) {
		$checked = $is_enabled ? 'checked' : '';
		$label   = ucfirst( $device );
		$id      = sprintf( 
			'wcd-slider-%s-%s-%s', 
			$type, 
			$device, 
			str_replace( array( '.', ':', '/' ), '-', $url_id ?? wp_generate_password( 5, false ) ) 
		);

		// Data attributes for JavaScript.
		$data_attrs = sprintf(
			'data-type="%s" data-device="%s" data-url="%s" data-url-id="%s" data-group-id="%s"',
			esc_attr( $type ),
			esc_attr( $device ),
			esc_attr( $url ),
			esc_attr( $url_id ?? '' ),
			esc_attr( $group_id ?? '' )
		);

		// Generate switch HTML structure.
		$html = sprintf(
			'<div class="wcd-admin-bar-slider">' .
			'<label for="%s" class="wcd-slider-label">%s:</label>' .
			'<label class="wcd-switch">' .
			'<input type="checkbox" id="%s" class="wcd-admin-bar-toggle" %s %s> ' .
			'<span class="wcd-modern-slider"></span>' .
			'</label>' .
			'</div>',
			esc_attr( $id ),
			esc_html( $label ),
			esc_attr( $id ),
			$checked,
			$data_attrs
		);

		return $html;
	}

	/**
	 * Take thumbnail for a URL.
	 *
	 * Initiates thumbnail generation for a specific URL via API.
	 *
	 * @since    1.0.0
	 * @param    array $postdata The post data containing URL ID.
	 * @return   mixed The API response.
	 */
	public static function take_thumbnail( $postdata ) {
		$url_id = $postdata['url_id'];

		$args = array(
			'action' => 'take_thumbnail',
			'url_id' => $url_id,
		);
		return mm_api( $args );
	}

	/**
	 * Get loading icon URL path.
	 *
	 * Returns the URL path to the loading icon image.
	 *
	 * @since    1.0.0
	 * @return   string The URL path to the loading icon.
	 */
	public static function get_loading_icon_url_path() {
		return plugin_dir_url( __FILE__ ) . '../public/img/loading.gif';
	}

	/**
	 * Get loading transparent background icon URL path.
	 *
	 * Returns the URL path to the loading icon with transparent background.
	 *
	 * @since    1.0.0
	 * @return   string The URL path to the transparent loading icon.
	 */
	public static function get_loading_transparent_bg_icon_url_path() {
		return plugin_dir_url( __FILE__ ) . '../public/img/loading-transparent-bg.gif';
	}

	/**
	 * Get user URLs from API.
	 *
	 * Retrieves all URLs associated with the current user account.
	 *
	 * @since    1.0.0
	 * @return   array|false Array of user URLs or false on failure.
	 */
	public static function get_user_urls() {
		$args = array(
			'action' => 'get_user_urls',
			// this is technically correct but cms_resource_id is ignored anyway for this route
			// 'cms_resource_id' => null,
		);

		return mm_api( $args );
	}

	/**
	 * Render user URLs view table.
	 *
	 * Displays a table of user URLs with group assignments and management actions.
	 *
	 * @since    1.0.0
	 * @param    array $user_urls       Array of user URLs.
	 * @param    array $groups_and_urls Array of groups and their URLs.
	 * @return   void                   Outputs HTML table.
	 */
	public static function get_user_urls_view( $user_urls, $groups_and_urls ) {
		?>
		<div class="responsive-table">
			<table class="toggle">
				<tr>
					<th width="70%">URL</th>
					<th width="30%">Assigned in groups</th>
					<th>Edit</th>
					<th>Delete</th>
				</tr>
			<?php

			// Go through all urls of client
			if ( count( $user_urls ) === 0 ) {
				?>
				<td colspan="4" style="text-align: center;">
					<strong>There are no URLs yet. You can manage URLs here after adding to Update- or Monitoring groups.</strong>
				</td>
				<?php
			} else {
				foreach ( $user_urls as $user_url ) {
					$assigned_in_group = array();

					// Get the groups this url is assigned to
					if ( is_iterable( $groups_and_urls ) ) {
						foreach ( $groups_and_urls as $group_and_urls ) {
							if ( count( $group_and_urls['urls'] ) > 0 ) {
								foreach ( $group_and_urls['urls'] as $group_url ) {
									if ( $group_url['id'] == $user_url['id'] ) {
										$assigned_in_group[] = get_group_icon( $group_and_urls ) . $group_and_urls['name'];
									}
								}
							}
						}
					}
					?>
					<tr id="url-row-<?php echo $user_url['id']; ?>">
						<td><strong><?php echo $user_url['html_title']; ?></strong><br><?php echo $user_url['url']; ?></td>
						<td><?php echo ! empty( $assigned_in_group ) ? implode( '<br>', $assigned_in_group ) : ''; ?></td>
						<td>
							<a onclick="showAddUrlPopup(<?php echo $user_url['id']; ?>, '<?php echo $user_url['url']; ?>', '<?php echo $user_url['html_title']; ?>')"
								class="ajax_save_url "
								data-url_id="<?php echo $user_url['id']; ?>"
								data-url="<?php echo $user_url['url']; ?>">
								<?php echo get_device_icon( 'edit', 'row-icon' ); ?>
							</a>
						</td>
						<td>
							<a class="ajax_delete_url"
								data-url_id="<?php echo $user_url['id']; ?>"
								data-url="<?php echo $user_url['url']; ?>">
								<?php echo get_device_icon( 'remove', 'row-icon' ); ?>
							</a>
						</td>
					</tr>
					<?php
				}
			}
			?>
			</table>
		</div>
		<?php
	}

	/**
	 * Get URL HTML content via API.
	 *
	 * Retrieves the HTML content of a specific URL.
	 *
	 * @since    1.0.0
	 * @param    array $postdata POST data containing url_id.
	 * @return   void           Outputs HTML content or error message.
	 */
	public static function get_url_html( $postdata ) {
		$url_id = $postdata['url_id'];

		$args = array(
			'action' => 'get_url_html',
			'url_id' => $url_id,
		);
		echo mm_message( mm_api( $args ) );
	}

	/**
	 * Get domain by group ID.
	 *
	 * Retrieves the domain associated with a specific group ID.
	 *
	 * @since    1.0.0
	 * @param    int $group_id The group ID to get domain for.
	 * @return   string|false  Domain string or false if not found.
	 */
	public static function get_domain_by_group_id( $group_id ) {
		$website = self::get_website_by_group_id( $group_id );
		return $website['domain'] ?? false;
	}

	/**
	 * Get website details by group ID.
	 *
	 * Helper method to get website information from a group ID with caching.
	 *
	 * @since    1.0.0
	 * @param    int $group_id The group ID to get website for.
	 * @return   array|false   Website details or false if not found.
	 */
	public static function get_website_by_group_id( $group_id ) {
		static $websites;

		// Only fetch websites once and cache them in static variable.
		if ( $websites === null ) {
			$websites = \Wp_Compare_API_V2::get_websites_v2()['data'] ?? [];
		}
		
		foreach ( $websites as $website ) {
			if ( in_array( $group_id, array( $website['manual_detection_group'], $website['auto_detection_group'] ) ) ) {
				return $website;
			}
		}
		return false;
	}

	/**
	 * Get status summary for a batch of comparisons.
	 *
	 * @since 1.0.0
	 * @param array $compares Array of comparison data.
	 * @return string HTML output of status summary.
	 */
	public static function get_status_for_batch( $compares ) {
		$status = array();
		foreach ( $compares as $compare_details ) {
			if ( is_null( $compare_details['status'] ) ) {
				$compare_details['status'] = 'none';
			}
			if ( ! isset( $status[ $compare_details['status'] ] ) ) {
				$status[ $compare_details['status'] ] = 0;
			}
			$status[ $compare_details['status'] ] = $status[ $compare_details['status'] ] + 1;
		}

		$output = '';
		foreach ( $status as $singleStatusSlug => $singleStatusAmount ) {
			if ( function_exists( 'prettyPrintComparisonStatus' ) ) {
				$output .= prettyPrintComparisonStatus( $singleStatusSlug );
			}
		}
		return $output;
	}

	/**
	 * Get processing queue count.
	 * 
	 * @since 1.0.0
	 * @param int|false $batch_id Optional batch ID to filter by.
	 * @return int Number of items in processing queue.
	 */
	public static function get_processing_queue( $batch_id = false ) {
		// Get batch_id from POST if not provided
		if ( ! $batch_id && isset( $_POST['batch_id'] ) ) {
			$batch_id = sanitize_text_field( wp_unslash( $_POST['batch_id'] ) );
		}

		// Use API V2 for queue data
		$batches = \Wp_Compare_API_V2::get_queue_v2( $batch_id ?: false, 'open,processing' );
		return $batches['meta']['total'] ?? 0;
	}

	/**
	 * Delete a group.
	 *
	 * @since 1.0.0
	 * @param int $group_id The group ID to delete.
	 * @return mixed API response.
	 */
	public static function delete_group( $group_id ) {
		return \Wp_Compare_API_V2::delete_group_v2( $group_id );
	}

	/**
	 * Delete a URL.
	 *
	 * @since 1.0.0
	 * @param array $postdata POST data containing url_id.
	 * @return mixed API response.
	 */
	public static function delete_url( $postdata ) {
		$args = array(
			'action' => 'delete_url',
			'url_id' => $postdata['url_id'],
		);
		return mm_api( $args );
	}

	/**
	 * Get comparisons by group IDs.
	 *
	 * @since 1.0.0
	 * @param array $args Arguments for the API call.
	 * @return mixed API response.
	 */
	public static function get_compares_by_ids( $args ) {
		// Set default values and ensure proper format
		$args['group_ids']  = isset( $args['group_id'] ) ? json_encode( array( $args['group_id'] ) ) : null;
		$args['limit_days'] = 30;
		$args['action']     = 'get_compares_by_group_ids';

		return mm_api( $args );
	}

	/**
	 * Get comparison partial data by token.
	 *
	 * @since 1.0.0
	 * @param string $token The comparison token.
	 * @return mixed API response.
	 */
	public static function get_comparison_partial( $token ) {
		$args = array(
			'action' => 'get_comparison_partial',
			'token'  => $token,
		);
		return mm_api( $args );
	}

	/**
	 * Get comparison data by token (returns JSON).
	 *
	 * @since 1.0.0
	 * @param string $token The comparison token.
	 * @return mixed API response.
	 */
	public static function get_comparison_by_token( $token ) {
		$args = array(
			'action' => 'get_comparison_by_token',
			'token'  => $token,
		);
		return mm_api( $args );
	}

	/**
	 * Save group CSS and JS settings.
	 *
	 * @since 1.0.0
	 * @param array $postdata POST data containing group_id, css, and js.
	 * @return mixed API response.
	 */
	public static function save_group_css( $postdata ) {
		$args = array(
			'action'   => 'update_group',
			'group_id' => $postdata['group_id'],
			'css'      => $postdata['css'],
			'js'       => $postdata['js'],
		);
		return mm_api( $args );
	}

	/**
	 * Get website details by domain (legacy API wrapper).
	 *
	 * @since 1.0.0
	 * @param string $domain The domain to get details for.
	 * @param string|null $api_token Optional API token.
	 * @return mixed API response.
	 */
	public static function get_website_details_by_domain( $domain, $api_token = null ) {
		$args = array(
			'action' => 'get_website_details',
			'domain' => $domain,
		);

		if ( ! empty( $api_token ) ) {
			$args['api_token'] = $api_token;
		}

		return mm_api( $args );
	}

	/**
	 * Get comparison status by batch ID.
	 *
	 * @since 1.0.0
	 * @param array $postdata POST data containing batch_id.
	 * @return array Array of comparison statuses.
	 */
	public static function get_comparison_status_by_batch_id( $postdata ) {
		$args     = array(
			'action'   => 'get-compares-by-group-ids',
			'batch_id' => $postdata['batch_id'],
		);
		$response = mm_api( $args );
		$status   = array();
		foreach ( $response as $comparison ) {
			$status[] = $comparison['status'];
		}
		return ( $status );
	}

	/**
	 * Update comparison status and return formatted output.
	 *
	 * @since 1.0.0
	 * @param array $postdata POST data containing comparison_id and status.
	 * @return string|false JSON encoded output or false on failure.
	 */
	public static function update_comparison_status( $postdata ) {
		$response = \Wp_Compare_API_V2::update_comparison_v2( $postdata['comparison_id'], $postdata['status'] );
		if ( ! $response ) {
			return false;
		}
		$output['currentComparison'] = prettyPrintComparisonStatus( $postdata['status'], 'mm_inline_block' );

		$batchStatuses = self::get_comparison_status_by_batch_id( $postdata );

		$printedStatuses = array();
		foreach ( $batchStatuses as $batchStatus ) {
			if ( ! in_array( $batchStatus, $printedStatuses ) ) {
				$output['batchStatuses'] .= prettyPrintComparisonStatus( $batchStatus, 'mm_inline_block mm_small_status' ) . '<br>';
				$printedStatuses[]        = $batchStatus;
			}
		}
        
		return json_encode( $output );
	}

	/**
	 * Add website (legacy API wrapper).
	 *
	 * @since 1.0.0
	 * @param array $postdata POST data containing domain.
	 * @return mixed API response.
	 */
	public static function add_website( $postdata ) {
		$args = array(
			'action' => 'add-website-groups',
			'domain' => $postdata['domain'],
		);

		return mm_api( $args );
	}

	/**
	 * Update URL in group (V2 API).
	 *
	 * @since 1.0.0
	 * @param array $postdata POST data containing group_id, url_id, and update data.
	 * @return bool True on success, false on failure.
	 */
	public static function update_group_url_v2( $postdata ) {
		$result_group_url = \Wp_Compare_API_V2::update_url_in_group_v2( $postdata['group_id'], $postdata['url_id'], $postdata );
		$result_url = \Wp_Compare_API_V2::update_url( $postdata );

		if ( ! empty( $result_group_url['data'] ) && ! empty( $result_url['data'] ) ) {
			return true;
		}
		return false;
	}

	/**
	 * Save new URL to group.
	 *
	 * @since 1.0.0
	 * @param array $postdata POST data containing URL and group information.
	 * @return mixed API response.
	 */
	public static function save_url( $postdata ) {
		$group_id = false;
		foreach ( $postdata as $key => $post ) {
			if ( strpos( $key, 'group_id-' ) === 0 )  {
                $group_id = $post;
            }
		}
		$urls = self::textfield_to_array( $postdata['url'] );

		$urls_arr = array();
		foreach ( $urls as $url ) {
			$new_url = \Wp_Compare_API_V2::add_url_v2($url);
			$urls_arr[] = [
                'id' => $new_url['data']['id'],
                'desktop'  => ! empty( $postdata[ 'desktop-' . $group_id ] ) ? $postdata[ 'desktop-' . $group_id ] : 0,
                'mobile'   => ! empty( $postdata[ 'mobile-' . $group_id ] ) ? $postdata[ 'mobile-' . $group_id ] : 0,
                'css'      => ! empty( $postdata[ 'css-' . $group_id ] ) ? $postdata[ 'css-' . $group_id ] : '',
                'js'       => ! empty( $postdata[ 'js-' . $group_id ] ) ? $postdata[ 'js-' . $group_id ] : '',
            ];
		}
        return \Wp_Compare_API_V2::add_urls_to_group_v2($group_id, $urls_arr);
	}

	/**
	 * Save group URLs (bulk update).
	 *
	 * @since 1.0.0
	 * @param array $postdata POST data containing group_id and URL settings.
	 * @return mixed API response.
	 */
	public static function save_group_urls( $postdata ) {
		$active_posts = array(); // init

		// only the ones with `pid-`
		$pidData = array_filter(
			$postdata,
			function ( $value, $key ) {
				return str_contains( $key, 'pid' );
			},
			ARRAY_FILTER_USE_BOTH
		);

		// Get active posts from post data
		foreach ( $pidData as $post_id ) {
			$tmp = array(); // init

			if ( isset( $postdata[ 'desktop-' . $post_id ] ) ) {
				$tmp['desktop'] = $postdata[ 'desktop-' . $post_id ];
			}
			if ( isset( $postdata[ 'mobile-' . $post_id ] ) ) {
				$tmp['mobile'] = $postdata[ 'mobile-' . $post_id ];
			}
			if ( isset( $postdata[ 'css-' . $post_id ] ) ) {
				$tmp['css'] = $postdata[ 'css-' . $post_id ];
			}

			if ( isset( $postdata[ 'js-' . $post_id ] ) ) {
				$tmp['js'] = $postdata[ 'js-' . $post_id ];
			}

			if ( ! empty( $tmp ) ) {
				$tmp['id']  = $post_id;
				$active_posts[] = $tmp; // this is given to API
			}
		}

        return \Wp_Compare_API_V2::update_urls_in_group_v2($postdata['group_id'],$active_posts);
	}

	/**
	 * Select/update URL in group (V2 API).
	 *
	 * @since 1.0.0
	 * @param array $postdata POST data containing device settings and URL info.
	 * @return mixed API response.
	 */
	public static function select_group_url_v2( $postdata ) {
		if ( ! empty( $postdata['device_name'] ) ) {
			$args = array(
				$postdata['device_name'] => $postdata['device_value'],
			);
		} else {
			$args = array(
				'desktop' => $postdata['desktop'] ?? 0,
				'mobile'  => $postdata['mobile'] ?? 0,
			);
		}

		if ( ! empty( $postdata['css'] ) ) {
			$args['css'] = $postdata['css'];
		}
		if ( ! empty( $postdata['js'] ) ) {
			$args['js'] = $postdata['js'];
		}
		if ( ! empty( $postdata['threshold'] ) ) {
			$args['threshold'] = $postdata['threshold'];
		}

		return \Wp_Compare_Api_V2::update_url_in_group_v2( $postdata['group_id'], $postdata['url_id'], $args );
	}

	/**
	 * Update monitoring settings for a group.
	 *
	 * @since 1.0.0
	 * @param array $postdata POST data containing group_id and monitoring setting.
	 * @return mixed API response.
	 */
	public static function update_monitoring_settings( $postdata ) {
		$args = array(
			'action'     => 'update_group',
			'group_id'   => $postdata['group_id'],
			'monitoring' => empty( $postdata['monitoring'] ) ? 0 : $postdata['monitoring'],
		);
		return mm_api( $args );
	}

	/**
	 * Assign URLs to a group.
	 *
	 * @since 1.0.0
	 * @param array $postdata POST data containing group_id and URL assignments.
	 * @return mixed API response.
	 */
	public static function assign_urls( $postdata ) {
		// Get active posts from post data
		$active_posts = array();

		foreach ( $postdata as $key => $url_id ) {
			if ( strpos( $key, 'url_id' ) === 0
			// && $postdata['assign-' . $url_id] == 1)
				&& ( $postdata[ 'desktop-' . $url_id ] == 1
				|| $postdata[ 'mobile-' . $url_id ] == 1 ) ) {
				$active_posts[] = array(
					'url_id'  => $url_id,
					'desktop' => ! empty( $postdata[ 'desktop-' . $url_id ] ) ? $postdata[ 'desktop-' . $url_id ] : 0,
					'mobile'  => ! empty( $postdata[ 'mobile-' . $url_id ] ) ? $postdata[ 'mobile-' . $url_id ] : 0,
					'css'     => ! empty( $postdata[ 'css-' . $url_id ] ) ? urlencode( $postdata[ 'css-' . $url_id ] ) : '',
				);
			}
		}

		// Update API URLs
		$args = array(
			'action'   => 'update_urls',
			'group_id' => $postdata['group_id'],
			'posts'    => json_encode( $active_posts ),
		);
		return mm_api( $args );
	}

	/**
	 * Unassign URLs from group (V2 API).
	 *
	 * @since 1.0.0
	 * @param array $postdata POST data containing group_id and urls to unassign.
	 * @return mixed API response.
	 */
	public static function unassign_group_urls_v2( $postdata ) {
		if ( ! empty( $postdata['url_id'] ) ) {
			$postdata['urls'][] = $postdata['url_id'];
		}
		return \Wp_Compare_API_V2::remove_urls_from_group_v2( $postdata['group_id'], $postdata['urls'] );
	}

	/**
	 * Get unassigned URLs for a group.
	 *
	 * @since 1.0.0
	 * @param int $group_id The group ID.
	 * @return mixed Array of unassigned URLs or false.
	 */
	private static function get_unassigned_urls( $group_id ) {
		$args = array(
			'action'   => 'get_unassigned_urls',
			'group_id' => $group_id,
		);
		return mm_api( $args );
	}

	/**
	 * Get view for unassigned group URLs (outputs HTML).
	 *
	 * @since 1.0.0
	 * @param array $postdata POST data containing group_id.
	 * @return void Outputs HTML directly.
	 */
	public static function get_view_unassigned_group_urls( $postdata ) {
		$client_urls = self::get_unassigned_urls( $postdata['group_id'] );
		if ( is_iterable( $client_urls ) && count( $client_urls ) === 0 ) {
			echo 'There are currently no urls which can be assigned. Please add a new URL to assign it to this group.';
		} elseif ( is_iterable( $client_urls ) ) {
			?>
		<div class="url-table">
			<table style="overflow: scroll">
				<thead>
				<tr>
					<th><?php echo get_device_icon( 'desktop' ); ?></th>
					<th><?php echo get_device_icon( 'mobile' ); ?></th>
					<th width="100%"> URL</th>
				</tr>
				</thead>
				<tbody>
				<?php
				foreach ( $client_urls as $client_url ) {
					$url_id     = $client_url['id'];
					$element_id = $postdata['group_id'] . '-' . $url_id;
					?>
					<input type="hidden" name="url_id-<?php echo $url_id; ?>" value="<?php echo $url_id; ?>">
					<tr>
						<td>
							<label id="container-desktop-<?php echo $element_id; ?>" class="checkbox_container"
									style="float: left">
								<input type="hidden" name="desktop-<?php echo $url_id; ?>" value="0">
								<input type="checkbox"
										id="checkbox-desktop-<?php echo $element_id; ?>"
										name="desktop-<?php echo $url_id; ?>"
										value="1">
								<span class="checkmark"></span>
							</label>
						</td>
						<td>
							<label id="container-mobile-<?php echo $element_id; ?>" class="checkbox_container"
									style="float: left">
								<input type="hidden" name="mobile-<?php echo $url_id; ?>" value="0">
								<input type="checkbox"
										id="checkbox-mobile-<?php echo $element_id; ?>"
										name="mobile-<?php echo $url_id; ?>"
										value="1">
								<span class="checkmark"></span>
							</label>
						</td>
						<td>
							<?php echo '<div class="html-title">' . $client_url['html_title'] . '</div>' . $client_url['url']; ?>
						</td>
					</tr>
					<script>mmMarkRows(<?php echo $url_id; ?>);</script>
					<?php
				}
				?>
				</tbody>
			</table>
		</div>
			<?php
		} else {
			echo 'Something went wrong, please try again.';
		}
	}

	/**
	 * Save user website settings.
	 * Migrated from legacy class-wp-compare.php.
	 *
	 * @param array $postdata Website form data.
	 * @return mixed API response from update_website_v2.
	 */
	public static function save_user_website( $postdata ) {
		$allowances = array();
		foreach ( $postdata as $post_key => $post_value ) {
			if ( starts_with( $post_key, 'allowances_' ) ) {
				$allowances[ substr( $post_key, strlen( 'allowances_' ) ) ] = $post_value;
			}
		}

		$auto_update_settings = array();
		foreach ( $postdata as $post_key => $post_value ) {
			if ( starts_with( $post_key, 'auto_update_settings_' ) ) {
				$auto_update_settings[ substr( $post_key, strlen( 'auto_update_settings_' ) ) ] = $post_value;
			}
		}

		$website_id = $postdata['id'];
		$website_details = array(
			'allowances'                 => $allowances,
			'auto_update_settings'       => $auto_update_settings,
			/*'enable_limits'              => 1, // backwards compatibility
			'allow_manual_detection'     => 1, // backwards compatibility
			'url_limit_manual_detection' => 1, // backwards compatibility
			'allow_auto_detection'       => 1, // backwards compatibility
			'sc_limit'                   => 1,// backwards compatibility */
		);

        return \WebChangeDetector\WebChangeDetector_API_V2::update_website_v2($website_id, $website_details, $postdata['api_token'] ?? null);
	}

	/**
	 * Delete user website and optionally its groups.
	 * Migrated from legacy class-wp-compare.php.
	 *
	 * @param array $postdata Website deletion data.
	 * @param bool  $delete_groups Whether to delete associated groups.
	 * @return mixed API response or false.
	 */
	public static function delete_user_website( $postdata, $delete_groups = false ) {
		$domain = mm_get_domain( $postdata['domain'] );
		$website_details = self::get_website_details_by_domain( $domain );
		
		if ( $delete_groups && $website_details) {
			$group['group_id'] = $website_details[0]['manual_detection_group_id'];
			self::delete_group( $group['group_id'] );

			$group['group_id'] = $website_details[0]['auto_detection_group_id'];
			self::delete_group( $group['group_id'] );
		}
		
        if($website_details) {
	        $args = array(
		        'action' => 'delete_website',
		        'domain' => $domain,
	        );
	        return mm_api( $args );
        }
        return false;
	}

	/**
	 * Get sync job status from database.
	 * Migrated from legacy class-wp-compare.php.
	 *
	 * @param string $job_id The sync job ID.
	 * @return array|null Job data or null if not found.
	 */
	public static function get_sync_job_status( $job_id ) {
		global $wpdb;
		
		return $wpdb->get_row( $wpdb->prepare(
			"SELECT * FROM {$wpdb->prefix}wcd_sync_jobs WHERE job_id = %s",
			$job_id
		), ARRAY_A );
	}

	/**
	 * Get group details and its URLs.
	 * Migrated from legacy class-wp-compare.php.
	 *
	 * @param string $group_uuid The group ID.
	 * @param array  $url_filter Filters for the URLs.
	 * @return array Group data with URLs and metadata.
	 */
	public static function get_group_and_urls_v2( $group_uuid, $url_filter = array() ) {
		$group_and_urls = \WebChangeDetector\Wp_Compare_API_V2::get_group_v2( $group_uuid )['data'];
		$urls           = \WebChangeDetector\Wp_Compare_API_V2::get_group_urls_v2( $group_uuid, $url_filter );

		if ( empty( $urls['data'] ) ) {
			// $this->sync_posts( true );
			// $urls = Wp_Compare_API_V2::get_group_urls_v2( $group_uuid, $url_filter );
		}

		$group_and_urls['urls']                = $urls['data'];
		$group_and_urls['meta']                = $urls['meta'];
		$group_and_urls['selected_urls_count'] = $urls['meta']['selected_urls_count'];

		return $group_and_urls;
	}

	/**
	 * Get comparison status by token.
	 *
	 * @param array $postdata Data containing the token.
	 * @return string Formatted comparison status.
	 */
	public static function get_comparison_status_by_token( $postdata ) {
		$comparison = self::get_comparison_by_token( $postdata['token'] );
		return \prettyPrintComparisonStatus( $comparison['status'], 'mm_inline_block' );
	}

	/**
	 * Save group settings.
	 *
	 * @param array $postdata The form data containing group settings.
	 * @return array|string The group data if successful, error message if failed.
	 */
	public static function save_group_settings( $postdata ) {
		$args = array(
			'enabled'    => empty( $postdata['enabled'] ) ? 0 : $postdata['enabled'],
			'monitoring' => empty( $postdata['monitoring'] ) ? 0 : $postdata['monitoring'],
			'cms'        => $postdata['cms'] ?? null,

			// 'groups' => json_encode( $assigned_groups )
		);
        if ( isset( $postdata['group_name'] ) ) {
            $args['name'] = $postdata['group_name'];
        }
        if ( isset( $postdata['threshold'] ) ) {
            $args['threshold'] = $postdata['threshold'];
        }
		if ( isset( $postdata['css'] ) ) {
			$args['css'] = $postdata['css'];
		}
        if( isset($postdata['js'])) {
	        $args['js']         = $postdata['js'];
        }

		// Send monitoring settings if it is a monitoring group
		if ( (int) $postdata['monitoring'] === 1 ) {
			if( isset($postdata['hour_of_day'])) {
				$args['hour_of_day']         = $postdata['hour_of_day'];
			}
			if( isset($postdata['interval_in_h'])) {
				$args['interval_in_h']         = $postdata['interval_in_h'];
			}
			if( isset($postdata['alert_emails'])) {
				$args['alert_emails']         = explode(",",$postdata['alert_emails']);
			}
		}
        if($postdata['group_id']) {
	        $group = \Wp_Compare_API_V2::update_group( $postdata['group_id'], $args );
        } else {
            $group = \Wp_Compare_API_V2::create_group_v2($args);
        }
		if ( empty( $group['data']) ) {
			return $group['message'];
		}
        $group = $group['data'];
		if ( isset( $postdata['url'] ) ) {
			$postdata[ 'group_id-' . $group['id'] ] = $group['id'];
			$postdata[ 'desktop-' . $group['id'] ]  = $postdata['desktop'];
			unset( $postdata['desktop'] );

			$postdata[ 'mobile-' . $group['id'] ] = $postdata['mobile'];
			unset( $postdata['mobile'] );

			$postdata['css'] = '';

			self::save_url( $postdata );
		}

		/*
		if(!empty($postdata['sync_wp_urls']) && $postdata['sync_wp_urls'] == true) {
			$result = $this->save_wp_group_settings($postdata);
		}*/

		return $group;
	}

	/**
	 * Get queue data.
	 *
	 * @deprecated This method has been replaced by get_processing_queue_v2 in WebChangeDetector_Admin class.
	 *             Use $admin->get_processing_queue_v2() instead.
	 * @param int $page The page number.
	 * @return array|string Queue data.
	 */
	public static function get_queue( $page ) {
		// Delegate to the new admin class if available
		if ( class_exists( '\WebChangeDetector\WebChangeDetector_Admin' ) ) {
			$admin = new \WebChangeDetector\WebChangeDetector_Admin();
			// The new method doesn't use pagination in the same way, but we'll try to adapt
			return $admin->get_processing_queue_v2( false, 30 );
		}

		// Fallback to legacy implementation for compatibility
		return \Wp_Compare_API_V2::get_queue_v2(false, 'open,processing,done,failed', array( 'page' => $page ));
	}

	/**
	 * Ensure sync jobs table exists - safe for live systems.
	 * Checks once and sets option to avoid future checks.
	 *
	 * @return void
	 */
	public static function ensure_sync_jobs_table_exists() {
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
	 * Safe method that can be called at runtime.
	 *
	 * @return void
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
	 * Get account details v2.
	 *
	 * @param string|null $api_token Optional API token to use for the request.
	 * @return array|string|false Account details array, error message, or false on failure.
	 */
	public static function get_account_details_v2( $api_token = null ) {
		// Use cached account details if available and no specific API token requested
		if ( ! empty( self::$account_details ) && empty( $api_token ) ) {
			return self::$account_details;
		}

		$account_details = \Wp_Compare_API_V2::get_account_v2( $api_token );

		if ( ! empty( $account_details['data'] ) ) {
			$account_details                 = $account_details['data'];
			$account_details['checks_limit'] = $account_details['checks_done'] + $account_details['checks_left'];
			
			// Cache the result if using default API token
			if ( empty( $api_token ) ) {
				self::$account_details = $account_details;
			}
			
			return $account_details;
		}
		
		if ( ! empty( $account_details['message'] ) ) {
			return $account_details['message'];
		}

		return false;
	}

	/**
	 * Format post types for sync_url_types field.
	 *
	 * @param array $post_types Post types from form.
	 * @return array Formatted sync URL types.
	 */
	public static function format_sync_url_types( $post_types ) {
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
	 * Static property to cache account details.
	 *
	 * @var array|null
	 */
	private static $account_details = null;
} 