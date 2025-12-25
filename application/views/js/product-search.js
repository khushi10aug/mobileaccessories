var searchArr = [];
var page = 1;

/* Reset result on clear(cross) icon on keyword search field. */
$(document).on("search", ".filterSearchJs", function () {
    if ("" == $(this).val()) {
        $("input[id=keyword]").val("");
        reloadProductListing(document.frmProductSearch);
        showSelectedFilters();
    }
});
/* Reset result on clear(cross) icon on keyword search field. */

$(function () {
    /* [is use to reload page when user hit back button */
    $(window).on("popstate", function () {
        location.reload(true);
    });
    /* is use to reload page when user hit back button] */

    if ("undefined" != typeof document.frmProductSearch) {
        var frm = document.frmProductSearch;
        var frmSiteSearch = document.frmSiteSearch;
        $(frmSiteSearch.keyword).val($(frm.keyword).val());
        setSelectedCatValue($(frm.category).val());

        $.each(frm.elements, function (index, elem) {
            if (
                elem.type != "text" &&
                elem.type != "textarea" &&
                elem.type != "hidden" &&
                elem.type != "submit"
            ) {
                /* i.e for selectbox */
                $(elem).change(function () {
                    reloadProductListing(frm);
                });
            }
        });
    }
    /* ] */

    /* form submit upon onchange of elements not inside form tag[ */
    if (typeof isBrandPage !== "undefined" && isBrandPage !== null) {
        $("input[name=brands]").attr("disabled", "disabled");
        $("input[name=brands]").parent("label").addClass("disabled");
    }

    $(document).on("change", "input[name=brands]", function () { 
        var id = $(this).attr("data-id");
        var val = $(this).val();
        var title = $(this).attr("data-title");
        if ($(this).is(":checked")) {
            if ($("#" + id).length == 0) {
                $("ul.brandFilter-js").prepend(
                    '<li><label class="checkbox brand" id="brand_' +
                    val +
                    '"><input name="brands" data-id="brand_' +
                    val +
                    '" value="' +
                    val +
                    '" data-title="' +
                    title +
                    '" type="checkbox" checked="true"><i class="input-helper">' +
                    title +
                    "</i><label></li>"
                );
            }

            $("input:checkbox[name=brands]").each(function () {
                if ($(this).attr("data-id") == id) {
                    $(this).prop("checked", true);
                }
            });
            addFilter(id, this);
            addToSearchQueryString(id, this);
        } else {
            removeFilter(id, false);
        }
        removePaginationFromLink();
        reloadProductListing(frm);
    });

    $(document).on("change", "input[name=cbrands]", function () { 
        var id = $(this).attr("data-id");
        var val = $(this).val();
        var title = $(this).attr("data-title");
        if ($(this).is(":checked")) {
            if ($("#" + id).length == 0) {
                $("ul.cbrandFilter-js").prepend(
                    '<li><label class="checkbox cbrand" id="cbrand_' +
                    val +
                    '"><input name="cbrands" data-id="cbrand_' +
                    val +
                    '" value="' +
                    val +
                    '" data-title="' +
                    title +
                    '" type="checkbox" checked="true"><i class="input-helper">' +
                    title +
                    "</i><label></li>"
                );
            }

            $("input:checkbox[name=cbrands]").each(function () {
                if ($(this).attr("data-id") == id) {
                    $(this).prop("checked", true);
                }
            });
            addFilter(id, this);
            addToSearchQueryString(id, this);
        } else {
            removeFilter(id, false);
        }
        removePaginationFromLink();
        reloadProductListing(frm);
    });


/*
    $(document).on("change", "input[name=model]", function () { 
        var id = $(this).attr("data-id");
        var val = $(this).val();
        var safeVal = makeModelId(val); // remove spaces
        var title = $(this).attr("data-title");
        if ($(this).is(":checked")) {
            if ($("#model_" + safeVal).length === 0) {
                $("ul.modelFilter-js").prepend(
                    '<li><label class="checkbox model" id="model_' +
                    safeVal +
                    '"><input name="model" data-id="model_' +
                    safeVal +
                    '" value="' +
                    val +
                    '" data-title="' +
                    title +
                    '" type="checkbox" checked="true"><i class="input-helper">' +
                    title +
                    "</i><label></li>"
                );
            }

            $("input:checkbox[name=model]").each(function () {
                if ($(this).attr("data-id") == id) {
                    $(this).prop("checked", true);
                }
            });
            safeVal = 'model_'+safeVal;
            addFilter(safeVal, this);
            addToSearchQueryString(safeVal, this);
        } else {
            removeFilter(safeVal, false);
        }
        removePaginationFromLink();
        reloadProductListing(frm);
    });
*/

$(document).on("change", "input[name=model]", function () { 
    var id = $(this).attr("data-id");
    var val = $(this).val();
    var title = $(this).attr("data-title");
    if ($(this).is(":checked")) {
        if ($("#" + id).length == 0) {
            $("ul.modelFilter-js").prepend(
                '<li><label class="checkbox model" id="model_' +
                val +
                '"><input name="model" data-id="model_' +
                val +
                '" value="' +
                val +
                '" data-title="' +
                title +
                '" type="checkbox" checked="true"><i class="input-helper">' +
                title +
                "</i><label></li>"
            );
        }

        $("input:checkbox[name=model]").each(function () {
            if ($(this).attr("data-id") == id) {
                $(this).prop("checked", true);
            }
        });
        addFilter(id, this);
        addToSearchQueryString(id, this);
    } else {
        removeFilter(id, false);
    }
    removePaginationFromLink();
    reloadProductListing(frm);
});

    $(document).on("change", "input[name=category]", function () {
        var id = $(this).parent().parent().find("label").attr("id");
        if ($(this).is(":checked")) {
            addFilter(id, this);
            addToSearchQueryString(id, this);
        } else {
            removeFilter(id, false);
        }
        removePaginationFromLink();
        reloadProductListing(frm);
    });

    $(document).on("change", "input[name=optionvalues]", function () {
        var id = $(this).parent().parent().find("label").attr("id");
        if ($(this).is(":checked")) {
            addFilter(id, this);
            addToSearchQueryString(id, this);
        } else {
            removeFilter(id, false);
        }
        removePaginationFromLink();
        reloadProductListing(frm);
    });

    $(document).on("change", "input[name=conditions]", function () {
        var id = $(this).parent().parent().find("label").attr("id");
        if ($(this).is(":checked")) {
            addFilter(id, this);
            addToSearchQueryString(id, this);
        } else {
            removeFilter(id, false);
        }
        removePaginationFromLink();
        reloadProductListing(frm);
    });

    $(document).on("change", "input[name=free_shipping]", function () {
        alert("Pending...");
    });

    $(document).on("change", "select[name=pageSizeSelect]", function () {
        var selectedVal = $(this).val();
        $("#pageSize").val(selectedVal);
        $("form[name=frmProductSearch] input[name=viewType]").val("");
        removePageSideFromLink();
        removePaginationFromLink();
        reloadProductListing(document.frmProductSearch);
    });
    $(document).on("change", "select[name=pageSizeSelectMap]", function () {
        var selectedVal = $(this).val();
        $("#pageSize").val(selectedVal);
        $("form[name=frmProductSearch] input[name=viewType]").val(
            "popupProduct"
        );
        removePageSideFromLink();
        removePaginationFromLink();
        reloadProductListingMap(document.frmProductSearch);
    });

    $(document).on("change", "select[name=sortBy]", function () {
        removePageSideFromLink();
        removePaginationFromLink();
        reloadProductListing();
    });

    $(document).on("change", "input[name=out_of_stock]", function () {
        var id = $(this).parent().parent().find("label").attr("id");
        if ($(this).is(":checked")) {
            addFilter(id, this);
            addToSearchQueryString(id, this);
        } else {
            removeFilter(id, false);
        }
        removePaginationFromLink();
        reloadProductListing(frm);
    });

    $(document).on("blur", "input[name=priceFilterMinValue]", function (e) {
        validatePriceFilter();
        e.preventDefault();
        removePaginationFromLink();
        addPricefilter(true);
    });

    $(document).on("blur", "input[name=priceFilterMaxValue]", function (e) {
        validatePriceFilter();
        e.preventDefault();
        removePaginationFromLink();
        addPricefilter(true);
    });

    $(document).on("keyup", "input[name=priceFilterMinValue]", function (e) {
        var code = e.which;
        if (code == 13) {
            e.preventDefault();
            removePaginationFromLink();
            addPricefilter(true);
        }
    });

    $(document).on("keyup", "input[name=priceFilterMaxValue]", function (e) {
        var code = e.which;
        if (code == 13) {
            e.preventDefault();
            removePaginationFromLink();
            addPricefilter(true);
        }
    });

    /* ] */

    $(window).on("load", function () {
        showSelectedFilters();
        initialize();
    });
    /******** function for left collapseable links  ****************/
    $(".block__body-js").show();
    $(".block__head-js").on("click", function () {
        $(this).toggleClass("is-active");
    });

    $(".block__head-js").on("click", function () {
        $(this).siblings(".block__body-js").slideToggle("slow");
    });

    var ww = document.body.clientWidth;
    if (ww <= 1050) {
        $(".block__body-js").hide();
        $(".block__body-js:first").show();
    } else {
        $(".block__body-js").show();
    }

    $(document).on("mouseover", ".bfilter-js li", function () {
        $(".brandList-js").addClass("filter-directory_disabled");
        $(".filter-directory_list_title").addClass("filter-directory_disabled");
        $(".b-" + $(this).attr("data-item").toLowerCase()).removeClass(
            "filter-directory_disabled"
        );
        lbl = $(this).attr("data-item");
        $(".filter-directory_list_title").each(function () {
            txt = $(this).attr("data-item").trim().toUpperCase();
            if (txt == lbl.toUpperCase()) {
                $(this).removeClass("filter-directory_disabled");
            }
        });
    });

    $(document).on("mouseout", ".bfilter-js li", function () {
        $(".brandList-js").removeClass("filter-directory_disabled");
        $(".filter-directory_list_title").removeClass(
            "filter-directory_disabled"
        );
    });

    $(document).on("click", ".bfilter-js li", function (e) {
        e.preventDefault();
        $(".filter-directory_list").animate(
            {
                scrollLeft: $(
                    "#" + $(this).attr("data-item").toLowerCase()
                ).position().left,
            },
            1000
        );
    });

    if ($(window).width() < 1050) {
        if ($(".grids")[0]) {
            $(".grids").masonry({
                itemSelector: ".grids__item",
            });
        }
    }
});

