<?php
/**
 * Minimal mail status logger.
 *
 * @package Alert404
 */

namespace Alert404;

use function get_option;
use function update_option;
use function current_time;

defined( 'ABSPATH' ) || exit;

class MailLogger {
	private const OPTION_KEY = '404_alert_mail_status';

	/**
	 * Retrieve stored mail status.
	 *
	 * @return array<string, mixed>
	 */
	public static function get_status(): array {
		$status = get_option( self::OPTION_KEY, [] );
		return is_array( $status ) ? $status : [];
	}

	/**
	 * Record a successful mail send.
	 *
	 * @param array<string, mixed> $context Optional contextual data.
	 */
	public static function record_success( array $context = [] ): void {
		$status = self::get_status();
		$status['last_success'] = [
			'time'    => current_time( 'mysql' ),
			'context' => $context,
		];
		unset( $status['last_error'] );
		update_option( self::OPTION_KEY, $status );
	}

	/**
	 * Record a failed mail send.
	 *
	 * @param string               $message Error message.
	 * @param array<string, mixed> $context Optional contextual data.
	 */
	public static function record_failure( string $message, array $context = [] ): void {
		$status = self::get_status();
		$status['last_error'] = [
			'time'    => current_time( 'mysql' ),
			'message' => $message,
			'context' => $context,
		];
		update_option( self::OPTION_KEY, $status );
	}
}
