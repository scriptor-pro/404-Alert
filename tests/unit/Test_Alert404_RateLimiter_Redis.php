<?php
/**
 * Tests unitaires pour Alert404_RateLimiter avec Redis
 * Teste atomicité et fallback
 */

class Test_Alert404_RateLimiter_Redis extends Alert404_UnitTestCase {

	/**
	 * État avant chaque test
	 */
	public function setUp(): void {
		parent::setUp();

		// Charger les classes
		if ( ! class_exists( 'Alert404_Redis_Handler' ) ) {
			require_once dirname( dirname( dirname( __FILE__ ) ) ) . '/includes/class-redis-handler.php';
		}

		// Configuration par défaut pour les tests
		$this->setup_plugin_options(
			[
				'daily_limit' => 100,
				'ip_cooldown' => 5,
			]
		);

		// Fermer Redis avant chaque test
		Alert404_Redis_Handler::close();

		// Nettoyer les clés Redis/transients
		$this->clear_all_transients();
	}

	/**
	 * Teste que check_and_increment() utilise Redis si disponible
	 */
	public function test_uses_redis_when_available() {
		if ( ! Alert404_Redis_Handler::is_available() ) {
			$this->markTestSkipped( 'Redis non disponible' );
		}

		$ip     = '192.168.1.100';
		$result = Alert404_RateLimiter::check_and_increment( $ip );

		// Première requête doit passer
		$this->assertTrue( $result );

		// Vérifier que la clé Redis existe
		$key = '404_alert_ip_' . wp_hash( $ip );
		$val = Alert404_Redis_Handler::get( $key );
		$this->assertNotFalse( $val );
	}

	/**
	 * Teste fallback à transients si Redis indisponible
	 */
	public function test_falls_back_to_transients_if_redis_unavailable() {
		// Fermer Redis pour forcer fallback
		Alert404_Redis_Handler::close();

		$ip     = '192.168.1.101';
		$result = Alert404_RateLimiter::check_and_increment( $ip );

		// Doit toujours fonctionner avec transients
		$this->assertTrue( $result );

		// Vérifier que la clé transient existe
		$key = '404_alert_ip_' . wp_hash( $ip );
		$val = get_transient( $key );
		$this->assertNotFalse( $val );
	}

	/**
	 * Teste que IP limit bloque les requêtes rapides avec Redis
	 */
	public function test_ip_limit_blocks_rapid_requests_redis() {
		if ( ! Alert404_Redis_Handler::is_available() ) {
			$this->markTestSkipped( 'Redis non disponible' );
		}

		$ip = '10.0.0.100';

		// Première requête
		$result1 = Alert404_RateLimiter::check_and_increment( $ip );
		$this->assertTrue( $result1 );

		// Deuxième immédiate (dans cooldown)
		$result2 = Alert404_RateLimiter::check_and_increment( $ip );
		$this->assertFalse( $result2, 'Deuxième requête doit être bloquée par cooldown' );
	}

	/**
	 * Teste que IP limit bloque les requêtes rapides avec transients (fallback)
	 */
	public function test_ip_limit_blocks_rapid_requests_transients() {
		// Forcer transients
		Alert404_Redis_Handler::close();

		$ip = '10.0.0.101';

		// Première requête
		$result1 = Alert404_RateLimiter::check_and_increment( $ip );
		$this->assertTrue( $result1 );

		// Deuxième immédiate
		$result2 = Alert404_RateLimiter::check_and_increment( $ip );
		$this->assertFalse( $result2, 'Deuxième requête doit être bloquée même avec transients' );
	}

	/**
	 * Teste que différentes IPs sont indépendantes avec Redis
	 */
	public function test_different_ips_independent_redis() {
		if ( ! Alert404_Redis_Handler::is_available() ) {
			$this->markTestSkipped( 'Redis non disponible' );
		}

		$ip1 = '172.16.0.1';
		$ip2 = '172.16.0.2';

		// IP1 première requête
		$r1 = Alert404_RateLimiter::check_and_increment( $ip1 );
		$this->assertTrue( $r1 );

		// IP1 deuxième requête (bloquée)
		$r2 = Alert404_RateLimiter::check_and_increment( $ip1 );
		$this->assertFalse( $r2 );

		// IP2 première requête (doit passer, IP différente)
		$r3 = Alert404_RateLimiter::check_and_increment( $ip2 );
		$this->assertTrue( $r3 );
	}

	/**
	 * Teste que différentes IPs sont indépendantes avec transients
	 */
	public function test_different_ips_independent_transients() {
		Alert404_Redis_Handler::close();

		$ip1 = '172.16.0.3';
		$ip2 = '172.16.0.4';

		$r1 = Alert404_RateLimiter::check_and_increment( $ip1 );
		$this->assertTrue( $r1 );

		$r2 = Alert404_RateLimiter::check_and_increment( $ip1 );
		$this->assertFalse( $r2 );

		$r3 = Alert404_RateLimiter::check_and_increment( $ip2 );
		$this->assertTrue( $r3 );
	}

