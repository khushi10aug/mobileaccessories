<?php
$quickSearch = $quickSearch ?? false;
$collapseClass = ($quickSearch ? 'collapsed' : 'collapse');
$quickSearchUlClass = ($quickSearch ? 'quickMenujs' : '');
$controller = strtolower($controller);
$action = strtolower($action);
?>
<ul class="dashboard-menu <?php echo $quickSearchUlClass; ?>">
    <?php if (User::canViewAdvertiserTab() && $userPrivilege->canViewPromotions(0, true)) { ?>
        <li class="dashboard-menu-item dropdownJs">
            <button class="dashboard-menu-btn menuLinkJs dropdown-toggle-custom collapsed" type="button" <?php if (false === $quickSearch) { ?>data-bs-toggle="collapse" data-bs-target="#nav-promotions" aria-expanded="true" aria-controls="collapseOne" <?php } ?> title="">
                <span class="dashboard-menu-icon">
                    <svg class="svg" width="18" height="18">
                        <use xlink:href="<?php echo CONF_WEBROOT_URL; ?>images/retina/sprite-sidebar.svg<?php echo AttachedFile::setTimeParam(RELEASE_DATE); ?>#my-promotions">
                        </use>
                    </svg>
                </span>
                <span class="dashboard-menu-head menuTitleJs">
                    <?php echo Labels::getLabel('LBL_Promotions', $siteLangId); ?>
                </span>
                <?php if (false === $quickSearch) { ?>
                    <i class="dashboard-menu-arrow dropdown-toggle-custom-arrow"></i>
                <?php } ?>
            </button>
            <ul class="menu-sub menu-sub-accordion <?php echo $collapseClass; ?>" id="nav-promotions" aria-labelledby="" data-parent="#dashboard-menu">
                <li class="menu-sub-item navItemJs">
                    <a class="menu-sub-link navLinkJs <?php echo (false === $quickSearch && $controller == 'advertiser' && ($action == 'promotions' || $action == 'viewpromotions')) ? 'is-active' : ''; ?>" title="<?php echo Labels::getLabel("LBL_My_Promotions", $siteLangId); ?>" href="<?php echo UrlHelper::generateUrl('advertiser', 'promotions'); ?>">
                        <span class="menu-item__title navTextJs"><?php echo Labels::getLabel("LBL_My_Promotions", $siteLangId); ?></span></a>
                </li>
                <li class="menu-sub-item navItemJs">
                    <a class="menu-sub-link navLinkJs <?php echo (false === $quickSearch && $controller == 'advertiser' && ($action == 'promotioncharges' || $action == 'viewpromotions')) ? 'is-active' : ''; ?>" title="<?php echo Labels::getLabel("LBL_Promotion_Charges", $siteLangId); ?>" href="<?php echo UrlHelper::generateUrl('advertiser', 'promotionCharges'); ?>">
                        <span class="menu-item__title navTextJs"><?php echo Labels::getLabel("LBL_Promotion_Charges", $siteLangId); ?></span></a>
                </li>

            </ul>
        </li>
    <?php } ?>

    <li class="dashboard-menu-item dropdownJs">
        <button class="dashboard-menu-btn menuLinkJs dropdown-toggle-custom collapsed" type="button" <?php if (false === $quickSearch) { ?>data-bs-toggle="collapse" data-bs-target="#nav-profile" aria-expanded="true" aria-controls="collapseOne" <?php } ?> title="">
            <span class="dashboard-menu-icon">
                <svg class="svg" width="18" height="18">
                    <use xlink:href="<?php echo CONF_WEBROOT_URL; ?>images/retina/sprite-sidebar.svg<?php echo AttachedFile::setTimeParam(RELEASE_DATE); ?>#my-account">
                    </use>
                </svg>
            </span>
            <span class="dashboard-menu-head menuTitleJs">
                <?php echo Labels::getLabel('LBL_Profile', $siteLangId); ?>
            </span>
            <?php if (false === $quickSearch) { ?>
                <i class="dashboard-menu-arrow dropdown-toggle-custom-arrow"></i>
            <?php } ?>
        </button>
        <ul class="menu-sub menu-sub-accordion <?php echo $collapseClass; ?>" id="nav-profile" aria-labelledby="" data-parent="#dashboard-menu">
            <li class="menu-sub-item navItemJs">
                <a class="menu-sub-link navLinkJs <?php echo (false === $quickSearch && $controller == 'account' && $action == 'profileinfo') ? 'is-active' : ''; ?>" title="<?php echo Labels::getLabel("LBL_My_Account", $siteLangId); ?>" href="<?php echo UrlHelper::generateUrl('Account', 'ProfileInfo'); ?>">
                    <span class="menu-item__title navTextJs"><?php echo Labels::getLabel("LBL_My_Account", $siteLangId); ?></span></a>
            </li>
            <?php if ($userParentId == UserAuthentication::getLoggedUserId()) { ?>
                <li class="menu-sub-item navItemJs">
                    <a class="menu-sub-link navLinkJs <?php echo (false === $quickSearch && $controller == 'account' && $action == 'credits') ? 'is-active' : ''; ?>" title="<?php echo Labels::getLabel("LBL_My_Credits", $siteLangId); ?>" href="<?php echo UrlHelper::generateUrl('Account', 'credits'); ?>">
                        <span class="menu-item__title navTextJs"><?php echo Labels::getLabel('LBL_My_Credits', $siteLangId); ?></span></a>
                </li>
            <?php } ?>
            <?php if (!User::isAffiliate()) { ?>
                <li class="menu-sub-item navItemJs">
                    <a class="menu-sub-link navLinkJs <?php echo (false === $quickSearch && $controller == 'account' && ($action == 'bankInfoForm')) ? 'active' : ''; ?>" title="<?php echo Labels::getLabel('LBL_BANK_ACCOUNT', $siteLangId); ?>" href="<?php echo UrlHelper::generateUrl('account', 'bankInfoForm'); ?>">
                        <span class="menu-sub-title"><?php echo Labels::getLabel("LBL_BANK_ACCOUNT", $siteLangId); ?></span>
                    </a>
                </li>
            <?php } ?>

            <?php if (FatApp::getConfig('CONF_ENABLE_COOKIES', FatUtility::VAR_INT, 1)) { ?>
                <li class="menu-sub-item navItemJs">
                    <a class="menu-sub-link navLinkJs <?php echo (false === $quickSearch && $controller == 'account' && ($action == 'cookiesPreferencesForm')) ? 'active' : ''; ?>" title="<?php echo Labels::getLabel('LBL_COOKIE_PREFERENCES', $siteLangId); ?>" href="<?php echo UrlHelper::generateUrl('account', 'cookiesPreferencesForm'); ?>">
                        <span class="menu-sub-title"><?php echo Labels::getLabel("LBL_COOKIE_PREFERENCES", $siteLangId); ?></span>
                    </a>
                </li>
            <?php } ?>
            <li class="menu-sub-item navItemJs">
                <a class="menu-sub-link navLinkJs <?php echo (false === $quickSearch && $controller == 'account' && $action == 'changeemailpassword') ? 'is-active' : ''; ?>" title="<?php echo Labels::getLabel("LBL_UPDATE_CREDENTIALS", $siteLangId); ?>" href="<?php echo UrlHelper::generateUrl('Account', 'changeEmailPassword'); ?>">
                    <span class="menu-item__title navTextJs"><?php echo Labels::getLabel('LBL_UPDATE_CREDENTIALS', $siteLangId); ?></span></a>
            </li>
        </ul>
    </li>

    <?php $this->includeTemplate('_partial/dashboardLanguageArea.php', [
        'quickSearch' => $quickSearch,
        'collapseClass' => $collapseClass,
        'quickSearchUlClass' => $quickSearchUlClass,
    ]); ?>

    <?php if ($quickSearch) { ?>
        <li class="noResultsFoundJs" style="display: none;">
            <?php $this->includeTemplate('_partial/no-record-found.php', [], false) ?>
        </li>
    <?php } ?>
</ul>