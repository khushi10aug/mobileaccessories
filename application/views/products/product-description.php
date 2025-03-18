<?php defined('SYSTEM_INIT') or die('Invalid Usage.'); ?>

<div class="product-description">
    <?php include(CONF_THEME_PATH . 'products/product-info.php'); ?>
    <?php if (FatApp::getConfig("CONF_ALLOW_REVIEWS", FatUtility::VAR_INT, 0) && $product['prod_rating'] > 0) { ?>
        <?php $label = (round($product['prod_rating']) > 0) ? round($product['totReviews'], 1) . ' ' . Labels::getLabel('LBL_Reviews', $siteLangId) : Labels::getLabel('LBL_No_Reviews', $siteLangId); ?>
        <div class="product-ratings">
            <svg class="svg svg-star" width="14" height="14">
                <use xlink:href="<?php echo CONF_WEBROOT_URL; ?>images/retina/sprite.svg<?php echo AttachedFile::setTimeParam(RELEASE_DATE); ?>#star-yellow">
                </use>
            </svg>
            <span class="rate"><?php echo round($product['prod_rating'], 1); ?></span>
            <a href="#itemRatings" class="totals-review"><?php echo $label; ?></a>
        </div>
    <?php } ?>

    <?php if (FatApp::getConfig("CONF_PRODUCT_INCLUSIVE_TAX", FatUtility::VAR_INT, 0) && 0 == Tax::getActivatedServiceId()) { ?>
        <p class="tax-inclusive">
            <?php echo Labels::getLabel('LBL_Inclusive_All_Taxes', $siteLangId); ?>
        </p>
    <?php } ?>

    <!-- Option block -->

    <?php if (!empty($optionRows)) { ?>
        <?php $selectedOptionsArr = $product['selectedOptionValues'] ?? [];
        $count = 0;
        foreach ($optionRows as $key => $option) {
            $selectedOptionValue = [];
            $selectedOptionColor = [];
            if (array_key_exists($key, $selectedOptionsArr)) {
                $selectedOptionValue = $option['values'][$selectedOptionsArr[$key]]['optionvalue_name'] ?? [];
                $selectedOptionColor = $option['values'][$selectedOptionsArr[$key]]['optionvalue_color_code'] ?? [];
            }

            if ($option['option_is_color'] && !empty($selectedOptionColor)) {
                $selectedOptionColor = ("#" == $selectedOptionColor[0] ? $selectedOptionColor : "#" . $selectedOptionColor);
            }
        ?>
            <div class="options-block">
                <div class="options-block-head">
                    <h6 class="h6"><?php echo $option['option_name']; ?></h6>
                </div>
                <?php if ($option['values']) { ?>
                    <div class="options-block-body">
                        <ul
                            class="select-options <?php echo ($option['option_is_color']) ? 'select-options-color' : 'select-options-size'; ?>">
                            <?php foreach ($option['values'] as $opVal) {
                                $isAvailable = true;
                                if (in_array($opVal['optionvalue_id'], $product['selectedOptionValues'])) {
                                    $optionUrl = UrlHelper::generateUrl('Products', 'view', array($product['selprod_id']));
                                } else {
                                    $optionUrl = Product::generateProductOptionsUrl($product['selprod_id'], $selectedOptionsArr, $option['option_id'], $opVal['optionvalue_id'], $product['product_id']);
                                    $optionUrlArr = explode("::", $optionUrl);
                                    if (is_array($optionUrlArr) && count($optionUrlArr) == 2) {
                                        $optionUrl = $optionUrlArr[0];
                                        $isAvailable = false;
                                    }
                                }

                                $colorStyle = '';
                                if ($option['option_is_color']) {
                                    if ($opVal['optionvalue_color_code'] != '') {
                                        $color = (false === strpos($opVal['optionvalue_color_code'], '#')) ? '#' . $opVal['optionvalue_color_code'] : $opVal['optionvalue_color_code'];
                                        $colorStyle = 'style="background-color:' . $color . '"';
                                    } else {
                                        $colorStyle = 'style="background-color:' . $opVal['optionvalue_name'] . '"';
                                    }
                                }

                                $title = $opVal['optionvalue_name'];
                                if (!$isAvailable) {
                                    $title = Labels::getLabel('LBL_Not_Available', $siteLangId);
                                }
                            ?>
                                <li
                                    class="select-options-item <?php echo (in_array($opVal['optionvalue_id'], $product['selectedOptionValues'])) ? 'selected' : ''; ?>">
                                    <a class="btn-option <?php echo (!$optionUrl) ? ' is-disabled' : ''; ?>"
                                        data-optionValueId="<?php echo $opVal['optionvalue_id']; ?>"
                                        data-selectedOptionValues="<?php echo implode("_", $selectedOptionsArr); ?>"
                                        title="<?php echo $title; ?>"
                                        href="<?php echo ($optionUrl) ? $optionUrl : 'javascript:void(0)'; ?>"
                                        <?php echo $colorStyle; ?>>
                                        <?php echo ($option['option_is_color']) ? '' : $opVal['optionvalue_name'];  ?>
                                    </a>
                                </li>
                            <?php } ?>
                        </ul>
                    </div>
                <?php } ?>
            </div>
        <?php $count++;
        } ?>
    <?php } ?>
    <!-- Add To Cart -->
    <?php
    $acceptedOfferId = 0;
    if (RequestForQuote::isEnabled($product['shop_rfq_enabled'], $product['selprod_cart_type'])) {
        $acceptedOffers = $_SESSION[UserAuthentication::SESSION_ELEMENT_NAME]['acceptedOffers'] ?? [];
        $acceptedOfferId = $acceptedOffers[$product['selprod_id']]['accepted_offer_id'] ?? 0;
    }

    if (0 < $currentStock && !$isOutOfMinOrderQty) {
        if (true == $displayProductNotAvailableLable && array_key_exists('availableInLocation', $product) && 0 == $product['availableInLocation']) {  ?>
            <button type="button" disabled="disabled" class="btn btn-brand btn-block mt-3">
                <?php echo Labels::getLabel('LBL_NOT_AVAILABLE_FOR_YOUR_LOCATION', $siteLangId); ?>
            </button>
            <?php } else {
            echo $frmBuyProduct->getFormTag();
            $qtyField =  $frmBuyProduct->getField('quantity');
            $qtyField->value = $product['selprod_min_order_qty'];
            $qtyField->addFieldTagAttribute('data-min-qty', $product['selprod_min_order_qty']);
            $qtyFieldName =  $qtyField->getCaption();
            if (strtotime($product['selprod_available_from']) <= strtotime(FatDate::nowInTimezone(FatApp::getConfig('CONF_TIMEZONE'), 'Y-m-d'))) { ?>
                <div class="options-block">
                    <?php if (false === SellerProduct::isPriceHidden($product['selprod_hide_price'], $product['shop_rfq_enabled'])) { ?>
                        <div class="options-block-head">
                            <h6 class="h6"><?php echo $qtyFieldName; ?></h6>
                            <div class="quantity" data-stock="<?php echo $product['selprod_stock']; ?>">
                                <button class="decrease decrease-js disabled" type="button">
                                    <svg class="svg" width="16" height="16">
                                        <use xlink:href="<?php echo CONF_WEBROOT_URL; ?>images/retina/sprite.svg<?php echo AttachedFile::setTimeParam(RELEASE_DATE); ?>#minus">
                                        </use>
                                    </svg>
                                </button>
                                <div class="qty-input-wrapper" data-stock="<?php echo $product['selprod_stock']; ?>">
                                    <?php echo $frmBuyProduct->getFieldHtml('quantity'); ?>
                                </div>
                                <button
                                    class="increase increase-js <?php echo $product['selprod_stock'] <= $product['selprod_min_order_qty'] ? 'disabled' : ''; ?>">
                                    <svg class="svg" width="16" height="16">
                                        <use xlink:href="<?php echo CONF_WEBROOT_URL; ?>images/retina/sprite.svg<?php echo AttachedFile::setTimeParam(RELEASE_DATE); ?>#plus">
                                        </use>
                                    </svg>
                                </button>
                            </div>
                        </div>
                    <?php } else { ?>
                        <span class="d-none">
                            <?php echo $frmBuyProduct->getFieldHtml('quantity'); ?>
                        </span>
                    <?php } ?>
                </div>
                <div class="buy-action">
                    <?php
                    $fromDate = strtotime($product['selprod_available_from']);
                    $currentDate = strtotime(FatDate::nowInTimezone(FatApp::getConfig('CONF_TIMEZONE'), 'Y-m-d'));

                    if (
                        $fromDate <= $currentDate &&
                        false === SellerProduct::isPriceHidden($product['selprod_hide_price'], $product['shop_rfq_enabled']) &&
                        (
                            SellerProduct::isCartType($product['shop_rfq_enabled'], $product['selprod_cart_type'])
                        )
                    ) {
                        echo $frmBuyProduct->getFieldHtml('btnAddToCart');
                    }
                    echo $frmBuyProduct->getFieldHtml('selprod_id');

                    /* if (0 < $acceptedOfferId) { ?>
        <a class="btn btn-outline-brand btn-block btn-rfq"
            href="<?php echo UrlHelper::generateUrl('RfqOffers', 'checkout', [$product['selprod_id'], $acceptedOfferId], CONF_WEBROOT_DASHBOARD); ?>"
            title="<?php echo Labels::getLabel('BTN_BUY_NOW'); ?>">
            <?php echo Labels::getLabel('BTN_BUY_NOW'); ?>
            <svg class="svg" width="20" height="20">
                <use xlink:href="<?php echo CONF_WEBROOT_URL; ?>images/retina/procurenet/sprite.svg<?php echo AttachedFile::setTimeParam(RELEASE_DATE); ?>#icon-arrow-tr">
                </use>
            </svg>
        </a>
        <?php } else { */
                    if (
                        RequestForQuote::isEnabled($product['shop_rfq_enabled'], $product['selprod_cart_type'])
                    ) { ?>
                        <button class="btn btn-outline-brand btn-block btn-rfq" name="requestForQuote" type="button"
                            onclick="requestForQuoteFn('<?php echo $product['selprod_id']; ?>');">
                            <?php echo Labels::getLabel('BTN_REQUEST_FOR_QUOTE'); ?>
                        </button>
                    <?php //}
                    } ?>
                </div>
            <?php } ?>
        <?php echo '</form>' . $frmBuyProduct->getExternalJs();
        }
    } else { ?>
        <div class="buy-action">
            <?php if (!RequestForQuote::isEnabled($product['shop_rfq_enabled'], $product['selprod_cart_type'])) { ?>
                <button type="button" disabled="disabled" class="btn btn-brand btn-block mt-3">
                    <?php echo Labels::getLabel('LBL_SOLD_OUT', $siteLangId); ?>
                </button>
            <?php } else { ?>
                <div class="divider mt-4"></div>
            <?php }

            /* if (0 < $acceptedOfferId) { ?>
        <a class="btn btn-brand btn-block btn-rfq"
            href="<?php echo UrlHelper::generateUrl('RfqOffers', 'checkout', [$product['selprod_id'], $acceptedOfferId], CONF_WEBROOT_DASHBOARD); ?>"
            title="<?php echo Labels::getLabel('BTN_BUY_NOW'); ?>">
            <?php echo Labels::getLabel('BTN_BUY_NOW'); ?>
            <svg class="svg" width="20" height="20">
                <use xlink:href="<?php echo CONF_WEBROOT_URL; ?>images/retina/procurenet/sprite.svg<?php echo AttachedFile::setTimeParam(RELEASE_DATE); ?>#icon-arrow-tr">
                </use>
            </svg>
        </a>
        <?php } else { */
            echo $frmBuyProduct->getFieldHtml('selprod_id');
            if (RequestForQuote::isEnabled($product['shop_rfq_enabled'], $product['selprod_cart_type'])) { ?>
                <button class="btn btn-outline-brand btn-block btn-rfq" name="requestForQuote" type="button"
                    onclick="requestForQuoteFn('<?php echo $product['selprod_id']; ?>');">
                    <?php echo Labels::getLabel('BTN_REQUEST_FOR_QUOTE'); ?>
                </button>
                <span class="d-none">
                    <?php echo $frmBuyProduct->getFieldHtml('quantity'); ?>
                </span>
            <?php //}
            } ?>
        </div>
    <?php } ?>

    <?php if (strtotime($product['selprod_available_from']) > strtotime(FatDate::nowInTimezone(FatApp::getConfig('CONF_TIMEZONE'), 'Y-m-d'))) { ?>
        <button type="button" disabled="disabled" class="btn btn-brand btn-block mt-3">
            <?php echo Labels::getLabel('LBL_NOT_AVAILABLE', $siteLangId); ?>
        </button>
        <p class="form-text text-muted">
            <?php echo str_replace('{available-date}', FatDate::Format($product['selprod_available_from']), Labels::getLabel('LBL_This_item_will_be_available_from_{available-date}', $siteLangId)); ?>
        </p>
    <?php } ?>

    <!-- Social Sharing -->
    <?php include('social-sharing.php');  ?>
    <!-- Social Sharing -->

    <!-- More Sellers -->
    <?php include('more-sellers.php');  ?>
    <!-- More Sellers -->

    <?php if ($product['product_type'] == Product::PRODUCT_TYPE_PHYSICAL) { ?>
        <div class="side-blocks delivery-options">
            <h5 class="h5"><?php echo Labels::getLabel('LBL_DELIVERY_OPTIONS'); ?></h5>
            <div class="side-blocks-body">
                <?php include(CONF_THEME_PATH . '_partial/product/shipping-rates.php'); ?></div>
        </div>
    <?php } ?>

    <?php
    $hidePrice = (SellerProduct::isPriceHidden($product['selprod_hide_price'], $product['shop_rfq_enabled']) || RequestForQuote::isCartTypeRfqOnly($product['shop_rfq_enabled'], $product['selprod_cart_type']));
    if (isset($volumeDiscountRows) && !empty($volumeDiscountRows) && 0 < $currentStock && false == $hidePrice) { ?>
        <div class="side-blocks wholesale-slider">
            <h5 class="h5"><?php echo Labels::getLabel('LBL_WHOLESALE_PRICE_(PIECE)') ?></h5>
            <div class="side-blocks-body">
                <ul class="wholesale-slider">
                    <?php foreach ($volumeDiscountRows as $volumeDiscountRow) {
                        $volumeDiscount = $product['theprice'] * ($volumeDiscountRow['voldiscount_percentage'] / 100);
                        $price = ($product['theprice'] - $volumeDiscount); ?>
                        <li class="wholesale-slider-item">
                            <span class="wholesale-slider-value"> <?php echo ($volumeDiscountRow['voldiscount_min_qty']); ?>
                                <?php echo Labels::getLabel('LBL_OR_MORE_PIECES', $siteLangId); ?></span>
                            <div class="products-price">
                                <span
                                    class="products-price-new"><?php echo CommonHelper::displayMoneyFormat($price, true, false, true, false, false, true); ?></span>
                                <del
                                    class="products-price-old"><?php echo CommonHelper::displayMoneyFormat($product['theprice'], true, false, true, false, false, true); ?></del>
                                <span
                                    class="products-price-off"><?php echo $volumeDiscountRow['voldiscount_percentage'] . '%'; ?>
                                    <?php echo Labels::getLabel('LBL_OFF', $siteLangId); ?></span>
                            </div>
                        </li>
                    <?php } ?>
                </ul>
            </div>
        </div>
    <?php }  ?>

    <!-- Upsell Products -->
    <?php if (count($upsellProducts) > 0 && false == $hidePrice) { ?>
        <div class="side-blocks product-add-ons">
            <h5 class="h5"> <?php echo Labels::getLabel('LBL_Product_Add-ons', $siteLangId); ?></h5>
            <div class="side-blocks-body">
                <ul class="list-addons list-addons--js">
                    <?php foreach ($upsellProducts as $usproduct) {
                        $cancelClass = '';
                        $uncheckBoxClass = '';
                        if ($usproduct['selprod_stock'] <= 0) {
                            $cancelClass = 'cancel cancelled--js';
                            $uncheckBoxClass = 'remove-add-on';
                        } ?>
                        <li
                            class="list-addons-item addon--js <?php echo $cancelClass; ?> <?php echo ($usproduct['selprod_stock'] <= 0) ? 'out-of-stock' : ''; ?>">
                            <div class="product-profile">
                                <figure class="product-profile__pic">
                                    <a title="<?php echo $usproduct['selprod_title']; ?>"
                                        href="<?php echo UrlHelper::generateUrl('products', 'view', array($usproduct['selprod_id'])) ?>">
                                        <img src="<?php echo UrlHelper::getCachedUrl(UrlHelper::generateFileUrl('Image', 'product', array($usproduct['product_id'], ImageDimension::VIEW_MINI, $usproduct['selprod_id'])), CONF_IMG_CACHE_TIME, '.jpg'); ?>"
                                            alt="<?php echo $usproduct['product_identifier']; ?>"
                                            <?php echo HtmlHelper::getImgDimParm(ImageDimension::TYPE_PRODUCTS, ImageDimension::VIEW_MINI); ?>>
                                    </a>
                                </figure>
                                <div class="product-profile-data">
                                    <a class="title"
                                        href="<?php echo UrlHelper::generateUrl('products', 'view', array($usproduct['selprod_id'])) ?>"><?php echo $usproduct['selprod_title'] ?></a>
                                    <?php if (false === SellerProduct::isPriceHidden($usproduct['selprod_hide_price'], $usproduct['shop_rfq_enabled'])) { ?>
                                        <div class="products-price">
                                            <?php echo CommonHelper::displayMoneyFormat($usproduct['theprice'], true, false, true, false, false, true); ?>
                                        </div>
                                        <div class="quantity quantity-2" data-stock="<?php echo $usproduct['selprod_stock']; ?>">
                                            <button class="decrease decrease-js disabled" type="button">
                                                <svg class="svg" width="16" height="16">
                                                    <use xlink:href="<?php echo CONF_WEBROOT_URL; ?>images/retina/sprite.svg<?php echo AttachedFile::setTimeParam(RELEASE_DATE); ?>#minus">
                                                    </use>
                                                </svg>
                                            </button>
                                            <div class="qty-input-wrapper" data-stock="<?php echo $usproduct['selprod_stock']; ?>">
                                                <input type="text" value="<?php echo $usproduct['selprod_min_order_qty']; ?>"
                                                    data-min-qty="<?php echo $usproduct['selprod_min_order_qty']; ?>"
                                                    data-page="product-view" placeholder="Qty"
                                                    class="qty-input cartQtyTextBox productQty-js"
                                                    data-lang="addons[<?php echo $usproduct['selprod_id'] ?>]"
                                                    name="addons[<?php echo $usproduct['selprod_id'] ?>]">
                                            </div>
                                            <button
                                                class="increase increase-js <?php echo $usproduct['selprod_stock'] <= $usproduct['selprod_min_order_qty'] ? 'disabled' : ''; ?>">
                                                <svg class="svg" width="16" height="16">
                                                    <use xlink:href="<?php echo CONF_WEBROOT_URL; ?>images/retina/sprite.svg<?php echo AttachedFile::setTimeParam(RELEASE_DATE); ?>#plus">
                                                    </use>
                                                </svg>
                                            </button>
                                        </div>
                                    <?php } ?>
                                </div>
                            </div>
                            <?php if (false === SellerProduct::isPriceHidden($usproduct['selprod_hide_price'], $usproduct['shop_rfq_enabled'])) { ?>
                                <label class="checkbox">
                                    <input <?php echo ($usproduct['selprod_stock'] > 0) ? 'checked="checked"' : ''; ?>
                                        type="checkbox" class="cancel <?php echo $uncheckBoxClass; ?>" name="check_addons"
                                        title="<?php echo Labels::getLabel('LBL_Remove', $siteLangId); ?>">
                                </label>
                            <?php } ?>
                        </li>
                    <?php } ?>
                </ul>
            </div>
        </div>
    <?php } ?>
</div>