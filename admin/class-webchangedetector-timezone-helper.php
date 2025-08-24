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
	 * @param string $utc_time Time in UTC format (H:i).
	 * @return string Time in site timezone format (H:i).
	 */
	public static function utc_to_site_time( $utc_time ) {
		if ( empty( $utc_time ) ) {
			return $utc_time;
		}

		try {
			// Get WordPress timezone.
			$timezone_string = wp_timezone_string();
			$site_timezone   = new \DateTimeZone( $timezone_string );
			$utc_timezone    = new \DateTimeZone( 'UTC' );

			// Create DateTime object with UTC time.
			$datetime = \DateTime::createFromFormat( 'H:i', $utc_time, $utc_timezone );
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
	 * @param string $site_time Time in site timezone format (H:i).
	 * @return string Time in UTC format (H:i).
	 */
	public static function site_time_to_utc( $site_time ) {
		if ( empty( $site_time ) ) {
			return $site_time;
		}

		try {
			// Get WordPress timezone.
			$timezone_string = wp_timezone_string();
			$site_timezone   = new \DateTimeZone( $timezone_string );
			$utc_timezone    = new \DateTimeZone( 'UTC' );

			// Create DateTime object with site timezone time.
			$datetime = \DateTime::createFromFormat( 'H:i', $site_time, $site_timezone );
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

	/**
	 * Get the current WordPress site timezone string.
	 *
	 * @return string Timezone string (e.g., 'America/New_York').
	 */
	public static function get_site_timezone_string() {
		return wp_timezone_string();
	}

	/**
	 * Get the current UTC offset for the site timezone.
	 *
	 * @return string UTC offset (e.g., '+05:00', '-08:00').
	 */
	public static function get_site_utc_offset() {
		try {
			$timezone_string = wp_timezone_string();
			$timezone        = new \DateTimeZone( $timezone_string );
			$datetime        = new \DateTime( 'now', $timezone );
			$offset_seconds  = $timezone->getOffset( $datetime );

			// Convert seconds to hours and minutes.
			$hours   = floor( abs( $offset_seconds ) / 3600 );
			$minutes = floor( ( abs( $offset_seconds ) % 3600 ) / 60 );
			$sign    = $offset_seconds >= 0 ? '+' : '-';

			return sprintf( '%s%02d:%02d', $sign, $hours, $minutes );
		} catch ( \Exception $e ) {
			return '+00:00';
		}
	}

	/**
	 * Get a display string for the current timezone.
	 *
	 * @return string Display string (e.g., 'UTC+05:00 (America/New_York)').
	 */
	public static function get_timezone_display_string() {
		$timezone_string = self::get_site_timezone_string();
		$utc_offset      = self::get_site_utc_offset();

		if ( 'UTC' === $timezone_string || '+00:00' === $utc_offset ) {
			return 'UTC';
		}

		return sprintf( 'UTC%s (%s)', $utc_offset, $timezone_string );
	}
}
