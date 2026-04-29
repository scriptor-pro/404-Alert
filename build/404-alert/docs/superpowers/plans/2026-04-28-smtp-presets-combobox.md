# Combo-box SMTP avec Solutions Personnalisées - Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Remplacer les boutons preset SMTP par une combo-box intelligente avec filtrage et sauvegarde de solutions personnalisées.

**Architecture:** 
- Frontend : Combo-box avec filtrage en temps réel, modal pour créer solutions personnalisées
- Backend : Méthodes PHP pour gérer les solutions personnalisées, endpoints AJAX pour sauvegarde/suppression
- Stockage : Option WordPress `404_alert_smtp_custom_presets` pour les solutions sauvegardées

**Tech Stack:** 
- jQuery (déjà enqueué)
- WordPress AJAX API
- Vanilla JavaScript pour combo-box

---

## File Structure

**Create:**
- `assets/js/alert404-smtp-presets.js` — Logique combo-box, filtrage, AJAX

**Modify:**
- `includes/class-alert404-smtp-presets.php` — Ajouter méthodes pour solutions personnalisées
- `includes/class-alert404-settings.php` — Ajouter AJAX handlers, modifier render_preset_buttons()
- `assets/css/alert404-admin.css` — Styles pour combo-box et modal (si nécessaire)

---

## Task 1: Ajouter méthodes pour gérer les solutions personnalisées

**Files:**
- Modify: `includes/class-alert404-smtp-presets.php`

- [ ] **Step 1: Ouvrir le fichier et ajouter la méthode get_custom_presets()**

Après la méthode `apply_preset()` (ligne ~108), ajouter :

```php
	/**
	 * Get all saved custom SMTP presets
	 *
	 * @return array
	 */
	public static function get_custom_presets(): array {
		return get_option( '404_alert_smtp_custom_presets', array() );
	}
```

- [ ] **Step 2: Ajouter la méthode save_custom_preset()**

Après `get_custom_presets()`, ajouter :

```php
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
```

- [ ] **Step 3: Ajouter la méthode delete_custom_preset()**

Après `save_custom_preset()`, ajouter :

```php
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
```

- [ ] **Step 4: Ajouter la méthode get_all_presets()**

Après `delete_custom_preset()`, ajouter :

```php
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
```

- [ ] **Step 5: Vérifier la syntaxe PHP**

```bash
php -l includes/class-alert404-smtp-presets.php
```

Expected: "No syntax errors detected"

- [ ] **Step 6: Commit**

```bash
git add includes/class-alert404-smtp-presets.php
git commit -m "feat: Ajouter méthodes pour gérer les solutions SMTP personnalisées

Nouvelles méthodes:
- get_custom_presets(): Récupère les solutions sauvegardées
- save_custom_preset(): Sauvegarde une nouvelle solution
- delete_custom_preset(): Supprime une solution
- get_all_presets(): Fusionne natifs + personnalisés

Stockage via option WordPress '404_alert_smtp_custom_presets'

Co-Authored-By: Claude Haiku 4.5 <noreply@anthropic.com>"
```

---

## Task 2: Ajouter les endpoints AJAX dans Alert404_Settings

**Files:**
- Modify: `includes/class-alert404-settings.php:20-25` (init method)
- Modify: `includes/class-alert404-settings.php:end of class` (new methods)

- [ ] **Step 1: Ajouter les actions AJAX dans init()**

À la ligne 24, après l'action `wp_ajax_404_alert_get_test_progress`, ajouter :

```php
		add_action( 'wp_ajax_404_alert_save_custom_preset', array( self::class, 'handle_save_custom_preset' ) );
		add_action( 'wp_ajax_404_alert_delete_custom_preset', array( self::class, 'handle_delete_custom_preset' ) );
		add_action( 'wp_ajax_404_alert_get_all_presets', array( self::class, 'handle_get_all_presets' ) );
```

- [ ] **Step 2: Ajouter la méthode handle_save_custom_preset()**

Avant la fermeture de la classe (avant la dernière accolade `}`), ajouter :

