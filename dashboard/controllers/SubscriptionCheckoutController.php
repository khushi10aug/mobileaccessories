<?php

class SubscriptionCheckoutController extends LoggedUserController
{
    public function __construct($action)
    {
        parent::__construct($action);
        UserAuthentication::subscriptionCheckLogin(true, UrlHelper::generateUrl('GuestUser', 'loginForm'));

        if (!FatApp::getConfig('CONF_ENABLE_SELLER_SUBSCRIPTION_MODULE')) {
            Message::addErrorMessage(Labels::getLabel('ERR_INVALID_REQUEST', $this->siteLangId));
            CommonHelper::redirectUserReferer();
        }

        $this->scartObj = new SubscriptionCart($this->userParentId, $this->siteLangId);
        if (!$this->scartObj->hasSusbscription()) {
            Message::addErrorMessage(Labels::getLabel('ERR_YOUR_CART_SEEMS_TO_BE_EMPTY,_PLEASE_SELECT_VALID_SUBSCRIPTION_PLAN.', $this->siteLangId));
            CommonHelper::redirectUserReferer();
        }

        $this->set('exculdeMainHeaderDiv', true);
    }

    public function index()
    {
        if (!$this->userPrivilege->canEditSubscription(UserAuthentication::getLoggedUserId(), true)) {
            Message::addErrorMessage(Labels::getLabel('ERR_YOU_DO_NOT_HAVE_A_SUFFICIENT_PERMISSION_TO_CHANGE_PLAN', $this->siteLangId));
            FatApp::redirectUser(UrlHelper::generateUrl('seller', 'packages'));
        }

        $obj = new Extrapage();
        $headerData = $obj->getContentByPageType(Extrapage::CHECKOUT_PAGE_HEADER_BLOCK, $this->siteLangId);
        $this->set('headerData', $headerData);
        $this->_template->render(true, false);
    }

    public function getFinancialSummary()
    {
        $cartSummary = $this->scartObj->getSubscriptionCartFinancialSummary($this->siteLangId);

        $orderId = $_SESSION['subscription_shopping_cart']["order_id"] ?? '';
        $this->set('couponsList', DiscountCoupons::getValidSubscriptionCoupons($this->userParentId, $this->siteLangId, '', $orderId));
        $this->set('PromoCouponsFrm', $this->getPromoCouponsForm($this->siteLangId));
        $this->set('cartSummary', $cartSummary);
        $this->set('subscriptions', $this->scartObj->getSubscription($this->siteLangId));

        $this->_template->render(false, false);
    }

    private function getCartSubscriptionInfo($spplan_id)
    {
        $spplan_id = FatUtility::int($spplan_id);
        $prodSrch = new SellerPackagePlansSearch($this->siteLangId);

        $prodSrch->joinPackage();

        $prodSrch->addCondition('spplan_id', '=', $spplan_id);
        $fields = array('spplan_id', 'spplan_price', 'spplan_discount', 'spackage_images_per_product', 'spackage_type', 'spackage_products_allowed', 'spackage_inventory_allowed', 'spackage_rfq_offers_allowed', 'spplan_interval', 'spplan_frequency', 'spackage_commission_rate');
        $prodSrch->addMultipleFields($fields);
        $prodSrch->doNotCalculateRecords();
        $prodSrch->setPageSize(1);
        $rs = $prodSrch->getResultSet();
        return  FatApp::getDb()->fetch($rs);
    }

    private function getSubscriptionCartLangData($spplan_id, $lang_id)
    {
        $langProdSrch = new SellerPackagePlansSearch();
        $langProdSrch->joinPackage($lang_id);
        $langProdSrch->doNotCalculateRecords();
        $langProdSrch->setPageSize(1);
        $langProdSrch->addCondition('spplan_id', '=', $spplan_id);
        $fields = array('IFNULL(spackage_name, spackage_identifier) as spackage_name');
        $langProdSrch->addMultipleFields($fields);
        $langProdRs = $langProdSrch->getResultSet();
        return  FatApp::getDb()->fetch($langProdRs);
    }

