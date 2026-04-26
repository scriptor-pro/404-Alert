<?php
/**
 * Dashboard for 404 Alert statistics.
 *
 * @package Alert404
 */

defined( 'ABSPATH' ) || exit;

/**
 * Dashboard for 404 Alert statistics.
 */
class Alert404_Dashboard {
	public static function init(): void {
		if ( ! class_exists( 'Alert404_Storage' ) ) {
			return;
		}

		add_action( 'admin_menu', array( self::class, 'add_menu' ) );
	}

	public static function add_menu(): void {
		add_submenu_page(
			'404_alert',
			'Statistiques 404',
			'Statistiques',
			'manage_options',
			'404_alert_stats',
			array( self::class, 'render_page' )
		);
	}

	public static function render_page(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( 'Accès refusé' );
		}

		$action = isset( $_GET['action'] ) ? sanitize_text_field( wp_unslash( $_GET['action'] ) ) : 'list';

		if ( 'export' === $action && check_admin_referer( '404_alert_export' ) ) {
			Alert404_Storage::export_csv();
		}

		if ( 'clear' === $action && check_admin_referer( '404_alert_clear' ) ) {
			Alert404_Storage::clear_stats();
			echo '<div class="notice notice-success"><p>Statistiques effacées.</p></div>';
		}

		$stats       = Alert404_Storage::get_stats( 100 );
		$total       = Alert404_Storage::get_total_count();
		$unique_urls = Alert404_Storage::get_unique_urls_count();
		$top_urls    = Alert404_Storage::get_top_urls( 5 );
		$top_ips     = Alert404_Storage::get_top_ips( 5 );

		if ( 0 === $total ) {
			?>
			<div class="wrap">
				<h1>404 Alert — Statistiques</h1>
				<div class="notice notice-info">
					<p>Aucune donnée statistique. Activez les statistiques dans les paramètres.</p>
				</div>
			</div>
			<?php
			return;
		}

		?>
		<div class="wrap">
			<h1>404 Alert — Statistiques</h1>

			<!-- Cartes de résumé -->
			<div style="display: flex; gap: 20px; margin-bottom: 30px;">
				<div style="flex: 1; background: #fff; padding: 20px; border: 1px solid #ddd; border-radius: 8px;">
					<h3 style="margin: 0 0 10px 0; color: #666;">Total 404</h3>
					<p style="font-size: 36px; margin: 0; font-weight: bold;"><?php echo esc_html( $total ); ?></p>
				</div>
				<div style="flex: 1; background: #fff; padding: 20px; border: 1px solid #ddd; border-radius: 8px;">
					<h3 style="margin: 0 0 10px 0; color: #666;">URLs uniques</h3>
					<p style="font-size: 36px; margin: 0; font-weight: bold;"><?php echo esc_html( $unique_urls ); ?></p>
				</div>
			</div>

			<!-- Top URLs et IPs -->
			<div style="display: flex; gap: 20px; margin-bottom: 30px;">
				<div style="flex: 1; background: #fff; padding: 20px; border: 1px solid #ddd; border-radius: 8px;">
					<h3 style="margin: 0 0 15px 0;">URLs les plus frequentes</h3>
					<table class="widefat" cellspacing="0">
						<thead>
							<tr>
								<th>URL</th>
								<th style="text-align: right;">Nombre</th>
							</tr>
						</thead>
						<tbody>
							<?php foreach ( $top_urls as $url => $count ) : ?>
								<tr>
									<td><?php echo esc_html( mb_strimwidth( $url, 0, 60, '...' ) ); ?></td>
									<td style="text-align: right;"><strong><?php echo esc_html( $count ); ?></strong></td>
								</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
				</div>

				<div style="flex: 1; background: #fff; padding: 20px; border: 1px solid #ddd; border-radius: 8px;">
					<h3 style="margin: 0 0 15px 0;">🌐 IPs les plus actives</h3>
					<table class="widefat" cellspacing="0">
						<thead>
							<tr>
								<th>IP</th>
								<th style="text-align: right;">Nombre</th>
							</tr>
						</thead>
						<tbody>
							<?php foreach ( $top_ips as $ip => $count ) : ?>
								<tr>
									<td><?php echo esc_html( $ip ); ?></td>
									<td style="text-align: right;"><strong><?php echo esc_html( $count ); ?></strong></td>
								</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
				</div>
			</div>

			<!-- Derniers 404 -->
			<div style="background: #fff; padding: 20px; border: 1px solid #ddd; border-radius: 8px;">
				<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
					<h3 style="margin: 0;">📋 Derniers 404 détectés</h3>
					<div>
						<a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin.php?page=404_alert_stats&action=export' ), '404_alert_export' ) ); ?>" class="button">Exporter CSV</a>
						<a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin.php?page=404_alert_stats&action=clear' ), '404_alert_clear' ) ); ?>" class="button" onclick="return confirm('Êtes-vous sûr de vouloir effacer toutes les statistiques ?');">Effacer</a>
					</div>
				</div>
				<table class="widefat" cellspacing="0">
					<thead>
						<tr>
							<th>Date</th>
							<th>URL</th>
							<th>IP</th>
							<th>Referrer</th>
							<th>Navigateur</th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $stats as $record ) : ?>
							<tr>
								<td><?php echo esc_html( $record['timestamp'] ); ?></td>
								<td><?php echo esc_html( mb_strimwidth( $record['url'], 0, 50, '...' ) ); ?></td>
								<td><?php echo esc_html( $record['ip'] ); ?></td>
								<td><?php echo esc_html( mb_strimwidth( $record['referrer'] ?? '-', 0, 30, '...' ) ); ?></td>
								<td><?php echo esc_html( $record['user_agent_readable'] ?? '-' ); ?></td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			</div>
		</div>
		<?php
	}
}
