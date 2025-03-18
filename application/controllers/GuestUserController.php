<?php

class GuestUserController extends MyAppController
{
    private $authToken = '';
    private $username = '';

    public function loginForm()
    {
        if (UserAuthentication::isGuestUserLogged()) {
            LibHelper::exitWithError(Labels::getLabel('ERR_ALREADY_LOGGED_IN', $this->siteLangId), false, true);
            FatApp::redirectUser(UrlHelper::generateUrl('home'));
        }

        if (UserAuthentication::isUserLogged()) {
            LibHelper::exitWithError(Labels::getLabel('ERR_ALREADY_LOGGED_IN', $this->siteLangId), false, true);
            FatApp::redirectUser(UrlHelper::generateUrl('account', '', [], CONF_WEBROOT_DASHBOARD, null, false, false, false));
        }

        $socialLoginApis = Plugin::getDataByType(Plugin::TYPE_SOCIAL_LOGIN, $this->siteLangId);
        $canSendSms = SmsArchive::canSendSms(SmsTemplate::LOGIN);
        $signInWithEmail = FatApp::getPostedData('signInWithEmail', FatUtility::VAR_INT, 0);
        $signInWithPhone = FatApp::getPostedData('signInWithPhone', FatUtility::VAR_INT, 0);
        if (0 < $signInWithPhone) {
            $signInWithPhone = (int) $canSendSms;
        }

        $loginFrm = $this->getLoginForm($signInWithPhone);
        $loginFrm->addSecurityToken();
        $loginFrmData = array(
            'loginFrm' => $loginFrm,
            'socialLoginApis' => $socialLoginApis,
            'siteLangId' => $this->siteLangId,
            'canSendSms' => $canSendSms,
            'includeGuestLogin' => FatApp::getPostedData('includeGuestLogin', FatUtility::VAR_STRING, false),
        );

        $this->set('signInWithPhone', $signInWithPhone);
        $this->set('signInWithEmail', $signInWithEmail);
        $this->set('signinpopup', FatApp::getPostedData('signinpopup', FatUtility::VAR_INT, 0));
        $this->set('loginData', $loginFrmData);
        $this->set('exculdeMainHeaderDiv', true);

        $fOutMode = FatApp::getPostedData('fOutMode', FatUtility::VAR_STRING);
        if ('json' == $fOutMode) {
            $this->set('html', $this->_template->render(false, false, NULL, true, false));
            $this->_template->render(false, false, 'json-success.php', true, false);
        } else {
            $this->_template->render(true, false);
        }
    }

    public function registrationForm()
    {

        if (UserAuthentication::isGuestUserLogged()) {
            FatApp::redirectUser(UrlHelper::generateUrl('home'));
        }

        if (UserAuthentication::isUserLogged()) {
            FatApp::redirectUser(UrlHelper::generateUrl('account', '', [], CONF_WEBROOT_DASHBOARD, null, false, false, false));
        }
        $this->set('smsPluginStatus', SmsArchive::canSendSms(SmsTemplate::LOGIN));
        $this->set('exculdeMainHeaderDiv', true);
        $this->registerFormDetail(1);

        $this->_template->render(true, false, 'guest-user/registration-form.php');
    }

    public function registerFormDetail($isRegisterForm, $signUpWithPhone = 0)
    {
        $registerFrm = $this->getRegistrationForm(true, $signUpWithPhone);
        $registerFrm->addSecurityToken();
        $cPageSrch = ContentPage::getSearchObject($this->siteLangId);
        $cPageSrch->addCondition('cpage_id', '=', FatApp::getConfig('CONF_TERMS_AND_CONDITIONS_PAGE', FatUtility::VAR_INT, 0));
        $cPageSrch->doNotCalculateRecords();
        $cPageSrch->setPageSize(1);
        $cpage = FatApp::getDb()->fetch($cPageSrch->getResultSet());
        if (!empty($cpage) && is_array($cpage)) {
            $termsAndConditionsLinkHref = UrlHelper::generateUrl('Cms', 'view', array($cpage['cpage_id']));
        } else {
            $termsAndConditionsLinkHref = 'javascript:void(0)';
        }

        $privacyPolicyLinkHref = 'javascript:void(0)';
        $cPageSrch = ContentPage::getSearchObject($this->siteLangId);
        $cPageSrch->addCondition('cpage_id', '=', FatApp::getConfig('CONF_PRIVACY_POLICY_PAGE', FatUtility::VAR_INT, 0));
        $cPageSrch->doNotCalculateRecords();
        $cPageSrch->setPageSize(1);
        $cpage = FatApp::getDb()->fetch($cPageSrch->getResultSet());
        if (!empty($cpage) && is_array($cpage)) {
            $privacyPolicyLinkHref = UrlHelper::generateUrl('Cms', 'view', array($cpage['cpage_id']));
        }

        $registerdata = array(
            'registerFrm' => $registerFrm,
            'termsAndConditionsLinkHref' => $termsAndConditionsLinkHref,
            'siteLangId' => $this->siteLangId,
            'signUpWithPhone' => $signUpWithPhone,
            'privacyPolicyLinkHref' => $privacyPolicyLinkHref,
        );
        $isRegisterForm = FatUtility::int($isRegisterForm);

        $this->set('smsPluginStatus', SmsArchive::canSendSms(SmsTemplate::LOGIN));
        $this->set('isRegisterForm', $isRegisterForm);
        $this->set('registerdata', $registerdata);
    }

    public function signUpWithPhone()
    {
        $obj = new Plugin();
        $active = $obj->getDefaultPluginData(Plugin::TYPE_SMS_NOTIFICATION, 'plugin_active');
        $status = SmsTemplate::getTpl(SmsTemplate::LOGIN, 0, 'stpl_status');
        $signUpWithPhone = (false != $active && !empty($active) && 0 < $status ? applicationConstants::YES : applicationConstants::NO);
        $this->registerFormDetail(applicationConstants::YES, $signUpWithPhone);
        $this->_template->render(false, false, 'guest-user/register-form-detail.php');
    }

    public function signUpWithEmail()
    {
        $this->registerFormDetail(applicationConstants::YES, applicationConstants::NO);
        $this->_template->render(false, false, 'guest-user/register-form-detail.php');
    }

    public function login()
    {
        $authentication = new UserAuthentication();
        $userType = FatApp::getPostedData('userType', FatUtility::VAR_INT, 0);
        if (true === MOBILE_APP_API_CALL && 1 > $userType) {
            $resp = LibHelper::formatResponse(applicationConstants::FAILURE, Labels::getLabel('ERR_MISSING_REQUEST_PARAMS', $this->siteLangId));
            LibHelper::dieJsonResponse($resp);
        }

        $loginWithOtp = FatApp::getPostedData('loginWithOtp', FatUtility::VAR_INT, 0);
        $frm = $this->getLoginForm($loginWithOtp);
        $post = $frm->getFormDataFromArray(FatApp::getPostedData(), [], !MOBILE_APP_API_CALL);
        if ($post == false) {
            $resp = LibHelper::formatResponse(applicationConstants::FAILURE, current($frm->getValidationErrors()));
            LibHelper::dieJsonResponse($resp);
        }

        $password = FatApp::getPostedData('password');
        $userName = FatApp::getPostedData('username');
        $dialCode = FatApp::getPostedData('username_dcode', FatUtility::VAR_STRING, '');

        if (0 < $loginWithOtp) {
            $post = FatApp::getPostedData();
            $authentication->setLoginWithOtp($dialCode, $userName);
            if (true === MOBILE_APP_API_CALL) {
                if (User::OTP_LENGTH != strlen($post['upv_otp'])) {
                    $resp = LibHelper::formatResponse(applicationConstants::FAILURE, Labels::getLabel('ERR_INVALID_OTP', $this->siteLangId));
                    LibHelper::dieJsonResponse($resp);
                }
                $password = $post['upv_otp'];
            } else {
                if (!is_array($post['upv_otp']) || User::OTP_LENGTH != count($post['upv_otp'])) {
                    $resp = LibHelper::formatResponse(applicationConstants::FAILURE, Labels::getLabel('ERR_INVALID_OTP', $this->siteLangId));
                    LibHelper::dieJsonResponse($resp);
                }
                $password = implode("", $post['upv_otp']);
            }

            if (empty($password)) {
                $resp = LibHelper::formatResponse(applicationConstants::FAILURE, Labels::getLabel('ERR_OTP_REQUIRED', $this->siteLangId));
                LibHelper::dieJsonResponse($resp);
            }
        }

        $withPhone = empty($dialCode) ? false : true;
        if (!empty($dialCode) && false === strpos($userName, $dialCode)) {
            $userName = trim($dialCode) . trim($userName);
        }

        if (!$authentication->login($userName, $password, $_SERVER['REMOTE_ADDR'], true, false, $this->app_user['temp_user_id'], $userType, $withPhone)) {
            $resp = LibHelper::formatResponse(applicationConstants::FAILURE, $authentication->getError());
            LibHelper::dieJsonResponse($resp);
        }

        $this->app_user['temp_user_id'] = 0;

        $userId = UserAuthentication::getLoggedUserId();

        $user = new User($userId);
        $userSelectedCookies = $user->getUserSelectedCookies();
        if (CommonHelper::checkCookiesEnabledSession() && empty($userSelectedCookies)) {
            $statisticalCookies = (isset($_COOKIE['ykStatisticalCookies']) && 1 == $_COOKIE['ykStatisticalCookies']) ? 1 : 0;
            $personaliseCookies = (isset($_COOKIE['ykPersonaliseCookies']) && 1 == $_COOKIE['ykPersonaliseCookies']) ? 1 : 0;
            if (!$user->saveUserCookiesPreferences($statisticalCookies, $personaliseCookies)) {
                $resp = LibHelper::formatResponse(applicationConstants::FAILURE, $user->getError());
                LibHelper::dieJsonResponse($resp);
            }
        }

        if (true === MOBILE_APP_API_CALL) {
            $uObj = new User($userId);
            if (!$token = $uObj->setMobileAppToken()) {
                $resp = LibHelper::formatResponse(applicationConstants::FAILURE, Labels::getLabel('ERR_INVALID_REQUEST', $this->siteLangId));
                LibHelper::dieJsonResponse($resp);
            }

            $userInfo = $uObj->getUserInfo(array('user_name', 'user_id', 'user_phone_dcode', 'user_phone', 'credential_email'), true, true, true);
            $appSessionId = isset($_SERVER['HTTP_X_APP_SESSION_ID']) && !empty($_SERVER['HTTP_X_APP_SESSION_ID']) ? $_SERVER['HTTP_X_APP_SESSION_ID'] : session_id();
            $this->set('token', $token);
            $this->set('userInfo', $userInfo);
            $this->set('app_session_id', $appSessionId);
            $this->_template->render();
        }

        $rememberme = FatApp::getPostedData('remember_me', FatUtility::VAR_INT, 0);
        if ($rememberme == 1) {
            if (!$this->setUserLoginCookie()) {
                Message::addErrorMessage(Labels::getLabel('ERR_COOKIES_NOT_ADDED', $this->siteLangId));
            }
        }
        if (!MOBILE_APP_API_CALL) {
            $frm->expireSecurityToken(FatApp::getPostedData());
        }
        setcookie('uc_id', $userId, time() + 3600 * 24 * 30, CONF_WEBROOT_URL);

        $data = User::getAttributesById($userId, array('user_preferred_dashboard', 'user_registered_initially_for'));

        $preferredDashboard = 0;
        if ($data != false) {
            $preferredDashboard = $data['user_preferred_dashboard'];
        }

        $redirectUrl = '';

        if (isset($_SESSION['referer_page_url'])) {
            $redirectUrl = $_SESSION['referer_page_url'];
            unset($_SESSION['referer_page_url']);

            $userPreferedDashboardType = ($data['user_preferred_dashboard']) ? $data['user_preferred_dashboard'] : $data['user_registered_initially_for'];

            switch ($userPreferedDashboardType) {
                case User::USER_TYPE_BUYER:
                    $_SESSION[UserAuthentication::SESSION_ELEMENT_NAME]['activeTab'] = 'B';
                    break;
                case User::USER_TYPE_SELLER:
                    $_SESSION[UserAuthentication::SESSION_ELEMENT_NAME]['activeTab'] = 'S';
                    break;
                case User::USER_TYPE_AFFILIATE:
                    $_SESSION[UserAuthentication::SESSION_ELEMENT_NAME]['activeTab'] = 'AFFILIATE';
                    break;
                case User::USER_TYPE_ADVERTISER:
                    $_SESSION[UserAuthentication::SESSION_ELEMENT_NAME]['activeTab'] = 'Ad';
                    break;
            }
        }

        if ($redirectUrl == '') {
            $redirectUrl = User::getPreferedDashbordRedirectUrl($preferredDashboard);
        }

        if ($redirectUrl == '') {
            $redirectUrl = UrlHelper::generateUrl('Account', '', [], CONF_WEBROOT_DASHBOARD, null, false, false, false);
        }
        $this->set('redirectUrl', $redirectUrl);
        $this->set('msg', Labels::getLabel("MSG_LOGIN_SUCCESSFULLY", $this->siteLangId));
        $this->_template->render(false, false, 'json-success.php');
    }

