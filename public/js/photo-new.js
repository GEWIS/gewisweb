/*
 * This script will handle all common javascript functions within the photo
 * module.
 * Depends: jquery
 */

Photo = {
    vote: function(item) {
        if (item.voted) {
            return;
        }
        $('.pswp__button--like').css({'color': '#D40026'});
        var url = $('a[href="' + item.src + '"]').data('vote-url');
        $.post(url, function(data) {
            $('.pswp__button--like')
                .attr('title', 'Voted!')
                .tooltip('fixTitle')
                .tooltip('show');
        });
        item.voted = true;
    },
    updateVoteButton: function(item) {
        if (item.voted) {
            $('.pswp__button--like').css({'color': '#D40026'});
            return;
        }
        $('.pswp__button--like')
            .attr('title', 'Vote for photo of the week')
            .css({'color': '#FFF'})
            .tooltip('hide')
            .tooltip('fixTitle');
    },
    initTagging: function () {
        $('.tagList').find('.remove-tag').each(function () {
            $(this).on('click', Photo.removeTag);
        });
        Photo.initTagSearch();
    },
    initTagSearch: function () {
        $('.tagSearch').each(function(item) {
            $(this).autocomplete({
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
                orientation: 'top',
                onSelect: function (suggestion) {
                    $.post($(this).data('url').replace('lidnr', suggestion.data),
                        {lidnr: suggestion.data}
                        , function (data) {
                            if (data.success) {
                                var removeURL = URLHelper.url('photo/photo/tag/remove', {
                                    'photo_id': data.tag.photo_id,
                                    'lidnr': data.tag.member_id
                                });

                                var memberURL = URLHelper.url('member/view', {
                                    'lidnr': data.tag.member_id
                                });

                                var id = 'removeTag' + data.tag.id;
                                // The tag list exists twice, so we need to remove it in each list
                                $('.tagList-' + data.tag.photo_id).each(function(i) {
                                    $(this).prepend('<a href="' + memberURL + '">' + suggestion.value + '</a>' +
                                        '<a href="' + removeURL + '" id="' + id + '">' +
                                        '<span class="glyphicon glyphicon-remove" aria-hidden="true">' +
                                        '</span></a>, '
                                    );
                                });
                                $('#' + id).on('click', Photo.removeTag);
                                $('.tagSearch').focus();
                            }
                            $('.tagSearch').val('');
                        });
                }
            });
            $(this).focus(function(e) {
                // Prevent the textbox from hiding while we're tagging
                $('.pswp__caption').attr('style', 'opacity: 1 !important')
            });
        });

    },

    initGrid: function () {

        /*
         * Pre size items such that we can do the layouting while the images are loading
         */
        var sizer = $('.grid-sizer').width();
        var gutter = $('.gutter-sizer').width();
        $('.photo-grid-item > a > img').each(function (index) {
            var item = $(this);
            var ratio = sizer / item.data('width');
            var height = Math.round(ratio * item.data('height'));
            if (item.parent().parent().hasClass('potw-thumb')) {
                item.attr('width', 2 * sizer + gutter);
                item.attr('height', 2 * height + gutter);
            } else {
                item.attr('width', sizer);
                item.attr('height', height);
            }
        });

        $('.photo-grid').masonry({
            itemSelector: '.photo-grid-item',
            columnWidth: '.grid-sizer',
            percentPosition: true,
            gutter: '.gutter-sizer',
            transitionDuration: 0,
            resize: true
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
var initPhotoSwipeFromDOM = function(gallerySelector) {

    // parse slide data (url, title, size ...) from DOM elements
    // (children of gallerySelector)
    var parseThumbnailElements = function(el) {
        var thumbElements = el.childNodes,
            numNodes = thumbElements.length,
            items = [],
            figureEl,
            linkEl,
            size,
            item;

        for(var i = 0; i < numNodes; i++) {

            figureEl = thumbElements[i]; // <figure> element
            // include only element nodes
            if(figureEl.nodeType !== 1 || figureEl.tagName !== 'FIGURE') {
                continue;
            }

            linkEl = figureEl.children[0]; // <a> element

            size = linkEl.getAttribute('data-size').split('x');

            // create slide object
            item = {
                src: linkEl.getAttribute('href'),
                w: parseInt(size[0], 10),
                h: parseInt(size[1], 10)
            };



            if(figureEl.children.length > 1) {
                // <figcaption> content
                item.info = figureEl.children[1].innerHTML;
                item.title = figureEl.children[2].innerHTML;
            }

            if(linkEl.children.length > 0) {
                // <img> thumbnail element, retrieving thumbnail url
                item.msrc = linkEl.children[0].getAttribute('src');
            }

            item.el = figureEl; // save link to element for getThumbBoundsFn
            items.push(item);
        }

        return items;
    };

    // find nearest parent element
    var closest = function closest(el, fn) {
        return el && ( fn(el) ? el : closest(el.parentNode, fn) );
    };

    // triggers when user clicks on thumbnail
    var onThumbnailsClick = function(e) {
        e = e || window.event;
        e.preventDefault ? e.preventDefault() : e.returnValue = false;

        var eTarget = e.target || e.srcElement;

        // find root element of slide
        var clickedListItem = closest(eTarget, function(el) {
            return (el.tagName && el.tagName.toUpperCase() === 'FIGURE');
        });

        if(!clickedListItem) {
            return;
        }

        // find index of clicked item by looping through all child nodes
        // alternatively, you may define index via data- attribute
        var clickedGallery = clickedListItem.parentNode,
            childNodes = clickedListItem.parentNode.childNodes,
            numChildNodes = childNodes.length,
            nodeIndex = 0,
            index;

        for (var i = 0; i < numChildNodes; i++) {
            if(childNodes[i].nodeType !== 1) {
                continue;
            }

            if(childNodes[i] === clickedListItem) {
                index = nodeIndex;
                break;
            }
            nodeIndex++;
        }

        if(index >= 0) {
            // open PhotoSwipe if valid index found
            // TODO: figure out why we need -2 here
            openPhotoSwipe( index-2, clickedGallery );
        }
        return false;
    };

    // parse picture index and gallery index from URL (#&pid=1&gid=2)
    var photoswipeParseHash = function() {
        var hash = window.location.hash.substring(1),
            params = {};

        if(hash.length < 5) {
            return params;
        }

        var vars = hash.split('&');
        for (var i = 0; i < vars.length; i++) {
            if(!vars[i]) {
                continue;
            }
            var pair = vars[i].split('=');
            if(pair.length < 2) {
                continue;
            }
            params[pair[0]] = pair[1];
        }

        if(params.gid) {
            params.gid = parseInt(params.gid, 10);
        }

        return params;
    };

    var openPhotoSwipe = function(index, galleryElement, disableAnimation, fromURL) {
        var pswpElement = document.querySelectorAll('.pswp')[0],
            gallery,
            options,
            items;
        items = parseThumbnailElements(galleryElement);

        // define options (if needed)
        options = {

            // define gallery index (for URL)
            galleryUID: galleryElement.getAttribute('data-pswp-uid'),

            getThumbBoundsFn: function(index) {
                // See Options -> getThumbBoundsFn section of documentation for more info
                var thumbnail = items[index].el.getElementsByTagName('img')[0], // find thumbnail
                    pageYScroll = window.pageYOffset || document.documentElement.scrollTop,
                    rect = thumbnail.getBoundingClientRect();

                return {x:rect.left, y:rect.top + pageYScroll, w:rect.width};
            },
            closeElClasses: ['item', 'zoom-wrap', 'ui', 'top-bar'],
            isClickableElement: function(el) {
                // TODO: this might break things
                // Although this fixes the tagging UI
                return true;
            }

        };

        // PhotoSwipe opened from URL
        if(fromURL) {
            if(options.galleryPIDs) {
                // parse real index when custom PIDs are used
                // http://photoswipe.com/documentation/faq.html#custom-pid-in-url
                for(var j = 0; j < items.length; j++) {
                    if(items[j].pid == index) {
                        options.index = j;
                        break;
                    }
                }
            } else {
                // in URL indexes start from 1
                options.index = parseInt(index, 10) - 1;
            }
        } else {
            options.index = parseInt(index, 10);
        }

        // exit if index not found
        if( isNaN(options.index) ) {
            return;
        }

        if(disableAnimation) {
            options.showAnimationDuration = 0;
        }

        // Pass data to PhotoSwipe and initialize it
        gallery = new PhotoSwipe( pswpElement, PhotoSwipeUI_Default, items, options);
        gallery.init();
        gallery.listen('afterChange', function(item) {
            // Allow the captions to hide again (in case tagging made them permanent)
            //$('.pswp__caption').attr('style', '')
            Photo.initTagging();
            // Reset the like button
            Photo.updateVoteButton(item)
        });
        Photo.initTagging();
        $(function () {
            $('[data-toggle="tooltip"]').tooltip()
        })
        gallery.listen('likeButtonClicked', function(item) {
            Photo.vote(item);
        });
    };

    // loop through all gallery elements and bind events
    var galleryElements = document.querySelectorAll( gallerySelector );
    for(var i = 0, l = galleryElements.length; i < l; i++) {
        galleryElements[i].setAttribute('data-pswp-uid', i+1);
        galleryElements[i].onclick = onThumbnailsClick;
    }

    // Parse URL and open gallery if it contains #&pid=3&gid=1
    var hashData = photoswipeParseHash();
    if(hashData.pid && hashData.gid) {
        openPhotoSwipe( hashData.pid ,  galleryElements[ hashData.gid - 1 ], true, true );
    }
};
