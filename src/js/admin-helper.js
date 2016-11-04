/*!
 * Admin screen helper for Gianism
 */

jQuery(document).ready(function($){

    "use strict";

	// Create appendix
	$('.gianism-wrap').find( 'h3,caption' ).each(function(index, elt){
		$(elt).attr('id', 'index_' + index);
		$('#index-list').append('<li><a href="#index_' + index + '">' + $(elt).text() + '</a></li>');
	});
});