    public function setUserPushNotificationToken()
    {
        $fcmToken = FatApp::getPostedData('deviceToken', FatUtility::VAR_STRING, '');
        $deviceOs = FatApp::getPostedData('deviceOs', FatUtility::VAR_INT, 0);
        $userType = FatApp::getPostedData('userType', FatUtility::VAR_INT, User::USER_TYPE_BUYER);
        if (empty($fcmToken)) {
            FatUtility::dieJSONError(Labels::getLabel('ERR_INVALID_REQUEST', $this->siteLangId));
        }

        if (!UserAuthentication::isUserLogged()) {
            if (!User::setGuestFcmToken($userType, $fcmToken, $deviceOs, $this->getAppTempUserId())) {
                FatUtility::dieJsonError(Labels::getLabel('ERR_UNABLE_TO_UPDATE.', $this->siteLangId));
            }
        } else {
            $userId = UserAuthentication::getLoggedUserId();
            $uObj = new User($userId);
            if (!$uObj->setPushNotificationToken($this->appToken, $fcmToken, $userType, $deviceOs)) {
                FatUtility::dieJsonError(Labels::getLabel('ERR_UNABLE_TO_UPDATE', $this->siteLangId));
            }
        }
        $this->set('msg', Labels::getLabel('MSG_SUCCESSFULLY_UPDATED', $this->siteLangId));
        $this->_template->render();
    }

    public function guestLogin()
    {
        $frm = $this->getGuestUserForm($this->siteLangId);
        $post = $frm->getFormDataFromArray(FatApp::getPostedData());

        if ($post == false) {
            $resp = LibHelper::formatResponse(applicationConstants::FAILURE, current($frm->getValidationErrors()));
            LibHelper::dieJsonResponse($resp);
        }

        $authentication = new UserAuthentication();
        if (!$authentication->guestLogin(FatApp::getPostedData('user_email'), FatApp::getPostedData('user_name'), $_SERVER['REMOTE_ADDR'])) {
            $resp = LibHelper::formatResponse(applicationConstants::FAILURE, $authentication->getError());
            LibHelper::dieJsonResponse($resp);
        }

        /* if (true === MOBILE_APP_API_CALL) {
            $resp = LibHelper::formatResponse(applicationConstants::SUCCESS, Labels::getLabel("MSG_GUEST_LOGIN_SUCCESSFULLY", $this->siteLangId));
            LibHelper::dieJsonResponse($resp);
        } */

        $redirectUrl = '';

        if (isset($_SESSION['referer_page_url'])) {
            $redirectUrl = $_SESSION['referer_page_url'];
            unset($_SESSION['referer_page_url']);
        }

        if ($redirectUrl == '') {
            $redirectUrl = User::getPreferedDashbordRedirectUrl(User::USER_BUYER_DASHBOARD);
        }

        if ($redirectUrl == '') {
            $redirectUrl = UrlHelper::generateUrl('Home');
        }

        $this->set('redirectUrl', $redirectUrl);
        $this->set('msg', Labels::getLabel("MSG_GUEST_LOGIN_SUCCESSFULLY", $this->siteLangId));
        $this->_template->render(false, false, 'json-success.php');
    }

    private function setUserLoginCookie()
    {
        $userId = UserAuthentication::getLoggedUserAttribute('user_id', true);

        if (null == $userId) {
            return false;
        }

        $token = $this->generateLoginToken();
        $expiry = strtotime("+7 DAYS");

        $values = array(
            'uauth_user_id' => $userId,
            'uauth_token' => $token,
            'uauth_expiry' => date('Y-m-d H:i:s', $expiry),
            'uauth_browser' => CommonHelper::userAgent(),
            'uauth_last_access' => date('Y-m-d H:i:s'),
            'uauth_last_ip' => CommonHelper::getClientIp(),
        );

        if (UserAuthentication::saveLoginToken($values)) {
            $cookieName = UserAuthentication::SYSTEMUSER_COOKIE_NAME;
            setcookie($cookieName, $token, $expiry, CONF_WEBROOT_URL);
            return true;
        }
        return false;
    }

    private function generateLoginToken()
    {
        return substr(md5(rand(1, 99999) . microtime()), 0, UserAuthentication::TOKEN_LENGTH);
    }

    public function form()
    {
        $frm = $this->getGuestUserForm($this->siteLangId);
        $this->set('frm', $frm);
        $this->_template->render(false, false);
    }

    public function checkAjaxUserLoggedIn()
    {
        $json = array();
        $json['isUserLogged'] = FatUtility::int(UserAuthentication::isUserLogged());
        if (!$json['isUserLogged']) {
            $json['isUserLogged'] = FatUtility::int(UserAuthentication::isGuestUserLogged());
        }
        die(json_encode($json));
    }

    public function registrationFormOld()
    {
        if (UserAuthentication::isGuestUserLogged()) {
            FatApp::redirectUser(UrlHelper::generateUrl('home'));
        }

        if (UserAuthentication::isUserLogged()) {
            FatApp::redirectUser(UrlHelper::generateUrl('account', '', [], CONF_WEBROOT_DASHBOARD, null, false, false, false));
        }

        $registerFrm = $this->getRegistrationForm();
        $registerFrm->addSecurityToken();

        $cPageSrch = ContentPage::getSearchObject($this->siteLangId);
        $cPageSrch->addCondition('cpage_id', '=', FatApp::getConfig('CONF_TERMS_AND_CONDITIONS_PAGE', FatUtility::VAR_INT, 0));
        $cPageSrch->doNotCalculateRecords();
        $cPageSrch->setPageSize(1);
        $cpage = FatApp::getDb()->fetch($cPageSrch->getResultSet());
        if (!empty($cpage) && is_array($cpage)) {
            $termsAndConditionsLinkHref = UrlHelper::generateUrl('Cms', 'view', array($cpage['cpage_id']));
        } else {
            $termsAndConditionsLinkHref = 'javascript:void(0)';
        }
        $data = array(
            'registerFrm' => $registerFrm,
            'termsAndConditionsLinkHref' => $termsAndConditionsLinkHref,
            'siteLangId' => $this->siteLangId
        );
        $obj = new Extrapage();
        $pageData = $obj->getContentByPageType(Extrapage::REGISTRATION_PAGE_RIGHT_BLOCK, $this->siteLangId);
        $this->set('pageData', $pageData);
        $this->set('data', $data);
        $this->_template->render(true, true, 'guest-user/registration-form.php');
    }

    /* Used for APP only. */

    public function checkEmailExists()
    {
        $emailAddress = FatApp::getPostedData('email', FatUtility::VAR_STRING, '');
        if (empty($emailAddress)) {
            $resp = LibHelper::formatResponse(applicationConstants::FAILURE, Labels::getLabel('ERR_INVALID_REQUEST', $this->siteLangId), [], LibHelper::RC_UNAUTHORIZED);
            LibHelper::dieJsonResponse($resp);
        }

        $uObj = new User();
        $data = (array) $uObj->checkUserByEmailOrUserName('', $emailAddress);
        if (empty($data)) {
            $resp = LibHelper::formatResponse(applicationConstants::FAILURE, Labels::getLabel('ERR_RESULT_NOT_FOUND', $this->siteLangId), [], LibHelper::RC_NOT_FOUND);
            LibHelper::dieJsonResponse($resp);
        }

        $resp = LibHelper::formatResponse(
            applicationConstants::SUCCESS,
            Labels::getLabel('MSG_RESULT_FOUND', $this->siteLangId),
            [
                'found' => 1,
                'verified' => $data['credential_verified'],
            ],
            LibHelper::RC_OK
        );
        LibHelper::dieJsonResponse($resp);
    }

