/* global alert404AdminVars, jQuery */
jQuery( document ).ready( function ( $ ) {
	$( '#404-alert-smtp-test' ).on( 'click', function ( e ) {
		e.preventDefault();
		var $btn = $( this );
		var $result = $( '#404-alert-smtp-test-result' );

		$btn.prop( 'disabled', true ).text( 'Test en cours...' );
		$result.show().html( '<p style="color: #999;">Vérification de la connexion...</p>' );

		$.ajax( {
			type: 'POST',
			url: alert404AdminVars.ajaxurl,
			data: {
				action: '404_alert_test_smtp',
				nonce: alert404AdminVars.nonce
			},
			success: function ( response ) {
				if ( response.success ) {
					$result.html( '<p style="color: #090; font-weight: bold;">✓ ' + response.data.message + '</p>' );
				} else {
					$result.html( '<p style="color: #c33; font-weight: bold;">✗ ' + response.data.message + '</p>' );
				}
			},
			error: function () {
				$result.html( '<p style="color: #c33;">Erreur lors du test de connexion.</p>' );
			},
			complete: function () {
				$btn.prop( 'disabled', false ).text( 'Tester la connexion' );
			}
		} );
	} );
} );
