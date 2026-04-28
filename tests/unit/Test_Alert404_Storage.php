<?php
/**
 * Tests unitaires pour Alert404_Storage
 */

defined( 'ABSPATH' ) || exit;

class Test_Alert404_Storage extends Alert404_UnitTestCase {

	/**
	 * État avant chaque test
	 */
	public function setUp(): void {
		parent::setUp();

		// Nettoyer les options
		delete_option( '404_alert_stats' );
		delete_option( '404_alert_stats_schema_version' );
		delete_option( '404_alert_stats_migrated' );

		// Nettoyer la table si elle existe
		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Cleanup of test table
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table name via wpdb->prefix constant
		$wpdb->query( "DROP TABLE IF EXISTS " . $wpdb->prefix . "404_alert_stats" );

		// Initialiser le stockage
		Alert404_Storage::init();
	}

	/**
	 * Teste que get_stats retourne un array
	 */
	public function test_get_stats_returns_array() {
		$stats = Alert404_Storage::get_stats();

		$this->assertIsArray( $stats );
	}

	/**
	 * Teste que get_stats retourne un array vide initialement
	 */
	public function test_get_stats_empty_initially() {
		$stats = Alert404_Storage::get_stats();

		$this->assertEmpty( $stats );
	}

	/**
	 * Teste que get_stats respecte la limite
	 */
	public function test_get_stats_respects_limit() {
		global $wpdb;
		$table_name = $wpdb->prefix . '404_alert_stats';

		// Insérer 10 enregistrements
		for ( $i = 0; $i < 10; ++$i ) {
			$wpdb->insert(
				$table_name,
				array(
					'url'                 => '/test-' . $i,
					'ip'                  => '192.168.1.' . $i,
					'referrer'            => 'https://google.com',
					'user_agent'          => 'Mozilla/5.0',
					'user_agent_readable' => 'Mozilla 5.0',
					'created_at'          => current_time( 'mysql' ),
				),
				array( '%s', '%s', '%s', '%s', '%s', '%s' )
			);
		}

		// Demander limit 5
		$stats = Alert404_Storage::get_stats( 5 );

		// Devrait retourner max 5
		$this->assertLessThanOrEqual( 5, count( $stats ) );
	}

	/**
	 * Teste que get_recent_ips retourne les IPs récentes
	 */
	public function test_get_recent_ips_returns_array() {
		$ips = Alert404_Storage::get_recent_ips( 10 );

		$this->assertIsArray( $ips );
	}

	/**
	 * Teste que get_recent_ips retourne un array vide initialement
	 */
	public function test_get_recent_ips_empty_initially() {
		$ips = Alert404_Storage::get_recent_ips( 10 );

		$this->assertEmpty( $ips );
	}

	/**
	 * Teste que get_count_for_date retourne un nombre
	 */
	public function test_get_count_for_date_returns_number() {
		$count = Alert404_Storage::get_count_for_date( gmdate( 'Y-m-d' ) );

		$this->assertIsInt( $count );
	}

	/**
	 * Teste que get_count_for_date retourne 0 pour une date vide
	 */
	public function test_get_count_for_date_zero_initially() {
		$count = Alert404_Storage::get_count_for_date( gmdate( 'Y-m-d' ) );

		$this->assertEquals( 0, $count );
	}

	/**
	 * Teste que get_count_by_referrer retourne un array
	 */
	public function test_get_count_by_referrer_returns_array() {
		$referrers = Alert404_Storage::get_count_by_referrer( 10 );

		$this->assertIsArray( $referrers );
	}

	/**
	 * Teste que get_count_by_referrer retourne un array vide initialement
	 */
	public function test_get_count_by_referrer_empty_initially() {
		$referrers = Alert404_Storage::get_count_by_referrer( 10 );

		$this->assertEmpty( $referrers );
	}

	/**
	 * Teste que get_total_count retourne un nombre
	 */
	public function test_get_total_count_returns_number() {
		$count = Alert404_Storage::get_total_count();

		$this->assertIsInt( $count );
	}

	/**
	 * Teste que get_total_count retourne 0 initialement
	 */
	public function test_get_total_count_zero_initially() {
		$count = Alert404_Storage::get_total_count();

		$this->assertEquals( 0, $count );
	}

	/**
	 * Teste que clear_all_stats fonctionne
	 */
	public function test_clear_all_stats() {
		global $wpdb;
		$table_name = $wpdb->prefix . '404_alert_stats';

		// Insérer un enregistrement
		$wpdb->insert(
			$table_name,
			array(
				'url'                 => '/test',
				'ip'                  => '192.168.1.1',
				'referrer'            => '',
				'user_agent'          => '',
				'user_agent_readable' => '',
				'created_at'          => current_time( 'mysql' ),
			),
			array( '%s', '%s', '%s', '%s', '%s', '%s' )
		);

		// Vérifier qu'il y a un enregistrement
		$count_before = Alert404_Storage::get_total_count();
		$this->assertGreater( 0, $count_before );

		// Vider
		Alert404_Storage::clear_all_stats();

		// Vérifier qu'il n'y a plus d'enregistrements
		$count_after = Alert404_Storage::get_total_count();
		$this->assertEquals( 0, $count_after );
	}

