<?php
/**
 * Admin settings page
 *
 * @package Alert404
 */

namespace Alert404;

use function add_menu_page;
use function add_action;
use function add_query_arg;
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
use function selected;
use function sanitize_email;

defined( 'ABSPATH' ) || exit;

class Settings {
	public function __construct() {
		add_action( 'admin_menu', [ $this, 'add_menu' ] );
		add_action( 'admin_init', [ $this, 'register_settings' ] );
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_admin_assets' ] );
		add_action( 'admin_post_404_alert_save_settings', [ $this, 'handle_save_settings' ] );
		add_action( 'admin_post_404_alert_test_smtp', [ $this, 'handle_test_smtp' ] );
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

		register_setting(
			'404-alert-group',
			'404_alert_smtp_options',
			[
				'sanitize_callback' => [ $this, 'sanitize_smtp_options' ],
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
			'recipient_email' => sanitize_email( (string) ( $options['recipient_email'] ?? '' ) ),
		];
	}

	/**
	 * Sanitize SMTP settings.
	 *
	 * @param array<string, mixed> $options SMTP form options.
	 * @return array<string, string|int>
	 */
	public function sanitize_smtp_options( $options ): array {
		if ( ! is_array( $options ) ) {
			$options = [];
		}

		return SMTP::sanitize_options( $options );
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
			'recipient_email' => sanitize_email( (string) wp_unslash( $_POST['recipient_email'] ?? '' ) ),
		];

		update_option( '404_alert_options', $options );

		$smtp_options = [
			'host'       => wp_unslash( $_POST['smtp_host'] ?? '' ),
			'port'       => wp_unslash( $_POST['smtp_port'] ?? 587 ),
			'encryption' => wp_unslash( $_POST['smtp_encryption'] ?? 'tls' ),
			'username'   => wp_unslash( $_POST['smtp_username'] ?? '' ),
			'password'   => wp_unslash( $_POST['smtp_password'] ?? '' ),
			'from_email' => wp_unslash( $_POST['smtp_from_email'] ?? '' ),
			'from_name'  => wp_unslash( $_POST['smtp_from_name'] ?? '' ),
		];

		update_option( '404_alert_smtp_options', SMTP::sanitize_options( $smtp_options ) );

		wp_safe_redirect( admin_url( 'admin.php?page=404-alert-settings&updated=1' ) );
		exit;
	}

	/**
	 * Handle SMTP test request.
	 */
	public function handle_test_smtp(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( 'Unauthorized' );
		}

		check_admin_referer( '404-alert-nonce' );

		$submitted = [
			'host'       => wp_unslash( $_POST['smtp_host'] ?? '' ),
			'port'       => wp_unslash( $_POST['smtp_port'] ?? 587 ),
			'encryption' => wp_unslash( $_POST['smtp_encryption'] ?? 'tls' ),
			'username'   => wp_unslash( $_POST['smtp_username'] ?? '' ),
			'password'   => wp_unslash( $_POST['smtp_password'] ?? '' ),
			'from_email' => wp_unslash( $_POST['smtp_from_email'] ?? '' ),
			'from_name'  => wp_unslash( $_POST['smtp_from_name'] ?? '' ),
		];

		$current = SMTP::get_config();
		$result  = SMTP::test_connection(
			[
				'host'       => '' !== (string) $submitted['host'] ? $submitted['host'] : $current['host'],
				'port'       => $submitted['port'],
				'encryption' => '' !== (string) $submitted['encryption'] ? $submitted['encryption'] : $current['encryption'],
				'username'   => '' !== (string) $submitted['username'] ? $submitted['username'] : $current['username'],
				'password'   => '' !== trim( (string) $submitted['password'] ) ? $submitted['password'] : $current['password'],
				'from_email' => '' !== (string) $submitted['from_email'] ? $submitted['from_email'] : $current['from_email'],
				'from_name'  => '' !== (string) $submitted['from_name'] ? $submitted['from_name'] : $current['from_name'],
			]
		);
		$target = add_query_arg(
			[
				'page'         => '404-alert-settings',
				'updated'      => 1,
				'smtp_test'    => 1,
				'smtp_result'   => $result['success'] ? 'success' : 'error',
				'smtp_message'  => $result['message'],
			],
			admin_url( 'admin.php' )
		);

		wp_safe_redirect( $target );
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
			$recipient_email = (string) ( $options['recipient_email'] ?? '' );
			$smtp_config = SMTP::get_config();
			$mail_health = MailHealthChecker::check();
			$mail_status = MailLogger::get_status();
			$smtp_password_saved = MailSettingsRepository::has_password();

		?>
			<div class="wrap">
				<h1>404 Alert Settings</h1>

				<?php if ( isset( $_GET['updated'] ) ): ?>
					<div class="notice notice-success"><p>Settings saved successfully.</p></div>
				<?php endif; ?>

