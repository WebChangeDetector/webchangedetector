<?php
/**
 * Multisite Helper Class for WebChangeDetector.
 *
 * Centralizes all multisite-specific logic: network option access,
 * blog context switching, and sub-site management.
 *
 * @package    WebChangeDetector
 * @subpackage WebChangeDetector/admin
 * @author     Mike Miler <mike@wp-mike.com>
 * @since      4.3.0
 */

namespace WebChangeDetector;

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Multisite Helper Class.
 *
 * Provides static methods for multisite-aware option access and site management.
 * All methods are safe to call on single-site installs (they fall back to standard WP functions).
 *
 * @since 4.3.0
 */
class WebChangeDetector_Multisite {

	/**
	 * Cached result of get_all_sites_with_status().
	 * Persists only within the current HTTP request.
	 *
	 * @var array|null
	 */
	private static $sites_cache = null;

	/**
	 * Cached result of get_all_group_ids().
	 * Persists only within the current HTTP request.
	 *
	 * @var array|null
	 */
	private static $groups_cache = null;

	/**
	 * Options that should be stored network-wide (shared across all sites).
	 *
	 * @var array
	 */
	const NETWORK_OPTIONS = array(
		'webchangedetector_api_token',
		'webchangedetector_account_email',
		'wcd_upgrade_url',
	);

