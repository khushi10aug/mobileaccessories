<?php
class AdminBaseController extends FatController
{
    use ProductSetup;

    protected $objPrivilege;
    protected $unAuthorizeAccess;
    protected $admin_id;
    protected $str_update_record;
    protected $str_invalid_request;
    protected $str_invalid_request_id;
    protected $str_delete_record;
    protected $str_invalid_Action;
    protected $str_setup_successful;
    protected $nodes = [];

    public function __construct($action)
    {

        parent::__construct($action);

        if (get_class($this) != 'AdminGuestController' && !FatUtility::isAjaxCall()) {
            $_SESSION['admin_referer_page_url'] = UrlHelper::getCurrUrl();
        }

        if (!AdminAuthentication::isAdminLogged()) {
            // PHP/session expired: close open login history via surviving cookie.
            AdminLoginHistory::closeOpenSessionFromCookie();
            CommonHelper::initCommonVariables(true);
            if ($this->_controllerName != 'HomeController') {
                LibHelper::exitWithError(Labels::getLabel('ERR_SESSION_SEEMS_TO_BE_EXPIRED', CommonHelper::getLangId()), false, true);
            }
            FatApp::redirectUser(UrlHelper::generateUrl('AdminGuest', 'loginForm'));
        }

        $this->admin_id = AdminAuthentication::getLoggedAdminId();

        $permissionUpdatedOn = Admin::getAttributesById($this->admin_id, 'admin_admperm_updated_on');
        if ($_SESSION[AdminAuthentication::SESSION_ELEMENT_NAME]['admin_admperm_updated_on'] != $permissionUpdatedOn) {
            AdminLoginHistory::logLogout($this->admin_id);
            AdminAuthentication::clearLoggedAdminLoginCookie();
            session_destroy();
            LibHelper::exitWithError(Labels::getLabel('ERR_SESSION_SEEMS_TO_BE_EXPIRED', CommonHelper::getLangId()), false, true);
            FatApp::redirectUser(UrlHelper::generateUrl('AdminGuest', 'loginForm'));
        }

        AdminLoginHistory::touchLastActivity();

        $this->objPrivilege = AdminPrivilege::getInstance();


        $this->setCommonValues();
        $this->_template->addCss([CONF_MAIN_CSS_DIR_PATH . '/main-' . CommonHelper::getLayoutDirection() . '.css']);
    }

