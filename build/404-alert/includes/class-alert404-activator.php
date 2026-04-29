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
	 * Initialize activation and deactivation hooks
	 *
	 * @return void
	 */
	public static function init(): void {
		register_activation_hook( ALERT404_MAIN_FILE, array( self::class, 'activate' ) );
		register_deactivation_hook( ALERT404_MAIN_FILE, array( self::class, 'deactivate' ) );
	}

	/**
	 * Executed when the plugin is activated
	 * Creates the statistics table and initializes options
	 *
	 * @return void
	 */
	public static function activate(): void {
		// Statistics table is created on demand in Alert404_Stats

		// Initialize default options if they don't exist.
		if ( ! get_option( '404_alert_options' ) ) {
			update_option(
				'404_alert_options',
				array(
					'email'         => get_option( 'admin_email' ),
					'daily_limit'   => 500,
					'ip_cooldown'   => 300,
					'force_logging' => 0,
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
	 * Executed when the plugin is deactivated
	 *
	 * @return void
	 */
	public static function deactivate(): void {
		// No action required on deactivation.
		// Plugin retains data for potential reactivation.
		// Data is deleted only on uninstall (uninstall.php).
	}
}
