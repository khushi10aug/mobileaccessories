<?php
defined('SYSTEM_INIT') or die('Invalid Usage.');
$frm->setFormTagAttribute('onsubmit', 'login(this, loginValidator); return(false);');
$frm->setFormTagAttribute('id', 'adminLoginForm');
$frm->setFormTagAttribute('class', 'form form-login');
$frm->setRequiredStarPosition('none');
$frm->setRequiredStarWith('none');
$frm->setJsErrorDisplay(FORM::FORM_ERROR_TYPE_AFTER_FIELD);
$frm->setValidatorJsObjectName('loginValidator');

$userNameFld = $frm->getField('username');
$userNameFld->addFieldTagAttribute('title', $userNameFld->getCaption());
$userNameFld->addFieldTagAttribute('autocomplete', 'no');

$passwordFld = $frm->getField('password');
$passwordFld->addFieldTagAttribute('title', $passwordFld->getCaption());
$passwordFld->addFieldTagAttribute('autocomplete', 'no');
$passwordFld->addFieldTagAttribute('id', 'password');

$fld = $frm->getField('rememberme');
$fld->addFieldTagAttribute('class', 'rememberFldJs');
?>
<div id="particles-js"></div>
<div class="login-page login-1">
    <div class="container">
        <div class="login-block">
            <?php
            $imgDataType = '';
            $logoWidth = '';
            $fileData = AttachedFile::getAttachment(AttachedFile::FILETYPE_ADMIN_LOGO, 0, 0, $siteLangId, false);
            $uploadedTime = AttachedFile::setTimeParam($fileData['afile_updated_at']);
            if (AttachedFile::FILE_ATTACHMENT_TYPE_SVG == $fileData['afile_attachment_type']) {
                $imgUrl = UrlHelper::getStaticImageUrl($fileData['afile_physical_path']) . $uploadedTime;
                $imgDataType = 'data-type="svg"';
                $logoWidth = 'width="120"';
            } else {
                $aspectRatioArr = AttachedFile::getRatioTypeArray($siteLangId);
                $imgUrl = UrlHelper::getCachedUrl(UrlHelper::generateFileUrl('Image', 'siteAdminLogo', array($siteLangId)) . $uploadedTime, CONF_IMG_CACHE_TIME, '.jpg');
            }
            ?>
            <div class="logo" <?php echo $imgDataType; ?>>
                <a href="<?php echo UrlHelper::generateUrl(); ?>">
                    <img <?php if (AttachedFile::FILE_ATTACHMENT_TYPE_OTHER == $fileData['afile_attachment_type'] && $fileData['afile_aspect_ratio'] > 0) { ?> data-ratio="<?php echo $aspectRatioArr[$fileData['afile_aspect_ratio']]; ?>" <?php } ?> title="<?php echo FatApp::getConfig("CONF_WEBSITE_NAME_" . $siteLangId); ?>" src="<?php echo $imgUrl; ?>" alt="<?php echo FatApp::getConfig("CONF_WEBSITE_NAME_" . $siteLangId); ?>" <?php echo $logoWidth; ?>>
                </a>
            </div>
            <div class="card">
                <div class="card-head">
                    <div class="title">
                        <h2><?php echo Labels::getlabel('FRM_SIGN_IN', $siteLangId); ?></h2>
                        <p class="text-muted"><?php echo Labels::getlabel('MSG_PLEASE_ENTER_YOUR_LOGIN_CREDENTIALS', $siteLangId); ?> </p>
                    </div>
                </div>
                <div class="card-body">
                    <?php echo $frm->getFormTag(); ?>
                    <div class="form-group">
                        <label class="label"><?php echo $userNameFld->getCaption() ?></label>
                        <?php echo $userNameFld->getHTML('username'); ?>
                    </div>
                    <div class="form-group">
                        <label class="label"><?php echo $passwordFld->getCaption() ?></label>
                        <div class="input-group">
                            <?php echo $passwordFld->getHTML('password'); ?>
                            <div class="input-group-append">
                                <span class="input-group-text field-password" id="showPass"></span>
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="switch switch-sm switch-icon remember-me">
                            <?php echo $frm->getFieldHTML('rememberme'); ?>
                            <span class="input-helper"></span><?php echo Labels::getlabel('FRM_REMEMBER_ME', $siteLangId); ?>
                        </label>
                    </div>
                    <div class="form-group">
                        <?php echo $frm->getFieldHTML('btn_submit'); ?>
                    </div>

                    <?php echo $frm->getExternalJS(); ?>
                    </form>
                </div>
                <div class="card-foot">
                    <ul class="other-links">
                        <li><a href="<?php echo UrlHelper::generateUrl('adminGuest', 'forgotPasswordForm'); ?>" class="link"><?php echo Labels::getLabel('LBL_FORGOT_PASSWORD?', $siteLangId); ?></a></li>
                    </ul>
                </div>
            </div>
            <p class="version"><?php $this->includeTemplate('_partial/footer/copyright-text.php', $this->variables, false); ?></p>
        </div>
    </div>
    <script>
        var hideTxt = '<?php echo Labels::getlabel('FRM_HIDE', $siteLangId); ?>';
        var showTxt = '<?php echo Labels::getlabel('FRM_SHOW', $siteLangId); ?>';
    </script>