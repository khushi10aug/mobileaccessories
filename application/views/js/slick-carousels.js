/* Start of Common Carousel.
This setting is applicable if you want to stop slick slider at any specific resolution.

Pass data-destroy="1,1,1,0". attribute to js-carousel element.
e.g. 1200 = 1(destroy)
     992 = 1(destroy)
     768 = 1(destroy)
     576 = 0(Run Slick slider)
*/

function loadSlickSlider() {
    var _carousel = $(".js-carousel");
    _carousel.each(function (index) {
        var _this = $(this),
            slidesData = _this.data("slides"),
            _slidesToShow = (slidesData != undefined && slidesData !== null && slidesData !== "")
                ? slidesData.toString().split(",")
                : ["3", "2", "1", "1", "1"];

        if (_this.hasClass("slick-initialized")) {
            return;
        }

        var slideCount = _this.children().length;
        if (slideCount < 1) {
            return;
        }

        var optionsArr = {
            slidesToShow: parseInt(
                _slidesToShow.length > 0 ? _slidesToShow[0] : "3"
            ),
            slidesToScroll: 1,
            centerMode: _this.data("mode"),
            arrows: _this.data("arrows"),
            /*// prevArrow: (() => { return $('.prev-arrow[data-href="#' + _this.attr("id") + '"]') })(),
        // nextArrow: (() => { return $('.next-arrow[data-href="#' + _this.attr("id") + '"]') })(),*/
            vertical: _this.data("vertical"),
            dots: _this.data("slickdots"),
            /*// appendDots: '.slider-controls[data-href="#' + _this.attr("id") + '"]',*/
            infinite: _this.data("infinite"),
            variableWidth:
                _this.data("variablewidth") != undefined
                    ? _this.data("variablewidth")
                    : false,
            autoplay: false,
            pauseOnHover: false,
            swipe:
                _this.data("swipe") != undefined ? _this.data("swipe") : true,
            swipeToSlide:
                _this.data("swipetoslide") != undefined
                    ? _this.data("swipetoslide")
                    : true,
            centerPadding: 0,
            adaptiveHeight: _this.data("adaptiveHeight"),
            rtl: "rtl" == langLbl.layoutDirection,
            responsive: [
                {
                    breakpoint: 1200,
                    settings: {
                        slidesToShow: parseInt(
                            parseInt(
                                _slidesToShow.length > 1
                                    ? _slidesToShow[1]
                                    : "2"
                            )
                        ),
                        vertical: false,
                    },
                },
                {
                    breakpoint: 992,
                    settings: {
                        slidesToShow: parseInt(
                            parseInt(
                                _slidesToShow.length > 2
                                    ? _slidesToShow[2]
                                    : "1"
                            )
                        ),
                        vertical: false,
                    },
                },
                {
                    breakpoint: 768,
                    settings: {
                        slidesToShow: parseInt(
                            parseInt(
                                _slidesToShow.length > 3
                                    ? _slidesToShow[3]
                                    : "1"
                            )
                        ),
                        vertical: false,
                    },
                },
                {
                    breakpoint: 576,
                    settings: {
                        slidesToShow: parseInt(
                            parseInt(
                                _slidesToShow.length > 4
                                    ? _slidesToShow[4]
                                    : "1"
                            )
                        ),
                        vertical: false,
                    },
                },
            ],
        };

        /* Prevent Slick ADA init crash on low/empty slide sets when dots are enabled. */
        if (slideCount <= 1) {
            optionsArr["dots"] = false;
            optionsArr["arrows"] = false;
            optionsArr["swipe"] = false;
            optionsArr["draggable"] = false;
        }
        if (_this.data("arrows") == true) {
            if (
                _this.data("customarrow") != undefined &&
                _this.data("customarrow") != ""
            ) {
                optionsArr["prevArrow"] = $(
                    "." + _this.data("arrowcontainer") + " .arrow-prev"
                );
                optionsArr["nextArrow"] = $(
                    "." + _this.data("arrowcontainer") + " .arrow-next"
                );
            }
        }
        if (_this.data("slickdots") == true) {
            if (
                _this.data("dotscontainer") != undefined &&
                _this.data("dotscontainer") != ""
            ) {
                optionsArr["appendDots"] = $("." + _this.data("dotscontainer"));
            }
        }

        _this.slick(optionsArr);
    });
}

$(document).ready(function () {
    loadSlickSlider();
});

/* End of Common Carousel */
