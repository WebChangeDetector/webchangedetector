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
class Wp_Compare_API_V2 extends Wp_Compare {

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
	public static function get_account_v2($api_token = null) {
		return self::api_v2( array( 'action' => 'account' ), 'GET', $api_token );
	}

	/** Sync urls.
	 *
	 * @param array $posts The posts to sync.
	 * @return false|mixed|string
	 */
	public static function sync_urls( $posts, $domain ) {
		if ( ! is_array( $posts ) ) {
			return false;
		}

		$args = array(
			'action'     => 'sync-urls',
			'domain'     => rtrim( mm_get_domain( $domain ), '/' ),
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
	public static function start_url_sync( $domain, $delete_missing_urls = true ) {
		return self::api_v2(
			array(
				'action'              => 'start-sync',
				'domain'              => rtrim( mm_get_domain( $domain ), '/' ),
				'delete_missing_urls' => $delete_missing_urls,
			)
		);
	}

	/**
	 * Sync URLs with specific API token for background jobs.
	 *
	 * @param array $urls URLs to sync.
	 * @param string $domain Website domain.
	 * @param string $api_token API token to use.
	 */
	public static function sync_urls_with_token( $urls, $domain, $api_token ) {
		if ( ! is_array( $urls ) ) {
			return false;
		}

		$args = array(
			'action'     => 'sync-urls',
			'domain'     => rtrim( mm_get_domain( $domain ), '/' ),
			'urls'       => $urls,
			'multi_call' => 'urls', // Keep multicall for performance
		);

		return self::api_v2( $args, 'POST', $api_token );
	}
	
	/**
	 * Start URL sync with specific API token for background jobs.
	 *
	 * @param string $domain Website domain.
	 * @param string $api_token API token to use.
	 * @param bool $delete_missing_urls Delete missing URLs.
	 */
	public static function start_url_sync_with_token( $domain, $api_token, $delete_missing_urls = true ) {
		$args = array(
			'action'              => 'start-sync',
			'domain'              => rtrim( mm_get_domain( $domain ), '/' ),
			'delete_missing_urls' => $delete_missing_urls,
		);
		
		return self::api_v2( $args, 'POST', $api_token );
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

	/** Update an url.
	 *
	 * @param $postdata
	 * @return array|string|null
	 */
	public static function update_url( $postdata ) {

		$args = array(
			'action'     => 'urls/' . $postdata['url_id'],
			'url'        => ! starts_with( $postdata['url'], 'http' ) ? 'http://' . $postdata['url'] : $postdata['url'],
		);
		if(!empty($postdata['html_title'])) {
			$args['html_title'] = $postdata['html_title'];
		}

		return self::api_v2( $args, 'PUT' );
	}

	/** Update urls.
	 *
	 * @param int   $group_id The group id.
	 * @param array $active_posts All active posts.
	 *
	 * @return array|string
	 */
	public static function update_url_in_group_v2( $group_id, $url_id, $args = array() ) {

		$params = array(
			'action'    => 'groups/' . $group_id . '/urls/' . $url_id,
			'threshold' => ! empty( $args['threshold'] ) ? $args['threshold'] : 0,
		);
		if ( isset( $args['css'] ) ) {
			$params['css'] = $args['css'];
		}
		if ( isset( $args['js'] ) ) {
			$params['js'] = $args['js'];
		}
		if ( isset( $args['desktop'] ) ) {
			$params['desktop'] = $args['desktop'];
		}
		if ( isset( $args['mobile'] ) ) {
			$params['mobile'] = $args['mobile'];
		}
		return self::api_v2( $params, 'PUT' );
	}

	/**
	 * Get groups.
	 *
	 * @return mixed|string
	 */
	public static function get_groups_v2($filters = []) {
		$args = array(
			'action' => 'groups',
			'per_page' => 50,
		);
		$args = array_merge( $args, $filters );
		return self::api_v2( $args, 'GET' );
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
		if(!starts_with(strtolower($url), 'http')) {
			$url = "http://" . $url;
		}
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
	 *
	 * @return mixed|string
	 */
	public static function add_urls_to_group_v2( $group_id, $urls ) {

		if ( ! is_array( $urls ) ) {
			$urls[] = $urls;
		}

		$args = array(
			'action' => 'groups/' . $group_id . '/add-urls',
			'urls'   => $urls,
		);

		return self::api_v2( $args );
	}

	public static function remove_urls_from_group_v2($group_id, $urls) {
		$params = array(
    		'action' => 'groups/' . $group_id . '/remove-urls',
    		'urls'   => $urls,
    	);

    	return self::api_v2( $params, 'POST' );
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
			'cms'
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

	public static function delete_group_v2( $group_id ) {
		$args = array(
			'action'   => 'groups/' . $group_id,
		);
		return self::api_v2($args, 'DELETE');
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
	 * @param string $status Status seperated by comma.
	 * @param array  $filters Additional filters.
	 * @return mixed|string
	 */
	public static function get_queue_v2( $batch_id = false, $status = false, $filters = array() ) {
		$args = array();
		if ( $batch_id ) {
			$args['batch'] = $batch_id;
		}
		if ( $status ) {
			$args['status'] = $status;
		}
		if ( ! empty( $filters ) ) {
			$args = array_merge( $args, $filters );
		}
		if(!isset($args['per_page'])) {
			$args['per_page'] = 100;
		}

		$args = array(
			'action' => 'queues?' . build_query( $args ),
		);

		return self::api_v2( $args, 'GET' );
	}

	/** Get queues
	 *
	 * @param string $batch_id The batch id.
	 * @param string $status Status seperatated by comma.
	 * @param array  $filters Additional filters.
	 * @return mixed|string
	 */
	public static function get_queues_v2( $batch_ids = [], $status = false, $filters = array() ) {
		$args = array();

		if(!is_array($batch_ids )) {
			return false;
		}
		if ( $batch_ids ) {
			$args['batches'] = implode(",",$batch_ids);
		}
		if ( $status ) {
			$args['status'] = $status;
		}
		
		$args = array_merge( $args, $filters );
		$args['action'] = 'queues';

		return self::api_v2( $args, 'GET' );
	}

	/** Get websites
	 * @param $filters
	 *
	 * @return mixed|string|true
	 */
	public static function get_websites_v2( $filters = [], $api_token = null ) {
		$args = array(
			'action' => 'websites',
		);
		$args = array_merge( $args, $filters );

		return self::api_v2( $args, 'GET', $api_token );
	}

	public static function update_website_v2( $args = array(), $api_token = null ) {
		if(empty($args['id'])) {
			return false;
		}
		$args['action'] = "websites/{$args['id']}";

		return self::api_v2( $args, 'PUT', $api_token );
	}

	/** Create new website.
	 *
	 * @param array $args Array with args to create website.
	 * @return mixed|string
	 */
	public static function create_website_v2( $args ) {
		if ( ! is_array( $args ) ) {
			$args = array();
		}
		$possible_args = array(
			'domain',
			'manual_detection_group_id',
			'auto_detection_group_id',
			'sync_url_types',
			'auto_update_settings',
			'allowances',
		);

		// Only allow possible args.
		foreach ( $args as $key => $value ) {
			if ( ! in_array( $key, $possible_args, true ) ) {
				unset( $args[ $key ] );
			}
		}

		$args['action'] = 'websites';
		return self::api_v2( $args );
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

	/** Get single batch.
	 *
	 * @param string $batch_id The batch_id.
	 * @return mixed|string
	 */
	public static function get_batch( $batch_id = false ) {

		if(!$batch_id) {
			return false;
		}

		$args = array(
			'action' => 'batches/' . $batch_id,
		);
		return self::api_v2( $args, 'GET' );
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

	public static function get_subaccounts() {
		$args = array(
			'action' => 'subaccounts',
		);
		return self::api_v2( $args, 'GET' );
	}

	public static function add_subaccount($args) {
		$args = array(
			'action' => 'subaccounts',
			'name_first' => $args['name_first'] ?? 'n/a',
			'name_last' => $args['name_last'] ?? 'n/a',
			'email' => $args['email'],
			'limit_checks' => $args['limit_checks']
		);
		return self::api_v2( $args, 'POST' );
	}

	public static function update_subaccount( $args  ) {
		$args['action'] = 'subaccounts/' . $args['id'];
		return self::api_v2( $args, 'PUT' );
	}


	/** Call the WCD api.
	 *
	 * @param array  $post All params for the request.
	 * @param string $method The request method.
	 * @param bool   $is_web Call web interface.
	 * @return mixed|string
	 */
	private static function api_v2( $post, $method = 'POST', $use_api_token = null ) {

		// Get the right api-token
		$selected_api_token = get_user_meta(get_current_user_id(), "wcd_active_api_token", true);
		if(!$selected_api_token) {
			update_user_meta(get_current_user_id(), "wcd_active_api_token", mm_api_token());
			$selected_api_token = mm_api_token();
		}

		// Some actions have to be performed by the main account.
		$actions_with_main_account = [
			'subaccounts',
		];

		if( !empty($use_api_token)) { // We get api token in call.
			$api_token = $use_api_token;
		} elseif (in_array( $post['action'], $actions_with_main_account)) { // Use main token for specific actions.
			$api_token = mm_api_token();
		} else {
			$api_token = $selected_api_token; // Use currently selected api token in webapp.
		}

		error_log("api_token: " . print_r($api_token, true));

		$url     = 'https://api.webchangedetector.com/api/v2/'; // init for production.
		$url_web = 'https://api.webchangedetector.com/';

		// This is where it can be changed to a local/dev address.
		if ( defined( 'WCD_API_URL_V2' ) && is_string( WCD_API_URL_V2 ) && ! empty( WCD_API_URL_V2 ) ) {
			$url = WCD_API_URL_V2;
		}

		// Overwrite $url if it is a get request. Do we still have web requests?
		/*if ( $is_web && defined( 'WCD_API_URL_WEB' ) && is_string( WCD_API_URL_WEB ) && ! empty( WCD_API_URL_WEB ) ) {
			$url_web = WCD_API_URL_WEB;
		}*/
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
					'domain'  => $post['domain'],
					'headers' => array(
						'Accept'        => 'application/json',
						'Authorization' => 'Bearer ' . $api_token,
					),
				);
			}
			if ( ! empty( $args ) ) {
				error_log( ' API V2 "' . $method . '" request: ' . $url . ' | args: multiple curl call' );
				error_log(print_r($args, true));
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
						error_log( "Responsetime Request $i: " . $response->headers['date'] );
					}
				}

				$response_code = (int) wp_remote_retrieve_response_code( $responses );
				error_log( ' Response code curl-multi-call: ' . $response_code );

			}
		} else {
			$args = array(
				'timeout' => WCD_REQUEST_TIMEOUT,
				'body'    => $post,
				'method'  => $method,
				'headers' => array(
					'Accept'        => 'application/json',
					'Authorization' => 'Bearer ' . $api_token,
				),
			);

			$log_args = $args;
			$startTime = microtime( true );
			error_log( ' API V2 "' . $method . '" request: ' . $url . ' | args: ' . wp_json_encode( $log_args ) );


			/*if ( $is_web ) {
				$response = wp_remote_request( $url_web, $args );
			} */

			$response = wp_remote_request( $url, $args );

			$body          = wp_remote_retrieve_body( $response );
			$response_code = (int) wp_remote_retrieve_response_code( $response );

			error_log( 'Responsecode: ' . $response_code . " after " . (microtime( true ) - $startTime) . " seconds.");

			$decoded_body = json_decode( $body, (bool) JSON_OBJECT_AS_ARRAY );
			if ( 200 !== $response_code  && 201 !== $response_code ) {
				if ( ! empty( $decoded_body ) && is_array( $decoded_body ) ) {
					error_log( print_r( $decoded_body, 1 ) );
				} else {
					error_log( print_r( $body, 1 ) );
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
