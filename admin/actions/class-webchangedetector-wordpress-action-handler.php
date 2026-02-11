<?php
/**
 * WordPress Action Handler for WebChangeDetector
 *
 * Handles all WordPress-related actions and business logic.
 *
 * @package    WebChangeDetector
 * @subpackage WebChangeDetector/admin/actions
 * @author     Mike Miler <mike@wp-mike.com>
 */

namespace WebChangeDetector;

/**
 * WordPress Action Handler Class.
 */
class WebChangeDetector_WordPress_Action_Handler {

	/**
	 * The admin instance.
	 *
	 * @var WebChangeDetector_Admin
	 */
	private $admin;

	/**
	 * Constructor.
	 *
	 * @param WebChangeDetector_Admin $admin The admin instance.
	 */
	public function __construct( $admin ) {
		$this->admin = $admin;
	}

	/**
	 * Handle add post type action.
	 *
	 * @param array $data The post type data.
	 * @return array Result with success status and message.
	 */
	public function handle_add_post_type( $data ) {
		try {
			// The form sends post_type as JSON, decode it first.
			$post_type_data = '';
			if ( ! empty( $data['post_type'] ) ) {
				$post_type_json = json_decode( $data['post_type'], true );
				$post_type_data = is_array( $post_type_json ) ? $post_type_json : array();
			}

			// Extract post type slug from the decoded data or fallback to direct slug.
			$post_type_slug = '';
			if ( ! empty( $post_type_data ) && is_array( $post_type_data ) ) {
				// Look for post_type_slug in the array (could be nested).
				$post_type_slug = $post_type_data['post_type_slug'] ?? $post_type_data[0]['post_type_slug'] ?? '';
			} else {
				// Fallback to direct slug parameter.
				$post_type_slug = sanitize_text_field( $data['post_type_slug'] ?? $data['post_type'] ?? '' );
			}

			if ( empty( $post_type_slug ) ) {
				return array(
					'success' => false,
					'message' => 'Post type slug is required.',
				);
			}

			// Validate post type exists.
			// Our post_type_slug is actually the post_type->rest_base or post_type->name as fallback.
			// So we need to skip this check.
			// if ( ! post_type_exists( $post_type_slug ) ) {.
			// return array(.
			// 'success' => false,.
			// 'message' => 'Post type does not exist: ' . $post_type_slug,.
			// );.
			// }.

			// Use the existing add_post_type method which handles the JSON data properly.
			$this->admin->wordpress_handler->add_post_type( $data );

			return array(
				'success'   => true,
				'message'   => 'Post type added successfully and URLs synchronized.',
				'post_type' => $post_type_slug,
			);
		} catch ( \Exception $e ) {
			return array(
				'success' => false,
				'message' => 'Error adding post type: ' . $e->getMessage(),
			);
		}
	}

	/**
	 * Handle manual checks workflow steps.
	 *
	 * @param array $data The step data.
	 * @return array Result with success status and message.
	 */
	public function handle_update_detection_step( $data ) {
		try {
			$step = sanitize_text_field( $data['step'] ?? '' );

			// Validate step.
			$valid_steps = array(
				WCD_OPTION_UPDATE_STEP_SETTINGS,
				WCD_OPTION_UPDATE_STEP_PRE,
				WCD_OPTION_UPDATE_STEP_PRE_STARTED,
				WCD_OPTION_UPDATE_STEP_MAKE_UPDATES,
				WCD_OPTION_UPDATE_STEP_POST,
				WCD_OPTION_UPDATE_STEP_POST_STARTED,
				WCD_OPTION_UPDATE_STEP_CHANGE_DETECTION,
			);

			if ( ! in_array( $step, $valid_steps, true ) ) {
				return array(
					'success' => false,
					'message' => 'Invalid detection step.',
				);
			}

			// Update step tracking.
			update_option( WCD_OPTION_UPDATE_STEP_KEY, $step );

			// Handle step-specific actions.
			$step_result = $this->handle_step_specific_actions( $step, $data );

			return array(
				'success'     => true,
				'message'     => 'Detection step updated successfully.',
				'step'        => $step,
				'step_result' => $step_result,
			);
		} catch ( \Exception $e ) {
			return array(
				'success' => false,
				'message' => 'Error updating detection step: ' . $e->getMessage(),
			);
		}
	}

	/**
	 * Handle start manual checks action.
	 *
	 * @param array $data The manual checks data.
	 * @return array Result with success status and message.
	 */
	public function handle_start_manual_checks( $data ) {
		try {
			// Update step in update detection - matches old implementation.
			if ( ! empty( $data['step'] ) ) {
				update_option( WCD_OPTION_UPDATE_STEP_KEY, sanitize_text_field( $data['step'] ) );
				$current_step = $data['step'];
			} else {
				// Default to pre-update step if no step provided.
				update_option( WCD_OPTION_UPDATE_STEP_KEY, WCD_OPTION_UPDATE_STEP_PRE );
				$current_step = WCD_OPTION_UPDATE_STEP_PRE;
			}

			// Clear any existing batch tracking when starting fresh.
			delete_option( 'wcd_manual_checks_batch' );

			return array(
				'success'      => true,
				'message'      => 'Manual checks workflow started successfully.',
				'current_step' => $current_step,
			);
		} catch ( \Exception $e ) {
			return array(
				'success' => false,
				'message' => 'Error starting manual checks: ' . $e->getMessage(),
			);
		}
	}

	/**
	 * Handle step-specific actions.
	 *
	 * @param string $step The step being processed.
	 * @param array  $data Additional data for the step.
	 * @return array Step-specific result.
	 */
	private function handle_step_specific_actions( $step, $data ) {
		// The $data parameter is reserved for future use in step implementations.
		unset( $data ); // Mark as intentionally unused.
		switch ( $step ) {
			case WCD_OPTION_UPDATE_STEP_PRE:
				// Prepare for pre-update screenshots.
				return array(
					'message'         => 'Ready for pre-update screenshots.',
					'next_action'     => 'take_screenshots',
					'screenshot_type' => 'pre',
				);

			case WCD_OPTION_UPDATE_STEP_MAKE_UPDATES:
				// User should make updates now.
				return array(
					'message'     => 'Please proceed with your website updates.',
					'next_action' => 'manual_updates',
				);

			case WCD_OPTION_UPDATE_STEP_POST:
				// Prepare for post-update screenshots.
				return array(
					'message'         => 'Ready for post-update screenshots.',
					'next_action'     => 'take_screenshots',
					'screenshot_type' => 'post',
				);

			case WCD_OPTION_UPDATE_STEP_CHANGE_DETECTION:
				// Initiate comparison.
				$batch_id = get_option( 'wcd_manual_checks_batch' );
				if ( $batch_id ) {
					return array(
						'message'     => 'Initiating change detection comparison.',
						'next_action' => 'view_comparisons',
						'batch_id'    => $batch_id,
					);
				}
				break;
		}

		return array( 'message' => 'Step updated.' );
	}
}
