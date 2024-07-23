<?php

class WebChangeDetector_API_V2 {

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

	public static function add_url_v2( $url ) {
		$args = array(
			'action' => 'urls',
			'url'    => $url,
		);
		return self::api_v2( $args );
	}

	public static function add_urls_to_group_v2( $group, $params ) {

		if ( ! is_array( $params ) ) {
			$params[] = $params;
		}

		$args = array(
			'action' => 'groups/' . $group . '/add-urls',
			'urls'   => $params,
		);
		$args = array_merge( $args, $params );
		return self::api_v2( $args );
	}

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
			if ( ! in_array( $key, $possible_args ) ) {
				unset( $args[ $key ] );
			}
		}

		$args['action'] = 'groups';
		return self::api_v2( $args );
	}

	public static function get_comparisons_v2( $filters = array() ) {
		$url = 'comparisons';
		if ( ! empty( $filters ) ) {
			$url = $url . '?' . build_query( $filters );
		}

		$args = array(
			'action' => $url,
		);
		return self::api_v2( $args, 'GET' );
	}

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

	public static function add_webhook_v2( $url, $event ) {
		$args = array(
			'action' => 'webhooks',
			'url'    => $url,
			'event'  => $event,
		);
		return self::api_v2( $args );
	}

	public static function delete_webhook_v2( $id ) {
		if ( $id ) {
			return false;
		}
		$args = array(
			'action' => 'webhooks/' . $id,
		);
		return self::api_v2( $args, 'DELETE' );
	}

	private static function api_v2( $post, $method = 'POST', $isWeb = false, ) {
		$api_token = get_option( 'webchangedetector_api_token' );
		if ( ! $api_token ) {
			$new_sub_account = self::create_sub_account_v2();
		}
		$url    = 'https://api.webchangedetector.com/api/v2/'; // init for production.
		$urlWeb = 'https://api.webchangedetector.com/';

		// This is where it can be changed to a local/dev address.
		if ( defined( 'WCD_API_URL_V2' ) && is_string( WCD_API_URL_V2 ) && ! empty( WCD_API_URL_V2 ) ) {
			$url = WCD_API_URL_V2;
		}

		// Overwrite $url if it is a get request.
		if ( $isWeb && defined( 'WCD_API_URL_WEB' ) && is_string( WCD_API_URL_WEB ) && ! empty( WCD_API_URL_WEB ) ) {
			$urlWeb = WCD_API_URL_WEB;
		}

		$url    .= str_replace( '_', '-', $post['action'] ); // add kebab action to url.
		$urlWeb .= str_replace( '_', '-', $post['action'] ); // add kebab action to url.
		$action  = $post['action']; // For debugging.

		// Get API Token from WP DB.
		// $api_token = $post['api_token'] ?? get_option( WCD_WP_OPTION_KEY_API_TOKEN ) ?? null;

		unset( $post['action'] ); // don't need to send as action as it's now the url.
		// unset( $post['api_token'] ); // just in case.

		$post['wp_plugin_version'] = WEBCHANGEDETECTOR_VERSION; // API will check this to check compatability.
		// there's checks in place on the API side, you can't just send a different domain here, you sneaky little hacker ;).
		$post['domain'] = $_SERVER['SERVER_NAME'];
		$post['wp_id']  = get_current_user_id();

		// Increase timeout for php.ini

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

		error_log( 'Sending API V2 request: ' . $url . ' | args: ' . json_encode( $args ) );

		if ( $isWeb ) {
			$response = wp_remote_request( $urlWeb, $args );
		} else {
			$response = wp_remote_request( $url, $args );
		}

		$body         = wp_remote_retrieve_body( $response );
		$responseCode = (int) wp_remote_retrieve_response_code( $response );

		$decodedBody = json_decode( $body, (bool) JSON_OBJECT_AS_ARRAY );

		// `message` is part of the Laravel Stacktrace.
		if ( $responseCode === WCD_HTTP_BAD_REQUEST &&
			is_array( $decodedBody ) &&
			array_key_exists( 'message', $decodedBody ) &&
			$decodedBody['message'] === 'plugin_update_required' ) {
			return 'update plugin';
		}

		if ( $responseCode === WCD_HTTP_INTERNAL_SERVER_ERROR && $action === 'account_details' ) {
			return 'activate account';
		}

		if ( $responseCode === WCD_HTTP_UNAUTHORIZED ) {
			return 'unauthorized';
		}

		// if parsing JSON into $decodedBody was without error.
		if ( json_last_error() === JSON_ERROR_NONE ) {
			return $decodedBody;
		}

		return $body;
	}
}
