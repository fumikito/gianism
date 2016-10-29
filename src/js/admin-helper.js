/*!
 * Admin screen helper for Gianism
 */

/*global SyntaxHighlighter:true*/
/*global Gianism:true*/

jQuery(document).ready(function($){

    "use strict";

	// Create appendix
	$('.gianism-wrap h3').each(function(index, elt){
		$(elt).attr('id', 'index_' + index);
		$('#index ol').append('<li><a href="#index_' + index + '">' + $(elt).text() + '</a></li>');
	});

	// Sidebar's Window scroll
	var $container = $('.gianism-wrap #index'),
		$parent = $('.gianism-inner'),
		$window = $(window);
	if ( $container.length && $parent.height() > $container.outerHeight(true)) {
		var top = $container.offset().top - parseFloat($container.css('marginTop').replace(/auto/, 0)),
            bottom = $parent.offset().top + $parent.height() - $container.outerHeight(true),
            floatingClass = 'floating',
            pinnedBottomClass = 'pinned-bottom';
		$window.scroll(function () {
			var y = $window.scrollTop();
			if (y > top) {
				$container.addClass(floatingClass);
				if (y > bottom) {
					$container.removeClass(floatingClass).addClass(pinnedBottomClass);
				} else {
					$container.removeClass(pinnedBottomClass);
				}
			} else {
				$container.removeClass(floatingClass);
			}
		});
	}

});