$(window).on("load", function () {
	setTimeout(function () {
		$("body").addClass("loaded");
		stylePhoneNumberFld(".phone-js");
	}, 1000);

	if (
		0 < $("#scrollElement-js").length &&
		0 < $(".menu__item.is-active").length
	) {
		var scrollPosition =
			$(".menu__item.is-active").position().top -
			($(window).height() / 2 - 100);
		$("#scrollElement-js").animate({ scrollTop: scrollPosition }, 1000);
	}
});

$(function () {
	$(document).on("click", ".selectItem--js", function () {
		if ($(this).prop("checked") == false) {
			$(".selectAll-js").prop("checked", false);
			$(this).closest("tr").removeClass("selected-row");
		} else {
			$(this).closest("tr").addClass("selected-row");
		}
		if ($(".selectItem--js").length == $(".selectItem--js:checked").length) {
			$(".selectAll-js").prop("checked", true);
		}
		showFormActionsBtns();
	});
	if (0 < $(".js-widget-scroll").length) {
		// slickWidgetScroll();
	}
	$(document).on("click", ".accordianheader", function () {
		$(this).next(".accordianbody").slideToggle();
		$(this).parent().parent().siblings().children().children().next().slideUp();
		return false;
	});

	/* Binding Feather Light gallery */
	bindFeatherLight();
	/* Binding Feather Light gallery */
});
$(document).on("keyup", "input.otpVal-js", function (e) {
	if ("" != $(this).val()) {
		$(this).removeClass("is-invalid");
	}
	var element = "";
	if (8 != e.which && "" != $(this).val()) {
		element = $(this).parents(".otpCol-js").nextAll()[0];
	} else {
		element = $(this).parents(".otpCol-js").prevAll()[0];
	}
	element = $(element).find("input.otpVal-js");
	if ("undefined" != typeof element) {
		element.focus();
	}
});

showSignInForm = function () {
	$(".socialSigninJs").hide();
	$(".localSigninJs").fadeIn();
};

hideSignInForm = function () {
	$(".localSigninJs").hide();
	$(".socialSigninJs").fadeIn();
};

installJsColor = function () {
	if (0 < $(".jscolor").length) {
		$(".jscolor").each(function () {
			$(this).attr("data-jscolor", "{}");
		});
		jscolor.install();
	}
};
installJsColor();
unlinkSlick = function () {
	$(".js-widget-scroll").slick("unslick");
};
/*slickWidgetScroll = function () {
	var slides = $(".widget-stats").length > 2 ? 3 : 2;
	$(".js-widget-scroll").slick(
		getSlickSliderSettings(slides, 1, langLbl.layoutDirection, false, {
			1199: 3,
			1023: 2,
			767: 1,
			480: 1,
		});
	);
};*/
invalidOtpField = function () {
	$("input.otpVal-js")
		.val("")
		.addClass("is-invalid")
		.attr("onkeyup", "checkEmpty($(this))");
};
checkEmpty = function (element) {
	if ("" == element.val()) {
		element.addClass("is-invalid");
	}
};
var otpIntervalObj;
startOtpInterval = function (parent = "", callback = "", params = []) {
	if ("undefined" != typeof otpIntervalObj) {
		clearInterval(otpIntervalObj);
	}
	var parent = "" != parent ? parent + " " : "";
	var element = $(parent + ".intervalTimer-js");
	var counter = langLbl.otpInterval;
	element.parent().parent().show();
	element.text(counter);
	$(parent + ".getOtpBtnBlock--js").addClass("d-none");
	var resendOtpEle = $(parent + ".resendOtp-js");
	var onClickFn = resendOtpEle.attr("onclick");
	resendOtpEle.removeAttr("onclick");
	otpIntervalObj = setInterval(function () {
		counter--;
		if (counter === 0) {
			clearInterval(otpIntervalObj);
			resendOtpEle.attr("onclick", onClickFn).removeClass("disabled");
			element.parent().parent().hide();
			if ("" != callback && eval("typeof " + callback) == "function") {
				window[callback](params);
			}
		}
		element.text(counter);
	}, 1000);
};
loginPopupOtp = function (userId, getOtpOnly = 0) {
	fcom.displayProcessing();
	fcom.ajax(
		fcom.makeUrl("GuestUser", "resendOtp", [userId, getOtpOnly]),
		"",
		function (t) {
			t = $.parseJSON(t);
			if (1 > t.status) {
				fcom.displayErrorMessage(t.msg);
				return false;
			}
			$.ykmsg.close();
			var parent = "";
			fcom.updateFaceboxContent(t.html);
			$(".contentBody--js .modal-header").addClass("border-0");
			$("#logoOtp").hide();
			if (0 < $(".loginpopup--js").length) {
				var parent = ".loginpopup--js";
			}
			var formClass = "";
			if ($(".contentBody--js form").hasClass("loginpopup--js")) {
				formClass = "form.loginpopup--js";
			}
			$(formClass + " .countdownFld--js, " + formClass + " .resendOtp-js")
				.parent()
				.removeClass("d-none");
			$(
				formClass + ".otpFieldBlock--js," + formClass + " .countdownFld--js"
			).removeClass("d-none");
			startOtpInterval(parent);
		}
	);
	return false;
};

function setCurrDateFordatePicker() {
	$(".start_date_js").datepicker("option", {
		minDate: new Date(),
	});
	$(".end_date_js").datepicker("option", {
		minDate: new Date(),
	});
}

function showFormActionsBtns() {
	if (typeof $(".selectItem--js:checked").val() === "undefined") {
		$(".formActionBtn-js").addClass("disabled");
	} else {
		$(".formActionBtn-js").removeClass("disabled");
	}
	var validateActionButtons = setInterval(function () {
		if (1 > $(".selectItem--js:checked").length) {
			$(".formActionBtn-js").addClass("disabled");
			clearInterval(validateActionButtons);
		}
		if ($(".formActionBtn-js").hasClass("disabled")) {
			clearInterval(validateActionButtons);
		}
	}, 1000);
}

function selectAll(obj) {
	$(".selectItem--js").each(function () {
		if (obj.prop("checked") == false) {
			$(this).prop("checked", false).closest("tr").removeClass("selected-row");
		} else {
			$(this).prop("checked", true).closest("tr").addClass("selected-row");
		}
	});
	showFormActionsBtns();
}

function formAction(frm, callback) {
	if (typeof $(".selectItem--js:checked").val() === "undefined") {
		fcom.displayErrorMessage(langLbl.atleastOneRecord);
		return false;
	}
	fcom.displayProcessing();
	data = fcom.frmData(frm);
	fcom.updateWithAjax(frm.action, data, function (resp) {
		callback();
	});
}

function initialize() {
	if (typeof google == "undefined") {
		return;
	}
	geocoder = new google.maps.Geocoder();
}

function getCountryStates(countryId, stateId, dv) {
	fcom.ajax(
		fcom.makeUrl("GuestUser", "getStates", [countryId, stateId]),
		"",
		function (res) {
			$(dv).empty();
			$(dv).append(res);
		}
	);
}

function getStatesByCountryCode(
	countryCode,
	stateCode,
	dv,
	idCol = "state_id"
) {
	fcom.ajax(
		fcom.makeUrl("GuestUser", "getStatesByCountryCode", [
			countryCode,
			stateCode,
			idCol,
		]),
		"",
		function (res) {
			$(dv).empty();
			$(dv).append(res).change();
		}
	);
}

function interRelatedProducts(selprodId) {
	if (typeof selprodId == "undefined") {
		selprodId = 0;
	}
	fcom.updateWithAjax(
		fcom.makeUrl("Products", "interRelatedProducts", [selprodId]),
		"",
		function (ans) {
			fcom.removeLoader();
			if ("" != ans.relatedProductsHtml) {
				$(".relatedProductsSectionJs").replaceWith(ans.relatedProductsHtml);
			} else {
				$(".relatedProductsSectionJs").remove();
			}

			if ("" != ans.recommendedProductsHtml) {
				$(".recommendedProductsSectionJs").replaceWith(
					ans.recommendedProductsHtml
				);
			} else {
				$(".recommendedProductsSectionJs").remove();
			}

			if ("" != ans.recentViewedProductsHtml) {
				$(".recentlyViewedProductsSectionJs").replaceWith(
					ans.recentViewedProductsHtml
				);
			} else {
				$(".recentlyViewedProductsSectionJs").remove();
			}
		},
		{ fOutMode: "json" }
	);
}

