# SMTP Configuration Redesign Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Replace the two-column SMTP configuration interface with a hybrid accordion system that guarantees all provider parameters are pre-filled by the plugin and correctly saved to the database.

**Architecture:** Two independent accordions (Preset and Custom) with shared state management in JavaScript. Hidden input fields guarantee provider parameters are sent to the server. PHP validation detects which mode is active and saves appropriate data with `provider_id` tracking.

**Tech Stack:** PHP 7.4+, jQuery, WordPress Settings API, custom AJAX handlers

---

## File Structure

**Files to modify:**
- `includes/class-alert404-settings.php` — HTML form structure, validation function
- `assets/js/alert404-smtp-config.js` — Complete rewrite of state management and accordion logic

**No new files needed** — all logic fits within existing structure.

---

## Task 1: Refactor HTML form structure for accordions

**Files:**
- Modify: `includes/class-alert404-settings.php:252-383` (replace `render_smtp_two_column_form()`)

- [ ] **Step 1: Back up current render function**

Open `includes/class-alert404-settings.php` and review the current `render_smtp_two_column_form()` method (lines 252-383). Save the old HTML structure as a comment at the bottom of the file for reference if needed.

- [ ] **Step 2: Rewrite render_smtp_two_column_form() — Part 1: Opening and Accordion 1 (Preset)**

Replace the entire `render_smtp_two_column_form()` method with the new structure. Start with the opening wrapper and Accordion 1:

```php
public static function render_smtp_two_column_form(): void {
	$smtp_options = get_option( '404_alert_smtp_options', array() );
	$presets      = Alert404_SMTP_Presets::get_presets();
	$provider_id  = $smtp_options['provider_id'] ?? '';
	$username     = $smtp_options['username'] ?? '';
	$from_email   = $smtp_options['from_email'] ?? get_option( 'admin_email' );
	$from_name    = $smtp_options['from_name'] ?? get_bloginfo( 'name' );
	?>
	<p>Configurez votre serveur SMTP en choisissant un fournisseur connu ou en entrant une configuration personnalisée.</p>

	<div style="margin-top: 20px;">

		<!-- ACCORDION 1: PRESET -->
		<div style="border: 1px solid #c3c4c7; border-radius: 4px; overflow: hidden; margin-bottom: 0;">
			<button type="button" class="404-accordion-toggle" data-accordion="preset" style="width: 100%; padding: 15px 20px; background: #f6f7f7; border: none; text-align: left; font-size: 14px; font-weight: 600; cursor: pointer; display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #c3c4c7;">
				<span style="display: flex; align-items: center; gap: 10px;">
					<span style="display: inline-block; width: 8px; height: 8px; border-radius: 50%; background: #2271b1; vertical-align: middle;"></span>
					📌 Fournisseur connu
				</span>
				<span class="404-accordion-icon" style="font-size: 16px;">➕</span>
			</button>

			<div id="404-accordion-preset" class="404-accordion-content" style="display: none; padding: 20px 24px; background: #fff;">
				<div style="margin-bottom: 14px;">
					<label for="404-preset-id" style="display: block; font-size: 12px; font-weight: 600; margin-bottom: 4px; color: #1d2327;">Fournisseur</label>
					<select id="404-preset-id" name="404_alert_smtp_options[preset_id]" style="width: 100%; padding: 7px 10px; border: 1px solid #8c8f94; border-radius: 3px; font-size: 13px;">
						<option value="">— Choisir un fournisseur —</option>
						<?php foreach ( $presets as $key => $preset ) : ?>
							<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $provider_id, $key ); ?>><?php echo esc_html( $preset['name'] ); ?></option>
						<?php endforeach; ?>
					</select>
				</div>

				<div style="margin-bottom: 14px;">
					<label for="404-preset-username" style="display: block; font-size: 12px; font-weight: 600; margin-bottom: 4px; color: #1d2327;">Email / Identifiant</label>
					<input type="text" id="404-preset-username" name="404_alert_smtp_options[preset_username]" style="width: 100%; padding: 7px 10px; border: 1px solid #8c8f94; border-radius: 3px; font-size: 13px;" placeholder="votre@email.com" value="<?php echo esc_attr( $username ); ?>" />
				</div>

				<div style="margin-bottom: 14px;">
					<label for="404-preset-password" style="display: block; font-size: 12px; font-weight: 600; margin-bottom: 4px; color: #1d2327;">Mot de passe / Clé API</label>
					<input type="password" id="404-preset-password" name="404_alert_smtp_options[preset_password]" style="width: 100%; padding: 7px 10px; border: 1px solid #8c8f94; border-radius: 3px; font-size: 13px;" autocomplete="new-password" />
				</div>

				<div id="404-preset-info" style="display: none; background: #f0f6fc; border: 1px solid #72aee6; border-radius: 3px; padding: 10px 12px; font-size: 12px; color: #2271b1; line-height: 1.5; margin-bottom: 14px;"></div>

				<div style="background: #f6f7f7; border: 1px solid #c3c4c7; border-radius: 3px; padding: 12px; margin-bottom: 0;">
					<div style="font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.06em; color: #646970; margin-bottom: 8px;">Paramètres (lecture seule)</div>
					<table style="width: 100%; font-size: 12px; border-collapse: collapse;">
						<tr style="border-bottom: 1px solid #ddd;">
							<td style="padding: 6px 0; font-weight: 600; width: 30%; color: #1d2327;">Serveur</td>
							<td style="padding: 6px 0; color: #2271b1;"><strong id="preset-summary-host">—</strong></td>
						</tr>
						<tr style="border-bottom: 1px solid #ddd;">
							<td style="padding: 6px 0; font-weight: 600; color: #1d2327;">Port</td>
							<td style="padding: 6px 0; color: #2271b1;"><strong id="preset-summary-port">—</strong></td>
						</tr>
						<tr>
							<td style="padding: 6px 0; font-weight: 600; color: #1d2327;">Chiffrement</td>
							<td style="padding: 6px 0; color: #2271b1;"><strong id="preset-summary-encryption">—</strong></td>
						</tr>
					</table>
				</div>

				<!-- Hidden inputs for preset data -->
				<input type="hidden" id="404-preset-host" name="404_alert_smtp_options[preset_host]" value="" />
				<input type="hidden" id="404-preset-port" name="404_alert_smtp_options[preset_port]" value="" />
				<input type="hidden" id="404-preset-encryption" name="404_alert_smtp_options[preset_encryption]" value="" />
			</div>
		</div>

		<?php
}
```

