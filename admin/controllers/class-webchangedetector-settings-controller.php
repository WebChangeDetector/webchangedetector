<?php

/**
 * Settings Controller for WebChangeDetector
 *
 * Handles settings page requests and logic.
 *
 * @package    WebChangeDetector
 * @subpackage WebChangeDetector/admin/controllers
 * @author     Mike Miler <mike@wp-mike.com>
 */

namespace WebChangeDetector;

/**
 * Settings Controller Class.
 */
class WebChangeDetector_Settings_Controller {


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
	 * Handle settings request.
	 */
	public function handle_request() {
		// Check permissions.
		if ( ! $this->admin->settings_handler->is_allowed( 'settings_view' ) ) {
			return;
		}

		// Check if viewing a log file.
		if ( isset( $_GET['view_log'] ) && isset( $_GET['_wpnonce'] ) ) {
			if ( wp_verify_nonce( $_GET['_wpnonce'], 'view_log_file' ) ) {
				$this->render_log_viewer();
				return;
			}
		}

		$this->render_settings_page();
	}

	/**
	 * Render settings page.
	 */
	private function render_settings_page() {
		?>
		<div class="action-container">

			<div class="wcd-settings-section">
				<div class="wcd-settings-card">
					<?php
					// Wizard functionality temporarily removed for phase 1.
					// Will be moved to view renderer in later phases.
					?>
					<h2><?php _e( 'URL Synchronization Settings', 'webchangedetector' ); ?></h2>

					<div class="wcd-form-row">
						<div class="wcd-form-label-wrapper">
							<label class="wcd-form-label"><?php _e( 'Show Post Types', 'webchangedetector' ); ?></label>
							<div class="wcd-description"><?php _e( 'Missing URLs to switch on for checking? Show additional post types in the URL list here.', 'webchangedetector' ); ?></div>
						</div>
						<div class="wcd-form-control">
							<?php $this->render_post_types_form(); ?>
						</div>
					</div>

					<div class="wcd-form-row">
						<div class="wcd-form-label-wrapper">
							<label class="wcd-form-label"><?php _e( 'Show Taxonomies', 'webchangedetector' ); ?></label>
							<div class="wcd-description"><?php _e( 'Missing taxonomies like categories or tags? Select them here and they appear in the URL list.', 'webchangedetector' ); ?></div>
						</div>
						<div class="wcd-form-control">
							<?php $this->render_taxonomies_form(); ?>
						</div>
					</div>

					<div class="wcd-form-row">
						<div class="wcd-form-label-wrapper">
							<label class="wcd-form-label"><?php _e( 'URL Sync Status', 'webchangedetector' ); ?></label>
							<div class="wcd-description">
							<?php
							_e(
								'To take screenshots and compare them, we synchronize the website urls with WebChange Detector.
                                This works automatically in the background. When you add a webpage, you can start the sync manually.',
								'webchangedetector'
							);
							?>
															</div>
						</div>
						<div class="wcd-form-control">
							<p><?php _e( 'Last Sync:', 'webchangedetector' ); ?> <span id="ajax_sync_urls_status" data-nonce="<?php echo esc_html( \WebChangeDetector\WebChangeDetector_Admin_Utils::create_nonce( 'ajax-nonce' ) ); ?>">
									<?php echo esc_html( date_i18n( 'd/m/Y H:i', get_option( 'wcd_last_urls_sync' ) ) ); ?>
								</span>
							</p>
							<button class="button button-secondary" onclick="sync_urls(1); return false;"><?php _e( 'Sync URLs Now', 'webchangedetector' ); ?></button>
						</div>
					</div>
				</div>
			</div>

			<hr />

			<div class="wcd-settings-section">
				<div class="wcd-settings-card">
					<h2><?php _e( 'Admin Bar Menu', 'webchangedetector' ); ?></h2>
					<form method="post">
						<?php wp_nonce_field( 'save_admin_bar_setting' ); ?>
						<input type="hidden" name="wcd_action" value="save_admin_bar_setting">

						<div class="wcd-form-row">
							<div class="wcd-form-label-wrapper">
								<label class="wcd-form-label"><?php _e( 'Disable Admin Bar Menu', 'webchangedetector' ); ?></label>
								<div class="wcd-description"><?php _e( 'Check this box to hide the WCD menu item in the frontend admin bar.', 'webchangedetector' ); ?></div>
							</div>
							<div class="wcd-form-control">
								<label>
									<input type="checkbox" name="wcd_disable_admin_bar_menu" value="1" <?php checked( get_option( 'wcd_disable_admin_bar_menu', 0 ), 1 ); ?> />
									<?php _e( 'Disable WCD Menu in Admin Bar', 'webchangedetector' ); ?>
								</label>
							</div>
						</div>

						<?php submit_button( __( 'Save Admin Bar Setting', 'webchangedetector' ) ); ?>
					</form>
				</div>
			</div>

			<hr />

			<div class="wcd-settings-section">
				<div class="wcd-settings-card">
					<h2><?php _e( 'Debug Logging', 'webchangedetector' ); ?></h2>
					<form method="post">
						<?php wp_nonce_field( 'save_debug_logging_setting' ); ?>
						<input type="hidden" name="wcd_action" value="save_debug_logging_setting">

						<div class="wcd-form-row">
							<div class="wcd-form-label-wrapper">
								<label class="wcd-form-label"><?php _e( 'Enable Debug Logging', 'webchangedetector' ); ?></label>
								<div class="wcd-description"><?php _e( 'Enable detailed debug logging to help troubleshoot issues. Logs are stored in the plugin directory and older logs are automatically cleaned up after 14 days.', 'webchangedetector' ); ?></div>
							</div>
							<div class="wcd-form-control">
								<label>
									<input type="checkbox" name="wcd_debug_logging" value="1" <?php checked( get_option( WCD_WP_OPTION_KEY_DEBUG_LOGGING, 0 ), 1 ); ?> />
									<?php _e( 'Enable Debug Logging', 'webchangedetector' ); ?>
								</label>
							</div>
						</div>

						<?php
						// Show available log files if debug logging is enabled or if log files exist.
						$logger    = isset( $this->admin->logger ) ? $this->admin->logger : null;
						$log_files = array();

						if ( $logger && method_exists( $logger, 'get_available_log_files' ) ) {
							$log_files = $logger->get_available_log_files();
						}

						if ( ! empty( $log_files ) ) :
							?>

						<?php endif; ?>

						<?php submit_button( __( 'Save Debug Logging Setting', 'webchangedetector' ) ); ?>
					</form>
					<div class="wcd-form-row">
						<div class="wcd-form-label-wrapper">
							<label class="wcd-form-label"><?php _e( 'Available Log Files', 'webchangedetector' ); ?></label>
							<div class="wcd-description"><?php _e( 'Download debug log files. Files are automatically cleaned up after 14 days.', 'webchangedetector' ); ?></div>
						</div>
						<div class="wcd-form-control">
							<div class="wcd-log-files-list">
								<?php foreach ( $log_files as $log_file ) : ?>
									<div class="wcd-log-file-item" style="margin-bottom: 10px;">
										<span class="wcd-log-file-info">
											<strong><?php echo esc_html( $log_file['date'] ); ?></strong>
											(<?php echo esc_html( $log_file['size_formatted'] ); ?>)
										</span>
										<a href="?page=webchangedetector-settings&view_log=<?php echo esc_attr( $log_file['filename'] ); ?>&_wpnonce=<?php echo wp_create_nonce( 'view_log_file' ); ?>" class="button button-small" style="margin-left: 10px;">
											<?php _e( 'View', 'webchangedetector' ); ?>
										</a>
										<form method="post" style="display: inline-block; margin-left: 5px;">
											<?php wp_nonce_field( 'download_log_file' ); ?>
											<input type="hidden" name="wcd_action" value="download_log_file">
											<input type="hidden" name="filename" value="<?php echo esc_attr( $log_file['filename'] ); ?>">
											<button type="submit" class="button button-small">
												<?php _e( 'Download', 'webchangedetector' ); ?>
											</button>
										</form>
									</div>
								<?php endforeach; ?>
							</div>

						</div>
					</div>
				</div>
			</div>

			<hr>
			<?php
			if ( ! get_option( WCD_WP_OPTION_KEY_API_TOKEN ) ) {
				echo '<div class="error notice">
                        <p>Please enter a valid API Token.</p>
                    </div>';
			} elseif ( $this->admin->settings_handler->is_allowed( 'upgrade_account' ) ) {
				?>
				<div class="wcd-settings-section">
					<div class="wcd-settings-card">
						<h2><?php _e( 'Need more checks?', 'webchangedetector' ); ?></h2>
						<p><?php _e( 'If you need more checks, please upgrade your account with the button below.', 'webchangedetector' ); ?></p>
						<a class="button" href="<?php echo esc_url( $this->admin->account_handler->get_upgrade_url() ); ?>"><?php _e( 'Upgrade', 'webchangedetector' ); ?></a>
					</div>
				</div>
				<?php
			}
			echo '<hr>';
			$this->admin->account_handler->get_api_token_form( get_option( WCD_WP_OPTION_KEY_API_TOKEN ) );
			$wizard_text = '<h2>Your account details</h2><p>You can see your WebChange Detector account here.
                                            Please don\'t share your API token with anyone. </p><p>
                                            Resetting your API Token will allow you to switch accounts. Keep in mind to
                                            save your API Token before the reset! </p><p>
                                            When you login with your API token after the reset, all your settings will be still there.</p>';
			// Wizard functionality temporarily removed for phase 1.
			?>

		</div>
		<div class="clear"></div>
		<?php
	}

