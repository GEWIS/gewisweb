/*
 * This script will handle all javascript functions neeeded for the admin
 * pages.
 * Depends: jquery, photo.js
 */

Photo.loadPage = function () {
}
Photo.initAdmin = function () {
    var COUNT_SPAN = '<span id="remove-count"></span>'
    $("#remove-multiple").html($("#remove-multiple").html().replace('%i', COUNT_SPAN));
    var count = 0;
    $(".thumbail-checkbox").change(function () {
        if (this.checked) {
            count++;
        } else {
            count--;
        }
        $("#remove-count").html(count);
        if (count > 0)
        {
            console.log("unhide");
            $("#remove-multiple").removeClass("btn-hidden");
        } else {
            $("#remove-multiple").addClass("btn-hidden");
        }
    });
}
$.fn.extend({
    treed: function () {
        //initialize each of the top levels
        var tree = $(this);
        tree.addClass("tree");
        tree.find('li').has("ul").each(function () {
            var branch = $(this); //li with children ul
            branch.prepend("<i class='indicator glyphicon glyphicon-plus-sign'></i>");
            branch.addClass('branch');
            branch.on('click', function (e) {
                if (this == e.target) {
                    var icon = $(this).children('i:first');
                    icon.toggleClass("glyphicon-minus-sign glyphicon-plus-sign");
                    $(this).children().children().toggle();
                }
            })
            branch.children().children().toggle();
        });
        //fire event from the dynamically added icon
        $('.branch .indicator').on('click', function () {
            $(this).closest('li').click();
        });
        //fire event to open branch if the li contains an anchor instead of text
        $('.branch>a').each(function () {
            $(this).on('click', function (e) {
                $(this).closest('li').click();
                e.preventDefault();
            });
        });
    }
});


