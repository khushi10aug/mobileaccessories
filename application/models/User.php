<?php

class User extends MyAppModel
{
    public const ADMIN_SESSION_ELEMENT_NAME = 'yokartAdmin';
    public const DB_TBL = 'tbl_users';
    public const DB_TBL_PREFIX = 'user_';

    public const DB_TBL_META = 'tbl_user_meta';
    public const DB_TBL_META_PREFIX = 'usermeta_';

    public const DB_TBL_CRED = 'tbl_user_credentials';
    public const DB_TBL_CRED_PREFIX = 'credential_';

    public const DB_TBL_USER_EMAIL_VER = 'tbl_user_email_verification';
    public const DB_TBL_UEMV_PREFIX = 'uev_';

    public const DB_TBL_USER_PHONE_VER = 'tbl_user_phone_verification';
    public const DB_TBL_UPV_PREFIX = 'upv_';

    public const DB_TBL_USR_SUPP_REQ = 'tbl_user_supplier_requests';
    public const DB_TBL_USR_SUPP_REQ_PREFIX = 'usuprequest_';

    public const DB_TBL_USR_BANK_INFO = 'tbl_user_bank_details';
    public const DB_TBL_USR_BANK_INFO_PREFIX = 'ub_';

    public const DB_TBL_USR_RETURN_ADDR = 'tbl_user_return_address';
    public const DB_TBL_USR_RETURN_ADDR_PREFIX = 'ura_';

    public const DB_TBL_USR_RETURN_ADDR_LANG = 'tbl_user_return_address_lang';
    public const DB_TBL_USR_RETURN_ADDR_LANG_PREFIX = 'uralang_';

    public const DB_TBL_USR_CATALOG_REQ = 'tbl_seller_catalog_requests';
    public const DB_TBL_USR_CATALOG_REQ_PREFIX = 'scatrequest_';

    public const DB_TBL_USR_CATALOG_REQ_MSG = 'tbl_catalog_request_messages';
    public const DB_TBL_USR_CATALOG_REQ_ERR_PREFIX = 'scatrequestERR_';

    public const DB_TBL_USR_WITHDRAWAL_REQ = 'tbl_user_withdrawal_requests';
    public const DB_TBL_USR_WITHDRAWAL_REQ_PREFIX = 'withdrawal_';

    public const DB_TBL_USR_WITHDRAWAL_REQ_SPEC = 'tbl_user_withdrawal_requests_specifics';
    public const DB_TBL_USR_WITHDRAWAL_REQ_SPEC_PREFIX = 'uwrs_';

    public const DB_TBL_USR_EXTRAS = 'tbl_user_extras';
    public const DB_TBL_USR_EXTRAS_PREFIX = 'uextra_';

    public const DB_TBL_USR_MOBILE_TEMP_TOKEN = 'tbl_user_temp_token_requests';
    public const DB_TBL_USR_MOBILE_TEMP_TOKEN_PREFIX = 'uttr_';

    public const DB_TBL_USR_COOKIES_PREFERENCES = 'tbl_user_cookies_preferences';
    public const DB_TBL_USR_COOKIES_PREFERENCES_PREFIX = 'ucp_';

    public const USER_FIELD_TYPE_TEXT = 1;
    public const USER_FIELD_TYPE_TEXTAREA = 2;
    public const USER_FIELD_TYPE_FILE = 3;
    public const USER_FIELD_TYPE_DATE = 4;
    public const USER_FIELD_TYPE_DATETIME = 5;
    public const USER_FIELD_TYPE_TIME = 6;
    public const USER_FIELD_TYPE_PHONE = 7;

    public const SUPPLIER_REQUEST_PENDING = 0;
    public const SUPPLIER_REQUEST_APPROVED = 1;
    public const SUPPLIER_REQUEST_CANCELLED = 2;

    public const USER_BUYER_DASHBOARD = 1;
    public const USER_SELLER_DASHBOARD = 2;
    public const USER_AFFILIATE_DASHBOARD = 3;
    public const USER_ADVERTISER_DASHBOARD = 4;

    public const USER_TYPE_BUYER = 1;
    public const USER_TYPE_SELLER = 2;
    public const USER_TYPE_AFFILIATE = 3;
    public const USER_TYPE_ADVERTISER = 4;
    public const USER_TYPE_SHIPPING_COMPANY = 5;
    public const USER_TYPE_BUYER_SELLER = 6;
    public const USER_TYPE_SUB_USER = 7;

    public const CATALOG_REQUEST_PENDING = 0;
    public const CATALOG_REQUEST_APPROVED = 1;
    public const CATALOG_REQUEST_CANCELLED = 2;

    public const AFFILIATE_PAYMENT_METHOD_CHEQUE = 1;
    public const AFFILIATE_PAYMENT_METHOD_BANK = 2;
    public const AFFILIATE_PAYMENT_METHOD_PAYPAL = 3;

    public const RETURN_ADDRESS_ACCOUNT_TAB = 'return-address';
    public const RETURN_ADDRESS_TAB_1 = 1;

    public const CLASS_PENDING = 'warning';
    public const CLASS_APPROVED = 'success';
    public const CLASS_CANCELLED = 'danger';

    public const FB_LOGIN = 1;
    public const GOOGLE_LOGIN = 2;
    public const APPLE_LOGIN = 3;


    // OTP Validate types
    public const OTP_FOR_OLD_PHONE_NO = 1;
    public const OTP_FOR_NEW_PHONE_NO = 2;
    public const OTP_FOR_EMAIL = 3;


    public const USER_INFO_ATTR = [
        'user_id',
        'user_name',
        'user_phone_dcode',
        'user_phone',
        'credential_email',
        'user_registered_initially_for',
        'user_preferred_dashboard',
        'user_deleted',
        'user_is_buyer',
        'user_is_supplier',
        'user_is_advertiser',
        'user_is_affiliate',
        'credential_active as user_active',
        'credential_username',
        'credential_password',
        'credential_verified',
    ];

    public const DEVICE_OS_BOTH = 0;
    public const DEVICE_OS_ANDROID = 1;
    public const DEVICE_OS_IOS = 2;

    public const OTP_LENGTH = 4;
    public const OTP_AGE = 15; //IN MINUTES
    public const OTP_INTERVAL = 30; //IN SECONDS

    public const AUTH_TYPE_GUEST = 1;
    public const AUTH_TYPE_REGISTERED = 2;

    public $parentId = 0;
    private $loginWithOtp = false;
    private $loginWithSocialAccount = false;

    public function __construct($userId = 0, $parentId = 0)
    {
        $this->parentId = FatUtility::int($parentId);

        parent::__construct(static::DB_TBL, static::DB_TBL_PREFIX . 'id', $userId);
        $this->objMainTableRecord->setSensitiveFields(
            array(
                'user_regdate',
                'user_id'
            )
        );

        /* if (0 < $this->parentId) {
            $this->addCondition('user_parent', '=', $this->parentId);
        } */
    }

    public static function getUserTypesArr($langId)
    {
        $langId = FatUtility::int($langId);
        if ($langId < 1) {
            $langId = FatApp::getConfig('CONF_ADMIN_DEFAULT_LANG');
        }
        return array(
            static::USER_TYPE_BUYER => Labels::getLabel('LBL_Buyer', $langId),
            static::USER_TYPE_SELLER => Labels::getLabel('LBL_Seller', $langId),
            static::USER_TYPE_ADVERTISER => Labels::getLabel('LBL_Advertiser', $langId),
            static::USER_TYPE_AFFILIATE => Labels::getLabel('LBL_Affiliate', $langId)
        );
    }

    public static function getUserTypeHtml(int $langId, int $status): string
    {
        $arr = static::getUserTypesArr($langId);
        $msg = $arr[$status] ?? Labels::getLabel('LBL_N/A', $langId);
        switch ($status) {
            case static::USER_TYPE_BUYER:
                $status = HtmlHelper::SUCCESS;
                break;
            case static::USER_TYPE_SELLER:
                $status = HtmlHelper::INFO;
                break;
            case static::USER_TYPE_ADVERTISER:
                $status = HtmlHelper::WARNING;
                break;
            case static::USER_TYPE_AFFILIATE:
                $status = HtmlHelper::DANGER;
                break;
            default:
                $status = HtmlHelper::INFO;
                break;
        }
        return HtmlHelper::getStatusHtml($status, $msg);
    }

    public static function getDeviceTypeArr($langId)
    {
        return [
            self::DEVICE_OS_BOTH => Labels::getLabel('LBL_BOTH_OS', $langId),
            self::DEVICE_OS_ANDROID => Labels::getLabel('LBL_ANDROID', $langId),
            self::DEVICE_OS_IOS => Labels::getLabel('LBL_IOS', $langId),
        ];
    }

    public static function getUserAuthTypeArr($langId)
    {
        return [
            self::AUTH_TYPE_GUEST => Labels::getLabel('LBL_GUEST', $langId),
            self::AUTH_TYPE_REGISTERED => Labels::getLabel('LBL_REGISTERED', $langId),
        ];
    }

    public static function getAffiliatePaymentMethodArr($langId)
    {
        $langId = FatUtility::int($langId);
        if ($langId < 1) {
            $langId = FatApp::getConfig('CONF_ADMIN_DEFAULT_LANG');
        }

        return array(
            static::AFFILIATE_PAYMENT_METHOD_CHEQUE => Labels::getLabel('LBL_Cheque', $langId),
            static::AFFILIATE_PAYMENT_METHOD_BANK => Labels::getLabel('LBL_Bank', $langId),
            static::AFFILIATE_PAYMENT_METHOD_PAYPAL => Labels::getLabel('LBL_PayPal', $langId),
        );
    }

    public static function getSearchObject($joinUserCredentials = false, $parentId = 0, $skipDeleted = true)
    {
        $parentId = FatUtility::int($parentId);

        $srch = new SearchBase(static::DB_TBL, 'u');
        if ($skipDeleted == true) {
            $srch->addCondition('user_deleted', '=', 'mysql_func_' . applicationConstants::NO, 'AND', true);
        }

        if (0 < $parentId) {
            $srch->addCondition('user_parent', '=', 'mysql_func_' . $parentId, 'AND', true);
        }

        if ($joinUserCredentials) {
            $srch->joinTable(static::DB_TBL_CRED, 'LEFT OUTER JOIN', 'uc.' . static::DB_TBL_CRED_PREFIX . 'user_id = u.user_id', 'uc');
        }
        return $srch;
    }

    public function getMainTableRecordId()
    {
        return $this->mainTableRecordId;
    }

    public static function isSeller($returnNullIfNotLogged = false)
    {
        return (1 == UserAuthentication::getLoggedUserAttribute('user_is_supplier', $returnNullIfNotLogged));
    }

    public static function isBuyer($returnNullIfNotLogged = false)
    {
        return (1 == UserAuthentication::getLoggedUserAttribute('user_is_buyer', $returnNullIfNotLogged));
    }

    public static function isAdvertiser($returnNullIfNotLogged = false)
    {
        return (1 == UserAuthentication::getLoggedUserAttribute('user_is_advertiser', $returnNullIfNotLogged));
    }

    public static function isAffiliate($returnNullIfNotLogged = false)
    {
        return (1 == UserAuthentication::getLoggedUserAttribute('user_is_affiliate', $returnNullIfNotLogged));
    }

    public static function isSigningUpForSeller()
    {
        return (static::USER_TYPE_SELLER == UserAuthentication::getLoggedUserAttribute('user_registered_initially_for'));
    }

    public static function isSigningUpBuyer()
    {
        return (static::USER_TYPE_BUYER == UserAuthentication::getLoggedUserAttribute('user_registered_initially_for'));
    }

    public static function isSigningUpAdvertiser()
    {
        return (static::USER_TYPE_ADVERTISER == UserAuthentication::getLoggedUserAttribute('user_registered_initially_for'));
    }

    public static function isSigningUpAffiliate()
    {
        return (static::USER_TYPE_AFFILIATE == UserAuthentication::getLoggedUserAttribute('user_registered_initially_for'));
    }

    public static function getUserMeta($userId, $key = '')
    {
        $userId = FatUtility::int($userId);
        if (1 > $userId) {
            return false;
        }

        $srch = new SearchBase(static::DB_TBL_META, 't_um');
        $srch->addMultipleFields(['usermeta_key', 'usermeta_value']);
        $srch->addCondition('t_um.' . static::DB_TBL_META_PREFIX . 'user_id', '=', 'mysql_func_' . $userId, 'AND', true);
        if (!empty($key)) {
            $srch->addCondition('t_um.' . static::DB_TBL_META_PREFIX . 'key', '=', $key);
        }
        $srch->doNotCalculateRecords();
        $rs = $srch->getResultSet();
        $result = FatApp::getDb()->fetchAll($rs);

        $userMetaData = [];
        foreach ($result as $val) {
            $userMetaData[$val[static::DB_TBL_META_PREFIX . "key"]] = $val[static::DB_TBL_META_PREFIX . "value"];
        }
        if (!empty($key)) {
            return isset($userMetaData[$key]) ? $userMetaData[$key] : '';
        }
        return $userMetaData;
    }

    public function updateUserMeta($key, $value)
    {
        if (1 > $this->mainTableRecordId) {
            $this->error = Labels::getLabel('ERR_INVALID_REQUEST_USER_NOT_INITIALIZED', $this->commonLangId);
            return false;
        }

        if (empty($key) || '' == $value) {
            $this->error = Labels::getLabel('ERR_INVALID_REQUEST_PARAMETERS', $this->commonLangId);
            return false;
        }

        $updateData = [
            static::DB_TBL_META_PREFIX . 'user_id' => $this->mainTableRecordId,
            static::DB_TBL_META_PREFIX . 'key' => $key,
            static::DB_TBL_META_PREFIX . 'value' => is_array($value) ? serialize($value) : $value,
        ];

        $db = FatApp::getDb();
        if (!$db->insertFromArray(static::DB_TBL_META, $updateData, false, array(), $updateData)) {
            $this->error = $db->getError();
            return false;
        }
        return $this->mainTableRecordId;
    }

    public static function canAccessSupplierDashboard()
    {
        if (static::isSeller()) {
            return true;
        }
        return false;
    }

    public static function isRequestedForSeller($userId)
    {
        $userId = FatUtility::int($userId);
        $userObj = new User($userId);
        $srch = $userObj->getUserSupplierRequestsObj();
        $srch->addFld(array('usuprequest_attempts', 'usuprequest_id'));
        $srch->doNotCalculateRecords();
        $rs = $srch->getResultSet();
        $supplierRequest = FatApp::getDb()->fetch($rs);
        if ($supplierRequest) {
            return true;
        }
        return false;
    }

    public static function canViewSupplierTab()
    {
        if (self::isSeller()) {
            return true;
        }

        if (self::isAdvertiser()) {
            return false;
        }

        if (self::isAffiliate()) {
            return false;
        }

        if (!FatApp::getConfig('CONF_ACTIVATE_SEPARATE_SIGNUP_FORM', FatUtility::VAR_INT, 1)) {
            return true;
        }

        if (FatApp::getConfig('CONF_ACTIVATE_SEPARATE_SIGNUP_FORM', FatUtility::VAR_INT, 1)) {
            if (FatApp::getConfig('CONF_BUYER_CAN_SEE_SELLER_TAB', FatUtility::VAR_INT, 0) && self::isBuyer()) {
                return true;
            }
        }
        if (FatApp::getConfig('CONF_ACTIVATE_SEPARATE_SIGNUP_FORM', FatUtility::VAR_INT, 1)) {
            if (!self::isBuyer()) {
                return true;
            }
        }

        if (static::isRequestedForSeller(UserAuthentication::getLoggedUserId())) {
            return true;
        }
        return false;
    }

    public static function canViewBuyerTab()
    {
        if (self::isBuyer()) {
            return true;
        }

        if (!FatApp::getConfig('CONF_ACTIVATE_SEPARATE_SIGNUP_FORM', FatUtility::VAR_INT, 1) && self::isBuyer()) {
            return true;
        }

        return false;
    }

    public static function canViewAdvertiserTab()
    {
        if (self::isAdvertiser()) {
            return true;
        }
        return false;
    }

    public static function canViewAffiliateTab()
    {
        if (self::isAffiliate()) {
            return true;
        }

        return false;
    }

    public static function canAddCustomProduct()
    {
        return (1 == FatApp::getConfig('CONF_ENABLED_SELLER_CUSTOM_PRODUCT', FatUtility::VAR_INT, 0));
    }

    public static function canRequestProduct()
    {
        /* return (1 == FatApp::getConfig('CONF_SELLER_CAN_REQUEST_PRODUCT', FatUtility::VAR_INT, 0)); */
        return false;
    }

    public static function canAddCustomProductAvailableToAllSellers()
    {
        return (1 == FatApp::getConfig('CONF_SELLER_CAN_REQUEST_CUSTOM_PRODUCT', FatUtility::VAR_INT, 0));
    }

    public static function getFieldTypes($langId)
    {
        $langId = FatUtility::int($langId);
        if ($langId == 0) {
            trigger_error(Labels::getLabel('ERR_Language_Id_not_specified.', $langId), E_USER_ERROR);
        }
        $arr = array(
            static::USER_FIELD_TYPE_TEXT => Labels::getLabel('LBL_Textbox', $langId),
            static::USER_FIELD_TYPE_TEXTAREA => Labels::getLabel('LBL_Textarea', $langId),
            static::USER_FIELD_TYPE_FILE => Labels::getLabel('LBL_File', $langId),
            static::USER_FIELD_TYPE_DATE => Labels::getLabel('LBL_Date', $langId),
            static::USER_FIELD_TYPE_DATETIME => Labels::getLabel('LBL_Datetime', $langId),
            static::USER_FIELD_TYPE_TIME => Labels::getLabel('LBL_Time', $langId),
            static::USER_FIELD_TYPE_PHONE => Labels::getLabel('LBL_Phone', $langId),
        );
        return $arr;
    }

