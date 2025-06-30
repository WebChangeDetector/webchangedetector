<?php

/**
 * The file that defines the ajax plugin class
 *
 * A class definition that includes attributes and functions used at the
 * public-facing side of the site .
 *
 * @link       wp-mike.com
 * @since      1.0.0
 *
 * @package    Wp_Compare_Ajax
 * @subpackage Wp_Compare/includes
 */

class Wp_Compare_Ajax {


	/**
	 * Constructor
	 */
	public function __construct() {
	}

	/**
	 * Create Wp_Compare class
	 *
	 * @return Wp_Compare
	 */
	public function wcd() {
		static $wcd;
		mm_verify_nonce();

		if ( $wcd ) {
			return $wcd;
		}

		return new Wp_Compare();
	}

	public function ajax_load_group_urls() {

		$limit_urls  = $_POST['limit_urls'] ?? 10;
		$offset_urls = $_POST['offset_urls'] ?? 0;
		$search = $_POST['search'] ?? '';
		unset( $_POST['limit_urls'] );
		unset( $_POST['offset_urls'] );

		$filters = array(
			'page' => $_POST['page'] ?? 1,
			'sorted'   => 'selected',
			'per_page' => 20,
		);
		if(!empty($search)) {
			$filters['search'] = $search;
		}

		$groups_and_urls = $this->wcd()->get_group_and_urls_v2( $_POST['group_id'], $filters );

		$_POST['limit_urls']  = $limit_urls;
		$_POST['offset_urls'] = $offset_urls;

		echo $this->wcd()->get_url_settings( $groups_and_urls );
		wp_die();
	}

	public function ajax_update_status_group() {
		echo json_encode( mm_api( $_POST ) );
		wp_die();
	}

	public function ajax_update_detection_step() {
		if ( ! empty( $_POST['step'] ) ) {
			update_user_meta( get_current_user_id(), WCD_OPTION_UPDATE_STEP_KEY, sanitize_text_field( $_POST['step'] ) );
		}

		// Save settings before updating step
		if ( ! empty( $_POST['group_ids'] ) ) {
			update_user_meta( get_current_user_id(), WCD_OPTION_UPDATE_GROUP_IDS, sanitize_text_field( $_POST['group_ids'] ) ); }
		// if(!empty($_POST['sc_type'])) { update_user_meta( get_current_user_id(), WCD_OPTION_UPDATE_SC_TYPE, sanitize_text_field($_POST['sc_type']) ); }
		if ( ! empty( $_POST['cms'] ) ) {
			update_user_meta( get_current_user_id(), WCD_OPTION_UPDATE_CMS_FILTER, sanitize_text_field( $_POST['cms'] ) ); }

		// update_user_meta(get_current_user_id(), WCD_OPTION_UPDATE_STEP_KEY, $_POST['step']);
		wp_die();
	}

	public function ajax_get_wp_urls() {
		$domain = $this->wcd()->check_url( $_POST['domain'] );
		echo $this->wcd()->get_wp_urls( $domain );
		wp_die();
	}

	public function ajax_get_preview_wp_urls() {
		$url = $_POST['domain'];
		if ( ! parse_url( $url, PHP_URL_SCHEME ) ) {
			$url = 'http://' . $url;
		}
		$parse  = parse_url( $url );
		$domain = $this->wcd()->check_url( $parse['host'] );
		echo json_encode( $this->wcd()->get_wp_urls( $domain[0] ) );
		wp_die();
	}

	public function ajax_preview_screenshot() {
		echo $this->wcd()->preview_screenshot( $_POST );
		wp_die();
	}

	public function ajax_add_subaccount() {
		$args = [
			'name_first' =>  $_POST['name_first'] ?? 'n/a',
			'name_last' => $_POST['name_last'] ?? 'n/a',
			'email' => $_POST['email'],
			'limit_checks' => $_POST['limit_checks'] ?? 100
		];
		$result = Wp_Compare_API_V2::add_subaccount( $args );
		if ( ! empty( $result['data'] ) ) {
			$user_subaccounts = get_user_meta( get_current_user_id(), 'wcd_subaccount_api_tokens', true );

			if ( ! $user_subaccounts ) {
				$user_subaccounts = array();
			}
			$user_subaccounts[ $result['data']['id'] ] = $result['data']['api_token'];
			update_user_meta( get_current_user_id(), 'wcd_subaccount_api_tokens', $user_subaccounts );

			echo mm_message( array( 'success', 'Subaccount created.' ) );
		} else {
			echo mm_message( array( 'error', 'Creating subaccount failed.' ) );
		}
		wp_die();
	}

