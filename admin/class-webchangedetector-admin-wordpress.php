<?php
/**
 * WebChange Detector Admin WordPress Integration Class
 *
 * This class handles all WordPress-specific integration functionality for the WebChange Detector plugin.
 * Extracted from the main admin class as part of the refactoring process to improve code organization
 * and maintainability following WordPress coding standards.
 *
 * @link       https://webchangedetector.com
 * @since      1.0.0
 *
 * @package    WebChangeDetector
 * @subpackage WebChangeDetector/admin
 */

namespace WebChangeDetector;

/**
 * The WordPress integration functionality of the plugin.
 *
 * Defines all functionality related to WordPress hooks, admin menus, script/style enqueuing,
 * admin bar integration, and post update handling for the WebChange Detector service.
 *
 * @package    WebChangeDetector
 * @subpackage WebChangeDetector/admin
 * @author     Mike Miler <mike@webchangedetector.com>
 * @since      1.0.0
 */
class WebChangeDetector_Admin_WordPress {


	/**
	 * The plugin name.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $plugin_name    The plugin name.
	 */
	private $plugin_name;

	/**
	 * The plugin version.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $version    The plugin version.
	 */
	private $version;

	/**
	 * The main admin instance.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      WebChangeDetector_Admin    $admin    The main admin instance.
	 */
	private $admin;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param    string                  $plugin_name    The plugin name.
	 * @param    string                  $version        The plugin version.
	 * @param    WebChangeDetector_Admin $admin          The main admin instance.
	 */
	public function __construct( $plugin_name, $version, $admin ) {
		$this->plugin_name = $plugin_name;
		$this->version     = $version;
		$this->admin       = $admin;
	}

	/**
	 * Register the stylesheets for the admin area.
	 *
	 * @since    1.0.0
	 * @return   void
	 */
	public function enqueue_styles() {
		wp_enqueue_style( 'jquery-ui-accordion' );
		wp_enqueue_style( $this->plugin_name, WCD_PLUGIN_URL . 'admin/css/webchangedetector-admin.css', array(), $this->version, 'all' );
		wp_enqueue_style( 'twentytwenty-css', WCD_PLUGIN_URL . 'admin/css/twentytwenty.css', array(), $this->version, 'all' );
		wp_enqueue_style( 'wp-codemirror' );
		wp_enqueue_style( 'codemirror-darcula', WCD_PLUGIN_URL . 'admin/css/darcula.css', array(), $this->version, 'all' );
		wp_enqueue_style( 'driver-css', WCD_PLUGIN_URL . 'admin/css/driver.css', array(), $this->version, 'all' );
	}

