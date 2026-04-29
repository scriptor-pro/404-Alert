<?php
/**
 * SMTP handler for 404 Alert plugin.
 *
 * @package Alert404
 * Sends emails directly via SMTP without relying on other plugins.
 */

defined( 'ABSPATH' ) || exit;

/**
 * SMTP handler for sending emails.
 */
class Alert404_SMTP_Handler {
	/**
	 * Prefixe de version pour les secrets SMTP chiffrés
	 */
	private const SECRET_PREFIX = 'enc:v1:';

	/**
	 * Envoie un email via SMTP
	 *
	 * @param array $args Arguments d'envoi (to, subject, message, headers).
	 * @return bool True si succès, False sinon.
	 */
	public static function send( array $args ): bool {
		// Get SMTP configuration.
		$config = self::get_smtp_config();

		if ( empty( $config['host'] ) || empty( $config['username'] ) || empty( $config['password'] ) ) {
			Alert404_Logger::log_email_failed(
				$args['to'] ?? 'unknown',
				'Incomplete SMTP configuration'
			);
			return false;
		}

		require_once ABSPATH . WPINC . '/PHPMailer/Exception.php';
		require_once ABSPATH . WPINC . '/PHPMailer/PHPMailer.php';
		require_once ABSPATH . WPINC . '/PHPMailer/SMTP.php';

		if ( ! class_exists( 'PHPMailer' ) && class_exists( '\\PHPMailer\\PHPMailer\\PHPMailer' ) ) {
			class_alias( '\\PHPMailer\\PHPMailer\\PHPMailer', 'PHPMailer' );
		}

		$phpmailer = new PHPMailer( true );

		// Log SMTP connection attempt.
		Alert404_Logger::log_smtp_connection_attempt(
			$config['host'],
			$config['port'],
			$config['encryption']
		);

		try {
			// Configure SMTP server.
			$phpmailer->isSMTP();
			// phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase -- PHPMailer external dependency uses camelCase
			$phpmailer->Host       = $config['host'];
			// phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase -- PHPMailer external dependency uses camelCase
			$phpmailer->Port       = (int) $config['port'];
			// phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase -- PHPMailer external dependency uses camelCase
			$phpmailer->SMTPAuth   = true;
			// phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase -- PHPMailer external dependency uses camelCase
			$phpmailer->Username   = $config['username'];
			// phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase -- PHPMailer external dependency uses camelCase
			$phpmailer->Password   = $config['password'];
			// phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase -- PHPMailer external dependency uses camelCase
			$phpmailer->SMTPSecure = $config['encryption'];
			// phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase -- PHPMailer external dependency uses camelCase
			$phpmailer->Timeout    = 30;

			// Configure email.
			$phpmailer->setFrom( $config['from_email'], $config['from_name'] );
			$phpmailer->addAddress( $args['to'] );
			// phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase -- PHPMailer external dependency uses camelCase
			$phpmailer->Subject = $args['subject'] ?? '';
			$phpmailer->isHTML( true );
			// phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase -- PHPMailer external dependency uses camelCase
			$phpmailer->Body = $args['message'] ?? '';

			// Add custom headers if provided.
			if ( ! empty( $args['headers'] ) && is_array( $args['headers'] ) ) {
				foreach ( $args['headers'] as $header ) {
					$phpmailer->addCustomHeader( $header );
				}
			}

			// Send email.
			$phpmailer->send();

			// Log email sent via SMTP.
			Alert404_Logger::log_email_sent_via_smtp(
				$args['to'],
				$config['from_email']
			);

			return true;
		} catch ( Exception $e ) {
			// Log SMTP error.
			Alert404_Logger::log_smtp_auth_failure(
				$config['host'],
				$config['username'],
				$e->getMessage()
			);

			Alert404_Logger::log_email_failed(
				$args['to'] ?? 'unknown',
				'Erreur SMTP: ' . $e->getMessage()
			);
			return false;
		}//end try
	}

