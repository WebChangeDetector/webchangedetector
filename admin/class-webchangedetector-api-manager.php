<?php
/**
 * WebChange Detector API Manager
 *
 * Centralizes all API communication with the WebChange Detector service.
 * Follows WordPress coding standards and Model-View-Presenter pattern.
 *
 * @link       https://www.webchangedetector.com
 * @since      1.0.0
 *
 * @package    WebChangeDetector
 * @subpackage WebChangeDetector/admin
 */

namespace WebChangeDetector;

/**
 * WebChange Detector API Manager Class
 *
 * This class handles all API communications with the WebChange Detector service.
 * It provides a centralized interface for all API operations.
 *
 * @package    WebChangeDetector
 * @subpackage WebChangeDetector/admin
 * @author     WebChange Detector <support@webchangedetector.com>
 */
class WebChangeDetector_API_Manager {

	/**
	 * The API base URL.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	private $api_base_url;

	/**
	 * The API token for authentication.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	private $api_token;

	/**
	 * Default request timeout in seconds.
	 *
	 * @since 1.0.0
	 * @var int
	 */
	private $default_timeout;

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 * @param string $api_token The API token for authentication.
	 * @param string $api_base_url Optional. The API base URL.
	 */
	public function __construct( $api_token = '', $api_base_url = '' ) {
		$this->api_token       = $api_token ?: get_option( 'webchangedetector_api_token', '' );
		$this->api_base_url    = $api_base_url ?: 'https://api.webchangedetector.com/api/v1';
		$this->default_timeout = 30;
	}

	/**
	 * Make a GET request to the API.
	 *
	 * @since 1.0.0
	 * @param string $endpoint The API endpoint.
	 * @param array  $params   Optional. Query parameters.
	 * @param array  $args     Optional. Additional request arguments.
	 * @return array|WP_Error Response data or WP_Error on failure.
	 */
	public function get( $endpoint, $params = array(), $args = array() ) {
		$url = $this->build_url( $endpoint, $params );
		$request_args = $this->build_request_args( 'GET', array(), $args );

		return $this->make_request( $url, $request_args );
	}

	/**
	 * Make a POST request to the API.
	 *
	 * @since 1.0.0
	 * @param string $endpoint The API endpoint.
	 * @param array  $data     Optional. Request body data.
	 * @param array  $args     Optional. Additional request arguments.
	 * @return array|WP_Error Response data or WP_Error on failure.
	 */
	public function post( $endpoint, $data = array(), $args = array() ) {
		$url = $this->build_url( $endpoint );
		$request_args = $this->build_request_args( 'POST', $data, $args );

		return $this->make_request( $url, $request_args );
	}

	/**
	 * Make a PUT request to the API.
	 *
	 * @since 1.0.0
	 * @param string $endpoint The API endpoint.
	 * @param array  $data     Optional. Request body data.
	 * @param array  $args     Optional. Additional request arguments.
	 * @return array|WP_Error Response data or WP_Error on failure.
	 */
	public function put( $endpoint, $data = array(), $args = array() ) {
		$url = $this->build_url( $endpoint );
		$request_args = $this->build_request_args( 'PUT', $data, $args );

		return $this->make_request( $url, $request_args );
	}

	/**
	 * Make a DELETE request to the API.
	 *
	 * @since 1.0.0
	 * @param string $endpoint The API endpoint.
	 * @param array  $args     Optional. Additional request arguments.
	 * @return array|WP_Error Response data or WP_Error on failure.
	 */
	public function delete( $endpoint, $args = array() ) {
		$url = $this->build_url( $endpoint );
		$request_args = $this->build_request_args( 'DELETE', array(), $args );

		return $this->make_request( $url, $request_args );
	}

	/**
	 * Account API methods.
	 */

	/**
	 * Get account information.
	 *
	 * @since 1.0.0
	 * @return array|WP_Error Account data or WP_Error on failure.
	 */
	public function get_account() {
		return $this->get( '/account' );
	}

	/**
	 * Update account information.
	 *
	 * @since 1.0.0
	 * @param array $account_data The account data to update.
	 * @return array|WP_Error Response data or WP_Error on failure.
	 */
	public function update_account( $account_data ) {
		return $this->put( '/account', $account_data );
	}

	/**
	 * Website API methods.
	 */

	/**
	 * Get all websites.
	 *
	 * @since 1.0.0
	 * @return array|WP_Error Websites data or WP_Error on failure.
	 */
	public function get_websites() {
		return $this->get( '/websites' );
	}

	/**
	 * Get a specific website.
	 *
	 * @since 1.0.0
	 * @param int $website_id The website ID.
	 * @return array|WP_Error Website data or WP_Error on failure.
	 */
	public function get_website( $website_id ) {
		return $this->get( "/websites/{$website_id}" );
	}