    public function register()
    {
        $signUpWithPhone = FatApp::getPostedData('signUpWithPhone', FatUtility::VAR_INT, 0);
        $showNewsLetterCheckBox = 0 < $signUpWithPhone ? false : true;

        $frm = $this->getRegistrationForm($showNewsLetterCheckBox, $signUpWithPhone);
        $userName = FatApp::getPostedData('user_username', FatUtility::VAR_STRING, '');
        if (empty($userName) || false === ValidateElement::username($userName)) {
            $message = Labels::getLabel("ERR_INVALID_USERNAME", $this->siteLangId);
            LibHelper::exitWithError($message, false, true);
            FatApp::redirectUser(UrlHelper::generateUrl('GuestUser', 'RegistrationForm', [], CONF_WEBROOT_FRONTEND));
        }

        $post = $frm->getFormDataFromArray(FatApp::getPostedData(), [], !MOBILE_APP_API_CALL);
        if ($post == false) {
            $message = Labels::getLabel(current($frm->getValidationErrors()), $this->siteLangId);
            LibHelper::exitWithError($message, false, true);
            FatApp::redirectUser(UrlHelper::generateUrl('GuestUser', 'RegistrationForm', [], CONF_WEBROOT_FRONTEND));
        }

        $dialCode = FatApp::getPostedData('user_phone_dcode', FatUtility::VAR_STRING, '');
        $phoneNumber = FatApp::getPostedData('user_phone', FatUtility::VAR_INT, '');
        if ((0 < $signUpWithPhone && empty($phoneNumber)) && empty($dialCode)) {
            $message = Labels::getLabel("ERR_INVALID_PHONE_NUMBER", $this->siteLangId);
            LibHelper::exitWithError($message, false, true);
            FatApp::redirectUser(UrlHelper::generateUrl('GuestUser', 'RegistrationForm', [], CONF_WEBROOT_FRONTEND));
        }

        $post['user_phone_dcode'] = $dialCode;
        $post['user_is_buyer'] = User::USER_TYPE_BUYER;
        $post['user_preferred_dashboard'] = User::USER_BUYER_DASHBOARD;
        $post['user_registered_initially_for'] = User::USER_TYPE_BUYER;
        $post['user_is_supplier'] = (FatApp::getConfig("CONF_ADMIN_APPROVAL_SUPPLIER_REGISTRATION", FatUtility::VAR_INT, 1) || FatApp::getConfig("CONF_ACTIVATE_SEPARATE_SIGNUP_FORM", FatUtility::VAR_INT, 1)) ? 0 : 1;
        $post['user_is_advertiser'] = (FatApp::getConfig("CONF_ADMIN_APPROVAL_SUPPLIER_REGISTRATION", FatUtility::VAR_INT, 1) || FatApp::getConfig("CONF_ACTIVATE_SEPARATE_SIGNUP_FORM", FatUtility::VAR_INT, 1)) ? 0 : 1;
        $post['user_active'] = FatApp::getConfig('CONF_ADMIN_APPROVAL_REGISTRATION', FatUtility::VAR_INT, 1) ? 0 : 1;
        $post['user_verify'] = FatApp::getConfig('CONF_EMAIL_VERIFICATION_REGISTRATION', FatUtility::VAR_INT, 1) ? 0 : 1;
        $post['referralToken'] = FatApp::getPostedData('referralToken', FatUtility::VAR_STRING, '');

        $userObj = new User();
        $returnUserId = (true == MOBILE_APP_API_CALL && 0 < $signUpWithPhone ? true : false);
        if (!$userId = $userObj->saveUserData($post, false, $returnUserId)) {
            $message = Labels::getLabel($userObj->getError(), $this->siteLangId);
            if (false !== strpos(strtolower($message), 'duplicate')  && false !== strpos(strtolower($message), 'user_dial_code')) {
                $message = Labels::getLabel('ERR_PHONE_NUMBER_ALREADY_EXIST', $this->siteLangId);
            }
            if (0 < $signUpWithPhone) {
                $row = (array) $userObj->checkUserByPhoneOrUserName($post['user_username'], $dialCode . $phoneNumber);
                if (0 < count($row)) {
                    $userId = $row['user_id'];
                    $replacements = [
                        '{CONTINUE-BTN}' => '<a class="btn btn-outline-white" href="javascript:void(0);" onclick="resendOtp(' . $userId . ')">' . Labels::getLabel('BTN_PROCEED', $this->siteLangId) . '</a>'
                    ];
                    $message = CommonHelper::replaceStringData($message, $replacements);
                }
            }

            LibHelper::exitWithError($message, false, true);
            FatApp::redirectUser(UrlHelper::generateUrl('GuestUser', 'registrationForm', [], CONF_WEBROOT_FRONTEND));
        }
        if (!MOBILE_APP_API_CALL) {
            $frm->expireSecurityToken(FatApp::getPostedData());
        }


        $data = [];
        $emailVerification = FatApp::getConfig('CONF_EMAIL_VERIFICATION_REGISTRATION', FatUtility::VAR_INT, 1);
        if (1 > $signUpWithPhone && !$emailVerification) {
            $cartObj = new Cart();
            $isCheckOutPage = (isset($post['isCheckOutPage']) && $cartObj->hasProducts()) ? FatUtility::int($post['isCheckOutPage']) : 0;
            $confAutoLoginRegisteration = ($isCheckOutPage) ? 1 : FatApp::getConfig('CONF_AUTO_LOGIN_REGISTRATION', FatUtility::VAR_INT, 1);
            if ($confAutoLoginRegisteration && !(FatApp::getConfig('CONF_ADMIN_APPROVAL_REGISTRATION', FatUtility::VAR_INT, 1))) {
                $authentication = new UserAuthentication();
                if (!$authentication->login(FatApp::getPostedData('user_username'), FatApp::getPostedData('user_password'), $_SERVER['REMOTE_ADDR'])) {
                    $message = Labels::getLabel($authentication->getError(), $this->siteLangId);
                    LibHelper::exitWithError($message, false, true);
                    FatApp::redirectUser(UrlHelper::generateUrl('GuestUser', 'loginForm'), [], CONF_WEBROOT_FRONTEND);
                }

                if (true === MOBILE_APP_API_CALL) {
                    if (!$token = $userObj->setMobileAppToken()) {
                        LibHelper::exitWithError(Labels::getLabel('ERR_INVALID_REQUEST', $this->siteLangId), true);
                    }
                    $data = [
                        'user_name' => $post['user_name'],
                        'user_id' => $userId,
                        'user_phone_dcode' => $post['user_phone_dcode'] ?? '',
                        'user_phone' => $post['user_phone'] ?? '',
                        'credential_email' => $post['credential_email'] ?? '',
                        'token' => $token,
                        'user_image' => UrlHelper::generateFullUrl('image', 'user', array($userId, ImageDimension::VIEW_THUMB))
                    ];
                } else {
                    $redirectUrl = UrlHelper::generateUrl('Buyer', '', [], CONF_WEBROOT_DASHBOARD, null, false, false, false);
                    if ($isCheckOutPage) {
                        $this->set('needLogin', 1);
                        $redirectUrl = UrlHelper::generateUrl('Checkout');
                    }
                    if (FatUtility::isAjaxCall()) {
                        $this->set('msg', Labels::getLabel('MSG_SUCCESSFULLY_REGISTERED', $this->siteLangId));
                        $this->set('redirectUrl', $redirectUrl);
                        $this->_template->render(false, false, 'json-success.php');
                        exit;
                    }
                    FatApp::redirectUser($redirectUrl);
                }
            }
        }

        if (true === MOBILE_APP_API_CALL) {
            if (0 < $signUpWithPhone) {
                $data = array_merge($data, ['user_id' => $userId]);
                $this->set('data', $data);
                $this->set('msg', Labels::getLabel('MSG_OTP_SENT!_PLEASE_CHECK_YOUR_PHONE.', $this->siteLangId));
            } else {
                $msg = ($emailVerification) ? Labels::getLabel('MSG_SUCCESSFULLY_REGISTERED._EMAIL_VERIFICATION_PENDING') : Labels::getLabel('MSG_SUCCESSFULLY_REGISTERED', $this->siteLangId);
                $this->set('data', $data);
                $this->set('msg', $msg);
            }
            $this->_template->render();
        }

        $actionUrl = 'registrationSuccess';
        if (0 < $signUpWithPhone) {
            $actionUrl = 'otpForm';
        }

        $redirectUrl = UrlHelper::generateUrl('GuestUser', $actionUrl, [], CONF_WEBROOT_FRONTEND);
        if (FatUtility::isAjaxCall()) {
            $this->set('msg', Labels::getLabel('MSG_SUCCESSFULLY_REGISTERED', $this->siteLangId));
            $this->set('redirectUrl', $redirectUrl);
            $this->_template->render(false, false, 'json-success.php');
            exit;
        }
        FatApp::redirectUser($redirectUrl);
    }

    public function validateOtp($recoverPwd = 0, $forgotPw = false)
    {
        $this->validateOtpApi(0, (!$forgotPw && FatApp::getConfig('CONF_AUTO_LOGIN_REGISTRATION', FatUtility::VAR_INT, 1)));
        $userId = FatApp::getPostedData('user_id', FatUtility::VAR_INT, 0);
        if (0 < $recoverPwd) {
            $obj = new UserAuthentication();
            $record = $obj->getUserResetPwdToken($userId);
            $token = $record['uprr_token'];
            $redirectUrl = UrlHelper::generateFullUrl('GuestUser', 'resetPassword', array($userId, $token));
        } else {
            if (isset($_SESSION['referer_page_url'])) {
                $redirectUrl =  $_SESSION['referer_page_url'];
            } elseif (FatApp::getConfig('CONF_AUTO_LOGIN_REGISTRATION', FatUtility::VAR_INT, 1)) {
                $this->set('msg', Labels::getLabel("MSG_LOGIN_SUCCESSFULLY", $this->siteLangId));
                $redirectUrl =  UrlHelper::generateUrl('Account', '', [], CONF_WEBROOT_DASHBOARD, null, false, false, false);
            } else {
                $redirectUrl =  UrlHelper::generateUrl('GuestUser', 'registrationSuccess', [1], CONF_WEBROOT_FRONTEND);
            }
        }
        unset($_SESSION[UserAuthentication::TEMP_SESSION_ELEMENT_NAME]);
        $this->set('redirectUrl', $redirectUrl);
        $this->_template->render(false, false, 'json-success.php');
    }

