<?php defined('SYSTEM_INIT') or die('Invalid Usage.');

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

$userIdFld = $frm->getField('user_id');
$userId = $userIdFld->value;

$frm->setFormTagAttribute('class', 'form form-login form-otp otpForm-js');
$frm->developerTags['fld_default_col'] = 2;
$frm->setFormTagAttribute('name', 'frmGuestLoginOtp');
$frm->setFormTagAttribute('id', 'frmGuestLoginOtp');
if (!$frm->getFormTagAttribute('onsubmit')) {
    $frm->setFormTagAttribute('onsubmit', 'return validateRegOtp(this);');
}

$btnFld = $frm->getField('btn_submit');
$btnFld->setFieldTagAttribute('class', 'btn btn-brand btn-block');
?>
<a class="form-sign-logo" id="logoOtp" href="<?php echo $logoUrl; ?>" <?php echo $imgDataType; ?>>
    <img <?php if (AttachedFile::FILE_ATTACHMENT_TYPE_OTHER == $fileData['afile_attachment_type'] && $fileData['afile_aspect_ratio'] > 0) { ?> data-ratio="<?php echo $aspectRatioArr[$fileData['afile_aspect_ratio']]; ?>" <?php } ?> src="<?php echo $siteLogo; ?>" alt="<?php echo FatApp::getConfig('CONF_WEBSITE_NAME_' . $siteLangId, FatUtility::VAR_STRING, '') ?>" title="<?php echo FatApp::getConfig('CONF_WEBSITE_NAME_' . $siteLangId, FatUtility::VAR_STRING, '') ?>" <?php echo $logoWidth; ?>>
</a>
<div class="login-popup">
    <div class="card-sign">
        <div class="card-sign_head">
            <h2 class="title">
                <?php echo Labels::getLabel('LBL_VERIFY_YOUR_PHONE_NUMBER', $siteLangId); ?>
            </h2>
        </div>
        <div class="card-sign_body">
            <?php echo $frm->getFormTag(); ?>
            <div class="row">
                <div class="col-md-12">
                    <div class="otp-row">
                        <?php for ($i = 0; $i < User::OTP_LENGTH; $i++) { ?>
                            <div class="otp-col otpCol-js">
                                <?php
                                $fld = $frm->getField('upv_otp[' . $i . ']');
                                $fld->setFieldTagAttribute('class', 'otpVal-js');
                                echo $frm->getFieldHtml('upv_otp[' . $i . ']'); ?>
                                <?php if ($i < (User::OTP_LENGTH - 1)) { ?>
                                    <span class="dash">-</span>
                                <?php } ?>
                            </div>
                        <?php } ?>
                    </div>
                </div>
                <div class="col-md-12">
                    <p class="form-text text-muted">
                        <?php echo Labels::getLabel('LBL_ENTER_THE_OTP_YOU_RECEIVED_ON_YOUR_PHONE_NUMBER', $siteLangId); ?>
                    </p>
                </div>
            </div>

            <div class="row mt-4">
                <div class="col">
                    <?php echo $frm->getFieldHtml('btn_submit'); ?>
                </div>
            </div>
            <div class="d-none">
                <p class="form-text text-muted text-center otp-seconds countdownFld--js">
                    <?php
                    $msg = Labels::getLabel('LBL_PLEASE_WAIT_{SECONDS}_SECONDS_TO_RESEND', $siteLangId);
                    $replace = [
                        '{SECONDS}' => '<span class="intervaltime intervalTimer-js">' . User::OTP_INTERVAL . '</span>',
                    ];
                    echo CommonHelper::replaceStringData($msg, $replace);
                    ?>
                </p>
            </div>
            <?php echo $frm->getFieldHtml('user_id'); ?>
            </form>
            <?php echo $frm->getExternalJs(); ?>
        </div>
        <div class="card-sign_foot">
            <div class="more-links d-none">
                <a class="link-underline resendOtp-js disabled" href="javascript:void(0);" onClick="resendOtp(<?php echo $userId; ?>, <?php echo applicationConstants::YES; ?>)"><?php echo Labels::getLabel('LBL_RESEND_OTP?', $siteLangId); ?></a>
            </div>
        </div>
    </div>
</div>