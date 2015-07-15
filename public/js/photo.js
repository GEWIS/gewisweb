/*
 * This script will handle all common javascript functions within the photo
 * module.
 * Depends: jquery
 */

var LEFT_ARROW = 37;
var RIGHT_ARROW = 39;

Photo = {
    previousPage: function () {
        if ($('#previous').length > 0) {
            $('#previous')[0].click();
        }
    },
    nextPage: function () {
        if ($('#next').length > 0) {
            $('#next')[0].click();
        }
    }
}

$(function () {
    $('html').keydown(function (e) {
        if (e.which === LEFT_ARROW) {
            Photo.previousPage();
        } else if (e.which === RIGHT_ARROW) {
            Photo.nextPage()
        }
    });
});