    public static function getUserDashboard($langId)
    {
        $langId = FatUtility::int($langId);
        if ($langId == 0) {
            trigger_error(Labels::getLabel('ERR_Language_Id_not_specified.', $langId), E_USER_ERROR);
        }
        $arr = array(
            static::USER_BUYER_DASHBOARD => Labels::getLabel('LBL_Buyer', $langId),
            static::USER_SELLER_DASHBOARD => Labels::getLabel('LBL_Seller', $langId),
            static::USER_ADVERTISER_DASHBOARD => Labels::getLabel('LBL_Advertiser', $langId),
            static::USER_AFFILIATE_DASHBOARD => Labels::getLabel('LBL_Affiliate', $langId),
        );
        return $arr;
    }

    public static function getPreferedDashbordRedirectUrl($preferredDashboard)
    {
        switch ($preferredDashboard) {
            case User::USER_BUYER_DASHBOARD:
                return UrlHelper::generateFullUrl('buyer', '', [], CONF_WEBROOT_DASHBOARD, null, false, false, false);
                break;
            case User::USER_SELLER_DASHBOARD:
                return UrlHelper::generateFullUrl('seller', '', [], CONF_WEBROOT_DASHBOARD, null, false, false, false);
                break;
            case User::USER_ADVERTISER_DASHBOARD:
                return UrlHelper::generateFullUrl('advertiser', '', [], CONF_WEBROOT_DASHBOARD, null, false, false, false);
                break;
            case User::USER_AFFILIATE_DASHBOARD:
                return UrlHelper::generateFullUrl('affiliate', '', [], CONF_WEBROOT_DASHBOARD, null, false, false, false);
                break;
        }
        return UrlHelper::generateFullUrl('account', '', [], CONF_WEBROOT_DASHBOARD, null, false, false, false);
    }

    public static function getSupplierReqStatusArr($langId)
    {
        $langId = FatUtility::int($langId);
        if ($langId == 0) {
            trigger_error(Labels::getLabel('ERR_Language_Id_not_specified.', $langId), E_USER_ERROR);
        }
        $arr = array(
            static::SUPPLIER_REQUEST_PENDING => Labels::getLabel('LBL_Pending', $langId),
            static::SUPPLIER_REQUEST_APPROVED => Labels::getLabel('LBL_Approved', $langId),
            static::SUPPLIER_REQUEST_CANCELLED => Labels::getLabel('LBL_Cancelled', $langId)
        );
        return $arr;
    }

    public static function getCatalogRequestClassArr()
    {
        return array(
            static::CATALOG_REQUEST_PENDING => static::CLASS_PENDING,
            static::CATALOG_REQUEST_APPROVED => static::CLASS_APPROVED,
            static::CATALOG_REQUEST_CANCELLED => static::CLASS_CANCELLED,
        );
    }

    public static function getCatalogReqStatusArr($langId)
    {
        $langId = FatUtility::int($langId);
        if ($langId == 0) {
            trigger_error(Labels::getLabel('ERR_Language_Id_not_specified.', $langId), E_USER_ERROR);
        }
        $arr = array(
            static::CATALOG_REQUEST_PENDING => Labels::getLabel('LBL_Pending', $langId),
            static::CATALOG_REQUEST_APPROVED => Labels::getLabel('LBL_Approved', $langId),
            static::CATALOG_REQUEST_CANCELLED => Labels::getLabel('LBL_Cancelled', $langId)
        );
        return $arr;
    }

    public function getUserSearchObj($attr = null, $joinUserCredentials = true, $skipDeleted = true)
    {
        $srch = static::getSearchObject($joinUserCredentials, 0, $skipDeleted);

        if ($this->mainTableRecordId > 0) {
            $srch->addCondition('u.' . static::DB_TBL_PREFIX . 'id', '=', 'mysql_func_' . $this->mainTableRecordId, 'AND', true);
        }

        if (null != $attr) {
            if (is_array($attr)) {
                $srch->addMultipleFields($attr);
            } elseif (is_string($attr)) {
                $srch->addFld($attr);
            }
        } else {
            $srch->addMultipleFields(
                array(
                    'u.' . static::DB_TBL_PREFIX . 'id',
                    'u.' . static::DB_TBL_PREFIX . 'name',
                    'u.' . static::DB_TBL_PREFIX . 'phone_dcode',
                    'u.' . static::DB_TBL_PREFIX . 'phone',
                    'u.' . static::DB_TBL_PREFIX . 'profile_info',
                    'u.' . static::DB_TBL_PREFIX . 'regdate',
                    'u.' . static::DB_TBL_PREFIX . 'preferred_dashboard',
                    'u.' . static::DB_TBL_PREFIX . 'registered_initially_for',
                    'u.' . static::DB_TBL_PREFIX . 'parent',
                    'uc.' . static::DB_TBL_CRED_PREFIX . 'username',
                    'uc.' . static::DB_TBL_CRED_PREFIX . 'email',
                    'uc.' . static::DB_TBL_CRED_PREFIX . 'active',
                    'uc.' . static::DB_TBL_CRED_PREFIX . 'verified'
                )
            );
        }
        return $srch;
    }

    public function getUserInfo($attr = null, $isActive = true, $isVerified = true, $joinUserCredentials = false)
    {
        if (($this->mainTableRecordId < 1)) {
            $this->error = Labels::getLabel('ERR_INVALID_REQUEST_USER_NOT_INITIALIZED', $this->commonLangId);
            return false;
        }
        $srch = $this->getUserSearchObj($attr);
        if ($isActive) {
            $srch->addCondition('uc.' . static::DB_TBL_CRED_PREFIX . 'active', '=', 'mysql_func_' . applicationConstants::ACTIVE, 'AND', true);
        }

        if ($isVerified) {
            $srch->addCondition('uc.' . static::DB_TBL_CRED_PREFIX . 'verified', '=', 'mysql_func_' . applicationConstants::YES, 'AND', true);
        }

        if ($joinUserCredentials) {
            $srch->joinTable(static::DB_TBL_CRED, 'LEFT OUTER JOIN', 'uc.' . static::DB_TBL_CRED_PREFIX . 'user_id = u.user_id', 'uc');
        }
        $srch->doNotCalculateRecords();
        return FatApp::getDb()->fetch($srch->getResultSet());
    }

    public function getUserSupplierRequestsObj($requestId = 0, $applyActive = true)
    {
        $requestId = FatUtility::int($requestId);

        $srch = new SearchBase(static::DB_TBL_USR_SUPP_REQ, 'tusr');
        $srch->joinTable(
            static::DB_TBL,
            'INNER JOIN',
            'tusr.' . static::DB_TBL_USR_SUPP_REQ_PREFIX . 'user_id = u.' . static::DB_TBL_PREFIX . 'id',
            'u'
        );
        $srch->joinTable(static::DB_TBL_CRED, 'LEFT OUTER JOIN', 'uc.' . static::DB_TBL_CRED_PREFIX . 'user_id = u.user_id', 'uc');
        if ($this->mainTableRecordId > 0) {
            $srch->addCondition('u.' . static::DB_TBL_PREFIX . 'id', '=', 'mysql_func_' . $this->mainTableRecordId, 'AND', true);
        }
        if ($requestId > 0) {
            $srch->addCondition('tusr.' . static::DB_TBL_USR_SUPP_REQ_PREFIX . 'id', '=', 'mysql_func_' . $requestId, 'AND', true);
        }
        if ($applyActive) {
            $srch->addCondition('uc.' . static::DB_TBL_CRED_PREFIX . 'active', '=', 'mysql_func_' . applicationConstants::ACTIVE, 'AND', true);
        }

        $srch->addMultipleFields(
            array(
                'u.' . static::DB_TBL_PREFIX . 'id',
                'u.' . static::DB_TBL_PREFIX . 'name',
                'u.' . static::DB_TBL_PREFIX . 'phone_dcode',
                'u.' . static::DB_TBL_PREFIX . 'phone',
                'u.' . static::DB_TBL_PREFIX . 'updated_on',
                'uc.' . static::DB_TBL_CRED_PREFIX . 'username',
                'uc.' . static::DB_TBL_CRED_PREFIX . 'email',
            )
        );
        return $srch;
    }

    public function getSupplierFormFields($langId)
    {
        $langId = FatUtility::int($langId);
        if ($langId <= 0) {
            trigger_error("Lang id not passed", E_USER_ERROR);
        }
        $srch = SupplierFormFields::getSearchObject();
        $srch->addMultipleFields(['*', 'IFNULL(sformfield_caption,sformfield_identifier) as sformfield_caption']);


        $srch->joinTable(
            SupplierFormFields::DB_TBL . '_lang',
            'LEFT OUTER JOIN',
            'sf_l.sformfieldlang_sformfield_id = sf.sformfield_id
		AND sf_l.sformfieldlang_lang_id = ' . $langId,
            'sf_l'
        );

        $srch->addOrder('sformfield_display_order');

        $srch->doNotCalculateRecords();
        $srch->doNotLimitRecords();

        $rs = $srch->getResultSet();

        $records = FatApp::getDb()->fetchAll($rs, 'sformfield_id');
        if (!empty($records)) {
            return $records;
        }

        return array();
    }

    public function getUserBankInfo()
    {
        if (($this->mainTableRecordId < 1)) {
            $this->error = Labels::getLabel('ERR_INVALID_REQUEST_USER_NOT_INITIALIZED', $this->commonLangId);
            return false;
        }

        $srch = new SearchBase(static::DB_TBL_USR_BANK_INFO, 'tub');
        $srch->addCondition(static::DB_TBL_USR_BANK_INFO_PREFIX . 'user_id', '=', 'mysql_func_' . $this->mainTableRecordId, 'AND', true);
        $srch->doNotCalculateRecords();
        $srch->setPageSize(1);
        $rs = $srch->getResultSet();
        return FatApp::getDb()->fetch($rs);
    }

    public function updateBankInfo($data = array())
    {
        if (($this->mainTableRecordId < 1)) {
            $this->error = Labels::getLabel('ERR_INVALID_REQUEST_USER_NOT_INITIALIZED', $this->commonLangId);
            return false;
        }
        $assignValues = array(
            'ub_user_id' => $this->mainTableRecordId,
            'ub_bank_name' => $data['ub_bank_name'],
            'ub_account_holder_name' => $data['ub_account_holder_name'],
            'ub_account_number' => $data['ub_account_number'],
            'ub_ifsc_swift_code' => $data['ub_ifsc_swift_code'],
            'ub_bank_address' => $data['ub_bank_address']
        );
        if (!FatApp::getDb()->insertFromArray(static::DB_TBL_USR_BANK_INFO, $assignValues, false, array(), $assignValues)) {
            $this->error = $this->db->getError();
            return false;
        }
        return true;
    }

    public function deleteBankInfo()
    {
        if (($this->mainTableRecordId < 1)) {
            $this->error = Labels::getLabel('ERR_INVALID_REQUEST_USER_NOT_INITIALIZED', $this->commonLangId);
            return false;
        }

        if (!FatApp::getDb()->deleteRecords(static::DB_TBL_USR_BANK_INFO, array('smt' => 'ub_user_id = ?', 'vals' => array($this->mainTableRecordId)))) {
            $this->error = FatApp::getDb()->getError();
            return false;
        }
        return true;
    }

    public function updateSettingsInfo($data = array())
    {
        if (($this->mainTableRecordId < 1)) {
            $this->error = Labels::getLabel('ERR_INVALID_REQUEST_USER_NOT_INITIALIZED', $this->commonLangId);
            return false;
        }
        $assignValues = array(
            'user_id' => $this->mainTableRecordId,
            'user_autorenew_subscription' => $data['user_autorenew_subscription'],

        );
        if (!FatApp::getDb()->insertFromArray(static::DB_TBL, $assignValues, false, array(), $assignValues)) {
            $this->error = FatApp::getDb()->getError();
            return false;
        }
        return true;
    }
    public function updateInfo($userId, $data = array())
    {
        $assignValues = array(
            'user_company' => $data['user_company'],
            'user_profile_info' => $data['user_profile_info'],
            'user_products_services' => $data['user_products_services'],
        );
        if (!FatApp::getDb()->updateFromArray(static::DB_TBL, $assignValues, array('smt' => static::DB_TBL_PREFIX . 'id = ? ', 'vals' => array((int) $userId)))) {
            $this->error = FatApp::getDb()->getError();
            echo $this->error;
            die;
        }
        return true;
    }

    public function truncateUserInfo()
    {
        if (($this->mainTableRecordId < 1)) {
            $this->error = Labels::getLabel('ERR_INVALID_REQUEST_USER_NOT_INITIALIZED', $this->commonLangId);
            return false;
        }

        $db = FatApp::getDb();
        $db->startTransaction();

        /* Delete User Addresses [ */
        $address = new Address();
        if (!$address->deleteByRecordId(Address::TYPE_USER, $this->mainTableRecordId)) {
            $db->rollbackTransaction();
            $this->error = $address->getError();
            return false;
        }
        /* ] */

        /* Update User information [ */
        $data = array(
            'user_name' => '',
            'user_phone_dcode' => '',
            'user_phone' => NULL,
            'user_dob' => '',
            'user_city' => '',
            'user_country_id' => '',
            'user_state_id' => '',
            'user_company' => '',
            'user_profile_info' => '',
            'user_address1' => '',
            'user_address2' => '',
            'user_zip' => '',
            'user_products_services' => '',
        );

        if (!$db->updateFromArray(static::DB_TBL, $data, array('smt' => static::DB_TBL_PREFIX . 'id = ? ', 'vals' => array($this->mainTableRecordId)))) {
            $this->error = $db->getError();
            return false;
        }

        $userData = $this->getProfileData();
        $newEmail = '';
        if (!empty($userData['user_email'])) {
            $newEmail = 'xxxxxxx-' . $this->mainTableRecordId . substr($userData['user_email'], strpos($userData['user_email'], "@"));
        }

        $data = array(
            'credential_username' => 'xxxxxxx' . "-" . $this->mainTableRecordId,
            'credential_email' => $newEmail,
        );

        if (!$db->updateFromArray(static::DB_TBL_CRED, $data, array('smt' => 'credential_user_id = ? ', 'vals' => array($this->mainTableRecordId)))) {
            $this->error = $db->getError();
            return false;
        }

        if ($shopId = Shop::getAttributesByUserId($this->mainTableRecordId, 'shop_id')) {
            $data = array(
                'shop_identifier' => 'xxxxxx-' . $shopId,
                'shop_phone' => '',
                'shop_lng' => '',
                'shop_lng' => '',
            );

            if (!$db->updateFromArray(Shop::DB_TBL, $data, array('smt' => 'shop_id = ? ', 'vals' => array($shopId)))) {
                $this->error = $db->getError();
                return false;
            }

            $data = array(
                'shop_name' => 'xxxxx',
                'shop_contact_person' => 'xxxxx',
                'shop_description' => '',
                'shop_address_line_1' => 'xxxxxx',
                'shop_address_line_2' => 'xxxxxx',
                'shop_city' => 'xxxxx',
                'shop_payment_policy' => '',
                'shop_delivery_policy' => '',
                'shop_refund_policy' => '',
                'shop_additional_info' => '',
                'shop_seller_info' => '',
            );

            if (!$db->updateFromArray(Shop::DB_TBL_LANG, $data, array('smt' => 'shoplang_shop_id = ? ', 'vals' => array($shopId)))) {
                $this->error = $db->getError();
                return false;
            }
        }
        /* ] */

        /* Delete User's Profile Image [ */
        $fileHandlerObj = new AttachedFile();
        if (!$fileHandlerObj->deleteFile(AttachedFile::FILETYPE_USER_PROFILE_IMAGE, $this->mainTableRecordId)) {
            FatUtility::dieJsonError($fileHandlerObj->getError());
        }

        if (!$fileHandlerObj->deleteFile(AttachedFile::FILETYPE_USER_PROFILE_CROPED_IMAGE, $this->mainTableRecordId)) {
            FatUtility::dieJsonError($fileHandlerObj->getError());
        }
        /* ] */

        /* Delete Bank Info [ */
        if (!$this->deleteBankInfo()) {
            $db->rollbackTransaction();
            $this->error = $db->getError();
            return false;
        }
        /* ] */

        /* Delete Seller's Return Address [ */
        $srch = $this->getUserSearchObj(array('user_is_supplier', 'user_registered_initially_for'));
        $srch->doNotCalculateRecords();
        $rs = $srch->getResultSet();

        $userData = $db->fetch($rs, 'user_id');

        if ($userData['user_is_supplier'] || $userData['user_registered_initially_for']) {
            if (!$this->deleteUserReturnAddress()) {
                $db->rollbackTransaction();
                $this->error = $db->getError();
                return false;
            }
        }
        /* ] */

        /* Update Order User Address [ */
        $order = new Orders();
        if (!$order->updateOrderUserAddress($this->mainTableRecordId)) {
            $db->rollbackTransaction();
            $this->error = $order->getError();
            return false;
        }
        /* ] */

        /* Deactivate Account [ */
        $this->assignValues(array('user_deleted' => applicationConstants::YES));
        if (!$this->save()) {
            $db->rollbackTransaction();
            $this->error = $db->getError();
            return false;
        }
        /* ] */

        $db->commitTransaction();
        return true;
    }

