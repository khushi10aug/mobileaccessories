<?php defined('SYSTEM_INIT') or die('Invalid Usage.'); ?>

<div id="body" class="body enter-page forgotPwForm">
    <div id="otpFom" class="form-sign">
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
            $logoWidth = 'width="180"';
        }
        ?>
        <a class="form-sign-logo" href="<?php echo UrlHelper::generateFullFileUrl(); ?>" <?php echo $imgDataType; ?>>
            <img src="<?php echo $siteLogo; ?>" alt="<?php echo FatApp::getConfig('CONF_WEBSITE_NAME_' . $siteLangId, FatUtility::VAR_STRING, '') ?>" title="<?php echo FatApp::getConfig('CONF_WEBSITE_NAME_' . $siteLangId, FatUtility::VAR_STRING, '') ?>" <?php echo $logoWidth; ?> />
        </a>
        <div class="form-sign-body">
            <div class="card-sign">
                <div class="card-sign_head">
                    <h1 class="title">
                        <?php echo Labels::getLabel('LBL_CONFIGURE_YOUR_EMAIL', $siteLangId); ?>
                    </h1>
                    <p class="text-muted"><?php echo Labels::getLabel('MSG_YOUR_EMAIL_WILL_NOT_UPDATE_UNTIL_YOU_VERIFY_YOUR_EMAIL_ADDRESS', $siteLangId) ?></p>
                </div>
            </div>
            <div class="card-sign_body">
                <?php
                if (!empty($newEmailToVerify)) {
                    $message = CommonHelper::replaceStringData(Labels::getLabel('LBL_PLEASE_VERIFY_YOUR_EMAIL_ID_SENT_ON_{EMAIL-ID}', $siteLangId), ['{EMAIL-ID}' => $newEmailToVerify]);
                    echo HtmlHelper::getInfoMessageHtml($message);
                }
                ?>
                <div id="changeEmailFrmBlock">
                    <?php echo Labels::getLabel('LBL_Loading..', $siteLangId); ?>
                </div>
            </div>
            <div class="card-sign_foot">
                <div class="more-links">
                    <a href="<?php echo UrlHelper::generateUrl('GuestUser', 'loginForm'); ?>" class="link-underline">
                        <?php echo Labels::getLabel('LBL_BACK_TO_LOGIN', $siteLangId); ?>
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
<?php include(CONF_THEME_PATH . '_partial/footer-part/fonts.php'); ?>