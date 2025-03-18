<div class="products <?php echo (isset($layoutClass)) ? $layoutClass : ''; ?> <?php if ($product['selprod_stock'] <= 0) { ?> out-of-stock <?php } ?>">
    <div class="products-body">
        <?php if ($product['selprod_stock'] <= 0) { ?>
            <div class="out-of-stock-txt">
                <?php echo Labels::getLabel('LBL_SOLD_OUT', $siteLangId); ?>
            </div>
        <?php  } ?>
        <div class="badges-wrap">
            <?php $this->includeTemplate('_partial/product-type-ribbon.php', ['productType' => $product['product_type'], 'siteLangId' => $siteLangId], false);
            if (!empty($selProdRibbons)) {
                foreach ($selProdRibbons as $ribbRow) {
                    $this->includeTemplate('_partial/ribbon-ui.php', ['ribbRow' => $ribbRow], false);
                }
            } ?>
        </div>
        <?php if (true == $displayProductNotAvailableLable && array_key_exists('availableInLocation', $product) && 0 == $product['availableInLocation']) { ?>
            <div class="not-available">
                <svg class="svg">
                    <use xlink:href="<?php echo CONF_WEBROOT_URL; ?>images/retina/sprite.svg<?php echo AttachedFile::setTimeParam(RELEASE_DATE); ?>#linkedinfo">
                    </use>
                </svg>
                <?php echo Labels::getLabel('LBL_NOT_AVAILABLE', $siteLangId); ?>
            </div>
        <?php } ?>

        <?php $uploadedTime = AttachedFile::setTimeParam($product['product_updated_on']); ?>
        <div class="products-img">
            <a title="<?php echo $product['selprod_title']; ?>" href="<?php echo !isset($product['promotion_id']) ? UrlHelper::generateUrl('Products', 'View', array($product['selprod_id'])) : UrlHelper::generateUrl('Products', 'track', array($product['promotion_record_id'])); ?>">
                <?php
                $pictureAttr = [
                    'webpImageUrl' => [
                        ImageDimension::VIEW_DESKTOP => UrlHelper::getCachedUrl(UrlHelper::generateFileUrl('image', 'product', array($product['product_id'], (isset($prodImgSize) && isset($i) && ($i == 1)) ? $prodImgSize : "WEBP" . $productThumb, $product['selprod_id'], 0, $siteLangId), CONF_WEBROOT_FRONTEND) . $uploadedTime, CONF_IMG_CACHE_TIME, '.webp'),
                        ImageDimension::VIEW_TABLET => UrlHelper::getCachedUrl(UrlHelper::generateFileUrl('image', 'product', array($product['product_id'], "WEBP" . ImageDimension::VIEW_TABLET, $product['selprod_id'], 0, $siteLangId), CONF_WEBROOT_FRONTEND) . $uploadedTime, CONF_IMG_CACHE_TIME, '.webp'),
                        ImageDimension::VIEW_MOBILE => UrlHelper::getCachedUrl(UrlHelper::generateFileUrl('image', 'product', array($product['product_id'], "WEBP" . ImageDimension::VIEW_MOBILE, $product['selprod_id'], 0, $siteLangId), CONF_WEBROOT_FRONTEND) . $uploadedTime, CONF_IMG_CACHE_TIME, '.webp')
                    ],
                    'jpgImageUrl' => [
                        ImageDimension::VIEW_DESKTOP => UrlHelper::getCachedUrl(UrlHelper::generateFileUrl('image', 'product', array($product['product_id'], (isset($prodImgSize) && isset($i) && ($i == 1)) ? $prodImgSize : $productThumb, $product['selprod_id'], 0, $siteLangId), CONF_WEBROOT_FRONTEND) . $uploadedTime, CONF_IMG_CACHE_TIME, '.jpg'),
                        ImageDimension::VIEW_TABLET => UrlHelper::getCachedUrl(UrlHelper::generateFileUrl('image', 'product', array($product['product_id'], ImageDimension::VIEW_TABLET, $product['selprod_id'], 0, $siteLangId), CONF_WEBROOT_FRONTEND) . $uploadedTime, CONF_IMG_CACHE_TIME, '.jpg'),
                        ImageDimension::VIEW_MOBILE => UrlHelper::getCachedUrl(UrlHelper::generateFileUrl('image', 'product', array($product['product_id'], ImageDimension::VIEW_MOBILE, $product['selprod_id'], 0, $siteLangId), CONF_WEBROOT_FRONTEND) . $uploadedTime, CONF_IMG_CACHE_TIME, '.jpg')
                    ],
                    'ratio' => '1:1',
                    'alt' => $product['prodcat_name'],
                    'imageUrl' => UrlHelper::getCachedUrl(UrlHelper::generateFileUrl('image', 'product', array($product['product_id'], (isset($prodImgSize) && isset($i) && ($i == 1)) ? $prodImgSize : ImageDimension::VIEW_CLAYOUT1, $product['selprod_id'], 0, $siteLangId), CONF_WEBROOT_FRONTEND) . $uploadedTime, CONF_IMG_CACHE_TIME, '.jpg'),
                    'siteLangId' => $siteLangId,
                ];
                $this->includeTemplate('_partial/picture-tag.php', $pictureAttr);
                ?>
            </a>
        </div>
    </div>
    <?php $selprod_condition = true;  ?>
    <?php if (false === SellerProduct::isPriceHidden($product['selprod_hide_price'], $product['shop_rfq_enabled'])) { ?>
        <div class="products-foot">
            <?php require(CONF_THEME_PATH . '_partial/collection/product-price.php'); ?>
        </div>
    <?php } ?>
</div>