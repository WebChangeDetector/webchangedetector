<?php
/**
 * View Generation and HTML Output for WebChange Detector
 *
 * This class handles all view generation functionality including URL settings,
 * group views, and other HTML output components.
 *
 * @link       https://www.webchangedetector.com
 * @since      1.0.0
 *
 * @package    WebChangeDetector
 * @subpackage WebChangeDetector/admin
 */

namespace WebChangeDetector;

/**
 * View generation and HTML output functionality.
 *
 * Defines all methods for generating HTML views, forms, and UI components
 * for the WebChange Detector admin interface.
 *
 * @since      1.0.0
 * @package    WebChangeDetector
 * @subpackage WebChangeDetector/admin
 * @author     Mike Miler <mike@wp-mike.com>
 */
class WebChangeDetector_Admin_Views {

	/**
	 * Generate URL settings view with search, pagination, and controls.
	 * Migrated from legacy class-wp-compare.php.
	 *
	 * @param array $group Group data with URLs and metadata.
	 * @return void Outputs HTML directly.
	 */
	public static function get_url_settings( $group ) {
		if ( ! empty( $group['urls'] ) || !empty($_POST['search'])) {

			// Check top checkbox if ALL checkboxes are checked
			$checkedAllDesktop = array_reduce(
				$group['urls'],
				function ( $carry, $url ) {
					return $carry += $url['desktop'];
				}
			) === count( $group['urls'] ) ? 'checked' : '';
			$checkedAllMobile  = array_reduce(
				$group['urls'],
				function ( $carry, $url ) {
					return $carry += $url['mobile'];
				}
			) === count( $group['urls'] ) ? 'checked' : '';

		        // Search for urls.
                ?>
            <div class="responsive-table">
                <form class="ajax search-url-table" >
                    <input type="hidden" name="action" value="load_group_urls">
                    <input type="hidden" name="group_id" value="<?= $group['id'] ?>">
                    <input type="hidden" name="action" value="load_group_urls">
                    <input name="search" type="text" placeholder="Search" value="<?= $_POST['search'] ?? '' ?>">
                </form>
                <table id="table_group_<?= $group['id']; ?>">
			<?php
			if ( count( $group['urls'] ) >= 1 ) { ?>
				<div class="enabled_switch devices" >
                        <label class="wcd-modern-switch">
                            <input type="checkbox"
                                <?= $checkedAllDesktop ?>
                                class="ajax-select-all"
                                data-device="desktop"
                                data-group_id="<?= $group['id'] ?>"
                                id="select-desktop-<?= $group['id'] ?>" />
                            <span class="wcd-modern-slider" style="font-size: 12px; line-height: 12px; text-align: center;">
                                All<br>Desktop
                            </span>
                         </label>
                    </div>
                    <div class="enabled_switch devices" >
                        <label class="wcd-modern-switch">
                            <input type="checkbox"
                                <?= $checkedAllMobile ?>
					            class="ajax-select-all"
                                data-device="mobile"
                                data-group_id="<?= $group['id'] ?>"
                                id="select-mobile-<?= $group['id'] ?>" />
                            <span class="wcd-modern-slider" style="font-size: 12px; line-height: 12px;  text-align: center;">
                                All<br>Mobile
                            </span>
                        </label>
                    </div>
					<div class="clear"></div>
                <?php
			}


			// Get the urls to show. We have this in a separate function as it is called with ajax for load more urls
			echo self::get_url_view( $group );


			?>
			</table>
			</div>

			<div class='pagination_container' style='margin-top: 30px;'>
            <?php
			foreach ($group['meta']['links'] as $link) {
				if (!empty($link['active'])) {
					$activeLabel = $link['label'];
					break;
				}
			}

			foreach ( $group['meta']['links'] as $link ) {
				// Parse the URL to get the query part
				$parsedUrl = parse_url($link['url'], PHP_URL_QUERY);

                // Parse the query string into an array
				parse_str($parsedUrl, $queryParams);

                // Get the 'page' parameter
				$page = isset($queryParams['page']) ? $queryParams['page'] : null;

				?>
					<button class="ajax_paginate_urls et_pb_button"
							style="padding-left: 15px !important; padding-right: 15px !important;"
							data-group_id="<?php echo $group['id']; ?>"
							data-page="<?php echo $page; ?>"
							<?php echo $link['active'] || is_null($page) ? ' disabled' : ''; ?>
							onclick="return false;"
					>
						<?php echo $link['label']; ?>
					</button>
				<?php

			}
            echo "Total: " . $group['meta']['total'] . " items";
			echo '</div>';
		} else {
			?>
			<div style="text-align: center; display: block;margin-top: 50px; margin-bottom: 50px">
				<p class="add-url">Add Webpage</p>
				<div class="ajax"
					data-group_id="<?php echo $group['id']; ?>"
					onclick="showAssignGroupUrlPopup('<?php echo $group['id']; ?>');">

					<?php echo get_device_icon( 'add-url', 'icon-big' ); ?>
				</div>
			</div>
			<?php
		}
	}

	/**
	 * Generate URL view with monitoring status and device selection.
	 * Migrated from legacy class-wp-compare.php.
	 *
	 * @param array $group Group data with URLs.
	 * @return string HTML output for URL view.
	 */
	public static function get_url_view( $group ) {
		$output = '';

        /* Disabled as this request just take way to long
        $comp_args = [
                'group_id' => $group['id'],
                'from' => date("Y-m-d", strtotime("-30 days")),
                'to' => date("Y-m-d", strtotime("+1 day")),
                'above_threshold' => true,
                'per_page' => 1000
                ];
        $comparisons = Wp_Compare_API_V2::get_comparisons_v2($comp_args)['data'] ?? null;
*/
        if(count($group['urls']) <= 0 ) {
            echo "<h2 style='text-align: center;'>No URLs to show</h2>";
            if(!empty($_POST['search'])) { ?>
                <p style="text-align: center;">
                    <button class="et_pb_button"
                            onclick='reset_search(); return false;'>
                        Reset search
                    </button>
                </p>
                <script>
                    function reset_search() {
                        let searchForm = jQuery("#ajax-group-urls_<?= $group['id'] ?>").find("form.search-url-table");
                        jQuery(searchForm).find("input[name='search']").val('');
                        searchForm.submit()
                    }
                </script>
            <?php }
        }

		foreach ( $group['urls'] as $key => $url_details ) {
			$timeAgoDefaultText      = 'None in last 30 days';
			$timeLastChangeDetection = $timeAgoDefaultText;
            $compare_token = false;

			/* Disabled for performance reasons.
			 foreach ( $comparisons as $compare ) {
				if ( $compare['url_id'] == $url_details['id'] && $timeLastChangeDetection == $timeAgoDefaultText ) {
					$compare_token           = $compare['token'];
					$timeLastChangeDetection = strtotime( $compare['screenshot_2_updated_at'] );
				}
			}*/

			// Check Desktop
			if ( isset( $url_details['desktop'] ) && $url_details['desktop'] == 1 ) {
				$checked_desktop = 'checked';
			} else {
				$checked_desktop = '';
			}

			// Check Mobile
			if ( isset( $url_details['mobile'] ) && $url_details['mobile'] == 1 ) {
				$checked_mobile = 'checked';
			} else {
				$checked_mobile = '';
			}

			$output .= '<tr data-url_id="' . $url_details['id'] . '" data-group_id="' . $group['id'] . '" class="post_id_' . $group['id'] . ' live-filter-row" id="' . $group['id'] . '-' . $url_details['id'] . '">';
			$output .= '<input type="hidden"  type="checkbox" name="pid-' . $url_details['id'] . '" value="' . $url_details['id'] . '">';

			// Monitoring animation
			if ( $group['monitoring'] ) {
				$output .= '<td style="text-align: center; width: 120px; order: 2" class="url-monitoring-status-container">
                                <div class="animation-enabled"></div>
                                <div class="monitoring-status" style="line-height: 1.0em;"></div>
                            </td>';
			}

			// URL title/name
			$output .= '<td style="order: 1; width: 35%">';
			$output .= '<a href="' . $url_details['url'] . '" target="_blank">' . $url_details['url'] . '</a>';
			$output .= '</td>';

			// Desktop checkbox
			$output .= '<td style="order: 3; text-align: center">';
			$output .= '<div class="enabled_switch">';
			$output .= '<label class="wcd-modern-switch">';
			$output .= '<input type="checkbox" ' . $checked_desktop . ' class="ajax-select-url" data-device="desktop" data-url_id="' . $url_details['id'] . '" data-group_id="' . $group['id'] . '" id="desktop-' . $url_details['id'] . '" />';
			$output .= '<span class="wcd-modern-slider"></span>';
			$output .= '</label>';
			$output .= '</div>';
			$output .= '</td>';

			// Mobile checkbox
			$output .= '<td style="order: 4; text-align: center">';
			$output .= '<div class="enabled_switch">';
			$output .= '<label class="wcd-modern-switch">';
			$output .= '<input type="checkbox" ' . $checked_mobile . ' class="ajax-select-url" data-device="mobile" data-url_id="' . $url_details['id'] . '" data-group_id="' . $group['id'] . '" id="mobile-' . $url_details['id'] . '" />';
			$output .= '<span class="wcd-modern-slider"></span>';
			$output .= '</label>';
			$output .= '</div>';
			$output .= '</td>';

			// Actions
			$output .= '<td style="order: 5; text-align: center">';
			$output .= '<div class="ajax" style="background: red; padding: 10px; border-radius: 5px; color: white; cursor: pointer;" data-url_id="' . $url_details['id'] . '" onclick="delete_url(' . $url_details['id'] . ')">Delete</div>';
			$output .= '</td>';

			$output .= '</tr>';
		}

		return $output;
	}