- [ ] **Step 3: Rewrite render_smtp_two_column_form() — Part 2: Accordion 2 (Custom)**

Continue the same method, add Accordion 2:

```php
		<!-- ACCORDION 2: CUSTOM -->
		<div style="border: 1px solid #c3c4c7; border-radius: 4px; overflow: hidden; margin-bottom: 0; border-top: none;">
			<button type="button" class="404-accordion-toggle" data-accordion="custom" style="width: 100%; padding: 15px 20px; background: #f6f7f7; border: none; text-align: left; font-size: 14px; font-weight: 600; cursor: pointer; display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #c3c4c7;">
				<span style="display: flex; align-items: center; gap: 10px;">
					<span style="display: inline-block; width: 8px; height: 8px; border-radius: 50%; background: #c3c4c7; vertical-align: middle;"></span>
					⚙️ Configuration personnalisée
				</span>
				<span class="404-accordion-icon" style="font-size: 16px;">➕</span>
			</button>

			<div id="404-accordion-custom" class="404-accordion-content" style="display: none; padding: 20px 24px; background: #fff;">
				<?php
				$current_host = $smtp_options['host'] ?? '';
				$current_port = $smtp_options['port'] ?? 587;
				$current_enc  = $smtp_options['encryption'] ?? 'tls';
				?>

				<div style="margin-bottom: 14px;">
					<label for="404-custom-host" style="display: block; font-size: 12px; font-weight: 600; margin-bottom: 4px; color: #1d2327;">Serveur SMTP</label>
					<input type="text" id="404-custom-host" name="404_alert_smtp_options[custom_host]" style="width: 100%; padding: 7px 10px; border: 1px solid #8c8f94; border-radius: 3px; font-size: 13px;" placeholder="smtp.exemple.com" value="<?php echo esc_attr( $current_host ); ?>" />
				</div>

				<div style="margin-bottom: 14px;">
					<label for="404-custom-port" style="display: block; font-size: 12px; font-weight: 600; margin-bottom: 4px; color: #1d2327;">Port SMTP</label>
					<input type="number" id="404-custom-port" name="404_alert_smtp_options[custom_port]" style="width: 100%; padding: 7px 10px; border: 1px solid #8c8f94; border-radius: 3px; font-size: 13px;" placeholder="587" value="<?php echo esc_attr( $current_port ); ?>" min="1" max="65535" />
				</div>

				<div style="margin-bottom: 14px;">
					<label for="404-custom-encryption" style="display: block; font-size: 12px; font-weight: 600; margin-bottom: 4px; color: #1d2327;">Chiffrement</label>
					<select id="404-custom-encryption" name="404_alert_smtp_options[custom_encryption]" style="width: 100%; padding: 7px 10px; border: 1px solid #8c8f94; border-radius: 3px; font-size: 13px;">
						<option value="tls" <?php selected( $current_enc, 'tls' ); ?>>TLS</option>
						<option value="ssl" <?php selected( $current_enc, 'ssl' ); ?>>SSL</option>
						<option value="none" <?php selected( $current_enc, 'none' ); ?>>Aucun</option>
					</select>
				</div>

				<div style="margin-bottom: 14px;">
					<label for="404-custom-username" style="display: block; font-size: 12px; font-weight: 600; margin-bottom: 4px; color: #1d2327;">Email / Identifiant</label>
					<input type="text" id="404-custom-username" name="404_alert_smtp_options[custom_username]" style="width: 100%; padding: 7px 10px; border: 1px solid #8c8f94; border-radius: 3px; font-size: 13px;" placeholder="utilisateur ou email" value="<?php echo esc_attr( $username ); ?>" />
				</div>

				<div style="margin-bottom: 14px;">
					<label for="404-custom-password" style="display: block; font-size: 12px; font-weight: 600; margin-bottom: 4px; color: #1d2327;">Mot de passe</label>
					<input type="password" id="404-custom-password" name="404_alert_smtp_options[custom_password]" style="width: 100%; padding: 7px 10px; border: 1px solid #8c8f94; border-radius: 3px; font-size: 13px;" autocomplete="new-password" />
				</div>

				<div style="background: #f6f7f7; border: 1px solid #c3c4c7; border-radius: 3px; padding: 12px; margin-bottom: 0;">
					<div style="font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.06em; color: #646970; margin-bottom: 8px;">Aperçu des paramètres</div>
					<table style="width: 100%; font-size: 12px; border-collapse: collapse;">
						<tr style="border-bottom: 1px solid #ddd;">
							<td style="padding: 6px 0; font-weight: 600; width: 30%; color: #1d2327;">Serveur</td>
							<td style="padding: 6px 0; color: #2271b1;"><strong id="custom-summary-host">—</strong></td>
						</tr>
						<tr style="border-bottom: 1px solid #ddd;">
							<td style="padding: 6px 0; font-weight: 600; color: #1d2327;">Port</td>
							<td style="padding: 6px 0; color: #2271b1;"><strong id="custom-summary-port">—</strong></td>
						</tr>
						<tr style="border-bottom: 1px solid #ddd;">
							<td style="padding: 6px 0; font-weight: 600; color: #1d2327;">Chiffrement</td>
							<td style="padding: 6px 0; color: #2271b1;"><strong id="custom-summary-encryption">—</strong></td>
						</tr>
						<tr style="border-bottom: 1px solid #ddd;">
							<td style="padding: 6px 0; font-weight: 600; color: #1d2327;">Identifiant</td>
							<td style="padding: 6px 0; color: #2271b1;"><strong id="custom-summary-username">—</strong></td>
						</tr>
						<tr>
							<td style="padding: 6px 0; font-weight: 600; color: #1d2327;">Mot de passe</td>
							<td style="padding: 6px 0; color: #2271b1;"><strong id="custom-summary-password">***</strong></td>
						</tr>
					</table>
				</div>
			</div>
		</div>

		<?php
}
```

