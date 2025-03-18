<?php
$showActionBtns = !empty($showActionBtns) ? $showActionBtns : false;
$isWishList = isset($isWishList) ? $isWishList : 0;
$staticCollectionClass = '';
if ($controllerName = 'Products' && isset($action) && $action == 'view') {
    $staticCollectionClass = 'static--collection';
}
if (!isset($showAddToFavorite)) {
    $showAddToFavorite = true;
    if (UserAuthentication::isUserLogged() && (!User::isBuyer())) {
        $showAddToFavorite = false;
    }
}

$canEditOrder = $canEditOrder ?? true;

if ($showAddToFavorite) {
    /* Get Ribbon */?>
    <div class="badges-wrap">
        <?php $this->includeTemplate('_partial/product-type-ribbon.php', ['productType' => $product['product_type'], 'siteLangId' => $siteLangId], false);
        if ((!isset($includeRibbon) || true === $includeRibbon) && !empty($selProdRibbons)) {
            foreach ($selProdRibbons as $ribbRow) {
                $this->includeTemplate('_partial/ribbon-ui.php', ['ribbRow' => $ribbRow], false);
            }
        }
        ?>
    </div>

<div class="favourite-wrapper">
    <?php if (true == $showActionBtns) { ?>
    <div class="actions_wishlist">
        <ul class="actions">
            <?php if ($product['in_stock'] && time() >= strtotime($product['selprod_available_from'])) { ?>
            <li>
                <label class="checkbox">
                    <input type="checkbox" name='selprod_id[]' class="selectItem--js"
                        value="<?php echo $product['selprod_id']; ?>" />

                </label>
            </li>
            <?php if ($canEditOrder && false === SellerProduct::isPriceHidden($product['selprod_hide_price'], $product['shop_rfq_enabled']) && SellerProduct::CART_TYPE_RFQ_ONLY != $product['selprod_cart_type']) { ?>
            <li>
                <a onClick="addToCart( $(this), event , <?php echo $isWishList; ?>);" href="javascript:void(0)" class=""
                    title="<?php echo Labels::getLabel('LBL_Move_to_cart', $siteLangId); ?>"
                    data-id='<?php echo $product['selprod_id']; ?>'>
                    <svg class="svg" width="18" height="18">
                        <use xlink:href=" <?php echo CONF_WEBROOT_FRONTEND; ?>images/retina/sprite.svg<?php echo AttachedFile::setTimeParam(RELEASE_DATE); ?>#shopping-cart">
                        </use>
                    </svg>
                </a>
            </li>
            <?php } ?>
            <?php } ?>
            <li>
                <?php if (FatApp::getConfig('CONF_ADD_FAVORITES_TO_WISHLIST', FatUtility::VAR_INT, 1) == applicationConstants::YES) { ?>
                <a title='<?php echo Labels::getLabel('LBL_Move_to_trash', $siteLangId); ?>'
                    onclick="removeFromWishlist(<?php echo $product['selprod_id']; ?>, <?php echo $product['uwlp_uwlist_id']; ?>, event);"
                    href="javascript:void(0)" class="">
                    <svg class="svg" width="20" height="20">
                        <use xlink:href="<?php echo CONF_WEBROOT_URL; ?>images/retina/sprite.svg<?php echo AttachedFile::setTimeParam(RELEASE_DATE); ?>#delete">
                        </use>
                    </svg>
                </a>
                <?php } else { ?>
                <a title='<?php echo Labels::getLabel('LBL_Move_to_trash', $siteLangId); ?>' href="javascript:void(0)"
                    onclick="removeFromFavorite(<?php echo $product['selprod_id']; ?>, 'searchFavouriteListItems');"
                    data-id="<?php echo $product['selprod_id']; ?>">
                    <svg class="svg" width="20" height="20">
                        <use xlink:href="<?php echo CONF_WEBROOT_URL; ?>images/retina/sprite.svg<?php echo AttachedFile::setTimeParam(RELEASE_DATE); ?>#delete">
                        </use>
                    </svg>
                </a>
                <?php } ?>
            </li>
        </ul>
    </div>
    <?php } ?>
    <ul class="actions">
        <?php if (isset($productView) && true == $productView) {
                if (false == $showActionBtns) { ?>
        <li>
            <?php if (FatApp::getConfig('CONF_ADD_FAVORITES_TO_WISHLIST', FatUtility::VAR_INT, 1) == applicationConstants::NO) {
                            $jsFunc = 0 < $product['ufp_id'] ? 'removeFromFavorite(' . $product['selprod_id'] . ')' : 'markAsFavorite(' . $product['selprod_id'] . ')';
                            ?>
            <a class="favourite <?php echo ($product['ufp_id']) ? 'is-active' : ''; ?>" onclick="<?php echo $jsFunc; ?>"
                data-id="<?php echo $product['selprod_id']; ?>" href="javascript:void(0)"
                title="<?php echo ($product['ufp_id']) ? Labels::getLabel('LBL_Remove_product_from_favourite_list', $siteLangId) : Labels::getLabel('LBL_Add_Product_to_favourite_list', $siteLangId); ?>">

                <svg class="svg" width="16" height="16">
                    <use xlink:href="<?php echo CONF_WEBROOT_URL; ?>images/retina/sprite.svg<?php echo AttachedFile::setTimeParam(RELEASE_DATE); ?>#heart">
                    </use>
                </svg>

            </a>
            <?php } else { ?>
            <a class="favourite wishListLink-Js <?php echo ($product['is_in_any_wishlist']) ? 'is-active' : ''; ?>"
                data-id="<?php echo $product['selprod_id']; ?>" href="javascript:void(0)"
                onClick="viewWishList(<?php echo $product['selprod_id']; ?>,this,event);"
                title="<?php echo ($product['is_in_any_wishlist']) ? Labels::getLabel('LBL_Remove_product_from_your_wishlist', $siteLangId) : Labels::getLabel('LBL_Add_Product_to_your_wishlist', $siteLangId); ?>">

                <svg class="svg" width="16" height="16">
                    <use xlink:href="<?php echo CONF_WEBROOT_URL; ?>images/retina/sprite.svg<?php echo AttachedFile::setTimeParam(RELEASE_DATE); ?>#heart">
                    </use>
                </svg>

            </a>
            <?php } ?>
        </li>
        <?php } ?>
        <li>
            <a class="" href="javascript:void(0)" data-bs-toggle="modal" data-bs-target="#shareIcon">

                <svg class="svg" width="16" height="16">
                    <use xlink:href="<?php echo CONF_WEBROOT_URL; ?>images/retina/sprite.svg<?php echo AttachedFile::setTimeParam(RELEASE_DATE); ?>#share">
                    </use>
                </svg>

            </a>
        </li>
    </ul>
    <div class="modal fade" id="shareIcon" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header border-bottom-0">
                    <h5 class="modal-title"></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">
                    </button>
                </div>
                <div class="modal-body">
                    <div class="share-wrap">
                        <h6><?php echo Labels::getLabel('Lbl_Share_this_via', $siteLangId); ?></h6>
                        <ul class="social-sharing">
                            <li class="social-facebook">
                                <a href="javascript:void(0)" class="st-custom-button" data-network="facebook"
                                    data-url="<?php echo UrlHelper::generateFullUrl('Products', 'view', array($product['selprod_id'])); ?>/">
                                    <svg class="svg">
                                        <use
                                            xlink:href="<?php echo CONF_WEBROOT_URL; ?>images/retina/sprite.svg<?php echo AttachedFile::setTimeParam(RELEASE_DATE); ?>#facebook">
                                        </use>
                                    </svg>

                                </a>
                            </li>
                            <li class="social-twitter">
                                <a href="javascript:void(0)" class="st-custom-button" data-network="twitter">

                                    <svg class="svg">
                                        <use
                                            xlink:href="<?php echo CONF_WEBROOT_URL; ?>images/retina/sprite.svg<?php echo AttachedFile::setTimeParam(RELEASE_DATE); ?>#twitter">
                                        </use>
                                    </svg>
                                </a>
                            </li>
                            <li class="social-pintrest">
                                <a href="javascript:void(0)" class="st-custom-button" data-network="pinterest">
                                    <svg class="svg">
                                        <use
                                            xlink:href="<?php echo CONF_WEBROOT_URL; ?>images/retina/sprite.svg<?php echo AttachedFile::setTimeParam(RELEASE_DATE); ?>#pinterest">
                                        </use>
                                    </svg>
                                </a>
                            </li>
                            <li class="social-email">
                                <a href="javascript:void(0)" class="st-custom-button" data-network="email">
                                    <svg class="svg">
                                        <use
                                            xlink:href="<?php echo CONF_WEBROOT_URL; ?>images/retina/sprite.svg<?php echo AttachedFile::setTimeParam(RELEASE_DATE); ?>#envelope">
                                        </use>
                                    </svg>
                                </a>
                            </li>
                        </ul>
                        <div class="gap"></div>
                        <h6><?php echo Labels::getLabel('MSG_OR_COPY_LINK', $siteLangId); ?></h6>
                        <div class="clipboard">
                            <span
                                class="copy-input clipboardTextJs"><?php echo UrlHelper::generateFullUrl('products', 'view', array($product['selprod_id']), CONF_WEBROOT_FRONT_URL) ?></span>
                            <button class="copy-btn clipboardTextJs" type="button" onclick="copyText(this, true)"
                                data-bs-toggle="tooltip" data-placement="top"
                                title="<?php echo Labels::getLabel('MSG_COPY_TO_CLIPBOARD', $siteLangId); ?>">
                                <svg class="svg" width="18" height="18">
                                    <use
                                        xlink:href="<?php echo CONF_WEBROOT_FRONTEND; ?>images/retina/sprite.svg<?php echo AttachedFile::setTimeParam(RELEASE_DATE); ?>#copy-to-all">
                                    </use>
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php } ?>
</div>
<?php }
?>

<script>
$(function() {
    $('#shareIcon').insertAfter("#body");
});
</script>