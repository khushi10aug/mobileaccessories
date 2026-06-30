<?php defined('SYSTEM_INIT') or die('Invalid Usage.');
if (isset($collection['categories']) && count($collection['categories'])) { ?>
    <section class="section bg-beige" data-section="section">
        <div class="container">
            <header class="section-head">
                <div class="section-heading">
                    <h2><?php echo $collection['collection_name']; ?></h2>
                </div>
            </header>
            <div class="section-body">
                <div class="category-layout-4">
                    <?php foreach ($collection['categories'] as $key => $category) { ?>
                        <div class="category-layout-4-item">
                            <h3 class="category-layout-4-head" title="<?php echo $category['catData']['prodcat_name'] ?? ''; ?>"><?php echo $category['catData']['prodcat_name'] ?? ''; ?></h3>
                            <div class="category-layout-4-body">
                                <?php
                                $tRightRibbons = $category['tRightRibbons'];
                                $rCount = 1;
                                foreach ($category['products'] as $key => $product) {
                                    $selProdRibbons = [];
                                    if (array_key_exists($product['selprod_id'], $tRightRibbons)) {
                                        $selProdRibbons[] = $tRightRibbons[$product['selprod_id']];
                                    }
                                ?>
                                    <?php $uploadedTime = AttachedFile::setTimeParam($product['product_updated_on']); ?>
                                    <div class="products-img">
                                        <a title="<?php echo $product['selprod_title']; ?>"
                                            href="<?php echo !isset($product['promotion_id']) ? UrlHelper::generateUrl('Products', 'View', array($product['selprod_id'])) : UrlHelper::generateUrl('Products', 'track', array($product['promotion_record_id'])); ?>">
                                            <?php
                                            $fileRow = CommonHelper::getImageAttributes(AttachedFile::FILETYPE_PRODUCT_IMAGE, $product['product_id']);
                                            $pictureAttr = [
                                                'webpImageUrl' => [ImageDimension::VIEW_DESKTOP => UrlHelper::getCachedUrl(UrlHelper::generateFileUrl('image', 'product', array($product['product_id'], "WEBP" . ImageDimension::VIEW_MOBILE, $product['selprod_id'], 0, $siteLangId)) . $uploadedTime, CONF_IMG_CACHE_TIME, '.webp')],
                                                'jpgImageUrl' => [ImageDimension::VIEW_DESKTOP => UrlHelper::getCachedUrl(UrlHelper::generateFileUrl('image', 'product', array($product['product_id'], ImageDimension::VIEW_MOBILE, $product['selprod_id'], 0, $siteLangId)) . $uploadedTime, CONF_IMG_CACHE_TIME, '.jpg')],
                                                'dimensionType' => ImageDimension::TYPE_PRODUCTS,
                            'dimensionSize' => ImageDimension::VIEW_MOBILE,
                            'sizeTypes' => [
                                ImageDimension::VIEW_DESKTOP => ImageDimension::VIEW_MOBILE,
                            ],
                            'ratio' => '1:1',
                                                'imageUrl' => UrlHelper::getCachedUrl(UrlHelper::generateFileUrl('image', 'product', array($product['product_id'], ImageDimension::VIEW_MOBILE, $product['selprod_id'], 0, $siteLangId)) . $uploadedTime, CONF_IMG_CACHE_TIME, '.jpg'),
                                                'alt' => (!empty($fileRow['afile_attribute_alt'])) ? $fileRow['afile_attribute_alt'] : $product['prodcat_name'],
                                                'siteLangId' => $siteLangId,
                                            ];

                                            $this->includeTemplate('_partial/picture-tag.php', $pictureAttr);
                                            ?>
                                        </a>
                                    </div>
                                <?php $rCount++;
                                    if (5 == $rCount) {
                                        break;
                                    }
                                } ?>
                            </div>
                        </div>
                    <?php } ?>
                </div>
            </div>
        </div>
    </section>
<?php } ?>