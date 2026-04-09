<?php
/**
 * Tests unitaires pour Alert404_SMTP_Handler
 */

class Test_Alert404_SMTP_Handler extends Alert404_UnitTestCase {

	/**
	 * État avant chaque test
	 */
	public function setUp(): void {
		parent::setUp();
		delete_option( '404_alert_smtp_options' );
	}

	/**
	 * Teste que get_smtp_config retourne un array
	 */
	public function test_get_smtp_config_returns_array() {
		$config = Alert404_SMTP_Handler::get_smtp_config();

		$this->assertIsArray( $config );
		$this->assertArrayHasKey( 'host', $config );
		$this->assertArrayHasKey( 'port', $config );
		$this->assertArrayHasKey( 'username', $config );
		$this->assertArrayHasKey( 'password', $config );
		$this->assertArrayHasKey( 'encryption', $config );
		$this->assertArrayHasKey( 'from_email', $config );
		$this->assertArrayHasKey( 'from_name', $config );
	}

	/**
	 * Teste que get_smtp_config retourne les valeurs configurées
	 */
	public function test_get_smtp_config_returns_configured_values() {
		$options = array(
			'host'       => 'smtp.gmail.com',
			'port'       => 587,
			'username'   => 'test@gmail.com',
			'password'   => '',
			'encryption' => 'tls',
			'from_email' => 'test@gmail.com',
			'from_name'  => '404 Alert',
		);

		update_option( '404_alert_smtp_options', $options );

		$config = Alert404_SMTP_Handler::get_smtp_config();

		$this->assertEquals( 'smtp.gmail.com', $config['host'] );
		$this->assertEquals( 587, $config['port'] );
		$this->assertEquals( 'test@gmail.com', $config['username'] );
		$this->assertEquals( 'tls', $config['encryption'] );
		$this->assertEquals( 'test@gmail.com', $config['from_email'] );
		$this->assertEquals( '404 Alert', $config['from_name'] );
	}

	/**
	 * Teste que get_smtp_config applique les défauts
	 */
	public function test_get_smtp_config_applies_defaults() {
		// Vider la configuration
		delete_option( '404_alert_smtp_options' );

		$config = Alert404_SMTP_Handler::get_smtp_config();

		// Port par défaut
		$this->assertEquals( 587, $config['port'] );

		// Encryption par défaut
		$this->assertEquals( 'tls', $config['encryption'] );

		// from_email par défaut (admin_email)
		$this->assertEquals( get_option( 'admin_email' ), $config['from_email'] );

		// from_name par défaut (blog name)
		$this->assertEquals( get_bloginfo( 'name' ), $config['from_name'] );
	}

	/**
	 * Teste que encrypt_password_for_storage chiffre un password
	 */
	public function test_encrypt_password_for_storage() {
		$password = 'MySecretPassword123';
		$encrypted = Alert404_SMTP_Handler::encrypt_password_for_storage( $password );

		// Devrait être un string non vide
		$this->assertIsString( $encrypted );
		$this->assertNotEmpty( $encrypted );

		// Devrait contenir le prefix de version
		$this->assertStringContainsString( 'enc:v1:', $encrypted );

		// Ne devrait pas contenir le password en clair
		$this->assertStringNotContainsString( $password, $encrypted );
	}

	/**
	 * Teste que encrypt_password_for_storage gère les passwords vides
	 */
	public function test_encrypt_password_for_storage_empty() {
		$encrypted = Alert404_SMTP_Handler::encrypt_password_for_storage( '' );

		// Devrait retourner string vide
		$this->assertEquals( '', $encrypted );
	}

	/**
	 * Teste le roundtrip encrypt/decrypt
	 */
	public function test_encrypt_decrypt_roundtrip() {
		$original = 'MyComplexPassword!@#$%';

		// Chiffrer
		$encrypted = Alert404_SMTP_Handler::encrypt_password_for_storage( $original );
		$this->assertNotEmpty( $encrypted );

		// Stocker
		$options = array( 'password' => $encrypted );
		update_option( '404_alert_smtp_options', $options );

		// Récupérer et déchiffrer (via get_smtp_config)
		$config = Alert404_SMTP_Handler::get_smtp_config();

		// Le password déchiffré devrait matcher l'original
		$this->assertEquals( $original, $config['password'] );
	}

	/**
	 * Teste que les différents ports sont acceptés
	 */
	public function test_smtp_ports_accepted() {
		$valid_ports = array( 25, 465, 587, 2525, 8025 );

		foreach ( $valid_ports as $port ) {
			$options = array(
				'host'       => 'smtp.example.com',
				'port'       => $port,
				'username'   => 'test@example.com',
				'encryption' => 'tls',
			);

			update_option( '404_alert_smtp_options', $options );
			$config = Alert404_SMTP_Handler::get_smtp_config();

			$this->assertEquals( $port, $config['port'] );
		}
	}

