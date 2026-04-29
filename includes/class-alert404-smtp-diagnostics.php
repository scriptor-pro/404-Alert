<?php
/**
 * SMTP Diagnostics page for 404 Alert plugin.
 *
 * @package Alert404
 */

defined( 'ABSPATH' ) || exit;

/**
 * Displays SMTP configuration status and diagnostics.
 */
class Alert404_SMTP_Diagnostics {
	/**
	 * Initialize the diagnostics class
	 *
	 * @return void
	 */
	public static function init(): void {
		add_action( 'admin_menu', array( self::class, 'add_menu' ) );
	}

	/**
	 * Add the SMTP diagnostics submenu page
	 *
	 * @return void
	 */
	public static function add_menu(): void {
		add_submenu_page(
			'404_alert',
			'SMTP Diagnostics',
			'SMTP',
			'manage_options',
			'404_alert_smtp_diagnostics',
			array( self::class, 'render_page' )
		);
	}

	/**
	 * Render the diagnostics page
	 *
	 * @return void
	 */
	public static function render_page(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( 'Accès refusé' );
		}

		$smtp_config = Alert404_SMTP_Handler::get_smtp_config();
		$validation  = self::validate_configuration( $smtp_config );
		?>
		<div class="wrap">
			<h1>Configuration SMTP — Diagnostic</h1>

			<!-- Statut global -->
			<div style="margin-top: 20px; padding: 20px; background: <?php echo $validation['is_complete'] ? '#f0f9f6' : '#fff0f1'; ?>; border-left: 4px solid <?php echo $validation['is_complete'] ? '#46b450' : '#dc3545'; ?>; border-radius: 4px;">
				<h2 style="margin-top: 0; color: <?php echo $validation['is_complete'] ? '#1e7e34' : '#a02830'; ?>;">
					<?php echo $validation['is_complete'] ? '✓ Configuration complète' : '⚠️ Configuration incomplète'; ?>
				</h2>
				<p style="margin: 0; color: #666;">
					<?php echo $validation['message']; ?>
				</p>
			</div>

			<!-- Paramètres SMTP -->
			<div style="margin-top: 30px; padding: 20px; background: #fff; border: 1px solid #ddd; border-radius: 4px;">
				<h3>⚙️ Paramètres SMTP</h3>
				<table style="width: 100%; border-collapse: collapse;">
					<tr style="background-color: #f9f9f9;">
						<th style="padding: 12px; border: 1px solid #ddd; text-align: left; font-weight: 600; color: #333;">Paramètre</th>
						<th style="padding: 12px; border: 1px solid #ddd; text-align: left; font-weight: 600; color: #333;">Valeur</th>
						<th style="padding: 12px; border: 1px solid #ddd; text-align: center; font-weight: 600; color: #333;">Statut</th>
					</tr>
					<?php self::render_config_row( 'Serveur SMTP', $smtp_config['host'], ! empty( $smtp_config['host'] ) ); ?>
					<?php self::render_config_row( 'Port', $smtp_config['port'], ! empty( $smtp_config['port'] ) ); ?>
					<?php self::render_config_row( 'Chiffrement', $smtp_config['encryption'], ! empty( $smtp_config['encryption'] ) ); ?>
					<?php self::render_config_row( 'Identifiant', $smtp_config['username'], ! empty( $smtp_config['username'] ) ); ?>
					<?php self::render_config_row( 'Mot de passe', self::mask_password( $smtp_config['password'] ), ! empty( $smtp_config['password'] ) ); ?>
				</table>
			</div>

			<!-- Paramètres d'expéditeur -->
			<div style="margin-top: 30px; padding: 20px; background: #fff; border: 1px solid #ddd; border-radius: 4px;">
				<h3>✉️ Paramètres d'expéditeur</h3>
				<table style="width: 100%; border-collapse: collapse;">
					<tr style="background-color: #f9f9f9;">
						<th style="padding: 12px; border: 1px solid #ddd; text-align: left; font-weight: 600; color: #333;">Paramètre</th>
						<th style="padding: 12px; border: 1px solid #ddd; text-align: left; font-weight: 600; color: #333;">Valeur</th>
						<th style="padding: 12px; border: 1px solid #ddd; text-align: center; font-weight: 600; color: #333;">Statut</th>
					</tr>
					<?php self::render_config_row( 'Adresse email', $smtp_config['from_email'], filter_var( $smtp_config['from_email'], FILTER_VALIDATE_EMAIL ) ); ?>
					<?php self::render_config_row( 'Nom expéditeur', $smtp_config['from_name'], ! empty( $smtp_config['from_name'] ) ); ?>
				</table>
			</div>

