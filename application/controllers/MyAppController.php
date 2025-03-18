<?php

class MyAppController extends FatController
{
    public $app_user = ['temp_user_id' => 0];
    public $appToken = '';
    protected $cartItemsCount;
    protected $currencySymbol;
    protected $totalFavouriteItems;
    protected $totalUnreadMessageCount;
    protected $totalUnreadNotificationCount;
    protected $pageData;

    public function __construct($action)
    {
        parent::__construct($action);
        $this->checkMaintenance();
        $this->setCommonVariables();
        $this->checkTempTokenLogin();
        $mainCss = ($this->_controllerName == 'HomeController') ? 'index' : 'main';
        $this->_template->addCss(CONF_MAIN_CSS_DIR_PATH . '/' . $mainCss . '-' . CommonHelper::getLayoutDirection() . '.css');
        global $rootCatArr;
        $rootCatArr = [];
    }

    private function setCommonVariables()
    {
        CommonHelper::initCommonVariables();

        $this->siteLangId = CommonHelper::getLangId();
        $this->setApiVariables();

        if (0 < FatApp::getPostedData('appUser', FatUtility::VAR_INT, 0)) {
            CommonHelper::setAppUser();
        }

        $this->set('siteLangId', $this->siteLangId);

        $loginFrmData = array(
            'loginFrm' => $this->getLoginForm(),
            'siteLangId' => $this->siteLangId,
            'showSignUpLink' => true
        );
        $this->set('loginData', $loginFrmData);

        if (!defined('CONF_MESSAGE_ERROR_HEADING')) {
            define('CONF_MESSAGE_ERROR_HEADING', Labels::getLabel('LBL_Following_error_occurred', $this->siteLangId));
        }

        $controllerName = substr($this->_controllerName, 0, '-' . strlen('Controller'));

        if (!FatUtility::isAjaxCall() && !in_array($controllerName, ['Cart', 'Checkout', 'WalletPay'])) {
            /* to keep track of temporary hold the product stock, update time in each row of tbl_product_stock_hold against current user[ */
            $cartObj = new Cart(UserAuthentication::getLoggedUserId(true), $this->siteLangId, $this->app_user['temp_user_id']);
            $cartObj->excludeTax();
            $cartProducts = $cartObj->getBasketProducts($this->siteLangId, false);
            if ($cartProducts) {
                foreach ($cartProducts as $product) {
                    if (1 > $product['selprod_track_inventory']) {
                        continue;
                    }
                    $cartObj->updateTempStockHold($product['selprod_id'], $product['quantity']);
                }
            }
            /* ] */
        }

        if (true === MOBILE_APP_API_CALL && !in_array($controllerName, ['WalletPay'])) {
            $cartObj = $cartObj ?? new Cart(UserAuthentication::getLoggedUserId(true), $this->siteLangId, $this->app_user['temp_user_id']);
            $this->cartItemsCount = $cartObj->countProducts();
            $this->set('cartItemsCount', $this->cartItemsCount);
        }
        $defultCountryId = FatApp::getConfig('CONF_COUNTRY', FatUtility::VAR_INT, 0);
        $defaultCountryCode = Countries::getAttributesById($defultCountryId, 'country_code');

        $jsVariables = [];
        if (!FatUtility::isAjaxCall() && false === MOBILE_APP_API_CALL) {
            $jsVariablesCache = CacheHelper::get('jsVariablesCache' . $this->siteLangId, CONF_DEF_CACHE_TIME, '.txt');
            if (!$jsVariablesCache) {
                $jsVariables = array(
                    'confirmRemove' => Labels::getLabel('LBL_Do_you_want_to_remove', $this->siteLangId),
                    'confirmReset' => Labels::getLabel('LBL_Do_you_want_to_reset_settings', $this->siteLangId),
                    'confirmDelete' => Labels::getLabel('LBL_Do_you_want_to_delete', $this->siteLangId),
                    'confirmUpdateStatus' => Labels::getLabel('LBL_Do_you_want_to_update_the_status', $this->siteLangId),
                    'confirmDeleteOption' => Labels::getLabel('LBL_Do_you_want_to_delete_this_option', $this->siteLangId),
                    'confirmDefault' => Labels::getLabel('LBL_Do_you_want_to_set_default', $this->siteLangId),
                    'setMainProduct' => Labels::getLabel('LBL_Set_as_main_product', $this->siteLangId),
                    'layoutDirection' => CommonHelper::getLayoutDirection(),
                    'selectPlan' => Labels::getLabel('LBL_Please_Select_any_Plan_From_The_Above_Plans', $this->siteLangId),
                    'alreadyHaveThisPlan' => str_replace("{clickhere}", '<a href="' . UrlHelper::generateUrl('seller', 'subscriptions') . '">' . Labels::getLabel('LBL_Click_Here', $this->siteLangId) . '</a>', Labels::getLabel('LBL_You_have_already_Bought_this_plan._Please_choose_some_other_Plan_or_renew_it_from_{clickhere}', $this->siteLangId)),
                    'processing' => Labels::getLabel('LBL_Processing...', $this->siteLangId),
                    'requestProcessing' => Labels::getLabel('LBL_Request_Processing...', $this->siteLangId),
                    'selectLocation' => Labels::getLabel('LBL_Select_Location_to_view_Wireframe', $this->siteLangId),
                    'favoriteToShop' => Labels::getLabel('LBL_Favorite_To_Shop', $this->siteLangId),
                    'unfavoriteToShop' => Labels::getLabel('LBL_Unfavorite_To_Shop', $this->siteLangId),
                    'userNotLogged' => Labels::getLabel('MSG_User_Not_Logged', $this->siteLangId),
                    'selectFile' => Labels::getLabel('MSG_File_not_uploaded', $this->siteLangId),
                    'thanksForSharing' => Labels::getLabel('MSG_Thanks_For_Sharing', $this->siteLangId),
                    'isMandatory' => Labels::getLabel('VLBL_is_mandatory', $this->siteLangId),
                    'pleaseEnterValidEmailId' => Labels::getLabel('VLBL_Please_enter_valid_email_ID_for', $this->siteLangId),
                    'charactersSupportedFor' => Labels::getLabel('VLBL_Only_characters_are_supported_for', $this->siteLangId),
                    'pleaseEnterIntegerValue' => Labels::getLabel('VLBL_Please_enter_integer_value_for', $this->siteLangId),
                    'pleaseEnterNumericValue' => Labels::getLabel('VLBL_Please_enter_numeric_value_for', $this->siteLangId),
                    'startWithLetterOnlyAlphanumeric' => Labels::getLabel('ERR_INVALID_USERNAME', $this->siteLangId),
                    'mustBeBetweenCharacters' => Labels::getLabel('VLBL_Length_Must_be_between_6_to_20_characters', $this->siteLangId),
                    'invalidValues' => Labels::getLabel('VLBL_Length_Invalid_value_for', $this->siteLangId),
                    'shouldNotBeSameAs' => Labels::getLabel('VLBL_should_not_be_same_as', $this->siteLangId),
                    'mustBeSameAs' => Labels::getLabel('VLBL_must_be_same_as', $this->siteLangId),
                    'mustBeGreaterOrEqual' => Labels::getLabel('VLBL_must_be_greater_than_or_equal_to', $this->siteLangId),
                    'mustBeGreaterThan' => Labels::getLabel('VLBL_must_be_greater_than', $this->siteLangId),
                    'mustBeLessOrEqual' => Labels::getLabel('VLBL_must_be_less_than_or_equal_to', $this->siteLangId),
                    'mustBeLessThan' => Labels::getLabel('VLBL_must_be_less_than', $this->siteLangId),
                    'lengthOf' => Labels::getLabel('VLBL_Length_of', $this->siteLangId),
                    'valueOf' => Labels::getLabel('VLBL_Value_of', $this->siteLangId),
                    'mustBeBetween' => Labels::getLabel('VLBL_must_be_between', $this->siteLangId),
                    'and' => Labels::getLabel('VLBL_and', $this->siteLangId),
                    'pleaseSelect' => Labels::getLabel('VLBL_Please_select', $this->siteLangId),
                    'to' => Labels::getLabel('VLBL_to', $this->siteLangId),
                    'options' => Labels::getLabel('VLBL_options', $this->siteLangId),
                    'isNotAvailable' => Labels::getLabel('VLBL_is_not_available', $this->siteLangId),
                    'RemoveProductFromFavourite' => Labels::getLabel('LBL_Remove_product_from_favourite_list', $this->siteLangId),
                    'AddProductToFavourite' => Labels::getLabel('LBL_Add_Product_To_favourite_list', $this->siteLangId),
                    'MovedSuccessfully' => Labels::getLabel('LBL_Moved_Successfully', $this->siteLangId),
                    'RemovedSuccessfully' => Labels::getLabel('LBL_Removed_Successfully', $this->siteLangId),
                    'confirmDeletePersonalInformation' => Labels::getLabel('LBL_Do_you_really_want_to_remove_all_your_personal_information', $this->siteLangId),
                    'preferredDimensions' => Labels::getLabel('LBL_Preferred_Dimensions_%s', $this->siteLangId),
                    'invalidCredentials' => Labels::getLabel('LBL_Invalid_Credentials', $this->siteLangId),
                    'searchString' => Labels::getLabel('LBL_Search_string_must_be_atleast_3_characters_long.', $this->siteLangId),
                    'atleastOneRecord' => Labels::getLabel('LBL_Please_select_atleast_one_record.', $this->siteLangId),
                    'primaryLanguageField' => Labels::getLabel('LBL_PRIMARY_LANGUAGE_DATA_NEEDS_TO_BE_FILLED_FOR_SYSTEM_TO_TRANSLATE_TO_OTHER_LANGUAGES.', $this->siteLangId),
                    'unknownPrimaryLanguageField' => Labels::getLabel('LBL_PRIMARY_LANGUAGE_FIELD_IS_NOT_SET.', $this->siteLangId),
                    'invalidRequest' => Labels::getLabel('LBL_INVALID_REQUEST', $this->siteLangId),
                    'scrollable' => Labels::getLabel('LBL_SCROLLABLE', $this->siteLangId),
                    'quantityAdjusted' => Labels::getLabel('MSG_MAX_QUANTITY_THAT_CAN_BE_PURCHASED_IS_{QTY}._SO,_YOUR_REQUESTED_QUANTITY_IS_ADJUSTED_TO_{QTY}.', $this->siteLangId),
                    'withUsernameOrEmail' => Labels::getLabel('LBL_USE_EMAIL_INSTEAD_?', $this->siteLangId),
                    'withPhoneNumber' => Labels::getLabel('LBL_USE_PHONE_NUMBER_INSTEAD_?', $this->siteLangId),
                    'byPhone' => Labels::getLabel('LBL_PHONE_?', $this->siteLangId),
                    'byEmail' => Labels::getLabel('LBL_EMAIL_?', $this->siteLangId),
                    'otpInterval' => User::OTP_INTERVAL,
                    'captchaSiteKey' => FatApp::getConfig('CONF_RECAPTCHA_SITEKEY', FatUtility::VAR_STRING, ''),
                    'allowedFileSize' => LibHelper::getMaximumFileUploadSize(),
                    'fileSizeExceeded' => Labels::getLabel("MSG_FILE_SIZE_SHOULD_BE_LESSER_THAN_{SIZE-LIMIT}", $this->siteLangId),
                    'copyToClipboard' => Labels::getLabel('LBL_Copy_to_clipboard', $this->siteLangId),
                    'copied' => Labels::getLabel('LBL_Copied', $this->siteLangId),
                    'invalidGRecaptchaKeys' => Labels::getLabel('LBL_YOU_MIGHT_HAVE_INVALID_GOOGLE_RECAPTCHA_V3_KEYS._PLEASE_VERIFY.', $this->siteLangId),
                    'saveProfileFirst' => Labels::getLabel('LBL_Save_Profile_First', $this->siteLangId),
                    'minimumOneLocationRequired' => Labels::getLabel('LBL_Minimum_one_location_is_required', $this->siteLangId),
                    'processing_counter' => Labels::getLabel('LBL_{counter}_OUT_OF_{count}_RECORD_BATCHES.', $this->siteLangId),
                    'loadingCaptcha' => Labels::getLabel('LBL_Loading_Captcha...', $this->siteLangId),
                    'confirmPayment' => Labels::getLabel('LBL_CONFIRM_PAYMENT', $this->siteLangId),
                    'currentPrice' => Labels::getLabel('LBL_Current_Price', $this->siteLangId),
                    'discountPercentage' => Labels::getLabel('LBL_Discount_Percentage', $this->siteLangId),
                    'paymentSucceeded' => Labels::getLabel('LBL_PAYMENT_SUCCEEDED._WAITING_FOR_CONFIRMATION', $this->siteLangId),
                    'otpSent' => Labels::getLabel('MSG_OTP_SENT!', $this->siteLangId),
                    'proceed' => Labels::getLabel('MSG_PROCEED', $this->siteLangId),
                    'invalidFromTime' => Labels::getLabel('LBL_PLEASE_SELECT_VALID_FROM_TIME', $this->siteLangId),
                    'selectTimeslotDay' => Labels::getLabel('LBL_ATLEAST_ONE_DAY_AND_TIMESLOT_NEEDS_TO_BE_CONFIGURED', $this->siteLangId),
                    'invalidTimeSlot' => Labels::getLabel('LBL_PLEASE_CONFIGURE_FROM_AND_TO_TIME', $this->siteLangId),
                    'changePickup' => Labels::getLabel('LBL_CHANGE_PICKUP', $this->siteLangId),
                    'selectProduct' => Labels::getLabel('LBL_PLEASE_SELECT_PRODUCT', $this->siteLangId),
                    'noRecordFound' => Labels::getLabel('LBL_No_Record_Found', $this->siteLangId),
                    'waitingForResponse' => Labels::getLabel('MSG_WAITING_FOR_PAYMENT_RESPONSE..', $this->siteLangId),
                    'updatingRecord' => Labels::getLabel('MSG_RESPONSE_RECEIVED._UPDATING_RECORDS..', $this->siteLangId),
                    'requiredFields' => Labels::getLabel('MSG_PLEASE_FILL_REQUIRED_FIELDS', $this->siteLangId),
                    'alreadySelected' => Labels::getLabel('MSG_ALREADY_SELECTED', $this->siteLangId),
                    'typeToSearch' => Labels::getLabel('MSG_TYPE_TO_SEARCH..', $this->siteLangId),
                    'resendOtp' => Labels::getLabel('LBL_RESEND_OTP?', $this->siteLangId),
                    'redirecting' => Labels::getLabel('MSG_REDIRECTING...', $this->siteLangId),
                    'uploadImageLimit' => Labels::getLabel('MSG_YOU_ARE_NOT_ALLOWED_TO_ADD_MORE_THAN_8_IMAGES', $this->siteLangId),
                    'deleteAccount' => Labels::getLabel('MSG_ARE_YOU_SURE_?_DELETING_ACCOUNT_WILL_UNLINK_ALL_TRANSACTIONS_RELATED_TO_THIS_ACCOUNT.', $this->siteLangId),
                    'unlinkAccount' => Labels::getLabel('MSG_ARE_YOU_SURE_?_UNLINKING_ACCOUNT_WILL_UNLINK_ALL_TRANSACTIONS_RELATED_TO_THIS_ACCOUNT.', $this->siteLangId),
                    'dialCodeFieldNotFound' => Labels::getLabel('LBL_DIAL_CODE_FIELD_NOT_FOUND', $this->siteLangId),
                    'searchAsIMoveTheMap' => Labels::getLabel('MSG_SEARCH_AS_I_MOVE_THE_MAP', $this->siteLangId),
                    'currentSearchLocation' => Labels::getLabel('LBL_CURRENT_SEARCH_LOCATION', $this->siteLangId),
                    'shopInfo' => Labels::getLabel('LBL_SHOP_INFO', $this->siteLangId),
                    'cacheTimeStamp' => UrlHelper::getCacheTimestamp($this->siteLangId),
                    'close' => Labels::getLabel('LBL_CLOSE', $this->siteLangId),
                    'copiedText' => Labels::getLabel('LBL_COPIED_TEXT', $this->siteLangId),
                    'fieldNotFound' => Labels::getLabel('LBL_{field}_NOT_FOUND', $this->siteLangId),
                    'faqsSearchStringLengthMsg' => CommonHelper::replaceStringData(Labels::getLabel('LBL_TYPE_ATLEAST_{LEN}_CHARACTERS_TO_SEARCH_IN_FAQS.', $this->siteLangId), ['{LEN}' => Faq::FAQS_SEARCH_STRING_LENGTH]),
                    'overwriteCartItems' => Labels::getLabel('LBL_DO_YOU_WANT_TO_REPLACE_EXISTING_SHOP_ITEMS?', $this->siteLangId),
                    'maxLengthValidator' => CommonHelper::replaceStringData(Labels::getLabel('FRM_USED_{charsTyped}_of_{charsTotal}_CHAR', $this->siteLangId), ["{charsTyped}" => "%charsTyped%", "{charsTotal}" => "%charsTotal%"]), /* Used By Maxlength bootstrap validator. */
                    'geoLocationNotSupported' => Labels::getLabel('FRM_GEO_LOCATION_NOT_SUPPORTED', $this->siteLangId),
                    'dontReloadPageWhilePayment' => Labels::getLabel('FRM_PLEASE_DON`T_REFRESH/RELOAD_OR_HITTING_BACK_BUTTON_ON_THE_BROWSER._WE_ARE_PROCESSING_YOUR_TRANSACTION.', $this->siteLangId),
                    'deliveryAddressMandatory' => Labels::getLabel('ERR_DELIVERY_ADDRESS_IS_MANDATORY', $this->siteLangId),
                );

                $languages = Language::getAllNames(false);
                foreach ($languages as $val) {
                    $jsVariables['language' . $val['language_id']] = $val['language_layout_direction'];
                }
                CacheHelper::create('jsVariablesCache' . $this->siteLangId, serialize($jsVariables), CacheHelper::TYPE_LABELS);
            } else {
                $jsVariables =  unserialize($jsVariablesCache);
            }
        }

        $jsVariables['controllerName'] = $controllerName;
        $jsVariables['defaultCountryCode'] = $defaultCountryCode;
        $jsVariables['siteCurrencyId'] = CommonHelper::getCurrencyId();

        if (false === MOBILE_APP_API_CALL && (!isset($_COOKIE['_ykGeoLat']) || !isset($_COOKIE['_ykGeoLng']) || !isset($_COOKIE['_ykGeoCountryCode'])) && FatApp::getConfig('CONF_DEFAULT_GEO_LOCATION', FatUtility::VAR_INT, 0)) {
            setcookie('_ykGeoLat', FatApp::getConfig('CONF_GEO_DEFAULT_LAT', FatUtility::VAR_INT, 40.72), time() + (86400 * 30), CONF_WEBROOT_FRONTEND,  $_SERVER['SERVER_NAME']); // 86400 = 1 day
            setcookie('_ykGeoLng', FatApp::getConfig('CONF_GEO_DEFAULT_LNG', FatUtility::VAR_INT, -73.96), time() + (86400 * 30), CONF_WEBROOT_FRONTEND,  $_SERVER['SERVER_NAME']); // 86400 = 1 day
            setcookie('_ykGeoStateCode', FatApp::getConfig('CONF_GEO_DEFAULT_STATE', FatUtility::VAR_STRING, ''), time() + (86400 * 30), CONF_WEBROOT_FRONTEND,  $_SERVER['SERVER_NAME']); // 86400 = 1 day
            setcookie('_ykGeoCountryCode', FatApp::getConfig('CONF_GEO_DEFAULT_COUNTRY', FatUtility::VAR_STRING, ''), time() + (86400 * 30), CONF_WEBROOT_FRONTEND,  $_SERVER['SERVER_NAME']); // 86400 = 1 day
            setcookie('_ykGeoZip', FatApp::getConfig('CONF_GEO_DEFAULT_ZIPCODE', FatUtility::VAR_INT, 0), time() + (86400 * 30), CONF_WEBROOT_FRONTEND,  $_SERVER['SERVER_NAME']); // 86400 = 1 day
            $address = FatApp::getConfig('CONF_GEO_DEFAULT_ADDR', FatUtility::VAR_STRING, '');
            if (empty($address)) {
                $address = FatApp::getConfig('CONF_GEO_DEFAULT_ZIPCODE', FatUtility::VAR_INT, 0) . '-' . FatApp::getConfig('CONF_GEO_DEFAULT_STATE', FatUtility::VAR_STRING, '');
            }
            setcookie('_ykGeoAddress', $address, time() + (86400 * 30), CONF_WEBROOT_FRONTEND, $_SERVER['SERVER_NAME']); // 86400 = 1 day
        }

        $this->includeDatePickerLangJs();
        $this->set('jsVariables', $jsVariables);
        $this->set('controllerName', $controllerName);
        $this->set('isAppUser', CommonHelper::isAppUser());
        $this->set('action', $this->_actionName);
    }