$(document).on("mouseover mouseout", ".productsListItemsJs", function (e) {
    let shopId = $(this).data("shopid");
    $.each(mapMarker, function (index, marker) {
        if (typeof marker != "undefined") {
            let iconImage = "/images/pin.png";
            if (marker["refId"] == shopId && e.type == "mouseover") {
                iconImage = "/images/pin2.png";
            }
            marker.setIcon(iconImage);
            //google.maps.event.trigger( marker, 'click' );
        }
    });
});

toogleMapView = function () {
    let vtype = "map";
    fcom.displayProcessing();
    if ($("form[name=frmProductSearch] input[name=vtype]").val() != vtype) {
        $("form[name=frmProductSearch] input[name=vtype]").val(vtype);
    } else {
        $("form[name=frmProductSearch] input[name=vtype]").val("");
    }
    // window.location.href = getSearchQueryUrl(true);
    var data = "viewType=popup";
    fcom.ajax(getSearchQueryUrl(true), data, function (ans) {
        $.facebox(ans, "modal-fullscreen modal-map");
        fcom.displaySuccessMessage();
    });
    $("form[name=frmProductSearch] input[name=vtype]").val("");
};

/* function updateQueryStringParameter(uri, key, value) {
  var re = new RegExp("([?&])" + key + "=.*?(&|$)", "i");
  var separator = uri.indexOf('?') !== -1 ? "&" : "?";
  if (uri.match(re)) {
    return uri.replace(re, '$1' + key + "=" + value + '$2');
  }
  else {
    return uri + separator + key + "=" + value;
  }
} */

