<?php
/**
 * High-level mail service.
 *
 * @package Alert404
 */

namespace Alert404;

use function apply_filters;
use function do_action;
use function wp_mail;

defined( 'ABSPATH' ) || exit;

class MailService {
	/**
	 * Send a 404 alert.
	 */
	public static function send_alert( string $url, string $ip, string $referrer ): bool {
		$recipients = RecipientResolver::resolve();
		if ( empty( $recipients ) ) {
			MailLogger::record_failure(
				'No recipients available for 404 alert.',
				[ 'url' => $url, 'ip' => $ip, 'referrer' => $referrer ]
			);
			return false;
		}

		$message = MailMessageFactory::build_alert( $url, $ip, $referrer );
		$message['subject'] = apply_filters( '404_alert_email_subject', $message['subject'] );
		$message['html']    = apply_filters( '404_alert_email_body', $message['html'] );

		do_action( '404_alert_before_send', compact( 'url', 'ip', 'referrer' ) );

		$result  = self::deliver( $recipients, $message, [ 'url' => $url, 'ip' => $ip, 'referrer' => $referrer ] );

		if ( $result['success'] ) {
			do_action( '404_alert_sent', compact( 'url', 'ip', 'referrer' ) );
		}

		return $result['success'];
	}

	/**
	 * Send a digest message.
	 *
	 * @param array<int, string> $urls URLs to report.
	 */
	public static function send_digest( array $urls ): bool {
		$recipients = RecipientResolver::resolve();
		if ( empty( $recipients ) || empty( $urls ) ) {
			MailLogger::record_failure(
				'No recipients or URLs available for digest.',
				[ 'urls' => $urls ]
			);
			return false;
		}

		$message = MailMessageFactory::build_digest( $urls );
		$message['subject'] = apply_filters( '404_alert_digest_subject', $message['subject'] );
		$result  = self::deliver( $recipients, $message, [ 'urls' => $urls ] );

		return $result['success'];
	}

	/**
	 * Test SMTP connectivity.
	 */
	public static function test_smtp( array $config = [] ): array {
		$config = [] === $config ? MailSettingsRepository::get_config() : $config;
		return SmtpTransport::test_connection( $config );
	}

	/**
	 * Deliver a payload using SMTP or wp_mail().
	 *
	 * @param array<int, string> $recipients Final recipients.
	 * @param array<string, mixed> $message Payload.
	 * @param array<string, mixed> $context Context for logging.
	 * @return array{success: bool, message: string}
	 */
	private static function deliver( array $recipients, array $message, array $context ): array {
		if ( MailSettingsRepository::is_configured() ) {
			$result = SmtpTransport::send( $message, $recipients, MailSettingsRepository::get_config() );
			if ( $result['success'] ) {
				MailLogger::record_success( $context + [ 'transport' => 'smtp' ] );
			} else {
				MailLogger::record_failure( (string) $result['message'], $context + [ 'transport' => 'smtp' ] );
			}

			return $result;
		}

		$sent = wp_mail(
			$recipients,
			(string) ( $message['subject'] ?? '' ),
			(string) ( $message['html'] ?? '' ),
			(array) ( $message['headers'] ?? [] )
		);

		if ( $sent ) {
			MailLogger::record_success( $context + [ 'transport' => 'wp_mail' ] );
			return [
				'success' => true,
				'message' => 'Message sent through wp_mail().',
			];
		}

		MailLogger::record_failure( 'wp_mail() failed.', $context + [ 'transport' => 'wp_mail' ] );

		return [
			'success' => false,
			'message' => 'wp_mail() failed.',
		];
	}
}