    private function setApiVariables()
    {
        if (false === MOBILE_APP_API_CALL) {
            return;
        }

        if (
            isset($_SERVER['HTTP_X_APP_SESSION_ID']) &&
            !empty($_SERVER['HTTP_X_APP_SESSION_ID']) &&
            (session_status() !== PHP_SESSION_ACTIVE ||
                $_SERVER['HTTP_X_APP_SESSION_ID'] != session_id()
            )
        ) {
            session_destroy();
            session_id($_SERVER['HTTP_X_APP_SESSION_ID']);
            session_start();
        }


        $post = FatApp::getPostedData();

        $this->appToken = CommonHelper::getAppToken();

        if (!empty($_SERVER['HTTP_X_TEMP_USER_ID'])) {
            $this->app_user['temp_user_id'] = $_SERVER['HTTP_X_TEMP_USER_ID'];
        }

        $forTempTokenBasedActions = array('send_to_web');
        if (('1.0' == MOBILE_APP_API_VERSION || in_array($this->_actionName, $forTempTokenBasedActions) || empty($this->appToken)) && array_key_exists('_token', $post)) {
            $this->appToken = ($post['_token'] != '') ? $post['_token'] : '';
        }

        if ($this->appToken) {
            if (!UserAuthentication::isUserLogged('', $this->appToken)) {
                $arr = array('status' => -1, 'msg' => Labels::getLabel('L_Invalid_Token', $this->siteLangId));
                die(json_encode($arr));
            }

            $userId = UserAuthentication::getLoggedUserId();
            $userObj = new User($userId);
            if (!$row = $userObj->getProfileData()) {
                $arr = array('status' => -1, 'msg' => Labels::getLabel('L_Invalid_Token', $this->siteLangId));
                die(json_encode($arr));
            }
            $this->app_user = $row;
            $this->app_user['temp_user_id'] = 0;
        }

        if (array_key_exists('language', $post)) {
            $this->siteLangId = FatUtility::int($post['language']);
            $_COOKIE['defaultSiteLang'] = $this->siteLangId;
        }

        if (array_key_exists('currency', $post)) {
            $_COOKIE['defaultSiteCurrency'] = CommonHelper::getCurrencyId();
        }

        $currencyRow = Currency::getAttributesById(CommonHelper::getCurrencyId());

        $this->currencySymbol = !empty($currencyRow['currency_symbol_left']) ? $currencyRow['currency_symbol_left'] : $currencyRow['currency_symbol_right'];
        $this->set('currencySymbol', $this->currencySymbol);

        $user_id = $this->getAppLoggedUserId();

        if (array_key_exists('favouriteItemsCount', $post)) {
            $this->totalFavouriteItems = UserFavorite::getUserFavouriteItemCount($user_id);
            $this->set('totalFavouriteItems', $this->totalFavouriteItems);
        }

        if (array_key_exists('unreadMessageCount', $post)) {
            $this->totalUnreadMessageCount = 0;
            if (0 < $user_id) {
                $threadObj = new Thread();
                $this->totalUnreadMessageCount = $threadObj->getMessageCount($user_id);
            }
            $this->set('totalUnreadMessageCount', $this->totalUnreadMessageCount);
        }

        if (array_key_exists('unreadNotificationCount', $post)) {
            $this->totalUnreadNotificationCount = 0;
            if (0 < $user_id) {
                $notificationObj = new Notifications();
                $this->totalUnreadNotificationCount = $notificationObj->getUnreadNotificationCount($user_id);
            }
            $this->set('totalUnreadNotificationCount', $this->totalUnreadNotificationCount);
        }
    }

