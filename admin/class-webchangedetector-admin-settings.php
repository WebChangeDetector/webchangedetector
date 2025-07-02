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
		// Debug: Log what we received for monitoring settings
		\WebChangeDetector\WebChangeDetector_Admin_Utils::log_error( 'Monitoring POST data received: ' . print_r( $group_data, true ) );
		\WebChangeDetector\WebChangeDetector_Admin_Utils::log_error( 'Full $_POST data: ' . print_r( $_POST, true ) );
		
		$monitoring_settings = \WebChangeDetector\WebChangeDetector_API_V2::get_group_v2( $this->admin->monitoring_group_uuid )['data'];

		$args = array(
			'monitoring'    => true,
			'hour_of_day'   => isset( $group_data['hour_of_day'] ) ? sanitize_key( $group_data['hour_of_day'] ) : $monitoring_settings['hour_of_day'],
			'interval_in_h' => isset( $group_data['interval_in_h'] ) ? sanitize_text_field( $group_data['interval_in_h'] ) : $monitoring_settings['interval_in_h'],
			'enabled'       => isset( $group_data['enabled'] ) && ( 'on' === $group_data['enabled'] || '1' === $group_data['enabled'] ),
			'alert_emails'  => isset( $group_data['alert_emails'] ) ? explode( ',', sanitize_textarea_field( $group_data['alert_emails'] ) ) : $monitoring_settings['alert_emails'],
			'name'          => isset( $group_data['group_name'] ) ? sanitize_text_field( $group_data['group_name'] ) : $monitoring_settings['name'],
			'threshold'     => isset( $group_data['threshold'] ) ? sanitize_text_field( $group_data['threshold'] ) : $monitoring_settings['threshold'],
		);

		if ( ! empty( $group_data['css'] ) ) {
			$args['css'] = sanitize_textarea_field( $group_data['css'] );
		}

		// Debug: Log what we're sending to the API
		\WebChangeDetector\WebChangeDetector_Admin_Utils::log_error( 'API update args: ' . print_r( $args, true ) );
		\WebChangeDetector\WebChangeDetector_Admin_Utils::log_error( 'Monitoring group UUID: ' . $this->admin->monitoring_group_uuid );
		
		// Check if monitoring group UUID exists
		if ( empty( $this->admin->monitoring_group_uuid ) ) {
			\WebChangeDetector\WebChangeDetector_Admin_Utils::log_error( 'ERROR: Monitoring group UUID is empty!' );
			return array(
				'success' => false,
				'message' => 'Monitoring group UUID is not set. Please contact support.',
			);
		}
		$result = \WebChangeDetector\WebChangeDetector_API_V2::update_group( $this->admin->monitoring_group_uuid, $args );
		
		// Debug: Log the API response
		\WebChangeDetector\WebChangeDetector_Admin_Utils::log_error( 'API response: ' . print_r( $result, true ) );
		
		// Return standardized response format
		if ( $result && ! is_string( $result ) ) {
			return array(
				'success' => true,
				'message' => 'Monitoring settings saved successfully.',
				'data' => $result,
			);
		} else {
			$error_msg = 'Failed to save monitoring settings.';
			if ( is_string( $result ) ) {
				$error_msg .= ' Error: ' . $result;
			}
			return array(
				'success' => false,
				'message' => $error_msg,
			);
		}
	}

	/**
	 * Update manual check group settings.
	 *
	 * @since    1.0.0
	 * @param    array    $postdata    The POST data containing settings.
	 * @return   array|string    The API response or error message.
	 */
	public function update_manual_check_group_settings( $postdata ) {
		// Debug: Log what we received
		\WebChangeDetector\WebChangeDetector_Admin_Utils::log_error( 'POST data received: ' . print_r( $postdata, true ) );
		
		// Saving auto update settings.
		$auto_update_settings = array();
		foreach ( $postdata as $key => $value ) {
			if ( 0 === strpos( $key, 'auto_update_checks_' ) ) {
				$auto_update_settings[ $key ] = $value;
			}
		}
		
		// Handle checkbox for auto_update_checks_enabled (unchecked checkboxes don't submit a value).
		if ( ! isset( $auto_update_settings['auto_update_checks_enabled'] ) ) {
			$auto_update_settings['auto_update_checks_enabled'] = '0';
		}
		
		// Debug: Log what auto update settings we extracted
		\WebChangeDetector\WebChangeDetector_Admin_Utils::log_error( 'Auto update settings extracted: ' . print_r( $auto_update_settings, true ) );
		
		$this->admin->website_details['auto_update_settings'] = $auto_update_settings;
		$this->update_website_details( $this->admin->website_details );

		// Force refresh website details cache after saving
		$this->get_website_details( true );

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
		$urls           = $group_and_urls['urls'] ?? array();
		$urls_meta      = $group_and_urls['meta'] ?? array();

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
			// Print the status bar for monitoring group.
			if ( $monitoring_group ) {
				$this->admin->print_monitoring_status_bar( $group_and_urls );
			}
			
			// Add Manual Checks Workflow section at the top (only for manual checks, not monitoring)
			if ( ! $monitoring_group && $group_and_urls['selected_urls_count'] > 0 ) {
				?>
				<div class="wcd-settings-section">
					<div class="wcd-settings-card wcd-start-checks-card">
						<div class="wcd-form-row">
							<div class="wcd-form-label-wrapper">
								<label class="wcd-form-label"><span class="dashicons dashicons-controls-play"></span> Manual Checks Workflow</label>
								<div class="wcd-description">Ready to start your manual checks? Click the button below to begin checking your selected URLs for changes.</div>
							</div>
							<div class="wcd-form-control">
								<button style="margin-top: 10px;" type="button" class="button button-primary" onclick="startManualChecks('<?php echo esc_js( $group_id ); ?>')">
									<span class="dashicons dashicons-controls-play"></span> Start Manual Checks
								</button>
							</div>
						</div>
					</div>
				</div>
				
				<hr style="margin: 20px 0; border-color: #e1e5e9;">
				<?php
			}
			?>
			
			<div class="wcd-settings-flex-container">
				<?php
				// Include the group settings.
				if ( ! $monitoring_group ) {
					$this->admin->view_renderer->get_component( 'templates' )->render_update_settings( $group_and_urls, $group_id );
				} else {
					// Monitoring settings.
					$this->admin->view_renderer->get_component( 'templates' )->render_auto_settings( $group_and_urls, $group_id );
				}

				// Select URLs section.
				if ( ( ! $monitoring_group && $this->is_allowed( 'manual_checks_urls' ) ) || ( $monitoring_group && $this->is_allowed( 'monitoring_checks_urls' ) ) ) {
					?>

			<div class="wcd-url-selection wcd-settings-card">
				<h2>Select URLs to Check<br><small></small></h2>
				<p style="text-align: center;">
					<strong>Currently selected URLs: <?php echo esc_html( $group_and_urls['selected_urls_count'] ); ?></strong><br>
					Missing URLs? Select them from other post types and taxonomies by enabling them in the
					<a href="?page=webchangedetector-settings">Settings</a><br>
					
				</p>
				<input type="hidden" value="webchangedetector" name="page">
				<input type="hidden" value="<?php echo esc_html( $group_and_urls['id'] ?? '' ); ?>" name="group_id">

					<div class="group_urls_container">
						<form method="get" style="float: left;">
							<input type="hidden" name="page" value="webchangedetector-<?php echo esc_html( $tab ); ?>">

							<div style="display: inline-block; margin-right: 15px; vertical-align: top;">
								<label for="filter-post-type" style="display: block; font-weight: 600; margin-bottom: 4px;">Post types</label>
								<select id="filter-post-type" name="post-type">
									<option value="0">All</option>
									<?php
									$selected_post_type = isset( $_GET['post-type'] ) ? sanitize_text_field( wp_unslash( $_GET['post-type'] ) ) : array();

									// Fix for old sync_url_types.
									$website_details = $this->admin->website_details ?? array();
									$sync_url_types = $website_details['sync_url_types'] ?? array();
									if ( isset( $website_details['sync_url_types'] ) && is_string( $website_details['sync_url_types'] ) ) {
										$sync_url_types = json_decode( $website_details['sync_url_types'], true ) ?? array();
									}

									if ( ! get_option( 'page_on_front' ) && ! empty( $sync_url_types ) && ! in_array( 'frontpage', array_column( $sync_url_types, 'post_type_slug' ), true ) ) {
										?>
										<option value="frontpage" <?php echo 'frontpage' === $selected_post_type ? 'selected' : ''; ?> >Frontpage</option>
										<?php
									}

									foreach ( $sync_url_types ?? array() as $url_type ) {
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
							</div>

							<div style="display: inline-block; margin-right: 15px; vertical-align: top;">
								<label for="filter-taxonomy" style="display: block; font-weight: 600; margin-bottom: 4px;">Taxonomies</label>
								<select id="filter-taxonomy" name="taxonomy">
									<option value="0">All</option>
									<?php
									$selected_post_type = isset( $_GET['taxonomy'] ) ? sanitize_text_field( wp_unslash( $_GET['taxonomy'] ) ) : '';

									foreach ( $sync_url_types ?? array() as $url_type ) {
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
							</div>
							
							<div style="display: inline-block; vertical-align: top; margin-top: 22px;">
								<button class="button button-secondary">Filter</button>
							</div>
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
										<label class="wcd-modern-switch">
											<input type="checkbox"
											id="select-desktop"
											data-nonce="<?php echo esc_html( $nonce ); ?>"
											data-screensize="desktop"
											onclick="mmToggle( this, 'desktop', '<?php echo esc_html( $group_and_urls['id'] ?? '' ); ?>' ); postUrl('select-desktop');"/>
											<span class="wcd-modern-slider"></span>
										</label>
									</td>

									<td>
										<label class="wcd-modern-switch">
											<input type="checkbox"
											id="select-mobile"
											data-nonce="<?php echo esc_html( $nonce ); ?>"
											data-screensize="mobile"
											onclick="mmToggle( this, 'mobile', '<?php echo esc_html( $group_and_urls['id'] ?? '' ); ?>' ); postUrl('select-mobile');" />
											<span class="wcd-modern-slider"></span>
										</label>
									</td>

									<td><strong>Select all</strong></td>
									<td></td>
								</tr>
								<?php
                                
								foreach ( $urls as $url ) {
									// init.
									$checked = array(
										'desktop' => $url['desktop'] ? 'checked' : '',
										'mobile'  => $url['mobile'] ? 'checked' : '',
									);
									?>
									<tr class="live-filter-row even-tr-white post_id_<?php echo esc_html( $group_and_urls['id'] ?? '' ); ?>" id="<?php echo esc_html( $url['id'] ); ?>" >
										<td class="checkbox-desktop" style="text-align: center;">
											<input type="hidden" value="0" name="desktop-<?php echo esc_html( $url['id'] ); ?>">
											<label class="wcd-modern-switch">
												<input type="checkbox"
												data-nonce="<?php echo esc_html( $nonce ); ?>"
												data-type="<?php echo esc_html( lcfirst( $url['category'] ) ); ?>"
												data-screensize="desktop"
												data-url_id="<?php echo esc_html( $url['id'] ); ?>"
												name="desktop-<?php echo esc_html( $url['id'] ); ?>"
												value="1" <?php echo esc_html( $checked['desktop'] ); ?>
												id="desktop-<?php echo esc_html( $url['id'] ); ?>"
												onclick="mmMarkRows('<?php echo esc_html( $url['id'] ); ?>'); postUrl('<?php echo esc_html( $url['id'] ); ?>');" >
												<span class="wcd-modern-slider"></span>
											</label>
										</td>

										<td class="checkbox-mobile" style="text-align: center;">
										<input type="hidden" value="0" name="mobile-<?php echo esc_html( $url['id'] ); ?>">
										<label class="wcd-modern-switch">
											<input type="checkbox"
											data-nonce="<?php echo esc_html( $nonce ); ?>"
											data-type="<?php echo esc_html( lcfirst( $url['category'] ) ); ?>"
											data-screensize="mobile"
											data-url_id="<?php echo esc_html( $url['id'] ); ?>"
											name="mobile-<?php echo esc_html( $url['id'] ); ?>"
											value="1" <?php echo esc_html( $checked['mobile'] ); ?>
											id="mobile-<?php echo esc_html( $url['id'] ); ?>"
											onclick="mmMarkRows('<?php echo esc_html( $url['id'] ); ?>'); postUrl('<?php echo esc_html( $url['id'] ); ?>');" >
											<span class="wcd-modern-slider"></span>
										</label>
										</td>

										<td style="text-align: left;">
											<strong><?php echo esc_html( $url['html_title'] ); ?></strong><br>
											<a href="<?php echo ( is_ssl() ? 'https://' : 'http://' ) . esc_html( $url['url'] ); ?>" target="_blank"><?php echo esc_html( $url['url'] ); ?></a>
										</td>

										<td style="text-align: left;"><?php echo esc_html( $url['category'] ); ?></td>

									</tr>

									<script>mmMarkRows('<?php echo esc_html( $url['id'] ); ?>');</script>

									<?php
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
						if ( ! empty( $urls_meta ) && isset( $urls_meta['last_page'] ) && $urls_meta['last_page'] > 1 ) {
							?>
							<div class="wcd-pagination-container" style="text-align: center; margin: 30px 0; padding: 20px; background: #f8f9fa; border-radius: 8px; border: 1px solid #e1e5e9;">
								<div class="wcd-pagination-wrapper">
									<?php
									// Initialize pagination variables
									$pagination_params['page'] = 'webchangedetector-' . $tab;
									$current_page = $urls_meta['current_page'] ?? $page;
									$total_pages = $urls_meta['last_page'];
									$total_items = $urls_meta['total'] ?? 0;
									?>
									<div class="wcd-pagination-info">
										<span class="wcd-displaying-num" style="color: #646970; font-weight: 500; font-size: 14px;">Showing page <?php echo esc_html( $current_page ); ?> of <?php echo esc_html( $total_pages ); ?> (<?php echo esc_html( $total_items ); ?> total URLs)</span>
									</div>
									<div class="wcd-pagination-links" style="margin-top: 15px;">
										<?php

										// Previous page link.
										if ( $current_page > 1 ) {
											$prev_page = $current_page - 1;
											$pagination_params['paged'] = $prev_page;
											$prev_url = add_query_arg( $pagination_params, admin_url( 'admin.php' ) );
											echo '<a class="wcd-pagination-btn et_pb_button" href="' . esc_url( $prev_url ) . '" style="margin: 0 3px;">‹ Previous</a> ';
										} else {
											echo '<span class="wcd-pagination-btn et_pb_button disabled" style="margin: 0 3px; opacity: 0.5; cursor: not-allowed; background: #dcdcde;">‹ Previous</span> ';
										}

										// Page numbers with smart truncation.
										$start_page = max( 1, $current_page - 2 );
										$end_page = min( $total_pages, $current_page + 2 );

										if ( $start_page > 1 ) {
											$pagination_params['paged'] = 1;
											$page_url = add_query_arg( $pagination_params, admin_url( 'admin.php' ) );
											echo '<a class="wcd-page-num et_pb_button small" href="' . esc_url( $page_url ) . '" style="margin: 0 2px;">1</a> ';
											if ( $start_page > 2 ) {
												echo '<span class="wcd-page-dots" style="margin: 0 5px; color: #646970;">…</span> ';
											}
										}

										for ( $i = $start_page; $i <= $end_page; $i++ ) {
											if ( $i == $current_page ) {
												echo '<span class="wcd-page-num et_pb_button small primary current" style="margin: 0 2px; background: #0073aa; color: #fff; font-weight: 700;">' . esc_html( $i ) . '</span> ';
											} else {
												$pagination_params['paged'] = $i;
												$page_url = add_query_arg( $pagination_params, admin_url( 'admin.php' ) );
												echo '<a class="wcd-page-num et_pb_button small" href="' . esc_url( $page_url ) . '" style="margin: 0 2px; background: #fff; color: #0073aa; border: 1px solid #dcdcde;">' . esc_html( $i ) . '</a> ';
											}
										}

										if ( $end_page < $total_pages ) {
											if ( $end_page < $total_pages - 1 ) {
												echo '<span class="wcd-page-dots" style="margin: 0 5px; color: #646970;">…</span> ';
											}
											$pagination_params['paged'] = $total_pages;
											$page_url = add_query_arg( $pagination_params, admin_url( 'admin.php' ) );
											echo '<a class="wcd-page-num et_pb_button small" href="' . esc_url( $page_url ) . '" style="margin: 0 2px; background: #fff; color: #0073aa; border: 1px solid #dcdcde;">' . esc_html( $total_pages ) . '</a> ';
										}

										// Next page link.
										if ( $current_page < $total_pages ) {
											$next_page = $current_page + 1;
											$pagination_params['paged'] = $next_page;
											$next_url = add_query_arg( $pagination_params, admin_url( 'admin.php' ) );
											echo ' <a class="wcd-pagination-btn et_pb_button" href="' . esc_url( $next_url ) . '" style="margin: 0 3px;">Next ›</a>';
										} else {
											echo ' <span class="wcd-pagination-btn et_pb_button disabled" style="margin: 0 3px; opacity: 0.5; cursor: not-allowed; background: #dcdcde;">Next ›</span>';
										}
										?>
									</div>
								</div>
							</div>
							<?php
						}
						?>
					</div>
				</div>
			</div> <!-- Close flex container -->
			

				
				<?php
			} else {
				// Close flex container even if URL selection is not allowed
				?>
			</div> <!-- Close flex container -->
			<?php
			}
			?>
		</div>
		<?php
	}

	/**
	 * Clear the cached website details.
	 *
	 * @since    1.0.0
	 * @return   void
	 */
	public function clear_website_details_cache() {
		// This method forces a refresh of the static cached website details
		// by calling get_website_details with the force_refresh parameter
		$this->get_website_details( true );
	}

	/**
	 * Get website details from API.
	 *
	 * @since    1.0.0
	 * @param    bool    $force_refresh    Whether to force refresh the cached data.
	 * @return   array|string    The website details or error message.
	 */
	public function get_website_details( $force_refresh = false ) {
		static $website_details;

		if ( $force_refresh || empty( $website_details ) ) {
			$websites = \WebChangeDetector\WebChangeDetector_API_V2::get_websites_v2();

			if ( empty( $websites['data'] ) ) {
				return 'No website details. Create them first.';
			}

			foreach ( $websites['data'] as $website ) {
				if ( str_starts_with( rtrim( $website['domain'], '/' ), rtrim( \WebChangeDetector\WebChangeDetector_Admin_Utils::get_domain_from_site_url(), '/' ) ) ) {
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
				'auto_update_checks_enabled'   => '0',
				'auto_update_checks_from'      => gmdate( 'H:i' ),
				'auto_update_checks_to'        => gmdate( 'H:i', strtotime( '+12 hours' ) ),
				'auto_update_checks_monday'    => '1',
				'auto_update_checks_tuesday'   => '1',
				'auto_update_checks_wednesday' => '1',
				'auto_update_checks_thursday'  => '1',
				'auto_update_checks_friday'    => '1',
				'auto_update_checks_saturday'  => '0',
				'auto_update_checks_sunday'    => '0',
				'auto_update_checks_emails'    => get_option( 'admin_email' ),
			);
			$local_auto_update_settings              = get_option( WCD_AUTO_UPDATE_SETTINGS );
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
		
		// Ensure we have a valid website ID before making API call.
		if ( ! empty( $update_website_details['id'] ) ) {
			\WebChangeDetector\WebChangeDetector_API_V2::update_website_v2( $update_website_details['id'], $update_website_details );
		}
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
					<?php \WebChangeDetector\WebChangeDetector_Admin_Utils::get_device_icon( 'update-group' ); ?> Auto Update Checks & Manual Checks
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