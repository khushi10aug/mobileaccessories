<?php
class CheckoutController extends MyAppController
{
    private $cartObj;
    private $errMessage;

    public function __construct($action)
    {   
        parent::__construct($action);
        UserAuthentication::checkLogin(true, UrlHelper::generateUrl('Cart'));        
        if (FatApp::getConfig('CONF_ENABLE_GEO_LOCATION', FatUtility::VAR_INT, 0) && !empty(FatApp::getConfig('CONF_GOOGLEMAP_API_KEY', FatUtility::VAR_STRING, '')) && !in_array($action, ['confirmOrder','giftCharge', 'walletGiftSelection', 'paymentTab'])) {
            $geoAddress = Address::getYkGeoData();
            if (!array_key_exists('ykGeoLat', $geoAddress) || $geoAddress['ykGeoLat'] == '' || !array_key_exists('ykGeoLng', $geoAddress) || $geoAddress['ykGeoLng'] == '') {
                $this->errMessage = Labels::getLabel('ERR_PLEASE_CONFIGURE_YOUR_LOCATION', $this->siteLangId);
                LibHelper::exitWithError($this->errMessage, false, true);
                FatApp::redirectUser(UrlHelper::generateUrl('Cart'));
            }
        }

        if (UserAuthentication::isGuestUserLogged()) {
            $user_is_buyer = User::getAttributesById(UserAuthentication::getLoggedUserId(), 'user_is_buyer');
            if (!$user_is_buyer) {
                $this->errMessage = Labels::getLabel('ERR_PLEASE_LOGIN_WITH_BUYER_ACCOUNT', $this->siteLangId);
                Message::addErrorMessage($this->errMessage);
                if (FatUtility::isAjaxCall()) {
                    FatUtility::dieWithError(Message::getHtml());
                }
                FatApp::redirectUser(UrlHelper::generateUrl('Cart'));
            }
        } else {
            $userObj = new User(UserAuthentication::getLoggedUserId());
            $userInfo = $userObj->getUserInfo(array(), false, false);
            if (empty($userInfo['user_phone']) && empty($userInfo['credential_email'])) {
                if (true == SmsArchive::canSendSms()) {
                    $message = Labels::getLabel('ERR_PLEASE_CONFIGURE_YOUR_EMAIL_OR_PHONE', $this->siteLangId);
                } else {
                    $message = Labels::getLabel('ERR_PLEASE_CONFIGURE_YOUR_EMAIL', $this->siteLangId);
                }
                if (true === MOBILE_APP_API_CALL) {
                    LibHelper::dieJsonError($message);
                }

                if (FatUtility::isAjaxCall()) {
                    $json['status'] = applicationConstants::NO;
                    $json['msg'] = $message;
                    $json['url'] = UrlHelper::generateUrl('GuestUser', 'configureEmail', [], CONF_WEBROOT_FRONTEND);
                    LibHelper::dieJsonError($json);
                }
                Message::addErrorMessage($message);
                FatApp::redirectUser(UrlHelper::generateUrl('GuestUser', 'configureEmail', [], CONF_WEBROOT_FRONTEND));
            }
        }


        $this->cartObj = new Cart(UserAuthentication::getLoggedUserId(), $this->siteLangId, $this->app_user['temp_user_id']);
        if (1 > $this->cartObj->getCartBillingAddress()) {
            $this->cartObj->setCartBillingAddress();
        }

        if ($this->cartObj->hasPhysicalProduct() && 1 > $this->cartObj->getCartShippingAddress()) {
            $this->cartObj->setShippingAddressSameAsBilling();
        }

        $orderType = FatApp::getPostedData('order_type', FatUtility::VAR_INT, 0);

        $user_is_buyer = User::getAttributesById(UserAuthentication::getLoggedUserId(), 'user_is_buyer');
        if (!$user_is_buyer && Orders::ORDER_PRODUCT == $orderType) {
            $this->cartObj->clear(true);
            $this->cartObj->updateUserCart();
            $errMsg = Labels::getLabel('ERR_PLEASE_LOGIN_WITH_BUYER_ACCOUNT_TO_PROCEED_AHEAD', $this->siteLangId);
            LibHelper::exitWithError($errMsg, false, true);
            FatApp::redirectUser(UrlHelper::generateUrl());
        }

        $this->set('exculdeMainHeaderDiv', true);
    }

    private function isEligibleForNextStep(&$criteria = array(), bool $addErrorMessage = true)
    {
        if (empty($criteria)) {
            return true;
        }
        foreach ($criteria as $key => $val) {
            switch ($key) {
                case 'isUserLogged':
                    if (!UserAuthentication::isUserLogged() && !UserAuthentication::isGuestUserLogged()) {
                        $key = false;
                        $this->errMessage = Labels::getLabel('ERR_YOUR_SESSION_SEEMS_TO_BE_EXPIRED.', $this->siteLangId);
                        if (true === $addErrorMessage) {
                            Message::addErrorMessage($this->errMessage);
                        }
                        return false;
                    }
                    break;
                case 'hasProducts':
                    if (!$this->cartObj->hasProducts()) {
                        $key = false;
                        $this->errMessage = Labels::getLabel('ERR_YOUR_CART_SEEMS_TO_BE_EMPTY,_PLEASE_TRY_AFTER_RELOADING_THE_PAGE.', $this->siteLangId);
                        if (true === $addErrorMessage) {
                            Message::addErrorMessage($this->errMessage);
                        }
                        return false;
                    }
                    break;
                case 'hasStock':
                    /* to check that product is temporary hold[ */
                    $cart_user_id = Cart::getCartUserId();
                    $intervalInMinutes = FatApp::getConfig('cart_stock_hold_minutes', FatUtility::VAR_INT, 15);
                    //$srch->addCondition('pshold_user_id', '!=', $cart_user_id);

                    /* ] */

                    $cartProducts = $this->cartObj->getBasketProducts($this->siteLangId);
                    foreach ($cartProducts as $product) {
                        if (!$product['in_stock'] && !isset($_SESSION['offer_checkout'])) {
                            $stock = false;
                            $key = false;
                            $this->errMessage = Labels::getLabel('ERR_PRODUCTS_ARE_OUT_OF_STOCK.', $this->siteLangId);
                            if (true === $addErrorMessage) {
                                Message::addErrorMessage($this->errMessage);
                            }
                            return false;
                            break;
                        }

                        if ($product['is_batch'] && !empty($product['products'])) {
                            foreach ($product['products'] as $pgproduct) {
                                $tempHoldStock = Product::tempHoldStockCount($pgproduct['selprod_id']);
                                $availableStock = $pgproduct['selprod_stock'] - $tempHoldStock;
                                $userTempHoldStock = Product::tempHoldStockCount($pgproduct['selprod_id'], $cart_user_id, $product['prodgroup_id'], true);
                                if ($availableStock < ($product['quantity'] - $userTempHoldStock)) {
                                    $key = false;
                                    $productName = (isset($pgproduct['selprod_title']) && $pgproduct['selprod_title'] != '') ? $pgproduct['selprod_title'] : $pgproduct['product_name'];

                                    $this->errMessage = str_replace('{product-name}', $productName, Labels::getLabel('ERR_{product-name}_IS_TEMPORARY_OUT_OF_STOCK_OR_HOLD_BY_OTHER_CUSTOMER', $this->siteLangId));
                                    if (true === $addErrorMessage) {
                                        Message::addErrorMessage($this->errMessage);
                                    }
                                    return false;
                                }
                            }
                        } else {
                            if (FatApp::getConfig('CONF_HIDE_PRICES', FatUtility::VAR_INT, 0)) {
                                $tempHoldStock = 0;
                                $availableStock = $product['quantity'];
                            } else {
                                $tempHoldStock = (0 < $product['selprod_track_inventory']) ? Product::tempHoldStockCount($product['selprod_id']) : 0;
                                $availableStock = $product['selprod_stock'] - $tempHoldStock;
                            }
                            $userTempHoldStock = 0;
                            if (0 < $product['selprod_track_inventory']) {
                                $userTempHoldStock = Product::tempHoldStockCount($product['selprod_id'], $cart_user_id, 0, true);
                            }
                            $productName = (isset($product['selprod_title']) && $product['selprod_title'] != '') ? $product['selprod_title'] : $product['product_name'];
                            if (!isset($_SESSION['offer_checkout'])) {
                                if ($availableStock < ($product['quantity'] - $userTempHoldStock)) {
                                    $key = false;
                                    $this->errMessage = Labels::getLabel('ERR_{PRODUCT-NAME}_IS_TEMPORARY_OUT_OF_STOCK_OR_HOLD_BY_OTHER_CUSTOMER', $this->siteLangId);
                                } elseif ($product['selprod_min_order_qty'] > ($availableStock + $userTempHoldStock)) {
                                    $this->errMessage = Labels::getLabel('ERR_{PRODUCT-NAME}_ITS_MIN_PURCHASE_QUANTITY_IS_HIGHER_THAN_AVAILABLE_STOCK_LIMIT._SO_UNABLE_TO_PROCEED_FURTHER.', $this->siteLangId);
                                } elseif ($product['selprod_min_order_qty'] > $product['quantity']) {
                                    $this->errMessage = Labels::getLabel('ERR_{PRODUCT-NAME}_ITS_PURCHASE_QUANTITY_IS_LESS_THAN_MIN_PURCHASE_QUANTITY._SO_UNABLE_TO_PROCEED_FURTHER.', $this->siteLangId);
                                }
                            }

                            if (!empty($this->errMessage)) {
                                $this->errMessage = CommonHelper::replaceStringData($this->errMessage, ['{PRODUCT-NAME}' => htmlentities($productName, ENT_QUOTES)]);
                                if (true === $addErrorMessage) {
                                    Message::addErrorMessage($this->errMessage);
                                }
                                return false;
                            }
                        }
                    }
                    break;
                case 'hasBillingAddress':
                    if (!$this->cartObj->getCartBillingAddress()) {
                        $key = false;
                        $this->errMessage = Labels::getLabel('ERR_BILLING_ADDRESS_IS_NOT_PROVIDED.', $this->siteLangId);
                        if (true === $addErrorMessage) {
                            Message::addErrorMessage($this->errMessage);
                        }
                        return false;
                    }
                    break;
                case 'hasShippingAddress':
                    if (!$this->cartObj->getCartShippingAddress()) {
                        $key = false;
                        $this->errMessage = Labels::getLabel('ERR_SHIPPING_ADDRESS_IS_NOT_PROVIDED.', $this->siteLangId);
                        if (true === $addErrorMessage) {
                            Message::addErrorMessage($this->errMessage);
                        }
                        return false;
                    }
                    break;
                case 'isProductShippingMethodSet':
                    if (!$this->cartObj->isProductShippingMethodSet()) {
                        $key = false;
                        $this->errMessage = Labels::getLabel('ERR_SHIPPING_METHOD_IS_NOT_SELECTED_ON_PRODUCTS_IN_CART.', $this->siteLangId);
                        if (true === $addErrorMessage) {
                            Message::addErrorMessage($this->errMessage);
                        }
                        return false;
                    }
                    break;
                case 'isProductPickUpAddrSet':
                    if (!$this->cartObj->isProductPickUpAddrSet()) {
                        $key = false;
                        $this->errMessage = Labels::getLabel('ERR_PICKUP_METHOD_IS_NOT_SELECTED_ON_PRODUCTS_IN_CART.', $this->siteLangId);
                        if (true === $addErrorMessage) {
                            Message::addErrorMessage($this->errMessage);
                        }
                        return false;
                    }
                    break;
            }
        }
        return true;
    }

    public function index($appParam = '', $appLang = '1', $appCurrency = '1')
    {
        if (!isset($_SESSION['offer_checkout']) && FatApp::getConfig('CONF_HIDE_PRICES', FatUtility::VAR_INT, 0)) {
            $cartObj = new Cart();
            $cartObj->clear();
            $cartObj->updateUserCart();
            LibHelper::exitWithError(Labels::getLabel('ERR_PLEASE_CHECKOUT_WITH_ACCEPTED_OFFER_ONLY.'), redirect: true);
            CommonHelper::redirectUserReferer();
        }

        if ($appParam == 'api') {
            $langId = FatUtility::int($appLang);
            if (0 < $langId) {
                $languages = Language::getAllNames();
                if (array_key_exists($langId, $languages)) {
                    setcookie('defaultSiteLang', $langId, time() + 3600 * 24 * 10, CONF_WEBROOT_URL);
                }
            }

            $currencyId = FatUtility::int($appCurrency);
            if (0 < $currencyId) {
                $currencies = Currency::getCurrencyAssoc($this->siteLangId);
                if (array_key_exists($currencyId, $currencies)) {
                    setcookie('defaultSiteCurrency', $currencyId, time() + 3600 * 24 * 10, CONF_WEBROOT_URL);
                }
            }
            commonhelper::setAppUser();
            FatApp::redirectUser(UrlHelper::generateUrl('checkout', 'index'));
        }

        $criteria = array('hasProducts' => true, 'hasStock' => true);
        if (!$this->isEligibleForNextStep($criteria)) {
            FatApp::redirectUser(UrlHelper::generateUrl('cart'));
        }
        $cartHasPhysicalProduct = false;
        if ($this->cartObj->hasPhysicalProduct()) {
            $cartHasPhysicalProduct = true;
        }

        $obj = new Extrapage();
        $headerData = $obj->getContentByPageType(Extrapage::CHECKOUT_PAGE_HEADER_BLOCK, $this->siteLangId);

        $address = new Address($this->cartObj->getCartShippingAddress(), $this->siteLangId);
        $addresses = $address->getData(Address::TYPE_USER, UserAuthentication::getLoggedUserId());
        $this->set('cartHasPhysicalProduct', $cartHasPhysicalProduct);

        $removeCartType = !isset($_SESSION['offer_checkout']) ? SellerProduct::CART_TYPE_RFQ_ONLY : -1;
        $cart_products = $this->cartObj->getProducts($this->siteLangId, removeCartType: $removeCartType);

        if (0 < count($cart_products) && FatApp::getConfig('CONF_ANALYTICS_ADVANCE_ECOMMERCE', FatUtility::VAR_INT, 0)) {
            $et = new EcommerceTracking(Labels::getLabel('MSG_CHECKOUT', $this->siteLangId), UserAuthentication::getLoggedUserId(true));
            $et->addProductAction(EcommerceTracking::PROD_ACTION_TYPE_CHECKOUT);
            foreach ($cart_products as $product) {
                $et->addProduct($product['selprod_id'], $product['selprod_title'], $product['prodcat_name'], $product['brand_name'], $product['quantity']);
            }
            $et->sendRequest();
        }

        $this->set('addresses', $addresses);
        $this->set('headerData', $headerData);
        $this->set('cartItemsCount', $this->cartObj->countProducts());

        $this->_template->render(true, false);
    }

    public function loadLoginDiv()
    {
        $this->_template->render(false, false);
    }

    public function login()
    {
        $socialLoginApis = Plugin::getDataByType(Plugin::TYPE_SOCIAL_LOGIN, $this->siteLangId);
        $loginFormData = array(
            'loginFrm' => $this->getLoginForm(),
            'guestLoginFrm' => $this->getGuestUserForm($this->siteLangId),
            'siteLangId' => $this->siteLangId,
            'showSignUpLink' => true,
            'socialLoginApis' => $socialLoginApis,
            'onSubmitFunctionName' => 'setUpLogin'
        );
        $this->set('loginFormData', $loginFormData);

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

        $signUpFrm = $this->getRegistrationForm(false);
        $signUpFrm->addHiddenField('', 'isCheckOutPage', 1);

        $signUpFormData = array(
            'frm' => $signUpFrm,
            'siteLangId' => $this->siteLangId,
            'showLogInLink' => false,
            'onSubmitFunctionName' => 'setUpRegisteration',
            'termsAndConditionsLinkHref' => $termsAndConditionsLinkHref,
        );

        $this->set('signUpFormData', $signUpFormData);
        $this->_template->render(false, false);
    }

