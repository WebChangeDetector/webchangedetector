<?php
/**
 * Guard restoring dropped update offers during an Auto Update Check run.
 *
 * Premium plugins and themes (not hosted on wordpress.org) inject their update
 * offers into the update_plugins / update_themes site transients via updaters
 * that are frequently registered on admin_init only. Any transient rebuild
 * from a non-admin request (wp-cron, webhook ping, parallel spawn) silently
 * drops those offers. Vanilla WordPress reads offers and installs in the same
 * request; WCD decides in one request (pre-update screenshots) and lets core
 * install in a later request, so a mid-window rebuild made core's upgrader
 * skip the item with a misleading "Could not access filesystem." error.
 *
 * This guard snapshots the offers at run start and re-injects missing ones on
 * the READ side (site_transient_update_{plugins,themes} filters, applied by
 * get_site_transient() on every read), so all consumers see restored offers
 * regardless of who wiped the transient in between.
 *
 * @package    WebChangeDetector
 * @subpackage WebChangeDetector/admin
 */

namespace WebChangeDetector;

defined( 'ABSPATH' ) || exit;

/**
 * Restores dropped update offers while an auto-update run is active.
 */
class WebChangeDetector_Update_Offer_Guard {

	/**
	 * Option holding the auto-update run state (mirrors WCD_PRE_AUTO_UPDATE).
	 *
	 * Literal on purpose: the WCD_PRE_AUTO_UPDATE constant is defined by the
	 * autoupdates class, which loads after this guard registers, and the guard
	 * must not depend on that load order.
	 *
	 * @var string
	 */
	const PRE_UPDATE_OPTION = 'wcd_pre_auto_update';

	/**
	 * In-request offer snapshot or null when not set in this request.
	 *
	 * Shape: array{plugins: array<string, object>, themes: array<string, array>}.
	 * Plugin entries are stdClass objects, theme entries are arrays; both are
	 * kept exactly as stored in the transients (core relies on these shapes).
	 *
	 * @var array|null
	 */
	private static $snapshot = null;

	/**
	 * Per-request cache of the offers persisted in the run option.
	 *
	 * False when no active run persists offers; null until first looked up.
	 *
	 * @var array|false|null
	 */
	private static $persisted_offers = null;

	/**
	 * Items already logged as restored in this request (log throttle).
	 *
	 * @var array<string, true>
	 */
	private static $logged_restores = array();

	/**
	 * Register the read-side restore filters.
	 *
	 * Must run on every request type (wp-cron, webhook, admin) so every
	 * consumer of the update transients sees restored offers during a run.
	 *
	 * @return void
	 */
	public static function register() {
		add_filter( 'site_transient_update_plugins', array( __CLASS__, 'filter_update_plugins' ) );
		add_filter( 'site_transient_update_themes', array( __CLASS__, 'filter_update_themes' ) );
	}

	/**
	 * Set the in-request offer snapshot (activates the guard for this request).
	 *
	 * @param array $plugin_offers Entries from update_plugins->response (stdClass objects).
	 * @param array $theme_offers  Entries from update_themes->response (arrays).
	 * @return void
	 */
	public static function set_snapshot( $plugin_offers, $theme_offers ) {
		self::$snapshot = array(
			'plugins' => is_array( $plugin_offers ) ? $plugin_offers : array(),
			'themes'  => is_array( $theme_offers ) ? $theme_offers : array(),
		);
	}

	/**
	 * Get the current in-request snapshot (for persisting into the run option).
	 *
	 * @return array Offers: 'plugins' (stdClass entries) and 'themes' (array entries).
	 */
	public static function get_snapshot() {
		if ( null !== self::$snapshot ) {
			return self::$snapshot;
		}
		return array(
			'plugins' => array(),
			'themes'  => array(),
		);
	}

	/**
	 * Reset all in-request state.
	 *
	 * Called from cleanup_auto_update_run() so the guard deactivates in the
	 * same request that deletes the run option.
	 *
	 * @return void
	 */
	public static function reset() {
		self::$snapshot         = null;
		self::$persisted_offers = null;
		self::$logged_restores  = array();
	}

	/**
	 * Filter callback for site_transient_update_plugins.
	 *
	 * @param mixed $value Transient value (object, or false when unset).
	 * @return mixed Value with missing snapshot offers re-injected.
	 */
	public static function filter_update_plugins( $value ) {
		return self::restore_offers( $value, 'plugins' );
	}

	/**
	 * Filter callback for site_transient_update_themes.
	 *
	 * @param mixed $value Transient value (object, or false when unset).
	 * @return mixed Value with missing snapshot offers re-injected.
	 */
	public static function filter_update_themes( $value ) {
		return self::restore_offers( $value, 'themes' );
	}

