<?php
/**
 * Main View Renderer for WebChangeDetector
 *
 * Handles rendering of admin views and components.
 *
 * @package    WebChangeDetector
 * @subpackage WebChangeDetector/admin/views
 * @author     Mike Miler <mike@wp-mike.com>
 */

namespace WebChangeDetector;

/**
 * Main View Renderer Class.
 */
class WebChangeDetector_View_Renderer {

	/**
	 * The admin instance.
	 *
	 * @var WebChangeDetector_Admin
	 */
	private $admin;

	/**
	 * Specialized view components.
	 *
	 * @var array
	 */
	private $view_components = array();

	/**
	 * Constructor.
	 *
	 * @param WebChangeDetector_Admin $admin The admin instance.
	 */
	public function __construct( $admin ) {
		$this->admin = $admin;
		$this->init_view_components();
	}

	/**
	 * Initialize view components.
	 */
	private function init_view_components() {
		$this->view_components = array(
			'notifications' => new WebChangeDetector_Notification_View( $this->admin ),
			'forms'         => new WebChangeDetector_Form_View( $this->admin ),
			'cards'         => new WebChangeDetector_Card_View( $this->admin ),
			'modals'        => new WebChangeDetector_Modal_View( $this->admin ),
			'templates'     => new WebChangeDetector_Template_View( $this->admin ),
		);
	}

	/**
	 * Render page wrapper with header and footer.
	 *
	 * @param string   $title The page title.
	 * @param callable $content_callback The callback to render page content.
	 * @param array    $data Optional data to pass to the content callback.
	 */
	public function render_page( $title, $content_callback, $data = array() ) {
		?>
		<div class="wrap">
			<div class="webchangedetector">
				<h1><?php echo esc_html( $title ); ?></h1>
				
				<?php
				// Render any flash messages.
				$this->render_flash_messages();

				// Render the main content.
				if ( is_callable( $content_callback ) ) {
					call_user_func( $content_callback, $data );
				}
				?>
			</div>
		</div>
		<?php
	}

	/**
	 * Render flash messages from session or options.
	 */
	public function render_flash_messages() {
		$messages = get_option( 'wcd_flash_messages', array() );

		if ( ! empty( $messages ) ) {
			foreach ( $messages as $message ) {
				$this->view_components['notifications']->render_notice(
					$message['message'],
					$message['type'] ?? 'info'
				);
			}

			// Clear messages after displaying.
			delete_option( 'wcd_flash_messages' );
		}
	}