function resendVerificationLink(user) {
	if (user == "") {
		return false;
	}
	fcom.updateWithAjax(
		fcom.makeUrl("GuestUser", "resendVerification", [user]),
		"",
		function (ans) {
			fcom.displaySuccessMessage(ans.msg);
		}
	);
}

function getCardType(number) {
	var re = new RegExp("^4");
	if (number.match(re) != null) return "Visa";
	re = new RegExp("^5[1-5]");
	if (number.match(re) != null) return "Mastercard";
	re = new RegExp("^3[47]");
	if (number.match(re) != null) return "AMEX";
	re = new RegExp(
		"^(6011|622(12[6-9]|1[3-9][0-9]|[2-8][0-9]{2}|9[0-1][0-9]|92[0-5]|64[4-9])|65)"
	);
	if (number.match(re) != null) return "Discover";
	re = new RegExp("^36");
	if (number.match(re) != null) return "Diners";
	re = new RegExp("^30[0-5]");
	if (number.match(re) != null) return "Diners - Carte Blanche";
	re = new RegExp("^35(2[89]|[3-8][0-9])");
	if (number.match(re) != null) return "JCB";
	re = new RegExp("^(4026|417500|4508|4844|491(3|7))");
	if (number.match(re) != null) return "Visa Electron";
	return "";
}
viewWishList = function (selprod_id, dv, event, excludeWishList = 0) {
	event.stopPropagation();
	if ($(dv).next().hasClass("is-item-active")) {
		$(dv).next().toggleClass("open-menu");
		$(dv).parent().toggleClass("list-is-active");
		return;
	}
	$(".collection-toggle").next().removeClass("is-item-active");
	if (isUserLogged() == 0) {
		loginPopUpBox();
		return false;
	}
	$.facebox(function () {
		fcom.ajax(
			fcom.makeUrl(
				"Account",
				"viewWishList",
				[selprod_id, excludeWishList],
				siteConstants.webroot_dashboard
			),
			"",
			function (ans) {
				fcom.updateFaceboxContent(ans);
				$("input[name=uwlist_title]").bind("focus", function (e) {
					e.stopPropagation();
				});
				activeFavList = selprod_id;
			}
		);
	});
	return false;
};
toggleShopFavorite = function (shop_id) {
	fcom.displayProcessing();
	var data = "shop_id=" + shop_id;
	fcom.updateWithAjax(
		fcom.makeUrl(
			"Account",
			"toggleShopFavorite",
			[],
			siteConstants.webroot_dashboard
		),
		data,
		function (ans) {
			fcom.removeLoader();
			fcom.displaySuccessMessage(ans.msg);
			if (ans.status) {
				if (ans.action == "A") {
					$("#shop_" + shop_id).addClass("active");
					$("#shop_" + shop_id).prop("title", "Unfavorite Shop");
				} else if (ans.action == "R") {
					$("#shop_" + shop_id).removeClass("active");
					$("#shop_" + shop_id).prop("title", "Favorite Shop");
				}
			}
		}
	);
};
setupWishList = function (frm, event) {
	if (!$(frm).validate()) return false;
	var data = fcom.frmData(frm);
	var selprod_id = $(frm).find('input[name="selprod_id"]').val();
	fcom.updateWithAjax(
		fcom.makeUrl(
			"Account",
			"setupWishList",
			[],
			siteConstants.webroot_dashboard
		),
		data,
		function (ans) {
			if (ans.status) {
				fcom.ajax(
					fcom.makeUrl(
						"Account",
						"viewWishList",
						[selprod_id],
						siteConstants.webroot_dashboard
					),
					"",
					function (ans) {
						$(".collection-ui-popup").html(ans);
						$("input[name=uwlist_title]").bind("focus", function (e) {
							e.stopPropagation();
						});
					}
				);
				if (ans.productIsInAnyList) {
					$("[data-id=" + selprod_id + "]").addClass("is-active");
				} else {
					$("[data-id=" + selprod_id + "]").removeClass("is-active");
				}
			}
		}
	);
};
addRemoveWishListProduct = function (selprod_id, wish_list_id, event) {
	event.stopPropagation();
	wish_list_id =
		typeof wish_list_id != "undefined" ? parseInt(wish_list_id) : 0;
	var dv = ".collection-ui-popup";
	var action = "addRemoveWishListProduct";
	var alternateData = "";
	if (0 >= selprod_id) {
		var oldWishListId = $("input[name='uwlist_id']").val();
		if (typeof oldWishListId !== "undefined" && wish_list_id != oldWishListId) {
			action = "updateRemoveWishListProduct";
			alternateData = $("#wishlistForm").serialize();
		}
	}

	if (0 < $("#cartList").length) {
		$("#cartList").prepend(fcom.getLoader());
	}
	fcom.updateWithAjax(
		fcom.makeUrl(
			"Account",
			action,
			[selprod_id, wish_list_id],
			siteConstants.webroot_dashboard
		),
		alternateData,
		function (ans) {
			if (ans.status == 1) {
				$(document).trigger("close.facebox");
				$(dv + " .active").removeClass("active");
				if (ans.productIsInAnyList) {
					$("[data-id=" + selprod_id + "]").addClass("active");
				} else {
					$("[data-id=" + selprod_id + "]").removeClass("active");
				}
				if (ans.action == "A") {
					ykevents.addToWishList();
					$(dv)
						.find(".wishListCheckBox_" + ans.wish_list_id)
						.addClass("active");
				} else if (ans.action == "R") {
					$(dv)
						.find(".wishListCheckBox_" + ans.wish_list_id)
						.removeClass("active");
				}
				if ("updateRemoveWishListProduct" == action) {
					viewWishListItems(oldWishListId);
				}

				if ("function" == typeof listCartProducts) {
					listCartProducts();
				}
			}
		}
	);
};
removeFromCart = function (key) {
	var data = "key=" + key;
	fcom.updateWithAjax(fcom.makeUrl("Cart", "remove"), data, function (ans) {
		fcom.removeLoader();
		if (ans.status) {
			if (ans.total == 0) {
				$(".emtyCartBtn-js").hide();
			}
			listCartProducts();
			cart.loadCartSummary();
		}
		fcom.closeProcessing();
		fcom.displaySuccessMessage(langLbl.MovedSuccessfully);
	});
};

function submitSiteSearch(frm, page) {
	ykevents.search();
	var keyword = $.trim($(frm).find('input[name="keyword"]').val());
	keyword = keyword.replace("&", "++");
	if (3 > keyword.length || "" === keyword) {
		fcom.displayErrorMessage(langLbl.searchString);
		return;
	}
	var qryParam = $(frm).serialize_without_blank();
	var urlString = "";
	if (qryParam.indexOf("keyword") > -1) {
		var protomatch = /^(https?|ftp):\/\//;
		urlString =
			urlString +
			setQueryParamSeperator(urlString) +
			"keyword-" +
			encodeURIComponent(
				keyword.replace(protomatch, "") /* .replace(/\//g, "-") */
			) +
			"&pagesize-" +
			page;
	}
	if (
		qryParam.indexOf("category") > -1 &&
		$(frm).find('input[name="category"]').val() > 0
	) {
		urlString =
			urlString +
			setQueryParamSeperator(urlString) +
			"category-" +
			$(frm).find('input[name="category"]').val();
	}
	url = productSearchUrl + urlString;
	document.location.href = url;
}

