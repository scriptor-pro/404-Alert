<?php
/**
 * Tests unitaires pour Alert404_Request_Info
 */

class Test_Alert404_Request_Info extends Alert404_UnitTestCase {

	/**
	 * État avant chaque test
	 */
	public function setUp(): void {
		parent::setUp();
		// Nettoyer les variables globales $_SERVER
		$this->reset_server_vars();
	}

	/**
	 * État après chaque test
	 */
	public function tearDown(): void {
		parent::tearDown();
		$this->reset_server_vars();
	}

	/**
	 * Réinitialise les variables $_SERVER
	 */
	private function reset_server_vars(): void {
		$_SERVER['REQUEST_URI']       = '/test-path';
		$_SERVER['REQUEST_METHOD']    = 'GET';
		$_SERVER['REMOTE_ADDR']       = '127.0.0.1';
		$_SERVER['HTTP_USER_AGENT']   = 'Mozilla/5.0';
		$_SERVER['HTTP_REFERER']      = '';
		$_SERVER['HTTP_ACCEPT_LANGUAGE'] = '';
	}

	/**
	 * Teste que gather() retourne un array
	 */
	public function test_gather_returns_array() {
		$info = Alert404_Request_Info::gather();

		$this->assertIsArray( $info );
	}

	/**
	 * Teste que gather() inclut les champs requis
	 */
	public function test_gather_includes_required_fields() {
		$info = Alert404_Request_Info::gather();

		$required_fields = array(
			'url',
			'full_url',
			'method',
			'ip',
			'referrer',
			'user_agent',
			'language',
			'browser',
			'os',
			'device',
			'user_readable',
			'wordpress',
			'timestamp',
			'timestamp_unix',
		);

		foreach ( $required_fields as $field ) {
			$this->assertArrayHasKey( $field, $info, "Champ $field manquant" );
		}
	}

	/**
	 * Teste la collecte de l'IP locale
	 */
	public function test_gather_gets_local_ip() {
		$_SERVER['REMOTE_ADDR'] = '192.168.1.100';

		$info = Alert404_Request_Info::gather();

		$this->assertEquals( '192.168.1.100', $info['ip'] );
	}

	/**
	 * Teste la collecte du User-Agent
	 */
	public function test_gather_gets_user_agent() {
		$_SERVER['HTTP_USER_AGENT'] = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)';

		$info = Alert404_Request_Info::gather();