    public function addresses()
    {
        $criteria = array('isUserLogged' => true);
        $cartObj = new Cart();
        if (!$this->isEligibleForNextStep($criteria)) {
            $this->set('redirectUrl', UrlHelper::generateUrl('GuestUser', 'LoginForm', [], CONF_WEBROOT_FRONTEND));
            Message::addErrorMessage(Labels::getLabel('ERR_YOUR_SESSION_SEEMS_TO_BE_EXPIRED.', $this->siteLangId));
            FatUtility::dieWithError(Message::getHtml());
        }

        $addressFrm = $this->getUserAddressForm($this->siteLangId);
        $address = new Address(0, $this->siteLangId);
        $addresses = $address->getData(Address::TYPE_USER, UserAuthentication::getLoggedUserId());

        $cartHasPhysicalProduct = false;
        if ($cartObj->hasPhysicalProduct()) {
            $cartHasPhysicalProduct = true;
        }
        $cart_products = $this->cartObj->getProducts($this->siteLangId);
        if (count($cart_products) == 0) {
            Message::addErrorMessage(Labels::getLabel('ERR_YOUR_CART_IS_EMPTY.', $this->siteLangId));
            FatUtility::dieWithError(Message::getHtml());
        }

        $selected_billing_address_id = $cartObj->getCartBillingAddress();
        $selected_shipping_address_id = $cartObj->getCartShippingAddress();
        $fulfillmentType = $cartObj->getCartCheckoutType();

        $this->set('selected_billing_address_id', $selected_billing_address_id);
        $this->set('selected_shipping_address_id', $selected_shipping_address_id);
        $this->set('fulfillmentType', $fulfillmentType);

        $isShippingSameAsBilling = $cartObj->getShippingAddressSameAsBilling();
        $this->set('isShippingSameAsBilling', $isShippingSameAsBilling);
        $this->set('cartHasPhysicalProduct', $cartHasPhysicalProduct);
        $this->set('addresses', $addresses);
        $this->set('stateId', 0);
        $this->set('addressFrm', $addressFrm);
        $this->set('checkoutAddressFrm', $this->getCheckoutAddressForm($this->siteLangId));

        $addressType = FatApp::getPostedData('address_type', FatUtility::VAR_INT, 0);
        $this->set('addressType', $addressType);
        $this->_template->render(false, false);
    }

    public function loadBillingShippingAddress()
    {
        $cartObj = new Cart();
        $addressId = $cartObj->getCartShippingAddress();

        $hasPhysicalProduct = $this->cartObj->hasPhysicalProduct();
        $this->set('hasPhysicalProduct', $hasPhysicalProduct);

        if (!$hasPhysicalProduct) {
            $addressId = $cartObj->getCartBillingAddress();
        }

        $address = new Address($addressId, $this->siteLangId);
        $addresses = $address->getData(Address::TYPE_USER, UserAuthentication::getLoggedUserId());
        $this->set('defaultAddress', $addresses);
        $this->_template->render(false, false);
    }

    public function setUpAddressSelection()
    {
        if (!UserAuthentication::isUserLogged() && !UserAuthentication::isGuestUserLogged()) {
            $this->errMessage = Labels::getLabel('ERR_YOUR_SESSION_SEEMS_TO_BE_EXPIRED.', $this->siteLangId);
            if (true === MOBILE_APP_API_CALL) {
                FatUtility::dieJsonError($this->errMessage);
            }
            $this->set('redirectUrl', UrlHelper::generateUrl('GuestUser', 'LoginForm', [], CONF_WEBROOT_FRONTEND));
            Message::addErrorMessage($this->errMessage);
            FatUtility::dieWithError(Message::getHtml());
        }
        $shipping_address_id = FatApp::getPostedData('shipping_address_id', FatUtility::VAR_INT, 0);

        $billing_address_id = FatApp::getPostedData('billing_address_id', FatUtility::VAR_INT, 0);
        $isShippingSameAsBilling = FatApp::getPostedData('isShippingSameAsBilling', FatUtility::VAR_INT, 0);

        // Validate cart has products and has stock.
        //$this->cartObj = new Cart();

        $hasProducts = $this->cartObj->hasProducts();
        $hasStock = $this->cartObj->hasStock();

        if ((!$hasProducts) || (!$hasStock)) {
            $this->errMessage = Labels::getLabel('ERR_CART_SEEMS_TO_BE_EMPTY_OR_PRODUCTS_ARE_OUT_OF_STOCK.', $this->siteLangId);
            if (true === MOBILE_APP_API_CALL) {
                FatUtility::dieJsonError($this->errMessage);
            }
            $this->set('redirectUrl', UrlHelper::generateUrl('cart'));
            Message::addErrorMessage($this->errMessage);
            FatUtility::dieWithError(Message::getHtml());
        }

        $hasPhysicalProduct = $this->cartObj->hasPhysicalProduct();

        if (1 > $billing_address_id) {
            $this->errMessage = Labels::getLabel('ERR_PLEASE_SELECT_BILLING_ADDRESS.', $this->siteLangId);
            if (true === MOBILE_APP_API_CALL) {
                FatUtility::dieJsonError($this->errMessage);
            }
            Message::addErrorMessage($this->errMessage);
            FatUtility::dieWithError(Message::getHtml());
        }

        if ($hasPhysicalProduct && 1 > $shipping_address_id) {
            $this->errMessage = Labels::getLabel('ERR_PLEASE_SELECT_SHIPPING_ADDRESS.', $this->siteLangId);
            if (true === MOBILE_APP_API_CALL) {
                FatUtility::dieJsonError($this->errMessage);
            }
            $this->set('loadAddressDiv', true);
            Message::addErrorMessage($this->errMessage);
            FatUtility::dieWithError(Message::getHtml());
        }

        /* setup billing address[ */
        $address = new Address($billing_address_id);
        $billingAddressDetail = $address->getData(Address::TYPE_USER, UserAuthentication::getLoggedUserId());

        if (!$billingAddressDetail) {
            $this->errMessage = Labels::getLabel('ERR_INVALID_BILLING_ADDRESS.', $this->siteLangId);
            if (true === MOBILE_APP_API_CALL) {
                FatUtility::dieJsonError($this->errMessage);
            }
            Message::addErrorMessage($this->errMessage);
            FatUtility::dieWithError(Message::getHtml());
        }
        $this->cartObj->setCartBillingAddress($billingAddressDetail['addr_id']);

        /* ] */

        if ($hasPhysicalProduct && $shipping_address_id) {
            if ($isShippingSameAsBilling) {
                $this->cartObj->setShippingAddressSameAsBilling();
                $shipping_address_id = $billing_address_id;
            }

            $address = new Address($shipping_address_id);
            $shippingAddressDetail = $address->getData(Address::TYPE_USER, UserAuthentication::getLoggedUserId());
            if (!$shippingAddressDetail) {
                $this->errMessage = Labels::getLabel('ERR_INVALID_SHIPPING_ADDRESS.', $this->siteLangId);
                if (true === MOBILE_APP_API_CALL) {
                    FatUtility::dieJsonError($this->errMessage);
                }
                Message::addErrorMessage($this->errMessage);
                FatUtility::dieWithError(Message::getHtml());
            }
            $this->cartObj->setCartShippingAddress($shippingAddressDetail['addr_id']);
        }

        if (!$isShippingSameAsBilling) {
            $this->cartObj->unSetShippingAddressSameAsBilling();
        }

        if (!$hasPhysicalProduct) {
            $this->cartObj->unSetShippingAddressSameAsBilling();
            $this->cartObj->unsetCartShippingAddress();
        }

        $this->cartObj->removeProductShippingMethod();

        $this->set('hasPhysicalProduct', $hasPhysicalProduct);
        if (true === MOBILE_APP_API_CALL) {
            $this->_template->render();
        }
        $this->set('msg', Labels::getLabel('MSG_ADDRESS_SELECTION_SUCCESSFULLY', $this->siteLangId));
        $this->_template->render(false, false, 'json-success.php');
    }

    public function shippingSummary()
    {
        $criteria = array('isUserLogged' => true);
        if (!$this->isEligibleForNextStep($criteria)) {
            if (Message::getErrorCount()) {
                $this->errMessage = Message::getHtml();
            } else {
                Message::addErrorMessage(Labels::getLabel('ERR_SOMETHING_WENT_WRONG,_PLEASE_TRY_AFTER_SOME_TIME.', $this->siteLangId));
                $this->errMessage = Message::getHtml();
            }
            if (true === MOBILE_APP_API_CALL) {
                $this->errMessage = Labels::getLabel('ERR_SOMETHING_WENT_WRONG,_PLEASE_TRY_AFTER_SOME_TIME.', $this->siteLangId);
                FatUtility::dieJsonError($this->errMessage);
            }
            FatUtility::dieWithError($this->errMessage);
        }

        if (true === MOBILE_APP_API_CALL) {
            $fulfillmentType = FatApp::getPostedData('fulfillmentType', FatUtility::VAR_INT, Shipping::FULFILMENT_SHIP);
        } else {
            $fulfillmentType = $this->cartObj->getCartCheckoutType();
        }

        $this->cartObj->setCartCheckoutType($fulfillmentType);

        $removeCartType = isset($_SESSION['offer_checkout']) ? -1 : SellerProduct::CART_TYPE_RFQ_ONLY;
        $cartProducts = $this->cartObj->getProducts($this->siteLangId, removeCartType:$removeCartType);
        if (count($cartProducts) == 0) {
            $this->errMessage = Labels::getLabel('ERR_YOUR_CART_IS_EMPTY', $this->siteLangId);
            if (true === MOBILE_APP_API_CALL) {
                FatUtility::dieJsonError($this->errMessage);
            }
            Message::addErrorMessage($this->errMessage);
            FatUtility::dieWithError(Message::getHtml());
        }

        $hasPhysicalProd = $this->cartObj->hasPhysicalProduct();
        if (!$hasPhysicalProd) {
            $this->cartObj->unSetShippingAddressSameAsBilling();
            $this->cartObj->unsetCartShippingAddress();
        }


        $template = 'checkout/shipping-summary-inner.php';
        $shippingRates = [];
        $orderShippingData = '';

        switch ($fulfillmentType) {
            case Shipping::FULFILMENT_PICKUP:
                $shippingRates = $this->cartObj->getPickupOptions($cartProducts);
                $template = 'checkout/shipping-summary-pickup.php';
                break;
            case Shipping::FULFILMENT_SHIP:
                $shippingRates = $this->cartObj->getShippingOptions();
                if (!empty($_SESSION['order_id'])) {
                    $order = new Orders();
                    $orderShippingData = $order->getOrderShippingData($_SESSION['order_id'], $this->siteLangId);
                }
                break;
        }

        if (!$hasPhysicalProd) {
            $selected_shipping_address_id = $this->cartObj->getCartBillingAddress();
        } else {
            $selected_shipping_address_id = $this->cartObj->getCartShippingAddress();
        }

        $address = new Address($selected_shipping_address_id, $this->siteLangId);
        $addresses = $address->getData(Address::TYPE_USER, UserAuthentication::getLoggedUserId());

        $this->set('cartHasPhysicalProduct', $hasPhysicalProd);
        $this->set('cartSummary', $this->cartObj->getCartFinancialSummary($this->siteLangId));
        $this->set('fulfillmentType', $fulfillmentType);
        $this->set('addresses', $addresses);
        $this->set('products', $cartProducts);
        $this->set('shippingRates', $shippingRates);
        $this->set('hasPhysicalProd', $hasPhysicalProd);
        $this->set('orderShippingData', $orderShippingData);

        if (true === MOBILE_APP_API_CALL) {
            $this->_template->render();
        }

        $this->_template->render(false, false, $template);
    }

    public function setUpShippingMethod()
    {
        $this->cartObj->removeProductPickUpAddresses();
        $post = FatApp::getPostedData();

        if ($this->cartObj->hasPhysicalProduct()) {
            $criteria = ['hasShippingAddress' => true];
        } else {
            $criteria = ['hasBillingAddress' => true];
        }

        if (!$this->isEligibleForNextStep($criteria, false)) {
            LibHelper::exitWithError($this->errMessage, true);
        }


        $shippingServices = $post['shipping_services'] ?? [];
        $this->cartObj->setselectedShipping($shippingServices);

        $cartProducts = $this->cartObj->getProducts($this->siteLangId);
        $shippingRates = $this->cartObj->getShippingRates();
        if (false == $shippingRates) {
            $message = Labels::getLabel('ERR_SHIPPING_RATES_ARE_NOT_AVAILABLE', $this->siteLangId);
            LibHelper::exitWithError($message, true);
        }

        $selectedShippingMethods = [];
        $shipProducts = [];

        $basketProducts = $this->cartObj->getBasketProducts($this->siteLangId);
        foreach ($shippingServices as $prodIdCobination => $rateId) {
            if (empty($rateId)) {
                $message = Labels::getLabel('ERR_SHIPPING_METHOD_IS_NOT_SELECTED_ON_PRODUCTS_IN_CART', $this->siteLangId);
                LibHelper::exitWithError($message, true);
            }

            if (!array_key_exists($prodIdCobination, $shippingRates)) {
                $message = Labels::getLabel('ERR_SHIPPING_METHOD_IS_NOT_SELECTED_ON_PRODUCTS_IN_CART', $this->siteLangId);
                LibHelper::exitWithError($message, true);
            }

            if (!array_key_exists($rateId, $shippingRates[$prodIdCobination])) {
                $message = Labels::getLabel('ERR_SOMETHING_WENT_WRONG,_PLEASE_TRY_AFTER_SOME_TIME.', $this->siteLangId);
                LibHelper::exitWithError($message, true);
            }

            $amt = 0;
            $productArr = explode('_', $prodIdCobination);
            foreach ($productArr as $selProdId) {
                foreach ($basketProducts as $cartKey => $product) {
                    if ($product['selprod_id'] != $selProdId) {
                        continue;
                    }
                    $amt = $amt + ($product['quantity'] * $product['theprice']);
                }
            }

            $selectedShippingMethods[$prodIdCobination]['totalAmount'] = $amt;
            $selectedShippingMethods[$prodIdCobination]['rates'] = $shippingRates[$prodIdCobination][$rateId];
        }

        /*break down shipping amount in proportional to each product price */
        foreach ($shippingServices as $prodIdCobination => $rateId) {
            $shippingAmount[$prodIdCobination] = 0;
            $totalAmount = $selectedShippingMethods[$prodIdCobination]['totalAmount'];

            $counter = 1;
            $productArr = explode('_', $prodIdCobination);
            $prodCount = count($productArr);

            foreach ($productArr as $selProdId) {
                foreach ($basketProducts as $cartKey => $product) {
                    if ($product['selprod_id'] != $selProdId) {
                        continue;
                    }

                    if ($prodCount > 0 && $counter == $prodCount) {
                        $shipProducts[$selProdId]['cost']  = $shippingRates[$prodIdCobination][$rateId]['cost'] - $shippingAmount[$prodIdCobination];
                    } else {
                        $amt = (($product['quantity'] * $product['theprice']) * $shippingRates[$prodIdCobination][$rateId]['cost']) / $totalAmount;
                        $shipProducts[$selProdId]['cost'] =  round($amt, 2);
                        $shippingAmount[$prodIdCobination] = $shippingAmount[$prodIdCobination] + $shipProducts[$selProdId]['cost'];
                    }
                    $shipProducts[$selProdId]['info'] = $shippingRates[$prodIdCobination][$rateId];
                }
                $counter++;
            }
        }

        if (empty($cartProducts)) {
            LibHelper::exitWithError(Labels::getLabel('ERR_SOMETHING_WENT_WRONG,_PLEASE_TRY_AFTER_SOME_TIME.', $this->siteLangId), true);
        }

        $productToShippingMethods = array();
        $sn = 0;
        $json = array();
        $prodSrch = new ProductSearch();
        $prodSrch->setDefinedCriteria();
        $prodSrch->joinProductToCategory();
        $prodSrch->joinProductShippedBy();
        $prodSrch->joinProductFreeShipping();
        $prodSrch->joinSellerSubscription();
        $prodSrch->addSubscriptionValidCondition();
        $prodSrch->doNotCalculateRecords();
        $prodSrch->addCondition('selprod_deleted', '=', 'mysql_func_' . applicationConstants::NO, 'AND', true);
        $prodSrch->addDirectCondition('selprod_id IN (' . implode(',', array_column($cartProducts, 'selprod_id')) . ')');
        $prodSrch->addMultipleFields(array('selprod_id', 'product_seller_id', 'psbs_user_id as shippedBySellerId'));
        $prodsData = FatApp::getDb()->fetchAll($prodSrch->getResultSet(), 'selprod_id');

        foreach ($cartProducts as $cartkey => $cartval) {
            $sn++;
            if ($cartval['product_type'] != Product::PRODUCT_TYPE_PHYSICAL) {
                continue;
            }

            if (!array_key_exists($cartval['selprod_id'], $shipProducts) || empty($shipProducts[$cartval['selprod_id']])) {
                $json['error']['product'][$sn] = sprintf(Labels::getLabel('MSG_SHIPPING_INFO_REQUIRED_FOR_%S', $this->siteLangId), htmlentities($cartval['product_name']));
                continue;
            }

            $shipInfo =  $shipProducts[$cartval['selprod_id']]['info'];
            $productToShippingMethods['product'][$cartval['selprod_id']] = array(
                'selprod_id' => $cartval['selprod_id'],
                'mshipapi_code' => $shipInfo['code'],
                'mshipapi_id' => $shipInfo['id'],
                'mshipapi_label' => $shipInfo['title'],
                'mshipapi_carrier' => $shipInfo['carrier_code'],
                'mshipapi_type' => $shipInfo['shipping_type'],
                'mshipapi_is_seller_plugin' => $shipInfo['is_seller_plugin'],
                'mshipapi_cost' => $shipProducts[$cartval['selprod_id']]['cost'],
                'shipped_by_seller' => Product::isShippedBySeller($cartval['selprod_user_id'], $prodsData[$cartval['selprod_id']]['product_seller_id'], $prodsData[$cartval['selprod_id']]['shippedBySellerId']),
                'mshipapi_level' => $shipInfo['shipping_level']
            );
        }

        if (!$json) {
            $this->cartObj->setProductShippingMethod($productToShippingMethods);
            if (!$this->cartObj->isProductShippingMethodSet()) {
                $this->errMessage = Labels::getLabel('ERR_SHIPPING_METHOD_IS_NOT_SELECTED_ON_PRODUCTS_IN_CART', $this->siteLangId);
                LibHelper::exitWithError($this->errMessage, true);
            }

            $this->set('msg', Labels::getLabel('MSG_SHIPPING_METHOD_SELECTED_SUCCESSFULLY.', $this->siteLangId));
            if (true === MOBILE_APP_API_CALL) {
                $fulfilmentType = FatApp::getPostedData('fulfilmentType', FatUtility::VAR_INT, Shipping::FULFILMENT_SHIP);
                $cartObj = new Cart();
                $cartObj->setFulfilmentType($fulfilmentType);
                $cartObj->setCartCheckoutType($fulfilmentType);
                $productsArr = $cartObj->getProducts($this->siteLangId);
                $cartSummary = $cartObj->getCartFinancialSummary($this->siteLangId);
                $this->set('products', $productsArr);
                $this->set('cartSummary', $cartSummary);
                $this->set('recordCount', !empty($cartProducts) ? count($cartProducts) : 0);
                $this->_template->render();
            }
            $this->_template->render(false, false, 'json-success.php');
        } else {
            $this->errMessage = Labels::getLabel('ERR_SHIPPING_METHOD_IS_NOT_SELECTED_ON_PRODUCTS_IN_CART', $this->siteLangId);
            LibHelper::exitWithError($this->errMessage, true);
        }
    }