	/**
	 * Récupère la configuration SMTP depuis les options WordPress
	 *
	 * @return array<string, string|int> Configuration SMTP (host, port, username, password, encryption, from_email, from_name)
	 */
	public static function get_smtp_config(): array {
		$options  = get_option( '404_alert_smtp_options', array() );
		$password = self::decrypt_password_from_storage( (string) ( $options['password'] ?? '' ) );

		return array(
			'host'       => $options['host'] ?? '',
			'port'       => $options['port'] ?? 587,
			'username'   => $options['username'] ?? '',
			'password'   => $password,
			'encryption' => $options['encryption'] ?? 'tls',
			'from_email' => $options['from_email'] ?? get_option( 'admin_email' ),
			'from_name'  => $options['from_name'] ?? get_bloginfo( 'name' ),
		);
	}

	/**
	 * Chiffre un mot de passe SMTP pour stockage en base.
	 *
	 * @param string $password Mot de passe SMTP en clair.
	 * @return string Secret chiffré (versionné) ou chaîne vide si échec.
	 */
	public static function encrypt_password_for_storage( string $password ): string {
		if ( '' === $password ) {
			return '';
		}

		$key = self::get_encryption_key();

		if ( '' === $key || ! function_exists( 'openssl_encrypt' ) ) {
			return '';
		}

		$iv = random_bytes( 16 );

		$ciphertext = openssl_encrypt( $password, 'AES-256-CBC', $key, OPENSSL_RAW_DATA, $iv );

		if ( false === $ciphertext ) {
			return '';
		}

		$payload = base64_encode( $iv . $ciphertext );

		return self::SECRET_PREFIX . $payload;
	}

	/**
	 * Déchiffre un secret stocké (format chiffré v1 + fallback legacy base64).
	 *
	 * @param string $stored_password Secret stocké.
	 * @return string Mot de passe SMTP en clair.
	 */
	private static function decrypt_password_from_storage( string $stored_password ): string {
		if ( '' === $stored_password ) {
			return '';
		}

		if ( 0 !== strpos( $stored_password, self::SECRET_PREFIX ) ) {
			$legacy = base64_decode( $stored_password, true );

			if ( false !== $legacy ) {
				return $legacy;
			}

			return $stored_password;
		}

		$payload = substr( $stored_password, strlen( self::SECRET_PREFIX ) );
		$raw     = base64_decode( $payload, true );

		if ( false === $raw || strlen( $raw ) <= 16 ) {
			return '';
		}

		$key = self::get_encryption_key();

		if ( '' === $key || ! function_exists( 'openssl_decrypt' ) ) {
			return '';
		}

		$iv         = substr( $raw, 0, 16 );
		$ciphertext = substr( $raw, 16 );

		$decrypted = openssl_decrypt( $ciphertext, 'AES-256-CBC', $key, OPENSSL_RAW_DATA, $iv );

		return false === $decrypted ? '' : $decrypted;
	}

	/**
	 * Génère une clé de chiffrement stable à partir des sels WordPress.
	 *
	 * @return string
	 */
	private static function get_encryption_key(): string {
		$auth_key        = defined( 'AUTH_KEY' ) ? (string) constant( 'AUTH_KEY' ) : '';
		$secure_auth_key = defined( 'SECURE_AUTH_KEY' ) ? (string) constant( 'SECURE_AUTH_KEY' ) : '';
		$material        = $auth_key . '|' . $secure_auth_key;
		if ( '' === $auth_key && '' === $secure_auth_key ) {
			return '';
		}

		return hash( 'sha256', $material, true );
	}

