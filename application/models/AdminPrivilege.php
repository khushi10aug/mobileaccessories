<?php

class AdminPrivilege
{
    public const SESSION_ELEMENT_NAME = 'yokartPrivileges';

    public const SECTION_PRODUCT_CATEGORIES = 1;
    public const SECTION_PRODUCTS = 2;
    public const SECTION_BRANDS = 3;
    public const SECTION_FILTER_GROUPS = 4;
    public const SECTION_FILTERS = 5;
    public const SECTION_ATTRIBUTES = 6;
    public const SECTION_TAGS = 7;
    public const SECTION_OPTIONS = 8;
    public const SECTION_EXTRA_ATTRIBUTES = 9;
    public const SECTION_GENERAL_SETTINGS = 10;
    public const SECTION_USERS = 11;
    public const SECTION_SUPPLIER_APPROVAL_FORM = 12;
    public const SECTION_SUPPLIER_APPROVAL_REQUESTS = 13;
    public const SECTION_SHOPS = 14;
    public const SECTION_PAYMENT_METHODS = 15;
    public const SECTION_CONTENT_BLOCKS = 16;
    public const SECTION_SHIPPING_DURATIONS = 17;
    public const SECTION_MANUAL_SHIPPING_API = 18;
    public const SECTION_LANGUAGE_LABELS = 19;
    public const SECTION_CURRENCY_MANAGEMENT = 20;
    public const SECTION_CONTENT_PAGES = 21;
    public const SECTION_EMPTY_CART_ITEMS_MANAGEMENT = 22;
    public const SECTION_NAVIGATION_MANAGEMENT = 23;
    public const SECTION_CATALOG_REQUESTS = 24;
    public const SECTION_SHIPPING_APIS = 25;
    public const SECTION_COMMISSION = 26;
    public const SECTION_ORDERS = 27;
    public const SECTION_VENDOR_ORDERS = 28;
    public const SECTION_WITHDRAW_REQUESTS = 29;
    public const SECTION_ORDER_CANCELLATION_REQUESTS = 30;
    public const SECTION_ORDER_RETURN_REQUESTS = 31;
    public const SECTION_TAX = 32;
    public const SECTION_SLIDES = 33;
    public const SECTION_COUNTRIES = 34;
    public const SECTION_ZONES = 35;
    public const SECTION_STATES = 36;
    public const SECTION_EMAIL_TEMPLATES = 37;
    public const SECTION_ADMIN_USERS = 38;
    public const SECTION_BANNERS = 39;
    public const SECTION_SOCIALPLATFORM = 40;
    public const SECTION_COLLECTIONS = 41;
    public const SECTION_HOME_PAGE_ELEMENTS = 42;
    public const SECTION_SHOP_REPORT_REASONS = 43;
    public const SECTION_SHOP_REPORTS = 44;
    public const SECTION_ORDER_CANCEL_REASONS = 45;
    public const SECTION_ORDER_RETURN_REASONS = 46;
    public const SECTION_META_TAGS = 47;
    public const SECTION_ADMIN_DASHBOARD = 48;
    public const SECTION_FAQ_CATEGORY = 49;
    public const SECTION_FAQ = 50;
    public const SECTION_URL_REWRITE = 51;
    public const SECTION_TESTIMONIAL = 52;
    public const SECTION_SUCCESS_STORIES = 53;
    public const SMART_RECOMENDED_WEIGHTAGES = 54;
    public const SMART_PRODUCT_TAG_PRODUCTS = 55;
    public const SECTION_ADMIN_PERMISSIONS = 56;
    public const SECTION_BLOG_POST_CATEGORIES = 57;
    public const SECTION_BLOG_POSTS = 58;
    public const SECTION_DISCOUNT_COUPONS = 59;
    public const SECTION_BLOG_CONTRIBUTIONS = 60;
    public const SECTION_BLOG_COMMENTS = 61;
    public const SECTION_SELLER_PRODUCTS = 62;
    public const SECTION_PRODUCT_REVIEWS = 63;
    public const SECTION_ABUSIVE_WORDS = 64;
    public const SECTION_QUESTION_BANKS = 65;
    public const SECTION_MESSAGES = 66;
    public const SECTION_POLLING = 67;
    public const SECTION_QUESTIONS = 68;
    public const SECTION_QUESTIONNAIRES = 69;
    public const SECTION_SALES_REPORT = 70;
    public const SECTION_USERS_REPORT = 71;
    public const SECTION_PRODUCTS_REPORT = 72;
    public const SECTION_SHOPS_REPORT = 73;
    public const SECTION_TAX_REPORT = 74;
    public const SECTION_COMMISSION_REPORT = 75;
    public const SECTION_CATALOG_REPORT = 76;
    public const SECTION_PERFORMANCE_REPORT = 77;
    public const SECTION_POLICY_POINTS = 78;
    public const SECTION_SELLER_PACKAGES = 79;
    public const SECTION_SELLER_DISCOUNT_COUPONS = 80;
    public const SECTION_TOOLS = 81;
    public const SECTION_THEME_COLOR = 82;
    public const SECTION_SUBSCRIPTION_ORDERS = 83;
    public const SECTION_AFFILIATE_COMMISSION = 84;
    public const SECTION_PROMOTIONS = 85;
    public const SECTION_AFFILIATES_REPORT = 86;
    public const SECTION_ADVERTISERS_REPORT = 87;
    public const SECTION_BRAND_REQUESTS = 88;
    public const SECTION_SHIPPING_COMPANY_USERS = 89;
    public const SECTION_REWARDS_ON_PURCHASE = 90;
    public const SECTION_LANGUAGE = 91;
    public const SECTION_ORDER_STATUS = 92;
    public const SECTION_NOTIFICATION = 93;
    public const SECTION_TOOLTIP = 94;
    public const SECTION_CUSTOM_PRODUCT_REQUESTS = 95;
    public const SECTION_CUSTOM_CATALOG_PRODUCT_REQUESTS = 96;
    public const SECTION_DATABASE_BACKUP = 96;
    public const SECTION_USER_REQUESTS = 97;
    public const SECTION_PRODUCT_TEMP_IMAGES = 98;
    public const SECTION_IMPORT_INSTRUCTIONS = 99;
    public const SECTION_UPLOAD_BULK_IMAGES = 100;
    public const SECTION_SITEMAP = 101;
    public const SECTION_PLUGINS = 102;
    public const SECTION_ABANDONED_CART = 103;
    public const SECTION_PUSH_NOTIFICATION = 104;
    public const SECTION_PRODUCT_ADVERTISEMENT = 105;
    public const SECTION_IMPORT_EXPORT = 106;
    // public const SECTION_APP_THEME_SETTINGS = 107;
    public const SECTION_PATCH_UPDATE = 109;
    public const SECTION_SMS_TEMPLATE = 108;
    public const SECTION_SHIPPING_PACKAGES = 109;
    public const SECTION_SHIPPING_MANAGEMENT = 110;
    public const SECTION_IMAGE_ATTRIBUTES = 111;
    public const SECTION_PICKUP_ADDRESSES = 112;
    public const SECTION_RATING_TYPES = 113;
    public const SECTION_SHIPPED_PRODUCTS_LISTING = 114;
    public const SECTION_BUYERS_REPORT = 115;
    public const SECTION_SELLERS_REPORT = 116;
    public const SECTION_SUBSCRIPTION_REPORT = 117;
    public const SECTION_FINANCIAL_REPORT = 118;
    public const SECTION_ORDERS_REPORT = 119;
    public const SECTION_BADGES = 120;
    public const SECTION_BADGE_LINKS = 121;
    public const SECTION_BADGE_REQUESTS = 122;
    public const SECTION_SYSTEMLOG = 123;
    public const SECTION_SETTINGS = 124;
    public const SECTION_PAGES_LANGUAGE_DATA = 125;
    public const SECTION_CATEGORY_REQUEST = 126;
    public const SECTION_GETTING_STARTED = 127;
    public const SECTION_REQUEST_FOR_QUOTE = 128;
    public const SECTION_RFQ_OFFERS = 129;
    public const SECTION_APP_RELEASE = 130;
    public const SECTION_ADMIN_LOGIN_HISTORY = 131;

    public const PRIVILEGE_NONE = 0;
    public const PRIVILEGE_READ = 1;
    public const PRIVILEGE_WRITE = 2;

    private static $instance = null;
    private $loadedPermissions = array();

