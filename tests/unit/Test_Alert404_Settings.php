<?php
/**
 * Tests unitaires pour Alert404_Settings
 */

class Test_Alert404_Settings extends Alert404_UnitTestCase {

	/**
	 * État avant chaque test
	 */
	public function setUp(): void {
		parent::setUp();
		// Nettoyer les options avant chaque test
		delete_option( '404_alert_options' );
		delete_option( '404_alert_smtp_options' );
	}

	/**
	 * Teste que sanitize_options applique les valeurs par défaut
	 */
	public function test_sanitize_options_defaults() {
		$input = array();
		$result = Alert404_Settings::sanitize_options( $input );

		$this->assertIsArray( $result );
		$this->assertArrayHasKey( 'email', $result );
		$this->assertArrayHasKey( 'daily_limit', $result );
		$this->assertArrayHasKey( 'ip_cooldown', $result );
	}

	/**
	 * Teste que sanitize_options valide l'email
	 */
	public function test_sanitize_options_email() {
		$input = array( 'email' => 'test@example.com' );
		$result = Alert404_Settings::sanitize_options( $input );

		$this->assertEquals( 'test@example.com', $result['email'] );
	}

	/**
	 * Teste que sanitize_options rejette un email invalide
	 */
	public function test_sanitize_options_invalid_email() {
		$input = array( 'email' => 'invalid-email' );
		$result = Alert404_Settings::sanitize_options( $input );

		// Email invalide doit retourner vide ou défaut
		$this->assertIsString( $result['email'] );
	}

	/**
	 * Teste que sanitize_options limite la limite journalière
	 */
	public function test_sanitize_options_daily_limit_constraints() {
		// Test min constraint
		$input = array( 'daily_limit' => 0 );
		$result = Alert404_Settings::sanitize_options( $input );
		$this->assertGreaterThanOrEqual( 1, $result['daily_limit'] );

		// Test max constraint
		$input = array( 'daily_limit' => 50000 );
		$result = Alert404_Settings::sanitize_options( $input );
		$this->assertLessThanOrEqual( 10000, $result['daily_limit'] );

		// Test valeur valide
		$input = array( 'daily_limit' => 500 );
		$result = Alert404_Settings::sanitize_options( $input );
		$this->assertEquals( 500, $result['daily_limit'] );
	}

	/**
	 * Teste que sanitize_options limite le cooldown IP
	 */
	public function test_sanitize_options_ip_cooldown_constraints() {
		// Test min constraint (60 secondes)
		$input = array( 'ip_cooldown' => 10 );
		$result = Alert404_Settings::sanitize_options( $input );
		$this->assertGreaterThanOrEqual( 60, $result['ip_cooldown'] );

		// Test max constraint (3600 secondes)
		$input = array( 'ip_cooldown' => 5000 );
		$result = Alert404_Settings::sanitize_options( $input );
		$this->assertLessThanOrEqual( 3600, $result['ip_cooldown'] );

		// Test valeur valide
		$input = array( 'ip_cooldown' => 300 );
		$result = Alert404_Settings::sanitize_options( $input );
		$this->assertEquals( 300, $result['ip_cooldown'] );
	}

	/**
	 * Teste que sanitize_options gère les checkboxes
	 */
	public function test_sanitize_options_checkboxes() {
		// Test force_logging activé
		$input = array( 'force_logging' => '1' );
		$result = Alert404_Settings::sanitize_options( $input );
		$this->assertEquals( 1, $result['force_logging'] );

		// Test force_logging désactivé
		$input = array( 'force_logging' => '' );
		$result = Alert404_Settings::sanitize_options( $input );
		$this->assertEquals( 0, $result['force_logging'] );

		// Test enable_stats
		$input = array( 'enable_stats' => '1' );
		$result = Alert404_Settings::sanitize_options( $input );
		$this->assertEquals( 1, $result['enable_stats'] );
	}

	/**
	 * Teste que sanitize_smtp_options applique les valeurs par défaut
	 */
	public function test_sanitize_smtp_options_defaults() {
		$input = array();
		$result = Alert404_Settings::sanitize_smtp_options( $input );

		$this->assertIsArray( $result );
		$this->assertArrayHasKey( 'host', $result );
		$this->assertArrayHasKey( 'port', $result );
		$this->assertArrayHasKey( 'encryption', $result );
	}

	/**
	 * Teste que sanitize_smtp_options valide le port
	 */
	public function test_sanitize_smtp_options_port_constraints() {
		// Test min constraint
		$input = array( 'port' => 0 );
		$result = Alert404_Settings::sanitize_smtp_options( $input );
		$this->assertGreaterThanOrEqual( 1, $result['port'] );

		// Test max constraint
		$input = array( 'port' => 99999 );
		$result = Alert404_Settings::sanitize_smtp_options( $input );
		$this->assertLessThanOrEqual( 65535, $result['port'] );

		// Test valeurs valides
		foreach ( array( 25, 587, 465 ) as $port ) {
			$input = array( 'port' => $port );
			$result = Alert404_Settings::sanitize_smtp_options( $input );
			$this->assertEquals( $port, $result['port'] );
		}
	}

