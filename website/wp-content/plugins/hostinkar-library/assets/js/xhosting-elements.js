(function ($, elementor) {
    "use strict";

    var xhosting = {

        init: function () {

            var widgets = {
                'xhosting-domain-form.default'            : xhosting.Domain,
            };
            $.each(widgets, function (widget, callback) {
                elementor.hooks.addAction('frontend/element_ready/' + widget, callback);
            });
        },

        TableTabsTitle: function ($scope) {


            $(".crm-monthly").each(function () {
                $(this).on("click", function () {
                    $(this).parents(".crm-pricing-switch-wrapper").find(".crm-checkbox-switch").prop("checked", false);
                });
            });
            $(".crm-yearly").each(function () {
                $(this).on("click", function () {
                    $(this).parents(".crm-pricing-switch-wrapper").find(".crm-checkbox-switch").prop("checked", true);
                });
            });
            $(".crm-pricing-switch").each(function () {
                $(this).on("click", function () {
                    var isBoxChecked = $(this).find(".crm-checkbox-switch").is(":checked");

                    if (isBoxChecked !== true) {
                        $(this).parents(".table").find(".crm_monthly_price").show();
                        $(this).parents(".table").find(".crm_yearly_price").hide();
                    } else {
                        $(this).parents(".table").find(".crm_monthly_price").hide();
                        $(this).parents(".table").find(".crm_yearly_price").show();
                    }
                });
            });

        },

        SupportChat: function ($scope) {

            let chattingSlider = $scope.find('.mn-chatting-slider');

            if ( chattingSlider.length > 0  ) {
                const mnChattingSlider = new Swiper(".mn-chatting-slider", {
                    loop: true,
                    autoplay: true,
                    slidesPerView: 3,
                    centeredSlides: true,
                    direction: "vertical"
                });
            }

        },


        Locations: function ($scope) {

            let dataCenter = $scope.find('.mn-data-center .data-center-pointer');
            if ( dataCenter.length > 0  ) {
                dataCenter.each(function () {
                    $(this).on('mouseenter', function () {
                        $(this).parents('.mn-data-center').find("a.active").removeClass('active');
                        $(this).addClass("active");
                    });
                }); //pricing switch
            }


            // Layout - 05 (Data Tab Locations)
            var $dtc_grid = $('.dtc-grid').isotope({}); // filter items on button click

            $('.data-center-filter-btns').on('click', 'button', function () {
                var filterValue = $(this).attr('data-filter');
                $dtc_grid.isotope({
                    filter: filterValue
                });
            });
            $(".data-center-filter-btns button").each(function () {
                $(this).on("click", function () {
                    $(this).parent().find("button.active").removeClass("active");
                    $(this).addClass("active");
                });
            }); //showing notice bar

        },


        Domain: function ($scope) {

            var element = $scope.find('#xhosting-domain-search');
            var elementClass = $scope.find('.xhosting-domain-search');
            var domainSuggestion = elementClass.attr('data-suggestion');
            
            /* Domain Check */
            var DomainCheck = {
                submit: function (e) {
                    e.preventDefault();
                    if (e.data.input.val() != '') {
                        var obj           = e.data,
                            el            = obj.wap.find("#xhosting-domain-results"),
                            domainDefault = "byteflows.com",
                            basename      = obj.input.val() !== "" ? obj.input.val() : domainDefault,
                            ext           = obj.select.val() !== "" ? obj.select.val() : '',
                            whmcs_url     = obj.whmcs_url.val() !== "" ? obj.whmcs_url.val() : '',
                            extension     = DomainCheck.dotExt(obj.input.val());

                        var domainName = "";
                        if (basename.indexOf('.') > -1) {
                            domainName = basename;
                        } else if (basename.indexOf('.') == -1) {
                            domainName = basename + (ext ? '.' + ext : '.com');
                        }

                        obj.security = obj.form.find("input[name=security]").val();
                        obj.el = el;
                        var domainData = {},
                            domainResultTable = $(
                                '<div id="xhosting_result_item" class="xhosting-result-domain-box" role="alert"> </div>'
                            ),
                            domainResult = $(
                                '<div class="inner-block-result-item">' +
                                '<div class="spinner xhosting-loading-results text-center mt-3">' +
                                '<i class="fa fa-spinner fa-spin fa-lg fa-fw"></i>' +
                                '<span> ' + translate.searching +'</span>' +
                                '<span class="sr-only">...</span>' +
                                "</div>"
                            );

                        $.extend(domainData, obj);
                        domainData.domain = domainName;
                        domainData.extension = extension;
                        domainData.whmcs_url = whmcs_url;
                        domainData.el = domainResult;

                        domainResult.data("domain", domainData.domain);

                        if (obj.el.find("#xhosting_result_item").length == 0) {
                            obj.el.append(domainResultTable);
                            obj.el.find("#xhosting_result_item").append(domainResult);
                        } else {
                            obj.el.find("#xhosting_result_item").remove();
                            obj.el.append(domainResultTable);
                            obj.el.find("#xhosting_result_item").append(domainResult);
                        }
                        
                        /**Reset Suggestion */
                        if (domainSuggestion == 'true') {
                            let suggestionWrap_ = document.getElementById('xhosting-domain-suggestions');
                            suggestionWrap_.innerHTML = '';
                        }
                        
                        
                        // main domain search ================
                        DomainCheck.checkAjax(domainData);

                    }
                    else {
                        var warning_text = document.createElement("span");
                        warning_text.setAttribute('class', 'alert-warning p-2 d-block mt-2');
                        warning_text.innerHTML = translate.enterDomain;
                        element.append(warning_text);

                        setTimeout(function () {
                            warning_text.remove();
                        }, 3000);
                    }
                },

                name: function (domain) {
                    return domain.replace(/^.*\/|\.[^.]*$/g, "");
                },

                dotExt: function (ext) {
                    var fExt,
                        tExt = ext.split(".", 3);

                    if (tExt[1] === undefined) {
                        fExt = "com";
                    } else if (tExt[0] === "www") {
                        fExt = tExt[2];
                    } else {
                        fExt = tExt[1];
                    }

                    return fExt;
                },

                checkAjax: function (obj) {
                    var data = {
                        domain: obj.domain,
                        whmcs_url: obj.whmcs_url,
                        action: "xhosting_ajax_search_domain",
                        security: obj.security,
                    };
                    let suggestionWrap_ = $('#xhosting-domain-suggestions');

                    $.ajax({
                        url: xhosting_ajax_url,
                        type: "POST",
                        dataType: "json",
                        data: data,
                        success: function (data) {
                            obj.el.find(".spinner").remove();
                            obj.el.append(data.results_html);
                            
                            console.log(data);

                            if (domainSuggestion == 'true') {
                                let suggestionTitle = document.createElement("h5");
                                suggestionTitle.setAttribute('class', 'title');
                                suggestionTitle.innerHTML = 'Recommended Domain Names';
                                suggestionWrap_.append(suggestionTitle);
                                // suggested domain search ================
                                DomainCheck.domainSeggestion(obj.domain);
                            }
                        },
                        error: function (xhr, ajaxOptions, thrownError) {
                            console.log(xhr);
                            console.log(thrownError);
                        },
                    });
                },
                domainSeggestion: function (obj) {
                    let domainExtS = document.querySelectorAll('.domain-info .info-box');
                    let extensionArr = [];
                    domainExtS.forEach(element => {
                        let extName = element.children[0].innerHTML;
                        extensionArr.push(extName);                                                  
                    });

                    var data = {
                        domain: obj,
                        extensions: extensionArr,
                        action: "xhosting_domain_search_seggestion"
                        
                    };
                    let suggestionWrap = $('#xhosting-domain-suggestions');

                    $.ajax({
                        url: xhosting_ajax_url,
                        type: "POST",
                        dataType: "json",
                        data: data,
                        beforeSend: function () {
                            suggestionWrap.append('<div class="spinner-border" role="status"></div>');
                        },
                        success: function (data) {
                            suggestionWrap.find(".spinner-border").remove();
                            if (data.length > 0) {
                                data.forEach(element => {
                                    suggestionWrap.append(element.results_html)
                                })
                            }

                        },
                        error: function (xhr, ajaxOptions, thrownError) {
                            console.log(xhr);
                            console.log(thrownError);
                        },
                    });
                },
            };

            element.is(function () {
                var id = $(this),
                    submitEl = id.find("#xhosting-search"),
                    inputEl = id.find("#xhosting-domain"),
                    selectEl = id.find("#domainext"),
                    formEl = id.find("#xhosting-domain-form"),
                    acEl = id.find('input[name="whmcs_url"]'),
                    data;

                data = {
                    submit: submitEl,
                    input: inputEl,
                    select: selectEl,
                    whmcs_url: acEl,
                    form: formEl,
                    div: id,
                    wap: id,
                };

                submitEl.attr("disabled", false);
                inputEl.keyup(function () {
                    if ($(this).val().length != 0) submitEl.attr("disabled", false);
                    else submitEl.attr("disabled", true);
                });

                // 👇 Updated handler
                submitEl.on("click", data, function (e) {
                    DomainCheck.submit(e);
                    $('.domain__rs-wrapper').addClass('active'); // 👈 Adds class after click
                });

                let input_ID = $('#xhosting-domain');
                input_ID.on("keypress", data, function (event) { 
                    if (event.key == "Enter") {
                        DomainCheck.submit(event);
                    }
                });
            });


            // Loading Screen
            const loadingClass = $(".loading"),
                removeFLow = $("html,body").css("overflow", "auto");

            if (loadingClass.length === 1) {
                $(window).on("load", function () {
                    loadingClass.fadeOut();
                    removeFLow;
                });
            }

            // Disable Enter Key Domain Search Form
            document.addEventListener('keypress', function (e) {
                if (e.keyCode === 13 || e.which === 13) {
                    e.preventDefault();
                    return false;
                }
            });

        },

        SwiperControls: function () {
            var swiper_container = $(".swiper");
            if (swiper_container.length) {
                swiper_container.each(function () {
                    var t     = $(this),
                        i     = $(this).data("perpage") || 1,
                        a     = $(this).data("loop"),
                        e     = $(this).data("speed") || 1000,
                        o     = $(this).data("space") || 0,
                        l     = $(this).data("effect"),
                        c     = $(this).data("center"),
                        ef    = $(this).data("effect") || 'slide',
                        pl    = $(this).data("autoplay"),
                        delay = $(this).data("delay") || 5000,
                        ap    = pl === true ? { delay: Number(delay) } : false,
                        nex   = $(this).data("next"),
                        pre   = $(this).data("prev"),
                        pag   = $(this).data("pagination"),
                        ptype = $(this).data("paginationtype"),
                        d     = $(this).data("direction") || "horizontal",
                        bp    = $(this).data("breakpoints");
        
                    // Initialize Swiper and store the instance
                    var swiperInstance = new Swiper(t[0], {
                        slidesPerView: Number(i),
                        direction: d,
                        spaceBetween: Number(o),
                        loop: Boolean(a),
                        speed: Number(e),
                        breakpoints: Object(bp),
                        centeredSlides: c,
                        autoplay: ap,
                        effect: ef,
                        fadeEffect: {
                            crossFade: true
                        },
                        pagination: {
                            el: pag,
                            type: ptype,
                            clickable: true
                        },
                        navigation: {
                            nextEl: nex,
                            prevEl: pre
                        }
                    });
        
                    // Add hover event to stop and start autoplay
                    t.hover(
                        function () {
                            swiperInstance.autoplay.stop();
                        },
                        function () {
                            swiperInstance.autoplay.start();
                        }
                    );
                });
            }
        },        

        IsotoControls: function () {
            $(document).ready(function () {

                // init Isotope
                var $isotop_filter_items = $('.gh-filter-items').isotope({// options
                }); // filter items on button click

                $('.gh-filter-controls').on('click', 'button', function () {
                    var filterValue = $(this).attr('data-filter');
                    $isotop_filter_items.isotope({
                        filter: filterValue
                    });
                }); //replace active class

                $(".gh-filter-controls button").each(function () {
                    $(this).on("click", function () {
                        $(this).parents(".gh-filter-controls").find("button.active").removeClass("active");
                        $(this).addClass("active");
                    });
                });
            });
        },

        HeroSlider: function ($scope) {
            let slider_1 = $scope.find('.xhosting_slider');
            let slider_2 = $scope.find('.hm7-hero-slider');
            
            if (slider_1.length > 0) {
                const HeroSlider1 = new Swiper(".xhosting_slider", {
                    slidesPerView: 1,
                    autoplay: true,
                    loop: true,
                    
                    effect: 'fade',
                    fadeEffect: {
                        crossFade: true
                    },
                    pagination: {
                        el: '.swiper-pagination',
                        type: 'bullets',
                        clickable: true
                    },
                });
            }

            if (slider_2.length > 0) {
                const HeroSlider2 = new Swiper(".hm7-hero-slider", {
                    slidesPerView: 1,
                    autoplay: true,
                    loop: true,
                    
                    effect: 'fade',
                    fadeEffect: {
                        crossFade: true
                    },
                    navigation: {
                        nextEl: '.hm7-hero-slider-next',
                        prevEl: '.hm7-hero-slider-prev'
                    }
                });
            }
        },

        VpsSlider: function ($scope) {
            var vpsRangeSlider = $scope.find('#vps_range_slider');
            if (vpsRangeSlider.length > 0) {
                //Price range slider
                var vps_range_slider = document.querySelector('#vps_range_slider');
                var dataSlide = vps_range_slider.getAttribute('data-slide');

                $(".range-slider").slider({
                    min: 1,
                    max: dataSlide,
                    value: 2
                });
                var rangeInput = $("#vps_range_slider input");
                var checkValue = rangeInput.val();

                for (let i = 1; dataSlide > i; i++) {
                    if (checkValue >= i) {
                        $(".vps_value").hide();
                        $(".vps_" + i + "_value").show();
                        $(".vps_label").removeClass("active");
                        $(".vps_label_" + i).addClass("active");
                    }
                }

                rangeInput.on("change", function () {
                    var checkValue = $(this).val();

                    for (let i = 1; dataSlide >= i; i++) {
                        if (checkValue >= i) {
                            $(".vps_value").hide();
                            $(".vps_" + i + "_value").show();
                            $(".vps_label").removeClass("active");
                            $(".vps_label_" + i).addClass("active");
                        }
                    }

                });
                var rangeTooltip = $("#vps_range_slider .tooltip");
                var layout_4 = $(".vps_labels.layout_4");
                if (rangeTooltip.length > 0) {
                    let marginValue = layout_4.length > 0 ? 3 : 2;
                    var tooltipOffset = rangeTooltip.attr('style').match(/\d+/g);
                    var labelPosition = tooltipOffset[0];
                    $(".price-slider-wrapper .vps_labels span.active").css({
                        marginLeft: tooltipOffset[0] - marginValue + '%'
                    });
                    rangeInput.on("change", function () {
                        var tooltipOffset = rangeTooltip.attr('style').match(/\d+/g);
                        $(".price-slider-wrapper .vps_labels span.active").css({
                            marginLeft: tooltipOffset[0] - marginValue + '%'
                        });
                    });
                }
            }
        }


    };
    $(window).on('elementor/frontend/init', xhosting.init);
}(jQuery, window.elementorFrontend));