    public function paymentSummary()
    {
        $this->userPrivilege->canEditSubscription(UserAuthentication::getLoggedUserId());

        $cartSummary = $this->scartObj->getSubscriptionCartFinancialSummary($this->siteLangId);

        $pmSrch = PaymentMethods::getSearchObject($this->siteLangId);
        $pmSrch->doNotCalculateRecords();
        $pmSrch->doNotLimitRecords();
        $pmSrch->addMultipleFields(Plugin::ATTRS);
        $pmRs = $pmSrch->getResultSet();
        $paymentMethods = FatApp::getDb()->fetchAll($pmRs);

        $orderData = array();
        /* add Order Data[ */
        $order_id = isset($_SESSION['subscription_shopping_cart']["order_id"]) ? $_SESSION['subscription_shopping_cart']["order_id"] : false;

        $userId = $this->userParentId;
        $orderData['order_id'] = $order_id;
        $orderData['order_user_id'] = $userId;
        $orderData['order_payment_status'] = Orders::ORDER_PAYMENT_PENDING;
        $orderData['order_date_added'] = date('Y-m-d H:i:s');
        $orderData['order_type'] = Orders::ORDER_SUBSCRIPTION;
        $orderData['order_renew'] = 0;

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

        if (!empty($cartSummary["cartDiscounts"])) {
            $orderData['order_discount_coupon_code'] = $cartSummary["cartDiscounts"]["coupon_code"];
            $orderData['order_discount_type'] = $cartSummary["cartDiscounts"]["coupon_discount_type"];
            $orderData['order_discount_value'] = $cartSummary["cartDiscounts"]["coupon_discount_value"];
            $orderData['order_discount_total'] = $cartSummary["cartDiscounts"]["coupon_discount_total"];
            $orderData['order_discount_info'] = $cartSummary["cartDiscounts"]["coupon_info"];
        } else {
            $orderData['order_discount_coupon_code'] = '';
            $orderData['order_discount_type'] = '';
            $orderData['order_discount_value'] = '';
            $orderData['order_discount_total'] = '';
            $orderData['order_discount_info'] = '';
        }

        $orderData['order_reward_point_used'] = $cartSummary["cartRewardPoints"];
        $orderData['order_reward_point_value'] = CommonHelper::convertRewardPointToCurrency($cartSummary["cartRewardPoints"]);

        $orderData['order_net_amount'] = $cartSummary["orderNetAmount"];
        $orderData['order_wallet_amount_charge'] = $cartSummary["WalletAmountCharge"];

        $orderData['order_cart_data'] = SubscriptionCart::getSubscriptionCartData();

        $allLanguages = Language::getAllNames();

        $orderLangData = array();

        $orderData['orderLangData'] = $orderLangData;

        /* order products[ */
        $cartSubscription = $this->scartObj->getSubscription($this->siteLangId);

        $orderData['subscriptions'] = array();
        $orderData['subscrCharges'] = array();
        $subscriptionType = '';
        if ($cartSubscription) {
            foreach ($cartSubscription as $cartSubscription) {
                $subscriptionInfo = $this->getCartSubscriptionInfo($cartSubscription['spplan_id']);
                if (!$subscriptionInfo) {
                    continue;
                }
                $subscriptionLangData = array();
                foreach ($allLanguages as $lang_id => $language_name) {
                    $langSpecificsubscriptionInfo = $this->getSubscriptionCartLangData($subscriptionInfo['spplan_id'], $lang_id);
                    if (!$langSpecificsubscriptionInfo) {
                        continue;
                    }
                    $op_subscription_title = (!empty($langSpecificsubscriptionInfo['spackage_name']) ? $langSpecificsubscriptionInfo['spackage_name'] : '');
                    $subscriptionLangData[$lang_id] = array(
                        OrderSubscription::DB_TBL_LANG_PREFIX . 'lang_id' => $lang_id,
                        'ossubs_subscription_name' => $op_subscription_title,
                    );
                }
                $planPayable = SellerPackagePlans::getPlanPayableAmountFromRow($subscriptionInfo);
                $orderData['subscriptions'][SUBSCRIPTIONCART::SUBSCRIPTION_CART_KEY_PREFIX_PRODUCT . $subscriptionInfo['spplan_id']] = array(
                    OrderSubscription::DB_TBL_PREFIX . 'price' => $planPayable,
                    OrderSubscription::DB_TBL_PREFIX . 'images_allowed' => $subscriptionInfo['spackage_images_per_product'],
                    OrderSubscription::DB_TBL_PREFIX . 'products_allowed' => $subscriptionInfo['spackage_products_allowed'],
                    OrderSubscription::DB_TBL_PREFIX . 'inventory_allowed' => $subscriptionInfo['spackage_inventory_allowed'],
                    OrderSubscription::DB_TBL_PREFIX . 'rfq_offers_allowed' => $subscriptionInfo['spackage_rfq_offers_allowed'],
                    OrderSubscription::DB_TBL_PREFIX . 'type' => $subscriptionInfo['spackage_type'],
                    OrderSubscription::DB_TBL_PREFIX . 'plan_id' => $subscriptionInfo['spplan_id'],
                    OrderSubscription::DB_TBL_PREFIX . 'interval' => $subscriptionInfo['spplan_interval'],
                    OrderSubscription::DB_TBL_PREFIX . 'frequency' => $subscriptionInfo['spplan_frequency'],
                    OrderSubscription::DB_TBL_PREFIX . 'commission' => $subscriptionInfo['spackage_commission_rate'],
                    OrderSubscription::DB_TBL_PREFIX . 'status_id' => FatApp::getConfig("CONF_DEFAULT_SUBSCRIPTION_ORDER_STATUS"),
                    'subscriptionsLangData' => $subscriptionLangData,
                );
                $subscriptionType = $subscriptionInfo['spackage_type'];
                $adjustedAmount = 0;
                if (FatApp::getConfig('CONF_ENABLE_ADJUST_AMOUNT_CHANGE_PLAN')) {
                    $adjustedAmount = $cartSummary["cartAdjustableAmount"];
                }

                $discount = 0;
                if (!empty($cartSummary["cartDiscounts"]["discountedSelProdIds"])) {
                    if (array_key_exists($subscriptionInfo['spplan_id'], $cartSummary["cartDiscounts"]["discountedSelProdIds"])) {
                        $discount = $cartSummary["cartDiscounts"]["discountedSelProdIds"][$subscriptionInfo['spplan_id']];
                    }
                }

                $rewardPoints = $orderData['order_reward_point_value'];
                $usedRewardPoint = 0;
                if ($rewardPoints > 0) {
                    $planPayable = isset($cartSubscription['spplan_payable_price']) ? $cartSubscription['spplan_payable_price'] : SellerPackagePlans::getPlanPayableAmountFromRow($cartSubscription);
                    $selProdAmount = ($planPayable) - $discount - $adjustedAmount;
                    $usedRewardPoint = round((($rewardPoints * $selProdAmount) / ($orderData['order_net_amount'] + $rewardPoints)), 2);
                }

                $orderData['subscrCharges'][SubscriptionCart::SUBSCRIPTION_CART_KEY_PREFIX_PRODUCT . $subscriptionInfo['spplan_id']] = array(

                    OrderProduct::CHARGE_TYPE_DISCOUNT => array(
                        'amount' => -$discount /*[Should be negative value]*/
                    ),

                    OrderProduct::CHARGE_TYPE_REWARD_POINT_DISCOUNT => array(
                        'amount' => -$usedRewardPoint /*[Should be negative value]*/
                    ),
                    OrderProduct::CHARGE_TYPE_ADJUST_SUBSCRIPTION_PRICE => array(
                        'amount' => -$adjustedAmount /*[Should be negative value]*/
                    ),
                );
                /* [ Add order Type[ */
                $orderData['order_type'] = Orders::ORDER_SUBSCRIPTION;
                /* ] */
            }
        }
        /* ] */
        /* ] */

        $orderObj = new Orders();
        if ($orderObj->addUpdateOrder($orderData, $this->siteLangId)) {
            $order_id = $orderObj->getOrderId();
        } else {
            Message::addErrorMessage($orderObj->getError());
            FatUtility::dieWithError(Message::getHtml());
        }

        $srch = Orders::getSearchObject();
        $srch->doNotCalculateRecords();
        $srch->doNotLimitRecords();
        $srch->addCondition('order_id', '=', $order_id);
        $srch->addCondition('order_type', '=', Orders::ORDER_SUBSCRIPTION);
        $srch->addCondition('order_payment_status', '=', Orders::ORDER_PAYMENT_PENDING);
        $rs = $srch->getResultSet();
        $orderInfo = FatApp::getDb()->fetch($rs);
        if (!$orderInfo) {
            $this->scartObj->clear();
            FatApp::redirectUser(UrlHelper::generateUrl('Seller', 'viewOrder', array($order_id)));
        }
        $walletPaymentForm = $this->getWalletPaymentForm($this->siteLangId);
        $confirmPaymentFrm = $this->getConfirmPaymentForm($this->siteLangId);
        $userWalletBalance = User::getUserBalance($userId);

        if ($userWalletBalance >= $cartSummary['orderNetAmount'] && $cartSummary['cartWalletSelected']) {
            $walletPaymentForm->addFormTagAttribute('action', UrlHelper::generateUrl('WalletPay', 'Charge', array($order_id), CONF_WEBROOT_FRONTEND));
            $walletPaymentForm->fill(array('order_id' => $order_id));
            $walletPaymentForm->setFormTagAttribute('onsubmit', 'confirmOrder(this); return(false);');
            $walletPaymentForm->addSubmitButton('', 'btn_submit', Labels::getLabel('BTN_PAY_NOW', $this->siteLangId));
            $orderObj->updateOrderInfo($order_id, array('order_pmethod_id' => 0));
        }

        if ($cartSummary['orderNetAmount'] == 0 || $cartSummary['orderNetAmount'] == 0) {
            $confirmPaymentFrm->addFormTagAttribute('action', UrlHelper::generateUrl('ConfirmPay', 'Charge', array($order_id), CONF_WEBROOT_FRONTEND));
            $confirmPaymentFrm->fill(array('order_id' => $order_id));
            $confirmPaymentFrm->addSubmitButton('', 'btn_submit', Labels::getLabel('BTN_CONFIRM', $this->siteLangId));
        }
        $excludePaymentGatewaysArr = applicationConstants::getExcludePaymentGatewayArr();

        $redeemRewardFrm = $this->getRewardsForm($this->siteLangId);

        $this->set('canUseWalletForPayment', PaymentMethods::canUseWalletForPayment());
        $this->set('userWalletBalance', $userWalletBalance);
        $this->set('subscriptionType', $subscriptionType);
        $this->set('redeemRewardFrm', $redeemRewardFrm);
        $this->set('paymentMethods', $paymentMethods);
        $this->set('excludePaymentGatewaysArr', $excludePaymentGatewaysArr);
        $this->set('cartSummary', $cartSummary);
        $this->set('orderInfo', $orderInfo);
        $this->set('walletPaymentForm', $walletPaymentForm);
        $this->set('confirmPaymentFrm', $confirmPaymentFrm);
        $this->_template->render(false, false);
    }

