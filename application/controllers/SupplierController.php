<?php

class SupplierController extends MyAppController
{
    private const SESSION_SELECTED_SELLER_PLAN = 'selected_seller_subscription_plan_id';

    public function __construct($action)
    {
        parent::__construct($action);
    }

    public function index()
    {
        $this->redirectToSubscriptionCheckoutIfPlanSelected();
        if (UserAuthentication::isUserLogged() && (User::isSeller() || User::isSigningUpForSeller())) {
            FatApp::redirectUser(UrlHelper::generateUrl('seller', '', [], CONF_WEBROOT_DASHBOARD, null, false, false, false));
        }
        if (isset($_SESSION['registered_supplier']['id'])) {
            FatApp::redirectUser(UrlHelper::generateUrl('supplier', 'account'));
        }
        if (UserAuthentication::isUserLogged()) {
            if (User::canViewSupplierTab()) {
                FatApp::redirectUser(UrlHelper::generateUrl('account', 'supplierApprovalForm', [], CONF_WEBROOT_DASHBOARD, null, false, false, false));
            }
            Message::addErrorMessage(Labels::getLabel('ERR_YOU_ARE_ALREADY_LOGGED_IN._PLEASE_LOGOUT_AND_REGISTER_FOR_SELLER.', $this->siteLangId));
            FatApp::redirectUser(UrlHelper::generateUrl('account', '', [], CONF_WEBROOT_DASHBOARD, null, false, false, false));
        }
        if (!FatApp::getConfig("CONF_ACTIVATE_SEPARATE_SIGNUP_FORM", FatUtility::VAR_INT, 1)) {
            FatApp::redirectUser(UrlHelper::generateUrl('guest-user', 'login-form', array(applicationConstants::YES)));
        }
        $sellerFrm = $this->getSellerForm();
        $obj = new Extrapage();
        $formText = $obj->getContentByPageType(Extrapage::SELLER_PAGE_FORM_TEXT, $this->siteLangId);
        $block1 = $obj->getContentByPageType(Extrapage::SELLER_PAGE_BLOCK1, $this->siteLangId);
        $block2 = $obj->getContentByPageType(Extrapage::SELLER_PAGE_BLOCK2, $this->siteLangId);
        $block3 = $obj->getContentByPageType(Extrapage::SELLER_PAGE_BLOCK3, $this->siteLangId);
        $slogan = $obj->getContentByPageType(Extrapage::SELLER_BANNER_SLOGAN, $this->siteLangId);
        if (!empty($slogan)) {
            $slogan['epage_extra_info'] = !empty($slogan['epage_extra_info']) ? json_decode($slogan['epage_extra_info'], true) : [];
        }

        $srch = FaqCategory::getSearchObject($this->siteLangId);
        $srch->joinTable('tbl_faqs', 'LEFT OUTER JOIN', 'faq_faqcat_id = faqcat_id and faq_active = ' . applicationConstants::ACTIVE . '  and faq_deleted = ' . applicationConstants::NO);
        $srch->joinTable('tbl_faqs_lang', 'LEFT OUTER JOIN', 'faqlang_faq_id = faq_id');
        $srch->addCondition('faqlang_lang_id', '=', $this->siteLangId);
        $srch->addCondition('faqcat_active', '=', applicationConstants::ACTIVE);
        $srch->addCondition('faqcat_type', '=', FaqCategory::SELLER_PAGE);
        $srch->getResultSet();

        $seller_navigation_left = Navigation::getNavigation(Navigations::NAVTYPE_SELLER_LEFT);
        $this->set('seller_navigation_left', $seller_navigation_left);
        $this->set('formText', $formText);
        $this->set('faqCount', $srch->recordCount());
        $this->set('block1', $block1);
        $this->set('block2', $block2);
        $this->set('block3', $block3);
        $this->set('slogan', $slogan);
        $this->set('sellerFrm', $sellerFrm);
        $this->set('faqSearchFrm', $this->getFaqSearchForm());
        $this->_template->render();
    }

    private function getFaqSearchForm()
    {
        $frm = new Form('frmSearchFaqs');
        $frm->addTextbox(Labels::getLabel('FRM_ENTER_YOUR_QUESTION', $this->siteLangId), 'question');
        $frm->addSubmitButton('', 'btn_submit', '');
        return $frm;
    }

    public function account()
    {
        $this->redirectToSubscriptionCheckoutIfPlanSelected();
        if (UserAuthentication::isUserLogged()) {
            FatApp::redirectUser(UrlHelper::generateUrl('account', '', [], CONF_WEBROOT_DASHBOARD, null, false, false, false));
        }
        if (!FatApp::getConfig("CONF_ACTIVATE_SEPARATE_SIGNUP_FORM", FatUtility::VAR_INT, 1)) {
            FatApp::redirectUser(UrlHelper::generateUrl('guest-user', 'registration-form'));
        }
        $frm = $this->getSellerForm();

        $post = FatApp::getPostedData();
        $postedData = [];
        if (!empty($post)) {
            $postedData = $frm->getFormDataFromArray($post);
        }

        $obj = new Extrapage();
        $slogan = $obj->getContentByPageType(Extrapage::SELLER_BANNER_SLOGAN, $this->siteLangId);
        if (!empty($slogan)) {
            $slogan['epage_extra_info'] = !empty($slogan['epage_extra_info']) ? json_decode($slogan['epage_extra_info'], true) : [];
        }
        $this->set('slogan', $slogan);
        $this->set('postedData', $postedData);
        $this->set('siteLangId', $this->siteLangId);
        $this->_template->render();
    }

