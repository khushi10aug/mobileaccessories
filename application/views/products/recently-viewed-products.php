<?php defined('SYSTEM_INIT') or die('Invalid Usage.');
$displayProductNotAvailableLable = false;
if (FatApp::getConfig('CONF_ENABLE_GEO_LOCATION', FatUtility::VAR_INT, 0) && !empty(FatApp::getConfig('CONF_GOOGLEMAP_API_KEY', FatUtility::VAR_STRING, ''))) {
    $displayProductNotAvailableLable = true;
}
if ($recentViewedProducts) { ?>
    <section class="section bg-gray" data-section="Recently view">
        <div class="container">
            <header class="section-head">
                <div class="section-heading">
                    <h2>
                        <?php echo Labels::getLabel('LBL_Recently_Viewed', $siteLangId); ?>
                    </h2>
                </div>
                <div class="section-action">
                <div class="slider-controls recentlyviewed">
                        <button class="btn btn-prev slick-arrow arrow-prev" type="button">
                            <span></span>
                        </button>
                        <button class="btn btn-next slick-arrow arrow-next" type="button">
                            <span></span>
                        </button>
                    </div>
                </div>
            </header>
            <div class="section-body">            
                <div class="js-carousel recently-viewed-products" id="product-listing-rvp" data-slides="5,4,3,2,2"
                    data-arrows="true" data-slickdots="false" data-custom="#product-listing-rvp" data-dots="false"
                    data-customarrow="true" data-arrowcontainer="recentlyviewed" data-dotscontainer=""
                    dir="<?php echo CommonHelper::getLayoutDirection(); ?>">
                    <?php
                    $tRightRibbons = isset($recentlyViewedRibbons['tRightRibbons']) ? $recentlyViewedRibbons['tRightRibbons'] : [];
                    foreach ($recentViewedProducts as $rProduct) {
                        $selProdRibbons = [];
                        if (array_key_exists($rProduct['selprod_id'], $tRightRibbons)) {
                            $selProdRibbons[] = $tRightRibbons[$rProduct['selprod_id']];
                        }
                        $productUrl = UrlHelper::generateUrl('Products', 'View', array($rProduct['selprod_id'])); ?>
                        <div class="js-carousel-item">
                            <div class="products">
                                <div class="products-body">
                                    <div class="badges-wrap">
                                        <?php $this->includeTemplate('_partial/product-type-ribbon.php', ['productType' => $rProduct['product_type'], 'siteLangId' => $siteLangId], false);
                                        if (!empty($selProdRibbons)) {
                                            foreach ($selProdRibbons as $ribbRow) {
                                                $this->includeTemplate('_partial/ribbon-ui.php', ['ribbRow' => $ribbRow], false);
                                            }
                                        } ?>
                                    </div>
                                    <?php if (true == $displayProductNotAvailableLable && array_key_exists('availableInLocation', $rProduct) && 0 == $rProduct['availableInLocation']) { ?>
                                        <div class="not-available">
                                            <svg class="svg">
                                                <use
                                                    xlink:href="<?php echo CONF_WEBROOT_URL; ?>images/retina/sprite.svg<?php echo AttachedFile::setTimeParam(RELEASE_DATE); ?>#linkedinfo">
                                                </use>
                                            </svg>
                                            <?php echo Labels::getLabel('LBL_NOT_AVAILABLE', $siteLangId); ?>
                                        </div>
                                    <?php } ?>

                                    <div class="products-img">
                                        <a title="<?php echo CommonHelper::renderHtml($rProduct['selprod_title'], true); ?>"
                                            href="<?php echo !isset($rProduct['promotion_id']) ? UrlHelper::generateUrl('Products', 'View', array($rProduct['selprod_id'])) : UrlHelper::generateUrl('Products', 'track', array($rProduct['promotion_record_id'])); ?>">
                                            <?php $fileRow = CommonHelper::getImageAttributes(AttachedFile::FILETYPE_PRODUCT_IMAGE, $rProduct['product_id']); ?>
                                            <?php
                                            $pictureAttr = [
                                                'webpImageUrl' => [ImageDimension::VIEW_DESKTOP => UrlHelper::getCachedUrl(UrlHelper::generateFileUrl('image', 'product', array($rProduct['product_id'], 'WEBP' . ImageDimension::VIEW_CLAYOUT1, $rProduct['selprod_id'], 0, $siteLangId)), CONF_IMG_CACHE_TIME, '.webp')],
                                                'jpgImageUrl' => [ImageDimension::VIEW_DESKTOP => UrlHelper::getCachedUrl(UrlHelper::generateFileUrl('image', 'product', array($rProduct['product_id'], ImageDimension::VIEW_CLAYOUT1, $rProduct['selprod_id'], 0, $siteLangId)), CONF_IMG_CACHE_TIME, '.jpg')],
                                                'ratio' => '1:1',
                                                'imageUrl' => UrlHelper::getCachedUrl(UrlHelper::generateFileUrl('image', 'product', array($rProduct['product_id'], ImageDimension::VIEW_CLAYOUT1, $rProduct['selprod_id'], 0, $siteLangId)), CONF_IMG_CACHE_TIME, '.jpg'),
                                                'siteLangId' => $siteLangId,
                                                'alt' => (!empty($fileRow['afile_attribute_alt'])) ? $fileRow['afile_attribute_alt'] : $rProduct['prodcat_name'],
                                            ];
                                            $this->includeTemplate('_partial/picture-tag.php', $pictureAttr);
                                            ?>
                                        </a>
                                    </div>
                                </div>
                                <div class="products-foot">
                                    <a class="products-category"
                                        href="<?php echo UrlHelper::generateUrl('Category', 'View', array($rProduct['prodcat_id'])); ?>" title="<?php echo CommonHelper::renderHtml($rProduct['prodcat_name'], true); ?>"><?php echo CommonHelper::renderHtml($rProduct['prodcat_name'], true); ?>
                                    </a>
                                    <a class="products-title"
                                        title="<?php echo CommonHelper::renderHtml($rProduct['selprod_title'], true); ?>"
                                        href="<?php echo UrlHelper::generateUrl('Products', 'View', array($rProduct['selprod_id'])); ?>"><?php echo (mb_strlen($rProduct['selprod_title']) > 50) ? mb_substr(CommonHelper::renderHtml($rProduct['selprod_title'], true), 0, 50) . "..." : CommonHelper::renderHtml($rProduct['selprod_title'], true); ?>
                                    </a>
                                    <?php $this->includeTemplate('_partial/collection-product-price.php', array('product' => $rProduct, 'siteLangId' => $siteLangId), false); ?>
                                </div>
                            </div>
                        </div>
                    <?php } ?>
                </div>
            </div>
        </div>
    </section>
    <?php
} ?>
<script>
    $(document).ready(function() {        
        loadSlickSlider();
    });
</script>