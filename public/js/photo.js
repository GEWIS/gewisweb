/*
 * This script will handle all common javascript functions within the photo
 * module.
 * Depends: jquery
 */

let Photo = {
    initGrid: function () {
        /*
         * Pre size items such that we can do the layouting while the images are loading
         */
        var sizer = $('.grid-sizer').width();
        var gutter = $('.gutter-sizer').width();
        $('figure.pswp-gallery__item > a > img').each(function (index) {
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

        let lazyLoadInstance = new LazyLoad({
            elements_selector: '.lazy-load',
        });

        $('.pswp-gallery').masonry({
            itemSelector: '.pswp-gallery__item',
            columnWidth: '.grid-sizer',
            percentPosition: true,
            gutter: '.gutter-sizer',
            transitionDuration: 0,
            resize: true,
        });
    },
    initRemoveTag: element => {
        element.addEventListener('click', event => {
            event.preventDefault();

            fetch(element.href, { method: 'POST' })
                .then(response => response.json())
                .then(result => {
                    document.querySelectorAll('a[data-tag-id="' + element.dataset.tagId + '"').forEach(tag => {
                        // Also remove the "In this photo:" text.
                        if (1 === tag.parentElement.parentElement.childElementCount) {
                            tag.parentElement.parentElement.parentElement.querySelector('.tag-title').classList.add('hidden');
                            tag.parentElement.parentElement.parentElement.querySelector('.no-tag-title').classList.remove('hidden');
                        }

                        tag.parentElement.remove();
                    })
                }).catch(error => {
                    // An error occurred somewhere along the way, perhaps we should notify the user.
                });
        });
    }
};
