<?php
/**
 * Change Detections Controller for WebChangeDetector
 *
 * Handles change detections page requests and logic.
 *
 * @package    WebChangeDetector
 * @subpackage WebChangeDetector/admin/controllers
 * @author     Mike Miler <mike@wp-mike.com>
 */

namespace WebChangeDetector;

/**
 * Change Detections Controller Class.
 */
class WebChangeDetector_Change_Detections_Controller {


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
	 * Handle change detections request.
	 */
	public function handle_request() {
		// Check permissions.
		if ( ! $this->admin->settings_handler->is_allowed( 'change_detections_view' ) ) {
			return;
		}

		$this->render_change_detections_page();
	}

	/**
	 * Parse and sanitize filter parameters from GET request.
	 *
	 * @return array|false Sanitized filter values or false on validation error.
	 */
	private function parse_filter_params() {
		// phpcs:disable WordPress.Security.NonceVerification.Recommended -- GET parameters for read-only filtering.
		$filters = array();

		// Date range (empty = all time).
		$filters['from'] = gmdate( 'Y-m-d', strtotime( '- 7 days' ) );
		if ( isset( $_GET['from'] ) ) {
			$filters['from'] = sanitize_text_field( wp_unslash( $_GET['from'] ) );
		}

		$filters['to'] = current_time( 'Y-m-d' );
		if ( isset( $_GET['to'] ) ) {
			$filters['to'] = sanitize_text_field( wp_unslash( $_GET['to'] ) );
		}

		// Source filter.
		$filters['source'] = '';
		if ( isset( $_GET['source'] ) ) {
			$filters['source'] = sanitize_text_field( wp_unslash( $_GET['source'] ) );
			if ( ! empty( $filters['source'] ) && ! in_array( $filters['source'], array( 'manual', 'monitoring', 'auto_update' ), true ) ) {
				echo '<div class="error notice"><p>' . esc_html__( 'Invalid source filter.', 'webchangedetector' ) . '</p></div>';
				return false;
			}
		}

		// Status (supports comma-separated string or array from multi-select).
		$filters['status'] = '';
		if ( isset( $_GET['status'] ) ) {
			// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Sanitized below per type.
			$raw_status = wp_unslash( $_GET['status'] );
			if ( is_array( $raw_status ) ) {
				$raw_status = implode( ',', array_map( 'sanitize_text_field', $raw_status ) );
			} else {
				$raw_status = sanitize_text_field( $raw_status );
			}
			if ( ! empty( $raw_status ) && ! empty( array_diff( explode( ',', $raw_status ), WebChangeDetector_Admin::VALID_COMPARISON_STATUS ) ) ) {
				echo '<div class="error notice"><p>' . esc_html__( 'Invalid status.', 'webchangedetector' ) . '</p></div>';
				return false;
			}
			$filters['status'] = $raw_status;
		}

		// Difference only.
		$filters['difference_only'] = '';
		if ( isset( $_GET['difference_only'] ) ) {
			$filters['difference_only'] = sanitize_text_field( wp_unslash( $_GET['difference_only'] ) );
		}

		// Group IDs (supports comma-separated string or array from multi-select).
		$filters['group_id'] = '';
		if ( isset( $_GET['group_id'] ) ) {
			// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Sanitized below per type.
			$raw_group_id = wp_unslash( $_GET['group_id'] );
			if ( is_array( $raw_group_id ) ) {
				$raw_group_id = implode( ',', array_map( 'sanitize_text_field', $raw_group_id ) );
			} else {
				$raw_group_id = sanitize_text_field( $raw_group_id );
			}
			$filters['group_id'] = $raw_group_id;
		}

		// URL IDs (supports comma-separated string or array from multi-select).
		$filters['urls'] = '';
		if ( isset( $_GET['urls'] ) ) {
			// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Sanitized below per type.
			$raw_urls = wp_unslash( $_GET['urls'] );
			if ( is_array( $raw_urls ) ) {
				$raw_urls = implode( ',', array_map( 'sanitize_text_field', $raw_urls ) );
			} else {
				$raw_urls = sanitize_text_field( $raw_urls );
			}
			$filters['urls'] = $raw_urls;
		}

		// View mode.
		$filters['view_mode'] = 'batch';
		if ( isset( $_GET['view_mode'] ) ) {
			$filters['view_mode'] = sanitize_text_field( wp_unslash( $_GET['view_mode'] ) );
			if ( ! in_array( $filters['view_mode'], array( 'batch', 'flat' ), true ) ) {
				$filters['view_mode'] = 'batch';
			}
		}

		// Pagination.
		$filters['paged'] = isset( $_GET['paged'] ) ? absint( wp_unslash( $_GET['paged'] ) ) : 1;

		// Batch ID (from auto-update history link).
		$filters['batch_id'] = '';
		if ( isset( $_GET['batch_id'] ) ) {
			$filters['batch_id'] = sanitize_text_field( wp_unslash( $_GET['batch_id'] ) );
		}
		// phpcs:enable WordPress.Security.NonceVerification.Recommended

		return $filters;
	}

