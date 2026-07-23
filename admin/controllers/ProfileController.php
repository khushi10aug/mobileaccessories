<?php

class ProfileController extends ListingBaseController
{
    public $_adminId = 0;
    public function __construct($action)
    {
        parent::__construct($action);
        if (0 == $this->_adminId) {
            $this->_adminId = AdminAuthentication::getLoggedAdminId();
        }
        $this->_adminProfileObj = new AdminUsers($this->_adminId);
    }

    public function index($tab = '')
    {
        $adminDetails = AdminUsers::getAttributesById($this->_adminId);
        $pageData = PageLanguageData::getAttributesByKey('MANAGE_PROFILE', $this->siteLangId);
        $pageTitle = $pageData['plang_title'] ?? LibHelper::getControllerName(true);
        $this->set('pageTitle', $pageTitle);
        $this->_template->addCss('css/cropper.css');
        $this->_template->addJs('js/cropper.js');
        $this->_template->addJs('js/cropper-main.js');
        $this->set('adminDetails', $adminDetails);
        $this->set('tab', $tab);
        $this->_template->render();
    }

    public function imgCropper()
    {
        //$this->set('image', UrlHelper::generateFullUrl('Image', 'profileImage', array($this->_adminId)));
        $this->_template->render(false, false, 'cropper/index.php');
    }

    public function profileInfoForm()
    {
        $imgFrm = $this->getImageForm();
        $adminRow = AdminUsers::getAttributesById($this->_adminId);
        $frm = $this->getProfileInfoForm();
        $frm->fill($adminRow);

        $isNewImage = true;
        $fileRow = AttachedFile::getAttachment(AttachedFile::FILETYPE_ADMIN_PROFILE_IMAGE, $this->_adminId);
        if ($fileRow != false  &&  0 < $fileRow['afile_id']) {
            $isNewImage = false;
        }
        $this->set('isNewImage', $isNewImage);
        $this->set('frm', $frm);
        $this->set('html', $this->_template->render(false, false, NULL, true));
        $this->_template->render(false, false, 'json-success.php', true, false);
    }

    public function updateProfileInfo()
    {
        $frm = $this->getProfileInfoForm();
        $post = FatApp::getPostedData();
        $post = $frm->getFormDataFromArray($post);

        if (false === $post) {
            LibHelper::exitWithError(current($frm->getValidationErrors()), true);
        }
        unset($post['admin_id']);

        $this->_adminProfileObj->assignValues($post);
        if (!$this->_adminProfileObj->save()) {
            LibHelper::exitWithError($this->_adminProfileObj->getError(), true);
        }

        $_SESSION[AdminAuthentication::SESSION_ELEMENT_NAME]['admin_name'] = $post['admin_name'];
        $_SESSION[AdminAuthentication::SESSION_ELEMENT_NAME]['admin_email'] = $post['admin_email'];
        $_SESSION[AdminAuthentication::SESSION_ELEMENT_NAME]['admin_username'] = $post['admin_username'];

        $this->set('msg', $this->str_setup_successful);
        $this->_template->render(false, false, 'json-success.php');
    }

    private function getProfileInfoForm()
    {
        $frm = new Form('frmProfileInfo');
        $frm->addFileUpload(Labels::getLabel('FRM_PROFILE_PICTURE', $this->siteLangId), 'user_profile_image');
        $fld = $frm->addRequiredField(Labels::getLabel('FRM_USERNAME', $this->siteLangId), 'admin_username');
        $fld->setUnique('tbl_admin', 'admin_username', 'admin_id', 'admin_id', 'admin_id');

        $fld = $frm->addRequiredField(Labels::getLabel('FRM_EMAIL', $this->siteLangId), 'admin_email');
        $fld->setUnique('tbl_admin', 'admin_email', 'admin_id', 'admin_id', 'admin_id');
        $frm->addHiddenField('', 'admin_id', '', array('id' => 'admin_id'));
        $frm->addRequiredField(Labels::getLabel('FRM_FULL_NAME', $this->siteLangId), 'admin_name');
        $frm->addSubmitButton('', 'btn_submit', Labels::getLabel('BTN_SAVE_CHANGES', $this->siteLangId));

        return $frm;
    }

