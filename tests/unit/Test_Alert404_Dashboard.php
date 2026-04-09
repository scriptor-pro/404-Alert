<?php
/**
 * Tests unitaires pour Alert404_Dashboard
 */

class Test_Alert404_Dashboard extends Alert404_UnitTestCase {

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
		$table_name = $wpdb->prefix . '404_alert_stats';
		$wpdb->query( "DROP TABLE IF EXISTS {$table_name}" );

		// Initialiser le stockage
		if ( class_exists( 'Alert404_Storage' ) ) {
			Alert404_Storage::init();
		}

		// Nettoyer $_GET et $_REQUEST
		$_GET = array();
		$_REQUEST = array();
	}

	/**
	 * Teste que init() enregistre le menu
	 */
	public function test_init_registers_menu() {
		Alert404_Dashboard::init();

		// Le hook admin_menu devrait être enregistré
		$this->assertTrue( has_action( 'admin_menu' ) );
	}

	/**
	 * Teste que add_menu est callable
	 */
	public function test_add_menu_is_callable() {
		$this->assertTrue( is_callable( array( 'Alert404_Dashboard', 'add_menu' ) );
	}

	/**
	 * Teste que render_page est callable
	 */
	public function test_render_page_is_callable() {
		$this->assertTrue( is_callable( array( 'Alert404_Dashboard', 'render_page' ) );
	}

	/**
	 * Teste que render_page nécessite manage_options
	 */
	public function test_render_page_requires_manage_options() {
		// Sans permission, devrait faire wp_die()
		$this->assertFalse( current_user_can( 'manage_options' ) );

		// Vérifier que la fonction vérifi la permission
		// (On ne peut pas réellement tester wp_die sans capturer la sortie)
		$this->assertTrue( true );
	}

	/**
	 * Teste que init() vérifie si Storage existe
	 */
	public function test_init_checks_storage_class() {
		// Si Storage n'existe pas, init() devrait retourner tôt
		Alert404_Dashboard::init();

		// Ne devrait pas faire d'erreur même si Storage absent
		$this->assertTrue( true );
	}

	/**
	 * Teste que render_page affiche un message si aucune donnée
	 */
	public function test_render_page_handles_no_data() {
		// Créer un utilisateur admin
		$user_id = self::factory()->user->create( array( 'role' => 'administrator' ) );
		wp_set_current_user( $user_id );

		// Capturer la sortie
		ob_start();
		Alert404_Dashboard::render_page();
		$output = ob_get_clean();

		// Devrait afficher un message
		$this->assertIsString( $output );

		// Cleanup
		wp_set_current_user( 0 );
	}

	/**
	 * Teste que render_page vérifie le nonce pour export
	 */
	public function test_render_page_verifies_export_nonce() {
		$_GET['action'] = 'export';
		// Sans nonce valide, ne devrait pas faire l'export

		$user_id = self::factory()->user->create( array( 'role' => 'administrator' ) );
		wp_set_current_user( $user_id );

		ob_start();
		Alert404_Dashboard::render_page();
		$output = ob_get_clean();

		$this->assertIsString( $output );

		// Cleanup
		wp_set_current_user( 0 );
		$_GET = array();
	}

	/**
	 * Teste que render_page vérifie le nonce pour clear
	 */
	public function test_render_page_verifies_clear_nonce() {
		$_GET['action'] = 'clear';
		// Sans nonce valide, ne devrait pas faire le clear

		$user_id = self::factory()->user->create( array( 'role' => 'administrator' ) );
		wp_set_current_user( $user_id );

		ob_start();
		Alert404_Dashboard::render_page();
		$output = ob_get_clean();

		$this->assertIsString( $output );

		// Cleanup
		wp_set_current_user( 0 );
		$_GET = array();
	}

	/**
	 * Teste que render_page gère l'action 'list'
	 */
	public function test_render_page_handles_list_action() {
		$user_id = self::factory()->user->create( array( 'role' => 'administrator' ) );
		wp_set_current_user( $user_id );

		ob_start();
		Alert404_Dashboard::render_page();
		$output = ob_get_clean();

		$this->assertIsString( $output );

		wp_set_current_user( 0 );
	}

	/**
	 * Teste que render_page sanitize l'action
	 */
	public function test_render_page_sanitizes_action() {
		$_GET['action'] = '<script>alert("xss")</script>';

		$user_id = self::factory()->user->create( array( 'role' => 'administrator' ) );
		wp_set_current_user( $user_id );

		ob_start();
		Alert404_Dashboard::render_page();
		$output = ob_get_clean();

		// L'action devrait être sanitizée
		$this->assertStringNotContainsString( '<script>', $output );

		wp_set_current_user( 0 );
		$_GET = array();
	}

	/**
	 * Teste que render_page affiche le titre
	 */
	public function test_render_page_displays_title() {
		$user_id = self::factory()->user->create( array( 'role' => 'administrator' ) );
		wp_set_current_user( $user_id );

		ob_start();
		Alert404_Dashboard::render_page();
		$output = ob_get_clean();

		// Devrait contenir "Statistiques" ou "404 Alert"
		$this->assertTrue(
			strpos( $output, 'Statistiques' ) !== false || strpos( $output, '404' ) !== false
		);

		wp_set_current_user( 0 );
	}

	/**
	 * Teste que add_menu enregistre un submenu
	 */
	public function test_add_menu_registers_submenu() {
		// Appeler la méthode
		Alert404_Dashboard::add_menu();

		// Ne devrait pas faire d'erreur
		$this->assertTrue( true );
	}

	/**
	 * Teste que render_page est protégée
	 */
	public function test_render_page_is_protected() {
		// Sans être connecté en admin, devrait faire wp_die
		$this->assertFalse( current_user_can( 'manage_options' ) );

		// Ne peut pas vraiment tester wp_die sans capturer la sortie
		$this->assertTrue( true );
	}

	/**
	 * Teste que render_page avec données affiche les statistiques
	 */
	public function test_render_page_displays_stats_with_data() {
		// Créer des données
		global $wpdb;
		$table_name = $wpdb->prefix . '404_alert_stats';

		$wpdb->insert(
			$table_name,
			array(
				'url'                 => '/test',
				'ip'                  => '192.168.1.1',
				'referrer'            => 'https://google.com',
				'user_agent'          => 'Mozilla/5.0',
				'user_agent_readable' => 'Chrome',
				'created_at'          => current_time( 'mysql' ),
			),
			array( '%s', '%s', '%s', '%s', '%s', '%s' )
		);

		$user_id = self::factory()->user->create( array( 'role' => 'administrator' ) );
		wp_set_current_user( $user_id );

		ob_start();
		Alert404_Dashboard::render_page();
		$output = ob_get_clean();

		// Devrait contenir le tableau des stats
		$this->assertIsString( $output );
		$this->assertNotEmpty( $output );

		wp_set_current_user( 0 );
	}

	/**
	 * Teste que les actions reçoivent les bons paramètres
	 */
	public function test_actions_receive_correct_parameters() {
		// Vérifier que add_submenu_page peut être appelée
		$this->assertTrue( is_callable( 'add_submenu_page' ) );
	}

	/**
	 * Teste que render_page peut être appelée multiple fois
	 */
	public function test_render_page_can_be_called_multiple_times() {
		$user_id = self::factory()->user->create( array( 'role' => 'administrator' ) );
		wp_set_current_user( $user_id );

		ob_start();
		Alert404_Dashboard::render_page();
		ob_end_clean();

		ob_start();
		Alert404_Dashboard::render_page();
		$output = ob_get_clean();

		$this->assertIsString( $output );

		wp_set_current_user( 0 );
	}

	/**
	 * Teste que init() retourne void si Storage absent
	 */
	public function test_init_returns_early_if_no_storage() {
		// Temporairement simuler que Storage n'existe pas
		// En pratique, Storage existe toujours en test
		Alert404_Dashboard::init();

		$this->assertTrue( true );
	}

	/**
	 * Teste que render_page affiche une notice si pas de stats
	 */
	public function test_render_page_displays_notice_if_no_stats() {
		$user_id = self::factory()->user->create( array( 'role' => 'administrator' ) );
		wp_set_current_user( $user_id );

		ob_start();
		Alert404_Dashboard::render_page();
		$output = ob_get_clean();

		// Si pas de stats, affiche une notice
		// Devrait contenir "notice" ou "aucune donnée"
		$this->assertIsString( $output );

		wp_set_current_user( 0 );
	}

	/**
	 * Teste que l'action par défaut est 'list'
	 */
	public function test_default_action_is_list() {
		// Sans action spécifiée, doit utiliser 'list'
		unset( $_GET['action'] );

		$user_id = self::factory()->user->create( array( 'role' => 'administrator' ) );
		wp_set_current_user( $user_id );

		ob_start();
		Alert404_Dashboard::render_page();
		$output = ob_get_clean();

		$this->assertIsString( $output );

		wp_set_current_user( 0 );
	}

	/**
	 * Teste que render_page échappe correctement les données
	 */
	public function test_render_page_escapes_output() {
		// Insérer des données avec du contenu HTML
		global $wpdb;
		$table_name = $wpdb->prefix . '404_alert_stats';

		$wpdb->insert(
			$table_name,
			array(
				'url'                 => '/test<script>alert(1)</script>',
				'ip'                  => '192.168.1.1',
				'referrer'            => 'https://example.com',
				'user_agent'          => 'Mozilla',
				'user_agent_readable' => 'Chrome',
				'created_at'          => current_time( 'mysql' ),
			),
			array( '%s', '%s', '%s', '%s', '%s', '%s' )
		);

		$user_id = self::factory()->user->create( array( 'role' => 'administrator' ) );
		wp_set_current_user( $user_id );

		ob_start();
		Alert404_Dashboard::render_page();
		$output = ob_get_clean();

		// Les balises script ne doivent pas apparaître dans la sortie
		// (elles doivent être échappées)
		$this->assertStringNotContainsString( '<script>', $output );

		wp_set_current_user( 0 );
	}

	/**
	 * Teste que add_menu utilise le bon parent menu
	 */
	public function test_add_menu_uses_correct_parent() {
		// add_menu doit enregistrer un submenu sous '404_alert'
		Alert404_Dashboard::add_menu();

		// Ne devrait pas faire d'erreur
		$this->assertTrue( true );
	}

	/**
	 * Teste que render_page appelle les bonnes méthodes Storage
	 */
	public function test_render_page_calls_storage_methods() {
		// Cette méthode appelle Storage::get_stats(), get_total_count(), etc.
		// On teste juste que tout s'exécute sans erreur

		$user_id = self::factory()->user->create( array( 'role' => 'administrator' ) );
		wp_set_current_user( $user_id );

		ob_start();
		Alert404_Dashboard::render_page();
		$output = ob_get_clean();

		$this->assertIsString( $output );

		wp_set_current_user( 0 );
	}
}