    public function paymentTab($order_id, $plugin_id)
    {
        $this->userPrivilege->canEditSubscription(UserAuthentication::getLoggedUserId());
        $plugin_id = FatUtility::int($plugin_id);
        if (!$plugin_id) {
            LibHelper::exitWithError(Labels::getLabel("ERR_INVALID_REQUEST!", $this->siteLangId));
        }

        if (!UserAuthentication::isUserLogged()) {
            LibHelper::exitWithError(Labels::getLabel('ERR_YOUR_SESSION_SEEMS_TO_BE_EXPIRED.', $this->siteLangId));
        }


        $srch = Orders::getSearchObject();
        $srch->doNotCalculateRecords();
        $srch->doNotLimitRecords();
        $srch->addCondition('order_id', '=', $order_id);
        $srch->addCondition('order_payment_status', '=', Orders::ORDER_PAYMENT_PENDING);
        $rs = $srch->getResultSet();
        $orderInfo = FatApp::getDb()->fetch($rs);
        if (!$orderInfo) {
            LibHelper::exitWithError(Labels::getLabel('ERR_INVALID_ORDER_PAID_CANCELLED', $this->siteLangId));
        }

        $pmSrch = PaymentMethods::getSearchObject($this->siteLangId);
        $pmSrch->doNotCalculateRecords();
        $pmSrch->setPageSize(1);
        $pmSrch->addMultipleFields(Plugin::ATTRS);
        $pmSrch->addCondition('plugin_id', '=', $plugin_id);
        $pmRs = $pmSrch->getResultSet();
        $paymentMethod = FatApp::getDb()->fetch($pmRs);
        if (!$paymentMethod) {
            LibHelper::exitWithError(Labels::getLabel("ERR_SELECTED_PAYMENT_METHOD_NOT_FOUND!", $this->siteLangId));
        }

        $frm = $this->getPaymentTabForm($this->siteLangId, $paymentMethod['plugin_code']);
        $controller = $paymentMethod['plugin_code'] . 'Pay';
        $methodCode = Plugin::getAttributesById($plugin_id, 'plugin_code');
        $frm->setFormTagAttribute('data-method', $methodCode);
        $frm->setFormTagAttribute('data-external', UrlHelper::generateUrl($controller, 'getExternalLibraries', [], CONF_WEBROOT_FRONTEND));

        $frm->setFormTagAttribute('action', UrlHelper::generateUrl($controller, 'charge', array($orderInfo['order_id']), CONF_WEBROOT_FRONTEND));
        $frm->fill(
            array(
                'order_id' => $order_id,
                'plugin_id' => $plugin_id
            )
        );


        $this->set('paymentMethod', $paymentMethod);
        $this->set('frm', $frm);

        $this->set('html', $this->_template->render(false, false, NULL, true));
        $this->_template->render(false, false, 'json-success.php', true, false);
    }