- [ ] **Step 4: Verify the closing PHP tag**

Ensure the method ends properly with `?>` and the closing `<?php` tags are balanced. The method should close after the final `</div>`.

- [ ] **Step 5: Commit Part 1**

```bash
git add includes/class-alert404-settings.php
git commit -m "refactor: Replace two-column SMTP form with accordion structure (HTML only)"
```

---

## Task 2: Add common fields zone to SMTP form

**Files:**
- Modify: `includes/class-alert404-settings.php:252-383` (add to `render_smtp_two_column_form()`)

- [ ] **Step 1: Add common fields after accordions**

After the closing `</div>` of the custom accordion, add the common fields zone and the rest of the form:

```php
	</div><!-- End accordions wrapper -->

	<!-- COMMON FIELDS: From Email and From Name -->
	<div style="border: 1px solid #c3c4c7; border-top: none; padding: 20px 24px; background: #fcfcfc; border-bottom-left-radius: 4px; border-bottom-right-radius: 4px;">
		<div style="font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.06em; color: #8c8f94; margin-bottom: 14px;">✉️ Paramètres expéditeur (commun aux deux modes)</div>
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

	<!-- Test Connection Button (initially hidden) -->
	<div style="margin-top: 20px; display: none;" id="404-alert-test-section">
		<button type="button" class="button" id="404-alert-smtp-test">Tester la connexion</button>
		<div id="404-alert-test-progress" style="margin-top: 20px; display: none;">
			<div class="alert404-progress-bar-container">
				<div class="alert404-progress-bar" style="width: 0%"></div>
			</div>
			<ul class="alert404-steps-list"></ul>
		</div>
	</div>

	<!-- Config Summary -->
	<div id="404-alert-config-summary" style="margin-top: 30px; padding: 20px; background: linear-gradient(135deg, #f0f7ff 0%, #f9f9f9 100%); border: 2px solid #0073aa; border-radius: 4px;">
		<h3 style="margin-top: 0; color: #0073aa; display: flex; align-items: center; gap: 10px;">
			<span style="font-size: 20px;">📋</span>
			Paramètres SMTP prêts à être enregistrés
		</h3>
		<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
			<div>
				<h4 style="color: #0073aa; margin: 0 0 12px 0; font-size: 13px; text-transform: uppercase; letter-spacing: 0.05em; padding-bottom: 10px; border-bottom: 2px solid #0073aa;">⚙️ Configuration SMTP</h4>
				<table style="width: 100%; font-size: 13px; border-collapse: collapse;">
					<tr style="border-bottom: 1px solid #ddd;">
						<td style="padding: 10px 0; font-weight: 600; width: 40%; color: #333;">Serveur</td>
						<td style="padding: 10px 0; color: #0073aa;"><strong id="summary-host">—</strong></td>
					</tr>
					<tr style="border-bottom: 1px solid #ddd;">
						<td style="padding: 10px 0; font-weight: 600; color: #333;">Port</td>
						<td style="padding: 10px 0; color: #0073aa;"><strong id="summary-port">—</strong></td>
					</tr>
					<tr style="border-bottom: 1px solid #ddd;">
						<td style="padding: 10px 0; font-weight: 600; color: #333;">Chiffrement</td>
						<td style="padding: 10px 0; color: #0073aa;"><strong id="summary-encryption">—</strong></td>
					</tr>
					<tr>
						<td style="padding: 10px 0; font-weight: 600; color: #333;">Identifiant</td>
						<td style="padding: 10px 0; color: #0073aa;"><strong id="summary-username">—</strong></td>
					</tr>
				</table>
			</div>
			<div>
				<h4 style="color: #0073aa; margin: 0 0 12px 0; font-size: 13px; text-transform: uppercase; letter-spacing: 0.05em; padding-bottom: 10px; border-bottom: 2px solid #0073aa;">✉️ Expéditeur</h4>
				<table style="width: 100%; font-size: 13px; border-collapse: collapse;">
					<tr style="border-bottom: 1px solid #ddd;">
						<td style="padding: 10px 0; font-weight: 600; width: 40%; color: #333;">Adresse</td>
						<td style="padding: 10px 0; color: #0073aa;"><strong id="summary-from-email">—</strong></td>
					</tr>
					<tr>
						<td style="padding: 10px 0; font-weight: 600; color: #333;">Nom</td>
						<td style="padding: 10px 0; color: #0073aa;"><strong id="summary-from-name">—</strong></td>
					</tr>
				</table>
			</div>
		</div>
		<p style="margin: 15px 0 0 0; padding-top: 15px; border-top: 1px solid #ddd; font-size: 12px; color: #0073aa; font-weight: 500;">
			✓ Mise à jour en temps réel • Cliquez sur "Save Settings" pour enregistrer
		</p>
	</div>

	<script>
		const a404Presets = <?php echo wp_json_encode( $presets ); ?>;
		const a404CurrentMode = <?php echo wp_json_encode( $provider_id ? 'preset' : 'custom' ); ?>;
	</script>
	<?php
}
```