function autoKeywordSearch(keyword) {
    keyword = keyword.toUpperCase();
    var myarray = [];
    $(".filter-directory_list li").each(function () {
        txt = $(this).text().trim().toUpperCase();
        if (txt.indexOf(keyword) > -1) {
            caption = $(this).attr("data-caption");
            if (typeof caption !== "undefined") {
                myarray.push(caption.toUpperCase());
            }
            $(this).show();
        } else {
            $(this).hide();
        }
    });
    myarray = $.unique(myarray);
    $(".filter-directory_list_title").each(function () {
        txt = $(this).attr("data-item").trim().toUpperCase();
        if ($.inArray(txt, myarray) >= 0) {
            $(this).show();
        } else {
            $(this).hide();
        }
    });
}

function brandFilters() {
    var frm = document.frmProductSearch;
    var url = window.location.href;
    if ($currentPageUrl == removeLastSpace(url) + "/index") {
        url = fcom.makeUrl("Products", "brandFilters");
    } else {
        url = url.replace(
            $currentPageUrl,
            fcom.makeUrl("Products", "brandFilters")
        );
    }
    if (url.indexOf("products/brandFilters") == -1) {
        url = fcom.makeUrl("Products", "brandFilters");
    }

    var data = fcom.frmData(frm);
    var brands = getSelectedBrands();
    if (brands.length) {
        data = data + "&brand=" + [brands];
    }

    $("body").removeClass("collection-sidebar--on");
    fcom.ajax(url, data, function (ans) {
        $.facebox(ans, "modal-xl");
    });
}
function cbrandFilters() {
    var frm = document.frmProductSearch;
    var url = window.location.href;
    if ($currentPageUrl == removeLastSpace(url) + "/index") {
        url = fcom.makeUrl("Products", "cbrandFilters");
    } else {
        url = url.replace(
            $currentPageUrl,
            fcom.makeUrl("Products", "cbrandFilters")
        );
    }
    if (url.indexOf("products/cbrandFilters") == -1) {
        url = fcom.makeUrl("Products", "cbrandFilters");
    }

    var data = fcom.frmData(frm);
    var cbrands = getSelectedcBrands();
    if (cbrands.length) {
        data = data + "&cbrand=" + [cbrands];
    }
    $("body").removeClass("collection-sidebar--on");
    fcom.ajax(url, data, function (ans) {
        $.facebox(ans, "modal-xl");
    });
}

