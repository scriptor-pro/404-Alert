<?php
/**
 * Rate limiting for 404 Alert plugin.
 *
 * @package Alert404
 * Uses Redis for atomicity with fallback to simple transients.
 */

defined( 'ABSPATH' ) || exit;

/**
 * Rate limiting manager for 404 alerts.
 */
class Alert404_RateLimiter {
	/**
	 * Check and increment rate limits (IP + global)
	 * Uses Redis for atomicity, fallback to simple transients if unavailable
	 *
	 * @param string $ip Source IP address.
	 * @return bool true if OK, false if limited.
	 */
	public static function check_and_increment( string $ip ): bool {
		$options     = get_option( '404_alert_options', array() );
		$cooldown    = $options['ip_cooldown'] ?? 300;
		$daily_limit = $options['daily_limit'] ?? 500;

		// Rate limit by IP.
		if ( ! self::check_ip_limit( $ip, $cooldown ) ) {
			return false;
		}

		// Global daily rate limit.
		if ( ! self::check_daily_limit( $daily_limit ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Check IP cooldown atomically
	 * Prefers Redis but falls back to simple transients
	 *
	 * @param string $ip Source IP address.
	 * @param int    $cooldown Cooldown in seconds.
	 * @return bool true if request is allowed, false if blocked.
	 */
	private static function check_ip_limit( string $ip, int $cooldown ): bool {
		// Use Redis if available.
		if ( Alert404_Redis_Handler::is_available() ) {
			return self::check_ip_limit_redis( $ip, $cooldown );
		}

		// Fallback to simple transients (best-effort).
		return self::check_ip_limit_transient( $ip, $cooldown );
	}

	/**
	 * Check IP cooldown with Redis (ATOMIC)
	 *
	 * @param string $ip Source IP address.
	 * @param int    $cooldown Cooldown in seconds.
	 * @return bool true if request is allowed, false if blocked.
	 */
	private static function check_ip_limit_redis( string $ip, int $cooldown ): bool {
		$key = '404_alert_ip_' . wp_hash( $ip );

		// GET the last time this IP visited.
		$last = Alert404_Redis_Handler::get( $key );

		if ( false !== $last && ( time() - (int) $last ) < $cooldown ) {
			Alert404_Logger::log_rate_limit_ip( $ip, $cooldown );
			return false;
			// Blocked.
		}

		// Set new timestamp (atomic).
		Alert404_Redis_Handler::set( $key, time(), $cooldown );
		return true;
		// Allowed.
	}

	/**
	 * Check IP cooldown with transients (best-effort, possible race condition)
	 * Used as fallback if Redis is unavailable
	 *
	 * @param string $ip Source IP address.
	 * @param int    $cooldown Cooldown in seconds.
	 * @return bool true if request is allowed, false if blocked.
	 */
	private static function check_ip_limit_transient( string $ip, int $cooldown ): bool {
		$key  = '404_alert_ip_' . wp_hash( $ip );
		$last = get_transient( $key );

		if ( false !== $last && ( time() - (int) $last ) < $cooldown ) {
			Alert404_Logger::log_rate_limit_ip( $ip, $cooldown );
			return false;
			// Blocked.
		}

		// Race condition possible here, but acceptable for fallback..
		set_transient( $key, time(), $cooldown );
		return true;
		// Allowed.
	}

	/**
	 * Check daily limit atomically
	 *
	 * @param int $daily_limit Maximum number of emails per day.
	 * @return bool true if limit not reached, false if exceeded.
	 */
	private static function check_daily_limit( int $daily_limit ): bool {
		// Use Redis if available.
		if ( Alert404_Redis_Handler::is_available() ) {
			return self::check_daily_limit_redis( $daily_limit );
		}

		// Fallback to simple transients..
		return self::check_daily_limit_transient( $daily_limit );
	}

	/**
	 * Check daily limit with Redis (ATOMIC)
	 *
	 * @param int $daily_limit Maximum number of emails per day.
	 * @return bool true if limit not reached, false if exceeded.
	 */
	private static function check_daily_limit_redis( int $daily_limit ): bool {
		$day_key = '404_alert_global_' . gmdate( 'Y-m-d' );

		// Calculate TTL until UTC midnight.
		$next_midnight = strtotime( 'tomorrow 00:00:00', current_time( 'timestamp' ) )
						- current_time( 'timestamp' );
		$ttl           = (int) max( 60, (int) $next_midnight );

		// INCR is atomic in Redis.
		$count = Alert404_Redis_Handler::increment( $day_key, $ttl );

		if ( false === $count ) {
			// Redis error, allow through (fail open).
			return true;
		}

		if ( $count > $daily_limit ) {
			Alert404_Logger::log_rate_limit_daily( $daily_limit );
			return false;
			// Blocked.
		}

		return true;
		// Allowed.
	}

	/**
	 * Check daily limit with transients (best-effort)
	 *
	 * @param int $daily_limit Maximum number of emails per day.
	 * @return bool true if limit not reached, false if exceeded.
	 */
	private static function check_daily_limit_transient( int $daily_limit ): bool {
		$day_key   = '404_alert_global_' . gmdate( 'Y-m-d' );
		$day_count = get_transient( $day_key );
		$count     = false === $day_count ? 0 : (int) $day_count;

		if ( $count >= $daily_limit ) {
			Alert404_Logger::log_rate_limit_daily( $daily_limit );
			return false;
			// Blocked.
		}

		// Calculate TTL until UTC midnight.
		$next_midnight = strtotime( 'tomorrow 00:00:00', current_time( 'timestamp' ) )
						- current_time( 'timestamp' );
		$expiration    = (int) max( 60, (int) $next_midnight );

		// Race condition possible here, but acceptable for fallback..
		set_transient( $day_key, $count + 1, $expiration );
		return true;
		// Allowed.
	}
}
