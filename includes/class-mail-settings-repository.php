<?php
/**
 * SMTP settings repository.
 *
 * @package Alert404
 */

namespace Alert404;

use function get_bloginfo;
use function get_option;
use function sanitize_email;
use function sanitize_text_field;
use function wp_unslash;

defined( 'ABSPATH' ) || exit;

class MailSettingsRepository {
	private const OPTION_KEY = '404_alert_smtp_options';

	/**
	 * Retrieve raw stored options.
	 *
	 * @return array<string, mixed>
	 */
	public static function get_options(): array {
		$options = get_option( self::OPTION_KEY, [] );
		return is_array( $options ) ? $options : [];
	}

	/**
	 * Retrieve normalized config for runtime use.
	 *
	 * @return array<string, string|int>
	 */
	public static function get_config(): array {
		$options = self::get_options();

		return [
			'host'       => sanitize_text_field( (string) ( $options['host'] ?? '' ) ),
			'port'       => self::normalize_port( $options['port'] ?? 587 ),
			'encryption' => self::normalize_encryption( (string) ( $options['encryption'] ?? 'tls' ) ),
			'username'   => sanitize_text_field( (string) ( $options['username'] ?? '' ) ),
			'password'   => SecretStore::decrypt( (string) ( $options['password'] ?? '' ) ),
			'from_email' => self::normalize_from_email( $options['from_email'] ?? '' ),
			'from_name'  => self::normalize_from_name( $options['from_name'] ?? '' ),
		];
	}

	/**
	 * Sanitize posted SMTP settings.
	 *
	 * @param array<string, mixed> $input Raw form data.
	 * @return array<string, string|int>
	 */
	public static function sanitize_input( array $input ): array {
		$existing = self::get_options();
		$password = isset( $input['password'] ) ? trim( (string) wp_unslash( $input['password'] ) ) : '';

		return [
			'host'       => sanitize_text_field( (string) wp_unslash( $input['host'] ?? '' ) ),
			'port'       => self::normalize_port( $input['port'] ?? 587 ),
			'encryption' => self::normalize_encryption( (string) wp_unslash( $input['encryption'] ?? 'tls' ) ),
			'username'   => sanitize_text_field( (string) wp_unslash( $input['username'] ?? '' ) ),
			'password'   => '' !== $password ? SecretStore::encrypt( $password ) : (string) ( $existing['password'] ?? '' ),
			'from_email' => self::normalize_from_email( $input['from_email'] ?? '' ),
			'from_name'  => self::normalize_from_name( $input['from_name'] ?? '' ),
		];
	}

	/**
	 * Whether SMTP settings are usable.
	 */
	public static function is_configured(): bool {
		$config = self::get_config();

		return '' !== $config['host']
			&& '' !== $config['username']
			&& '' !== $config['password']
			&& filter_var( $config['from_email'], FILTER_VALIDATE_EMAIL );
	}

	/**
	 * Determine whether a SMTP password is currently stored.
	 */
	public static function has_password(): bool {
		$config = self::get_config();
		return '' !== (string) $config['password'];
	}

	/**
	 * Return a transport summary for admin display.
	 *
	 * @return array{configured: bool, transport: string, sendmail_available: bool, message: string}
	 */
	public static function get_transport_summary(): array {
		$configured = self::is_configured();
		$sendmail   = MailHealthChecker::is_sendmail_available();

		if ( $configured ) {
			return [
				'configured'          => true,
				'transport'           => 'smtp',
				'sendmail_available'  => $sendmail,
				'message'             => 'SMTP is configured and will be used for outgoing mail.',
			];
		}

		if ( $sendmail ) {
			return [
				'configured'          => false,
				'transport'           => 'wp_mail',
				'sendmail_available'  => true,
				'message'             => 'SMTP is not configured. WordPress will fall back to wp_mail() via sendmail.',
			];
		}

		return [
			'configured'          => false,
			'transport'           => 'unavailable',
			'sendmail_available'  => false,
			'message'             => 'No SMTP settings are saved and the server does not expose a working sendmail binary.',
		];
	}

	/**
	 * Normalize port.
	 */
	private static function normalize_port( $port ): int {
		$port = (int) $port;
		return max( 1, min( 65535, $port ) );
	}

	/**
	 * Normalize encryption.
	 */
	private static function normalize_encryption( string $encryption ): string {
		$encryption = strtolower( trim( $encryption ) );
		return in_array( $encryption, [ 'tls', 'ssl', 'none' ], true ) ? $encryption : 'tls';
	}

	/**
	 * Normalize sender email.
	 */
	private static function normalize_from_email( $email ): string {
		$email = sanitize_email( (string) $email );
		if ( '' === $email ) {
			$email = sanitize_email( (string) get_option( 'admin_email', '' ) );
		}

		return $email;
	}

	/**
	 * Normalize sender name.
	 */
	private static function normalize_from_name( $name ): string {
		$name = sanitize_text_field( (string) $name );
		if ( '' === $name ) {
			$name = (string) get_bloginfo( 'name' );
		}

		return $name;
	}
}