    public function walletSelection()
    {
        $this->userPrivilege->canEditSubscription(UserAuthentication::getLoggedUserId());
        $post = FatApp::getPostedData();
        $payFromWallet = $post['payFromWallet'];
        $this->scartObj->updateCartWalletOption($payFromWallet);
        $this->_template->render(false, false, 'json-success.php');
    }

    public function useRewardPoints()
    {
        $this->userPrivilege->canEditSubscription(UserAuthentication::getLoggedUserId());
        $post = FatApp::getPostedData();

        if (false == $post) {
            Message::addErrorMessage(Labels::getLabel('LBL_INVALID_REQUEST', $this->siteLangId));
            FatUtility::dieWithError(Message::getHtml());
        }

        if (empty($post['redeem_rewards'])) {
            Message::addErrorMessage(Labels::getLabel('LBL_INVALID_REQUEST', $this->siteLangId));
            FatUtility::dieWithError(Message::getHtml());
        }

        $rewardPoints = $post['redeem_rewards'];
        $totalBalance = UserRewardBreakup::rewardPointBalance($this->userParentId);
        /* var_dump($totalBalance);exit; */
        if ($totalBalance == 0 || $totalBalance < $rewardPoints) {
            FatUtility::dieJsonError(Labels::getLabel('ERR_INSUFFICIENT_REWARD_POINT_BALANCE', $this->siteLangId));
        }

        $scartObj = new SubscriptionCart();
        $cartSummary = $scartObj->getSubscriptionCartFinancialSummary($this->siteLangId);
        $rewardPointValues = min(CommonHelper::convertRewardPointToCurrency($rewardPoints), $cartSummary['orderNetAmount']);
        $rewardPoints = CommonHelper::convertCurrencyToRewardPoint($rewardPointValues);

        if ($rewardPoints < FatApp::getConfig('CONF_MIN_REWARD_POINT') || $rewardPoints > FatApp::getConfig('CONF_MAX_REWARD_POINT')) {
            $msg = Labels::getLabel('ERR_PLEASE_USE_REWARD_POINT_BETWEEN_{MIN}_to_{MAX}', $this->siteLangId);
            $msg = str_replace('{MIN}', FatApp::getConfig('CONF_MIN_REWARD_POINT'), $msg);
            $msg = str_replace('{MAX}', FatApp::getConfig('CONF_MAX_REWARD_POINT'), $msg);
            FatUtility::dieJsonError($msg);
        }

        if (!$scartObj->updateCartUseRewardPoints($rewardPoints)) {
            Message::addErrorMessage(Labels::getLabel('LBL_ACTION_TRYING_PERFORM_NOT_VALID', $this->siteLangId));
            FatUtility::dieWithError(Message::getHtml());
        }

        $this->set('msg', Labels::getLabel("MSG_Used_Reward_point", $this->siteLangId) . '-' . $rewardPoints);
        $this->_template->render(false, false, 'json-success.php');
    }

