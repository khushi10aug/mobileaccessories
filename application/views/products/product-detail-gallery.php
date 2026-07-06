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
                    foreach ($productImagesArr as $afile_id => $image) {
                        $uploadedTime = AttachedFile::setTimeParam($image['afile_updated_at']);
                        $originalImgUrl = UrlHelper::getCachedUrl(UrlHelper::generateFileUrl('Image', 'product', array($product['product_id'], ImageDimension::VIEW_ORIGINAL, 0, $image['afile_id'])) . $uploadedTime, CONF_IMG_CACHE_TIME, '.jpg');
                        $mainImgUrl = UrlHelper::getCachedUrl(UrlHelper::generateFullFileUrl('Image', 'product', array($product['product_id'], ImageDimension::VIEW_LARGE, 0, $image['afile_id'])) . $uploadedTime, CONF_IMG_CACHE_TIME, '.jpg');
                        $thumbImgUrl = UrlHelper::getCachedUrl(UrlHelper::generateFileUrl('Image', 'product', array($product['product_id'], ImageDimension::VIEW_THUMB, 0, $image['afile_id'])) . $uploadedTime, CONF_IMG_CACHE_TIME, '.jpg');
                ?>
                        <a data-fancybox="gallery-product-detail" href="<?php echo $mainImgUrl; ?>">
                            <img loading="lazy" class="img-fluid" title="<?php echo $image['afile_attribute_title']; ?>" alt="<?php echo $image['afile_attribute_alt']; ?>" src="<?php echo $mainImgUrl; ?>" data-xoriginal="<?php echo $originalImgUrl; ?>" <?php echo HtmlHelper::getImgDimParm(ImageDimension::TYPE_PRODUCTS, ImageDimension::VIEW_ORIGINAL); ?>>
                        </a>
                    <?php
                    }
                } else {
                    $mainImgUrl = UrlHelper::getCachedUrl(UrlHelper::generateFileUrl('Image', 'product', array(0, ImageDimension::VIEW_LARGE, 0)), CONF_IMG_CACHE_TIME, '.jpg');
                    $originalImgUrl = UrlHelper::getCachedUrl(UrlHelper::generateFileUrl('Image', 'product', array(0, ImageDimension::VIEW_ORIGINAL, 0)), CONF_IMG_CACHE_TIME, '.jpg');
                    /* $mainWebpImgUrl = UrlHelper::getCachedUrl(UrlHelper::generateFileUrl('Image', 'product', array(0, 'WEBP' . ImageDimension::VIEW_MEDIUM, 0)), CONF_IMG_CACHE_TIME, '.webp'); */
                    ?>
                    <a data-fancybox="gallery" href="<?php echo $mainImgUrl; ?>">
                        <img loading="lazy" class="img-fluid" title="<?php echo Labels::getLabel('LBL_DUMMY_IMAGE', $siteLangId); ?>" alt="<?php echo Labels::getLabel('LBL_DUMMY_IMAGE', $siteLangId); ?>" src="<?php echo $mainImgUrl; ?>" data-xoriginal="<?php echo $originalImgUrl; ?>" <?php echo HtmlHelper::getImgDimParm(ImageDimension::TYPE_PRODUCTS, ImageDimension::VIEW_MEDIUM); ?>>
                    </a>
                <?php } ?>
            </div>
            <div class="thumb-nav" dir="<?php echo CommonHelper::getLayoutDirection(); ?>">
                <?php if ($productImagesArr) { ?>
                    <?php foreach ($productImagesArr as $afile_id => $image) {
                        $uploadedTime = AttachedFile::setTimeParam($image['afile_updated_at']);
                        $mainImgUrl = UrlHelper::getCachedUrl(UrlHelper::generateFileUrl('Image', 'product', array($product['product_id'], ImageDimension::VIEW_THUMB, 0, $image['afile_id'])) . $uploadedTime, CONF_IMG_CACHE_TIME, '.jpg');
                        /* $mainWebpImgUrl = UrlHelper::getCachedUrl(UrlHelper::generateFileUrl('Image', 'product', array($product['product_id'], 'WEBP' . ImageDimension::VIEW_MEDIUM, 0, $image['afile_id'])) . $uploadedTime, CONF_IMG_CACHE_TIME, '.webp');  */ ?>
                        <div class="thumb-nav-item">
                            <img width="110" height="110" title="<?php echo $image['afile_attribute_title']; ?>" alt="<?php echo $image['afile_attribute_alt']; ?>" src="<?php echo $mainImgUrl; ?>" <?php echo HtmlHelper::getImgDimParm(ImageDimension::TYPE_PRODUCTS, ImageDimension::VIEW_THUMB); ?> />
                        </div>
                    <?php } ?>

                <?php } else { ?>
                    <div class="thumb-nav-item">
                        <img width="110" height="110" title="<?php echo Labels::getLabel('LBL_DUMMY_IMAGE', $siteLangId); ?>" alt="<?php echo Labels::getLabel('LBL_DUMMY_IMAGE', $siteLangId); ?>" src="<?php echo UrlHelper::getCachedUrl(UrlHelper::generateFileUrl('Image', 'product', array(0, ImageDimension::VIEW_THUMB, 0)), CONF_IMG_CACHE_TIME, '.jpg'); ?>" <?php echo HtmlHelper::getImgDimParm(ImageDimension::TYPE_PRODUCTS, ImageDimension::VIEW_THUMB); ?> />
                    </div>
                <?php } ?>
            </div>
        </div>
    </div>
</div>