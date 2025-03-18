<?php defined('SYSTEM_INIT') or die('Invalid Usage.');
if (0 < $withPhone) {
    $frm->setFormTagAttribute('onsubmit', 'getOtpForm(this); return(false);');
}
?>
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
        }
        ?>
        <a class="form-sign-logo" href="<?php echo UrlHelper::generateFullFileUrl(); ?>" <?php echo $imgDataType; ?>>
            <img src="<?php echo $siteLogo; ?>" alt="<?php echo FatApp::getConfig('CONF_WEBSITE_NAME_' . $siteLangId, FatUtility::VAR_STRING, '') ?>" title="<?php echo FatApp::getConfig('CONF_WEBSITE_NAME_' . $siteLangId, FatUtility::VAR_STRING, '') ?>" <?php echo $logoWidth; ?>>
        </a>
        <div class="form-sign-body">
            <div class="card-sign">
                <div class="card-sign_head">
                    <h1 class="title">
                        <?php echo Labels::getLabel('LBL_FORGOT_PASSWORD?', $siteLangId); ?>
                    </h1>
                    <p>
                        <?php if (1 > $withPhone) {
                            echo Labels::getLabel('LBL_ENTER_THE_E-MAIL_ADDRESS_ASSOCIATED_WITH_YOUR_ACCOUNT', $siteLangId);
                        } else {
                            echo Labels::getLabel('LBL_RECOVER_PASSWORD_FORM_MSG', $siteLangId);
                        } ?>
                        <?php if (isset($smsPluginStatus) && true === $smsPluginStatus) {
                            if (isset($withPhone) && 1 > $withPhone) { ?>
                                <a class="link-underline" href="javaScript:void(0)" onClick="forgotPwdForm(<?php echo applicationConstants::YES; ?>)">
                                    <?php echo Labels::getLabel('LBL_USE_PHONE_NUMBER_INSTEAD', $siteLangId); ?>
                                </a>
                            <?php } else { ?>
                                <a class="link-underline" href="javaScript:void(0)" onClick="forgotPwdForm(<?php echo applicationConstants::NO; ?>)">
                                    <?php echo Labels::getLabel('LBL_USE_EMAIL_INSTEAD', $siteLangId); ?>
                                </a>
                            <?php } ?>
                        <?php } ?>
                    </p>
                </div>
            </div>
            <div class="card-sign_body">
                <?php
                $frm->setFormTagAttribute('class', 'form form--normal');
                $frm->developerTags['colClassPrefix'] = 'col-lg-12 col-md-12 col-sm-';
                $frm->developerTags['fld_default_col'] = 12;
                $frm->setFormTagAttribute('id', 'frmPwdForgot');
                $frm->setFormTagAttribute('autocomplete', 'off');
                $frm->setValidatorJsObjectName('forgotValObj');
                $frm->setFormTagAttribute('action', UrlHelper::generateUrl('GuestUser', 'forgotPassword'));
                $btnFld = $frm->getField('btn_submit');
                $btnFld->setFieldTagAttribute('class', 'btn btn-secondary btn-block');
                if (1 > $withPhone) {
                    $frmFld = $frm->getField('user_email_username');
                    $frmFld->setFieldTagAttribute('placeholder', Labels::getLabel('LBL_USERNAME_OR_EMAIL', $siteLangId));
                } else {
                    $frmFld = $frm->getField('user_phone');
                }
                $frmFld->developerTags['noCaptionTag'] = true;

                $frmFld = $frm->getField('btn_submit');
                $frmFld->developerTags['noCaptionTag'] = true;
                echo $frm->getFormHtml(); ?>
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
<?php
$siteKey = FatApp::getConfig('CONF_RECAPTCHA_SITEKEY', FatUtility::VAR_STRING, '');
$secretKey = FatApp::getConfig('CONF_RECAPTCHA_SECRETKEY', FatUtility::VAR_STRING, '');
if (!empty($siteKey) && !empty($secretKey)) { ?>
    <script src='https://www.google.com/recaptcha/api.js?onload=googleCaptcha&render=<?php echo $siteKey; ?>'></script>
<?php } ?>