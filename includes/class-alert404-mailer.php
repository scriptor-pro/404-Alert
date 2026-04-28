<?php
/**
 * Email sending for 404 Alert plugin.
 *
 * @package Alert404
 */

defined( 'ABSPATH' ) || exit;

/**
 * Email notifications for 404 errors.
 */
class Alert404_Mailer {
	/**
	 * Send an email notification for the 404 error
	 * Verifies sending success and logs the event
	 *
	 * @param array $payload Data from the 404 request (url, ip, referrer, userAgent, occurredAt).
	 * @return void.
	 */
	public static function send( array $payload ): void {
		$options = get_option( '404_alert_options', array() );
		$to      = $options['email'] ?? get_option( 'admin_email' );

		if ( ! sanitize_email( $to ) ) {
			Alert404_Logger::log_email_failed( '', 'Email destinataire invalide: ' . $to );
			return;
		}

		$subject = sprintf( '🚨 404 sur %s - %s', get_bloginfo( 'name' ), $payload['full_url'] ?? $payload['url'] ?? '' );
		if ( strlen( $subject ) > 200 ) {
			$subject = '404 Alert - Erreur détectée';
		}
		$headers = array( 'Content-Type: text/html; charset=UTF-8' );

		// Apply filters to allow extensions to customize behavior.
		$to      = apply_filters( '404_alert_email_to', $to, $payload );
		$subject = apply_filters( '404_alert_email_subject', $subject, $payload );
		$headers = apply_filters( '404_alert_email_headers', $headers, $payload );

		// Generate HTML body with enriched information.
		$html = self::render_email_html( $payload );

		// Apply filter for HTML content.
		$html = apply_filters( '404_alert_email_body', $html, $payload );

		$smtp_config = Alert404_SMTP_Handler::get_smtp_config();
		$use_smtp    = ! empty( $smtp_config['host'] ) && ! empty( $smtp_config['username'] ) && ! empty( $smtp_config['password'] );

		try {
			if ( $use_smtp ) {
				$sent = Alert404_SMTP_Handler::send(
					array(
						'to'      => $to,
						'subject' => $subject,
						'message' => $html,
						'headers' => $headers,
					)
				);
			} else {
				$sent = wp_mail( $to, $subject, $html, $headers );
			}
		} catch ( Throwable $e ) {
			Alert404_Logger::log_email_failed( $to, 'Exception: ' . $e->getMessage() );
			do_action( '404_alert_email_failed', $to, $subject, $payload );
			return;
		}

		if ( $sent ) {
			Alert404_Logger::log_email_sent( $to, $payload['full_url'] ?? $payload['url'] ?? '' );
			do_action( '404_alert_email_sent', $to, $subject, $payload );
		} else {
			Alert404_Logger::log_email_failed( $to, $use_smtp ? 'Erreur SMTP' : 'wp_mail() retourné false' );
			do_action( '404_alert_email_failed', $to, $subject, $payload );
		}
	}

