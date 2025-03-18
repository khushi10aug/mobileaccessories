<?php
/* This Class is used for to list sellable products, sellable means,
    => shop must be active
    => Shop Display Status must be on
    => Seller/User must be active
    => Seller/User email must be verified.
    => Seller/User must be supplier = 1
    => Product Must be active
    => Product must be approved
    => Associated Brand must be active and not deleted.
    => Product Category must be active and category must not be deleted.
    */
class ProductSearch extends SearchBase
{
    private $langId;

    private $sellerProductsJoined = false;
    private $sellerUserJoined = false;
    private $shopsJoined = false;
    private $commonLangId;
    private $sellerSubscriptionOrderJoined = false;
    private $joinProductShippedBy = false;
    private $geoAddress = [];
    private $locationBasedInnerJoin = true;

    public function __construct($langId = 0, $otherTbl = null, $prodIdColumName = null, $isProductActive = true, $isProductApproved = true, $isProductDeleted = true)
    {
        $this->langId = FatUtility::int($langId);
        $this->commonLangId = CommonHelper::getLangId();

        if ($otherTbl == null) {
            parent::__construct(Product::DB_TBL, 'p');
        } else {
            /* Same productsearch class used to fetch products under any batch/group, do not call setDefinedCriteria, call setBatchProductsCriteria().[ */
            parent::__construct($otherTbl, 'temp');
            $this->joinTable(SellerProduct::DB_TBL, 'INNER JOIN', 'temp.' . ProductGroup::DB_PRODUCT_TO_GROUP_PREFIX . 'selprod_id = sp.selprod_id', 'sp');
            if ($this->langId > 0) {
                $this->joinTable(SellerProduct::DB_TBL_LANG, 'LEFT OUTER JOIN', 'sp.selprod_id = sp_l.selprodlang_selprod_id AND selprodlang_lang_id = ' . $this->langId, 'sp_l');
            }
            $this->joinTable(Product::DB_TBL, 'INNER JOIN', 'sp.selprod_product_id = p.product_id', 'p');
            /* ] */
            $this->addOrder('ptg_is_main_product', 'DESC');
            /* $this->addCondition('p.product_deleted','=',applicationConstants::NO); */
        }

        if ($langId > 0) {
            $this->joinProductsLang($this->langId);
        }

        if ($isProductActive) {
            $this->addCondition('product_active', '=', 'mysql_func_' . applicationConstants::ACTIVE, 'AND', true);
        }

        if ($isProductDeleted) {
            $this->addCondition('product_deleted', '=', 'mysql_func_' . applicationConstants::NO, 'AND', true);
        }

        if ($isProductApproved) {
            $this->addCondition('product_approved', '=', 'mysql_func_' . Product::APPROVED, 'AND', true);
        }
    }

    public function setGeoAddress($address = [])
    {
        $this->geoAddress = Address::getYkGeoData($address);
    }

    public function joinProductsLang($langId)
    {
        $this->joinTable(Product::DB_TBL_LANG, 'LEFT OUTER JOIN', 'productlang_product_id = p.product_id AND productlang_lang_id = ' . $langId, 'tp_l');
    }

    public function setDefaultLangForJoins($langId)
    {
        $this->langId = FatUtility::int($langId);
    }

    public function unsetDefaultLangForJoins()
    {
        $this->langId = 0;
    }

    public function setDefinedCriteria($joinPrice = 0, $bySeller = 0, $criteria = array(), $checkAvailableFrom = true, $useTempTable = false)
    {
        $joinPrice = FatUtility::int($joinPrice);
        if (0 < $joinPrice) {
            $this->joinForPrice('', $criteria, $checkAvailableFrom, $useTempTable);
        } else {
            $this->joinSellerProducts($bySeller, '', $criteria, $checkAvailableFrom);
        }

        $joinSellers = true;
        if (isset($criteria['doNotJoinSellers']) && true == $criteria['doNotJoinSellers']) {
            $joinSellers = false;
        }

        if ($joinSellers) {
            $this->joinSellers();
        }

        $shopId = 0;
        if (isset($criteria['shop_id']) && 0 < $criteria['shop_id']) {
            $shopId = FatUtility::int($criteria['shop_id']);
        }
        $this->joinShops(0, true, true, $shopId, true);

        $joinShopCountry = true;
        if (isset($criteria['doNotJoinShopCountry']) && true == $criteria['doNotJoinShopCountry']) {
            $joinShopCountry = false;
        }

        if ($joinShopCountry) {
            $countryId = 0;
            if (isset($criteria['country_id']) && 0 < $criteria['country_id']) {
                $countryId = FatUtility::int($criteria['country_id']);
            }
            $this->joinShopCountry(0, true, $countryId);
        }

        $joinShopState = true;
        if (isset($criteria['doNotJoinShopState']) && true == $criteria['doNotJoinShopState']) {
            $joinShopState = false;
        }

        if ($joinShopState) {
            $stateId = 0;
            if (isset($criteria['state_id']) && 0 < $criteria['state_id']) {
                $stateId = FatUtility::int($criteria['state_id']);
            }
            $this->joinShopState(0, true, $stateId);
        }
        $this->joinBrands(0, true, true, true, $criteria);

        $joinShippingPkg = true;
        if (isset($criteria['doNotJoinShippingPkg']) && true == $criteria['doNotJoinShippingPkg']) {
            $joinShippingPkg = false;
        }

        if ($joinShippingPkg) {
            $this->joinShippingPackages();
        }
    }

    public function setBatchProductsCriteria($splPriceForDate = '')
    {
        $now = FatDate::nowInTimezone(FatApp::getConfig('CONF_TIMEZONE'), 'Y-m-d');
        if ('' == $splPriceForDate) {
            $splPriceForDate = $now;
        }
        $this->joinProductToCategory();
        $this->joinSellers();
        $this->joinShops();
        $this->joinShopCountry();
        $this->joinShopState();
        $this->joinBrands();
        $this->doNotCalculateRecords();
        $this->doNotLimitRecords();
        /* groupby added, beacouse if same product is linked with multiple categories, then showing in repeat for each category[ */
        $this->addGroupBy('selprod_id');
        /* ] */
        $this->joinTable(
            SellerProduct::DB_TBL_SELLER_PROD_SPCL_PRICE,
            'LEFT OUTER JOIN',
            'splprice_selprod_id = selprod_id AND \'' . $splPriceForDate . '\' BETWEEN splprice_start_date AND splprice_end_date'
        );
    }

    /* Only used for product listing page for Home page, categories or search page*/
    public function joinForPrice($splPriceForDate = '', $criteria = array(), $checkAvailableFrom = true, $useTempTable = true)
    {
        if ($this->sellerProductsJoined) {
            trigger_error(Labels::getLabel('ERR_SELLERPRODUCTS_CAN_BE_JOINED_ONLY_ONCE.', $this->commonLangId), E_USER_ERROR);
        }
        $this->sellerProductsJoined = true;
        $now = FatDate::nowInTimezone(FatApp::getConfig('CONF_TIMEZONE'), 'Y-m-d');
        if ('' == $splPriceForDate) {
            $splPriceForDate = $now;
        }

        $joinCondition = '';
        if (isset($criteria['product_id']) && 0 < $criteria['product_id']) {
            $joinCondition = ' and msellprod.selprod_product_id = ' . $criteria['product_id'];
        }

        $this->joinTable(SellerProduct::DB_TBL, 'INNER JOIN', 'msellprod.selprod_product_id = p.product_id and selprod_deleted = ' . applicationConstants::NO . ' and selprod_active = ' . applicationConstants::ACTIVE . $joinCondition, 'msellprod');
        if (isset($criteria['optionvalue']) && $criteria['optionvalue'] != '') {
            $this->addOptionCondition($criteria['optionvalue']);
        }

        if (isset($criteria['selProdIds']) && 0 < count($criteria['selProdIds'])) {
            $joinCondition = ' and msellprod.selprod_id  IN (' . implode(",", $criteria['selProdIds']) . ')';
        }

        if (isset($criteria['prodIds']) && 0 < count($criteria['prodIds'])) {
            $joinCondition = ' and msellprod.selprod_product_id  IN (' . implode(",", $criteria['prodIds']) . ')';
        }

        /* if (isset($criteria['collection_product_id']) && $criteria['collection_product_id'] > 0) {
            $this->joinTable(
                Collections::DB_TBL_COLLECTION_TO_SELPROD,
                'INNER JOIN',
                Collections::DB_TBL_COLLECTION_TO_SELPROD_PREFIX . 'selprod_id = selprod_id and ' . Collections::DB_TBL_COLLECTION_TO_SELPROD_PREFIX . 'collection_id = ' . $criteria['collection_product_id']
            );
            $useTempTable = false;
        } */

        if ($this->langId) {
            $this->joinTable(SellerProduct::DB_TBL_LANG, 'LEFT OUTER JOIN', 'msellprod.selprod_id = sprods_l.selprodlang_selprod_id AND sprods_l.selprodlang_lang_id = ' . $this->langId, 'sprods_l');
            $fields2 = array('selprod_title', 'selprod_warranty', 'selprod_return_policy', 'sprods_l.selprod_comments as selprodComments');
        }

        if (isset($criteria['optionvalue']) && $criteria['optionvalue'] != '') {
            $this->addOptionCondition($criteria['optionvalue']);
            $useTempTable = false;
        }

        if (!empty($criteria['keyword']) || !empty($criteria['shop']) || !empty($criteria['shop_id'])) {
            $useTempTable = false;
        }

        if (array_key_exists('price-min-range', $criteria) || array_key_exists('min_price_range', $criteria)) {
            $useTempTable = false;
        }
        /* Need to cross check results in case of temp table */
        if ($useTempTable === true) {
            $srch = new SearchBase(Product::DB_PRODUCT_MIN_PRICE);
            $srch->doNotLimitRecords();
            $srch->doNotCalculateRecords();
            $srch->addMultipleFields(array('pmp_product_id', 'pmp_selprod_id', 'pmp_min_price as theprice', 'pmp_max_price as maxprice', 'pmp_splprice_id', 'if(pmp_splprice_id,1,0) as special_price_found'));
            $tmpQry = $srch->getQuery();
            $this->joinTable('(' . $tmpQry . ')', 'INNER JOIN', 'pricetbl.pmp_product_id = msellprod.selprod_product_id and msellprod.selprod_id = pricetbl.pmp_selprod_id', 'pricetbl');
            $this->joinTable(SellerProduct::DB_TBL_SELLER_PROD_SPCL_PRICE, 'LEFT OUTER JOIN', 'msplpric.splprice_selprod_id = pricetbl.pmp_selprod_id and pricetbl.pmp_splprice_id = msplpric.splprice_id', 'msplpric');
            // $this->addFld('pricetbl.maxprice');
            if ($checkAvailableFrom) {
                $this->addCondition('msellprod.selprod_available_from', '<=', FatDate::nowInTimezone(FatApp::getConfig('CONF_TIMEZONE'), 'Y-m-d'));
            }
        } else {
            $this->joinBasedOnPriceCondition($splPriceForDate, $criteria, $checkAvailableFrom);
        }
        // $this->joinBasedOnPriceCondition($splPriceForDate, $criteria, $checkAvailableFrom);
    }

