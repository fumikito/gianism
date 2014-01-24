jQuery(document).ready(function($){
    $('#chat-form').submit(function(e){
        e.preventDefault();
        var str = $(this).find('textarea').val();
        if(str.length){
            $(this).ajaxSubmit({
                success: function(response){
                   if(response.success){
                       $('.chat-container').prepend(response.message).effect('highlight');
                   }else{
                       alert(response.message);
                   }
                }
            });
        }
    });
});