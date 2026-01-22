/*!
 * Google analytics helper
 *
 * @handle gianism-analytics-helper
 * @deps jquery-form
 */

/*global Gianalytics:false*/

jQuery( document ).ready( function( $ ) {
	'use strict';

	// Google Analytics
	const gaProfile = function( parent, callback ) {
		const td = $( parent ).parents( 'td' );
		td.addClass( 'loading' );
		$.ajax( {
			type: 'GET',
			url: Gianalytics.endpoint,
			dataType: 'json',
			data: {
				nonce: Gianalytics.nonce,
				action: Gianalytics.action,
				target: $( parent ).attr( 'id' ).replace( 'ga-', '' ),
				account_id: $( '#ga-account' ).val(),
				profile_id: $( '#ga-profile' ).val(),
			},
			success: function( result ) {
				td.removeClass( 'loading' );
				if ( result.success ) {
					const select = $( '#' + $( parent ).attr( 'data-child' ) );
					$.each( result.items, function( index, item ) {
						const opt = document.createElement( 'option' );
						$( opt ).attr( 'value', item.id ).text( item.name );
						select.append( opt );
					} );
					if ( callback ) {
						callback( select );
					}
				} else {
					window.alert( result.message );
				}
			},
		} );
	};

	// Bind profile change.
	$( '.ga-profile-select', '#ga-connection' ).change( function() {
		const threshold = parseInt( $( this ).attr( 'data-clear-target' ), 10 );
		// Clear all
		$( 'select', '#ga-connection' ).each( function( index, elt ) {
			if ( index >= threshold ) {
				$( elt ).find( 'option' ).each( function( i, option ) {
					if ( i > 0 ) {
						$( option ).remove();
					}
				} );
			}
		} );
		// Search
		gaProfile( this );
	} );

	// Fill
	const accountSelect = $( '#ga-account' );
	if ( accountSelect.length && '0' !== accountSelect.attr( 'data-ga-account-id' ) ) {
		gaProfile( accountSelect, function( profileSelect ) {
			profileSelect.find( 'option' ).each( function( index, option ) {
				// Make select box checked.
				if ( $( option ).attr( 'value' ) === profileSelect.attr( 'data-ga-profile-id' ) ) {
					$( option ).prop( 'selected', true );
					profileSelect.addClass( 'success' );
				}
			} );
			// Ajax
			if ( '0' !== profileSelect.attr( 'data-ga-profile-id' ) ) {
				gaProfile( profileSelect, function( viewSelect ) {
					viewSelect.find( 'option' ).each( function( index, option ) {
						// Make select box checked.
						if ( $( option ).attr( 'value' ) === viewSelect.attr( 'data-ga-view-id' ) ) {
							$( option ).prop( 'selected', true );
							viewSelect.addClass( 'success' );
						}
					} );
				} );
			}
		} );
	}

	// Check status
	const checkGaStatus = function() {
		if ( '0' !== $( this ).val() ) {
			$( this ).addClass( 'success' );
		} else {
			$( this ).removeClass( 'success' );
		}
	};
	$( 'select', '#ga-connection' )
		.change( checkGaStatus )
		.each( checkGaStatus );

	// Cron checker
	$( '#cron-checker' ).submit( function( e ) {
		e.preventDefault();
		const form = this;
		$( this ).ajaxSubmit( {
			dataType: 'json',
			success: function( result ) {
				$( form ).find( 'pre' ).text( JSON.stringify( result.items, function( key, value ) {
					return value;
				}, 4 ) );
			},
			error: function( xhr, status, msg ) {
				window.alert( msg );
			},
		} );
	} );

	// Ajax Checker
	$( '#ajax-checker' ).submit( function( e ) {
		e.preventDefault();
		const $form = $( this ),
			$checked = $form.find( 'input[type=radio]:checked' );
		if ( $checked.length ) {
			$form.find( 'input[name=_wpnonce]' ).val( $checked.attr( 'data-nonce' ) );
			$form.ajaxSubmit( {
				dataType: 'json',
				success: function( result ) {
					$form.find( 'pre' ).text( JSON.stringify( result, function( key, value ) {
						return value;
					}, 4 ) );
				},
				error: function( xhr, status, msg ) {
					window.alert( msg );
				},
			} );
		}
	} );
} );