    public function removeRewardPoints()
    {
        $this->userPrivilege->canEditSubscription(UserAuthentication::getLoggedUserId());
        $scartObj = new SubscriptionCart();
        if (!$scartObj->removeUsedRewardPoints()) {
            Message::addErrorMessage(Labels::getLabel('LBL_ACTION_TRYING_PERFORM_NOT_VALID', $this->siteLangId));
            FatUtility::dieWithError(Message::getHtml());
        }

        $this->set('msg', Labels::getLabel("MSG_used_reward_point_removed", $this->siteLangId));
        $this->_template->render(false, false, 'json-success.php');
    }

    public function confirmOrder()
    {
        $this->userPrivilege->canEditSubscription(UserAuthentication::getLoggedUserId());
        $user_id = $this->userParentId;
        $cartSummary = $this->scartObj->getSubscriptionCartFinancialSummary($this->siteLangId);

        $userWalletBalance = User::getUserBalance($user_id);

        $post = FatApp::getPostedData();
        $plugin_id = FatApp::getPostedData('plugin_id', FatUtility::VAR_INT, 0);


        if ($userWalletBalance >= $cartSummary['orderNetAmount'] && $cartSummary['cartWalletSelected'] && !$plugin_id) {
            $frm = $this->getWalletPaymentForm($this->siteLangId);
        } else {
            $frm = $this->getPaymentTabForm($this->siteLangId);
        }

        $post = $frm->getFormDataFromArray($post);
        if (!isset($post['order_id']) || $post['order_id'] == '') {
            LibHelper::dieJsonError(Labels::getLabel('ERR_INVALID_REQUEST', $this->siteLangId));
        }
        $orderObj = new Orders();
        $order_id = $post['order_id'];

        $srch = Orders::getSearchObject();
        $srch->doNotCalculateRecords();
        $srch->setPageSize(1);
        $srch->addCondition('order_id', '=', $order_id);
        $srch->addCondition('order_user_id', '=', $user_id);
        $srch->addCondition('order_type', '=', Orders::ORDER_SUBSCRIPTION);
        $srch->addCondition('order_payment_status', '=', Orders::ORDER_PAYMENT_PENDING);
        $rs = $srch->getResultSet();
        $orderInfo = FatApp::getDb()->fetch($rs);
        if (!$orderInfo) {
            LibHelper::dieJsonError(Labels::getLabel('ERR_INVALID_ORDER_PAID_CANCELLED', $this->siteLangId));
        }


        if ($cartSummary['orderPaymentGatewayCharges'] == 0 && $plugin_id) {
            LibHelper::dieJsonError(Labels::getLabel('ERR_AMOUNT_FOR_PAYMENT_GATEWAY_MUST_BE_GREATER_THAN_ZERO.', $this->siteLangId));
        }

        if ($cartSummary['cartWalletSelected'] && $userWalletBalance >= $cartSummary['orderNetAmount'] && !$plugin_id) {
            $this->_template->render(false, false, 'json-success.php');
            exit;
        }
        if ($cartSummary['orderPaymentGatewayCharges'] == 0) {
            $this->_template->render(false, false, 'json-success.php');
            exit;
        }

        $paymentMethodRow = Plugin::getAttributesById($plugin_id);

        if (!$paymentMethodRow || $paymentMethodRow['plugin_active'] != Plugin::ACTIVE && $cartSummary['orderPaymentGatewayCharges'] > 0) {
            LibHelper::dieJsonError(Labels::getLabel("LBL_Invalid_Payment_method,_Please_contact_Webadmin.", $this->siteLangId));
        }


        if ($cartSummary['cartWalletSelected'] && $cartSummary['orderPaymentGatewayCharges'] == 0) {
            LibHelper::dieJsonError(Labels::getLabel('ERR_TRY_TO_PAY_USING_WALLET_BALANCE_AS_AMOUNT_FOR_PAYMENT_GATEWAY_IS_NOT_ENOUGH.', $this->siteLangId));
        }


        $_SESSION['order_type'] = Orders::ORDER_SUBSCRIPTION;
        $orderObj->updateOrderInfo($order_id, array('order_pmethod_id' => $plugin_id));
        $this->_template->render(false, false, 'json-success.php');
    }

