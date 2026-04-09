<?php
/**
 * Tests unitaires pour Alert404_Mailer
 */

class Test_Alert404_Mailer extends Alert404_UnitTestCase {

	public function setUp(): void {
		parent::setUp();
		$this->setup_plugin_options();
		// Intercepter les emails
		add_filter( 'pre_wp_mail', [ $this, 'mock_wp_mail' ], 10, 2 );
	}

	public function tearDown(): void {
		remove_filter( 'pre_wp_mail', [ $this, 'mock_wp_mail' ] );
		parent::tearDown();
	}

	/**
	 * Stocke les appels à wp_mail pour inspection
	 */
	public static $mocked_emails = [];

	/**
	 * Mock pour wp_mail
	 */
	public function mock_wp_mail( $return, $args ) {
		self::$mocked_emails[] = $args;
		return true;
	}

	/**
	 * Obtient un payload de test valide
	 */
	private function get_test_payload() {
		return [
			'url'        => 'https://example.com/inexistent',
			'referrer'   => 'https://google.com',
			'userAgent'  => 'Mozilla/5.0',
			'ip'         => '192.168.1.1',
			'occurredAt' => '2024-01-01T12:00:00+00:00',
		];
	}

	/**
	 * Teste que l'email utilise le destinataire configuré
	 */
	public function test_email_uses_configured_recipient() {
		self::$mocked_emails = [];

		$this->setup_plugin_options( [ 'email' => 'admin@test.local' ] );

		$payload = $this->get_test_payload();
		Alert404_Mailer::send( $payload );

		$this->assertCount( 1, self::$mocked_emails );
		$this->assertEquals( 'admin@test.local', self::$mocked_emails[0]['to'] );
	}

	/**
	 * Teste que l'email utilise l'email admin par défaut
	 */
	public function test_email_uses_default_admin_email() {
		self::$mocked_emails = [];

		delete_option( '404_alert_options' );

		$payload = $this->get_test_payload();
		Alert404_Mailer::send( $payload );

		$this->assertCount( 1, self::$mocked_emails );
		$this->assertEquals( get_option( 'admin_email' ), self::$mocked_emails[0]['to'] );
	}

	/**
	 * Teste que le sujet contient le nom du site et l'URL
	 */
	public function test_email_subject_format() {
		self::$mocked_emails = [];

		$payload = $this->get_test_payload();
		Alert404_Mailer::send( $payload );

		$this->assertCount( 1, self::$mocked_emails );
		$subject = self::$mocked_emails[0]['subject'];

		$this->assertStringContainsString( '404', $subject );
		$this->assertStringContainsString( $payload['url'], $subject );
	}

	/**
	 * Teste que le contenu HTML est présent
	 */
	public function test_email_content_is_html() {
		self::$mocked_emails = [];

		$payload = $this->get_test_payload();
		Alert404_Mailer::send( $payload );

		$this->assertCount( 1, self::$mocked_emails );
		$message = self::$mocked_emails[0]['message'];

		$this->assertStringContainsString( '<div', $message );
		$this->assertStringContainsString( 'Erreur 404 Détectée', $message );
	}

	/**
	 * Teste que le contenu HTML contient les données
	 */
	public function test_email_content_contains_data() {
		self::$mocked_emails = [];

		$payload = $this->get_test_payload();
		Alert404_Mailer::send( $payload );

		$this->assertCount( 1, self::$mocked_emails );
		$message = self::$mocked_emails[0]['message'];

		$this->assertStringContainsString( $payload['url'], $message );
		$this->assertStringContainsString( $payload['ip'], $message );
		$this->assertStringContainsString( 'Navigateur', $message );
	}

	/**
	 * Teste que le Content-Type est défini correctement
	 */
	public function test_email_headers_content_type() {
		self::$mocked_emails = [];

		$payload = $this->get_test_payload();
		Alert404_Mailer::send( $payload );

		$this->assertCount( 1, self::$mocked_emails );
		$headers = self::$mocked_emails[0]['headers'];

		$this->assertIsArray( $headers );
		$this->assertContains( 'Content-Type: text/html; charset=UTF-8', $headers );
	}

