<?php

class Shop extends MyAppModel
{
    public const DB_TBL = 'tbl_shops';
    public const DB_TBL_PREFIX = 'shop_';

    public const DB_TBL_LANG = 'tbl_shops_lang';
    public const DB_TBL_LANG_PREFIX = 'shoplang_';

    public const DB_TBL_STATS = 'tbl_shop_stats';
    public const DB_TBL_STATS_PREFIX = 'sstats_';

    public const DB_TBL_SHOP_FAVORITE = 'tbl_user_favourite_shops';
    public const DB_TBL_SHOP_THEME_COLOR = 'tbl_shops_to_theme';

    public const FILETYPE_SHOP_LOGO = 1;
    public const FILETYPE_SHOP_BANNER = 2;
    public const TEMPLATE_ONE = 10001;
    public const TEMPLATE_TWO = 10002;
    public const TEMPLATE_THREE = 10003;
    public const TEMPLATE_FOUR = 10004;
    public const TEMPLATE_FIVE = 10005;

    public const SHOP_VIEW_ORGINAL_URL = 'shops/view/';
    public const SHOP_REVIEWS_ORGINAL_URL = 'reviews/shop/';
    public const SHOP_POLICY_ORGINAL_URL = 'shops/policy/';
    public const SHOP_SEND_MESSAGE_ORGINAL_URL = 'shops/send-message/';
    public const SHOP_TOP_PRODUCTS_ORGINAL_URL = 'shops/top-products/';
    public const SHOP_COLLECTION_ORGINAL_URL = 'shops/collection/';

    public const USE_SHOP_POLICY = 1;
    public const SHOP_PRODUCTS_COUNT_AT_HOMEPAGE = 2;

    public const GOVT_INFO_LEN = 255;

    private $userId = 0;
    private $langId = 0;
    private $active = null;
    private $data = null;

    private static $sellerListingForRfq = false;

    /**
     * __construct
     *
     * @param  int $shopId
     * @param  int $userId
     * @return void
     */
    public function __construct(int $shopId, int $userId = 0, int $langId = 0)
    {
        if (0 < $shopId) {
            $this->userId = $this->getUserId();
        }

        if (1 > $shopId && 0 < $userId) {
            $this->userId = $userId;
            $shopId = $this->getIdFromUserId();
        }

        $this->langId = $langId;

        parent::__construct(static::DB_TBL, static::DB_TBL_PREFIX . 'id', $shopId);
        $this->objMainTableRecord->setSensitiveFields(array());
    }

    public static function getSearchObject($isActive = true, $langId = 0, $joinSpecifics = false)
    {
        $langId = FatUtility::int($langId);

        $srch = new SearchBase(static::DB_TBL, 's');

        if ($isActive == true) {
            $srch->addCondition(static::tblFld('active'), '=', 'mysql_func_' . applicationConstants::ACTIVE, 'AND', true);
        }

        if ($langId > 0) {
            $srch->joinTable(
                static::DB_TBL_LANG,
                'LEFT OUTER JOIN',
                's_l.' . static::DB_TBL_LANG_PREFIX . 'shop_id = s.' . static::tblFld('id') . ' and
                s_l.' . static::DB_TBL_LANG_PREFIX . 'lang_id = ' . $langId,
                's_l'
            );
        }

        if (true === $joinSpecifics) {
            $srch->joinTable(
                ShopSpecifics::DB_TBL,
                'LEFT OUTER JOIN',
                'spec.' . ShopSpecifics::DB_TBL_PREFIX . 'shop_id = s.' . static::tblFld('id'),
                'spec'
            );
        }

        return $srch;
    }

    public static function getAttributesByUserId($userId, $attr = null, $isActive = true, $langId = 0)
    {
        $langId = FatUtility::int($langId);
        $userId = FatUtility::int($userId);

        $db = FatApp::getDb();
        $srch = static::getSearchObject($isActive, $langId, true);
        $srch->addCondition(static::tblFld('user_id'), '=', 'mysql_func_' . $userId, 'AND', true);

        if (null != $attr) {
            if (is_array($attr)) {
                $srch->addMultipleFields($attr);
            } elseif (is_string($attr)) {
                $srch->addFld($attr);
            }
        }
        $srch->doNotCalculateRecords();
        $row = $db->fetch($srch->getResultSet());
        if (!is_array($row)) {
            return false;
        }
        if (is_string($attr)) {
            return $row[$attr];
        }
        return $row;
    }

