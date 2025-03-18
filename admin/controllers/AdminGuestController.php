<?php

class AdminGuestController extends FatController
{
    public function __construct($action)
    {
        parent::__construct($action);

        if ($this->doCookieAdminLogin()) {
            FatApp::redirectUser(UrlHelper::generateUrl('home'));
        }

        CommonHelper::initCommonVariables(true);
        $this->siteLangId = CommonHelper::getLangId();

        if (AdminAuthentication::isAdminLogged()) {
            $msg = Labels::getLabel('MSG_You_are_already_logged_in', $this->siteLangId);
            $redirect =  FatApp::redirectUser(UrlHelper::generateUrl('home'));
            LibHelper::exitWithError($msg, true, $redirect);
        }

        $controllerName = get_class($this);
        $arr = explode('-', FatUtility::camel2dashed($controllerName));
        array_pop($arr);
        $controllerName = ucfirst(FatUtility::dashed2Camel(implode('-', $arr)));

        $jsVariables = array(
            'processing' => Labels::getLabel('LBL_PROCESSING...', $this->siteLangId),
            'isMandatory' => Labels::getLabel('LBL_IS_MANDATORY', $this->siteLangId),
            'pleaseEnterValidEmailId' => Labels::getLabel('VLBL_PLEASE_ENTER_VALID_EMAIL_ID_FOR', $this->siteLangId)
        );

        $this->set('isAdminLogged', AdminAuthentication::isAdminLogged());
        $this->set('controllerName', $controllerName);
        $this->set('jsVariables', $jsVariables);
        $this->set('siteLangId', $this->siteLangId);
        $this->set('bodyClass', '');
        $this->_template->addCss(CONF_MAIN_CSS_DIR_PATH . '/main-' . CommonHelper::getLayoutDirection() . '.css');
    }

    public function loginForm()
    {
        $this->set('frm', $this->getLoginForm());
        $this->_template->render();
    }

    public function forgotPasswordForm()
    {
        $this->set('frm', $this->getForgotForm());
        $this->_template->render();
    }

    public function login()
    {
        $frm = $this->getLoginForm();
        $post = $frm->getFormDataFromArray(FatApp::getPostedData());
        if (false == $post) {
            LibHelper::dieJsonError(current($frm->getValidationErrors()));
        }

        $adminAuthObj = AdminAuthentication::getInstance();
        if (!$adminAuthObj->login($post['username'], $post['password'], CommonHelper::getClientIp())) {
            LibHelper::dieJsonError($adminAuthObj->getError(), true);
        }

        if (FatUtility::int($post['rememberme']) == 1) {
            $this->setAdminLoginCookie();
        }

        /* Redirect to previous page[ */
        $redirectUrl = '';
        if (isset($_SESSION['admin_referer_page_url']) && (strpos(($_SESSION['admin_referer_page_url']), 'help-center') === false)) {
            $redirectUrl = $_SESSION['admin_referer_page_url'];
            unset($_SESSION['admin_referer_page_url']);
        }

        if ($redirectUrl == '') {
            $redirectUrl = UrlHelper::generateUrl('Home');
        }
        $this->set('redirectUrl', $redirectUrl);
        /* ] */

        $msg = Labels::getLabel('MSG_LOGIN_SUCCESSFUL', $this->siteLangId);
        Message::addMessage($msg);
        $this->set('msg', $msg);
        $this->_template->render(false, false, 'json-success.php');
    }

