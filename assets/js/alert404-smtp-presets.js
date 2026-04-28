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
				$( this ).css( 'background-color', '#ffe680' );
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
