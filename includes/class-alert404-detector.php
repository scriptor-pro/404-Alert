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
	 * Initialize the 404 error detector
	 * Registers the WordPress hook to intercept 404 errors
	 *
	 * @return void
	 */
	public static function init(): void {
		add_action( 'template_redirect', array( self::class, 'on_template_redirect' ) );
	}

	/**
	 * Callback for the template_redirect hook
	 * Detects 404 errors, validates the IP, and sends notifications
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
	 * Extract and validate the source IP
	 * Supports proxies with HTTP_X_FORWARDED_FOR
	 *
	 * @return string Valid IP or empty string if invalid
	 */
	private static function get_ip(): string {
		require_once ALERT404_DIR . 'includes/class-request-info.php';

		$ip = Alert404_Request_Info::get_client_ip();

		return 'Invalid' === $ip ? '' : $ip;
	}

	/**
	 * Collect information about the 404 request
	 * Compile useful data for notifications
	 *
	 * @param string $ip Validated source IP address (unused, Alert404_Request_Info retrieves it).
	 * @return array<string, mixed> Array containing all enriched information.
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