    public function joinBasedOnPriceCondition($splPriceForDate = '', $criteria = array(), $checkAvailableFrom = true)
    {
        $now = FatDate::nowInTimezone(FatApp::getConfig('CONF_TIMEZONE'), 'Y-m-d');
        if ('' == $splPriceForDate) {
            $splPriceForDate = $now;
        }

        $this->joinTable(SellerProduct::DB_TBL_SELLER_PROD_SPCL_PRICE, 'LEFT OUTER JOIN', 'msplpric.splprice_selprod_id = msellprod.selprod_id AND \'' . $splPriceForDate . '\' BETWEEN msplpric.splprice_start_date AND msplpric.splprice_end_date AND (msplpric.splprice_price < msellprod.selprod_price or msplpric.splprice_price > msellprod.selprod_price)', 'msplpric');

        $srch = new SearchBase(SellerProduct::DB_TBL, 'sprods');

        /* if (isset($criteria['collection_product_id']) && $criteria['collection_product_id'] > 0) {
            $srch->joinTable(
                Collections::DB_TBL_COLLECTION_TO_SELPROD,
                'INNER JOIN',
                Collections::DB_TBL_COLLECTION_TO_SELPROD_PREFIX . 'selprod_id = sprods.selprod_id and ' . Collections::DB_TBL_COLLECTION_TO_SELPROD_PREFIX . 'collection_id = ' . $criteria['collection_product_id']
            );
        } */

        $shopCondition = '';
        if (array_key_exists('shop_id', $criteria) && $criteria['shop_id'] > 0) {
            $shopId = FatUtility::int($criteria['shop_id']);
            $shopCondition .= ' and ts.shop_id = ' . $shopId;
            // $srch->addCondition('ts.shop_id', '=', 'mysql_func_' . $shopId, 'AND', true);
        }

        $shopCondition = '';
        if (array_key_exists('country_id', $criteria) && $criteria['country_id'] > 0) {
            $shopCondition .= ' and ts.shop_country_id = ' . FatUtility::int($criteria['country_id']);
            // $srch->addCondition('ts.shop_country_id', '=', 'mysql_func_' . FatUtility::int($criteria['country_id']), 'AND', true);
        }

        $shopCondition = '';
        if (array_key_exists('state_id', $criteria) && $criteria['state_id'] > 0) {
            $shopCondition .= ' and ts.shop_state_id = ' . FatUtility::int($criteria['state_id']);
            // $srch->addCondition('ts.shop_state_id', '=', 'mysql_func_' . FatUtility::int($criteria['state_id']), 'AND', true);
        }

        $productCondition = '';
        if (array_key_exists('brand_id', $criteria) && $criteria['brand_id'] > 0) {
            $brandId = FatUtility::int($criteria['brand_id']);
            $productCondition = ' and tp.product_brand_id = ' . $brandId;
            // $srch->addCondition('tp.product_brand_id', '=', 'mysql_func_' . $brandId, 'AND', true);
        }

        $srch->joinTable(Product::DB_TBL, 'INNER JOIN', 'tp.product_id = sprods.selprod_product_id AND tp.product_active = ' . applicationConstants::ACTIVE . ' and tp.product_deleted = ' . applicationConstants::NO . ' and tp.product_approved = ' . Product::APPROVED . $productCondition, 'tp');
        /* $srch->joinTable(User::DB_TBL, 'INNER JOIN', 'tu.user_id = sprods.selprod_user_id AND tu.user_is_supplier = ' . applicationConstants::YES, 'tu');
        $srch->joinTable(User::DB_TBL_CRED, 'INNER JOIN', 'tuc.credential_user_id = tu.user_id and tuc.credential_active = ' . applicationConstants::ACTIVE . ' and tuc.credential_verified = ' . applicationConstants::YES, 'tuc'); */
        $srch->joinTable(Shop::DB_TBL, 'INNER JOIN', 'ts.shop_user_id = sprods.selprod_user_id and ts.shop_user_valid = 1 and ts.shop_active = ' . applicationConstants::YES . ' AND ts.shop_supplier_display_status = ' . applicationConstants::YES . $shopCondition, 'ts');
        // $srch->joinTable(Countries::DB_TBL, 'INNER JOIN', 'tcn.country_id = ts.shop_country_id and tcn.country_active = ' . applicationConstants::YES, 'tcn');
        //$srch->joinTable(States::DB_TBL, 'INNER JOIN', 'tst.state_id = ts.shop_state_id and tst.state_active = ' . applicationConstants::YES, 'tst');

        if (FatApp::getConfig("CONF_PRODUCT_BRAND_MANDATORY", FatUtility::VAR_INT, 1)) {
            $srch->joinTable(Brand::DB_TBL, 'INNER JOIN', 'tb.brand_id = tp.product_brand_id and tb.brand_active = ' . applicationConstants::YES . ' and tb.brand_deleted = ' . applicationConstants::NO, 'tb');
        } else {
            $srch->joinTable(Brand::DB_TBL, 'LEFT OUTER JOIN', 'tb.brand_id = tp.product_brand_id', 'tb');
        }

        $srch->joinTable(Product::DB_TBL_PRODUCT_TO_CATEGORY, 'INNER JOIN', 'tptc.ptc_product_id = tp.product_id', 'tptc');
        $srch->joinTable(ProductCategory::DB_TBL, 'INNER JOIN', 'tc.prodcat_id = tptc.ptc_prodcat_id and tc.prodcat_active = ' . applicationConstants::YES . ' and tc.prodcat_deleted = ' . applicationConstants::NO, 'tc');
        /*$srch->addMultipleFields(array('selprod_product_id','MIN(COALESCE(splprice_price, selprod_price)) AS theprice','(CASE WHEN splprice_selprod_id IS NULL THEN 0 ELSE 1 END) AS special_price_found'));*/

        $srch->addMultipleFields(array('sprods.selprod_product_id', 'if(splprice_selprod_id IS NULL,0,1) AS special_price_found'));

        if (!empty($criteria['keyword'])) {
            /* $srch->addFld('if(sp_l.selprod_title LIKE ' . FatApp::getDb()->quoteVariable('%' . $criteria['keyword'] . '%') . ',  COALESCE(splprice_price, sprods.selprod_price), MIN(COALESCE(tsp.splprice_price, sprods.selprod_price)) ) as theprice'); */
            $srch->addFld('COALESCE(splprice_price, sprods.selprod_price) as theprice');
            $srch->addFld('if(sp_l.selprod_title LIKE ' . FatApp::getDb()->quoteVariable('%' . $criteria['keyword'] . '%') . ',  1, 0 ) as keywordFound');
            /* if (isset($criteria['max_price']) && true == $criteria['max_price']) {
                $srch->addFld('MAX(COALESCE(tsp.splprice_price, sprods.selprod_price)) as maxprice');
            } */
        } else {
            $srch->addFld('MIN(COALESCE(tsp.splprice_price, sprods.selprod_price)) AS theprice');
            $srch->addFld('0 as keywordFound');
            /* if (isset($criteria['max_price']) && true == $criteria['max_price']) {
                $srch->addFld('MAX(COALESCE(tsp.splprice_price, sprods.selprod_price)) AS maxprice');
            } */
        }

        $minPriceRange = 0;
        if (array_key_exists('price-min-range', $criteria)) {
            $currCurrencyId = isset($criteria['currency_id']) ? $criteria['currency_id'] : FatApp::getConfig('CONF_CURRENCY', FatUtility::VAR_INT, 1);
            $minPriceRange = CommonHelper::convertExistingToOtherCurrency($currCurrencyId, $criteria['price-min-range'], FatApp::getConfig('CONF_CURRENCY', FatUtility::VAR_INT, 1), false);
            //            $minPriceRange = floor($criteria['price-min-range']);
        } elseif (array_key_exists('min_price_range', $criteria)) {
            $currCurrencyId = isset($criteria['currency_id']) ? $criteria['currency_id'] : FatApp::getConfig('CONF_CURRENCY', FatUtility::VAR_INT, 1);
            $minPriceRange = CommonHelper::convertExistingToOtherCurrency($currCurrencyId, $criteria['min_price_range'], FatApp::getConfig('CONF_CURRENCY', FatUtility::VAR_INT, 1), false);
            //            $minPriceRange = floor($criteria['min_price_range']);
        }
        if (0 < $minPriceRange) {
            $srch->addDirectCondition('COALESCE(tsp.splprice_price, sprods.selprod_price) >= ' . $minPriceRange);
        }

        $srch->joinTable(
            SellerProduct::DB_TBL_SELLER_PROD_SPCL_PRICE,
            'LEFT OUTER JOIN',
            'tsp.splprice_selprod_id = sprods.selprod_id AND \'' . $splPriceForDate . '\' BETWEEN tsp.splprice_start_date AND tsp.splprice_end_date and (tsp.splprice_price < sprods.selprod_price or tsp.splprice_price > sprods.selprod_price )',
            'tsp'
        );
        $srch->addCondition('sprods.selprod_active', '=', 'mysql_func_' . applicationConstants::ACTIVE, 'AND', true);
        $srch->addCondition('sprods.selprod_deleted', '=', 'mysql_func_' . applicationConstants::NO, 'AND', true);

        if (isset($criteria['optionvalue']) && $criteria['optionvalue'] != '') {
            $this->addOptionCondition($criteria['optionvalue'], $srch);
        }

        if (isset($criteria['condition']) && !empty($criteria['condition'])) {
            if (true === MOBILE_APP_API_CALL) {
                $criteria['condition'] = json_decode($criteria['condition'], true);
            }
            $condition = is_array($criteria['condition']) ? array_filter($criteria['condition']) : $criteria['condition'];
            $this->addConditionCondition($condition, $srch);
        }

        if (isset($criteria['out_of_stock']) && !empty($criteria['out_of_stock'])) {
            $srch->addCondition('sprods.selprod_stock', '>', 'mysql_func_' . applicationConstants::NO, 'AND', true);
        }

        if (!empty($criteria['keyword']) && $this->langId) {
            $srch->joinTable(Product::DB_TBL_LANG, 'LEFT OUTER JOIN', 'productlang_product_id = tp.product_id	AND productlang_lang_id = ' . $this->langId, 'tp_l');
            $srch->joinTable(SellerProduct::DB_TBL_LANG, 'LEFT OUTER JOIN', 'selprodlang_selprod_id = sprods.selprod_id	AND selprodlang_lang_id = ' . $this->langId, 'sp_l');
            $srch->joinTable(Brand::DB_TBL_LANG, 'LEFT OUTER JOIN', 'brandlang_brand_id = tb.brand_id	AND brandlang_lang_id = ' . $this->langId, 'tb_l');
            $srch->joinTable(ProductCategory::DB_TBL_LANG, 'LEFT OUTER JOIN', 'prodcatlang_prodcat_id = tc.prodcat_id AND prodcatlang_lang_id = ' . $this->langId, 'tc_l');
            $this->addKeywordSearch($criteria['keyword'], $srch, false);
        }

        if ($checkAvailableFrom) {
            $srch->addCondition('sprods.selprod_available_from', '<=', $splPriceForDate);
        }
        $srch->doNotLimitRecords();
        $srch->doNotCalculateRecords();
        if (isset($criteria['collection_product_id']) && $criteria['collection_product_id'] > 0) {
            $srch->addGroupBy('sprods.selprod_id');
        } else {
            $srch->addGroupBy('sprods.selprod_product_id');
            if (!empty($criteria['keyword'])) {
                $srch->addGroupBy('keywordFound');
            }
        }

        if (isset($criteria['product_id']) && 0 < $criteria['product_id']) {
            $srch->addCondition('sprods.selprod_product_id', '=', $criteria['product_id']);
        }

        $tmpQry = $srch->getQuery();

        $this->joinTable('(' . $tmpQry . ')', 'INNER JOIN', "pricetbl.selprod_product_id = msellprod.selprod_product_id and (splprice_price = theprice OR selprod_price = theprice)", 'pricetbl');
    }

