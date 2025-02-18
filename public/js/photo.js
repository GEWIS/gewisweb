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
        function getElementWidth(selector) {
            return document.querySelector(selector).offsetWidth;
        }

        function updateImageSizes() {
            let sizer = getElementWidth('.grid-sizer');
            let gutter = getElementWidth('.gutter-sizer');

            document.querySelectorAll('figure.pswp-gallery__item > a > img').forEach(function (item) {
                let ratio = sizer / item.dataset.width;
                let height = Math.round(ratio * item.dataset.height);

                if (item.closest('.potw-thumb')) {
                    item.setAttribute('width', 2 * sizer + gutter);
                    item.setAttribute('height', 2 * height + gutter);
                } else {
                    item.setAttribute('width', sizer);
                    item.setAttribute('height', height);
                }
            });
        }

        updateImageSizes();
        let lazyLoadInstance = new LazyLoad({
            elements_selector: '.lazy-load',
        });

        let msnry = new Masonry('.pswp-gallery', {
            itemSelector: '.pswp-gallery__item',
            columnWidth: '.grid-sizer',
            percentPosition: true,
            gutter: '.gutter-sizer',
            transitionDuration: 0,
            resize: true,
        });

        // Allow reflowing of the layout when changing the window aspect-ratio and/or size.
        window.addEventListener('resize', function () {
            updateImageSizes();
            msnry.layout();
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

                        if (realTag.dataset.member === document.querySelector('.pswp-gallery').dataset.lidnr) {
                            document.querySelector('.pswp__button--profile-photo-button').classList.add('pswp__button--hidden');
                        }

                        realTag.remove();
                    })
                }).catch(error => {
                    // An error occurred somewhere along the way, perhaps we should notify the user.
                });
        });
    }
};
