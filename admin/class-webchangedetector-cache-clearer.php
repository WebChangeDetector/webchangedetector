<?php
/**
 * Cache clearing across third-party cache plugins and hosting stacks.
 *
 * @package    WebChangeDetector
 * @subpackage WebChangeDetector/admin
 */

namespace WebChangeDetector;

defined( 'ABSPATH' ) || exit;

/**
 * Clears all known WordPress cache plugins and systems.
 *
 * Used by the auto-update workflow before taking pre- and post-update screenshots
 * so both batches capture fresh, uncached pages.
 *
 * The integration list mirrors MainWP Child's class-mainwp-child-cache-purge.php,
 * with three deliberate differences: we run ALL matching integrations instead of
 * first-match (better for stacked setups), we never preload after purging (it only
 * costs time before the screenshot batch), and we keep extra integrations MainWP
 * Child does not cover (Borlabs, WP Engine, Pagely, Redis Object Cache, Object
 * Cache Pro, Perfmatters, core object cache, transients). Re-check against that
 * MainWP Child file whenever this list is touched.
 */
class WebChangeDetector_Cache_Clearer {

	/**
	 * Clear every detected cache system.
	 *
	 * Every integration is guarded by its own presence check and runs inside its
	 * own catch block, so a broken third-party plugin can never fatal the
	 * auto-update flow (\Throwable, not just \Exception: TypeErrors from changed
	 * third-party signatures are the realistic failure).
	 *
	 * @return void
	 */
	public static function clear_all() {
		\WebChangeDetector\WebChangeDetector_Admin_Utils::log_error( 'Clearing all WordPress caches before taking screenshots.', 'cache_clearer', 'debug' );

		$cleared_caches = array();
		$failed_caches  = array();

		foreach ( self::get_integrations() as $cache_name => $purge_callback ) {
			try {
				if ( $purge_callback() ) {
					$cleared_caches[] = $cache_name;
				}
			} catch ( \Throwable $e ) {
				$failed_caches[] = $cache_name . ': ' . $e->getMessage();
			}
		}

		if ( ! empty( $cleared_caches ) ) {
			\WebChangeDetector\WebChangeDetector_Admin_Utils::log_error( 'Successfully cleared caches: ' . implode( ', ', $cleared_caches ), 'cache_clearer', 'debug' );
		}
		if ( ! empty( $failed_caches ) ) {
			\WebChangeDetector\WebChangeDetector_Admin_Utils::log_error( 'Failed to clear some caches: ' . implode( '; ', $failed_caches ), 'cache_clearer', 'debug' );
		}
		if ( empty( $cleared_caches ) && empty( $failed_caches ) ) {
			\WebChangeDetector\WebChangeDetector_Admin_Utils::log_error( 'No cache plugins detected or cleared.', 'cache_clearer', 'debug' );
		}
	}

