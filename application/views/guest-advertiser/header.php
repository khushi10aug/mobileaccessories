<?php
defined('SYSTEM_INIT') or die('Invalid Usage.');

$commonHeadData = array(
    'siteLangId' => $siteLangId,
    'controllerName' => $controllerName,
    'action' => $action,
    'jsVariables' => $jsVariables,
    'canonicalUrl' => isset($canonicalUrl) ? $canonicalUrl : '',
);

if (isset($socialShareContent) && $socialShareContent != '') {
    $commonHeadData['socialShareContent'] = $socialShareContent;
}

$this->includeTemplate('_partial/header/commonHeadTop.php', $commonHeadData, false);
/* This is not included in common head, because, commonhead file not able to access the $this->Controller and $this->action[ */
echo $this->writeMetaTags();
/* ] */
$this->includeTemplate('_partial/header/commonHeadMiddle.php', $commonHeadData, false);
/* This is not included in common head, because, if we are adding any css/js from any controller then that file is not included[ */
echo $this->getJsCssIncludeHtml(!CONF_DEVELOPMENT_MODE);
/* ] */

$this->includeTemplate('_partial/header/commonHeadBottom.php', $commonHeadData, false);
?>
<div class="wrapper">
    <div id="header" class="header header-advertiser">
        <?php
        if (FatApp::getConfig('CONF_AUTO_RESTORE_ON', FatUtility::VAR_INT, 1) && CommonHelper::demoUrl()) {
            $this->includeTemplate('restore-system/top-header.php');
        }
        ?>

        <div class="top-head">
            <div class="container">
                <div class="logo-bar">
                    <div class="logo-bar-start">
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
                            <a href="<?php echo UrlHelper::generateUrl(); ?>">
                                <img <?php if (AttachedFile::FILE_ATTACHMENT_TYPE_OTHER == $fileData['afile_attachment_type'] && $fileData['afile_aspect_ratio'] > 0) { ?>
                                    data-ratio="<?php echo $aspectRatioArr[$fileData['afile_aspect_ratio']]; ?>" <?php } ?> src="<?php echo $siteLogo; ?>"
                                    alt="<?php echo FatApp::getConfig('CONF_WEBSITE_NAME_' . $siteLangId) ?>"
                                    title="<?php echo FatApp::getConfig('CONF_WEBSITE_NAME_' . $siteLangId) ?>" <?php echo $logoWidth; ?> />
                            </a>
                        </div>
                    </div>
                    <div class="logo-bar-end">
                        <ul class="quick-nav">
                            <?php $this->includeTemplate('_partial/headerTopNavigation.php'); ?>
                            <li class="quick-nav-item item-mobile">
                                <div class="dropdown">
                                    <button type="button" class="quick-nav-link" data-bs-toggle="offcanvas" data-bs-target="#offcanvas-seller-nav">
                                        <svg class="svg" width="20" height="20">
                                            <use xlink:href="<?php echo CONF_WEBROOT_URL; ?>images/retina/sprite-header.svg<?php echo AttachedFile::setTimeParam(RELEASE_DATE); ?>#mbl-menu"></use>
                                        </svg>
                                    </button>
                                </div>
                            </li>
                            <li class="quick-nav-item">
                                <div class="dropdown">
                                    <button type="button" class="quick-nav-link button-account sign-in sign-in-popup-js">
                                        <svg class="svg" width="20" height="20">
                                            <use xlink:href="<?php echo CONF_WEBROOT_URL; ?>images/retina/sprite-header.svg<?php echo AttachedFile::setTimeParam(RELEASE_DATE); ?>#login"></use>
                                        </svg>
                                        <span class="txt">
                                            <?php echo Labels::getLabel('LBL_Login', $siteLangId); ?> </span>
                                    </button>
                                </div>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>