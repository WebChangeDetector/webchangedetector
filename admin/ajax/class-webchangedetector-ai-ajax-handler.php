<?php
/**
 * AI AJAX handler.
 *
 * Handles all AI feedback rule-related AJAX operations including
 * rule creation, toggling, scope updates, and deletion.
 *
 * @link       https://www.webchangedetector.com
 * @since      4.1.0
 * @package    WebChangeDetector
 * @subpackage WebChangeDetector/admin/ajax
 */

namespace WebChangeDetector;

/**
 * AI AJAX handler.
 *
 * Handles all AI feedback rule-related AJAX operations.
 *
 * @since      4.1.0
 * @package    WebChangeDetector
 * @subpackage WebChangeDetector/admin/ajax
 */
class WebChangeDetector_AI_Ajax_Handler extends WebChangeDetector_Ajax_Handler_Base {

	/**
	 * The AI action handler instance.
	 *
	 * @since    4.1.0
	 * @access   private
	 * @var      WebChangeDetector_AI_Action_Handler    $ai_handler    The AI action handler instance.
	 */
	private $ai_handler;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    4.1.0
	 * @param    WebChangeDetector_Admin              $admin      The main admin class instance.
	 * @param    WebChangeDetector_AI_Action_Handler  $ai_handler The AI action handler instance.
	 */
	public function __construct( $admin, $ai_handler ) {
		parent::__construct( $admin );
		$this->ai_handler = $ai_handler;
	}

	/**
	 * Register AJAX hooks for AI feedback rules.
	 *
	 * @since    4.1.0
	 */
	public function register_hooks() {
		add_action( 'wp_ajax_wcd_create_ai_feedback_rule', array( $this, 'ajax_create_ai_feedback_rule' ) );
		add_action( 'wp_ajax_wcd_toggle_ai_feedback_rule', array( $this, 'ajax_toggle_ai_feedback_rule' ) );
		add_action( 'wp_ajax_wcd_delete_ai_feedback_rule', array( $this, 'ajax_delete_ai_feedback_rule' ) );
		add_action( 'wp_ajax_wcd_update_ai_feedback_rule_scope', array( $this, 'ajax_update_ai_feedback_rule_scope' ) );
	}

	/**
	 * Create an AI feedback rule via AJAX.
	 *
	 * @since    4.1.0
	 */
	public function ajax_create_ai_feedback_rule() {
		if ( ! $this->security_check() ) {
			return;
		}

		$post_data = $this->validate_post_data( array( 'comparison_id' ) );

		if ( false === $post_data ) {
			$this->send_error_response( __( 'Comparison ID is required.', 'webchangedetector' ), 'create_ai_feedback_rule' );
			return;
		}

		$type = sanitize_text_field( $post_data['type'] ?? 'visual' );

		$rule_data = array(
			'comparison_id' => $post_data['comparison_id'],
			'scope'         => $post_data['scope'] ?? 'url',
			'type'          => $type,
		);

		if ( 'console' === $type ) {
			$rule_data['console_entry']  = sanitize_text_field( $post_data['console_entry'] ?? '' );
			$rule_data['console_source'] = esc_url_raw( $post_data['console_source'] ?? '' );
		} else {
			$rule_data['region_id'] = $post_data['region_id'] ?? 0;
		}

		$result = $this->ai_handler->create_feedback_rule( $rule_data );

		if ( $result['success'] ) {
			$this->send_success_response( $result['data'] ?? null );
		} else {
			$this->send_error_response( $result['message'] ?? __( 'Failed to create rule.', 'webchangedetector' ), 'create_ai_feedback_rule' );
		}
	}

	/**
	 * Toggle an AI feedback rule active/inactive via AJAX.
	 *
	 * @since    4.1.0
	 */
	public function ajax_toggle_ai_feedback_rule() {
		if ( ! $this->security_check() ) {
			return;
		}

		$post_data = $this->validate_post_data( array( 'rule_id' ) );

		if ( false === $post_data ) {
			$this->send_error_response( __( 'Rule ID is required.', 'webchangedetector' ), 'toggle_ai_feedback_rule' );
			return;
		}

		$active = filter_var( $post_data['active'] ?? true, FILTER_VALIDATE_BOOLEAN );
		$result = $this->ai_handler->toggle_feedback_rule( $post_data['rule_id'], $active );

		if ( $result['success'] ) {
			$this->send_success_response( $result['data'] ?? null );
		} else {
			$this->send_error_response( $result['message'] ?? __( 'Failed to update rule.', 'webchangedetector' ), 'toggle_ai_feedback_rule' );
		}
	}

	/**
	 * Delete an AI feedback rule via AJAX.
	 *
	 * @since    4.1.0
	 */
	public function ajax_delete_ai_feedback_rule() {
		if ( ! $this->security_check() ) {
			return;
		}

		$post_data = $this->validate_post_data( array( 'rule_id' ) );

		if ( false === $post_data ) {
			$this->send_error_response( __( 'Rule ID is required.', 'webchangedetector' ), 'delete_ai_feedback_rule' );
			return;
		}

		$result = $this->ai_handler->delete_feedback_rule( $post_data['rule_id'] );

		if ( $result['success'] ) {
			$this->send_success_response();
		} else {
			$this->send_error_response( $result['message'] ?? __( 'Failed to delete rule.', 'webchangedetector' ), 'delete_ai_feedback_rule' );
		}
	}

	/**
	 * Update the scope of an AI feedback rule via AJAX.
	 *
	 * @since    4.1.0
	 */
	public function ajax_update_ai_feedback_rule_scope() {
		if ( ! $this->security_check() ) {
			return;
		}

		$post_data = $this->validate_post_data( array( 'rule_id', 'scope' ) );

		if ( false === $post_data ) {
			$this->send_error_response( __( 'Rule ID and scope are required.', 'webchangedetector' ), 'update_ai_feedback_rule_scope' );
			return;
		}

		$result = $this->ai_handler->update_feedback_rule_scope( $post_data['rule_id'], $post_data['scope'] );

		if ( $result['success'] ) {
			$this->send_success_response( $result['data'] ?? null );
		} else {
			$this->send_error_response( $result['message'] ?? __( 'Failed to update rule scope.', 'webchangedetector' ), 'update_ai_feedback_rule_scope' );
		}
	}
}
