<?php
/**
 * On-demand checks - change detection
 *
 *   @package    webchangedetector
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Include on-demand check tiles
 */
require 'update-step-tiles.php';

$actions_disabled = false;
require __DIR__ . '/update-step-next-actions.php';

$batches = \WebChangeDetector\WebChangeDetector_API_V2::get_batches_v2();
$batch   = array_slice( $batches['data'], 0, 1 );

// Pass only batch data to create accordion containers, content will be loaded via AJAX.
$wcd->dashboard_handler->compare_view_v2( $batch );
