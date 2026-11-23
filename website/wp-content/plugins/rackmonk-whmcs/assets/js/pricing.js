/**
 * Rackmonk Pricing JavaScript
 */

(function($) {
    'use strict';

    $(document).ready(function() {

        // Cycle Switcher (48, 24, 12, 1 Months)
        $(document).on('click', '.rackmonk-cycle-btn', function(e) {
            e.preventDefault();
            var $btn = $(this);
            var $container = $btn.closest('.rackmonk-pricing-container');
            var cycleKey = $btn.data('cycle').toString();

            // Toggle active class on cycle buttons
            $container.find('.rackmonk-cycle-btn').removeClass('active');
            $btn.addClass('active');

            // Update all plan cards
            $container.find('.rackmonk-plan-card').each(function() {
                var $card = $(this);
                var rawData = $card.attr('data-cycles');
                if (!rawData) return;

                try {
                    var cycles = JSON.parse(rawData);
                    var data = cycles[cycleKey];
                    if (!data) return;

                    // Update price display
                    $card.find('.price-val').text(data.available === false ? ($container.attr('data-unavailable-label') ?? 'Unavailable') : (data.display_price ?? numberWithCommas(Math.round(data.price_per_month))));
                    $card.find('.reg-price-val').text(data.display_regular ?? numberWithCommas(Math.round(data.regular_price)));
                    
                    if (data.discount_text) {
                        $card.find('.discount-val').text(data.discount_text).show();
                    } else {
                        $card.find('.discount-val').hide();
                    }

                    $card.find('.note-val').text(data.billing_note);

                    setOrderUrl($card.find('.order-link-btn'), data.order_url);
                } catch(err) {
                    console.error('Error parsing plan data', err);
                }
            });
        });

        // Category Filter Tabs
        $(document).on('click', '.rackmonk-cat-btn', function(e) {
            e.preventDefault();
            var $btn = $(this);
            var $container = $btn.closest('.rackmonk-pricing-container');
            var gid = $btn.data('gid').toString();

            $container.find('.rackmonk-cat-btn').removeClass('active');
            $btn.addClass('active');

            if (gid === 'all') {
                $container.find('.rackmonk-plan-card').fadeIn(200);
            } else {
                $container.find('.rackmonk-plan-card').each(function() {
                    var cardGid = $(this).data('gid').toString();
                    if (cardGid === gid) {
                        $(this).fadeIn(200);
                    } else {
                        $(this).hide();
                    }
                });
            }
        });

        function setOrderUrl($link, url) {
            if (url) $link.attr('href', url).removeAttr('aria-disabled');
            else $link.removeAttr('href').attr('aria-disabled', 'true');
        }

        // Hostinkar's theme toggles price classes globally. Follow the displayed
        // duration, rather than maintaining a second, potentially divergent state.
        function syncHostinkar() {
            $('[data-rm-hostinkar-cycles]').each(function() {
                var $widget = $(this);
                var items = JSON.parse($widget.attr('data-rm-hostinkar-cycles'));
                $widget.find('.pricing__box, .pricing__box-four').each(function(index) {
                    if (!items[index]) return;
                    var $box = $(this);
                    var annual = $box.find('.pricing__price').hasClass('change-subs-duration');
                    var cycle = items[index][annual ? '12' : '48'];
                    setOrderUrl($box.find('.pricing__btn a'), cycle.order_url);
                    $box.find('.pricing__price p').text(cycle.billing_note);
                    $box.find('.price-off').text(cycle.discount_text);
                });
            });
        }
        syncHostinkar();
        $(document).on('click', '.pricing-tab-switcher, .tab-btn', syncHostinkar);
        $(window).on('elementor/frontend/init', function() {
            window.elementorFrontend.hooks.addAction('frontend/element_ready/global', syncHostinkar);
        });

        function numberWithCommas(x) {
            return x.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
        }

    });

})(jQuery);
