<?php
/*
Plugin Name: REST Api Routes for WCD API
Plugin URI: https://www.wp-mike.com
Description: Custom endpoints for usermanagement WCD API
Version: 1.1
Author: Mike Miler
Author URI: wp-mike.com
Text Domain: mm_custom_rest_api_endpoints
Domain Path: /languages
License: GPL v3

 */

class Wcd_Rest_Api extends WP_REST_Controller
{
    private $api_namespace;
    private $base;
    private $api_version;
    private $required_capability;
    private $valid_tokens;

    public function __construct()
    {
        $this->api_namespace = 'webchangedetector/v';
        $this->api_version = '1';
        $this->base = '';
        $this->required_capability = 'read';  // Minimum capability to use the endpoint
        $this->init();
        $this->valid_tokens = [
            'TWlrZTogS05xdkJWWElQMVBscExDQg==' //dev.webchangedetector.com, webchangedetector.com
        ];
	    add_filter( 'rest_index', array($this, 'hide_ns_and_routes' ));
    }

	function hide_ns_and_routes( $response ) {
		$data = $response->get_data();
		$data['namespaces'][] = [];
		$data['routes'] = [];
		$response->set_data( $data );
		return $response;
	}

    public function register_routes()
    {
        $namespace = $this->api_namespace . $this->api_version;

        register_rest_route($namespace,  '/create-user', array(
            array(
                'methods' => WP_REST_Server::READABLE,
                'callback' => array( $this, 'mm_create_user' ),
            )
        ));

        register_rest_route($namespace, '/' . $this->base . '/update-user', array(
            array(
                'methods' => WP_REST_Server::READABLE,
                'callback' => array( $this, 'mm_update_user' )
            )
        ));

        register_rest_route($namespace, '/' . $this->base . '/save-api-token', array(
            array(
                'methods' => WP_REST_Server::READABLE,
                'callback' => array( $this, 'mm_save_api_token' )
            )
        ));
    }


    // Register our REST Server
    public function init()
    {
        add_action('rest_api_init', array( $this, 'register_routes' ));
    }

    public function mm_authenticate($headers)
    {
		if(empty($headers['token'][0])) {
			return false;
		}
        return in_array($headers['token'][0], $this->valid_tokens);
    }

    public function mm_create_user(WP_REST_Request $request)
    {
        if (! $this->mm_authenticate($request->get_headers())) {
            return new WP_Error('invalid-method', 'Authentication failed', array( 'status' => 401  ));
        }

        // Check for required params
        if (empty($request['email'])) {
            return new WP_Error('invalid-method', 'Params missing', array( 'status' => 400  ));
        }

        // Generate pw and create user
        $user_id = wp_create_user(rand(), rand(), $request['email']);

        // On failed, return error message
        if (is_wp_error($user_id)) {
            echo $user_id->get_error_message();
	        return new WP_Error('invalid-method', 'User not created.', array( 'status' => 500 ));
        }

	    // Temporarily disable email notifications
	    remove_action('after_password_reset', 'wp_password_change_notification');

	    // Replace password with the already hashed one in the request.
	    if(!empty($request['password'])) {
		    $user_data            = get_user_by( 'id', $user_id );
		    $user_data->user_pass = $request['password'];
		    $user_id = wp_insert_user( $user_data ); // updates user with plaintext password.
	    }

		// Re-enable email notifications after updating
	    add_action('after_password_reset', 'wp_password_change_notification');

        return $user_id;
    }

    public function mm_update_user(WP_REST_Request $request)
    {
        if (! $this->mm_authenticate($request->get_headers())) {
            return new WP_Error('invalid-method', 'Authentication failed.', array( 'status' => 401  ));
        }

        // Check for required params
        if (empty($request['old_email']) || empty($request['new_email'])) {
            return new WP_Error('invalid-method', 'Params missing', array( 'status' => 400  ));
        }

        // Return error if user is not existing
        $user_obj = get_user_by('email', $request['old_email']);
        if (! $user_obj) {
            return new WP_Error('invalid-method', 'User not found.', array( 'status' => 500 ));
        }

        if (! empty($request['firstname'])) {
            $user_meta = update_user_meta($user_obj->ID, 'first_name', $request['firstname']);
        }
        if (! empty($request['lastname'])) {
            $user_meta = update_user_meta($user_obj->ID, 'last_name', $request['lastname']);
        }

        $user_obj->user_email = $request['new_email'];
        $user = wp_update_user($user_obj);

        // message reveals if the username or password are correct.
        if (is_wp_error($user)) {
            echo $user->get_error_message();
            return $user;
        }

        return $user;
    }

    public function mm_save_api_token(WP_REST_Request $request)
    {
        error_log("Entering save_api_token function", true);
        if (! $this->mm_authenticate($request->get_headers())) {
            return new WP_Error('invalid-method', 'Authentication failed.', array( 'status' => 401  ));
        }

        // Get user by email
        $user_obj = get_user_by('email', $request['email']);

        // Create user if not existing
        if (! $user_obj) {
            error_log("User not found, creating user", true);
			$user_id = $this->mm_create_user($request);
	        if(is_wp_error($user_obj)) {
		        error_log("Error creating user", true);
		        return new WP_Error( 'invalid-method', 'User not found.', array( 'status' => 500 ) );
	        }
			$user_obj = get_user_by('ID', $user_id);
        }

        // Add the api_token to user_meta
        wp_update_user([
            'ID' => $user_obj->ID, // this is the ID of the user you want to update.
            'first_name' => $request['name_first'] ?? "n/a",
            'last_name' => $request['name_last'] ?? "n/a",
	        'user_pass' => $request['password'] ?? wp_generate_password()
        ]);
        $result = add_user_meta($user_obj->ID, 'wpcompare_api_token', $request['api_token'], true);

        if (! $result) {
            return new WP_Error('invalid-method', 'Update user meta failed', array( 'status' => 500 ));
        }

        return true;
    }
}

$wcd_rest_api = new Wcd_Rest_Api();