    public static function getInstance()
    {
        if (self::$instance == null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public static function isAdminSuperAdmin($adminId)
    {
        return (1 == $adminId);
    }

    public static function getPermissionArr(): array
    {
        $langId = CommonHelper::getLangId();
        $arr = array(
            static::PRIVILEGE_NONE => Labels::getLabel('LBL_NONE', $langId),
            static::PRIVILEGE_READ => Labels::getLabel('LBL_READ_ONLY', $langId),
            static::PRIVILEGE_WRITE => Labels::getLabel('LBL_READ_AND_WRITE', $langId)
        );
        return $arr;
    }

    public static function getPermissionModulesArr(): array
    {
        $langId = CommonHelper::getLangId();
        $arr = CacheHelper::get('permissionLabels' . $langId, CONF_DEF_CACHE_TIME, '.txt');
        if (!$arr) {
            $arr = array(
                static::SECTION_ADMIN_DASHBOARD => Labels::getLabel('NAV_ADMIN_DASHBOARD', $langId),
                static::SECTION_SHOPS => Labels::getLabel('NAV_SHOPS', $langId),
                static::SECTION_PRODUCT_CATEGORIES => Labels::getLabel('NAV_PRODUCT_CATEGORIES', $langId),
                static::SECTION_PRODUCTS => Labels::getLabel('NAV_PRODUCTS', $langId),
                static::SECTION_SELLER_PRODUCTS => Labels::getLabel('NAV_SELLER_PRODUCTS', $langId),
                static::SECTION_PRODUCT_REVIEWS => Labels::getLabel('NAV_PRODUCT_REVIEWS', $langId),
                static::SECTION_BRANDS => Labels::getLabel('NAV_BRANDS', $langId),
                static::SECTION_OPTIONS => Labels::getLabel('NAV_OPTIONS', $langId),
                static::SECTION_TAGS => Labels::getLabel('NAV_TAGS', $langId),
                static::SECTION_BRAND_REQUESTS => Labels::getLabel('NAV_BRAND_REQUESTS', $langId),
                static::SECTION_ATTRIBUTES => Labels::getLabel('NAV_ATTRIBUTES', $langId),

                static::SECTION_USERS => Labels::getLabel('NAV_USERS', $langId),
                static::SECTION_SHIPPING_COMPANY_USERS => Labels::getLabel('NAV_SHIPPING_COMPANY_USERS', $langId),
                static::SECTION_SUPPLIER_APPROVAL_FORM => Labels::getLabel('NAV_SELLER_APPROVAL_FORM', $langId),
                static::SECTION_SUPPLIER_APPROVAL_REQUESTS => Labels::getLabel('NAV_SELLER_APPROVAL_REQUESTS', $langId),
                static::SECTION_CATALOG_REQUESTS => Labels::getLabel('NAV_CATALOG_REQUESTS', $langId),
                static::SECTION_CUSTOM_PRODUCT_REQUESTS => Labels::getLabel('NAV_CUSTOM_CATALOG_REQUESTS', $langId),
                static::SECTION_CUSTOM_CATALOG_PRODUCT_REQUESTS => Labels::getLabel('NAV_CUSTOM_CATALOG_PRODUCT_REQUESTS', $langId),
                static::SECTION_CATEGORY_REQUEST => Labels::getLabel('NAV_CATEGORY_REQUEST', $langId),

                static::SECTION_CONTENT_PAGES => Labels::getLabel('NAV_CONTENT_PAGES', $langId),
                static::SECTION_CONTENT_BLOCKS => Labels::getLabel('NAV_CONTENT_BLOCKS', $langId),
                static::SECTION_NAVIGATION_MANAGEMENT => Labels::getLabel('NAV_NAVIGATION_MANAGEMENT', $langId),
                static::SECTION_COUNTRIES => Labels::getLabel('NAV_COUNTRIES', $langId),
                /* static::SECTION_ZONES => Labels::getLabel('NAV_ZONES',$langId), */
                static::SECTION_STATES => Labels::getLabel('NAV_STATES', $langId),
                static::SECTION_COLLECTIONS => Labels::getLabel('NAV_COLLECTIONS', $langId),
                static::SECTION_EMPTY_CART_ITEMS_MANAGEMENT => Labels::getLabel('NAV_EMPTY_CART_MANAGEMENT', $langId),
                static::SECTION_SOCIALPLATFORM => Labels::getLabel('NAV_SOCIAL_PLATFORM', $langId),
                static::SECTION_SHOP_REPORT_REASONS => Labels::getLabel('NAV_SHOP_REPORT_REASONS', $langId),
                static::SECTION_ORDER_CANCEL_REASONS => Labels::getLabel('NAV_ORDER_CANCEL_REASONS', $langId),
                static::SECTION_ORDER_RETURN_REASONS => Labels::getLabel('NAV_ORDER_RETURN_REASONS', $langId),
                static::SECTION_TESTIMONIAL => Labels::getLabel('NAV_TESTIMONIAL', $langId),
                static::SECTION_DISCOUNT_COUPONS => Labels::getLabel('NAV_DISCOUNT_COUPONS', $langId),
                static::SECTION_LANGUAGE_LABELS => Labels::getLabel('NAV_LANGUAGE_LABELS', $langId),
                static::SECTION_SLIDES => Labels::getLabel('NAV_HOME_PAGE_SLIDE_MANAGEMENT', $langId),
                static::SECTION_BANNERS => Labels::getLabel('NAV_BANNERS', $langId),

                static::SECTION_SHIPPING_APIS => Labels::getLabel('NAV_SHIPPING_API_METHODS', $langId),
                static::SECTION_SHIPPING_DURATIONS => Labels::getLabel('NAV_SHIPPING_DURATIONS', $langId),
                static::SECTION_SHIPPING_PACKAGES => Labels::getLabel('NAV_SHIPPING_PACKAGES', $langId),
                static::SECTION_SHIPPING_MANAGEMENT => Labels::getLabel('NAV_SHIPPING_MANAGEMENT', $langId),
                static::SECTION_SHIPPED_PRODUCTS_LISTING => Labels::getLabel('NAV_SHIPPING_PRODUCTS_LISTING', $langId),
                /* static::SECTION_MANUAL_SHIPPING_API => Labels::getLabel('NAV_MANUAL_SHIPPING_API',$langId), */

                static::SECTION_GENERAL_SETTINGS => Labels::getLabel('NAV_GENERAL_SETTINGS', $langId),
                static::SECTION_PAYMENT_METHODS => Labels::getLabel('NAV_PAYMENT_METHODS', $langId),
                static::SECTION_CURRENCY_MANAGEMENT => Labels::getLabel('NAV_CURRENCY_MANAGEMENT', $langId),
                static::SECTION_TAX => Labels::getLabel('NAV_TAX', $langId),
                static::SECTION_COMMISSION => Labels::getLabel('NAV_COMMISSION', $langId),
                static::SECTION_AFFILIATE_COMMISSION => Labels::getLabel('NAV_AFFILIATE_COMMISSION', $langId),
                static::SECTION_EMAIL_TEMPLATES => Labels::getLabel('NAV_EMAIL_TEMPLATES', $langId),
                static::SECTION_POLICY_POINTS => Labels::getLabel('NAV_POLICY_POINTS', $langId),
                static::SECTION_SELLER_PACKAGES => Labels::getLabel('NAV_SELLER_PACKAGES', $langId),
                static::SECTION_REWARDS_ON_PURCHASE => Labels::getLabel('NAV_REWARDS_ON_PURCHASE', $langId),

                static::SECTION_ORDERS => Labels::getLabel('NAV_ORDERS', $langId),
                static::SECTION_VENDOR_ORDERS => Labels::getLabel('NAV_SELLER_ORDERS', $langId),
                static::SECTION_WITHDRAW_REQUESTS => Labels::getLabel('NAV_WITHDRAW_REQUESTS', $langId),
                static::SECTION_ORDER_CANCELLATION_REQUESTS => Labels::getLabel('NAV_ORDER_CANCELLATION_REQUESTS', $langId),
                static::SECTION_ORDER_RETURN_REQUESTS => Labels::getLabel('NAV_ORDER_RETURN_REQUESTS', $langId),

                static::SMART_RECOMENDED_WEIGHTAGES => Labels::getLabel('NAV_RECOMMENDED_WEIGHTAGES', $langId),
                static::SMART_PRODUCT_TAG_PRODUCTS => Labels::getLabel('NAV_RECOMMENDED_TAG_PRODUCTS', $langId),

                static::SECTION_PROMOTIONS => Labels::getLabel('NAV_PROMOTIONS', $langId),

                static::SECTION_META_TAGS => Labels::getLabel('NAV_META_TAGS', $langId),
                static::SECTION_FAQ_CATEGORY => Labels::getLabel('NAV_FAQ_CATEGORY', $langId),
                static::SECTION_FAQ => Labels::getLabel('NAV_FAQ', $langId),
                static::SECTION_URL_REWRITE => Labels::getLabel('NAV_URL_REWRITING', $langId),
                static::SECTION_IMAGE_ATTRIBUTES => Labels::getLabel('NAV_IMAGE_ATTRIBUTES', $langId),

                static::SECTION_BLOG_POST_CATEGORIES => Labels::getLabel('NAV_BLOG_CATEGORIES', $langId),
                static::SECTION_BLOG_POSTS => Labels::getLabel('NAV_BLOG_POSTS', $langId),
                static::SECTION_BLOG_CONTRIBUTIONS => Labels::getLabel('NAV_BLOG_CONTRIBUTIONS', $langId),
                static::SECTION_BLOG_COMMENTS => Labels::getLabel('NAV_BLOG_COMMENTS', $langId),

                static::SECTION_SHOP_REPORTS => Labels::getLabel('NAV_SHOP_REPORTS', $langId),
                static::SECTION_SHOPS_REPORT => Labels::getLabel('NAV_SHOPS_REPORT', $langId),
                static::SECTION_SALES_REPORT => Labels::getLabel('NAV_SALES_REPORT', $langId),
                static::SECTION_USERS_REPORT => Labels::getLabel('NAV_USERS_REPORT', $langId),
                static::SECTION_PRODUCTS_REPORT => Labels::getLabel('NAV_PRODUCTS_REPORT', $langId),
                static::SECTION_TAX_REPORT => Labels::getLabel('NAV_TAX_REPORT', $langId),
                static::SECTION_COMMISSION_REPORT => Labels::getLabel('NAV_COMMISSION_REPORT', $langId),
                static::SECTION_CATALOG_REPORT => Labels::getLabel('NAV_CATALOG_REPORT', $langId),
                static::SECTION_PERFORMANCE_REPORT => Labels::getLabel('NAV_PROFORMANCE_REPORT', $langId),
                static::SECTION_AFFILIATES_REPORT => Labels::getLabel('NAV_AFFILIATE_REPORT', $langId),
                static::SECTION_ADVERTISERS_REPORT => Labels::getLabel('NAV_ADVERTISER_REPORT', $langId),
                /* static::SECTION_SELLER_DISCOUNT_COUPONS => Labels::getLabel('NAV_SELLER_DISCOUNT_COUPONS',$langId), */
                static::SECTION_THEME_COLOR => Labels::getLabel('NAV_THEME_COLOR', $langId),

                static::SECTION_ADMIN_USERS => Labels::getLabel('NAV_ADMIN_USERS', $langId),
                static::SECTION_ADMIN_LOGIN_HISTORY => Labels::getLabel('NAV_ADMIN_LOGIN_HISTORY', $langId),
                static::SECTION_ADMIN_PERMISSIONS => Labels::getLabel('NAV_ADMIN_ROLES', $langId),

                //static::SECTION_TOOLS => Labels::getLabel('NAV_TOOLS', $langId),
                static::SECTION_MESSAGES => Labels::getLabel('NAV_MESSAGES', $langId),
                // static::SECTION_NOTIFICATION => Labels::getLabel('NAV_NOTIFICATIONS',$langId),
                static::SECTION_DATABASE_BACKUP => Labels::getLabel('NAV_DATABASE_BACKUP', $langId),
                static::SECTION_ORDER_STATUS => Labels::getLabel('NAV_ORDER_STATUS_MANAGEMENT', $langId),
                static::SECTION_USER_REQUESTS => Labels::getLabel('NAV_USER_REQUESTS', $langId),
                static::SECTION_PRODUCT_TEMP_IMAGES => Labels::getLabel('NAV_PRODUCTS_TEMP_IMAGES', $langId),
                static::SECTION_IMPORT_INSTRUCTIONS => Labels::getLabel('NAV_IMPORT_INSTRUCTIONS', $langId),
                static::SECTION_UPLOAD_BULK_IMAGES => Labels::getLabel('NAV_BULK_UPLOAD', $langId),
                static::SECTION_SITEMAP => Labels::getLabel('NAV_SITEMAP', $langId),
                static::SECTION_PUSH_NOTIFICATION => Labels::getLabel('NAV_PUSH_NOTIFICATION', $langId),
                static::SECTION_PRODUCT_ADVERTISEMENT => Labels::getLabel('NAV_PRODUCT_ADVERTISEMENT', $langId),
                static::SECTION_PLUGINS => Labels::getLabel('NAV_PLUGINS', $langId),
                // static::SECTION_APP_THEME_SETTINGS => Labels::getLabel('NAV_APP_THEME_SETTINGS', $langId),
                static::SECTION_ABANDONED_CART => Labels::getLabel('NAV_ABANDONED_CART', $langId),
                static::SECTION_IMPORT_EXPORT => Labels::getLabel('NAV_IMPORT_EXPORT', $langId),
                static::SECTION_SMS_TEMPLATE => Labels::getLabel('NAV_SMS_TEMPLATE', $langId),

                static::SECTION_ABUSIVE_WORDS => Labels::getLabel('NAV_ABUSIVE_WORDS', $langId),
                static::SECTION_SUBSCRIPTION_ORDERS => Labels::getLabel('NAV_SUBSCRIPTION_ORDERS', $langId),

                static::SECTION_PICKUP_ADDRESSES => Labels::getLabel('NAV_PICKUP_ADDRESSES', $langId),
                static::SECTION_RATING_TYPES => Labels::getLabel('NAV_RATING_TYPES', $langId),
                static::SECTION_BUYERS_REPORT => Labels::getLabel('NAV_CUSTOMER_REPORT', $langId),
                static::SECTION_SELLERS_REPORT => Labels::getLabel('NAV_SELLER_REPORT', $langId),
                static::SECTION_SUBSCRIPTION_REPORT => Labels::getLabel('NAV_SUBSCRIPTION_REPORT', $langId),
                static::SECTION_FINANCIAL_REPORT => Labels::getLabel('NAV_FINANCIAL_REPORT', $langId),
                static::SECTION_ORDERS_REPORT => Labels::getLabel('NAV_ORDERS_REPORT', $langId),

                static::SECTION_BADGES => Labels::getLabel('NAV_BADGES_&_RIBBONS', $langId),
                static::SECTION_BADGE_LINKS => Labels::getLabel('NAV_BADGE_LINKS', $langId),
                static::SECTION_BADGE_REQUESTS => Labels::getLabel('NAV_BADGE_REQUESTS', $langId),
                static::SECTION_SETTINGS => Labels::getLabel('NAV_SYSTEM_SETTINGS', $langId),
                static::SECTION_PAGES_LANGUAGE_DATA => Labels::getLabel('NAV_PAGES_LANGUAGE_DATA_SETTINGS', $langId),
                static::SECTION_GETTING_STARTED => Labels::getLabel('NAV_GETTING_STARTED', $langId),
                static::SECTION_REQUEST_FOR_QUOTE => Labels::getLabel('NAV_REQUEST_FOR_QUOTES', $langId),

                /* static::SECTION_Languages => Labels::getLabel('NAV_LANGUAGES',$langId),
                static::SECTION_Languages => Labels::getLabel('NAV_ORDER_STATUS',$langId), */

                /*static::SECTION_SUCCESS_STORIES => Labels::getLabel('NAV_SUCCESS_STORIES',$langId),
                static::SECTION_HOME_PAGE_ELEMENTS => Labels::getLabel('NAV_HOME_PAGE_ELEMENTS',$langId),
                static::SECTION_QUESTION_BANKS => Labels::getLabel('NAV_QUESTION_BANKS',$langId),

                static::SECTION_QUESTIONS => Labels::getLabel('NAV_QUESTIONS',$langId),
                static::SECTION_QUESTIONNAIRES => Labels::getLabel('NAV_QUESTIONNAIRES',$langId), */

                /* static::SECTION_POLLING => Labels::getLabel('NAV_POLLING',$langId),
                static::SECTION_FILTER_GROUPS => 'Filter Groups',
                static::SECTION_FILTERS => 'Filters',
                static::SECTION_EXTRA_ATTRIBUTES => 'Extra Attributes',	 */
            );
            CacheHelper::create('permissionLabels' . $langId, FatUtility::convertToJson($arr), CacheHelper::TYPE_LABELS);
            return $arr;
        }

        return json_decode($arr, true);
    }

    public static function getWriteOnlyPermissionModulesArr(): array
    {
        return array(
            static::SECTION_UPLOAD_BULK_IMAGES,
        );
    }

    public static function getReadOnlyPermissionModulesArr(): array
    {
        return array(
            static::SECTION_ABANDONED_CART,
        );
    }

    public static function getAdminPermissionLevel(int $adminId, int $sectionId = 0)
    {
        /* Are you looking for permissions of administrator [ */
        if (static::isAdminSuperAdmin($adminId)) {
            $arrLevels = [];
            if ($sectionId > 0) {
                $arrLevels[$sectionId] = static::PRIVILEGE_WRITE;
            } else {
                for ($i = 0; $i <= 2; $i++) {
                    $arrLevels[$i] = static::PRIVILEGE_WRITE;
                }
            }
            return $arrLevels;
        }
        /* ] */

        $srch = new SearchBase('tbl_admin_permissions');
        $srch->addCondition('admperm_admin_id', '=', 'mysql_func_' . $adminId, 'AND', true);
        $srch->doNotCalculateRecords();
        if (0 < $sectionId) {
            $srch->addCondition('admperm_section_id', '=', 'mysql_func_' . $sectionId, 'AND', true);
        }

        $srch->addMultipleFields(array('admperm_section_id', 'admperm_value'));
        $rs = $srch->getResultSet();
        $arr = FatApp::getDb()->fetchAllAssoc($rs);
        return $arr;
    }

    private function cacheLoadedPermission($adminId, $secId, $level)
    {
        /*  if (!isset($this->loadedPermissions[$adminId])) {
            $this->loadedPermissions[$adminId] = array();
        }
        $this->loadedPermissions[$adminId][$secId] = $level; */

        if (!isset($_SESSION[self::SESSION_ELEMENT_NAME])) {
            $_SESSION[self::SESSION_ELEMENT_NAME] = array();
        }

        $_SESSION[self::SESSION_ELEMENT_NAME][$adminId][$secId] = $level;
        // $this->loadedPermissions = $_SESSION[self::SESSION_ELEMENT_NAME];
    }

    private function checkPermission(int $adminId, int $secId, int $level, bool $returnResult = false)
    {

        if (!in_array($level, array(1, 2))) {
            trigger_error(Labels::getLabel('ERR_INVALID_PERMISSION_LEVEL_CHECKED', CommonHelper::getLangId()) . ' ' . $level, E_USER_ERROR);
        }

        if (0 == $adminId) {
            $adminId = AdminAuthentication::getLoggedAdminId();
        }

        if (isset($_SESSION[self::SESSION_ELEMENT_NAME][$adminId][$secId])) {
            if ($level <= $_SESSION[self::SESSION_ELEMENT_NAME][$adminId][$secId]) {
                return true;
            }
            return $this->returnFalseOrDie($returnResult);
        }
        /*  if (isset($this->loadedPermissions[$adminId][$secId])) {
            if ($level <= $this->loadedPermissions[$adminId][$secId]) {
                return true;
            }
            return $this->returnFalseOrDie($returnResult);
        } */

        if ($this->isAdminSuperAdmin($adminId)) {
            return true;
        }

        $rowAdmin = Admin::getAttributesById($adminId, array('admin_active'));
        if (empty($rowAdmin)) {
            LibHelper::exitWithError(Labels::getLabel('ERR_INVALID_REQUEST', CommonHelper::getLangId()));
        }

        if ($rowAdmin['admin_active'] != applicationConstants::ACTIVE) {
            LibHelper::exitWithError(Labels::getLabel('ERR_INVALID_REQUEST', CommonHelper::getLangId()));
        }

        $db = FatApp::getDb();
        $rs = $db->query(
            "SELECT admperm_value FROM tbl_admin_permissions WHERE
				admperm_admin_id = " . $adminId . " AND admperm_section_id = " . $secId
        );
        if (!$row = $db->fetch($rs)) {
            $this->cacheLoadedPermission($adminId, $secId, static::PRIVILEGE_NONE);
            return $this->returnFalseOrDie($returnResult);
        }

        $permissionLevel = $row['admperm_value'];

        $this->cacheLoadedPermission($adminId, $secId, $permissionLevel);

        if ($level > $permissionLevel) {
            return $this->returnFalseOrDie($returnResult);
        }

        return true;
    }

    private function returnFalseOrDie($returnResult, $msg = '')
    {
        if ($returnResult) {
            return (false);
        }

        if (empty($msg)) {
            $msg = Labels::getLabel('ERR_UNAUTHORIZED_ACCESS!', CommonHelper::getLangId());
        }
        LibHelper::exitWithError($msg);
    }

    public function clearPermissionCache($adminId)
    {
        if (isset($this->loadedPermissions[$adminId])) {
            unset($this->loadedPermissions[$adminId]);
        }
    }

    public function canViewProductCategories($adminId = 0, $returnResult = false)
    {
        return $this->checkPermission($adminId, static::SECTION_PRODUCT_CATEGORIES, static::PRIVILEGE_READ, $returnResult);
    }

    public function canEditProductCategories($adminId = 0, $returnResult = false)
    {
        return $this->checkPermission($adminId, static::SECTION_PRODUCT_CATEGORIES, static::PRIVILEGE_WRITE, $returnResult);
    }

    public function canViewProducts($adminId = 0, $returnResult = false)
    {
        return $this->checkPermission($adminId, static::SECTION_PRODUCTS, static::PRIVILEGE_READ, $returnResult);
    }

    public function canEditProducts($adminId = 0, $returnResult = false)
    {
        return $this->checkPermission($adminId, static::SECTION_PRODUCTS, static::PRIVILEGE_WRITE, $returnResult);
    }

    public function canViewBrands($adminId = 0, $returnResult = false)
    {
        return $this->checkPermission($adminId, static::SECTION_BRANDS, static::PRIVILEGE_READ, $returnResult);
    }

    public function canEditBrands($adminId = 0, $returnResult = false)
    {
        return $this->checkPermission($adminId, static::SECTION_BRANDS, static::PRIVILEGE_WRITE, $returnResult);
    }

    public function canViewFilterGroups($adminId = 0, $returnResult = false)
    {
        return $this->checkPermission($adminId, static::SECTION_FILTER_GROUPS, static::PRIVILEGE_READ, $returnResult);
    }

    public function canEditFilterGroups($adminId = 0, $returnResult = false)
    {
        return $this->checkPermission($adminId, static::SECTION_FILTER_GROUPS, static::PRIVILEGE_WRITE, $returnResult);
    }

    public function canViewFilters($adminId = 0, $returnResult = false)
    {
        return $this->checkPermission($adminId, static::SECTION_FILTERS, static::PRIVILEGE_READ, $returnResult);
    }

    public function canEditFilters($adminId = 0, $returnResult = false)
    {
        return $this->checkPermission($adminId, static::SECTION_FILTERS, static::PRIVILEGE_WRITE, $returnResult);
    }

    public function canViewTags($adminId = 0, $returnResult = false)
    {
        return $this->checkPermission($adminId, static::SECTION_TAGS, static::PRIVILEGE_READ, $returnResult);
    }

    public function canEditTags($adminId = 0, $returnResult = false)
    {
        return $this->checkPermission($adminId, static::SECTION_TAGS, static::PRIVILEGE_WRITE, $returnResult);
    }

    public function canViewOptions($adminId = 0, $returnResult = false)
    {
        return $this->checkPermission($adminId, static::SECTION_OPTIONS, static::PRIVILEGE_READ, $returnResult);
    }

    public function canEditOptions($adminId = 0, $returnResult = false)
    {
        return $this->checkPermission($adminId, static::SECTION_OPTIONS, static::PRIVILEGE_WRITE, $returnResult);
    }

    public function canViewAttributes($adminId = 0, $returnResult = false)
    {
        return $this->checkPermission($adminId, static::SECTION_ATTRIBUTES, static::PRIVILEGE_READ, $returnResult);
    }

    public function canEditAttributes($adminId = 0, $returnResult = false)
    {
        return $this->checkPermission($adminId, static::SECTION_ATTRIBUTES, static::PRIVILEGE_WRITE, $returnResult);
    }

    public function canViewExtraAttributes($adminId = 0, $returnResult = false)
    {
        return $this->checkPermission($adminId, static::SECTION_EXTRA_ATTRIBUTES, static::PRIVILEGE_READ, $returnResult);
    }

    public function canEditExtraAttributes($adminId = 0, $returnResult = false)
    {
        return $this->checkPermission($adminId, static::SECTION_EXTRA_ATTRIBUTES, static::PRIVILEGE_WRITE, $returnResult);
    }

    public function canViewGeneralSettings($adminId = 0, $returnResult = false)
    {
        return $this->checkPermission($adminId, static::SECTION_GENERAL_SETTINGS, static::PRIVILEGE_READ, $returnResult);
    }

    public function canEditGeneralSettings($adminId = 0, $returnResult = false)
    {
        return $this->checkPermission($adminId, static::SECTION_GENERAL_SETTINGS, static::PRIVILEGE_WRITE, $returnResult);
    }

    public function canViewUsers($adminId = 0, $returnResult = false)
    {
        return $this->checkPermission($adminId, static::SECTION_USERS, static::PRIVILEGE_READ, $returnResult);
    }

    public function canEditUsers($adminId = 0, $returnResult = false)
    {
        return $this->checkPermission($adminId, static::SECTION_USERS, static::PRIVILEGE_WRITE, $returnResult);
    }

    public function canVerifyUsers($adminId = 0, $returnResult = false)
    {
        return $this->checkPermission($adminId, static::SECTION_USERS, static::PRIVILEGE_WRITE, $returnResult);
    }

    public function canViewSellerApprovalForm($adminId = 0, $returnResult = false)
    {
        return $this->checkPermission($adminId, static::SECTION_SUPPLIER_APPROVAL_FORM, static::PRIVILEGE_READ, $returnResult);
    }

    public function canEditSellerApprovalForm($adminId = 0, $returnResult = false)
    {
        return $this->checkPermission($adminId, static::SECTION_SUPPLIER_APPROVAL_FORM, static::PRIVILEGE_WRITE, $returnResult);
    }

    public function canViewSellerApprovalRequests($adminId = 0, $returnResult = false)
    {
        return $this->checkPermission($adminId, static::SECTION_SUPPLIER_APPROVAL_REQUESTS, static::PRIVILEGE_READ, $returnResult);
    }

    public function canEditSellerApprovalRequests($adminId = 0, $returnResult = false)
    {
        return $this->checkPermission($adminId, static::SECTION_SUPPLIER_APPROVAL_REQUESTS, static::PRIVILEGE_WRITE, $returnResult);
    }

    public function canViewShops($adminId = 0, $returnResult = false)
    {
        return $this->checkPermission($adminId, static::SECTION_SHOPS, static::PRIVILEGE_READ, $returnResult);
    }

    public function canEditShops($adminId = 0, $returnResult = false)
    {
        return $this->checkPermission($adminId, static::SECTION_SHOPS, static::PRIVILEGE_WRITE, $returnResult);
    }

    public function canViewPaymentMethods($adminId = 0, $returnResult = false)
    {
        return $this->checkPermission($adminId, static::SECTION_PAYMENT_METHODS, static::PRIVILEGE_READ, $returnResult);
    }

    public function canEditPaymentMethods($adminId = 0, $returnResult = false)
    {
        return $this->checkPermission($adminId, static::SECTION_PAYMENT_METHODS, static::PRIVILEGE_WRITE, $returnResult);
    }

    public function canViewContentBlocks($adminId = 0, $returnResult = false)
    {
        return $this->checkPermission($adminId, static::SECTION_CONTENT_BLOCKS, static::PRIVILEGE_READ, $returnResult);
    }

    public function canEditContentBlocks($adminId = 0, $returnResult = false)
    {
        return $this->checkPermission($adminId, static::SECTION_CONTENT_BLOCKS, static::PRIVILEGE_WRITE, $returnResult);
    }
    public function canViewShippingDurationLabels($adminId = 0, $returnResult = false)
    {
        return $this->checkPermission($adminId, static::SECTION_SHIPPING_DURATIONS, static::PRIVILEGE_READ, $returnResult);
    }

    public function canEditShippingDurationLabels($adminId = 0, $returnResult = false)
    {
        return $this->checkPermission($adminId, static::SECTION_SHIPPING_DURATIONS, static::PRIVILEGE_WRITE, $returnResult);
    }

    public function canViewManualShippingApi($adminId = 0, $returnResult = false)
    {
        return $this->checkPermission($adminId, static::SECTION_MANUAL_SHIPPING_API, static::PRIVILEGE_READ, $returnResult);
    }

    public function canEditManualShippingApi($adminId = 0, $returnResult = false)
    {
        return $this->checkPermission($adminId, static::SECTION_MANUAL_SHIPPING_API, static::PRIVILEGE_WRITE, $returnResult);
    }

    public function canViewLanguageLabels($adminId = 0, $returnResult = false)
    {
        return $this->checkPermission($adminId, static::SECTION_LANGUAGE_LABELS, static::PRIVILEGE_READ, $returnResult);
    }

    public function canEditLanguageLabels($adminId = 0, $returnResult = false)
    {
        return $this->checkPermission($adminId, static::SECTION_LANGUAGE_LABELS, static::PRIVILEGE_WRITE, $returnResult);
    }

    public function canViewCurrencyManagement($adminId = 0, $returnResult = false)
    {
        return $this->checkPermission($adminId, static::SECTION_CURRENCY_MANAGEMENT, static::PRIVILEGE_READ, $returnResult);
    }

    public function canEditCurrencyManagement($adminId = 0, $returnResult = false)
    {
        return $this->checkPermission($adminId, static::SECTION_CURRENCY_MANAGEMENT, static::PRIVILEGE_WRITE, $returnResult);
    }

    public function canViewContentPages($adminId = 0, $returnResult = false)
    {
        return $this->checkPermission($adminId, static::SECTION_CONTENT_PAGES, static::PRIVILEGE_READ, $returnResult);
    }

    public function canEditContentPages($adminId = 0, $returnResult = false)
    {
        return $this->checkPermission($adminId, static::SECTION_CONTENT_PAGES, static::PRIVILEGE_WRITE, $returnResult);
    }

    public function canViewEmptyCartItems($adminId = 0, $returnResult = false)
    {
        return $this->checkPermission($adminId, static::SECTION_EMPTY_CART_ITEMS_MANAGEMENT, static::PRIVILEGE_READ, $returnResult);
    }

    public function canEditEmptyCartItems($adminId = 0, $returnResult = false)
    {
        return $this->checkPermission($adminId, static::SECTION_EMPTY_CART_ITEMS_MANAGEMENT, static::PRIVILEGE_WRITE, $returnResult);
    }
    public function canViewNavigationManagement($adminId = 0, $returnResult = false)
    {
        return $this->checkPermission($adminId, static::SECTION_NAVIGATION_MANAGEMENT, static::PRIVILEGE_READ, $returnResult);
    }

    public function canEditNavigationManagement($adminId = 0, $returnResult = false)
    {
        return $this->checkPermission($adminId, static::SECTION_NAVIGATION_MANAGEMENT, static::PRIVILEGE_WRITE, $returnResult);
    }
    public function canViewSellerCatalogRequests($adminId = 0, $returnResult = false)
    {
        return $this->checkPermission($adminId, static::SECTION_CATALOG_REQUESTS, static::PRIVILEGE_READ, $returnResult);
    }

    public function canEditSellerCatalogRequests($adminId = 0, $returnResult = false)
    {
        return $this->checkPermission($adminId, static::SECTION_CATALOG_REQUESTS, static::PRIVILEGE_WRITE, $returnResult);
    }

    public function canViewShippingMethods($adminId = 0, $returnResult = false)
    {
        return $this->checkPermission($adminId, static::SECTION_SHIPPING_APIS, static::PRIVILEGE_READ, $returnResult);
    }

    public function canEditShippingMethods($adminId = 0, $returnResult = false)
    {
        return $this->checkPermission($adminId, static::SECTION_SHIPPING_APIS, static::PRIVILEGE_WRITE, $returnResult);
    }

    public function canViewShippingCompanies($adminId = 0, $returnResult = false)
    {
        return $this->checkPermission($adminId, static::SECTION_SHIPPING_APIS, static::PRIVILEGE_READ, $returnResult);
    }

    public function canEditShippingCompanies($adminId = 0, $returnResult = false)
    {
        return $this->checkPermission($adminId, static::SECTION_SHIPPING_APIS, static::PRIVILEGE_WRITE, $returnResult);
    }

    public function canViewCommissionSettings($adminId = 0, $returnResult = false)
    {
        return $this->checkPermission($adminId, static::SECTION_COMMISSION, static::PRIVILEGE_READ, $returnResult);
    }

    public function canEditCommissionSettings($adminId = 0, $returnResult = false)
    {
        return $this->checkPermission($adminId, static::SECTION_COMMISSION, static::PRIVILEGE_WRITE, $returnResult);
    }

    public function canViewOrders($adminId = 0, $returnResult = false)
    {
        return $this->checkPermission($adminId, static::SECTION_ORDERS, static::PRIVILEGE_READ, $returnResult);
    }

    public function canEditOrders($adminId = 0, $returnResult = false)
    {
        return $this->checkPermission($adminId, static::SECTION_ORDERS, static::PRIVILEGE_WRITE, $returnResult);
    }

    public function canViewSellerOrders($adminId = 0, $returnResult = false)
    {
        return $this->checkPermission($adminId, static::SECTION_VENDOR_ORDERS, static::PRIVILEGE_READ, $returnResult);
    }

    public function canEditSellerOrders($adminId = 0, $returnResult = false)
    {
        return $this->checkPermission($adminId, static::SECTION_VENDOR_ORDERS, static::PRIVILEGE_WRITE, $returnResult);
    }

    public function canViewWithdrawRequests($adminId = 0, $returnResult = false)
    {
        return $this->checkPermission($adminId, static::SECTION_WITHDRAW_REQUESTS, static::PRIVILEGE_READ, $returnResult);
    }

    public function canEditWithdrawRequests($adminId = 0, $returnResult = false)
    {
        return $this->checkPermission($adminId, static::SECTION_WITHDRAW_REQUESTS, static::PRIVILEGE_WRITE, $returnResult);
    }

    public function canViewOrderCancellationRequests($adminId = 0, $returnResult = false)
    {
        return $this->checkPermission($adminId, static::SECTION_ORDER_CANCELLATION_REQUESTS, static::PRIVILEGE_READ, $returnResult);
    }

    public function canEditOrderCancellationRequests($adminId = 0, $returnResult = false)
    {
        return $this->checkPermission($adminId, static::SECTION_ORDER_CANCELLATION_REQUESTS, static::PRIVILEGE_WRITE, $returnResult);
    }

    public function canViewOrderReturnRequests($adminId = 0, $returnResult = false)
    {
        return $this->checkPermission($adminId, static::SECTION_ORDER_RETURN_REQUESTS, static::PRIVILEGE_READ, $returnResult);
    }

    public function canEditOrderReturnRequests($adminId = 0, $returnResult = false)
    {
        return $this->checkPermission($adminId, static::SECTION_ORDER_RETURN_REQUESTS, static::PRIVILEGE_WRITE, $returnResult);
    }

    public function canViewTax($adminId = 0, $returnResult = false)
    {
        return $this->checkPermission($adminId, static::SECTION_TAX, static::PRIVILEGE_READ, $returnResult);
    }

    public function canEditTax($adminId = 0, $returnResult = false)
    {
        return $this->checkPermission($adminId, static::SECTION_TAX, static::PRIVILEGE_WRITE, $returnResult);
    }

    public function canViewSlides($adminId = 0, $returnResult = false)
    {
        return $this->checkPermission($adminId, static::SECTION_SLIDES, static::PRIVILEGE_READ, $returnResult);
    }

    public function canEditSlides($adminId = 0, $returnResult = false)
    {
        return $this->checkPermission($adminId, static::SECTION_SLIDES, static::PRIVILEGE_WRITE, $returnResult);
    }

    public function canViewCountries($adminId = 0, $returnResult = false)
    {
        return $this->checkPermission($adminId, static::SECTION_COUNTRIES, static::PRIVILEGE_READ, $returnResult);
    }

    public function canEditCountries($adminId = 0, $returnResult = false)
    {
        return $this->checkPermission($adminId, static::SECTION_COUNTRIES, static::PRIVILEGE_WRITE, $returnResult);
    }

    public function canViewZones($adminId = 0, $returnResult = false)
    {
        return $this->checkPermission($adminId, static::SECTION_ZONES, static::PRIVILEGE_READ, $returnResult);
    }

    public function canEditZones($adminId = 0, $returnResult = false)
    {
        return $this->checkPermission($adminId, static::SECTION_ZONES, static::PRIVILEGE_WRITE, $returnResult);
    }

    public function canViewStates($adminId = 0, $returnResult = false)
    {
        return $this->checkPermission($adminId, static::SECTION_STATES, static::PRIVILEGE_READ, $returnResult);
    }

    public function canEditStates($adminId = 0, $returnResult = false)
    {
        return $this->checkPermission($adminId, static::SECTION_STATES, static::PRIVILEGE_WRITE, $returnResult);
    }

    public function canViewEmailTemplates($adminId = 0, $returnResult = false)
    {
        return $this->checkPermission($adminId, static::SECTION_EMAIL_TEMPLATES, static::PRIVILEGE_READ, $returnResult);
    }

    public function canEditEmailTemplates($adminId = 0, $returnResult = false)
    {
        return $this->checkPermission($adminId, static::SECTION_EMAIL_TEMPLATES, static::PRIVILEGE_WRITE, $returnResult);
    }

    public function canViewAdminUsers($adminId = 0, $returnResult = false)
    {
        return $this->checkPermission($adminId, static::SECTION_ADMIN_USERS, static::PRIVILEGE_READ, $returnResult);
    }

    public function canEditAdminUsers($adminId = 0, $returnResult = false)
    {
        return $this->checkPermission($adminId, static::SECTION_ADMIN_USERS, static::PRIVILEGE_WRITE, $returnResult);
    }

    public function canViewBanners($adminId = 0, $returnResult = false)
    {
        return $this->checkPermission($adminId, static::SECTION_BANNERS, static::PRIVILEGE_READ, $returnResult);
    }

    public function canEditBanners($adminId = 0, $returnResult = false)
    {
        return $this->checkPermission($adminId, static::SECTION_BANNERS, static::PRIVILEGE_WRITE, $returnResult);
    }

    public function canViewSocialPlatforms($adminId = 0, $returnResult = false)
    {
        return $this->checkPermission($adminId, static::SECTION_SOCIALPLATFORM, static::PRIVILEGE_READ, $returnResult);
    }

    public function canEditSocialPlatforms($adminId = 0, $returnResult = false)
    {
        return $this->checkPermission($adminId, static::SECTION_SOCIALPLATFORM, static::PRIVILEGE_WRITE, $returnResult);
    }

    public function canViewCollections($adminId = 0, $returnResult = false)
    {
        return $this->checkPermission($adminId, static::SECTION_COLLECTIONS, static::PRIVILEGE_READ, $returnResult);
    }

    public function canEditCollections($adminId = 0, $returnResult = false)
    {
        return $this->checkPermission($adminId, static::SECTION_COLLECTIONS, static::PRIVILEGE_WRITE, $returnResult);
    }

    public function canViewHomePageElements($adminId = 0, $returnResult = false)
    {
        return $this->checkPermission($adminId, static::SECTION_HOME_PAGE_ELEMENTS, static::PRIVILEGE_READ, $returnResult);
    }

    public function canEditHomePageElements($adminId = 0, $returnResult = false)
    {
        return $this->checkPermission($adminId, static::SECTION_HOME_PAGE_ELEMENTS, static::PRIVILEGE_WRITE, $returnResult);
    }

    public function canViewShopReportReasons($adminId = 0, $returnResult = false)
    {
        return $this->checkPermission($adminId, static::SECTION_SHOP_REPORT_REASONS, static::PRIVILEGE_READ, $returnResult);
    }

    public function canEditShopReportReasons($adminId = 0, $returnResult = false)
    {
        return $this->checkPermission($adminId, static::SECTION_SHOP_REPORT_REASONS, static::PRIVILEGE_WRITE, $returnResult);
    }

    public function canViewShopReports($adminId = 0, $returnResult = false)
    {
        return $this->checkPermission($adminId, static::SECTION_SHOP_REPORTS, static::PRIVILEGE_READ, $returnResult);
    }

    public function canEditShopReports($adminId = 0, $returnResult = false)
    {
        return $this->checkPermission($adminId, static::SECTION_SHOP_REPORTS, static::PRIVILEGE_WRITE, $returnResult);
    }

    public function canViewOrderCancelReasons($adminId = 0, $returnResult = false)
    {
        return $this->checkPermission($adminId, static::SECTION_ORDER_CANCEL_REASONS, static::PRIVILEGE_READ, $returnResult);
    }

    public function canEditOrderCancelReasons($adminId = 0, $returnResult = false)
    {
        return $this->checkPermission($adminId, static::SECTION_ORDER_CANCEL_REASONS, static::PRIVILEGE_WRITE, $returnResult);
    }

    public function canViewOrderReturnReasons($adminId = 0, $returnResult = false)
    {
        return $this->checkPermission($adminId, static::SECTION_ORDER_RETURN_REASONS, static::PRIVILEGE_READ, $returnResult);
    }

    public function canEditOrderReturnReasons($adminId = 0, $returnResult = false)
    {
        return $this->checkPermission($adminId, static::SECTION_ORDER_RETURN_REASONS, static::PRIVILEGE_WRITE, $returnResult);
    }

    public function canViewMetaTags($adminId = 0, $returnResult = false)
    {
        return $this->checkPermission($adminId, static::SECTION_META_TAGS, static::PRIVILEGE_READ, $returnResult);
    }

    public function canEditMetaTags($adminId = 0, $returnResult = false)
    {
        return $this->checkPermission($adminId, static::SECTION_META_TAGS, static::PRIVILEGE_WRITE, $returnResult);
    }

    public function canViewAdminDashboard($adminId = 0, $returnResult = false)
    {
        return $this->checkPermission($adminId, static::SECTION_ADMIN_DASHBOARD, static::PRIVILEGE_READ, $returnResult);
    }

    public function canEditAdminDashboard($adminId = 0, $returnResult = false)
    {
        return $this->checkPermission($adminId, static::SECTION_ADMIN_DASHBOARD, static::PRIVILEGE_WRITE, $returnResult);
    }

    public function canViewFaqCategories($adminId = 0, $returnResult = false)
    {
        return $this->checkPermission($adminId, static::SECTION_FAQ_CATEGORY, static::PRIVILEGE_READ, $returnResult);
    }

    public function canEditFaqCategories($adminId = 0, $returnResult = false)
    {
        return $this->checkPermission($adminId, static::SECTION_FAQ_CATEGORY, static::PRIVILEGE_WRITE, $returnResult);
    }

    public function canViewFaq($adminId = 0, $returnResult = false)
    {
        return $this->checkPermission($adminId, static::SECTION_FAQ, static::PRIVILEGE_READ, $returnResult);
    }

    public function canEditFaq($adminId = 0, $returnResult = false)
    {
        return $this->checkPermission($adminId, static::SECTION_FAQ, static::PRIVILEGE_WRITE, $returnResult);
    }

    public function canViewUrlRewrite($adminId = 0, $returnResult = false)
    {
        return $this->checkPermission($adminId, static::SECTION_URL_REWRITE, static::PRIVILEGE_READ, $returnResult);
    }

    public function canEditUrlRewrite($adminId = 0, $returnResult = false)
    {
        return $this->checkPermission($adminId, static::SECTION_URL_REWRITE, static::PRIVILEGE_WRITE, $returnResult);
    }

    public function canViewImageAttributes($adminId = 0, $returnResult = false)
    {
        return $this->checkPermission($adminId, static::SECTION_IMAGE_ATTRIBUTES, static::PRIVILEGE_READ, $returnResult);
    }

    public function canEditImageAttributes($adminId = 0, $returnResult = false)
    {
        return $this->checkPermission($adminId, static::SECTION_IMAGE_ATTRIBUTES, static::PRIVILEGE_WRITE, $returnResult);
    }

    public function canViewTestimonial($adminId = 0, $returnResult = false)
    {
        return $this->checkPermission($adminId, static::SECTION_TESTIMONIAL, static::PRIVILEGE_READ, $returnResult);
    }

    public function canEditTestimonial($adminId = 0, $returnResult = false)
    {
        return $this->checkPermission($adminId, static::SECTION_TESTIMONIAL, static::PRIVILEGE_WRITE, $returnResult);
    }

    public function canViewSuccessStories($adminId = 0, $returnResult = false)
    {
        return $this->checkPermission($adminId, static::SECTION_SUCCESS_STORIES, static::PRIVILEGE_READ, $returnResult);
    }

    public function canEditSuccessStories($adminId = 0, $returnResult = false)
    {
        return $this->checkPermission($adminId, static::SECTION_SUCCESS_STORIES, static::PRIVILEGE_WRITE, $returnResult);
    }

    public function canViewRecomendedWeightages($adminId = 0, $returnResult = false)
    {
        return $this->checkPermission($adminId, static::SMART_RECOMENDED_WEIGHTAGES, static::PRIVILEGE_READ, $returnResult);
    }

    public function canEditRecomendedWeightages($adminId = 0, $returnResult = false)
    {
        return $this->checkPermission($adminId, static::SMART_RECOMENDED_WEIGHTAGES, static::PRIVILEGE_WRITE, $returnResult);
    }

    public function canViewRecomendedTagProducts($adminId = 0, $returnResult = false)
    {
        return $this->checkPermission($adminId, static::SMART_PRODUCT_TAG_PRODUCTS, static::PRIVILEGE_READ, $returnResult);
    }

    public function canEditRecomendedTagProducts($adminId = 0, $returnResult = false)
    {
        return $this->checkPermission($adminId, static::SMART_PRODUCT_TAG_PRODUCTS, static::PRIVILEGE_WRITE, $returnResult);
    }

    public function canViewAdminPermissions($adminId = 0, $returnResult = false)
    {
        return $this->checkPermission($adminId, static::SECTION_ADMIN_PERMISSIONS, static::PRIVILEGE_READ, $returnResult);
    }

    public function canEditAdminPermissions($adminId = 0, $returnResult = false)
    {
        return $this->checkPermission($adminId, static::SECTION_ADMIN_PERMISSIONS, static::PRIVILEGE_WRITE, $returnResult);
    }

    public function canViewBlogPostCategories($adminId = 0, $returnResult = false)
    {
        return $this->checkPermission($adminId, static::SECTION_BLOG_POST_CATEGORIES, static::PRIVILEGE_READ, $returnResult);
    }

    public function canEditBlogPostCategories($adminId = 0, $returnResult = false)
    {
        return $this->checkPermission($adminId, static::SECTION_BLOG_POST_CATEGORIES, static::PRIVILEGE_WRITE, $returnResult);
    }

    public function canViewBlogPosts($adminId = 0, $returnResult = false)
    {
        return $this->checkPermission($adminId, static::SECTION_BLOG_POSTS, static::PRIVILEGE_READ, $returnResult);
    }

    public function canEditBlogPosts($adminId = 0, $returnResult = false)
    {
        return $this->checkPermission($adminId, static::SECTION_BLOG_POSTS, static::PRIVILEGE_WRITE, $returnResult);
    }

    public function canViewDiscountCoupons($adminId = 0, $returnResult = false)
    {
        return $this->checkPermission($adminId, static::SECTION_DISCOUNT_COUPONS, static::PRIVILEGE_READ, $returnResult);
    }

    public function canEditDiscountCoupons($adminId = 0, $returnResult = false)
    {
        return $this->checkPermission($adminId, static::SECTION_DISCOUNT_COUPONS, static::PRIVILEGE_WRITE, $returnResult);
    }

    public function canViewBlogContributions($adminId = 0, $returnResult = false)
    {
        return $this->checkPermission($adminId, static::SECTION_BLOG_CONTRIBUTIONS, static::PRIVILEGE_READ, $returnResult);
    }

    public function canEditBlogContributions($adminId = 0, $returnResult = false)
    {
        return $this->checkPermission($adminId, static::SECTION_BLOG_CONTRIBUTIONS, static::PRIVILEGE_WRITE, $returnResult);
    }

    public function canViewBlogComments($adminId = 0, $returnResult = false)
    {
        return $this->checkPermission($adminId, static::SECTION_BLOG_COMMENTS, static::PRIVILEGE_READ, $returnResult);
    }

    public function canEditBlogComments($adminId = 0, $returnResult = false)
    {
        return $this->checkPermission($adminId, static::SECTION_BLOG_COMMENTS, static::PRIVILEGE_WRITE, $returnResult);
    }

    public function canViewSellerProducts($adminId = 0, $returnResult = false)
    {
        return $this->checkPermission($adminId, static::SECTION_SELLER_PRODUCTS, static::PRIVILEGE_READ, $returnResult);
    }

    public function canEditSellerProducts($adminId = 0, $returnResult = false)
    {
        return $this->checkPermission($adminId, static::SECTION_SELLER_PRODUCTS, static::PRIVILEGE_WRITE, $returnResult);
    }

    public function canViewProductReviews($adminId = 0, $returnResult = false)
    {
        return $this->checkPermission($adminId, static::SECTION_PRODUCT_REVIEWS, static::PRIVILEGE_READ, $returnResult);
    }

    public function canEditProductReviews($adminId = 0, $returnResult = false)
    {
        return $this->checkPermission($adminId, static::SECTION_PRODUCT_REVIEWS, static::PRIVILEGE_WRITE, $returnResult);
    }

    public function canViewAbusiveWords($adminId = 0, $returnResult = false)
    {
        return $this->checkPermission($adminId, static::SECTION_ABUSIVE_WORDS, static::PRIVILEGE_READ, $returnResult);
    }

    public function canEditAbusiveWords($adminId = 0, $returnResult = false)
    {
        return $this->checkPermission($adminId, static::SECTION_ABUSIVE_WORDS, static::PRIVILEGE_WRITE, $returnResult);
    }

    public function canViewQuestionBanks($adminId = 0, $returnResult = false)
    {
        return $this->checkPermission($adminId, static::SECTION_QUESTION_BANKS, static::PRIVILEGE_READ, $returnResult);
    }

    public function canEditQuestionBanks($adminId = 0, $returnResult = false)
    {
        return $this->checkPermission($adminId, static::SECTION_QUESTION_BANKS, static::PRIVILEGE_WRITE, $returnResult);
    }

    public function canViewMessages($adminId = 0, $returnResult = false)
    {
        return $this->checkPermission($adminId, static::SECTION_MESSAGES, static::PRIVILEGE_READ, $returnResult);
    }

    public function canEditPolling($adminId = 0, $returnResult = false)
    {
        return $this->checkPermission($adminId, static::SECTION_POLLING, static::PRIVILEGE_WRITE, $returnResult);
    }

    public function canViewPolling($adminId = 0, $returnResult = false)
    {
        return $this->checkPermission($adminId, static::SECTION_POLLING, static::PRIVILEGE_READ, $returnResult);
    }

    public function canEditQuestions($adminId = 0, $returnResult = false)
    {
        return $this->checkPermission($adminId, static::SECTION_QUESTIONS, static::PRIVILEGE_WRITE, $returnResult);
    }

    public function canViewQuestions($adminId = 0, $returnResult = false)
    {
        return $this->checkPermission($adminId, static::SECTION_QUESTIONS, static::PRIVILEGE_READ, $returnResult);
    }

    public function canEditQuestionnaires($adminId = 0, $returnResult = false)
    {
        return $this->checkPermission($adminId, static::SECTION_QUESTIONNAIRES, static::PRIVILEGE_WRITE, $returnResult);
    }

    public function canViewQuestionnaires($adminId = 0, $returnResult = false)
    {
        return $this->checkPermission($adminId, static::SECTION_QUESTIONNAIRES, static::PRIVILEGE_READ, $returnResult);
    }

    public function canEditSalesReport($adminId = 0, $returnResult = false)
    {
        return $this->checkPermission($adminId, static::SECTION_SALES_REPORT, static::PRIVILEGE_WRITE, $returnResult);
    }

    public function canViewSalesReport($adminId = 0, $returnResult = false)
    {
        return $this->checkPermission($adminId, static::SECTION_SALES_REPORT, static::PRIVILEGE_READ, $returnResult);
    }

    public function canEditUsersReport($adminId = 0, $returnResult = false)
    {
        return $this->checkPermission($adminId, static::SECTION_USERS_REPORT, static::PRIVILEGE_WRITE, $returnResult);
    }

    public function canViewUsersReport($adminId = 0, $returnResult = false)
    {
        return $this->checkPermission($adminId, static::SECTION_USERS_REPORT, static::PRIVILEGE_READ, $returnResult);
    }
    public function canViewSubscriptionReport($adminId = 0, $returnResult = false)
    {
        return $this->checkPermission($adminId, static::SECTION_SUBSCRIPTION_REPORT, static::PRIVILEGE_READ, $returnResult);
    }
    public function canViewFinancialReport($adminId = 0, $returnResult = false)
    {
        return $this->checkPermission($adminId, static::SECTION_FINANCIAL_REPORT, static::PRIVILEGE_READ, $returnResult);
    }
    public function canViewOrdersReport($adminId = 0, $returnResult = false)
    {
        return $this->checkPermission($adminId, static::SECTION_ORDERS_REPORT, static::PRIVILEGE_READ, $returnResult);
    }

    public function canViewBuyersReport($adminId = 0, $returnResult = false)
    {
        return $this->checkPermission($adminId, static::SECTION_BUYERS_REPORT, static::PRIVILEGE_READ, $returnResult);
    }

    public function canViewSellersReport($adminId = 0, $returnResult = false)
    {
        return $this->checkPermission($adminId, static::SECTION_SELLERS_REPORT, static::PRIVILEGE_READ, $returnResult);
    }

    public function canEditProductsReport($adminId = 0, $returnResult = false)
    {
        return $this->checkPermission($adminId, static::SECTION_PRODUCTS_REPORT, static::PRIVILEGE_WRITE, $returnResult);
    }

    public function canViewProductsReport($adminId = 0, $returnResult = false)
    {
        return $this->checkPermission($adminId, static::SECTION_PRODUCTS_REPORT, static::PRIVILEGE_READ, $returnResult);
    }

    public function canEditCatalogReport($adminId = 0, $returnResult = false)
    {
        return $this->checkPermission($adminId, static::SECTION_CATALOG_REPORT, static::PRIVILEGE_WRITE, $returnResult);
    }

    public function canViewCatalogReport($adminId = 0, $returnResult = false)
    {
        return $this->checkPermission($adminId, static::SECTION_CATALOG_REPORT, static::PRIVILEGE_READ, $returnResult);
    }

    public function canEditShopsReport($adminId = 0, $returnResult = false)
    {
        return $this->checkPermission($adminId, static::SECTION_SHOPS_REPORT, static::PRIVILEGE_WRITE, $returnResult);
    }

    public function canViewShopsReport($adminId = 0, $returnResult = false)
    {
        return $this->checkPermission($adminId, static::SECTION_SHOPS_REPORT, static::PRIVILEGE_READ, $returnResult);
    }

    public function canEditTaxReport($adminId = 0, $returnResult = false)
    {
        return $this->checkPermission($adminId, static::SECTION_TAX_REPORT, static::PRIVILEGE_WRITE, $returnResult);
    }

    public function canViewTaxReport($adminId = 0, $returnResult = false)
    {
        return $this->checkPermission($adminId, static::SECTION_TAX_REPORT, static::PRIVILEGE_READ, $returnResult);
    }

    public function canEditCommissionReport($adminId = 0, $returnResult = false)
    {
        return $this->checkPermission($adminId, static::SECTION_COMMISSION_REPORT, static::PRIVILEGE_WRITE, $returnResult);
    }

    public function canViewCommissionReport($adminId = 0, $returnResult = false)
    {
        return $this->checkPermission($adminId, static::SECTION_COMMISSION_REPORT, static::PRIVILEGE_READ, $returnResult);
    }

    public function canEditPerformanceReport($adminId = 0, $returnResult = false)
    {
        return $this->checkPermission($adminId, static::SECTION_PERFORMANCE_REPORT, static::PRIVILEGE_WRITE, $returnResult);
    }

    public function canViewPerformanceReport($adminId = 0, $returnResult = false)
    {
        return $this->checkPermission($adminId, static::SECTION_PERFORMANCE_REPORT, static::PRIVILEGE_READ, $returnResult);
    }

    public function canEditPolicyPoints($adminId = 0, $returnResult = false)
    {
        return $this->checkPermission($adminId, static::SECTION_POLICY_POINTS, static::PRIVILEGE_WRITE, $returnResult);
    }

    public function canViewPolicyPoints($adminId = 0, $returnResult = false)
    {
        return $this->checkPermission($adminId, static::SECTION_POLICY_POINTS, static::PRIVILEGE_READ, $returnResult);
    }
    public function canEditSellerPackages($adminId = 0, $returnResult = false)
    {
        return $this->checkPermission($adminId, static::SECTION_SELLER_PACKAGES, static::PRIVILEGE_WRITE, $returnResult);
    }
    public function canViewSellerPackages($adminId = 0, $returnResult = false)
    {
        return $this->checkPermission($adminId, static::SECTION_SELLER_PACKAGES, static::PRIVILEGE_READ, $returnResult);
    }
    public function canEditSellerDiscountCoupons($adminId = 0, $returnResult = false)
    {
        return $this->checkPermission($adminId, static::SECTION_SELLER_DISCOUNT_COUPONS, static::PRIVILEGE_WRITE, $returnResult);
    }
    public function canViewSellerDiscountCoupons($adminId = 0, $returnResult = false)
    {
        return $this->checkPermission($adminId, static::SECTION_SELLER_DISCOUNT_COUPONS, static::PRIVILEGE_READ, $returnResult);
    }
    public function canEditThemeColor($adminId = 0, $returnResult = false)
    {
        return $this->checkPermission($adminId, static::SECTION_THEME_COLOR, static::PRIVILEGE_WRITE, $returnResult);
    }
    public function canViewThemeColor($adminId = 0, $returnResult = false)
    {
        return $this->checkPermission($adminId, static::SECTION_THEME_COLOR, static::PRIVILEGE_READ, $returnResult);
    }
    public function canViewProductTempImages($adminId = 0, $returnResult = false)
    {
        return $this->checkPermission($adminId, static::SECTION_PRODUCT_TEMP_IMAGES, static::PRIVILEGE_READ, $returnResult);
    }
    public function canEditProductTempImages($adminId = 0, $returnResult = false)
    {
        return $this->checkPermission($adminId, static::SECTION_PRODUCT_TEMP_IMAGES, static::PRIVILEGE_WRITE, $returnResult);
    }
    public function canUploadBulkImages($adminId = 0, $returnResult = false)
    {
        return $this->checkPermission($adminId, static::SECTION_UPLOAD_BULK_IMAGES, static::PRIVILEGE_WRITE, $returnResult);
    }
    public function canViewImportInstructions($adminId = 0, $returnResult = false)
    {
        return $this->checkPermission($adminId, static::SECTION_IMPORT_INSTRUCTIONS, static::PRIVILEGE_READ, $returnResult);
    }
    public function canEditImportInstructions($adminId = 0, $returnResult = false)
    {
        return $this->checkPermission($adminId, static::SECTION_IMPORT_INSTRUCTIONS, static::PRIVILEGE_WRITE, $returnResult);
    }
    public function canEditSubscriptionOrders($adminId = 0, $returnResult = false)
    {
        return $this->checkPermission($adminId, static::SECTION_SUBSCRIPTION_ORDERS, static::PRIVILEGE_WRITE, $returnResult);
    }
    public function canViewSubscriptionOrders($adminId = 0, $returnResult = false)
    {
        return $this->checkPermission($adminId, static::SECTION_SUBSCRIPTION_ORDERS, static::PRIVILEGE_READ, $returnResult);
    }
    public function canViewTools($adminId = 0, $returnResult = false)
    {
        return $this->checkPermission($adminId, static::SECTION_TOOLS, static::PRIVILEGE_READ, $returnResult);
    }

    public function canViewAffiliateCommissionSettings($adminId = 0, $returnResult = false)
    {
        return $this->checkPermission($adminId, static::SECTION_AFFILIATE_COMMISSION, static::PRIVILEGE_READ, $returnResult);
    }

    public function canEditAffiliateCommissionSettings($adminId = 0, $returnResult = false)
    {
        return $this->checkPermission($adminId, static::SECTION_AFFILIATE_COMMISSION, static::PRIVILEGE_WRITE, $returnResult);
    }

    public function canViewPromotions($adminId = 0, $returnResult = false)
    {
        return $this->checkPermission($adminId, static::SECTION_PROMOTIONS, static::PRIVILEGE_READ, $returnResult);
    }

    public function canEditPromotions($adminId = 0, $returnResult = false)
    {
        return $this->checkPermission($adminId, static::SECTION_PROMOTIONS, static::PRIVILEGE_WRITE, $returnResult);
    }

    public function canViewAffiliatesReport($adminId = 0, $returnResult = false)
    {
        return $this->checkPermission($adminId, static::SECTION_AFFILIATES_REPORT, static::PRIVILEGE_READ, $returnResult);
    }

    public function canEditAffiliatesReport($adminId = 0, $returnResult = false)
    {
        return $this->checkPermission($adminId, static::SECTION_AFFILIATES_REPORT, static::PRIVILEGE_WRITE, $returnResult);
    }

    public function canViewAdvertisersReport($adminId = 0, $returnResult = false)
    {
        return $this->checkPermission($adminId, static::SECTION_ADVERTISERS_REPORT, static::PRIVILEGE_READ, $returnResult);
    }

    public function canEditAdvertisersReport($adminId = 0, $returnResult = false)
    {
        return $this->checkPermission($adminId, static::SECTION_ADVERTISERS_REPORT, static::PRIVILEGE_WRITE, $returnResult);
    }
    public function canViewBrandRequests($adminId = 0, $returnResult = false)
    {
        return $this->checkPermission($adminId, static::SECTION_BRAND_REQUESTS, static::PRIVILEGE_READ, $returnResult);
    }

    public function canEditBrandRequests($adminId = 0, $returnResult = false)
    {
        return $this->checkPermission($adminId, static::SECTION_BRAND_REQUESTS, static::PRIVILEGE_WRITE, $returnResult);
    }

    public function canViewShippingCompanyUsers($adminId = 0, $returnResult = false)
    {
        return $this->checkPermission($adminId, static::SECTION_SHIPPING_COMPANY_USERS, static::PRIVILEGE_READ, $returnResult);
    }

    public function canEditShippingCompanyUsers($adminId = 0, $returnResult = false)
    {
        return $this->checkPermission($adminId, static::SECTION_SHIPPING_COMPANY_USERS, static::PRIVILEGE_WRITE, $returnResult);
    }

    public function canViewRewardsOnPurchase($adminId = 0, $returnResult = false)
    {
        return $this->checkPermission($adminId, static::SECTION_REWARDS_ON_PURCHASE, static::PRIVILEGE_READ, $returnResult);
    }

    public function canEditRewardsOnPurchase($adminId = 0, $returnResult = false)
    {
        return $this->checkPermission($adminId, static::SECTION_REWARDS_ON_PURCHASE, static::PRIVILEGE_WRITE, $returnResult);
    }

    public function canViewLanguage($adminId = 0, $returnResult = false)
    {
        return $this->checkPermission($adminId, static::SECTION_LANGUAGE, static::PRIVILEGE_READ, $returnResult);
    }

    public function canEditLanguage($adminId = 0, $returnResult = false)
    {
        return $this->checkPermission($adminId, static::SECTION_LANGUAGE, static::PRIVILEGE_WRITE, $returnResult);
    }

    public function canViewOrderStatus($adminId = 0, $returnResult = false)
    {
        return $this->checkPermission($adminId, static::SECTION_ORDER_STATUS, static::PRIVILEGE_READ, $returnResult);
    }

    public function canEditOrderStatus($adminId = 0, $returnResult = false)
    {
        return $this->checkPermission($adminId, static::SECTION_ORDER_STATUS, static::PRIVILEGE_WRITE, $returnResult);
    }

    public function canViewNotifications($adminId = 0, $returnResult = false)
    {
        // return $this->checkPermission($adminId, static::SECTION_NOTIFICATION, static::PRIVILEGE_READ, $returnResult);
        return true;
    }

    public function canEditNotifications($adminId = 0, $returnResult = false)
    {
        // return $this->checkPermission($adminId, static::SECTION_NOTIFICATION, static::PRIVILEGE_WRITE, $returnResult);
        return true;
    }

    public function canViewTooltip($adminId = 0, $returnResult = false)
    {
        return $this->checkPermission($adminId, static::SECTION_TOOLTIP, static::PRIVILEGE_READ, $returnResult);
    }

    public function canEditTooltip($adminId = 0, $returnResult = false)
    {
        return $this->checkPermission($adminId, static::SECTION_TOOLTIP, static::PRIVILEGE_WRITE, $returnResult);
    }

    public function canViewCustomProductRequests($adminId = 0, $returnResult = false)
    {
        return $this->checkPermission($adminId, static::SECTION_CUSTOM_PRODUCT_REQUESTS, static::PRIVILEGE_READ, $returnResult);
    }

    public function canEditCustomProductRequests($adminId = 0, $returnResult = false)
    {
        return $this->checkPermission($adminId, static::SECTION_CUSTOM_PRODUCT_REQUESTS, static::PRIVILEGE_WRITE, $returnResult);
    }

    public function canViewCustomCatalogProductRequests($adminId = 0, $returnResult = false)
    {
        return $this->checkPermission($adminId, static::SECTION_CUSTOM_CATALOG_PRODUCT_REQUESTS, static::PRIVILEGE_READ, $returnResult);
    }

    public function canEditCustomCatalogProductRequests($adminId = 0, $returnResult = false)
    {
        return $this->checkPermission($adminId, static::SECTION_CUSTOM_CATALOG_PRODUCT_REQUESTS, static::PRIVILEGE_WRITE, $returnResult);
    }

    public function canViewDatabaseBackupView($adminId = 0, $returnResult = false)
    {
        return $this->checkPermission($adminId, static::SECTION_DATABASE_BACKUP, static::PRIVILEGE_READ, $returnResult);
    }

    public function canEditDatabaseBackupView($adminId = 0, $returnResult = false)
    {
        return $this->checkPermission($adminId, static::SECTION_DATABASE_BACKUP, static::PRIVILEGE_WRITE, $returnResult);
    }

    public function canViewUserRequests($adminId = 0, $returnResult = false)
    {
        return $this->checkPermission($adminId, static::SECTION_USER_REQUESTS, static::PRIVILEGE_READ, $returnResult);
    }

    public function canEditUserRequests($adminId = 0, $returnResult = false)
    {
        return $this->checkPermission($adminId, static::SECTION_USER_REQUESTS, static::PRIVILEGE_WRITE, $returnResult);
    }

    public function canViewSitemap($adminId = 0, $returnResult = false)
    {
        return $this->checkPermission($adminId, static::SECTION_SITEMAP, static::PRIVILEGE_READ, $returnResult);
    }

    public function canEditSitemap($adminId = 0, $returnResult = false)
    {
        return $this->checkPermission($adminId, static::SECTION_SITEMAP, static::PRIVILEGE_WRITE, $returnResult);
    }

    public function canViewPushNotification($adminId = 0, $returnResult = false)
    {
        return $this->checkPermission($adminId, static::SECTION_PUSH_NOTIFICATION, static::PRIVILEGE_READ, $returnResult);
    }

    public function canEditPushNotification($adminId = 0, $returnResult = false)
    {
        return $this->checkPermission($adminId, static::SECTION_PUSH_NOTIFICATION, static::PRIVILEGE_WRITE, $returnResult);
    }

    public function canViewPlugins($adminId = 0, $returnResult = false)
    {
        return $this->checkPermission($adminId, static::SECTION_PLUGINS, static::PRIVILEGE_READ, $returnResult);
    }

    public function canEditPlugins($adminId = 0, $returnResult = false)
    {
        return $this->checkPermission($adminId, static::SECTION_PLUGINS, static::PRIVILEGE_WRITE, $returnResult);
    }

    public function canViewAbandonedCart($adminId = 0, $returnResult = false)
    {
        return $this->checkPermission($adminId, static::SECTION_ABANDONED_CART, static::PRIVILEGE_READ, $returnResult);
    }

    /* public function canEditAbandonedCart($adminId = 0, $returnResult = false)
    {
        return $this->checkPermission($adminId, static::SECTION_ABANDONED_CART, static::PRIVILEGE_WRITE, $returnResult);
    } */

    public function canViewAdvertisements($adminId = 0, $returnResult = false)
    {
        return $this->checkPermission($adminId, static::SECTION_PRODUCT_ADVERTISEMENT, static::PRIVILEGE_READ, $returnResult);
    }

    public function canEditAdvertisements($adminId = 0, $returnResult = false)
    {
        return $this->checkPermission($adminId, static::SECTION_PRODUCT_ADVERTISEMENT, static::PRIVILEGE_WRITE, $returnResult);
    }

    /*  public function canViewAppThemeSettings($adminId = 0, $returnResult = false)
    {
        return $this->checkPermission($adminId, static::SECTION_APP_THEME_SETTINGS, static::PRIVILEGE_READ, $returnResult);
    }

    public function canEditAppThemeSettings($adminId = 0, $returnResult = false)
    {
        return $this->checkPermission($adminId, static::SECTION_APP_THEME_SETTINGS, static::PRIVILEGE_WRITE, $returnResult);
    } */

    public function canViewImportExport($adminId = 0, $returnResult = false)
    {
        return $this->checkPermission($adminId, static::SECTION_IMPORT_EXPORT, static::PRIVILEGE_READ, $returnResult);
    }

    public function canEditImportExport($adminId = 0, $returnResult = false)
    {
        return $this->checkPermission($adminId, static::SECTION_IMPORT_EXPORT, static::PRIVILEGE_WRITE, $returnResult);
    }

    public function canViewPatch($adminId = 0, $returnResult = false)
    {
        return $this->checkPermission($adminId, static::SECTION_PATCH_UPDATE, static::PRIVILEGE_READ, $returnResult);
    }

    public function canEditPatch($adminId = 0, $returnResult = false)
    {
        return $this->checkPermission($adminId, static::SECTION_PATCH_UPDATE, static::PRIVILEGE_WRITE, $returnResult);
    }

    public function canEditSmsTemplate($adminId = 0, $returnResult = false)
    {
        return $this->checkPermission($adminId, static::SECTION_SMS_TEMPLATE, static::PRIVILEGE_WRITE, $returnResult);
    }

    public function canViewSmsTemplate($adminId = 0, $returnResult = false)
    {
        return $this->checkPermission($adminId, static::SECTION_SMS_TEMPLATE, static::PRIVILEGE_READ, $returnResult);
    }

    public function canViewShippingPackages($adminId = 0, $returnResult = false)
    {
        return $this->checkPermission($adminId, static::SECTION_SHIPPING_PACKAGES, static::PRIVILEGE_READ, $returnResult);
    }

    public function canEditShippingPackages($adminId = 0, $returnResult = false)
    {
        return $this->checkPermission($adminId, static::SECTION_SHIPPING_PACKAGES, static::PRIVILEGE_WRITE, $returnResult);
    }

    public function canViewShippingManagement($adminId = 0, $returnResult = false)
    {
        return $this->checkPermission($adminId, static::SECTION_SHIPPING_MANAGEMENT, static::PRIVILEGE_READ, $returnResult);
    }

    public function canEditShippingManagement($adminId = 0, $returnResult = false)
    {
        return $this->checkPermission($adminId, static::SECTION_SHIPPING_MANAGEMENT, static::PRIVILEGE_WRITE, $returnResult);
    }

    public function canViewPickupAddresses($adminId = 0, $returnResult = false)
    {
        return $this->checkPermission($adminId, static::SECTION_PICKUP_ADDRESSES, static::PRIVILEGE_READ, $returnResult);
    }

    public function canEditPickupAddresses($adminId = 0, $returnResult = false)
    {
        return $this->checkPermission($adminId, static::SECTION_PICKUP_ADDRESSES, static::PRIVILEGE_WRITE, $returnResult);
    }

    public function canViewShippedProducts($adminId = 0, $returnResult = false)
    {
        return $this->checkPermission($adminId, static::SECTION_SHIPPED_PRODUCTS_LISTING, static::PRIVILEGE_READ, $returnResult);
    }

    public function canEditShippedProducts($adminId = 0, $returnResult = false)
    {
        return $this->checkPermission($adminId, static::SECTION_SHIPPED_PRODUCTS_LISTING, static::PRIVILEGE_WRITE, $returnResult);
    }

    public function canViewTrackingRelationCode()
    {
        $plugin = new Plugin();
        $shipApiPluginKey = $plugin->getDefaultPluginKeyName(Plugin::TYPE_SHIPPING_SERVICES);
        $trackingApiPluginKey = $plugin->getDefaultPluginKeyName(Plugin::TYPE_SHIPMENT_TRACKING);
        if (Plugin::isActive($shipApiPluginKey) === true && Plugin::isActive($trackingApiPluginKey) === true) {
            return true;
        }
        return false;
    }

    public function canViewRatingTypes($adminId = 0, $returnResult = false)
    {
        return $this->checkPermission($adminId, static::SECTION_RATING_TYPES, static::PRIVILEGE_READ, $returnResult);
    }

    public function canEditRatingTypes($adminId = 0, $returnResult = false)
    {
        return $this->checkPermission($adminId, static::SECTION_RATING_TYPES, static::PRIVILEGE_WRITE, $returnResult);
    }

    public function canViewBadgesAndRibbons($adminId = 0, $returnResult = false)
    {
        return $this->checkPermission($adminId, static::SECTION_BADGES, static::PRIVILEGE_READ, $returnResult);
    }

    public function canEditBadgesAndRibbons($adminId = 0, $returnResult = false)
    {
        return $this->checkPermission($adminId, static::SECTION_BADGES, static::PRIVILEGE_WRITE, $returnResult);
    }

    public function canViewBadgeLinks($adminId = 0, $returnResult = false)
    {
        return $this->checkPermission($adminId, static::SECTION_BADGE_LINKS, static::PRIVILEGE_READ, $returnResult);
    }

    public function canEditBadgeLinks($adminId = 0, $returnResult = false)
    {
        return $this->checkPermission($adminId, static::SECTION_BADGE_LINKS, static::PRIVILEGE_WRITE, $returnResult);
    }

    public function canViewBadgeRequests($adminId = 0, $returnResult = false)
    {
        return $this->checkPermission($adminId, static::SECTION_BADGE_REQUESTS, static::PRIVILEGE_READ, $returnResult);
    }

    public function canEditBadgeRequests($adminId = 0, $returnResult = false)
    {
        return $this->checkPermission($adminId, static::SECTION_BADGE_REQUESTS, static::PRIVILEGE_WRITE, $returnResult);
    }

    public function canViewSystemLog($adminId = 0, $returnResult = false)
    {
        return $this->checkPermission($adminId, static::SECTION_SYSTEMLOG, static::PRIVILEGE_READ, $returnResult);
    }

    public function canEditSystemLog($adminId = 0, $returnResult = false)
    {
        return $this->checkPermission($adminId, static::SECTION_SYSTEMLOG, static::PRIVILEGE_WRITE, $returnResult);
    }

    public function canViewSettings($adminId = 0, $returnResult = false)
    {
        return $this->checkPermission($adminId, static::SECTION_SETTINGS, static::PRIVILEGE_READ, $returnResult);
    }

    public function canEditSettings($adminId = 0, $returnResult = false)
    {
        return $this->checkPermission($adminId, static::SECTION_SETTINGS, static::PRIVILEGE_WRITE, $returnResult);
    }

    public function canViewPagesLanguageData($adminId = 0, $returnResult = false)
    {
        return $this->checkPermission($adminId, static::SECTION_PAGES_LANGUAGE_DATA, static::PRIVILEGE_READ, $returnResult);
    }

    public function canEditPagesLanguageData($adminId = 0, $returnResult = false)
    {
        return $this->checkPermission($adminId, static::SECTION_PAGES_LANGUAGE_DATA, static::PRIVILEGE_WRITE, $returnResult);
    }

    public function canViewCategoryRequests($adminId = 0, $returnResult = false)
    {
        return $this->checkPermission($adminId, static::SECTION_CATEGORY_REQUEST, static::PRIVILEGE_READ, $returnResult);
    }

    public function canEditCategoryRequests($adminId = 0, $returnResult = false)
    {
        return $this->checkPermission($adminId, static::SECTION_CATEGORY_REQUEST, static::PRIVILEGE_WRITE, $returnResult);
    }

    public function canViewGettingStarted($adminId = 0, $returnResult = false)
    {
        return $this->checkPermission($adminId, static::SECTION_GETTING_STARTED, static::PRIVILEGE_READ, $returnResult);
    }

    public function canViewRequestForQuote($adminId = 0, $returnResult = false)
    {
        return $this->checkPermission($adminId, static::SECTION_REQUEST_FOR_QUOTE, static::PRIVILEGE_READ, $returnResult);
    }

    public function canEditRequestForQuote($adminId = 0, $returnResult = false)
    {
        return $this->checkPermission($adminId, static::SECTION_REQUEST_FOR_QUOTE, static::PRIVILEGE_WRITE, $returnResult);
    }

    public function canViewRfqOffers($adminId = 0, $returnResult = false)
    {
        return $this->checkPermission($adminId, static::SECTION_RFQ_OFFERS, static::PRIVILEGE_READ, $returnResult);
    }

    public function canEditRfqOffers($adminId = 0, $returnResult = false)
    {
        return $this->checkPermission($adminId, static::SECTION_RFQ_OFFERS, static::PRIVILEGE_WRITE, $returnResult);
    }
    public function canViewAppReleaseVersions($adminId = 0, $returnResult = false)
    {
        return $this->checkPermission($adminId, static::SECTION_APP_RELEASE, static::PRIVILEGE_READ, $returnResult);
    }

    public function canEditAppReleaseVersions($adminId = 0, $returnResult = false)
    {
        return $this->checkPermission($adminId, static::SECTION_APP_RELEASE, static::PRIVILEGE_WRITE, $returnResult);
    }

    public function canViewAdminLoginHistory($adminId = 0, $returnResult = false)
    {
        return $this->checkPermission($adminId, static::SECTION_ADMIN_LOGIN_HISTORY, static::PRIVILEGE_READ, $returnResult);
    }

    public function canEditAdminLoginHistory($adminId = 0, $returnResult = false)
    {
        return $this->checkPermission($adminId, static::SECTION_ADMIN_LOGIN_HISTORY, static::PRIVILEGE_WRITE, $returnResult);
    }
}