/*function getSlickGallerySettings(
	imagesForNav,
	layoutDirection,
	slidesToShow = 4,
	slidesToScroll = 1
) {
	slidesToShow =
		typeof slidesToShow != "undefined" ? parseInt(slidesToShow) : 4;
	slidesToScroll =
		typeof slidesToScroll != "undefined" ? parseInt(slidesToScroll) : 1;
	layoutDirection =
		typeof layoutDirection != "undefined" ? layoutDirection : "ltr";
	if (imagesForNav) {
		var sliderSettings = {
			slidesToShow: slidesToShow,
			slidesToScroll: slidesToScroll,
			asNavFor: ".slider-for",
			dots: false,
			centerMode: false,
			focusOnSelect: true,
			autoplay: true,
			arrows: true,
			responsive: [
				{
					breakpoint: 1499,
					settings: {
						slidesToShow: 4,
					},
				},
				{
					breakpoint: 1199,
					settings: {
						slidesToShow: 4,

					},
				},
				{
					breakpoint: 767,
					settings: {
						slidesToShow: 2,

					},
				},
			],
		};
		if ($(window).width() < 1025 && layoutDirection == "rtl") {
			sliderSettings["rtl"] = true;
		}
	} else {
		var sliderSettings = {
			slidesToShow: 1,
			slidesToScroll: 1,
			arrows: false,
			fade: true,
			autoplay: true,
		};
		if (layoutDirection == "rtl") {
			sliderSettings["rtl"] = true;
		}
	}
	return sliderSettings;
}
var screenResolutionForSlider = {
	1199: 4,
	1023: 3,
	767: 2,
	480: 2,
	375: 1,
};

function getSlickSliderSettings(
	slidesToShow,
	slidesToScroll,
	layoutDirection,
	autoInfinitePlay,
	slidesToShowForDiffResolution,
	adaptiveHeight
) {
	slidesToShow =
		typeof slidesToShow != "undefined" ? parseInt(slidesToShow) : 4;
	slidesToScroll =
		typeof slidesToScroll != "undefined" ? parseInt(slidesToScroll) : 1;
	layoutDirection =
		typeof layoutDirection != "undefined" ? layoutDirection : "ltr";
	autoInfinitePlay =
		typeof autoInfinitePlay != "undefined" ? autoInfinitePlay : true;
	adaptiveHeight = typeof adaptiveHeight != "undefined" ? adaptiveHeight : true;
	if (typeof slidesToShowForDiffResolution != "undefined") {
		slidesToShowForDiffResolution = $.extend(
			screenResolutionForSlider,
			slidesToShowForDiffResolution
		);
	} else {
		slidesToShowForDiffResolution = screenResolutionForSlider;
	}
	var sliderSettings = {
		dots: false,
		slidesToShow: slidesToShow,
		slidesToScroll: slidesToScroll,
		infinite: autoInfinitePlay,
		autoplay: autoInfinitePlay,
		adaptiveHeight: adaptiveHeight,
		arrows: true,
		responsive: [
			{
				breakpoint: 1199,
				settings: {
					slidesToShow: slidesToShowForDiffResolution[1199],
				},
			},
			{
				breakpoint: 1023,
				settings: {
					slidesToShow: slidesToShowForDiffResolution[1023],
				},
			},
			{
				breakpoint: 767,
				settings: {
					slidesToShow: slidesToShowForDiffResolution[767],
				},
			},
			{
				breakpoint: 480,
				settings: {
					slidesToShow: slidesToShowForDiffResolution[480],
					arrows: false,
					dots: true,
				},
			},
			{
				breakpoint: 375,
				settings: {
					slidesToShow: slidesToShowForDiffResolution[375],
					arrows: false,
					dots: true,
				},
			},
		],
	};
	if (layoutDirection == "rtl") {
		sliderSettings["rtl"] = true;
	}
	return sliderSettings;
}*/

function codeLatLng(lat, lng, callback) {
	initialize();
	var latlng = new google.maps.LatLng(lat, lng);
	geocoder.geocode(
		{
			latLng: latlng,
		},
		function (results, status) {
			if (status == google.maps.GeocoderStatus.OK) {
				if (results[1]) {
					var lat = results[0]["geometry"]["location"].lat();
					var lng = results[0]["geometry"]["location"].lng();
					for (var i = 0; i < results[0].address_components.length; i++) {
						if (results[0].address_components[i].types[0] == "country") {
							var country = results[0].address_components[i].long_name;
						}
						if (results[0].address_components[i].types[0] == "country") {
							var country_code = results[0].address_components[i].short_name;
						}
						if (
							results[0].address_components[i].types[0] ==
							"administrative_area_level_1"
						) {
							var state_code = results[0].address_components[i].short_name;
							var state = results[0].address_components[i].long_name;
						}
						if (
							results[0].address_components[i].types[0] ==
							"administrative_area_level_2"
						) {
							var city = results[0].address_components[i].long_name;
						}
						if (results[0].address_components[i].types[0] == "postal_code") {
							var postal_code = results[0].address_components[i].long_name;
						}
					}
					var data = {
						country: country,
						country_code: country_code,
						state: state,
						state_code: state_code,
						city: city,
						lat: lat,
						lng: lng,
						postal_code: postal_code,
					};
					callback(data);
				} else {
					console.log("Geocoder No results found");
				}
			} else {
				console.log("Geocoder failed due to: " + status);
			}
		}
	);
}

