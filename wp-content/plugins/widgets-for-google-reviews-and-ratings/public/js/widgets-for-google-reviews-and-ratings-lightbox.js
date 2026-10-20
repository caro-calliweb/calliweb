(function ($) {
    'use strict';

    let $lightbox, $img, $counter, $navButtons;
    let photos = [];
    let currentIndex = 0;

    function buildLightbox() {
        if ($lightbox) {
            return;
        }
        $lightbox = $(
            '<div class="repocean-lightbox" role="dialog" aria-modal="true">' +
                '<div class="repocean-lightbox-counter"></div>' +
                '<button type="button" class="repocean-lightbox-close" aria-label="Close">&times;</button>' +
                '<button type="button" class="repocean-lightbox-nav repocean-lightbox-prev" aria-label="Previous photo"></button>' +
                '<div class="repocean-lightbox-stage">' +
                    '<img class="repocean-lightbox-img" src="" alt="Review photo" referrerpolicy="no-referrer">' +
                '</div>' +
                '<button type="button" class="repocean-lightbox-nav repocean-lightbox-next" aria-label="Next photo"></button>' +
            '</div>'
        );
        $('body').append($lightbox);

        $img = $lightbox.find('.repocean-lightbox-img');
        $counter = $lightbox.find('.repocean-lightbox-counter');
        $navButtons = $lightbox.find('.repocean-lightbox-nav');

        $lightbox.on('click', '.repocean-lightbox-close', closeLightbox);
        $lightbox.on('click', '.repocean-lightbox-prev', function (e) {
            e.stopPropagation();
            show(currentIndex - 1);
        });
        $lightbox.on('click', '.repocean-lightbox-next', function (e) {
            e.stopPropagation();
            show(currentIndex + 1);
        });
        // Click on the backdrop (anywhere but the image) closes the lightbox
        $lightbox.on('click', function (e) {
            if (e.target === this || $(e.target).hasClass('repocean-lightbox-stage')) {
                closeLightbox();
            }
        });

        // Swipe navigation on touch devices
        let touchStartX = 0;
        const lbEl = $lightbox[0];
        lbEl.addEventListener('touchstart', function (e) {
            touchStartX = e.touches[0].clientX;
        }, { passive: true });
        lbEl.addEventListener('touchend', function (e) {
            const diff = touchStartX - e.changedTouches[0].clientX;
            if (Math.abs(diff) < 40) {
                return;
            }
            show(currentIndex + (diff > 0 ? 1 : -1)); // swipe left = next, right = prev
        }, { passive: true });
    }

    function show(index) {
        const total = photos.length;
        if (total === 0) {
            return;
        }
        currentIndex = (index + total) % total; // wrap around
        $img.attr('src', photos[currentIndex]);
        $counter.text((currentIndex + 1) + ' / ' + total);
        $navButtons.css('display', total > 1 ? 'flex' : 'none');
    }

    function openLightbox(photoList, index) {
        buildLightbox();
        photos = photoList;
        show(index);
        $lightbox.addClass('is-open');
        $('body').addClass('repocean-lightbox-open');
    }

    function closeLightbox() {
        if (!$lightbox) {
            return;
        }
        $lightbox.removeClass('is-open');
        $('body').removeClass('repocean-lightbox-open');
    }

    // Delegated so it covers every layout and slick's cloned slides
    $(document).on('click', '.repocean-review-photos img', function () {
        const $thumbs = $(this).closest('.repocean-review-photos').find('img');
        const list = $thumbs.map(function () {
            return $(this).attr('src');
        }).get();
        openLightbox(list, $thumbs.index(this));
    });

    $(document).on('keydown', function (e) {
        if (!$lightbox || !$lightbox.hasClass('is-open')) {
            return;
        }
        if (e.key === 'Escape') {
            closeLightbox();
        } else if (e.key === 'ArrowLeft') {
            show(currentIndex - 1);
        } else if (e.key === 'ArrowRight') {
            show(currentIndex + 1);
        }
    });
})(jQuery);