    private function getImageForm()
    {
        $frm = new Form('frmProfile', array('id' => 'frmProfile'));
        $frm->addFileUpload(Labels::getLabel('FRM_Profile_Picture', $this->siteLangId), 'user_profile_image', array('onChange' => 'popupImage(this)', 'accept' => 'image/*'));
        $frm->addHiddenField('', 'update_profile_img', Labels::getLabel('FRM_Update_Profile_Picture', $this->siteLangId), array('id' => 'update_profile_img'));
        $frm->addHiddenField('', 'rotate_left', Labels::getLabel('FRM_Rotate_Left', $this->siteLangId), array('id' => 'rotate_left'));
        $frm->addHiddenField('', 'rotate_right', Labels::getLabel('FRM_Rotate_Right', $this->siteLangId), array('id' => 'rotate_right'));
        $frm->addHiddenField('', 'remove_profile_img', 0, array('id' => 'remove_profile_img'));
        $frm->addHiddenField('', 'action', 'avatar', array('id' => 'avatar-action'));
        $frm->addHiddenField('', 'img_data', '', array('id' => 'img_data'));
        return $frm;
    }

    public function uploadProfileImage()
    {
        $post = FatApp::getPostedData();
        if (empty($post)) {
            LibHelper::exitWithError(Labels::getLabel('ERR_Invalid_Request_Or_File_not_supported', $this->siteLangId), true);
        }
        if (isset($_FILES['org_image']['tmp_name'])) {
            if (!is_uploaded_file($_FILES['org_image']['tmp_name'])) {
                LibHelper::exitWithError(Labels::getLabel('ERR_Please_select_a_file', $this->siteLangId), true);
            }

            $fileHandlerObj = new AttachedFile();

            if (!$res = $fileHandlerObj->saveImage($_FILES['org_image']['tmp_name'], AttachedFile::FILETYPE_ADMIN_PROFILE_IMAGE, $this->_adminId, 0, $_FILES['org_image']['name'], -1, true)) {
                LibHelper::exitWithError($fileHandlerObj->getError(), true);
            }
            $this->set('file', UrlHelper::generateFullUrl('Image', 'profileImage', array($this->_adminId)));
        }

        if (isset($_FILES['cropped_image']['tmp_name'])) {
            if (!is_uploaded_file($_FILES['cropped_image']['tmp_name'])) {
                LibHelper::exitWithError(Labels::getLabel('ERR_Please_select_a_file', $this->siteLangId), true);
            }

            $fileHandlerObj = new AttachedFile();

            if (!$res = $fileHandlerObj->saveImage($_FILES['cropped_image']['tmp_name'], AttachedFile::FILETYPE_ADMIN_PROFILE_CROPED_IMAGE, $this->_adminId, 0, $_FILES['cropped_image']['name'], -1, true)) {
                LibHelper::exitWithError($fileHandlerObj->getError(), true);
            }

            /*$data = json_decode(stripslashes($post['img_data']));
            CommonHelper::crop($data, CONF_UPLOADS_PATH .$res, $this->siteLangId);*/
            $this->set('file', UrlHelper::generateFullUrl('Account', 'userProfileImage', array($this->_adminId, ImageDimension::VIEW_CROPED, true)));
        }

        $_SESSION[AdminAuthentication::SESSION_ELEMENT_NAME]['admin_updated_on'] = time();

        $this->set('msg', Labels::getLabel('MSG_FILE_UPLOADED_SUCCESSFULLY', $this->siteLangId));
        $this->_template->render(false, false, 'json-success.php');
    }

    public function removeProfileImage()
    {
        $fileHandlerObj = new AttachedFile();
        if (!$fileHandlerObj->deleteFile(AttachedFile::FILETYPE_ADMIN_PROFILE_IMAGE, $this->_adminId)) {
            LibHelper::exitWithError($fileHandlerObj->getError(), true);
        }

        if (!$fileHandlerObj->deleteFile(AttachedFile::FILETYPE_ADMIN_PROFILE_CROPED_IMAGE, $this->_adminId)) {
            LibHelper::exitWithError($fileHandlerObj->getError(), true);
        }

        $_SESSION[AdminAuthentication::SESSION_ELEMENT_NAME]['admin_updated_on'] = time();

        $this->set('msg', Labels::getLabel('MSG_FILE_DELETED_SUCCESSFULLY', $this->siteLangId));
        $this->_template->render(false, false, 'json-success.php');
    }

    public function getBreadcrumbNodes($action)
    {
        switch ($action) {
            case 'index':
                $this->nodes = [
                    ['title' => Labels::getLabel('LBL_PROFILE', $this->siteLangId)]
                ];
                break;
            default:
                parent::getBreadcrumbNodes($action);
                break;
        }
        return $this->nodes;
    }

    public function changePassword()
    {
        $this->set('frm', $this->getPwdFrm());
        $this->set('html', $this->_template->render(false, false, NULL, true));
        $this->_template->render(false, false, 'json-success.php', true, false);
    }

