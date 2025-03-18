<?php defined('SYSTEM_INIT') or die('Invalid Usage.');
$frm->setFormTagAttribute('id', 'profileInfoFrm');
$frm->setFormTagAttribute('class', 'form');
$frm->developerTags['colClassPrefix'] = 'col-md-';
$frm->developerTags['fld_default_col'] = 6;

$submitFld = $frm->getField('btn_submit');
$submitFld->developerTags['col'] = 12;
$submitFld->developerTags['noCaptionTag'] = (User::isAffiliate()) ? false : true;
$submitFld->setFieldTagAttribute('class', "btn btn-brand");

HtmlHelper::formatFormFields($frm, 6);


$frm->setFormTagAttribute('onsubmit', 'updateProfileInfo(this); return(false);');

$usernameFld = $frm->getField('credential_username');
$usernameFld->setFieldTagAttribute('disabled', 'disabled');

$phoneFld = $frm->getField('user_phone');
if (true == SmsArchive::canSendSms()) {
    $phoneFld->setFieldTagAttribute('disabled', 'disabled');
}
if (!empty($data['user_phone'])) {
    $phoneFld->setFieldTagAttribute('disabled', 'disabled');
    $phoneFld->setFieldTagAttribute('id', 'user-phone');
    $phoneFld->setFieldTagAttribute('data-value', $data['user_phone']);
    HtmlHelper::setFieldEncryptedValue($phoneFld, CommonHelper::displayEncryptedFieldData($data['user_phone']));
    $handleDisabled = (false == SmsArchive::canSendSms()) ? 1 : 0;
    $phoneFld->htmlAfterField = '<span toggle="#user-phone" onclick ="toggleEncryptedFields(this, ' . $handleDisabled . ', 1)" class="icn-eye fa js-toggle-data fa-eye"></span>';
}


$userDobFld = $frm->getField('user_dob');
if (!empty($data['user_dob']) && $data['user_dob'] != '0000-00-00') {
    $userDobFld->setFieldTagAttribute('disabled', 'disabled');
    HtmlHelper::setFieldEncryptedValue($userDobFld, CommonHelper::displayEncryptedDob($data['user_dob']));
    $userDobFld->setFieldTagAttribute('id', 'user-dob');
    $userDobFld->setFieldTagAttribute('data-value', $data['user_dob']);
    $userDobFld->htmlAfterField = '<span toggle="#user-dob" onclick ="toggleEncryptedFields(this)" class="icn-eye fa js-toggle-data fa-eye"></span>';
}

$userDobFld->setFieldTagAttribute('class', 'user_dob_js');

$emailFld = $frm->getField('credential_email');
$emailFld->setFieldTagAttribute('disabled', 'disabled');
$emailFld->setFieldTagAttribute('id', 'user-email');
$emailFld->setFieldTagAttribute('data-value', $data['credential_email']);
HtmlHelper::setFieldEncryptedValue($emailFld, CommonHelper::displayEncryptedEmail($data['credential_email']));
$emailFld->htmlAfterField = '<span toggle="#user-email" onclick ="toggleEncryptedFields(this)" class="icn-eye fa js-toggle-data fa-eye"></span>';


$countryFld = $frm->getField('user_country_id');
$countryFld->setFieldTagAttribute('id', 'user_country_id');
$countryFld->setFieldTagAttribute('onChange', 'getCountryStates(this.value,' . $stateId . ',\'#user_state_id\')');

$stateFld = $frm->getField('user_state_id');
$stateFld->setFieldTagAttribute('id', 'user_state_id');



$parent = User::getAttributesById(UserAuthentication::getLoggedUserId(true), 'user_parent');
if (User::isAdvertiser() && $parent == 0) {
    $fld = $frm->getField('user_profile_info');
    $fld->developerTags['colWidthValues'] = [null, '6', null, null];

    $fld = $frm->getField('user_products_services');
    $fld->developerTags['colWidthValues'] = [null, '6', null, null];

    $fld = $frm->getField('user_company');
    $fld->developerTags['colWidthValues'] = [null, '12', null, null];
}


