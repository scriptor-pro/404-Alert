<?php
/**
 * Plugin activation handler
 *
 * @package Alert404
 */

namespace Alert404;

use function get_option;
use function update_option;
use function wp_next_scheduled;
use function wp_schedule_event;

defined( 'ABSPATH' ) || exit;

class Activator {
	/**
	 * Activation hook callback
	 */
	public static function activate(): void {
		self::create_table();
		self::set_defaults();
		self::schedule_cron();
	}

	/**
	 * Deactivation hook callback
	 */
	public static function deactivate(): void {
		wp_clear_scheduled_hook( 'alert404_send_digest' );
	}

	/**
	 * Create the database table
	 */
	private static function create_table(): void {
		global $wpdb;

		$charset_collate = $wpdb->get_charset_collate();
		$table_name      = $wpdb->prefix . '404_alert_log';

		$sql = "CREATE TABLE IF NOT EXISTS $table_name (
			id         bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			url        varchar(2048) NOT NULL,
			ip         varchar(45)   NOT NULL DEFAULT '',
			referrer   varchar(2048) NOT NULL DEFAULT '',
			user_agent varchar(512)  NOT NULL DEFAULT '',
			alerted    tinyint(1)    NOT NULL DEFAULT 0,
			created_at datetime      NOT NULL DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			KEY idx_created_at (created_at),
			KEY idx_url (url(191))
		) $charset_collate;";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );

		update_option( '404_alert_schema_v1', '1' );
	}

	/**
	 * Set default options
	 */
	private static function set_defaults(): void {
		$defaults = [
			'daily_limit'   => 20,
			'digest_enabled' => true,
			'digest_time'   => '08:00',
			'log_retention' => 5000,
		];

		if ( ! get_option( '404_alert_options' ) ) {
			update_option( '404_alert_options', $defaults );
		}
	}

	/**
	 * Schedule the daily digest cron
	 */
	private static function schedule_cron(): void {
		if ( ! wp_next_scheduled( 'alert404_send_digest' ) ) {
			wp_schedule_event( time(), 'daily', 'alert404_send_digest' );
		}
	}
}
