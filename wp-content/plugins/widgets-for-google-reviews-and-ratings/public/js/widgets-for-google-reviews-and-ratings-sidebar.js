(function ($) {
    'use strict';
    $(document).ready(function () {
        const $slider = $('.repocean-sidebar-main .slider-start').slick({
            dots: false,
            prevArrow: '<button class="slide-arrow prev-arrow"></button>',
            nextArrow: '<button class="slide-arrow next-arrow"></button>',
            infinite: true,
            autoplay: true,
            speed: 700,
            autoplaySpeed: 6100,
            slidesToShow: 1,
            slidesToScroll: 1,
            adaptiveHeight: true,
            arrows: repocean_sidebar_js.arrowVisibility === 'true'
        });
        $(".prev-btn").on('click', () => $slider.slick("slickPrev"));
        $(".next-btn").on('click', () => $slider.slick("slickNext"));
        $slider.on("afterChange", function (event, slick, currentSlide) {
            $(".prev-btn").toggleClass("slick-disabled", currentSlide === 0);
            $(".next-btn").toggleClass("slick-disabled", currentSlide === slick.slideCount - 1);
        });
        $(".prev-btn").addClass("slick-disabled");
        // Show the "Read more" button only when the review text really overflows
        // the collapsed box, measured at the actual rendered width. Slick clones
        // slides, so every .bottom-part-inner (originals + clones) is measured.
        const refreshReadMore = () => {
            $('.repocean-sidebar-main .bottom-part-inner').each(function () {
                const $desc = $(this).find('.description');
                const $btn = $(this).find('.button-content');
                if (!$desc.length || !$btn.length || $desc.hasClass('expanded') || $desc.hasClass('expanding')) {
                    return;
                }
                const el = $desc[0];
                const fits = el.scrollHeight <= el.clientHeight + 2;
                $btn.toggleClass('hide', fits);
            });
        };
        setTimeout(refreshReadMore, 100);
        setTimeout(refreshReadMore, 400);
        $slider.on('setPosition', refreshReadMore);
        $(window).on('resize', refreshReadMore);
        $('.repocean-sidebar-main').on('click', '.button-content .readmore-button a', function (event) {
            event.preventDefault();
            const $button = $(this);
            const $description = $button.closest('.bottom-part-inner').find('.description');
            const isExpanded = $description.hasClass('expanded');
            const fullHeight = $description[0].scrollHeight;
            const collapsedHeight = 40;
            const totalDuration = 400; // ms
            const totalSteps = 40;
            const delay = totalDuration / totalSteps;
            const heightStep = (fullHeight - collapsedHeight) / totalSteps;
            // Prevent overlapping animations
            if ($description.data('animating'))
                return;
            $description.data('animating', true);
            function expandLoop(currentHeight, stepCount) {
                if (stepCount >= totalSteps) {
                    $description.addClass('expanded').removeData('animating');
                    $description.css('max-height', fullHeight + 'px');
                    if (typeof $slider !== 'undefined' && $slider.slick) {
                        $slider.slick('refresh');
                    }
                    return;
                }
                currentHeight += heightStep;
                $description.css('max-height', currentHeight + 'px');
                setTimeout(() => expandLoop(currentHeight, stepCount + 1), delay);
            }
            function collapseLoop(currentHeight, stepCount) {
                if (stepCount >= totalSteps) {
                    $description.removeClass('expanding expanded').removeData('animating').css('max-height', '');
                    if (typeof $slider !== 'undefined' && $slider.slick) {
                        $slider.slick('refresh');
                    }
                    return;
                }
                currentHeight -= heightStep;
                $description.css('max-height', currentHeight + 'px');
                setTimeout(() => collapseLoop(currentHeight, stepCount + 1), delay);
            }
            if (isExpanded) {
                $description.removeClass('expanded');
                collapseLoop(fullHeight, 0);
                $button.text(repocean_sidebar_js.read_more);
            } else {
                $description.addClass('expanding').css('max-height', collapsedHeight + 'px');
                expandLoop(collapsedHeight, 0);
                $button.text(repocean_sidebar_js.hide);
            }
        });
    });
})(jQuery);