	/**
	 * Test SMTP connection.
	 *
	 * @param array $config Configuration array (host, port, encryption, email, password).
	 * @return array Array with 'success' => bool and 'error' => string if failed.
	 */
	public static function test_connection( array $config ): array {
		require_once ABSPATH . WPINC . '/PHPMailer/Exception.php';
		require_once ABSPATH . WPINC . '/PHPMailer/PHPMailer.php';
		require_once ABSPATH . WPINC . '/PHPMailer/SMTP.php';

		if ( ! class_exists( 'PHPMailer' ) && class_exists( '\\PHPMailer\\PHPMailer\\PHPMailer' ) ) {
			class_alias( '\\PHPMailer\\PHPMailer\\PHPMailer', 'PHPMailer' );
		}

		$phpmailer = new PHPMailer( true );

		try {
			$phpmailer->isSMTP();
			// phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase -- PHPMailer external dependency uses camelCase
			$phpmailer->Host       = $config['host'];
			// phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase -- PHPMailer external dependency uses camelCase
			$phpmailer->Port       = $config['port'];
			// phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase -- PHPMailer external dependency uses camelCase
			$phpmailer->SMTPSecure = self::get_smtp_secure_value( $config['encryption'] );
			// phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase -- PHPMailer external dependency uses camelCase
			$phpmailer->SMTPAuth   = true;
			// phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase -- PHPMailer external dependency uses camelCase
			$phpmailer->Username   = $config['email'];
			// phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase -- PHPMailer external dependency uses camelCase
			$phpmailer->Password   = $config['password'];
			// phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase -- PHPMailer external dependency uses camelCase
			$phpmailer->SMTPDebug  = 0;

			$phpmailer->smtpConnect();
			$phpmailer->smtpClose();

			return array( 'success' => true );
		} catch ( Exception $e ) {
			return array(
				'success' => false,
				'error'   => $e->getMessage(),
			);
		}
	}

	/**
	 * Récupère la configuration SMTP depuis les données du formulaire
	 *
	 * @param array $formData Données du formulaire.
	 * @return array<string, mixed> Configuration SMTP
	 */
	private static function extract_smtp_config_from_form( array $formData ): array {
		if ( ! empty( $formData ) ) {
			return array(
				'host'       => isset( $formData['host'] ) ? sanitize_text_field( $formData['host'] ) : '',
				'port'       => isset( $formData['port'] ) ? max( 1, min( 65535, (int) $formData['port'] ) ) : 587,
				'username'   => isset( $formData['username'] ) ? sanitize_text_field( $formData['username'] ) : '',
				'password'   => isset( $formData['password'] ) ? wp_unslash( $formData['password'] ) : '',
				'encryption' => isset( $formData['encryption'] ) && in_array( $formData['encryption'], array( 'tls', 'ssl', 'none' ), true ) ? $formData['encryption'] : 'tls',
				'from_email' => isset( $formData['from_email'] ) ? sanitize_email( $formData['from_email'] ) : get_option( 'admin_email' ),
				'from_name'  => isset( $formData['from_name'] ) ? sanitize_text_field( $formData['from_name'] ) : get_bloginfo( 'name' ),
			);
		}

		return self::get_smtp_config();
	}

	/**
	 * Valide la configuration SMTP et retourne les erreurs
	 *
	 * @param array $config Configuration SMTP.
	 * @return array<string> Messages d'erreur de validation
	 */
	private static function validate_smtp_config( array $config ): array {
		$errors = array();

		if ( empty( $config['host'] ) ) {
			$errors[] = '❌ Serveur SMTP vide';
		}

		if ( empty( $config['username'] ) ) {
			$errors[] = '❌ Identifiant vide';
		}

		if ( empty( $config['password'] ) ) {
			$errors[] = '❌ Mot de passe vide';
		}

		if ( ! filter_var( $config['from_email'], FILTER_VALIDATE_EMAIL ) ) {
			$errors[] = '❌ Email expéditeur invalide';
		}

		return $errors;
	}

	/**
	 * Convert encryption string to PHPMailer constant.
	 */
	private static function get_smtp_secure_value( string $encryption ): string {
		switch ( $encryption ) {
			case 'ssl':
				return PHPMailer::ENCRYPTION_SMTPS;
			case 'tls':
				return PHPMailer::ENCRYPTION_STARTTLS;
			default:
				return '';
		}
	}
}