	/**
	 * Build API filter parameters from parsed filters.
	 *
	 * @param array $filters Parsed filter values.
	 * @return array API filter parameters.
	 */
	private function build_api_filters( $filters ) {
		$api_filters = array(
			'page'     => $filters['paged'],
			'per_page' => 20,
		);

		if ( ! empty( $filters['from'] ) ) {
			$api_filters['from'] = gmdate( 'Y-m-d', strtotime( $filters['from'] ) );
		}
		if ( ! empty( $filters['to'] ) ) {
			$api_filters['to'] = gmdate( 'Y-m-d', strtotime( $filters['to'] ) );
		}

		if ( ! empty( $filters['source'] ) ) {
			$api_filters['source'] = $filters['source'];
		}

		if ( ! empty( $filters['status'] ) ) {
			$api_filters['status'] = $filters['status'];
		} else {
			$api_filters['status'] = 'new,ok,to_fix,false_positive';
		}

		if ( ! empty( $filters['difference_only'] ) ) {
			$api_filters['above_threshold'] = (bool) $filters['difference_only'];
		}

		if ( ! empty( $filters['group_id'] ) ) {
			$api_filters['groups'] = $filters['group_id'];
		}

		if ( ! empty( $filters['urls'] ) ) {
			$api_filters['urls'] = $filters['urls'];
		}

		return $api_filters;
	}

