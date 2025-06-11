<?php
/**
 * The requests to webchangedetector api v2.
 *
 * @package    WebChangeDetector
 * @subpackage WebChangeDetector/admin
 * @author     Mike Miler <mike@wp-mike.com>
 */

use WpOrg\Requests\Transport\Curl;

/**
 * Class for wcd api v2 requests.
 *
 * @package    WebChangeDetector
 */
class WebChangeDetector_API_V2 {

	/** Possible status for comparitons
	 */
	const WCD_POSSIBLE_COMPARISON_STATUS = array(
		'new',
		'ok',
		'to_fix',
		'false_positive',
	);
	/** Get account details.
	 *
	 * @return mixed|string
	 */
	public static function get_account_v2() {
		return self::api_v2( array( 'action' => 'account' ), 'GET' );
	}

    public static function get_websites_v2() {
        $args = array(
            'action' => 'websites'
        );
        return self::api_v2( $args, 'GET' );
    }

    public static function get_website_v2($uuid = false) {
        if(!$uuid) {
            return false;
        }

        $args = array(
            'action' => 'websites/' . $uuid
        );
        return self::api_v2( $args, 'GET' );
    }

	/** Sync urls.
	 *
	 * @param array $posts The posts to sync.
	 * @return false|mixed|string
	 */
	public static function sync_urls( $posts ) {
		if ( ! is_array( $posts ) ) {
			return false;
		}

		$args = array(
			'action'     => 'sync-urls',
			'domain'     => WebChangeDetector_Admin::get_domain_from_site_url(),
			'urls'       => $posts,
			'multi_call' => 'urls', // This tells our api_v2 to use array_key 'urls' as for multi-curl.
		);

		// Upload urls.
		return self::api_v2( $args );
	}

	/**
	 * Start the sync with the already uploaded urls.
	 *
	 * @param bool $delete_missing_urls Delete missing urls or not.
	 */
	public static function start_url_sync( $delete_missing_urls = true ) {
		return self::api_v2(
			array(
				'action'              => 'start-sync',
				'delete_missing_urls' => $delete_missing_urls,
			)
		);
	}

	/** Update group settings.
	 *
	 * @param string $group_id The group id.
	 * @param array  $group_settings Group settings to save.
	 * @return mixed|string
	 */
	public static function update_group( $group_id, $group_settings ) {
		$args = array(
			'action' => 'groups/' . $group_id,
		);
		$args = array_merge( $args, $group_settings );

		return self::api_v2( $args, 'PATCH' );
	}

	/** Update urls.
	 *
	 * @param int   $group_id The group id.
	 * @param array $active_posts All active posts.
	 *
	 * @return array|string
	 */
	public static function update_urls_in_group_v2( $group_id, $active_posts = array() ) {
		$args = array(
			'action' => 'groups/' . $group_id . '/urls',
			'urls'   => $active_posts,
		);

		return self::api_v2( $args, 'PUT' );
	}

	/** Get group details.
	 *
	 * @param string $group_id The group id.
	 * @return mixed|string
	 */
	public static function get_group_v2( $group_id ) {
		$args = array(
			'action' => 'groups/' . $group_id,
		);
		return self::api_v2( $args, 'GET' );
	}

	/** Get urls of a group.
	 *
	 * @param int   $group_id The group id.
	 * @param array $filters Filters for group urls.
	 *
	 * @return array|mixed|string
	 */
	public static function get_group_urls_v2( $group_id, $filters = array() ) {
		$args = array(
			'action' => 'groups/' . $group_id . '/urls?' . build_query( $filters ),
		);

		return self::api_v2( $args, 'GET' );
	}

	/** Delete urls from group
	 *
	 * @param string $group_id The group_id.
	 * @param array  $group_url_ids Ids of group_urls.
	 * @return mixed|string
	 */
	public static function delete_group_urls_v2( $group_id, $group_url_ids = array() ) {
		$args = array(
			'action' => 'groups/' . $group_id . '/remove-urls',
			'urls'   => $group_url_ids,
		);
		return self::api_v2( $args, 'PUT' );
	}

	/** Take screenshots.
	 *
	 * @param array  $group_ids Array with group_ids.
	 * @param string $sc_type screenshot type 'pre' or 'post'.
	 * @return mixed|string
	 */
	public static function take_screenshot_v2( $group_ids, $sc_type ) {
		if ( ! is_array( $group_ids ) ) {
			$group_ids = array( $group_ids );
		}
		$args = array(
			'action'    => 'screenshots/take',
			'sc_type'   => $sc_type,
			'group_ids' => $group_ids,
		);
		return self::api_v2( $args );
	}

