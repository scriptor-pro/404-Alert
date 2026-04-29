<?php
/**
 * SMTP presets for common email providers.
 *
 * @package Alert404
 */

defined( 'ABSPATH' ) || exit;

/**
 * Manages SMTP configuration presets for common email providers.
 */
class Alert404_SMTP_Presets {
	/**
	 * Get all available SMTP presets
	 *
	 * @return array[]
	 */
	public static function get_presets(): array {
		return array(
			'gmail'     => array(
				'name'       => '📧 Gmail',
				'host'       => 'smtp.gmail.com',
				'port'       => 587,
				'encryption' => 'tls',
				'info'       => '<strong>💡 Conseil :</strong> Utilisez un <a href="https://myaccount.google.com/apppasswords" target="_blank">mot de passe d\'application</a> (pas votre mot de passe Google).',
				'limit'      => '500 emails/jour',
			),
			'outlook'   => array(
				'name'       => '💼 Outlook.com / Hotmail',
				'host'       => 'smtp-mail.outlook.com',
				'port'       => 587,
				'encryption' => 'tls',
				'info'       => '<strong>💡 Conseil :</strong> Utilisez votre adresse Outlook/Hotmail complète comme identifiant. <a href="https://account.microsoft.com/account/security" target="_blank">Activez l\'authentification à deux facteurs</a> et générez un mot de passe d\'application.',
				'limit'      => '10000 emails/jour',
			),
			'yahoo'     => array(
				'name'       => '💌 Yahoo Mail',
				'host'       => 'smtp.mail.yahoo.com',
				'port'       => 587,
				'encryption' => 'tls',
				'info'       => '<strong>💡 Conseil :</strong> Générez un <a href="https://login.yahoo.com/account/security" target="_blank">mot de passe d\'application</a> depuis vos paramètres de sécurité Yahoo.',
				'limit'      => '450 emails/jour',
			),
			'yandex'    => array(
				'name'       => '🚀 Yandex Mail',
				'host'       => 'smtp.yandex.com',
				'port'       => 465,
				'encryption' => 'ssl',
				'info'       => '<strong>💡 Conseil :</strong> Utilisez votre adresse Yandex complète comme identifiant. Générez un <a href="https://passport.yandex.com/security" target="_blank">mot de passe d\'application</a> pour l\'accès SMTP.',
				'limit'      => '500 emails/jour',
			),
			'postmark'  => array(
				'name'       => '📨 Postmark',
				'host'       => 'smtp.postmarkapp.com',
				'port'       => 587,
				'encryption' => 'tls',
				'info'       => '<strong>💡 Conseil :</strong> Utilisez "POSTMARK_API_TOKEN" comme identifiant (exactement). Utilisez votre token API Postmark comme mot de passe.',
				'limit'      => '10000 emails/mois (free)',
			),
			'zoho'      => array(
				'name'       => '🌐 Zoho Mail',
				'host'       => 'smtp.zoho.com',
				'port'       => 587,
				'encryption' => 'tls',
				'info'       => '<strong>💡 Conseil :</strong> Utilisez votre adresse Zoho complète comme identifiant. Générez un <a href="https://mail.zoho.com/mail" target="_blank">mot de passe d\'application</a> depuis vos paramètres de sécurité.',
				'limit'      => '500 emails/jour (free)',
			),
			'icloud'    => array(
				'name'       => '🍎 iCloud Mail',
				'host'       => 'smtp.mail.me.com',
				'port'       => 587,
				'encryption' => 'tls',
				'info'       => '<strong>💡 Conseil :</strong> Utilisez votre adresse iCloud (@icloud.com) comme identifiant. Générez un <a href="https://appleid.apple.com" target="_blank">mot de passe d\'application spécifique</a> pour Apple ID.',
				'limit'      => '500 emails/jour',
			),
			'resend'    => array(
				'name'       => '⚡ Resend',
				'host'       => 'smtp.resend.com',
				'port'       => 587,
				'encryption' => 'tls',
				'info'       => '<strong>💡 Conseil :</strong> Utilisez "default" ou votre email comme identifiant. Utilisez votre clé API Resend comme mot de passe.',
				'limit'      => '100 emails/jour (free)',
			),
			'protonmail' => array(
				'name'       => '🔐 ProtonMail',
				'host'       => 'smtp.protonmail.com',
				'port'       => 1025,
				'encryption' => 'tls',
				'info'       => '<strong>💡 Conseil :</strong> Générez un <a href="https://account.protonmail.com/mail/settings/passwords" target="_blank">mot de passe d\'application</a> depuis vos paramètres de sécurité ProtonMail. Utilisez votre adresse email ProtonMail comme identifiant.',
				'limit'      => 'Illimité (freemium: 150/jour)',
			),
			'brevo'     => array(
				'name'       => '📬 Brevo',
				'host'       => 'smtp-relay.brevo.com',
				'port'       => 587,
				'encryption' => 'tls',
				'info'       => '<strong>💡 Conseil :</strong> Utilisez votre clé API Brevo comme mot de passe.',
				'limit'      => '300 emails/jour',
			),
			'mailtrap'  => array(
				'name'       => '📮 Mailtrap',
				'host'       => 'sandbox.smtp.mailtrap.io',
				'port'       => 587,
				'encryption' => 'tls',
				'info'       => '<strong>💡 Conseil :</strong> Service de test. Récupérez vos identifiants depuis votre tableau de bord Mailtrap.',
				'limit'      => '100 emails (test)',
			),
			'sendgrid'  => array(
				'name'       => '✉️ SendGrid',
				'host'       => 'smtp.sendgrid.net',
				'port'       => 587,
				'encryption' => 'tls',
				'info'       => '<strong>💡 Conseil :</strong> Utilisez "apikey" comme identifiant et votre clé API SendGrid comme mot de passe.',
				'limit'      => '100 emails/jour (plan free)',
			),
			'mailgun'   => array(
				'name'       => '🔔 Mailgun',
				'host'       => 'smtp.mailgun.org',
				'port'       => 587,
				'encryption' => 'tls',
				'info'       => '<strong>💡 Conseil :</strong> Utilisez votre email SMTP et votre mot de passe SMTP depuis le tableau de bord Mailgun.',
				'limit'      => '5000 emails/mois',
			),
		);
	}

