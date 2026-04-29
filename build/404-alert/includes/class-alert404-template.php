<?php
/**
 * Custom 404 page for 404 Alert.
 *
 * @package Alert404
 */

defined( 'ABSPATH' ) || exit;

/**
 * Custom 404 page for 404 Alert.
 */
class Alert404_Template {
	/**
	 * Initialize the custom template manager
	 * Registers the WordPress action to load the custom 404 template
	 *
	 * @return void
	 */
	public static function init(): void {
		add_action( 'template_redirect', array( self::class, 'display_404_template' ), 20 );
	}

	/**
	 * Display the custom 404 template when a 404 error occurs
	 *
	 * @return void
	 */
	public static function display_404_template(): void {
		if ( is_404() ) {
			$custom_template = self::get_template_path();
			if ( file_exists( $custom_template ) ) {
				status_header( 404 );
				include $custom_template;
				exit;
			}
		}
	}

	/**
	 * Return the path to the plugin's custom 404 template
	 *
	 * @return string Absolute path to 404.php template
	 */
	private static function get_template_path(): string {
		return plugin_dir_path( __FILE__ ) . '../templates/404.php';
	}
}