	/**
	 * Re-inject snapshot offers missing from the transient value.
	 *
	 * Only fills keys missing from ->response and never overwrites entries
	 * present in the incoming value. An offer is only injected while the item
	 * is still installed at a version older than the offer's new_version, so
	 * already-updated items are never re-offered. The incoming false / non-
	 * object value is normalized to a stdClass only when there is something
	 * to inject. No recursion risk: this method never reads a site transient.
	 *
	 * @param mixed  $value Transient value (object, or false when unset).
	 * @param string $type  Either 'plugins' or 'themes'.
	 * @return mixed Original or amended value.
	 */
	private static function restore_offers( $value, $type ) {
		$offers = self::get_active_offers( $type );
		if ( empty( $offers ) ) {
			return $value;
		}

		// Cheap pass first: on the common hot path nothing is missing.
		$missing = array();
		foreach ( $offers as $key => $offer ) {
			if ( ! is_object( $value ) || ! isset( $value->response[ $key ] ) ) {
				$missing[ $key ] = $offer;
			}
		}
		if ( empty( $missing ) ) {
			return $value;
		}

		$installed = self::get_installed_versions( $type );

		foreach ( $missing as $key => $offer ) {
			$new_version = 'plugins' === $type ? (string) ( $offer->new_version ?? '' ) : (string) ( $offer['new_version'] ?? '' );
			if ( '' === $new_version || ! isset( $installed[ $key ] ) || ! version_compare( $installed[ $key ], $new_version, '<' ) ) {
				continue;
			}

			if ( ! is_object( $value ) ) {
				$value = new \stdClass();
			}
			if ( ! isset( $value->response ) || ! is_array( $value->response ) ) {
				$value->response = array();
			}
			$value->response[ $key ] = $offer;
			self::log_restore( $type, $key, $new_version );
		}

		return $value;
	}

	/**
	 * Get the active offer snapshot for a type, or an empty array when inactive.
	 *
	 * The in-request snapshot (set by check_for_available_updates() in the
	 * request that starts a run) wins; on all later requests of the run window
	 * the offers persisted in the run option are used. The option lookup is
	 * cached per request: the filters run on a hot path and the option is
	 * stored with autoload=false.
	 *
	 * @param string $type Either 'plugins' or 'themes'.
	 * @return array Offer entries keyed by plugin file resp. theme slug.
	 */
	private static function get_active_offers( $type ) {
		if ( null !== self::$snapshot ) {
			return self::$snapshot[ $type ];
		}

		if ( null === self::$persisted_offers ) {
			self::$persisted_offers = false;
			$run_state              = get_option( self::PRE_UPDATE_OPTION );
			if ( is_array( $run_state ) && isset( $run_state['offers'] ) && is_array( $run_state['offers'] ) ) {
				self::$persisted_offers = self::normalize_offers( $run_state['offers'] );
			}
		}

		if ( false === self::$persisted_offers ) {
			return array();
		}
		return self::$persisted_offers[ $type ] ?? array();
	}

	/**
	 * Normalize offers read back from the run option to their core shapes.
	 *
	 * The option round-trip keeps stdClass entries via PHP serialize, but an
	 * external object cache may degrade them; core strictly needs plugin
	 * entries as stdClass objects and theme entries as ARRAYS
	 * (Theme_Upgrader::upgrade() uses array access on them).
	 *
	 * @param array $offers Offers as stored in the run option.
	 * @return array Normalized offers: 'plugins' and 'themes'.
	 */
	private static function normalize_offers( $offers ) {
		$plugins = array();
		foreach ( (array) ( $offers['plugins'] ?? array() ) as $key => $offer ) {
			$plugins[ $key ] = is_array( $offer ) ? (object) $offer : $offer;
		}

		$themes = array();
		foreach ( (array) ( $offers['themes'] ?? array() ) as $key => $offer ) {
			$themes[ $key ] = is_object( $offer ) ? (array) $offer : $offer;
		}

		return array(
			'plugins' => $plugins,
			'themes'  => $themes,
		);
	}

	/**
	 * Get installed versions keyed like the transient response entries.
	 *
	 * Read fresh on every call (only reached when an offer is actually
	 * missing): get_plugins() and the theme directory scan carry their own
	 * WordPress-level caches, which core invalidates after an install, so a
	 * just-updated item is never re-offered from a stale local cache.
	 *
	 * @param string $type Either 'plugins' or 'themes'.
	 * @return array<string, string> Installed versions keyed by plugin file resp. theme slug.
	 */
	private static function get_installed_versions( $type ) {
		$versions = array();

		if ( 'plugins' === $type ) {
			if ( ! function_exists( 'get_plugins' ) ) {
				require_once ABSPATH . 'wp-admin/includes/plugin.php';
			}
			foreach ( get_plugins() as $plugin_file => $plugin_data ) {
				if ( isset( $plugin_data['Version'] ) ) {
					$versions[ $plugin_file ] = $plugin_data['Version'];
				}
			}
			return $versions;
		}

		foreach ( wp_get_themes() as $stylesheet => $theme ) {
			$versions[ $stylesheet ] = $theme->get( 'Version' );
		}
		return $versions;
	}

	/**
	 * Debug-log a restored offer, throttled to once per item per request.
	 *
	 * @param string $type        Either 'plugins' or 'themes'.
	 * @param string $key         Plugin file resp. theme slug.
	 * @param string $new_version Offered version.
	 * @return void
	 */
	private static function log_restore( $type, $key, $new_version ) {
		$item = $type . ':' . $key;
		if ( isset( self::$logged_restores[ $item ] ) ) {
			return;
		}
		self::$logged_restores[ $item ] = true;

		WebChangeDetector_Admin_Utils::log_error(
			'Restored dropped update offer ' . $item . ' (new version ' . $new_version . ') during the auto-update run window.',
			'update_offer_guard',
			'debug'
		);
	}
}