    private function getAppLoggedUserId()
    {
        return isset($this->app_user["user_id"]) ? $this->app_user["user_id"] : 0;
    }

    public function getStates($countryId, $stateId = 0, $return = false, $idCol = 'state_id')
    {
        $countryId = FatUtility::int($countryId);

        $stateObj = new States();
        $statesArr = $stateObj->getStatesByCountryId($countryId, $this->siteLangId, true, $idCol);

        if (true === $return) {
            return $statesArr;
        }

        $this->set('statesArr', $statesArr);
        $this->set('stateId', $stateId);
        $this->_template->render(false, false, '_partial/states-list.php');
    }

    public function getStatesByCountryCode($countryCode, $stateCode = '', $idCol = 'state_id')
    {
        $countryId = Countries::getCountryByCode($countryCode, 'country_id');
        $this->getStates($countryId, $stateCode, false, $idCol);
    }

    public function getBreadcrumbNodes($action)
    {
        $nodes = array();
        $className = get_class($this);
        $arr = explode('-', FatUtility::camel2dashed($className));
        array_pop($arr);
        $urlController = implode('-', $arr);
        $className = mb_strtoupper(implode('_', $arr));

        if ($action == 'index') {
            $nodes[] = array('title' => ucwords(Labels::getLabel('BCN_' . $className)));
        } else {
            $nodes[] = array('title' => ucwords(Labels::getLabel('BCN_' . $className)), 'href' => UrlHelper::generateUrl($urlController));
            $action = str_replace('-', '_', FatUtility::camel2dashed($action));
            $nodes[] = array('title' => ucwords(Labels::getLabel('BCN_' . $action)));
        }
        return $nodes;
    }

