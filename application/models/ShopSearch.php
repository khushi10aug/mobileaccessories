<?php

class ShopSearch extends SearchBase
{
    private $sellerOrderJoined = false;
    private $langId;
    public function __construct($langId = 0, $isActive = true, $shopSupplierDisplayStatus = true, $joinLangToAll = true)
    {
        parent::__construct(Shop::DB_TBL, 's');
        $langId = FatUtility::int($langId);
        if ($joinLangToAll) {
            $this->langId = $langId;
        }

        if ($langId > 0) {
            $this->joinTable(
                Shop::DB_TBL_LANG,
                'LEFT OUTER JOIN',
                's_l.' . Shop::DB_TBL_LANG_PREFIX . 'shop_id = s.shop_id
			AND s_l.' . Shop::DB_TBL_LANG_PREFIX . 'lang_id = ' . $langId,
                's_l'
            );
        }
        if ($isActive) {
            $this->addCondition('shop_active', '=', 'mysql_func_' . applicationConstants::ACTIVE, 'AND', true);
            $this->addCondition('shop_user_valid', '=', 'mysql_func_' . applicationConstants::YES, 'AND', true);
            $this->addCondition('shop_supplier_display_status', '=', 'mysql_func_' . applicationConstants::ACTIVE, 'AND', true);
            if (FatApp::getConfig('CONF_ENABLE_SELLER_SUBSCRIPTION_MODULE', FatUtility::VAR_INT, 0)) {
                $this->addDirectCondition('(shop_has_valid_subscription = ' . applicationConstants::YES . ' OR shop_user_id = 1)');
            }
        }
        if ($shopSupplierDisplayStatus) {
            $this->addCondition('shop_supplier_display_status', '=', 'mysql_func_' . applicationConstants::ACTIVE, 'AND', true);
        }
    }

    public function setDefinedCriteria($langId = 0, $isActive = true)
    {
        $this->joinShopOwner($isActive);
        $this->joinShopCountry($langId, $isActive);
        $this->joinShopState($langId, $isActive);
    }

    public function joinShopOwner($isActive = true)
    {
        $this->joinTable(User::DB_TBL, 'LEFT OUTER JOIN', 'shop_user_id = u.user_id', 'u');
        $this->joinTable(User::DB_TBL_CRED, 'LEFT OUTER JOIN', 'credential_user_id = u.user_id', 'u_cred');
        $this->addCondition('u.user_is_supplier', '=', 'mysql_func_' . applicationConstants::YES, 'AND', true);

        if ($isActive) {
            $this->addCondition('u.user_deleted', '=', applicationConstants::NO);
            $this->addCondition('credential_active', '=', 'mysql_func_' . applicationConstants::ACTIVE, 'AND', true);
            $this->addCondition('credential_verified', '=', 'mysql_func_' . applicationConstants::YES, 'AND', true);
        }
    }

    public function joinShopCountry($langId = 0, $isActive = true)
    {
        $langId = FatUtility::int($langId);
        if ($this->langId) {
            $langId = $this->langId;
        }
        $this->joinTable(Countries::DB_TBL, 'LEFT OUTER JOIN', 's.shop_country_id = shop_country.country_id', 'shop_country');

        if ($langId) {
            $this->joinTable(Countries::DB_TBL_LANG, 'LEFT OUTER JOIN', 'shop_country.country_id = shop_country_l.countrylang_country_id AND shop_country_l.countrylang_lang_id = ' . $langId, 'shop_country_l');
        }
        if ($isActive) {
            $this->addCondition('shop_country.country_active', '=', 'mysql_func_' . applicationConstants::ACTIVE, 'AND', true);
        }
    }

    public function joinShopState($langId = 0, $isActive = true)
    {
        $langId = FatUtility::int($langId);
        if ($this->langId) {
            $langId = $this->langId;
        }
        $this->joinTable(States::DB_TBL, 'LEFT OUTER JOIN', 's.shop_state_id = shop_state.state_id', 'shop_state');

        if ($langId) {
            $this->joinTable(States::DB_TBL_LANG, 'LEFT OUTER JOIN', 'shop_state.state_id = shop_state_l.statelang_state_id AND shop_state_l.statelang_lang_id = ' . $langId, 'shop_state_l');
        }
        if ($isActive) {
            $this->addCondition('shop_state.state_active', '=', 'mysql_func_' . applicationConstants::ACTIVE, 'AND', true);
        }
    }

    public function joinSellerOrder()
    {
        $this->sellerOrderJoined = true;
        if (FatApp::getConfig('CONF_ENABLE_SELLER_SUBSCRIPTION_MODULE')) {
            $this->joinTable(Orders::DB_TBL, 'INNER JOIN', 'o.order_user_id=shop_user_id and o.order_type=' . ORDERS::ORDER_SUBSCRIPTION, 'o');
        }
    }

    public function joinSellerOrderSubscription($langId = 0)
    {
        $langId = FatUtility::int($langId);
        if ($this->langId) {
            $langId = $this->langId;
        }
        if (!$this->sellerOrderJoined) {
            trigger_error(Labels::getLabel('ERR_Seller_Subscription_Order_must_joined.', CommonHelper::getLangId()), E_USER_ERROR);
        }
        if (FatApp::getConfig('CONF_ENABLE_SELLER_SUBSCRIPTION_MODULE')) {
            $this->joinTable(OrderSubscription::DB_TBL, 'INNER JOIN', 'o.order_id = oss.ossubs_order_id ', 'oss');
            if ($langId > 0) {
                $this->joinTable(OrderSubscription::DB_TBL_LANG, 'LEFT OUTER JOIN', 'oss.ossubs_id = ossl.' . OrderSubscription::DB_TBL_LANG_PREFIX . 'ossubs_id AND ossl.' . OrderSubscription::DB_TBL_LANG_PREFIX . 'lang_id = ' . $langId, 'ossl');
            }
        }
    }
    public function joinSellerSubscription($langId = 0)
    {
        $this->joinSellerOrder();
        $this->joinSellerOrderSubscription($langId);
        $this->addSubscriptionValidCondition();
    }

