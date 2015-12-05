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
    },
    initTagging: function () {
        $('#tagList').find(".remove-tag").each(function () {
            $(this).on('click', Photo.removeTag);
        });
        Photo.initTagSearch();
    },
    initTagSearch: function () {
        $('#tagSearch').autocomplete({
            lookup: function (query, done) {
                if (query.length >= 2) {
                    $.getJSON(URLHelper.url('member/search') + '?q=' + query, function (data) {
                        var result = {suggestions: []};

                        $.each(data.members, function (i, member) {
                            result.suggestions.push({
                                'value': member.fullName, 'data': member.lidnr
                            })
                        });

                        done(result);
                    });
                }
            },
            onSelect: function (suggestion) {
                $.post($('#tagForm').attr('action').replace('lidnr', suggestion.data),
                    {lidnr: suggestion.data}
                    , function (data) {
                        if (data.success) {
                            var removeURL = URLHelper.url('photo/photo/tag/remove', {
                                'photo_id': data.tag.photo_id,
                                'lidnr': data.tag.member_id
                            });

                            var id = 'removeTag' + data.tag.id;
                            $('#tagList').append('<li><a href="#">' + suggestion.value + '</a>' +
                                '<a href="' + removeURL + '" id="' + id + '">' +
                                '<span class="glyphicon glyphicon-remove" aria-hidden="true">' +
                                '</span></a></li>'
                            );
                            $('#' + id).on('click', Photo.removeTag);
                        }
                        $('#tagSearch').val('');
                    });
            }
        });

    },

    removeTag: function (e) {
        e.preventDefault()
        parent = $(this).parent();
        $.post($(this).attr('href'), function (data) {
            if (data.success) {
                parent.remove();
            }
        });
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

