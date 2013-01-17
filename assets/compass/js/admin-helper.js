jQuery(document).ready(function($){
	//Create index
	$('.gianism-wrap h3').each(function(index, elt){
		$(elt).attr('id', 'index_' + index);
		$('#index ol').append('<li><a href="#index_' + index + '">' + $(elt).text() + '</a></li>');
	});
	//Window scroll
	var $container = $('.gianism-wrap #index'),
		$parent = $('.gianism-wrap'),
		$window = $(window),
		top = $container.offset().top - parseFloat($container.css('marginTop').replace(/auto/, 0)),
		bottom = $parent.offset().top + $parent.height() - $container.outerHeight(true),
		floatingClass = 'floating',
		pinnedBottomClass = 'pinned-bottom';
	if ($parent.height() > $container.outerHeight(true)) {
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
	//Syntax highlight
	if($('.gianism-wrap pre').length > 0){
		SyntaxHighlighter.all();
	}
});