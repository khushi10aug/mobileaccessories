<?php defined('SYSTEM_INIT') or die('Invalid Usage.'); ?>
<div class="product-detail-gallery">
    <?php
    /* Get Ribbon */
    $data['product'] = $product;
    $data['productImagesArr'] = $productImagesArr;
    $data['imageGallery'] = true; ?>
    <div class="product-gallery" id="detail">
    <div class="badges-wrap">
        <?php $this->includeTemplate('_partial/product-type-ribbon.php', ['productType' => $product['product_type'], 'siteLangId' => $siteLangId], false);
        if (!empty($selProdRibbons)) {
            foreach ($selProdRibbons as $ribbRow) {
                $this->includeTemplate('_partial/ribbon-ui.php', ['ribbRow' => $ribbRow], false);
            }
        } ?>
    </div>
        <div class="product-images demo-gallery">
            <div class="main-img-slider" dir="<?php echo CommonHelper::getLayoutDirection(); ?>">
                <?php if ($productImagesArr) {
                    $galleryIndex = 0;
                    foreach ($productImagesArr as $afile_id => $image) {
                        $uploadedTime = AttachedFile::setTimeParam($image['afile_updated_at']);
                        $originalImgUrl = UrlHelper::getCachedUrl(UrlHelper::generateFileUrl('Image', 'product', array($product['product_id'], ImageDimension::VIEW_ORIGINAL, 0, $image['afile_id'])) . $uploadedTime, CONF_IMG_CACHE_TIME, '.jpg');
                        $lightboxImgUrl = UrlHelper::getCachedUrl(UrlHelper::generateFullFileUrl('Image', 'product', array($product['product_id'], ImageDimension::VIEW_LARGE, 0, $image['afile_id'])) . $uploadedTime, CONF_IMG_CACHE_TIME, '.jpg');
                        $pictureAttr = [
                            'webpImageUrl' => [
                                ImageDimension::VIEW_MOBILE => UrlHelper::getCachedUrl(UrlHelper::generateFileUrl('Image', 'product', array($product['product_id'], 'WEBP' . ImageDimension::VIEW_MOBILE, 0, $image['afile_id'])) . $uploadedTime, CONF_IMG_CACHE_TIME, '.webp'),
                                ImageDimension::VIEW_TABLET => UrlHelper::getCachedUrl(UrlHelper::generateFileUrl('Image', 'product', array($product['product_id'], 'WEBP' . ImageDimension::VIEW_SMALL, 0, $image['afile_id'])) . $uploadedTime, CONF_IMG_CACHE_TIME, '.webp'),
                                ImageDimension::VIEW_DESKTOP => UrlHelper::getCachedUrl(UrlHelper::generateFileUrl('Image', 'product', array($product['product_id'], 'WEBP' . ImageDimension::VIEW_MEDIUM, 0, $image['afile_id'])) . $uploadedTime, CONF_IMG_CACHE_TIME, '.webp'),
                            ],
                            'jpgImageUrl' => [
                                ImageDimension::VIEW_MOBILE => UrlHelper::getCachedUrl(UrlHelper::generateFileUrl('Image', 'product', array($product['product_id'], ImageDimension::VIEW_MOBILE, 0, $image['afile_id'])) . $uploadedTime, CONF_IMG_CACHE_TIME, '.jpg'),
                                ImageDimension::VIEW_TABLET => UrlHelper::getCachedUrl(UrlHelper::generateFileUrl('Image', 'product', array($product['product_id'], ImageDimension::VIEW_SMALL, 0, $image['afile_id'])) . $uploadedTime, CONF_IMG_CACHE_TIME, '.jpg'),
                                ImageDimension::VIEW_DESKTOP => UrlHelper::getCachedUrl(UrlHelper::generateFileUrl('Image', 'product', array($product['product_id'], ImageDimension::VIEW_MEDIUM, 0, $image['afile_id'])) . $uploadedTime, CONF_IMG_CACHE_TIME, '.jpg'),
                            ],
                            'imageUrl' => UrlHelper::getCachedUrl(UrlHelper::generateFileUrl('Image', 'product', array($product['product_id'], ImageDimension::VIEW_MEDIUM, 0, $image['afile_id'])) . $uploadedTime, CONF_IMG_CACHE_TIME, '.jpg'),
                            'dimensionType' => ImageDimension::TYPE_PRODUCTS,
                            'dimensionSize' => ImageDimension::VIEW_MEDIUM,
                            'sizeTypes' => [
                                ImageDimension::VIEW_MOBILE => ImageDimension::VIEW_MOBILE,
                                ImageDimension::VIEW_TABLET => ImageDimension::VIEW_SMALL,
                                ImageDimension::VIEW_DESKTOP => ImageDimension::VIEW_MEDIUM,
                            ],
                            'sizes' => '(max-width: 576px) 100vw, (max-width: 1199px) 50vw, 500px',
                            'lazyLoading' => (0 < $galleryIndex),
                            'fetchPriority' => (0 === $galleryIndex) ? 'high' : '',
                            'ratio' => '1:1',
                            'siteLangId' => $siteLangId,
                            'alt' => $image['afile_attribute_alt'],
                            'title' => $image['afile_attribute_title'],
                        ];
                ?>
                        <a data-fancybox="gallery-product-detail" href="<?php echo $lightboxImgUrl; ?>">
                            <?php $this->includeTemplate('_partial/picture-tag.php', $pictureAttr); ?>
                        </a>
                    <?php
                        $galleryIndex++;
                    }
                } else {
                    $mainImgUrl = UrlHelper::getCachedUrl(UrlHelper::generateFileUrl('Image', 'product', array(0, ImageDimension::VIEW_MEDIUM, 0)), CONF_IMG_CACHE_TIME, '.jpg');
                    $lightboxImgUrl = UrlHelper::getCachedUrl(UrlHelper::generateFileUrl('Image', 'product', array(0, ImageDimension::VIEW_LARGE, 0)), CONF_IMG_CACHE_TIME, '.jpg');
                    $pictureAttr = [
                        'webpImageUrl' => [
                            ImageDimension::VIEW_DESKTOP => UrlHelper::getCachedUrl(UrlHelper::generateFileUrl('Image', 'product', array(0, 'WEBP' . ImageDimension::VIEW_MEDIUM, 0)), CONF_IMG_CACHE_TIME, '.webp'),
                        ],
                        'jpgImageUrl' => [
                            ImageDimension::VIEW_DESKTOP => $mainImgUrl,
                        ],
                        'imageUrl' => $mainImgUrl,
                        'dimensionType' => ImageDimension::TYPE_PRODUCTS,
                        'dimensionSize' => ImageDimension::VIEW_MEDIUM,
                        'lazyLoading' => false,
                        'fetchPriority' => 'high',
                        'ratio' => '1:1',
                        'siteLangId' => $siteLangId,
                        'alt' => Labels::getLabel('LBL_DUMMY_IMAGE', $siteLangId),
                        'title' => Labels::getLabel('LBL_DUMMY_IMAGE', $siteLangId),
                    ];
                    ?>
                    <a data-fancybox="gallery" href="<?php echo $lightboxImgUrl; ?>">
                        <?php $this->includeTemplate('_partial/picture-tag.php', $pictureAttr); ?>
                    </a>
                <?php } ?>
            </div>
            <div class="thumb-nav" dir="<?php echo CommonHelper::getLayoutDirection(); ?>">
                <?php if ($productImagesArr) { ?>
                    <?php foreach ($productImagesArr as $afile_id => $image) {
                        $uploadedTime = AttachedFile::setTimeParam($image['afile_updated_at']);
                        $thumbImgUrl = UrlHelper::getCachedUrl(UrlHelper::generateFileUrl('Image', 'product', array($product['product_id'], ImageDimension::VIEW_THUMB, 0, $image['afile_id'])) . $uploadedTime, CONF_IMG_CACHE_TIME, '.jpg');
                        $thumbDims = ImageDimension::getData(ImageDimension::TYPE_PRODUCTS, ImageDimension::VIEW_THUMB);
                    ?>
                        <div class="thumb-nav-item">
                            <img loading="lazy" decoding="async" width="<?php echo $thumbDims[ImageDimension::WIDTH]; ?>" height="<?php echo $thumbDims[ImageDimension::HEIGHT]; ?>" title="<?php echo $image['afile_attribute_title']; ?>" alt="<?php echo $image['afile_attribute_alt']; ?>" src="<?php echo $thumbImgUrl; ?>" <?php echo HtmlHelper::getImgDimParm(ImageDimension::TYPE_PRODUCTS, ImageDimension::VIEW_THUMB); ?> />
                        </div>
                    <?php } ?>

                <?php } else { ?>
                    <?php $thumbDims = ImageDimension::getData(ImageDimension::TYPE_PRODUCTS, ImageDimension::VIEW_THUMB); ?>
                    <div class="thumb-nav-item">
                        <img loading="lazy" decoding="async" width="<?php echo $thumbDims[ImageDimension::WIDTH]; ?>" height="<?php echo $thumbDims[ImageDimension::HEIGHT]; ?>" title="<?php echo Labels::getLabel('LBL_DUMMY_IMAGE', $siteLangId); ?>" alt="<?php echo Labels::getLabel('LBL_DUMMY_IMAGE', $siteLangId); ?>" src="<?php echo UrlHelper::getCachedUrl(UrlHelper::generateFileUrl('Image', 'product', array(0, ImageDimension::VIEW_THUMB, 0)), CONF_IMG_CACHE_TIME, '.jpg'); ?>" <?php echo HtmlHelper::getImgDimParm(ImageDimension::TYPE_PRODUCTS, ImageDimension::VIEW_THUMB); ?> />
                    </div>
                <?php } ?>
            </div>
        </div>
    </div>
</div>
