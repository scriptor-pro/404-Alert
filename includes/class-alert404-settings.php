<?php
/**
 * Settings page for 404 Alert plugin.
 *
 * @package Alert404
 */

defined( 'ABSPATH' ) || exit;

/**
 * Manages settings and options for 404 Alert plugin.
 */
class Alert404_Settings {
	/**
	 * Initialize the Settings class
	 * Registers necessary administration hooks
	 *
	 * @return void
	 */
	public static function init(): void {
		add_action( 'admin_menu', array( self::class, 'add_menu' ) );
		add_action( 'admin_init', array( self::class, 'register_settings' ) );
		add_action( 'wp_ajax_404_alert_test_smtp', array( self::class, 'handle_test_smtp' ) );
		add_action( 'wp_ajax_404_alert_get_test_progress', array( self::class, 'handle_get_progress' ) );
		add_action( 'wp_ajax_404_alert_save_custom_preset', array( self::class, 'handle_save_custom_preset' ) );
		add_action( 'wp_ajax_404_alert_delete_custom_preset', array( self::class, 'handle_delete_custom_preset' ) );
		add_action( 'wp_ajax_404_alert_get_all_presets', array( self::class, 'handle_get_all_presets' ) );
		add_action( 'admin_enqueue_scripts', array( self::class, 'enqueue_admin_scripts' ) );
	}

	/**
	 * Register the admin JS script
	 *
	 * @param string $hook Current page identifier.
	 * @return void
	 */
	public static function enqueue_admin_scripts( string $hook ): void {
		if ( 'settings_page_404_alert' !== $hook ) {
			return;
		}
		wp_enqueue_style(
			'404-alert-progress',
			plugin_dir_url( ALERT404_MAIN_FILE ) . 'assets/css/alert404-progress.css',
			array(),
			ALERT404_VERSION
		);
		wp_enqueue_script(
			'404-alert-admin',
			plugin_dir_url( ALERT404_MAIN_FILE ) . 'assets/js/alert404-admin.js',
			array( 'jquery' ),
			ALERT404_VERSION,
			true
		);
		wp_localize_script(
			'404-alert-admin',
			'alert404AdminVars',
			array(
				'ajaxurl'     => admin_url( 'admin-ajax.php' ),
				'nonce'       => wp_create_nonce( '404_alert_test_smtp' ),
				'presetNonce' => wp_create_nonce( '404_alert_preset_nonce' ),
			)
		);
		wp_enqueue_script(
			'404-alert-smtp-presets',
			plugin_dir_url( ALERT404_MAIN_FILE ) . 'assets/js/alert404-smtp-presets.js',
			array( 'jquery' ),
			ALERT404_VERSION,
			true
		);
		wp_enqueue_script(
			'404-alert-smtp-config',
			plugin_dir_url( ALERT404_MAIN_FILE ) . 'assets/js/alert404-smtp-config.js',
			array( 'jquery' ),
			ALERT404_VERSION,
			true
		);
	}

