<?php
/**
 * Page 404 personnalisée pour 404 Alert
 */

defined( 'ABSPATH' ) || exit;

class Alert404_Template {
	/**
	 * Initialise le gestionnaire de templates personnalisés
	 * Enregistre le filtre WordPress pour charger le template 404 personnalisé
	 *
	 * @return void
	 */
	public static function init(): void {
		add_filter( 'template_include', array( self::class, 'load_404_template' ) );
	}

	/**
	 * Charge le template 404 personnalisé du plugin si disponible
	 * Utile pour personnaliser la page d'erreur 404
	 *
	 * @param string $template Chemin du template courant
	 * @return string Chemin du template 404 personnalisé ou template courant
	 */
	public static function load_404_template( string $template ): string {
		if ( is_404() ) {
			$custom_template = self::get_template_path();
			if ( file_exists( $custom_template ) ) {
				return $custom_template;
			}
		}
		return $template;
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
