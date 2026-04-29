<?php
/**
 * Robust 404 statistics tracking and retrieval.
 *
 * @package Alert404
 */

defined( 'ABSPATH' ) || exit;

/**
 * 404 Statistics management with data validation, error handling, and caching.
 */
class Alert404_Stats {
	private const TABLE_NAME = '404_alert_stats';
	private const MAX_RECORDS = 1000;
	private const SCHEMA_VERSION = '2';
	private const SCHEMA_OPTION_KEY = '404_alert_stats_schema_v2';
	private const CACHE_GROUP = '404_alert_stats';
	private const CACHE_TTL = 300;

	/**
	 * Record a 404 error event.
	 *
	 * @param array $event {
	 *   @type string $url         Requested URL (required).
	 *   @type string $ip          Source IP address (required).
	 *   @type string $referrer    HTTP Referrer (optional).
	 *   @type string $user_agent  User-Agent string (optional).
	 *   @type string $user_readable Parsed user-agent (optional).
	 * }
	 * @return bool True if recorded successfully, false otherwise.
	 */
	public static function record( array $event ): bool {
		try {
			self::ensure_table_exists();

			$validated = self::validate_event( $event );
			if ( ! $validated ) {
				return false;
			}

			return self::insert_record( $validated );
		} catch ( Throwable $e ) {
			Alert404_Logger::log_stats_error(
				'Record 404 failed',
				$e->getMessage()
			);
			return false;
		}
	}

	/**
	 * Get recent 404 records.
	 *
	 * @param int $limit Number of records to retrieve (1-1000).
	 * @return array List of 404 records.
	 */
	public static function get_recent( int $limit = 100 ): array {
		$limit = self::validate_limit( $limit );
		$cache_key = "recent_{$limit}";

		$cached = self::get_cache( $cache_key );
		if ( null !== $cached ) {
			return $cached;
		}

		$records = self::query_recent( $limit );
		self::set_cache( $cache_key, $records );

		return $records;
	}