    public function setUpNewsLetter()
    {
        $siteLangId = CommonHelper::getLangId();
        $post = FatApp::getPostedData();
        $frm = Common::getNewsLetterForm(CommonHelper::getLangId());
        $post = $frm->getFormDataFromArray($post);

        $api_key = FatApp::getConfig("CONF_MAILCHIMP_KEY");
        $list_id = FatApp::getConfig("CONF_MAILCHIMP_LIST_ID");
        if ($api_key == '' || $list_id == '') {
            Message::addErrorMessage(Labels::getLabel("LBL_Newsletter_is_not_configured_yet,_Please_contact_admin", $siteLangId));
            FatUtility::dieWithError(Message::getHtml());
        }

        try {
            MailchimpHelper::subscribe(['email' => htmlentities($post['email'])], $this->siteLangId);
            if (!empty($subscriber['msg'])) {
                Message::addErrorMessage($subscriber['msg']);
                FatUtility::dieWithError(Message::getHtml());
            }
        } catch (Mailchimp_Error $e) {
            Message::addErrorMessage($e->getMessage());
            FatUtility::dieWithError(Message::getHtml());
        }

        $this->set('msg', Labels::getLabel('MSG_SUCCESSFULLY_SUBSCRIBED', $siteLangId));
        $this->_template->render(false, false, 'json-success.php');
    }

    protected function getGuestUserForm($langId = 0)
    {
        $siteLangId = FatUtility::int($langId);
        $frm = new Form('frmGuestLogin');
        $frm->addRequiredField(Labels::getLabel('FRM_NAME', $siteLangId), 'user_name', '', array('placeholder' => Labels::getLabel('LBL_Name', $siteLangId)));
        $fld = $frm->addEmailField(Labels::getLabel('FRM_EMAIL', $siteLangId), 'user_email', '', array('placeholder' => Labels::getLabel('LBL_EMAIL_ADDRESS', $siteLangId)));
        $fld->requirement->setRequired(true);

        $frm->addHtml('', 'space', '');
        $frm->addSubmitButton('', 'btn_submit', Labels::getLabel('BTN_GUEST_SIGN_IN', $siteLangId));
        return $frm;
    }

