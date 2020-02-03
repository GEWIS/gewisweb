$.fn.extend({
    treed: function () {
        //initialize each of the top levels
        var tree = $(this);
        tree.addClass("tree");
        tree.find('li').has("ul").each(function () {
            var branch = $(this); //li with children ul
            branch.prepend("<i class='fas fa-plus-circle'></i>");
            branch.addClass('branch');
            branch.on('click', function (e) {
                var icon = $(this).children('i:first');
                  icon.toggleClass("fa-plus-circle fa-minus-circle");
                $(this).children().children().toggle();
            });
            branch.children().children().toggle();
        });
        //fire event from the dynamically added icon
        $('.branch .indicator').on('click', function () {
            $(this).closest('li').click();
        });
        //fire event to open branch if the li contains an anchor instead of text

    }
});