    public function updateCredInfo($userId, $data = array())
    {
        $assignValues = array(
            static::DB_TBL_CRED_PREFIX . 'password' => UserAuthentication::encryptPassword($data['user_password'])
        );
        if (!FatApp::getDb()->updateFromArray(static::DB_TBL_CRED, $assignValues, array('smt' => static::DB_TBL_CRED_PREFIX . 'user_id = ? ', 'vals' => array((int) $userId)))) {
            $this->error = FatApp::getDb()->getError();
            echo $this->error;
            die;
        }
        return true;
    }

    public function getUserReturnAddress($langId = 0)
    {
        $langId = FatUtility::int($langId);
        if (($this->mainTableRecordId < 1)) {
            $this->error = Labels::getLabel('ERR_INVALID_REQUEST_USER_NOT_INITIALIZED', $this->commonLangId);
            return false;
        }

        $srch = new SearchBase(static::DB_TBL_USR_RETURN_ADDR, 'tura');
        $srch->joinTable(Countries::DB_TBL, 'LEFT OUTER JOIN', 'c.country_id = tura.ura_country_id', 'c');
        $srch->joinTable(States::DB_TBL, 'LEFT OUTER JOIN', 's.state_id = tura.ura_state_id', 's');

        $srch->addCondition(static::DB_TBL_USR_RETURN_ADDR_PREFIX . 'user_id', '=', 'mysql_func_' . $this->mainTableRecordId, 'AND', true);
        if ($langId > 0) {
            $srch->joinTable(static::DB_TBL_USR_RETURN_ADDR_LANG, 'LEFT OUTER JOIN', 'tura_l.uralang_user_id = tura.ura_user_id and tura_l.uralang_lang_id = ' . $langId, 'tura_l');
            $srch->joinTable(Countries::DB_TBL_LANG, 'LEFT OUTER JOIN', 'c_l.countrylang_country_id = tura.ura_country_id and c_l.countrylang_lang_id = ' . $langId, 'c_l');
            $srch->joinTable(States::DB_TBL_LANG, 'LEFT OUTER JOIN', 's_l.statelang_state_id = tura.ura_state_id and s_l.statelang_lang_id = ' . $langId, 's_l');
            $srch->addMultipleFields(array('tura_l.*', 'IFNULL(country_name,country_code) as country_name', 'country_code', 'IFNULL(state_name,state_identifier) as state_name', 'state_code'));
        }

        $srch->addMultipleFields(array('tura.*'));
        $srch->doNotCalculateRecords();
        $srch->setPageSize(1);
        $rs = $srch->getResultSet();
        return FatApp::getDb()->fetch($rs);
    }

    public function updateUserReturnAddress($data = array())
    {
        if (($this->mainTableRecordId < 1)) {
            $this->error = Labels::getLabel('ERR_INVALID_REQUEST_USER_NOT_INITIALIZED', $this->commonLangId);
            return false;
        }
        $assignValues = array(
            'ura_user_id' => $this->mainTableRecordId,
            'ura_state_id' => $data['ura_state_id'],
            'ura_country_id' => $data['ura_country_id'],
            'ura_zip' => $data['ura_zip'],
            'ura_phone_dcode' => $data['ura_phone_dcode'],
            'ura_phone' => $data['ura_phone']
        );
        if (!FatApp::getDb()->insertFromArray(static::DB_TBL_USR_RETURN_ADDR, $assignValues, false, array(), $assignValues)) {
            $this->error = $this->db->getError();
            return false;
        }
        return true;
    }

    public function updateUserReturnAddressLang($data = array())
    {
        if (($this->mainTableRecordId < 1)) {
            $this->error = Labels::getLabel('ERR_INVALID_REQUEST_USER_NOT_INITIALIZED', $this->commonLangId);
            return false;
        }
        $assignValues = array(
            'uralang_user_id' => $this->mainTableRecordId,
            'uralang_lang_id' => $data['lang_id'],
            'ura_name' => $data['ura_name'],
            'ura_city' => $data['ura_city'],
            'ura_address_line_1' => $data['ura_address_line_1'],
            'ura_address_line_2' => $data['ura_address_line_2']
        );
        if (!FatApp::getDb()->insertFromArray(static::DB_TBL_USR_RETURN_ADDR_LANG, $assignValues, false, array(), $assignValues)) {
            $this->error = $this->db->getError();
            return false;
        }
        return true;
    }

    public function deleteUserReturnAddress()
    {
        if (($this->mainTableRecordId < 1)) {
            $this->error = Labels::getLabel('ERR_INVALID_REQUEST_USER_NOT_INITIALIZED', $this->commonLangId);
            return false;
        }

        if (!FatApp::getDb()->deleteRecords(static::DB_TBL_USR_RETURN_ADDR, array('smt' => 'ura_user_id = ?', 'vals' => array($this->mainTableRecordId)))) {
            $this->error = FatApp::getDb()->getError();
            return false;
        }
        if (!FatApp::getDb()->deleteRecords(static::DB_TBL_USR_RETURN_ADDR_LANG, array('smt' => 'uralang_user_id = ?', 'vals' => array($this->mainTableRecordId)))) {
            $this->error = FatApp::getDb()->getError();
            return false;
        }
        return true;
    }

    public function getSupplierRequestFieldsValueArr($requestId, $langId)
    {
        $requestId = FatUtility::int($requestId);
        if (1 > $requestId) {
            $this->error = Labels::getLabel('ERR_INVALID_REQUEST', $langId);
            return false;
        }
        $srch = new SearchBase(static::DB_TBL_USR_SUPP_REQ, 'tusr');
        $srch->joinTable('tbl_user_supplier_request_values', 'INNER JOIN', 'tusr.usuprequest_id = tusrv.sfreqvalue_request_id', 'tusrv');
        $srch->joinTable('tbl_user_supplier_request_values_lang', 'LEFT OUTER JOIN', 'tusrv.sfreqvalue_id = tusrv_lang.sfreqvaluelang_sfreqvalue_id AND tusrv_lang.sfreqvaluelang_lang_id = ' . $langId, 'tusrv_lang');
        $srch->joinTable('tbl_user_supplier_form_fields', 'LEFT OUTER JOIN', 'tusrv.sfreqvalue_formfield_id=tusff.sformfield_id', 'tusff');
        $srch->joinTable(
            'tbl_user_supplier_form_fields_lang',
            'LEFT OUTER JOIN',
            'tusff_l.sformfieldlang_sformfield_id=tusff.sformfield_id and tusff_l.sformfieldlang_lang_id = ' . $langId,
            'tusff_l'
        );
        $srch->joinTable(
            'tbl_attached_files',
            'LEFT OUTER JOIN',
            'af.afile_type =' . AttachedFile::FILETYPE_SELLER_APPROVAL_FILE . ' and
			af.afile_record_id = tusr.usuprequest_user_id and af.afile_record_subid = tusrv.sfreqvalue_formfield_id',
            'af'
        );
        $srch->addCondition('tusrv.sfreqvalue_request_id', '=', 'mysql_func_' . $requestId, 'AND', true);
        $srch->addMultipleFields(
            array('tusrv.*', 'tusff_l.sformfield_caption', 'tusff.*', 'af.afile_id', 'afile_physical_path', 'afile_name', 'IFNULL(tusrv_lang.sfreqvalue_sformfield_caption, tusff_l.sformfield_caption) as sformfield_caption')
        );
        $srch->doNotCalculateRecords();
        $rs = $srch->getResultSet();
        return FatApp::getDb()->fetchAll($rs);
    }

    public function addSupplierRequestData($data, $langId)
    {
        $user_id = FatUtility::int($data['user_id']);
        unset($data['user_id']);
        if ($user_id < 1) {
            $this->error = Labels::getLabel('ERR_INVALID_REQUEST', $langId);
            return false;
        }
        $db = FatApp::getDb();

        $record = new TableRecord(static::DB_TBL_USR_SUPP_REQ);

        $assign_fields = array();
        $assign_fields['usuprequest_user_id'] = $user_id;
        $assign_fields['usuprequest_reference'] = $data["reference"];
        $assign_fields['usuprequest_date'] = date('Y-m-d H:i:s');
        $assign_fields['usuprequest_attempts'] = 1;
        $status = 0;
        if (!FatApp::getConfig("CONF_ADMIN_APPROVAL_SUPPLIER_REGISTRATION", FatUtility::VAR_INT, 1)) {
            $status = 1;
        }

        $assign_fields['usuprequest_status'] = $status;

        $record->assignValues($assign_fields, false, '', '', true);

        $record->setFldValue('usuprequest_attempts', 1, true);
        $onDuplicateKeyUpdate = array(
            'usuprequest_status' => (FatApp::getConfig("CONF_ADMIN_APPROVAL_SUPPLIER_REGISTRATION", FatUtility::VAR_INT, 1)) ? 0 : 1,
            'usuprequest_attempts' => 'mysql_func_usuprequest_attempts+1'
        );
        if (!$record->addNew(array(), $onDuplicateKeyUpdate)) {
            $this->error = $record->getError();
            return false;
        }

        $supplier_request_id = $record->getId();
        if ($supplier_request_id == 0) {
            $this->error = Labels::getLabel('ERR_INVALID_REQUEST', $langId);
            return false;
        }

        /* user update user_is_supplier */
        $userObj = new User($user_id);
        $userObj->activateSupplier($status);

        if (!$db->deleteRecords('tbl_user_supplier_request_values', array('smt' => 'sfreqvalue_request_id = ?', 'vals' => array($supplier_request_id)))) {
            $this->error = $db->getError();
            return false;
        }

        $record = new TableRecord('tbl_user_supplier_request_values');
        if (empty($data['fieldIdsArr'])) {
            return false;
        }

        /* [ */
        $langs = Language::getAllNames();
        $sformFieldCaptionsArr = array();
        if ($langs) {
            foreach ($langs as $language_id => $langName) {
                $sformFieldCaptionsArr[$language_id] = $this->getSupplierFormFields($language_id);
            }
        }
        /* ] */

        foreach ($data['fieldIdsArr'] as $key => $fieldId) {
            if (isset($data['sformfield_' . $fieldId]) && $data['sformfield_' . $fieldId] != '') {
                $arr = array(
                    'sfreqvalue_request_id' => (int) $supplier_request_id,
                    'sfreqvalue_formfield_id' => (int) $fieldId,
                    'sfreqvalue_text' => $data['sformfield_' . $fieldId],
                );
                $record->assignValues($arr);
                if (!$record->addNew()) {
                    $this->error = $record->getError();
                    return false;
                }
                $sfreqvalue_id = $record->getId();

                /* [ */
                if ($langs) {
                    foreach ($langs as $language_id => $langName) {
                        $langData = array(
                            'sfreqvaluelang_sfreqvalue_id' => $sfreqvalue_id,
                            'sfreqvaluelang_lang_id' => $language_id,
                            'sfreqvalue_sformfield_caption' => $sformFieldCaptionsArr[$language_id][$fieldId]['sformfield_caption']
                        );
                        $db->insertFromArray('tbl_user_supplier_request_values_lang', $langData);
                        /* foreach( $sformFieldCaptionsArr[$language_id] as $data ){
                        $langData = array(
                        'sfreqvaluelang_sfreqvalue_id' => $sfreqvalue_id,
                        'sfreqvaluelang_lang_id'=>$language_id,
                        'sfreqvalue_sformfield_caption'=>$data['sformfield_caption']
                        );
                        $db->insertFromArray( 'tbl_user_supplier_request_values_lang', $langData );
                        } */
                    }
                }
                /* ] */
            }
        }


        /* [ */

        /* ] */
        CalculativeDataRecord::updateSellerApprovalCount();
        return $supplier_request_id;
    }

    public function updateSupplierRequest($data = array())
    {
        if (empty($data)) {
            $this->error = Labels::getLabel('ERR_INVALID_REQUEST', $this->commonLangId);
            return false;
        }

        $srequest_id = FatUtility::int($data['request_id']);

        $assignValues = array(
            'usuprequest_status' => $data['status'],
            'usuprequest_comments' => isset($data['comments']) ? $data['comments'] : '',
        );
        if (!FatApp::getDb()->updateFromArray(
            static::DB_TBL_USR_SUPP_REQ,
            $assignValues,
            array('smt' => 'usuprequest_id = ? ', 'vals' => array((int) $srequest_id))
        )) {
            $this->error = $this->db->getError();
            return false;
        }
        CalculativeDataRecord::updateSellerApprovalCount();
        return true;
    }

    public function getUserCatalogRequestsObj($requestId = 0)
    {
        $requestId = FatUtility::int($requestId);

        $srch = new SearchBase(static::DB_TBL_USR_CATALOG_REQ, 'tucr');
        $srch->joinTable(
            static::DB_TBL,
            'INNER JOIN',
            'tucr.' . static::DB_TBL_USR_CATALOG_REQ_PREFIX . 'user_id = u.' . static::DB_TBL_PREFIX . 'id',
            'u'
        );
        $srch->joinTable(static::DB_TBL_CRED, 'LEFT OUTER JOIN', 'uc.' . static::DB_TBL_CRED_PREFIX . 'user_id = u.user_id', 'uc');

        $srch->addCondition('uc.' . static::DB_TBL_CRED_PREFIX . 'active', '=', 'mysql_func_' . applicationConstants::ACTIVE, 'AND', true);

        if ($this->mainTableRecordId > 0) {
            $srch->addCondition('u.' . static::DB_TBL_PREFIX . 'id', '=', 'mysql_func_' . $this->mainTableRecordId, 'AND', true);
        }

        if ($requestId > 0) {
            $srch->addCondition('tucr.' . static::DB_TBL_USR_CATALOG_REQ_PREFIX . 'id', '=', 'mysql_func_' . $requestId, 'AND', true);
        }
        $srch->addCondition('tucr.' . static::DB_TBL_USR_CATALOG_REQ_PREFIX . 'deleted', '=', 'mysql_func_' . applicationConstants::NO, 'AND', true);

        $srch->addMultipleFields(
            array(
                'u.' . static::DB_TBL_PREFIX . 'id',
                'u.' . static::DB_TBL_PREFIX . 'name',
                'u.' . static::DB_TBL_PREFIX . 'phone',
                'uc.' . static::DB_TBL_CRED_PREFIX . 'username',
                'uc.' . static::DB_TBL_CRED_PREFIX . 'email',
            )
        );
        return $srch;
    }

    public function addCatalogRequest($data = array())
    {
        if (!FatApp::getDb()->insertFromArray(static::DB_TBL_USR_CATALOG_REQ, $data)) {
            $this->error = $this->db->getError();
            return false;
        }
        return true;
    }

    public function notifyAdminCatalogRequest($data, $langId)
    {
        $data = array(
            'reference_number' => $data['scatrequest_reference'],
            'request_title' => $data['scatrequest_title'],
            'request_content' => $data['scatrequest_content'],
        );
        $email = new EmailHandler();

        if (!$email->sendNewCatalogNotification($langId, $data)) {
            Message::addMessage(Labels::getLabel("ERR_ERROR_IN_SENDING_NOTIFICATION_EMAIL_TO_ADMIN", $langId));
            return false;
        }
        return true;
    }

    public function updateCatalogRequest($data = array())
    {
        if (empty($data)) {
            $this->error = Labels::getLabel('ERR_INVALID_REQUEST.', $this->commonLangId);
            return false;
        }

        $scatrequest_id = FatUtility::int($data['request_id']);

        $assignValues = array(
            'scatrequest_status' => $data['status'],
            'scatrequest_comments' => isset($data['comments']) ? $data['comments'] : '',
        );
        if (!FatApp::getDb()->updateFromArray(
            static::DB_TBL_USR_CATALOG_REQ,
            $assignValues,
            array('smt' => 'scatrequest_id = ? ', 'vals' => array((int) $scatrequest_id))
        )) {
            $this->error = $this->db->getError();
            return false;
        }
        return true;
    }

    public function deleteCatalogRequest($scatrequest_id)
    {
        $scatrequest_id = FatUtility::int($scatrequest_id);

        if (1 > $scatrequest_id) {
            $this->error = Labels::getLabel('ERR_INVALID_REQUEST.', $this->commonLangId);
            return false;
        }

        $assignValues = ['scatrequest_deleted' => 1];
        if (!FatApp::getDb()->updateFromArray(static::DB_TBL_USR_CATALOG_REQ, $assignValues, ['smt' => 'scatrequest_id = ? ', 'vals' => array((int) $scatrequest_id)])) {
            $this->error = $this->db->getError();
            return false;
        }
        return true;
    }

    public function save()
    {
        $broken = false;
        if (!($this->mainTableRecordId > 0)) {
            $this->setFldValue('user_regdate', date('Y-m-d H:i:s'));
            $this->setFldValue('user_referral_code', uniqid());
        }
        return parent::save();
    }


    /* this function is called for newly signup/registered user, will manage the crediting of referral reward points if any upon new sign up and handle the affilaite user rewarding.*/
    public function setUpRewardEntry($referredUserId, $langId, $referrerCodeSignup = '', $affiliateReferrerCodeSignup = '')
    {
        $referredUserId = FatUtility::int($referredUserId);
        $langId = FatUtility::int($langId);
        if ($referredUserId <= 0 || $langId <= 0) {
            trigger_error("Parameters are not passed", E_USER_ERROR);
        }
        $broken = false;
        /* rewarding will work on the basis of latest cookie date, if both cookies are saved, i.e "Share&Earn Module from Buyer account" and "Affiliate Module" */
        $isAffiliateCookieSet = false;
        $isReferrerCookieSet = false;

        if (!empty($affiliateReferrerCodeSignup)) {
            $isAffiliateCookieSet = true;
        }

        if (!empty($referrerCodeSignup)) {
            $isReferrerCookieSet = true;
        }

        /* prioritize only when, both cookies are set, then credit on the basis of latest cookie set. [ */
        if ($isAffiliateCookieSet && $isReferrerCookieSet) {
            $affiliateReferrerCookieArr = unserialize($affiliateReferrerCodeSignup);
            $referrerCookieArr = unserialize($referrerCodeSignup);
            if ($affiliateReferrerCookieArr['creation_time'] > $referrerCookieArr['creation_time']) {
                $isReferrerCookieSet = false;
            } else {
                $isAffiliateCookieSet = false;
            }
        }
        /* ] */

        if ($isReferrerCookieSet) {
            $this->setUpReferrarRewarding($referredUserId, $langId, $referrerCodeSignup);
        }

        if ($isAffiliateCookieSet) {
            $this->setUpAffiliateRewarding($referredUserId, $langId, $affiliateReferrerCodeSignup);
        }
    }

