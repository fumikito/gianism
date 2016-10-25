jQuery(document).ready(function($){

    'use strict';

    /**
     * Show message on button
     *
     * @param message
     */
    var showMessage = function(message){
        var msgBox = $('<p class="message-box">' + message + '</p>');
        $('#chat-more').before(msgBox);
        msgBox.effect('highlight', {duration: 1000}).delay(5000).fadeOut(1000, function(){
            $(this).remove();
        });
    };

    // Load older chats
    $('#chat-more').click(function(e){
        e.preventDefault();
        // If disabled, return false
        if( $(this).hasClass('disabled') ){
            return false;
        }
        // Grab oldest
        var button = $(this),
            oldest = $(this).attr('data-chat-oldest'),
            action = $(this).attr('data-action'),
            nonce = $(this).attr('data-nonce'),
            loader = $(this).prev('.loader');
        // Now let's display indicator
        loader.fadeIn();
        $(this).addClass('disabled');
        $.post($(this).attr('href'),{
                action: action,
                _wp_gianism_nonce: nonce,
                oldest: oldest
            },function(response){
                loader.fadeOut(1000, function(){
                });
                button.removeClass('disabled');
                if(response.success){
                    if(!response.html){
                        showMessage(response.message);
                        loader.remove();
                        button.remove();
                    }else{
                        for(var i = 0, l = response.html.length; i < l; i++){
                            $(response.html[i]).appendTo('.chat-container')
                                .effect('highlight', {duration: 1000});
                        }
                        button.attr('data-chat-oldest', response.oldest);
                    }
                }else{
                    showMessage(response.message);
                }
        }, 'json');
    });

    // Delete chat
    $('.chat-container').on('click', 'a.button', function(e){
        e.preventDefault();
        var list = $(this).parents('li'),
            umetaId = list.attr('data-message-id'),
            container = $(this).parents('ol'),
            nonce = container.attr('data-nonce'),
            url = container.attr('data-url'),
            action = container.attr('data-action');
        $.post(url, {
            _wp_gianism_nonce: nonce,
            action: action,
            umeta_id: umetaId
        }, function(response){
            if(response.success){
                list.effect('highlight', {duration: 1500}, function(){
                    $(this).remove();
                    if( !container.find('li').length ){
                        container.remove();
                    }
                });
            }else{
                showMessage(response.message);
            }
        });
    });
});