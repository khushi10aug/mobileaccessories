<?php defined('SYSTEM_INIT') or die('Invalid Usage.');
if (FatApp::getConfig('CONF_AUTO_RESTORE_ON', FatUtility::VAR_INT, 1) && CommonHelper::demoUrl()) {
    $this->includeTemplate('restore-system/top-header.php');
    $this->includeTemplate('restore-system/page-content.php');
} ?>
<section class="checkout">
    <header class="header-checkout" data-header="">
        <div class="container">
            <div class="header-checkout_inner">
                <?php
                $fileData = AttachedFile::getAttachment(AttachedFile::FILETYPE_FRONT_LOGO, 0, 0, $siteLangId, false);
                $uploadedTime = AttachedFile::setTimeParam($fileData['afile_updated_at']);
                $logoWidth = '';
                if (AttachedFile::FILE_ATTACHMENT_TYPE_SVG == $fileData['afile_attachment_type']) {
                    $siteLogo = UrlHelper::getStaticImageUrl($fileData['afile_physical_path']) . $uploadedTime;
                    $logoWidth = 'width="120"';
                } else {
                    $aspectRatioArr = AttachedFile::getRatioTypeArray($siteLangId);
                    $siteLogo = UrlHelper::getCachedUrl(UrlHelper::generateFullFileUrl('Image', 'siteLogo', array($siteLangId), CONF_WEBROOT_FRONT_URL) . $uploadedTime, CONF_IMG_CACHE_TIME, '.jpg');
                }
                ?>
                <a class="logo-checkout-main" href="<?php echo UrlHelper::generateUrl(); ?>">
                    <img <?php if (AttachedFile::FILE_ATTACHMENT_TYPE_OTHER == $fileData['afile_attachment_type'] && $fileData['afile_aspect_ratio'] > 0) { ?> data-ratio="<?php echo $aspectRatioArr[$fileData['afile_aspect_ratio']]; ?>" <?php } ?> src="<?php echo $siteLogo; ?>" alt="<?php echo FatApp::getConfig('CONF_WEBSITE_NAME_' . $siteLangId) ?>" title="<?php echo FatApp::getConfig('CONF_WEBSITE_NAME_' . $siteLangId) ?>" <?php echo $logoWidth; ?>>
                </a>

                <ul class="checkout-progress">
                    <li id="step1" class="checkout-progress-step checkoutNav-js">
                        <a href="<?php echo UrlHelper::generateUrl('Seller', 'packages'); ?>">
                            <?php echo Labels::getLabel('LBL_PACKAGES', $siteLangId); ?>
                        </a>
                    </li>
                    <li id="step2" class="checkout-progress-step checkoutNav-js payment-js">
                        <?php echo Labels::getLabel('LBL_PAYMENT', $siteLangId); ?>
                    </li>
                </ul>
            </div>
        </div>
    </header>