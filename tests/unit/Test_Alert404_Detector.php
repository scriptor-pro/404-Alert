<?php
/**
 * Tests unitaires pour Alert404_Detector
 */

class Test_Alert404_Detector extends Alert404_UnitTestCase {

	public function setUp(): void {
		parent::setUp();
		$this->setup_plugin_options();
		add_filter( '404_alert_trusted_proxies', array( $this, 'trusted_proxies' ) );
	}

	public function tearDown(): void {
		remove_filter( '404_alert_trusted_proxies', array( $this, 'trusted_proxies' ) );
		parent::tearDown();
	}

	public function trusted_proxies( $proxies ) {
		unset( $proxies );
		return array( '198.51.100.10', '2001:db8::ff' );
	}

	/**
	 * Teste que le hook template_redirect est enregistré
	 */
	public function test_init_registers_hook() {
		// Réinitialiser le hook pour le test
		Alert404_Detector::init();

		// Vérifier que le hook est enregistré
		$this->assertTrue( has_action( 'template_redirect' ) );
	}

	/**
	 * Teste que les non-404 ne déclenchent rien
	 */
	public function test_non_404_does_not_trigger() {
		// Ne pas créer de 404
		global $wp_query;
		$wp_query->is_404 = false;

		// Mock wp_mail pour vérifier qu'il n'est pas appelé
		$called = false;

		add_filter(
			'pre_wp_mail',
			function() use ( &$called ) {
				$called = true;
				return true;
			}
		);

		Alert404_Detector::on_template_redirect();

		$this->assertFalse( $called, 'wp_mail ne devrait pas être appelé pour les non-404' );
	}

	/**
	 * Teste l'extraction de l'IP REMOTE_ADDR
	 */
	public function test_ip_extraction_from_remote_addr() {
		$_SERVER['REMOTE_ADDR'] = '192.168.1.100';
		unset( $_SERVER['HTTP_X_FORWARDED_FOR'] );

		// Utiliser la réflexion pour tester la méthode privée
		$reflection = new ReflectionClass( 'Alert404_Detector' );
		$method     = $reflection->getMethod( 'get_ip' );
		$method->setAccessible( true );

		$ip = $method->invoke( null );

		$this->assertEquals( '192.168.1.100', $ip );
	}

	/**
	 * Teste l'extraction de l'IP depuis HTTP_X_FORWARDED_FOR
	 */
	public function test_ip_extraction_from_x_forwarded_for() {
		$_SERVER['REMOTE_ADDR']       = '198.51.100.10';
		$_SERVER['HTTP_X_FORWARDED_FOR'] = '203.0.113.1, 198.51.100.1';

		$reflection = new ReflectionClass( 'Alert404_Detector' );
		$method     = $reflection->getMethod( 'get_ip' );
		$method->setAccessible( true );

		$ip = $method->invoke( null );

		// Devrait prendre la première IP valide
		$this->assertEquals( '203.0.113.1', $ip );
	}

	/**
	 * Teste que X-Forwarded-For est ignoré si le proxy n'est pas de confiance
	 */
	public function test_untrusted_proxy_header_is_ignored() {
		$_SERVER['REMOTE_ADDR']       = '203.0.113.250';
		$_SERVER['HTTP_X_FORWARDED_FOR'] = '10.0.0.1';

		$reflection = new ReflectionClass( 'Alert404_Detector' );
		$method     = $reflection->getMethod( 'get_ip' );
		$method->setAccessible( true );

		$ip = $method->invoke( null );

		$this->assertEquals( '203.0.113.250', $ip );
	}

	/**
	 * Teste l'extraction de l'IP avec espaces à trimmer
	 */
	public function test_ip_extraction_with_whitespace() {
		$_SERVER['REMOTE_ADDR']       = '198.51.100.10';
		$_SERVER['HTTP_X_FORWARDED_FOR'] = '  192.168.1.50  , 10.0.0.1';

		$reflection = new ReflectionClass( 'Alert404_Detector' );
		$method     = $reflection->getMethod( 'get_ip' );
		$method->setAccessible( true );

		$ip = $method->invoke( null );

		$this->assertEquals( '192.168.1.50', $ip );
	}

	/**
	 * Teste que les IPs invalides sont ignorées
	 */
	public function test_invalid_ips_are_ignored() {
		$_SERVER['REMOTE_ADDR']       = '198.51.100.10';
		$_SERVER['HTTP_X_FORWARDED_FOR'] = 'invalid-ip, another-invalid, 127.0.0.1';

		$reflection = new ReflectionClass( 'Alert404_Detector' );
		$method     = $reflection->getMethod( 'get_ip' );
		$method->setAccessible( true );

		$ip = $method->invoke( null );

		// Devrait sauter les invalides et prendre la première valide
		$this->assertEquals( '127.0.0.1', $ip );
	}