- [ ] **Step 2: Commit Part 2**

```bash
git add includes/class-alert404-settings.php
git commit -m "refactor: Add common fields zone and config summary to SMTP form"
```

---

## Task 3: Completely rewrite JavaScript state management

**Files:**
- Modify: `assets/js/alert404-smtp-config.js` (complete rewrite)

- [ ] **Step 1: Write new alert404-smtp-config.js with full state management**

Replace the entire file with:

```javascript
/* global a404Presets, a404CurrentMode, jQuery */
jQuery(document).ready(function($) {
  // Global state
  const state = {
    preset: {
      selected: '',
      username: '',
      password: ''
    },
    custom: {
      host: '',
      port: '587',
      encryption: 'tls',
      username: '',
      password: ''
    },
    common: {
      fromEmail: '',
      fromName: ''
    }
  };

  // Accordion state
  const accordionState = {
    preset: false,
    custom: false
  };

  // Initialize on page load
  function init() {
    loadInitialState();
    setupAccordionToggles();
    setupPresetChangeListener();
    setupCustomFieldListeners();
    setupCommonFieldListeners();
    updateAllSummaries();
  }

  // Load initial state from form fields
  function loadInitialState() {
    // Load preset state
    state.preset.selected = $('#404-preset-id').val() || '';
    state.preset.username = $('#404-preset-username').val() || '';
    state.preset.password = $('#404-preset-password').val() || '';

    // Load custom state
    state.custom.host = $('#404-custom-host').val() || '';
    state.custom.port = $('#404-custom-port').val() || '587';
    state.custom.encryption = $('#404-custom-encryption').val() || 'tls';
    state.custom.username = $('#404-custom-username').val() || '';
    state.custom.password = $('#404-custom-password').val() || '';

    // Load common state
    state.common.fromEmail = $('#404-from-email').val() || '';
    state.common.fromName = $('#404-from-name').val() || '';

    // Determine which accordion was active based on provider_id
    if (a404CurrentMode === 'preset' && state.preset.selected) {
      accordionState.preset = true;
    } else if (a404CurrentMode === 'custom' || (state.custom.host && !state.preset.selected)) {
      accordionState.custom = true;
    }

    // Open the appropriate accordion
    if (accordionState.preset) {
      openAccordion('preset');
    } else if (accordionState.custom) {
      openAccordion('custom');
    }
  }

  // Setup accordion toggle buttons
  function setupAccordionToggles() {
    $('.404-accordion-toggle').on('click', function(e) {
      e.preventDefault();
      const target = $(this).data('accordion');
      if (accordionState[target]) {
        closeAccordion(target);
      } else {
        openAccordion(target);
      }
    });
  }

  function openAccordion(type) {
    accordionState[type] = true;
    const content = $('#404-accordion-' + type);
    const toggle = $('[data-accordion="' + type + '"]');

    content.slideDown(300);
    toggle.find('.404-accordion-icon').text('➖');
  }

  function closeAccordion(type) {
    accordionState[type] = false;
    const content = $('#404-accordion-' + type);
    const toggle = $('[data-accordion="' + type + '"]');

    content.slideUp(300);
    toggle.find('.404-accordion-icon').text('➕');
  }

  // Listen for preset selection changes
  function setupPresetChangeListener() {
    $('#404-preset-id').on('change', function() {
      const key = $(this).val();
      state.preset.selected = key;

      if (key && a404Presets[key]) {
        const preset = a404Presets[key];
        // Fill hidden inputs with preset data
        $('#404-preset-host').val(preset.host);
        $('#404-preset-port').val(preset.port);
        $('#404-preset-encryption').val(preset.encryption);

        // Display preset info if available
        if (preset.info) {
          $('#404-preset-info').html(preset.info).show();
        } else {
          $('#404-preset-info').hide();
        }
      } else {
        // Clear preset data if no selection
        $('#404-preset-host').val('');
        $('#404-preset-port').val('');
        $('#404-preset-encryption').val('');
        $('#404-preset-info').hide();
      }

      updateAllSummaries();
    });
  }

  // Listen for custom field changes
  function setupCustomFieldListeners() {
    $('#404-custom-host').on('input', function() {
      state.custom.host = $(this).val();
      updateAllSummaries();
    });

    $('#404-custom-port').on('input change', function() {
      state.custom.port = $(this).val() || '587';
      updateAllSummaries();
    });

    $('#404-custom-encryption').on('change', function() {
      state.custom.encryption = $(this).val();
      updateAllSummaries();
    });

    $('#404-custom-username').on('input', function() {
      state.custom.username = $(this).val();
      updateAllSummaries();
    });

    $('#404-custom-password').on('input', function() {
      state.custom.password = $(this).val();
      updateAllSummaries();
    });
  }

  // Listen for common field changes
  function setupCommonFieldListeners() {
    $('#404-from-email').on('input', function() {
      state.common.fromEmail = $(this).val();
      updateAllSummaries();
    });

    $('#404-from-name').on('input', function() {
      state.common.fromName = $(this).val();
      updateAllSummaries();
    });
  }

  // Update all summary displays
  function updateAllSummaries() {
    updatePresetSummary();
    updateCustomSummary();
    updateMainSummary();
  }

  // Update preset accordion summary
  function updatePresetSummary() {
    const key = state.preset.selected;
    if (key && a404Presets[key]) {
      const preset = a404Presets[key];
      $('#preset-summary-host').text(preset.host);
      $('#preset-summary-port').text(preset.port);
      $('#preset-summary-encryption').text(preset.encryption);
    } else {
      $('#preset-summary-host').text('—');
      $('#preset-summary-port').text('—');
      $('#preset-summary-encryption').text('—');
    }
  }

  // Update custom accordion summary
  function updateCustomSummary() {
    $('#custom-summary-host').text(state.custom.host || '—');
    $('#custom-summary-port').text(state.custom.port || '—');
    $('#custom-summary-encryption').text(state.custom.encryption || '—');
    $('#custom-summary-username').text(state.custom.username || '—');
    $('#custom-summary-password').text(state.custom.password ? '***' : '—');
  }

  // Update main summary (at bottom)
  function updateMainSummary() {
    let host, port, encryption, username;

    if (accordionState.preset && state.preset.selected && a404Presets[state.preset.selected]) {
      const preset = a404Presets[state.preset.selected];
      host = preset.host;
      port = preset.port;
      encryption = preset.encryption;
      username = state.preset.username;
    } else if (accordionState.custom) {
      host = state.custom.host;
      port = state.custom.port;
      encryption = state.custom.encryption;
      username = state.custom.username;
    } else {
      host = port = encryption = username = '—';
    }

    const fromEmail = state.common.fromEmail || '—';
    const fromName = state.common.fromName || '—';

    $('#summary-host').text(host);
    $('#summary-port').text(port);
    $('#summary-encryption').text(encryption);
    $('#summary-username').text(username);
    $('#summary-from-email').text(fromEmail);
    $('#summary-from-name').text(fromName);
  }

  // Initialize on document ready
  init();
});
```

