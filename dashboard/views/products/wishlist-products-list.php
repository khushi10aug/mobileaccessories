<?php defined('SYSTEM_INIT') or die('Invalid Usage.');
$colMdVal = isset($colMdVal) ? $colMdVal : 4;
$displayProductNotAvailableLabel = false;
if (FatApp::getConfig('CONF_ENABLE_GEO_LOCATION', FatUtility::VAR_INT, 0) && !empty(FatApp::getConfig('CONF_GOOGLEMAP_API_KEY', FatUtility::VAR_STRING, ''))) {
    $displayProductNotAvailableLabel = true;
}

$vtype = $postedData['vtype'] ?? false;
?>
<div id="productsList">
    <div class="product-listing" data-view="<?php echo $colMdVal; ?>">
        <?php if ($products) {
            $isWishList = isset($isWishList) ? $isWishList : 0;
            foreach ($products as $product) {
                $selProdRibbons = [];
                if (array_key_exists($product['selprod_id'], $tRightRibbons)) {
                    $selProdRibbons[] = $tRightRibbons[$product['selprod_id']];
                }
                $isNotServiceable = (true == $displayProductNotAvailableLabel && array_key_exists('availableInLocation', $product) && 0 == $product['availableInLocation']);
                $showActionBtns = !empty($showActionBtns) ? $showActionBtns : false;
                $productUrl = UrlHelper::generateUrl('Products', 'View', array($product['selprod_id']), CONF_WEBROOT_FRONTEND);

                $tempHoldStock = Product::tempHoldStockCount($product['selprod_id']);
                $availableStock = $product['selprod_stock'] - $tempHoldStock;
                $isOutOfMinOrderQty = ((int) ($product['selprod_min_order_qty'] > $availableStock));
                ?>

                <div class="product-listing-item">
                    <div class="products">
                        <?php $this->includeTemplate('_partial/quick-view.php', ['product' => $product, 'siteLangId' => $siteLangId], false); ?>
                        <?php if ($product['in_stock'] == 0 || 0 < $isOutOfMinOrderQty) { ?>
                            <span class="tag--soldout"><?php echo Labels::getLabel('LBL_SOLD_OUT', $siteLangId); ?></span>
                        <?php } ?>
                        <div class="products-body">
                            <?php $this->includeTemplate('_partial/collection-ui.php', array('product' => $product, 'siteLangId' => $siteLangId, 'showActionBtns' => ($showActionBtns && false === $isNotServiceable), 'isWishList' => $isWishList, 'selProdRibbons' => $selProdRibbons, 'isOutOfMinOrderQty' => $isOutOfMinOrderQty), false); ?>
                            <?php if ($isNotServiceable) { ?>
                                <div class="not-available">
                                    <svg class="svg">
                                        <use xlink:href="<?php echo CONF_WEBROOT_URL; ?>images/retina/sprite.svg<?php echo AttachedFile::setTimeParam(RELEASE_DATE); ?>#info"
                                            href="<?php echo CONF_WEBROOT_URL; ?>images/retina/sprite.svg<?php echo AttachedFile::setTimeParam(RELEASE_DATE); ?>#info">
                                        </use>
                                    </svg> <?php echo Labels::getLabel('LBL_NOT_SERVICEABLE', $siteLangId); ?>
                                </div>
                            <?php } ?>
                            <div class="products-img">
                                <?php $uploadedTime = AttachedFile::setTimeParam($product['product_updated_on']); ?>
                                <a title="<?php echo $product['selprod_title']; ?>"
                                    href="<?php echo !isset($product['promotion_id']) ? UrlHelper::generateUrl('Products', 'View', array($product['selprod_id']), CONF_WEBROOT_FRONTEND) : UrlHelper::generateUrl('Products', 'track', array($product['promotion_record_id']), CONF_WEBROOT_FRONTEND) ?>">
                                    <?php $fileRow = CommonHelper::getImageAttributes(AttachedFile::FILETYPE_PRODUCT_IMAGE, $product['product_id']); ?>
                                    <?php
                                    $pictureAttr = [
                                        'webpImageUrl' => [ImageDimension::VIEW_DESKTOP => UrlHelper::getCachedUrl(UrlHelper::generateFileUrl('image', 'product', array($product['product_id'], 'WEBP' . ImageDimension::VIEW_CLAYOUT3, $product['selprod_id'], 0, $siteLangId), CONF_WEBROOT_FRONTEND) . $uploadedTime, CONF_IMG_CACHE_TIME, '.webp')],
                                        'jpgImageUrl' => [ImageDimension::VIEW_DESKTOP => UrlHelper::getCachedUrl(UrlHelper::generateFileUrl('image', 'product', array($product['product_id'], ImageDimension::VIEW_CLAYOUT3, $product['selprod_id'], 0, $siteLangId), CONF_WEBROOT_FRONTEND) . $uploadedTime, CONF_IMG_CACHE_TIME, '.jpg')],
                                        'imageUrl' => UrlHelper::getCachedUrl(UrlHelper::generateFileUrl('image', 'product', array($product['product_id'], ImageDimension::VIEW_CLAYOUT3, $product['selprod_id'], 0, $siteLangId), CONF_WEBROOT_FRONTEND) . $uploadedTime, CONF_IMG_CACHE_TIME, '.jpg'),
                                        'alt' => (!empty($fileRow['afile_attribute_alt'])) ? $fileRow['afile_attribute_alt'] : $product['prodcat_name'],
                                        'data-ratio' => "1:1",
                                        'title' => (!empty($fileRow['afile_attribute_title'])) ? $fileRow['afile_attribute_title'] : $product['prodcat_name']
                                    ];
                                    $this->includeTemplate('_partial/picture-tag.php', $pictureAttr, false);
                                    ?>
                                </a>
                            </div>
                        </div>
                        <div class="products-foot">

                            <a class="products-category"
                                href="<?php echo UrlHelper::generateUrl('Category', 'View', array($product['prodcat_id']), CONF_WEBROOT_FRONTEND); ?>" title="<?php echo html_entity_decode($product['prodcat_name'], ENT_QUOTES, 'UTF-8'); ?>"><?php echo html_entity_decode($product['prodcat_name'], ENT_QUOTES, 'UTF-8'); ?>
                            </a>

                            <a class="products-title" title="<?php echo $product['selprod_title']; ?>"
                                href="<?php echo UrlHelper::generateUrl('Products', 'View', array($product['selprod_id']), CONF_WEBROOT_FRONTEND); ?>"><?php echo (mb_strlen($product['selprod_title']) > 50) ? mb_substr($product['selprod_title'], 0, 50) . "..." : $product['selprod_title']; ?>
                            </a>

                            <?php $this->includeTemplate('_partial/collection-product-price.php', array('product' => $product, 'siteLangId' => $siteLangId), false); ?>
                        </div>
                    </div>
                </div>
            <?php } ?>
        </div> <?php
        $searchFunction = 'goToProductListingSearchPage';
        if (isset($pagingFunc)) {
            $searchFunction = $pagingFunc;
        }

        $postedData['page'] = (isset($page)) ? $page : 1;
        $postedData['recordDisplayCount'] = $recordCount;
        echo FatUtility::createHiddenFormFromData($postedData, array('name' => 'frmProductSearchPaging', 'id' => 'frmProductSearchPaging'));
        $pagingArr = array('pageCount' => $pageCount, 'page' => $postedData['page'], 'recordCount' => $recordCount, 'callBackJsFunc' => $searchFunction);
        $this->includeTemplate('_partial/pagination.php', $pagingArr, false); ?>
    <?php } else { ?>
    </div>
    <?php
    $arr['recordDisplayCount'] = $recordCount;
    echo FatUtility::createHiddenFormFromData($arr, array('name' => 'frmProductSearchPaging', 'id' => 'frmProductSearchPaging'));
    $message = Labels::getLabel('LBL_No_Records_Found', $siteLangId);
    $this->includeTemplate('_partial/no-record-found.php', array('siteLangId' => $siteLangId, 'message' => $message)); ?>
<?php }
// } 
?>
</div>