	/**
	 * Check if multisite mode is active for this plugin.
	 *
	 * Returns true only if WordPress is running as multisite AND
	 * the plugin is network-activated.
	 *
	 * @since 4.3.0
	 * @return bool
	 */
	public static function is_multisite_active() {
		if ( ! is_multisite() ) {
			return false;
		}

		if ( ! function_exists( 'is_plugin_active_for_network' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		return is_plugin_active_for_network( WCD_PLUGIN_BASENAME );
	}

	/**
	 * Run a callback in the context of another blog and ALWAYS restore the
	 * original blog on exit, even if the callback throws.
	 *
	 * Centralizes the switch_to_blog / restore_current_blog pattern. Without
	 * try/finally, an uncaught exception inside the callback leaves the WP
	 * `$switched_stack` corrupted, which causes subsequent option writes to
	 * land on the wrong blog (and contaminates other plugins running in the
	 * same request). PLUGIN.md mandates this pattern.
	 *
	 * Skips the switch entirely if we're already on the target blog
	 * (cheap optimization + avoids unnecessary stack growth).
	 *
	 * @since 4.3.0
	 * @param int      $blog_id Target blog id.
	 * @param callable $fn      Callback to run inside the switched context.
	 *                          Receives no arguments. Its return value is returned.
	 * @return mixed The callback's return value.
	 */
	public static function with_blog( $blog_id, callable $fn ) {
		$blog_id = (int) $blog_id;

		if ( $blog_id <= 0 || (int) get_current_blog_id() === $blog_id ) {
			return $fn();
		}

		switch_to_blog( $blog_id );
		try {
			return $fn();
		} finally {
			restore_current_blog();
		}
	}

	// -------------------------------------------------------------------------
	// API Token helpers
	// -------------------------------------------------------------------------

	/**
	 * Get the API token.
	 *
	 * On multisite (network-activated): reads from wp_sitemeta.
	 * On single-site: reads from wp_options.
	 *
	 * @since 4.3.0
	 * @return string|false The API token or false.
	 */
	public static function get_api_token() {
		if ( self::is_multisite_active() ) {
			return get_site_option( 'webchangedetector_api_token', false );
		}
		return get_option( 'webchangedetector_api_token', false );
	}

	/**
	 * Set the API token.
	 *
	 * @since 4.3.0
	 * @param string $token The API token.
	 * @return bool
	 */
	public static function set_api_token( $token ) {
		$token = sanitize_text_field( $token );
		if ( self::is_multisite_active() ) {
			return update_site_option( 'webchangedetector_api_token', $token );
		}
		return update_option( 'webchangedetector_api_token', $token, false );
	}

	/**
	 * Delete the API token.
	 *
	 * @since 4.3.0
	 * @return bool
	 */
	public static function delete_api_token() {
		if ( self::is_multisite_active() ) {
			return delete_site_option( 'webchangedetector_api_token' );
		}
		return delete_option( 'webchangedetector_api_token' );
	}

	// -------------------------------------------------------------------------
	// Shared (network-wide) option helpers
	// -------------------------------------------------------------------------

	/**
	 * Get a shared option (network-wide on multisite, regular on single-site).
	 *
	 * @since 4.3.0
	 * @param string $key           The option key.
	 * @param mixed  $default_value Default value.
	 * @return mixed
	 */
	public static function get_shared_option( $key, $default_value = false ) {
		if ( self::is_multisite_active() ) {
			return get_site_option( $key, $default_value );
		}
		return get_option( $key, $default_value );
	}

	/**
	 * Set a shared option.
	 *
	 * @since 4.3.0
	 * @param string $key   The option key.
	 * @param mixed  $value The option value.
	 * @return bool
	 */
	public static function set_shared_option( $key, $value ) {
		if ( self::is_multisite_active() ) {
			return update_site_option( $key, $value );
		}
		return update_option( $key, $value, false );
	}

	/**
	 * Delete a shared option.
	 *
	 * @since 4.3.0
	 * @param string $key The option key.
	 * @return bool
	 */
	public static function delete_shared_option( $key ) {
		if ( self::is_multisite_active() ) {
			return delete_site_option( $key );
		}
		return delete_option( $key );
	}

	/**
	 * Check if an option key should be stored network-wide.
	 *
	 * @since 4.3.0
	 * @param string $key The option key.
	 * @return bool
	 */
	public static function is_network_option( $key ) {
		return in_array( $key, self::NETWORK_OPTIONS, true );
	}

	// -------------------------------------------------------------------------
	// Blog / Site management helpers
	// -------------------------------------------------------------------------

	/**
	 * Get the blog ID for the current admin context.
	 *
	 * In the network admin, reads from the wcd_blog_id GET/POST parameter.
	 * Falls back to the main site if no parameter is provided.
	 * Outside network admin, returns get_current_blog_id().
	 *
	 * @since 4.3.0
	 * @return int The blog ID.
	 */
	public static function get_current_managed_blog_id() {
		if ( ! self::is_multisite_active() || ! is_network_admin() ) {
			return get_current_blog_id();
		}

		// Check GET parameter first, then POST.
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only context switch.
		if ( isset( $_GET['wcd_blog_id'] ) ) {
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended
			if ( 'all' === $_GET['wcd_blog_id'] ) {
				return get_main_site_id();
			}
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended
			return absint( $_GET['wcd_blog_id'] );
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Missing
		if ( isset( $_POST['wcd_blog_id'] ) ) {
			// phpcs:ignore WordPress.Security.NonceVerification.Missing
			if ( 'all' === $_POST['wcd_blog_id'] ) {
				return get_main_site_id();
			}
			// phpcs:ignore WordPress.Security.NonceVerification.Missing
			return absint( $_POST['wcd_blog_id'] );
		}

		// Default to main site.
		return get_main_site_id();
	}

	/**
	 * Get all sites in the network with their WCD registration status.
	 *
	 * Returns an array of site data including blog_id, domain, path,
	 * and whether each site has a registered WCD website.
	 *
	 * @since 4.3.0
	 * @return array Array of site data arrays.
	 */
	public static function get_all_sites_with_status() {
		if ( ! self::is_multisite_active() ) {
			return array();
		}

		if ( null !== self::$sites_cache ) {
			return self::$sites_cache;
		}

		$sites  = get_sites( array( 'number' => 0 ) );
		$result = array();

		foreach ( $sites as $site ) {
			switch_to_blog( $site->blog_id );
			try {
				$website_id = get_option( 'webchangedetector_website_id', '' );
				$groups     = get_option( 'wcd_website_groups', array() );

				$result[] = array(
					'blog_id'    => (int) $site->blog_id,
					'domain'     => $site->domain,
					'path'       => $site->path,
					'url'        => get_site_url(),
					'registered' => ! empty( $website_id ),
					'website_id' => $website_id,
					'groups'     => $groups,
				);
			} finally {
				restore_current_blog();
			}
		}

		self::$sites_cache = $result;
		return $result;
	}

	/**
	 * Get the count of sites in the network.
	 *
	 * @since 4.3.0
	 * @return int
	 */
	public static function get_site_count() {
		if ( ! self::is_multisite_active() ) {
			return 0;
		}
		return (int) get_blog_count();
	}

	/**
	 * Check if a specific site has a registered WCD website.
	 *
	 * @since 4.3.0
	 * @param int $blog_id The blog ID.
	 * @return bool
	 */
	public static function is_site_registered( $blog_id ) {
		switch_to_blog( $blog_id );
		try {
			$website_id = get_option( 'webchangedetector_website_id', '' );
		} finally {
			restore_current_blog();
		}

		return ! empty( $website_id );
	}

	/**
	 * Get the admin URL for a WCD page, including blog context for network admin.
	 *
	 * @since 4.3.0
	 * @param string   $page    The WCD page slug.
	 * @param int|null $blog_id Optional blog ID for network admin context.
	 * @return string The admin URL.
	 */
	public static function get_admin_url( $page = 'webchangedetector', $blog_id = null ) {
		$args = array( 'page' => $page );

		if ( self::is_multisite_active() && null !== $blog_id ) {
			$args['wcd_blog_id'] = $blog_id;
			return network_admin_url( 'admin.php?' . http_build_query( $args ) );
		}

		return admin_url( 'admin.php?' . http_build_query( $args ) );
	}

	/**
	 * Get the hidden form field HTML that preserves the blog context in POST forms.
	 *
	 * Returns the HTML for a hidden input (or an empty string when not in network
	 * admin context). Use this when building an HTML string; use render_blog_context_field()
	 * when outputting directly.
	 *
	 * @since 4.3.0
	 * @return string The hidden input HTML or an empty string.
	 */
	public static function get_blog_context_field_html() {
		if ( ! self::is_multisite_active() || ! self::is_network_context() ) {
			return '';
		}

		if ( self::is_all_sites_mode() ) {
			return '<input type="hidden" name="wcd_blog_id" value="all">';
		}

		$blog_id = self::get_current_managed_blog_id();
		return '<input type="hidden" name="wcd_blog_id" value="' . esc_attr( $blog_id ) . '">';
	}

	/**
	 * Render a hidden form field to preserve the blog context in POST forms.
	 *
	 * Outputs a hidden input for wcd_blog_id when in network admin context.
	 * Call this inside any <form> that submits via POST in the network admin.
	 *
	 * @since 4.3.0
	 */
	public static function render_blog_context_field() {
		echo self::get_blog_context_field_html(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Safe HTML built from controlled values with esc_attr().
	}

	/**
	 * Get the correct form action URL for the current admin context.
	 *
	 * Returns the proper admin URL (network or regular) with blog context preserved.
	 *
	 * @since 4.3.0
	 * @param string $page The WCD page slug.
	 * @return string The form action URL.
	 */
	public static function get_form_action_url( $page ) {
		$url = 'admin.php?page=' . $page;

		if ( self::is_multisite_active() && is_network_admin() ) {
			if ( self::is_all_sites_mode() ) {
				return network_admin_url( $url . '&wcd_blog_id=all' );
			}
			$blog_id = self::get_current_managed_blog_id();
			return network_admin_url( $url . '&wcd_blog_id=' . intval( $blog_id ) );
		}

		return $url;
	}

	/**
	 * Check if "All Websites" mode is active.
	 *
	 * Returns true when the user selected "All Websites" in the network admin
	 * site selector. Works in both page loads and AJAX requests.
	 *
	 * Does NOT check is_network_admin() because that is always false in AJAX.
	 *
	 * @since 4.3.0
	 * @return bool
	 */
	public static function is_all_sites_mode() {
		if ( ! self::is_multisite_active() ) {
			return false;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( isset( $_GET['wcd_blog_id'] ) && 'all' === $_GET['wcd_blog_id'] ) {
			return true;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Missing
		if ( isset( $_POST['wcd_blog_id'] ) && 'all' === $_POST['wcd_blog_id'] ) {
			return true;
		}

		return false;
	}

	/**
	 * Get all group IDs across all registered sites in the network.
	 *
	 * Returns arrays of monitoring and manual group UUIDs from all
	 * registered sub-sites. Uses get_all_sites_with_status() internally.
	 *
	 * @since 4.3.0
	 * @return array {
	 *     @type array $monitoring Array of monitoring group UUIDs.
	 *     @type array $manual     Array of manual group UUIDs.
	 *     @type array $all        All group UUIDs combined.
	 *     @type array $by_site    Group UUIDs keyed by site data.
	 * }
	 */
	public static function get_all_group_ids() {
		if ( null !== self::$groups_cache ) {
			return self::$groups_cache;
		}

		$sites      = self::get_all_sites_with_status();
		$all_groups = array(
			'monitoring' => array(),
			'manual'     => array(),
			'all'        => array(),
			'by_site'    => array(),
		);

		foreach ( $sites as $site ) {
			if ( ! $site['registered'] || empty( $site['groups'] ) ) {
				continue;
			}

			$site_groups = array(
				'url'        => $site['url'],
				'blog_id'    => $site['blog_id'],
				'monitoring' => null,
				'manual'     => null,
			);

			if ( ! empty( $site['groups'][ WCD_AUTO_DETECTION_GROUP ] ) ) {
				$group_id                   = $site['groups'][ WCD_AUTO_DETECTION_GROUP ];
				$all_groups['monitoring'][] = $group_id;
				$all_groups['all'][]        = $group_id;
				$site_groups['monitoring']  = $group_id;
			}

			if ( ! empty( $site['groups'][ WCD_MANUAL_DETECTION_GROUP ] ) ) {
				$group_id               = $site['groups'][ WCD_MANUAL_DETECTION_GROUP ];
				$all_groups['manual'][] = $group_id;
				$all_groups['all'][]    = $group_id;
				$site_groups['manual']  = $group_id;
			}

			$all_groups['by_site'][] = $site_groups;
		}

		self::$groups_cache = $all_groups;
		return $all_groups;
	}

	/**
	 * Clear the static caches for site and group data.
	 *
	 * Call this after operations that modify site registration status
	 * or group assignments within the same HTTP request.
	 *
	 * @since 4.3.0
	 */
	public static function clear_cache() {
		self::$sites_cache  = null;
		self::$groups_cache = null;
	}

	/**
	 * Detect and repair inconsistent token storage.
	 *
	 * If the plugin was toggled between network-activated and per-site-activated
	 * without running the activation hook (e.g. via WP-CLI or manual edits), the
	 * API token may live in the wrong storage (wp_options vs wp_sitemeta). This
	 * method migrates the token into the correct storage on admin_init.
	 *
	 * Cheap path: two option reads when storage is already consistent.
	 * Recovery path: one switch_to_blog() to read the main site's legacy value.
	 *
	 * @since 4.3.0
	 */
	public static function ensure_token_storage_consistency() {
		if ( ! is_multisite() ) {
			return;
		}

		// Network-activated: token should live in wp_sitemeta.
		if ( self::is_multisite_active() ) {
			$network_token = get_site_option( 'webchangedetector_api_token', false );
			if ( ! empty( $network_token ) ) {
				return;
			}

			// Fallback: look for a legacy token on the main site's wp_options.
			$site_data = self::with_blog(
				get_main_site_id(),
				function () {
					return array(
						'token' => get_option( 'webchangedetector_api_token', false ),
						'email' => get_option( 'webchangedetector_account_email', false ),
					);
				}
			);
			$site_token = $site_data['token'];
			$site_email = $site_data['email'];

			if ( ! empty( $site_token ) ) {
				update_site_option( 'webchangedetector_api_token', $site_token );
				if ( ! empty( $site_email ) ) {
					update_site_option( 'webchangedetector_account_email', $site_email );
				}
			}
			return;
		}

		// Not network-activated: token should live in wp_options of the current site.
		// Only repair on the main site; sub-sites do not own the shared token.
		if ( ! is_main_site() ) {
			return;
		}

		$local_token = get_option( 'webchangedetector_api_token', false );
		if ( ! empty( $local_token ) ) {
			return;
		}

		$network_token = get_site_option( 'webchangedetector_api_token', false );
		$network_email = get_site_option( 'webchangedetector_account_email', false );
		if ( ! empty( $network_token ) ) {
			update_option( 'webchangedetector_api_token', $network_token, false );
			if ( ! empty( $network_email ) ) {
				update_option( 'webchangedetector_account_email', $network_email, false );
			}
		}
	}

	/**
	 * Check if the current request is a multisite network context.
	 *
	 * Works in both regular page loads (is_network_admin()) and AJAX
	 * (detects wcd_blog_id parameter).
	 *
	 * @since 4.3.0
	 * @return bool
	 */
	public static function is_network_context() {
		if ( ! self::is_multisite_active() ) {
			return false;
		}

		// Require super-admin capability in BOTH branches (defense-in-depth).
		// WordPress normally enforces `manage_network_options` before rendering
		// network admin pages, but `is_network_admin()` itself only checks the
		// URL pattern — and the result of this function is used to set the
		// `x-wcd-network-admin: 1` header on API calls (see api_v2() / api_v2_bulk()),
		// which the API trusts for allowance writes. Without this capability check,
		// any sub-site admin who can reach a network-admin URL (deep link, leaked
		// path, future routing change) would get the elevated header.
		if ( ! current_user_can( 'manage_network_options' ) ) {
			return false;
		}

		if ( is_network_admin() ) {
			return true;
		}

		// In AJAX, is_network_admin() is false. Detect via parameter.
		// phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.NonceVerification.Recommended
		$has_blog_id = ! empty( $_POST['wcd_blog_id'] ) || ! empty( $_GET['wcd_blog_id'] );

		return $has_blog_id;
	}
}