	/**
	 * Render change detections page.
	 */
	private function render_change_detections_page() {
		$filters = $this->parse_filter_params();
		if ( false === $filters ) {
			return;
		}

		$api_filters = $this->build_api_filters( $filters );

		// Fetch groups limited to plugin's own groups.
		$plugin_group_ids = get_option( WCD_WEBSITE_GROUPS );
		$groups_data      = array();
		if ( is_array( $plugin_group_ids ) ) {
			foreach ( $plugin_group_ids as $group_key => $group_uuid ) {
				if ( ! empty( $group_uuid ) ) {
					$group_label   = WCD_AUTO_DETECTION_GROUP === $group_key
						? __( 'Monitoring', 'webchangedetector' )
						: __( 'Manual Checks', 'webchangedetector' );
					$groups_data[] = array(
						'id'   => $group_uuid,
						'name' => $group_label,
					);
				}
			}
		}
		$selected_groups = ! empty( $filters['group_id'] ) ? explode( ',', $filters['group_id'] ) : array();

		// Fetch URLs scoped to plugin's groups.
		$urls_data     = array();
		$selected_urls = ! empty( $filters['urls'] ) ? explode( ',', $filters['urls'] ) : array();
		if ( is_array( $plugin_group_ids ) && ! empty( $plugin_group_ids ) ) {
			$url_filters   = array(
				'groups'   => implode( ',', array_values( $plugin_group_ids ) ),
				'per_page' => 100,
			);
			$urls_response = \WebChangeDetector\WebChangeDetector_API_V2::get_urls_v2( $url_filters );
			$urls_data     = ! empty( $urls_response['data'] ) ? $urls_response['data'] : array();
		}

		// Status options.
		$available_statuses = array(
			'new'            => __( 'New', 'webchangedetector' ),
			'ok'             => __( 'Ok', 'webchangedetector' ),
			'to_fix'         => __( 'To Fix', 'webchangedetector' ),
			'false_positive' => __( 'False Positive', 'webchangedetector' ),
		);
		$selected_statuses  = ! empty( $filters['status'] ) ? explode( ',', $filters['status'] ) : array();

		// Build filter query for pagination and view mode links.
		$filter_query_args = array(
			'page'            => 'webchangedetector-change-detections',
			'from'            => $filters['from'],
			'to'              => $filters['to'],
			'source'          => $filters['source'],
			'difference_only' => $filters['difference_only'],
		);
		if ( ! empty( $filters['status'] ) ) {
			$filter_query_args['status'] = $filters['status'];
		}
		if ( ! empty( $filters['group_id'] ) ) {
			$filter_query_args['group_id'] = $filters['group_id'];
		}
		if ( ! empty( $filters['urls'] ) ) {
			$filter_query_args['urls'] = $filters['urls'];
		}

		?>
		<div class="action-container wizard-change-detections">

			<!-- Filter Bar -->
			<div class="wcd-filters-card">
				<form id="wcd-filter-form" method="get">
					<input type="hidden" name="page" value="webchangedetector-change-detections">
					<input type="hidden" name="view_mode" value="<?php echo esc_attr( $filters['view_mode'] ); ?>">

					<div class="wcd-filters-layout">

						<!-- Left: Date Range with Calendar -->
						<div class="wcd-filters-date">
							<div class="wcd-filter-label">
								<span class="dashicons dashicons-calendar-alt"></span>
								<?php esc_html_e( 'Date Range', 'webchangedetector' ); ?>
							</div>
							<div class="wcd-date-presets">
								<button type="button" class="wcd-date-preset" data-days="7"><?php esc_html_e( '7 Days', 'webchangedetector' ); ?></button>
								<button type="button" class="wcd-date-preset" data-days="30"><?php esc_html_e( '30 Days', 'webchangedetector' ); ?></button>
								<button type="button" class="wcd-date-preset" data-days="90"><?php esc_html_e( '90 Days', 'webchangedetector' ); ?></button>
								<button type="button" class="wcd-date-preset" data-days="all"><?php esc_html_e( 'All Time', 'webchangedetector' ); ?></button>
							</div>
							<div id="wcd-daterange-inline"></div>
							<input type="hidden" name="from" id="wcd-date-from" value="<?php echo esc_attr( $filters['from'] ); ?>">
							<input type="hidden" name="to" id="wcd-date-to" value="<?php echo esc_attr( $filters['to'] ); ?>">
						</div>

						<!-- Right: Filters + Actions -->
						<div class="wcd-filters-options">

							<div class="wcd-filter-grid">
								<!-- Row 1: URLs + Status -->
								<?php if ( ! empty( $urls_data ) ) { ?>
								<div class="wcd-filter-item">
									<div class="wcd-filter-label">
										<span class="dashicons dashicons-admin-links"></span>
										<?php esc_html_e( 'URLs', 'webchangedetector' ); ?>
									</div>
									<div class="wcd-checkbox-dropdown" data-name="urls[]" data-placeholder="<?php esc_attr_e( 'All URLs', 'webchangedetector' ); ?>">
										<div class="wcd-checkbox-dropdown-trigger">
											<div class="wcd-checkbox-dropdown-tags"></div>
											<input type="text" class="wcd-checkbox-dropdown-search"
												placeholder="<?php esc_attr_e( 'All URLs', 'webchangedetector' ); ?>"
												autocomplete="off">
											<span class="dashicons dashicons-arrow-down-alt2"></span>
										</div>
										<div class="wcd-checkbox-dropdown-list">
											<?php
											foreach ( $urls_data as $url_item ) {
												$url_id  = $url_item['id'] ?? '';
												$url_val = $url_item['url'] ?? $url_id;
												$checked = in_array( $url_id, $selected_urls, true );
												?>
												<label class="wcd-checkbox-dropdown-option">
													<input type="checkbox" name="urls[]" value="<?php echo esc_attr( $url_id ); ?>" <?php checked( $checked ); ?>>
													<span><?php echo esc_html( $url_val ); ?></span>
												</label>
											<?php } ?>
										</div>
									</div>
								</div>
								<?php } ?>

								<div class="wcd-filter-item">
									<div class="wcd-filter-label">
										<span class="dashicons dashicons-flag"></span>
										<?php esc_html_e( 'Status', 'webchangedetector' ); ?>
									</div>
									<div class="wcd-checkbox-dropdown" data-name="status[]" data-placeholder="<?php esc_attr_e( 'All Status', 'webchangedetector' ); ?>">
										<div class="wcd-checkbox-dropdown-trigger">
											<div class="wcd-checkbox-dropdown-tags"></div>
											<input type="text" class="wcd-checkbox-dropdown-search"
												placeholder="<?php esc_attr_e( 'All Status', 'webchangedetector' ); ?>"
												autocomplete="off">
											<span class="dashicons dashicons-arrow-down-alt2"></span>
										</div>
										<div class="wcd-checkbox-dropdown-list">
											<?php
											foreach ( $available_statuses as $status_key => $status_label ) {
												$checked = in_array( $status_key, $selected_statuses, true );
												?>
												<label class="wcd-checkbox-dropdown-option">
													<input type="checkbox" name="status[]" value="<?php echo esc_attr( $status_key ); ?>" <?php checked( $checked ); ?>>
													<span><?php echo esc_html( $status_label ); ?></span>
												</label>
											<?php } ?>
										</div>
									</div>
								</div>

								<!-- Row 2: Visual Changes + Type -->
								<div class="wcd-filter-item">
									<div class="wcd-filter-label">
										<span class="dashicons dashicons-visibility"></span>
										<?php esc_html_e( 'Visual Changes', 'webchangedetector' ); ?>
									</div>
									<select name="difference_only" class="wcd-filter-select">
										<option value="0" <?php echo empty( $filters['difference_only'] ) || '0' === $filters['difference_only'] ? 'selected' : ''; ?>><?php esc_html_e( 'All detections', 'webchangedetector' ); ?></option>
										<option value="1" <?php selected( $filters['difference_only'], '1' ); ?>><?php esc_html_e( 'With changes only', 'webchangedetector' ); ?></option>
									</select>
								</div>

								<div class="wcd-filter-item">
									<div class="wcd-filter-label">
										<span class="dashicons dashicons-category"></span>
										<?php esc_html_e( 'Type', 'webchangedetector' ); ?>
									</div>
									<select name="source" class="wcd-filter-select">
										<option value="" <?php selected( $filters['source'], '' ); ?>><?php esc_html_e( 'All Checks', 'webchangedetector' ); ?></option>
										<option value="manual" <?php selected( $filters['source'], 'manual' ); ?>><?php esc_html_e( 'Manual Checks', 'webchangedetector' ); ?></option>
										<option value="monitoring" <?php selected( $filters['source'], 'monitoring' ); ?>><?php esc_html_e( 'Monitoring', 'webchangedetector' ); ?></option>
										<option value="auto_update" <?php selected( $filters['source'], 'auto_update' ); ?>><?php esc_html_e( 'Auto-Update Checks', 'webchangedetector' ); ?></option>
									</select>
								</div>
							</div>

							<!-- Row 3: Actions -->
							<div class="wcd-filter-actions">
								<button type="submit" class="button button-primary"><?php esc_html_e( 'Filter', 'webchangedetector' ); ?></button>
								<a href="?page=webchangedetector-change-detections" class="button"><?php esc_html_e( 'Reset', 'webchangedetector' ); ?></a>
							</div>

						</div>

					</div>
				</form>
			</div>

			<?php
			// Batch-specific view (from auto-update history link).
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- GET parameter for filtering only.
			if ( ! empty( $filters['batch_id'] ) ) {
				$this->render_single_batch_view( $filters );
				return;
			}

			// View mode toggle.
			$batch_url = add_query_arg( array_merge( $filter_query_args, array( 'view_mode' => 'batch' ) ), admin_url( 'admin.php' ) );
			$flat_url  = add_query_arg( array_merge( $filter_query_args, array( 'view_mode' => 'flat' ) ), admin_url( 'admin.php' ) );
			?>
			<div class="wcd-view-mode-toggle">
				<a href="<?php echo esc_url( $batch_url ); ?>" class="wcd-view-mode-btn <?php echo 'batch' === $filters['view_mode'] ? 'active' : ''; ?>">
					<span class="dashicons dashicons-list-view"></span>
					<?php esc_html_e( 'Batch View', 'webchangedetector' ); ?>
				</a>
				<a href="<?php echo esc_url( $flat_url ); ?>" class="wcd-view-mode-btn <?php echo 'flat' === $filters['view_mode'] ? 'active' : ''; ?>">
					<span class="dashicons dashicons-editor-ul"></span>
					<?php esc_html_e( 'List View', 'webchangedetector' ); ?>
				</a>
			</div>

			<?php
			if ( 'flat' === $filters['view_mode'] ) {
				$this->render_flat_view( $api_filters, $filter_query_args );
			} else {
				$this->render_batch_view( $api_filters, $filter_query_args );
			}
			?>
		</div>
		<div class="clear"></div>
		<?php
	}

