<?php

namespace WebChangeDetector;

/**
 * WebChange Detector Admin Settings Management
 *
 * Handles all settings, configuration, and website details management
 * for the WebChange Detector plugin.
 *
 * This class was extracted from the main admin class as part of the
 * refactoring process to improve code organization and maintainability.
 *
 * @since      1.0.0
 * @package    WebChangeDetector
 * @subpackage WebChangeDetector/admin
 */

/**
 * WebChange Detector Admin Settings Class
 *
 * Manages plugin settings, configuration, and website details.
 * Handles monitoring settings, manual check settings, permissions,
 * and tab navigation.
 *
 * @since      1.0.0
 * @package    WebChangeDetector
 * @subpackage WebChangeDetector/admin
 */
class WebChangeDetector_Admin_Settings {

	/**
	 * Reference to the main admin instance.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      WebChangeDetector_Admin    $admin    The main admin instance.
	 */
	private $admin;

	/**
	 * API Manager instance for handling API communications.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      WebChangeDetector_API_Manager    $api_manager    The API manager instance.
	 */
	private $api_manager;

	/**
	 * Reference to the account handler instance.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      WebChangeDetector_Admin_Account    $account_handler    The account handler instance.
	 */
	private $account_handler;

	/**
	 * Initialize the settings class.
	 *
	 * @since    1.0.0
	 * @param    WebChangeDetector_Admin    $admin    The main admin instance.
	 */
	public function __construct( $admin ) {
		$this->admin = $admin;
		$this->api_manager = new WebChangeDetector_API_Manager();
		$this->account_handler = new WebChangeDetector_Admin_Account( $this->api_manager );
	}

	/**
	 * Update monitoring settings for a group.
	 *
	 * @since    1.0.0
	 * @param    array    $group_data    The group data to update.
	 * @return   array|string    The API response or error message.
	 */
	public function update_monitoring_settings( $group_data ) {
		$monitoring_settings = \WebChangeDetector\WebChangeDetector_API_V2::get_group_v2( $this->admin->monitoring_group_uuid )['data'];

		$args = array(
			'monitoring'    => true,
			'hour_of_day'   => ! isset( $group_data['hour_of_day'] ) ? $monitoring_settings['hour_of_day'] : sanitize_key( $group_data['hour_of_day'] ),
			'interval_in_h' => ! isset( $group_data['interval_in_h'] ) ? $monitoring_settings['interval_in_h'] : sanitize_text_field( $group_data['interval_in_h'] ),
			'enabled'       => ( isset( $group_data['enabled'] ) && 'on' === $group_data['enabled'] ) ? 1 : 0,
			'alert_emails'  => ! isset( $group_data['alert_emails'] ) ? $monitoring_settings['alert_emails'] : explode( ',', sanitize_textarea_field( $group_data['alert_emails'] ) ),
			'name'          => ! isset( $group_data['group_name'] ) ? $monitoring_settings['name'] : sanitize_text_field( $group_data['group_name'] ),
			'threshold'     => ! isset( $group_data['threshold'] ) ? $monitoring_settings['threshold'] : sanitize_text_field( $group_data['threshold'] ),
		);

		if ( ! empty( $group_data['css'] ) ) {
			$args['css'] = sanitize_textarea_field( $group_data['css'] );
		}

		return \WebChangeDetector\WebChangeDetector_API_V2::update_group( $this->admin->monitoring_group_uuid, $args );
	}

	/**
	 * Update manual check group settings.
	 *
	 * @since    1.0.0
	 * @param    array    $postdata    The POST data containing settings.
	 * @return   array|string    The API response or error message.
	 */
	public function update_manual_check_group_settings( $postdata ) {
		// Saving auto update settings.
		$auto_update_settings = array();
		foreach ( $postdata as $key => $value ) {
			if ( 0 === strpos( $key, 'auto_update_checks_' ) ) {
				$auto_update_settings[ $key ] = $value;
			}
		}
		$website_details = $this->admin->website_details;
		$website_details['auto_update_settings'] = $auto_update_settings;
		$this->update_website_details( $website_details );

		do_action( 'wcd_save_update_group_settings', $postdata );

		// Update group settings in API.
		$args = array(
			'name'      => $postdata['group_name'],
			'threshold' => sanitize_text_field( $postdata['threshold'] ),
		);

		if ( ! empty( $postdata['css'] ) ) {
			$args['css'] = sanitize_textarea_field( $postdata['css'] );
		}

		return ( \WebChangeDetector\WebChangeDetector_API_V2::update_group( $this->admin->manual_group_uuid, $args ) );
	}