	/**
	 * Render post types form.
	 */
	private function render_post_types_form() {
		// Add post types form.
		$post_types           = get_post_types( array( 'public' => true ), 'objects' );
		$available_post_types = array();

		foreach ( $post_types as $post_type ) {
			$wp_post_type_slug = \WebChangeDetector\WebChangeDetector_Admin_Utils::get_post_type_slug( $post_type );
			$show_type         = false;

			if ( ! empty( $this->admin->website_details['sync_url_types'] ) ) {
				foreach ( $this->admin->website_details['sync_url_types'] as $sync_url_type ) {
					if ( $wp_post_type_slug && $sync_url_type['post_type_slug'] === $wp_post_type_slug ) {
						$show_type = true;
						break;
					}
				}
			}

			if ( $wp_post_type_slug && ! $show_type ) {
				$available_post_types[] = $post_type;
			}
		}

		if ( ! empty( $available_post_types ) ) {
			?>
			<form method="post" style="display: inline-block; margin-right: 10px;">
				<input type="hidden" name="wcd_action" value="add_post_type">
				<?php wp_nonce_field( 'add_post_type' ); ?>
				<select name="post_type">
					<?php
					foreach ( $available_post_types as $available_post_type ) {
						$current_post_type_slug = \WebChangeDetector\WebChangeDetector_Admin_Utils::get_post_type_slug( $available_post_type );
						$current_post_type_name = \WebChangeDetector\WebChangeDetector_Admin_Utils::get_post_type_name( $current_post_type_slug );
						$add_post_type          = wp_json_encode(
							array(
								array(
									'url_type_slug'  => 'types',
									'url_type_name'  => 'Post Types',
									'post_type_slug' => $current_post_type_slug,
									'post_type_name' => $current_post_type_name,
								),
							)
						);
						?>
						<option value='<?php echo esc_attr( $add_post_type ); ?>'><?php echo esc_html( $available_post_type->label ); ?></option>
					<?php } ?>
				</select>
				<?php submit_button( __( 'Show Post Type', 'webchangedetector' ), 'secondary', 'submit', false ); ?>
			</form>
			<?php
		} else {
			?>
			<p><i><?php _e( 'All available post types are already shown.', 'webchangedetector' ); ?></i></p>
			<?php
		}
	}

