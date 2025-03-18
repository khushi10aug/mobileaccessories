<?php defined('SYSTEM_INIT') or die('Invalid Usage');
if (User::isBuyer(true) || (!UserAuthentication::isUserLogged())) {
    if (true === $showHeaderButton) { ?>
        <button type="button" class="quick-nav-link button-cart" data-bs-toggle="offcanvas" data-bs-target="#sideCartJs">
            <svg class="svg" width="20" height="20">
                <use xlink:href="<?php echo CONF_WEBROOT_URL; ?>images/retina/sprite-header.svg<?php echo AttachedFile::setTimeParam(RELEASE_DATE); ?>#cart"></use>
            </svg>
            <span class="cart-qty">
                <?php
                $cartObj = new Cart();
                echo (Cart::CART_MAX_DISPLAY_QTY < $cartObj->countProducts()) ? Cart::CART_MAX_DISPLAY_QTY . '+' : $cartObj->countProducts(); ?>
            </span>
            <span class="txt">
                <?php echo Labels::getLabel("LBL_MY_BAG", $siteLangId); ?>
            </span>
        </button>
    <?php } else { ?>
        <div class="offcanvas offcanvas-end offcanvas-side-cart" tabindex="-1" id="sideCartJs">
            <div class="offcanvas-header">
                <h5 class="offcanvas-title">
                    <?php echo Labels::getLabel('LBL_ITEMS', $siteLangId); ?> <span class="count-items"> (<?php echo $totalCartItems; ?>) </span>
                </h5>
                <button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas" aria-label="Close"></button>
            </div>
            <?php if ($totalCartItems > 0) { ?>
                <div class="offcanvas-body">
                    <div class="short-detail">
                        <ul class="list-cart">
                            <?php
                            if (count($products)) {
                                foreach ($products as $product) {
                                    $uploadedTime = AttachedFile::setTimeParam($product['product_updated_on']);
                                    $productUrl = UrlHelper::generateUrl('Products', 'View', array($product['selprod_id']));
                                    $shopUrl = UrlHelper::generateUrl('Shops', 'View', array($product['shop_id']));
                                    $imageUrl =  UrlHelper::getCachedUrl(UrlHelper::generateFileUrl('image', 'product', array($product['product_id'], ImageDimension::VIEW_THUMB, $product['selprod_id'], 0, $siteLangId)) . $uploadedTime, CONF_IMG_CACHE_TIME, '.jpg');
                                    $imageWebpUrl = UrlHelper::getCachedUrl(UrlHelper::generateFileUrl('image', 'product', array($product['product_id'], "WEBP" . ImageDimension::VIEW_THUMB, $product['selprod_id'], 0, $siteLangId)) . $uploadedTime,   CONF_IMG_CACHE_TIME, '.webp');
                                    $isDisabled = (!$product['in_stock']) ? 'disabled' : '';
                                    $productType = ($product['is_digital_product']) ? 'digital_product_tab-js' : 'physical_product_tab-js'; ?>

                                    <li class="list-cart-item block-cart block-cart-sm <?php echo $isDisabled . ' ' . $productType; ?>">
                                        <div class="block-cart-img ">
                                            <div class="products-img">
                                                <a href="<?php echo $productUrl; ?>">
                                                    <?php
                                                    $pictureAttr = [
                                                        'siteLangId' => $siteLangId,
                                                        'webpImageUrl' => [ImageDimension::VIEW_DESKTOP => $imageWebpUrl],
                                                        'jpgImageUrl' => [ImageDimension::VIEW_DESKTOP => $imageUrl],
                                                        'imageUrl' => $imageUrl,
                                                        'alt' => $product['product_name'],
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
                                                            <a class="link-brand" href="<?php echo UrlHelper::generateUrl('shops', 'view', array($product['shop_id'])); ?>">
                                                                <span class="text--dark"><?php echo CommonHelper::renderHtml($product['shop_name'], true); ?></span>
                                                            </a>
                                                        </div>
                                                        <?php
                                                        $productName = ($product['selprod_title']) ? $product['selprod_title'] : $product['product_name'];
                                                        $productName = CommonHelper::renderHtml($productName, true);
                                                        ?>
                                                        <a class="title" title="<?php echo $productName; ?>" href="<?php echo $productUrl; ?>"><?php echo $productName; ?></a>
                                                        <?php require(CONF_THEME_PATH . '_partial/collection/product-price.php'); ?>
                                                        <div class="options">
                                                            <?php
                                                            if (isset($product['options']) && count($product['options'])) {
                                                                $count = 0;
                                                                foreach ($product['options'] as $option) {
                                                            ?>
                                                                    <?php echo ($count > 0) ? ' | ' : '';
                                                                    echo CommonHelper::renderHtml($option['option_name'], true) . ':'; ?>
                                                                    <?php echo CommonHelper::renderHtml($option['optionvalue_name'], true); ?>
                                                            <?php $count++;
                                                                }
                                                                echo ' | ';
                                                            }
                                                            echo Labels::getLabel('LBL_Quantity:', $siteLangId) ?>
                                                            <?php echo $product['quantity']; ?>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="block-cart-detail-bottom">
                                                <ul class="cart-action">
                                                    <li class="cart-action-item">
                                                        <button class="btn btn-link" type="button" onclick="cart.remove('<?php echo md5($product['key']); ?>')" title="<?php echo Labels::getLabel('LBL_Remove', $siteLangId); ?>">
                                                            <?php echo Labels::getLabel('LBL_Remove', $siteLangId); ?>
                                                        </button>
                                                    </li>
                                                </ul>
                                            </div>
                                        </div>
                                    </li>
                                <?php
                                }
                            } else {
                                echo Labels::getLabel('LBL_YOUR_CART_IS_EMPTY', $siteLangId);
                                if (isset($saveForLaterProducts) && !empty($saveForLaterProducts)) { ?>
                                    <a class="link-underline" href="<?php echo UrlHelper::generateUrl('Cart'); ?>">
                                        <?php echo CommonHelper::replaceStringData(Labels::getLabel('LBL_VIEW_SAVED_FOR_LATER_({ITEMS-COUNT})_ITEMS', $siteLangId), ['{ITEMS-COUNT}' => count($saveForLaterProducts)]); ?>
                                    </a>
                            <?php }
                            } ?>
                        </ul>
                    </div>
                </div>
                <div class="offcanvas-foot">
                    <ul class="cart-summary">
                        <li class="cart-summary-item">
                            <span class="label"><?php echo Labels::getLabel('LBL_CART_TOTAL', $siteLangId); ?></span>
                            <span class="value"><?php echo CommonHelper::displayMoneyFormat($cartSummary['cartTotal']); ?></span>
                        </li>
                        <?php if (0 < $cartSummary['cartVolumeDiscount']) { ?>
                            <li class="cart-summary-item">
                                <span class="label"><?php echo Labels::getLabel('LBL_Volume_Discount', $siteLangId); ?></span>
                                <span class="value"><?php echo CommonHelper::displayMoneyFormat($cartSummary['cartVolumeDiscount']); ?></span>
                            </li>
                        <?php } ?>
                        <?php ?>
                        <?php $netChargeAmt = $cartSummary['cartTotal'] - ((0 < $cartSummary['cartVolumeDiscount']) ? $cartSummary['cartVolumeDiscount'] : 0); ?>
                        <li class="cart-summary-item highlighted">
                            <span class="label"><?php echo Labels::getLabel('LBL_Net_Payable', $siteLangId); ?></span>
                            <span class="value"><?php echo CommonHelper::displayMoneyFormat($netChargeAmt); ?></span>
                        </li>
                    </ul>

                    <div class="buttons-group">
                        <a href="javascript:void(0);" onclick="cart.clear();" class="btn btn-outline-gray"><?php echo Labels::getLabel('LBL_CLEAR_CART', $siteLangId); ?> </a>
                        <a class="btn btn-brand" href="<?php echo UrlHelper::generateUrl('cart'); ?>"><?php echo Labels::getLabel('LBL_Proceed_To_Pay', $siteLangId); ?></a>
                    </div>

                </div>
            <?php } else { ?>
                <div class="block-empty m-auto text-center">
                    <img class="block__img" width="200" height="200" src="<?php echo CONF_WEBROOT_URL; ?>images/retina/empty-cart.svg" alt="<?php echo Labels::getLabel('LBL_No_Record_Found', $siteLangId); ?>">
                    <h3>
                        <?php echo Labels::getLabel('LBL_YOUR_SHOPPING_BAG_IS_EMPTY', $siteLangId); ?>
                    </h3>
                    <?php if (isset($saveForLaterProducts) && !empty($saveForLaterProducts)) { ?>
                        <a class="link-underline" href="<?php echo UrlHelper::generateUrl('Cart'); ?>">
                            <?php echo CommonHelper::replaceStringData(Labels::getLabel('LBL_VIEW_SAVED_FOR_LATER_({ITEMS-COUNT})_ITEMS', $siteLangId), ['{ITEMS-COUNT}' => count($saveForLaterProducts)]); ?>
                        </a>
                    <?php } ?>
                </div>
            <?php } ?>
        </div>
    <?php } ?>
<?php } ?>