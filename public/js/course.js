/*
 * This script will handle all common javascript functions within the course
 * module.
 * Depends: jquery
 */

Course = {
    initCourseAdding: function () {
        $('#parent').autocomplete({
            lookup: function (query, done) {
                if (query.length >= 2) {
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
            }
        });

    }
}