    public function joinSellerProducts($bySeller = 0, $splPriceForDate = '', $criteria = array(), $checkAvailableFrom = true, $isProductActive = true)
    {
        if ($this->sellerProductsJoined) {
            trigger_error(Labels::getLabel('ERR_SELLERPRODUCTS_CAN_BE_JOINED_ONLY_ONCE.', $this->commonLangId), E_USER_ERROR);
        }
        $this->sellerProductsJoined = true;
        $joinSpecialPrice = true;
        if (array_key_exists('doNotJoinSpecialPrice', $criteria) && $criteria['doNotJoinSpecialPrice'] == true) {
            $joinSpecialPrice = false;
        }

        $excludeProdForRfqOnly = false;
        if (array_key_exists('excludeProdForRfqOnly', $criteria) && $criteria['excludeProdForRfqOnly'] == true) {
            $excludeProdForRfqOnly = true;
        }

        if ($joinSpecialPrice == true) {
            $now = FatDate::nowInTimezone(FatApp::getConfig('CONF_TIMEZONE'), 'Y-m-d');
            if ('' == $splPriceForDate) {
                $splPriceForDate = $now;
            }
            $bySeller = FatUtility::int($bySeller);
            $srch = new SearchBase(SellerProduct::DB_TBL, 'sprods');

            $fields2 = array();
            if ($this->langId) {
                $srch->joinTable(SellerProduct::DB_TBL_LANG, 'LEFT OUTER JOIN', 'sprods.selprod_id = sprods_l.selprodlang_selprod_id AND sprods_l.selprodlang_lang_id = ' . $this->langId, 'sprods_l');
                $fields2 = array('selprod_title', 'selprod_warranty', 'selprod_return_policy', 'sprods_l.selprod_comments as selprodComments');
            }

            $srch->joinTable(
                SellerProduct::DB_TBL_SELLER_PROD_SPCL_PRICE,
                'LEFT OUTER JOIN',
                'm.splprice_selprod_id = selprod_id AND \'' . $splPriceForDate . '\' BETWEEN m.splprice_start_date AND m.splprice_end_date',
                'm'
            );

            $srch->joinTable(
                SellerProduct::DB_TBL_SELLER_PROD_SPCL_PRICE,
                'LEFT OUTER JOIN',
                's.splprice_selprod_id = selprod_id AND s.splprice_price < m.splprice_price
                 AND \'' . $splPriceForDate . '\' BETWEEN s.splprice_start_date AND s.splprice_end_date',
                's'
            );

            $srch->addCondition('s.splprice_selprod_id', 'IS', 'mysql_func_NULL', 'AND', true);
            if ($excludeProdForRfqOnly) {
                $srch->addCondition('selprod_cart_type', '!=', SellerProduct::CART_TYPE_RFQ_ONLY, 'AND', true);
                $srch->addCondition('selprod_hide_price', '!=', applicationConstants::YES, 'AND', true);
            }
            if ($isProductActive == true) {
                $srch->addCondition('selprod_active', '=', 'mysql_func_' . applicationConstants::ACTIVE, 'AND', true);
            }
            $srch->addCondition('selprod_deleted', '=', 'mysql_func_' . applicationConstants::NO, 'AND', true);
            if ($checkAvailableFrom) {
                $srch->addCondition('selprod_available_from', '<=', $now);
            }

            $fields1 = array(
                'sprods.*',
                'm.*',
                'if(m.splprice_selprod_id IS NULL,0,1) AS special_price_found',
                'COALESCE(m.splprice_price, selprod_price) AS theprice'
            );
            $srch->addMultipleFields(array_merge($fields1, $fields2));

            $srch->doNotLimitRecords();
            $srch->doNotCalculateRecords();
            if (isset($criteria['product_id']) && 0 < $criteria['product_id']) {
                $srch->addCondition('selprod_product_id', '=', $criteria['product_id']);
            }

            if (isset($criteria['selProdIds']) && 0 < count($criteria['selProdIds'])) {
                $srch->addCondition('sprods.selprod_id', 'IN', $criteria['selProdIds']);
            }

            if (isset($criteria['prodIds']) && 0 < count($criteria['prodIds'])) {
                $srch->addCondition('selprod_product_id', 'IN', $criteria['prodIds']);
            }

            $this->joinTable('(' . $srch->getQuery() . ')', 'LEFT OUTER JOIN', 'p.product_id = pricetbl.selprod_product_id', 'pricetbl');
        } else {
            $joinCondition = '';
            if (isset($criteria['product_id']) && 0 < $criteria['product_id']) {
                $joinCondition = ' and sprods.selprod_product_id = ' . $criteria['product_id'];
            }

            if (isset($criteria['selProdIds']) && 0 < count($criteria['selProdIds'])) {
                $joinCondition = ' and msellprod.selprod_id IN (' . implode(",", $criteria['selProdIds']) . ')';
            }

            if (isset($criteria['prodIds']) && 0 < count($criteria['prodIds'])) {
                $joinCondition = ' and sprods.selprod_product_id IN (' . implode(",", $criteria['prodIds']) . ')';
            }

            if ($excludeProdForRfqOnly) {
                $this->addCondition('sprods.selprod_cart_type', '!=', SellerProduct::CART_TYPE_RFQ_ONLY, 'AND', true);
                $this->addCondition('sprods.selprod_hide_price', '!=', applicationConstants::YES, 'AND', true);
            }

            $this->joinTable(SellerProduct::DB_TBL, 'INNER JOIN', 'p.product_id = sprods.selprod_product_id ' . $joinCondition . ' and selprod_active = ' . applicationConstants::ACTIVE . ' and selprod_deleted = ' . applicationConstants::NO, 'sprods');
            if ($this->langId) {
                $this->joinTable(SellerProduct::DB_TBL_LANG, 'LEFT OUTER JOIN', 'sprods.selprod_id = sprods_l.selprodlang_selprod_id AND sprods_l.selprodlang_lang_id = ' . $this->langId, 'sprods_l');
            }
        }
        if (0 < $bySeller) {
            $this->addCondition('selprod_user_id', '=', 'mysql_func_' . $bySeller, 'AND', true);
        } else {
            $this->addCondition('selprod_user_id', '>',  'mysql_func_0', 'AND', true);
        }
    }

    public function joinProductVariant($joinType = 'LEFT OUTER JOIN')
    {
        $this->joinTable(Product::DB_PRODUCT_TO_OPTION, $joinType, 'tpv.prodoption_product_id = p.product_id', 'tpv');
    }

    /* public function joinProductVariantOptions(){
    $this->joinTable( SellerProduct::DB_TBL_SELLER_PROD_OPTIONS, 'LEFT OUTER JOIN', 'pricetbl.selprod_id = tspo.selprodoption_selprod_id', 'tspo');
    $this->joinTable( OptionValue::DB_TBL, 'LEFT OUTER JOIN', 'tspo.selprodoption_optionvalue_id = opval.optionvalue_id', 'opval' );
    $this->joinTable( Option::DB_TBL, 'LEFT OUTER JOIN', 'opval.optionvalue_option_id = op.option_id', 'op' );
    $this->addGroupBy('tspo.selprodoption_selprod_id');
    // $this->addMultipleFields(array('GROUP_CONCAT( selprodoption_option_id ) as option_ids', 'GROUP_CONCAT( selprodoption_optionvalue_id ) as option_value_ids'));
    // 'GROUP_CONCAT( option_name ) as option_names', 'GROUP_CONCAT( optionvalue_name ) as option_value_names'
    if( $this->langId ){
    $this->joinTable( Option::DB_TBL.'_lang', 'LEFT OUTER JOIN', 'op.option_id = op_l.optionlang_option_id AND op_l.optionlang_lang_id = '. $this->langId, 'op_l' );
    $this->joinTable( OptionValue::DB_TBL.'_lang', 'LEFT OUTER JOIN', 'opval.optionvalue_id = opval_l.optionvaluelang_optionvalue_id AND opval_l.optionvaluelang_lang_id = '. $this->langId, 'opval_l' );
    }
    } */

    public function joinSellers()
    {
        $this->sellerUserJoined = true;
        $joinCondition = '';
        if (FatApp::getConfig('CONF_ENABLE_SELLER_SUBSCRIPTION_MODULE', FatUtility::VAR_INT, 0)) {
            $joinCondition = ' and user_has_valid_subscription = ' . applicationConstants::YES;
        }

        $this->joinTable(User::DB_TBL, 'INNER JOIN', 'selprod_user_id = seller_user.user_id and seller_user.user_is_supplier = ' . applicationConstants::YES . ' AND seller_user.user_deleted = ' . applicationConstants::NO . $joinCondition, 'seller_user');
        $this->joinTable(User::DB_TBL_CRED, 'INNER JOIN', 'credential_user_id = seller_user.user_id and credential_active = ' . applicationConstants::ACTIVE . ' and credential_verified = ' . applicationConstants::YES, 'seller_user_cred');
    }

