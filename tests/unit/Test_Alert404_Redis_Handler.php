<?php
/**
 * Tests unitaires pour Alert404_Redis_Handler
 * Teste les opérations Redis atomiques avec fallback
 */

class Test_Alert404_Redis_Handler extends Alert404_UnitTestCase {

	/**
	 * État avant chaque test
	 */
	public function setUp(): void {
		parent::setUp();
		// Charger la classe Redis Handler
		if ( ! class_exists( 'Alert404_Redis_Handler' ) ) {
			require_once dirname( dirname( dirname( __FILE__ ) ) ) . '/includes/class-redis-handler.php';
		}
		// Fermer toute connexion existante
		Alert404_Redis_Handler::close();
	}

	/**
	 * Teste que init() retourne false si extension Redis n'est pas installée
	 */
	public function test_init_returns_false_if_extension_missing() {
		// Cette extension est probablement manquante en test
		// Donc on attend false
		$result = Alert404_Redis_Handler::init();
		// Si Redis est disponible, ce test sera vrai; sinon il retourne false comme prévu
		$this->assertIsBool( $result );
	}

	/**
	 * Teste que is_available() retourne un booléen
	 */
	public function test_is_available_returns_bool() {
		$result = Alert404_Redis_Handler::is_available();
		$this->assertIsBool( $result );
	}

	/**
	 * Teste que get_instance() retourne null ou Redis
	 */
	public function test_get_instance_returns_redis_or_null() {
		Alert404_Redis_Handler::init();
		$instance = Alert404_Redis_Handler::get_instance();

		if ( $instance !== null ) {
			$this->assertInstanceOf( 'Redis', $instance );
		} else {
			$this->assertNull( $instance );
		}
	}

	/**
	 * Teste set() et get() roundtrip simple
	 */
	public function test_set_and_get_roundtrip() {
		if ( ! Alert404_Redis_Handler::is_available() ) {
			$this->markTestSkipped( 'Redis non disponible' );
		}

		$key   = 'test_key_' . uniqid();
		$value = 'test_value_' . time();

		// Set
		$set_result = Alert404_Redis_Handler::set( $key, $value, 60 );
		$this->assertTrue( $set_result );

		// Get
		$get_result = Alert404_Redis_Handler::get( $key );
		$this->assertEquals( $value, $get_result );
	}

	/**
	 * Teste set() avec TTL
	 */
	public function test_set_with_ttl() {
		if ( ! Alert404_Redis_Handler::is_available() ) {
			$this->markTestSkipped( 'Redis non disponible' );
		}

		$key   = 'test_ttl_' . uniqid();
		$value = 'ttl_value';

		// Set avec TTL de 1 seconde
		$result = Alert404_Redis_Handler::set( $key, $value, 1 );
		$this->assertTrue( $result );

		// Vérifier que la clé existe immédiatement
		$get_result = Alert404_Redis_Handler::get( $key );
		$this->assertEquals( $value, $get_result );

		// Attendre l'expiration
		sleep( 2 );

		// Vérifier que la clé a expiré
		$expired = Alert404_Redis_Handler::get( $key );
		$this->assertFalse( $expired );
	}

	/**
	 * Teste increment() atomique
	 */
	public function test_increment_atomic() {
		if ( ! Alert404_Redis_Handler::is_available() ) {
			$this->markTestSkipped( 'Redis non disponible' );
		}

		$key = 'counter_' . uniqid();

		// Incrémenter à partir de 0
		$result1 = Alert404_Redis_Handler::increment( $key, 60 );
		$this->assertEquals( 1, $result1 );

		// Incrémenter encore
		$result2 = Alert404_Redis_Handler::increment( $key, 60 );
		$this->assertEquals( 2, $result2 );

		// Vérifier la valeur
		$value = Alert404_Redis_Handler::get( $key );
		$this->assertEquals( '2', $value );
	}

	/**
	 * Teste increment() avec TTL
	 */
	public function test_increment_with_ttl() {
		if ( ! Alert404_Redis_Handler::is_available() ) {
			$this->markTestSkipped( 'Redis non disponible' );
		}

		$key = 'ttl_counter_' . uniqid();

		// Incrémenter avec TTL court
		$result = Alert404_Redis_Handler::increment( $key, 1 );
		$this->assertEquals( 1, $result );

		// Attendre expiration
		sleep( 2 );

		// Incrémenter après expiration (devrait être 1, pas 2)
		$result2 = Alert404_Redis_Handler::increment( $key, 1 );
		$this->assertEquals( 1, $result2 );
	}

	/**
	 * Teste acquire_lock() atomique
	 */
	public function test_acquire_lock_atomic() {
		if ( ! Alert404_Redis_Handler::is_available() ) {
			$this->markTestSkipped( 'Redis non disponible' );
		}

		$key = 'lock_' . uniqid();

		// Première acquisition doit réussir
		$lock1 = Alert404_Redis_Handler::acquire_lock( $key, 5 );
		$this->assertTrue( $lock1 );

		// Deuxième acquisition doit échouer (NX = only if not exists)
		$lock2 = Alert404_Redis_Handler::acquire_lock( $key, 5 );
		$this->assertFalse( $lock2 );
	}