    public static function getAttributesById($recordId, $attr = null, $joinSpecifics = false)
    {
        $recordId = FatUtility::int($recordId);
        $db = FatApp::getDb();

        $srch = new SearchBase(static::DB_TBL, 'ts');
        $srch->doNotCalculateRecords();
        $srch->setPageSize(1);
        $srch->addCondition(static::tblFld('id'), '=', 'mysql_func_' . $recordId, 'AND', true);

        if (true === $joinSpecifics) {
            $srch->joinTable(
                ShopSpecifics::DB_TBL,
                'LEFT OUTER JOIN',
                'ss.' . ShopSpecifics::DB_TBL_PREFIX . 'shop_id = ts.' . static::tblFld('id'),
                'ss'
            );
        }

        if (null != $attr) {
            if (is_array($attr)) {
                $srch->addMultipleFields($attr);
            } elseif (is_string($attr)) {
                $srch->addFld($attr);
            }
        }
        $rs = $srch->getResultSet();
        $row = $db->fetch($rs);
        if (!is_array($row)) {
            return false;
        }

        if (is_string($attr)) {
            return $row[$attr];
        }
        return $row;
    }

    public static function getProdCategoriesObj($userId, $siteLangId, $shopId = 0, $prodcat_id = 0)
    {
        $userId = FatUtility::int($userId);
        $prodcat_id = FatUtility::int($prodcat_id);
        $shopId = FatUtility::int($shopId);

        $srch = new ProductSearch();
        $srch->joinSellerProducts();
        $srch->joinSellers();
        $srch->joinShops();
        $srch->joinProductToCategory($siteLangId);

        $srch->addCondition('selprod_user_id', '=', 'mysql_func_' . $userId, 'AND', true);
        if ($shopId > 0) {
            $srch->addCondition('shop_id', '=', 'mysql_func_' . $shopId, 'AND', true);
        }

        if ($prodcat_id > 0) {
            $srch->addCondition('prodcat_id', '=', 'mysql_func_' . $prodcat_id, 'AND', true);
        }
        $srch->addGroupBy('prodcat_id');
        $srch->addMultipleFields(array('prodcat_id', 'ifnull(prodcat_name,prodcat_identifier) as prodcat_name', 'shop_id'));
        return $srch;
    }

    public static function getShopAddress($shop_id, $isActive = true, $langId = 0, $attr = array())
    {
        $shop_id = FatUtility::int($shop_id);
        $langId = FatUtility::int($langId);
        $db = FatApp::getDb();
        $srch = static::getSearchObject($isActive, $langId);
        $srch->addCondition(static::tblFld('id'), '=', 'mysql_func_' . $shop_id, 'AND', true);
        $srch->joinTable(States::DB_TBL, 'LEFT JOIN', 's.shop_state_id=ss.state_id and ss.state_active=' . applicationConstants::ACTIVE, 'ss');
        $srch->joinTable(Countries::DB_TBL, 'LEFT JOIN', 's.shop_country_id=sc.country_id and sc.country_active=' . applicationConstants::ACTIVE, 'sc');

        if (0 < $langId) {
            $srch->joinTable(States::DB_TBL_LANG, 'LEFT JOIN', 'ss_l.statelang_state_id=ss.state_id and ss_l.statelang_lang_id=' . $langId, 'ss_l');
            $srch->joinTable(Countries::DB_TBL_LANG, 'LEFT JOIN', 'sc_l.countrylang_country_id=sc.country_id and sc_l.countrylang_lang_id=' . $langId, 'sc_l');
        }

        if ($isActive) {
            $srch->addCondition('s.shop_active', '=', 'mysql_func_' . applicationConstants::ACTIVE, 'AND', true);
        }
        $srch->addCondition('s.shop_id', '=', 'mysql_func_' . $shop_id, 'AND', true);
        if (null != $attr) {
            if (is_array($attr)) {
                $srch->addMultipleFields($attr);
            } elseif (is_string($attr)) {
                $srch->addFld($attr);
            }
        }
        $srch->doNotCalculateRecords();
        $row = $db->fetch($srch->getResultSet());
        if (!is_array($row)) {
            return false;
        }
        if (is_string($attr)) {
            return $row[$attr];
        }
        return $row;
    }