    public function joinProductShippedBySeller($sellerId = 0)
    {
        $sellerId = FatUtility::int($sellerId);
        if (FatApp::getConfig('CONF_SHIPPED_BY_ADMIN_ONLY', FatUtility::VAR_INT, 0)) {
            $sellerId = 0;
        }
        $this->joinTable(Product::DB_PRODUCT_SHIPPED_BY_SELLER, 'LEFT OUTER JOIN', 'psbs.psbs_product_id = p.product_id and psbs.psbs_user_id = ' . $sellerId, 'psbs');
    }

    public function joinProductShippedBy()
    {
        $this->joinProductShippedBy = true;
        $cond = 'and psbs.psbs_user_id = selprod_user_id';
        if (FatApp::getConfig('CONF_SHIPPED_BY_ADMIN_ONLY', FatUtility::VAR_INT, 0)) {
            $cond = 'and psbs.psbs_user_id = 0';
        }
        $this->joinTable(Product::DB_PRODUCT_SHIPPED_BY_SELLER, 'LEFT OUTER JOIN', 'psbs.psbs_product_id = p.product_id ' . $cond, 'psbs');
    }

    public function joinProductFreeShipping()
    {
        $cond = 'and ps.ps_user_id = selprod_user_id';
        if (FatApp::getConfig('CONF_SHIPPED_BY_ADMIN_ONLY', FatUtility::VAR_INT, 0)) {
            $cond = 'and ps.ps_user_id = 0';
        }
        $this->joinTable(Product::DB_TBL_PRODUCT_SHIPPING, 'LEFT OUTER JOIN', 'ps.ps_product_id = p.product_id ' . $cond, 'ps');
    }

    public function setLocationBasedInnerJoin($innerJoin = true)
    {
        $this->locationBasedInnerJoin = $innerJoin;
    }

    public function joinShops($langId = 0, $isActive = true, $isDisplayStatus = true, $shopId = 0, $validUser = null)
    {
        /* if (!$this->sellerUserJoined) {
            trigger_error(Labels::getLabel('ERR_JOINSHOPS_CANNOT_BE_JOINED,_UNLESS_JOINSELLERS_IS_NOT_APPLIED.', $this->commonLangId), E_USER_ERROR);
        } */

        $langId = FatUtility::int($langId);
        if ($this->langId && 1 > $langId) {
            $langId = $this->langId;
        }

        $shopCondition = '';
        if ($validUser != null) {
            $shopCondition .= ' and shop.shop_user_valid = ' . applicationConstants::ACTIVE;
            $this->addCondition('shop.shop_user_valid', '=', 'mysql_func_' . applicationConstants::ACTIVE, 'AND', true);
        }

        if ($isActive) {
            $shopCondition .= ' and shop.shop_active = ' . applicationConstants::ACTIVE;
            $this->addCondition('shop.shop_active', '=', 'mysql_func_' . applicationConstants::ACTIVE, 'AND', true);
        }

        if ($isDisplayStatus) {
            $shopCondition .= ' and shop.shop_supplier_display_status = ' . applicationConstants::ON;
            $this->addCondition('shop.shop_supplier_display_status', '=', 'mysql_func_' . applicationConstants::ON, 'AND', true);
        }

        $shopId = FatUtility::int($shopId);
        if (0 < $shopId) {
            $shopCondition .= ' and shop.shop_id = ' . $shopId;
        }

        if ($isActive && FatApp::getConfig('CONF_ENABLE_SELLER_SUBSCRIPTION_MODULE', FatUtility::VAR_INT, 0)) {
            $shopCondition .= ' and shop_has_valid_subscription = ' . applicationConstants::YES;
        }

        $joinShopWithSubQuery = false;
        if (FatApp::getConfig('CONF_ENABLE_GEO_LOCATION', FatUtility::VAR_INT, 0) && !empty(FatApp::getConfig('CONF_GOOGLEMAP_API_KEY', FatUtility::VAR_STRING, ''))) {
            $prodGeoCondition = FatApp::getConfig('CONF_PRODUCT_GEO_LOCATION', FatUtility::VAR_INT, 0);
            switch ($prodGeoCondition) {
                case applicationConstants::BASED_ON_RADIUS:
                    if (array_key_exists('ykGeoLat', $this->geoAddress) && $this->geoAddress['ykGeoLat'] != '' && array_key_exists('ykGeoLng', $this->geoAddress) && $this->geoAddress['ykGeoLng'] != '') {
                        $shopSearch = new SearchBase(Shop::DB_TBL, 'shop');
                        $shopSearch->doNotCalculateRecords();
                        $shopSearch->doNotLimitRecords();
                        $shopSearch->addCondition('shop.shop_supplier_display_status', '=', 'mysql_func_' . applicationConstants::ON, 'AND', true);
                        $shopSearch->addCondition('shop.' . Shop::tblFld('active'), '=', 'mysql_func_' . applicationConstants::ACTIVE, 'AND', true);
                        $shopSearch->addCondition('shop.' . Shop::tblFld('user_valid'), '=', 'mysql_func_' . applicationConstants::ACTIVE, 'AND', true);
                        $shopSearch->addFld('*');
                        if ($isActive && FatApp::getConfig('CONF_ENABLE_SELLER_SUBSCRIPTION_MODULE', FatUtility::VAR_INT, 0)) {
                            $shopSearch->addCondition('shop' . Shop::tblFld('has_valid_subscription'), '=', 'mysql_func_' . applicationConstants::YES, 'AND', true);
                        }
                        /*  $shopSearch->addFld('( 6371 * acos( cos( radians(' . $this->geoAddress['ykGeoLat'] . ') ) * cos( radians( shop.`shop_lat` ) ) * cos( radians( shop.`shop_lng` ) - radians(' . $this->geoAddress['ykGeoLng'] . ') ) + sin( radians(' . $this->geoAddress['ykGeoLat'] . ') ) * sin( radians( shop.`shop_lat` ) ) ) ) AS distance'); */
                        $shopSearch->addFld('(ST_Distance_Sphere(point(' . $this->geoAddress['ykGeoLng'] . ', ' . $this->geoAddress['ykGeoLat'] . '), point(shop.`shop_lng`, shop.`shop_lat`)) / 1000) AS distance');
                        $shopSearch->addHaving('distance', '<=', FatApp::getConfig('CONF_RADIUS_DISTANCE_IN_MILES', FatUtility::VAR_INT, 10));
                        if (false == $this->locationBasedInnerJoin) {
                            $shopSubQuery = $shopSearch->getQuery();
                            $shopSearch = new SearchBase(Shop::DB_TBL, 'sshop');
                            $shopSearch->doNotCalculateRecords();
                            $shopSearch->doNotLimitRecords();
                            $shopSearch->addCondition('sshop.shop_supplier_display_status', '=', 'mysql_func_' . applicationConstants::ON, 'AND', true);
                            $shopSearch->addCondition('sshop.' . Shop::tblFld('active'), '=', 'mysql_func_' . applicationConstants::ACTIVE, 'AND', true);
                            $shopSearch->addCondition('sshop.' . Shop::tblFld('user_valid'), '=', 'mysql_func_' . applicationConstants::ACTIVE, 'AND', true);
                            if ($isActive && FatApp::getConfig('CONF_ENABLE_SELLER_SUBSCRIPTION_MODULE', FatUtility::VAR_INT, 0)) {
                                $shopSearch->addCondition('sshop.' . Shop::tblFld('has_valid_subscription'), '=', 'mysql_func_' . applicationConstants::YES, 'AND', true);
                            }
                            $shopSearch->addMultipleFields(array('sshop.*', 'shop.distance'));
                            $shopSearch->joinTable('(' . $shopSubQuery . ')', 'LEFT OUTER JOIN', 'shop.shop_id = sshop.shop_id', 'shop');
                        }
                        $joinShopWithSubQuery = true;
                    }
                    break;
                case applicationConstants::BASED_ON_CURRENT_LOCATION:
                    $level = FatApp::getConfig('CONF_LOCATION_LEVEL', FatUtility::VAR_INT, 0);
                    $countryBased = $stateBased = $zipBased = false;
                    if (applicationConstants::LOCATION_COUNTRY == $level) {
                        $countryBased = true;
                    } elseif (applicationConstants::LOCATION_STATE == $level) {
                        $countryBased = $stateBased = true;
                    } elseif (applicationConstants::LOCATION_ZIP == $level) {
                        $countryBased = $stateBased = $zipBased = true;
                    }

                    $locCondition = ' and shop_user_valid = 1';
                    if ($countryBased && array_key_exists('ykGeoCountryId', $this->geoAddress) && $this->geoAddress['ykGeoCountryId'] > 0) {
                        $locCondition .= ' and shop.shop_country_id = ' . $this->geoAddress['ykGeoCountryId'];
                    }

                    if ($stateBased && array_key_exists('ykGeoStateId', $this->geoAddress) && $this->geoAddress['ykGeoStateId'] > 0) {
                        $locCondition .= ' and shop.shop_state_id = ' . $this->geoAddress['ykGeoStateId'];
                    }

                    if ($zipBased && array_key_exists('ykGeoZip', $this->geoAddress) && $this->geoAddress['ykGeoZip'] > 0) {
                        $locCondition .= ' and shop.shop_postalcode = "' . $this->geoAddress['ykGeoZip'] . '"';
                    }

                    if (true == $this->locationBasedInnerJoin) {
                        $shopCondition .= $locCondition;
                        $this->addFld('1 as availableInLocation');
                    } else {
                        if (!empty($locCondition)) {
                            $this->addFld('if ((1 ' . $locCondition . '), 1, 0) as availableInLocation');
                        } else {
                            $this->addFld('1 as availableInLocation');
                        }
                    }
                    break;
            }
        }

        $locationBasedInnerJoin = (true == $this->locationBasedInnerJoin) ? 'INNER JOIN' : 'LEFT OUTER JOIN';
        if ($joinShopWithSubQuery) {
            $this->joinTable('(' . $shopSearch->getQuery() . ')', $locationBasedInnerJoin, 'selprod_user_id = shop.shop_user_id  ' . $shopCondition, 'shop');
        } else {

            $this->joinTable(Shop::DB_TBL, 'INNER JOIN', 'selprod_user_id = shop.shop_user_id ' . $shopCondition, 'shop');
        }

        $this->shopsJoined = true;

        if ($langId) {
            $this->joinShopsLang($langId);
        }
    }

