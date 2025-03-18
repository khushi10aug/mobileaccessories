<?php
$quickSearch = $quickSearch ?? false;
$collapseClass = ($quickSearch ? 'collapsed' : 'collapse');
$quickSearchUlClass = ($quickSearch ? 'quickMenujs' : '');
$controller = strtolower($controller);
$action = strtolower($action); ?>

<ul class="dashboard-menu <?php echo $quickSearchUlClass; ?>">
    <li class="dashboard-menu-item dropdownJs">
        <button class="dashboard-menu-btn menuLinkJs dropdown-toggle-custom collapsed" type="button" <?php if (false === $quickSearch) { ?> data-bs-toggle="collapse" data-bs-target="#nav-orders" aria-expanded="true" aria-controls="collapseOne" <?php } ?> title="">
            <span class="dashboard-menu-icon">
                <svg class="svg" width="18" height="18">
                    <use xlink:href="<?php echo CONF_WEBROOT_URL; ?>images/retina/sprite-sidebar.svg<?php echo AttachedFile::setTimeParam(RELEASE_DATE); ?>#order-sales">
                    </use>
                </svg>
            </span>
            <span class="dashboard-menu-head menuTitleJs">
                <?php echo Labels::getLabel('LBL_Orders', $siteLangId); ?>
            </span>

            <?php if (false === $quickSearch) { ?>
                <i class="dashboard-menu-arrow dropdown-toggle-custom-arrow"></i>
            <?php } ?>
        </button>
        <ul class="menu-sub menu-sub-accordion <?php echo $collapseClass; ?>" id="nav-orders" aria-labelledby="" data-parent="#dashboard-menu">
            <li class="menu-sub-item navItemJs">
                <a class="menu-sub-link navLinkJs <?php echo (false === $quickSearch && $controller == 'buyer' && ($action == 'orders' || $action == 'vieworder')) ? 'active' : ''; ?>" title="<?php echo Labels::getLabel("LBL_Orders", $siteLangId); ?>" href="<?php echo UrlHelper::generateUrl('Buyer', 'Orders', [], CONF_WEBROOT_DASHBOARD); ?>">
                    <span class="menu-sub-title navTextJs"><?php echo Labels::getLabel("LBL_Orders", $siteLangId); ?></span>
                </a>
            </li>
            <li class="menu-sub-item navItemJs">
                <a class="menu-sub-link navLinkJs <?php echo (false === $quickSearch && $controller == 'buyer' && ($action == 'mydownloads')) ? 'active' : ''; ?>" title="<?php echo Labels::getLabel("LBL_Downloads", $siteLangId); ?>" href="<?php echo UrlHelper::generateUrl('Buyer', 'MyDownloads', [], CONF_WEBROOT_DASHBOARD); ?>">
                    <span class="menu-sub-title navTextJs"><?php echo Labels::getLabel("LBL_Downloads", $siteLangId); ?></span>
                </a>
            </li>
            <li class="menu-sub-item navItemJs">
                <a class="menu-sub-link navLinkJs <?php echo (false === $quickSearch && $controller == 'buyer' && $action == 'ordercancellationrequests') ? 'active' : ''; ?>" title="<?php echo Labels::getLabel("LBL_Order_Cancellation_Requests", $siteLangId); ?>" href="<?php echo UrlHelper::generateUrl('Buyer', 'orderCancellationRequests', [], CONF_WEBROOT_DASHBOARD); ?>">
                    <span class="menu-sub-title navTextJs"><?php echo Labels::getLabel("LBL_Order_Cancellation_Requests", $siteLangId); ?></span>
                </a>
            </li>
            <li class="menu-sub-item navItemJs">
                <a class="menu-sub-link navLinkJs <?php echo (false === $quickSearch && $controller == 'buyer' && ($action == 'orderreturnrequests' || $action == 'vieworderreturnrequest')) ? 'active' : ''; ?>" title="<?php echo Labels::getLabel("LBL_Return_Requests", $siteLangId); ?>" href="<?php echo UrlHelper::generateUrl('Buyer', 'orderReturnRequests', [], CONF_WEBROOT_DASHBOARD); ?>">
                    <span class="menu-sub-title navTextJs"><?php echo Labels::getLabel("LBL_Return_Requests", $siteLangId); ?></span>
                </a>
            </li>
            <?php if(0 < FatApp::getConfig('CONF_RFQ_MODULE', FatUtility::VAR_INT, 0)) {?>
                <li class="menu-sub-item navItemJs">
                    <a class="menu-sub-link navLinkJs <?php echo (false === $quickSearch && (($controller == 'requestforquotes' && $action == 'index') || ($controller == 'rfqoffers' && $action == 'listing'))) ? 'active' : ''; ?>" title="<?php echo Labels::getLabel("LBL_REQUEST_FOR_QUOTES", $siteLangId); ?>" href="<?php echo UrlHelper::generateUrl('RequestForQuotes', '', [], CONF_WEBROOT_DASHBOARD); ?>">
                        <span class="menu-sub-title navTextJs"><?php echo Labels::getLabel("LBL_REQUEST_FOR_QUOTES", $siteLangId); ?></span>
                    </a>
                </li>
            <?php }?>
        </ul>
    </li>
    <?php if (User::canViewBuyerTab()) { ?>
        <li class="dashboard-menu-item dropdownJs">
            <button class="dashboard-menu-btn menuLinkJs dropdown-toggle-custom collapsed" type="button" <?php if (false === $quickSearch) { ?> data-bs-toggle="collapse" data-bs-target="#nav-offers" aria-expanded="true" aria-controls="collapseOne" <?php } ?> title="">
                <span class="dashboard-menu-icon">
                    <svg class="svg" width="18" height="18">
                        <use xlink:href="<?php echo CONF_WEBROOT_URL; ?>images/retina/sprite-sidebar.svg<?php echo AttachedFile::setTimeParam(RELEASE_DATE); ?>#my-offers">
                        </use>
                    </svg>
                </span>
                <span class="dashboard-menu-head menuTitleJs">
                    <?php echo Labels::getLabel('LBL_Offers_&_Rewards', $siteLangId); ?>
                </span>

                <?php if (false === $quickSearch) { ?>
                    <i class="dashboard-menu-arrow dropdown-toggle-custom-arrow"></i>
                <?php } ?>
            </button>
            <ul class="menu-sub menu-sub-accordion <?php echo $collapseClass; ?>" id="nav-offers" aria-labelledby="" data-parent="#dashboard-menu">
                <li class="menu-sub-item navItemJs">
                    <a class="menu-sub-link navLinkJs <?php echo (false === $quickSearch && $controller == 'buyer' && $action == 'offers') ? 'active' : ''; ?>" title="<?php echo Labels::getLabel("LBL_My_Offers", $siteLangId); ?>" href="<?php echo UrlHelper::generateUrl('Buyer', 'offers', [], CONF_WEBROOT_DASHBOARD); ?>">
                        <span class="menu-sub-title navTextJs"><?php echo Labels::getLabel("LBL_My_Offers", $siteLangId); ?></span>
                    </a>
                </li>
                <li class="menu-sub-item navItemJs">
                    <a class="menu-sub-link navLinkJs <?php echo (false === $quickSearch && $controller == 'buyer' && $action == 'rewardpoints') ? 'active' : ''; ?>" title="<?php echo Labels::getLabel("LBL_Reward_Points", $siteLangId); ?>" href="<?php echo UrlHelper::generateUrl('Buyer', 'rewardPoints', [], CONF_WEBROOT_DASHBOARD); ?>">
                        <span class="menu-sub-title navTextJs"><?php echo Labels::getLabel("LBL_Reward_Points", $siteLangId); ?></span>
                    </a>
                </li>
                <?php if (FatApp::getConfig('CONF_ENABLE_REFERRER_MODULE', FatUtility::VAR_INT, 1)) { ?>
                    <li class="menu-sub-item navItemJs">
                        <a class="menu-sub-link navLinkJs <?php echo (false === $quickSearch && $controller == 'buyer' && $action == 'shareEarn') ? 'active' : ''; ?>" title="<?php echo Labels::getLabel("LBL_Share_and_Earn", $siteLangId); ?>" href="<?php echo UrlHelper::generateUrl('Buyer', 'shareEarn', [], CONF_WEBROOT_DASHBOARD); ?>">
                            <span class="menu-sub-title navTextJs"><?php echo Labels::getLabel("LBL_Share_and_Earn", $siteLangId); ?></span>
                        </a>
                    </li>
                <?php } ?>
                <?php
                $isSplitPaymentMethod = Plugin::isSplitPaymentEnabled($siteLangId);
                if (!$isSplitPaymentMethod) {
                ?>
                    <li class="menu-sub-item navItemJs">
                        <a class="menu-sub-link navLinkJs <?php echo (false === $quickSearch && $controller == 'buyer' && $action == 'giftcards') ? 'active' : ''; ?>" title="<?php echo Labels::getLabel("LBL_Gift_Cards", $siteLangId); ?>" href="<?php echo UrlHelper::generateUrl('Buyer', 'giftCards', [], CONF_WEBROOT_DASHBOARD); ?>">
                            <span class="menu-sub-title navTextJs"><?php echo Labels::getLabel("LBL_Gift_Cards", $siteLangId); ?></span>
                        </a>
                    </li>
                <?php } ?>
            </ul>
        </li>
    <?php } ?>
    <li class="dashboard-menu-item dropdownJs">
        <button class="dashboard-menu-btn menuLinkJs dropdown-toggle-custom collapsed" type="button" <?php if (false === $quickSearch) { ?> data-bs-toggle="collapse" data-bs-target="#nav-general" aria-expanded="true" aria-controls="collapseOne" <?php } ?> title="">
            <span class="dashboard-menu-icon">
                <svg class="svg" width="18" height="18">
                    <use xlink:href="<?php echo CONF_WEBROOT_URL; ?>images/retina/sprite-sidebar.svg<?php echo AttachedFile::setTimeParam(RELEASE_DATE); ?>#saved-searches">
                    </use>
                </svg>
            </span>
            <span class="dashboard-menu-head menuTitleJs">
                <?php echo Labels::getLabel('LBL_General', $siteLangId); ?>
            </span>

            <?php if (false === $quickSearch) { ?>
                <i class="dashboard-menu-arrow dropdown-toggle-custom-arrow"></i>
            <?php } ?>
        </button>
        <ul class="menu-sub menu-sub-accordion <?php echo $collapseClass; ?>" id="nav-general" aria-labelledby="" data-parent="#dashboard-menu">
            <li class="menu-sub-item navItemJs">
                <a class="menu-sub-link navLinkJs <?php echo (false === $quickSearch && $controller == 'account' && ($action == 'messages' || strtolower($action) == 'viewmessages')) ? 'active' : ''; ?>" title="<?php echo Labels::getLabel("LBL_Messages", $siteLangId); ?>" href="<?php echo UrlHelper::generateUrl('Account', 'Messages', [], CONF_WEBROOT_DASHBOARD); ?>">
                    <span class="menu-sub-title navTextJs"><?php echo Labels::getLabel("LBL_Messages", $siteLangId); ?>
                        <span class="msg-count"><?php echo CommonHelper::displayBadgeCount($todayUnreadMessageCount, 9); ?></span>
                    </span>
                </a>
            </li>
            <li class="menu-sub-item navItemJs">
                <a class="menu-sub-link navLinkJs <?php echo (false === $quickSearch && $controller == 'account' && $action == 'credits') ? 'active' : ''; ?>" title="<?php echo Labels::getLabel("LBL_My_Credits", $siteLangId); ?>" href="<?php echo UrlHelper::generateUrl('Account', 'credits', [], CONF_WEBROOT_DASHBOARD); ?>">
                    <span class="menu-sub-title navTextJs"><?php echo Labels::getLabel('LBL_My_Credits', $siteLangId); ?></span>
                </a>
            </li>
            <li class="menu-sub-item navItemJs">
                <?php
                $label = Labels::getLabel("LBL_FAVORITES", $siteLangId);
                if (0 < FatApp::getConfig('CONF_ADD_FAVORITES_TO_WISHLIST', FatUtility::VAR_INT, 1)) {
                    $label = Labels::getLabel("LBL_WISHLIST", $siteLangId);
                }
                ?>
                <a class="menu-sub-link navLinkJs <?php echo (false === $quickSearch && $controller == 'account' && $action == 'wishlist') ? 'active' : ''; ?>" title="<?php echo $label; ?>" href="<?php echo UrlHelper::generateUrl('Account', 'wishlist', [], CONF_WEBROOT_DASHBOARD); ?>">
                    <span class="menu-sub-title navTextJs"><?php echo $label; ?></span>
                </a>
            </li>
            <li class="menu-sub-item navItemJs">
                <a class="menu-sub-link navLinkJs <?php echo (false === $quickSearch && $controller == 'savedproductssearch' && $action == 'listing') ? 'active' : ''; ?>" title="<?php echo Labels::getLabel("LBL_Saved_Searches", $siteLangId); ?>" href="<?php echo UrlHelper::generateUrl('SavedProductsSearch', 'listing', [], CONF_WEBROOT_DASHBOARD); ?>">
                    <span class="menu-sub-title navTextJs"><?php echo Labels::getLabel('LBL_Saved_Searches', $siteLangId); ?></span>
                </a>
            </li>
        </ul>
    </li>
    <li class="dashboard-menu-item dropdownJs">
        <button class="dashboard-menu-btn menuLinkJs dropdown-toggle-custom collapsed" type="button" <?php if (false === $quickSearch) { ?> data-bs-toggle="collapse" data-bs-target="#nav-profile" aria-expanded="true" aria-controls="collapseOne" <?php } ?> title="">
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
                <a class="menu-sub-link navLinkJs <?php echo (false === $quickSearch && $controller == 'account' && $action == 'profileinfo') ? 'active' : ''; ?>" title="<?php echo Labels::getLabel("LBL_Account_Settings", $siteLangId); ?>" href="<?php echo UrlHelper::generateUrl('Account', 'ProfileInfo', [], CONF_WEBROOT_DASHBOARD); ?>">
                    <span class="menu-sub-title navTextJs"><?php echo Labels::getLabel("LBL_My_Account", $siteLangId); ?></span>
                </a>
            </li>
            <?php if (!User::isAffiliate()) { ?>
                <li class="menu-sub-item navItemJs">
                    <a class="menu-sub-link navLinkJs <?php echo (false === $quickSearch && $controller == 'account' && ($action == 'bankInfoForm')) ? 'active' : ''; ?>" title="<?php echo Labels::getLabel('LBL_BANK_ACCOUNT', $siteLangId); ?>" href="<?php echo UrlHelper::generateUrl('account', 'bankInfoForm', [], CONF_WEBROOT_DASHBOARD); ?>">
                        <span class="menu-sub-title navTextJs"><?php echo Labels::getLabel("LBL_BANK_ACCOUNT", $siteLangId); ?></span>
                    </a>
                </li>
            <?php } ?>

            <?php if (FatApp::getConfig('CONF_ENABLE_COOKIES', FatUtility::VAR_INT, 1)) { ?>
                <li class="menu-sub-item navItemJs">
                    <a class="menu-sub-link navLinkJs <?php echo (false === $quickSearch && $controller == 'account' && ($action == 'cookiesPreferencesForm')) ? 'active' : ''; ?>" title="<?php echo Labels::getLabel('LBL_COOKIE_PREFERENCES', $siteLangId); ?>" href="<?php echo UrlHelper::generateUrl('account', 'cookiesPreferencesForm', [], CONF_WEBROOT_DASHBOARD); ?>">
                        <span class="menu-sub-title navTextJs"><?php echo Labels::getLabel("LBL_COOKIE_PREFERENCES", $siteLangId); ?></span>
                    </a>
                </li>
            <?php } ?>
            <li class="menu-sub-item navItemJs">
                <a class="menu-sub-link navLinkJs <?php echo (false === $quickSearch && $controller == 'account' && $action == 'myaddresses') ? 'active' : ''; ?>" title="<?php echo Labels::getLabel("LBL_Manage_Addresses", $siteLangId); ?>" href="<?php echo UrlHelper::generateUrl('Account', 'myAddresses', [], CONF_WEBROOT_DASHBOARD); ?>">
                    <span class="menu-sub-title navTextJs"><?php echo Labels::getLabel("LBL_Manage_Addresses", $siteLangId); ?></span>
                </a>
            </li>
            <li class="menu-sub-item navItemJs">
                <a class="menu-sub-link navLinkJs <?php echo (false === $quickSearch && $controller == 'account' && $action == 'changeemailpassword') ? 'active' : ''; ?>" title="<?php echo Labels::getLabel("LBL_UPDATE_CREDENTIALS", $siteLangId); ?>" href="<?php echo UrlHelper::generateUrl('Account', 'changeEmailPassword', [], CONF_WEBROOT_DASHBOARD); ?>">
                    <span class="menu-sub-title navTextJs"><?php echo Labels::getLabel('LBL_UPDATE_CREDENTIALS', $siteLangId); ?></span>
                </a>
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