	/**
	 * Get URL settings for monitoring or manual groups.
	 *
	 * @since    1.0.0
	 * @param    bool    $monitoring_group    Whether this is for monitoring group.
	 * @return   void    Outputs the settings HTML.
	 */
	public function get_url_settings( $monitoring_group = false ) {
		// Sync urls - post_types defined in function @TODO make settings for post_types to sync.

		if ( ! empty( $_GET['_wpnonce'] ) && ! wp_verify_nonce( wp_unslash( sanitize_key( $_GET['_wpnonce'] ) ) ) ) {
			echo 'Something went wrong. Try again.';
			wp_die();
		}

		if ( $monitoring_group ) {
			$group_id = $this->admin->monitoring_group_uuid;
		} else {
			$group_id = $this->admin->manual_group_uuid;
		}

		// Setting pagination page.
		$page = 1;
		if ( ! empty( $_GET['paged'] ) ) {
			$page = sanitize_key( wp_unslash( $_GET['paged'] ) );
		}

		// Set filters for urls.
		$filters = array(
			'per_page' => 20,
			'sorted'   => 'selected',
			'page'     => $page,
		);

		$pagination_params = array();
		if ( ! empty( $_GET['post-type'] ) ) {
			$filters['category']            = \WebChangeDetector\WebChangeDetector_Admin_Utils::get_post_type_name( sanitize_text_field( wp_unslash( $_GET['post-type'] ) ) );
			$pagination_params['post-type'] = sanitize_text_field( wp_unslash( $_GET['post-type'] ) );
		}
		if ( ! empty( $_GET['taxonomy'] ) ) {
			$filters['category']           = \WebChangeDetector\WebChangeDetector_Admin_Utils::get_taxonomy_name( sanitize_text_field( wp_unslash( $_GET['taxonomy'] ) ) );
			$pagination_params['taxonomy'] = sanitize_text_field( wp_unslash( $_GET['post-type'] ) );

		}
		if ( ! empty( $_GET['search'] ) ) {
			$filters['search']           = sanitize_text_field( wp_unslash( $_GET['search'] ) );
			$pagination_params['search'] = sanitize_text_field( wp_unslash( $_GET['search'] ) );
		}

		// Get the urls.
		$group_and_urls = $this->admin->get_group_and_urls( $group_id, $filters );
		$urls           = $group_and_urls['urls'];
		$urls_meta      = $group_and_urls['meta'];

		// Set tab for the right url.
		$tab = 'update-settings'; // init.
		if ( $monitoring_group ) {
			$tab = 'auto-settings';
		}

		// Show message if no urls are selected.
		if ( ! $group_and_urls['selected_urls_count'] ) {
			?>
			<div class="notice notice-warning"><p><strong>WebChange Detector:</strong> Select URLs for manual checks to get started.</p></div>
			<?php
		}

		$nonce = wp_create_nonce( 'ajax-nonce' );
		?>

		<div class="wcd-select-urls-container">
			<?php

			// Include the group settings.
			if ( ! $monitoring_group ) {
				include 'partials/templates/update-settings.php';
			} else {

				// Print the status bar.
				$this->admin->print_monitoring_status_bar( $group_and_urls );

				// Monitoring settings.
				include 'partials/templates/auto-settings.php';
			}

			// Select URLs section.
			if ( ( ! $monitoring_group && $this->is_allowed( 'manual_checks_urls' ) ) || ( $monitoring_group && $this->is_allowed( 'monitoring_checks_urls' ) ) ) {
				?>

			<div class="wcd-frm-settings box-plain">
				<h2>Select URLs to Check<br><small></small></h2>
				<p style="text-align: center;">
					<strong>Currently selected URLs: <?php echo esc_html( $group_and_urls['selected_urls_count'] ); ?></strong><br>
					Missing URLs? Select them from other post types and taxonomies by enabling them in the
					<a href="?page=webchangedetector-settings">Settings</a><br>
					
				</p>
				<input type="hidden" value="webchangedetector" name="page">
				<input type="hidden" value="<?php echo esc_html( $group_and_urls['id'] ); ?>" name="group_id">

					<div class="group_urls_container">
						<form method="get" style="float: left;">
							<input type="hidden" name="page" value="webchangedetector-<?php echo esc_html( $tab ); ?>">

							Post types
							<select id="filter-post-type" name="post-type">
								<option value="0">All</option>
								<?php
								$selected_post_type = isset( $_GET['post-type'] ) ? sanitize_text_field( wp_unslash( $_GET['post-type'] ) ) : array();

								// Fix for old sync_url_types.
								$website_details = $this->admin->website_details;
								$sync_url_types = $website_details['sync_url_types'];
								if ( is_string( $website_details['sync_url_types'] ) ) {
									$sync_url_types = json_decode( $website_details['sync_url_types'], true );
								}

								if ( ! get_option( 'page_on_front' ) && ! in_array( 'frontpage', array_column( $sync_url_types, 'post_type_slug' ), true ) ) {
									?>
									<option value="frontpage" <?php echo 'frontpage' === $selected_post_type ? 'selected' : ''; ?> >Frontpage</option>
									<?php
								}

								foreach ( $sync_url_types as $url_type ) {
									if ( 'types' !== $url_type['url_type_slug'] ) {
										continue;
									}
									$selected = $url_type['post_type_slug'] === $selected_post_type ? 'selected' : '';
									?>
									<option value="<?php echo esc_html( $url_type['post_type_slug'] ); ?>" <?php echo esc_html( $selected ); ?>>
										<?php echo esc_html( \WebChangeDetector\WebChangeDetector_Admin_Utils::get_post_type_name( $url_type['post_type_slug'] ) ); ?>
									</option>
								<?php } ?>
							</select>

							Taxonomies
							<select id="filter-taxonomy" name="taxonomy">
								<option value="0">All</option>
								<?php
								$selected_post_type = isset( $_GET['taxonomy'] ) ? sanitize_text_field( wp_unslash( $_GET['taxonomy'] ) ) : '';

								foreach ( $sync_url_types as $url_type ) {
									if ( 'types' === $url_type['url_type_slug'] ) {
										continue;
									}
									$selected = $url_type['post_type_slug'] === $selected_post_type ? 'selected' : '';
									?>
									<option value="<?php echo esc_html( $url_type['post_type_slug'] ); ?>" <?php echo esc_html( $selected ); ?>>
										<?php echo esc_html( \WebChangeDetector\WebChangeDetector_Admin_Utils::get_taxonomy_name( $url_type['post_type_slug'] ) ); ?>
									</option>
								<?php } ?>
							</select>
							<button class="button button-secondary">Filter</button>
						</form>

						<script>
							jQuery("#filter-post-type").change(function() {
								if(jQuery(this).val() !== '0') {
									jQuery('#filter-taxonomy').val(0);
								}
							});

							jQuery("#filter-taxonomy").change(function() {
								if(jQuery(this).val() !== '0') {
									jQuery('#filter-post-type').val(0);
								}
							});
						</script>

						<form method="get" style="float: right;">
							<input type="hidden" name="page" value="webchangedetector-<?php echo esc_html( $tab ); ?>">
							<button type="submit" style="float: right" class="button button-secondary">Search</button>
							<input style="margin: 0" name="search" type="text" placeholder="Search" value="<?php echo esc_html( sanitize_text_field( wp_unslash( $_GET['search'] ?? '' ) ) ); ?>">
						</form>
						<div class="clear" style="margin-bottom: 20px;"></div>

						<table class="no-margin filter-table">
							<tr>
												<th style="min-width: 50px; text-align: center;"><?php \WebChangeDetector\WebChangeDetector_Admin_Utils::get_device_icon( 'desktop' ); ?><br>Desktop</th>
				<th style="min-width: 50px; text-align: center;"><?php \WebChangeDetector\WebChangeDetector_Admin_Utils::get_device_icon( 'mobile' ); ?> Mobile</th>
								<th style="width: 100%">URL</th>
								<th style="min-width: 90px">Post type</th>
							</tr>
							<?php if ( count( $urls ) ) { ?>
								<?php // Select all from same device. ?>
								<tr class=" even-tr-white" style="background: none; text-align: center">
									<td>
										<label class="switch">
											<input type="checkbox"
											id="select-desktop"
											data-nonce="<?php echo esc_html( $nonce ); ?>"
											data-screensize="desktop"
											onclick="mmToggle( this, 'desktop', '<?php echo esc_html( $group_and_urls['id'] ); ?>' ); postUrl('select-desktop');"/>
											<span class="slider round"></span>
										</label>
									</td>

									<td>
										<label class="switch">
											<input type="checkbox"
											id="select-mobile"
											data-nonce="<?php echo esc_html( $nonce ); ?>"
											data-screensize="mobile"
											onclick="mmToggle( this, 'mobile', '<?php echo esc_html( $group_and_urls['id'] ); ?>' ); postUrl('select-mobile');" />
											<span class="slider round"></span>
										</label>
									</td>

									<td><strong>Select all</strong></td>
									<td></td>
								</tr>
								<?php
								foreach ( $urls as $url ) {
									$this->admin->generate_slider_html( 'url', 'desktop', $url['enabled_desktop'], $url, $url['id'], $group_and_urls['id'] );
								}
								?>
							<?php } else { ?>
								<tr>
									<td colspan="4" style="text-align: center; color: #999; font-style: italic;">
										<?php echo esc_html__( 'No URLs found. Try adjusting your filters or check your URL sync settings.', 'webchangedetector' ); ?>
									</td>
								</tr>
							<?php } ?>
						</table>

						<?php
						// Pagination.
						if ( $urls_meta['total_pages'] > 1 ) {
							?>
							<div class="pagination-container" style="text-align: center; margin-top: 20px;">
								<?php
								$pagination_params['page'] = 'webchangedetector-' . $tab;
								$current_page = $page;
								$total_pages = $urls_meta['total_pages'];

								// Previous page link.
								if ( $current_page > 1 ) {
									$prev_page = $current_page - 1;
									$pagination_params['paged'] = $prev_page;
									$prev_url = add_query_arg( $pagination_params, admin_url( 'admin.php' ) );
									echo '<a href="' . esc_url( $prev_url ) . '" class="button button-secondary">← Previous</a> ';
								}

								// Page numbers.
								for ( $i = 1; $i <= $total_pages; $i++ ) {
									if ( $i == $current_page ) {
										echo '<strong>' . esc_html( $i ) . '</strong> ';
									} else {
										$pagination_params['paged'] = $i;
										$page_url = add_query_arg( $pagination_params, admin_url( 'admin.php' ) );
										echo '<a href="' . esc_url( $page_url ) . '">' . esc_html( $i ) . '</a> ';
									}
								}

								// Next page link.
								if ( $current_page < $total_pages ) {
									$next_page = $current_page + 1;
									$pagination_params['paged'] = $next_page;
									$next_url = add_query_arg( $pagination_params, admin_url( 'admin.php' ) );
									echo ' <a href="' . esc_url( $next_url ) . '" class="button button-secondary">Next →</a>';
								}
								?>
							</div>
							<?php
						}
						?>
					</div>
				</div>
				<?php
			}
			?>
		</div>
		<?php
	}