	/**
	 * Get the account view with usage statistics and plan details.
	 *
	 * @since    1.0.0
	 * @param    array $account_details Account details from API.
	 * @return   string|false           The account view HTML or false on failure.
	 */
	public static function get_account_view( $account_details ) {
		// Could be null.
		if ( ! is_array( $account_details ) ) {
			// Fail gracefully.
			return false;
		}
		$available_percent = $account_details['checks_left'] / $account_details['checks_limit'] * 100;
		$available_class   = '';
		if ( $available_percent <= 10 ) {
			$available_class = 'low-credit';
		}
		if ( $account_details['checks_left'] <= 0 ) {
			$available_class = 'no-credit';
		}

		$renewal_label = 'Next Renew:';

		return '<div class="side_nav_content">
                <h3>Your Account</h3>
                <p class="' . $available_class . ' available-screenshots"><strong>Available Checks:</strong><br>
                <span id="available-credits" data-available_sc="' . $account_details['checks_left'] . '" >' . $account_details['checks_left'] . '</span> / ' . $account_details['checks_limit'] . '</strong></p>
                <p id="current-plan" data-plan_id="' . $account_details['plan'] . '" ><strong>Current Plan:</strong><br> ' . $account_details['plan_name'] . '</p>
                <p><strong>' . $renewal_label . '</strong><br>
                    <span class="local-date" data-date="' . strtotime( $account_details['renewal_at'] ) . '">
                    ' . gmdate( 'd/m/Y', strtotime( $account_details['renewal_at'] ) ) . '
                    </span>
                </p>
                
                </div>';
	}