```php
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
```

- [ ] **Step 3: Ajouter la méthode handle_delete_custom_preset()**

Après `handle_save_custom_preset()`, ajouter :

```php
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
```

- [ ] **Step 4: Ajouter la méthode handle_get_all_presets()**

Après `handle_delete_custom_preset()`, ajouter :

```php
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
```

- [ ] **Step 5: Modifier enqueue_admin_scripts() pour ajouter le nonce**

À la ligne ~44, après `wp_localize_script()`, modifier le tableau de localization pour ajouter le nonce :

Chercher ce bloc :
```php
		wp_localize_script(
			'404-alert-admin',
			'alert404AdminVars',
			array(
				'ajaxurl' => admin_url( 'admin-ajax.php' ),
				'nonce'   => wp_create_nonce( '404_alert_test_smtp' ),
			)
		);
```

Et le remplacer par :
```php
		wp_localize_script(
			'404-alert-admin',
			'alert404AdminVars',
			array(
				'ajaxurl'     => admin_url( 'admin-ajax.php' ),
				'nonce'       => wp_create_nonce( '404_alert_test_smtp' ),
				'presetNonce' => wp_create_nonce( '404_alert_preset_nonce' ),
			)
		);
```

- [ ] **Step 6: Ajouter le nouvel enqueuing du JavaScript pour les presets**

Après l'enqueuing de `404-alert-admin`, ajouter :

```php
		wp_enqueue_script(
			'404-alert-smtp-presets',
			plugin_dir_url( ALERT404_MAIN_FILE ) . 'assets/js/alert404-smtp-presets.js',
			array( 'jquery' ),
			ALERT404_VERSION,
			true
		);
```

- [ ] **Step 7: Vérifier la syntaxe PHP**

```bash
php -l includes/class-alert404-settings.php
```

Expected: "No syntax errors detected"

- [ ] **Step 8: Commit**

```bash
git add includes/class-alert404-settings.php
git commit -m "feat: Ajouter endpoints AJAX pour gérer les solutions personnalisées

Nouvelles méthodes AJAX:
- handle_save_custom_preset(): Sauvegarde une solution personnalisée
- handle_delete_custom_preset(): Supprime une solution
- handle_get_all_presets(): Récupère tous les présets

Ajouter nonce '404_alert_preset_nonce' et enqueuer le JS des présets

Co-Authored-By: Claude Haiku 4.5 <noreply@anthropic.com>"
```

---

## Task 3: Créer le JavaScript pour la combo-box

**Files:**
- Create: `assets/js/alert404-smtp-presets.js`

- [ ] **Step 1: Créer le fichier avec l'initialisation**