	/**
	 * Render error page.
	 *
	 * @param string $title   The error title.
	 * @param string $message The error message.
	 * @param array  $actions Optional array of action buttons.
	 */
	public function render_error_page( $title, $message, $actions = array() ) {
		?>
		<div class="wrap">
			<div class="webchangedetector">
				<h1>WebChange Detector</h1>
				
				<div class="error notice">
					<h3><?php echo esc_html( $title ); ?></h3>
					<p><?php echo esc_html( $message ); ?></p>
					
					<?php if ( ! empty( $actions ) ) : ?>
						<p>
							<?php foreach ( $actions as $action ) : ?>
								<form method="post" style="display: inline-block; margin-right: 10px;">
									<input type="hidden" name="wcd_action" value="<?php echo esc_attr( $action['action'] ); ?>">
									<?php wp_nonce_field( $action['action'] ); ?>
									<?php if ( ! empty( $action['hidden_fields'] ) ) : ?>
										<?php foreach ( $action['hidden_fields'] as $field => $value ) : ?>
											<input type="hidden" name="<?php echo esc_attr( $field ); ?>" value="<?php echo esc_attr( $value ); ?>">
										<?php endforeach; ?>
									<?php endif; ?>
									<input type="submit" value="<?php echo esc_attr( $action['label'] ); ?>" class="button <?php echo esc_attr( $action['class'] ?? '' ); ?>">
								</form>
							<?php endforeach; ?>
						</p>
					<?php endif; ?>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Render loading overlay.
	 *
	 * @param string $message The loading message.
	 */
	public function render_loading_overlay( $message = 'Loading...' ) {
		?>
		<div id="wcd-loading-overlay" style="display: none;">
			<div class="wcd-loading-content">
				<img src="<?php echo esc_url( plugin_dir_url( __DIR__ ) . '../admin/img/logo-webchangedetector.png' ); ?>" alt="WebChangeDetector Logo" class="wcd-loading-logo">
				<p class="wcd-loading-text"><?php echo esc_html( $message ); ?></p>
				<img src="<?php echo esc_url( plugin_dir_url( __DIR__ ) . '../admin/img/loading-bar.gif' ); ?>" alt="Loading..." class="wcd-loading-gif">
			</div>
		</div>
		<?php
	}

	/**
	 * Render the navigation tabs.
	 *
	 * @param string $active_tab The currently active tab.
	 */
	public function render_navigation_tabs( $active_tab ) {
		?>
		<div class="wrap">
			<h2 class="nav-tab-wrapper">
				<?php if ( $this->admin->settings_handler->is_allowed( 'dashboard_view' ) ) : ?>
				<a href="?page=webchangedetector"
					class="nav-tab <?php echo 'webchangedetector' === $active_tab ? 'nav-tab-active' : ''; ?>">
					<?php \WebChangeDetector\WebChangeDetector_Admin_Utils::get_device_icon( 'dashboard' ); ?> <?php echo esc_html__( 'Dashboard', 'webchangedetector' ); ?>
				</a>
				<?php endif; ?>
				
				<?php if ( $this->admin->settings_handler->is_allowed( 'manual_checks_view' ) ) : ?>
				<a href="?page=webchangedetector-update-settings"
					class="nav-tab <?php echo 'webchangedetector-update-settings' === $active_tab ? 'nav-tab-active' : ''; ?>">
					<?php \WebChangeDetector\WebChangeDetector_Admin_Utils::get_device_icon( 'update-group' ); ?> <?php echo esc_html__( 'Auto Update Checks & Manual Checks', 'webchangedetector' ); ?>
				</a>
				<?php endif; ?>
				
				<?php if ( $this->admin->settings_handler->is_allowed( 'monitoring_checks_view' ) ) : ?>
				<a href="?page=webchangedetector-auto-settings"
					class="nav-tab <?php echo 'webchangedetector-auto-settings' === $active_tab ? 'nav-tab-active' : ''; ?>">
					<?php \WebChangeDetector\WebChangeDetector_Admin_Utils::get_device_icon( 'auto-group' ); ?> <?php echo esc_html__( 'Monitoring', 'webchangedetector' ); ?>
				</a>
				<?php endif; ?>
				
				<?php if ( $this->admin->settings_handler->is_allowed( 'change_detections_view' ) ) : ?>
				<a href="?page=webchangedetector-change-detections"
					class="nav-tab <?php echo 'webchangedetector-change-detections' === $active_tab ? 'nav-tab-active' : ''; ?>">
					<?php \WebChangeDetector\WebChangeDetector_Admin_Utils::get_device_icon( 'change-detections' ); ?> <?php echo esc_html__( 'Change Detections', 'webchangedetector' ); ?>
				</a>
				<?php endif; ?>
				
				<?php if ( $this->admin->settings_handler->is_allowed( 'logs_view' ) ) : ?>
				<a href="?page=webchangedetector-logs"
					class="nav-tab <?php echo 'webchangedetector-logs' === $active_tab ? 'nav-tab-active' : ''; ?>">
					<?php \WebChangeDetector\WebChangeDetector_Admin_Utils::get_device_icon( 'logs' ); ?> <?php echo esc_html__( 'Logs', 'webchangedetector' ); ?>
				</a>
				<?php endif; ?>
				
				<?php if ( $this->admin->settings_handler->is_allowed( 'settings_view' ) ) : ?>
				<a href="?page=webchangedetector-settings"
					class="nav-tab <?php echo 'webchangedetector-settings' === $active_tab ? 'nav-tab-active' : ''; ?>">
					<?php \WebChangeDetector\WebChangeDetector_Admin_Utils::get_device_icon( 'settings' ); ?> <?php echo esc_html__( 'Settings', 'webchangedetector' ); ?>
				</a>
				<?php endif; ?>
				
				<?php if ( $this->admin->settings_handler->is_allowed( 'upgrade_account' ) ) : ?>
				<a href="<?php echo esc_url( $this->admin->account_handler->get_upgrade_url() ); ?>" target="_blank"
					class="nav-tab upgrade">
					<?php \WebChangeDetector\WebChangeDetector_Admin_Utils::get_device_icon( 'upgrade' ); ?> <?php echo esc_html__( 'Upgrade Account', 'webchangedetector' ); ?>
				</a>
				<?php endif; ?>
			</h2>
		</div>
		<?php
	}

	/**
	 * Get a view component.
	 *
	 * @param string $component The component name.
	 * @return mixed The view component instance or null.
	 */
	public function get_component( $component ) {
		return $this->view_components[ $component ] ?? null;
	}

	/**
	 * Render action container wrapper.
	 *
	 * @param callable $content_callback The callback to render container content.
	 * @param array    $data Optional data to pass to the content callback.
	 */
	public function render_action_container( $content_callback, $data = array() ) {
		?>
		<div class="action-container">
			<?php
			if ( is_callable( $content_callback ) ) {
				call_user_func( $content_callback, $data );
			}
			?>
		</div>
		<div class="clear"></div>
		<?php
	}

	/**
	 * Render inline JavaScript.
	 *
	 * @param string $script The JavaScript code.
	 */
	public function render_inline_script( $script ) {
		?>
		<script type="text/javascript">
			<?php echo $script; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		</script>
		<?php
	}
}
