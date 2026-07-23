$(function () {
    $(".cancel").on('click', function () {
        $(this).closest('.addon--js').toggleClass('cancelled--js ');
        $(this).toggleClass('remove-add-on');
    });

    $(".be-first").on('click', function () {
        $('html, body').animate({ scrollTop: $("#itemRatings").offset().top - 130 }, 'slow');
        fcom.scrollToTop($("#itemRatings"));
    });

    $(".itemthumb").on('click', function () {
        var mainSrc = $(this).find('img').attr('main-src');
        $(".item__main").find('img').attr('src', mainSrc);
    });

    $(".link_li").on('click', function (event) {
        event.preventDefault();

        var target_offset = $(".product--specifications").offset();
        var target_top = target_offset.top - 100;
        $('html, body').animate({ scrollTop: target_top }, 1000);
    });

    $(".link--write").on('click', function () {
        $('html, body').animate({ scrollTop: $("#itemRatings").offset().top - 130 }, 'slow');
        fcom.scrollToTop($("#itemRatings"));
    });

    /* bannerAdds(); */
    reviews(document.frmReviewSearch);

    $(document).on('click', ".btnAddToCart--js", function (event) {
        event.preventDefault();
        var data = $("#frmBuyProduct").serialize();
        var selprod_id = $(this).attr('data-id');
        var quantity = $(this).attr('data-min-qty');
        var cartHasProducts = $(this).data('cartHasProduct');
        if (0 < cartHasProducts && !confirm(langLbl.overwriteCartItems)) {
            return false;
        }
        data = "selprod_id=" + selprod_id + "&quantity=" + quantity;
        ykevents.addToCart();
        fcom.updateWithAjax(fcom.makeUrl('cart', 'add'), data, function (ans) {
            fcom.closeProcessing();
            fcom.removeLoader();
            if (ans['redirect']) {
                location = ans['redirect'];
                return false;
            }
            $('span.cartQuantity').html(ans.total);
            cart.loadCartSummary();
        });
        return false;
    });
});

$(window).on('load', function () {

    /* Product Gallery */
    $("#detail .main-img-slider").slick({
        rtl: ('rtl' == langLbl.layoutDirection) ? true : false,
        slidesToShow: 1,
        slidesToScroll: 1,
        infinite: false,
        arrows: true,
        fade: true,
        autoplay: true,
        autoplaySpeed: 4000,
        speed: 300,
        lazyLoad: "ondemand",
        asNavFor: ".thumb-nav",
        prevArrow: '<button class="btn btn-prev"><span></span> </button>',
        nextArrow: '<button class="btn btn-next"><span></span> </button>',
        responsive: [
            {
                breakpoint: 1024,
                settings: {
                    dots: true,
                }
            },
            {
                breakpoint: 600,
                settings: {
                    dots: true,
                }
            },
            {
                breakpoint: 480,
                settings: {
                    dots: true,
                }
            }

        ]
    });

    /* Thumbnail/alternates slider for product page */
    $(".thumb-nav").slick({
        rtl: ('rtl' == langLbl.layoutDirection) ? true : false,
        slidesToShow: 5,
        slidesToScroll: 1,
        infinite: false,
        centerMode: true,
        centerPadding: '0px',
        asNavFor: ".main-img-slider",
        dots: false,
        arrows: false,
        draggable: true,
        speed: 200,
        focusOnSelect: true,
        vertical: true,
        verticalSwiping: true,
        prevArrow: '<button class="btn btn-prev"><span></span> </button>',
        nextArrow: '<button class="btn btn-next"><span></span> </button>',
        responsive: [
            {
                breakpoint: 1600,
                settings: {
                    slidesToShow: 4,
                    slidesToScroll: 1,
                }
            },

            {
                breakpoint: 1024,
                settings: {
                    slidesToScroll: 1,
                    settings: 'unslick'
                }
            },
            {
                breakpoint: 600,
                settings: {
                    settings: 'unslick'
                }
            },
            {
                breakpoint: 480,
                settings: {
                    settings: 'unslick'
                }
            }

        ]
    });

    /* keeps thumbnails active when changing main image, via mouse/touch drag/swipe */
    $(".main-img-slider").on("afterChange", function (event, slick, currentSlide, nextSlide) {
        /* remove all active class */
        $(".thumb-nav .slick-slide").removeClass("slick-current");
        /* set active class for current slide */
        $(".thumb-nav .slick-slide:not(.slick-cloned)").eq(currentSlide).addClass("slick-current");
    });
    /* Product Gallery */
});