    private function setUpReferrarRewarding($referredUserId, $langId, $referrerCodeSignup)
    {
        $referredUserId = FatUtility::int($referredUserId);
        $langId = FatUtility::int($langId);
        if ($referredUserId <= 0 || $langId <= 0) {
            trigger_error("Parameters are not passed", E_USER_ERROR);
        }

        /* store refferer details, if any[ */
        $referrerUserId = 0;
        $referrerUserName = '';
        if (!empty($referrerCodeSignup)) {
            $cookieDataArr = unserialize($referrerCodeSignup);
            $userReferrerCode = $cookieDataArr['data'];
            $referrerUserRow = $this->getUserByReferrerCode($userReferrerCode);
            if ($referrerUserRow && $referrerUserRow['user_referral_code'] == $userReferrerCode && $userReferrerCode != '' && $referrerUserRow['user_referral_code'] != '') {
                $referrerUserId = $referrerUserRow['user_id'];
                $referrerUserName = $referrerUserRow['user_name'];
                $this->setUserInfo(array('user_referrer_user_id' => $referrerUserId));
            }
        }
        /* ] */

        /* add Rewards points, upon signing up, referrer will get rewarded[ */
        $CONF_REGISTRATION_REFERRER_REWARD_POINTS = FatApp::getConfig("CONF_REGISTRATION_REFERRER_REWARD_POINTS", FatUtility::VAR_INT, 0);
        if (($referrerUserId > 0) && FatApp::getConfig("CONF_ENABLE_REFERRER_MODULE") && $CONF_REGISTRATION_REFERRER_REWARD_POINTS > 0) {
            $this->addReferrerRewardPoints($referrerUserId, $referredUserId);
        }
        /* ] */


        /* add Rewards points, upon signing up, referral will get rewarded[ */
        $CONF_REGISTRATION_REFERRAL_REWARD_POINTS = FatApp::getConfig("CONF_REGISTRATION_REFERRAL_REWARD_POINTS", FatUtility::VAR_INT, 0);
        if (($referrerUserId > 0) && FatApp::getConfig("CONF_ENABLE_REFERRER_MODULE") && $CONF_REGISTRATION_REFERRAL_REWARD_POINTS > 0) {
            $this->addReferralRewardPoints($referredUserId, $referrerUserId, $referrerUserName);
        }
        /* ] */

        /* remove referrer signup cookie, becoz, new user and referrer rewarded[ */
        /* if( ($isReferrerRewarded || $isReferralRewarded) && $broken === false ){ */
        /* removing cookie */
        CommonHelper::setCookie('referrer_code_signup', '', time() - 3600);
        /* } */
        /* ] */
    }


    private function setUpAffiliateRewarding($referredUserId, $langId, $affiliateReferrerCodeSignup)
    {
        $referredUserId = FatUtility::int($referredUserId);
        $langId = FatUtility::int($langId);
        if ($referredUserId <= 0 || $langId <= 0) {
            trigger_error("Parameters are not passed", E_USER_ERROR);
        }

        $affiliateReferrerUserId = 0;
        /* binding user to its referrer affiliate user[ */
        if (!empty($affiliateReferrerCodeSignup)) {
            $cookieDataArr = unserialize($affiliateReferrerCodeSignup);
            $affiliateReferrerCode = $cookieDataArr['data'];
            $affiliateReferrerUserRow = $this->getUserByReferrerCode($affiliateReferrerCode);
            if ($affiliateReferrerUserRow && $affiliateReferrerUserRow['user_referral_code'] == $affiliateReferrerCode && $affiliateReferrerCode != '' && $affiliateReferrerUserRow['user_referral_code'] != '') {
                $affiliateReferrerUserId = $affiliateReferrerUserRow['user_id'];
                $this->setUserInfo(array('user_affiliate_referrer_user_id' => $affiliateReferrerUserId));
            }
        }
        /* ] */

        /* crediting wallet money to affiliate referrer as per admin configuration[ */
        $CONF_AFFILIATE_SIGNUP_COMMISSION = FatApp::getConfig("CONF_AFFILIATE_SIGNUP_COMMISSION", FatUtility::VAR_INT, 0);
        if ($affiliateReferrerUserId > 0 && $CONF_AFFILIATE_SIGNUP_COMMISSION > 0) {
            $referredUserName = User::getAttributesById($referredUserId, "user_name");

            $utxn_comments = Labels::getLabel('LBL_Signup_Commission_Received.{username}_Registered.', $langId);
            $utxn_comments = str_replace('{username}', $referredUserName, $utxn_comments);
            $transObj = new Transactions();
            $txnDataArr = array(
                'utxn_user_id' => $affiliateReferrerUserId,
                'utxn_credit' => $CONF_AFFILIATE_SIGNUP_COMMISSION,
                'utxn_status' => Transactions::STATUS_COMPLETED,
                'utxn_comments' => $utxn_comments,
                'utxn_type' => Transactions::TYPE_AFFILIATE_REFERRAL_SIGN_UP
            );
            if (!$txnId = $transObj->addTransaction($txnDataArr)) {
                $this->error = $transObj->getError();
            }
            /* Send email to User[ */
            $emailNotificationObj = new EmailHandler();
            $emailNotificationObj->sendTxnNotification($txnId, $langId);
            /* ] */
        }
        /* ] */

        CommonHelper::setCookie('affiliate_referrer_code_signup', '', time() - 3600);
        return true;
    }

    public function setLoginCredentials($username, $email, $password = null, $active = null, $verified = null)
    {
        if (!($this->mainTableRecordId > 0)) {
            $this->error = Labels::getLabel('ERR_INVALID_REQUEST_USER_NOT_INITIALIZED', $this->commonLangId);
            return false;
        }

        if (null != $password) {
            if (!ValidateElement::password($password)) {
                $this->error = Labels::getLabel('ERR_PASSWORD_MUST_BE_EIGHT_CHARACTERS_LONG_AND_ALPHANUMERIC', $this->commonLangId);
                return false;
            }
        }

        $email = (empty($email)) ? null : $email;
        $record = new TableRecord(static::DB_TBL_CRED);
        $arrFlds = array(
            static::DB_TBL_CRED_PREFIX . 'username' => $username,
            static::DB_TBL_CRED_PREFIX . 'email' => $email,
        );
        if (null != $password) {
            $arrFlds[static::DB_TBL_CRED_PREFIX . 'password'] = UserAuthentication::encryptPassword($password);
        }

        if (null != $active) {
            $arrFlds[static::DB_TBL_CRED_PREFIX . 'active'] = $active;
        }
        if (null != $verified) {
            $arrFlds[static::DB_TBL_CRED_PREFIX . 'verified'] = $verified;
        }

        $record->setFldValue(static::DB_TBL_CRED_PREFIX . 'user_id', $this->mainTableRecordId);
        $record->assignValues($arrFlds);
        if (!$record->addNew(array(), $arrFlds)) {
            $this->error = $record->getError();
            return false;
        }

        return true;
    }

    public function setUserInfo($data = array())
    {
        if (empty($data)) {
            return false;
        }

        if (!($this->mainTableRecordId > 0)) {
            $this->error = Labels::getLabel('ERR_INVALID_REQUEST_USER_NOT_INITIALIZED', $this->commonLangId);
            return false;
        }
        $record = new TableRecord(static::DB_TBL);
        $record->setFldValue(static::DB_TBL_PREFIX . 'id', $this->mainTableRecordId);
        $record->assignValues($data);
        if (!$record->addNew(array(), $data)) {
            $this->error = $record->getError();
            return false;
        }
        return true;
    }

    public function setLoginPassword($password, $userId = 0)
    {
        $userId = FatUtility::int($userId);
        if (0 >= $userId) {
            $userId = $this->mainTableRecordId;
        }
        if (!($userId > 0)) {
            $this->error = Labels::getLabel('ERR_INVALID_REQUEST_USER_NOT_INITIALIZED', $this->commonLangId);
            return false;
        }
        $record = new TableRecord(static::DB_TBL_CRED);
        $arrFlds = array(
            static::DB_TBL_CRED_PREFIX . 'password' => UserAuthentication::encryptPassword($password),
            static::DB_TBL_CRED_PREFIX . 'password_old' => ''
        );
        $record->setFldValue(static::DB_TBL_CRED_PREFIX . 'user_id', $userId);
        $record->assignValues($arrFlds);
        if (!$record->addNew(array(), $arrFlds)) {
            $this->error = $record->getError();
            return false;
        }

        if (UserAuthentication::isGuestUserLogged()) {
            $user = new self($userId);
            $userdata = $user->getUserInfo('credential_email', false, false, true);
            $authentication = new UserAuthentication();
            $authentication->login($userdata['credential_email'], $password, $_SERVER['REMOTE_ADDR'], true, 0, 0, User::USER_TYPE_BUYER);
        }

        return true;
    }

    public function changeEmail($email)
    {
        if (trim($email) == '') {
            return false;
        }

        if (!($this->mainTableRecordId > 0)) {
            $this->error = Labels::getLabel('ERR_INVALID_REQUEST_USER_NOT_INITIALIZED', $this->commonLangId);
            return false;
        }

        $record = new TableRecord(static::DB_TBL_CRED);
        $arrFlds = array(
            static::DB_TBL_CRED_PREFIX . 'email' => $email
        );
        $record->setFldValue(static::DB_TBL_CRED_PREFIX . 'user_id', $this->mainTableRecordId);
        $record->assignValues($arrFlds);
        if (!$record->addNew(array(), $arrFlds)) {
            $this->error = $record->getError();
            return false;
        }

        return true;
    }

    public function verifyAccount($v = 1)
    {
        if (!($this->mainTableRecordId > 0)) {
            $this->error = Labels::getLabel('ERR_INVALID_REQUEST_USER_NOT_INITIALIZED', $this->commonLangId);
            return false;
        }

        $db = FatApp::getDb();
        $dataToUpdate = [static::DB_TBL_CRED_PREFIX . 'verified' => $v];
        $condition = ['smt' => static::DB_TBL_CRED_PREFIX . 'user_id = ?', 'vals' => [$this->mainTableRecordId]];

        if (!$db->updateFromArray(static::DB_TBL_CRED, $dataToUpdate, $condition)) {
            $this->error = $db->getError();
            return false;
        }
        return true;
    }

    public function activateAccount($v = 1)
    {
        if (!($this->mainTableRecordId > 0)) {
            $this->error = Labels::getLabel('ERR_INVALID_REQUEST_USER_NOT_INITIALIZED', $this->commonLangId);
            return false;
        }

        $db = FatApp::getDb();
        if (!$db->updateFromArray(
            static::DB_TBL_CRED,
            array(
                static::DB_TBL_CRED_PREFIX . 'active' => $v
            ),
            array(
                'smt' => static::DB_TBL_CRED_PREFIX . 'user_id = ?',
                'vals' => array(
                    $this->mainTableRecordId
                )
            )
        )) {
            $this->error = $db->getError();
            return false;
        }
        $this->logUpdatedRecord();
        return true;
    }

    public function activateSupplier($activateAdveracc = 0)
    {
        if ($this->mainTableRecordId < 1) {
            $this->error = Labels::getLabel('ERR_INVALID_REQUEST_USER_NOT_INITIALIZED', $this->commonLangId);
            return false;
        }

        $arrToUpdate[static::DB_TBL_PREFIX . 'is_supplier'] = applicationConstants::ACTIVE;
        if ($activateAdveracc == 1) {
            $arrToUpdate[static::DB_TBL_PREFIX . 'is_advertiser'] = applicationConstants::ACTIVE;
        }

        $db = FatApp::getDb();
        if (!$db->updateFromArray(
            static::DB_TBL,
            $arrToUpdate,
            array(
                'smt' => static::DB_TBL_PREFIX . 'id = ?',
                'vals' => array(
                    $this->mainTableRecordId
                )
            )
        )) {
            $this->error = $db->getError();
            return false;
        }

        return true;
    }

    public function getProfileData()
    {
        if (!$this->mainTableRecordId > 0) {
            return false;
        }
        $srch = static::getSearchObject(true);
        $srch->addCondition('u.' . static::DB_TBL_PREFIX . 'id', '=', 'mysql_func_' . $this->mainTableRecordId, 'AND', true);
        $srch->doNotCalculateRecords();
        $record = FatApp::getDb()->fetch($srch->getResultSet());
        if (false === $record || is_null($record)) {
            return false;
        }
        unset($record['credential_password']);
        $record['user_email'] = $record['credential_email'];
        return $record;
    }

    public function prepareUserVerificationCode($email = '', $verificationCode = '')
    {
        if (($this->mainTableRecordId < 1)) {
            $this->error = Labels::getLabel('ERR_INVALID_REQUEST.', $this->commonLangId);
            return false;
        }

        $verificationCode = empty($verificationCode) ? $this->mainTableRecordId . '_' . FatUtility::getRandomString(15) : $verificationCode;
        $data = array(
            static::DB_TBL_UEMV_PREFIX . 'user_id' => $this->mainTableRecordId,
            static::DB_TBL_UEMV_PREFIX . 'token' => $verificationCode,
            static::DB_TBL_UEMV_PREFIX . 'email' => trim($email),
        );

        $tblRec = new TableRecord(static::DB_TBL_USER_EMAIL_VER);

        $tblRec->assignValues($data);

        if ($tblRec->addNew(array(), $data)) {
            return $verificationCode;
        } else {
            return false;
        }
    }

    public function prepareUserPhoneOtp($dialCode, $phone)
    {
        if (($this->mainTableRecordId < 1)) {
            $this->error = Labels::getLabel('ERR_INVALID_REQUEST.', $this->commonLangId);
            return false;
        }
        if ($row = $this->getOtpDetail()) {
            $applicableUpto = strtotime($row[static::DB_TBL_UPV_PREFIX . 'expired_on'] . "+" . self::OTP_INTERVAL . " seconds");
            $now = strtotime("+" . self::OTP_AGE . " minutes", time());
            if ($applicableUpto >= $now) {
                $msg = Labels::getLabel('LBL_PLEASE_WAIT_{SECONDS}_SECONDS_TO_RESEND.', $this->commonLangId);
                $this->error = CommonHelper::replaceStringData($msg, ['{SECONDS}' => ($applicableUpto - $now)]);
                return false;
            }
        }

        $min = pow(10, self::OTP_LENGTH - 1);
        $max = pow(10, self::OTP_LENGTH) - 1;
        $otp = mt_rand($min, $max);

        $data = [
            static::DB_TBL_UPV_PREFIX . 'user_id' => $this->mainTableRecordId,
            static::DB_TBL_UPV_PREFIX . 'otp' => $otp,
            static::DB_TBL_UPV_PREFIX . 'phone_dcode' => trim($dialCode),
            static::DB_TBL_UPV_PREFIX . 'phone' => trim($phone),
            static::DB_TBL_UPV_PREFIX . 'expired_on' => date('Y-m-d H:i:s', strtotime("+" . self::OTP_AGE . " minutes", time())),
        ];

        $tblRec = new TableRecord(static::DB_TBL_USER_PHONE_VER);

        $tblRec->assignValues($data);

        if (!$tblRec->addNew([], $data)) {
            $this->error = $tblRec->getError();
            return false;
        }
        return $otp;
    }

    public function verifyUserEmailVerificationCode($code)
    {
        $arrCode = explode('_', $code, 2);
        if (!is_numeric($arrCode[0])) {
            $this->error = Labels::getLabel('ERR_INVALID_CODE', $this->commonLangId);
            return false;
        }

        $userId = FatUtility::int($arrCode[0]);

        $emvSrch = new SearchBase(static::DB_TBL_USER_EMAIL_VER);
        $emvSrch->addCondition(static::DB_TBL_UEMV_PREFIX . 'user_id', '=', 'mysql_func_' . $userId, 'AND', true);
        $emvSrch->addCondition(static::DB_TBL_UEMV_PREFIX . 'token', '=', $code, 'AND');

        $emvSrch->addFld(array(static::DB_TBL_UEMV_PREFIX . 'user_id', static::DB_TBL_UEMV_PREFIX . 'email'));
        $emvSrch->doNotCalculateRecords();
        $rs = $emvSrch->getResultSet();
        if ($row = FatApp::getDb()->fetch($rs)) {
            $this->deleteEmailVerificationToken($userId);
            if (trim($row['uev_email']) == '') {
                return true;
            }
            return $row['uev_email'];
        }
        $this->error = Labels::getLabel('ERR_INVALID_CODE.', $this->commonLangId);
        return false;
    }

