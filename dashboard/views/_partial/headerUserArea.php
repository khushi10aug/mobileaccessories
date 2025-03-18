<?php defined('SYSTEM_INIT') or die('Invalid Usage');

$getOrgUrl = (CONF_DEVELOPMENT_MODE) ? true : false;
global $dashboardOrgUrl;
$userActiveTab = false;
if (User::canViewSupplierTab() && (isset($_SESSION[UserAuthentication::SESSION_ELEMENT_NAME]['activeTab']) && $_SESSION[UserAuthentication::SESSION_ELEMENT_NAME]['activeTab'] == 'S')) {
    $userActiveTab = true;
    $dashboardUrl = UrlHelper::generateUrl('Seller', '', [], CONF_WEBROOT_DASHBOARD);
    $dashboardOrgUrl = UrlHelper::generateUrl('Seller', '', [], CONF_WEBROOT_DASHBOARD, null, false, $getOrgUrl);
} elseif (User::canViewBuyerTab() && (isset($_SESSION[UserAuthentication::SESSION_ELEMENT_NAME]['activeTab']) && $_SESSION[UserAuthentication::SESSION_ELEMENT_NAME]['activeTab'] == 'B')) {
    $userActiveTab = true;
    $dashboardUrl = UrlHelper::generateUrl('Buyer', '', [], CONF_WEBROOT_DASHBOARD);
    $dashboardOrgUrl = UrlHelper::generateUrl('Buyer', '', [], CONF_WEBROOT_DASHBOARD, null, false, $getOrgUrl);
} elseif (User::canViewAdvertiserTab() && (isset($_SESSION[UserAuthentication::SESSION_ELEMENT_NAME]['activeTab']) && $_SESSION[UserAuthentication::SESSION_ELEMENT_NAME]['activeTab'] == 'Ad')) {
    $userActiveTab = true;
    $dashboardUrl = UrlHelper::generateUrl('Advertiser', '', [], CONF_WEBROOT_DASHBOARD);
    $dashboardOrgUrl = UrlHelper::generateUrl('Advertiser', '', [], CONF_WEBROOT_DASHBOARD, null, false, $getOrgUrl);
} elseif (User::canViewAffiliateTab() && (isset($_SESSION[UserAuthentication::SESSION_ELEMENT_NAME]['activeTab']) && $_SESSION[UserAuthentication::SESSION_ELEMENT_NAME]['activeTab'] == 'AFFILIATE')) {
    $userActiveTab = true;
    $dashboardUrl = UrlHelper::generateUrl('Affiliate', '', [], CONF_WEBROOT_DASHBOARD);
    $dashboardOrgUrl = UrlHelper::generateUrl('Affiliate', '', [], CONF_WEBROOT_DASHBOARD, null, false, $getOrgUrl);
}
if (!$userActiveTab) {
    $dashboardUrl = UrlHelper::generateUrl('Account', '', [], CONF_WEBROOT_DASHBOARD);
    $dashboardOrgUrl = UrlHelper::generateUrl('Account', '', [], CONF_WEBROOT_DASHBOARD, null, false, $getOrgUrl);
}

