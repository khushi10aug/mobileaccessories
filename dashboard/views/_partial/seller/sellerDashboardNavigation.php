<?php
$quickSearch = $quickSearch ?? false;
$collapseClass = ($quickSearch ? 'collapsed' : 'collapse');
$quickSearchUlClass = ($quickSearch ? 'quickMenujs' : '');
$controller = strtolower($controller);
$action = strtolower($action);
$plugin = new Plugin();
?>
<ul class="dashboard-menu <?php echo $quickSearchUlClass; ?>">
    <?php if (
        $userPrivilege->canViewShop(UserAuthentication::getLoggedUserId(), true) ||
        $userPrivilege->canViewProducts(UserAuthentication::getLoggedUserId(), true) ||
        $userPrivilege->canViewProductOptions(UserAuthentication::getLoggedUserId(), true) ||
        $userPrivilege->canViewTaxCategory(UserAuthentication::getLoggedUserId(), true) ||
        $userPrivilege->canViewSellerRequests(UserAuthentication::getLoggedUserId(), true)
    ) { ?>
        <li class="dashboard-menu-item dropdownJs">
            <button class="dashboard-menu-btn menuLinkJs dropdown-toggle-custom collapsed" type="button" <?php if (false === $quickSearch) { ?>data-bs-toggle="collapse" data-bs-target="#nav-shop" aria-expanded="true" aria-controls="collapseOne" <?php } ?> title="">
                <span class="dashboard-menu-icon">
                    <svg class="svg" width="18" height="18">
                        <use xlink:href="<?php echo CONF_WEBROOT_URL; ?>images/retina/sprite-sidebar.svg<?php echo AttachedFile::setTimeParam(RELEASE_DATE); ?>#manage-shop">
                        </use>
                    </svg>
                </span>
                <span class="dashboard-menu-head menuTitleJs">
                    <?php echo Labels::getLabel('LBL_Shop', $siteLangId); ?>
                </span>
                <?php if (false === $quickSearch) { ?>
                    <i class="dashboard-menu-arrow dropdown-toggle-custom-arrow"></i>
                <?php } ?>
            </button>
            <ul class="menu-sub menu-sub-accordion <?php echo $collapseClass; ?>" id="nav-shop" aria-labelledby="" data-parent="#dashboard-menu">
                <?php if ($userPrivilege->canViewShop(UserAuthentication::getLoggedUserId(), true)) { ?>
                    <li class="menu-sub-item navItemJs">
                        <a class="menu-sub-link navLinkJs <?php echo (false === $quickSearch && $controller == 'seller' && $action == 'shop') ? 'active' : ''; ?>" title="<?php echo Labels::getLabel('LBL_Manage_Shop', $siteLangId); ?>" href="<?php echo UrlHelper::generateUrl('Seller', 'shop'); ?>">
                            <span class="menu-sub-title navTextJs">
                                <?php echo Labels::getLabel('LBL_Manage_Shop', $siteLangId); ?>
                            </span>
                        </a>
                    </li>
                <?php } ?>
                <?php if ($userPrivilege->canViewProducts(UserAuthentication::getLoggedUserId(), true)) {
                    $prodUrl = UrlHelper::generateUrl('seller', 'products');
                    if (FatApp::getConfig('CONF_WITHOUT_PROD_VARIANTS', FatUtility::VAR_INT, 0)) {
                        $prodUrl = UrlHelper::generateUrl('seller', 'catalog');
                    }
                ?>
                    <li class="menu-sub-item navItemJs">
                        <a class="menu-sub-link navLinkJs <?php echo (false === $quickSearch && $controller == 'seller' && ($action == 'catalog' || $action == 'products')) ||  $controller == 'products' ? 'active' : ''; ?>" title="<?php echo Labels::getLabel('LBL_Products', $siteLangId); ?>" href="<?php echo $prodUrl; ?>">
                            <span class="menu-sub-title navTextJs">
                                <?php echo Labels::getLabel('LBL_Shop_Inventory', $siteLangId); ?></span>
                        </a>
                    </li>
                <?php } ?>
                <?php if (User::canAddCustomProduct() && $userPrivilege->canViewProducts(UserAuthentication::getLoggedUserId(), true)) { ?>
                    <li class="menu-sub-item navItemJs">
                        <a class="menu-sub-link navLinkJs <?php echo (false === $quickSearch && $controller == 'seller' && $action == 'producttags') ? 'active' : ''; ?>" title="<?php echo Labels::getLabel('LBL_Product_Tags', $siteLangId); ?>" href="<?php echo UrlHelper::generateUrl('Seller', 'productTags'); ?>">
                            <span class="menu-sub-title navTextJs">
                                <?php echo Labels::getLabel('LBL_Product_Tags', $siteLangId); ?>
                            </span>
                        </a>
                    </li>
                <?php } ?>
                <?php $canAddCustomProd = FatApp::getConfig('CONF_ENABLED_SELLER_CUSTOM_PRODUCT', FatUtility::VAR_INT, 0);
                if (0 < $canAddCustomProd && !FatApp::getConfig('CONF_WITHOUT_PROD_VARIANTS', FatUtility::VAR_INT, 0) && $userPrivilege->canViewProductOptions(UserAuthentication::getLoggedUserId(), true)) { ?>
                    <li class="menu-sub-item navItemJs">
                        <a class="menu-sub-link navLinkJs <?php echo (false === $quickSearch && $controller == 'seller' && $action == 'options') ? 'active' : ''; ?>" title="<?php echo Labels::getLabel('LBL_Product_Options', $siteLangId); ?>" href="<?php echo UrlHelper::generateUrl('Seller', 'options'); ?>">
                            <span class="menu-sub-title navTextJs">
                                <?php echo Labels::getLabel('LBL_Product_Options', $siteLangId); ?>
                            </span>
                        </a>
                    </li>
                <?php } ?>
                <?php if (FatApp::getConfig('CONF_ENABLED_SELLER_CUSTOM_PRODUCT', FatUtility::VAR_INT, 0) && $userPrivilege->canViewTaxCategory(UserAuthentication::getLoggedUserId(), true)) { ?>
                    <li class="menu-sub-item navItemJs">
                        <a class="menu-sub-link navLinkJs <?php echo (false === $quickSearch && $controller == 'seller' && ($action == 'taxcategories' || $action == 'taxrules')) ? 'active' : ''; ?>" title="<?php echo Labels::getLabel('LBL_Tax_Categories', $siteLangId); ?>" href="<?php echo UrlHelper::generateUrl('Seller', 'taxCategories'); ?>">
                            <span class="menu-sub-title navTextJs"><?php echo Labels::getLabel('LBL_Tax_Categories', $siteLangId); ?>
                            </span>
                        </a>
                    </li>
                <?php } ?>
                <?php if ($userPrivilege->canViewSellerRequests(UserAuthentication::getLoggedUserId(), true)) { ?>
                    <li class="menu-sub-item navItemJs">
                        <a class="menu-sub-link navLinkJs <?php echo (false === $quickSearch && $controller == 'sellerrequests' && $action == 'index') ? 'active' : ''; ?>" title="<?php echo Labels::getLabel('LBL_Requests', $siteLangId); ?>" href="<?php echo UrlHelper::generateUrl('SellerRequests'); ?>">
                            <span class="menu-sub-title navTextJs">
                                <?php echo Labels::getLabel('LBL_Requests', $siteLangId); ?>
                            </span>
                        </a>
                    </li>
                <?php } ?>
            </ul>
        </li>
    <?php }

    $shippingObj = new Shipping($siteLangId);
    $shippingProfileEnabled = $userPrivilege->canViewShippingProfiles(UserAuthentication::getLoggedUserId(), true) && !FatApp::getConfig('CONF_SHIPPED_BY_ADMIN_ONLY', FatUtility::VAR_INT, 0) && (!$shippingObj->getShippingApiObj($userParentId) || Shop::getAttributesByUserId($userParentId, 'shop_use_manual_shipping_rates'));
    $shippingPackagesEnabled = $userPrivilege->canViewShippingPackages(UserAuthentication::getLoggedUserId(), true) && FatApp::getConfig("CONF_PRODUCT_DIMENSIONS_ENABLE", FatUtility::VAR_INT, 1) && FatApp::getConfig('CONF_ENABLED_SELLER_CUSTOM_PRODUCT', FatUtility::VAR_INT, 0);

    if (
        $shippingProfileEnabled ||
        $shippingPackagesEnabled
    ) {
    ?>
        <li class="dashboard-menu-item dropdownJs">
            <button class="dashboard-menu-btn menuLinkJs dropdown-toggle-custom collapsed" type="button" <?php if (false === $quickSearch) { ?>data-bs-toggle="collapse" data-bs-target="#nav-shipping" aria-expanded="true" aria-controls="collapseOne" <?php } ?> title="">
                <span class="dashboard-menu-icon">
                    <svg class="svg" width="18" height="18">
                        <use xlink:href="<?php echo CONF_WEBROOT_URL; ?>images/retina/sprite-sidebar.svg<?php echo AttachedFile::setTimeParam(RELEASE_DATE); ?>#shipping-profile">
                        </use>
                    </svg>
                </span>
                <span class="dashboard-menu-head menuTitleJs">
                    <?php echo Labels::getLabel('LBL_Shipping', $siteLangId); ?>
                </span>
                <?php if (false === $quickSearch) { ?>
                    <i class="dashboard-menu-arrow dropdown-toggle-custom-arrow"></i>
                <?php } ?>
            </button>
            <ul class="menu-sub menu-sub-accordion <?php echo $collapseClass; ?>" id="nav-shipping" aria-labelledby="" data-parent="#dashboard-menu">
                <?php
                if ($shippingProfileEnabled) {
                ?>
                    <li class="menu-sub-item navItemJs">
                        <a class="menu-sub-link navLinkJs <?php echo (false === $quickSearch && $controller == 'shippingprofile') ? 'active' : ''; ?>" title="<?php echo Labels::getLabel('LBL_Shipping_Profiles', $siteLangId); ?>" href="<?php echo UrlHelper::generateUrl('shippingProfile'); ?>">
                            <span class="menu-sub-title navTextJs"><?php echo Labels::getLabel('LBL_Shipping_Profiles', $siteLangId); ?>
                            </span>
                        </a>
                    </li>
                <?php }
                if ($shippingPackagesEnabled) { ?>
                    <li class="menu-sub-item navItemJs">
                        <a class="menu-sub-link navLinkJs <?php echo (false === $quickSearch && $controller == 'shippingpackages') ? 'active' : ''; ?>" title="<?php echo Labels::getLabel('LBL_Shipping_Packages', $siteLangId); ?>" href="<?php echo UrlHelper::generateUrl('shippingPackages'); ?>">
                            <span class="menu-sub-title navTextJs">
                                <?php echo Labels::getLabel('LBL_Shipping_Packages', $siteLangId); ?></span>
                        </a>
                    </li>
                <?php } ?>
            </ul>
        </li>
    <?php }
    if (
        $userPrivilege->canViewSales(UserAuthentication::getLoggedUserId(), true) ||
        $userPrivilege->canViewCancellationRequests(UserAuthentication::getLoggedUserId(), true) ||
        $userPrivilege->canViewReturnRequests(UserAuthentication::getLoggedUserId(), true) ||
        $userPrivilege->canViewRequestForQuote(UserAuthentication::getLoggedUserId(), true)

    ) { ?>
        <li class="dashboard-menu-item dropdownJs">
            <button class="dashboard-menu-btn menuLinkJs dropdown-toggle-custom collapsed" type="button" <?php if (false === $quickSearch) { ?>data-bs-toggle="collapse" data-bs-target="#nav-sales" aria-expanded="true" aria-controls="collapseOne" <?php } ?> title="">
                <span class="dashboard-menu-icon">
                    <svg class="svg" width="18" height="18">
                        <use xlink:href="<?php echo CONF_WEBROOT_URL; ?>images/retina/sprite-sidebar.svg<?php echo AttachedFile::setTimeParam(RELEASE_DATE); ?>#order-sales">
                        </use>
                    </svg>
                </span>
                <span class="dashboard-menu-head menuTitleJs">
                    <?php echo Labels::getLabel('LBL_Sales', $siteLangId); ?>
                </span>
                <?php if (false === $quickSearch) { ?>
                    <i class="dashboard-menu-arrow dropdown-toggle-custom-arrow"></i>
                <?php } ?>
            </button>
            <ul class="menu-sub menu-sub-accordion <?php echo $collapseClass; ?>" id="nav-sales" aria-labelledby="" data-parent="#dashboard-menu">
                <?php if ($userPrivilege->canViewSales(UserAuthentication::getLoggedUserId(), true)) { ?>
                    <li class="menu-sub-item navItemJs">
                        <a class="menu-sub-link navLinkJs <?php echo (false === $quickSearch && $controller == 'seller' && $action == 'sales') ? 'active' : ''; ?>" title="<?php echo Labels::getLabel('LBL_Orders', $siteLangId); ?>" href="<?php echo UrlHelper::generateUrl('Seller', 'Sales'); ?>">
                            <span class="menu-sub-title navTextJs"><?php echo Labels::getLabel('LBL_Orders', $siteLangId); ?></span></a>

                    </li>
                <?php } ?>
                <?php if ($userPrivilege->canViewCancellationRequests(UserAuthentication::getLoggedUserId(), true)) { ?>
                    <li class="menu-sub-item navItemJs">
                        <a class="menu-sub-link navLinkJs <?php echo (false === $quickSearch && $controller == 'seller' && $action == 'ordercancellationrequests') ? 'active' : '' ?>" title="<?php echo Labels::getLabel('LBL_Order_Cancellation_Requests', $siteLangId); ?>" href="<?php echo UrlHelper::generateUrl('Seller', 'orderCancellationRequests'); ?>">
                            <span class="menu-sub-title navTextJs"><?php echo Labels::getLabel("LBL_Order_Cancellation_Requests", $siteLangId); ?>
                            </span>
                        </a>

                    </li>
                <?php } ?>
                <?php if ($userPrivilege->canViewReturnRequests(UserAuthentication::getLoggedUserId(), true)) { ?>
                    <li class="menu-sub-item navItemJs">
                        <a class="menu-sub-link navLinkJs <?php echo (false === $quickSearch && $controller == 'seller' && ($action == 'orderreturnrequests' || $action == 'vieworderreturnrequest')) ? 'active' : ''; ?>" title="<?php echo Labels::getLabel('LBL_Order_Return_Requests', $siteLangId); ?>" href="<?php echo UrlHelper::generateUrl('Seller', 'orderReturnRequests'); ?>">
                            <span class="menu-sub-title navTextJs"><?php echo Labels::getLabel("LBL_Order_Return_Requests", $siteLangId); ?></span></a>
                    </li>
                <?php } ?>

                <?php if ($userPrivilege->canViewRequestForQuote(UserAuthentication::getLoggedUserId(), true) && 0 < FatApp::getConfig('CONF_RFQ_MODULE', FatUtility::VAR_INT, 0)) { ?>
                    <li class="menu-sub-item navItemJs">
                        <a class="menu-sub-link navLinkJs <?php echo (false === $quickSearch && ($controller == 'sellerrequestforquotes' || $controller == 'sellerrfqoffers') && ($action == 'index' || $action == 'listing') && $action != 'global') ? 'active' : ''; ?>" title="<?php echo Labels::getLabel('NAV_REQUEST_FOR_QUOTES', $siteLangId); ?>" href="<?php echo UrlHelper::generateUrl('SellerRequestForQuotes'); ?>">
                            <span class="menu-sub-title navTextJs"><?php echo Labels::getLabel("NAV_REQUEST_FOR_QUOTES", $siteLangId); ?></span>
                        </a>
                    </li>
                    <?php if (0 < FatApp::getConfig('CONF_GLOBAL_RFQ_MODULE', FatUtility::VAR_INT, 0)) { ?>
                        <li class="menu-sub-item navItemJs">
                            <a class="menu-sub-link navLinkJs <?php echo (false === $quickSearch && (($controller == 'sellerrequestforquotes' || $controller == 'sellerrfqoffers') && ($action == 'global' || $action == 'globallisting'))) ? 'active' : ''; ?>" title="<?php echo Labels::getLabel('NAV_GLOBAL_REQUEST_FOR_QUOTES', $siteLangId); ?>" href="<?php echo UrlHelper::generateUrl('SellerRequestForQuotes', 'global'); ?>">
                                <span class="menu-sub-title navTextJs"><?php echo Labels::getLabel("NAV_GLOBAL_REQUEST_FOR_QUOTES", $siteLangId); ?></span>
                            </a>
                        </li>
                    <?php } ?>
                <?php } ?>
            </ul>
        </li>
    <?php } ?>

    <?php if (
        $userPrivilege->canViewSpecialPrice(UserAuthentication::getLoggedUserId(), true) ||
        $userPrivilege->canViewVolumeDiscount(UserAuthentication::getLoggedUserId(), true) ||
        $userPrivilege->canViewBuyTogetherProducts(UserAuthentication::getLoggedUserId(), true) ||
        $userPrivilege->canViewRelatedProducts(UserAuthentication::getLoggedUserId(), true) ||
        $userPrivilege->canViewAdvertisementFeed(UserAuthentication::getLoggedUserId(), true) ||
        $userPrivilege->canViewBadgeLinks(UserAuthentication::getLoggedUserId(), true)
    ) { ?>
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
                <?php if ($userPrivilege->canViewSpecialPrice(UserAuthentication::getLoggedUserId(), true)) { ?>
                    <li class="menu-sub-item navItemJs">
                        <a class="menu-sub-link navLinkJs <?php echo (false === $quickSearch && $controller == 'seller' && $action == 'specialprice') ? 'active' : ''; ?>" title="<?php echo Labels::getLabel('LBL_Special_Price', $siteLangId); ?>" href="<?php echo UrlHelper::generateUrl('Seller', 'specialPrice'); ?>">

                            <span class="menu-sub-title navTextJs"><?php echo Labels::getLabel('LBL_Special_Price', $siteLangId); ?></span>
                        </a>

                    </li>
                <?php } ?>
                <?php if ($userPrivilege->canViewVolumeDiscount(UserAuthentication::getLoggedUserId(), true)) { ?>
                    <li class="menu-sub-item navItemJs">
                        <a class="menu-sub-link navLinkJs <?php echo (false === $quickSearch && $controller == 'seller' && $action == 'volumediscount') ? 'active' : ''; ?>" title="<?php echo Labels::getLabel('LBL_Volume_Discount', $siteLangId); ?>" href="<?php echo UrlHelper::generateUrl('Seller', 'volumeDiscount'); ?>">
                            <span class="menu-sub-title navTextJs"><?php echo Labels::getLabel('LBL_Volume_Discount', $siteLangId); ?></span></a>

                    </li>
                <?php } ?>
                <?php if ($userPrivilege->canViewBuyTogetherProducts(UserAuthentication::getLoggedUserId(), true)) { ?>
                    <li class="menu-sub-item navItemJs">
                        <a class="menu-sub-link navLinkJs <?php echo (false === $quickSearch && $controller == 'seller' && $action == 'upsellproducts') ? 'active' : ''; ?>" title="<?php echo Labels::getLabel('LBL_Buy_Together_Products', $siteLangId); ?>" href="<?php echo UrlHelper::generateUrl('Seller', 'upsellProducts'); ?>">
                            <span class="menu-sub-title navTextJs"><?php echo Labels::getLabel('LBL_Buy_Together_Products', $siteLangId); ?></span></a>

                    </li>
                <?php } ?>
                <?php if ($userPrivilege->canViewRelatedProducts(UserAuthentication::getLoggedUserId(), true)) { ?>
                    <li class="menu-sub-item navItemJs">
                        <a class="menu-sub-link navLinkJs <?php echo (false === $quickSearch && $controller == 'seller' && $action == 'relatedproducts') ? 'active' : ''; ?>" title="<?php echo Labels::getLabel('LBL_Related_Products', $siteLangId); ?>" href="<?php echo UrlHelper::generateUrl('Seller', 'RelatedProducts'); ?>">
                            <span class="menu-sub-title navTextJs"><?php echo Labels::getLabel('LBL_Related_Products', $siteLangId); ?></span></a>

                    </li>
                <?php } ?>
                <?php
                $pluginData = $plugin->getDefaultPluginData(Plugin::TYPE_ADVERTISEMENT_FEED, null, $siteLangId);
                if ($userPrivilege->canViewAdvertisementFeed(UserAuthentication::getLoggedUserId(), true) && false !== $pluginData && !empty($pluginData) && 0 < $pluginData['plugin_active'] && $userPrivilege->canViewAdvertisementFeed(UserAuthentication::getLoggedUserId(), true)) { ?>
                    <li class="menu-sub-item navItemJs">
                        <a class="menu-sub-link navLinkJs <?php echo (false === $quickSearch && $controller == strtolower($pluginData['plugin_code'])) ? 'active' : ''; ?>" title="<?php echo $pluginData['plugin_name']; ?>" href="<?php echo UrlHelper::generateUrl($pluginData['plugin_code']); ?>">
                            <span class="menu-sub-title navTextJs"><?php echo $pluginData['plugin_name']; ?></span>
                        </a>

                    </li>
                <?php } ?>
                <?php if ($userPrivilege->canViewBadgeLinks(UserAuthentication::getLoggedUserId(), true)) { ?>
                    <li class="menu-sub-item navItemJs">
                        <a class="menu-sub-link navLinkJs <?php echo (false === $quickSearch && $controller == 'badges' && current($_GET) == 'badges/list/1') ? 'active' : ''; ?>" title="<?php echo Labels::getLabel('LBL_BADGES_LINKING', $siteLangId); ?>" href="<?php echo UrlHelper::generateUrl('Badges', 'list', [Badge::TYPE_BADGE]); ?>">
                            <span class="menu-sub-title navTextJs"><?php echo Labels::getLabel('LBL_BADGES', $siteLangId); ?></span>
                        </a>

                    </li>
                    <li class="menu-sub-item navItemJs">
                        <a class="menu-sub-link navLinkJs <?php echo (false === $quickSearch && $controller == 'badges' && current($_GET) == 'badges/list/2') ? 'active' : ''; ?>" title="<?php echo Labels::getLabel('LBL_RIBBONS_LINKING', $siteLangId); ?>" href="<?php echo UrlHelper::generateUrl('Badges', 'list', [Badge::TYPE_RIBBON]); ?>">
                            <span class="menu-sub-title navTextJs"><?php echo Labels::getLabel('LBL_RIBBONS', $siteLangId); ?></span>
                        </a>
                    </li>
                <?php } ?>
            </ul>
        </li>
    <?php } ?>

    <?php
    $marketPlaceChannels = (array) Plugin::getDataByType(Plugin::TYPE_MARKETPLACE_CHANNELS, $siteLangId);
    if ($userPrivilege->canViewMarketplaceChannel(UserAuthentication::getLoggedUserId(), true) && 0 < count($marketPlaceChannels)) { ?>
        <li class="dashboard-menu-item dropdownJs">
            <button class="dashboard-menu-btn menuLinkJs dropdown-toggle-custom collapsed" type="button" <?php if (false === $quickSearch) { ?>data-bs-toggle="collapse" data-bs-target="#nav-channel" aria-expanded="true" aria-controls="collapseOne" <?php } ?> title="">
                <span class="dashboard-menu-icon">
                    <svg class="svg" width="18" height="18">
                        <use xlink:href="<?php echo CONF_WEBROOT_URL; ?>images/retina/sprite-sidebar.svg<?php echo AttachedFile::setTimeParam(RELEASE_DATE); ?>#manage-shop">
                        </use>
                    </svg>
                </span>
                <span class="dashboard-menu-head menuTitleJs">
                    <?php echo Labels::getLabel('LBL_OMNI_CHANNEL_MANAGEMENT', $siteLangId); ?>
                </span>
                <?php if (false === $quickSearch) { ?>
                    <i class="dashboard-menu-arrow dropdown-toggle-custom-arrow"></i>
                <?php } ?>
            </button>
            <ul class="menu-sub menu-sub-accordion <?php echo $collapseClass; ?>" id="nav-channel" aria-labelledby="" data-parent="#dashboard-menu">
                <?php
                foreach ($marketPlaceChannels as $channel) {
                    $fileData = AttachedFile::getAttachment(AttachedFile::FILETYPE_PLUGIN_LOGO, $channel['plugin_id']);
                    $uploadedTime = '';
                    $aspectRatio = '';
                    if (!empty($fileData)) {
                        $uploadedTime = AttachedFile::setTimeParam($fileData['afile_updated_at']);
                        $aspectRatio = ($fileData['afile_aspect_ratio'] > 0 && isset($aspectRatioArr[$fileData['afile_aspect_ratio']])) ? $aspectRatioArr[$fileData['afile_aspect_ratio']] : '';
                    }
                ?>
                    <li class="menu-sub-item navItemJs">
                        <a class="menu-sub-link navLinkJs <?php echo (false === $quickSearch && $controller == strtolower($channel['plugin_code'])) ? 'active' : ''; ?>" title="<?php echo $channel['plugin_name']; ?>" href="<?php echo UrlHelper::generateUrl($channel['plugin_code']); ?>">
                            <span class="menu-sub-title navTextJs"><?php echo $channel['plugin_name']; ?></span>
                        </a>
                    </li>
                <?php  } ?>
            </ul>
        </li>
    <?php } ?>

    <?php if (
        $userPrivilege->canViewMetaTags(UserAuthentication::getLoggedUserId(), true) ||
        $userPrivilege->canViewUrlRewriting(UserAuthentication::getLoggedUserId(), true)
    ) { ?>
        <li class="dashboard-menu-item dropdownJs">
            <button class="dashboard-menu-btn menuLinkJs dropdown-toggle-custom collapsed" type="button" <?php if (false === $quickSearch) { ?>data-bs-toggle="collapse" data-bs-target="#nav-seo" aria-expanded="true" aria-controls="collapseOne" <?php } ?> title="">
                <span class="dashboard-menu-icon">
                    <svg class="svg" width="18" height="18">
                        <use xlink:href="<?php echo CONF_WEBROOT_URL; ?>images/retina/sprite-sidebar.svg<?php echo AttachedFile::setTimeParam(RELEASE_DATE); ?>#URL-rewriting">
                        </use>
                    </svg>
                </span>
                <span class="dashboard-menu-head menuTitleJs">
                    <?php echo Labels::getLabel('LBL_SEO', $siteLangId); ?>
                </span>
                <?php if (false === $quickSearch) { ?>
                    <i class="dashboard-menu-arrow dropdown-toggle-custom-arrow"></i>
                <?php } ?>
            </button>
            <ul class="menu-sub menu-sub-accordion <?php echo $collapseClass; ?>" id="nav-seo" aria-labelledby="" data-parent="#dashboard-menu">
                <?php if ($userPrivilege->canViewMetaTags(UserAuthentication::getLoggedUserId(), true)) { ?>
                    <li class="menu-sub-item navItemJs">
                        <a class="menu-sub-link navLinkJs <?php echo (false === $quickSearch && $controller == 'seller' && $action == 'productseo') ? 'active' : ''; ?>" title="<?php echo Labels::getLabel('LBL_Meta_Tags', $siteLangId); ?>" href="<?php echo UrlHelper::generateUrl('Seller', 'productSeo'); ?>">
                            <span class="menu-sub-title navTextJs"><?php echo Labels::getLabel('LBL_Meta_Tags', $siteLangId); ?></span>
                        </a>

                    </li>
                <?php } ?>
                <?php if ($userPrivilege->canViewUrlRewriting(UserAuthentication::getLoggedUserId(), true)) { ?>
                    <li class="menu-sub-item navItemJs">
                        <a class="menu-sub-link navLinkJs <?php echo (false === $quickSearch && $controller == 'seller' && $action == 'producturlrewriting') ? 'active' : ''; ?>" title="<?php echo Labels::getLabel('LBL_URL_Rewriting', $siteLangId); ?>" href="<?php echo UrlHelper::generateUrl('Seller', 'productUrlRewriting'); ?>">
                            <span class="menu-sub-title navTextJs"><?php echo Labels::getLabel('LBL_URL_Rewriting', $siteLangId); ?></span></a>
                    </li>
                <?php } ?>
            </ul>
        </li>
    <?php } ?>

    <?php if (FatApp::getConfig('CONF_ENABLE_SELLER_SUBSCRIPTION_MODULE') && $userPrivilege->canViewSubscription(UserAuthentication::getLoggedUserId(), true)) { ?>
        <li class="dashboard-menu-item dropdownJs">
            <button class="dashboard-menu-btn menuLinkJs dropdown-toggle-custom collapsed" type="button" <?php if (false === $quickSearch) { ?>data-bs-toggle="collapse" data-bs-target="#nav-subscription" aria-expanded="true" aria-controls="collapseOne" <?php } ?> title="">
                <span class="dashboard-menu-icon">
                    <svg class="svg" width="18" height="18">
                        <use xlink:href="<?php echo CONF_WEBROOT_URL; ?>images/retina/sprite-sidebar.svg<?php echo AttachedFile::setTimeParam(RELEASE_DATE); ?>#subscription-packages">
                        </use>
                    </svg>
                </span>
                <span class="dashboard-menu-head menuTitleJs">
                    <?php echo Labels::getLabel('LBL_Subscription', $siteLangId); ?>
                </span>
                <?php if (false === $quickSearch) { ?>
                    <i class="dashboard-menu-arrow dropdown-toggle-custom-arrow"></i>
                <?php } ?>
            </button>
            <ul class="menu-sub menu-sub-accordion <?php echo $collapseClass; ?>" id="nav-subscription" aria-labelledby="" data-parent="#dashboard-menu">
                <li class="menu-sub-item navItemJs">
                    <a class="menu-sub-link navLinkJs <?php echo (false === $quickSearch && $controller == 'seller' && ($action == 'subscriptions' || $action == 'viewsubscriptionorder')) ? 'active' : ''; ?>" title="<?php echo Labels::getLabel('LBL_My_Subscriptions', $siteLangId); ?>" href="<?php echo UrlHelper::generateUrl('Seller', 'subscriptions'); ?>">
                        <span class="menu-sub-title navTextJs"><?php echo Labels::getLabel("LBL_My_Subscriptions", $siteLangId); ?></span></a>

                </li>
                <li class="menu-sub-item navItemJs">
                    <a class="menu-sub-link navLinkJs <?php echo (false === $quickSearch && $controller == 'seller' && ($action == 'packages')) ? 'active' : ''; ?>" title="<?php echo Labels::getLabel('LBL_Subscription_Packages', $siteLangId); ?>" href="<?php echo UrlHelper::generateUrl('seller', 'Packages'); ?>">
                        <span class="menu-sub-title navTextJs"><?php echo Labels::getLabel('LBL_Subscription_Packages', $siteLangId); ?></span></a>

                </li>
                <li class="menu-sub-item navItemJs">
                    <a class="menu-sub-link navLinkJs <?php echo (false === $quickSearch && $controller == 'seller' && ($action == 'selleroffers')) ? 'active' : ''; ?>" title="<?php echo Labels::getLabel('LBL_Subscription_Offers', $siteLangId); ?>" href="<?php echo UrlHelper::generateUrl('seller', 'SellerOffers'); ?>">
                        <span class="menu-sub-title navTextJs"><?php echo Labels::getLabel('LBL_Subscription_Offers', $siteLangId); ?></span></a>
                </li>
            </ul>
        </li>
    <?php } ?>

    <?php if (
        $userPrivilege->canViewSalesReport(UserAuthentication::getLoggedUserId(), true) ||
        $userPrivilege->canViewCatalogReport(UserAuthentication::getLoggedUserId(), true) ||
        $userPrivilege->canViewPerformanceReport(UserAuthentication::getLoggedUserId(), true) ||
        $userPrivilege->canViewInventoryReport(UserAuthentication::getLoggedUserId(), true)
    ) { ?>
        <li class="dashboard-menu-item dropdownJs">
            <button class="dashboard-menu-btn menuLinkJs dropdown-toggle-custom collapsed" type="button" <?php if (false === $quickSearch) { ?>data-bs-toggle="collapse" data-bs-target="#nav-sales-report" aria-expanded="true" aria-controls="collapseOne" <?php } ?> title="">
                <span class="dashboard-menu-icon">
                    <svg class="svg" width="18" height="18">
                        <use xlink:href="<?php echo CONF_WEBROOT_URL; ?>images/retina/sprite-sidebar.svg<?php echo AttachedFile::setTimeParam(RELEASE_DATE); ?>#products-inventory">
                        </use>
                    </svg>
                </span>
                <span class="dashboard-menu-head menuTitleJs">
                    <?php echo Labels::getLabel("LBL_Sales_Report", $siteLangId); ?>
                </span>
                <?php if (false === $quickSearch) { ?>
                    <i class="dashboard-menu-arrow dropdown-toggle-custom-arrow"></i>
                <?php } ?>
            </button>
            <ul class="menu-sub menu-sub-accordion <?php echo $collapseClass; ?>" id="nav-sales-report" aria-labelledby="" data-parent="#dashboard-menu">
                <?php if ($userPrivilege->canViewSalesReport(UserAuthentication::getLoggedUserId(), true)) { ?>
                    <li class="menu-sub-item navItemJs">
                        <a class="menu-sub-link navLinkJs <?php echo (false === $quickSearch && $controller == 'reports' && $action == 'salesreport') ? 'active' : ''; ?>" title="<?php echo Labels::getLabel('LBL_Sales_Over_Time', $siteLangId); ?>" href="<?php echo UrlHelper::generateUrl('Reports', 'SalesReport'); ?>">
                            <span class="menu-sub-title navTextJs"><?php echo Labels::getLabel('LBL_Sales_Over_Time', $siteLangId); ?></span></a>

                    </li>
                <?php } ?>
                <?php if ($userPrivilege->canViewCatalogReport(UserAuthentication::getLoggedUserId(), true)) { ?>
                    <li class="menu-sub-item navItemJs">
                        <a class="menu-sub-link navLinkJs <?php echo (false === $quickSearch && $controller == 'catalogreport' && $action == 'index') ? 'active' : ''; ?>" title="<?php echo Labels::getLabel('LBL_Products', $siteLangId); ?>" href="<?php echo UrlHelper::generateUrl('CatalogReport'); ?>">
                            <span class="menu-sub-title navTextJs"><?php echo Labels::getLabel('LBL_Products', $siteLangId); ?></span></a>

                    </li>
                <?php } ?>
            </ul>
        </li>

        <?php if ($userPrivilege->canViewFinancialReport(UserAuthentication::getLoggedUserId(), true)) { ?>
            <li class="dashboard-menu-item dropdownJs">
                <button class="dashboard-menu-btn menuLinkJs dropdown-toggle-custom collapsed" type="button" <?php if (false === $quickSearch) { ?>data-bs-toggle="collapse" data-bs-target="#nav-financial-report" aria-expanded="true" aria-controls="collapseOne" <?php } ?> title="">
                    <span class="dashboard-menu-icon">
                        <svg class="svg" width="18" height="18">
                            <use xlink:href="<?php echo CONF_WEBROOT_URL; ?>images/retina/sprite-sidebar.svg<?php echo AttachedFile::setTimeParam(RELEASE_DATE); ?>#product-profit">
                            </use>
                        </svg>
                    </span>
                    <span class="dashboard-menu-head menuTitleJs">
                        <?php echo Labels::getLabel("LBL_Financial_Report", $siteLangId); ?>
                    </span>
                    <i class="dashboard-menu-arrow dropdown-toggle-custom-arrow">
                    </i>
                </button>
                <ul class="menu-sub menu-sub-accordion <?php echo $collapseClass; ?>" id="nav-financial-report" aria-labelledby="" data-parent="#dashboard-menu">
                    <li class="menu-sub-item navItemJs">
                        <a class="menu-sub-link navLinkJs <?php echo (false === $quickSearch && $controller == 'productprofitreport' && $action == 'index') ? 'active' : ''; ?>" title="<?php echo Labels::getLabel('LBL_Profit_by_products', $siteLangId); ?>" href="<?php echo UrlHelper::generateUrl('ProductProfitReport'); ?>">
                            <span class="menu-sub-title navTextJs"><?php echo Labels::getLabel('LBL_Profit_by_products', $siteLangId); ?></span>
                        </a>
                    </li>
                    <li class="menu-sub-item navItemJs">
                        <a class="menu-sub-link navLinkJs <?php echo (false === $quickSearch && $controller == 'payoutreport' && $action == 'index') ? 'active' : ''; ?>" title="<?php echo Labels::getLabel('LBL_Payout', $siteLangId); ?>" href="<?php echo UrlHelper::generateUrl('PayoutReport'); ?>">
                            <span class="menu-sub-title navTextJs"><?php echo Labels::getLabel('LBL_Payout', $siteLangId); ?></span></a>

                    </li>
                    <li class="menu-sub-item navItemJs">
                        <a class="menu-sub-link navLinkJs <?php echo (false === $quickSearch && $controller == 'transactionreport' && $action == 'index') ? 'active' : ''; ?>" title="<?php echo Labels::getLabel('LBL_Transaction_Report', $siteLangId); ?>" href="<?php echo UrlHelper::generateUrl('TransactionReport'); ?>">
                            <span class="menu-sub-title navTextJs"><?php echo Labels::getLabel('LBL_Transaction_Report', $siteLangId); ?></span></a>

                    </li>
                </ul>
            </li>
        <?php } ?>

        <li class="dashboard-menu-item dropdownJs">
            <button class="dashboard-menu-btn menuLinkJs dropdown-toggle-custom collapsed" type="button" <?php if (false === $quickSearch) { ?>data-bs-toggle="collapse" data-bs-target="#nav-inventory-report" aria-expanded="true" aria-controls="collapseOne" <?php } ?> title="">
                <span class="dashboard-menu-icon">
                    <svg class="svg" width="18" height="18">
                        <use xlink:href="<?php echo CONF_WEBROOT_URL; ?>images/retina/sprite-sidebar.svg<?php echo AttachedFile::setTimeParam(RELEASE_DATE); ?>#product-performance-report">
                        </use>
                    </svg>
                </span>
                <span class="dashboard-menu-head menuTitleJs">
                    <?php echo Labels::getLabel("LBL_INVENTORY_REPORTS", $siteLangId); ?>
                </span>
                <?php if (false === $quickSearch) { ?>
                    <i class="dashboard-menu-arrow dropdown-toggle-custom-arrow"></i>
                <?php } ?>
            </button>
            <ul class="menu-sub menu-sub-accordion <?php echo $collapseClass; ?>" id="nav-inventory-report" aria-labelledby="" data-parent="#dashboard-menu">
                <?php if ($userPrivilege->canViewInventoryReport(UserAuthentication::getLoggedUserId(), true)) { ?>
                    <li class="menu-sub-item navItemJs">
                        <a class="menu-sub-link navLinkJs <?php echo (false === $quickSearch && $controller == 'reports' && $action == 'productsinventory') ? 'active' : ''; ?>" title="<?php echo Labels::getLabel('LBL_Products_Inventory', $siteLangId); ?>" href="<?php echo UrlHelper::generateUrl('Reports', 'productsInventory'); ?>">
                            <span class="menu-sub-title navTextJs"><?php echo Labels::getLabel('LBL_Products_Inventory', $siteLangId); ?></span></a>

                    </li>
                    <li class="menu-sub-item navItemJs">
                        <a class="menu-sub-link navLinkJs <?php echo (false === $quickSearch && $controller == 'reports' && $action == 'productsinventorystockstatus') ? 'active' : ''; ?>" title="<?php echo Labels::getLabel('LBL_Products_Inventory_Stock_Status', $siteLangId); ?>" href="<?php echo UrlHelper::generateUrl('Reports', 'productsInventoryStockStatus'); ?>">
                            <span class="menu-sub-title navTextJs"><?php echo Labels::getLabel('LBL_Products_Inventory_Stock_Status', $siteLangId); ?></span></a>

                    </li>
                <?php } ?>

                <?php if ($userPrivilege->canViewPerformanceReport(UserAuthentication::getLoggedUserId(), true)) { ?>
                    <li class="menu-sub-item navItemJs">
                        <a class="menu-sub-link navLinkJs <?php echo (false === $quickSearch && $controller == 'reports' && $action == 'productsperformance') ? 'active' : ''; ?>" title="<?php echo Labels::getLabel('LBL_Products_Performance', $siteLangId); ?>" href="<?php echo UrlHelper::generateUrl('Reports', 'ProductsPerformance'); ?>">
                            <span class="menu-sub-title navTextJs"><?php echo Labels::getLabel('LBL_Products_Performance', $siteLangId); ?></span></a>

                    </li>
                <?php } ?>
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
                <?php echo Labels::getLabel("LBL_Profile", $siteLangId); ?>
            </span>
            <i class="dashboard-menu-arrow dropdown-toggle-custom-arrow">
            </i>
        </button>
        <ul class="menu-sub menu-sub-accordion <?php echo $collapseClass; ?>" id="nav-profile" aria-labelledby="" data-parent="#dashboard-menu">
            <li class="menu-sub-item navItemJs">
                <a class="menu-sub-link navLinkJs <?php echo (false === $quickSearch && $controller == 'account' && $action == 'profileinfo') ? 'active' : ''; ?>" title="<?php echo Labels::getLabel('LBL_My_Account', $siteLangId); ?>" href="<?php echo UrlHelper::generateUrl('Account', 'ProfileInfo'); ?>">
                    <span class="menu-sub-title navTextJs"><?php echo Labels::getLabel("LBL_My_Account", $siteLangId); ?></span>
                </a>
            </li>

            <?php if ($userParentId == UserAuthentication::getLoggedUserId()) { ?>
                <?php if (!User::isAffiliate()) { ?>
                    <li class="menu-sub-item navItemJs">
                        <a class="menu-sub-link navLinkJs <?php echo (false === $quickSearch && $controller == 'account' && ($action == 'bankInfoForm')) ? 'active' : ''; ?>" title="<?php echo Labels::getLabel('LBL_BANK_ACCOUNT', $siteLangId); ?>" href="<?php echo UrlHelper::generateUrl('account', 'bankInfoForm'); ?>">
                            <span class="menu-sub-title navTextJs"><?php echo Labels::getLabel("LBL_BANK_ACCOUNT", $siteLangId); ?></span>
                        </a>
                    </li>
                <?php } ?>

                <?php if (FatApp::getConfig('CONF_ENABLE_COOKIES', FatUtility::VAR_INT, 1)) { ?>
                    <li class="menu-sub-item navItemJs">
                        <a class="menu-sub-link navLinkJs <?php echo (false === $quickSearch && $controller == 'account' && ($action == 'cookiesPreferencesForm')) ? 'active' : ''; ?>" title="<?php echo Labels::getLabel('LBL_COOKIE_PREFERENCES', $siteLangId); ?>" href="<?php echo UrlHelper::generateUrl('account', 'cookiesPreferencesForm'); ?>">
                            <span class="menu-sub-title navTextJs"><?php echo Labels::getLabel("LBL_COOKIE_PREFERENCES", $siteLangId); ?></span>
                        </a>
                    </li>
                <?php } ?>

                <?php if (Plugin::isActiveByType(Plugin::TYPE_PAYOUTS)) { ?>
                    <li class="menu-sub-item navItemJs">
                        <a class="menu-sub-link navLinkJs <?php echo (false === $quickSearch && $controller == 'account' && ($action == 'payouts')) ? 'active' : ''; ?>" title="<?php echo Labels::getLabel('LBL_PAYOUT_DETAIL', $siteLangId); ?>" href="<?php echo UrlHelper::generateUrl('account', 'payouts'); ?>">
                            <span class="menu-sub-title navTextJs"><?php echo Labels::getLabel("LBL_PAYOUT_DETAIL", $siteLangId); ?></span>
                        </a>
                    </li>
                <?php } ?>

                <li class="menu-sub-item navItemJs">
                    <a class="menu-sub-link navLinkJs <?php echo (false === $quickSearch && $controller == 'seller' && ($action == 'users' || $action == 'userpermissions')) ? 'active' : ''; ?>" title="<?php echo Labels::getLabel('LBL_Sub_Users', $siteLangId); ?>" href="<?php echo UrlHelper::generateUrl('Seller', 'Users'); ?>">
                        <span class="menu-sub-title navTextJs"><?php echo Labels::getLabel("LBL_Sub_Users", $siteLangId); ?></span>
                    </a>
                </li>
            <?php } ?>
            <?php if ($userPrivilege->canViewMessages(UserAuthentication::getLoggedUserId(), true)) { ?>
                <li class="menu-sub-item navItemJs">
                    <a class="menu-sub-link navLinkJs <?php echo (false === $quickSearch && $controller == 'account' && ($action == 'messages' || strtolower($action) == 'viewmessages')) ? 'active' : ''; ?>" title="<?php echo Labels::getLabel('LBL_Messages', $siteLangId); ?>" href="<?php echo UrlHelper::generateUrl('Account', 'Messages'); ?>">
                        <span class="menu-sub-title navTextJs"><?php echo Labels::getLabel("LBL_Messages", $siteLangId); ?>
                            <span class="msg-count"><?php echo CommonHelper::displayBadgeCount($todayUnreadMessageCount, 9); ?></span>
                        </span>
                    </a>
                </li>
            <?php } ?>
            <?php if ($userParentId == UserAuthentication::getLoggedUserId()) { ?>
                <li class="menu-sub-item navItemJs">
                    <a class="menu-sub-link navLinkJs <?php echo (false === $quickSearch && $controller == 'account' && $action == 'credits') ? 'active' : ''; ?>" title="<?php echo Labels::getLabel('LBL_My_Credits', $siteLangId); ?>" href="<?php echo UrlHelper::generateUrl('Account', 'credits'); ?>">
                        <span class="menu-sub-title navTextJs"><?php echo Labels::getLabel('LBL_My_Credits', $siteLangId); ?></span></a>

                </li>
            <?php } ?>
            <li class="menu-sub-item navItemJs">
                <a class="menu-sub-link navLinkJs <?php echo (false === $quickSearch && $controller == 'account' && $action == 'changeemailpassword') ? 'active' : ''; ?>" title="<?php echo Labels::getLabel('LBL_UPDATE_CREDENTIALS', $siteLangId); ?>" href="<?php echo UrlHelper::generateUrl('Account', 'changeEmailPassword'); ?>">
                    <span class="menu-sub-title navTextJs"><?php echo Labels::getLabel('LBL_UPDATE_CREDENTIALS', $siteLangId); ?></span></a>

            </li>
        </ul>
    </li>
    <?php if ($userPrivilege->canViewSellerPlugins(UserAuthentication::getLoggedUserId(), true)) { ?>
        <li class="dashboard-menu-item dropdownJs">
            <button class="dashboard-menu-btn menuLinkJs dropdown-toggle-custom collapsed" type="button" <?php if (false === $quickSearch) { ?>data-bs-toggle="collapse" data-bs-target="#nav-plugins" aria-expanded="true" aria-controls="collapseOne" <?php } ?> title="">
                <span class="dashboard-menu-icon">
                    <svg class="svg" width="18" height="18">
                        <use xlink:href="<?php echo CONF_WEBROOT_URL; ?>images/retina/sprite-sidebar.svg<?php echo AttachedFile::setTimeParam(RELEASE_DATE); ?>#plugin-data-migration">
                        </use>
                    </svg>
                </span>
                <span class="dashboard-menu-head menuTitleJs">
                    <?php echo Labels::getLabel("LBL_PLUGINS", $siteLangId); ?>
                </span>
                <?php if (false === $quickSearch) { ?>
                    <i class="dashboard-menu-arrow dropdown-toggle-custom-arrow"></i>
                <?php } ?>
            </button>
            <ul class="menu-sub menu-sub-accordion <?php echo $collapseClass; ?>" id="nav-plugins" aria-labelledby="" data-parent="#dashboard-menu">
                <?php foreach (SellerPlugin::getAllowedTypeArr($siteLangId) as $type => $name) {
                    $canUseShippingApi = Shipping::canUseShippingApi(UserAuthentication::getLoggedUserId(0));
                    if (false === $canUseShippingApi && Plugin::TYPE_SHIPPING_SERVICES == $type) {
                        continue;
                    }
                ?>
                    <li class="menu-sub-item navItemJs">
                        <a class="menu-sub-link navLinkJs <?php echo (false === $quickSearch && $controller == 'sellerplugins' && $action == 'index' && is_array($params) && current($params) == $type) ? 'active' : ''; ?>" title="<?php echo $name; ?>" href="<?php echo UrlHelper::generateUrl('sellerPlugins', 'index', [$type]); ?>">
                            <span class="menu-sub-title navTextJs"><?php echo $name; ?></span>
                        </a>

                    </li>
                <?php } ?>
            </ul>
        </li>
    <?php } ?>
    <?php if ($userPrivilege->canViewImportExport(UserAuthentication::getLoggedUserId(), true)) { ?>
        <li class="dashboard-menu-item dropdownJs">
            <button class="dashboard-menu-btn menuLinkJs dropdown-toggle-custom collapsed" type="button" <?php if (false === $quickSearch) { ?>data-bs-toggle="collapse" data-bs-target="#nav-import-export" aria-expanded="true" aria-controls="collapseOne" <?php } ?> title="">
                <span class="dashboard-menu-icon">
                    <svg class="svg" width="18" height="18">
                        <use xlink:href="<?php echo CONF_WEBROOT_URL; ?>images/retina/sprite-sidebar.svg<?php echo AttachedFile::setTimeParam(RELEASE_DATE); ?>#import-export">
                        </use>
                    </svg>
                </span>
                <span class="dashboard-menu-head menuTitleJs">
                    <?php echo Labels::getLabel('LBL_Import_Export', $siteLangId); ?>
                </span>
                <?php if (false === $quickSearch) { ?>
                    <i class="dashboard-menu-arrow dropdown-toggle-custom-arrow"></i>
                <?php } ?>
            </button>
            <ul class="menu-sub menu-sub-accordion <?php echo $collapseClass; ?>" id="nav-import-export" aria-labelledby="" data-parent="#dashboard-menu">
                <?php if (FatApp::getConfig('CONF_ENABLE_IMPORT_EXPORT', FatUtility::VAR_INT, 0)) { ?>
                    <li class="menu-sub-item navItemJs">
                        <a class="menu-sub-link navLinkJs <?php echo (false === $quickSearch && $controller == 'importexport' && ($action == 'index')) ? 'active' : ''; ?>" title="<?php echo Labels::getLabel('LBL_IMPORT_EXPORT', $siteLangId); ?>" href="<?php echo UrlHelper::generateUrl('ImportExport'); ?>">
                            <span class="menu-sub-title navTextJs"><?php echo Labels::getLabel('LBL_Import_Export', $siteLangId); ?>
                            </span>
                        </a>

                    </li>
                <?php } ?>
            </ul>
        </li>
    <?php } ?>

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