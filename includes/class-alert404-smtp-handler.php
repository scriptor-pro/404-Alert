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
	 * Vérifie la connexion SMTP
	 *
	 * @return array<string, bool|string> ['success' => bool, 'message' => string]
	 */
	public static function test_connection(): array {
		Alert404_Test_Progress::init_test();

		$config = self::get_smtp_config();

		// Step 1: Vérification de la configuration
		Alert404_Test_Progress::update_step(
			'Vérification de la configuration',
			'running',
			'Validation des paramètres SMTP...'
		);

		if ( empty( $config['host'] ) || empty( $config['username'] ) || empty( $config['password'] ) ) {
			Alert404_Test_Progress::update_step(
				'Vérification de la configuration',
				'error',
				'Configuration SMTP incomplète'
			);
			return array(
				'success' => false,
				'message' => 'Configuration SMTP incomplète. Veuillez remplir tous les champs.',
			);
		}

		Alert404_Test_Progress::update_step(
			'Vérification de la configuration',
			'success',
			'Configuration SMTP valide'
		);

		require_once ABSPATH . WPINC . '/PHPMailer/Exception.php';
		require_once ABSPATH . WPINC . '/PHPMailer/PHPMailer.php';
		require_once ABSPATH . WPINC . '/PHPMailer/SMTP.php';

		if ( ! class_exists( 'PHPMailer' ) && class_exists( '\\PHPMailer\\PHPMailer\\PHPMailer' ) ) {
			class_alias( '\\PHPMailer\\PHPMailer\\PHPMailer', 'PHPMailer' );
		}

		$phpmailer = new PHPMailer( true );

		try {
			// Step 2: Connexion au serveur
			Alert404_Test_Progress::update_step(
				'Connexion au serveur',
				'running',
				'Établissement de la connexion TCP avec ' . $config['host'] . ':' . $config['port'] . '...'
			);

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

			Alert404_Test_Progress::update_step(
				'Connexion au serveur',
				'success',
				'Connecté au serveur SMTP'
			);

			// Step 3: Authentification
			Alert404_Test_Progress::update_step(
				'Authentification',
				'running',
				'Authentification avec ' . $config['username'] . '...'
			);

			$phpmailer->smtpConnect();

			Alert404_Test_Progress::update_step(
				'Authentification',
				'success',
				'Authentification réussie'
			);

			// Step 4: Configuration de l'email
			Alert404_Test_Progress::update_step(
				'Configuration de l\'email',
				'running',
				'Préparation du message de test...'
			);

			$phpmailer->setFrom( $config['from_email'], $config['from_name'] );
			$phpmailer->addAddress( $config['from_email'] );
			// phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase -- PHPMailer external dependency uses camelCase
			$phpmailer->Subject = '[404 Alert Test] Test de connexion SMTP';
			$phpmailer->isHTML( true );
			// phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase -- PHPMailer external dependency uses camelCase
			$phpmailer->Body = '<p>Ceci est un email de test du plugin 404 Alert.</p><p>Si vous recevez ce message, votre configuration SMTP est correcte.</p>';

			Alert404_Test_Progress::update_step(
				'Configuration de l\'email',
				'success',
				'Email configuré'
			);

			// Step 5: Envoi de l'email de test
			Alert404_Test_Progress::update_step(
				'Envoi de l\'email de test',
				'running',
				'Envoi de l\'email...'
			);

			$phpmailer->send();

			Alert404_Test_Progress::update_step(
				'Envoi de l\'email de test',
				'success',
				'Email de test envoyé'
			);

			// Step 6: Fermeture de la connexion
			Alert404_Test_Progress::update_step(
				'Fermeture de la connexion',
				'running',
				'Fermeture de la connexion...'
			);

			$phpmailer->smtpClose();

			Alert404_Test_Progress::update_step(
				'Fermeture de la connexion',
				'success',
				'Connexion fermée'
			);

			return array(
				'success' => true,
				'message' => 'Connexion SMTP réussie! ✓',
			);
		} catch ( Exception $e ) {
			$error_message = $e->getMessage();

			// Déterminer à quelle étape l'erreur s'est produite
			if ( strpos( $error_message, 'SMTP connect' ) !== false ) {
				Alert404_Test_Progress::update_step(
					'Connexion au serveur',
					'error',
					'Impossible de se connecter: ' . $error_message
				);
			} elseif ( strpos( $error_message, 'authenticate' ) !== false ) {
				Alert404_Test_Progress::update_step(
					'Authentification',
					'error',
					'Authentification échouée: ' . $error_message
				);
			} else {
				Alert404_Test_Progress::update_step(
					'Envoi de l\'email de test',
					'error',
					'Erreur d\'envoi: ' . $error_message
				);
			}

			return array(
				'success' => false,
				'message' => 'Erreur de connexion SMTP: ' . $error_message,
			);
		}//end try
	}
}