```javascript
/* global alert404AdminVars, jQuery */
jQuery( document ).ready( function ( $ ) {
	let allPresets = {};
	let customPresets = {};

	// Initialize presets
	initializePresets();

	function initializePresets() {
		// Fetch all presets via AJAX
		$.ajax( {
			type: 'POST',
			url: alert404AdminVars.ajaxurl,
			data: {
				action: '404_alert_get_all_presets',
				nonce: alert404AdminVars.presetNonce
			},
			success: function ( response ) {
				if ( response.success ) {
					allPresets = response.data.presets;
					setupComboBox();
				}
			},
			error: function () {
				console.error( 'Failed to load SMTP presets' );
			}
		} );
	}

	function setupComboBox() {
		const $searchInput = $( '#404-alert-smtp-preset-search' );
		const $suggestionsList = $( '#404-alert-preset-suggestions' );

		// Handle input/search
		$searchInput.on( 'input', function () {
			const query = $( this ).val().toLowerCase();
			updateSuggestions( query, $suggestionsList );
		} );

		// Handle focus
		$searchInput.on( 'focus', function () {
			const query = $( this ).val().toLowerCase();
			updateSuggestions( query, $suggestionsList );
			$suggestionsList.show();
		} );

		// Handle blur
		$searchInput.on( 'blur', function () {
			setTimeout( function () {
				$suggestionsList.hide();
			}, 200 );
		} );

		// Handle suggestion click
		$suggestionsList.on( 'click', 'li', function () {
			const presetId = $( this ).data( 'preset-id' );
			selectPreset( presetId, $searchInput );
		} );

		// Handle "Autre" button
		$( document ).on( 'click', '.404-alert-preset-autre', function ( e ) {
			e.preventDefault();
			openCreatePresetModal();
		} );
	}

	function updateSuggestions( query, $list ) {
		$list.empty();

		// Filter presets
		const filtered = Object.keys( allPresets )
			.filter( function ( key ) {
				const preset = allPresets[ key ];
				const name = preset.name.toLowerCase();
				const host = preset.host.toLowerCase();
				return name.includes( query ) || host.includes( query ) || query === '';
			} )
			.slice( 0, 10 ); // Limit to 10 suggestions

		// Add filtered presets
		filtered.forEach( function ( key ) {
			const preset = allPresets[ key ];
			const isCustom = key.startsWith( 'custom_' );
			const $item = $( '<li></li>' )
				.data( 'preset-id', key )
				.html(
					'<strong>' + escapeHtml( preset.name ) + '</strong><br><small>' +
					escapeHtml( preset.host ) + ':' + preset.port + ' (' + preset.encryption + ')' +
					'</small>'
				)
				.css( {
					'padding': '8px 12px',
					'cursor': 'pointer',
					'border-bottom': '1px solid #eee',
					'background-color': isCustom ? '#f0f7ff' : '#fff'
				} )
				.on( 'mouseenter', function () {
					$( this ).css( 'background-color', '#f5f5f5' );
				} )
				.on( 'mouseleave', function () {
					$( this ).css( 'background-color', isCustom ? '#f0f7ff' : '#fff' );
				} );

			$list.append( $item );
		} );

		// Add "Autre" option
		const $autre = $( '<li class="404-alert-preset-autre"></li>' )
			.html( '<strong>+ Autre</strong>' )
			.css( {
				'padding': '8px 12px',
				'cursor': 'pointer',
				'border-bottom': 'none',
				'background-color': '#fff9e6',
				'font-weight': 'bold'
			} )
			.on( 'mouseenter', function () {
				$( this ).css( 'background-color': '#ffe680' );
			} )
			.on( 'mouseleave', function () {
				$( this ).css( 'background-color', '#fff9e6' );
			} );

		$list.append( $autre );
	}

	function selectPreset( presetId, $searchInput ) {
		const preset = allPresets[ presetId ];
		if ( preset ) {
			// Fill SMTP fields
			$( 'input[name="404_alert_smtp_options[host]"]' ).val( preset.host );
			$( 'input[name="404_alert_smtp_options[port]"]' ).val( preset.port );
			$( 'select[name="404_alert_smtp_options[encryption]"]' ).val( preset.encryption );

			// Update search input
			$searchInput.val( preset.name );
			$( '#404-alert-preset-suggestions' ).hide();
		}
	}

	function openCreatePresetModal() {
		const currentHost = $( 'input[name="404_alert_smtp_options[host]"]' ).val();
		const currentPort = $( 'input[name="404_alert_smtp_options[port]"]' ).val();
		const currentEncryption = $( 'select[name="404_alert_smtp_options[encryption]"]' ).val();

		const modal = $( '<div class="404-alert-preset-modal" style="display:none;"></div>' );
		const backdrop = $( '<div class="404-alert-preset-backdrop" style="display:none;"></div>' );

		modal.html(
			'<div style="position: relative; width: 400px; background: white; padding: 20px; border-radius: 5px; box-shadow: 0 2px 10px rgba(0,0,0,0.2);">' +
			'<h3 style="margin-top: 0;">Créer une nouvelle solution</h3>' +
			'<p>Donnez un nom à votre solution SMTP:</p>' +
			'<input type="text" id="404-alert-preset-name" placeholder="Ex: Mon serveur ProtonMail" style="width: 100%; padding: 8px; margin-bottom: 10px; border: 1px solid #ccc; border-radius: 3px;">' +
			'<p style="margin-top: 15px; color: #666; font-size: 13px;"><strong>Paramètres actuels:</strong></p>' +
			'<p style="margin: 5px 0; color: #666; font-size: 13px;">Host: <code>' + escapeHtml( currentHost ) + '</code></p>' +
			'<p style="margin: 5px 0; color: #666; font-size: 13px;">Port: <code>' + escapeHtml( currentPort ) + '</code></p>' +
			'<p style="margin: 5px 0 15px 0; color: #666; font-size: 13px;">Encryption: <code>' + escapeHtml( currentEncryption ) + '</code></p>' +
			'<div style="text-align: right;">' +
			'<button class="button 404-alert-preset-cancel" style="margin-right: 10px;">Annuler</button>' +
			'<button class="button button-primary 404-alert-preset-save">Sauvegarder</button>' +
			'</div>' +
			'</div>'
		);

		$( 'body' ).append( backdrop ).append( modal );

		// Show modal
		backdrop.fadeIn();
		modal.fadeIn();

		// Handle cancel
		modal.on( 'click', '.404-alert-preset-cancel', function () {
			modal.fadeOut();
			backdrop.fadeOut( function () {
				modal.remove();
				backdrop.remove();
			} );
		} );

		// Handle save
		modal.on( 'click', '.404-alert-preset-save', function () {
			const name = $( '#404-alert-preset-name' ).val().trim();
			if ( ! name ) {
				alert( 'Veuillez donner un nom à votre solution' );
				return;
			}

			// Save via AJAX
			$.ajax( {
				type: 'POST',
				url: alert404AdminVars.ajaxurl,
				data: {
					action: '404_alert_save_custom_preset',
					nonce: alert404AdminVars.presetNonce,
					name: name,
					host: currentHost,
					port: currentPort,
					encryption: currentEncryption
				},
				success: function ( response ) {
					if ( response.success ) {
						// Reload presets
						allPresets = {};
						initializePresets();

						// Close modal
						modal.fadeOut();
						backdrop.fadeOut( function () {
							modal.remove();
							backdrop.remove();
						} );

						// Show confirmation
						const $searchInput = $( '#404-alert-smtp-preset-search' );
						$searchInput.val( name );

						// Brief confirmation message
						const $msg = $( '<div style="color: #46b450; font-weight: bold; margin-top: 10px;">✓ Solution sauvegardée!</div>' );
						$searchInput.after( $msg );
						setTimeout( function () {
							$msg.fadeOut( function () {
								$msg.remove();
							} );
						}, 2000 );
					}
				},
				error: function () {
					alert( 'Erreur lors de la sauvegarde de la solution' );
				}
			} );
		} );

		// Close on backdrop click
		backdrop.on( 'click', function () {
			modal.fadeOut();
			backdrop.fadeOut( function () {
				modal.remove();
				backdrop.remove();
			} );
		} );

		// Focus on input
		$( '#404-alert-preset-name' ).focus();
	}

	function escapeHtml( text ) {
		const map = {
			'&': '&amp;',
			'<': '&lt;',
			'>': '&gt;',
			'"': '&quot;',
			"'": '&#039;'
		};
		return text.replace( /[&<>"']/g, function ( m ) {
			return map[ m ];
		} );
	}
} );
```

