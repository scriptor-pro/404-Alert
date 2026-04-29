<?php
/**
 * Plugin Name: 404 Alert
 * Plugin URI: https://github.com/baudouin/404-alert
 * Description: Envoie un email à l'administrateur à chaque erreur 404. <a href="options-general.php?page=404_alert">Paramètres</a>
 * Version: 1.2.9
 * Author: Baudouin
 * Author URI: https://etik.com
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: 404-alert
 * Domain Path: /languages
 * Requires at least: 5.0
 * Requires PHP: 8.1
 * Tested up to: 6.9
 *
 * @package Alert404
 */

defined( 'ABSPATH' ) || exit;

// Disable plugin on fatal error.
register_shutdown_function( function() {
	$error = error_get_last();
	if ( $error && in_array( $error['type'], array( E_ERROR, E_PARSE, E_COMPILE_ERROR ), true ) ) {
		deactivate_plugins( __FILE__ );
	}
} );

define( 'ALERT404_VERSION', '1.2.9' );
define( 'ALERT404_DIR', plugin_dir_path( __FILE__ ) );
define( 'ALERT404_MAIN_FILE', __FILE__ );

require_once ALERT404_DIR . 'includes/class-alert404-logger.php';
require_once ALERT404_DIR . 'includes/class-alert404-redis-handler.php';
require_once ALERT404_DIR . 'includes/class-alert404-user-agent-parser.php';
require_once ALERT404_DIR . 'includes/class-alert404-request-info.php';
require_once ALERT404_DIR . 'includes/class-alert404-smtp-presets.php';
require_once ALERT404_DIR . 'includes/class-alert404-test-progress.php';
require_once ALERT404_DIR . 'includes/class-alert404-smtp-handler.php';
require_once ALERT404_DIR . 'includes/class-alert404-settings.php';
require_once ALERT404_DIR . 'includes/class-alert404-smtp-diagnostics.php';
require_once ALERT404_DIR . 'includes/class-alert404-rate-limiter.php';
require_once ALERT404_DIR . 'includes/class-alert404-stats.php';
require_once ALERT404_DIR . 'includes/class-alert404-storage.php';
require_once ALERT404_DIR . 'includes/class-alert404-mailer.php';
require_once ALERT404_DIR . 'includes/class-alert404-detector.php';
require_once ALERT404_DIR . 'includes/class-alert404-template.php';
require_once ALERT404_DIR . 'includes/class-alert404-dashboard.php';
require_once ALERT404_DIR . 'includes/class-alert404-activator.php';

// Initialiser les hooks d'activation/désactivation immédiatement (ne doit être appelé qu'une fois).
Alert404_Activator::init();

/**
 * Initialise le plugin au démarrage de WordPress
 *
 * @return void
 */
function alert404_init(): void {
	// Initialiser Redis (optionnel, fallback à transients).
	Alert404_Redis_Handler::init();


	Alert404_Settings::init();
	Alert404_SMTP_Diagnostics::init();
	Alert404_Template::init();
	Alert404_Detector::init();
	Alert404_Dashboard::init();
}

// Initialiser le plugin au hook plugins_loaded.
add_action( 'plugins_loaded', 'alert404_init' );
