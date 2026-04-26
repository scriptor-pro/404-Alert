<?php
/**
 * Storage of 404 data for dashboard.
 *
 * @package Alert404
 */

defined( 'ABSPATH' ) || exit;

/**
 * Storage and retrieval of 404 data.
 */
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

		$charset_collate = $wpdb->get_charset_collate();
		$table_name      = $wpdb->prefix . '404_alert_stats';

		$sql = "CREATE TABLE {$table_name} (
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

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );
	}

	private static function migrate_legacy_option_storage(): void {
		global $wpdb;

		$legacy_stats = get_option( self::OPTION_KEY, array() );

		if ( ! is_array( $legacy_stats ) || empty( $legacy_stats ) ) {
			return;
		}

		foreach ( $legacy_stats as $record ) {
			if ( ! is_array( $record ) ) {
				continue;
			}

			$wpdb->insert(
				$wpdb->prefix . '404_alert_stats',
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

		$wpdb->insert(
			$wpdb->prefix . '404_alert_stats',
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

		// Invalider le cache après insertion
		self::invalidate_cache();
	}

	private static function enforce_max_records(): void {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Cleanup query with prepared statement
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table name via prefix constant is safe
		$wpdb->query(
			$wpdb->prepare(
				'DELETE FROM ' . $wpdb->prefix . '404_alert_stats
				 WHERE id NOT IN (
					SELECT id FROM (
						SELECT id FROM ' . $wpdb->prefix . '404_alert_stats
						ORDER BY created_at DESC, id DESC
						LIMIT %d
					) AS keep_ids
				)',
				self::MAX_RECORDS
			)
		);
	}

	public static function get_stats( int $limit = 100 ): array {
		global $wpdb;

		$limit = max( 1, $limit );

		// Vérifier le cache en premier
		$cache_key = 'alert404_stats_' . $limit;
		$cached    = wp_cache_get( $cache_key, '404_alert' );
		if ( false !== $cached ) {
			return $cached;
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Read query with prepared statement
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table name via prefix constant is safe
		$results = $wpdb->get_results(
			$wpdb->prepare(
				'SELECT id, url, ip, referrer, user_agent, user_agent_readable, created_at AS timestamp
				 FROM ' . $wpdb->prefix . '404_alert_stats
				 ORDER BY created_at DESC, id DESC
				 LIMIT %d',
				$limit
			),
			'ARRAY_A'
		);

		$results = is_array( $results ) ? $results : array();

		// Mettre en cache avec TTL de 5 minutes
		wp_cache_set( $cache_key, $results, '404_alert', 300 );

		return $results;
	}

	public static function get_stats_by_date( string $date ): array {
		global $wpdb;

		$like_date = $wpdb->esc_like( $date ) . '%';

		// Vérifier le cache en premier
		$cache_key = 'alert404_stats_by_date_' . $date;
		$cached    = wp_cache_get( $cache_key, '404_alert' );
		if ( false !== $cached ) {
			return $cached;
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Read query with prepared statement
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table name via prefix constant is safe
		$results = $wpdb->get_results(
			$wpdb->prepare(
				'SELECT id, url, ip, referrer, user_agent, user_agent_readable, created_at AS timestamp
				 FROM ' . $wpdb->prefix . '404_alert_stats
				 WHERE created_at LIKE %s
				 ORDER BY created_at DESC, id DESC',
				$like_date
			),
			'ARRAY_A'
		);

		$results = is_array( $results ) ? $results : array();

		// Mettre en cache avec TTL de 5 minutes
		wp_cache_set( $cache_key, $results, '404_alert', 300 );

		return $results;
	}

	public static function get_total_count(): int {
		global $wpdb;

		// Vérifier le cache en premier
		$cache_key = 'alert404_total_count';
		$cached    = wp_cache_get( $cache_key, '404_alert' );
		if ( false !== $cached ) {
			return $cached;
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Simple count query with prepared statement
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table name via prefix constant is safe
		$total = $wpdb->get_var( $wpdb->prepare( 'SELECT COUNT(*) FROM ' . $wpdb->prefix . '404_alert_stats' ) );

		$total = (int) $total;

		// Mettre en cache avec TTL de 5 minutes
		wp_cache_set( $cache_key, $total, '404_alert', 300 );

		return $total;
	}

	public static function get_unique_urls_count(): int {
		global $wpdb;

		// Vérifier le cache en premier
		$cache_key = 'alert404_unique_urls_count';
		$cached    = wp_cache_get( $cache_key, '404_alert' );
		if ( false !== $cached ) {
			return $cached;
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Simple count query with prepared statement
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table name via prefix constant is safe
		$total = $wpdb->get_var( $wpdb->prepare( 'SELECT COUNT(DISTINCT url) FROM ' . $wpdb->prefix . '404_alert_stats' ) );

		$total = (int) $total;

		// Mettre en cache avec TTL de 5 minutes
		wp_cache_set( $cache_key, $total, '404_alert', 300 );

		return $total;
	}

	public static function get_top_urls( int $limit = 10 ): array {
		global $wpdb;

		$limit = max( 1, $limit );

		// Vérifier le cache en premier
		$cache_key = 'alert404_top_urls_' . $limit;
		$cached    = wp_cache_get( $cache_key, '404_alert' );
		if ( false !== $cached ) {
			return $cached;
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Aggregation query with prepared statement
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table name via prefix constant is safe
		$rows = $wpdb->get_results(
			$wpdb->prepare(
				'SELECT url, COUNT(*) AS count
				 FROM ' . $wpdb->prefix . '404_alert_stats
				 GROUP BY url
				 ORDER BY count DESC
				 LIMIT %d',
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

		// Mettre en cache avec TTL de 5 minutes
		wp_cache_set( $cache_key, $result, '404_alert', 300 );

		return $result;
	}

	public static function get_top_ips( int $limit = 10 ): array {
		global $wpdb;

		$limit = max( 1, $limit );

		// Vérifier le cache en premier
		$cache_key = 'alert404_top_ips_' . $limit;
		$cached    = wp_cache_get( $cache_key, '404_alert' );
		if ( false !== $cached ) {
			return $cached;
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Aggregation query with prepared statement
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table name via prefix constant is safe
		$rows = $wpdb->get_results(
			$wpdb->prepare(
				'SELECT ip, COUNT(*) AS count
				 FROM ' . $wpdb->prefix . '404_alert_stats
				 GROUP BY ip
				 ORDER BY count DESC
				 LIMIT %d',
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

		// Mettre en cache avec TTL de 5 minutes
		wp_cache_set( $cache_key, $result, '404_alert', 300 );

		return $result;
	}

	/**
	 * Récupère les IPs récentes ayant généré des erreurs 404
	 *
	 * @param int $limit Nombre d'IPs à retourner.
	 * @return array Tableau associatif IP => nombre de 404.
	 */
	public static function get_recent_ips( int $limit = 10 ): array {
		return self::get_top_ips( $limit );
	}

	/**
	 * Récupère le nombre d'erreurs 404 pour une date donnée
	 *
	 * @param string $date Date au format YYYY-MM-DD.
	 * @return int Nombre d'erreurs 404 pour cette date.
	 */
	public static function get_count_for_date( string $date ): int {
		global $wpdb;

		$like_date = $wpdb->esc_like( $date ) . '%';

		// Vérifier le cache en premier
		$cache_key = 'alert404_count_for_date_' . $date;
		$cached    = wp_cache_get( $cache_key, '404_alert' );
		if ( false !== $cached ) {
			return $cached;
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Count query with prepared statement
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table name via prefix constant is safe
		$count = $wpdb->get_var(
			$wpdb->prepare(
				'SELECT COUNT(*) FROM ' . $wpdb->prefix . '404_alert_stats
				 WHERE created_at LIKE %s',
				$like_date
			)
		);

		$count = (int) $count;

		// Mettre en cache avec TTL de 5 minutes
		wp_cache_set( $cache_key, $count, '404_alert', 300 );

		return $count;
	}

	/**
	 * Récupère le nombre d'erreurs 404 par referrer
	 *
	 * @param int $limit Nombre de referrers à retourner.
	 * @return array Tableau associatif referrer => nombre de 404.
	 */
	public static function get_count_by_referrer( int $limit = 10 ): array {
		global $wpdb;

		$limit = max( 1, $limit );

		// Vérifier le cache en premier
		$cache_key = 'alert404_count_by_referrer_' . $limit;
		$cached    = wp_cache_get( $cache_key, '404_alert' );
		if ( false !== $cached ) {
			return $cached;
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Aggregation query with prepared statement
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table name via prefix constant is safe
		$rows = $wpdb->get_results(
			$wpdb->prepare(
				'SELECT referrer, COUNT(*) AS count
				 FROM ' . $wpdb->prefix . '404_alert_stats
				 GROUP BY referrer
				 ORDER BY count DESC
				 LIMIT %d',
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

			$result[ (string) ( $row['referrer'] ?? '' ) ] = (int) ( $row['count'] ?? 0 );
		}

		// Mettre en cache avec TTL de 5 minutes
		wp_cache_set( $cache_key, $result, '404_alert', 300 );

		return $result;
	}

	/**
	 * Alias pour clear_stats() pour compatibilité avec les tests
	 *
	 * @return void
	 */
	public static function clear_all_stats(): void {
		self::clear_stats();
	}

	public static function clear_stats(): void {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- TRUNCATE requires direct query
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table name via prefix constant is safe
		$wpdb->query( 'TRUNCATE TABLE ' . $wpdb->prefix . '404_alert_stats' );
		delete_option( self::OPTION_KEY );

		// Invalider tout le cache après suppression
		self::invalidate_cache();
	}

	/**
	 * Invalide tous les caches liés aux statistiques
	 *
	 * @return void
	 */
	private static function invalidate_cache(): void {
		if ( function_exists( 'wp_cache_flush_group' ) ) {
			wp_cache_flush_group( '404_alert' );
			return;
		}

		wp_cache_delete( 'alert404_total_count', '404_alert' );
		wp_cache_delete( 'alert404_unique_urls_count', '404_alert' );

		foreach ( array( 10, 100 ) as $limit ) {
			wp_cache_delete( 'alert404_stats_' . $limit, '404_alert' );
			wp_cache_delete( 'alert404_top_urls_' . $limit, '404_alert' );
			wp_cache_delete( 'alert404_top_ips_' . $limit, '404_alert' );
			wp_cache_delete( 'alert404_count_by_referrer_' . $limit, '404_alert' );
		}

		wp_cache_delete( 'alert404_stats_by_date_' . gmdate( 'Y-m-d' ), '404_alert' );
		wp_cache_delete( 'alert404_count_for_date_' . gmdate( 'Y-m-d' ), '404_alert' );
	}

	public static function export_csv(): void {
		$stats = self::get_stats( self::MAX_RECORDS );

		header( 'Content-Type: text/csv' );
		header( 'Content-Disposition: attachment; filename="404-stats-' . gmdate( 'Y-m-d' ) . '.csv"' );

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fopen -- Using php://output stream for direct browser download
		$output = fopen( 'php://output', 'w' );
		if ( false === $output ) {
			return;
		}

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fputcsv -- Using fputcsv for CSV stream output
		fputcsv( $output, array( 'ID', 'URL', 'IP', 'Referrer', 'User Agent', 'Timestamp' ) );

		foreach ( $stats as $record ) {
			// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fputcsv -- Using fputcsv for CSV stream output
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

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose -- Closing php://output stream
		fclose( $output );
		exit;
	}
}