	/**
	 * Get total 404 count.
	 *
	 * @return int Total number of 404s recorded.
	 */
	public static function get_total_count(): int {
		$cache_key = 'total_count';

		$cached = self::get_cache( $cache_key );
		if ( null !== $cached ) {
			return $cached;
		}

		try {
			global $wpdb;
			$table = self::get_table_name();

			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$count = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table}" );

			self::set_cache( $cache_key, $count );
			return $count;
		} catch ( Throwable $e ) {
			Alert404_Logger::log_stats_error( 'Get total count failed', $e->getMessage() );
			return 0;
		}
	}

	/**
	 * Get unique URLs count.
	 *
	 * @return int Number of unique 404 URLs.
	 */
	public static function get_unique_urls_count(): int {
		$cache_key = 'unique_urls_count';

		$cached = self::get_cache( $cache_key );
		if ( null !== $cached ) {
			return $cached;
		}

		try {
			global $wpdb;
			$table = self::get_table_name();

			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$count = (int) $wpdb->get_var( "SELECT COUNT(DISTINCT url) FROM {$table}" );

			self::set_cache( $cache_key, $count );
			return $count;
		} catch ( Throwable $e ) {
			Alert404_Logger::log_stats_error( 'Get unique URLs count failed', $e->getMessage() );
			return 0;
		}
	}

	/**
	 * Get top accessed URLs.
	 *
	 * @param int $limit Number of URLs to return (1-100).
	 * @return array Associative array of URL => count.
	 */
	public static function get_top_urls( int $limit = 10 ): array {
		$limit = self::validate_limit( $limit, 1, 100 );
		$cache_key = "top_urls_{$limit}";

		$cached = self::get_cache( $cache_key );
		if ( null !== $cached ) {
			return $cached;
		}

		try {
			global $wpdb;
			$table = self::get_table_name();

			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$results = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT url, COUNT(*) AS count FROM {$table} GROUP BY url ORDER BY count DESC LIMIT %d",
					$limit
				),
				ARRAY_A
			);

			$urls = array();
			if ( is_array( $results ) ) {
				foreach ( $results as $row ) {
					if ( is_array( $row ) && isset( $row['url'], $row['count'] ) ) {
						$urls[ (string) $row['url'] ] = (int) $row['count'];
					}
				}
			}

			self::set_cache( $cache_key, $urls );
			return $urls;
		} catch ( Throwable $e ) {
			Alert404_Logger::log_stats_error( 'Get top URLs failed', $e->getMessage() );
			return array();
		}
	}

	/**
	 * Get top source IPs.
	 *
	 * @param int $limit Number of IPs to return (1-100).
	 * @return array Associative array of IP => count.
	 */
	public static function get_top_ips( int $limit = 10 ): array {
		$limit = self::validate_limit( $limit, 1, 100 );
		$cache_key = "top_ips_{$limit}";

		$cached = self::get_cache( $cache_key );
		if ( null !== $cached ) {
			return $cached;
		}

		try {
			global $wpdb;
			$table = self::get_table_name();

			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$results = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT ip, COUNT(*) AS count FROM {$table} GROUP BY ip ORDER BY count DESC LIMIT %d",
					$limit
				),
				ARRAY_A
			);

			$ips = array();
			if ( is_array( $results ) ) {
				foreach ( $results as $row ) {
					if ( is_array( $row ) && isset( $row['ip'], $row['count'] ) ) {
						$ips[ (string) $row['ip'] ] = (int) $row['count'];
					}
				}
			}

			self::set_cache( $cache_key, $ips );
			return $ips;
		} catch ( Throwable $e ) {
			Alert404_Logger::log_stats_error( 'Get top IPs failed', $e->getMessage() );
			return array();
		}
	}

	/**
	 * Get 404 count for a specific date.
	 *
	 * @param string $date Date in YYYY-MM-DD format.
	 * @return int Count of 404s on that date.
	 */
	public static function get_count_for_date( string $date ): int {
		if ( ! self::validate_date_format( $date ) ) {
			return 0;
		}

		$cache_key = "count_date_{$date}";
		$cached = self::get_cache( $cache_key );
		if ( null !== $cached ) {
			return $cached;
		}

		try {
			global $wpdb;
			$table = self::get_table_name();

			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$count = (int) $wpdb->get_var(
				$wpdb->prepare(
					"SELECT COUNT(*) FROM {$table} WHERE DATE(created_at) = %s",
					$date
				)
			);

			self::set_cache( $cache_key, $count );
			return $count;
		} catch ( Throwable $e ) {
			Alert404_Logger::log_stats_error( 'Get count for date failed', $e->getMessage() );
			return 0;
		}
	}

	/**
	 * Get 404 count by referrer.
	 *
	 * @param int $limit Number of referrers to return (1-100).
	 * @return array Associative array of referrer => count.
	 */
	public static function get_count_by_referrer( int $limit = 10 ): array {
		$limit = self::validate_limit( $limit, 1, 100 );
		$cache_key = "count_referrer_{$limit}";

		$cached = self::get_cache( $cache_key );
		if ( null !== $cached ) {
			return $cached;
		}

		try {
			global $wpdb;
			$table = self::get_table_name();

			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$results = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT referrer, COUNT(*) AS count FROM {$table} WHERE referrer != '' GROUP BY referrer ORDER BY count DESC LIMIT %d",
					$limit
				),
				ARRAY_A
			);

			$referrers = array();
			if ( is_array( $results ) ) {
				foreach ( $results as $row ) {
					if ( is_array( $row ) && isset( $row['referrer'], $row['count'] ) ) {
						$referrers[ (string) $row['referrer'] ] = (int) $row['count'];
					}
				}
			}

			self::set_cache( $cache_key, $referrers );
			return $referrers;
		} catch ( Throwable $e ) {
			Alert404_Logger::log_stats_error( 'Get count by referrer failed', $e->getMessage() );
			return array();
		}
	}

	/**
	 * Clear all statistics.
	 *
	 * @return bool True if cleared successfully, false otherwise.
	 */
	public static function clear(): bool {
		try {
			global $wpdb;
			$table = self::get_table_name();

			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->query( "TRUNCATE TABLE {$table}" );

			self::clear_all_cache();
			return true;
		} catch ( Throwable $e ) {
			Alert404_Logger::log_stats_error( 'Clear stats failed', $e->getMessage() );
			return false;
		}
	}

	/**
	 * Export statistics as CSV.
	 *
	 * @return void
	 */
	public static function export_csv(): void {
		try {
			$records = self::get_recent( self::MAX_RECORDS );

			header( 'Content-Type: text/csv; charset=utf-8' );
			header( 'Content-Disposition: attachment; filename="404-stats-' . gmdate( 'Y-m-d-His' ) . '.csv"' );

			// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fopen
			$output = fopen( 'php://output', 'w' );
			if ( false === $output ) {
				wp_die( 'Failed to create CSV export.' );
			}

			// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fputcsv
			fputcsv( $output, array( 'ID', 'URL', 'IP', 'Referrer', 'User Agent', 'Timestamp' ) );

			foreach ( $records as $record ) {
				// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fputcsv
				fputcsv(
					$output,
					array(
						$record['id'] ?? '',
						$record['url'] ?? '',
						$record['ip'] ?? '',
						$record['referrer'] ?? '',
						$record['user_agent'] ?? '',
						$record['created_at'] ?? '',
					)
				);
			}

			// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose
			fclose( $output );
			exit;
		} catch ( Throwable $e ) {
			Alert404_Logger::log_stats_error( 'CSV export failed', $e->getMessage() );
			wp_die( 'Failed to export statistics.' );
		}
	}

	/**
	 * Ensure the database table exists.
	 *
	 * @return void
	 */
	private static function ensure_table_exists(): void {
		$current_version = (string) get_option( self::SCHEMA_OPTION_KEY, '' );

		if ( self::SCHEMA_VERSION === $current_version ) {
			return;
		}

		self::create_table();
		update_option( self::SCHEMA_OPTION_KEY, self::SCHEMA_VERSION, false );
	}

	/**
	 * Create the database table.
	 *
	 * @return void
	 */
	private static function create_table(): void {
		global $wpdb;

		$table_name = self::get_table_name();
		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE IF NOT EXISTS {$table_name} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			url text NOT NULL,
			ip varchar(45) NOT NULL,
			referrer text NOT NULL DEFAULT '',
			user_agent text NOT NULL DEFAULT '',
			user_agent_readable text NOT NULL DEFAULT '',
			created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			KEY idx_created_at (created_at),
			KEY idx_ip (ip),
			KEY idx_url (url(100))
		) {$charset_collate};";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );

		if ( ! empty( $wpdb->last_error ) ) {
			Alert404_Logger::log_stats_error(
				'Table creation failed',
				'Table: ' . $table_name . ' | Error: ' . $wpdb->last_error
			);
		}
	}

	/**
	 * Validate and normalize an event.
	 *
	 * @param array $event Event data to validate.
	 * @return array|false Validated event or false if invalid.
	 */
	private static function validate_event( array $event ) {
		$url = (string) ( $event['url'] ?? $event['full_url'] ?? '' );
		$ip = (string) ( $event['ip'] ?? '' );

		if ( empty( $url ) || empty( $ip ) ) {
			Alert404_Logger::log_stats_error(
				'Invalid event data',
				'Missing required fields: url=' . ( $url ? 'OK' : 'EMPTY' ) . ', ip=' . ( $ip ? 'OK' : 'EMPTY' )
			);
			return false;
		}

		if ( strlen( $url ) > 2000 ) {
			$url = substr( $url, 0, 2000 );
		}

		return array(
			'url' => sanitize_url( $url ),
			'ip' => sanitize_text_field( $ip ),
			'referrer' => sanitize_url( (string) ( $event['referrer'] ?? '' ) ),
			'user_agent' => sanitize_text_field( (string) ( $event['user_agent'] ?? '' ) ),
			'user_agent_readable' => sanitize_text_field( (string) ( $event['user_readable'] ?? '' ) ),
		);
	}

	/**
	 * Insert a record into the database.
	 *
	 * @param array $validated Validated event data.
	 * @return bool True if inserted successfully, false otherwise.
	 */
	private static function insert_record( array $validated ): bool {
		global $wpdb;

		try {
			self::ensure_table_exists();

			$table = self::get_table_name();
			$result = $wpdb->insert(
				$table,
				array(
					'url' => $validated['url'],
					'ip' => $validated['ip'],
					'referrer' => $validated['referrer'],
					'user_agent' => $validated['user_agent'],
					'user_agent_readable' => $validated['user_agent_readable'],
					'created_at' => current_time( 'mysql' ),
				),
				array( '%s', '%s', '%s', '%s', '%s', '%s' )
			);

			if ( false === $result ) {
				Alert404_Logger::log_stats_error(
					'Insert failed',
					'Error: ' . $wpdb->last_error
				);
				return false;
			}

			self::enforce_max_records();
			self::clear_all_cache();

			return true;
		} catch ( Throwable $e ) {
			Alert404_Logger::log_stats_error( 'Insert exception', $e->getMessage() );
			return false;
		}
	}

	/**
	 * Query recent records.
	 *
	 * @param int $limit Number of records to retrieve.
	 * @return array List of records.
	 */
	private static function query_recent( int $limit ): array {
		global $wpdb;
		$table = self::get_table_name();

		try {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$results = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT id, url, ip, referrer, user_agent, user_agent_readable, created_at FROM {$table} ORDER BY id DESC LIMIT %d",
					$limit
				),
				ARRAY_A
			);

			$records = array();
			if ( is_array( $results ) ) {
				foreach ( $results as $row ) {
					if ( is_array( $row ) ) {
						$records[] = array(
							'id' => (int) ( $row['id'] ?? 0 ),
							'url' => (string) ( $row['url'] ?? '' ),
							'ip' => (string) ( $row['ip'] ?? '' ),
							'referrer' => (string) ( $row['referrer'] ?? '' ),
							'user_agent' => (string) ( $row['user_agent'] ?? '' ),
							'user_agent_readable' => (string) ( $row['user_agent_readable'] ?? '' ),
							'created_at' => (string) ( $row['created_at'] ?? '' ),
						);
					}
				}
			}

			return $records;
		} catch ( Throwable $e ) {
			Alert404_Logger::log_stats_error( 'Query recent failed', $e->getMessage() );
			return array();
		}
	}

	/**
	 * Enforce maximum record limit.
	 *
	 * @return void
	 */
	private static function enforce_max_records(): void {
		global $wpdb;
		$table = self::get_table_name();

		try {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$wpdb->query(
				$wpdb->prepare(
					"DELETE FROM {$table} WHERE id NOT IN (SELECT id FROM (SELECT id FROM {$table} ORDER BY id DESC LIMIT %d) AS keep_ids)",
					self::MAX_RECORDS
				)
			);
		} catch ( Throwable $e ) {
			Alert404_Logger::log_stats_error( 'Enforce max records failed', $e->getMessage() );
		}
	}

	/**
	 * Validate date format.
	 *
	 * @param string $date Date string to validate.
	 * @return bool True if valid YYYY-MM-DD format.
	 */
	private static function validate_date_format( string $date ): bool {
		return (bool) preg_match( '/^\d{4}-\d{2}-\d{2}$/', $date );
	}

	/**
	 * Validate and constrain limit parameter.
	 *
	 * @param int $limit Limit value.
	 * @param int $min Minimum value.
	 * @param int $max Maximum value.
	 * @return int Validated limit.
	 */
	private static function validate_limit( int $limit, int $min = 1, int $max = 1000 ): int {
		return max( $min, min( $max, $limit ) );
	}

	/**
	 * Get table name with prefix.
	 *
	 * @return string Table name.
	 */
	private static function get_table_name(): string {
		global $wpdb;
		return $wpdb->prefix . self::TABLE_NAME;
	}

	/**
	 * Get value from cache.
	 *
	 * @param string $key Cache key.
	 * @return mixed Cached value or null if not found.
	 */
	private static function get_cache( string $key ) {
		$cached = wp_cache_get( $key, self::CACHE_GROUP );
		return false === $cached ? null : $cached;
	}

	/**
	 * Set value in cache.
	 *
	 * @param string $key Cache key.
	 * @param mixed  $value Value to cache.
	 * @return void
	 */
	private static function set_cache( string $key, $value ): void {
		wp_cache_set( $key, $value, self::CACHE_GROUP, self::CACHE_TTL );
	}

	/**
	 * Clear all stats cache.
	 *
	 * @return void
	 */
	private static function clear_all_cache(): void {
		if ( function_exists( 'wp_cache_flush_group' ) ) {
			wp_cache_flush_group( self::CACHE_GROUP );
		} else {
			wp_cache_delete( 'recent_100', self::CACHE_GROUP );
			wp_cache_delete( 'total_count', self::CACHE_GROUP );
			wp_cache_delete( 'unique_urls_count', self::CACHE_GROUP );
			for ( $i = 1; $i <= 100; $i++ ) {
				wp_cache_delete( "top_urls_{$i}", self::CACHE_GROUP );
				wp_cache_delete( "top_ips_{$i}", self::CACHE_GROUP );
				wp_cache_delete( "count_referrer_{$i}", self::CACHE_GROUP );
			}
		}
	}
}