function defaultSetUpLogin(frm, v) {
	var formClass = "";
	var isPopup = $(frm).hasClass("loginpopup--js");
	if ($(".submitBtn--js").css("display") == "none") {
		$(".getOtpBtnBlock--js button").trigger("click");
		return;
	}

	if (isPopup) {
		formClass = "form.loginpopup--js ";
		$.ykmodal(fcom.getLoader(), true);
	} else {
		$(".loginFormJs").prepend(fcom.getLoader());
	}

	if (
		0 < $(formClass + ".loginWithOtp--js").length &&
		0 < $(formClass + ".loginWithOtp--js").val()
	) {
		$(formClass + "input.otpVal-js").each(function () {
			if ("undefined" == typeof $(this).val() || "" == $(this).val()) {
				$(formClass + '.pwdField--js input[name="password"]').attr(
					"data-fatreq",
					'{"required":false}'
				);
				invalidOtpField();
				fcom.removeLoader();
				fcom.displayErrorMessage(langLbl.requiredFields);
				return false;
			}
		});
	}
	v.validate();
	if (!v.isValid()) {
		fcom.removeLoader();
		return false;
	}
	fcom.ajax(
		fcom.makeUrl("GuestUser", "login"),
		fcom.frmData(frm),
		function (ans) {
			fcom.removeLoader();
			if (ans.status == 1) {
				fcom.displaySuccessMessage(ans.msg);
				setTimeout(() => {
					location.href = ans.redirectUrl;
				}, 1000);
				return;
			}
			fcom.displayErrorMessage(ans.msg);
		},
		{ fOutMode: "json" }
	);
	return false;
}
sendResetPasswordLink = function (user) {
	if (user == "") {
		return false;
	}
	fcom.displayProcessing();
	fcom.updateWithAjax(
		fcom.makeUrl("GuestUser", "sendResetPasswordLink", [user]),
		"",
		function (ans) {
			fcom.displaySuccessMessage(ans.msg);
		}
	);
};
(function ($) {
	var screenHeight = $(window).height() - 100;
	window.onresize = function (event) {
		var screenHeight = $(window).height() - 100;
	};
	$.extend(fcom, {
		processingCounter: 0,
		processingClass: "processingJs",
		getLoader: function (addAsNew = false) {
			$(document.body).css({ cursor: "wait" });
			return '<div class="processing loaderJs"><div class="spinner spinner--sm spinner--brand"></div></div>';
		},
		getPageLoader: function () {
			return '<div class="page-loader"><span> Loading... <i class="loader-line"></i></span></div>';
		},
		scrollToTop: function (obj) {
			if (typeof obj == undefined || obj == null) {
				$("html, body").animate(
					{
						scrollTop: $("html, body").offset().top - 100,
					},
					"slow"
				);
			} else {
				$("html, body").animate(
					{
						scrollTop: $(obj).offset().top - 100,
					},
					"slow"
				);
			}
		},
		resetEditorInstance: function () {
			if (extendEditorJs == true) {
				var editors = oUtil.arrEditor;
				for (x in editors) {
					eval("delete window." + editors[x]);
				}
				oUtil.arrEditor = [];
			}
		},
		resetEditorWidth: function (width = "100%") {
			if (typeof oUtil != "undefined") {
				oUtil.arrEditor.forEach(function (input) {
					var oEdit1 = eval(input);
					$("#idArea" + oEdit1.oName).attr("width", width);
				});
			}
		},
		setEditorLayout: function (lang_id) {
			if (extendEditorJs == true) {
				var editors = oUtil.arrEditor;
				layout = langLbl["language" + lang_id];
				for (x in editors) {
					$("#idContent" + editors[x])
						.contents()
						.find("body")
						.css("direction", layout);
				}
			}
		},
		displayProcessing: function () {
			$.ykmsg.clear();
			fcom.processingCounter++;
			$.ykmsg.info(
				langLbl.processing,
				-1,
				fcom.processingClass +
				" " +
				fcom.processingClass +
				"-" +
				fcom.processingCounter
			);
		},
		closeProcessing: function (counter) {
			var cls = fcom.processingClass;
			if (typeof counter !== "undefined") {
				cls += "-" + counter;
			}
			$("." + cls).remove();
			//$.ykmsg.close();
		},

		displaySuccessMessage: function (msg) {
			$.ykmsg.close();
			$.ykmsg.success(msg);
		},

		displayErrorMessage: function (msg) {
			$.ykmsg.close();
			$.ykmsg.error(msg);
		},

		getModalBody: function () {
			return '<div class="modal fade" id="modalBoxJs"  data-backdrop="static" data-keyboard="false" tabindex="-1" role="dialog" aria-labelledby="modalBoxJsLabel" aria-hidden="true"><div class="modal-dialog modal-dialog-centered modal-lg" role="document"><div class="modal-content"><div class="modal-header"><h6 class="modal-title"></h6><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div><div class="modal-body"><div class="table-processing loaderJs"><div class="spinner spinner--sm spinner--brand"></div></div></div><div class="modal-footer"></div></div></div></div>';
		},
		removeLoader: function (cls) {
			$(document.body).css({ cursor: "default" });
			$(".loaderJs").remove();
			$(".submitBtnJs").removeClass("loading").removeAttr("disabled");
		},
		getRowSpinner: function () {
			return '<div class="spinner spinner--v2 spinner--sm spinner--brand"></div>';
		},
		updateFaceboxContent: function (t, cls) {
			if (typeof cls == "undefined" || cls == "undefined") {
				cls = "";
			}
			$.facebox(t, cls);
			$.ykmsg.close();
			fcom.resetFaceboxHeight();
		},
		resetFaceboxHeight: function () {
			facebocxHeight = screenHeight;
			var fbContentHeight =
				parseInt($("#facebox .content").height()) + parseInt(150);
			setTimeout(function () {
				$("#facebox .content").css(
					"max-height",
					parseInt(facebocxHeight) - parseInt(facebocxHeight) / 4 + "px"
				);
			}, 700);
			$("#facebox .content").css("overflow-y", "auto");
			if (fbContentHeight > screenHeight - parseInt(100)) {
				$("#facebox .content").css("display", "block");
			} else {
				$("#facebox .content").css("max-height", "");
			}
		},
	});

	$.fn.serialize_without_blank = function () {
		var $form = this,
			result,
			$disabled = $([]);
		$form.find(":input").each(function () {
			var $this = $(this);
			if ($.trim($this.val()) === "" && !$this.is(":disabled")) {
				$disabled.add($this);
				$this.attr("disabled", true);
			}
		});
		result = $form.serialize();
		$disabled.removeAttr("disabled");
		return result;
	};
})(jQuery);
$(function () {
	var typingTimer;
	var doneTypingInterval = 400;
	var $input = $("#header_search_keyword");
	$input.focus(function (e) {
		searchProductTagsAuto($input.val());
	});
	$input.keyup(function (e) {
		clearTimeout(typingTimer);
		typingTimer = setTimeout(doneTyping, doneTypingInterval);
	});
	$input.keydown(function (e) {
		clearTimeout(typingTimer);
	});
	doneTyping = function (e) {
		searchProductTagsAuto($input.val());
	};
	$(document).on("keyup", "input, textarea", function (event) {
		if ($(this).val().length > 0) {
			$(this).addClass("filled");
		} else {
			$(this).removeClass("filled");
		}
	});
	$(document).on("click", ".recentSearch-js", function () {
		$input.val($(this).parent("li").attr("data-keyword"));
		searchProductTagsAuto($(this).parent("li").attr("data-keyword"));
	});
	$(document).on("click", ".clearSearch-js", function () {
		var obj = $(this).hasClass("clear-all") ? "all" : "";
		clearSearchKeyword(obj);
	});
});
$(document).mouseup(function (e) {
	var container = $("#search-suggestions-js");
	var inputFld = $("#header_search_keyword");
	if (
		!container.is(e.target) &&
		container.has(e.target).length === 0 &&
		!inputFld.is(e.target) &&
		inputFld.has(e.target).length === 0
	) {
		$("#search-suggestions-js").html("");
	}
});
$(function () {
	var searchSuggestionsJs = $("#search-suggestions-js");
	var currentRequest = null;
	removeAutoSuggest = function () {
		$("#header_search_keyword").val("");
		searchSuggestionsJs.html("");
	};
	searchTags = function (obj) {
		var frmSiteSearch = document.frmSiteSearch;
		$(frmSiteSearch.keyword).val($(obj).data("txt"));
		$(frmSiteSearch).trigger("submit");
	};
	searchProductTagsAuto = function (keyword) {
		if (parseInt($(window).width()) < 768 || keyword.length < 3) {
			return;
		}
		var data = "keyword=" + keyword;
		if (currentRequest != null) {
			currentRequest.abort();
		}
		currentRequest = fcom.updateWithAjax(
			fcom.makeUrl("Products", "searchProductTagsAutocomplete"),
			data,
			function (t) {
				if (t.html.length > 0) {
					if (!searchSuggestionsJs.find("div").hasClass("search-suggestions")) {
						searchSuggestionsJs.html(
							'<div class="search-suggestions" id="tagsSuggetionList"></div>'
						);
					}
					$("#tagsSuggetionList").html(t.html);
				} else {
					searchSuggestionsJs.html("");
				}
			},
			"",
			false
		);
	};
	clearSearchKeyword = function (obj) {
		var data = "";
		var keyword = $(obj).attr("data-keyword");
		if (typeof keyword != "undefined") {
			data = "keyword=" + keyword;
		}
		fcom.ajax(
			fcom.makeUrl("Products", "clearSearchKeywords"),
			data,
			function (t) {
				if ("all" == obj) {
					$("#search-suggestions-js").html("");
				} else {
					$(obj).closest("li").remove();
					if (
						0 < $("#search-suggestions-js").length &&
						1 > $(".recentSearch-js").length
					) {
						$("#search-suggestions-js").html("");
					}
				}
			}
		);
	};
	if (
		$(".system_message").find(".div_error").length > 0 ||
		$(".system_message").find(".div_msg").length > 0 ||
		$(".system_message").find(".div_info").length > 0 ||
		$(".system_message").find(".div_msg_dialog").length > 0
	) {
		$(".system_message").show();
	}
	$(".close").on("click", function () {
		$(".system_message").hide();
	});
	markAsFavorite = function (selProdId) {
		if (isUserLogged() == 0) {
			loginPopUpBox();
			return false;
		}
		$.ykmsg.close();
		fcom.ajax(
			fcom.makeUrl(
				"Account",
				"markAsFavorite",
				[selProdId],
				siteConstants.webroot_dashboard
			),
			"",
			function (ans) {
				if (ans.status) {
					$("[data-id=" + selProdId + "]").addClass("active");
					$("[data-id=" + selProdId + "]").attr(
						"onclick",
						"removeFromFavorite(" + selProdId + ")"
					);
					$("[data-id=" + selProdId + "] span").attr(
						"title",
						langLbl.RemoveProductFromFavourite
					);
					fcom.displaySuccessMessage(ans.msg);
				}
			},
			{ fOutMode: "json" }
		);
	};

	removeFromFavorite = function (selProdId, callbackFunction = false) {
		if (isUserLogged() == 0) {
			loginPopUpBox();
			return false;
		}
		$.ykmsg.close();
		fcom.ajax(
			fcom.makeUrl(
				"Account",
				"removeFromFavorite",
				[selProdId],
				siteConstants.webroot_dashboard
			),
			"",
			function (ans) {
				if (ans.status) {
					$("[data-id=" + selProdId + "]").removeClass("active");
					$("[data-id=" + selProdId + "]").attr(
						"onclick",
						"markAsFavorite(" + selProdId + ")"
					);
					$("[data-id=" + selProdId + "] span").attr(
						"title",
						langLbl.AddProductToFavourite
					);
					fcom.displaySuccessMessage(ans.msg);
				}
			},
			{ fOutMode: "json" }
		);
		if (callbackFunction !== false) {
			window[callbackFunction]();
		}
	};
	guestUserFrm = function () {
		fcom.ajax(fcom.makeUrl("GuestUser", "form"), "", function (t) {
			fcom.updateFaceboxContent(t);
		});
	};
	signInWithPhone = function (obj, flag) {
		var form = $(obj).data("form");
		var formElement =
			"undefined" != typeof form ? 'form[name="' + form + '"]' : "form";

		var data = "signInWithPhone=" + parseInt(flag);
		if (parseInt(flag) == 0) {
			var data = "signInWithEmail=1";
		}

		var popup = $(formElement).closest("." + $.ykmodal.element);
		if (0 < popup.length) {
			$.ykmodal(fcom.getLoader(), true);
			data += "&signinpopup=1";
		} else {
			$(".loginFormJs").prepend(fcom.getLoader());
		}
		fcom.updateWithAjax(
			fcom.makeUrl("GuestUser", "loginForm"),
			data,
			function (t) {
				if (0 < popup.length) {
					$.ykmodal(t.html, true);
				} else {
					$(".loginFormJs").replaceWith(t.html);
				}
				fcom.removeLoader();
				stylePhoneNumberFld(formElement + " input[name='username']", !flag);
			}
		);
	};

	getLoginOtp = function (obj) {
		var formClass = "";
		var isPopup = $(obj).closest("form").hasClass("loginpopup--js");
		if (isPopup) {
			formClass = "form.loginpopup--js ";
			$.ykmodal(fcom.getLoader(), true);
		} else {
			$(".loginFormJs").prepend(fcom.getLoader());
		}

		var phone = $(formClass + 'input[name="username"]').val();
		var dialCode = $(formClass + 'input[name="username_dcode"]').val();

		if (
			"undefined" == typeof phone ||
			"" == phone ||
			"undefined" == typeof dialCode ||
			"" == dialCode
		) {
			$(obj).closest("form").submit();
			fcom.removeLoader();
			fcom.displayErrorMessage(langLbl.requiredFields);
			return false;
		}

		fcom.displayProcessing();
		var data =
			"username=" +
			$(formClass + 'input[name="username"]').val() +
			"&username_dcode=" +
			$(formClass + 'input[name="username_dcode"]').val();
		fcom.ajax(fcom.makeUrl("GuestUser", "getLoginOtp", []), data, function (t) {
			fcom.removeLoader();
			t = $.parseJSON(t);
			if (1 > t.status) {
				fcom.displayErrorMessage(t.msg);
				return false;
			}
			fcom.closeProcessing();
			$(obj).closest(".getOtpBtnBlock--js").hide();
			$(formClass + " .submitBtn--js").show();
			$(formClass + " .resendOtp-js").addClass("disabled");
			$(formClass + '.pwdField--js input[name="password"]').attr(
				"data-fatreq",
				'{"required":false}'
			);
			$(formClass + ".loginWithOtp--js").val(1);
			$(formClass + " .countdownFld--js, " + formClass + " .resendOtp-js")
				.parent()
				.show();
			$(
				formClass + ".otpFieldBlock--js," + formClass + " .countdownFld--js"
			).show();
			startOtpInterval(formClass);
		});
		return false;
	};

	validateRegOtp = function (frm) {
		if (!$(frm).validate()) return;
		var data = fcom.frmData(frm);
		$("#sign-up").prepend(fcom.getLoader());
		fcom.ajax(fcom.makeUrl("GuestUser", "validateOtp"), data, function (t) {
			fcom.removeLoader();
			t = $.parseJSON(t);
			if (1 == t.status) {
				fcom.displaySuccessMessage(t.msg);
				setTimeout((location.href = t.redirectUrl), 2000);
				return;
			} else {
				fcom.displayErrorMessage(t.msg);
				invalidOtpField();
			}
		});
		return false;
	};

	openSignInForm = function (includeGuestLogin) {
		if (typeof includeGuestLogin == "undefined") {
			includeGuestLogin = false;
		}
		data = "includeGuestLogin=" + includeGuestLogin + "&signinpopup=1";
		fcom.updateWithAjax(
			fcom.makeUrl("GuestUser", "loginForm"),
			data,
			function (t) {
				fcom.closeProcessing();
				$.ykmodal(t.html, true);
				fcom.removeLoader();
			}
		);
	};

	showLanguageDropdown = function () {
		$.ykmodal(fcom.getLoader(), true);
		fcom.ajax(fcom.makeUrl("Home", "languageArea"), '', function (ans) {
			fcom.removeLoader();
			$.ykmodal(ans, true, '', '', '', false);
		});
	};

	setGeoLocation = function () {
		fcom.displayProcessing();
		fcom.ajax(fcom.makeUrl("Home", "setGeoLocation"), '', function (ans) {
			fcom.closeProcessing();
			$.ykmodal(ans, true, '', '', '', false);
			googleAddressAutocomplete('ga-autoComplete-header');
		});
	};

	guestUserLogin = function (frm, v) {
		v.validate();
		if (!v.isValid()) return;
		fcom.displayProcessing();
		fcom.ajax(
			fcom.makeUrl("GuestUser", "guestLogin"),
			fcom.frmData(frm),
			function (t) {
				var ans = JSON.parse(t);
				if (ans.status == 1) {
					fcom.displaySuccessMessage(ans.msg);
					location.href = ans.redirectUrl;
					return;
				}
				fcom.displayErrorMessage(ans.msg);
			}
		);
		return false;
	};
	autofillLangData = function (autoFillBtn, frm) {
		var actionUrl = autoFillBtn.data("action");
		var defaultLangField = $("input.defaultLang", frm);
		if (1 > defaultLangField.length) {
			fcom.displayErrorMessage(langLbl.unknownPrimaryLanguageField);
			return false;
		}
		var proceed = true;
		var stringToTranslate = "";
		defaultLangField.each(function (index) {
			if ("" != $(this).val()) {
				if (0 < index) {
					stringToTranslate += "&";
				}
				stringToTranslate += $(this).attr("name") + "=" + $(this).val();
			} else {
				$(this).focus();
				fcom.displayErrorMessage(langLbl.primaryLanguageField);
				proceed = false;
				return false;
			}
		});
		if (true == proceed) {
			fcom.displayProcessing();
			fcom.ajax(actionUrl, stringToTranslate, function (t) {
				var res = $.parseJSON(t);
				$.each(res, function (langId, values) {
					$.each(values, function (selector, value) {
						$("input.langField_" + langId + "[name='" + selector + "']").val(
							value
						);
					});
				});
			});
		}
	};

	redirectfunc = function (url, orderStatus) {
		var input =
			'<input type="hidden" name="status" value="' + orderStatus + '">';
		$('<form action="' + url + '" method="POST">' + input + "</form>")
			.appendTo($(document.body))
			.submit();
	};
	$(document).on("click", ".wishListJs", function () {
		if (isUserLogged() == 0) {
			loginPopUpBox();
			return false;
		}
		window.location.href = fcom.makeUrl(
			"account",
			"wishlist",
			[],
			siteConstants.webroot_dashboard
		);
	});

	$(document).on("click", ".sign-in-popup-js", function () {
		openSignInForm();
	});

	$(".cc-cookie-accept-js").on("click", function () {
		var data = {
			statistical_cookies: 1,
			personalise_cookies: 1,
		};
		updateUserCookies(data);
	});
	$(".cookie-preferences-js").on("click", function () {
		$.facebox(function () {
			fcom.ajax(
				fcom.makeUrl("Custom", "cookiePreferencesData"),
				"",
				function (t) {
					fcom.updateFaceboxContent(t);
				}
			);
		});
	});
	setUserCookiePreferences = function () {
		var statisticalCookies = 0;
		if ($("input[name='statistical_cookies']").prop("checked") == true) {
			statisticalCookies = 1;
		}
		var personaliseCookies = 0;
		if ($("input[name='personalise_cookies']").prop("checked") == true) {
			personaliseCookies = 1;
		}
		var data = {
			statistical_cookies: statisticalCookies,
			personalise_cookies: personaliseCookies,
		};
		updateUserCookies(data);
	};
	updateUserCookies = function (data) {
		$("#cookieInfoBox").hide();
		fcom.ajax(
			fcom.makeUrl("Custom", "updateUserCookies"),
			data,
			function (rsp) {
				var ans = $.parseJSON(rsp);
				if (ans.status == 0) {
					fcom.displayErrorMessage(ans.msg);
				} else {
					$("#cookieInfoBox").hide("slow");
					$("#cookieInfoBox").remove();
					/*  $.cookie('ykStatisticalCookies', data.statistical_cookies, { expires: 10, path: siteConstants.rooturl });
							   $.cookie('ykPersonaliseCookies', data.personalise_cookies, { expires: 10, path: siteConstants.rooturl }); */
					$.facebox.close();
				}
			}
		);
	};
	$(document).on("click", ".increase-js", function () {
		var type = $('input[name="fulfillment_type"]:checked').val();
		if ($(this).hasClass("disabled")) {
			return false;
		}
		$(this).siblings(".disabled").removeClass("disabled");
		var rval = $(this).parent().parent("div").find("input").val();
		if (isNaN(rval)) {
			$(this).parent().parent("div").find("input").val(1);
			return false;
		}
		var key = $(this).parent().parent("div").find("input").attr("data-key");
		var page = $(this).parent().parent("div").find("input").attr("data-page");
		val = parseInt(rval) + 1;
		if (val > $(this).parent().data("stock")) {
			val = $(this).parent().data("stock");
			$(this).addClass("disabled");
		}
		if (
			$(this).hasClass("disabled") &&
			rval >= $(this).parent().data("stock")
		) {
			return false;
		}
		$(this).parent().parent("div").find("input").val(val);
		if (page == "product-view") {
			return false;
		}

		var section = "";
		if (0 < $(".checkout-content-js").length) {
			section = $(".checkout-content-js");
		}
		if (0 < $("#cartList").length) {
			section = $("#cartList");
		}
		section.prepend(fcom.getLoader());
		cart.update(key, page, type);
	});
	$(document).on("keyup", ".productQty-js", function () {
		if ($(this).val() > $(this).parent().data("stock")) {
			val = $(this).parent().data("stock");
			var message = langLbl.quantityAdjusted.replace(/{qty}/g, val);
			fcom.displaySuccessMessage(message);
			$(this).parent().parent("div").find(".increase-js").addClass("disabled");
			$(this)
				.parent()
				.parent("div")
				.find(".decrease-js")
				.removeClass("disabled");
		} else if ($(this).val() <= 0) {
			val = 1;
			$(this).parent().parent("div").find(".decrease-js").addClass("disabled");
			$(this)
				.parent()
				.parent("div")
				.find(".increase-js")
				.removeClass("disabled");
		} else {
			val = $(this).val();
			if (
				$(this).parent().parent("div").find(".decrease-js").hasClass("disabled")
			) {
				$(this)
					.parent()
					.parent("div")
					.find(".decrease-js")
					.removeClass("disabled");
			}
			if (
				$(this).parent().parent("div").find(".increase-js").hasClass("disabled")
			) {
				$(this)
					.parent()
					.parent("div")
					.find(".increase-js")
					.removeClass("disabled");
			}
		}
		$(this).val(val);
		var key = $(this).attr("data-key");
		var page = $(this).attr("data-page");
		if (page == "product-view") {
			return false;
		}
	});
	$(document).on("blur", ".productQty-js", function () {
		var key = $(this).attr("data-key");
		var page = $(this).attr("data-page");
		if (page == "product-view") {
			return false;
		}
		var fulfillmentType = $("input[name='fulfillment_type']:checked").val();

		var section = "";
		if (0 < $(".checkout-content-js").length) {
			section = $(".checkout-content-js");
		}
		if (0 < $("#cartList").length) {
			section = $("#cartList");
		}
		section.prepend(fcom.getLoader());
		cart.addCallBackFn = function (results) {
			loadShippingSummaryDiv();
		};
		cart.update(key, page, fulfillmentType);
	});

	$(document).on("click", ".decrease-js", function () {
		var type = $('input[name="fulfillment_type"]:checked').val();
		if ($(this).hasClass("disabled")) {
			return false;
		}
		$(this).siblings(".disabled").removeClass("disabled");
		var rval = $(this).parent().parent("div").find("input").val();
		if (isNaN(rval)) {
			$(this).parent().parent("div").find("input").val(1);
			return false;
		}
		var key = $(this).parent().parent("div").find("input").attr("data-key");
		var page = $(this).parent().parent("div").find("input").attr("data-page");
		var minQty = $(this)
			.parent()
			.parent("div")
			.find("input")
			.attr("data-min-qty");
		var minVal = minQty > 1 ? minQty : 1;
		val = parseInt(rval) - 1;
		if (val <= minVal) {
			val = minVal;
			$(this).addClass("disabled");
		}
		if ($(this).hasClass("disabled") && rval <= minVal) {
			return false;
		}
		$(this).parent().parent("div").find("input").val(val);
		if (page == "product-view") {
			return false;
		}

		var section = "";
		if (0 < $(".checkout-content-js").length) {
			section = $(".checkout-content-js");
		}
		if (0 < $("#cartList").length) {
			section = $("#cartList");
		}
		section.prepend(fcom.getLoader());
		cart.update(key, page, type);
	});
	$(document).on("click", ".setactive-js li", function () {
		$(this).closest(".setactive-js").find("li").removeClass("is-active");
		$(this).addClass("is-active");
	});
	$(document).on("keydown", "input[name=user_username]", function (e) {
		if (e.which === 32) {
			return false;
		}
		this.value = this.value.replace(/\s/g, "");
	});
	$(document).on("change", "input[name=user_username]", function (e) {
		this.value = this.value.replace(/\s/g, "");
	});
	$(document).on("submit", "form", function () {
		moveErrorAfterIti();
	});
	$(document).on(
		"keyup",
		"form .iti input[data-intl-tel-input-id]",
		function () {
			moveErrorAfterIti();
		}
	);
});