- [ ] **Step 2: Verify the JavaScript file**

Check that all IDs match the HTML form elements:
- `#404-preset-id`, `#404-preset-username`, `#404-preset-password`
- `#404-preset-host`, `#404-preset-port`, `#404-preset-encryption` (hidden)
- `#404-custom-host`, `#404-custom-port`, `#404-custom-encryption`
- `#404-custom-username`, `#404-custom-password`
- `#404-from-email`, `#404-from-name`
- All summary elements match: `#preset-summary-*`, `#custom-summary-*`, `#summary-*`

- [ ] **Step 3: Commit JavaScript rewrite**

```bash
git add assets/js/alert404-smtp-config.js
git commit -m "refactor: Complete rewrite of SMTP JavaScript state management with accordion logic"
```

---

## Task 4: Update PHP sanitization function to handle new field names

**Files:**
- Modify: `includes/class-alert404-settings.php:672-725` (rewrite `sanitize_smtp_options()`)

- [ ] **Step 1: Locate current sanitize_smtp_options() function**

Find the function starting at line 672. Read it completely to understand the current structure.

- [ ] **Step 2: Replace sanitize_smtp_options() with new version**

Replace the entire function with:

```php
/**
 * Valide et nettoie la configuration SMTP avant sauvegarde
 *
 * @param array $input Configuration SMTP fournie par l'utilisateur.
 * @return array<string, mixed> Validated and cleaned SMTP configuration
 */
public static function sanitize_smtp_options( array $input ): array {
	$existing_options = get_option( '404_alert_smtp_options', array() );

	// Traiter les données imbriquées du formulaire (404_alert_smtp_options[key])
	if ( isset( $input['404_alert_smtp_options'] ) && is_array( $input['404_alert_smtp_options'] ) ) {
		$input = $input['404_alert_smtp_options'];
	}

	$preset_id      = isset( $input['preset_id'] ) ? sanitize_text_field( $input['preset_id'] ) : '';
	$custom_host    = isset( $input['custom_host'] ) ? sanitize_text_field( $input['custom_host'] ) : '';
	$custom_port    = isset( $input['custom_port'] ) ? absint( $input['custom_port'] ) : 0;
	$custom_encryption = isset( $input['custom_encryption'] ) ? sanitize_text_field( $input['custom_encryption'] ) : 'tls';
	$custom_username = isset( $input['custom_username'] ) ? sanitize_text_field( $input['custom_username'] ) : '';
	$password_input = isset( $input['preset_password'] ) ? wp_unslash( (string) $input['preset_password'] ) : '';

	// If no preset password, try custom password
	if ( '' === $password_input ) {
		$password_input = isset( $input['custom_password'] ) ? wp_unslash( (string) $input['custom_password'] ) : '';
	}

	// Handle password encryption
	if ( '' === $password_input ) {
		$stored_password = (string) ( $existing_options['password'] ?? '' );
	} else {
		$stored_password = Alert404_SMTP_Handler::encrypt_password_for_storage( $password_input );

		if ( '' === $stored_password ) {
			$stored_password = (string) ( $existing_options['password'] ?? '' );
		}
	}

	// Determine which mode is being used
	$using_preset = ! empty( $preset_id );

	if ( $using_preset ) {
		// MODE: PRESET
		$preset = Alert404_SMTP_Presets::get_preset( $preset_id );

		if ( ! $preset ) {
			// Invalid preset, return error
			return $existing_options;
		}

		$preset_username = isset( $input['preset_username'] ) ? sanitize_text_field( $input['preset_username'] ) : '';

		$new_options = array(
			'provider_id' => $preset_id,
			'host'        => $preset['host'],
			'port'        => $preset['port'],
			'encryption'  => $preset['encryption'],
			'username'    => $preset_username,
			'password'    => $stored_password,
			'from_email'  => isset( $input['from_email'] ) ? sanitize_email( $input['from_email'] ) : get_option( 'admin_email' ),
			'from_name'   => isset( $input['from_name'] ) ? sanitize_text_field( $input['from_name'] ) : get_bloginfo( 'name' ),
		);
	} else {
		// MODE: CUSTOM
		// Validate that all custom fields are provided
		if ( empty( $custom_host ) || empty( $custom_port ) || empty( $custom_username ) ) {
			// If custom mode is incomplete, return existing options without changes
			return $existing_options;
		}

		$new_options = array(
			'provider_id' => 'custom',
			'host'        => $custom_host,
			'port'        => max( 1, min( 65535, $custom_port ) ),
			'encryption'  => in_array( $custom_encryption, array( 'tls', 'ssl', 'none' ), true ) ? $custom_encryption : 'tls',
			'username'    => $custom_username,
			'password'    => $stored_password,
			'from_email'  => isset( $input['from_email'] ) ? sanitize_email( $input['from_email'] ) : get_option( 'admin_email' ),
			'from_name'   => isset( $input['from_name'] ) ? sanitize_text_field( $input['from_name'] ) : get_bloginfo( 'name' ),
		);
	}

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
```