	/**
	 * Integration list: cache name => guarded purge callback.
	 *
	 * Each callback returns true when its cache system was detected and purged and
	 * false when the plugin is not present, so absent plugins are never logged as
	 * "cleared" (pure do_action integrations gate on has_action for the same
	 * reason). Callbacks may throw; clear_all() catches per integration.
	 *
	 * @return array<string, callable> Ordered integration list.
	 */
	private static function get_integrations() {
		return array(
			'WP Rocket'                   => static function () {
				if ( ! function_exists( '\rocket_clean_domain' ) ) {
					return false;
				}
				rocket_clean_domain();
				if ( function_exists( '\rocket_clean_minify' ) ) {
					rocket_clean_minify();
				}
				return true;
			},
			'W3 Total Cache'              => static function () {
				if ( ! function_exists( '\w3tc_flush_all' ) ) {
					return false;
				}
				w3tc_flush_all();
				return true;
			},
			'LiteSpeed Cache'             => static function () {
				$cleared = false;
				if ( defined( 'LSCWP_VERSION' ) ) {
					do_action( 'litespeed_purge_all' );
					do_action( 'litespeed_purge_cssjs' );
					do_action( 'litespeed_purge_object' );
					$cleared = true;
				}
				if ( class_exists( '\LiteSpeed_Cache_API' ) && method_exists( '\LiteSpeed_Cache_API', 'purge_all' ) ) {
					\LiteSpeed_Cache_API::purge_all();
					$cleared = true;
				}
				return $cleared;
			},
			'WP Super Cache'              => static function () {
				if ( function_exists( '\wp_cache_clear_cache' ) ) {
					// Expects an optional blog id, not a boolean.
					wp_cache_clear_cache();
					return true;
				}
				if ( function_exists( '\wp_cache_post_change' ) ) {
					wp_cache_post_change( '' );
					return true;
				}
				return false;
			},
			'WP Fastest Cache'            => static function () {
				if ( ! function_exists( '\wpfc_clear_all_cache' ) ) {
					return false;
				}
				wpfc_clear_all_cache( true );
				return true;
			},
			'Cache Enabler'               => static function () {
				// Current API first (v1.5.0+), legacy calls as fallback.
				if ( class_exists( '\Cache_Enabler' ) && method_exists( '\Cache_Enabler', 'clear_complete_cache' ) ) {
					\Cache_Enabler::clear_complete_cache();
					return true;
				}
				$cleared = false;
				if ( class_exists( '\Cache_Enabler' ) && method_exists( '\Cache_Enabler', 'clear_total_cache' ) ) {
					\Cache_Enabler::clear_total_cache();
					$cleared = true;
				}
				if ( class_exists( '\Cache_Enabler_Engine' ) && method_exists( '\Cache_Enabler_Engine', 'clear_cache' ) ) {
					\Cache_Enabler_Engine::clear_cache();
					$cleared = true;
				}
				return $cleared;
			},
			'Comet Cache'                 => static function () {
				if ( ! class_exists( '\comet_cache' ) || ! method_exists( '\comet_cache', 'clear' ) ) {
					return false;
				}
				\comet_cache::clear();
				return true;
			},
			'Swift Performance'           => static function () {
				if ( ! class_exists( '\Swift_Performance_Cache' ) || ! method_exists( '\Swift_Performance_Cache', 'clear_all_cache' ) ) {
					return false;
				}
				\Swift_Performance_Cache::clear_all_cache();
				return true;
			},
			'Borlabs Cache'               => static function () {
				$cleared = false;
				if ( function_exists( '\borlabsCacheClearCache' ) ) {
					borlabsCacheClearCache();
					$cleared = true;
				}
				// phpcs:ignore WordPress.NamingConventions.ValidHookName.UseUnderscores, WordPress.NamingConventions.ValidHookName.NotLowercase -- Third-party hook name.
				if ( has_action( 'borlabsCookie/thirdPartyCacheClearer/shouldClearCache' ) ) {
					// phpcs:ignore WordPress.NamingConventions.ValidHookName.UseUnderscores, WordPress.NamingConventions.ValidHookName.NotLowercase -- Third-party hook name.
					do_action( 'borlabsCookie/thirdPartyCacheClearer/shouldClearCache', true );
					$cleared = true;
				}
				return $cleared;
			},
			'NitroPack'                   => static function () {
				// Current API first, legacy functions as fallback.
				if ( function_exists( '\nitropack_purge' ) ) {
					nitropack_purge();
					return true;
				}
				if ( function_exists( '\nitropack_reset_cache' ) ) {
					nitropack_reset_cache();
					return true;
				}
				if ( function_exists( '\nitropack_purge_cache' ) ) {
					nitropack_purge_cache();
					return true;
				}
				return false;
			},
			'Redis Object Cache'          => static function () {
				global $wp_object_cache;
				if ( ! $wp_object_cache || ! method_exists( $wp_object_cache, 'flush' ) ) {
					return false;
				}
				$wp_object_cache->flush();
				return true;
			},
			'Object Cache Pro'            => static function () {
				if ( ! class_exists( '\Object_Cache_Pro' ) ) {
					return false;
				}
				global $wp_object_cache;
				if ( method_exists( $wp_object_cache, 'flushRuntime' ) ) {
					$wp_object_cache->flushRuntime();
				}
				if ( method_exists( $wp_object_cache, 'flushBlog' ) ) {
					$wp_object_cache->flushBlog();
				}
				return true;
			},
			'SiteGround Optimizer'        => static function () {
				// Current API first, legacy function as fallback.
				if ( function_exists( '\sg_cachepress_purge_everything' ) ) {
					sg_cachepress_purge_everything();
					return true;
				}
				$cleared = false;
				if ( function_exists( '\sg_cachepress_purge_cache' ) ) {
					sg_cachepress_purge_cache();
					$cleared = true;
				}
				if ( has_action( 'siteground_optimizer_flush_cache' ) ) {
					do_action( 'siteground_optimizer_flush_cache' );
					$cleared = true;
				}
				return $cleared;
			},
			'WP-Optimize'                 => static function () {
				if ( ! function_exists( '\wpo_cache_flush' ) ) {
					return false;
				}
				wpo_cache_flush();
				return true;
			},
			'Autoptimize'                 => static function () {
				if ( ! class_exists( '\autoptimizeCache' ) || ! method_exists( '\autoptimizeCache', 'clearall' ) ) {
					return false;
				}
				\autoptimizeCache::clearall();
				return true;
			},
			'Hummingbird'                 => static function () {
				if ( ! has_action( 'wphb_clear_page_cache' ) ) {
					return false;
				}
				do_action( 'wphb_clear_page_cache' );
				return true;
			},
			'Breeze'                      => static function () {
				if ( ! has_action( 'breeze_clear_all_cache' ) ) {
					return false;
				}
				do_action( 'breeze_clear_all_cache' );
				return true;
			},
			'Kinsta Cache'                => static function () {
				if ( ! class_exists( '\Kinsta\Cache' ) ) {
					return false;
				}
				global $kinsta_cache;
				if ( empty( $kinsta_cache ) || empty( $kinsta_cache->kinsta_cache_purge ) ) {
					return false;
				}
				$kinsta_cache->kinsta_cache_purge->purge_complete_caches();
				return true;
			},
			'Pagely Cache'                => static function () {
				if ( ! class_exists( '\PagelyCachePurge' ) || ! method_exists( '\PagelyCachePurge', 'purgeAll' ) ) {
					return false;
				}
				\PagelyCachePurge::purgeAll();
				return true;
			},
			'WP Engine'                   => static function () {
				if ( ! class_exists( '\WpeCommon' ) ) {
					return false;
				}
				$cleared = false;
				if ( method_exists( '\WpeCommon', 'purge_memcached' ) ) {
					\WpeCommon::purge_memcached();
					$cleared = true;
				}
				if ( method_exists( '\WpeCommon', 'purge_varnish_cache' ) ) {
					\WpeCommon::purge_varnish_cache();
					$cleared = true;
				}
				return $cleared;
			},
			// Official Cloudflare plugin (\CF\WordPress\Hooks): intentionally NOT integrated.
			// The plugin documents no external purge function or listener hook; its
			// cloudflare_purge_everything_actions filter is only read while the plugin wires
			// its own hooks, which is long before we run. The previous integration
			// instantiated the plugin's main Hooks class with `new`, re-running its
			// constructor (API client setup) as a side effect, and was dropped for that reason.
			'Flying Press'                => static function () {
				if ( ! class_exists( '\FlyingPress\Purge' ) || ! method_exists( '\FlyingPress\Purge', 'purge_everything' ) ) {
					return false;
				}
				\FlyingPress\Purge::purge_everything();
				return true;
			},
			'Super Page Cache'            => static function () {
				// Formerly "WP Cloudflare Super Page Cache". Documented purge action;
				// replaces the old integration that instantiated the plugin's main class.
				if ( ! has_action( 'swcfpc_purge_cache' ) ) {
					return false;
				}
				do_action( 'swcfpc_purge_cache' );
				return true;
			},
			'Perfmatters'                 => static function () {
				if ( ! function_exists( '\perfmatters_clear_page_cache' ) ) {
					return false;
				}
				perfmatters_clear_page_cache();
				return true;
			},
			'WP-Rocket Cloudflare Add-on' => static function () {
				if ( ! function_exists( '\rocket_cloudflare_purge_cache' ) ) {
					return false;
				}
				rocket_cloudflare_purge_cache();
				return true;
			},
			'Nginx Helper'                => static function () {
				if ( ! has_action( 'rt_nginx_helper_purge_all' ) ) {
					return false;
				}
				do_action( 'rt_nginx_helper_purge_all' );
				return true;
			},
			'Seraphinite Accelerator'     => static function () {
				if ( ! class_exists( '\seraph_accel\API' )
					|| ! method_exists( '\seraph_accel\API', 'OperateCache' )
					|| ! defined( '\seraph_accel\API::CACHE_OP_DEL' ) ) {
					return false;
				}
				\seraph_accel\API::OperateCache( \seraph_accel\API::CACHE_OP_DEL );
				return true;
			},
			'Swis Performance'            => static function () {
				if ( ! has_action( 'swis_clear_complete_cache' ) ) {
					return false;
				}
				do_action( 'swis_clear_complete_cache' );
				return true;
			},
			'RunCloud Hub'                => static function () {
				if ( ! class_exists( '\RunCloud_Hub' ) || ! method_exists( '\RunCloud_Hub', 'purge_cache_all' ) ) {
					return false;
				}
				\RunCloud_Hub::purge_cache_all();
				return true;
			},
			'FastPixel'                   => static function () {
				// phpcs:ignore WordPress.NamingConventions.ValidHookName.UseUnderscores -- Third-party hook name.
				if ( ! has_action( 'fastpixel/purge/all' ) ) {
					return false;
				}
				// phpcs:ignore WordPress.NamingConventions.ValidHookName.UseUnderscores -- Third-party hook name.
				do_action( 'fastpixel/purge/all' );
				return true;
			},
			'Pressable'                   => static function () {
				if ( ! function_exists( '\flush_pressable_cache_callback' ) ) {
					return false;
				}
				flush_pressable_cache_callback();
				return true;
			},
			'WordPress Core Object Cache' => static function () {
				if ( ! function_exists( '\wp_cache_flush' ) ) {
					return false;
				}
				wp_cache_flush();
				return true;
			},
			'WooCommerce Transients'      => static function () {
				if ( ! function_exists( '\wc_delete_product_transients' ) ) {
					return false;
				}
				wc_delete_product_transients();
				return true;
			},
			'Expired Transients'          => static function () {
				if ( ! function_exists( '\delete_expired_transients' ) ) {
					return false;
				}
				delete_expired_transients( true );
				return true;
			},
		);
	}
}