	/**
	 * Generate the HTML content of the email
	 *
	 * @param array $payload Data from the 404 request.
	 * @return string Formatted HTML.
	 */
	private static function render_email_html( array $payload ): string {
		$url           = esc_html( $payload['full_url'] ?? $payload['url'] ?? 'Unknown' );
		$referrer      = esc_html( $payload['referrer'] ?? 'No referrer' );
		$ip            = esc_html( $payload['ip'] ?? 'Unknown' );
		$method        = esc_html( $payload['method'] ?? 'GET' );
		$timestamp     = esc_html( $payload['timestamp'] ?? 'Unknown' );
		$language      = esc_html( $payload['language'] ?? 'Not specified' );
		$user_readable = esc_html( $payload['user_readable'] ?? 'Unknown browser' );
		$device_type   = esc_html( $payload['device'] ?? 'Unknown' );

		// Browser/OS information.
		$browser_name    = esc_html( $payload['browser']['name'] ?? 'Unknown' );
		$browser_version = esc_html( $payload['browser']['version'] ?? 'Unknown' );
		$os_name         = esc_html( $payload['os']['name'] ?? 'Unknown' );
		$os_version      = esc_html( $payload['os']['version'] ?? 'Unknown' );

		// WordPress information.
		$wp_info      = $payload['wordpress'] ?? array();
		$is_logged_in = $wp_info['logged_in'] ?? false;
		$user_login   = $wp_info['user_name'] ?? '';
		$user_email   = $wp_info['user_email'] ?? '';

		// Format user login status.
		$user_status = $is_logged_in
			? sprintf( '<strong>Yes</strong> - %s (%s)', esc_html( $user_login ), esc_html( $user_email ) )
			: '<strong>No</strong> - Anonymous visitor';

		// Full JSON for reference - use ENT_QUOTES | ENT_HTML5 for better XSS protection.
		$json_body = esc_html(
			wp_json_encode(
				$payload,
				JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT
			)
		);
		$site_url  = esc_html( home_url() );

		// Determine CSS class for device badge.
		$device_class = strtolower( $device_type ) === 'mobile' ? 'mobile' : ( strtolower( $device_type ) === 'tablet' ? 'tablet' : 'desktop' );

		return sprintf(
			'<!DOCTYPE html>
<html>
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<style>
		body {
			font-family: \'Segoe UI\', Tahoma, Geneva, Verdana, sans-serif;
			line-height: 1.6;
			color: #333;
			background-color: #f5f5f5;
			margin: 0;
			padding: 0;
		}
		.container {
			max-width: 600px;
			margin: 20px auto;
			background-color: #ffffff;
			border-radius: 8px;
			box-shadow: 0 2px 8px rgba(0,0,0,0.1);
			overflow: hidden;
		}
		.header {
			background: linear-gradient(135deg, #d32f2f 0%%, #b71c1c 100%%);
			color: white;
			padding: 30px 20px;
			text-align: center;
		}
		.header h1 {
			margin: 0;
			font-size: 28px;
			font-weight: bold;
		}
		.header p {
			margin: 10px 0 0 0;
			font-size: 14px;
			opacity: 0.9;
		}
		.content {
			padding: 30px 20px;
		}
		.section {
			margin-bottom: 25px;
		}
		.section-title {
			font-size: 16px;
			font-weight: 600;
			color: #1976d2;
			margin-bottom: 12px;
			border-bottom: 2px solid #e0e0e0;
			padding-bottom: 8px;
		}
		.info-row {
			display: flex;
			padding: 8px 0;
			border-bottom: 1px solid #f0f0f0;
		}
		.info-label {
			font-weight: 600;
			width: 35%%;
			color: #555;
		}
		.info-value {
			width: 65%%;
			word-break: break-all;
			color: #333;
		}
		.alert-box {
			background-color: #fff3cd;
			border-left: 4px solid #ffc107;
			padding: 12px;
			margin-bottom: 15px;
			border-radius: 4px;
			font-size: 13px;
		}
		.json-section {
			background-color: #f5f5f5;
			border: 1px solid #ddd;
			padding: 12px;
			border-radius: 4px;
			overflow-x: auto;
			margin-top: 15px;
		}
		.json-section pre {
			margin: 0;
			font-size: 12px;
			font-family: \'Courier New\', monospace;
			color: #333;
		}
		.footer {
			background-color: #f9f9f9;
			padding: 15px 20px;
			border-top: 1px solid #e0e0e0;
			font-size: 12px;
			color: #999;
			text-align: center;
		}
		.badge {
			display: inline-block;
			background-color: #1976d2;
			color: white;
			padding: 3px 8px;
			border-radius: 12px;
			font-size: 11px;
			font-weight: 600;
			margin-right: 5px;
		}
		.badge.mobile { background-color: #ff9800; }
		.badge.tablet { background-color: #9c27b0; }
		.badge.desktop { background-color: #4caf50; }
	</style>
</head>
<body>
	<div class="container">
		<div class="header">
			<h1>🚨 Erreur 404 Détectée</h1>
			<p>%s</p>
		</div>

		<div class="content">
			<!-- Section URL -->
			<div class="section">
				<div class="section-title">📄 Page Demandée</div>
				<div class="info-row">
					<div class="info-label">URL complète:</div>
					<div class="info-value">%s</div>
				</div>
				<div class="info-row">
					<div class="info-label">Méthode HTTP:</div>
					<div class="info-value">%s</div>
				</div>
				<div class="info-row">
					<div class="info-label">Provenance:</div>
					<div class="info-value">%s</div>
				</div>
			</div>

			<!-- Section Client -->
			<div class="section">
				<div class="section-title">👤 Information Client</div>
				<div class="info-row">
					<div class="info-label">Adresse IP:</div>
					<div class="info-value"><strong>%s</strong></div>
				</div>
				<div class="info-row">
					<div class="info-label">Langue:</div>
					<div class="info-value">%s</div>
				</div>
			</div>

			<!-- Section Navigateur & OS -->
			<div class="section">
				<div class="section-title">🖥️ Navigateur & Système</div>
				<div class="alert-box">
					<strong>Résumé:</strong> %s
				</div>
				<div class="info-row">
					<div class="info-label">Navigateur:</div>
					<div class="info-value">
						%s %s
					</div>
				</div>
				<div class="info-row">
					<div class="info-label">Système d\'exploitation:</div>
					<div class="info-value">
						%s %s
					</div>
				</div>
				<div class="info-row">
					<div class="info-label">Type d\'appareil:</div>
					<div class="info-value">
						<span class="badge %s">
							%s
						</span>
					</div>
				</div>
			</div>

			<!-- Section WordPress -->
			<div class="section">
				<div class="section-title">⚙️ État WordPress</div>
				<div class="info-row">
					<div class="info-label">Utilisateur connecté:</div>
					<div class="info-value">
						%s
					</div>
				</div>
			</div>

			<!-- Section JSON -->
			<div class="section">
				<div class="section-title">📊 Données complètes (JSON)</div>
				<div class="json-section">
					<pre>%s</pre>
				</div>
			</div>
		</div>

		<div class="footer">
			<p>Cet email a été généré automatiquement par le plugin 404 Alert.</p>
			<p>Site: %s</p>
		</div>
	</div>
</body>
</html>',
			$timestamp,
			$url,
			$method,
			$referrer,
			$ip,
			$language,
			$user_readable,
			$browser_name,
			$browser_version,
			$os_name,
			$os_version,
			$device_class,
			$device_type,
			$user_status,
			$json_body,
			$site_url
		);
	}
}