    public function getOtpDetail(int $otp = null)
    {
        if (($this->mainTableRecordId < 1)) {
            $this->error = Labels::getLabel('ERR_INVALID_REQUEST.', $this->commonLangId);
            return false;
        }

        $emvSrch = new SearchBase(static::DB_TBL_USER_PHONE_VER);
        $emvSrch->addCondition(static::DB_TBL_UPV_PREFIX . 'user_id', '=', 'mysql_func_' . $this->mainTableRecordId, 'AND', true);

        if (null != $otp) {
            $emvSrch->addCondition(static::DB_TBL_UPV_PREFIX . 'otp', '=', $otp);
        }

        $attr = [
            static::DB_TBL_UPV_PREFIX . 'user_id',
            static::DB_TBL_UPV_PREFIX . 'phone_dcode',
            static::DB_TBL_UPV_PREFIX . 'phone',
            static::DB_TBL_UPV_PREFIX . 'expired_on',
            static::DB_TBL_UPV_PREFIX . 'otp'
        ];
        $emvSrch->addMultipleFields($attr);
        $emvSrch->doNotCalculateRecords();
        $rs = $emvSrch->getResultSet();
        return FatApp::getDb()->fetch($rs);
    }

    public function verifyUserPhoneOtp($otp, $doLogin = false, $returnRow = false)
    {
        if (($this->mainTableRecordId < 1)) {
            $this->error = Labels::getLabel('ERR_INVALID_REQUEST.', $this->commonLangId);
            return false;
        }

        if ('' == $otp || 1 > $otp) {
            $this->error = Labels::getLabel('ERR_INVALID_OTP', $this->commonLangId);
            return false;
        }

        if ($row = $this->getOtpDetail($otp)) {
            if (strtotime($row[static::DB_TBL_UPV_PREFIX . 'expired_on']) < time()) {
                $this->error = Labels::getLabel('ERR_OTP_EXPIRED.', $this->commonLangId);
                return false;
            }

            // $this->deletePhoneOtp($this->mainTableRecordId);
            $this->verifyAccount(applicationConstants::YES);

            if (true === $doLogin) {
                $attr = [
                    'user_phone_dcode',
                    'user_phone',
                    'credential_password'
                ];
                $userInfo = $this->getUserInfo($attr, false, false);
                $userAuthObj = new UserAuthentication();
                $userAuthObj->setLoginWithOtp($userInfo['user_phone_dcode'], $userInfo['user_phone']);
                $userAuthObj->login($userInfo['user_phone_dcode'] . $userInfo['user_phone'], $otp, $_SERVER['REMOTE_ADDR'], false);
                /*
                $this->loginWithOtp = true;
                $this->doLogin($userInfo['user_phone_dcode'] . $userInfo['user_phone'], $otp);
                */
            }
            return (true == $returnRow) ? $row : true;
        } else {
            $this->error = Labels::getLabel('ERR_INVALID_OTP.', $this->commonLangId);
            return false;
        }
        return false;
    }

    public function resetPassword($pwd)
    {
        if (!($this->mainTableRecordId > 0)) {
            $this->error = Labels::getLabel('ERR_INVALID_REQUEST_USER_NOT_INITIALIZED', $this->commonLangId);
            return false;
        }

        $db = FatApp::getDb();
        if (!$db->updateFromArray(static::DB_TBL_CRED, [static::DB_TBL_CRED_PREFIX . 'password' => $pwd, static::DB_TBL_CRED_PREFIX . 'password_old' => ''], ['smt' => static::DB_TBL_CRED_PREFIX . 'user_id = ?', 'vals' => [$this->mainTableRecordId]])) {
            $this->error = $db->getError();
            return false;
        }

        return true;
    }

    public function notifyAdminRegistration($data, $langId)
    {
        $userType = isset($data['user_registered_initially_for']) ? $data['user_registered_initially_for'] : '';
        $phone = !empty($data['user_phone']) ? $data['user_phone'] : '';
        $dialCode = !empty($data['user_phone_dcode']) ? ValidateElement::formatDialCode($data['user_phone_dcode']) : '';
        $data = array(
            'user_name' => $data['user_name'],
            'user_username' => $data['user_username'],
            'user_email' => isset($data['user_email']) ? $data['user_email'] : '',
            'user_phone_dcode' => $dialCode,
            'user_phone' => $phone,
            'user_type' => $userType,
        );
        $email = new EmailHandler();
        if (!$email->sendNewRegistrationNotification($langId, $data)) {
            $this->error = Labels::getLabel("ERR_ERROR_IN_SENDING_NOTIFICATION_EMAIL_TO_ADMIN", $langId);
            return false;
        }
        return true;
    }

    public function userEmailVerification($data, $langId)
    {
        $verificationCode = $this->prepareUserVerificationCode();
        $link = UrlHelper::generateFullUrl('GuestUser', 'userCheckEmailVerification', array('verify' => $verificationCode), CONF_WEBROOT_FRONT_URL);
        $data = array(
            'user_name' => $data['user_name'],
            'link' => $link,
            'user_email' => $data['user_email'],
        );
        $email = new EmailHandler();
        if (!$email->sendSignupVerificationLink($langId, $data)) {
            Message::addMessage(Labels::getLabel("ERR_ERROR_IN_SENDING_VERIFICATION_EMAIL", $langId));
            return false;
        }
        return true;
    }

    public function userPhoneVerification($data, $langId, $tpl = SmsTemplate::LOGIN)
    {
        $phone = !empty($data['user_phone']) ? trim($data['user_phone']) : '';
        $dialCode = !empty($data['user_phone_dcode']) ? ValidateElement::formatDialCode(trim($data['user_phone_dcode'])) : '';
        $user_name = !empty($data['user_name']) && isset($data['user_name']) ? $data['user_name'] : $dialCode . '-' . $phone;

        $otp = $this->prepareUserPhoneOtp($dialCode, $phone);
        if (false === $otp) {
            return false;
        }
        return $this->sendOtp($dialCode . $phone, $user_name, $otp, $langId, $tpl);
    }

    public function sendOtp($phone, $user_name, $otp, $langId, $tpl = SmsTemplate::LOGIN)
    {
        $langId = FatUtility::int($langId);
        if (empty($phone) || empty($otp)) {
            $this->error = Labels::getLabel("ERR_INVALID_REQUEST", $langId);
            return false;
        }
        $phone = false !== strpos($phone, '+') ? $phone : '+' . $phone;
        $replacements = [
            '{OTP}' => $otp,
            '{USER_NAME}' => $user_name
        ];
        $smsArchive = new SmsArchive();
        $smsArchive->toPhone($phone);
        if (false === $smsArchive->setTemplate($langId, $tpl, $replacements)) {
            $this->error = $smsArchive->getError();
            return false;
        }

        if (false === $smsArchive->send()) {
            $this->error = $smsArchive->getError();
            return false;
        }
        return true;
    }

    public function resendOtp($tpl = SmsTemplate::LOGIN)
    {
        if ($this->mainTableRecordId < 1) {
            $this->error = Labels::getLabel('ERR_INVALID_REQUEST_USER_NOT_INITIALIZED', $this->commonLangId);
            return false;
        }

        $attr = ['user_name', 'user_phone_dcode', 'user_phone'];
        $userData = $this->getUserInfo($attr, false, false);
        return $this->userPhoneVerification($userData, $this->commonLangId, $tpl);
    }

    public function guestUserWelcomeEmail($data, $langId)
    {
        $link = UrlHelper::generateFullUrl('GuestUser', 'loginForm');
        $data = array(
            'user_name' => $data['user_name'],
            'user_email' => $data['user_email'],
            'account_type' => User::getUserTypesArr($langId)[$data['user_type']],
            'link' => $link,
        );

        $email = new EmailHandler();
        if (!$email->sendWelcomeEmailToGuestUser($langId, $data)) {
            Message::addMessage(Labels::getLabel("ERR_ERROR_IN_SENDING_WELCOME_EMAIL", $langId));
            return false;
        }

        return true;
    }

    public function userWelcomeEmailRegistration($data, $link, $langId)
    {
        $dialCode = (array_key_exists('user_phone_dcode', $data) ? ValidateElement::formatDialCode($data['user_phone_dcode']) : '');
        $phone = (array_key_exists('user_phone', $data) ? $data['user_phone'] : '');

        $data = array(
            'user_name' => $data['user_name'],
            'user_email' => $data['user_email'],
            'username' => $data['credential_username'] ?? $data['user_name'],
            'account_type' => $data['account_type'] ?? '',
            'user_phone_dcode' => $dialCode,
            'user_phone' => $phone,
            'link' => $link,
            'user_is_affiliate' => $data['user_is_affiliate'] ?? 0
        );

        $email = new EmailHandler();
        if (!$email->sendWelcomeEmail($langId, $data)) {
            Message::addMessage(Labels::getLabel("ERR_ERROR_IN_SENDING_WELCOME_EMAIL", $langId));
            return false;
        }
        return true;
    }

    public function sendAdminNewUserCreationEmail($userData, $langId)
    {
        $userAuthObj = new UserAuthentication();
        $token = FatUtility::getRandomString(30);
        $userAuthObj->deleteOldPasswordResetRequest($userData['user_id']);

        $data = array(
            'user_name' => $userData['user_name'],
            'user_id' => $userData['user_id'],
            'user_email' => $userData['user_email'],
            'account_type' => $userData['account_type'],
            'link' => UrlHelper::generateFullUrl('GuestUser', 'resetPassword', array($userData['user_id'], $token), CONF_WEBROOT_FRONT_URL),
            'token' => $token,
            'days' => 7,
        );

        if (!$userAuthObj->addPasswordResetRequest($data)) {
            $this->error = $userAuthObj->getError();
            return false;
        }

        $email = new EmailHandler();
        if (!$email->sendAdminNewUserCreationEmail($langId, $data)) {
            $this->error = $email->getError();
            return false;
        }
        return true;
    }

    public function notifyAdminSupplierApproval($userObj, $data, $approval_request = 1, $langId = 0)
    {
        $langId = 1 > $langId ? CommonHelper::getLangId() : $langId;
        $attr = array('user_name', 'credential_username', 'credential_email');
        $userData = $userObj->getUserInfo($attr, false, false);

        if ($userData === false) {
            $this->error = Labels::getLabel("ERR_INVALID_USER", $langId);
            return false;
        }

        $data = array(
            'user_name' => $userData['user_name'],
            'username' => $userData['credential_username'],
            'user_email' => $userData['credential_email'],
            'reference_number' => $data['reference'],
        );

        $email = new EmailHandler();
        if (!$email->sendSupplierApprovalNotification($langId, $data, $approval_request)) {
            $err = $email->getError();
            $this->error = !empty($err) ? $err : Labels::getLabel("ERR_ERROR_IN_SENDING_SUPPLIER_APPROVAL_EMAIL", $langId);
            return false;
        }
        return true;
    }

    public static function getUserBalance($user_id, $excludePendingWidrawReq = true, $excludePromotion = true, $excludeProcessedWidrawReq = true)
    {
        $user_id = FatUtility::int($user_id);
        $srch = new SearchBase('tbl_user_transactions', 'txn');
        $srch->doNotCalculateRecords();
        $srch->doNotLimitRecords();
        $srch->addGroupBy('txn.utxn_user_id');
        $srch->addMultipleFields(array("SUM(utxn_credit - utxn_debit) as userBalance"));
        $srch->addCondition('utxn_user_id', '=', 'mysql_func_' . $user_id, 'AND', true);
        $srch->addCondition('utxn_status', '=', 'mysql_func_' . Transactions::STATUS_COMPLETED, 'AND', true);
        $rs = $srch->getResultSet();
        if (!$row = FatApp::getDb()->fetch($rs)) {
            return 0;
        }

        $userBalance = $row["userBalance"];

        if ($excludePendingWidrawReq) {
            $srch = new SearchBase('tbl_user_withdrawal_requests', 'uwr');
            $srch->doNotCalculateRecords();
            $srch->doNotLimitRecords();
            $srch->addGroupBy('uwr.withdrawal_user_id');
            $srch->addMultipleFields(array("SUM(withdrawal_amount) as withdrawal_amount"));
            $srch->addCondition('withdrawal_user_id', '=', $user_id);
            $cnd = $srch->addCondition('withdrawal_status', '=', Transactions::WITHDRAWL_STATUS_PENDING);
            if (true == $excludeProcessedWidrawReq) {
                $cnd->attachCondition('withdrawal_status', '=', Transactions::WITHDRAWL_STATUS_PROCESSED);
            }
            $rs = $srch->getResultSet();
            if ($res = FatApp::getDb()->fetch($rs)) {
                $userBalance = $userBalance - $res["withdrawal_amount"];
            }
        }

        if ($excludePromotion) {
            $promotionCharges = Promotion::getPromotionWalleToBeCharged($user_id);
            $userBalance = $userBalance - $promotionCharges;
        }
        return $userBalance;
    }

    public static function getUserWithdrawnRequestAmount($user_id)
    {
        $srch = new SearchBase('tbl_user_withdrawal_requests', 'uwr');
        $srch->doNotCalculateRecords();
        $srch->doNotLimitRecords();
        $srch->addGroupBy('uwr.withdrawal_user_id');
        $srch->addMultipleFields(array("SUM(withdrawal_amount) as withdrawal_amount"));
        $srch->addCondition('withdrawal_user_id', '=', $user_id);
        $srch->addCondition('withdrawal_status', '=', Transactions::WITHDRAWL_STATUS_PENDING);
        $rs = $srch->getResultSet();
        $withdrawlAmount = 0;
        if ($res = FatApp::getDb()->fetch($rs)) {
            $withdrawlAmount = $res["withdrawal_amount"];
        }
        return $withdrawlAmount;
    }


    public static function getAffiliateUserRevenue($user_id, $date = '')
    {
        $user_id = FatUtility::int($user_id);
        $srch = new SearchBase('tbl_user_transactions', 'txn');
        $srch->doNotCalculateRecords();
        $srch->doNotLimitRecords();
        $srch->addGroupBy('txn.utxn_user_id');
        $srch->addMultipleFields(array("SUM(utxn_credit) as userRevenue"));
        $srch->addCondition('utxn_user_id', '=', $user_id);
        $srch->addCondition('utxn_status', '=', Transactions::STATUS_COMPLETED);
        $cnd = $srch->addCondition('utxn_type', '=', Transactions::TYPE_AFFILIATE_REFERRAL_SIGN_UP);
        $cnd->attachCondition('utxn_type', '=', Transactions::TYPE_AFFILIATE_REFERRAL_ORDER);
        if (!empty($date)) {
            $srch->addCondition('mysql_func_DATE(utxn_date)', '=', $date, 'AND', true);
        }
        $row = FatApp::getDb()->fetch($srch->getResultSet());
        return $row["userRevenue"] ?? 0;
    }

    public static function getUserLastWithdrawalRequest($userId)
    {
        $userId = FatUtility::int($userId);
        if (1 > $userId) {
            return false;
        }

        $srch = new SearchBase(static::DB_TBL_USR_WITHDRAWAL_REQ, 'tuwr');
        $srch->doNotCalculateRecords();
        $srch->doNotLimitRecords();
        $srch->addCondition('withdrawal_user_id', '=', $userId);
        $srch->addOrder('withdrawal_request_date', 'desc');
        $rs = $srch->getResultSet();

        if (!$rs) {
            return false;
        }

        if (!$row = FatApp::getDb()->fetch($rs)) {
            return false;
        }
        return $row;
    }

    public function addWithdrawalRequest($data, $langId)
    {
        $userId = FatUtility::int($data['ub_user_id']);
        unset($data['ub_user_id']);
        if ($userId < 1) {
            $this->error = Labels::getLabel('ERR_INVALID_REQUEST.', $this->commonLangId);
            return false;
        }

        /* $assignFields = array(
        'withdrawal_amount'=>$data['withdrawal_amount'],
        'withdrawal_bank'=>$data['ub_bank_name'],
        'withdrawal_account_holder_name'=>$data['ub_account_holder_name'],
        'withdrawal_account_number'=>$data['ub_account_number'],
        'withdrawal_ifc_swift_code'=>$data['ub_ifsc_swift_code'],
        'withdrawal_bank_address'=>$data['ub_bank_address'],
        'withdrawal_comments'=>$data['withdrawal_comments'],
        'withdrawal_status'=>0,
        'withdrawal_request_date'=>date('Y-m-d H:i:s'),
        'withdrawal_user_id'=>$userId,
        ); */
        $assignFields = array(
            'withdrawal_user_id' => $userId,
            'withdrawal_payment_method' => isset($data['withdrawal_payment_method']) ? $data['withdrawal_payment_method'] : '',
            'withdrawal_amount' => isset($data['withdrawal_amount']) ? $data['withdrawal_amount'] : '',
            'withdrawal_bank' => isset($data['ub_bank_name']) ? $data['ub_bank_name'] : '',
            'withdrawal_account_holder_name' => isset($data['ub_account_holder_name']) ? $data['ub_account_holder_name'] : '',
            'withdrawal_account_number' => isset($data['ub_account_number']) ? $data['ub_account_number'] : '',
            'withdrawal_ifc_swift_code' => isset($data['ub_ifsc_swift_code']) ? $data['ub_ifsc_swift_code'] : '',
            'withdrawal_bank_address' => isset($data['ub_bank_address']) ? $data['ub_bank_address'] : '',
            'withdrawal_instructions' => isset($data['withdrawal_instructions']) ? $data['withdrawal_instructions'] : '',
            'withdrawal_status' => 0,
            'withdrawal_request_date' => date('Y-m-d H:i:s'),
            'withdrawal_cheque_payee_name' => isset($data['withdrawal_cheque_payee_name']) ? $data['withdrawal_cheque_payee_name'] : '',
            'withdrawal_paypal_email_id' => isset($data['withdrawal_paypal_email_id']) ? $data['withdrawal_paypal_email_id'] : ''
        );

        $broken = false;

        $db = FatApp::getDb();
        $db->startTransaction();
        if ($db->insertFromArray(static::DB_TBL_USR_WITHDRAWAL_REQ, $assignFields)) {
            $withdrawRequestId = $db->getInsertId();

            $formattedRequestValue = '#' . str_pad($withdrawRequestId, 6, '0', STR_PAD_LEFT);

            $txnArray["utxn_user_id"] = $userId;
            $txnArray["utxn_debit"] = $data["withdrawal_amount"];
            $txnArray["utxn_status"] = Transactions::STATUS_PENDING;
            $txnArray["utxn_comments"] = Labels::getLabel('LBL_Funds_Withdrawn', $langId) . '. ' . Labels::getLabel('LBL_Request_ID', $langId) . ' ' . $formattedRequestValue;
            $txnArray["utxn_withdrawal_id"] = $withdrawRequestId;
            $txnArray['utxn_type'] = Transactions::TYPE_MONEY_WITHDRAWN;

            $transObj = new Transactions();
            if ($txnId = $transObj->addTransaction($txnArray)) {
                /*
                becoz email sent while requesting wallet withdrawal.
                $emailNotificationObj = new EmailHandler();
                $emailNotificationObj->sendTxnNotification($txnId,$langId) ;*/
            } else {
                $this->error = $transObj->getError();
                $broken = true;
            }
        } else {
            $this->error = $db->getError();
            $broken = true;
        }

        if ($broken === false && $db->commitTransaction()) {
            return $withdrawRequestId;
        }

        $db->rollbackTransaction();
        return false;
    }