$imgFrm->setFormTagAttribute('action', UrlHelper::generateUrl('Account', 'uploadProfileImage'));
?>
<div class="row justify-content-center">
    <div class="col-lg-12">
        <div class="profile-image" id="profileImageFrmBlock">
            <div class="avatar">
                <?php
                $userId = UserAuthentication::getLoggedUserId();
                $userImgUpdatedOn = User::getAttributesById($userId, 'user_updated_on');
                $uploadedTime = AttachedFile::setTimeParam($userImgUpdatedOn);
                $file_row = AttachedFile::getAttachment(AttachedFile::FILETYPE_USER_PROFILE_IMAGE, $userId);

                $profileImg = UrlHelper::getCachedUrl(UrlHelper::generateFileUrl('Image', 'user', array($userId, ImageDimension::VIEW_THUMB, true), CONF_WEBROOT_FRONTEND) . $uploadedTime, CONF_IMG_CACHE_TIME, '.jpg');
                ?>
                <img src="<?php echo $profileImg; ?>" alt="<?php echo Labels::getLabel('LBL_Profile_Image', $siteLangId); ?>">
                <?php echo $imgFrm->getFormTag(); ?>
                <?php if ($mode == 'Edit') { ?>
                    <label class="btn btn-delete" title="<?php echo Labels::getLabel('LBL_REMOVE_IMAGE', $siteLangId); ?>" onclick="removeProfileImage()">                       
                        <svg class="svg" width="14" height="14">
                            <use xlink:href="<?php echo CONF_WEBROOT_URL; ?>images/retina/sprite-actions.svg<?php echo AttachedFile::setTimeParam(RELEASE_DATE); ?>#delete">
                            </use>
                        </svg>
                    </label>  
                    <?php } ?>
                    <label class="btn btn-edit" title="<?php echo Labels::getLabel('LBL_UPLOAD_IMAGE_FILE', $siteLangId); ?>">
                        <input type="file" class="sr-only" id="profileInputImage" name="file" accept="image/*" onChange="popupImage(this)">
                        <svg class="svg" width="14" height="14">
                            <use xlink:href="<?php echo CONF_WEBROOT_URL; ?>images/retina/sprite-actions.svg<?php echo AttachedFile::setTimeParam(RELEASE_DATE); ?>#edit">
                            </use>
                        </svg>
                    </label>
                
                </form>
                <?php echo $imgFrm->getExternalJS(); ?>
                <div id="dispMessage"></div>
            </div>
        </div>
        <?php if (User::isBuyer() && User::isSeller()) { ?>
            <div class="my-5">
                <h6> <?php echo Labels::getLabel('LBL_Preferred_Dashboard', $siteLangId); ?> </h6>
                <ul class="user-type setactive-js">
                    <?php if (User::canViewBuyerTab() && (User::canViewSupplierTab() || User::canViewAdvertiserTab() || User::canViewAffiliateTab())) { ?>
                        <li <?php echo (User::USER_BUYER_DASHBOARD == $data['user_preferred_dashboard']) ? 'class="is-active"' : '' ?>>
                            <button class="user-type-link" type="button" href="javascript:void(0)" onclick="setPreferredDashboad(<?php echo User::USER_BUYER_DASHBOARD; ?>)">
                                <svg class="svg" width="14" height="14">
                                    <use xlink:href="<?php echo CONF_WEBROOT_URL; ?>images/retina/sprite-actions.svg<?php echo AttachedFile::setTimeParam(RELEASE_DATE); ?>#tick">
                                    </use>
                                </svg>
                                <?php echo Labels::getLabel('LBL_Buyer', $siteLangId); ?>
                            </button>
                        </li>
                    <?php } ?>
                    <?php if (User::canViewSupplierTab() && (User::canViewBuyerTab() || User::canViewAdvertiserTab() || User::canViewAffiliateTab())) { ?>
                        <li <?php echo (User::USER_SELLER_DASHBOARD == $data['user_preferred_dashboard']) ? 'class="is-active"' : '' ?>>
                            <button class="user-type-link" type="button" href="javascript:void(0)" onclick="setPreferredDashboad(<?php echo User::USER_SELLER_DASHBOARD; ?>)">
                                <svg class="svg" width="14" height="14">
                                    <use xlink:href="<?php echo CONF_WEBROOT_URL; ?>images/retina/sprite-actions.svg<?php echo AttachedFile::setTimeParam(RELEASE_DATE); ?>#tick">
                                    </use>
                                </svg>
                                <?php echo Labels::getLabel('LBL_Seller', $siteLangId); ?></button>
                        </li>
                    <?php } ?>
                </ul>

            </div>

        <?php }

        echo $frm->getFormHtml(); ?>

        <div class="or"><span><?php echo Labels::getLabel('LBL_MORE_OPTIONS', $siteLangId); ?></span></div>

        <div class="account-delete">
            <button type="button" class="btn btn-light" onclick="truncateDataRequestPopup()">
                <?php echo Labels::getLabel('LBL_REQUEST_TO_REMOVE_MY_DATA', $siteLangId); ?>
            </button>
            <button type="button" class="btn btn-light" onclick="requestData()">
                <?php echo Labels::getLabel('LBL_REQUEST_MY_DATA', $siteLangId); ?>
            </button>
        </div>
    </div>
</div>
<script language="javascript">
    var cropperHeading = '<?php echo Labels::getLabel('LBL_PROFILE_IMAGE', $siteLangId); ?>';
    $(document).ready(function() {
        getCountryStates($("#user_country_id").val(), <?php echo $stateId; ?>, '#user_state_id');
        $('.user_dob_js').datepicker('option', {
            maxDate: new Date()
        });

        toggleEncryptedFields = function(element, handleDisabled = 0, handleValidations = 0) {
            $(element).toggleClass("fa-eye fa-eye-slash");
            var input = $($(element).attr("toggle"));
            if ($(element).hasClass('fa-eye')) {
                input.val(input.attr('data-value'));
                if (handleDisabled == 1) {
                    input.removeAttr('disabled');
                }
                if (handleValidations == 1) {
                    input.attr('data-fatreq', input.attr('data-validations'));
                }
            } else {
                input.val(input.attr('data-encrypted-value'));
                if (handleDisabled == 1) {
                    input.attr('disabled', 'disabled');
                }
                if (handleValidations == 1) {
                    var validations = input.attr('data-fatreq');
                    input.attr('data-validations', validations);
                    input.attr('data-fatreq', '');
                }
            }
        }

        $('.js-toggle-data').trigger('click');
    });
</script>
<?php
if (isset($countryIso) && !empty($countryIso)) { ?>
    <script>
        langLbl.defaultCountryCode = '<?php echo $countryIso; ?>';
    </script>
<?php } ?>