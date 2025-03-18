<?php
class AccountController extends LoggedUserController
{
    public function __construct($action)
    {
        parent::__construct($action);
        if (!isset($_SESSION[UserAuthentication::SESSION_ELEMENT_NAME]['activeTab'])) {
            $_SESSION[UserAuthentication::SESSION_ELEMENT_NAME]['activeTab'] = '';
            if (User::isBuyer() || User::isSigningUpBuyer()) {
                $_SESSION[UserAuthentication::SESSION_ELEMENT_NAME]['activeTab'] = 'B';
            } elseif (User::isSeller() || User::isSigningUpForSeller()) {
                $_SESSION[UserAuthentication::SESSION_ELEMENT_NAME]['activeTab'] = 'S';
            } elseif (User::isAdvertiser() || User::isSigningUpAdvertiser()) {
                $_SESSION[UserAuthentication::SESSION_ELEMENT_NAME]['activeTab'] = 'Ad';
            } elseif (User::isAffiliate() || User::isSigningUpAffiliate()) {
                $_SESSION[UserAuthentication::SESSION_ELEMENT_NAME]['activeTab'] = 'AFFILIATE';
            }
        }
    }

    public function index()
    {
        if (UserAuthentication::isGuestUserLogged()) {
            FatApp::redirectUser(UrlHelper::generateUrl('home', '', [], CONF_WEBROOT_FRONTEND, null, false, false, true, $this->siteLangId));
        }

        switch ($_SESSION[UserAuthentication::SESSION_ELEMENT_NAME]['activeTab']) {
            case 'B':
                FatApp::redirectUser(UrlHelper::generateUrl('buyer', '', [], CONF_WEBROOT_DASHBOARD));
                break;
            case 'S':
                FatApp::redirectUser(UrlHelper::generateUrl('seller', '', [], CONF_WEBROOT_DASHBOARD));
                break;
            case 'Ad':
                FatApp::redirectUser(UrlHelper::generateUrl('advertiser', '', [], CONF_WEBROOT_DASHBOARD));
                break;
            case 'AFFILIATE':
                FatApp::redirectUser(UrlHelper::generateUrl('affiliate', '', [], CONF_WEBROOT_DASHBOARD));
                break;
            default:
                FatApp::redirectUser(UrlHelper::generateUrl('', '', [], CONF_WEBROOT_DASHBOARD));
                break;
        }
    }

    public function viewSupplierRequest($requestId)
    {
        $requestId = FatUtility::int($requestId);

        if ($this->userId < 1 || $requestId < 1) {
            Message::addErrorMessage(Labels::getLabel('ERR_INVALID_REQUEST', $this->siteLangId));
            FatApp::redirectUser(UrlHelper::generateUrl('Account', 'SupplierApprovalForm', [], CONF_WEBROOT_DASHBOARD));
            //FatUtility::dieJsonError( Message::getHtml() );
        }
        $userObj = new User($this->userId);
        $srch = $userObj->getUserSupplierRequestsObj($requestId, false);
        $srch->addFld('tusr.*');

        $rs = $srch->getResultSet();
        /* if(!$rs){
        Message::addErrorMessage(Labels::getLabel('ERR_INVALID_REQUEST',$this->siteLangId));
        FatUtility::dieJsonError( Message::getHtml() );
        } */

        $supplierRequest = FatApp::getDb()->fetch($rs);

        if (!$supplierRequest || $supplierRequest['usuprequest_id'] != $requestId) {
            Message::addErrorMessage(Labels::getLabel('ERR_INVALID_REQUEST', $this->siteLangId));
            FatApp::redirectUser(UrlHelper::generateUrl('Account', 'SupplierApprovalForm', [], CONF_WEBROOT_DASHBOARD));
        }

        if ($supplierRequest["usuprequest_status"] == User::SUPPLIER_REQUEST_APPROVED) {
            $userData = User::getAttributesById($this->userId, ['user_is_supplier', 'user_is_advertiser']);
            $_SESSION[UserAuthentication::SESSION_ELEMENT_NAME]['user_is_supplier'] = $userData['user_is_supplier'];
            $_SESSION[UserAuthentication::SESSION_ELEMENT_NAME]['user_is_advertiser'] = $userData['user_is_advertiser'];
            $msg = Labels::getLabel('LBL_Hello', $this->siteLangId) . ', ' . $supplierRequest["user_name"] . ', ' . Labels::getLabel('LBL_Your_Application_Approved', $this->siteLangId);
            Message::addMessage($msg);
            FatApp::redirectUser(UrlHelper::generateUrl('Seller'));
        }

        $maxAttempts = FatApp::getConfig('CONF_MAX_SUPPLIER_REQUEST_ATTEMPT', FatUtility::VAR_INT, 3);
        if ($supplierRequest && $supplierRequest['usuprequest_attempts'] >= $maxAttempts) {
            $this->set('maxAttemptsReached', true);
        }

        $this->set('maxAttempts', FatApp::getConfig('CONF_MAX_SUPPLIER_REQUEST_ATTEMPT', FatUtility::VAR_INT, 3));
        $this->set('supplierRequest', $supplierRequest);
        $this->_template->render();
    }

    public function supplierApprovalForm($p = '')
    {
        if (!User::canViewSupplierTab()) {
            Message::addErrorMessage(Labels::getLabel('ERR_INVALID_REQUEST_FOR_SUPPLIER_DASHBOARD', $this->siteLangId));
            if (User::isBuyer()) {
                FatApp::redirectUser(UrlHelper::generateUrl('buyer', '', [], CONF_WEBROOT_DASHBOARD));
            } elseif (User::isAdvertiser()) {
                FatApp::redirectUser(UrlHelper::generateUrl('advertiser', '', [], CONF_WEBROOT_DASHBOARD));
            } elseif (User::isAffiliate()) {
                FatApp::redirectUser(UrlHelper::generateUrl('affiliate', '', [], CONF_WEBROOT_DASHBOARD));
            } else {
                FatApp::redirectUser(UrlHelper::generateUrl('Account', 'ProfileInfo', [], CONF_WEBROOT_DASHBOARD));
            }
        }

        $userObj = new User($this->userId);
        $srch = $userObj->getUserSupplierRequestsObj(0, false);
        $srch->addFld(array('usuprequest_attempts', 'usuprequest_id'));

        $rs = $srch->getResultSet();
        if (!$rs) {
            Message::addErrorMessage(Labels::getLabel('ERR_INVALID_REQUEST', $this->siteLangId));
            FatUtility::dieWithError(Message::getHtml());
        }

        $supplierRequest = FatApp::getDb()->fetch($rs);
        $maxAttempts = FatApp::getConfig('CONF_MAX_SUPPLIER_REQUEST_ATTEMPT', FatUtility::VAR_INT, 3);
        if ($supplierRequest && $supplierRequest['usuprequest_attempts'] >= $maxAttempts) {
            Message::addErrorMessage(Labels::getLabel('ERR_YOU_HAVE_ALREADY_CONSUMED_MAX_ATTEMPTS', $this->siteLangId));
            FatApp::redirectUser(UrlHelper::generateUrl('account', 'viewSupplierRequest', array($supplierRequest["usuprequest_id"]), CONF_WEBROOT_DASHBOARD));
        }

        if ($supplierRequest && ($p != "reopen")) {
            FatApp::redirectUser(UrlHelper::generateUrl('account', 'viewSupplierRequest', array($supplierRequest["usuprequest_id"]), CONF_WEBROOT_DASHBOARD));
        }

        $data = array('id' => isset($supplierRequest['usuprequest_id']) ? $supplierRequest['usuprequest_id'] : 0);
        $approvalFrm = $this->getSupplierForm();
        $approvalFrm->fill($data);
        $approvalFrm->addSecurityToken();

        $this->set('approvalFrm', $approvalFrm);
        $this->_template->addJs(array('js/jquery.datetimepicker.js'));
        $this->_template->addCss(array('css/jquery.datetimepicker.css'), false);
        $this->_template->render();
    }

    public function setupSupplierApproval()
    {
        $error_messages = array();
        $fieldIdsArr = array();
        /* check if maximum attempts reached [ */
        $userObj = new User($this->userId);
        $srch = $userObj->getUserSupplierRequestsObj();
        $srch->addFld(array('usuprequest_attempts', 'usuprequest_id'));

        $rs = $srch->getResultSet();
        if (!$rs) {
            FatUtility::dieJsonError(Labels::getLabel('ERR_INVALID_REQUEST', $this->siteLangId));
        }

        $supplierRequest = FatApp::getDb()->fetch($rs);
        $maxAttempts = FatApp::getConfig('CONF_MAX_SUPPLIER_REQUEST_ATTEMPT', FatUtility::VAR_INT, 3);
        if ($supplierRequest && $supplierRequest['usuprequest_attempts'] >= $maxAttempts) {
            FatUtility::dieJsonError(Labels::getLabel('ERR_YOU_HAVE_ALREADY_CONSUMED_MAX_ATTEMPTS', $this->siteLangId));
        }
        /* ] */

        $frm = $this->getSupplierForm();
        $post = $frm->getFormDataFromArray(FatApp::getPostedData(), [], true);

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

        $frm->expireSecurityToken(FatApp::getPostedData());

        $supplier_form_fields = $userObj->getSupplierFormFields($this->siteLangId);
        foreach ($supplier_form_fields as $field) {
            $fieldIdsArr[] = $field['sformfield_id'];
            //$fieldCaptionsArr[] = $field['sformfield_caption'];
            if ($field['sformfield_required'] && empty($post["sformfield_" . $field['sformfield_id']])) {
                $error_messages[] = sprintf(Labels::getLabel('ERR_LABEL_REQUIRED', $this->siteLangId), $field['sformfield_caption']);
            }
        }

        if (!empty($error_messages)) {
            FatUtility::dieJsonError($error_messages);
        }

        $reference_number = $this->userId . '-' . time();
        $data = array_merge($post, array("user_id" => $this->userId, "reference" => $reference_number, 'fieldIdsArr' => $fieldIdsArr));

        $db = FatApp::getDb();
        $db->startTransaction();

        if (!$supplier_request_id = $userObj->addSupplierRequestData($data, $this->siteLangId)) {
            $db->rollbackTransaction();
            $msg = $userObj->getError();
            $msg = empty($msg) ? Labels::getLabel('ERR_DETAILS_NOT_SAVED', $this->siteLangId) : $msg;
            FatUtility::dieJsonError($msg);
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
            'notification_record_id' => $supplier_request_id,
            'notification_user_id' => $this->userId,
            'notification_label_key' => ($approval_request) ? Notification::NEW_SUPPLIER_APPROVAL_NOTIFICATION : Notification::NEW_SELLER_APPROVED_NOTIFICATION,
            'notification_added_on' => date('Y-m-d H:i:s'),
        );

        if (!Notification::saveNotifications($notificationData)) {
            $db->rollbackTransaction();
            FatUtility::dieJsonError(Labels::getLabel("ERR_NOTIFICATION_COULD_NOT_BE_SENT", $this->siteLangId));
        }

        $db->commitTransaction();
        if (FatApp::getConfig("CONF_ADMIN_APPROVAL_SUPPLIER_REGISTRATION", FatUtility::VAR_INT, 1)) {
            CalculativeDataRecord::updateSellerApprovalCount();
        }
        $this->set('supplier_request_id', $supplier_request_id);
        $this->set('msg', $msg);
        $this->_template->render(false, false, 'json-success.php');
    }

    public function uploadSupplierFormImages()
    {
        $post = FatApp::getPostedData();

        if (empty($post)) {
            FatUtility::dieJsonError(Labels::getLabel('ERR_INVALID_REQUEST_OR_FILE_NOT_SUPPORTED', $this->siteLangId));
        }
        $field_id = $post['field_id'];

        $fileHandlerObj = new AttachedFile();
        if (!$fileHandlerObj->isUploadedFile($_FILES['file']['tmp_name'])) {
            FatUtility::dieJsonError($fileHandlerObj->getError());
        }

        $fileHandlerObj->deleteFile($fileHandlerObj::FILETYPE_SELLER_APPROVAL_FILE, $this->userId, 0, $field_id);

        if (!$res = $fileHandlerObj->saveAttachment(
            $_FILES['file']['tmp_name'],
            $fileHandlerObj::FILETYPE_SELLER_APPROVAL_FILE,
            $this->userId,
            $field_id,
            $_FILES['file']['name'],
            -1,
            false
        )) {
            /* Message::addErrorMessage($fileHandlerObj->getError()); */
            FatUtility::dieJsonError($fileHandlerObj->getError());
        }

        $this->set('file', $_FILES['file']['name']);
        $this->set('msg', /* $_FILES['file']['name'].' '. */ Labels::getLabel('MSG_File_uploaded_successfully', $this->siteLangId));
        $this->_template->render(false, false, 'json-success.php');
    }

    public function changeEmailPassword()
    {
        $this->set('siteLangId', $this->siteLangId);
        $this->set('canSendSms', SmsArchive::canSendSms(SmsTemplate::LOGIN));
        $this->set('hasEmailId', !empty((new User($this->userId))->getUserInfo('credential_email', false, false, true)['credential_email']));
        $this->_template->render();
    }

    public function changePasswordForm()
    {
        $frm = $this->getChangePasswordForm();

        $this->set('frm', $frm);
        $this->_template->render(false, false);
    }

    public function updatePassword()
    {
        $pwdFrm = $this->getChangePasswordForm();
        $post = $pwdFrm->getFormDataFromArray(FatApp::getPostedData());

        if ($post === false) {
            $message = Labels::getLabel(current($pwdFrm->getValidationErrors()), $this->siteLangId);
            FatUtility::dieJsonError($message);
        }

        /* Restrict to change password for demo user on demo URL. */
        if (CommonHelper::demoUrl() && 4 == $this->userId) {
            FatUtility::dieJsonError(Labels::getLabel('ERR_YOU_ARE_NOT_ALLOWED_TO_CHANGE_PASSWORD_FOR_DEMO', $this->siteLangId));
        }

        $userObj = new User($this->userId);
        $srch = $userObj->getUserSearchObj(array('user_id', 'credential_password', 'credential_password_old'));
        $rs = $srch->getResultSet();

        $data = FatApp::getDb()->fetch($rs, 'user_id');

        if ($data === false) {
            $message = Labels::getLabel('MSG_Invalid_User', $this->siteLangId);
            FatUtility::dieJsonError($message);
        }

        if (false == UserAuthentication::isGuestUserLogged()) {
            if (empty($data['credential_password'])) {
                $currentEncPassword = UserAuthentication::encryptPassword($post['current_password'], true);
                if ($currentEncPassword !== $data['credential_password_old']) {
                    $message = Labels::getLabel('MSG_YOUR_CURRENT_PASSWORD_MIS_MATCHED', $this->siteLangId);
                    FatUtility::dieJsonError($message);
                }
            } else {
                if (false == password_verify($post['current_password'], $data['credential_password'])) {
                    $message = Labels::getLabel('MSG_YOUR_CURRENT_PASSWORD_MIS_MATCHED', $this->siteLangId);
                    FatUtility::dieJsonError($message);
                }
            }
        }

        if (!$userObj->setLoginPassword($post['new_password'])) {
            $message = Labels::getLabel('MSG_Password_could_not_be_set', $this->siteLangId) . $userObj->getError();
            FatUtility::dieJsonError($message);
        }


        if (UserAuthentication::isGuestUserLogged()) {
            $userObj = new User($this->userId);
            $srch = $userObj->getUserSearchObj('uc.credential_active');
            $data = FatApp::getDb()->fetch($srch->getResultSet());
            $lbl = Labels::getLabel('MSG_PASSWORD_CHANGED_SUCCESSFULLY', $this->siteLangId);
            if (0 == $data['credential_active']) {
                $lbl = Labels::getLabel('MSG_PASSWORD_UPDATED_SUCCESSFULLY._BUT_ACCOUNT_YET_TO_BE_VERIFIED_BY_THE_SITE_ADMIN.', $this->siteLangId);
            }
            $this->set('msg', $lbl);
        } else {
            $this->set('msg', Labels::getLabel('MSG_PASSWORD_CHANGED_SUCCESSFULLY', $this->siteLangId));
        }

        if (true === MOBILE_APP_API_CALL) {
            $this->_template->render();
        }

        $this->_template->render(false, false, 'json-success.php');
    }

    public function setPrefferedDashboard($dasboardType)
    {
        $dasboardType = FatUtility::int($dasboardType);

        switch ($dasboardType) {
            case User::USER_BUYER_DASHBOARD:
                if (!User::canViewBuyerTab()) {
                    FatUtility::dieJsonError(Labels::getLabel('ERR_INVALID_ACCESS', $this->siteLangId));
                }
                break;
            case User::USER_SELLER_DASHBOARD:
                if (!User::canViewSupplierTab()) {
                    FatUtility::dieJsonError(Labels::getLabel('ERR_INVALID_ACCESS', $this->siteLangId));
                }
                break;
            case User::USER_ADVERTISER_DASHBOARD:
                if (!User::canViewAdvertiserTab()) {
                    FatUtility::dieJsonError(Labels::getLabel('ERR_INVALID_ACCESS', $this->siteLangId));
                }
                break;
            case User::USER_AFFILIATE_DASHBOARD:
                if (!User::canViewAffiliateTab()) {
                    FatUtility::dieJsonError(Labels::getLabel('ERR_INVALID_ACCESS', $this->siteLangId));
                }
                break;
            default:
                FatUtility::dieJsonError(Labels::getLabel('ERR_INVALID_ACCESS', $this->siteLangId));
                break;
        }

        $arr = array('user_preferred_dashboard' => $dasboardType);

        if (1 > $this->userId) {
            FatUtility::dieJsonError(Labels::getLabel('ERR_INVALID_ACCESS', $this->siteLangId));
        }

        $userObj = new User($this->userId);
        $userObj->assignValues($arr);
        if (!$userObj->save()) {
            FatUtility::dieJsonError($userObj->getError());
        }

        $_SESSION[UserAuthentication::SESSION_ELEMENT_NAME]['user_preferred_dashboard'] = $dasboardType;

        $this->set('msg', Labels::getLabel('MSG_Setup_successful', $this->siteLangId));
        $this->_template->render(false, false, 'json-success.php');
    }

    private function canAddMoneyWallet(): bool
    {
        $canAddMoneyToWallet = true;
        if (User::isAffiliate()) {
            $canAddMoneyToWallet = false;
        } else {
            $excludePaymentGatewaysArr = applicationConstants::getExcludePaymentGatewayArr();
            $pmSrch = PaymentMethods::getSearchObject($this->siteLangId);
            $pmSrch->addCondition('plugin_code', 'NOT IN', $excludePaymentGatewaysArr[applicationConstants::CHECKOUT_ADD_MONEY_TO_WALLET]);
            $pmSrch->doNotCalculateRecords();
            $pmSrch->doNotLimitRecords();
            $pmRs = $pmSrch->getResultSet();
            $paymentMethod = FatApp::getDb()->fetch($pmRs);
            if (false == $paymentMethod || empty($paymentMethod)) {
                $canAddMoneyToWallet = false;
            }
        }
        return $canAddMoneyToWallet;
    }


    private function canRedeemGiftCard(): bool
    {
        if (User::isAffiliate()) {
            return false;
        }

        $giftCard = GiftCards::getGiftCards($this->userParentId);
        return !empty($giftCard);
    }

    public function walletRechargeForm()
    {
        if (false === $this->canAddMoneyWallet()) {
            LibHelper::exitWithError(Labels::getLabel('ERR_YOU_ARE_NOT_ALLOWED_RECHARGE_YOUR_WALLET'), true);
        }
        $this->set('frmRechargeWallet', $this->getRechargeWalletForm($this->siteLangId));
        $this->set('html', $this->_template->render(false, false, NULL, true, false));
        $this->_template->render(false, false, 'json-success.php', true, false);
    }

    public function credits()
    {
        $frm = $this->getCreditsSearchForm($this->siteLangId);
        $codMinWalletBalance = -1;
        if (User::isSeller() && $_SESSION[UserAuthentication::SESSION_ELEMENT_NAME]['activeTab'] == 'S') {
            $shop_cod_min_wallet_balance = Shop::getAttributesByUserId($this->userId, 'shop_cod_min_wallet_balance');
            if ($shop_cod_min_wallet_balance > -1) {
                $codMinWalletBalance = $shop_cod_min_wallet_balance;
            } elseif (FatApp::getConfig('CONF_COD_MIN_WALLET_BALANCE', FatUtility::VAR_FLOAT, -1) > -1) {
                $codMinWalletBalance = FatApp::getConfig('CONF_COD_MIN_WALLET_BALANCE', FatUtility::VAR_FLOAT, -1);
            }
        }
        $txnObj = new Transactions();

        $accountSummary = $txnObj->getTransactionSummary($this->userId);
        $this->set('userWalletBalance', User::getUserBalance($this->userId));
        $this->set('userTotalWalletBalance', User::getUserBalance($this->userId, false, false));
        $this->set('promotionWalletToBeCharged', Promotion::getPromotionWalleToBeCharged($this->userId));
        $this->set('withdrawlRequestAmount', User::getUserWithdrawnRequestAmount($this->userId));
        $this->set('codMinWalletBalance', $codMinWalletBalance);
        $this->set('frmSearch', $frm);
        $this->set('keywordPlaceholder', Labels::getLabel('FRM_SEARCH_BY_TRANSACTION_ID,_ORDER_ID_OR_COMMENT', $this->siteLangId));
        $this->set('accountSummary', $accountSummary);
        $this->set('canAddMoneyToWallet', $this->canAddMoneyWallet());
        $this->set('canRedeemGiftCard', $this->canRedeemGiftCard());
        $this->_template->render();
    }

    public function payouts()
    {
        if (true === MOBILE_APP_API_CALL) {
            $payoutPlugins = Plugin::getDataByType(Plugin::TYPE_PAYOUTS, $this->siteLangId);
            $data = [
                'isBankPayoutEnabled' => applicationConstants::YES,
                'payoutPlugins' => array_values($payoutPlugins)
            ];
            $this->set('data', $data);
        } else {
            $payoutPlugins = Plugin::getNamesWithCode(Plugin::TYPE_PAYOUTS, $this->siteLangId);
            $this->set('payouts', $payoutPlugins);
        }

        $this->_template->render();
    }

    public function setUpWalletRecharge()
    {
        $minimumRechargeAmount = 1;
        $frm = $this->getRechargeWalletForm($this->siteLangId);
        $post = $frm->getFormDataFromArray(FatApp::getPostedData());
        if (false === $post) {
            LibHelper::dieJsonError(current($frm->getValidationErrors()));
        }

        $excludePaymentGatewaysArr = applicationConstants::getExcludePaymentGatewayArr();

        $pmSrch = PaymentMethods::getSearchObject($this->siteLangId);
        $pmSrch->addCondition('plugin_code', 'NOT IN', $excludePaymentGatewaysArr[applicationConstants::CHECKOUT_ADD_MONEY_TO_WALLET]);
        $pmSrch->doNotCalculateRecords();
        $pmSrch->doNotLimitRecords();
        $pmRs = $pmSrch->getResultSet();
        $paymentMethod = FatApp::getDb()->fetch($pmRs);

        if (false == $paymentMethod) {
            LibHelper::dieJsonError(Labels::getLabel("ERR_PAYMENT_METHOD_IS_NOT_AVAILABLE._PLEASE_CONTACT_YOUR_ADMINISTRATOR.", $this->siteLangId));
        }


        $order_net_amount = $post['amount'];
        if ($order_net_amount < $minimumRechargeAmount) {
            $str = Labels::getLabel("LBL_Recharge_amount_must_be_greater_than_{minimumrechargeamount}", $this->siteLangId);
            $str = str_replace("{minimumrechargeamount}", CommonHelper::displayMoneyFormat($minimumRechargeAmount, true, true), $str);
            LibHelper::dieJsonError($str);
        }
        $orderData = array();
        $order_id = isset($_SESSION['wallet_recharge_cart']["order_id"]) ? $_SESSION['wallet_recharge_cart']["order_id"] : false;
        $orderData['order_type'] = Orders::ORDER_WALLET_RECHARGE;

        $orderData['userAddresses'] = array(); //No Need of it
        $orderData['order_id'] = $order_id;
        $orderData['order_user_id'] = $this->userId;
        $orderData['order_payment_status'] = Orders::ORDER_PAYMENT_PENDING;
        $orderData['order_date_added'] = date('Y-m-d H:i:s');

        /* order extras[ */
        $orderData['extra'] = array(
            'oextra_order_id' => $order_id,
            'order_ip_address' => $_SERVER['REMOTE_ADDR']
        );

        if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $orderData['extra']['order_forwarded_ip'] = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } elseif (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            $orderData['extra']['order_forwarded_ip'] = $_SERVER['HTTP_CLIENT_IP'];
        } else {
            $orderData['extra']['order_forwarded_ip'] = '';
        }

