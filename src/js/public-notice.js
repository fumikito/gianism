/**
 * Read message from cookie
 */


jQuery(document).ready(function($){

    'use strict';

    // Activate JSON parse
    $.cookie.json = true;

    // Create message boxes
    var divs = [],
        container = $('<div class="wpg-notices toggle"></div>'),
        str = ['updated', 'error'];
    for( var i = 0; i < 2; i++){
        var key_name = 'gianism_' + str[i],
            messages = $.cookie(key_name);
        if( messages ){
            // Message exists.
            var div = $('<div><p></p></div>');
            div.addClass(str[i]).find('p').html(messages.join('<br />'));
            divs.push(div);
            // Delete cookie
            $.removeCookie(key_name, {
                path: '/'
            });
        }
    }
    if( divs.length ){
        // Add all div
        $.each(divs, function(index, div){
            // Append each div to container.
            container.append(div);
            // If not admin, append close button
            $(div).append('<a href="#close" class="close-btn"><i class="lsf lsf-close"></i></a>').click(function(e){
                e.preventDefault();
                $(div).remove();
            });
            // Add container to footer.
            $('body').append(container);
            // Fade in, then
            container.removeClass('toggle').find('div.updated, div.error').effect('highlight', {}, 1000, function(){
                setTimeout(function(){
                    container.fadeOut(2000, function(){
                        $(this).remove();
                    });
                }, 15000);
            });
        });
    }
});