    public function userCheckEmailVerification($code)
    {
        $code = FatUtility::convertToType($code, FatUtility::VAR_STRING);
        if (strlen($code) < 1) {
            Message::addMessage(Labels::getLabel("MSG_PLEASE_CHECK_YOUR_EMAIL_IN_ORDER_TO_VERIFY", $this->siteLangId));
            FatApp::redirectUser(UrlHelper::generateUrl('GuestUser', 'loginForm', [], CONF_WEBROOT_FRONTEND));
        }

        $arrCode = explode('_', $code, 2);

        $userId = FatUtility::int($arrCode[0]);
        if ($userId < 1) {
            Message::addErrorMessage(Labels::getLabel('ERR_INVALID_CODE', $this->siteLangId));
            FatApp::redirectUser(UrlHelper::generateUrl('GuestUser', 'loginForm', [], CONF_WEBROOT_FRONTEND));
        }

        $userObj = new User($userId);
        $userData = User::getAttributesById($userId, array('user_id', 'user_is_affiliate'));
        if (!$userData) {
            Message::addErrorMessage(Labels::getLabel('ERR_INVALID_CODE', $this->siteLangId));
            FatApp::redirectUser(UrlHelper::generateUrl('GuestUser', 'loginForm', [], CONF_WEBROOT_FRONTEND));
        }

        $db = FatApp::getDb();
        $db->startTransaction();

        if (!$userObj->verifyUserEmailVerificationCode($code)) {
            $db->rollbackTransaction();
            Message::addErrorMessage(Labels::getLabel("ERR_MSG_INVALID_VERIFICATION_REQUEST", $this->siteLangId));
            FatApp::redirectUser(UrlHelper::generateUrl('GuestUser', 'loginForm', [], CONF_WEBROOT_FRONTEND));
        }

        if ($userData['user_is_affiliate'] != applicationConstants::YES) {
            $srch = new SearchBase('tbl_user_credentials');
            $srch->addCondition('credential_user_id', '=', $userId);
            $srch->doNotCalculateRecords();
            $srch->setPageSize(1);
            $rs = $srch->getResultSet();
            $checkActiveRow = $db->fetch($rs);
            if ($checkActiveRow['credential_active'] != applicationConstants::ACTIVE) {
                $active = FatApp::getConfig('CONF_ADMIN_APPROVAL_REGISTRATION', FatUtility::VAR_INT, 1) ? 0 : 1;
                if (!$userObj->activateAccount($active)) {
                    $db->rollbackTransaction();
                    Message::addErrorMessage(Labels::getLabel('ERR_INVALID_CODE', $this->siteLangId));
                    FatApp::redirectUser(UrlHelper::generateUrl('GuestUser', 'loginForm', [], CONF_WEBROOT_FRONTEND));
                }
            }
        }

        if (!$userObj->verifyAccount()) {
            $db->rollbackTransaction();
            Message::addErrorMessage(Labels::getLabel('ERR_INVALID_CODE', $this->siteLangId));
            FatApp::redirectUser(UrlHelper::generateUrl('GuestUser', 'loginForm', [], CONF_WEBROOT_FRONTEND));
        }

        unset($_SESSION[UserAuthentication::SESSION_ELEMENT_NAME]['newEmailToVerify']);

        $userdata = $userObj->getUserInfo(array('credential_username', 'credential_email', 'credential_password', 'user_name', 'credential_active'), false);

        if (FatApp::getConfig('CONF_WELCOME_EMAIL_REGISTRATION', FatUtility::VAR_INT, 1)) {
            $data['user_email'] = $userdata['credential_email'];
            $data['user_name'] = $userdata['user_name'];
            $data['credential_username'] = $userdata['credential_username'];
            //ToDO::Change login link to contact us link
            $link = UrlHelper::generateFullUrl('GuestUser', 'loginForm');
            if (!$userObj->userWelcomeEmailRegistration($data, $link, $this->siteLangId)) {
                Message::addErrorMessage(Labels::getLabel("ERR_WELCOME_EMAIL_COULD_NOT_BE_SENT", $this->siteLangId));
                $db->rollbackTransaction();
                FatApp::redirectUser(UrlHelper::generateUrl('GuestUser', 'loginForm', [], CONF_WEBROOT_FRONTEND));
            }
        }

        $db->commitTransaction();

        if (FatApp::getConfig('CONF_AUTO_LOGIN_REGISTRATION', FatUtility::VAR_INT, 1)  && $userdata['credential_active'] == applicationConstants::YES && !UserAuthentication::isGuestUserLogged()) {
            $authentication = new UserAuthentication();
            if (!$authentication->login($userdata['credential_email'], $userdata['credential_password'], $_SERVER['REMOTE_ADDR'], false)) {
                Message::addErrorMessage(Labels::getLabel($authentication->getError(), $this->siteLangId));
                FatApp::redirectUser(UrlHelper::generateUrl('GuestUser', 'loginForm', [], CONF_WEBROOT_FRONTEND));
            }
            FatApp::redirectUser(UrlHelper::generateUrl('Account', '', [], CONF_WEBROOT_DASHBOARD, null, false, false, false));
        }

        if (UserAuthentication::isGuestUserLogged()) {
            Message::addMessage(Labels::getLabel("MSG_EMAIL_VERIFIED._PLEASE_UPDATE_YOUR_NEW_PASSWORD", $this->siteLangId));
            FatApp::redirectUser(UrlHelper::generateUrl('Account', 'changeEmailPassword', [], CONF_WEBROOT_DASHBOARD, null, false, false, false));
        }

        Message::addMessage(Labels::getLabel("MSG_EMAIL_VERIFIED", $this->siteLangId));
        FatApp::redirectUser(UrlHelper::generateUrl('GuestUser', 'loginForm', [], CONF_WEBROOT_FRONTEND));
    }

    public function changeEmailVerification($code)
    {
        $code = FatUtility::convertToType($code, FatUtility::VAR_STRING);
        if (strlen($code) < 1) {
            Message::addMessage(Labels::getLabel("ERR_PLEASE_CHECK_YOUR_EMAIL_IN_ORDER_TO_VERIFY", $this->siteLangId));
            FatApp::redirectUser(UrlHelper::generateUrl('GuestUser', 'loginForm', [], CONF_WEBROOT_FRONTEND));
        }

        $arrCode = explode('_', $code, 2);

        $userId = FatUtility::int($arrCode[0]);
        if ($userId < 1) {
            Message::addErrorMessage(Labels::getLabel('ERR_INVALID_CODE', $this->siteLangId));
            FatApp::redirectUser(UrlHelper::generateUrl('GuestUser', 'loginForm', [], CONF_WEBROOT_FRONTEND));
        }

        $userObj = new User($userId);

        $newUserEmail = $userObj->verifyUserEmailVerificationCode($code);

        if (!$newUserEmail) {
            Message::addErrorMessage(Labels::getLabel("ERR_MSG_INVALID_VERIFICATION_REQUEST", $this->siteLangId));
            FatApp::redirectUser(UrlHelper::generateUrl('GuestUser', 'loginForm', [], CONF_WEBROOT_FRONTEND));
        }

        $usr = new User();
        $srch = $usr->getUserSearchObj(array('uc.credential_email'));
        $srch->addCondition('uc.credential_email', '=', $newUserEmail);
        $srch->doNotCalculateRecords();
        $srch->doNotLimitRecords();

        $rs = $srch->getResultSet();
        $record = FatApp::getDb()->fetch($rs);

        if ($record) {
            Message::addErrorMessage(Labels::getLabel("ERR_DUPLICATE_EMAIL", $this->siteLangId));
            FatApp::redirectUser(UrlHelper::generateUrl('GuestUser', 'loginForm', [], CONF_WEBROOT_FRONTEND));
        }


        $srchUser = $usr->getUserSearchObj(array('u.user_name', 'u.user_phone_dcode', 'u.user_phone', 'uc.credential_email'));
        $srchUser->addCondition('u.user_id', '=', $userId);
        $srchUser->doNotCalculateRecords();
        $srchUser->doNotLimitRecords();
        $rs = $srchUser->getResultSet();
        $data = FatApp::getDb()->fetch($rs);

        if (!$userObj->changeEmail($newUserEmail)) {
            Message::addErrorMessage(Labels::getLabel("ERR_UPDATED_EMAIL_COULD_NOT_BE_SET", $this->siteLangId) . $userObj->getError());
            FatApp::redirectUser(UrlHelper::generateUrl('GuestUser', 'loginForm', [], CONF_WEBROOT_FRONTEND));
        }

        $email = new EmailHandler();
        $currentEmail = $data['credential_email'];
        $phone = $data['user_phone'];
        $dialCode = ValidateElement::formatDialCode($data['user_phone_dcode']);
        if (!empty($currentEmail) && !$email->sendEmailChangedNotification($this->siteLangId, array('user_name' => $data['user_name'], 'user_email' => $data['credential_email'], 'user_new_email' => $newUserEmail, 'user_phone_dcode' => $dialCode, 'user_phone' => $phone))) {
            Message::addErrorMessage(Labels::getLabel("ERR_UNABLE_TO_SEND_EMAIL_CHANGE_NOTIFICATION", $this->siteLangId) . $userObj->getError());
            FatApp::redirectUser(UrlHelper::generateUrl('GuestUser', 'loginForm', [], CONF_WEBROOT_FRONTEND));
        }

        if (FatApp::getConfig('CONF_AUTO_LOGIN_REGISTRATION', FatUtility::VAR_INT, 1) || UserAuthentication::isUserLogged()) {
            $userdata = $userObj->getUserInfo(array('credential_username', 'credential_password'));
            $authentication = new UserAuthentication();
            if (!$authentication->login($userdata['credential_username'], $userdata['credential_password'], $_SERVER['REMOTE_ADDR'], false)) {
                Message::addErrorMessage(Labels::getLabel($authentication->getError(), $this->siteLangId));
                FatApp::redirectUser(UrlHelper::generateUrl('GuestUser', 'loginForm', [], CONF_WEBROOT_FRONTEND));
            }
            Message::addMessage(Labels::getLabel("MSG_EMAIL_VERIFIED", $this->siteLangId));
            FatApp::redirectUser(UrlHelper::generateUrl('Account', '', [], CONF_WEBROOT_DASHBOARD, null, false, false, false));
        }

        Message::addMessage(Labels::getLabel("MSG_EMAIL_VERIFIED", $this->siteLangId));
        FatApp::redirectUser(UrlHelper::generateUrl('GuestUser', 'loginForm', [], CONF_WEBROOT_FRONTEND));
    }

