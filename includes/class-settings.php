<?php
/**
 * Admin settings page
 *
 * @package Alert404
 */

namespace Alert404;

use function add_menu_page;
use function add_action;
use function get_option;
use function update_option;
use function current_user_can;
use function wp_nonce_field;
use function check_admin_referer;
use function wp_enqueue_style;
use function wp_enqueue_script;
use function plugin_dir_url;
use function sanitize_text_field;
use function register_setting;
use function admin_url;
use function esc_url;
use function esc_attr;
use function checked;
use function submit_button;
use function wp_safe_remote_post;
use function apply_filters;
use function wp_redirect;

defined( 'ABSPATH' ) || exit;

class Settings {
	public function __construct() {
		add_action( 'admin_menu', [ $this, 'add_menu' ] );
		add_action( 'admin_init', [ $this, 'register_settings' ] );
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_admin_assets' ] );
		add_action( 'admin_post_404_alert_save_settings', [ $this, 'handle_save_settings' ] );
	}

	/**
	 * Add admin menu
	 */
	public function add_menu(): void {
		add_menu_page(
			'404 Alert Settings',
			'404 Alert',
			'manage_options',
			'404-alert-settings',
			[ $this, 'render_settings_page' ],
			'dashicons-warning',
			26
		);
	}

	/**
	 * Register settings
	 */
	public function register_settings(): void {
		register_setting(
			'404-alert-group',
			'404_alert_options',
			[
				'sanitize_callback' => [ $this, 'sanitize_options' ],
			]
		);
	}

	/**
	 * Sanitize settings before saving
	 */
	public function sanitize_options( $options ): array {
		if ( ! is_array( $options ) ) {
			return [];
		}

		return [
			'daily_limit'    => max( 1, min( 1000, (int) ( $options['daily_limit'] ?? 20 ) ) ),
			'digest_enabled' => isset( $options['digest_enabled'] ) ? 1 : 0,
			'digest_time'    => sanitize_text_field( $options['digest_time'] ?? '08:00' ),
		];
	}

	/**
	 * Enqueue CSS/JS
	 */
	public function enqueue_admin_assets( string $hook ): void {
		if ( 'toplevel_page_404-alert-settings' !== $hook ) {
			return;
		}

		wp_enqueue_style(
			'404-alert-admin',
			plugin_dir_url( ALERT404_FILE ) . 'assets/css/admin.css',
			[],
			ALERT404_VERSION
		);

		wp_enqueue_script(
			'404-alert-admin',
			plugin_dir_url( ALERT404_FILE ) . 'assets/js/admin.js',
			[ 'jquery' ],
			ALERT404_VERSION,
			true
		);
	}

	/**
	 * Handle form submission
	 */
	public function handle_save_settings(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( 'Unauthorized' );
		}

		check_admin_referer( '404-alert-nonce' );

		$options = [
			'daily_limit'    => max( 1, min( 1000, (int) ( wp_unslash( $_POST['daily_limit'] ?? 20 ) ) ) ),
			'digest_enabled' => isset( $_POST['digest_enabled'] ) ? 1 : 0,
			'digest_time'    => sanitize_text_field( wp_unslash( $_POST['digest_time'] ?? '08:00' ) ),
		];

		update_option( '404_alert_options', $options );

		wp_safe_redirect( admin_url( 'admin.php?page=404-alert-settings&updated=1' ) );
		exit;
	}

	/**
	 * Render settings page
	 */
	public function render_settings_page(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( 'Unauthorized' );
		}

		$options = get_option( '404_alert_options', [] );
		$daily_limit = (int) ( $options['daily_limit'] ?? 20 );
		$digest_enabled = (bool) ( $options['digest_enabled'] ?? true );
		$digest_time = $options['digest_time'] ?? '08:00';

		?>
		<div class="wrap">
			<h1>404 Alert Settings</h1>

			<?php if ( isset( $_GET['updated'] ) ): ?>
				<div class="notice notice-success"><p>Settings saved successfully.</p></div>
			<?php endif; ?>

			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="404_alert_save_settings">
				<?php wp_nonce_field( '404-alert-nonce' ); ?>

				<table class="form-table">
					<tr>
						<th scope="row"><label for="daily_limit">Daily Alert Limit</label></th>
						<td>
							<input type="number" name="daily_limit" id="daily_limit" value="<?php echo esc_attr( $daily_limit ); ?>" min="1" max="1000">
							<p class="description">Maximum number of immediate alerts per day. Excess 404s go to digest.</p>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="digest_enabled">Enable Daily Digest</label></th>
						<td>
							<input type="checkbox" name="digest_enabled" id="digest_enabled" value="1" <?php checked( $digest_enabled ); ?>>
							<label for="digest_enabled">Send a daily digest of 404 errors that exceed the immediate alert limit.</label>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="digest_time">Digest Send Time</label></th>
						<td>
							<input type="time" name="digest_time" id="digest_time" value="<?php echo esc_attr( $digest_time ); ?>">
							<p class="description">Time to send the daily digest (server timezone).</p>
						</td>
					</tr>
				</table>

				<?php submit_button(); ?>
			</form>

			<hr>

			<h2>Current Status</h2>
			<table class="widefat">
				<tr>
					<th>Total 404 Errors Logged</th>
					<td><?php echo esc_html( Stats::get_total_count() ); ?></td>
				</tr>
				<tr>
					<th>Pending Digest Count</th>
					<td><?php echo esc_html( count( Digest::get_digest_urls() ) ); ?></td>
				</tr>
			</table>
		</div>
		<?php
	}
}