    public function sendResetPasswordLink($usernameOrEmail)
    {
        if (empty($usernameOrEmail)) {
            LibHelper::exitWithError($this->str_invalid_request, true);
        }
    
        $adminAuthObj = AdminAuthentication::getInstance();

        $admin = $adminAuthObj->checkAdminEmailOrUsername($usernameOrEmail);

        if (!$admin) {
            Message::addErrorMessage($adminAuthObj->getError());
            $this->set('msg', Message::getHtml());
            $this->_template->render(false, false, 'json-error.php', true, false);
        }

        $token = FatUtility::getRandomString(30);

        $data = array('admin_id' => $admin['admin_id'], 'token' => $token);
        $reset_url = UrlHelper::generateFullUrl('adminGuest', 'resetPwd', array($admin['admin_id'], $token));
        $adminAuthObj->deleteOldPasswordResetRequest($admin['admin_id']);
        if (!$adminAuthObj->addPasswordResetRequest($data)) {
            Message::addErrorMessage($adminAuthObj->getError());
            $this->set('msg', Message::getHtml());
            $this->_template->render(false, false, 'json-error.php', true, false);
        }
        $replacements = array(
            '{reset_url}' => $reset_url,
            '{site_domain}' => UrlHelper::generateFullUrl('', '', array(), CONF_WEBROOT_FRONTEND),
            '{user_full_name}' => trim($admin['admin_name']),
        );

        $sendEmail = (new FatMailer($this->siteLangId, 'admin_forgot_password'))
            ->setTo($admin['admin_email'])
            ->setVariables($replacements)
            ->send();
        if (!$sendEmail) {
            LibHelper::exitWithError(Labels::getLabel('ERR_UNABLE_TO_SEND_EMAIL', $this->siteLangId), true);
        }

        $emaiHandObj = new EmailHandler();
        $emaiHandObj->sendSms('admin_forgot_password', ValidateElement::formatDialCode(FatApp::getConfig('CONF_SITE_PHONE_dcode')) . FatApp::getConfig('CONF_SITE_PHONE'), $replacements, $this->siteLangId);

        $this->set('msg', Labels::getLabel('MSG_YOUR_PASSWORD_RESET_INSTRUCTIONS_TO_YOUR_EMAIL', $this->siteLangId));
        $this->_template->render(false, false, 'json-success.php', true, false);
    }

    public function forgotPassword()
    {
        $frm = $this->getForgotForm();
        $post = $frm->getFormDataFromArray(FatApp::getPostedData());
        if (false == $post) {
            LibHelper::exitWithError(current($frm->getValidationErrors()), true);
        }
        $adminEmail = FatApp::getPostedData('admin_email');
        if (FatApp::getConfig('CONF_RECAPTCHA_SITEKEY', FatUtility::VAR_STRING, '') && !CommonHelper::verifyCaptcha()) {
            LibHelper::exitWithError(Labels::getLabel('ERR_INCORRECT_SECURITY_CODE', $this->siteLangId), true);
        }

        $adminAuthObj = AdminAuthentication::getInstance();
        $admin = $adminAuthObj->checkAdminEmail($adminEmail);

        if (!$admin) {
            LibHelper::exitWithError($adminAuthObj->getError(), true);
        }
        if ($adminAuthObj->checkAdminPwdResetRequest($admin['admin_id'])) {
            LibHelper::exitWithError($adminAuthObj->getError(), true);
        }

        $token = FatUtility::getRandomString(30);

        $data = array('admin_id' => $admin['admin_id'], 'token' => $token);
        $reset_url = UrlHelper::generateFullUrl('adminGuest', 'resetPwd', array($admin['admin_id'], $token));
        $adminAuthObj->deleteOldPasswordResetRequest();
        if (!$adminAuthObj->addPasswordResetRequest($data)) {
            LibHelper::exitWithError($adminAuthObj->getError(), true);
        }
        $replacements = array(
            '{reset_url}' => $reset_url,
            '{site_domain}' => UrlHelper::generateFullUrl('', '', array(), CONF_WEBROOT_FRONTEND),
            '{user_full_name}' => trim($admin['admin_name']),
        );

        $sendEmail = (new FatMailer($this->siteLangId, 'admin_forgot_password'))
            ->setTo($admin['admin_email'])
            ->setVariables($replacements)
            ->send();

        if (false === $sendEmail) {
            LibHelper::exitWithError(Labels::getLabel('ERR_UNABLE_TO_SEND_EMAIL', $this->siteLangId), true);
        }
        $emaiHandObj = new EmailHandler();
        $emaiHandObj->sendSms('admin_forgot_password', ValidateElement::formatDialCode(FatApp::getConfig('CONF_SITE_PHONE_dcode')) . FatApp::getConfig('CONF_SITE_PHONE'), $replacements, $this->siteLangId);

        $this->set('msg', Labels::getLabel('MSG_YOUR_PASSWORD_RESET_INSTRUCTIONS_TO_YOUR_EMAIL', $this->siteLangId));
        $this->_template->render(false, false, 'json-success.php', true, false);
    }

