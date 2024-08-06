<?php
/**
 * The requests to webchangedetector api v2.
 *
 * @package    WebChangeDetector
 * @subpackage WebChangeDetector/admin
 * @author     Mike Miler <mike@wp-mike.com>
 */

/**
 * Class for wcd api v2 requests.
 *
 * @package    WebChangeDetector
 */
class WebChangeDetector_API_V2 {

	/** Sync urls.
	 *
	 * @param array $posts The posts to sync.
	 * @param bool  $delete_missing_urls Delete missing urls or not.
	 * @return false|mixed|string
	 */
	public static function sync_urls( $posts, $delete_missing_urls = true ) {
		if ( ! is_array( $posts ) ) {
			return false;
		}

		$args = array(
			'action'              => 'urls/sync',
			'urls'                => ( $posts ),
			'delete_missing_urls' => $delete_missing_urls,
		);

		return self::api_v2( $args );
	}

	/** Get group details.
* @param $group_id
* @return mixed|string
	 */
	public static function get_group_v2 ($group_id) {
		$args = array(
			'action' => 'groups/'.$group_id
		);
		return self::api_v2($args, 'GET' );
	}

	/** Get urls of a group.
	 *
	 * @param int $group_id The group id.
	 *
	 * @return array|mixed|string
	 */
	public static function get_group_urls_v2( $group_id, $filters = [] ) {
		$args = array(
			'action'   => 'groups/'.$group_id . '/urls?' . build_query($filters),
		);

		return self::api_v2( $args, 'GET' );
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

	/** Get comparisons.
	 *
	 * @param array $filters Filters for getting comparisons.
	 * @return mixed|string
	 */
	public static function get_comparisons_v2( $filters = array() ) {
		$url = 'comparisons';
		if ( ! empty( $filters ) ) {
			$url = $url . '?' . ( build_query( $filters ) );
		}

		$args = array(
			'action' => $url,
		);

		return self::api_v2( $args, 'GET' );
	}

	/** Get queues
	 *
	 * @param string $batch_id The batch id.
	 * @param string $status Status seperatated by comma.
	 * @return mixed|string
	 */
	public static function get_queue_v2( $batch_id = false, $status = false ) {
		$args = array();
		if ( $batch_id ) {
			$args['batch'] = $batch_id;
		}
		if ( $status ) {
			$args['status'] = $status;
		}

		$args = array(
			'action' => 'queues?' . build_query( $args ),
		);

		return self::api_v2( $args, 'GET' );
	}

	/** Add webhook
	 *
	 * @param string $url The url to send the webhook to.
	 * @param string $event The event on which the webhook is sent.
	 * @return mixed|string
	 */
	public static function add_webhook_v2( $url, $event ) {
		$args = array(
			'action' => 'webhooks',
			'url'    => $url,
			'event'  => $event,
		);
		return self::api_v2( $args );
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
		$args = array(
			'action' => 'batches?' . build_query( $filter ),
		);
		return self::api_v2( $args, 'GET' );
	}

	/** Update comparison.
	 *
	 * @param string $id The comparison id.
	 * @param string $status The status (new, ok, to_fix, false_positive).
	 * @return mixed|string
	 */
	public static function update_comparison_v2( $id, $status ) {
		$possible_status = array(
			'new',
			'ok',
			'to_fix',
			'false_positive',
		);
		if ( ! in_array( $status, $possible_status, true ) ) {
			return false;
		}

		$args = array(
			'action' => 'comparisons/' . esc_html( $id ),
			'status' => esc_html( $status ),
		);
		return self::api_v2( $args, 'PATCH' );
	}

	/** Call the WCD api.
	 *
	 * @param array  $post All params for the request.
	 * @param string $method The request method.
	 * @param bool   $is_web Call web interface.
	 * @return mixed|string
	 */
	private static function api_v2( $post, $method = 'POST', $is_web = false, ) {
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

		$url     .= $post['action']; // add kebab action to url.
		$url_web .= $post['action']; // add kebab action to url.
		$action   = $post['action']; // For debugging.

		unset( $post['action'] ); // don't need to send as action as it's now the url.
		unset( $post['api_token'] ); // just in case.

		$post['wp_plugin_version'] = WEBCHANGEDETECTOR_VERSION; // API will check this to check compatability.
		// there's checks in place on the API side, you can't just send a different domain here, you sneaky little hacker ;).
		$post['domain'] = isset( $_SERVER['SERVER_NAME'] ) ? sanitize_text_field( wp_unslash( $_SERVER['SERVER_NAME'] ) ) : '';
		$post['wp_id']  = get_current_user_id();

		// Increase timeout for php.ini.
		if ( ! ini_get( 'safe_mode' ) ) {
			set_time_limit( WCD_REQUEST_TIMEOUT + 10 );
		}

		$args = array(
			'timeout' => WCD_REQUEST_TIMEOUT,
			'body'    => $post,
			'method'  => $method,
			'headers' => array(
				'Accept'        => 'application/json',
				'Authorization' => 'Bearer ' . $api_token,
			),
		);

		WebChangeDetector_Admin::error_log( 'Sending API V2 request: ' . $url . ' | args: ' . wp_json_encode( $args ) );

		if ( $is_web ) {
			$response = wp_remote_request( $url_web, $args );
		} else {
			$response = wp_remote_request( $url, $args );
		}

		$body          = wp_remote_retrieve_body( $response );
		$response_code = (int) wp_remote_retrieve_response_code( $response );
		$decoded_body  = json_decode( $body, (bool) JSON_OBJECT_AS_ARRAY );

		// `message` is part of the Laravel Stacktrace.
		if ( WCD_HTTP_BAD_REQUEST === $response_code &&
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
			return $decoded_body;
		}

		return $body;
	}
}
