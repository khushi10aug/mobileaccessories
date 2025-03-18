<?php

class FilterHelper extends FatUtility
{
    public const LAYOUT_DEFAULT = 1;
    public const LAYOUT_TOP = 2;

    public const ENCRYPTION_KEY = 'vt%qkpCDRWB*bq@R&#4e';
    public const ENCRYPTION_IV = 'r&!qmJ#zvQaIK9VKsnZa';

    public static function getLayouts(int $langId)
    {
        return [
            self::LAYOUT_DEFAULT => Labels::getLabel('LBL_Default', $langId),
            self::LAYOUT_TOP => Labels::getLabel('LBL_TOP', $langId)
        ];
    }

    public static function getSearchObj($langId, $headerFormParamsAssocArr)
    {
        $langId = FatUtility::int($langId);
        $post = FatApp::getPostedData();

        $prodSrchObj = new ProductSearch($langId);
        if (array_key_exists('addFld', $headerFormParamsAssocArr)) {
            $prodSrchObj->addFld($headerFormParamsAssocArr['addFld']);
        }
        /*
        $prodSrchObj->setDefinedCriteria(0, 0, $headerFormParamsAssocArr, true);
        $prodSrchObj->joinProductToCategory();
        $prodSrchObj->joinSellerSubscription(0, false, true);
        $prodSrchObj->addSubscriptionValidCondition();*/
        $prodSrchObj->joinSellerProducts(0, '', $headerFormParamsAssocArr, true);
        $prodSrchObj->unsetDefaultLangForJoins();
        // $prodSrchObj->joinSellers();
        $prodSrchObj->setGeoAddress();
        $prodSrchObj->joinShops($langId, true, true, 0, true);
        /* $prodSrchObj->joinShopCountry();
        $prodSrchObj->joinShopState(); */
        $prodSrchObj->joinBrands($langId);
        $prodSrchObj->joinProductToCategory($langId);
        $prodSrchObj->validateAndJoinDeliveryLocation();
        $prodSrchObj->joinProductToTax();
        $prodSrchObj->addCondition('selprod_code', 'IS NOT', 'mysql_func_null', 'and', true);

        if (array_key_exists('category', $post)) {
            $joinWithRelationTableInstead = $headerFormParamsAssocArr['joinWithRelationTableInstead'] ?? false;
            $prodSrchObj->addCategoryCondition($post['category'], $joinWithRelationTableInstead);
        }

        $shopId = FatApp::getPostedData('shop_id', FatUtility::VAR_INT, 0);
        if (0 < $shopId) {
            $prodSrchObj->addShopIdCondition($shopId);
        }

        $topProducts = FatApp::getPostedData('top_products', FatUtility::VAR_INT, 0);
        if (0 < $topProducts) {
            // $prodSrchObj->joinProductRating();
            $prodSrchObj->addCondition('product_rating', '>=', 3);
        }

        $brandId = FatApp::getPostedData('brand_id', FatUtility::VAR_INT, 0);
        if (0 < $brandId) {
            $prodSrchObj->addBrandCondition($brandId);
        }

        $featured = FatApp::getPostedData('featured', FatUtility::VAR_INT, 0);
        if (0 < $featured) {
            $prodSrchObj->addCondition('product_featured', '=', applicationConstants::YES);
        }

        $keyword = '';
        if (array_key_exists('keyword', $headerFormParamsAssocArr) && !empty($headerFormParamsAssocArr['keyword'])) {
            $keyword = $headerFormParamsAssocArr['keyword'];
            $prodSrchObj->addKeywordSearch($keyword, false, false);
        }
        return $prodSrchObj;
    }

    public static function getParamsAssocArr()
    {
        $post = FatApp::getPostedData();

        $get = FatApp::getParameters();
        $headerFormParamsAssocArr = Product::convertArrToSrchFiltersAssocArr($get);
        return array_merge($headerFormParamsAssocArr, $post);
    }