    public function packages()
    {
        if (!FatApp::getConfig('CONF_ENABLE_SELLER_SUBSCRIPTION_MODULE', FatUtility::VAR_INT, 0)) {
            Message::addErrorMessage(Labels::getLabel('ERR_INVALID_REQUEST', $this->siteLangId));
            FatApp::redirectUser(UrlHelper::generateUrl());
        }

        $this->redirectToSubscriptionCheckoutIfPlanSelected();
        $packagesArr = SellerPackages::getSellerVisiblePackages($this->siteLangId, true);
        foreach ($packagesArr as $key => $package) {
            $packagesArr[$key]['plans'] = SellerPackagePlans::getSellerVisiblePackagePlans($package[SellerPackages::DB_TBL_PREFIX . 'id']);
            $packagesArr[$key]['cheapPlan'] = SellerPackagePlans::getCheapestPlanByPackageId($package[SellerPackages::DB_TBL_PREFIX . 'id']);
        }

        $obj = new Extrapage();
        $pageData = $obj->getContentByPageType(Extrapage::SUBSCRIPTION_PAGE_BLOCK, $this->siteLangId);

        $this->set('pageData', $pageData);
        $this->set('packagesArr', $packagesArr);
        $this->set('pendingPlanId', FatUtility::int($_SESSION[static::SESSION_SELECTED_SELLER_PLAN] ?? 0));
        $this->_template->render();
    }

    public function selectPackage($spplanId = 0)
    {
        if (!FatApp::getConfig('CONF_ENABLE_SELLER_SUBSCRIPTION_MODULE', FatUtility::VAR_INT, 0)) {
            Message::addErrorMessage(Labels::getLabel('ERR_INVALID_REQUEST', $this->siteLangId));
            FatApp::redirectUser(UrlHelper::generateUrl());
        }

        $spplanId = FatUtility::int(FatApp::getPostedData('spplan_id', FatUtility::VAR_INT, $spplanId));
        if (1 > $spplanId) {
            Message::addErrorMessage(Labels::getLabel('LBL_INVALID_PLAN_REQUEST', $this->siteLangId));
            FatApp::redirectUser(UrlHelper::generateUrl('supplier', 'packages'));
        }

        $srch = new SellerPackagePlansSearch($this->siteLangId);
        $srch->addCondition(SellerPackagePlans::DB_TBL_PREFIX . 'active', '=', applicationConstants::ACTIVE);
        $srch->addCondition(SellerPackagePlans::DB_TBL_PREFIX . 'id', '=', $spplanId);
        $srch->addMultipleFields(array('spplan_id'));
        $srch->setPageSize(1);
        $srch->doNotCalculateRecords();
        $sellerPlanRow = FatApp::getDb()->fetch($srch->getResultSet());
        if (!$sellerPlanRow) {
            Message::addErrorMessage(Labels::getLabel('LBL_INVALID_REQUEST', $this->siteLangId));
            FatApp::redirectUser(UrlHelper::generateUrl('supplier', 'packages'));
        }
        $spplanId = FatUtility::int($sellerPlanRow['spplan_id']);
        $_SESSION[static::SESSION_SELECTED_SELLER_PLAN] = $spplanId;

        if (!UserAuthentication::isUserLogged()) {
            Message::addMessage(Labels::getLabel('MSG_Please_complete_seller_registration_to_continue', $this->siteLangId));
            FatApp::redirectUser(UrlHelper::generateUrl('supplier', 'account'));
        }

        if (!(User::isSeller() || User::isSigningUpForSeller())) {
            Message::addMessage(Labels::getLabel('MSG_Please_complete_seller_profile_to_continue', $this->siteLangId));
            FatApp::redirectUser(UrlHelper::generateUrl('account', 'supplierApprovalForm', [], CONF_WEBROOT_DASHBOARD, null, false, false, false));
        }

        $userId = User::getUserParentId(UserAuthentication::getLoggedUserId(true));
        if (!UserPrivilege::canSellerUpgradeOrDowngradePlan($userId, $spplanId, $this->siteLangId)) {
            unset($_SESSION[static::SESSION_SELECTED_SELLER_PLAN]);
            Message::addErrorMessage(Labels::getLabel('LBL_INVALID_PLAN_REQUEST', $this->siteLangId));
            FatApp::redirectUser(UrlHelper::generateUrl('seller', 'packages', [], CONF_WEBROOT_DASHBOARD, null, false, false, false));
        }

        $subsObj = new SubscriptionCart($userId);
        $subsObj->add($spplanId);
        $subsObj->adjustPreviousPlan($this->siteLangId);

        unset($_SESSION[static::SESSION_SELECTED_SELLER_PLAN]);
        FatApp::redirectUser(UrlHelper::generateUrl('SubscriptionCheckout', '', [], CONF_WEBROOT_DASHBOARD, null, false, false, false));
    }

    public function form()
    {
        if (UserAuthentication::isUserLogged()) {
            Message::addErrorMessage(Labels::getLabel('ERR_USER_ALREADY_LOGGED_IN', $this->siteLangId));
            FatUtility::dieWithError(Message::getHtml());
        }

        $userId = $this->getRegisteredSupplierId();
        if ($userId > 0) {
            $this->profileActivationForm($userId);
        } else {
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
            $frm = $this->getSellerForm();
            $post = $frm->getFormDataFromArray(FatApp::getPostedData());
            $registrationFrm = $this->getSellerRegistrationForm();
            if (isset($post['btn_submit'])) {
                unset($post['btn_submit']);
            }
            $registrationFrm->fill($post);
            $this->set('termsAndConditionsLinkHref', $termsAndConditionsLinkHref);
            $this->set('frm', $registrationFrm);
            $this->set('siteLangId', $this->siteLangId);
            $this->_template->render(false, false);
        }
    }