	/**
	 * Get website details from API.
	 *
	 * @since    1.0.0
	 * @return   array|string    The website details or error message.
	 */
	public function get_website_details() {
		static $website_details;

		if ( empty( $website_details ) ) {
			$websites = \WebChangeDetector\WebChangeDetector_API_V2::get_websites_v2();

			if ( empty( $websites['data'] ) ) {
				return 'No website details. Create them first.';
			}

			foreach ( $websites['data'] as $website ) {
				if ( str_starts_with( rtrim( $website['domain'] ?? '', '/' ), rtrim( \WebChangeDetector\WebChangeDetector_Admin_Utils::get_domain_from_site_url() ?? '', '/' ) ) ) {
					$website_details                   = $website;
					$website_details['sync_url_types'] = is_string( $website['sync_url_types'] ) ? json_decode( $website['sync_url_types'], true ) : $website['sync_url_types'] ?? array();
					break;
				}
			}
		}

		$update = false;

		// Set default sync types.
		if ( ! empty( $website_details ) && empty( $website_details['sync_url_types'] ) ) {
			$update = true;
			if ( $this->is_allowed( 'only_frontpage' ) ) {
				$website_details['sync_url_types'] = array(
					array(
						'url_type_slug'  => 'types',
						'url_type_name'  => 'frontpage',
						'post_type_slug' => 'frontpage',
						'post_type_name' => 'Frontpage',
					),
				);
			} else {
				$website_details['sync_url_types'] = array(
					array(
						'url_type_slug'  => 'types',
						'url_type_name'  => 'Post Types',
						'post_type_slug' => 'posts',
						'post_type_name' => \WebChangeDetector\WebChangeDetector_Admin_Utils::get_post_type_name( 'posts' ),
					),
					array(
						'url_type_slug'  => 'types',
						'url_type_name'  => 'Post Types',
						'post_type_slug' => 'pages',
						'post_type_name' => \WebChangeDetector\WebChangeDetector_Admin_Utils::get_post_type_name( 'pages' ),
					),
				);
			}
		}

		// Set default auto update settings.
		if ( ! empty( $website_details ) && empty( $website_details['auto_update_settings'] ) ) {
			$update                                  = true;
			$website_details['auto_update_settings'] = array(
				'auto_update_checks_enabled'   => true,
				'auto_update_checks_from'      => gmdate( 'H:i' ),
				'auto_update_checks_to'        => gmdate( 'H:i', strtotime( '+12 hours' ) ),
				'auto_update_checks_monday'    => true,
				'auto_update_checks_tuesday'   => true,
				'auto_update_checks_wednesday' => true,
				'auto_update_checks_thursday'  => true,
				'auto_update_checks_friday'    => true,
				'auto_update_checks_saturday'  => true,
				'auto_update_checks_sunday'    => true,
				'auto_update_checks_emails'    => get_option( 'admin_email' ),
			);

            // Get local auto update settings from option.
			$local_auto_update_settings              = get_option( WCD_AUTO_UPDATE_SETTINGS );

            // If local auto update settings exist, merge them with the default settings. We don't need them locally anymore.
			if ( $local_auto_update_settings && is_array( $local_auto_update_settings ) ) {
				delete_option( WCD_AUTO_UPDATE_SETTINGS );
				$website_details['auto_update_settings'] = array_merge( $website_details['auto_update_settings'], $local_auto_update_settings );
			}
		}

		if ( $update ) {
			$this->update_website_details( $website_details );
		}

		return $website_details ?? false;
	}

