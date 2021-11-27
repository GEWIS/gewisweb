/*
 * This script will handle all common javascript functions within the course
 * module.
 * Depends: jquery
 */

Course = {
    initCourseAdding: function () {
        new VanillaAutocomplete(document.querySelector('#parent'), {
            minChars: 2,
            lookup: function (query, done) {
                $.getJSON(URLHelper.url('add/course') + '?q=' + query, function (data) {
                    var result = {suggestions: []};

                    $.each(data.courses, function (i, course) {
                        result.suggestions.push({
                            'value': course.code, 'data': course.code
                        })
                    });

                    done(result);
                });
            }
        });
    }
}
