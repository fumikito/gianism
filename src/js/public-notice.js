/**
 * Read message from cookie
 */


jQuery(document).ready(function($){

    'use strict';

    window.GianSays = {
        divs: [],
        str: ['updated', 'error'],
        pushMsg: function(msg, className){
            var div = $('<div><p></p></div>');
            div.addClass(className).find('p').html( msg );
            this.divs.push(div);
        },
        grabMessage: function(){
            for( var i = 0; i < 2; i++){
                var key_name = 'gianism_' + this.str[i],
                    messages = Cookies.getJSON(key_name);
                if( messages ){
                    // Message exists.
                    for(var j = 0, k = messages.length; j < k; j++){
                        this.pushMsg( messages[j], this.str[i] );
                    }
                    // Delete cookie
                    Cookies.remove(key_name, {
                        path: '/'
                    });
                }
            }
        },
        flushMessage: function(){
            if ( this.divs.length ) {
                // Add all div
                var $container = $('<div class="wpg-notices toggle"></div>');
                $.each(this.divs, function(index, div){
                    // Append each div to container.
                    $container.append(div);
                    // Append close button
                    $(div).append('<a href="#close" class="close-btn"><i class="lsf lsf-close"></i></a>').click(function(e){
                        e.preventDefault();
                        $(div).remove();
                    });
                    // Add container to footer.
                    $('body').append($container);
                    // Fade in, then
                    $container.removeClass('toggle').find('div.updated, div.error').effect('highlight', {}, 1000, function(){
                        setTimeout(function(){
                            $container.fadeOut(2000, function(){
                                $(this).remove();
                            });
                        }, 15000);
                    });
                });
                this.div = [];
            }
        }
    };

    // Flush message if set.
    GianSays.grabMessage();
    GianSays.flushMessage();

    // Debug mode.
    var match = /gianism_debug_message=(updated|error)/.exec(window.location.hash);
    if(match){
        GianSays.pushMsg('This is a test message. Just check for that.', match[1]);
        GianSays.flushMessage();
    }

});