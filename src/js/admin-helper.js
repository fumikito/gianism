/*!
 * Admin screen helper for Gianism
 */

jQuery(document).ready(function ($) {

  "use strict";

  // Create appendix
  $('.gianism-wrap').find('h3,caption').each(function (index, elt) {
    $(elt).attr('id', 'index_' + index);
    $('#index-list').append('<li><a href="#index_' + index + '">' + $(elt).text() + '</a></li>');
  });

  // Notice dismiss
  $('.gianism-admin-notice').click(function(e){
    e.preventDefault();
    var $btn = $(this);
    $.get( $btn.attr('data-endpoint') ).done(function(response){
      if ( response.success ) {
        $btn.parents('div.error').remove();
      } else {
        window.alert( 'Error' );
      }
    }).fail(function(response){
      window.alert(response.responseJSON.data);
    });
  });
});