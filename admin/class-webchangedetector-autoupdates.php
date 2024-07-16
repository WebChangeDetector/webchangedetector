<?php
/**
Title: WebChange Detector Auto Update Feature
Description: Check your website on auto updates visually and see what changed.
Version: 1.0
*/

new WebChangeDetector_Autoupdates;

class WebChangeDetector_Autoupdates {

	// Has to be synced with WP_Automatic_Updater::run()
	private $lock_name = 'auto_updater.lock';

	public $one_minute_in_seconds = 60;
    public $two_minutes_in_seconds = 120;
	public $sc_done = false;

	public $wcd;

	/**
	 * Plugin constructor
	 */
	public function __construct() {

		add_action('wcd_wp_maybe_auto_update', array($this, 'wcd_wp_maybe_auto_update'));

		// Hooks into JetPack's remote updater (manual updates performed from the wordpress.com console)
		add_action('jetpack_pre_plugin_upgrade', array($this, 'jetpack_pre_plugin_upgrade'), 10, 3);
		add_action('jetpack_pre_theme_upgrade', array($this, 'jetpack_pre_theme_upgrade'), 10, 2);
		add_action('jetpack_pre_core_upgrade', array($this, 'jetpack_pre_core_upgrade'));

		add_action('plugins_loaded', array($this, 'plugins_loaded'));


        // Post updates
		add_action( 'upgrader_process_complete', array($this, 'upgrader_process_complete'), 10, 2);
		add_action( 'wcd_cron_check_post_queues', array($this, 'wcd_cron_check_post_queues'), 10, 2);

        add_action('admin_init', array($this,'admin_init'));

		$this->wcd = new WebChangeDetector_Admin();
	}

	/**
	 * We start here, when all plugins are loaded.
	 */
	public function plugins_loaded() {

		// Action added in WP 4.4. So we are not compatible before 4.4 (which was launched 2015).
		//error_log("Function: plugins_loaded");

		add_filter('auto_update_plugin', array($this, 'auto_update_plugin'), PHP_INT_MAX, 2);
		add_filter('auto_update_theme', array($this, 'auto_update_theme'), PHP_INT_MAX, 2);
		add_filter('auto_update_core', array($this, 'auto_update_core'), PHP_INT_MAX, 2);

		//add_action('pre_auto_update', array($this, 'pre_auto_update'), 10, 3);
	}

	// Delete this one. Only for debugging
    public function admin_init() {
	   // error_log("Function: admin_init");
	    $core_transient = get_site_transient('update_core');
	    $themes_transient = get_site_transient('update_themes');
	    $plugins_transient = get_site_transient('update_plugins');

		$new_time = strtotime('-13 hours');
		$core_transient->last_checked = $new_time;
	    $themes_transient->last_checked = $new_time;
	    $plugins_transient->last_checked = $new_time;

		set_site_transient("update_core", $core_transient);
		set_site_transient("update_themes", $themes_transient);
		set_site_transient("update_plugins", $plugins_transient);
	    //$plugins_transient = get_site_transient('update_plugins');
		//error_log("Plugins last checked: " . date("d.m.Y H:i",$plugins_transient->last_checked));
    }

	/**
	 * Fires when the update process is finished (for each plugin/theme/core).
	 */
     public function upgrader_process_complete( $upgrader, $hook_extra ) {

        error_log("Function: upgrader_process_complete");
		error_log("Upgrade complete data: " . json_encode($hook_extra));

	     // Add items to update to option wcd_updated_items.
	     error_log("Item: " . json_encode($hook_extra["temp_backup"]));
	     $updated_items = get_option('wcd_updated_items');
	     if(!$updated_items) {
		     $updated_items = [];
	     }
		 switch($hook_extra['type']) {
			 case 'core':
				 $updated_items[] = ['core' => 'core'];
				 break;

			 case 'plugin':
				 $updated_items[] = ['plugin' => $hook_extra['plugin']];
				 break;

			 case 'theme':
				 $updated_items[] = ['theme' => $hook_extra['theme']];
				 break;
		 }

	     update_option('wcd_updated_items', $updated_items);

		 $updating_items = get_option("wcd_updating_items");
		 if(count($updating_items) === count($updated_items)) {
			 $response = $this->wcd->take_screenshot_v2($this->wcd->manual_group_uuid, 'post');
			 error_log("Post-Screenshot Response: " . json_encode($response));
			 add_option('wcd_post_auto_update', ['status' => 'processing', 'batch_id' => $response['batch']]);
			 $this->wcd_cron_check_post_queues($this->one_minute_in_seconds);

			 $this->reschedule($this->one_minute_in_seconds,'wcd_cron_check_post_queues');
			 // Cleanup wp_options.
			 //delete_option('wcd_updated_items');
			 //delete_option('wcd_updating_items');
			 //delete_option('wcd_pre_auto_update');

			 // TODOStart checking queue for post_sc to be finished
		 }

        //$post_batch_id = get_option('wcd_auto_updates_post_batch_id');
        //$comparisons = $wcd->get_comparisons(["batch_id" => $post_batch_id]);
        //error_log(json_encode($comparisons));
        // TODO send mail with the comparisons
     }

