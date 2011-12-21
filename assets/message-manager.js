/* 
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */


jQuery(document).ready(function($){
	//Do Ajax Message Delete
	$('.wpg-message a.delete').click(function(e){
		e.preventDefault();
		var tr = $(this).parents('tr');
		if(confirm(WPG.deleteConfirm)){
			$.post(WPG.endpoint, {
				action: WPG.action,
				_wpnonce: WPG.nonce,
				post_id: $(this).attr('href').replace(/[^0-9]/g, '')
			}, function(response){
				if(response.status == true){
					tr.fadeOut('normal', function(){
						$(this).remove();
						if($('.wpg-message tr').length < 1){
							$('.wpg-message table').replaceWith('<p>' + WPG.deleteComplete + '</p>');
						}
					});
				}else{
					alert(WPG.deleteFailed);
				}
			});
		}
	});
});