	/**
	 * Render the batch (accordion) view.
	 *
	 * @param array $api_filters      API filter parameters.
	 * @param array $filter_query_args Query args for pagination links.
	 */
	private function render_batch_view( $api_filters, $filter_query_args ) {
		$batches = \WebChangeDetector\WebChangeDetector_API_V2::get_batches_v2( $api_filters );

		$this->admin->dashboard_handler->compare_view_v2( $batches['data'] ?? array() );

		// Pagination.
		if ( ! empty( $batches['meta']['links'] ) ) {
			$this->render_pagination( $batches['meta'], $filter_query_args, 'batch' );
		}
	}

	/**
	 * Render the flat (list) view.
	 *
	 * @param array $api_filters      API filter parameters.
	 * @param array $filter_query_args Query args for pagination links.
	 */
	private function render_flat_view( $api_filters, $filter_query_args ) {
		$flat_filters = array(
			'page'           => $api_filters['page'],
			'per_page'       => $api_filters['per_page'],
			'from'           => $api_filters['from'],
			'to'             => $api_filters['to'],
			'orderBy'        => 'created_at',
			'orderDirection' => 'desc',
		);

		// Copy relevant filters.
		foreach ( array( 'source', 'status', 'above_threshold', 'groups' ) as $key ) {
			if ( isset( $api_filters[ $key ] ) ) {
				$flat_filters[ $key ] = $api_filters[ $key ];
			}
		}

		$comparisons = \WebChangeDetector\WebChangeDetector_API_V2::get_comparisons_v2( $flat_filters );

		if ( empty( $comparisons['data'] ) ) {
			?>
			<div class="wcd-empty-state">
				<strong><?php esc_html_e( 'No Change Detections (yet)', 'webchangedetector' ); ?></strong>
				<p><?php esc_html_e( 'Start monitoring webpages or start Manual Checks. Try different filters if there should be Change Detections.', 'webchangedetector' ); ?></p>
			</div>
			<?php
			return;
		}

		$this->admin->dashboard_handler->load_flat_comparisons_view( $comparisons );

		// Pagination.
		if ( ! empty( $comparisons['meta']['links'] ) ) {
			$this->render_pagination( $comparisons['meta'], $filter_query_args, 'flat' );
		}
	}