	 public function wcd_cron_check_post_queues() {
		 $post_sc_option = get_option('wcd_post_auto_update');
		 $response = $this->wcd->get_queue_v2($post_sc_option['batch_id'], 'open,processing');

		 // If we don't have open or processing queues of the batch anymore, we can check for comparisons.
		 if(count($response['data']) === 0) {
			 // TODO get the comparisons
			$comparisons = $this->wcd->get_comparisons_v2(['batch_id' => $post_sc_option['batch_id']]);
			$mail_body = '';
			foreach($comparisons['data'] as $comparison) {
				//if(!$comparison['difference_percent']) {
					$mail_body .= 'Difference: ' . $comparison['difference_percent'] .
					              '<a href="' . $comparison['public_link'] . '">Check Change Detection</a><br>';
				//}

			}
			error_log("Final Mail: \n" . $mail_body);

			 // Cleanup wp_options.
			 delete_option('wcd_updated_items');
			 delete_option('wcd_updating_items');
			 delete_option('wcd_pre_auto_update');
			 delete_option('wcd_post_auto_update');

			wp_mail('mike@miler.de','Auto Update Checks', $mail_body);
		 } else { // check in a minute again
			 $this->reschedule($this->one_minute_in_seconds, 'wcd_cron_check_post_queues');
		 }
	 }

	public function wcd_wp_maybe_auto_update() {
		error_log("Checking if sc are ready");
		$pre_sc_option = get_option('wcd_pre_auto_update');
		$response = $this->wcd->get_queue_v2($pre_sc_option['batch_id'], 'open,processing');

		// If we don't have open or processing queues of the batch anymore, we can do auto-updates.
		if(count($response['data']) === 0) {
			$pre_sc_option['status'] = 'done';
			update_option('wcd_pre_auto_update', $pre_sc_option);
		}

		// If the queues are not done yet, we reschedule and exit.
		if("done" !== $pre_sc_option['status']) {
			error_log("Rescheduling updates as sc are not ready yet.");
			$this->reschedule($this->one_minute_in_seconds, 'wcd_wp_maybe_auto_update');
			return;
		}

		// Remove the lock, to allow the WP updater to claim it and proceed.
		delete_option($this->lock_name);

		// Start the auto-updates.
		wp_maybe_auto_update();
	}