        if (isset($_SERVER['HTTP_USER_AGENT'])) {
            $orderData['extra']['order_user_agent'] = $_SERVER['HTTP_USER_AGENT'];
        } else {
            $orderData['extra']['order_user_agent'] = '';
        }

        if (isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
            $orderData['extra']['order_accept_language'] = $_SERVER['HTTP_ACCEPT_LANGUAGE'];
        } else {
            $orderData['extra']['order_accept_language'] = '';
        }
        /* ] */

        $languageRow = Language::getAttributesById($this->siteLangId);
        $orderData['order_language_id'] = $languageRow['language_id'];
        $orderData['order_language_code'] = $languageRow['language_code'];

        $currencyRow = Currency::getAttributesById(CommonHelper::getCurrencyId());
        $orderData['order_currency_id'] = $currencyRow['currency_id'];
        $orderData['order_currency_code'] = $currencyRow['currency_code'];
        $orderData['order_currency_value'] = $currencyRow['currency_value'];

        $orderData['order_user_comments'] = '';
        $orderData['order_admin_comments'] = '';

        $orderData['order_shippingapi_id'] = 0;
        $orderData['order_shippingapi_code'] = '';
        $orderData['order_tax_charged'] = 0;
        $orderData['order_site_commission'] = 0;
        $orderData['order_net_amount'] = $order_net_amount;
        $orderData['order_wallet_amount_charge'] = 0;

