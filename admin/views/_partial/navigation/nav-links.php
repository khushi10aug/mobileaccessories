<?php
$quickSearch = $quickSearch ?? false;
$quickSearchUlClass = ($quickSearch ? 'quickMenujs' : '');
$collapseClass = ($quickSearch ? 'collapsed' : 'collapse');
?>
<ul class="menu <?php echo $quickSearchUlClass; ?>" id="sidebarNavLinks">
    <?php if (!$quickSearch) { ?>
        <li class="menu-item dropdownJs">
            <button class="menu-section navLinkJs" type="button" data-selector='["Home"]' onclick="redirectFn('<?php echo UrlHelper::generateUrl(); ?>')">
                <span class="menu-icon">
                    <svg class="svg " width="24" height="24">
                        <use xlink:href="<?php echo CONF_WEBROOT_URL; ?>images/retina/sprite-aside-menu.svg<?php echo AttachedFile::setTimeParam(RELEASE_DATE); ?>#icon-dashboard">
                        </use>
                    </svg>
                </span>
                <span class="menu-title menuTitleJs"><?php echo Labels::getLabel('NAV_HOME', $siteLangId); ?></span>
            </button>
        </li>
    <?php } ?>
    <?php
    if (
        $objPrivilege->canViewBrands(AdminAuthentication::getLoggedAdminId(), true) ||
        $objPrivilege->canViewShops(AdminAuthentication::getLoggedAdminId(), true) ||
        $objPrivilege->canViewProductCategories(AdminAuthentication::getLoggedAdminId(), true) ||
        $objPrivilege->canViewProducts(AdminAuthentication::getLoggedAdminId(), true) ||
        $objPrivilege->canViewSellerProducts(AdminAuthentication::getLoggedAdminId(), true) ||
        $objPrivilege->canViewOptions(AdminAuthentication::getLoggedAdminId(), true) ||
        $objPrivilege->canViewSellerProducts(AdminAuthentication::getLoggedAdminId(), true) ||
        $objPrivilege->canViewTags(AdminAuthentication::getLoggedAdminId(), true)
    ) {
    ?>
        <li class="menu-item dropdownJs">
            <button class="menu-section dropdown-toggle-custom menuLinkJs collapsed" type="button" <?php if (!$quickSearch) { ?>data-bs-toggle="collapse" data-bs-target="#NAV_PRODUCT_MANAGEMENT" <?php } ?> aria-expanded="true" aria-controls="collapseOne">
                <span class="menu-icon">
                    <svg class="svg" width="24" height="24">
                        <use xlink:href="<?php echo CONF_WEBROOT_URL; ?>images/retina/sprite-aside-menu.svg<?php echo AttachedFile::setTimeParam(RELEASE_DATE); ?>#icon-product-catalog">
                        </use>
                    </svg>
                </span>
                <span class="menu-title menuTitleJs"><?php echo Labels::getLabel('NAV_PRODUCT_MANAGEMENT', $siteLangId); ?></span>
                <?php if (!$quickSearch) { ?>
                    <i class="menu_arrow dropdown-toggle-custom-arrow"></i>
                <?php } ?>
            </button>
            <div class="sidebar-dropdown-menu <?php echo $collapseClass; ?>" <?php if (!$quickSearch) { ?>id="NAV_PRODUCT_MANAGEMENT" <?php } ?> aria-labelledby="" data-bs-parent="#sidebarNavLinks">
                <ul class="nav nav-level">
                    <?php if ($objPrivilege->canViewProducts(AdminAuthentication::getLoggedAdminId(), true)) { ?>
                        <li class="nav_item navItemJs">
                            <a class="nav_link navLinkJs dropdown-toggle-custom" data-selector='["Products"]' href="<?php echo UrlHelper::generateUrl('Products'); ?>">
                                <span class="nav_icon">
                                    <svg class="svg" width="24" height="24">
                                        <use xlink:href="<?php echo CONF_WEBROOT_URL; ?>images/retina/sprite-aside-menu.svg<?php echo AttachedFile::setTimeParam(RELEASE_DATE); ?>#test">
                                        </use>
                                    </svg>
                                </span> <span class="nav_text navTextJs"><?php echo Labels::getLabel('NAV_PRODUCT_CATALOG', $siteLangId); ?></span>
                            </a>
                        </li>
                    <?php } ?>
                    <?php if ($objPrivilege->canViewSellerProducts(AdminAuthentication::getLoggedAdminId(), true)) { ?>
                        <li class="nav_item navItemJs">
                            <a class="nav_link navLinkJs dropdown-toggle-custom" data-selector='["SellerProducts"]' href="<?php echo UrlHelper::generateUrl('SellerProducts'); ?>">
                                <span class="nav_icon">
                                    <svg class="svg" width="24" height="24">
                                        <use xlink:href="<?php echo CONF_WEBROOT_URL; ?>images/retina/sprite-aside-menu.svg<?php echo AttachedFile::setTimeParam(RELEASE_DATE); ?>#test">
                                        </use>
                                    </svg>
                                </span> <span class="nav_text navTextJs"><?php echo Labels::getLabel('NAV_PRODUCT_INVENTORY', $siteLangId); ?></span>
                            </a>
                        </li>
                    <?php } ?>
                    <?php if ($objPrivilege->canViewProductCategories(AdminAuthentication::getLoggedAdminId(), true)) { ?>
                        <li class="nav_item navItemJs">
                            <a class="nav_link navLinkJs dropdown-toggle-custom" data-selector='["ProductCategories"]' href="<?php echo UrlHelper::generateUrl('ProductCategories'); ?>">
                                <span class="nav_icon">
                                    <svg class="svg" width="24" height="24">
                                        <use xlink:href="<?php echo CONF_WEBROOT_URL; ?>images/retina/sprite-aside-menu.svg<?php echo AttachedFile::setTimeParam(RELEASE_DATE); ?>#test">
                                        </use>
                                    </svg>
                                </span> <span class="nav_text navTextJs"><?php echo Labels::getLabel('NAV_CATEGORIES', $siteLangId); ?></span>
                            </a>
                        </li>
                    <?php } ?>
                    <?php if ($objPrivilege->canViewBrands(AdminAuthentication::getLoggedAdminId(), true)) { ?>
                        <li class="nav_item navItemJs">
                            <a class="nav_link navLinkJs dropdown-toggle-custom" data-selector='["Brands"]' href="<?php echo UrlHelper::generateUrl('Brands'); ?>">
                                <span class="nav_icon">
                                    <svg class="svg" width="24" height="24">
                                        <use xlink:href="<?php echo CONF_WEBROOT_URL; ?>images/retina/sprite-aside-menu.svg<?php echo AttachedFile::setTimeParam(RELEASE_DATE); ?>#test">
                                        </use>
                                    </svg>
                                </span>
                                <span class="nav_text navTextJs"><?php echo Labels::getLabel('NAV_BRANDS', $siteLangId); ?></span>
                            </a>
                        </li>
                    <?php } ?>
                    <?php if ($objPrivilege->canViewShops(AdminAuthentication::getLoggedAdminId(), true)) { ?>
                        <li class="nav_item navItemJs">
                            <a class="nav_link navLinkJs dropdown-toggle-custom" data-selector='["Shops"]' href="<?php echo UrlHelper::generateUrl('Shops'); ?>">
                                <span class="nav_icon">
                                    <svg class="svg" width="24" height="24">
                                        <use xlink:href="<?php echo CONF_WEBROOT_URL; ?>images/retina/sprite-aside-menu.svg<?php echo AttachedFile::setTimeParam(RELEASE_DATE); ?>#test">
                                        </use>
                                    </svg>
                                </span> <span class="nav_text navTextJs"><?php echo Labels::getLabel('NAV_SHOPS', $siteLangId); ?></span>
                            </a>
                        </li>
                    <?php } ?>

                    <?php if (!FatApp::getConfig('CONF_WITHOUT_PROD_VARIANTS', FatUtility::VAR_INT, 0) && $objPrivilege->canViewOptions(AdminAuthentication::getLoggedAdminId(), true)) { ?>
                        <li class="nav_item navItemJs">
                            <a class="nav_link navLinkJs dropdown-toggle-custom" data-selector='["Options", "OptionValues"]' href="<?php echo UrlHelper::generateUrl('Options'); ?>">
                                <span class="nav_icon">
                                    <svg class="svg" width="24" height="24">
                                        <use xlink:href="<?php echo CONF_WEBROOT_URL; ?>images/retina/sprite-aside-menu.svg<?php echo AttachedFile::setTimeParam(RELEASE_DATE); ?>#test">
                                        </use>
                                    </svg>
                                </span> <span class="nav_text navTextJs"><?php echo Labels::getLabel('NAV_OPTIONS', $siteLangId); ?></span>
                            </a>
                        </li>
                    <?php } ?>
                    <?php if ($objPrivilege->canViewTags(AdminAuthentication::getLoggedAdminId(), true)) { ?>
                        <li class="nav_item navItemJs">
                            <a class="nav_link navLinkJs dropdown-toggle-custom" data-selector='["Tags"]' href="<?php echo UrlHelper::generateUrl('Tags'); ?>">
                                <span class="nav_icon">
                                    <svg class="svg" width="24" height="24">
                                        <use xlink:href="<?php echo CONF_WEBROOT_URL; ?>images/retina/sprite-aside-menu.svg<?php echo AttachedFile::setTimeParam(RELEASE_DATE); ?>#test">
                                        </use>
                                    </svg>
                                </span> <span class="nav_text navTextJs"><?php echo Labels::getLabel('NAV_PRODUCT_TAGS', $siteLangId); ?></span>
                            </a>
                        </li>
                    <?php } ?>
                    <?php if ($objPrivilege->canViewSellerProducts(AdminAuthentication::getLoggedAdminId(), true)) { ?>
                        <li class="nav_item navItemJs">
                            <a class="nav_link navLinkJs dropdown-toggle-custom" data-selector='["ThresholdProducts"]' href="<?php echo UrlHelper::generateUrl('thresholdProducts'); ?>">
                                <span class="nav_icon">
                                    <svg class="svg" width="24" height="24">
                                        <use xlink:href="<?php echo CONF_WEBROOT_URL; ?>images/retina/sprite-aside-menu.svg<?php echo AttachedFile::setTimeParam(RELEASE_DATE); ?>#test">
                                        </use>
                                    </svg>
                                </span> <span class="nav_text navTextJs"><?php echo Labels::getLabel('NAV_THRESHOLD_PRODUCTS', $siteLangId); ?><?php if (!$quickSearch && $threshSelProdCount) { ?> (<?php echo HtmlHelper::displayNumberWithPlus($threshSelProdCount); ?>)<?php } ?></span>
                            </a>
                        </li>
                    <?php } ?>

                </ul>
            </div>
        </li>
    <?php } ?>

    <?php
    if (
        $objPrivilege->canViewBrandRequests(AdminAuthentication::getLoggedAdminId(), true) ||
        $objPrivilege->canViewSellerApprovalRequests(AdminAuthentication::getLoggedAdminId(), true) ||
        $objPrivilege->canViewProductCategories(AdminAuthentication::getLoggedAdminId(), true) ||
        $objPrivilege->canViewWithdrawRequests(AdminAuthentication::getLoggedAdminId(), true) ||
        $objPrivilege->canViewOrderCancellationRequests(AdminAuthentication::getLoggedAdminId(), true) ||
        $objPrivilege->canViewOrderReturnRequests(AdminAuthentication::getLoggedAdminId(), true) ||
        $objPrivilege->canViewCustomProductRequests(AdminAuthentication::getLoggedAdminId(), true) ||
        $objPrivilege->canViewBadgeRequests(AdminAuthentication::getLoggedAdminId(), true) ||
        $objPrivilege->canViewUserRequests(AdminAuthentication::getLoggedAdminId(), true)

    ) {
    ?>
        <li class="menu-item dropdownJs">
            <button class="menu-section dropdown-toggle-custom menuLinkJs collapsed" type="button" <?php if (!$quickSearch) { ?>data-bs-toggle="collapse" data-bs-target="#NAV_REQUESTS" <?php } ?> aria-expanded="true" aria-controls="collapseOne">
                <span class="menu-icon">
                    <svg class="svg" width="24" height="24">
                        <use xlink:href="<?php echo CONF_WEBROOT_URL; ?>images/retina/sprite-aside-menu.svg<?php echo AttachedFile::setTimeParam(RELEASE_DATE); ?>#icon-requests">
                        </use>
                    </svg>
                </span>
                <span class="menu-title menuTitleJs"><?php echo Labels::getLabel('NAV_REQUESTS', $siteLangId); ?></span>
                <?php if (!$quickSearch) { ?>
                    <i class="menu_arrow dropdown-toggle-custom-arrow"></i>
                <?php } ?>
            </button>
            <div class="sidebar-dropdown-menu <?php echo $collapseClass; ?>" <?php if (!$quickSearch) { ?>id="NAV_REQUESTS" <?php } ?> aria-labelledby="" data-bs-parent="#sidebarNavLinks">
                <ul class="nav nav-level">
                    <?php if ($objPrivilege->canViewBrandRequests(AdminAuthentication::getLoggedAdminId(), true)) { ?>
                        <li class="nav_item navItemJs">
                            <a class="nav_link navLinkJs dropdown-toggle-custom" data-selector='["BrandRequests"]' href="<?php echo UrlHelper::generateUrl('brandRequests'); ?>">
                                <span class="nav_icon">
                                    <svg class="svg" width="24" height="24">
                                        <use xlink:href="<?php echo CONF_WEBROOT_URL; ?>images/retina/sprite-aside-menu.svg<?php echo AttachedFile::setTimeParam(RELEASE_DATE); ?>#test">
                                        </use>
                                    </svg>
                                </span> <span class="nav_text navTextJs"><?php echo Labels::getLabel('NAV_BRAND_REQUEST', $siteLangId); ?><?php if (!$quickSearch && $brandReqCount) { ?> (<?php echo HtmlHelper::displayNumberWithPlus($brandReqCount); ?>)<?php } ?></span>
                            </a>
                        </li>
                    <?php } ?>
                    <?php if ($objPrivilege->canViewProductCategories(AdminAuthentication::getLoggedAdminId(), true)) { ?>
                        <li class="nav_item navItemJs">
                            <a class="nav_link navLinkJs dropdown-toggle-custom" data-selector='["ProductCategoriesRequest"]' href="<?php echo UrlHelper::generateUrl('ProductCategoriesRequest'); ?>">
                                <span class="nav_icon">
                                    <svg class="svg" width="24" height="24">
                                        <use xlink:href="<?php echo CONF_WEBROOT_URL; ?>images/retina/sprite-aside-menu.svg<?php echo AttachedFile::setTimeParam(RELEASE_DATE); ?>#test">
                                        </use>
                                    </svg>
                                </span> <span class="nav_text navTextJs"><?php echo Labels::getLabel('NAV_CATEGORIES_REQUESTS', $siteLangId); ?>
                                    <?php if (!$quickSearch && $categoryReqCount) { ?> (<?php echo HtmlHelper::displayNumberWithPlus($categoryReqCount); ?>)<?php } ?></span>
                            </a>
                        </li>
                    <?php } ?>
                    <?php if ($objPrivilege->canViewCustomProductRequests(AdminAuthentication::getLoggedAdminId(), true) && 0 < FatApp::getConfig('CONF_SELLER_CAN_REQUEST_CUSTOM_PRODUCT', FatUtility::VAR_INT, 0)) { ?>
                        <li class="nav_item navItemJs">
                            <a class="nav_link navLinkJs dropdown-toggle-custom" href="<?php echo UrlHelper::generateUrl('CustomProducts'); ?>">
                                <span class="nav_icon">
                                    <svg class="svg" width="24" height="24">
                                        <use xlink:href="<?php echo CONF_WEBROOT_URL; ?>images/retina/sprite-aside-menu.svg<?php echo AttachedFile::setTimeParam(RELEASE_DATE); ?>#test">
                                        </use>
                                    </svg>
                                </span> <span class="nav_text navTextJs"><?php echo Labels::getLabel('NAV_MASTER_PRODUCT_REQUESTS', $siteLangId); ?>
                                    <?php if (!$quickSearch && $custProdReqCount) { ?> (<?php echo HtmlHelper::displayNumberWithPlus($custProdReqCount); ?>)<?php } ?></span>
                            </a>
                        </li>
                    <?php } ?>
                    <?php if ($objPrivilege->canViewProducts(AdminAuthentication::getLoggedAdminId(), true)) { ?>
                        <li class="nav_item navItemJs">
                            <a class="nav_link navLinkJs dropdown-toggle-custom" href="<?php echo UrlHelper::generateUrl('products', 'approvalPending'); ?>">
                                <span class="nav_icon">
                                    <svg class="svg" width="24" height="24">
                                        <use xlink:href="<?php echo CONF_WEBROOT_URL; ?>images/retina/sprite-aside-menu.svg<?php echo AttachedFile::setTimeParam(RELEASE_DATE); ?>#test">
                                        </use>
                                    </svg>
                                </span> <span class="nav_text navTextJs"><?php echo Labels::getLabel('NAV_SELLER_PRODUCT_REQUESTS', $siteLangId); ?>
                                    <?php if (!$quickSearch && $selProdReqCount) { ?> (<?php echo HtmlHelper::displayNumberWithPlus($selProdReqCount); ?>)<?php } ?></span>
                            </a>
                        </li>
                    <?php } ?>
                    <?php if ($objPrivilege->canViewSellerApprovalRequests(AdminAuthentication::getLoggedAdminId(), true)) { ?>
                        <li class="nav_item navItemJs">
                            <a class="nav_link navLinkJs dropdown-toggle-custom" data-selector='["SellerApprovalRequests"]' href="<?php echo UrlHelper::generateUrl('sellerApprovalRequests'); ?>">
                                <span class="nav_icon">
                                    <svg class="svg" width="24" height="24">
                                        <use xlink:href="<?php echo CONF_WEBROOT_URL; ?>images/retina/sprite-aside-menu.svg<?php echo AttachedFile::setTimeParam(RELEASE_DATE); ?>#test">
                                        </use>
                                    </svg>
                                </span> <span class="nav_text navTextJs"><?php echo Labels::getLabel('NAV_SELLER_APPROVAL_REQUESTS', $siteLangId); ?><?php if (!$quickSearch && $supReqCount) { ?> (<?php echo HtmlHelper::displayNumberWithPlus($supReqCount); ?>)<?php } ?></span>
                            </a>
                        </li>
                    <?php } ?>


                    <?php if ($objPrivilege->canViewOrderReturnRequests(AdminAuthentication::getLoggedAdminId(), true)) { ?>
                        <li class="nav_item navItemJs">
                            <a class="nav_link navLinkJs dropdown-toggle-custom" data-selector='["OrderReturnRequests"]' href="<?php echo UrlHelper::generateUrl('OrderReturnRequests'); ?>">
                                <span class="nav_icon">
                                    <svg class="svg" width="24" height="24">
                                        <use xlink:href="<?php echo CONF_WEBROOT_URL; ?>images/retina/sprite-aside-menu.svg<?php echo AttachedFile::setTimeParam(RELEASE_DATE); ?>#test">
                                        </use>
                                    </svg>
                                </span>
                                <span class="nav_text navTextJs">
                                    <?php echo Labels::getLabel('NAV_ORDER_RETURN_REQUESTS', $siteLangId); ?>
                                    <?php if (!$quickSearch && $orderRetReqCount) { ?> (<?php echo HtmlHelper::displayNumberWithPlus($orderRetReqCount); ?>)<?php } ?>
                                </span>
                            </a>
                        </li>
                    <?php } ?>
                    <?php if ($objPrivilege->canViewWithdrawRequests(AdminAuthentication::getLoggedAdminId(), true)) { ?>
                        <li class="nav_item navItemJs">
                            <a class="nav_link navLinkJs dropdown-toggle-custom" data-selector='["WithdrawalRequests"]' href="<?php echo UrlHelper::generateUrl('WithdrawalRequests'); ?>">
                                <span class="nav_icon">
                                    <svg class="svg" width="24" height="24">
                                        <use xlink:href="<?php echo CONF_WEBROOT_URL; ?>images/retina/sprite-aside-menu.svg<?php echo AttachedFile::setTimeParam(RELEASE_DATE); ?>#test">
                                        </use>
                                    </svg>
                                </span>
                                <span class="nav_text navTextJs">
                                    <?php
                                    $menuLabel = Labels::getLabel('NAV_WITHDRAWL_REQUESTS', $siteLangId);
                                    $menuLabel .= (!$quickSearch && $drReqCount ? ' (' . HtmlHelper::displayNumberWithPlus($drReqCount) . ')' : '');
                                    echo $menuLabel;
                                    ?>
                                </span>
                            </a>
                        </li>
                    <?php } ?>
                    <?php if ($objPrivilege->canViewOrderCancellationRequests(AdminAuthentication::getLoggedAdminId(), true)) { ?>
                        <li class="nav_item navItemJs">
                            <a class="nav_link navLinkJs dropdown-toggle-custom" data-selector='["OrderCancellationRequests"]' href="<?php echo UrlHelper::generateUrl('OrderCancellationRequests'); ?>">
                                <span class="nav_icon">
                                    <svg class="svg" width="24" height="24">
                                        <use xlink:href="<?php echo CONF_WEBROOT_URL; ?>images/retina/sprite-aside-menu.svg<?php echo AttachedFile::setTimeParam(RELEASE_DATE); ?>#test">
                                        </use>
                                    </svg>
                                </span>
                                <span class="nav_text navTextJs">
                                    <?php
                                    $menuLabel = Labels::getLabel('NAV_CANCELLATION_REQUESTS', $siteLangId);
                                    $menuLabel .= (!$quickSearch && $orderCancelReqCount ? ' (' . HtmlHelper::displayNumberWithPlus($orderCancelReqCount) . ')' : '');
                                    echo $menuLabel;
                                    ?>
                                </span>
                            </a>
                        </li>
                    <?php } ?>
                    <?php if ($objPrivilege->canViewBadgeRequests(AdminAuthentication::getLoggedAdminId(), true)) { ?>
                        <li class="nav_item navItemJs">
                            <a class="nav_link navLinkJs dropdown-toggle-custom" data-selector='["BadgeRequests"]' href="<?php echo UrlHelper::generateUrl('BadgeRequests'); ?>">
                                <span class="nav_icon">
                                    <svg class="svg" width="24" height="24">
                                        <use xlink:href="<?php echo CONF_WEBROOT_URL; ?>images/retina/sprite-aside-menu.svg<?php echo AttachedFile::setTimeParam(RELEASE_DATE); ?>#test">
                                        </use>
                                    </svg>
                                </span>
                                <span class="nav_text navTextJs">
                                    <?php
                                    $menuLabel = Labels::getLabel('NAV_BADGE_REQUEST', $siteLangId);
                                    $menuLabel .= (!$quickSearch && $badgeRequestCount ? ' (' . HtmlHelper::displayNumberWithPlus($badgeRequestCount) . ')' : '');
                                    echo $menuLabel;
                                    ?>
                                </span>
                            </a>
                        </li>
                    <?php } ?>
                    <?php if ($objPrivilege->canViewUserRequests(AdminAuthentication::getLoggedAdminId(), true)) { ?>
                        <li class="nav_item navItemJs">
                            <a class="nav_link navLinkJs dropdown-toggle-custom" data-selector='["UserGdprRequests"]' href="<?php echo UrlHelper::generateUrl('userGdprRequests'); ?>">
                                <span class="nav_icon">
                                    <svg class="svg" width="24" height="24">
                                        <use xlink:href="<?php echo CONF_WEBROOT_URL; ?>images/retina/sprite-aside-menu.svg<?php echo AttachedFile::setTimeParam(RELEASE_DATE); ?>#test">
                                        </use>
                                    </svg>
                                </span>
                                <span class="nav_text navTextJs">
                                    <?php
                                    $menuLabel = Labels::getLabel('NAV_GDPR_REQUESTS', $siteLangId);
                                    $menuLabel .= (!$quickSearch && $gdprReqCount ? ' (' . HtmlHelper::displayNumberWithPlus($gdprReqCount) . ')' : '');
                                    echo $menuLabel;
                                    ?> </span>
                            </a>
                        </li>
                    <?php } ?>
                </ul>
            </div>
        </li>
    <?php } ?>
    <?php
    if (
        $objPrivilege->canViewRequestForQuote(AdminAuthentication::getLoggedAdminId(), true) && (FatApp::getConfig('CONF_RFQ_MODULE', FatUtility::VAR_INT, 0))
    ) {
    ?>
        <li class="menu-item dropdownJs">
            <button class="menu-section dropdown-toggle-custom menuLinkJs collapsed" type="button" <?php if (!$quickSearch) { ?>data-bs-toggle="collapse" data-bs-target="#NAV_RFQ" <?php } ?> aria-expanded="true" aria-controls="collapseOne">
                <span class="menu-icon">
                    <svg class="svg" width="24" height="24">
                        <use xlink:href="<?php echo CONF_WEBROOT_URL; ?>images/retina/sprite-aside-menu.svg<?php echo AttachedFile::setTimeParam(RELEASE_DATE); ?>#icon-rfq">
                        </use>
                    </svg>
                </span>
                <span class="menu-title menuTitleJs"><?php echo Labels::getLabel('NAV_RFQ', $siteLangId); ?></span>
                <?php if (!$quickSearch) { ?>
                    <i class="menu_arrow dropdown-toggle-custom-arrow"></i>
                <?php } ?>
            </button>
            <div class="sidebar-dropdown-menu <?php echo $collapseClass; ?>" <?php if (!$quickSearch) { ?>id="NAV_RFQ" <?php } ?> aria-labelledby="" data-bs-parent="#sidebarNavLinks">
                <ul class="nav nav-level">
                    <?php if ($objPrivilege->canViewRequestForQuote(AdminAuthentication::getLoggedAdminId(), true)) { ?>
                        <li class="nav_item navItemJs">
                            <a class="nav_link navLinkJs dropdown-toggle-custom" data-selector='["RequestForQuotes", "RfqOffers"]' href="<?php echo UrlHelper::generateUrl('RequestForQuotes'); ?>">
                                <span class="nav_icon">
                                    <svg class="svg" width="24" height="24">
                                        <use xlink:href="<?php echo CONF_WEBROOT_URL; ?>images/retina/sprite-aside-menu.svg<?php echo AttachedFile::setTimeParam(RELEASE_DATE); ?>#test">
                                        </use>
                                    </svg>
                                </span>
                                <span class="nav_text navTextJs">
                                    <?php
                                    $menuLabel = Labels::getLabel('NAV_REQUEST_FOR_QUOTES', $siteLangId);
                                    $menuLabel .= (!$quickSearch && $rfqCount ? ' (' . HtmlHelper::displayNumberWithPlus($rfqCount) . ')' : '');
                                    echo $menuLabel;
                                    ?>
                                </span>
                            </a>
                        </li>
                    <?php } ?>
                </ul>
            </div>
        </li>
    <?php } ?>
    <?php
    if (
        $objPrivilege->canViewOrders(AdminAuthentication::getLoggedAdminId(), true) ||
        $objPrivilege->canViewSubscriptionOrders(AdminAuthentication::getLoggedAdminId(), true) ||
        $objPrivilege->canViewOrderCancelReasons(AdminAuthentication::getLoggedAdminId(), true) ||
        $objPrivilege->canViewOrderReturnReasons(AdminAuthentication::getLoggedAdminId(), true) ||
        $objPrivilege->canViewOrderStatus(AdminAuthentication::getLoggedAdminId(), true) ||
        $objPrivilege->canViewProductReviews(AdminAuthentication::getLoggedAdminId(), true) ||
        $objPrivilege->canViewAbandonedCart(AdminAuthentication::getLoggedAdminId(), true)
    ) {
    ?>
        <li class="menu-item dropdownJs">
            <button class="menu-section dropdown-toggle-custom menuLinkJs collapsed" type="button" <?php if (!$quickSearch) { ?>data-bs-toggle="collapse" data-bs-target="#NAV_ORDERS" <?php } ?> aria-expanded="true" aria-controls="collapseOne">
                <span class="menu-icon">
                    <svg class="svg" width="24" height="24">
                        <use xlink:href="<?php echo CONF_WEBROOT_URL; ?>images/retina/sprite-aside-menu.svg<?php echo AttachedFile::setTimeParam(RELEASE_DATE); ?>#icon-orders">
                        </use>
                    </svg>
                </span>
                <span class="menu-title menuTitleJs"><?php echo Labels::getLabel('NAV_ORDERS', $siteLangId); ?></span>
                <?php if (!$quickSearch) { ?>
                    <i class="menu_arrow dropdown-toggle-custom-arrow"></i>
                <?php } ?>
            </button>
            <div class="sidebar-dropdown-menu <?php echo $collapseClass; ?>" <?php if (!$quickSearch) { ?>id="NAV_ORDERS" <?php } ?> aria-labelledby="" data-bs-parent="#sidebarNavLinks">
                <ul class="nav nav-level">
                    <?php if ($objPrivilege->canViewOrders(AdminAuthentication::getLoggedAdminId(), true)) { ?>
                        <li class="nav_item navItemJs">
                            <a class="nav_link navLinkJs dropdown-toggle-custom" data-selector='["Orders"]' href="<?php echo UrlHelper::generateUrl('Orders'); ?>">
                                <span class="nav_icon">
                                    <svg class="svg" width="24" height="24">
                                        <use xlink:href="<?php echo CONF_WEBROOT_URL; ?>images/retina/sprite-aside-menu.svg<?php echo AttachedFile::setTimeParam(RELEASE_DATE); ?>#test">
                                        </use>
                                    </svg>
                                </span> <span class="nav_text navTextJs"><?php echo Labels::getLabel('NAV_ORDERS', $siteLangId); ?></span>
                            </a>
                        </li>
                    <?php } ?>
                    <?php if ($objPrivilege->canViewOrders(AdminAuthentication::getLoggedAdminId(), true) && (false == Plugin::isSplitPaymentEnabled($siteLangId))) { ?>
                        <li class="nav_item navItemJs">
                            <a class="nav_link navLinkJs dropdown-toggle-custom" data-selector='["GiftCardOrders"]' href="<?php echo UrlHelper::generateUrl('GiftCardOrders'); ?>">
                                <span class="nav_icon">
                                    <svg class="svg" width="24" height="24">
                                        <use xlink:href="<?php echo CONF_WEBROOT_URL; ?>images/retina/sprite-aside-menu.svg<?php echo AttachedFile::setTimeParam(RELEASE_DATE); ?>#test">
                                        </use>
                                    </svg>
                                </span> <span class="nav_text navTextJs"><?php echo Labels::getLabel('NAV_GIFT_CARD_ORDERS', $siteLangId); ?></span>
                            </a>
                        </li>
                    <?php } ?>
                    <?php if ($objPrivilege->canViewSubscriptionOrders(AdminAuthentication::getLoggedAdminId(), true)) { ?>
                        <li class="nav_item navItemJs">
                            <a class="nav_link navLinkJs dropdown-toggle-custom" data-selector='["SubscriptionOrders"]' href="<?php echo UrlHelper::generateUrl('SubscriptionOrders'); ?>">
                                <span class="nav_icon">
                                    <svg class="svg" width="24" height="24">
                                        <use xlink:href="<?php echo CONF_WEBROOT_URL; ?>images/retina/sprite-aside-menu.svg<?php echo AttachedFile::setTimeParam(RELEASE_DATE); ?>#test">
                                        </use>
                                    </svg>
                                </span> <span class="nav_text navTextJs"><?php echo Labels::getLabel('NAV_SUBSCRIPTION_ORDERS', $siteLangId); ?></span>
                            </a>
                        </li>
                    <?php } ?>
                    <?php if ($objPrivilege->canViewOrderCancelReasons(AdminAuthentication::getLoggedAdminId(), true)) { ?>
                        <li class="nav_item navItemJs">
                            <a class="nav_link navLinkJs dropdown-toggle-custom" data-selector='["OrderCancelReasons"]' href="<?php echo UrlHelper::generateUrl('OrderCancelReasons'); ?>">
                                <span class="nav_icon">
                                    <svg class="svg" width="24" height="24">
                                        <use xlink:href="<?php echo CONF_WEBROOT_URL; ?>images/retina/sprite-aside-menu.svg<?php echo AttachedFile::setTimeParam(RELEASE_DATE); ?>#test">
                                        </use>
                                    </svg>
                                </span> <span class="nav_text navTextJs"><?php echo Labels::getLabel('NAV_ORDER_CANCEL_REASONS', $siteLangId); ?></span>
                            </a>
                        </li>
                    <?php } ?>
                    <?php if ($objPrivilege->canViewOrderReturnReasons(AdminAuthentication::getLoggedAdminId(), true)) { ?>
                        <li class="nav_item navItemJs">
                            <a class="nav_link navLinkJs dropdown-toggle-custom" data-selector='["OrderReturnReasons"]' href="<?php echo UrlHelper::generateUrl('OrderReturnReasons'); ?>">
                                <span class="nav_icon">
                                    <svg class="svg" width="24" height="24">
                                        <use xlink:href="<?php echo CONF_WEBROOT_URL; ?>images/retina/sprite-aside-menu.svg<?php echo AttachedFile::setTimeParam(RELEASE_DATE); ?>#test">
                                        </use>
                                    </svg>
                                </span>
                                <span class="nav_text navTextJs">
                                    <?php echo Labels::getLabel('NAV_ORDER_RETURN_REASONS', $siteLangId); ?>
                                </span>
                            </a>
                        </li>
                    <?php } ?>
                    <?php if ($objPrivilege->canViewOrderStatus(AdminAuthentication::getLoggedAdminId(), true)) { ?>
                        <li class="nav_item navItemJs">
                            <a class="nav_link navLinkJs dropdown-toggle-custom" data-selector='["OrderStatus"]' href="<?php echo UrlHelper::generateUrl('OrderStatus'); ?>">
                                <span class="nav_icon">
                                    <svg class="svg" width="24" height="24">
                                        <use xlink:href="<?php echo CONF_WEBROOT_URL; ?>images/retina/sprite-aside-menu.svg<?php echo AttachedFile::setTimeParam(RELEASE_DATE); ?>#test">
                                        </use>
                                    </svg>
                                </span> <span class="nav_text navTextJs"><?php echo Labels::getLabel('NAV_ORDER_STATUSES', $siteLangId); ?></span>
                            </a>
                        </li>
                    <?php } ?>
                    <?php if ($objPrivilege->canViewProductReviews(AdminAuthentication::getLoggedAdminId(), true)) { ?>
                        <li class="nav_item navItemJs">
                            <a class="nav_link navLinkJs dropdown-toggle-custom" data-selector='["ProductReviews"]' href="<?php echo UrlHelper::generateUrl('ProductReviews'); ?>">
                                <span class="nav_icon">
                                    <svg class="svg" width="24" height="24">
                                        <use xlink:href="<?php echo CONF_WEBROOT_URL; ?>images/retina/sprite-aside-menu.svg<?php echo AttachedFile::setTimeParam(RELEASE_DATE); ?>#test">
                                        </use>
                                    </svg>
                                </span> <span class="nav_text navTextJs"><?php echo Labels::getLabel('NAV_PRODUCT_REVIEWS', $siteLangId); ?></span>
                            </a>
                        </li>
                    <?php } ?>
                    <?php if ($objPrivilege->canViewAbandonedCart(AdminAuthentication::getLoggedAdminId(), true)) { ?>
                        <li class="nav_item navItemJs">
                            <a class="nav_link navLinkJs dropdown-toggle-custom" data-selector='["AbandonedCart", "AbandonedCartProducts"]' href="<?php echo UrlHelper::generateUrl('AbandonedCart'); ?>">
                                <span class="nav_icon">
                                    <svg class="svg" width="24" height="24">
                                        <use xlink:href="<?php echo CONF_WEBROOT_URL; ?>images/retina/sprite-aside-menu.svg<?php echo AttachedFile::setTimeParam(RELEASE_DATE); ?>#test">
                                        </use>
                                    </svg>
                                </span> <span class="nav_text navTextJs"><?php echo Labels::getLabel('NAV_ABANDONED_CART', $siteLangId); ?></span>
                            </a>
                        </li>
                    <?php } ?>
                </ul>
            </div>
        </li>
    <?php } ?>

    <?php
    if (
        $objPrivilege->canViewAdminUsers(AdminAuthentication::getLoggedAdminId(), true) ||
        $objPrivilege->canViewUsers(AdminAuthentication::getLoggedAdminId(), true) ||
        $objPrivilege->canViewMessages(AdminAuthentication::getLoggedAdminId(), true)
    ) {
    ?>
        <li class="menu-item dropdownJs">
            <button class="menu-section dropdown-toggle-custom menuLinkJs collapsed" type="button" <?php if (!$quickSearch) { ?>data-bs-toggle="collapse" data-bs-target="#NAV_USERS" <?php } ?> aria-expanded="true" aria-controls="collapseOne">
                <span class="menu-icon">
                    <svg class="svg" width="24" height="24">
                        <use xlink:href="<?php echo CONF_WEBROOT_URL; ?>images/retina/sprite-aside-menu.svg<?php echo AttachedFile::setTimeParam(RELEASE_DATE); ?>#icon-users">
                        </use>
                    </svg>
                </span>
                <span class="menu-title menuTitleJs"><?php echo Labels::getLabel('NAV_USERS', $siteLangId); ?></span>
                <?php if (!$quickSearch) { ?>
                    <i class="menu_arrow dropdown-toggle-custom-arrow"></i>
                <?php } ?>
            </button>
            <div class="sidebar-dropdown-menu <?php echo $collapseClass; ?>" <?php if (!$quickSearch) { ?>id="NAV_USERS" <?php } ?> aria-labelledby="" data-bs-parent="#sidebarNavLinks">
                <ul class="nav nav-level">
                    <?php if ($objPrivilege->canViewAdminUsers(AdminAuthentication::getLoggedAdminId(), true)) { ?>
                        <li class="nav_item navItemJs">
                            <a class="nav_link navLinkJs dropdown-toggle-custom" data-selector='["AdminUsers", "AdminPermissions"]' href="<?php echo UrlHelper::generateUrl('AdminUsers') ?>">
                                <span class="nav_icon">
                                    <svg class="svg" width="24" height="24">
                                        <use xlink:href="<?php echo CONF_WEBROOT_URL; ?>images/retina/sprite-aside-menu.svg<?php echo AttachedFile::setTimeParam(RELEASE_DATE); ?>#test">
                                        </use>
                                    </svg>
                                </span>
                                <span class="nav_text navTextJs"><?php echo Labels::getLabel('NAV_ADMIN_USERS', $siteLangId); ?></span>
                            </a>
                        </li>
                    <?php } ?>
                    <?php if ($objPrivilege->canViewUsers(AdminAuthentication::getLoggedAdminId(), true)) { ?>
                        <li class="nav_item navItemJs">
                            <a class="nav_link navLinkJs dropdown-toggle-custom" data-selector='["Users"]' href="<?php echo UrlHelper::generateUrl('Users'); ?>">
                                <span class="nav_icon">
                                    <svg class="svg" width="24" height="24">
                                        <use xlink:href="<?php echo CONF_WEBROOT_URL; ?>images/retina/sprite-aside-menu.svg<?php echo AttachedFile::setTimeParam(RELEASE_DATE); ?>#test">
                                        </use>
                                    </svg>
                                </span>
                                <span class="nav_text navTextJs"> <?php echo Labels::getLabel('NAV_USERS', $siteLangId); ?></span>
                            </a>
                        </li>
                        <li class="nav_item navItemJs">
                            <a class="nav_link navLinkJs dropdown-toggle-custom" data-selector='["Rewards"]' href="<?php echo UrlHelper::generateUrl('Rewards'); ?>">
                                <span class="nav_icon">
                                    <svg class="svg" width="24" height="24">
                                        <use xlink:href="<?php echo CONF_WEBROOT_URL; ?>images/retina/sprite-aside-menu.svg<?php echo AttachedFile::setTimeParam(RELEASE_DATE); ?>#test">
                                        </use>
                                    </svg>
                                </span>
                                <span class="nav_text navTextJs"><?php echo Labels::getLabel('NAV_REWARDS', $siteLangId); ?></span>
                            </a>
                        </li>
                        <li class="nav_item navItemJs">
                            <a class="nav_link navLinkJs dropdown-toggle-custom" data-selector='["Transactions"]' href="<?php echo UrlHelper::generateUrl('Transactions'); ?>">
                                <span class="nav_icon">
                                    <svg class="svg" width="24" height="24">
                                        <use xlink:href="<?php echo CONF_WEBROOT_URL; ?>images/retina/sprite-aside-menu.svg<?php echo AttachedFile::setTimeParam(RELEASE_DATE); ?>#test">
                                        </use>
                                    </svg>
                                </span>
                                <span class="nav_text navTextJs">
                                    <?php echo Labels::getLabel('NAV_TRANSACTIONS', $siteLangId); ?></span>
                            </a>
                        </li>
                        <li class="nav_item navItemJs">
                            <a class="nav_link navLinkJs dropdown-toggle-custom" data-selector='["DeletedUsers"]' href="<?php echo UrlHelper::generateUrl('DeletedUsers'); ?>">
                                <span class="nav_icon">
                                    <svg class="svg" width="24" height="24">
                                        <use xlink:href="<?php echo CONF_WEBROOT_URL; ?>images/retina/sprite-aside-menu.svg<?php echo AttachedFile::setTimeParam(RELEASE_DATE); ?>#test">
                                        </use>
                                    </svg>
                                </span>
                                <span class="nav_text navTextJs">
                                    <?php echo Labels::getLabel('NAV_DELETED_USERS', $siteLangId); ?></span>
                            </a>
                        </li>
                        <li class="nav_item navItemJs">
                            <a class="nav_link navLinkJs dropdown-toggle-custom" data-selector='["UsersAddresses"]' href="<?php echo UrlHelper::generateUrl('UsersAddresses'); ?>">
                                <span class="nav_icon">
                                    <svg class="svg" width="24" height="24">
                                        <use xlink:href="<?php echo CONF_WEBROOT_URL; ?>images/retina/sprite-aside-menu.svg<?php echo AttachedFile::setTimeParam(RELEASE_DATE); ?>#test">
                                        </use>
                                    </svg>
                                </span>
                                <span class="nav_text navTextJs"><?php echo Labels::getLabel('NAV_USERS_ADDRESSES', $siteLangId); ?></span>
                            </a>
                        </li>
                    <?php } ?>
                    <?php if ($objPrivilege->canViewMessages(AdminAuthentication::getLoggedAdminId(), true)) { ?>
                        <li class="nav_item navItemJs">
                            <a class="nav_link navLinkJs dropdown-toggle-custom" data-selector='["Messages"]' href="<?php echo UrlHelper::generateUrl('Messages') ?>">
                                <span class="nav_icon">
                                    <svg class="svg" width="24" height="24">
                                        <use xlink:href="<?php echo CONF_WEBROOT_URL; ?>images/retina/sprite-aside-menu.svg<?php echo AttachedFile::setTimeParam(RELEASE_DATE); ?>#test">
                                        </use>
                                    </svg>
                                </span>
                                <span class="nav_text navTextJs"><?php echo Labels::getLabel('NAV_MESSAGES', $siteLangId); ?></span>
                            </a>
                        </li>
                    <?php } ?>
                </ul>
            </div>
        </li>
    <?php } ?>

    <?php
    if (
        $objPrivilege->canViewProducts(AdminAuthentication::getLoggedAdminId(), true) ||
        $objPrivilege->canViewPromotions(AdminAuthentication::getLoggedAdminId(), true) ||
        $objPrivilege->canViewRewardsOnPurchase(AdminAuthentication::getLoggedAdminId(), true) ||
        $objPrivilege->canViewRecomendedWeightages(AdminAuthentication::getLoggedAdminId(), true) ||
        $objPrivilege->canViewDiscountCoupons(AdminAuthentication::getLoggedAdminId(), true) ||
        $objPrivilege->canViewPushNotification(AdminAuthentication::getLoggedAdminId(), true) ||
        $objPrivilege->canViewBadgesAndRibbons(AdminAuthentication::getLoggedAdminId(), true)
    ) {
    ?>
        <li class="menu-item dropdownJs">
            <button class="menu-section dropdown-toggle-custom menuLinkJs collapsed" type="button" <?php if (!$quickSearch) { ?>data-bs-toggle="collapse" data-bs-target="#NAV_PROMOTIONS" <?php } ?> aria-expanded="true" aria-controls="collapseOne">
                <span class="menu-icon">
                    <svg class="svg" width="24" height="24">
                        <use xlink:href="<?php echo CONF_WEBROOT_URL; ?>images/retina/sprite-aside-menu.svg<?php echo AttachedFile::setTimeParam(RELEASE_DATE); ?>#icon-promotions">
                        </use>
                    </svg>
                </span>
                <span class="menu-title menuTitleJs"><?php echo Labels::getLabel('NAV_PROMOTIONS', $siteLangId); ?></span>
                <?php if (!$quickSearch) { ?>
                    <i class="menu_arrow dropdown-toggle-custom-arrow"></i>
                <?php } ?>
            </button>
            <div class="sidebar-dropdown-menu <?php echo $collapseClass; ?>" <?php if (!$quickSearch) { ?>id="NAV_PROMOTIONS" <?php } ?> aria-labelledby="" data-bs-parent="#sidebarNavLinks">
                <ul class="nav nav-level">
                    <?php if ($objPrivilege->canViewProducts(AdminAuthentication::getLoggedAdminId(), true)) { ?>
                        <li class="nav_item navItemJs">
                            <a class="nav_link navLinkJs dropdown-toggle-custom" data-selector='["SpecialPrice"]' href="<?php echo UrlHelper::generateUrl('SpecialPrice'); ?>">
                                <span class="nav_icon">
                                    <svg class="svg" width="24" height="24">
                                        <use xlink:href="<?php echo CONF_WEBROOT_URL; ?>images/retina/sprite-aside-menu.svg<?php echo AttachedFile::setTimeParam(RELEASE_DATE); ?>#test">
                                        </use>
                                    </svg>
                                </span>
                                <span class="nav_text navTextJs"><?php echo Labels::getLabel('NAV_SPECIAL_PRICE', $siteLangId); ?></span>
                            </a>
                        </li>

                        <li class="nav_item navItemJs">
                            <a class="nav_link navLinkJs dropdown-toggle-custom" data-selector='["VolumeDiscount"]' href="<?php echo UrlHelper::generateUrl('VolumeDiscount'); ?>">
                                <span class="nav_icon">
                                    <svg class="svg" width="24" height="24">
                                        <use xlink:href="<?php echo CONF_WEBROOT_URL; ?>images/retina/sprite-aside-menu.svg<?php echo AttachedFile::setTimeParam(RELEASE_DATE); ?>#test">
                                        </use>
                                    </svg>
                                </span>
                                <span class="nav_text navTextJs"><?php echo Labels::getLabel('NAV_VOLUME_DISCOUNT', $siteLangId); ?></span>
                            </a>
                        </li>

                        <li class="nav_item navItemJs">
                            <a class="nav_link navLinkJs dropdown-toggle-custom" data-selector='["RelatedProducts"]' href="<?php echo UrlHelper::generateUrl('RelatedProducts'); ?>">
                                <span class="nav_icon">
                                    <svg class="svg" width="24" height="24">
                                        <use xlink:href="<?php echo CONF_WEBROOT_URL; ?>images/retina/sprite-aside-menu.svg<?php echo AttachedFile::setTimeParam(RELEASE_DATE); ?>#test">
                                        </use>
                                    </svg>
                                </span>
                                <span class="nav_text navTextJs">
                                    <?php echo Labels::getLabel('NAV_RELATED_PRODUCTS', $siteLangId); ?></span>
                            </a>
                        </li>
                        <li class="nav_item navItemJs">
                            <a class="nav_link navLinkJs dropdown-toggle-custom" data-selector='["BuyTogetherProducts"]' href="<?php echo UrlHelper::generateUrl('BuyTogetherProducts'); ?>">
                                <span class="nav_icon">
                                    <svg class="svg" width="24" height="24">
                                        <use xlink:href="<?php echo CONF_WEBROOT_URL; ?>images/retina/sprite-aside-menu.svg<?php echo AttachedFile::setTimeParam(RELEASE_DATE); ?>#test">
                                        </use>
                                    </svg>
                                </span>
                                <span class="nav_text navTextJs">
                                    <?php echo Labels::getLabel('NAV_BUY_TOGETHER_PRODUCTS', $siteLangId); ?></span>
                            </a>
                        </li>
                    <?php } ?>

                    <?php if ($objPrivilege->canViewPromotions(AdminAuthentication::getLoggedAdminId(), true)) { ?>
                        <li class="nav_item navItemJs">
                            <a class="nav_link navLinkJs dropdown-toggle-custom" data-selector='["Promotions"]' href="<?php echo UrlHelper::generateUrl('promotions'); ?>">
                                <span class="nav_icon">
                                    <svg class="svg" width="24" height="24">
                                        <use xlink:href="<?php echo CONF_WEBROOT_URL; ?>images/retina/sprite-aside-menu.svg<?php echo AttachedFile::setTimeParam(RELEASE_DATE); ?>#test">
                                        </use>
                                    </svg>
                                </span>
                                <span class="nav_text navTextJs">
                                    <?php echo Labels::getLabel('NAV_PPC_PROMOTION_MANAGEMENT', $siteLangId); ?></span>
                            </a>
                        </li>
                    <?php } ?>


                    <?php if ($objPrivilege->canViewRewardsOnPurchase(AdminAuthentication::getLoggedAdminId(), true)) { ?>
                        <li class="nav_item navItemJs">
                            <a class="nav_link navLinkJs dropdown-toggle-custom" data-selector='["RewardsOnPurchase"]' href="<?php echo UrlHelper::generateUrl('RewardsOnPurchase'); ?>">
                                <span class="nav_icon">
                                    <svg class="svg" width="24" height="24">
                                        <use xlink:href="<?php echo CONF_WEBROOT_URL; ?>images/retina/sprite-aside-menu.svg<?php echo AttachedFile::setTimeParam(RELEASE_DATE); ?>#test">
                                        </use>
                                    </svg>
                                </span>
                                <span class="nav_text navTextJs">
                                    <?php echo Labels::getLabel('NAV_REWARDS_ON_PURCHASE', $siteLangId); ?></span>
                            </a>
                        </li>
                    <?php } ?>

                    <?php if ($objPrivilege->canViewRecomendedWeightages(AdminAuthentication::getLoggedAdminId(), true)) { ?>
                        <li class="nav_item navItemJs">
                            <a class="nav_link navLinkJs dropdown-toggle-custom" data-selector='["SmartRecomendedWeightages"]' href="<?php echo UrlHelper::generateUrl('SmartRecomendedWeightages'); ?>">
                                <span class="nav_icon">
                                    <svg class="svg" width="24" height="24">
                                        <use xlink:href="<?php echo CONF_WEBROOT_URL; ?>images/retina/sprite-aside-menu.svg<?php echo AttachedFile::setTimeParam(RELEASE_DATE); ?>#test">
                                        </use>
                                    </svg>
                                </span>
                                <span class="nav_text navTextJs">
                                    <?php echo Labels::getLabel('NAV_MANAGE_WEIGHTAGES', $siteLangId); ?></span>
                            </a>
                        </li>
                        <li class="nav_item navItemJs">
                            <a class="nav_link navLinkJs dropdown-toggle-custom" data-selector='["RecomendedTagProducts"]' href="<?php echo UrlHelper::generateUrl('RecomendedTagProducts'); ?>">
                                <span class="nav_icon">
                                    <svg class="svg" width="24" height="24">
                                        <use xlink:href="<?php echo CONF_WEBROOT_URL; ?>images/retina/sprite-aside-menu.svg<?php echo AttachedFile::setTimeParam(RELEASE_DATE); ?>#test">
                                        </use>
                                    </svg>
                                </span>
                                <span class="nav_text navTextJs">
                                    <?php echo Labels::getLabel('NAV_RECOMMENDED_TAG_PRODUCTS_WEIGHTAGES', $siteLangId); ?>
                                </span>
                            </a>
                        </li>
                    <?php } ?>
                    <?php if ($objPrivilege->canViewDiscountCoupons(AdminAuthentication::getLoggedAdminId(), true)) { ?>
                        <li class="nav_item navItemJs">
                            <a class="nav_link navLinkJs dropdown-toggle-custom" data-selector='["DiscountCoupons"]' href="<?php echo UrlHelper::generateUrl('DiscountCoupons'); ?>">
                                <span class="nav_icon">
                                    <svg class="svg" width="24" height="24">
                                        <use xlink:href="<?php echo CONF_WEBROOT_URL; ?>images/retina/sprite-aside-menu.svg<?php echo AttachedFile::setTimeParam(RELEASE_DATE); ?>#test">
                                        </use>
                                    </svg>
                                </span>
                                <span class="nav_text navTextJs">
                                    <?php echo Labels::getLabel('NAV_DISCOUNT_COUPONS', $siteLangId); ?></span>
                            </a>
                        </li>
                    <?php } ?>
                    <?php
                    $active = (new Plugin())->getDefaultPluginData(Plugin::TYPE_PUSH_NOTIFICATION, 'plugin_active');
                    if ($objPrivilege->canViewPushNotification(AdminAuthentication::getLoggedAdminId(), true) && false != $active && !empty($active)) { ?>
                        <li class="nav_item navItemJs">
                            <a class="nav_link navLinkJs dropdown-toggle-custom" data-selector='["PushNotifications"]' href="<?php echo UrlHelper::generateUrl('PushNotifications'); ?>">
                                <span class="nav_icon">
                                    <svg class="svg" width="24" height="24">
                                        <use xlink:href="<?php echo CONF_WEBROOT_URL; ?>images/retina/sprite-aside-menu.svg<?php echo AttachedFile::setTimeParam(RELEASE_DATE); ?>#mobile">
                                        </use>
                                    </svg>
                                </span>
                                <span class="nav_text navTextJs">
                                    <?php echo Labels::getLabel('NAV_PUSH_NOTIFICATIONS', $siteLangId); ?></span>
                            </a>
                        </li>
                    <?php } ?>
                    <?php if ($objPrivilege->canViewBadgesAndRibbons(AdminAuthentication::getLoggedAdminId(), true)) { ?>
                        <li class="nav_item navItemJs">
                            <a class="nav_link navLinkJs dropdown-toggle-custom" data-selector='["Badges"]' href="<?php echo UrlHelper::generateUrl('Badges'); ?>">
                                <span class="nav_icon">
                                    <svg class="svg" width="24" height="24">
                                        <use xlink:href="<?php echo CONF_WEBROOT_URL; ?>images/retina/sprite-aside-menu.svg<?php echo AttachedFile::setTimeParam(RELEASE_DATE); ?>#icon-badge">
                                        </use>
                                    </svg>
                                </span>
                                <span class="nav_text navTextJs">
                                    <?php echo Labels::getLabel('NAV_BADGES', $siteLangId); ?></span>
                            </a>
                        </li>
                        <li class="nav_item navItemJs">
                            <a class="nav_link navLinkJs dropdown-toggle-custom" data-selector='["Ribbons"]' href="<?php echo UrlHelper::generateUrl('Ribbons'); ?>">
                                <span class="nav_icon">
                                    <svg class="svg" width="24" height="24">
                                        <use xlink:href="<?php echo CONF_WEBROOT_URL; ?>images/retina/sprite-aside-menu.svg<?php echo AttachedFile::setTimeParam(RELEASE_DATE); ?>#icon-ribbon">
                                        </use>
                                    </svg>
                                </span>
                                <span class="nav_text navTextJs">
                                    <?php echo Labels::getLabel('NAV_RIBBONS', $siteLangId); ?></span>
                            </a>
                        </li>
                    <?php } ?>
                </ul>
            </div>
        </li>
    <?php } ?>

    <?php
    if (
        $objPrivilege->canViewBlogPostCategories(AdminAuthentication::getLoggedAdminId(), true) ||
        $objPrivilege->canViewBlogPosts(AdminAuthentication::getLoggedAdminId(), true) ||
        $objPrivilege->canViewBlogContributions(AdminAuthentication::getLoggedAdminId(), true) ||
        $objPrivilege->canViewBlogComments(AdminAuthentication::getLoggedAdminId(), true)
    ) {
    ?>
        <li class="menu-item dropdownJs">
            <button class="menu-section dropdown-toggle-custom menuLinkJs collapsed" type="button" <?php if (!$quickSearch) { ?>data-bs-toggle="collapse" data-bs-target="#NAV_BLOG" <?php } ?> aria-expanded="true" aria-controls="collapseOne">
                <span class="menu-icon">
                    <svg class="svg" width="24" height="24">
                        <use xlink:href="<?php echo CONF_WEBROOT_URL; ?>images/retina/sprite-aside-menu.svg<?php echo AttachedFile::setTimeParam(RELEASE_DATE); ?>#icon-blog">
                        </use>
                    </svg>
                </span>
                <span class="menu-title menuTitleJs"><?php echo Labels::getLabel('NAV_BLOG', $siteLangId); ?></span>
                <?php if (!$quickSearch) { ?>
                    <i class="menu_arrow dropdown-toggle-custom-arrow"></i>
                <?php } ?>
            </button>
            <div class="sidebar-dropdown-menu <?php echo $collapseClass; ?>" <?php if (!$quickSearch) { ?>id="NAV_BLOG" <?php } ?> aria-labelledby="" data-bs-parent="#sidebarNavLinks">
                <ul class="nav nav-level">
                    <?php if ($objPrivilege->canViewBlogPostCategories(AdminAuthentication::getLoggedAdminId(), true)) { ?>
                        <li class="nav_item navItemJs">
                            <a class="nav_link navLinkJs dropdown-toggle-custom" data-selector='["BlogPostCategories"]' href="<?php echo UrlHelper::generateUrl('BlogPostCategories'); ?>">
                                <span class="nav_icon">
                                    <svg class="svg" width="24" height="24">
                                        <use xlink:href="<?php echo CONF_WEBROOT_URL; ?>images/retina/sprite-aside-menu.svg<?php echo AttachedFile::setTimeParam(RELEASE_DATE); ?>#test">
                                        </use>
                                    </svg>
                                </span> <span class="nav_text navTextJs"><?php echo Labels::getLabel('NAV_BLOG_POST_CATEGORIES', $siteLangId); ?></span>
                            </a>
                        </li>
                    <?php } ?>
                    <?php if ($objPrivilege->canViewBlogPosts(AdminAuthentication::getLoggedAdminId(), true)) { ?>
                        <li class="nav_item navItemJs">
                            <a class="nav_link navLinkJs dropdown-toggle-custom" data-selector='["BlogPosts"]' href="<?php echo UrlHelper::generateUrl('BlogPosts'); ?>">
                                <span class="nav_icon">
                                    <svg class="svg" width="24" height="24">
                                        <use xlink:href="<?php echo CONF_WEBROOT_URL; ?>images/retina/sprite-aside-menu.svg<?php echo AttachedFile::setTimeParam(RELEASE_DATE); ?>#test">
                                        </use>
                                    </svg>
                                </span> <span class="nav_text navTextJs"><?php echo Labels::getLabel('NAV_BLOG_POSTS', $siteLangId); ?></span>
                            </a>
                        </li>
                    <?php } ?>
                    <?php if ($objPrivilege->canViewBlogContributions(AdminAuthentication::getLoggedAdminId(), true)) { ?>
                        <li class="nav_item navItemJs">
                            <a class="nav_link navLinkJs dropdown-toggle-custom" data-selector='["BlogContributions"]' href="<?php echo UrlHelper::generateUrl('BlogContributions'); ?>">
                                <span class="nav_icon">
                                    <svg class="svg" width="24" height="24">
                                        <use xlink:href="<?php echo CONF_WEBROOT_URL; ?>images/retina/sprite-aside-menu.svg<?php echo AttachedFile::setTimeParam(RELEASE_DATE); ?>#test">
                                        </use>
                                    </svg>
                                </span> <span class="nav_text navTextJs"><?php echo Labels::getLabel('NAV_BLOG_CONTRIBUTIONS', $siteLangId); ?></span>
                            </a>
                        </li>
                    <?php } ?>
                    <?php if ($objPrivilege->canViewBlogComments(AdminAuthentication::getLoggedAdminId(), true)) { ?>
                        <li class="nav_item navItemJs">
                            <a class="nav_link navLinkJs dropdown-toggle-custom" data-selector='["BlogComments"]' href="<?php echo UrlHelper::generateUrl('BlogComments'); ?>">
                                <span class="nav_icon">
                                    <svg class="svg" width="24" height="24">
                                        <use xlink:href="<?php echo CONF_WEBROOT_URL; ?>images/retina/sprite-aside-menu.svg<?php echo AttachedFile::setTimeParam(RELEASE_DATE); ?>#test">
                                        </use>
                                    </svg>
                                </span><span class="nav_text navTextJs"><?php echo Labels::getLabel('NAV_BLOG_COMMENTS', $siteLangId); ?></span>
                            </a>
                        </li>
                    <?php } ?>
                </ul>
            </div>
        </li>
    <?php } ?>

    <?php
    if (
        $objPrivilege->canViewTax(AdminAuthentication::getLoggedAdminId(), true)
    ) {
        $active = (new Plugin())->getDefaultPluginData(Plugin::TYPE_TAX_SERVICES, 'plugin_active');
    ?>
        <li class="menu-item dropdownJs">
            <button class="menu-section dropdown-toggle-custom menuLinkJs collapsed" type="button" <?php if (!$quickSearch) { ?>data-bs-toggle="collapse" data-bs-target="#NAV_TAX" <?php } ?> aria-expanded="true" aria-controls="collapseOne">
                <span class="menu-icon">
                    <svg class="svg" width="24" height="24">
                        <use xlink:href="<?php echo CONF_WEBROOT_URL; ?>images/retina/sprite-aside-menu.svg<?php echo AttachedFile::setTimeParam(RELEASE_DATE); ?>#icon-sales-tax">
                        </use>
                    </svg>
                </span>
                <span class="menu-title menuTitleJs"><?php echo Labels::getLabel('NAV_TAX', $siteLangId); ?></span>
                <?php if (!$quickSearch) { ?>
                    <i class="menu_arrow dropdown-toggle-custom-arrow"></i>
                <?php } ?>
            </button>
            <div class="sidebar-dropdown-menu <?php echo $collapseClass; ?>" <?php if (!$quickSearch) { ?>id="NAV_TAX" <?php } ?> aria-labelledby="" data-bs-parent="#sidebarNavLinks">
                <ul class="nav nav-level">
                    <?php if ($objPrivilege->canViewTax(AdminAuthentication::getLoggedAdminId(), true)) { ?>
                        <?php if (false === $active) { ?>
                            <li class="nav_item navItemJs">
                                <a class="nav_link navLinkJs dropdown-toggle-custom" data-selector='["TaxStructure"]' href="<?php echo UrlHelper::generateUrl('TaxStructure'); ?>">
                                    <span class="nav_icon">
                                        <svg class="svg" width="24" height="24">
                                            <use xlink:href="<?php echo CONF_WEBROOT_URL; ?>images/retina/sprite-aside-menu.svg<?php echo AttachedFile::setTimeParam(RELEASE_DATE); ?>#test">
                                            </use>
                                        </svg>
                                    </span> <span class="nav_text navTextJs"><?php echo Labels::getLabel('NAV_TAX_STRUCTURE', $siteLangId); ?></span>
                                </a>
                            </li>
                        <?php } ?>
                        <li class="nav_item navItemJs">
                            <a class="nav_link navLinkJs dropdown-toggle-custom" data-selector='["TaxCategories"]' href="<?php echo UrlHelper::generateUrl('TaxCategories'); ?>">
                                <span class="nav_icon">
                                    <svg class="svg" width="24" height="24">
                                        <use xlink:href="<?php echo CONF_WEBROOT_URL; ?>images/retina/sprite-aside-menu.svg<?php echo AttachedFile::setTimeParam(RELEASE_DATE); ?>#test">
                                        </use>
                                    </svg>
                                </span> <span class="nav_text navTextJs"><?php echo Labels::getLabel('NAV_TAX_CATEGORIES', $siteLangId); ?></span>
                            </a>
                        </li>
                        <?php if (false === $active) { ?>
                            <li class="nav_item navItemJs">
                                <a class="nav_link navLinkJs dropdown-toggle-custom" data-selector='["TaxCategoriesRule"]' href="<?php echo UrlHelper::generateUrl('TaxCategoriesRule'); ?>">
                                    <span class="nav_icon">
                                        <svg class="svg" width="24" height="24">
                                            <use xlink:href="<?php echo CONF_WEBROOT_URL; ?>images/retina/sprite-aside-menu.svg<?php echo AttachedFile::setTimeParam(RELEASE_DATE); ?>#test">
                                            </use>
                                        </svg>
                                    </span> <span class="nav_text navTextJs"><?php echo Labels::getLabel('NAV_TAX_CATEGORIES_RULE', $siteLangId); ?></span>
                                </a>
                            </li>
                        <?php } ?>
                    <?php } ?>
                </ul>
            </div>
        </li>
    <?php } ?>

    <?php
    if (
        $objPrivilege->canViewSlides(AdminAuthentication::getLoggedAdminId(), true) ||
        $objPrivilege->canViewBanners(AdminAuthentication::getLoggedAdminId(), true) ||
        $objPrivilege->canViewContentPages(AdminAuthentication::getLoggedAdminId(), true) ||
        $objPrivilege->canViewContentBlocks(AdminAuthentication::getLoggedAdminId(), true) ||
        $objPrivilege->canViewFaqCategories(AdminAuthentication::getLoggedAdminId(), true) ||
        $objPrivilege->canViewTestimonial(AdminAuthentication::getLoggedAdminId(), true) ||
        $objPrivilege->canViewNavigationManagement(AdminAuthentication::getLoggedAdminId(), true) ||
        $objPrivilege->canViewCollections(AdminAuthentication::getLoggedAdminId(), true) ||
        $objPrivilege->canViewImportInstructions(AdminAuthentication::getLoggedAdminId(), true)
    ) {
    ?>
        <li class="menu-item dropdownJs">
            <button class="menu-section dropdown-toggle-custom menuLinkJs collapsed" type="button" <?php if (!$quickSearch) { ?>data-bs-toggle="collapse" data-bs-target="#NAV_CMS" <?php } ?> aria-expanded="true" aria-controls="collapseOne">
                <span class="menu-icon">
                    <svg class="svg" width="24" height="24">
                        <use xlink:href="<?php echo CONF_WEBROOT_URL; ?>images/retina/sprite-aside-menu.svg<?php echo AttachedFile::setTimeParam(RELEASE_DATE); ?>#icon-CMS">
                        </use>
                    </svg>
                </span>
                <span class="menu-title menuTitleJs"><?php echo Labels::getLabel('NAV_CMS', $siteLangId); ?></span>
                <?php if (!$quickSearch) { ?>
                    <i class="menu_arrow dropdown-toggle-custom-arrow"></i>
                <?php } ?>
            </button>
            <div class="sidebar-dropdown-menu <?php echo $collapseClass; ?>" <?php if (!$quickSearch) { ?>id="NAV_CMS" <?php } ?> aria-labelledby="" data-bs-parent="#sidebarNavLinks">
                <ul class="nav nav-level">
                    <?php if ($objPrivilege->canViewSlides(AdminAuthentication::getLoggedAdminId(), true)) { ?>
                        <li class="nav_item navItemJs">
                            <a class="nav_link navLinkJs dropdown-toggle-custom" data-selector='["Slides"]' href="<?php echo UrlHelper::generateUrl('Slides'); ?>">
                                <span class="nav_icon">
                                    <svg class="svg" width="24" height="24">
                                        <use xlink:href="<?php echo CONF_WEBROOT_URL; ?>images/retina/sprite-aside-menu.svg<?php echo AttachedFile::setTimeParam(RELEASE_DATE); ?>#test">
                                        </use>
                                    </svg>
                                </span>
                                <span class="nav_text navTextJs">
                                    <?php echo Labels::getLabel('NAV_HOME_PAGE_SLIDES', $siteLangId); ?>
                                </span>
                            </a>
                        </li>
                    <?php } ?>
                    <?php if ($objPrivilege->canViewBanners(AdminAuthentication::getLoggedAdminId(), true)) { ?>
                        <li class="nav_item navItemJs">
                            <a class="nav_link navLinkJs dropdown-toggle-custom" data-selector='["BannerLocation", "Banners"]' href="<?php echo UrlHelper::generateUrl('BannerLocation'); ?>">
                                <span class="nav_icon">
                                    <svg class="svg" width="24" height="24">
                                        <use xlink:href="<?php echo CONF_WEBROOT_URL; ?>images/retina/sprite-aside-menu.svg<?php echo AttachedFile::setTimeParam(RELEASE_DATE); ?>#test">
                                        </use>
                                    </svg>
                                </span>
                                <span class="nav_text navTextJs">
                                    <?php echo Labels::getLabel('NAV_BANNERS', $siteLangId); ?>
                                </span>
                            </a>
                        </li>
                    <?php } ?>
                    <?php if ($objPrivilege->canViewContentPages(AdminAuthentication::getLoggedAdminId(), true)) { ?>
                        <li class="nav_item navItemJs">
                            <a class="nav_link navLinkJs dropdown-toggle-custom" data-selector='["ContentPages"]' href="<?php echo UrlHelper::generateUrl('ContentPages'); ?>">
                                <span class="nav_icon">
                                    <svg class="svg" width="24" height="24">
                                        <use xlink:href="<?php echo CONF_WEBROOT_URL; ?>images/retina/sprite-aside-menu.svg<?php echo AttachedFile::setTimeParam(RELEASE_DATE); ?>#test">
                                        </use>
                                    </svg>
                                </span>
                                <span class="nav_text navTextJs">
                                    <?php echo Labels::getLabel('NAV_CONTENT_PAGES', $siteLangId); ?>
                                </span>
                            </a>
                        </li>
                    <?php } ?>
                    <?php if ($objPrivilege->canViewContentBlocks(AdminAuthentication::getLoggedAdminId(), true)) { ?>
                        <li class="nav_item navItemJs">
                            <a class="nav_link navLinkJs dropdown-toggle-custom" data-selector='["ContentBlock"]' href="<?php echo UrlHelper::generateUrl('ContentBlock'); ?>">
                                <span class="nav_icon">
                                    <svg class="svg" width="24" height="24">
                                        <use xlink:href="<?php echo CONF_WEBROOT_URL; ?>images/retina/sprite-aside-menu.svg<?php echo AttachedFile::setTimeParam(RELEASE_DATE); ?>#test">
                                        </use>
                                    </svg>
                                </span> <span class="nav_text navTextJs"><?php echo Labels::getLabel('NAV_CONTENT_BLOCK', $siteLangId); ?></span>
                            </a>
                        </li>
                    <?php } ?>
                    <?php if ($objPrivilege->canViewImportInstructions(AdminAuthentication::getLoggedAdminId(), true)) { ?>
                        <li class="nav_item navItemJs">
                            <a class="nav_link navLinkJs dropdown-toggle-custom" data-selector='["ImportInstructions"]' href="<?php echo UrlHelper::generateUrl('ImportInstructions'); ?>">
                                <span class="nav_icon">
                                    <svg class="svg" width="24" height="24">
                                        <use xlink:href="<?php echo CONF_WEBROOT_URL; ?>images/retina/sprite-aside-menu.svg<?php echo AttachedFile::setTimeParam(RELEASE_DATE); ?>#test">
                                        </use>
                                    </svg>
                                </span> <span class="nav_text navTextJs"><?php echo Labels::getLabel('NAV_IMPORT_INSTRUCTIONS', $siteLangId); ?></span>
                            </a>
                        </li>
                    <?php } ?>
                    <?php if ($objPrivilege->canViewFaqCategories(AdminAuthentication::getLoggedAdminId(), true)) { ?>
                        <li class="nav_item navItemJs">
                            <a class="nav_link navLinkJs dropdown-toggle-custom" data-selector='["FaqCategories", "Faq"]' href="<?php echo UrlHelper::generateUrl('FaqCategories'); ?>">
                                <span class="nav_icon">
                                    <svg class="svg" width="24" height="24">
                                        <use xlink:href="<?php echo CONF_WEBROOT_URL; ?>images/retina/sprite-aside-menu.svg<?php echo AttachedFile::setTimeParam(RELEASE_DATE); ?>#test">
                                        </use>
                                    </svg>
                                </span> <span class="nav_text navTextJs"><?php echo Labels::getLabel('NAV_FAQS', $siteLangId); ?></span>
                            </a>
                        </li>
                    <?php } ?>
                    <?php if ($objPrivilege->canViewTestimonial(AdminAuthentication::getLoggedAdminId(), true)) { ?>
                        <li class="nav_item navItemJs">
                            <a class="nav_link navLinkJs dropdown-toggle-custom" data-selector='["Testimonials"]' href="<?php echo UrlHelper::generateUrl('Testimonials'); ?>">
                                <span class="nav_icon">
                                    <svg class="svg" width="24" height="24">
                                        <use xlink:href="<?php echo CONF_WEBROOT_URL; ?>images/retina/sprite-aside-menu.svg<?php echo AttachedFile::setTimeParam(RELEASE_DATE); ?>#test">
                                        </use>
                                    </svg>
                                </span> <span class="nav_text navTextJs"><?php echo Labels::getLabel('NAV_TESTIMONIALS', $siteLangId); ?></span>
                            </a>
                        </li>
                    <?php } ?>
                    <?php if ($objPrivilege->canViewNavigationManagement(AdminAuthentication::getLoggedAdminId(), true)) { ?>
                        <li class="nav_item navItemJs">
                            <a class="nav_link navLinkJs dropdown-toggle-custom" data-selector='["Navigations"]' href="<?php echo UrlHelper::generateUrl('Navigations'); ?>">
                                <span class="nav_icon">
                                    <svg class="svg" width="24" height="24">
                                        <use xlink:href="<?php echo CONF_WEBROOT_URL; ?>images/retina/sprite-aside-menu.svg<?php echo AttachedFile::setTimeParam(RELEASE_DATE); ?>#test">
                                        </use>
                                    </svg>
                                </span> <span class="nav_text navTextJs"><?php echo Labels::getLabel('NAV_NAVIGATIONS', $siteLangId); ?></span>
                            </a>
                        </li>
                    <?php } ?>
                    <?php if ($objPrivilege->canViewCollections(AdminAuthentication::getLoggedAdminId(), true)) { ?>
                        <li class="nav_item navItemJs">
                            <a class="nav_link navLinkJs dropdown-toggle-custom" data-selector='["Collections"]' href="<?php echo UrlHelper::generateUrl('Collections'); ?>">
                                <span class="nav_icon">
                                    <svg class="svg" width="24" height="24">
                                        <use xlink:href="<?php echo CONF_WEBROOT_URL; ?>images/retina/sprite-aside-menu.svg<?php echo AttachedFile::setTimeParam(RELEASE_DATE); ?>#test">
                                        </use>
                                    </svg>
                                </span> <span class="nav_text navTextJs"><?php echo Labels::getLabel('NAV_COLLECTIONS', $siteLangId); ?></span>
                            </a>
                        </li>
                    <?php } ?>
                </ul>
            </div>
        </li>
    <?php } ?>

    <?php
    if (
        $objPrivilege->canViewSalesReport(AdminAuthentication::getLoggedAdminId(), true) ||
        $objPrivilege->canViewUsersReport(AdminAuthentication::getLoggedAdminId(), true) ||
        $objPrivilege->canViewProductsReport(AdminAuthentication::getLoggedAdminId(), true) ||
        $objPrivilege->canViewCatalogReport(AdminAuthentication::getLoggedAdminId(), true) ||
        $objPrivilege->canViewShopsReport(AdminAuthentication::getLoggedAdminId(), true) ||
        /* $objPrivilege->canViewTaxReport(AdminAuthentication::getLoggedAdminId(), true) ||
                      $objPrivilege->canViewCommissionReport(AdminAuthentication::getLoggedAdminId(), true) || */
        $objPrivilege->canViewPerformanceReport(AdminAuthentication::getLoggedAdminId(), true) ||
        $objPrivilege->canViewAffiliatesReport(AdminAuthentication::getLoggedAdminId(), true) ||
        $objPrivilege->canViewBuyersReport(AdminAuthentication::getLoggedAdminId(), true) ||
        $objPrivilege->canViewSellersReport(AdminAuthentication::getLoggedAdminId(), true) ||
        $objPrivilege->canViewAdvertisersReport(AdminAuthentication::getLoggedAdminId(), true) ||
        $objPrivilege->canViewFinancialReport(AdminAuthentication::getLoggedAdminId(), true) ||
        $objPrivilege->canViewSubscriptionReport(AdminAuthentication::getLoggedAdminId(), true)
    ) {
    ?>
        <li class="menu-item dropdownJs">
            <button class="menu-section dropdown-toggle-custom menuLinkJs collapsed" type="button" <?php if (!$quickSearch) { ?>data-bs-toggle="collapse" data-bs-target="#NAV_REPORTS" <?php } ?> aria-expanded="true" aria-controls="collapseOne">
                <span class="menu-icon">
                    <svg class="svg" width="24" height="24">
                        <use xlink:href="<?php echo CONF_WEBROOT_URL; ?>images/retina/sprite-aside-menu.svg<?php echo AttachedFile::setTimeParam(RELEASE_DATE); ?>#icon-reports">
                        </use>
                    </svg>
                </span>
                <span class="menu-title menuTitleJs"><?php echo Labels::getLabel('NAV_REPORTS', $siteLangId); ?></span>
                <?php if (!$quickSearch) { ?>
                    <i class="menu_arrow dropdown-toggle-custom-arrow"></i>
                <?php } ?>
            </button>
            <div class="sidebar-dropdown-menu <?php echo $collapseClass; ?>" <?php if (!$quickSearch) { ?>id="NAV_REPORTS" <?php } ?> aria-labelledby="" data-bs-parent="#sidebarNavLinks">
                <ul class="nav nav-level" <?php if (!$quickSearch) { ?>id="reportsNav" <?php } ?>>
                    <?php
                    if (
                        $objPrivilege->canViewSalesReport(AdminAuthentication::getLoggedAdminId(), true) ||
                        $objPrivilege->canViewCatalogReport(AdminAuthentication::getLoggedAdminId(), true) ||
                        $objPrivilege->canViewProductsReport(AdminAuthentication::getLoggedAdminId(), true) ||
                        $objPrivilege->canViewShopsReport(AdminAuthentication::getLoggedAdminId(), true) ||
                        $objPrivilege->canViewBuyersReport(AdminAuthentication::getLoggedAdminId(), true)
                    ) {
                    ?>
                        <li class="nav_item hasNestedChildJs">
                            <a class="nav_link navLinkJs dropdown-toggle-custom collapsed" data-bs-toggle="collapse" href="#salesReportNav" aria-expanded="true">
                                <span class="nav_icon">
                                    <svg class="svg" width="24" height="24">
                                        <use xlink:href="<?php echo CONF_WEBROOT_URL; ?>images/retina/sprite-aside-menu.svg<?php echo AttachedFile::setTimeParam(RELEASE_DATE); ?>#test">
                                        </use>
                                    </svg>
                                </span> <span class="nav_text navTextJs"><?php echo Labels::getLabel('NAV_SALES_REPORTS', $siteLangId); ?></span>
                                <?php if (!$quickSearch) { ?>
                                    <i class="menu_arrow dropdown-toggle-custom-arrow"></i>
                                <?php } ?>
                            </a>
                            <div <?php if (!$quickSearch) { ?>id="salesReportNav" <?php } ?> class="panel-collapse <?php echo $collapseClass; ?> collapseJs" data-bs-parent="#reportsNav">
                                <ul class="nav nav-level">
                                    <?php if ($objPrivilege->canViewSalesReport(AdminAuthentication::getLoggedAdminId(), true)) { ?>
                                        <li class="nav_item navItemJs">
                                            <a class="nav_link navLinkJs dropdown-toggle-custom" data-selector='["SalesReport"]' href="<?php echo UrlHelper::generateUrl('SalesReport'); ?>">
                                                <span class="nav_text navTextJs"><?php echo Labels::getLabel('NAV_SALES_OVER_TIME', $siteLangId); ?></span>
                                            </a>
                                        </li>
                                    <?php } ?>
                                    <?php if ($objPrivilege->canViewCatalogReport(AdminAuthentication::getLoggedAdminId(), true)) { ?>
                                        <li class="nav_item navItemJs">
                                            <a class="nav_link navLinkJs dropdown-toggle-custom" data-selector='["CatalogReport"]' href="<?php echo UrlHelper::generateUrl('CatalogReport'); ?>">
                                                <span class="nav_text navTextJs"><?php echo Labels::getLabel('NAV_PRODUCT_CATALOG', $siteLangId); ?></span>
                                            </a>
                                        </li>
                                    <?php } ?>
                                    <?php if ($objPrivilege->canViewProductsReport(AdminAuthentication::getLoggedAdminId(), true)) { ?>
                                        <li class="nav_item navItemJs">
                                            <a class="nav_link navLinkJs dropdown-toggle-custom" data-selector='["ProductsReport"]' href="<?php echo UrlHelper::generateUrl('ProductsReport'); ?>">
                                                <span class="nav_text navTextJs"><?php echo Labels::getLabel('NAV_PRODUCT_VARIENTS', $siteLangId); ?></span>
                                            </a>
                                        </li>
                                    <?php } ?>
                                    <?php if ($objPrivilege->canViewShopsReport(AdminAuthentication::getLoggedAdminId(), true)) { ?>
                                        <li class="nav_item navItemJs">
                                            <a class="nav_link navLinkJs dropdown-toggle-custom" data-selector='["ShopsReport"]' href="<?php echo UrlHelper::generateUrl('ShopsReport'); ?>">
                                                <span class="nav_text navTextJs"><?php echo Labels::getLabel('NAV_SHOPS', $siteLangId); ?></span>
                                            </a>
                                        </li>
                                    <?php } ?>
                                    <?php if ($objPrivilege->canViewBuyersReport(AdminAuthentication::getLoggedAdminId(), true)) { ?>
                                        <li class="nav_item navItemJs">
                                            <a class="nav_link navLinkJs dropdown-toggle-custom" data-selector='["BuyersReport"]' href="<?php echo UrlHelper::generateUrl('BuyersReport'); ?>">
                                                <span class="nav_text navTextJs"><?php echo Labels::getLabel('NAV_CUSTOMERS', $siteLangId); ?></span>
                                            </a>
                                        </li>
                                    <?php } ?>
                                </ul>
                            </div>
                        </li>
                    <?php } ?>
                    <?php
                    if (
                        $objPrivilege->canViewBuyersReport(AdminAuthentication::getLoggedAdminId(), true) ||
                        $objPrivilege->canViewSellersReport(AdminAuthentication::getLoggedAdminId(), true) ||
                        $objPrivilege->canViewAffiliatesReport(AdminAuthentication::getLoggedAdminId(), true) ||
                        $objPrivilege->canViewAdvertisersReport(AdminAuthentication::getLoggedAdminId(), true)
                    ) {
                    ?>
                        <li class="nav_item hasNestedChildJs">
                            <a class="nav_link navLinkJs dropdown-toggle-custom collapsed" data-bs-toggle="collapse" href="#usersReportNav" aria-expanded="true">
                                <span class="nav_icon">
                                    <svg class="svg" width="24" height="24">
                                        <use xlink:href="<?php echo CONF_WEBROOT_URL; ?>images/retina/sprite-aside-menu.svg<?php echo AttachedFile::setTimeParam(RELEASE_DATE); ?>#test">
                                        </use>
                                    </svg>
                                </span>
                                <span class="nav_text navTextJs">
                                    <?php echo Labels::getLabel('NAV_USERS_REPORT', $siteLangId); ?>
                                </span>
                                <?php if (!$quickSearch) { ?>
                                    <i class="menu_arrow dropdown-toggle-custom-arrow"></i>
                                <?php } ?>
                            </a>
                            <div <?php if (!$quickSearch) { ?>id="usersReportNav" <?php } ?> class="panel-collapse <?php echo $collapseClass; ?> collapseJs" data-bs-parent="#reportsNav">
                                <ul class="nav nav-level">
                                    <?php if ($objPrivilege->canViewBuyersReport(AdminAuthentication::getLoggedAdminId(), true)) { ?>
                                        <li class="nav_item navItemJs">
                                            <a class="nav_link navLinkJs dropdown-toggle-custom" data-selector='["UsersReport/index/<?php echo User::USER_TYPE_BUYER; ?>"]' href="<?php echo UrlHelper::generateUrl('UsersReport', 'index', [User::USER_TYPE_BUYER]); ?>">
                                                <span class="nav_text navTextJs">
                                                    <?php echo Labels::getLabel('NAV_BUYERS', $siteLangId); ?>
                                                </span>
                                            </a>
                                        </li>
                                    <?php } ?>
                                    <?php if ($objPrivilege->canViewSellersReport(AdminAuthentication::getLoggedAdminId(), true)) { ?>
                                        <li class="nav_item navItemJs">
                                            <a class="nav_link navLinkJs dropdown-toggle-custom" data-selector='["UsersReport/index/<?php echo User::USER_TYPE_SELLER; ?>"]' href="<?php echo UrlHelper::generateUrl('UsersReport', 'index', [User::USER_TYPE_SELLER]); ?>">
                                                <span class="nav_text navTextJs">
                                                    <?php echo Labels::getLabel('NAV_SELLERS', $siteLangId); ?>
                                                </span>
                                            </a>
                                        </li>
                                    <?php } ?>
                                    <?php if ($objPrivilege->canViewAffiliatesReport(AdminAuthentication::getLoggedAdminId(), true)) { ?>
                                        <li class="nav_item navItemJs">
                                            <a class="nav_link navLinkJs dropdown-toggle-custom" data-selector='["AffiliatesReport"]' href="<?php echo UrlHelper::generateUrl('AffiliatesReport'); ?>">
                                                <span class="nav_text navTextJs">
                                                    <?php echo Labels::getLabel('NAV_AFFILIATES', $siteLangId); ?>
                                                </span>
                                            </a>
                                        </li>
                                    <?php } ?>
                                    <?php if ($objPrivilege->canViewAdvertisersReport(AdminAuthentication::getLoggedAdminId(), true)) { ?>
                                        <li class="nav_item navItemJs">
                                            <a class="nav_link navLinkJs dropdown-toggle-custom" data-selector='["AdvertisersReport"]' href="<?php echo UrlHelper::generateUrl('AdvertisersReport'); ?>">
                                                <span class="nav_text navTextJs">
                                                    <?php echo Labels::getLabel('NAV_ADVERTISERS', $siteLangId); ?>
                                                </span>
                                            </a>
                                        </li>
                                    <?php } ?>
                                </ul>
                            </div>
                        </li>
                    <?php } ?>
                    <?php if ($objPrivilege->canViewFinancialReport(AdminAuthentication::getLoggedAdminId(), true)) { ?>
                        <li class="nav_item hasNestedChildJs">
                            <a class="nav_link navLinkJs dropdown-toggle-custom collapsed" data-bs-toggle="collapse" href="#financialReportNav" aria-expanded="true">
                                <span class="nav_icon">
                                    <svg class="svg" width="24" height="24">
                                        <use xlink:href="<?php echo CONF_WEBROOT_URL; ?>images/retina/sprite-aside-menu.svg<?php echo AttachedFile::setTimeParam(RELEASE_DATE); ?>#test">
                                        </use>
                                    </svg>
                                </span>
                                <span class="nav_text navTextJs"><?php echo Labels::getLabel('NAV_FINANCIAL_REPORT', $siteLangId); ?></span>
                                <?php if (!$quickSearch) { ?>
                                    <i class="menu_arrow dropdown-toggle-custom-arrow"></i>
                                <?php } ?>
                            </a>
                            <div <?php if (!$quickSearch) { ?>id="financialReportNav" <?php } ?> class="panel-collapse <?php echo $collapseClass; ?> collapseJs" data-bs-parent="#reportsNav">
                                <ul class="nav nav-level">
                                    <li class="nav_item navItemJs">
                                        <a href="<?php echo UrlHelper::generateUrl('EarningsReport'); ?>" class="nav_link navLinkJs ">
                                            <span class="nav_text navTextJs"><?php echo Labels::getLabel('NAV_EARNINGS', $siteLangId); ?></span>
                                        </a>
                                    </li>
                                    <li class="nav_item navItemJs">
                                        <a href="<?php echo UrlHelper::generateUrl('ProductProfitReport'); ?>" class="nav_link navLinkJs ">
                                            <span class="nav_text navTextJs"><?php echo Labels::getLabel('NAV_PROFIT_BY_PRODUCTS', $siteLangId); ?></span>
                                        </a>
                                    </li>
                                    <li class="nav_item navItemJs">
                                        <a href="<?php echo UrlHelper::generateUrl('PreferredPaymentMethod'); ?>" class="nav_link navLinkJs ">
                                            <span class="nav_text navTextJs"><?php echo Labels::getLabel('NAV_PREFERRED_PAYMENT_METHOD', $siteLangId); ?></span>
                                        </a>
                                    </li>
                                    <li class="nav_item navItemJs">
                                        <a href="<?php echo UrlHelper::generateUrl('payoutReport'); ?>" class="nav_link navLinkJs ">
                                            <span class="nav_text navTextJs"><?php echo Labels::getLabel('NAV_PAYOUT', $siteLangId); ?></span>
                                        </a>
                                    </li>
                                    <li class="nav_item navItemJs">
                                        <a href="<?php echo UrlHelper::generateUrl('TransactionReport'); ?>" class="nav_link navLinkJs ">
                                            <span class="nav_text navTextJs"><?php echo Labels::getLabel('NAV_TRANSACTION_REPORT', $siteLangId); ?></span>
                                        </a>
                                    </li>
                                </ul>
                            </div>
                        </li>
                    <?php } ?>
                    <?php if ($objPrivilege->canViewSubscriptionReport(AdminAuthentication::getLoggedAdminId(), true)) { ?>
                        <li class="nav_item hasNestedChildJs">
                            <a class="nav_link navLinkJs dropdown-toggle-custom collapsed" data-bs-toggle="collapse" href="#subscriptionReportNav" aria-expanded="true">
                                <span class="nav_icon">
                                    <svg class="svg" width="24" height="24">
                                        <use xlink:href="<?php echo CONF_WEBROOT_URL; ?>images/retina/sprite-aside-menu.svg<?php echo AttachedFile::setTimeParam(RELEASE_DATE); ?>#test">
                                        </use>
                                    </svg>
                                </span> <span class="nav_text navTextJs"><?php echo Labels::getLabel('NAV_SUBSCRIPTION_REPORT', $siteLangId); ?></span>
                                <?php if (!$quickSearch) { ?>
                                    <i class="menu_arrow dropdown-toggle-custom-arrow"></i>
                                <?php } ?>
                            </a>
                            <div <?php if (!$quickSearch) { ?>id="subscriptionReportNav" <?php } ?> class="panel-collapse <?php echo $collapseClass; ?> collapseJs" data-bs-parent="#reportsNav">
                                <ul class="nav nav-level">
                                    <li class="nav_item navItemJs">
                                        <a href="<?php echo UrlHelper::generateUrl('SubscriptionPlanReport'); ?>" class="nav_link navLinkJs ">
                                            <span class="nav_text navTextJs"><?php echo Labels::getLabel('NAV_BY_PLAN', $siteLangId); ?></span>
                                        </a>
                                    </li>
                                    <li class="nav_item navItemJs">
                                        <a href="<?php echo UrlHelper::generateUrl('SubscriptionSellerReport'); ?>" class="nav_link navLinkJs ">
                                            <span class="nav_text navTextJs"><?php echo Labels::getLabel('NAV_BY_SELLER', $siteLangId); ?></span>
                                        </a>
                                    </li>
                                </ul>
                            </div>
                        </li>
                    <?php } ?>

                    <?php if ($objPrivilege->canViewDiscountCoupons(AdminAuthentication::getLoggedAdminId(), true)) { ?>
                        <li class="nav_item navItemJs">
                            <a class="nav_link navLinkJs dropdown-toggle-custom collapsed" data-selector='["DiscountCouponsReport"]' href="<?php echo UrlHelper::generateUrl('DiscountCouponsReport'); ?>">
                                <span class="nav_icon">
                                    <svg class="svg" width="24" height="24">
                                        <use xlink:href="<?php echo CONF_WEBROOT_URL; ?>images/retina/sprite-aside-menu.svg<?php echo AttachedFile::setTimeParam(RELEASE_DATE); ?>#test">
                                        </use>
                                    </svg>
                                </span>
                                <span class="nav_text navTextJs">
                                    <?php echo Labels::getLabel('NAV_DISCOUNT_COUPONS', $siteLangId); ?>
                                </span>
                            </a>
                        </li>
                    <?php } ?>
                </ul>
            </div>
        </li>
    <?php } ?>

    <?php
    if (
        $objPrivilege->canViewImportExport(AdminAuthentication::getLoggedAdminId(), true)
    ) {
    ?>
        <li class="menu-item dropdownJs">
            <button class="menu-section dropdown-toggle-custom menuLinkJs collapsed" type="button" <?php if (!$quickSearch) { ?>data-bs-toggle="collapse" data-bs-target="#NAV_IMPORT_EXPORT" <?php } ?> aria-expanded="true" aria-controls="collapseOne">
                <span class="menu-icon">
                    <svg class="svg" width="24" height="24">
                        <use xlink:href="<?php echo CONF_WEBROOT_URL; ?>images/retina/sprite-aside-menu.svg<?php echo AttachedFile::setTimeParam(RELEASE_DATE); ?>#icon-import-export">
                        </use>
                    </svg>
                </span>
                <span class="menu-title menuTitleJs"><?php echo Labels::getLabel('NAV_IMPORT_EXPORT', $siteLangId); ?></span>
                <?php if (!$quickSearch) { ?>
                    <i class="menu_arrow dropdown-toggle-custom-arrow"></i>
                <?php } ?>
            </button>
            <div class="sidebar-dropdown-menu <?php echo $collapseClass; ?>" <?php if (!$quickSearch) { ?>id="NAV_IMPORT_EXPORT" <?php } ?> aria-labelledby="" data-bs-parent="#sidebarNavLinks">
                <ul class="nav nav-level">
                    <li class="nav_item navItemJs">
                        <a class="nav_link navLinkJs dropdown-toggle-custom" data-selector='["ImportExport"]' href="<?php echo UrlHelper::generateUrl('ImportExport'); ?>">
                            <span class="nav_icon">
                                <svg class="svg" width="24" height="24">
                                    <use xlink:href="<?php echo CONF_WEBROOT_URL; ?>images/retina/sprite-aside-menu.svg<?php echo AttachedFile::setTimeParam(RELEASE_DATE); ?>#test">
                                    </use>
                                </svg>
                            </span> <span class="nav_text navTextJs"><?php echo Labels::getLabel('NAV_IMPORT_EXPORT', $siteLangId); ?></span>
                        </a>
                    </li>

                </ul>
            </div>
        </li>
    <?php } ?>

    <?php
    if (
        $objPrivilege->canViewShippingCompanyUsers(AdminAuthentication::getLoggedAdminId(), true) ||
        $objPrivilege->canViewShippingPackages(AdminAuthentication::getLoggedAdminId(), true) ||
        $objPrivilege->canViewShippingManagement(AdminAuthentication::getLoggedAdminId(), true) ||
        $objPrivilege->canViewPickupAddresses(AdminAuthentication::getLoggedAdminId(), true) ||
        $objPrivilege->canViewPickupAddresses(AdminAuthentication::getLoggedAdminId(), true) ||
        $objPrivilege->canViewTrackingRelationCode(AdminAuthentication::getLoggedAdminId(), true)
    ) {
    ?>
        <li class="menu-item dropdownJs">
            <button class="menu-section dropdown-toggle-custom menuLinkJs collapsed" type="button" <?php if (!$quickSearch) { ?>data-bs-toggle="collapse" data-bs-target="#NAV_SHIPPING" <?php } ?> aria-expanded="true" aria-controls="collapseOne">
                <span class="menu-icon">
                    <svg class="svg" width="24" height="24">
                        <use xlink:href="<?php echo CONF_WEBROOT_URL; ?>images/retina/sprite-aside-menu.svg<?php echo AttachedFile::setTimeParam(RELEASE_DATE); ?>#icon-shipping-pickup">
                        </use>
                    </svg>
                </span>
                <span class="menu-title menuTitleJs"><?php echo Labels::getLabel('NAV_SHIPPING/PICKUP', $siteLangId); ?></span>
                <?php if (!$quickSearch) { ?>
                    <i class="menu_arrow dropdown-toggle-custom-arrow"></i>
                <?php } ?>
            </button>
            <div class="sidebar-dropdown-menu <?php echo $collapseClass; ?>" <?php if (!$quickSearch) { ?>id="NAV_SHIPPING" <?php } ?> aria-labelledby="" data-bs-parent="#sidebarNavLinks">
                <ul class="nav nav-level">
                    <?php if ($objPrivilege->canViewShippingCompanyUsers(AdminAuthentication::getLoggedAdminId(), true)) { ?>
                        <li class="nav_item navItemJs">
                            <a class="nav_link navLinkJs dropdown-toggle-custom" data-selector='["ShippingCompanyUsers"]' href="<?php echo UrlHelper::generateUrl('ShippingCompanyUsers'); ?>">
                                <span class="nav_icon">
                                    <svg class="svg" width="24" height="24">
                                        <use xlink:href="<?php echo CONF_WEBROOT_URL; ?>images/retina/sprite-aside-menu.svg<?php echo AttachedFile::setTimeParam(RELEASE_DATE); ?>#test">
                                        </use>
                                    </svg>
                                </span> <span class="nav_text navTextJs"><?php echo Labels::getLabel('NAV_SHIPPING_COMPANY_USERS', $siteLangId); ?></span>
                            </a>
                        </li>
                    <?php } ?>

                    <?php if ($objPrivilege->canViewShippingPackages(AdminAuthentication::getLoggedAdminId(), true) && FatApp::getConfig("CONF_PRODUCT_DIMENSIONS_ENABLE", FatUtility::VAR_INT, 1)) { ?>
                        <li class="nav_item navItemJs">
                            <a class="nav_link navLinkJs" data-selector='["ShippingPackages"]' href="<?php echo UrlHelper::generateUrl('shippingPackages'); ?>">
                                <span class="nav_icon">
                                    <svg class="svg" width="24" height="24">
                                        <use xlink:href="<?php echo CONF_WEBROOT_URL; ?>images/retina/sprite-aside-menu.svg<?php echo AttachedFile::setTimeParam(RELEASE_DATE); ?>#test">
                                        </use>
                                    </svg>
                                </span>
                                <span class="nav_text navTextJs"><?php echo Labels::getLabel('NAV_SHIPPING_PACKAGES', $siteLangId); ?></span>
                            </a>
                        </li>
                    <?php } ?>

                    <?php if ($objPrivilege->canViewShippingManagement(AdminAuthentication::getLoggedAdminId(), true)) { ?>
                        <li class="nav_item navItemJs">
                            <a class="nav_link navLinkJs" data-selector='["ShippingProfile"]' href="<?php echo UrlHelper::generateUrl('shippingProfile'); ?>">
                                <span class="nav_icon">
                                    <svg class="svg" width="24" height="24">
                                        <use xlink:href="<?php echo CONF_WEBROOT_URL; ?>images/retina/sprite-aside-menu.svg<?php echo AttachedFile::setTimeParam(RELEASE_DATE); ?>#test">
                                        </use>
                                    </svg>
                                </span>
                                <span class="nav_text navTextJs"><?php echo Labels::getLabel('NAV_SHIPPING_PROFILE', $siteLangId); ?></span>
                            </a>
                        </li>
                    <?php } ?>

                    <?php if ($objPrivilege->canViewPickupAddresses(AdminAuthentication::getLoggedAdminId(), true)) { ?>
                        <li class="nav_item navItemJs">
                            <a class="nav_link navLinkJs dropdown-toggle-custom" data-selector='["PickupAddresses"]' href="<?php echo UrlHelper::generateUrl('PickupAddresses'); ?>">
                                <span class="nav_icon">
                                    <svg class="svg" width="24" height="24">
                                        <use xlink:href="<?php echo CONF_WEBROOT_URL; ?>images/retina/sprite-aside-menu.svg<?php echo AttachedFile::setTimeParam(RELEASE_DATE); ?>#test">
                                        </use>
                                    </svg>
                                </span> <span class="nav_text navTextJs"><?php echo Labels::getLabel('NAV_PICKUP_ADDRESSES', $siteLangId); ?></span>
                            </a>
                        </li>
                    <?php } ?>

                    <?php if ($objPrivilege->canViewShippedProducts(AdminAuthentication::getLoggedAdminId(), true)) { ?>
                        <li class="nav_item navItemJs">
                            <a class="nav_link navLinkJs dropdown-toggle-custom" data-selector='["ShippedProducts"]' href="<?php echo UrlHelper::generateUrl('ShippedProducts'); ?>">
                                <span class="nav_icon">
                                    <svg class="svg" width="24" height="24">
                                        <use xlink:href="<?php echo CONF_WEBROOT_URL; ?>images/retina/sprite-aside-menu.svg<?php echo AttachedFile::setTimeParam(RELEASE_DATE); ?>#test">
                                        </use>
                                    </svg>
                                </span> <span class="nav_text navTextJs"><?php echo Labels::getLabel('NAV_SHIPPED_PRODUCTS', $siteLangId); ?></span>
                            </a>
                        </li>
                    <?php } ?>

                    <?php if ($objPrivilege->canViewTrackingRelationCode(AdminAuthentication::getLoggedAdminId(), true)) { ?>
                        <li class="nav_item navItemJs">
                            <a class="nav_link navLinkJs dropdown-toggle-custom" data-selector='["TrackingCodeRelation"]' href="<?php echo UrlHelper::generateUrl('TrackingCodeRelation'); ?>">
                                <span class="nav_icon">
                                    <svg class="svg" width="24" height="24">
                                        <use xlink:href="<?php echo CONF_WEBROOT_URL; ?>images/retina/sprite-aside-menu.svg<?php echo AttachedFile::setTimeParam(RELEASE_DATE); ?>#test">
                                        </use>
                                    </svg>
                                </span> <span class="nav_text navTextJs"><?php echo Labels::getLabel('NAV_TRACKING_CODE_RELATION', $siteLangId); ?></span>
                            </a>
                        </li>
                    <?php } ?>
                </ul>
            </div>
        </li>
    <?php } ?>

    <?php
    if (
        $objPrivilege->canViewSitemap(AdminAuthentication::getLoggedAdminId(), true) ||
        $objPrivilege->canViewUrlRewrite(AdminAuthentication::getLoggedAdminId(), true) ||
        $objPrivilege->canViewMetaTags(AdminAuthentication::getLoggedAdminId(), true)
    ) {
    ?>
        <li class="menu-item dropdownJs">
            <button class="menu-section dropdown-toggle-custom menuLinkJs collapsed" type="button" <?php if (!$quickSearch) { ?>data-bs-toggle="collapse" data-bs-target="#NAV_SEO" <?php } ?> aria-expanded="true" aria-controls="collapseOne">
                <span class="menu-icon">
                    <svg class="svg" width="24" height="24">
                        <use xlink:href="<?php echo CONF_WEBROOT_URL; ?>images/retina/sprite-aside-menu.svg<?php echo AttachedFile::setTimeParam(RELEASE_DATE); ?>#icon-SEO">
                        </use>
                    </svg>
                </span>
                <span class="menu-title menuTitleJs"><?php echo Labels::getLabel('NAV_SEO', $siteLangId); ?></span>
                <?php if (!$quickSearch) { ?>
                    <i class="menu_arrow dropdown-toggle-custom-arrow"></i>
                <?php } ?>
            </button>
            <div class="sidebar-dropdown-menu <?php echo $collapseClass; ?>" <?php if (!$quickSearch) { ?>id="NAV_SEO" <?php } ?> aria-labelledby="" data-bs-parent="#sidebarNavLinks">
                <ul class="nav nav-level">
                    <?php if ($objPrivilege->canViewUrlRewrite(AdminAuthentication::getLoggedAdminId(), true)) { ?>
                        <li class="nav_item navItemJs">
                            <a class="nav_link navLinkJs dropdown-toggle-custom" data-selector='["UrlRewriting"]' href="<?php echo UrlHelper::generateUrl('UrlRewriting'); ?>">
                                <span class="nav_icon">
                                    <svg class="svg" width="24" height="24">
                                        <use xlink:href="<?php echo CONF_WEBROOT_URL; ?>images/retina/sprite-aside-menu.svg<?php echo AttachedFile::setTimeParam(RELEASE_DATE); ?>#test">
                                        </use>
                                    </svg>
                                </span> <span class="nav_text navTextJs"><?php echo Labels::getLabel('NAV_URL_REWRITING', $siteLangId); ?></span>
                            </a>
                        </li>
                    <?php } ?>

                    <?php if ($objPrivilege->canViewImageAttributes(AdminAuthentication::getLoggedAdminId(), true)) { ?>
                        <li class="nav_item navItemJs">
                            <a class="nav_link navLinkJs dropdown-toggle-custom" data-selector='["ImageAttributes"]' href="<?php echo UrlHelper::generateUrl('ImageAttributes'); ?>">
                                <span class="nav_icon">
                                    <svg class="svg" width="24" height="24">
                                        <use xlink:href="<?php echo CONF_WEBROOT_URL; ?>images/retina/sprite-aside-menu.svg<?php echo AttachedFile::setTimeParam(RELEASE_DATE); ?>#test">
                                        </use>
                                    </svg>
                                </span>
                                <span class="nav_text navTextJs">
                                    <?php echo Labels::getLabel('NAV_IMAGE_ATTRIBUTES', $siteLangId); ?></span>
                            </a>
                        </li>
                    <?php } ?>

                    <?php if ($objPrivilege->canViewUrlRewrite(AdminAuthentication::getLoggedAdminId(), true)) { ?>
                        <li class="nav_item navItemJs">
                            <a class="nav_link navLinkJs dropdown-toggle-custom" data-selector='["Sitemap"]' href="<?php echo UrlHelper::generateUrl('sitemap', 'generate'); ?>">
                                <span class="nav_icon">
                                    <svg class="svg" width="24" height="24">
                                        <use xlink:href="<?php echo CONF_WEBROOT_URL; ?>images/retina/sprite-aside-menu.svg<?php echo AttachedFile::setTimeParam(RELEASE_DATE); ?>#test">
                                        </use>
                                    </svg>
                                </span><span class="nav_text navTextJs"><?php echo Labels::getLabel('NAV_GENERATE_SITEMAP', $siteLangId); ?></span>
                            </a>
                        </li>
                        <li class="nav_item navItemJs">
                            <a class="nav_link navLinkJs dropdown-toggle-custom" target="_blank" href="<?php echo UrlHelper::generateFullUrl('custom', 'sitemap', array(), CONF_WEBROOT_FRONT_URL); ?>">
                                <span class="nav_icon">
                                    <svg class="svg" width="24" height="24">
                                        <use xlink:href="<?php echo CONF_WEBROOT_URL; ?>images/retina/sprite-aside-menu.svg<?php echo AttachedFile::setTimeParam(RELEASE_DATE); ?>#test">
                                        </use>
                                    </svg>
                                </span> <span class="nav_text navTextJs"><?php echo Labels::getLabel('NAV_VIEW_HTML', $siteLangId); ?></span>
                            </a>
                        </li>
                        <li class="nav_item navItemJs">
                            <a class="nav_link navLinkJs dropdown-toggle-custom" target="_blank" href="<?php echo UrlHelper::generateFullUrl('', '', array(), CONF_WEBROOT_FRONT_URL) . 'sitemap.xml'; ?>">
                                <span class="nav_icon">
                                    <svg class="svg" width="24" height="24">
                                        <use xlink:href="<?php echo CONF_WEBROOT_URL; ?>images/retina/sprite-aside-menu.svg<?php echo AttachedFile::setTimeParam(RELEASE_DATE); ?>#test">
                                        </use>
                                    </svg>
                                </span> <span class="nav_text navTextJs"><?php echo Labels::getLabel('NAV_VIEW_XML', $siteLangId); ?></span>
                            </a>
                        </li>
                    <?php } ?>

                    <?php if ($objPrivilege->canViewMetaTags(AdminAuthentication::getLoggedAdminId(), true)) { ?>
                        <li class="nav_item navItemJs">
                            <a class="nav_link navLinkJs dropdown-toggle-custom" data-selector='["MetaTags"]' href="<?php echo UrlHelper::generateUrl('MetaTags'); ?>">
                                <span class="nav_icon">
                                    <svg class="svg" width="24" height="24">
                                        <use xlink:href="<?php echo CONF_WEBROOT_URL; ?>images/retina/sprite-aside-menu.svg<?php echo AttachedFile::setTimeParam(RELEASE_DATE); ?>#test">
                                        </use>
                                    </svg>
                                </span> <span class="nav_text navTextJs"><?php echo Labels::getLabel('NAV_META_TAGS_MANAGEMENT', $siteLangId); ?></span>
                            </a>
                        </li>
                    <?php } ?>
                </ul>
            </div>
        </li>
    <?php } ?>

    <?php if ($quickSearch) { ?>
        <li class="noResultsFoundJs" style="display: none;">
            <?php $this->includeTemplate('_partial/no-record-found.php', [], false) ?>
        </li>
    <?php } ?>
</ul>