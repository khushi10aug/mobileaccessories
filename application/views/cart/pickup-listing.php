<?php defined('SYSTEM_INIT') or die('Invalid Usage.');
$showAddToFavorite = true;
if (UserAuthentication::isUserLogged() && (!User::isBuyer())) {
    $showAddToFavorite = false;
}
?>
<div class="cart-blocks">
    <?php
    $productsCount = count($products);
    if ($productsCount) {
        uasort($products, function ($a, $b) {
            return $b['fulfillment_type'] - $a['fulfillment_type'];
        });
    ?>
        <script>
            var productData = [];
        </script>
        <ul
            class="list-cart <?php echo (count($fulfillmentProdArr[Shipping::FULFILMENT_PICKUP]) != $productsCount) ? '' : 'list-cart-page'; ?>">
            <?php
            //if (count($fulfillmentProdArr[Shipping::FULFILMENT_PICKUP]) > 0 && count($fulfillmentProdArr[Shipping::FULFILMENT_PICKUP]) != $productsCount) {
            if (count($fulfillmentProdArr[Shipping::FULFILMENT_PICKUP]) != $productsCount) {
            ?>
                <li class="list-cart-item minus-space">
                    <div class="delivery-info">
                        <svg class="svg" width="20" height="20">
                            <use xlink:href="<?php echo CONF_WEBROOT_URL; ?>images/retina/sprite.svg<?php echo AttachedFile::setTimeParam(RELEASE_DATE); ?>#warning">
                            </use>
                        </svg>
                        <span class="not-pickup">
                            <?php echo Labels::getLabel('MSG_SOME_ITEMS_NOT_AVAILABLE_FOR_PICKUP', $siteLangId); ?>
                            <?php if (count($fulfillmentProdArr[Shipping::FULFILMENT_SHIP]) == $productsCount) { ?>

                                <button class="link-underline"
                                    onClick="listCartProducts(<?php echo Shipping::FULFILMENT_SHIP; ?>);"><?php echo Labels::getLabel('LBL_Ship_Entire_Order', $siteLangId); ?>
                                </button>
                            <?php } ?>
                        </span>
                        <button class="btn-close" onClick="removeShippedOnlyProducts();" data-bs-toggle="tooltip"
                            data-placement="top"
                            title="<?php echo Labels::getLabel('MSG_REMOVE_SHIP_ONLY_PRODUCTS', $siteLangId); ?>">
                        </button>
                    </div>
                </li>
                <?php foreach ($products as $key => $product) {
                    if ($product['fulfillment_type'] != Shipping::FULFILMENT_SHIP) {
                        continue;
                    }
                    $uploadedTime = AttachedFile::setTimeParam($product['product_updated_on']);
                    $productUrl = UrlHelper::generateUrl('Products', 'View', array($product['selprod_id']));
                    $shopUrl = UrlHelper::generateUrl('Shops', 'View', array($product['shop_id']));
                    $imageUrl = UrlHelper::getCachedUrl(UrlHelper::generateFileUrl('image', 'product', array($product['product_id'], ImageDimension::VIEW_THUMB, $product['selprod_id'], 0, $siteLangId)) . $uploadedTime, CONF_IMG_CACHE_TIME, '.jpg');
                    $imageWebpUrl = UrlHelper::getCachedUrl(UrlHelper::generateFileUrl('image', 'product', array($product['product_id'], 'WEBP' . ImageDimension::VIEW_THUMB, $product['selprod_id'], 0, $siteLangId)) . $uploadedTime, CONF_IMG_CACHE_TIME, '.webp');
                    $productTitle = ($product['selprod_title']) ? CommonHelper::renderHtml($product['selprod_title'], true) : CommonHelper::renderHtml($product['product_name'], true);
                ?>
                    <li
                        class="list-cart-item block-cart <?php echo md5($product['key']); ?> <?php echo (!$product['in_stock']) ? 'disabled' : ''; ?> list-saved-later">
                        <div class="block-cart-img">
                            <div class="product-profile">
                                <div class="products-img">
                                    <a href="<?php echo $productUrl; ?>">
                                        <?php
                                        $pictureAttr = [
                                            'webpImageUrl' => [ImageDimension::VIEW_DESKTOP => $imageWebpUrl],
                                            'jpgImageUrl' => [ImageDimension::VIEW_DESKTOP => $imageUrl],
                                            'ratio' => '1:1',
                                            'alt' => $productTitle,
                                            'imageUrl' => $imageUrl,
                                            'siteLangId' => $siteLangId,
                                        ];

                                        $this->includeTemplate('_partial/picture-tag.php', $pictureAttr);
                                        ?>
                                    </a>
                                </div>

                            </div>
                        </div>
                        <div class="block-cart-detail">
                            <div class="block-cart-detail-top">
                                <div class="product-profile">
                                    <div class="product-profile-data">
                                        <div class="item__category">
                                            <a class="link-brand"
                                                href="<?php echo UrlHelper::generateUrl('shops', 'view', array($product['shop_id'])); ?>">
                                                <span
                                                    class="text--dark"><?php echo CommonHelper::renderHtml($product['shop_name'], true); ?></span>
                                            </a>
                                        </div>
                                        <a class="title" href="<?php echo $productUrl; ?>"><?php echo $productTitle; ?></a>
                                        <div class="options">
                                            <p class="">
                                                <?php
                                                if (isset($product['options']) && count($product['options'])) {
                                                    foreach ($product['options'] as $key => $option) {
                                                        if (0 < $key) {
                                                            echo ' | ';
                                                        }
                                                        echo CommonHelper::renderHtml($option['option_name'], true) . ':'; ?>
                                                        <span
                                                            class="text--dark"><?php echo CommonHelper::renderHtml($option['optionvalue_name'], true); ?></span>
                                                <?php }
                                                } ?>
                                            </p>
                                        </div>

                                        <p class="text-danger pt-2">
                                            <?php echo Labels::getLabel('LBL_NOT_AVAILABLE_FOR_PICKUP', $siteLangId); ?>
                                        </p>
                                    </div>
                                </div>
                            </div>
                            <div class="block-cart-detail-bottom">
                                <ul class="cart-action">
                                    <li>
                                        <button class="btn btn-link"
                                            onClick="moveToSaveForLater( '<?php echo md5($product['key']); ?>',<?php echo $product['selprod_id']; ?>, <?php echo Shipping::FULFILMENT_PICKUP; ?> );">
                                            <?php echo Labels::getLabel('LBL_SAVE_FOR_LATER', $siteLangId); ?>
                                        </button>
                                    </li>
                                </ul>
                            </div>

                        </div>
                    </li>
                <?php } ?>
        </ul>
        <ul class="list-cart">
        <?php } ?>
        <?php foreach ($products as $product) {

            if ($product['fulfillment_type'] == Shipping::FULFILMENT_SHIP) {
                continue;
            }
            $uploadedTime = AttachedFile::setTimeParam($product['product_updated_on']);
            $productUrl = UrlHelper::generateUrl('Products', 'View', array($product['selprod_id']));
            $shopUrl = UrlHelper::generateUrl('Shops', 'View', array($product['shop_id']));
            $imageUrl = UrlHelper::getCachedUrl(UrlHelper::generateFileUrl('image', 'product', array($product['product_id'], ImageDimension::VIEW_THUMB, $product['selprod_id'], 0, $siteLangId)) . $uploadedTime, CONF_IMG_CACHE_TIME, '.jpg');
            $imageWebpUrl = UrlHelper::getCachedUrl(UrlHelper::generateFileUrl('image', 'product', array($product['product_id'], 'WEBP' . ImageDimension::VIEW_THUMB, $product['selprod_id'], 0, $siteLangId)) . $uploadedTime, CONF_IMG_CACHE_TIME, '.webp');
            $productTitle = ($product['selprod_title']) ? CommonHelper::renderHtml($product['selprod_title'], true) : CommonHelper::renderHtml($product['product_name'], true);

        ?>

            <li
                class="list-cart-item block-cart <?php echo md5($product['key']); ?> <?php echo (!$product['in_stock']) ? 'disabled' : ''; ?>">
                <div class="block-cart-img">
                    <div class="products-img">
                        <a href="<?php echo $productUrl; ?>">
                            <?php
                            $pictureAttr = [
                                'webpImageUrl' => [ImageDimension::VIEW_DESKTOP => $imageWebpUrl],
                                'jpgImageUrl' => [ImageDimension::VIEW_DESKTOP => $imageUrl],
                                'imageUrl' => $imageUrl,
                                'ratio' => '1:1',
                                'alt' => $productTitle,
                                'siteLangId' => $siteLangId,
                            ];

                            $this->includeTemplate('_partial/picture-tag.php', $pictureAttr);
                            ?>
                        </a>
                    </div>

                </div>
                <div class="block-cart-detail">
                    <div class="block-cart-detail-top">
                        <div class="product-profile">
                            <div class="product-profile-data">
                                <div class="item__category">
                                    <a class="link-brand"
                                        href="<?php echo UrlHelper::generateUrl('shops', 'view', array($product['shop_id'])); ?>">
                                        <span
                                            class="text--dark"><?php echo CommonHelper::renderHtml($product['shop_name'], true); ?></span>
                                    </a>
                                </div>
                                <a class="title" href="<?php echo $productUrl; ?>"><?php echo $productTitle; ?></a>
                                <div class="products-price">
                                    <span
                                        class="products-price-new"><?php echo trim(CommonHelper::displayMoneyFormat($product['theprice'], true, false, true, false, false, true)); ?></span>
                                    <?php if ($product['selprod_price'] > $product['theprice']) { ?>
                                        <del
                                            class="products-price-old"><?php echo trim(CommonHelper::displayMoneyFormat($product['selprod_price'], true, false, true, false, false, true)); ?></del>
                                        <div class="products-price-off">
                                            <?php echo trim(CommonHelper::showProductDiscountedText($product, $siteLangId)); ?>
                                        </div>
                                    <?php } ?>
                                </div>
                                <div class="options">
                                    <p>
                                        <?php
                                        if (isset($product['options']) && count($product['options'])) {
                                            foreach ($product['options'] as $key => $option) {
                                                if (0 < $key) {
                                                    echo ' | ';
                                                }
                                                echo CommonHelper::renderHtml($option['option_name'], true) . ':'; ?>
                                                <span
                                                    class="text--dark"><?php echo CommonHelper::renderHtml($option['optionvalue_name'], true); ?></span>
                                        <?php }
                                        } ?>
                                    </p>
                                </div>
                            </div>
                        </div>
                        <div class="product-quantity">
                            <div class="quantity quantity-sm" data-stock="<?php echo $product['selprod_stock']; ?>">
                                <?php if (isset($_SESSION['offer_checkout']) && $_SESSION['offer_checkout']['selprod_id'] == $product['selprod_id']) { ?>
                                    <div class="selected-qty">
                                        <strong><?php echo Labels::getLabel('LBL_QTY_:') ?></strong>
                                        <?php echo $product['quantity']; ?>
                                        <?php echo $_SESSION['offer_checkout']['offer_quantity_unit']; ?>
                                    </div>
                                <?php } else { ?>
                                    <button
                                        class="decrease decrease-js shipProductsCount <?php echo ($product['quantity'] <= $product['selprod_min_order_qty']) ? 'disabled' : ''; ?>"
                                        type="button">
                                        <svg class="svg" width="16" height="16">
                                            <use xlink:href="<?php echo CONF_WEBROOT_URL; ?>images/retina/sprite.svg<?php echo AttachedFile::setTimeParam(RELEASE_DATE); ?>#minus">
                                            </use>
                                        </svg>
                                    </button>
                                    <div class="qty-input-wrapper" data-stock="<?php echo $product['selprod_stock']; ?>">
                                        <input name="qty_<?php echo md5($product['key']); ?>"
                                            data-key="<?php echo md5($product['key']); ?>"
                                            class="qty-input cartQtyTextBox productQty-js"
                                            value="<?php echo $product['quantity']; ?>" type="text" />
                                    </div>
                                    <button
                                        class="increase increase-js <?php echo ($product['selprod_stock'] <= $product['quantity']) ? 'disabled' : ''; ?>">
                                        <svg class="svg" width="16" height="16">
                                            <use xlink:href="<?php echo CONF_WEBROOT_URL; ?>images/retina/sprite.svg<?php echo AttachedFile::setTimeParam(RELEASE_DATE); ?>#plus">
                                            </use>
                                        </svg>
                                    </button>
                                <?php } ?>
                            </div>
                        </div>
                    </div>
                    <div class="block-cart-detail-bottom">
                        <ul class="cart-action">
                            <li class="cart-action-item">
                                <button class="btn btn-link" type="button"
                                    onclick="cart.remove('<?php echo md5($product['key']); ?>','cart')"
                                    title="<?php echo Labels::getLabel('LBL_Remove', $siteLangId); ?>">
                                    <?php echo Labels::getLabel('LBL_Remove', $siteLangId); ?>
                                </button>
                            </li>
                            <?php if ($showAddToFavorite) { ?>
                                <li class="cart-action-item">
                                    <?php if (FatApp::getConfig('CONF_ADD_FAVORITES_TO_WISHLIST', FatUtility::VAR_INT, 1) == applicationConstants::NO) {
                                        if (empty($product['ufp_id'])) { ?>
                                            <button class="btn btn-link"
                                                onClick="addToFavourite( '<?php echo md5($product['key']); ?>',<?php echo $product['selprod_id']; ?> );"
                                                title="<?php echo Labels::getLabel('LBL_MOVE_TO_FAVOURITE', $siteLangId); ?>">

                                                <?php echo Labels::getLabel('LBL_MOVE_TO_FAVOURITE', $siteLangId); ?>


                                            </button>

                                        <?php } else { ?>
                                            <button class="btn btn-link is-active"
                                                title="<?php echo Labels::getLabel('LBL_Already_marked_as_favourites.', $siteLangId); ?>">
                                                <?php echo Labels::getLabel('LBL_Already_marked_as_favourites.', $siteLangId); ?>
                                            </button>
                                        <?php }
                                    } else {
                                        if (empty($product['is_in_any_wishlist'])) { ?>
                                            <button class="btn btn-link"
                                                onClick="moveToWishlist( <?php echo $product['selprod_id']; ?>, event, '<?php echo md5($product['key']); ?>' );"
                                                title="<?php echo Labels::getLabel('LBL_Move_to_wishlist', $siteLangId); ?>">
                                                <?php echo Labels::getLabel('LBL_Move_to_wishlist', $siteLangId); ?></button>
                                        <?php } else { ?>
                                            <button class="btn btn-link favourite wishListLink-Js is-active"
                                                data-id="<?php echo $product['selprod_id']; ?>"
                                                onClick="viewWishList(<?php echo $product['selprod_id']; ?>,this,event);"
                                                title="<?php echo Labels::getLabel('LBL_Remove_product_from_your_wishlist', $siteLangId); ?>">
                                                <?php echo Labels::getLabel('LBL_Remove_product_from_your_wishlist', $siteLangId); ?>
                                            </button>
                                    <?php }
                                    }
                                    ?>
                                </li>
                            <?php } ?>
                            <li>
                                <button class="btn btn-link"
                                    onClick="moveToSaveForLater( '<?php echo md5($product['key']); ?>',<?php echo $product['selprod_id']; ?>, <?php echo Shipping::FULFILMENT_PICKUP; ?> );"
                                    title="<?php echo Labels::getLabel('LBL_SAVE_FOR_LATER', $siteLangId); ?>">
                                    <?php echo Labels::getLabel('LBL_SAVE_FOR_LATER', $siteLangId); ?>
                                </button>
                            </li>
                        </ul>
                    </div>
                </div>

                <script type="text/javascript">
                    productData.push({
                        item_id: "<?php echo $product['selprod_id']; ?>",
                        item_name: "<?php echo CommonHelper::renderHtml($product['selprod_title'], true); ?>",
                        discount: "<?php echo ($product['selprod_price'] - $product['theprice']); ?>",
                        index: "<?php echo $product['selprod_id']; ?>",
                        item_brand: "<?php echo CommonHelper::renderHtml($product['brand_name'], true); ?>",
                        item_category: "<?php echo CommonHelper::renderHtml($product['prodcat_name'], true); ?>",
                        price: "<?php echo $product['theprice']; ?>",
                        quantity: "<?php echo $product['quantity']; ?>"
                    })
                </script>
            </li>
        <?php } ?>
        </ul>
    <?php } ?>
    <?php if (0 < count($saveForLaterProducts)) { ?>
        <h5 class="cart-title mt-5"><?php echo Labels::getLabel('LBL_Save_For_later', $siteLangId); ?>
            (<?php echo count($saveForLaterProducts); ?>)</h5>
        <ul class="list-cart <?php echo 1 > $productsCount ? 'list-cart-triple' : ''; ?>">
            <?php foreach ($saveForLaterProducts as $product) {
                $productUrl = UrlHelper::generateUrl('Products', 'View', array($product['selprod_id']));
                $imageUrl = UrlHelper::getCachedUrl(UrlHelper::generateFileUrl('image', 'product', array($product['product_id'], ImageDimension::VIEW_THUMB, $product['selprod_id'], 0, $siteLangId)), CONF_IMG_CACHE_TIME, '.jpg');
                $productTitle = ($product['selprod_title']) ? $product['selprod_title'] : $product['product_name'];
                $imageWebpUrl = UrlHelper::getCachedUrl(UrlHelper::generateFileUrl('image', 'product', array($product['product_id'], 'WEBP' . ImageDimension::VIEW_THUMB, $product['selprod_id'], 0, $siteLangId)), CONF_IMG_CACHE_TIME, '.webp');
            ?>
                <li
                    class="list-cart-item block-cart <?php echo isset($product['key']) ? md5($product['key']) : ''; ?> <?php echo (!$product['in_stock']) ? 'disabled' : ''; ?>">
                    <div class="block-cart-img">
                        <div class="products-img">
                            <a href="<?php echo $productUrl; ?>">
                                <?php
                                $pictureAttr = [
                                    'webpImageUrl' => [ImageDimension::VIEW_DESKTOP => $imageWebpUrl],
                                    'jpgImageUrl' => [ImageDimension::VIEW_DESKTOP => $imageUrl],
                                    'imageUrl' => $imageUrl,
                                    'ratio' => '1:1',
                                    'alt' => $productTitle,
                                    'siteLangId' => $siteLangId,
                                ];

                                $this->includeTemplate('_partial/picture-tag.php', $pictureAttr);
                                ?>
                            </a>
                        </div>

                    </div>
                    <div class="block-cart-detail">
                        <div class="block-cart-detail-top">
                            <div class="product-profile">
                                <div class="product-profile-data">
                                    <div class="item__category">
                                        <a class="link-brand"
                                            href="<?php echo UrlHelper::generateUrl('shops', 'view', array($product['shop_id'])); ?>">
                                            <span
                                                class="text--dark"><?php echo CommonHelper::renderHtml($product['shop_name'], true); ?></span>
                                        </a>
                                    </div>
                                    <a class="title" href="<?php echo $productUrl; ?>">
                                        <?php echo $productTitle; ?>
                                    </a>
                                    <div class="products-price">
                                        <?php echo CommonHelper::displayMoneyFormat($product['theprice']); ?>
                                    </div>
                                    <div class="options">
                                        <?php
                                        if (isset($product['options']) && count($product['options'])) {
                                            foreach ($product['options'] as $key => $option) {
                                                if (0 < $key) {
                                                    echo ' | ';
                                                }
                                                echo CommonHelper::renderHtml($option['option_name'], true) . ':'; ?>
                                                <span
                                                    class="text-muted"><?php echo CommonHelper::renderHtml($option['optionvalue_name'], true); ?></span>
                                        <?php }
                                        } ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="block-cart-detail-bottom">
                            <ul class="cart-action">
                                <li class="cart-action-item">
                                    <button class="btn btn-link" type="button"
                                        onclick="removeFromWishlist(<?php echo $product['selprod_id']; ?>, <?php echo $product['uwlp_uwlist_id']; ?>, event)">
                                        <?php echo Labels::getLabel('LBL_Remove', $siteLangId); ?>
                                    </button>
                                </li>
                                <li class="cart-action-item">
                                    <button class="btn btn-link"
                                        onclick="moveToCart(<?php echo $product['selprod_id']; ?>, <?php echo $product['uwlp_uwlist_id']; ?>, event, <?php echo Shipping::FULFILMENT_PICKUP; ?>)">
                                        <?php echo Labels::getLabel('LBL_MOVE_TO_CART', $siteLangId); ?>
                                    </button>
                                </li>
                            </ul>
                        </div>
                    </div>
                </li>
            <?php } ?>
        </ul>
    <?php } ?>
</div>
<?php $netChargeAmt = $cartSummary['cartTotal'] - ((0 < $cartSummary['cartVolumeDiscount']) ? $cartSummary['cartVolumeDiscount'] : 0);
$netChargeAmt = $netChargeAmt - ((isset($cartSummary['cartDiscounts']['coupon_discount_total']) && 0 < $cartSummary['cartDiscounts']['coupon_discount_total']) ? $cartSummary['cartDiscounts']['coupon_discount_total'] : 0); ?>
<script type="text/javascript">
    ykevents.viewCart({
        currency: currencyCode,
        value: "<?php echo $netChargeAmt; ?>",
        items: productData
    });
</script>