	/**
	 * Render taxonomies form.
	 */
	private function render_taxonomies_form() {
		// Add Taxonomies form.
		$taxonomies           = get_taxonomies( array( 'public' => true ), 'objects' );
		$available_taxonomies = array();

		foreach ( $taxonomies as $taxonomy ) {
			$wp_taxonomy_slug = \WebChangeDetector\WebChangeDetector_Admin_Utils::get_taxonomy_slug( $taxonomy );
			$show_taxonomy    = false;

			if ( ! empty( $this->admin->website_details['sync_url_types'] ) ) {
				foreach ( $this->admin->website_details['sync_url_types'] as $sync_url_type ) {
					if ( $wp_taxonomy_slug && $sync_url_type['post_type_slug'] === $wp_taxonomy_slug ) {
						$show_taxonomy = true;
						break;
					}
				}
			}

			if ( $wp_taxonomy_slug && ! $show_taxonomy ) {
				$available_taxonomies[] = $taxonomy;
			}
		}

		if ( ! empty( $available_taxonomies ) ) {
			?>
			<form method="post" style="display: inline-block; margin-right: 10px;">
				<input type="hidden" name="wcd_action" value="add_post_type">
				<?php wp_nonce_field( 'add_post_type' ); ?>
				<select name="post_type">
					<?php
					foreach ( $available_taxonomies as $available_taxonomy ) {
						$current_taxonomy_slug = \WebChangeDetector\WebChangeDetector_Admin_Utils::get_taxonomy_slug( $available_taxonomy );
						$current_taxonomy_name = \WebChangeDetector\WebChangeDetector_Admin_Utils::get_taxonomy_name( $current_taxonomy_slug );
						$add_post_type         = wp_json_encode(
							array(
								array(
									'url_type_slug'  => 'taxonomies',
									'url_type_name'  => 'Taxonomies',
									'post_type_slug' => $current_taxonomy_slug,
									'post_type_name' => $current_taxonomy_name,
								),
							)
						);
						?>
						<option value='<?php echo esc_attr( $add_post_type ); ?>'><?php echo esc_html( $available_taxonomy->label ); ?></option>
					<?php } ?>
				</select>
				<?php submit_button( __( 'Show Taxonomy', 'webchangedetector' ), 'secondary', 'submit', false ); ?>
			</form>
			<?php
		} else {
			?>
			<p><i><?php _e( 'All available taxonomies are already shown.', 'webchangedetector' ); ?></i></p>
			<?php
		}
	}