    public function joinShopSpecifics()
    {
        if (!$this->shopsJoined) {
            trigger_error('Shops are not joined', E_USER_ERROR);
        }
        $this->joinTable(ShopSpecifics::DB_TBL, 'LEFT OUTER JOIN', 'shop.shop_id = ss.ss_shop_id', 'ss');
    }

    public function joinShopsLang($langId)
    {
        $this->joinTable(Shop::DB_TBL_LANG, 'LEFT OUTER JOIN', 'shop.shop_id = s_l.shoplang_shop_id AND shoplang_lang_id = ' . $langId, 's_l');
    }

    public function joinShopCountry($langId = 0, $isActive = true, $countryId = 0)
    {
        $langId = FatUtility::int($langId);
        if ($this->langId && 1 > $langId) {
            $langId = $this->langId;
        }

        $countryCondition = '';
        if ($isActive) {
            $countryCondition = ' and shop_country.country_active = ' . applicationConstants::ACTIVE;
            // $this->addCondition('shop_country.country_active', '=', 'mysql_func_' . applicationConstants::ACTIVE, 'AND', true);
        }

        if ($countryId) {
            $countryCondition .= ' and shop_country.country_id = ' . $countryId;
            // $this->addCondition('shop_country.country_id', '=', 'mysql_func_' . $countryId, 'AND', true);
        }

        $this->joinTable(Countries::DB_TBL, 'INNER JOIN', 'shop.shop_country_id = shop_country.country_id' . $countryCondition, 'shop_country');

        if ($langId) {
            $this->joinShopCountryLang($langId);
        }
    }

    public function joinShopCountryLang($langId)
    {
        $langId = FatUtility::int($langId);
        $this->joinTable(Countries::DB_TBL_LANG, 'LEFT OUTER JOIN', 'shop_country.country_id = shop_country_l.countrylang_country_id AND shop_country_l.countrylang_lang_id = ' . $langId, 'shop_country_l');
    }

    public function joinShopState($langId = 0, $isActive = true, $stateId = 0)
    {
        $langId = FatUtility::int($langId);
        if ($this->langId && 1 > $langId) {
            $langId = $this->langId;
        }

        $stateCondition = '';
        if ($isActive) {
            $stateCondition = 'and shop_state.state_active = ' . applicationConstants::ACTIVE;
            // $this->addCondition('shop_state.state_active', '=', 'mysql_func_' . applicationConstants::ACTIVE, 'AND', true);
        }

        if ($stateId) {
            $stateCondition .= ' and shop_state.state_id = ' . $stateId;
            // $this->addCondition('shop_state.state_id', '=', 'mysql_func_' . $stateId, 'AND', true);
        }

        $this->joinTable(States::DB_TBL, 'INNER JOIN', 'shop.shop_state_id = shop_state.state_id ' . $stateCondition, 'shop_state');

        if ($langId) {
            $this->joinTable(States::DB_TBL_LANG, 'LEFT OUTER JOIN', 'shop_state.state_id = shop_state_l.statelang_state_id AND shop_state_l.statelang_lang_id = ' . $langId, 'shop_state_l');
        }
    }

    public function joinBrands($langId = 0, $isActive = true, $isDeleted = true, $useInnerJoin = true, $criteria = [])
    {
        $langId = FatUtility::int($langId);
        if ($this->langId && 1 > $langId) {
            $langId = $this->langId;
        }
        $join = ($useInnerJoin && FatApp::getConfig("CONF_PRODUCT_BRAND_MANDATORY", FatUtility::VAR_INT, 1)) ? 'INNER JOIN' : 'LEFT OUTER JOIN';

        $brandCondition = '';
        if ($isActive && FatApp::getConfig("CONF_PRODUCT_BRAND_MANDATORY", FatUtility::VAR_INT, 1)) {
            $brandCondition = ' and brand.brand_active = ' . applicationConstants::ACTIVE;
            $this->addCondition('brand.brand_active', '=', 'mysql_func_' . applicationConstants::ACTIVE, 'AND', true);
        }


        if ($isDeleted && FatApp::getConfig("CONF_PRODUCT_BRAND_MANDATORY", FatUtility::VAR_INT, 1)) {
            $brandCondition .= ' and brand.brand_deleted = ' . applicationConstants::NO;
            $this->addCondition('brand.brand_deleted', '=', 'mysql_func_' . applicationConstants::NO, 'AND', true);
        }

        if (array_key_exists('brand_id', $criteria) && 0 < $criteria['brand_id']) {
            $brandCondition .= ' and brand.brand_id = ' . $criteria['brand_id'];
            $this->addCondition('brand.brand_id', '=', 'mysql_func_' . $criteria['brand_id'], 'AND', true);
        }

        $this->joinTable(Brand::DB_TBL, $join, 'p.product_brand_id = brand.brand_id' . $brandCondition, 'brand');

        if ($langId) {
            $this->joinBrandsLang($langId);
        }
    }

    public function joinBrandsLang($langId, $keyword = '')
    {
        $joinCondition = '';
        $joinBy = 'LEFT OUTER JOIN';
        if (!empty($keyword)) {
            $joinBy = 'INNER JOIN';
            $joinCondition = ' and tb_l.brand_name like ' . FatApp::getDb()->quoteVariable($keyword);
        }
        $this->joinTable(Brand::DB_TBL_LANG, $joinBy, 'brand.brand_id = tb_l.brandlang_brand_id AND brandlang_lang_id = ' . $langId . $joinCondition, 'tb_l');
    }

    public function joinProductToCategory($langId = 0, $isActive = true, $isDeleted = true, $useInnerJoin = true, $useRelationTable = false, $criteria = [])
    {
        $langId = FatUtility::int($langId);
        if ($this->langId && 1 > $langId) {
            $langId = $this->langId;
        }
        $join = ($useInnerJoin) ? 'INNER JOIN' : 'LEFT OUTER JOIN';

        $catCondition = '';
        if ($useInnerJoin) {
            $catCondition .= (isset($criteria['categoryIds']) && count($criteria['categoryIds']) > 0) ? ' and ptc_prodcat_id IN (' . implode(",", $criteria['categoryIds']) . ')' : '';
        }

        $this->joinTable(Product::DB_TBL_PRODUCT_TO_CATEGORY, $join, 'ptc.ptc_product_id = p.product_id' . $catCondition, 'ptc');

        $categoryActiveCondition = '';
        if ($isActive) {
            $categoryActiveCondition = 'and c.prodcat_active = ' . applicationConstants::ACTIVE;
            $this->addCondition('c.prodcat_active', '=', 'mysql_func_' . applicationConstants::ACTIVE, 'AND', true);
        }

        $categoryDeletedCondition = '';
        if ($isDeleted) {
            $categoryDeletedCondition = 'and c.prodcat_deleted = ' . applicationConstants::NO;
            $this->addCondition('c.prodcat_deleted', '=', 'mysql_func_' . applicationConstants::NO, 'AND', true);
        }

        $this->addCondition('c.prodcat_status', '=', 'mysql_func_' . ProductCategory::REQUEST_APPROVED, 'AND', true);

        if (true == $useRelationTable) {
            $this->joinCategoryRelationWithParent();
            $this->joinTable(ProductCategory::DB_TBL, $join, 'c.prodcat_id = cr.pcr_prodcat_id ' . $categoryActiveCondition . ' ' . $categoryDeletedCondition, 'c');
        } else {
            $this->joinTable(ProductCategory::DB_TBL, $join, 'c.prodcat_id = ptc.ptc_prodcat_id ' . $categoryActiveCondition . ' ' . $categoryDeletedCondition, 'c');
        }

        if ($langId) {
            $this->joinProductToCategoryLang($langId);
        }
    }

    /**
     * joinCategoryRelationWithParent
     * Used to find all the childrens of category
     * @return void
     */
    public function joinCategoryRelationWithParent()
    {
        $this->joinTable(ProductCategory::DB_TBL_PROD_CAT_RELATIONS, 'INNER JOIN', 'ptc.ptc_prodcat_id = cr.pcr_parent_id', 'cr');
    }

    /**
     * joinCategoryRelationWithChild
     * Used to find all the parents of category
     * @return void
     */
    public function joinCategoryRelationWithChild($parentId = 0)
    {
        $parentId = FatUtility::int($parentId);
        $cond = '';
        if (0 < $parentId) {
            $cond = 'and cr.pcr_parent_id = ' . $parentId;
        }
        $this->joinTable(ProductCategory::DB_TBL_PROD_CAT_RELATIONS, 'INNER JOIN', 'ptc.ptc_prodcat_id = cr.pcr_prodcat_id ' . $cond, 'cr');
    }

    public function joinProductToCategoryLang($langId)
    {
        $this->joinTable(ProductCategory::DB_TBL_LANG, 'LEFT OUTER JOIN', 'c_l.prodcatlang_prodcat_id = c.prodcat_id AND prodcatlang_lang_id = ' . $langId, 'c_l');
    }

    public function joinFavouriteProducts($user_id)
    {
        $this->joinTable(Product::DB_TBL_PRODUCT_FAVORITE, 'LEFT OUTER JOIN', 'ufp.ufp_selprod_id = selprod_id and ufp.ufp_user_id = ' . $user_id, 'ufp');
    }

    public function joinProductToTax($langId = 0, $isActive = true, $isDeleted = true, $useInnerJoin = true)
    {
        $join = ($useInnerJoin) ? 'INNER JOIN' : 'LEFT OUTER JOIN';
        $this->joinTable(Tax::DB_TBL_PRODUCT_TO_TAX, $join, 'ptt.ptt_product_id = product_id', 'ptt');

        $extraQuery = '';
        if ($useInnerJoin) {
            if ($isActive) {
                $extraQuery = ' and pt.taxcat_active = ' . applicationConstants::ACTIVE;
            }
            if ($isDeleted) {
                $extraQuery .= ' and pt.taxcat_deleted = ' . applicationConstants::NO;
            }
        } else {
            if ($isActive) {
                $this->addCondition('pt.taxcat_active', '=', 'mysql_func_' . applicationConstants::ACTIVE, 'AND', true);
            }
            if ($isDeleted) {
                $this->addCondition('pt.taxcat_deleted', '=', 'mysql_func_' . applicationConstants::NO, 'AND', true);
            }
        }

        $this->joinTable(Tax::DB_TBL, $join, 'pt.taxcat_id = ptt.ptt_taxcat_id AND pt.taxcat_plugin_id = ' . Tax::getActivatedServiceId() . $extraQuery, 'pt');

        if (1 < $langId) {
            $this->joinProductToTaxLang($langId);
        }
    }

