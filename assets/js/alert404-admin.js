/* global alert404AdminVars, jQuery */
jQuery( document ).ready( function ( $ ) {
	let pollingInterval = null;
	let testInProgress = false;

	$( '#404-alert-smtp-test' ).on( 'click', function ( e ) {
		e.preventDefault();

		if ( testInProgress ) {
			return;
		}

		testInProgress = true;
		var $btn = $( this );
		var $progressContainer = $( '#404-alert-test-progress' );

		$btn.prop( 'disabled', true ).text( 'Test en cours...' );
		$progressContainer.show();

		// Initialize progress display
		initProgressDisplay();

		// Collect form data from visible fields
		var smtpFormData = collectSmtpFormData();

		// Start the SMTP test
		$.ajax( {
			type: 'POST',
			url: alert404AdminVars.ajaxurl,
			data: {
				action: '404_alert_test_smtp',
				_wpnonce: alert404AdminVars.nonce,
				formData: JSON.stringify( smtpFormData )
			},
			success: function () {
				// Test started, begin polling
				startPolling();
			},
			error: function () {
				$progressContainer.html( '<p style="color: #c33;">❌ Erreur lors du test de connexion.</p>' );
				$btn.prop( 'disabled', false ).text( 'Tester la connexion' );
				testInProgress = false;
			}
		} );
	} );

	function collectSmtpFormData() {
		// Déterminer si la colonne gauche (preset) ou droite (custom) est active
		var colLeft = $( '#404-smtp-col-left' );
		var colRight = $( '#404-smtp-col-right' );

		var isLeftActive = colLeft.hasClass( 'active' );

		if ( isLeftActive ) {
			// Mode preset
			return {
				host: $( '#404-left-host' ).val(),
				port: $( '#404-left-port' ).val(),
				encryption: $( '#404-left-encryption' ).val(),
				username: $( '#404-left-username' ).val(),
				password: $( '#404-left-password' ).val(),
				from_email: $( '#404-from-email' ).val(),
				from_name: $( '#404-from-name' ).val()
			};
		} else {
			// Mode custom
			return {
				host: $( '#404-right-host' ).val(),
				port: $( '#404-right-port' ).val(),
				encryption: $( '#404-right-encryption' ).val(),
				username: $( '#404-right-username' ).val(),
				password: $( '#404-right-password' ).val(),
				from_email: $( '#404-from-email' ).val(),
				from_name: $( '#404-from-name' ).val()
			};
		}
	}

	function initProgressDisplay() {
		var $container = $( '#404-alert-test-progress' );
		var progressHtml = '<div class="alert404-progress-bar-container"><div class="alert404-progress-bar" style="width: 0%"></div></div><ul class="alert404-steps-list"></ul>';
		$container.html( progressHtml );
	}

	function startPolling() {
		var pollAttempts = 0;
		var maxAttempts = 120; // 60 seconds with 500ms interval

		pollingInterval = setInterval( function () {
			pollAttempts++;

			$.ajax( {
				type: 'POST',
				url: alert404AdminVars.ajaxurl,
				data: {
					action: '404_alert_get_test_progress',
					_wpnonce: alert404AdminVars.nonce
				},
				success: function ( response ) {
					if ( response.success ) {
						updateProgressDisplay( response.data );

						// Check if test is complete
						if ( ! response.data.is_running ) {
							completeTest();
						}
					}
				},
				error: function () {
					// Retry on error
					if ( pollAttempts >= maxAttempts ) {
						completeTest();
					}
				}
			} );

			// Stop polling after timeout
			if ( pollAttempts >= maxAttempts ) {
				completeTest();
			}
		}, 500 );
	}

	function updateProgressDisplay( progress ) {
		var $container = $( '#404-alert-test-progress' );
		var $progressBar = $container.find( '.alert404-progress-bar' );
		var $stepsList = $container.find( '.alert404-steps-list' );

		// Update progress bar
		$progressBar.css( 'width', progress.progress + '%' );

		// Update steps list
		if ( progress.steps && progress.steps.length > 0 ) {
			var stepsHtml = '';
			$.each( progress.steps, function ( index, step ) {
				var icon = getIconForStatus( step.status );
				var stepClass = 'alert404-step ' + step.status;

				stepsHtml += '<li class="' + stepClass + '">';
				stepsHtml += '<div class="alert404-step-icon">' + icon + '</div>';
				stepsHtml += '<div class="alert404-step-text">';
				stepsHtml += '<span class="alert404-step-label">' + step.step + '</span>';
				if ( step.message ) {
					stepsHtml += '<span class="alert404-step-message">' + escapeHtml( step.message ) + '</span>';
				}
				stepsHtml += '</div>';
				stepsHtml += '</li>';
			} );

			$stepsList.html( stepsHtml );
		}
	}

	function getIconForStatus( status ) {
		switch ( status ) {
			case 'pending':
				return '⏳';
			case 'running':
				return '⌛';
			case 'success':
				return '✓';
			case 'error':
				return '✗';
			default:
				return '○';
		}
	}

	function escapeHtml( text ) {
		var map = {
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

	function completeTest() {
		if ( pollingInterval ) {
			clearInterval( pollingInterval );
			pollingInterval = null;
		}

		var $btn = $( '#404-alert-smtp-test' );
		$btn.prop( 'disabled', false ).text( 'Tester la connexion' );

		testInProgress = false;
	}
} );