	/**
	 * Update website details with current settings.
	 *
	 * @since    1.0.0
	 * @param    array|false    $update_website_details    Website details to update.
	 * @return   void
	 */
	public function update_website_details( $update_website_details = false ) {
		if ( ! $update_website_details ) {
			$update_website_details = $this->admin->website_details;
		}
		\WebChangeDetector\WebChangeDetector_API_V2::update_website_v2( $update_website_details['id'], $update_website_details );
	}

	/**
	 * Check if current account is allowed for specific view or action.
	 *
	 * @since    1.0.0
	 * @param    string    $allowed    The allowance string to check.
	 * @return   bool|int    True if allowed, false if not, or integer value for specific allowances.
	 */
	public function is_allowed( $allowed ) {
		$website_details = $this->admin->website_details;
		$allowances = $website_details['allowances'] ?? false;

		// Set default allowances if we don't have any yet.
		if ( empty( $allowances ) ) {
			$allowances = array(
				'change_detections_view'     => 1,
				'manual_checks_view'         => 1,
				'manual_checks_start'        => 1,
				'manual_checks_settings'     => 1,
				'manual_checks_urls'         => 1,
				'monitoring_checks_view'     => 1,
				'monitoring_checks_settings' => 1,
				'monitoring_checks_urls'     => 1,
				'logs_view'                  => 1,
				'settings_view'              => 1,
				'settings_add_urls'          => 1,
				'settings_account_settings'  => 1,
				'upgrade_account'            => 1,
				'wizard_start'               => 1,
				'only_frontpage'             => 0,
			);
		}

		// Disable upgrade account for subaccounts.
		if ( ! empty( $this->account_handler->get_account()['is_subaccount'] ) && $this->account_handler->get_account()['is_subaccount'] ) {
			$allowances['upgrade_account'] = 0;
		}

		// Save allowances as option for the admin menu.
		update_option( WCD_ALLOWANCES, ( $allowances ) );

		// Return allowance value if exists.
		if ( array_key_exists( $allowed, $allowances ) ) {
			return $allowances[ $allowed ];
		}

		// Shouldn't get here. But if so, we allow.
		return true;
	}

