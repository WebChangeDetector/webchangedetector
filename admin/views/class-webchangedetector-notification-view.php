<?php
/**
 * Notification View Component for WebChangeDetector
 *
 * Handles rendering of notifications, notices, and alerts.
 *
 * @package    WebChangeDetector
 * @subpackage WebChangeDetector/admin/views
 * @author     Mike Miler <mike@wp-mike.com>
 */

namespace WebChangeDetector;

/**
 * Notification View Component Class.
 */
class WebChangeDetector_Notification_View {

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
	 * Render a notice.
	 *
	 * @param string $message The notice message.
	 * @param string $type    The notice type (success, error, warning, info).
	 * @param bool   $dismissible Whether the notice is dismissible.
	 */
	public function render_notice( $message, $type = 'info', $dismissible = true ) {
		$css_class = 'notice';

		switch ( $type ) {
			case 'success':
				$css_class .= ' notice-success';
				break;
			case 'error':
				$css_class .= ' notice-error';
				break;
			case 'warning':
				$css_class .= ' notice-warning';
				break;
			default:
				$css_class .= ' notice-info';
				break;
		}

		if ( $dismissible ) {
			$css_class .= ' is-dismissible';
		}

		?>
		<div class="<?php echo esc_attr( $css_class ); ?>">
			<p><?php echo wp_kses_post( $message ); ?></p>
		</div>
		<?php
	}

	/**
	 * Render credits warning.
	 *
	 * @param array $account_details The account details.
	 */
	public function render_credits_warning( $account_details ) {
		$usage_percent = 0;
		if ( $account_details['checks_limit'] > 0 ) {
			$usage_percent = (int) ( $account_details['checks_done'] / $account_details['checks_limit'] * 100 );
		}

		if ( $usage_percent >= 100 ) {
			$this->render_notice(
				'<strong>WebChange Detector:</strong> You ran out of checks. Please upgrade your account to continue.',
				'error'
			);
		} elseif ( $usage_percent > 70 ) {
			$this->render_notice(
				'<strong>WebChange Detector:</strong> You used ' . $usage_percent . '% of your checks.',
				'warning'
			);
		}
	}

	/**
	 * Render plugin update required notice.
	 */
	public function render_plugin_update_notice() {
		$this->render_notice(
			'There are major updates in our system which requires to update the plugin WebChangeDetector. Please install the update at <a href="/wp-admin/plugins.php">Plugins</a>.',
			'error',
			false
		);
	}

	/**
	 * Render API error notice.
	 */
	public function render_api_error_notice() {
		$message  = 'Something went wrong. Maybe the API token is invalid?';
		$message .= '<form method="post" style="margin-top: 10px;">';
		$message .= '<input type="hidden" name="wcd_action" value="reset_api_token">';
		$message .= wp_nonce_field( 'reset_api_token', '_wpnonce', true, false );
		$message .= '<input type="submit" value="Reset API token" class="button button-delete">';
		$message .= '</form>';

		$this->render_notice( $message, 'error', false );
	}

	/**
	 * Render inactive account notice.
	 *
	 * @param string $status The account status.
	 */
	public function render_inactive_account_notice( $status ) {
		$message  = '<h3>Your account status is ' . esc_html( $status ) . '</h3>';
		$message .= '<p>Please <a href="' . esc_url( $this->admin->account_handler->get_upgrade_url() ) . '">Upgrade</a> to re-activate your account.</p>';
		$message .= '<p>To use a different account, please reset the API token.</p>';
		$message .= '<form method="post">';
		$message .= '<input type="hidden" name="wcd_action" value="reset_api_token">';
		$message .= wp_nonce_field( 'reset_api_token', '_wpnonce', true, false );
		$message .= '<input type="submit" value="Reset API token" class="button button-delete">';
		$message .= '</form>';

		$this->render_notice( $message, 'error', false );
	}

	/**
	 * Render website details error notice.
	 */
	public function render_website_details_error() {
		$api_token = get_option( WCD_WP_OPTION_KEY_API_TOKEN );

		$message  = 'We couldn\'t find your website settings. Please reset the API token in settings and re-add your website with your API Token.';
		$message .= '<p>Your current API token is: <strong>' . esc_html( $api_token ) . '</strong>.</p>';
		$message .= '<form method="post">';
		$message .= '<input type="hidden" name="wcd_action" value="reset_api_token">';
		$message .= wp_nonce_field( 'reset_api_token', '_wpnonce', true, false );
		$message .= '<input type="hidden" name="api_token" value="' . esc_attr( $api_token ) . '">';
		$message .= '<input type="submit" value="Reset API token" class="button button-delete">';
		$message .= '</form>';

		$this->render_notice( $message, 'error', false );
	}

	/**
	 * Render success message for settings saved.
	 */
	public function render_settings_saved() {
		$this->render_notice( 'Settings saved.', 'success' );
	}

	/**
	 * Render action success message.
	 *
	 * @param string $action The action that was completed.
	 * @param string $item   The item that was affected.
	 */
	public function render_action_success( $action, $item = '' ) {
		$message = '<strong>WebChange Detector:</strong> ' . ucfirst( $action );
		if ( $item ) {
			$message .= ' ' . esc_html( $item );
		}
		$message .= '.';

		$this->render_notice( $message, 'success' );
	}
}