    public function joinProductToTaxLang($langId)
    {
        $this->joinTable(Tax::DB_TBL_LANG, 'LEFT OUTER JOIN', 'pt_l.taxcatlang_taxcat_id = pt.taxcat_id AND prodcatlang_lang_id = ' . $langId, 'pt_l');
    }

    public function joinUserWishListProducts($user_id)
    {
        $user_id = FatUtility::int($user_id);
        $wislistPSrchObj = new UserWishListProductSearch();
        $wislistPSrchObj->joinWishLists();
        $wislistPSrchObj->doNotCalculateRecords();
        $wislistPSrchObj->doNotLimitRecords();
        $wislistPSrchObj->addCondition('uwlist_user_id', '=', 'mysql_func_' . $user_id, 'AND', true);
        $wislistPSrchObj->addMultipleFields(array('uwlp_selprod_id', 'uwlp_uwlist_id'));
        $wishListSubQuery = $wislistPSrchObj->getQuery();
        $this->joinTable('(' . $wishListSubQuery . ')', 'LEFT OUTER JOIN', 'uwlp.uwlp_selprod_id = selprod_id', 'uwlp');
    }

    public function addCategoryCondition($category, bool $joinWithRelationTableInstead = false)
    {
        if (!empty($category) && true == $joinWithRelationTableInstead) {
            $this->joinTable(ProductCategory::DB_TBL_PROD_CAT_RELATIONS, 'INNER JOIN', 'pcr.pcr_prodcat_id = c.prodcat_id', 'pcr');
        }

        $categoryArr = [];
        if (is_numeric($category)) {
            $category_id = FatUtility::int($category);
            if (!$category_id) {
                return;
            }
            if (false == $joinWithRelationTableInstead) {
                $catCode = ProductCategory::getAttributesById($category_id, 'prodcat_code');
                $this->addCondition('c.prodcat_code', 'LIKE', $catCode . '%', 'AND', true);
            } else {
                $categoryArr[] = $category_id;
            }
        } else {
            if (empty($category)) {
                return;
            }
            $categoryArr = $category;
            if (!is_array($category)) {
                $categoryArr = explode(",", $category);
            }

            if (0 < count(array_filter($categoryArr)) && false == $joinWithRelationTableInstead) {
                $condition = '(';
                foreach ($categoryArr as $catId) {
                    $catId = FatUtility::int($catId);
                    if (1 > $catId) {
                        continue;
                    }
                    $catCode = ProductCategory::getAttributesById($catId, 'prodcat_code');
                    $condition .= " c.prodcat_code LIKE '" . $catCode . "%' OR";
                }
                $condition = substr($condition, 0, -2);
                $condition .= ')';

                $this->addDirectCondition($condition);
            }
        }

        if (!empty($categoryArr) && true == $joinWithRelationTableInstead) {
            $categoryArr = array_filter($categoryArr);
            $this->addCondition('pcr.pcr_parent_id', 'IN', $categoryArr);
        }
    }

    public function addProductShippedBySellerCondition($sellerId = 0)
    {
        $sellerId = FatUtility::int($sellerId);
        $this->addDirectCondition(" ( isnull(psbs.psbs_user_id) or  psbs.psbs_user_id ='" . $sellerId . "')");
    }

    public function addKeywordSearch($keyword, $obj = false, $useRelevancy = true)
    {
        if (empty($keyword) || $keyword == '') {
            return;
        }

        if (false === $obj) {
            $obj = $this;
        }

        $keywordLength = mb_strlen($keyword);
        $cnd = $obj->addCondition('product_isbn', 'LIKE', '%' . $keyword . '%');
        /* $cnd->attachCondition('product_upc', 'LIKE', '%' . $keyword . '%'); */

        $arr = explode(' ', $keyword);
        $arr_keywords = array();
        foreach ($arr as $value) {
            $value = trim($value);
            if (strlen($value) < 3) {
                continue;
            }
            $arr_keywords[] = $value;
        }

        if (count($arr_keywords) > 0) {
            if ($keywordLength <= 80) {
                foreach ($arr_keywords as $value) {
                    $cnd->attachCondition('product_tags_string', 'LIKE', '%' . $value . '%');
                    $cnd->attachCondition('selprod_title', 'LIKE', '%' . $value . '%');
                    /*  $cnd->attachCondition('product_name', 'LIKE', '%' . $value . '%'); */
                    $cnd->attachCondition('brand_name', 'LIKE', '%' . $value . '%');
                    $cnd->attachCondition('prodcat_name', 'LIKE', '%' . $value . '%');
                }
            }
            $strKeyword = FatApp::getDb()->quoteVariable('%' . $keyword . '%');
            if ($useRelevancy === true) {
                $obj->addFld(
                    "IF(product_isbn LIKE $strKeyword, 15, 0)
                + IF(selprod_title LIKE $strKeyword, 4, 0)                
                + IF(product_tags_string LIKE $strKeyword, 4, 0)
                AS keyword_relevancy"
                );
            } else {
                $obj->addFld('0 AS keyword_relevancy');
            }
        } else {
            // $cnd->attachCondition('product_tags_string', 'LIKE', '%' . $value . '%');
            $obj->addFld('0 AS keyword_relevancy');
        }
    }

    public function addProductIdCondition($product_id)
    {
        $product_id = FatUtility::int($product_id);
        if (!$product_id) {
            trigger_error(Labels::getLabel('ERR_PRODUCT_ID_NOT_PASSED!', $this->commonLangId), E_USER_ERROR);
        }
        $this->addCondition('product_id', '=', 'mysql_func_' . $product_id, 'AND', true);
    }

    public function addBrandCondition($brand)
    {
        //@todo enhancements
        if (FatApp::getConfig('CONF_PRODUCT_BRAND_MANDATORY', FatUtility::VAR_INT, 1) == 1) {
            if (is_numeric($brand)) {
                $brandId = FatUtility::int($brand);
                $this->addCondition('brand_id', '=', 'mysql_func_' . $brandId, 'AND', true);
            } elseif (is_array($brand) && 0 < count($brand)) {
                $brand = array_filter(array_unique($brand));
                if (!empty($brand)) {
                    $this->addDirectCondition('brand_id IN (' . implode(',', $brand) . ')');
                }
            } else {
                if (!empty($brand)) {
                    $brand = explode(",", $brand);
                    $brand = array_filter(array_unique($brand));
                    $this->addDirectCondition('brand_id IN (' . implode(',', $brand) . ')');
                }
            }
        } else {
            if (is_numeric($brand)) {
                $brandId = FatUtility::int($brand);
                $brandId = ($brandId <= 0) ? 0 : $brandId;
                $this->addCondition('product_brand_id', '=', 'mysql_func_' . $brandId, 'AND', true);
            } elseif (is_array($brand) && 0 < count($brand)) {
                $brand = array_filter(array_unique($brand));
                $brandString = str_replace('-1', '0', implode(',', $brand));
                $this->addDirectCondition('product_brand_id IN (' . $brandString . ')');
            } else {
                if (!empty($brand)) {
                    $brand = explode(",", $brand);
                    $brand = array_filter(array_unique($brand));
                    $brandString = str_replace('-1', '0', implode(',', $brand));
                    $this->addDirectCondition('product_brand_id IN (' . $brandString . ')');
                }
            }
        }
    }

    public function addOptionCondition($optionValue, $obj = false, $alias = '')
    {
        if ($obj === false) {
            $obj = $this;
        }

        if ($alias != '') {
            $alias .= '.';
        }

        if (is_array($optionValue)) {
            $str = '( ';
            $orCnd = '';
            $andCnd = '';
            foreach ($optionValue as $val) {
                //$str.= $andCnd;
                if (1 > FatUtility::int($val)) {
                    continue;
                }
                $str .= $orCnd . " " . $alias . "selprod_code like '%\_" . $val . "\_%' or " . $alias . "selprod_code like '%\_" . $val . "'";
                $orCnd = ' or';
                //$andCnd = ") and (";
            }
            $str .= " )";
            $obj->addDirectCondition($str);
        } elseif (strpos($optionValue, ",") === false) {
            if (strpos($optionValue, "_") === false) {
                $opVal = $optionValue;
            } else {
                $opVal = substr($optionValue, strpos($optionValue, "_") + 1);
            }

            $opVal = FatUtility::int($opVal);
            $obj->addDirectCondition(" (" . $alias . "selprod_code like '%\_" . $opVal . "\_%' or " . $alias . "selprod_code like '%\_" . $opVal . "') ");
        } else {
            $optionValueArr = explode(",", $optionValue);
            sort($optionValueArr);
            $opValArr = array();
            foreach ($optionValueArr as $val) {
                $opVal = explode("_", $val);
                $opValArr[$opVal[0]][] = $opVal[1];
            }
            $str = '( ';
            $orCnd = '';
            $andCnd = '';
            foreach ($opValArr as $row) {
                $str .= $andCnd;
                foreach ($row as $val) {
                    if (1 > FatUtility::int($val)) {
                        continue;
                    }
                    $str .= $orCnd . " " . $alias . "selprod_code like '%\_" . $val . "\_%' or " . $alias . "selprod_code like '%\_" . $val . "'";
                    $orCnd = 'or';
                }
                $orCnd = "";
                $andCnd = ") and (";
            }
            $str .= " )";
            $obj->addDirectCondition($str);
        }
    }

    /* public function addUPCCondition($upc){
    $this->addCondition('product_upc', 'like', $upc );
    }
    public function addISBNCondition($isbn){
    $this->addCondition('product_isbn', 'like', $isbn );
    } */

    public function addShopIdCondition($shop_id)
    {
        $shop_id = FatUtility::int($shop_id);
        if (!$shop_id) {
            trigger_error(Labels::getLabel('ERR_SHOP_ID_NOT_PASSED', $this->commonLangId), E_USER_ERROR);
        }
        $this->addCondition('shop_id', '=', 'mysql_func_' . $shop_id, 'AND', true);
    }

    public function addCollectionIdCondition($collection_id)
    {
        $collection_id = FatUtility::int($collection_id);
        if (!$collection_id) {
            trigger_error(Labels::getLabel('ERR_COLLECTION_ID_NOT_PASSED', $this->commonLangId), E_USER_ERROR);
        }

        $this->joinTable(ShopCollection::DB_TBL_SHOP_COLLECTION_PRODUCTS, 'INNER JOIN', ShopCollection::DB_SELLER_PRODUCTS_PREFIX . 'id = ' . ShopCollection::DB_TBL_SHOP_COLLECTION_PRODUCTS_PREFIX . 'selprod_id and ' . ShopCollection::DB_TBL_SHOP_COLLECTION_PRODUCTS_PREFIX . 'scollection_id = ' . $collection_id);
        //return $srch;
    }