				<?php if ( isset( $_GET['smtp_test'] ) ) : ?>
					<?php
					$smtp_result  = sanitize_text_field( wp_unslash( $_GET['smtp_result'] ?? '' ) );
					$smtp_message = sanitize_text_field( wp_unslash( $_GET['smtp_message'] ?? '' ) );
					$notice_class = 'success' === $smtp_result ? 'notice-success' : 'notice-error';
					$default_text = 'success' === $smtp_result ? 'SMTP connection successful.' : 'SMTP test failed.';
					?>
					<div class="notice <?php echo esc_attr( $notice_class ); ?>">
						<p><?php echo esc_html( '' !== $smtp_message ? $smtp_message : $default_text ); ?></p>
					</div>
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
						<tr>
							<th scope="row"><label for="recipient_email">Alert Recipient Email</label></th>
							<td>
								<input type="email" name="recipient_email" id="recipient_email" value="<?php echo esc_attr( $recipient_email ); ?>" class="regular-text">
								<p class="description">Leave empty to send alerts to all administrator users. If set, alerts will go only to this address.</p>
							</td>
						</tr>
					</table>

				<hr>

			<h2>SMTP Configuration</h2>
			<p class="description">If configured, the plugin will send alerts via SMTP instead of relying on <code>wp_mail()</code>.</p>

			<table class="form-table">
				<tr>
					<th scope="row"><label for="smtp_host">SMTP Host</label></th>
					<td>
						<input type="text" name="smtp_host" id="smtp_host" value="<?php echo esc_attr( $smtp_config['host'] ); ?>" class="regular-text">
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="smtp_port">SMTP Port</label></th>
					<td>
						<input type="number" name="smtp_port" id="smtp_port" value="<?php echo esc_attr( (string) $smtp_config['port'] ); ?>" min="1" max="65535">
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="smtp_encryption">Encryption</label></th>
					<td>
						<select name="smtp_encryption" id="smtp_encryption">
							<option value="tls" <?php selected( $smtp_config['encryption'], 'tls' ); ?>>TLS</option>
							<option value="ssl" <?php selected( $smtp_config['encryption'], 'ssl' ); ?>>SSL</option>
							<option value="none" <?php selected( $smtp_config['encryption'], 'none' ); ?>>None</option>
						</select>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="smtp_username">SMTP Username</label></th>
					<td>
						<input type="text" name="smtp_username" id="smtp_username" value="<?php echo esc_attr( $smtp_config['username'] ); ?>" class="regular-text">
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="smtp_password">SMTP Password</label></th>
					<td>
						<input type="password" name="smtp_password" id="smtp_password" value="" class="regular-text" autocomplete="new-password">
						<p class="description">
							<?php if ( $smtp_password_saved ) : ?>
								SMTP password is saved in memory. You can leave this field blank when testing.
							<?php else : ?>
								No SMTP password is saved yet. Enter it before testing.
							<?php endif; ?>
						</p>
					</td>
					</tr>
				<tr>
					<th scope="row"><label for="smtp_from_email">From Email</label></th>
					<td>
						<input type="email" name="smtp_from_email" id="smtp_from_email" value="<?php echo esc_attr( $smtp_config['from_email'] ); ?>" class="regular-text">
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="smtp_from_name">From Name</label></th>
					<td>
						<input type="text" name="smtp_from_name" id="smtp_from_name" value="<?php echo esc_attr( $smtp_config['from_name'] ); ?>" class="regular-text">
					</td>
				</tr>
			</table>

				<div class="notice <?php echo esc_attr( 'smtp' === $mail_health['transport'] ? 'notice-success' : ( 'wp_mail' === $mail_health['transport'] ? 'notice-info' : 'notice-warning' ) ); ?> inline">
					<p><strong>Mail transport:</strong> <?php echo esc_html( $mail_health['message'] ); ?></p>
				</div>

				<?php if ( '' !== (string) $mail_health['last_error'] ) : ?>
					<div class="notice notice-error inline">
						<p><strong>Last mail error:</strong> <?php echo esc_html( (string) $mail_health['last_error'] ); ?></p>
					</div>
				<?php endif; ?>

				<?php if ( isset( $mail_status['last_success']['time'] ) ) : ?>
					<p><strong>Last successful mail:</strong> <?php echo esc_html( (string) $mail_status['last_success']['time'] ); ?></p>
				<?php endif; ?>

					<?php submit_button(); ?>
				</form>

					<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" id="404-alert-smtp-test-form">
					<input type="hidden" name="action" value="404_alert_test_smtp">
					<input type="hidden" name="smtp_host" id="404-alert-test-smtp-host" value="">
					<input type="hidden" name="smtp_port" id="404-alert-test-smtp-port" value="">
					<input type="hidden" name="smtp_encryption" id="404-alert-test-smtp-encryption" value="">
					<input type="hidden" name="smtp_username" id="404-alert-test-smtp-username" value="">
					<input type="hidden" name="smtp_password" id="404-alert-test-smtp-password" value="">
						<input type="hidden" name="smtp_from_email" id="404-alert-test-smtp-from-email" value="">
						<input type="hidden" name="smtp_from_name" id="404-alert-test-smtp-from-name" value="">
						<?php wp_nonce_field( '404-alert-nonce' ); ?>
						<h2>SMTP Test</h2>
						<p class="description">This button tests the values currently displayed in the form without saving them again. If the password field is blank, the saved SMTP password will be used.</p>
						<?php submit_button( 'Test SMTP with current values', 'secondary', '404-alert-test-smtp', false ); ?>
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