    public function addSubscriptionValidCondition($date = '')
    {
        if ($date == '') {
            $date = date("Y-m-d");
        }
        if (FatApp::getConfig('CONF_ENABLE_SELLER_SUBSCRIPTION_MODULE')) {
            $activeStatuses = implode(',', array_map('intval', Orders::getActiveSubscriptionStatusArr()));
            $this->addDirectCondition(
                "((oss.ossubs_till_date >= '" . $date . "' AND ossubs_status_id IN (" . $activeStatuses . ")) OR shop_user_id = 1)"
            );
        }
    }

    public function addProductsCount()
    {
        $srch = new ProductSearch(0);
        $srch->doNotCalculateRecords();
        $srch->doNotLimitRecords();
        $srch->addGroupBy('selprod_user_id');
        $srch->joinSellerProducts();
        $srch->addMultipleFields(array('count(selprod_id) as totStoreProducts', 'selprod_user_id'));
        $this->joinTable('(' . $srch->getQuery() . ')', 'LEFT OUTER JOIN', 'spc.selprod_user_id = s.shop_user_id', 'spc');
        $this->addFld('IFNULL(spc.totStoreProducts, 0) as totProducts');
    }

    public function addRatingsCount()
    {
        /*Need to fetch avg rating from already joined shop table */
        /* $srch = new SelProdReviewSearch();
        $srch->joinSeller();
        $srch->joinSellerProducts();
        $srch->joinSelProdRating();
        $srch->joinOrderProduct();
        $srch->joinOrderProductShipping();
        $srch->addMultipleFields(array('ROUND(AVG(sprating_rating),2) as avg_rating', 'count(DISTINCT(spreview_id)) as totReviews', 'spreview_seller_user_id'));
        $srch->addDirectCondition("(CASE WHEN 0 < opshipping_by_seller_user_id THEN `ratingtype_type` IN('" . RatingType::TYPE_SHOP . "', '" . RatingType::TYPE_DELIVERY . "') ELSE `ratingtype_type` = '" . RatingType::TYPE_SHOP . "' END)");
        $srch->doNotCalculateRecords();
        $srch->doNotLimitRecords();
        $srch->addCondition('spr.spreview_status', '=', 'mysql_func_' . SelProdReview::STATUS_APPROVED, 'AND', true);
        $srch->addGroupby('spreview_seller_user_id'); */
        $srch = new SearchBase(Shop::DB_TBL, 'stemp');
        $srch->doNotCalculateRecords();
        $srch->doNotLimitRecords();
        $srch->addMultipleFields(['stemp.shop_user_id as spreview_seller_user_id', 'stemp.shop_avg_rating as avg_rating', 'stemp.shop_total_reviews as totReviews']);
        $srch->addGroupby('stemp.shop_user_id');

        $this->joinTable('(' . $srch->getQuery() . ')', 'LEFT OUTER JOIN', 'sprating.spreview_seller_user_id = s.shop_user_id', 'sprating');
        $this->addFld('IFNULL(sprating.avg_rating, 0) as totRating');
        $this->addFld('IFNULL(sprating.totReviews, 0) as totReviews');
    }

    public function addFavoritesCount()
    {
        $srch = new UserFavoriteShopSearch();
        $srch->doNotCalculateRecords();
        $srch->doNotLimitRecords();
        $srch->addGroupBy('ufs_shop_id');
        $srch->addMultipleFields(array('ufs_shop_id', 'count(ufs_user_id) as totalFavorites'));
        $this->joinTable('(' . $srch->getQuery() . ')', 'LEFT OUTER JOIN', 's.shop_id = ufsq.ufs_shop_id', 'ufsq');
        $this->addFld('IFNULL(ufsq.totalFavorites, 0) as totalFavorites');
    }

    /* public function joinShopOwnerCountry( $langId = 0, $isActive = true ){
    $langId = FatUtility::int($langId);
    $this->joinTable( Countries::DB_TBL, 'LEFT OUTER JOIN', 'u.user_country_id = user_c.country_id', 'user_c' );

    if( $langId ){
    $this->joinTable( Countries::DB_TBL_LANG, 'LEFT OUTER JOIN', 'user_c.country_id = c_l.countrylang_country_id AND c_l.countrylang_lang_id = '.$langId, 'c_l' );
    }
    if( $isActive ){
    $this->addCondition( 'user_c.country_active', '=', applicationConstants::ACTIVE );
    }
    }

    public function joinShopOwnerState( $langId = 0, $isActive = true ){
    $langId = FatUtility::int($langId);
    $this->joinTable( States::DB_TBL, 'LEFT OUTER JOIN', 'u.user_state_id = user_state.state_id', 'user_state' );

    if( $langId ){
    $this->joinTable( States::DB_TBL_LANG, 'LEFT OUTER JOIN', 'user_state.state_id = state_l.statelang_state_id AND state_l.statelang_lang_id = '.$langId, 'state_l' );
    }

    if( $isActive ){
    $this->addCondition( 'user_state.state_active', '=', applicationConstants::ACTIVE );
    }
    } */
}