    public function registrationSuccess($viaOtp = 0)
    {
        $this->set('registrationMsg', Labels::getLabel("MSG_REGISTERED_SUCCESSFULLY", $this->siteLangId));
        if (1 > $viaOtp) {
            if (FatApp::getConfig('CONF_EMAIL_VERIFICATION_REGISTRATION', FatUtility::VAR_INT, 1)) {
                $this->set('registrationMsg', Labels::getLabel("MSG_SUCCESS_USER_SIGNUP_EMAIL_VERIFICATION_PENDING", $this->siteLangId));
            } elseif (FatApp::getConfig('CONF_ADMIN_APPROVAL_REGISTRATION', FatUtility::VAR_INT, 1)) {
                $this->set('registrationMsg', Labels::getLabel("MSG_SUCCESS_USER_SIGNUP_ADMIN_APPROVAL_PENDING", $this->siteLangId));
            }
        }
        $this->_template->render();
    }

    public function forgotPasswordForm($withPhone = 0, $includeHeaderAndFooter = 1)
    {
        if (UserAuthentication::isGuestUserLogged()) {
            LibHelper::exitWithError(Labels::getLabel('ERR_ALREADY_LOGGED_IN', $this->siteLangId), false, true);
            FatApp::redirectUser(UrlHelper::generateUrl('home'));
        }

        if (UserAuthentication::isUserLogged()) {
            LibHelper::exitWithError(Labels::getLabel('ERR_ALREADY_LOGGED_IN', $this->siteLangId), false, true);
            FatApp::redirectUser(UrlHelper::generateUrl('account', '', [], CONF_WEBROOT_DASHBOARD, null, false, false, false));
        }

        $frm = $this->getForgotForm($withPhone);
        $frm->addSecurityToken();

        $this->set('smsPluginStatus', SmsArchive::canSendSms(SmsTemplate::LOGIN));
        $this->set('withPhone', $withPhone);

        $this->set('frm', $frm);
        $this->set('siteLangId', $this->siteLangId);

        if (1 > $withPhone && 0 < $includeHeaderAndFooter) {
            $this->set('exculdeMainHeaderDiv', true);
            $this->_template->render(true, false);
            return;
        }
        $this->_template->render(false, false);
    }

    public function sendResetPasswordLink($usernameOrEmail = '')
    {
        if (empty($usernameOrEmail)) {
            FatUtility::dieJsonError(Labels::getLabel('ERR_INVALID_REQUEST', $this->siteLangId));
        }
        $userAuthObj = new UserAuthentication();
        $row = $userAuthObj->getUserByEmailOrUserName($usernameOrEmail, '', false);
        if (!$row || false === $row) {
            $message = Labels::getLabel($userAuthObj->getError(), $this->siteLangId);
            if (true === MOBILE_APP_API_CALL || FatUtility::isAjaxCall()) {
                FatUtility::dieJsonError($message);
            }
            Message::addErrorMessage($message);
            FatApp::redirectUser(UrlHelper::generateUrl('GuestUser', 'forgotPasswordForm'));
        }
        $token = FatUtility::getRandomString(30);
        $row['token'] = $token;
        $userAuthObj->deleteOldPasswordResetRequest($row['user_id']);

        if (!$userAuthObj->addPasswordResetRequest($row)) {
            $message = Labels::getLabel($userAuthObj->getError(), $this->siteLangId);
            if (true === MOBILE_APP_API_CALL || FatUtility::isAjaxCall()) {
                FatUtility::dieJsonError($message);
            }
            Message::addErrorMessage($message);
            FatApp::redirectUser(UrlHelper::generateUrl('GuestUser', 'loginForm'));
        }
        $row['link'] = UrlHelper::generateFullUrl('GuestUser', 'resetPassword', array($row['user_id'], $token));
        $email = new EmailHandler();
        if (!$email->sendForgotPasswordLinkEmail($this->siteLangId, $row)) {
            $message = Labels::getLabel("ERR_PASSWORD_RESET_EMAIL_COULD_NOT_BE_SENT", $this->siteLangId);
            FatUtility::dieJsonError($message);
        }
        $message = Labels::getLabel("MSG_YOUR_PASSWORD_RESET_INSTRUCTIONS_TO_YOUR_EMAIL", $this->siteLangId);

        if (true === MOBILE_APP_API_CALL) {
            $this->set('msg', $message);
            $this->_template->render();
        }
        FatUtility::dieJsonSuccess($message);
    }

    public function forgotPassword()
    {
        $withPhone = FatApp::getPostedData('withPhone', FatUtility::VAR_INT, 0);
        $frm = $this->getForgotForm($withPhone);
        $post = $frm->getFormDataFromArray(FatApp::getPostedData(), [], !MOBILE_APP_API_CALL);
        if (false === $post) {
            LibHelper::exitWithError(current($frm->getValidationErrors()), false, true);
            FatApp::redirectUser(UrlHelper::generateUrl('GuestUser', 'forgotPasswordForm', [], CONF_WEBROOT_FRONTEND));
        }
        if (!MOBILE_APP_API_CALL) {
            $frm->expireSecurityToken(FatApp::getPostedData());
        }

        if (false === MOBILE_APP_API_CALL && FatApp::getConfig('CONF_RECAPTCHA_SITEKEY', FatUtility::VAR_STRING, '') != '' && FatApp::getConfig('CONF_RECAPTCHA_SECRETKEY', FatUtility::VAR_STRING, '') != '') {
            if (!CommonHelper::verifyCaptcha()) {
                LibHelper::exitWithError(Labels::getLabel('ERR_THAT_CAPTCHA_WAS_INCORRECT', $this->siteLangId), false, true);
                FatApp::redirectUser(UrlHelper::generateUrl('GuestUser', 'forgotPasswordForm', [], CONF_WEBROOT_FRONTEND));
            }
        }

        $post['user_phone_dcode'] = FatApp::getPostedData('user_phone_dcode', FatUtility::VAR_STRING, '');
        $user = (0 < $withPhone) ? trim($post['user_phone_dcode']) . trim($post['user_phone']) : $post['user_email_username'];

        $userAuthObj = new UserAuthentication();
        if (0 < $withPhone) {
            $row = $userAuthObj->getUserByPhone($user, '', false);
            if (empty($row)) {
                LibHelper::exitWithError(Labels::getLabel('ERR_INVALID_ACCOUNT', $this->siteLangId), false, true);
                FatApp::redirectUser(UrlHelper::generateUrl('GuestUser', 'forgotPasswordForm', [], CONF_WEBROOT_FRONTEND));
            } else if (!array_key_exists('credential_email', $row) || empty($row['credential_email'])) {
                LibHelper::exitWithError(Labels::getLabel('ERR_AS_THIS_ACCOUNT_IS_REGISTERED_WITH_PHONE._SO_PASSWORD_IS_NOT_REQUIRED', $this->siteLangId), false, true);
                FatApp::redirectUser(UrlHelper::generateUrl('GuestUser', 'forgotPasswordForm', [], CONF_WEBROOT_FRONTEND));
            }
        } else {
            $row = $userAuthObj->getUserByEmailOrUserName($user, '', false);
        }

        if (!$row || false === $row) {
            LibHelper::exitWithError($userAuthObj->getError(), false, true);
            FatApp::redirectUser(UrlHelper::generateUrl('GuestUser', 'forgotPasswordForm', [], CONF_WEBROOT_FRONTEND));
        }

        if ($row['user_is_shipping_company'] == applicationConstants::YES) {
            LibHelper::exitWithError(Labels::getLabel('ERR_SHIPPING_USER_ARE_NOT_ALLOWED_TO_PLACE_FORGOT_PASSWORD_REQUEST', $this->siteLangId), false, true);
            FatApp::redirectUser(UrlHelper::generateUrl('GuestUser', 'forgotPasswordForm', [], CONF_WEBROOT_FRONTEND));
        }

        if (1 > $withPhone && $userAuthObj->checkUserPwdResetRequest($row['user_id'])) {
            LibHelper::exitWithError($userAuthObj->getError(), false, true);
            FatApp::redirectUser(UrlHelper::generateUrl('GuestUser', 'forgotPasswordForm', [], CONF_WEBROOT_FRONTEND));
        }

        $token = FatUtility::getRandomString(30);
        $row['token'] = $token;

        $recordId = 0 < $withPhone ? $row['user_id'] : 0;
        $userAuthObj->deleteOldPasswordResetRequest($recordId);

        $db = FatApp::getDb();
        $db->startTransaction();

        if (!$userAuthObj->addPasswordResetRequest($row)) {
            $db->rollbackTransaction();
            LibHelper::exitWithError($userAuthObj->getError(), false, true);
            FatApp::redirectUser(UrlHelper::generateUrl('GuestUser', 'forgotPasswordForm', [], CONF_WEBROOT_FRONTEND));
        }
        $row['link'] = UrlHelper::generateFullUrl('GuestUser', 'resetPassword', array($row['user_id'], $token));
        $row['user_email'] = $row['credential_email'];

        /* Send verification email if email not verified[ */
        $srch = new SearchBase('tbl_user_credentials');
        $srch->addCondition('credential_user_id', '=', $row['user_id']);
        $srch->doNotCalculateRecords();
        $srch->setPageSize(1);
        $rs = $srch->getResultSet();
        $checkVerificationRow = $db->fetch($rs);

        $userObj = new User($row['user_id']);
        $notVerified = false;
        if ($checkVerificationRow['credential_verified'] == applicationConstants::NO) {
            $error = false;
            if (0 < $withPhone) {
                $row['user_phone_dcode'] = ValidateElement::formatDialCode($post['user_phone_dcode']);
                $row['user_phone'] = $post['user_phone'];
                if (!$userObj->userPhoneVerification($row, $this->siteLangId)) {
                    $message = !empty($userObj->getError()) ? $userObj->getError() : Labels::getLabel("ERR_ERROR_IN_SENDING_VERIFICATION_SMS", $this->siteLangId);
                    $error = true;
                }
                $notVerified = true;
            } else {
                if (!$userObj->userEmailVerification($row, $this->siteLangId)) {
                    $message = Labels::getLabel("ERR_VERIFICATION_EMAIL_COULD_NOT_BE_SENT", $this->siteLangId);
                    $error = true;
                }
            }
            if (true === $error) {
                LibHelper::exitWithError($message, false, true);
                FatApp::redirectUser(UrlHelper::generateUrl('GuestUser', 'forgotPasswordForm', [], CONF_WEBROOT_FRONTEND));
            }
        }
        /* ] */

        if (1 > $withPhone) {
            $email = new EmailHandler();
            $uData = User::getAttributesById($row['user_id'], ['user_phone_dcode', 'user_phone']);
            $row = array_merge($row, $uData);
            if (!$email->sendForgotPasswordLinkEmail($this->siteLangId, $row)) {
                $db->rollbackTransaction();
                $message = Labels::getLabel("ERR_ERROR_IN_SENDING_PASSWORD_RESET_LINK_EMAIL", $this->siteLangId);
                LibHelper::exitWithError($message, false, true);
                FatApp::redirectUser(UrlHelper::generateUrl('GuestUser', 'forgotPasswordForm', [], CONF_WEBROOT_FRONTEND));
            }
        } else {
            if (false === $notVerified && !$userObj->resendOtp()) {
                LibHelper::exitWithError($userObj->getError(), false, true);
                FatApp::redirectUser(UrlHelper::generateUrl('GuestUser', 'forgotPasswordForm', [], CONF_WEBROOT_FRONTEND));
            }
        }

        $db->commitTransaction();
        if (1 > $withPhone) {
            $message = Labels::getLabel("MSG_YOUR_PASSWORD_RESET_INSTRUCTIONS_TO_YOUR_EMAIL", $this->siteLangId);
        } else {
            $message = Labels::getLabel("MSG_AN_OTP_SENT_ON_YOUR_PHONE", $this->siteLangId);
        }

        if (true === MOBILE_APP_API_CALL || FatUtility::isAjaxCall()) {
            $this->set('msg', $message);
            if (true === MOBILE_APP_API_CALL) {
                if (0 < $withPhone) {
                    $this->set('data', ['user_id' => $row['user_id']]);
                }
                $this->_template->render();
            } else if (0 < $withPhone) {
                $frm = $this->getOtpForm();
                $frm->fill(['user_id' => $row['user_id']]);
                $this->set('frm', $frm);
                $json['html'] = $this->_template->render(false, false, 'guest-user/forgot-otp-form.php', true, false);
                FatUtility::dieJsonSuccess($json);
            }
            $this->_template->render(false, false, 'json-success.php');
            exit;
        }

        Message::addMessage($message);
        FatApp::redirectUser(UrlHelper::generateUrl('GuestUser', 'loginForm', [], CONF_WEBROOT_FRONTEND));
    }

