<?php $collection['collection_primary_records'] = $collection['collection_primary_records'] ?? 4; ?>
<div class="shop-layout-1"
    data-view="<?php echo $collection['collection_primary_records'] ?>">
    <?php $i = 0;
    foreach ($collection['shops'] as $shop) {
        $uploadedTime = AttachedFile::setTimeParam($shop['shopData']['shop_updated_on']); ?>
        <div class="shop">
            <div class="shop-head">
                <a href="<?php echo (!isset($shop['shopData']['promotion_id']) ? UrlHelper::generateUrl('shops', 'view', array($shop['shopData']['shop_id'])) : UrlHelper::generateUrl('shops', 'track', array($shop['shopData']['promotion_record_id']))); ?>"
                    class="shop-logo">
                    <?php
                    $pictureAttr = [
                        'webpImageUrl' => [ImageDimension::VIEW_DESKTOP => UrlHelper::getCachedUrl(UrlHelper::generateFileUrl('image', 'shopLogo', array($shop['shopData']['shop_id'], $siteLangId, "WEBP" . ImageDimension::VIEW_THUMB, 0, false), CONF_WEBROOT_URL) . $uploadedTime, CONF_IMG_CACHE_TIME, '.webp')],
                        'jpgImageUrl' => [ImageDimension::VIEW_DESKTOP => UrlHelper::getCachedUrl(UrlHelper::generateFileUrl('image', 'shopLogo', array($shop['shopData']['shop_id'], $siteLangId, ImageDimension::VIEW_THUMB, 0, false), CONF_WEBROOT_URL) . $uploadedTime, CONF_IMG_CACHE_TIME, '.jpg')],
                        'alt' => $shop['shopData']['shop_name'],
                        'imageUrl' => UrlHelper::getCachedUrl(UrlHelper::generateFileUrl('image', 'shopLogo', array($shop['shopData']['shop_id'], $siteLangId, ImageDimension::VIEW_THUMB, 0, false), CONF_WEBROOT_URL) . $uploadedTime, CONF_IMG_CACHE_TIME, '.jpg'),
                        'siteLangId' => $siteLangId,
                    ];

                    $this->includeTemplate('_partial/picture-tag.php', $pictureAttr);
                    ?>
                </a>

            </div>
            <div class="shop-body">
                <div class="shop-title"><a
                        href="<?php echo (!isset($shop['shopData']['promotion_id']) ? UrlHelper::generateUrl('shops', 'view', array($shop['shopData']['shop_id'])) : UrlHelper::generateUrl('shops', 'track', array($shop['shopData']['promotion_record_id']))); ?>"><?php echo $shop['shopData']['shop_name']; ?></a>
                </div>
                <div class="shop-location">
                    <?php echo $shop['shopData']['state_name']; ?>
                    <?php echo ($shop['shopData']['country_name'] && $shop['shopData']['state_name']) ? ', ' : ''; ?>
                    <?php echo $shop['shopData']['country_name']; ?>
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
            </div>
            <!-- Shop Badge  -->
            <?php
            $badgesArr = Badge::getShopBadges($siteLangId, [$shop['shopData']['shop_id']]);
            $this->includeTemplate('_partial/badge-ui.php', ['badgesArr' => $badgesArr, 'siteLangId' => $siteLangId], false); ?>
            <div class="shop-foot">
                <a class="btn btn-outline-black btn-sm"
                    href="<?php echo (!isset($shop['shopData']['promotion_id']) ? UrlHelper::generateUrl('shops', 'view', array($shop['shopData']['shop_id'])) : UrlHelper::generateUrl('shops', 'track', array($shop['shopData']['promotion_record_id']))); ?>">
                    <?php echo Labels::getLabel('LBL_Shop_Now', $siteLangId); ?></a>
            </div>

        </div>

    <?php $i++;
        isset($shop['shopData']['promotion_id']) ? Promotion::updateImpressionData($shop['shopData']['promotion_id']) : '';
        // if ($i == Collections::LIMIT_SHOP_LAYOUT1) break;
    } ?>
</div>