jQuery(document).ready(function($){

    // Submit chat
    $('#chat-form').submit(function(e){
        e.preventDefault();
        if($(this).hasClass('loading')){
            return false;
        }
        var textArea = $(this).find('textarea'),
            str = textArea.val(),
            newest = $('.chat-container li:first');
        if(str.length){
            $(this).addClass('loading');
            if(newest.length){
                $(this).find('input[name=newest]').val(newest.attr('data-chat-id'));
            }
            $(this).ajaxSubmit({
                success: function(response){
                   $('#chat-form').removeClass('loading');
                   if(response.success){
                       // Remove
                       textArea.val('');
                       $('.chat-container').prepend(response.message).effect('highlight');
                   }else{
                       alert(response.message);
                   }
                }
            });
        }
    });

    // Short cut
    $('#chat-form textarea').keydown(function(e){
        var code = e.keyCode || e.which;
        if(code == 13 && (e.ctrlKey || e.metaKey)){
            e.preventDefault();
            $('#chat-form').submit();
        }
    });

    // Refresh
    setInterval(function(){
        if(!$('#chat-form').hasClass('loading') && !($('.prev-loader').hasClass('active')) ){
            $('.prev-loader').addClass('active');
            var newest = $('.chat-container li:first').attr('data-chat-id'),
                nonce = $('#_wp_gianism_nonce').val(),
                thread_id = $('#chat-form input[name=thread_id]').val();
            $.post($('#chat-form').attr('action'), {
                action: 'gianism_chat_newer',
                _wp_gianism_nonce: nonce,
                thread_id: thread_id,
                newest: newest
            }, function(response){
                $('.prev-loader').removeClass('active');
                if(response.success){
                    if( response.message ){
                        $('.chat-container').prepend(response.message);
                    }
                }else{
                    alert(response.message);
                }
            }, 'json');
        }
    }, 8000);

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
            threadId = $(this).attr('data-thread-id'),
            action = $(this).attr('data-action'),
            oldest = $('.chat-container li:last').attr('data-chat-id'),
            nonce = $(this).attr('data-nonce'),
            loader = $(this).prev('.loader');
        // Now let's display indicator
        loader.fadeIn();
        $(this).addClass('disabled');
        $.post($(this).attr('href'),{
                action: action,
                _wp_gianism_nonce: nonce,
                thread_id: threadId,
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
                    }
                }else{
                    showMessage(response.message);
                }
        }, 'json');
    });
});