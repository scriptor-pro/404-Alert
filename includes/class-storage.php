<?php
/**
 * Stockage des données 404 pour le dashboard
 */

defined( 'ABSPATH' ) || exit;

class Alert404_Storage {
	private const OPTION_KEY           = '404_alert_stats';
	private const MAX_RECORDS          = 1000;
	private const SCHEMA_VERSION       = '1';
	private const SCHEMA_OPTION_KEY    = '404_alert_stats_schema_version';
	private const MIGRATION_OPTION_KEY = '404_alert_stats_migrated';

	public static function init(): void {
		self::ensure_storage_ready();
		add_action( '404_alert_email_sent', array( self::class, 'record_404' ), 10, 3 );
	}

	private static function ensure_storage_ready(): void {
		$current_version = (string) get_option( self::SCHEMA_OPTION_KEY, '' );

		if ( self::SCHEMA_VERSION !== $current_version ) {
			self::create_or_update_table();
			update_option( self::SCHEMA_OPTION_KEY, self::SCHEMA_VERSION, false );
		}

		if ( ! get_option( self::MIGRATION_OPTION_KEY, false ) ) {
			self::migrate_legacy_option_storage();
			update_option( self::MIGRATION_OPTION_KEY, 1, false );
		}
	}

	private static function get_table_name(): string {
		global $wpdb;
		return $wpdb->prefix . '404_alert_stats';
	}

