/* Media picker for the PDF field in the Publication details box. */
( function ( $ ) {
	var frame;

	$( document ).on( 'click', '.iat-pub-pdf-choose', function ( event ) {
		var $field = $( this ).closest( '.iat-pub-pdf' );

		event.preventDefault();

		if ( ! frame ) {
			frame = wp.media( {
				title: $field.data( 'title' ),
				button: { text: $field.data( 'button' ) },
				library: { type: 'application/pdf' },
				multiple: false
			} );

			frame.on( 'select', function () {
				var file = frame.state().get( 'selection' ).first().toJSON();

				$field.find( 'input[name="iat_pub_pdf_id"]' ).val( file.id );
				$field.find( '.iat-pub-pdf-name' ).attr( 'href', file.url ).text( file.filename ).prop( 'hidden', false );
				$field.find( '.iat-pub-pdf-remove' ).prop( 'hidden', false );
			} );
		}

		frame.open();
	} );

	$( document ).on( 'click', '.iat-pub-pdf-remove', function ( event ) {
		var $field = $( this ).closest( '.iat-pub-pdf' );

		event.preventDefault();

		$field.find( 'input[name="iat_pub_pdf_id"]' ).val( '' );
		$field.find( '.iat-pub-pdf-name' ).attr( 'href', '' ).text( '' ).prop( 'hidden', true );
		$( this ).prop( 'hidden', true );
	} );
} )( jQuery );
