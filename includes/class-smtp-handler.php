<?php
/**
 * Gestionnaire SMTP pour le plugin 404 Alert
 * Envoie les emails directement via SMTP sans dépendre d'un autre plugin
 */

defined( 'ABSPATH' ) || exit;

class Alert404_SMTP_Handler {
	/**
	 * Prefixe de version pour les secrets SMTP chiffrés
	 */
	private const SECRET_PREFIX = 'enc:v1:';

	/**
	 * Envoie un email via SMTP
	 *
	 * @param array $args Arguments d'envoi (to, subject, message, headers)
	 * @return bool True si succès, False sinon
	 */
	public static function send( array $args ): bool {
		// Récupérer la configuration SMTP
		$config = self::get_smtp_config();

		if ( empty( $config['host'] ) || empty( $config['username'] ) || empty( $config['password'] ) ) {
			Alert404_Logger::log_email_failed(
				$args['to'] ?? 'unknown',
				'Configuration SMTP incomplète'
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

		// Log: Tentative de connexion SMTP
		Alert404_Logger::log_smtp_connection_attempt(
			$config['host'],
			$config['port'],
			$config['encryption']
		);

		try {
			// Configuration serveur SMTP
			$phpmailer->isSMTP();
			$phpmailer->Host       = $config['host'];
			$phpmailer->Port       = (int) $config['port'];
			$phpmailer->SMTPAuth   = true;
			$phpmailer->Username   = $config['username'];
			$phpmailer->Password   = $config['password'];
			$phpmailer->SMTPSecure = $config['encryption'];
			$phpmailer->Timeout    = 30;

			// Configuration de l'email
			$phpmailer->setFrom( $config['from_email'], $config['from_name'] );
			$phpmailer->addAddress( $args['to'] );
			$phpmailer->Subject = $args['subject'] ?? '';
			$phpmailer->isHTML( true );
			$phpmailer->Body = $args['message'] ?? '';

			// Ajouter les headers additionnels si fournis
			if ( ! empty( $args['headers'] ) && is_array( $args['headers'] ) ) {
				foreach ( $args['headers'] as $header ) {
					$phpmailer->addCustomHeader( $header );
				}
			}

			// Envoyer l'email
			$phpmailer->send();

			// Log: Email envoyé via SMTP
			Alert404_Logger::log_email_sent_via_smtp(
				$args['to'],
				$config['from_email']
			);

			return true;
		} catch ( Exception $e ) {
			// Log: Erreur SMTP
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
	 * @return array Configuration SMTP (host, port, username, password, encryption, from_email, from_name)
	 */
	public static function get_smtp_config(): array {
		$options = get_option( '404_alert_smtp_options', array() );
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
	 * @param string $password Mot de passe SMTP en clair
	 * @return string Secret chiffré (versionné) ou chaîne vide si échec
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
	 * @param string $stored_password Secret stocké
	 * @return string Mot de passe SMTP en clair
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

		return hash( 'sha256', $material, true );
	}

	/**
	 * Vérifie la connexion SMTP
	 *
	 * @return array ['success' => bool, 'message' => string]
	 */
	public static function test_connection(): array {
		$config = self::get_smtp_config();

		if ( empty( $config['host'] ) || empty( $config['username'] ) || empty( $config['password'] ) ) {
			return array(
				'success' => false,
				'message' => 'Configuration SMTP incomplète. Veuillez remplir tous les champs.',
			);
		}

		require_once ABSPATH . WPINC . '/PHPMailer/Exception.php';
		require_once ABSPATH . WPINC . '/PHPMailer/PHPMailer.php';
		require_once ABSPATH . WPINC . '/PHPMailer/SMTP.php';

		if ( ! class_exists( 'PHPMailer' ) && class_exists( '\\PHPMailer\\PHPMailer\\PHPMailer' ) ) {
			class_alias( '\\PHPMailer\\PHPMailer\\PHPMailer', 'PHPMailer' );
		}

		$phpmailer = new PHPMailer( true );

		try {
			$phpmailer->isSMTP();
			$phpmailer->Host       = $config['host'];
			$phpmailer->Port       = (int) $config['port'];
			$phpmailer->SMTPAuth   = true;
			$phpmailer->Username   = $config['username'];
			$phpmailer->Password   = $config['password'];
			$phpmailer->SMTPSecure = $config['encryption'];

			// Essayer de se connecter sans envoyer d'email
			$phpmailer->smtpConnect();
			$phpmailer->smtpClose();

			return array(
				'success' => true,
				'message' => 'Connexion SMTP réussie! ✓',
			);
		} catch ( Exception $e ) {
			return array(
				'success' => false,
				'message' => 'Erreur de connexion SMTP: ' . $e->getMessage(),
			);
		}//end try
	}
}
