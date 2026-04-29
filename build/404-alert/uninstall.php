<?php
/**
 * Fichier de désinstallation du plugin 404 Alert
 * Supprime toutes les données créées par le plugin
 *
 * @package Alert404
 */

defined( 'ABSPATH' ) || exit;

// Vérifier que la demande vient bien de WordPress
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

global $wpdb;

// Supprimer la table de statistiques
$alert404_table_name = $wpdb->prefix . '404_alert_stats';
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange
$wpdb->query( $wpdb->prepare( 'DROP TABLE IF EXISTS %i', $alert404_table_name ) );

// Supprimer toutes les options du plugin
delete_option( '404_alert_options' );
delete_option( '404_alert_smtp_options' );

// Supprimer les transients du plugin
$wpdb->query( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
	$wpdb->prepare(
		"DELETE FROM $wpdb->options WHERE option_name LIKE %s",
		'%404_alert%transient%'
	)
);

// Supprimer les caches de l'application
wp_cache_flush();