if ($layoutType == applicationConstants::SCREEN_DESKTOP) { ?>
<div class="my-account dropstart">
    <button class="my-account-btn dropdown-toggle no-after" data-bs-toggle="dropdown" data-bs-auto-close="outside"
        type="button" id="my-account-target" aria-expanded="false">
        <img class="my-account-avatar" src="<?php echo $profilePicUrl; ?>" alt="<?php echo $userName; ?>">
    </button>
    <div class="dropdown-menu dropdown-menu-fit dropdown-menu-right dropdown-menu-anim my-account-target"
        aria-labelledby="my-account-target">
        <div class="profile">
            <div class="profile-img">
                <img alt="<?php echo $userName; ?>" src="<?php echo $profilePicUrl; ?>">
            </div>
            <div class="profile-detail">
                <h6 class="h6">
                    <?php echo Labels::getLabel('LBL_HI,', $siteLangId) . ' ' . htmlspecialchars($_SESSION[UserAuthentication::SESSION_ELEMENT_NAME]['user_name'], ENT_QUOTES, 'utf-8'); ?>
                </h6>
                <span class="text-muted">
                    <?php echo $_SESSION[UserAuthentication::SESSION_ELEMENT_NAME]['user_email']; ?>
                </span>
            </div>
        </div>
        <li class="divider"></li>
        <li class="dropdown-menu-item">
            <a class="dropdown-menu-link" href="<?php echo $dashboardOrgUrl; ?>">
                <svg class="svg" width="18" height="18">
                    <use xlink:href="<?php echo CONF_WEBROOT_URL; ?>images/retina/sprite.svg<?php echo AttachedFile::setTimeParam(RELEASE_DATE); ?>#icn-dashboard"></use>
                </svg><?php echo Labels::getLabel("NAV_DASHBOARD", $siteLangId); ?>
            </a>
        </li>
        <li class="dropdown-menu-item">
            <a class="dropdown-menu-link" target="_blank"
                href="<?php echo UrlHelper::generateUrl('', '', [], CONF_WEBROOT_FRONTEND); ?>"> <svg class="svg"
                    width="18" height="18">
                    <use xlink:href="<?php echo CONF_WEBROOT_URL; ?>images/retina/sprite.svg<?php echo AttachedFile::setTimeParam(RELEASE_DATE); ?>#icn-home"></use>
                </svg>
                <?php echo Labels::getLabel("NAV_HOME", $siteLangId); ?>
            </a>
        </li>
        <?php if ($isShopActive && $shop_id > 0 && $activeTab == 'S') { ?>
        <li class="dropdown-menu-item"> <a class="dropdown-menu-link"
                title="<?php echo Labels::getLabel('NAV_SHOP', $siteLangId); ?>" target="_blank"
                href="<?php echo UrlHelper::generateUrl('Shops', 'view', array($shop_id), CONF_WEBROOT_FRONTEND); ?>">
                <svg class="svg" width="18" height="18">
                    <use xlink:href="<?php echo CONF_WEBROOT_URL; ?>images/retina/sprite.svg<?php echo AttachedFile::setTimeParam(RELEASE_DATE); ?>#icn-shop">
                    </use>
                </svg><?php echo Labels::getLabel("NAV_SHOP", $siteLangId); ?>
            </a> </li>
        <?php } ?>
        <li class="dropdown-menu-item"><a class="dropdown-menu-link"
                href="<?php echo UrlHelper::generateUrl('account', 'profileInfo', [], CONF_WEBROOT_DASHBOARD); ?>">
                <svg class="svg" width="18" height="18">
                    <use xlink:href="<?php echo CONF_WEBROOT_URL; ?>images/retina/sprite.svg<?php echo AttachedFile::setTimeParam(RELEASE_DATE); ?>#icn-user"></use>
                </svg><?php echo Labels::getLabel("NAV_PROFILE", $siteLangId); ?></a> </li>

        <li class="dropdown-menu-item"> <a class="dropdown-menu-link"
                href="<?php echo UrlHelper::generateUrl('Account', 'changeEmailPassword'); ?>">
                <svg class="svg" width="18" height="18">
                    <use xlink:href="<?php echo CONF_WEBROOT_URL; ?>images/retina/sprite.svg<?php echo AttachedFile::setTimeParam(RELEASE_DATE); ?>#icn-update"></use>
                </svg><?php echo Labels::getLabel('NAV_UPDATE_CREDENTIALS', $siteLangId); ?></a> </li>

        <li class="divider"></li>
        <li class="dropdown-menu-item">
            <a class="logout-btn"
                href="<?php echo UrlHelper::generateUrl('GuestUser', 'logout', [], CONF_WEBROOT_FRONTEND, null, false, false, true, $siteLangId); ?>">
                <svg class="svg" width="18" height="18">
                    <use xlink:href="<?php echo CONF_WEBROOT_URL; ?>images/retina/sprite.svg<?php echo AttachedFile::setTimeParam(RELEASE_DATE); ?>#icn-logout"></use>
                </svg>
                <?php echo Labels::getLabel('NAV_LOGOUT', $siteLangId); ?></a>
        </li>
    </div>
</div>
<?php
} elseif ($layoutType == applicationConstants::SCREEN_MOBILE) { ?>
<div class="offcanvas offcanvas-start offcanvas-account" tabindex="-1" id="offcanvas-account">
    <div class="offcanvas-header">
        <h5 class="offcanvas-title"><?php echo Labels::getLabel('LBL_Profile', $siteLangId); ?> </h5>
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
                    <?php echo Labels::getLabel("NAV_DASHBOARD", $siteLangId); ?>
                    <i class="icon icon-arrow-right"></i>
                </a>
            </li>
            <li class="account-nav-item">
                <a class="account-nav-link"
                    href="<?php echo UrlHelper::generateUrl('', '', [], CONF_WEBROOT_FRONTEND); ?>">
                    <?php echo Labels::getLabel("NAV_HOME", $siteLangId); ?>
                    <i class="icon icon-arrow-right"></i>
                </a>
            </li>
            <?php if ($isShopActive && $shop_id > 0 && $activeTab == 'S') { ?>
            <li class="account-nav-item">
                <a class="account-nav-link"
                    href="<?php echo UrlHelper::generateUrl('Shops', 'view', array($shop_id), CONF_WEBROOT_FRONTEND); ?>">
                    <?php echo Labels::getLabel("NAV_SHOP", $siteLangId); ?>
                    <i class="icon icon-arrow-right"></i>
                </a>
            </li>
            <?php } ?>
            <li class="account-nav-item">
                <a class="account-nav-link"
                    href="<?php echo UrlHelper::generateUrl('account', 'profileInfo', [], CONF_WEBROOT_DASHBOARD); ?>">
                    <?php echo Labels::getLabel("NAV_PROFILE", $siteLangId); ?>
                    <i class="icon icon-arrow-right"></i>
                </a>
            </li>
            <li class="account-nav-item">
                <a class="account-nav-link"
                    href="<?php echo UrlHelper::generateUrl('Account', 'changeEmailPassword'); ?>">
                    <?php echo Labels::getLabel("NAV_UPDATE_CREDENTIALS", $siteLangId); ?>
                    <i class="icon icon-arrow-right"></i>
                </a>
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