    public function addConditionCondition($condition, $obj = false)
    {
        if ($obj === false) {
            $obj = $this;
        }

        if (is_numeric($condition)) {
            $condition = FatUtility::int($condition);
            $obj->addCondition('selprod_condition', '=', 'mysql_func_' . $condition, 'AND', true);
        } elseif (is_array($condition)) {
            $condition = array_filter(array_unique($condition));
            $obj->addDirectCondition('selprod_condition IN (' . implode(',', $condition) . ')');
        } else {
            $condition = explode(",", $condition);
            $condition = FatUtility::int($condition);
            $condition = array_filter(array_unique($condition));
            $obj->addDirectCondition('selprod_condition IN (' . implode(',', $condition) . ')');
        }
    }

    public function addMoreSellerCriteria($productCode, $sellerId = 0, $includeSeller = false)
    {
        $sellerId = FatUtility::int($sellerId);
        if ($productCode == '') {
            trigger_error(Labels::getLabel('ERR_INVALID_ARGUMENT_PASSED', $this->commonLangId), E_USER_ERROR);
        }

        //$this->setDefinedCriteria();
        $this->joinSellerProducts();
        //$this->joinSellers();
        $this->joinShops(0, true, true, 0, true);
        $this->joinShopCountry();
        $this->joinShopState();
        $this->joinBrands();
        $this->joinSellerSubscription();
        $this->addSubscriptionValidCondition();
        $this->joinProductToCategory();
        $this->doNotCalculateRecords();
        $this->doNotLimitRecords();
        $this->addCondition('selprod_deleted', '=', 'mysql_func_' . applicationConstants::NO, 'AND', true);
        if ($sellerId > 0 && false === $includeSeller) {
            $this->addCondition('selprod_user_id', '!=', $sellerId);
        } else if ($sellerId > 0 && true === $includeSeller) {
            $this->addCondition('selprod_user_id', '=', $sellerId);
        }
        if (is_array($productCode)) {
            $this->addCondition('selprod_code', 'IN', $productCode);
        } else {
            $this->addCondition('selprod_code', '=', $productCode);
        }
    }

    public function excludeOutOfStockProducts()
    {
        $this->addCondition('selprod_stock', '>', 'mysql_func_' . applicationConstants::NO, 'AND', true);
    }

    public function addAttributesCriteria($product_id, $lang_id)
    {
        $product_id = FatUtility::int($product_id);
        $lang_id = FatUtility::int($lang_id);
        if (!$product_id || !$lang_id) {
            trigger_error(Labels::getLabel('ERR_INVALID_ARGUMENT_PASSED', $this->commonLangId), E_USER_ERROR);
        }

        $this->joinTable(AttributeGroup::DB_TBL_ATTRIBUTES, 'INNER JOIN', 'p.product_attrgrp_id = attr.attr_attrgrp_id', 'attr');
        $this->joinTable(AttributeGroup::DB_TBL_ATTRIBUTES . '_lang', 'LEFT OUTER JOIN', 'attr.attr_id = attr_l.attrlang_attr_id AND attr_l.attrlang_lang_id = ' . $lang_id, 'attr_l');
        $this->addProductIdCondition($product_id);
    }

    public function joinProductRating()
    {
        $selProdReviewObj = new SelProdReviewSearch();
        $selProdReviewObj->joinSellerProducts();
        $selProdReviewObj->joinSelProdRating();
        $selProdReviewObj->addCondition('ratingtype_type', '=', 'mysql_func_' . RatingType::TYPE_PRODUCT, 'AND', true);
        $selProdReviewObj->doNotCalculateRecords();
        $selProdReviewObj->doNotLimitRecords();
        $selProdReviewObj->addGroupBy('spr.spreview_product_id');
        $selProdReviewObj->addCondition('spr.spreview_status', '=', 'mysql_func_' . SelProdReview::STATUS_APPROVED, 'AND', true);
        $selProdReviewObj->addMultipleFields(array('spr.spreview_selprod_id', 'spreview_product_id', "ROUND(AVG(sprating_rating),2) as prod_rating", "count(spreview_id) as totReviews"));
        $selProdRviewSubQuery = $selProdReviewObj->getQuery();
        $this->joinTable('(' . $selProdRviewSubQuery . ')', 'LEFT OUTER JOIN', 'sq_sprating.spreview_product_id = product_id', 'sq_sprating');
    }

    public function joinSellerOrder($langId = 0)
    {
        if (!$this->sellerUserJoined) {
            trigger_error(Labels::getLabel('ERR_SELLER_MUST_JOINED.', CommonHelper::getLangId()), E_USER_ERROR);
        }
        $this->sellerSubscriptionOrderJoined = true;
        if (FatApp::getConfig('CONF_ENABLE_SELLER_SUBSCRIPTION_MODULE', FatUtility::VAR_INT, 0)) {
            $this->joinTable(Orders::DB_TBL, 'INNER JOIN', 'o.order_user_id=seller_user.user_id AND o.order_type=' . ORDERS::ORDER_SUBSCRIPTION . ' AND o.order_payment_status =1', 'o');
        }
    }

    public function joinSellerOrderSubscription($langId = 0, $includeDateCondition = false)
    {
        $langId = FatUtility::int($langId);

        if (!$this->sellerSubscriptionOrderJoined) {
            trigger_error(Labels::getLabel('ERR_SELLER_SUBSCRIPTION_ORDER_MUST_JOINED.', $this->commonLangId), E_USER_ERROR);
        }

        $validDateCondition = '';
        if ($includeDateCondition) {
            $validDateCondition = " and oss.ossubs_till_date >= '" . date('Y-m-d') . "'";
        }

        if (FatApp::getConfig('CONF_ENABLE_SELLER_SUBSCRIPTION_MODULE')) {
            $this->joinTable(OrderSubscription::DB_TBL, 'INNER JOIN', 'o.order_id = oss.ossubs_order_id and oss.ossubs_status_id=' . FatApp::getConfig('CONF_DEFAULT_SUBSCRIPTION_PAID_ORDER_STATUS') . $validDateCondition, 'oss');
            if ($langId > 0) {
                $this->joinTable(OrderSubscription::DB_TBL_LANG, 'LEFT OUTER JOIN', 'oss.ossubs_id = ossl.' . OrderSubscription::DB_TBL_LANG_PREFIX . 'ossubs_id AND ossubslang_lang_id = ' . $langId, 'ossl');
            }
        }
    }

    public function joinSellerSubscription($langId = 0, $joinSeller = false, $includeDateCondition = false)
    {
        $langId = FatUtility::int($langId);
        if ($this->langId && 1 > $langId) {
            $langId = $this->langId;
        }

        if ($joinSeller && FatApp::getConfig('CONF_ENABLE_SELLER_SUBSCRIPTION_MODULE', FatUtility::VAR_INT, 0)) {
            $this->joinSellers();
        }

        /* if (!$this->sellerUserJoined) {
            trigger_error(Labels::getLabel('ERR_SELLER_MUST_JOINED.', CommonHelper::getLangId()), E_USER_ERROR);
        }

        $validDateCondition = '';
        if ($includeDateCondition) {
            $validDateCondition = " and oss.ossubs_till_date >= '" . date('Y-m-d') . "'";
        }

        if (FatApp::getConfig('CONF_ENABLE_SELLER_SUBSCRIPTION_MODULE', FatUtility::VAR_INT, 0)) {            

            $sSrch = new SearchBase(Orders::DB_TBL, 'o');
            $sSrch->addMultipleFields(['max(o.order_id) as currentOrderId']);
            $sSrch->addCondition('o.order_type', '=', 'mysql_func_' . Orders::ORDER_SUBSCRIPTION, 'AND', true);
            $sSrch->addCondition('o.order_payment_status', '=',  'mysql_func_' . Orders::ORDER_PAYMENT_PAID, 'AND', true);
            $sSrch->addGroupBy('o.order_id');
            $sSrch->doNotCalculateRecords();
            $sSrch->doNotLimitRecords();

            $srch = new searchBase(Orders::DB_TBL, 'o');
            $srch->joinTable('(' . $sSrch->getQuery() . ')', 'INNER JOIN', 'otemp.currentOrderId=o.order_id', 'otemp');
            $srch->joinTable(OrderSubscription::DB_TBL, 'INNER JOIN', 'o.order_id = oss.ossubs_order_id and oss.ossubs_status_id =' . FatApp::getConfig('CONF_DEFAULT_SUBSCRIPTION_PAID_ORDER_STATUS') . $validDateCondition, 'oss');
            if ($langId > 0) {
                $srch->joinTable(OrderSubscription::DB_TBL_LANG, 'LEFT OUTER JOIN', 'oss.ossubs_id = ossl.' . OrderSubscription::DB_TBL_LANG_PREFIX . 'ossubs_id AND ossubslang_lang_id = ' . $langId, 'ossl');
            }
            $srch->addCondition('oss.ossubs_status_id', 'IN ', Orders::getActiveSubscriptionStatusArr());
            $srch->addCondition('o.order_type', '=', 'mysql_func_' . ORDERS::ORDER_SUBSCRIPTION, 'AND', true);
            $srch->addCondition('o.order_payment_status', '=', 'mysql_func_' . Orders::ORDER_PAYMENT_PAID, 'AND', true);
            $srch->doNotCalculateRecords();
            $srch->doNotLimitRecords();
            $srch->addGroupBy('o.order_user_id');
            $srch->addMultipleFields(array('oss.*', 'order_user_id', 'order_id', 'order_type'));
            $this->joinTable('(' . $srch->getQuery() . ')', 'INNER JOIN', 'oss.order_user_id=seller_user.user_id', 'oss');
        } */
    }

    public function addSubscriptionValidCondition($date = '')
    {
        /* if ($date == '') {
            $date = date("Y-m-d");
        }
        if (FatApp::getConfig('CONF_ENABLE_SELLER_SUBSCRIPTION_MODULE')) {
            $this->addCondition('oss.ossubs_till_date', '>=', $date);
            $this->addCondition('ossubs_status_id', 'IN ', Orders::getActiveSubscriptionStatusArr());
        } */
    }

    public function joinSellerProductSpecifics()
    {
        $this->joinTable(SellerProductSpecifics::DB_TBL, 'LEFT JOIN', 'sps.sps_selprod_id = selprod_id', 'sps');
    }

