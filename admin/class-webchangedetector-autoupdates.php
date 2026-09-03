<?php
/**
 * Title: WebChange Detector Auto Update Feature
 * Description: Check your website on auto updates visually and see what changed.
 * Version: 1.0
 *
 * @package    WebChangeDetector
 */

namespace WebChangeDetector;

new WebChangeDetector_Autoupdates();

/**
 * Checks on wp auto updates
 *
 * @package    WebChangeDetector
 */
class WebChangeDetector_Autoupdates {


	/** Wp auto update lock name.
	 *
	 * @var string
	 */
	private string $lock_name = 'auto_updater.lock';

	/** Option name for the "always allow WordPress core security updates" toggle.
	 *
	 * Write-through mirror of the API value auto_update_settings.allow_core_security_updates:
	 * the settings save writes both, the hourly sync mirrors dashboard changes back into
	 * the option. Readers use only the option (offline-safe in wp-cron). Default ON when
	 * unset (get_option default true).
	 */
	const OPTION_ALLOW_CORE_SECURITY = 'wcd_allow_core_security_updates';

	/** Whether THIS request armed the core-security bypass filters.
	 *
	 * Read by automatic_updates_complete() (same instance, same request) to
	 * record the restricted core install without starting the WCD pipeline.
	 *
	 * @var bool
	 */
	private bool $core_security_bypass = false;

	/** Group ID for on-demand checks (property name kept for backwards compatibility).
	 *
	 * Initialized to '' so the constructor's early return (missing groups option,
	 * e.g. during onboarding) cannot leave the typed property uninitialized,
	 * which would fatal on every cron pass in validate_wcd_configuration().
	 *
	 * @var string
	 */
	public string $manual_group_id = '';

	/** Group ID for monitoring checks.
	 *
	 * @var string
	 */
	public string $monitoring_group_id = '';

	/** Per-request cache for the auto-update settings from the API.
	 *
	 * @var array|null
	 */
	private static $auto_update_settings_cache = null;

	/**
	 * Plugin constructor.
	 */
	public function __construct() {

		$this->set_defines();

		// Add webhook endpoint for triggering cron jobs (always available for manual triggers).
		add_action( 'init', array( $this, 'handle_webhook_trigger' ), 5 );

		// On a multisite Subsite, the network main site orchestrates auto-update
		// pre/post screenshots for the whole network in a single API batch. We
		// skip all hook + cron registration here so the Subsite's WP-cron run
		// never tries to trigger its own pre/post pipeline (which only ever
		// captured this one site's URLs anyway, leaving the rest of the
		// network unprotected — the bug FEAT-16 fixes). State (PRE/POST/RUNNING/
		// auto_updater.lock) lives naturally in the orchestrator's wp_options
		// because Hook-Gating ensures only the main-site cron writes them — no
		// shared-option / wp_sitemeta wrapper needed.
		if ( WebChangeDetector_Multisite::is_multisite_subsite() ) {
			// Orphan cron events from previous plugin versions (when subsites still
			// orchestrated their own pipeline). Both recurring AND single events.
			$orphan_hooks = array(
				'wcd_sync_auto_update_schedule',
				'wcd_check_update_completion',
				'wcd_wp_version_check',
				'wcd_cron_check_post_queues',
				'wp_maybe_auto_update', // Single-event reschedules we added.
			);
			foreach ( $orphan_hooks as $hook ) {
				if ( wp_next_scheduled( $hook ) ) {
					wp_clear_scheduled_hook( $hook );
				}
			}

			// Stale API webhook: previous-version subsites registered a cron-trigger
			// webhook so the API could ping them daily. With Hook-Gating that ping
			// goes nowhere — drop the registration so the API stops calling.
			$webhook_id = get_option( WCD_WORDPRESS_CRON );
			if ( $webhook_id ) {
				\WebChangeDetector\WebChangeDetector_API_V2::delete_webhook_v2( $webhook_id );
				delete_option( WCD_WORDPRESS_CRON );
			}
			return;
		}

		// Only register API-dependent hooks if we have an API token.
		$api_token = WebChangeDetector_Multisite::get_api_token();
		if ( ! empty( $api_token ) ) {
			// Register the complete hook in constructor to ensure it's always registered.
			add_action( 'automatic_updates_complete', array( $this, 'automatic_updates_complete' ), 10, 1 );

			// Fallback for when no updates are available.
			add_action( 'wcd_check_update_completion', array( $this, 'check_update_completion' ) );

			// Post updates.
			add_action( 'wcd_cron_check_post_queues', array( $this, 'wcd_cron_check_post_queues' ) );

			// Saving settings.
			add_action( 'wcd_save_update_group_settings', array( $this, 'wcd_save_update_group_settings' ) );

			// Backup cron job for checking for updates.
			add_action( 'wcd_wp_version_check', array( $this, 'wcd_wp_version_check' ) );

			// Hooking into the update process.
			add_action( 'wp_maybe_auto_update', array( $this, 'wp_maybe_auto_update' ), 5 );

			// Hourly sync with the API. This must stay HOURLY, never reduce it to daily:
			// (a) its re-pin of wp_version_check heals third-party cron displacement within 1h whenever the API is reachable;
			// (b) its stuck-process sweeper is the termination bound for the post-queue retry loop and the cleanup point
			// for the wcd_wordpress_cron webhook option; both degrade to 24h+ on a daily schedule;
			// (c) the gate values (window/weekdays/enabled) do NOT depend on this sync, every cron tick reads them fresh
			// from the API; the sync exists only for cron re-pinning, webhook maintenance, health status and the sweeper.
			add_action( 'wcd_sync_auto_update_schedule', array( $this, 'sync_auto_update_schedule_from_api' ) );
			if ( ! wp_next_scheduled( 'wcd_sync_auto_update_schedule' ) ) {
				wp_schedule_event( time(), 'hourly', 'wcd_sync_auto_update_schedule' );
			}
		} else {
			// Clear any existing scheduled events if no API token.
			if ( wp_next_scheduled( 'wcd_sync_auto_update_schedule' ) ) {
				wp_clear_scheduled_hook( 'wcd_sync_auto_update_schedule' );
			}
			if ( wp_next_scheduled( 'wcd_check_update_completion' ) ) {
				wp_clear_scheduled_hook( 'wcd_check_update_completion' );
			}
			if ( wp_next_scheduled( 'wcd_wp_version_check' ) ) {
				wp_clear_scheduled_hook( 'wcd_wp_version_check' );
			}
		}

		$wcd_groups = get_option( WCD_WEBSITE_GROUPS );
		if ( ! $wcd_groups ) {
			return;
		}
		$this->manual_group_id     = $wcd_groups[ WCD_MANUAL_DETECTION_GROUP ] ?? '';
		$this->monitoring_group_id = $wcd_groups[ WCD_AUTO_DETECTION_GROUP ] ?? '';
	}

	/**
	 * This is a backup cron job for checking for updates.
	 * We need to be careful not to interfere with an already running update process.
	 *
	 * Do not delete this cron as "redundant": if another plugin or the host reschedules
	 * or unschedules the native wp_version_check cron outside our allowed window, this
	 * is the only API-independent trigger left at the window start. The hourly sync's
	 * re-pin only runs when its API call succeeds, and the daily wordpress_single_call
	 * webhook only spawns ALREADY-DUE cron events via spawn_cron(), so neither can
	 * replace it.
	 *
	 * @return void
	 */
	public function wcd_wp_version_check() {
		// Check if pre-update screenshots are in progress. If so, we skip the version check.
		$pre_update_data = get_option( WCD_PRE_AUTO_UPDATE );
		// Note: Stuck process checking is now handled centrally in hourly sync.
		if ( $pre_update_data && isset( $pre_update_data['status'] ) ) {
			\WebChangeDetector\WebChangeDetector_Admin_Utils::log_error(
				'Skipping backup version check - pre-update screenshots in progress',
				'wcd_wp_version_check',
				'debug'
			);
			return;
		}

		\WebChangeDetector\WebChangeDetector_Admin_Utils::log_error(
			'Running backup wp_version_check',
			'wcd_wp_version_check',
			'debug'
		);
		wp_version_check();
	}


	/**
	 * Fires when wp auto updates are done.
	 *
	 * @param array $update_results Array of update results.
	 * @return void
	 * @throws \Exception If the update results are invalid.
	 */
	public function automatic_updates_complete( $update_results = array() ) {
		\WebChangeDetector\WebChangeDetector_Admin_Utils::log_error( 'Automatic Updates Complete. Running post-update stuff.', 'automatic_updates_complete', 'debug' );

		// Remove the backup complete checker.
		wp_clear_scheduled_hook( 'wcd_check_update_completion' );

		// Auto updates are done. So we ALWAYS remove the option, regardless of other conditions.
		delete_option( WCD_AUTO_UPDATES_RUNNING );

		// Also ensure lock is removed in case it got stuck.
		delete_option( $this->lock_name );

		// Idempotency guard: if a post-update batch is already in flight, never start a second one.
		$existing_post_update_data = get_option( WCD_POST_AUTO_UPDATE );
		if ( ! empty( $existing_post_update_data ) ) {
			\WebChangeDetector\WebChangeDetector_Admin_Utils::log_error(
				'Post-update workflow already running for batch ' . ( $existing_post_update_data['batch_id'] ?? 'unknown' ) . '. Skipping duplicate trigger.',
				'automatic_updates_complete',
				'debug'
			);
			return;
		}

		// Core security bypass: THIS request installed a minor core security release
		// without WCD checks (no pre batch exists). Record it in the history and stop:
		// core sends its own result email, and no WCD pipeline (post batch, mail,
		// cooldown) is involved.
		if ( $this->core_security_bypass ) {
			if ( ! empty( $update_results ) ) {
				$this->save_update_results( $update_results, null, 'security_update' );
			}
			return;
		}

		// We don't do anything here if wcd checks are disabled, or we don't have pre_auto_update option.
		$auto_update_settings = self::get_auto_update_settings();
		if ( ! array_key_exists( 'auto_update_checks_enabled', $auto_update_settings ) ) {
			\WebChangeDetector\WebChangeDetector_Admin_Utils::log_error( 'Skipping after update stuff as checks are disabled.', 'automatic_updates_complete', 'debug' );
			return;
		}

		$pre_update_data = get_option( WCD_PRE_AUTO_UPDATE );
		if ( ! $pre_update_data ) {
			\WebChangeDetector\WebChangeDetector_Admin_Utils::log_error( 'Skipping after update stuff as we don\'t have pre-update checks. This could happen if they were cleaned up due to timeout.', 'automatic_updates_complete', 'debug' );
			return;
		}

		// Clear all caches before taking post-update screenshots.
		WebChangeDetector_Cache_Clearer::clear_all();

		// Start the post-update screenshots.
		\WebChangeDetector\WebChangeDetector_Admin_Utils::log_error( 'Starting post-update screenshots and comparisons.', 'automatic_updates_complete', 'debug' );

		try {
			// Mirror the group selection used for pre-update so post screenshots cover
			// exactly the same sub-sites that were captured before the update ran.
			$group_ids = $this->collect_network_group_ids();
			if ( empty( $group_ids ) ) {
				\WebChangeDetector\WebChangeDetector_Admin_Utils::log_error( 'Auto-update checks were disabled mid-cycle (between pre and post) or no sites are enabled. Cleaning up pre-update state.', 'automatic_updates_complete', 'warning' );
				$this->cleanup_auto_update_run();
				return;
			}
			$response = \WebChangeDetector\WebChangeDetector_API_V2::take_screenshot_v2( $group_ids, 'post', 'auto_update' );
			\WebChangeDetector\WebChangeDetector_Admin_Utils::log_error( 'Post-Screenshot Response: ' . wp_json_encode( $response ), 'automatic_updates_complete', 'debug' );

			// Validate response structure.
			if ( empty( $response ) || ! isset( $response['batch'] ) ) {
				throw new \Exception( 'Invalid API response for post-update screenshots: missing batch ID' );
			}

			$post_update_data = array(
				'status'    => 'processing',
				'batch_id'  => $response['batch'],
				'timestamp' => time(), // Add timestamp for timeout detection.
			);
		} catch ( \Exception $e ) {
			\WebChangeDetector\WebChangeDetector_Admin_Utils::log_error(
				'Failed to start post-update screenshots: ' . $e->getMessage() . '. Cleaning up.',
				'automatic_updates_complete',
				'error'
			);

			// Log error for user visibility.
			$this->log_auto_update_error(
				'skip_error',
				array(
					'phase' => 'post_update_screenshots',
					'error' => $e->getMessage(),
				)
			);

			// Clean up pre-update data since we can't complete the comparison.
			$this->cleanup_auto_update_run();
			return;
		}

		update_option( WCD_POST_AUTO_UPDATE, $post_update_data, false );

		// Save the update results to options for display in frontend (only if we have results).
		if ( ! empty( $update_results ) && ! empty( $response['batch'] ) ) {
			$this->save_update_results( $update_results, $response['batch'] );
		}

		// Schedule the cron to check post-update queue status.
		$this->reschedule( 'wcd_cron_check_post_queues' );
		\WebChangeDetector\WebChangeDetector_Admin_Utils::log_error(
			'Scheduled wcd_cron_check_post_queues to check post-update screenshot status',
			'automatic_updates_complete',
			'debug'
		);

		// Add the batch id to the comparison batches. This is used to send the mail and for showing "Auto Update Checks" in the change detection page.
		$comparison_batches = get_option( WCD_AUTO_UPDATE_COMPARISON_BATCHES );
		if ( ! is_array( $comparison_batches ) ) {
			$comparison_batches = array();
		}
		$comparison_batches[] = $response['batch'];

		// Keep only the newest 30 batch ids (mirrors the history option cap). Batches older
		// than that merely lose their "Auto Update Checks" label in the listings. Store
		// non-autoloaded; the option used to grow unbounded and load on every request.
		$comparison_batches = array_slice( $comparison_batches, -30 );
		if ( function_exists( 'wp_set_option_autoload' ) ) {
			update_option( WCD_AUTO_UPDATE_COMPARISON_BATCHES, $comparison_batches, false );
			wp_set_option_autoload( WCD_AUTO_UPDATE_COMPARISON_BATCHES, false );
		} else {
			// Pre-WP-6.4: delete + add is the only deterministic way to drop the autoload flag.
			delete_option( WCD_AUTO_UPDATE_COMPARISON_BATCHES );
			update_option( WCD_AUTO_UPDATE_COMPARISON_BATCHES, $comparison_batches, false );
		}

		$this->wcd_cron_check_post_queues();
	}