	/**
	 * Register the JavaScript for the admin area.
	 *
	 * @since    1.0.0
	 * @param    string $hook_suffix    The hook suffix for the current page.
	 * @return   void
	 */
	public function enqueue_scripts( $hook_suffix ) {
		if ( strpos( $hook_suffix, 'webchangedetector' ) !== false ) {
			wp_enqueue_script( $this->plugin_name, WCD_PLUGIN_URL . 'admin/js/webchangedetector-admin.js', array( 'jquery' ), $this->version, false );

			// Localize script for translations.
			wp_localize_script(
				$this->plugin_name,
				'wcdL10n',
				array(
					'unsavedChanges'           => __( 'Changes were not saved. Do you wish to leave the page without saving?', 'webchangedetector' ),
					'confirmResetAccount'      => __( 'Are you sure you want to reset your account? This cannot be undone.', 'webchangedetector' ),
					/* translators: %s: Settings type (e.g., "WordPress", "Manual", etc.) */
					'confirmOverwriteSettings' => __( 'Are you sure you want to overwrite the %s detection settings? This cannot be undone.', 'webchangedetector' ),
					'confirmCancelChecks'      => __( 'Are you sure you want to cancel the manual checks?', 'webchangedetector' ),
					'noTrackingsActive'        => __( 'No trackings active', 'webchangedetector' ),
					'currently'                => __( 'Currently', 'webchangedetector' ),
					'nextMonitoringChecks'     => __( 'Next monitoring checks in ', 'webchangedetector' ),
					'notTracking'              => __( 'Not Tracking', 'webchangedetector' ),
					'somethingWentWrong'       => __( 'Something went wrong. Please try again.', 'webchangedetector' ),
					'unexpectedResponse'       => __( 'Unexpected response from server. Please try again.', 'webchangedetector' ),
					'healthCheckSuccessful'    => __( 'Health check completed successfully.', 'webchangedetector' ),
					/* translators: %s: Error message from the health check */
					'healthCheckFailed'        => __( 'Health check failed: %s', 'webchangedetector' ),
					'healthCheckRequestFailed' => __( 'Health check request failed.', 'webchangedetector' ),
					'manualRecoveryAttempt'    => __( 'Manual recovery attempt', 'webchangedetector' ),
					'attemptingRecovery'       => __( 'Attempting Recovery...', 'webchangedetector' ),
					/* translators: %s: Recovery success message */
					'recoverySuccessful'       => __( 'Recovery successful: %s', 'webchangedetector' ),
					'recoveryComplete'         => __( 'Recovery Successful', 'webchangedetector' ),
					'confirmClearLogs'         => __( 'Are you sure you want to clear the logs? This action cannot be undone.', 'webchangedetector' ),
					'statusOk'                 => __( 'Ok', 'webchangedetector' ),
					'statusToFix'              => __( 'To Fix', 'webchangedetector' ),
					'statusFalsePositive'      => __( 'False Positive', 'webchangedetector' ),
					'statusFailed'             => __( 'Failed', 'webchangedetector' ),
					'statusNew'                => __( 'New', 'webchangedetector' ),
					'hour'                     => __( 'Hour', 'webchangedetector' ),
					'hours'                    => __( 'Hours', 'webchangedetector' ),
					'minute'                   => __( 'Minute', 'webchangedetector' ),
					'minutes'                  => __( 'Minutes', 'webchangedetector' ),
				)
			);

			wp_enqueue_script( 'jquery-ui-accordion' );
			wp_enqueue_script( 'twentytwenty-js', WCD_PLUGIN_URL . 'admin/js/jquery.twentytwenty.js', array( 'jquery' ), $this->version, false );
			wp_enqueue_script( 'twentytwenty-move-js', WCD_PLUGIN_URL . 'admin/js/jquery.event.move.js', array( 'jquery' ), $this->version, false );
			wp_enqueue_script( 'driver-js', WCD_PLUGIN_URL . 'admin/js/driver.js.iife.js', array(), $this->version, false );
			wp_enqueue_script( 'wcd-wizard', WCD_PLUGIN_URL . 'admin/js/wizard.js', array( 'jquery', 'driver-js' ), $this->version, false );
			// CodeMirror settings for CSS.
			$css_settings              = array(
				'type'       => 'text/css',
				'codemirror' => array(
					'theme'             => 'darcula',
					'mode'              => 'css',
					'lineNumbers'       => true,
					'autoCloseBrackets' => true,
					'matchBrackets'     => true,
					'styleActiveLine'   => true,
					'indentUnit'        => 2,
					'tabSize'           => 2,
				),
			);
			$cm_settings['codeEditor'] = wp_enqueue_code_editor( $css_settings );
			wp_localize_script( 'jquery', 'cm_settings', $cm_settings );
			wp_enqueue_script( 'wp-theme-plugin-editor' );

			wp_localize_script(
				'wcd-wizard',
				'wcdWizardData',
				array(
					'ajax_url'     => admin_url( 'admin-ajax.php' ),
					'nonce'        => wp_create_nonce( 'wcd_wizard_nonce' ),
					'translations' => array(
						// Navigation buttons.
						'nextBtnText'                 => __( 'Next →', 'webchangedetector' ),
						'prevBtnText'                 => __( '← Previous', 'webchangedetector' ),
						'doneBtnText'                 => __( 'Finish Wizard', 'webchangedetector' ),
						'closeBtnText'                => __( 'Exit Wizard', 'webchangedetector' ),
						/* translators: {{current}}: Current step number, {{total}}: Total number of steps */
						'progressText'                => __( 'Step {{current}} of {{total}}', 'webchangedetector' ),
						'finishTour'                  => __( 'Finish Tour →', 'webchangedetector' ),

						// Loading/navigation messages.
						'letsContinue'                => __( 'Let\'s continue on the next page.', 'webchangedetector' ),
						'loading'                     => __( 'Loading...', 'webchangedetector' ),
						'wizardComplete'              => __( 'Wizard Complete!', 'webchangedetector' ),
						'returningToDashboard'        => __( 'Returning to dashboard...', 'webchangedetector' ),

						// Dashboard steps.
						'welcomeTitle'                => __( 'Welcome to WebChange Detector', 'webchangedetector' ),
						'welcomeDesc'                 => __( 'WebChange Detector monitors your WordPress site for visual changes. It takes screenshots, compares them, and alerts you to any differences. Let\'s start the tour!', 'webchangedetector' ),
						'checkCreditsTitle'           => __( 'Your Check Credits', 'webchangedetector' ),
						'checkCreditsDesc'            => __( 'This shows your available checks and current usage. Monitor your usage to stay within limits. You will see warnings if the estimated amount of checks is higher than your credits.', 'webchangedetector' ),
						'recentChangesTitle'          => __( 'Recent Changes', 'webchangedetector' ),
						'recentChangesDesc'           => __( 'Your latest detected changes appear here. You\'ll see visual comparisons highlighting what changed on your site.', 'webchangedetector' ),

						// URL selection steps.
						'autoUpdateStatusTitle'       => __( 'Auto Update Checks Status', 'webchangedetector' ),
						'autoUpdateStatusDesc'        => __( 'This shows the current status of your WordPress auto-update checks. When enabled, WebChange Detector will automatically monitor your site before and after WordPress updates to detect any visual changes or issues.', 'webchangedetector' ),
						'manualChecksTitle'           => __( 'Manual Checks & Auto Update Settings', 'webchangedetector' ),
						'manualChecksDesc'            => __( 'You can start the Manual Checks here. But first, let\'s walk through each important setting.', 'webchangedetector' ),
						'enableAutoUpdateTitle'       => __( 'Enable Auto Update Checks', 'webchangedetector' ),
						'enableAutoUpdateDesc'        => __( 'Please turn this ON to enable automatic checks during WordPress auto-updates. This is required to continue the wizard. You can always turn it off later if you don\'t want to use it.', 'webchangedetector' ),
						'enabledAutoUpdatesTitle'     => __( 'Enabled Auto Updates', 'webchangedetector' ),
						'enabledAutoUpdatesDesc'      => __( 'Here you see a list of all enabled auto updates. Enable or disable the auto updates in the WordPress settings.', 'webchangedetector' ),
						'autoUpdateTimeframeTitle'    => __( 'Auto Update Timeframe', 'webchangedetector' ),
						'autoUpdateTimeframeDesc'     => __( 'Set the time window when WordPress is allowed to perform auto-updates. WebChange Detector will check your site during this period. For example: 2:00 AM - 4:00 AM when traffic is low.', 'webchangedetector' ),
						'weekdaySelectionTitle'       => __( 'Weekday Selection', 'webchangedetector' ),
						'weekdaySelectionDesc'        => __( 'Choose which days WordPress can perform auto-updates. Many prefer weekdays to avoid weekend issues, or specific days when support is available.', 'webchangedetector' ),
						'notificationEmailsTitle'     => __( 'Notification Emails', 'webchangedetector' ),
						'notificationEmailsDesc'      => __( 'Enter email addresses to receive notifications about auto-update check results. You can add multiple emails separated by commas.', 'webchangedetector' ),
						'changeThresholdTitle'        => __( 'Change Detection Threshold', 'webchangedetector' ),
						'changeThresholdDesc'         => __( 'Set the sensitivity for detecting changes (0-100%). Note: even small changes like 0.1% can be significant on long pages.', 'webchangedetector' ),
						'cssInjectionTitle'           => __( 'CSS Injection', 'webchangedetector' ),
						'cssInjectionDesc'            => __( 'Add custom CSS to hide dynamic elements before screenshots (like dates, counters, ads). Example: .dynamic-date { display: none !important; }', 'webchangedetector' ),
						'urlSelectionTitle'           => __( 'URL Selection Table', 'webchangedetector' ),
						'urlSelectionDesc'            => __( 'Select which pages to monitor. Toggle Desktop/Mobile options for each URL. Pro tip: Start with your most important pages like homepage, contact, and key product pages.', 'webchangedetector' ),
						'saveSettingsTitle'           => __( 'Save Your Settings', 'webchangedetector' ),
						'saveSettingsDesc'            => __( 'Don\'t forget to save! Your settings will be applied to both manual checks and auto-update monitoring.', 'webchangedetector' ),

						// Monitoring steps.
						'monitoringSettingsTitle'     => __( 'Automatic Monitoring Settings', 'webchangedetector' ),
						'monitoringSettingsDesc'      => __( 'Set up automatic monitoring to regularly check your website for unexpected changes. This is perfect for detecting hacks, broken layouts, or content issues.', 'webchangedetector' ),
						'enableMonitoringTitle'       => __( 'Enable Monitoring', 'webchangedetector' ),
						'enableMonitoringDesc'        => __( 'Please turn this ON to activate automatic monitoring. This is required to continue the wizard. Your selected pages will be checked regularly based on your schedule settings.', 'webchangedetector' ),
						'checkFrequencyTitle'         => __( 'Check Frequency', 'webchangedetector' ),
						'checkFrequencyDesc'          => __( 'How often should we check your site? Daily (24h) is recommended for most sites. High-traffic sites may want more frequent checks.', 'webchangedetector' ),
						'preferredCheckTimeTitle'     => __( 'Preferred Check Time', 'webchangedetector' ),
						'preferredCheckTimeDesc'      => __( 'Choose when checks should run. Pick a low-traffic time like 3 AM to minimize impact on visitors.', 'webchangedetector' ),
						'changeSensitivityTitle'      => __( 'Change Sensitivity', 'webchangedetector' ),
						'changeSensitivityDesc'       => __( 'Set how sensitive the monitoring should be. Note: even 0.1% changes can be significant on long pages.', 'webchangedetector' ),
						'alertRecipientsTitle'        => __( 'Alert Recipients', 'webchangedetector' ),
						'alertRecipientsDesc'         => __( 'Who should be notified when changes are detected? Add multiple emails separated by commas. Include your developer and key stakeholders.', 'webchangedetector' ),
						'cssCustomizationTitle'       => __( 'CSS Customization', 'webchangedetector' ),
						'cssCustomizationDesc'        => __( 'Hide dynamic content that changes frequently (timestamps, visitor counters, etc.) to avoid false positives in monitoring.', 'webchangedetector' ),
						'saveMonitoringTitle'         => __( 'Save Monitoring Settings', 'webchangedetector' ),
						'saveMonitoringDesc'          => __( 'Save your configuration to activate monitoring. Changes take effect immediately.', 'webchangedetector' ),
						'selectPagesToMonitorTitle'   => __( 'Select Pages to Monitor', 'webchangedetector' ),
						'selectPagesToMonitorDesc'    => __( 'Choose which pages to monitor automatically. Select your most critical pages - homepage, checkout, contact forms, and high-traffic content.', 'webchangedetector' ),

						// Change detection steps.
						'changeDetectionHistoryTitle' => __( 'Change Detection History', 'webchangedetector' ),
						'changeDetectionHistoryDesc'  => __( 'This is your change detection hub. View all detected changes with visual comparisons showing exactly what changed, when, and by how much.', 'webchangedetector' ),
						'detectionTableTitle'         => __( 'Detection Table', 'webchangedetector' ),
						'detectionTableDesc'          => __( 'Each row shows a detected change. Click on any row to see before/after screenshots with differences highlighted. The filters above help you find specific changes.', 'webchangedetector' ),
						'filterOptionsTitle'          => __( 'Filter Options', 'webchangedetector' ),
						'filterOptionsDesc'           => __( 'Use these filters to find specific changes by date, check type, status, or to show only changes with differences.', 'webchangedetector' ),

						// Logs steps.
						'activityLogsTitle'           => __( 'Activity Logs', 'webchangedetector' ),
						'activityLogsDesc'            => __( 'Track all WebChange Detector activities - scheduled checks, manual checks, API calls, and system events. Essential for troubleshooting.', 'webchangedetector' ),
						'logDetailsTitle'             => __( 'Log Details', 'webchangedetector' ),
						'logDetailsDesc'              => __( 'Each entry shows: timestamp, action type, status (success/error), and details. Green entries show successful operations, red indicates errors.', 'webchangedetector' ),

						// Settings steps.
						'urlManagementTitle'          => __( 'URL Management', 'webchangedetector' ),
						'urlManagementDesc'           => __( 'Control which content types appear in your URL list. Add custom post types, taxonomies, or WooCommerce products for monitoring.', 'webchangedetector' ),
						'urlSyncTitle'                => __( 'URL Synchronization', 'webchangedetector' ),
						'urlSyncDesc'                 => __( 'WebChange Detector syncs your site\'s URLs automatically. Use "Sync Now" after adding new content or if URLs are missing.', 'webchangedetector' ),
						'quickAccessTitle'            => __( 'Quick Access', 'webchangedetector' ),
						'quickAccessDesc'             => __( 'The admin bar menu provides quick access to WebChange Detector from your site\'s frontend. Disable if you prefer a cleaner toolbar.', 'webchangedetector' ),
						'apiConnectionTitle'          => __( 'API Connection', 'webchangedetector' ),
						'apiConnectionDesc'           => __( 'Your API token connects this site to WebChange Detector\'s screenshot service. Keep it secret and secure!', 'webchangedetector' ),
						'setupCompleteTitle'          => __( 'Setup Complete!', 'webchangedetector' ),
						'setupCompleteDesc'           => __( 'You\'re all set! WebChange Detector is now monitoring your site. Check the dashboard for updates and configure additional settings as needed.', 'webchangedetector' ),

						// Generic steps.
						'genericTitle'                => __( 'WebChange Detector', 'webchangedetector' ),
						'genericDesc'                 => __( 'Welcome to WebChange Detector! Use the navigation tabs to access different features.', 'webchangedetector' ),
						/* translators: %s: Account creation/validation/recovery message or error */
						'apiConnectionRestored'       => __( 'API connection restored: %s', 'webchangedetector' ),

						// Notification messages.
						'requiredSetting'             => __( 'Required Setting', 'webchangedetector' ),
						// translators: %s: Setting name.
						'requiredSettingMessage'      => __( 'Please enable <strong>%s</strong> to continue with the wizard. <br>You can disable this after finishing the wizard again.', 'webchangedetector' ),
						'autoUpdateChecks'            => __( 'Auto Update Checks', 'webchangedetector' ),
						'monitoring'                  => __( 'Monitoring', 'webchangedetector' ),
					),
				)
			);

			wp_localize_script(
				$this->plugin_name,
				'wcdAjaxData',
				array(
					'ajax_url'                  => admin_url( 'admin-ajax.php' ),
					'nonce'                     => \WebChangeDetector\WebChangeDetector_Admin_Utils::create_nonce( 'ajax-nonce' ),
					'take_screenshots_nonce'    => wp_create_nonce( 'take_screenshots' ),
					'start_manual_checks_nonce' => wp_create_nonce( 'start_manual_checks' ),
					'plugin_url'                => WCD_PLUGIN_URL . 'admin/',
				)
			);
		}
	}