	/**
	 * Teste que sanitize_smtp_options valide l'encryption
	 */
	public function test_sanitize_smtp_options_encryption() {
		$valid_encryptions = array( 'tls', 'ssl', 'none' );

		foreach ( $valid_encryptions as $encryption ) {
			$input = array( 'encryption' => $encryption );
			$result = Alert404_Settings::sanitize_smtp_options( $input );
			$this->assertEquals( $encryption, $result['encryption'] );
		}

		// Test encryption invalide (doit retourner défaut)
		$input = array( 'encryption' => 'invalid' );
		$result = Alert404_Settings::sanitize_smtp_options( $input );
		$this->assertEquals( 'tls', $result['encryption'] );
	}

	/**
	 * Teste que sanitize_smtp_options nettoie le host
	 */
	public function test_sanitize_smtp_options_host() {
		$input = array( 'host' => 'smtp.gmail.com' );
		$result = Alert404_Settings::sanitize_smtp_options( $input );
		$this->assertEquals( 'smtp.gmail.com', $result['host'] );

		// Test host avec espaces (doit être nettoyé)
		$input = array( 'host' => '  smtp.gmail.com  ' );
		$result = Alert404_Settings::sanitize_smtp_options( $input );
		$this->assertEquals( 'smtp.gmail.com', $result['host'] );
	}

	/**
	 * Teste que sanitize_smtp_options nettoie username
	 */
	public function test_sanitize_smtp_options_username() {
		$input = array( 'username' => 'user@example.com' );
		$result = Alert404_Settings::sanitize_smtp_options( $input );
		$this->assertEquals( 'user@example.com', $result['username'] );
	}

	/**
	 * Teste que sanitize_smtp_options nettoie from_email
	 */
	public function test_sanitize_smtp_options_from_email() {
		$input = array( 'from_email' => 'sender@example.com' );
		$result = Alert404_Settings::sanitize_smtp_options( $input );
		$this->assertEquals( 'sender@example.com', $result['from_email'] );
	}

	/**
	 * Teste que sanitize_smtp_options nettoie from_name
	 */
	public function test_sanitize_smtp_options_from_name() {
		$input = array( 'from_name' => '404 Alert System' );
		$result = Alert404_Settings::sanitize_smtp_options( $input );
		$this->assertEquals( '404 Alert System', $result['from_name'] );
	}

	/**
	 * Teste que sanitize_smtp_options gère le password vide (conservation)
	 */
	public function test_sanitize_smtp_options_password_empty() {
		// Ajouter un password existant
		$existing = array( 'password' => 'encrypted_password' );
		update_option( '404_alert_smtp_options', $existing );

		// Essayer de sauvegarder avec password vide
		$input = array( 'password' => '' );
		$result = Alert404_Settings::sanitize_smtp_options( $input );

		// Devrait conserver le password existant
		$this->assertNotEmpty( $result['password'] );
	}

	/**
	 * Teste le flux complet de sanitization
	 */
	public function test_sanitize_options_complete_flow() {
		$input = array(
			'email'         => 'test@example.com',
			'daily_limit'   => 1000,
			'ip_cooldown'   => 600,
			'force_logging' => '1',
			'enable_stats'  => '0',
		);

		$result = Alert404_Settings::sanitize_options( $input );

		$this->assertEquals( 'test@example.com', $result['email'] );
		$this->assertEquals( 1000, $result['daily_limit'] );
		$this->assertEquals( 600, $result['ip_cooldown'] );
		$this->assertEquals( 1, $result['force_logging'] );
		$this->assertEquals( 0, $result['enable_stats'] );
	}

	/**
	 * Teste le flux SMTP complet
	 */
	public function test_sanitize_smtp_options_complete_flow() {
		$input = array(
			'host'       => 'smtp.gmail.com',
			'port'       => 587,
			'username'   => 'test@gmail.com',
			'password'   => 'app_password_123',
			'encryption' => 'tls',
			'from_email' => 'test@gmail.com',
			'from_name'  => '404 Alert',
		);

		$result = Alert404_Settings::sanitize_smtp_options( $input );

		$this->assertEquals( 'smtp.gmail.com', $result['host'] );
		$this->assertEquals( 587, $result['port'] );
		$this->assertEquals( 'test@gmail.com', $result['username'] );
		$this->assertNotEmpty( $result['password'] );
		$this->assertEquals( 'tls', $result['encryption'] );
		$this->assertEquals( 'test@gmail.com', $result['from_email'] );
		$this->assertEquals( '404 Alert', $result['from_name'] );
	}

	/**
	 * Teste que les options sont sauvegardées correctement
	 */
	public function test_options_are_saved() {
		$options = array(
			'email'       => 'admin@example.com',
			'daily_limit' => 750,
			'ip_cooldown' => 400,
		);

		update_option( '404_alert_options', $options );
		$saved = get_option( '404_alert_options' );

		$this->assertEquals( $options['email'], $saved['email'] );
		$this->assertEquals( $options['daily_limit'], $saved['daily_limit'] );
		$this->assertEquals( $options['ip_cooldown'], $saved['ip_cooldown'] );
	}

	/**
	 * Teste que les options SMTP sont sauvegardées
	 */
	public function test_smtp_options_are_saved() {
		$options = array(
			'host'       => 'smtp.example.com',
			'port'       => 587,
			'username'   => 'user@example.com',
			'encryption' => 'tls',
		);

		update_option( '404_alert_smtp_options', $options );
		$saved = get_option( '404_alert_smtp_options' );

		$this->assertEquals( $options['host'], $saved['host'] );
		$this->assertEquals( $options['port'], $saved['port'] );
		$this->assertEquals( $options['username'], $saved['username'] );
		$this->assertEquals( $options['encryption'], $saved['encryption'] );
	}
}