    private function getPaymentTabForm($langId, $paymentMethodCode = '')
    {
        $frm = new Form('frmPaymentTabForm');
        $frm->setFormTagAttribute('id', 'frmPaymentTabForm');

        if (in_array(strtolower($paymentMethodCode), ["cashondelivery", "payatstore"])) {
            CommonHelper::addCaptchaField($frm);
        }

        $frm->addSubmitButton('', 'btn_submit', Labels::getLabel('BTN_CONFIRM_PAYMENT', $langId));
        $frm->addHiddenField('', 'order_id');
        $frm->addHiddenField('', 'plugin_id');
        return $frm;
    }

    private function getWalletPaymentForm($langId)
    {
        $frm = new Form('frmWalletPayment');
        $frm->addHiddenField('', 'order_id');
        return $frm;
    }
    private function getConfirmPaymentForm($langId)
    {
        $frm = new Form('frmConfirmPayment');
        $frm->addHiddenField('', 'order_id');
        return $frm;
    }

    private function getRewardsForm($langId)
    {
        $langId = FatUtility::int($langId);
        $frm = new Form('frmRewards');
        $frm->addTextBox(Labels::getLabel('FRM_REWARD_POINTS', $langId), 'redeem_rewards', '', array('placeholder' => Labels::getLabel('LBL_Use_Reward_Point', $langId)));
        $frm->addSubmitButton('', 'btn_submit', Labels::getLabel('BTN_APPLY', $langId));
        return $frm;
    }

    public function getCoupons()
    {
        $orderId = $_SESSION['subscription_shopping_cart']["order_id"] ?? '';
        $this->set('couponsList', DiscountCoupons::getValidSubscriptionCoupons($this->userParentId, $this->siteLangId, '', $orderId));
        
        $this->set('html', $this->_template->render(false, false, NULL, true));
        $this->_template->render(false, false, 'json-success.php', true, false);
    }

    private function getPromoCouponsForm($langId)
    {
        $langId = FatUtility::int($langId);
        $frm = new Form('frmPromoCoupons');
        $frm->addTextBox(Labels::getLabel('FRM_COUPON_CODE', $langId), 'coupon_code', '', array('placeholder' => Labels::getLabel('FRM_ENTER_YOUR_CODE', $langId)));

        $frm->addHtml('', 'btn_submit', HtmlHelper::addButtonHtml(Labels::getLabel('BTN_APPLY', $langId), 'submit', 'btn_submit', 'btn-apply'));
        return $frm;
    }

