<?php
$showActionBtns = !empty($showActionBtns) ? $showActionBtns : false;
$isWishList = isset($isWishList) ? $isWishList : 0;
$staticCollectionClass = '';
if ($controllerName = 'Products' && isset($action) && $action == 'view') {
    $staticCollectionClass = 'static-collection';
}
if (!isset($showAddToFavorite)) {
    $showAddToFavorite = true;
    if (UserAuthentication::isUserLogged() && (!User::isBuyer())) {
        $showAddToFavorite = false;
    }
}


if (!isset($isOutOfMinOrderQty)) {
    $tempHoldStock = Product::tempHoldStockCount($product['selprod_id']);
    $availableStock = $product['selprod_stock'] - $tempHoldStock;
    $isOutOfMinOrderQty = ((int) ($product['selprod_min_order_qty'] > $availableStock));
}

if ($showAddToFavorite) {
    /* Get Ribbon */?>
    <div class="badges-wrap">
        <?php $this->includeTemplate('_partial/product-type-ribbon.php', ['productType' => $product['product_type'], 'siteLangId' => $siteLangId], false);
        if ((!isset($includeRibbon) || true === $includeRibbon) && !empty($selProdRibbons)) {
            foreach ($selProdRibbons as $ribbRow) {
                $this->includeTemplate('_partial/ribbon-ui.php', ['ribbRow' => $ribbRow], false);
            }
        } ?>
    </div>
    <?php if (true ==  $showActionBtns) { ?>
        <ul class="actions actions-wishlist">
            <?php if ($product['in_stock'] &&  time() >= strtotime($product['selprod_available_from']) && 0 == $isOutOfMinOrderQty) { ?>
                <li>
                    <label class="checkbox">
                        <input type="checkbox" name='selprod_id[]' class="selectItem--js" value="<?php echo $product['selprod_id']; ?>" />
                    </label>
                </li>
                <?php if (false === SellerProduct::isPriceHidden($product['selprod_hide_price'], $product['shop_rfq_enabled']) && SellerProduct::CART_TYPE_RFQ_ONLY != $product['selprod_cart_type']) { ?>
                    <li>
                        <a onclick="addToCart( $(this), event , <?php echo $isWishList; ?>);" href="javascript:void(0)" class="" title="<?php echo Labels::getLabel('LBL_Move_to_cart', $siteLangId); ?>" data-id='<?php echo $product['selprod_id']; ?>'>
                            <svg class="svg" width="18" height="18">
                                <use xlink:href="<?php echo CONF_WEBROOT_URL; ?>images/retina/sprite-actions.svg<?php echo AttachedFile::setTimeParam(RELEASE_DATE); ?>#cart">
                                </use>
                            </svg>
                        </a>
                    </li>
                <?php } ?>
            <?php } ?>
            <li>
                <?php if (FatApp::getConfig('CONF_ADD_FAVORITES_TO_WISHLIST', FatUtility::VAR_INT, 1) == applicationConstants::YES) { ?>
                    <a title='<?php echo Labels::getLabel('LBL_Move_to_trash', $siteLangId); ?>' onclick="removeFromWishlist(<?php echo $product['selprod_id']; ?>, <?php echo $product['uwlp_uwlist_id']; ?>, event);" href="javascript:void(0)" class="">
                        <svg class="svg" width="18" height="18">
                            <use xlink:href="<?php echo CONF_WEBROOT_URL; ?>images/retina/sprite-actions.svg<?php echo AttachedFile::setTimeParam(RELEASE_DATE); ?>#delete">
                            </use>
                        </svg>
                    </a>
                <?php } else { ?>
                    <a title='<?php echo Labels::getLabel('LBL_Move_to_trash', $siteLangId); ?>' href="javascript:void(0)" onclick="removeFromFavorite(<?php echo $product['selprod_id']; ?>, 'searchFavouriteListItems');" data-id="<?php echo $product['selprod_id']; ?>">
                        <svg class="svg" width="18" height="18">
                            <use xlink:href="<?php echo CONF_WEBROOT_URL; ?>images/retina/sprite-actions.svg<?php echo AttachedFile::setTimeParam(RELEASE_DATE); ?>#delete">
                            </use>
                        </svg>
                    </a>
                <?php } ?>
            </li>
        </ul>
        <?php
    }

    if (isset($productView) && true == $productView) {
        if (false == $showActionBtns) {
            if (FatApp::getConfig('CONF_ADD_FAVORITES_TO_WISHLIST', FatUtility::VAR_INT, 1) == applicationConstants::NO) {
                $jsFunc = 0 < $product['ufp_id'] ? 'removeFromFavorite(' . $product['selprod_id'] . ')' : 'markAsFavorite(' . $product['selprod_id'] . ')';
                ?>
<div class="favourite heart-wrapper <?php echo ($product['ufp_id']) ? 'is-active' : ''; ?>"
    onclick="<?php echo $jsFunc; ?>" data-id="<?php echo $product['selprod_id']; ?>">
    <a href="javascript:void(0)"
        title="<?php echo ($product['ufp_id']) ? Labels::getLabel('LBL_Remove_product_from_favourite_list', $siteLangId) : Labels::getLabel('LBL_Add_Product_to_favourite_list', $siteLangId); ?>">

    </a>
</div>
<?php } else { ?>
<div class="favourite heart-wrapper wishListLink-Js <?php echo ($product['is_in_any_wishlist']) ? 'is-active' : ''; ?>"
    data-id="<?php echo $product['selprod_id']; ?>">
    <a href="javascript:void(0)" onclick="viewWishList(<?php echo $product['selprod_id']; ?>,this,event);"
        title="<?php echo ($product['is_in_any_wishlist']) ? Labels::getLabel('LBL_Remove_product_from_your_wishlist', $siteLangId) : Labels::getLabel('LBL_Add_Product_to_your_wishlist', $siteLangId); ?>">

    </a>
</div>
<?php }
        } ?>
<div class="dropdown">
    <a class="no-after share-icon" data-display="static" href="javascript:void(0)" data-bs-toggle="dropdown"
        data-bs-auto-close="outside">
        <i class="icn">
            <svg class="svg">
                <use xlink:href="<?php echo CONF_WEBROOT_URL; ?>images/retina/sprite.svg<?php echo AttachedFile::setTimeParam(RELEASE_DATE); ?>#share">
                </use>
            </svg>
        </i>
    </a>
    <div class="dropdown-menu dropdown-menu-anim">
        <ul class="social-sharing">
            <li class="social-facebook">
                <a href="javascript:void(0)" class="st-custom-button" data-network="facebook"
                    data-url="<?php echo UrlHelper::generateFullUrl('Products', 'view', array($product['selprod_id']), CONF_WEBROOT_FRONTEND); ?>/">
                    <i class="icn">
                        <svg class="svg">
                            <use xlink:href="<?php echo CONF_WEBROOT_URL; ?>images/retina/sprite.svg<?php echo AttachedFile::setTimeParam(RELEASE_DATE); ?>#fb"></use>
                        </svg>
                    </i>
                </a>
            </li>
            <li class="social-twitter">
                <a href="javascript:void(0)" class="st-custom-button" data-network="twitter">
                    <i class="icn">
                        <svg class="svg">
                            <use xlink:href="<?php echo CONF_WEBROOT_URL; ?>images/retina/sprite.svg<?php echo AttachedFile::setTimeParam(RELEASE_DATE); ?>#tw"></use>
                        </svg>
                    </i>
                </a>
            </li>
            <li class="social-pintrest">
                <a href="javascript:void(0)" class="st-custom-button" data-network="pinterest">
                    <i class="icn">
                        <svg class="svg">
                            <use xlink:href="<?php echo CONF_WEBROOT_URL; ?>images/retina/sprite.svg<?php echo AttachedFile::setTimeParam(RELEASE_DATE); ?>#pt"></use>
                        </svg>
                    </i>
                </a>
            </li>
            <li class="social-email">
                <a href="javascript:void(0)" class="st-custom-button" data-network="email">
                    <i class="icn">
                        <svg class="svg">
                            <use xlink:href="<?php echo CONF_WEBROOT_URL; ?>images/retina/sprite.svg<?php echo AttachedFile::setTimeParam(RELEASE_DATE); ?>#envelope">
                            </use>
                        </svg>
                    </i>
                </a>
            </li>
        </ul>
    </div>
</div>
<?php }
}