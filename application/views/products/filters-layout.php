<aside class="collection-sidebar" id="collection-sidebar" data-sidebar="collection-sidebar">
    <?php if (isset($shop)) { ?>
    <div class="shop-information">
        <div class="shop-logo">
            <?php
                $fileData = AttachedFile::getAttachment(AttachedFile::FILETYPE_SHOP_LOGO, $shop['shop_id'], 0, 0, false);
                $aspectRatioArr = AttachedFile::getRatioTypeArray($siteLangId);
                ?>
            <img src="<?php echo UrlHelper::generateUrl('image', 'shopLogo', array($shop['shop_id'], $siteLangId, ImageDimension::VIEW_THUMB)); ?>"
                alt="<?php echo $shop['shop_name']; ?>"
                <?php echo HtmlHelper::getImgDimParm(ImageDimension::TYPE_SHOP_LOGO, ImageDimension::VIEW_THUMB); ?>>
        </div>

        <div class="shop-info">
            <div class="shop-detail">
                <h6 class="shop-title">
                    <?php echo $shop['shop_name']; ?>
                    <span class="blk-txt"><?php echo Labels::getLabel('LBL_Shop_Opened_On', $siteLangId); ?> <strong>
                            <?php $date = new DateTime($shop['user_regdate']);
                                echo $date->format('M d, Y'); ?>
                        </strong></span>
                </h6>
                <?php if (0 < FatApp::getConfig("CONF_ALLOW_REVIEWS", FatUtility::VAR_INT, 0)) { ?>
                <div class="product-ratings">
                    <i class="icn">
                        <svg class="svg svg-star" width="14" height="14">
                            <use xlink:href="<?php echo CONF_WEBROOT_URL; ?>images/retina/sprite.svg<?php echo AttachedFile::setTimeParam(RELEASE_DATE); ?>#star-yellow"></use>
                        </svg></i>
                    <span class="rate">
                        <?php echo round($shopRating, 1), ' ', Labels::getLabel('Lbl_Out_of', $siteLangId), ' ', '5';
                                if ($shopTotalReviews) { ?>
                        - <a
                            href="<?php echo UrlHelper::generateUrl('Reviews', 'shop', array($shop['shop_id'])); ?>"><?php echo $shopTotalReviews, ' ', Labels::getLabel('Lbl_Reviews', $siteLangId); ?></a>
                        <?php } ?>
                    </span>
                </div>
                <?php } ?>
                <!-- Shop Badge  -->
                <?php
                    $badgesArr = Badge::getShopBadges($siteLangId, [$shop['shop_id']]);
                    $this->includeTemplate('_partial/badge-ui.php', ['badgesArr' => $badgesArr, 'siteLangId' => $siteLangId], false);
                    ?>
            </div>
            <div class="shop-btn-group">
                <div class="dropdown">
                    <a class="dropdown-toggle no-after share-icon" href="javascript:void(0)" data-bs-toggle="dropdown"
                        data-bs-auto-close="outside">
                        <i class="icn" title="<?php echo Labels::getLabel('Lbl_Share', $siteLangId); ?>">
                            <svg class="svg" width="20" height="20">
                                <use xlink:href="<?php echo CONF_WEBROOT_URL; ?>images/retina/sprite.svg<?php echo AttachedFile::setTimeParam(RELEASE_DATE); ?>#share"></use>
                            </svg>
                        </i></a>
                    <div class="dropdown-menu dropdown-menu-anim">
                        <ul class="social-sharing">
                            <li class="social-facebook">
                                <a href="javascript:void(0)" class="social-link st-custom-button"
                                    data-network="facebook"
                                    data-url="<?php echo UrlHelper::generateFullUrl('Shops', 'view', array($shop['shop_id'])); ?>/">
                                    <svg class="svg" width="20" height="20">
                                        <use
                                            xlink:href="<?php echo CONF_WEBROOT_URL; ?>images/retina/sprite.svg<?php echo AttachedFile::setTimeParam(RELEASE_DATE); ?>#facebook">
                                        </use>
                                    </svg>
                                </a>
                            </li>
                            <li class="social-twitter">
                                <a href="javascript:void(0)" class="social-link st-custom-button"
                                    data-network="twitter">
                                    <svg class="svg" width="20" height="20">
                                        <use
                                            xlink:href="<?php echo CONF_WEBROOT_URL; ?>images/retina/sprite.svg<?php echo AttachedFile::setTimeParam(RELEASE_DATE); ?>#twitter">
                                        </use>
                                    </svg>
                                </a>
                            </li>
                            <li class="social-pintrest">
                                <a href="javascript:void(0)" class="social-link st-custom-button"
                                    data-network="pinterest">
                                    <svg class="svg" width="20" height="20">
                                        <use
                                            xlink:href="<?php echo CONF_WEBROOT_URL; ?>images/retina/sprite.svg<?php echo AttachedFile::setTimeParam(RELEASE_DATE); ?>#pinterest">
                                        </use>
                                    </svg>
                                </a>
                            </li>
                            <li class="social-email">
                                <a href="javascript:void(0)" class="social-link st-custom-button" data-network="email">
                                    <svg class="svg" width="20" height="20">
                                        <use
                                            xlink:href="<?php echo CONF_WEBROOT_URL; ?>images/retina/sprite.svg<?php echo AttachedFile::setTimeParam(RELEASE_DATE); ?>#envelope">
                                        </use>
                                    </svg>
                                </a>
                            </li>
                        </ul>
                    </div>
                </div>
                <?php $showAddToFavorite = true;
                    if (UserAuthentication::isUserLogged() && (!User::isBuyer())) {
                        $showAddToFavorite = false;
                    }
                    ?>
                <?php if ($showAddToFavorite) { ?>
                <a href="javascript:void(0)"
                    title="<?php echo ($shop['is_favorite']) ? Labels::getLabel('Lbl_Unfavorite_Shop', $siteLangId) : Labels::getLabel('Lbl_Favorite_Shop', $siteLangId); ?>"
                    onclick="toggleShopFavorite(<?php echo $shop['shop_id']; ?>);"
                    class="btn btn-brand btn-sm <?php echo ($shop['is_favorite']) ? 'is-active' : ''; ?>"
                    id="shop_<?php echo $shop['shop_id']; ?>"><i class="icn">
                        <svg class="svg">
                            <use xlink:href="<?php echo CONF_WEBROOT_URL; ?>images/retina/sprite.svg<?php echo AttachedFile::setTimeParam(RELEASE_DATE); ?>#heart"></use>
                        </svg></i></a>
                <?php } ?>
                <?php $showMoreButtons = true;
                    if (isset($userParentId) && $userParentId == $shop['shop_user_id']) {
                        $showMoreButtons = false;
                    } ?>
                <?php if ($showMoreButtons) {
                        $shopRepData = ShopReport::getReportDetail($shop['shop_id'], UserAuthentication::getLoggedUserId(true), 'sreport_id');
                        if (false === UserAuthentication::isUserLogged() || empty($shopRepData)) { ?>
                <a href="<?php echo UrlHelper::generateUrl('Shops', 'ReportSpam', array($shop['shop_id'])); ?>"
                    title="<?php echo Labels::getLabel('Lbl_Report_Spam', $siteLangId); ?>"
                    class="btn btn-brand btn-sm"><i class="icn"><svg class="svg">
                            <use xlink:href="<?php echo CONF_WEBROOT_URL; ?>images/retina/sprite.svg<?php echo AttachedFile::setTimeParam(RELEASE_DATE); ?>#report"
                                href="<?php echo CONF_WEBROOT_URL; ?>images/retina/sprite.svg<?php echo AttachedFile::setTimeParam(RELEASE_DATE); ?>#report"></use>
                        </svg></i></a>
                <?php } ?>
                <?php if (!UserAuthentication::isUserLogged() || (UserAuthentication::isUserLogged() && ((User::isBuyer()) || (User::isSeller())))) { ?>
                <a href="<?php echo UrlHelper::generateUrl('shops', 'sendMessage', array($shop['shop_id'])); ?>"
                    title="<?php echo Labels::getLabel('Lbl_Send_Message', $siteLangId); ?>"
                    class="btn btn-brand btn-sm"><i class="icn"><svg class="svg">
                            <use xlink:href="<?php echo CONF_WEBROOT_URL; ?>images/retina/sprite.svg<?php echo AttachedFile::setTimeParam(RELEASE_DATE); ?>#send-msg"
                                href="<?php echo CONF_WEBROOT_URL; ?>images/retina/sprite.svg<?php echo AttachedFile::setTimeParam(RELEASE_DATE); ?>#send-msg"></use>
                        </svg></i></a>
                <?php } ?>
                <?php } ?>
            </div>
            <?php if ($socialPlatforms) { ?>
            <div class="social-profiles">
                <p><strong><?php echo Labels::getLabel('LBL_Follow_Us', $siteLangId); ?></strong> </p>
                <ul class="social-icons">
                    <?php foreach ($socialPlatforms as $row) { ?>
                    <li>
                        <a <?php if ($row['splatform_url'] != '') { ?> target="_blank" <?php } ?>
                            href="<?php echo ($row['splatform_url'] != '') ? $row['splatform_url'] : 'javascript:void(0)'; ?>"><i
                                class="fab fa-<?php echo $row['splatform_icon_class']; ?>"></i>
                        </a>
                    </li>
                    <?php } ?>
                </ul>
            </div>
            <?php } ?>
        </div>
    </div>

    <?php } ?>
    <?php if (array_key_exists('brand_id', $postedData) && $postedData['brand_id'] > 0) {
        ?>
    <div class="shop-information">
        <div class="shop-logo">
            <?php
                $fileData = AttachedFile::getAttachment(AttachedFile::FILETYPE_BRAND_LOGO, $postedData['brand_id'], 0, 0, false);
                $aspectRatioArr = AttachedFile::getRatioTypeArray($siteLangId);
                ?>
            <img <?php if ($fileData['afile_aspect_ratio'] > 0) { ?>
                data-ratio="<?php echo $aspectRatioArr[$fileData['afile_aspect_ratio']]; ?>" <?php } ?>
                src="<?php echo UrlHelper::getCachedUrl(UrlHelper::generateFileUrl('image', 'brand', array($postedData['brand_id'], $siteLangId, ImageDimension::VIEW_LISTING_PAGE)), CONF_IMG_CACHE_TIME, '.jpg'); ?>"
                alt="<?php echo (!empty($fileData['afile_attribute_alt'])) ? $fileData['afile_attribute_alt'] : $pageTitle; ?>"
                title="<?php echo (!empty($fileData['afile_attribute_alt'])) ? $fileData['afile_attribute_alt'] : $pageTitle; ?>">
        </div>
    </div> <?php
    } ?>
    <div class="filters">
        <div class="filters__ele">
            <div class="productFiltersJs"></div>
            <div class="otherFiltersJs"></div>
        </div>
    </div>
</aside>