    private function deleteEmailVerificationToken($userId)
    {
        FatApp::getDb()->deleteRecords(static::DB_TBL_USER_EMAIL_VER, array('smt' => static::DB_TBL_UEMV_PREFIX . 'user_id = ?', 'vals' => array($userId)));
        return true;
    }

    public function deletePhoneOtp($userId)
    {
        $db = FatApp::getDb();
        if (!$db->deleteRecords(static::DB_TBL_USER_PHONE_VER, array('smt' => static::DB_TBL_UPV_PREFIX . 'user_id = ?', 'vals' => [$userId]))) {
            $this->error = $db->getError();
            return false;
        }
        return true;
    }

    /* function getUser($data = array()){
    $srch = new SearchBase(static::DB_TBL,'tu');
    $srch->joinTable('tbl_states', 'LEFT JOIN', 'tu.user_state_county=ts.state_id', 'ts');
    $srch->joinTable('tbl_countries', 'LEFT JOIN','tu.user_country=tc.country_id', 'tc');

    foreach($data as $key=>$val) {
    if(strval($val)=='') continue;
        switch($key) {
            case 'user_id':
    case 'id':
                $srch->addCondition('tu.user_id', '=', intval($val));
            break;
            case 'user_email':
                $srch->addCondition('tu.user_email', '=', $val);
            break;
    case 'user_username':
                $srch->addCondition('tu.user_username', '=', $val);
            break;
    case 'user_name':
                $srch->addCondition('tu.user_name', '=', $val);
            break;
    case 'user_email_username':
                $cndCondition=$srch->addCondition('tu.user_email', '=', $val);
                $cndCondition->attachCondition('tu.user_username', '=', $val,'OR');
            break;
    case 'facebook_id':
                $srch->addCondition('tu.user_facebook_id', '=', $val);
            break;
    case 'googleplus_id':
                $srch->addCondition('tu.user_googleplus_id', '=', $val);
                break;
    case 'token':
                $srch->addCondition('tu.user_app_token', '=', $val);
                break;
    case 'refer_code':
                $srch->addCondition('tu.user_referral_code', '=', $val);
                break;
            }
        }

    $rs = $srch->getResultSet();
    if(!$row = $this->db->fetch($rs)){
    return false;
    }

    return $row;
    } */
    public static function getUserShopName(int $user_id, $langId)
    {
        $user_id = FatUtility::int($user_id);
        $srch = new SearchBase(static::DB_TBL, 'tu');
        $srch->joinTable('tbl_shops', 'LEFT JOIN', 'tu.user_id = s.shop_user_id', 's');
        $srch->joinTable(Shop::DB_TBL_LANG, 'LEFT JOIN', 's.shop_id = s_l.shoplang_shop_id AND shoplang_lang_id = ' . $langId, 's_l');
        $srch->joinTable(static::DB_TBL_CRED, 'LEFT OUTER JOIN', 'uc.' . static::DB_TBL_CRED_PREFIX . 'user_id = tu.user_id', 'uc');
        $srch->addMultipleFields(array('user_id', 'user_name', 'COALESCE(s_l.shop_name, s.shop_identifier) as shop_name'));
        $srch->addCondition('tu.user_id', '=', intval($user_id));
        $srch->doNotCalculateRecords();
        $srch->doNotLimitRecords();
        $srch->setPagesize(1);
        if (!$row = FatApp::getDb()->fetch($srch->getResultSet())) {
            return false;
        }

        return $row;
    }

    public static function isAdminLogged($ip = '')
    {
        if ($ip == '') {
            $ip = $_SERVER['REMOTE_ADDR'];
        }

        if (isset($_SESSION[static::ADMIN_SESSION_ELEMENT_NAME]) && $_SESSION[static::ADMIN_SESSION_ELEMENT_NAME]['admin_ip'] == $ip) {
            return true;
        }

        return false;
    }

    public static function isSellerVerified($userId)
    {
        $userId = FatUtility::int($userId);
        $userObj = new User($userId);
        $srch = $userObj->getUserSupplierRequestsObj();
        $srch->addFld(array('usuprequest_attempts', 'usuprequest_id', 'usuprequest_status'));
        $srch->doNotCalculateRecords();
        $rs = $srch->getResultSet();
        $supplierRequest = FatApp::getDb()->fetch($rs);
        if (is_array($supplierRequest) && $supplierRequest['usuprequest_status'] == User::SUPPLIER_REQUEST_APPROVED) {
            return true;
        }
        return false;
    }

    public static function getUserExtraData($user_id, $attr = null)
    {
        $user_id = FatUtility::int($user_id);
        $srch = new SearchBase(static::DB_TBL_USR_EXTRAS);
        $srch->doNotCalculateRecords();

        if (null != $attr) {
            if (is_array($attr)) {
                $srch->addMultipleFields($attr);
            } elseif (is_string($attr)) {
                $srch->addFld($attr);
            }
        }
        $srch->addCondition('uextra_user_id', '=', $user_id);
        $rs = $srch->getResultSet();
        return FatApp::getDb()->fetch($rs);
    }

    public static function isCatalogRequestSubmittedForApproval($preqId)
    {
        $row = ProductRequest::getAttributesById($preqId, array('preq_submitted_for_approval'));
        if (!empty($row) && $row['preq_submitted_for_approval'] == applicationConstants::YES) {
            return true;
        }
        return false;
    }

    public function setMobileAppToken(int $expirationAge = 7)
    {
        if (($this->mainTableRecordId < 1)) {
            $this->error = Labels::getLabel('ERR_INVALID_REQUEST_USER_NOT_INITIALIZED', $this->commonLangId);
            return false;
        }

        $generatedToken = substr(md5(rand(1, 99999) . microtime()), 0, UserAuthentication::TOKEN_LENGTH);

        $expiry = strtotime("+" . $expirationAge . " DAYS");
        $values = array(
            'uauth_user_id' => $this->mainTableRecordId,
            'uauth_token' => $generatedToken,
            'uauth_expiry' => date('Y-m-d H:i:s', $expiry),
            'uauth_browser' => CommonHelper::userAgent(),
            'uauth_last_access' => date('Y-m-d H:i:s'),
            'uauth_last_ip' => CommonHelper::getClientIp(),
        );
        if (!UserAuthentication::saveLoginToken($values)) {
            return false;
        }

        return $generatedToken;
    }

    public function createUserTempToken($generatedToken)
    {
        if (($this->mainTableRecordId < 1)) {
            $this->error = Labels::getLabel('ERR_INVALID_REQUEST_USER_NOT_INITIALIZED', $this->commonLangId);
            return false;
        }
        FatApp::getDb()->deleteRecords(static::DB_TBL_USR_MOBILE_TEMP_TOKEN, array('smt' => static::DB_TBL_USR_MOBILE_TEMP_TOKEN_PREFIX . 'user_id = ?', 'vals' => array((int) $this->mainTableRecordId)));
        $assignValues = array(
            static::DB_TBL_USR_MOBILE_TEMP_TOKEN_PREFIX . 'user_id' => $this->mainTableRecordId,
            static::DB_TBL_USR_MOBILE_TEMP_TOKEN_PREFIX . 'token' => $generatedToken,
            static::DB_TBL_USR_MOBILE_TEMP_TOKEN_PREFIX . 'expiry' => date('Y-m-d H:i:s', strtotime("+10 MINUTE")),
        );
        if (!FatApp::getDb()->insertFromArray(static::DB_TBL_USR_MOBILE_TEMP_TOKEN, $assignValues, false, array(), $assignValues)) {
            $this->error = FatApp::getDb()->getError();
            return false;
        }
        return true;
    }

    public function validateAPITempToken($token)
    {
        if (($this->mainTableRecordId < 1)) {
            $this->error = Labels::getLabel('ERR_INVALID_REQUEST_USER_NOT_INITIALIZED', $this->commonLangId);
            return false;
        }
        $srch = new SearchBase(static::DB_TBL_USR_MOBILE_TEMP_TOKEN);
        $srch->addCondition('uttr_user_id', '=', $this->mainTableRecordId);
        $srch->addCondition('uttr_token', '=', $token);
        $srch->addCondition('uttr_expiry', '>=', date('Y-m-d H:i:s'));
        $srch->addMultipleFields(array('uttr_user_id', 'uttr_token'));
        $srch->doNotCalculateRecords();
        $srch->setPagesize(1);
        $rs = $srch->getResultSet();
        if ((!$row = FatApp::getDb()->fetch($rs)) || ($row['uttr_token'] !== $token)) {
            return false;
        }
        return $row;
    }

    public function deleteUserAPITempToken()
    {
        if (($this->mainTableRecordId < 1)) {
            $this->error = Labels::getLabel('ERR_INVALID_REQUEST_USER_NOT_INITIALIZED', $this->commonLangId);
            return false;
        }
        if (FatApp::getDb()->deleteRecords(static::DB_TBL_USR_MOBILE_TEMP_TOKEN, array('smt' => static::DB_TBL_USR_MOBILE_TEMP_TOKEN_PREFIX . 'user_id = ?', 'vals' => array((int) $this->mainTableRecordId)))) {
            return true;
        }
    }

    public static function getFcmTokenUserId(string $fcmToken): string
    {
        $srch = new SearchBase(UserAuthentication::DB_TBL_USER_AUTH, 'uat');
        $srch->addFld('uauth_user_id');
        $srch->addCondition('uauth_fcm_id', '=', $fcmToken);
        $srch->doNotCalculateRecords();
        $rs = $srch->getResultSet();
        $row = FatApp::getDb()->fetch($rs);
        return isset($row['uauth_user_id']) ? $row['uauth_user_id'] : '';
    }

    public static function getUserAuthFcmFormattedData(int $userType, string $fcmToken, mixed $deviceOs = null, mixed $mainTableRecordId = null, string $appToken = '')
    {
        $expiry = strtotime("+7 DAYS");
        $userType = 1 > $userType ? User::USER_TYPE_BUYER : $userType;

        $data = [
            'uauth_user_type' => $userType,
            'uauth_expiry' => date('Y-m-d H:i:s', $expiry),
            'uauth_browser' => CommonHelper::userAgent(),
            'uauth_fcm_id' => $fcmToken,
            'uauth_last_access' => date('Y-m-d H:i:s'),
            'uauth_last_ip' => CommonHelper::getClientIp(),
        ];

        if (null !== $deviceOs && is_int($deviceOs)) {
            $data['uauth_device_os'] = $deviceOs;
        }

        if (null !== $mainTableRecordId && is_int($mainTableRecordId)) {
            $data['uauth_user_id'] = $mainTableRecordId;
        }

        if (!empty($appToken)) {
            $data['uauth_token'] = $appToken;
        }
        return $data;
    }

    public function setPushNotificationToken($appToken, $fcmDeviceId, $userType = User::USER_TYPE_BUYER, $deviceOs = 0)
    {
        if (($this->mainTableRecordId < 1)) {
            $this->error = Labels::getLabel('ERR_INVALID_REQUEST_USER_NOT_INITIALIZED', $this->commonLangId);
            return false;
        }
        $deviceOs = FatUtility::int($deviceOs);

        $userId = static::getFcmTokenUserId($fcmDeviceId);

        if (empty($userId)) {
            FatApp::getDb()->deleteRecords(
                UserAuthentication::DB_TBL_USER_AUTH,
                [
                    'smt' => 'uauth_fcm_id = ? and uauth_token != ?',
                    'vals' => array($fcmDeviceId, $appToken)
                ]
            );
            $values = static::getUserAuthFcmFormattedData($userType, $fcmDeviceId, $deviceOs);
            $where = array('smt' => 'uauth_user_id = ? and uauth_token = ?', 'vals' => array((int) $this->mainTableRecordId, $appToken));
        } else {
            FatApp::getDb()->deleteRecords(
                UserAuthentication::DB_TBL_USER_AUTH,
                [
                    'smt' => 'uauth_token = ?',
                    'vals' => array($appToken)
                ]
            );
            $values = static::getUserAuthFcmFormattedData($userType, $fcmDeviceId, $deviceOs, (int) $this->mainTableRecordId, $appToken);
            $where = array('smt' => 'uauth_fcm_id = ?', 'vals' => [$fcmDeviceId]);
        }
        return UserAuthentication::updateFcmDeviceToken($values, $where);
    }

    public static function setGuestFcmToken(int $userType, string $fcmToken, int $deviceOs, string $appToken): bool
    {
        $data = static::getUserAuthFcmFormattedData($userType, $fcmToken, $deviceOs, null, $appToken);
        return UserAuthentication::saveLoginToken($data);
    }


    public function getPushNotificationTokens()
    {
        if (($this->mainTableRecordId < 1)) {
            $this->error = Labels::getLabel('ERR_INVALID_REQUEST_USER_NOT_INITIALIZED', $this->commonLangId);
            return false;
        }
        $db = FatApp::getDb();

        $srch = self::getSearchObject(true);
        $srch->joinTable(UserAuthentication::DB_TBL_USER_AUTH, 'LEFT OUTER JOIN', 'uauth.uauth_user_id = u.user_id', 'uauth');
        $srch->addCondition('user_id', '=', $this->mainTableRecordId);
        $srch->addCondition('uc.' . static::DB_TBL_CRED_PREFIX . 'active', '=', 1);
        $srch->addCondition('uc.' . static::DB_TBL_CRED_PREFIX . 'verified', '=', 1);
        $srch->addCondition('uauth_fcm_id', '!=', '');
        // $srch->addCondition('uauth_last_access', '>=', date('Y-m-d H:i:s', strtotime("-7 DAYS")));
        $srch->addMultipleFields(['uauth_fcm_id', 'uauth_device_os']);
        $srch->addOrder('uauth_last_access', 'DESC');
        $srch->doNotCalculateRecords();
        $srch->doNotLimitRecords();
        $rs = $srch->getResultSet();
        if (!$row = $db->fetchAll($rs)) {
            return array();
        }
        return $row;
    }

    public function referredByAffilates($affilateUserId)
    {
        if ($affilateUserId < 1) {
            $this->error = Labels::getLabel('ERR_INVALID_REQUEST_USER_NOT_INITIALIZED', $this->commonLangId);
            return false;
        }

        $srch = $this->getUserSearchObj(null, true);
        $srch->addCondition('user_affiliate_referrer_user_id', '=', $affilateUserId);

        return $srch;
    }

    public static function setImageUpdatedOn($userId, $date = '')
    {
        $date = empty($date) ? date('Y-m-d  H:i:s') : $date;
        $where = array('smt' => 'user_id = ?', 'vals' => array($userId));
        FatApp::getDb()->updateFromArray(static::DB_TBL, array('user_updated_on' => date('Y-m-d  H:i:s')), $where);
    }