			<!-- Recommandations -->
			<?php if ( ! $validation['is_complete'] ) : ?>
				<div style="margin-top: 30px; padding: 20px; background: #fffbea; border-left: 4px solid #ffb81c; border-radius: 4px;">
					<h3 style="margin-top: 0; color: #7d6a0a;">💡 Recommandations</h3>
					<ul style="margin: 0; padding-left: 20px; color: #666;">
						<?php if ( empty( $smtp_config['host'] ) ) : ?>
							<li>Veuillez configurer le <strong>serveur SMTP</strong> (exemple: smtp.gmail.com)</li>
						<?php endif; ?>
						<?php if ( empty( $smtp_config['port'] ) ) : ?>
							<li>Veuillez configurer le <strong>port SMTP</strong> (généralement 587 pour TLS ou 465 pour SSL)</li>
						<?php endif; ?>
						<?php if ( empty( $smtp_config['encryption'] ) ) : ?>
							<li>Veuillez configurer le <strong>type de chiffrement</strong> (TLS ou SSL)</li>
						<?php endif; ?>
						<?php if ( empty( $smtp_config['username'] ) ) : ?>
							<li>Veuillez configurer l'<strong>identifiant SMTP</strong></li>
						<?php endif; ?>
						<?php if ( empty( $smtp_config['password'] ) ) : ?>
							<li>Veuillez configurer le <strong>mot de passe SMTP</strong></li>
						<?php endif; ?>
						<?php if ( ! filter_var( $smtp_config['from_email'], FILTER_VALIDATE_EMAIL ) ) : ?>
							<li>Veuillez configurer une <strong>adresse email valide</strong> pour l'expéditeur</li>
						<?php endif; ?>
					</ul>
					<p style="margin-top: 15px; color: #666;">
						<a href="<?php echo esc_url( admin_url( 'admin.php?page=404_alert' ) ); ?>" class="button button-primary">Accéder aux paramètres →</a>
					</p>
				</div>
			<?php endif; ?>

			<!-- Informations complémentaires -->
			<div style="margin-top: 30px; padding: 20px; background: #f0f7ff; border-left: 4px solid #0073aa; border-radius: 4px;">
				<h3 style="margin-top: 0; color: #0073aa;">ℹ️ Informations</h3>
				<ul style="margin: 0; padding-left: 20px; color: #666; font-size: 13px;">
					<li>Cette page est <strong>informatif uniquement</strong> — aucune modification n'est possible ici</li>
					<li>Les paramètres SMTP sont <strong>chiffrés</strong> lors du stockage</li>
					<li>Le mot de passe n'est pas affiché pour des raisons de sécurité</li>
					<li>Pour modifier la configuration, accédez à <strong>404 Alert → Paramètres</strong></li>
					<li>Utilisez le bouton <strong>"Tester la connexion"</strong> pour valider votre configuration</li>
				</ul>
			</div>
		</div>
		<?php
	}

	/**
	 * Render a configuration row in the table
	 *
	 * @param string $label Label of the parameter.
	 * @param string $value Value of the parameter.
	 * @param bool   $is_valid Whether the parameter is valid.
	 * @return void
	 */
	private static function render_config_row( string $label, string $value, bool $is_valid ): void {
		$status = $is_valid ? '<span style="color: #46b450; font-weight: 600;">✓ OK</span>' : '<span style="color: #dc3545; font-weight: 600;">✗ Non configuré</span>';
		$value_display = empty( $value ) ? '<em style="color: #999;">Non configuré</em>' : '<code style="background: #f5f5f5; padding: 4px 8px; border-radius: 3px;">' . esc_html( $value ) . '</code>';
		?>
		<tr>
			<td style="padding: 12px; border: 1px solid #ddd; font-weight: 600; color: #333; width: 25%;"><?php echo esc_html( $label ); ?></td>
			<td style="padding: 12px; border: 1px solid #ddd;"><?php echo wp_kses_post( $value_display ); ?></td>
			<td style="padding: 12px; border: 1px solid #ddd; text-align: center;"><?php echo wp_kses_post( $status ); ?></td>
		</tr>
		<?php
	}

	/**
	 * Validate SMTP configuration
	 *
	 * @param array $config SMTP configuration.
	 * @return array Validation result with is_complete and message.
	 */
	private static function validate_configuration( array $config ): array {
		$is_complete = ! empty( $config['host'] )
			&& ! empty( $config['port'] )
			&& ! empty( $config['encryption'] )
			&& ! empty( $config['username'] )
			&& ! empty( $config['password'] )
			&& filter_var( $config['from_email'], FILTER_VALIDATE_EMAIL );

		if ( $is_complete ) {
			return array(
				'is_complete' => true,
				'message'     => 'Tous les paramètres SMTP sont correctement configurés et prêts à être utilisés.',
			);
		}

		$missing = array();
		if ( empty( $config['host'] ) ) {
			$missing[] = 'serveur SMTP';
		}
		if ( empty( $config['port'] ) ) {
			$missing[] = 'port';
		}
		if ( empty( $config['encryption'] ) ) {
			$missing[] = 'chiffrement';
		}
		if ( empty( $config['username'] ) ) {
			$missing[] = 'identifiant';
		}
		if ( empty( $config['password'] ) ) {
			$missing[] = 'mot de passe';
		}
		if ( ! filter_var( $config['from_email'], FILTER_VALIDATE_EMAIL ) ) {
			$missing[] = 'adresse email valide';
		}

		return array(
			'is_complete' => false,
			'message'     => 'Paramètre(s) manquant(s) : ' . implode( ', ', $missing ) . '. Veuillez compléter la configuration.',
		);
	}

	/**
	 * Mask the password for display (shows only *)
	 *
	 * @param string $password Password to mask.
	 * @return string Masked password.
	 */
	private static function mask_password( string $password ): string {
		return empty( $password ) ? '' : '••••••••';
	}
}