function moreSellerRows(selprodCode, sellerId) {
    fcom.ajax(fcom.makeUrl('Products', 'moreSellersRows', [selprodCode, sellerId]), '', function (res) {
        $('.moreSellerRows--js').append(res);
    });
}

function getSortedReviews(elm) {
    if ($(elm).length) {
        var sortBy = $(elm).data('sort');
        if (sortBy) {
            document.frmReviewSearch.orderBy.value = $(elm).data('sort');
            $(elm).parent().siblings().removeClass('is-active');
            $(elm).parent().addClass('is-active');
        }
    }
    $('.sortByTxtJs').text($(elm).text());
    $('.sortByEleJs').removeClass('active');
    $(elm).addClass('active');
    reviews(document.frmReviewSearch);
}

function reviewAbuse(reviewId) {
    if (reviewId) {
        $.facebox(function () {
            fcom.ajax(fcom.makeUrl('Reviews', 'reviewAbuse', [reviewId]), '', function (t) {
                $.facebox(t);
            });
        });
    }
}

function setupReviewAbuse(frm) {
    if (!$(frm).validate()) return;
    var data = fcom.frmData(frm);
    fcom.updateWithAjax(fcom.makeUrl('Reviews', 'setupReviewAbuse'), data, function (t) {
        fcom.closeProcessing();
        fcom.removeLoader();
        $(document).trigger('close.facebox');
    });
    return false;
}

(function () {
    var setProdWeightage = false;
    var timeSpendOnProd = false;
    bannerAdds = function () {
        fcom.ajax(fcom.makeUrl('Banner', 'products'), '', function (res) {
            $("#productBanners").html(res);
        });
    };

    setProductWeightage = function (code) {
        var data = 'selprod_code=' + code;
        if (setProdWeightage == true && timeSpendOnProd == true) { return; }
        if (setProdWeightage == true) {
            timeSpendOnProd = true;
            data += '&timeSpend=true';
        }
        setProdWeightage = true;
        fcom.ajax(fcom.makeUrl('Products', 'logWeightage'), data, function (res) { });
    };

    /* reviews section[ */
    var dv = '#itemRatings .reviewListJs';
    var currPage = 1;

    reviews = function (frm, append) {
        if (typeof append == undefined || append == null) {
            append = 0;
        }

        var data = fcom.frmData(frm);
        if (append == 1) {
            $(dv).prepend(fcom.getLoader());
        } else {
            $(dv).html(fcom.getLoader());
        }
        data += '&productView=1';
        fcom.updateWithAjax(fcom.makeUrl('Reviews', 'searchForProduct'), data, function (ans) {
            fcom.closeProcessing();
            fcom.removeLoader();
            if (ans.totalRecords) {
                $('#reviews-pagination-strip--js').show();
            }
            if (append == 1) {
                $(dv).find('.loader-yk').remove();
                $(dv).find('form[name="frmSearchReviewsPaging"]').remove();
                $(dv).append(ans.html);
                $('#reviewEndIndex').html((Number($('#reviewEndIndex').html()) + ans.recordsToDisplay));
            } else {
                $(dv).html(ans.html);
                $('#reviewStartIndex').html(ans.startRecord);
                $('#reviewEndIndex').html(ans.recordsToDisplay);
            }
            $('#reviewsTotal').html(ans.totalRecords);
            $("#loadMoreReviewsBtnDiv").html(ans.loadMoreBtnHtml);
        }, '', false);
    };

    let lastIndex = 0;
    let pageNumber = 1;
    reviewsWithImages = function (selprodId, page = 1) {
        riDv = '#itemRatings .reviewImagesListJs';
        data = 'productView=1&selprod_id=' + selprodId + '&page=' + page;
        $(riDv).prepend(fcom.getLoader());
        fcom.updateWithAjax(fcom.makeUrl('Reviews', 'getReviewsImages'), data, function (ans) {
            fcom.closeProcessing();
            fcom.removeLoader();
            $('.revsMoreImagesJs').remove();
            if ('' == ans.html && 1 < page) {
                lastIndex = 0;
                return;
            }

            if ('' == ans.html) {
                $('.reviewsWithImagesSectionJs').remove();
            } else {
                $('.reviewsImageblockJs').show();
                $('.reviewsWithImagesSectionJs').fadeIn();
                $(riDv).append(ans.html);
                pageNumber = ans.page;
                /* 
                $.fancybox.close();
                let selector = riDv + " [data-fancybox]";
                $(selector).fancybox({
                    afterShow: function (instance, current) {
                        if (current.index === instance.group.length - 1) {
                            if (ans.total_records > instance.group.length) {
                                lastIndex = current.index;
                                fancyboxInstance = instance;
                                reviewsWithImages(selprodId, page + 1);
                            }
                        }
                    },
                    afterClose: function () {
                        if (1 < pageNumber && 0 < lastIndex) {
                            // $.fancybox.open($(selector), {}, (lastIndex + 1));
                            $(selector + ':eq(' + (lastIndex + 1) + ')').click();
                            lastIndex = 0;
                        }
                    }
                }); */
            }
        }, '', false);
    };

    goToLoadMoreReviews = function (page) {
        if (typeof page == undefined || page == null) {
            page = 1;
        }
        currPage = page;
        var frm = document.frmSearchReviewsPaging;
        $(frm.page).val(page);
        reviews(frm, 1);
    };

    /*] */

    markReviewHelpful = function (reviewId, isHelpful) {
        if (isUserLogged() == 0) {
            loginPopUpBox();
            return false;
        }
        isHelpful = (isHelpful) ? isHelpful : 0;
        var data = 'reviewId=' + reviewId + '&isHelpful=' + isHelpful;
        fcom.updateWithAjax(fcom.makeUrl('Reviews', 'markHelpful'), data, function (ans) {
            fcom.closeProcessing();
            fcom.removeLoader();
            reviews(document.frmReviewSearch);
        });
    }

	rateAndReviewProduct = function (product_id, selprod_id) {
		if (isUserLogged() == 0) {
			loginPopUpBox();
			return false;
		}
		selprod_id = selprod_id || 0;
		window.location = fcom.makeUrl('Reviews', 'write', [product_id, selprod_id]);
	}

    checkUserLoggedIn = function () {
        if (isUserLogged() == 0) {
            loginPopUpBox();
            return false;
        } else return true;
    }

    loadMoreImages = function (obj, e) {
        e.preventDefault();
        $(obj).removeAttr("onclick");
        $(obj).find('.moreMediaCountJs').remove();
        $(obj).siblings('.moreMediaJs').removeClass("d-none");
        return false;
    }


})();