    public function register()
    {
        if (UserAuthentication::isUserLogged()) {
            FatUtility::dieJsonError(Labels::getLabel('ERR_USER_ALREADY_LOGGED_IN', $this->siteLangId));
        }

        $frm = $this->getSellerRegistrationForm();
        $post = $frm->getFormDataFromArray(FatApp::getPostedData());
        if ($post == false) {
            FatUtility::dieJsonError(current($frm->getValidationErrors()));
        }

        if (!ValidateElement::username($post['user_username'])) {
            $msg = Labels::getLabel('ERR_USERNAME_MUST_BE_THREE_CHARACTERS_LONG_AND_ALPHANUMERIC', $this->siteLangId);
            if (FatUtility::isAjaxCall()) {
                FatUtility::dieWithError($msg);
            }

            Message::addErrorMessage($msg);
            FatApp::redirectUser(UrlHelper::generateUrl('Supplier', 'Account'));
            return;
        }

        if (!ValidateElement::password($post['user_password'])) {
            $msg = Labels::getLabel('ERR_PASSWORD_MUST_BE_EIGHT_CHARACTERS_LONG_AND_ALPHANUMERIC', $this->siteLangId);
            if (FatUtility::isAjaxCall()) {
                FatUtility::dieWithError($msg);
            }

            Message::addErrorMessage($msg);
            FatApp::redirectUser(UrlHelper::generateUrl('Supplier', 'Account'));
            return;
        }

        $userObj = new User();
        $db = FatApp::getDb();
        $db->startTransaction();
        if (FatApp::getConfig("CONF_ACTIVATE_SEPARATE_SIGNUP_FORM", FatUtility::VAR_INT, 1) && !FatApp::getConfig("CONF_ADMIN_APPROVAL_SUPPLIER_REGISTRATION", FatUtility::VAR_INT, 1)) {
            $post['user_is_supplier'] = 1;
            $post['user_is_advertiser'] = 1;
        }
        $post['user_is_buyer'] = 1;

        $post['user_registered_initially_for'] = User::USER_TYPE_SELLER;
        if (FatApp::getConfig("CONF_ACTIVATE_SEPARATE_SIGNUP_FORM", FatUtility::VAR_INT, 1)) {
            $post['user_is_buyer'] = 0;
            $post['user_preferred_dashboard'] = User::USER_SELLER_DASHBOARD;
        }

        $userObj->assignValues($post);

        if (!$userObj->save()) {
            $db->rollbackTransaction();
            FatUtility::dieJsonError(Labels::getLabel("ERR_USER_COULD_NOT_BE_SET", $this->siteLangId) . $userObj->getError());
        }

        $active = FatApp::getConfig('CONF_ADMIN_APPROVAL_REGISTRATION', FatUtility::VAR_INT, 1) ? 0 : 1;
        $verify = FatApp::getConfig('CONF_EMAIL_VERIFICATION_REGISTRATION', FatUtility::VAR_INT, 1) ? 0 : 1;

        if (!$userObj->setLoginCredentials($post['user_username'], $post['user_email'], $post['user_password'], $active, $verify)) {
            $db->rollbackTransaction();
            FatUtility::dieJsonError(Labels::getLabel("ERR_LOGIN_CREDENTIALS_COULD_NOT_BE_SET", $this->siteLangId) . $userObj->getError());
        }

        $referrerCodeSignup = '';
        if (isset($_COOKIE['referrer_code_signup']) && $_COOKIE['referrer_code_signup'] != '') {
            $referrerCodeSignup = $_COOKIE['referrer_code_signup'];
        }
        $affiliateReferrerCodeSignup = '';
        if (isset($_COOKIE['affiliate_referrer_code_signup']) && $_COOKIE['affiliate_referrer_code_signup'] != '') {
            $affiliateReferrerCodeSignup = $_COOKIE['affiliate_referrer_code_signup'];
        }

        $userObj->setUpRewardEntry($userObj->getMainTableRecordId(), $this->siteLangId, $referrerCodeSignup, $affiliateReferrerCodeSignup);

        if (FatApp::getPostedData('user_newsletter_signup')) {
            $api_key = FatApp::getConfig("CONF_MAILCHIMP_KEY");
            $list_id = FatApp::getConfig("CONF_MAILCHIMP_LIST_ID");
            if ($api_key == '' || $list_id == '') {
                Message::addErrorMessage(Labels::getLabel("ERR_NEWSLETTER_IS_NOT_CONFIGURED_YET,_PLEASE_CONTACT_ADMIN", $this->siteLangId));
                FatUtility::dieWithError(Message::getHtml());
            }

            try {
                MailchimpHelper::subscribe(['email' => htmlentities($post['user_email'])], $this->siteLangId);
                /* if ( empty( $subscriber['msg'] ) ) {
                Message::addErrorMessage( Labels::getLabel('MSG_Newsletter_subscription_valid_email', $siteLangId) );
                FatUtility::dieWithError( Message::getHtml() );
                } */
            } catch (Mailchimp_Error $e) {
                /* Message::addErrorMessage( $e->getMessage() );
                FatUtility::dieWithError( Message::getHtml() ); */
            }
        }

        if (FatApp::getConfig('CONF_NOTIFY_ADMIN_REGISTRATION', FatUtility::VAR_INT, 1)) {
            if (!$userObj->notifyAdminRegistration($post, $this->siteLangId)) {
                $db->rollbackTransaction();
                FatUtility::dieJsonError(Labels::getLabel("ERR_NOTIFICATION_EMAIL_COULD_NOT_BE_SENT", $this->siteLangId));
            }
        }

        //send notification to admin
        $notificationData = array(
            'notification_record_type' => Notification::TYPE_USER,
            'notification_record_id' => $userObj->getMainTableRecordId(),
            'notification_user_id' => $userObj->getMainTableRecordId(),
            'notification_label_key' => Notification::NEW_SUPPLIER_REGISTERATION_NOTIFICATION,
            'notification_added_on' => date('Y-m-d H:i:s'),
        );

        if (!Notification::saveNotifications($notificationData)) {
            $db->rollbackTransaction();
            FatUtility::dieJsonError(Labels::getLabel("ERR_NOTIFICATION_COULD_NOT_BE_SENT", $this->siteLangId));
        }

        if (FatApp::getConfig('CONF_EMAIL_VERIFICATION_REGISTRATION', FatUtility::VAR_INT, 1)) {
            if (!$userObj->userEmailVerification($post, $this->siteLangId)) {
                $db->rollbackTransaction();
                FatUtility::dieJsonError(Labels::getLabel("ERR_VERIFICATION_EMAIL_COULD_NOT_BE_SENT", $this->siteLangId));
            }
        } else {
            if (FatApp::getConfig('CONF_WELCOME_EMAIL_REGISTRATION', FatUtility::VAR_INT, 1)) {
                $link = UrlHelper::generateFullUrl('GuestUser', 'loginForm');
                if (!$userObj->userWelcomeEmailRegistration($post, $link, $this->siteLangId)) {
                    $db->rollbackTransaction();
                    FatUtility::dieJsonError(Labels::getLabel("ERR_WELCOME_EMAIL_COULD_NOT_BE_SENT", $this->siteLangId));
                }
            }
        }

        $db->commitTransaction();
        if ($verify) {
            $this->set('msg', Labels::getLabel("MSG_SUCCESS_USER_SIGNUP_VERIFIED", $this->siteLangId));
        } else {
            $this->set('msg', Labels::getLabel("MSG_SUCCESS_USER_SIGNUP", $this->siteLangId));
        }

        $userId = $userObj->getMainTableRecordId();
        $_SESSION['registered_supplier']['id'] = $userId;
        $this->set('userId', $userId);

        $redirectUrl = '';
        $selectedPlanId = FatUtility::int($_SESSION[static::SESSION_SELECTED_SELLER_PLAN] ?? 0);
        if ($selectedPlanId > 0 && $active == applicationConstants::YES && $verify == applicationConstants::YES) {
            $userData = User::getAttributesById($userId, ['user_is_supplier']);
            if (!empty($userData) && FatUtility::int($userData['user_is_supplier']) == applicationConstants::YES) {
                $auth = new UserAuthentication();
                if ($auth->login($post['user_email'], $post['user_password'], CommonHelper::getClientIp())) {
                    unset($_SESSION['registered_supplier']['id']);
                    $redirectUrl = UrlHelper::generateUrl('Supplier', 'selectPackage', [$selectedPlanId]);
                }
            }
        }
        $this->set('redirectUrl', $redirectUrl);
        $this->_template->render(false, false, 'json-success.php');
    }