	/**
	 * Note - with the addition of support for JetPack remote updates (via manual action in a user's wordpress.com dashboard), this is now more accurately a method to handle *background* updates, rather than "automatic" ones.
	 *
	 * @param  string $update
	 * @param  object $item
	 * @param  array  $type
	 * @return string
	 */
	public function auto_update($update, $item, $type) {

		error_log("Function: auto_update " . $update);

		//$this->wcd_api_v2 = new WebChangeDetector_Api_V2();
		//error_log("enabled $type: " . (wp_is_auto_update_enabled_for_type( $type ) ? "true" : "false"));
		//error_log("wp is auto update enabled: ". (wp_is_auto_update_enabled_for_type($type) ? "true" : "false"));
		// Early return start updates (?)
		if (
			!$update ||
			//!$this->sc_done ||
			    (
				!doing_filter('wp_maybe_auto_update') &&
				!doing_filter('jetpack_pre_plugin_upgrade') &&
				!doing_filter('jetpack_pre_theme_upgrade') &&
				!doing_filter('jetpack_pre_core_upgrade')
			    )
		    )
        {

            return $update;
        }

		// This has to be copied from WP_Automatic_Updater::should_update() because it's another reason why the eventual decision may be false.
		// If it's a core update, are we actually compatible with its requirements?
		if ('core' == $type) {
			global $wpdb;
			$php_compat = version_compare(phpversion(), $item->php_version, '>=');
			if (file_exists(WP_CONTENT_DIR . '/db.php') && empty($wpdb->is_mysql))
				$mysql_compat = true;
			else $mysql_compat = version_compare($wpdb->db_version(), $item->mysql_version, '>=');
			if (!$php_compat || !$mysql_compat)
				return false;
		}

		// Add items to update to option wcd_updating_items.
		error_log("Item: " . json_encode($item));
		$updating_items = get_option('wcd_updating_items');
		if(!$updating_items) {
			$updating_items = [];
		}
		if(isset($item->theme)) {
			$updating_items[] = ['theme' => $item->theme];
		} elseif(isset($item->plugin)) {
			$updating_items[] = ['plugin' => $item->plugin];
		} elseif(isset($item->response) && 'autoupdate' === $item->response ) {
			$updating_items[] = ['core' => 'core'];
		}

		update_option('wcd_updating_items', $updating_items);

		error_log("Checking status of Screenshots");
		error_log("Update type: " . json_encode($type));

		// TODO Do the WCD Magic and start pre-update screenshots
		$wcd_update_option = get_option("wcd_pre_auto_update");

		if(false === $wcd_update_option) { // We don't have an wp_option yet. So we start screenshots
			$sc_response = $this->wcd->take_screenshot_v2($this->wcd->manual_group_uuid, 'pre');
			error_log("Pre update SC data: " . json_encode($sc_response));
			$transientData = [
				"status" => 'processing',
				"batch_id" => esc_html($sc_response['batch']),
			];

			error_log("Started taking screenshots and setting transients");
			add_option("wcd_pre_auto_update", ($transientData));
			$this->reschedule($this->one_minute_in_seconds, 'wcd_wp_maybe_auto_update');
			return false;
		} elseif("done" !== $wcd_update_option['status']) { // SC are not done yet. Reschedule updates
			$this->reschedule($this->one_minute_in_seconds, 'wcd_wp_maybe_auto_update');
			error_log("Rescheduling...");
			return false;
		}

		// shouldn't get here, but to be safe...
		return $update;
	}

	/**
	 * Reschedule the automatic update check event
	 *
	 * @param Integer $how_long - how many seconds in the future from now to reschedule for
	 * @return void
	 */
	private function reschedule($how_long, $hook) {
		wp_clear_scheduled_hook($hook);
		if (!$how_long) {
			return;
			}

		wp_schedule_single_event(time() + $how_long, $hook);
	}

	/**
	 * Hooks for jetpack.
	 *
	 * @param  array $plugin
	 * @param  array $plugins
	 * @param  array $update_attempted
	 * @return void
	 */
	public function jetpack_pre_plugin_upgrade($plugin, $plugins, $update_attempted) {// phpcs:ignore VariableAnalysis.CodeAnalysis.VariableAnalysis.UnusedVariable -- Filter use
		$this->auto_update(true, $plugin, 'plugins');
	}

	public function jetpack_pre_theme_upgrade($theme, $themes) {// phpcs:ignore VariableAnalysis.CodeAnalysis.VariableAnalysis.UnusedVariable -- Filter use
		$this->auto_update(true, $theme, 'themes');
	}

	public function jetpack_pre_core_upgrade($update) {
		$this->auto_update(true, $update, 'core');
	}

	/**
	 * Hooking into auto-updates.
	 *
	 * @param  string $update
	 * @param  object $item
	 * @return string
	 */
	public function auto_update_plugin($update, $item) {
		return $this->auto_update($update, $item, 'plugins');
	}

	public function auto_update_theme($update, $item) {
		return $this->auto_update($update, $item, 'themes');
	}

	public function auto_update_core($update, $item) {
		return $this->auto_update($update, $item, 'core');
	}
}


