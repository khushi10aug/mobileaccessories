$(document).ready(function () {
    if ($('.jsSliderSection').length) {
        var fullWidth = $('.jsSliderSection').attr("data-width");
        var data = 'fullWidth=' + fullWidth;
        fcom.ajax(
            fcom.makeUrl("Home", "getSlidesHtml"),
            data,
            function (t) {
                $('.jsSliderSection').replaceWith(t.html);
                bindHeroSlider();
            }, { 'fOutMode': 'json' }
        );
    } else {
        scheduleHeroSliderInit();
    }
    bindWholesaleCategoryMegaMenu();
});

scheduleHeroSliderInit = function () {
    var delays = [0, 250, 800];
    for (var i = 0; i < delays.length; i++) {
        setTimeout(bindHeroSlider, delays[i]);
    }
    $(window).on('load.heroSlider', function () {
        bindHeroSlider();
    });
};

bindHeroSlider = function () {
    if (typeof $.fn.slick === 'undefined') {
        return;
    }

    var isRtl = (typeof langLbl !== 'undefined' && langLbl.layoutDirection === 'rtl');
    var $sliders = $('#wholesaleHeroSlider, .wholesale-hero-block .js-hero-slider, .jsSliderSection .js-hero-slider');

    $sliders = $sliders.filter(function () {
        return $(this).children('.hero-slider-item').length > 0;
    });

    if (!$sliders.length) {
        return;
    }

    $sliders.each(function () {
        var $slider = $(this);
        var slideCount = $slider.children('.hero-slider-item').length;
        var isWholesale = $slider.closest('.wholesale-hero-block').length > 0 || $slider.is('#wholesaleHeroSlider');
        var slickOpts = {
            rtl: isRtl,
            autoplay: slideCount > 1,
            autoplaySpeed: 8000,
            pauseOnHover: true,
            pauseOnFocus: false,
            draggable: slideCount > 1,
            arrows: false,
            dots: slideCount > 1,
            fade: !isWholesale && slideCount > 1,
            slidesToShow: 1,
            slidesToScroll: 1,
            speed: isWholesale ? 500 : 900,
            infinite: slideCount > 1,
            cssEase: 'ease',
            touchThreshold: 100,
            adaptiveHeight: false
        };

        try {
            if ($slider.hasClass('slick-initialized')) {
                $slider.slick('setPosition');
                if (slideCount > 1) {
                    $slider.slick('slickPlay');
                }
                return;
            }
            $slider.slick(slickOpts);
        } catch (e) {
            return;
        }
    });
};

bindWholesaleCategoryMegaMenu = function () {
    var $menuItems = $('.wholesale-hero-categories__item.has-children');
    if (!$menuItems.length || window.matchMedia('(max-width: 991px)').matches) {
        return;
    }

    var openDelay = 140;
    var closeDelay = 220;
    var isRtl = (typeof langLbl !== 'undefined' && langLbl.layoutDirection === 'rtl');

    var positionWholesaleSubmenu = function ($item) {
        var $sub = $item.children('.wholesale-hero-categories__submenu');
        var $link = $item.children('.wholesale-hero-categories__link');
        if (!$sub.length || !$link.length) {
            return;
        }
        var el = $link[0];
        var rect = el.getBoundingClientRect();
        var gap = 4;
        var vw = window.innerWidth || document.documentElement.clientWidth || 0;
        var vh = window.innerHeight || document.documentElement.clientHeight || 0;
        var width = Math.min(620, Math.max(280, vw * 0.56));
        var pad = 12;
        var left;
        if (isRtl) {
            left = rect.left - gap - width;
            if (left < pad) {
                left = pad;
            }
        } else {
            left = rect.right + gap;
            if (left + width > vw - pad) {
                left = Math.max(pad, vw - width - pad);
            }
        }
        var top = rect.top;
        var maxH = Math.min(420, vh - top - pad);
        if (maxH < 160) {
            top = Math.max(pad, vh - 160 - pad);
            maxH = Math.min(420, vh - top - pad);
        }
        $sub.css({
            top: top + 'px',
            left: left + 'px',
            maxHeight: maxH + 'px'
        });
    };

    $(window).on('resize.wholesaleCat scroll.wholesaleCat', function () {
        var $open = $menuItems.filter('.is-open').first();
        if ($open.length) {
            positionWholesaleSubmenu($open);
        }
    });

    $menuItems.each(function () {
        var $item = $(this);
        var openTimer;
        var closeTimer;

        $item.on('mouseenter', function () {
            clearTimeout(closeTimer);
            clearTimeout(openTimer);
            openTimer = setTimeout(function () {
                $menuItems.not($item).removeClass('is-open');
                $menuItems.not($item).find('.wholesale-hero-categories__submenu').removeAttr('style');
                $item.addClass('is-open');
                positionWholesaleSubmenu($item);
            }, openDelay);
        });

        $item.on('mouseleave', function () {
            clearTimeout(openTimer);
            clearTimeout(closeTimer);
            closeTimer = setTimeout(function () {
                $item.removeClass('is-open');
                $item.children('.wholesale-hero-categories__submenu').removeAttr('style');
            }, closeDelay);
        });
    });
};

resendOtp = function (userId, getOtpOnly = 0) {
    fcom.displayProcessing();
    fcom.ajax(fcom.makeUrl('GuestUser', 'resendOtp', [userId, getOtpOnly]), '', function (t) {
        t = $.parseJSON(t);
        if (1 > t.status) {
            fcom.displayErrorMessage(t.msg);
            return false;
        }
        fcom.displaySuccessMessage(t.msg);
        startOtpInterval();
    });
    return false;
};

validateOtp = function (frm) {
    if (!$(frm).validate()) return;
    var data = fcom.frmData(frm);
    fcom.ajax(fcom.makeUrl('GuestUser', 'validateOtp'), data, function (t) {
        t = $.parseJSON(t);
        if (1 == t.status) {
            window.location.href = t.redirectUrl;
        } else {
            fcom.displayErrorMessage(t.msg);
            invalidOtpField();
        }
    });
    return false;
};
