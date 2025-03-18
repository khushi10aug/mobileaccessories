<?php defined('SYSTEM_INIT') or die('Invalid Usage.');
$signinpopup = $signinpopup ?? 0;
if (0 < $signinpopup) { ?>
    <div class="modal-header border-0"></div>
    <div class="modal-body">
        <div class="login-popup loaderContainerJs">
            <?php
            $loginData['signInWithPhone'] = $signInWithPhone;
            $loginData['signinpopup'] = $signinpopup;
            $loginData['signInWithEmail'] = $signInWithEmail;
            $this->includeTemplate('guest-user/loginPageTemplate.php', $loginData, false);
            ?>
        </div>
    </div>
<?php } else { ?>
    <div id="body" class="body enter-page loginFormJs">
        <div class="form-sign">
            <?php
            $imgDataType = '';
            $logoWidth = '';
            $logoUrl = UrlHelper::generateUrl();
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
            <a class="form-sign-logo" href="<?php echo $logoUrl; ?>" <?php echo $imgDataType; ?>>
                <img <?php if (AttachedFile::FILE_ATTACHMENT_TYPE_OTHER == $fileData['afile_attachment_type'] && $fileData['afile_aspect_ratio'] > 0) { ?> data-ratio="<?php echo $aspectRatioArr[$fileData['afile_aspect_ratio']]; ?>" <?php } ?> src="<?php echo $siteLogo; ?>" alt="<?php echo FatApp::getConfig('CONF_WEBSITE_NAME_' . $siteLangId, FatUtility::VAR_STRING, '') ?>" title="<?php echo FatApp::getConfig('CONF_WEBSITE_NAME_' . $siteLangId, FatUtility::VAR_STRING, '') ?>" <?php echo $logoWidth; ?>>
            </a>
            <div class="form-sign-body">
                <?php
                $loginData['signInWithPhone'] = $signInWithPhone;
                $loginData['signInWithEmail'] = $signInWithEmail;
                $this->includeTemplate('guest-user/loginPageTemplate.php', $loginData, false);
                ?>
            </div>
        </div>
    </div>
    <?php include(CONF_THEME_PATH . '_partial/footer-part/fonts.php'); ?>
<?php } ?>