	/**
	 * Register the JavaScript for the admin bar on the frontend.
	 *
	 * @since    1.0.0
	 * @return   void
	 */
	public function enqueue_admin_bar_scripts() {
		if ( get_option( 'wcd_disable_admin_bar_menu' ) ) {
			return;
		}

		if ( is_admin_bar_showing() && ! is_admin() && current_user_can( 'manage_options' ) ) {
			$admin_bar_script_handle = 'webchangedetector-admin-bar';

			// Enqueue JavaScript.
			wp_enqueue_script( $admin_bar_script_handle, WCD_PLUGIN_URL . 'admin/js/webchangedetector-admin-bar.js', array( 'jquery' ), $this->version, true );

			wp_localize_script(
				$admin_bar_script_handle,
				'wcdAdminBarData',
				array(
					'ajax_url'              => admin_url( 'admin-ajax.php' ),
					'nonce'                 => wp_create_nonce( 'wcd_admin_bar_nonce' ),
					'postUrlNonce'          => \WebChangeDetector\WebChangeDetector_Admin_Utils::create_nonce( 'ajax-nonce' ),
					'action'                => 'wcd_get_admin_bar_status',
					'loading_text'          => __( 'Loading WCD Status...', 'webchangedetector' ),
					'error_text'            => __( 'Error loading status.', 'webchangedetector' ),
					'not_tracked_text'      => __( 'URL not tracked by WCD', 'webchangedetector' ),
					'manual_label'          => __( 'Manual / Auto Update Checks', 'webchangedetector' ),
					'monitoring_label'      => __( 'Monitoring', 'webchangedetector' ),
					'desktop_label'         => __( 'Desktop', 'webchangedetector' ),
					'mobile_label'          => __( 'Mobile', 'webchangedetector' ),
					'dashboard_label'       => __( 'WCD Dashboard', 'webchangedetector' ),
					'dashboard_url'         => admin_url( 'admin.php?page=webchangedetector' ),
					'error_missing_data'    => __( 'Error: Missing data needed to save the change.', 'webchangedetector' ),
					'error_config_missing'  => __( 'Error: Configuration data missing. Cannot save change.', 'webchangedetector' ),
					'failed_update_setting' => __( 'Failed to update setting. Please try again.', 'webchangedetector' ),
				)
			);
		}
	}

	/**
	 * Add WebChange Detector to backend navigation.
	 *
	 * @since    1.0.0
	 * @return   void
	 */
	public function wcd_plugin_setup_menu() {
		require_once 'partials/webchangedetector-admin-display.php';
		$allowances = get_option( WCD_ALLOWANCES );

		add_menu_page( __( 'WebChange Detector', 'webchangedetector' ), __( 'WebChange Detector', 'webchangedetector' ), 'manage_options', 'webchangedetector', 'wcd_webchangedetector_init', WCD_PLUGIN_URL . 'admin/img/icon-wp-backend.svg' );
		add_submenu_page( 'webchangedetector', __( 'Dashboard', 'webchangedetector' ), __( 'Dashboard', 'webchangedetector' ), 'manage_options', 'webchangedetector', 'wcd_webchangedetector_init' );

		if ( is_array( $allowances ) && $allowances['change_detections_view'] ) {
			add_submenu_page( 'webchangedetector', __( 'Change Detections', 'webchangedetector' ), __( 'Change Detections', 'webchangedetector' ), 'manage_options', 'webchangedetector-change-detections', 'wcd_webchangedetector_init' );
		}
		if ( is_array( $allowances ) && $allowances['manual_checks_view'] ) {
			add_submenu_page( 'webchangedetector', __( 'Auto Update Checks & Manual Checks', 'webchangedetector' ), __( 'Auto Update Checks & Manual Checks', 'webchangedetector' ), 'manage_options', 'webchangedetector-update-settings', 'wcd_webchangedetector_init' );
		}
		if ( is_array( $allowances ) && $allowances['monitoring_checks_view'] ) {
			add_submenu_page( 'webchangedetector', __( 'Monitoring', 'webchangedetector' ), __( 'Monitoring', 'webchangedetector' ), 'manage_options', 'webchangedetector-auto-settings', 'wcd_webchangedetector_init' );
		}
		if ( is_array( $allowances ) && $allowances['logs_view'] ) {
			add_submenu_page( 'webchangedetector', __( 'Logs', 'webchangedetector' ), __( 'Logs', 'webchangedetector' ), 'manage_options', 'webchangedetector-logs', 'wcd_webchangedetector_init' );
		}
		if ( is_array( $allowances ) && $allowances['settings_view'] ) {
			add_submenu_page( 'webchangedetector', __( 'Settings', 'webchangedetector' ), __( 'Settings', 'webchangedetector' ), 'manage_options', 'webchangedetector-settings', 'wcd_webchangedetector_init' );
		}

		// Hidden submenu pages (not visible in menu but accessible via URL).
		if ( is_array( $allowances ) && $allowances['change_detections_view'] ) {
			add_submenu_page( null, __( 'Show Detection', 'webchangedetector' ), __( 'Show Detection', 'webchangedetector' ), 'manage_options', 'webchangedetector-show-detection', 'wcd_webchangedetector_init' );
			add_submenu_page( null, __( 'Show Screenshot', 'webchangedetector' ), __( 'Show Screenshot', 'webchangedetector' ), 'manage_options', 'webchangedetector-show-screenshot', 'wcd_webchangedetector_init' );
		}
	}

	/**
	 * Get the WebChange Detector plugin URL.
	 *
	 * @since    1.0.0
	 * @return   string    The plugin URL.
	 */
	public static function get_wcd_plugin_url() {
		return WCD_PLUGIN_URL;
	}

