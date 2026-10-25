<?php
/**
 * Secret storage helper.
 *
 * @package Alert404
 */

namespace Alert404;

defined( 'ABSPATH' ) || exit;

class SecretStore {
	private const SECRET_PREFIX = 'enc:v1:';

	/**
	 * Encrypt a secret for storage.
	 */
	public static function encrypt( string $secret ): string {
		if ( '' === $secret ) {
			return '';
		}

		$key = self::get_key();
		if ( '' === $key || ! function_exists( 'openssl_encrypt' ) ) {
			return $secret;
		}

		$iv         = random_bytes( 16 );
		$ciphertext = openssl_encrypt( $secret, 'AES-256-CBC', $key, OPENSSL_RAW_DATA, $iv );

		if ( false === $ciphertext ) {
			return $secret;
		}

		return self::SECRET_PREFIX . base64_encode( $iv . $ciphertext );
	}

	/**
	 * Decrypt a stored secret.
	 */
	public static function decrypt( string $stored_secret ): string {
		if ( '' === $stored_secret ) {
			return '';
		}

		if ( 0 !== strpos( $stored_secret, self::SECRET_PREFIX ) ) {
			return $stored_secret;
		}

		if ( ! function_exists( 'openssl_decrypt' ) ) {
			return '';
		}

		$raw = base64_decode( substr( $stored_secret, strlen( self::SECRET_PREFIX ) ), true );
		if ( false === $raw || strlen( $raw ) <= 16 ) {
			return '';
		}

		$key = self::get_key();
		if ( '' === $key ) {
			return '';
		}

		$iv         = substr( $raw, 0, 16 );
		$ciphertext = substr( $raw, 16 );
		$decrypted  = openssl_decrypt( $ciphertext, 'AES-256-CBC', $key, OPENSSL_RAW_DATA, $iv );

		return false === $decrypted ? '' : $decrypted;
	}

	/**
	 * Build the encryption key from WordPress salts.
	 */
	private static function get_key(): string {
		$auth_key        = defined( 'AUTH_KEY' ) ? (string) constant( 'AUTH_KEY' ) : '';
		$secure_auth_key = defined( 'SECURE_AUTH_KEY' ) ? (string) constant( 'SECURE_AUTH_KEY' ) : '';
		$material        = $auth_key . '|' . $secure_auth_key;

		if ( '' === $material || '|' === $material ) {
			return '';
		}

		return hash( 'sha256', $material, true );
	}
}
