<?php defined('SYSTEM_INIT') or die('Invalid Usage.');
if (isset($productView) && true == $productView) { ?>
    <div class="product-sharing">
        <?php if (FatApp::getConfig('CONF_ADD_FAVORITES_TO_WISHLIST', FatUtility::VAR_INT, 1) == applicationConstants::NO) {
            $jsFunc = 0 < $product['ufp_id'] ? 'removeFromFavorite(' . $product['selprod_id'] . ')' : 'markAsFavorite(' . $product['selprod_id'] . ')';
            $isActive = ($product['ufp_id']) ? 'active' : '';
            $title = ($product['ufp_id']) ? Labels::getLabel('LBL_REMOVE_PRODUCT_FROM_FAVOURITE_LIST', $siteLangId) : Labels::getLabel('LBL_ADD_PRODUCT_TO_FAVOURITE_LIST', $siteLangId);
        } else {
            $jsFunc = 'viewWishList(' . $product['selprod_id'] . ', this, event)';
            $isActive = ($product['is_in_any_wishlist']) ? 'active' : '';
            $title = ($product['is_in_any_wishlist']) ? Labels::getLabel('LBL_REMOVE_PRODUCT_FROM_YOUR_WISHLIST', $siteLangId) : Labels::getLabel('LBL_ADD_PRODUCT_TO_YOUR_WISHLIST', $siteLangId);
        }
        ?>
        <button class="btn btn-outline-light btn-favorite <?php echo $isActive; ?>" type="button" data-id="<?php echo $product['selprod_id']; ?>" onclick="<?php echo $jsFunc; ?>" data-bs-toggle="tooltip" data-bs-placement="top" title="<?php echo $title; ?>">

            <svg class="svg" width="20" height="20">
                <use xlink:href="<?php echo CONF_WEBROOT_URL; ?>images/retina/sprite.svg<?php echo AttachedFile::setTimeParam(RELEASE_DATE); ?>#heart-filled">
                </use>
            </svg>
        </button>

        <ul class="via-social-sharing">
            <li class="via-social-sharing-item">
                <a href="javascript:void(0)" class="via-social-sharing-link st-custom-button" data-network="facebook" data-url="<?php echo UrlHelper::generateFullUrl('Products', 'view', array($product['selprod_id'])); ?>/">
                    <svg class="svg" width="20" height="20">
                        <use xlink:href="<?php echo CONF_WEBROOT_URL; ?>images/retina/sprite.svg<?php echo AttachedFile::setTimeParam(RELEASE_DATE); ?>#facebook">
                        </use>
                    </svg>
                </a>
            </li>
            <li class="via-social-sharing-item">
                <a href="javascript:void(0)" class="via-social-sharing-link st-custom-button" data-network="twitter">
                    <svg class="svg" width="20" height="20">
                        <use xlink:href="<?php echo CONF_WEBROOT_URL; ?>images/retina/sprite.svg<?php echo AttachedFile::setTimeParam(RELEASE_DATE); ?>#twitter">
                        </use>
                    </svg>
                </a>
            </li>
            <li class="via-social-sharing-item">
                <a href="javascript:void(0)" class="via-social-sharing-link st-custom-button" data-network="pinterest">
                    <svg class="svg" width="20" height="20">
                        <use xlink:href="<?php echo CONF_WEBROOT_URL; ?>images/retina/sprite.svg<?php echo AttachedFile::setTimeParam(RELEASE_DATE); ?>#pinterest">
                        </use>
                    </svg>
                </a>
            </li>
            <li class="via-social-sharing-item">
                <a href="javascript:void(0)" class="via-social-sharing-link st-custom-button" data-network="email">
                    <svg class="svg" width="20" height="20">
                        <use xlink:href="<?php echo CONF_WEBROOT_URL; ?>images/retina/sprite.svg<?php echo AttachedFile::setTimeParam(RELEASE_DATE); ?>#envelope">
                        </use>
                    </svg>
                </a>
            </li>
        </ul>
    </div>
<?php } ?>