	/** Add url.
	 *
	 * @param string $url Url to add.
	 * @return mixed|string
	 */
	public static function add_url_v2( $url ) {
		$args = array(
			'action' => 'urls',
			'url'    => $url,
		);
		return self::api_v2( $args );
	}

	/** Add urls to group.
	 *
	 * @param string $group_id Group uuid.
	 * @param array  $params Urls and other params.
	 * @return mixed|string
	 */
	public static function add_urls_to_group_v2( $group_id, $params ) {

		if ( ! is_array( $params ) ) {
			$params[] = $params;
		}

		$args = array(
			'action' => 'groups/' . $group_id . '/add-urls',
			'urls'   => $params,
		);
		$args = array_merge( $args, $params );
		return self::api_v2( $args );
	}

	/** Create new group.
	 *
	 * @param array $args Array with args to create group.
	 * @return mixed|string
	 */
	public static function create_group_v2( $args ) {
		if ( ! is_array( $args ) ) {
			$args = array();
		}
		$possible_args = array(
			'name',
			'monitoring',
			'enabled',
			'hour_of_day',
			'interval_in_h',
			'alert_emails',
			'css',
			'js',
			'threshold',
		);

		// Only allow possible args.
		foreach ( $args as $key => $value ) {
			if ( ! in_array( $key, $possible_args, true ) ) {
				unset( $args[ $key ] );
			}
		}

		$args['action'] = 'groups';
		return self::api_v2( $args );
	}

	/** Get single comparison.
	 *
	 * @param string $comparison_id The comparison id.
	 * @return mixed|string
	 */
	public static function get_comparison_v2( $comparison_id ) {

		$args = array(
			'action' => 'comparisons/' . $comparison_id,
		);

		return self::api_v2( $args, 'GET' );
	}

	/** Get comparisons.
	 *
	 * @param array $filters Filters for getting comparisons.
	 * @return mixed|string
	 */
	public static function get_comparisons_v2( $filters = array() ) {

		// Make sure to show only change detections from the current website.
		if ( empty( $filters['groups'] ) ) {
			$groups = get_option( WCD_WEBSITE_GROUPS );
			if ( $groups ) {
				$filters['groups'] = implode( ',', $groups );
			} else {
				// We don't have a group id. So we can't get comparisons.
				return false;
			}
		}

		$args = array(
			'action' => 'comparisons?' . build_query( $filters ),
		);

		return self::api_v2( $args, 'GET' );
	}

	/** Get single queue
	 *
	 * @param string $batch_id The batch id.
	 * @return mixed|string
	 */
	public static function get_queue_v2( $batch_id = false ) {

		if ( ! $batch_id ) {
			return false;
		}

		$args = array(
			'action' => 'queues?id=' . $batch_id,
		);

		return self::api_v2( $args, 'GET' );
	}

	/** Get queues
	 *
	 * @param array  $batch_ids Array of batch_ids.
	 * @param string $status Status seperatated by comma.
	 * @param array  $group_ids Array of group_ids.
	 * @param array  $filters Additional filters.
	 * @return mixed|string
	 */
	public static function get_queues_v2( $batch_ids = false, $status = false, $group_ids = false, $filters = array() ) {
		$args = array();

		// Batch ids.
		if ( $batch_ids ) {
			if ( is_array( $batch_ids ) ) {
				$args['batches'] = implode( ',', $batch_ids );
			} else {
				$args['batches'] = $batch_ids;
			}
		}

		// Group ids.
		if ( $group_ids ) {
			if ( is_array( $group_ids ) ) {
				$args['groups'] = implode( ',', $group_ids );
			} else {
				$args['groups'] = $group_ids;
			}
		}

		// Status.
		if ( $status ) {
			if ( is_array( $status ) ) {
				$args['status'] = implode( ',', $status );
			} else {
				$args['status'] = $status;
			}
		}

		// Filters.
		if ( ! empty( $filters ) ) {
			$args = array_merge( $args, $filters );
		}

		$args['action'] = 'queues?' . build_query( $args );

		return self::api_v2( $args, 'GET' );
	}

