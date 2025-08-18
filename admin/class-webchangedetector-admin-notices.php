<?php
/**
 * WebChangeDetector Admin Notices - Simplified
 *
 * Simplified admin notices using WordPress patterns.
 *
 * @package    WebChangeDetector
 * @subpackage WebChangeDetector/admin
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WebChangeDetector Admin Notices Class
 *
 * Provides simplified admin notices using WordPress patterns.
 */
class WebChangeDetector_Admin_Notices {

	/**
	 * Notice types.
	 */
	const TYPE_SUCCESS = 'success';
	const TYPE_INFO    = 'info';
	const TYPE_WARNING = 'warning';
	const TYPE_ERROR   = 'error';

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'admin_notices', array( $this, 'display_admin_notices' ) );
	}

	/**
	 * Add admin notice.
	 *
	 * @param string $message     Notice message.
	 * @param string $type        Notice type (success, info, warning, error).
	 * @param array  $options     Additional options.
	 */
	public function add_notice( $message, $type = self::TYPE_INFO, $options = array() ) {
		$options = wp_parse_args(
			$options,
			array(
				'dismissible' => true,
				'context'     => '',
			)
		);

		$notice = array(
			'message'     => $message,
			'type'        => $type,
			'dismissible' => $options['dismissible'],
			'context'     => $options['context'],
			'timestamp'   => time(),
		);

		// Store in transient for current user.
		$transient_key = 'wcd_admin_notices_' . get_current_user_id();
		$notices       = get_transient( $transient_key );
		if ( ! is_array( $notices ) ) {
			$notices = array();
		}

		$notices[] = $notice;
		set_transient( $transient_key, $notices, 300 ); // 5 minutes.
	}

	/**
	 * Add success notice.
	 *
	 * @param string $message Notice message.
	 * @param array  $options Additional options.
	 */
	public function add_success( $message, $options = array() ) {
		$this->add_notice( $message, self::TYPE_SUCCESS, $options );
	}

	/**
	 * Add info notice.
	 *
	 * @param string $message Notice message.
	 * @param array  $options Additional options.
	 */
	public function add_info( $message, $options = array() ) {
		$this->add_notice( $message, self::TYPE_INFO, $options );
	}

	/**
	 * Add warning notice.
	 *
	 * @param string $message Notice message.
	 * @param array  $options Additional options.
	 */
	public function add_warning( $message, $options = array() ) {
		$this->add_notice( $message, self::TYPE_WARNING, $options );
	}

	/**
	 * Add error notice.
	 *
	 * @param string $message Notice message.
	 * @param array  $options Additional options.
	 */
	public function add_error( $message, $options = array() ) {
		$this->add_notice( $message, self::TYPE_ERROR, $options );
	}

	/**
	 * Add error notice from WP_Error.
	 *
	 * @param WP_Error $wp_error WP_Error object.
	 * @param array    $options  Additional options.
	 */
	public function add_wp_error( $wp_error, $options = array() ) {
		if ( ! is_wp_error( $wp_error ) ) {
			return;
		}

		$message = $wp_error->get_error_message();
		$this->add_error( $message, $options );
	}

	/**
	 * Add error notice from Exception.
	 *
	 * @param Exception $exception Exception object.
	 * @param array     $options   Additional options.
	 */
	public function add_exception( $exception, $options = array() ) {
		if ( ! $exception instanceof Exception ) {
			return;
		}

		$message = 'An error occurred: ' . $exception->getMessage();
		$this->add_error( $message, $options );
	}

	/**
	 * Display admin notices.
	 */
	public function display_admin_notices() {
		// Only show on WebChangeDetector pages.
		if ( ! $this->is_wcd_page() ) {
			return;
		}

		$transient_key = 'wcd_admin_notices_' . get_current_user_id();
		$notices       = get_transient( $transient_key );

		if ( ! is_array( $notices ) || empty( $notices ) ) {
			return;
		}

		foreach ( $notices as $notice ) {
			$this->render_notice( $notice );
		}

		// Clear notices after displaying.
		delete_transient( $transient_key );
	}

	/**
	 * Render a single notice.
	 *
	 * @param array $notice Notice data.
	 */
	private function render_notice( $notice ) {
		$css_class = 'notice notice-' . esc_attr( $notice['type'] );
		if ( $notice['dismissible'] ) {
			$css_class .= ' is-dismissible';
		}

		printf(
			'<div class="%s"><p>%s</p></div>',
			$css_class,
			wp_kses_post( $notice['message'] )
		);
	}

	/**
	 * Check if current page is a WebChangeDetector admin page.
	 *
	 * @return bool True if on WebChangeDetector page.
	 */
	private function is_wcd_page() {
		$current_screen = get_current_screen();

		if ( ! $current_screen ) {
			return false;
		}

		$wcd_pages = array(
			'toplevel_page_webchangedetector',
			'webchangedetector_page_webchangedetector-update-settings',
			'webchangedetector_page_webchangedetector-auto-settings',
			'webchangedetector_page_webchangedetector-change-detections',
			'webchangedetector_page_webchangedetector-logs',
			'webchangedetector_page_webchangedetector-settings',
		);

		return in_array( $current_screen->id, $wcd_pages, true );
	}

	/**
	 * Clear all notices for current user.
	 */
	public function clear_notices() {
		$transient_key = 'wcd_admin_notices_' . get_current_user_id();
		delete_transient( $transient_key );
	}
}