	/**
	 * Cron for checking post_sc to be finished
	 *
	 * @return void
	 */
	public function wcd_cron_check_post_queues() {
		$post_sc_option = get_option( WCD_POST_AUTO_UPDATE );

		// Note: Stuck process cleanup is now handled centrally in hourly sync.
		// We just check if the option exists to proceed with queue checking.

		// Check if we still have the post_sc_option. If not, we already sent the mail.
		if ( ! $post_sc_option ) {
			\WebChangeDetector\WebChangeDetector_Admin_Utils::log_error( 'No post_sc_option found. So we already sent the mail.', 'wcd_cron_check_post_queues', 'debug' );
			return;
		}
		\WebChangeDetector\WebChangeDetector_Admin_Utils::log_error( 'Checking if post-update screenshots are done: ' . wp_json_encode( $post_sc_option ), 'wcd_cron_check_post_queues', 'debug' );
		$response = \WebChangeDetector\WebChangeDetector_API_V2::get_queues_v2( $post_sc_option['batch_id'], 'open,processing' );
		\WebChangeDetector\WebChangeDetector_Admin_Utils::log_error( 'Response: ' . wp_json_encode( $response ), 'wcd_cron_check_post_queues', 'debug' );

		// Validate the response before using it. api_v2() returns plain strings on failure.
		if ( ! is_array( $response ) || ! isset( $response['data'] ) || ! is_array( $response['data'] ) ) {
			$terminal_errors = array( 'unauthorized', 'No API token found', 'update plugin', 'not found' );
			if ( is_string( $response ) && in_array( $response, $terminal_errors, true ) ) {
				// Terminal error: retrying cannot succeed, so clean up the run right away. No mail.
				\WebChangeDetector\WebChangeDetector_Admin_Utils::log_error( 'Terminal API error while checking post-update queues: ' . $response . '. Cleaning up the auto-update run.', 'wcd_cron_check_post_queues', 'error' );
				$this->cleanup_auto_update_run();
				return;
			}

			// Transient error: retry in 30 seconds. The hourly stuck-sweeper bounds this retry loop.
			\WebChangeDetector\WebChangeDetector_Admin_Utils::log_error( 'Invalid API response while checking post-update queues. Retrying in 30 seconds.', 'wcd_cron_check_post_queues', 'warning' );
			$this->reschedule( 'wcd_cron_check_post_queues' );
			return;
		}

		// Check if the batch is done.
		if ( count( $response['data'] ) > 0 ) {
			// There are still open or processing queues. So we check again in a minute.
			\WebChangeDetector\WebChangeDetector_Admin_Utils::log_error( 'There are still open or processing queues. So we check again in a minute.', 'wcd_cron_check_post_queues', 'debug' );
			$this->reschedule( 'wcd_cron_check_post_queues' );
		} else {

			// Send the mail. The cleanup below must always run, even if the mail fails.
			try {
				$this->send_change_detection_mail( $post_sc_option );
			} catch ( \Throwable $e ) {
				\WebChangeDetector\WebChangeDetector_Admin_Utils::log_error( 'Failed to send change detection mail: ' . $e->getMessage(), 'wcd_cron_check_post_queues', 'error' );
			}

			// Cleanup wp_options, cron webhook and scheduled fallback check.
			$this->cleanup_auto_update_run();
		}
	}

	/**
	 * Delete all auto-update run state options, the API minute-webhook and the scheduled fallback check.
	 *
	 * @return void
	 */
	private function cleanup_auto_update_run() {
		// We don't need the webhook anymore.
		$webhook_id = get_option( WCD_WORDPRESS_CRON );
		if ( $webhook_id ) {
			\WebChangeDetector\WebChangeDetector_API_V2::delete_webhook_v2( $webhook_id );
			delete_option( WCD_WORDPRESS_CRON );
		}

		// Cleanup wp_options.
		delete_option( WCD_PRE_AUTO_UPDATE );
		delete_option( WCD_POST_AUTO_UPDATE );
		delete_option( WCD_AUTO_UPDATES_RUNNING );
		delete_option( WCD_AUTO_UPDATE_TRIGGERED_TIME );

		// Clean up scheduled fallback check.
		wp_clear_scheduled_hook( 'wcd_check_update_completion' );

		// Deactivate the update-offer restore in this request too: the run
		// option is gone, but the guard caches its lookup per request.
		WebChangeDetector_Update_Offer_Guard::reset();
	}

	/**
	 * Set lock to prevent wp from updating
	 *
	 * @return void
	 */
	public function set_lock() {
		\WebChangeDetector\WebChangeDetector_Admin_Utils::log_error( 'Setting Lock', 'set_lock', 'debug' );
		update_option( $this->lock_name, time() - HOUR_IN_SECONDS + MINUTE_IN_SECONDS );
	}

	/**
	 * Delete lock
	 *
	 * @return void
	 */
	public function delete_lock() {
		delete_option( $this->lock_name );
	}

	/**
	 * Set the lock unless a WordPress auto-update is currently running.
	 *
	 * The skip branches (cooldown / weekday / time window) can fire from a cron tick
	 * while core's updater holds the real auto_updater.lock. Overwriting that live
	 * lock with our backdated one mid-update could let a second updater start.
	 * No stalling branch runs while WCD_AUTO_UPDATES_RUNNING is set, so skipping is safe.
	 *
	 * @return void
	 */
	private function set_lock_if_not_updating() {
		if ( get_option( WCD_AUTO_UPDATES_RUNNING ) ) {
			\WebChangeDetector\WebChangeDetector_Admin_Utils::log_error( 'Skipping lock: WordPress auto-updates are currently running.', 'set_lock', 'debug' );
			return;
		}
		$this->set_lock();
	}

	/**
	 * Check if concurrent execution should be prevented.
	 *
	 * @return bool True if should skip execution, false otherwise.
	 */
	private function should_skip_concurrent_execution() {
		$execution_lock = get_transient( 'wcd_update_check_running' );
		if ( $execution_lock && ( time() - $execution_lock ) < 30 ) {
			\WebChangeDetector\WebChangeDetector_Admin_Utils::log_error(
				'Skipping - another update check is already running (started ' . ( time() - $execution_lock ) . ' seconds ago).',
				'wp_maybe_auto_update',
				'debug'
			);
			return true;
		}
		return false;
	}

	/**
	 * Check if auto-updates were already run recently.
	 *
	 * Uses WCD_LAST_AUTO_UPDATE_CHECK_TIME to prevent repeated workflow starts within 12 hours,
	 * regardless of whether the previous attempt succeeded or failed. This prevents infinite
	 * retry loops when updates fail due to plugin incompatibilities or other issues.
	 *
	 * @return bool True if should skip due to cooldown, false otherwise.
	 */
	private function is_within_cooldown_period() {

		$last_check_time = get_option( WCD_LAST_AUTO_UPDATE_CHECK_TIME );
		if ( $last_check_time && $last_check_time + 12 * HOUR_IN_SECONDS > time() ) {
			$next_allowed = $last_check_time + 12 * HOUR_IN_SECONDS;
			\WebChangeDetector\WebChangeDetector_Admin_Utils::log_error(
				sprintf(
					'Auto update workflow already started at %s (%s ago). Next check allowed at %s (%s from now). Skipping to prevent retry loop.',
					gmdate( 'Y-m-d H:i:s', $last_check_time ),
					human_time_diff( $last_check_time, time() ),
					gmdate( 'Y-m-d H:i:s', $next_allowed ),
					human_time_diff( time(), $next_allowed )
				),
				'wp_maybe_auto_update',
				'debug'
			);

			return true;
		}
		\WebChangeDetector\WebChangeDetector_Admin_Utils::log_error( 'No cooldown period found or it has passed', 'wp_maybe_auto_update', 'debug' );
		return false;
	}

	/**
	 * Check if any WordPress updates are available.
	 *
	 * @return array|false Array with update info if updates available, false otherwise.
	 */
	private function check_for_available_updates() {
		// Snapshot the current update offers BEFORE the forced refresh below.
		// Premium updaters often register on admin_init only, so a non-admin
		// rebuild silently drops their offers; the Update Offer Guard
		// re-injects the snapshot on the read side for the whole run window.
		// At this point neither guard gate is active, so both reads return
		// the raw transients.
		$plugin_transient = get_site_transient( 'update_plugins' );
		$theme_transient  = get_site_transient( 'update_themes' );
		WebChangeDetector_Update_Offer_Guard::set_snapshot(
			is_object( $plugin_transient ) && isset( $plugin_transient->response ) && is_array( $plugin_transient->response ) ? $plugin_transient->response : array(),
			is_object( $theme_transient ) && isset( $theme_transient->response ) && is_array( $theme_transient->response ) ? $theme_transient->response : array()
		);

		// Force a fresh check for updates.
		wp_version_check();
		wp_update_plugins();
		wp_update_themes();

		$has_updates = array(
			'core'    => false,
			'plugins' => false,
			'themes'  => false,
			'total'   => 0,
		);

		// Check for core updates. The forced wp_version_check() above refreshed the
		// update_core transient, so the helper reads fresh data.
		$core_offer = $this->get_core_autoupdate_offer();
		if ( $core_offer ) {
			$has_updates['core'] = true;
			++$has_updates['total'];
			\WebChangeDetector\WebChangeDetector_Admin_Utils::log_error(
				'Core auto-update available: ' . $core_offer->version,
				'check_for_available_updates',
				'debug'
			);
		}

		// Check for plugin updates (only those WordPress will actually auto-install).
		$plugin_updates = get_site_transient( 'update_plugins' );
		if ( $plugin_updates && ! empty( $plugin_updates->response ) ) {
			$auto_updatable_count = 0;
			foreach ( $plugin_updates->response as $item ) {
				if ( $this->wp_would_auto_update_item( 'plugin', $item ) ) {
					++$auto_updatable_count;
				}
			}
			if ( $auto_updatable_count > 0 ) {
				$has_updates['plugins'] = true;
				$has_updates['total']  += $auto_updatable_count;
				\WebChangeDetector\WebChangeDetector_Admin_Utils::log_error(
					'Plugin updates with auto-update enabled: ' . $auto_updatable_count . ' of ' . count( $plugin_updates->response ) . ' available',
					'check_for_available_updates',
					'debug'
				);
			}
		}

		// Check for theme updates (only those WordPress will actually auto-install).
		$theme_updates = get_site_transient( 'update_themes' );
		if ( $theme_updates && ! empty( $theme_updates->response ) ) {
			$auto_updatable_count = 0;
			foreach ( $theme_updates->response as $item ) {
				if ( $this->wp_would_auto_update_item( 'theme', (object) $item ) ) {
					++$auto_updatable_count;
				}
			}
			if ( $auto_updatable_count > 0 ) {
				$has_updates['themes'] = true;
				$has_updates['total'] += $auto_updatable_count;
				\WebChangeDetector\WebChangeDetector_Admin_Utils::log_error(
					'Theme updates with auto-update enabled: ' . $auto_updatable_count . ' of ' . count( $theme_updates->response ) . ' available',
					'check_for_available_updates',
					'debug'
				);
			}
		}

		if ( $has_updates['total'] > 0 ) {
			\WebChangeDetector\WebChangeDetector_Admin_Utils::log_error(
				'Total auto-updatable items: ' . $has_updates['total'],
				'check_for_available_updates',
				'info'
			);
			return $has_updates;
		}

		return false;
	}

	/**
	 * Find the pending core 'autoupdate' offer WordPress would install automatically.
	 *
	 * Side-effect-free: reads the update_core transient WITHOUT forcing a refresh.
	 * Callers must ensure the transient is fresh (check_for_available_updates() runs
	 * wp_version_check() first; the security bypass runs inside the same request
	 * that wp_version_check() fired wp_maybe_auto_update from).
	 *
	 * Only 'autoupdate' offers count (mirroring find_core_auto_update()); 'upgrade'
	 * offers (e.g. a pending major release) are manual-only.
	 *
	 * @return object|false The core update offer object, or false if none.
	 */
	private function get_core_autoupdate_offer() {
		$core_updates = get_site_transient( 'update_core' );
		if ( ! $core_updates || empty( $core_updates->updates ) ) {
			return false;
		}

		foreach ( $core_updates->updates as $update ) {
			if ( 'autoupdate' !== $update->response ) {
				continue;
			}

			// Mirror core's full decision via Core_Upgrader::should_update_to_version():
			// static and side-effect-free (reads WP_AUTO_UPDATE_CORE, the auto_update_core_*
			// options, the critical-failure lockout and the allow_*_auto_core_updates filters).
			// It covers cases the 'autoupdate' offer alone does not, e.g. WP_AUTO_UPDATE_CORE
			// set to false or disabled minor updates. Core only loads the upgrader classes in
			// its own priority-10 cron callback, after our priority-5 callback runs, so we
			// load them ourselves (core re-requires the same file moments later).
			require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
			if ( empty( $update->version ) || ! \Core_Upgrader::should_update_to_version( $update->version ) ) {
				\WebChangeDetector\WebChangeDetector_Admin_Utils::log_error(
					'Core auto-update offer ' . ( $update->version ?? 'unknown' ) . ' rejected by Core_Upgrader::should_update_to_version(). Not counting it.',
					'check_for_available_updates',
					'debug'
				);
				continue;
			}

			return $update;
		}

		return false;
	}