    public function reviewCart()
    {
        $loggedUserId = UserAuthentication::getLoggedUserId();
        $cartOrderData = Cart::getCartData($loggedUserId);
        $cartOrderData = !empty($cartOrderData) ? json_decode($cartOrderData, true) : [];
        $fulfillmentType = 0;
        if (isset($cartOrderData['shopping_cart']['checkout_type'])) {
            $fulfillmentType = $cartOrderData['shopping_cart']['checkout_type'];
        }

        $criteria = array('isUserLogged' => true, 'hasProducts' => true, 'hasStock' => true, 'hasBillingAddress' => true);
        if ($this->cartObj->hasPhysicalProduct()) {
            if ($fulfillmentType == Shipping::FULFILMENT_SHIP) {
                $criteria['hasShippingAddress'] = true;
                $criteria['isProductShippingMethodSet'] = true;
            } elseif ($fulfillmentType == Shipping::FULFILMENT_PICKUP) {
                $criteria['isProductPickUpAddrSet'] = true;
            }
        }

        if (!$this->isEligibleForNextStep($criteria)) {
            if (Message::getErrorCount()) {
                $this->errMessage = Message::getHtml();
            } else {
                $this->errMessage = Labels::getLabel('ERR_NOT_ALLOWED_TO_PROCEED_FOR_NEXT_STEP', $this->siteLangId);
            }
            LibHelper::exitWithError($this->errMessage, true);
        }

        if (0 >= $fulfillmentType) {
            $msg = Labels::getLabel("ERR_INVALID_FULFILLMENT_TYPE", $this->siteLangId);
            FatUtility::dieJsonError($msg);
        }

        $this->cartObj->setCartCheckoutType($fulfillmentType);
        $this->cartObj->setFulfilmentType($fulfillmentType);

        // $cartProducts = $this->cartObj->getProducts($this->siteLangId);
        $cartProducts = $this->cartObj->getBasketProducts($this->siteLangId);
        if (count($cartProducts) == 0) {
            $this->errMessage = Labels::getLabel('ERR_YOUR_CART_IS_EMPTY', $this->siteLangId);
            FatUtility::dieJsonError($this->errMessage);
        }

        $hasPhysicalProd = $this->cartObj->hasPhysicalProduct();
        $cartHasDigitalProduct = $this->cartObj->hasDigitalProduct();
        if (!$hasPhysicalProd) {
            $this->cartObj->unSetShippingAddressSameAsBilling();
            $this->cartObj->unsetCartShippingAddress();
        }
        $cartSummary = $this->cartObj->getCartFinancialSummary($this->siteLangId);

        $shippingRates = [];

        switch ($fulfillmentType) {
            case Shipping::FULFILMENT_PICKUP:
                $shippingRates = $this->cartObj->getPickupOptions($cartProducts);
                break;
            case Shipping::FULFILMENT_SHIP:
                $shippingRates = $this->cartObj->getShippingOptions();
                break;
        }

        if (!$hasPhysicalProd) {
            $selected_shipping_address_id = $this->cartObj->getCartBillingAddress();
        } else {
            $selected_shipping_address_id = $this->cartObj->getCartShippingAddress();
        }

        $address = new Address($selected_shipping_address_id, $this->siteLangId);
        $addresses = $address->getData(Address::TYPE_USER, UserAuthentication::getLoggedUserId());

        $billingAddressId = $this->cartObj->getCartBillingAddress();
        $address->setMainTableRecordId($billingAddressId);
        $billingAddress = $address->getData(Address::TYPE_USER, UserAuthentication::getLoggedUserId());

        $obj = new Extrapage();
        $headerData = $obj->getContentByPageType(Extrapage::CHECKOUT_PAGE_HEADER_BLOCK, $this->siteLangId);
        $this->set('cartSummary', $cartSummary);
        $this->set('fulfillmentType', $fulfillmentType);
        $this->set('addresses', $addresses);
        $this->set('billingAddress', $billingAddress);
        $this->set('isShippingAddressSameAsBilling', (int) ($selected_shipping_address_id == $billingAddressId));
        $this->set('products', $cartProducts);
        $this->set('hasPhysicalProd', $hasPhysicalProd);
        $this->set('cartHasDigitalProduct', $cartHasDigitalProduct);
        $this->set('cartOrderData', $cartOrderData);
        $this->set('shippingRates', $shippingRates);
        $this->set('headerData', $headerData);
        $this->_template->addJs('js/scroll-hint.js');
        $this->_template->render();
    }

    private function getCartProductsInfo($selprodIds)
    {
        $prodSrch = new ProductSearch($this->siteLangId);
        $prodSrch->setDefinedCriteria();
        $prodSrch->joinShopSpecifics();
        $prodSrch->joinSellerProductSpecifics();
        $prodSrch->joinProductSpecifics();
        $prodSrch->joinBrands();
        $prodSrch->joinSellerSubscription();
        $prodSrch->addSubscriptionValidCondition();
        $prodSrch->joinProductToCategory();
        $prodSrch->doNotCalculateRecords();
        $prodSrch->addCondition('selprod_deleted', '=', 'mysql_func_' . applicationConstants::NO, 'AND', true);
        $prodSrch->addDirectCondition('selprod_id IN (' . implode(',', $selprodIds) . ')');
        $fields = array(
            'product_id',
            'product_type',
            'product_length',
            'product_width',
            'product_height',
            'product_dimension_unit',
            'product_weight',
            'product_weight_unit',
            'product_model',
            'selprod_id',
            'selprod_user_id',
            'selprod_stock',
            'IF(selprod_stock > 0, 1, 0) AS in_stock',
            'selprod_sku',
            'selprod_condition',
            'selprod_code',
            'special_price_found',
            'theprice',
            'shop_id',
            'IFNULL(product_name, product_identifier) as product_name',
            'IFNULL(selprod_title  ,IFNULL(product_name, product_identifier)) as selprod_title',
            'IFNULL(brand_name, brand_identifier) as brand_name',
            'shop_name',
            'seller_user.user_name as shop_onwer_name',
            'seller_user_cred.credential_username as shop_owner_username',
            'seller_user.user_phone_dcode as shop_owner_phone_dcode',
            'seller_user.user_phone as shop_owner_phone',
            'seller_user_cred.credential_email as shop_owner_email',
            'selprod_download_validity_in_days',
            'selprod_max_download_times',
            'ps.product_warranty',
            'COALESCE(sps.selprod_return_age, ss.shop_return_age) as return_age',
            'COALESCE(sps.selprod_cancellation_age, ss.shop_cancellation_age) as cancellation_age',
            'prodcat_id',
            'product_attachements_with_inventory',
            'selprod_product_id'
        );
        $prodSrch->addMultipleFields($fields);
        return FatApp::getDb()->fetchAll($prodSrch->getResultSet(), 'selprod_id');
    }

    private function getCartProductsLangData($selprodIds, $lang_id)
    {
        $langProdSrch = new ProductSearch($lang_id);
        $langProdSrch->setDefinedCriteria(0, 0, ['doNotJoinSellers' => true, 'doNotJoinSpecialPrice' => true]);
        $langProdSrch->joinBrands();
        $langProdSrch->joinProductToCategory();
        $langProdSrch->joinSellerSubscription();
        $langProdSrch->addSubscriptionValidCondition();
        $langProdSrch->doNotCalculateRecords();
        $langProdSrch->doNotLimitRecords();
        $langProdSrch->addCondition('selprod_deleted', '=', 'mysql_func_' . applicationConstants::NO, 'AND', true);
        $langProdSrch->addDirectCondition('selprod_id IN (' . implode(',', $selprodIds) . ')');
        $fields = array('selprod_id', 'IFNULL(product_name, product_identifier) as product_name', 'IFNULL(selprod_title  ,IFNULL(product_name, product_identifier)) as selprod_title', 'IFNULL(brand_name, brand_identifier) as brand_name', 'IFNULL(shop_name, shop_identifier) as shop_name');
        $langProdSrch->addMultipleFields($fields);
        return FatApp::getDb()->fetchAll($langProdSrch->getResultSet(), 'selprod_id');
    }

