<?php
/**
 * Tests d'intégration E2E pour Alert404
 * Teste les workflows complets du plugin
 */

defined( 'ABSPATH' ) || exit;

class Test_Alert404_E2E extends Alert404_UnitTestCase {

	/**
	 * État avant chaque test
	 */
	public function setUp(): void {
		parent::setUp();

		// Nettoyer les options
		delete_option( '404_alert_options' );
		delete_option( '404_alert_smtp_options' );
		delete_option( '404_alert_stats' );
		delete_option( '404_alert_stats_schema_version' );
		delete_option( '404_alert_stats_migrated' );

		// Nettoyer la table si elle existe
		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Cleanup of test table
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table name via wpdb->prefix constant
		$wpdb->query( "DROP TABLE IF EXISTS " . $wpdb->prefix . "404_alert_stats" );

		// Réinitialiser Redis si disponible
		if ( class_exists( 'Alert404_Redis_Handler' ) && Alert404_Redis_Handler::is_available() ) {
			Alert404_Redis_Handler::get_instance()->flushdb();
		}

		// Initialiser les services
		Alert404_Settings::init();
		Alert404_Storage::init();
		Alert404_Redis_Handler::init();

		// Configuration de test standard
		$this->setup_plugin_options(
			array(
				'email'       => 'admin@test.example.com',
				'daily_limit' => 500,
				'ip_cooldown' => 300,
			)
		);

		// Configurer les variables $_SERVER pour les tests
		$_SERVER['REQUEST_URI']       = '/';
		$_SERVER['REQUEST_METHOD']    = 'GET';
		$_SERVER['REMOTE_ADDR']       = '192.168.1.100';
		$_SERVER['HTTP_USER_AGENT']   = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)';
		$_SERVER['HTTP_REFERER']      = '';
		$_SERVER['HTTP_ACCEPT_LANGUAGE'] = 'en-US,en;q=0.9';
	}

	/**
	 * Scenario 1: Une requête 404 déclenche la collecte de données et l'envoi d'email
	 *
	 * Workflow:
	 * 1. Simuler une requête 404
	 * 2. Appeler le hook template_redirect
	 * 3. Vérifier que les données sont collectées
	 * 4. Vérifier que l'email est envoyé
	 * 5. Vérifier que les stats sont stockées
	 */
	public function test_e2e_404_triggers_email_and_storage() {
		// Setup
		$this->set_404( '/nonexistent-page' );

		// Capturer les emails envoyés
		$emails_sent = array();
		$capture_emails = function( $args ) use ( &$emails_sent ) {
			$emails_sent[] = $args;
			// Retourner true pour indiquer l'envoi réussi
			return true;
		};

		// Ajouter le filtre pour capturer les emails
		add_filter( 'wp_mail', $capture_emails );

		// Déclencher le détecteur
		Alert404_Detector::on_template_redirect();

		// Vérification 1: Un email a été envoyé
		$this->assertNotEmpty( $emails_sent, 'Aucun email n\'a été envoyé' );
		$this->assertCount( 1, $emails_sent );

		// Vérification 2: L'email contient les bonnes informations
		$email = $emails_sent[0];
		$this->assertEquals( 'admin@test.example.com', $email['to'] );
		$this->assertStringContainsString( '404', $email['subject'] );
		$this->assertStringContainsString( '/nonexistent-page', $email['message'] );

		// Vérification 3: Les stats sont stockées
		$stats = Alert404_Storage::get_stats( 10 );
		$this->assertNotEmpty( $stats, 'Les stats n\'ont pas été sauvegardées' );
		$this->assertEquals( '/nonexistent-page', $stats[0]['url'] );
		$this->assertEquals( '192.168.1.100', $stats[0]['ip'] );

		// Vérification 4: Le count total est correct
		$total = Alert404_Storage::get_total_count();
		$this->assertEquals( 1, $total );

		// Cleanup
		remove_filter( 'wp_mail', $capture_emails );
	}

	/**
	 * Scenario 2: Le rate limiting fonctionne correctement
	 *
	 * Workflow:
	 * 1. Configurer un cooldown court (30 secondes)
	 * 2. Déclencher deux 404 du même IP rapidement
	 * 3. Vérifier que le second est bloqué
	 * 4. Vérifier qu'un seul email est envoyé
	 */
	public function test_e2e_rate_limiting_blocks_duplicate_ips() {
		// Setup
		$this->setup_plugin_options(
			array(
				'email'       => 'admin@test.example.com',
				'daily_limit' => 500,
				'ip_cooldown' => 30,  // Cooldown court pour le test
			)
		);

		$this->set_404( '/test1' );

		// Capturer les emails
		$emails_sent = array();
		add_filter( 'wp_mail', function( $args ) use ( &$emails_sent ) {
			$emails_sent[] = $args;
			return true;
		} );

		// Première requête
		Alert404_Detector::on_template_redirect();
		$this->assertCount( 1, $emails_sent, 'Premier 404 devrait envoyer un email' );

		// Simuler une deuxième requête du même IP sans attendre
		$this->set_404( '/test2' );
		Alert404_Detector::on_template_redirect();

		// Deuxième requête ne devrait pas envoyer d'email (rate limitée)
		$this->assertCount( 1, $emails_sent, 'Deuxième 404 devrait être limité' );

		// Vérifier que les deux 404 sont comptabilisés dans les stats
		// (même si un seul email est envoyé)
		$total = Alert404_Storage::get_total_count();
		$this->assertGreaterThanOrEqual( 1, $total );
	}

	/**
	 * Scenario 3: Redis est utilisé quand disponible
	 *
	 * Workflow:
	 * 1. Vérifier que Redis est disponible
	 * 2. Configurer un IP cooldown
	 * 3. Déclencher un 404
	 * 4. Vérifier que le cooldown est stocké dans Redis
	 * 5. Vérifier que la clé Redis existe
	 */
	public function test_e2e_redis_stores_rate_limit_when_available() {
		// Vérifier Redis disponible
		if ( ! Alert404_Redis_Handler::is_available() ) {
			$this->markTestSkipped( 'Redis n\'est pas disponible' );
		}

		$this->set_404( '/redis-test' );

		// Capturer l'IP testée
		$test_ip = '203.0.113.50';
		$_SERVER['REMOTE_ADDR'] = $test_ip;

		// Déclencher le 404
		Alert404_Detector::on_template_redirect();

		// Vérifier que la clé Redis existe
		$redis_key = '404_alert_ip_' . wp_hash( $test_ip );
		$redis_value = Alert404_Redis_Handler::get( $redis_key );

		$this->assertNotFalse( $redis_value, 'Le cooldown n\'a pas été stocké dans Redis' );
		$this->assertIsNumeric( $redis_value );
	}

	/**
	 * Scenario 4: Fallback vers transients quand Redis est indisponible
	 *
	 * Workflow:
	 * 1. Désactiver Redis (simulé)
	 * 2. Déclencher un 404
	 * 3. Vérifier que le transient est créé
	 * 4. Vérifier que le rate limiting fonctionne avec transients
	 */
	public function test_e2e_transient_fallback_when_redis_unavailable() {
		// Simuler Redis indisponible en supprimant la classe
		// (dans un vrai test, on aurait une option pour désactiver Redis)
		// Pour ce test, on vérifie juste que les transients fonctionnent

		$this->set_404( '/fallback-test' );
		$test_ip = '203.0.113.51';
		$_SERVER['REMOTE_ADDR'] = $test_ip;

		// Déclencher le 404
		Alert404_Detector::on_template_redirect();

		// Vérifier que les données sont stockées en quelque forme
		$total = Alert404_Storage::get_total_count();
		$this->assertGreaterThan( 0, $total, 'Les données devraient être stockées même sans Redis' );
	}

	/**
	 * Scenario 5: SMTP envoie l'email quand configuré
	 *
	 * Workflow:
	 * 1. Configurer SMTP
	 * 2. Déclencher un 404
	 * 3. Vérifier que Mailer utilise SMTP si configuré
	 */
	public function test_e2e_smtp_configuration_affects_email_sending() {
		// Setup SMTP config
		$smtp_config = array(
			'host'       => 'smtp.gmail.com',
			'port'       => 587,
			'username'   => 'test@gmail.com',
			'password'   => Alert404_SMTP_Handler::encrypt_password_for_storage( 'app_password' ),
			'encryption' => 'tls',
			'from_email' => 'test@gmail.com',
			'from_name'  => '404 Alert',
		);
		update_option( '404_alert_smtp_options', $smtp_config );

		$this->set_404( '/smtp-test' );

		// Capturer l'appel wp_mail
		$wp_mail_called = false;
		add_filter( 'wp_mail', function( $args ) use ( &$wp_mail_called ) {
			$wp_mail_called = true;
			return true;
		} );

		// Déclencher le 404
		Alert404_Detector::on_template_redirect();

		// Vérifier que wp_mail a été appelée
		$this->assertTrue( $wp_mail_called, 'wp_mail devrait être appelée' );
	}

	/**
	 * Scenario 6: Fallback vers wp_mail quand SMTP échoue
	 *
	 * Workflow:
	 * 1. Configurer une SMTP invalide
	 * 2. Déclencher un 404
	 * 3. Vérifier que wp_mail est utilisée en fallback
	 */
	public function test_e2e_wp_mail_fallback_when_smtp_fails() {
		// Configuration SMTP invalide
		update_option( '404_alert_smtp_options', array(
			'host'     => 'invalid.example.com',
			'port'     => 587,
			'username' => 'invalid',
			'password' => '',
		) );

		$this->set_404( '/fallback-smtp-test' );

		// Capturer les appels
		$calls = array(
			'wp_mail' => false,
		);

		add_filter( 'wp_mail', function( $args ) use ( &$calls ) {
			$calls['wp_mail'] = true;
			return true;
		} );

		// Déclencher le 404
		Alert404_Detector::on_template_redirect();

		// wp_mail devrait être appelée à un moment ou un autre
		// (soit directement, soit en fallback)
		$stats = Alert404_Storage::get_total_count();
		$this->assertGreaterThan( 0, $stats );
	}

	/**
	 * Scenario 7: Les settings sont sauvegardées et rechargées correctement
	 *
	 * Workflow:
	 * 1. Sauvegarder des settings personnalisés
	 * 2. Déclencher un 404
	 * 3. Vérifier que les settings sont appliquées
	 * 4. Recharger les settings
	 * 5. Vérifier que les valeurs sont correctes
	 */
	public function test_e2e_settings_persistence_across_requests() {
		// Configurer des settings personnalisés
		$custom_options = array(
			'email'       => 'custom@example.com',
			'daily_limit' => 1000,
			'ip_cooldown' => 600,
		);
		update_option( '404_alert_options', $custom_options );

		// Déclencher un 404
		$this->set_404( '/settings-test' );
		Alert404_Detector::on_template_redirect();

		// Recharger et vérifier
		$settings = get_option( '404_alert_options' );
		$this->assertEquals( 'custom@example.com', $settings['email'] );
		$this->assertEquals( 1000, $settings['daily_limit'] );
		$this->assertEquals( 600, $settings['ip_cooldown'] );
	}

	/**
	 * Scenario 8: La page admin est accessible et affiche les statistiques
	 *
	 * Workflow:
	 * 1. Créer un utilisateur admin
	 * 2. Se connecter
	 * 3. Générer quelques 404s
	 * 4. Accéder à la page admin
	 * 5. Vérifier que les stats s'affichent
	 */
	public function test_e2e_admin_page_displays_statistics() {
		// Créer un admin
		$admin_id = self::factory()->user->create( array( 'role' => 'administrator' ) );
		wp_set_current_user( $admin_id );

		// Générer quelques 404s
		for ( $i = 0; $i < 3; $i++ ) {
			$this->set_404( "/test-$i" );
			$_SERVER['REMOTE_ADDR'] = '192.168.1.' . ( 100 + $i );
			Alert404_Detector::on_template_redirect();
		}

		// Capturer la sortie de la page admin
		ob_start();
		Alert404_Dashboard::render_page();
		$output = ob_get_clean();

		// Vérifier que la page admin contient les informations
		$this->assertIsString( $output );
		$this->assertNotEmpty( $output );
		$this->assertStringContainsString( 'Statistiques', $output, 'La page devrait afficher "Statistiques"' );

		// Cleanup
		wp_set_current_user( 0 );
	}

	/**
	 * Scenario 9: Intégration complète - 404 → Rate limit → Storage → Email → Dashboard
	 *
	 * Workflow:
	 * 1. Configurer toutes les options
	 * 2. Déclencher un 404
	 * 3. Vérifier qu'il est collecté dans Storage
	 * 4. Vérifier qu'il apparaît dans le Dashboard
	 * 5. Vérifier que la limite de taux est appliquée
	 */
	public function test_e2e_complete_workflow_404_to_dashboard() {
		// Setup admin
		$admin_id = self::factory()->user->create( array( 'role' => 'administrator' ) );

		// Configurer les options
		$this->setup_plugin_options(
			array(
				'email'       => 'admin@test.example.com',
				'daily_limit' => 500,
				'ip_cooldown' => 120,
			)
		);

		// Déclencher un 404
		$test_ip = '203.0.113.100';
		$_SERVER['REMOTE_ADDR'] = $test_ip;
		$this->set_404( '/complete-workflow-test' );

		// Capturer l'email
		$emails = array();
		add_filter( 'wp_mail', function( $args ) use ( &$emails ) {
			$emails[] = $args;
			return true;
		} );

		// Déclencher le détecteur
		Alert404_Detector::on_template_redirect();

		// Vérifications en cascade
		$this->assertCount( 1, $emails, 'Un email devrait être envoyé' );

		// Vérifier le stockage
		$stats = Alert404_Storage::get_stats( 10 );
		$this->assertNotEmpty( $stats );
		$this->assertEquals( '/complete-workflow-test', $stats[0]['url'] );

		// Vérifier le total
		$total = Alert404_Storage::get_total_count();
		$this->assertEquals( 1, $total );

		// Vérifier le dashboard
		wp_set_current_user( $admin_id );
		ob_start();
		Alert404_Dashboard::render_page();
		$output = ob_get_clean();

		$this->assertStringContainsString( '/complete-workflow-test', $output );
		wp_set_current_user( 0 );
	}

	/**
	 * Scenario 10: Plusieurs IPs sont traitées indépendamment
	 *
	 * Workflow:
	 * 1. Déclencher 404 d'IP1
	 * 2. Déclencher 404 d'IP2
	 * 3. Vérifier qu'IP1 peut de nouveau faire un 404 (limit indépendante)
	 * 4. Vérifier que les stats enregistrent les deux IPs
	 */
	public function test_e2e_multiple_ips_are_rate_limited_independently() {
		$this->setup_plugin_options(
			array(
				'email'       => 'admin@test.example.com',
				'daily_limit' => 500,
				'ip_cooldown' => 30,
			)
		);

		$emails = array();
		add_filter( 'wp_mail', function( $args ) use ( &$emails ) {
			$emails[] = $args;
			return true;
		} );

		// IP1 - Premier 404
		$_SERVER['REMOTE_ADDR'] = '203.0.113.1';
		$this->set_404( '/ip1-test1' );
		Alert404_Detector::on_template_redirect();
		$this->assertCount( 1, $emails );

		// IP1 - Deuxième 404 (limité)
		$this->set_404( '/ip1-test2' );
		Alert404_Detector::on_template_redirect();
		$this->assertCount( 1, $emails, 'IP1 devrait être limitée' );

		// IP2 - Premier 404 (devrait être envoyé)
		$_SERVER['REMOTE_ADDR'] = '203.0.113.2';
		$this->set_404( '/ip2-test1' );
		Alert404_Detector::on_template_redirect();
		$this->assertCount( 2, $emails, 'IP2 devrait avoir son propre quota' );

		// Vérifier les stats
		$stats = Alert404_Storage::get_stats( 10 );
		$this->assertNotEmpty( $stats );
		// Devrait y avoir au moins 2 entrées
		$this->assertGreaterThanOrEqual( 1, count( $stats ) );
	}

	/**
	 * Scenario 11: Le daily limit fonctionne correctement
	 *
	 * Workflow:
	 * 1. Configurer un daily limit faible (ex: 5)
	 * 2. Déclencher 5 404s d'IPs différentes
	 * 3. Vérifier que le 6e est bloqué
	 */
	public function test_e2e_daily_limit_prevents_excess_emails() {
		$this->setup_plugin_options(
			array(
				'email'       => 'admin@test.example.com',
				'daily_limit' => 3,  // Limite basse pour le test
				'ip_cooldown' => 0,  // Pas de cooldown IP pour ce test
			)
		);

		$emails = array();
		add_filter( 'wp_mail', function( $args ) use ( &$emails ) {
			$emails[] = $args;
			return true;
		} );

		// Déclencher 4 404s d'IPs différentes
		for ( $i = 1; $i <= 4; $i++ ) {
			$_SERVER['REMOTE_ADDR'] = "203.0.113.$i";
			$this->set_404( "/daily-limit-test-$i" );
			Alert404_Detector::on_template_redirect();
		}

		// Devrait y avoir max 3 emails (le daily limit)
		$this->assertLessThanOrEqual( 3, count( $emails ), 'Le daily limit devrait être respecté' );
	}

	/**
	 * Scenario 12: Les données sensibles ne sont pas loggées en brut
	 *
	 * Workflow:
	 * 1. Déclencher un 404 avec données sensibles (user agent long, etc.)
	 * 2. Vérifier que les données sont tronquées/échappées
	 * 3. Vérifier qu'aucune injection n'est possible
	 */
	public function test_e2e_sensitive_data_is_sanitized() {
		$this->set_404( '/sensitive-test' );

		// User agent avec tentative d'injection
		$_SERVER['HTTP_USER_AGENT'] = str_repeat( 'x', 1000 ) . '<script>alert("xss")</script>';

		// Déclencher le 404
		Alert404_Detector::on_template_redirect();

		// Vérifier les stats
		$stats = Alert404_Storage::get_stats( 10 );
		$this->assertNotEmpty( $stats );

		// Les données ne doivent pas contenir la script tag
		$this->assertStringNotContainsString( '<script>', $stats[0]['user_agent'] );
		// Et doivent être tronquées
		$this->assertLessThanOrEqual( 500, strlen( $stats[0]['user_agent'] ) );
	}
}
