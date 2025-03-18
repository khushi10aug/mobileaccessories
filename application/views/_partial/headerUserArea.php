<?php defined('SYSTEM_INIT') or die('Invalid Usage');
$getOrgUrl = (CONF_DEVELOPMENT_MODE) ? true : false;
$dashboardOrgUrl = '';
if (UserAuthentication::isUserLogged()) {
    $userActiveTab = false;
    if (User::canViewSupplierTab() && (isset($_SESSION[UserAuthentication::SESSION_ELEMENT_NAME]['activeTab']) && $_SESSION[UserAuthentication::SESSION_ELEMENT_NAME]['activeTab'] == 'S')) {
        $userActiveTab = true;
        $dashboardUrl = UrlHelper::generateUrl('Seller', '', [], CONF_WEBROOT_DASHBOARD, null, false, false, false);
        $dashboardOrgUrl = UrlHelper::generateUrl('Seller', '', [], CONF_WEBROOT_DASHBOARD, null, false, $getOrgUrl, false);
    } elseif (User::canViewBuyerTab() && (isset($_SESSION[UserAuthentication::SESSION_ELEMENT_NAME]['activeTab']) && $_SESSION[UserAuthentication::SESSION_ELEMENT_NAME]['activeTab'] == 'B')) {
        $userActiveTab = true;
        $dashboardUrl = UrlHelper::generateUrl('Buyer', '', [], CONF_WEBROOT_DASHBOARD, null, false, false, false);
        $dashboardOrgUrl = UrlHelper::generateUrl('Buyer', '', [], CONF_WEBROOT_DASHBOARD, null, false, $getOrgUrl, false);
    } elseif (User::canViewAdvertiserTab() && (isset($_SESSION[UserAuthentication::SESSION_ELEMENT_NAME]['activeTab']) && $_SESSION[UserAuthentication::SESSION_ELEMENT_NAME]['activeTab'] == 'Ad')) {
        $userActiveTab = true;
        $dashboardUrl = UrlHelper::generateUrl('Advertiser', '', [], CONF_WEBROOT_DASHBOARD, null, false, false, false);
        $dashboardOrgUrl = UrlHelper::generateUrl('Advertiser', '', [], CONF_WEBROOT_DASHBOARD, null, false, $getOrgUrl, false);
    } elseif (User::canViewAffiliateTab() && (isset($_SESSION[UserAuthentication::SESSION_ELEMENT_NAME]['activeTab']) && $_SESSION[UserAuthentication::SESSION_ELEMENT_NAME]['activeTab'] == 'AFFILIATE')) {
        $userActiveTab = true;
        $dashboardUrl = UrlHelper::generateUrl('Affiliate', '', [], CONF_WEBROOT_DASHBOARD, null, false, false, false);
        $dashboardOrgUrl = UrlHelper::generateUrl('Affiliate', '', [], CONF_WEBROOT_DASHBOARD, null, false, $getOrgUrl, false);
    }
    if (!$userActiveTab) {
        $dashboardUrl = UrlHelper::generateUrl('Account', '', [], CONF_WEBROOT_DASHBOARD, null, false, false, false);
        $dashboardOrgUrl = UrlHelper::generateUrl('Account', '', [], CONF_WEBROOT_DASHBOARD, null, false, $getOrgUrl, false);
    }
}

