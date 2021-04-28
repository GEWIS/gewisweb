$('ul.dropdown-menu [data-toggle=dropdown]').on('click', function(event) {
    event.preventDefault(); 
    event.stopPropagation(); 

    // Also update aria-expanded for accessibility purposes.
    if ($(this).parent().hasClass('open')) {
        $(this).parent().removeClass('open');
        $(this).attr('aria-expanded', 'false');
    } else {
        $(this).parent().addClass('open');
        $(this).attr('aria-expanded', 'true');
    }
});