	/**
	 * Render a single batch view (from auto-update history link).
	 *
	 * @param array $filters Parsed filter values.
	 */
	private function render_single_batch_view( $filters ) {
		$batch_id     = $filters['batch_id'];
		$single_batch = \WebChangeDetector\WebChangeDetector_API_V2::get_batch_v2( $batch_id );

		if ( ! $single_batch ) {
			echo '<div class="notice notice-error"><p>' . esc_html__( 'Batch not found.', 'webchangedetector' ) . '</p></div>';
			return;
		}

		$batches = array( $single_batch['data'] );

		// Show notice if this is from auto-update history.
		$auto_update_batches = get_option( WCD_AUTO_UPDATE_COMPARISON_BATCHES );
		if ( is_array( $auto_update_batches ) && in_array( $batch_id, $auto_update_batches, true ) ) {
			echo '<div class="notice notice-info"><p>';
			echo esc_html__( 'Viewing Auto-Update Check visual comparisons. ', 'webchangedetector' );
			echo '<a href="?page=webchangedetector-logs&tab=auto-updates">' . esc_html__( '← Back to Auto-Update History', 'webchangedetector' ) . '</a>';
			echo '</p></div>';
		}

		$this->admin->dashboard_handler->compare_view_v2( $batches );
		echo '</div><div class="clear"></div>';
	}

	/**
	 * Render pagination links.
	 *
	 * @param array  $meta             Pagination meta from API.
	 * @param array  $filter_query_args Base query args for links.
	 * @param string $view_mode        Current view mode.
	 */
	private function render_pagination( $meta, $filter_query_args, $view_mode ) {
		$filter_query_args['view_mode'] = $view_mode;
		?>
		<div class="tablenav">
			<div class="tablenav-pages">
				<span class="displaying-num"><?php echo esc_html( $meta['total'] ?? 0 ); ?> <?php esc_html_e( 'items', 'webchangedetector' ); ?></span>
				<span class="pagination-links">
					<?php
					foreach ( $meta['links'] as $link ) {
						$page_num = \WebChangeDetector\WebChangeDetector_Admin_Utils::get_params_of_url( $link['url'] )['page'] ?? '';
						$class    = ! $link['url'] || $link['active'] ? 'disabled' : '';
						$href     = add_query_arg( array_merge( $filter_query_args, array( 'paged' => $page_num ) ), admin_url( 'admin.php' ) );
						?>
						<a class="tablenav-pages-navspan button <?php echo esc_attr( $class ); ?>"
							href="<?php echo esc_url( $href ); ?>">
							<?php echo esc_html( $link['label'] ); ?>
						</a>
						<?php
					}
					?>
				</span>
			</div>
		</div>
		<?php
	}
}