	/**
	 * Display navigation tabs for the plugin.
	 *
	 * @since    1.0.0
	 * @return   void
	 */
	public function tabs() {
		$active_tab = 'webchangedetector';

		if ( ! empty( $_GET['_wpnonce'] ) && ! wp_verify_nonce( wp_unslash( sanitize_key( $_GET['_wpnonce'] ) ) ) ) {
			echo 'Something went wrong. Please try again.';
		}

		if ( isset( $_GET['page'] ) ) {
			$active_tab = sanitize_text_field( wp_unslash( $_GET['page'] ) );
		}
		?>
		<div class="wrap">
			<h2 class="nav-tab-wrapper">
				<?php if ( $this->is_allowed( 'dashboard_view' ) ) { ?>
				<a href="?page=webchangedetector"
					class="nav-tab <?php echo 'webchangedetector' === $active_tab ? 'nav-tab-active' : ''; ?>">
					<?php \WebChangeDetector\WebChangeDetector_Admin_Utils::get_device_icon( 'dashboard' ); ?> Dashboard
				</a>
				<?php } ?>
				<?php if ( $this->is_allowed( 'manual_checks_view' ) ) { ?>
				<a href="?page=webchangedetector-update-settings"
					class="nav-tab <?php echo 'webchangedetector-update-settings' === $active_tab ? 'nav-tab-active' : ''; ?>">
					<?php \WebChangeDetector\WebChangeDetector_Admin_Utils::get_device_icon( 'update-group' ); ?> Manual Checks & Auto Update Checks
				</a>
				<?php } ?>
				<?php if ( $this->is_allowed( 'monitoring_checks_view' ) ) { ?>
				<a href="?page=webchangedetector-auto-settings"
					class="nav-tab <?php echo 'webchangedetector-auto-settings' === $active_tab ? 'nav-tab-active' : ''; ?>">
					<?php \WebChangeDetector\WebChangeDetector_Admin_Utils::get_device_icon( 'auto-group' ); ?> Monitoring
				</a>
				<?php } ?>
				<?php if ( $this->is_allowed( 'change_detections_view' ) ) { ?>
				<a href="?page=webchangedetector-change-detections"
					class="nav-tab <?php echo 'webchangedetector-change-detections' === $active_tab ? 'nav-tab-active' : ''; ?>">
					<?php \WebChangeDetector\WebChangeDetector_Admin_Utils::get_device_icon( 'change-detections' ); ?> Change Detections
				</a>
				<?php } ?>
				<?php if ( $this->is_allowed( 'logs_view' ) ) { ?>
				<a href="?page=webchangedetector-logs"
					class="nav-tab <?php echo 'webchangedetector-logs' === $active_tab ? 'nav-tab-active' : ''; ?>">
					<?php \WebChangeDetector\WebChangeDetector_Admin_Utils::get_device_icon( 'logs' ); ?> Queue
				</a>
				<?php } ?>
				<?php if ( $this->is_allowed( 'settings_view' ) ) { ?>
				<a href="?page=webchangedetector-settings"
					class="nav-tab <?php echo 'webchangedetector-settings' === $active_tab ? 'nav-tab-active' : ''; ?>">
					<?php \WebChangeDetector\WebChangeDetector_Admin_Utils::get_device_icon( 'settings' ); ?> Settings
				</a>
				<?php } ?>
			</h2>
		</div>
		<?php
	}
} 