/* jQuery(document).ready(function ($) {
    $('a[rel*=facebox]').facebox()
}); */

/* for sticky things*/
if ($(window).width() > 1050) {
    function sticky_relocate() {
        var window_top = $(window).scrollTop();
        var div_top = $('.fixed__panel').offset().top - 110;
        var sticky_left = $('#fixed__panel');
        if ((window_top + sticky_left.height()) >= ($('.unique-heading').offset().top - 40)) {
            var to_reduce = ((window_top + sticky_left.height()) - ($('.unique-heading').offset().top - 40));
            var set_stick_top = -40 - to_reduce;
            sticky_left.css('top', set_stick_top + 'px');
        } else {
            sticky_left.css('top', '110px');
            if (window_top > div_top) {
                $('#fixed__panel').addClass('stick');
            } else {
                $('#fixed__panel').removeClass('stick');
            }
        }
    }


}

$('.gallery').modaal({
    type: 'image'
});

function playVideo(videoPath, type, filename) {
    fcom.updateFaceboxContent(function () {
        $.facebox.reveal('<div class="modal-body"><div class="jwplayer--content"><div id="jwplayer-blk"></div></div></div>');
        $("." + $.facebox.element + ' .modal-header').prepend("<h4>" + filename + "</h4>");
        /* 
        ------following code can be used if need to set height/width of jw player and need to set the . Other wise we have the option to set the aspect ratio.

        var width = $('.jwplayer--content').width() || 0;
        var height = $('.jwplayer--content').height() || 0;

        if (1 > width) {
            width = $('#facebox').width();
        }
        if (1 > height) {
            height = $('#facebox').height();
        } */

        var type = type || "mp4";
        var jwplayerInst = jwplayer("jwplayer-blk").setup({
            file: videoPath,
            type: type,
            /* width: width,
            height: height, */
            aspectratio: "16:9",
            events: {
                onTime: function (object) {
                    if (object.position > object.duration - 1) {
                        this.pause();
                    }
                }
            }
        }).play();
    });
}