    protected function getLoginForm(bool $signInWithPhone = false)
    {
        $siteLangId = CommonHelper::getLangId();
        $frm = new Form('frmLogin');
        $userName = '';
        $pass = '';
        if (CommonHelper::demoUrl()) {
            $userName = (false === $signInWithPhone) ? 'login@dummyid.com' : '';
            $pass = 'kanwar@123';
        }

        if (false === $signInWithPhone) {
            $frm->addRequiredField(Labels::getLabel('FRM_USERNAME', $siteLangId), 'username', $userName);
            $pwd = $frm->addPasswordField(Labels::getLabel('FRM_PASSWORD', $siteLangId), 'password', $pass, array('placeholder' => Labels::getLabel('FRM_PASSWORD', $siteLangId)));
            $pwd->requirements()->setRequired();

            $frm->addCheckbox(Labels::getLabel('FRM_REMEMBER_ME', $siteLangId), 'remember_me', 1, array(), '', 0);
            $frm->addHtml('', 'forgot', '');
        } else {
            $phnFld = $frm->addRequiredField(Labels::getLabel('FRM_PHONE_NUMBER', $siteLangId), 'username', $userName);
            $phnFld->requirements()->setRegularExpressionToValidate(ValidateElement::PHONE_REGEX);
            $attr = ['maxlength' => 1, 'size' => 1, 'placeholder' => '*'];
            for ($i = 0; $i < User::OTP_LENGTH; $i++) {
                $frm->addTextBox('', 'upv_otp[' . $i . ']', '', $attr);
            }
            $frm->addHiddenField('', 'loginWithOtp', 1);
        }

        $frm->addSubmitButton('', 'btn_submit', Labels::getLabel('BTN_LOGIN', $siteLangId));
        return $frm;
    }

    protected function getRegistrationForm($showNewsLetterCheckBox = true, $signUpWithPhone = 0)
    {
        $siteLangId = $this->siteLangId;

        $frm = new Form('frmRegister');
        $frm->addHiddenField('', 'user_id', 0, array('id' => 'user_id'));
        $frm->addRequiredField(Labels::getLabel('FRM_NAME', $siteLangId), 'user_name', '', array('placeholder' => Labels::getLabel('FRM_NAME', $siteLangId)));
        $fld = $frm->addTextBox(Labels::getLabel('FRM_USERNAME', $siteLangId), 'user_username', '', array('placeholder' => Labels::getLabel('FRM_USERNAME', $siteLangId)));
        if (false === MOBILE_APP_API_CALL) {
            $fld->setUnique('tbl_user_credentials', 'credential_username', 'credential_user_id', 'user_id', 'user_id');
        }
        $fld->requirements()->setRequired();
        $fld->requirements()->setUsername();

        if (0 < $signUpWithPhone) {
            $frm->addHiddenField('', 'signUpWithPhone', 1);
            $frm->addHiddenField('', 'user_phone_dcode');
            $frm->addRequiredField(Labels::getLabel('FRM_PHONE_NUMBER', $siteLangId), 'user_phone', '', array('placeholder' => Labels::getLabel('FRM_PHONE_NUMBER', $siteLangId), 'class' => 'phone-js'));
        } else {
            $fld = $frm->addEmailField(Labels::getLabel('FRM_EMAIL', $siteLangId), 'user_email', '', array('placeholder' => Labels::getLabel('FRM_EMAIL', $siteLangId)));
            if (false === MOBILE_APP_API_CALL) {
                $fld->setUnique('tbl_user_credentials', 'credential_email', 'credential_user_id', 'user_id', 'user_id');
            }
            $fld = $frm->addPasswordField(Labels::getLabel('FRM_PASSWORD', $siteLangId), 'user_password', '', array('placeholder' => Labels::getLabel('FRM_PASSWORD', $siteLangId)));
            $fld->requirements()->setRequired();
            $fld->requirements()->setRegularExpressionToValidate(ValidateElement::PASSWORD_REGEX);
            $fld->requirements()->setCustomErrorMessage(Labels::getLabel('MSG_PASSWORD_MUST_BE_EIGHT_CHARACTERS_LONG_AND_ALPHANUMERIC', $siteLangId));

            $fld1 = $frm->addPasswordField(Labels::getLabel('FRM_CONFIRM_PASSWORD', $siteLangId), 'password1', '', array('placeholder' => Labels::getLabel('FRM_CONFIRM_PASSWORD', $siteLangId)));
            $fld1->requirements()->setRequired();
            $fld1->requirements()->setCompareWith('user_password', 'eq', Labels::getLabel('FRM_PASSWORD', $siteLangId));
        }

        $fld = $frm->addCheckBox('', 'agree', 1);
        $fld->requirements()->setRequired();
        $fld->requirements()->setCustomErrorMessage(Labels::getLabel('FRM_TERMS_CONDITION_IS_MANDATORY.', $siteLangId));

        if (1 > $signUpWithPhone && $showNewsLetterCheckBox && FatApp::getConfig('CONF_ENABLE_NEWSLETTER_SUBSCRIPTION')) {
            $api_key = FatApp::getConfig("CONF_MAILCHIMP_KEY");
            $list_id = FatApp::getConfig("CONF_MAILCHIMP_LIST_ID");
            if ($api_key != '' || $list_id != '') {
                $frm->addCheckBox(Labels::getLabel('FRM_NEWSLETTER_SIGNUP', $siteLangId), 'user_newsletter_signup', 1);
            }
        }

        $isCheckOutPage = false;
        if (isset($_SESSION['referer_page_url'])) {
            $checkoutPage = basename(parse_url($_SESSION['referer_page_url'], PHP_URL_PATH));
            if ($checkoutPage == 'checkout') {
                $isCheckOutPage = true;
            }
        }
        if ($isCheckOutPage) {
            $frm->addHiddenField('', 'isCheckOutPage', 1);
        }

        //$frm->addDateField(Labels::getLabel('FRM_DOB',CommonHelper::getLangId()), 'user_dob', '',array('readonly' =>'readonly'));
        //$frm->addTextBox(Labels::getLabel('LBL_PHONE',CommonHelper::getLangId()), 'user_phone');
        $frm->addSubmitButton(Labels::getLabel('BTN_REGISTER', $siteLangId), 'btn_submit', Labels::getLabel('BTN_REGISTER', $siteLangId));
        return $frm;
    }

    protected function getUserAddressForm($siteLangId, $btnOrderFlip = false)
    {
        $siteLangId = FatUtility::int($siteLangId);
        $frm = new Form('frmAddress');
        $frm->addHiddenField('', 'addr_id');
        $frm->addHiddenField('', 'shipping_addr_id');
        $fld = $frm->addTextBox(Labels::getLabel('FRM_ADDRESS_TITLE', $siteLangId), 'addr_title');
        $fld->requirement->setRequired(true);
        $fld->setFieldTagAttribute('maxlength', Address::ADDRESS_TITLE_LENGTH);
        $fld->setFieldTagAttribute('placeholder', Labels::getLabel('LBL_E.g:_My_Office_Address', $siteLangId));
        $frm->addRequiredField(Labels::getLabel('LBL_Name', $siteLangId), 'addr_name');
        $frm->addRequiredField(Labels::getLabel('LBL_Address_Line1', $siteLangId), 'addr_address1');
        $frm->addTextBox(Labels::getLabel('LBL_Address_Line2', $siteLangId), 'addr_address2');

        $countryObj = new Countries();
        $countriesArr = $countryObj->getCountriesAssocArr($siteLangId);
        $fld = $frm->addSelectBox(Labels::getLabel('LBL_Country', $siteLangId), 'addr_country_id', $countriesArr, FatApp::getConfig('CONF_COUNTRY'), array(), Labels::getLabel('LBL_Select', $siteLangId));
        $fld->requirement->setRequired(true);

        $frm->addSelectBox(Labels::getLabel('LBL_State', $siteLangId), 'addr_state_id', array(), '', array(), Labels::getLabel('LBL_Select', $siteLangId))->requirement->setRequired(true);
        $frm->addRequiredField(Labels::getLabel('LBL_City', $siteLangId), 'addr_city');

        $frm->addRequiredField(Labels::getLabel('LBL_Postalcode', $this->siteLangId), 'addr_zip');

        $frm->addHiddenField('', 'addr_phone_dcode');
        $phnFld = $frm->addRequiredField(Labels::getLabel('LBL_Phone', $siteLangId), 'addr_phone', '', array('class' => 'phone-js ltr-right', 'placeholder' => ValidateElement::PHONE_NO_FORMAT, 'maxlength' => ValidateElement::PHONE_NO_LENGTH));
        $phnFld->requirements()->setRegularExpressionToValidate(ValidateElement::PHONE_REGEX);
        $phnFld->requirements()->setCustomErrorMessage(Labels::getLabel('LBL_Please_enter_valid_phone_number_format.', $this->siteLangId));

        if ($btnOrderFlip) {
            $frm->addButton('', 'btn_cancel', Labels::getLabel('LBL_Cancel', $siteLangId));
            $frm->addSubmitButton('', 'btn_submit', Labels::getLabel('BTN_SAVE', $siteLangId));
            return $frm;
        }
        $frm->addSubmitButton('', 'btn_submit', Labels::getLabel('BTN_SAVE', $siteLangId));
        $frm->addButton('', 'btn_cancel', Labels::getLabel('LBL_CANCEL', $siteLangId));

        return $frm;
    }