function moveErrorAfterIti() {
	if (0 < $(".iti .errorlist").length) {
		$(".iti .errorlist").detach().insertAfter(".iti");
	}
}

function isUserLogged() {
	var isUserLogged = 0;
	$.ajax({
		method: "POST",
		url: fcom.makeUrl("GuestUser", "checkAjaxUserLoggedIn"),
		data: "fIsAjax=1",
		async: false,
		dataType: "json",
	}).done(function (ans) {
		isUserLogged = parseInt(ans.isUserLogged);
	});
	return isUserLogged;
}

function loginPopUpBox(includeGuestLogin) {
	openSignInForm(includeGuestLogin);
}

function setSiteDefaultLang(langId) {
	var url = window.location.pathname;
	var srchString = window.location.search;
	var data = "pathname=" + url;
	fcom.ajax(
		fcom.makeUrl("Home", "setLanguage", [langId]),
		data,
		function (res) {
			var ans = $.parseJSON(res);
			if (ans.status == 1) {
				window.location.href = ans.redirectUrl + srchString;
			}
		}
	);
}

function setSiteDefaultCurrency(currencyId) {
	var currUrl = window.location.href;
	fcom.ajax(
		fcom.makeUrl("Home", "setCurrency", [currencyId]),
		"",
		function (res) {
			document.location.reload();
		}
	);
}