    public function applyPromoCode()
    {
        $this->userPrivilege->canEditSubscription(UserAuthentication::getLoggedUserId());
        UserAuthentication::checkLogin();

        $post = FatApp::getPostedData();
        if (empty($post['coupon_code'])) {
            FatUtility::dieWithError(Labels::getLabel('ERR_PLEASE_ENTER_VALID_COUPON_CODE', $this->siteLangId));
        }

        $couponCode = $post['coupon_code'];

        $orderId = isset($_SESSION['subscription_shopping_cart']["order_id"]) ? $_SESSION['subscription_shopping_cart']["order_id"] : '';
        $couponInfo = DiscountCoupons::getValidSubscriptionCoupons(UserAuthentication::getLoggedUserId(), $this->siteLangId, $couponCode, $orderId);

        if ($couponInfo == false) {
            FatUtility::dieWithError(Labels::getLabel('LBL_Invalid_Coupon_Code', $this->siteLangId));
        }

        $cartObj = new SubscriptionCart();
        if (!$cartObj->updateCartDiscountCoupon($couponInfo['coupon_code'])) {
            FatUtility::dieWithError(Labels::getLabel('LBL_Action_Trying_Perform_Not_Valid', $this->siteLangId));
        }

        $holdCouponData = array(
            'couponhold_coupon_id' => $couponInfo['coupon_id'],
            'couponhold_user_id' => $this->userParentId,
            'couponhold_added_on' => date('Y-m-d H:i:s'),
        );

        if (!FatApp::getDb()->insertFromArray(DiscountCoupons::DB_TBL_COUPON_HOLD, $holdCouponData, true, array(), $holdCouponData)) {
            FatUtility::dieWithError(Labels::getLabel('LBL_ACTION_TRYING_PERFORM_NOT_VALID', $this->siteLangId));
        }

        $this->_template->render(false, false, 'json-success.php');
    }

    public function removePromoCode()
    {
        $this->userPrivilege->canEditSubscription(UserAuthentication::getLoggedUserId());
        $scartObj = new SubscriptionCart();
        if (!$scartObj->removeCartDiscountCoupon()) {
            Message::addErrorMessage(Labels::getLabel('LBL_ACTION_TRYING_PERFORM_NOT_VALID', $this->siteLangId));
            FatUtility::dieWithError(Message::getHtml());
        }

        $this->set('msg', Labels::getLabel("MSG_cart_discount_coupon_removed", $this->siteLangId));
        $this->_template->render(false, false, 'json-success.php');
    }
    public function paymentBlankDiv()
    {
        $this->userPrivilege->canEditSubscription(UserAuthentication::getLoggedUserId());
        $this->_template->render(false, false);
    }

