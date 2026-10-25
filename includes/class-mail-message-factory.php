<?php
/**
 * Build mail payloads.
 *
 * @package Alert404
 */

namespace Alert404;

use function esc_html;
use function get_bloginfo;

defined( 'ABSPATH' ) || exit;

class MailMessageFactory {
	/**
	 * Build payload for a single 404 alert.
	 *
	 * @return array{subject: string, html: string, text: string, headers: array<int, string>}
	 */
	public static function build_alert( string $url, string $ip, string $referrer ): array {
		$site_name = (string) get_bloginfo( 'name' );
		$subject   = sprintf( '[%s] 404 Error: %s', $site_name, $url );

		$html  = '<!DOCTYPE html><html><head><meta charset="UTF-8"><style>';
		$html .= 'body{font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,sans-serif;line-height:1.6;color:#333}.container{max-width:600px;margin:0 auto;padding:20px}.header{background:#f0f0f0;padding:15px;border-radius:5px;margin-bottom:20px}.details{background:#fafafa;padding:15px;border-left:4px solid #dc3545}code{background:#f5f5f5;padding:2px 6px;border-radius:3px}.footer{color:#666;font-size:12px;margin-top:20px}';
		$html .= '</style></head><body><div class="container"><div class="header">';
		$html .= '<h2 style="margin:0;">404 Error Alert</h2>';
		$html .= '<p style="margin:5px 0 0 0;">Detected on ' . esc_html( $site_name ) . '</p>';
		$html .= '</div><div class="details">';
		$html .= '<p><strong>URL:</strong> <code>' . esc_html( $url ) . '</code></p>';
		$html .= '<p><strong>Client IP:</strong> <code>' . esc_html( $ip ) . '</code></p>';
		$html .= '<p><strong>Referrer:</strong> <code>' . esc_html( $referrer ) . '</code></p>';
		$html .= '</div><div class="footer"><p>This is an automated alert from the 404 Alert plugin.</p></div></div></body></html>';

		$text  = "404 Error Alert\n";
		$text .= 'Detected on ' . $site_name . "\n\n";
		$text .= 'URL: ' . $url . "\n";
		$text .= 'Client IP: ' . $ip . "\n";
		$text .= 'Referrer: ' . $referrer . "\n";

		return [
			'subject' => $subject,
			'html'    => $html,
			'text'    => $text,
			'headers' => [ 'Content-Type: text/html; charset=UTF-8' ],
		];
	}

	/**
	 * Build payload for a digest email.
	 *
	 * @param array<int, string> $urls URLs included in the digest.
	 * @return array{subject: string, html: string, text: string, headers: array<int, string>}
	 */
	public static function build_digest( array $urls ): array {
		$site_name = (string) get_bloginfo( 'name' );
		$subject   = sprintf( '[%s] Daily 404 Digest (%d errors)', $site_name, count( $urls ) );

		$list_html = '';
		$list_text = '';

		foreach ( $urls as $url ) {
			$list_html .= '<li><code>' . esc_html( $url ) . '</code></li>';
			$list_text .= '- ' . $url . "\n";
		}

		$html  = '<!DOCTYPE html><html><head><meta charset="UTF-8"><style>';
		$html .= 'body{font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,sans-serif;line-height:1.6;color:#333}.container{max-width:600px;margin:0 auto;padding:20px}.header{background:#f0f0f0;padding:15px;border-radius:5px;margin-bottom:20px}.list{background:#fafafa;padding:15px}ul{margin:0;padding-left:20px}li{margin-bottom:8px}code{background:#f5f5f5;padding:2px 6px;border-radius:3px}.footer{color:#666;font-size:12px;margin-top:20px}';
		$html .= '</style></head><body><div class="container"><div class="header">';
		$html .= '<h2 style="margin:0;">Daily 404 Digest</h2>';
		$html .= '<p style="margin:5px 0 0 0;">Summary for ' . esc_html( $site_name ) . '</p>';
		$html .= '</div><div class="list">';
		$html .= '<p>The following URLs exceeded the immediate alert limit and are reported in this digest:</p>';
		$html .= '<ul>' . $list_html . '</ul>';
		$html .= '</div><div class="footer"><p>This is an automated digest from the 404 Alert plugin.</p></div></div></body></html>';

		$text  = "Daily 404 Digest\n";
		$text .= 'Summary for ' . $site_name . "\n\n";
		$text .= "The following URLs exceeded the immediate alert limit:\n";
		$text .= $list_text;

		return [
			'subject' => $subject,
			'html'    => $html,
			'text'    => $text,
			'headers' => [ 'Content-Type: text/html; charset=UTF-8' ],
		];
	}
}
