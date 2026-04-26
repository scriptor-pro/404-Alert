<?php
/**
 * Plugin activation and deactivation handler.
 *
 * @package Alert404
 */

defined( 'ABSPATH' ) || exit;

/**
 * Plugin activation and deactivation handler.
 */
class Alert404_Activator {

	/**
	 * Initialise les hooks d'activation et de désactivation
	 *
	 * @return void
	 */
	public static function init(): void {
		register_activation_hook( ALERT404_MAIN_FILE, array( self::class, 'activate' ) );
		register_deactivation_hook( ALERT404_MAIN_FILE, array( self::class, 'deactivate' ) );
	}

	/**
	 * Exécuté lors de l'activation du plugin
	 * Crée la table de statistiques et initialise les options
	 *
	 * @return void
	 */
	public static function activate(): void {
		// Ensure statistics table exists.
		if ( class_exists( 'Alert404_Storage' ) ) {
			Alert404_Storage::init();
		}

		// Initialize default options if they don't exist.
		if ( ! get_option( '404_alert_options' ) ) {
			update_option(
				'404_alert_options',
				array(
					'email'         => get_option( 'admin_email' ),
					'daily_limit'   => 500,
					'ip_cooldown'   => 300,
					'force_logging' => 0,
					'enable_stats'  => 0,
				)
			);
		}

		if ( ! get_option( '404_alert_smtp_options' ) ) {
			update_option(
				'404_alert_smtp_options',
				array(
					'host'       => '',
					'port'       => 587,
					'username'   => '',
					'password'   => '',
					'encryption' => 'tls',
					'from_email' => get_option( 'admin_email' ),
					'from_name'  => get_bloginfo( 'name' ),
				)
			);
		}

		// Create cached activation message for admin display.
		set_transient( 'alert404_activated', true, HOUR_IN_SECONDS );
	}

	/**
	 * Exécuté lors de la désactivation du plugin
	 *
	 * @return void
	 */
	public static function deactivate(): void {
		// No action required on deactivation.
		// Plugin retains data for potential reactivation.
		// Data is deleted only on uninstall (uninstall.php).
	}
}