    public function updatePassword()
    {
        $pwdFrm = $this->getPwdFrm();
        $post = $pwdFrm->getFormDataFromArray(FatApp::getPostedData());
        if (!$pwdFrm->validate($post)) {
            LibHelper::exitWithError(current($pwdFrm->getValidationErrors()), true);
        }

        /* Restrict to change password for admin on demo URL. */
        if (CommonHelper::demoUrl() && 1 > $this->_adminId) {
            LibHelper::exitWithError(Labels::getLabel('ERR_YOU_ARE_NOT_ALLOWED_TO_CHANGE_PASSWORD_FOR_DEMO', $this->siteLangId), true);
        }

        if (!$adminCredentials = AdminUsers::getAttributesById($this->_adminId, ['admin_password', 'admin_password_old'])) {
            LibHelper::exitWithError($this->str_invalid_request, true);
        }

        if (!empty($adminCredentials['admin_password'])) {
            if (false == password_verify(FatApp::getPostedData('current_password'), $adminCredentials['admin_password'])) {
                LibHelper::exitWithError(Labels::getLabel('ERR_Your_current_Password_mis-matched!', $this->siteLangId), true);
            }
        } else {
            $currentEncPassword = UserAuthentication::encryptPassword(FatApp::getPostedData('current_password'), true);
            if ($currentEncPassword !== $adminCredentials['admin_password_old']) {
                LibHelper::exitWithError(Labels::getLabel('ERR_Your_current_Password_mis-matched!', $this->siteLangId), true);
            }
        }
        $newPassword = UserAuthentication::encryptPassword(FatApp::getPostedData('new_password'));

        $data = array('admin_password' => $newPassword, 'admin_password_old' => '');

        $this->_adminProfileObj->assignValues($data);
        if (!$this->_adminProfileObj->save()) {
            LibHelper::exitWithError($this->_adminProfileObj->getError(), true);
        }

        $this->set('msg', Labels::getLabel('MSG_PASSWORD_UPDATED_SUCCESSFULLY', $this->siteLangId));
        $this->_template->render(false, false, 'json-success.php');
    }

    public function logout()
    {
        AdminLoginHistory::logLogout(AdminAuthentication::getLoggedAdminId());
        AdminLoginHistory::clearLoginHistoryCookie();
        AdminAuthentication::clearLoggedAdminLoginCookie();
        session_destroy();
        Message::addMessage(Labels::getLabel('MSG_YOU_ARE_LOGGED_OUT_SUCCESSFULLY', $this->siteLangId));
        FatApplication::redirectUser(UrlHelper::generateUrl('adminGuest', 'loginForm'));
    }

    public function themeSetup()
    {
        $post = FatApp::getPostedData();
        $session_element_name = AdminAuthentication::SESSION_ELEMENT_NAME;
        $cookie_name = $session_element_name . 'layout';
        if (setcookie($cookie_name, $post['layout'], time() + 86400 * 30, CONF_WEBROOT_FRONT_URL)) {
            LibHelper::exitWithError(Labels::getLabel('ERR_Setting_Updated_Successfully', $this->siteLangId), true);
        } else {
            LibHelper::exitWithError($this->str_invalid_request, true);
        }
    }

    private function getPwdFrm()
    {
        $frm = new Form('getPwdFrm');
        $frm->setFormTagAttribute('action', UrlHelper::generateUrl('profile', 'updatePassword'));
        $frm->setFormTagAttribute('method', 'post');
        $frm->setFormTagAttribute('id', 'getPwdFrm');

        $curPwd = $frm->addPasswordField(
            Labels::getLabel('FRM_CURRENT_PASSWORD', $this->siteLangId),
            'current_password',
            '',
            array('id' => 'current_password')
        );
        $curPwd->requirements()->setRequired();

        $newPwd = $frm->addPasswordField(
            Labels::getLabel('FRM_NEW_PASSWORD', $this->siteLangId),
            'new_password',
            '',
            array('id' => 'new_password')
        );
        $newPwd->requirements()->setRequired();

        $conNewPwd = $frm->addPasswordField(
            Labels::getLabel('FRM_CONFIRM_NEW_PASSWORD', $this->siteLangId),
            'conf_new_password',
            '',
            array('id' => 'conf_new_password')
        );
        $conNewPwdReq = $conNewPwd->requirements();
        $conNewPwdReq->setRequired();
        $conNewPwdReq->setCompareWith('new_password', 'eq');
        $conNewPwdReq->setCustomErrorMessage(Labels::getLabel('FRM_Confirm_Password_Not_Matched!', $this->siteLangId));

        $frm->addSubmitButton(Labels::getLabel('FRM_CHANGE', $this->siteLangId), 'btn_submit', Labels::getLabel('BTN_CHANGE', $this->siteLangId), array('id' => 'btn_submit'));
        return $frm;
    }
}