    public static function getFilterSearchForm()
    {
        $frm = new Form('frmSearch');
        $frm->addTextBox('', 'keyword');
        $frm->addHiddenField('', 'shop_id');
        $frm->addHiddenField('', 'join_price');
        $frm->addSubmitButton('', 'btnProductSrchSubmit', '');
        return $frm;
    }

    private function _rewriteUrl($keyword, $type = 'shop', $collectionId = 0)
    {
        if ($this->mainTableRecordId < 1) {
            return false;
        }
        $originalUrl = $this->getRewriteOriginalUrl($type, $collectionId);
        $seoUrl = $this->sanitizeSeoUrl($keyword, $type);

        $customUrl = UrlRewrite::getValidSeoUrl($seoUrl, $originalUrl, $this->mainTableRecordId);
        return UrlRewrite::update($originalUrl, $customUrl);
    }

    private function getRewriteOriginalUrl($type = 'shop', $collectionId = 0)
    {
        if ($this->mainTableRecordId < 1) {
            return false;
        }
        switch (strtolower($type)) {
            case 'top-products':
                $originalUrl = Shop::SHOP_TOP_PRODUCTS_ORGINAL_URL . $this->mainTableRecordId;
                break;
            case 'reviews':
                $originalUrl = Shop::SHOP_REVIEWS_ORGINAL_URL . $this->mainTableRecordId;
                break;
            case 'contact':
                $originalUrl = Shop::SHOP_SEND_MESSAGE_ORGINAL_URL . $this->mainTableRecordId;
                break;
            case 'policy':
                $originalUrl = Shop::SHOP_POLICY_ORGINAL_URL . $this->mainTableRecordId;
                break;
            case 'collection':
                $originalUrl = Shop::SHOP_COLLECTION_ORGINAL_URL . $this->mainTableRecordId . '/' . $collectionId;
                break;
            default:
                $originalUrl = Shop::SHOP_VIEW_ORGINAL_URL . $this->mainTableRecordId;
                break;
        }
        return $originalUrl;
    }

    public function sanitizeSeoUrl($keyword, $type = 'shop')
    {
        $seoUrl = CommonHelper::seoUrl($keyword);
        switch (strtolower($type)) {
            case 'top-products':
                $seoUrl = preg_replace('/-top-products$/', '', $seoUrl);
                $seoUrl .= '-top-products';
                break;
            case 'reviews':
                $seoUrl = preg_replace('/-reviews$/', '', $seoUrl);
                $seoUrl .= '-reviews';
                break;
            case 'contact':
                $seoUrl = preg_replace('/-contact$/', '', $seoUrl);
                $seoUrl .= '-contact';
                break;
            case 'policy':
                $seoUrl = preg_replace('/-policy$/', '', $seoUrl);
                $seoUrl .= '-policy';
                break;
            case 'collection':
                $shopUrl = static::getRewriteCustomUrl($this->mainTableRecordId);
                $seoUrl = preg_replace('/-' . $shopUrl . '$/', '', $seoUrl);
                $seoUrl .= '-' . $shopUrl;
                break;
            default:
                break;
        }
        return $seoUrl;
    }

    public function setupCollectionUrl($keyword, $collectionId)
    {
        return $this->_rewriteUrl($keyword, 'collection', $collectionId);
    }

    public function rewriteUrlShop($keyword)
    {
        return $this->_rewriteUrl($keyword);
    }

    public function getRewriteShopOriginalUrl()
    {
        return $this->getRewriteOriginalUrl('shop');
    }

    public function rewriteUrlReviews($keyword)
    {
        return $this->_rewriteUrl($keyword, 'reviews');
    }

    public function rewriteUrlTopProducts($keyword)
    {
        return $this->_rewriteUrl($keyword, 'top-products');
    }

    public function rewriteUrlContact($keyword)
    {
        return $this->_rewriteUrl($keyword, 'contact');
    }

    public function rewriteUrlpolicy($keyword)
    {
        return $this->_rewriteUrl($keyword, 'policy');
    }