function stylePhoneNumberFld(
	element = "input[name='user_phone']",
	destroy = false
) {
	var inputList = document.querySelectorAll(element);
	var country =
		"" == langLbl.defaultCountryCode ||
			"undefined" == typeof langLbl.defaultCountryCode
			? "in"
			: langLbl.defaultCountryCode;
	inputList.forEach(function (input) {
		var form = input.closest("form");
		if (true == destroy) {
			$(input, form).removeAttr("style");
			var clone = input.cloneNode(true);
			$(".iti").replaceWith(clone);
		} else {
			if ($(input, form).hasClass("hasFlag-js")) {
				return;
			}

			$(input, form).addClass("hasFlag-js");
			var hasOnlyFlag = $(element, form).hasClass("onlyFlag--js");
			var elementName = $(input, form).attr("name") + "_dcode";
			var dialCodeElement = $('input[name="' + elementName + '"]', form);

			if (false === hasOnlyFlag) {
				if (
					0 < dialCodeElement.length &&
					"" != dialCodeElement.val() &&
					"undefined" != typeof dialCodeElement.val()
				) {
					var elementVal = dialCodeElement.val();
					var countryCodePos = elementVal.indexOf("-");
					if (0 < countryCodePos) {
						country = elementVal.substring(
							countryCodePos + 1,
							elementVal.length
						);
					} else {
						country = getCountryIso2CodeFromDialCode(parseInt(elementVal));
					}
				}
			}
			var iti = window.intlTelInput(input, {
				separateDialCode: !hasOnlyFlag,
				initialCountry: country,
			});
			var dialCode = "+" + iti.getSelectedCountryData().dialCode;
			var dialCodeWithPhone =
				dialCode + "-" + iti.getSelectedCountryData().iso2;
			$(input, form).attr("data-before", dialCode);
			if (1 == dialCodeElement.length && false === hasOnlyFlag) {
				dialCodeElement.insertAfter(input);
				if ("" == dialCodeElement.val()) {
					dialCodeElement.val(dialCodeWithPhone);
				}
			} else if (1 > dialCodeElement.length && false === hasOnlyFlag) {
				$("<input>")
					.attr({
						type: "hidden",
						name: elementName,
						value: dialCodeWithPhone,
					})
					.insertAfter(input, form);
			} else if (true === hasOnlyFlag) {
				var phoneNumber = $(input, form).val();
				if ("undefined" == typeof phoneNumber || "" == phoneNumber) {
					phoneNumber = dialCode;
				} else if (-1 == phoneNumber.indexOf("+")) {
					phoneNumber = dialCode + phoneNumber;
				}
				$(input, form).val(phoneNumber);
			}

			input.addEventListener("countrychange", function (e) {
				if (typeof iti.getSelectedCountryData().dialCode !== "undefined") {
					var dialCode = "+" + iti.getSelectedCountryData().dialCode;
					var dialCodeWithPhone =
						dialCode + "-" + iti.getSelectedCountryData().iso2;
					if (false === hasOnlyFlag) {
						if ($('input[name="' + elementName + '"]', form).length < 1) {
							fcom.displaySuccessMessage(
								$(input, form).attr("name") +
								" " +
								langLbl.dialCodeFieldNotFound,
								"alert-danger"
							);
							return;
						}
						$('input[name="' + elementName + '"]', form).val(dialCodeWithPhone);
					} else {
						var phoneNumber = $(input).val();
						if ("undefined" == typeof phoneNumber || "" == phoneNumber) {
							phoneNumber = dialCode;
						} else if (-1 == phoneNumber.indexOf("+")) {
							phoneNumber = dialCode + phoneNumber;
						} else if ($(input, form).data("before") != dialCode) {
							phoneNumber = phoneNumber.replace(
								$(input, form).data("before"),
								dialCode
							);
						}
						$(input, form).val(phoneNumber);
					}
					$(input, form).attr("data-before", dialCode);
				}
			});
			input.addEventListener("keyup", function (e) {
				if (true === hasOnlyFlag && "+" != input.value.charAt(0)) {
					input.value = "+" + input.value;
				}
			});
		}
	});
}

