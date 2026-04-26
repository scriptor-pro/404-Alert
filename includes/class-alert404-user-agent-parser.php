<?php
/**
 * User-Agent parser to extract client information.
 *
 * @package Alert404
 * Detects OS, browser, and device type.
 */

defined( 'ABSPATH' ) || exit;

/**
 * User-Agent parser to extract client information.
 */
class Alert404_UserAgent_Parser {
	/**
	 * Parse le User-Agent et retourne les informations
	 *
	 * @param string $user_agent String User-Agent HTTP
	 * @return array Informations parsées (browser, os, device_type)
	 */
	public static function parse( string $user_agent ): array {
		return array(
			'browser'     => self::detect_browser( $user_agent ),
			'os'          => self::detect_os( $user_agent ),
			'device_type' => self::detect_device_type( $user_agent ),
		);
	}

	/**
	 * Détecte le navigateur à partir du User-Agent
	 *
	 * @param string $user_agent User-Agent string
	 * @return array ['name' => string, 'version' => string]
	 */
	private static function detect_browser( string $user_agent ): array {
		$name    = 'Unknown';
		$version = 'Unknown';

		// Chrome (inclut Chromium, Edge basé sur Chromium)
		if ( preg_match( '/Chrome\/(\d+(?:\.\d+)*)/', $user_agent, $matches ) ) {
			$name    = 'Chrome';
			$version = $matches[1];
		} elseif ( preg_match( '/Firefox\/(\d+(?:\.\d+)*)/', $user_agent, $matches ) ) {
			// Firefox.
			$name    = 'Firefox';
			$version = $matches[1];
		} elseif ( preg_match( '/Safari\//', $user_agent ) && preg_match( '/Version\/(\d+(?:\.\d+)*)/', $user_agent, $matches ) ) {
			// Safari (avant Chrome).
			$name    = 'Safari';
			$version = $matches[1];
		} elseif ( preg_match( '/Edge\/(\d+(?:\.\d+)*)/', $user_agent, $matches ) ) {
			// Edge legacy.
			$name    = 'Edge (Legacy)';
			$version = $matches[1];
		} elseif ( preg_match( '/Edg\/(\d+(?:\.\d+)*)/', $user_agent, $matches ) ) {
			// Edge Chromium.
			$name    = 'Edge';
			$version = $matches[1];
		} elseif ( preg_match( '/Opera\/(\d+(?:\.\d+)*)/', $user_agent, $matches ) ) {
			// Opera.
			$name    = 'Opera';
			$version = $matches[1];
		} elseif ( preg_match( '/MSIE (\d+(?:\.\d+)*)/', $user_agent, $matches ) ) {
			// Internet Explorer.
			$name    = 'Internet Explorer';
			$version = $matches[1];
		} elseif ( preg_match( '/Trident\//', $user_agent ) ) {
			// Trident (IE 11).
			$name = 'Internet Explorer 11';
		}//end if

		return array(
			'name'    => $name,
			'version' => $version,
		);
	}

	/**
	 * Détecte le système d'exploitation à partir du User-Agent
	 *
	 * @param string $user_agent User-Agent string
	 * @return array ['name' => string, 'version' => string]
	 */
	private static function detect_os( string $user_agent ): array {
		$name    = 'Unknown';
		$version = 'Unknown';

		// Windows
		if ( preg_match( '/Windows NT (\d+\.\d+)/', $user_agent, $matches ) ) {
			$name    = self::get_windows_name( $matches[1] );
			$version = $matches[1];
		} elseif ( preg_match( '/Mac OS X ([0-9._]+)/', $user_agent, $matches ) ) {
			// macOS.
			$name    = 'macOS';
			$version = str_replace( '_', '.', $matches[1] );
		} elseif ( preg_match( '/OS (\d+_\d+(?:_\d+)?)/', $user_agent, $matches ) ) {
			// iOS.
			$name    = 'iOS';
			$version = str_replace( '_', '.', $matches[1] );
		} elseif ( preg_match( '/Android ([0-9.]+)/', $user_agent, $matches ) ) {
			// Android.
			$name    = 'Android';
			$version = $matches[1];
		} elseif ( false !== strpos( $user_agent, 'Linux' ) ) {
			// Linux.
			$name = 'Linux';
		} elseif ( false !== strpos( $user_agent, 'Ubuntu' ) ) {
			// Ubuntu.
			$name = 'Ubuntu';
		}//end if

		return array(
			'name'    => $name,
			'version' => $version,
		);
	}

	/**
	 * Détecte le type d'appareil (Desktop, Mobile, Tablet)
	 *
	 * @param string $user_agent User-Agent string
	 * @return string Type d'appareil
	 */
	private static function detect_device_type( string $user_agent ): string {
		// Mobile phones
		if ( preg_match( '/(iPhone|Android|Mobile|Windows Phone|IEMobile|Opera Mini)/', $user_agent ) ) {
			return 'Mobile';
		}

		// Tablets
		if ( preg_match( '/(iPad|Android|Tablet)/', $user_agent ) ) {
			return 'Tablet';
		}

		return 'Desktop';
	}

	/**
	 * Convertit le numéro de version Windows en nom lisible
	 *
	 * @param string $version Version NT
	 * @return string Nom Windows
	 */
	private static function get_windows_name( string $version ): string {
		$versions = array(
			'10.0' => 'Windows 11/10',
			'6.3'  => 'Windows 8.1',
			'6.2'  => 'Windows 8',
			'6.1'  => 'Windows 7',
			'6.0'  => 'Windows Vista',
			'5.2'  => 'Windows XP Pro',
			'5.1'  => 'Windows XP',
		);

		foreach ( $versions as $nt_version => $name ) {
			if ( strpos( $version, $nt_version ) === 0 ) {
				return $name;
			}
		}

		return 'Windows';
	}

	/**
	 * Formate les informations parsées en texte lisible
	 *
	 * @param array $parsed_info Résultat de parse()
	 * @return string Texte formaté
	 */
	public static function format_human_readable( array $parsed_info ): string {
		$browser = $parsed_info['browser'];
		$os      = $parsed_info['os'];
		$device  = $parsed_info['device_type'];

		$browser_str = $browser['name'];
		if ( 'Unknown' !== $browser['version'] ) {
			$browser_str .= ' ' . $browser['version'];
		}

		$os_str = $os['name'];
		if ( 'Unknown' !== $os['version'] ) {
			$os_str .= ' ' . $os['version'];
		}

		return sprintf(
			'%s sur %s (%s)',
			$browser_str,
			$os_str,
			$device
		);
	}

	/**
	 * Retourne les informations parsées dans un format structuré
	 *
	 * @param string $user_agent User-Agent string
	 * @return array Informations structurées
	 */
	public static function get_structured_info( string $user_agent ): array {
		$parsed = self::parse( $user_agent );

		return array(
			'browser_name'    => $parsed['browser']['name'],
			'browser_version' => $parsed['browser']['version'],
			'os_name'         => $parsed['os']['name'],
			'os_version'      => $parsed['os']['version'],
			'device_type'     => $parsed['device_type'],
			'readable'        => self::format_human_readable( $parsed ),
		);
	}
}