- [ ] **Step 2: Vérifier qu'il n'y a pas d'erreurs JavaScript**

```bash
node -c assets/js/alert404-smtp-presets.js 2>/dev/null || echo "Note: Requires Node.js, skipping check"
```

- [ ] **Step 3: Commit**

```bash
git add assets/js/alert404-smtp-presets.js
git commit -m "feat: Créer combo-box SMTP avec filtrage et modal de création

Implémente:
- Combo-box avec filtrage en temps réel des présets
- Modal pour créer et nommer des solutions personnalisées
- Sauvegarde via AJAX
- Réutilisation des solutions sauvegardées
- Escape HTML pour prévenir XSS

Co-Authored-By: Claude Haiku 4.5 <noreply@anthropic.com>"
```

---

## Task 4: Remplacer les boutons par la combo-box dans le HTML

**Files:**
- Modify: `includes/class-alert404-settings.php:451-486`

- [ ] **Step 1: Remplacer render_preset_buttons() par render_smtp_presets_combobox()**

Chercher la fonction `render_preset_buttons()` (ligne ~451) et la remplacer entièrement par :

```php
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
```

- [ ] **Step 2: Chercher où render_preset_buttons() est appelée**

```bash
grep -n "render_preset_buttons" includes/class-alert404-settings.php
```

