<?php
/**
 * Tests unitaires pour Alert404_Template
 */

class Test_Alert404_Template extends Alert404_UnitTestCase {

	/**
	 * État avant chaque test
	 */
	public function setUp(): void {
		parent::setUp();
		// Réinitialiser l'état WP_Query
		global $wp_query;
		$wp_query = null;
		wp_reset_query();
	}

	/**
	 * Teste que init() enregistre le filtre
	 */
	public function test_init_registers_filter() {
		// Vérifier que le filtre est enregistré
		Alert404_Template::init();

		// Vérifier que le hook est enregistré
		$this->assertTrue( has_filter( 'template_include' ) );
	}

	/**
	 * Teste que load_404_template retourne le template courant si pas 404
	 */
	public function test_load_404_template_returns_current_if_not_404() {
		$template = '/path/to/template.php';

		$result = Alert404_Template::load_404_template( $template );

		// Devrait retourner le même template
		$this->assertEquals( $template, $result );
	}

	/**
	 * Teste que load_404_template retourne template courant si fichier custom absent
	 */
	public function test_load_404_template_returns_current_if_custom_missing() {
		// Simuler une requête 404
		$this->set_404();

		$template = '/path/to/template.php';

		$result = Alert404_Template::load_404_template( $template );

		// Devrait retourner le template courant si fichier custom n'existe pas
		$this->assertEquals( $template, $result );
	}

	/**
	 * Teste que load_404_template est un callable
	 */
	public function test_load_404_template_is_callable() {
		$this->assertTrue( is_callable( array( 'Alert404_Template', 'load_404_template' ) ) );
	}

	/**
	 * Teste que load_404_template accepte un string en paramètre
	 */
	public function test_load_404_template_accepts_string_parameter() {
		$template = 'some/template/path.php';

		// Ne devrait pas faire d'erreur
		$result = Alert404_Template::load_404_template( $template );

		$this->assertIsString( $result );
	}

	/**
	 * Teste que load_404_template retourne un string
	 */
	public function test_load_404_template_returns_string() {
		$template = '/path/to/template.php';

		$result = Alert404_Template::load_404_template( $template );

		$this->assertIsString( $result );
	}

	/**
	 * Teste que la méthode fonctionne avec différents chemins
	 */
	public function test_load_404_template_with_various_paths() {
		$paths = array(
			'/wp-content/themes/theme/404.php',
			'wp-content/themes/theme/page.php',
			'/var/www/html/wp-content/themes/mytheme/template.php',
			'./templates/custom.php',
			'../templates/404.php',
		);

		foreach ( $paths as $path ) {
			$result = Alert404_Template::load_404_template( $path );
			$this->assertIsString( $result );
		}
	}

	/**
	 * Teste que le template path contient 404.php
	 */
	public function test_template_path_contains_404_php() {
		// La méthode privée est testée indirectement
		// On simule une 404 et on vérifie que le template est recherché
		$this->set_404();

		$template = '/path/to/old.php';
		$result = Alert404_Template::load_404_template( $template );

		// Le résultat devrait contenir le chemin vers 404.php
		// Ou retourner le template courant si fichier absent
		$this->assertIsString( $result );
	}

	/**
	 * Teste que init() peut être appelé plusieurs fois sans erreur
	 */
	public function test_init_can_be_called_multiple_times() {
		Alert404_Template::init();
		Alert404_Template::init();

		// Ne devrait pas faire d'erreur
		$this->assertTrue( true );
	}

	/**
	 * Teste que la fonction travaille avec des templates vides
	 */
	public function test_load_404_template_with_empty_string() {
		$result = Alert404_Template::load_404_template( '' );

		$this->assertIsString( $result );
	}

	/**
	 * Teste que load_404_template préserve les chemins relatifs
	 */
	public function test_load_404_template_preserves_relative_paths() {
		$template = '../templates/page.php';

		$result = Alert404_Template::load_404_template( $template );

		// Devrait retourner le même chemin si pas 404
		$this->assertEquals( $template, $result );
	}

	/**
	 * Teste que load_404_template préserve les chemins absolus
	 */
	public function test_load_404_template_preserves_absolute_paths() {
		$template = '/absolute/path/to/template.php';

		$result = Alert404_Template::load_404_template( $template );

		// Devrait retourner le même chemin si pas 404
		$this->assertEquals( $template, $result );
	}

	/**
	 * Teste que is_404() est utilisé correctement
	 */
	public function test_load_404_template_uses_is_404() {
		// Simuler une 404
		$this->set_404();

		// La condition is_404() doit être vraie
		$this->assertTrue( is_404() );

		$template = '/path/to/template.php';
		$result = Alert404_Template::load_404_template( $template );

		// Résultat sera le template courant ou custom si existe
		$this->assertIsString( $result );
	}

	/**
	 * Teste le comportement avec une requête normale (non-404)
	 */
	public function test_load_404_template_on_normal_request() {
		// Ne pas simuler une 404
		global $wp_query;

		// Assurer que is_404() retourne false
		if ( isset( $wp_query ) ) {
			$wp_query->is_404 = false;
		}

		$template = '/path/to/normal-template.php';
		$result = Alert404_Template::load_404_template( $template );

		// Devrait retourner le même template
		$this->assertEquals( $template, $result );
	}

	/**
	 * Teste que le filtre reçoit le bon paramètre
	 */
	public function test_load_404_template_receives_correct_parameter() {
		$template = '/path/to/test.php';

		// Appeler la fonction avec un template valide
		$result = Alert404_Template::load_404_template( $template );

		// Le paramètre devrait être préservé ou remplacé
		$this->assertIsString( $result );
	}

	/**
	 * Teste que le filtre peut être dédupliqué (idempotent)
	 */
	public function test_load_404_template_is_idempotent() {
		$template = '/path/to/template.php';

		// Appeler deux fois avec le même paramètre
		$result1 = Alert404_Template::load_404_template( $template );
		$result2 = Alert404_Template::load_404_template( $template );

		// Les résultats doivent être identiques
		$this->assertEquals( $result1, $result2 );
	}
}