	/**
	 * Teste que get_stats retourne les champs corrects
	 */
	public function test_get_stats_returns_correct_fields() {
		global $wpdb;
		$table_name = $wpdb->prefix . '404_alert_stats';

		// Insérer un enregistrement avec tous les champs
		$wpdb->insert(
			$table_name,
			array(
				'url'                 => '/test-page',
				'ip'                  => '203.0.113.1',
				'referrer'            => 'https://example.com',
				'user_agent'          => 'Mozilla/5.0',
				'user_agent_readable' => 'Chrome on Windows',
				'created_at'          => current_time( 'mysql' ),
			),
			array( '%s', '%s', '%s', '%s', '%s', '%s' )
		);

		$stats = Alert404_Storage::get_stats( 10 );

		$this->assertNotEmpty( $stats );
		$this->assertArrayHasKey( 'url', $stats[0] );
		$this->assertArrayHasKey( 'ip', $stats[0] );
		$this->assertArrayHasKey( 'referrer', $stats[0] );
		$this->assertArrayHasKey( 'user_agent', $stats[0] );
		$this->assertArrayHasKey( 'user_agent_readable', $stats[0] );
		$this->assertArrayHasKey( 'created_at', $stats[0] );
	}

	/**
	 * Teste que get_top_urls retourne un array
	 */
	public function test_get_top_urls_returns_array() {
		$urls = Alert404_Storage::get_top_urls( 10 );

		$this->assertIsArray( $urls );
	}

	/**
	 * Teste que get_top_urls retourne un array vide initialement
	 */
	public function test_get_top_urls_empty_initially() {
		$urls = Alert404_Storage::get_top_urls( 10 );

		$this->assertEmpty( $urls );
	}

	/**
	 * Teste que get_recent_ips respecte la limite
	 */
	public function test_get_recent_ips_respects_limit() {
		global $wpdb;
		$table_name = $wpdb->prefix . '404_alert_stats';

		// Insérer 10 enregistrements
		for ( $i = 0; $i < 10; ++$i ) {
			$wpdb->insert(
				$table_name,
				array(
					'url'                 => '/test',
					'ip'                  => '192.168.' . floor( $i / 10 ) . '.' . ( $i % 256 ),
					'referrer'            => '',
					'user_agent'          => '',
					'user_agent_readable' => '',
					'created_at'          => current_time( 'mysql' ),
				),
				array( '%s', '%s', '%s', '%s', '%s', '%s' )
			);
		}

		// Demander limit 5
		$ips = Alert404_Storage::get_recent_ips( 5 );

		// Devrait retourner max 5
		$this->assertLessThanOrEqual( 5, count( $ips ) );
	}

	/**
	 * Teste que le stockage fonctionne avec différentes dates
	 */
	public function test_storage_works_with_different_dates() {
		global $wpdb;
		$table_name = $wpdb->prefix . '404_alert_stats';

		// Insérer pour la date d'aujourd'hui
		$wpdb->insert(
			$table_name,
			array(
				'url'                 => '/today',
				'ip'                  => '192.168.1.1',
				'referrer'            => '',
				'user_agent'          => '',
				'user_agent_readable' => '',
				'created_at'          => current_time( 'mysql' ),
			),
			array( '%s', '%s', '%s', '%s', '%s', '%s' )
		);

		// Insérer pour hier
		$yesterday = gmdate( 'Y-m-d H:i:s', time() - DAY_IN_SECONDS );
		$wpdb->insert(
			$table_name,
			array(
				'url'                 => '/yesterday',
				'ip'                  => '192.168.1.2',
				'referrer'            => '',
				'user_agent'          => '',
				'user_agent_readable' => '',
				'created_at'          => $yesterday,
			),
			array( '%s', '%s', '%s', '%s', '%s', '%s' )
		);

		// Total devrait être 2
		$count = Alert404_Storage::get_total_count();
		$this->assertEquals( 2, $count );
	}

	/**
	 * Teste que les champs sont bien nettoyés (sanitization)
	 */
	public function test_storage_sanitizes_input() {
		global $wpdb;
		$table_name = $wpdb->prefix . '404_alert_stats';

		// Insérer avec du contenu dangereux
		$wpdb->insert(
			$table_name,
			array(
				'url'                 => '/test<script>alert(1)</script>',
				'ip'                  => '192.168.1.1',
				'referrer'            => 'https://example.com?param=<img src=x>',
				'user_agent'          => 'Mozilla/5.0',
				'user_agent_readable' => 'Chrome',
				'created_at'          => current_time( 'mysql' ),
			),
			array( '%s', '%s', '%s', '%s', '%s', '%s' )
		);

		$stats = Alert404_Storage::get_stats( 10 );

		// Le contenu ne doit pas contenir de balises HTML
		$this->assertStringNotContainsString( '<script>', $stats[0]['url'] );
	}
}