    public function profileActivationForm()
    {
        if (!$userId = $this->getRegisteredSupplierId()) {
            Message::addErrorMessage(Labels::getLabel("ERR_INVALID_ACCESS", $this->siteLangId));
            FatUtility::dieWithError(Message::getHtml());
        }

        if (UserAuthentication::isUserLogged()) {
            Message::addErrorMessage(Labels::getLabel('ERR_USER_ALREADY_LOGGED_IN', $this->siteLangId));
            FatUtility::dieWithError(Message::getHtml());
        }

        $userObj = new User($userId);
        $userdata = $userObj->getUserInfo(array('credential_email', 'user_name'), false, false);

        if (false == $userdata) {
            unset($_SESSION['registered_supplier']['id']);
            Message::addErrorMessage(Labels::getLabel("ERR_INVALID_ACCESS", $this->siteLangId));
            FatUtility::dieWithError(Message::getHtml());
        }

        $srch = $userObj->getUserSupplierRequestsObj();
        $srch->addFld(array('usuprequest_attempts', 'usuprequest_id'));
        $srch->doNotCalculateRecords();
        $srch->setPageSize(1);

        $rs = $srch->getResultSet();
        if (!$rs) {
            Message::addErrorMessage(Labels::getLabel('ERR_INVALID_REQUEST', $this->siteLangId));
            FatUtility::dieWithError(Message::getHtml());
        }

        $supplierRequest = FatApp::getDb()->fetch($rs);
        $maxAttempts = FatApp::getConfig('CONF_MAX_SUPPLIER_REQUEST_ATTEMPT', FatUtility::VAR_INT, 3);

        if ($supplierRequest && $supplierRequest['usuprequest_attempts'] > $maxAttempts) {
            Message::addErrorMessage(Labels::getLabel('ERR_YOU_HAVE_ALREADY_CONSUMED_MAX_ATTEMPTS', $this->siteLangId));
            FatUtility::dieWithError(Message::getHtml());
        }

        $data = array('id' => isset($supplierRequest['usuprequest_id']) ? $supplierRequest['usuprequest_id'] : 0);
        $approvalFrm = $this->getSupplierForm();
        $approvalFrm->fill($data);

        $this->set('siteLangId', $this->siteLangId);
        $this->set('approvalFrm', $approvalFrm);
        $this->_template->render(false, false, 'supplier/profile-activation-form.php');
    }

