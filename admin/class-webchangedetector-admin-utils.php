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
	 * Extract parameters from URL query string.
	 *
	 * Safely parses URL parameters and returns them as an associative array.
	 *
	 * @since 1.0.0
	 * @param string $url The URL to parse.
	 * @return array|false Array of parameters or false if URL is empty.
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
	 * Generate HTML for admin bar slider.
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
			'<span class="wcd-slider-round"></span>' .
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
} 