<?php
$logoUrl = UrlHelper::generateUrl();
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
<a class="form-sign-logo" href="<?php echo $logoUrl; ?>" <?php echo $imgDataType; ?>>
    <img <?php if (AttachedFile::FILE_ATTACHMENT_TYPE_OTHER == $fileData['afile_attachment_type'] && $fileData['afile_aspect_ratio'] > 0) { ?> data-ratio="<?php echo $aspectRatioArr[$fileData['afile_aspect_ratio']]; ?>" <?php } ?> src="<?php echo $siteLogo; ?>" alt="<?php echo FatApp::getConfig('CONF_WEBSITE_NAME_' . $siteLangId, FatUtility::VAR_STRING, '') ?>" title="<?php echo FatApp::getConfig('CONF_WEBSITE_NAME_' . $siteLangId, FatUtility::VAR_STRING, '') ?>" <?php echo $logoWidth; ?>>
</a>
<div class="form-sign-body">
    <div class="card-sign">
        <div class="card-sign_head">
            <h2 class="title">
                <?php echo Labels::getLabel('LBL_CREATE_YOUR_SITE_ACCOUNT', $siteLangId); ?>
            </h2>
        </div>
        <div class="card-sign_body">
            <?php $this->includeTemplate('guest-user/registerationFormTemplate.php', $registerdata, false); ?>
        </div>
        <div class="card-sign_foot">
            <h6><?php echo Labels::getLabel('LBL_ALREADY_HAVE_AN_ACCOUNT?', $siteLangId); ?></h6>
            <div class="more-links">
                <?php if (isset($registerdata['signUpWithPhone']) && true === $smsPluginStatus) {
                    if (0 == $registerdata['signUpWithPhone']) { ?>
                        <a class="link-underline otp-link" href="javaScript:void(0)" onClick="signUpWithPhone()"><?php echo Labels::getLabel('LBL_USE_PHONE_NUMBER_INSTEAD_?', $siteLangId); ?></a>
                    <?php } else { ?>
                        <a class="link-underline otp-link" href="javaScript:void(0)" onClick="signUpWithEmail()"><?php echo Labels::getLabel('LBL_USE_EMAIL_INSTEAD_?', $siteLangId); ?></a>
                    <?php } ?>
                <?php } ?>

                <a class="link-underline loginRegBtn--js" href="<?php echo UrlHelper::generateUrl('GuestUser', 'LoginForm'); ?>">
                    <?php echo Labels::getLabel('LBL_SIGN_IN_NOW', $siteLangId); ?>
                </a>
            </div>
        </div>
    </div>
</div>