    public function paymentSummary()
    {
        $canUseWallet = PaymentMethods::canUseWalletForPayment();
        if (true === MOBILE_APP_API_CALL || false === $canUseWallet) {
            $payFromWallet = FatApp::getPostedData('payFromWallet', Fatutility::VAR_INT, 0);
            $this->cartObj->updateCartWalletOption($payFromWallet);

            $useRewardPoints = FatApp::getPostedData('redeem_rewards', FatUtility::VAR_INT, 0);
            if (0 < $useRewardPoints && true === MOBILE_APP_API_CALL) {
                $this->useRewardPoints(true);
            }
        }

        $criteria = array('isUserLogged' => true, 'hasProducts' => true, 'hasStock' => true, 'hasBillingAddress' => true);
        $fulfillmentType = $this->cartObj->getCartCheckoutType();
        $cartHasPhysicalProduct = $this->cartObj->hasPhysicalProduct();
        if ($cartHasPhysicalProduct && $fulfillmentType == Shipping::FULFILMENT_SHIP) {
            $criteria['hasShippingAddress'] = true;
            $criteria['isProductShippingMethodSet'] = true;
        }
        if ($fulfillmentType == Shipping::FULFILMENT_PICKUP) {
            $criteria['isProductPickUpAddrSet'] = true;
        }

        if (!$this->isEligibleForNextStep($criteria)) {
            $this->errMessage = !empty($this->errMessage) ? $this->errMessage : Labels::getLabel('ERR_SOMETHING_WENT_WRONG,_PLEASE_TRY_AFTER_SOME_TIME.', $this->siteLangId);
            LibHelper::exitWithError($this->errMessage);
        }

        if ($this->cartObj->getError() != '') {
            LibHelper::exitWithError($this->cartObj->getError());
        }
        $cartSummary = $this->cartObj->getCartFinancialSummary($this->siteLangId);

        $userId = UserAuthentication::getLoggedUserId();
        $userWalletBalance = User::getUserBalance($userId, true);

        /* Payment Methods[ */
        $splitPaymentMethodsPlugins = Plugin::getDataByType(Plugin::TYPE_SPLIT_PAYMENT_METHOD, $this->siteLangId);
        $regularPaymentMethodsPlugins = Plugin::getDataByType(Plugin::TYPE_REGULAR_PAYMENT_METHOD, $this->siteLangId);

        if ($fulfillmentType == Shipping::FULFILMENT_PICKUP) {
            $codPlugInId = Plugin::getAttributesByCode('CashOnDelivery', 'plugin_id');
            if (array_key_exists($codPlugInId, $regularPaymentMethodsPlugins)) {
                unset($regularPaymentMethodsPlugins[$codPlugInId]);
            }
        } else if ($fulfillmentType == Shipping::FULFILMENT_SHIP) {
            $codPlugInId = Plugin::getAttributesByCode('PayAtStore', 'plugin_id');
            if (array_key_exists($codPlugInId, $regularPaymentMethodsPlugins)) {
                unset($regularPaymentMethodsPlugins[$codPlugInId]);
            }
        }

        $paymentMethods = array_merge($splitPaymentMethodsPlugins, $regularPaymentMethodsPlugins);
        $cashOnDeliveryKey = array_search('CashOnDelivery', array_column($paymentMethods, 'plugin_code'));
        if ($this->cartObj->hasServiceProduct() && false !== $cashOnDeliveryKey) {
            unset($paymentMethods[$cashOnDeliveryKey]);
        }

        if ($cartSummary["cartWalletSelected"]) {
            $transferBankKey = array_search('TransferBank', array_column($paymentMethods, 'plugin_code'));
            if (false !== $transferBankKey) {
                unset($paymentMethods[$transferBankKey]);
            }
        }

        /* ] */

        $opComments = FatApp::getPostedData('op_comments');
        $orderData = array();
        /* add Order Data[ */
        if (true === MOBILE_APP_API_CALL) {
            $order_id = FatApp::getPostedData('orderId', Fatutility::VAR_INT, 0);
        } else {
            $order_id = isset($_SESSION['shopping_cart']["order_id"]) ? $_SESSION['shopping_cart']["order_id"] : 0;
        }

        $shippingAddressArr = array();
        $billingAddressArr = array();
        $shippingAddressId = $this->cartObj->getCartShippingAddress();
        $billingAddressId = $this->cartObj->getCartBillingAddress();

        if ($shippingAddressId) {
            $address = new Address($shippingAddressId, $this->siteLangId);
            $shippingAddressArr = $address->getData(Address::TYPE_USER, $userId);
        }
        if ($billingAddressId) {
            $address = new Address($billingAddressId, $this->siteLangId);
            $billingAddressArr = $address->getData(Address::TYPE_USER, $userId);
        }

        $orderData['order_id'] = $order_id;
        $orderData['order_user_id'] = $userId;
        /* $orderData['order_user_name'] = $userDataArr['user_name'];
        $orderData['order_user_email'] = $userDataArr['credential_email'];
        $orderData['order_user_phone'] = $userDataArr['user_phone']; */
        $orderData['order_payment_status'] = Orders::ORDER_PAYMENT_PENDING;
        $orderData['order_date_added'] = date('Y-m-d H:i:s');

        /* addresses[ */
        $userAddresses[0] = array(
            'oua_order_id' => $order_id,
            'oua_type' => Orders::BILLING_ADDRESS_TYPE,
            'oua_name' => $billingAddressArr['addr_name'],
            'oua_address1' => $billingAddressArr['addr_address1'],
            'oua_address2' => $billingAddressArr['addr_address2'],
            'oua_city' => $billingAddressArr['addr_city'],
            'oua_state' => $billingAddressArr['state_name'],
            'oua_country' => $billingAddressArr['country_name'],
            'oua_country_code' => $billingAddressArr['country_code'],
            'oua_country_code_alpha3' => $billingAddressArr['country_code_alpha3'],
            'oua_state_code' => $billingAddressArr['state_code'],
            'oua_phone_dcode' => $billingAddressArr['addr_phone_dcode'],
            'oua_phone' => $billingAddressArr['addr_phone'],
            'oua_zip' => $billingAddressArr['addr_zip'],
        );

        if (!empty($shippingAddressArr) && $fulfillmentType == Shipping::FULFILMENT_SHIP) {
            $userAddresses[1] = array(
                'oua_order_id' => $order_id,
                'oua_type' => Orders::SHIPPING_ADDRESS_TYPE,
                'oua_name' => $shippingAddressArr['addr_name'],
                'oua_address1' => $shippingAddressArr['addr_address1'],
                'oua_address2' => $shippingAddressArr['addr_address2'],
                'oua_city' => $shippingAddressArr['addr_city'],
                'oua_state' => $shippingAddressArr['state_name'],
                'oua_country' => $shippingAddressArr['country_name'],
                'oua_country_code' => $shippingAddressArr['country_code'],
                'oua_country_code_alpha3' => $shippingAddressArr['country_code_alpha3'],
                'oua_state_code' => $shippingAddressArr['state_code'],
                'oua_phone_dcode' => $shippingAddressArr['addr_phone_dcode'],
                'oua_phone' => $shippingAddressArr['addr_phone'],
                'oua_zip' => $shippingAddressArr['addr_zip'],
            );
        }

        $orderData['userAddresses'] = $userAddresses;
        /* ] */

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

        //$orderData['order_payment_gateway_charges'] = $cartSummary["orderPaymentGatewayCharges"];
        //$orderData['order_cart_total'] = $cartSummary["cartTotal"];
        //$orderData['order_shipping_charged'] = $cartSummary["shippingTotal"];
        $orderData['order_tax_charged'] = $cartSummary["cartTaxTotal"];
        $orderData['order_site_commission'] = $cartSummary["siteCommission"];
        $orderData['order_volume_discount_total'] = $cartSummary["cartVolumeDiscount"];
        //$orderData['order_sub_total'] = $cartSummary["netTotalWithoutDiscount"];
        //$orderData['order_net_charged'] = $cartSummary["netTotalAfterDiscount"];
        //$orderData['order_actual_paid'] = $cartSummary["cartActualPaid"];
        $orderData['order_net_amount'] = $cartSummary["orderNetAmount"];
        $orderData['order_is_wallet_selected'] = $cartSummary["cartWalletSelected"];
        $orderData['order_wallet_amount_charge'] = $cartSummary["WalletAmountCharge"];
        $orderData['order_type'] = Orders::ORDER_PRODUCT;

        /* referrer details[ */
        $srchOrder = new OrderSearch();
        $srchOrder->doNotCalculateRecords();
        $srchOrder->setPageSize(1);
        $srchOrder->addCondition('order_user_id', '=',  'mysql_func_' . $userId, 'AND', true);
        $srchOrder->addCondition('order_payment_status', '=', 'mysql_func_' . Orders::ORDER_PAYMENT_PAID, 'AND', true);
        $srchOrder->addCondition('order_referrer_user_id', '!=', 'mysql_func_' . applicationConstants::NO, 'AND', true);
        $srchOrder->addMultipleFields(array('count(o.order_id) as totalOrders'));
        $rs = $srchOrder->getResultSet();
        $existingReferrerOrderRow = FatApp::getDb()->fetch($rs);

        $orderData['order_referrer_user_id'] = 0;
        $orderData['order_referrer_reward_points'] = 0;
        $orderData['order_referral_reward_points'] = 0;
        $orderData['order_cart_data'] = Cart::getCartData($userId);

        $referrerUserId = 0;
        if (isset($_COOKIE['referrer_code_checkout']) && !empty($_COOKIE['referrer_code_checkout'])) {
            $userReferrerCode = $_COOKIE['referrer_code_checkout'];

            $userSrchObj = User::getSearchObject();
            $userSrchObj->doNotCalculateRecords();
            $userSrchObj->setPageSize(1);
            $userSrchObj->addCondition('user_referral_code', '=', $userReferrerCode);
            $userSrchObj->addCondition('user_id', '!=', 'mysql_func_' . $userId, 'AND', true);
            $userSrchObj->addMultipleFields(array('user_id', 'user_referral_code', 'user_name'));
            $rs = $userSrchObj->getResultSet();
            $referrerUserRow = FatApp::getDb()->fetch($rs);
            if ($referrerUserRow && $referrerUserRow['user_referral_code'] == $userReferrerCode && $userReferrerCode != '' && $referrerUserRow['user_referral_code'] != '') {
                $referrerUserId = $referrerUserRow['user_id'];
                //$referrerUserName = $referrerUserRow['user_name'];
            }
        }

        if ($referrerUserId > 0 && FatApp::getConfig("CONF_ENABLE_REFERRER_MODULE") && $existingReferrerOrderRow['totalOrders'] == 0) {
            $orderData['order_referrer_user_id'] = $referrerUserId;
            $orderData['order_referrer_reward_points'] = FatApp::getConfig("CONF_SALE_REFERRER_REWARD_POINTS", FatUtility::VAR_INT, 0);
            $orderData['order_referral_reward_points'] = FatApp::getConfig("CONF_SALE_REFERRAL_REWARD_POINTS", FatUtility::VAR_INT, 0);
        }
        /* ] */

        $allLanguages = Language::getAllNames();
        $productSelectedShippingMethodsArr = $this->cartObj->getProductShippingMethod();
        $productSelectedPickUpAddresses = $this->cartObj->getProductPickUpAddresses();
        $orderLangData = array();
        foreach ($allLanguages as $lang_id => $language_name) {
            $order_shippingapi_name = '';

            if ($this->cartObj->getCartShippingApi()) {
                $shippingApiLangRow = ShippingApi::getAttributesByLangId($lang_id, $this->cartObj->getCartShippingApi());
                $order_shippingapi_name = $shippingApiLangRow['shippingapi_name'];
                if (empty($order_shippingapi_name)) {
                    $order_shippingapi_name = $shippingApiLangRow['shippingapi_identifier'];
                }
            }

            $orderLangData[$lang_id] = array(
                'orderlang_lang_id' => $lang_id,
                'order_shippingapi_name' => $order_shippingapi_name
            );
        }
        $orderData['orderLangData'] = $orderLangData;

        /* order products[ */
        $cartProducts = $this->cartObj->getProducts($this->siteLangId);
        $orderData['products'] = array();
        $orderData['prodCharges'] = array();

        $order_affiliate_user_id = 0;
        $order_affiliate_total_commission = 0;
        $totalRoundingOff = 0;

        $productOptionsData = [];
        $cartProductData = $this->getCartProductsInfo(array_column($cartProducts, 'selprod_id'));
        foreach ($allLanguages as $lang_id => $language_name) {
            $cartProductsLangData[$lang_id] = $this->getCartProductsLangData(array_column($cartProducts, 'selprod_id'), $lang_id);
            $prodOptionsData = SellerProduct::getSellerProductOptions(array_column($cartProducts, 'selprod_id'), true, $lang_id);
            foreach ($prodOptionsData as $data) {
                $productOptionsData[$data['selprodoption_selprod_id']][$lang_id][] = $data;
            }
        }

        $rewardPoints = 0;
        if ($cartProducts) {
            foreach ($cartProducts as $cartProduct) {
                $codEnabled = $cartProduct['isProductShippedBySeller'] ? $cartProduct['selprod_cod_enabled'] : $cartProduct['product_cod_enabled'];
                if (applicationConstants::NO == $codEnabled) {
                    $key = array_search('CashOnDelivery', array_column($paymentMethods, 'plugin_code'));
                    if (false !== $key) {
                        unset($paymentMethods[$key]);
                    }
                }

                $productShippingData = array();
                $productTaxChargesData = array();
                $productInfo = $cartProductData[$cartProduct['selprod_id']];
                if (!$productInfo) {
                    continue;
                }

                $sduration_name = '';
                $shippingDurationTitle = '';
                $shippingDurationRow = array();

                if ($productInfo['product_type'] == Product::PRODUCT_TYPE_PHYSICAL && !empty($productSelectedShippingMethodsArr['product']) && isset($productSelectedShippingMethodsArr['product'][$productInfo['selprod_id']])) {
                    $shippingDurationRow = $productSelectedShippingMethodsArr['product'][$productInfo['selprod_id']];
                    $productShippingData = array(
                        'opshipping_code' => $shippingDurationRow['mshipapi_code'],
                        'opshipping_level' => $shippingDurationRow['mshipapi_level'],
                        'opshipping_label' => $shippingDurationRow['mshipapi_label'],
                        'opshipping_by_seller_user_id' => $shippingDurationRow['shipped_by_seller']
                    );
                    if ($shippingDurationRow['mshipapi_type'] == Shipping::TYPE_MANUAL) {
                        $productShippingData['opshipping_rate_id'] = $shippingDurationRow['mshipapi_id'];
                    } else {
                        $productShippingData['opshipping_rate_id'] = 0;
                        $productShippingData['opshipping_service_code'] = $shippingDurationRow['mshipapi_id'];
                        $productShippingData['opshipping_carrier_code'] = $shippingDurationRow['mshipapi_carrier'];
                        $productShippingData['opshipping_plugin_id'] = $shippingDurationRow['mshipapi_type'];
                        $productShippingData['opshipping_is_seller_plugin'] = $shippingDurationRow['mshipapi_is_seller_plugin'];
                        $productShippingData['opshipping_plugin_charges'] = $shippingDurationRow['mshipapi_cost'];
                    }
                }

                $productPickUpData = array();
                $productPickupAddress = array();
                if ($productInfo['product_type'] == Product::PRODUCT_TYPE_PHYSICAL && !empty($productSelectedPickUpAddresses) && isset($productSelectedPickUpAddresses[$productInfo['selprod_id']])) {
                    $pickUpDataRow = $productSelectedPickUpAddresses[$productInfo['selprod_id']];
                    $productPickUpData = array(
                        'opshipping_fulfillment_type' => Shipping::FULFILMENT_PICKUP,
                        'opshipping_by_seller_user_id' => $pickUpDataRow['shipped_by_seller'],
                        'opshipping_pickup_addr_id' => $pickUpDataRow['time_slot_addr_id'],
                        'opshipping_date' => $pickUpDataRow['time_slot_date'],
                        'opshipping_time_slot_from' => $pickUpDataRow['time_slot_from_time'],
                        'opshipping_time_slot_to' => $pickUpDataRow['time_slot_to_time'],
                    );

                    $addressRecordId = Address::getAttributesById($pickUpDataRow['time_slot_addr_id'], 'addr_record_id');
                    $addr = new Address($pickUpDataRow['time_slot_addr_id'], $this->siteLangId);
                    $pickUpAddressArr = $addr->getData($pickUpDataRow['time_slot_type'], $addressRecordId);
                    if (!empty($pickUpAddressArr)) {
                        $productPickupAddress = array(
                            'oua_order_id' => $order_id,
                            'oua_op_id' => '',
                            'oua_type' => Orders::PICKUP_ADDRESS_TYPE,
                            'oua_name' => $pickUpAddressArr['addr_name'] ?? Shop::getName($cartProduct['shop_id'], $this->siteLangId) ?? '',
                            'oua_address1' => $pickUpAddressArr['addr_address1'],
                            'oua_address2' => $pickUpAddressArr['addr_address2'],
                            'oua_city' => $pickUpAddressArr['addr_city'],
                            'oua_state' => $pickUpAddressArr['state_name'],
                            'oua_country' => $pickUpAddressArr['country_name'],
                            'oua_country_code' => $pickUpAddressArr['country_code'],
                            'oua_country_code_alpha3' => $pickUpAddressArr['country_code_alpha3'],
                            'oua_state_code' => $pickUpAddressArr['state_code'],
                            'oua_phone_dcode' => $pickUpAddressArr['addr_phone_dcode'],
                            'oua_phone' => $pickUpAddressArr['addr_phone'],
                            'oua_zip' => $pickUpAddressArr['addr_zip'],
                        );
                    }
                }

                $productTaxOption = array();
                if (array_key_exists($productInfo['selprod_id'], $cartSummary["prodTaxOptions"])) {
                    $productTaxOption = $cartSummary["prodTaxOptions"][$productInfo['selprod_id']];
                }

                foreach ($productTaxOption as $taxStroId => $taxStroName) {
                    $label = Labels::getLabel('MSG_TAX', $this->siteLangId);
                    if (array_key_exists('name', $taxStroName) && $taxStroName['name'] != '') {
                        $label = $taxStroName['name'];
                    }
                    $productTaxChargesData[$taxStroId] = array(
                        'opchargelog_type' => OrderProduct::CHARGE_TYPE_TAX,
                        'opchargelog_identifier' => $label,
                        'opchargelog_value' => $taxStroName['value'],
                        'opchargelog_is_percent' => $taxStroName['inPercentage'],
                        'opchargelog_percentvalue' => $taxStroName['percentageValue']
                    );
                }

                $productsLangData = array();
                $productShippingLangData = array();
                foreach ($allLanguages as $lang_id => $language_name) {
                    if (0 == $lang_id) {
                        continue;
                    }
                    $langSpecificProductInfo = $cartProductsLangData[$lang_id][$productInfo['selprod_id']];
                    if (!$langSpecificProductInfo) {
                        continue;
                    }

                    if (!empty($shippingDurationRow)) {
                        $langData =  ShippingRate::getAttributesByLangId($shippingDurationRow['mshipapi_id'], $lang_id);
                        $label = (isset($langData['shiprate_name']) && $langData['shiprate_name'] != '') ? $langData['shiprate_name'] : $shippingDurationRow['mshipapi_label'];
                        $productShippingLangData[$lang_id] = array(
                            'opshipping_title' => $label,
                            'opshipping_duration' => '',
                            'opshipping_duration_name' => $label . '-' . $shippingDurationRow['mshipapi_cost'],
                            'opshippinglang_lang_id' => $lang_id
                        );
                    }

                    $weightUnitsArr = applicationConstants::getWeightUnitsArr($lang_id);
                    $lengthUnitsArr = applicationConstants::getLengthUnitsArr($lang_id);
                    $op_selprod_title = ($langSpecificProductInfo['selprod_title'] != '') ? $langSpecificProductInfo['selprod_title'] : '';

                    /* stamping/locking of product options language based [ */
                    $op_selprod_options = '';
                    $productOptionsRows = !empty($productOptionsData[$productInfo['selprod_id']][$lang_id]) ? $productOptionsData[$productInfo['selprod_id']][$lang_id] : [];
                    if (!empty($productOptionsRows)) {
                        $optionCounter = 1;
                        foreach ($productOptionsRows as $poLang) {
                            $op_selprod_options .= $poLang['option_name'] . SellerProduct::OPTION_NAME_SEPARATOR . $poLang['optionvalue_name'];
                            if ($optionCounter != count($productOptionsRows)) {
                                $op_selprod_options .= SellerProduct::MULTIPLE_OPTION_SEPARATOR;
                            }
                            $optionCounter++;
                        }
                    }
                    /* ] */

                    $op_products_dimension_unit_name = ($productInfo['product_dimension_unit']) ? $lengthUnitsArr[$productInfo['product_dimension_unit']] : '';
                    $op_product_weight_unit_name = ($productInfo['product_weight_unit']) ? $weightUnitsArr[$productInfo['product_weight_unit']] : '';

                    $op_product_tax_options = array();


                    foreach ($productTaxOption as $taxStroId => $taxStroName) {
                        $label = Labels::getLabel('MSG_TAX', $lang_id);
                        if (array_key_exists('name', $taxStroName) && $taxStroName['name'] != '') {
                            $label = $taxStroName['name'];
                        }
                        $op_product_tax_options[$label]['name'] = $label;
                        $op_product_tax_options[$label]['value'] = $taxStroName['value'];
                        $op_product_tax_options[$label]['percentageValue'] = $taxStroName['percentageValue'];
                        $op_product_tax_options[$label]['inPercentage'] = $taxStroName['inPercentage'];
                        $langLabel = $label;
                        if (isset($taxStroName['taxstr_id']) && $taxStroName['taxstr_id'] != '') {
                            $langData =  TaxStructure::getAttributesByLangId($lang_id, $taxStroName['taxstr_id'], array('taxstr_name', 'taxstr_is_combined', 'taxstr_identifier'), 1);
                            if ($langData && 1 == $langData['taxstr_is_combined']) {
                                $langLabel = isset($langData['taxstr_name']) && !empty($langData['taxstr_name']) ? $langData['taxstr_name'] : $langData['taxstr_identifier'];
                            }
                        }

                        $productTaxChargesData[$taxStroId]['langData'][$lang_id] = array(
                            'opchargeloglang_lang_id' => $lang_id,
                            'opchargelog_name' => $langLabel
                        );
                    }

                    $productsLangData[$lang_id] = array(
                        'oplang_lang_id' => $lang_id,
                        'op_product_name' => $langSpecificProductInfo['product_name'],
                        'op_selprod_title' => $op_selprod_title,
                        'op_selprod_options' => $op_selprod_options,
                        'op_brand_name' => !empty($langSpecificProductInfo['brand_name']) ? $langSpecificProductInfo['brand_name'] : '',
                        'op_shop_name' => $langSpecificProductInfo['shop_name'],
                        'op_shipping_duration_name' => $sduration_name,
                        'op_shipping_durations' => $shippingDurationTitle,
                        'op_products_dimension_unit_name' => $op_products_dimension_unit_name,
                        'op_product_weight_unit_name' => $op_product_weight_unit_name,
                        'op_product_tax_options' => json_encode($op_product_tax_options),
                    );
                }
                /* $taxCollectedBySeller = applicationConstants::NO;
                if(FatApp::getConfig('CONF_TAX_COLLECTED_BY_SELLER',FatUtility::VAR_INT,0)){
                $taxCollectedBySeller = applicationConstants::YES;
                } */

                /*
                    if (1 > $productInfo['return_age']) {
                        $productInfo['return_age'] = FatApp::getConfig("CONF_DEFAULT_RETURN_AGE", FatUtility::VAR_INT, 7);
                    }
                */

                $ofrSelprodId = $_SESSION['offer_checkout']['selprod_id'] ?? 0;
                $offerId = $_SESSION['offer_checkout']['offer_id'] ?? 0;
                $offerId = ($productInfo['selprod_id'] == $ofrSelprodId) ? $offerId : 0;

                $orderData['products'][CART::CART_KEY_PREFIX_PRODUCT . $productInfo['selprod_id']] = array(
                    'op_selprod_id' => $productInfo['selprod_id'],
                    'op_offer_id' => $offerId,
                    'op_is_batch' => 0,
                    'op_selprod_user_id' => $productInfo['selprod_user_id'],
                    'op_selprod_code' => $productInfo['selprod_code'],
                    'op_qty' => $cartProduct['quantity'],
                    'op_unit_price' => $cartProduct['theprice'],
                    'op_unit_cost' => $cartProduct['selprod_cost'],
                    'op_selprod_price' => $cartProduct['selprod_price'],
                    'op_selprod_sku' => $productInfo['selprod_sku'],
                    'op_selprod_condition' => $productInfo['selprod_condition'],
                    'op_product_model' => $productInfo['product_model'],
                    'op_product_type' => $productInfo['product_type'],
                    'op_product_length' => $cartProduct['shippack_length'],
                    'op_product_width' => $cartProduct['shippack_width'],
                    'op_product_height' => $cartProduct['shippack_height'],
                    'op_product_dimension_unit' => $cartProduct['shippack_units'],
                    'op_product_weight' => $productInfo['product_weight'],
                    'op_product_weight_unit' => $productInfo['product_weight_unit'],
                    'op_shop_id' => $productInfo['shop_id'],
                    'op_shop_owner_username' => $productInfo['shop_owner_username'],
                    'op_shop_owner_name' => $productInfo['shop_onwer_name'],
                    'op_shop_owner_email' => $productInfo['shop_owner_email'],
                    'op_shop_owner_phone_dcode' => isset($productInfo['shop_owner_phone_dcode']) && !empty($productInfo['shop_owner_phone_dcode']) ? $productInfo['shop_owner_phone_dcode'] : '',
                    'op_shop_owner_phone' => isset($productInfo['shop_owner_phone']) && !empty($productInfo['shop_owner_phone']) ? $productInfo['shop_owner_phone'] : '',
                    'op_selprod_max_download_times' => ($productInfo['selprod_max_download_times'] != '-1') ? $cartProduct['quantity'] * $productInfo['selprod_max_download_times'] : $productInfo['selprod_max_download_times'],
                    'op_selprod_download_validity_in_days' => $productInfo['selprod_download_validity_in_days'],
                    'opshipping_rate_id' => $cartProduct['opshipping_rate_id'],
                    //'op_discount_total'    =>    0, //todo:: after coupon discount integration
                    //'op_tax_total'    =>    $cartProduct['tax'],
                    'op_commission_charged' => $cartProduct['commission'],
                    'op_commission_percentage' => $cartProduct['commission_percentage'],
                    'op_affiliate_commission_percentage' => isset($cartProduct['affiliate_commission_percentage']) ? $cartProduct['affiliate_commission_percentage'] : 0,
                    'op_affiliate_commission_charged' => isset($cartProduct['affiliate_commission']) ? $cartProduct['affiliate_commission'] : 0,
                    'op_status_id' => FatApp::getConfig("CONF_DEFAULT_ORDER_STATUS"),
                    // 'op_volume_discount_percentage'    =>    $cartProduct['volume_discount_percentage'],
                    'productsLangData' => $productsLangData,
                    'productShippingData' => $productShippingData,
                    'productPickUpData' => $productPickUpData,
                    'productPickupAddress' => $productPickupAddress,
                    'productShippingLangData' => $productShippingLangData,
                    'productChargesLogData' => $productTaxChargesData,
                    /* 'op_tax_collected_by_seller'    =>    $taxCollectedBySeller, */
                    /* 'op_free_ship_upto' => $cartProduct['shop_free_ship_upto'], */
                    'op_actual_shipping_charges' => $cartProduct['shipping_cost'],
                    'op_tax_code' => $cartProduct['taxCode'],
                    'productSpecifics' => [
                        'op_selprod_return_age' => $productInfo['return_age'],
                        'op_selprod_cancellation_age' => $productInfo['cancellation_age'],
                        'op_product_warranty' => $productInfo['product_warranty'],
                        'op_prodcat_id' => $productInfo['prodcat_id'],
                        'op_special_price' => ($cartProduct['selprod_price'] > $cartProduct['actualPrice']) ? $cartProduct['selprod_price'] - $cartProduct['actualPrice'] : 0,
                    ],
                    'op_rounding_off' => $cartProduct['rounding_off'],
                    'op_comments' => $opComments[$productInfo['selprod_id']] ?? '',
                    'selprod_product_id' => $productInfo['selprod_product_id'],
                    'product_attachements_with_inventory' => $productInfo['product_attachements_with_inventory'],
                );

                $order_affiliate_user_id = isset($cartProduct['affiliate_user_id']) ? $cartProduct['affiliate_user_id'] : '';
                $order_affiliate_total_commission += isset($cartProduct['affiliate_commission']) ? $cartProduct['affiliate_commission'] : 0;

                $discount = 0;
                if (!empty($cartSummary["cartDiscounts"]["discountedSelProdIds"])) {
                    if (array_key_exists($productInfo['selprod_id'], $cartSummary["cartDiscounts"]["discountedSelProdIds"])) {
                        $discount = $cartSummary["cartDiscounts"]["discountedSelProdIds"][$productInfo['selprod_id']];
                    }
                }

                $shippingCost = $cartProduct['shipping_cost'];
                /* if ($cartProduct['shop_eligible_for_free_shipping'] && $cartProduct['psbs_user_id'] > 0) {
                    $shippingCost = 0;
                } */

                $rewardPoints = $orderData['order_reward_point_value'];
                $usedRewardPoint = 0;
                if ($rewardPoints > 0) {
                    $selProdAmount = ($cartProduct['quantity'] * $cartProduct['theprice']) + $shippingCost + $cartProduct['tax'] - $discount - $cartProduct['volume_discount_total'];
                    $usedRewardPoint = round((($rewardPoints * $selProdAmount) / ($orderData['order_net_amount'] + $rewardPoints)), 2);
                }

                $orderData['prodCharges'][CART::CART_KEY_PREFIX_PRODUCT . $productInfo['selprod_id']] = array(
                    OrderProduct::CHARGE_TYPE_SHIPPING => array(
                        'amount' => $shippingCost
                    ),
                    OrderProduct::CHARGE_TYPE_TAX => array(
                        'amount' => $cartProduct['tax']
                    ),
                    OrderProduct::CHARGE_TYPE_DISCOUNT => array(
                        'amount' => -$discount /*[Should be negative value]*/
                    ),
                    OrderProduct::CHARGE_TYPE_REWARD_POINT_DISCOUNT => array(
                        'amount' => -$usedRewardPoint
                    ),
                    /* OrderProduct::CHARGE_TYPE_BATCH_DISCOUNT => array(
                'amount' => -$cartProduct['batch_discount_single_product'] */
                    OrderProduct::CHARGE_TYPE_VOLUME_DISCOUNT => array(
                        'amount' => -$cartProduct['volume_discount_total']
                    ),

                );
                $totalRoundingOff += $cartProduct['rounding_off'];
            }
            $orderData['order_rounding_off'] = $totalRoundingOff;
        }
        $orderData['order_affiliate_user_id'] = $order_affiliate_user_id;
        $orderData['order_affiliate_total_commission'] = $order_affiliate_total_commission;
        /* ] */
        /* ] */

        $orderObj = new Orders();
        if ($orderObj->addUpdateOrder($orderData, $this->siteLangId)) {
            $order_id = $orderObj->getMainTableRecordId();
            $_SESSION['order_id'] = $order_id;
        } else {
            LibHelper::exitWithError($orderObj->getError());
        }

        $userWalletBalance = User::getUserBalance($userId, true);

        if (false === MOBILE_APP_API_CALL) {
            $confirmForm = $this->getConfirmFormWithNoAmount($this->siteLangId);

            if ($cartSummary['orderNetAmount'] <= 0) {
                $confirmForm->addFormTagAttribute('action', UrlHelper::generateUrl('ConfirmPay', 'Charge', array($order_id)));
                $confirmForm->fill(array('order_id' => $order_id));
                $confirmForm->addSubmitButton('', 'btn_submit', Labels::getLabel('BTN_CONFIRM_ORDER', $this->siteLangId));
            }
        }

        if ($userWalletBalance >= $cartSummary['orderNetAmount'] && $cartSummary['cartWalletSelected']) {
            $orderObj->updateOrderInfo($order_id, array('order_pmethod_id' => 0));
        }

        $walletPaymentForm = $this->getWalletPaymentForm($this->siteLangId);
        if ((FatUtility::convertToType($userWalletBalance, FatUtility::VAR_FLOAT) > 0) && $cartSummary['cartWalletSelected'] && $cartSummary['orderNetAmount'] > 0) {
            $orderId = $_SESSION['order_id'] ?? '';
            $walletPaymentForm->addFormTagAttribute('action', UrlHelper::generateUrl('WalletPay', 'Charge', array($orderId)));
            $walletPaymentForm->fill(array('order_id' => $orderId));
            $walletPaymentForm->setFormTagAttribute('onsubmit', 'confirmOrder(this); return(false);');
            $walletPaymentForm->addSubmitButton('', 'btn_submit', Labels::getLabel('BTN_PAY_NOW', $this->siteLangId));
        }
        $this->set('walletPaymentForm', $walletPaymentForm);

        $this->set('redeemRewardFrm', $this->getRewardsForm($this->siteLangId));
        $this->set('rewardPointBalance', UserRewardBreakup::rewardPointBalance($userId));

        $cartHasDigitalProduct = $this->cartObj->hasDigitalProduct();
        $this->set('paymentMethods', $paymentMethods);
        $this->set('userWalletBalance', $userWalletBalance);
        $this->set('cartSummary', $cartSummary);
        $this->set('fulfillmentType', $fulfillmentType);
        $this->set('cartHasDigitalProduct', $cartHasDigitalProduct);
        $excludePaymentGatewaysArr = applicationConstants::getExcludePaymentGatewayArr();
        $this->set('cartHasPhysicalProduct', $cartHasPhysicalProduct);
        $this->set('excludePaymentGatewaysArr', $excludePaymentGatewaysArr);
        if (false === MOBILE_APP_API_CALL) {
            $this->set('confirmForm', $confirmForm);
        }

        $this->set('canUseWalletForPayment', $canUseWallet);
        $this->set('shippingAddressId', $shippingAddressId);
        $this->set('billingAddressId', $billingAddressId);
        $this->set('billingAddressArr', $billingAddressArr);
        $this->set('shippingAddressArr', $shippingAddressArr);
        $this->set('orderId', $order_id);
        $this->set('opComments', $opComments);
        $this->set('orderType', $orderData['order_type']);

        if (true === MOBILE_APP_API_CALL) {
            $this->set('products', $cartProducts);
            if (0 < $useRewardPoints) {
                $this->set('msg', Labels::getLabel("MSG_USED_REWARD_POINT", $this->siteLangId) . '-' . $useRewardPoints);
                $this->_template->render(true, true, 'checkout/use-reward-points.php');
            }
            $this->_template->render();
        }

        $this->set('html', $this->_template->render(false, false, NULL, true));
        $this->_template->render(false, false, 'json-success.php', true, false);
    }

