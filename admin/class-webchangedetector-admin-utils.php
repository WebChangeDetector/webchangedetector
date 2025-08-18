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
	 * Removes http:// or https:// protocol from URLs for display purposes.
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

		// Remove http:// or https:// protocol.
		$url = preg_replace( '/^https?:\/\//', '', $url );

		return $url;
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
		$icons = array(
			'thumbnail'         => 'camera-alt',
			'desktop'           => 'laptop',
			'mobile'            => 'smartphone',
			'page'              => 'media-default',
			'change-detections' => 'welcome-view-site',
			'dashboard'         => 'admin-home',
			'logs'              => 'menu-alt',
			'settings'          => 'admin-generic',
			'website-settings'  => 'welcome-widgets-menus',
			'help'              => 'editor-help',
			'auto-group'        => 'clock',
			'update-group'      => 'admin-page',
			'auto-update-group' => 'update',
			'trash'             => 'trash',
			'check'             => 'yes-alt',
			'fail'              => 'dismiss',
			'warning'           => 'warning',
			'upgrade'           => 'cart',
		);

		if ( ! isset( $icons[ $icon ] ) ) {
			return;
		}

		// Special case for thumbnail which doesn't use group_icon class.
		$class_prefix = ( $icon === 'thumbnail' ) ? '' : 'group_icon ' . $css_class . ' ';

		$output = sprintf(
			'<span class="%sdashicons dashicons-%s"></span>',
			$class_prefix,
			$icons[ $icon ]
		);

		echo wp_kses( $output, array( 'span' => array( 'class' => array() ) ) );
	}

	/**
	 * Get allowed HTML for device icon.
	 *
	 * Returns an array of allowed HTML for the device icon.
	 *
	 * @since 1.0.0
	 * @return array Allowed HTML for device icon.
	 */
	public static function get_device_icon_allowed_html() {
		return array(
			'span' => array(
				'class' => array(),
			),
		);
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
			'ok'             => __( 'Ok', 'webchangedetector' ),
			'to_fix'         => __( 'To Fix', 'webchangedetector' ),
			'false_positive' => __( 'False Positive', 'webchangedetector' ),
			'failed'         => __( 'Failed', 'webchangedetector' ),
		);

		return isset( $status_names[ $status ] ) ? $status_names[ $status ] : __( 'New', 'webchangedetector' );
	}

	/**
	 * Log error messages for debugging.
	 *
	 * Logs error messages using the database logging system.
	 * Only logs when debug logging is enabled.
	 *
	 * @since 1.0.0
	 * @param string $message The error message to log.
	 * @param string $context Optional context for the error.
	 * @param string $severity Optional severity level.
	 */
	public static function log_error( $message, $context = 'general', $severity = 'info' ) {
		// Use the database logger for static logging.
		$logger = new \WebChangeDetector\WebChangeDetector_Database_Logger();
		$logger->log( $message, $context, $severity );
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

		// Parse URL components.
		$parsed_url = wp_parse_url( $site_url );

		if ( ! isset( $parsed_url['host'] ) ) {
			return '';
		}

		$domain = $parsed_url['host'];

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
		if ( is_object( $post_type ) ) {
			// Check if rest_base is set and not false/empty, otherwise use the post type name.
			if ( ! empty( $post_type->rest_base ) ) {
				return $post_type->rest_base;
			}
			if ( ! empty( $post_type->name ) ) {
				return $post_type->name;
			}
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
	 * Uses labels->name to match exactly what the sync process uses.
	 *
	 * @since 1.0.0
	 * @param string|WP_Post_Type $post_type Post type object or name.
	 * @return string Post type display name.
	 */
	public static function get_post_type_name( $post_type ) {
		if ( is_object( $post_type ) && isset( $post_type->labels->name ) ) {
			return $post_type->labels->name;
		}

		if ( is_string( $post_type ) ) {
			$post_type        = self::get_post_type_slug_from_rest_base( $post_type );
			$post_type_object = get_post_type_object( $post_type );

			if ( $post_type_object && isset( $post_type_object->labels->name ) ) {
				return $post_type_object->labels->name;
			}
		}

		return ucfirst( $post_type );
	}

	/**
	 * Get post type name from rest_base or slug.
	 *
	 * Converts rest_base back to actual post type name, then gets the label.
	 * This handles cases where we have rest_base but need the actual post type label.
	 * Uses labels->name to match exactly what the sync process uses.
	 *
	 * @since 1.0.0
	 * @param string $rest_base_or_slug The rest_base or post type slug.
	 * @return string Post type display name.
	 */
	public static function get_post_type_name_from_rest_base( $rest_base_or_slug ) {
		// First try as direct post type name.
		$post_type_object = get_post_type_object( $rest_base_or_slug );
		if ( $post_type_object && isset( $post_type_object->labels->name ) ) {
			return $post_type_object->labels->name;
		}

		// If not found, search through all post types to find one with matching rest_base.
		$post_types = get_post_types( array( 'public' => true ), 'objects' );
		foreach ( $post_types as $post_type ) {
			$current_rest_base = self::get_post_type_slug( $post_type );
			if ( $current_rest_base === $rest_base_or_slug ) {
				return $post_type->labels->name;
			}
		}

		// Fallback to ucfirst if not found.
		return ucfirst( $rest_base_or_slug );
	}

	/**
	 * Get WordPress post type slug from rest_base.
	 *
	 * Converts rest_base back to the actual WordPress post type slug (internal name).
	 * This is needed when we have rest_base but need the WordPress post type slug.
	 *
	 * @since 1.0.0
	 * @param string $rest_base The rest_base to convert.
	 * @return string WordPress post type slug.
	 */
	public static function get_post_type_slug_from_rest_base( $rest_base ) {
		// First try as direct post type slug.
		if ( post_type_exists( $rest_base ) ) {
			return $rest_base;
		}

		// If not found, search through all post types to find one with matching rest_base.
		$post_types = get_post_types( array( 'public' => true ), 'objects' );
		foreach ( $post_types as $post_type ) {
			$current_rest_base = self::get_post_type_slug( $post_type );
			if ( $current_rest_base === $rest_base ) {
				return $post_type->name; // Return the actual WordPress post type slug.
			}
		}

		// Fallback to the input if not found.
		return $rest_base;
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
	 * Uses labels->name to match exactly what the sync process uses.
	 *
	 * @since 1.0.0
	 * @param string|WP_Taxonomy $taxonomy Taxonomy object or name.
	 * @return string Taxonomy display name.
	 */
	public static function get_taxonomy_name( $taxonomy ) {
		if ( is_object( $taxonomy ) && isset( $taxonomy->labels->name ) ) {
			return $taxonomy->labels->name;
		}

		if ( is_string( $taxonomy ) ) {
			$taxonomy_object = get_taxonomy( $taxonomy );
			if ( $taxonomy_object && isset( $taxonomy_object->labels->name ) ) {
				return $taxonomy_object->labels->name;
			}
		}

		return ucfirst( $taxonomy );
	}

	/**
	 * Get taxonomy name from slug.
	 *
	 * Handles cases where we have taxonomy slug but need the actual taxonomy label.
	 * Unlike post types, taxonomy slugs are typically the same as the taxonomy name.
	 * Uses labels->name to match exactly what the sync process uses.
	 *
	 * @since 1.0.0
	 * @param string $taxonomy_slug The taxonomy slug.
	 * @return string Taxonomy display name.
	 */
	public static function get_taxonomy_name_from_slug( $taxonomy_slug ) {
		// Try direct lookup first.
		$taxonomy_object = get_taxonomy( $taxonomy_slug );
		if ( $taxonomy_object && isset( $taxonomy_object->labels->name ) ) {
			return $taxonomy_object->labels->name;
		}

		// For taxonomies, the slug is typically the same as the name,.
		// so we don't need complex reverse lookup like with post types.
		return ucfirst( $taxonomy_slug );
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
	 * Format difference percentage for display.
	 * Shows "< 0.01" for values between 0 and 0.005, otherwise rounds to 2 decimal places.
	 *
	 * @since 4.0.0
	 * @param float $difference_percent The difference percentage value
	 * @return string Formatted percentage value (without % sign)
	 */
	public static function format_difference_percent( $difference_percent ) {
		if ( $difference_percent > 0 && $difference_percent < 0.005 ) {
			return '< 0.01';
		}
		return round( $difference_percent, 2 );
	}
}