- [ ] **Step 3: Commit sanitization update**

```bash
git add includes/class-alert404-settings.php
git commit -m "refactor: Update sanitize_smtp_options() to handle preset and custom modes"
```

---

## Task 5: Add CSS styles for accordions and animations

**Files:**
- Create: `assets/css/alert404-smtp-config.css`

- [ ] **Step 1: Create new CSS file**

Create a new file at `assets/css/alert404-smtp-config.css` with:

```css
/* Accordion animations */
.404-accordion-toggle {
  transition: background-color 0.2s ease;
}

.404-accordion-toggle:hover {
  background-color: #ececec;
}

.404-accordion-content {
  animation: slideDown 0.3s ease;
}

@keyframes slideDown {
  from {
    opacity: 0;
    max-height: 0;
    overflow: hidden;
  }
  to {
    opacity: 1;
    max-height: 1000px;
  }
}

/* Smooth transitions */
.404-accordion-toggle .404-accordion-icon {
  transition: transform 0.3s ease;
}

/* Summary styling */
#404-alert-config-summary table {
  word-break: break-word;
}

#404-alert-config-summary strong {
  color: #0073aa;
}
```

- [ ] **Step 2: Enqueue the CSS in settings**

In `includes/class-alert404-settings.php`, find the `enqueue_admin_scripts()` method (around line 37). Add the CSS enqueue before the script enqueues:

