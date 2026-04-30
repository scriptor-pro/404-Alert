<?php
/**
 * Rate limiting logic (24h per URL + daily plafond)
 *
 * @package Alert404
 */

namespace Alert404;

use function get_option;
use function get_transient;
use function set_transient;
use function current_time;

defined( 'ABSPATH' ) || exit;

class RateLimiter {
	private const TRANSIENT_URL_PREFIX = '404_alert_url_';
	private const TRANSIENT_DAILY_COUNT = '404_alert_daily_';
	private const URL_COOLDOWN_SECONDS = DAY_IN_SECONDS;

	/**
	 * Check if this URL should trigger an immediate alert
	 *
	 * @param string $url The request URL.
	 * @return bool False if already alerted in 24h or daily limit reached.
	 */
	public static function should_alert_immediately( string $url ): bool {
		$url_hash = md5( $url );

		// Check URL cooldown (24h)
		if ( get_transient( self::TRANSIENT_URL_PREFIX . $url_hash ) ) {
			return false;
		}

		// Check daily plafond
		$options     = get_option( '404_alert_options', [] );
		$daily_limit = (int) ( $options['daily_limit'] ?? 20 );
		$date        = current_time( 'Y-m-d' );

		if ( self::get_daily_count( $date ) >= $daily_limit ) {
			return false;
		}

		// Mark URL as alerted
		set_transient( self::TRANSIENT_URL_PREFIX . $url_hash, 1, self::URL_COOLDOWN_SECONDS );

		// Increment daily counter
		self::increment_daily_count( $date );

		return true;
	}

	/**
	 * Get current daily alert count
	 */
	private static function get_daily_count( string $date ): int {
		return (int) get_transient( self::TRANSIENT_DAILY_COUNT . $date );
	}

	/**
	 * Increment daily counter
	 */
	private static function increment_daily_count( string $date ): void {
		$count = self::get_daily_count( $date );
		set_transient( self::TRANSIENT_DAILY_COUNT . $date, $count + 1, DAY_IN_SECONDS );
	}
}
