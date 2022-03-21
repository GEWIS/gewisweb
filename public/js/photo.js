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
                        let realTag = tag.parentElement;

                        // Check if we are removing the last tag from the current slide. If so, make sure we change the
                        // text shown to the user ("In this photo:" or "Tag someone now!").
                        if (1 === realTag.parentElement.childElementCount) {
                            realTag.parentElement.parentElement.querySelector('.tag-title').classList.add('hidden');
                            realTag.parentElement.parentElement.querySelector('.no-tag-title').classList.remove('hidden');
                        } else {
                            // If there are 1 or more elements left, check if the tag was the last element and if so,
                            // fix the spacer(s).
                            if (null === realTag.nextElementSibling) {
                                realTag.previousElementSibling.querySelector('.tag-spacer').remove();

                                if (2 < realTag.parentElement.childElementCount) {
                                    // TODO: Cannot be translated.
                                    realTag.previousElementSibling.previousElementSibling.querySelector('.tag-spacer').textContent = 'and';
                                }
                            }
                        }

                        realTag.remove();
                    })
                }).catch(error => {
                    // An error occurred somewhere along the way, perhaps we should notify the user.
                });
        });
    }
};