    /*
    # Function: setCommonValues
    # Description: Function to set the common values.
    */
    private function setCommonValues()
    {
        CommonHelper::initCommonVariables(true);
        $this->siteLangId = CommonHelper::getLangId();

        $curdLangLabelCache = CacheHelper::get('curdLangLabelCache' . $this->siteLangId, CONF_DEF_CACHE_TIME, '.txt');
        if (!$curdLangLabelCache) {
            $arr = [
                'str_update_record' => Labels::getLabel('MSG_RECORD_UPDATED_SUCCESSFULLY', $this->siteLangId),
                'str_invalid_request_id' => Labels::getLabel('ERR_INVALID_REQUEST_ID', $this->siteLangId),
                'str_invalid_request' => Labels::getLabel('ERR_INVALID_REQUEST', $this->siteLangId),
                'str_delete_record' => Labels::getLabel('MSG_RECORD_DELETED_SUCCESSFULLY', $this->siteLangId),
                'str_invalid_Action' => Labels::getLabel('ERR_INVALID_ACTION', $this->siteLangId),
                'str_setup_successful' => Labels::getLabel('MSG_SETUP_SUCCESSFUL', $this->siteLangId)
            ];
            CacheHelper::create('curdLangLabelCache' . $this->siteLangId, serialize($arr), CacheHelper::TYPE_LABELS);
        } else {
            $arr =  unserialize($curdLangLabelCache);
        }

        $this->str_update_record = $arr['str_update_record'];
        $this->str_invalid_request_id = $arr['str_invalid_request_id'];
        $this->str_invalid_request = $arr['str_invalid_request'];
        $this->str_delete_record = $arr['str_delete_record'];
        $this->str_invalid_Action = $arr['str_invalid_Action'];
        $this->str_setup_successful = $arr['str_setup_successful'];

        $languages = Language::getAllNames(false);
        $jsVariables = [];
        if (!FatUtility::isAjaxCall()) {
            $defultCountryId = FatApp::getConfig('CONF_COUNTRY', FatUtility::VAR_INT, 0);
            $defaultCountryCode = Countries::getAttributesById($defultCountryId, 'country_code');

            $jsAdminVariablesCache = FatCache::get('jsAdminVariablesCache' . $this->siteLangId, CONF_DEF_CACHE_TIME, '.txt');
            if (!$jsAdminVariablesCache) {
                $jsVariables = array(
                    'confirmRemove' => Labels::getLabel('MSG_DO_YOU_WANT_TO_REMOVE', $this->siteLangId),
                    'confirmRemoveOption' => Labels::getLabel('MSG_DO_YOU_WANT_TO_REMOVE_THIS_OPTION', $this->siteLangId),
                    'confirmRemoveShop' => Labels::getLabel('MSG_DO_YOU_WANT_TO_REMOVE_THIS_SHOP', $this->siteLangId),
                    'confirmRemoveBrand' => Labels::getLabel('MSG_DO_YOU_WANT_TO_REMOVE_THIS_BRAND', $this->siteLangId),
                    'confirmRemoveProduct' => Labels::getLabel('MSG_DO_YOU_WANT_TO_REMOVE_THIS_PRODUCT', $this->siteLangId),
                    'confirmRemoveCategory' => Labels::getLabel('MSG_DO_YOU_WANT_TO_REMOVE_THIS_CATEGORY', $this->siteLangId),
                    'confirmReset' => Labels::getLabel('MSG_DO_YOU_WANT_TO_RESET_SETTINGS', $this->siteLangId),
                    'confirmActivate' => Labels::getLabel('MSG_DO_YOU_WANT_TO_ACTIVATE_STATUS', $this->siteLangId),
                    'confirmUpdate' => Labels::getLabel('MSG_DO_YOU_WANT_TO_UPDATE', $this->siteLangId),
                    'confirmUpdateStatus' => Labels::getLabel('MSG_DO_YOU_WANT_TO_UPDATE', $this->siteLangId),
                    'confirmDelete' => Labels::getLabel('MSG_DO_YOU_WANT_TO_DELETE', $this->siteLangId),
                    'confirmDeleteImage' => Labels::getLabel('MSG_DO_YOU_WANT_TO_DELETE_IMAGE', $this->siteLangId),
                    'confirmDeleteBackgroundImage' => Labels::getLabel('MSG_DO_YOU_WANT_TO_DELETE_BACKGROUND_IMAGE', $this->siteLangId),
                    'confirmDeleteLogo' => Labels::getLabel('MSG_DO_YOU_WANT_TO_DELETE_LOGO', $this->siteLangId),
                    'confirmDeleteBanner' => Labels::getLabel('MSG_DO_YOU_WANT_TO_DELETE_BANNER', $this->siteLangId),
                    'confirmDeleteIcon' => Labels::getLabel('MSG_DO_YOU_WANT_TO_DELETE_ICON', $this->siteLangId),
                    'confirmDefault' => Labels::getLabel('MSG_DO_YOU_WANT_TO_SET_DEFAULT', $this->siteLangId),
                    'setMainProduct' => Labels::getLabel('LBL_SET_AS_MAIN_PRODUCT', $this->siteLangId),
                    'layoutDirection' => CommonHelper::getLayoutDirection(),
                    'selectPlan' => Labels::getLabel('LBL_PLEASE_SELECT_ANY_PLAN', $this->siteLangId),
                    'alreadyHaveThisPlan' => Labels::getLabel('LBL_ALREADY_HAVE_THIS_PLAN', $this->siteLangId),
                    'invalidRequest' => Labels::getLabel('ERR_INVALID_REQUEST!', $this->siteLangId),
                    'pleaseWait' => Labels::getLabel('LBL_PLEASE_WAIT...', $this->siteLangId),
                    'DoYouWantTo' => Labels::getLabel('LBL_DO_YOU_REALLY_WANT_TO', $this->siteLangId),
                    'theRequest' => Labels::getLabel('LBL_THE_REQUEST', $this->siteLangId),
                    'confirmCancelOrder' => Labels::getLabel('LBL_ARE_YOU_SURE_TO_CANCEL_THIS_ORDER', $this->siteLangId),
                    'confirmReplaceCurrentToDefault' => Labels::getLabel('LBL_CONFIRM_REPLACE_CURRENT_TO_DEFAULT', $this->siteLangId),
                    'processing' => Labels::getLabel('LBL_PROCESSING...', $this->siteLangId),
                    'preferredDimensions' => Labels::getLabel('LBL_PREFERRED_DIMENSIONS_%s', $this->siteLangId),
                    'confirmRestore' => Labels::getLabel('LBL_DO_YOU_WANT_TO_RESTORE', $this->siteLangId),
                    'thanksForSharing' => Labels::getLabel('LBL_MSG_THANKS_FOR_SHARING', $this->siteLangId),
                    'isMandatory' => Labels::getLabel('VLBL_IS_MANDATORY', $this->siteLangId),
                    'pleaseEnterValidEmailId' => Labels::getLabel('VLBL_PLEASE_ENTER_VALID_EMAIL_ID_FOR', $this->siteLangId),
                    'charactersSupportedFor' => Labels::getLabel('VLBL_ONLY_CHARACTERS_ARE_SUPPORTED_FOR', $this->siteLangId),
                    'pleaseEnterIntegerValue' => Labels::getLabel('VLBL_PLEASE_ENTER_INTEGER_VALUE_FOR', $this->siteLangId),
                    'pleaseEnterNumericValue' => Labels::getLabel('VLBL_PLEASE_ENTER_NUMERIC_VALUE_FOR', $this->siteLangId),
                    'startWithLetterOnlyAlphanumeric' => Labels::getLabel('LBL_START_WITH_LETTER_ONLY_ALPHANUMERIC', $this->siteLangId),
                    'mustBeBetweenCharacters' => Labels::getLabel('VLBL_LENGTH_MUST_BE_BETWEEN_6_TO_20_CHARACTERS', $this->siteLangId),
                    'invalidValues' => Labels::getLabel('VLBL_LENGTH_INVALID_VALUE_FOR', $this->siteLangId),
                    'shouldNotBeSameAs' => Labels::getLabel('VLBL_SHOULD_NOT_BE_SAME_AS', $this->siteLangId),
                    'mustBeSameAs' => Labels::getLabel('VLBL_MUST_BE_SAME_AS', $this->siteLangId),
                    'mustBeGreaterOrEqual' => Labels::getLabel('VLBL_MUST_BE_GREATER_THAN_OR_EQUAL_TO', $this->siteLangId),
                    'mustBeGreaterThan' => Labels::getLabel('VLBL_MUST_BE_GREATER_THAN', $this->siteLangId),
                    'mustBeLessOrEqual' => Labels::getLabel('VLBL_MUST_BE_LESS_THAN_OR_EQUAL_TO', $this->siteLangId),
                    'mustBeLessThan' => Labels::getLabel('VLBL_MUST_BE_LESS_THAN', $this->siteLangId),
                    'lengthOf' => Labels::getLabel('VLBL_LENGTH_OF', $this->siteLangId),
                    'valueOf' => Labels::getLabel('VLBL_VALUE_OF', $this->siteLangId),
                    'mustBeBetween' => Labels::getLabel('VLBL_MUST_BE_BETWEEN', $this->siteLangId),
                    'and' => Labels::getLabel('VLBL_AND', $this->siteLangId),
                    'pleaseSelect' => Labels::getLabel('VLBL_PLEASE_SELECT', $this->siteLangId),
                    'to' => Labels::getLabel('VLBL_TO', $this->siteLangId),
                    'options' => Labels::getLabel('VLBL_OPTIONS', $this->siteLangId),
                    'isNotAvailable' => Labels::getLabel('VLBL_IS_NOT_AVAILABLE', $this->siteLangId),
                    'confirmRestoreBackup' => Labels::getLabel('LBL_DO_YOU_WANT_TO_RESTORE_DATABASE_TO_THIS_RECORD', $this->siteLangId),
                    'confirmChangeRequestStatus' => Labels::getLabel('LBL_DO_YOU_WANT_TO_CHANGE_REQUEST_STATUS', $this->siteLangId),
                    'confirmTruncateUserData' => Labels::getLabel('LBL_DO_YOU_WANT_TO_TRUNCATE_USER_DATA', $this->siteLangId),
                    'atleastOneRecord' => Labels::getLabel('LBL_PLEASE_SELECT_ATLEAST_ONE_RECORD.', $this->siteLangId),
                    'primaryLanguageField' => Labels::getLabel('LBL_PRIMARY_LANGUAGE_FIELD_DATA_REQUIRED', $this->siteLangId),
                    'updateCurrencyRates' => Labels::getLabel('LBL_WANT_TO_UPDATE_CURRENCY_RATES?.', $this->siteLangId),
                    'cloneNotification' => Labels::getLabel('LBL_DO_YOU_REALLY_WANT_TO_CLONE?', $this->siteLangId),
                    'clonedNotification' => Labels::getLabel('LBL_NOTIFICATION_CLONED_SUCCESSFULLY', $this->siteLangId),
                    'confirmRemoveBlog' => Labels::getLabel('LBL_DO_YOU_WANT_TO_REMOVE_THIS_BLOG', $this->siteLangId),
                    'actionButtonsClass' => Labels::getLabel('LBL_ACTION_BUTTONS_CLASS_REQUIREMENT', $this->siteLangId),
                    'allowedFileSize' => LibHelper::getMaximumFileUploadSize(),
                    'fileSizeExceeded' => Labels::getLabel("MSG_FILE_SIZE_SHOULD_BE_LESSER_THAN_{SIZE-LIMIT}", $this->siteLangId),
                    'currentPrice' => Labels::getLabel('LBL_CURRENT_PRICE', $this->siteLangId),
                    'currentStock' => Labels::getLabel('LBL_CURRENT_STOCK', $this->siteLangId),
                    'discountPercentage' => Labels::getLabel('LBL_DISCOUNT_PERCENTAGE', $this->siteLangId),
                    'shippingUser' => Labels::getLabel('MSG_Please_assign_shipping_user', $this->siteLangId),
                    'saveProfileFirst' => Labels::getLabel('LBL_SAVE_PROFILE_FIRST', $this->siteLangId),
                    'minimumOneLocationRequired' => Labels::getLabel('LBL_MINIMUM_ONE_LOCATION_IS_REQUIRED', $this->siteLangId),
                    'confirmTransfer' => Labels::getLabel('LBL_CONFIRM_TRANSFER_?', $this->siteLangId),
                    'invalidFromTime' => Labels::getLabel('LBL_PLEASE_SELECT_VALID_FROM_TIME', $this->siteLangId),
                    'selectTimeslotDay' => Labels::getLabel('LBL_ATLEAST_ONE_DAY_AND_TIMESLOT_NEEDS_TO_BE_CONFIGURED', $this->siteLangId),
                    'invalidTimeSlot' => Labels::getLabel('LBL_PLEASE_CONFIGURE_FROM_AND_TO_TIME', $this->siteLangId),
                    'noRecordFound' => Labels::getLabel('LBL_NO_RECORD_FOUND', $this->siteLangId),
                    'disableChildCategories' => Labels::getLabel('LBL_DISABLE_CHILD_CATEGORY_VALIDATION', $this->siteLangId),
                    'areYouSure' => Labels::getLabel('LBL_ARE_YOU_SURE?', $this->siteLangId),
                    'enableParentCategories' => Labels::getLabel('LBL_ENABLE_PARENT_CATEGORIES_VALIDATION', $this->siteLangId),
                    'defaultCountryCode' => $defaultCountryCode,
                    'dialCodeFieldNotFound' => Labels::getLabel('LBL_DIAL_CODE_FIELD_NOT_FOUND', $this->siteLangId),
                    'copied' => Labels::getLabel('LBL_COPIED', $this->siteLangId),
                    'rateFromDecimal' => Labels::getLabel('LBL_RATE_FROM(DECIMAL)', $this->siteLangId),
                    'rateToDecimal' => Labels::getLabel('LBL_RATE_TO(DECIMAL)', $this->siteLangId),
                    'fromDigit' => Labels::getLabel('LBL_FROM(DIGIT)', $this->siteLangId),
                    'toDigit' => Labels::getLabel('LBL_TO(DIGIT)', $this->siteLangId),
                    'fromDecimal' => Labels::getLabel('LBL_FROM(DECIMAL)', $this->siteLangId),
                    'toDecimal' => Labels::getLabel('LBL_TO(DECIMAL)', $this->siteLangId),
                    'unlinkRecords' => Labels::getLabel('LBL_FIRST_UNLINK_ALL_RECORDS', $this->siteLangId),
                    'remove' => Labels::getLabel('LBL_REMOVE', $this->siteLangId),
                    'alreadySelected' => Labels::getLabel('MSG_ALREADY_SELECTED', $this->siteLangId),
                    'controllerNameRequired' => Labels::getLabel('MSG_CONTROLLER_NAME_MUST_BE_DECLARED', $this->siteLangId),
                    'selectFont' => Labels::getLabel('MSG_PLEASE_SELECT_FONT_FAMILY', $this->siteLangId),
                    'dropFilesToUpload' => Labels::getLabel('MSG_DROP_FILES_HERE_TO_UPLOAD', $this->siteLangId),
                    'invalidUploadFileType' => Labels::getLabel('MSG_INVALID_FILE_TYPE._ONLY_{FILE-TYPE}_FILE_CAN_BE_UPLOADED', $this->siteLangId),
                    'clickToCopy' => Labels::getLabel('LBL_CLICK_TO_COPY', $this->siteLangId),
                    'confirmAsBuyer' => Labels::getLabel('LBL_DO_YOU_WANT_TO_MARK_THIS_USER_AS_BUYER?', $this->siteLangId),
                    'maxLengthValidator' => CommonHelper::replaceStringData(Labels::getLabel('FRM_USED_{charsTyped}_of_{charsTotal}_CHAR', $this->siteLangId), ["{charsTyped}" => "%charsTyped%", "{charsTotal}" => "%charsTotal%"]), /* Used By Maxlength bootstrap validator. */
                    'unread' => Labels::getLabel('LBL_UNREAD', $this->siteLangId),
                    'notANumber' => Labels::getLabel('ERR_NOT_A_NUMBER', $this->siteLangId),
                    'invalidState' => Labels::getLabel('ERR_INVALID_STATE', $this->siteLangId),
                    'off' => Labels::getLabel('LBL_OFF', $this->siteLangId),
                    'systemIdentifier' => Labels::getLabel('LBL_SYSTEM_IDENTIFIER', $this->siteLangId),
                    'clickToHide' => Labels::getLabel('LBL_CLICK_TO_HIDE', $this->siteLangId),
                    'clickToExpand' => Labels::getLabel('LBL_CLICK_TO_EXPAND', $this->siteLangId),
                    'total' => Labels::getLabel('LBL_TOTAL', $this->siteLangId),
                    'enableRfqModule' => Labels::getLabel('LBL_PLEASE_ENABLE_RFQ_MODULE_FIRST.', $this->siteLangId),
                    'disableHidePriceSettings' => Labels::getLabel('LBL_PLEASE_DISABLE_HIDE_PRICE_SETTING_FIRST.', $this->siteLangId),
                    'confirmDisableRfq' => Labels::getLabel('LBL_ARE_YOU_SURE?_GLOBAL_RFQ_MODULE_WILL_BE_DISABLED', $this->siteLangId),
                );
                foreach ($languages as $val) {
                    if (empty($val)) {
                        continue;
                    }
                    $jsVariables['language' . $val['language_id']] = $val['language_layout_direction'];
                    $jsVariables['defaultFormLangId'] = CommonHelper::getDefaultFormLangId();
                }
                $jsVariables['languages'] = $languages;
                FatCache::set('jsAdminVariablesCache' . $this->siteLangId, serialize($jsVariables), '.txt');
            } else {
                $jsVariables =  unserialize($jsAdminVariablesCache);
            }
            $this->set('jsVariables', $jsVariables);

            $this->includeDatePickerLangJs();

            $this->set('bodyClass', 'fb-body');
        }

        $this->set('languages', $languages);
        $this->set('siteLangId', $this->siteLangId);
        $this->set('isAdminLogged', AdminAuthentication::isAdminLogged());
        $this->siteDefaultCurrencyCode = CommonHelper::getCurrencyCode();
        $this->set('siteDefaultCurrencyCode', $this->siteDefaultCurrencyCode);
    }

