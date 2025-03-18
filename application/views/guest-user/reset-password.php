<?php defined('SYSTEM_INIT') or die('Invalid Usage.');


$frm->setRequiredStarPosition(Form::FORM_REQUIRED_STAR_POSITION_NONE);
$frm->setFormTagAttribute('class', 'form');
$frm->setValidatorJsObjectName('resetValObj');
$frm->developerTags['colClassPrefix'] = 'col-lg-12 col-md-12 col-sm-';
$frm->developerTags['fld_default_col'] = 12;
$frm->setFormTagAttribute('action', '');
$btnFld = $frm->getField('btn_submit');
$btnFld->setFieldTagAttribute('class', 'btn btn-secondary btn-block');
$btnFld->developerTags['noCaptionTag'] = true;
if (empty($user_password)) {
    $btnFld->value = Labels::getLabel('LBL_SET_PASSWORD', $siteLangId);
}

$frm->setFormTagAttribute('onSubmit', 'resetpwd(this, resetValObj); return(false);');
$passFld = $frm->getField('new_pwd');
$passFld->setFieldTagAttribute('placeholder', Labels::getLabel('LBL_NEW_PASSWORD', $siteLangId));
$confirmFld = $frm->getField('confirm_pwd');
$confirmFld->setFieldTagAttribute('placeholder', Labels::getLabel('LBL_CONFIRM_NEW_PASSWORD', $siteLangId));
$fld = $frm->getField('user_name');
$fld->setFieldTagAttribute('disabled', 'disabled');
$fld->value = $credential_username;
?>
<div id="body" class="body enter-page">
    <div class="form-sign">
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
                        <?php echo empty($user_password) ? Labels::getLabel('LBL_SET_PASSWORD', $siteLangId) : Labels::getLabel('LBL_RESET_PASSWORD', $siteLangId); ?>
                    </h2>
                </div>
                <div class="card-sign_body">
                    <?php echo $frm->getFormHtml(); ?>
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
</div>

<?php include(CONF_THEME_PATH . '_partial/footer-part/fonts.php'); ?>