<?php
/**
 * SMTP transport implementation.
 *
 * @package Alert404
 */

namespace Alert404;

use PHPMailer\PHPMailer\Exception as PHPMailer_Exception;
use PHPMailer\PHPMailer\PHPMailer;
use function sanitize_email;

defined( 'ABSPATH' ) || exit;

class SmtpTransport {
	/**
	 * Send a message through SMTP.
	 *
	 * @param array<string, string>   $message Message payload.
	 * @param array<int, string>|string $recipients Recipient list.
	 * @param array<string, string|int> $config SMTP config.
	 * @return array{success: bool, message: string}
	 */
	public static function send( array $message, array|string $recipients, array $config ): array {
		if ( ! MailSettingsRepository::is_configured() ) {
			return [
				'success' => false,
				'message' => 'SMTP configuration is incomplete.',
			];
		}

		require_once ABSPATH . WPINC . '/PHPMailer/Exception.php';
		require_once ABSPATH . WPINC . '/PHPMailer/PHPMailer.php';
		require_once ABSPATH . WPINC . '/PHPMailer/SMTP.php';

		try {
			$mailer = self::build_mailer( $config );
			$recipient_count = self::add_recipients( $mailer, $recipients );

			if ( 0 === $recipient_count ) {
				return [
					'success' => false,
					'message' => 'No valid recipients were provided.',
				];
			}

			$mailer->Subject = (string) ( $message['subject'] ?? '' );
			$mailer->Body    = (string) ( $message['html'] ?? '' );
			$mailer->AltBody = (string) ( $message['text'] ?? '' );

			if ( ! empty( $message['headers'] ) && is_array( $message['headers'] ) ) {
				foreach ( $message['headers'] as $header ) {
					$mailer->addCustomHeader( (string) $header );
				}
			}

			$sent = $mailer->send();

			return [
				'success' => $sent,
				'message' => $sent ? 'SMTP send completed.' : ( $mailer->ErrorInfo ?: 'SMTP send failed.' ),
			];
		} catch ( PHPMailer_Exception $e ) {
			return [
				'success' => false,
				'message' => $e->getMessage(),
			];
		} catch ( \Throwable $e ) {
			return [
				'success' => false,
				'message' => $e->getMessage(),
			];
		}
	}

	/**
	 * Test SMTP connectivity using the saved config.
	 *
	 * @param array<string, string|int> $config SMTP config.
	 * @return array{success: bool, message: string}
	 */
	public static function test_connection( array $config ): array {
		$missing = [];

		if ( '' === (string) ( $config['host'] ?? '' ) ) {
			$missing[] = 'SMTP host';
		}
		if ( '' === (string) ( $config['username'] ?? '' ) ) {
			$missing[] = 'SMTP username';
		}
		if ( '' === (string) ( $config['password'] ?? '' ) ) {
			$missing[] = 'SMTP password';
		}
		if ( ! filter_var( (string) ( $config['from_email'] ?? '' ), FILTER_VALIDATE_EMAIL ) ) {
			$missing[] = 'From email';
		}

		if ( ! empty( $missing ) ) {
			return [
				'success' => false,
				'message' => 'SMTP configuration is incomplete: ' . implode( ', ', $missing ) . '.',
			];
		}

		require_once ABSPATH . WPINC . '/PHPMailer/Exception.php';
		require_once ABSPATH . WPINC . '/PHPMailer/PHPMailer.php';
		require_once ABSPATH . WPINC . '/PHPMailer/SMTP.php';

		try {
			$mailer = self::build_mailer( $config, 15 );
			$mailer->smtpConnect();
			$mailer->smtpClose();

			return [
				'success' => true,
				'message' => 'SMTP connection successful.',
			];
		} catch ( PHPMailer_Exception $e ) {
			return [
				'success' => false,
				'message' => $e->getMessage(),
			];
		} catch ( \Throwable $e ) {
			return [
				'success' => false,
				'message' => $e->getMessage(),
			];
		}
	}

	/**
	 * Build a PHPMailer instance from config.
	 *
	 * @param array<string, string|int> $config SMTP config.
	 * @param int                       $timeout Connection timeout.
	 */
	private static function build_mailer( array $config, int $timeout = 30 ): PHPMailer {
		$mailer = new PHPMailer( true );
		$mailer->isSMTP();
		$mailer->Host        = (string) $config['host'];
		$mailer->Port        = (int) $config['port'];
		$mailer->SMTPAuth    = true;
		$mailer->Username    = (string) $config['username'];
		$mailer->Password    = (string) $config['password'];
		$mailer->SMTPSecure  = self::phpmailer_encryption( (string) $config['encryption'] );
		$mailer->SMTPAutoTLS = 'none' !== $config['encryption'];
		$mailer->Timeout     = $timeout;
		$mailer->CharSet     = 'UTF-8';
		$mailer->isHTML( true );
		$mailer->setFrom( (string) $config['from_email'], (string) $config['from_name'] );

		return $mailer;
	}

	/**
	 * Add sanitized recipients.
	 *
	 * @param PHPMailer                  $mailer Mailer instance.
	 * @param array<int, string>|string  $recipients Recipients list.
	 * @return int Number of recipients added.
	 */
	private static function add_recipients( PHPMailer $mailer, array|string $recipients ): int {
		$list = is_array( $recipients ) ? $recipients : [ $recipients ];
		$count = 0;

		foreach ( $list as $recipient ) {
			$recipient = sanitize_email( (string) $recipient );
			if ( '' !== $recipient ) {
				$mailer->addAddress( $recipient );
				$count++;
			}
		}

		return $count;
	}

	/**
	 * Convert our labels to PHPMailer encryption values.
	 */
	private static function phpmailer_encryption( string $encryption ): string {
		return 'none' === $encryption ? '' : $encryption;
	}
}
