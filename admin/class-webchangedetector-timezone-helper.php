<?php
/**
 * Timezone Helper for WebChangeDetector
 *
 * Handles timezone conversions between UTC and WordPress site timezone.
 *
 * @package    WebChangeDetector
 * @subpackage WebChangeDetector/admin
 * @author     Mike Miler <mike@wp-mike.com>
 */

namespace WebChangeDetector;

/**
 * Timezone Helper Class.
 */
class WebChangeDetector_Timezone_Helper {

	/**
	 * Convert UTC time to WordPress site timezone.
	 *
	 * Uses current date for accurate DST offset calculation.
	 *
	 * @param string $utc_time Time in UTC format (H:i).
	 * @param string $date Optional date in Y-m-d format. Defaults to today.
	 * @return string Time in site timezone format (H:i).
	 */
	public static function utc_to_site_time( $utc_time, $date = null ) {
		if ( empty( $utc_time ) ) {
			return $utc_time;
		}

		try {
			// Get WordPress timezone.
			$timezone_string = wp_timezone_string();
			$site_timezone   = new \DateTimeZone( $timezone_string );
			$utc_timezone    = new \DateTimeZone( 'UTC' );

			// Use provided date or current date for accurate DST calculation.
			if ( null === $date ) {
				$date = gmdate( 'Y-m-d' );
			}

			// Create DateTime object with full date and time in UTC.
			$datetime = \DateTime::createFromFormat( 'Y-m-d H:i', $date . ' ' . $utc_time, $utc_timezone );
			if ( ! $datetime ) {
				// If format doesn't match, return original.
				return $utc_time;
			}

			// Convert to site timezone.
			$datetime->setTimezone( $site_timezone );

			// Return formatted time.
			return $datetime->format( 'H:i' );
		} catch ( \Exception $e ) {
			// If any error occurs, return original time.
			return $utc_time;
		}
	}

	/**
	 * Convert WordPress site timezone time to UTC.
	 *
	 * Uses current date for accurate DST offset calculation.
	 *
	 * @param string $site_time Time in site timezone format (H:i).
	 * @param string $date Optional date in Y-m-d format. Defaults to today.
	 * @return string Time in UTC format (H:i).
	 */
	public static function site_time_to_utc( $site_time, $date = null ) {
		if ( empty( $site_time ) ) {
			return $site_time;
		}

		try {
			// Get WordPress timezone.
			$timezone_string = wp_timezone_string();
			$site_timezone   = new \DateTimeZone( $timezone_string );
			$utc_timezone    = new \DateTimeZone( 'UTC' );

			// Use provided date or current date for accurate DST calculation.
			if ( null === $date ) {
				$date = gmdate( 'Y-m-d' );
			}

			// Create DateTime object with full date and time in site timezone.
			$datetime = \DateTime::createFromFormat( 'Y-m-d H:i', $date . ' ' . $site_time, $site_timezone );
			if ( ! $datetime ) {
				// If format doesn't match, return original.
				return $site_time;
			}

			// Convert to UTC.
			$datetime->setTimezone( $utc_timezone );

			// Return formatted time.
			return $datetime->format( 'H:i' );
		} catch ( \Exception $e ) {
			// If any error occurs, return original time.
			return $site_time;
		}
	}
}
