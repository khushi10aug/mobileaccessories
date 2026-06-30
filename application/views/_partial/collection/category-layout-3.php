<?php defined('SYSTEM_INIT') or die('Invalid Usage.'); ?>
<?php if (isset($collection['categories']) && count($collection['categories'])) { ?>
    <section class="section" data-section="section">
        <div class="container">
            <header class="section-head section-head-center">
                <?php echo ($collection['collection_name'] != '') ? ' <div class="section-heading"><h2>' . $collection['collection_name'] . '</h2></div>' : ''; ?>
            </header>
            <div class="section-body">
                <div class="category-layout-3">
                    <?php
                    foreach ($collection['categories'] as $category) { ?>
                        <div class="category">
                            <?php $fileRow = CommonHelper::getImageAttributes(AttachedFile::FILETYPE_CATEGORY_BANNER, $category['prodcat_id']);
                            $uploadedTime = AttachedFile::setTimeParam($fileRow['afile_updated_at']);
                            ?>
                            <div class="category-head">
                                <?php
                                $productId = $category['product']['product_id'] ?? 0;
                                $selProdId = $category['product']['selprod_id'] ?? 0;
                                $prodcatName = $category['product']['prodcat_name'] ?? '';
                                $fileRow = CommonHelper::getImageAttributes(AttachedFile::FILETYPE_PRODUCT_IMAGE, $productId);
                                $pictureAttr = [
                                    'webpImageUrl' => [ImageDimension::VIEW_DESKTOP => UrlHelper::getCachedUrl(UrlHelper::generateFileUrl('image', 'product', array($productId, "WEBP" . ImageDimension::VIEW_CLAYOUT3, $selProdId, 0, $siteLangId)) . $uploadedTime, CONF_IMG_CACHE_TIME, '.webp')],
                                    'jpgImageUrl' => [ImageDimension::VIEW_DESKTOP => UrlHelper::getCachedUrl(UrlHelper::generateFileUrl('image', 'product', array($productId, ImageDimension::VIEW_CLAYOUT3, $selProdId, 0, $siteLangId)) . $uploadedTime, CONF_IMG_CACHE_TIME, '.jpg')],
                                    'dimensionType' => ImageDimension::TYPE_PRODUCTS,
                            'dimensionSize' => ImageDimension::VIEW_CLAYOUT3,
                            'sizeTypes' => [
                                ImageDimension::VIEW_DESKTOP => ImageDimension::VIEW_CLAYOUT3,
                            ],
                            'ratio' => '1:1',
                                    'imageUrl' => UrlHelper::getCachedUrl(UrlHelper::generateFileUrl('image', 'product', array($productId, ImageDimension::VIEW_CLAYOUT3, $selProdId, 0, $siteLangId)) . $uploadedTime, CONF_IMG_CACHE_TIME, '.jpg'),
                                    'alt' => (!empty($fileRow['afile_attribute_alt'])) ? $fileRow['afile_attribute_alt'] : $prodcatName,
                                    'siteLangId' => $siteLangId,
                                ];
                                $prodUrl = 0 < $selProdId ? UrlHelper::generateUrl('Products', 'View', array($selProdId)) : 'javascript:void(0);';
                                ?>
                                <a title="<?php echo $prodcatName; ?>" href="<?php echo $prodUrl; ?>">
                                    <?php $this->includeTemplate('_partial/picture-tag.php', $pictureAttr); ?>
                                </a>
                            </div>
                            <div class="category-body">
                                <ul class="category-list">
                                    <li class="category-list-item category-list-head">
                                        <a
                                            href="<?php echo UrlHelper::generateUrl('Category', 'View', array($category['prodcat_id'])); ?>" title="<?php echo $category['prodcat_name']; ?>">
                                            <?php echo $category['prodcat_name']; ?>
                                        </a>
                                    </li>
                                    <?php $i = 1;
                                    foreach ($category['subCategories'] as $subCat) { ?>
                                        <li class="category-list-item">
                                            <a
                                                href="<?php echo UrlHelper::generateUrl('Category', 'View', array($subCat['prodcat_id'])); ?>" title="<?php echo $subCat['prodcat_name']; ?>">
                                                <?php echo $subCat['prodcat_name']; ?></a>
                                        </li>
                                    <?php $i++;
                                        if ($i > 5) {
                                            break;
                                        }
                                    } ?>

                                </ul>
                            </div>
                        </div>
                    <?php } ?>
                </div>
            </div>
            <?php if (count($collection['categories']) > Collections::LIMIT_CATEGORY_LAYOUT3) { ?>
                <div class="section-foot">
                    <a href="<?php echo UrlHelper::generateUrl('Collections', 'View', array($collection['collection_id'])); ?>"
                        class="link-underline">
                        <?php echo Labels::getLabel('LBL_VIEW_ALL', $siteLangId); ?>
                    </a>
                </div>
            <?php } ?>
        </div>
    </section>
<?php } ?>