    public function paymentTab($order_id, $plugin_id)
    {
        $plugin_id = FatUtility::int($plugin_id);
        if (!$plugin_id) {
            LibHelper::exitWithError(Labels::getLabel("ERR_INVALID_REQUEST!", $this->siteLangId));
        }

        if (!UserAuthentication::isUserLogged() && !UserAuthentication::isGuestUserLogged()) {
            LibHelper::exitWithError(Labels::getLabel('ERR_YOUR_SESSION_SEEMS_TO_BE_EXPIRED.', $this->siteLangId));
        }
        $user_id = UserAuthentication::getLoggedUserId();

        $srch = Orders::getSearchObject();
        $srch->doNotCalculateRecords();
        $srch->setPageSize(1);
        $srch->addCondition('order_id', '=', $order_id);
        $srch->addCondition('order_payment_status', '=', 'mysql_func_' . Orders::ORDER_PAYMENT_PENDING, 'AND', true);
        $rs = $srch->getResultSet();
        $orderInfo = FatApp::getDb()->fetch($rs);
        if (!$orderInfo) {
            LibHelper::exitWithError(Labels::getLabel('ERR_INVALID_ORDER_PAID_CANCELLED', $this->siteLangId));
        }

        $methodCode = Plugin::getAttributesById($plugin_id, 'plugin_code');
        $this->plugin = LibHelper::callPlugin($methodCode, [$this->siteLangId], $error, $this->siteLangId);
        if (false === $this->plugin) {
            LibHelper::exitWithError($error);
        }
        $paymentMethod = $this->plugin->getSettings();

        $frm = '';
        if (isset($methodCode) &&  in_array(strtolower($methodCode), ['cashondelivery', 'payatstore']) && isset($paymentMethod["otp_verification"]) && 0 < $paymentMethod["otp_verification"]) {
            $userObj = new User($user_id);
            $userData = $userObj->getUserInfo([], false, false);
            $phoneNumber = $userData['user_phone'];
            $canSendSms = (!empty($phoneNumber) && SmsArchive::canSendSms(SmsTemplate::COD_OTP_VERIFICATION));

            $this->set('canSendSms', $canSendSms);
            $this->set('userData', $userData);

            $frm = $this->getOtpForm();
        }

        $frm = $this->getPaymentTabForm($this->siteLangId, $methodCode, $frm);
        $controller = $methodCode . 'Pay';
        $frm->setFormTagAttribute('action', UrlHelper::generateUrl($controller, 'charge', array($order_id)));
        $frm->setFormTagAttribute('data-method', $methodCode);
        $frm->setFormTagAttribute('data-external', UrlHelper::generateUrl($controller, 'getExternalLibraries'));

        $frm->fill(
            array(
                'order_type' => $orderInfo['order_type'],
                'order_id' => $order_id,
                'plugin_id' => $plugin_id,
            )
        );

        $this->set('orderId', $order_id);
        $this->set('pluginId', $plugin_id);
        $this->set('orderInfo', $orderInfo);
        $this->set('paymentMethod', $paymentMethod);
        $this->set('frm', $frm);
        /* Partial Payment is not allowed, Wallet + COD, So, disabling COD in case of Partial Payment Wallet Selected. [ */
        if (isset($methodCode) && in_array(strtolower($methodCode), ['cashondelivery', 'payatstore'])) {
            if ($this->cartObj->hasDigitalProduct()) {
                $str = Labels::getLabel('ERR_{COD}_IS_NOT_AVAILABLE_IF_YOUR_CART_HAS_ANY_DIGITAL_PRODUCT', $this->siteLangId);
                $str = str_replace('{cod}', $paymentMethod['plugin_name'], $str);
                LibHelper::exitWithError($str);
            }
            $cartSummary = $this->cartObj->getCartFinancialSummary($this->siteLangId);
            $user_id = UserAuthentication::getLoggedUserId();
            $userWalletBalance = User::getUserBalance($user_id, true);

            if (!$cartSummary['isCodEnabled']) {
                $str = Labels::getLabel('ERR_SORRY_{COD}_IS_NOT_AVAILABLE_ON_THIS_ORDER.', $this->siteLangId);
                $str = str_replace('{cod}', $paymentMethod['plugin_name'], $str);
                LibHelper::exitWithError($str);
            }

            if (1 > $cartSummary['isCodValidForNetAmt']) {
                $str = Labels::getLabel('ERR_SORRY_{COD}_IS_NOT_AVAILABLE_ON_THIS_ORDER.', $this->siteLangId) . ' <br/>' . Labels::getLabel('ERR_{COD}_IS_AVAILABLE_ON_PAYABLE_AMOUNT_BETWEEN_{MIN}_AND_{MAX}', $this->siteLangId);
                $str = CommonHelper::replaceStringData($str, ['{COD}' => $paymentMethod['plugin_name'], '{MIN}' => $cartSummary['min_cod_order_limit'], '{MAX}' => $cartSummary['max_cod_order_limit']]);
                LibHelper::exitWithError($str);
            }

            if ($cartSummary['cartWalletSelected'] && $userWalletBalance < $cartSummary['orderNetAmount']) {
                $str = Labels::getLabel('ERR_WALLET_CAN_NOT_BE_USED_ALONG_WITH_{COD}', $this->siteLangId);
                $str = str_replace('{cod}', $paymentMethod['plugin_name'], $str);
                LibHelper::exitWithError($str);
            }
        }
        /* ] */
        $this->set('html', $this->_template->render(false, false, NULL, true));
        $this->_template->render(false, false, 'json-success.php', true, false);
    }