	/**
	 * Create a new website.
	 *
	 * @since 1.0.0
	 * @param array $website_data The website data.
	 * @return array|WP_Error Response data or WP_Error on failure.
	 */
	public function create_website( $website_data ) {
		return $this->post( '/websites', $website_data );
	}

	/**
	 * Update a website.
	 *
	 * @since 1.0.0
	 * @param int   $website_id   The website ID.
	 * @param array $website_data The website data to update.
	 * @return array|WP_Error Response data or WP_Error on failure.
	 */
	public function update_website( $website_id, $website_data ) {
		return $this->put( "/websites/{$website_id}", $website_data );
	}

	/**
	 * Delete a website.
	 *
	 * @since 1.0.0
	 * @param int $website_id The website ID.
	 * @return array|WP_Error Response data or WP_Error on failure.
	 */
	public function delete_website( $website_id ) {
		return $this->delete( "/websites/{$website_id}" );
	}

	/**
	 * Group API methods.
	 */

	/**
	 * Get all groups for a website.
	 *
	 * @since 1.0.0
	 * @param int $website_id The website ID.
	 * @return array|WP_Error Groups data or WP_Error on failure.
	 */
	public function get_groups( $website_id ) {
		return $this->get( "/websites/{$website_id}/groups" );
	}

	/**
	 * Create a new group.
	 *
	 * @since 1.0.0
	 * @param int   $website_id  The website ID.
	 * @param array $group_data  The group data.
	 * @return array|WP_Error Response data or WP_Error on failure.
	 */
	public function create_group( $website_id, $group_data ) {
		return $this->post( "/websites/{$website_id}/groups", $group_data );
	}

	/**
	 * URL API methods.
	 */

	/**
	 * Get all URLs for a group.
	 *
	 * @since 1.0.0
	 * @param int $group_id The group ID.
	 * @return array|WP_Error URLs data or WP_Error on failure.
	 */
	public function get_urls( $group_id ) {
		return $this->get( "/groups/{$group_id}/urls" );
	}

	/**
	 * Create URLs for a group.
	 *
	 * @since 1.0.0
	 * @param int   $group_id  The group ID.
	 * @param array $urls_data The URLs data.
	 * @return array|WP_Error Response data or WP_Error on failure.
	 */
	public function create_urls( $group_id, $urls_data ) {
		return $this->post( "/groups/{$group_id}/urls", $urls_data );
	}

	/**
	 * Update a URL.
	 *
	 * @since 1.0.0
	 * @param int   $url_id   The URL ID.
	 * @param array $url_data The URL data to update.
	 * @return array|WP_Error Response data or WP_Error on failure.
	 */
	public function update_url( $url_id, $url_data ) {
		return $this->put( "/urls/{$url_id}", $url_data );
	}

	/**
	 * Delete a URL.
	 *
	 * @since 1.0.0
	 * @param int $url_id The URL ID.
	 * @return array|WP_Error Response data or WP_Error on failure.
	 */
	public function delete_url( $url_id ) {
		return $this->delete( "/urls/{$url_id}" );
	}

	/**
	 * Comparison API methods.
	 */

	/**
	 * Get comparisons for a URL.
	 *
	 * @since 1.0.0
	 * @param int   $url_id The URL ID.
	 * @param array $params Optional. Query parameters.
	 * @return array|WP_Error Comparisons data or WP_Error on failure.
	 */
	public function get_comparisons( $url_id, $params = array() ) {
		return $this->get( "/urls/{$url_id}/comparisons", $params );
	}

	/**
	 * Get batch comparisons.
	 *
	 * @since 1.0.0
	 * @param array $params Optional. Query parameters.
	 * @return array|WP_Error Batch comparisons data or WP_Error on failure.
	 */
	public function get_batch_comparisons( $params = array() ) {
		return $this->get( '/batch/comparisons', $params );
	}

	/**
	 * Queue API methods.
	 */

	/**
	 * Get processing queue.
	 *
	 * @since 1.0.0
	 * @param array $params Optional. Query parameters.
	 * @return array|WP_Error Queue data or WP_Error on failure.
	 */
	public function get_processing_queue( $params = array() ) {
		return $this->get( '/queue', $params );
	}

	/**
	 * Get failed queues.
	 *
	 * @since 1.0.0
	 * @param array $params Optional. Query parameters.
	 * @return array|WP_Error Failed queues data or WP_Error on failure.
	 */
	public function get_failed_queues( $params = array() ) {
		return $this->get( '/queue/failed', $params );
	}

	/**
	 * Screenshot API methods.
	 */

	/**
	 * Get screenshots for a URL.
	 *
	 * @since 1.0.0
	 * @param int   $url_id The URL ID.
	 * @param array $params Optional. Query parameters.
	 * @return array|WP_Error Screenshots data or WP_Error on failure.
	 */
	public function get_screenshots( $url_id, $params = array() ) {
		return $this->get( "/urls/{$url_id}/screenshots", $params );
	}

	/**
	 * Private helper methods.
	 */