	/**
	 * Get the comparison view with filters and comparison results.
	 *
	 * @since    1.0.0
	 * @param    array $postdata      Request data with filters and pagination.
	 * @param    bool  $show_filters  Whether to show filter UI (default: true).
	 * @return   void
	 */
	public static function get_compares_view( $postdata, $show_filters = true ) {

        // Get batches.
        $filter_batches = array(
            'page'     => $postdata['pagination'] ?? 1,
            'per_page' => 20,
        );
        if ( ! empty( $postdata['from'] ) ) {
            $filter_batches['from'] = gmdate( 'Y-m-d', strtotime( $postdata['from'] ) );
        }
        if ( ! empty( $postdata['to'] ) ) {
            $filter_batches['to'] = gmdate( 'Y-m-d', strtotime( $postdata['to'] ) );
        }

        if ( ! empty( $postdata['group_type'] ) ) {
            $extra_filters['queue_type'] = $postdata['group_type'];
        } else {
            $extra_filters['queue_type'] = 'post,auto';
        }

        if ( ! empty( $postdata['status'] ) ) {
            $extra_filters['status'] = $postdata['status'];
        } else {
            $extra_filters['status'] = 'new,ok,to_fix,false_positive';
        }

        if ( ! empty( $postdata['difference_only'] ) ) {
            $extra_filters['above_threshold'] = (bool) $postdata['difference_only'];
        }

        if ( ! empty( $postdata['group_id'] ) ) {
            $extra_filters['group_ids'] = $postdata['group_id'];
        }

		$filter_batches_in_comparisons = array();
		if(!empty($postdata['batch_id'])) {
			$filter_batches_in_comparisons[] = $postdata['batch_id'];
            $batch =  \WebChangeDetector\Wp_Compare_API_V2::get_batch( $postdata['batch_id']);
            $batches['data'][0] = $batch['data']; // Prepare for foreach-loop.
		} else {
	        $batches = \WebChangeDetector\Wp_Compare_API_V2::get_batches( array_merge( $filter_batches, $extra_filters ) );

	        foreach ( $batches['data'] as $batch ) {
		        $filter_batches_in_comparisons[] = $batch['id'];
	        }
        }

		$filters = array(
			'from'      => $postdata['from'] ?? null,
			'to' => $postdata['to'] ?? null,
			'status'          => $postdata['status'] ?? '',
			'group_id'        => $postdata['group_id'] ?? '',
			'difference_only' => $postdata['difference_only'] ?? false,
			'group_type'      => $postdata['group_type'] ?? '',
			//'limit_domain'    => $postdata['limit_domain'] ?? '',
		);

		if ( isset( $postdata['show_filters'] ) && ! (int) $postdata['show_filters'] ) {
			$show_filters = false;
		}

		if ( isset( $postdata['latest_batch'] ) ) {
			$filters['latest_batch'] = $postdata['latest_batch'];
		}

		if ( isset( $postdata['limit_domain'] ) ) {
			$filters['limit_domain'] = $postdata['limit_domain'];
		}

		// Show Change detection by get parameter.
		if ( isset( $_GET['show-change-detection-token'] ) ) {
			$token = $_GET['show-change-detection-token'];
			echo '<script>
                    jQuery(document).ready(function() {
                        ajaxShowChangeDetectionPopup("' . $token . '");
                    });
                </script>';
		}

		?>

		<div class="action_container wcd-modern-dashboard">
			<div class="latest_compares_content">
		    <?php if($show_filters ) { ?>
			<div class="wcd-card wcd-filters-card">
				<div class="wcd-card-header">
					<h3>
						<span class="dashicons dashicons-filter" style="color: #266ECC;"></span>
						Filter Change Detections
					</h3>
				</div>
				<div class="wcd-card-content">
                	<form id="form-filter-change-detections" method="post" class="ajax-filter wcd-filter-form" style="overflow: visible; margin-bottom: 30px; " >
                        <div class="filter-row" style="margin: 0;">
                        <input type="hidden" name="action" value="filter_change_detections">
                        <input type="hidden" name="pagination" value="1">
                        <div class="change_detection_amount" style="display: none; "><?php echo $compares['meta']['total'] ?? '0'; ?> Change Detections</div>

                        <div class="filter-dropdowns wcd-filter-grid">
                            <div class="dropdown-container from wcd-filter-item">
                                <div class="change-detection-filter-label wcd-filter-label">
									<span class="dashicons dashicons-calendar-alt"></span>
									From
								</div>
                                <input type="date" name="from" class="js-dropdown-style wcd-filter-input" value="<?= $filters['from'] ?? gmdate( 'Y-m-d', strtotime( '- 30 days' ) ); ?>">
                            </div>

                            <div class="dropdown-container to wcd-filter-item">
								<div class="change-detection-filter-label wcd-filter-label">
									<span class="dashicons dashicons-calendar-alt"></span>
									To
								</div>
                                <input type="date" name="to" class="js-dropdown-style wcd-filter-input" value="<?= $filters['to'] ?? gmdate( 'Y-m-d' ); ?>">
                            </div>

                            <div class="dropdown-container difference_only wcd-filter-item">
								<div class="change-detection-filter-label wcd-filter-label">
									<span class="dashicons dashicons-search"></span>
									Changes
								</div>
                                <select name="difference_only" class="js-dropdown-style wcd-filter-input">
                                    <option value="0" <?php echo empty( $filters['difference_only'] ) ? 'selected' : ''; ?>>All detections</option>
                                    <option value="1" <?php echo $filters['difference_only'] ? 'selected' : ''; ?>>With changes only</option>
                                </select>
                            </div>

                            <div class="dropdown-container group_type wcd-filter-item">
								<div class="change-detection-filter-label wcd-filter-label">
									<span class="dashicons dashicons-category"></span>
									Type
								</div>
                                <select name="group_type" class="js-dropdown-style wcd-filter-input">
                                    <option value="" <?php echo ! $filters['group_type'] ? 'selected' : ''; ?>>All types</option>
                                    <option value="post" <?php echo $filters['group_type'] == 'post' ? 'selected' : ''; ?>>Manual Checks</option>
                                    <option value="auto" <?php echo $filters['group_type'] == 'auto' ? 'selected' : ''; ?>>Monitoring</option>
                                </select>
                            </div>
                            <?php

                            $availableStatus = array(
                                'none'           => 'None',
                                'new'            => 'New',
                                'ok'             => 'Ok',
                                'to_fix'         => 'To fix',
                                'false_positive' => 'False positive',
                            );
                            ?>
                            <div class="dropdown-container status wcd-filter-item">
								<div class="change-detection-filter-label wcd-filter-label">
									<span class="dashicons dashicons-flag"></span>
									Status
								</div>
                                <select name="status" class="js-dropdown-style wcd-filter-input">
                                    <option value="" <?php echo ! $filters['status'] ? 'selected' : ''; ?>>All status</option>
                                    <?php foreach ( $availableStatus as $statusKey => $statusName ) { ?>
                                        <option value="<?php echo $statusKey; ?>" <?php echo $filters['status'] == $statusKey ? 'selected' : ''; ?>><?php echo $statusName; ?></option>
                                    <?php } ?>
                                </select>
                            </div>
                            
                            <div class="dropdown-container submit wcd-filter-actions">
                                <input type="submit" class="et_pb_button" value="Filter" style="margin-top: 0;">
                                <a href="?tab=change-detections" class="wcd-reset-link">Reset Filter</a>
                            </div>
                            <div class="clear"></div>
                        </div>
                        </div>
                	</form>
				</div>
			</div>
			<?php
		} ?>


        <div id="change-detection-batches">
            <div class="responsive-table">
            <?php

			if(empty($batches['data'])) {
				?>
				<div class="box-plain bg" style="text-align: center; padding: 0px 0; margin-top: 20px; color: #333;">
					<strong>No Change Detections (yet)</strong>
					<p>
						Start monitoring webpages or start Manual Checks or try different filters if there should be Change Detections.<br>
					</p>
				</div>
				<?php
			} else {

            foreach($batches['data'] as $batch) {
                
				// Set sort filters.
				// TODO Make this easier to handle.
				$extra_filters['orderBy'] = 'difference_percent';
				$filters['orderBy'] = 'difference_percent';
				$extra_filters['orderDirection'] = 'desc';
				$filters['orderDirection'] = 'desc';

                

                // We don't have any change detections to show.
                if(empty($batch)) {
                    if ( isset( $filters['latest_batch'] ) ) {
                        ?>
                        <div style="text-align: center; margin-top: 50px;">
                            <div class="wcd-highlight-bg done">
                                <div><?php echo get_device_icon( 'check', 'icon-big' ); ?></div>
                                <h3>All Good</h3>
                                <p>No changes were detected. <br><a href="?tab=change-detections">Check all change detections</a></p>
                            </div>
                        </div>
                    <?php } else { ?>
                        <div class="website-settings">
                            <div class="box-plain bg" style="text-align: center; padding: 50px 0; margin-top: 20px; color: #333">
                                <h3>No Change Detections yet</h3>
                                <p>
                                    Start monitoring webpages or start Manual Checks. Change Detections will appear here.<br>
                                    Try different filters if there should be Change Detections.
                                </p>

                            </div>
                        </div>
                    <?php }
                } else {

				$amount_failed = $batch['queues_count']['failed'] ?? 0;
                
                ?>
                <div class="accordion-container" data-batch_id="<?php echo $batch['id']; ?>" data-failed_count="<?php echo $amount_failed; ?>">
                    <div class="accordion">
                        <div class="mm_accordion_title">
							
                            <h3 style="position: relative; text-align: center" class="ajax_comparisons_container">
                                <div class="accordion-state-icon-position" style="">
                                    <span class="accordion-state-icon dashicons dashicons-arrow-right-alt2" ></span>
                                </div>
                                <div class="box fourth center status">
                                    <div class="status_container">
                                        <small>Status</small>
                                        <strong>
                                            <div class="status_buttons">
                                            <?php
                                            
                                            foreach ( $batch['comparisons_count'] as $singleStatus => $amountStatus ) {
                                                if($amountStatus > 0 && $singleStatus !== 'above_threshold') {
                                                    echo \prettyPrintComparisonStatus( $singleStatus, 'mm_inline_block mm_small_status', $amountStatus ) . '<br>';
                                                }
                                            }

											// Show browser console errors (only for supported plans).
											$user_account = \WebChangeDetector\WebChangeDetector_Admin_Utils::get_account_details_v2();
											$user_plan = $user_account['plan'] ?? 'free';
											$canAccessBrowserConsole = \wcd_can_access_feature('browser_console', $user_plan);
											
											if($canAccessBrowserConsole && !empty($_COOKIE['show_browser_console_errors']) && $_COOKIE['show_browser_console_errors'] == 'true') {
												if(!empty($batch['browser_console_count']['added'] || !empty($batch['browser_console_count']['mixed']))) {
													echo '<span style="color: darkred; font-size: 14px; font-weight: 700;">New browser console errors: ' . $batch['browser_console_count']['added'] + $batch['browser_console_count']['mixed'] . '</span><br>';
												}
											}

											// Show failed checks.
                                            if($amount_failed) {
                                                echo "<span style='color: darkred; font-size: 14px; font-weight: 700;'>{$amount_failed} " . ($amount_failed > 1 ? "checks" : "check") . " failed</span>";
                                            }

                                            ?>
                                            </div>
                                        </strong>
                                    </div>
                                </div>
                                <div class="box fourth">
                                    <small>Change Detection</small>
                                    <strong><?php echo $batch['name']; ?></strong>
                                </div>
                                <div class="box fourth">
                                    <small>Created </small>
                                    <div class="big">
                                        <strong>
                                            <?php
                                            if($batch['finished_at']) {
                                                echo \WebChangeDetector\WebChangeDetector_Admin_Utils::timeAgo( $batch['finished_at'] ) . " <br>";
                                                echo "<span class='local-time' data-date='" .  date( 'U', strtotime( $batch['finished_at'] ) ) . "'>" .
                                                    date( 'd/m/Y H:i', strtotime( $batch['finished_at'] ) ) .
                                                '</span>';
                                            } else {
                                                echo "Processing";
                                            }
                                            ?>
                                        </strong>
                                    </div>
                                </div>
                                <div class="box fourth">
                                    <small>Change(s) found</small>
                                    <strong> On <?php echo $batch['comparisons_count']['above_threshold']; ?>  checks <br><?php echo ($batch['comparisons_count']['new'] + $batch['comparisons_count']['ok'] + $batch['comparisons_count']['to_fix'] + $batch['comparisons_count']['false_positive']); ?> URLs checked</strong>
                                </div>
                                <div class="clear"></div>
                            </h3>

                            <div class='mm_accordion_content no-padding'>
								<div class="ajax_batch_comparisons_content">
									<div class="ajax-loading-container">
										<img decoding="async" src="/wp-content/plugins/app/public/img/loading.gif">
										<div style="text-align: center;">Loading</div>
									</div>
								</div>
                                <?php
                                // We call this in an extra function to be able to use it for pagination.
                                //$this->load_comparisons_view($batch['id'], $compares, $filters);
                                ?>
                            </div>
                        </div>
                    </div>
                </div>
            <?php }
            }
		}

        ?>
            </div>
        </div>
        </div>
        </div>

		<?php
	}

	/**
	 * Get the action view - includes auto-update settings partial.
	 *
	 * @since    1.0.0
	 * @param    array $groups_and_urls  Group and URL data.
	 * @param    array $filters          Filters for the view.
	 * @param    bool  $update_view      Whether this is for update view (default: true).
	 * @return   void
	 */
	public static function get_action_view( $groups_and_urls, $filters, $update_view = true ) {
		include wcd_get_plugin_dir() . '/public/partials/auto-update-settings.php';
	}

