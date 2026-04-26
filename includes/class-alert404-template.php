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
	 * Initialise le gestionnaire de templates personnalisés
	 * Enregistre l'action WordPress pour charger le template 404 personnalisé
	 *
	 * @return void
	 */
	public static function init(): void {
		add_action( 'template_redirect', array( self::class, 'display_404_template' ), 20 );
	}

	/**
	 * Affiche le template 404 personnalisé lors d'une erreur 404
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
	 * Retourne le chemin vers le template 404 personnalisé du plugin
	 *
	 * @return string Chemin absolu du template 404.php
	 */
	private static function get_template_path(): string {
		return plugin_dir_path( __FILE__ ) . '../templates/404.php';
	}
}
