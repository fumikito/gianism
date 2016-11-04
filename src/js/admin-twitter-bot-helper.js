/**
 * Description
 */

/*global GianismBotLabel:true*/

window.GianismBot = {};

(function ($) {
    'use strict';

    // Append Row
    $('#gianism-bot-schedule').on('click', '.add-row', function(e){
        e.preventDefault();
        // Make Tr
        var time = $('#g-row-time').val() + ':' + $('#g-row-minute').val(),
            tr = $('<tr><th>' + time + '</th></tr>'),
            tbody = $('tbody', '#gianism-bot-schedule');
        for( var i = 1; i <= 7; i++ ){
            tr.append('<td><input type="checkbox" name="gianism_bot_schedule[' + time + ':00][]" value="' + i + '" checked /></td>');
        }
        tr.append('<td><a class="button row-delete" href="#">&times;</a></td>');
        // Seek earlier
        var earlier = 0,
            timeInt = parseInt(time.replace(':', ''), 10),
            shouldAppend = true;
        tbody.find('tr').each(function(index, row){
            var rowTime = parseInt($(row).find('th').text().replace(':', ''), 10);
            if( rowTime < timeInt ){
                earlier = index;
            }else if( rowTime === timeInt ){
                shouldAppend = false;
            }
        });
        // If same time, return false.
        if( !shouldAppend ){
            if( window.confirm(GianismBotLabel.duplicate) ){

            }
            return false;
        }
        // Else, append row.
        if( earlier ){
            tbody.find('tr').each(function(index, row){
                if( index === earlier ){
                    $(row).after(tr);
                }
            });
        }else{
            tbody.append(tr);
        }
        tr.effect('highlight', {}, 1500);
    });

    // Delete Row
    $('#gianism-bot-schedule').on('click', '.row-delete', function(e){
        e.preventDefault();
        $(this).parents('tr').effect('highlight', {}, 500, function(){
            $(this).remove();
        });
    });



})(jQuery);