    public function walletSelection()
    {
        $payFromWallet = FatApp::getPostedData('payFromWallet', FatUtility::VAR_INT, 0);
        $this->cartObj->updateCartWalletOption($payFromWallet);
        $this->_template->render(false, false, 'json-success.php');
    }

    /* Used through payment summary api to rid off session functionality in case of APP calling. */
    public function useRewardPoints(bool $return = false)
    {
        $loggedUserId = UserAuthentication::getLoggedUserId();
        $post = FatApp::getPostedData();

        if (empty($post)) {
            $this->errMessage = Labels::getLabel('ERR_INVALID_REQUEST', $this->siteLangId);
            if (true === MOBILE_APP_API_CALL) {
                FatUtility::dieJsonError($this->errMessage);
            }
            Message::addErrorMessage($this->errMessage);
            FatUtility::dieWithError(Message::getHtml());
        }
        $rewardPoints = floor($post['redeem_rewards']);

        if (empty($rewardPoints)) {
            $this->errMessage = Labels::getLabel('ERR_YOU_CANNOT_USE_0_REWARD_POINTS._PLEASE_ADD_REWARD_POINTS_GREATER_THAN_0', $this->siteLangId);
            if (true === MOBILE_APP_API_CALL) {
                FatUtility::dieJsonError($this->errMessage);
            }
            Message::addErrorMessage($this->errMessage);
            FatUtility::dieWithError(Message::getHtml());
        }

        $orderId = $_SESSION['order_id'] ?? '';
        if (true === MOBILE_APP_API_CALL) {
            if (empty($post['orderId'])) {
                FatUtility::dieJsonError(Labels::getLabel('ERR_ORDER_ID_IS_REQUIRED', $this->siteLangId));
            }
            $orderId = $post['orderId'];
        }

        $totalBalance = UserRewardBreakup::rewardPointBalance($loggedUserId, $orderId);

        if ($totalBalance == 0 || $totalBalance < $rewardPoints) {
            $this->errMessage = Labels::getLabel('ERR_INSUFFICIENT_REWARD_POINT_BALANCE', $this->siteLangId);
            FatUtility::dieJsonError($this->errMessage);
        }

        if (false == $return) {
            $this->cartObj = new Cart($loggedUserId, $this->siteLangId, $this->app_user['temp_user_id']);
        }

        $cartSummary = $this->cartObj->getCartFinancialSummary($this->siteLangId);

        $cartTotal = $cartSummary['cartTotal'] ?? 0;
        $cartDiscounts = $cartSummary['cartDiscounts']["coupon_discount_total"] ?? 0;
        $cartTotalWithoutDiscount = $cartTotal - $cartDiscounts;
        $canBeUse = min($totalBalance, CommonHelper::convertCurrencyToRewardPoint($cartTotal - $cartSummary['cartVolumeDiscount'] - $cartDiscounts));
        $canBeUse = min($canBeUse, FatApp::getConfig('CONF_MAX_REWARD_POINT', FatUtility::VAR_INT, 0));
        if ($canBeUse < $rewardPoints) {
            $this->errMessage = CommonHelper::replaceStringData(Labels::getLabel('ERR_YOU_ARE_NOT_ALLOWED_TO_USE_MORE_THAN_{REWARD}', $this->siteLangId), ['{REWARD}' => $canBeUse]);
            FatUtility::dieJsonError($this->errMessage);
        }

        $rewardPointValues = min(CommonHelper::convertRewardPointToCurrency($rewardPoints), $cartTotalWithoutDiscount);
        $rewardPoints = CommonHelper::convertCurrencyToRewardPoint($rewardPointValues);

        if ($rewardPoints < FatApp::getConfig('CONF_MIN_REWARD_POINT') || $rewardPoints > FatApp::getConfig('CONF_MAX_REWARD_POINT')) {
            $msg = Labels::getLabel('ERR_PLEASE_USE_REWARD_POINT_BETWEEN_{MIN}_TO_{MAX}', $this->siteLangId);
            $msg = CommonHelper::replaceStringData($msg, array('{MIN}' => FatApp::getConfig('CONF_MIN_REWARD_POINT'), '{MAX}' => FatApp::getConfig('CONF_MAX_REWARD_POINT')));
            LibHelper::dieJsonError($msg);
        }
        if (!$this->cartObj->updateCartUseRewardPoints($rewardPoints)) {
            $this->errMessage = Labels::getLabel('ERR_ACTION_TRYING_PERFORM_NOT_VALID', $this->siteLangId);
            if (true === MOBILE_APP_API_CALL) {
                FatUtility::dieJsonError($this->errMessage);
            }
            Message::addErrorMessage($this->errMessage);
            FatUtility::dieWithError(Message::getHtml());
        }

        if (true === $return) {
            return true;
        }

        $this->set('msg', Labels::getLabel("MSG_USED_REWARD_POINT", $this->siteLangId) . '-' . $rewardPoints);
        if (true === MOBILE_APP_API_CALL) {
            $cartSummary = $this->cartObj->getCartFinancialSummary($this->siteLangId);
            $cartProducts = $this->cartObj->getProducts($this->siteLangId);
            $dataToUpdate = [
                'order_id' => $orderId,
                'order_number' => Orders::getAttributesById($orderId, 'order_number'),
                'order_user_id' => UserAuthentication::getLoggedUserId(true),
                'order_net_amount' => $cartSummary["orderNetAmount"],
                'order_type' => Orders::ORDER_PRODUCT
            ];
            $orderObj = new Orders();
            $orderObj->addUpdateOrder($dataToUpdate, $this->siteLangId);

            $this->set('cartSummary', $cartSummary);
            $this->set('products', $cartProducts);
            $this->_template->render();
        }

        $this->_template->render(false, false, 'json-success.php');
    }

    public function removeRewardPoints()
    {
        $cartObj = new Cart(UserAuthentication::getLoggedUserId(true), $this->siteLangId, $this->app_user['temp_user_id']);
        if (!$cartObj->removeUsedRewardPoints()) {
            LibHelper::exitWithError(Labels::getLabel('ERR_ACTION_TRYING_PERFORM_NOT_VALID', $this->siteLangId));
        }
        $this->set('msg', Labels::getLabel("MSG_USED_REWARD_POINT_REMOVED", $this->siteLangId));
        if (true === MOBILE_APP_API_CALL) {
            $orderId = FatApp::getPostedData('orderId', FatUtility::VAR_STRING, '');
            if (empty($orderId)) {
                LibHelper::exitWithError(Labels::getLabel('ERR_ORDER_ID_IS_REQUIRED', $this->siteLangId));
            }

            $cartSummary = $cartObj->getCartFinancialSummary($this->siteLangId);
            $cartProducts = $cartObj->getProducts($this->siteLangId);

            $dataToUpdate = [
                'order_id' => $orderId,
                'order_number' => Orders::getAttributesById($orderId, 'order_number'),
                'order_user_id' => UserAuthentication::getLoggedUserId(true),
                'order_net_amount' => $cartSummary["orderNetAmount"],
                'order_type' => Orders::ORDER_PRODUCT
            ];
            $orderObj = new Orders();
            $orderObj->addUpdateOrder($dataToUpdate, $this->siteLangId);

            $this->set('cartSummary', $cartSummary);
            $this->set('products', $cartProducts);
            $this->_template->render(true, true, 'checkout/use-reward-points.php');
        }
        $this->_template->render(false, false, 'json-success.php');
    }

    public function confirmOrder()
    {
        $order_type = FatApp::getPostedData('order_type', FatUtility::VAR_INT, 0);
        $plugin_id = FatApp::getPostedData('plugin_id', FatUtility::VAR_INT, 0);
        $order_id = FatApp::getPostedData("order_id", FatUtility::VAR_STRING, "");
        $user_id = UserAuthentication::getLoggedUserId();

        if (Orders::ORDER_GIFT_CARD == $order_type) {
            $cartSummary = $this->cartObj->getCartGiftFinancialSummary($this->siteLangId, $order_id);
        } else {
            $cartSummary = $this->cartObj->getCartFinancialSummary($this->siteLangId);
        }
        $userWalletBalance = FatUtility::convertToType(User::getUserBalance($user_id, true), FatUtility::VAR_FLOAT);
        $orderNetAmount = isset($cartSummary['orderNetAmount']) ? FatUtility::convertToType($cartSummary['orderNetAmount'], FatUtility::VAR_FLOAT) : 0;

        if (0 < $plugin_id) {
            $paymentMethodRow = Plugin::getAttributesById($plugin_id);
            $isActive = $paymentMethodRow['plugin_active'];
            $pmethodCode = $paymentMethodRow['plugin_code'];
            $pmethodIdentifier = $paymentMethodRow['plugin_identifier'];

            if (!$paymentMethodRow || $isActive != applicationConstants::ACTIVE) {
                $this->errMessage = Labels::getLabel("ERR_INVALID_PAYMENT_METHOD,_PLEASE_CONTACT_WEBADMIN.", $this->siteLangId);
                LibHelper::dieJsonError($this->errMessage);
            }
        }

        if (!empty($pmethodCode) && in_array(strtolower($pmethodCode), ['cashondelivery', 'payatstore']) && $cartSummary['cartWalletSelected'] && $userWalletBalance < $orderNetAmount) {
            $str = Labels::getLabel('ERR_WALLET_CAN_NOT_BE_USED_ALONG_WITH_{COD}', $this->siteLangId);
            $str = str_replace('{cod}', $pmethodIdentifier, $str);
            LibHelper::dieJsonError($str);
        }

        if (true === MOBILE_APP_API_CALL) {
            $paymentUrl = '';
            $sendToWeb = 1;
            if (0 < $plugin_id) {
                $controller = $pmethodCode . 'Pay';
                $paymentUrl = UrlHelper::generateFullUrl($controller, 'charge', array($order_id));
            }
            if (
                Orders::ORDER_WALLET_RECHARGE != $order_type &&
                (
                    $cartSummary['cartWalletSelected'] ||
                    (
                        Orders::ORDER_GIFT_CARD == $order_type &&
                        0 < $this->cartObj->isCartUserGiftWalletSelected()
                    )
                ) &&
                $userWalletBalance >= $orderNetAmount
            ) {
                $sendToWeb = $plugin_id = 0;
                $paymentUrl = UrlHelper::generateFullUrl('WalletPay', 'charge', array($order_id));
            }
            if (empty($paymentUrl)) {
                LibHelper::dieJsonError(Labels::getLabel('ERR_PLEASE_SELECT_PAYMENT_METHOD', $this->siteLangId));
            }
            $this->set('sendToWeb', $sendToWeb);
            $this->set('orderPayment', $paymentUrl);
        }

        /* Loading Money to wallet[ */
        if ($order_type == Orders::ORDER_WALLET_RECHARGE) {
            $criteria = array('isUserLogged' => true);
            if (!$this->isEligibleForNextStep($criteria)) {
                $this->errMessage = Labels::getLabel('ERR_SOMETHING_WENT_WRONG,_PLEASE_TRY_AFTER_SOME_TIME.', $this->siteLangId);
                LibHelper::dieJsonError($this->errMessage);
            }

            $user_id = UserAuthentication::getLoggedUserId();

            if ($order_id == '') {
                $this->errMessage = Labels::getLabel("ERR_INVALID_REQUEST", $this->siteLangId);
                LibHelper::dieJsonError($this->errMessage);
            }
            $orderObj = new Orders();

            $srch = Orders::getSearchObject();
            $srch->doNotCalculateRecords();
            $srch->setPageSize(1);
            $srch->addCondition('order_id', '=', $order_id);
            $srch->addCondition('order_user_id', '=', 'mysql_func_' . $user_id, 'AND', true);
            $srch->addCondition('order_payment_status', '=', 'mysql_func_' . Orders::ORDER_PAYMENT_PENDING, 'AND', true);
            $srch->addCondition('order_type', '=', 'mysql_func_' . Orders::ORDER_WALLET_RECHARGE, 'AND', true);
            $rs = $srch->getResultSet();
            $orderInfo = FatApp::getDb()->fetch($rs);
            if (!$orderInfo) {
                $this->errMessage = Labels::getLabel("ERR_INVALID_ORDER_PAID_CANCELLED", $this->siteLangId);
                LibHelper::dieJsonError($this->errMessage);
            }

            //No Need to clear cart in case of wallet recharge
            /*$this->cartObj->clear();
            $this->cartObj->updateUserCart();*/

            $orderObj->updateOrderInfo($order_id, array('order_pmethod_id' => $plugin_id));

            if (true === MOBILE_APP_API_CALL) {
                $this->_template->render();
            }
            $this->_template->render(false, false, 'json-success.php');
        }
        /* ] */

        /* Loading GIFT CARDS[ */
        if ($order_type == Orders::ORDER_GIFT_CARD) {
            $criteria = array('isUserLogged' => true);
            if (!$this->isEligibleForNextStep($criteria)) {
                $this->errMessage = Labels::getLabel('ERR_SOMETHING_WENT_WRONG,_PLEASE_TRY_AFTER_SOME_TIME.', $this->siteLangId);
                LibHelper::dieJsonError($this->errMessage);
            }

            $user_id = UserAuthentication::getLoggedUserId();

            if ($order_id == '') {
                $this->errMessage = Labels::getLabel("ERR_INVALID_REQUEST", $this->siteLangId);
                LibHelper::dieJsonError($this->errMessage);
            }
            $orderObj = new Orders();

            $srch = Orders::getSearchObject();
            $srch->doNotCalculateRecords();
            $srch->setPageSize(1);
            $srch->addCondition('order_id', '=', $order_id);
            $srch->addCondition('order_user_id', '=', 'mysql_func_' . $user_id, 'AND', true);
            $srch->addCondition('order_payment_status', '=', 'mysql_func_' . Orders::ORDER_PAYMENT_PENDING, 'AND', true);
            $srch->addCondition('order_type', '=', 'mysql_func_' . Orders::ORDER_GIFT_CARD, 'AND', true);
            $rs = $srch->getResultSet();
            $orderInfo = FatApp::getDb()->fetch($rs);
            if (!$orderInfo) {
                $this->errMessage = Labels::getLabel("ERR_INVALID_ORDER_PAID_CANCELLED", $this->siteLangId);
                LibHelper::dieJsonError($this->errMessage);
            }
            $orderObj->updateOrderInfo($order_id, array('order_pmethod_id' => $plugin_id));
            if (true === MOBILE_APP_API_CALL) {
                $this->_template->render();
            }
            $this->_template->render(false, false, 'json-success.php');
        }
        /* Loading GIFT CARDS[ */



        /* confirmOrder function is called for both wallet payments and for paymentgateway selection as well. */
        $criteria = array('isUserLogged' => true, 'hasProducts' => true, 'hasStock' => true, 'hasBillingAddress' => true);
        $fulfillmentType = $this->cartObj->getCartCheckoutType();
        if ($this->cartObj->hasPhysicalProduct() && $fulfillmentType == Shipping::FULFILMENT_SHIP) {
            $criteria['hasShippingAddress'] = true;
            $criteria['isProductShippingMethodSet'] = true;
        }
        if ($fulfillmentType == Shipping::FULFILMENT_PICKUP) {
            $criteria['isProductPickUpAddrSet'] = true;
        }

        if (!$this->isEligibleForNextStep($criteria)) {
            $this->errMessage = Labels::getLabel('ERR_SOMETHING_WENT_WRONG,_PLEASE_TRY_AFTER_SOME_TIME.', $this->siteLangId);
            LibHelper::dieJsonError($this->errMessage);
        }

        if ($cartSummary['cartWalletSelected'] && $userWalletBalance >= $orderNetAmount && !$plugin_id) {
            if (true === MOBILE_APP_API_CALL) {
                $this->_template->render();
            }
            $this->_template->render(false, false, 'json-success.php');
            exit;
        }

        $post = FatApp::getPostedData();

        if (!$paymentMethodRow || $isActive != applicationConstants::ACTIVE) {
            $this->errMessage = Labels::getLabel("ERR_INVALID_PAYMENT_METHOD,_PLEASE_CONTACT_WEBADMIN.", $this->siteLangId);
            LibHelper::dieJsonError($this->errMessage);
        }

        if (false === MOBILE_APP_API_CALL && isset($pmethodCode) && in_array(strtolower($pmethodCode), ['cashondelivery', 'payatstore']) && FatApp::getConfig('CONF_RECAPTCHA_SITEKEY', FatUtility::VAR_STRING, '' && FatApp::getConfig('CONF_RECAPTCHA_SECRETKEY', FatUtility::VAR_STRING, '') != '')) {
            if (!CommonHelper::verifyCaptcha()) {
                LibHelper::dieJsonError(Labels::getLabel('ERR_THAT_CAPTCHA_WAS_INCORRECT', $this->siteLangId));
            }
        }

        if ($userWalletBalance >= $cartSummary['orderNetAmount'] && $cartSummary['cartWalletSelected'] && !$plugin_id) {
            $frm = $this->getWalletPaymentForm($this->siteLangId);
        } else {
            $frm = $this->getPaymentTabForm($this->siteLangId);
        }

        $post = $frm->getFormDataFromArray($post);
        if (!isset($post['order_id']) || $post['order_id'] == '') {
            $this->errMessage = Labels::getLabel('ERR_INVALID_REQUEST', $this->siteLangId);
            LibHelper::dieJsonError($this->errMessage);
        }

        $orderObj = new Orders();
        $order_id = $post['order_id'];

        $srch = Orders::getSearchObject();
        $srch->doNotCalculateRecords();
        $srch->setPageSize(1);
        $srch->addCondition('order_id', '=', $order_id);
        $srch->addCondition('order_user_id', '=', 'mysql_func_' . $user_id, 'AND', true);
        $srch->addCondition('order_payment_status', '=', 'mysql_func_' . Orders::ORDER_PAYMENT_PENDING, 'AND', true);
        $rs = $srch->getResultSet();
        $orderInfo = FatApp::getDb()->fetch($rs);

        if (!$orderInfo) {
            $this->errMessage = Labels::getLabel('ERR_INVALID_ORDER_PAID_CANCELLED', $this->siteLangId);
            LibHelper::dieJsonError($this->errMessage);
        }
        if ($cartSummary['cartWalletSelected'] && $cartSummary['orderPaymentGatewayCharges'] == 0) {
            $this->errMessage = Labels::getLabel('ERR_TRY_TO_PAY_USING_WALLET_BALANCE_AS_AMOUNT_FOR_PAYMENT_GATEWAY_IS_NOT_ENOUGH.', $this->siteLangId);
            LibHelper::dieJsonError($this->errMessage);
        }

        if ($cartSummary['orderPaymentGatewayCharges'] == 0 && $plugin_id) {
            $this->errMessage = Labels::getLabel('ERR_AMOUNT_FOR_PAYMENT_GATEWAY_MUST_BE_GREATER_THAN_ZERO.', $this->siteLangId);
            LibHelper::dieJsonError($this->errMessage);
        }

        if ($plugin_id) {
            $_SESSION['cart_order_id'] = $order_id;
            $_SESSION['order_type'] = $order_type;
            $orderObj->updateOrderInfo($order_id, array('order_pmethod_id' => $plugin_id));
            // $this->cartObj->clear();
            // $this->cartObj->updateUserCart();
        }

        /* Deduct reward point in case of cashondelivery [ */
        if (isset($pmethodCode) && in_array(strtolower($pmethodCode), ['cashondelivery', 'payatstore']) && $orderInfo['order_reward_point_used'] > 0) {
            $rewardDebited = UserRewards::debit($orderInfo['order_user_id'], $orderInfo['order_reward_point_used'], $order_id, $orderInfo['order_language_id']);
            if (!$rewardDebited) {
                $msg = Labels::getLabel("ERR_UNABLE_TO_DEBIT_REWARD_POINTS", $this->siteLangId);
                LibHelper::dieJsonError($msg);
            }
        }

        /*]*/

        if (true === MOBILE_APP_API_CALL) {
            $this->_template->render();
        }
        $this->_template->render(false, false, 'json-success.php');
    }

