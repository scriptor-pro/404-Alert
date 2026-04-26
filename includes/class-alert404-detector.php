<?php
/**
 * 404 error detection and data collection.
 *
 * @package Alert404
 */

defined( 'ABSPATH' ) || exit;

/**
 * 404 error detection and data collection.
 */
class Alert404_Detector {
	/**
	 * Initialise le détecteur d'erreurs 404
	 * Enregistre le hook WordPress pour intercepter les erreurs 404
	 *
	 * @return void
	 */
	public static function init(): void {
		add_action( 'template_redirect', array( self::class, 'on_template_redirect' ) );
	}

	/**
	 * Callback du hook template_redirect
	 * Détecte les erreurs 404, valide l'IP et envoie les notifications
	 *
	 * @return void
	 */
	public static function on_template_redirect(): void {
		if ( ! is_404() ) {
			return;
		}

		$ip = self::get_ip();

		// Validate IP before proceeding.
		if ( empty( $ip ) || ! filter_var( $ip, FILTER_VALIDATE_IP ) ) {
			Alert404_Logger::log_invalid_ip( $ip );
			return;
		}

		// Collect request data.
		$payload = self::collect_payload( $ip );

		// Apply rate limiting.
		if ( ! Alert404_RateLimiter::check_and_increment( $ip ) ) {
			// Rate limit exceeded (see rate limiter for details).
			return;
		}

		// Send email notification.
		Alert404_Mailer::send( $payload );
	}

	/**
	 * Extrait et valide l'IP source
	 * Supporte les proxies avec HTTP_X_FORWARDED_FOR
	 *
	 * @return string IP valide ou chaîne vide si invalide
	 */
	private static function get_ip(): string {
		require_once ALERT404_DIR . 'includes/class-request-info.php';

		$ip = Alert404_Request_Info::get_client_ip();

		return 'Invalid' === $ip ? '' : $ip;
	}

	/**
	 * Collecte les informations sur la requête 404
	 * Compile les données utiles pour les notifications
	 *
	 * @param string $ip Adresse IP source validée (non utilisée, Alert404_Request_Info la récupère).
	 * @return array<string, mixed> Tableau contenant toutes les informations enrichies.
	 */
	private static function collect_payload( string $ip ): array {
		require_once ALERT404_DIR . 'includes/class-request-info.php';

		try {
			$request_info = Alert404_Request_Info::gather();
		} catch ( Throwable $e ) {
			Alert404_Logger::log_invalid_ip( 'Payload collection failed: ' . $e->getMessage() );
			$fallback_url = isset( $_SERVER['REQUEST_URI'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : 'unknown';

			return array(
				'ip'    => $ip,
				'url'   => $fallback_url,
				'error' => 'Failed to gather request info',
			);
		}

		return $request_info;
	}
}