```php
wp_enqueue_style(
	'404-alert-smtp-config',
	plugin_dir_url( ALERT404_MAIN_FILE ) . 'assets/css/alert404-smtp-config.css',
	array(),
	ALERT404_VERSION
);
```

- [ ] **Step 3: Commit CSS and style enqueue**

```bash
git add assets/css/alert404-smtp-config.css includes/class-alert404-settings.php
git commit -m "feat: Add accordion CSS styles and enqueue stylesheet"
```

---

## Task 6: Test the form in browser and verify data saving

**Files:**
- Test: SMTP settings page in WordPress admin

- [ ] **Step 1: Start development environment**

```bash
cd /home/Baudouin/Documents/Projets/404-alert
# Ensure WordPress is running (if using local dev setup)
```

- [ ] **Step 2: Navigate to SMTP settings page**

Log in to WordPress admin, go to: **404 Alert → Paramètres → SMTP Configuration**

- [ ] **Step 3: Test Scenario A: Select a preset and save**

1. Click "Fournisseur connu" accordion to open it
2. Select "Gmail" from dropdown
3. Enter email: `test@gmail.com`
4. Enter password: `testpassword123`
5. Verify the summary shows: Gmail • smtp.gmail.com:587 • TLS • test@gmail.com
6. Click "Save Settings"
7. Reload the page
8. Verify: "Fournisseur connu" accordion opens automatically with Gmail selected and email filled

**Database check:**
```bash
# Check that provider_id, host, port, encryption, username are saved
wp option get 404_alert_smtp_options
# Expected output should include:
# "provider_id":"gmail",
# "host":"smtp.gmail.com",
# "port":587,
# "encryption":"tls",
# "username":"test@gmail.com"
```

- [ ] **Step 4: Test Scenario B: Switch to custom mode**

1. Open "Configuration personnalisée" accordion
2. Enter:
   - Serveur: `mail.example.com`
   - Port: `587`
   - Chiffrement: `TLS`
   - Email: `admin@example.com`
   - Mot de passe: `custompassword`
3. Verify the custom summary shows all fields
4. Click "Save Settings"
5. Reload the page
6. Verify: "Configuration personnalisée" accordion opens with all fields filled

**Database check:**
```bash
wp option get 404_alert_smtp_options
# Expected:
# "provider_id":"custom",
# "host":"mail.example.com",
# "port":587,
# "encryption":"tls",
# "username":"admin@example.com"
```