		$this->assertStringContainsString( 'Mozilla', $info['user_agent'] );
	}

	/**
	 * Teste la collecte du Referrer
	 */
	public function test_gather_gets_referrer() {
		$_SERVER['HTTP_REFERER'] = 'https://google.com/search';

		$info = Alert404_Request_Info::gather();

		$this->assertStringContainsString( 'google', $info['referrer'] );
	}

	/**
	 * Teste la collecte de la méthode HTTP
	 */
	public function test_gather_gets_http_method() {
		$_SERVER['REQUEST_METHOD'] = 'POST';

		$info = Alert404_Request_Info::gather();

		$this->assertEquals( 'POST', $info['method'] );
	}

	/**
	 * Teste la collecte de l'URL
	 */
	public function test_gather_gets_url() {
		$_SERVER['REQUEST_URI'] = '/products/nonexistent-product';

		$info = Alert404_Request_Info::gather();

		$this->assertStringContainsString( 'products', $info['url'] );
	}

	/**
	 * Teste que les timestamps sont valides
	 */
	public function test_gather_timestamps_are_valid() {
		$info = Alert404_Request_Info::gather();

		// Timestamp unix doit être un entier
		$this->assertIsInt( $info['timestamp_unix'] );

		// Timestamp string doit ressembler à une date
		$this->assertStringContainsString( '-', $info['timestamp'] );
		$this->assertStringContainsString( ':', $info['timestamp'] );
	}

	/**
	 * Teste que les infos WordPress sont collectées
	 */
	public function test_gather_wordpress_info() {
		$info = Alert404_Request_Info::gather();

		$this->assertIsArray( $info['wordpress'] );
		$this->assertArrayHasKey( 'logged_in', $info['wordpress'] );
		$this->assertArrayHasKey( 'user_id', $info['wordpress'] );
		$this->assertArrayHasKey( 'user_name', $info['wordpress'] );
		$this->assertArrayHasKey( 'user_email', $info['wordpress'] );
	}

	/**
	 * Teste que les infos navigateur sont collectées
	 */
	public function test_gather_browser_info() {
		$info = Alert404_Request_Info::gather();

		$this->assertIsArray( $info['browser'] );
		$this->assertArrayHasKey( 'name', $info['browser'] );
		$this->assertArrayHasKey( 'version', $info['browser'] );
	}

	/**
	 * Teste que les infos OS sont collectées
	 */
	public function test_gather_os_info() {
		$info = Alert404_Request_Info::gather();

		$this->assertIsArray( $info['os'] );
		$this->assertArrayHasKey( 'name', $info['os'] );
		$this->assertArrayHasKey( 'version', $info['os'] );
	}

	/**
	 * Teste que le type d'appareil est collecté
	 */
	public function test_gather_device_type() {
		$info = Alert404_Request_Info::gather();

		$this->assertIsString( $info['device'] );
	}

	/**
	 * Teste la collecte de l'IP valide
	 */
	public function test_get_client_ip_valid_ipv4() {
		$_SERVER['REMOTE_ADDR'] = '203.0.113.1';

		$ip = Alert404_Request_Info::get_client_ip();

		$this->assertEquals( '203.0.113.1', $ip );
	}

	/**
	 * Teste que get_client_ip retourne "Invalid" pour une IP invalide
	 */
	public function test_get_client_ip_invalid_returns_invalid() {
		$_SERVER['REMOTE_ADDR'] = 'not-an-ip';

		$ip = Alert404_Request_Info::get_client_ip();

		$this->assertEquals( 'Invalid', $ip );
	}

	/**
	 * Teste que get_client_ip fonctionne avec IPv6
	 */
	public function test_get_client_ip_ipv6() {
		$_SERVER['REMOTE_ADDR'] = '2001:db8::1';

		$ip = Alert404_Request_Info::get_client_ip();

		$this->assertEquals( '2001:db8::1', $ip );
	}

	/**
	 * Teste que get_client_ip retourne défaut si REMOTE_ADDR manquant
	 */
	public function test_get_client_ip_missing_remote_addr() {
		unset( $_SERVER['REMOTE_ADDR'] );

		$ip = Alert404_Request_Info::get_client_ip();

		$this->assertEquals( 'Invalid', $ip );
	}

	/**
	 * Teste que l'URL est tronquée si trop longue
	 */
	public function test_gather_truncates_long_url() {
		// URL très longue (2000+ chars)
		$_SERVER['REQUEST_URI'] = '/' . str_repeat( 'a', 2500 );

		$info = Alert404_Request_Info::gather();

		// L'URL devrait être tronquée
		$this->assertLessThanOrEqual( 2000, strlen( $info['url'] ) );
	}

	/**
	 * Teste que le User-Agent est tronqué si trop long
	 */
	public function test_gather_truncates_long_user_agent() {
		// User-Agent très long
		$_SERVER['HTTP_USER_AGENT'] = str_repeat( 'x', 600 );

		$info = Alert404_Request_Info::gather();

		// Le User-Agent devrait être tronqué
		$this->assertLessThanOrEqual( 500, strlen( $info['user_agent'] ) );
	}

	/**
	 * Teste que le Referrer est tronqué si trop long
	 */
	public function test_gather_truncates_long_referrer() {
		// Referrer très long
		$_SERVER['HTTP_REFERER'] = 'http://example.com/' . str_repeat( 'a', 2500 );

		$info = Alert404_Request_Info::gather();

		// Le Referrer devrait être tronqué
		$this->assertLessThanOrEqual( 2000, strlen( $info['referrer'] ) );
	}

	/**
	 * Teste la collecte de la langue d'acceptation
	 */
	public function test_gather_accept_language() {
		$_SERVER['HTTP_ACCEPT_LANGUAGE'] = 'fr-FR,fr;q=0.9,en;q=0.8';

		$info = Alert404_Request_Info::gather();

		// Devrait contenir "fr" en premier
		$this->assertStringContainsString( 'fr', strtolower( $info['language'] ) );
	}

	/**
	 * Teste que les données non-loggées restent non-loggées
	 */
	public function test_gather_user_not_logged_in() {
		// Vérifier que l'utilisateur n'est pas connecté
		$this->assertFalse( is_user_logged_in() );

		$info = Alert404_Request_Info::gather();

		$this->assertFalse( $info['wordpress']['logged_in'] );
		$this->assertNull( $info['wordpress']['user_id'] );
		$this->assertNull( $info['wordpress']['user_name'] );
		$this->assertNull( $info['wordpress']['user_email'] );
	}

	/**
	 * Teste que les données loggées sont collectées
	 */
	public function test_gather_user_logged_in() {
		// Créer un utilisateur de test
		$user_id = self::factory()->user->create( array( 'user_login' => 'testuser' ) );

		// Se connecter
		wp_set_current_user( $user_id );

		$info = Alert404_Request_Info::gather();

		$this->assertTrue( $info['wordpress']['logged_in'] );
		$this->assertEquals( $user_id, $info['wordpress']['user_id'] );
		$this->assertEquals( 'testuser', $info['wordpress']['user_name'] );
		$this->assertNotEmpty( $info['wordpress']['user_email'] );

		// Cleanup
		wp_set_current_user( 0 );
	}

	/**
	 * Teste que full_url est formé correctement
	 */
	public function test_gather_full_url_is_formed() {
		$_SERVER['REQUEST_URI'] = '/test';
		$_SERVER['HTTP_HOST']     = 'example.com';

		$info = Alert404_Request_Info::gather();

		// Full URL devrait contenir le domaine
		$this->assertStringContainsString( 'test', $info['full_url'] );
	}

	/**
	 * Teste que la requête GET est détectée
	 */
	public function test_gather_request_method_get() {
		$_SERVER['REQUEST_METHOD'] = 'GET';

		$info = Alert404_Request_Info::gather();

		$this->assertEquals( 'GET', $info['method'] );
	}

	/**
	 * Teste que la requête POST est détectée
	 */
	public function test_gather_request_method_post() {
		$_SERVER['REQUEST_METHOD'] = 'POST';

		$info = Alert404_Request_Info::gather();

		$this->assertEquals( 'POST', $info['method'] );
	}

	/**
	 * Teste que la requête PUT est détectée
	 */
	public function test_gather_request_method_put() {
		$_SERVER['REQUEST_METHOD'] = 'PUT';

		$info = Alert404_Request_Info::gather();

		$this->assertEquals( 'PUT', $info['method'] );
	}

	/**
	 * Teste que la requête DELETE est détectée
	 */
	public function test_gather_request_method_delete() {
		$_SERVER['REQUEST_METHOD'] = 'DELETE';

		$info = Alert404_Request_Info::gather();

		$this->assertEquals( 'DELETE', $info['method'] );
	}
}
