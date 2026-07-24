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
			'templates'     => new WebChangeDetector_Template_View( $this->admin ),
		);
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
		// Preserve blog context in tab URLs for multisite network admin.
		$blog_param   = '';
		$is_all_sites = \WebChangeDetector\WebChangeDetector_Multisite::is_all_sites_mode();
		if ( $is_all_sites ) {
			$blog_param = '&wcd_blog_id=all';
		} elseif ( \WebChangeDetector\WebChangeDetector_Multisite::is_multisite_active() && is_network_admin() ) {
			$blog_id    = \WebChangeDetector\WebChangeDetector_Multisite::get_current_managed_blog_id();
			$blog_param = '&wcd_blog_id=' . intval( $blog_id );
		}
		?>
		<h2 class="nav-tab-wrapper">
			<?php if ( $this->admin->settings_handler->is_allowed( 'dashboard_view' ) ) : ?>
			<a href="?page=webchangedetector<?php echo esc_attr( $blog_param ); ?>"
				class="nav-tab <?php echo 'webchangedetector' === $active_tab ? 'nav-tab-active' : ''; ?>">
				<?php \WebChangeDetector\WebChangeDetector_Admin_Utils::get_device_icon( 'dashboard' ); ?> <?php echo esc_html__( 'Dashboard', 'webchangedetector' ); ?>
			</a>
			<?php endif; ?>
			
			<?php if ( $this->admin->settings_handler->is_allowed( 'manual_checks_view' ) ) : ?>
			<a href="?page=webchangedetector-update-settings<?php echo esc_attr( $blog_param ); ?>"
				class="nav-tab <?php echo 'webchangedetector-update-settings' === $active_tab ? 'nav-tab-active' : ''; ?>">
				<?php \WebChangeDetector\WebChangeDetector_Admin_Utils::get_device_icon( 'update-group' ); ?> <?php echo esc_html__( 'On-Demand Checks', 'webchangedetector' ); ?>
			</a>
			<?php endif; ?>

			<?php if ( $this->admin->settings_handler->is_allowed( 'monitoring_checks_view' ) ) : ?>
			<a href="?page=webchangedetector-auto-settings<?php echo esc_attr( $blog_param ); ?>"
				class="nav-tab <?php echo 'webchangedetector-auto-settings' === $active_tab ? 'nav-tab-active' : ''; ?>">
				<?php \WebChangeDetector\WebChangeDetector_Admin_Utils::get_device_icon( 'auto-group' ); ?> <?php echo esc_html__( 'Monitoring', 'webchangedetector' ); ?>
			</a>
			<?php endif; ?>

			<?php if ( $this->admin->settings_handler->is_allowed( 'change_detections_view' ) ) : ?>
			<a href="?page=webchangedetector-change-detections<?php echo esc_attr( $blog_param ); ?>"
				class="nav-tab <?php echo 'webchangedetector-change-detections' === $active_tab ? 'nav-tab-active' : ''; ?>">
				<?php \WebChangeDetector\WebChangeDetector_Admin_Utils::get_device_icon( 'change-detections' ); ?> <?php echo esc_html__( 'Checks', 'webchangedetector' ); ?>
			</a>
			<?php endif; ?>

			<?php if ( $this->admin->settings_handler->is_allowed( 'ai_rules_view' ) ) : ?>
			<a href="?page=webchangedetector-ai-rules<?php echo esc_attr( $blog_param ); ?>"
				class="nav-tab <?php echo 'webchangedetector-ai-rules' === $active_tab ? 'nav-tab-active' : ''; ?>">
				<?php \WebChangeDetector\WebChangeDetector_Admin_Utils::get_device_icon( 'ai-rules' ); ?> <?php echo esc_html__( 'AI Rules', 'webchangedetector' ); ?>
			</a>
			<?php endif; ?>

			<?php if ( $this->admin->settings_handler->is_allowed( 'logs_view' ) ) : ?>
			<a href="?page=webchangedetector-logs<?php echo esc_attr( $blog_param ); ?>"
				class="nav-tab <?php echo 'webchangedetector-logs' === $active_tab ? 'nav-tab-active' : ''; ?>">
				<?php \WebChangeDetector\WebChangeDetector_Admin_Utils::get_device_icon( 'logs' ); ?> <?php echo esc_html__( 'Logs', 'webchangedetector' ); ?>
			</a>
			<?php endif; ?>

			<?php if ( $this->admin->settings_handler->is_allowed( 'settings_view' ) && ! $is_all_sites ) : ?>
			<a href="?page=webchangedetector-settings<?php echo esc_attr( $blog_param ); ?>"
				class="nav-tab <?php echo 'webchangedetector-settings' === $active_tab ? 'nav-tab-active' : ''; ?>">
				<?php \WebChangeDetector\WebChangeDetector_Admin_Utils::get_device_icon( 'settings' ); ?> <?php echo esc_html__( 'Settings', 'webchangedetector' ); ?>
			</a>
			<?php endif; ?>

			<?php if ( \WebChangeDetector\WebChangeDetector_Multisite::is_multisite_active() && is_network_admin() ) : ?>
			<a href="?page=webchangedetector-sites"
				class="nav-tab <?php echo 'webchangedetector-sites' === $active_tab ? 'nav-tab-active' : ''; ?>">
				<?php \WebChangeDetector\WebChangeDetector_Admin_Utils::get_device_icon( 'sites' ); ?> <?php echo esc_html__( 'Sites', 'webchangedetector' ); ?>
			</a>
			<a href="?page=webchangedetector-allowances<?php echo esc_attr( $blog_param ); ?>"
				class="nav-tab <?php echo 'webchangedetector-allowances' === $active_tab ? 'nav-tab-active' : ''; ?>">
				<?php \WebChangeDetector\WebChangeDetector_Admin_Utils::get_device_icon( 'settings' ); ?> <?php echo esc_html__( 'Sub-Site Allowances', 'webchangedetector' ); ?>
			</a>
			<?php endif; ?>

			<?php if ( $this->admin->settings_handler->is_allowed( 'upgrade_account' ) && \WebChangeDetector\WebChangeDetector_Multisite::can_manage_account() ) : ?>
			<a href="<?php echo esc_url( $this->admin->account_handler->get_upgrade_url() ); ?>" target="_blank"
				class="nav-tab upgrade">
				<?php \WebChangeDetector\WebChangeDetector_Admin_Utils::get_device_icon( 'upgrade' ); ?> <?php echo esc_html__( 'Upgrade Account', 'webchangedetector' ); ?>
			</a>
			<?php endif; ?>
		</h2>
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
}