	/**
	 * Build the full URL for an API endpoint.
	 *
	 * @since 1.0.0
	 * @param string $endpoint The API endpoint.
	 * @param array  $params   Optional. Query parameters.
	 * @return string The full URL.
	 */
	private function build_url( $endpoint, $params = array() ) {
		$url = rtrim( $this->api_base_url, '/' ) . '/' . ltrim( $endpoint, '/' );

		if ( ! empty( $params ) ) {
			$url = add_query_arg( $params, $url );
		}

		return $url;
	}

	/**
	 * Build request arguments for wp_remote_request.
	 *
	 * @since 1.0.0
	 * @param string $method The HTTP method.
	 * @param array  $data   Optional. Request body data.
	 * @param array  $args   Optional. Additional arguments.
	 * @return array Request arguments.
	 */
	private function build_request_args( $method, $data = array(), $args = array() ) {
		$default_args = array(
			'method'  => $method,
			'timeout' => $this->default_timeout,
			'headers' => array(
				'Authorization' => 'Bearer ' . $this->api_token,
				'Content-Type'  => 'application/json',
				'User-Agent'    => 'WebChangeDetector-WordPress/' . WEBCHANGEDETECTOR_VERSION,
			),
		);

		if ( ! empty( $data ) && in_array( $method, array( 'POST', 'PUT', 'PATCH' ), true ) ) {
			$default_args['body'] = wp_json_encode( $data );
		}

		return wp_parse_args( $args, $default_args );
	}

	/**
	 * Make the actual API request.
	 *
	 * @since 1.0.0
	 * @param string $url  The request URL.
	 * @param array  $args The request arguments.
	 * @return array|WP_Error Response data or WP_Error on failure.
	 */
	private function make_request( $url, $args ) {
		// Log the request if debugging is enabled
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			\WebChangeDetector\WebChangeDetector_Admin_Utils::log_error( "API Request: {$args['method']} {$url}", 'API' );
		}

		$response = wp_remote_request( $url, $args );

		if ( is_wp_error( $response ) ) {
			\WebChangeDetector\WebChangeDetector_Admin_Utils::log_error( 'API Request failed: ' . $response->get_error_message(), 'API' );
			return $response;
		}

		$response_code = wp_remote_retrieve_response_code( $response );
		$response_body = wp_remote_retrieve_body( $response );

		// Log the response if debugging is enabled
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			\WebChangeDetector\WebChangeDetector_Admin_Utils::log_error( "API Response: {$response_code}", 'API' );
		}

		if ( $response_code >= 400 ) {
			$error_message = sprintf( 'API request failed with status %d', $response_code );
			
			// Try to get error details from response body
			if ( \WebChangeDetector\WebChangeDetector_Admin_Utils::is_json( $response_body ) ) {
				$error_data = json_decode( $response_body, true );
				if ( isset( $error_data['message'] ) ) {
					$error_message .= ': ' . $error_data['message'];
				}
			}

			\WebChangeDetector\WebChangeDetector_Admin_Utils::log_error( $error_message, 'API' );
			return new \WP_Error( 'api_error', $error_message, array( 'status' => $response_code ) );
		}

		// Parse JSON response
		if ( \WebChangeDetector\WebChangeDetector_Admin_Utils::is_json( $response_body ) ) {
			return json_decode( $response_body, true );
		}

		return $response_body;
	}

	/**
	 * Set the API token.
	 *
	 * @since 1.0.0
	 * @param string $api_token The API token.
	 */
	public function set_api_token( $api_token ) {
		$this->api_token = $api_token;
	}

	/**
	 * Get the current API token.
	 *
	 * @since 1.0.0
	 * @return string The API token.
	 */
	public function get_api_token() {
		return $this->api_token;
	}

	/**
	 * Set the API base URL.
	 *
	 * @since 1.0.0
	 * @param string $api_base_url The API base URL.
	 */
	public function set_api_base_url( $api_base_url ) {
		$this->api_base_url = $api_base_url;
	}

	/**
	 * Get the current API base URL.
	 *
	 * @since 1.0.0
	 * @return string The API base URL.
	 */
	public function get_api_base_url() {
		return $this->api_base_url;
	}

	/**
	 * Check if API is properly configured.
	 *
	 * @since 1.0.0
	 * @return bool True if API is configured, false otherwise.
	 */
	public function is_configured() {
		return ! empty( $this->api_token ) && ! empty( $this->api_base_url );
	}

	/**
	 * Test API connection.
	 *
	 * @since 1.0.0
	 * @return bool|WP_Error True if connection successful, WP_Error on failure.
	 */
	public function test_connection() {
		if ( ! $this->is_configured() ) {
			return new \WP_Error( 'api_not_configured', __( 'API is not properly configured.', 'webchangedetector' ) );
		}

		$result = $this->get_account();
		
		if ( is_wp_error( $result ) ) {
			return $result;
		}

		return true;
	}
} 