- [ ] **Step 5: Test Scenario C: Preserve data when switching accordions**

1. In "Fournisseur connu", select Yahoo and fill email/password
2. Open "Configuration personnalisée" and fill all custom fields
3. Verify Yahoo is still selected in the first accordion
4. Click "Save Settings"
5. Verify the custom config is saved
6. Reload and verify custom accordion opens
7. Click "Fournisseur connu" and verify Yahoo is still selected with email/password

- [ ] **Step 6: Test Scenario D: Partial form should not save**

1. Open "Configuration personnalisée"
2. Fill only Serveur and Port, leave Email/Identifiant and Mot de passe empty
3. Try to click "Save Settings"
4. Verify: You get an error message (this will be validated on form submission)

- [ ] **Step 7: Verify common fields**

1. Enter From Email: `noreply@example.com`
2. Enter From Name: `My Site`
3. Save
4. Reload
5. Verify: From Email and From Name are still filled

- [ ] **Step 8: Final visual check**

- Accordions open/close smoothly
- Summaries update in real-time as you type
- Main summary at bottom shows correct data
- No JavaScript errors in browser console

- [ ] **Step 9: Commit test documentation**

```bash
# No code changes, so no commit needed yet
# But make note of any bugs found for next task
```

---

## Task 7: Add form validation before submission

**Files:**
- Modify: `includes/class-alert404-settings.php` (add validation JavaScript)

- [ ] **Step 1: Add validation function to render_page()**

In the `render_page()` method, before the closing `?>`, add validation JavaScript:

```php
<script>
jQuery(document).ready(function($) {
  const form = $('form[action="options.php"]');
  
  form.on('submit', function(e) {
    const presetId = $('#404-preset-id').val();
    const presetUsername = $('#404-preset-username').val();
    const presetPassword = $('#404-preset-password').val();
    
    const customHost = $('#404-custom-host').val();
    const customPort = $('#404-custom-port').val();
    const customEncryption = $('#404-custom-encryption').val();
    const customUsername = $('#404-custom-username').val();
    const customPassword = $('#404-custom-password').val();
    
    const presetValid = presetId && presetUsername && presetPassword;
    const customValid = customHost && customPort && customEncryption && customUsername && customPassword;
    
    if (!presetValid && !customValid) {
      e.preventDefault();
      alert('Veuillez compléter soit un fournisseur connu (email + mot de passe), soit une configuration personnalisée (tous les champs).');
      return false;
    }
  });
});
</script>
```

- [ ] **Step 2: Commit validation**

```bash
git add includes/class-alert404-settings.php
git commit -m "feat: Add form validation before SMTP config submission"
```

---

## Task 8: Final testing and edge cases

**Files:**
- Test: SMTP settings page

- [ ] **Step 1: Test empty password scenario**

1. Select a preset with email filled
2. Leave password empty
3. Try to save
4. Verify: Error message appears (password is required)

- [ ] **Step 2: Test special characters in fields**

1. Enter email with special chars: `test+alias@gmail.com`
2. Save and reload
3. Verify: Email is preserved correctly

- [ ] **Step 3: Test port validation**

1. In custom mode, try entering port `99999`
2. Save
3. Check database — verify port is clamped to 65535

- [ ] **Step 4: Test encryption values**

1. Set encryption to each option: TLS, SSL, none
2. Save each
3. Verify: Each value is persisted correctly

- [ ] **Step 5: Test from_email and from_name persistence**

1. Change from_email to a different value
2. Switch between accordions multiple times
3. Save
4. Reload
5. Verify: from_email and from_name are preserved

- [ ] **Step 6: Verify no JavaScript errors**

Open browser DevTools Console (F12)
Reload the settings page
Verify: No red errors in console
Verify: All accordion toggles work smoothly

- [ ] **Step 7: Manual smoke test with SMTP test button**

If there's a "Tester la connexion" button:
1. Fill a valid preset (ex: Gmail test account)
2. Click test button
3. Verify: Test uses correct host/port/encryption/username from database
4. (Note: Password testing requires encrypted password to be decrypted correctly)

- [ ] **Step 8: Final commit**

```bash
git status
# Verify all files are committed
git log --oneline -10
# Verify all commits are present
```

---

## Summary

**Total commits:** 8-9
**Files modified:** 3 (PHP, JS, CSS)
**New test coverage:** Manual browser testing

**Key guarantees:**
✅ All provider parameters are saved to database (provider_id + host + port + encryption)
✅ Form validation ensures complete data before saving
✅ Accordions preserve data when switching between modes
✅ Common fields (from_email, from_name) work independently
✅ Page reload correctly determines which accordion to open based on provider_id

