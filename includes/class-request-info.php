<?php
/**
 * Request information collector
 *
 * @package Alert404
 */

namespace Alert404;

use function apply_filters;

defined( 'ABSPATH' ) || exit;

class RequestInfo {
	/**
	 * Get client IP address (proxy-aware)
	 */
	public static function get_client_ip(): string {
		$ip = '';

		// Cloudflare
		if ( ! empty( $_SERVER['HTTP_CF_CONNECTING_IP'] ) ) {
			$ip = sanitize_text_field( wp_unslash( $_SERVER['HTTP_CF_CONNECTING_IP'] ) );
		}
		// X-Forwarded-For
		elseif ( ! empty( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) {
			$ips = array_map( 'trim', explode( ',', sanitize_text_field( wp_unslash( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) ) );
			$ip  = $ips[0] ?? '';
		}
		// Forwarded
		elseif ( ! empty( $_SERVER['HTTP_FORWARDED'] ) ) {
			$match = preg_match( '/for=([^;,]+)/', sanitize_text_field( wp_unslash( $_SERVER['HTTP_FORWARDED'] ) ), $m );
			$ip    = $match ? trim( $m[1], '[]' ) : '';
		}
		// Fallback to REMOTE_ADDR
		elseif ( ! empty( $_SERVER['REMOTE_ADDR'] ) ) {
			$ip = sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) );
		}

		// Validate IP
		if ( ! filter_var( $ip, FILTER_VALIDATE_IP ) ) {
			$ip = '';
		}

		return apply_filters( '404_alert_client_ip', $ip );
	}

	/**
	 * Get the current request URL
	 */
	public static function get_url(): string {
		return isset( $_SERVER['REQUEST_URI'] ) ? esc_url_raw( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '';
	}

	/**
	 * Get referrer
	 */
	public static function get_referrer(): string {
		return isset( $_SERVER['HTTP_REFERER'] ) ? esc_url_raw( wp_unslash( $_SERVER['HTTP_REFERER'] ) ) : '';
	}

	/**
	 * Get user agent
	 */
	public static function get_user_agent(): string {
		return isset( $_SERVER['HTTP_USER_AGENT'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) ) : '';
	}
}