if ($layoutType == applicationConstants::SCREEN_DESKTOP) {
    /*if (0 < FatApp::getConfig('CONF_RFQ_MODULE', FatUtility::VAR_INT, 0) && (User::isBuyer(true) || !UserAuthentication::isUserLogged())) { ?>
<button class="btn btn-outline-brand btn-sm btn-rfq" type="button" onclick="requestForQuoteFn(0);">
    <?php echo Labels::getLabel('LBL_REQUEST_FOR_QUOTE', $siteLangId); ?>
</button>
<?php } */

    if (!UserAuthentication::isUserLogged()) {
        if (UserAuthentication::isGuestUserLogged()) { ?>
            <li class="quick-nav-item item-desktop">
                <div class="dropdown">
                    <button type="button" class="quick-nav-link button-account dropdown-toggle no-after" data-bs-toggle="dropdown"
                        data-bs-auto-close="outside">
                        <svg class="svg" width="20" height="20">
                            <use xlink:href="<?php echo CONF_WEBROOT_URL; ?>images/retina/sprite-header.svg<?php echo AttachedFile::setTimeParam(RELEASE_DATE); ?>#login"></use>
                        </svg>
                        <span class="txt">
                            <?php echo Labels::getLabel('LBL_Hi,', $siteLangId) . ' ' . htmlspecialchars($userName, ENT_QUOTES, 'utf-8'); ?>
                        </span>
                    </button>

                    <ul class="dropdown-menu dropdown-menu-fit dropdown-menu-right dropdown-menu-anim">
                        <li class="dropdown-menu-item">
                            <a class="dropdown-menu-link"
                                href="<?php echo UrlHelper::generateUrl('account', 'profileInfo', [], CONF_WEBROOT_DASHBOARD, null, false, false, false); ?>">
                                <?php echo Labels::getLabel('LBL_Hi,', $siteLangId) . ' ' . $userName; ?>
                            </a>
                        </li>
                        <li class="dropdown-menu-item logout">
                            <a class="dropdown-menu-link"
                                data-org-url="<?php echo UrlHelper::generateUrl('GuestUser', 'logout', [], CONF_WEBROOT_FRONTEND, null, false, $getOrgUrl, false); ?>"
                                href="<?php echo UrlHelper::generateUrl('GuestUser', 'logout', [], CONF_WEBROOT_FRONTEND); ?>">
                                <?php echo Labels::getLabel('LBL_Logout', $siteLangId); ?>
                            </a>
                        </li>
                    </ul>

                </div>
            </li>
            <?php
        } else {
            ?>
            <li class="quick-nav-item item-desktop">
                <div class="dropdown">
                    <button type="button" class="quick-nav-link button-account sign-in sign-in-popup-js">
                        <svg class="svg" width="20" height="20">
                            <use xlink:href="<?php echo CONF_WEBROOT_URL; ?>images/retina/sprite-header.svg<?php echo AttachedFile::setTimeParam(RELEASE_DATE); ?>#login"></use>
                        </svg>
                        <span class="txt">
                            <?php echo Labels::getLabel('LBL_Sign_In_/_Register', $siteLangId); ?> </span>
                    </button>
                </div>
            </li> <?php
        }
    } else { ?>
        <li class="quick-nav-item item-desktop">
            <div class="dropdown">
                <button type="button" class="quick-nav-link button-account dropdown-toggle no-after" data-bs-toggle="dropdown"
                    data-bs-auto-close="outside">
                    <svg class="svg" width="20" height="20">
                        <use xlink:href="<?php echo CONF_WEBROOT_URL; ?>images/retina/sprite-header.svg<?php echo AttachedFile::setTimeParam(RELEASE_DATE); ?>#login"></use>
                    </svg>
                    <span class="txt">
                        <?php echo Labels::getLabel('LBL_Hi,', $siteLangId) . ' ' . htmlspecialchars($_SESSION[UserAuthentication::SESSION_ELEMENT_NAME]['user_name'], ENT_QUOTES, 'utf-8'); ?></span>
                </button>

                <ul class="dropdown-menu dropdown-menu-fit dropdown-menu-right dropdown-menu-anim">
                    <li class="dropdown-menu-item">
                        <a class="dropdown-menu-link" data-org-url="<?php echo $dashboardOrgUrl; ?>"
                            href="<?php echo $dashboardUrl; ?>"><?php echo Labels::getLabel("LBL_Dashboard", $siteLangId); ?></a>
                    </li>
                    <?php
                    $this->includeTemplate('_partial/header/sellerUserArea.php', ['siteLangId' => $siteLangId]);
                    $this->includeTemplate('_partial/header/buyerUserArea.php', ['siteLangId' => $siteLangId]);
                    ?>
                    <li class="dropdown-menu-item">
                        <a class="dropdown-menu-link"
                            data-org-url="<?php echo UrlHelper::generateUrl('Account', 'ProfileInfo', [], CONF_WEBROOT_DASHBOARD, null, false, $getOrgUrl, false); ?>"
                            href="<?php echo UrlHelper::generateUrl('Account', 'ProfileInfo', [], CONF_WEBROOT_DASHBOARD, null, false, false, false); ?>"><?php echo Labels::getLabel("LBL_My_Account", $siteLangId); ?></a>
                    </li>
                    <li class="dropdown-menu-item logout">
                        <a class="dropdown-menu-link"
                            data-org-url="<?php echo UrlHelper::generateUrl('GuestUser', 'logout', array(), CONF_WEBROOT_FRONTEND, null, false, $getOrgUrl); ?>"
                            href="<?php echo UrlHelper::generateUrl('GuestUser', 'logout', [], CONF_WEBROOT_FRONTEND); ?>"><?php echo Labels::getLabel('LBL_Logout', $siteLangId); ?>
                        </a>
                    </li>
                </ul>

            </div>
        </li>
    <?php }
} elseif ($layoutType == applicationConstants::SCREEN_MOBILE) { ?>
    <div class="offcanvas offcanvas-start  offcanvas-account" tabindex="-1" id="offcanvas-account">
        <div class="offcanvas-header">
            <h5 class="offcanvas-title"><?php echo Labels::getLabel('LBL_PROFILE', $siteLangId); ?> </h5>
            <button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas" aria-label="Close"></button>
        </div>
        <div class="offcanvas-body p-0">
            <div class="profile">
                <div class="profile-image">
                    <img class="profile-avatar" width="80" height="80" src="<?php echo $profilePicUrl; ?>" alt="">
                </div>
                <div class="profile-data">
                    <h6 class="profile-name"><?php echo $userName; ?> </h6>
                    <p class="profile-email"><?php echo $userEmail; ?></p>
                    <?php
                    if (!empty($userPhone)) { ?>
                        <p class="profile-phone"><?php echo $userPhone; ?></p>
                    <?php } ?>
                </div>
            </div>
            <ul class="account-nav">
                <li class="account-nav-item">
                    <a class="account-nav-link" href="<?php echo $dashboardOrgUrl; ?>">
                        <?php echo Labels::getLabel("LBL_Dashboard", $siteLangId); ?><i class="icon icon-arrow-right"></i>
                    </a>
                </li>
                <?php
                $this->includeTemplate('_partial/header/sellerUserArea.php', ['siteLangId' => $siteLangId, 'layoutType' => applicationConstants::SCREEN_MOBILE]);
                $this->includeTemplate('_partial/header/buyerUserArea.php', ['siteLangId' => $siteLangId, 'layoutType' => applicationConstants::SCREEN_MOBILE]);
                ?>
                <li class="account-nav-item">
                    <a class="account-nav-link"
                        href="<?php echo UrlHelper::generateUrl('Account', 'ProfileInfo', [], CONF_WEBROOT_DASHBOARD); ?>"><?php echo Labels::getLabel("LBL_My_Account", $siteLangId); ?><i
                            class="icon icon-arrow-right"></i></a>
                </li>
            </ul>
        </div>
        <div class="offcanvas-foot">
            <a class="btn btn-logout"
                href="<?php echo UrlHelper::generateUrl('GuestUser', 'logout', [], CONF_WEBROOT_FRONTEND); ?>">
                <?php echo Labels::getLabel('LBL_Logout', $siteLangId); ?>
            </a>
        </div>
    </div>
<?php }