	/** Add webhook.
	 *
	 * @param string $url The url to send the webhook to.
	 * @param string $event The event on which the webhook is sent.
	 * @param string $expires_at The date and time the webhook expires.
	 * @return mixed|string
	 */
	public static function add_webhook_v2( $url, $event, $expires_at = false ) {
		$args = array(
			'action' => 'webhooks',
			'url'    => $url,
			'event'  => $event,
		);

		// The event wordpress_cron always needs an expires_at.
		if ( 'wordpress_cron' === $event && ! $expires_at ) {
			$args['expires_at'] = gmdate( 'Y-m-d H:i:s', time() + HOUR_IN_SECONDS * 3 );
		} elseif ( $expires_at ) {
			$args['expires_at'] = $expires_at;
		}

		return self::api_v2( $args );
	}

	/** Update webhook
	 *
	 * @param string $id Id of the webhook.
	 * @param string $url The url to send the webhook to.
	 * @return mixed|string
	 */
	public static function update_webhook_v2( $id, $url ) {
		$args = array(
			'action' => 'webhooks/' . $id,
			'url'    => $url,
		);
		return self::api_v2( $args, 'PUT' );
	}


	/** Delete webhook
	 *
	 * @param string $id Id of the webhook.
	 * @return false|mixed|string
	 */
	public static function delete_webhook_v2( $id ) {
		if ( ! $id ) {
			return false;
		}
		$args = array(
			'action' => 'webhooks/' . $id,
		);
		return self::api_v2( $args, 'DELETE' );
	}

	/** Get batches.
	 *
	 * @param array $filter Filters for the batches.
	 * @return mixed|string
	 */
	public static function get_batches( $filter = array() ) {
		if ( empty( $filter['group_ids'] ) ) {
			$filter['group_ids'] = implode( ',', get_option( WCD_WEBSITE_GROUPS ) );
		}
		$args = array(
			'action' => 'batches?' . build_query( $filter ),
		);
		return self::api_v2( $args, 'GET' );
	}

	/** Update name of batch.
	 *
	 * @param string $batch_id The batch_id.
	 * @param string $name The new batch name.
	 * @return mixed|string|true
	 */
	public static function update_batch_v2( $batch_id, $name ) {
		$args = array(
			'action' => 'batches/' . $batch_id,
			'name'   => $name,
		);
		return self::api_v2( $args, 'PUT' );
	}

	/** Update comparison.
	 *
	 * @param string $id The comparison id.
	 * @param string $status The status (new, ok, to_fix, false_positive).
	 * @return mixed|string
	 */
	public static function update_comparison_v2( $id, $status ) {
		if ( empty( $id ) ) {
			return 'Id is missing.';
		}

		if ( ! in_array( $status, self::WCD_POSSIBLE_COMPARISON_STATUS, true ) ) {
			return 'Wrong status.';
		}

		$args = array(
			'action' => 'comparisons/' . ( $id ),
			'status' => ( $status ),
		);

		return self::api_v2( $args, 'PUT' );
	}

