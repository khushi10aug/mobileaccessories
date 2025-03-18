<?php
if ($allShops) {
    foreach ($allShops as $shop) {
        $contentString = '
                <ul class="gmap-list">
                    <li  class="gmap-list-item">
                        <div class="product-profile">
                            <div class="product-profile-thumbnail"><img class="product-img" src="' . UrlHelper::getCachedUrl(UrlHelper::generateFileUrl('image', 'shopLogo', array($shop['shop_id'], $siteLangId, ImageDimension::VIEW_MINI, 0, false), CONF_WEBROOT_URL), CONF_IMG_CACHE_TIME, '.jpg') . '" alt="' . $shop['shop_name'] . '" ' . HtmlHelper::getImgDimParm(ImageDimension::TYPE_SHOP_LOGO, ImageDimension::VIEW_MINI) . '></div>
                            <div class="product-profile-data"><div class="title"><a href="' . UrlHelper::generateUrl('shops', 'view', array($shop['shop_id']), '', null, false, false, true, true) . '"><strong>' . $shop['shop_name'] . '</strong></a></div></div>
                        </div>
                    </li>
                </ul>';

        $markers[$shop['shop_id']] = [
            'lat' => $shop['shop_lat'],
            'lng' => $shop['shop_lng'],
            'content' => $contentString,
            'title' => Labels::getLabel('LBL_SHOP_INFO', $siteLangId)
        ];
?>
        <li class="stores-item" data-shopId="<?php echo $shop['shop_id']; ?>">
            <a class="stores-link"
                href="<?php echo UrlHelper::generateUrl('shops', 'view', array($shop['shop_id']), '', null, false, false, true, true); ?>">
                <div class="stores-photo">
                    <img src="<?php echo UrlHelper::getCachedUrl(UrlHelper::generateFileUrl('image', 'shopLogo', array($shop['shop_id'], $siteLangId, ImageDimension::VIEW_THUMB, 0, false), CONF_WEBROOT_URL), CONF_IMG_CACHE_TIME, '.jpg'); ?>"
                        alt="<?php echo $shop['shop_name']; ?>"
                        <?php echo HtmlHelper::getImgDimParm(ImageDimension::TYPE_SHOP_LOGO, ImageDimension::VIEW_THUMB); ?>>
                </div>
                <div class="stores-detail">
                    <h6 class="stores-name"><?php echo $shop['shop_name']; ?></h6>
                    <p class="stores-location">
                        <?php echo $shop['state_name']; ?>
                        <?php echo ($shop['country_name'] && $shop['state_name']) ? ', ' : ''; ?>
                        <?php echo $shop['country_name']; ?>
                    </p>
                    <div class="stores-foot">
                        <?php if (0 < FatApp::getConfig("CONF_ALLOW_REVIEWS", FatUtility::VAR_INT, 0) && round($shop['shopRating']) > 0) { ?>
                            <div class="product-ratings">
                                <svg class="svg svg-star" width="14" height="14">
                                    <use xlink:href="<?php echo CONF_WEBROOT_URL; ?>images/retina/sprite.svg<?php echo AttachedFile::setTimeParam(RELEASE_DATE); ?>#star-yellow">
                                    </use>
                                </svg>
                                <span class="rate"><?php echo round($shop['shopRating'], 1); ?> </span>
                            </div>
                        <?php } ?>
                    </div>
                </div>
            </a>
        </li>
    <?php } ?>
<?php } else {
    $this->includeTemplate('_partial/no-record-found.php', array('siteLangId' => $siteLangId), false);
}
$postedData['page'] = (isset($page)) ? $page : 1;
$postedData['recordDisplayCount'] = $recordCount;
$postedData['viewType'] = 'popupShops';
echo FatUtility::createHiddenFormFromData($postedData, array('name' => 'frmSearchShopsPagingMap', 'id' => 'frmSearchShopsPagingMap'));
$pagingArr = array('pageCount' => $pageCount, 'page' => $postedData['page'], 'recordCount' => $recordCount, 'callBackJsFunc' => 'goToShopSearchPage');
$itemsPerPage = FatApp::getConfig('CONF_PAGE_SIZE', FatUtility::VAR_INT, 10); ?>
<?php
if ($pageSize < $recordCount) { ?>
    <div class="collection-pager">
        <?php
        $this->includeTemplate('_partial/pagination.php', $pagingArr, false);
        if (!isset($removePageSize)) { ?>
            <select name="pageSizeSelectMap" id="pageSizeSelectMap" class="custom-select sorting-select">
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
    </div>
<?php }
    } ?>
<script>
    var markers = <?php echo json_encode($markers); ?>;
</script>