	/**
	 * Print websites list with configuration forms.
	 *
	 * @since    1.0.0
	 * @param    array      $websites_obj         Website data object from API.
	 * @param    string|null $subaccount_api_token Optional subaccount API token.
	 * @return   void
	 */
	public static function print_websites( $websites_obj, $subaccount_api_token = null ) {
        if(empty($websites_obj['data'])) {
            echo '<div style="text-align: center; display: block;margin-top: 50px; margin-bottom: 50px">
				<p class="add-url">There are no WordPress Websites yet</p>
			</div>';
            return;
        }
        $websites = $websites_obj['data'];
        $websites_meta = $websites_obj['meta'];

        foreach ( $websites as $website ) {
            $website_name = empty( $website['domain'] ) ? 'Default settings for newly added websites' : $website['domain'];
            $class        = empty( $website['domain'] ) ? 'mm_default_website_settings' : '';
            ?>
            <div class="accordion-container">
                <div class="accordion">
                    <div class="mm_accordion_title <?php echo $class; ?>" >
                        <h3 style="position: relative">
                            <div class="accordion-state-icon-position" >
                                <span class="accordion-state-icon dashicons dashicons-arrow-right-alt2" ></span>
                            </div>
                            <div style="display: inline-block">
                                <div style="margin-left: 30px;">
                                    <?php if ( ! empty( $website['domain'] ) ) { ?>
                                        <img src="http://www.google.com/s2/favicons?domain=<?php echo $website_name; ?>">
                                        <?php
                                    }
                                    echo $website_name;
                                    ?>
                                </div>
                            </div>
                        </h3>
                        <div class='mm_accordion_content'>
                            <form method='post' class='ajax website-form' onsubmit='return false'>
                                <div class="wcd-website-settings-column">
                                    <input type="hidden" name="action" value="save_user_website">
                                    <input type="hidden" name="api_token" value="<?php echo $subaccount_api_token ?>">
                                    <input type="hidden" name="id" value="<?php echo $website['id']; ?>">
                                    <input type="hidden" name="domain" value="<?php echo $website['domain'] ?? 'Default settings for newly added websites'; ?>">

                                    <h4 class="website-setting" style=" margin-bottom: 10px;">
                                        Tabs in WP Plugin<br><small>Select which tabs should be enabled at the WP website</small>
                                    </h4>
                                    <label class="website-setting">
                                        <input type="hidden" name="allowances_manual_checks_view" value="0">
                                        <input type="checkbox" name='allowances_manual_checks_view' class="website-setting" value="1"
                                            <?php echo ! empty( $website['allowances']['manual_checks_view'] ) ? 'checked' : ''; ?>>
                                        Manual checks view
                                    </label>

                                    <label class="website-setting">
                                        <input type="hidden" name="allowances_monitoring_checks_view" value="0">
                                        <input type="checkbox" name='allowances_monitoring_checks_view' class="website-setting" value="1"
                                            <?php echo ! empty( $website['allowances']['monitoring_checks_view'] ) ? 'checked' : ''; ?>>
                                        Monitoring checks view
                                    </label>

                                    <label class="website-setting">
                                        <input type="hidden" name="allowances_change_detections_view" value="0">
                                        <input type="checkbox" name='allowances_change_detections_view' class="website-setting" value="1"
                                            <?php echo ! empty( $website['allowances']['change_detections_view'] ) ? 'checked' : ''; ?>>
                                        Change Detections view
                                    </label>

                                    <label class="website-setting">
                                        <input type="hidden" name="allowances_settings_view" value="0">
                                        <input type="checkbox" name='allowances_settings_view' class="website-setting" value="1"
                                            <?php echo ! empty( $website['allowances']['settings_view'] ) ? 'checked' : ''; ?>>
                                        Settings view
                                    </label>

                                    <label class="website-setting">
                                        <input type="hidden" name="allowances_logs_view" value="0">
                                        <input type="checkbox" name='allowances_logs_view' class="website-setting" value="1"
                                            <?php echo ! empty( $website['allowances']['logs_view'] ) ? 'checked' : ''; ?>>
                                        Queue view
                                    </label>

                                    <h4 class="website-setting" style="margin-top: 20px; margin-bottom: 10px;">
                                        Manual checks<br><small>The "Manual checks view" must be enabled</small>
                                    </h4>
                                    <label class="website-setting">
                                        <input type="hidden" name="allowances_manual_checks_start" value="0">
                                        <input type="checkbox" name='allowances_manual_checks_start' class="website-setting" value="1"
                                            <?php echo ! empty( $website['allowances']['manual_checks_start'] ) ? 'checked' : ''; ?>>
                                        Allow start manual checks
                                    </label>

                                    <label class="website-setting">
                                        <input type="hidden" name="allowances_manual_checks_settings" value="0">
                                        <input type="checkbox" name='allowances_manual_checks_settings' class="website-setting" value="1"
                                            <?php echo ! empty( $website['allowances']['manual_checks_settings'] ) ? 'checked' : ''; ?>>
                                        Show manual checks settings
                                    </label>
                                    <label class="website-setting">
                                        <input type="hidden" name="allowances_manual_checks_urls" value="0">
                                        <input type="checkbox" name='allowances_manual_checks_urls' class="website-setting" value="1"
                                            <?php echo ! empty( $website['allowances']['manual_checks_urls'] ) ? 'checked' : ''; ?>>
                                        Show manual checks urls
                                    </label>

                                    <h4 class="website-setting" style="margin-top: 20px; margin-bottom: 10px;">
                                        Monitoring checks <br><small>The "Monitoring checks view" must be enabled</small></h4>
                                    <label class="website-setting">
                                        <input type="hidden" name="allowances_monitoring_checks_settings" value="0">
                                        <input type="checkbox" name='allowances_monitoring_checks_settings' class="website-setting" value="1"
                                            <?php echo ! empty( $website['allowances']['monitoring_checks_settings'] ) ? 'checked' : ''; ?>>
                                        Show monitoring checks settings
                                    </label>
                                    <label class="website-setting">
                                        <input type="hidden" name="allowances_monitoring_checks_urls" value="0">
                                        <input type="checkbox" name='allowances_monitoring_checks_urls' class="website-setting" value="1"
                                            <?php echo ! empty( $website['allowances']['monitoring_checks_urls'] ) ? 'checked' : ''; ?>>
                                        Show monitoring checks urls
                                    </label>

                                    <h4 class="website-setting" style="margin-top: 20px; margin-bottom: 10px;">
                                        Other settings<br><small>Some more restrictions you can set for the WP website</small>
                                    </h4>
                                    <label class="website-setting">
                                        <input type="hidden" name="allowances_settings_add_urls" value="0">
                                        <input type="checkbox" name='allowances_settings_add_urls' class="website-setting" value="1"
                                            <?php echo ! empty( $website['allowances']['settings_add_urls'] ) ? 'checked' : ''; ?>>
                                        Show add url types in settings
                                    </label>
                                    <label class="website-setting">
                                        <input type="hidden" name="allowances_settings_account_settings" value="0">
                                        <input type="checkbox" name='allowances_settings_account_settings' class="website-setting" value="1"
                                            <?php echo ! empty( $website['allowances']['settings_account_settings'] ) ? 'checked' : ''; ?>>
                                        Show account settings
                                    </label>

                                    <label class="website-setting">
                                        <input type="hidden" name="allowances_upgrade_account" value="0">
                                        <input type="checkbox" name='allowances_upgrade_account' class="website-setting" value="1"
                                            <?php echo ! empty( $website['allowances']['upgrade_account'] ) ? 'checked' : ''; ?>>
                                        Allow upgrading account
                                    </label>
                                    <label class="website-setting">
                                        <input type="hidden" name="allowances_wizard_start" value="0">
                                        <input type="checkbox" name='allowances_wizard_start' class="website-setting" value="1"
                                            <?php echo ! empty( $website['allowances']['wizard_start'] ) ? 'checked' : ''; ?>>
                                        Start the wizard
                                    </label>
                                    <label class="website-setting">
                                        <input type="hidden" name="allowances_only_frontpage" value="0">
                                        <input type="checkbox" name='allowances_only_frontpage' class="website-setting" value="1"
                                            <?php echo ! empty( $website['allowances']['only_frontpage'] ) ? 'checked' : ''; ?>>
                                        Allow only checks for frontpage
                                    </label>
                                </div>

                                <div class="wcd-website-settings-column">
                                    <h4 class="website-settings">
                                        Auto Update Settings<br><small>Make the settings for WP auto update checks</small>
                                    </h4>
                                    <label class="website-setting">
                                        <input type="hidden" name="auto_update_settings_auto_update_checks_enabled" value="0">
                                        <input type="checkbox" name='auto_update_settings_auto_update_checks_enabled' class="website-setting" value="1"
                                            <?php echo ! empty( $website['auto_update_settings']['auto_update_checks_enabled'] ) ? 'checked' : ''; ?>>
                                        Auto Updates Checks enabled
                                    </label>
                                    <p style="margin-top: 20px; margin-bottom: 10px; font-size: 16px;">Do WP auto-updates only on these weekdays:</p>
                                    <label class="website-setting">
                                        <input type="hidden" name="auto_update_settings_auto_update_checks_monday" value="0">
                                        <input type="checkbox" name='auto_update_settings_auto_update_checks_monday' class="website-setting" value="1"
                                            <?php echo ! empty( $website['auto_update_settings']['auto_update_checks_monday'] ) ? 'checked' : ''; ?>>
                                        Monday
                                    </label>
                                    <label class="website-setting">
                                        <input type="hidden" name="auto_update_settings_auto_update_checks_tuesday" value="0">
                                        <input type="checkbox" name='auto_update_settings_auto_update_checks_tuesday' class="website-setting" value="1"
                                            <?php echo ! empty( $website['auto_update_settings']['auto_update_checks_tuesday'] ) ? 'checked' : ''; ?>>
                                        Tuesday
                                    </label>
                                    <label class="website-setting">
                                        <input type="hidden" name="auto_update_settings_auto_update_checks_wednesday" value="0">
                                        <input type="checkbox" name='auto_update_settings_auto_update_checks_wednesday' class="website-setting" value="1"
                                            <?php echo ! empty( $website['auto_update_settings']['auto_update_checks_wednesday'] ) ? 'checked' : ''; ?>>
                                        Wednesday
                                    </label>
                                    <label class="website-setting">
                                        <input type="hidden" name="auto_update_settings_auto_update_checks_thursday" value="0">
                                        <input type="checkbox" name='auto_update_settings_auto_update_checks_thursday' class="website-setting" value="1"
                                            <?php echo ! empty( $website['auto_update_settings']['auto_update_checks_thursday'] ) ? 'checked' : ''; ?>>
                                        Thursday
                                    </label>
                                    <label class="website-setting">
                                        <input type="hidden" name="auto_update_settings_auto_update_checks_friday" value="0">
                                        <input type="checkbox" name='auto_update_settings_auto_update_checks_friday' class="website-setting" value="1"
                                            <?php echo ! empty( $website['auto_update_settings']['auto_update_checks_friday'] ) ? 'checked' : ''; ?>>
                                        Friday
                                    </label>
                                    <label class="website-setting">
                                        <input type="hidden" name="auto_update_settings_auto_update_checks_saturday" value="0">
                                        <input type="checkbox" name='auto_update_settings_auto_update_checks_saturday' class="website-setting" value="1"
                                            <?php echo ! empty( $website['auto_update_settings']['auto_update_checks_saturday'] ) ? 'checked' : ''; ?>>
                                        Saturday
                                    </label>
                                    <label class="website-setting">
                                        <input type="hidden" name="auto_update_settings_auto_update_checks_sunday" value="0">
                                        <input type="checkbox" name='auto_update_settings_auto_update_checks_sunday' class="website-setting" value="1"
                                            <?php echo ! empty( $website['auto_update_settings']['auto_update_checks_sunday'] ) ? 'checked' : ''; ?>>
                                        Sunday
                                    </label>

                                    <h4 class="website-setting" style="margin-top: 20px; margin-bottom: 10px;">
                                        Other settings<br><small>Some more restrictions you can set for the WP website</small>
                                    </h4>
                                    <label class="website-setting">
                                        <input type="hidden" name="auto_update_settings_auto_update_wait_24h" value="0">
                                        <input type="checkbox" name='auto_update_settings_auto_update_wait_24h' class="website-setting" value="1"
                                            <?php echo ! empty( $website['auto_update_settings']['auto_update_wait_24h'] ) ? 'checked' : ''; ?>>
                                        Wait 24h after updates to take screenshots
                                    </label>

                                    <label class="website-setting">
                                        <input type="hidden" name="auto_update_settings_auto_update_cms_filter" value="">
                                        <label for="auto_update_settings_auto_update_cms_filter">Post types for update checks (comma-separated)</label>
                                        <input type="text" name='auto_update_settings_auto_update_cms_filter' class="website-setting" placeholder="page,post"
                                            value="<?php echo ! empty( $website['auto_update_settings']['auto_update_cms_filter'] ) ? esc_attr( $website['auto_update_settings']['auto_update_cms_filter'] ) : ''; ?>">
                                    </label>
                                </div>

                                <div style="width: 100%; clear: both; text-align: center; margin-top: 30px;">
                                    <input type="submit" class="et_pb_button" value="Save Settings">
                                    <?php if ( ! empty( $website['domain'] ) ) { ?>
                                        <button type="button" onclick="return deleteUserWebsite('<?php echo esc_js( $website['domain'] ); ?>', '<?php echo esc_js( $website['id'] ); ?>');" class="et_pb_button wcd-delete-button" style="margin-left: 10px;">Delete Website</button>
                                    <?php } ?>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
            <?php
        }
    }