    public function joinProductSpecifics()
    {
        $this->joinTable(ProductSpecifics::DB_TBL, 'LEFT JOIN', 'ps.ps_product_id = p.product_id', 'ps');
    }

    public function joinShippingPackages($langId = 0)
    {
        $langId = FatUtility::int($langId);
        if ($this->langId && 1 > $langId) {
            $langId = $this->langId;
        }

        $this->joinTable(ShippingPackage::DB_TBL, 'LEFT OUTER JOIN', 'shipkg.shippack_id = p.product_ship_package', 'shipkg');
    }

    public function joinShippingProfileProducts()
    {
        if (!$this->joinProductShippedBy) {
            trigger_error(Labels::getLabel('ERR_JOINPRODUCTSHIPPEDBY_FUNCTION_NOT_JOINED.', $this->commonLangId), E_USER_ERROR);
        }

        $joinCondition = 'if(product_seller_id = 0, (if(psbs.psbs_user_id > 0, spprod.shippro_user_id = psbs.psbs_user_id, spprod.shippro_user_id = 0)), (spprod.shippro_user_id = selprod_user_id))';
        if (FatApp::getConfig('CONF_SHIPPED_BY_ADMIN_ONLY', FatUtility::VAR_INT, 0)) {
            $joinCondition = 'spprod.shippro_user_id = 0';
        }

        $this->joinTable(ShippingProfileProduct::DB_TBL, 'LEFT OUTER JOIN', 'spprod.shippro_product_id = selprod_product_id and ' . $joinCondition, 'spprod');
    }

    public function joinShippingProfile($langId = 0)
    {
        $this->joinTable(ShippingProfile::DB_TBL, 'LEFT OUTER JOIN', 'spprod.shippro_shipprofile_id = spprof.shipprofile_id and spprof.shipprofile_active = ' . applicationConstants::YES, 'spprof');
        if (0 < $langId) {
            $this->joinTable(ShippingProfile::DB_TBL_LANG, 'LEFT OUTER JOIN', 'spprof_l.shipprofilelang_shipprofile_id = spprof.shipprofile_id and spprof_l.shipprofilelang_lang_id = ' . $langId, 'spprof_l');
        }
    }

    public function joinShippingProfileZones()
    {
        $this->joinTable(ShippingProfileZone::DB_TBL, 'LEFT OUTER JOIN', 'shippz.shipprozone_shipprofile_id = spprof.shipprofile_id', 'shippz');
    }

    public function joinShippingZones()
    {
        $this->joinTable(ShippingZone::DB_TBL, 'LEFT OUTER JOIN', 'shipz.shipzone_id = shippz.shipprozone_shipzone_id and shipz.shipzone_active = ' . applicationConstants::YES, 'shipz');
    }

    public function joinShippingRates($langId = 0)
    {
        $langId = FatUtility::int($langId);
        if ($this->langId && 1 > $langId) {
            $langId = $this->langId;
        }

        $this->joinTable(ShippingRate::DB_TBL, 'LEFT OUTER JOIN', 'shipr.shiprate_shipprozone_id = shippz.shipprozone_id', 'shipr');
        if (0 < $langId) {
            $this->joinTable(ShippingRate::DB_TBL_LANG, 'LEFT OUTER JOIN', 'shipr_l.shipratelang_shiprate_id = shipr.shiprate_id and shipr_l.shipratelang_lang_id = ' . $langId, 'shipr_l');
        }
    }

    public function joinShippingLocations($countryId, $stateId, $langId = 0, $innerJoin = true)
    {
        $langId = FatUtility::int($langId);
        if ($this->langId && 1 > $langId) {
            $langId = $this->langId;
        }

        $srch = ShippingZone::getZoneLocationSearchObject($langId);
        $srch->addDirectCondition("(shiploc_country_id = '-1' or (shiploc_country_id = '" . $countryId . "' and (shiploc_state_id = '-1' or shiploc_state_id = '" . $stateId . "')) )");
        $srch->doNotCalculateRecords();
        $srch->doNotLimitRecords();

        $joinCondition = (true == $innerJoin) ? 'INNER JOIN' : 'LEFT OUTER JOIN';

        $this->joinTable('(' . $srch->getQuery() . ')', $joinCondition, 'shiploc.shiploc_shipzone_id = shippz.shipprozone_shipzone_id', 'shiploc');
    }

    public function validateAndJoinDeliveryLocation($includeShipingProfileCheck = false, $addAvailableInLocation = true)
    {
        if (FatApp::getConfig('CONF_ENABLE_GEO_LOCATION', FatUtility::VAR_INT, 0) && !empty(FatApp::getConfig('CONF_GOOGLEMAP_API_KEY', FatUtility::VAR_STRING, ''))) {
            $prodGeoCondition = FatApp::getConfig('CONF_PRODUCT_GEO_LOCATION', FatUtility::VAR_INT, 0);
            switch ($prodGeoCondition) {
                case applicationConstants::BASED_ON_DELIVERY_LOCATION:
                    $shippingServiceActive = Plugin::isActiveByType(Plugin::TYPE_SHIPPING_SERVICES);
                    if (!$shippingServiceActive) {
                        $this->joinDeliveryLocations();
                        if (true == $includeShipingProfileCheck) {
                            $this->addHaving('shippingProfile', 'IS NOT', 'mysql_func_null', 'and', true);
                            if ($addAvailableInLocation) {
                                $this->addFld('1 as availableInLocation');
                            }
                        } else if ($addAvailableInLocation) {
                            $this->addFld('if(p.product_type = ' . Product::PRODUCT_TYPE_PHYSICAL . ', ifnull(shipprofile.shippro_product_id,0), 1) as availableInLocation');
                        }
                    }

                    break;
                case applicationConstants::BASED_ON_RADIUS:
                    if (array_key_exists('ykGeoLat', $this->geoAddress) && $this->geoAddress['ykGeoLat'] != '' && array_key_exists('ykGeoLng', $this->geoAddress) && $this->geoAddress['ykGeoLng'] != '') {
                        $distanceInMiles = FatApp::getConfig('CONF_RADIUS_DISTANCE_IN_MILES', FatUtility::VAR_INT, 10);
                        if ($addAvailableInLocation) {
                            $this->addFld('if(shop.distance <= ' . $distanceInMiles .  ', 1, 0) as availableInLocation');
                        }
                    } else if ($addAvailableInLocation) {
                        $this->addFld('0 as availableInLocation');
                    }
                    break;
                case applicationConstants::BASED_ON_CURRENT_LOCATION:

                    break;
                default:
                    if ($addAvailableInLocation) {
                        $this->addFld('1 as availableInLocation');
                    }
                    break;
            }
        } else if ($addAvailableInLocation) {
            $this->addFld('1 as availableInLocation');
        }
    }

    public function joinDeliveryLocations($langId = 0)
    {
        $langId = FatUtility::int($langId);
        if ($this->langId && 1 > $langId) {
            $langId = $this->langId;
        }

        if (empty($this->geoAddress)) {
            trigger_error(Labels::getLabel('ERR_SETGOEADDRESS_FUNCTION_NOT_JOINED.', $langId), E_USER_ERROR);
        }

        $countryId = 0;
        $stateId = 0;
        if (array_key_exists('ykGeoCountryId', $this->geoAddress) && $this->geoAddress['ykGeoCountryId'] > 0) {
            $countryId = $this->geoAddress['ykGeoCountryId'];
        }

        if (array_key_exists('ykGeoStateId', $this->geoAddress) && $this->geoAddress['ykGeoStateId'] > 0) {
            $stateId = $this->geoAddress['ykGeoStateId'];
        }

        $srch = ShippingProfileProduct::getUserSearchObject(0, true);
        $srch->joinTable(ShippingProfile::DB_TBL, 'INNER JOIN', 'sppro.shippro_shipprofile_id = spprof.shipprofile_id and spprof.shipprofile_active = ' . applicationConstants::YES, 'spprof');
        $srch->joinTable(ShippingProfileZone::DB_TBL, 'INNER JOIN', 'shippz.shipprozone_shipprofile_id = spprof.shipprofile_id', 'shippz');
        $srch->joinTable(ShippingZone::DB_TBL, 'INNER JOIN', 'shipz.shipzone_id = shippz.shipprozone_shipzone_id and shipz.shipzone_active = ' . applicationConstants::YES, 'shipz');
        $srch->joinTable(Product::DB_PRODUCT_SHIPPED_BY_SELLER, 'LEFT OUTER JOIN', 'psbs.psbs_product_id = tp.product_id', 'psbs');

        $joinCondition = 'if(tp.product_seller_id = 0, (if(psbs.psbs_user_id > 0, sppro.shippro_user_id = psbs.psbs_user_id, sppro.shippro_user_id = 0)), (sppro.shippro_user_id = tp.product_seller_id))';
        if (FatApp::getConfig('CONF_SHIPPED_BY_ADMIN_ONLY', FatUtility::VAR_INT, 0)) {
            $joinCondition = 'sppro.shippro_user_id = 0';
        }
        $srch->addDirectCondition($joinCondition);

        $tempSrch = ShippingZone::getZoneLocationSearchObject();
        $tempSrch->addDirectCondition("(shiploc_country_id = '-1' or (shiploc_country_id = '" . $countryId . "' and (shiploc_state_id = '-1' or shiploc_state_id = '" . $stateId . "')) )");
        $tempSrch->doNotCalculateRecords();
        $tempSrch->doNotLimitRecords();

        $srch->joinTable('(' . $tempSrch->getQuery() . ')', 'INNER JOIN', 'shiploc.shiploc_shipzone_id = shippz.shipprozone_shipzone_id', 'shiploc');

        $this->joinTable('(' . $srch->getQuery() . ')', 'LEFT OUTER JOIN', 'shipprofile.shippro_product_id = p.product_id', 'shipprofile');
        $this->addFld('if(p.product_type = ' . Product::PRODUCT_TYPE_PHYSICAL . ', shipprofile.shippro_product_id, -1) as shippingProfile');
        // $this->joinTable('(' . $srch->getQuery() . ')', 'INNER JOIN', '(if(p.product_type = ' . Product::PRODUCT_TYPE_PHYSICAL . ', shipprofile.shippro_product_id = p.product_id, p.product_id =  p.product_id))', 'shipprofile');
        /* $this->addFld('if(p.product_type = ' . Product::PRODUCT_TYPE_PHYSICAL . ', shipprofile.shippro_product_id, -1) as shippingProfile');
        $this->addHaving('shippingProfile', '!=', 'null'); */
    }
}