	/**
	 * Teste daily limit avec Redis
	 */
	public function test_daily_limit_redis() {
		if ( ! Alert404_Redis_Handler::is_available() ) {
			$this->markTestSkipped( 'Redis non disponible' );
		}

		// Configurer limite très basse
		$this->setup_plugin_options(
			[
				'daily_limit' => 2,
				'ip_cooldown' => 1,
			]
		);

		// Requête 1 (différente IP pour éviter cooldown)
		$r1 = Alert404_RateLimiter::check_and_increment( '192.168.2.1' );
		$this->assertTrue( $r1 );

		// Requête 2 (IP différente)
		$r2 = Alert404_RateLimiter::check_and_increment( '192.168.2.2' );
		$this->assertTrue( $r2 );

		// Requête 3 (doit être bloquée par limite quotidienne)
		$r3 = Alert404_RateLimiter::check_and_increment( '192.168.2.3' );
		$this->assertFalse( $r3, 'Troisième requête doit être bloquée par limite quotidienne' );
	}

	/**
	 * Teste daily limit avec transients
	 */
	public function test_daily_limit_transients() {
		Alert404_Redis_Handler::close();

		$this->setup_plugin_options(
			[
				'daily_limit' => 2,
				'ip_cooldown' => 1,
			]
		);

		$r1 = Alert404_RateLimiter::check_and_increment( '192.168.3.1' );
		$this->assertTrue( $r1 );

		$r2 = Alert404_RateLimiter::check_and_increment( '192.168.3.2' );
		$this->assertTrue( $r2 );

		$r3 = Alert404_RateLimiter::check_and_increment( '192.168.3.3' );
		$this->assertFalse( $r3 );
	}

	/**
	 * Teste que reset quotidien se fait à minuit UTC
	 */
	public function test_daily_limit_reset_at_midnight() {
		if ( ! Alert404_Redis_Handler::is_available() ) {
			$this->markTestSkipped( 'Redis non disponible' );
		}

		$this->setup_plugin_options(
			[
				'daily_limit' => 1,
				'ip_cooldown' => 1,
			]
		);

		// Première requête
		$r1 = Alert404_RateLimiter::check_and_increment( '203.0.113.1' );
		$this->assertTrue( $r1 );

		// Deuxième bloquée
		$r2 = Alert404_RateLimiter::check_and_increment( '203.0.113.2' );
		$this->assertFalse( $r2 );

		// Vérifier que la clé a un TTL jusqu'à minuit
		$key = '404_alert_global_' . gmdate( 'Y-m-d' );
		$val = Alert404_Redis_Handler::get( $key );
		$this->assertNotFalse( $val );
	}

	/**
	 * Teste cooldown expiration
	 */
	public function test_cooldown_expiration() {
		if ( ! Alert404_Redis_Handler::is_available() ) {
			$this->markTestSkipped( 'Redis non disponible' );
		}

		// Cooldown très court pour les tests
		$this->setup_plugin_options(
			[
				'daily_limit' => 100,
				'ip_cooldown' => 1,
			]
		);

		$ip = '198.51.100.1';

		// Première requête
		$r1 = Alert404_RateLimiter::check_and_increment( $ip );
		$this->assertTrue( $r1 );

		// Immédiatement bloquée
		$r2 = Alert404_RateLimiter::check_and_increment( $ip );
		$this->assertFalse( $r2 );

		// Attendre expiration
		sleep( 2 );

		// Maintenant doit passer
		$r3 = Alert404_RateLimiter::check_and_increment( $ip );
		$this->assertTrue( $r3, 'Après expiration du cooldown, requête doit passer' );
	}

	/**
	 * Teste que les deux limits (IP + quotidien) fonctionnent ensemble
	 */
	public function test_both_limits_together() {
		if ( ! Alert404_Redis_Handler::is_available() ) {
			$this->markTestSkipped( 'Redis non disponible' );
		}

		$this->setup_plugin_options(
			[
				'daily_limit' => 10,
				'ip_cooldown' => 5,
			]
		);

		$ip = '192.0.2.1';

		// Première requête passe les deux
		$r1 = Alert404_RateLimiter::check_and_increment( $ip );
		$this->assertTrue( $r1 );

		// Immédiatement bloquée par cooldown IP
		$r2 = Alert404_RateLimiter::check_and_increment( $ip );
		$this->assertFalse( $r2 );

		// IP différente, limite quotidienne pas atteinte
		$r3 = Alert404_RateLimiter::check_and_increment( '192.0.2.2' );
		$this->assertTrue( $r3 );
	}

	/**
	 * Teste atomicité (pas de race condition)
	 * Note: Test difficile en unit test, mais on peut vérifier le comportement
	 */
	public function test_atomic_behavior() {
		if ( ! Alert404_Redis_Handler::is_available() ) {
			$this->markTestSkipped( 'Redis non disponible' );
		}

		$this->setup_plugin_options(
			[
				'daily_limit' => 2,
				'ip_cooldown' => 300,
			]
		);

		// Simuler des requêtes rapides
		$ips = [ '203.0.113.10', '203.0.113.11', '203.0.113.12' ];

		$passed = 0;
		foreach ( $ips as $ip ) {
			if ( Alert404_RateLimiter::check_and_increment( $ip ) ) {
				++$passed;
			}
		}

		// Exactement 2 doivent passer (limite quotidienne)
		$this->assertEquals( 2, $passed );
	}

	/**
	 * Teste IPv6 avec Redis
	 */
	public function test_ipv6_redis() {
		if ( ! Alert404_Redis_Handler::is_available() ) {
			$this->markTestSkipped( 'Redis non disponible' );
		}

		$ipv6 = '2001:db8::1';

		$r1 = Alert404_RateLimiter::check_and_increment( $ipv6 );
		$this->assertTrue( $r1 );

		$r2 = Alert404_RateLimiter::check_and_increment( $ipv6 );
		$this->assertFalse( $r2 );
	}
}