        $orderData['orderLangData'] = array();
        $orderObj = new Orders();
        if ($orderObj->addUpdateOrder($orderData, $this->siteLangId)) {
            $order_id = $orderObj->getOrderId();
        } else {
            if (true === MOBILE_APP_API_CALL) {
                LibHelper::dieJsonError($orderObj->getError());
            }
            Message::addErrorMessage($orderObj->getError());
            FatUtility::dieWithError(Message::getHtml());
        }
        if (true === MOBILE_APP_API_CALL) {
            /* Payment Methods[ */
            $pmSrch = PaymentMethods::getSearchObject($this->siteLangId);
            $pmSrch->doNotCalculateRecords();
            $pmSrch->doNotLimitRecords();
            $pmSrch->addMultipleFields(Plugin::ATTRS);
            $pmSrch->addCondition('plugin_code', '!=', 'CashOnDelivery');

            $pmRs = $pmSrch->getResultSet();
            $paymentMethods = FatApp::getDb()->fetchAll($pmRs);
            $excludePaymentGatewaysArr = applicationConstants::getExcludePaymentGatewayArr();
            /* ] */
            $this->set('paymentMethods', $paymentMethods);
            $this->set('excludePaymentGatewaysArr', $excludePaymentGatewaysArr);
            $this->set('order_id', $order_id);
            $this->set('orderType', Orders::ORDER_WALLET_RECHARGE);
            $this->_template->render();
        }
        $this->set('redirectUrl', UrlHelper::generateUrl('WalletPay', 'Recharge', array($order_id), CONF_WEBROOT_FRONT_URL));
        $this->set('msg', Labels::getLabel('MSG_Redirecting', $this->siteLangId));
        $this->_template->render(false, false, 'json-success.php');
    }

    public function creditSearch()
    {
        $frm = $this->getCreditsSearchForm($this->siteLangId);

        $post = $frm->getFormDataFromArray(FatApp::getPostedData());

        //$page = (empty($post['page']) || $post['page'] <= 0) ? 1 : FatUtility::int($post['page']);
        $page = FatApp::getPostedData('page', FatUtility::VAR_INT, 1);
        if ($page < 2) {
            $page = 1;
        }
        $pagesize = FatApp::getConfig('conf_page_size', FatUtility::VAR_INT, 10);

        $debit_credit_type = FatApp::getPostedData('debit_credit_type', FatUtility::VAR_INT, -1);
        $dateOrder = FatApp::getPostedData('date_order', FatUtility::VAR_STRING, "DESC");

        $srch = Transactions::getUserTransactionsObj($this->userId);
        $keyword = FatApp::getPostedData('keyword', null, '');
        if (!empty($keyword)) {
            $cond = $srch->addCondition('utxn.utxn_order_id', 'like', '%' . $keyword . '%');
            $cond->attachCondition('utxn.utxn_op_id', 'like', '%' . $keyword . '%', 'OR');
            $cond->attachCondition('utxn.utxn_comments', 'like', '%' . $keyword . '%', 'OR');
            $cond->attachCondition('concat("TN-" ,lpad( utxn.`utxn_id`,7,0))', 'like', '%' . $keyword . '%', 'OR', true);
        }

        $fromDate = FatApp::getPostedData('date_from', FatUtility::VAR_DATE, '');
        if (!empty($fromDate)) {
            $cond = $srch->addCondition('utxn.utxn_date', '>=', $fromDate);
        }

        $toDate = FatApp::getPostedData('date_to', FatUtility::VAR_DATE, '');
        if (!empty($toDate)) {
            $cond = $srch->addCondition('cast( utxn.`utxn_date` as date)', '<=', $toDate, 'and', true);
        }
        if ($debit_credit_type > 0) {
            switch ($debit_credit_type) {
                case Transactions::CREDIT_TYPE:
                    $srch->addCondition('utxn.utxn_credit', '>', 'mysql_func_0', 'AND', true);
                    $srch->addCondition('utxn.utxn_debit', '=', 'mysql_func_0', 'AND', true);
                    break;

                case Transactions::DEBIT_TYPE:
                    $srch->addCondition('utxn.utxn_debit', '>', 'mysql_func_0', 'AND', true);
                    $srch->addCondition('utxn.utxn_credit', '=', 'mysql_func_0', 'AND', true);
                    break;
            }
        }
        $recordCountSrch = clone $srch;
        $srch->doNotCalculateRecords();
        $srch->setPageNumber($page);
        $srch->setPageSize($pagesize);
        $srch->addOrder('utxn.utxn_date', $dateOrder);
        $records = FatApp::getDb()->fetchAll($srch->getResultSet());
        $this->setRecordCount($recordCountSrch, $pagesize, $page, $post, true);
        $this->set('arrListing', $records);
        $this->set('postedData', $post);
        $this->set('siteLangId', $this->siteLangId);
        $this->set('statusArr', Transactions::getStatusArr($this->siteLangId));
        $this->set('statusClassArr', Transactions::getStatusClassArr());
        $this->set('canRedeemGiftCard', $this->canRedeemGiftCard());
        if (true === MOBILE_APP_API_CALL) {
            $this->creditsInfo();
            $this->_template->render();
        }
        $this->_template->render(false, false);
    }

    private function creditsInfo()
    {
        $this->set('userWalletBalance', User::getUserBalance($this->userId));
        $this->set('userTotalWalletBalance', User::getUserBalance($this->userId, false, false));
        $this->set('promotionWalletToBeCharged', Promotion::getPromotionWalleToBeCharged($this->userId));
        $this->set('withdrawlRequestAmount', User::getUserWithdrawnRequestAmount($this->userId));
    }

    public function requestWithdrawal()
    {
        $frm = $this->getWithdrawalForm($this->siteLangId);

        if (User::isAffiliate()) {
            $fld = $frm->getField('ub_ifsc_swift_code');
            $fld->requirements()->setRegularExpressionToValidate(ValidateElement::USERNAME_REGEX);
        }
        $balance = User::getUserBalance($this->userId);
        $lastWithdrawal = User::getUserLastWithdrawalRequest($this->userId);

        if ($lastWithdrawal && (strtotime($lastWithdrawal["withdrawal_request_date"] . "+" . FatApp::getConfig("CONF_MIN_INTERVAL_WITHDRAW_REQUESTS", FatUtility::VAR_INT, 0) . " days") - time()) > 0) {
            $nextWithdrawalDate = date('d M,Y', strtotime($lastWithdrawal["withdrawal_request_date"] . "+" . FatApp::getConfig("CONF_MIN_INTERVAL_WITHDRAW_REQUESTS", FatUtility::VAR_INT, 0) . " days"));
            Message::addErrorMessage(sprintf(Labels::getLabel('MSG_Withdrawal_Request_Date', $this->siteLangId), FatDate::format($lastWithdrawal["withdrawal_request_date"]), FatDate::format($nextWithdrawalDate), FatApp::getConfig("CONF_MIN_INTERVAL_WITHDRAW_REQUESTS")));
            FatUtility::dieWithError(Message::getHtml());
        }

        $minimumWithdrawLimit = FatApp::getConfig("CONF_MIN_WITHDRAW_LIMIT", FatUtility::VAR_INT, 0);
        if ($balance < $minimumWithdrawLimit) {
            Message::addErrorMessage(sprintf(Labels::getLabel('MSG_Withdrawal_Request_Minimum_Balance_Less', $this->siteLangId), CommonHelper::displayMoneyFormat($minimumWithdrawLimit)));
            FatUtility::dieWithError(Message::getHtml());
        }

        $userObj = new User($this->userId);
        $data = $userObj->getUserBankInfo();
        $data['uextra_payment_method'] = User::AFFILIATE_PAYMENT_METHOD_CHEQUE;

        if (User::isAffiliate()) {
            $userExtraData = User::getUserExtraData($this->userId, array('uextra_payment_method', 'uextra_cheque_payee_name', 'uextra_paypal_email_id'));
            $uextra_payment_method = isset($userExtraData['uextra_payment_method']) ? $userExtraData['uextra_payment_method'] : User::AFFILIATE_PAYMENT_METHOD_CHEQUE;
            $data = array_merge($data, $userExtraData);
            $data['uextra_payment_method'] = $uextra_payment_method;
            $this->set('uextra_payment_method', $uextra_payment_method);
        }

        $frm->fill($data);

        $this->set('frm', $frm);
        $this->set('html', $this->_template->render(false, false, null, true, false));
        $this->_template->render(false, false, 'json-success.php', true, false);
    }

    public function setupRequestWithdrawal()
    {
        $balance = User::getUserBalance($this->userId);
        $lastWithdrawal = User::getUserLastWithdrawalRequest($this->userId);

        if ($lastWithdrawal && (strtotime($lastWithdrawal["withdrawal_request_date"] . "+" . FatApp::getConfig("CONF_MIN_INTERVAL_WITHDRAW_REQUESTS", FatUtility::VAR_INT, 0) . " days") - time()) > 0) {
            $nextWithdrawalDate = date('d M,Y', strtotime($lastWithdrawal["withdrawal_request_date"] . "+" . FatApp::getConfig("CONF_MIN_INTERVAL_WITHDRAW_REQUESTS") . " days"));

            $message = sprintf(Labels::getLabel('MSG_Withdrawal_Request_Date', $this->siteLangId), FatDate::format($lastWithdrawal["withdrawal_request_date"]), FatDate::format($nextWithdrawalDate), FatApp::getConfig("CONF_MIN_INTERVAL_WITHDRAW_REQUESTS"));
            FatUtility::dieJsonError($message);
        }

        $minimumWithdrawLimit = FatApp::getConfig("CONF_MIN_WITHDRAW_LIMIT", FatUtility::VAR_INT, 0);
        if ($balance < $minimumWithdrawLimit) {
            $message = sprintf(Labels::getLabel('MSG_Withdrawal_Request_Minimum_Balance_Less', $this->siteLangId), CommonHelper::displayMoneyFormat($minimumWithdrawLimit));
            FatUtility::dieJsonError($message);
        }

        $frm = $this->getWithdrawalForm($this->siteLangId);
        $post = $frm->getFormDataFromArray(FatApp::getPostedData());

        if (false === $post) {
            LibHelper::dieJsonError(current($frm->getValidationErrors()));
        }

        if (($minimumWithdrawLimit > $post["withdrawal_amount"])) {
            $message = sprintf(Labels::getLabel('MSG_Your_withdrawal_request_amount_is_less_than_the_minimum_allowed_amount_of_%s', $this->siteLangId), CommonHelper::displayMoneyFormat($minimumWithdrawLimit));
            FatUtility::dieJsonError($message);
        }

        $maximumWithdrawLimit = FatApp::getConfig("CONF_MAX_WITHDRAW_LIMIT", FatUtility::VAR_INT, 0);
        if (($maximumWithdrawLimit < $post["withdrawal_amount"])) {
            $message = sprintf(Labels::getLabel('MSG_Your_withdrawal_request_amount_is_greater_than_the_maximum_allowed_amount_of_%s', $this->siteLangId), CommonHelper::displayMoneyFormat($maximumWithdrawLimit));
            FatUtility::dieJsonError($message);
        }

        if (($post["withdrawal_amount"] > $balance)) {
            $message = Labels::getLabel('MSG_Withdrawal_Request_Greater', $this->siteLangId);
            FatUtility::dieJsonError($message);
        }

        $accountNumber = FatApp::getPostedData('ub_account_number', FatUtility::VAR_STRING, 0);

        if ((string) $accountNumber != $post['ub_account_number']) {
            $message = Labels::getLabel('MSG_Invalid_Account_Number', $this->siteLangId);
            FatUtility::dieJsonError($message);
        }


        $userObj = new User($this->userId);
        if (!$userObj->updateBankInfo($post)) {
            $message = Labels::getLabel($userObj->getError(), $this->siteLangId);
            FatUtility::dieJsonError($message);
        }

        $withdrawal_payment_method = FatApp::getPostedData('uextra_payment_method', FatUtility::VAR_INT, 0);

        $withdrawal_payment_method = ($withdrawal_payment_method > 0 && array_key_exists($withdrawal_payment_method, User::getAffiliatePaymentMethodArr($this->siteLangId))) ? $withdrawal_payment_method : User::AFFILIATE_PAYMENT_METHOD_BANK;
        $withdrawal_cheque_payee_name = '';
        $withdrawal_paypal_email_id = '';
        $withdrawal_bank = '';
        $withdrawal_account_holder_name = '';
        $withdrawal_account_number = '';
        $withdrawal_ifc_swift_code = '';
        $withdrawal_bank_address = '';
        $withdrawal_instructions = $post['withdrawal_instructions'];

        switch ($withdrawal_payment_method) {
            case User::AFFILIATE_PAYMENT_METHOD_CHEQUE:
                $withdrawal_cheque_payee_name = $post['uextra_cheque_payee_name'];
                break;
            case User::AFFILIATE_PAYMENT_METHOD_BANK:
                $withdrawal_bank = $post['ub_bank_name'];
                $withdrawal_account_holder_name = $post['ub_account_holder_name'];
                $withdrawal_account_number = $post['ub_account_number'];
                $withdrawal_ifc_swift_code = $post['ub_ifsc_swift_code'];
                $withdrawal_bank_address = $post['ub_bank_address'];
                break;
            case User::AFFILIATE_PAYMENT_METHOD_PAYPAL:
                $withdrawal_paypal_email_id = $post['uextra_paypal_email_id'];
                break;
        }


        $post['withdrawal_payment_method'] = $withdrawal_payment_method;
        $post['withdrawal_cheque_payee_name'] = $withdrawal_cheque_payee_name;
        $post['withdrawal_paypal_email_id'] = $withdrawal_paypal_email_id;

        $post['ub_bank_name'] = $withdrawal_bank;
        $post['ub_account_holder_name'] = $withdrawal_account_holder_name;
        $post['ub_account_number'] = $withdrawal_account_number;
        $post['ub_ifsc_swift_code'] = $withdrawal_ifc_swift_code;
        $post['ub_bank_address'] = $withdrawal_bank_address;

        $post['withdrawal_instructions'] = $withdrawal_instructions;

        if (!$withdrawRequestId = $userObj->addWithdrawalRequest(array_merge($post, array("ub_user_id" => $this->userId)), $this->siteLangId)) {
            $message = Labels::getLabel($userObj->getError(), $this->siteLangId);
            FatUtility::dieJsonError($message);
        }

        $emailNotificationObj = new EmailHandler();
        if (!$emailNotificationObj->sendWithdrawRequestNotification($withdrawRequestId, $this->siteLangId, "A")) {
            $message = Labels::getLabel($emailNotificationObj->getError(), $this->siteLangId);
            FatUtility::dieJsonError($message);
        }

        //send notification to admin
        $notificationData = array(
            'notification_record_type' => Notification::TYPE_WITHDRAWAL_REQUEST,
            'notification_record_id' => $withdrawRequestId,
            'notification_user_id' => $this->userId,
            'notification_label_key' => Notification::WITHDRAWL_REQUEST_NOTIFICATION,
            'notification_added_on' => date('Y-m-d H:i:s'),
        );

        if (!Notification::saveNotifications($notificationData)) {
            $message = Labels::getLabel("MSG_NOTIFICATION_COULD_NOT_BE_SENT", $this->siteLangId);
            FatUtility::dieJsonError($message);
        }
        CalculativeDataRecord::updateWithdrawalRequestCount();
        $this->set('msg', Labels::getLabel('MSG_Withdraw_request_placed_successfully', $this->siteLangId));

        if (true === MOBILE_APP_API_CALL) {
            $this->_template->render();
        }
        $this->_template->render(false, false, 'json-success.php');
    }

    public function removeProfileImage()
    {
        if (1 > $this->userId) {
            $message = Labels::getLabel('MSG_INVALID_REQUEST_ID', $this->siteLangId);
            FatUtility::dieJsonError($message);
        }

        $fileHandlerObj = new AttachedFile();
        if (!$fileHandlerObj->deleteFile(AttachedFile::FILETYPE_USER_PROFILE_IMAGE, $this->userId)) {
            $message = Labels::getLabel($fileHandlerObj->getError(), $this->siteLangId);
            FatUtility::dieJsonError($message);
        }

        if (!$fileHandlerObj->deleteFile(AttachedFile::FILETYPE_USER_PROFILE_CROPED_IMAGE, $this->userId)) {
            $message = Labels::getLabel($fileHandlerObj->getError(), $this->siteLangId);
            FatUtility::dieJsonError($message);
        }

        $this->set('msg', Labels::getLabel('MSG_Profile_Image_Removed_Successfully', $this->siteLangId));
        if (true ===  MOBILE_APP_API_CALL) {
            $userImgUpdatedOn = User::getAttributesById($this->userId, 'user_updated_on');
            $uploadedTime = AttachedFile::setTimeParam($userImgUpdatedOn);
            $userImage = UrlHelper::getCachedUrl(UrlHelper::generateFullFileUrl('Image', 'user', array($this->userId, ImageDimension::VIEW_THUMB, true), CONF_WEBROOT_FRONTEND) . $uploadedTime, CONF_IMG_CACHE_TIME, '.jpg');

            $data = array('userImage' => $userImage);

            $this->set('data', $data);
            $this->_template->render();
        }

        $this->_template->render(false, false, 'json-success.php');
    }

    public function userProfileImage($sizeType = '', $cropedImage = false)
    {
        $default_image = 'user_deafult_image.jpg';
        $recordId = FatUtility::int($this->userId);

        $file_row = false;
        if ($cropedImage == true) {
            $file_row = AttachedFile::getAttachment(AttachedFile::FILETYPE_USER_PROFILE_CROPED_IMAGE, $recordId);
        }

        if ($file_row == false) {
            $file_row = AttachedFile::getAttachment(AttachedFile::FILETYPE_USER_PROFILE_IMAGE, $recordId);
        }

        $image_name = isset($file_row['afile_physical_path']) ? $file_row['afile_physical_path'] : '';
        $image_name = AttachedFile::setNamePrefix($image_name, $sizeType);

        $imageDimensions = ImageDimension::getData(ImageDimension::TYPE_USER_PROFILE_IMAGE, $sizeType);

        if ($sizeType) {
            AttachedFile::displayImage($image_name, $imageDimensions['width'], $imageDimensions['height'], $default_image);
        } else {
            AttachedFile::displayOriginalImage($image_name, $default_image);
        }
    }

    public function profileInfo()
    {
        if (true === MOBILE_APP_API_CALL) {
            $userImgUpdatedOn = User::getAttributesById($this->userId, 'user_updated_on');
            $uploadedTime = AttachedFile::setTimeParam($userImgUpdatedOn);

            $hasDigitalProducts = 0;

            $srch = Product::getSearchObject();
            $srch->addMultipleFields(['product_id']);
            $srch->addCondition('product_type', '=', 'mysql_func_' . Product::PRODUCT_TYPE_DIGITAL, 'AND', true);
            $srch->setPageSize(1);
            $rs = $srch->getResultSet();
            $row = $this->db->fetch($rs);
            if (!empty($row) && 0 < count($row)) {
                $hasDigitalProducts = 1;
            }
            $splitPaymentMethods = Plugin::getDataByType(Plugin::TYPE_SPLIT_PAYMENT_METHOD, $this->siteLangId);
            $this->loadBankInfoForm();
            $personalInfo = $this->personalInfo();
            $personalInfo['userImage'] = UrlHelper::getCachedUrl(UrlHelper::generateFullFileUrl('image', 'user', array($this->userId, ImageDimension::VIEW_SMALL, true), CONF_WEBROOT_FRONTEND) . $uploadedTime, CONF_IMG_CACHE_TIME, '.jpg');
            $this->set('personalInfo', empty($personalInfo) ? (object) array() : $personalInfo);
            $this->set('privacyPolicyLink', FatApp::getConfig('CONF_PRIVACY_POLICY_PAGE', FatUtility::VAR_STRING, ''));
            $this->set('hasDigitalProducts', $hasDigitalProducts);
            $this->set('splitPaymentMethods', $splitPaymentMethods);
            $this->_template->render();
        }

        $this->_template->addJs('js/jquery.form.js');
        $this->_template->addJs('js/cropper.js');
        $this->_template->addJs('js/cropper-main.js');
        $this->includeDateTimeFiles();

        $userObj = new User($this->userId);
        $srch = $userObj->getUserSearchObj(array('user_preferred_dashboard', 'user_registered_initially_for', 'user_parent', 'uc.credential_active', 'uc.credential_verified'));
        $data = FatApp::getDb()->fetch($srch->getResultSet());
        if ($data === false) {
            FatUtility::dieWithError(Labels::getLabel('MSG_INVALID_ACCESS', $this->siteLangId));
        }

        $showSellerActivateButton = false;
        if (!User::canAccessSupplierDashboard() && $data['user_registered_initially_for'] == User::USER_TYPE_SELLER) {
            $showSellerActivateButton = true;
        }

        $payoutPlugins = Plugin::getNamesWithCode(Plugin::TYPE_PAYOUTS, $this->siteLangId);

        $this->set('loggedUserInfo', $data);
        $this->set('userParentId', $data['user_parent']);
        $this->set('payouts', $payoutPlugins);
        $this->set('showSellerActivateButton', $showSellerActivateButton);
        $this->set('userPreferredDashboard', $data['user_preferred_dashboard']);
        $this->_template->render();
    }

    public function personalInfo()
    {
        $userObj = new User($this->userId);
        $srch = $userObj->getUserSearchObj();
        $srch->addMultipleFields(array('u.*', 'country_name', 'state_name'));
        $srch->joinTable('tbl_countries_lang', 'LEFT JOIN', 'countrylang_country_id = user_country_id and countrylang_lang_id = ' . $this->siteLangId);
        $srch->joinTable('tbl_states_lang', 'LEFT JOIN', 'statelang_state_id = user_state_id and statelang_lang_id = ' . $this->siteLangId);
        $rs = $srch->getResultSet();
        $data = FatApp::getDb()->fetch($rs, 'user_id');
        if (true === MOBILE_APP_API_CALL) {
            return $data;
        }
        $this->set('info', $data);
        $this->_template->render(false, false);
    }

    public function profileInfoForm()
    {
        $frm = $this->getProfileInfoForm();
        $imgFrm = $this->getProfileImageForm();
        $stateId = 0;

        $userObj = new User($this->userId);
        $srch = $userObj->getUserSearchObj();
        $srch->addMultipleFields(array('u.*'));
        $rs = $srch->getResultSet();
        $data = FatApp::getDb()->fetch($rs, 'user_id');

        if (User::isAffiliate()) {
            $userExtraData = User::getUserExtraData($this->userId, array('uextra_company_name', 'uextra_website'));
            $userExtraData = ($userExtraData) ? $userExtraData : array();
            $data = array_merge($userExtraData, $data);
        }

        if ($data['user_dob'] == "0000-00-00") {
            $dobFld = $frm->getField('user_dob');
            $dobFld->requirements()->setRequired(true);
        }

        $frm->fill($data);
        $stateId = $data['user_state_id'];

        $mode = 'Add';
        $file_row = AttachedFile::getAttachment(AttachedFile::FILETYPE_USER_PROFILE_IMAGE, $this->userId);
        if (0 < $file_row['afile_id']) {
            $mode = 'Edit';
        }

        $countryIso = Countries::getCountryById($data['user_country_id'], $this->siteLangId, 'country_code');
        $this->set('countryIso', $countryIso);
        $this->set('data', $data);
        $this->set('frm', $frm);
        $this->set('imgFrm', $imgFrm);
        $this->set('mode', $mode);
        $this->set('stateId', $stateId);
        $this->set('siteLangId', $this->siteLangId);
        $this->_template->render(false, false);
    }

    public function imgCropper()
    {
        $userImgUpdatedOn = User::getAttributesById($this->userId, 'user_updated_on');
        $uploadedTime = AttachedFile::setTimeParam($userImgUpdatedOn);
        $fileRow = AttachedFile::getAttachment(AttachedFile::FILETYPE_USER_PROFILE_IMAGE, $this->userId);
        $userImage = "";
        if (0 < $fileRow['afile_id']) {
            $userImage = UrlHelper::getCachedUrl(UrlHelper::generateFullFileUrl('image', 'user', array($this->userId), CONF_WEBROOT_FRONTEND) . $uploadedTime, CONF_IMG_CACHE_TIME, '.jpg');
        }

        $this->set('image', $userImage);
        $this->_template->render(false, false, 'cropper/index.php');
    }

    public function profileImageForm()
    {
        $imgFrm = $this->getProfileImageForm();
        $mode = 'Add';
        $file_row = AttachedFile::getAttachment(AttachedFile::FILETYPE_USER_PROFILE_IMAGE, $this->userId);
        if ($file_row != false) {
            $mode = 'Edit';
        }
        $this->set('mode', $mode);
        $this->set('imgFrm', $imgFrm);
        $this->set('siteLangId', $this->siteLangId);
        $this->_template->render(false, false);
    }

    public function uploadProfileImage()
    {
        $post = FatApp::getPostedData();
        if (empty($post)) {
            $message = Labels::getLabel('LBL_Invalid_Request_Or_File_not_supported', $this->siteLangId);
            FatUtility::dieJsonError($message);
        }
        $updatedAt = date('Y-m-d H:i:s');
        $uploadedTime = AttachedFile::setTimeParam($updatedAt);

        if (isset($_FILES['org_image']['tmp_name'])) {
            $fileHandlerObj = new AttachedFile();
            if (!$fileHandlerObj->isUploadedFile($_FILES['org_image']['tmp_name'])) {
                FatUtility::dieJsonError($fileHandlerObj->getError());
            }
            if (!$res = $fileHandlerObj->saveImage($_FILES['org_image']['tmp_name'], AttachedFile::FILETYPE_USER_PROFILE_IMAGE, $this->userId, 0, $_FILES['org_image']['name'], -1, true)) {
                $message = Labels::getLabel($fileHandlerObj->getError(), $this->siteLangId);
                FatUtility::dieJsonError($message);
            }
        }

        if (isset($_FILES['cropped_image']['tmp_name'])) {
            $fileHandlerObj = new AttachedFile();
            if (!$fileHandlerObj->isUploadedFile($_FILES['cropped_image']['tmp_name'])) {
                FatUtility::dieJsonError($fileHandlerObj->getError());
            }

            if (!$res = $fileHandlerObj->saveImage($_FILES['cropped_image']['tmp_name'], AttachedFile::FILETYPE_USER_PROFILE_CROPED_IMAGE, $this->userId, 0, $_FILES['cropped_image']['name'], -1, true)) {
                $message = Labels::getLabel($fileHandlerObj->getError(), $this->siteLangId);
                FatUtility::dieJsonError($message);
            }
        }

        if (false === MOBILE_APP_API_CALL) {
            $profileImg = UrlHelper::getCachedUrl(UrlHelper::generateFullFileUrl('Account', 'userProfileImage', array(ImageDimension::VIEW_CROPED, 1)) . $uploadedTime, CONF_IMG_CACHE_TIME, '.jpg');
            $this->set('file', $profileImg);
        } else {
            $profileImg = UrlHelper::getCachedUrl(UrlHelper::generateFullFileUrl('Image', 'user', array($this->userId, ImageDimension::VIEW_MINI, 1), CONF_WEBROOT_FRONTEND) . $uploadedTime, CONF_IMG_CACHE_TIME, '.jpg');
            $this->set('file', $profileImg);
        }
        $this->set('file', $profileImg);

        User::setImageUpdatedOn($this->userId, $updatedAt);
        $this->set('msg', Labels::getLabel('MSG_File_uploaded_successfully', $this->siteLangId));
        if (true === MOBILE_APP_API_CALL) {
            $this->_template->render();
        }
        $this->_template->render(false, false, 'json-success.php');
    }

    public function updateProfileInfo()
    {
        $frm = $this->getProfileInfoForm();

        $post = FatApp::getPostedData();

        if (1 > count($post) && true === MOBILE_APP_API_CALL) {
            LibHelper::dieJsonError(Labels::getLabel("ERR_INVALID_REQUEST", $this->siteLangId));
        }

        $dob = FatApp::getPostedData('user_dob', FatUtility::VAR_STRING, '');
        if (CommonHelper::isFieldEncrypted($dob) == true) {
            unset($post['user_dob']);
        }

        $userphone = FatApp::getPostedData('user_phone');
        if (CommonHelper::isFieldEncrypted($userphone) == true) {
            unset($post['user_phone']);
        }

        $user_state_id = FatApp::getPostedData('user_state_id', FatUtility::VAR_INT, 0);
        $post = $frm->getFormDataFromArray($post);

        if (false === $post) {
            $message = Labels::getLabel(current($frm->getValidationErrors()), $this->siteLangId);
            FatUtility::dieJsonError($message);
        }

        if (strtotime($post['user_dob']) > time()) {
            $message = Labels::getLabel("MSG_Invalid_date_of_birth", $this->siteLangId);
            FatUtility::dieJsonError($message);
        }

        $post['user_state_id'] = $user_state_id;

        if (isset($post['user_id'])) {
            unset($post['user_id']);
        }


        $post['user_phone_dcode'] = FatApp::getPostedData('user_phone_dcode', FatUtility::VAR_STRING, '');

        if (isset($post['user_phone']) && true == SmsArchive::canSendSms()) {
            unset($post['user_phone'], $post['user_phone_dcode']);
        }

        if ($post['user_dob'] == "0000-00-00" || $post['user_dob'] == "" || strtotime($post['user_dob']) == 0) {
            unset($post['user_dob']);
        }

        unset($post['credential_username'], $post['credential_email']);

        /* saving user extras[ */
        if (User::isAffiliate()) {
            $dataToSave = array(
                'uextra_user_id' => $this->userId,
                'uextra_company_name' => $post['uextra_company_name'],
                'uextra_website' => CommonHelper::processUrlString($post['uextra_website'])
            );
            $dataToUpdateOnDuplicate = $dataToSave;
            unset($dataToUpdateOnDuplicate['uextra_user_id']);
            if (!FatApp::getDb()->insertFromArray(User::DB_TBL_USR_EXTRAS, $dataToSave, false, array(), $dataToUpdateOnDuplicate)) {
                $message = Labels::getLabel("LBL_Details_could_not_be_saved!", $this->siteLangId);
                if (true === MOBILE_APP_API_CALL) {
                    FatUtility::dieJsonError($message);
                }

                Message::addErrorMessage($message);
                if (FatUtility::isAjaxCall()) {
                    FatUtility::dieWithError(Message::getHtml());
                }
                FatApp::redirectUser(UrlHelper::generateUrl('Account', 'ProfileInfo'), [], CONF_WEBROOT_DASHBOARD);
            }
        }
        /* ] */

        $userObj = new User($this->userId);
        if (isset($post['user_phone']) && empty($post['user_phone'])) {
            $userObj->setFldValue('user_phone', 'mysql_func_null', true);
            $userObj->setFldValue('user_phone_dcode', '');
        }
        $userObj->assignValues($post);
        if (!$userObj->save()) {
            $msg = $userObj->getError();
            if (false !== strpos(strtolower($msg), 'duplicate')) {
                $msg = Labels::getLabel('ERR_DUPLICATE_RECORD', $this->siteLangId);
            }
            LibHelper::exitWithError($msg, true);
        }

        $postUserName = isset($post['user_name']) ? $post['user_name'] : '';
        $sessionUserName = isset($_SESSION[UserAuthentication::SESSION_ELEMENT_NAME]['user_name']) ? $_SESSION[UserAuthentication::SESSION_ELEMENT_NAME]['user_name'] : '';
        if (!empty($postUserName) && !empty($sessionUserName) && $postUserName != $sessionUserName) {
            $_SESSION[UserAuthentication::SESSION_ELEMENT_NAME]['user_name'] = $postUserName;
        }

        $this->set('msg', Labels::getLabel('MSG_Updated_Successfully', $this->siteLangId));
        if (true === MOBILE_APP_API_CALL) {
            $this->_template->render();
        }

        $this->_template->render(false, false, 'json-success.php');
    }

    private function loadBankInfoForm()
    {
        if (User::isAffiliate()) {
            $message = Labels::getLabel('LBL_Invalid_Request', $this->siteLangId);
            FatUtility::dieJsonError($message);
        }

        $userObj = new User($this->userId);
        $data = $userObj->getUserBankInfo();

        if (true === MOBILE_APP_API_CALL) {
            $this->set('bankInfo', (object) $data);
            return;
        }

        $frm = $this->getBankInfoForm();
        if ($data != false) {
            $frm->fill($data);
        }
        $this->set('frm', $frm);
        $this->set('info', $data);
    }

    public function bankInfo()
    {
        $this->loadBankInfoForm();
        $this->_template->render();
    }

    public function bankInfoForm()
    {
        $this->loadBankInfoForm();
        $this->_template->addJs('account/page-js/profile-info.js');
        $this->_template->render();
    }

    public function settingsInfo()
    {
        $frm = $this->getSettingsForm();

        $userObj = new User($this->userId);
        $srch = $userObj->getUserSearchObj();
        $srch->addMultipleFields(array('u.*'));
        $rs = $srch->getResultSet();
        $data = FatApp::getDb()->fetch($rs, 'user_id');
        if ($data != false) {
            $frm->fill($data);
        }

        $this->set('frm', $frm);
        $this->_template->render(false, false);
    }

    public function updateBankInfo()
    {
        $post = FatApp::getPostedData();
        if (1 > count($post) && true === MOBILE_APP_API_CALL) {
            LibHelper::dieJsonError(Labels::getLabel("ERR_INVALID_REQUEST", $this->siteLangId));
        }

        $frm = $this->getBankInfoForm();
        $post = $frm->getFormDataFromArray($post);

        if (false === $post) {
            $message = Labels::getLabel(current($frm->getValidationErrors()), $this->siteLangId);
            FatUtility::dieJsonError($message);
        }
        $accountNumber = FatApp::getPostedData('ub_account_number', FatUtility::VAR_STRING, 0);

        if ((string) $accountNumber != $post['ub_account_number']) {
            $message = Labels::getLabel('MSG_Invalid_Account_Number', $this->siteLangId);
            FatUtility::dieJsonError($message);
        }


        $userObj = new User($this->userId);
        if (!$userObj->updateBankInfo($post)) {
            $message = Labels::getLabel($userObj->getError(), $this->siteLangId);
            FatUtility::dieJsonError($message);
        }

        $this->set('msg', Labels::getLabel('MSG_Updated_Successfully', $this->siteLangId));
        if (true === MOBILE_APP_API_CALL) {
            $this->_template->render();
        }

        $this->_template->render(false, false, 'json-success.php');
    }

    public function updateSettingsInfo()
    {
        $frm = $this->getSettingsForm();
        $post = $frm->getFormDataFromArray(FatApp::getPostedData());

        if (false === $post) {
            FatUtility::dieJsonError(current($frm->getValidationErrors()));
        }

        $userObj = new User($this->userId);
        if (!$userObj->updateSettingsInfo($post)) {
            FatUtility::dieJsonError(Labels::getLabel($userObj->getError(), $this->siteLangId));
        }

        $this->set('msg', Labels::getLabel('MSG_Setup_successful', $this->siteLangId));
        $this->_template->render(false, false, 'json-success.php');
    }

    public function changeEmailForm()
    {
        $frm = $this->getChangeEmailForm();

        $this->set('frm', $frm);
        $this->set('siteLangId', $this->siteLangId);
        $this->_template->render(false, false);
    }

    public function updateEmail()
    {
        $emailFrm = $this->getChangeEmailForm();
        $post = $emailFrm->getFormDataFromArray(FatApp::getPostedData());

        if (false === $post) {
            $message = $emailFrm->getValidationErrors();
            if (true === MOBILE_APP_API_CALL) {
                LibHelper::dieJsonError(current($message));
            }
            FatUtility::dieJsonError($message);
        }

        if ($post['new_email'] != $post['conf_new_email']) {
            $message = Labels::getLabel('MSG_New_email_confirm_email_does_not_match', $this->siteLangId);
            FatUtility::dieJsonError($message);
        }

        $userAuthObj = new UserAuthentication();
        if ($userAuthObj->getUserByEmail($post['new_email'], false, false)) {
            LibHelper::dieJsonError(Labels::getLabel('ERR_EMAIL_ALREADY_EXIST', $this->siteLangId));
        }

        $userObj = new User($this->userId);
        $srch = $userObj->getUserSearchObj(array('user_id', 'credential_password', 'credential_email', 'user_name', 'user_phone_dcode', 'user_phone'));
        $rs = $srch->getResultSet();

        if (!$rs) {
            $message = Labels::getLabel('MSG_INVALID_REQUEST', $this->siteLangId);
            FatUtility::dieJsonError($message);
        }

        $data = FatApp::getDb()->fetch($rs, 'user_id');

        if ($data === false) {
            $message = Labels::getLabel('MSG_INVALID_REQUEST', $this->siteLangId);
            FatUtility::dieJsonError($message);
        }

        if (false == password_verify($post['current_password'], $data['credential_password'])) {
            $message = Labels::getLabel('MSG_YOUR_CURRENT_PASSWORD_MIS_MATCHED', $this->siteLangId);
            FatUtility::dieJsonError($message);
        }

        $phone = array_key_exists('user_phone', $data) ? $data['user_phone'] : '';
        $dialCode = array_key_exists('user_phone_dcode', $data) ? ValidateElement::formatDialCode($data['user_phone_dcode']) : '';
        $arr = array(
            'user_name' => $data['user_name'],
            'user_phone_dcode' => $dialCode,
            'user_phone' => $phone,
            'user_email' => $data['credential_email'],
            'user_new_email' => $post['new_email']
        );

        if (!$this->userEmailVerifications($userObj, $arr)) {
            $message = Labels::getLabel('MSG_ERROR_IN_SENDING_VERFICATION_EMAIL', $this->siteLangId);
            FatUtility::dieJsonError($message);
        }

        $this->set('msg', Labels::getLabel('MSG_CHANGE_EMAIL_REQUEST_SENT_SUCCESSFULLY', $this->siteLangId));
        if (true === MOBILE_APP_API_CALL) {
            $this->_template->render();
        }
        $this->_template->render(false, false, 'json-success.php');
    }

    public function updateEmailPasswordUsingPhone()
    {
        $emailFrm = $this->getChangeEmailUsingPhoneForm2();
        $post = $emailFrm->getFormDataFromArray(FatApp::getPostedData());

        if (false === $post) {
            $message = $emailFrm->getValidationErrors();
            if (true === MOBILE_APP_API_CALL) {
                LibHelper::dieJsonError(current($message));
            }
            FatUtility::dieJsonError($message);
        }
        $userAuthObj = new UserAuthentication();
        if ($userAuthObj->getUserByEmail($post['new_email'], false, false)) {
            LibHelper::dieJsonError(Labels::getLabel('ERR_EMAIL_ALREADY_EXIST', $this->siteLangId));
        }

        $userObj = new User($this->userId);
        if (!$userObj->verifyUserPhoneOtp($post['otp'], false, false)) {
            LibHelper::dieJsonError(Labels::getLabel('MSG_INVALID_REQUEST', $this->siteLangId));
        }

        $srch = $userObj->getUserSearchObj(array('user_id', 'credential_password', 'credential_email', 'user_name', 'user_phone_dcode', 'user_phone'));
        $data = FatApp::getDb()->fetch($srch->getResultSet(), 'user_id');

        if ($data === false ||  !empty($data['credential_email'])) {
            $message = Labels::getLabel('MSG_INVALID_REQUEST', $this->siteLangId);
            FatUtility::dieJsonError($message);
        }
        if (!$userObj->setLoginPassword($post['new_password'])) {
            $message = Labels::getLabel('MSG_Password_could_not_be_set', $this->siteLangId) . $userObj->getError();
            FatUtility::dieJsonError($message);
        }

        $phone = array_key_exists('user_phone', $data) ? $data['user_phone'] : '';
        $dialCode = array_key_exists('user_phone_dcode', $data) ? ValidateElement::formatDialCode($data['user_phone_dcode']) : '';
        $arr = array(
            'user_name' => $data['user_name'],
            'user_phone_dcode' => $dialCode,
            'user_phone' => $phone,
            'user_email' => $data['credential_email'],
            'user_new_email' => $post['new_email']
        );

        if (!$this->userEmailVerifications($userObj, $arr)) {
            $message = Labels::getLabel('MSG_ERROR_IN_SENDING_VERFICATION_EMAIL', $this->siteLangId);
            FatUtility::dieJsonError($message);
        }

        $this->set('msg', Labels::getLabel('MSG_CHANGE_EMAIL_REQUEST_SENT_SUCCESSFULLY', $this->siteLangId));
        if (true === MOBILE_APP_API_CALL) {
            $this->_template->render();
        }
        $this->_template->render(false, false, 'json-success.php');
    }

    public function moveToWishList($selProdId)
    {
        $wishList = new UserWishList();
        $defaultWishListId = $wishList->getWishListId($this->userId, UserWishList::TYPE_DEFAULT_WISHLIST);
        $this->addRemoveWishListProduct($selProdId, $defaultWishListId);
    }

    public function moveToSaveForLater($selProdId)
    {
        $wishList = new UserWishList();
        $wishListId = $wishList->getWishListId($this->userId, UserWishList::TYPE_SAVE_FOR_LATER);
        if (!$wishList->addUpdateListProducts($wishListId, $selProdId)) {
            FatUtility::dieJsonError(Labels::getLabel("ERR_INVALID_REQUEST", $this->siteLangId));
        }

        $cartObj = new Cart($this->userId, $this->siteLangId, $this->app_user['temp_user_id']);
        $key = md5(base64_encode(json_encode(Cart::CART_KEY_PREFIX_PRODUCT . $selProdId)));
        if (!$cartObj->remove($key)) {
            LibHelper::dieJsonError($cartObj->getError());
        }

        if (true === MOBILE_APP_API_CALL) {
            $fulfilmentType = FatApp::getPostedData('fulfilmentType', FatUtility::VAR_INT, Shipping::FULFILMENT_SHIP);
            $cartObj = new Cart(UserAuthentication::getLoggedUserId(true), $this->siteLangId, $this->app_user['temp_user_id'], Cart::PAGE_TYPE_CART);
            $cartObj->setFulfilmentType($fulfilmentType);
            $cartObj->setCartCheckoutType($fulfilmentType);
            $productsArr = $cartObj->getProducts($this->siteLangId);
            $cartSummary = $cartObj->getCartFinancialSummary($this->siteLangId);
            $this->set('products', $productsArr);
            $this->set('cartSummary', $cartSummary);
            $this->_template->render();
        }

        $this->_template->render(false, false, 'json-success.php');
    }

    /* called from products listing page */
    public function viewWishList($selprod_id, $excludeWishList = 0)
    {
        $excludeWishList = FatUtility::int($excludeWishList);
        $wishLists = UserWishList::getUserWishLists($this->userId, true, $excludeWishList);
        $frm = $this->getCreateWishListForm();
        $frm->fill(array('selprod_id' => $selprod_id));
        $this->set('frm', $frm);
        $this->set('wishLists', $wishLists);
        $this->set('selprod_id', $selprod_id);
        $this->_template->render(false, false);
    }

    public function setupWishList()
    {
        $frm = $this->getCreateWishListForm();
        $post = $frm->getFormDataFromArray(FatApp::getPostedData());
        $selprod_id = FatUtility::int($post['selprod_id']);
        if (false === $post) {
            $message = current($frm->getValidationErrors());
            if (true === MOBILE_APP_API_CALL) {
                FatUtility::dieJsonError($message);
            }
            Message::addErrorMessage($message);
            FatUtility::dieWithError(Message::getHtml());
        }
        $wListObj = new UserWishList();
        $data_to_save_arr = $post;
        $data_to_save_arr['uwlist_added_on'] = date('Y-m-d H:i:s');
        $data_to_save_arr['uwlist_user_id'] = $this->userId;
        $wListObj->assignValues($data_to_save_arr);

        /* create new List[ */
        if (!$wListObj->save()) {
            $message = $wListObj->getError();
            if (true === MOBILE_APP_API_CALL) {
                FatUtility::dieJsonError($message);
            }
            Message::addErrorMessage($message);
            FatUtility::dieWithError(Message::getHtml());
        }
        $uwlp_uwlist_id = $wListObj->getMainTableRecordId();
        /* ] */

        $successMsg = Labels::getLabel('LBL_WishList_Created_Successfully', $this->siteLangId);
        /* Assign current product to newly created list[ */
        if ($uwlp_uwlist_id && $selprod_id) {
            if (!$wListObj->addUpdateListProducts($uwlp_uwlist_id, $selprod_id)) {
                Message::addMessage($successMsg);
                $msg = Labels::getLabel('LBL_Error_while_assigning_product_under_selected_list.', $this->siteLangId);

                if (true === MOBILE_APP_API_CALL) {
                    LibHelper::dieJsonError($msg);
                }
                Message::addErrorMessage($msg);
                FatUtility::dieWithError(Message::getHtml());
            }
        }
        /* ] */

        //UserWishList
        $srch = UserWishList::getSearchObject($this->userId);
        $srch->joinTable(UserWishList::DB_TBL_LIST_PRODUCTS, 'LEFT OUTER JOIN', 'uwlist_id = uwlp_uwlist_id');
        $srch->addCondition('uwlp_selprod_id', '=', 'mysql_func_' . $selprod_id, 'AND', true);
        $srch->doNotCalculateRecords();
        $srch->doNotLimitRecords();
        $srch->addMultipleFields(array('uwlist_id'));
        $rs = $srch->getResultSet();
        $row = FatApp::getDb()->fetch($rs);
        $productIsInAnyList = false;
        if ($row) {
            $productIsInAnyList = true;
        }

        $this->set('productIsInAnyList', $productIsInAnyList);
        $this->set('wish_list_id', $uwlp_uwlist_id);
        $this->set('msg', $successMsg);
        if (true === MOBILE_APP_API_CALL) {
            $this->set('data', ['wish_list_id' => $uwlp_uwlist_id]);
            $this->_template->render();
        }
        $this->_template->render(false, false, 'json-success.php');
    }

    public function addRemoveWishListProductArr()
    {
        $selprod_id_arr = FatApp::getPostedData('selprod_id');
        $selprod_id_arr = !empty($selprod_id_arr) ? array_filter($selprod_id_arr) : array();

        $uwlist_id = FatApp::getPostedData('uwlist_id', FatUtility::VAR_INT, 0);

        if (empty($selprod_id_arr) || empty($uwlist_id)) {
            $message = Labels::getLabel('LBL_Invalid_Request', $this->siteLangId);
            if (true === MOBILE_APP_API_CALL) {
                FatUtility::dieJsonError($message);
            }
            Message::addErrorMessage($message);
            FatUtility::dieWithError(Message::getHtml());
        }

        foreach ($selprod_id_arr as $selprod_id) {
            $action = $this->updateWishList($selprod_id, $uwlist_id);
        }

        if (true === MOBILE_APP_API_CALL) {
            $this->_template->render();
        }

        $this->_template->render(false, false, 'json-success.php');
    }

    public function updateRemoveWishListProduct($selprodId, $wishListId)
    {
        $selprodIdArr = FatApp::getPostedData('selprod_id');
        $oldWishlistId = FatApp::getPostedData('uwlist_id', FatUtility::VAR_INT, 0);

        if (empty($selprodIdArr) || empty($oldWishlistId)) {
            Message::addErrorMessage(Labels::getLabel("LBL_Invalid_Request", $this->siteLangId));
            FatUtility::dieWithError(Message::getHtml());
        }
        foreach ($selprodIdArr as $selprodId) {
            $this->updateWishList($selprodId, $oldWishlistId);
            $isExists = UserWishList::getListProductsByListId($wishListId, $selprodId);
            if (empty($isExists)) {
                $this->updateWishList($selprodId, $wishListId);
            }
        }
        $this->_template->render(false, false, 'json-success.php');
    }

    public function addRemoveWishListProduct($selprod_id, $wish_list_id, $rowAction = '', $removeFromCart = 0)
    {
        $selprod_id = FatUtility::int($selprod_id);
        $wish_list_id = FatUtility::int($wish_list_id);
        $rowAction = ('' == $rowAction ? -1 : $rowAction);

        if (1 > $wish_list_id) {
            $wishList = new UserWishList();
            $wish_list_id = $wishList->getWishListId($this->userId, UserWishList::TYPE_DEFAULT_WISHLIST);
        }

        if (1 > $selprod_id) {
            $message = Labels::getLabel('LBL_Invalid_Request', $this->siteLangId);
            if (true === MOBILE_APP_API_CALL) {
                FatUtility::dieJsonError($message);
            }
            Message::addErrorMessage($message);
            FatUtility::dieWithError(Message::getHtml());
        }

        $action = $this->updateWishList($selprod_id, $wish_list_id, $rowAction);

        //UserWishList
        $srch = UserWishList::getSearchObject($this->userId);
        $srch->joinTable(UserWishList::DB_TBL_LIST_PRODUCTS, 'LEFT OUTER JOIN', 'uwlist_id = uwlp_uwlist_id');
        $srch->addCondition('uwlp_selprod_id', '=', 'mysql_func_' . $selprod_id, 'AND', true);
        $srch->doNotCalculateRecords();
        $srch->doNotLimitRecords();
        $srch->addMultipleFields(array('uwlist_id'));
        $rs = $srch->getResultSet();
        $row = FatApp::getDb()->fetch($rs);
        $productIsInAnyList = false;
        if ($row) {
            $productIsInAnyList = true;
        }

        if (0 < $removeFromCart) {
            $cartObj = new Cart($this->userId, $this->siteLangId, $this->app_user['temp_user_id']);
            $key = md5(base64_encode(json_encode(Cart::CART_KEY_PREFIX_PRODUCT . $selprod_id)));
            if (!$cartObj->remove($key)) {
                LibHelper::dieJsonError($cartObj->getError());
            }

            if (true === MOBILE_APP_API_CALL) {
                $fulfilmentType = FatApp::getPostedData('fulfilmentType', FatUtility::VAR_INT, Shipping::FULFILMENT_SHIP);
                $cartObj = new Cart(UserAuthentication::getLoggedUserId(true), $this->siteLangId, $this->app_user['temp_user_id'], Cart::PAGE_TYPE_CART);
                $cartObj->setFulfilmentType($fulfilmentType);
                $cartObj->setCartCheckoutType($fulfilmentType);
                $productsArr = $cartObj->getProducts($this->siteLangId);
                $cartSummary = $cartObj->getCartFinancialSummary($this->siteLangId);
                $this->set('products', $productsArr);
                $this->set('cartSummary', $cartSummary);
            }
        }

        $this->updateFavConfTime();

        $this->set('productIsInAnyList', $productIsInAnyList);
        $this->set('action', $action);
        $this->set('wish_list_id', $wish_list_id);
        $this->set('totalWishListItems', Common::countWishList());

        if (true === MOBILE_APP_API_CALL) {
            $this->set('removeFromCart', $removeFromCart);
            $this->_template->render();
        }
        $this->_template->render(false, false, 'json-success.php');
    }

    private function updateWishList($selprod_id, $wish_list_id, $rowAction = -1)
    {
        $row = false;
        $selprod_id = FatUtility::int($selprod_id);
        $db = FatApp::getDb();
        $wListObj = new UserWishList();
        if (0 > $rowAction) {
            $srch = UserWishList::getSearchObject($this->userId);
            $wListObj->joinWishListProducts($srch);
            $srch->addMultipleFields(array('uwlist_id'));
            $srch->doNotCalculateRecords();
            $srch->doNotLimitRecords();
            $srch->addCondition('uwlp_selprod_id', '=', 'mysql_func_' . $selprod_id, 'AND', true);
            // $srch->addCondition('uwlp_uwlist_id', '=', $wish_list_id);

            $rs = $srch->getResultSet();
            $row = $db->fetchAll($rs);
            if (is_array($row)) {
                foreach ($row as $key => $wishlistRow) {
                    /* In case user wants to remove from wishlist. */
                    if ($wishlistRow['uwlist_id'] == $wish_list_id) {
                        continue;
                    }

                    /* In case user wants to add item in wishlist but remove from others as one item can be added in one wishlist only. */
                    if (!$db->deleteRecords(UserWishList::DB_TBL_LIST_PRODUCTS, array('smt' => 'uwlp_uwlist_id = ? AND uwlp_selprod_id = ?', 'vals' => array($wishlistRow['uwlist_id'], $selprod_id)))) {
                        continue;
                    }
                    unset($row[$key]);
                }
                $row = empty($row) ? false : $row;
            }
        }

        $action = 'N'; //nothing happened
        if (!$row && (0 < $rowAction || 0 > $rowAction)) {
            if (!$wListObj->addUpdateListProducts($wish_list_id, $selprod_id)) {
                $message = Labels::getLabel('LBL_Some_problem_occurred,_Please_contact_webmaster', $this->siteLangId);
                if (true === MOBILE_APP_API_CALL) {
                    FatUtility::dieJsonError($message);
                }
                Message::addErrorMessage($message);
                FatUtility::dieWithError(Message::getHtml());
            }
            $action = 'A'; //Added to wishlist
            $this->set('msg', Labels::getLabel('MSG_PRODUCT_ADDED_IN_LIST_SUCCESSFULLY', $this->siteLangId));
        } else {
            $uwlistIds = array();
            if (true === MOBILE_APP_API_CALL) {
                $srch = UserWishList::getSearchObject($this->userId);
                $srch->addMultipleFields(array('uwlist_id'));
                $rs = $srch->getResultSet();
                $row = $db->fetchAll($rs, 'uwlist_id');
                $uwlistIds = array_keys($row);
            } else {
                $uwlistIds[] = $wish_list_id;
            }
            $err = true;
            foreach ($uwlistIds as $uwlistId) {
                $err = false;
                if (!$db->deleteRecords(UserWishList::DB_TBL_LIST_PRODUCTS, array('smt' => 'uwlp_uwlist_id = ? AND uwlp_selprod_id = ?', 'vals' => array($uwlistId, $selprod_id)))) {
                    $err = true;
                    break;
                }
            }

            if (true == $err) {
                $message = Labels::getLabel('LBL_Some_problem_occurred,_Please_contact_webmaster', $this->siteLangId);
                if (true === MOBILE_APP_API_CALL) {
                    FatUtility::dieJsonError($message);
                }
                Message::addErrorMessage($message);
                FatUtility::dieWithError(Message::getHtml());
            }

            $action = 'R'; //Removed from wishlist
            $this->set('msg', Labels::getLabel('MSG_PRODUCT_REMOVED_FROM_LIST_SUCCESSFULLY', $this->siteLangId));
        }
        return $action;
    }

    public function wishlist()
    {
        $this->_template->addJs('js/slick.js');
        $this->_template->render();
    }

    public function wishListSearch()
    {
        if (FatApp::getConfig('CONF_ADD_FAVORITES_TO_WISHLIST', FatUtility::VAR_INT, 1) == applicationConstants::NO) {
            $wishLists[] = Product::getUserFavouriteProducts($this->userId, $this->siteLangId);
        } else {
            $wishLists = UserWishList::getUserWishLists($this->userId, false);
            if ($wishLists) {
                $srchObj = new UserWishListProductSearch($this->siteLangId);
                $db = FatApp::getDb();
                foreach ($wishLists as &$wishlist) {
                    $srch = clone $srchObj;
                    $srch->joinSellerProducts();
                    $srch->joinProducts();
                    $srch->joinBrands();
                    $srch->joinSellers();
                    $srch->joinShops();
                    $srch->joinProductToCategory();
                    $srch->joinSellerSubscription($this->siteLangId, true);
                    $srch->addSubscriptionValidCondition();
                    $srch->joinSellerProductSpecialPrice();
                    $srch->joinFavouriteProducts($this->userId);
                    $srch->addCondition('uwlp_uwlist_id', '=', $wishlist['uwlist_id']);
                    $srch->addCondition('selprod_deleted', '=', 'mysql_func_' . applicationConstants::NO, 'AND', true);
                    $srch->addCondition('selprod_active', '=', 'mysql_func_' . applicationConstants::YES, 'AND', true);
                    $srch->setPageNumber(1);
                    $srch->setPageSize(4);
                    $srch->addMultipleFields(array('selprod_id', 'IFNULL(selprod_title  ,IFNULL(product_name, product_identifier)) as selprod_title', 'product_id', 'IFNULL(product_name, product_identifier) as product_name', 'IF(selprod_stock > 0, 1, 0) AS in_stock', 'product_updated_on'));
                    $srch->addOrder('uwlp_added_on');
                    $srch->addGroupBy('selprod_id');
                    $products = $db->fetchAll($srch->getResultSet());
                    $wishlist['products'] = $products;
                    $wishlist['totalProducts'] = $srch->recordCount();
                }
            }
        }
        /* $wishLists = array_merge($favouriteProducts,$wishLists); */
        $this->set('wishLists', $wishLists);

        if (true === MOBILE_APP_API_CALL) {
            $this->_template->render();
        }

        $frm = $this->getCreateWishListForm();
        $this->set('frm', $frm);
        $this->_template->render(false, false);
    }

    public function searchWishListItems()
    {
        $post = FatApp::getPostedData();
        $db = FatApp::getDb();
        $page = (empty($post['page']) || $post['page'] <= 0) ? 1 : FatUtility::int($post['page']);
        $pageSize = FatApp::getConfig('conf_page_size', FatUtility::VAR_INT, 10);
        $uwlist_id = FatApp::getPostedData('uwlist_id', FatUtility::VAR_INT, 0);

        if (false === MOBILE_APP_API_CALL) {
            $wishListRow = UserWishList::getAttributesById($uwlist_id, array('uwlist_id'));
            if (!$wishListRow) {
                $message = Labels::getLabel('LBL_Invalid_Request', $this->siteLangId);
                if (true === MOBILE_APP_API_CALL) {
                    FatUtility::dieJsonError($message);
                }
                Message::addErrorMessage($message);
                FatUtility::dieWithError(Message::getHtml());
            }
        }

        $srch = new UserWishListProductSearch($this->siteLangId);
        $srch->joinSellerProducts();
        $srch->joinProducts();
        $srch->joinBrands();
        $srch->joinSellers();
        $srch->setGeoAddress();
        $srch->joinShops();
        $srch->validateAndJoinDeliveryLocation();
        $srch->joinProductToCategory();
        $srch->joinSellerSubscription($this->siteLangId, true);
        $srch->addSubscriptionValidCondition();
        $srch->joinSellerProductSpecialPrice();
        $srch->joinFavouriteProducts($this->userId);
        if (true === MOBILE_APP_API_CALL && 0 >= $uwlist_id) {
            $srch->joinWishLists();
            $srch->addCondition('uwlist_user_id', '=', 'mysql_func_' . $this->userId, 'AND', true);
        } else {
            $srch->addCondition('uwlp_uwlist_id', '=', 'mysql_func_' . $uwlist_id, 'AND', true);
        }
        $srch->addCondition('selprod_deleted', '=', 'mysql_func_' . applicationConstants::NO, 'AND', true);
        $srch->addCondition('selprod_active', '=', 'mysql_func_' . applicationConstants::YES, 'AND', true);
        $selProdReviewObj = new SelProdReviewSearch();
        $selProdReviewObj->joinSellerProducts();
        $selProdReviewObj->joinSelProdRating();
        $selProdReviewObj->addCondition('ratingtype_type', '=', 'mysql_func_' . RatingType::TYPE_PRODUCT, 'AND', true);
        $selProdReviewObj->doNotCalculateRecords();
        $selProdReviewObj->doNotLimitRecords();
        $selProdReviewObj->addGroupBy('spr.spreview_product_id');
        $selProdReviewObj->addCondition('spr.spreview_status', '=', 'mysql_func_' . SelProdReview::STATUS_APPROVED, 'AND', true);
        $selProdReviewObj->addMultipleFields(array('spr.spreview_selprod_id', "ROUND(AVG(sprating_rating),2) as prod_rating"));
        $selProdRviewSubQuery = $selProdReviewObj->getQuery();
        $srch->joinTable('(' . $selProdRviewSubQuery . ')', 'LEFT OUTER JOIN', 'sq_sprating.spreview_selprod_id = selprod_id', 'sq_sprating');

        /* $favProductObj = new UserWishListProductSearch();
        $favProductObj->joinFavouriteProducts(); */


        // echo $srch->getQuery(); die;

        $srch->setPageNumber($page);
        $srch->setPageSize($pageSize);

        /* groupby added, beacouse if same product is linked with multiple categories, then showing in repeat for each category[ */
        $srch->addGroupBy('selprod_id');
        /* ] */

        $srch->addMultipleFields(
            array(
                'selprod_id',
                'IFNULL(selprod_title  ,IFNULL(product_name, product_identifier)) as selprod_title',
                'product_id',
                'prodcat_id',
                'ufp_id',
                'IFNULL(product_name, product_identifier) as product_name',
                'IFNULL(prodcat_name, prodcat_identifier) as prodcat_name',
                'product_updated_on',
                'IF(selprod_stock > 0, 1, 0) AS in_stock',
                'brand.brand_id',
                'product_model',
                'IFNULL(brand_name, brand_identifier) as brand_name',
                'IFNULL(splprice_price, selprod_price) AS theprice',
                'splprice_display_list_price',
                'splprice_display_dis_val',
                'splprice_display_dis_type',
                'CASE WHEN splprice_selprod_id IS NULL THEN 0 ELSE 1 END AS special_price_found',
                'selprod_price',
                'selprod_user_id',
                'selprod_code',
                'selprod_sold_count',
                'selprod_condition',
                'IFNULL(uwlp.uwlp_selprod_id, 0) as is_in_any_wishlist',
                'IFNULL(uwlp.uwlp_uwlist_id, 0) as uwlp_uwlist_id',
                'ifnull(prod_rating,0) prod_rating',
                'selprod_min_order_qty',
                'selprod_available_from',
                'selprod_stock',
                'shop_id',
                'product_updated_on',
                'selprod_cart_type',
                'selprod_hide_price',
                'shop_rfq_enabled',
                'product_type'
            )
        );


        $srch->addOrder('uwlp_added_on', 'DESC');
        $rs = $srch->getResultSet();
        /* echo $srch->getQuery(); die; */
        $products = $db->fetchAll($rs);

        $selprodIdsArr = $tRightRibbons = [];
        if (count($products)) {
            foreach ($products as &$arr) {
                $arr['options'] = SellerProduct::getSellerProductOptions($arr['selprod_id'], true, $this->siteLangId);
                $selprodIdsArr[] = $arr['selprod_id'];
            }

            $tRightRibbons = Badge::getRibbons($this->siteLangId, Badge::RIBB_POS_TRIGHT, $selprodIdsArr);
        }

        $this->set('tRightRibbons', $tRightRibbons);
        $this->set('products', $products);
        $this->set('showProductShortDescription', false);
        $this->set('showProductReturnPolicy', false);
        $this->set('colMdVal', 4);
        $this->set('page', $page);
        $this->set('recordCount', $srch->recordCount());
        $this->set('pageCount', $srch->pages());
        $this->set('postedData', $post);

        $startRecord = ($page - 1) * $pageSize + 1;
        $endRecord = $page * $pageSize;
        $totalRecords = $srch->recordCount();
        if ($totalRecords < $endRecord) {
            $endRecord = $totalRecords;
        }
        $this->set('totalRecords', $totalRecords);
        $this->set('startRecord', $startRecord);
        $this->set('endRecord', $endRecord);
        $this->set('showActionBtns', true);
        $this->set('isWishList', true);
        $this->set('uwlist_id', $uwlist_id);

        if (true === MOBILE_APP_API_CALL) {
            $this->_template->render();
        }

        $this->set('html', $this->_template->render(false, false, NULL, true, false));
        $this->_template->render(false, false, 'json-success.php', true, false);
    }

    public function searchFavouriteListItems()
    {
        $post = FatApp::getPostedData();
        $db = FatApp::getDb();
        $page = (empty($post['page']) || $post['page'] <= 0) ? 1 : FatUtility::int($post['page']);
        $pageSize = FatApp::getConfig('conf_page_size', FatUtility::VAR_INT, 10);

        $wishListRow = Product::getUserFavouriteProducts($this->userId, $this->siteLangId);
        if (!$wishListRow) {
            $message = Labels::getLabel('LBL_Invalid_Request', $this->siteLangId);
            if (true === MOBILE_APP_API_CALL) {
                FatUtility::dieJsonError($message);
            }
            Message::addErrorMessage($message);
            FatUtility::dieWithError(Message::getHtml());
        }

        $srch = new UserFavoriteProductSearch($this->siteLangId);
        $srch->setDefinedCriteria($this->siteLangId);
        $srch->joinBrands();
        $srch->joinSellers();
        $srch->joinShops();
        $srch->joinProductToCategory();
        $srch->joinSellerProductSpecialPrice();
        $srch->joinSellerSubscription($this->siteLangId, true);
        $srch->addSubscriptionValidCondition();
        $srch->addCondition('selprod_deleted', '=', 'mysql_func_' . applicationConstants::NO, 'AND', true);
        $wislistPSrchObj = new UserWishListProductSearch();
        $wislistPSrchObj->joinWishLists();
        $wislistPSrchObj->doNotCalculateRecords();
        $wislistPSrchObj->addCondition('uwlist_user_id', '=', 'mysql_func_' . $this->userId, 'AND', true);
        $wishListSubQuery = $wislistPSrchObj->getQuery();
        $srch->joinTable('(' . $wishListSubQuery . ')', 'LEFT OUTER JOIN', 'uwlp.uwlp_selprod_id = selprod_id', 'uwlp');

        $selProdReviewObj = new SelProdReviewSearch();
        $selProdReviewObj->joinSellerProducts();
        $selProdReviewObj->joinSelProdRating();
        $selProdReviewObj->addCondition('ratingtype_type', '=', 'mysql_func_' . RatingType::TYPE_PRODUCT, 'AND', true);
        $selProdReviewObj->doNotCalculateRecords();
        $selProdReviewObj->doNotLimitRecords();
        $selProdReviewObj->addGroupBy('spr.spreview_product_id');
        $selProdReviewObj->addCondition('spr.spreview_status', '=', 'mysql_func_' . SelProdReview::STATUS_APPROVED, 'AND', true);
        $selProdReviewObj->addMultipleFields(array('spr.spreview_selprod_id', "ROUND(AVG(sprating_rating),2) as prod_rating"));
        $selProdRviewSubQuery = $selProdReviewObj->getQuery();
        $srch->joinTable('(' . $selProdRviewSubQuery . ')', 'LEFT OUTER JOIN', 'sq_sprating.spreview_selprod_id = selprod_id', 'sq_sprating');

        $srch->setPageNumber($page);
        $srch->setPageSize($pageSize);

        /* groupby added, beacouse if same product is linked with multiple categories, then showing in repeat for each category[ */
        $srch->addGroupBy('selprod_id');
        /* ] */

        $srch->addMultipleFields(
            array(
                'selprod_id',
                'IFNULL(selprod_title  ,IFNULL(product_name, product_identifier)) as selprod_title',
                'product_id',
                'prodcat_id',
                'ufp_id',
                'IFNULL(product_name, product_identifier) as product_name',
                'IFNULL(prodcat_name, prodcat_identifier) as prodcat_name',
                'product_updated_on',
                'IF(selprod_stock > 0, 1, 0) AS in_stock',
                'brand.brand_id',
                'product_model',
                'IFNULL(brand_name, brand_identifier) as brand_name',
                'IFNULL(splprice_price, selprod_price) AS theprice',
                'splprice_display_list_price',
                'splprice_display_dis_val',
                'splprice_display_dis_type',
                'CASE WHEN splprice_selprod_id IS NULL THEN 0 ELSE 1 END AS special_price_found',
                'selprod_price',
                'selprod_user_id',
                'selprod_code',
                'selprod_condition',
                'IFNULL(uwlp.uwlp_selprod_id, 0) as is_in_any_wishlist',
                'ifnull(prod_rating,0) prod_rating',
                'selprod_sold_count',
                'selprod_min_order_qty',
                'selprod_available_from',
                'selprod_stock',
                'selprod_cart_type',
                'selprod_hide_price',
                'shop_rfq_enabled',
                'product_type'
            )
        );

        $srch->addOrder('ufp_id', 'desc');
        $srch->addCondition('ufp_user_id', '=', 'mysql_func_' . $this->userId, 'AND', true);
        $products = $db->fetchAll($srch->getResultSet());

        $selProdIdsArr = array_column($products, 'selprod_id');
        $tRightRibbons = Badge::getRibbons($this->siteLangId, Badge::RIBB_POS_TRIGHT, $selProdIdsArr);

        $this->set('products', $products);
        $this->set('tRightRibbons', $tRightRibbons);
        $this->set('showProductShortDescription', false);
        $this->set('showProductReturnPolicy', false);
        $this->set('colMdVal', 4);
        $this->set('page', $page);
        $this->set('pagingFunc', 'goToFavouriteListingSearchPage');
        $this->set('recordCount', $srch->recordCount());
        $this->set('pageCount', $srch->pages());
        $this->set('postedData', $post);
        $this->set('showActionBtns', true);
        $startRecord = ($page - 1) * $pageSize + 1;
        $endRecord = $page * $pageSize;
        $totalRecords = $srch->recordCount();
        if ($totalRecords < $endRecord) {
            $endRecord = $totalRecords;
        }

        $this->set('totalRecords', $totalRecords);
        $this->set('startRecord', $startRecord);
        $this->set('endRecord', $endRecord);

        if (true === MOBILE_APP_API_CALL) {
            $this->_template->render();
        }

        $this->set('html', $this->_template->render(false, false, NULL, true, false));
        $this->_template->render(false, false, 'json-success.php', true, false);
    }

    public function deleteWishList()
    {
        $uwlist_id = FatApp::getPostedData('uwlist_id', FatUtility::VAR_INT, 0);
        if (0 >= $uwlist_id) {
            $message = Labels::getLabel('LBL_Invalid_Request', $this->siteLangId);
            if (true === MOBILE_APP_API_CALL) {
                FatUtility::dieJsonError($message);
            }
            Message::addErrorMessage($message);
            FatUtility::dieWithError(Message::getHtml());
        }

        $srch = UserWishList::getSearchObject($this->userId);
        $srch->doNotCalculateRecords();
        $srch->doNotLimitRecords();
        $srch->addCondition('uwlist_id', '=', 'mysql_func_' . $uwlist_id, 'AND', true);
        $srch->addCondition('uwlist_type', '!=', 'mysql_func_' . UserWishList::TYPE_DEFAULT_WISHLIST, 'AND', true);
        $rs = $srch->getResultSet();
        $row = FatApp::getDb()->fetch($rs);
        if (!$row) {
            $message = Labels::getLabel('MSG_No_record_found', $this->siteLangId);
            if (true === MOBILE_APP_API_CALL) {
                FatUtility::dieJsonError($message);
            }
            Message::addErrorMessage($message);
            FatUtility::dieWithError(Message::getHtml());
        }

        $obj = new UserWishList();
        $obj->deleteWishList($row['uwlist_id']);
        $this->set('msg', Labels::getLabel('MSG_RECORD_DELETED_SUCCESSFULLY', $this->siteLangId));
        if (true === MOBILE_APP_API_CALL) {
            $this->_template->render();
        }

        $this->_template->render(false, false, 'json-success.php');
    }

    public function updateSearchdate()
    {
        $post = FatApp::getPostedData();
        $pssearch_id = FatUtility::int($post['pssearch_id']);

        $srch = new SearchBase(SavedSearchProduct::DB_TBL);
        $srch->addCondition('pssearch_id', '=', 'mysql_func_' . $pssearch_id, 'AND', true);
        $rs = $srch->getResultSet();
        $row = FatApp::getDb()->fetch($rs);
        if (!$row) {
            Message::addErrorMessage(Labels::getLabel('LBL_INVALID_REQUEST', $this->siteLangId));
            FatUtility::dieWithError(Message::getHtml());
        }

        $updateArray = array('pssearch_updated_on' => date('Y-m-d H:i:s'));
        $whr = array('smt' => 'pssearch_id = ?', 'vals' => array($pssearch_id));

        if (!FatApp::getDb()->updateFromArray(SavedSearchProduct::DB_TBL, $updateArray, $whr)) {
            Message::addErrorMessage(Labels::getLabel('ERR_INVALID_ACCESS', $this->siteLangId));
            FatUtility::dieWithError(Message::getHtml());
        }

        $this->set('msg', Labels::getLabel('MSG_RECORD_DELETED_SUCCESSFULLY', $this->siteLangId));
        $this->_template->render(false, false, 'json-success.php');
    }

    public function toggleShopFavorite()
    {
        $shop_id = FatApp::getPostedData('shop_id', FatUtility::VAR_INT, 0);
        $db = FatApp::getDb();

        $srch = new ShopSearch($this->siteLangId);
        $srch->setDefinedCriteria($this->siteLangId);
        $srch->joinSellerSubscription();
        $srch->doNotCalculateRecords();
        $srch->addMultipleFields(
            array(
                'shop_id',
                'shop_user_id',
                'shop_ltemplate_id',
                'shop_created_on',
                'shop_name',
                'shop_description',
                'shop_country_l.country_name as shop_country_name',
                'shop_state_l.state_name as shop_state_name',
                'shop_city'
            )
        );
        $srch->addCondition('shop_id', '=', 'mysql_func_' . $shop_id, 'AND', true);
        //echo $srch->getQuery();
        $shopRs = $srch->getResultSet();
        $shop = $db->fetch($shopRs);

        if (!$shop) {
            $message = Labels::getLabel('LBL_Invalid_Request', $this->siteLangId);
            if (true === MOBILE_APP_API_CALL) {
                FatUtility::dieJsonError($message);
            }
            Message::addErrorMessage($message);
            FatUtility::dieWithError(Message::getHtml());
        }

        $action = 'N'; //nothing happened
        $srch = new UserFavoriteShopSearch();
        $srch->doNotCalculateRecords();
        $srch->doNotLimitRecords();
        $srch->addCondition('ufs_user_id', '=', 'mysql_func_' . $this->userId, 'AND', true);
        $srch->addCondition('ufs_shop_id', '=', 'mysql_func_' . $shop_id, 'AND', true);
        $rs = $srch->getResultSet();
        if (!$row = $db->fetch($rs)) {
            $shopObj = new Shop($shop_id);
            if (!$shopObj->setFavorite($this->userId)) {
                $message = Labels::getLabel('LBL_Some_problem_occurred,_Please_contact_webmaster', $this->siteLangId);
                if (true === MOBILE_APP_API_CALL) {
                    FatUtility::dieJsonError($message);
                }
                Message::addErrorMessage($message);
                FatUtility::dieWithError(Message::getHtml());
            }
            $action = 'A'; //Added to favorite
            $this->set('msg', Labels::getLabel('MSG_SHOP_IS_MARKED_AS_FAVOUTITE', $this->siteLangId));
        } else {
            if (!$db->deleteRecords(Shop::DB_TBL_SHOP_FAVORITE, array('smt' => 'ufs_user_id = ? AND ufs_shop_id = ?', 'vals' => array($this->userId, $shop_id)))) {
                $message = Labels::getLabel('LBL_Some_problem_occurred,_Please_contact_webmaster', $this->siteLangId);
                if (true === MOBILE_APP_API_CALL) {
                    FatUtility::dieJsonError($message);
                }
                Message::addErrorMessage($message);
                FatUtility::dieWithError(Message::getHtml());
            }
            $action = 'R'; //Removed from favorite
            $this->set('msg', Labels::getLabel('MSG_SHOP_HAS_BEEN_REMOVED_FROM_YOUR_FAVOURITE_LIST', $this->siteLangId));
        }

        $this->set('action', $action);

        if (true === MOBILE_APP_API_CALL) {
            $this->_template->render();
        }
        $this->_template->render(false, false, 'json-success.php');
    }

    public function favoriteShopSearch()
    {
        $post = FatApp::getPostedData();
        $page = FatApp::getPostedData('page', FatUtility::VAR_INT, 1);
        if ($page < 2) {
            $page = 1;
        }
        $pageSize = FatApp::getConfig('conf_page_size', FatUtility::VAR_INT, 10);
        $db = FatApp::getDb();
        $srch = new UserFavoriteShopSearch($this->siteLangId);
        $srch->setDefinedCriteria();
        $srch->joinSellerOrder();
        $srch->joinSellerOrderSubscription($this->siteLangId);
        $srch->addCondition('ufs_user_id', '=', 'mysql_func_' . $this->userId, 'AND', true);
        $srch->addMultipleFields(
            array(
                's.shop_id',
                'shop_user_id',
                'shop_ltemplate_id',
                'shop_created_on',
                'shop_name',
                'shop_description',
                'shop_country_l.country_name as country_name',
                'shop_state_l.state_name as state_name',
                'shop_city',
                'IFNULL(ufs.ufs_id, 0) as is_favorite',
                'shop_updated_on'
            )
        );
        $srch->setPageNumber($page);
        $srch->setPageSize($pageSize);
        $rs = $srch->getResultSet();
        $shops = $db->fetchAll($rs);

        $totalProductsToShow = 4;
        if ($shops) {
            foreach ($shops as &$shop) {
                $uploadedTime = AttachedFile::setTimeParam($shop['shop_updated_on']);
                $shop['shopRating'] = SelProdRating::getSellerRating($shop['shop_user_id'], true);
                $shop['shop_logo'] = UrlHelper::generateFullUrl('image', 'shopLogo', [$shop['shop_id'], $this->siteLangId, ImageDimension::VIEW_SMALL], CONF_WEBROOT_FRONTEND) . $uploadedTime;
            }
        }
        $this->set('page', $page);
        $this->set('pageCount', $srch->pages());
        $this->set('recordCount', $srch->recordCount());
        $this->set('postedData', $post);
        $this->set('shops', $shops);

        if (true === MOBILE_APP_API_CALL) {
            $this->_template->render();
        }
        $this->_template->render(false, false);
    }

    private function isValidSelProd($selprodId)
    {
        $db = FatApp::getDb();
        $selprodId = FatUtility::int($selprodId);
        $srch = new ProductSearch($this->siteLangId);
        $srch->setDefinedCriteria(0, 0, array(), false);
        $srch->doNotCalculateRecords();
        $srch->addMultipleFields(['selprod_id']);
        $srch->addCondition('selprod_id', '=', 'mysql_func_' . $selprodId, 'AND', true);
        $srch->joinProductToCategory();
        // $srch->joinShops();
        $srch->joinSellerSubscription();
        $srch->addSubscriptionValidCondition();
        $srch->addCondition('selprod_deleted', '=', 'mysql_func_' . applicationConstants::NO, 'AND', true);

        $productRs = $srch->getResultSet();
        $product = $db->fetch($productRs);

        if (!$product) {
            $message = Labels::getLabel('LBL_Invalid_Request', $this->siteLangId);
            if (true === MOBILE_APP_API_CALL) {
                FatUtility::dieJsonError($message);
            }
            Message::addErrorMessage($message);
            FatUtility::dieWithError(Message::getHtml());
        }
        return true;
    }

    public function toggleProductStatus(int $selprodId, int $status)
    {
        $this->isValidSelProd($selprodId);

        switch ($status) {
            case applicationConstants::ACTIVE:
                $this->markAsFavorite($selprodId, false);
                $this->set('msg', Labels::getLabel('MSG_Product_has_been_marked_as_favourite_successfully', $this->siteLangId));
                break;
            case applicationConstants::INACTIVE:
                $this->removeFromFavorite($selprodId, false);
                $this->set('msg', Labels::getLabel('MSG_Product_has_been_removed_from_favourite_list', $this->siteLangId));
                break;
            default:
                FatUtility::dieJsonError(Labels::getLabel('ERR_UNKNOWN_ACTION', $this->siteLangId));
                break;
        }

        $this->_template->render();
    }

    public function markAsFavorite($selprodId, $renderView = true)
    {
        $this->isValidSelProd($selprodId);
        $prodObj = new Product();
        if (!$prodObj->addUpdateUserFavoriteProduct($this->userId, $selprodId)) {
            $message = Labels::getLabel('LBL_Some_problem_occurred,_Please_contact_webmaster', $this->siteLangId);
            if (true === MOBILE_APP_API_CALL) {
                FatUtility::dieJsonError($message);
            }
            Message::addErrorMessage($message);
            FatUtility::dieWithError(Message::getHtml());
        }

        $this->updateFavConfTime();

        if (false === $renderView) {
            return true;
        }

        $this->set('msg', Labels::getLabel('MSG_Product_has_been_marked_as_favourite_successfully', $this->siteLangId));

        if (true === MOBILE_APP_API_CALL) {
            $this->_template->render();
        }

        $this->_template->render(false, false, 'json-success.php');
    }

    public function removeFromFavorite($selprodId, $renderView = true)
    {
        $this->isValidSelProd($selprodId);
        $db = FatApp::getDb();
        if (!$db->deleteRecords(Product::DB_TBL_PRODUCT_FAVORITE, array('smt' => 'ufp_user_id = ? AND ufp_selprod_id = ?', 'vals' => array($this->userId, $selprodId)))) {
            $message = Labels::getLabel('LBL_Some_problem_occurred,_Please_contact_webmaster', $this->siteLangId);
            if (true === MOBILE_APP_API_CALL) {
                FatUtility::dieJsonError($message);
            }
            Message::addErrorMessage($message);
            FatUtility::dieWithError(Message::getHtml());
        }

        $this->updateFavConfTime();

        if (false === $renderView) {
            return true;
        }

        $this->set('msg', Labels::getLabel('MSG_PRODUCT_HAS_BEEN_REMOVED_FROM_FAVOURITE_LIST', $this->siteLangId));

        if (true === MOBILE_APP_API_CALL) {
            $this->_template->render();
        }

        $this->_template->render(false, false, 'json-success.php');
    }

    public function removeFromFavoriteArr()
    {
        $selprodIdsArr = (array) FatApp::getPostedData('selprod_id', FatUtility::VAR_INT);
        if (empty($selprodIdsArr)) {
            $message = Labels::getLabel('LBL_Invalid_Request', $this->siteLangId);
            if (true === MOBILE_APP_API_CALL) {
                FatUtility::dieJsonError($message);
            }
            Message::addErrorMessage($message);
            FatUtility::dieWithError(Message::getHtml());
        }

        foreach ($selprodIdsArr as $selprodId) {
            $this->removeFromFavorite($selprodId, false);
        }

        $this->set('msg', Labels::getLabel('MSG_PRODUCT_HAS_BEEN_REMOVED_FROM_FAVOURITE_LIST', $this->siteLangId));

        if (true === MOBILE_APP_API_CALL) {
            $this->_template->render();
        }

        $this->_template->render(false, false, 'json-success.php');
    }

    private function setMessages()
    {
        $userImgUpdatedOn = User::getAttributesById($this->userId, 'user_updated_on');
        $uploadedTime = AttachedFile::setTimeParam($userImgUpdatedOn);

        $frm = $this->getMessageSearchForm($this->siteLangId);

        $post = $frm->getFormDataFromArray(FatApp::getPostedData());

        $page = FatApp::getPostedData('page', FatUtility::VAR_INT, 1);
        $page = ($page <= 0) ? 1 : $page;
        $pagesize = FatApp::getConfig('conf_page_size', FatUtility::VAR_INT, 10);
        $page = (empty($page) || $page <= 0) ? 1 : $page;
        $page = FatUtility::int($page);

        $srch = new MessageSearch();
        $srch->joinThreadLastMessage();
        $srch->joinMessagePostedFromUser(true, $this->siteLangId);
        $srch->joinMessagePostedToUser(true, $this->siteLangId);
        $srch->joinThreadStartedByUser();
        $srch->addCondition('ttm.message_deleted', '=', 0);

        $parentAndTheirChildIds = [];
        switch ($_SESSION[UserAuthentication::SESSION_ELEMENT_NAME]['activeTab']) {
            case 'B':
                $srch->addCondition('tth.thread_started_by', '=', UserAuthentication::getLoggedUserId());
                break;
            case 'S':
                $srch->addCondition('tth.thread_started_by', '!=', UserAuthentication::getLoggedUserId());
                $parentAndTheirChildIds = User::getParentAndTheirChildIds($this->userParentId, false, true);
                $cnd = $srch->addCondition('ttm.message_from', 'IN', $parentAndTheirChildIds);
                $cnd->attachCondition('ttm.message_to', 'IN', $parentAndTheirChildIds, 'OR');
                break;
            default:
                FatApp::redirectUser(UrlHelper::generateUrl('', '', [], CONF_WEBROOT_DASHBOARD));
                break;
        }

        $srch->addGroupBy('ttm.message_thread_id');
        if ($post['keyword'] != '') {
            $cnd = $srch->addCondition('tth.thread_subject', 'like', "%" . $post['keyword'] . "%");
            $cnd->attachCondition('ttm.message_text', 'like', '%' . $post['keyword'] . '%');
            if ($_SESSION[UserAuthentication::SESSION_ELEMENT_NAME]['activeTab'] == "S") {
                $cnd->attachCondition('tfr.user_name', 'like', "%" . $post['keyword'] . "%", 'OR');
                $cnd->attachCondition('tfr_c.credential_username', 'like', "%" . $post['keyword'] . "%", 'OR');
            } else {
                $cnd->attachCondition('tfto.user_name', 'like', "%" . $post['keyword'] . "%", 'OR');
                $cnd->attachCondition('tfto_c.credential_username', 'like', "%" . $post['keyword'] . "%", 'OR');
            }
        }

        $date_from = FatApp::getPostedData('date_from', FatUtility::VAR_DATE, '');
        if (!empty($date_from)) {
            $srch->addCondition('ttm.message_date', '>=', $date_from . ' 00:00:00');
        }

        $date_to = FatApp::getPostedData('date_to', FatUtility::VAR_DATE, '');
        if (!empty($date_to)) {
            $srch->addCondition('ttm.message_date', '<=', $date_to . ' 23:59:59');
        }

        $this->setRecordCount(clone $srch, $pagesize, $page, $post, true);
        $srch->doNotCalculateRecords();
        $srch->addMultipleFields(array(
            'tth.*',
            'ttm.*',
            'tfr.user_id as message_sent_by',
            'tfr.user_updated_on as message_from_user_updated_on',
            'tfr.user_phone as message_from_user_phone',
            'tfr.user_phone_dcode as message_from_user_phone_dcode',
            'tfr.user_name as message_sent_by_username',
            'tfto.user_id as message_sent_to',
            'tfto.user_updated_on as message_to_user_updated_on',
            'tfto.user_name as message_sent_to_name',
            'tfto_c.credential_email as message_sent_to_email',
            'tfrs.shop_id as message_from_shop_id',
            'tfrs.shop_user_id as message_from_shop_user_id',
            'tfto.user_name as message_sent_to_name',
            'IFNULL(tfrs_l.shop_name, tfrs.shop_identifier) as message_from_shop_name'
        ));
        $srch->setPageNumber($page);
        $srch->setPageSize($pagesize);
        $srch->addOrder('message_id', 'DESC');
        $rs = $srch->getResultSet();
        $records = FatApp::getDb()->fetchAll($rs);
        if (true === MOBILE_APP_API_CALL) {
            $message_records = array();
            foreach ($records as $mkey => $mval) {
                $profile_images_arr = array(
                    "message_from_profile_url" => UrlHelper::getCachedUrl(UrlHelper::generateFullFileUrl('image', 'user', array($mval['message_from_user_id'], ImageDimension::VIEW_THUMB, 1), CONF_WEBROOT_FRONTEND) . $uploadedTime, CONF_IMG_CACHE_TIME, '.jpg'),
                    "message_to_profile_url" => UrlHelper::getCachedUrl(UrlHelper::generateFullFileUrl('image', 'user', array($mval['message_to_user_id'], ImageDimension::VIEW_THUMB, 1), CONF_WEBROOT_FRONTEND) . $uploadedTime, CONF_IMG_CACHE_TIME, '.jpg'),
                    "message_timestamp" => strtotime($mval['message_date'])
                );
                $message_records[] = array_merge($mval, $profile_images_arr);
            }
            $records = $message_records;
        }

        $this->set("arrListing", $records);
        $this->set('parentAndTheirChildIds', $parentAndTheirChildIds);
        $this->set('postedData', $post);
        $this->set('activeTab', $_SESSION[UserAuthentication::SESSION_ELEMENT_NAME]['activeTab']);
    }

    public function messages()
    {
        $this->userPrivilege->canViewMessages($this->userId);
        $frm = $this->getMessageSearchForm($this->siteLangId);
        $this->set('frmSearch', $frm);

        $this->setMessages();
        $this->_template->render();
    }

    public function messageSearch()
    {
        $this->setMessages();
        $this->set('loggedUserId', $this->userId);
        if (true === MOBILE_APP_API_CALL) {
            $this->_template->render();
        }
        $this->_template->render(false, false);
    }

    /**
     * Used for load more functionality
     */
    public function getRows()
    {
        $this->setMessages();
        $jsonData = [
            'html' => $this->_template->render(false, false, 'account/message-search.php', true)
        ];
        LibHelper::exitWithSuccess($jsonData, true);
    }

    public function viewThread(int $threadId)
    {
        if (empty($threadId)) {
            LibHelper::exitWithError($this->str_invalid_request, true);
        }
        $srch = new MessageSearch();
        $srch->joinThreadMessage();
        $srch->joinMessagePostedFromUser(true, $this->siteLangId);
        $srch->joinMessagePostedToUser();
        $srch->joinShops($this->siteLangId);
        $srch->joinOrderProducts($this->siteLangId);
        $srch->addMultipleFields(array(
            'tth.*',
            'ttm.*',
            'tfr.user_id as message_sent_by',
            'tfr.user_updated_on as message_from_user_updated_on',
            'tfr.user_phone as message_from_user_phone',
            'tfr.user_phone_dcode as message_from_user_phone_dcode',
            'tfr.user_name as message_sent_by_username',
            'tfto.user_id as message_sent_to',
            'tfto.user_updated_on as message_to_user_updated_on',
            'tfto.user_name as message_sent_to_name',
            'tfto_c.credential_email as message_sent_to_email',
            'tfrs.shop_id as message_from_shop_id',
            'tfrs.shop_user_id as message_from_shop_user_id',
            'tfto.user_name as message_sent_to_name',
            'IFNULL(tfrs_l.shop_name, tfrs.shop_identifier) as message_from_shop_name'
        ));
        $srch->addCondition('message_deleted', '=', applicationConstants::NO);
        $srch->addCondition('tth.thread_id', '=', $threadId);
        $records = FatApp::getDb()->fetchAll($srch->getResultSet());
        $this->set("threadListing", $records);

        /*Mark messages as read [*/
        $threadObj = new Thread();
        $threadObj->markMessageReadFromUserArr($threadId, [$this->userId]);
        $todayUnreadMessageCount = $threadObj->getMessageCount($this->userId, Thread::MESSAGE_IS_UNREAD, date('Y-m-d'));
        /*]*/

        $frm = $this->sendMessageForm($this->siteLangId);
        $frm->fill(array('message_thread_id' => $threadId));
        $this->set('frm', $frm);
        $this->set('activeTab', $_SESSION[UserAuthentication::SESSION_ELEMENT_NAME]['activeTab']);
        $this->set('html', $this->_template->render(false, false, NULL, true));
        $this->set('html', $this->_template->render(false, false, NULL, true));
        $this->set('todayUnreadMessageCount', CommonHelper::displayBadgeCount($todayUnreadMessageCount, 9));
        $this->_template->render(false, false, 'json-success.php', true, false);
    }

    public function threadMessageSearch()
    {
        $this->userPrivilege->canViewMessages($this->userId);
        $post = FatApp::getPostedData();
        $threadId = empty($post['thread_id']) ? 0 : FatUtility::int($post['thread_id']);

        if (1 > $threadId) {
            $message = Labels::getLabel('MSG_INVALID_ACCESS', $this->siteLangId);
            FatUtility::dieJsonError($message);
        }

        if (true === MOBILE_APP_API_CALL) {
            $threadObj = new Thread($threadId);
            if (!$threadObj->markUserMessageRead($threadId, $this->userId)) {
                $msg = $threadObj->getError();
                $msg = is_array($msg) ? current($msg) : $msg;
                LibHelper::dieJsonError(strip_tags($msg));
            }
        }

        $allowedUserIds = User::getParentAndTheirChildIds($this->userParentId, false, true);
        $page = (empty($post['page']) || $post['page'] <= 0) ? 1 : FatUtility::int($post['page']);
        $pagesize = FatApp::getConfig('CONF_PAGE_SIZE', FatUtility::VAR_INT, 10);

        $srch = new MessageSearch();
        $srch->joinThreadMessage();
        $srch->joinMessagePostedFromUser(true, $this->siteLangId);
        $srch->joinMessagePostedToUser(true, $this->siteLangId);
        $srch->joinThreadStartedByUser();
        $srch->addMultipleFields(array(
            'tth.*',
            'ttm.message_id',
            'ttm.message_text',
            'ttm.message_date',
            'ttm.message_is_unread',
            'IFNULL(tfrs_l.shop_name, tfrs.shop_identifier) as message_from_shop_name',
            'tfrs.shop_id as message_from_shop_id',
            'tftos.shop_id as message_to_shop_id',
            'IFNULL(tftos_l.shop_name, tftos.shop_identifier) as message_to_shop_name'
        ));
        $srch->addCondition('ttm.message_deleted', '=', 'mysql_func_0', 'AND', true);
        $srch->addCondition('tth.thread_id', '=', 'mysql_func_' . $threadId, 'AND', true);
        $cnd = $srch->addCondition('ttm.message_from', 'in', $allowedUserIds);
        $cnd->attachCondition('ttm.message_to', 'in', $allowedUserIds, 'OR');
        $srch->addOrder('message_id', 'DESC');
        $srch->setPageNumber($page);
        $srch->setPageSize($pagesize);
        $rs = $srch->getResultSet();
        $records = FatApp::getDb()->fetchAll($rs, 'message_id');

        ksort($records);

        $this->set("arrListing", $records);
        $this->set('pageCount', $srch->pages());
        $this->set('recordCount', $srch->recordCount());
        $this->set('page', $page);
        $this->set('pageSize', $pagesize);
        $this->set('postedData', $post);

        $startRecord = ($page - 1) * $pagesize + 1;
        $endRecord = $pagesize;
        $totalRecords = $srch->recordCount();
        if ($totalRecords < $endRecord) {
            $endRecord = $totalRecords;
        }

        $this->set('totalRecords', $totalRecords);
        $this->set('startRecord', $startRecord);
        $this->set('endRecord', $endRecord);
        $this->set('records', $records);

        if (true === MOBILE_APP_API_CALL) {
            $this->_template->render();
        }

        $this->set('loadMoreBtnHtml', $this->_template->render(false, false, '_partial/load-previous-btn.php', true));
        $this->set('html', $this->_template->render(false, false, 'account/thread-message-search.php', true, false));
        $this->_template->render(false, false, 'json-success.php', true, false);
    }

    public function sendMessage()
    {
        $frm = $this->sendMessageForm($this->siteLangId);
        $post = $frm->getFormDataFromArray(FatApp::getPostedData());
        if (false === $post) {
            if (true === MOBILE_APP_API_CALL) {
                LibHelper::dieJsonError(current($frm->getValidationErrors()));
            }
            Message::addErrorMessage(current($frm->getValidationErrors()));
            FatUtility::dieWithError(Message::getHtml());
        }

        $threadId = FatUtility::int($post['message_thread_id']);

        if (1 > $threadId) {
            $message = Labels::getLabel('MSG_INVALID_ACCESS', $this->siteLangId);
            if (true === MOBILE_APP_API_CALL) {
                FatUtility::dieJsonError($message);
            }
            Message::addErrorMessage($message);
            FatUtility::dieWithError(Message::getHtml());
        }

        $allowedUserIds = User::getParentAndTheirChildIds($this->userParentId, false, true);

        $srch = new MessageSearch();
        $srch->joinThreadMessage();
        $srch->joinMessagePostedFromUser();
        $srch->joinMessagePostedToUser();
        $srch->joinThreadStartedByUser();
        $srch->addMultipleFields(array('tth.*'));
        $srch->addCondition('ttm.message_deleted', '=', 'mysql_func_0', 'AND', true);
        $srch->addCondition('tth.thread_id', '=', 'mysql_func_' . $threadId, 'AND', true);
        $cnd = $srch->addCondition('ttm.message_from', 'in', $allowedUserIds);
        $cnd->attachCondition('ttm.message_to', 'in', $allowedUserIds, 'OR');
        $rs = $srch->getResultSet();

        $threadDetails = FatApp::getDb()->fetch($rs);
        if (empty($threadDetails)) {
            $message = Labels::getLabel('MSG_INVALID_ACCESS', $this->siteLangId);
            if (true === MOBILE_APP_API_CALL) {
                FatUtility::dieJsonError($message);
            }
            Message::addErrorMessage($message);
            FatUtility::dieWithError(Message::getHtml());
        }

        $messageSendTo = ($threadDetails['message_from_user_id'] == $this->userId || $threadDetails['message_from_user_id'] == $this->userParentId) ? $threadDetails['message_to_user_id'] : $threadDetails['message_from_user_id'];

        $data = array(
            'message_thread_id' => $threadId,
            'message_from' => $this->userId,
            'message_to' => $messageSendTo,
            'message_text' => $post['message_text'],
            'message_date' => date('Y-m-d H:i:s'),
            'message_is_unread' => 1
        );

        $tObj = new Thread();

        if (!$insertId = $tObj->addThreadMessages($data)) {
            $message = Labels::getLabel($tObj->getError(), $this->siteLangId);
            if (true === MOBILE_APP_API_CALL) {
                FatUtility::dieJsonError($message);
            }
            Message::addErrorMessage($message);
            FatUtility::dieWithError(Message::getHtml());
        }

        if ($insertId) {
            $emailObj = new EmailHandler();
            $emailObj->SendMessageNotification($insertId, $this->siteLangId);
        }

        $this->set('threadId', $threadId);
        $this->set('messageId', $insertId);
        $this->set('msg', Labels::getLabel('MSG_Message_Submitted_Successfully!', $this->siteLangId));
        if (true === MOBILE_APP_API_CALL) {
            $this->set('messageDetail', $data);
            $this->_template->render();
        }
        $this->_template->render(false, false, 'json-success.php');
    }

    private function getMessageSearchForm($langId)
    {
        $frm = new Form('frmRecordSearch');
        $frm->addHiddenField('', 'page', 1);
        $frm->addHiddenField('', 'total_record_count', 1);
        $fld = $frm->addTextBox(Labels::getLabel('FRM_KEYWORD', $this->siteLangId), 'keyword');
        $fld->overrideFldType('search');

        $frm->addDateField(Labels::getLabel('FRM_DATE_FROM', $this->siteLangId), 'date_from', '', array('placeholder' => Labels::getLabel('FRM_DATE_FROM', $this->siteLangId), 'readonly' => 'readonly', 'class' => 'small dateTimeFld field--calender'));
        $frm->addDateField(Labels::getLabel('FRM_DATE_TO', $this->siteLangId), 'date_to', '', array('placeholder' => Labels::getLabel('FRM_DATE_TO', $this->siteLangId), 'readonly' => 'readonly', 'class' => 'small dateTimeFld field--calender'));

        HtmlHelper::addSearchButton($frm);
        HtmlHelper::addClearButton($frm, 'btn btn-clear');
        return $frm;
    }

    private function getWithdrawalForm($langId)
    {
        $frm = new Form('frmWithdrawal');

        $payoutPlugins = Plugin::getNamesWithCode(Plugin::TYPE_PAYOUTS, $this->siteLangId);
        if (0 < count($payoutPlugins)) {
            $payouts = [-1 => Labels::getLabel("LBL_BANK_PAYOUT", $this->siteLangId)] + $payoutPlugins;
            $frm->addSelectBox(Labels::getLabel('FRM_SELECT_PAYOUT', $this->siteLangId), 'payout', $payouts, -1, array(), '');
        }

        $fld = $frm->addRequiredField(Labels::getLabel('FRM_AMOUNT_TO_BE_WITHDRAWN', $langId) . ' [' . commonHelper::getDefaultCurrencySymbol() . ']', 'withdrawal_amount');
        $fld->requirement->setFloat(true);
        $walletBalance = User::getUserBalance($this->userId);
        $fld->htmlAfterField = '<span class="form-text">' . Labels::getLabel("FRM_CURRENT_WALLET_BALANCE", $langId) . ' ' . CommonHelper::displayMoneyFormat($walletBalance, true, true) . '</span>';

        if (User::isAffiliate()) {
            $PayMethodFld = $frm->addRadioButtons(Labels::getLabel('FRM_PAYMENT_METHOD', $langId), 'uextra_payment_method', User::getAffiliatePaymentMethodArr($langId));

            /* [ */
            $frm->addTextBox(Labels::getLabel('FRM_CHEQUE_PAYEE_NAME', $langId), 'uextra_cheque_payee_name');
            $chequePayeeNameUnReqFld = new FormFieldRequirement('uextra_cheque_payee_name', Labels::getLabel('FRM_CHEQUE_PAYEE_NAME', $langId));
            $chequePayeeNameUnReqFld->setRequired(false);

            $chequePayeeNameReqFld = new FormFieldRequirement('uextra_cheque_payee_name', Labels::getLabel('FRM_CHEQUE_PAYEE_NAME', $langId));
            $chequePayeeNameReqFld->setRequired(true);

            $PayMethodFld->requirements()->addOnChangerequirementUpdate(User::AFFILIATE_PAYMENT_METHOD_CHEQUE, 'eq', 'uextra_cheque_payee_name', $chequePayeeNameReqFld);
            $PayMethodFld->requirements()->addOnChangerequirementUpdate(User::AFFILIATE_PAYMENT_METHOD_BANK, 'eq', 'uextra_cheque_payee_name', $chequePayeeNameUnReqFld);
            $PayMethodFld->requirements()->addOnChangerequirementUpdate(User::AFFILIATE_PAYMENT_METHOD_PAYPAL, 'eq', 'uextra_cheque_payee_name', $chequePayeeNameUnReqFld);
            /* ] */

            /* [ */
            $frm->addTextBox(Labels::getLabel('FRM_BANK_NAME', $langId), 'ub_bank_name');
            $bankNameUnReqFld = new FormFieldRequirement('ub_bank_name', Labels::getLabel('FRM_BANK_NAME', $langId));
            $bankNameUnReqFld->setRequired(false);

            $bankNameReqFld = new FormFieldRequirement('ub_bank_name', Labels::getLabel('FRM_BANK_NAME', $langId));
            $bankNameReqFld->setRequired(true);

            $PayMethodFld->requirements()->addOnChangerequirementUpdate(User::AFFILIATE_PAYMENT_METHOD_CHEQUE, 'eq', 'ub_bank_name', $bankNameUnReqFld);
            $PayMethodFld->requirements()->addOnChangerequirementUpdate(User::AFFILIATE_PAYMENT_METHOD_BANK, 'eq', 'ub_bank_name', $bankNameReqFld);
            $PayMethodFld->requirements()->addOnChangerequirementUpdate(User::AFFILIATE_PAYMENT_METHOD_PAYPAL, 'eq', 'ub_bank_name', $bankNameUnReqFld);
            /* ] */

            /* [ */
            $frm->addTextBox(Labels::getLabel('FRM_ACCOUNT_HOLDER_NAME', $langId), 'ub_account_holder_name');
            $bankAccHolderNameUnReqFld = new FormFieldRequirement('ub_account_holder_name', Labels::getLabel('FRM_ACCOUNT_HOLDER_NAME', $langId));
            $bankAccHolderNameUnReqFld->setRequired(false);

            $bankAccHolderNameReqFld = new FormFieldRequirement('ub_account_holder_name', Labels::getLabel('FRM_ACCOUNT_HOLDER_NAME', $langId));
            $bankAccHolderNameReqFld->setRequired(true);

            $PayMethodFld->requirements()->addOnChangerequirementUpdate(User::AFFILIATE_PAYMENT_METHOD_CHEQUE, 'eq', 'ub_account_holder_name', $bankAccHolderNameUnReqFld);
            $PayMethodFld->requirements()->addOnChangerequirementUpdate(User::AFFILIATE_PAYMENT_METHOD_BANK, 'eq', 'ub_account_holder_name', $bankAccHolderNameReqFld);
            $PayMethodFld->requirements()->addOnChangerequirementUpdate(User::AFFILIATE_PAYMENT_METHOD_PAYPAL, 'eq', 'ub_account_holder_name', $bankAccHolderNameUnReqFld);
            /* ] */

            /* [ */
            $frm->addTextBox(Labels::getLabel('FRM_BANK_ACCOUNT_NUMBER', $langId), 'ub_account_number');
            $bankAccNumberUnReqFld = new FormFieldRequirement('ub_account_number', Labels::getLabel('FRM_BANK_ACCOUNT_NUMBER', $langId));
            $bankAccNumberUnReqFld->setRequired(false);

            $bankAccNumberReqFld = new FormFieldRequirement('ub_account_number', Labels::getLabel('FRM_BANK_ACCOUNT_NUMBER', $langId));
            $bankAccNumberReqFld->setRequired(true);

            $PayMethodFld->requirements()->addOnChangerequirementUpdate(User::AFFILIATE_PAYMENT_METHOD_CHEQUE, 'eq', 'ub_account_number', $bankAccNumberUnReqFld);
            $PayMethodFld->requirements()->addOnChangerequirementUpdate(User::AFFILIATE_PAYMENT_METHOD_BANK, 'eq', 'ub_account_number', $bankAccNumberReqFld);
            $PayMethodFld->requirements()->addOnChangerequirementUpdate(User::AFFILIATE_PAYMENT_METHOD_PAYPAL, 'eq', 'ub_account_number', $bankAccNumberUnReqFld);
            /* ] */

            /* [ */
            $frm->addTextBox(Labels::getLabel('FRM_SWIFT_CODE', $langId), 'ub_ifsc_swift_code');
            $bankIfscUnReqFld = new FormFieldRequirement('ub_ifsc_swift_code', Labels::getLabel('FRM_SWIFT_CODE', $langId));
            $bankIfscUnReqFld->setRequired(false);

            $bankIfscReqFld = new FormFieldRequirement('ub_ifsc_swift_code', Labels::getLabel('FRM_SWIFT_CODE', $langId));
            $bankIfscReqFld->setRequired(true);

            $PayMethodFld->requirements()->addOnChangerequirementUpdate(User::AFFILIATE_PAYMENT_METHOD_CHEQUE, 'eq', 'ub_ifsc_swift_code', $bankIfscUnReqFld);
            $PayMethodFld->requirements()->addOnChangerequirementUpdate(User::AFFILIATE_PAYMENT_METHOD_BANK, 'eq', 'ub_ifsc_swift_code', $bankIfscReqFld);
            $PayMethodFld->requirements()->addOnChangerequirementUpdate(User::AFFILIATE_PAYMENT_METHOD_PAYPAL, 'eq', 'ub_ifsc_swift_code', $bankIfscUnReqFld);
            /* ] */

            /* [ */
            $frm->addTextArea(Labels::getLabel('FRM_BANK_ADDRESS', $langId), 'ub_bank_address');
            $bankBankAddressUnReqFld = new FormFieldRequirement('ub_bank_address', Labels::getLabel('FRM_BANK_ADDRESS', $langId));
            $bankBankAddressUnReqFld->setRequired(false);

            $bankBankAddressReqFld = new FormFieldRequirement('ub_bank_address', Labels::getLabel('FRM_BANK_ADDRESS', $langId));
            $bankBankAddressReqFld->setRequired(true);

            $PayMethodFld->requirements()->addOnChangerequirementUpdate(User::AFFILIATE_PAYMENT_METHOD_CHEQUE, 'eq', 'ub_bank_address', $bankBankAddressUnReqFld);
            $PayMethodFld->requirements()->addOnChangerequirementUpdate(User::AFFILIATE_PAYMENT_METHOD_BANK, 'eq', 'ub_bank_address', $bankBankAddressReqFld);
            $PayMethodFld->requirements()->addOnChangerequirementUpdate(User::AFFILIATE_PAYMENT_METHOD_PAYPAL, 'eq', 'ub_bank_address', $bankBankAddressUnReqFld);
            /* ] */

            /* [ */
            $fld = $frm->addTextBox(Labels::getLabel('FRM_PAYPAL_EMAIL_ACCOUNT', $langId), 'uextra_paypal_email_id');
            $PPEmailIdUnReqFld = new FormFieldRequirement('uextra_paypal_email_id', Labels::getLabel('FRM_PAYPAL_EMAIL_ACCOUNT', $langId));
            $PPEmailIdUnReqFld->setRequired(false);

            $PPEmailIdReqFld = new FormFieldRequirement('uextra_paypal_email_id', Labels::getLabel('FRM_PAYPAL_EMAIL_ACCOUNT', $langId));
            $PPEmailIdReqFld->setRequired(true);
            $PPEmailIdReqFld->setEmail();

            $PayMethodFld->requirements()->addOnChangerequirementUpdate(User::AFFILIATE_PAYMENT_METHOD_CHEQUE, 'eq', 'uextra_paypal_email_id', $PPEmailIdUnReqFld);
            $PayMethodFld->requirements()->addOnChangerequirementUpdate(User::AFFILIATE_PAYMENT_METHOD_BANK, 'eq', 'uextra_paypal_email_id', $PPEmailIdUnReqFld);
            $PayMethodFld->requirements()->addOnChangerequirementUpdate(User::AFFILIATE_PAYMENT_METHOD_PAYPAL, 'eq', 'uextra_paypal_email_id', $PPEmailIdReqFld);
            /* ] */
        } else {
            $frm->addRequiredField(Labels::getLabel('FRM_BANK_NAME', $langId), 'ub_bank_name');
            $frm->addRequiredField(Labels::getLabel('FRM_ACCOUNT_HOLDER_NAME', $langId), 'ub_account_holder_name');
            $frm->addRequiredField(Labels::getLabel('FRM_ACCOUNT_NUMBER', $langId), 'ub_account_number');
            $ifsc = $frm->addRequiredField(Labels::getLabel('FRM_IFSC_SWIFT_CODE', $langId), 'ub_ifsc_swift_code');
            $ifsc->requirements()->setRegularExpressionToValidate(ValidateElement::USERNAME_REGEX);
            $frm->addTextArea(Labels::getLabel('FRM_BANK_ADDRESS', $langId), 'ub_bank_address');
        }
        $frm->addTextArea(Labels::getLabel('FRM_OTHER_INFO_INSTRUCTIONS', $langId), 'withdrawal_instructions');
        return $frm;
    }

    private function getCreateWishListForm()
    {
        $frm = new Form('frmCreateWishList');
        $frm->setRequiredStarWith('NONE');
        $frm->addRequiredField('', 'uwlist_title');
        $frm->addHiddenField('', 'selprod_id');
        $frm->addSubmitButton('', 'btn_submit', Labels::getLabel('BTN_ADD', $this->siteLangId));
        $frm->setJsErrorDisplay('afterfield');
        return $frm;
    }

    private function getProfileInfoForm()
    {
        $frm = new Form('frmProfileInfo');
        $frm->addTextBox(Labels::getLabel('FRM_USERNAME', $this->siteLangId), 'credential_username', '');
        $frm->addTextBox(Labels::getLabel('FRM_EMAIL', $this->siteLangId), 'credential_email', '');
        $frm->addRequiredField(Labels::getLabel('FRM_CUSTOMER_NAME', $this->siteLangId), 'user_name');
        $frm->addDateField(Labels::getLabel('FRM_DATE_OF_BIRTH', $this->siteLangId), 'user_dob', '', array('readonly' => 'readonly'));
        $frm->addHiddenField('', 'user_phone_dcode');
        $phoneFld = $frm->addTextBox(Labels::getLabel('FRM_PHONE', $this->siteLangId), 'user_phone', '', array('class' => 'phone-js ltr-right', 'placeholder' => ValidateElement::PHONE_NO_FORMAT, 'maxlength' => ValidateElement::PHONE_NO_LENGTH));
        $phoneFld->requirements()->setRegularExpressionToValidate(ValidateElement::PHONE_REGEX);
        $phoneFld->requirements()->setCustomErrorMessage(Labels::getLabel('FRM_PLEASE_ENTER_VALID_PHONE_NUMBER_FORMAT.', $this->siteLangId));

        if (User::isAffiliate()) {
            $frm->addTextBox(Labels::getLabel('FRM_COMPANY', $this->siteLangId), 'uextra_company_name');
            $frm->addTextBox(Labels::getLabel('FRM_WEBSITE', $this->siteLangId), 'uextra_website');
            $frm->addTextBox(Labels::getLabel('FRM_ADDRESS_LINE1', $this->siteLangId), 'user_address1')->requirements()->setRequired();
            $frm->addTextBox(Labels::getLabel('FRM_ADDRESS_LINE2', $this->siteLangId), 'user_address2');
        }

        $countryObj = new Countries();
        $countriesArr = $countryObj->getCountriesAssocArr($this->siteLangId);
        $fld = $frm->addSelectBox(Labels::getLabel('FRM_COUNTRY', $this->siteLangId), 'user_country_id', $countriesArr, FatApp::getConfig('CONF_COUNTRY', FatUtility::VAR_INT, 0), array(), Labels::getLabel('FRM_SELECT', $this->siteLangId));
        $fld->requirement->setRequired(true);

        $frm->addSelectBox(Labels::getLabel('FRM_STATE', $this->siteLangId), 'user_state_id', array(), '', array(), Labels::getLabel('FRM_SELECT', $this->siteLangId))->requirement->setRequired(true);
        $frm->addTextBox(Labels::getLabel('FRM_CITY', $this->siteLangId), 'user_city');

        if (User::isAffiliate()) {
            $zipFld = $frm->addRequiredField(Labels::getLabel('FRM_POSTALCODE', $this->siteLangId), 'user_zip');
        }
        $parent = User::getAttributesById(UserAuthentication::getLoggedUserId(true), 'user_parent');
        if (User::isAdvertiser() && $parent == 0) {
            $fld = $frm->addTextBox(Labels::getLabel('FRM_COMPANY', $this->siteLangId), 'user_company');
            $fld = $frm->addTextArea(Labels::getLabel('FRM_BRIEF_PROFILE', $this->siteLangId), 'user_profile_info');
            $fld->html_after_field = '<small>' . Labels::getLabel('FRM_PLEASE_TELL_US_SOMETHING_ABOUT_YOURSELF', $this->siteLangId) . '</small>';
            $frm->addTextArea(Labels::getLabel('FRM_WHAT_KIND_PRODUCTS_SERVICES_ADVERTISE', $this->siteLangId), 'user_products_services');
        }
        $fld = $frm->addTextBox(Labels::getLabel('FRM_GST_NUMBER', $this->siteLangId), 'user_gst_number');
        $frm->addSubmitButton('', 'btn_submit', Labels::getLabel('BTN_SAVE_CHANGES', $this->siteLangId));
        return $frm;
    }

    private function getProfileImageForm()
    {
        $frm = new Form('frmProfile', array('id' => 'frmProfile'));
        $frm->addFileUpload(Labels::getLabel('FRM_PROFILE_PICTURE', $this->siteLangId), 'user_profile_image', array('id' => 'user_profile_image', 'onclick' => 'popupImage(this)', 'accept' => 'image/*', 'data-frm' => 'frmProfile'));
        return $frm;
    }

    private function getBankInfoForm()
    {
        $frm = new Form('frmBankInfo');
        $frm->addRequiredField(Labels::getLabel('FRM_BANK_NAME', $this->siteLangId), 'ub_bank_name', '');
        $frm->addRequiredField(Labels::getLabel('FRM_ACCOUNT_HOLDER_NAME', $this->siteLangId), 'ub_account_holder_name', '');
        $fld = $frm->addRequiredField(Labels::getLabel('FRM_ACCOUNT_NUMBER', $this->siteLangId), 'ub_account_number', '');
        $fld->requirement->setRequired(true);

        $ifsc = $frm->addRequiredField(Labels::getLabel('FRM_IFSC_SWIFT_CODE', $this->siteLangId), 'ub_ifsc_swift_code', '');
        $ifsc->requirements()->setRegularExpressionToValidate(ValidateElement::USERNAME_REGEX);

        $frm->addTextArea(Labels::getLabel('FRM_BANK_ADDRESS', $this->siteLangId), 'ub_bank_address', '');
        $htm = '<div class="alert alert-info" role="alert">
                    <div class="alert-icon">
                        <svg class="svg" width="18" height="18">
                            <use xlink:href="' . CONF_WEBROOT_URL . 'images/retina/sprite.svg' . AttachedFile::setTimeParam(RELEASE_DATE) . '#info">
                            </use>
                        </svg>
                     </div> 
                        <div class="alert-text"> ' . Labels::getLabel('LBL_YOUR_BANK_INFORMATION_IS_SAFE_WITH_US.', $this->siteLangId) . '
                        </div>
                </div>
                </>';
        $frm->addHtml('bank_info_safety_text', 'bank_info_safety_text', $htm);
        $frm->addSubmitButton('', 'btn_submit', Labels::getLabel('BTN_SAVE_CHANGES', $this->siteLangId));
        return $frm;
    }

    private function getChangePasswordForm()
    {
        $frm = new Form('changePwdFrm');

        if (false == UserAuthentication::isGuestUserLogged()) {
            $curPwd = $frm->addPasswordField(
                Labels::getLabel('FRM_CURRENT_PASSWORD', $this->siteLangId),
                'current_password'
            );
            $curPwd->requirements()->setRequired();
        }

        $newPwd = $frm->addPasswordField(
            Labels::getLabel('FRM_NEW_PASSWORD', $this->siteLangId),
            'new_password'
        );
        $newPwd->htmlAfterField = '<span class="form-text text-muted">' . sprintf(Labels::getLabel('FRM_EXAMPLE_PASSWORD', $this->siteLangId), 'User@123') . '</span>';
        $newPwd->requirements()->setRequired();
        $newPwd->requirements()->setRegularExpressionToValidate(ValidateElement::PASSWORD_REGEX);
        $newPwd->requirements()->setCustomErrorMessage(Labels::getLabel('MSG_PASSWORD_MUST_BE_ATLEAST_EIGHT_CHARACTERS_LONG_AND_ALPHANUMERIC', $this->siteLangId));
        $conNewPwd = $frm->addPasswordField(
            Labels::getLabel('FRM_CONFIRM_NEW_PASSWORD', $this->siteLangId),
            'conf_new_password'
        );
        $conNewPwdReq = $conNewPwd->requirements();
        $conNewPwdReq->setRequired();
        $conNewPwdReq->setCompareWith('new_password', 'eq');
        /* $conNewPwdReq->setCustomErrorMessage(Labels::getLabel('FRM_CONFIRM_PASSWORD_NOT_MATCHED',
        $this->siteLangId)); */
        $frm->addSubmitButton('', 'btn_submit', Labels::getLabel('BTN_SAVE', $this->siteLangId));
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
                        Labels::getLabel('LBL_Upload_File', $this->siteLangId),
                        array('class' => 'fileType-Js', 'id' => 'button-upload' . $field['sformfield_id'], 'data-field_id' => $field['sformfield_id'])
                    );
                    $fld1->htmlAfterField = '<span id="input-sformfield' . $field['sformfield_id'] . '"></span>';
                    if ($field['sformfield_required'] == 1) {
                        $fld1->captionWrapper = array('<div class="astrick">', '</div>');
                    }
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
                    $fld->requirements()->setRegularExpressionToValidate(ValidateElement::TIME_REGEX);
                    $fld->htmlAfterField = Labels::getLabel('LBL_HH:MM', $this->siteLangId);
                    $fld->requirements()->setCustomErrorMessage(Labels::getLabel('LBL_Please_enter_valid_time_format.', $this->siteLangId));
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
                $fld->htmlAfterField = '<p class="note">' . $field['sformfield_comment'] . '</p>';
            }
        }
        $frm->addSubmitButton('', 'btn_submit', Labels::getLabel('BTN_SAVE_CHANGES', $this->siteLangId));
        return $frm;
    }

    public function updatePhoto()
    {
        if (is_uploaded_file($_FILES['photo']['tmp_name'])) {
            $attachment = new AttachedFile();
            if ($attachment->saveImage(
                $_FILES['photo']['tmp_name'],
                AttachedFile::FILETYPE_USER_IMAGE,
                $this->userId,
                0,
                $_FILES['photo']['name'],
                0,
                false
            )) {
                Message::addMessage(Labels::getLabel('MSG_PROFILE_PICTURE_UPDATED', $this->siteLangId));
            } else {
                Message::addErrorMessage($attachment->getError());
            }
        } else {
            Message::addErrorMessage(Labels::getLabel('ERR_NO_FILE_UPLOADED', $this->siteLangId));
        }
        FatApp::redirectUser(UrlHelper::generateUrl('member', 'account'), [], CONF_WEBROOT_DASHBOARD);
    }

    public function escalateOrderReturnRequest($orrequest_id)
    {
        $orrequest_id = FatUtility::int($orrequest_id);
        if (!$orrequest_id) {
            Message::addErrorMessage(Labels::getLabel('ERR_INVALID_ACCESS', $this->siteLangId));
            CommonHelper::redirectUserReferer();
        }
        $srch = new OrderReturnRequestSearch();
        $srch->joinOrderProducts();
        $srch->addCondition('orrequest_id', '=', 'mysql_func_' . $orrequest_id, 'AND', true);
        $srch->addCondition('orrequest_status', '=', OrderReturnRequest::RETURN_REQUEST_STATUS_PENDING);

        /* $cnd = $srch->addCondition( 'orrequest_user_id', '=', $this->userId );
        $cnd->attachCondition('op_selprod_user_id', '=', $this->userId ); */
        $srch->addCondition('op_selprod_user_id', '=', $this->userId);

        $srch->doNotCalculateRecords();
        $srch->doNotLimitRecords();
        $srch->addMultipleFields(array('orrequest_id', 'orrequest_user_id'));
        $rs = $srch->getResultSet();
        $request = FatApp::getDb()->fetch($rs);

        if (!$request || $request['orrequest_id'] != $orrequest_id) {
            Message::addErrorMessage(Labels::getLabel('ERR_INVALID_ACCESS', $this->siteLangId));
            CommonHelper::redirectUserReferer();
        }

        /* buyer cannot escalate request[ */
        // if( $this->userId == $request['orrequest_user_id'] ){
        if (!User::isSeller()) {
            Message::addErrorMessage(Labels::getLabel('ERR_INVALID_ACCESS', $this->siteLangId));
            CommonHelper::redirectUserReferer();
        }
        /* ] */


        $orrObj = new OrderReturnRequest();
        if (!$orrObj->escalateRequest($request['orrequest_id'], $this->userId, $this->siteLangId)) {
            Message::addErrorMessage(Labels::getLabel($orrObj->getError(), $this->siteLangId));
            CommonHelper::redirectUserReferer();
        }

        /* email notification handling[ */
        $emailNotificationObj = new EmailHandler();
        if (!$emailNotificationObj->sendOrderReturnRequestStatusChangeNotification($orrequest_id, $this->siteLangId)) {
            Message::addErrorMessage(Labels::getLabel($emailNotificationObj->getError(), $this->siteLangId));
            CommonHelper::redirectUserReferer();
        }
        /* ] */
        CalculativeDataRecord::updateOrderReturnRequestCount();
        Message::addMessage(Labels::getLabel('MSG_YOUR_REQUEST_SENT', $this->siteLangId));
        CommonHelper::redirectUserReferer();
    }

    public function orderReturnRequestMessageSearch()
    {
        $frm = $this->getOrderReturnRequestMessageSearchForm($this->siteLangId);
        $postedData = FatApp::getPostedData();
        $post = $frm->getFormDataFromArray($postedData);
        $page = (empty($post['page']) || $post['page'] <= 0) ? 1 : FatUtility::int($post['page']);
        $pageSize = FatApp::getConfig('conf_page_size', FatUtility::VAR_INT, 10);

        $orrequest_id = isset($post['orrequest_id']) ? FatUtility::int($post['orrequest_id']) : 0;
        $isSeller = isset($postedData['isSeller']) ? FatUtility::int($postedData['isSeller']) : 0;

        $parentAndTheirChildIds = User::getParentAndTheirChildIds($this->userParentId, false, true);
        $srch = new OrderReturnRequestMessageSearch($this->siteLangId);
        $srch->joinOrderReturnRequests();
        $srch->joinMessageUser($this->siteLangId);
        $srch->joinMessageAdmin();
        $srch->joinOrderProducts();
        $srch->addCondition('orrmsg_orrequest_id', '=', 'mysql_func_' . $orrequest_id, 'AND', true);
        if (0 < $isSeller) {
            $srch->addCondition('op_selprod_user_id', 'in', $parentAndTheirChildIds);
        } else {
            $srch->addCondition('orrequest_user_id', 'in', $parentAndTheirChildIds);
        }
        $srch->setPageNumber($page);
        $srch->setPageSize($pageSize);
        $srch->addOrder('orrmsg_id', 'DESC');
        $srch->addMultipleFields(
            array(
                'orrmsg_id',
                'orrmsg_from_user_id',
                'orrmsg_msg',
                'orrmsg_date',
                'msg_user.user_name as msg_user_name',
                'orrequest_status',
                'orrmsg_from_admin_id',
                'admin_name',
                'ifnull(s_l.shop_name, s.shop_identifier) as shop_name',
                's.shop_id',
                'op_selprod_user_id',
                'op_rounding_off'
            )
        );

        $rs = $srch->getResultSet();
        $messagesList = FatApp::getDb()->fetchAll($rs, 'orrmsg_id');
        ksort($messagesList);

        $this->set('messagesList', (!empty($messagesList) ? $messagesList : array()));
        $this->set('page', $page);
        $this->set('pageCount', $srch->pages());
        $this->set('postedData', $post);

        $startRecord = ($page - 1) * $pageSize + 1;
        $endRecord = $page * $pageSize;
        $totalRecords = $srch->recordCount();
        if ($totalRecords < $endRecord) {
            $endRecord = $totalRecords;
        }
        $this->set('totalRecords', $totalRecords);
        $this->set('startRecord', $startRecord);
        $this->set('endRecord', $endRecord);
        $this->set('parentAndTheirChildIds', $parentAndTheirChildIds);

        if (true === MOBILE_APP_API_CALL) {
            $this->_template->render();
        }

        $this->set('loadMoreBtnHtml', $this->_template->render(false, false, '_partial/load-previous-btn.php', true));
        $this->set('html', $this->_template->render(false, false, 'account/order-return-request-messages-list.php', true, false));
        $this->_template->render(false, false, 'json-success.php', true, false);
    }

    private function getCreditsSearchForm($langId)
    {
        $frm = new Form('frmRecordSearch');
        $frm->addHiddenField('', 'total_record_count', '');
        $frm->addHiddenField('', 'page');
        $frm->addTextBox(Labels::getLabel('FRM_KEYWORD', $langId), 'keyword', '');
        $frm->addSelectBox(Labels::getLabel('FRM_CREDIT_TYPE', $langId), 'debit_credit_type', array(-1 => Labels::getLabel('FRM_BOTH-Debit/Credit', $langId)) + Transactions::getCreditDebitTypeArr($langId), -1, array(), '');
        $frm->addDateField(Labels::getLabel('FRM_DATE_FROM', $langId), 'date_from', '', array('readonly' => 'readonly', 'class' => 'field--calender'));
        $frm->addDateField(Labels::getLabel('FRM_DATE_TO', $langId), 'date_to', '', array('readonly' => 'readonly', 'class' => 'field--calender'));

        HtmlHelper::addSearchButton($frm);
        HtmlHelper::addClearButton($frm, 'btn btn-clear');
        return $frm;
    }

    private function sendMessageForm($langId)
    {
        $frm = new Form('frmSendMessage');
        $frm->addHiddenField('', 'message_thread_id');
        $frm->addTextarea(Labels::getLabel('FRM_COMMENTS', $langId), 'message_text', '')->requirements()->setRequired(true);
        return $frm;
    }

    private function getSettingsForm()
    {
        $frm = new Form('frmBankInfo');
        $activeInactiveArr = applicationConstants::getActiveInactiveArr($this->siteLangId);
        $frm->addSelectBox(Labels::getLabel('FRM_AUTO_RENEW_SUBSCRIPTION', $this->siteLangId), 'user_autorenew_subscription', $activeInactiveArr, '', array(), Labels::getLabel('LBL_Select', $this->siteLangId));
        $frm->addSubmitButton('', 'btn_submit', Labels::getLabel('BTN_SAVE_CHANGES', $this->siteLangId));
        return $frm;
    }

    private function getRechargeWalletForm($langId)
    {
        $frm = new Form('frmRechargeWallet');
        $frm->addFloatField('', 'amount');
        $frm->addHtml('', 'btn_submit', HtmlHelper::addButtonHtml(Labels::getLabel('BTN_ADD_CREDITS', $langId), 'submit', 'btn_submit', 'btn-apply'));
        return $frm;
    }

    public function myAddresses()
    {
        $this->_template->render();
    }

    public function searchAddresses()
    {
        $address = new Address(0, $this->siteLangId);
        $addresses = (array) $address->getData(Address::TYPE_USER, $this->userId);

        $this->set('addresses', $addresses);

        if (true === MOBILE_APP_API_CALL) {
            $cartObj = new Cart($this->userId);
            $this->set('shippingAddressId', $cartObj->getCartShippingAddress());
            $this->_template->render();
        }

        if (empty($addresses)) {
            $this->set('noRecordsHtml', $this->_template->render(false, false, '_partial/no-record-found.php', true));
        }
        $this->_template->render(false, false);
    }

    public function addAddressForm(int $addr_id = 0, int $langId = 0)
    {
        $langId = 1 > $langId ? $this->siteLangId : $langId;
        $addressFrm = $this->getUserAddressForm($langId);

        $stateId = 0;

        if ($addr_id > 0) {
            $address = new Address($addr_id, $langId);
            $data = $address->getData(Address::TYPE_USER, $this->userId);
            if (empty($data)) {
                FatUtility::dieJsonError(Labels::getLabel('ERR_INVALID_REQUEST', $langId));
            }
            $stateId = $data['addr_state_id'];
            $addressFrm->fill($data);
        }

        $this->set('addr_id', $addr_id);
        $this->set('stateId', $stateId);
        $this->set('addressFrm', $addressFrm);
        $this->set('formLayout', Language::getLayoutDirection($langId));
        $this->_template->render(false, false);
    }

    public function truncateDataRequestPopup()
    {
        if (UserAuthentication::isGuestUserLogged()) {
            LibHelper::exitWithError(Labels::getLabel("ERR_UNAUTHORISED_ACCESS", $this->siteLangId));
        }

        $this->_template->render(false, false);
    }

    public function sendTruncateRequest()
    {
        $srch = new UserGdprRequestSearch();
        $srch->addCondition('ureq_user_id', '=', 'mysql_func_' . $this->userId, 'AND', true);
        $srch->addCondition('ureq_type', '=', 'mysql_func_' . UserGdprRequest::TYPE_TRUNCATE, 'AND', true);
        $srch->addCondition('ureq_status', '=', 'mysql_func_' . UserGdprRequest::STATUS_PENDING, 'AND', true);
        $srch->addCondition('ureq_deleted', '=', 'mysql_func_' . applicationConstants::NO, 'AND', true);
        $rs = $srch->getResultSet();
        $row = FatApp::getDb()->fetch($rs);
        if ($row) {
            LibHelper::exitWithError(Labels::getLabel('ERR_YOU_HAVE_ALRADY_SUBMITTED_THE_REQUEST', $this->siteLangId));
        }

        $assignValues = array(
            'ureq_user_id' => $this->userId,
            'ureq_type' => UserGdprRequest::TYPE_TRUNCATE,
            'ureq_date' => date('Y-m-d H:i:s'),
        );

        $userReqObj = new UserGdprRequest();
        $userReqObj->assignValues($assignValues);
        if (!$userReqObj->save()) {
            LibHelper::exitWithError($userReqObj->getError());
        }
        CalculativeDataRecord::updateGdprRequestCount();
        $this->set('msg', Labels::getLabel('MSG_Request_sent_successfully', $this->siteLangId));
        if (true === MOBILE_APP_API_CALL) {
            $this->_template->render();
        }
        $this->_template->render(false, false, 'json-success.php');
    }

    private function getRequestDataForm()
    {
        $frm = new Form('frmRequestdata');
        $frm->addTextBox(Labels::getLabel('FRM_EMAIL', $this->siteLangId), 'credential_email', '', array('readonly' => 'readonly'));
        $frm->addTextBox(Labels::getLabel('FRM_NAME', $this->siteLangId), 'user_name', '', array('readonly' => 'readonly'));
        $purposeFld = $frm->addTextArea(Labels::getLabel('FRM_PURPOSE_OF_REQUEST_DATA', $this->siteLangId), 'ureq_purpose');
        $purposeFld->requirements()->setRequired();
        $frm->addSubmitButton('', 'btn_submit', Labels::getLabel('BTN_SEND_REQUEST', $this->siteLangId));
        return $frm;
    }

    public function requestDataForm()
    {
        if (UserAuthentication::isGuestUserLogged()) {
            LibHelper::exitWithError(Labels::getLabel("ERR_UNAUTHORISED_ACCESS", $this->siteLangId));
        }

        $userObj = new User($this->userId);
        $srch = $userObj->getUserSearchObj(array('credential_username', 'credential_email', 'user_name'));
        $rs = $srch->getResultSet();

        if (!$rs) {
            FatUtility::dieJsonError(Labels::getLabel('ERR_INVALID_REQUEST', $this->siteLangId));
        }

        $data = FatApp::getDb()->fetch($rs, 'user_id');

        if ($data === false) {
            FatUtility::dieJsonError(Labels::getLabel('ERR_INVALID_REQUEST', $this->siteLangId));
        }
        $cPageSrch = ContentPage::getSearchObject($this->siteLangId);
        $cPageSrch->addCondition('cpage_id', '=', 'mysql_func_' . FatApp::getConfig('CONF_GDPR_POLICY_PAGE', FatUtility::VAR_INT, 0), 'AND', true);
        $cpage = FatApp::getDb()->fetch($cPageSrch->getResultSet());
        $gdprPolicyLinkHref = '';
        if (!empty($cpage) && is_array($cpage)) {
            $gdprPolicyLinkHref = UrlHelper::generateUrl('Cms', 'view', array($cpage['cpage_id']), CONF_WEBROOT_FRONTEND);
        }

        $frm = $this->getRequestDataForm();
        $frm->fill($data);
        $this->set('frm', $frm);
        $this->set('gdprPolicyLinkHref', $gdprPolicyLinkHref);
        $this->set('siteLangId', $this->siteLangId);
        $this->_template->render(false, false);
    }

    public function setupRequestData()
    {
        if (UserAuthentication::isGuestUserLogged()) {
            LibHelper::exitWithError(Labels::getLabel("ERR_UNAUTHORISED_ACCESS", $this->siteLangId));
        }

        $frm = $this->getRequestDataForm();
        $post = $frm->getFormDataFromArray(FatApp::getPostedData());
        if (false === $post) {
            LibHelper::exitWithError(current($frm->getValidationErrors()));
        }

        $srch = new UserGdprRequestSearch();
        $srch->addCondition('ureq_user_id', '=', 'mysql_func_' . $this->userId, 'AND', true);
        $srch->addCondition('ureq_type', '=', 'mysql_func_' . UserGdprRequest::TYPE_DATA_REQUEST, 'AND', true);
        $srch->addCondition('ureq_status', '=', 'mysql_func_' . UserGdprRequest::STATUS_PENDING, 'AND', true);
        $srch->addCondition('ureq_deleted', '=', 'mysql_func_' . applicationConstants::NO, 'AND', true);
        $rs = $srch->getResultSet();
        $row = FatApp::getDb()->fetch($rs);
        if ($row) {
            LibHelper::exitWithError(Labels::getLabel('ERR_YOU_HAVE_ALRADY_SUBMITTED_THE_DATA_REQUEST', $this->siteLangId));
        }

        $assignValues = array(
            'ureq_user_id' => $this->userId,
            'ureq_type' => UserGdprRequest::TYPE_DATA_REQUEST,
            'ureq_date' => date('Y-m-d H:i:s'),
            'ureq_purpose' => $post['ureq_purpose'],
        );

        $userReqObj = new UserGdprRequest();
        $userReqObj->assignValues($assignValues);
        if (!$userReqObj->save()) {
            LibHelper::exitWithError($userReqObj->getError());
        }

        $post['user_id'] = $this->userId;
        $emailNotificationObj = new EmailHandler();
        if (!$emailNotificationObj->sendDataRequestNotification($post, $this->siteLangId)) {
            LibHelper::exitWithError(Labels::getLabel($emailNotificationObj->getError(), $this->siteLangId));
        }

        $this->set('msg', Labels::getLabel('MSG_REQUEST_SENT_SUCCESSFULLY', $this->siteLangId));
        if (true === MOBILE_APP_API_CALL) {
            $this->_template->render();
        }
        $this->_template->render(false, false, 'json-success.php');
    }

    //Valid for 10 Minutes only
    public function getTempToken()
    {
        $uObj = new User($this->userId);
        $tempToken = substr(md5(rand(1, 99999) . microtime()), 0, UserAuthentication::TOKEN_LENGTH);

        if (!$uObj->createUserTempToken($tempToken)) {
            FatUtility::dieJsonError($uObj->getError());
        }
        $this->set('data', array('tempToken' => $tempToken));
        $this->_template->render();
    }

    public function notifications()
    {
        $page = FatApp::getPostedData('page', FatUtility::VAR_INT, 1);
        $defaultPageSize = FatApp::getConfig('conf_page_size', FatUtility::VAR_INT, 10);
        $pageSize = FatApp::getPostedData('pagesize', FatUtility::VAR_INT, $defaultPageSize);
        $srch = Notifications::getSearchObject();
        $srch->addCondition('unt.unotification_user_id', '=', 'mysql_func_' . $this->userId, 'AND', true);
        if (MOBILE_APP_API_CALL) {
            $srch->addCondition('unt.unotification_type', 'NOT IN', Notifications::SELLER_ONLY_NOTIFICATION_TYPES);
        }
        $srch->addOrder('unt.unotification_id', 'DESC');
        $srch->addMultipleFields(array('unt.*'));
        $srch->setPageNumber($page);
        $srch->setPageSize($pageSize);
        $rs = $srch->getResultSet();
        $records = FatApp::getDb()->fetchAll($rs);

        $this->set('notifications', $records);
        $this->set('total_pages', $srch->pages());
        $this->set('total_records', $srch->recordCount());
        $this->_template->render();
    }

    public function readAllNotifications()
    {
        $smt = array(
            'smt' => Notifications::DB_TBL_PREFIX . 'is_read = ? AND ' . Notifications::DB_TBL_PREFIX . 'user_id = ?',
            'vals' => array(applicationConstants::NO, (int) $this->userId)
        );
        $db = FatApp::getDb();
        if (!$db->updateFromArray(Notifications::DB_TBL, array(Notifications::DB_TBL_PREFIX . 'is_read' => 1), $smt)) {
            FatUtility::dieJsonError($db->getError());
        }
        $this->set('msg', Labels::getLabel('MSG_SUCCESSFULLY_UPDATED', $this->siteLangId));
        $this->_template->render();
    }

    public function markNotificationRead($notificationId)
    {
        $notificationId = FatUtility::int($notificationId);
        if (1 > $notificationId) {
            FatUtility::dieJSONError(Labels::getLabel('ERR_INVALID_REQUEST', $this->siteLangId));
        }

        $srch = Notifications::getSearchObject();
        $srch->addCondition('unt.unotification_user_id', '=', 'mysql_func_' . $this->userId, 'AND', true);
        $srch->addCondition('unt.unotification_id', '=', 'mysql_func_' . $notificationId, 'AND', true);
        $srch->setPageSize(1);
        $rs = $srch->getResultSet();
        $notification = FatApp::getDb()->fetch($rs);
        if (!($notification)) {
            FatUtility::dieJSONError(Labels::getLabel('ERR_INVALID_REQUEST', $this->siteLangId));
        }
        $nObj = new Notifications();
        if (!$nObj->readUserNotification($notificationId, $this->userId)) {
            FatUtility::dieJsonError($nObj->getError());
        }
        $this->set('msg', Labels::getLabel('MSG_SUCCESSFULLY_UPDATED', $this->siteLangId));
        $this->_template->render();
    }

    public function changePhoneForm($updatePhnFrm = 0)
    {
        $phData = User::getAttributesById($this->userId, ['user_phone_dcode', 'user_phone', 'user_country_id']);
        $updatePhnFrm = empty($updatePhnFrm) ? (empty($phData['user_phone']) ? 1 : 0) : $updatePhnFrm;

        $frm = $this->getPhoneNumberForm();
        if (1 > $updatePhnFrm && !empty($phData['user_phone'])) {
            $frm->fill($phData + ['use_for' => User::OTP_FOR_OLD_PHONE_NO]);
            $phnFld = $frm->getField('user_phone');
            $phnFld->setFieldTagAttribute('readonly', 'readonly');
        }
        if (0 < $updatePhnFrm) {
            $frm->fill(['use_for' => User::OTP_FOR_NEW_PHONE_NO]);
        }

        $countryIso = Countries::getCountryById($phData['user_country_id'], $this->siteLangId, 'country_code');
        $this->set('countryIso', $countryIso);
        $this->set('frm', $frm);
        $this->set('updatePhnFrm', $updatePhnFrm);
        $this->set('siteLangId', $this->siteLangId);
        $json['html'] = $this->_template->render(false, false, 'account/change-phone-form.php', true, false);
        FatUtility::dieJsonSuccess($json);
    }

    public function changeEmailUsingPhoneForm1()
    {
        $phData = User::getAttributesById($this->userId, ['user_phone_dcode', 'user_phone', 'user_country_id']);
        $frm = $this->getPhoneNumberForm();
        $frm->fill($phData + ['use_for' => User::OTP_FOR_EMAIL]);
        $phnFld = $frm->getField('user_phone');
        $phnFld->setFieldTagAttribute('readonly', 'readonly');

        $countryIso = Countries::getCountryById($phData['user_country_id'], $this->siteLangId, 'country_code');
        $this->set('countryIso', $countryIso);
        $this->set('frm', $frm);
        $this->set('siteLangId', $this->siteLangId);
        $json['html'] = $this->_template->render(false, false, NULL, true, false);
        FatUtility::dieJsonSuccess($json);
    }

    private function sendOtp(int $userId, string $dialCode, int $phone)
    {
        $userObj = new User($userId);
        $otp = $userObj->prepareUserPhoneOtp($dialCode, $phone);
        if (false == $otp) {
            LibHelper::dieJsonError($userObj->getError());
        }

        $dialCode = ValidateElement::formatDialCode(trim($dialCode));
        $userData = $userObj->getUserInfo('user_name', false, false);
        $obj = clone $userObj;
        if (false === $obj->sendOtp($dialCode . $phone, $userData['user_name'], $otp, $this->siteLangId)) {
            LibHelper::dieJsonError($obj->getError());
        }
        return true;
    }

    public function getOtp()
    {
        $frm = $this->getPhoneNumberForm();

        $post = $frm->getFormDataFromArray(FatApp::getPostedData());
        if (false === $post) {
            LibHelper::dieJsonError(current($frm->getValidationErrors()));
        }
        $useFor = FatApp::getPostedData('use_for', FatUtility::VAR_INT, 0);
        if (1 > $useFor) {
            LibHelper::dieJsonError(Labels::getLabel("MSG_INVALID_FORM_TYPE", $this->siteLangId));
        }
        $phoneNumber = FatApp::getPostedData('user_phone', FatUtility::VAR_INT, '');
        $dialCode = FatApp::getPostedData('user_phone_dcode', FatUtility::VAR_STRING, '');
        if (empty($phoneNumber) || empty($dialCode)) {
            $message = Labels::getLabel("MSG_INVALID_PHONE_NUMBER_FORMAT", $this->siteLangId);
            LibHelper::dieJsonError($message);
        }

        if (User::OTP_FOR_NEW_PHONE_NO != $useFor && false === UserAuthentication::validateUserPhone($this->userId, $phoneNumber)) {
            LibHelper::dieJsonError(Labels::getLabel('ERR_INVALID_PHONE_NUMBER', $this->siteLangId));
        }

        if (User::OTP_FOR_NEW_PHONE_NO == $useFor) {
            $db = FatApp::getDb();
            $srch = User::getSearchObject(false, 0, false);
            $srch->addCondition('user_phone', '=', 'mysql_func_' . $phoneNumber, 'AND', true);
            $srch->addCondition('user_id', '!=', 'mysql_func_' . $this->userId, 'AND', true);
            $rs = $srch->getResultSet();
            $row = $db->fetch($rs);
            if (!empty($row)) {
                LibHelper::dieJsonError(Labels::getLabel('ERR_THIS_PHONE_NUMBER_IS_ALREADY_EXISTS.', $this->siteLangId));
            }
        }

        $this->sendOtp($this->userId, $dialCode,  $phoneNumber);

        $this->set('msg', Labels::getLabel('MSG_OTP_SENT!_PLEASE_CHECK_YOUR_PHONE.', $this->siteLangId));
        if (true === MOBILE_APP_API_CALL) {
            $this->_template->render();
        }

        $otpFrm = $this->getOtpForm();
        $otpFrm->fill(['user_id' => $this->userId]);
        $this->set('frm', $otpFrm);
        $this->set('dialCode', $dialCode);
        $this->set('phoneNumber', $phoneNumber);
        $this->set('useFor', $useFor);
        $json['html'] = $this->_template->render(false, false, 'account/otp-form.php', true, false);
        FatUtility::dieJsonSuccess($json);
    }

    public function validateOtp($openFrmType = 0)
    {
        $updateToDb = (User::OTP_FOR_NEW_PHONE_NO == $openFrmType ? 1 : 0);
        $this->validateOtpApi($updateToDb, false);

        if (User::OTP_FOR_OLD_PHONE_NO == $openFrmType) {
            $this->changePhoneForm(1);
            exit;
        } elseif (User::OTP_FOR_EMAIL == $openFrmType) {
            $frm = $this->getChangeEmailUsingPhoneForm2();
            $frm->fill(['otp' => $this->get('otp')]);
            $this->set('frm', $frm);
            $this->set('siteLangId', $this->siteLangId);
            $json['html'] = $this->_template->render(false, false, 'account/change-email-using-phone-form2.php', true, false);
            FatUtility::dieJsonSuccess($json);
            exit;
        }

        $this->_template->render(false, false, 'json-success.php');
    }

    public function resendOtp()
    {
        $dialCode = FatApp::getPostedData('user_phone_dcode', FatUtility::VAR_STRING, '');
        $phone = FatApp::getPostedData('user_phone', FatUtility::VAR_INT, 0);

        if (!empty($phone)) {
            $this->sendOtp($this->userId, $dialCode, $phone);
        } else {
            $userObj = new User($this->userId);
            if (false == $userObj->resendOtp()) {
                FatUtility::dieJsonError($userObj->getError());
            }
        }

        $this->set('msg', Labels::getLabel('MSG_OTP_SENT!_PLEASE_CHECK_YOUR_PHONE.', $this->siteLangId));
        if (true === MOBILE_APP_API_CALL) {
            $this->_template->render();
        }
        $this->_template->render(false, false, 'json-success.php');
    }

    public function pushNotifications()
    {
        $page = FatApp::getPostedData('page', FatUtility::VAR_INT, 1);
        $defaultPageSize = FatApp::getConfig('conf_page_size', FatUtility::VAR_INT, 10);
        $pageSize = FatApp::getPostedData('pagesize', FatUtility::VAR_INT, $defaultPageSize);

        $srch = User::getSearchObject();
        $srch->joinTable(UserAuthentication::DB_TBL_USER_AUTH, 'INNER JOIN', 'ua.uauth_user_id = u.user_id', 'ua');
        $srch->addMultipleFields(['uauth_device_os', 'user_regdate']);
        $srch->addCondition('uauth_user_id', '=', 'mysql_func_' . $this->userId, 'AND', true);
        $srch->setPageSize(1);
        $rs = $srch->getResultSet();
        $uData = FatApp::getDb()->fetch($rs);

        $srch = PushNotification::getSearchObject(true);
        $srch->addMultipleFields([
            'pnotification_id',
            'pnotification_title',
            'pnotification_description',
            'pnotification_url',
            'pntu_user_id'
        ]);
        $cond = $srch->addCondition('pnotification_status', '=', 'mysql_func_' . PushNotification::STATUS_COMPLETED, 'AND', true);
        $cond->attachCondition('pnotification_status', '=', 'mysql_func_' . PushNotification::STATUS_PROCESSING, 'OR', true);
        $srch->addCondition('pnotification_user_auth_type', '=', 'mysql_func_' . User::AUTH_TYPE_REGISTERED, 'AND', true);
        $srch->addCondition('pnotification_added_on', '>=', $uData['user_regdate']);
        $cond = $srch->addCondition('pntu_user_id', 'IS', 'mysql_func_NULL', 'AND', true);
        $cond->attachCondition('pntu_user_id', '=', 'mysql_func_' . $this->userId, 'OR', true);
        $cond = $srch->addCondition('pnotification_device_os', '=', 'mysql_func_' . User::DEVICE_OS_BOTH, 'AND', true);
        $cond->attachCondition('pnotification_device_os', '=', $uData['uauth_device_os'], 'OR');
        $srch->setPageNumber($page);
        $srch->setPageSize($pageSize);
        $srch->addOrder('pnotification_added_on', 'DESC');
        $srch->addOrder('pnotification_id', 'DESC');
        $rs = $srch->getResultSet();
        $pnotificationsArr = FatApp::getDb()->fetchAll($rs);

        $this->set('pnotifications', $pnotificationsArr);
        $this->set('total_pages', $srch->pages());
        $this->set('total_records', $srch->recordCount());
        $this->_template->render();
    }

    public function cookiesPreferencesForm()
    {
        $user = new User($this->userId);
        $data = $user->getUserSelectedCookies();

        if (true === MOBILE_APP_API_CALL) {
            $this->set('data', ['cookiesPreferencesInfo' => (object) $data]);
            $this->_template->render();
        }

        $frm = $this->getCookiesPreferencesForm();
        if ($data != false) {
            $frm->fill($data);
        }

        $this->set('frm', $frm);
        $this->_template->addJs('account/page-js/profile-info.js');
        $this->_template->render();
    }

    private function getCookiesPreferencesForm()
    {
        $frm = new Form('frmCookiesPreferences');
        $frm->addCheckBox(Labels::getLabel("FRM_FUNCTIONAL", $this->siteLangId), 'ucp_functional', 1, array(), true, 0);
        $frm->addCheckBox(Labels::getLabel("FRM_STATISTICAL_ANALYSIS", $this->siteLangId), 'ucp_statistical', 1, array(), false, 0);
        $frm->addCheckBox(Labels::getLabel("FRM_PERSONALISE_EXPERIENCE", $this->siteLangId), 'ucp_personalized', 1, array(), false, 0);
        $frm->addSubmitButton('', 'btn_submit', Labels::getLabel('BTN_SAVE_CHANGES', $this->siteLangId));
        return $frm;
    }

    public function updateCookiesPreferences()
    {
        $post = FatApp::getPostedData();
        if (1 > count($post) && true === MOBILE_APP_API_CALL) {
            LibHelper::dieJsonError(Labels::getLabel("ERR_INVALID_REQUEST", $this->siteLangId));
        }

        $frm = $this->getCookiesPreferencesForm();
        $post = $frm->getFormDataFromArray($post);
        if (false === $post) {
            $message = Labels::getLabel(current($frm->getValidationErrors()), $this->siteLangId);
            FatUtility::dieJsonError($message);
        }

        $data = [
            'ucp_statistical' => FatApp::getPostedData('ucp_statistical', FatUtility::VAR_INT, 0),
            'ucp_personalized' => FatApp::getPostedData('ucp_personalized', FatUtility::VAR_INT, 0)
        ];

        $user = new User($this->userId);
        if (!$user->updateCookiesPreferences($data)) {
            $message = Labels::getLabel($user->getError(), $this->siteLangId);
            FatUtility::dieJsonError($message);
        }

        $this->set('msg', Labels::getLabel('MSG_Updated_Successfully', $this->siteLangId));
        if (true === MOBILE_APP_API_CALL) {
            $this->_template->render();
        }
        $this->_template->render(false, false, 'json-success.php');
    }

    public function viewBuyerOrderInvoice($orderId, $opId = 0)
    {
        if (!$orderId) {
            $message = Labels::getLabel('MSG_INVALID_ACCESS', $this->siteLangId);
            if (true === MOBILE_APP_API_CALL) {
                LibHelper::dieJsonError($message);
            }
            Message::addErrorMessage($message);
            CommonHelper::redirectUserReferer();
        }

        $opId = FatUtility::int($opId);

        $srch = new OrderProductSearch($this->siteLangId, true, true);
        $srch->joinPaymentMethod();
        $srch->joinSellerProducts();
        $srch->joinProduct();
        $srch->joinShop();
        $srch->joinShopSpecifics();
        $srch->joinShopCountry();
        $srch->joinShopState();
        $srch->addOrderProductCharges();
        $srch->joinOrderProductSpecifics();
        $srch->joinShippingCharges();
        $srch->addCondition('order_id', '=', $orderId);
        if (0 < $opId) {
            $srch->addCondition('op_id', '=', 'mysql_func_' . $opId, 'AND', true);
        }
        $srch->addDirectCondition("((op_selprod_user_id = $this->userId and op.op_status_id IN (" . implode(",", unserialize(FatApp::getConfig("CONF_VENDOR_ORDER_STATUS"))) . ")) or (order_user_id=$this->userId and op.op_status_id IN (" . implode(",", unserialize(FatApp::getConfig("CONF_BUYER_ORDER_STATUS"))) . ") ) )");

        $srch->addMultipleFields(array('*', 'shop_country_l.country_name as shop_country_name', 'shop_state_l.state_name as shop_state_name', 'shop_city', 'product_hsn_code'));

        $childOrderDetail = FatApp::getDb()->fetchAll($srch->getResultSet(), 'op_id');

        if (1 > count($childOrderDetail)) {
            $message = Labels::getLabel('MSG_Invalid_Order', $this->siteLangId);
            if (true === MOBILE_APP_API_CALL) {
                LibHelper::dieJsonError($message);
            }
            Message::addErrorMessage($message);
            CommonHelper::redirectUserReferer();
        }

        $orderObj = new Orders();
        $orderDetail = $orderObj->getOrderById($orderId, $this->siteLangId);
        $orderDetail['charges'] = $orderObj->getOrderProductChargesByOrderId($orderDetail['order_id']);

        if (count($childOrderDetail)) {
            foreach ($childOrderDetail as &$arr) {
                $arr['options'] = SellerProduct::getSellerProductOptions($arr['op_selprod_id'], true, $this->siteLangId);
            }
        }

        foreach ($childOrderDetail as $op_id => $val) {
            $childOrderDetail[$op_id]['charges'] = $orderDetail['charges'][$op_id];

            $opChargesLog = new OrderProductChargeLog($op_id);
            $taxOptions = $opChargesLog->getData($this->siteLangId);
            $childOrderDetail[$op_id]['taxOptions'] = $taxOptions;
        }

        $address = $orderObj->getOrderAddresses($orderDetail['order_id']);
        $orderDetail['billingAddress'] = $address[Orders::BILLING_ADDRESS_TYPE];
        $orderDetail['shippingAddress'] = (!empty($address[Orders::SHIPPING_ADDRESS_TYPE])) ? $address[Orders::SHIPPING_ADDRESS_TYPE] : array();

        $pickUpAddress = $orderObj->getOrderAddresses($orderDetail['order_id'], $opId);
        $orderDetail['pickupAddress'] = (!empty($pickUpAddress[Orders::PICKUP_ADDRESS_TYPE])) ? $pickUpAddress[Orders::PICKUP_ADDRESS_TYPE] : array();

        // CommonHelper::printArray($childOrderDetail, true);

        $template = new FatTemplate('', '');
        $template->set('siteLangId', $this->siteLangId);
        $template->set('orderDetail', $orderDetail);
        $template->set('childOrderDetail', $childOrderDetail);
        $template->set('opId', $opId);
        $template->set('buyerGstNumber', User::getAttributesById($orderDetail['order_user_id'], 'user_gst_number'));
        $template->set('sellerGstNumber', User::getAttributesById(current($childOrderDetail)['selprod_user_id'], 'user_gst_number'));

        require_once CONF_INSTALLATION_PATH . 'vendor/autoload.php';
        $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
        $pdf->SetCreator(PDF_CREATOR);
        $pdf->SetAuthor(FatApp::getConfig("CONF_WEBSITE_NAME_" . $this->siteLangId));
        $pdf->SetKeywords(FatApp::getConfig("CONF_WEBSITE_NAME_" . $this->siteLangId));
        $pdf->setHeaderFont(array(PDF_FONT_NAME_MAIN, '', PDF_FONT_SIZE_MAIN));
        $pdf->SetHeaderMargin(0);
        $pdf->SetHeaderData('', 0, '', '', array(255, 255, 255), array(255, 255, 255));
        $pdf->setFooterData(array(0, 0, 0), array(200, 200, 200));
        $pdf->SetFooterMargin(PDF_MARGIN_FOOTER);
        $pdf->setFooterFont(array(PDF_FONT_NAME_DATA, '', PDF_FONT_SIZE_DATA));
        $pdf->SetMargins(10, 10, 10);
        $pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);
        $pdf->AddPage();
        $pdf->SetTitle(Labels::getLabel('LBL_Tax_Invoice', $this->siteLangId));
        $pdf->SetSubject(Labels::getLabel('LBL_Tax_Invoice', $this->siteLangId));

        // set LTR direction for english translation
        $pdf->setRTL(('rtl' == Language::getLayoutDirection($this->siteLangId)));
        // set font
        $pdf->SetFont('dejavusans');

        $templatePath = "account/view-buyer-order-invoice.php";
        $html = $template->render(false, false, $templatePath, true, true);
        $pdf->writeHTML($html, true, false, true, false, '');
        $pdf->lastPage();

        ob_end_clean();
        // $saveFile = CONF_UPLOADS_PATH . 'demo-pdf.pdf';
        //$pdf->Output($saveFile, 'F');
        $pdf->Output('tax-invoice.pdf', 'I');
        return true;
    }

    private function updateFavConfTime()
    {
        $arrToUpdate = [
            'conf_name' => 'LAST_FAV_MARK_TIME',
            'conf_val' => time()
        ];
        if (!FatApp::getDb()->insertFromArray(
            'tbl_configurations',
            $arrToUpdate,
            false,
            array(),
            $arrToUpdate
        )) {
            echo "2424";
        }
    }

    public function getBreadcrumbNodes($action)
    {
        if (FatUtility::isAjaxCall()) {
            return;
        }

        $className = get_class($this);
        $arr = explode('-', FatUtility::camel2dashed($className));
        array_pop($arr);
        $className = ucwords(implode('_', $arr));

        if ($action == 'index') {
            $this->nodes[] = array('title' => ucwords(Labels::getLabel('BCN_' . $className)));
        } else if ($action == 'bankInfoForm') {
            $this->nodes[] = array('title' => Labels::getLabel('LBL_BANK_ACCOUNT_INFORMATION', $this->siteLangId));
        } else if ($action == 'cookiesPreferencesForm') {
            $this->nodes[] = array('title' => Labels::getLabel('LBL_COOKIE_PREFERENCES', $this->siteLangId));
        } else if ($action == 'changeEmailPassword') {
            $this->nodes[] = array('title' => ucwords(Labels::getLabel('LBL_UPDATE_CREDENTIALS')));
        } else if ($action == 'wishlist') {
            $title = str_replace('-', '_', FatUtility::camel2dashed($action));
            $title = ucwords(Labels::getLabel('BCN_' . $title, $this->siteLangId));
            if (1 > FatApp::getConfig('CONF_ADD_FAVORITES_TO_WISHLIST', FatUtility::VAR_INT, 1)) {
                $title = Labels::getLabel('LBL_FAVORITES');
            }
            $this->nodes[] = array('title' => $title);
        } else {
            $action = str_replace('-', '_', FatUtility::camel2dashed($action));
            $this->nodes[] = array('title' => ucwords(Labels::getLabel('BCN_' . $action, $this->siteLangId)));
        }
        return $this->nodes;
    }

    protected function getChangeEmailUsingPhoneForm2()
    {
        $frm = new Form('changeEmailUsingPhoneFrm');
        $newEmail = $frm->addEmailField(
            Labels::getLabel('FRM_NEW_EMAIL', $this->siteLangId),
            'new_email'
        );
        $newEmail->requirements()->setRequired();

        $conNewEmail = $frm->addEmailField(
            Labels::getLabel('FRM_CONFIRM_NEW_EMAIL', $this->siteLangId),
            'conf_new_email'
        );
        $conNewEmailReq = $conNewEmail->requirements();
        $conNewEmailReq->setRequired();
        $conNewEmailReq->setCompareWith('new_email', 'eq');

        $pwd = $frm->addPasswordField(Labels::getLabel('FRM_NEW_PASSWORD', $this->siteLangId), 'new_password');
        $pwd->requirements()->setRequired();
        $pwd->requirements()->setRegularExpressionToValidate(ValidateElement::PASSWORD_REGEX);
        $pwd->requirements()->setCustomErrorMessage(Labels::getLabel('MSG_PASSWORD_MUST_BE_ATLEAST_EIGHT_CHARACTERS_LONG_AND_ALPHANUMERIC', $this->siteLangId));
        $fld = $frm->addHiddenField('', 'otp');
        $fld->requirements()->setRequired();

        $frm->addSubmitButton('', 'btn_submit', Labels::getLabel('BTN_SAVE', $this->siteLangId));
        return $frm;
    }

    public function redeemGiftCardForm()
    {
        $this->set('frm', $this->getGiftcardRedeemForm($this->siteLangId));
        $this->_template->render(false, false);
    }


    private function getGiftcardRedeemForm($langId)
    {
        $frm = new Form('giftCardReeedem');
        $frm->addTextBox('', 'giftcard_code');
        $frm->addHtml('', 'btn_submit', HtmlHelper::addButtonHtml(Labels::getLabel('BTN_REDEEM', $langId), 'submit', 'btn_submit', 'btn-apply'));
        return $frm;
    }

    public function reedemGiftcard()
    {
        $frm = $this->getGiftcardRedeemForm($this->siteLangId);
        if (!$post = $frm->getFormDataFromArray(FatApp::getPostedData())) {
            FatUtility::dieJsonError(current($frm->getValidationErrors()));
        }
        $giftcard = new GiftCards();
        if (!$giftcard->redeem($post['giftcard_code'], UserAuthentication::getLoggedUserId(), $this->siteLangId)) {
            FatUtility::dieJsonError($giftcard->getError());
        }
        FatUtility::dieJsonSuccess(Labels::getLabel('MSG_GIFTCARD_REDEEMED_SUCCESSFULLY'));
    }

    public function guestActivate()
    {
        if (false == UserAuthentication::isGuestUserLogged()) {
            LibHelper::exitWithError(Labels::getLabel('ERR_INVALID_REQUEST', $this->siteLangId), true);
        }

        $userObj = new User($this->userId);
        $srch = $userObj->getUserSearchObj(array('u.user_name', 'uc.credential_email as user_email'));
        $data = FatApp::getDb()->fetch($srch->getResultSet());
        if (empty($data)) {
            LibHelper::exitWithError(Labels::getLabel('ERR_INVALID_USER', $this->siteLangId), true);
        }
        if (false == $userObj->userEmailVerification($data, $this->siteLangId)) {
            LibHelper::exitWithError($userObj->getError(), true);
        }
        FatUtility::dieJsonSuccess(Labels::getLabel("MSG_VERIFICATION_EMAIL_SENT!", $this->siteLangId));
    }
}
