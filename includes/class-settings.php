<?php
/**
 * Page de réglages pour le plugin 404 Alert
 */

defined( 'ABSPATH' ) || exit;

class Alert404_Settings {
	/**
	 * Initialise la classe Settings
	 * Enregistre les hooks d'administration nécessaires
	 *
	 * @return void
	 */
	public static function init(): void {
		add_action( 'admin_menu', array( self::class, 'add_menu' ) );
		add_action( 'admin_init', array( self::class, 'register_settings' ) );
		add_action( 'wp_ajax_404_alert_test_smtp', array( self::class, 'handle_test_smtp' ) );
	}

	/**
	 * Gère la requête AJAX pour tester la connexion SMTP
	 *
	 * @return void
	 */
	public static function handle_test_smtp(): void {
		check_ajax_referer( '404_alert_test_smtp', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => 'Accès refusé' ) );
		}

		$result = Alert404_SMTP_Handler::test_connection();
		wp_send_json( $result );
	}

	/**
	 * Ajoute la page de réglages dans le menu Paramètres de WordPress
	 * Crée une nouvelle entrée "Surveillance 404" dans le menu admin
	 *
	 * @return void
	 */
	public static function add_menu(): void {
		add_options_page(
			'Surveillance 404',
			'Surveillance 404',
			'manage_options',
			'404_alert',
			array( self::class, 'render_page' )
		);
	}

	/**
	 * Enregistre les paramètres et les champs du formulaire
	 * Crée les sections de réglages avec sanitization
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
			'Paramètres de surveillance',
			array( self::class, 'render_section' ),
			'404_alert'
		);

		add_settings_field(
			'404_alert_email',
			'Email destinataire',
			array( self::class, 'render_field_email' ),
			'404_alert',
			'404_alert_section'
		);

		add_settings_field(
			'404_alert_daily_limit',
			'Limite journalière',
			array( self::class, 'render_field_daily_limit' ),
			'404_alert',
			'404_alert_section'
		);

		add_settings_field(
			'404_alert_ip_cooldown',
			'Délai par IP (secondes)',
			array( self::class, 'render_field_ip_cooldown' ),
			'404_alert',
			'404_alert_section'
		);

		add_settings_field(
			'404_alert_force_logging',
			'Logging forcé',
			array( self::class, 'render_field_force_logging' ),
			'404_alert',
			'404_alert_section'
		);

		add_settings_field(
			'404_alert_enable_stats',
			'Statistiques',
			array( self::class, 'render_field_enable_stats' ),
			'404_alert',
			'404_alert_section'
		);

		// Section SMTP
		add_settings_section(
			'404_alert_smtp_section',
			'Configuration SMTP',
			array( self::class, 'render_smtp_section' ),
			'404_alert'
		);

		add_settings_field(
			'404_alert_smtp_host',
			'Serveur SMTP',
			array( self::class, 'render_field_smtp_host' ),
			'404_alert',
			'404_alert_smtp_section'
		);

		add_settings_field(
			'404_alert_smtp_port',
			'Port SMTP',
			array( self::class, 'render_field_smtp_port' ),
			'404_alert',
			'404_alert_smtp_section'
		);

		add_settings_field(
			'404_alert_smtp_username',
			'Nom d\'utilisateur',
			array( self::class, 'render_field_smtp_username' ),
			'404_alert',
			'404_alert_smtp_section'
		);

		add_settings_field(
			'404_alert_smtp_password',
			'Mot de passe',
			array( self::class, 'render_field_smtp_password' ),
			'404_alert',
			'404_alert_smtp_section'
		);

		add_settings_field(
			'404_alert_smtp_encryption',
			'Chiffrement',
			array( self::class, 'render_field_smtp_encryption' ),
			'404_alert',
			'404_alert_smtp_section'
		);

		add_settings_field(
			'404_alert_smtp_from_email',
			'Email d\'envoi',
			array( self::class, 'render_field_smtp_from_email' ),
			'404_alert',
			'404_alert_smtp_section'
		);

		add_settings_field(
			'404_alert_smtp_from_name',
			'Nom d\'envoi',
			array( self::class, 'render_field_smtp_from_name' ),
			'404_alert',
			'404_alert_smtp_section'
		);

		add_settings_field(
			'404_alert_smtp_test',
			'Test de connexion',
			array( self::class, 'render_field_smtp_test' ),
			'404_alert',
			'404_alert_smtp_section'
		);
	}

	/**
	 * Affiche la description de la section de réglages
	 *
	 * @return void
	 */
	public static function render_section(): void {
		echo '<p>Configurez les paramètres de surveillance des erreurs 404.</p>';
	}

	/**
	 * Affiche la description de la section SMTP
	 *
	 * @return void
	 */
	public static function render_smtp_section(): void {
		?>
		<p>Configurez votre serveur SMTP pour l'envoi d'emails.</p>
		
		<details style="margin-top: 15px;">
			<summary style="cursor: pointer; font-weight: bold; padding: 10px; background-color: #f5f5f5; border: 1px solid #ddd; border-radius: 4px;">📧 Gmail (recommandé)</summary>
			<div style="margin-top: 10px; padding: 15px; border: 1px solid #ddd; border-top: none;">
				<table style="border-collapse: collapse; width: 100%;">
					<tr style="background-color: #f9f9f9;">
						<th style="padding: 10px; border: 1px solid #ddd; text-align: left;">Paramètre</th>
						<th style="padding: 10px; border: 1px solid #ddd; text-align: left;">Valeur</th>
					</tr>
					<tr>
						<td style="padding: 10px; border: 1px solid #ddd;"><strong>Serveur SMTP</strong></td>
						<td style="padding: 10px; border: 1px solid #ddd;"><code>smtp.gmail.com</code></td>
					</tr>
					<tr style="background-color: #f9f9f9;">
						<td style="padding: 10px; border: 1px solid #ddd;"><strong>Port</strong></td>
						<td style="padding: 10px; border: 1px solid #ddd;"><code>587</code></td>
					</tr>
					<tr>
						<td style="padding: 10px; border: 1px solid #ddd;"><strong>Chiffrement</strong></td>
						<td style="padding: 10px; border: 1px solid #ddd;"><code>TLS</code></td>
					</tr>
					<tr style="background-color: #f9f9f9;">
						<td style="padding: 10px; border: 1px solid #ddd;"><strong>Nom d'utilisateur</strong></td>
						<td style="padding: 10px; border: 1px solid #ddd;">Votre adresse Gmail</td>
					</tr>
					<tr>
						<td style="padding: 10px; border: 1px solid #ddd;"><strong>Mot de passe</strong></td>
						<td style="padding: 10px; border: 1px solid #ddd;"><a href="https://myaccount.google.com/apppasswords" target="_blank">Mot de passe d'application</a></td>
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
	 * Affiche le champ email destinataire
	 * Récupère l'email du plugin ou celui de l'administrateur par défaut
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
	 * Affiche le champ limite journalière d'emails
	 * Plage: 1 à 10000 emails par jour
	 *
	 * @return void
	 */
	public static function render_field_daily_limit(): void {
		$options = get_option( '404_alert_options', array() );
		$limit   = $options['daily_limit'] ?? 500;
		printf(
			'<input type="number" name="404_alert_options[daily_limit]" value="%d" min="1" max="10000" /> emails par jour',
			esc_attr( $limit )
		);
	}

	/**
	 * Affiche le champ délai de cooldown par IP
	 * Plage: 60 à 3600 secondes (1 min à 1 heure)
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
	 * Affiche le champ de logging forcé
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
		<label>Activer le logging même si WP_DEBUG_LOG est désactivé</label>
		<p class="description" style="color: #666;">
			Utile pour le débogage. Les logs seront écrits dans <code>wp-content/debug.log</code>.
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
		printf(
			'<input type="number" name="404_alert_smtp_options[port]" value="%d" min="1" max="65535" />',
			esc_attr( $port )
		);
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
		$options  = get_option( '404_alert_smtp_options', array() );
		$has_password = ! empty( $options['password'] );
		printf(
			'<input type="password" name="404_alert_smtp_options[password]" value="" autocomplete="new-password" placeholder="%s" style="width:300px;" />',
			esc_attr( $has_password ? 'Laisser vide pour conserver le mot de passe actuel' : '' )
		);

		echo '<p class="description" style="color: #666; margin-top: 6px;">Le mot de passe n\'est jamais réaffiché. Laissez ce champ vide pour conserver la valeur existante.</p>';
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
		<div id="404-alert-smtp-test-result" style="margin-top: 10px; display: none;"></div>
		<script>
		jQuery(document).ready(function($) {
			$('#404-alert-smtp-test').on('click', function(e) {
				e.preventDefault();
				var $btn = $(this);
				var $result = $('#404-alert-smtp-test-result');

				$btn.prop('disabled', true).text('Test en cours...');
				$result.show().html('<p style="color: #999;">Vérification de la connexion...</p>');

				$.ajax({
					type: 'POST',
					url: ajaxurl,
					data: {
						action: '404_alert_test_smtp',
						nonce: '<?php echo esc_js( wp_create_nonce( '404_alert_test_smtp' ) ); ?>'
					},
					success: function(response) {
						if (response.success) {
							$result.html('<p style="color: #090; font-weight: bold;">✓ ' + response.data.message + '</p>');
						} else {
							$result.html('<p style="color: #c33; font-weight: bold;">✗ ' + response.data.message + '</p>');
						}
					},
					error: function() {
						$result.html('<p style="color: #c33;">Erreur lors du test de connexion.</p>');
					},
					complete: function() {
						$btn.prop('disabled', false).text('Tester la connexion');
					}
				});
			});
		});
		</script>
		<?php
	}

	/**
	 * Valide et nettoie les paramètres avant sauvegarde
	 * Applique les contraintes de valeurs (min/max) et les valeurs par défaut
	 *
	 * @param array $input Tableau des paramètres fournis par l'utilisateur
	 * @return array Tableau des paramètres validés et nettoyés
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

		// Log des changements si les options ont changé
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
	 * @param array $input Configuration SMTP fournie par l'utilisateur
	 * @return array Configuration SMTP validée et nettoyée
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

		// Log des changements SMTP si les options ont changé
		if ( $existing_options !== $new_options ) {
			$changed = array();
			foreach ( $new_options as $key => $value ) {
				// Ne pas logger les passwords
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
	 * Affiche la page de réglages du plugin
	 * Vérifie que l'utilisateur a les droits d'administration avant d'afficher
	 *
	 * @return void
	 */
	public static function render_page(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( 'Accès refusé' );
		}

		// Afficher un message de succès après sauvegarde
		if ( isset( $_GET['settings-updated'] ) && wp_verify_nonce(
			isset( $_REQUEST['_wpnonce'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['_wpnonce'] ) ) : '',
			'404_alert'
		) ) {
			echo '<div class="notice notice-success is-dismissible"><p><strong>404 Alert :</strong> Les paramètres ont été enregistrés avec succès.</p></div>';
		}
		?>
		<div class="wrap">
			<h1>404 Alert</h1>
			<form method="post" action="options.php">
				<?php
				// Ajoute automatiquement le nonce et l'action via settings_fields()
				settings_fields( '404_alert' );
				do_settings_sections( '404_alert' );
				submit_button( 'Enregistrer les paramètres' );
				?>
			</form>
		</div>
		<?php
	}
}
