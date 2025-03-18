<?php
defined('SYSTEM_INIT') or die('Invalid Usage.');

$userNameFld = $frm->getField('admin_username');
$userNameFld->addFieldTagAttribute('id', 'admin_username');

$emailFld = $frm->getField('admin_email');
$emailFld->addFieldTagAttribute('id', 'admin_email');

$nameFld = $frm->getField('admin_name');

$frm->setFormTagAttribute('id', 'profileInfoFrm');
$frm->setFormTagAttribute('class', 'form');
$frm->developerTags['fld_default_col'] = 6;
$frm->setFormTagAttribute('onsubmit', 'updateProfileInfo(this); return(false);');

$imageFld = $frm->getField('user_profile_image');
$imageFld->addFieldTagAttribute('onChange', 'popupImage(this)');
$imageFld->addFieldTagAttribute('class', 'file-upload');

$imageFld->addFieldTagAttribute('accept', 'image/*');
$imageProfileDimensions = ImageDimension::getData(ImageDimension::TYPE_USER_PROFILE_IMAGE, ImageDimension::VIEW_CROPED);
$profileImg  = UrlHelper::generateFileUrl('Image', 'profileImage', array(AdminAuthentication::getLoggedAdminId(), 'croped', true));
?>
<div class="card">
    <div class="card-head">
        <div class="card-head-label">
            <h3 class="card-head-title"><?php echo Labels::getLabel('LBL_MY_PROFILE', $siteLangId); ?></h3>
        </div>
    </div>
    <?php echo $frm->getFormTag(); ?>
    <div class="card-body">
        <!--begin::Input group-->
        <div class="row form-group justify-content-center">
            <div class="col-lg-3 text-center">
                <!--begin::Image input-->
                <div class="avatar avatar-outline avatar-circle" id="user_avatar_3">
                    <div data-aspect-ratio="<?php echo $imageProfileDimensions[ImageDimension::VIEW_CROPED]['aspectRatio']; ?>" class="avatar__holder" style="background-image: url('<?php echo $profileImg . "t=?" . time(); ?>')">
                    </div>
                    <label class="avatar__upload" data-bs-toggle="tooltip" title="" data-original-title="<?php echo Labels::getLabel('LBL_EDIT_IMAGE', $siteLangId); ?>">
                        <svg class="svg" width="12" height="12">
                            <use xlink:href="<?php echo CONF_WEBROOT_URL; ?>images/retina/sprite-actions.svg<?php echo AttachedFile::setTimeParam(RELEASE_DATE); ?>#edit">
                            </use>
                        </svg>
                        <?php echo $imageFld->getHTML(); ?>
                    </label>

                    <?php if (!$isNewImage) { ?>
                        <label class="avatar__cancel" data-bs-toggle="tooltip" title="" data-original-title="<?php echo Labels::getLabel('LBL_REMOVE_IMAGE', $siteLangId); ?>" onclick="removeProfileImage();">
                            <svg class="svg" width="12" height="12">
                                <use xlink:href="<?php echo CONF_WEBROOT_URL; ?>images/retina/sprite-actions.svg<?php echo AttachedFile::setTimeParam(RELEASE_DATE); ?>#delete">
                                </use>
                            </svg>
                        </label>
                    <?php } ?>

                </div>
            </div>
            <!--end::Col-->
        </div>
        <div class="row">
            <div class="col-md-6">
                <div class="form-group">
                    <label class="label <?php echo $userNameFld->requirements()->isRequired() ? 'required ' : '' ?>"><?php echo $userNameFld->getCaption(); ?></label>
                    <?php echo $userNameFld->getHTML(); ?>
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group">
                    <label class="label <?php echo $emailFld->requirements()->isRequired() ? 'required ' : '' ?>"><?php echo $emailFld->getCaption(); ?></label>
                    <?php echo $emailFld->getHTML(); ?>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-md-6">
                <div class="form-group">
                    <label class="label <?php echo $nameFld->requirements()->isRequired() ? 'required ' : '' ?>"><?php echo $nameFld->getCaption(); ?></label>
                    <?php echo $nameFld->getHTML(); ?>
                </div>
            </div>
        </div>
    </div>
    <div class="card-foot">
        <div class="row">
            <div class="col"> </div>
            <div class="col-auto">
                <button type="submit" class="btn btn-brand gb-btn gb-btn-primary"><?php echo Labels::getLabel('LBL_UPDATE', $siteLangId); ?></button>
            </div>
        </div>
    </div>
    <?php echo $frm->getFieldHTML('admin_id'); ?>
    </form>
    <?php echo $frm->getExternalJS(); ?>
</div>