    public function resendVerification($usernameOrEmail = '')
    {
        $frm = $this->getForgotForm();
        if (empty($usernameOrEmail)) {
            FatUtility::dieJsonError(Labels::getLabel('ERR_INVALID_REQUEST', $this->siteLangId));
        }

        $userAuthObj = new UserAuthentication();

        if (!$row = $userAuthObj->getUserByEmailOrUserName($usernameOrEmail, false, false)) {
            FatUtility::dieJsonError(Labels::getLabel($userAuthObj->getError(), $this->siteLangId));
        }

        $row['user_email'] = $row['credential_email'];
        $db = FatApp::getDb();
        $srch = new SearchBase('tbl_user_credentials');
        $srch->addCondition('credential_email', '=', $row['user_email']);
        $srch->doNotCalculateRecords();
        $srch->setPageSize(1);
        $rs = $srch->getResultSet();
        $checkVerificationRow = $db->fetch($rs);

        $userObj = new User($row['user_id']);
        if ($checkVerificationRow['credential_verified'] != 1) {
            if (!$userObj->userEmailVerification($row, $this->siteLangId)) {
                FatUtility::dieJsonError(Labels::getLabel("ERR_VERIFICATION_EMAIL_COULD_NOT_BE_SENT", $this->siteLangId));
            } else {
                $message = Labels::getLabel("ERR_VERIFICATION_EMAIL_HAS_BEEN_SENT_AGAIN", $this->siteLangId);
                if (true === MOBILE_APP_API_CALL) {
                    $this->set('msg', $message);
                    $this->_template->render();
                }
                FatUtility::dieJsonSuccess($message);
            }
        } else {
            FatUtility::dieJsonError(Labels::getLabel("ERR_YOU_ARE_ALREADY_VERIFIED_PLEASE_LOGIN.", $this->siteLangId));
        }
    }

    public function resetPassword($userId = 0, $token = '')
    {
        $userId = FatUtility::int($userId);

        if ($userId < 1 || strlen(trim($token)) < 20) {
            FatApp::redirectUser(UrlHelper::generateUrl('GuestUser', 'loginForm', [], CONF_WEBROOT_FRONTEND));
        }

        $userAuthObj = new UserAuthentication();

        if (!$userAuthObj->checkResetLink($userId, trim($token))) {
            Message::addErrorMessage($userAuthObj->getError());
            FatApp::redirectUser(UrlHelper::generateUrl('GuestUser', 'loginForm', [], CONF_WEBROOT_FRONTEND));
        }
        $userObj = new User($userId);
        $user = $userObj->getUserInfo(array('credential_password', 'credential_username'), false, false);
        if (!$user) {
            FatUtility::dieJsonError(Labels::getLabel('ERR_INVALID_REQUEST', $this->siteLangId));
        }
        $this->set('user_password', $user['credential_password']);
        $this->set('credential_username', $user['credential_username']);
        $this->set('frm', $this->getResetPwdForm($userId, trim($token)));
        $this->set('exculdeMainHeaderDiv', true);
        $this->_template->render(true, false);
    }

    public function resetPasswordSetup()
    {
        $newPwd = FatApp::getPostedData('new_pwd');
        $userId = FatApp::getPostedData('user_id', FatUtility::VAR_INT);
        $token = FatApp::getPostedData('token', FatUtility::VAR_STRING);

        if ($userId < 1 && strlen(trim($token)) < 20) {
            FatUtility::dieJsonError(Labels::getLabel('ERR_REQUEST_IS_INVALID_OR_EXPIRED', $this->siteLangId));
        }

        /* Restrict to change password for demo user on demo URL. */
        if (CommonHelper::demoUrl() && 4 == $userId) {
            FatUtility::dieJsonError(Labels::getLabel('ERR_YOU_ARE_NOT_ALLOWED_TO_CHANGE_PASSWORD_FOR_DEMO', $this->siteLangId));
        }

        $frm = $this->getResetPwdForm($userId, $token);
        $post = $frm->getFormDataFromArray(FatApp::getPostedData());

        if ($post == false) {
            FatUtility::dieJsonError(current($frm->getValidationErrors()));
        }


        $userAuthObj = new UserAuthentication();

        if (!$userAuthObj->checkResetLink($userId, trim($token))) {
            FatUtility::dieJsonError($userAuthObj->getError());
        }

        if (!$userAuthObj->resetUserPassword($userId, $newPwd)) {
            FatUtility::dieJsonError($userAuthObj->getError());
        }

        $userObj = new User($userId);
        if (!$userObj->verifyAccount()) {
            FatUtility::dieJsonError($userObj->getError());
        }

        $email = new EmailHandler();

        $userObj = new User($userId);
        $row = $userObj->getUserInfo(array(User::tblFld('name'), User::DB_TBL_CRED_PREFIX . 'email', 'user_phone_dcode', 'user_phone'), '', false);
        $row['link'] = UrlHelper::generateFullUrl('GuestUser', 'loginForm');
        $email->sendResetPasswordConfirmationEmail($this->siteLangId, $row);

        $this->set('msg', Labels::getLabel('MSG_PASSWORD_CHANGED_SUCCESSFULLY', $this->siteLangId));
        if (true === MOBILE_APP_API_CALL) {
            $this->_template->render();
        }
        $this->_template->render(false, false, 'json-success.php');
    }

    public function resendOtp($userId, $getOtpOnly = 0)
    {
        $userId = FatUtility::int($userId);
        $userObj = new User($userId);
        if (false == $userObj->resendOtp()) {
            $resp = LibHelper::formatResponse(applicationConstants::FAILURE, $userObj->getError(), [], LibHelper::RC_BAD_REQUEST);
            LibHelper::dieJsonResponse($resp);
        }

        $getOtpOnly = (true === MOBILE_APP_API_CALL) ? applicationConstants::YES : $getOtpOnly;
        if (0 < $getOtpOnly) {
            $this->set('data', ['user_id' => $userId]);
            $this->set('msg', Labels::getLabel('MSG_OTP_SENT!_PLEASE_CHECK_YOUR_PHONE.', $this->siteLangId));
            if (true === MOBILE_APP_API_CALL) {
                $this->_template->render();
            }
            $this->_template->render(false, false, 'json-success.php');
        }
        $this->otpForm($userId);
    }

    public function getLoginOtp()
    {
        $phone = FatApp::getPostedData('username', FatUtility::VAR_INT, 0);
        $phoneDialCode = FatApp::getPostedData('username_dcode', FatUtility::VAR_STRING, '');

        if (1 > $phone || '' == $phoneDialCode) {
            $resp = LibHelper::formatResponse(applicationConstants::FAILURE, Labels::getLabel('ERR_INVALID_REQUEST', $this->siteLangId));
            LibHelper::dieJsonResponse($resp);
        }

        $userId = $this->registerByPhone();

        if (1 > $userId) {
            $phoneDialCode = false === strpos($phoneDialCode, '+') ? '+' . trim($phoneDialCode) : trim($phoneDialCode);
            $userPhone = $phoneDialCode . $phone;
            $user = new User();
            $row = $user->checkUserByPhoneOrUserName($userPhone, $userPhone);
            if (empty($row)) {
                $resp = LibHelper::formatResponse(applicationConstants::FAILURE, Labels::getLabel('ERR_INVALID_USER', $this->siteLangId), [], LibHelper::RC_NOT_FOUND);
                LibHelper::dieJsonResponse($resp);
            }

            if (1 > $row['credential_verified']) {
                $message = Labels::getLabel('ERR_THIS_PHONE_NUMBER_IS_NOT_VERIFIED_YET._DO_YOU_WANT_TO_CONTINUE?_{CONTINUE-BTN}', $this->siteLangId);
                $replacements = [
                    '{CONTINUE-BTN}' => '<a class="btn btn-outline-white" href="javascript:void(0);" onclick="loginPopupOtp(' . $row['user_id'] . ', ' . applicationConstants::NO . ')">' . Labels::getLabel('BTN_PROCEED', $this->siteLangId) . '</a>'
                ];
                $msg = CommonHelper::replaceStringData($message, $replacements);
                $resp = LibHelper::formatResponse(applicationConstants::FAILURE, $msg, ['user_id' => $row['user_id']], LibHelper::RC_UNAUTHORIZED);
                LibHelper::dieJsonResponse($resp);
            }

            $userId = $row['user_id'];
            $this->resendOtp($userId, 1);
        }
    }

