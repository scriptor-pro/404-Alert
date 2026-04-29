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
	 * Teste la connexion SMTP avec les paramètres du formulaire actuellement visible
	 *
	 * @param array $formData Données du formulaire SMTP.
	 * @return array<string, bool|string> ['success' => bool, 'message' => string]
	 */
	public static function test_connection( array $formData = array() ): array {
		Alert404_Test_Progress::init_test();

		// Récupère les paramètres du formulaire ou de la configuration stockée
		$config = self::extract_smtp_config_from_form( $formData );

		// Step 1: Vérification de la configuration
		Alert404_Test_Progress::update_step(
			'Vérification de la configuration',
			'running',
			'Validation des paramètres SMTP...'
		);

		$validation_errors = self::validate_smtp_config( $config );
		if ( ! empty( $validation_errors ) ) {
			$error_msg = implode( ' | ', $validation_errors );
			Alert404_Test_Progress::update_step(
				'Vérification de la configuration',
				'error',
				$error_msg
			);
			return array(
				'success' => false,
				'message' => $error_msg,
			);
		}

		Alert404_Test_Progress::update_step(
			'Vérification de la configuration',
			'success',
			'✓ Serveur: ' . $config['host'] . ':' . $config['port']
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
				'Connexion TCP',
				'running',
				'Connexion à ' . $config['host'] . ':' . $config['port'] . '...'
			);

			$phpmailer->isSMTP();
			$phpmailer->Host       = $config['host'];
			$phpmailer->Port       = (int) $config['port'];
			$phpmailer->SMTPAuth   = true;
			$phpmailer->Username   = $config['username'];
			$phpmailer->Password   = $config['password'];
			$phpmailer->SMTPSecure = $config['encryption'];
			$phpmailer->Timeout    = 30;

			// Try to establish SMTP connection
			if ( ! $phpmailer->smtpConnect() ) {
				throw new Exception(
					'Impossible de se connecter au serveur SMTP. Vérifiez que le serveur et le port sont corrects.'
				);
			}

			Alert404_Test_Progress::update_step(
				'Connexion TCP',
				'success',
				'✓ Connecté au serveur SMTP'
			);

			// Step 3: Authentification
			Alert404_Test_Progress::update_step(
				'Authentification',
				'running',
				'Authentification avec l\'identifiant: ' . $config['username']
			);

			if ( ! $phpmailer->smtpAuth() ) {
				throw new Exception(
					'Authentification SMTP échouée. Vérifiez votre identifiant et mot de passe.'
				);
			}

			Alert404_Test_Progress::update_step(
				'Authentification',
				'success',
				'✓ Authentification réussie'
			);

			// Step 4: Configuration de l'email
			Alert404_Test_Progress::update_step(
				'Configuration du test',
				'running',
				'Préparation du message de test...'
			);

			$phpmailer->setFrom( $config['from_email'], $config['from_name'] );
			$phpmailer->addAddress( $config['from_email'] );
			$phpmailer->Subject = '[404 Alert Test] Test de connexion SMTP';
			$phpmailer->isHTML( true );
			$phpmailer->Body = '<p>Ceci est un email de test du plugin 404 Alert.</p><p>✓ Si vous recevez ce message, votre configuration SMTP est correcte.</p>';

			Alert404_Test_Progress::update_step(
				'Configuration du test',
				'success',
				'✓ Email préparé'
			);

			// Step 5: Envoi de l'email de test
			Alert404_Test_Progress::update_step(
				'Envoi de l\'email',
				'running',
				'Envoi de l\'email de test à ' . $config['from_email'] . '...'
			);

			$phpmailer->send();

			Alert404_Test_Progress::update_step(
				'Envoi de l\'email',
				'success',
				'✓ Email de test envoyé'
			);

			// Step 6: Fermeture
			Alert404_Test_Progress::update_step(
				'Fermeture',
				'running',
				'Fermeture de la connexion...'
			);

			$phpmailer->smtpClose();

			Alert404_Test_Progress::update_step(
				'Fermeture',
				'success',
				'✓ Connexion fermée'
			);

			return array(
				'success' => true,
				'message' => 'Connexion SMTP réussie! ✓ Un email de test a été envoyé à ' . $config['from_email'],
			);
		} catch ( Exception $e ) {
			$error_msg = $e->getMessage();
			$pedagogical_msg = self::get_pedagogical_error_message( $error_msg, $config );

			// Déterminer à quelle étape l'erreur s'est produite
			if ( strpos( $error_msg, 'connect' ) !== false ) {
				Alert404_Test_Progress::update_step(
					'Connexion TCP',
					'error',
					$pedagogical_msg
				);
			} elseif ( strpos( $error_msg, 'authenticate' ) !== false || strpos( $error_msg, 'Authentification' ) !== false ) {
				Alert404_Test_Progress::update_step(
					'Authentification',
					'error',
					$pedagogical_msg
				);
			} else {
				Alert404_Test_Progress::update_step(
					'Envoi de l\'email',
					'error',
					$pedagogical_msg
				);
			}

			return array(
				'success' => false,
				'message' => $pedagogical_msg,
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
	 * Génère un message d'erreur pédagogique en fonction du type d'erreur
	 *
	 * @param string $error_msg Message d'erreur brut.
	 * @param array  $config Configuration SMTP.
	 * @return string Message d'erreur pédagogique
	 */
	private static function get_pedagogical_error_message( string $error_msg, array $config ): string {
		// Erreurs de connexion
		if ( strpos( $error_msg, 'connect' ) !== false || strpos( $error_msg, 'Could not resolve' ) !== false ) {
			return '❌ Impossible de se connecter au serveur ' . $config['host'] . ':' . $config['port'] . '. '
				. 'Vérifiez: '
				. '(1) Le serveur SMTP est correct, '
				. '(2) Le port est correct (' . $config['port'] . ' pour TLS, 465 pour SSL, 25 pour SMTP classique), '
				. '(3) Votre connexion Internet est active.';
		}

		// Erreurs d'authentification
		if ( strpos( $error_msg, 'authenticate' ) !== false || strpos( $error_msg, 'Username and Password not accepted' ) !== false ) {
			return '❌ Authentification échouée. '
				. 'Vérifiez: '
				. '(1) L\'identifiant et le mot de passe sont corrects, '
				. '(2) Pour Gmail, utilisez un mot de passe d\'application (pas le mot de passe Google), '
				. '(3) Pour les services avec API, assurez-vous que c\'est une clé API valide.';
		}

		// Erreurs de protocole
		if ( strpos( $error_msg, 'STARTTLS' ) !== false ) {
			return '❌ Problème de chiffrement STARTTLS. '
				. 'Essayez de changer le chiffrement: SSL (port 465) ou TLS (port 587).';
		}

		// Erreurs d'envoi d'email
		if ( strpos( $error_msg, 'Relay access denied' ) !== false ) {
			return '❌ Accès relai refusé. Le serveur SMTP ne vous permet pas d\'envoyer des emails. '
				. 'Vérifiez que votre adresse email est autorisée sur le serveur.';
		}

		// Message par défaut
		return '❌ Erreur SMTP: ' . $error_msg;
	}
}