	/**
	 * Handle post updates for URL synchronization.
	 *
	 * @since    1.0.0
	 * @param    int     $post_id      The post ID.
	 * @param    WP_Post $post_after   The post after update.
	 * @param    WP_Post $post_before  The post before update.
	 * @return   void
	 */
	public function update_post( $post_id, $post_after, $post_before ) {
		if ( wp_is_post_revision( $post_id ) || wp_is_post_autosave( $post_id ) || 'publish' !== $post_after->post_status ) {
			return;
		}

		$post_after_title      = get_the_title( $post_after );
		$post_before_title     = get_the_title( $post_before );
		$post_after_permalink  = get_permalink( $post_after );
		$post_before_permalink = get_permalink( $post_before );

		if ( $post_after_title === $post_before_title && $post_after_permalink === $post_before_permalink ) {
			return;
		}

		$post_type       = get_post_type_object( $post_after->post_type );
		$post_category   = \WebChangeDetector\WebChangeDetector_Admin_Utils::get_post_type_name( \WebChangeDetector\WebChangeDetector_Admin_Utils::get_post_type_slug( $post_type ) );
		$post_title      = get_the_title( $post_id );
		$post_before_url = get_permalink( $post_before );
		$post_after_url  = get_permalink( $post_after );

		$website_details = $this->admin->settings_handler->get_website_details();
		$to_sync         = false;

		// Get the post type slug for comparison.
		$post_type_slug = \WebChangeDetector\WebChangeDetector_Admin_Utils::get_post_type_slug( $post_type );

		foreach ( $website_details['sync_url_types'] as $sync_url_type ) {
			if ( $post_type_slug === $sync_url_type['post_type_slug'] ) {
				$to_sync = true;
				break;
			}
		}

		if ( ! $to_sync ) {
			return;
		}

		$data[][ 'types%%' . $post_category ][] = array(
			'html_title' => $post_title,
			'url'        => \WebChangeDetector\WebChangeDetector_Admin_Utils::remove_url_protocol( $post_before_url ),
			'new_url'    => \WebChangeDetector\WebChangeDetector_Admin_Utils::remove_url_protocol( $post_after_url ),
		);

		// Schedule async single post sync instead of blocking sync.
		$this->schedule_async_single_post_sync( $data );
	}

	/**
	 * Sync posts after save - WordPress hook handler.
	 *
	 * @since    1.0.0
	 * @param    int     $post_id The post ID.
	 * @param    WP_Post $post    The post object.
	 * @param    bool    $update  Whether this is an existing post being updated.
	 * @return   bool    True on success.
	 */
	public function wcd_sync_post_after_save( $post_id = null, $post = null, $update = false ) {
		// Skip if this is an update (handled by update_post hook) or if post is not published.
		if ( $update || ! $post || 'publish' !== $post->post_status ) {
			return true;
		}

		// Skip revisions and autosaves.
		if ( wp_is_post_revision( $post_id ) || wp_is_post_autosave( $post_id ) ) {
			return true;
		}

		// Get post details for single post sync.
		$post_type     = get_post_type_object( $post->post_type );
		$post_category = \WebChangeDetector\WebChangeDetector_Admin_Utils::get_post_type_name( \WebChangeDetector\WebChangeDetector_Admin_Utils::get_post_type_slug( $post_type ) );
		$post_title    = get_the_title( $post_id );
		$post_url      = get_permalink( $post_id );

		// Check if this post type should be synced.
		$website_details = $this->admin->settings_handler->get_website_details();
		$to_sync         = false;

		// Get both the WordPress post type name and the rest_base for comparison.
		$wp_post_type_name = $post_type->name; // WordPress internal name (e.g., "product").
		$wp_rest_base      = \WebChangeDetector\WebChangeDetector_Admin_Utils::get_post_type_slug( $post_type ); // rest_base (e.g., "products").

		foreach ( $website_details['sync_url_types'] as $sync_url_type ) {
			// Check if the stored slug matches either the WordPress name or the rest_base.
			if (
				$wp_post_type_name === $sync_url_type['post_type_slug'] ||
				$wp_rest_base === $sync_url_type['post_type_slug']
			) {
				$to_sync = true;
				break;
			}
		}

		if ( ! $to_sync ) {
			\WebChangeDetector\WebChangeDetector_Admin_Utils::log_error( 'Post type not configured for sync: ' . $post_category, 'update_post', 'debug' );
			return true;
		}

		// Build data structure for single post sync.
		$data[][ 'types%%' . $post_category ][] = array(
			'html_title' => $post_title,
			'url'        => \WebChangeDetector\WebChangeDetector_Admin_Utils::remove_url_protocol( $post_url ),
		);

		\WebChangeDetector\WebChangeDetector_Admin_Utils::log_error( 'New post published, scheduling single post sync: ' . $post_id, 'wcd_sync_post_after_save', 'debug' );
		// Schedule async single post sync instead of full sync.
		$this->schedule_async_single_post_sync( $data );
		return true;
	}

	/**
	 * Daily synchronization cron job handler.
	 *
	 * @since    4.0.0
	 * @return   void
	 */
	public function daily_sync_posts_cron_job() {
		// Sync posts.
		$this->sync_posts( true );

		// Cleanup old logs daily instead of randomly.
		$logger  = new \WebChangeDetector\WebChangeDetector_Database_Logger();
		$deleted = $logger->cleanup_old_logs();
		if ( $deleted > 0 ) {
			\WebChangeDetector\WebChangeDetector_Admin_Utils::log_error( "Daily log cleanup: deleted {$deleted} old log entries", 'daily_sync_posts_cron_job', 'info' );
		}
	}

	/**
	 * Schedule async single post sync.
	 *
	 * @since    1.0.0
	 * @param    array $data The post data to sync.
	 * @return   bool  True if scheduled successfully.
	 */
	public function schedule_async_single_post_sync( $data ) {
		// Use a unique hook name to avoid conflicts.
		$hook = 'wcd_async_single_post_sync';

		// Schedule single event to run in 10 seconds to ensure proper execution.
		$scheduled = wp_schedule_single_event( time() + 10, $hook, array( $data ) );

		if ( false !== $scheduled ) {
			\WebChangeDetector\WebChangeDetector_Admin_Utils::log_error( 'Scheduled async single post sync to run in 10 seconds', 'schedule_async_single_post_sync', 'debug' );
			return true;
		} else {
			\WebChangeDetector\WebChangeDetector_Admin_Utils::log_error( 'Failed to schedule async single post sync', 'schedule_async_single_post_sync', 'error' );
			return false;
		}
	}

	/**
	 * Schedule async full sync.
	 *
	 * @since    1.0.0
	 * @param    bool $force_sync Whether to force sync.
	 * @return   bool True if scheduled successfully.
	 */
	public function schedule_async_full_sync( $force_sync = false ) {
		// Use a unique hook name to avoid conflicts.
		$hook = 'wcd_async_full_sync';

		// Schedule single event to run in 1 second to ensure proper execution.
		$scheduled = wp_schedule_single_event( time() + 1, $hook, array( $force_sync ) );

		if ( false !== $scheduled ) {
			\WebChangeDetector\WebChangeDetector_Admin_Utils::log_error( 'Scheduled async full sync (force: ' . ( $force_sync ? 'yes' : 'no' ) . ') to run in 10 seconds', 'schedule_async_full_sync', 'debug' );
			return true;
		} else {
			\WebChangeDetector\WebChangeDetector_Admin_Utils::log_error( 'Failed to schedule async full sync', 'schedule_async_full_sync', 'error' );
			return false;
		}
	}

	/**
	 * Async single post sync cron handler.
	 *
	 * @since    1.0.0
	 * @param    array $data The post data to sync.
	 * @return   void
	 */
	public function async_single_post_sync_handler( $data ) {
		\WebChangeDetector\WebChangeDetector_Admin_Utils::log_error( 'Executing async single post sync', 'async_single_post_sync_handler', 'debug' );
		$this->sync_single_post( $data );
	}

	/**
	 * Async full sync cron handler.
	 *
	 * @since    1.0.0
	 * @param    bool $force_sync Whether to force sync.
	 * @return   void
	 */
	public function async_full_sync_handler( $force_sync = false ) {
		\WebChangeDetector\WebChangeDetector_Admin_Utils::log_error( 'Executing async full sync (force: ' . ( $force_sync ? 'yes' : 'no' ) . ')', 'async_full_sync_handler', 'debug' );
		$this->sync_posts( $force_sync );
	}

