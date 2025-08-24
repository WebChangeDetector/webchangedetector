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
	 * Handle URL synchronization with API.
	 *
	 * @param array $data The sync data.
	 * @return array Result with success status and message.
	 */
	public function handle_sync_urls_to_api( $data ) {
		try {
			$force      = ! empty( $data['force'] );
			$post_types = $data['post_types'] ?? array();

			// If specific post types are provided, sync only those.
			if ( ! empty( $post_types ) ) {
				$sync_result = $this->sync_specific_post_types( $post_types, $force );
			} else {
				// Sync all configured post types.
				$sync_result = $this->admin->wordpress_handler->sync_posts( $force );
			}

			if ( $sync_result ) {
				return array(
					'success' => true,
					'message' => 'URLs synchronized successfully with the API.',
				);
			} else {
				return array(
					'success' => false,
					'message' => 'Failed to synchronize URLs with the API.',
				);
			}
		} catch ( \Exception $e ) {
			return array(
				'success' => false,
				'message' => 'Error synchronizing URLs: ' . $e->getMessage(),
			);
		}
	}

	/**
	 * Get available post types for monitoring.
	 *
	 * @return array Available post types.
	 */
	public function get_available_post_types() {
		try {
			$post_types      = get_post_types( array( 'public' => true ), 'objects' );
			$available_types = array();

			// Exclude certain post types.
			$excluded_types = array( 'attachment', 'revision', 'nav_menu_item' );

			// Get already enabled post types from website details.
			$enabled_post_types = array();
			if ( ! empty( $this->admin->website_details['sync_url_types'] ) ) {
				foreach ( $this->admin->website_details['sync_url_types'] as $sync_url_type ) {
					if ( ! empty( $sync_url_type['post_type_slug'] ) ) {
						$enabled_post_types[] = $sync_url_type['post_type_slug'];
					}
				}
			}

			foreach ( $post_types as $post_type ) {
				// Get the proper slug using the utility method that handles rest_base fallback to name.
				$post_type_slug = WebChangeDetector_Admin_Utils::get_post_type_slug( $post_type );

				// Skip if post type is excluded or already enabled.
				if ( in_array( $post_type_slug, $excluded_types, true ) || in_array( $post_type_slug, $enabled_post_types, true ) ) {
					continue;
				}

				$available_types[] = array(
					'slug'        => $post_type_slug,
					'name'        => $post_type->label,
					'description' => $post_type->description,
					'public'      => $post_type->public,
					'count'       => wp_count_posts( $post_type_slug )->publish ?? 0,
				);
			}

			return $available_types;
		} catch ( \Exception $e ) {
			return array();
		}
	}

	/**
	 * Get current manual checks workflow status.
	 *
	 * @return array Workflow status information.
	 */
	public function get_manual_checks_status() {
		try {
			$current_step = get_option( WCD_OPTION_UPDATE_STEP_KEY, WCD_OPTION_UPDATE_STEP_SETTINGS );
			$batch_id     = get_option( 'wcd_manual_checks_batch' );

			$status = array(
				'current_step' => $current_step,
				'batch_id'     => $batch_id,
				'step_name'    => $this->get_step_display_name( $current_step ),
				'can_proceed'  => $this->can_proceed_to_next_step( $current_step ),
			);

			// Get additional data based on current step.
			switch ( $current_step ) {
				case WCD_OPTION_UPDATE_STEP_PRE_STARTED:
				case WCD_OPTION_UPDATE_STEP_POST_STARTED:
					if ( $batch_id ) {
						$queue_status           = $this->admin->get_processing_queue_v2( $batch_id, 10 );
						$status['queue_status'] = $queue_status;
					}
					break;
			}

			return $status;
		} catch ( \Exception $e ) {
			return array(
				'error' => 'Error getting workflow status: ' . $e->getMessage(),
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

	/**
	 * Sync specific post types to API.
	 *
	 * @param array $post_types The post types to sync.
	 * @param bool  $force      Whether to force sync.
	 * @return bool Success status.
	 */
	private function sync_specific_post_types( $post_types, $force ) {
		// For now, we'll use the general sync_posts method.
		// since there's no specific sync_post_type method available.
		// The sync_posts method handles all configured post types.
		$result = $this->admin->wordpress_handler->sync_posts( $force );

		return (bool) $result;
	}

	/**
	 * Get display name for workflow step.
	 *
	 * @param string $step The step identifier.
	 * @return string Display name.
	 */
	private function get_step_display_name( $step ) {
		$step_names = array(
			WCD_OPTION_UPDATE_STEP_SETTINGS         => 'Configure Settings',
			WCD_OPTION_UPDATE_STEP_PRE              => 'Pre-Update Screenshots',
			WCD_OPTION_UPDATE_STEP_PRE_STARTED      => 'Taking Pre-Update Screenshots',
			WCD_OPTION_UPDATE_STEP_MAKE_UPDATES     => 'Make Updates',
			WCD_OPTION_UPDATE_STEP_POST             => 'Post-Update Screenshots',
			WCD_OPTION_UPDATE_STEP_POST_STARTED     => 'Taking Post-Update Screenshots',
			WCD_OPTION_UPDATE_STEP_CHANGE_DETECTION => 'View Change Detection',
		);

		return $step_names[ $step ] ?? 'Unknown Step';
	}

	/**
	 * Check if workflow can proceed to next step.
	 *
	 * @param string $current_step The current step.
	 * @return bool Whether can proceed.
	 */
	private function can_proceed_to_next_step( $current_step ) {
		switch ( $current_step ) {
			case WCD_OPTION_UPDATE_STEP_SETTINGS:
				// Always can proceed from settings.
				return true;

			case WCD_OPTION_UPDATE_STEP_PRE_STARTED:
			case WCD_OPTION_UPDATE_STEP_POST_STARTED:
				// Check if screenshots are complete.
				$batch_id = get_option( 'wcd_manual_checks_batch' );
				if ( $batch_id ) {
					$queue_status     = $this->admin->get_processing_queue_v2( $batch_id, 1 );
					$processing_items = 0;

					if ( ! empty( $queue_status['data'] ) ) {
						foreach ( $queue_status['data'] as $item ) {
							if ( 'done' !== $item['status'] ) {
								++$processing_items;
							}
						}
					}

					return 0 === $processing_items;
				}
				return true;

			default:
				return true;
		}
	}

	/**
	 * Get WordPress environment information.
	 *
	 * @return array Environment information.
	 */
	public function get_environment_info() {
		global $wp_version;

		return array(
			'wp_version'     => $wp_version,
			'php_version'    => PHP_VERSION,
			'site_url'       => get_site_url(),
			'home_url'       => get_home_url(),
			'is_multisite'   => is_multisite(),
			'theme'          => get_template(),
			'active_plugins' => get_option( 'active_plugins', array() ),
			'timezone'       => get_option( 'timezone_string' ) ? get_option( 'timezone_string' ) : 'UTC',
		);
	}
}