    public function getBreadcrumbNodes($action)
    {
        if (FatUtility::isAjaxCall()) {
            return;
        }

        $className = get_class($this);
        $arr = explode('-', FatUtility::camel2dashed($className));
        array_pop($arr);
        $urlController = implode('-', $arr);
        $className = mb_strtoupper(implode('_', $arr));

        $pageTitle = '';
        if (isset($this->pageKey)) {
            $pageData = PageLanguageData::getAttributesByKey($this->pageKey, $this->siteLangId);
            $pageTitle = $pageData['plang_title'] ?? '';
        }

        if (!empty($pageTitle)) {
            $this->nodes[] = array('title' => $pageTitle);
        } else if ($action == 'index') {
            $this->nodes[] = array('title' => ucwords(Labels::getLabel('BCN_' . $className)));
        } else {
            $arr = explode('-', FatUtility::camel2dashed($action));
            $action = mb_strtoupper(implode('_', $arr));
            $this->nodes[] = array('title' => ucwords(Labels::getLabel('BCN_' . $className)), 'href' => UrlHelper::generateUrl($urlController));
            $this->nodes[] = array('title' => ucwords(Labels::getLabel('BCN_' . $action)));
        }
        return $this->nodes;
    }

    public function includeDatePickerLangJs()
    {
        $langCode = strtolower(CommonHelper::getLangCode());
        $langCountryCode = strtoupper(CommonHelper::getLangCountryCode());
        $jsPath = FatCache::get('datepickerlangfilePath' . $langCode . "-" . $langCountryCode, CONF_DEF_CACHE_TIME, '.txt');
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
        FatCache::set('datepickerlangfilePath' . $langCode . "-" . $langCountryCode, $jsPath, '.txt');
    }

    public function getStates($countryId, $stateId = 0, $langId = 0, $idCol = 'state_id')
    {
        $countryId = FatUtility::int($countryId);
        $langId = FatUtility::int($langId);

        if ($langId == 0) {
            $langId = $this->siteLangId;
        }

        $stateObj = new States();
        $statesArr = $stateObj->getStatesByCountryId($countryId, $this->siteLangId, true, $idCol);

        $this->set('statesArr', $statesArr);
        $this->set('stateId', $stateId);

        $this->set('html', $this->_template->render(false, false, '_partial/states-list.php', true));
        $this->_template->render(false, false, 'json-success.php', true, false);
    }

    public function getStatesByCountryCode($countryCode, $stateCode = '', $idCol = 'state_id')
    {
        $countryId = Countries::getCountryByCode($countryCode, 'country_id');
        $this->getStates($countryId, $stateCode, $this->siteLangId, $idCol);
    }

