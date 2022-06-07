$.fn.extend({
    treed: function () {
        //initialize each of the top levels
        var tree = $(this);
        tree.addClass('tree');
        tree.find('li').has('ul').each(function () {
            let branch = $(this); //li with children ul
            branch.prepend('<i class="fas fa-folder-plus indicator"></i>');
            branch.addClass('branch');
            branch.on('click', function (e) {
                if (this == e.target) {
                    let icon = $(this).children('i:first');
                    icon.toggleClass('fa-folder-plus fa-folder-minus');
                    $(this).children().children().toggle();
                }
            });
            branch.children().children().toggle();
        });
        //fire event from the dynamically added icon
        $('.branch .indicator').on('click', function () {
            $(this).closest('li').click();
        });
    }
});
