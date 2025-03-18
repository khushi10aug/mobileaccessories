<?php defined('SYSTEM_INIT') or die('Invalid Usage.');
if (isset($collection['categories']) && count($collection['categories'])) { ?>
    <section class="section" data-section="section">
        <div class="container">
            <div class="section-body">
                <div class="category-layout-1">
                    <?php foreach ($collection['categories'] as $key => $category) { ?>
                        <div class="category-layout-1-item">
                            <header class="section-head">
                                <div class="section-heading" title="<?php echo $category['catData']['prodcat_name'] ?? ''; ?>">
                                    <h2><?php echo $category['catData']['prodcat_name'] ?? ''; ?></h2>
                                </div>
                                <div class="section-action">
                                    <a href="<?php echo UrlHelper::generateUrl('Category', 'View', array($category['catData']['prodcat_id'])); ?>" class="link-underline link-more"> <?php echo Labels::getLabel('LBL_VIEW_ALL', $siteLangId); ?> </a>
                                </div>
                            </header>
                            <div class="section-body">
                                <div class="category-layout-1-body">
                                    <?php
                                    $tRightRibbons = $category['tRightRibbons'];
                                    $rCount = 1;
                                    foreach ($category['products'] as $key => $product) {
                                        $selProdRibbons = [];
                                        if (array_key_exists($product['selprod_id'], $tRightRibbons)) {
                                            $selProdRibbons[] = $tRightRibbons[$product['selprod_id']];
                                        }
                                    ?>
                                        <div class="products product-item <?php echo (isset($layoutClass)) ? $layoutClass : ''; ?> <?php if ($product['selprod_stock'] <= 0) { ?> out-of-stock <?php } ?>">
                                            <div class="products-body">
                                                <?php if ($product['selprod_stock'] <= 0) { ?>
                                                    <div class="out-of-stock-txt">
                                                        <?php echo Labels::getLabel('LBL_SOLD_OUT', $siteLangId); ?>
                                                    </div>
                                                <?php } ?>
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
                                                            <use
                                                                xlink:href="<?php echo CONF_WEBROOT_URL; ?>images/retina/sprite.svg<?php echo AttachedFile::setTimeParam(RELEASE_DATE); ?>#linkedinfo">
                                                            </use>
                                                        </svg>
                                                        <?php echo Labels::getLabel('LBL_NOT_AVAILABLE', $siteLangId); ?>
                                                    </div>
                                                <?php } ?>

                                                <?php $uploadedTime = AttachedFile::setTimeParam($product['product_updated_on']); ?>
                                                <div class="products-img">
                                                    <a title="<?php echo $product['selprod_title']; ?>"
                                                        href="<?php echo !isset($product['promotion_id']) ? UrlHelper::generateUrl('Products', 'View', array($product['selprod_id'])) : UrlHelper::generateUrl('Products', 'track', array($product['promotion_record_id'])); ?>">
                                                        <?php
                                                        $fileRow = CommonHelper::getImageAttributes(AttachedFile::FILETYPE_PRODUCT_IMAGE, $product['product_id']);
                                                        $pictureAttr = [
                                                            'webpImageUrl' => [ImageDimension::VIEW_DESKTOP => UrlHelper::getCachedUrl(UrlHelper::generateFileUrl('image', 'product', array($product['product_id'], "WEBP" . ImageDimension::VIEW_THUMB, $product['selprod_id'], 0, $siteLangId)) . $uploadedTime, CONF_IMG_CACHE_TIME, '.webp')],
                                                            'jpgImageUrl' => [ImageDimension::VIEW_DESKTOP => UrlHelper::getCachedUrl(UrlHelper::generateFileUrl('image', 'product', array($product['product_id'], ImageDimension::VIEW_THUMB, $product['selprod_id'], 0, $siteLangId)) . $uploadedTime, CONF_IMG_CACHE_TIME, '.jpg')],
                                                            'ratio' => '1:1',
                                                            'imageUrl' => UrlHelper::getCachedUrl(UrlHelper::generateFileUrl('image', 'product', array($product['product_id'], ImageDimension::VIEW_THUMB, $product['selprod_id'], 0, $siteLangId)) . $uploadedTime, CONF_IMG_CACHE_TIME, '.jpg'),
                                                            'alt' => (!empty($fileRow['afile_attribute_alt'])) ? $fileRow['afile_attribute_alt'] : $product['prodcat_name'],
                                                            'siteLangId' => $siteLangId,
                                                        ];

                                                        $this->includeTemplate('_partial/picture-tag.php', $pictureAttr);
                                                        ?>
                                                    </a>
                                                </div>

                                            </div>
                                            <div class="products-foot">
                                                <?php if ($product['product_rating']) { ?>
                                                    <div class="product-ratings">
                                                        <svg class="svg svg-star" width="14" height="14">
                                                            <use xlink:href="/images/retina/sprite.svg<?php echo AttachedFile::setTimeParam(RELEASE_DATE); ?>#star-yellow">
                                                            </use>
                                                        </svg>
                                                        <span class="rate"><?php echo $product['product_rating']; ?></span>
                                                    </div>
                                                <?php } ?>
                                                <a class="products-title" title="<?php echo $product['selprod_title']; ?>"
                                                    href="<?php echo UrlHelper::generateUrl('Products', 'View', array($product['selprod_id'])); ?>"><?php echo substr($product['selprod_title'], 0, 50); ?>
                                                </a>
                                                <?php include(CONF_THEME_PATH . '_partial/collection/product-price.php'); ?>
                                            </div>
                                        </div>
                                    <?php $rCount++;
                                        if (4 == $rCount) {
                                            break;
                                        }
                                    } ?>
                                </div>
                            </div>
                        </div>
                    <?php } ?>
                </div>
            </div>
        </div>
    </section>
<?php } ?>