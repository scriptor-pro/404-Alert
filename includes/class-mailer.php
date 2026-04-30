<?php
/**
 * Email sending via wp_mail()
 *
 * @package Alert404
 */

namespace Alert404;

use function get_users;
use function wp_mail;
use function apply_filters;
use function do_action;
use function get_bloginfo;
use function get_the_author_meta;

defined( 'ABSPATH' ) || exit;

class Mailer {
	/**
	 * Send alert email to all admins
	 */
	public static function send_alert( string $url, string $ip, string $referrer ): bool {
		$admins = self::get_admin_emails();

		if ( empty( $admins ) ) {
			return false;
		}

		$subject = apply_filters(
			'404_alert_email_subject',
			sprintf( '[%s] 404 Error: %s', get_bloginfo( 'name' ), $url )
		);

		$body = self::build_email_body( $url, $ip, $referrer );
		$body = apply_filters( '404_alert_email_body', $body );

		$headers = [
			'Content-Type: text/html; charset=UTF-8',
		];

		$recipients = apply_filters( '404_alert_recipients', $admins );

		do_action( '404_alert_before_send', compact( 'url', 'ip', 'referrer' ) );

		$sent = wp_mail( $recipients, $subject, $body, $headers );

		if ( $sent ) {
			do_action( '404_alert_sent', compact( 'url', 'ip', 'referrer' ) );
		}

		return $sent;
	}

	/**
	 * Send digest email with multiple 404s
	 */
	public static function send_digest( array $urls ): bool {
		$admins = self::get_admin_emails();

		if ( empty( $admins ) || empty( $urls ) ) {
			return false;
		}

		$subject = apply_filters(
			'404_alert_digest_subject',
			sprintf( '[%s] Daily 404 Digest (%d errors)', get_bloginfo( 'name' ), count( $urls ) )
		);

		$body = self::build_digest_body( $urls );

		$headers = [
			'Content-Type: text/html; charset=UTF-8',
		];

		$recipients = apply_filters( '404_alert_recipients', $admins );

		return wp_mail( $recipients, $subject, $body, $headers );
	}

	/**
	 * Get all admin email addresses
	 */
	private static function get_admin_emails(): array {
		$admins = get_users( [
			'role'   => 'administrator',
			'fields' => 'ID',
		] );

		$emails = [];

		foreach ( $admins as $admin_id ) {
			$email = get_the_author_meta( 'user_email', $admin_id );
			if ( $email ) {
				$emails[] = $email;
			}
		}

		return array_unique( $emails );
	}

	/**
	 * Build HTML email body for single alert
	 */
	private static function build_email_body( string $url, string $ip, string $referrer ): string {
		$site_name = get_bloginfo( 'name' );

		return <<<HTML
<!DOCTYPE html>
<html>
<head>
	<meta charset="UTF-8">
	<style>
		body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; line-height: 1.6; color: #333; }
		.container { max-width: 600px; margin: 0 auto; padding: 20px; }
		.header { background: #f0f0f0; padding: 15px; border-radius: 5px; margin-bottom: 20px; }
		.details { background: #fafafa; padding: 15px; border-left: 4px solid #dc3545; }
		.footer { color: #666; font-size: 12px; margin-top: 20px; }
		code { background: #f5f5f5; padding: 2px 6px; border-radius: 3px; }
	</style>
</head>
<body>
	<div class="container">
		<div class="header">
			<h2 style="margin: 0;">🚨 404 Error Alert</h2>
			<p style="margin: 5px 0 0 0;">Detected on $site_name</p>
		</div>

		<div class="details">
			<p><strong>URL:</strong> <code>$url</code></p>
			<p><strong>Client IP:</strong> <code>$ip</code></p>
			<p><strong>Referrer:</strong> <code>$referrer</code></p>
			<p><strong>Time:</strong> <code>{{TIME}}</code></p>
		</div>

		<div class="footer">
			<p>This is an automated alert from the 404 Alert plugin.</p>
		</div>
	</div>
</body>
</html>
HTML;
	}

	/**
	 * Build HTML email body for digest
	 */
	private static function build_digest_body( array $urls ): string {
		$site_name = get_bloginfo( 'name' );
		$list      = '';

		foreach ( $urls as $url ) {
			$list .= "<li><code>$url</code></li>";
		}

		return <<<HTML
<!DOCTYPE html>
<html>
<head>
	<meta charset="UTF-8">
	<style>
		body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; line-height: 1.6; color: #333; }
		.container { max-width: 600px; margin: 0 auto; padding: 20px; }
		.header { background: #f0f0f0; padding: 15px; border-radius: 5px; margin-bottom: 20px; }
		.list { background: #fafafa; padding: 15px; }
		ul { margin: 0; padding-left: 20px; }
		li { margin-bottom: 8px; }
		code { background: #f5f5f5; padding: 2px 6px; border-radius: 3px; }
		.footer { color: #666; font-size: 12px; margin-top: 20px; }
	</style>
</head>
<body>
	<div class="container">
		<div class="header">
			<h2 style="margin: 0;">📋 Daily 404 Digest</h2>
			<p style="margin: 5px 0 0 0;">Summary for $site_name</p>
		</div>

		<div class="list">
			<p>The following URLs exceeded the immediate alert limit and are reported in this digest:</p>
			<ul>
				$list
			</ul>
		</div>

		<div class="footer">
			<p>This is an automated digest from the 404 Alert plugin.</p>
		</div>
	</div>
</body>
</html>
HTML;
	}
}