	/**
	 * Get the WordPress website settings view.
	 *
	 * @since    1.0.0
	 * @return   void
	 */
	public static function get_wp_website_settings() { ?>
        <div class="wcd-modern-dashboard">
            <!-- Main Dashboard Grid -->
            <div class="wcd-dashboard-grid">
                <!-- WordPress Plugin Card -->
                <div class="wcd-card">
                    <div class="wcd-card-header">
                        <h2>
                            <span class="dashicons dashicons-wordpress" style="color: #266ECC;"></span>
                            Plugin for WordPress
                        </h2>
                    </div>
                    <div class="wcd-card-content">
                        <p>Connect your WP website to the WebChange Detector WebApp using your API token.</p>
                        <p>Download and configure the WordPress plugin to automatically sync your website URLs and run change detection checks.</p>
                        <a class="et_pb_button" href="https://wordpress.org/plugins/webchangedetector/">Download the plugin</a>
                    </div>
                </div>

                <!-- API Token Card -->
                <div class="wcd-card">
                    <div class="wcd-card-header">
                        <h2>
                            <span class="dashicons dashicons-admin-network" style="color: #266ECC;"></span>
                            Your API Token
                        </h2>
                    </div>
                    <div class="wcd-card-content">
                        <p>Use your API token in our WordPress plugin to link your sites to your WebChangeDetector account, configure settings, and manage restrictions. Websites using your API token will appear here.</p>
                        
                        <div class="wcd-api-token-section" data-token="<?php echo esc_attr(mm_api_token()); ?>">
                            <div class="wcd-api-token-controls">
                                <span id="api-token-display" style="display: none; font-family: monospace; font-size: 13px;"><?php echo esc_html(mm_api_token()); ?></span>
                                <span id="api-token-hidden" style="font-family: monospace; letter-spacing: 2px;">••••••••••••••••••••••••••••••••••••••••••••••••••</span>
                                <button type="button" id="toggle-api-token" class="wcd-token-toggle" title="Show/Hide API Token">
                                    <span class="dashicons dashicons-visibility"></span>
                                </button>
                            </div>
                            <div class="wcd-security-notice">
                                <span class="dashicons dashicons-shield-alt"></span>
                                Keep your API token secure and never share it publicly
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Linked WP Sites Section -->
            <div class="wcd-card" style="margin-top: 20px;">
                <div class="wcd-card-header">
                    <h2>
                        <span class="dashicons dashicons-admin-links" style="color: #266ECC;"></span>
                        Your Linked WP Sites
                    </h2>
                </div>
                <div class="wcd-card-content">
                    <?php
                    $filter = [
                        'per_page' => 25,
                        'page' => $_GET['pagination'] ?? 1
                    ];
                    $websites = \WebChangeDetector\Wp_Compare_API_V2::get_websites_v2($filter);
                    self::print_websites( $websites );
                    ?>
                </div>
            </div>
        </div>

        <script>
        document.addEventListener('DOMContentLoaded', function() {
            const toggleBtn = document.getElementById('toggle-api-token');
            const tokenDisplay = document.getElementById('api-token-display');
            const tokenHidden = document.getElementById('api-token-hidden');
            const icon = toggleBtn.querySelector('.dashicons');
            
            toggleBtn.addEventListener('click', function() {
                if (tokenDisplay.style.display === 'none') {
                    tokenDisplay.style.display = 'inline';
                    tokenHidden.style.display = 'none';
                    icon.classList.remove('dashicons-visibility');
                    icon.classList.add('dashicons-hidden');
                    toggleBtn.title = 'Hide API Token';
                } else {
                    tokenDisplay.style.display = 'none';
                    tokenHidden.style.display = 'inline';
                    icon.classList.remove('dashicons-hidden');
                    icon.classList.add('dashicons-visibility');
                    toggleBtn.title = 'Show API Token';
                }
            });
        });
        </script>

    <?php
    }

