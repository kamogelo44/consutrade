/**
 * ConsuTrade - Language Dropdown
 */
var $langBtn = $('#languageBtn');
var $langMenu = $('#languageMenu');

if ($langBtn.length && $langMenu.length) {
    $langBtn.on('click', function(e) {
        e.stopPropagation();
        $langMenu.toggleClass('active');
    });
    
    $(document).on('click', function() {
        $langMenu.removeClass('active');
    });
}
