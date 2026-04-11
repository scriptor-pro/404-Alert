<?php
/**
 * Bootstrap pour les tests PHPUnit
 * Charge le framework de test WordPress et initialise l'environnement
 */

defined( 'ABSPATH' ) || exit;

// Détecter le répertoire WordPress (pour les environnements de test)
if ( ! defined( 'WP_TESTS_DIR' ) ) {
	$wp_tests_dir_env = getenv( 'WP_TESTS_DIR' );
	if ( ! empty( $wp_tests_dir_env ) && file_exists( $wp_tests_dir_env . '/includes/functions.php' ) ) {
		define( 'WP_TESTS_DIR', $wp_tests_dir_env );
	}
}

if ( ! defined( 'WP_TESTS_CONFIG_FILE_PATH' ) ) {
	define( 'WP_TESTS_CONFIG_FILE_PATH', __DIR__ . '/wp-tests-config.php' );
}

if ( ! defined( 'WP_TESTS_PHPUNIT_POLYFILLS_PATH' ) ) {
	define( 'WP_TESTS_PHPUNIT_POLYFILLS_PATH', dirname( __DIR__ ) . '/vendor/yoast/phpunit-polyfills' );
}

if ( ! defined( 'WP_TESTS_DIR' ) ) {
	// Chercher dans les emplacements courants
	$wp_tests_dirs = [
		// Installation locale avec Composer
		dirname( __DIR__ ) . '/wordpress-local/wp-content/plugins/404-alert/tests/wordpress',
		// Installation Docker
		'/wordpress/tests-lib',
		// Installation standard
		dirname( dirname( dirname( __DIR__ ) ) ) . '/wordpress-tests-lib',
		// Via WP_TESTS_DIR env
		getenv( 'WP_TESTS_DIR' ),
		// Via WORDPRESS_TESTS_DIR env
		getenv( 'WORDPRESS_TESTS_DIR' ),
	];

	$found = false;
	foreach ( $wp_tests_dirs as $dir ) {
		if ( file_exists( $dir . '/includes/functions.php' ) ) {
			define( 'WP_TESTS_DIR', $dir );
			$found = true;
			break;
		}
	}

	if ( ! $found ) {
		echo 'Erreur: Impossible de trouver WordPress Test Framework.' . PHP_EOL;
		echo 'Définir WORDPRESS_TESTS_DIR=' . PHP_EOL;
		exit( 1 );
	}
}

// Charger le framework de test WordPress
require_once WP_TESTS_DIR . '/includes/functions.php';

// Charger le plugin à tester
function _manually_load_plugin() {
	if ( ! defined( 'ALERT404_DIR' ) ) {
		define( 'ALERT404_DIR', dirname( dirname( __FILE__ ) ) . '/' );
	}

	// Charger les classes du plugin
	require_once dirname( dirname( __FILE__ ) ) . '/includes/class-logger.php';
	require_once dirname( dirname( __FILE__ ) ) . '/includes/class-redis-handler.php';
	require_once dirname( dirname( __FILE__ ) ) . '/includes/class-user-agent-parser.php';
	require_once dirname( dirname( __FILE__ ) ) . '/includes/class-request-info.php';
	require_once dirname( dirname( __FILE__ ) ) . '/includes/class-smtp-handler.php';
	require_once dirname( dirname( __FILE__ ) ) . '/includes/class-settings.php';
	require_once dirname( dirname( __FILE__ ) ) . '/includes/class-rate-limiter.php';
	require_once dirname( dirname( __FILE__ ) ) . '/includes/class-storage.php';
	require_once dirname( dirname( __FILE__ ) ) . '/includes/class-mailer.php';
	require_once dirname( dirname( __FILE__ ) ) . '/includes/class-detector.php';
	require_once dirname( dirname( __FILE__ ) ) . '/includes/class-404-template.php';
	require_once dirname( dirname( __FILE__ ) ) . '/includes/class-dashboard.php';

	// Initialiser les classes
	Alert404_Settings::init();
	Alert404_Storage::init();
	Alert404_Template::init();
	Alert404_Detector::init();
	Alert404_Dashboard::init();
}

tests_add_filter( 'muplugins_loaded', '_manually_load_plugin' );

// Charger le framework de test WordPress
require_once WP_TESTS_DIR . '/includes/bootstrap.php';

// Classe de base pour les tests 404 Alert
if ( ! class_exists( 'Alert404_UnitTestCase' ) ) {
	/**
	 * Classe de base pour les tests unitaires 404 Alert
	 */
	class Alert404_UnitTestCase extends WP_UnitTestCase {

		/**
		 * Nettoie les transients après chaque test
		 *
		 * @return void
		 */
		public function tearDown(): void {
			parent::tearDown();
			$this->clear_all_transients();
		}

		/**
		 * Efface tous les transients du plugin
		 *
		 * @return void
		 */
		protected function clear_all_transients() {
			global $wpdb;
			// Supprimer tous les transients du plugin
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Cleanup of test transients
			$wpdb->query(
				$wpdb->prepare(
					"DELETE FROM $wpdb->options
					WHERE option_name LIKE %s
					AND option_name LIKE %s",
					'%404_alert%',
					'%_transient_%'
				)
			);
		}

		/**
		 * Simule une requête 404
		 *
		 * @param string $url URL à tester
		 * @return void
		 */
		protected function set_404( $url = '/inexistent' ) {
			global $wp_query;
			$wp_query->set_404();
			$_SERVER['REQUEST_URI'] = $url;
		}

		/**
		 * Obtient une option de test
		 *
		 * @return array
		 */
		protected function get_test_options() {
			return [
				'email'       => 'admin@example.com',
				'daily_limit' => 500,
				'ip_cooldown' => 300,
			];
		}

		/**
		 * Initialise les options du plugin
		 *
		 * @param array $options Options personnalisées
		 * @return void
		 */
		protected function setup_plugin_options( $options = [] ) {
			$defaults = $this->get_test_options();
			$merged   = array_merge( $defaults, $options );
			update_option( '404_alert_options', $merged );
		}
	}
}