    protected function getUserForm($user_id = 0, $userType = 0)
    {
        $user_id = FatUtility::int($user_id);
        $userType = FatUtility::int($userType);

        $frm = new Form('frmUser', array('id' => 'frmUser'));
        $frm->addHiddenField('', 'user_id', $user_id);
        $frm->addHiddenField('', 'user_type');
        $frm->addTextBox(Labels::getLabel('FRM_USERNAME', $this->siteLangId), 'credential_username', '');
        $frm->addRequiredField(Labels::getLabel('FRM_CUSTOMER_NAME', $this->siteLangId), 'user_name');
        $frm->addDateField(Labels::getLabel('FRM_DATE_OF_BIRTH', $this->siteLangId), 'user_dob', '', array('readonly' => 'readonly', 'class' => 'field--calender'));
        $frm->addHiddenField('', 'user_phone_dcode');
        $phnFld = $frm->addTextBox(Labels::getLabel('FRM_PHONE', $this->siteLangId), 'user_phone', '', array('class' => 'phoneJs ltr-right', 'placeholder' => ValidateElement::PHONE_NO_FORMAT, 'maxlength' => ValidateElement::PHONE_NO_LENGTH));
        $phnFld->requirements()->setRegularExpressionToValidate(ValidateElement::PHONE_REGEX);
        $phnFld->requirements()->setCustomErrorMessage(Labels::getLabel('FRM_PLEASE_ENTER_VALID_PHONE_NUMBER.', $this->siteLangId));

        $frm->addEmailField(Labels::getLabel('FRM_EMAIL', $this->siteLangId), 'credential_email', '');

        $countryObj = new Countries();
        $countriesArr = $countryObj->getCountriesAssocArr($this->siteLangId);
        $fld = $frm->addSelectBox(Labels::getLabel('FRM_COUNTRY', $this->siteLangId), 'user_country_id', $countriesArr, FatApp::getConfig('CONF_COUNTRY', FatUtility::VAR_INT, 223), array(), Labels::getLabel('FRM_SELECT', $this->siteLangId));
        $fld->requirement->setRequired(true);

        $frm->addSelectBox(Labels::getLabel('FRM_STATE', $this->siteLangId), 'user_state_id', array(), '', [], Labels::getLabel('FRM_SELECT', $this->siteLangId))->requirement->setRequired(true);
        $frm->addTextBox(Labels::getLabel('FRM_CITY', $this->siteLangId), 'user_city');

        switch ($userType) {
            case User::USER_TYPE_SHIPPING_COMPANY:
                $frm->addTextBox(Labels::getLabel('FRM_TRACKING_SITE_URL', $this->siteLangId), 'user_order_tracking_url');
                break;
        }

        $frm->addSubmitButton('', 'btn_submit', Labels::getLabel('BTN_SAVE_CHANGES', $this->siteLangId));
        return $frm;
    }

    protected function getSellerOrderSearchForm($langId)
    {
        $currency_id = FatApp::getConfig('CONF_CURRENCY', FatUtility::VAR_INT, 1);
        $currencyData = Currency::getAttributesById($currency_id, array('currency_code', 'currency_symbol_left', 'currency_symbol_right'));
        $currencySymbol = ($currencyData['currency_symbol_left'] != '') ? $currencyData['currency_symbol_left'] : $currencyData['currency_symbol_right'];

        $frm = new Form('frmVendorOrderSearch');
        $keyword = $frm->addTextBox(Labels::getLabel('FRM_KEYWORDS', $this->siteLangId), 'keyword', '', array('id' => 'keyword', 'autocomplete' => 'off'));
        $frm->addTextBox(Labels::getLabel('FRM_BUYER', $this->siteLangId), 'buyer', '');
        $frm->addSelectBox(Labels::getLabel('FRM_STATUS', $this->siteLangId), 'op_status_id', Orders::getOrderStatusArr($langId), '', array(), Labels::getLabel('FRM_ALL', $langId));
        $frm->addTextBox(Labels::getLabel('FRM_SELLER/Shop', $this->siteLangId), 'shop_name');
        /* $frm->addTextBox(Labels::getLabel('FRM_CUSTOMER',$this->siteLangId),'customer_name'); */

        $frm->addDateField(Labels::getLabel('FRM_DATE_FROM', $this->siteLangId), 'date_from', '', array('placeholder' => Labels::getLabel('FRM_DATE_FROM', $this->siteLangId), 'readonly' => 'readonly', 'class' => 'field--calender'));
        $frm->addDateField(Labels::getLabel('FRM_DATE_TO', $this->siteLangId), 'date_to', '', array('placeholder' => Labels::getLabel('FRM_DATE_TO', $this->siteLangId), 'readonly' => 'readonly', 'class' => 'field--calender'));
        $frm->addTextBox(Labels::getLabel('FRM_AMOUNT_FROM', $this->siteLangId), 'price_from', '', array('placeholder' => Labels::getLabel('FRM_AMOUNT_FROM', $this->siteLangId) . ' [' . $currencySymbol . ']'));
        $frm->addTextBox(Labels::getLabel('FRM_AMOUNT_TO', $this->siteLangId), 'price_to', '', array('placeholder' => Labels::getLabel('FRM_AMOUNT_TO', $this->siteLangId) . ' [' . $currencySymbol . ']'));

        $frm->addHiddenField('', 'page');
        $frm->addHiddenField('', 'user_id');
        $frm->addHiddenField('', 'order_id');
        $frm->addHiddenField('', 'shipping_company_user_id', 0);
        $fld_submit = $frm->addSubmitButton('&nbsp;', 'btn_submit', Labels::getLabel('BTN_SEARCH', $this->siteLangId));
        $fld_cancel = $frm->addButton("", "btn_clear", Labels::getLabel('BTN_CLEAR', $this->siteLangId));
        $fld_submit->attachField($fld_cancel);
        return $frm;
    }

