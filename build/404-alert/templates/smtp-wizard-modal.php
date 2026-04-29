<?php
/**
 * SMTP Wizard Modal Template
 *
 * @package Alert404
 */

defined( 'ABSPATH' ) || exit;

$presets = Alert404_SMTP_Presets::get_presets();
$nonce   = wp_create_nonce( '404_alert_smtp_wizard_nonce' );
?>

<div id="404-alert-smtp-wizard-overlay" style="display: none;">
	<div id="404-alert-smtp-wizard-modal" class="404-alert-modal">
		<div class="404-alert-modal-header">
			<h2 id="wizard-title">Configuration SMTP</h2>
			<button type="button" id="404-alert-wizard-close" class="404-alert-modal-close">&times;</button>
		</div>

		<div class="404-alert-modal-body">
			<!-- Step 1: Choose Provider -->
			<div class="wizard-step" id="wizard-step-1" style="display: none;">
				<h3>Quel fournisseur SMTP utilisez-vous ?</h3>
				<p class="help-text">Sélectionnez votre fournisseur de messagerie. Si vous ne le trouvez pas, choisissez "Autre".</p>

				<div class="provider-grid">
					<?php foreach ( $presets as $provider_id => $preset ) : ?>
						<label class="provider-option">
							<input type="radio" name="provider_id" value="<?php echo esc_attr( $provider_id ); ?>" />
							<span><?php echo esc_html( $preset['name'] ); ?></span>
						</label>
					<?php endforeach; ?>
					<label class="provider-option">
						<input type="radio" name="provider_id" value="custom" />
						<span>🔧 Autre (personnalisé)</span>
					</label>
				</div>
			</div>

			<!-- Step 2.1: Email and Password (for preset) -->
			<div class="wizard-step" id="wizard-step-2-1" style="display: none;">
				<h3 id="step-2-1-title">Identifiants</h3>

				<div class="form-group">
					<label for="wizard-email">Adresse email</label>
					<input type="email" id="wizard-email" name="email" required />
				</div>

				<div class="form-group">
					<label for="wizard-password">Mot de passe</label>
					<input type="password" id="wizard-password" name="password" required />
				</div>

				<div id="preset-help" class="help-text"></div>
			</div>

			<!-- Step 2.2: Full SMTP Parameters (for custom) -->
			<div class="wizard-step" id="wizard-step-2-2" style="display: none;">
				<h3>Paramètres SMTP personnalisés</h3>
				<p class="help-text">Votre hébergeur ou fournisseur mail vous donne ces informations.</p>

				<div class="form-group">
					<label for="wizard-host">Serveur SMTP</label>
					<input type="text" id="wizard-host" name="host" placeholder="smtp.example.com" required />
				</div>

				<div class="form-group">
					<label for="wizard-port">Port</label>
					<input type="number" id="wizard-port" name="port" value="587" min="1" max="65535" required />
				</div>

				<div class="form-group">
					<label for="wizard-encryption">Chiffrement</label>
					<select id="wizard-encryption" name="encryption" required>
						<option value="">Aucun</option>
						<option value="tls">TLS</option>
						<option value="ssl">SSL</option>
					</select>
				</div>

				<div class="form-group">
					<label for="wizard-custom-email">Adresse email</label>
					<input type="email" id="wizard-custom-email" name="email" required />
				</div>

				<div class="form-group">
					<label for="wizard-custom-password">Mot de passe</label>
					<input type="password" id="wizard-custom-password" name="password" required />
				</div>
			</div>

			<!-- Step 3: Connection Test -->
			<div class="wizard-step" id="wizard-step-3" style="display: none;">
				<h3>Vérification de la configuration...</h3>

				<div id="test-result" style="display: none;">
					<p id="test-message"></p>
				</div>

				<div id="test-spinner" class="spinner"></div>
			</div>

			<!-- Step 4: Summary -->
			<div class="wizard-step" id="wizard-step-4" style="display: none;">
				<h3>Configuration SMTP terminée !</h3>

				<table class="summary-table">
					<tr>
						<td><strong>Fournisseur</strong></td>
						<td id="summary-provider">-</td>
					</tr>
					<tr>
						<td><strong>Email</strong></td>
						<td id="summary-email">-</td>
					</tr>
					<tr>
						<td><strong>Serveur SMTP</strong></td>
						<td id="summary-server">-</td>
					</tr>
				</table>

				<p class="help-text">⚠️ Le mot de passe n'est pas affiché pour des raisons de sécurité.</p>
			</div>

			<!-- Error/Success Messages -->
			<div id="wizard-message" class="wizard-message" style="display: none;"></div>
		</div>

		<div class="404-alert-modal-footer">
			<button type="button" id="btn-back" class="button" style="display: none;">Retour</button>
			<button type="button" id="btn-test" class="button button-primary" style="display: none;">Test de connexion</button>
			<button type="button" id="btn-next" class="button button-primary" style="display: none;">Suivant</button>
			<button type="button" id="btn-finalize" class="button button-primary" style="display: none;">Terminer</button>
			<button type="button" id="btn-modify" class="button" style="display: none;">Modifier</button>
		</div>
	</div>
</div>

<input type="hidden" id="wizard-nonce" value="<?php echo esc_attr( $nonce ); ?>" />
<input type="hidden" id="wizard-ajax-url" value="<?php echo esc_attr( admin_url( 'admin-ajax.php' ) ); ?>" />

<script>
	window.alert404SMTPPresets = <?php echo wp_json_encode( $presets ); ?>;
</script>
