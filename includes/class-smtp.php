<?php
/**
 * Backward-compatible SMTP facade.
 *
 * @package Alert404
 */

namespace Alert404;

defined( 'ABSPATH' ) || exit;

class SMTP {
	/**
	 * Retrieve raw stored SMTP options.
	 *
	 * @return array<string, mixed>
	 */
	public static function get_options(): array {
		return MailSettingsRepository::get_options();
	}

	/**
	 * Retrieve normalized SMTP configuration.
	 *
	 * @return array<string, string|int>
	 */
	public static function get_config(): array {
		return MailSettingsRepository::get_config();
	}

	/**
	 * Backward-compatible config accessor.
	 *
	 * @return array<string, string|int>
	 */
	public static function get_smtp_config(): array {
		return self::get_config();
	}

	/**
	 * Normalize and sanitize posted SMTP settings.
	 *
	 * @param array<string, mixed> $input Submitted form data.
	 * @return array<string, string|int>
	 */
	public static function sanitize_options( array $input ): array {
		return MailSettingsRepository::sanitize_input( $input );
	}

	/**
	 * Backward-compatible password encryption helper.
	 */
	public static function encrypt_password_for_storage( string $password ): string {
		return SecretStore::encrypt( $password );
	}

	/**
	 * Determine whether SMTP can be used.
	 */
	public static function is_configured(): bool {
		return MailSettingsRepository::is_configured();
	}

	/**
	 * Send email using configured SMTP.
	 *
	 * @param array{
	 *     to: array<int, string>|string,
	 *     subject: string,
	 *     message: string,
	 *     headers?: array<int, string>,
	 *     text?: string
	 * } $args Email arguments.
	 * @return bool
	 */
	public static function send( array $args ): bool {
		$message = [
			'subject' => (string) ( $args['subject'] ?? '' ),
			'html'    => (string) ( $args['message'] ?? '' ),
			'text'    => (string) ( $args['text'] ?? '' ),
			'headers' => (array) ( $args['headers'] ?? [] ),
		];

		$result = SmtpTransport::send( $message, $args['to'] ?? [], MailSettingsRepository::get_config() );
		return $result['success'];
	}

	/**
	 * Test the SMTP connection.
	 *
	 * @param array<string, mixed> $config Optional override config.
	 * @return array{success: bool, message: string}
	 */
	public static function test_connection( array $config = [] ): array {
		$config = [] === $config ? MailSettingsRepository::get_config() : $config;
		return SmtpTransport::test_connection( $config );
	}
}
