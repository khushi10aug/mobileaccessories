var isAjaxRunning = false;
var cart = {
    add: function (selprod_id, quantity, isRedirectToCart) {
        isRedirectToCart = (typeof (isRedirectToCart) != 'undefined') ? true : false;
        var data = 'selprod_id=' + selprod_id + '&quantity=' + (typeof (quantity) != 'undefined' ? quantity : 1);
        if (0 < $(".list-addons--js").length && typeof mainSelprodId != 'undefined' && mainSelprodId == selprod_id) {
            $(".list-addons--js").find("input").each(function (e) {
                if (($(this).val() > 0) && (!$(this).closest(".addon--js").hasClass("cancelled--js"))) {
                    data = data + '&' + $(this).attr('data-lang') + "=" + $(this).val();
                }
            });
        }

        fcom.updateWithAjax(fcom.makeUrl('Cart', 'add'), data, function (ans) {
            fcom.closeProcessing();
            fcom.removeLoader();
            if (ans['redirect']) {
                location = ans['redirect'];
            }
            fcom.displaySuccessMessage(ans.msg);

            productData = [];
            var totalCartItemsPrice = 0;
            $.each(ans.cartItems, function (key, val) {
                totalCartItemsPrice += val.theprice;
                productData.push({
                    item_id: val.selprod_id,
                    item_name: val.selprod_title,
                    discount: (val.selprod_price - val.theprice),
                    index: key,
                    item_brand: val.brand_name,
                    item_category: val.prodcat_name,
                    price: val.theprice,
                    quantity: val.addedQty
                })
            });

            ykevents.addToCart({
                currency: currencyCode,
                value: totalCartItemsPrice,
                items: productData
            });

            /* isRedirectToCart needed from product detail page */
            if (isRedirectToCart) {
                setTimeout(function () {
                    window.location = fcom.makeUrl('Checkout');
                }, 300);
            } else {
                $('span.cartQuantity').html(ans.total);
                cart.loadCartSummary();
            }

        });
    },

    remove: function (key, page, saveForLater) {
        if (confirm(langLbl.confirmRemove)) {
            var data = 'key=' + key + '&saveForLater=' + saveForLater;
            fcom.updateWithAjax(fcom.makeUrl('Cart', 'remove'), data, function (ans) {
                fcom.removeLoader();
                removedItems = [];
                var totalCartItemsPrice = 0;
                $.each(ans.cartItems, function (key, val) {
                    totalCartItemsPrice += val.theprice;
                    removedItems.push({
                        item_id: val.selprod_id,
                        item_name: val.selprod_title,
                        discount: (val.selprod_price - val.theprice),
                        index: key,
                        item_brand: val.brand_name,
                        item_category: val.prodcat_name,
                        price: val.theprice,
                        quantity: val.addedQty
                    })
                });

                ykevents.removeFromCart({
                    currency: currencyCode,
                    value: totalCartItemsPrice,
                    items: removedItems
                });

                if (page == 'checkout') {
                    if (ans.status) {
                        loadFinancialSummary();
                        resetCheckoutDiv();
                    }
                    if (ans.total == 0) {
                        window.location = fcom.makeUrl('Cart');
                    }
                }
                else if (page == 'cart') {
                    if (ans.status) {
                        listCartProducts();
                        cart.loadCartSummary();
                    }
                    if (ans.total == 0) {
                        $('.emtyCartBtn-js').hide();
                    }
                }
                else {
                    cart.loadCartSummary();
                }
                $.ykmsg.close();
            });
        }
    },

    update: function (key, loadDiv, fulfilmentType = 0) {
        if (true === isAjaxRunning) {
            return false;
        }
        isAjaxRunning = true;
        var data = 'key=' + key + '&quantity=' + $("input[name='qty_" + key + "']").val();
        fcom.ajax(fcom.makeUrl('Cart', 'update'), data, function (ans) {
            fcom.removeLoader();
            if (!ans.status) {
                fcom.displayErrorMessage(ans.msg);
                if (typeof (cart.addCallBackFn) == 'function') {
                    cart.addCallBackFn(ans);
                }
                return;
            }


            isAjaxRunning = false;
            if (ans.status) {
                if (loadDiv != undefined) {
                    $(financialSummary).prepend(fcom.getLoader(true));
                    loadFinancialSummary();
                    if (1 > $("#hasAddress").length || ($("#hasAddress").length > 0 && 0 < $("#hasAddress").val())) {
                        resetCheckoutDiv();
                    }
                } else if (0 < fulfilmentType) {
                    listCartProducts(fulfilmentType);
                } else {
                    listCartProducts();
                }
            }

        }, { fOutMode: 'json' });
    },

    updateGroup: function (prodgroup_id) {
        $.ykmsg.close();
        var data = 'prodgroup_id=' + prodgroup_id + '&quantity=' + $("input[name='qty_" + prodgroup_id + "']").val();;
        fcom.updateWithAjax(fcom.makeUrl('Cart', 'updateGroup'), data, function (ans) {
            fcom.removeLoader();
            if (ans.status) {
                listCartProducts();
            }
        });
    },

    addGroup: function (prodgroup_id, isRedirectToCart) {
        isRedirectToCart = (typeof (isRedirectToCart) != 'undefined') ? true : false;
        var data = 'prodgroup_id=' + prodgroup_id;
        fcom.updateWithAjax(fcom.makeUrl('Cart', 'addGroup'), data, function (ans) {
            fcom.removeLoader();
            setTimeout(function () {
                fcom.closeProcessing();
            }, 3000);

            $(".cart-item-counts-js").html(ans.total);
            if (isRedirectToCart) {
                setTimeout(function () {
                    window.location = fcom.makeUrl('Cart');
                }, 300);
            }
        });
    },

    removeGroup: function (prodgroup_id) {
        if (confirm(langLbl.confirmRemove)) {
            var data = 'prodgroup_id=' + prodgroup_id;
            fcom.updateWithAjax(fcom.makeUrl('Cart', 'removeGroup'), data, function (ans) {
                fcom.removeLoader();
                if (ans.status) {
                    listCartProducts();
                }
                $.ykmsg.close();
            });
        }
    },

    clear: function () {
        if (confirm(langLbl.confirmRemove)) {
            fcom.updateWithAjax(fcom.makeUrl('Cart', 'clear'), '', function (ans) {
                fcom.removeLoader();
                if (ans.status) {
                    removedItems = [];
                    var totalCartItemsPrice = 0;
                    $.each(ans.cartItems, function (key, val) {
                        totalCartItemsPrice += val.theprice;
                        removedItems.push({
                            item_id: val.selprod_id,
                            item_name: val.selprod_title,
                            discount: (val.selprod_price - val.theprice),
                            index: key,
                            item_brand: val.brand_name,
                            item_category: val.prodcat_name,
                            price: val.theprice,
                            quantity: val.addedQty
                        })
                    });

                    ykevents.removeFromCart({
                        currency: currencyCode,
                        value: totalCartItemsPrice,
                        items: removedItems
                    });

                    if (typeof listCartProducts === "function") {
                        listCartProducts();
                    }
                    $('span.cartQuantity').html(ans.total);
                    cart.loadCartSummary();
                    $('body').removeClass('side-cart--on');
                }
                $.ykmsg.close();
            });
        }
    },

    loadCartSummary: function (show = true) {
        var isOffcanvas = (0 < $("#sideCartJs.offcanvas").length);
        if (true === show && isOffcanvas) {
            $("#sideCartJs").prepend(fcom.getLoader()).offcanvas('hide');
        }

        fcom.updateWithAjax(fcom.makeUrl('Cart', 'getCartSummary'), '', function (ans) {
            if (true === show && isOffcanvas) {
                fcom.removeLoader();
                $('#cartSummaryJs').html(ans.buttonHtml);
                $('#sideCartJs').replaceWith(ans.offCanvasHtml);
                $("#sideCartJs").offcanvas('show');
            }
        });
    },
    addCallBackFn: null,
};

