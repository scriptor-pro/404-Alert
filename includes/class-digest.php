<?php
/**
 * Daily digest collector and sender
 *
 * @package Alert404
 */

namespace Alert404;

use function get_transient;
use function set_transient;
use function delete_transient;

defined( 'ABSPATH' ) || exit;

class Digest {
	private const TRANSIENT_KEY = '404_alert_digest';

	/**
	 * Add URL to digest (when exceeds daily limit)
	 */
	public static function add_to_digest( string $url ): void {
		$urls = self::get_digest_urls();

		if ( ! in_array( $url, $urls, true ) ) {
			$urls[] = $url;
			set_transient( self::TRANSIENT_KEY, $urls, DAY_IN_SECONDS );
		}
	}

	/**
	 * Get all URLs in digest
	 */
	public static function get_digest_urls(): array {
		$urls = get_transient( self::TRANSIENT_KEY );
		return is_array( $urls ) ? $urls : [];
	}

	/**
	 * Send and clear digest
	 */
	public static function send_and_clear(): bool {
		$urls = self::get_digest_urls();

		if ( empty( $urls ) ) {
			return true; // Nothing to send
		}

		$sent = Mailer::send_digest( $urls );

		if ( $sent ) {
			delete_transient( self::TRANSIENT_KEY );
		}

		return $sent;
	}

	/**
	 * Clear without sending
	 */
	public static function clear(): void {
		delete_transient( self::TRANSIENT_KEY );
	}
}