	/**
	 * Get a specific preset by key
	 *
	 * @param string $key Preset key.
	 * @return array|null
	 */
	public static function get_preset( string $key ): ?array {
		$presets = self::get_presets();
		return $presets[ $key ] ?? null;
	}

	/**
	 * Apply a preset to current SMTP options
	 *
	 * @param string $key Preset key.
	 * @return array Updated options.
	 */
	public static function apply_preset( string $key ): array {
		$preset  = self::get_preset( $key );
		$options = get_option( '404_alert_options', array() );

		if ( $preset ) {
			$options['smtp_host']       = $preset['host'];
			$options['smtp_port']       = $preset['port'];
			$options['smtp_encryption'] = $preset['encryption'];
		}

		return $options;
	}

	/**
	 * Get all saved custom SMTP presets
	 *
	 * @return array
	 */
	public static function get_custom_presets(): array {
		return get_option( '404_alert_smtp_custom_presets', array() );
	}

	/**
	 * Save a custom SMTP preset
	 *
	 * @param string $name Preset name.
	 * @param string $host SMTP host.
	 * @param int $port SMTP port.
	 * @param string $encryption Encryption type (tls, ssl, none).
	 * @return string Preset ID.
	 */
	public static function save_custom_preset( string $name, string $host, int $port, string $encryption ): string {
		$custom = self::get_custom_presets();
		$id     = 'custom_' . time() . '_' . wp_rand( 1000, 9999 );

		$custom[ $id ] = array(
			'name'       => sanitize_text_field( $name ),
			'host'       => sanitize_text_field( $host ),
			'port'       => absint( $port ),
			'encryption' => sanitize_text_field( $encryption ),
			'created_at' => current_time( 'mysql' ),
		);

		update_option( '404_alert_smtp_custom_presets', $custom );
		return $id;
	}

	/**
	 * Delete a custom SMTP preset
	 *
	 * @param string $id Preset ID.
	 * @return bool
	 */
	public static function delete_custom_preset( string $id ): bool {
		$custom = self::get_custom_presets();
		if ( isset( $custom[ $id ] ) ) {
			unset( $custom[ $id ] );
			update_option( '404_alert_smtp_custom_presets', $custom );
			return true;
		}
		return false;
	}

	/**
	 * Get all presets (native + custom)
	 *
	 * @return array
	 */
	public static function get_all_presets(): array {
		$presets = self::get_presets();
		$custom  = self::get_custom_presets();
		return array_merge( $presets, $custom );
	}
}