	/** Call the WCD api.
	 *
	 * @param array  $post All params for the request.
	 * @param string $method The request method.
	 * @param bool   $is_web Call web interface.
	 * @return mixed|string
	 */
	private static function api_v2( $post, $method = 'POST', $is_web = false ) {
		$api_token = get_option( 'webchangedetector_api_token' );

		$url     = 'https://api.webchangedetector.com/api/v2/'; // init for production.
		$url_web = 'https://api.webchangedetector.com/';

		// This is where it can be changed to a local/dev address.
		if ( defined( 'WCD_API_URL_V2' ) && is_string( WCD_API_URL_V2 ) && ! empty( WCD_API_URL_V2 ) ) {
			$url = WCD_API_URL_V2;
		}

		// Overwrite $url if it is a get request.
		if ( $is_web && defined( 'WCD_API_URL_WEB' ) && is_string( WCD_API_URL_WEB ) && ! empty( WCD_API_URL_WEB ) ) {
			$url_web = WCD_API_URL_WEB;
		}
		$multicall = false;
		if ( ! empty( $post['multi_call'] ) ) {
			$multicall = $post['multi_call'];
			unset( $post['multi_call'] );
		}

		$url     .= $post['action']; // add kebab action to url.
		$url_web .= $post['action']; // add kebab action to url.
		$action   = $post['action']; // For debugging.

		unset( $post['action'] ); // don't need to send as action as it's now the url.
		unset( $post['api_token'] ); // just in case.

		// Increase timeout for php.ini.
		if ( ! ini_get( 'safe_mode' ) ) {
			set_time_limit( WCD_REQUEST_TIMEOUT + 10 );
		}

		if ( $multicall ) {
			$args = array();
			foreach ( $post[ $multicall ] as $multicall_data ) {
				$args[] = array(
					'url'     => $url,
					'timeout' => WCD_REQUEST_TIMEOUT,
					'data'    => array_merge( $post, array( $multicall => $multicall_data ) ),
					'type'    => $method,
					'headers' => array(
						'Accept'        => 'application/json',
						'Authorization' => 'Bearer ' . $api_token,
						'x-wcd-domain'  => WebChangeDetector_Admin::get_domain_from_site_url(),
						'x-wcd-wp-id'   => get_current_user_id(),
						'x-wcd-plugin'  => 'webchangedetector-official/' . WEBCHANGEDETECTOR_VERSION,
					),
				);
			}
			if ( ! empty( $args ) ) {
				WebChangeDetector_Admin::error_log( ' API V2 "' . $method . '" request: ' . $url . ' | args: ' . wp_json_encode( $args ) );
				$responses = WpOrg\Requests\Requests::request_multiple(
					$args,
					array(
						'data-format' => 'data',
					)
				);
				$i         = 0;
				foreach ( $responses as $response ) {
					++$i;
					if ( isset( $response->headers['date'] ) ) {
						WebChangeDetector_Admin::error_log( "Responsetime Request $i: " . $response->headers['date'] );
					}
				}

				$response_code = (int) wp_remote_retrieve_response_code( $responses );
				WebChangeDetector_Admin::error_log( ' Response code curl-multi-call: ' . $response_code );

			}
		} else {
			$args = array(
				'timeout' => WCD_REQUEST_TIMEOUT,
				'body'    => $post,
				'method'  => $method,
				'headers' => array(
					'Accept'        => 'application/json',
					'Authorization' => 'Bearer ' . $api_token,
					'x-wcd-domain'  => WebChangeDetector_Admin::get_domain_from_site_url(),
					'x-wcd-wp-id'   => get_current_user_id(),
					'x-wcd-plugin'  => 'webchangedetector-official/' . WEBCHANGEDETECTOR_VERSION,
				),
			);

			$log_args = $args;
			WebChangeDetector_Admin::error_log( ' API V2 "' . $method . '" request: ' . $url . ' | args: ' . wp_json_encode( $log_args ) );

			if ( $is_web ) {
				$response = wp_remote_request( $url_web, $args );
			} else {
				// Todo Check if api token is empty.
				if ( empty( $api_token ) ) {
					return 'No API token found';
				}
				$response = wp_remote_request( $url, $args );
			}
			$body          = wp_remote_retrieve_body( $response );
			$response_code = (int) wp_remote_retrieve_response_code( $response );

			WebChangeDetector_Admin::error_log( 'Responsecode: ' . $response_code );
			$decoded_body = json_decode( $body, (bool) JSON_OBJECT_AS_ARRAY );
			if ( 200 !== $response_code ) {
				if ( ! empty( $decoded_body ) && is_array( $decoded_body ) ) {
					// phpcs:ignore
					WebChangeDetector_Admin::error_log( print_r( $decoded_body, 1 ) );
				} else {
					// phpcs:ignore
					WebChangeDetector_Admin::error_log( print_r( $body, 1 ) );
				}
			}
		}

		// `message` is part of the Laravel Stacktrace.
		if ( WCD_HTTP_BAD_REQUEST === $response_code &&
			! empty( $decoded_body ) &&
			is_array( $decoded_body ) &&
			array_key_exists( 'message', $decoded_body ) &&
			'plugin_update_required' === $decoded_body['message'] ) {
			return 'update plugin';
		}

		if ( WCD_HTTP_INTERNAL_SERVER_ERROR === $response_code && 'account_details' === $action ) {
			return 'activate account';
		}

		if ( WCD_HTTP_UNAUTHORIZED === $response_code ) {
			return 'unauthorized';
		}

		// if parsing JSON into $decoded_body was without error.
		if ( JSON_ERROR_NONE === json_last_error() ) {
			return $decoded_body ?? true;
		}

		return $body ?? true;
	}
}