    public function setupSupplierApproval()
    {
        $userId = $this->getRegisteredSupplierId();

        if (!$this->isRegisteredSupplierId($userId)) {
            FatUtility::dieJsonError(Labels::getLabel("ERR_INVALID_ACCESS", $this->siteLangId));
        }

        if (UserAuthentication::isUserLogged()) {
            FatUtility::dieJsonError(Labels::getLabel('ERR_USER_ALREADY_LOGGED_IN', $this->siteLangId));
        }

        $frm = $this->getSupplierForm();
        $post = $frm->getFormDataFromArray(FatApp::getPostedData());
        if (false === $post) {
            FatUtility::dieJsonError(current($frm->getValidationErrors()));
        }

        foreach ($post as $key => $val) {
            if (false !== strpos($key, '_dcode')) {
                $phoneKey = str_replace('_dcode', '', $key);
                if (!empty($phoneKey)) {
                    $post[$phoneKey] = $val . $post[$phoneKey];
                }
            }
        }



        $userObj = new User($userId);
        $supplier_form_fields = $userObj->getSupplierFormFields($this->siteLangId);

        foreach ($supplier_form_fields as $field) {
            $fieldIdsArr[] = $field['sformfield_id'];
            if ($field['sformfield_required'] && empty($post["sformfield_" . $field['sformfield_id']])) {
                $error_messages[] = sprintf(Labels::getLabel('ERR_Label_Required', $this->siteLangId), $field['sformfield_caption']);
            }
        }

        if (!empty($error_messages)) {
            FatUtility::dieJsonError($error_messages);
        }

        $reference_number = $userId . '-' . time();
        $data = array_merge(
            $post,
            array(
                "user_id" => $userId,
                "reference" => $reference_number,
                'fieldIdsArr' => $fieldIdsArr
            )
        );

        $db = FatApp::getDb();
        $db->startTransaction();

        if (!$userObj->addSupplierRequestData($data, $this->siteLangId)) {
            $db->rollbackTransaction();
            FatUtility::dieJsonError(Labels::getLabel('ERR_DETAILS_NOT_SAVED', $this->siteLangId));
        }

        if (FatApp::getConfig("CONF_ADMIN_APPROVAL_SUPPLIER_REGISTRATION", FatUtility::VAR_INT, 1)) {
            $approval_request = 1;
            $msg = Labels::getLabel('MSG_YOUR_SELLER_APPROVAL_FORM_REQUEST_SENT', $this->siteLangId);
        } else {
            $approval_request = 0;
            $msg = Labels::getLabel('MSG_YOUR_APPLICATION_IS_APPROVED', $this->siteLangId);
        }

        if (!$userObj->notifyAdminSupplierApproval($userObj, $data, $approval_request, $this->siteLangId)) {
            $db->rollbackTransaction();
            FatUtility::dieJsonError($userObj->getError());
        }

        //send notification to admin
        $notificationData = array(
            'notification_record_type' => Notification::TYPE_USER,
            'notification_record_id' => $userObj->getMainTableRecordId(),
            'notification_user_id' => $userId,
            'notification_label_key' => ($approval_request) ? Notification::NEW_SUPPLIER_APPROVAL_NOTIFICATION : Notification::NEW_SELLER_APPROVED_NOTIFICATION,
            'notification_added_on' => date('Y-m-d H:i:s'),
        );

        if (!Notification::saveNotifications($notificationData)) {
            $db->rollbackTransaction();
            FatUtility::dieJsonError(Labels::getLabel("ERR_SELLER_APPROVAL_NOTIFICATION_COULD_NOT_BE_SENT", $this->siteLangId));
        }

        $db->commitTransaction();
        $this->set('userId', $userId);
        $this->set('msg', $msg);

        $redirectUrl = '';
        $selectedPlanId = FatUtility::int($_SESSION[static::SESSION_SELECTED_SELLER_PLAN] ?? 0);
        if ($selectedPlanId > 0 && $approval_request == 0 && UserAuthentication::isUserLogged()) {
            unset($_SESSION['registered_supplier']['id']);
            $redirectUrl = UrlHelper::generateUrl('Supplier', 'selectPackage', [$selectedPlanId]);
        }
        $this->set('redirectUrl', $redirectUrl);
        $this->_template->render(false, false, 'json-success.php');
    }

    public function profileConfirmation()
    {
        if (!$userId = $this->getRegisteredSupplierId()) {
            Message::addErrorMessage(Labels::getLabel("ERR_INVALID_ACCESS", $this->siteLangId));
            FatUtility::dieWithError(Message::getHtml());
        }

        if (UserAuthentication::isUserLogged()) {
            FatUtility::dieJsonError(Labels::getLabel('ERR_USER_ALREADY_LOGGED_IN', $this->siteLangId));
        }

        $userObj = new User($userId);
        $userdata = $userObj->getUserInfo(array('credential_active', 'credential_verified'), false, false);

        if (false == $userdata) {
            FatUtility::dieJsonError(Labels::getLabel("ERR_INVALID_ACCESS", $this->siteLangId));
        }

        if (/* $userdata['credential_active'] == 1 &&  */$userdata['credential_verified'] == applicationConstants::YES) {
            $success_message = Labels::getLabel('MSG_SELLER_SIGNUP_VERIFIED_SUCCESSFULLY', $this->siteLangId);
        } else {
            $success_message = Labels::getLabel('MSG_SELLER_SIGNUP_SUCCESSFULLY', $this->siteLangId);
        }

        unset($_SESSION['registered_supplier']['id']);
        $this->set('success_message', $success_message);
        $this->set('siteLangId', $this->siteLangId);
        $this->_template->render(false, false);
    }