	/**
	 * Add items to the WordPress admin bar.
	 *
	 * @since    1.0.0
	 * @param    WP_Admin_Bar $wp_admin_bar    WP_Admin_Bar instance.
	 * @return   void
	 */
	public function wcd_admin_bar_menu( $wp_admin_bar ) {
		if ( get_option( 'wcd_disable_admin_bar_menu' ) ) {
			return;
		}

		if ( ! is_admin() && is_admin_bar_showing() && current_user_can( 'manage_options' ) ) {
			$icon_url  = WCD_PLUGIN_URL . 'admin/img/icon-wp-backend.svg';
			$wcd_title = sprintf( '<span style="float:left; margin-right: 5px;"><img src="%s" style="width: 20px; height: 20px; vertical-align: middle;" /></span>%s', esc_url( $icon_url ), esc_html__( 'WebChange Detector', 'webchangedetector' ) );

			$wp_admin_bar->add_menu(
				array(
					'id'    => 'wcd-admin-bar',
					'title' => $wcd_title,
					'href'  => admin_url( 'admin.php?page=webchangedetector' ),
					'meta'  => array( 'title' => __( 'WebChange Detector Dashboard', 'webchangedetector' ) ),
				)
			);

			// Add placeholder that will be replaced by JavaScript with actual URL status.
			$wp_admin_bar->add_menu(
				array(
					'parent' => 'wcd-admin-bar',
					'id'     => 'wcd-status-placeholder',
					'title'  => esc_html__( 'Loading...', 'webchangedetector' ),
					'meta'   => array( 'title' => __( 'Current page monitoring status', 'webchangedetector' ) ),
				)
			);

			$wp_admin_bar->add_menu(
				array(
					'parent' => 'wcd-admin-bar',
					'id'     => 'wcd-dashboard',
					'title'  => esc_html__( 'Dashboard', 'webchangedetector' ),
					'href'   => admin_url( 'admin.php?page=webchangedetector' ),
					'meta'   => array( 'title' => __( 'Go to WebChange Detector Dashboard', 'webchangedetector' ) ),
				)
			);
		}
	}

