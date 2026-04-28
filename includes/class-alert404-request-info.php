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
	 * Size limits for fields
	 */
	private const MAX_URL_LENGTH     = 2000;
	private const MAX_REFERER_LENGTH = 2000;
	private const MAX_USER_AGENT     = 500;

	/**
	 * Collect all available information about the 404 request
	 *
	 * @return array<string, mixed> Complete request information
	 */
	public static function gather(): array {
		$server      = $_SERVER;
		$user_agent  = sanitize_text_field( wp_unslash( $server['HTTP_USER_AGENT'] ?? '' ) );
		$referrer    = sanitize_url( wp_unslash( $server['HTTP_REFERER'] ?? '' ) );
		$ip          = self::get_client_ip();
		$accept_lang = sanitize_text_field( wp_unslash( $server['HTTP_ACCEPT_LANGUAGE'] ?? '' ) );

		// Apply size limits.
		$user_agent = self::truncate( $user_agent, self::MAX_USER_AGENT );
		$referrer   = self::truncate( $referrer, self::MAX_REFERER_LENGTH );

		// Parse the User-Agent.
		require_once ALERT404_DIR . 'includes/class-user-agent-parser.php';
		$user_info = Alert404_UserAgent_Parser::get_structured_info( $user_agent );

		// WordPress information.
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
	 * Truncate a string to a maximum length
	 *
	 * @param string $value String to truncate.
	 * @param int    $max_length Maximum length.
	 * @return string Truncated string
	 */
	private static function truncate( string $value, int $max_length ): string {
		if ( strlen( $value ) > $max_length ) {
			return substr( $value, 0, $max_length );
		}
		return $value;
	}

	/**
	 * Get the client IP address while only trusting
	 * proxy headers if REMOTE_ADDR is a trusted proxy.
	 *
	 * @return string Valid IP address or "Invalid"
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
	 * Returns the first valid IP from proxy headers.
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
	 * Extract candidate IPs from a proxy header.
	 *
	 * @param string $raw Raw header value.
	 * @param string $header Header name.
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
	 * Check if a source IP is in the list of trusted proxies.
	 *
	 * @param string $ip Source IP (REMOTE_ADDR).
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
	 * Check if an IP matches an IP/CIDR range.
	 *
	 * @param string $ip IP to validate.
	 * @param string $range Exact IP or CIDR network.
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
	 * Build the complete URL of the request
	 *
	 * @return string Complete URL.
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
	 * Parse the Accept-Language header
	 *
	 * @param string $accept_lang Accept-Language header.
	 * @return string First preferred language.
	 */
	private static function parse_accept_language( string $accept_lang ): string {
		if ( empty( $accept_lang ) ) {
			return 'Not specified';
		}

		// Extract the first language (before hyphen and semicolon).
		preg_match( '/^([a-z]{2}(?:-[a-z]{2})?)/', strtolower( $accept_lang ), $matches );

		return $matches[1] ?? 'Not specified';
	}
}
