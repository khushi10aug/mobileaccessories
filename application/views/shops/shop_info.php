<?php if (isset($shop)) { ?>
<div class="shop-information">
    <div class="shop-information-start">
        <div class="shop-information-logo">
            <img src="<?php echo UrlHelper::generateFileUrl('image', 'shopLogo', array($shop['shop_id'], $siteLangId, ImageDimension::VIEW_THUMB)); ?>"
                alt="<?php echo $shop['shop_name']; ?>"
                <?php echo HtmlHelper::getImgDimParm(ImageDimension::TYPE_SHOP_LOGO, ImageDimension::VIEW_THUMB); ?>>
        </div>
        <div class="shop-information-detail">
            <h6 class="title">
                <?php echo $shop['shop_name']; ?>
                <?php
                    $badgesArr = Badge::getShopBadges($siteLangId, [$shop['shop_id']]);
                    $this->includeTemplate('_partial/badge-ui.php', ['badgesArr' => $badgesArr, 'siteLangId' => $siteLangId], false);
                    ?>

            </h6> <span class="blk-txt">
                <?php echo Labels::getLabel('LBL_Shop_Opened_On', $siteLangId); ?> <strong>
                    <?php $date = new DateTime($shop['user_regdate']);
                        echo $date->format('M d, Y'); ?>
                </strong>
            </span>
            <?php if (0 < FatApp::getConfig("CONF_ALLOW_REVIEWS", FatUtility::VAR_INT, 0) && $shopTotalReviews > 0) { ?>
            <div class="product-ratings">
                <svg class="svg svg-star" width="14" height="14">
                    <use xlink:href="<?php echo CONF_WEBROOT_URL; ?>images/retina/sprite.svg<?php echo AttachedFile::setTimeParam(RELEASE_DATE); ?>#star-yellow"></use>
                </svg>
                <span class="rate">
                    <?php echo round($shopRating, 1), ' ', Labels::getLabel('Lbl_Out_of', $siteLangId), ' ', '5'; ?>
                    - <a class="link-underline"
                        href="<?php echo UrlHelper::generateUrl('Reviews', 'shop', array($shop['shop_id'])); ?>"><?php echo $shopTotalReviews, ' ', Labels::getLabel('Lbl_Reviews', $siteLangId); ?></a>
                </span>
            </div>
            <?php } ?>
        </div>
    </div>

    <div class="shop-information-end">
        <ul class="contact-social">
            <li class="contact-social-item">
                <a class="contact-social-link" href="javascript:void(0)" data-bs-toggle="modal"
                    data-bs-target="#shareIcon">
                    <svg class="svg" width="20" height="20">
                        <use xlink:href="<?php echo CONF_WEBROOT_URL; ?>images/retina/sprite.svg<?php echo AttachedFile::setTimeParam(RELEASE_DATE); ?>#share"></use>
                    </svg>
                </a>
            </li>
            <?php $showAddToFavorite = true;
                if (UserAuthentication::isUserLogged() && (!User::isBuyer())) {
                    $showAddToFavorite = false;
                }
                ?>
            <?php if ($showAddToFavorite) { ?>
            <li class="contact-social-item">
                <a class="contact-social-link <?php echo ($shop['is_favorite']) ? 'active' : ''; ?>"
                    title="<?php echo ($shop['is_favorite']) ? Labels::getLabel('Lbl_Unfavorite_Shop', $siteLangId) : Labels::getLabel('Lbl_Favorite_Shop', $siteLangId); ?>"
                    onclick="toggleShopFavorite(<?php echo $shop['shop_id']; ?>);"
                    id="shop_<?php echo $shop['shop_id']; ?>">
                    <svg class="svg" width="20" height="20">
                        <use xlink:href="<?php echo CONF_WEBROOT_URL; ?>images/retina/sprite.svg<?php echo AttachedFile::setTimeParam(RELEASE_DATE); ?>#heart"></use>
                    </svg>
                </a>
            </li>
            <?php } ?>
            <?php $showMoreButtons = true;
                if (isset($userParentId) && $userParentId == $shop['shop_user_id']) {
                    $showMoreButtons = false;
                } ?>
            <?php if ($showMoreButtons) {
                    $shopRepData = ShopReport::getReportDetail($shop['shop_id'], UserAuthentication::getLoggedUserId(true), 'sreport_id');
                    if (false === UserAuthentication::isUserLogged() || empty($shopRepData)) { ?>
            <li class="contact-social-item">
                <a class="contact-social-link"
                    href="<?php echo UrlHelper::generateUrl('Shops', 'ReportSpam', array($shop['shop_id'])); ?>"
                    title="<?php echo Labels::getLabel('Lbl_Report_Spam', $siteLangId); ?>">
                    <svg class="svg" width="20" height="20">
                        <use xlink:href="<?php echo CONF_WEBROOT_URL; ?>images/retina/sprite.svg<?php echo AttachedFile::setTimeParam(RELEASE_DATE); ?>#report"></use>
                    </svg>

                </a>
            </li>
            <?php } ?>
            <?php if (!UserAuthentication::isUserLogged() || (UserAuthentication::isUserLogged() && ((User::isBuyer()) || (User::isSeller())))) { ?>
            <li class="contact-social-item">
                <a class="contact-social-link"
                    href="<?php echo UrlHelper::generateUrl('shops', 'sendMessage', array($shop['shop_id'])); ?>"
                    title="<?php echo Labels::getLabel('Lbl_Send_Message', $siteLangId); ?>">
                    <svg class="svg" width="20" height="20">
                        <use xlink:href="<?php echo CONF_WEBROOT_URL; ?>images/retina/sprite.svg<?php echo AttachedFile::setTimeParam(RELEASE_DATE); ?>#send-msg"></use>
                    </svg> </a>
            </li>
            <?php } ?>
            <?php }

                if ($socialPlatforms) {
                    foreach ($socialPlatforms as $row) { ?>
            <li class="contact-social-item">
                <a class="contact-social-link" <?php if ($row['splatform_url'] != '') { ?> target="_blank" <?php } ?>
                    href="<?php echo ($row['splatform_url'] != '') ? $row['splatform_url'] : 'javascript:void(0)'; ?>">
                    <svg class="svg" width="20" height="20">
                        <use
                            xlink:href="<?php echo CONF_WEBROOT_URL; ?>images/retina/sprite.svg<?php echo AttachedFile::setTimeParam(RELEASE_DATE); ?>#<?php echo $row['splatform_icon_class']; ?>">
                        </use>
                    </svg>
                </a>
            </li>
            <?php }
                } ?>
        </ul>
    </div>
</div>
<?php } ?>
<div class="modal fade" id="shareIcon" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header border-bottom-0">
                <h5 class="modal-title"> </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="share-wrap">
                    <h6><?php echo Labels::getLabel('Lbl_Share_this_via', $siteLangId); ?></h6>
                    <ul class="social-sharing">
                        <li class="social-facebook">
                            <a href="javascript:void(0)" class="social-link st-custom-button" data-network="facebook"
                                data-url="<?php echo UrlHelper::generateFullUrl('Shops', 'view', array($shop['shop_id'])); ?>/">
                                <svg class="svg" width="20" height="20">
                                    <use xlink:href="<?php echo CONF_WEBROOT_URL; ?>images/retina/sprite.svg<?php echo AttachedFile::setTimeParam(RELEASE_DATE); ?>#facebook">
                                    </use>
                                </svg>
                            </a>
                        </li>
                        <li class="social-twitter">
                            <a href="javascript:void(0)" class="social-link st-custom-button" data-network="twitter">
                                <svg class="svg" width="20" height="20">
                                    <use xlink:href="<?php echo CONF_WEBROOT_URL; ?>images/retina/sprite.svg<?php echo AttachedFile::setTimeParam(RELEASE_DATE); ?>#twitter">
                                    </use>
                                </svg>
                            </a>
                        </li>
                        <li class="social-pintrest">
                            <a href="javascript:void(0)" class="social-link st-custom-button" data-network="pinterest">
                                <svg class="svg" width="20" height="20">
                                    <use xlink:href="<?php echo CONF_WEBROOT_URL; ?>images/retina/sprite.#pinterest">
                                    </use>
                                </svg>
                            </a>
                        </li>
                        <li class="social-email">
                            <a href="javascript:void(0)" class="social-link st-custom-button" data-network="email">
                                <svg class="svg" width="20" height="20">
                                    <use xlink:href="<?php echo CONF_WEBROOT_URL; ?>images/retina/sprite.svg<?php echo AttachedFile::setTimeParam(RELEASE_DATE); ?>#envelope">
                                    </use>
                                </svg>
                            </a>
                        </li>
                    </ul>
                    <div class="gap"></div>
                    <h6><?php echo Labels::getLabel('MSG_OR_COPY_LINK', $siteLangId); ?></h6>
                    <div class="clipboard">
                        <span
                            class="copy-input clipboardTextJs"><?php echo UrlHelper::generateFullUrl('shops', 'view', array($shop['shop_id']), CONF_WEBROOT_FRONT_URL); ?></span>
                        <button class="copy-btn" type="button" onclick="copyText(this, true)" data-bs-toggle="tooltip"
                            data-placement="top"
                            title="<?php echo Labels::getLabel('MSG_COPY_TO_CLIPBOARD', $siteLangId); ?>">
                            <svg class="svg" width="18" height="18">
                                <use
                                    xlink:href="<?php echo  CONF_WEBROOT_FRONTEND; ?>images/retina/sprite.svg<?php echo AttachedFile::setTimeParam(RELEASE_DATE); ?>#copy-to-all">
                                </use>
                            </svg>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<script>
$('#shareIcon').insertAfter("#body");
</script>