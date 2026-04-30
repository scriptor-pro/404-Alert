<?php
/**
 * Plugin uninstall script
 *
 * @package Alert404
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

global $wpdb;

// Drop table
$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}404_alert_log" );

// Delete options
delete_option( '404_alert_options' );
delete_option( '404_alert_stats_schema_v2' );

// Clear transients
$wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '%404_alert_%'" );

// Clear cron
wp_clear_scheduled_hook( 'alert404_send_digest' );
