<?php defined('SYSTEM_INIT') or die('Invalid Usage.');

$frm->setFormTagAttribute('onsubmit', 'resetPassword(this); return false;');
$frm->setFormTagAttribute('class', 'form form-login');
$frm->setRequiredStarPosition('none');
$frm->setValidatorJsObjectName('resetValidator');

$fld = $frm->getField('btn_reset');
$fld->addFieldTagAttribute('class', 'btn btn-brand btn-lg btn-block');

$newPwd = $frm->getField('new_pwd');
$newPwd->setFieldTagAttribute('title', $newPwd->getCaption());
$newPwd->setFieldTagAttribute('autocomplete', 'no');
$newPwd->setFieldTagAttribute('class', 'passwordFieldJs');
$newPwd->requirements()->setLength(4, 20);
$newPwd->setRequiredStarWith('none');

$confirmPwd = $frm->getField('confirm_pwd');
$confirmPwd->setFieldTagAttribute('title', $confirmPwd->getCaption());
$confirmPwd->setFieldTagAttribute('autocomplete', 'no');
$confirmPwd->setFieldTagAttribute('class', 'passwordFieldJs');
$confirmPwd->setFieldTagAttribute('id', 'password');
$confirmPwd->setRequiredStarWith('none');
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
                    <img <?php if (AttachedFile::FILE_ATTACHMENT_TYPE_OTHER == $fileData['afile_attachment_type'] && $fileData['afile_aspect_ratio'] > 0) { ?> data-ratio="<?php echo $aspectRatioArr[$fileData['afile_aspect_ratio']]; ?>" <?php } ?> title="<?php echo FatApp::getConfig("CONF_WEBSITE_NAME_" . $siteLangId); ?>" src="<?php echo $imgUrl; ?>" alt="<?php echo FatApp::getConfig("CONF_WEBSITE_NAME_" . $siteLangId); ?>" <?php $logoWidth; ?>>
                </a>
            </div>

            <div class="card">
                <div class="card-head">
                    <div class="title">
                        <h2><?php echo Labels::getLabel('LBL_RESET_PASSWORD', CommonHelper::getLangId()); ?></h2>
                        <p class="text-muted"><?php echo Labels::getLabel('LBL_Enter_The_E-mail_Address_Associated_With_Your_Account', $siteLangId) ?></p>
                    </div>
                </div>
                <div class="card-body">
                    <?php echo $frm->getFormTag(); ?>
                    <div class="form-group">
                        <label class="label"><?php echo $newPwd->getCaption() ?></label>
                        <div class="input-group passwordSectionJs">
                            <?php echo $newPwd->getHTML('new_pwd'); ?>
                            <div class="input-group-append">
                                <span class="input-group-text field-password showPassJs"></span>
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="label"><?php echo $confirmPwd->getCaption() ?></label>
                        <div class="input-group passwordSectionJs">
                            <?php echo $confirmPwd->getHTML('confirm_pwd'); ?>
                            <div class="input-group-append">
                                <span class="input-group-text field-password showPassJs"></span>
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <?php
                        echo $frm->getFieldHTML('apr_id');
                        echo $frm->getFieldHTML('token');
                        echo $frm->getFieldHTML('btn_reset');
                        ?>
                    </div>

                    <?php echo $frm->getExternalJS(); ?>
                    </form>
                </div>
                <div class="card-foot">
                    <ul class="other-links">
                        <li>
                            <a href="<?php echo UrlHelper::generateUrl('adminGuest', 'loginForm'); ?>" class="link"><?php echo Labels::getLabel('LBL_Back_to_Login', $siteLangId); ?></a>
                        </li>
                    </ul>
                </div>
            </div>
            <p class="version"><?php $this->includeTemplate('_partial/footer/copyright-text.php', $this->variables, false); ?></p>
        </div>
    </div>
</div>