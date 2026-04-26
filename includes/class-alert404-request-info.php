<?php
/**
 * Collects and enriches 404 request information.
 *
 * @package Alert404
 */

defined( 'ABSPATH' ) || exit;

/**
 * Collects and enriches information about 404 requests.
 */
class Alert404_Request_Info {
	/**
	 * Limites de taille pour les champs
	 */
	private const MAX_URL_LENGTH     = 2000;
	private const MAX_REFERER_LENGTH = 2000;
	private const MAX_USER_AGENT     = 500;

	/**
	 * Collecte toutes les informations disponibles sur la requête 404
	 *
	 * @return array<string, mixed> Informations complètes sur la requête
	 */
	public static function gather(): array {
		$server      = $_SERVER;
		$user_agent  = sanitize_text_field( wp_unslash( $server['HTTP_USER_AGENT'] ?? '' ) );
		$referrer    = sanitize_url( wp_unslash( $server['HTTP_REFERER'] ?? '' ) );
		$ip          = self::get_client_ip();
		$accept_lang = sanitize_text_field( wp_unslash( $server['HTTP_ACCEPT_LANGUAGE'] ?? '' ) );

		// Appliquer les limites de taille.
		$user_agent = self::truncate( $user_agent, self::MAX_USER_AGENT );
		$referrer   = self::truncate( $referrer, self::MAX_REFERER_LENGTH );

		// Parser le User-Agent.
		require_once ALERT404_DIR . 'includes/class-user-agent-parser.php';
		$user_info = Alert404_UserAgent_Parser::get_structured_info( $user_agent );

		// Informations WordPress.
		$wp_user      = wp_get_current_user();
		$is_logged_in = is_user_logged_in();

		$url = self::truncate( esc_url_raw( $server['REQUEST_URI'] ?? '' ), self::MAX_URL_LENGTH );

		return array(
			'url'            => $url,
			'full_url'       => self::get_full_url(),
			'method'         => sanitize_text_field( $server['REQUEST_METHOD'] ?? 'GET' ),
			'ip'             => $ip,
			'referrer'       => $referrer,
			'user_agent'     => $user_agent,
			'language'       => self::parse_accept_language( $accept_lang ),
			'browser'        => array(
				'name'    => $user_info['browser_name'],
				'version' => $user_info['browser_version'],
			),
			'os'             => array(
				'name'    => $user_info['os_name'],
				'version' => $user_info['os_version'],
			),
			'device'         => $user_info['device_type'],
			'user_readable'  => $user_info['readable'],
			'wordpress'      => array(
				'logged_in'  => $is_logged_in,
				'user_id'    => $is_logged_in ? $wp_user->ID : null,
				'user_name'  => $is_logged_in ? $wp_user->user_login : null,
				'user_email' => $is_logged_in ? $wp_user->user_email : null,
			),
			'timestamp'      => current_time( 'Y-m-d H:i:s' ),
			'timestamp_unix' => time(),
		);
	}

	/**
	 * Tronque une chaîne à une longueur maximale
	 *
	 * @param string $value Chaîne à tronquer.
	 * @param int    $max_length Longueur maximale.
	 * @return string Chaîne tronquée
	 */
	private static function truncate( string $value, int $max_length ): string {
		if ( strlen( $value ) > $max_length ) {
			return substr( $value, 0, $max_length );
		}
		return $value;
	}