    public function resetPwd($adminId = 0, $token = '')
    {
        /* die("We are currently working on this area..., for now, we have saved the sent email and token in table for this, but you cannot update the password for now <a href=".UrlHelper::generateFullUrl('','',array()).">Go to Admin Area</a>"); */
        $adminId = FatUtility::int($adminId);

        if ($adminId < 1 || strlen(trim($token)) < 20) {
            Message::addErrorMessage(Labels::getLabel('ERR_LINK_IS_INVALID_OR_EXPIRED', $this->siteLangId));
            FatApp::redirectUser(UrlHelper::generateUrl('adminGuest', 'loginForm'));
        }

        $adminAuthObj = AdminAuthentication::getInstance();

        if (!$adminAuthObj->checkResetLink($adminId, trim($token))) {
            Message::addErrorMessage($adminAuthObj->getError());
            FatApp::redirectUser(UrlHelper::generateUrl('adminGuest', 'loginForm'));
        }

        $frm = $this->getResetPwdForm($adminId, trim($token));

        $this->set('frm', $frm);
        $this->_template->render();
    }

    public function resetPasswordSubmit()
    {
        if (!FatUtility::isAjaxCall()) {
            LibHelper::exitWithError(Labels::getLabel('ERR_INVALID_REQUEST', $this->siteLangId), true);
        }

        $newPwd = FatApp::getPostedData('new_pwd');
        $confirmPwd = FatApp::getPostedData('confirm_pwd');
        $adminId = FatApp::getPostedData('apr_id', FatUtility::VAR_INT);
        $token = FatApp::getPostedData('token', FatUtility::VAR_STRING);

        if ($adminId < 1 || strlen(trim($token)) < 20) {
            Message::addErrorMessage(Labels::getLabel('ERR_REQUEST_IS_INVALID_OR_EXPIRED', $this->siteLangId));
            $this->set('msg', Message::getHtml());
            $this->_template->render(false, false, 'json-error.php', true, false);
        }
        $frm = $this->getResetPwdForm($adminId, $token);
        $post = $frm->getFormDataFromArray(FatApp::getPostedData());
        if (!$frm->validate($post)) {
            LibHelper::exitWithError($frm->getValidationErrors(), true);
        }
        
        $adminAuthObj = AdminAuthentication::getInstance();
        
        if (!$adminAuthObj->checkResetLink($adminId, trim($token))) {
            LibHelper::exitWithError($adminAuthObj->getError(), true);
        }
        $admin_row = $adminAuthObj->getAdminById($adminId);

        $pwd = UserAuthentication::encryptPassword($newPwd);

        if ($admin_row['admin_id'] != $adminId || !$adminAuthObj->changeAdminPwd($adminId, $pwd)) {
            Message::addErrorMessage(Labels::getLabel('ERR_INVALID_REQUEST', $this->siteLangId));
            $this->set('msg', Message::getHtml());
            $this->_template->render(false, false, 'json-error.php', true, false);
        }

        $arr_replacements = array(
            '{user_full_name}' => trim($admin_row['admin_name']),
            '{login_link}' => UrlHelper::generateFullUrl('adminGuest', 'loginForm', array())
        );
        
        (new FatMailer($this->siteLangId, 'user_admin_password_changed_successfully'))
            ->setTo($admin_row['admin_email'])
            ->setVariables($arr_replacements)
            ->send();

        if (!empty(FatApp::getConfig('CONF_SITE_PHONE'))) {
            $emaiHandObj = new EmailHandler();
            $emaiHandObj->sendSms('user_admin_password_changed_successfully', ValidateElement::formatDialCode(FatApp::getConfig('CONF_SITE_PHONE_dcode')) . FatApp::getConfig('CONF_SITE_PHONE'), $arr_replacements, $this->siteLangId);
        }

        $this->set('msg', Labels::getLabel('MSG_PASSWORD_CHANGED_SUCCESSFULLY', $this->siteLangId));
        $this->_template->render(false, false, 'json-success.php', true, false);
    }

    private function setAdminLoginCookie()
    {
        $admin_id = AdminAuthentication::getLoggedAdminId();

        if ($admin_id < 1) {
            return false;
        }
        $token = $this->generateLoginToken();
        $expiry = strtotime('+7 day');
        $values = array(
            'admauth_admin_id' => $admin_id,
            'admauth_token' => $token,
            'admauth_expiry' => date('Y-m-d H:i:s', $expiry),
            'admauth_browser' => $_SERVER['HTTP_USER_AGENT'],
            'admauth_last_access' => date('Y-m-d H:i:s'),
            'admauth_last_ip' => $_SERVER['REMOTE_ADDR'],
        );
        $adminAuthObj = AdminAuthentication::getInstance();
        if ($adminAuthObj->saveRememberLoginToken($values)) {
            $cookie_name = AdminAuthentication::ADMIN_REMEMBER_ME_COOKIE_NAME;
            $cookres = setcookie($cookie_name, $token, $expiry, CONF_WEBROOT_FRONT_URL);
            return true;
        }
        return false;
    }

