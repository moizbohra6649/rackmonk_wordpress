(function ($) {
    'use strict';
    function initialize(scope) {
        $(scope).find('.rackmonk-hosting-active').each(function () {
            if (this.swiper || typeof window.Swiper !== 'function') return;
            var section = this.parentElement;
            while (section.parentElement && !section.querySelector('.hosting-button-next')) {
                section = section.parentElement;
            }
            new window.Swiper(this, {
                slidesPerView: 1,
                spaceBetween: 24,
                loop: false,
                watchOverflow: true,
                breakpoints: {
                    576: { slidesPerView: 2 },
                    992: { slidesPerView: 3 },
                    1200: { slidesPerView: 4 }
                },
                navigation: {
                    nextEl: section.querySelector('.hosting-button-next'),
                    prevEl: section.querySelector('.hosting-button-prev')
                }
            });
        });
    }
    $(function () { initialize(document); });
    $(window).on('elementor/frontend/init', function () {
        window.elementorFrontend.hooks.addAction('frontend/element_ready/xhosting-service.default', function ($scope) {
            initialize($scope);
        });
    });
})(jQuery);
