/**
 * Settings page: add/remove per-page message rows.
 */
( function ( $ ) {
	'use strict';

	var container = $( '#acwppm-rows' );
	var template = $( '#acwppm-row-template' );

	function nextIndex() {
		var max = -1;
		container.find( '.acwppm-row select' ).each( function () {
			var match = $( this ).attr( 'name' ).match( /\[page_messages\]\[(\d+)\]/ );
			if ( match && parseInt( match[ 1 ], 10 ) > max ) {
				max = parseInt( match[ 1 ], 10 );
			}
		} );
		return max + 1;
	}

	$( '#acwppm-add-row' ).on( 'click', function () {
		var html = template.html().replace( /__INDEX__/g, nextIndex() );
		container.append( html );
	} );

	container.on( 'click', '.acwppm-remove-row', function () {
		$( this ).closest( '.acwppm-row' ).remove();
	} );
} )( jQuery );