    public function otpForm($userId = 0)
    {
        $userId = FatUtility::int($userId);
        if (1 > $userId && !isset($_SESSION[UserAuthentication::TEMP_SESSION_ELEMENT_NAME]['otpUserId'])) {
            FatApp::redirectUser(UrlHelper::generateUrl('GuestUser', 'loginForm', [], CONF_WEBROOT_FRONTEND));
        }

        $userId = 0 < $userId ? $userId : $_SESSION[UserAuthentication::TEMP_SESSION_ELEMENT_NAME]['otpUserId'];

        $frm = $this->getOtpForm();
        $frm->fill(['user_id' => $userId]);
        $this->set('frm', $frm);
        $json['html'] = $this->_template->render(false, false, 'guest-user/otp-form.php', true, false);
        FatUtility::dieJsonSuccess($json);
    }

    public function configureEmail()
    {
        if (!UserAuthentication::isUserLogged()) {
            Message::addErrorMessage(Labels::getLabel('ERR_PLEASE_LOGIN_TO_CONFIGURE_EMAIL', $this->siteLangId));
            FatApp::redirectUser(UrlHelper::generateUrl('GuestUser', 'loginForm', [], CONF_WEBROOT_FRONTEND));
        }
        $userId = UserAuthentication::getLoggedUserId();
        $userObj = new User($userId);
        $userInfo = $userObj->getUserInfo(array(), true, false);
        if (!empty($userInfo['credential_email']) || !empty($userInfo['user_phone'])) {
            Message::addErrorMessage(Labels::getLabel('ERR_INVALID_ACCESS', $this->siteLangId));
            FatApp::redirectUser(UrlHelper::generateUrl('', '', [], CONF_WEBROOT_DASHBOARD, null, false, false, false));
        }
        $this->set('userInfo', $userInfo);
        $this->set('canSendSms', false);
        $this->set('newEmailToVerify', $_SESSION[UserAuthentication::SESSION_ELEMENT_NAME]['newEmailToVerify'] ?? '');
        $this->set('exculdeMainHeaderDiv', true);
        $this->_template->render(true, false);
    }

    public function changeEmailForm()
    {
        $frm = $this->getChangeEmailForm(false);

        $this->set('frm', $frm);
        $this->set('siteLangId', $this->siteLangId);
        $this->set('canSendSms', SmsArchive::canSendSms(SmsTemplate::LOGIN));
        $this->_template->render(false, false, 'guest-user/change-email-form.php');
    }

    public function configurePhoneForm()
    {
        $frm = $this->getPhoneNumberForm();
        $this->set('frm', $frm);
        $this->set('updatePhnFrm', applicationConstants::YES);
        $this->set('siteLangId', $this->siteLangId);
        $this->_template->render(false, false, 'guest-user/change-phone-form.php');
    }

    public function updateEmail()
    {
        $emailFrm = $this->getChangeEmailForm(false);
        $post = $emailFrm->getFormDataFromArray(FatApp::getPostedData());

        if (false === $post) {
            $message = current($emailFrm->getValidationErrors());
            LibHelper::dieJsonError($message);
        }

        if ($post['new_email'] != $post['conf_new_email']) {
            $message = Labels::getLabel('ERR_NEW_EMAIL_CONFIRM_EMAIL_DOES_NOT_MATCH', $this->siteLangId);
            LibHelper::dieJsonError($message);
        }

        $usr = new User();
        $srch = $usr->getUserSearchObj(array('uc.credential_email'));
        $srch->addCondition('uc.credential_email', '=', $post['new_email']);
        $srch->doNotCalculateRecords();
        $srch->setPageSize(1);

        $rs = $srch->getResultSet();
        $record = FatApp::getDb()->fetch($rs);
        if ($record) {
            $message = Labels::getLabel("ERR_DUPLICATE_EMAIL", $this->siteLangId);
            LibHelper::dieJsonError($message);
        }

        $userObj = new User(UserAuthentication::getLoggedUserId());
        $srch = $userObj->getUserSearchObj(array('user_id', 'credential_email', 'user_name', 'user_phone_dcode', 'user_phone'));
        $srch->doNotCalculateRecords();
        $srch->setPageSize(1);
        $rs = $srch->getResultSet();

        if (!$rs) {
            $message = Labels::getLabel('ERR_INVALID_REQUEST', $this->siteLangId);
            LibHelper::dieJsonError($message);
        }

        $data = FatApp::getDb()->fetch($rs, 'user_id');
        if ($data === false || $data['credential_email'] != '') {
            $message = Labels::getLabel('ERR_INVALID_REQUEST', $this->siteLangId);
            LibHelper::dieJsonError($message);
        }

        $dialCode = array_key_exists('user_phone_dcode', $data) ? ValidateElement::formatDialCode($data['user_phone_dcode']) : '';
        $phone = array_key_exists('user_phone', $data) ? $data['user_phone'] : '';
        $arr = array(
            'user_name' => $data['user_name'],
            'user_phone_dcode' => $dialCode,
            'user_phone' => $phone,
            'user_email' => $post['new_email']
        );

        if (!$this->userEmailVerifications($userObj, $arr, true)) {
            $message = Labels::getLabel('ERR_ERROR_IN_SENDING_VERIFICATION_EMAIL', $this->siteLangId);
            LibHelper::dieJsonError($message);
        }

        $_SESSION[UserAuthentication::SESSION_ELEMENT_NAME]['newEmailToVerify'] = $arr['user_email'];

        $this->set('msg', Labels::getLabel('MSG_UPDATE_EMAIL_REQUEST_SENT_SUCCESSFULLY._YOU_NEED_TO_VERIFY_YOUR_NEW_EMAIL_ADDRESS_BEFORE_ACCESSING_OTHER_MODULES', $this->siteLangId));
        if (true === MOBILE_APP_API_CALL) {
            $this->_template->render();
        }
        $this->_template->render(false, false, 'json-success.php');
    }

    public function logout()
    {
        UserAuthentication::logout();
        if (true === MOBILE_APP_API_CALL) {
            $fcmToken = FatApp::getPostedData('fcmToken', FatUtility::VAR_STRING, '');
            $userType = FatApp::getPostedData('userType', FatUtility::VAR_INT, User::USER_TYPE_BUYER);
            if (empty($fcmToken)) {
                LibHelper::dieJSONError(Labels::getLabel('ERR_INVALID_REQUEST', $this->siteLangId));
            }

            $values = User::getUserAuthFcmFormattedData($userType, $fcmToken, null, applicationConstants::NO);
            $where = array('smt' => 'uauth_fcm_id = ?', 'vals' => [$fcmToken]);
            if (!UserAuthentication::updateFcmDeviceToken($values, $where)) {
                LibHelper::dieJsonError(Labels::getLabel('ERR_UNABLE_TO_UPDATE_FCM_TOKEN', $this->siteLangId));
            }

            $this->_template->render();
        }

        FatApp::redirectUser(UrlHelper::generateUrl('GuestUser', 'loginForm', [], CONF_WEBROOT_FRONTEND, null, false, false, true, $this->siteLangId));
    }

    private function getForgotForm($withPhone = 0)
    {
        $frm = new Form('frmPwdForgot');
        $frm->addHiddenField('', 'withPhone', $withPhone);
        if (1 > $withPhone) {
            $frm->addRequiredField(Labels::getLabel('FRM_ENTER_YOUR_EMAIL_ADDRESS', $this->siteLangId), 'user_email_username');
        } else {
            $frm->addHiddenField('', 'user_phone_dcode');
            $phnFld = $frm->addRequiredField(Labels::getLabel('FRM_PHONE_NUMBER', $this->siteLangId), 'user_phone', '', array('placeholder' => Labels::getLabel('FRM_PHONE_NUMBER', $this->siteLangId), 'class' => 'phone-js'));
            $phnFld->requirements()->setRegularExpressionToValidate(ValidateElement::PHONE_REGEX);
        }

        CommonHelper::addCaptchaField($frm);
        $label = (1 > $withPhone) ? Labels::getLabel('BTN_SUBMIT', $this->siteLangId) : Labels::getLabel('BTN_GET_OTP', $this->siteLangId);
        $frm->addSubmitButton('', 'btn_submit', $label);
        return $frm;
    }

    private function getResetPwdForm($uId, $token)
    {
        $frm = new Form('frmResetPwd');
        $frm->addTextBox(Labels::getLabel('FRM_Username', $this->siteLangId), 'user_name');
        $fld_np = $frm->addPasswordField(Labels::getLabel('FRM_NEW_PASSWORD', $this->siteLangId), 'new_pwd');
        $fld_np->htmlAfterField = '<p class="form-text text-muted">' . sprintf(Labels::getLabel('LBL_EXAMPLE_PASSWORD', $this->siteLangId), 'User@123') . '</p>';
        $fld_np->requirements()->setRequired();
        $fld_np->requirements()->setRegularExpressionToValidate(ValidateElement::PASSWORD_REGEX);
        $fld_np->requirements()->setCustomErrorMessage(Labels::getLabel('ERR_PASSWORD_MUST_BE_EIGHT_CHARACTERS_LONG_AND_ALPHANUMERIC', $this->siteLangId));
        $fld_cp = $frm->addPasswordField(Labels::getLabel('FRM_CONFIRM_NEW_PASSWORD', $this->siteLangId), 'confirm_pwd');
        $fld_cp->requirements()->setRequired();
        $fld_cp->requirements()->setCompareWith('new_pwd', 'eq', '');

        $frm->addHiddenField('', 'user_id', $uId, array('id' => 'user_id'));
        $frm->addHiddenField('', 'token', $token, array('id' => 'token'));

        $frm->addSubmitButton('', 'btn_submit', Labels::getLabel('BTN_RESET_PASSWORD', $this->siteLangId));
        return $frm;
    }

    public function redirectAbandonedCartUser($userId, $selProdId, $reminderEmail = false)
    {
        $userId = FatUtility::int($userId);
        $selProdId = FatUtility::int($selProdId);
        if (!UserAuthentication::isUserLogged() && !UserAuthentication::isGuestUserLogged()) {
            FatApp::redirectUser(UrlHelper::generateUrl('GuestUser', 'loginForm', [], CONF_WEBROOT_FRONTEND));
        }
        if ($reminderEmail == true) {
            FatApp::redirectUser(UrlHelper::generateUrl('Cart'));
        }

        $cart = new Cart($userId);
        if (!$cart->hasProducts()) {
            FatApp::redirectUser(UrlHelper::generateUrl('Products', 'view', array($selProdId)));
        }
        $cartProducts = $cart->getProducts($this->siteLangId);
        $found = false;
        foreach ($cartProducts as $key => $data) {
            if ($data['selprod_id'] == $selProdId) {
                $found = true;
                break;
            }
        }
        if ($found == true) {
            FatApp::redirectUser(UrlHelper::generateUrl('Cart'));
        } else {
            FatApp::redirectUser(UrlHelper::generateUrl('Products', 'view', array($selProdId)));
        }
    }

