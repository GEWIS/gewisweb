// Handle submenus
$('li.dropdown-submenu > [data-toggle=dropdown]').on('click touchstart', function(event) {
    event.preventDefault();
    event.stopPropagation();

    var parent = $(this).parent();

    // Also update aria-expanded for accessibility purposes.
    if (parent.hasClass('open')) {
        parent.removeClass('open');
        $(this).attr('aria-expanded', 'false');
    } else {
        parent.addClass('open');
        $(this).attr('aria-expanded', 'true');
    }
});

// Handle opening an always open dropdown when uncollapsing
$('#navbar-gewis-collapse').on('shown.bs.collapse', function() {
    $('.dropdown.default').addClass('open');
});