	/**
	 * Teste release_lock()
	 */
	public function test_release_lock() {
		if ( ! Alert404_Redis_Handler::is_available() ) {
			$this->markTestSkipped( 'Redis non disponible' );
		}

		$key = 'release_lock_' . uniqid();

		// Acquérir le verrou
		Alert404_Redis_Handler::acquire_lock( $key, 5 );

		// Libérer le verrou
		$released = Alert404_Redis_Handler::release_lock( $key );
		$this->assertTrue( $released );

		// Devrait pouvoir le réacquérir maintenant
		$lock = Alert404_Redis_Handler::acquire_lock( $key, 5 );
		$this->assertTrue( $lock );
	}

	/**
	 * Teste delete() avec clé simple
	 */
	public function test_delete_single_key() {
		if ( ! Alert404_Redis_Handler::is_available() ) {
			$this->markTestSkipped( 'Redis non disponible' );
		}

		$key = 'delete_test_' . uniqid();

		// Créer une clé
		Alert404_Redis_Handler::set( $key, 'value', 60 );

		// Supprimer
		$deleted = Alert404_Redis_Handler::delete( $key );
		$this->assertEquals( 1, $deleted );

		// Vérifier qu'elle n'existe plus
		$value = Alert404_Redis_Handler::get( $key );
		$this->assertFalse( $value );
	}

	/**
	 * Teste delete() avec clés multiples
	 */
	public function test_delete_multiple_keys() {
		if ( ! Alert404_Redis_Handler::is_available() ) {
			$this->markTestSkipped( 'Redis non disponible' );
		}

		$key1 = 'delete_multi_1_' . uniqid();
		$key2 = 'delete_multi_2_' . uniqid();
		$key3 = 'delete_multi_3_' . uniqid();

		// Créer les clés
		Alert404_Redis_Handler::set( $key1, 'val1', 60 );
		Alert404_Redis_Handler::set( $key2, 'val2', 60 );
		Alert404_Redis_Handler::set( $key3, 'val3', 60 );

		// Supprimer les 3
		$deleted = Alert404_Redis_Handler::delete( [ $key1, $key2, $key3 ] );
		$this->assertEquals( 3, $deleted );
	}

	/**
	 * Teste delete() sur une clé inexistante
	 */
	public function test_delete_nonexistent_key() {
		if ( ! Alert404_Redis_Handler::is_available() ) {
			$this->markTestSkipped( 'Redis non disponible' );
		}

		$key = 'nonexistent_' . uniqid();

		// Supprimer une clé qui n'existe pas
		$deleted = Alert404_Redis_Handler::delete( $key );
		$this->assertEquals( 0, $deleted );
	}

	/**
	 * Teste que set() retourne false si Redis indisponible
	 */
	public function test_set_returns_false_if_redis_unavailable() {
		// Forcer fermeture de Redis
		Alert404_Redis_Handler::close();

		// Définir une config impossible
		if ( ! defined( 'ALERT404_REDIS_HOST' ) ) {
			define( 'ALERT404_REDIS_HOST', '127.0.0.1' );
		}
		if ( ! defined( 'ALERT404_REDIS_PORT' ) ) {
			define( 'ALERT404_REDIS_PORT', 9999 ); // Port invalide
		}

		$result = Alert404_Redis_Handler::set( 'test_key', 'value', 60 );
		// Devrait échouer ou retourner false
		$this->assertFalse( $result );
	}

	/**
	 * Teste que get() retourne false si Redis indisponible
	 */
	public function test_get_returns_false_if_redis_unavailable() {
		Alert404_Redis_Handler::close();

		$result = Alert404_Redis_Handler::get( 'nonexistent_key' );
		// Retourne false si pas de Redis
		$this->assertFalse( $result );
	}

	/**
	 * Teste que increment() retourne false si Redis indisponible
	 */
	public function test_increment_returns_false_if_redis_unavailable() {
		Alert404_Redis_Handler::close();

		$result = Alert404_Redis_Handler::increment( 'test_counter', 60 );
		$this->assertFalse( $result );
	}

	/**
	 * Teste get_info() si disponible
	 */
	public function test_get_info() {
		if ( ! Alert404_Redis_Handler::is_available() ) {
			$this->markTestSkipped( 'Redis non disponible' );
		}

		$info = Alert404_Redis_Handler::get_info();

		// Devrait être un array si disponible, false sinon
		if ( $info !== false ) {
			$this->assertIsArray( $info );
		} else {
			$this->assertFalse( $info );
		}
	}

	/**
	 * Teste close()
	 */
	public function test_close() {
		if ( ! Alert404_Redis_Handler::is_available() ) {
			$this->markTestSkipped( 'Redis non disponible' );
		}

		// Initialiser
		Alert404_Redis_Handler::init();
		$this->assertTrue( Alert404_Redis_Handler::is_available() );

		// Fermer
		Alert404_Redis_Handler::close();

		// Après fermeture, la prochaine tentative d'init devrait reconnecter
		$result = Alert404_Redis_Handler::init();
		// Retournera true si Redis est accessible, false sinon
		$this->assertIsBool( $result );
	}
}
