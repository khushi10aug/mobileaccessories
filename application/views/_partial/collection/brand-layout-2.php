<?php if (isset($collection['brands']) && count($collection['brands']) > 0) { ?>
    <section class="section" data-section="section">
        <div class="container">
            <header class="section-head section-head-center1">
                <?php echo ($collection['collection_name'] != '') ? ' <div class="section-heading"><h2>' . $collection['collection_name'] . '</h2></div>' : ''; ?>
            </header>
            <div class="section-body">
                <div class="brand-layout-2">
                    <?php $i = 0;
                    foreach ($collection['brands'] as $brand) {
                        $fileData = AttachedFile::getAttachment(AttachedFile::FILETYPE_BRAND_IMAGE, $brand['brand_id'], 0, 0, false, ImageDimension::VIEW_MOBILE);
                        $uploadedTime = AttachedFile::setTimeParam($fileData['afile_updated_at']);
                        $aspectRatioArr = AttachedFile::getRatioTypeArray($siteLangId);
                        $ratio = "";
                        if (isset($fileData['afile_aspect_ratio']) && $fileData['afile_aspect_ratio'] > 0 && isset($aspectRatioArr[$fileData['afile_aspect_ratio']])) {
                            $ratio = $aspectRatioArr[$fileData['afile_aspect_ratio']];
                        }
                        $productId = $brand['product']['product_id'] ?? 0;
                        $selProdId = $brand['product']['selprod_id'] ?? 0;
                        $prodcatName = $brand['product']['prodcat_name'] ?? '';
                        $fileRow = CommonHelper::getImageAttributes(AttachedFile::FILETYPE_PRODUCT_IMAGE, $productId);
                        $productUrl = UrlHelper::generateUrl('Products', 'View', array($selProdId));

                        $pictureAttr = [
                            'webpImageUrl' => [ImageDimension::VIEW_DESKTOP => UrlHelper::getCachedUrl(UrlHelper::generateFileUrl('image', 'product', array($productId, (isset($prodImgSize) && isset($i) && ($i == 1)) ? $prodImgSize : "WEBP" . ImageDimension::VIEW_CLAYOUT2, $selProdId, 0, $siteLangId)) . $uploadedTime, CONF_IMG_CACHE_TIME, '.webp')],
                            'jpgImageUrl' => [ImageDimension::VIEW_DESKTOP => UrlHelper::getCachedUrl(UrlHelper::generateFileUrl('image', 'product', array($productId, (isset($prodImgSize) && isset($i) && ($i == 1)) ? $prodImgSize : ImageDimension::VIEW_CLAYOUT2, $selProdId, 0, $siteLangId)) . $uploadedTime, CONF_IMG_CACHE_TIME, '.jpg')],
                            'ratio' => '1:1',
                            'imageUrl' => UrlHelper::getCachedUrl(UrlHelper::generateFileUrl('image', 'product', array($productId, (isset($prodImgSize) && isset($i) && ($i == 1)) ? $prodImgSize : ImageDimension::VIEW_CLAYOUT2, $selProdId, 0, $siteLangId)) . $uploadedTime, CONF_IMG_CACHE_TIME, '.jpg'),
                            'alt' => (!empty($fileRow['afile_attribute_alt'])) ? $fileRow['afile_attribute_alt'] : $prodcatName,
                            'siteLangId' => $siteLangId,
                        ];
                    ?>
                        <div class="brand">
                            <a href="<?php echo $productUrl; ?>">
                                <div class="brand-thumb">
                                    <?php $this->includeTemplate('_partial/picture-tag.php', $pictureAttr); ?>
                                </div>
                            </a>
                            <a href="<?php echo UrlHelper::generateUrl('brands', 'View', array($brand['brand_id'])); ?>">
                                <div class="brand-logo">
                                    <img loading='lazy' data-ratio="<?php echo $ratio; ?>"
                                        src="<?php echo UrlHelper::getCachedUrl(UrlHelper::generateFileUrl('image', 'brand', array($brand['brand_id'], $siteLangId, ImageDimension::VIEW_MOBILE)) . $uploadedTime, CONF_IMG_CACHE_TIME, '.jpg'); ?>"
                                        alt="<?php echo (!empty($fileData['afile_attribute_alt'])) ? $fileData['afile_attribute_alt'] : $brand['brand_name']; ?>"
                                        title="<?php echo (!empty($fileData['afile_attribute_alt'])) ? $fileData['afile_attribute_alt'] : $brand['brand_name']; ?>">
                                </div>
                            </a>
                        </div>
                    <?php $i++;
                    } ?>
                </div>
            </div>
            <?php if ($collection['totBrands'] > Collections::LIMIT_BRAND_LAYOUT1) { ?>
                <div class="section-foot">
                    <a href="<?php echo UrlHelper::generateUrl('Collections', 'View', array($collection['collection_id'])); ?>"
                        class="link-underline">
                        <?php echo Labels::getLabel('LBL_VIEW_ALL', $siteLangId); ?></a>
                </div>
            <?php } ?>
        </div>
    </section>
<?php } ?>