	/**
	 * Get the subaccount management view.
	 *
	 * @since    1.0.0
	 * @return   void
	 */
	public static function get_subaccount_view() {
		// Use account handler directly.
		$account_handler = new \WebChangeDetector\WebChangeDetector_Admin_Account( new \WebChangeDetector\WebChangeDetector_API_Manager() );
		if($account_handler->is_main_account()) {
			include wcd_get_plugin_dir() . 'public/partials/popup-add-subaccount.php'; ?>
			<div class="action_container wcd-modern-dashboard">
				<div class="website-settings wcd-card">
					<div class="wcd-card-header">
						<h2>
							<span class="dashicons dashicons-admin-users" style="color: #266ECC;"></span>
							Subaccount Management
						</h2>
					</div>
					<div class="wcd-card-content">
						<p>Create and manage subaccounts to delegate access and limit checks per account.</p>
						
						<div style="margin-bottom: 20px;">
							<button class='et_pb_button' onclick='showAddSubaccountPopup(); return false;'>
								<span class="dashicons dashicons-plus-alt"></span>
								Create Subaccount
							</button>
						</div>

						<div id="ajax_subaccounts_container">
							<?php
							$subaccounts           = \WebChangeDetector\Wp_Compare_API_V2::get_subaccounts()['data'];
							$subaccount_api_tokens = get_user_meta( get_current_user_id(), 'wcd_subaccount_api_tokens', true );

							if ( count( $subaccounts ) ) {
								foreach ( $subaccounts as $subaccount ) {
									?>
									<div class="box-half wcd-card" style="margin-bottom: 20px;">
										<div class="wcd-card-content">
											<div class="wcd-account-section">
												<div class="wcd-account-item">
													<span class="wcd-account-label">Subaccount</span>
													<span class="wcd-account-value"><?php echo esc_html($subaccount['name_first'] . ' ' . $subaccount['name_last']); ?></span>
												</div>

												<div class="wcd-account-item">
													<span class="wcd-account-label">Email</span>
													<span class="wcd-account-value"><?php echo esc_html($subaccount['email']); ?></span>
												</div>

												<div class="wcd-account-item">
													<span class="wcd-account-label">Usage</span>
													<span class="wcd-account-value"><?php echo esc_html($subaccount['checks_left'] . ' / ' . $subaccount['checks_limit']); ?> checks</span>
													<?php 
													$usage_percent = $subaccount['checks_limit'] > 0 ? (($subaccount['checks_limit'] - $subaccount['checks_left']) / $subaccount['checks_limit']) * 100 : 0;
													?>
													<div class="wcd-progress-container">
														<div class="wcd-progress-bar" style="width: <?php echo esc_attr(number_format($usage_percent, 1)); ?>%;"></div>
														<span class="wcd-progress-text"><?php echo esc_html(number_format($usage_percent, 1)); ?>%</span>
													</div>
												</div>

												<div class="wcd-account-item">
													<span class="wcd-account-label">API Token</span>
													<?php $subaccount_api_token = $subaccount_api_tokens[ $subaccount['id'] ] ?? false; ?>
													<div class="wcd-api-token-section" data-token="<?php echo esc_attr($subaccount_api_token); ?>">
														<div class="wcd-api-token-controls">
															<span class="api-token-display" style="display: none; font-family: monospace; font-size: 13px;"><?php echo esc_html($subaccount_api_token); ?></span>
															<span class="api-token-hidden" style="font-family: monospace; letter-spacing: 2px;">••••••••••••••••••••••••••••••••••••••••••••••••••</span>
															<button type="button" class="wcd-token-toggle" title="Show/Hide API Token" onclick="return revealApiToken(this);">
																<span class="dashicons dashicons-visibility"></span>
															</button>
														</div>
													</div>
												</div>

												<div class="wcd-account-item">
													<span class="wcd-account-label">Actions</span>
													<div style="display: flex; gap: 10px; flex-wrap: wrap;">
														<button class='et_pb_button' onclick="showUpdateSubaccountPopup('<?php echo $subaccount['id'] ?>')">
															<span class="dashicons dashicons-edit"></span>
															Edit
														</button>
														<button class='et_pb_button ajax-switch-account-button' data-id="<?php echo $subaccount['id'] ?>">
															<span class="dashicons dashicons-admin-users"></span>
															Switch to Account
														</button>
													</div>
												</div>
											</div>
										</div>
									</div>
									<?php
								}
							} else { ?>
								<div class="box-half wcd-card" style="text-align: center; padding: 40px;">
									<span class="dashicons dashicons-welcome-add-page" style="font-size: 48px; color: #64748b; margin-bottom: 16px;"></span>
									<h3 style="color: #64748b; margin-bottom: 8px;">No Subaccounts Yet</h3>
									<p style="color: #64748b; margin-bottom: 20px;">Create your first subaccount to delegate access and manage checks.</p>
									<button class='et_pb_button' onclick="showAddSubaccountPopup(); return false;">
										<span class="dashicons dashicons-plus-alt"></span>
										Create First Subaccount
									</button>
								</div>
							<?php } ?>
						</div>
					</div>
				</div>
			</div>
			<?php } else { ?>
			<div class="action_container wcd-modern-dashboard">
				<div class="website-settings wcd-card" style="text-align: center;">
					<div class="wcd-card-content">
						<span class="dashicons dashicons-admin-users" style="font-size: 48px; color: #64748b; margin-bottom: 16px;"></span>
						<h3 style="color: #1e293b; margin-bottom: 16px;">Main Account Required</h3>
						<p style="margin-bottom: 20px;">Subaccounts can only be managed from your main account.</p>
						<a href="" class="et_pb_button ajax-switch-account-button" data-id="<?= \WebChangeDetector\WebChangeDetector_Admin_Utils::get_account_details_v2(mm_api_token())['id']; ?>">
							<span class="dashicons dashicons-admin-users"></span>
							Switch to Main Account
						</a>
					</div>
				</div>
			</div>
			<?php } ?>
	<?php
	}

