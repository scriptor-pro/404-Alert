<?php
/**
 * Plugin Name: 404 Alert
 * Plugin URI: https://github.com/baudouin-dev/404-alert
 * Description: Intelligent 404 error alerts with daily digest for WordPress admins
 * Version: 2.3.0
 * Author: Baudouin
 * Author URI: https://etik.com
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: 404-alert
 * Domain Path: /languages
 * Requires at least: 6.4
 * Requires PHP: 8.1
 *
 * @package Alert404
 */

defined( 'ABSPATH' ) || exit;

const ALERT404_VERSION = '2.3.0';
const ALERT404_FILE = __FILE__;
const ALERT404_DIR = __DIR__;
define( 'ALERT404_URL', plugin_dir_url( __FILE__ ) );

/**
 * Simple PSR-4 autoloader
 */
spl_autoload_register( function ( $class ) {
	if ( 0 !== strpos( $class, 'Alert404\\' ) ) {
		return;
	}

	$short_class = substr( $class, strlen( 'Alert404\\' ) );
	$map = [
		'Activator'              => 'class-activator.php',
		'Dashboard'              => 'class-dashboard.php',
		'Detector'               => 'class-detector.php',
		'Digest'                 => 'class-digest.php',
		'MailHealthChecker'      => 'class-mail-health-checker.php',
		'MailLogger'             => 'class-mail-logger.php',
		'MailMessageFactory'     => 'class-mail-message-factory.php',
		'MailService'            => 'class-mail-service.php',
		'MailSettingsRepository' => 'class-mail-settings-repository.php',
		'Mailer'                 => 'class-mailer.php',
		'RecipientResolver'      => 'class-recipient-resolver.php',
		'SecretStore'            => 'class-secret-store.php',
		'SmtpTransport'          => 'class-smtp-transport.php',
		'SMTP'                   => 'class-smtp.php',
		'RateLimiter'            => 'class-rate-limiter.php',
		'RequestInfo'            => 'class-request-info.php',
		'Settings'               => 'class-settings.php',
		'Stats'                  => 'class-stats.php',
	];

	if ( ! isset( $map[ $short_class ] ) ) {
		return;
	}

	$file = ALERT404_DIR . '/includes/' . $map[ $short_class ];

	if ( file_exists( $file ) ) {
		require_once $file;
	}
} );

/**
 * Bootstrap the plugin
 */
function alert404_init() {
	if ( is_admin() ) {
		new Alert404\Settings();
		new Alert404\Dashboard();
	}

	new Alert404\Detector();
}

register_activation_hook( ALERT404_FILE, [ 'Alert404\Activator', 'activate' ] );
register_deactivation_hook( ALERT404_FILE, [ 'Alert404\Activator', 'deactivate' ] );

add_action( 'plugins_loaded', 'alert404_init' );
