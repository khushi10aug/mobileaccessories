<?php
defined('SYSTEM_INIT') or die('Invalid Usage.');
$colMdVal = isset($colMdVal) ? $colMdVal : 4;
$displayProductNotAvailableLable = false;
if (FatApp::getConfig('CONF_ENABLE_GEO_LOCATION', FatUtility::VAR_INT, 0) && !empty(FatApp::getConfig('CONF_GOOGLEMAP_API_KEY', FatUtility::VAR_STRING, ''))) {
    $displayProductNotAvailableLable = true;
}

?>
<div id="productsList">
    <div class="product-listing" data-view="<?php echo $colMdVal; ?>">
        <?php
        if ($products) {
            $showActionBtns = !empty($showActionBtns) ? $showActionBtns : false;
            $isWishList = isset($isWishList) ? $isWishList : 0;
            foreach ($products as $product) {
                $selProdRibbons = [];
                if (isset($tRightRibbons)) {
                    if (array_key_exists($product['selprod_id'], $tRightRibbons)) {
                        $selProdRibbons[] = $tRightRibbons[$product['selprod_id']];
                    }
                } else if (isset($product['ribbons'])) {
                    $selProdRibbons = $product['ribbons'];
                }
                $productUrl = UrlHelper::generateUrl('Products', 'View', array($product['selprod_id'])); ?>
                <div class="product-listing-item productsListItemsJs" data-shopId="<?php echo $product['shop_id']; ?>">
                    <div class="products">
                        <div class="products-body">
                            <?php if ($product['in_stock'] == 0) { ?>
                                <div class="out-of-stock-txt"><?php echo Labels::getLabel('LBL_SOLD_OUT', $siteLangId); ?></div>
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
                                    <svg class="svg" width="14" height="14">
                                        <use xlink:href="<?php echo CONF_WEBROOT_URL; ?>images/retina/sprite.svg<?php echo AttachedFile::setTimeParam(RELEASE_DATE); ?>#linkedinfo">
                                        </use>
                                    </svg>
                                    <?php echo Labels::getLabel('LBL_NOT_SERVICEABLE', $siteLangId); ?>
                                </div>
                            <?php } ?>
                            <div class="products-img">
                                <?php $uploadedTime = AttachedFile::setTimeParam($product['product_updated_on']); ?>
                                <a title="<?php echo CommonHelper::renderHtml($product['selprod_title'], true); ?>"
                                    href="<?php echo !isset($product['promotion_id']) ? UrlHelper::generateUrl('Products', 'View', array($product['selprod_id'])) : UrlHelper::generateUrl('Products', 'track', array($product['promotion_record_id'])) ?>">
                                    <?php
                                    $fileRow = CommonHelper::getImageAttributes(AttachedFile::FILETYPE_PRODUCT_IMAGE, $product['product_id']);
                                    $pictureAttr = [
                                        'webpImageUrl' => [
                                            ImageDimension::VIEW_DESKTOP => UrlHelper::getCachedUrl(UrlHelper::generateFileUrl('image', 'product', array($product['product_id'], 'WEBP' . ImageDimension::VIEW_CLAYOUT1, $product['selprod_id'], 0, $siteLangId)) . $uploadedTime, CONF_IMG_CACHE_TIME, '.webp'),
                                            ImageDimension::VIEW_TABLET => UrlHelper::getCachedUrl(UrlHelper::generateFileUrl('image', 'product', array($product['product_id'], 'WEBP' . ImageDimension::VIEW_SMALL, $product['selprod_id'], 0, $siteLangId)) . $uploadedTime, CONF_IMG_CACHE_TIME, '.webp'),
                                            ImageDimension::VIEW_MOBILE => UrlHelper::getCachedUrl(UrlHelper::generateFileUrl('image', 'product', array($product['product_id'], 'WEBP' . ImageDimension::VIEW_MOBILE, $product['selprod_id'], 0, $siteLangId)) . $uploadedTime, CONF_IMG_CACHE_TIME, '.webp'),
                                        ],
                                        'jpgImageUrl' => [
                                            ImageDimension::VIEW_DESKTOP => UrlHelper::getCachedUrl(UrlHelper::generateFileUrl('image', 'product', array($product['product_id'], ImageDimension::VIEW_CLAYOUT1, $product['selprod_id'], 0, $siteLangId)) . $uploadedTime, CONF_IMG_CACHE_TIME, '.jpg'),
                                            ImageDimension::VIEW_TABLET => UrlHelper::getCachedUrl(UrlHelper::generateFileUrl('image', 'product', array($product['product_id'], ImageDimension::VIEW_SMALL, $product['selprod_id'], 0, $siteLangId)) . $uploadedTime, CONF_IMG_CACHE_TIME, '.jpg'),
                                            ImageDimension::VIEW_MOBILE => UrlHelper::getCachedUrl(UrlHelper::generateFileUrl('image', 'product', array($product['product_id'], ImageDimension::VIEW_MOBILE, $product['selprod_id'], 0, $siteLangId)) . $uploadedTime, CONF_IMG_CACHE_TIME, '.jpg')
                                        ],
                                        'ratio' => '1:1',
                                        'imageUrl' => UrlHelper::getCachedUrl(UrlHelper::generateFileUrl('image', 'product', array($product['product_id'], ImageDimension::VIEW_CLAYOUT1, $product['selprod_id'], 0, $siteLangId)) . $uploadedTime, CONF_IMG_CACHE_TIME, '.jpg'),
                                        'siteLangId' => $siteLangId,
                                        'alt' => (!empty($fileRow['afile_attribute_alt'])) ? $fileRow['afile_attribute_alt'] : $product['prodcat_name'],
                                        'title' => (!empty($fileRow['afile_attribute_title'])) ? $fileRow['afile_attribute_title'] : $product['prodcat_name'],
                                    ];
                                    $this->includeTemplate('_partial/picture-tag.php', $pictureAttr); ?>
                                </a>
                            </div>
                        </div>
                        <div class="products-foot">
                            <a class="products-category"
                                href="<?php echo UrlHelper::generateUrl('Category', 'View', array($product['prodcat_id'])); ?>" title="<?php echo CommonHelper::renderHtml($product['prodcat_name'], true); ?>"><?php echo CommonHelper::renderHtml($product['prodcat_name'], true); ?>
                            </a>
                            <a class="products-title"
                                title="<?php echo CommonHelper::renderHtml($product['selprod_title'], true); ?>"
                                href="<?php echo UrlHelper::generateUrl('Products', 'View', array($product['selprod_id'])); ?>"><?php echo (mb_strlen($product['selprod_title']) > 50) ? mb_substr(CommonHelper::renderHtml($product['selprod_title'], true), 0, 50) . "..." : CommonHelper::renderHtml($product['selprod_title'], true); ?>
                            </a>
                            <?php $this->includeTemplate('_partial/collection-product-price.php', array('product' => $product, 'siteLangId' => $siteLangId), false); ?>
                        </div>
                    </div>
                </div>
                <!--/product tile-->
            <?php } ?>
        </div>
        <?php
        $searchFunction = 'goToProductListingSearchPage';
        if (isset($pagingFunc)) {
            $searchFunction = $pagingFunc;
        }

        $postedData['page'] = (isset($page)) ? $page : 1;
        $postedData['recordDisplayCount'] = $recordCount;
        $postedData['pageRecordCount'] = FilterHelper::encrypt($recordCount);
        echo FatUtility::createHiddenFormFromData($postedData, array('name' => 'frmProductSearchPaging', 'id' => 'frmProductSearchPaging'));
        $pagingArr = array('pageCount' => $pageCount, 'page' => $postedData['page'], 'recordCount' => $recordCount, 'callBackJsFunc' => $searchFunction);
        $itemsPerPage = FatApp::getConfig('CONF_ITEMS_PER_PAGE_CATALOG', FatUtility::VAR_INT, 10);
        if ($itemsPerPage < $recordCount) { ?>
            <div class="collection-pager">
                <?php
                $this->includeTemplate('_partial/pagination.php', $pagingArr, false);
                if (!isset($removePageSize)) { ?>
                    <select name="pageSizeSelect" id="pageSizeSelect" class="custom-select sorting-select">
                        <?php foreach ($pageSizeArr as $key => $val) { ?>
                            <option value="<?php echo $key; ?>" <?php echo ($key == $pageSize) ? 'selected' : ''; ?>>
                                <?php echo $val; ?>
                            </option>
                            <?php
                            if ($recordCount < $key) {
                                break;
                            }
                        } ?>
                    </select>
                <?php } ?>
            </div>
        <?php }
        } else { ?>
    </div>
    <?php
    $arr['recordDisplayCount'] = $recordCount;
    $arr['pageRecordCount'] = FilterHelper::encrypt($recordCount);
    echo FatUtility::createHiddenFormFromData($arr, array('name' => 'frmProductSearchPaging', 'id' => 'frmProductSearchPaging'));
    $message = Labels::getLabel('LBL_No_Records_Found', $siteLangId);
    $this->includeTemplate('_partial/no-record-found.php', array('siteLangId' => $siteLangId, 'message' => $message));
        } ?>
<script>
    $(function () {
        var e = document.getElementById("pageSizeSelect");
        if (e != null) {
            var pageSize = e.options[e.selectedIndex].value;
            $('#pageSize').val(pageSize);
        }
    })
</script>
</div>