    public function saveUserData($postedData, $socialUser = false, $returnUserId = false)
    {
        $db = FatApp::getDb();
        $db->startTransaction();

        $userPhone = array_key_exists('user_phone', $postedData) ? $postedData['user_phone_dcode'] . $postedData['user_phone'] : '';

        if (empty($userPhone) && !filter_var($postedData['user_email'], FILTER_VALIDATE_EMAIL)) {
            $this->error = Labels::getLabel("ERR_Invalid_email_address", $this->commonLangId);
            return false;
        }

        $email = isset($postedData['user_email']) && !empty($postedData['user_email']) ? $postedData['user_email'] : '';

        if (!$this->validateUserForRegistration(strtolower($postedData['user_username']), $email, $userPhone)) {
            return false;
        }

        $this->assignValues($postedData);
        if (!$this->save()) {
            $db->rollbackTransaction();
            return false;
        }

        $password = array_key_exists('user_password', $postedData) && !empty($postedData['user_password']) ? $postedData['user_password'] : '';

        if (!$this->setLoginCredentials($postedData['user_username'], $email, $password, $postedData['user_active'], $postedData['user_verify'])) {
            $db->rollbackTransaction();
            return false;
        }

        if (empty($userPhone) && isset($postedData['user_newsletter_signup']) && $postedData['user_newsletter_signup'] == 1) {
            if (!MailchimpHelper::subscribe(['email' => $email], $this->commonLangId)) {
                $db->rollbackTransaction();
                $this->error = Labels::getLabel("ERR_Newsletter_is_not_configured_yet,_Please_contact_admin", $this->commonLangId);
                return false;
            }
        }

        if ($socialUser == false) {
            if (!$this->saveUserNotifications()) {
                $db->rollbackTransaction();
                $this->error = $db->getError();
                return false;
            }
        }

        if (empty($userPhone) && FatApp::getConfig('CONF_EMAIL_VERIFICATION_REGISTRATION', FatUtility::VAR_INT, 1) && $socialUser == false) {
            if (!$this->userEmailVerification($postedData, $this->commonLangId)) {
                $db->rollbackTransaction();
                $this->error = Labels::getLabel("ERR_ERROR_IN_SENDING_VERIFICATION_EMAIL", $this->commonLangId);
                return false;
            }
        } elseif (!empty($userPhone)) {
            if (!$this->userPhoneVerification($postedData, $this->commonLangId)) {
                $db->rollbackTransaction();
                $this->error = !empty($this->error) ? $this->error : Labels::getLabel("ERR_ERROR_IN_SENDING_VERIFICATION_SMS", $this->commonLangId);
                return false;
            }
            $_SESSION[UserAuthentication::TEMP_SESSION_ELEMENT_NAME]['otpUserId'] = $this->getMainTableRecordId();
        } else {
            if (FatApp::getConfig('CONF_WELCOME_EMAIL_REGISTRATION', FatUtility::VAR_INT, 1)) {
                $link = UrlHelper::generateFullUrl('GuestUser', 'loginForm');
                if (!$this->userWelcomeEmailRegistration($postedData, $link, $this->commonLangId)) {
                    $db->rollbackTransaction();
                    $message = Labels::getLabel("ERR_ERROR_IN_SENDING_WELCOME_EMAIL", $this->siteLangId);
                    return false;
                }
            }
        }

        if (FatApp::getConfig('CONF_NOTIFY_ADMIN_REGISTRATION', FatUtility::VAR_INT, 1)) {
            if (!$this->notifyAdminRegistration($postedData, $this->commonLangId)) {
                $db->rollbackTransaction();
                $this->error = Labels::getLabel("ERR_ERROR_IN_SENDING_NOTIFICATION_EMAIL_TO_ADMIN", $this->commonLangId);
                return false;
            }
        }

        $db->commitTransaction();

        $referrerCodeSignup = '';
        if (isset($_COOKIE['referrer_code_signup']) && $_COOKIE['referrer_code_signup'] != '') {
            $referrerCodeSignup = $_COOKIE['referrer_code_signup'];
        }
        $affiliateReferrerCodeSignup = '';
        if (isset($_COOKIE['affiliate_referrer_code_signup']) && $_COOKIE['affiliate_referrer_code_signup'] != '') {
            $affiliateReferrerCodeSignup = $_COOKIE['affiliate_referrer_code_signup'];
        }

        if (true === MOBILE_APP_API_CALL) {
            $referralToken = array_key_exists('referralToken', $postedData) ? $postedData['referralToken'] : $referrerCodeSignup;
            if (!empty($referralToken)) {
                $userSrchObj = User::getSearchObject();
                $userSrchObj->doNotCalculateRecords();
                $userSrchObj->setPageSize(1);
                $userSrchObj->addCondition('user_referral_code', '=', $referralToken);
                $userSrchObj->addMultipleFields(['user_is_buyer', 'user_is_affiliate']);
                $rs = $userSrchObj->getResultSet();
                $row = FatApp::getDb()->fetch($rs);

                if (!empty($row)) {
                    $referral = serialize(array('data' => $referralToken, 'creation_time' => time()));
                    if (0 < $row['user_is_buyer']) {
                        $referrerCodeSignup = $referral;
                    }

                    if (0 < $row['user_is_affiliate']) {
                        $affiliateReferrerCodeSignup = $referral;
                    }
                }
            }
        }

        if (!empty($email)) {
            if (!$this->assignGiftCard($email)) {
                $db->rollbackTransaction();
                $this->error = Labels::getLabel('MSG_USER_COULD_NOT_BE_SET');
                return false;
            }
        }

        $this->setUpRewardEntry($this->getMainTableRecordId(), $this->commonLangId, $referrerCodeSignup, $affiliateReferrerCodeSignup);
        return true === $returnUserId ? $this->getMainTableRecordId() : true;
    }


    public function validateUserForRegistration($userName, $userEmail, $userPhone = '')
    {
        if (empty($userPhone)) {
            $row = $this->checkUserByEmailOrUserName($userName, $userEmail);
        } else {
            $row = $this->checkUserByPhoneOrUserName($userName, $userPhone);
        }

        if (empty($row)) {
            return true;
        }

        if ($row['credential_username'] == $userName) {
            $this->error = Labels::getLabel('ERR_DUPLICATE_USERNAME', $this->commonLangId);
            return false;
        }
        if (empty($userPhone) && $row['credential_email'] == $userEmail) {
            $this->error = Labels::getLabel('ERR_DUPLICATE_EMAIL', $this->commonLangId);
            return false;
        } elseif (!empty($userPhone) && $row['user_phone_dcode'] . $row['user_phone'] == $userPhone) {
            $this->error = Labels::getLabel('ERR_DUPLICATE_PHONE.', $this->commonLangId);
            if ($row['credential_verified'] == applicationConstants::NO) {
                $this->error .= ' ' . Labels::getLabel('MSG_THIS_PHONE_NUMBER_IS_NOT_VERIFIED_YET._DO_YOU_WANT_TO_CONTINUE?_{CONTINUE-BTN}', $this->commonLangId);
            }
            return false;
        }
        return true;
    }

    public function checkUserByEmailOrUserName($userName, $userEmail)
    {
        $srch = $this->getUserSearchObj(array('user_id', 'credential_email', 'credential_username', 'credential_verified', 'user_deleted'), true, false);
        $condition = $srch->addCondition('credential_username', '=', $userName);
        $condition->attachCondition('credential_email', '=', $userEmail, 'OR');
        $srch->doNotCalculateRecords();
        $rs = $srch->getResultSet();
        return FatApp::getDb()->fetch($rs);
    }

    public function checkUserByPhoneOrUserName($userName, $userPhone)
    {
        $srch = $this->getUserSearchObj(['user_id', 'user_phone_dcode', 'user_phone', 'credential_username', 'credential_verified', 'user_deleted']);
        $condition = $srch->addCondition('credential_username', '=', $userName);
        $condition->attachCondition('mysql_func_CONCAT(user_phone_dcode, user_phone)', '=', $userPhone, 'OR', true);
        $srch->doNotCalculateRecords();
        $rs = $srch->getResultSet();
        $row = FatApp::getDb()->fetch($rs);
        return (is_array($row) ? $row : []);
    }

    public function saveUserNotifications()
    {
        $notificationData = array(
            'notification_record_type' => Notification::TYPE_USER,
            'notification_record_id' => $this->getMainTableRecordId(),
            'notification_user_id' => $this->getMainTableRecordId(),
            'notification_label_key' => Notification::NEW_USER_REGISTERATION_NOTIFICATION,
            'notification_added_on' => date('Y-m-d H:i:s'),
        );

        if (!Notification::saveNotifications($notificationData)) {
            $this->error = FatApp::getDb()->getError();
            return false;
        }
        return true;
    }

    public function getUserByReferrerCode($userReferrerCode)
    {
        $userSrchObj = User::getSearchObject();
        $userSrchObj->doNotCalculateRecords();
        $userSrchObj->doNotLimitRecords();
        $userSrchObj->addCondition('user_referral_code', '=', $userReferrerCode);
        $userSrchObj->addMultipleFields(array('user_id', 'user_referral_code', 'user_name'));
        $rs = $userSrchObj->getResultSet();
        return FatApp::getDb()->fetch($rs);
    }

    public function addReferrerRewardPoints($referrerUserId, $referredUserId)
    {
        $rewardExpiryDate = '0000-00-00';
        $CONF_REGISTRATION_REFERRER_REWARD_POINTS_VALIDITY = FatApp::getConfig("CONF_REGISTRATION_REFERRER_REWARD_POINTS_VALIDITY", FatUtility::VAR_INT, 0);
        if ($CONF_REGISTRATION_REFERRER_REWARD_POINTS_VALIDITY > 0) {
            $rewardExpiryDate = date('Y-m-d', strtotime('+' . $CONF_REGISTRATION_REFERRER_REWARD_POINTS_VALIDITY . ' days'));
        }
        $CONF_REGISTRATION_REFERRER_REWARD_POINTS = FatApp::getConfig("CONF_REGISTRATION_REFERRER_REWARD_POINTS", FatUtility::VAR_INT, 0);

        $rewardsRecord = new UserRewards();
        $referralUserName = User::getAttributesById($referredUserId, "user_name");
        $urpComments = Labels::getLabel("LBL_Signup_Reward_Points._Your_Referral_{username}_registered.", CommonHelper::getLangId());
        $urpComments = str_replace("{username}", $referralUserName, $urpComments);
        $rewardsRecord->assignValues(
            array(
                'urp_user_id' => $referrerUserId,
                'urp_referral_user_id' => $referredUserId,
                'urp_points' => $CONF_REGISTRATION_REFERRER_REWARD_POINTS,
                'urp_comments' => $urpComments,
                'urp_used' => 0,
                'urp_date_expiry' => $rewardExpiryDate
            )
        );
        if ($rewardsRecord->save()) {
            $urpId = $rewardsRecord->getMainTableRecordId();
            $emailObj = new EmailHandler();
            $emailObj->sendRewardPointsNotification(CommonHelper::getLangId(), $urpId);
        } else {
            $this->error = $rewardsRecord->getError();
        }
        $where = array('smt' => 'user_id = ?', 'vals' => array($referrerUserId));
        FatApp::getDb()->updateFromArray(static::DB_TBL, array('user_updated_on' => date('Y-m-d  H:i:s')), $where);
    }

    /**
     * validateUser - Used in case of social login.
     *
     * @param  string $email
     * @param  string $username
     * @param  string $socialAccountId
     * @param  string $keyName
     * @param  string $userType
     * @param  string $referralToken
     * @return mixed
     */
    public function validateUser($email, $username, $socialAccountId, $keyName, $userType, $referralToken = '')
    {
        $db = FatApp::getDb();
        $socialIdColumn = strtolower($keyName) . '_account_id';

        $attr = static::USER_INFO_ATTR;
        $attr[] = 'usermeta_value as ' . $socialIdColumn;

        $srch = $this->getUserSearchObj($attr);
        $srch->joinTable(static::DB_TBL_META, 'LEFT OUTER JOIN', 't_um.' . static::DB_TBL_META_PREFIX . 'user_id = u.user_id', 't_um');

        $srch->addDirectCondition('(credential_email = "' . $email . '" OR (usermeta_key = "' . strtolower($keyName) . '_account_id" AND usermeta_value = "' . $socialAccountId . '"))');

        $srch->doNotCalculateRecords();
        $srch->setPageSize(1);
        $rs = $srch->getResultSet();

        $row = $db->fetch($rs);

        if (isset($row['user_active']) && $row['user_active'] != applicationConstants::ACTIVE) {
            $this->error = Labels::getLabel('ERR_YOUR_ACCOUNT_HAS_BEEN_DEACTIVATED', $this->commonLangId);
            return false;
        }

        if (isset($row['user_active']) && $row['user_deleted'] == applicationConstants::YES) {
            $this->error = Labels::getLabel("ERR_USER_INACTIVE_OR_DELETED", $this->commonLangId);
            return false;
        }

        if (!empty($row)) {
            if (0 < $userType) {
                $userTypeArr = [
                    static::USER_TYPE_BUYER => 'user_is_buyer',
                    static::USER_TYPE_SELLER => 'user_is_supplier',
                    static::USER_TYPE_ADVERTISER => 'user_is_advertiser',
                    static::USER_TYPE_AFFILIATE => 'user_is_affiliate',
                ];

                $invalidUser = false;
                if (in_array($userType, array_keys($userTypeArr)) && $row[$userTypeArr[$userType]] == applicationConstants::NO) {
                    $invalidUser = true;
                } elseif (!in_array($userType, array_keys($userTypeArr)) && $row['user_registered_initially_for'] != $userType) {
                    $invalidUser = true;
                }

                if ($invalidUser) {
                    $this->error = Labels::getLabel('ERR_Invalid_User', $this->commonLangId);
                    return false;
                }
            }

            $userObj = new User($row['user_id']);
            if (false === $userObj->updateUserMeta($socialIdColumn, $socialAccountId)) {
                return false;
            }

            if (empty($row['credential_email']) || applicationConstants::YES > $row['credential_verified']) {
                $assignValues = [
                    static::DB_TBL_CRED_PREFIX . 'user_id' => $row['user_id'],
                    static::DB_TBL_CRED_PREFIX . 'email' => $email,
                ];
                if (!empty($socialAccountId)) {
                    $assignValues[static::DB_TBL_CRED_PREFIX . 'verified'] = applicationConstants::YES;
                }

                if (!FatApp::getDb()->insertFromArray(static::DB_TBL_CRED, $assignValues, false, array(), $assignValues)) {
                    $this->error = FatApp::getDb()->getError();
                    return false;
                }
            }

            unset($row[$socialIdColumn]);
        } else {
            $userId = $this->setupUser($email, $username, $socialAccountId, $keyName, $userType, $referralToken);
            if (false === $userId) {
                return false;
            }

            if (($key = array_search('usermeta_value as ' . $socialIdColumn, $attr)) !== false) {
                unset($attr[$key]);
            }

            if (!$row = $this->getUserInfo($attr)) {
                $this->error = Labels::getLabel("ERR_USER_COULD_NOT_BE_SET", $this->commonLangId);
                return false;
            }
        }

        if (!empty($socialAccountId)) {
            $this->loginWithSocialAccount = true;
        }

        if (!$this->doLogin($row['credential_username'], $row['credential_password'])) {
            return false;
        }
        unset($row['credential_password']);
        return $row;
    }

    public function setupUser($email, $username, $socialAccountId, $keyName, $userType, $referralToken = '')
    {
        $db = FatApp::getDb();
        $socialIdColumn = strtolower($keyName) . '_account_id';

        $isApprovedForSupplier = FatApp::getConfig("CONF_ADMIN_APPROVAL_SUPPLIER_REGISTRATION", FatUtility::VAR_INT, 1);
        $isActivateSeparateSignUp = FatApp::getConfig("CONF_ACTIVATE_SEPARATE_SIGNUP_FORM", FatUtility::VAR_INT, 1);

        $user_is_advertiser = (0 < $isApprovedForSupplier || 0 < $isActivateSeparateSignUp) ? 0 : 1;
        $user_is_buyer = 0;
        $user_is_supplier = 0;

        if (isset($userType) && $userType == static::USER_TYPE_BUYER) {
            $userPreferredDashboard = static::USER_BUYER_DASHBOARD;
            $user_registered_initially_for = static::USER_TYPE_BUYER;
            $user_is_buyer = 1;
            $user_is_supplier = (FatApp::getConfig("CONF_ADMIN_APPROVAL_SUPPLIER_REGISTRATION", FatUtility::VAR_INT, 1) || FatApp::getConfig("CONF_ACTIVATE_SEPARATE_SIGNUP_FORM", FatUtility::VAR_INT, 1)) ? 0 : 1;
        }
        if (isset($userType) && $userType == static::USER_TYPE_SELLER) {
            $userPreferredDashboard = static::USER_SELLER_DASHBOARD;
            $user_registered_initially_for = static::USER_TYPE_SELLER;
            $user_is_supplier = 1;
        }

        $db->startTransaction();

        $userData = [
            'user_name' => $username,
            'user_is_buyer' => $user_is_buyer,
            'user_is_supplier' => $user_is_supplier,
            'user_is_advertiser' => $user_is_advertiser,
            'user_preferred_dashboard' => $userPreferredDashboard,
            'user_registered_initially_for' => $user_registered_initially_for
        ];

        $this->assignValues($userData);
        if (!$this->save()) {
            $this->error = Labels::getLabel("ERR_USER_COULD_NOT_BE_SET", $this->commonLangId) . $this->getError();
            return false;
        }
        $userId = $this->getMainTableRecordId();
        $this->updateUserMeta($socialIdColumn, $socialAccountId);
        if (!$this->setLoginCredentials($username, $email, uniqid(), 1, 1)) {
            $this->error = Labels::getLabel("ERR_LOGIN_CREDENTIALS_COULD_NOT_BE_SET", $this->commonLangId) . $this->getError();
            $db->rollbackTransaction();
            return false;
        }

        $userData['user_username'] = $username;
        $userData['user_email'] = $email;

        $uData = self::getAttributesById($userId, ['user_phone_dcode', 'user_phone']);
        $userData = array_merge($userData, $uData);

        if (FatApp::getConfig('CONF_NOTIFY_ADMIN_REGISTRATION', FatUtility::VAR_INT, 1)) {
            if (!$this->notifyAdminRegistration($userData, $this->commonLangId)) {
                $this->error = Labels::getLabel("ERR_NOTIFICATION_EMAIL_COULD_NOT_BE_SENT", $this->commonLangId);
                $db->rollbackTransaction();
                return false;
            }
        }

        if (FatApp::getConfig('CONF_WELCOME_EMAIL_REGISTRATION', FatUtility::VAR_INT, 1) && $email) {
            $data['user_email'] = $email;
            $data['user_name'] = $username;

            //ToDO::Change login link to contact us link
            $link = UrlHelper::generateFullUrl('GuestUser', 'loginForm');
            $data = array_merge($data, $uData);

            if (!$this->userWelcomeEmailRegistration($data, $link, $this->commonLangId)) {
                $this->error = Labels::getLabel("ERR_WELCOME_EMAIL_COULD_NOT_BE_SENT", $this->commonLangId);
                $db->rollbackTransaction();
                return false;
            }
        }

        $db->commitTransaction();

        $referrerCodeSignup = '';
        if (isset($_COOKIE['referrer_code_signup']) && $_COOKIE['referrer_code_signup'] != '') {
            $referrerCodeSignup = $_COOKIE['referrer_code_signup'];
        }
        $affiliateReferrerCodeSignup = '';
        if (isset($_COOKIE['affiliate_referrer_code_signup']) && $_COOKIE['affiliate_referrer_code_signup'] != '') {
            $affiliateReferrerCodeSignup = $_COOKIE['affiliate_referrer_code_signup'];
        }

        if (true === MOBILE_APP_API_CALL && !empty($referralToken)) {
            $userSrchObj = User::getSearchObject();
            $userSrchObj->doNotCalculateRecords();
            $userSrchObj->setPageSize(1);
            $userSrchObj->addCondition('user_referral_code', '=', $referralToken);
            $userSrchObj->addMultipleFields(['user_is_buyer', 'user_is_affiliate']);
            $rs = $userSrchObj->getResultSet();
            $row = FatApp::getDb()->fetch($rs);

            if (!empty($row)) {
                $referral = serialize(array('data' => $referralToken, 'creation_time' => time()));
                if (0 < $row['user_is_buyer']) {
                    $referrerCodeSignup = $referral;
                }

                if (0 < $row['user_is_affiliate']) {
                    $affiliateReferrerCodeSignup = $referral;
                }
            }
        }

        $this->setUpRewardEntry($this->getMainTableRecordId(), $this->commonLangId, $referrerCodeSignup, $affiliateReferrerCodeSignup);

        return $userId;
    }

