<?php
/**
 * Centralized logging for 404 Alert events.
 *
 * @package Alert404
 * Logs events to wp-content/debug.log when WP_DEBUG_LOG is enabled.
 */

defined( 'ABSPATH' ) || exit;

/**
 * Centralized logging for 404 Alert events.
 */
class Alert404_Logger {
	/**
	 * Logue un événement 404 détecté
	 *
	 * @param string $ip Adresse IP source.
	 * @param string $url URL demandée.
	 * @param array  $context Données additionnelles (referrer, user_agent, etc.).
	 * @return void
	 */
	public static function log_404_detected( string $ip, string $url, array $context = array() ): void {
		self::log(
			'404_detected',
			array(
				'ip'  => $ip,
				'url' => $url,
				...$context,
			)
		);
	}

	/**
	 * Logue un refus de rate limit par IP
	 *
	 * @param string $ip Adresse IP source.
	 * @param int    $cooldown Délai de cooldown en secondes.
	 * @return void
	 */
	public static function log_rate_limit_ip( string $ip, int $cooldown ): void {
		self::log(
			'rate_limit_ip',
			array(
				'ip'       => $ip,
				'cooldown' => $cooldown,
			)
		);
	}

	/**
	 * Logue un refus de la limite quotidienne
	 *
	 * @param int $daily_limit Limite quotidienne configurée.
	 * @return void
	 */
	public static function log_rate_limit_daily( int $daily_limit ): void {
		self::log(
			'rate_limit_daily',
			array(
				'daily_limit' => $daily_limit,
			)
		);
	}

	/**
	 * Logue un succès d'envoi d'email
	 *
	 * @param string $to Destinataire de l'email.
	 * @param string $url URL qui a déclenché la notification.
	 * @return void
	 */
	public static function log_email_sent( string $to, string $url ): void {
		self::log(
			'email_sent',
			array(
				'to'  => $to,
				'url' => $url,
			)
		);
	}

	/**
	 * Logue un échec d'envoi d'email
	 *
	 * @param string $to Destinataire de l'email.
	 * @param string $reason Raison de l'échec (optionnel).
	 * @return void
	 */
	public static function log_email_failed( string $to, string $reason = '' ): void {
		self::log(
			'email_failed',
			array(
				'to'     => $to,
				'reason' => $reason,
			)
		);
	}

	/**
	 * Logue une IP invalide ignorée
	 *
	 * @param string $raw_ip IP brute reçue (non valide).
	 * @return void
	 */
	public static function log_invalid_ip( string $raw_ip ): void {
		self::log(
			'invalid_ip',
			array(
				'raw_ip' => $raw_ip,
			)
		);
	}

	/**
	 * Logue une erreur générale
	 *
	 * @param string $message Message d'erreur.
	 * @param array  $context Contexte additionnel.
	 * @return void
	 */
	public static function log_error( string $message, array $context = array() ): void {
		self::log(
			'error',
			array(
				'message' => $message,
				...$context,
			)
		);
	}

	/**
	 * Logue une erreur Redis
	 *
	 * @param string $message Message d'erreur.
	 * @return void
	 */
	public static function log_redis_error( string $message ): void {
		self::log(
			'redis_error',
			array(
				'message' => $message,
			)
		);
	}

	/**
	 * Logue l'indisponibilité de Redis
	 *
	 * @param string $reason Raison de l'indisponibilité.
	 * @return void
	 */
	public static function log_redis_unavailable( string $reason ): void {
		self::log(
			'redis_unavailable',
			array(
				'reason'   => $reason,
				'fallback' => 'Using transient-based rate limiting',
			)
		);
	}

	/**
	 * Logue une tentative de connexion SMTP
	 *
	 * @param string $host Serveur SMTP.
	 * @param int    $port Port SMTP.
	 * @param string $encryption Type de chiffrement (tls, ssl, none).
	 * @return void
	 */
	public static function log_smtp_connection_attempt( string $host, int $port, string $encryption ): void {
		self::log(
			'smtp_connection_attempt',
			array(
				'host'       => $host,
				'port'       => $port,
				'encryption' => $encryption,
			)
		);
	}

	/**
	 * Logue une erreur d'authentification SMTP
	 *
	 * @param string $host Serveur SMTP.
	 * @param string $username Nom d'utilisateur.
	 * @param string $error Message d'erreur.
	 * @return void
	 */
	public static function log_smtp_auth_failure( string $host, string $username, string $error ): void {
		self::log(
			'smtp_auth_failure',
			array(
				'host'     => $host,
				'username' => $username,
				'error'    => $error,
			)
		);
	}

	/**
	 * Logue un email envoyé avec succès via SMTP
	 *
	 * @param string $to Destinataire.
	 * @param string $from Adresse d'envoi.
	 * @return void
	 */
	public static function log_email_sent_via_smtp( string $to, string $from ): void {
		self::log(
			'email_sent_via_smtp',
			array(
				'to'     => $to,
				'from'   => $from,
				'method' => 'SMTP',
			)
		);
	}

	/**
	 * Logue un email envoyé via wp_mail (fallback)
	 *
	 * @param string $to Destinataire.
	 * @return void
	 */
	public static function log_email_sent_via_wp_mail( string $to ): void {
		self::log(
			'email_sent_via_wp_mail',
			array(
				'to'     => $to,
				'method' => 'wp_mail (fallback)',
			)
		);
	}

	/**
	 * Logue une reconnexion Redis après perte de connexion
	 *
	 * @param string $reason Raison de la reconnexion.
	 * @return void
	 */
	public static function log_redis_reconnected( string $reason ): void {
		self::log(
			'redis_reconnected',
			array(
				'reason' => $reason,
			)
		);
	}

	/**
	 * Logue un changement des options du plugin
	 *
	 * @param array $changed_options Options modifiées avec ancien et nouveau.
	 * @return void
	 */
	public static function log_options_changed( array $changed_options ): void {
		self::log(
			'options_changed',
			array(
				'changed_options' => $changed_options,
			)
		);
	}

	/**
	 * Logue un changement de configuration SMTP
	 *
	 * @param array $changed_options Options SMTP modifiées.
	 * @return void
	 */
	public static function log_smtp_config_changed( array $changed_options ): void {
		self::log(
			'smtp_config_changed',
			array(
				'changed_options' => $changed_options,
			)
		);
	}

	/**
	 * Enregistre un événement générique dans les logs
	 * N'écrit que si WP_DEBUG_LOG est activé
	 *
	 * @param string $event Nom de l'événement.
	 * @param array  $context Données contextuelles.
	 * @return void
	 */
	private static function log( string $event, array $context = array() ): void {
		$options       = get_option( '404_alert_options', array() );
		$force_logging = ! empty( $options['force_logging'] );

		if ( ! $force_logging && ( ! defined( 'WP_DEBUG_LOG' ) || ! WP_DEBUG_LOG ) ) {
			return;
		}

		// Formater le message
		$timestamp    = current_time( 'mysql' );
		$context_json = ! empty( $context ) ? wp_json_encode( $context, JSON_UNESCAPED_SLASHES ) : '{}';
		$message      = sprintf(
			'[404-Alert] [%s] %s: %s',
			$timestamp,
			$event,
			$context_json
		);

		// Enregistrer dans les logs WordPress
		error_log( $message );
	}
}