var ykevents = {
    /* 1: For FB, 2: For GA4 (gtag). Also pushes to GTM dataLayer when useGtmDataLayer is true (admin GTM scripts saved). */
    _gtmOn: function () {
        return typeof useGtmDataLayer !== 'undefined' && useGtmDataLayer === true;
    },
    _ensureDataLayer: function () {
        window.dataLayer = window.dataLayer || [];
    },
    _ga4ItemsFromPayload: function (data) {
        if (!data || typeof data !== 'object') {
            return [];
        }
        if (Array.isArray(data.items) && data.items.length) {
            return data.items.map(function (it, i) {
                var row = {
                    item_id: String(it.item_id != null ? it.item_id : ''),
                    item_name: it.item_name || '',
                    item_brand: it.item_brand || '',
                    item_category: it.item_category || '',
                    price: parseFloat(it.price) || 0,
                    quantity: parseInt(it.quantity, 10) || 1,
                    index: typeof it.index !== 'undefined' ? it.index : i
                };
                if (it.discount != null && it.discount !== '') {
                    row.discount = parseFloat(it.discount) || 0;
                }
                return row;
            });
        }
        if (data.item_id != null && data.item_id !== '') {
            return [{
                item_id: String(data.item_id),
                item_name: data.item_name || '',
                item_brand: data.item_brand || '',
                item_category: data.item_category || '',
                price: parseFloat(data.price) || 0,
                quantity: parseInt(data.quantity, 10) || 1,
                index: 0
            }];
        }
        return [];
    },
    _pushGtmEcommerce: function (eventName, data) {
        if (!ykevents._gtmOn()) {
            return;
        }
        ykevents._ensureDataLayer();
        var items = ykevents._ga4ItemsFromPayload(data || {});
        var currency = (data && data.currency) ? data.currency : (typeof currencyCode !== 'undefined' ? currencyCode : '');
        var value = (data && typeof data.value !== 'undefined') ? parseFloat(data.value) : 0;
        window.dataLayer.push({ ecommerce: null });
        var ecommerce = { currency: currency, value: isNaN(value) ? 0 : value };
        if (items.length) {
            ecommerce.items = items;
        }
        if (data && data.transaction_id) {
            ecommerce.transaction_id = String(data.transaction_id);
        }
        if (data && data.tax != null) {
            ecommerce.tax = parseFloat(data.tax) || 0;
        }
        if (data && data.shipping != null) {
            ecommerce.shipping = parseFloat(data.shipping) || 0;
        }
        if (data && data.coupon) {
            ecommerce.coupon = String(data.coupon);
        }
        window.dataLayer.push({ event: eventName, ecommerce: ecommerce });
    },
    _pushGtmEvent: function (eventName, payload) {
        if (!ykevents._gtmOn()) {
            return;
        }
        ykevents._ensureDataLayer();
        var o = { event: eventName };
        if (payload && typeof payload === 'object') {
            for (var k in payload) {
                if (Object.prototype.hasOwnProperty.call(payload, k)) {
                    o[k] = payload[k];
                }
            }
        }
        window.dataLayer.push(o);
    },
    _validateAndTrigger: function (requestTo, event, data) {
        if (1 == requestTo && 'undefined' !== typeof fbPixel && true == fbPixel) {
            fbq('track', event, data);
        }
        if (2 == requestTo && 'undefined' !== typeof gtag && '' != data) {
            gtag("event", event, data);
        }
    },

    viewItem: function (data) {
        ykevents._validateAndTrigger(2, 'view_item', data);
        ykevents._pushGtmEcommerce('view_item', data);
    },

    addToCart: function (data) {
        ykevents._validateAndTrigger(1, 'AddToCart');
        ykevents._validateAndTrigger(2, 'add_to_cart', data);
        ykevents._pushGtmEcommerce('add_to_cart', data);
    },

    viewCart: function (data) {
        ykevents._validateAndTrigger(2, 'view_cart', data);
        ykevents._pushGtmEcommerce('view_cart', data);
    },

    removeFromCart: function (data) {
        ykevents._validateAndTrigger(2, 'remove_from_cart', data);
        ykevents._pushGtmEcommerce('remove_from_cart', data);
    },

    addToWishList: function () {
        ykevents._validateAndTrigger(1, 'AddToWishlist');
        ykevents._pushGtmEvent('add_to_wishlist', { yk_source: 'yokart' });
    },

    contactUs: function () {
        ykevents._validateAndTrigger(1, 'Contact');
        ykevents._pushGtmEvent('contact', { yk_source: 'yokart' });
    },

    customizeProduct: function () {
        ykevents._validateAndTrigger(1, 'CustomizeProduct');
        ykevents._pushGtmEvent('customize_product', { yk_source: 'yokart' });
    },

    initiateCheckout: function (data) {
        ykevents._validateAndTrigger(1, 'InitiateCheckout');
        ykevents._validateAndTrigger(2, 'begin_checkout', data);
        ykevents._pushGtmEcommerce('begin_checkout', data);
    },

    search: function (extra) {
        ykevents._validateAndTrigger(1, 'search');
        ykevents._pushGtmEvent('search', extra && typeof extra === 'object' ? extra : {});
    },

    purchase: function (data) {
        ykevents._validateAndTrigger(1, 'Purchase', data);
        ykevents._validateAndTrigger(2, 'purchase', data);
        ykevents._pushGtmEcommerce('purchase', data);
    },

    /*
        A visit to a web page you care about. For example, a product or landing page.
    */
    viewContent: function () {
        ykevents._validateAndTrigger(1, 'viewContent');
        ykevents._pushGtmEvent('yk_view_content', {
            yk_controller: typeof className !== 'undefined' ? className : '',
            yk_action: typeof actionName !== 'undefined' ? actionName : ''
        });
    },

    newsLetterSubscription: function () {
        ykevents._validateAndTrigger(1, 'CompleteRegistration');
        ykevents._pushGtmEvent('newsletter_subscribe', { yk_source: 'yokart' });
    },

    /** User completed signup (buyer registration success page). Maps to GA4 recommended event name in GTM. */
    signUp: function (payload) {
        ykevents._pushGtmEvent('sign_up', payload && typeof payload === 'object' ? payload : { method: 'web' });
    }
};

/*sidebar.js */
$(document).on("click", ".resetModalFormJs", function (e) {
    if ($.ykmodal.isSideBarView()) {
        $.ykmodal(fcom.getLoader());
    }

    var onClear = $(".modalFormJs").data("onclear");
    if ('undefined' != typeof onClear) {
        eval(onClear);
    } else if (0 < $("." + $.ykmodal.element + " .navTabsJs .nav-link").length) {
        $("." + $.ykmodal.element + " .navTabsJs .nav-link.active").click();
    }
});