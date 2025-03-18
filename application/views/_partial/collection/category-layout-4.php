<?php defined('SYSTEM_INIT') or die('Invalid Usage.');
if (isset($collection['categories']) && count($collection['categories'])) { ?>
    <section class="section" data-section="section">
        <div class="container">
            <header class="section-head category-product">
                <?php echo ($collection['collection_name'] != '') ? ' <div class="section-heading"><h2>' . $collection['collection_name'] . '</h2></div>' : ''; ?>
                <div class="section-action">
                    <ul class="nav nav-tabs tabs-masking" role="tablist">
                        <?php
                        $x = 0;
                        foreach ($collection['categories'] as $key => $category) {
                            $x++; ?>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link <?php echo 1 == $x ? 'active' : ''; ?>" data-bs-toggle="tab"
                                    data-bs-target="#tb-<?php echo $key . "-" . $collection['collection_id']; ?>" role="tab"
                                    aria-controls="tabpanel-<?php echo $key . "-" . $collection['collection_id']; ?>"
                                    title="<?php echo $category['catData']['prodcat_name']; ?>">
                                    <?php echo $category['catData']['prodcat_name']; ?>
                                </button>
                            </li>
                            <?php if (4 == $x) {
                                break;
                            }
                        } ?>
                    </ul>
                </div>
            </header>
            <div class="section-body">
                <div class="tab-content" role="tabpanel">
                    <?php $j = 0;
                    $dataDisplayCount = (Collections::TYPE_CATEGORY_LAYOUT8 == $collection['collection_layout_type']) ? $collection['collection_primary_records'] : (0 < $collection['collection_primary_records'] ? $collection['collection_primary_records'] : 4);
                    foreach ($collection['categories'] as $key => $category) {
                        $j++;
                        ?>
                        <div class="tab-pane fade category-product-layout-1  <?php echo 1 == $j ? 'show active' : ''; ?>"
                            id="tb-<?php echo $key . "-" . $collection['collection_id']; ?>">
                            <div class="product-listing" data-view="<?php echo $dataDisplayCount; ?>">
                                <?php
                                $tRightRibbons = $category['tRightRibbons'];
                                $i = 1;
                                foreach ($category['products'] as $key => $product) {

                                    $selProdRibbons = [];
                                    if (array_key_exists($product['selprod_id'], $tRightRibbons)) {
                                        $selProdRibbons[] = $tRightRibbons[$product['selprod_id']];
                                    }
                                    ?>
                                    <div class="product-listing-item">
                                        <div
                                            class="products <?php echo (isset($layoutClass)) ? $layoutClass : ''; ?> <?php if ($product['selprod_stock'] <= 0) { ?> out-of-stock <?php } ?>">
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
                                                                xlink:href="<?php echo CONF_WEBROOT_URL; ?>images/retina/<?php echo AttachedFile::setTimeParam(RELEASE_DATE); ?>#linkedinfo">
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
                                                            'webpImageUrl' => [
                                                                ImageDimension::VIEW_DESKTOP => UrlHelper::getCachedUrl(UrlHelper::generateFileUrl('image', 'product', array($product['product_id'], (isset($prodImgSize) && isset($i) && ($i == 1)) ? $prodImgSize : "WEBP" . ImageDimension::VIEW_CLAYOUT1, $product['selprod_id'], 0, $siteLangId), CONF_WEBROOT_FRONTEND) . $uploadedTime, CONF_IMG_CACHE_TIME, '.webp'),
                                                                ImageDimension::VIEW_TABLET => UrlHelper::getCachedUrl(UrlHelper::generateFileUrl('image', 'product', array($product['product_id'], "WEBP" . ImageDimension::VIEW_TABLET, $product['selprod_id'], 0, $siteLangId), CONF_WEBROOT_FRONTEND) . $uploadedTime, CONF_IMG_CACHE_TIME, '.webp'),
                                                                ImageDimension::VIEW_MOBILE => UrlHelper::getCachedUrl(UrlHelper::generateFileUrl('image', 'product', array($product['product_id'], "WEBP" . ImageDimension::VIEW_MOBILE, $product['selprod_id'], 0, $siteLangId), CONF_WEBROOT_FRONTEND) . $uploadedTime, CONF_IMG_CACHE_TIME, '.webp')
                                                            ],
                                                            'jpgImageUrl' => [
                                                                ImageDimension::VIEW_DESKTOP => UrlHelper::getCachedUrl(UrlHelper::generateFileUrl('image', 'product', array($product['product_id'], (isset($prodImgSize) && isset($i) && ($i == 1)) ? $prodImgSize : ImageDimension::VIEW_CLAYOUT1, $product['selprod_id'], 0, $siteLangId), CONF_WEBROOT_FRONTEND) . $uploadedTime, CONF_IMG_CACHE_TIME, '.jpg'),
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
                                            <div class="products-foot">
                                                <a class="products-title" title="<?php echo $product['selprod_title']; ?>"
                                                    href="<?php echo UrlHelper::generateUrl('Products', 'View', array($product['selprod_id'])); ?>"><?php echo $product['selprod_title']; ?>
                                                </a>
                                                <?php include(CONF_THEME_PATH . '_partial/collection/product-price.php'); ?>
                                            </div>
                                        </div>
                                    </div>
                                    <?php $i++;
                                } ?>
                            </div>
                        </div>
                        <?php if (4 == $j) {
                            break;
                        }
                    } ?>
                </div>
            </div>
            <?php if (count($collection['categories']) > 4) { ?>
                <div class="section-foot">
                    <a href="<?php echo UrlHelper::generateUrl('Collections', 'View', array($collection['collection_id'])); ?>"
                        class="link-underline"><?php echo Labels::getLabel('LBL_VIEW_ALL', $siteLangId); ?></a>
                </div>
            <?php } ?>

        </div>
    </section>
<?php } ?>