	/**
	 * Render log file viewer.
	 */
	private function render_log_viewer() {
		$filename = isset( $_GET['view_log'] ) ? sanitize_file_name( $_GET['view_log'] ) : '';

		// Validate filename format.
		if ( ! preg_match( '/^wcd-\d{4}-\d{2}-\d{2}\.log$/', $filename ) ) {
			echo '<div class="error notice"><p>Invalid log file.</p></div>';
			return;
		}

		$logger = isset( $this->admin->logger ) ? $this->admin->logger : null;
		if ( ! $logger ) {
			echo '<div class="error notice"><p>Logger not available.</p></div>';
			return;
		}

		// Get log directory - same as in logger class.
		$log_dir   = plugin_dir_path( dirname( __DIR__ ) ) . 'logs';
		$file_path = $log_dir . '/' . $filename;

		if ( ! file_exists( $file_path ) ) {
			echo '<div class="error notice"><p>Log file not found at: ' . esc_html( $file_path ) . '</p></div>';
			echo '<div class="notice notice-info"><p>Log directory: ' . esc_html( $log_dir ) . '</p></div>';
			echo '<div class="notice notice-info"><p>Looking for: ' . esc_html( $filename ) . '</p></div>';

			// Show available files in the directory.
			if ( is_dir( $log_dir ) ) {
				$files = scandir( $log_dir );
				echo '<div class="notice notice-info"><p>Available files in log directory: ' . esc_html( implode( ', ', $files ) ) . '</p></div>';
			} else {
				echo '<div class="error notice"><p>Log directory does not exist!</p></div>';
			}
			return;
		}

		if ( ! is_readable( $file_path ) ) {
			echo '<div class="error notice"><p>Log file exists but is not readable.</p></div>';
			return;
		}

		// Read log content.
		$log_content = file_get_contents( $file_path );
		$lines       = explode( "\n", $log_content );

		?>
		<div class="wrap">
			<h1><?php _e( 'Log Viewer', 'webchangedetector' ); ?>: <?php echo esc_html( $filename ); ?></h1>
			
			<div style="margin: 20px 0;">
				<a href="?page=webchangedetector-settings" class="button">
					<?php _e( '← Back to Settings', 'webchangedetector' ); ?>
				</a>
				<form method="post" style="display: inline-block; margin-left: 10px;">
					<?php wp_nonce_field( 'download_log_file' ); ?>
					<input type="hidden" name="wcd_action" value="download_log_file">
					<input type="hidden" name="filename" value="<?php echo esc_attr( $filename ); ?>">
					<button type="submit" class="button button-primary">
						<?php _e( 'Download Log File', 'webchangedetector' ); ?>
					</button>
				</form>
			</div>

			<div class="wcd-log-viewer">
				<?php
				foreach ( $lines as $line ) {
					if ( empty( trim( $line ) ) ) {
						continue;
					}

					// Parse log line for better formatting.
					$formatted_line = $this->format_log_line( $line );
					echo $formatted_line;
				}
				?>
			</div>
		</div>
		<?php
	}

	/**
	 * Format a log line with syntax highlighting.
	 *
	 * @param string $line The log line to format.
	 * @return string The formatted HTML.
	 */
	private function format_log_line( $line ) {
		// Escape HTML.
		$line = esc_html( $line );

		// Highlight timestamps (format: [YYYY-MM-DD HH:MM:SS]).
		$line = preg_replace( '/\[(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2})\]/', '<span class="wcd-log-timestamp">[$1]</span>', $line );

		// Highlight log levels.
		$line = preg_replace( '/\[ERROR\]/i', '<span class="wcd-log-level-error">[ERROR]</span>', $line );
		$line = preg_replace( '/\[WARNING\]/i', '<span class="wcd-log-level-warning">[WARNING]</span>', $line );
		$line = preg_replace( '/\[INFO\]/i', '<span class="wcd-log-level-info">[INFO]</span>', $line );
		$line = preg_replace( '/\[DEBUG\]/i', '<span class="wcd-log-level-debug">[DEBUG]</span>', $line );

		// Highlight context in brackets.
		$line = preg_replace( '/\[([^\]]+)\]/', '<span class="wcd-log-context">[$1]</span>', $line );

		// Highlight URLs.
		$line = preg_replace( '/(https?:\/\/[^\s]+)/', '<span class="wcd-log-url">$1</span>', $line );

		// Highlight file paths.
		$line = preg_replace( '/(\/[^\s:]+\.(php|js|css|html))/', '<span class="wcd-log-filepath">$1</span>', $line );

		return '<div class="wcd-log-line">' . $line . '</div>';
	}
}