	public function ajax_update_subaccount() {
		$args = [
			'id'=> $_POST['id'],
			'name_first' =>  $_POST['name_first'] ?? 'n/a',
			'name_last' => $_POST['name_last'] ?? 'n/a',
			'email' => $_POST['email'],
			'limit_checks' => $_POST['limit_checks'] ?? 100
		];

		$result = Wp_Compare_API_V2::update_subaccount( $args );
		if ( ! empty( $result['data'] ) ) {
			echo mm_message( array( 'success', 'Subaccount updated.' ) );
		} else {
			echo mm_message( array( 'error', 'Updating subaccount failed.' ) );
		}
		wp_die();
	}

	public function ajax_get_change_detection_popup() {
		$compare = Wp_Compare_API_V2::get_comparisons_v2(['token' => $_POST['token']]) ['data'][0] ?? [];
		include wcd_get_plugin_dir() . '/public/partials/change-detection-content.php';
		wp_die();
	}

	public function ajax_get_batch_comparisons_view() {
		$filters = $_POST['filters'] ?? [];
		// Ensure filters is always an array
		if (!is_array($filters)) {
			$filters = [];
		}
		$filters['batches'] = $_POST['batch_id'] ?? 0;
		$filters['page'] = $_POST['page'] ?? 1;
		$filters['orderBy'] = 'difference_percent';
		$filters['orderDirection'] = 'desc';

		$filters = array_filter($filters);

		$comparisons = Wp_Compare_API_V2::get_comparisons_v2($filters);

		 $this->wcd()->load_comparisons_view($filters['batches'], $comparisons, $filters, (int)($_POST['failed_count'] ?? 0));
		 wp_die();
	}

	public function ajax_load_failed_queues() {
		$batch_id = $_POST['batch_id'] ?? 0;
		
		if (empty($batch_id)) {
			echo '<div style="padding: 20px; text-align: center; color: #666;">Invalid batch ID.</div>';
			wp_die();
		}

		$this->wcd()->load_failed_queues_view($batch_id);
		wp_die();
	}

	public function ajax_update_comparison_status() {
		echo $this->wcd()->update_comparison_status( $_POST );
		wp_die();
	}

	public function ajax_get_comparison_status_by_token() {
		echo $this->wcd()->get_comparison_status_by_token( $_POST );
		wp_die();
	}

	public function ajax_take_screenshots() {

		/*
		if($_POST['step'] == WCD_OPTION_UPDATE_STEP_PRE_STARTED) {
			update_user_meta( get_current_user_id(), WCD_OPTION_UPDATE_GROUP_IDS, $_POST['group_ids'] );
			update_user_meta( get_current_user_id(), WCD_OPTION_UPDATE_SC_TYPE, $_POST['sc_type'] );
			update_user_meta( get_current_user_id(), WCD_OPTION_UPDATE_CMS_FILTER, $_POST['cms'] );
		}*/
		$this->wcd()->take_screenshot( $_POST );
		update_user_meta( get_current_user_id(), WCD_OPTION_UPDATE_STEP_KEY, sanitize_text_field( $_POST['step'] ) );

		/*
		update_user_meta(get_current_user_id(),
			WCD_OPTION_UPDATE_STEP_KEY,
			$_POST['sc_type'] == 'pre' ? WCD_OPTION_UPDATE_STEP_PRE_STARTED : WCD_OPTION_UPDATE_STEP_POST_STARTED);
		//$this->ajax_update_detection_step();*/
		wp_die();
	}

	/**
	 * Action `update_urls`, transformation of pid in `save_group_urls()`
	 *
	 * @return void
	 */
	public function ajax_save_group_urls() {
		$result = $this->wcd()->save_group_urls( $_POST );
		echo json_encode( $result );
		wp_die();
	}

	public function ajax_get_unassigned_group_urls() {
		$this->wcd()->get_view_unassigned_group_urls( $_POST );
		wp_die();
	}

	public function ajax_assign_group_urls() {
		$result = $this->wcd()->assign_urls( $_POST );

		if ( $result ) {
			echo mm_message( array( 'success', 'Selected urls were added.' ) );
		} else {
			echo mm_message( array( 'error', 'Something went wrong. No URLs were added.' ) );
		}
		wp_die();
	}

