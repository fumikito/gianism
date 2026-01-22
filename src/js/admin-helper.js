/*!
 * Admin screen helper for Gianism
 *
 * @handle giansim-admin-helper
 * @deps jquery-effects-highlight
 */

jQuery( document ).ready( function( $ ) {
	'use strict';

	// Create appendix
	$( '.gianism-wrap' ).find( 'h3,caption' ).each( function( index, elt ) {
		$( elt ).attr( 'id', 'index_' + index );
		$( '#index-list' ).append( '<li><a href="#index_' + index + '">' + $( elt ).text() + '</a></li>' );
	} );

	// Notice dismiss
	$( '.gianism-admin-notice' ).click( function( e ) {
		e.preventDefault();
		const $btn = $( this );
		$.get( $btn.attr( 'data-endpoint' ) ).done( function( response ) {
			if ( response.success ) {
				$btn.parents( 'div.error' ).remove();
			} else {
				window.alert( 'Error' );
			}
		} ).fail( function( response ) {
			window.alert( response.responseJSON.data );
		} );
	} );

	// Toggle conditional inputs.
	$( '.gianism-toggle' ).each( function( index, p ) {
		const $p = $( p );
		const $target = $( $p.attr( 'data-target' ) );
		const list = $p.attr( 'data-valid' ).split( ',' );
		if ( -1 < list.indexOf( $target.val() ) ) {
			$p.addClass( 'toggle' );
		}
		$target.change( function() {
			if ( -1 < list.indexOf( $( this ).val() ) ) {
				$p.addClass( 'toggle' ).effect( 'highlight' );
			} else if ( $p.hasClass( 'toggle' ) ) {
				$p.effect( 'highlight', {}, 400, function() {
					$p.removeClass( 'toggle' );
				} );
			}
		} );
	} );
} );