function getCountryIso2CodeFromDialCode(dialCode) {
	var countriesData = window.intlTelInputGlobals.getCountryData();
	var countryData = countriesData.filter(function (country) {
		return country.dialCode == dialCode;
	});
	return countryData[0].iso2;
}
$(document).on("click", ".readMore", function () {
	var $this = $(this);
	var $moreText = $this.siblings(".moreText");
	var $lessText = $this.siblings(".lessText");
	if ($this.hasClass("expanded")) {
		$moreText.hide();
		$lessText.fadeIn();
		$this.text($linkMoreText);
	} else {
		$lessText.hide();
		$moreText.fadeIn();
		$moreText.removeClass("hidden");
		$this.text($linkLessText);
	}
	$this.toggleClass("expanded");
});
$(document).on("click", "#btn-demo", function () {
	$.facebox(function () {
		fcom.ajax(fcom.makeUrl("Custom", "requestDemo"), "", function (t) {
			fcom.updateFaceboxContent(t);
		});
	});
});
$(document).on("click", ".add-to-cart--js", function (event) {
	ykevents.addToCart();
	event.preventDefault();
	var selprodId = $(this).siblings('input[name="selprod_id"]').val();
	var quantity = document.frmBuyProduct.quantity.value;
	var cartHasProducts = $(this).data("cartHasProduct");
	if (0 < cartHasProducts && !confirm(langLbl.overwriteCartItems)) {
		return false;
	}
	cart.add(selprodId, quantity);
	return false;
});
$(function () {
	if ($(window).width() < 1025) {
		$("html").removeClass("sticky-demo-header");
		$("div.demo-header").hide();
	}
});

$(document).ajaxComplete(function () {
	stylePhoneNumberFld(".phone-js");
	if (0 < $("#facebox").length) {
		if ($("#facebox").is(":visible")) {
			$("html").addClass("pop-on");
		} else {
			$("html").removeClass("pop-on");
		}
		$("#facebox .close.close--white").on("click", function () {
			$("html").removeClass("pop-on");
		});
	}
	$("body").on("click", function () {
		if ($("html").hasClass("pop-on")) {
			$("html").removeClass("pop-on");
		}
	});
	installJsColor();

	/* Binding Feather Light gallery */
	bindFeatherLight();
	/* -------------------------- */

	/* Binding Slick slider element if loaded after ajax complete. */
	if ("undefined" != typeof initCarousel) {
		initCarousel();
	}
	/* -------------------------- */
});
$(function () {
	$("body")
		.find("*[data-trigger]")
		.on("click", function () {
			var targetElmId = $(this).data("trigger");
			var elmToggleClass = targetElmId + "--on";
			if ($("body").hasClass(elmToggleClass)) {
				$("body").removeClass(elmToggleClass);
			} else {
				$("body").addClass(elmToggleClass);
			}
		});
	$("body")
		.find("*[data-bs-target-close]")
		.on("click", function () {
			var targetElmId = $(this).data("target-close");
			$("body").toggleClass(targetElmId + "--on");
		});
	$("body").on("mouseup", function (event) {
		if (
			$(event.target).data("trigger") != "" &&
			typeof $(event.target).data("trigger") !== typeof undefined
		) {
			event.preventDefault();
			return;
		}
		$("body")
			.find("*[data-close-on-click-outside]")
			.each(function (idx, elm) {
				var slctr = $(elm);
				if (!slctr.is(event.target) && !$.contains(slctr[0], event.target)) {
					$("body").removeClass(slctr.data("close-on-click-outside") + "--on");
				}
			});
	});
	$("body").tooltip({
		selector: "[data-toggle=tooltip]",
	});
});
$(document).on("change", "input[type='file']", fileSizeValidation);

function fileSizeValidation() {
	const fsize = this.files[0].size;
	if (fsize > langLbl.allowedFileSize) {
		var msg = langLbl.fileSizeExceeded;
		var msg = msg.replace("{size-limit}", bytesToSize(langLbl.allowedFileSize));
		fcom.displayErrorMessage(msg);
		$(this).val("");
		return false;
	}
}

function bytesToSize(bytes) {
	var sizes = ["Bytes", "KB", "MB", "GB", "TB"];
	if (bytes == 0) return "0 Byte";
	var i = parseInt(Math.floor(Math.log(bytes) / Math.log(1024)));
	return Math.round(bytes / Math.pow(1024, i), 2) + " " + sizes[i];
}
$(".form")
	.find("input, textarea, select")
	.each(function () {
		if ($(this).val() != "") {
			$(this).addClass("filled");
		} else {
			$(this).removeClass("filled");
		}
	});
$(".dropdown-menu").on("click", function (e) {
	e.stopPropagation();
});

function awebersignup() {
	var content = $(".aweber-js").html();
	fcom.updateFaceboxContent(content);
	var weberformload = setInterval(function () {
		if (0 < $(".aweberform-js form").length) {
			var myForm = $(".aweberform-js form")[0];
			myForm.onsubmit = function () {
				var popwidth = 500,
					popheight = 700,
					popleft = $(window).width() / 2 - popwidth / 2,
					poptop = $(window).height() / 2 - popheight / 2,
					popup = window.open(
						"",
						"popup",
						"width=" +
						popwidth +
						", height=" +
						popheight +
						", top=" +
						poptop +
						", left=" +
						popleft
					);
				this.target = "popup";
				$(document).trigger("close.facebox");
			};
			clearInterval(weberformload);
		}
	}, 1000);
}
var imagesPreview = function (input, placeToInsertImagePreview) {
	if (input.files) {
		if (1 > $(placeToInsertImagePreview + " ul").length) {
			$(placeToInsertImagePreview).html(
				'<ul class="js-review-media-list"></ul>'
			);
		}
		var fileFldName = $(input).attr("name");
		var filesAmount = input.files.length;
		for (i = 0; i < filesAmount; i++) {
			let selectedFile = input.files[i];
			var reader = new FileReader();
			reader.onload = function (event) {
				var htm =
					'<li><div class="uploaded-file"><span class="uploaded-file__thumb"></span><a href="javascript:void(0);" class="close-layer close-layer-sm fileRemove--js" data-filefld="' +
					fileFldName +
					'"></a></div></li>';
				$(placeToInsertImagePreview + " ul").append(htm);
				$(
					$.parseHTML(
						'<img class="imgToUpload--js" title="' + selectedFile.name + '">'
					)
				)
					.attr("src", event.target.result)
					.appendTo(
						placeToInsertImagePreview +
						" ul li:last-child .uploaded-file__thumb"
					);
			};
			reader.readAsDataURL(input.files[i]);
		}
	}
};