	public function ajax_unassign_group_url() {
		$result = $this->wcd()->unassign_group_urls_v2( $_POST );

		if ( $result ) {
			echo mm_message( array( 'success', 'The URL was removed from this group.' ) );
		}
		wp_die();
	}

	public function ajax_add_api_token() {
		if(!empty($_POST['api_token'])) {
			$account_details = $this->wcd()->get_account_details_v2($_POST['api_token']);
			if(is_array($account_details)) {
				$result = update_user_meta( get_current_user_id(), 'wpcompare_api_token', sanitize_text_field( $_POST['api_token'] ) );
				echo mm_message( array( 'success', 'API token saved.' ) );
			} else {
				echo mm_message( array( 'error', 'Invalid API token.' ) );
			}
		}

		wp_die();
	}

	public function ajax_switch_account() {
		$user_id = $_POST['user_id'];
		$subaccounts = get_user_meta(get_current_user_id(), 'wcd_subaccount_api_tokens',1);
		if(array_key_exists($user_id, $subaccounts)) { // switch to subaccount
			$switch_to_api_token = $subaccounts[$user_id];
			update_user_meta(get_current_user_id(), 'wcd_active_api_token', $switch_to_api_token);
			update_user_meta(get_current_user_id(), 'wcd_active_user_id', $user_id);
			echo mm_message( array( 'success', 'Account switched' ));
		} elseif($user_id === $this->wcd()->get_account_details_v2(mm_api_token())['id']) { // switch to main account
			update_user_meta(get_current_user_id(), 'wcd_active_api_token', mm_api_token());
			update_user_meta(get_current_user_id(), 'wcd_active_user_id', $user_id);
			echo mm_message( array( 'success', 'Account switched' ));
		} else {
			echo mm_message( array( 'error', 'Sorry, we couldn\'t find your sub-account. Please contact support'  ));
		}
		wp_die();
	}

	public function ajax_send_feedback_mail() {
		$content = 'Client feedback from email ' . $_POST['email'] . "\r\nMessage: \r\n\r\n" . $_POST['message'];
		echo wp_mail( MM_EMAIL_FEEDBACK, 'Feedback from ' . mm_app_domain() . ' - APP', $content );
		try {
			$slackArgs = array(
				'headers' => array(
					'Content-type' => 'application/json',
				),
				'body'    => json_encode(
					array(
						'text' => ':envelope: New Feedback-Mail from' . mm_app_domain() . ' - APP: ' . $content,
					)
				),
			);

			@wp_remote_post( SLACK_WEBHOOK_URL_NOTIFICATION_DUMP, $slackArgs );
		} catch ( \Exception $e ) {
		}
		wp_die();
	}

	public function ajax_save_group_settings() {
		$result = $this->wcd()->save_group_settings( $_POST );

		if ( ! empty( $result['id'] ) ) { // group_id is returned after saving non-wp-group
			echo mm_message( array( 'success', 'The group "' . $result['name'] . '" was saved.' ) );
		} elseif ( ! empty( $result['urls_added'] ) ) {
			echo mm_message( array( 'success', 'Website and URLs synchronised.' ) );
		} else {
			echo mm_message( array( 'error', 'Something went wrong. The group was not saved.' ) );
		}
		wp_die();
	}

	public function ajax_get_wp_post_types() {
		$domain = $this->wcd()->check_url( $_POST['domain'] )[0];

		// Early return if domain is invalid
		if ( empty( $domain ) ) {
			echo json_encode( 'Please check your domain and try again.' );
			wp_die();
		}
		$domain = rtrim( $domain, '/' );

		// Check if the website was added already
		if ( $_POST['group_id'] === '0' ) {
			$args    = array(
				'action' => 'websites',
				'domain' => $domain,
			);

			
			$websites = Wp_Compare_API_V2::get_websites_v2($args);
			foreach($websites['data'] as $website) {
				if($website['domain'] === $domain) {
					echo json_encode( 'This website is already in your account.' );
					wp_die();
				}
			}
		}

		$result = $this->wcd()->get_wp_post_types( $domain );
		if ( $result ) {
			echo json_encode( $result );
		} else {
			echo json_encode( 'Please check your domain. Only WP websites with REST API activated can be added.' );

		}
		wp_die();
	}

