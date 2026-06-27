<?php
/**
 * Account AJAX handler.
 *
 * Handles all account and dashboard-related AJAX operations including
 * account activation checking and usage statistics retrieval.
 *
 * @link       https://www.webchangedetector.com
 * @since      4.0.0
 * @package    WebChangeDetector
 * @subpackage WebChangeDetector/admin/ajax
 */

namespace WebChangeDetector;

/**
 * Account AJAX handler.
 *
 * Handles all account and dashboard-related AJAX operations.
 *
 * @since      4.0.0
 * @package    WebChangeDetector
 * @subpackage WebChangeDetector/admin/ajax
 * @author     Mike Miler <mike@webchangedetector.com>
 */
class WebChangeDetector_Account_Ajax_Handler extends WebChangeDetector_Ajax_Handler_Base {

	/**
	 * The account handler instance.
	 *
	 * @since    4.0.0
	 * @access   private
	 * @var      WebChangeDetector_Admin_Account    $account_handler    The account handler instance.
	 */
	private $account_handler;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    4.0.0
	 * @param    WebChangeDetector_Admin         $admin           The main admin class instance.
	 * @param    WebChangeDetector_Admin_Account $account_handler The account handler instance.
	 */
	public function __construct( $admin, $account_handler ) {
		parent::__construct( $admin );

		$this->account_handler = $account_handler;
	}

	/**
	 * Register AJAX hooks for account operations.
	 *
	 * Registers all WordPress AJAX hooks for account-related operations.
	 *
	 * @since    4.0.0
	 */
	public function register_hooks() {
		add_action( 'wp_ajax_get_dashboard_latest_changes', array( $this, 'ajax_get_dashboard_latest_changes' ) );
		add_action( 'wp_ajax_get_dashboard_latest_cleared', array( $this, 'ajax_get_dashboard_latest_cleared' ) );
	}

	/**
	 * Render the "Latest Detected Changes" dashboard card.
	 *
	 * Returns the last few comparisons with status=new (the final post-AI verdict)
	 * for THIS website only. get_comparisons_v2() auto-scopes to the current site's
	 * groups (WCD_WEBSITE_GROUPS), so no extra website filter is needed. Echoes the
	 * shared card partial as raw HTML for the lazy-loader to inject.
	 *
	 * @since    4.0.0
	 */
	public function ajax_get_dashboard_latest_changes() {
		if ( ! $this->security_check() ) {
			return;
		}

		$comparisons = \WebChangeDetector\WebChangeDetector_API_V2::get_comparisons_v2(
			array(
				'status'   => 'new',
				'per_page' => 7,
			)
		);
		if ( ! is_array( $comparisons ) ) {
			$comparisons = array();
		}

		$card_title         = __( 'Latest Detected Changes', 'webchangedetector' );
		$card_icon          = 'warning';
		$card_icon_modifier = 'wcd-card-header-icon-warning';
		$card_intro         = __( 'Most recent changes WCD flagged for your attention.', 'webchangedetector' );
		$empty_state        = __( 'No detected changes yet.', 'webchangedetector' );
		$view_all_url       = admin_url( 'admin.php?page=webchangedetector-change-detections' );

		require WCD_PLUGIN_DIR . 'admin/partials/dashboard/card-comparison-list.php';
		wp_die();
	}

	/**
	 * Render the "Recently AI-Cleared" dashboard card.
	 *
	 * Returns the last few comparisons with status=ok AND above_threshold for THIS
	 * website: visual diffs above the threshold that the AI classified as "no real
	 * change". Shows the value of AI verification — what it filtered out so the user
	 * does not have to look at it. Auto-scoped to the current site's groups.
	 *
	 * @since    4.0.0
	 */
	public function ajax_get_dashboard_latest_cleared() {
		if ( ! $this->security_check() ) {
			return;
		}

		$comparisons = \WebChangeDetector\WebChangeDetector_API_V2::get_comparisons_v2(
			array(
				'status'          => 'ok',
				'above_threshold' => 1,
				'per_page'        => 7,
			)
		);
		if ( ! is_array( $comparisons ) ) {
			$comparisons = array();
		}

		$card_title         = __( 'Recently AI-Cleared', 'webchangedetector' );
		$card_icon          = 'shield';
		$card_icon_modifier = 'wcd-card-header-icon-success';
		$card_intro         = __( 'Visual diffs WCD AI determined were not real changes.', 'webchangedetector' );
		$empty_state        = __( 'Nothing AI-cleared recently.', 'webchangedetector' );
		$view_all_url       = admin_url( 'admin.php?page=webchangedetector-change-detections' );

		require WCD_PLUGIN_DIR . 'admin/partials/dashboard/card-comparison-list.php';
		wp_die();
	}
}