	private static function create_or_update_table(): void {
		global $wpdb;

		$table_name      = self::get_table_name();
		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE IF NOT EXISTS {$table_name} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			url text NOT NULL,
			ip varchar(45) NOT NULL,
			referrer text NOT NULL,
			user_agent text NOT NULL,
			user_agent_readable text NOT NULL,
			created_at datetime NOT NULL,
			PRIMARY KEY  (id),
			KEY created_at (created_at),
			KEY ip (ip)
		) {$charset_collate};";

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Schema creation requires direct query
		$wpdb->query( $sql );
	}

	private static function migrate_legacy_option_storage(): void {
		global $wpdb;

		$legacy_stats = get_option( self::OPTION_KEY, array() );

		if ( ! is_array( $legacy_stats ) || empty( $legacy_stats ) ) {
			return;
		}

		$table_name = self::get_table_name();

		foreach ( $legacy_stats as $record ) {
			if ( ! is_array( $record ) ) {
				continue;
			}

			$wpdb->insert(
				$table_name,
				array(
					'url'                 => sanitize_text_field( (string) ( $record['url'] ?? '' ) ),
					'ip'                  => sanitize_text_field( (string) ( $record['ip'] ?? '' ) ),
					'referrer'            => sanitize_text_field( (string) ( $record['referrer'] ?? '' ) ),
					'user_agent'          => sanitize_text_field( (string) ( $record['user_agent'] ?? '' ) ),
					'user_agent_readable' => sanitize_text_field( (string) ( $record['user_agent_readable'] ?? '' ) ),
					'created_at'          => sanitize_text_field( (string) ( $record['timestamp'] ?? current_time( 'mysql' ) ) ),
				),
				array( '%s', '%s', '%s', '%s', '%s', '%s' )
			);
		}

		delete_option( self::OPTION_KEY );
		self::enforce_max_records();
	}

	public static function record_404( string $to, string $subject, array $payload ): void {
		global $wpdb;

		unset( $to, $subject );

		$options = get_option( '404_alert_options', array() );

		if ( empty( $options['enable_stats'] ) ) {
			return;
		}

		$table_name = self::get_table_name();

		$wpdb->insert(
			$table_name,
			array(
				'url'                 => sanitize_text_field( (string) ( $payload['url'] ?? $payload['full_url'] ?? 'unknown' ) ),
				'ip'                  => sanitize_text_field( (string) ( $payload['ip'] ?? 'unknown' ) ),
				'referrer'            => sanitize_text_field( (string) ( $payload['referrer'] ?? '' ) ),
				'user_agent'          => sanitize_text_field( (string) ( $payload['user_agent'] ?? '' ) ),
				'user_agent_readable' => sanitize_text_field( (string) ( $payload['user_readable'] ?? '' ) ),
				'created_at'          => current_time( 'mysql' ),
			),
			array( '%s', '%s', '%s', '%s', '%s', '%s' )
		);

		self::enforce_max_records();
	}

	private static function enforce_max_records(): void {
		global $wpdb;

		$table_name = self::get_table_name();

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Cleanup query with prepared statement
		$wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$table_name}
				 WHERE id NOT IN (
					SELECT id FROM (
						SELECT id FROM {$table_name}
						ORDER BY created_at DESC, id DESC
						LIMIT %d
					) AS keep_ids
				)",
				self::MAX_RECORDS
			)
		);
	}

	public static function get_stats( int $limit = 100 ): array {
		global $wpdb;

		$table_name = self::get_table_name();
		$limit      = max( 1, $limit );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Read query with prepared statement
		$results = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT id, url, ip, referrer, user_agent, user_agent_readable, created_at AS timestamp
				 FROM {$table_name}
				 ORDER BY created_at DESC, id DESC
				 LIMIT %d",
				$limit
			),
			'ARRAY_A'
		);

		return is_array( $results ) ? $results : array();
	}

	public static function get_stats_by_date( string $date ): array {
		global $wpdb;

		$table_name = self::get_table_name();
		$like_date  = $wpdb->esc_like( $date ) . '%';

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Read query with prepared statement
		$results = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT id, url, ip, referrer, user_agent, user_agent_readable, created_at AS timestamp
				 FROM {$table_name}
				 WHERE created_at LIKE %s
				 ORDER BY created_at DESC, id DESC",
				$like_date
			),
			'ARRAY_A'
		);

		return is_array( $results ) ? $results : array();
	}

	public static function get_total_count(): int {
		global $wpdb;

		$table_name = self::get_table_name();
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Simple count query
		$total = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$table_name}" ) );

		return (int) $total;
	}

	public static function get_unique_urls_count(): int {
		global $wpdb;

		$table_name = self::get_table_name();
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Simple count query
		$total = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(DISTINCT url) FROM {$table_name}" ) );

		return (int) $total;
	}

	public static function get_top_urls( int $limit = 10 ): array {
		global $wpdb;

		$table_name = self::get_table_name();
		$limit      = max( 1, $limit );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Aggregation query with prepared statement
		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT url, COUNT(*) AS count
				 FROM {$table_name}
				 GROUP BY url
				 ORDER BY count DESC
				 LIMIT %d",
				$limit
			),
			'ARRAY_A'
		);

		if ( ! is_array( $rows ) ) {
			return array();
		}

		$result = array();

		foreach ( $rows as $row ) {
			if ( ! is_array( $row ) ) {
				continue;
			}

			$result[ (string) ( $row['url'] ?? '' ) ] = (int) ( $row['count'] ?? 0 );
		}

		return $result;
	}

	public static function get_top_ips( int $limit = 10 ): array {
		global $wpdb;

		$table_name = self::get_table_name();
		$limit      = max( 1, $limit );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Aggregation query with prepared statement
		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT ip, COUNT(*) AS count
				 FROM {$table_name}
				 GROUP BY ip
				 ORDER BY count DESC
				 LIMIT %d",
				$limit
			),
			'ARRAY_A'
		);

		if ( ! is_array( $rows ) ) {
			return array();
		}

		$result = array();

		foreach ( $rows as $row ) {
			if ( ! is_array( $row ) ) {
				continue;
			}

			$result[ (string) ( $row['ip'] ?? '' ) ] = (int) ( $row['count'] ?? 0 );
		}

		return $result;
	}

	public static function clear_stats(): void {
		global $wpdb;

		$table_name = self::get_table_name();
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- TRUNCATE requires direct query
		$wpdb->query( "TRUNCATE TABLE {$table_name}" );
		delete_option( self::OPTION_KEY );
	}

	public static function export_csv(): void {
		$stats = self::get_stats( self::MAX_RECORDS );

		header( 'Content-Type: text/csv' );
		header( 'Content-Disposition: attachment; filename="404-stats-' . gmdate( 'Y-m-d' ) . '.csv"' );

		$output = fopen( 'php://output', 'w' );
		if ( false === $output ) {
			return;
		}

		fputcsv( $output, array( 'ID', 'URL', 'IP', 'Referrer', 'User Agent', 'Timestamp' ) );

		foreach ( $stats as $record ) {
			fputcsv(
				$output,
				array(
					$record['id'] ?? '',
					$record['url'] ?? '',
					$record['ip'] ?? '',
					$record['referrer'] ?? '',
					$record['user_agent'] ?? '',
					$record['timestamp'] ?? '',
				)
			);
		}

		fclose( $output );
		exit;
	}
}