	public function ajax_save_wp_group_settings() {
		$result = $this->wcd()->save_wp_group_settings( $_POST );
		// $this->log($result);
		if ( is_string( $result ) ) {
			switch ( $result ) {
				case 'website exists':
					echo mm_message( array( 'error', 'This website already exists' ) );
					break;

				case 'no posts':
					echo mm_message( array( 'error', 'We couldn\'t get pages. Please check if the WP Api is activated' ) );
					break;

				default:
					echo mm_message( array( 'error', 'Something went wrong. The group was not saved. | ' . $result ) );
			}
		} elseif ( isset( $result['urls_added'] ) ) {
			echo mm_message( array( 'success', 'Website and URLs synchronized.' ) );
		} else {
			echo mm_message( array( 'error', 'Something went wrong. The group was not saved.' ) );
		}
		wp_die();
	}

	/**
	 * AJAX handler for asynchronous WordPress group settings save.
	 * Creates the website group and queues background URL sync.
	 */
	public function ajax_save_wp_group_settings_async() {
		try {
			// Log the attempt
			error_log( 'WCD: ajax_save_wp_group_settings_async called' );
			
			$result = $this->wcd()->save_wp_group_settings_async( $_POST );
			
			error_log( 'WCD: save_wp_group_settings_async result: ' . print_r( $result, true ) );
			
			if ( is_string( $result ) ) {
				switch ( $result ) {
					case 'website exists':
						echo json_encode( array( 'success' => false, 'message' => 'This website already exists' ) );
						break;
					case 'no posts':
						echo json_encode( array( 'success' => false, 'message' => 'We couldn\'t get pages. Please check if the WP Api is activated' ) );
						break;
					default:
						echo json_encode( array( 'success' => false, 'message' => 'Something went wrong. The group was not saved. | ' . $result ) );
				}
			} elseif ( isset( $result['job_queued'] ) ) {
				echo json_encode( array(
					'success' => true,
					'message' => 'Website added! URL synchronization is running in the background.',
					'job_id' => $result['job_id'],
					'domain' => $result['domain']
				) );
			} else {
				echo json_encode( array( 'success' => false, 'message' => 'Something went wrong. The group was not saved.' ) );
			}
		} catch ( Exception $e ) {
			error_log( 'WCD: ajax_save_wp_group_settings_async error: ' . $e->getMessage() );
			echo json_encode( array( 'success' => false, 'message' => 'Server error: ' . $e->getMessage() ) );
		}
		wp_die();
	}

	/**
	 * AJAX handler to check sync job status.
	 */
	public function ajax_check_sync_job_status() {
		try {
			error_log( 'WCD: ajax_check_sync_job_status called' );
			
			$job_id = $_POST['job_id'] ?? '';
			
			if ( ! $job_id ) {
				error_log( 'WCD: No job_id provided' );
				wp_send_json_error( 'Job ID required' );
			}
			
			error_log( 'WCD: Checking status for job_id: ' . $job_id );
			
			$job_data = $this->wcd()->get_sync_job_status( $job_id );
			
			if ( ! $job_data ) {
				error_log( 'WCD: Job not found: ' . $job_id );
				wp_send_json_error( 'Job not found' );
			}
			
			error_log( 'WCD: Job status: ' . print_r( $job_data, true ) );
			wp_send_json_success( $job_data );
		} catch ( Exception $e ) {
			error_log( 'WCD: ajax_check_sync_job_status error: ' . $e->getMessage() );
			wp_send_json_error( 'Server error: ' . $e->getMessage() );
		}
	}

	public function ajax_save_group_css() {
		$group = $this->wcd()->save_group_css( $_POST );

		if ( ! empty( $group['id'] ) ) {
			echo mm_message( array( 'success', 'The CSS was saved.' ) );
		} else {
			echo mm_message( array( 'error', 'Something went wrong. The CSS was not saved.' ) );
		}
		wp_die();
	}

	public function ajax_wcd_content() {
		echo wcd_content( $_POST['ajaxTab'] ?? false, $_POST['ajaxSubTab'] ?? false );
		wp_die();
	}

	public function ajax_update_url() {
		$result = $this->wcd()->update_group_url_v2( $_POST );

		if ( $result ) {
			echo mm_message( array( 'success', 'URL settings updated.' ) );
		} else {
			echo mm_message( array( 'error', 'Something went wrong...' ) );
		}
		wp_die();
	}

