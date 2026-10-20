(function ($) {
    'use strict';
    $(document).ready(function () {
        const calculateSlidesToShow_v1 = () => Math.max(Math.floor($('.repocean-content-wrapper').width() / 305), 1);
        const initializeSlider_v1 = () => {
            const slidesToShow_v1 = calculateSlidesToShow_v1();
            $('.repocean-slider-main-v1 .repocean-slider-box-parent').slick({
                dots: false,
                autoplay: true,
                infinite: true,
                autoplaySpeed: 6100,
                prevArrow: '<button class="slide-arrow prev-arrow" aria-label="Previous"><svg viewBox="0 0 24 24" width="18" height="18" stroke="currentColor" stroke-width="2.5" fill="none" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="9,18 15,12 9,6"></polyline></svg></button>',
                nextArrow: '<button class="slide-arrow next-arrow" aria-label="Next"><svg viewBox="0 0 24 24" width="18" height="18" stroke="currentColor" stroke-width="2.5" fill="none" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="9,18 15,12 9,6"></polyline></svg></button>',
                speed: 700,
                slidesToShow: slidesToShow_v1,
                slidesToScroll: 1,
                variableWidth: false,
                arrows: repocean_slider_js_v1.arrowVisibility === 'true'
            });
        };
        const refreshReadMore = () => {
            $('.repocean-slider-main-v1 .slider-box-inner').each(function () {
                const $desc = $(this).find('.description');
                const $btn = $(this).find('.button-content');
                if (!$desc.length || !$btn.length || $desc.hasClass('expanded')) {
                    return;
                }
                const el = $desc[0];
                const fits = el.scrollHeight <= el.clientHeight + 2;
                const hasPhotos = $(this).find('.repocean-review-photos').length > 0;
                $btn.toggleClass('hide', fits && !hasPhotos);
                $btn.toggleClass('is-placeholder', fits && hasPhotos);
            });
        };
        initializeSlider_v1();
        setTimeout(() => {
            $('.repocean-slider-main-v1 .slider-box-inner, .repocean-slider-main-v1 .review-sub-title').show();
            $('.repocean-slider-main-v1 .repocean-content-wrapper').css('visibility', 'visible');
            refreshReadMore();
        }, 1);
        $(window).on('resize', () => {
            $('.repocean-slider-main-v1 .repocean-slider-box-parent').slick('unslick');
            initializeSlider_v1();
            refreshReadMore();
        });
    });

    $('.repocean-slider-main-v1').on('click', '.button-content .readmore-button a', function (event) {
        event.preventDefault();

        const $button = $(this);
        const $description = $button.closest('.slider-box-inner').find('.description');
        const isExpanded = $description.hasClass('expanded');
        const fullHeight = $description[0].scrollHeight;
        const collapsedHeight = 40;
        const totalSteps = 15;
        const delay = 10 / totalSteps;
        const heightStep = (fullHeight - collapsedHeight) / totalSteps;

        if ($description.data('animating')) {
            return;
        }
        $description.data('animating', true);

        function expandLoop(currentHeight, stepCount) {
            if (stepCount >= totalSteps) {
                $description.addClass('expanded').removeData('animating').css('max-height', fullHeight + 'px');
                return;
            }
            currentHeight += heightStep;
            $description.css('max-height', currentHeight + 'px');
            setTimeout(() => expandLoop(currentHeight, stepCount + 1), delay);
        }

        function collapseLoop(currentHeight, stepCount) {
            if (stepCount >= totalSteps) {
                $description.removeClass('expanding expanded').removeData('animating').css('max-height', '');
                return;
            }
            currentHeight -= heightStep;
            $description.css('max-height', currentHeight + 'px');
            setTimeout(() => collapseLoop(currentHeight, stepCount + 1), delay);
        }

        if (isExpanded) {
            $description.removeClass('expanded');
            collapseLoop(fullHeight, 0);
            $button.text(repocean_slider_js_v1.read_more);
        } else {
            $description.addClass('expanding').css('max-height', collapsedHeight + 'px');
            expandLoop(collapsedHeight, 0);
            $button.text(repocean_slider_js_v1.hide);
        }
    });
})(jQuery);
