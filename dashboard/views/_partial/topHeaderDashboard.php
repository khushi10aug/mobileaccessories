<?php if (FatApp::getConfig('CONF_AUTO_RESTORE_ON', FatUtility::VAR_INT, 1) && CommonHelper::demoUrl() && 'subscriptioncheckout' != strtolower($controllerName)) {
    $this->includeTemplate('restore-system/top-header.php');
    $this->includeTemplate('restore-system/page-content.php');
} ?>

<header id="header-dashboard" class="header-dashboard no-print">
    <?php if ((User::canViewSupplierTab() && User::canViewBuyerTab()) || (User::canViewSupplierTab() && User::canViewAdvertiserTab() && $userPrivilege->canViewPromotions(0, true)) || (User::canViewBuyerTab() && User::canViewAdvertiserTab()) || (User::canViewBuyerTab() && User::canViewAffiliateTab())) { ?>
        <div class="dropdown dashboard-user">
            <button class="btn dropdown-toggle-custom dropdown-toggle collapsed no-after" type="button"
                id="dashboardDropdown" data-bs-toggle="dropdown" data-bs-auto-close="outside" data-display="static"
                aria-haspopup="true" aria-expanded="false">
                <?php
                $dashboardArr = [
                    'S' => Labels::getLabel('NAV_SELLER_DASHBOARD', $siteLangId),
                    'B' => Labels::getLabel('NAV_BUYER_DASHBOARD', $siteLangId),
                    'Ad' => Labels::getLabel('NAV_ADVERTISER_DASHBOARD', $siteLangId),
                    'AFFILIATE' => Labels::getLabel('NAV_AFFILIATE_DASHBOARD', $siteLangId),
                ];
                ?>
                <span class="meta"><?php echo HtmlHelper::displayWordsFirstLetter($dashboardArr[$activeTab]); ?></span>
                <?php echo $dashboardArr[$activeTab]; ?>
                <i class="dropdown-toggle-custom-arrow"></i>
            </button>

            <ul class="dropdown-menu dropdown-menu-fit dropdown-menu-anim choose-dashboard"
                aria-labelledby="dashboardDropdown">
                <?php if (User::canViewSupplierTab()) { ?>
                    <li class="dropdown-menu-item <?php echo ($activeTab == 'S') ? 'is-active' : ''; ?>">
                        <a class="dropdown-menu-link" href="<?php echo UrlHelper::generateUrl('Seller'); ?>">
                            <div class="meta-block">
                                <span
                                    class="meta-img"><?php echo HtmlHelper::displayWordsFirstLetter($dashboardArr['S']); ?></span>
                                <?php echo $dashboardArr['S']; ?>
                            </div>

                        </a>
                    </li>
                <?php } ?>
                <?php if (User::canViewBuyerTab()) { ?>
                    <li class="dropdown-menu-item <?php echo ($activeTab == 'B') ? 'is-active' : ''; ?>">
                        <a class="dropdown-menu-link" href="<?php echo UrlHelper::generateUrl('Buyer'); ?>">
                            <div class="meta-block">
                                <span
                                    class="meta-img"><?php echo HtmlHelper::displayWordsFirstLetter($dashboardArr['B']); ?></span>
                                <?php echo $dashboardArr['B']; ?>
                            </div>
                        </a>
                    </li>
                <?php } ?>
                <?php if (User::canViewAffiliateTab()) { ?>
                    <li class="dropdown-menu-item <?php echo ($activeTab == 'AFFILIATE') ? 'is-active' : ''; ?>">
                        <a class="dropdown-menu-link" href="<?php echo UrlHelper::generateUrl('Affiliate'); ?>">
                            <div class="meta-block">
                                <span
                                    class="meta-img"><?php echo HtmlHelper::displayWordsFirstLetter($dashboardArr['AFFILIATE']); ?></span>
                                <?php echo $dashboardArr['AFFILIATE']; ?>
                            </div>
                        </a>
                    </li>
                <?php } ?>
                <?php if (User::canViewAdvertiserTab() && $userPrivilege->canViewPromotions(0, true)) { ?>
                    <li class="dropdown-menu-item <?php echo ($activeTab == 'Ad') ? 'is-active' : ''; ?>">
                        <a class="dropdown-menu-link" href="<?php echo UrlHelper::generateUrl('Advertiser'); ?>">
                            <div class="meta-block">
                                <span
                                    class="meta-img"><?php echo HtmlHelper::displayWordsFirstLetter($dashboardArr['Ad']); ?></span>
                                <?php echo $dashboardArr['Ad']; ?>
                            </div>
                        </a>
                    </li>
                <?php } ?>
            </ul>
        </div>
    <?php } ?>
    <div class="header-icons-group">

        <button class="c-header-icon quick-search" data-bs-toggle="modal" data-bs-target="#search-main">
            <svg class="svg" width="20" height="20">
                <use xlink:href="<?php echo CONF_WEBROOT_URL; ?>images/retina/sprite-header.svg<?php echo AttachedFile::setTimeParam(RELEASE_DATE); ?>#icon-search">
                </use>
            </svg>
        </button>
        <?php if ($userPrivilege->canViewMessages(0, true)) {
            if ($activeTab == 'B' || $activeTab == 'S') {
                $getOrgUrl = (CONF_DEVELOPMENT_MODE) ? true : false;
                ?>
                <a class="c-header-icon bell"
                    data-org-url="<?php echo UrlHelper::generateUrl('Account', 'Messages', array(), '', null, false, $getOrgUrl); ?>"
                    href="<?php echo UrlHelper::generateUrl('Account', 'Messages'); ?>"
                    title="<?php echo Labels::getLabel('LBL_Messages', $siteLangId); ?>">
                    <svg class="svg bell-shake-delay" width="20" height="20">
                        <use xlink:href="<?php echo CONF_WEBROOT_URL; ?>images/retina/sprite-header.svg<?php echo AttachedFile::setTimeParam(RELEASE_DATE); ?>#envelope">
                        </use>
                    </svg>
                    <span class="h-badge msg-count">
                        <?php echo CommonHelper::displayBadgeCount($todayUnreadMessageCount, 9); ?>
                    </span>
                </a>
            <?php }
        } ?>
        <?php $this->includeTemplate('_partial/headerUserArea.php', ['layoutType' => applicationConstants::SCREEN_DESKTOP]); ?>
    </div>
</header>
<div class="display-in-print text-center">
    <?php
    $fileData = AttachedFile::getAttachment(AttachedFile::FILETYPE_INVOICE_LOGO, 0, 0, $siteLangId, false);
    $aspectRatioArr = AttachedFile::getRatioTypeArray($siteLangId);
    ?>
    <img <?php if ($fileData['afile_aspect_ratio'] > 0) { ?>
            data-ratio="<?php echo $aspectRatioArr[$fileData['afile_aspect_ratio']]; ?>" <?php } ?>
        src="<?php echo UrlHelper::generateFullUrl('Image', 'invoiceLogo', array($siteLangId), CONF_WEBROOT_FRONTEND); ?>"
        alt="<?php echo FatApp::getConfig('CONF_WEBSITE_NAME_' . $siteLangId, FatUtility::VAR_STRING, '') ?>"
        title="<?php echo FatApp::getConfig('CONF_WEBSITE_NAME_' . $siteLangId, FatUtility::VAR_STRING, '') ?>">
</div>