<?php
$adminSidebar = $_COOKIE['adminSidebar'] ?? 0;
$adminSidebar = (in_array(FatApp::getController(), ['ProductsController', 'CustomProductsController']) && FatApp::getAction() == 'form') ? 0 : $adminSidebar;
?>
<sidebar class="sidebar sidebar-hoverable" id="sidebar" data-close-on-click-outside="sidebar">
    <div class="sidebar-logo">
        <button class="sidebar-toggle sidebarOpenerBtnJs <?php if (0 < $adminSidebar) { ?>active<?php } ?>" type="button" title="<?php echo 0 < $adminSidebar ? Labels::getLabel('LBL_CLICK_TO_HIDE', $siteLangId) : Labels::getLabel('LBL_CLICK_TO_EXPAND', $siteLangId); ?>">
            <span class="sidebar-toggle-icon"><span class="toggle-line"></span></span>
        </button>
        <a href="<?php echo UrlHelper::generateUrl(); ?>" class="logo">
            <?php
            $fileData = AttachedFile::getAttachment(AttachedFile::FILETYPE_ADMIN_LOGO, 0, 0, $siteLangId, false);
            $logoWidth = '';
            $uploadedTime = AttachedFile::setTimeParam($fileData['afile_updated_at']);
            if (AttachedFile::FILE_ATTACHMENT_TYPE_SVG == $fileData['afile_attachment_type']) {
                $imgUrl = UrlHelper::getStaticImageUrl($fileData['afile_physical_path']) . $uploadedTime;
                $logoWidth = 'width="100"';
            } else {
                $aspectRatioArr = AttachedFile::getRatioTypeArray($siteLangId);
                $imgUrl = UrlHelper::getCachedUrl(UrlHelper::generateFileUrl('Image', 'siteAdminLogo', array($siteLangId)) . $uploadedTime, CONF_IMG_CACHE_TIME, '.jpg');
            }
            ?>
            <img <?php if (AttachedFile::FILE_ATTACHMENT_TYPE_OTHER == $fileData['afile_attachment_type'] && $fileData['afile_aspect_ratio'] > 0) { ?> data-ratio="<?php echo $aspectRatioArr[$fileData['afile_aspect_ratio']]; ?>" <?php } else { ?> data-ratio="1:1" <?php } ?> title="<?php echo FatApp::getConfig("CONF_WEBSITE_NAME_" . $siteLangId); ?>" src="<?php echo $imgUrl; ?>" alt="<?php echo FatApp::getConfig("CONF_WEBSITE_NAME_" . $siteLangId); ?>" <?php echo $logoWidth; ?>>
        </a>
    </div>
    <div class="sidebar-menu sidebarMenuJs" id="sidebar-menu">
        <?php require CONF_THEME_PATH . '_partial/navigation/nav-links.php'; ?>
    </div>
    <?php if ($objPrivilege->canViewSettings(AdminAuthentication::getLoggedAdminId(), true)) { ?>
        <div class="sidebar-foot">
            <ul class="menu">
                <li class="menu-item dropdownJs">
                    <button class="menu-section menuLinkJs" data-selector='["Settings"]' onclick="redirectFn('<?php echo UrlHelper::generateUrl('Settings'); ?>')" type="button">
                        <span class="menu-icon">
                            <svg class="svg" width="24" height="24">
                                <use xlink:href="<?php echo CONF_WEBROOT_URL; ?>images/retina/sprite-aside-menu.svg<?php echo AttachedFile::setTimeParam(RELEASE_DATE); ?>#icon-system-settings">
                                </use>
                            </svg>
                        </span>
                        <span class="menu-title"><?php echo Labels::getLabel('NAV_SETTINGS', $siteLangId); ?></span>
                    </button>
                </li>
            </ul>
        </div>
    <?php } ?>
</sidebar>