    protected function getProductSearchForm($addKeywordRelvancy = false)
    {
        $sortByArr = array(
            'price_asc' => Labels::getLabel('LBL_Price_(Low_to_High)', $this->siteLangId),
            'price_desc' => Labels::getLabel('LBL_Price_(High_to_Low)', $this->siteLangId),
            'popularity_desc' => Labels::getLabel('LBL_Sort_by_Popularity', $this->siteLangId),
            'discounted' => Labels::getLabel('LBL_Most_discounted', $this->siteLangId),
        );

        if (0 < FatApp::getConfig('CONF_HIDE_PRICES', FatUtility::VAR_INT, 0)) {
            unset($sortByArr['price_asc'], $sortByArr['price_desc']);
        }

        /* if (0 < FatApp::getConfig("CONF_ALLOW_REVIEWS", FatUtility::VAR_INT, 0)) {
            $sortByArr['rating_desc'] = Labels::getLabel('LBL_Sort_by_Rating', $this->siteLangId);
        } */

        $sortBy = 'popularity_desc';
        if ($addKeywordRelvancy) {
            $sortByArr = array('keyword_relevancy' => Labels::getLabel('LBL_Keyword_Relevancy', $this->siteLangId)) + $sortByArr;
            $sortBy = 'keyword_relevancy';
        }

        $frm = new Form('frmProductSearch');
        $frm->addTextBox('', 'keyword', '', array('id' => 'keyword'));
        $frm->addSelectBox('', 'sortBy', $sortByArr, $sortBy, array('id' => 'sortBy'), '');
        $frm->addHiddenField('', 'pageSize', '', array('id' => 'pageSize'));
        $frm->addHiddenField('', 'page', 1);
        $frm->addHiddenField('', 'sortOrder', 'asc');
        $frm->addHiddenField('', 'category', 0);
        $frm->addHiddenField('', 'shop_id', 0);
        $frm->addHiddenField('', 'brand_id', 0);
        $frm->addHiddenField('', 'collection_id', 0);
        $frm->addHiddenField('', 'join_price', 0);
        $frm->addHiddenField('', 'featured', 0);
        $frm->addHiddenField('', 'top_products', 0);
        $frm->addHiddenField('', 'currency_id', CommonHelper::getCurrencyId());
        $frm->addSubmitButton('', 'btnProductSrchSubmit', '');
        $frm->addHiddenField('', 'pageRecordCount');
        $frm->addHiddenField('', 'vtype');
        $frm->addHiddenField('', 'viewType');
        return $frm;
    }

    public function fatActionCatchAll($action)
    {
        $this->_template->render(false, false, 'error-pages/404.php');
    }

    public function setupPoll()
    {
        $siteLangId = CommonHelper::getLangId();
        $pollId = FatApp::getPostedData('pollfeedback_polling_id', FatUtility::VAR_INT, 0);
        if ($pollId <= 0) {
            Message::addErrorMessage(Labels::getLabel('ERR_INVALID_REQUEST', $siteLangId));
            FatUtility::dieWithError(Message::getHtml());
        }
        $frm = Common::getPollForm($pollId, $siteLangId);
        if (!$post = $frm->getFormDataFromArray(FatApp::getPostedData())) {
            Message::addErrorMessage($frm->getValidationErrors());
            FatUtility::dieWithError(Message::getHtml());
        }
        $pollFeedback = new PollFeedback();
        if ($pollFeedback->isPollAnsweredFromIP($pollId, $_SERVER['REMOTE_ADDR'])) {
            Message::addErrorMessage(Labels::getLabel('ERR_POLL_ALREADY_POSTED_FROM_THIS_IP', $this->siteLangId));
            FatUtility::dieWithError(Message::getHtml());
        }
        $post['pollfeedback_response_ip'] = $_SERVER['REMOTE_ADDR'];
        $post['pollfeedback_added_on'] = date('Y-m-d H:i:s');

        $pollFeedback->assignValues($post);
        if (!$pollFeedback->save()) {
            Message::addErrorMessage($pollFeedback->getError());
            FatUtility::dieWithError(Message::getHtml());
        }
        FatUtility::dieJsonSuccess(Labels::getLabel('Msg_Poll_Feedback_Sent_Successfully', $siteLangId));
    }

    protected function getChangeEmailForm($passwordField = true)
    {
        $frm = new Form('changeEmailFrm');
        $newEmail = $frm->addEmailField(
            Labels::getLabel('LBL_NEW_EMAIL', $this->siteLangId),
            'new_email'
        );
        $newEmail->requirements()->setRequired();

        $conNewEmail = $frm->addEmailField(
            Labels::getLabel('LBL_CONFIRM_NEW_EMAIL', $this->siteLangId),
            'conf_new_email'
        );
        $conNewEmailReq = $conNewEmail->requirements();
        $conNewEmailReq->setRequired();
        $conNewEmailReq->setCompareWith('new_email', 'eq');

        if ($passwordField) {
            $curPwd = $frm->addPasswordField(Labels::getLabel('LBL_CURRENT_PASSWORD', $this->siteLangId), 'current_password');
            $curPwd->requirements()->setRequired();
        }

        $frm->addSubmitButton('', 'btn_submit', Labels::getLabel('BTN_SAVE', $this->siteLangId));
        return $frm;
    }

    protected function getOtpForm()
    {
        $frm = new Form('otpFrm');
        $frm->addHiddenField('', 'user_id');
        if (true === MOBILE_APP_API_CALL) {
            $frm->addRequiredField('', 'upv_otp');
        } else {
            $attr = ['maxlength' => 1, 'size' => 1, 'placeholder' => '*'];
            for ($i = 0; $i < User::OTP_LENGTH; $i++) {
                $frm->addTextBox('', 'upv_otp[' . $i . ']', '', $attr);
            }
        }
        $frm->addSubmitButton('', 'btn_submit', Labels::getLabel('BTN_VERIFY', $this->siteLangId));
        return $frm;
    }

