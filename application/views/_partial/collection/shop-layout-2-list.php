<div class="shop-layout-2">
    <?php $i = 0;
    foreach ($collection['shops'] as $shop) {
        $uploadedTime = AttachedFile::setTimeParam($shop['shopData']['shop_updated_on']);

        $productId = $shop['shopData']['product']['product_id'] ?? 0;
        $selProdId = $shop['shopData']['product']['selprod_id'] ?? 0;
        $prodcatName = $shop['shopData']['product']['prodcat_name'] ?? '';
        $fileRow = CommonHelper::getImageAttributes(AttachedFile::FILETYPE_PRODUCT_IMAGE, $productId);
        $pictureAttr = [
            'webpImageUrl' => [ImageDimension::VIEW_DESKTOP => UrlHelper::getCachedUrl(UrlHelper::generateFileUrl('image', 'product', array($productId, (isset($prodImgSize) && isset($i) && ($i == 1)) ? $prodImgSize : "WEBP" . ImageDimension::VIEW_CLAYOUT2, $selProdId, 0, $siteLangId)) . $uploadedTime, CONF_IMG_CACHE_TIME, '.webp')],
            'jpgImageUrl' => [ImageDimension::VIEW_DESKTOP => UrlHelper::getCachedUrl(UrlHelper::generateFileUrl('image', 'product', array($productId, (isset($prodImgSize) && isset($i) && ($i == 1)) ? $prodImgSize : ImageDimension::VIEW_CLAYOUT2, $selProdId, 0, $siteLangId)) . $uploadedTime, CONF_IMG_CACHE_TIME, '.jpg')],
            'ratio' => '1:1',
            'imageUrl' => UrlHelper::getCachedUrl(UrlHelper::generateFileUrl('image', 'product', array($productId, (isset($prodImgSize) && isset($i) && ($i == 1)) ? $prodImgSize : ImageDimension::VIEW_CLAYOUT2, $selProdId, 0, $siteLangId)) . $uploadedTime, CONF_IMG_CACHE_TIME, '.jpg'),
            'alt' => (!empty($fileRow['afile_attribute_alt'])) ? $fileRow['afile_attribute_alt'] : $prodcatName,
            'siteLangId' => $siteLangId,
        ];
        $prodUrl = 0 < $selProdId ? UrlHelper::generateUrl('Products', 'View', array($selProdId)) : 'javascript:void(0);';
        ?>
        <div class="shop">
            <div class="shop-body">
                <a title="<?php echo $prodcatName; ?>" href="<?php echo $prodUrl; ?>">
                    <?php $this->includeTemplate('_partial/picture-tag.php', $pictureAttr); ?>
                </a>
            </div>
            <div class="shop-foot">
                <div class="shop-title">
                    <a
                        href="<?php echo (!isset($shop['shopData']['promotion_id']) ? UrlHelper::generateUrl('shops', 'view', array($shop['shopData']['shop_id'])) : UrlHelper::generateUrl('shops', 'track', array($shop['shopData']['promotion_record_id']))); ?>"><?php echo $shop['shopData']['shop_name']; ?>
                    </a>
                </div>
                <div class="shop-location">
                    <?php echo $shop['shopData']['state_name']; ?>    <?php echo ($shop['shopData']['country_name'] && $shop['shopData']['state_name']) ? ', ' : ''; ?>    <?php echo $shop['shopData']['country_name']; ?>
                </div>
                <?php if (round($collection['rating'][$shop['shopData']['shop_id']]) > 0) { ?>
                    <div class="product-ratings">
                        <svg class="svg svg-star" width="14" height="14">
                            <use xlink:href="<?php echo CONF_WEBROOT_URL; ?>images/retina/sprite.svg<?php echo AttachedFile::setTimeParam(RELEASE_DATE); ?>#star-yellow"></use>
                        </svg>
                        <span class="rate">
                            <?php echo round($collection['rating'][$shop['shopData']['shop_id']], 1); ?>
                        </span>
                    </div>
                <?php } ?>
                <div class="shop-action">
                    <a class="btn btn-outline-black btn-sm"
                        href="<?php echo (!isset($shop['shopData']['promotion_id']) ? UrlHelper::generateUrl('shops', 'view', array($shop['shopData']['shop_id'])) : UrlHelper::generateUrl('shops', 'track', array($shop['shopData']['promotion_record_id'], ))); ?>">
                        <?php echo Labels::getLabel('LBL_Shop_Now', $siteLangId); ?></a>
                </div>

            </div>
        </div>


        <?php $i++;
        isset($shop['shopData']['promotion_id']) ? Promotion::updateImpressionData($shop['shopData']['promotion_id']) : '';
        if ($i == Collections::LIMIT_SHOP_LAYOUT2)
            break;
    } ?>
</div>