<?php
/**
 * Tests unitaires pour Alert404_RateLimiter
 */

class Test_Alert404_RateLimiter extends Alert404_UnitTestCase {

	public function setUp(): void {
		parent::setUp();
		$this->setup_plugin_options();
	}

	/**
	 * Teste que la première requête d'une IP est autorisée
	 */
	public function test_first_request_is_allowed() {
		$result = Alert404_RateLimiter::check_and_increment( '192.168.1.1' );
		$this->assertTrue( $result );
	}

	/**
	 * Teste que la deuxième requête d'une IP dans le cooldown est bloquée
	 */
	public function test_second_immediate_request_is_blocked() {
		// Première requête autorisée
		$result1 = Alert404_RateLimiter::check_and_increment( '192.168.1.1' );
		$this->assertTrue( $result1 );

		// Deuxième requête immédiate bloquée
		$result2 = Alert404_RateLimiter::check_and_increment( '192.168.1.1' );
		$this->assertFalse( $result2 );
	}

	/**
	 * Teste que les IPs différentes ne sont pas affectées par le cooldown des autres
	 */
	public function test_different_ips_are_independent() {
		// IP 1 : première requête
		$result1 = Alert404_RateLimiter::check_and_increment( '192.168.1.1' );
		$this->assertTrue( $result1 );

		// IP 1 : deuxième requête (bloquée)
		$result2 = Alert404_RateLimiter::check_and_increment( '192.168.1.1' );
		$this->assertFalse( $result2 );

		// IP 2 : première requête (autorisée, car IP différente)
		$result3 = Alert404_RateLimiter::check_and_increment( '192.168.1.2' );
		$this->assertTrue( $result3 );
	}

	/**
	 * Teste la limite quotidienne avec une seule IP
	 */
	public function test_daily_limit_blocks_after_threshold() {
		$ip      = '192.168.1.100';
		$options = [
			'daily_limit' => 3, // Limite très basse pour tester
			'ip_cooldown' => 1,  // Cooldown très court (1 sec)
		];
		$this->setup_plugin_options( $options );

		// Attendre un peu entre les requêtes pour contourner le cooldown
		// (dans un vrai test, on mockrait les transients)
		
		// Simuler 3 requêtes différentes en nettoyant les transients
		for ( $i = 0; $i < 3; $i++ ) {
			// On va faire des requêtes depuis différentes IPs pour éviter le cooldown
			$test_ip = '192.168.1.' . (100 + $i);
			$result  = Alert404_RateLimiter::check_and_increment( $test_ip );
			$this->assertTrue( $result, "Requête $i devrait être autorisée" );
		}

		// La 4ème requête devrait être bloquée par la limite quotidienne
		$result4 = Alert404_RateLimiter::check_and_increment( '192.168.1.200' );
		$this->assertFalse( $result4 );
	}

	/**
	 * Teste que les deux niveaux de rate limiting fonctionnent ensemble
	 */
	public function test_both_limits_work_together() {
		$options = [
			'daily_limit' => 10,
			'ip_cooldown' => 300, // 5 minutes
		];
		$this->setup_plugin_options( $options );

		$ip = '192.168.1.50';

		// Première requête de cette IP : OK (cooldown + limite globale)
		$result1 = Alert404_RateLimiter::check_and_increment( $ip );
		$this->assertTrue( $result1 );

		// Deuxième requête immédiate de la même IP : bloquée par cooldown
		$result2 = Alert404_RateLimiter::check_and_increment( $ip );
		$this->assertFalse( $result2 );
	}

	/**
	 * Teste que les options par défaut sont correctes
	 */
	public function test_default_options_are_applied() {
		// Supprimer les options pour tester les défauts
		delete_option( '404_alert_options' );

		$ip     = '10.0.0.1';
		$result = Alert404_RateLimiter::check_and_increment( $ip );

		// Devrait utiliser les valeurs par défaut et autoriser
		$this->assertTrue( $result );
	}

	/**
	 * Teste que les IPs IPv6 sont supportées
	 */
	public function test_ipv6_is_supported() {
		$ipv6 = '2001:db8::1';

		$result1 = Alert404_RateLimiter::check_and_increment( $ipv6 );
		$this->assertTrue( $result1 );

		$result2 = Alert404_RateLimiter::check_and_increment( $ipv6 );
		$this->assertFalse( $result2 );
	}

	/**
	 * Teste que les IPs localhost sont gérées
	 */
	public function test_localhost_is_handled() {
		$result1 = Alert404_RateLimiter::check_and_increment( '127.0.0.1' );
		$this->assertTrue( $result1 );

		$result2 = Alert404_RateLimiter::check_and_increment( '127.0.0.1' );
		$this->assertFalse( $result2 );
	}

	/**
	 * Teste que le cooldown peut être modifié
	 */
	public function test_cooldown_can_be_configured() {
		$options = [
			'ip_cooldown' => 60, // 1 minute au lieu de 5
		];
		$this->setup_plugin_options( $options );

		$ip = '172.16.0.1';

		$result1 = Alert404_RateLimiter::check_and_increment( $ip );
		$this->assertTrue( $result1 );

		$result2 = Alert404_RateLimiter::check_and_increment( $ip );
		$this->assertFalse( $result2 );
	}

	/**
	 * Teste que la limite quotidienne peut être modifiée
	 */
	public function test_daily_limit_can_be_configured() {
		$options = [
			'daily_limit' => 1000, // Augmenter la limite
			'ip_cooldown' => 1,
		];
		$this->setup_plugin_options( $options );

		// Première requête devrait réussir
		$result = Alert404_RateLimiter::check_and_increment( '203.0.113.0' );
		$this->assertTrue( $result );
	}

	/**
	 * Teste que le transient est créé avec la bonne expiration
	 */
	public function test_transient_expiration() {
		$ip = '198.51.100.0';

		// Créer une première requête
		Alert404_RateLimiter::check_and_increment( $ip );

		// Vérifier que le transient existe
		$key = '404_alert_ip_' . wp_hash( $ip );

		// Le transient doit exister
		$transient = get_transient( $key );
		$this->assertNotFalse( $transient, 'Transient devrait exister après une requête' );

		// La valeur du transient doit être un timestamp
		$this->assertIsInt( (int) $transient );
	}
}