    public static function getCacheKey($langId, $post)
    {
        $cacheKey = $langId;

        if (array_key_exists('category', $post)) {
            $cacheKey .= '-' . FatUtility::int($post['category']);
        }

        if (array_key_exists('shop_id', $post)) {
            $cacheKey .= '-' . $post['shop_id'];
        }

        if (array_key_exists('top_products', $post)) {
            $cacheKey .= '-tp';
        }

        if (array_key_exists('brand_id', $post)) {
            $cacheKey .= '-' . $post['brand_id'];
        }

        if (array_key_exists('featured', $post)) {
            $cacheKey .= '-f';
        }

        if (array_key_exists('keyword', $post) && !empty($post['keyword'])) {
            $cacheKey .= '-' . urlencode($post['keyword']);
        }

        return $cacheKey;
    }

    public static function selectedBrands($post)
    {
        if (array_key_exists('brand', $post)) {
            if (true === MOBILE_APP_API_CALL) {
                $post['brand'] = json_decode($post['brand'], true);
            }

            if (is_array($post['brand'])) {
                return $post['brand'];
            }

            return explode(',', $post['brand']);
        }
        return array();
    }

    public static function brands($prodSrchObj, $langId, $post, $doNotLimitRecord = false, $includePriority = false)
    {
        $brandId = 0;
        if (array_key_exists('brand_id', $post)) {
            $brandId = FatUtility::int($post['brand_id']);
        }

        $brandsCheckedArr = array();
        if (true == $includePriority) {
            $brandsCheckedArr = static::selectedBrands($post);
        }

        if (FatApp::getConfig('CONF_DEFAULT_PLUGIN_' . Plugin::TYPE_FULL_TEXT_SEARCH, FatUtility::VAR_INT, 0)) {
            $pageSize = max(count($brandsCheckedArr), 10);

            $srch = FullTextSearch::getListingObj($post, $langId);
            $srch->setFields(array('brand.brand_id', 'brand.brand_name'));
            $srch->setPageNumber(0);
            $srch->setPageSize($pageSize);
            $srch->setSortFields(array('brand.brand_name.keyword' => array('order' => 'asc')));
            $srch->setGroupByField('brand.brand_name');
            return $srch->convertToSystemData($srch->fetch(), 'brand');
        }

        $brandSrch = clone $prodSrchObj;
        if (true == $doNotLimitRecord) {
            $brandSrch->doNotLimitRecords();
        } else {
            $pageSize = max(count($brandsCheckedArr), 10);
            $brandSrch->setPageSize($pageSize);
        }

        $brandSrch->joinBrandsLang($langId);
        $brandSrch->addGroupBy('brand.brand_id');
        $brandSrch->addMultipleFields(array('brand.brand_id', 'COALESCE(tb_l.brand_name,brand.brand_identifier) as brand_name'));
        if ($brandId) {
            $brandSrch->addCondition('brand_id', '=', $brandId);
            $brandsCheckedArr = array($brandId);
        }

        if (!empty($brandsCheckedArr) && true == $includePriority) {
            $brandSrch->addFld('IF(FIND_IN_SET(brand.brand_id, "' . implode(',', $brandsCheckedArr) . '"), 1, 0) as priority');
            $brandSrch->addOrder('priority', 'desc');
        } else {
            $brandSrch->addFld('0 as priority');
        }
        $brandSrch->addOrder('tb_l.brand_name');
        $brandSrch->addCondition('brand_id', '!=', 'null');
        /* if needs to show product counts under brands[ */
        //$brandSrch->addFld('count(selprod_id) as totalProducts');
        /* ] */
        $brandSrch->doNotCalculateRecords();
        $brandRs = $brandSrch->getResultSet();
        $brands = FatApp::getDb()->fetchAll($brandRs);

        if (count($brands) > 0 && !FatApp::getConfig('CONF_PRODUCT_BRAND_MANDATORY', FatUtility::VAR_INT, 1) && in_array(null, array_column($brands, 'brand_id'))) {
            array_push($brands, array(
                'brand_id' => '-1',
                'brand_name' => Labels::getLabel('LBL_Unbranded', CommonHelper::getLangId()),
                'priority' => 9999
            ));
            $brands = array_map('array_filter', $brands);
            $brands = array_values(array_filter($brands));
        }
        return $brands;
    }