    public function uploadSupplierFormImages()
    {
        $userId = $this->getRegisteredSupplierId();

        if (!$this->isRegisteredSupplierId($userId)) {
            FatUtility::dieJsonError(Labels::getLabel("ERR_INVALID_ACCESS", $this->siteLangId));
        }

        if (UserAuthentication::isUserLogged()) {
            FatUtility::dieJsonError(Labels::getLabel('ERR_USER_ALREADY_LOGGED_IN', $this->siteLangId));
        }

        $post = FatApp::getPostedData();
        if (empty($post)) {
            FatUtility::dieJsonError(Labels::getLabel('ERR_INVALID_REQUEST_OR_FILE_NOT_SUPPORTED', $this->siteLangId));
        }
        if (!isset($post['field_id']) || FatUtility::int($post['field_id']) == 0) {
            FatUtility::dieJsonError(Labels::getLabel('ERR_INVALID_REQUEST_ID', $this->siteLangId));
        }
        $field_id = $post['field_id'];

        if (!is_uploaded_file($_FILES['file']['tmp_name'])) {
            FatUtility::dieJsonError(Labels::getLabel('ERR_PLEASE_SELECT_A_FILE', $this->siteLangId));
        }

        $fileHandlerObj = new AttachedFile();
        $fileHandlerObj->deleteFile($fileHandlerObj::FILETYPE_SELLER_APPROVAL_FILE, $userId, 0, $field_id);

        if (!$res = $fileHandlerObj->saveAttachment($_FILES['file']['tmp_name'], $fileHandlerObj::FILETYPE_SELLER_APPROVAL_FILE, $userId, $field_id, $_FILES['file']['name'], -1, false)) {
            /* Message::addErrorMessage($fileHandlerObj->getError()); */
            FatUtility::dieJsonError($fileHandlerObj->getError());
        }

        $this->set('file', $_FILES['file']['name']);
        $this->set('msg', /* $_FILES['file']['name'].' '. */ Labels::getLabel('MSG_FILE_UPLOADED_SUCCESSFULLY', $this->siteLangId));
        $this->_template->render(false, false, 'json-success.php');
    }

    public function faq()
    {
        $cmsPagesToFaq = FatApp::getConfig('conf_cms_pages_to_faq_page');
        $cmsPagesToFaq = unserialize($cmsPagesToFaq);
        if (sizeof($cmsPagesToFaq) > 0 && is_array($cmsPagesToFaq)) {
            $contentPageSrch = ContentPage::getSearchObject($this->siteLangId);
            $contentPageSrch->addCondition('cpage_id', 'in', $cmsPagesToFaq);
            $contentPageSrch->addMultipleFields(array('cpage_id', 'cpage_identifier', 'cpage_title'));
            $contentPageSrch->doNotCalculateRecords();
            $rs = $contentPageSrch->getResultSet();
            $cpages = FatApp::getDb()->fetchAll($rs);
            $this->set('cpages', $cpages);
        }
        $this->_template->render();
    }

    public function searchFaqs($catId = '')
    {
        $faqMainCat = FatApp::getConfig("CONF_SELLER_PAGE_MAIN_CATEGORY", FatUtility::VAR_STRING, '');
        if (!empty($catId) && $catId > 0) {
            $faqCatId = array($catId);
        } elseif ($faqMainCat) {
            $faqCatId = array($faqMainCat);
        } else {
            $srchFAQCat = FaqCategory::getSearchObject($this->siteLangId);
            $srchFAQCat->setPageSize(1);
            $srchFAQCat->doNotCalculateRecords();
            $srchFAQCat->addFld('faqcat_id');
            $rs = $srchFAQCat->getResultSet();
            $faqCatId = FatApp::getDb()->fetch($rs, 'faqcat_id');
        }

        $srch = FaqCategory::getSearchObject($this->siteLangId);
        $srch->joinTable('tbl_faqs', 'LEFT OUTER JOIN', 'faq_faqcat_id = faqcat_id and faq_active = ' . applicationConstants::ACTIVE . '  and faq_deleted = ' . applicationConstants::NO);
        $srch->joinTable('tbl_faqs_lang', 'LEFT OUTER JOIN', 'faqlang_faq_id = faq_id');
        $srch->addCondition('faqlang_lang_id', '=', $this->siteLangId);
        $srch->addCondition('faqcat_active', '=', applicationConstants::ACTIVE);
        $srch->addCondition('faqcat_type', '=', FaqCategory::SELLER_PAGE);
        if (!empty($faqCatId)) {
            $srch->addCondition('faqcat_id', 'IN', $faqCatId);
        }

        $question = FatApp::getPostedData('question', FatUtility::VAR_STRING, '');
        if (!empty($question)) {
            $srchCondition = $srch->addCondition('faq_title', 'like', "%$question%");
            $srch->doNotLimitRecords();
        }
        $srch->addOrder('faqcat_display_order', 'asc');
        $srch->addOrder('faq_faqcat_id', 'asc');
        $srch->addOrder('faq_display_order', 'asc');

        $rs = $srch->getResultSet();
        $records = FatApp::getDb()->fetchAll($rs);

        $json['recordCount'] = $srch->recordCount();

        if (isset($srchCondition)) {
            $srchCondition->remove();
        }

        $this->set('siteLangId', $this->siteLangId);
        $this->set('faqCatIdArr', $faqCatId);
        $this->set('list', $records);
        $json['html'] = ''; //$this->_template->render( false, false,'_partial/no-record-found.php', true );
        if (!empty($records)) {
            $json['html'] = $this->_template->render(false, false, 'supplier/search-faqs.php', true, false);
        }
        FatUtility::dieJsonSuccess($json);
    }