	/**
	 * Check whether a core update offer stays within the currently installed major.minor branch.
	 *
	 * A minor-branch offer (e.g. 6.8.2 while 6.8.1 is installed) is a security/maintenance
	 * release. Without this check, a pending MAJOR 'autoupdate' offer (WP_AUTO_UPDATE_CORE
	 * = true setups) would make the bypass skip the lock for weeks while the armed filters
	 * block the major install, so nothing would ever be installed in those passes.
	 *
	 * @param object $offer Core update offer object from the update_core transient.
	 * @return bool True when the offered version is in the installed major.minor branch.
	 */
	private function is_minor_core_offer( $offer ) {
		if ( empty( $offer->version ) ) {
			return false;
		}

		// wp_get_wp_version() exists since WP 6.7; the plugin supports 5.5+.
		$installed = function_exists( 'wp_get_wp_version' ) ? wp_get_wp_version() : $GLOBALS['wp_version'];

		$installed_branch = implode( '.', array_slice( explode( '.', $installed ), 0, 2 ) );
		$offer_branch     = implode( '.', array_slice( explode( '.', (string) $offer->version ), 0, 2 ) );

		return $installed_branch === $offer_branch;
	}

	/**
	 * Check whether the "always allow WordPress core security updates" option is enabled.
	 *
	 * Default ON when the option row does not exist (existing and new installs);
	 * a saved "off" is stored as '' and stays off.
	 *
	 * @return bool True when core security updates may bypass the WCD gates.
	 */
	private function is_core_security_bypass_enabled() {
		return (bool) get_option( self::OPTION_ALLOW_CORE_SECURITY, true );
	}

	/**
	 * Arm the one-request filters for a restricted core-security-only update pass.
	 *
	 * Registered at priority 5 of wp_maybe_auto_update, consumed by core's
	 * priority-10 callback in the SAME request; nothing is persisted. Plugins,
	 * themes, major/dev core and non-core translations are blocked, so core's
	 * WP_Automatic_Updater::run() installs only the minor core update and its
	 * core-type language packs.
	 *
	 * @return void
	 */
	private function arm_core_security_filters() {
		add_filter( 'auto_update_plugin', '__return_false', PHP_INT_MAX );
		add_filter( 'auto_update_theme', '__return_false', PHP_INT_MAX );
		add_filter( 'allow_major_auto_core_updates', '__return_false', PHP_INT_MAX );
		add_filter( 'allow_dev_auto_core_updates', '__return_false', PHP_INT_MAX );
		add_filter( 'auto_update_translation', array( $this, 'filter_translation_core_only' ), PHP_INT_MAX, 2 );
	}

	/**
	 * Filter callback: allow only core-type translation updates.
	 *
	 * Translation offers carry a type of core, plugin or theme. During a
	 * core-security bypass pass only the core language pack may install.
	 *
	 * @param bool|mixed $update Whether to update the translation.
	 * @param object     $item   Translation update offer item.
	 * @return bool|mixed False for non-core translations, the incoming value otherwise.
	 */
	public function filter_translation_core_only( $update, $item ) {
		return ( isset( $item->type ) && 'core' === $item->type ) ? $update : false;
	}

	/**
	 * Let a pending minor core security update through a blocking gate, without WCD checks.
	 *
	 * Called at the gate points of wp_maybe_auto_update() (cooldown, weekday, time
	 * window) right before they would set the backdated lock. When all conditions
	 * hold, the lock is NOT set and the one-request filters are armed instead, so
	 * core's priority-10 callback runs a restricted core-only pass.
	 *
	 * Conditions: option enabled; NO active WCD run state (WCD_PRE_AUTO_UPDATE,
	 * WCD_POST_AUTO_UPDATE, WCD_AUTO_UPDATES_RUNNING all empty, so a WCD-managed
	 * run, including the pre option's 'done' phase, is never interfered with);
	 * a minor-branch core 'autoupdate' offer is pending.
	 *
	 * Deliberately touches neither the lock (a backdated lock from an earlier
	 * blocked pass is stale for core's create_lock() after 60 seconds) nor
	 * WCD_LAST_AUTO_UPDATE_CHECK_TIME (the cooldown protects paid batches; the
	 * bypass starts none).
	 *
	 * @param string $reason Gate name for logging (e.g. 'cooldown', 'weekday', 'time_window').
	 * @return bool True when the bypass was armed and the caller must return without locking.
	 */
	private function maybe_bypass_for_core_security( $reason ) {
		if ( ! $this->is_core_security_bypass_enabled() ) {
			return false;
		}

		if ( get_option( WCD_PRE_AUTO_UPDATE ) || get_option( WCD_POST_AUTO_UPDATE ) || get_option( WCD_AUTO_UPDATES_RUNNING ) ) {
			return false;
		}

		$offer = $this->get_core_autoupdate_offer();
		if ( ! $offer || ! $this->is_minor_core_offer( $offer ) ) {
			return false;
		}

		$this->arm_core_security_filters();
		set_transient( 'wcd_core_security_bypass', time(), MINUTE_IN_SECONDS );
		$this->core_security_bypass = true;

		\WebChangeDetector\WebChangeDetector_Admin_Utils::log_error(
			'Core security bypass armed at gate "' . $reason . '": letting core install minor update ' . $offer->version . ' without WCD checks.',
			'wp_maybe_auto_update',
			'info'
		);

		return true;
	}

	/**
	 * Replicate WordPress core's per-item auto-update decision for plugins and themes.
	 *
	 * Mirrors the item rule in WP_Automatic_Updater::should_update()
	 * (wp-admin/includes/class-wp-automatic-updater.php) WITHOUT its side effects
	 * (filesystem credential check, core notification mails): the wp.org-forced
	 * `$item->autoupdate` flag OR the `auto_update_{$type}s` site option, minus
	 * `disable_autoupdate`, passed through the `auto_update_{$type}` filter.
	 * Counting only the site option (old behavior) missed forced security updates
	 * and filter-managed setups, and counted filter-blocked items.
	 *
	 * @param string $type Either 'plugin' or 'theme'.
	 * @param object $item Update offer item from the update_{plugins|themes} transient.
	 * @return bool True when WordPress would auto-install this item.
	 */
	private function wp_would_auto_update_item( $type, $item ) {
		$update = ! empty( $item->autoupdate );

		// wp_is_auto_update_enabled_for_type() lives in wp-admin/includes/update.php, which core
		// only loads in its own priority-10 cron callback, AFTER our priority-5 callback runs.
		// Load it ourselves (core loads the superset moments later in the same request); keep the
		// function_exists guard as belt-and-braces, defaulting to enabled like core.
		require_once ABSPATH . 'wp-admin/includes/update.php';
		$type_enabled = ! function_exists( 'wp_is_auto_update_enabled_for_type' ) || wp_is_auto_update_enabled_for_type( $type );
		if ( ! $update && $type_enabled ) {
			$enabled_items = (array) get_site_option( "auto_update_{$type}s", array() );
			$update        = in_array( $item->{$type} ?? '', $enabled_items, true );
		}

		// The disable_autoupdate flag overrides any user choice, but filters still apply.
		if ( ! empty( $item->disable_autoupdate ) ) {
			$update = false;
		}

		/** This filter is documented in wp-admin/includes/class-wp-automatic-updater.php */
		return (bool) apply_filters( "auto_update_{$type}", $update, $item );
	}

	/**
	 * Read a WP-cron timestamp from the orchestrator site's context.
	 *
	 * On a multisite-network sub-site the orchestrator is the main site;
	 * the sub-site's own wp_version_check stays at WP core's default random
	 * hour because the autoupdates constructor early-returns and never
	 * reschedules it to the inherited window. Reading it locally would yield
	 * a time-of-day that cannot match the inherited weekday/time window.
	 *
	 * @param string $hook Cron hook name (e.g. 'wp_version_check').
	 * @return int|false Next scheduled timestamp, or false if not scheduled.
	 */
	public static function get_orchestrator_cron_time( $hook ) {
		if ( WebChangeDetector_Multisite::is_multisite_subsite() ) {
			return WebChangeDetector_Multisite::with_blog(
				get_main_site_id(),
				static function () use ( $hook ) {
					return wp_next_scheduled( $hook );
				}
			);
		}
		return wp_next_scheduled( $hook );
	}