    /**
     * validateAuthLoginRequest
     *
     * @return void
     */
    private function validateAuthLoginRequest(bool $authTokenRequest = false)
    {
        $maketPlaceAuthToken = $_SERVER['HTTP_EEC_TOKEN'];
        $uAuth = new UserAuthentication();
        if (false === $uAuth->validateMarketplaceAuthToken($maketPlaceAuthToken)) {
            $msg = Labels::getLabel("ERR_UNAUTHORIZED_ACCESS", $this->siteLangId);
            $resp = LibHelper::formatResponse(Plugin::RETURN_FALSE, $msg);
            FatUtility::dieJsonError($resp);
        }

        $requestParam = "";
        if (true === $authTokenRequest) {
            $requestParam = $this->username = FatApp::getPostedData('username', FatUtility::VAR_STRING, '');
        } else {
            $requestParam = $this->authToken = FatApp::getPostedData('authToken', FatUtility::VAR_STRING, '');
        }

        if (empty($requestParam)) {
            $msg = Labels::getLabel('ERR_INVALID_REQUEST', $this->siteLangId);
            $resp = LibHelper::formatResponse(Plugin::RETURN_FALSE, $msg);
            FatUtility::dieJsonError($resp);
        }
    }

    /**
     * getAuthToken - This function is used by Marketplace API (E.g. EasyEcom) if access token is expired.
     * 
     * @return void
     */
    public function getAuthToken()
    {
        $this->validateAuthLoginRequest(true);

        $uObj = new User();
        $data = $uObj->checkUserByEmailOrUserName($this->username, $this->username);
        if (false === $data || empty($data)) {
            $msg = Labels::getLabel('ERR_INVALID_USER', $this->siteLangId);
            $resp = LibHelper::formatResponse(Plugin::RETURN_FALSE, $msg);
            FatUtility::dieJsonError($resp);
        }
        $userId = array_key_exists('user_id', $data) ? $data['user_id'] : '';
        $uObj->setMainTableRecordId($userId);

        if (!$newAuthToken = $uObj->setMobileAppToken(UserAuthentication::TOKEN_AGE_IN_DAYS)) {
            FatUtility::dieJsonError(Labels::getLabel('ERR_INVALID_REQUEST', $this->siteLangId));
        }

        if (false === $uObj->updateUserMeta('seller_auth_token', $newAuthToken)) {
            $resp = LibHelper::formatResponse(Plugin::RETURN_FALSE, $uObj->getError());
            FatUtility::dieJsonError($resp);
        }

        $msg = Labels::getLabel("MSG_SUCCESS", $this->siteLangId);
        $resp = LibHelper::formatResponse(Plugin::RETURN_TRUE, $msg, ['authToken' => $newAuthToken]);
        CommonHelper::jsonEncodeUnicode($resp, true);
    }

    /**
     * getAuthLoginToken - This function is used by Marketplace API (E.g. EasyEcom). It is being used when easyecom submit's user temp token from cookie while providing access to their account location. 
     * 
     * @return void
     */
    public function getAuthLoginToken()
    {
        $this->validateAuthLoginRequest();

        $srch = new SearchBase(User::DB_TBL_USR_MOBILE_TEMP_TOKEN);
        $srch->addCondition('uttr_token', '=', $this->authToken);
        $srch->addCondition('uttr_expiry', '>=', date('Y-m-d H:i:s'));
        $srch->addMultipleFields(['uttr_user_id']);
        $srch->doNotCalculateRecords();
        $srch->setPagesize(1);
        $rs = $srch->getResultSet();
        $row = FatApp::getDb()->fetch($rs);
        if (empty($row)) {
            $msg = Labels::getLabel("ERR_INVALID_REQUEST_TOKEN_OR_EXPIRED", $this->siteLangId);
            $resp = LibHelper::formatResponse(Plugin::RETURN_FALSE, $msg);
            FatUtility::dieJsonError($resp);
        }

        $user = new User($row['uttr_user_id']);
        if (false === $user->deleteUserAPITempToken()) {
            $resp = LibHelper::formatResponse(Plugin::RETURN_FALSE, $user->getError());
            FatUtility::dieJsonError($resp);
        }

        $authToken = User::getUserMeta($row['uttr_user_id'], 'seller_auth_token');
        $msg = Labels::getLabel("MSG_SUCCESS", $this->siteLangId);
        $resp = LibHelper::formatResponse(Plugin::RETURN_TRUE, $msg, ['authToken' => $authToken]);
        CommonHelper::jsonEncodeUnicode($resp, true);
    }

    /**
     * registerByPhone
     *
     * @param  mixed $registerIfNotExists
     * @return void
     */
    private function registerByPhone(int $registerIfNotExists = 1)
    {
        $registerByPhone = FatApp::getPostedData('registerByPhone', FatUtility::VAR_INT, 0);
        if (1 > $registerByPhone) {
            return 0;
        }

        $phone = FatApp::getPostedData('username', FatUtility::VAR_INT, 0);
        $phoneDialCode = FatApp::getPostedData('username_dcode', FatUtility::VAR_STRING, '');

        $userId = 0;
        $row = [];
        if (0 < $registerIfNotExists) {
            if (1 > $phone || '' == $phoneDialCode) {
                $resp = LibHelper::formatResponse(applicationConstants::FAILURE, Labels::getLabel('ERR_INVALID_REQUEST', $this->siteLangId));
                LibHelper::dieJsonResponse($resp);
            }

            $skipRegister = false;

            $phoneDialCode = false === strpos($phoneDialCode, '+') ? '+' . trim($phoneDialCode) : trim($phoneDialCode);
            $userPhone = $phoneDialCode . $phone;
            $user = new User();
            $row = $user->checkUserByPhoneOrUserName($userPhone, $userPhone);
            if (!empty($row)) {
                if (applicationConstants::NO == $row['user_deleted'] && applicationConstants::YES == $row['credential_verified']) {
                    $resp = LibHelper::formatResponse(applicationConstants::FAILURE, Labels::getLabel('ERR_DUPLICATE_USER', $this->siteLangId), [], LibHelper::RC_NOT_FOUND);
                    LibHelper::dieJsonResponse($resp);
                } else if (applicationConstants::YES == $row['user_deleted'] && applicationConstants::YES == $row['credential_verified']) {
                    $resp = LibHelper::formatResponse(applicationConstants::FAILURE, Labels::getLabel('ERR_YOU_CANNOT_ACCESS_YOUR_ACCOUNT._PLEASE_CONTACT_ADMIN.', $this->siteLangId), [], LibHelper::RC_NOT_FOUND);
                    LibHelper::dieJsonResponse($resp);
                } else {
                    $skipRegister = true;
                }
            }
        }

        if (empty($row) && false === $skipRegister) {
            $dialCode = ValidateElement::formatDialCode($phoneDialCode);
            $userName = str_replace('+', '', $dialCode) . $phone;

            $post['user_phone'] = $phone;
            $post['user_phone_dcode'] = $phoneDialCode;

            $post['user_username'] = $userName;
            $post['user_name'] = $userName;

            $post['user_is_buyer'] = User::USER_TYPE_BUYER;
            $post['user_preferred_dashboard'] = User::USER_BUYER_DASHBOARD;
            $post['user_registered_initially_for'] = User::USER_TYPE_BUYER;
            $post['user_is_supplier'] = (FatApp::getConfig("CONF_ADMIN_APPROVAL_SUPPLIER_REGISTRATION", FatUtility::VAR_INT, 1) || FatApp::getConfig("CONF_ACTIVATE_SEPARATE_SIGNUP_FORM", FatUtility::VAR_INT, 1)) ? 0 : 1;
            $post['user_is_advertiser'] = (FatApp::getConfig("CONF_ADMIN_APPROVAL_SUPPLIER_REGISTRATION", FatUtility::VAR_INT, 1) || FatApp::getConfig("CONF_ACTIVATE_SEPARATE_SIGNUP_FORM", FatUtility::VAR_INT, 1)) ? 0 : 1;
            $post['user_active'] = FatApp::getConfig('CONF_ADMIN_APPROVAL_REGISTRATION', FatUtility::VAR_INT, 1) ? 0 : 1;
            $post['user_verify'] = FatApp::getConfig('CONF_EMAIL_VERIFICATION_REGISTRATION', FatUtility::VAR_INT, 1) ? 0 : 1;
            $post['referralToken'] = FatApp::getPostedData('referralToken', FatUtility::VAR_STRING, '');
            $userObj = new User();
            if (!$userId = $userObj->saveUserData($post, false, MOBILE_APP_API_CALL)) {
                $resp = LibHelper::formatResponse(applicationConstants::FAILURE, $userObj->getError());
                LibHelper::dieJsonResponse($resp);
            }

            if (0 < $userId) {
                $this->set('data', ['user_id' => $userId]);
                $this->set('msg', Labels::getLabel('MSG_OTP_SENT!_PLEASE_CHECK_YOUR_PHONE.', $this->siteLangId));
                if (true === MOBILE_APP_API_CALL) {
                    $this->_template->render();
                }
                $this->_template->render(false, false, 'json-success.php');
            }
        }
        return $userId;
    }

    public function appversion()
    {
        $deviceType = isset($_SERVER['HTTP_DEVICETYPE']) ? $_SERVER['HTTP_DEVICETYPE'] : '';
        $packageName = isset($_SERVER['HTTP_PACKAGENAME']) ? $_SERVER['HTTP_PACKAGENAME'] : '';
        if (empty($deviceType)) {
            $message = Labels::getLabel('ERR_INVALID_DEVICE_TYPE', $this->siteLangId);
            FatUtility::dieJsonError($message);
        }
        if (empty($packageName)) {
            $message = Labels::getLabel('ERR_INVALID_PACKAGE_NAME', $this->siteLangId);
            FatUtility::dieJsonError($message);
        }

        $srch = new AppReleaseVersionSearch();
        $versionRow = $srch->searchVersions(['arv_app_type' => $deviceType, 'arv_package_name' => $packageName]);
        if (empty($versionRow)) {
            $message = Labels::getLabel('ERR_INVALID_DEVICE_TYPE_OR_PACKAGE_NAME', $this->siteLangId);
            FatUtility::dieJsonError($message);
        }
        $getAppVersionDetail =  [
            'app_version' => $versionRow['arv_app_version'],
            'is_version_critical' => $versionRow['arv_is_critical'],
            'version_features' => $versionRow['arv_description'],
            'appstore_url' => $versionRow['arv_store_url'],
        ];
        $this->set('versionDetails', $getAppVersionDetail);
        $this->_template->render();
    }
}