    protected function getProductCatalogForm($attrgrp_id = 0, $type = 'CUSTOM_PRODUCT', $productType = Product::PRODUCT_TYPE_PHYSICAL)
    {
        $langId = $this->siteLangId;
        $this->objPrivilege->canViewProducts();
        $frm = new Form('frmProduct', array('id' => 'frmProduct'));
        if ($type == 'CUSTOM_PRODUCT') {
            $fld = $frm->addTextBox(Labels::getLabel('FRM_USER', $this->siteLangId), 'selprod_user_shop_name', '', array(' ' => ' '));
            $fld->htmlAfterField = '<span class="form-text text-muted">' . Labels::getLabel('FRM_PLEASE_LEAVE_EMPTY_IF_YOU_WANT_TO_ADD_PRODUCT_IN_SYSTEM_CATALOG', $this->siteLangId) . ' </span>';
            $frm->addHtml('', 'user_shop', '<div id="user_shop_name"></div>');
        }

        $frm->addHiddenField('', 'product_seller_id');
        $fld = $frm->addRequiredField(Labels::getLabel('FRM_PRODUCT_IDENTIFIER', $this->siteLangId), 'product_identifier');
        $fld->htmlAfterField = '<span class="form-text text-muted">' . Labels::getLabel('FRM_IT_MAY_BE_SAME_AS_OF_PRODUCT_NAME', $this->siteLangId) . ' </span>';

        $pTypeFld = $frm->addSelectBox(Labels::getLabel('FRM_PRODUCT_TYPE', $this->siteLangId), 'product_type', Product::getProductTypes($langId), Product::PRODUCT_TYPE_PHYSICAL, array('id' => 'product_type'), '');

        if (!FatApp::getConfig('CONF_WITHOUT_PROD_VARIANTS', FatUtility::VAR_INT, 0)) {
            $frm->addSelectBox(Labels::getLabel('FRM_PRODUCT_DOWNLOAD_ATTACHEMENTS_AT_INVENTORY_LEVEL', $this->siteLangId), 'product_attachements_with_inventory', applicationConstants::getYesNoArr($this->siteLangId), '', array(), '');
        } else {
            $frm->addHiddenField('', 'product_attachements_with_inventory', 0);
        }

        /* $downloadAttachementsWithInventoryTrue = new FormFieldRequirement('product_attachements_with_inventory', 'value');
$downloadAttachementsWithInventoryTrue->setRequired();
$downloadAttachementsWithInventoryFalse = new FormFieldRequirement('product_attachements_with_inventory', 'value');
$downloadAttachementsWithInventoryFalse->setRequired(false);

$prodTypeFld = $frm->getField('product_type');
$prodTypeFld->requirements()->addOnChangerequirementUpdate(applicationConstants::YES, 'eq', 'product_attachements_with_inventory', $downloadAttachementsWithInventoryTrue);
$prodTypeFld->requirements()->addOnChangerequirementUpdate(applicationConstants::NO, 'eq', 'product_attachements_with_inventory', $downloadAttachementsWithInventoryFalse); */

        if ($type == 'REQUESTED_CATALOG_PRODUCT') {
            $brandFld = $frm->addTextBox(Labels::getLabel('FRM_BRAND/Manfacturer', $this->siteLangId), 'brand_name');
            if (FatApp::getConfig("CONF_PRODUCT_BRAND_MANDATORY", FatUtility::VAR_INT, 1)) {
                $brandFld->requirements()->setRequired();
            }

            //$fld1 = $frm->addTextBox(Labels::getLabel('FRM_CATEGORY',$this->siteLangId),'category_name');

            $frm->addHiddenField('', 'product_brand_id');
            $frm->addHiddenField('', 'product_category_id');
            $frm->addHiddenField('', 'preq_id');
            $frm->addHiddenField('', 'product_options');
        }

        $fld_model = $frm->addTextBox(Labels::getLabel('FRM_MODEL', $this->siteLangId), 'product_model');
        if (FatApp::getConfig("CONF_PRODUCT_MODEL_MANDATORY", FatUtility::VAR_INT, 1)) {
            $fld_model->requirements()->setRequired();
        }
        $frm->addCheckBox(Labels::getLabel('FRM_PRODUCT_FEATURED', $this->siteLangId), 'product_featured', 1, array(), false, 0);

        $fld = $frm->addFloatField(Labels::getLabel('FRM_MINIMUM_SELLING_PRICE', $langId) . ' [' . CommonHelper::getCurrencySymbol(true) . ']', 'product_min_selling_price', '');
        $fld->requirements()->setRange('0.01', '99999999.99');

        $fld = $frm->addRequiredField(Labels::getLabel('FRM_PRODUCT_WARRANTY', $this->siteLangId), 'product_warranty');
        $fld->requirements()->setInt();
        $fld->requirements()->setPositive();
        $fld->htmlAfterField = '<span class="form-text text-muted">' . Labels::getLabel('FRM_WARRANTY_IN_DAYS', $this->siteLangId) . ' </span>';
        if (Product::PRODUCT_TYPE_DIGITAL == $productType) {
            $fld->requirements()->setRequired(false);
        }
        $taxCategories = Tax::getSaleTaxCatArr($this->siteLangId);
        $frm->addSelectBox(Labels::getLabel('FRM_TAX_CATEGORY', $this->siteLangId), 'ptt_taxcat_id', $taxCategories, '', array(), Labels::getLabel('FRM_SELECT', $this->siteLangId))->requirements()->setRequired(true);

        if (Product::PRODUCT_TYPE_PHYSICAL == $productType) {
            $shipProfileArr = ShippingProfile::getProfileArr($this->siteLangId, 0, true, true);
            if ($type == 'REQUESTED_CATALOG_PRODUCT') {
                $fulFillmentArr = Shipping::getFulFillmentArr($this->siteLangId, FatApp::getConfig('CONF_FULFILLMENT_TYPE', FatUtility::VAR_INT, -1));
                $fulFillmentTypeFld = $frm->addSelectBox(Labels::getLabel('FRM_FULFILLMENT_METHOD', $this->siteLangId), 'product_fulfillment_type', $fulFillmentArr, applicationConstants::NO, ['class' => 'fieldsVisibilityJs'], Labels::getLabel('FRM_SELECT', $this->siteLangId));
                $fulFillmentTypeFld->requirements()->setRequired();
            }
            $frm->addSelectBox(Labels::getLabel('FRM_SHIPPING_PROFILE', $this->siteLangId), 'shipping_profile', $shipProfileArr, '', [], Labels::getLabel('FRM_SELECT', $this->siteLangId))->requirements()->setRequired();
            if ($fulFillmentTypeFld) {
                $profileUnReqObj = new FormFieldRequirement('shipping_profile', Labels::getLabel('FRM_SHIPPING_PROFILE', $this->siteLangId));
                $profileUnReqObj->setRequired(false);
                $profileReqObj = new FormFieldRequirement('shipping_profile', Labels::getLabel('FRM_SHIPPING_PROFILE', $this->siteLangId));
                $profileReqObj->setRequired(true);

                $fulFillmentTypeFld->requirements()->addOnChangerequirementUpdate(Shipping::FULFILMENT_PICKUP, 'eq', 'shipping_profile', $profileUnReqObj);
                $fulFillmentTypeFld->requirements()->addOnChangerequirementUpdate(Shipping::FULFILMENT_PICKUP, 'ne', 'shipping_profile', $profileReqObj);
            }
        }

        if (FatApp::getConfig("CONF_PRODUCT_DIMENSIONS_ENABLE", FatUtility::VAR_INT, 1)) {
            if (Product::PRODUCT_TYPE_PHYSICAL == $productType) {
                $shipPackArr = ShippingPackage::getAllNames();
                $frm->addSelectBox(Labels::getLabel('FRM_SHIPPING_PACKAGE', $this->siteLangId), 'product_ship_package', $shipPackArr, '', [], Labels::getLabel('FRM_SELECT', $this->siteLangId))->requirements()->setRequired();
            }
        }

        if (FatApp::getConfig("CONF_PRODUCT_WEIGHT_ENABLE", FatUtility::VAR_INT, 1)) {
            /* weight unit[ */
            $weightUnitsArr = applicationConstants::getWeightUnitsArr($langId);
            $frm->addSelectBox(Labels::getLabel('FRM_WEIGHT_UNIT', $langId), 'product_weight_unit', $weightUnitsArr, '', [], Labels::getLabel('FRM_SELECT', $this->siteLangId))->requirements()->setRequired();
            $pWeightUnitUnReqObj = new FormFieldRequirement('product_weight_unit', Labels::getLabel('FRM_WEIGHT_UNIT', $langId));
            $pWeightUnitUnReqObj->setRequired(false);

            $pWeightUnitReqObj = new FormFieldRequirement('product_weight_unit', Labels::getLabel('FRM_WEIGHT_UNIT', $langId));
            $pWeightUnitReqObj->setRequired(true);
            /* ] */

            /* weight[ */
            $frm->addFloatField(Labels::getLabel('FRM_WEIGHT', $langId), 'product_weight', '0.00');
            $pWeightUnReqObj = new FormFieldRequirement('product_weight', Labels::getLabel('FRM_WEIGHT', $langId));
            $pWeightUnReqObj->setRequired(false);

            $pWeightReqObj = new FormFieldRequirement('product_weight', Labels::getLabel('FRM_WEIGHT', $langId));
            $pWeightReqObj->setRequired(true);
            $pWeightReqObj->setFloatPositive();
            $pWeightReqObj->setRange('0.01', '9999999999');
            /* ] */

            $pTypeFld->requirements()->addOnChangerequirementUpdate(Product::PRODUCT_TYPE_DIGITAL, 'eq', 'product_weight', $pWeightUnReqObj);
            $pTypeFld->requirements()->addOnChangerequirementUpdate(Product::PRODUCT_TYPE_PHYSICAL, 'eq', 'product_weight', $pWeightReqObj);

            $pTypeFld->requirements()->addOnChangerequirementUpdate(Product::PRODUCT_TYPE_DIGITAL, 'eq', 'product_weight_unit', $pWeightUnitUnReqObj);
            $pTypeFld->requirements()->addOnChangerequirementUpdate(Product::PRODUCT_TYPE_PHYSICAL, 'eq', 'product_weight_unit', $pWeightUnitReqObj);
        }

        /* $frm->addTextBox('UPC','product_upc');
$frm->addTextBox('ISBN Code','product_isbn'); */
        if ($type == 'CUSTOM_PRODUCT') {
            $approveUnApproveArr = Product::getApproveUnApproveArr($langId);
            $frm->addSelectBox(Labels::getLabel('FRM_APPROVAL_STATUS', $this->siteLangId), 'product_approved', $approveUnApproveArr, Product::APPROVED, array(), '');
        }

        $activeInactiveArr = applicationConstants::getActiveInactiveArr($langId);
        $frm->addSelectBox(Labels::getLabel('FRM_PRODUCT_STATUS', $this->siteLangId), 'product_active', $activeInactiveArr, applicationConstants::NO, array(), '');

        $yesNoArr = applicationConstants::getYesNoArr($langId);
        $codFld = $frm->addSelectBox(Labels::getLabel('FRM_AVAILABLE_FOR_COD', $this->siteLangId), 'product_cod_enabled', $yesNoArr, applicationConstants::NO, array(), '');

        $paymentMethod = new PaymentMethods();
        if (!$paymentMethod->cashOnDeliveryIsActive()) {
            $codFld->addFieldTagAttribute('disabled', 'disabled');
            $codFld->htmlAfterField = '<span class="form-text text-muted">' . Labels::getLabel('FRM_COD_OPTION_IS_DISABLED_IN_PAYMENT_GATEWAY_SETTINGS', $this->siteLangId) . '</span>';
        }

        if ($type == 'REQUESTED_CATALOG_PRODUCT') {
            $fld1 = $frm->addTextBox(Labels::getLabel('FRM_ADD_OPTION_GROUPS', $this->siteLangId), 'option_name');
            $fld1->htmlAfterField = '<div class="box--scroller"><ul class="columlist list--vertical" id="product-option-js"></ul></div>';

            $fld1 = $frm->addTextBox(Labels::getLabel('FRM_ADD_TAG', $this->siteLangId), 'tag_name');
            $fld1->htmlAfterField = '<div class="box--scroller"><ul class="columlist list--vertical" id="product-tag-js"></ul></div>';
        }
        if ($type != 'REQUESTED_CATALOG_PRODUCT') {
            $frm->addTextBox(Labels::getLabel('FRM_EAN/UPC/GTIN_code', $this->siteLangId), 'product_upc');
        }

        if ($type != 'REQUESTED_CATALOG_PRODUCT') {
            $fld = $frm->addTextBox(Labels::getLabel('FRM_COUNTRY_OF_ORIGIN', $langId), 'shipping_country');
            //$fld = $frm->addCheckBox(Labels::getLabel('FRM_FREE_SHIPPING', $langId), 'ps_free', 1);
            $frm->addHtml('', '', '<table id="tab_shipping" width="100%"></table><div class="gap"></div>');
        }

        $frm->addHiddenField('', 'ps_from_country_id');
        $frm->addHiddenField('', 'product_id');
        $frm->addHiddenField('', 'product_options');


        /* code to input values for the comparison attributes[ */
        if ($attrgrp_id) {
            $db = FatApp::getDb();
            //$attrGrpAttrObj = new AttrGroupAttribute();
            $srch = AttrGroupAttribute::getSearchObject();
            $srch->joinTable(AttrGroupAttribute::DB_TBL . '_lang', 'LEFT JOIN', 'lang.attrlang_attr_id = ' . AttrGroupAttribute::DB_TBL_PREFIX . 'id AND attrlang_lang_id = ' . $langId, 'lang');
            $srch->addCondition(AttrGroupAttribute::DB_TBL_PREFIX . 'attrgrp_id', '=', $attrgrp_id);
            $srch->addCondition(AttrGroupAttribute::DB_TBL_PREFIX . 'type', '!=', 'mysql_func_' . AttrGroupAttribute::ATTRTYPE_TEXT, 'AND', true);
            $srch->addOrder(AttrGroupAttribute::DB_TBL_PREFIX . 'display_order');
            $srch->addMultipleFields(array('attr_identifier', 'attr_type', 'attr_fld_name', 'attr_name', 'attr_options', 'attr_prefix', 'attr_postfix'));
            $rs = $srch->getResultSet();
            $attributes = $db->fetchAll($rs);
            if ($attributes) {
                foreach ($attributes as $attr) {
                    $caption = ($attr['attr_name'] != '') ? $attr['attr_name'] : $attr['attr_identifier'];
                    switch ($attr['attr_type']) {
                        case AttrGroupAttribute::ATTRTYPE_NUMBER:
                            //$fld = $frm->addIntegerField($caption, $attr['attr_fld_name']);
                            $fld = $frm->addFloatField($caption, $attr['attr_fld_name']);
                            break;
                        case AttrGroupAttribute::ATTRTYPE_DECIMAL:
                            $fld = $frm->addFloatField($caption, $attr['attr_fld_name']);
                            break;
                        case AttrGroupAttribute::ATTRTYPE_SELECT_BOX:
                            $arr_options = array();
                            if ($attr['attr_options'] != '') {
                                $arr_options = explode("\n", $attr['attr_options']);
                                if (is_array($arr_options)) {
                                    $arr_options = array_map('trim', $arr_options);
                                }
                            }
                            $fld_txt_box = $frm->addSelectBox($caption, $attr['attr_fld_name'], $arr_options, '', array(), '');
                            break;
                    }
                    if ($attr['attr_prefix'] != '') {
                        $fld->htmlBeforeField = $attr['attr_prefix'];
                    }
                    $postfix_hint = '';
                    if ($attr['attr_postfix'] != '') {
                        $postfix_hint = '(' . $attr['attr_postfix'] . ') ';
                    }
                    $postfix_hint .= " Enter -1 for N.A";
                    $fld->htmlAfterField = '<span class="form-text text-muted">' . $postfix_hint . '</span class="form-text text-muted">';
                }
            }
        }
        $frm->addHiddenField('', 'product_attrgrp_id', $attrgrp_id);
        $frm->addHiddenField('', 'product_id');
        $frm->addSubmitButton('', 'btn_submit', Labels::getLabel('BTN_SAVE_CHANGES', $this->siteLangId));
        return $frm;
    }

