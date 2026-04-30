<?php
/**
 * Statistics and logging to database
 *
 * @package Alert404
 */

namespace Alert404;

use function get_option;
use function current_time;

defined( 'ABSPATH' ) || exit;

class Stats {
	/**
	 * Record a 404 event
	 */
	public static function record( string $url, string $ip, string $referrer, string $user_agent, bool $alerted = false ): int|false {
		global $wpdb;

		$table = $wpdb->prefix . '404_alert_log';

		// Rotate: keep only latest N records
		$options     = get_option( '404_alert_options', [] );
		$retention   = (int) ( $options['log_retention'] ?? 5000 );
		$total_count = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$table}" ) );

		if ( $total_count >= $retention ) {
			$excess = $total_count - $retention + 1;
			$wpdb->query( $wpdb->prepare( "DELETE FROM {$table} ORDER BY created_at ASC LIMIT %d", $excess ) );
		}

		// Insert
		return $wpdb->insert(
			$table,
			[
				'url'        => $url,
				'ip'         => $ip,
				'referrer'   => $referrer,
				'user_agent' => $user_agent,
				'alerted'    => $alerted ? 1 : 0,
				'created_at' => current_time( 'mysql' ),
			],
			[ '%s', '%s', '%s', '%s', '%d', '%s' ]
		);
	}

	/**
	 * Get top URLs
	 */
	public static function get_top_urls( int $limit = 10 ): array {
		global $wpdb;

		$table   = $wpdb->prefix . '404_alert_log';
		$results = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT url, COUNT(*) as count FROM {$table} GROUP BY url ORDER BY count DESC LIMIT %d",
				$limit
			)
		);

		return $results ?? [];
	}

	/**
	 * Get top IPs
	 */
	public static function get_top_ips( int $limit = 10 ): array {
		global $wpdb;

		$table   = $wpdb->prefix . '404_alert_log';
		$results = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT ip, COUNT(*) as count FROM {$table} WHERE ip != '' GROUP BY ip ORDER BY count DESC LIMIT %d",
				$limit
			)
		);

		return $results ?? [];
	}

	/**
	 * Get total 404 count
	 */
	public static function get_total_count(): int {
		global $wpdb;

		$table = $wpdb->prefix . '404_alert_log';
		return (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$table}" ) );
	}

	/**
	 * Export logs to CSV
	 */
	public static function export_csv(): string {
		global $wpdb;

		$table   = $wpdb->prefix . '404_alert_log';
		$results = $wpdb->get_results( $wpdb->prepare( "SELECT url, ip, referrer, user_agent, alerted, created_at FROM {$table} ORDER BY created_at DESC" ), ARRAY_A );

		if ( empty( $results ) ) {
			return '';
		}

		$fp = fopen( 'php://memory', 'r+' );
		if ( ! $fp ) {
			return '';
		}

		fputcsv( $fp, array_keys( $results[0] ) );

		foreach ( $results as $row ) {
			fputcsv( $fp, $row );
		}

		rewind( $fp );
		$csv = stream_get_contents( $fp );
		fclose( $fp );

		return $csv;
	}
}
