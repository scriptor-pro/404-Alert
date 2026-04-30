<?php
/**
 * Admin dashboard (statistics page)
 *
 * @package Alert404
 */

namespace Alert404;

use function add_submenu_page;
use function add_action;
use function current_user_can;
use function wp_enqueue_style;
use function plugin_dir_url;
use function esc_html;
use function esc_url;
use function admin_url;
use function gmdate;
use function wp_die;

defined( 'ABSPATH' ) || exit;

class Dashboard {
	public function __construct() {
		add_action( 'admin_menu', [ $this, 'add_menu' ] );
		add_action( 'admin_init', [ $this, 'handle_export_csv' ] );
	}

	/**
	 * Add submenu page
	 */
	public function add_menu(): void {
		add_submenu_page(
			'404-alert-settings',
			'404 Statistics',
			'Statistics',
			'manage_options',
			'404-alert-dashboard',
			[ $this, 'render_dashboard' ]
		);
	}

	/**
	 * Handle CSV export
	 */
	public function handle_export_csv(): void {
		if ( ! isset( $_GET['action'] ) || '404_alert_export_csv' !== $_GET['action'] ) {
			return;
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( 'Unauthorized' );
		}

		header( 'Content-Type: text/csv' );
		header( 'Content-Disposition: attachment; filename="404-errors-' . gmdate( 'Y-m-d' ) . '.csv"' );

		global $wpdb;
		$table   = $wpdb->prefix . '404_alert_log';
		$results = $wpdb->get_results( "SELECT url, ip, referrer, user_agent, alerted, created_at FROM $table ORDER BY created_at DESC", ARRAY_A );

		if ( empty( $results ) ) {
			exit;
		}

		$output = fopen( 'php://output', 'w' );
		fputcsv( $output, array_keys( $results[0] ) );

		foreach ( $results as $row ) {
			fputcsv( $output, $row );
		}

		fclose( $output );
		exit;
	}

	/**
	 * Render dashboard
	 */
	public function render_dashboard(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( 'Unauthorized' );
		}

		$total_count = Stats::get_total_count();
		$top_urls    = Stats::get_top_urls( 10 );
		$top_ips     = Stats::get_top_ips( 10 );

		?>
		<div class="wrap">
			<h1>404 Statistics</h1>

			<div class="card" style="padding: 20px; margin: 20px 0;">
				<h2 style="margin-top: 0;">Summary</h2>
				<p>
					<strong>Total 404 Errors Logged:</strong> <?php echo esc_html( $total_count ); ?>
				</p>
				<p>
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=404-alert-dashboard&action=404_alert_export_csv' ) ); ?>" class="button">
						📥 Export to CSV
					</a>
				</p>
			</div>

			<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
				<div class="card" style="padding: 20px;">
					<h3>Top 10 URLs</h3>
					<?php if ( ! empty( $top_urls ) ): ?>
						<table class="wp-list-table widefat">
							<thead>
								<tr>
									<th>URL</th>
									<th>Count</th>
								</tr>
							</thead>
							<tbody>
								<?php foreach ( $top_urls as $entry ): ?>
									<tr>
										<td><code><?php echo esc_html( $entry->url ); ?></code></td>
										<td><?php echo esc_html( $entry->count ); ?></td>
									</tr>
								<?php endforeach; ?>
							</tbody>
						</table>
					<?php else: ?>
						<p>No data available.</p>
					<?php endif; ?>
				</div>

				<div class="card" style="padding: 20px;">
					<h3>Top 10 IPs</h3>
					<?php if ( ! empty( $top_ips ) ): ?>
						<table class="wp-list-table widefat">
							<thead>
								<tr>
									<th>IP Address</th>
									<th>Count</th>
								</tr>
							</thead>
							<tbody>
								<?php foreach ( $top_ips as $entry ): ?>
									<tr>
										<td><code><?php echo esc_html( $entry->ip ); ?></code></td>
										<td><?php echo esc_html( $entry->count ); ?></td>
									</tr>
								<?php endforeach; ?>
							</tbody>
						</table>
					<?php else: ?>
						<p>No data available.</p>
					<?php endif; ?>
				</div>
			</div>
		</div>
		<?php
	}
}