function modelFilters() {
    var frm = document.frmProductSearch;
    var url = window.location.href;
    if ($currentPageUrl == removeLastSpace(url) + "/index") {
        url = fcom.makeUrl("Products", "modelFilters");
    } else {
        url = url.replace(
            $currentPageUrl,
            fcom.makeUrl("Products", "modelFilters")
        );
    }
    if (url.indexOf("products/modelFilters") == -1) {
        url = fcom.makeUrl("Products", "modelFilters");
    }

    var data = fcom.frmData(frm);
    var model = getSelectedModel();
    if (model.length) {
        data = data + "&model=" + [model];
    }
    $("body").removeClass("collection-sidebar--on");
    fcom.ajax(url, data, function (ans) {
        $.facebox(ans, "modal-xl");
    });
}

function htmlEncode(value) {
    return $("<div/>").text(value).html();
}

function addFilter(id, obj) { console.log(id);
    if (typeof id === "undefined") {
        return;
    }
    removePaginationFromLink();
    var click = "onclick=removeFilter('" + id + "')";
    $filter = $(obj).parent().text();console.log($filter);
    $filterVal = htmlEncode($(obj).parent().text());console.log($filterVal);
    if (!$(".selectedFiltersJs").find("a").hasClass(id)) {
        $(".selectedFiltersJs").prepend(
            "<span class='chip'>" +
            $filterVal +
            " <a href='javascript:void(0);' data-yk='" +
            id +
            "' class='remove btn-close text-reset " +
            id +
            "' " +
            click +
            "></a></span>"
        );
    }
    showSelectedFilters();
}

function resetListingFilter() {
    searchArr = [];
    /* $("input:checkbox[name=brands]").each(function(){
        $(this).prop( "checked", false );	
    }); */
    var shop_id = parseInt($("input[name=shop_id]").val());
    if (shop_id > 0) {
        $("#filterSearchForm").get(0).reset();
        $("input[id=keyword]").val("");
    }
    document.frmProductSearch.reset();
    document.frmProductSearchPaging.reset();
    var frm = document.frmProductSearch;
    $(".selectedFiltersJs a").each(function () {
        id = $(this).attr("data-yk");
        clearFilters(id, this);
    });

    updatePriceFilter();
    reloadProductListing(frm);
    showSelectedFilters();
}

function addPaginationInlink(page) {
    searchArr["page"] = page;
}

function validatePriceFilter() {
    var max = parseInt($("input[name=priceFilterMaxValue]").val());
    var min = parseInt($("input[name=priceFilterMinValue]").val());
    if (max <= min) {
        $("input[name=priceFilterMaxValue]").val(min + 1);
    }
}

function removePaginationFromLink() {
    if (typeof searchArr["page"] == "undefined") {
        return;
    }
    delete searchArr["page"];
    var frm = document.frmProductSearchPaging;
    $(frm.page).val(1);
}

function removePageSideFromLink() {
    if (typeof searchArr["pagesize"] == "undefined") {
        return;
    }
    delete searchArr["pagesize"];
}

function showSelectedFilters() {
    if ($(".selectedFiltersJs a").length > 0) {
        $(".resetFilterSectionJs, #resetAllJs,#mapFilterJs").css(
            "display",
            "block"
        );
    } else {
        $(".resetFilterSectionJs, #resetAllJs,#mapFilterJs").css(
            "display",
            "none"
        );
    }
}

function removeFilter(id, reload) {
    $("." + id)
        .parent()
        .remove();
    $("#" + id)
        .find("input[type='checkbox']")
        .prop("checked", false);
    $("input:checkbox[name=brands]").each(function () {
        if ($(this).attr("data-id") == id) {
            $(this).prop("checked", false);
        }
    });
    $("input:checkbox[name=cbrands]").each(function () {
        if ($(this).attr("data-id") == id) {
            $(this).prop("checked", false);
        }
    });
    $("input:checkbox[name=model]").each(function () {
        if ($(this).attr("data-id") == id) {
            $(this).prop("checked", false);
        } 
    });
    var frm = document.frmProductSearch;
    /* form submit upon onchange of form elements select box[ */
    removeFromSearchQueryString(id);
    if (typeof reload == "undefined" || reload == true) {
        reloadProductListing(frm);
    }
    showSelectedFilters();
}

