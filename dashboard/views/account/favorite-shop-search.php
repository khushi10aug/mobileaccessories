<?php defined('SYSTEM_INIT') or die('Invalid Usage.');

$isWishList = (0 < FatApp::getConfig('CONF_ADD_FAVORITES_TO_WISHLIST', FatUtility::VAR_INT, 1));

$label = Labels::getLabel("LBL_PRODUCTS", $siteLangId);
$function = 'searchFavouriteListItems()';
if ($isWishList) {
    $label = Labels::getLabel("LBL_WISHLIST", $siteLangId);
    $function = 'searchWishList()';
}

?>
<div class="content-body" id="listingDiv">
    <div class="card card-tabs">
        <div class="card-head">
            <nav class="nav nav-tabs">
                <a class="nav-link navLinkJs favtProductsJs" onclick="<?php echo $function; ?>"
                    href="javascript:void(0);" id="tab-wishlist">
                    <?php echo $label; ?>
                </a>
                <a class="nav-link active navLinkJs favtShopsJs" onclick="searchFavoriteShop();"
                    href="javascript:void(0);"><?php echo Labels::getLabel('LBL_Shops', $siteLangId); ?></a>
            </nav>
        </div>
        <div class="card-body">
            <?php if ($shops) { ?>
            <div class="featured">
                <?php foreach ($shops as $shop) {
                        $uploadedTime = AttachedFile::setTimeParam($shop['shop_updated_on']);
                    ?>
                <div class="featured-item">
                    <div class="featured-item__body">
                        <div class="favourite-wrapper">
                            <div class="favourite heart-wrapper is-active">
                                <a href="javascript:void(0);"
                                    onclick="toggleShopFavorite2(<?php echo $shop['shop_id']; ?>)"
                                    title="<?php echo Labels::getLabel('LBL_Unfavorite_Shop', $siteLangId); ?>">

                                </a>
                            </div>
                        </div>
                        <div class="featured_logo"><img
                                src="<?php echo UrlHelper::getCachedUrl(UrlHelper::generateFileUrl('image', 'shopLogo', array($shop['shop_id'], $siteLangId, ImageDimension::VIEW_THUMB, 0, false), CONF_WEBROOT_FRONTEND) . $uploadedTime, CONF_IMG_CACHE_TIME, '.jpg'); ?>"
                                alt="<?php echo $shop['shop_name']; ?>"></div>
                        <div class="featured_detail">
                            <div class="featured_name"><a
                                    href="<?php echo UrlHelper::generateUrl('shops', 'view', [$shop['shop_id']], CONF_WEBROOT_FRONTEND, null, false, false, true, $siteLangId); ?>"><?php echo $shop['shop_name']; ?></a>
                            </div>
                            <div class="featured_location">
                                <?php echo $shop['state_name']; ?><?php echo ($shop['country_name'] && $shop['state_name']) ? ', ' : ''; ?><?php echo $shop['country_name']; ?>
                            </div>
                        </div>

                        <!-- Shop Badge  -->
                        <?php
                                $badgesArr = Badge::getShopBadges($siteLangId, [$shop['shop_id']]);
                                $this->includeTemplate('_partial/badge-ui.php', ['badgesArr' => $badgesArr, 'siteLangId' => $siteLangId], false);
                                ?>
                    </div>
                    <div class="featured-item__foot">
                        <div class="featured_footer">
                            <?php if (0 < FatApp::getConfig("CONF_ALLOW_REVIEWS", FatUtility::VAR_INT, 0) && round($shop['shopRating']) > 0) { ?>
                            <div class="products__rating">
                                <svg class="svg svg-star" width="16" height="16">
                                    <use
                                        xlink:href="<?php echo CONF_WEBROOT_URL; ?>images/retina/sprite.svg<?php echo AttachedFile::setTimeParam(RELEASE_DATE); ?>#star-yellow">
                                    </use>
                                </svg>

                                <span class="rate"><?php echo  round($shop['shopRating'], 1); ?><span></span></span>
                            </div>
                            <?php } ?>
                            <a href="<?php echo UrlHelper::generateUrl('shops', 'view', [$shop['shop_id']], CONF_WEBROOT_FRONTEND, null, false, false, true, $siteLangId); ?>"
                                class="btn btn-brand btn-sm"><?php echo Labels::getLabel('LBL_Shop_Now', $siteLangId); ?></a>
                        </div>
                    </div>

                </div>
                <?php } ?>
                <?php
                    $postedData['page'] = $page;
                    echo FatUtility::createHiddenFormFromData($postedData, array('name' => 'frmFavShopSearchPaging'));

                    $pagingArr = array('pageCount' => $pageCount, 'page' => $page, 'callBackJsFunc' => 'goToFavoriteShopSearchPage');
                    $this->includeTemplate('_partial/pagination.php', $pagingArr, false);
                    ?>
            </div>
            <?php } else {
                $this->includeTemplate('_partial/no-record-found.php', array('siteLangId' => $siteLangId), false);
            } ?>
        </div>
    </div>
</div>