    protected function getSellerProductForm($product_id, $type = 'SELLER_PRODUCT')
    {
        $frm = new Form('frmSellerProduct');
        $defaultProductCond = '';

        if ($type == 'REQUESTED_CATALOG_PRODUCT') {
            $reqData = ProductRequest::getAttributesById($product_id, array('preq_content'));
            $productData = array_merge($reqData, json_decode($reqData['preq_content'], true));
            $productData['sellerProduct'] = 0;
            $optionArr = isset($productData['product_option']) ? $productData['product_option'] : array();
            if (!empty($optionArr)) {
                $frm->addHtml('', 'optionSectionHeading', '');
            }
            foreach ($optionArr as $val) {
                $val = FatUtility::int($val);
                $optionSrch = Option::getSearchObject($this->siteLangId);
                $optionSrch->addMultipleFields(array('IFNULL(option_name,option_identifier) as option_name', 'option_id'));
                $optionSrch->doNotCalculateRecords();
                $optionSrch->setPageSize(1);
                $optionSrch->addCondition('option_id', '=', 'mysql_func_' . $val, 'AND', true);
                $rs = $optionSrch->getResultSet();
                $option = FatApp::getDb()->fetch($rs);
                if ($option == false) {
                    continue;
                }
                $optionValues = Product::getOptionValues($option['option_id'], $this->siteLangId);
                $option_name = ($option['option_name'] != '') ? $option['option_name'] : $option['option_identifier'];
                $fld = $frm->addSelectBox($option_name, 'selprodoption_optionvalue_id[' . $option['option_id'] . ']', $optionValues, '', array(), Labels::getLabel('FRM_SELECT', $this->siteLangId));
                $fld->requirements()->setRequired();
            }
        } else {
            $productData = Product::getAttributesById($product_id, array('product_type', 'product_min_selling_price', 'if(product_seller_id > 0, 1, 0) as sellerProduct', 'product_seller_id'));

            if ($productData['product_type'] == Product::PRODUCT_TYPE_DIGITAL) {
                $defaultProductCond = Product::CONDITION_NEW;
            }

            $productOptions = Product::getProductOptions($product_id, $this->siteLangId, true);
            if ($productOptions) {
                $frm->addHtml('', 'optionSectionHeading', '');
                foreach ($productOptions as $option) {
                    $option_name = ($option['option_name'] != '') ? $option['option_name'] : $option['option_identifier'];
                    $fld = $frm->addSelectBox($option_name, 'selprodoption_optionvalue_id[' . $option['option_id'] . ']', $option['optionValues'], '', array(), Labels::getLabel('FRM_SELECT', $this->siteLangId));
                }
            }
            $frm->addTextBox(Labels::getLabel('FRM_USER', $this->siteLangId), 'selprod_user_shop_name', '', array(' ' => ' '))->requirements()->setRequired();
        }

        if (!FatApp::getConfig('CONF_WITHOUT_PROD_VARIANTS', FatUtility::VAR_INT, 0)) {
            $frm->addRequiredField(Labels::getLabel('FRM_TITLE', $this->siteLangId), 'selprod_title');
        }

        $isPickupEnabled = applicationConstants::NO;
        if ($productData['sellerProduct'] > 0) {
            $isPickupEnabled = Shop::getAttributesByUserId($productData['product_seller_id'], 'shop_fulfillment_type');
        } else {
            $isPickupEnabled = FatApp::getConfig('CONF_FULFILLMENT_TYPE', FatUtility::VAR_INT, -1);
        }

        $frm->addHiddenField('', 'selprod_user_id');
        $frm->addTextBox(Labels::getLabel('FRM_URL_KEYWORD', $this->siteLangId), 'selprod_url_keyword');

        $costPrice = $frm->addFloatField(Labels::getLabel('FRM_COST_PRICE', $this->siteLangId) . ' [' . CommonHelper::getCurrencySymbol(true) . ']', 'selprod_cost');
        $costPrice->requirements()->setPositive();

        $fld = $frm->addFloatField(Labels::getLabel('FRM_SELLING_PRICE', $this->siteLangId) . ' [' . CommonHelper::getCurrencySymbol(true) . ']', 'selprod_price');
        $fld->requirements()->setRange('0.01', '99999999.99');
        if (isset($productData['product_min_selling_price'])) {
            $fld->requirements()->setRange($productData['product_min_selling_price'], 99999999.99);
        }

        if ($productData['product_type'] != Product::PRODUCT_TYPE_SERVICE) {
            $fld = $frm->addIntegerField(Labels::getLabel('FRM_AVAILABLE_QUANTITY', $this->siteLangId), 'selprod_stock');
            $fld->requirements()->setPositive();

            $fld_sku = $frm->addTextBox(Labels::getLabel('FRM_PRODUCT_SKU', $this->siteLangId), 'selprod_sku');
            if (FatApp::getConfig("CONF_PRODUCT_SKU_MANDATORY", FatUtility::VAR_INT, 1)) {
                $fld_sku->requirements()->setRequired();
            }

            $fld = $frm->addIntegerField(Labels::getLabel('FRM_MINIMUM_PURCHASE_QUANTITY', $this->siteLangId), 'selprod_min_order_qty');
            $fld->requirements()->setPositive();
        } else {
            $frm->addHiddenField('', 'selprod_stock', 1);
            $frm->addHiddenField('', 'selprod_min_order_qty', 1);
        }

        if ($productData['product_type'] == Product::PRODUCT_TYPE_DIGITAL) {
            $fld = $frm->addIntegerField(Labels::getLabel('FRM_MAX_DOWNLOAD_TIMES', $this->siteLangId), 'selprod_max_download_times');
            $fld->htmlAfterField = '<span class="form-text text-muted">' . Labels::getLabel('FRM_-1_for_unlimited', $this->siteLangId) . '</span>';

            $fld1 = $frm->addIntegerField(Labels::getLabel('FRM_DOWNLOAD_VALIDITY_(days)', $this->siteLangId), 'selprod_download_validity_in_days');
            $fld1->htmlAfterField = '<span class="form-text text-muted">' . Labels::getLabel('FRM_-1_for_unlimited', $this->siteLangId) . '</span>';
            $frm->addHiddenField('', 'selprod_condition', $defaultProductCond);
        } else {
            $fld = $frm->addSelectBox(Labels::getLabel('FRM_PRODUCT_CONDITION', $this->siteLangId), 'selprod_condition', Product::getConditionArr($this->siteLangId), '', array(), Labels::getLabel('FRM_SELECT_CONDITION', $this->siteLangId));
            $fld->requirements()->setRequired();
        }

        if ($productData['product_type'] != Product::PRODUCT_TYPE_DIGITAL) {
            $codFld = $frm->addSelectBox(Labels::getLabel('FRM_AVAILABLE_FOR_COD', $this->siteLangId), 'selprod_cod_enabled', applicationConstants::getYesNoArr($this->siteLangId), '0', array(), '');
            $paymentMethod = new PaymentMethods();
            if (!$paymentMethod->cashOnDeliveryIsActive()) {
                $codFld->addFieldTagAttribute('disabled', 'disabled');
                $codFld->htmlAfterField = '<span class="form-text text-muted">' . Labels::getLabel('FRM_COD_OPTION_IS_DISABLED_IN_PAYMENT_GATEWAY_SETTINGS', $this->siteLangId) . '</span>';
            }

            $fulFillmentArr = Shipping::getFulFillmentArr($this->siteLangId, $isPickupEnabled);
            $fld = $frm->addSelectBox(Labels::getLabel('FRM_FULFILLMENT_METHOD', $this->siteLangId), 'selprod_fulfillment_type', $fulFillmentArr, applicationConstants::NO, array(), Labels::getLabel('FRM_SELECT', $this->siteLangId));
            $fld->requirement->setRequired(true);
        }

        $frm->addDateField(Labels::getLabel('FRM_DATE_AVAILABLE', $this->siteLangId), 'selprod_available_from', '', array('readonly' => 'readonly', 'class' => 'field--calender'))->requirements()->setRequired();
        
        if (0 < FatApp::getConfig('CONF_RFQ_MODULE', FatUtility::VAR_INT, 0) && 1 > FatApp::getConfig('CONF_HIDE_PRICES', FatUtility::VAR_INT, 0)) {
            $cartTypeFld = $frm->addSelectBox(Labels::getLabel('FRM_CART_TYPE', $this->siteLangId), 'selprod_cart_type', SellerProduct::getCartType(), SellerProduct::CART_TYPE_BOTH, array('class' => 'fieldsVisibilityJs onlyShowHideJs'), '');
            $cartTypeFld->requirements()->setRequired();
            $frm->addCheckBox(Labels::getLabel("FRM_HIDE_PRICE", $this->siteLangId), 'selprod_hide_price', 1, array(), false, 0);

            $hidePriceReqFld = new FormFieldRequirement('selprod_hide_price', Labels::getLabel('FRM_HIDE_PRICE', $this->siteLangId));
            $hidePriceReqFld->setRequired(true);
            $hidePriceReqFld->setPositive();

            $hidePriceUnReqFld = new FormFieldRequirement('selprod_hide_price', Labels::getLabel('FRM_HIDE_PRICE', $this->siteLangId));
            $hidePriceUnReqFld->setRequired(false);
            $hidePriceUnReqFld->setPositive();

            $cartTypeFld->requirements()->addOnChangerequirementUpdate(SellerProduct::CART_TYPE_RFQ_ONLY, 'eq', 'selprod_hide_price', $hidePriceReqFld);
            $cartTypeFld->requirements()->addOnChangerequirementUpdate(SellerProduct::CART_TYPE_RFQ_ONLY, 'ne', 'selprod_hide_price', $hidePriceUnReqFld);
        }

        if ($productData['product_type'] != Product::PRODUCT_TYPE_SERVICE) {
            $frm->addCheckBox(Labels::getLabel('FRM_SYSTEM_SHOULD_MAINTAIN_STOCK_LEVELS', $this->siteLangId), 'selprod_subtract_stock', applicationConstants::YES, array(), false, 0);
            $fld = $frm->addCheckBox(Labels::getLabel('FRM_SYSTEM_SHOULD_TRACK_PRODUCT_INVENTORY', $this->siteLangId), 'selprod_track_inventory', Product::INVENTORY_TRACK, ['class' => 'fieldsVisibilityJs'], false, 0);

            $stockLevelReqFld = new FormFieldRequirement('selprod_threshold_stock_level', Labels::getLabel('FRM_ALERT_STOCK_LEVEL', $this->siteLangId));
            $stockLevelReqFld->setRequired(true);

            $stockLevelUnReqFld = new FormFieldRequirement('selprod_threshold_stock_level', Labels::getLabel('FRM_ALERT_STOCK_LEVEL', $this->siteLangId));
            $stockLevelUnReqFld->setRequired(false);

            $fld->requirements()->addOnChangerequirementUpdate(1, 'eq', 'selprod_threshold_stock_level', $stockLevelReqFld);
            $fld->requirements()->addOnChangerequirementUpdate(1, 'ne', 'selprod_threshold_stock_level', $stockLevelUnReqFld);

            $fld = $frm->addTextBox(Labels::getLabel('FRM_ALERT_STOCK_LEVEL', $this->siteLangId), 'selprod_threshold_stock_level');
            $fld->requirements()->setInt();
        }

        $useShopPolicy = $frm->addCheckBox(Labels::getLabel('FRM_USE_SHOP_RETURN_AND_CANCELLATION_AGE_POLICY', $this->siteLangId), 'use_shop_policy', 1, ['id' => 'use_shop_policy'], false, 0);

        if ($productData['product_type'] != Product::PRODUCT_TYPE_SERVICE) {
            $fld = $frm->addIntegerField(Labels::getLabel('FRM_PRODUCT_ORDER_RETURN_PERIOD_(Days)', $this->siteLangId), 'selprod_return_age');
            $orderReturnAgeReqFld = new FormFieldRequirement('selprod_return_age', Labels::getLabel('FRM_PRODUCT_ORDER_RETURN_PERIOD_(Days)', $this->siteLangId));
            $orderReturnAgeReqFld->setRequired(true);
            $orderReturnAgeReqFld->setPositive();
            $orderReturnAgeReqFld->htmlAfterField = '<br/><small>' . Labels::getLabel('FRM_WARRANTY_IN_DAYS', $this->siteLangId) . ' </small>';

            $orderReturnAgeUnReqFld = new FormFieldRequirement('selprod_return_age', Labels::getLabel('FRM_PRODUCT_ORDER_RETURN_PERIOD_(Days)', $this->siteLangId));
            $orderReturnAgeUnReqFld->setRequired(false);
            $orderReturnAgeUnReqFld->setPositive();
        }

        $fld = $frm->addIntegerField(Labels::getLabel('FRM_PRODUCT_ORDER_CANCELLATION_PERIOD_(Days)', $this->siteLangId), 'selprod_cancellation_age');
        $fld->htmlAfterField = '<span class="form-text text-muted">' . Labels::getLabel('FRM_PERIOD_IN_DAYS', $this->siteLangId) . ' </span>';

        $orderCancellationAgeReqFld = new FormFieldRequirement('selprod_cancellation_age', Labels::getLabel('FRM_PRODUCT_ORDER_CANCELLATION_PERIOD_(Days)', $this->siteLangId));
        $orderCancellationAgeReqFld->setRequired(true);
        $orderCancellationAgeReqFld->setPositive();

        $orderCancellationAgeUnReqFld = new FormFieldRequirement('selprod_cancellation_age', Labels::getLabel('FRM_PRODUCT_ORDER_CANCELLATION_PERIOD_(Days)', $this->siteLangId));
        $orderCancellationAgeUnReqFld->setRequired(false);
        $orderCancellationAgeUnReqFld->setPositive();

        if ($productData['product_type'] != Product::PRODUCT_TYPE_SERVICE) {
            $useShopPolicy->requirements()->addOnChangerequirementUpdate(Shop::USE_SHOP_POLICY, 'eq', 'selprod_return_age', $orderReturnAgeUnReqFld);
            $useShopPolicy->requirements()->addOnChangerequirementUpdate(Shop::USE_SHOP_POLICY, 'ne', 'selprod_return_age', $orderReturnAgeReqFld);
        }

        $useShopPolicy->requirements()->addOnChangerequirementUpdate(Shop::USE_SHOP_POLICY, 'eq', 'selprod_cancellation_age', $orderCancellationAgeUnReqFld);
        $useShopPolicy->requirements()->addOnChangerequirementUpdate(Shop::USE_SHOP_POLICY, 'ne', 'selprod_cancellation_age', $orderCancellationAgeReqFld);


        $frm->addCheckBox(Labels::getLabel('FRM_PUBLISH_INVENTORY', $this->siteLangId), 'selprod_active', applicationConstants::ACTIVE, [], false, applicationConstants::INACTIVE);

        $frm->addTextArea(Labels::getLabel('FRM_ANY_EXTRA_COMMENT_FOR_BUYER', $this->siteLangId), 'selprod_comments');

        $languageArr = Language::getDropDownList();
        $translatorSubscriptionKey = FatApp::getConfig('CONF_TRANSLATOR_SUBSCRIPTION_KEY', FatUtility::VAR_STRING, '');
        if (!empty($translatorSubscriptionKey) && 1 < count($languageArr)) {
            $frm->addCheckBox(Labels::getLabel('FRM_UPDATE_OTHER_LANGUAGES_DATA', $this->siteLangId), 'auto_update_other_langs_data', 1, array(), false, 0);
        }

        $frm->addHiddenField('', 'selprod_product_id', $product_id);
        $frm->addHiddenField('', 'selprod_id');
        return $frm;
    }