function clearFilters(id, obj) {
    $("." + id)
        .parent("span")
        .remove();
    $("#" + id)
        .find("input[type='checkbox']")
        .prop("checked", false);
    $('[data-id="' + id + '"]').prop("checked", false);
    $("input:checkbox[name=brands]").each(function () {
        if ($(this).attr("data-id") == id) {
            $(this).prop("checked", false);
        }
    });
}

function addToSearchQueryString(id, obj) {
    if (typeof id === "undefined") {
        return;
    }

    var attrVal = $(obj).attr("data-title");
    if (typeof attrVal !== "undefined" && attrVal !== false) {
        $filterVal = htmlEncode(removeSpecialCharacter(attrVal));
    } else {
        $filterVal = htmlEncode(removeSpecialCharacter($(obj).parent().text()));
    }
    $filterVal = $filterVal.trim().toLowerCase();
    /* searchArr[id] = encodeURIComponent($filterVal.replace(/ /g,'-'));	 */
    searchArr[id] = $filterVal.replace(/ /g, "-");
}

function removeSpecialCharacter($str) {
    return $str.replace(/[&\/\\#,+()$~%.'":*?<>{}]/g, "");
}

function removeFromSearchQueryString(key) {
    delete searchArr[key];
}

function setQueryParamSeperator(url) {
    if (url.indexOf("?") > -1) {
        return "&";
    }
    return "?";
}

function getSearchQueryUrl(includeBaseUrl) {
    url = "";
    itemSeperator = "&";
    valueSeperator = "-";

    if (typeof includeBaseUrl != "undefined" || includeBaseUrl != null) {
        url = $currentPageUrl;
    }
    var keyword = $("input[id=keyword]").val();
    if (keyword != "") {
        delete searchArr["keyword"];
        /* keyword = encodeURIComponent(keyword);		 */
        url =
            url +
            setQueryParamSeperator(url) +
            "keyword" +
            valueSeperator +
            keyword /* .replace(/_/g, '-') */;
    }

    var category = parseInt($("input[id=searched_category]").val());
    if (category > 0) {
        delete searchArr["category"];
        url =
            url +
            setQueryParamSeperator(url) +
            "category" +
            valueSeperator +
            category;
    }
    for (var key in searchArr) {
        url =
            url +
            setQueryParamSeperator(url) +
            key.replace(/_/g, "-") +
            valueSeperator +
            searchArr[key];
    }

    /* var currency = parseInt($("input[name=currency_id]").val());
    if(currency > 0){
        delete searchArr['currency'];
        url = url +setQueryParamSeperator(url)+'currency'+valueSeperator+currency;
    } */

    var featured = parseInt($("input[name=featured]").val());
    if (featured > 0) {
        url =
            url +
            setQueryParamSeperator(url) +
            "featured" +
            valueSeperator +
            featured;
    }

    var collection_id = parseInt($("input[name=collection_id]").val());
    if (collection_id > 0) {
        url =
            url +
            setQueryParamSeperator(url) +
            "collection" +
            valueSeperator +
            collection_id;
    }

    var shop_id = parseInt($("input[name=shop_id]").val());
    if (shop_id > 0) {
        url =
            url +
            setQueryParamSeperator(url) +
            "shop" +
            valueSeperator +
            shop_id;
    }

    var vtype = $("form[name=frmProductSearch] input[name=vtype]").val();
    // url = url + setQueryParamSeperator(url) + 'vtype' + valueSeperator + vtype;

    var pageRecordCount = $(
        "form[name=frmProductSearch] input[name=pageRecordCount]"
    ).val();
    url =
        url +
        setQueryParamSeperator(url) +
        "pagerecordcount" +
        valueSeperator +
        pageRecordCount;

    /* var page = parseInt($("input[name=page]").val());
    if(page > 1){
        url = url +setQueryParamSeperator(url)+'page-'+page;
    } */

    var e = document.getElementById("sortBy");
    if ($(e).is("select")) {
        var sortBy = e.options[e.selectedIndex].value;
    } else {
        var sortBy = e.value;
    }

    if (sortBy) {
        url =
            url +
            setQueryParamSeperator(url) +
            "sort" +
            valueSeperator +
            sortBy.replace(/_/g, "-");
    }

    var e = document.getElementById("pageSize");
    var pageSize = parseInt(e.value);
    if (pageSize > 0) {
        url =
            url +
            setQueryParamSeperator(url) +
            "pagesize" +
            valueSeperator +
            pageSize;
    }

    return encodeURI(url);
}

function addPricefilter(reloadPage) {
    if (typeof reloadPage == "undefined") {
        reloadPage = false;
    }
    $(".price").parent().remove();
    if (!$(".selectedFiltersJs").find("a").hasClass("price")) {
        $(".selectedFiltersJs").prepend(
            "<span class='chip'> " +
            currencySymbolLeft +
            $("input[name=priceFilterMinValue]").val() +
            currencySymbolRight +
            " - " +
            currencySymbolLeft +
            $("input[name=priceFilterMaxValue]").val() +
            currencySymbolRight +
            " <a href='javascript:void(0);' data-yk='price' class='remove price btn-close text-reset' onclick='removePriceFilter(this)'> </a></span>"
        );
    }
    searchArr["price_min_range"] = $("input[name=priceFilterMinValue]").val();
    searchArr["price_max_range"] = $("input[name=priceFilterMaxValue]").val();
    searchArr["currency"] = langLbl.siteCurrencyId;
    var frm = document.frmProductSearch;
    if (reloadPage) {
        reloadProductListing(frm);
    }
    showSelectedFilters();
}

function removePriceFilter(reloadPage) {
    if (typeof reloadPage == "undefined") {
        reloadPage = true;
    }
    updatePriceFilter();
    var frm = document.frmProductSearch;
    delete searchArr["price_min_range"];
    delete searchArr["price_max_range"];
    delete searchArr["currency"];
    if (reloadPage) {
        reloadProductListing(frm);
    }

    $(".price").parent().remove();
    showSelectedFilters();
}

function updatePriceFilter(minPrice, maxPrice, addPriceFilter) {
    if (typeof addPriceFilter == "undefined") {
        addPriceFilter = false;
    }

    if (typeof minPrice == "undefined" || typeof maxPrice == "undefined") {
        minPrice = parseFloat($("#priceFilterMinValue").data("defaultvalue"));
        maxPrice = parseFloat($("#priceFilterMaxValue").data("defaultvalue"));
    }

    $('input[name="priceFilterMinValue"]').val(minPrice).trigger("change");
    $('input[name="priceFilterMaxValue"]').val(maxPrice).trigger("change");

    if (addPriceFilter) {
        addPricefilter();
    }
}

(function () {
    updateRange = function (from, to) {
        if (typeof range !== "undefined") {
            range.update({
                from: from,
                to: to,
            });
        }
    };

    bannerAdds = function (url) {
        fcom.ajax(url, "", function (res) {
            $("#searchPageBanners").html(res);
        });
    };

    reloadProductListing = function (frm, page) {
        if (typeof page == "undefined") {
            page = 0;
        }

        $("#productsList").html(fcom.getLoader());
        if (0 < page) {
            addPaginationInlink(page);
        } else {
            getSetSelectedOptionsUrl(frm);
        }
        var data = fcom.frmData(frm);
        var currUrl = getSearchQueryUrl(true);

        fcom.ajax(currUrl, data, function (res) {
            fcom.removeLoader();
            $("#productsList").replaceWith(res);
            var frm = document.frmProductSearchPaging;
            var recordCount = parseInt($(frm.recordDisplayCount).val());
            $("form[name=frmProductSearch] input[name=pageRecordCount]").val(
                $(document.frmProductSearchPaging.pageRecordCount).val()
            );
            $("#total_records").html(recordCount);
            if (1 > recordCount) {
                $(".saveSearch-js").hide();
            } else {
                $(".saveSearch-js").show();
            }
        });
        window.history.pushState("", "", currUrl);
        /* window.location.href = getSearchQueryUrl(true); */
    };

    reloadProductListingMap = function (frm, page) {
        if (typeof page == "undefined") {
            page = 0;
        }

        $("#productsListMap").html(fcom.getLoader());
        if (0 < page) {
            addPaginationInlink(page);
        } else {
            getSetSelectedOptionsUrl(frm);
        }
        var data = fcom.frmData(frm);
        var currUrl = getSearchQueryUrl(true);

        fcom.ajax(currUrl, data, function (res) {
            fcom.removeLoader();
            $("#productsListMap").replaceWith(res);
            var frm = document.frmProductSearchPaging;
            var recordCount = parseInt($(frm.recordDisplayCount).val());
            $("form[name=frmProductSearch] input[name=pageRecordCount]").val(
                $(document.frmProductSearchPaging.pageRecordCount).val()
            );
            $("#total_records").html(recordCount);
            if (1 > recordCount) {
                $(".saveSearch-js").hide();
            } else {
                $(".saveSearch-js").show();
            }
        });
        window.history.pushState("", "", currUrl);
        /* window.location.href = getSearchQueryUrl(true); */
    };

    searchProducts = function (frm) {
        var keyword = $.trim($(frm.keyword).val());
        if (3 > keyword.length || "" === keyword) {
            fcom.displayErrorMessage(langLbl.searchString);
            return;
        }
        $("input[id=keyword]").val(keyword);
        removePageSideFromLink();
        removePaginationFromLink();
        reloadProductListing(frm);
        $("#resetAllJs").show();
    };

    loadProductListingCatfilters = function (frm) {
        $(".productFiltersJs").html(fcom.getLoader());
        var url = window.location.href;
        if ($currentPageUrl == removeLastSpace(url) + "/index") {
            url = fcom.makeUrl("Products", "catFilter");
        } else {
            url = url.replace(
                $currentPageUrl,
                fcom.makeUrl("Products", "catFilter")
            );
        }

        if (url.indexOf("products/catFilter") == -1) {
            url = fcom.makeUrl("Products", "catFilter");
        }

        url = url.replace(/\/?$/, "/");

        var data = fcom.frmData(frm);
        fcom.ajax(url, data, function (res) {
            $(".productFiltersJs").html(res);
            fcom.removeLoader();
        });
    };

    loadProductListingfilters = function (frm) {
        loadProductListingCatfilters(frm);
        var url = window.location.href;
        if ($currentPageUrl == removeLastSpace(url) + "/index") {
            url = fcom.makeUrl("Products", "filters");
        } else {
            url = url.replace(
                $currentPageUrl,
                fcom.makeUrl("Products", "filters")
            );
        }

        if (url.indexOf("products/filters") == -1) {
            url = fcom.makeUrl("Products", "filters");
        }
        url = url.replace(/\/?$/, "/");
        var data = fcom.frmData(frm);
        fcom.ajax(url, data, function (res) {
            $(".otherFiltersJs").html(res);
            getSetSelectedOptionsUrl(frm);
            fcom.removeLoader();
        });
    };

    removeLastSpace = function (str) {
        return str.replace(/\/*$/, "");
    };

    getSelectedBrands = function () {
        var brands = [];
        $("input:checkbox[name=brands]:checked").each(function () {
            var id = $(this).attr("data-id");
            addToSearchQueryString(id, this);
            addFilter(id, this);
            brands.push($(this).val());
        });
        return brands;
    };

    getSelectedcBrands = function () {
        var cbrands = [];
        $("input:checkbox[name=cbrands]:checked").each(function () {
            var id = $(this).attr("data-id");
            addToSearchQueryString(id, this);
            addFilter(id, this);
            cbrands.push($(this).val()); 
        });
        return cbrands;
    };

    getSelectedModel = function () {
        var models = [];
        $("input:checkbox[name=model]:checked").each(function () {
            var id = $(this).attr("data-id");
            addToSearchQueryString(id, this);
            addFilter(id, this);
            models.push($(this).val()); 
        });
        return models;
    };

    getSetSelectedOptionsUrl = function (frm) {
        var data = fcom.frmData(frm);

        /* Category filter value pickup[ */
        var category = [];
        $("input:checkbox[name=category]:checked").each(function () {
            var id = $(this).parent().parent().find("label").attr("id");
            addToSearchQueryString(id, this);
            addFilter(id, this);
            category.push($(this).val());
        });
        if (category.length) {
            data = data + "&category=" + [category];
        }
        /* ] */

        /* brands filter value pickup[ */
        var brands = getSelectedBrands();
        if (brands.length) {
            data = data + "&brand=" + [brands];
        }
        /* ] */

        /* compatible brands filter value pickup[ */
        var cbrands = getSelectedcBrands();
        if (cbrands.length) {
            data = data + "&cbrand=" + [cbrands];
        }
        /* ] */

        /* compatible brands filter value pickup[ */
        var models = getSelectedModel();
        if (models.length) {
            data = data + "&model=" + [models];
        }
        /* ] */        

        /* Option filter value pickup[ */
        var optionvalues = [];
        $("input:checkbox[name=optionvalues]:checked").each(function () {
            var id = $(this).parent().parent().find("label").attr("id");
            addToSearchQueryString(id, this);
            addFilter(id, this);
            optionvalues.push($(this).val());
        });
        if (optionvalues.length) {
            data = data + "&optionvalue=" + [optionvalues];
        }
        /* ] */

        /* condition filters value pickup[ */
        var conditions = [];
        $("input:checkbox[name=conditions]:checked").each(function () {
            var id = $(this).parent().parent().find("label").attr("id");
            addToSearchQueryString(id, this);
            addFilter(id, this);
            conditions.push($(this).val());
        });
        if (conditions.length) {
            data = data + "&condition=" + [conditions];
        }
        /* ] */

        /* Free Shipping Filter value pickup[ */

        /* ] */

        /* Out Of Stock Filter value pickup[ */
        $("input:checkbox[name=out_of_stock]:checked").each(function () {
            var id = $(this).parent().parent().find("label").attr("id");
            addToSearchQueryString(id, this);
            addFilter(id, this);
            data = data + "&out_of_stock=1";
        });
        /* ] */

        /* price filter value pickup[ */
        if (typeof $("input[name=priceFilterMinValue]").val() != "undefined") {
            data =
                data +
                "&min_price_range=" +
                $("input[name=priceFilterMinValue]").val();
        }

        if (typeof $("input[name=priceFilterMaxValue]").val() != "undefined") {
            data =
                data +
                "&max_price_range=" +
                $("input[name=priceFilterMaxValue]").val();
        }

        var defaultMinPrice = parseInt(
            $("input[name=priceFilterMinValue]").data("defaultvalue")
        );
        var minPrice = parseInt($("input[name=priceFilterMinValue]").val());
        var defaultMaxPrice = parseInt(
            $("input[name=priceFilterMaxValue]").data("defaultvalue")
        );
        var maxPrice = parseInt($("input[name=priceFilterMaxValue]").val());
        if (
            !isNaN(defaultMinPrice) &&
            !isNaN(defaultMaxPrice) &&
            (minPrice != defaultMinPrice || maxPrice != defaultMaxPrice)
        ) {
            addPricefilter(false);
        }

        return data;
    };

    goToProductListingSearchPage = function (page) {
        if (typeof page == undefined || page == null) {
            page = 1;
        }
        $("form[name=frmProductSearch] input[name=viewType]").val("");
        $("#pageSize").val($("#pageSizeSelect").val());
        reloadProductListing(document.frmProductSearch, page);
        $("html, body").animate(
            { scrollTop: $("#productsList").offset().top },
            "slow"
        );

        /*
        removePaginationFromLink(page);
        var frm = document.frmProductSearchPaging;
        $(frm.page).val(page);
        $("form[name='frmProductSearchPaging']").remove();
        getSetSelectedOptionsUrl(frm);
        var url = getSearchQueryUrl(true);
        window.location.href = url + setQueryParamSeperator(url) + 'page-' + page;
        /* $('html, body').animate({ scrollTop: 0 }, 'slow'); */
    };

    saveProductSearch = function () {
        if (isUserLogged() == 0) {
            loginPopUpBox();
            return false;
        }
        $.facebox(function () {
            fcom.ajax(
                fcom.makeUrl(
                    "SavedProductsSearch",
                    "form",
                    [],
                    siteConstants.webroot_dashboard
                ),
                "",
                function (ans) {
                    $.facebox(ans);
                    if (ans.status) {
                        $.facebox.close();
                    }
                }
            );
        });
        return false;
    };

    goToProductListingSearchPageMap = function (page) {
        if (typeof page == undefined || page == null) {
            page = 1;
        }
        $("form[name=frmProductSearch] input[name=viewType]").val(
            "popupProduct"
        );
        reloadProductListingMap(document.frmProductSearch, page);
        $("html, body").animate(
            { scrollTop: $("#productsListMap").offset().top },
            "slow"
        );
    };

    saveProductSearch = function () {
        if (isUserLogged() == 0) {
            loginPopUpBox();
            return false;
        }
        $.facebox(function () {
            fcom.ajax(
                fcom.makeUrl(
                    "SavedProductsSearch",
                    "form",
                    [],
                    siteConstants.webroot_dashboard
                ),
                "",
                function (ans) {
                    $.facebox(ans);
                    if (ans.status) {
                        $.facebox.close();
                    }
                }
            );
        });
        return false;
    };

    setupSaveProductSearch = function (frm) {
        if (!$(frm).validate()) return false;
        var data = fcom.frmData(frm);
        data = data + "&pssearch_type=" + $productSearchPageType;
        data = data + "&pssearch_record_id=" + $recordId;
        data = data + "&curr_page=" + $currentPageUrl;
        fcom.updateWithAjax(
            fcom.makeUrl(
                "SavedProductsSearch",
                "setup",
                [],
                siteConstants.webroot_dashboard
            ),
            data,
            function (ans) {
                fcom.closeProcessing();
                fcom.removeLoader();
                if (ans.status) {
                    $.facebox.close();
                }
            }
        );
    };

    resendOtp = function (userId, getOtpOnly = 0) {
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
                fcom.displaySuccessMessage(t.msg);
                startOtpInterval();
            }
        );
        return false;
    };

    validateOtp = function (frm) {
        if (!$(frm).validate()) return;
        var data = fcom.frmData(frm);
        fcom.ajax(fcom.makeUrl("GuestUser", "validateOtp"), data, function (t) {
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
    dragCallback = function (dragendMap) {
        canSetCookie = true;
        codeLatLng(
            dragendMap.getCenter().lat(),
            dragendMap.getCenter().lng(),
            function (data) {
                displayGeoAddress(setGeoAddress(data));
                if (typeof dragTimeOutEvent != "undefined") {
                    clearTimeout(dragTimeOutEvent);
                }
                dragTimeOutEvent = setTimeout(function () {
                    $("form[name=frmProductSearch] input[name=viewType]").val(
                        "popupProduct"
                    );
                    reloadProductListingMap(document.frmProductSearch);
                    // loadProductListingfilters(document.frmProductSearch);
                }, 1200);
            }
        );
    };
})();
