( function ( $ ) {
	'use strict';

	$( function () {
		var config = window.enjinmelSmtpSettings || {};
		var strings = config.strings || {};

		$( '#enjinmel_smtp_toggle_api_key' ).on( 'click', function () {
			var input = $( '#enjinmel_smtp_api_key' );
			var button = $( this );

			if ( 'password' === input.attr( 'type' ) ) {
				input.attr( 'type', 'text' );
				button.text( strings.hide );
			} else {
				input.attr( 'type', 'password' );
				button.text( strings.show );
			}
		} );

		$( '#enjinmel_smtp_send_test' ).on( 'click', function ( event ) {
			var result = $( '#enjinmel_smtp_test_result' );

			event.preventDefault();
			result.text( strings.sending );

			$.post( config.ajaxUrl, {
				action: config.action,
				nonce: config.nonce,
				to: $( '#enjinmel_smtp_test_to' ).val()
			} ).done( function ( response ) {
				var message;

				if ( response && response.success ) {
					result.text( strings.success );
					return;
				}

				message = response && response.data && response.data.message ? response.data.message : strings.failed;
				result.text( strings.errorPrefix + message );
			} ).fail( function () {
				result.text( strings.requestFailed );
			} );
		} );
	} );
}( jQuery ) );