    private function doLogin($username, $password)
    {
        $authentication = new UserAuthentication();
        $authentication->loginWithOtp = $this->loginWithOtp;
        $authentication->loginWithSocialAccount = $this->loginWithSocialAccount;
        $remoteAddress = $_SERVER['REMOTE_ADDR'];
        if (!$authentication->login($username, $password, $remoteAddress, false)) {
            $this->error = Labels::getLabel($authentication->getError(), $this->commonLangId);
            return false;
        }
        return true;
    }

    public function addReferralRewardPoints($referredUserId, $referrerUserId, $referrerUserName)
    {
        $rewardReferralExpiryDate = '0000-00-00';
        $CONF_REGISTRATION_REFERRAL_REWARD_POINTS_VALIDITY = FatApp::getConfig("CONF_REGISTRATION_REFERRAL_REWARD_POINTS_VALIDITY", FatUtility::VAR_INT, 0);
        if ($CONF_REGISTRATION_REFERRAL_REWARD_POINTS_VALIDITY > 0) {
            $rewardReferralExpiryDate = date('Y-m-d', strtotime('+' . $CONF_REGISTRATION_REFERRAL_REWARD_POINTS_VALIDITY . ' days'));
        }
        $CONF_REGISTRATION_REFERRAL_REWARD_POINTS = FatApp::getConfig("CONF_REGISTRATION_REFERRAL_REWARD_POINTS", FatUtility::VAR_INT, 0);

        $rewardsRecord = new UserRewards();
        $urpComments = Labels::getLabel("LBL_Signup_Reward_Points._Registered_through_referral_link_of_your_friend_{referrerusername}.", $this->commonLangId);
        $urpComments = str_replace("{referrerusername}", $referrerUserName, $urpComments);
        $rewardsRecord->assignValues(
            array(
                'urp_user_id' => $referredUserId,
                'urp_referral_user_id' => $referrerUserId,
                'urp_points' => $CONF_REGISTRATION_REFERRAL_REWARD_POINTS,
                'urp_comments' => $urpComments,
                'urp_used' => 0,
                'urp_date_expiry' => $rewardReferralExpiryDate
            )
        );
        if ($rewardsRecord->save()) {
            $urpId = $rewardsRecord->getMainTableRecordId();
            $emailObj = new EmailHandler();
            $emailObj->sendRewardPointsNotification(CommonHelper::getLangId(), $urpId);
        } else {
            $this->error = $rewardsRecord->getError();
        }
    }

    public static function getSubUsers($userId, $attr = null)
    {
        $srch = User::getSearchObject(true, $userId);
        $srch->addCondition('credential_active', '=', applicationConstants::ACTIVE);
        if (null != $attr) {
            if (is_array($attr)) {
                $srch->addMultipleFields($attr);
            } elseif (is_string($attr)) {
                $srch->addFld($attr);
            }
        } else {
            $srch->addMultipleFields(array('user_id', 'user_name', 'user_phone_dcode', 'user_phone', 'credential_email'));
        }
        $srch->doNotCalculateRecords();
        $rs = $srch->getResultSet();
        return FatApp::getDb()->fetchAll($rs);
    }

    public static function getSubUsersReceipents($userId, $privilegeFunction, $type = false)
    {
        $usersArr = static::getSubUsers($userId);
        $userPrivilege = UserPrivilege::getInstance();
        $phoneNumbers = array();
        $bccEmails = array();
        foreach ($usersArr as $user) {
            if ($userPrivilege->$privilegeFunction($user['user_id'], true)) {
                $phoneNumbers[] = !empty($user['user_phone']) ? ValidateElement::formatDialCode($user['user_phone_dcode']) . $user['user_phone'] : '';
                $bccEmails[$user['credential_email']] = $user['user_name'];
            }
        }

        switch (strtolower($type)) {
            case 'email':
                return $bccEmails;
                break;
            case 'phone':
                return $phoneNumbers;
                break;
            default:
                return [
                    'email' => $bccEmails,
                    'phone' => $phoneNumbers
                ];
                break;
        }
    }

    public function getSellerData($langId, $attr = null)
    {
        if (($this->mainTableRecordId < 1)) {
            $this->error = Labels::getLabel('ERR_INVALID_REQUEST_USER_NOT_INITIALIZED', $this->commonLangId);
            return false;
        }
        $srch = $this->getUserSearchObj($attr);
        $srch->joinTable(static::DB_TBL_CRED, 'LEFT OUTER JOIN', 'uc.' . static::DB_TBL_CRED_PREFIX . 'user_id = u.user_id', 'uc');
        $srch->joinTable(Shop::DB_TBL, 'LEFT OUTER JOIN', 'shop_user_id = if(u.user_parent > 0, user_parent, u.user_id)', 'shop');
        $srch->joinTable(Shop::DB_TBL_LANG, 'LEFT OUTER JOIN', 'shop.shop_id = s_l.shoplang_shop_id AND shoplang_lang_id = ' . $langId, 's_l');
        $srch->addCondition('uc.' . static::DB_TBL_CRED_PREFIX . 'active', '=', applicationConstants::ACTIVE);
        $srch->addCondition('uc.' . static::DB_TBL_CRED_PREFIX . 'verified', '=', applicationConstants::YES);
        $srch->doNotCalculateRecords();
        $rs = $srch->getResultSet();
        $record = FatApp::getDb()->fetch($rs);

        if (!empty($record)) {
            return $record;
        }
        return false;
    }

    public static function getAuthenticUserIds($userId, $parentId = 0, $active = false)
    {
        $userId = FatUtility::int($userId);
        $parentId = FatUtility::int($parentId);

        $srch = new SearchBase(User::DB_TBL, 'u');
        $srch->joinTable(Shop::DB_TBL, 'LEFT OUTER JOIN', 'shop_user_id = if(u.user_parent > 0, user_parent, u.user_id)', 'shop');

        if ($userId != $parentId) {
            $srch->addDirectCondition('(user_id = ' . $userId . ' or user_parent = ' . $userId . ')');
        } else {
            $srch->addDirectCondition('(user_id = ' . $userId . ' or user_parent = ' . $parentId . ')');
        }
        if (true == $active) {
            $srch->joinTable(static::DB_TBL_CRED, 'LEFT OUTER JOIN', 'uc.' . static::DB_TBL_CRED_PREFIX . 'user_id = u.user_id', 'uc');
            $srch->addCondition('uc.' . static::DB_TBL_CRED_PREFIX . 'active', '=', applicationConstants::ACTIVE);
            $srch->addCondition('uc.' . static::DB_TBL_CRED_PREFIX . 'verified', '=', applicationConstants::YES);
        }
        $srch->doNotCalculateRecords();
        $srch->doNotLimitRecords();
        $srch->addMultipleFields(array('user_id', 'shop_id'));
        $record = FatApp::getDb()->fetchAllAssoc($srch->getResultSet());
        return array_keys($record);
    }

    public static function getParentAndTheirChildIds($userId, $active = false, $isParentId = false)
    {
        $userId = FatUtility::int($userId);
        if (false == $isParentId) {
            $parent = User::getAttributesById($userId, 'user_parent');
            if (0 < $parent) {
                $userId = $parent;
            }
        }
        $srch = new SearchBase(User::DB_TBL, 'u');
        $srch->joinTable(Shop::DB_TBL, 'LEFT OUTER JOIN', 'shop_user_id = if(u.user_parent > 0, user_parent, u.user_id)', 'shop');
        $srch->addDirectCondition('(user_id = ' . $userId . ' or user_parent = ' . $userId . ')');
        if (true == $active) {
            $srch->joinTable(static::DB_TBL_CRED, 'LEFT OUTER JOIN', 'uc.' . static::DB_TBL_CRED_PREFIX . 'user_id = u.user_id', 'uc');
            $srch->addCondition('uc.' . static::DB_TBL_CRED_PREFIX . 'active', '=', applicationConstants::ACTIVE);
            $srch->addCondition('uc.' . static::DB_TBL_CRED_PREFIX . 'verified', '=', applicationConstants::YES);
        }
        $srch->doNotCalculateRecords();
        $srch->doNotLimitRecords();
        $srch->addMultipleFields(array('user_id', 'shop_id'));
        $rs = $srch->getResultSet();
        $record = FatApp::getDb()->fetchAllAssoc($rs);
        return array_keys($record);
    }

    public static function deleteUserMeta(string $keyValue, &$error = '')
    {
        if (!FatApp::getDb()->deleteRecords(static::DB_TBL_META, array('smt' => 'usermeta_key = ?', 'vals' => array($keyValue)))) {
            $error = FatApp::getDb()->getError();
            return false;
        }
        return true;
    }

    public function saveUserCookiesPreferences($statisticalCookies, $personaliseCookies)
    {
        if (1 > $this->mainTableRecordId) {
            $this->error = Labels::getLabel('ERR_INVALID_REQUEST_USER_NOT_INITIALIZED', $this->commonLangId);
            return false;
        }

        $data = [
            'ucp_user_id' => $this->mainTableRecordId,
            'ucp_statistical' => $statisticalCookies,
            'ucp_personalized' => $personaliseCookies,
        ];

        $record = new TableRecord(static::DB_TBL_USR_COOKIES_PREFERENCES);
        $record->assignValues($data);
        if (!$record->addNew(array(), $data)) {
            $this->error = $record->getError();
            return false;
        }
        return true;
    }

    public static function getUserMetaDetail(string $col, string $value = '')
    {
        $srch = new SearchBase(static::DB_TBL_META, 't_um');
        $srch->addCondition(static::DB_TBL_META_PREFIX . 'key', '=', $col);
        if (!empty($value)) {
            $srch->addCondition(static::DB_TBL_META_PREFIX . 'value', '=', $value);
        }
        $srch->doNotCalculateRecords();
        $rs = $srch->getResultSet();
        return FatApp::getDb()->fetchAll($rs);
    }

    public function getUserSelectedCookies()
    {
        if (1 > $this->mainTableRecordId) {
            $this->error = Labels::getLabel('ERR_INVALID_REQUEST_USER_NOT_INITIALIZED', $this->commonLangId);
            return false;
        }

        $srch = new SearchBase(static::DB_TBL_USR_COOKIES_PREFERENCES, 'ucp');
        $srch->addCondition('ucp_user_id', '=', $this->mainTableRecordId);
        $srch->addMultipleFields(['ucp_user_id', 'ucp_statistical', 'ucp_personalized']);
        $srch->doNotCalculateRecords();
        $srch->setPageSize(1);
        $rs = $srch->getResultSet();
        return FatApp::getDb()->fetch($rs);
    }

    public function updateCookiesPreferences($data)
    {
        if (1 > $this->mainTableRecordId) {
            $this->error = Labels::getLabel('ERR_INVALID_REQUEST_USER_NOT_INITIALIZED', $this->commonLangId);
            return false;
        }

        $data['ucp_user_id'] = $this->mainTableRecordId;
        if (!FatApp::getDb()->insertFromArray(static::DB_TBL_USR_COOKIES_PREFERENCES, $data, false, array(), $data)) {
            $this->error = FatApp::getDb()->getError();
            return false;
        }
        return true;
    }

    public static function checkStatisticalCookiesEnabled()
    {
        if (!FatApp::getConfig('CONF_ENABLE_COOKIES', FatUtility::VAR_INT, 1)) {
            return true;
        }
        $userId = UserAuthentication::getLoggedUserId(true);
        if ($userId > 0) {
            $user = new User($userId);
            $userSelectedCookies = $user->getUserSelectedCookies();
            return (!empty($userSelectedCookies) && $userSelectedCookies['ucp_statistical'] == 1) ? true : false;
        } else {
            return (isset($_COOKIE['ykStatisticalCookies']) && 1 == $_COOKIE['ykStatisticalCookies']) ? true : false;
        }
    }

    public static function checkPersonalizedCookiesEnabled()
    {
        if (!FatApp::getConfig('CONF_ENABLE_COOKIES', FatUtility::VAR_INT, 1)) {
            return true;
        }
        $userId = UserAuthentication::getLoggedUserId(true);
        if ($userId > 0) {
            $user = new User($userId);
            $userSelectedCookies = $user->getUserSelectedCookies();
            return (!empty($userSelectedCookies) && $userSelectedCookies['ucp_personalized'] == 1) ? true : false;
        } else {
            return (isset($_COOKIE['ykPersonaliseCookies']) && 1 == $_COOKIE['ykPersonaliseCookies']) ? true : false;
        }
    }

    public static function getFirstName(string $name): string
    {
        return (false !== strpos($name, ' ') ? (explode(' ', $name))[0] : $name);
    }

    public static function getLastName(string $name): string
    {
        return (false !== strpos($name, ' ') ? (explode(' ', $name))[1] : $name);
    }

    public static function getCredentialName(int $userId): string
    {
        if (isset($_SESSION[UserAuthentication::SESSION_ELEMENT_NAME]['credential_username'])  && !empty($_SESSION[UserAuthentication::SESSION_ELEMENT_NAME]['credential_username'])) {
            return $_SESSION[UserAuthentication::SESSION_ELEMENT_NAME]['credential_username'];
        }

        $srch = new SearchBase(static::DB_TBL_CRED, 'uc');
        $srch->addFld('credential_username');
        $srch->addCondition('credential_user_id', '=', $userId);
        $srch->doNotCalculateRecords();
        $srch->setPageSize(1);
        $result = FatApp::getDb()->fetch($srch->getResultSet());
        if ($result) {
            return $_SESSION[UserAuthentication::SESSION_ELEMENT_NAME]['credential_username'] = $result['credential_username'];
        }
        return '';
    }

    public function updateShopValidUser()
    {
        if (1 > $this->mainTableRecordId) {
            return;
        }
        FatApp::getDb()->query("UPDATE tbl_shops SET shop_user_valid = 1 WHERE shop_user_id = ( SELECT u.user_id FROM tbl_users u INNER JOIN tbl_user_credentials c ON u.user_id = c.credential_user_id WHERE u.user_id = " . $this->mainTableRecordId . " AND u.user_is_supplier = 1 AND u.user_deleted = 0 AND c.credential_active = 1 AND c.credential_verified = 1 LIMIT 1 )");
    }
    
    /* Get By Email
     * 
     * @param string $email
     * @return null|array
     */
    public static function getByEmail(string $email)
    {
        $srch = new SearchBase(User::DB_TBL, 'u');
        $srch->joinTable(static::DB_TBL_CRED, 'LEFT OUTER JOIN', 'uc.' . static::DB_TBL_CRED_PREFIX . 'user_id = u.user_id', 'uc');
        $srch->addMultipleFields([
            'u.user_id as user_id',
            'user_name',
            'credential_email',
            'user_deleted'
        ]);
        $srch->addCondition('credential_email', '=', $email);
        //  $srch->addCondition('user_deleted', '=', applicationConstants::NO);
        $srch->doNotCalculateRecords();
        return FatApp::getDb()->fetch($srch->getResultSet());
    }

    public function assignGiftCard(string $email): bool
    {
        if (!FatApp::getDb()->updateFromArray(GiftCards::DB_TBL, ['ogcards_receiver_id' => $this->getMainTableRecordId()], ['smt' => 'ogcards_receiver_email = ?', 'vals' => [$email]])) {
            $this->error = FatApp::getDb()->getError();
            return false;
        }
        return true;
    }
    
    /**
     * getUserParentId //if parent id is zero it will give the current user id.
     *
     * @param  mixed $userId
     * @param  mixed $active
     * @return void
     */
    public static function getUserParentId(int $userId, $active = true)
    {
        $userId = FatUtility::int($userId);
        $srch = new SearchBase(User::DB_TBL, 'u');
        $srch->addCondition('user_id', '=', $userId);

        if (true == $active) {
            $srch->joinTable(static::DB_TBL_CRED, 'LEFT OUTER JOIN', 'uc.' . static::DB_TBL_CRED_PREFIX . 'user_id = u.user_id', 'uc');
            $srch->addCondition('uc.' . static::DB_TBL_CRED_PREFIX . 'active', '=', applicationConstants::ACTIVE);
            $srch->addCondition('uc.' . static::DB_TBL_CRED_PREFIX . 'verified', '=', applicationConstants::YES);
        }
        $srch->addFld('if(u.user_parent > 0, u.user_parent, u.user_id) as user_parent');
        $record = FatApp::getDb()->fetch($srch->getResultSet());
        return $record['user_parent'];
    }
}