    public function faqCategoriesPanel()
    {
        $srch = FaqCategory::getSearchObject($this->siteLangId);
        $srch->joinTable('tbl_faqs', 'LEFT OUTER JOIN', 'faq_faqcat_id = faqcat_id AND faq_active = ' . applicationConstants::ACTIVE . ' AND faq_deleted = ' . applicationConstants::NO);
        $srch->joinTable('tbl_faqs_lang', 'LEFT OUTER JOIN', 'faqlang_faq_id = faq_id');
        $srch->addCondition('faqlang_lang_id', '=', $this->siteLangId);
        $srch->addCondition('faqcat_active', '=', applicationConstants::ACTIVE);
        $srch->addCondition('faqcat_type', '=', FaqCategory::SELLER_PAGE);
        $srch->addOrder('faqcat_display_order', 'asc');
        $srch->addOrder('faq_faqcat_id', 'asc');
        $srch->addOrder('faq_display_order', 'asc');
        $srch->doNotLimitRecords();
        $rs = $srch->getResultSet();
        $records = FatApp::getDb()->fetchAll($rs);

        $json['recordCount'] = $srch->recordCount();

        $srch->addGroupBy('faqcat_id');
        $srch->addMultipleFields(array('faqcat_name', 'faqcat_id'));
        $srch->addFld('COUNT(1) AS faq_count');
        if (isset($srchCondition)) {
            $srchCondition->remove();
        }
        $rsCat = $srch->getResultSet();
        $recordsCategories = array();
        if ($rsCat) {
            $recordsCategories = FatApp::getDb()->fetchAll($rsCat);
        }

        $faqMainCat = FatApp::getConfig("CONF_SELLER_PAGE_MAIN_CATEGORY", FatUtility::VAR_STRING, '');

        $this->set('siteLangId', $this->siteLangId);
        $this->set('list', $records);
        $this->set('listCategories', $recordsCategories);
        $this->set('faqMainCat', $faqMainCat);
        $this->set('page', 'seller');
        $json['html'] = $this->_template->render(false, false, '_partial/no-record-found.php', true, false);
        if (!empty($records)) {
            $json['html'] = $this->_template->render(false, false, 'supplier/search-faqs.php', true, false);
        }
        $json['categoriesPanelHtml'] = $this->_template->render(false, false, 'custom/faq-categories-panel.php', true, false);
        FatUtility::dieJsonSuccess($json);
    }

    private function getRegisteredSupplierId()
    {
        if (!isset($_SESSION['registered_supplier']['id'])) {
            return false;
        }
        return $_SESSION['registered_supplier']['id'];
    }

    private function isRegisteredSupplierId($userId)
    {
        if (!isset($_SESSION['registered_supplier'])) {
            return false;
        }

        $userId = FatUtility::int($userId);
        if (1 > $userId || $userId != $_SESSION['registered_supplier']['id']) {
            return false;
        }
        return true;
    }

    private function getSellerRegistrationForm()
    {
        $frm = new Form('frmSellerRegistration');

        $frm->addRequiredField(Labels::getLabel('FRM_NAME', $this->siteLangId), 'user_name');

        $frm->addHiddenField('', 'user_id', 0, array('id' => 'user_id'));

        $fld = $frm->addTextBox(Labels::getLabel('FRM_USERNAME', $this->siteLangId), 'user_username');
        $fld->setUnique('tbl_user_credentials', 'credential_username', 'credential_user_id', 'user_id', 'user_id');
        $fld->requirements()->setRequired();
        $fld->requirements()->setUsername();

        $fld = $frm->addEmailField(Labels::getLabel('FRM_EMAIL', $this->siteLangId), 'user_email');
        $fld->setUnique('tbl_user_credentials', 'credential_email', 'credential_user_id', 'user_id', 'user_id');

        $fld = $frm->addPasswordField(Labels::getLabel('FRM_PASSWORD', $this->siteLangId), 'user_password');
        $fld->requirements()->setRequired();
        $fld->requirements()->setRegularExpressionToValidate(ValidateElement::PASSWORD_REGEX);
        $fld->requirements()->setCustomErrorMessage(Labels::getLabel('ERR_PASSWORD_MUST_BE_EIGHT_CHARACTERS_LONG_AND_ALPHANUMERIC', $this->siteLangId));

        $fld1 = $frm->addPasswordField(Labels::getLabel('FRM_CONFIRM_PASSWORD', $this->siteLangId), 'password1');
        $fld1->requirements()->setRequired();
        $fld1->requirements()->setCompareWith('user_password', 'eq', Labels::getLabel('FRM_PASSWORD', $this->siteLangId));

        $fld = $frm->addCheckBox('', 'agree', 1);
        $fld->requirements()->setRequired();
        $fld->requirements()->setCustomErrorMessage(Labels::getLabel('ERR_TERMS_CONDITION_IS_MANDATORY.', $this->siteLangId));
        if (FatApp::getConfig('CONF_ENABLE_NEWSLETTER_SUBSCRIPTION')) {
            $api_key = FatApp::getConfig("CONF_MAILCHIMP_KEY");
            $list_id = FatApp::getConfig("CONF_MAILCHIMP_LIST_ID");
            if ($api_key != '' || $list_id != '') {
                $frm->addCheckBox(Labels::getLabel('FRM_Newsletter_Signup', $this->siteLangId), 'user_newsletter_signup', 1);
            }
        }
        $frm->addSubmitButton('', 'btn_submit', Labels::getLabel('BTN_SUBMIT', $this->siteLangId));

        return $frm;
    }

