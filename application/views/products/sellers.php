<?php defined('SYSTEM_INIT') or die('Invalid Usage.');
$displayProductNotAvailableLable = false;
if (FatApp::getConfig('CONF_ENABLE_GEO_LOCATION', FatUtility::VAR_INT, 0) && !empty(FatApp::getConfig('CONF_GOOGLEMAP_API_KEY', FatUtility::VAR_STRING, ''))) {
    $displayProductNotAvailableLable = true;
}
?>
<div id="body" class="body">
    <?php $this->includeTemplate('_partial/page-head-section.php', ['headLabel' => Labels::getLabel("LBL_PRODUCT'S_SELLERS")]); ?>
    <section class="section" data-section="section">
        <div class="container">
            <div class="grid-layout">
                <div class="grid-layout-start">
                    <div class="sticky-md-top">
                        <div class="product-card">
                            <div class="product-card-start">
                                <div class="product-card-img">
                                    <a title="<?php echo $product['selprod_title']; ?>"
                                        href="<?php echo UrlHelper::generateUrl('products', 'view', array($product['selprod_id'])); ?>">
                                        <img <?php echo HtmlHelper::getImgDimParm(ImageDimension::TYPE_PRODUCTS, ImageDimension::VIEW_SMALL); ?>
                                            alt=""
                                            src="<?php echo UrlHelper::getCachedUrl(UrlHelper::generateFileUrl('image', 'product', array($product['product_id'], ImageDimension::VIEW_SMALL, $product['selprod_id'], 0, $siteLangId), CONF_WEBROOT_URL), CONF_IMG_CACHE_TIME, '.jpg'); ?>">
                                    </a>
                                </div>
                            </div>
                            <div class="product-card-end">
                                <div class="product-card-data">
                                    <a class="title" title="<?php echo $product['selprod_title']; ?>"
                                        href="<?php echo UrlHelper::generateUrl('products', 'view', array($product['selprod_id'])); ?>"><?php echo $product['selprod_title']; ?></a>
                                    <?php if (round($product['prod_rating']) > 0 && FatApp::getConfig("CONF_ALLOW_REVIEWS", FatUtility::VAR_INT, 0)) {
                                        $label = (round($product['prod_rating']) > 0) ? round($product['totReviews'], 1) . ' ' . Labels::getLabel('LBL_Reviews', $siteLangId) : Labels::getLabel('LBL_No_Reviews', $siteLangId); ?>
                                    <div class="rating-block">
                                        <div class="average-rating">
                                            <span class="rate">
                                                <?php echo round($product['prod_rating'], 1); ?>
                                                <svg class="svg svg-star" width="16" height="16">
                                                    <use
                                                        xlink:href="<?php echo CONF_WEBROOT_URL; ?>images/retina/sprite.svg<?php echo AttachedFile::setTimeParam(RELEASE_DATE); ?>#star-yellow">
                                                    </use>
                                                </svg>
                                            </span>
                                            <span class="totals"><?php echo $label; ?></span>
                                        </div>
                                    </div>
                                    <?php } ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="grid-layout-end">
                    <div class="seller-lists">
                        <?php foreach ($product['moreSellersArr'] as $sn => $moresellers) { ?>
                        <div class="seller-card">
                            <div class="seller-card-head">
                                <a title="<?php echo $moresellers['shop_name']; ?>"
                                    href="<?php echo UrlHelper::generateUrl('shops', 'view', array($moresellers['shop_id'])); ?>">
                                    <img class="seller-logo"
                                        src="<?php echo UrlHelper::generateFileUrl('image', 'shopLogo', array($moresellers['shop_id'], $siteLangId, ImageDimension::VIEW_THUMB)); ?>"
                                        alt="<?php echo $moresellers['shop_name']; ?>"
                                        <?php echo HtmlHelper::getImgDimParm(ImageDimension::TYPE_SHOP_LOGO, ImageDimension::VIEW_THUMB); ?>>
                                </a>
                            </div>
                            <div class="seller-card-body">
                                <a class="title" title="<?php echo $moresellers['shop_name']; ?>"
                                    href="<?php echo UrlHelper::generateUrl('shops', 'view', array($moresellers['shop_id'])); ?>">
                                    <?php echo $moresellers['shop_name']; ?>
                                </a>
                                <span class="location">
                                    <?php echo $moresellers['shop_state_name'] . "," . $moresellers['shop_country_name']; ?>
                                </span>
                                <?php if (false === SellerProduct::isPriceHidden($moresellers['selprod_hide_price'], $moresellers['shop_rfq_enabled'])) { ?>
                                <span class="price">
                                    <?php echo CommonHelper::displayMoneyFormat($moresellers['theprice']);
                                            if ($moresellers['selprod_price'] > $moresellers['theprice']) { ?>
                                    <span
                                        class="item__price_old"><?php echo CommonHelper::displayMoneyFormat($moresellers['selprod_price']); ?></span>
                                    <div class="item__price_off">
                                        <?php echo CommonHelper::showProductDiscountedText($moresellers, $siteLangId); ?>
                                    </div>
                                    <?php } ?>
                                </span>
                                <?php } ?>
                                <?php
                                    if (Plugin::isActive('CashOnDelivery') && !empty($product['cod'][$moresellers['selprod_user_id']]) && $product['cod'][$moresellers['selprod_user_id']]) { ?>
                                <span class="payment-mode">
                                    <?php echo Labels::getLabel('LBL_CASH_ON_DELIVERY_AVAILABLE', $siteLangId); ?>
                                </span>
                                <?php } ?>

                                <?php if (SellerProduct::isPriceHidden($moresellers['selprod_hide_price'], $moresellers['shop_rfq_enabled']) && RequestForQuote::isCartTypeRfqOnly($moresellers['shop_rfq_enabled'], $moresellers['selprod_cart_type'])) { ?>
                                <p class="payment-mode">
                                    <?php echo Labels::getLabel('LBL_REQUEST_FOR_QUOTE_ONLY', $siteLangId); ?>
                                </p>
                                <?php } ?>
                            </div>
                            <div class="seller-card-foot">
                                <a class="link-underline"
                                    href="<?php echo UrlHelper::generateUrl('products', 'view', array($moresellers['selprod_id'])); ?>">
                                    <?php echo Labels::getLabel('LBL_VIEW_DETAILS'); ?>
                                </a>
                                <?php if (true == $displayProductNotAvailableLable && array_key_exists('availableInLocation', $product) && 0 == $product['availableInLocation']) { ?>
                                <span
                                    class="text-danger"><?php echo Labels::getLabel('LBL_NOT_AVAILABLE', $siteLangId); ?></span>
                                <?php } else if (false === SellerProduct::isPriceHidden($moresellers['selprod_hide_price'], $moresellers['shop_rfq_enabled']) && !RequestForQuote::isCartTypeRfqOnly($moresellers['shop_rfq_enabled'], $moresellers['selprod_cart_type'])) {
                                        if (date('Y-m-d', strtotime($moresellers['selprod_available_from'])) <= FatDate::nowInTimezone(FatApp::getConfig('CONF_TIMEZONE'), 'Y-m-d')) { ?>
                                <button class="btn btn-outline-black btn-sm btnAddToCart--js" type="button"
                                    data-id="<?php echo $moresellers['selprod_id']; ?>"
                                    data-min-qty="<?php echo $moresellers['selprod_min_order_qty']; ?>">
                                    <?php echo Labels::getLabel('LBL_Add_To_Cart', $siteLangId); ?>
                                </button>
                                <?php } else {
                                            echo CommonHelper::replaceStringData(Labels::getLabel('LBL_THIS_ITEM_WILL_BE_AVAILABLE_FROM_{AVAILABLE-DATE}', $siteLangId), ['{AVAILABLE-DATE}' => FatDate::Format($moresellers['selprod_available_from'])]);
                                        }
                                    } else { ?>
                                <button class="btn btn-outline-black btn-sm" name="requestForQuote" type="button"
                                    onclick="requestForQuoteFn('<?php echo $moresellers['selprod_id']; ?>');"
                                    data-bs-toggle="tooltip"
                                    title="<?php echo Labels::getLabel('BTN_REQUEST_FOR_QUOTE', $siteLangId); ?>">
                                    <?php echo Labels::getLabel('BTN_RFQ'); ?>
                                </button>
                                <?php } ?>
                            </div>
                        </div>
                        <?php } ?>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>