    /**
     * setFavorite
     *
     * @param  int $userId
     * @return bool
     */
    public function setFavorite(int $userId): bool
    {
        if (1 > $this->mainTableRecordId || 1 > $userId) {
            return false;
        }

        $data_to_save = array('ufs_user_id' => $userId, 'ufs_shop_id' => $this->mainTableRecordId);
        $data_to_save_on_duplicate = array('ufs_shop_id' => $this->mainTableRecordId);
        if (!FatApp::getDb()->insertFromArray(static::DB_TBL_SHOP_FAVORITE, $data_to_save, false, array(), $data_to_save_on_duplicate)) {
            $this->error = FatApp::getDb()->getError();
            return false;
        }
        return true;
    }

    /**
     * getRewriteCustomUrl
     *
     * @param  int $shopId
     * @return string
     */
    public static function getRewriteCustomUrl(int $shopId = 0): string
    {
        $db = FatApp::getDb();
        $shopOriginalUrl = 'shops/view/' . $shopId;
        $urlSrch = UrlRewrite::getSearchObject();
        $urlSrch->doNotCalculateRecords();
        $urlSrch->doNotLimitRecords();
        $urlSrch->addCondition('urlrewrite_original', '=', $shopOriginalUrl);
        $urlSrch->addFld('urlrewrite_custom');
        $rs = $urlSrch->getResultSet();
        $row = $db->fetch($rs);

        if (!is_array($row)) {
            return false;
        }

        return $row['urlrewrite_custom'];
    }

    /**
     * getName
     *
     * @param  int $shopId
     * @param  int $langId
     * @param  bool $isActive
     * @return string
     */
    public static function getName(int $shopId, int $langId = 0, bool $isActive = true): string
    {
        if (1 > $shopId) {
            return '';
        }

        $srch = static::getSearchObject($isActive, $langId);
        $srch->addMultipleFields(array('IFNULL(shop_name, shop_identifier) as shop_name'));
        $srch->addCondition('shop_id', '=', 'mysql_func_' . $shopId, 'AND', true);
        $srch->setPageSize(1);
        $srch->doNotCalculateRecords();
        $row = FatApp::getDb()->fetch($srch->getResultSet());
        return $row['shop_name'] ?? '';
    }

    /**
     * isActive
     *
     * @return int
     */
    public function isActive(): int
    {
        if (1 > $this->mainTableRecordId) {
            return 0;
        }
        if (null != $this->active) {
            return $this->active;
        }

        if (null != $this->data) {
            return $this->active = $this->data['shop_active'];
        }

        $this->getData();

        if (!empty($this->data)) {
            return $this->active = $this->data['shop_active'];
        }

        return 0;
    }

    /**
     * getData
     *
     * @return array
     */
    public function getData(): array
    {
        if (1 > $this->mainTableRecordId) {
            trigger_error('Shop instance not initialized!', E_USER_ERROR);
        }

        if (null == $this->data || empty($this->data)) {
            $this->setData();
        }

        return $this->data;
    }

    /**
     * setData
     *
     * @return void
     */
    private function setData(): void
    {
        if (1 > $this->mainTableRecordId) {
            trigger_error('Shop instance not initialized!', E_USER_ERROR);
        }

        if (null != $this->data && !empty($this->data)) {
            return;
        }

        $this->data = self::getAttributesById($this->mainTableRecordId);
    }

    /**
     * getIdFromUserId
     *
     * @return int
     */
    private function getIdFromUserId(): int
    {
        if (0 < $this->mainTableRecordId) {
            return  $this->mainTableRecordId;
        }

        if (1 > $this->userId) {
            return 0;
        }

        return self::getAttributesByUserId($this->userId, 'shop_id');
    }

    /**
     * getUserId
     *
     * @return int
     */
    private function getUserId(): int
    {
        if (1 > $this->mainTableRecordId) {
            return  0;
        }

        if (0 < $this->userId) {
            return $this->userId;
        }

        if (null != $this->data) {
            return $this->userId = $this->data['shop_user_id'];
        }

        $this->getData();

        if (!empty($this->data)) {
            return $this->userId = $this->data['shop_user_id'];
        }

        return 0;
    }