    private function getSupplierForm()
    {
        $frm = new Form('frmSupplierForm');
        $frm->addHiddenField('', 'id', 0);

        $userObj = new User();
        $supplier_form_fields = $userObj->getSupplierFormFields($this->siteLangId);

        foreach ($supplier_form_fields as $field) {
            $fieldName = 'sformfield_' . $field['sformfield_id'];

            switch ($field['sformfield_type']) {
                case User::USER_FIELD_TYPE_TEXT:
                    $fld = $frm->addTextBox($field['sformfield_caption'], $fieldName);
                    break;

                case User::USER_FIELD_TYPE_TEXTAREA:
                    $fld = $frm->addTextArea($field['sformfield_caption'], $fieldName);
                    break;

                case User::USER_FIELD_TYPE_FILE:
                    $fld1 = $frm->addButton(
                        $field['sformfield_caption'],
                        'button[' . $field['sformfield_id'] . ']',
                        Labels::getLabel('FRM_UPLOAD_FILE', $this->siteLangId),
                        array('class' => 'fileType-Js btn btn-outline-brand btn-block btn-upload', 'id' => 'button-upload' . $field['sformfield_id'], 'data-field_id' => $field['sformfield_id'])
                    );
                    $fld1->htmlAfterField = '<span id="input-sformfield' . $field['sformfield_id'] . '"></span>';
                    if ($field['sformfield_required'] == 1) {
                        $fld1->captionWrapper = array('<div class="astrick">', '</div>');
                    }
                    // $fld = $frm->addHiddenField($field['sformfield_caption'],$fieldName,'',array('id'=>$fieldName));
                    $fld = $frm->addTextBox('', $fieldName, '', array('id' => $fieldName, 'hidden' => 'hidden', 'title' => $field['sformfield_caption']));
                    $fld->setRequiredStarWith(Form::FORM_REQUIRED_STAR_WITH_NONE);
                    $fld1->attachField($fld);
                    break;

                case User::USER_FIELD_TYPE_DATE:
                    $fld = $frm->addDateField($field['sformfield_caption'], $fieldName, '', array('readonly' => 'readonly', 'class' => 'field--calender'));
                    break;

                case User::USER_FIELD_TYPE_DATETIME:
                    $fld = $frm->addDateTimeField($field['sformfield_caption'], $fieldName, '', array('readonly' => 'readonly', 'class' => 'field--calender'));
                    break;

                case User::USER_FIELD_TYPE_TIME:
                    $fld = $frm->addTextBox($field['sformfield_caption'], $fieldName);
                    $fld->requirement->setRegularExpressionToValidate(ValidateElement::TIME_REGEX);
                    $fld->htmlAfterField = Labels::getLabel('MSG_HH:MM', $this->siteLangId);
                    $fld->requirements()->setCustomErrorMessage(Labels::getLabel('ERR_PLEASE_ENTER_VALID_TIME_FORMAT.', $this->siteLangId));
                    break;

                case User::USER_FIELD_TYPE_PHONE:
                    $frm->addHiddenField('', $fieldName . '_dcode');
                    $fld = $frm->addTextBox($field['sformfield_caption'], $fieldName, '', array('class' => 'phone-js ltr-right', 'placeholder' => ValidateElement::PHONE_NO_FORMAT, 'maxlength' => ValidateElement::PHONE_NO_LENGTH));
                    $fld->requirements()->setRegularExpressionToValidate(ValidateElement::PHONE_REGEX);
                    break;
            }

            if ($field['sformfield_required'] == 1) {
                $fld->requirements()->setRequired();
            }
            if ($field['sformfield_comment']) {
                $fld->htmlAfterField = '<span class="form-text text-muted">' . $field['sformfield_comment'] . '</span>';
            }
        }
        $frm->addSubmitButton('', 'btn_submit', Labels::getLabel('BTN_SAVE_CHANGES', $this->siteLangId));
        return $frm;
    }

    private function getSellerForm()
    {
        $frm = new Form('frmSeller');
        $frm->addHiddenField('', 'user_id', 0, array('id' => 'user_id'));
        $frm->setFormTagAttribute("class", "form invalid");
        $frm->setFormTagAttribute("action", UrlHelper::generateUrl('supplier', 'account'));
        $fld = $frm->addEmailField(Labels::getLabel('FRM_YOUR_EMAIL', $this->siteLangId), 'user_email', '');
        $fld->setUnique('tbl_user_credentials', 'credential_email', 'credential_user_id', 'user_id', 'user_id');
        $frm->addRequiredField(Labels::getLabel('FRM_YOUR_NAME', $this->siteLangId), 'user_name', '');
        $frm->addSubmitButton('', 'btn_submit', Labels::getLabel('BTN_START_SELLING', $this->siteLangId));
        /* $frm->addHtml('', 'htmlNote',Labels::getLabel('Lbl_Need_help_in_getting_PAN/VAT',$this->siteLangId).'?');
        $frm->addHtml('', 'htmlNote','<a href="" class="">'.Labels::getLabel('Lbl_Click_Here',$this->siteLangId).'</a> '.Labels::getLabel('Lbl_to_contact_our_partners_near_your_location',$this->siteLangId));     */
        return $frm;
    }

    public function registerNewAccount()
    {
        unset($_SESSION['registered_supplier']['id']);
        FatApp::redirectUser(UrlHelper::generateUrl('supplier'));
    }

    private function redirectToSubscriptionCheckoutIfPlanSelected()
    {
        if (!UserAuthentication::isUserLogged()) {
            return;
        }

        $spplanId = FatUtility::int($_SESSION[static::SESSION_SELECTED_SELLER_PLAN] ?? 0);
        if (1 > $spplanId) {
            return;
        }

        if (User::isSeller() || User::isSigningUpForSeller()) {
            FatApp::redirectUser(UrlHelper::generateUrl('supplier', 'selectPackage', array($spplanId)));
        }
    }
}
