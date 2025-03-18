<div class="wrapper">
    <?php if (FatApp::getConfig('CONF_LOADER', FatUtility::VAR_INT, 0)) { ?>
        <div class="page-loader">
            <span><?php echo Labels::getLabel('LBL_Loading...'); ?><i class="loader-line"></i></span>
        </div>
    <?php } ?>
    <!--header start here-->
    <header id="header"
        class="header <?php echo (FatApp::getConfig('CONF_HEADER_FULL_WIDTH', FatUtility::VAR_INT, 1) ? 'fluid' : '') ?> no-print">
        <?php if (FatApp::getConfig('CONF_AUTO_RESTORE_ON', FatUtility::VAR_INT, 1) && CommonHelper::demoUrl()) {
            $this->includeTemplate('restore-system/top-header.php');
        } ?>

        <div class="top-bar no-print">
            <div class="container">
                <div class="top-bar__inner">
                    <div class="top-bar__left">
                        <?php
                        $imgDataType = '';
                        $logoWidth = '';
                        $fileData = AttachedFile::getAttachment(AttachedFile::FILETYPE_FRONT_LOGO, 0, 0, $siteLangId, false);
                        $uploadedTime = AttachedFile::setTimeParam($fileData['afile_updated_at']);
                        if (AttachedFile::FILE_ATTACHMENT_TYPE_SVG == $fileData['afile_attachment_type']) {
                            $siteLogo = UrlHelper::getStaticImageUrl($fileData['afile_physical_path']) . $uploadedTime;
                            $imgDataType = 'data-type="svg"';
                            $logoWidth = 'width="120"';
                        } else {
                            $aspectRatioArr = AttachedFile::getRatioTypeArray($siteLangId);
                            $siteLogo = UrlHelper::getCachedUrl(UrlHelper::generateFullFileUrl('Image', 'siteLogo', array($siteLangId), CONF_WEBROOT_FRONT_URL) . $uploadedTime, CONF_IMG_CACHE_TIME, '.jpg');
                        }
                        ?>
                        <div class="logo" <?php echo $imgDataType; ?>>
                            <a href="<?php echo UrlHelper::generateUrl('', '', [], CONF_WEBROOT_FRONTEND); ?>">
                                <img <?php if (AttachedFile::FILE_ATTACHMENT_TYPE_OTHER == $fileData['afile_attachment_type'] && $fileData['afile_aspect_ratio'] > 0) { ?>
                                    data-ratio="<?php echo $aspectRatioArr[$fileData['afile_aspect_ratio']]; ?>"
                                    <?php } ?> src="<?php echo $siteLogo; ?>"
                                    alt="<?php echo FatApp::getConfig('CONF_WEBSITE_NAME_' . $siteLangId, FatUtility::VAR_STRING, '') ?>"
                                    title="<?php echo FatApp::getConfig('CONF_WEBSITE_NAME_' . $siteLangId, FatUtility::VAR_STRING, '') ?>" <?php echo $logoWidth; ?> />
                            </a>
                        </div>
                        <?php
                        $diplayGeoLocation = ($controllerName != 'Cart') ? true : false;
                        if ($controllerName == 'Cart' && !isset($_COOKIE['_ykGeoLat'])) {
                            $diplayGeoLocation = true;
                        }

                        if (FatApp::getConfig('CONF_ENABLE_GEO_LOCATION', FatUtility::VAR_INT, 0) && !empty(FatApp::getConfig('CONF_GOOGLEMAP_API_KEY', FatUtility::VAR_STRING, '')) && $diplayGeoLocation) { ?>
                            <div class="geo-location">
                                <div class="geo-location_inner">
                                    <div class="dropdown">
                                        <?php
                                        $geoAddress = '';
                                        if ((!isset($_COOKIE['_ykGeoLat']) || !isset($_COOKIE['_ykGeoLng']) || !isset($_COOKIE['_ykGeoCountryCode'])) && FatApp::getConfig('CONF_DEFAULT_GEO_LOCATION', FatUtility::VAR_INT, 0)) {
                                            $geoAddress = FatApp::getConfig('CONF_GEO_DEFAULT_ADDR', FatUtility::VAR_STRING, '');
                                            if (empty($address)) {
                                                $address = FatApp::getConfig('CONF_GEO_DEFAULT_ZIPCODE', FatUtility::VAR_INT, 0) . '-' . FatApp::getConfig('CONF_GEO_DEFAULT_STATE', FatUtility::VAR_STRING, '');
                                            }
                                        }
                                        if (empty($geoAddress)) {
                                            $geoAddress = Labels::getLabel("LBL_Location", $siteLangId);
                                        }
                                        $geoAddress =  isset($_COOKIE["_ykGeoAddress"]) ? $_COOKIE["_ykGeoAddress"] : $geoAddress;
                                        ?>
                                        <button class="button-geo-location geo-location_trigger" type="button"
                                            onclick="setGeoLocation()">

                                            <svg class="svg" width="18" height="18">
                                                <use
                                                    xlink:href="<?php echo CONF_WEBROOT_URL; ?>images/retina/sprite-header.svg<?php echo AttachedFile::setTimeParam(RELEASE_DATE); ?>#gps">
                                                </use>
                                            </svg>

                                            <div class="geo-location-selected">
                                                <?php echo $geoAddress; ?>
                                            </div>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        <?php }
                        $this->includeTemplate('_partial/footer-part/headerSearchFormArea.php', ['openSerachForm' => true]);
                        ?>
                    </div>
                    <div class="top-bar__right">
                        <ul class="quick-nav">

                            <?php if (0 < FatApp::getConfig('CONF_RFQ_MODULE', FatUtility::VAR_INT, 0) && 0 < FatApp::getConfig('CONF_GLOBAL_RFQ_MODULE', FatUtility::VAR_INT, 0) && (User::isBuyer(true) || !UserAuthentication::isUserLogged())) { ?>
                                <li class="quick-nav-item item-mobile">
                                    <button class="btn btn-brand btn-rfq" type="button" onclick="requestForQuoteFn(0);">
                                        <?php echo Labels::getLabel('LBL_REQUEST_FOR_QUOTE', $siteLangId); ?>
                                    </button>
                                </li>
                            <?php } ?>

                            <li class="quick-nav-item item-desktop wishListJs">
                                <button class="quick-nav-link button-store" type="button">
                                    <svg class="svg" width="20" height="20">
                                        <use
                                            xlink:href="<?php echo CONF_WEBROOT_URL; ?>images/retina/sprite-header.svg<?php echo AttachedFile::setTimeParam(RELEASE_DATE); ?>#wishlist">
                                        </use>
                                    </svg>
                                    <span
                                        class="txt"><?php echo FatApp::getConfig('CONF_ADD_FAVORITES_TO_WISHLIST', FatUtility::VAR_INT, 1) ? Labels::getLabel('NAV_WISHLIST', $siteLangId) : Labels::getLabel('LBL_FAVORITES', $siteLangId); ?></span>
                                </button>
                            </li>
                            <?php $this->includeTemplate('_partial/headerUserArea.php', ['layoutType' => applicationConstants::SCREEN_DESKTOP]); ?>
                            <li class="quick-nav-item item-mobile">
                                <button class="quick-nav-link btn-mega-search toggle--search" role="button"
                                    data-bs-backdrop="true" data-bs-toggle="offcanvas" data-bs-target="#mega-nav-search"
                                    aria-label="search">
                                    <svg class="svg" width="20" height="20">
                                        <use
                                            xlink:href="<?php echo CONF_WEBROOT_URL; ?>images/retina/sprite-header.svg<?php echo AttachedFile::setTimeParam(RELEASE_DATE); ?>#magnifying">
                                        </use>
                                    </svg>
                                </button>
                            </li>

                            <?php if ($controllerName != 'Cart' && (User::isBuyer(true) || (!UserAuthentication::isUserLogged()))) { ?>
                                <li class="quick-nav-item" id="cartSummaryJs">
                                    <button class="quick-nav-link button-cart" type="button" data-bs-toggle="offcanvas"
                                        data-bs-target="#sideCartJs">
                                        <svg class="svg" width="20" height="20">
                                            <use
                                                xlink:href="<?php echo CONF_WEBROOT_URL; ?>images/retina/sprite-header.svg<?php echo AttachedFile::setTimeParam(RELEASE_DATE); ?>#cart">
                                            </use>
                                        </svg>
                                        <span class="cart-qty">
                                            <?php
                                            $cartObj = new Cart();
                                            $qty = (Cart::CART_MAX_DISPLAY_QTY < $cartObj->countProducts()) ? Cart::CART_MAX_DISPLAY_QTY . '+' : $cartObj->countProducts();
                                            $qty = FatUtility::int($qty) - (isset($_SESSION['offer_checkout']) ? 1 : 0);
                                            echo (0 > $qty ? 0 : $qty);
                                            ?>
                                        </span>
                                        <span class="txt">
                                            <?php echo Labels::getLabel("LBL_Cart", $siteLangId); ?>
                                        </span>
                                    </button>
                                </li>
                            <?php }

                            if (FatApp::getConfig("CONF_ENABLE_ENGAGESPOT_PUSH_NOTIFICATION", FatUtility::VAR_STRING, '') && UserAuthentication::getLoggedUserId(true) > 0) {
                            ?>
                                <li class="quick-nav-item">
                                    <div class="btn-engagespot" id="engagespotUI"></div>
                                </li>
                            <?php } ?>

                        </ul>
                    </div>
                </div>
            </div>
        </div>
        <div class="main-bar no-print">
            <div class="container">
                <div class="main-bar__inner">
                    <?php $this->includeTemplate('_partial/headerNavigation.php', ['layoutType' => applicationConstants::SCREEN_DESKTOP]); ?>
                    <div class="main-bar-end">
                        <?php
                        if (CommonHelper::demoUrl()) { ?>
                            <a class="btn btn-secondary btn-cta-outline"
                                href="https://www.yo-kart.com/contact-us.html?demo-cta" rel="noopener" target="_blank"
                                title="Connect with Yo!Kart team to build a Multivendor Marketplace">Start Your
                                Marketplace</a>
                        <?php } ?>

                        <?php if ($layoutType == applicationConstants::SCREEN_DESKTOP) {
                            if (0 < FatApp::getConfig('CONF_RFQ_MODULE', FatUtility::VAR_INT, 0) && 0 < FatApp::getConfig('CONF_GLOBAL_RFQ_MODULE', FatUtility::VAR_INT, 0) && (User::isBuyer(true) || !UserAuthentication::isUserLogged())) { ?>

                                <button class="btn btn-brand btn-rfq" type="button" onclick="requestForQuoteFn(0);">
                                    <?php echo Labels::getLabel('LBL_REQUEST_FOR_QUOTE', $siteLangId); ?>
                                </button>
                        <?php }
                        } ?>
                    </div>
                </div>
            </div>
        </div>
    </header>