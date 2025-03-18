<?php
if (isset($collection['shops']) && count($collection['shops'])) {
    ?>
    <section class="section" data-section="section">
        <div class="container">
            <header class="section-head">
                <div class="section-heading">
                    <h2><?php echo ($collection['collection_name'] != '') ? $collection['collection_name'] : ''; ?></h2>
                </div>
            </header>
            <div class="section-body">
                <div class="recommended-layout">
                    <?php foreach ($collection['shops'] as $shop) {
                        $uploadedTime = AttachedFile::setTimeParam($shop['shopData']['shop_updated_on']);
                        $productId = $shop['shopData']['product']['product_id'] ?? 0;
                        $selProdId = $shop['shopData']['product']['selprod_id'] ?? 0;
                        $prodcatName = $shop['shopData']['product']['prodcat_name'] ?? '';
                        ?>
                        <div class="recommended-layout-item">
                            <div class="recommended-layout-head">
                                <div class="shop-profile">
                                    <div class="shop-profile-thumbnail">
                                        <?php
                                        $pictureAttr = [
                                            'webpImageUrl' => [ImageDimension::VIEW_DESKTOP => UrlHelper::getCachedUrl(UrlHelper::generateFileUrl('image', 'shopLogo', array($shop['shopData']['shop_id'], $siteLangId, "WEBP" . ImageDimension::VIEW_THUMB, 0, false), CONF_WEBROOT_URL) . $uploadedTime, CONF_IMG_CACHE_TIME, '.webp')],
                                            'jpgImageUrl' => [ImageDimension::VIEW_DESKTOP => UrlHelper::getCachedUrl(UrlHelper::generateFileUrl('image', 'shopLogo', array($shop['shopData']['shop_id'], $siteLangId, ImageDimension::VIEW_THUMB, 0, false), CONF_WEBROOT_URL) . $uploadedTime, CONF_IMG_CACHE_TIME, '.jpg')],
                                            'alt' => $shop['shopData']['shop_name'],
                                            'imageUrl' => UrlHelper::getCachedUrl(UrlHelper::generateFileUrl('image', 'shopLogo', array($shop['shopData']['shop_id'], $siteLangId, ImageDimension::VIEW_THUMB, 0, false), CONF_WEBROOT_URL) . $uploadedTime, CONF_IMG_CACHE_TIME, '.jpg'),
                                            'siteLangId' => $siteLangId
                                        ]; ?>
                                        <a href="<?php echo UrlHelper::generateUrl('Shops', 'view', [$shop['shopData']['shop_id']]);?>"><?php $this->includeTemplate('_partial/picture-tag.php', $pictureAttr); ?></a>                                        
                                    </div>
                                    <div class="shop-profile-data">
                                        <?php if (round($shop['shopData']['shop_avg_rating']) > 0) { ?>
                                            <div class="product-ratings">
                                                <svg class="svg svg-star" width="14" height="14">
                                                    <use xlink:href="/images/retina/sprite.svg<?php echo AttachedFile::setTimeParam(RELEASE_DATE); ?>#star-yellow"></use>
                                                </svg>
                                                <span class="rate">
                                                    <?php echo round($shop['shopData']['shop_avg_rating']); ?>
                                                    <?php echo Labels::getLabel('LBL_OUT_OF'); ?> 5 - <a class="link-underline"
                                                        href="/akshays-e-store-reviews"><?php echo $shop['shopData']['shop_total_reviews']; ?>
                                                        <?php echo Labels::getLabel('LBL_REVIEWS'); ?></a>
                                                </span>
                                            </div>
                                        <?php } ?>
                                        <h3 class="title"><?php echo $shop['shopData']['shop_name']; ?></h3>
                                        <p class="shop-address"> <?php echo $shop['shopData']['state_name']; ?>
                                            <?php echo ($shop['shopData']['country_name'] && $shop['shopData']['state_name']) ? ', ' : ''; ?>
                                            <?php echo $shop['shopData']['country_name']; ?>
                                        </p>
                                    </div>
                                </div>
                            </div>
                            <div class="recommended-layout-body">
                                <?php
                                $uploadedTime = AttachedFile::setTimeParam($shop['shopData']['shop_updated_on']);
                                foreach ($shop['shopData']['product'] as $product) {
                                    $fileRow = CommonHelper::getImageAttributes(AttachedFile::FILETYPE_PRODUCT_IMAGE, $product['product_id']);
                                    $productId = $product['product_id'] ?? 0;
                                    $selProdId = $product['selprod_id'] ?? 0;
                                    $pictureAttr = [
                                        'webpImageUrl' => [ImageDimension::VIEW_DESKTOP => UrlHelper::getCachedUrl(UrlHelper::generateFileUrl('image', 'product', array($productId, (isset($prodImgSize) && isset($i) && ($i == 1)) ? $prodImgSize : "WEBP" . ImageDimension::VIEW_MOBILE, $selProdId, 0, $siteLangId)) . $uploadedTime, CONF_IMG_CACHE_TIME, '.webp')],
                                        'jpgImageUrl' => [ImageDimension::VIEW_DESKTOP => UrlHelper::getCachedUrl(UrlHelper::generateFileUrl('image', 'product', array($productId, (isset($prodImgSize) && isset($i) && ($i == 1)) ? $prodImgSize : ImageDimension::VIEW_MOBILE, $selProdId, 0, $siteLangId)) . $uploadedTime, CONF_IMG_CACHE_TIME, '.jpg')],
                                        'ratio' => '1:1',
                                        'imageUrl' => UrlHelper::getCachedUrl(UrlHelper::generateFileUrl('image', 'product', array($productId, (isset($prodImgSize) && isset($i) && ($i == 1)) ? $prodImgSize : ImageDimension::VIEW_MOBILE, $selProdId, 0, $siteLangId)) . $uploadedTime, CONF_IMG_CACHE_TIME, '.jpg'),
                                        'alt' => (!empty($fileRow['afile_attribute_alt'])) ? $fileRow['afile_attribute_alt'] : $product['product_name'],
                                        'siteLangId' => $siteLangId,
                                    ];
                                    $prodUrl = 0 < $selProdId ? UrlHelper::generateUrl('Products', 'View', array($selProdId)) : 'javascript:void(0);';

                                    ?>
                                    <div class="products-img">
                                        <a title="<?php echo $prodcatName; ?>" href="<?php echo $prodUrl; ?>">
                                            <?php $this->includeTemplate('_partial/picture-tag.php', $pictureAttr); ?>
                                        </a>
                                    </div>
                                <?php } ?>
                            </div>
                        </div>
                    <?php } ?>
                </div>
            </div>
        </div>
    </section>
<?php }
/* ] */