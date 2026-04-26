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
	 * Vérifie et incrémente les rate limits (IP + global)
	 * Utilise Redis pour garantir l'atomicité, fallback à transients simples si indisponible
	 *
	 * @param string $ip Adresse IP source.
	 * @return bool true si OK, false si limité.
	 */
	public static function check_and_increment( string $ip ): bool {
		$options     = get_option( '404_alert_options', array() );
		$cooldown    = $options['ip_cooldown'] ?? 300;
		$daily_limit = $options['daily_limit'] ?? 500;

		// Rate limit par IP
		if ( ! self::check_ip_limit( $ip, $cooldown ) ) {
			return false;
		}

		// Rate limit global journalier.
		if ( ! self::check_daily_limit( $daily_limit ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Vérifie le cooldown par IP de manière atomique
	 * Préfère Redis mais fallback à transients simples
	 *
	 * @param string $ip Adresse IP source.
	 * @param int    $cooldown Cooldown en secondes.
	 * @return bool true si la requête est autorisée, false si elle est bloquée.
	 */
	private static function check_ip_limit( string $ip, int $cooldown ): bool {
		// Utiliser Redis si disponible.
		if ( Alert404_Redis_Handler::is_available() ) {
			return self::check_ip_limit_redis( $ip, $cooldown );
		}

		// Fallback à transients simples (best-effort)
		return self::check_ip_limit_transient( $ip, $cooldown );
	}

	/**
	 * Vérifie le cooldown par IP avec Redis (ATOMIQUE)
	 *
	 * @param string $ip Adresse IP source.
	 * @param int    $cooldown Cooldown en secondes.
	 * @return bool true si la requête est autorisée, false si elle est bloquée.
	 */
	private static function check_ip_limit_redis( string $ip, int $cooldown ): bool {
		$key = '404_alert_ip_' . wp_hash( $ip );

		// GET la dernière fois que cette IP a visité.
		$last = Alert404_Redis_Handler::get( $key );

		if ( false !== $last && ( time() - (int) $last ) < $cooldown ) {
			Alert404_Logger::log_rate_limit_ip( $ip, $cooldown );
			return false;
			// Bloquée.
		}

		// SET le nouveau timestamp (atomique)
		Alert404_Redis_Handler::set( $key, time(), $cooldown );
		return true;
		// Autorisée.
	}

	/**
	 * Vérifie le cooldown par IP avec transients (best-effort, peut avoir dépassement)
	 * Utilisé comme fallback si Redis indisponible
	 *
	 * @param string $ip Adresse IP source.
	 * @param int    $cooldown Cooldown en secondes.
	 * @return bool true si la requête est autorisée, false si elle est bloquée.
	 */
	private static function check_ip_limit_transient( string $ip, int $cooldown ): bool {
		$key  = '404_alert_ip_' . wp_hash( $ip );
		$last = get_transient( $key );

		if ( false !== $last && ( time() - (int) $last ) < $cooldown ) {
			Alert404_Logger::log_rate_limit_ip( $ip, $cooldown );
			return false;
			// Bloquée.
		}

		// Race condition possible ici, mais acceptable en fallback.
		set_transient( $key, time(), $cooldown );
		return true;
		// Autorisée.
	}

	/**
	 * Vérifie la limite quotidienne de manière atomique
	 *
	 * @param int $daily_limit Nombre max d'emails par jour.
	 * @return bool true si la limite n'est pas atteinte, false si elle est dépassée.
	 */
	private static function check_daily_limit( int $daily_limit ): bool {
		// Utiliser Redis si disponible.
		if ( Alert404_Redis_Handler::is_available() ) {
			return self::check_daily_limit_redis( $daily_limit );
		}

		// Fallback à transients simples.
		return self::check_daily_limit_transient( $daily_limit );
	}

	/**
	 * Vérifie la limite quotidienne avec Redis (ATOMIQUE)
	 *
	 * @param int $daily_limit Nombre max d'emails par jour.
	 * @return bool true si la limite n'est pas atteinte, false si elle est dépassée.
	 */
	private static function check_daily_limit_redis( int $daily_limit ): bool {
		$day_key = '404_alert_global_' . gmdate( 'Y-m-d' );

		// Calcul du TTL jusqu'à minuit UTC
		$next_midnight = strtotime( 'tomorrow 00:00:00', current_time( 'timestamp' ) )
						- current_time( 'timestamp' );
		$ttl           = (int) max( 60, (int) $next_midnight );

		// INCR est atomique dans Redis.
		$count = Alert404_Redis_Handler::increment( $day_key, $ttl );

		if ( false === $count ) {
			// Redis erreur, laisser passer (fail open)
			return true;
		}

		if ( $count > $daily_limit ) {
			Alert404_Logger::log_rate_limit_daily( $daily_limit );
			return false;
			// Bloquée.
		}

		return true;
		// Autorisée.
	}

	/**
	 * Vérifie la limite quotidienne avec transients (best-effort)
	 *
	 * @param int $daily_limit Nombre max d'emails par jour.
	 * @return bool true si la limite n'est pas atteinte, false si elle est dépassée.
	 */
	private static function check_daily_limit_transient( int $daily_limit ): bool {
		$day_key   = '404_alert_global_' . gmdate( 'Y-m-d' );
		$day_count = get_transient( $day_key );
		$count     = false === $day_count ? 0 : (int) $day_count;

		if ( $count >= $daily_limit ) {
			Alert404_Logger::log_rate_limit_daily( $daily_limit );
			return false;
			// Bloquée.
		}

		// Calcul du TTL jusqu'à minuit UTC
		$next_midnight = strtotime( 'tomorrow 00:00:00', current_time( 'timestamp' ) )
						- current_time( 'timestamp' );
		$expiration    = (int) max( 60, (int) $next_midnight );

		// Race condition possible ici, mais acceptable en fallback.
		set_transient( $day_key, $count + 1, $expiration );
		return true;
		// Autorisée.
	}
}