	public function ajax_save_url() {
		$this->wcd()->save_url( $_POST );
		echo mm_message( array( 'success', 'Your URLs were saved.' ) );
		wp_die();
	}

	public function ajax_delete_url() {
		$result = $this->wcd()->delete_url( $_POST );

		if ( $result ) {
			echo mm_message( array( 'success', 'The URL was deleted.' ) );
		} else {
			echo mm_message( array( 'error', 'Something went wrong. The URL was not deleted.' ) );
		}
		wp_die();
	}

	public function ajax_delete_group() {
		$result = $this->wcd()->delete_group( $_POST['group_id'] );

		if ( $result ) {
			echo mm_message( array( 'success', 'The group was deleted.' ) );
		} else {
			echo mm_message( array( 'error', 'Something went wrong. The group was not deleted.' ) );
		}
		wp_die();
	}

	public function ajax_get_processing_queue() {
		echo $this->wcd()->get_processing_queue();
		wp_die();
	}

	public function ajax_get_batch_processing_status() {
		$batch_id = $_POST['batch_id'] ?? false;
		if (!$batch_id) {
			echo wp_json_encode(['error' => 'Missing batch_id']);
			wp_die();
		}

		// Get all queue items for this batch (all statuses)
		$allQueues = Wp_Compare_API_V2::get_queue_v2($batch_id);
		$queueItems = $allQueues['data'] ?? [];

		// Count items by status
		$openProcessingCount = 0;
		$processedCount = 0;

		foreach ($queueItems as $item) {
			$status = $item['status'] ?? '';
			if (in_array($status, ['open', 'processing'])) {
				$openProcessingCount++;
			} elseif (in_array($status, ['done', 'failed'])) {
				$processedCount++;
			}
		}

		$response = [
			'open_processing' => $openProcessingCount,
			'processed' => $processedCount,
			'total' => count($queueItems)
		];

		echo wp_json_encode($response);
		wp_die();
	}

	public function ajax_filter_change_detections() {
		$this->wcd()->get_compares_view( $_POST, false );
		wp_die();
	}

	public function ajax_save_user_website() {
		$result = $this->wcd()->save_user_website( $_POST );
		if ( $result ) {
			echo mm_message( array( 'success', 'Settings saved for ' . $_POST['domain'] . '.' ) );
		} else {
			echo mm_message( array( 'error', 'Something went wrong. The settings were not saved.' ) );
		}
		wp_die();
	}

	public function ajax_delete_website() {
		$result = $this->wcd()->delete_user_website( $_POST, true );

		if ( $result ) {
			echo mm_message( array( 'success', 'Website ' . $_POST['domain'] . ' deleted.' ) );
		} else {
			echo mm_message( array( 'error', 'Something went wrong. Website was not deleted.' ) );
		}
		wp_die();
	}

	public function ajax_select_group_url() {
		echo wp_json_encode( $this->wcd()->select_group_url_v2( $_POST ) );
		wp_die();
	}

	public function ajax_check_url() {
		$response = $this->wcd()->check_url( $_POST['url'] );
		if ( ! $response ) {
			echo 0;
		} else {
			echo json_encode( $response );
		}
		wp_die();
	}
}
if ( ! defined( 'HTTP_OK' ) ) {
	define( 'HTTP_OK', 200 );
}

if ( ! defined( 'HTTP_MOVED_PERMANENTLY' ) ) {
	define( 'HTTP_MOVED_PERMANENTLY', 301 );
}

if ( ! defined( 'HTTP_FOUND' ) ) {
	define( 'HTTP_FOUND', 302 );
}

/*
public function ajax_get_sc_groups_and_urls() {
	if(strpos($_POST['group_ids'], ",") === false) {
		$group_ids[] = $_POST['group_ids'];
	} else {
		$group_ids = explode(",", $_POST['group_ids']);
	}
	$output = '';
	foreach($group_ids as $group_id) {
		$group = $this->wcd()->get_user_groups_and_urls($_POST['cms'],'all', $group_id)[0];
		//$output .= $group_id . json_encode($group);
		$output.= $group['name'] . '<ol>';

		foreach($group['urls'] as $url) {
			$output .= '<li>';
			if($url['html_title']) {
				$output .=  $url['html_title'] . '<br>';
			}
			$output .= $url['url'] . '</li>';
		}
		$output .= '</ol>';
	}
	echo $output;
	wp_die();
}*/