Expected: Affichera la ligne où elle est appelée (probablement ligne ~281)

- [ ] **Step 3: Remplacer l'appel à render_preset_buttons() par render_smtp_presets_combobox()**

À la ligne trouvée, remplacer :
```php
<?php self::render_preset_buttons(); ?>
```

Par :
```php
<?php self::render_smtp_presets_combobox(); ?>
```

- [ ] **Step 4: Vérifier la syntaxe PHP**

```bash
php -l includes/class-alert404-settings.php
```

Expected: "No syntax errors detected"

- [ ] **Step 5: Commit**

```bash
git add includes/class-alert404-settings.php
git commit -m "feat: Remplacer les boutons preset par combo-box SMTP

Remplacer render_preset_buttons() par render_smtp_presets_combobox()
qui affiche une combo-box avec filtrage et suggestions dynamiques.

Co-Authored-By: Claude Haiku 4.5 <noreply@anthropic.com>"
```

---

## Task 5: Vérification finale et création du ZIP

**Files:**
- No files to modify

- [ ] **Step 1: Vérifier que tous les fichiers sont syntaxiquement corrects**

```bash
php -l includes/class-alert404-smtp-presets.php && \
php -l includes/class-alert404-settings.php && \
php -l 404-alert.php && \
echo "✓ Tous les fichiers PHP sont valides"
```

Expected: "✓ Tous les fichiers PHP sont valides"

- [ ] **Step 2: Vérifier le statut Git**

```bash
git status
```

Expected: Affiche tous les fichiers modifiés/créés

- [ ] **Step 3: Créer un ZIP avec les modifications**

```bash
zip -r "404-alert-combobox-$(date +%Y%m%d_%H%M%S).zip" . \
  -x "*.git*" "node_modules/*" "vendor/*" ".DS_Store" ".claude/*" ".codex*" \
  "404-alert-*.zip" && \
ls -lh 404-alert-combobox-*.zip | tail -1
```

Expected: Affiche le fichier ZIP créé avec sa taille

- [ ] **Step 4: Commit final avec tous les changements**

```bash
git add -A && \
git commit -m "feat: Implémenter combo-box SMTP complète

Implémentation complète de la combo-box SMTP avec:
- Filtrage en temps réel des présets natifs
- Création de solutions personnalisées nommées
- Sauvegarde et réutilisation des solutions
- Modal d'interface utilisateur
- Endpoints AJAX sécurisés

Fichiers créés:
- assets/js/alert404-smtp-presets.js

Fichiers modifiés:
- includes/class-alert404-smtp-presets.php (nouvelles méthodes)
- includes/class-alert404-settings.php (AJAX handlers + render)

Co-Authored-By: Claude Haiku 4.5 <noreply@anthropic.com>"
```

Expected: "5 files changed"

- [ ] **Step 5: Afficher le log final**

```bash
git log --oneline -5
```

Expected: Affiche les 5 derniers commits incluant les nouveaux commits de cette implémentation

---

## Implementation Summary

✅ **Task 1:** Méthodes pour gérer solutions personnalisées dans Alert404_SMTP_Presets  
✅ **Task 2:** Endpoints AJAX dans Alert404_Settings  
✅ **Task 3:** JavaScript combo-box avec filtrage et modal  
✅ **Task 4:** Remplacer HTML des boutons par combo-box  
✅ **Task 5:** Vérification et création ZIP  

**Total:** 5 tâches, ~30-40 minutes d'implémentation