	/**
	 * Get monitoring settings form HTML.
	 *
	 * @param array $group Group configuration array.
	 * @param bool $cancel_button Whether to show cancel button.
	 * @param int $monitoring_group Monitoring group ID.
	 * @param string|null $cms CMS type.
	 * @return void
	 */
	public static function get_monitoring_settings( array $group, $cancel_button = false, $monitoring_group = 0, $cms = null ) {
		if ( ! ( $group['id'] ) ) {
			$group['id']      = 0;
			$group['cms']     = $cms;
			$group['enabled'] = 1;
			$group['name']    = '';
		}

		if ( $cancel_button ) {
			?>
		<form action="" method="post">
			<input type="hidden" name="action" value="save_group_settings">
			<input type="hidden" name="group_id" value="<?php echo $group['id']; ?>">
			<input type="hidden" name="cms" value="<?php echo $cms ?? $group['cms']; ?>">

		<?php } ?>
		<div class="form-container">
			<input type="hidden" name="monitoring" value="<?php echo $monitoring_group; ?>">
			<input type="hidden" name="enabled" id="enabled<?php echo $group['uuid'] ?? 1; ?>" value="<?php echo isset( $group['enabled'] ) ? $group['enabled'] : '1'; ?>">

			<?php
			// if(!$cms || $group['id']) {
			$group_name_id = '';
			if ( $group['id'] === 0 && $group['cms'] ) {
				$group_name_id = 'ajax-fill-group-name';
			}
			?>

			<div class="form-row bg">
				<label for="name">Group name</label>
				<input id="<?php echo $group_name_id; ?>" type="text" name="group_name" value="<?php echo $group['name'] ?? $_POST['domain'] ?? ''; ?>" width="100%">
			</div>

			<?php if ( $monitoring_group ) { ?>
				<div id="monitoring-settings-<?php echo $group['id']; ?>" class="monitoring-settings">

					<div class="form-row">
						<?php
						// Get main plugin instance for account details.
						$wp_compare = new \Wp_Compare();
						$account_details                    = $wp_compare->get_account_details_v2();
						$exclude_plans_for_minute_intervals = array(
                            'trial',
                            'free',
                            'personal',
                            'personal_pro',

						);
						$show_minute_intervals              = false;
						if ( ! in_array( $account_details['plan'], $exclude_plans_for_minute_intervals ) ) {
							$show_minute_intervals = true;
						}
						?>
					<label for="interval_in_h">Monitoring Interval</label>
						<select class="interval_in_h" name="interval_in_h">
							<option value="0.25"
								<?php echo ! $show_minute_intervals ? 'disabled ' : ''; ?>
								<?php echo isset( $group['interval_in_h'] ) && $group['interval_in_h'] == '0.25' ? 'selected' : ''; ?>
								<?php echo ! isset( $group['interval_in_h'] ) ? 'selected' : ''; ?>>
								Every 15 minutes <?php echo ! $show_minute_intervals ? '("Freelancer" plan or higher)' : ''; ?>
							</option>
							<option value="0.5"
								<?php echo ! $show_minute_intervals ? 'disabled ' : ''; ?>
								<?php echo isset( $group['interval_in_h'] ) && $group['interval_in_h'] == '0.5' ? 'selected' : ''; ?>
								<?php echo ! isset( $group['interval_in_h'] ) ? 'selected' : ''; ?>>
								Every 30 minutes <?php echo ! $show_minute_intervals ? '("Freelancer" plan or higher)' : ''; ?>
							</option>
							<option value="1"
								<?php echo isset( $group['interval_in_h'] ) && $group['interval_in_h'] == '1' ? 'selected' : ''; ?>>
								Every 1 hour
							</option>
							<option value="3"
								<?php echo isset( $group['interval_in_h'] ) && $group['interval_in_h'] == '3' ? 'selected' : ''; ?>>
								Every 3 hours
							</option>
							<option value="6"
								<?php echo isset( $group['interval_in_h'] ) && $group['interval_in_h'] == '6' ? 'selected' : ''; ?>>
								Every 6 hours
							</option>
							<option value="12"
								<?php echo isset( $group['interval_in_h'] ) && $group['interval_in_h'] == '12' ? 'selected' : ''; ?>>
								Every 12 hours
							</option>
							<option value="24"
								<?php echo isset( $group['interval_in_h'] ) && $group['interval_in_h'] == '24' ? 'selected' : ''; ?>
								<?php echo ! isset( $group['interval_in_h'] ) ? 'selected' : ''; ?>>
								Every 24 hours
							</option>
						</select>
						
						<div id="wcd-monitoring-group-time-settings">
						from
							<select name="hour_of_day">
								<?php
								for ( $i = 0; $i < HOURS_IN_DAY; $i++ ) {
									if ( isset( $group['hour_of_day'] ) && $group['hour_of_day'] == $i ) {
										$selected = 'selected';
									} elseif ( ! isset( $group['hour_of_day'] ) && gmdate( 'H' ) == $i ) {
										$selected = 'selected';
									} else {
										$selected = '';
									}
									echo '<option class="select-time" value="' . $i . '" ' . $selected . '></option>';
								}
								?>
							</select>
							o'clock
						</div>
					</div>

					<div class="form-row bg">
						<label for="alert_emails">Email addresses for alerts (comma separated for multiple email addresses)</label>
						<input type="text" name="alert_emails"
                            <?php
                            echo "value='";
                            echo ! empty( $group['alert_emails'] ) ?
                                $group['alert_emails']:
                                $account_details['email'];
                            echo "'";
                            ?>
                            >
					</div>
				</div>

			<?php } ?>
			<div class="form-row">
				<label for="name">Threshold for difference in change detections</label>
				<input class="threshold" type="number" step='0.1' name="threshold" value="<?php echo $group['threshold'] ?? '0.00'; ?>" min="0" max="100"> %
			</div>
		</div>
		<?php

		if ( $cancel_button ) {
			?>
			<p>
				<input class="et_pb_button" type="submit" value="Save group settings">
				<button class="et_pb_button" onclick="return closeAddGroupPopup()">Cancel</button>
			</p>
			</form>
			<?php
		}
	}

	/**
	 * Load comparisons view via AJAX.
	 *
	 * @param string $batch_id The batch ID to get comparisons for.
	 * @param array $compares Comparison data.
	 * @param array $filters Filter parameters.
	 * @param int $failed_count Number of failed items.
	 * @return void
	 */
	public static function load_comparisons_view( $batch_id, $compares, $filters, $failed_count ) {
		// Get main plugin instance for account details.
		$wp_compare = new \Wp_Compare();
		?>
        <div class="ajax_batch_comparisons_content">
            <!-- Failed Queues Accordion -->
			 <?php if($failed_count > 0) { ?>
				<div class="accordion-container failed-queues-accordion" style="margin: 20px">
					<div class="accordion">
						<div class="mm_accordion_title">
								<h3 onclick="toggleFailedQueues(this, '<?php echo $batch_id; ?>')" style="position: relative; cursor: pointer;">
								<div class="accordion-state-icon-position">
									<span class="accordion-state-icon accordion-arrow dashicons dashicons-arrow-right-alt2" style="transition: transform 0.3s;"></span>
								</div>
								<div style="display: inline-block">
									<div style="margin-left: 30px;">
										Failed checks
									</div>
								</div>
							</h3>
							<div class="failed-queues-content mm_accordion_content" style="display: none;">
                            <div class="failed-queues-loading" style="padding: 20px; text-align: center; display: none;">
                                Loading failed URLs...
                            </div>
                            <div class="failed-queues-table-container"></div>
                        </div>
						</div>
					</div>
				</div>
			<?php } ?>
            <table class="toggle">
                <tr class="table-headline-row">
                    <th style="width: 170px">Status</th>
                    <th style="width:auto">URL</th>
                    <th style="width:220px">Compared Screenshots</th>
                    <th style="width:100px">Difference</th>
                    <th style="width:120px">Console</th>
                </tr>
                <?php
                // Change detections
                foreach ( $compares['data'] as $key => $compare ) {
                $next_comparison_token = false; // used in template
                if ( isset( $compares[ $key + 1 ] ) ) {
                    $next_comparison_token = $compares[ $key + 1 ]['token'];
                }
                $previous_comparison_token = false; // used in template
                if ( isset( $compares[ $key - 1 ] ) ) {
                    $previous_comparison_token = $compares[ $key - 1 ]['token'];
                }
                
                // Check for browser console data and plan access
                $hasConsoleChanges = !empty($compare['browser_console_added']) || !empty($compare['browser_console_removed']) || !empty($compare['browser_console_change']);
                $consoleChangeCount = 0;
                if ($hasConsoleChanges) {
                    $consoleChangeCount += is_array($compare['browser_console_added']) ? count($compare['browser_console_added']) : 0;
                    $consoleChangeCount += is_array($compare['browser_console_removed']) ? count($compare['browser_console_removed']) : 0;
                }
                
                $user_account = $wp_compare->get_account_details_v2();
                $user_plan = $user_account['plan'] ?? 'free';
                $canAccessBrowserConsole = \wcd_can_access_feature('browser_console', $user_plan);
                ?>
                <tr class="comparison_row"
                    data-url_id="<?php echo $compare['url']; ?>"
                    data-comparison_id="<?php echo $compare['id']; ?>"
                    onclick="ajaxShowChangeDetectionPopup('<?php echo $compare['token']; ?>','<?php echo $key; ?>', '<?php echo ( $compares['meta']['total'] ); ?>')"
                >
                    <td data-label="Status" style="order: 2">
                        <div class="status_container">
                            <span class="current_status"><?php echo \prettyPrintComparisonStatus( $compare['status'], 'mm_inline_block' ); ?></span>
                        </div>
                    </td>
                    <td data-label="URL" style="order: 1">
                        <?php
                        if ( ! empty( $compare['html_title'] ) ) {
                            echo '<strong>' . $compare['html_title'] . '</strong><br>';
                        }
                        echo \get_device_icon( $compare['device'] ) . $compare['url']; ?>
                    </td>

                    <td data-label="Compared Screenshots" style="order: 3">
                        <div class="screenshot-date local-time" data-date="<?php echo strtotime($compare['screenshot_1_created_at']); ?>">
                            <?php echo gmdate( 'd/m/Y H:i', strtotime($compare['screenshot_1_created_at'] )); ?>
                        </div>
                        <div class="screenshot-date local-time" data-date="<?php echo strtotime($compare['screenshot_2_created_at']); ?>">
                            <?php echo gmdate( 'd/m/Y H:i', strtotime($compare['screenshot_2_created_at']) ); ?>
                        </div>
                    </td>

                    <?php
                    $class = 'no-difference';
                    if ( $compare['difference_percent'] ) {
                        $class = 'is-difference';
                    }
                    ?>

                    <td class="diff-tile <?php echo $class; ?>"
                        data-diff_percent="<?php echo $compare['difference_percent']; ?>"
                        data-threshold="<?php echo $compare['threshold']; ?>"
						style="order: 4"
                    >
						<div class="mobile-label-difference" style="display: none" >Difference</div>
                        <?php echo $compare['difference_percent']; ?> %
                        <?php echo $compare['threshold'] > $compare['difference_percent'] ? '<div style="font-size: 10px">Threshold: ' . $compare['threshold'] . '%</div>' : ''; ?>
                    </td>

                    <!-- Console Changes Column -->
                    <td class="console-tile wcd-console-changes-column <?php echo $hasConsoleChanges ? 'has-console-changes' : 'no-console-changes'; ?> <?php echo !$canAccessBrowserConsole ? 'wcd-console-restricted' : ''; ?>"
                        data-console_changes="<?php echo $consoleChangeCount; ?>"
						style="order: 5; position: relative;"
                    >
						<div class="mobile-label-difference" style="display: none">Console Changes</div>
                        <div class="wcd-console-diff-box" <?php echo !$canAccessBrowserConsole ? 'style="filter: blur(3px);"' : ''; ?>>
                            <?php if ($canAccessBrowserConsole) { ?>
                                <?php if ($hasConsoleChanges && $consoleChangeCount > 0) { ?>
                                    <div class="wcd-console-indicator-badge">
                                        <span class="wcd-console-count"><?php echo $consoleChangeCount; ?></span>
                                        <span class="wcd-console-text">changes</span>
                                    </div>
                                <?php } else { ?>
                                    <div class="wcd-no-console-changes">
                                        <span class="wcd-console-none-text">No changes</span>
                                    </div>
                                <?php } ?>
                            <?php } else { ?>
                                <!-- Dummy content for restricted plans -->
                                <div class="wcd-console-indicator-badge">
                                    <span class="wcd-console-count">3</span>
                                    <span class="wcd-console-text">changes</span>
                                </div>
                            <?php } ?>
                        </div>
                        
                        <?php if (!$canAccessBrowserConsole) { ?>
                            <!-- Lock overlay for restricted plans -->
                            <div style="position: absolute; top: 0; left: 0; right: 0; bottom: 0; display: flex; align-items: center; justify-content: center; background: rgba(255,255,255,0.8); z-index: 10;">
                                <div style="text-align: center; color: #666; font-size: 12px;">
                                    <span class="dashicons dashicons-lock" style="font-size: 20px; margin-bottom: 5px;"></span><br>
                                    Needs Personal Pro
                                </div>
                            </div>
                        <?php } ?>
                    </td>

					<td style="order: 6; display: none;">
                        <button id="show-compare-<?php echo $key; ?>" onclick="jQuery(this).closest('.comparison_row').click();" class="et_pb_button">
                            Show
                        </button>
                    </td>
                </tr>
                <?php
                }
				

                ?>
            </table>

            <div class='pagination_container' style='margin-top: 30px; margin-left: 20px;'>
                <?php
                

                foreach ( $compares['meta']['links'] as $link ) {
                    // Parse the URL to get the query part
                    $parsedUrl = wp_parse_url($link['url'], PHP_URL_QUERY);

                    // Parse the query string into an array
                    wp_parse_str($parsedUrl, $queryParams);

                    // Get the 'page' parameter
                    $page = isset($queryParams['page']) ? $queryParams['page'] : null;

                    ?>
                    <button class="ajax_paginate_batch_comparisons et_pb_button"
                            style="padding-left: 15px !important; padding-right: 15px !important;"
                            data-filters='<?php echo json_encode($filters) ?>'
                            data-page="<?php echo $page; ?>"
                        <?php echo $link['active'] || is_null($page) ? ' disabled' : ''; ?>
                            onclick="return false;"
                    >
                        <?php echo $link['label']; ?>
                    </button>
                    <?php

                }
                echo "Total: " . $compares['meta']['total'] . " items"; ?>
            </div>
        </div>
        <?php
	}