    private function doCookieAdminLogin()
    {
        $remember_me_cookie_name = AdminAuthentication::ADMIN_REMEMBER_ME_COOKIE_NAME;

        if (isset($_COOKIE[$remember_me_cookie_name])) {
            $token = $_COOKIE[$remember_me_cookie_name];
            $auth_row = false;
            $auth_row = AdminAuthentication::checkLoginTokenInDB($token);
            if (strlen($token) != 32 || empty($auth_row)) {
                AdminAuthentication::clearLoggedAdminLoginCookie();
                return false;
            }

            $browser = $_SERVER['HTTP_USER_AGENT'];
            $ip = $_SERVER['REMOTE_ADDR'];
            if (strtotime($auth_row['admauth_expiry']) < strtotime('now') || $auth_row['admauth_browser'] != $browser || $ip != $auth_row['admauth_last_ip']) {
                AdminAuthentication::clearLoggedAdminLoginCookie();
                return false;
            }
            if ($this->loginById($auth_row['admauth_admin_id'])) {
                return true;
            }
            AdminAuthentication::clearLoggedAdminLoginCookie();
        }
        return false;
    }

    private function loginById($admin_id)
    {
        if (!$admin_id) {
            return false;
        }
        if ($row = AdminUsers::getAttributesById($admin_id)) {
            $row['admin_ip'] = $_SERVER['REMOTE_ADDR'];
            $adminAuthObj = AdminAuthentication::getInstance();
            $adminAuthObj->setAdminSession($row);
            return true;
        }
        return false;
    }

    private function generateLoginToken()
    {
        do {
            $salt = substr(md5(microtime()), 5, 12);
            $token = md5($salt . microtime() . substr($salt, 5));
        } while (AdminAuthentication::checkLoginTokenInDB($token));
        return $token;
    }

    private function getLoginForm()
    {
        $userName = '';
        $pass = '';
        if (CommonHelper::demoUrl()) {
            $userName = 'admin';
            $pass = 'admin@123';
        }

        $frm = new Form('frmLogin');
        $frm->addTextBox(Labels::getlabel('FRM_USERNAME', $this->siteLangId), 'username', $userName)->requirements()->setRequired();
        $frm->addPasswordField(Labels::getlabel('FRM_PASSWORD', $this->siteLangId), 'password', $pass)->requirements()->setRequired();
        $frm->addCheckBox('', 'rememberme', 1);
        $frm->addHtml('', 'btn_submit', HtmlHelper::addButtonHtml(Labels::getLabel('BTN_LOGIN', $this->siteLangId), 'submit', 'btn_submit', 'btn btn-brand btn-lg btn-block'));
        return $frm;
    }

    private function getForgotForm()
    {
        $frm = new Form('adminFrmForgot');
        $frm->addEmailField(Labels::getLabel('FRM_ENTER_YOUR_EMAIL_ADDRESS', $this->siteLangId), 'admin_email')->requirements()->setRequired();
        CommonHelper::addCaptchaField($frm);
        $frm->addSubmitButton('', 'btn_forgot', Labels::getLabel('BTN_SUBMIT', $this->siteLangId));
        return $frm;
    }

    private function getResetPwdForm($aId, $token)
    {
        $frm = new Form('frmResetPassword');
        $frm->addPasswordField(Labels::getLabel('FRM_NEW_PASSWORD', $this->siteLangId), 'new_pwd')->requirements()->setRequired();
        $fld_cp = $frm->addPasswordField(Labels::getLabel('FRM_CONFIRM_NEW_PASSWORD', $this->siteLangId), 'confirm_pwd');
        $fld_cp->requirements()->setCompareWith('new_pwd', 'eq', '');
        $frm->addHiddenField('', 'apr_id', $aId, array('id' => 'apr_id'));
        $frm->addHiddenField('', 'token', $token, array('id' => 'token'));
        $frm->addSubmitButton('', 'btn_reset', Labels::getLabel('BTN_RESET_PASWORD', $this->siteLangId));
        return $frm;
    }
}
