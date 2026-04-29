<?php
/**
 * DEPRECATED: Backward compatibility wrapper for Alert404_Stats.
 *
 * @package Alert404
 * @deprecated Use Alert404_Stats instead.
 */

defined( 'ABSPATH' ) || exit;

/**
 * Backward compatibility wrapper for Alert404_Stats.
 * All methods delegate to Alert404_Stats.
 *
 * @deprecated Use Alert404_Stats instead.
 */
class Alert404_Storage {

	public static function init(): void {
		// Alert404_Stats creates table on demand, no init needed
	}

	public static function ensure_storage_ready(): void {
		// Alert404_Stats creates table on demand
	}

	public static function record_404( string $to, string $subject, array $payload ): void {
		unset( $to, $subject );

		// Statistics are now mandatory - always record
		Alert404_Stats::record( $payload );
	}

	public static function get_stats( int $limit = 100 ): array {
		$records = Alert404_Stats::get_recent( $limit );

		foreach ( $records as &$record ) {
			$record['timestamp'] = $record['created_at'];
			unset( $record['created_at'] );
		}

		return $records;
	}

	public static function get_stats_by_date( string $date ): array {
		return Alert404_Stats::get_recent( 1000 );
	}

	public static function get_total_count(): int {
		return Alert404_Stats::get_total_count();
	}

	public static function get_unique_urls_count(): int {
		return Alert404_Stats::get_unique_urls_count();
	}

	public static function get_top_urls( int $limit = 10 ): array {
		return Alert404_Stats::get_top_urls( $limit );
	}

	public static function get_top_ips( int $limit = 10 ): array {
		return Alert404_Stats::get_top_ips( $limit );
	}

	public static function get_recent_ips( int $limit = 10 ): array {
		return Alert404_Stats::get_top_ips( $limit );
	}

	public static function get_count_for_date( string $date ): int {
		return Alert404_Stats::get_count_for_date( $date );
	}

	public static function get_count_by_referrer( int $limit = 10 ): array {
		return Alert404_Stats::get_count_by_referrer( $limit );
	}

	public static function clear_all_stats(): void {
		Alert404_Stats::clear();
	}

	public static function clear_stats(): void {
		Alert404_Stats::clear();
	}

	public static function export_csv(): void {
		Alert404_Stats::export_csv();
	}
}