    public static function setSellerListingForRfq(bool $flag = false) {
        self::$sellerListingForRfq = $flag;
    }

    public static function getSellersAutocomplete(int $langId, bool $favouriteOnly = false, int $userId = 0, bool $noLimit = false)
    {
        $page = FatApp::getPostedData('page', FatUtility::VAR_INT, 1);
        $page = 1 > $page ? 1 : $page;
        $pageSize = FatApp::getPostedData('pageSize', FatUtility::VAR_INT, 10);

        /* SubQuery, Shop have products[ */
        $prodShopSrch = new ProductSearch($langId);
        $prodShopSrch->addMultipleFields(array('distinct(shop_id)'));
        $prodShopSrch->setGeoAddress();
        $prodShopSrch->setDefinedCriteria();
        $prodShopSrch->validateAndJoinDeliveryLocation();
        $prodShopSrch->joinProductToCategory();
        $prodShopSrch->doNotCalculateRecords();
        $prodShopSrch->doNotLimitRecords();
        $prodShopSrch->joinSellerSubscription($langId, true);
        $prodShopSrch->addSubscriptionValidCondition();
        /* ] */

        $srch = new ShopSearch($langId);
        $srch->setDefinedCriteria($langId);
        $srch->joinShopCountry();
        $srch->joinShopState();
        $srch->joinSellerSubscription();
        $srch->joinTable('(' . $prodShopSrch->getQuery() . ')', 'INNER JOIN', 'temp.shop_id = s.shop_id', 'temp');


        $keyword = FatApp::getPostedData('keyword', null, '');
        if (!empty($keyword)) {
            $cond = $srch->addCondition('u_cred.credential_username', 'like', '%' . $keyword . '%');
            $cond->attachCondition('u_cred.credential_email', 'like', '%' . $keyword . '%', 'OR');
            $cond->attachCondition('u.user_name', 'like', '%' . $keyword . '%');
            $cond->attachCondition('s_l.shop_name', 'like', '%' . $keyword . '%');
            $cond->attachCondition('s.shop_identifier', 'like', '%' . $keyword . '%');
        }

        if ($favouriteOnly) {
            $srch->joinTable(Shop::DB_TBL_SHOP_FAVORITE, 'INNER JOIN', 's.shop_id = ufs.ufs_shop_id AND ufs.ufs_user_id = ' . $userId, 'ufs');
        }

        if (self::$sellerListingForRfq) {
            $srch->addCondition('shop_rfq_enabled', '=', applicationConstants::YES);
        }

        $flds = [
            's.shop_id',
            'shop_user_id',
            'user_name',
            'IFNULL(shop_name, shop_identifier) as shop_name'
        ];
        $srch->addMultipleFields($flds);
        $srch->addGroupBy('s.shop_id');

        if (false == $noLimit) {
            $srch->setPageNumber($page);
            $srch->setPageSize($pageSize);
        } else {
            $srch->doNotCalculateRecords();
            $srch->doNotLimitRecords();
        }
        $srch->addOrder('shop_created_on');
        $result = FatApp::getDb()->fetchAll($srch->getResultSet(), 'shop_id');
        $allShops = array(
            'pageCount' => $srch->pages(),
            'results' => []
        );

        foreach ($result as $shop) {
            $name = $shop['user_name'] . ' (' . $shop['shop_name'] . ')';
            $allShops['results'][] = array(
                'id' => $shop['shop_user_id'],
                'text' => strip_tags(html_entity_decode($name, ENT_QUOTES, 'UTF-8'))
            );
        }

        return $allShops;
    }
    public static function updateValidSubscription(int $userId = 0): bool
    {
        if (1 > FatApp::getConfig('CONF_ENABLE_SELLER_SUBSCRIPTION_MODULE', FatUtility::VAR_INT, 0)) {
            return true;
        }

        $sSrch = new SearchBase(Orders::DB_TBL, 'o');
        $sSrch->addMultipleFields(['max(o.order_id) as currentOrderId']);
        $sSrch->addCondition('o.order_type', '=', 'mysql_func_' . Orders::ORDER_SUBSCRIPTION, 'AND', true);
        $sSrch->addCondition('o.order_payment_status', '=',  'mysql_func_' . Orders::ORDER_PAYMENT_PAID, 'AND', true);
        $sSrch->addGroupBy('o.order_id');
        $sSrch->doNotCalculateRecords();
        $sSrch->doNotLimitRecords();

        $srch = new searchBase(Orders::DB_TBL, 'o');
        $srch->joinTable('(' . $sSrch->getQuery() . ')', 'INNER JOIN', 'otemp.currentOrderId=o.order_id', 'otemp');
        $srch->joinTable(OrderSubscription::DB_TBL, 'INNER JOIN', 'o.order_id = oss.ossubs_order_id and oss.ossubs_status_id =' . FatApp::getConfig('CONF_DEFAULT_SUBSCRIPTION_PAID_ORDER_STATUS') . " and oss.ossubs_till_date > '" . date('Y-m-d') . "'", 'oss');
        $srch->joinTable(User::DB_TBL, 'INNER JOIN', 'o.order_user_id = u.user_id', 'u');

        if (0 < $userId) {
            $srch->addCondition('u.user_id', '= ', $userId);
        }

        $srch->addCondition('u.user_has_valid_subscription', '= ', applicationConstants::YES);
        $srch->addCondition('oss.ossubs_status_id', 'IN ', Orders::getActiveSubscriptionStatusArr());
        $srch->addCondition('o.order_type', '=', 'mysql_func_' . ORDERS::ORDER_SUBSCRIPTION, 'AND', true);
        $srch->addCondition('o.order_payment_status', '=', 'mysql_func_' . Orders::ORDER_PAYMENT_PAID, 'AND', true);

        $srch->doNotCalculateRecords();
        $srch->doNotLimitRecords();
        $srch->addGroupBy('o.order_user_id');
        $srch->addFld('o.order_user_id');
        $srch->addMultipleFields(['o.order_user_id']);

        $result = FatApp::getDb()->fetchAll($srch->getResultSet());
        foreach ($result as $user) {
            $assignValues = ['shop_has_valid_subscription' => applicationConstants::YES];
            FatApp::getDb()->updateFromArray(Shop::DB_TBL, $assignValues, array('smt' => 'shop_user_id = ? ', 'vals' => array((int) $user['order_user_id'])));
        }
        return true;
    }

