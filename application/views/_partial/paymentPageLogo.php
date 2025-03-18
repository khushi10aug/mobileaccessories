<?php defined('SYSTEM_INIT') or die('Invalid Usage'); ?>
<div class="logo-payment">
    <?php   
    $fileData = AttachedFile::getAttachment(AttachedFile::FILETYPE_PAYMENT_PAGE_LOGO, 0, 0, $siteLangId, false);
    if (AttachedFile::FILE_ATTACHMENT_TYPE_SVG == $fileData['afile_attachment_type']) {
        $imgUrl = UrlHelper::getStaticImageUrl($fileData['afile_physical_path']);
    } else {
        // $aspectRatioArr = AttachedFile::getRatioTypeArray($siteLangId);
        $imgUrl = UrlHelper::generateFullUrl('Image','paymentPageLogo',array($siteLangId), CONF_WEBROOT_FRONT_URL);
    }
    ?>
    <img src="<?php echo $imgUrl; ?>" alt="<?php echo FatApp::getConfig('CONF_WEBSITE_NAME_'.$siteLangId) ?>" title="<?php echo FatApp::getConfig('CONF_WEBSITE_NAME_'.$siteLangId) ?>" />
</div>