    protected function userEmailVerifications($userObj, $data, $configureEmail = false)
    {
        if (!$configureEmail) {
            $verificationCode = $userObj->prepareUserVerificationCode($data['user_new_email']);
        } else {
            $verificationCode = $userObj->prepareUserVerificationCode($data['user_email']);
        }

        $link = UrlHelper::generateFullUrl('GuestUser', 'changeEmailVerification', array('verify' => $verificationCode), CONF_WEBROOT_FRONTEND);

        $email = new EmailHandler();
        if (!$configureEmail) {
            $dataArr = array(
                'user_name' => $data['user_name'],
                'user_phone_dcode' => ValidateElement::formatDialCode($data['user_phone_dcode']),
                'user_phone' => $data['user_phone'],
                'link' => $link,
                'user_new_email' => $data['user_new_email'],
                'user_email' => $data['user_email'],
            );
            if (!$email->sendChangeEmailRequestNotification($this->siteLangId, $dataArr)) {
                return false;
            }
        }

        $dataArr = array(
            'user_name' => $data['user_name'],
            'link' => $link,
            'user_email' => $data['user_email'],
            'user_phone_dcode' => ValidateElement::formatDialCode($data['user_phone_dcode']),
            'user_phone' => $data['user_phone'],
        );

        if (!$email->sendEmailVerificationLink($this->siteLangId, $dataArr)) {
            return false;
        }
        return true;
    }

    public function includeDateTimeFiles()
    {
        $this->_template->addJs(array('js/jquery-ui-timepicker-addon.js'), false);
    }

    public function includeProductPageJsCss()
    {
        $this->_template->addJs('js/masonry.pkgd.js');
        $this->_template->addJs('js/product-search.js');
        $this->_template->addJs('js/nouislider.js');
        // $this->_template->addJs('js/ion.rangeSlider.js');
        $this->_template->addJs('js/listing-functions.js');
    }

    public function includeDatePickerLangJs()
    {
        $langCode = strtolower(CommonHelper::getLangCode());
        $langCountryCode = strtoupper(CommonHelper::getLangCountryCode());
        $jsPath = CacheHelper::get('datepickerlangfilePath' . $langCode . "-" . $langCountryCode, CONF_DEF_CACHE_TIME, '.txt');
        if ($jsPath) {
            if ($jsPath == 'notfound') {
                return;
            }
            $this->_template->addJs($jsPath);
            return;
        } elseif ($jsPath == 'notfound') {
            return;
        }
        $jsPath = 'js/jqueryui-i18n/datepicker-' . $langCode . '-' . $langCountryCode . '.js';
        $filePath = CONF_APPLICATION_PATH . '/views/' . $jsPath;

        $fileFound = false;
        if (file_exists($filePath)) {
            $fileFound = true;
        }
        if (false == $fileFound) {
            $jsPath = 'js/jqueryui-i18n/datepicker-' . $langCode . '.js';
            $filePath = CONF_APPLICATION_PATH . '/views/' . $jsPath;
            if (file_exists($filePath)) {
                $fileFound = true;
            }
        }

        if (true == $fileFound) {
            $this->_template->addJs($jsPath);
        } else {
            $jsPath = 'notfound';
        }
        CacheHelper::create('datepickerlangfilePath' . $langCode . "-" . $langCountryCode, $jsPath);
    }

    public function getAppTempUserId()
    {
        if (array_key_exists('temp_user_id', $this->app_user) && !empty($this->app_user["temp_user_id"])) {
            return $this->app_user["temp_user_id"];
        }

        if ($this->appToken && UserAuthentication::isUserLogged('', $this->appToken)) {
            $userId = UserAuthentication::getLoggedUserId();
            if ($userId > 0) {
                return $userId;
            }
        }

        $generatedTempId = substr(md5(rand(1, 99999) . microtime()), 0, UserAuthentication::TOKEN_LENGTH);
        return $this->app_user['temp_user_id'] = $generatedTempId;
    }

    private function checkTempTokenLogin()
    {
        if (!in_array($this->_controllerName, ['BuyerController', 'StripeConnectPayController'])) {
            return;
        }

        if (in_array($this->_controllerName, ['BuyerController']) && !in_array($this->_actionName, ['downloadDigitalFile'])) {
            return;
        }

        // $get = FatApp::getQueryStringData();
        $get = $_REQUEST;
        if (empty($get) || !array_key_exists('ttk', $get)) {
            return;
        }

        $ttk = ($get['ttk'] != '') ? $get['ttk'] : '';

        if (strlen($ttk) != UserAuthentication::TOKEN_LENGTH) {
            FatUtility::dieJSONError(Labels::getLabel('ERR_INVALID_TEMP_TOKEN', CommonHelper::getLangId()));
        }

        $userId = 0;
        if (!empty($get) && array_key_exists('user_id', $get)) {
            $userId = FatUtility::int($get['user_id']);
        }

        $uObj = new User($userId);
        if (!$uObj->validateAPITempToken($ttk)) {
            FatUtility::dieJSONError(Labels::getLabel('ERR_INVALID_TOKEN_DATA', CommonHelper::getLangId()));
        }

        if (!$user = $uObj->getUserInfo(array('credential_username', 'credential_password', 'user_id'), true, true)) {
            FatUtility::dieJSONError(Labels::getLabel('ERR_INVALID_REQUEST', CommonHelper::getLangId()));
        }

        $authentication = new UserAuthentication();
        if ($authentication->login($user['credential_username'], $user['credential_password'], $_SERVER['REMOTE_ADDR'], false)) {
            $uObj->deleteUserAPITempToken();
        }
    }

    public function translateLangFields($tbl, $data)
    {
        if (!empty($tbl) && !empty($data)) {
            $updateLangDataobj = new TranslateLangData($tbl);
            $translatedText = $updateLangDataobj->directTranslate($data);
            if (false === $translatedText) {
                FatUtility::dieJsonError($updateLangDataobj->getError());
            }
            return $translatedText;
        }
        FatUtility::dieJsonError(Labels::getLabel('ERR_INVALID_REQUEST', $this->siteLangId));
    }

    protected function getPhoneNumberForm()
    {
        $frm = new Form('phoneNumberFrm');
        $frm->addHiddenField('', 'user_phone_dcode');
        $frm->addRequiredField(Labels::getLabel('LBL_PHONE_NUMBER', $this->siteLangId), 'user_phone', '', array('placeholder' => Labels::getLabel('LBL_PHONE_NUMBER', $this->siteLangId)));

        $frm->addSubmitButton('', 'btn_submit', Labels::getLabel('BTN_GET_OTP', $this->siteLangId));
        return $frm;
    }