    public static function getPrice($post, $langId)
    {
        if (FatApp::getConfig('CONF_DEFAULT_PLUGIN_' . Plugin::TYPE_FULL_TEXT_SEARCH, FatUtility::VAR_INT, 0)) {
            $srch = FullTextSearch::getListingObj($post, $langId);
            $srch->setFields(array('aggregations'));
            $srch->setPageNumber(0);
            $srch->setPageSize(9999);
            $result = $srch->fetch(true);
            $priceArr = [
                'minPrice' => 0,
                'maxPrice' => 0
            ];

            if (is_array($result) && array_key_exists('aggregations', $result)) {
                $priceArr = [
                    'minPrice' => $result['aggregations']['min_price']['value'],
                    'maxPrice' =>  $result['aggregations']['max_price']['value']
                ];
            }
            return $priceArr;
        }

        $langIdForKeywordSeach = 0;
        if (array_key_exists('keyword', $post) && !empty($post['keyword'])) {
            $langIdForKeywordSeach = $langId;
        }

        unset($post['doNotJoinSpecialPrice']);

        if (0 < FatApp::getConfig('CONF_RFQ_MODULE', FatUtility::VAR_INT, 0)) {
            $post['excludeProdForRfqOnly'] = true;
        }

        $priceSrch = static::getSearchObj($langIdForKeywordSeach, $post);
        $priceSrch->doNotLimitRecords();
        $priceSrch->doNotCalculateRecords();

        $useSubQuery = false;
        if (FatApp::getConfig('CONF_ENABLE_GEO_LOCATION', FatUtility::VAR_INT, 0) && !empty(FatApp::getConfig('CONF_GOOGLEMAP_API_KEY', FatUtility::VAR_STRING, ''))) {
            $prodGeoCondition = FatApp::getConfig('CONF_PRODUCT_GEO_LOCATION', FatUtility::VAR_INT, 0);
            switch ($prodGeoCondition) {
                case applicationConstants::BASED_ON_DELIVERY_LOCATION:
                    $shippingServiceActive = Plugin::isActiveByType(Plugin::TYPE_SHIPPING_SERVICES);
                    if (!$shippingServiceActive) {
                        $priceSrch->addMultipleFields(array('theprice'));
                        $rs = FatApp::getDb()->query('select MIN(theprice) as minPrice, MAX(theprice) as maxPrice from ( ' . $priceSrch->getQuery() . ') as pricetbl');
                        $useSubQuery = true;
                    }
                    break;
            }
        }

        if (false == $useSubQuery) {
            $priceSrch->addMultipleFields(array('MIN(theprice) as minPrice', 'MAX(theprice) as maxPrice'));
            $priceSrch->addHaving('minPrice', 'IS NOT', 'mysql_func_null', 'and', true);
            $priceSrch->addHaving('maxPrice', 'IS NOT', 'mysql_func_null', 'and', true);
            $rs = $priceSrch->getResultSet();
        }

        return FatApp::getDb()->fetch($rs);
    }

    public static function getCategories($langId, $categoryId, $prodSrchObj, $cacheKey)
    {
        $cacheKey .= (true ===  MOBILE_APP_API_CALL) ? $cacheKey . '-m' : $cacheKey;
        $catFilter =  CacheHelper::get('catFilter' . $cacheKey, CONF_FILTER_CACHE_TIME, '.txt');
        if (!$catFilter) {
            if (0 < $categoryId) {
                $categoriesArr = ProductCategory::getArray($langId, $categoryId, true, true, false, true);
            } else {
                $catSrch = clone $prodSrchObj;
                $catSrch->doNotLimitRecords();
                /* 
                $catSrch->joinProductToCategoryLang($langId);
                $catSrch->addGroupBy('c.prodcat_id'); */
                $excludeCatHavingNoProducts = true;
                if (!empty($keyword)) {
                    $excludeCatHavingNoProducts = false;
                }
                $categoriesArr = ProductCategory::getTreeArr($langId, $categoryId, false, $catSrch, $excludeCatHavingNoProducts);
            }
            $categoriesArr = (true ===  MOBILE_APP_API_CALL) ? array_values($categoriesArr) : $categoriesArr;
            CacheHelper::create('catFilter' . $cacheKey, serialize($categoriesArr));
            return $categoriesArr;
        }
        return unserialize($catFilter);
    }