	/**
	 * Teste que les différents chiffrements sont acceptés
	 */
	public function test_smtp_encryptions_accepted() {
		$valid_encryptions = array( 'tls', 'ssl', 'none' );

		foreach ( $valid_encryptions as $encryption ) {
			$options = array(
				'host'       => 'smtp.example.com',
				'encryption' => $encryption,
			);

			update_option( '404_alert_smtp_options', $options );
			$config = Alert404_SMTP_Handler::get_smtp_config();

			$this->assertEquals( $encryption, $config['encryption'] );
		}
	}

	/**
	 * Teste que send() retourne false si configuration incomplète
	 */
	public function test_send_returns_false_if_config_incomplete() {
		// Configuration vide
		delete_option( '404_alert_smtp_options' );

		$args = array(
			'to'      => 'test@example.com',
			'subject' => 'Test',
			'message' => 'Test message',
		);

		$result = Alert404_SMTP_Handler::send( $args );

		// Devrait retourner false
		$this->assertFalse( $result );
	}

	/**
	 * Teste que test_connection() retourne un array
	 */
	public function test_test_connection_returns_array() {
		$result = Alert404_SMTP_Handler::test_connection();

		$this->assertIsArray( $result );
		$this->assertArrayHasKey( 'success', $result );
		$this->assertArrayHasKey( 'message', $result );
	}

	/**
	 * Teste que test_connection() retourne false si config incomplète
	 */
	public function test_test_connection_returns_false_if_incomplete_config() {
		// Configuration vide
		delete_option( '404_alert_smtp_options' );

		$result = Alert404_SMTP_Handler::test_connection();

		$this->assertFalse( $result['success'] );
		$this->assertStringContainsString( 'incomplète', $result['message'] );
	}

	/**
	 * Teste que test_connection() retourne message si host manquant
	 */
	public function test_test_connection_missing_host() {
		$options = array(
			'host'     => '', // Vide
			'username' => 'test@example.com',
			'password' => 'password',
		);

		update_option( '404_alert_smtp_options', $options );

		$result = Alert404_SMTP_Handler::test_connection();

		$this->assertFalse( $result['success'] );
	}

	/**
	 * Teste que test_connection() retourne message si username manquant
	 */
	public function test_test_connection_missing_username() {
		$options = array(
			'host'     => 'smtp.example.com',
			'username' => '', // Vide
			'password' => 'password',
		);

		update_option( '404_alert_smtp_options', $options );

		$result = Alert404_SMTP_Handler::test_connection();

		$this->assertFalse( $result['success'] );
	}

	/**
	 * Teste que test_connection() retourne message si password manquant
	 */
	public function test_test_connection_missing_password() {
		$options = array(
			'host'     => 'smtp.example.com',
			'username' => 'test@example.com',
			'password' => '', // Vide
		);

		update_option( '404_alert_smtp_options', $options );

		$result = Alert404_SMTP_Handler::test_connection();

		$this->assertFalse( $result['success'] );
	}

	/**
	 * Teste que send() gère les args manquants
	 */
	public function test_send_handles_missing_args() {
		$options = array(
			'host'       => 'smtp.example.com',
			'port'       => 587,
			'username'   => 'test@example.com',
			'password'   => 'password',
			'encryption' => 'tls',
		);

		update_option( '404_alert_smtp_options', $options );

		// Args avec manque
		$args = array(
			'to' => 'recipient@example.com',
			// subject et message manquants
		);

		// Ne devrait pas faire d'erreur
		$result = Alert404_SMTP_Handler::send( $args );

		// Résultat dépend de la configuration SMTP réelle (probablement false en test)
		$this->assertIsBool( $result );
	}

	/**
	 * Teste que encrypt_password_for_storage produit des résultats différents avec des passwords différents
	 */
	public function test_encrypt_produces_different_results() {
		$password1 = 'Password123';
		$password2 = 'Password456';

		$encrypted1 = Alert404_SMTP_Handler::encrypt_password_for_storage( $password1 );
		$encrypted2 = Alert404_SMTP_Handler::encrypt_password_for_storage( $password2 );

		// Les deux encrypted doivent être différents
		$this->assertNotEquals( $encrypted1, $encrypted2 );
	}

	/**
	 * Teste que la configuration respecte les valeurs stockées
	 */
	public function test_config_respects_all_stored_values() {
		$options = array(
			'host'       => 'custom.smtp.com',
			'port'       => 2525,
			'username'   => 'custom_user',
			'password'   => '', // Pas chiffré en test
			'encryption' => 'ssl',
			'from_email' => 'sender@custom.com',
			'from_name'  => 'Custom Sender',
		);

		update_option( '404_alert_smtp_options', $options );
		$config = Alert404_SMTP_Handler::get_smtp_config();

		// Vérifier tous les champs
		foreach ( array( 'host', 'port', 'username', 'encryption', 'from_email', 'from_name' ) as $key ) {
			$this->assertEquals( $options[ $key ], $config[ $key ], "Valeur $key ne correspond pas" );
		}
	}
}
