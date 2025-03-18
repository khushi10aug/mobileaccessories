<div class="sidebar-head">
    <?php $isOpened = '';
    if (array_key_exists('openSidebar', $_COOKIE) && !empty(FatUtility::int($_COOKIE['openSidebar'])) && array_key_exists('screenWidth', $_COOKIE) && applicationConstants::MOBILE_SCREEN_WIDTH < FatUtility::int($_COOKIE['screenWidth'])) {
        $isOpened = 'is-opened';
    } ?>
    <div class="js-hamburger hamburger-toggle <?php echo $isOpened; ?>">
        <span class="bar-top"></span>
        <span class="bar-mid"></span>
        <span class="bar-bot"> </span>
    </div>
    <?php
    $imgDataType = '';
    $logoWidth = '';
    $logoUrl = UrlHelper::generateUrl('', '', [], CONF_WEBROOT_FRONTEND, null, false, false, true, $siteLangId);
    $fileData = AttachedFile::getAttachment(AttachedFile::FILETYPE_FRONT_LOGO, 0, 0, $siteLangId, false);
    $uploadedTime = isset($fileData['afile_updated_at']) ? AttachedFile::setTimeParam($fileData['afile_updated_at']) : '';
    if (AttachedFile::FILE_ATTACHMENT_TYPE_SVG == $fileData['afile_attachment_type']) {
        $siteLogo = UrlHelper::getStaticImageUrl($fileData['afile_physical_path']) . $uploadedTime;
        $imgDataType = 'data-type="svg"';
        $logoWidth = 'width="120"';
    } else {
        $aspectRatioArr = AttachedFile::getRatioTypeArray($siteLangId);
        $siteLogo = UrlHelper::getCachedUrl(UrlHelper::generateFullFileUrl('Image', 'siteLogo', array($siteLangId), CONF_WEBROOT_FRONTEND) . $uploadedTime, CONF_IMG_CACHE_TIME, '.jpg');
    }

    $ratio = '';
    if (AttachedFile::FILE_ATTACHMENT_TYPE_OTHER == $fileData['afile_attachment_type'] && isset($fileData['afile_aspect_ratio']) && $fileData['afile_aspect_ratio'] > 0 && isset($aspectRatioArr[$fileData['afile_aspect_ratio']])) {
        $ratio = $aspectRatioArr[$fileData['afile_aspect_ratio']];
    }
    ?>
    <div class="logo-dashboard" <?php echo $imgDataType; ?>>
        <a href="<?php echo $logoUrl; ?>">
            <img data-ratio="<?php echo $ratio; ?>" src="<?php echo $siteLogo; ?>" alt="<?php echo FatApp::getConfig('CONF_WEBSITE_NAME_' . $siteLangId) ?>" title="<?php echo FatApp::getConfig('CONF_WEBSITE_NAME_' . $siteLangId) ?>" <?php echo $logoWidth; ?>>
        </a>
    </div>


</div>