    public function editAddress()
    {
        $post = FatApp::getPostedData();
        $address_id = isset($post['address_id']) ? FatUtility::int($post['address_id']) : 0;
        $addressFrm = $this->getUserAddressForm($this->siteLangId, true);

        $address = new Address($address_id, $this->siteLangId);
        $addresses = $address->getData(Address::TYPE_USER, UserAuthentication::getLoggedUserId());

        $shippingAddressId = $this->cartObj->getCartShippingAddress();
        $addresses['shipping_addr_id'] = $shippingAddressId;

        $stateId = 0;
        if ($address_id) {
            $stateId = $addresses['addr_state_id'];
        }
        $addressFrm->fill($addresses);
        $this->set('addressFrm', $addressFrm);
        $this->set('address_id', $address_id);
        $labelHeading = Labels::getLabel('MSG_ADD_ADDRESS', $this->siteLangId);
        if ($address_id > 0) {
            $labelHeading = Labels::getLabel('MSG_EDIT_ADDRESS', $this->siteLangId);
        }

        $cartHasPhysicalProduct = false;
        if ($this->cartObj->hasPhysicalProduct()) {
            $cartHasPhysicalProduct = true;
        }

        $this->set('cartHasPhysicalProduct', $cartHasPhysicalProduct);
        $this->set('labelHeading', $labelHeading);
        $this->set('stateId', $stateId);
        $addressType = FatApp::getPostedData('address_type', FatUtility::VAR_INT, 0);
        $this->set('addressType', $addressType);
        $this->set('address_id', $address_id);
        $this->_template->render(false, false, 'checkout/address-form.php');
    }

    private function getCheckoutAddressForm($langId)
    {
        $frm = new Form('frmAddress');
        $address = new Address(0, $langId);
        $addresses = $address->getData(Address::TYPE_USER, UserAuthentication::getLoggedUserId());

        $addressAssoc = array();
        foreach ($addresses as $address) {
            $city = $address['addr_city'];
            $state = (strlen($address['addr_city']) > 0) ? ', ' . $address['state_name'] : $address['state_name'];
            $country = (strlen($state) > 0) ? ', ' . $address['country_name'] : $address['country_name'];
            $location = $city . $state . $country;
            $addressAssoc[$address['addr_id']] = $location;
        }
        $frm->addRadioButtons('', 'shipping_address_id', $addressAssoc);
        $frm->addRadioButtons('', 'billing_address_id', $addressAssoc);
        return $frm;
    }

    private function getPaymentTabForm($langId, $paymentMethodCode = '', $externalFrm = '')
    {
        $frm = $externalFrm;
        if (empty($externalFrm)) {
            $frm = new Form('frmPaymentTabForm');
        }

        $frm->setFormTagAttribute('id', 'frmPaymentTabForm');

        if (in_array(strtolower($paymentMethodCode), ["cashondelivery", "payatstore"])) {
            CommonHelper::addCaptchaField($frm);
        }

        $frm->addHiddenField('', 'order_type');
        $frm->addHiddenField('', 'order_id');
        $frm->addHiddenField('', 'plugin_id');
        if (empty($externalFrm)) {
            $frm->addSubmitButton('', 'btn_submit', Labels::getLabel('BTN_CONFIRM', $langId));
        }
        return $frm;
    }

    private function getWalletPaymentForm($langId)
    {
        $frm = new Form('frmWalletPayment');
        $frm->addHiddenField('', 'order_id');
        return $frm;
    }

    private function getConfirmFormWithNoAmount($langId)
    {
        $frm = new Form('frmConfirmForm');
        $frm->addHiddenField('', 'order_id');
        return $frm;
    }

    private function getRewardsForm($langId)
    {
        $langId = FatUtility::int($langId);
        $frm = new Form('frmRewards');
        $fld = $frm->addTextBox(Labels::getLabel('FRM_Reward_Points', $langId), 'redeem_rewards', '', array());
        $fld->requirements()->setRequired();

        $frm->addHtml('', 'btn_submit', HtmlHelper::addButtonHtml(Labels::getLabel('BTN_APPLY', $langId), 'submit', 'btn_submit', 'btn-apply'));
        return $frm;
    }

    public function resetShippingSummary()
    {
        $this->_template->render(false, false);
    }

    public function resetCartReview()
    {
        $cartHasPhysicalProduct = false;
        if ($this->cartObj->hasPhysicalProduct()) {
            $cartHasPhysicalProduct = true;
        }
        $this->set('cartHasPhysicalProduct', $cartHasPhysicalProduct);
        $this->_template->render(false, false);
    }

    public function resetPaymentSummary()
    {
        $cartHasPhysicalProduct = false;
        if ($this->cartObj->hasPhysicalProduct()) {
            $cartHasPhysicalProduct = true;
        }
        $this->set('cartHasPhysicalProduct', $cartHasPhysicalProduct);
        $this->_template->render(false, false);
    }

    public function loadCartReview()
    {
        $cartHasPhysicalProduct = false;
        if ($this->cartObj->hasPhysicalProduct()) {
            $cartHasPhysicalProduct = true;
        }
        $products = $this->cartObj->getProducts($this->siteLangId);
        $this->set('cartHasPhysicalProduct', $cartHasPhysicalProduct);
        $this->set('products', $products);
        $this->set('html', $this->_template->render(false, false, NULL, true));
        $this->_template->render(false, false, 'json-success.php', true, false);
    }

    public function loadShippingSummary()
    {
        $products = $this->cartObj->getProducts($this->siteLangId);
        $this->set('products', $products);
        $this->_template->render(false, false);
    }

    public function removeShippingSummary()
    {
        $this->cartObj->removeProductShippingMethod();
    }

    public function getFinancialSummary(int $isShippingSelected = 0)
    {
        $userId = UserAuthentication::getLoggedUserId();
        $cartSummary = $this->cartObj->getCartFinancialSummary($this->siteLangId);
        $products = $this->cartObj->getProducts($this->siteLangId);
        $shippingAddress = $this->cartObj->getCartShippingAddress();
        $userWalletBalance = User::getUserBalance($userId, true);

        $fulfillmentType = $this->cartObj->getCartCheckoutType();
        /* Payment Methods[ */
        $splitPaymentMethodsPlugins = Plugin::getDataByType(Plugin::TYPE_SPLIT_PAYMENT_METHOD, $this->siteLangId);
        $regularPaymentMethodsPlugins = Plugin::getDataByType(Plugin::TYPE_REGULAR_PAYMENT_METHOD, $this->siteLangId);

        if ($fulfillmentType == Shipping::FULFILMENT_PICKUP) {
            $codPlugInId = Plugin::getAttributesByCode('CashOnDelivery', 'plugin_id');
            if (array_key_exists($codPlugInId, $regularPaymentMethodsPlugins)) {
                unset($regularPaymentMethodsPlugins[$codPlugInId]);
            }
        } else if ($fulfillmentType == Shipping::FULFILMENT_SHIP) {
            $codPlugInId = Plugin::getAttributesByCode('PayAtStore', 'plugin_id');
            if (array_key_exists($codPlugInId, $regularPaymentMethodsPlugins)) {
                unset($regularPaymentMethodsPlugins[$codPlugInId]);
            }
        }

        $paymentMethods = array_merge($splitPaymentMethodsPlugins, $regularPaymentMethodsPlugins);
        $this->set('paymentMethods', $paymentMethods);
        if (0 < $isShippingSelected && 1 > count($paymentMethods)) {
            $this->set('shippingAddressId', $this->cartObj->getCartShippingAddress());

            $billingAddressId = $this->cartObj->getCartBillingAddress();
            $billingAddressArr = [];
            if ($billingAddressId) {
                $address = new Address($billingAddressId, $this->siteLangId);
                $billingAddressArr = $address->getData(Address::TYPE_USER, $userId);
            }
            $this->set('billingAddressId', $billingAddressId);
            $this->set('billingAddressArr', $billingAddressArr);
        }
        /* ] */

        $this->set('shippingAddress', $shippingAddress);
        $this->set('isShippingSelected', $isShippingSelected);
        $this->set('products', $products);
        $this->set('cartSummary', $cartSummary);
        $this->set('cartHasPhysicalProduct', $this->cartObj->hasPhysicalProduct());
        $this->set('fulfillmentType', $fulfillmentType);
        $this->set('netAmount', CommonHelper::displayMoneyFormat($cartSummary['orderNetAmount']));
        $this->set('userWalletBalance', $userWalletBalance);
        $this->set('canUseWalletForPayment', PaymentMethods::canUseWalletForPayment());
        $this->set('html', $this->_template->render(false, false, 'checkout/get-financial-summary.php', true));
        $this->_template->render(false, false, 'json-success.php', false, false);
    }

    public function getCoupons()
    {
        $loggedUserId = UserAuthentication::getLoggedUserId();
        $orderId = isset($_SESSION['order_id']) ? $_SESSION['order_id'] : '';
        $couponsList = 0 < $loggedUserId ? DiscountCoupons::getValidCoupons($loggedUserId, $this->siteLangId, '', $orderId) : [];
        $this->set('couponsList', $couponsList);

        if (true === MOBILE_APP_API_CALL) {
            $this->_template->render();
        }
        $this->set('html', $this->_template->render(false, false, NULL, true));
        $this->_template->render(false, false, 'json-success.php', true, false);
    }

