<?php
if (!empty($allShops)) {
    $i = 0;
    if (FatApp::getConfig('CONF_ENABLE_GEO_LOCATION', FatUtility::VAR_INT, 0) && !empty(FatApp::getConfig('CONF_GOOGLEMAP_API_KEY', FatUtility::VAR_STRING, '')) && 1 == $page) { ?>
        <div class="collection-search-bottom mb-4">
            <div id="top-filters" class="collection-search-toolbar hide_on_no_product">
                <ul class="page-sort">
                    <li class="page-sort-item">
                        <button
                            class="btn btn-outline-black btn-map-view <?php echo $vtype == 'map' ? 'active' : ''; ?>"
                            type="button" onclick="toogleMapView();">
                            <svg class="svg" width="16" height="16" xmlns="http://www.w3.org/2000/svg"
                                viewBox="0 0 24 24" fill="currentColor">
                                <path
                                    d="M2 5L9 2L15 5L21.303 2.2987C21.5569 2.18992 21.8508 2.30749 21.9596 2.56131C21.9862 2.62355 22 2.69056 22 2.75827V19L15 22L9 19L2.69696 21.7013C2.44314 21.8101 2.14921 21.6925 2.04043 21.4387C2.01375 21.3765 2 21.3094 2 21.2417V5ZM16 19.3955L20 17.6812V5.03308L16 6.74736V19.3955ZM14 19.2639V6.73607L10 4.73607V17.2639L14 19.2639ZM8 17.2526V4.60451L4 6.31879V18.9669L8 17.2526Z">
                                </path>
                            </svg>
                            <?php echo Labels::getLabel('LBL_MAP_VIEW', $siteLangId); ?></button>
                    </li>
                </ul>
            </div>
        </div>
    <?php }
    ?>
    <ul class="collection-shops">
        <?php
        foreach ($allShops as $shop) { ?>

            <li class="collection-shops-item">
                <div class="shop">
                    <div class="shop-head">
                        <div class="shop-logo"> <img
                                src="<?php echo UrlHelper::getCachedUrl(UrlHelper::generateFileUrl('image', 'shopLogo', array($shop['shop_id'], $siteLangId, ImageDimension::VIEW_THUMB, 0, false), CONF_WEBROOT_URL), CONF_IMG_CACHE_TIME, '.jpg'); ?>"
                                alt="<?php echo $shop['shop_name']; ?>"
                                <?php echo HtmlHelper::getImgDimParm(ImageDimension::TYPE_SHOP_LOGO, ImageDimension::VIEW_THUMB); ?>>
                        </div>
                    </div>
                    <div class="shop-body">
                        <div class="shop-title"><a
                                href="<?php echo UrlHelper::generateUrl('shops', 'view', array($shop['shop_id'])); ?>"><?php echo $shop['shop_name']; ?></a>
                        </div>
                        <div class="shop-location">
                            <?php echo $shop['state_name']; ?>
                            <?php echo ($shop['country_name'] && $shop['state_name']) ? ', ' : ''; ?>
                            <?php echo $shop['country_name']; ?>
                        </div>
                    </div>
                    <div class="shop-foot">
                        <?php
                        $badgesArr = Badge::getShopBadges($siteLangId, [$shop['shop_id']]);
                        $this->includeTemplate('_partial/badge-ui.php', ['badgesArr' => $badgesArr, 'siteLangId' => $siteLangId], false);
                        ?>
                        <?php if (0 < FatApp::getConfig("CONF_ALLOW_REVIEWS", FatUtility::VAR_INT, 0) && round($shop['shopRating']) > 0) { ?>
                            <div class="product-ratings">
                                <svg class="svg svg-star" width="14" height="14">
                                    <use xlink:href="<?php echo CONF_WEBROOT_URL; ?>images/retina/sprite.svg<?php echo AttachedFile::setTimeParam(RELEASE_DATE); ?>#star-yellow"></use>
                                </svg>
                                <span class="rate"><?php echo round($shop['shopRating'], 1); ?> </span>
                            </div>
                        <?php } ?>
                        <a class="btn btn-outline-black btn-sm"
                            href="<?php echo UrlHelper::generateUrl('shops', 'view', array($shop['shop_id']), '', null, false, false, true, true); ?>">
                            <?php echo Labels::getLabel('LBL_View_Shop', $siteLangId); ?></a>

                    </div>
                </div>
                <div class="product-wrapper">
                    <div class="row">
                        <?php
                        $displayProductNotAvailableLable = (FatApp::getConfig('CONF_ENABLE_GEO_LOCATION', FatUtility::VAR_INT, 0) && !empty(FatApp::getConfig('CONF_GOOGLEMAP_API_KEY', FatUtility::VAR_STRING, '')));

                        $tRightRibbons = $shop['tRightRibbons'];
                        foreach ($shop['products'] as $product) {
                            $selProdRibbons = [];

                            if (array_key_exists($product['selprod_id'], $tRightRibbons)) {
                                $selProdRibbons[] = $tRightRibbons[$product['selprod_id']];
                            }
                        ?>
                            <div class="col-6 col-lg-3 mb-3 mb-md-0">
                                <?php include(CONF_THEME_PATH . '_partial/collection/product-layout-1-list.php'); ?>
                            </div>
                        <?php } ?>
                    </div>
                </div>
            </li>
        <?php $i++;
        }
        ?>
    </ul>
<?php
} else {
    $this->includeTemplate('_partial/no-record-found.php', array('siteLangId' => $siteLangId), false);
}

$postedData['page'] = (isset($page)) ? $page : 1;
echo FatUtility::createHiddenFormFromData($postedData, array(
    'name' => 'frmSearchShopsPaging'
));