    public static function getShopMissingInfo(int $shopId, $langId): array
    {
        $validationArr = [
            'user_deleted' => ['title' => Labels::getLabel('LBL_SELLER_DELETED', $langId), 'currentStatus' => '', 'valid' => false],
            'credential_active' => ['title' => Labels::getLabel('LBL_SELLER_ACTIVE', $langId), 'currentStatus' => '', 'valid' => false],
            'credential_verified' => ['title' => Labels::getLabel('LBL_SELLER_VERIFIED', $langId), 'currentStatus' => '', 'valid' => false],
            'shop_active' => ['title' => Labels::getLabel('LBL_SHOP_ACTIVE', $langId), 'currentStatus' => '', 'valid' => false],
            'shop_supplier_display_status' => ['title' => Labels::getLabel('LBL_SHOP_DISPLAY_STATUS', $langId), 'currentStatus' => '', 'valid' => false],
            'country_active' => ['title' => Labels::getLabel('LBL_SHOP_COUNTRY_ACTIVE', $langId), 'currentStatus' => '', 'valid' => false],
            'state_active' => ['title' => Labels::getLabel('LBL_SHOP_STATE_ACTIVE', $langId), 'currentStatus' => '', 'valid' => false],
            'user_is_supplier' => ['title' => Labels::getLabel('LBL_USER_IS_SELLER', $langId), 'currentStatus' => '', 'valid' => false],
        ];

        if (FatApp::getConfig('CONF_ENABLE_SELLER_SUBSCRIPTION_MODULE', FatUtility::VAR_INT, 0)) {
            $validationArr['subscription'] = ['title' => Labels::getLabel('LBL_SELLER_SUBSCRIPTION_ACTIVE', $langId), 'currentStatus' => '', 'valid' => false];
        }

        $shop = Shop::getAttributesById($shopId, ['shop_user_id', 'shop_active', 'shop_supplier_display_status', 'shop_country_id', 'shop_state_id']);

        if ($shop) {
            if (FatApp::getConfig('CONF_ENABLE_SELLER_SUBSCRIPTION_MODULE', FatUtility::VAR_INT, 0)) {
                $validationArr['subscription']['valid']  = false;
                $validationArr['subscription']['currentStatus']  = 0;

                $currentActivePlan = OrderSubscription::getUserCurrentActivePlanDetails($langId, $shop['shop_user_id'], array(OrderSubscription::DB_TBL_PREFIX . 'till_date', OrderSubscription::DB_TBL_PREFIX . 'price', OrderSubscription::DB_TBL_PREFIX . 'type'));
                if ($currentActivePlan) {
                    $validationArr['subscription']['valid'] = FatDate::diff(date("Y-m-d"), $currentActivePlan[OrderSubscription::DB_TBL_PREFIX . 'till_date']) > 0;
                    $validationArr['subscription']['currentStatus'] = $validationArr['subscription']['valid'] ? 1 : 0;
                }
            }

            $userObj = User::getSearchObject(true, 0, false);
            $userObj->addMultipleFields(['user_deleted', 'credential_active', 'credential_verified', 'user_is_supplier']);
            $userObj->addCondition('user_id', '=', $shop['shop_user_id']);
            $userObj->doNotCalculateRecords();
            $userObj->setPageSize(1);
            $seller = FatApp::getDb()->fetch($userObj->getResultSet());
            if ($seller) {
                $validationArr['user_deleted']['valid'] = $seller['user_deleted'] === applicationConstants::NO;
                $validationArr['user_deleted']['currentStatus'] = $seller['user_deleted'];
                $validationArr['credential_active']['valid'] = $seller['credential_active'] === applicationConstants::YES;
                $validationArr['credential_active']['currentStatus'] = $seller['credential_active'];
                $validationArr['credential_verified']['valid'] = $seller['credential_verified'] === applicationConstants::YES;
                $validationArr['credential_verified']['currentStatus'] = $seller['credential_verified'];
                $validationArr['user_is_supplier']['valid'] = $seller['user_is_supplier'] === applicationConstants::YES;
                $validationArr['user_is_supplier']['currentStatus'] = $seller['user_is_supplier'];

                $validationArr['shop_active']['valid'] = $shop['shop_active'] === applicationConstants::YES;
                $validationArr['shop_active']['currentStatus'] = $shop['shop_active'];
                $validationArr['shop_supplier_display_status']['valid'] = $shop['shop_supplier_display_status'] === applicationConstants::YES;
                $validationArr['shop_supplier_display_status']['currentStatus'] = $shop['shop_supplier_display_status'];
                $shopCountry = Countries::getAttributesById($shop['shop_country_id'], ['country_active']);
                if ($shopCountry) {
                    $validationArr['country_active']['valid'] = $shopCountry['country_active'] === applicationConstants::YES;
                    $validationArr['country_active']['currentStatus'] = $shopCountry['country_active'];
                }

                $shopState = States::getAttributesById($shop['shop_state_id'], ['state_active']);
                if ($shopState) {
                    $validationArr['state_active']['valid'] = $shopState['state_active'] === applicationConstants::YES;
                    $validationArr['state_active']['currentStatus'] = $shopState['state_active'];
                }
            }
        }

        return $validationArr;
    }

    
        /**
     * updateShopsDisplayStatus
     * disable shop_supplier_display_status when an country or state marked disabled
     * 
     * @param  mixed $countryId
     * @param  mixed $stateId
     * @return bool
     */
    public static function updateShopsDisplayStatus(int $countryId = 0, int $stateId = 0)
    {
        $where = '';
        $values = [];

        if ($countryId) {
            $where .= static::tblFld('country_id') . " = ?";
            array_push($values, $countryId);
        }

        if ($stateId) {
            if ($where != '') {
                $where .= ' and ';
            }
            $where .= static::tblFld('state_id') . " = ?";
            array_push($values, $stateId);
        }

        if (!empty($values)) {
            $whereCondition = ['smt' => $where, 'vals' => $values];
            if (!FatApp::getDb()->updateFromArray(static::DB_TBL, array(static::tblFld('supplier_display_status') => 0), $whereCondition)) {
                return false;
            }
        }
        return true;
    }
}
