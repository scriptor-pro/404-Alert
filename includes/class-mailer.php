<?php
/**
 * Email sending facade.
 *
 * @package Alert404
 */

namespace Alert404;

defined( 'ABSPATH' ) || exit;

class Mailer {
	/**
	 * Send alert email to all admins.
	 */
	public static function send_alert( string $url, string $ip, string $referrer ): bool {
		return MailService::send_alert( $url, $ip, $referrer );
	}

	/**
	 * Send digest email with multiple 404s.
	 *
	 * @param array<int, string> $urls URLs to include.
	 */
	public static function send_digest( array $urls ): bool {
		return MailService::send_digest( $urls );
	}
}