    public function setUpPickUp()
    {
        $this->cartObj->removeProductShippingMethod();
        $post = FatApp::getPostedData();

        $pickupAddressArr = array();
        // $basketProducts = $this->cartObj->getBasketProducts($this->siteLangId);
        $basketProducts = [];
        $pickupOptions = $this->cartObj->getPickupOptions($basketProducts);

        foreach ($post['slot_id'] as $pickUpBy => $slotId) {
            if (empty($slotId) || empty($post['slot_date'][$pickUpBy])) {
                $message = Labels::getLabel('ERR_PICKUP_METHOD_IS_NOT_SELECTED_ON_PRODUCTS_IN_CART', $this->siteLangId);
                LibHelper::exitWithError($message, true);
            }

            $slotData = TimeSlot::getAttributesById($slotId);
            if (empty($slotData)) {
                $message = Labels::getLabel('ERR_NO_TIME_SLOT_FOUND.', $this->siteLangId);
                LibHelper::exitWithError($message, true);
            }

            $selectedDate = $post['slot_date'][$pickUpBy];
            $selectedDay = date('w', strtotime($selectedDate));
            if ($selectedDay != $slotData['tslot_day']) {
                $message = Labels::getLabel('ERR_INVALID_SLOT_DAY.', $this->siteLangId);
                LibHelper::exitWithError($message, true);
            }

            if (array_search($slotData['tslot_record_id'], array_column($pickupOptions[$pickUpBy]['pickup_options'], 'addr_id')) === false) {
                $message = Labels::getLabel('ERR_INVALID_PICKUP_ADDRESS.', $this->siteLangId);
                LibHelper::exitWithError($message, true);
            }

            $cartProducts = $this->cartObj->getProducts($this->siteLangId);
            if (empty($cartProducts)) {
                $message = Labels::getLabel('ERR_YOUR_CART_IS_EMPTY', $this->siteLangId);
                LibHelper::exitWithError($message, true);
            }

            foreach ($pickupOptions[$pickUpBy]['products'] as $pickupProduct) {
                foreach ($cartProducts as $cartKey => $cartval) {
                    if ($cartval['selprod_id'] != $pickupProduct['selprod_id'] || in_array($cartval['product_type'], [Product::PRODUCT_TYPE_DIGITAL, Product::PRODUCT_TYPE_SERVICE])) {
                        continue;
                    }
                    /* get Product Data[ */
                    $prodSrch = new ProductSearch();
                    $prodSrch->setDefinedCriteria(0, 0, ['doNotJoinSellers' => true, 'doNotJoinSpecialPrice' => true]);
                    $prodSrch->joinProductToCategory();
                    $prodSrch->joinProductShippedBy();
                    $prodSrch->joinProductFreeShipping();
                    $prodSrch->joinSellerSubscription();
                    $prodSrch->addSubscriptionValidCondition();
                    $prodSrch->doNotCalculateRecords();
                    $prodSrch->setPageSize(1);
                    $prodSrch->addCondition('selprod_deleted', '=', 'mysql_func_' . applicationConstants::NO, 'AND', true);
                    $prodSrch->addCondition('selprod_id', '=', 'mysql_func_' . $cartval['selprod_id'], 'AND', true);
                    $prodSrch->addMultipleFields(array('selprod_id', 'product_seller_id', 'psbs_user_id as shippedBySellerId'));
                    $productRs = $prodSrch->getResultSet();
                    $productInfo = FatApp::getDb()->fetch($productRs);
                    /* ] */
                    $pickupAddressArr[$cartval['selprod_id']] = array(
                        'selprod_id' => $cartval['selprod_id'],
                        'shipped_by_seller' => Product::isShippedBySeller($cartval['selprod_user_id'], $productInfo['product_seller_id'], $productInfo['shippedBySellerId']),
                        'time_slot_addr_id' => $slotData['tslot_record_id'],
                        'time_slot_id' => $slotData['tslot_id'],
                        'time_slot_type' => $slotData['tslot_type'],
                        'time_slot_from_time' => $slotData['tslot_from_time'],
                        'time_slot_to_time' => $slotData['tslot_to_time'],
                        'time_slot_date' => $selectedDate,
                    );
                }
            }
        }

        $this->cartObj->setProductPickUpAddresses($pickupAddressArr);
        $this->set('msg', Labels::getLabel('MSG_PICKUP_METHOD_SELECTED_SUCCESSFULLY.', $this->siteLangId));
        if (true === MOBILE_APP_API_CALL) {
            $this->_template->render();
        }
        $this->_template->render(false, false, 'json-success.php');
    }

    public function setUpBillingAddressSelection()
    {
        if (!UserAuthentication::isUserLogged() && !UserAuthentication::isGuestUserLogged()) {
            $this->errMessage = Labels::getLabel('ERR_YOUR_SESSION_SEEMS_TO_BE_EXPIRED.', $this->siteLangId);
            if (true === MOBILE_APP_API_CALL) {
                FatUtility::dieJsonError($this->errMessage);
            }
            $this->set('redirectUrl', UrlHelper::generateUrl('GuestUser', 'LoginForm', [], CONF_WEBROOT_FRONTEND));
            Message::addErrorMessage($this->errMessage);
            FatUtility::dieWithError(Message::getHtml());
        }

        $billing_address_id = FatApp::getPostedData('billing_address_id', FatUtility::VAR_INT, 0);
        $isShippingSameAsBilling = FatApp::getPostedData('isShippingSameAsBilling', FatUtility::VAR_INT, 0);

        $hasProducts = $this->cartObj->hasProducts();
        $hasStock = $this->cartObj->hasStock();
        if ((!$hasProducts) || (!$hasStock)) {
            $this->errMessage = Labels::getLabel('ERR_CART_SEEMS_TO_BE_EMPTY_OR_PRODUCTS_ARE_OUT_OF_STOCK.', $this->siteLangId);
            if (true === MOBILE_APP_API_CALL) {
                FatUtility::dieJsonError($this->errMessage);
            }
            $this->set('redirectUrl', UrlHelper::generateUrl('cart'));
            Message::addErrorMessage($this->errMessage);
            FatUtility::dieWithError(Message::getHtml());
        }

        if (1 > $billing_address_id) {
            $this->errMessage = Labels::getLabel('ERR_PLEASE_SELECT_BILLING_ADDRESS.', $this->siteLangId);
            if (true === MOBILE_APP_API_CALL) {
                FatUtility::dieJsonError($this->errMessage);
            }
            Message::addErrorMessage($this->errMessage);
            FatUtility::dieWithError(Message::getHtml());
        }

        $address = new Address($billing_address_id);
        $billingAddressDetail = $address->getData(Address::TYPE_USER, UserAuthentication::getLoggedUserId());
        if (!$billingAddressDetail) {
            $this->errMessage = Labels::getLabel('ERR_INVALID_BILLING_ADDRESS.', $this->siteLangId);
            if (true === MOBILE_APP_API_CALL) {
                FatUtility::dieJsonError($this->errMessage);
            }
            Message::addErrorMessage($this->errMessage);
            FatUtility::dieWithError(Message::getHtml());
        }
        $this->cartObj->setCartBillingAddress($billingAddressDetail['addr_id']);

        if ($isShippingSameAsBilling == 0) {
            $this->cartObj->unSetShippingAddressSameAsBilling();
        }

        if (true === MOBILE_APP_API_CALL) {
            $this->_template->render();
        }
        $this->set('msg', Labels::getLabel('MSG_ADDRESS_SELECTION_SUCCESSFULLY', $this->siteLangId));
        $this->_template->render(false, false, 'json-success.php');
    }

    public function orderPickUpData()
    {
        $orderId = FatApp::getPostedData('order_id', FatUtility::VAR_STRING, '');
        $order = new Orders();
        $orderPickUpData = $order->getOrderPickUpData($orderId, $this->siteLangId);
        $this->set('orderPickUpData', $orderPickUpData);
        $this->_template->render(false, false);
    }

    public function resendOtp()
    {
        $userId = UserAuthentication::getLoggedUserId();
        $userObj = new User($userId);
        $userData = $userObj->getUserInfo([], false, false);
        $phoneNumber = $userData['user_phone'];

        $canSendSms = (!empty($phoneNumber) && SmsArchive::canSendSms(SmsTemplate::COD_OTP_VERIFICATION));

        $otp = '';
        if (true == $canSendSms) {
            if (false == $userObj->resendOtp(SmsTemplate::COD_OTP_VERIFICATION)) {
                FatUtility::dieJsonError($userObj->getError());
            }
            $data = $userObj->getOtpDetail();
            $otp = $data['upv_otp'];
        }

        if (empty($otp)) {
            $min = pow(10, User::OTP_LENGTH - 1);
            $max = pow(10, User::OTP_LENGTH) - 1;
            $otp = mt_rand($min, $max);
        }

        if (false === $userObj->prepareUserVerificationCode($userData['credential_email'], $userId . '_' . $otp)) {
            FatUtility::dieWithError($userObj->getError());
        }

        $replace = [
            'user_name' => $userData['user_name'],
            'otp' => $otp,
            'credential_email' => $userData['credential_email'],
        ];

        $email = new EmailHandler();
        if (false === $email->sendCodOtpVerification($this->siteLangId, $replace)) {
            FatUtility::dieWithError($userObj->getError());
        }

        $this->set('msg', Labels::getLabel('MSG_OTP_SENT!', $this->siteLangId));
        $this->_template->render(false, false, 'json-success.php');
    }

    public function validateOtp()
    {
        $user_id = UserAuthentication::getLoggedUserId();
        $userObj = new User($user_id);
        $userData = $userObj->getUserInfo([], false, false);
        $phoneNumber = $userData['user_phone'];

        $canSendSms = (!empty($phoneNumber) && SmsArchive::canSendSms(SmsTemplate::COD_OTP_VERIFICATION));

        $verified = false;
        if (true == $canSendSms) {
            $this->validateOtpApi(0, false);
            $verified = true;
        }

        if (false === $verified) {
            $db = FatApp::getDb();
            $db->startTransaction();

            $otpFrm = $this->getOtpForm();
            $post = $otpFrm->getFormDataFromArray(FatApp::getPostedData());
            if (false === $post) {
                LibHelper::dieJsonError(current($otpFrm->getValidationErrors()));
            }

            if (true === MOBILE_APP_API_CALL) {
                if (User::OTP_LENGTH != strlen($post['upv_otp'])) {
                    LibHelper::dieJsonError(Labels::getLabel('ERR_INVALID_OTP', $this->siteLangId));
                }
                $otp = $post['upv_otp'];
            } else {
                if (!is_array($post['upv_otp']) || User::OTP_LENGTH != count($post['upv_otp'])) {
                    LibHelper::dieJsonError(Labels::getLabel('ERR_INVALID_OTP', $this->siteLangId));
                }
                $otp = implode("", $post['upv_otp']);
            }

            if (!$userObj->verifyUserEmailVerificationCode($user_id . '_' . $otp)) {
                $db->rollbackTransaction();
                LibHelper::dieJsonError($userObj->getError());
            }
            $db->commitTransaction();
        }

        $this->_template->render(false, false, 'json-success.php');
    }

    public function orderShippingData()
    {
        $orderId = FatApp::getPostedData('order_id', FatUtility::VAR_STRING, '');
        $order = new Orders();
        $orderShippingData = $order->getOrderShippingData($orderId, $this->siteLangId);
        $shippingData = [];
        foreach ($orderShippingData as $data) {
            $shippingData[$data['opshipping_code']][] = $data;
        }
        $this->set('orderShippingData', $shippingData);
        $this->_template->render(false, false);
    }

    public function giftCharge($order_id)
    {
        $isSplitPaymentMethod = Plugin::isSplitPaymentEnabled($this->siteLangId);
        if ($isSplitPaymentMethod) {
            Message::addErrorMessage(Labels::getLabel('LBL_INVALID_REQUEST'));
            FatApp::redirectUser(UrlHelper::generateUrl('Buyer', 'giftCards', [], CONF_WEBROOT_DASHBOARD));
        }
        $criteria = array('isUserLogged' => true);
        if (!$this->isEligibleForNextStep($criteria)) {
            $this->errMessage = !empty($this->errMessage) ? $this->errMessage : Labels::getLabel('ERR_SOMETHING_WENT_WRONG,_PLEASE_TRY_AFTER_SOME_TIME.', $this->siteLangId);
            LibHelper::exitWithError($this->errMessage);
        }
        $userId = UserAuthentication::getLoggedUserId();
        $userWalletBalance = User::getUserBalance($userId, true);
        /* Payment Methods[ */
        $paymentMethods = Plugin::getDataByType(Plugin::TYPE_REGULAR_PAYMENT_METHOD, $this->siteLangId);
        $paymentMethods = array_filter($paymentMethods, function ($pm) {
            return !in_array($pm['plugin_code'], [
                'CashOnDelivery',
                'PayAtStore',
                'TransferBank'
            ]);
        });

        /* ] */
        $canUseWallet = PaymentMethods::canUseWalletForPayment();
        $orderData = Orders::getOrderPaymentStatus($order_id, Orders::ORDER_GIFT_CARD, Orders::ORDER_PAYMENT_PENDING);
        if (empty($orderData)) {
            Message::addErrorMessage(Labels::getLabel('ERR_INVALID_ORDER', $this->siteLangId));
            FatApp::redirectUser(UrlHelper::generateUrl('Buyer', 'giftCards', [], CONF_WEBROOT_DASHBOARD));
        }
        foreach ($paymentMethods as $key => $paymeth) {
            if (in_array($paymeth['plugin_code'], Plugin::PAY_LATER)) {
                unset($paymentMethods[$key]);
            }
        }
        if ($userWalletBalance <= 0 && empty($paymentMethods)) {
            Message::addErrorMessage(Labels::getLabel('ERR_PAYMENT_METHOD_NOT_AVAILABLE', $this->siteLangId));
            FatApp::redirectUser(UrlHelper::generateUrl('Buyer', 'giftCards', [], CONF_WEBROOT_DASHBOARD));
        }
        $walletPaymentForm = $this->getWalletPaymentForm($this->siteLangId);
        if ((FatUtility::convertToType($userWalletBalance, FatUtility::VAR_FLOAT) > 0)  && $orderData['order_net_amount'] > 0) {
            $walletPaymentForm->addFormTagAttribute('action', UrlHelper::generateUrl('WalletPay', 'Charge', array($order_id)));
            $walletPaymentForm->fill(array('order_id' => $order_id));
            $walletPaymentForm->setFormTagAttribute('onsubmit', 'confirmOrder(this); return(false);');
            $walletPaymentForm->addSubmitButton('', 'btn_submit', Labels::getLabel('BTN_PAY_NOW', $this->siteLangId));
        }
        $obj = new Extrapage();
        $headerData = $obj->getContentByPageType(Extrapage::CHECKOUT_PAGE_HEADER_BLOCK, $this->siteLangId);
        $this->set('headerData', $headerData);
        $this->set('walletPaymentForm', $walletPaymentForm);
        $this->set('redeemRewardFrm', $this->getRewardsForm($this->siteLangId));
        $this->set('rewardPointBalance', UserRewardBreakup::rewardPointBalance($userId));
        $this->set('paymentMethods', $paymentMethods);
        $this->set('userWalletBalance', $userWalletBalance);
        $this->set('excludePaymentGatewaysArr', applicationConstants::getExcludePaymentGatewayArr());
        $this->set('canUseWalletForPayment', $canUseWallet);
        $this->set('orderId', $order_id);
        $this->set('orderData', $orderData);
        $cartSummary = $this->cartObj->getCartGiftFinancialSummary($this->siteLangId, $order_id);
        $updatedData['order_wallet_amount_charge'] =  $cartSummary['WalletAmountCharge'];
        $updatedData['order_is_wallet_selected'] = $cartSummary['cartWalletSelected'];
        $updatedData['order_type'] = Orders::ORDER_GIFT_CARD;
        $updatedData['order_id'] = $order_id;
        $orderObj = new Orders($order_id);
        $orderObj->assignValues($updatedData);
        if (!$orderObj->save($updatedData, [])) {
            LibHelper::dieJsonError($orderObj->getError());
        }
        $this->set('cartSummary', $cartSummary);
        $this->set('exculdeMainHeaderDiv', true);
        $this->_template->render(true, true);
    }

    public function walletGiftSelection()
    {
        $payFromWallet = FatApp::getPostedData('payFromWallet', FatUtility::VAR_INT, 0);
        $this->cartObj->updateCartGiftWalletOption($payFromWallet);
        if (MOBILE_APP_API_CALL) {
            $orderId = FatApp::getPostedData('order_id', FatUtility::VAR_INT, 0);
            if (1 > $orderId) {
                LibHelper::dieJsonError(Labels::getLabel('ERR_INVALID_ORDER_ID', $this->siteLangId));
            }
            $cartSummary = $this->cartObj->getCartGiftFinancialSummary($this->siteLangId, $orderId);
            $updatedData['order_wallet_amount_charge'] =  $cartSummary['WalletAmountCharge'];
            $updatedData['order_is_wallet_selected'] = $cartSummary['cartWalletSelected'];
            $updatedData['order_type'] = Orders::ORDER_GIFT_CARD;
            $updatedData['order_id'] = $orderId;
            $orderObj = new Orders($orderId);
            $orderObj->assignValues($updatedData);
            if (!$orderObj->save($updatedData, [])) {
                LibHelper::dieJsonError($orderObj->getError());
            }
            $this->_template->render();
        }
        $this->_template->render(false, false, 'json-success.php');
    }
}