	/**
	 * Handle AJAX request to test SMTP connection
	 *
	 * @return void
	 */
	public static function handle_test_smtp(): void {
		check_ajax_referer( '404_alert_test_smtp', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => 'Access denied' ) );
		}

		// Récupère les données du formulaire si disponibles
		$formData = isset( $_POST['formData'] ) ? json_decode( wp_unslash( $_POST['formData'] ), true ) : array();

		$result = Alert404_SMTP_Handler::test_connection( $formData );
		if ( $result['success'] ) {
			wp_send_json_success( array( 'message' => $result['message'] ) );
		} else {
			wp_send_json_error( array( 'message' => $result['message'] ) );
		}
	}

	/**
	 * Handle AJAX request to get current test progress
	 *
	 * @return void
	 */
	public static function handle_get_progress(): void {
		check_ajax_referer( '404_alert_test_smtp', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => 'Access denied' ) );
		}

		$progress = Alert404_Test_Progress::get_progress();
		wp_send_json_success( $progress );
	}

	/**
	 * Add the settings page to WordPress Settings menu
	 * Creates a new "Alert404" entry in admin menu
	 *
	 * @return void
	 */
	public static function add_menu(): void {
		add_menu_page(
			'404 Alert',
			'404 Alert',
			'manage_options',
			'404_alert',
			array( self::class, 'render_page' ),
			'dashicons-warning',
			25
		);
	}

	/**
	 * Register settings and form fields
	 * Creates settings sections with sanitization
	 *
	 * @return void
	 */
	public static function register_settings(): void {
		register_setting(
			'404_alert',
			'404_alert_options',
			array(
				'type'              => 'array',
				'sanitize_callback' => array( self::class, 'sanitize_options' ),
				'show_in_rest'      => false,
			)
		);

		register_setting(
			'404_alert',
			'404_alert_smtp_options',
			array(
				'type'              => 'array',
				'sanitize_callback' => array( self::class, 'sanitize_smtp_options' ),
				'show_in_rest'      => false,
			)
		);

		add_settings_section(
			'404_alert_section',
			'Monitoring Settings',
			array( self::class, 'render_section' ),
			'404_alert'
		);

		add_settings_field(
			'404_alert_email',
			'Recipient Email',
			array( self::class, 'render_field_email' ),
			'404_alert',
			'404_alert_section'
		);

		add_settings_field(
			'404_alert_daily_limit',
			'Daily Limit',
			array( self::class, 'render_field_daily_limit' ),
			'404_alert',
			'404_alert_section'
		);

		add_settings_field(
			'404_alert_ip_cooldown',
			'IP Cooldown (seconds)',
			array( self::class, 'render_field_ip_cooldown' ),
			'404_alert',
			'404_alert_section'
		);

		add_settings_field(
			'404_alert_force_logging',
			'Force Logging',
			array( self::class, 'render_field_force_logging' ),
			'404_alert',
			'404_alert_section'
		);

		add_settings_field(
			'404_alert_enable_stats',
			'Statistics',
			array( self::class, 'render_field_enable_stats' ),
			'404_alert',
			'404_alert_section'
		);

		// SMTP section.
		add_settings_section(
			'404_alert_smtp_section',
			'SMTP Configuration',
			array( self::class, 'render_smtp_section' ),
			'404_alert'
		);
	}

	/**
	 * Display the monitoring settings section description
	 *
	 * @return void
	 */
	public static function render_section(): void {
		echo '<p>Configure the monitoring settings for 404 errors.</p>';
	}

	/**
	 * Display the SMTP section description
	 *
	 * @return void
	 */
	public static function render_smtp_section(): void {
		self::render_smtp_two_column_form();
	}

	/**
	 * Render the two-column SMTP configuration form
	 *
	 * @return void
	 */
	public static function render_smtp_two_column_form(): void {
		$smtp_options = get_option( '404_alert_smtp_options', array() );
		$presets      = Alert404_SMTP_Presets::get_presets();
		$current_host = $smtp_options['host'] ?? '';
		$current_port = $smtp_options['port'] ?? 587;
		$current_enc  = $smtp_options['encryption'] ?? 'tls';
		$username     = $smtp_options['username'] ?? '';
		$from_email   = $smtp_options['from_email'] ?? get_option( 'admin_email' );
		$from_name    = $smtp_options['from_name'] ?? get_bloginfo( 'name' );
		?>
		<p>Configure your SMTP server for sending emails. Choose a preset on the left or enter custom settings on the right.</p>

		<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0; border: 1px solid #c3c4c7; border-radius: 4px; overflow: hidden; margin-top: 20px;">

			<!-- LEFT COLUMN: Preset -->
			<div id="404-smtp-col-left" style="padding: 20px 24px; background: #f6f7f7; border-right: 1px solid #c3c4c7;">
				<div style="font-size: 12px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; color: #646970; margin-bottom: 14px;">
					<span style="display: inline-block; width: 8px; height: 8px; border-radius: 50%; background: #2271b1; vertical-align: middle; margin-right: 8px;"></span>
					Fournisseur connu
				</div>

				<div style="margin-bottom: 14px;">
					<label for="404-preset-select" style="display: block; font-size: 12px; font-weight: 600; margin-bottom: 4px; color: #1d2327;">Fournisseur</label>
					<select id="404-preset-select" style="width: 100%; padding: 7px 10px; border: 1px solid #8c8f94; border-radius: 3px; font-size: 13px;">
						<option value="">— Choisir un fournisseur —</option>
						<?php foreach ( $presets as $key => $preset ) : ?>
							<option value="<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $preset['name'] ); ?></option>
						<?php endforeach; ?>
					</select>
				</div>

				<div style="margin-bottom: 14px;">
					<label for="404-left-username" style="display: block; font-size: 12px; font-weight: 600; margin-bottom: 4px; color: #1d2327;">Adresse email</label>
					<input type="email" id="404-left-username" name="404_alert_smtp_options[username]" style="width: 100%; padding: 7px 10px; border: 1px solid #8c8f94; border-radius: 3px; font-size: 13px;" placeholder="votre@email.com" value="<?php echo esc_attr( $username ); ?>" />
				</div>

				<div style="margin-bottom: 14px;">
					<label for="404-left-password" style="display: block; font-size: 12px; font-weight: 600; margin-bottom: 4px; color: #1d2327;">Mot de passe / Clé API</label>
					<input type="password" id="404-left-password" name="404_alert_smtp_options[password]" style="width: 100%; padding: 7px 10px; border: 1px solid #8c8f94; border-radius: 3px; font-size: 13px;" placeholder="••••••••" autocomplete="new-password" />
				</div>

				<div id="404-preset-info" style="display: none; background: #f0f6fc; border: 1px solid #72aee6; border-radius: 3px; padding: 10px 12px; font-size: 12px; color: #2271b1; line-height: 1.5; margin-bottom: 14px;"></div>

				<!-- Hidden fields for preset data -->
				<input type="hidden" id="404-left-host" value="<?php echo esc_attr( $current_host ); ?>" />
				<input type="hidden" id="404-left-port" value="<?php echo esc_attr( $current_port ); ?>" />
				<input type="hidden" id="404-left-encryption" value="<?php echo esc_attr( $current_enc ); ?>" />
			</div>

			<!-- RIGHT COLUMN: Custom -->
			<div id="404-smtp-col-right" style="padding: 20px 24px; background: #fff;">
				<div style="font-size: 12px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; color: #646970; margin-bottom: 14px;">
					<span style="display: inline-block; width: 8px; height: 8px; border-radius: 50%; background: #c3c4c7; vertical-align: middle; margin-right: 8px;"></span>
					Fournisseur non listé
				</div>

				<div style="margin-bottom: 14px;">
					<label for="404-right-host" style="display: block; font-size: 12px; font-weight: 600; margin-bottom: 4px; color: #1d2327;">Serveur SMTP</label>
					<input type="text" id="404-right-host" name="404_alert_smtp_options[host]" style="width: 100%; padding: 7px 10px; border: 1px solid #8c8f94; border-radius: 3px; font-size: 13px;" placeholder="smtp.exemple.com" value="<?php echo esc_attr( $current_host ); ?>" disabled />
				</div>

				<div style="margin-bottom: 14px;">
					<label style="display: block; font-size: 12px; font-weight: 600; margin-bottom: 4px; color: #1d2327;">Port SMTP</label>
					<div style="display: flex; gap: 8px;">
						<div style="flex: 1;">
							<select id="404-right-port" name="404_alert_smtp_options[port]" style="width: 100%; padding: 7px 10px; border: 1px solid #8c8f94; border-radius: 3px; font-size: 13px;" disabled>
								<option value="587" <?php selected( $current_port, 587 ); ?>>587 — TLS (recommandé)</option>
								<option value="465" <?php selected( $current_port, 465 ); ?>>465 — SSL</option>
								<option value="25" <?php selected( $current_port, 25 ); ?>>25 — SMTP classique</option>
								<option value="1025" <?php selected( $current_port, 1025 ); ?>>1025 — ProtonMail Bridge</option>
								<option value="2525" <?php selected( $current_port, 2525 ); ?>>2525 — Alternatif</option>
								<option value="0">Autre…</option>
							</select>
						</div>
						<div id="404-port-custom-wrap" style="display: none; flex: 1;">
							<input type="number" id="404-right-port-custom" style="width: 100%; padding: 7px 10px; border: 1px solid #8c8f94; border-radius: 3px; font-size: 13px;" placeholder="Port" min="1" max="65535" disabled />
						</div>
					</div>
				</div>

				<div style="margin-bottom: 14px;">
					<label for="404-right-encryption" style="display: block; font-size: 12px; font-weight: 600; margin-bottom: 4px; color: #1d2327;">Chiffrement</label>
					<select id="404-right-encryption" name="404_alert_smtp_options[encryption]" style="width: 100%; padding: 7px 10px; border: 1px solid #8c8f94; border-radius: 3px; font-size: 13px;" disabled>
						<option value="tls" <?php selected( $current_enc, 'tls' ); ?>>TLS</option>
						<option value="ssl" <?php selected( $current_enc, 'ssl' ); ?>>SSL</option>
						<option value="none" <?php selected( $current_enc, 'none' ); ?>>Aucun</option>
					</select>
				</div>

				<div style="margin-bottom: 14px;">
					<label for="404-right-username" style="display: block; font-size: 12px; font-weight: 600; margin-bottom: 4px; color: #1d2327;">Identifiant SMTP</label>
					<input type="text" id="404-right-username" name="404_alert_smtp_options[username]" style="width: 100%; padding: 7px 10px; border: 1px solid #8c8f94; border-radius: 3px; font-size: 13px;" placeholder="utilisateur ou email" value="<?php echo esc_attr( $username ); ?>" disabled />
				</div>

				<div style="margin-bottom: 14px;">
					<label for="404-right-password" style="display: block; font-size: 12px; font-weight: 600; margin-bottom: 4px; color: #1d2327;">Mot de passe</label>
					<input type="password" id="404-right-password" name="404_alert_smtp_options[password]" style="width: 100%; padding: 7px 10px; border: 1px solid #8c8f94; border-radius: 3px; font-size: 13px;" placeholder="••••••••" autocomplete="new-password" disabled />
				</div>
			</div>
		</div>

		<!-- Common fields below grid -->
		<div style="border: 1px solid #c3c4c7; border-top: none; padding: 20px 24px; background: #fcfcfc; border-bottom-left-radius: 4px; border-bottom-right-radius: 4px;">
			<div style="font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.06em; color: #8c8f94; margin-bottom: 14px;">Expéditeur — commun aux deux modes</div>
			<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px 24px;">
				<div style="margin: 0;">
					<label for="404-from-email" style="display: block; font-size: 12px; font-weight: 600; margin-bottom: 4px; color: #1d2327;">Adresse expéditeur</label>
					<input type="email" id="404-from-email" name="404_alert_smtp_options[from_email]" style="width: 100%; padding: 7px 10px; border: 1px solid #8c8f94; border-radius: 3px; font-size: 13px;" placeholder="noreply@monsite.com" value="<?php echo esc_attr( $from_email ); ?>" />
				</div>
				<div style="margin: 0;">
					<label for="404-from-name" style="display: block; font-size: 12px; font-weight: 600; margin-bottom: 4px; color: #1d2327;">Nom expéditeur</label>
					<input type="text" id="404-from-name" name="404_alert_smtp_options[from_name]" style="width: 100%; padding: 7px 10px; border: 1px solid #8c8f94; border-radius: 3px; font-size: 13px;" placeholder="Mon Site WordPress" value="<?php echo esc_attr( $from_name ); ?>" />
				</div>
			</div>
		</div>

		<!-- Test Connection Button -->
		<div style="margin-top: 20px; display: none;" id="404-alert-test-section">
			<button type="button" class="button" id="404-alert-smtp-test">Tester la connexion</button>
			<div id="404-alert-test-progress" style="margin-top: 20px; display: none;">
				<div class="alert404-progress-bar-container">
					<div class="alert404-progress-bar" style="width: 0%"></div>
				</div>
				<ul class="alert404-steps-list"></ul>
			</div>
		</div>

		<script>
			const a404Presets = <?php echo wp_json_encode( $presets ); ?>;
		</script>

		<details style="margin-top: 15px;">
			<summary style="cursor: pointer; font-weight: bold; padding: 10px; background-color: #f5f5f5; border: 1px solid #ddd; border-radius: 4px;">📧 Gmail (recommended)</summary>
			<div style="margin-top: 10px; padding: 15px; border: 1px solid #ddd; border-top: none;">
				<table style="border-collapse: collapse; width: 100%;">
					<tr style="background-color: #f9f9f9;">
						<th style="padding: 10px; border: 1px solid #ddd; text-align: left;">Paramètre</th>
						<th style="padding: 10px; border: 1px solid #ddd; text-align: left;">Valeur</th>
					</tr>
					<tr>
						<td style="padding: 10px; border: 1px solid #ddd;"><strong>SMTP Server</strong></td>
						<td style="padding: 10px; border: 1px solid #ddd;"><code>smtp.gmail.com</code></td>
					</tr>
					<tr style="background-color: #f9f9f9;">
						<td style="padding: 10px; border: 1px solid #ddd;"><strong>Port</strong></td>
						<td style="padding: 10px; border: 1px solid #ddd;"><code>587</code></td>
					</tr>
					<tr>
						<td style="padding: 10px; border: 1px solid #ddd;"><strong>Encryption</strong></td>
						<td style="padding: 10px; border: 1px solid #ddd;"><code>TLS</code></td>
					</tr>
					<tr style="background-color: #f9f9f9;">
						<td style="padding: 10px; border: 1px solid #ddd;"><strong>Username</strong></td>
						<td style="padding: 10px; border: 1px solid #ddd;">Your Gmail address</td>
					</tr>
					<tr>
						<td style="padding: 10px; border: 1px solid #ddd;"><strong>Mot de passe</strong></td>
						<td style="padding: 10px; border: 1px solid #ddd;"><a href="https://myaccount.google.com/apppasswords" target="_blank">App password</a></td>
					</tr>
					<tr style="background-color: #f9f9f9;">
						<td style="padding: 10px; border: 1px solid #ddd;"><strong>Limite/jour</strong></td>
						<td style="padding: 10px; border: 1px solid #ddd;">500 emails</td>
					</tr>
				</table>
				<p style="margin-top: 10px; font-size: 0.9em; color: #666;">
					<strong>💡 Conseil :</strong> Utilisez un <a href="https://myaccount.google.com/apppasswords" target="_blank">mot de passe d'application</a> (pas votre mot de passe Google).
				</p>
			</div>
		</details>

		<details style="margin-top: 10px;">
			<summary style="cursor: pointer; font-weight: bold; padding: 10px; background-color: #f5f5f5; border: 1px solid #ddd; border-radius: 4px;">💌 Yahoo Mail</summary>
			<div style="margin-top: 10px; padding: 15px; border: 1px solid #ddd; border-top: none;">
				<table style="border-collapse: collapse; width: 100%;">
					<tr style="background-color: #f9f9f9;">
						<th style="padding: 10px; border: 1px solid #ddd; text-align: left;">Paramètre</th>
						<th style="padding: 10px; border: 1px solid #ddd; text-align: left;">Valeur</th>
					</tr>
					<tr>
						<td style="padding: 10px; border: 1px solid #ddd;"><strong>SMTP Server</strong></td>
						<td style="padding: 10px; border: 1px solid #ddd;"><code>smtp.mail.yahoo.com</code></td>
					</tr>
					<tr style="background-color: #f9f9f9;">
						<td style="padding: 10px; border: 1px solid #ddd;"><strong>Port</strong></td>
						<td style="padding: 10px; border: 1px solid #ddd;"><code>587</code></td>
					</tr>
					<tr>
						<td style="padding: 10px; border: 1px solid #ddd;"><strong>Encryption</strong></td>
						<td style="padding: 10px; border: 1px solid #ddd;"><code>TLS</code></td>
					</tr>
					<tr style="background-color: #f9f9f9;">
						<td style="padding: 10px; border: 1px solid #ddd;"><strong>Username</strong></td>
						<td style="padding: 10px; border: 1px solid #ddd;">Your Yahoo email address</td>
					</tr>
					<tr>
						<td style="padding: 10px; border: 1px solid #ddd;"><strong>Mot de passe</strong></td>
						<td style="padding: 10px; border: 1px solid #ddd;"><a href="https://login.yahoo.com/account/security" target="_blank">App password</a></td>
					</tr>
					<tr style="background-color: #f9f9f9;">
						<td style="padding: 10px; border: 1px solid #ddd;"><strong>Limite/jour</strong></td>
						<td style="padding: 10px; border: 1px solid #ddd;">450 emails</td>
					</tr>
				</table>
				<p style="margin-top: 10px; font-size: 0.9em; color: #666;">
					<strong>💡 Conseil :</strong> Générez un <a href="https://login.yahoo.com/account/security" target="_blank">mot de passe d'application</a> depuis vos paramètres de sécurité Yahoo.
				</p>
			</div>
		</details>

		<details style="margin-top: 10px;">
			<summary style="cursor: pointer; font-weight: bold; padding: 10px; background-color: #f5f5f5; border: 1px solid #ddd; border-radius: 4px;">🔐 ProtonMail</summary>
			<div style="margin-top: 10px; padding: 15px; border: 1px solid #ddd; border-top: none;">
				<table style="border-collapse: collapse; width: 100%;">
					<tr style="background-color: #f9f9f9;">
						<th style="padding: 10px; border: 1px solid #ddd; text-align: left;">Paramètre</th>
						<th style="padding: 10px; border: 1px solid #ddd; text-align: left;">Valeur</th>
					</tr>
					<tr>
						<td style="padding: 10px; border: 1px solid #ddd;"><strong>SMTP Server</strong></td>
						<td style="padding: 10px; border: 1px solid #ddd;"><code>smtp.protonmail.com</code></td>
					</tr>
					<tr style="background-color: #f9f9f9;">
						<td style="padding: 10px; border: 1px solid #ddd;"><strong>Port</strong></td>
						<td style="padding: 10px; border: 1px solid #ddd;"><code>1025</code></td>
					</tr>
					<tr>
						<td style="padding: 10px; border: 1px solid #ddd;"><strong>Encryption</strong></td>
						<td style="padding: 10px; border: 1px solid #ddd;"><code>TLS</code></td>
					</tr>
					<tr style="background-color: #f9f9f9;">
						<td style="padding: 10px; border: 1px solid #ddd;"><strong>Username</strong></td>
						<td style="padding: 10px; border: 1px solid #ddd;">Your ProtonMail address</td>
					</tr>
					<tr>
						<td style="padding: 10px; border: 1px solid #ddd;"><strong>Mot de passe</strong></td>
						<td style="padding: 10px; border: 1px solid #ddd;"><a href="https://account.protonmail.com/mail/settings/passwords" target="_blank">App password</a></td>
					</tr>
					<tr style="background-color: #f9f9f9;">
						<td style="padding: 10px; border: 1px solid #ddd;"><strong>Limite/jour</strong></td>
						<td style="padding: 10px; border: 1px solid #ddd;">Illimité (freemium: 150/jour)</td>
					</tr>
				</table>
				<p style="margin-top: 10px; font-size: 0.9em; color: #666;">
					<strong>💡 Conseil :</strong> Générez un <a href="https://account.protonmail.com/mail/settings/passwords" target="_blank">mot de passe d'application</a> depuis vos paramètres de sécurité ProtonMail. Utilisez votre adresse email ProtonMail comme identifiant.
				</p>
			</div>
		</details>

		<details style="margin-top: 10px;">
			<summary style="cursor: pointer; font-weight: bold; padding: 10px; background-color: #f5f5f5; border: 1px solid #ddd; border-radius: 4px;">📬 Autres services SMTP</summary>
			<div style="margin-top: 10px; padding: 15px; border: 1px solid #ddd; border-top: none;">
				<table style="border-collapse: collapse; width: 100%;">
					<tr style="background-color: #f5f5f5;">
						<th style="padding: 10px; border: 1px solid #ddd; text-align: left;">Service</th>
						<th style="padding: 10px; border: 1px solid #ddd; text-align: left;">Serveur SMTP</th>
						<th style="padding: 10px; border: 1px solid #ddd; text-align: left;">Port</th>
						<th style="padding: 10px; border: 1px solid #ddd; text-align: left;">Chiffrement</th>
						<th style="padding: 10px; border: 1px solid #ddd; text-align: left;">Limite/jour</th>
					</tr>
					<tr>
						<td style="padding: 10px; border: 1px solid #ddd;"><strong>Brevo</strong></td>
						<td style="padding: 10px; border: 1px solid #ddd;"><code>smtp-relay.brevo.com</code></td>
						<td style="padding: 10px; border: 1px solid #ddd;">587</td>
						<td style="padding: 10px; border: 1px solid #ddd;">TLS</td>
						<td style="padding: 10px; border: 1px solid #ddd;">300</td>
					</tr>
					<tr style="background-color: #f9f9f9;">
						<td style="padding: 10px; border: 1px solid #ddd;"><strong>Mailtrap</strong></td>
						<td style="padding: 10px; border: 1px solid #ddd;"><code>sandbox.smtp.mailtrap.io</code></td>
						<td style="padding: 10px; border: 1px solid #ddd;">587</td>
						<td style="padding: 10px; border: 1px solid #ddd;">TLS</td>
						<td style="padding: 10px; border: 1px solid #ddd;">100 (test)</td>
					</tr>
					<tr>
						<td style="padding: 10px; border: 1px solid #ddd;"><strong>SendGrid</strong></td>
						<td style="padding: 10px; border: 1px solid #ddd;"><code>smtp.sendgrid.net</code></td>
						<td style="padding: 10px; border: 1px solid #ddd;">587</td>
						<td style="padding: 10px; border: 1px solid #ddd;">TLS</td>
						<td style="padding: 10px; border: 1px solid #ddd;">100</td>
					</tr>
					<tr style="background-color: #f9f9f9;">
						<td style="padding: 10px; border: 1px solid #ddd;"><strong>Mailgun</strong></td>
						<td style="padding: 10px; border: 1px solid #ddd;"><code>smtp.mailgun.org</code></td>
						<td style="padding: 10px; border: 1px solid #ddd;">587</td>
						<td style="padding: 10px; border: 1px solid #ddd;">TLS</td>
						<td style="padding: 10px; border: 1px solid #ddd;">5000/mois</td>
					</tr>
				</table>
			</div>
		</details>
		<?php
	}

	/**
	 * Render preset buttons for quick SMTP setup
	 *
	 * @return void
	 */
	public static function render_smtp_presets_combobox(): void {
		?>
		<div style="margin-bottom: 20px;">
			<label for="404-alert-smtp-preset-search" style="display: block; margin-bottom: 8px; font-weight: bold;">
				Choisir une solution d'envoi ou créer une personnalisée:
			</label>
			<div style="position: relative;">
				<input
					type="text"
					id="404-alert-smtp-preset-search"
					placeholder="Rechercher ou sélectionner... (Gmail, Yahoo, etc.)"
					style="width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 3px; font-size: 14px;"
				/>
				<ul
					id="404-alert-preset-suggestions"
					style="position: absolute; top: 100%; left: 0; right: 0; background: white; border: 1px solid #ccc; border-top: none; margin: 0; padding: 0; list-style: none; display: none; z-index: 1000; max-height: 300px; overflow-y: auto; border-bottom-left-radius: 3px; border-bottom-right-radius: 3px; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
				</ul>
			</div>
			<p style="margin-top: 10px; font-size: 12px; color: #666;">
				💡 Conseil: Sélectionnez un service ou créez une solution personnalisée. Vous pouvez réutiliser vos solutions sauvegardées.
			</p>
		</div>
		<?php
	}

	/**
	 * Display the recipient email field
	 * Retrieves the plugin email or the default administrator email
	 *
	 * @return void
	 */
	public static function render_field_email(): void {
		$options = get_option( '404_alert_options', array() );
		$email   = $options['email'] ?? get_option( 'admin_email' );
		printf(
			'<input type="email" name="404_alert_options[email]" value="%s" required />',
			esc_attr( $email )
		);
	}

	/**
	 * Display the daily email limit field
	 * Range: 1 to 10000 emails per day
	 *
	 * @return void
	 */
	public static function render_field_daily_limit(): void {
		$options = get_option( '404_alert_options', array() );
		$limit   = $options['daily_limit'] ?? 500;
		printf(
			'<input type="number" name="404_alert_options[daily_limit]" value="%d" min="1" max="10000" /> emails per day',
			esc_attr( $limit )
		);
	}

	/**
	 * Display the IP cooldown delay field
	 * Range: 60 to 3600 seconds (1 min to 1 hour)
	 *
	 * @return void
	 */
	public static function render_field_ip_cooldown(): void {
		$options  = get_option( '404_alert_options', array() );
		$cooldown = $options['ip_cooldown'] ?? 300;
		printf(
			'<input type="number" name="404_alert_options[ip_cooldown]" value="%d" min="60" max="3600" /> (min: 60, max: 3600)',
			esc_attr( $cooldown )
		);
	}

	/**
	 * Display the force logging field
	 *
	 * @return void
	 */
	public static function render_field_force_logging(): void {
		$options       = get_option( '404_alert_options', array() );
		$force_logging = ! empty( $options['force_logging'] );
		?>
		<input type="checkbox"
				name="404_alert_options[force_logging]"
				value="1"
				<?php checked( $force_logging, true ); ?> />
		<label>Enable logging even if WP_DEBUG_LOG is disabled</label>
		<p class="description" style="color: #666;">
			Useful for debugging. Logs will be written to <code>wp-content/debug.log</code>.
		</p>
		<?php
	}

	/**
	 * Affiche le champ d'activation des statistiques
	 *
	 * @return void
	 */
	public static function render_field_enable_stats(): void {
		$options      = get_option( '404_alert_options', array() );
		$enable_stats = ! empty( $options['enable_stats'] );
		?>
		<input type="checkbox" 
				name="404_alert_options[enable_stats]" 
				value="1" 
				id="404_alert_enable_stats"
				<?php checked( $enable_stats, true ); ?> />
		<label for="404_alert_enable_stats">Activer les statistiques</label>
		<p class="description" style="color: #666;">
			Enregistre les 404 pour afficher des statistiques dans le dashboard. Maximum 1000 enregistrements.
		</p>
		<?php
	}

	/**
	 * Affiche le champ serveur SMTP
	 *
	 * @return void
	 */
	public static function render_field_smtp_host(): void {
		$options = get_option( '404_alert_smtp_options', array() );
		$host    = $options['host'] ?? '';
		printf(
			'<input type="text" name="404_alert_smtp_options[host]" value="%s" placeholder="smtp.mailtrap.io" style="width:300px;" />',
			esc_attr( $host )
		);
	}

	/**
	 * Affiche le champ port SMTP
	 *
	 * @return void
	 */
	public static function render_field_smtp_port(): void {
		$options = get_option( '404_alert_smtp_options', array() );
		$port    = $options['port'] ?? 587;
		$ports   = array( 25, 465, 587, 2525 );
		?>
		<select name="404_alert_smtp_options[port]">
			<?php foreach ( $ports as $p ) : ?>
				<option value="<?php echo esc_attr( $p ); ?>" <?php selected( $port, $p ); ?>>
					<?php echo esc_html( $p ); ?>
				</option>
			<?php endforeach; ?>
		</select>
		<?php
	}

	/**
	 * Affiche le champ nom d'utilisateur SMTP
	 *
	 * @return void
	 */
	public static function render_field_smtp_username(): void {
		$options  = get_option( '404_alert_smtp_options', array() );
		$username = $options['username'] ?? '';
		printf(
			'<input type="text" name="404_alert_smtp_options[username]" value="%s" style="width:300px;" />',
			esc_attr( $username )
		);
	}

	/**
	 * Affiche le champ mot de passe SMTP
	 *
	 * @return void
	 */
	public static function render_field_smtp_password(): void {
		$options      = get_option( '404_alert_smtp_options', array() );
		$has_password = ! empty( $options['password'] );
		printf(
			'<input type="password" name="404_alert_smtp_options[password]" value="" autocomplete="new-password" placeholder="%s" style="width:300px;" />',
			esc_attr( $has_password ? 'Laisser vide to preserve le mot de passe actuel' : '' )
		);

		echo '<p class="description" style="color: #666; margin-top: 6px;">Le mot de passe n\'est jamais réaffiché. Leave this field empty to preserve la existing value.</p>';
	}

	/**
	 * Affiche le champ chiffrement SMTP
	 *
	 * @return void
	 */
	public static function render_field_smtp_encryption(): void {
		$options    = get_option( '404_alert_smtp_options', array() );
		$encryption = $options['encryption'] ?? 'tls';
		?>
		<select name="404_alert_smtp_options[encryption]">
			<option value="tls" <?php selected( $encryption, 'tls' ); ?>>TLS (Port 587)</option>
			<option value="ssl" <?php selected( $encryption, 'ssl' ); ?>>SSL (Port 465)</option>
			<option value="none" <?php selected( $encryption, 'none' ); ?>>Aucun</option>
		</select>
		<?php
	}

	/**
	 * Affiche le champ email d'envoi SMTP
	 *
	 * @return void
	 */
	public static function render_field_smtp_from_email(): void {
		$options    = get_option( '404_alert_smtp_options', array() );
		$from_email = $options['from_email'] ?? get_option( 'admin_email' );
		printf(
			'<input type="email" name="404_alert_smtp_options[from_email]" value="%s" style="width:300px;" />',
			esc_attr( $from_email )
		);
	}

	/**
	 * Affiche le champ nom d'envoi SMTP
	 *
	 * @return void
	 */
	public static function render_field_smtp_from_name(): void {
		$options   = get_option( '404_alert_smtp_options', array() );
		$from_name = $options['from_name'] ?? get_bloginfo( 'name' );
		printf(
			'<input type="text" name="404_alert_smtp_options[from_name]" value="%s" placeholder="%s" style="width:300px;" />',
			esc_attr( $from_name ),
			esc_attr( get_bloginfo( 'name' ) )
		);
	}

	/**
	 * Affiche le bouton de test de connexion SMTP
	 *
	 * @return void
	 */
	public static function render_field_smtp_test(): void {
		?>
		<button type="button" class="button" id="404-alert-smtp-test">Tester la connexion</button>
		<div id="404-alert-test-progress" style="margin-top: 20px; display: none;">
			<div class="alert404-progress-bar-container">
				<div class="alert404-progress-bar" style="width: 0%"></div>
			</div>
			<ul class="alert404-steps-list"></ul>
		</div>
		<?php
	}

	/**
	 * Validate and clean parameters before saving
	 * Apply value constraints (min/max) et les valeurs par défaut
	 *
	 * @param array $input Array of user-provided parameters.
	 * @return array<string, mixed> Array of validated and cleaned parameters
	 */
	public static function sanitize_options( array $input ): array {
		$old_options = get_option( '404_alert_options', array() );

		$new_options = array(
			'email'         => isset( $input['email'] ) ? sanitize_email( $input['email'] ) : get_option( 'admin_email' ),
			'daily_limit'   => isset( $input['daily_limit'] ) ? max( 1, min( 10000, (int) $input['daily_limit'] ) ) : 500,
			'ip_cooldown'   => isset( $input['ip_cooldown'] ) ? max( 60, min( 3600, (int) $input['ip_cooldown'] ) ) : 300,
			'force_logging' => ! empty( $input['force_logging'] ) ? 1 : 0,
			'enable_stats'  => ! empty( $input['enable_stats'] ) ? 1 : 0,
		);

		// Log changes if options have changed.
		if ( $old_options !== $new_options ) {
			$changed = array();
			foreach ( $new_options as $key => $value ) {
				$old_value = $old_options[ $key ] ?? null;
				if ( $old_value !== $value ) {
					$changed[ $key ] = array(
						'old' => $old_value,
						'new' => $value,
					);
				}
			}
			if ( ! empty( $changed ) ) {
				Alert404_Logger::log_options_changed( $changed );
			}
		}

		return $new_options;
	}

	/**
	 * Valide et nettoie la configuration SMTP avant sauvegarde
	 *
	 * @param array $input Configuration SMTP fournie par l'utilisateur.
	 * @return array<string, mixed> Validated and cleaned SMTP configuration
	 */
	public static function sanitize_smtp_options( array $input ): array {
		$existing_options = get_option( '404_alert_smtp_options', array() );
		$password_input   = isset( $input['password'] ) ? wp_unslash( (string) $input['password'] ) : '';

		if ( '' === $password_input ) {
			$stored_password = (string) ( $existing_options['password'] ?? '' );
		} else {
			$stored_password = Alert404_SMTP_Handler::encrypt_password_for_storage( $password_input );

			if ( '' === $stored_password ) {
				$stored_password = (string) ( $existing_options['password'] ?? '' );
			}
		}

		$new_options = array(
			'host'       => isset( $input['host'] ) ? sanitize_text_field( $input['host'] ) : '',
			'port'       => isset( $input['port'] ) ? max( 1, min( 65535, (int) $input['port'] ) ) : 587,
			'username'   => isset( $input['username'] ) ? sanitize_text_field( $input['username'] ) : '',
			'password'   => $stored_password,
			'encryption' => isset( $input['encryption'] ) && in_array( $input['encryption'], array( 'tls', 'ssl', 'none' ), true ) ? $input['encryption'] : 'tls',
			'from_email' => isset( $input['from_email'] ) ? sanitize_email( $input['from_email'] ) : get_option( 'admin_email' ),
			'from_name'  => isset( $input['from_name'] ) ? sanitize_text_field( $input['from_name'] ) : get_bloginfo( 'name' ),
		);

		// Log SMTP changes if options have changed.
		if ( $existing_options !== $new_options ) {
			$changed = array();
			foreach ( $new_options as $key => $value ) {
				// Do not log passwords.
				if ( 'password' === $key ) {
					continue;
				}

				$old_value = $existing_options[ $key ] ?? null;
				if ( $old_value !== $value ) {
					$changed[ $key ] = array(
						'old' => $old_value,
						'new' => $value,
					);
				}
			}
			if ( ! empty( $changed ) ) {
				Alert404_Logger::log_smtp_config_changed( $changed );
			}
		}

		return $new_options;
	}

	/**
	 * Display the plugin settings page
	 * Verify that user has admin rights before displaying
	 *
	 * @return void.
	 */
	public static function render_page(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( 'Access denied' );
		}

		// Display success message after saving.
		if ( isset( $_GET['settings-updated'] ) && wp_verify_nonce(
			isset( $_REQUEST['_wpnonce'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['_wpnonce'] ) ) : '',
			'404_alert'
		) ) {
			echo '<div class="notice notice-success is-dismissible"><p><strong>404 Alert :</strong> Les paramètres ont été enregistrés avec succès.</p></div>';
		}
		?>
		<div class="wrap">
			<div style="display: flex; justify-content: space-between; align-items: center;">
				<h1>404 Alert</h1>
				<div style="background-color: #f5f5f5; padding: 8px 12px; border-radius: 4px; border: 1px solid #ddd;">
					<small style="color: #666;">Version <?php echo esc_html( ALERT404_VERSION ); ?></small>
				</div>
			</div>
			<form method="post" action="options.php">
				<?php
				// Ajoute automatiquement le nonce et l'action via settings_fields().
				settings_fields( '404_alert' );
				do_settings_sections( '404_alert' );
				?>
				<!-- Aperçu des paramètres SMTP -->
				<div id="404-alert-config-summary" style="margin-top: 30px; padding: 20px; background: #f9f9f9; border: 1px solid #ddd; border-radius: 4px; display: none;">
					<h3 style="margin-top: 0; color: #333;">📋 Résumé de la configuration qui sera sauvegardée</h3>
					<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
						<div>
							<h4 style="color: #666; margin: 0 0 12px 0; font-size: 13px; text-transform: uppercase; letter-spacing: 0.05em;">Configuration SMTP</h4>
							<table style="width: 100%; font-size: 13px; border-collapse: collapse;">
								<tr style="border-bottom: 1px solid #ddd;">
									<td style="padding: 8px 0; font-weight: 600; width: 40%; color: #333;">Serveur</td>
									<td style="padding: 8px 0; color: #666;"><code id="summary-host">—</code></td>
								</tr>
								<tr style="border-bottom: 1px solid #ddd;">
									<td style="padding: 8px 0; font-weight: 600; color: #333;">Port</td>
									<td style="padding: 8px 0; color: #666;"><code id="summary-port">—</code></td>
								</tr>
								<tr style="border-bottom: 1px solid #ddd;">
									<td style="padding: 8px 0; font-weight: 600; color: #333;">Chiffrement</td>
									<td style="padding: 8px 0; color: #666;"><code id="summary-encryption">—</code></td>
								</tr>
								<tr>
									<td style="padding: 8px 0; font-weight: 600; color: #333;">Identifiant</td>
									<td style="padding: 8px 0; color: #666;"><code id="summary-username">—</code></td>
								</tr>
							</table>
						</div>
						<div>
							<h4 style="color: #666; margin: 0 0 12px 0; font-size: 13px; text-transform: uppercase; letter-spacing: 0.05em;">Expéditeur</h4>
							<table style="width: 100%; font-size: 13px; border-collapse: collapse;">
								<tr style="border-bottom: 1px solid #ddd;">
									<td style="padding: 8px 0; font-weight: 600; width: 40%; color: #333;">Adresse</td>
									<td style="padding: 8px 0; color: #666;"><code id="summary-from-email">—</code></td>
								</tr>
								<tr>
									<td style="padding: 8px 0; font-weight: 600; color: #333;">Nom</td>
									<td style="padding: 8px 0; color: #666;"><code id="summary-from-name">—</code></td>
								</tr>
							</table>
						</div>
					</div>
					<p style="margin: 15px 0 0 0; padding-top: 15px; border-top: 1px solid #ddd; font-size: 12px; color: #999;">
						✓ Mise à jour en temps réel lors de la saisie
					</p>
				</div>
				<?php submit_button( 'Save Settings' ); ?>
			</form>
		</div>
		<?php
	}

	/**
	 * Handle AJAX request to save a custom SMTP preset
	 *
	 * @return void
	 */
	public static function handle_save_custom_preset(): void {
		check_ajax_referer( '404_alert_preset_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => 'Access denied' ) );
		}

		$name       = isset( $_POST['name'] ) ? sanitize_text_field( wp_unslash( $_POST['name'] ) ) : '';
		$host       = isset( $_POST['host'] ) ? sanitize_text_field( wp_unslash( $_POST['host'] ) ) : '';
		$port       = isset( $_POST['port'] ) ? absint( wp_unslash( $_POST['port'] ) ) : 0;
		$encryption = isset( $_POST['encryption'] ) ? sanitize_text_field( wp_unslash( $_POST['encryption'] ) ) : '';

		if ( empty( $name ) || empty( $host ) || empty( $port ) || empty( $encryption ) ) {
			wp_send_json_error( array( 'message' => 'Missing required fields' ) );
		}

		$id = Alert404_SMTP_Presets::save_custom_preset( $name, $host, $port, $encryption );
		wp_send_json_success( array( 'id' => $id, 'message' => 'Preset saved successfully' ) );
	}

	/**
	 * Handle AJAX request to delete a custom SMTP preset
	 *
	 * @return void
	 */
	public static function handle_delete_custom_preset(): void {
		check_ajax_referer( '404_alert_preset_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => 'Access denied' ) );
		}

		$id = isset( $_POST['id'] ) ? sanitize_text_field( wp_unslash( $_POST['id'] ) ) : '';

		if ( empty( $id ) ) {
			wp_send_json_error( array( 'message' => 'Missing preset ID' ) );
		}

		if ( Alert404_SMTP_Presets::delete_custom_preset( $id ) ) {
			wp_send_json_success( array( 'message' => 'Preset deleted successfully' ) );
		} else {
			wp_send_json_error( array( 'message' => 'Preset not found' ) );
		}
	}

	/**
	 * Handle AJAX request to get all presets (native + custom)
	 *
	 * @return void
	 */
	public static function handle_get_all_presets(): void {
		check_ajax_referer( '404_alert_preset_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => 'Access denied' ) );
		}

		$presets = Alert404_SMTP_Presets::get_all_presets();
		wp_send_json_success( array( 'presets' => $presets ) );
	}
}
