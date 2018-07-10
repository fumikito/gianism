/**
 * Read message from cookie
 */

/*global Cookies:false*/
/*global GianSays:true*/
/*global GianismHelper: false*/

jQuery(document).ready(function($){

    'use strict';

    window.GianSays = {
        divs: [],
        str: ['updated', 'error'],
        pushMsg: function(msg, className){
            var div = $('<div><p></p></div>');
            div.addClass(className).find('p').html( msg.replace( /\+/g, ' ' ) );
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
        },
        confirm: function(e) {
            var $btn = $(this);
            var message = $btn.attr('data-gianism-confirmation');
            var labels  = $btn.attr('data-gianism-target').split(',');
            e.preventDefault();
            GianSays.confirmDialog( message, labels, $btn.attr('href') );
        },
        confirmDialog: function( message, labels, url ) {
          var list = $.map(labels, function(item, index){
              return '<li>' + item + '</li>';
          }).join('');
          var $markup = $('<div class="wpg-confirm-container">' +
              '<div class="wpg-confirm-body">' +
                '<div class="wpg-confirm-title">' + GianismHelper.confirmLabel + '</div>' +
                '<div class="wpg-confirm-content">' +
                   '<p>' + message + '</p>' +
                   '<ul>' + list + '</ul>' +
                '</div>' +
                '<div class="wpg-confirm-footer">' +
                  '<button class="deny">' + GianismHelper.btnCancel + '</button>' +
                  '<button class="confirm">' + GianismHelper.btnConfirm + '</button>' +
                '</div>' +
              '</div>' +
            '</div>');
          $('body').append($markup);
          $markup.on('click', 'button', function(){
              if($(this).hasClass('deny')){
                  $markup.remove();
              }else if($(this).hasClass('confirm')){
                  window.location.href = url;
              }
          });
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

    // Confirmation button.
    $('.wpg-button[data-gianism-confirmation]').click(GianSays.confirm);

});