    public static function getOptions($langId, $categoryId, $prodSrchObj)
    {
        if (false == ProductCategory::isLastChildCategory($categoryId) && !FatApp::getConfig('CONF_DISPLAY_OPTION_FILTER', FatUtility::VAR_INT, 0)) {
            return [];
        }

        $options =  CacheHelper::get('options' . $categoryId . '-' . $langId, CONF_FILTER_CACHE_TIME, '.txt');
        if (!$options) {
            $options = array();
            $selProdCodeSrch = clone $prodSrchObj;
            $selProdCodeSrch->doNotLimitRecords();
            $selProdCodeSrch->doNotCalculateRecords();
            /*Removed Group by as taking time for huge data. handled in fetch all second param*/
            //$selProdCodeSrch->addGroupBy('selprod_code');
            $selProdCodeSrch->addMultipleFields(array('product_id', 'selprod_code'));
            $selProdCodeArr = FatApp::getDb()->fetchAll($selProdCodeSrch->getResultSet(), 'selprod_code');

            if (!empty($selProdCodeArr)) {
                foreach ($selProdCodeArr as $val) {
                    $optionsVal = SellerProduct::getSellerProductOptionsBySelProdCode($val['selprod_code'], $langId, true);
                    $options = $options + $optionsVal;
                }
            }

            usort(
                $options,
                function ($a, $b) {
                    if ($a['optionvalue_id'] == $b['optionvalue_id']) {
                        return 0;
                    }
                    return ($a['optionvalue_id'] < $b['optionvalue_id']) ? -1 : 1;
                }
            );

            CacheHelper::create('options ' . $categoryId . '-' . $langId, serialize($options));
            return $options;
        }
        return unserialize($options);
    }

    public static function getPageSizeValues()
    {
        return [10, 12, 24, 48];
    }

    public static function getPageSizeArr($langId)
    {
        $pageSize = FatApp::getConfig('CONF_ITEMS_PER_PAGE_CATALOG', FatUtility::VAR_INT, 10);

        $itemsTxt = Labels::getLabel('LBL_Items', $langId);

        $pageSizeArr[$pageSize] = Labels::getLabel('LBL_Default', $langId);
        $pageSizeArr[12] = 12 . ' ' . $itemsTxt;
        $pageSizeArr[24] = 24 . ' ' . $itemsTxt;
        $pageSizeArr[48] = 48 . ' ' . $itemsTxt;
        return $pageSizeArr;
    }

    public static function parseArrayByKeys($arr, $allowedKeys, $inAllowedKeysSequence = false)
    {
        if (true == $inAllowedKeysSequence) {
            $arrSort = [];
            foreach ($allowedKeys as $key) {
                if (!array_key_exists($key, $arr)) {
                    continue;
                }
                $arrSort[$key] = $arr[$key];
            }
            return $arrSort;
        }

        return array_filter(
            $arr,
            function ($key) use ($allowedKeys) {
                return in_array($key, $allowedKeys);
            },
            ARRAY_FILTER_USE_KEY
        );
    }

    public static function encrypt($string)
    {
        $key = hash('sha256', self::ENCRYPTION_KEY);
        $iv = substr(hash('sha256', self::ENCRYPTION_IV), 0, 16);
        $output = openssl_encrypt($string, "AES-256-CBC", $key, 0, $iv);
        return base64_encode($output);
    }

    public static function decrypt($string)
    {
        $key = hash('sha256', self::ENCRYPTION_KEY);
        $iv = substr(hash('sha256', self::ENCRYPTION_IV), 0, 16);
        return openssl_decrypt(base64_decode($string), "AES-256-CBC", $key, 0, $iv);
    }
}