	/**
	 * Load failed queues view via AJAX.
	 *
	 * @param string $batch_id The batch ID to get failed queues for.
	 * @return void
	 */
	public static function load_failed_queues_view( $batch_id ) {
		$failed_queues = \Wp_Compare_API_V2::get_queues_v2([$batch_id], 'failed', ['per_page' => 100]);
        
        // Handle pagination for failed queues if needed.
        if(!empty($failed_queues['meta']['last_page']) && $failed_queues['meta']['last_page'] > 1) {
            for($i = 2; $i <= $failed_queues['meta']['last_page']; $i++) {
                $failed_queues_data = \Wp_Compare_API_V2::get_queues_v2($batch_id, 'failed', ['per_page' => 100, 'page' => $i]);
                $failed_queues['data'] = array_merge($failed_queues['data'], $failed_queues_data['data']);
            }
        }

        if (empty($failed_queues['data'])) {
            echo '<div style="padding: 20px; text-align: center; color: #666;">No failed URLs found for this batch.</div>';
            return;
        }
        ?>
        <table class="toggle" style="margin: 0;">
            <tr class="table-headline-row">
                <th>Status</th>
                <th style="width:auto">URL</th>
                <th style="width:auto">Error Message</th>
            </tr>
            <?php
			
            foreach($failed_queues['data'] as $failed_queue) {
                if($batch_id === $failed_queue['batch']) { ?>
                    <tr style="background-color: rgba(220, 50, 50, 0.1);">
                        <td style="order: 2">
                            <div class="status_container">
                                <span class="current_status">
                                    <?php echo \prettyPrintComparisonStatus( 'failed', 'mm_inline_block' ); ?>
                                </span>
                            </div>
                        </td>
                        <td style="order: 1;">
                            <?php
                            if ( ! empty( $failed_queue['html_title'] ) ) {
                                echo '<strong>' . $failed_queue['html_title'] . '</strong><br>';
                            }
                            echo \get_device_icon( $failed_queue['device'] ) . $failed_queue['url_link'];
                            ?>
                        </td>
                        <td style="order: 3">
                            <?php echo $failed_queue['error_msg'] ?? 'Unknown error'; ?>
                        </td>
                    </tr>
                <?php }
            }
            ?>
        </table>
        <?php
	}

	/**
	 * Show activate account page.
	 *
	 * @return bool Always returns true.
	 */
	public static function show_activate_account() {
		// Delegate to the new account handler if available
		if ( class_exists( '\WebChangeDetector\WebChangeDetector_Admin' ) ) {
			$admin = new \WebChangeDetector\WebChangeDetector_Admin();
			return $admin->account_handler->show_activate_account( null );
		}

		// Fallback to legacy implementation for compatibility
		global $current_user;
		// The account is not activated yet, but the api_token is there already
		if ( isset( $_POST['action'] ) && $_POST['action'] === 'resend_verification_email' ) {
			// Use account handler directly
			$account_handler = new \WebChangeDetector\WebChangeDetector_Admin_Account( new \WebChangeDetector\WebChangeDetector_API_Manager() );
			$account_handler->resend_verification_email();
			echo '<div style="max-width: 600px; margin: 0 auto;">
                    <div class="mm_message_container" style="margin-left: 0; margin-right: 0;">
                        <div class="message success" style="padding: 10px;">Email sent successfully.</div>
                    </div>
                </div>';
		}

		echo '<div class="mm_wp_compare mm_no_account" style="max-width: 600px; margin: 0 auto; padding: 40px 20px;">
                <div class="mm_activation_card">
                    <h1 class="mm_activation_title">Activate Your Account</h1>
                    <p class="mm_activation_text">
                        We\'ve sent a confirmation email to activate your account. Click the link in the email to get started.
                    </p>
                    <div class="mm_email_highlight">
                        <div class="mm_email_address">' . $current_user->user_email . '</div>
                    </div>
                    <p class="mm_activation_text">
                        Can\'t find the email? Check your spam folder or request a new one below.
                    </p>
                    
                    <div class="mm_resend_section">
                        <form action="' . MM_APP_PATH . '?tab=dashboard" method="post">
                            <input type="hidden" name="action" value="resend_verification_email">
                            <input type="submit" id="resend-email-btn" value="Send activation email again" class="et_pb_button" disabled>
                        </form>
                        <p class="mm_help_text">You can request a new email in <span id="countdown-display" class="mm_countdown_timer">30</span> seconds</p>
                    </div>
                </div>
                <script>
                (function() {
                    let countdown = 30;
                    const button = document.getElementById("resend-email-btn");
                    const countdownDisplay = document.getElementById("countdown-display");
                    const originalText = button.value;
                    const timer = setInterval(function() {
                        countdown--;
                        if (countdown > 0) {
                            //button.value = originalText + " (" + countdown + ")";
                            countdownDisplay.textContent = countdown;
                        } else {
                            button.value = originalText;
                            countdownDisplay.parentElement.style.display = "none";
                            button.disabled = false;
                            clearInterval(timer);
                        }
                    }, 1000);
                })();
                </script>
            </div>';
		return true;
	}

} 