	/**
	 * Calculate the next time auto-updates will actually run based on weekday settings.
	 *
	 * @return int|false Unix timestamp of next auto-update run, or false if not scheduled.
	 */
	public static function get_next_auto_update_time() {
		$auto_update_settings = self::get_auto_update_settings();

		if ( ! $auto_update_settings ||
			! array_key_exists( 'auto_update_checks_enabled', $auto_update_settings ) ||
			empty( $auto_update_settings['auto_update_checks_enabled'] ) ) {
			return false;
		}

		// Get both cron timestamps. Another plugin or host may reschedule wp_version_check
		// to a time outside our window, so we also check our backup cron.
		$wp_check_time  = self::get_orchestrator_cron_time( 'wp_version_check' );
		$wcd_check_time = self::get_orchestrator_cron_time( 'wcd_wp_version_check' );

		// Skip if neither cron is scheduled.
		if ( ! $wp_check_time && ! $wcd_check_time ) {
			return false;
		}

		// Get enabled weekdays.
		$enabled_weekdays = array();
		$weekdays         = array( 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday' );
		foreach ( $weekdays as $weekday ) {
			$key = 'auto_update_checks_' . $weekday;
			if ( array_key_exists( $key, $auto_update_settings ) && ! empty( $auto_update_settings[ $key ] ) ) {
				$enabled_weekdays[] = $weekday;
			}
		}

		// If no weekdays are enabled, auto-updates won't run.
		if ( empty( $enabled_weekdays ) ) {
			return false;
		}

		// Check both crons and return the earliest match.
		$candidates = array();

		if ( $wp_check_time ) {
			$match = self::find_next_matching_cron_time( $wp_check_time, $auto_update_settings );
			if ( $match ) {
				$candidates[] = $match;
			}
		}

		if ( $wcd_check_time ) {
			$match = self::find_next_matching_cron_time( $wcd_check_time, $auto_update_settings );
			if ( $match ) {
				$candidates[] = $match;
			}
		}

		return $candidates ? min( $candidates ) : false;
	}

	/**
	 * Find the next cron execution time that falls within the enabled weekdays and time window.
	 *
	 * Uses evaluate_schedule_window() so the prediction applies exactly the same
	 * site-local weekday + window rule as the gate in wp_maybe_auto_update().
	 *
	 * @param int   $next_cron_time       Unix timestamp of the next scheduled cron event.
	 * @param array $auto_update_settings Auto-update settings.
	 * @return int|false Unix timestamp of next matching time, or false if none found.
	 */
	private static function find_next_matching_cron_time( $next_cron_time, $auto_update_settings ) {
		$check_time        = $next_cron_time;
		$max_days_to_check = 8; // Check up to a week ahead plus one day for safety.

		for ( $i = 0; $i < $max_days_to_check; $i++ ) {
			$schedule = self::evaluate_schedule_window( $check_time, $auto_update_settings );
			if ( $schedule['weekday_allowed'] && $schedule['in_window'] ) {
				return $check_time;
			}

			// Move to the next day's scheduled time.
			// WordPress typically schedules wp_version_check twice daily.
			// We need to find the next scheduled occurrence.
			$check_time = $check_time + DAY_IN_SECONDS;

			// Get the actual next scheduled time after this point.
			$crons      = _get_cron_array();
			$next_found = false;
			foreach ( $crons as $timestamp => $cron ) {
				if ( $timestamp > $check_time - HOUR_IN_SECONDS && $timestamp < $check_time + HOUR_IN_SECONDS ) {
					if ( isset( $cron['wp_version_check'] ) || isset( $cron['wcd_wp_version_check'] ) ) {
						$check_time = $timestamp;
						$next_found = true;
						break;
					}
				}
			}

			// If we couldn't find a scheduled check around this time, estimate it.
			if ( ! $next_found ) {
				// Use the original time of day.
				$original_hour = gmdate( 'H:i:s', $next_cron_time );
				$next_date     = gmdate( 'Y-m-d', $check_time );
				$check_time    = strtotime( $next_date . ' ' . $original_hour . ' GMT' );
			}
		}

		// If we couldn't find a valid time in the next week, return false.
		return false;
	}

	/**
	 * Check if WCD auto-update checks are properly configured and at least one
	 * site participates.
	 *
	 * Single-site / per-site activation: gated by this site's own
	 * `auto_update_checks_enabled` toggle.
	 *
	 * Multisite-network: gated by the aggregated participants list across all
	 * sub-sites + the main site. Main toggle off + at least one sub-site on
	 * still passes — orchestration runs and screenshots only the participating
	 * sub-sites. The schedule (when to run, today's allowed weekdays, time
	 * window) always comes from the main site's settings, returned here.
	 *
	 * @return array|false Auto-update settings or false if not configured / no participants.
	 */
	private function validate_wcd_configuration() {
		$auto_update_settings = self::get_auto_update_settings();

		// Check if we have settings and a group ID on the orchestrator site.
		if ( ! $auto_update_settings || ! $this->manual_group_id ) {
			\WebChangeDetector\WebChangeDetector_Admin_Utils::log_error(
				'Running auto updates without checks. Don\'t have a group_id or auto update settings.',
				'wp_maybe_auto_update',
				'debug'
			);
			return false;
		}

		// Anyone opted in? On single-site this is just this site's toggle; on
		// multisite-network this is "any registered site has participate=on".
		if ( empty( $this->collect_network_group_ids() ) ) {
			\WebChangeDetector\WebChangeDetector_Admin_Utils::log_error(
				'Running auto updates without checks. No site has auto-update checks enabled.',
				'wp_maybe_auto_update',
				'debug'
			);
			return false;
		}

		return $auto_update_settings;
	}

	/**
	 * Evaluate the weekday + time window rule for a moment, in site-local time.
	 *
	 * The from/to times are stored in UTC (converted on save) and are converted
	 * back to site-local time here; the weekday checkboxes are stored as the
	 * user's LOCAL days. The weekday that counts is the weekday of the window
	 * START in site-local time: for a window wrapping local midnight (from > to,
	 * e.g. 23:00-01:00), a moment in the post-midnight portion belongs to the
	 * PREVIOUS local day's window. Both the gate (is_allowed_today /
	 * is_within_time_window) and the status-bar prediction
	 * (get_next_auto_update_time) must use this helper so they never diverge.
	 *
	 * @param int   $timestamp            Unix timestamp of the moment to evaluate.
	 * @param array $auto_update_settings Auto-update settings.
	 * @return array {
	 *     Evaluation result.
	 *
	 *     @type bool   $in_window       Whether the moment is inside the from/to window.
	 *     @type bool   $weekday_allowed Whether the local window-start weekday is enabled.
	 *     @type string $weekday         Local window-start weekday name (lowercase).
	 *     @type string $from_local      Window start in site-local H:i.
	 *     @type string $to_local        Window end in site-local H:i.
	 * }
	 */
	private static function evaluate_schedule_window( $timestamp, $auto_update_settings ) {
		require_once WCD_PLUGIN_DIR . 'admin/class-webchangedetector-timezone-helper.php';

		$utc_date   = gmdate( 'Y-m-d', $timestamp );
		$from_local = \WebChangeDetector\WebChangeDetector_Timezone_Helper::utc_to_site_time( $auto_update_settings['auto_update_checks_from'] ?? '00:00', $utc_date );
		$to_local   = \WebChangeDetector\WebChangeDetector_Timezone_Helper::utc_to_site_time( $auto_update_settings['auto_update_checks_to'] ?? '23:59', $utc_date );

		$local          = ( new \DateTimeImmutable( '@' . $timestamp ) )->setTimezone( wp_timezone() );
		$local_time_hm  = $local->format( 'H:i' );
		$window_weekday = strtolower( $local->format( 'l' ) );

		if ( $from_local <= $to_local ) {
			// Window within one local day: e.g., 09:00 to 17:00.
			$in_window = ( $local_time_hm >= $from_local && $local_time_hm <= $to_local );
		} else {
			// Window wrapping local midnight: e.g., 23:00 to 01:00.
			$in_window = ( $local_time_hm >= $from_local || $local_time_hm <= $to_local );
			if ( $local_time_hm <= $to_local ) {
				// Post-midnight portion: this moment belongs to the previous local day's window.
				$window_weekday = strtolower( $local->modify( '-1 day' )->format( 'l' ) );
			}
		}

		return array(
			'in_window'       => $in_window,
			'weekday_allowed' => ! empty( $auto_update_settings[ 'auto_update_checks_' . $window_weekday ] ),
			'weekday'         => $window_weekday,
			'from_local'      => $from_local,
			'to_local'        => $to_local,
		);
	}

	/**
	 * Check if auto-updates are allowed for today's weekday (site-local time).
	 *
	 * @param array $auto_update_settings Auto-update settings.
	 * @return bool True if allowed today, false otherwise.
	 */
	private function is_allowed_today( $auto_update_settings ) {
		$schedule = self::evaluate_schedule_window( time(), $auto_update_settings );

		if ( ! $schedule['weekday_allowed'] ) {
			\WebChangeDetector\WebChangeDetector_Admin_Utils::log_error(
				'Canceling auto updates: ' . $schedule['weekday'] . ' (site-local day of the window start) is disabled.',
				'wp_maybe_auto_update',
				'debug'
			);
			return false;
		}
		return true;
	}

	/**
	 * Check if current time is within the allowed time window (site-local time).
	 *
	 * @param array $auto_update_settings Auto-update settings.
	 * @return bool True if within time window, false otherwise.
	 */
	private function is_within_time_window( $auto_update_settings ) {
		$schedule = self::evaluate_schedule_window( time(), $auto_update_settings );

		if ( ! $schedule['in_window'] ) {
			$this->log_time_window_violation( $schedule['from_local'], $schedule['to_local'], $schedule['from_local'] > $schedule['to_local'] );
		}

		return $schedule['in_window'];
	}

	/**
	 * Log time window violation.
	 *
	 * @param string $from_time From time in site-local time.
	 * @param string $to_time To time in site-local time.
	 * @param bool   $spans_midnight Whether the time range spans midnight.
	 */
	private function log_time_window_violation( $from_time, $to_time, $spans_midnight ) {
		$message = sprintf(
			'Canceling auto updates: %s site time is not between %s and %s site time%s',
			wp_date( 'H:i' ),
			$from_time,
			$to_time,
			$spans_midnight ? ' (spans midnight)' : ''
		);
		\WebChangeDetector\WebChangeDetector_Admin_Utils::log_error(
			$message,
			'wp_maybe_auto_update',
			'debug'
		);
	}

	/**
	 * Collect manual_group_ids of all sites that should participate in this
	 * pre/post auto-update batch.
	 *
	 * Single-site / per-site activation: returns this site's manual group when
	 * its `auto_update_checks_enabled` toggle is on.
	 *
	 * Multisite-network: each registered site (main + subsites) decides
	 * independently via its own per-site `WCD_AUTO_UPDATE_PARTICIPATE` option
	 * (mirror of the toggle, written by
	 * `WebChangeDetector_Admin_Settings::update_manual_check_group_settings()`).
	 * No global "main must be on" gate — main off + subsites on still runs;
	 * only the participating subsites get screenshotted. The orchestrator only
	 * runs on the main site (subsites early-return in the constructor; WP core
	 * itself gates `WP_Automatic_Updater::run()` to
	 * `is_main_network() && is_main_site()` so subsites cannot trigger
	 * auto-updates anyway). The schedule fields (when to run) live on the main
	 * site and are read by the caller via `get_auto_update_settings()`.
	 *
	 * Returned UUIDs go into a single `take_screenshot_v2()` call. Empty array
	 * means nobody participates — orchestrator skips.
	 *
	 * @since 4.4.0
	 * @return string[] List of manual group UUIDs (deduplicated, may be empty).
	 */
	private function collect_network_group_ids() {
		// Single-site / per-site activation: only this site, gated by its own toggle.
		if ( ! WebChangeDetector_Multisite::is_multisite_active() ) {
			$settings = self::get_auto_update_settings();
			if ( empty( $settings['auto_update_checks_enabled'] ) || empty( $this->manual_group_id ) ) {
				return array();
			}
			return array( $this->manual_group_id );
		}

		// Multisite-network: aggregate from each registered site that opted in
		// via its per-site participate flag. `'number' => 0` makes the query
		// unbounded — default is 100 and would silently drop sub-sites on
		// networks larger than that.
		$group_ids = array();
		$sites     = get_sites(
			array(
				'archived' => 0,
				'spam'     => 0,
				'deleted'  => 0,
				'number'   => 0,
			)
		);

		foreach ( $sites as $site ) {
			$site_data = WebChangeDetector_Multisite::with_blog(
				(int) $site->blog_id,
				static function () {
					$groups       = get_option( 'wcd_website_groups', array() );
					$manual_group = is_array( $groups ) ? ( $groups[ WCD_MANUAL_DETECTION_GROUP ] ?? '' ) : '';
					return array(
						'participate'  => (bool) get_option( WCD_AUTO_UPDATE_PARTICIPATE, false ),
						'manual_group' => is_string( $manual_group ) ? $manual_group : '',
					);
				}
			);

			if ( ! empty( $site_data['participate'] ) && ! empty( $site_data['manual_group'] ) ) {
				$group_ids[] = $site_data['manual_group'];
			}
		}

		return array_values( array_unique( $group_ids ) );
	}

	/**
	 * Start pre-update screenshots.
	 *
	 * @return bool True if started successfully, false on error.
	 * @throws \Exception If the API response is invalid.
	 */
	private function start_pre_update_screenshots() {

		// Clear caches.
		WebChangeDetector_Cache_Clearer::clear_all();

		// Resolve participating groups. Single-site: just this site's group;
		// multisite-network: every enabled site's manual group in a single batch.
		// On the pre-path validate_wcd_configuration() runs first, so this empty
		// case is rare (network-wide all-disabled) but defensive.
		$group_ids = $this->collect_network_group_ids();
		if ( empty( $group_ids ) ) {
			\WebChangeDetector\WebChangeDetector_Admin_Utils::log_error(
				'No enabled sites for auto-update screenshots. Skipping.',
				'wp_maybe_auto_update',
				'debug'
			);
			delete_option( WCD_AUTO_UPDATES_RUNNING );
			delete_option( WCD_AUTO_UPDATE_TRIGGERED_TIME );
			return false;
		}

		// Take screenshots.
		try {
			$sc_response = \WebChangeDetector\WebChangeDetector_API_V2::take_screenshot_v2(
				$group_ids,
				'pre',
				'auto_update'
			);
			\WebChangeDetector\WebChangeDetector_Admin_Utils::log_error(
				'Pre update SC data: ' . wp_json_encode( $sc_response ),
				'wp_maybe_auto_update',
				'debug'
			);

			// Validate response.
			if ( empty( $sc_response ) || ! isset( $sc_response['batch'] ) ) {
				throw new \Exception( 'Invalid API response: missing batch ID' );
			}

			// Capture current plugin and theme versions before updates.
			$current_versions = $this->capture_current_versions();

			$option_data = array(
				'status'    => 'processing',
				'batch_id'  => esc_html( $sc_response['batch'] ),
				'timestamp' => time(),
				'versions'  => $current_versions,
				// Update offers snapshotted by check_for_available_updates();
				// persisted so the Update Offer Guard can restore dropped
				// offers in the later requests of this run window.
				'offers'    => WebChangeDetector_Update_Offer_Guard::get_snapshot(),
			);

			// Save state.
			\WebChangeDetector\WebChangeDetector_Admin_Utils::log_error(
				'Started taking screenshots and setting options',
				'wp_maybe_auto_update',
				'debug'
			);
			update_option( WCD_PRE_AUTO_UPDATE, $option_data, false );

			// Set lock to prevent WordPress updates.
			$this->set_lock();
			return true;

		} catch ( \Exception $e ) {
			\WebChangeDetector\WebChangeDetector_Admin_Utils::log_error(
				'Failed to start pre-update screenshots: ' . $e->getMessage(),
				'wp_maybe_auto_update',
				'error'
			);

			// Log error for user visibility.
			$this->log_auto_update_error(
				'skip_error',
				array(
					'phase' => 'pre_update_screenshots',
					'error' => $e->getMessage(),
				)
			);

			delete_option( WCD_AUTO_UPDATES_RUNNING );
			return false;
		}
	}

	/**
	 * Capture current versions of all plugins and themes before updates.
	 *
	 * @return array Array containing current plugin and theme versions.
	 */
	private function capture_current_versions() {
		$versions = array(
			'plugins' => array(),
			'themes'  => array(),
		);

		// Ensure get_plugins function is available.
		if ( ! function_exists( 'get_plugins' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		// Capture all plugin versions.
		if ( function_exists( 'get_plugins' ) ) {
			$all_plugins = get_plugins();
			foreach ( $all_plugins as $plugin_file => $plugin_data ) {
				if ( isset( $plugin_data['Version'] ) ) {
					$versions['plugins'][ $plugin_file ] = $plugin_data['Version'];
				}
			}
		}

		// Capture all theme versions.
		$all_themes = wp_get_themes();
		foreach ( $all_themes as $theme_slug => $theme ) {
			$versions['themes'][ $theme_slug ] = $theme->get( 'Version' );
		}

		\WebChangeDetector\WebChangeDetector_Admin_Utils::log_error(
			'Captured ' . count( $versions['plugins'] ) . ' plugin versions and ' . count( $versions['themes'] ) . ' theme versions before updates',
			'capture_current_versions',
			'debug'
		);

		return $versions;
	}

	/**
	 * Check if pre-update screenshots are ready.
	 *
	 * On every false return (still processing or transient API error) the single
	 * caller, wp_maybe_auto_update() Step 2, reschedules the check and re-sets
	 * the lock; this method only reports the status.
	 *
	 * @param array $pre_update_data Pre-update data with batch ID.
	 * @return bool True if ready, false if still processing.
	 * @throws \Exception If the API response is invalid.
	 */
	private function check_pre_update_screenshots_status( $pre_update_data ) {

		\WebChangeDetector\WebChangeDetector_Admin_Utils::log_error(
			'Checking if screenshots are ready',
			'wp_maybe_auto_update',
			'debug'
		);

		try {
			$response = \WebChangeDetector\WebChangeDetector_API_V2::get_queues_v2(
				$pre_update_data['batch_id'],
				'open,processing'
			);

			// Validate response.
			if ( ! is_array( $response ) || ! isset( $response['data'] ) ) {
				throw new \Exception( 'Invalid queue response structure' );
			}

			\WebChangeDetector\WebChangeDetector_Admin_Utils::log_error(
				'Queue: ' . wp_json_encode( $response ),
				'wp_maybe_auto_update',
				'debug'
			);

			// Check if queues are done.
			if ( count( $response['data'] ) === 0 ) {
				// Queues are done.
				return true;
			}

			// Still processing.
			\WebChangeDetector\WebChangeDetector_Admin_Utils::log_error(
				'SCs are not ready yet. Waiting for next cron run.',
				'wp_maybe_auto_update',
				'debug'
			);
			return false;

		} catch ( \Exception $e ) {
			\WebChangeDetector\WebChangeDetector_Admin_Utils::log_error(
				'Failed to check queue status: ' . $e->getMessage() . '. Will retry on next run.',
				'wp_maybe_auto_update',
				'warning'
			);

			// Log error for user visibility.
			$this->log_auto_update_error(
				'skip_error',
				array(
					'phase' => 'queue_status_check',
					'error' => $e->getMessage(),
				)
			);

			return false;
		}
	}

	/**
	 * Trigger WordPress auto-updates after pre-update screenshots are ready.
	 */
	private function trigger_wordpress_updates() {
		\WebChangeDetector\WebChangeDetector_Admin_Utils::log_error(
			'SCs are ready. Continuing with the updates.',
			'wp_maybe_auto_update',
			'debug'
		);

		// Mark auto-updates as running.
		update_option( WCD_AUTO_UPDATES_RUNNING, true );

		// Store timestamp when we triggered updates (for fallback check).
		update_option( WCD_AUTO_UPDATE_TRIGGERED_TIME, time() );

		// Remove the lock so WordPress can run updates.
		$this->delete_lock();
		\WebChangeDetector\WebChangeDetector_Admin_Utils::log_error(
			'Removed auto_updater.lock to allow WordPress to proceed with updates.',
			'wp_maybe_auto_update',
			'debug'
		);

		// Check if WordPress is installing.
		if ( wp_installing() ) {
			\WebChangeDetector\WebChangeDetector_Admin_Utils::log_error(
				'Cannot run updates: WordPress is currently installing.',
				'wp_maybe_auto_update',
				'debug'
			);
			delete_option( WCD_AUTO_UPDATES_RUNNING );
			delete_option( WCD_AUTO_UPDATE_TRIGGERED_TIME );
			return;
		}

		// Schedule a fallback check for when no updates are available.
		$this->schedule_update_completion_check();

		// Let WordPress handle the updates naturally.
		\WebChangeDetector\WebChangeDetector_Admin_Utils::log_error(
			'WordPress is not installing. Returning to trigger the wp hook of wp_maybe_auto_update.',
			'wp_maybe_auto_update',
			'debug'
		);
	}

	/**
	 * Schedule a check to see if WordPress updates have completed.
	 * This is a fallback for when automatic_updates_complete doesn't fire (no updates available).
	 */
	private function schedule_update_completion_check() {
		// Schedule check in 2 minutes to see if updates are done.
		wp_clear_scheduled_hook( 'wcd_check_update_completion' );
		wp_schedule_single_event( time() + 120, 'wcd_check_update_completion' );

		\WebChangeDetector\WebChangeDetector_Admin_Utils::log_error(
			'Scheduled fallback check for update completion in 2 minutes.',
			'schedule_update_completion_check',
			'debug'
		);
	}

	/**
	 * Check if WordPress updates have completed (with or without actual updates).
	 * This is called by cron as a fallback when automatic_updates_complete doesn't fire.
	 */
	public function check_update_completion() {
		// Check if we're still waiting for updates.
		if ( ! get_option( WCD_AUTO_UPDATES_RUNNING ) ) {
			\WebChangeDetector\WebChangeDetector_Admin_Utils::log_error(
				'Update completion check: Updates are not marked as running. Nothing to do.',
				'check_update_completion',
				'debug'
			);
			return;
		}

		// Logging for Checking for update completion.
		\WebChangeDetector\WebChangeDetector_Admin_Utils::log_error(
			'Checking for update completion',
			'check_update_completion',
			'debug'
		);

		// Check if the auto_updater.lock still exists.
		$lock = get_option( $this->lock_name );

		if ( $lock ) {
			// Lock still exists, WordPress might still be checking/updating.
			$lock_age = time() - $lock;

			// WordPress uses 1 hour as lock timeout, so if it's older, it's stuck.
			if ( $lock_age > HOUR_IN_SECONDS ) {
				\WebChangeDetector\WebChangeDetector_Admin_Utils::log_error(
					'Update completion check: Lock is stuck (age: ' . $lock_age . ' seconds). Treating as completed.',
					'check_update_completion',
					'warning'
				);
				// Treat as completed and clean up.
				delete_option( $this->lock_name );
				$this->handle_no_updates_scenario();
			} else {
				\WebChangeDetector\WebChangeDetector_Admin_Utils::log_error(
					'Update completion check: Lock still exists (age: ' . $lock_age . ' seconds). Updates are still running.',
					'check_update_completion',
					'debug'
				);
				// Check again in 1 minute.
				wp_schedule_single_event( time() + 60, 'wcd_check_update_completion' );
			}
		} else {
			// No lock means WordPress finished checking (with or without updates).
			\WebChangeDetector\WebChangeDetector_Admin_Utils::log_error(
				'Update completion check: No lock found. WordPress finished with updates.',
				'check_update_completion',
				'debug'
			);

			// Idempotency guard: the post-update workflow already started (automatic_updates_complete fired).
			$post_update_data = get_option( WCD_POST_AUTO_UPDATE );
			if ( ! empty( $post_update_data ) ) {
				\WebChangeDetector\WebChangeDetector_Admin_Utils::log_error(
					'Update completion check: Post-update workflow already running for batch ' . ( $post_update_data['batch_id'] ?? 'unknown' ) . '. Skipping duplicate trigger.',
					'check_update_completion',
					'debug'
				);
				return;
			}

			// Check how long ago we triggered the updates.
			$triggered_time = get_option( WCD_AUTO_UPDATE_TRIGGERED_TIME );
			if ( $triggered_time ) {
				$elapsed = time() - $triggered_time;
				\WebChangeDetector\WebChangeDetector_Admin_Utils::log_error(
					'Updates were triggered ' . $elapsed . ' seconds ago.',
					'check_update_completion',
					'debug'
				);

				// Too recent: the updater may still be starting up (the missing lock can be a race
				// with the 120s fallback delay). Re-check instead of treating the run as finished.
				if ( $elapsed < 120 ) {
					\WebChangeDetector\WebChangeDetector_Admin_Utils::log_error(
						'Update completion check: Updates were triggered only ' . $elapsed . ' seconds ago. Re-checking in 1 minute.',
						'check_update_completion',
						'debug'
					);
					wp_schedule_single_event( time() + 60, 'wcd_check_update_completion' );
					return;
				}
			}

			// Handle the case where no updates were available.
			$this->handle_no_updates_scenario();
		}
	}

	/**
	 * Handle the scenario where WordPress checked but found no updates.
	 * This mimics what automatic_updates_complete would do.
	 */
	private function handle_no_updates_scenario() {
		\WebChangeDetector\WebChangeDetector_Admin_Utils::log_error(
			'No updates were available. Running post-update workflow anyway.',
			'handle_no_updates_scenario',
			'info'
		);

		// Clean up the triggered time.
		delete_option( WCD_AUTO_UPDATE_TRIGGERED_TIME );

		// Call the same logic as automatic_updates_complete.
		// This ensures we complete the workflow even when no updates happened.
		$this->automatic_updates_complete();
	}

	/** Reset next cron run of wp_version_check to our auto_update_checks_from.
	 *
	 * @param array $group_settings Array of group settings (auto_update_checks_* keys).
	 * @return void
	 */
	public function wcd_save_update_group_settings( $group_settings = array() ) {
		$auto_update_settings = self::get_auto_update_settings();

		// We only need to setup the webhook if auto update checks are enabled. Prefer the
		// passed payload for the gate (fresh from the save/sync path); fall back to the
		// cached settings otherwise. The WCD_AUTO_UPDATES_ENABLED define override always wins.
		if ( is_array( $group_settings ) && array_key_exists( 'auto_update_checks_enabled', $group_settings ) ) {
			$checks_enabled = ! empty( $group_settings['auto_update_checks_enabled'] );
		} else {
			$checks_enabled = ! empty( $auto_update_settings['auto_update_checks_enabled'] );
		}
		if ( defined( 'WCD_AUTO_UPDATES_ENABLED' ) && true === WCD_AUTO_UPDATES_ENABLED ) {
			$checks_enabled = true;
		}
		if ( ! $checks_enabled ) {
			return;
		}

		// Get the time in UTC from API.
		if ( ! empty( $group_settings['auto_update_checks_from'] ) ) {
			$auto_update_checks_from_utc = $group_settings['auto_update_checks_from'] ?? '00:00';
		} else {
			$auto_update_checks_from_utc = $auto_update_settings['auto_update_checks_from'] ?? '00:00';
		}

		// IMPORTANT: The time from API is in UTC and represents when the user wants.
		// the check to run IN THEIR LOCAL TIME.
		// Example: User wants checks at 09:00 local time (EST).
		// - User enters: 09:00.
		// - We convert and save to API: 14:00 UTC (09:00 + 5 hours).
		// - API returns: 14:00 UTC.
		// - We schedule cron for: 14:00 UTC.
		// - Cron runs at: 14:00 UTC which is 09:00 EST (correct!).

		// Create DateTime for today at the scheduled UTC time.
		\WebChangeDetector\WebChangeDetector_Admin_Utils::log_error( 'Auto update checks "from" time from API (UTC): ' . $auto_update_checks_from_utc, 'wcd_save_update_group_settings', 'debug' );
		$today_utc              = gmdate( 'Y-m-d' );
		$scheduled_datetime_utc = $today_utc . ' ' . $auto_update_checks_from_utc . ':00';
		// Use strtotime with explicit UTC timezone to ensure correct parsing.
		$should_next_run_gmt = strtotime( $scheduled_datetime_utc . ' UTC' );

		// If the next run is in the past, we skip to the next day.
		if ( $should_next_run_gmt < time() ) {
			$should_next_run_gmt = $should_next_run_gmt + DAY_IN_SECONDS;
		}

		// Log for debugging.
		require_once WCD_PLUGIN_DIR . 'admin/class-webchangedetector-timezone-helper.php';
		$site_time = \WebChangeDetector\WebChangeDetector_Timezone_Helper::utc_to_site_time( $auto_update_checks_from_utc );

		\WebChangeDetector\WebChangeDetector_Admin_Utils::log_error(
			sprintf(
				'Scheduling wp_version_check - API stores as %s UTC, Scheduling cron for %s UTC (%s local)',
				$auto_update_checks_from_utc,
				gmdate( 'Y-m-d H:i:s', $should_next_run_gmt ),
				$site_time
			),
			'wcd_save_update_group_settings',
			'debug'
		);

		// Get currently scheduled times.
		$current_wp_version_check     = wp_next_scheduled( 'wp_version_check' );
		$current_wcd_wp_version_check = wp_next_scheduled( 'wcd_wp_version_check' );

		// Only reschedule if the time is different (allow 60 second tolerance to avoid constant rescheduling).
		$wp_version_check_needs_reschedule     = ! $current_wp_version_check || abs( $current_wp_version_check - $should_next_run_gmt ) > 60;
		$wcd_wp_version_check_needs_reschedule = ! $current_wcd_wp_version_check || abs( $current_wcd_wp_version_check - ( $should_next_run_gmt + 1 ) ) > 60;

		if ( $wp_version_check_needs_reschedule ) {
			// Clear and reschedule the WordPress update check crons.
			wp_clear_scheduled_hook( 'wp_version_check' );
			wp_schedule_event( $should_next_run_gmt, 'twicedaily', 'wp_version_check' );
			\WebChangeDetector\WebChangeDetector_Admin_Utils::log_error( 'Rescheduled wp_version_check', 'wcd_save_update_group_settings', 'debug' );
		} else {
			\WebChangeDetector\WebChangeDetector_Admin_Utils::log_error( 'Skipped rescheduling wp_version_check - already scheduled for correct time', 'wcd_save_update_group_settings', 'debug' );
		}

		if ( $wcd_wp_version_check_needs_reschedule ) {
			// Backup cron in case something else changes the wp_version_check cron. We add 1 second to let it run 2nd.
			wp_clear_scheduled_hook( 'wcd_wp_version_check' );
			wp_schedule_event( $should_next_run_gmt + 1, 'daily', 'wcd_wp_version_check' );
			\WebChangeDetector\WebChangeDetector_Admin_Utils::log_error( 'Rescheduled wcd_wp_version_check', 'wcd_save_update_group_settings', 'debug' );
		} else {
			\WebChangeDetector\WebChangeDetector_Admin_Utils::log_error( 'Skipped rescheduling wcd_wp_version_check - already scheduled for correct time', 'wcd_save_update_group_settings', 'debug' );
		}

		// Create our external webhook url for checking for updates daily.
		$webhook_url = add_query_arg(
			array(
				'wcd_action' => WCD_TRIGGER_WP_VERSION_CHECK,
				'key'        => $this->get_or_create_webhook_key(),
			),
			site_url()
		);

		// Set the webhook to expire at the next run. Expires is the next and only run time for this webhook.
		$expires_at     = $should_next_run_gmt + MINUTE_IN_SECONDS;
		$expires_at_gmt = gmdate( 'Y-m-d H:i:s', $expires_at );

		// Stored value is an array with the id and the last-sent url + expiry
		// (legacy installs may still hold a plain id string).
		$stored_webhook = get_transient( 'wcd_single_call_webhook_id' );
		$webhook_id     = is_array( $stored_webhook ) ? ( $stored_webhook['id'] ?? '' ) : $stored_webhook;

		if ( $webhook_id ) {
			// This method runs hourly via the schedule sync, but url + expiry only change
			// about once a day. Skip the API write when nothing changed.
			if ( is_array( $stored_webhook )
				&& ( $stored_webhook['url'] ?? '' ) === $webhook_url
				&& ( $stored_webhook['expires_at'] ?? '' ) === $expires_at_gmt ) {
				\WebChangeDetector\WebChangeDetector_Admin_Utils::log_error( 'Daily webhook unchanged (same url and expiry). Skipping API update.', 'wcd_save_update_group_settings', 'debug' );
				return;
			}
			\WebChangeDetector\WebChangeDetector_Admin_Utils::log_error( 'Webhook already exists. Updating it.', 'wcd_save_update_group_settings', 'debug' );
			$result = \WebChangeDetector\WebChangeDetector_API_V2::update_webhook_v2( $webhook_id, $webhook_url, $expires_at_gmt );
		} else {
			// Add a one-time webhook to trigger the wp_version_check cron.
			\WebChangeDetector\WebChangeDetector_Admin_Utils::log_error( 'Creating webhook to trigger ' . WCD_TRIGGER_WP_VERSION_CHECK, 'wcd_save_update_group_settings', 'debug' );
			$result = \WebChangeDetector\WebChangeDetector_API_V2::add_webhook_v2( $webhook_url, 'wordpress_single_call', $expires_at_gmt );
		}

		if ( isset( $result['data']['id'] ) ) {
			set_transient(
				'wcd_single_call_webhook_id',
				array(
					'id'         => $result['data']['id'],
					'url'        => $webhook_url,
					'expires_at' => $expires_at_gmt,
				),
				$expires_at - time()
			);
		} else {
			\WebChangeDetector\WebChangeDetector_Admin_Utils::log_error( 'Webhook create/update failed. Clearing stored webhook id.', 'wcd_save_update_group_settings', 'debug' );
			delete_transient( 'wcd_single_call_webhook_id' );
		}
		\WebChangeDetector\WebChangeDetector_Admin_Utils::log_error( 'Webhook result: ' . wp_json_encode( $result ), 'wcd_save_update_group_settings', 'debug' );
	}

	/** Starting the pre-update screenshots before auto-updates are started.
	 * We set the lock to delay WP from starting the auto updates.
	 * Auto updates are delayed when they are not in the selected timeframe.
	 *
	 * This method has been refactored into smaller, focused methods for better
	 * readability and maintainability. Each step is now clearly separated.
	 *
	 * @return void
	 */
	public function wp_maybe_auto_update() {
		// Step 1: Check for concurrent execution.
		if ( $this->should_skip_concurrent_execution() ) {
			// A parallel request may have armed the core-security bypass and skipped the
			// lock. If core's priority-10 callback of THIS request wins create_lock()
			// against that request, it would run UNRESTRICTED outside the window, so
			// re-arm the one-request filters here before returning.
			if ( $this->is_core_security_bypass_enabled() && get_transient( 'wcd_core_security_bypass' ) ) {
				$this->arm_core_security_filters();
			}
			return;
		}

		// Set execution lock for this function.
		set_transient( 'wcd_update_check_running', time(), 30 );

		// Step 2: Check if we started pre-update screenshots already.
		// Only enter while the pre-update batch is still 'processing': after the update ran,
		// the option stays with status 'done' until post-queue cleanup, and re-entering here
		// would re-trigger the updates and produce duplicate post-update batches.
		$wcd_pre_update_data = get_option( WCD_PRE_AUTO_UPDATE );
		if ( $wcd_pre_update_data && isset( $wcd_pre_update_data['batch_id'] ) && isset( $wcd_pre_update_data['status'] ) && 'processing' === $wcd_pre_update_data['status'] ) {
			$is_ready = $this->check_pre_update_screenshots_status( $wcd_pre_update_data );

			// Check if pre-update screenshots are ready.
			if ( $is_ready ) {
				// Update status to done, preserving all existing data including versions.
				$wcd_pre_update_data['status'] = 'done';
				if ( ! isset( $wcd_pre_update_data['timestamp'] ) ) {
					$wcd_pre_update_data['timestamp'] = time();
				}
				update_option( WCD_PRE_AUTO_UPDATE, $wcd_pre_update_data, false );

				// Screenshots are ready, trigger WordPress updates (which also removes the lock).
				$this->trigger_wordpress_updates();
				return;
			}

			// The pre-update screenshots are not ready, so we set the lock and re-schedule.
			$this->reschedule( 'wp_maybe_auto_update' );
			$this->set_lock();
			return;
		}

		// Step 3: Check cooldown period.
		if ( $this->is_within_cooldown_period() ) {
			if ( $this->maybe_bypass_for_core_security( 'cooldown' ) ) {
				return; // No lock: core's priority-10 callback runs the restricted pass.
			}
			$this->set_lock_if_not_updating();
			return;
		}

		// Step 4: Validate WCD configuration and check if auto-update checks are enabled.
		$auto_update_settings = $this->validate_wcd_configuration();
		if ( ! $auto_update_settings ) {
			return;
		}

		// Step 5: Check if updates are allowed today.
		if ( ! $this->is_allowed_today( $auto_update_settings ) ) {
			if ( $this->maybe_bypass_for_core_security( 'weekday' ) ) {
				return; // No lock: core's priority-10 callback runs the restricted pass.
			}
			$this->set_lock_if_not_updating();
			return;
		}

		// Step 6: Check if current time is within allowed window.
		if ( ! $this->is_within_time_window( $auto_update_settings ) ) {
			if ( $this->maybe_bypass_for_core_security( 'time_window' ) ) {
				return; // No lock: core's priority-10 callback runs the restricted pass.
			}
			$this->set_lock_if_not_updating();
			return;
		}

		// Step 8: Handle pre-update screenshots.

		if ( false === $wcd_pre_update_data ) {

			// Step 9: Skip when WP automatic updates are disabled (e.g. by a hosting
			// tool via the 'automatic_updater_disabled' filter or constant). Core's
			// updater would exit immediately, so pre/post screenshots would only
			// produce pointless "no changes" results.
			$wp_updates_status = WebChangeDetector_Autoupdate_Guard::get_status();
			if ( $wp_updates_status['effective_disabled'] ) {
				$this->log_auto_update_error(
					'wp_updates_disabled',
					array(
						'cause'           => $wp_updates_status['cause'],
						'override_active' => WebChangeDetector_Autoupdate_Guard::is_override_enabled(),
					)
				);

				// Clear any stuck state since WP will not run updates. Also removes the
				// minute-webhook and the fallback check with it (webhook lifecycle is
				// tied to the pre/post state lifecycle); all deletes are no-op safe.
				$this->cleanup_auto_update_run();

				// Set lock to prevent checking again too soon.
				$this->set_lock();
				return;
			}

			// Step 10: Check if there are actually updates available.
			$available_updates = $this->check_for_available_updates();

			// If we don't have updates to install, we remove all options and set the lock.
			if ( ! $available_updates ) {
				\WebChangeDetector\WebChangeDetector_Admin_Utils::log_error(
					'No updates available. Skipping auto-update process and screenshots.',
					'wp_maybe_auto_update',
					'info'
				);

				// Clear any stuck state since there's nothing to update. Also removes the
				// minute-webhook and the fallback check with it (webhook lifecycle is
				// tied to the pre/post state lifecycle); all deletes are no-op safe.
				$this->cleanup_auto_update_run();

				// Set lock to prevent checking again too soon.
				$this->set_lock();
				return;
			}

			\WebChangeDetector\WebChangeDetector_Admin_Utils::log_error(
				'Updates available - Core: ' . $available_updates['core'] .
				', Plugins: ' . $available_updates['plugins'] .
				', Themes: ' . $available_updates['themes'] .
				'. Proceeding with auto-update process.',
				'wp_maybe_auto_update',
				'info'
			);

			// Arm the 12h cooldown only now, when a real run is actually starting the
			// pre-update batch. Setting it earlier (before the WP-disabled and no-updates
			// cheap exits) latched the cooldown for passes that started no paid batch,
			// blocking manual test triggers and legitimate later-in-window runs.
			// Set BEFORE the paid batch begins so re-entry within 12h is blocked once a
			// real run starts (the Step-2 processing gate covers re-entry during the run).
			update_option( WCD_LAST_AUTO_UPDATE_CHECK_TIME, time() );
			\WebChangeDetector\WebChangeDetector_Admin_Utils::log_error(
				'Set auto-update check timestamp to prevent retries within 12 hours',
				'wp_maybe_auto_update',
				'debug'
			);

			// Start new pre-update screenshots and reschedule wp_maybe_auto_update.
			$this->start_pre_update_screenshots();

			// Schedule re-check for when the pre-update screenshots are done.
			$this->reschedule( 'wp_maybe_auto_update' );
		}

		// Clear execution lock.
		delete_transient( 'wcd_update_check_running' );
	}

	/** Send the change detection mail.
	 *
	 * Assembles the data, renders the mail body from the auto-update-mail partial
	 * and sends it via wp_mail().
	 *
	 * @param array $post_sc_option Data about the post sc.
	 * @return void
	 */
	public function send_change_detection_mail( $post_sc_option ) {
		// If we don't have open or processing queues of the batch anymore, we can check for comparisons.
		$comparisons = \WebChangeDetector\WebChangeDetector_API_V2::get_comparisons_v2( array( 'batches' => $post_sc_option['batch_id'] ) );

		// Validate the response before using it. api_v2() returns plain strings on failure.
		if ( ! is_array( $comparisons ) || ! isset( $comparisons['data'] ) || ! is_array( $comparisons['data'] ) ) {
			\WebChangeDetector\WebChangeDetector_Admin_Utils::log_error( 'Invalid API response for comparisons. Skipping the change detection mail. Results are still available in the app.', 'send_change_detection_mail', 'error' );
			return;
		}

		$auto_update_settings = self::get_auto_update_settings();
		$to                   = '';
		if ( ! empty( $auto_update_settings['auto_update_checks_emails'] ) ) {
			$emails = $auto_update_settings['auto_update_checks_emails'];
			$to     = is_array( $emails ) ? implode( ',', $emails ) : $emails;
		}

		if ( empty( $to ) ) {
			\WebChangeDetector\WebChangeDetector_Admin_Utils::log_error( 'No notification emails configured, skipping mail', 'send_change_detection_mail', 'debug' );
			return;
		}

		$comparison_rows  = $comparisons['data'];
		$batch_ai_summary = $comparison_rows[0]['batch']['ai_summary']['summary'] ?? '';

		// finally guarantees the output buffer is closed even when the include throws
		// (the caller catches the Throwable; a leaked buffer would swallow later output).
		ob_start();
		try {
			include WCD_PLUGIN_DIR . 'admin/partials/templates/auto-update-mail.php';
		} finally {
			$mail_body = ob_get_clean();
		}

		$subject = '[' . get_bloginfo( 'name' ) . '] Auto Update Checks by WebChange Detector';
		$headers = array( 'Content-Type: text/html; charset=UTF-8' );
		\WebChangeDetector\WebChangeDetector_Admin_Utils::log_error( 'Sending Mail with differences', 'send_change_detection_mail', 'debug' );
		wp_mail( $to, $subject, $mail_body, $headers );
	}

	/** Get the auto-update settings.
	 *
	 * @param bool $force_refresh Force refresh from API, bypassing the cache.
	 * @return array
	 */
	public static function get_auto_update_settings( $force_refresh = false ) {
		// Return cached version unless force refresh is requested.
		if ( ! empty( self::$auto_update_settings_cache ) && ! $force_refresh ) {
			return self::$auto_update_settings_cache;
		}

		$wcd = new WebChangeDetector_Admin();
		return self::cache_auto_update_settings( $wcd->settings_handler->get_website_details( $force_refresh )['auto_update_settings'] ?? array() );
	}

	/** Store auto-update settings in the per-request cache.
	 *
	 * Lets the hourly sync refresh the cache from already-fetched website details
	 * instead of triggering a second forced API call via get_auto_update_settings( true ).
	 *
	 * @param array $settings Auto-update settings as returned by the API.
	 * @return array The cached settings (with the define override applied).
	 */
	private static function cache_auto_update_settings( $settings ) {
		if ( ! is_array( $settings ) ) {
			$settings = array();
		}

		// Enable auto-update checks if the defines are set.
		if ( defined( 'WCD_AUTO_UPDATES_ENABLED' ) && true === WCD_AUTO_UPDATES_ENABLED ) {
			$settings['auto_update_checks_enabled'] = true;
		}

		self::$auto_update_settings_cache = $settings;
		return $settings;
	}

	/**
	 * Sync auto-update schedule from API settings.
	 * This runs hourly to ensure local schedulers match API settings.
	 * Also performs basic health checks since API call validates connectivity.
	 *
	 * @return void
	 */
	public function sync_auto_update_schedule_from_api() {
		\WebChangeDetector\WebChangeDetector_Admin_Utils::log_error( 'Starting auto-update schedule sync with API', 'sync_auto_update_schedule_from_api', 'debug' );

		// Perform basic health status update.
		$health_status = array(
			'overall_status' => 'healthy',
			'checks'         => array(),
			'timestamp'      => current_time( 'mysql' ),
		);

		try {
			// Get fresh settings from API (force refresh).
			// This call effectively validates API connectivity and authentication.
			$wcd                   = new WebChangeDetector_Admin();
			$fresh_website_details = $wcd->settings_handler->get_website_details( true ); // Force refresh from API.

			// API call succeeded - mark as healthy.
			$health_status['checks']['api'] = array(
				'status'  => true,
				'message' => 'API connectivity OK',
			);

			// Check configuration while we have the data.
			$api_token                                = WebChangeDetector_Multisite::get_api_token();
			$groups                                   = get_option( WCD_WEBSITE_GROUPS );
			$health_status['checks']['configuration'] = array(
				'status'  => ! empty( $api_token ) && ! empty( $groups ),
				'message' => ( ! empty( $api_token ) && ! empty( $groups ) ) ? 'Configuration OK' : 'Configuration incomplete',
			);

			$api_auto_update_settings = $fresh_website_details['auto_update_settings'] ?? array();

			if ( empty( $api_auto_update_settings ) ) {
				\WebChangeDetector\WebChangeDetector_Admin_Utils::log_error( 'No auto-update settings from API, skipping sync', 'sync_auto_update_schedule_from_api', 'debug' );
			} else {
				// Refresh the settings cache from the already-fetched details; previously
				// this was a second forced API call for the same data.
				self::cache_auto_update_settings( $api_auto_update_settings );

				// Mirror the dashboard-controlled core-security toggle into the local
				// wp_option (write-through mirror: is_core_security_bypass_enabled()
				// keeps reading only the option, so wp-cron works offline). A response
				// without the key means an older API: leave the option untouched.
				// Truthiness on purpose, never a strict boolean check: V1-written
				// rows can carry string values ('1'/'0') instead of booleans.
				if ( array_key_exists( 'allow_core_security_updates', $api_auto_update_settings ) ) {
					$api_allow_core = ! empty( $api_auto_update_settings['allow_core_security_updates'] ) ? '1' : '0';
					if ( (string) get_option( self::OPTION_ALLOW_CORE_SECURITY, '1' ) !== $api_allow_core ) {
						update_option( self::OPTION_ALLOW_CORE_SECURITY, $api_allow_core );
					}
				}

				// Update the schedule using existing method (this reschedules the crons).
				// The wcd_save_update_group_settings method already handles everything:.
				// - Reschedules wp_version_check.
				// - Reschedules wcd_wp_version_check.
				// - Sets the correct timeframe.
				$this->wcd_save_update_group_settings( $api_auto_update_settings );

				\WebChangeDetector\WebChangeDetector_Admin_Utils::log_error(
					'Auto-update schedule synced with API settings',
					'sync_auto_update_schedule_from_api',
					'debug'
				);
			}
		} catch ( \Exception $e ) {
			// API call failed - mark as unhealthy.
			$health_status['checks']['api']  = array(
				'status'  => false,
				'message' => 'API connectivity failed: ' . $e->getMessage(),
			);
			$health_status['overall_status'] = 'unhealthy';

			\WebChangeDetector\WebChangeDetector_Admin_Utils::log_error(
				'Failed to sync auto-update schedule: ' . $e->getMessage(),
				'sync_auto_update_schedule_from_api',
				'error'
			);
		}

		// Check for stuck processes as part of hourly sync.
		$stuck_processes_cleaned = $this->check_and_clean_all_stuck_processes();
		if ( ! empty( $stuck_processes_cleaned ) ) {
			$health_status['checks']['stuck_processes'] = array(
				'status'  => false,
				'message' => 'Cleaned stuck processes: ' . implode( ', ', $stuck_processes_cleaned ),
			);
			$health_status['overall_status']            = 'warning';
		} else {
			$health_status['checks']['stuck_processes'] = array(
				'status'  => true,
				'message' => 'No stuck processes found',
			);
		}

		// Update health status.
		update_option( WCD_WP_OPTION_KEY_HEALTH_STATUS, $health_status );
	}

	/**
	 * Check and clean all stuck auto-update processes.
	 *
	 * This centralized method checks for stuck processes across all auto-update
	 * operations and cleans them up if they exceed timeout thresholds.
	 * Handles migration from old format without timestamps by adding current time.
	 *
	 * @return array List of cleaned stuck processes for logging
	 */
	private function check_and_clean_all_stuck_processes() {
		$stuck_processes      = array();
		$cleaned_update_state = false;

		// Define timeout thresholds (in seconds).
		$pre_update_timeout     = 2 * HOUR_IN_SECONDS; // 2 hours for pre-update screenshots.
		$post_update_timeout    = 2 * HOUR_IN_SECONDS; // 2 hours for post-update screenshots.
		$wordpress_lock_timeout = HOUR_IN_SECONDS;  // 1 hour for WordPress lock.

		// Check pre-update screenshots.
		$pre_update_data = get_option( WCD_PRE_AUTO_UPDATE );
		if ( $pre_update_data ) {
			if ( ! isset( $pre_update_data['timestamp'] ) ) {
				// Old format without timestamp - add current time.
				$pre_update_data['timestamp'] = time();
				update_option( WCD_PRE_AUTO_UPDATE, $pre_update_data, false );

				\WebChangeDetector\WebChangeDetector_Admin_Utils::log_error(
					'Found pre-update process without timestamp. Added current timestamp to allow completion.',
					'check_and_clean_all_stuck_processes',
					'info'
				);
			} else {
				// Has timestamp - check if stuck.
				$age_in_seconds = time() - $pre_update_data['timestamp'];
				if ( $age_in_seconds > $pre_update_timeout ) {
					\WebChangeDetector\WebChangeDetector_Admin_Utils::log_error(
						'Found stuck pre-update process from ' . $age_in_seconds . ' seconds ago. Cleaning up.',
						'check_and_clean_all_stuck_processes',
						'warning'
					);
					delete_option( WCD_PRE_AUTO_UPDATE );
					delete_option( WCD_AUTO_UPDATES_RUNNING );
					// Deactivate the update-offer restore in this request too:
					// the guard caches its option lookup per request.
					WebChangeDetector_Update_Offer_Guard::reset();
					$cleaned_update_state = true;
					$stuck_processes[]    = 'pre-update (age: ' . $age_in_seconds . 's)';
				}
			}
		}

		// Check post-update screenshots.
		$post_update_data = get_option( WCD_POST_AUTO_UPDATE );
		if ( $post_update_data ) {
			if ( ! isset( $post_update_data['timestamp'] ) ) {
				// Old format without timestamp - add current time.
				$post_update_data['timestamp'] = time();
				update_option( WCD_POST_AUTO_UPDATE, $post_update_data, false );

				\WebChangeDetector\WebChangeDetector_Admin_Utils::log_error(
					'Found post-update process without timestamp. Added current timestamp to allow completion.',
					'check_and_clean_all_stuck_processes',
					'info'
				);
			} else {
				// Has timestamp - check if stuck.
				$age_in_seconds = time() - $post_update_data['timestamp'];
				if ( $age_in_seconds > $post_update_timeout ) {
					\WebChangeDetector\WebChangeDetector_Admin_Utils::log_error(
						'Found stuck post-update process from ' . $age_in_seconds . ' seconds ago. Cleaning up.',
						'check_and_clean_all_stuck_processes',
						'warning'
					);
					delete_option( WCD_POST_AUTO_UPDATE );
					$cleaned_update_state = true;
					$stuck_processes[]    = 'post-update (age: ' . $age_in_seconds . 's)';
				}
			}
		}

		// The minute-webhook only serves the pre/post-update state. When we clean that state, the webhook
		// (and its stored id) must go too. Otherwise reschedule() skips webhook creation against a stale id forever.
		if ( $cleaned_update_state ) {
			$webhook_id = get_option( WCD_WORDPRESS_CRON );
			if ( $webhook_id ) {
				// The webhook may already be expired or deleted on the API. We ignore the response; deleting the option is what matters.
				\WebChangeDetector\WebChangeDetector_API_V2::delete_webhook_v2( $webhook_id );
				\WebChangeDetector\WebChangeDetector_Admin_Utils::log_error( 'Deleted minute-webhook ' . $webhook_id . ' together with stuck auto-update state.', 'check_and_clean_all_stuck_processes', 'debug' );
				delete_option( WCD_WORDPRESS_CRON );
				$stuck_processes[] = 'minute webhook';
			}
		}

		// Check auto-updates running flag.
		$auto_updates_running = get_option( WCD_AUTO_UPDATES_RUNNING );
		if ( $auto_updates_running ) {
			// This flag should be cleared when pre/post update processes complete.
			// If it exists without corresponding pre/post update data, it's likely stuck.
			$has_active_process = get_option( WCD_PRE_AUTO_UPDATE ) || get_option( WCD_POST_AUTO_UPDATE );

			if ( ! $has_active_process ) {
				\WebChangeDetector\WebChangeDetector_Admin_Utils::log_error(
					'Found orphaned auto-updates running flag without active process. Cleaning up.',
					'check_and_clean_all_stuck_processes',
					'warning'
				);
				delete_option( WCD_AUTO_UPDATES_RUNNING );
				$stuck_processes[] = 'orphaned running flag';
			}
		}

		// Check WordPress auto-updater lock.
		$lock = get_option( $this->lock_name );
		if ( $lock ) {
			$lock_age = time() - $lock;
			if ( $lock_age > $wordpress_lock_timeout ) {
				\WebChangeDetector\WebChangeDetector_Admin_Utils::log_error(
					'Found stuck auto_updater.lock (age: ' . $lock_age . ' seconds). Removing it.',
					'check_and_clean_all_stuck_processes',
					'warning'
				);
				delete_option( $this->lock_name );
				$stuck_processes[] = 'WordPress lock (age: ' . $lock_age . 's)';
			}
		}

		// If we cleaned any stuck processes, log summary.
		if ( ! empty( $stuck_processes ) ) {
			\WebChangeDetector\WebChangeDetector_Admin_Utils::log_error(
				'Cleaned ' . count( $stuck_processes ) . ' stuck processes: ' . implode( ', ', $stuck_processes ),
				'check_and_clean_all_stuck_processes',
				'info'
			);
		}

		return $stuck_processes;
	}

	/**
	 * Get or create webhook key for API authentication
	 *
	 * @return string The webhook key
	 */
	private function get_or_create_webhook_key() {
		$webhook_key = get_option( 'wcd_webhook_key', '' );
		if ( empty( $webhook_key ) ) {
			// Create a new webhook key if we don't have one.
			$webhook_key = wp_generate_password( 32, false );
			update_option( 'wcd_webhook_key', $webhook_key );
		}
		return $webhook_key;
	}

	/**
	 * Create scheduled event and create a cron at our api to trigger it.
	 *
	 * @param string $hook Hook name.
	 * @return void
	 */
	private function reschedule( $hook ) {
		// Our cron method for the hook.
		$how_long = 30; // 30 seconds.
		wp_clear_scheduled_hook( $hook );
		wp_schedule_single_event( time() + $how_long, $hook );

		// Check if we need to create a new webhook.
		$webhook_id = get_option( WCD_WORDPRESS_CRON, false );
		if ( $webhook_id ) {
			// Webhook is already created, so we skip.
			return;
		}

		// Create our external webhook url.
		$webhook_url = add_query_arg(
			array(
				'wcd_action' => WCD_TRIGGER_AUTO_UPDATE_CRON,
				'key'        => $this->get_or_create_webhook_key(),
			),
			site_url()
		);

		\WebChangeDetector\WebChangeDetector_Admin_Utils::log_error( 'Creating webhook to trigger ' . $hook, 'reschedule', 'debug' );

		// Create a new WordPress cron webhook checking every minute and expires in 2 hours.
		$result = \WebChangeDetector\WebChangeDetector_API_V2::add_webhook_v2( $webhook_url, 'wordpress_cron', gmdate( 'Y-m-d H:i:s', time() + HOUR_IN_SECONDS * 2 ) );

		if ( is_array( $result ) && isset( $result['data'] ) && isset( $result['data']['id'] ) ) {
			// Store the webhook ID for later reference.
			update_option( WCD_WORDPRESS_CRON, $result['data']['id'] );
		}
	}

	/**
	 * Process webhook trigger by executing the appropriate WordPress cron event
	 */
	public function handle_webhook_trigger() {
		$is_authorized = false;

		// We're using a custom API key verification approach instead of nonces since this is an external webhook.
		// that needs to remain valid for several hours. The 'key' parameter contains a random 32-character string.
		// that's verified against our stored option.
		// phpcs:disable WordPress.Security.NonceVerification.Recommended -- Using API key-based authentication instead of nonce for cron requests.
		if ( isset( $_GET['wcd_action'] ) && isset( $_GET['key'] ) ) {
			$wcd_action = sanitize_text_field( wp_unslash( $_GET['wcd_action'] ) );
			$key        = sanitize_text_field( wp_unslash( $_GET['key'] ) );
			// phpcs:enable WordPress.Security.NonceVerification.Recommended

			$authorized_actions = array(
				WCD_TRIGGER_AUTO_UPDATE_CRON,
				WCD_TRIGGER_WP_VERSION_CHECK,
			);

			if ( in_array( $wcd_action, $authorized_actions, true ) && ! empty( $key ) ) {
				$webhook_key = $this->get_or_create_webhook_key();
				if ( ! empty( $webhook_key ) && hash_equals( $webhook_key, $key ) ) {
					$is_authorized = true;
				}
			}
		}

		if ( $is_authorized ) {
			\WebChangeDetector\WebChangeDetector_Admin_Utils::log_error( 'Processing authorized webhook trigger: ' . $wcd_action, 'handle_webhook_trigger', 'debug' );

			// Also trigger our fallback check for update completion.
			if ( get_option( WCD_AUTO_UPDATES_RUNNING ) ) {
				\WebChangeDetector\WebChangeDetector_Admin_Utils::log_error(
					'Webhook trigger: Also checking update completion status',
					'handle_webhook_trigger',
					'debug'
				);

				// @todo Move this completion check into the cron handler so the webhook stays lean.
				$this->check_update_completion();
			}

			// Force WordPress to process all pending cron events.
			spawn_cron();

			echo 'OK';
			exit;
		}
	}

	/**
	 * Defines.
	 *
	 * @return void
	 */
	private function set_defines() {

		if ( ! defined( 'WCD_WEBSITE_GROUPS' ) ) {
			define( 'WCD_WEBSITE_GROUPS', 'wcd_website_groups' );
		}
		if ( ! defined( 'WCD_MANUAL_DETECTION_GROUP' ) ) {
			define( 'WCD_MANUAL_DETECTION_GROUP', 'manual_detection_group' );
		}
		if ( ! defined( 'WCD_AUTO_DETECTION_GROUP' ) ) {
			define( 'WCD_AUTO_DETECTION_GROUP', 'auto_detection_group' );
		}
		if ( ! defined( 'WCD_WORDPRESS_CRON' ) ) {
			define( 'WCD_WORDPRESS_CRON', 'wcd_wordpress_cron' );
		}
		if ( ! defined( 'WCD_LAST_AUTO_UPDATE_CHECK_TIME' ) ) {
			define( 'WCD_LAST_AUTO_UPDATE_CHECK_TIME', 'wcd_last_auto_update_check_time' );
		}
		if ( ! defined( 'WCD_PRE_AUTO_UPDATE' ) ) {
			define( 'WCD_PRE_AUTO_UPDATE', 'wcd_pre_auto_update' );
		}
		if ( ! defined( 'WCD_WP_MAYBE_AUTO_UPDATE' ) ) {
			define( 'WCD_WP_MAYBE_AUTO_UPDATE', 'wp_maybe_auto_update' );
		}
		if ( ! defined( 'WCD_POST_AUTO_UPDATE' ) ) {
			define( 'WCD_POST_AUTO_UPDATE', 'wcd_post_auto_update' );
		}
		if ( ! defined( 'WCD_AUTO_UPDATES_RUNNING' ) ) {
			define( 'WCD_AUTO_UPDATES_RUNNING', 'wcd_auto_updates_running' );
		}
		if ( ! defined( 'WCD_AUTO_UPDATE_SETTINGS' ) ) {
			define( 'WCD_AUTO_UPDATE_SETTINGS', 'wcd_auto_update_settings' );
		}
		if ( ! defined( 'WCD_AUTO_UPDATE_PARTICIPATE' ) ) {
			// Per-site flag mirroring the auto_update_checks_enabled toggle.
			// Read by the network orchestrator's collect_network_group_ids()
			// to decide which sub-sites' URLs go into the pre/post batch.
			define( 'WCD_AUTO_UPDATE_PARTICIPATE', 'wcd_auto_update_participate' );
		}
		if ( ! defined( 'WCD_ALLOWANCES' ) ) {
			define( 'WCD_ALLOWANCES', 'wcd_allowances' );
		}
		if ( ! defined( 'WCD_AUTO_UPDATE_COMPARISON_BATCHES' ) ) {
			define( 'WCD_AUTO_UPDATE_COMPARISON_BATCHES', 'wcd_auto_update_comparison_batches' );
		}
		if ( ! defined( 'WCD_TRIGGER_AUTO_UPDATE_CRON' ) ) {
			define( 'WCD_TRIGGER_AUTO_UPDATE_CRON', 'trigger_auto_update_cron' );
		}

		if ( ! defined( 'WCD_TRIGGER_WP_VERSION_CHECK' ) ) {
			define( 'WCD_TRIGGER_WP_VERSION_CHECK', 'trigger_wp_version_check' );
		}
		if ( ! defined( 'WCD_AUTO_UPDATE_TRIGGERED_TIME' ) ) {
			define( 'WCD_AUTO_UPDATE_TRIGGERED_TIME', 'wcd_auto_update_triggered_time' );
		}
		if ( ! defined( 'WCD_WP_OPTION_KEY_DEBUG_LOGGING' ) ) {
			define( 'WCD_WP_OPTION_KEY_DEBUG_LOGGING', 'webchangedetector_debug_logging' );
		}
		if ( ! defined( 'WCD_WP_OPTION_KEY_API_TOKEN' ) ) {
			define( 'WCD_WP_OPTION_KEY_API_TOKEN', 'webchangedetector_api_token' );
		}
		if ( ! defined( 'WCD_WP_OPTION_KEY_HEALTH_STATUS' ) ) {
			define( 'WCD_WP_OPTION_KEY_HEALTH_STATUS', 'webchangedetector_health_status' );
		}
	}

	/**
	 * Save auto-update results to options for frontend display.
	 *
	 * @param array       $update_results The update results from WordPress.
	 * @param string|null $batch_id_post_update The batch ID for the post-update process.
	 * @param string|null $context Optional context marker for the history entry (e.g. 'security_update').
	 * @return void
	 */
	private function save_update_results( $update_results, $batch_id_post_update = null, $context = null ) {
		try {
			// Log the raw update results for debugging.
			\WebChangeDetector\WebChangeDetector_Admin_Utils::log_error(
				'Raw update results structure: ' . wp_json_encode( $update_results ),
				'save_update_results',
				'debug'
			);

			// Get existing history or initialize empty array.
			$history = get_option( 'wcd_auto_update_history', array() );
			if ( ! is_array( $history ) ) {
				$history = array();
			}

			// Parse results and summary in a single pass.
			$results = $this->parse_update_results( $update_results );

			// Create new entry with parsed results.
			$new_entry = array(
				'timestamp' => time(),
				'batch_id'  => $batch_id_post_update,
				'updates'   => $results['updates'],
				'summary'   => $results['summary'],
			);

			// Mark special runs (e.g. a core security update installed without checks).
			if ( null !== $context ) {
				$new_entry['context'] = $context;
			}

			// Add new entry to beginning of array.
			array_unshift( $history, $new_entry );

			// Keep only last 30 entries to prevent option bloat.
			$history = array_slice( $history, 0, 30 );

			// Save updated history.
			update_option( 'wcd_auto_update_history', $history, false );

			\WebChangeDetector\WebChangeDetector_Admin_Utils::log_error(
				'Successfully saved auto-update results. Total history entries: ' . count( $history ),
				'save_update_results',
				'debug'
			);
		} catch ( \Exception $e ) {
			\WebChangeDetector\WebChangeDetector_Admin_Utils::log_error(
				'Error saving auto-update results: ' . $e->getMessage() . ' | Trace: ' . $e->getTraceAsString(),
				'save_update_results',
				'error'
			);
		}
	}

	/**
	 * Log auto-update error for user visibility.
	 *
	 * Only logs actionable errors that prevent auto-updates from running
	 * when they should be running. This helps users understand why their
	 * configured auto-updates didn't execute.
	 *
	 * @since 4.0.2
	 * @param string $error_type Error type: 'skip_cooldown', 'skip_error' or 'wp_updates_disabled'.
	 * @param array  $details    Error details (context-specific information).
	 * @return void
	 */
	private function log_auto_update_error( $error_type, $details ) {
		// Get existing history.
		$history = get_option( 'wcd_auto_update_history', array() );
		if ( ! is_array( $history ) ) {
			$history = array();
		}

		// Create error entry.
		$error_entry = array(
			'timestamp' => time(),
			'batch_id'  => null,
			'updates'   => array(),
			'summary'   => array(
				'status'          => 'error',
				'total_attempted' => 0,
				'successful'      => 0,
			),
			'error'     => array(
				'type'    => sanitize_text_field( $error_type ),
				'details' => $details,
			),
		);

		// Add to beginning of history.
		array_unshift( $history, $error_entry );

		// Keep only last 30 entries to prevent option bloat.
		$history = array_slice( $history, 0, 30 );

		// Save updated history.
		update_option( 'wcd_auto_update_history', $history, false );

		// Also log to debug logs for troubleshooting.
		\WebChangeDetector\WebChangeDetector_Admin_Utils::log_error(
			'Auto-update error logged: ' . $error_type . ' - ' . wp_json_encode( $details ),
			'log_auto_update_error',
			'warning'
		);
	}

	/**
	 * Parse WordPress update results into structured entries plus summary counts.
	 *
	 * Single pass over the raw results produced by WP_Automatic_Updater
	 * (class-wp-automatic-updater.php): each entry is an object with ->item,
	 * ->result and, for plugins/themes, ->name. The pre-update versions are read
	 * ONCE from WCD_PRE_AUTO_UPDATE, which is guaranteed to still exist here
	 * (post-queue cleanup deletes it later).
	 *
	 * @param array $update_results Raw update results from WordPress.
	 * @return array {
	 *     Parsed results.
	 *
	 *     @type array $updates Parsed entries: core (array|null), plugins (array[]), themes (array[]).
	 *     @type array $summary Counts: total_attempted, successful, failed, status.
	 * }
	 */
	private function parse_update_results( $update_results ) {
		$parsed  = array(
			'core'    => null,
			'plugins' => array(),
			'themes'  => array(),
		);
		$summary = array(
			'total_attempted' => 0,
			'successful'      => 0,
			'failed'          => 0,
			'status'          => 'completed',
		);

		$pre_update_data = get_option( WCD_PRE_AUTO_UPDATE );
		$known_versions  = is_array( $pre_update_data ) && isset( $pre_update_data['versions'] ) ? $pre_update_data['versions'] : array();

		foreach ( array( 'core', 'plugin', 'theme' ) as $type ) {
			if ( ! isset( $update_results[ $type ] ) || ! is_array( $update_results[ $type ] ) ) {
				continue;
			}

			foreach ( $update_results[ $type ] as $update ) {
				if ( ! is_object( $update ) ) {
					\WebChangeDetector\WebChangeDetector_Admin_Utils::log_error(
						ucfirst( $type ) . ' update entry is not an object: ' . wp_json_encode( $update ),
						'parse_update_results',
						'warning'
					);
					continue;
				}

				$item       = isset( $update->item ) && is_object( $update->item ) ? $update->item : null;
				$has_result = property_exists( $update, 'result' );
				$error      = $has_result && is_wp_error( $update->result ) ? $update->result->get_error_message() : null;
				$success    = $has_result && null === $error;
				$messages   = $success ? array() : $this->extract_failure_messages( $update );

				// Summary counts: a result of false (core: update not attempted) counts as failed,
				// while the per-entry success flag mirrors the historical parse behavior.
				++$summary['total_attempted'];
				if ( $success && false !== $update->result ) {
					++$summary['successful'];
				} else {
					++$summary['failed'];
				}

				if ( 'core' === $type ) {
					$parsed['core'] = array(
						'attempted'    => true,
						'success'      => $success,
						'from_version' => $item->current ?? 'unknown',
						'to_version'   => $item->version ?? 'unknown',
						'error'        => $error,
						'messages'     => $messages,
					);
					continue;
				}

				// Key into the captured pre-update versions: plugin file resp. theme slug.
				$version_key = 'plugin' === $type ? ( $item->plugin ?? null ) : ( $item->theme ?? null );

				$parsed[ $type . 's' ][] = array(
					'slug'         => 'plugin' === $type ? ( $item->slug ?? 'unknown' ) : ( $item->theme ?? 'unknown' ),
					'name'         => $update->name ?? ( 'plugin' === $type ? 'Unknown Plugin' : 'Unknown Theme' ),
					'from_version' => null !== $version_key && isset( $known_versions[ $type . 's' ][ $version_key ] ) ? $known_versions[ $type . 's' ][ $version_key ] : 'n/a',
					'to_version'   => $item->new_version ?? 'unknown',
					'success'      => $success,
					'error'        => $error,
					'messages'     => $messages,
				);
			}
		}

		if ( $summary['failed'] > 0 ) {
			$summary['status'] = $summary['successful'] > 0 ? 'completed_with_errors' : 'failed';
		}

		return array(
			'updates' => $parsed,
			'summary' => $summary,
		);
	}

	/**
	 * Extract core's per-item upgrader messages for a failed update.
	 *
	 * WP_Automatic_Updater stores the Automatic_Upgrader_Skin messages per
	 * result item (already wp_kses-filtered to a[href], br, em, strong).
	 * They carry the real failure reason (e.g. "The plugin is at the latest
	 * version.") where the WP_Error is often a misleading generic
	 * fs_unavailable. Capped in count and length so wcd_auto_update_history
	 * stays small. The LAST messages are kept: the skin appends progress
	 * feedback first (downloading, unpacking, installing) and the actual
	 * failure reason last, so keeping the first ones would push it out.
	 *
	 * @param object $update Raw per-item update result object.
	 * @return string[] Up to 5 most recent messages, each truncated to 300 characters.
	 */
	private function extract_failure_messages( $update ) {
		if ( empty( $update->messages ) || ! is_array( $update->messages ) ) {
			return array();
		}

		$messages = array();
		foreach ( array_slice( $update->messages, -5 ) as $message ) {
			if ( ! is_string( $message ) || '' === trim( $message ) ) {
				continue;
			}
			$messages[] = mb_substr( $message, 0, 300 );
		}
		return $messages;
	}
}
