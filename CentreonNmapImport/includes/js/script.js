/**
 * @author    Thierno IB. Barry
 * @license   GNU/LGPL v2.1
 */
jQuery.noConflict();
jQuery(document).ready(function($){
    $(".editable").editInPlace({
        //url: "./server.php",
        callback: function(original_element, html, original){
            $("#"+original_element+" ~ input").val(html);
            return(html);
        }
    });
    
    $('input[type=checkbox][name="checkall"]').bind('click', function(){
        var _checked = $(this).attr('checked');
        $('.ckbox-import').each(function(i, ckbox){
            $(ckbox).attr('checked', _checked);
        });
    });
    
    $('#filter select').change(triggerFilter);
    
    $('#search-cni-host').keyup(triggerSearch);
    
    function triggerFilter() {
        var filterAllVal = '';
        var filterVal = $(this).val();
        var filterType = filterVal.split('-');
        
        filterType = filterType[0];
        
        if(filterVal == filterAllVal) {  
            $('#host-list tr.host-row.hidden-filter[class^='+filterType+']').fadeIn('slow').removeClass('hidden-filter');  
        }
        else{ 
            $('#host-list tr.host-row').each(function(){
                if($(this).hasClass(filterVal)){
                    $(this).fadeIn('slow').removeClass('hidden-filter');  
                }
                else{
                    $(this).fadeOut('normal').addClass('hidden-filter');  
                }
             });
        }
    }
    
    function triggerSearch(key) {
        var hostName = $(this).val();
        if(hostName == '') {  
            $('#host-list tr.host-row.hidden-search').fadeIn('fast').removeClass('hidden-search');  
        }
        else{ 
            $('#host-list tr.host-row .editable').each(function(){
                var hostRow = $(this).parent().parent();
                if($(this).is(":contains('"+hostName+"')")){
                    $(hostRow).fadeIn('fast').removeClass('hidden-search');  
                }
                else{
                    $(hostRow).fadeOut('fast').addClass('hidden-search');  
                }
            });
        }
    }
}); 