    public function renewSubscriptionOrder($ossubs_id = 0)
    {
        if (!$this->userPrivilege->canEditSubscription(UserAuthentication::getLoggedUserId(), true)) {
            Message::addErrorMessage(Labels::getLabel('LBL_UNAUTHORIZED_ACCESS!', $this->siteLangId));
            FatApp::redirectUser(UrlHelper::generateUrl('Seller', 'subscriptions'));
        }
        $statusArr = Orders::getActiveSubscriptionStatusArr();
        // $endDate = date("Y-m-d");
        $srch = new OrderSubscriptionSearch();
        $srch->joinOrders();
        $srch->joinOrderUser();
        $srch->addCondition('order_payment_status', '=', ORDERS::ORDER_PAYMENT_PAID);
        $srch->addCondition('ossubs_status_id', 'in', $statusArr);
        $srch->addCondition('ossubs_id', '=', $ossubs_id);
        $srch->addCondition('ossubs_type', '=', SellerPackages::PAID_TYPE);
        $srch->addCondition('order_user_id', '=', $this->userParentId);
        // $srch->addCondition('ossubs_till_date', '<=', $endDate);
        $srch->addCondition('ossubs_till_date', '!=', '0000-00-00');
        //$srch->addCondition('user_autorenew_subscription', '!=', 1);
        $srch->addMultipleFields(array('order_user_id', 'order_id', 'order_number', 'ossubs_id', 'ossubs_type', 'ossubs_price', 'ossubs_images_allowed', 'ossubs_products_allowed', 'ossubs_inventory_allowed', 'ossubs_rfq_offers_allowed', 'ossubs_plan_id', 'ossubs_interval', 'ossubs_frequency', 'ossubs_commission'));  
        $srch->addOrder('ossubs_id', 'desc');
        $srch->doNotCalculateRecords();
        $srch->setPageSize(1);     
        $activeSub = (array) FatApp::getDb()->fetch($srch->getResultSet(), 'ossubs_id');

        if (empty($activeSub) && count($activeSub) == 0) {
            Message::addErrorMessage(Labels::getLabel("ERR_Subscription_is_not_active", $this->siteLangId));
            FatApp::redirectUser(UrlHelper::generateUrl('Seller', 'subscriptions'));
        }

        $userId = $activeSub['order_user_id'];
        $userBalance = User::getUserBalance($userId);

        if ($userBalance < $activeSub['ossubs_price']) {
            $low_bal_msg = str_replace("{clickhere}", '<a href="' . UrlHelper::generateUrl('account', 'credits') . '">' . Labels::getLabel('LBL_Click_Here', $this->siteLangId) . '</a>', Labels::getLabel('MSG_Please_Maintain_your_wallet_balance_to_renew_subscription_{clickhere}', $this->siteLangId));

            Message::addErrorMessage($low_bal_msg);
            FatApp::redirectUser(UrlHelper::generateUrl('Seller', 'subscriptions'));
        }

        $orderData = array();
        /* add Order Data[ */
        $order_id = 0;
        $orderData['order_id'] = $order_id;
        $orderData['order_number'] = false;
        $orderData['order_user_id'] = $userId;
        /* $orderData['order_user_name'] = $userDataArr['user_name'];
        $orderData['order_user_email'] = $userDataArr['credential_email'];
        $orderData['order_user_phone'] = $userDataArr['user_phone']; */
        $orderData['order_payment_status'] = Orders::ORDER_PAYMENT_PENDING;
        $orderData['order_date_added'] = date('Y-m-d H:i:s');
        $orderData['order_type'] = Orders::ORDER_SUBSCRIPTION;

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
        $orderData['order_reward_point_used'] = 0;
        $orderData['order_reward_point_value'] = 0;
        $orderData['order_net_amount'] = $activeSub['ossubs_price'];
        $orderData['order_wallet_amount_charge'] = $activeSub['ossubs_price'];

        // Discussin Required
        $orderData['order_cart_data'] = '';

        $allLanguages = Language::getAllNames();   

        $orderLangData = array();
        $orderData['orderLangData'] = $orderLangData;

        $subscriptionLangData = array();
        foreach ($allLanguages as $lang_id => $language_name) {
            $subscriptionInfo = OrderSubscription::getAttributesByLangId($lang_id, $activeSub['ossubs_id']);
            $op_subscription_title = $subscriptionInfo['ossubs_subscription_name'];
            $subscriptionLangData[$lang_id] = array(
                'ossubslang_lang_id' => $lang_id,
                'ossubs_subscription_name' => $op_subscription_title,
            );
        }

        $orderData['subscriptions'][SubscriptionCart::SUBSCRIPTION_CART_KEY_PREFIX_PRODUCT . $activeSub['ossubs_plan_id']] = array(
            OrderSubscription::DB_TBL_PREFIX . 'price' => $activeSub['ossubs_price'],
            OrderSubscription::DB_TBL_PREFIX . 'images_allowed' => $activeSub['ossubs_images_allowed'],
            OrderSubscription::DB_TBL_PREFIX . 'products_allowed' => $activeSub['ossubs_products_allowed'],
            OrderSubscription::DB_TBL_PREFIX . 'inventory_allowed' => $activeSub['ossubs_inventory_allowed'],
            OrderSubscription::DB_TBL_PREFIX . 'rfq_offers_allowed' => $activeSub['ossubs_rfq_offers_allowed'],
            OrderSubscription::DB_TBL_PREFIX . 'plan_id' => $activeSub['ossubs_plan_id'],
            OrderSubscription::DB_TBL_PREFIX . 'type' => $activeSub['ossubs_type'],
            OrderSubscription::DB_TBL_PREFIX . 'interval' => $activeSub['ossubs_interval'],
            OrderSubscription::DB_TBL_PREFIX . 'frequency' => $activeSub['ossubs_frequency'],
            OrderSubscription::DB_TBL_PREFIX . 'commission' => $activeSub['ossubs_commission'],
            OrderSubscription::DB_TBL_PREFIX . 'status_id' => FatApp::getConfig("CONF_DEFAULT_ORDER_STATUS"),
            'subscriptionsLangData' => $subscriptionLangData,
        );
       
        $orderData['subscrCharges'][SubscriptionCart::SUBSCRIPTION_CART_KEY_PREFIX_PRODUCT . $activeSub['ossubs_plan_id']] = array(

            OrderProduct::CHARGE_TYPE_DISCOUNT => array(
                'amount' => 0 /*[Should be negative value]*/
            ),

            OrderProduct::CHARGE_TYPE_REWARD_POINT_DISCOUNT => array(
                'amount' => 0 /*[Should be negative value]*/
            ),
            OrderProduct::CHARGE_TYPE_ADJUST_SUBSCRIPTION_PRICE => array(
                'amount' => 0 /*[Should be negative value]*/
            ),
        );
        /* [ Add order Type[ */
        $orderData['order_type'] = Orders::ORDER_SUBSCRIPTION;
        $orderData['order_renew'] = 1;
   
        $orderObj = new Orders();
        if ($orderObj->addUpdateOrder($orderData, $this->siteLangId)) {
            $order_id = $orderObj->getOrderId();

            $orderPaymentObj = new OrderPayment($order_id);
            if ($orderPaymentObj->chargeUserWallet($activeSub['ossubs_price'])) {
                Message::addMessage(Labels::getLabel("MSG_Subscription_Successfully_renewed", $this->siteLangId));
                FatApp::redirectUser(UrlHelper::generateUrl('Seller', 'subscriptions'));
            }
        }
        Message::addErrorMessage($orderObj->getError());
        FatApp::redirectUser(UrlHelper::generateUrl('Seller', 'subscriptions'));
    }
}