    public function validateOtpApi($updateToDb = 0, $doLogin = true)
    {
        $updateToDb = FatUtility::int($updateToDb);
        $recoverPwd = FatApp::getPostedData('recoverPwd', FatUtility::VAR_INT, 0);
        $doLogin = 0 < $recoverPwd ? false : $doLogin;

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

        $userId = FatApp::getPostedData('user_id', FatUtility::VAR_INT, 0);
        $userId = 1 > $userId ?  UserAuthentication::getLoggedUserId(true) : $userId;

        $obj = new User($userId);
        $resp = $obj->verifyUserPhoneOtp($otp, ($doLogin && false === MOBILE_APP_API_CALL && !UserAuthentication::isUserLogged()), true);
        if (false == $resp) {
            LibHelper::dieJsonError($obj->getError());
        }
        $this->set('msg', Labels::getLabel('MSG_OTP_MATCHED_SUCCESSFULLY.', $this->siteLangId));

        if (0 < $recoverPwd && true === MOBILE_APP_API_CALL) {
            $obj = new UserAuthentication();
            $record = $obj->getUserResetPwdToken($userId);
            $token = $record['uprr_token'];
            $this->set('data', ['token' => $token]);
            $this->_template->render();
        }

        if (0 < $updateToDb) {
            $userObj = clone $obj;
            $userObj->assignValues(['user_phone_dcode' => $resp['upv_phone_dcode'], 'user_phone' => $resp['upv_phone']]);
            if (!$userObj->save()) {
                LibHelper::dieJsonError($userObj->getError());
            }
            $this->set('msg', Labels::getLabel('MSG_UPDATED_SUCCESSFULLY', $this->siteLangId));
        }

        if (true === MOBILE_APP_API_CALL) {
            if (!UserAuthentication::isUserLogged()) {
                $uObj = new User($userId);
                if (!$token = $uObj->setMobileAppToken()) {
                    LibHelper::dieJsonError(Labels::getLabel('ERR_INVALID_REQUEST', $this->siteLangId));
                }

                $userInfo = $uObj->getUserInfo(array('user_name', 'user_id', 'user_phone_dcode', 'user_phone', 'credential_email', 'credential_active', 'credential_verified'), false, false, true);
                if (!empty($userInfo) && $userInfo['credential_active'] == applicationConstants::INACTIVE) {
                    LibHelper::dieJsonError(Labels::getLabel('ERR_USER_NOT_ACTIVE', $this->siteLangId));
                } elseif (!empty($userInfo) && $userInfo['credential_verified'] == applicationConstants::NO) {
                    LibHelper::dieJsonError(Labels::getLabel('ERR_USER_NOT_VERIFIED', $this->siteLangId));
                }
                $data = array_merge(['token' => $token], $userInfo);
                $this->set('data', $data);
            }

            $this->_template->render();
        }
    }

    /*
     * You can override this function in child class if that class required any external js library.
     */
    public function getExternalLibraries()
    {
        $json['libraries'] = [];
        FatUtility::dieJsonSuccess($json);
    }


    /**
     * getTransferBankForm
     *
     * @param  mixed $langId
     * @param  mixed $orderId
     * @return object
     */
    public function getTransferBankForm(int $langId, $orderId = ''): object
    {
        $frm = new Form('frmPayment');
        $frm->addHiddenField('', 'opayment_order_id', $orderId);
        $frm->addTextBox(Labels::getLabel('LBL_PAYMENT_METHOD', $langId), 'opayment_method');
        $frm->addTextBox(Labels::getLabel('LBL_TXN_ID', $langId), 'opayment_gateway_txn_id');
        $frm->addTextBox(Labels::getLabel('LBL_AMOUNT', $langId), 'opayment_amount')->requirements()->setFloatPositive(true);
        $frm->addTextArea(Labels::getLabel('LBL_COMMENTS', $langId), 'opayment_comments', '');
        $frm->addSubmitButton('&nbsp;', 'btn_submit', Labels::getLabel('BTN_CONFIRM_ORDER', $langId));
        return $frm;
    }

    public function includeFeatherLight()
    {
        $this->_template->addJs(['js/featherlight/featherlight.min.js', 'js/featherlight/featherlight.gallery.min.js', 'js/featherlight/jquery.detect_swipe.min.js']);
        $this->_template->addCss(['css/featherlight/featherlight.min.css', 'css/featherlight/featherlight.gallery.min.css']);
    }

    private function checkMaintenance()
    {
        if (FatApp::getConfig("CONF_MAINTENANCE", FatUtility::VAR_INT, 0) == applicationConstants::NO) {
            return true;
        }

        if (in_array($this->_controllerName, ['MaintenanceController'])) {
            return true;
        }

        if (in_array($this->_actionName, ['setLanguage', 'updateUserCookies'])) {
            return true;
        }

        if (isset($_SESSION[User::ADMIN_SESSION_ELEMENT_NAME]) && FatUtility::int($_SESSION[User::ADMIN_SESSION_ELEMENT_NAME]['admin_id']) > 0) {
            return true;
        }

        $msg = Labels::getLabel('ERR_SITE_UNDER_MAINTENANCE', CommonHelper::getLangId());
        if (true === MOBILE_APP_API_CALL) {
            $data =  [
                'status' => '-2',
                'responseCode' => LibHelper::RC_OK,
                'msg' => $msg
            ];
            CommonHelper::jsonEncodeUnicode($data, true);
        }

        LibHelper::exitWithError($msg, false, true);
        FatApp::redirectUser(UrlHelper::generateUrl('maintenance'));
    }

    public function getRecordsCount(object $recordCountSrch, $isGroupSearch = false, $removeFlds = [])
    {
        $recordCountSrch->doNotLimitRecords();
        if ($isGroupSearch == false) {
            if (!empty($removeFlds)) {
                $recordCountSrch->removeFld($removeFlds);
            }
            $recordCountSrch->addFld('count(1) as totalRecords');
            $recordCountSrch->doNotCalculateRecords();
            $results = FatApp::getDb()->fetch($recordCountSrch->getResultSet());
            $defaultRecordCount = !empty($results['totalRecords']) ? $results['totalRecords'] : 0;
        } else {
            if (!empty($removeFlds)) {
                $recordCountSrch->removeFld($removeFlds);
            }

            $recordCountSrch->getResultSet();
            $defaultRecordCount = $recordCountSrch->recordCount();
        }

        return $defaultRecordCount;
    }

    public function setRecordCount(object $recordCountSrch, int $pageSize, int $page, &$post, $isGroupSearch = false, $removeFlds = [])
    {
        if ($pageSize < 1) {
            return;
        }

        $pageRecordCount = 0;
        if (1 < $page && !empty($post['pageRecordCount'])) {
            $pageRecordCount = FilterHelper::decrypt($post['pageRecordCount']);
        }

        if (0 < $pageRecordCount) {
            $this->setPageRecord($pageRecordCount, $pageSize, $page);
            return;
        }

        $recordCountSrch->doNotLimitRecords();
        if ($isGroupSearch == false) {
            if (!empty($removeFlds)) {
                $recordCountSrch->removeFld($removeFlds);
            }
            $recordCountSrch->addFld('count(1) as totalRecords');
            $recordCountSrch->doNotCalculateRecords();
            $results = FatApp::getDb()->fetch($recordCountSrch->getResultSet());
            $defaultRecordCount = !empty($results['totalRecords']) ? $results['totalRecords'] : 0;
        } else {
            if (!empty($removeFlds)) {
                $recordCountSrch->removeFld($removeFlds);
            }

            $recordCountSrch->getResultSet();
            $defaultRecordCount = $recordCountSrch->recordCount();
        }

        $this->setPageRecord($defaultRecordCount, $pageSize, $page);
        $post['pageRecordCount'] = FilterHelper::encrypt($defaultRecordCount);
    }

    private function setPageRecord($recordCount, $pageSize, $page)
    {
        $this->pageData = [
            'pageCount' => ($recordCount > 0) ? ceil($recordCount / $pageSize) : 0,
            'recordCount' => $recordCount,
            'pageSize' => $pageSize,
            'page' => $page
        ];

        $this->set('pageCount', ($recordCount > 0) ? ceil($recordCount / $pageSize) : 0);
        $this->set('recordCount', $recordCount);
        $this->set('pageSize', $pageSize);
        $this->set('page', $page);
    }
}
