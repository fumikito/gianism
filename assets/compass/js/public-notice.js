jQuery(document).ready(function($){
    if( $('.wpg-notices div').length ){
        $('.wpg-notices > div').each(function(index, elt){
            $(elt).append('<a href="#close" class="close-btn"><i class="lsf lsf-close"></i></a>').click(function(e){
                e.preventDefault();
                $('.wpg-notices').remove();
            });
        });
        $('.wpg-notices').removeClass('toggle').find('div.updated, div.error').effect('highlight', {}, 1000, function(){
            setTimeout(function(){
                $('.wpg-notices').fadeOut(2000, function(){
                    $(this).remove();
                });
            }, 15000);
        });
    }
});