	/**
	 * Teste le payload collecté
	 */
	public function test_payload_collection() {
		unset( $_SERVER['HTTP_CF_CONNECTING_IP'] );
		unset( $_SERVER['HTTP_X_FORWARDED_FOR'] );
		unset( $_SERVER['HTTP_X_FORWARDED'] );
		unset( $_SERVER['HTTP_FORWARDED_FOR'] );
		unset( $_SERVER['HTTP_FORWARDED'] );
		$_SERVER['REMOTE_ADDR'] = '192.168.1.1';
		$_SERVER['REQUEST_URI'] = '/page-inexistante';
		$_SERVER['HTTP_REFERER'] = 'https://google.com';
		$_SERVER['HTTP_USER_AGENT'] = 'Mozilla/5.0 Chrome/90.0';

		$reflection = new ReflectionClass( 'Alert404_Detector' );
		$method     = $reflection->getMethod( 'collect_payload' );
		$method->setAccessible( true );

		$payload = $method->invoke( null, '192.168.1.1' );

		$this->assertArrayHasKey( 'url', $payload );
		$this->assertArrayHasKey( 'referrer', $payload );
		$this->assertArrayHasKey( 'user_agent', $payload );
		$this->assertArrayHasKey( 'ip', $payload );
		$this->assertArrayHasKey( 'timestamp', $payload );

		$this->assertEquals( '192.168.1.1', $payload['ip'] );
		$this->assertStringContainsString( '/page-inexistante', $payload['url'] );
	}

	/**
	 * Teste que le payload est limité en taille
	 */
	public function test_payload_is_size_limited() {
		$_SERVER['REQUEST_URI']   = str_repeat( '/a', 3000 ); // URL très longue
		$_SERVER['HTTP_REFERER']  = str_repeat( 'x', 3000 );
		$_SERVER['HTTP_USER_AGENT'] = str_repeat( 'y', 1000 );

		$reflection = new ReflectionClass( 'Alert404_Detector' );
		$method     = $reflection->getMethod( 'collect_payload' );
		$method->setAccessible( true );

		$payload = $method->invoke( null, '192.168.1.1' );

		// Vérifier que les limites de taille sont respectées
		$this->assertLessThanOrEqual( 2000, strlen( $payload['url'] ) );
		$this->assertLessThanOrEqual( 2000, strlen( $payload['referrer'] ) );
		$this->assertLessThanOrEqual( 500, strlen( $payload['user_agent'] ) );
	}

	/**
	 * Teste que les valeurs NULL sont gérées
	 */
	public function test_null_values_in_payload() {
		unset( $_SERVER['HTTP_REFERER'] );
		unset( $_SERVER['HTTP_USER_AGENT'] );

		$reflection = new ReflectionClass( 'Alert404_Detector' );
		$method     = $reflection->getMethod( 'collect_payload' );
		$method->setAccessible( true );

		$payload = $method->invoke( null, '192.168.1.1' );

		// Les champs manquants peuvent être null
		$this->assertSame( '', $payload['referrer'] );
		$this->assertSame( '', $payload['user_agent'] );
	}

	/**
	 * Teste l'extraction de l'IP manquante
	 */
	public function test_missing_ip_returns_empty_string() {
		unset( $_SERVER['REMOTE_ADDR'] );
		unset( $_SERVER['HTTP_X_FORWARDED_FOR'] );

		$reflection = new ReflectionClass( 'Alert404_Detector' );
		$method     = $reflection->getMethod( 'get_ip' );
		$method->setAccessible( true );

		$ip = $method->invoke( null );

		$this->assertEquals( '', $ip );
	}

	/**
	 * Teste que l'ordre des IPs est respecté
	 */
	public function test_ip_order_is_preserved() {
		$_SERVER['REMOTE_ADDR']       = '198.51.100.10';
		$_SERVER['HTTP_X_FORWARDED_FOR'] = '10.0.0.1, 192.168.1.1, 172.16.0.1';

		$reflection = new ReflectionClass( 'Alert404_Detector' );
		$method     = $reflection->getMethod( 'get_ip' );
		$method->setAccessible( true );

		$ip = $method->invoke( null );

		// Doit prendre la première valide dans l'ordre
		$this->assertEquals( '10.0.0.1', $ip );
	}

	/**
	 * Teste la gestion des IPs IPv6
	 */
	public function test_ipv6_is_extracted() {
		$_SERVER['REMOTE_ADDR']       = '2001:db8::ff';
		$_SERVER['HTTP_X_FORWARDED_FOR'] = '2001:db8::1';

		$reflection = new ReflectionClass( 'Alert404_Detector' );
		$method     = $reflection->getMethod( 'get_ip' );
		$method->setAccessible( true );

		$ip = $method->invoke( null );

		$this->assertEquals( '2001:db8::1', $ip );
	}
}
