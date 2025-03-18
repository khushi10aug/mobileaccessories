<?php defined('SYSTEM_INIT') or die('Invalid Usage.');
$count = 1;
$shopTotalReviews = $shopTotalReviews ?? 0;
$displaySellerId = $displaySellerId ?? 0;
foreach ($sellers as $key => $sellerDetail) {
    $isActive = array_key_exists('isActive', $sellerDetail) && true === $sellerDetail['isActive'];
    if ($count > Product::VIEW_MORE_SELLER_COUNT) { ?>
        <li class="more-sellers-item more-link">
            <a href="<?php echo UrlHelper::generateUrl('products', 'sellers', array($sellerDetail['selprod_id'])); ?>"
                class="link-underline">
                <?php echo Labels::getLabel('LBL_VIEW_ALL', $siteLangId); ?>
            </a>
        </li>
    <?php break;
    }
    $shopTotalReviews = $sellerDetail['shopTotalReviews'] ?? $shopTotalReviews;
    if (1 == $count && !array_key_exists('isActive', $sellerDetail)) {
        echo '<li class="more-sellers-head">' . Labels::getLabel('LBL_MORE_SELLERS', $siteLangId) . '</li>';
    }
    ?>
    <li class="more-sellers-item <?php echo ($isActive ? 'is-active' : ''); ?>">
        <?php if (false === SellerProduct::isPriceHidden($sellerDetail['selprod_hide_price'], $sellerDetail['shop_rfq_enabled'])) { ?>
            <div class="sold-price"><?php echo CommonHelper::displayMoneyFormat($sellerDetail['theprice']); ?></div>
        <?php } ?>
        <div class="sold-by">
            <span class="sold-by-txt"><?php echo Labels::getLabel('LBL_SOLD_BY', $siteLangId); ?></span>
            <?php if ($displaySellerId == $sellerDetail['selprod_user_id']) { ?>
                <a class="sold-by-name"
                    href="<?php echo UrlHelper::generateFullUrl('Shops', 'View', array($sellerDetail['shop_id'])); ?>"
                    title="<?php echo $sellerDetail['shop_name']; ?>">
                    <?php echo $sellerDetail['shop_name']; ?>
                </a>
            <?php } else { ?>
                <a class="sold-by-name"
                    href="<?php echo UrlHelper::generateFullUrl('Products', 'View', array($sellerDetail['selprod_id'])); ?>"
                    title="<?php echo $sellerDetail['shop_name']; ?>">
                    <?php echo $sellerDetail['shop_name']; ?>
                </a>
            <?php } ?>
        </div>
        <?php
        $badgesArr = Badge::getShopBadges($siteLangId, [$sellerDetail['shop_id']]);
        $this->includeTemplate('_partial/badge-ui.php', ['badgesArr' => $badgesArr, 'siteLangId' => $siteLangId], false);
        if ($shopTotalReviews > 0 && FatApp::getConfig("CONF_ALLOW_REVIEWS", FatUtility::VAR_INT, 0)) {
            $shop_rating = SelProdRating::getSellerRating($sellerDetail['selprod_user_id'], true);
        ?>
            <div class="shop-wrap">
                <div class="product-ratings">
                    <svg class="svg svg-star" width="14" height="14">
                        <use xlink:href="<?php echo CONF_WEBROOT_URL; ?>images/retina/sprite.svg<?php echo AttachedFile::setTimeParam(RELEASE_DATE); ?>#star-yellow">
                        </use>
                    </svg>
                    <span class="rate"><?php echo round($shop_rating, 1); ?></span>
                    <a href="<?php echo UrlHelper::generateUrl('reviews', 'shop', array($sellerDetail['shop_id'])); ?>"
                        class="totals-review"><?php echo $shopTotalReviews; ?>
                        <?php echo Labels::getLabel('LBL_REVIEWS', $siteLangId); ?>
                    </a>
                </div>
            </div>
            <?php }
        if (false === $isActive) {
            if (false === SellerProduct::isPriceHidden($sellerDetail['selprod_hide_price'], $sellerDetail['shop_rfq_enabled']) && !RequestForQuote::isCartTypeRfqOnly($sellerDetail['shop_rfq_enabled'], $sellerDetail['selprod_cart_type'])) { ?>
                <button class="btn btn-outline-black btn-sm btnAddToCart--js" data-id='<?php echo $sellerDetail['selprod_id']; ?>'
                    data-min-qty="<?php echo $sellerDetail['selprod_min_order_qty']; ?>" type="button"
                    data-cart-has-product="<?php echo 0 < $cartSellerId && $sellerDetail['selprod_user_id'] != $cartSellerId && 0 < FatApp::getConfig('CONF_SINGLE_SELLER_CART', FatUtility::VAR_INT, 0); ?>"><?php echo Labels::getLabel('BTN_ADD_TO_CART', $siteLangId); ?></button>
            <?php } else { ?>
                <button class="btn btn-outline-black btn-sm btn-rfq" name="requestForQuote" type="button" onclick="requestForQuoteFn('<?php echo $sellerDetail['selprod_id']; ?>');">
                    <?php echo Labels::getLabel('BTN_REQUEST_FOR_QUOTE'); ?>
                </button>
        <?php }
        } ?>
    </li>
<?php $count++;
}