function DataURIToBlob(dataURI) {
	const splitDataURI = dataURI.split(",");
	const byteString =
		splitDataURI[0].indexOf("base64") >= 0
			? atob(splitDataURI[1])
			: decodeURI(splitDataURI[1]);
	const mimeString = splitDataURI[0].split(":")[1].split(";")[0];
	const ia = new Uint8Array(byteString.length);
	for (let i = 0; i < byteString.length; i++) ia[i] = byteString.charCodeAt(i);
	return new Blob([ia], {
		type: mimeString,
	});
}

$(document).on("click", ".fileRemove--js", function () {
	$(this).closest("li").remove();
});

function previewImage(obj, e) {
	e.preventDefault();
	var imgUrl = $("img", obj).data("altimg");
	if ("" == imgUrl || "undefined" == typeof imgUrl) {
		imgUrl = $("img", obj).attr("src");
	}
	var img = $($.parseHTML("<img>")).attr("src", imgUrl).get(0).outerHTML;
	fcom.updateFaceboxContent(img, "text-center");
	return false;
}

function loadMoreImages(obj, e) {
	e.preventDefault();
	$("a", obj).removeAttr("data-count").attr("onclick", "previewImage(this)");
	$(obj).removeClass("more-media").removeAttr("onclick");
	$(obj).nextAll().removeClass("d-none");
	return false;
}

function bindFeatherLight() {
	if (0 < $(".featherLightGalleryJs").length) {
		if ("undefined" == typeof $.fn.featherlightGallery) {
			fcom.displayErrorMessage(
				"Please Include Feather Light JS Library Files."
			);
			return;
		}

		$(".featherLightGalleryJs").each(function () {
			$(this).find("[data-featherlight]").featherlightGallery({
				previousIcon: "«",
				nextIcon: "»",
				galleryFadeIn: 300,
				openSpeed: 300,
			});
		});
	}
}

function redirectUrl(url) {
	window.location.href = url;
}
$.extend(fcom, {
	copyToClipboard: function (targetId) {
		var targetId = targetId || "_copytext_";
		var target = document.getElementById(targetId);
		target.select();
		target.focus();
		target.setSelectionRange(0, target.value.length);
		var succeed = true;
		try {
			succeed = document.execCommand("copy");
			fcom.displaySuccessMessage(langLbl.copied);
		} catch (e) {
			succeed = false;
		}
		return succeed;
	},
});

copyText = function (obj, applyToolTipInfo = true) {
	if (applyToolTipInfo) {
		var title = $(obj).data("title");
	} else {
		var title = $(obj).attr("data-url");
	}

	if (!navigator.clipboard) {
		console.warn("clipboard API only works on localhost and https");
		// Clipboard API  only works on localhost anf https as per doc
		return;
	}
	try {
		navigator.clipboard.writeText(title);
		if (applyToolTipInfo) {
			tooltipCopyHelper(obj, title);
		}
	} catch (err) {
		console.error("Failed to copy!", err);
	}
};

tooltipCopyHelper = function (obj, title) {
	title = "undefined" == typeof title || "" == title ? "" : ": " + title;
	$(obj)
		.tooltip("hide")
		.attr("data-bs-original-title", langLbl.copied + title)
		.tooltip("update")
		.tooltip("show");

	$(obj).mouseout(function () {
		$(obj)
			.tooltip("hide")
			.attr("data-bs-original-title", langLbl.clickToCopy + title)
			.tooltip("update");
		$(obj).unbind("mouseout");
	});
};

/* Check if element is in viewport. */
$.fn.isInViewport = function () {
	let elem = $(this);
	// if the element doesn't exist, abort
	if (elem.length == 0) {
		return;
	}
	var $window = jQuery(window);
	var viewport_top = $window.scrollTop();
	var viewport_height = $window.height();
	var viewport_bottom = viewport_top + viewport_height;
	var $elem = jQuery(elem);
	var top = $elem.offset().top;
	var height = $elem.height();
	var bottom = top + height;

	return (
		(top >= viewport_top && top < viewport_bottom) ||
		(bottom > viewport_top && bottom <= viewport_bottom) ||
		(height > viewport_height &&
			top <= viewport_top &&
			bottom >= viewport_bottom)
	);
};

bindMaxLengthValidator = function () {
	$("[maxlength]").maxlength({
		alwaysShow: true,
		threshold: 10,
		warningClass: "badge badge-info",
		limitReachedClass: "badge badge-warning",
		placement: "top",
		message: langLbl.maxLengthValidator,
	});
};

$(document).ready(function () {
	bindMaxLengthValidator();
});

$(document).ajaxComplete(function () {
	bindMaxLengthValidator();
});

/*
expected response
{
  "results": [
	{
	  "id": 1,
	  "text": "Option 1"
	},
	{
	  "id": 2,
	  "text": "Option 2"
	}
  ],
  "pageCount" : 3 
}

postdata object| callback function like {record:1}
*/
select2 = function (
	elmId,
	url,
	postdata = {},
	callbackOnSelect = "",
	callbackOnUnSelect = "",
	processResultsCallback = "",
	data = [],
) {
	let ele = $("#" + elmId);
	if (1 > ele.length) {
		return false;
	}

	var obj = ele.closest('form').length ? ele.closest('form') : null;
	ele.select2({
		dropdownParent: ele.data('dropdownparent-id') ? $('#' + ele.data('dropdownparent-id')) : obj,
		closeOnSelect: ele.data("closeOnSelect") || true,
		data: data,
		/*dir: layoutDirection,*/
		allowClear: ele.data("allowClear") || true,
		placeholder: ele.attr("placeholder") || "",
		ajax: {
			url: url,
			dataType: "json",
			delay: 250,
			method: "post",
			data: function (params) {
				return $.extend(
					{
						keyword: params.term, // search term
						page: params.page,
						fIsAjax: 1,
						fOutMode: 'json'
					},
					("function" == typeof postdata ? postdata(ele) : postdata)
				);
			},
			processResults: function (data, params) {
				if (1 > data.status) {
					fcom.displayErrorMessage(data.msg);
				}
				params.page = params.page || 1;
				data.pageCount = data.pageCount || 1;
				if ("function" == typeof processResultsCallback) {
					return processResultsCallback(data, params, ele);
				}
				return {
					results: data.results,
					pagination: {
						more: params.page < data.pageCount,
					},
				};
			},
			cache: true,
		},
		minimumInputLength: 0,
		dropdownPosition: "below",
	}).on("select2:selecting", function (e) {
		if ("function" == typeof callbackOnSelect) {
			callbackOnSelect(e);
		}
	}).on("select2:unselecting", function (e) {
		if ("function" == typeof callbackOnUnSelect) {
			callbackOnUnSelect(e);
		}
	}).on('select2:open', function (e) {
		if (ele.attr('multiple') == undefined) {
			$('#select2-' + elmId + '-results').closest('.select2-dropdown').addClass("custom-select2 custom-select2-single")
		} else {
			$('#select2-' + elmId + '-results').closest('.select2-dropdown').addClass("custom-select2 custom-select2-multiple");
		}
	});

	var select2Selector = ele.data("select2");
	var elementName = ele.attr('name').replace('[]', '');
	select2Selector.$container.addClass("custom-select2");

	if ('undefined' != typeof (select2Selector.dropdown)) {
		$(select2Selector.dropdown.$search).attr({ 'name': elementName + '-select2', 'autocomplete': 'no' });
	}

	if ('undefined' != typeof (select2Selector.selection)) {
		$(select2Selector.selection.$search).attr({ 'name': elementName + '-select2', 'autocomplete': 'no' });
	}

	if (0 < ele.closest(".advancedSearchJs").length || 0 < ele.closest(".form-group").length) {
		select2Selector.$container.addClass("custom-select2-width");
	}


	if (ele.attr('multiple') != undefined) {
		select2Selector.$container.addClass("custom-select2 custom-select2-multiple");
	} else {
		select2Selector.$container.addClass("custom-select2 custom-select2-single");
	}
	$("." + $.ykmodal.element).removeAttr("tabindex");
};