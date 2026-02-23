<?php
/**
 * AI Action Handler for WebChangeDetector
 *
 * Handles all AI feedback rule-related actions and business logic.
 *
 * @package    WebChangeDetector
 * @subpackage WebChangeDetector/admin/actions
 */

namespace WebChangeDetector;

/**
 * AI Action Handler Class.
 *
 * @since 4.1.0
 */
class WebChangeDetector_AI_Action_Handler {

	/**
	 * Valid feedback rule scopes.
	 *
	 * @var array
	 */
	const VALID_SCOPES = array( 'url', 'group_or_website' );

	/**
	 * Create an AI feedback rule.
	 *
	 * @param array $data Rule data (comparison_id, region_id, scope).
	 * @return array Result with success status and data.
	 */
	public function create_feedback_rule( $data ) {
		try {
			$comparison_id = sanitize_text_field( $data['comparison_id'] ?? '' );
			$region_id     = intval( $data['region_id'] ?? 0 );
			$scope         = sanitize_text_field( $data['scope'] ?? 'url' );

			if ( empty( $comparison_id ) ) {
				return array(
					'success' => false,
					'message' => 'Comparison ID is required.',
				);
			}

			if ( ! in_array( $scope, self::VALID_SCOPES, true ) ) {
				return array(
					'success' => false,
					'message' => 'Invalid scope.',
				);
			}

			$result = WebChangeDetector_API_V2::create_ai_feedback_rule(
				array(
					'comparison_id' => $comparison_id,
					'region_id'     => $region_id,
					'scope'         => $scope,
				)
			);

			if ( ! empty( $result['data'] ) ) {
				return array(
					'success' => true,
					'data'    => $result['data'],
				);
			}

			return array(
				'success' => false,
				'message' => $result['message'] ?? 'Failed to create feedback rule.',
			);
		} catch ( \Exception $e ) {
			return array(
				'success' => false,
				'message' => 'Error creating feedback rule: ' . $e->getMessage(),
			);
		}
	}

	/**
	 * Toggle an AI feedback rule active/inactive.
	 *
	 * @param string $uuid   The rule UUID.
	 * @param bool   $active Whether the rule should be active.
	 * @return array Result with success status.
	 */
	public function toggle_feedback_rule( $uuid, $active ) {
		try {
			if ( empty( $uuid ) ) {
				return array(
					'success' => false,
					'message' => 'Rule ID is required.',
				);
			}

			$result = WebChangeDetector_API_V2::toggle_ai_feedback_rule( $uuid, $active );

			if ( ! empty( $result['data'] ) ) {
				return array(
					'success' => true,
					'data'    => $result['data'],
				);
			}

			return array(
				'success' => false,
				'message' => $result['message'] ?? 'Failed to update rule.',
			);
		} catch ( \Exception $e ) {
			return array(
				'success' => false,
				'message' => 'Error updating rule: ' . $e->getMessage(),
			);
		}
	}

	/**
	 * Delete an AI feedback rule.
	 *
	 * @param string $uuid The rule UUID.
	 * @return array Result with success status.
	 */
	public function delete_feedback_rule( $uuid ) {
		try {
			if ( empty( $uuid ) ) {
				return array(
					'success' => false,
					'message' => 'Rule ID is required.',
				);
			}

			$result = WebChangeDetector_API_V2::delete_ai_feedback_rule( $uuid );

			// DELETE returns 204 (no content) on success: api_v2() may return null, true, or empty.
			if ( null === $result || true === $result || '' === $result || ( is_array( $result ) && empty( $result['message'] ) ) ) {
				return array(
					'success' => true,
				);
			}

			return array(
				'success' => false,
				'message' => is_array( $result ) ? ( $result['message'] ?? 'Failed to delete rule.' ) : 'Failed to delete rule.',
			);
		} catch ( \Exception $e ) {
			return array(
				'success' => false,
				'message' => 'Error deleting rule: ' . $e->getMessage(),
			);
		}
	}

	/**
	 * Update the scope of an AI feedback rule.
	 *
	 * @param string $uuid  The rule UUID.
	 * @param string $scope The new scope.
	 * @return array Result with success status.
	 */
	public function update_feedback_rule_scope( $uuid, $scope ) {
		try {
			if ( empty( $uuid ) ) {
				return array(
					'success' => false,
					'message' => 'Rule ID is required.',
				);
			}

			if ( ! in_array( $scope, self::VALID_SCOPES, true ) ) {
				return array(
					'success' => false,
					'message' => 'Invalid scope.',
				);
			}

			$result = WebChangeDetector_API_V2::update_ai_feedback_rule_scope( $uuid, $scope );

			if ( ! empty( $result['data'] ) ) {
				return array(
					'success' => true,
					'data'    => $result['data'],
				);
			}

			return array(
				'success' => false,
				'message' => $result['message'] ?? 'Failed to update rule scope.',
			);
		} catch ( \Exception $e ) {
			return array(
				'success' => false,
				'message' => 'Error updating rule scope: ' . $e->getMessage(),
			);
		}
	}
}
