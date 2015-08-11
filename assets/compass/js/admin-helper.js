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
		$parent = $('.gianism-wrap'),
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

	// Syntax highlight
	if($('.gianism-wrap .main-content > pre').length > 0){
		SyntaxHighlighter.all();
	}

    // Google Analytics
    var gaProfile = function(parent, callback){
        var td = $(parent).parents('td');
        td.addClass('loading');
        $.ajax({
            type: 'GET',
            url: Gianism.endpoint,
            dataType: 'json',
            data: {
                nonce: Gianism.nonce,
                action: Gianism.action,
                target: $(parent).attr('id').replace('ga-', ''),
                account_id: $('#ga-account').val(),
                profile_id: $('#ga-profile').val()
            },
            success: function(result){
                td.removeClass('loading');
                if( result.success ){
                    var select = $('#' + $(parent).attr('data-child'));
                    $.each(result.items, function(index, item){
                        var opt = document.createElement('option');
                        $(opt).attr('value', item.id).text(item.name);
                        select.append(opt);
                    });
                    if( callback ){
                        callback(select);
                    }
                }else{
                    window.alert(result.message);
                }
            }
        });
    };

    // Bind profile change.
    $('.ga-profile-select', '#ga-connection').change(function(){
        var threshold = parseInt($(this).attr('data-clear-target'), 10);
        // Clear all
        $('select', '#ga-connection').each(function(index, elt){
            if( index >= threshold ){
                $(elt).find('option').each(function(i, option){
                    if( i > 0 ){
                        $(option).remove();
                    }
                });
            }
        });
        // Search
        gaProfile(this);
    });

    // Fill
    var accountSelect = $('#ga-account');
    if( accountSelect.length && '0' !== accountSelect.attr('data-ga-account-id') ){
        gaProfile(accountSelect, function(profileSelect){
            profileSelect.find('option').each(function(index, option){
                // Make select box checked.
                if( $(option).attr('value') === profileSelect.attr('data-ga-profile-id') ){
                    $(option).prop('selected', true);
                    profileSelect.addClass('success');
                }
            });
            // Ajax
            if( '0' !== profileSelect.attr('data-ga-profile-id') ){
                gaProfile(profileSelect, function(viewSelect){
                    viewSelect.find('option').each(function(index, option){
                        // Make select box checked.
                        if( $(option).attr('value') === viewSelect.attr('data-ga-view-id') ){
                            $(option).prop('selected', true);
                            viewSelect.addClass('success');
                        }
                    });
                });
            }
        });
    }

    // Check status
    var checkGaStatus = function(){
        if( '0' !== $(this).val() ){
            $(this).addClass('success');
        }else{
            $(this).removeClass('success');
        }
    };
    $('select', '#ga-connection')
        .change(checkGaStatus)
        .each(checkGaStatus);


    // Cron checker
    $('#cron-checker').submit(function(e){
        e.preventDefault();
        var form = this;
        $(this).ajaxSubmit({
            dataType: 'json',
            success: function(result){
                $(form).find('pre').text(JSON.stringify(result.items, function(key, value){
                    return value;
                }, 4));
            },
            error: function(xhr, status, msg){
                window.alert(msg);
            }
        });
    });

    // Ajax Checker
    $('#ajax-checker').submit(function(e){
        e.preventDefault();
        var $form = $(this),
            $checked = $form.find("input[type=radio]:checked");
        if( $checked.length ){
            $form.find('input[name=_wpnonce]').val($checked.attr('data-nonce'));
            $form.ajaxSubmit({
                dataType: 'json',
                success: function(result){
                    $form.find('pre').text(JSON.stringify(result, function(key, value){
                        return value;
                    }, 4));

                },
                error: function(xhr, status, msg){
                    window.alert(msg);
                }
            });
        }
    });

});