	/**
	 * Récupère l'adresse IP du client en ne faisant confiance
	 * aux headers proxy que si REMOTE_ADDR est un proxy de confiance.
	 *
	 * @return string Adresse IP valide ou "Invalid"
	 */
	public static function get_client_ip(): string {
		$remote_ip = sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ?? '' ) );

		if ( ! filter_var( $remote_ip, FILTER_VALIDATE_IP ) ) {
			return 'Invalid';
		}

		if ( ! self::is_trusted_proxy( $remote_ip ) ) {
			return $remote_ip;
		}

		$forwarded_ip = self::get_forwarded_ip();

		if ( '' === $forwarded_ip ) {
			return $remote_ip;
		}

		return $forwarded_ip;
	}

	/**
	 * Retourne la première IP valide issue des headers proxy.
	 *
	 * @return string
	 */
	private static function get_forwarded_ip(): string {
		$headers = array(
			'HTTP_CF_CONNECTING_IP',
			'HTTP_X_FORWARDED_FOR',
			'HTTP_X_FORWARDED',
			'HTTP_FORWARDED_FOR',
			'HTTP_FORWARDED',
		);

		foreach ( $headers as $header ) {
			$raw = sanitize_text_field( wp_unslash( $_SERVER[ $header ] ?? '' ) );

			if ( '' === $raw ) {
				continue;
			}

			$ips = self::extract_ips_from_header( $raw, $header );

			foreach ( $ips as $ip ) {
				if ( filter_var( $ip, FILTER_VALIDATE_IP ) ) {
					return $ip;
				}
			}
		}

		return '';
	}

	/**
	 * Extrait les IP candidates depuis un header proxy.
	 *
	 * @param string $raw Valeur brute du header.
	 * @param string $header Nom du header.
	 * @return array<int, string>
	 */
	private static function extract_ips_from_header( string $raw, string $header ): array {
		if ( 'HTTP_FORWARDED' === $header ) {
			preg_match_all( '/for="?\[?([^\]";,]+)\]?"?/i', $raw, $matches );
			return array_map( 'trim', $matches[1] );
		}

		return array_map( 'trim', explode( ',', $raw ) );
	}

	/**
	 * Vérifie si une IP source est dans la liste des proxies de confiance.
	 *
	 * @param string $ip IP source (REMOTE_ADDR).
	 * @return bool
	 */
	private static function is_trusted_proxy( string $ip ): bool {
		$trusted_proxies = apply_filters( '404_alert_trusted_proxies', array() );

		if ( ! is_array( $trusted_proxies ) || empty( $trusted_proxies ) ) {
			return false;
		}

		foreach ( $trusted_proxies as $proxy ) {
			$proxy = sanitize_text_field( (string) $proxy );

			if ( self::ip_matches_range( $ip, $proxy ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Vérifie si une IP correspond à une IP/CIDR.
	 *
	 * @param string $ip IP à valider.
	 * @param string $range IP exacte ou réseau CIDR.
	 * @return bool
	 */
	private static function ip_matches_range( string $ip, string $range ): bool {
		if ( false === strpos( $range, '/' ) ) {
			return $ip === $range;
		}

		list( $subnet, $prefix ) = explode( '/', $range, 2 );
		$prefix                  = (int) $prefix;

		$ip_bin     = inet_pton( $ip );
		$subnet_bin = inet_pton( $subnet );

		if ( false === $ip_bin || false === $subnet_bin ) {
			return false;
		}

		if ( strlen( $ip_bin ) !== strlen( $subnet_bin ) ) {
			return false;
		}

		$max_prefix = 8 * strlen( $ip_bin );

		if ( $prefix < 0 || $prefix > $max_prefix ) {
			return false;
		}

		$full_bytes = intdiv( $prefix, 8 );
		$extra_bits = $prefix % 8;

		if ( $full_bytes > 0 && substr( $ip_bin, 0, $full_bytes ) !== substr( $subnet_bin, 0, $full_bytes ) ) {
			return false;
		}

		if ( 0 === $extra_bits ) {
			return true;
		}

		$mask = ( 0xFF << ( 8 - $extra_bits ) ) & 0xFF;

		return ( ord( $ip_bin[ $full_bytes ] ) & $mask ) === ( ord( $subnet_bin[ $full_bytes ] ) & $mask );
	}

	/**
	 * Construit l'URL complète de la requête
	 *
	 * @return string URL complète.
	 */
	private static function get_full_url(): string {
		$https       = isset( $_SERVER['HTTPS'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTPS'] ) ) : '';
		$server_port = isset( $_SERVER['SERVER_PORT'] ) ? (int) $_SERVER['SERVER_PORT'] : 80;
		$is_https    = ( ! empty( $https ) && 'off' !== strtolower( $https ) ) || 443 === $server_port;
		$protocol    = $is_https ? 'https://' : 'http://';
		$host        = sanitize_text_field( wp_unslash( $_SERVER['HTTP_HOST'] ?? '' ) );
		$path        = sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ?? '' ) );

		$full_url = $protocol . $host . $path;

		return self::truncate( esc_url_raw( $full_url ), self::MAX_URL_LENGTH );
	}

	/**
	 * Parse le header Accept-Language
	 *
	 * @param string $accept_lang Header Accept-Language.
	 * @return string Première langue préférée.
	 */
	private static function parse_accept_language( string $accept_lang ): string {
		if ( empty( $accept_lang ) ) {
			return 'Not specified';
		}

		// Extraire la première langue (avant le tiret et le point-virgule).
		preg_match( '/^([a-z]{2}(?:-[a-z]{2})?)/', strtolower( $accept_lang ), $matches );

		return $matches[1] ?? 'Not specified';
	}
}