	/**
	 * Teste le filtre 404_alert_email_to
	 */
	public function test_filter_email_to_is_applied() {
		self::$mocked_emails = [];

		add_filter(
			'404_alert_email_to',
			function( $to ) {
				return 'override@test.local';
			}
		);

		$payload = $this->get_test_payload();
		Alert404_Mailer::send( $payload );

		$this->assertEquals( 'override@test.local', self::$mocked_emails[0]['to'] );

		remove_filter( '404_alert_email_to', 'wp_filter_object_list' );
	}

	/**
	 * Teste le filtre 404_alert_email_subject
	 */
	public function test_filter_email_subject_is_applied() {
		self::$mocked_emails = [];

		add_filter(
			'404_alert_email_subject',
			function( $subject ) {
				return '[ALERT] ' . $subject;
			}
		);

		$payload = $this->get_test_payload();
		Alert404_Mailer::send( $payload );

		$this->assertStringStartsWith( '[ALERT]', self::$mocked_emails[0]['subject'] );

		remove_filter( '404_alert_email_subject', 'wp_filter_object_list' );
	}

	/**
	 * Teste le filtre 404_alert_email_body
	 */
	public function test_filter_email_body_is_applied() {
		self::$mocked_emails = [];

		add_filter(
			'404_alert_email_body',
			function( $body ) {
				return $body . '<p>FOOTER</p>';
			}
		);

		$payload = $this->get_test_payload();
		Alert404_Mailer::send( $payload );

		$this->assertStringContainsString( 'FOOTER', self::$mocked_emails[0]['message'] );

		remove_filter( '404_alert_email_body', 'wp_filter_object_list' );
	}

	/**
	 * Teste l'action 404_alert_email_sent
	 */
	public function test_action_email_sent_is_triggered() {
		self::$mocked_emails = [];

		$action_called = false;
		add_action(
			'404_alert_email_sent',
			function() use ( &$action_called ) {
				$action_called = true;
			}
		);

		$payload = $this->get_test_payload();
		Alert404_Mailer::send( $payload );

		$this->assertTrue( $action_called );

		remove_action( '404_alert_email_sent', 'wp_filter_object_list' );
	}

	/**
	 * Teste l'action 404_alert_email_failed si wp_mail retourne false
	 */
	public function test_action_email_failed_is_triggered_on_failure() {
		self::$mocked_emails = [];

		// Mock wp_mail pour retourner false
		add_filter(
			'pre_wp_mail',
			function() {
				return false;
			}
		);

		$action_called = false;
		add_action(
			'404_alert_email_failed',
			function() use ( &$action_called ) {
				$action_called = true;
			}
		);

		$payload = $this->get_test_payload();
		Alert404_Mailer::send( $payload );

		$this->assertTrue( $action_called );

		remove_filter( 'pre_wp_mail', 'wp_filter_object_list' );
		remove_action( '404_alert_email_failed', 'wp_filter_object_list' );
	}

	/**
	 * Teste que les données sensibles sont échappées
	 */
	public function test_data_is_escaped() {
		self::$mocked_emails = [];

		$payload           = $this->get_test_payload();
		$payload['url']    = 'https://example.com/<script>alert("xss")</script>';
		$payload['ip']     = '192.168.1.1<script>';

		Alert404_Mailer::send( $payload );

		$message = self::$mocked_emails[0]['message'];

		// Les < et > doivent être échappés ou encodés
		$this->assertStringNotContainsString( '<script>', $message );
	}

	/**
	 * Teste que le payload complet est inclus en JSON
	 */
	public function test_payload_json_is_included() {
		self::$mocked_emails = [];

		$payload = $this->get_test_payload();
		Alert404_Mailer::send( $payload );

		$message = self::$mocked_emails[0]['message'];

		// La méthode utilise json_encode du payload
		$this->assertStringContainsString( $payload['ip'], $message );
		$this->assertStringContainsString( 'occurredAt', $message );
	}
}