	/**
	 * AJAX handler for admin bar status.
	 *
	 * @since    1.0.0
	 * @return   void
	 */
	public function ajax_get_wcd_admin_bar_status() {
		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'wcd_admin_bar_nonce' ) ) {
			wp_send_json_error( array( 'message' => __( 'Invalid nonce.', 'webchangedetector' ) ), 403 );
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'webchangedetector' ) ), 403 );
		}

		$current_url = isset( $_POST['current_url'] ) ? esc_url_raw( wp_unslash( $_POST['current_url'] ) ) : '';

		if ( empty( $current_url ) ) {
			wp_send_json_error( array( 'message' => __( 'No URL provided.', 'webchangedetector' ) ), 400 );
		}

		$url_without_protocol = \WebChangeDetector\WebChangeDetector_Admin_Utils::remove_url_protocol( $current_url );
		$status_data          = $this->get_url_monitoring_status( $url_without_protocol );

		if ( $status_data ) {
			wp_send_json_success( $status_data );
		} else {
			wp_send_json_success(
				array(
					'tracked' => false,
					'message' => __( 'URL not tracked by WebChange Detector', 'webchangedetector' ),
				)
			);
		}
	}

	/**
	 * Get monitoring status for a specific URL.
	 *
	 * @since    1.0.0
	 * @param    string $url    The URL to check status for.
	 * @return   array|false       Status data or false if not found.
	 */
	private function get_url_monitoring_status( $url ) {
		// Get group UUIDs from the admin instance.
		$manual_group_uuid     = $this->admin->manual_group_uuid;
		$monitoring_group_uuid = $this->admin->monitoring_group_uuid;

		if ( ! $manual_group_uuid || ! $monitoring_group_uuid ) {
			\WebChangeDetector\WebChangeDetector_Admin_Utils::log_error( '[WCD Admin Bar] Group UUIDs not found - Manual: ' . $manual_group_uuid . ', Monitoring: ' . $monitoring_group_uuid, 'get_url_monitoring_status', 'error' );
			return false;
		}

		// Create URL filter to search for this specific URL.
		$url_filter = array(
			'url' => $url,
		);

		\WebChangeDetector\WebChangeDetector_Admin_Utils::log_error( '[WCD Admin Bar] Searching for URL: ' . $url, 'get_url_monitoring_status', 'debug' );

		// Search in both groups for the URL.
		$manual_group_urls     = \WebChangeDetector\WebChangeDetector_API_V2::get_group_urls_v2( $manual_group_uuid, $url_filter );
		$monitoring_group_urls = \WebChangeDetector\WebChangeDetector_API_V2::get_group_urls_v2( $monitoring_group_uuid, $url_filter );

		// Check if URL exists in either group.
		$manual_urls     = $manual_group_urls['data'] ?? array();
		$monitoring_urls = $monitoring_group_urls['data'] ?? array();

		if ( empty( $manual_urls ) && empty( $monitoring_urls ) ) {
			\WebChangeDetector\WebChangeDetector_Admin_Utils::log_error( '[WCD Admin Bar] URL not found in any group: ' . $url, 'get_url_monitoring_status', 'error' );
			return false;
		}

		// Find the URL data.
		$manual_url_data     = null;
		$monitoring_url_data = null;
		$wcd_url_id          = null;

		// First, try to find exact URL matches.
		foreach ( $manual_urls as $url_data ) {
			if ( isset( $url_data['url'] ) && \WebChangeDetector\WebChangeDetector_Admin_Utils::remove_url_protocol( $url_data['url'] ) === $url ) {
				$manual_url_data = $url_data;
				if ( isset( $url_data['id'] ) ) {
					$wcd_url_id = $url_data['id'];
				}
				break;
			}
		}

		foreach ( $monitoring_urls as $url_data ) {
			if ( isset( $url_data['url'] ) && \WebChangeDetector\WebChangeDetector_Admin_Utils::remove_url_protocol( $url_data['url'] ) === $url ) {
				$monitoring_url_data = $url_data;
				if ( ! $wcd_url_id && isset( $url_data['id'] ) ) {
					$wcd_url_id = $url_data['id'];
				}
				break;
			}
		}

		// Fallback: If we still don't have a URL ID but we have URL data, use the first available ID.
		if ( ! $wcd_url_id ) {
			\WebChangeDetector\WebChangeDetector_Admin_Utils::log_error( '[WCD Admin Bar] No exact URL match found, using fallback logic for URL: ' . $url, 'get_url_monitoring_status', 'debug' );
			// Try to get ID from first manual URL.
			if ( ! empty( $manual_urls ) && isset( $manual_urls[0]['id'] ) ) {
				$wcd_url_id      = $manual_urls[0]['id'];
				$manual_url_data = $manual_urls[0];
				\WebChangeDetector\WebChangeDetector_Admin_Utils::log_error( '[WCD Admin Bar] Using first manual URL ID: ' . $wcd_url_id, 'get_url_monitoring_status', 'debug' );
			} elseif ( ! empty( $monitoring_urls ) && isset( $monitoring_urls[0]['id'] ) ) {
				// If not found in manual, try monitoring group.
				$wcd_url_id          = $monitoring_urls[0]['id'];
				$monitoring_url_data = $monitoring_urls[0];
				\WebChangeDetector\WebChangeDetector_Admin_Utils::log_error( '[WCD Admin Bar] Using first monitoring URL ID: ' . $wcd_url_id, 'get_url_monitoring_status', 'debug' );
			}
		}

		// Final check: Make sure we have a URL ID.
		if ( ! $wcd_url_id ) {
			\WebChangeDetector\WebChangeDetector_Admin_Utils::log_error( '[WCD Admin Bar] ERROR: No URL ID found for URL: ' . $url . ' - Manual URLs: ' . wp_json_encode( $manual_urls ) . ' - Monitoring URLs: ' . wp_json_encode( $monitoring_urls ), 'get_url_monitoring_status', 'error' );
			return false;
		}

		// Build response data structure expected by the JavaScript.
		$response_data = array(
			'tracked'               => true,
			'current_url'           => $url,
			'wcd_url_id'            => $wcd_url_id,
			'manual_group_uuid'     => $manual_group_uuid,
			'monitoring_group_uuid' => $monitoring_group_uuid,
			'manual_status'         => array(
				'desktop' => $manual_url_data ? (bool) ( $manual_url_data['desktop'] ?? false ) : false,
				'mobile'  => $manual_url_data ? (bool) ( $manual_url_data['mobile'] ?? false ) : false,
			),
			'monitoring_status'     => array(
				'desktop' => $monitoring_url_data ? (bool) ( $monitoring_url_data['desktop'] ?? false ) : false,
				'mobile'  => $monitoring_url_data ? (bool) ( $monitoring_url_data['mobile'] ?? false ) : false,
			),
		);

		\WebChangeDetector\WebChangeDetector_Admin_Utils::log_error( '[WCD Admin Bar] Response data: ' . wp_json_encode( $response_data ), 'get_url_monitoring_status', 'debug' );

		return $response_data;
	}

	/**
	 * Add post type to website sync settings.
	 *
	 * @since    1.0.0
	 * @param    array $postdata    The post data containing post type information.
	 * @return   void
	 */
	public function add_post_type( $postdata ) {
		$post_type                                      = json_decode( $postdata['post_type'], true );
		$this->admin->website_details['sync_url_types'] = array_merge( $post_type, $this->admin->website_details['sync_url_types'] );

		// TODO: Move to settings handler.
		$website_details = \WebChangeDetector\WebChangeDetector_API_V2::update_website_v2( $this->admin->website_details['id'], $this->admin->website_details );
		if ( isset( $website_details['data'] ) && ! empty( $website_details['data']['sync_url_types'] ) ) {
			$this->admin->website_details = $website_details['data'];
			$this->sync_posts( true );
		}
	}

	/**
	 * Get posts by post type.
	 *
	 * @since    1.0.0
	 * @param    string $posttype    The post type.
	 * @return   int[]|WP_Post[]
	 */
	public function get_posts( $posttype ) {
		$args           = array(
			'post_type'        => $posttype,
			'post_status'      => array( 'publish', 'inherit' ),
			'numberposts'      => -1,
			'order'            => 'ASC',
			'orderby'          => 'title',
			'suppress_filters' => false, // need this for wpml to work.
		);
		$wpml_languages = $this->get_wpml_languages();

		if ( ! $wpml_languages ) {
			\WebChangeDetector\WebChangeDetector_Admin_Utils::log_error( 'No WPML languages found, getting posts without WPML.', 'get_posts', 'debug' );
			$posts = get_posts( $args );
		} else {
			\WebChangeDetector\WebChangeDetector_Admin_Utils::log_error( 'WPML languages found, getting posts with WPML.', 'get_posts', 'debug' );
			$posts = array();
			foreach ( $wpml_languages['languages'] as $language_code ) {
				do_action( 'wpml_switch_language', $language_code );
				$posts = array_merge( $posts, get_posts( $args ) );
				\WebChangeDetector\WebChangeDetector_Admin_Utils::log_error( 'Posts: ' . wp_json_encode( $posts ), 'get_posts', 'debug' );
			}
			do_action( 'wpml_switch_language', $wpml_languages['current_language'] );
		}

		return $this->filter_unique_posts_by_id( $posts );
	}

	/**
	 * Filter duplicate post IDs.
	 *
	 * @since    1.0.0
	 * @param    array $posts    The posts array.
	 * @return   array
	 */
	public function filter_unique_posts_by_id( $posts ) {
		$unique_posts = array();
		$post_ids     = array();

		foreach ( $posts as $post ) {
			unset( $post->post_content ); // Don't need to send too much unnecessary data.
			if ( ! in_array( $post->ID, $post_ids, true ) ) {
				$post_ids[]     = $post->ID;
				$unique_posts[] = $post;
			}
		}

		return $unique_posts;
	}

	/**
	 * Filter duplicate terms.
	 *
	 * @since    1.0.0
	 * @param    array $terms    The terms array.
	 * @return   array
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
	 * Get terms by taxonomy.
	 *
	 * @since    1.0.0
	 * @param    string $taxonomy    The taxonomy.
	 * @return   array|int[]|string|string[]|WP_Error|WP_Term[]
	 */
	public function get_terms( $taxonomy ) {
		$args = array(
			'number'           => '0',
			'taxonomy'         => $taxonomy,
			'hide_empty'       => false,
			'suppress_filters' => false, // need this for wpml to work.
		);

		// Get terms for all languages if WPML is enabled.
		$wpml_languages = $this->get_wpml_languages();

		// If we don't have languages, we can return the terms.
		if ( ! $wpml_languages ) {
			$terms = get_terms( $args );
		} else {
			// With languages, we loop through them and return all of them.
			$terms = array();
			foreach ( $wpml_languages['languages'] as $language_code ) {
				do_action( 'wpml_switch_language', $language_code );
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
	 * @return   array|false
	 */
	public function get_wpml_languages() {

		if ( ! class_exists( 'SitePress' ) ) {
			return false;
		}
		$wpml_languages = apply_filters( 'wpml_active_languages', null );
		if ( empty( $wpml_languages ) ) {
			return false;
		}

		// Get just the language codes.
		$languages        = array_keys( (array) $wpml_languages );
		$current_language = apply_filters( 'wpml_current_language', null );

		return array(
			'current_language' => $current_language,
			'languages'        => $languages,
		);
	}

	/**
	 * Check if WPML is active.
	 *
	 * @since    4.0.0
	 * @return   bool
	 */
	public function wpml_is_active() {
		return class_exists( 'SitePress' );
	}

	/**
	 * Check if Polylang is active.
	 *
	 * @since    4.0.0
	 * @return   bool
	 */
	public function polylang_is_active() {
		return class_exists( 'Polylang' );
	}

	/**
	 * Get all posts data for synchronization.
	 *
	 * @since    1.0.0
	 * @param    array $post_types    The post types to get.
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
				\WebChangeDetector\WebChangeDetector_Admin_Utils::log_error( 'Getting next chunk. Offset: ' . $offset, 'get_all_posts_data', 'debug' );
				// Set up WP_Query arguments.
				$args = array(
					'post_type'      => $single_post_type,  // Pass the array of post types.
					'post_status'    => 'publish',
					'posts_per_page' => $posts_per_batch,  // Fetch 1000 posts at a time.
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
				\WebChangeDetector\WebChangeDetector_Admin_Utils::log_error( 'Sending Posts.', 'get_all_posts_data', 'debug' );

				// Call uploadUrls after every batch.
				$this->upload_urls_in_batches( $all_posts_data );

				// Clear the data array after each batch to free memory.
				$all_posts_data = array();

				// Get the count of the results.
				$count = $query->found_posts;

				// If we've processed all posts, break the loop.
				if ( $offset >= $count ) {
					break;
				}
			} while ( true );
		}
	}

	/**
	 * Get all terms data for synchronization.
	 *
	 * @since    1.0.0
	 * @param    array $taxonomies    The taxonomies to get.
	 * @return   void
	 */
	public function get_all_terms_data( $taxonomies ) {
		// Array to store all terms data.
		$all_terms_data = array();

		if ( empty( $taxonomies ) ) {
			return;
		}

		$batch_size  = 500;  // Limit each batch to 500 terms.
		$offset      = 0;    // Initial offset to start from.
		$total_terms = true; // Placeholder to control loop.

		// Continue fetching terms until no more terms are found.
		while ( $total_terms ) {
			// Get terms in batches of 500 with an offset.
			$terms = get_terms(
				array(
					'taxonomy'   => $taxonomies, // Pass the taxonomies as an array.
					'hide_empty' => false,       // Show all terms, including those with no posts.
					'fields'     => 'all',       // Retrieve all term fields (term_id, name, slug, etc.).
					'number'     => $batch_size, // Fetch only 500 terms at a time.
					'offset'     => $offset,     // Offset to start from for each batch.
				)
			);

			// Check for errors or empty result.
			if ( is_wp_error( $terms ) || empty( $terms ) ) {
				// Stop the loop if no terms are found.
				$total_terms = false;
				continue;
			}

			// Process each term in the current batch.
			foreach ( $terms as $term ) {
				// Retrieve the term link (URL).
				$url = get_term_link( (int) $term->term_id, $term->taxonomy );

				// Retrieve the taxonomy object to get the label.
				$taxonomy_object = get_taxonomy( $term->taxonomy );
				$taxonomy_label  = $taxonomy_object ? $taxonomy_object->labels->name : $term->taxonomy;

				// Add the data to the main array.
				$all_terms_data[ 'taxonomy%%' . $taxonomy_label ][] = array(
					'url'        => \WebChangeDetector\WebChangeDetector_Admin_Utils::remove_url_protocol( $url ),
					'html_title' => $term->name,
				);
			}

			// Increment the offset for the next batch.
			$offset += $batch_size;

			// Call uploadUrls in batches of 500 elements.
			// Pass the entire $all_terms_data for each batch.
			$this->upload_urls_in_batches( $all_terms_data );

			// Reset the all_terms_data array after each batch to avoid memory overflow.
			$all_terms_data = array();
		}
	}

	/**
	 * Upload URLs in batches to the WebChange Detector service.
	 *
	 * @since    1.0.0
	 * @param    array $upload_array    The URLs to upload.
	 * @return   bool|string    True on success, error message on failure.
	 */
	public function upload_urls_in_batches( $upload_array ) {
		if ( ! empty( $upload_array ) ) {
			$this->admin->sync_urls[] = $upload_array;
		}
		return true;
	}

	/**
	 * Sync single post.
	 *
	 * @since    1.0.0
	 * @param    array $single_post    The sync array.
	 * @return   void
	 */
	public function sync_single_post( $single_post ) {
		if ( ! empty( $single_post ) ) {
			\WebChangeDetector\WebChangeDetector_Admin_Utils::log_error( 'Start single url sync', 'sync_single_post', 'debug' );
			$collection_uuid         = wp_generate_uuid4();
			$response_sync_urls      = \WebChangeDetector\WebChangeDetector_API_V2::sync_urls( $single_post, $collection_uuid );
			$response_start_url_sync = \WebChangeDetector\WebChangeDetector_API_V2::start_url_sync( false, $collection_uuid );
			\WebChangeDetector\WebChangeDetector_Admin_Utils::log_error( 'Response upload URLs: ' . $response_sync_urls, 'sync_single_post', 'debug' );
			\WebChangeDetector\WebChangeDetector_Admin_Utils::log_error( 'Response Start URL sync: ' . $response_start_url_sync, 'sync_single_post', 'debug' );
		}
	}

	/**
	 * Sync posts with API.
	 *
	 * @since    1.0.0
	 * @param    bool       $force_sync        Skip cache and force sync.
	 * @param    array|bool $website_details   The website details or false.
	 * @return   bool
	 */
	public function sync_posts( $force_sync = false, $website_details = false ) {
		$last_sync     = get_option( 'wcd_last_urls_sync' );
		$sync_interval = '+1 hour';

		// Skip sync if last sync is less than sync interval.
		if ( $last_sync && ! $force_sync && strtotime( $sync_interval, $last_sync ) >= date_i18n( 'U' ) ) {
			// Returning last sync datetime.
			return date_i18n( 'd.m.Y H:i', $last_sync );
		}

		\WebChangeDetector\WebChangeDetector_Admin_Utils::log_error( 'Starting Sync', 'sync_posts', 'debug' );
		update_option( 'wcd_last_urls_sync', date_i18n( 'U' ) );

		// Clear any existing sync URLs to start fresh.
		$this->admin->sync_urls = array();

		// Check if we got website_details or if we use the ones from the class.
		$array = array(); // init.
		if ( ! $website_details ) {
			$website_details = $this->admin->settings_handler->get_website_details();
		}

		// We only sync the frontpage.
		if ( ! empty( $website_details['allowances']['only_frontpage'] ) ) {
			\WebChangeDetector\WebChangeDetector_Admin_Utils::log_error( 'only frontpage: ' . wp_json_encode( $website_details['allowances']['only_frontpage'] ), 'sync_posts', 'debug' );
			$array['frontpage%%Frontpage'][] = array(
				'url'        => WebChangeDetector_Admin_Utils::get_domain_from_site_url(),
				'html_title' => get_bloginfo( 'name' ),
			);
			$this->upload_urls_in_batches( $array );
			return true;
		}

		// Init sync urls if we don't have them yet.
		if ( ! empty( $website_details['sync_url_types'] ) ) {

			// Get all WP post_types.
			$post_types      = get_post_types( array( 'public' => true ), 'objects' );
			$post_type_names = array(); // Initialize array.
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
				\WebChangeDetector\WebChangeDetector_Admin_Utils::log_error( 'Syncing post types: ' . implode( ', ', $post_type_names ), 'sync_posts', 'debug' );

				if ( $this->wpml_is_active() ) {
					$wpml_languages = $this->get_wpml_languages();
					foreach ( $wpml_languages['languages'] as $lang_code ) {
						do_action( 'wpml_switch_language', $lang_code );
						$this->get_all_posts_data( $post_type_names );
					}
					do_action( 'wpml_switch_language', $wpml_languages['current_language'] );
				} elseif ( $this->polylang_is_active() && function_exists( 'pll_languages_list' ) ) {
					// Get all Polylang language codes.
					$polylang_languages = pll_languages_list();
					$current_lang       = function_exists( 'pll_current_language' ) ? pll_current_language() : false;

					if ( ! empty( $polylang_languages ) ) {
						foreach ( $polylang_languages as $lang_code ) {
							// Switch to each language.
							if ( function_exists( 'PLL' ) ) {
								PLL()->curlang = PLL()->model->get_language( $lang_code );
							}
							$this->get_all_posts_data( $post_type_names );
						}
						// Switch back to original language.
						if ( $current_lang && function_exists( 'PLL' ) ) {
							PLL()->curlang = PLL()->model->get_language( $current_lang );
						}
					} else {
						// No languages found, sync without language switching.
						$this->get_all_posts_data( $post_type_names );
					}
				} else {
					$this->get_all_posts_data( $post_type_names );
				}
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
				\WebChangeDetector\WebChangeDetector_Admin_Utils::log_error( 'Syncing taxonomies: ' . implode( ', ', $taxonomy_post_names ), 'sync_posts', 'debug' );

				// WPML fix.
				if ( $this->wpml_is_active() ) {
					$wpml_languages = $this->get_wpml_languages();
					foreach ( $wpml_languages['languages'] as $lang_code ) {
						do_action( 'wpml_switch_language', $lang_code );
						$this->get_all_terms_data( $taxonomy_post_names );
					}
					do_action( 'wpml_switch_language', $wpml_languages['current_language'] );
				} elseif ( $this->polylang_is_active() && function_exists( 'pll_languages_list' ) ) {
					// Polylang fix.
					// Get all Polylang language codes.
					$polylang_languages = pll_languages_list();
					$current_lang       = function_exists( 'pll_current_language' ) ? pll_current_language() : false;

					if ( ! empty( $polylang_languages ) ) {
						foreach ( $polylang_languages as $lang_code ) {
							// Switch to each language.
							if ( function_exists( 'PLL' ) ) {
								PLL()->curlang = PLL()->model->get_language( $lang_code );
							}
							$this->get_all_terms_data( $taxonomy_post_names );
						}
						// Switch back to original language.
						if ( $current_lang && function_exists( 'PLL' ) ) {
							PLL()->curlang = PLL()->model->get_language( $current_lang );
						}
					} else {
						// No languages found, sync without language switching.
						$this->get_all_terms_data( $taxonomy_post_names );
					}
				} else {
					$this->get_all_terms_data( $taxonomy_post_names );
				}
			}
		}

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
			if ( $this->wpml_is_active() ) {
				$wpml_languages = $this->get_wpml_languages();

				if ( ! empty( $wpml_languages ) ) {
					foreach ( $wpml_languages['languages'] as $lang_code ) {
						// Switch to each language.
						do_action( 'wpml_switch_language', $lang_code );

						// Store the title in the array with the language code as the key.
						$array['frontpage%%Frontpage'][] = array(
							'url'        => \WebChangeDetector\WebChangeDetector_Admin_Utils::remove_url_protocol( apply_filters( 'wpml_home_url', get_home_url(), $lang_code ) ),
							'html_title' => get_bloginfo( 'name' ),
						);
					}

					// Switch back to the original language.
					do_action( 'wpml_switch_language', $wpml_languages['current_language'] );
				}

				// Polylang fix.
			} elseif ( $this->polylang_is_active() ) {

				if ( function_exists( 'pll_languages_list' ) ) {
					// Get all language codes (returns array of language slugs like ['en', 'fr', 'de']).
					$language_codes = pll_languages_list();

					if ( ! empty( $language_codes ) ) {
						foreach ( $language_codes as $lang_code ) {
							// Get the home URL for this language.
							$home_url = function_exists( 'pll_home_url' ) ? pll_home_url( $lang_code ) : false;

							if ( ! $home_url ) {
								continue;
							}

							$array['frontpage%%Frontpage'][] = array(
								'url'        => \WebChangeDetector\WebChangeDetector_Admin_Utils::remove_url_protocol( $home_url ),
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
				$this->admin->settings_handler->update_website_details( $website_details );
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
			$this->admin->settings_handler->update_website_details( $website_details );
		}

		// Check if we have any URLs to sync.
		if ( empty( $this->admin->sync_urls ) ) {
			\WebChangeDetector\WebChangeDetector_Admin_Utils::log_error( __( 'No URLs found to sync', 'webchangedetector' ), 'sync_posts', 'error' );
			return __( 'No URLs found to sync', 'webchangedetector' );
		}

		// Log the number of URL batches being synced.
		$total_batches = count( $this->admin->sync_urls );
		\WebChangeDetector\WebChangeDetector_Admin_Utils::log_error( 'Syncing ' . $total_batches . ' URL batches', 'sync_posts', 'debug' );

		// Create uuid for sync urls.
		$collection_uuid = wp_generate_uuid4();

		// Sync urls.
		$response_sync_urls      = \WebChangeDetector\WebChangeDetector_API_V2::sync_urls( $this->admin->sync_urls, $collection_uuid );
		$response_start_url_sync = \WebChangeDetector\WebChangeDetector_API_V2::start_url_sync( false, $collection_uuid );
		\WebChangeDetector\WebChangeDetector_Admin_Utils::log_error( 'Response upload URLs: ' . wp_json_encode( $response_sync_urls ), 'sync_posts', 'debug' );
		\WebChangeDetector\WebChangeDetector_Admin_Utils::log_error( 'Response Start URL sync: ' . wp_json_encode( $response_start_url_sync ), 'sync_posts', 'debug' );

		return date_i18n( 'd/m/Y H:i' );
	}

	/**
	 * Get WordPress post types via REST API.
	 *
	 * Retrieves available post types, taxonomies, and WPML languages from a WordPress site
	 * using the WordPress REST API.
	 *
	 * @since    1.0.0
	 * @param    string $domain    The domain to get post types from.
	 * @return   array|string    Array of post types and taxonomies, or error message.
	 */
	public function get_wp_post_types( $domain ) {
		( 'Starting get_wp_post_types' );
		$scheme = $this->is_website_https( $domain ) ? 'https://' : 'http://';

		// Check for WPML & if api is reachable.
		$response = wp_remote_get( $scheme . $domain . '/wp-json/wp/v2/' );

		$status = wp_remote_retrieve_response_code( $response );
		if ( 200 !== $status ) {
			return __( 'We couldn\'t reach the WP Api. Please make sure it is enabled on the WP website', 'webchangedetector' );
		}
		$body       = wp_remote_retrieve_body( $response );
		$api_routes = json_decode( $body, true );

		$return = array(); // init.

		// Get Post Types.
		$response     = wp_remote_get( $scheme . $domain . '/wp-json/wp/v2/types' );
		$status_types = wp_remote_retrieve_response_code( $response );
		if ( 200 !== $status_types ) {
			return 'We couldn\'t reach the WP Api. Please make sure it is enabled on the WP website';
		}
		$body       = wp_remote_retrieve_body( $response );
		$post_types = json_decode( $body, true );

		// Get Taxonomies.
		$response = wp_remote_get( $scheme . $domain . '/wp-json/wp/v2/taxonomies' );

		$status_taxonomies = wp_remote_retrieve_response_code( $response );
		if ( 200 !== $status_taxonomies ) {
			return 'We couldn\'t reach the WP Api. Please make sure it is enabled on the WP website';
		}
		$body       = wp_remote_retrieve_body( $response );
		$taxonomies = json_decode( $body, true );

		$return_post_types = array();
		$return_taxonomies = array();

		// Prepare return post_types.
		foreach ( $post_types as $post_type ) {
			$return_post_types[] = array(
				'name' => $post_type['name'],
				'slug' => $post_type['rest_base'],
			);
		}

		// Prepare return taxonomies.
		foreach ( $taxonomies as $taxonomy ) {
			$return_taxonomies[] = array(
				'name' => $taxonomy['name'],
				'slug' => $taxonomy['rest_base'],
			);
		}

		// Get it together.
		$return[] = array(
			'url_type_slug' => 'types',
			'url_type_name' => 'Post Types',
			'url_types'     => $return_post_types,
		);

		$return[] = array(
			'url_type_slug' => 'taxonomies',
			'url_type_name' => 'Taxonomies',
			'url_types'     => $return_taxonomies,
		);

		// Check for WPML languages.
		$wpml_language_codes = array();
		if ( ! empty( $api_routes['routes']['/wp/v2']['endpoints'][0]['args']['wpml_language']['enum'] ) ) {
			$wpml_language_codes = $api_routes['routes']['/wp/v2']['endpoints'][0]['args']['wpml_language']['enum'];
		}

		// Prepare languages.
		if ( $wpml_language_codes ) {
			foreach ( $wpml_language_codes as $wpml_language_code ) {
				$return_wpml_languages[] = array(
					'name' => strtoupper( $wpml_language_code ),
					'slug' => $wpml_language_code,
				);
			}

			if ( ! empty( $return_wpml_languages ) ) {
				$return[] = array(
					'url_type_slug' => 'wpml_language',
					'url_type_name' => 'Languages',
					'url_types'     => $return_wpml_languages,
				);
			}
		}
		return $return;
	}

	/**
	 * Get WordPress URLs via REST API.
	 *
	 * Retrieves URLs from a WordPress site using the WordPress REST API.
	 * Groups URLs by post type and taxonomy and includes metadata.
	 *
	 * @since    1.0.0
	 * @param    string $domain     The domain to get URLs from.
	 * @param    array  $url_types  The URL types to retrieve (optional).
	 * @return   array|string|false Array of URLs grouped by type, error message, or false on failure.
	 */
	public function get_wp_urls( $domain, $url_types = false ) {
		if ( ! $domain ) {
			return 'domain invalid';
		}

		$scheme = $this->is_website_https( $domain ) ? 'https://' : 'http://';

		if ( ! $url_types ) {
			$url_types = array(
				array(
					'url_type_slug'  => 'types',
					'url_type_name'  => 'Post Types',
					'post_type_slug' => 'pages',
					'post_type_name' => 'Pages',
				),
				array(
					'url_type_slug'  => 'types',
					'url_type_name'  => 'Post Types',
					'post_type_slug' => 'posts',
					'post_type_name' => 'Posts',
				),
			);
		}

		// Check if we have different languages with wpml.
		$languages = array();
		foreach ( $url_types as $key => $url_type ) {
			if ( 'wpml_language' === $url_type['url_type_slug'] ) {
				$languages[] = $url_type['post_type_slug'];
				unset( $url_types[ $key ] );
			}
		}
		if ( empty( $languages ) ) {
			$languages = array( false );
		}

		$urls             = array();
		$frontpage_has_id = false;

		// Loop for every language.
		foreach ( $languages as $language ) {
			// Loop for url_types like post_type or taxonomy.
			foreach ( $url_types as $url_type ) {
				$pages_added = 0;
				$offset      = 0;

				// Loop through the post_types / taxonomies.
				switch ( $url_type['url_type_slug'] ) {
					case 'taxonomies':
						$is_taxonomie = true;
						$args         = array(
							'per_page' => '100',
							'_fields'  => 'id,link,name',
							'order'    => 'asc',
						);
						break;

					default:
						$is_taxonomie = false;
						$args         = array(
							'per_page' => '100',
							'_fields'  => 'id,link,title',
							'orderby'  => 'parent',
							'order'    => 'asc',
						);
				}
				// add wmpl language to the args.
				if ( $language ) {
					$args['wpml_language'] = $language;
				}

				do {
					$args['offset'] = $offset;
					$response       = wp_remote_get( $scheme . $domain . '/wp-json/wp/v2/' . $url_type['post_type_slug'] . '/?' . http_build_query( $args ) );
					$status_code    = wp_remote_retrieve_response_code( $response );
					$type_urls      = wp_remote_retrieve_body( $response );
					$type_urls      = json_decode( $type_urls );

					if ( 200 === $status_code ) {
						foreach ( $type_urls as $type_url ) {
							$clean_link = str_replace( array( 'http://', 'https://' ), '', $type_url->link );

							$chunk_key = (int) ( $pages_added / 1000 );

							$urls[ $chunk_key ][ $url_type['url_type_slug'] . '%%' . $url_type['post_type_name'] ][] = array(
								'url'        => $clean_link,
								'html_title' => $is_taxonomie ? $type_url->name : $type_url->title->rendered,
							);

							if ( in_array( $clean_link, array( $domain, $domain . '/', 'www.' . $domain, 'www.' . $domain . '/' ), true ) ) {
								$frontpage_has_id = true;
							}
						}
					}

					if ( is_iterable( $type_urls ) ) {
						$pages_added += count( $type_urls ) ?? 0;
					}

					$offset += 100;
				} while ( $pages_added === $offset );
			}
		}

		if ( ! $frontpage_has_id && count( $urls ) ) {
			$urls[]['frontpage%%Frontpage'][] = array(
				'url'        => str_replace( array( 'http://', 'https://' ), '', $domain . '/' ),
				'html_title' => 'Home',
			);
		}

		if ( count( $urls ) ) {
			return $urls;
		}
		return false;
	}

	/**
	 * Check if website uses HTTPS.
	 *
	 * Tests if a website is accessible via HTTPS by making a request
	 * and checking the response status. Uses static caching for performance.
	 *
	 * @since    1.0.0
	 * @param    string $url The URL to test for HTTPS support.
	 * @return   bool        True if HTTPS is supported, false otherwise.
	 */
	public function is_website_https( $url ) {
		static $scheme;

		if ( isset( $scheme ) ) {
			return $scheme;
		}

		$url      = str_replace( array( 'http://', 'https://' ), '', $url );
		$response = wp_remote_get( 'https://' . $url );
		$status   = wp_remote_retrieve_response_code( $response );

		if ( 200 === $status ) {
			$scheme = true;
		} else {
			$scheme = false;
		}
		return $scheme;
	}
}
