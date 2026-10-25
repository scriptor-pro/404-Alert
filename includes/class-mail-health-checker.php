<?php
/**
 * Mail health diagnostics.
 *
 * @package Alert404
 */

namespace Alert404;

defined( 'ABSPATH' ) || exit;

class MailHealthChecker {
	/**
	 * Check whether the local sendmail binary is available.
	 */
	public static function is_sendmail_available(): bool {
		$path = trim( (string) ini_get( 'sendmail_path' ) );
		if ( '' === $path ) {
			return false;
		}

		$binary = strtok( $path, ' ' );
		return is_string( $binary ) && '' !== $binary && is_file( $binary ) && is_executable( $binary );
	}

	/**
	 * Get a lightweight transport health summary.
	 *
	 * @return array{configured: bool, transport: string, ready: bool, message: string, last_error: string}
	 */
	public static function check(): array {
		$configured = MailSettingsRepository::is_configured();
		$sendmail   = self::is_sendmail_available();
		$last_error  = '';
		$status     = MailLogger::get_status();

		if ( isset( $status['last_error']['message'] ) ) {
			$last_error = (string) $status['last_error']['message'];
		}

		if ( $configured ) {
			return [
				'configured' => true,
				'transport'  => 'smtp',
				'ready'      => true,
				'message'    => 'SMTP is configured. Use the Test SMTP button to verify connectivity.',
				'last_error' => $last_error,
			];
		}

		if ( $sendmail ) {
			return [
				'configured' => false,
				'transport'  => 'wp_mail',
				'ready'      => true,
				'message'    => 'SMTP is not configured. WordPress will use wp_mail() via sendmail.',
				'last_error' => $last_error,
			];
		}

		return [
			'configured' => false,
			'transport'  => 'unavailable',
			'ready'      => false,
			'message'    => 'No SMTP settings are saved and the server does not expose a working sendmail binary.',
			'last_error' => $last_error,
		];
	}
}
