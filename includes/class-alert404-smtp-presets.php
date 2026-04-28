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
			'yahoo'     => array(
				'name'       => '💌 Yahoo Mail',
				'host'       => 'smtp.mail.yahoo.com',
				'port'       => 587,
				'encryption' => 'tls',
				'info'       => '<strong>💡 Conseil :</strong> Générez un <a href="https://login.yahoo.com/account/security" target="_blank">mot de passe d\'application</a> depuis vos paramètres de sécurité Yahoo.',
				'limit'      => '450 emails/jour',
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
}