    protected function renderJsonError($msg = '')
    {
        $this->set('msg', $msg);
        $this->_template->render(false, false, 'json-error.php', false, false);
    }

    protected function renderJsonSuccess($msg = '')
    {
        $this->set('msg', $msg);
        $this->_template->render(false, false, 'json-success.php', false, false);
    }

    public function translateLangFields($tbl, $data)
    {
        if (!empty($tbl) && !empty($data)) {
            $updateLangDataobj = new TranslateLangData($tbl);
            $translatedText = $updateLangDataobj->directTranslate($data);
            if (false === $translatedText) {
                LibHelper::exitWithError($updateLangDataobj->getError());
            }
            return $translatedText;
        }
        LibHelper::exitWithError($this->str_invalid_request);
    }

    public function imgCropper()
    {
        $this->set('title', FatApp::getPostedData('title', FatUtility::VAR_STRING, Labels::getLabel('LBL_UPLOAD_IMAGE', $this->siteLangId)));
        $this->set('html', $this->_template->render(false, false, 'cropper/index.php', true));
        $this->_template->render(false, false, 'json-success.php', true, false);
    }

    public function recordInfoSection()
    {
        $this->_template->render(false, false, '_partial/record-info-section.php');
    }

    public function includeFeatherLightJsCss()
    {
        $this->_template->addJs(['js/featherlight/featherlight.min.js', 'js/featherlight/featherlight.gallery.min.js', 'js/featherlight/jquery.detect_swipe.min.js']);
        $this->_template->addCss(['css/featherlight/featherlight.min.css', 'css/featherlight/featherlight.gallery.min.css']);
    }
}
