<?php

class ProductsController extends MyAppController
{
    private int $sellerId = 0;
    private int $cartSellerId = 0;
    public function __construct($action)
    {
        parent::__construct($action);
    }

    public function index()
    {
        //$this->productsData(__FUNCTION__);
        $this->featured();
    }

    public function search()
    {
        $post = (MOBILE_APP_API_CALL) ? FatApp::getPostedData() : [];
        $this->productsData(__FUNCTION__, true, $post);
    }

    public function featured()
    {
        $this->productsData(__FUNCTION__);
    }

    private function productsData($method, $validateBrand = false, $post = [])
    {
        $get = (!empty($post)) ? $post : Product::convertArrToSrchFiltersAssocArr(FatApp::getParameters());
        $viewType = FatApp::getPostedData('viewType', FatUtility::VAR_STRING, '');
        $postBrands = [];
        if (MOBILE_APP_API_CALL) {
            $postBrands = FatApp::getPostedData('brand', FatUtility::VAR_STRING, '[]');
            $postBrands = json_decode($postBrands, true);
        }

        $includeKeywordRelevancy = false;
        $keyword = '';
        if (array_key_exists('keyword', $get)) {
            $includeKeywordRelevancy = true;
            $keyword = trim($get['keyword']);
        }

        if ($validateBrand && array_key_exists('keyword', $get)) {
            $prodSrchObj = new ProductSearch(0);
            $prodSrchObj->addMultipleFields(array('brand_id', 'COALESCE(tb_l.brand_name, brand.brand_identifier) as brand_name'));
            $prodSrchObj->joinSellerProducts(0, '', ['doNotJoinSpecialPrice' => true], true);
            // $prodSrchObj->joinSellers();
            $prodSrchObj->setGeoAddress();
            $prodSrchObj->joinShops(0, true, true, 0, true);
            $prodSrchObj->validateAndJoinDeliveryLocation();
            $prodSrchObj->joinBrands();
            $prodSrchObj->joinBrandsLang($this->siteLangId, $keyword);
            $prodSrchObj->joinProductToCategory();
            $prodSrchObj->joinProductToTax();
            /*  $prodSrchObj->joinSellerSubscription(0, false, true);
            $prodSrchObj->addSubscriptionValidCondition(); */
            $prodSrchObj->doNotCalculateRecords();
            $prodSrchObj->setPageSize(1);
            $prodSrchObj->doNotCalculateRecords();
            $prodSrchObj->addHaving('brand_name', 'like', $keyword);
            $brandRs = $prodSrchObj->getResultSet();
            $brandArr = FatApp::getDb()->fetchAllAssoc($brandRs);
            if (!empty($brandArr)) {
                $brands = array_keys($brandArr);
                $get['brand'] = !empty($postBrands) ? array_merge($brands, $postBrands) : $brands;
            }
        }

        $frm = $this->getProductSearchForm($includeKeywordRelevancy);

        $get['join_price'] = 1;

        $arr = array();

        switch ($method) {
            case 'index':
                $arr = array(
                    'viewType' => $viewType,
                    'pageTitle' => Labels::getLabel('MSG_All_PRODUCTS', $this->siteLangId),
                    'canonicalUrl' => UrlHelper::generateFullUrl('Products', 'index'),
                    'productSearchPageType' => SavedSearchProduct::PAGE_PRODUCT_INDEX,
                    'bannerListigUrl' => UrlHelper::generateFullUrl('Banner', 'allProducts'),
                );
                break;
            case 'search':
                $arr = array(
                    'viewType' => $viewType,
                    'pageTitle' => Labels::getLabel('MSG_SEARCH_RESULTS_FOR', $this->siteLangId),
                    'canonicalUrl' => UrlHelper::generateFullUrl('Products', 'search'),
                    'productSearchPageType' => SavedSearchProduct::PAGE_PRODUCT,
                    'bannerListigUrl' => UrlHelper::generateFullUrl('Banner', 'searchListing'),
                    'keyword' => $keyword,
                );
                break;
            case 'featured':
                $arr = array(
                    'viewType' => $viewType,
                    'pageTitle' => Labels::getLabel('MSG_FEATURED_PRODUCTS', $this->siteLangId),
                    'canonicalUrl' => UrlHelper::generateFullUrl('Products', 'featured'),
                    'productSearchPageType' => SavedSearchProduct::PAGE_FEATURED_PRODUCT,
                    'bannerListigUrl' => UrlHelper::generateFullUrl('Banner', 'searchListing'),
                );
                $get['featured'] = 1;
                break;
        }

        $get['vtype']  = $get['vtype'] ?? 'grid';
        if (!FatApp::getConfig('CONF_ENABLE_GEO_LOCATION', FatUtility::VAR_INT, 0) && !empty(FatApp::getConfig('CONF_GOOGLEMAP_API_KEY', FatUtility::VAR_STRING, '')) && $get['vtype'] == 'map') {
            $get['vtype'] = 'grid';
        }

        $data = $this->getListingData($get);
        $get = $data['postedData'];
        $frm->fill($get);

        if (array_key_exists('keyword', $get) && count($data['products'])) {
            $searchItemObj = new SearchItem();
            $searchData = array('keyword' => $get['keyword']);
            $searchItemObj->addSearchResult($searchData);
        }

        $tRightRibbons = [];
        if (isset($data['products'])) {
            $selProdIdsArr = array_column($data['products'], 'selprod_id');
            $tRightRibbons = Badge::getRibbons($this->siteLangId, Badge::RIBB_POS_TRIGHT, $selProdIdsArr);
        }

        $common = [];
        if (false === MOBILE_APP_API_CALL) {
            $common = array(
                'frmProductSearch' => $frm,
                'recordId' => 0,
                'showBreadcrumb' => false,
                'pageSizeArr' => FilterHelper::getPageSizeArr($this->siteLangId)
            );
        }

        $data = array_merge($data, $common, $arr);

        if (0 < $data['recordCount'] && FatApp::getConfig('CONF_ANALYTICS_ADVANCE_ECOMMERCE', FatUtility::VAR_INT, 0)) {
            $et = new EcommerceTracking($method, UserAuthentication::getLoggedUserId(true));
            $et->addImpression(($method == 'search' ? Labels::getLabel('MSG_SEARCH_RESULTS', $this->siteLangId) : $arr['pageTitle']));
            $productPostion = 1;
            foreach ($data['products'] as $product) {
                $et->addImpressionProduct($product['selprod_id'], $product['selprod_title'], $product['prodcat_name'], $product['brand_name'], $productPostion);
                $productPostion++;
            }
            $et->sendRequest();
        }

        if (FatUtility::isAjaxCall() && $viewType != 'popupProduct' && $viewType != 'popup') {
            $this->set('products', $data['products']);
            $this->set('tRightRibbons', $tRightRibbons);
            /*$this->set('moreSellersProductsArr', $data['moreSellersProductsArr']);*/
            $this->set('page', $data['page']);
            $this->set('pageCount', $data['pageCount']);
            $this->set('postedData', $get);
            $this->set('recordCount', $data['recordCount']);
            $this->set('siteLangId', $this->siteLangId);
            $this->set('pageSize', $data['pageSize']);
            $this->set('pageSizeArr', $data['pageSizeArr']);
            echo $this->_template->render(false, false, 'products/products-list.php', true);
            exit;
        }
        if (FatUtility::isAjaxCall() && $viewType == 'popupProduct') {
            $this->set('products', $data['products']);
            $this->set('postedData', $get);
            $this->set('siteLangId', $this->siteLangId);
            $this->set('pageSizeArr', $data['pageSizeArr']);
            $this->set('tRightRibbons', $tRightRibbons);
            echo $this->_template->render(false, false, 'products/products-map-list-left.php', true);
            exit;
        }
        if (FatUtility::isAjaxCall() && $viewType == 'popup') {
            $this->set('products', $data['products']);
            $this->set('postedData', $get);
            $this->set('siteLangId', $this->siteLangId);
            $this->set('pageSizeArr', $data['pageSizeArr']);
            $this->set('tRightRibbons', $tRightRibbons);
            $this->_template->render(false, false, 'products/listing-map-page.php');
            exit;
        }
        $data['tRightRibbons'] = $tRightRibbons;
        $data['tRightRibbons'] = $tRightRibbons;
        $data['showBreadcrumb'] = true;
        $this->set('data', $data);

        $this->includeProductPageJsCss();
        $this->_template->addJs('js/slick.min.js');
        $this->_template->render(true, true, 'products/index.php');
    }

    private function getFilterSearchObj($langId, $headerFormParamsAssocArr)
    {
        return FilterHelper::getSearchObj($langId, $headerFormParamsAssocArr);
    }

    public function brandFilters()
    {
        $post = FilterHelper::getParamsAssocArr();

        $langIdForKeywordSeach = 0;
        if (array_key_exists('keyword', $post) && !empty($post['keyword'])) {
            $langIdForKeywordSeach = $this->siteLangId;
        }

        $post['doNotJoinSpecialPrice'] = true;
        $prodSrchObj = $this->getFilterSearchObj($langIdForKeywordSeach, $post);
        $prodSrchObj->doNotCalculateRecords();

        $brandsCheckedArr = FilterHelper::selectedBrands($post);
        //$prodSrchObj->addFld('count(selprod_id) as totalProducts');
        $cacheKey = FilterHelper::getCacheKey($this->siteLangId, $post);

        $brandFilter = CacheHelper::get('brandFilter' . $cacheKey, CONF_FILTER_CACHE_TIME, '.txt');
        if (!$brandFilter) {
            $brandsArr = FilterHelper::brands($prodSrchObj, $this->siteLangId, $post, true);
            CacheHelper::create('brandFilter' . $cacheKey, serialize($brandsArr));
        } else {
            $brandsArr = unserialize($brandFilter);
        }

        if (true === MOBILE_APP_API_CALL) {
            $this->set('data', [
                'brandsArr' => $brandsArr,
                'brandsCheckedArr' => $brandsCheckedArr,
            ]);
            $this->_template->render();
        }

        $this->set('brandsArr', $brandsArr);
        $this->set('brandsCheckedArr', $brandsCheckedArr);

        echo $this->_template->render(false, false, 'products/brand-filters.php', true);
        exit;
    }

    public function catFilter()
    {
        $headerFormParamsAssocArr = FilterHelper::getParamsAssocArr();
        $categoryId = 0;
        if (array_key_exists('category', $headerFormParamsAssocArr)) {
            $categoryId = FatUtility::int($headerFormParamsAssocArr['category']);
        }

        $keyword = '';
        $langIdForKeywordSeach = 0;
        if (array_key_exists('keyword', $headerFormParamsAssocArr) && !empty($headerFormParamsAssocArr['keyword'])) {
            $keyword = $headerFormParamsAssocArr['keyword'];
            $langIdForKeywordSeach = $this->siteLangId;
        }

        $cacheKey = FilterHelper::getCacheKey($this->siteLangId, $headerFormParamsAssocArr);
        $categoryFilterData = CacheHelper::get('categoryFilterData' . $cacheKey, CONF_FILTER_CACHE_TIME, '.txt');
        if ($categoryFilterData) {
            echo $categoryFilterData;
            exit;
        }

        /* Categories Data[ ToDO need to update logic fetch from prodsrch obj or catid only*/
        $categoriesArr = array();
        if (empty($keyword)) {
            $catCriteria = $headerFormParamsAssocArr;
            $catCriteria['addFld'] = 'DISTINCT(prodcat_id) as prodcatid';
            $catCriteria['doNotJoinSpecialPrice'] = true;

            $catProdSrchObj = $this->getFilterSearchObj($langIdForKeywordSeach, $catCriteria);
            $catProdSrchObj->doNotCalculateRecords();
            $catProdSrchObj->removeFld('1 as availableInLocation');
            $categoriesArr = FilterHelper::getCategories($this->siteLangId, $categoryId, $catProdSrchObj, $cacheKey);
        }
        /* ] */

        $prodcatArr = array();
        //$productCategories = array();
        if (array_key_exists('prodcat', $headerFormParamsAssocArr)) {
            $prodcatArr = $headerFormParamsAssocArr['prodcat'];
            // $productCatObj = new ProductCategory;
            // $productCategories =  $productCatObj->getCategoriesForSelectBox($this->siteLangId);
        }

        $tpl = new FatTemplate('', '');

        $shopCatFilters = false;
        if (array_key_exists('shop_id', $headerFormParamsAssocArr)) {
            $shop_id = FatUtility::int($headerFormParamsAssocArr['shop_id']);
            $searchFrm = Shop::getFilterSearchForm();
            $searchFrm->fill($headerFormParamsAssocArr);
            $tpl->set('searchFrm', $searchFrm);
            if (0 < $shop_id) {
                $shopCatFilters = true;
            }
        }

        $tpl->set('shopCatFilters', $shopCatFilters);
        $tpl->set('prodcatArr', $prodcatArr);
        $tpl->set('categoriesArr', $categoriesArr);
        $tpl->set('categoryId', $categoryId);
        $tpl->set('siteLangId', $this->siteLangId);

        $html = $tpl->render(false, false, 'products/cat-filter.php', true, true);
        CacheHelper::create('categoryFilterData' . $cacheKey, $html, CacheHelper::TYPE_PRODUCT_CATEGORIES);
        echo $html;
        exit;
    }

    public function filters()
    {
        $db = FatApp::getDb();
        $headerFormParamsAssocArr = FilterHelper::getParamsAssocArr();
        $categoryId = 0;
        if (array_key_exists('category', $headerFormParamsAssocArr)) {
            $categoryId = FatUtility::int($headerFormParamsAssocArr['category']);
        }

        $keyword = '';
        $langIdForKeywordSeach = 0;
        if (array_key_exists('keyword', $headerFormParamsAssocArr) && !empty($headerFormParamsAssocArr['keyword'])) {
            $keyword = $headerFormParamsAssocArr['keyword'];
            $langIdForKeywordSeach = $this->siteLangId;
        }

        $cacheKey = FilterHelper::getCacheKey($this->siteLangId, $headerFormParamsAssocArr);

        $headerFormParamsAssocArr['doNotJoinSpecialPrice'] = true;
        $headerFormParamsAssocArr['joinWithRelationTableInstead'] = true;

        $prodSrchObj = $this->getFilterSearchObj($langIdForKeywordSeach, $headerFormParamsAssocArr);
        $prodSrchObj->doNotCalculateRecords();
        $prodSrchObj->removeFld('1 as availableInLocation');

        /* Brand Filters Data[ */
        $brandsCheckedArr = FilterHelper::selectedBrands($headerFormParamsAssocArr);
        $brandsArr = FilterHelper::brands($prodSrchObj, $this->siteLangId, $headerFormParamsAssocArr, MOBILE_APP_API_CALL, true);
        /* ] */

        /* {Can modify the logic fetch data directly from query . will implement later}
        Option Filters Data[ */
        $options = FilterHelper::getOptions($this->siteLangId, $categoryId, $prodSrchObj);
        /* $optionSrch->joinSellerProductOptionsWithSelProdCode();
        $optionSrch->addGroupBy('optionvalue_id'); */
        /*]*/


        /* Condition filters data[ */
        $conditionsArr = array();
        $conditions = CacheHelper::get('conditions' . $cacheKey, CONF_FILTER_CACHE_TIME, '.txt');
        if (!$conditions) {
            $conditionArr = Product::getConditionArr($this->siteLangId);
            $conditions = array();
            foreach ($conditionArr as $key => $val) {
                $conditionSrch = clone $prodSrchObj;
                $conditionSrch->setPageSize(1);
                $conditionSrch->doNotCalculateRecords();
                $conditionSrch->addMultipleFields(array('selprod_condition'));
                $conditionSrch->addCondition('selprod_condition', '=', $key);
                /* if needs to show product counts under any condition[ */
                //$conditionSrch->addFld('count(selprod_condition) as totalProducts');
                /* ] */
                // echo $conditionSrch->getQuery().'<br>';
                $conditionRs = $conditionSrch->getResultSet();
                $row = $db->fetch($conditionRs);
                if (!empty($row)) {
                    $conditionsArr[] = $row;
                }
            }
            CacheHelper::create('conditions' . $cacheKey, serialize($conditionsArr));
        } else {
            $conditionsArr = unserialize($conditions);
        }
        $conditionsArr = array_filter($conditionsArr);
        /* ] */

        /* Price Filters[ */
        $priceArr = FilterHelper::getPrice($headerFormParamsAssocArr, $this->siteLangId);

        $priceInFilter = false;
        $filterDefaultMinValue = 0;
        $filterDefaultMaxValue = 0;

        if (!empty($priceArr)) {
            $filterDefaultMinValue = $priceArr['minPrice'];
            $filterDefaultMaxValue = $priceArr['maxPrice'];

            if (CommonHelper::getCurrencyId() != FatApp::getConfig('CONF_CURRENCY', FatUtility::VAR_INT, 1) || (array_key_exists('currency_id', $headerFormParamsAssocArr) && $headerFormParamsAssocArr['currency_id'] != CommonHelper::getCurrencyId())) {
                $filterDefaultMinValue = CommonHelper::displayMoneyFormat($priceArr['minPrice'], false, false, false);
                $filterDefaultMaxValue = CommonHelper::displayMoneyFormat($priceArr['maxPrice'], false, false, false);
                $priceArr['minPrice'] = $filterDefaultMinValue;
                $priceArr['maxPrice'] = $filterDefaultMaxValue;
            }

            if (array_key_exists('price-min-range', $headerFormParamsAssocArr) && array_key_exists('price-max-range', $headerFormParamsAssocArr)) {
                $priceArr['minPrice'] = $headerFormParamsAssocArr['price-min-range'];
                $priceArr['maxPrice'] = $headerFormParamsAssocArr['price-max-range'];
                $priceInFilter = true;
            }

            if (array_key_exists('currency_id', $headerFormParamsAssocArr) && $headerFormParamsAssocArr['currency_id'] != CommonHelper::getCurrencyId() && array_key_exists('price-min-range', $headerFormParamsAssocArr) && array_key_exists('price-max-range', $headerFormParamsAssocArr)) {
                $filterDefaultMinValue = CommonHelper::convertExistingToOtherCurrency($headerFormParamsAssocArr['currency_id'], $headerFormParamsAssocArr['price-min-range'], CommonHelper::getCurrencyId(), false);
                $filterDefaultMaxValue = CommonHelper::convertExistingToOtherCurrency($headerFormParamsAssocArr['currency_id'], $headerFormParamsAssocArr['price-max-range'], CommonHelper::getCurrencyId(), false);
                $priceArr['minPrice'] = $filterDefaultMinValue;
                $priceArr['maxPrice'] = $filterDefaultMaxValue;
            }
        }
        /* ] */

        /* Availability Filters[ */
        $availabilities = CacheHelper::get('availabilities' . $cacheKey, CONF_FILTER_CACHE_TIME, '.txt');
        if (!$availabilities) {
            $availabilitySrch = clone $prodSrchObj;
            $availabilitySrch->setPageSize(1);
            //$availabilitySrch->addGroupBy('in_stock');
            $availabilitySrch->addHaving('in_stock', '>', 0);
            $availabilitySrch->addMultipleFields(array('if(selprod_stock > 0,1,0) as in_stock'));
            $availabilitySrch->doNotCalculateRecords();
            $availabilityRs = $availabilitySrch->getResultSet();
            $availabilityArr = $db->fetchAll($availabilityRs, 'in_stock');
            CacheHelper::create('availabilities' . $cacheKey, serialize($availabilityArr));
        } else {
            $availabilityArr = unserialize($availabilities);
        }
        /*] */

        $optionValueCheckedArr = array();
        if (array_key_exists('optionvalue', $headerFormParamsAssocArr)) {
            $optionValueCheckedArr = $headerFormParamsAssocArr['optionvalue'];
        }

        $conditionsCheckedArr = array();
        if (array_key_exists('condition', $headerFormParamsAssocArr)) {
            $conditionsCheckedArr = $headerFormParamsAssocArr['condition'];
        }

        $availability = 0;
        if (array_key_exists('out_of_stock', $headerFormParamsAssocArr)) {
            $availability = $headerFormParamsAssocArr['out_of_stock'];
        }

        $productFiltersArr = array('count_for_view_more' => FatApp::getConfig('CONF_COUNT_FOR_VIEW_MORE', FatUtility::VAR_INT, 5));

        $this->set('productFiltersArr', $productFiltersArr);
        $this->set('headerFormParamsAssocArr', $headerFormParamsAssocArr);
        // $this->set('productCategories',$productCategories);
        $this->set('brandsArr', $brandsArr);
        $this->set('brandsCheckedArr', $brandsCheckedArr);
        $this->set('optionValueCheckedArr', $optionValueCheckedArr);
        $this->set('conditionsArr', $conditionsArr);
        $this->set('conditionsCheckedArr', $conditionsCheckedArr);
        $this->set('options', $options);
        $this->set('priceArr', $priceArr);
        $this->set('priceInFilter', $priceInFilter);
        $this->set('filterDefaultMinValue', $filterDefaultMinValue);
        $this->set('filterDefaultMaxValue', $filterDefaultMaxValue);
        $this->set('availability', $availability);
        $availabilityArr = (true === MOBILE_APP_API_CALL) ? array_values($availabilityArr) : $availabilityArr;
        $this->set('availabilityArr', $availabilityArr);
        $this->set('layoutDirection', CommonHelper::getLayoutDirection());

        if (true === MOBILE_APP_API_CALL) {
            $this->set('position', FatApp::getPostedData('position', FatUtility::VAR_INT, 0));

            /* Categories Data[ ToDO need to update logic fetch from prodsrch obj or catid only*/
            $categoriesArr = array();
            if (empty($keyword)) {
                $catCriteria = $headerFormParamsAssocArr;
                $catCriteria['addFld'] = 'DISTINCT(prodcat_id) as prodcatid';
                $catCriteria['doNotJoinSpecialPrice'] = true;

                $catProdSrchObj = $this->getFilterSearchObj($langIdForKeywordSeach, $catCriteria);
                $catProdSrchObj->doNotCalculateRecords();
                $catProdSrchObj->removeFld('1 as availableInLocation');
                $categoriesArr = FilterHelper::getCategories($this->siteLangId, $categoryId, $catProdSrchObj, $cacheKey);
            }
            /* ] */

            $prodcatArr = array();
            if (array_key_exists('prodcat', $headerFormParamsAssocArr)) {
                $prodcatArr = $headerFormParamsAssocArr['prodcat'];
            }

            $shopCatFilters = false;
            if (array_key_exists('shop_id', $headerFormParamsAssocArr)) {
                $shop_id = FatUtility::int($headerFormParamsAssocArr['shop_id']);
                $searchFrm = Shop::getFilterSearchForm();
                $searchFrm->fill($headerFormParamsAssocArr);
                $this->set('searchFrm', $searchFrm);
                if (0 < $shop_id) {
                    $shopCatFilters = true;
                }
            }

            $this->set('shopCatFilters', $shopCatFilters);
            $this->set('prodcatArr', $prodcatArr);
            $this->set('categoriesArr', $categoriesArr);

            $this->_template->render();
        }

        $templateName = 'filters.php';
        echo $this->_template->render(false, false, 'products/' . $templateName, true);
        exit;
    }

    private function getSelProdReviewObj($forReviewsRating = true)
    {
        $selProdReviewObj = new SelProdReviewSearch();
        $selProdReviewObj->joinProducts($this->siteLangId);
        $selProdReviewObj->joinSellerProducts($this->siteLangId);
        $selProdReviewObj->joinSelProdRating();
        $selProdReviewObj->joinUser();
        $selProdReviewObj->addCondition('ratingtype_type', 'IN', [RatingType::TYPE_PRODUCT, RatingType::TYPE_OTHER]);
        $selProdReviewObj->addCondition('spr.spreview_status', '=', SelProdReview::STATUS_APPROVED);
        if (true == $forReviewsRating) {
            $selProdReviewObj->doNotCalculateRecords();
            $selProdReviewObj->doNotLimitRecords();
            $selProdReviewObj->addGroupBy('spr.spreview_product_id');
            $selProdReviewObj->addMultipleFields(array('spr.spreview_selprod_id', 'spr.spreview_product_id', "ROUND(AVG(sprating_rating),2) as prod_rating", "COUNT(DISTINCT(spreview_id)) AS totReviews"));
        }
        return $selProdReviewObj;
    }

    private function getProductDetail(int $selprod_id)
    {
        $prodSrchObj = new ProductSearch($this->siteLangId);
        $productId = SellerProduct::getAttributesById($selprod_id, 'selprod_product_id');
        if (empty($productId)) {
            if (true === MOBILE_APP_API_CALL) {
                LibHelper::exitWithError(Labels::getLabel('ERR_INVALID_PRODUCT'));
            }
            FatUtility::exitWithErrorCode(404);
        }
        /* fetch requested product[ */
        $prodSrch = clone $prodSrchObj;
        $prodSrch->setLocationBasedInnerJoin(false);
        $prodSrch->setGeoAddress();
        $prodSrch->setDefinedCriteria(0, 0, array('product_id' => $productId), false);
        $prodSrch->joinProductToCategory();
        $prodSrch->joinShopSpecifics();
        $prodSrch->joinProductSpecifics();
        $prodSrch->joinSellerProductSpecifics();
        $prodSrch->joinSellerSubscription();
        $prodSrch->addSubscriptionValidCondition();
        $prodSrch->validateAndJoinDeliveryLocation(false);
        $prodSrch->joinProductToTax();
        $prodSrch->doNotCalculateRecords();
        $prodSrch->addCondition('selprod_id', '=', $selprod_id);
        $prodSrch->addCondition('selprod_deleted', '=', applicationConstants::NO);
        $prodSrch->doNotLimitRecords();

        /* sub query to find out that logged user have marked current product as in wishlist or not[ */
        $loggedUserId = 0;
        if (UserAuthentication::isUserLogged()) {
            $loggedUserId = UserAuthentication::getLoggedUserId();
        }
        if (FatApp::getConfig('CONF_ADD_FAVORITES_TO_WISHLIST', FatUtility::VAR_INT, 1) == applicationConstants::NO) {
            $prodSrch->joinFavouriteProducts($loggedUserId);
            $prodSrch->addFld('IFNULL(ufp_id, 0) as ufp_id');
        } else {
            $prodSrch->joinUserWishListProducts($loggedUserId);
            $prodSrch->addFld('COALESCE(uwlp.uwlp_selprod_id, 0) as is_in_any_wishlist');
        }
        $prodSrch->addCondition('selprod_code', 'IS NOT', 'mysql_func_null', 'and', true);
        $prodSrch->addMultipleFields(
            array(
                'product_id',
                'selprod_sku',
                'product_identifier',
                'COALESCE(product_name,product_identifier) as product_name',
                'product_seller_id',
                'product_model',
                'product_type',
                'prodcat_id',
                'COALESCE(prodcat_name,prodcat_identifier) as prodcat_name',
                'product_upc',
                'product_isbn',
                'product_short_description',
                'product_description',
                'selprod_id',
                'selprod_user_id',
                'selprod_code',
                'selprod_condition',
                'selprod_price',
                'special_price_found',
                'splprice_start_date',
                'splprice_end_date',
                'COALESCE(selprod_title, product_name, product_identifier) as selprod_title',
                'selprod_warranty',
                'selprod_return_policy',
                'selprodComments',
                'theprice',
                'selprod_stock',
                'selprod_threshold_stock_level',
                'IF(selprod_stock > 0, 1, 0) AS in_stock',
                'brand_id',
                'COALESCE(brand_name, brand_identifier) as brand_name',
                'brand_short_description',
                'user_name',
                'shop_id',
                'COALESCE(shop_name, shop_identifier) as shop_name',
                'splprice_display_dis_type',
                'splprice_display_dis_val',
                'splprice_display_list_price',
                'product_attrgrp_id',
                'product_youtube_video',
                'product_cod_enabled',
                'selprod_cod_enabled',
                'selprod_available_from',
                'selprod_min_order_qty',
                'product_updated_on',
                'product_warranty',
                'selprod_return_age',
                'selprod_cancellation_age',
                'shop_return_age',
                'shop_cancellation_age',
                'selprod_fulfillment_type',
                'shop_fulfillment_type',
                'product_fulfillment_type',
                'product_attachements_with_inventory',
                'selprod_product_id',
                'COALESCE(shop_state_l.state_name,state_identifier) as shop_state_name',
                'COALESCE(shop_country_l.country_name,shop_country.country_code) as shop_country_name',
                'selprod_condition',
                'product_warranty_unit',
                'shop_rfq_enabled',
                'selprod_cart_type',
                'selprod_hide_price'
            )
        );
        $productRs = $prodSrch->getResultSet();
        $row = FatApp::getDb()->fetch($productRs);
        $row = (is_array($row) ? $row : []);

        if (!empty($row)) {
            $selProdReviewObj = $this->getSelProdReviewObj();
            $selProdReviewObj->addCondition('spr.spreview_product_id', '=', 'mysql_func_' . $productId, 'AND', true);
            $selProdReviewObj->addCondition('spreview_product_id', '=', $row['product_id']);
            $row += (array)FatApp::getDb()->fetch($selProdReviewObj->getResultSet());
            $row['prod_rating'] = $row['prod_rating'] ?? 0;
            $row['totReviews'] = $row['totReviews'] ?? 0;
        }
        return $row;
    }

    public function view(int $selprod_id)
    {
        $page = FatApp::getPostedData('page', FatUtility::VAR_INT, 1);
        $this->set('page', $page);

        $product = $this->getProductDetail($selprod_id);
        if (!$product) {
            LibHelper::exitWithError(Labels::getLabel('ERR_CURRENTLY_THE_PRODUCT_IS_UNAVAILABLE', $this->siteLangId), false, true);
            FatUtility::exitWithErrorCode(404);
        }

        $loggedUserId = 0;
        if (UserAuthentication::isUserLogged()) {
            $loggedUserId = UserAuthentication::getLoggedUserId();
        }

        if (Product::PRODUCT_TYPE_DIGITAL == $product['product_type']) {
            $selProdOption = explode('_', $product['selprod_code']);
            array_shift($selProdOption);

            $optionComb = '0';
            if (0 < count($selProdOption)) {
                $optionComb = implode('_', $selProdOption);
                $optionComb = ['0', $optionComb];
            }

            $recordId = $selprod_id;
            $productType = Product::CATALOG_TYPE_INVENTORY;
            if (0 == $product['product_attachements_with_inventory']) {
                $recordId = $product['selprod_product_id'];
                $productType = Product::CATALOG_TYPE_PRIMARY;
            }

            $product['preview_links'] = DigitalDownloadSearch::getLinks($recordId, $productType, $optionComb, $this->siteLangId, null, true, true);

            $attrs = [
                'afile_id as prev_afile_id',
                'pddr_id',
                'pddr_options_code',
                'afile_record_id',
                'afile_record_subid',
                'afile_lang_id',
                'afile_name as preview',
                'afile_type',
                'afile_id'
            ];

            $product['preview_attachments'] = DigitalDownloadSearch::getAttachments(
                $recordId,
                $productType,
                $optionComb,
                $this->siteLangId,
                true,
                AttachedFile::FILETYPE_SELLER_PRODUCT_DIGITAL_DOWNLOAD_PREVIEW,
                $attrs
            );
        }

        if (1 < $page || false == MOBILE_APP_API_CALL) {
            /* over all catalog product reviews */
            $selProdReviewObj = $this->getSelProdReviewObj();
            $selProdReviewObj->addCondition('spreview_product_id', '=', $product['product_id']);
            $selProdReviewObj->addMultipleFields(array('sum(if(sprating_rating=1,1,0)) rated_1', 'sum(if(sprating_rating=2,1,0)) rated_2', 'sum(if(sprating_rating=3,1,0)) rated_3', 'sum(if(sprating_rating=4,1,0)) rated_4', 'sum(if(sprating_rating=5,1,0)) rated_5', 'SUM(sprating_rating) as totRatings', 'count(distinct(ratingtype_id)) as totalType'));
            $selProdReviewObj->doNotCalculateRecords();
            $selProdReviewObj->setPageSize(1);
            $reviews = FatApp::getDb()->fetch($selProdReviewObj->getResultSet());
            $this->set('reviews', $reviews);
        }

        $subscription = false;
        $allowed_images = -1;
        if (FatApp::getConfig('CONF_ENABLE_SELLER_SUBSCRIPTION_MODULE', FatUtility::VAR_INT, applicationConstants::NO)) {
            $currentPlanData = OrderSubscription::getUserCurrentActivePlanDetails($this->siteLangId, $product['selprod_user_id'], array('ossubs_images_allowed'));
            $allowed_images = $currentPlanData['ossubs_images_allowed'];
            $subscription = true;
        }

        $sellerProduct = new SellerProduct($selprod_id);
        if (1 == $page) {
            /* Current Product option Values[ */
            $options = SellerProduct::getSellerProductOptions($selprod_id, false);
            $productSelectedOptionValues = array();
            $productGroupImages = array();
            $productImagesArr = array();

            if (count($options) > 0) {
                foreach ($options as $op) {
                    /* Product UPC code [ */
                    $product['product_upc'] = UpcCode::getUpcCode($product['product_id'], $op['selprodoption_optionvalue_id']);
                    /* ] */
                    $images = AttachedFile::getMultipleAttachments(AttachedFile::FILETYPE_PRODUCT_IMAGE, $product['product_id'], $op['selprodoption_optionvalue_id'], $this->siteLangId, true, '', $allowed_images);
                    if ($images) {
                        $productImagesArr += $images;
                    }
                    $productSelectedOptionValues[$op['selprodoption_option_id']] = $op['selprodoption_optionvalue_id'];
                }
            }

            if (count($productImagesArr) > 0) {
                foreach ($productImagesArr as $image) {
                    $afileId = $image['afile_id'];
                    if (!array_key_exists($afileId, $productGroupImages)) {
                        $productGroupImages[$afileId] = array();
                    }
                    $productGroupImages[$afileId] = $image;
                }
            }

            $product['selectedOptionValues'] = $productSelectedOptionValues;
            /* ] */

            if (isset($allowed_images) && $allowed_images > 0) {
                $universal_allowed_images_count = $allowed_images - count($productImagesArr);
            }

            $productUniversalImagesArr = array();
            if (empty($productGroupImages) || !$subscription || isset($universal_allowed_images_count)) {
                $universalImages = AttachedFile::getMultipleAttachments(AttachedFile::FILETYPE_PRODUCT_IMAGE, $product['product_id'], -1, $this->siteLangId, true, '');
                if ($universalImages) {
                    if (isset($universal_allowed_images_count)) {
                        $images = array_slice($universalImages, 0, $universal_allowed_images_count);
                        $productUniversalImagesArr = $images;
                        $universal_allowed_images_count = $universal_allowed_images_count - count($productUniversalImagesArr);
                    } elseif (!$subscription) {
                        $productUniversalImagesArr = $universalImages;
                    }
                }
            }

            if ($productUniversalImagesArr) {
                foreach ($productUniversalImagesArr as $image) {
                    $afileId = $image['afile_id'];
                    if (!array_key_exists($afileId, $productGroupImages)) {
                        $productGroupImages[$afileId] = array();
                    }
                    $productGroupImages[$afileId] = $image;
                }
            }

            //abled and Get Shipping Rates [*/
            $codEnabled = false;
            $isProductShippedBySeller = Product::isProductShippedBySeller($product['product_id'], $product['product_seller_id'], $product['selprod_user_id']);
            if ($isProductShippedBySeller) {
                $walletBalance = User::getUserBalance($product['selprod_user_id']);

                $codEnabled = (0 < $product['product_cod_enabled'] ? $product['selprod_cod_enabled'] : 0);
                $codMinWalletBalance = -1;
                $shop_cod_min_wallet_balance = Shop::getAttributesByUserId($product['selprod_user_id'], 'shop_cod_min_wallet_balance');
                if ($shop_cod_min_wallet_balance > -1) {
                    $codMinWalletBalance = $shop_cod_min_wallet_balance;
                } elseif (FatApp::getConfig('CONF_COD_MIN_WALLET_BALANCE', FatUtility::VAR_FLOAT, -1) > -1) {
                    $codMinWalletBalance = FatApp::getConfig('CONF_COD_MIN_WALLET_BALANCE', FatUtility::VAR_FLOAT, -1);
                }
                if ($codMinWalletBalance > -1 && $codMinWalletBalance > $walletBalance) {
                    $codEnabled = false;
                }
            } else {
                if ($product['product_cod_enabled']) {
                    $codEnabled = true;
                }
            }

            $fulfillmentType = $product['selprod_fulfillment_type'];
            if (true == $isProductShippedBySeller) {
                if ($product['shop_fulfillment_type'] != Shipping::FULFILMENT_ALL) {
                    $fulfillmentType = $product['shop_fulfillment_type'];
                    $product['selprod_fulfillment_type'] = $fulfillmentType;
                }
            } else {
                $fulfillmentType = isset($product['product_fulfillment_type']) ? $product['product_fulfillment_type'] : Shipping::FULFILMENT_SHIP;
                $product['selprod_fulfillment_type'] = $fulfillmentType;
                if (FatApp::getConfig('CONF_FULFILLMENT_TYPE', FatUtility::VAR_INT, -1) != Shipping::FULFILMENT_ALL) {
                    $fulfillmentType = FatApp::getConfig('CONF_FULFILLMENT_TYPE', FatUtility::VAR_INT, -1);
                    $product['selprod_fulfillment_type'] = $fulfillmentType;
                }
            }

            if (in_array($product['product_type'], [Product::PRODUCT_TYPE_DIGITAL, Product::PRODUCT_TYPE_SERVICE])) {
                $fulfillmentType = Shipping::FULFILMENT_ALL;
            }

            $this->set('fulfillmentType', $fulfillmentType);
            $this->set('codEnabled', $codEnabled);
            /*]*/

            $this->sellerId = (false === MOBILE_APP_API_CALL) ? $product['selprod_user_id'] : 0;
            $product['moreSellersArr'] = Product::getMoreSeller($product['selprod_code'], $this->siteLangId, $this->sellerId);

            $cartObj = new Cart();
            $cartObj->getBasketProducts($this->siteLangId);
            $this->cartSellerId = $cartObj->singleCartSellerId;

            /* Form buy product[ */
            $frm = $this->getCartForm($this->siteLangId);
            $frm->fill(array('selprod_id' => $selprod_id));
            $this->set('frmBuyProduct', $frm);
            $this->set('cartSellerId', $this->cartSellerId);
            $this->set('cartHasProducts', $cartObj->hasProducts());
            /* ] */

            $optionSrchObj = new ProductSearch($this->siteLangId);
            $optionSrchObj->setDefinedCriteria(0, 0, ['doNotJoinSellers' => true]);
            $optionSrchObj->doNotCalculateRecords();
            $optionSrchObj->doNotLimitRecords();
            $optionSrchObj->joinTable(SellerProduct::DB_TBL_SELLER_PROD_OPTIONS, 'LEFT OUTER JOIN', 'selprod_id = tspo.selprodoption_selprod_id', 'tspo');
            $optionSrchObj->joinTable(OptionValue::DB_TBL, 'LEFT OUTER JOIN', 'tspo.selprodoption_optionvalue_id = opval.optionvalue_id', 'opval');
            $optionSrchObj->joinTable(Option::DB_TBL, 'LEFT OUTER JOIN', 'opval.optionvalue_option_id = op.option_id', 'op');
            if (FatApp::getConfig('CONF_ENABLE_SELLER_SUBSCRIPTION_MODULE', FatUtility::VAR_INT, 0)) {
                $validDateCondition = " and oss.ossubs_till_date >= '" . date('Y-m-d') . "'";
                $optionSrchObj->joinTable(Orders::DB_TBL, 'INNER JOIN', 'o.order_user_id=selprod_user_id AND o.order_type=' . ORDERS::ORDER_SUBSCRIPTION . ' AND o.order_payment_status =1', 'o');
                $optionSrchObj->joinTable(OrderSubscription::DB_TBL, 'INNER JOIN', 'o.order_id = oss.ossubs_order_id and oss.ossubs_status_id=' . FatApp::getConfig('CONF_DEFAULT_SUBSCRIPTION_PAID_ORDER_STATUS') . $validDateCondition, 'oss');
            }
            $optionSrchObj->addCondition('product_id', '=', $product['product_id']);

            $optionSrch = clone $optionSrchObj;
            $optionSrch->joinTable(Option::DB_TBL . '_lang', 'LEFT OUTER JOIN', 'op.option_id = op_l.optionlang_option_id AND op_l.optionlang_lang_id = ' . $this->siteLangId, 'op_l');
            $optionSrch->addMultipleFields(array('option_id', 'option_is_color', 'COALESCE(option_name,option_identifier) as option_name'));
            $optionSrch->addCondition('option_id', '!=', 'NULL');
            $optionSrch->addCondition('selprodoption_selprod_id', '=', $selprod_id);
            $optionSrch->addGroupBy('option_id');

            $optionRs = $optionSrch->getResultSet();
            if (true === MOBILE_APP_API_CALL) {
                $optionRows = FatApp::getDb()->fetchAll($optionRs);
            } else {
                $optionRows = FatApp::getDb()->fetchAll($optionRs, 'option_id');
            }

            if (count($optionRows) > 0) {
                foreach ($optionRows as &$option) {
                    $optionValueSrch = clone $optionSrchObj;
                    $optionValueSrch->joinTable(OptionValue::DB_TBL . '_lang', 'LEFT OUTER JOIN', 'opval.optionvalue_id = opval_l.optionvaluelang_optionvalue_id AND opval_l.optionvaluelang_lang_id = ' . $this->siteLangId, 'opval_l');
                    $optionValueSrch->addCondition('product_id', '=', $product['product_id']);
                    $optionValueSrch->addCondition('option_id', '=', $option['option_id']);
                    $optionValueSrch->addMultipleFields(array('COALESCE(product_name, product_identifier) as product_name', 'selprod_id', 'selprod_user_id', 'selprod_code', 'option_id', 'COALESCE(optionvalue_name,optionvalue_identifier) as optionvalue_name ', 'theprice', 'optionvalue_id', 'optionvalue_color_code'));
                    $optionValueSrch->addGroupBy('optionvalue_id');
                    $optionValueSrch->addOrder('optionvalue_display_order');
                    $optionValueRs = $optionValueSrch->getResultSet();
                    if (true === MOBILE_APP_API_CALL) {
                        $optionValueRows = FatApp::getDb()->fetchAll($optionValueRs);
                    } else {
                        $optionValueRows = FatApp::getDb()->fetchAll($optionValueRs, 'optionvalue_id');
                    }
                    $option['values'] = $optionValueRows;
                }
            }

            $this->set('optionRows', $optionRows);

            $upsellProducts = $sellerProduct->getUpsellProducts($product['selprod_id'], $this->siteLangId, $loggedUserId);
            $upSellSelProdIdsArr = array_column($upsellProducts, 'selprod_id');
            $upsellProductsRibbons = [
                'tRightRibbons' => Badge::getRibbons($this->siteLangId, Badge::RIBB_POS_TRIGHT, $upSellSelProdIdsArr)
            ];
        }

        if (1 < $page && true === MOBILE_APP_API_CALL) {
            $relatedProducts = $sellerProduct->getRelatedProducts($this->siteLangId, $product['selprod_id'], ['selprod_id']);
            $relatedProductsRs = $this->relatedProductsById(array_keys($relatedProducts));
            $relSelProdIdsArr = array_column($relatedProducts, 'selprod_id');
            $relatedProductsRibbons = [
                'tRightRibbons' => Badge::getRibbons($this->siteLangId, Badge::RIBB_POS_TRIGHT, $relSelProdIdsArr)
            ];
        }

        if (1 == $page || false === MOBILE_APP_API_CALL) {
            $srch = new ShopSearch($this->siteLangId);
            $srch->setDefinedCriteria($this->siteLangId);
            $srch->doNotCalculateRecords();
            $srch->addMultipleFields(
                array('shop_id', 'shop_user_id', 'shop_ltemplate_id', 'shop_created_on', 'COALESCE(shop_name, shop_identifier) as shop_name', 'shop_description', 'shop_payment_policy', 'shop_delivery_policy', 'shop_refund_policy',  'COALESCE(shop_country_l.country_name,shop_country.country_code) as shop_country_name', 'COALESCE(shop_state_l.state_name,state_identifier) as shop_state_name', 'shop_city')
            );
            $srch->addCondition('shop_id', '=', $product['shop_id']);
            $srch->setPageSize(1);
            $shopRs = $srch->getResultSet();
            $shop = FatApp::getDb()->fetch($shopRs);

            $shop_rating = 0;
            if (FatApp::getConfig("CONF_ALLOW_REVIEWS", FatUtility::VAR_INT, 0)) {
                $shop_rating = SelProdRating::getSellerRating($shop['shop_user_id'], true);
            }


            /* Get Product Specifications */
            $this->set('productSpecifications', $this->getProductSpecifications($product['product_id'], $this->siteLangId));
            /* End of Product Specifications */
        }

        if (1 < $page || false == MOBILE_APP_API_CALL) {
            /*   [ Promotional Banner   */
            $banners = BannerLocation::getPromotionalBanners(0, $this->siteLangId);
            /* End of Prmotional Banner  ]*/
            $this->set('banners', $banners);
        }

        $canSubmitFeedback = true;
        if ($loggedUserId) {
            $orderProduct = SelProdReview::getProductOrderId($product['product_id'], $loggedUserId);
            if (empty($orderProduct) || (isset($orderProduct['op_order_id']) && !Orders::canSubmitFeedback($loggedUserId, $orderProduct['op_order_id'], $selprod_id))) {
                $canSubmitFeedback = false;
            }
        }

        $displayProductNotAvailableLable = false;
        if (FatApp::getConfig('CONF_ENABLE_GEO_LOCATION', FatUtility::VAR_INT, 0) && !empty(FatApp::getConfig('CONF_GOOGLEMAP_API_KEY', FatUtility::VAR_STRING, ''))) {
            $displayProductNotAvailableLable = true;
        }

        $tRightRibbons = Badge::getRibbons($this->siteLangId, Badge::RIBB_POS_TRIGHT, [$selprod_id]);
        $selProdRibbons = [];
        if (array_key_exists($selprod_id, $tRightRibbons)) {
            $selProdRibbons[] = $tRightRibbons[$selprod_id];
        }
        $this->set('selProdRibbons', $selProdRibbons);

        $ratingAspects = SelProdRating::getProdRatingAspects($product['product_id'], $this->siteLangId);

        $this->set('ratingAspects', $ratingAspects);
        $this->set('productView', true);
        $this->set('displayProductNotAvailableLable', $displayProductNotAvailableLable);
        $this->set('sellers', [$product]);

        $this->set('currSelprodId', $selprod_id);
        $this->set('canSubmitFeedback', $canSubmitFeedback);

        if (1 == $page || false === MOBILE_APP_API_CALL) {
            $this->set('upsellProducts', !empty($upsellProducts) ? $upsellProducts : array());
            $this->set('upsellProductsRibbons', $upsellProductsRibbons);

            $this->set('product', $product);
            $this->set('shop_rating', $shop_rating);
            $this->set('shop', $shop);
            $this->set('shopTotalReviews', SelProdReview::getSellerTotalReviews($shop['shop_user_id'], true));
            $this->set('productImagesArr', $productGroupImages);


            $frmReviewSearch = $this->getReviewSearchForm();
            $frmReviewSearch->fill(array('selprod_id' => $selprod_id));
            $this->set('frmReviewSearch', $frmReviewSearch);

            $currentStock = $product['selprod_stock'] - Product::tempHoldStockCount($selprod_id);
            $this->set('currentStock', $currentStock);
            $this->set('isOutOfMinOrderQty', ((int)($product['selprod_min_order_qty'] > $currentStock)));

            /* Get Product Volume Discount (if any)[ */
            $this->set('volumeDiscountRows', $sellerProduct->getVolumeDiscounts());
            /* ] */

            if (!empty($product)) {
                $afile_id = !empty($productGroupImages) ? array_keys($productGroupImages)[0] : 0;
                $this->set('socialShareContent', $this->getOgTags($product, $afile_id));
            }
        }

        if (1 < $page && true === MOBILE_APP_API_CALL) {
            $this->set('relatedProductsRs', !empty($relatedProductsRs) ? $relatedProductsRs : array());
            $this->set('relatedProductsRibbons', $relatedProductsRibbons);
            /* Recommended Products */
            $loggedUserId = UserAuthentication::getLoggedUserId(true);
            $recommendedProducts = (array) $this->getRecommendedProducts($selprod_id, $this->siteLangId, $loggedUserId);
            $recommendedProducts = (0 < count(array_filter($recommendedProducts)) ? array_filter($recommendedProducts) : []);
            $extraRecCount = (5 - count($recommendedProducts));
            if (0 < $extraRecCount) {
                $srch = Product::getListingObj(['category' => $product['prodcat_id']], $this->siteLangId);
                $srch->setPageSize($extraRecCount);
                $srch->doNotCalculateRecords();
                $products = FatApp::getDb()->fetchAll($srch->getResultSet());
                $recommendedProducts = array_merge($recommendedProducts, $products);
            }

            $recSelProdIdsArr = array_column($recommendedProducts, 'selprod_id');
            $recommendedProductsRibbons = [
                'tRightRibbons' => Badge::getRibbons($this->siteLangId, Badge::RIBB_POS_TRIGHT, $recSelProdIdsArr)
            ];
            $this->set('recommendedProducts', $recommendedProducts);
            $this->set('recommendedProductsRibbons', $recommendedProductsRibbons);
            /* ----------------- */

            /* Recently Viewed Products */
            $recentViewedProducts = $recentlyViewedRibbons = [];
            $recentlyViewed = FatApp::getPostedData('recentlyViewed');
            if (is_array($recentlyViewed) && 0 < count($recentlyViewed)) {
                if ($selprod_id && in_array($selprod_id, $recentlyViewed)) {
                    $pos = array_search($selprod_id, $recentlyViewed);
                    unset($recentlyViewed[$pos]);
                }

                $recentlyViewed = array_map('intval', $recentlyViewed);
                $recentlyViewed = array_reverse($recentlyViewed);

                $recentViewedProducts = $this->getRecentlyViewedProductsDetail($recentlyViewed);

                if (!empty($recentViewedProducts)) {
                    $recentSelProdIdsArr = array_column($recentViewedProducts, 'selprod_id');
                    $recentlyViewedRibbons = [
                        'tRightRibbons' => Badge::getRibbons($this->siteLangId, Badge::RIBB_POS_TRIGHT, $recentSelProdIdsArr)
                    ];
                }
            }

            $this->set('recentlyViewedRibbons', $recentlyViewedRibbons);
            $this->set('recentlyViewed', $recentViewedProducts);
            /* ----------------- */

            /* Reviews with Images*/
            $selProdReviewObj = $this->getSelProdReviewObj(false);
            $selProdReviewObj->joinSelProdReviewHelpful();
            $selProdReviewObj->addGroupBy('spr.spreview_id');
            $selProdReviewObj->addCondition('spr.spreview_product_id', '=', $product['product_id']);
            $selProdReviewObj->setPageNumber(1);
            $selProdReviewObj->setPageSize(4);
            $selProdReviewObj->addOrder('spr.spreview_posted_on', 'desc');
            $selProdReviewObj->joinTable(AttachedFile::DB_TBL, 'INNER JOIN', 'af.afile_type = ' . AttachedFile::FILETYPE_ORDER_FEEDBACK . ' AND af.afile_record_id = spr.spreview_id', 'af');
            $selProdReviewObj->addMultipleFields(array('spreview_id', 'user_updated_on', 'spreview_postedby_user_id'));
            $reviewsList = (array) FatApp::getDb()->fetchAll($selProdReviewObj->getResultSet(), 'spreview_id');
            $this->set('imageReviewsPageCount', $selProdReviewObj->pages());
            $this->set('imageReviewsRecordCount', $selProdReviewObj->recordCount());
            $this->set('imageReviewsList', array_values($reviewsList));
            /* ----------------- */

            /* Reviews without Images*/
            $selProdReviewObj = new SelProdReviewSearch();
            $selProdReviewObj->joinProducts($this->siteLangId);
            $selProdReviewObj->joinSellerProducts($this->siteLangId);
            $selProdReviewObj->joinUser();
            $selProdReviewObj->joinSelProdReviewHelpful();
            $selProdReviewObj->addCondition('spr.spreview_status', '=', SelProdReview::STATUS_APPROVED);
            $selProdReviewObj->addGroupBy('spr.spreview_id');
            $selProdReviewObj->addCondition('spr.spreview_product_id', '=', $product['product_id']);
            $selProdReviewObj->addCondition('af.afile_id', 'IS', 'mysql_func_null', 'AND', true);
            $selProdReviewObj->setPageNumber(1);
            $selProdReviewObj->setPageSize(3);
            $selProdReviewObj->addOrder('helpful', 'desc');
            $selProdReviewObj->joinTable(AttachedFile::DB_TBL, 'LEFT JOIN', 'af.afile_type = ' . AttachedFile::FILETYPE_ORDER_FEEDBACK . ' AND af.afile_record_id = spr.spreview_id', 'af');
            $selProdReviewObj->addMultipleFields(array('spreview_id', 'spreview_selprod_id', 'spreview_title', 'spreview_description', 'spreview_posted_on', 'spreview_postedby_user_id', 'user_name', 'group_concat(case when sprh_helpful = 1 then concat(sprh_user_id,"~",1) else concat(sprh_user_id,"~",0) end ) usersMarked', 'sum(if(sprh_helpful = 1 , 1 ,0)) as helpful', 'sum(if(sprh_helpful = 0 , 1 ,0)) as notHelpful', 'count(sprh_spreview_id) as countUsersMarked', 'user_updated_on'));

            $reviewsList = (array) FatApp::getDb()->fetchAll($selProdReviewObj->getResultSet(), 'spreview_id');
            $this->set('reviewsPageCount', $selProdReviewObj->pages());
            $this->set('reviewsRecordCount', $selProdReviewObj->recordCount());
            $this->set('reviewsList', array_values($reviewsList));

            $recordRatings = [];
            if (0 < count($reviewsList)) {
                $ratings = SelProdRating::getSearchObj();
                $ratings->joinTable(
                    RatingType::DB_TBL,
                    'INNER JOIN',
                    'rt.ratingtype_id = sprating_ratingtype_id AND rt.ratingtype_active = ' . applicationConstants::ACTIVE,
                    'rt'
                );
                $ratings->joinTable(
                    RatingType::DB_TBL_LANG,
                    'LEFT OUTER JOIN',
                    'rt_l.ratingtypelang_ratingtype_id = rt.ratingtype_id AND rt_l.ratingtypelang_lang_id = ' . $this->siteLangId,
                    'rt_l'
                );

                $ratings->addMultipleFields(['sprating_spreview_id', 'ratingtype_id', 'COALESCE(ratingtype_name, ratingtype_identifier) as ratingtype_name', 'sprating_rating']);

                $ratings->addCondition('sprating_spreview_id', 'IN', array_keys($reviewsList));
                $ratings->addCondition('ratingtype_type', 'IN', [RatingType::TYPE_PRODUCT, RatingType::TYPE_OTHER]);
                $ratings->doNotLimitRecords();
                $ratings->doNotCalculateRecords();
                $recordRatings = (array) FatApp::getDb()->fetchAll($ratings->getResultSet());
            }
            $this->set('recordRatings', $recordRatings);
            /* ----------------- */
        }

        if (false === MOBILE_APP_API_CALL) {
            if (User::checkPersonalizedCookiesEnabled() == true) {
                $this->setRecentlyViewedItem($selprod_id);
            }

            $this->_template->addJs(array('js/slick.min.js', 'js/modaal.js', 'js/product-detail.js', 'js/magnific-popup.js', 'js/jw-player.js', 'js/slick-carousels.js'));
        }

        if (FatApp::getConfig('CONF_ANALYTICS_ADVANCE_ECOMMERCE', FatUtility::VAR_INT, 0)) {
            /* [product click event from search page */
            $refererParseUrl = parse_url(CommonHelper::redirectUserReferer(true));
            if (isset($refererParseUrl['path'])) {
                $productAction = '';
                switch ($refererParseUrl['path']) {
                    case '/products/index':
                        $productAction = Labels::getLabel('MSG_All_PRODUCTS', $this->siteLangId);
                        break;
                    case '/products/search':
                        $productAction = Labels::getLabel('MSG_SEARCH_RESULTS', $this->siteLangId);
                        break;
                    case '/products/featured':
                        $productAction = Labels::getLabel('MSG_FEATURED_PRODUCTS', $this->siteLangId);
                        break;
                }
            }
            if (!empty($productAction)) {
                $et = new EcommerceTracking(null, UserAuthentication::getLoggedUserId(true));
                $et->addProductAction(EcommerceTracking::PROD_ACTION_TYPE_CLICK);
                $et->addProductActionList($productAction);
                $et->addProduct($product['selprod_id'], $product['selprod_title'], $product['prodcat_name'], $product['brand_name'], 1, $product['selprod_price']);
                $et->addEvent('click', 'UX');
                $et->sendRequest();
            }
            /* product click event from search page] */

            /* [product view */
            $et = new EcommerceTracking(Labels::getLabel('MSG_Product_Detail', $this->siteLangId), UserAuthentication::getLoggedUserId(true));
            $et->addProductAction(EcommerceTracking::PROD_ACTION_TYPE_DETAIL);
            $et->addProduct($product['selprod_id'], $product['selprod_title'], $product['prodcat_name'], $product['brand_name'], 1, $product['selprod_price']);

            if (isset($recommendedProducts) && 0 < count($recommendedProducts)) {
                $et->addImpression(Labels::getLabel('MSG_Recommended_Products', $this->siteLangId));
                $productPostion = 1;
                foreach ($recommendedProducts as $product) {
                    $et->addImpressionProduct($product['selprod_id'], $product['selprod_title'], $product['prodcat_name'], $product['brand_name'], $productPostion);
                    $productPostion++;
                }
            }
            $et->sendRequest();
        }


        if (false === MOBILE_APP_API_CALL) {
            // $this->includeFeatherLight();
            $this->_template->addJs(['js/popper.min.js', 'js/slick.min.js', 'js/jquery.fancybox.min.js']);
        }
        $this->_template->render();
    }

    public function moreSellersRows(string $selprodCode, int $sellerId)
    {
        $moreSellers = Product::getMoreSeller($selprodCode, $this->siteLangId, $sellerId);
        foreach ($moreSellers as &$sellerDetail) {
            $sellerDetail['shopTotalReviews'] = SelProdReview::getSellerTotalReviews($sellerDetail['shop_user_id'], true);
        }

        $cartObj = new Cart();
        $cartObj->getBasketProducts($this->siteLangId);
        $this->cartSellerId = $cartObj->singleCartSellerId;
        $this->set('cartSellerId', $this->cartSellerId);
        $this->set('sellers', $moreSellers);
        $this->set('cartHasProducts', $cartObj->hasProducts());
        $this->_template->render(false, false);
    }

    private function getProductSpecifications($product_id, $langId)
    {
        $product_id = FatUtility::int($product_id);
        $langId = FatUtility::int($langId);
        if (1 > $product_id) {
            return array();
        }
        $specSrchObj = new ProductSearch(0);
        $specSrchObj->setDefinedCriteria(0, 0, ['doNotJoinSellers' => true, 'doNotJoinSpecialPrice' => true]);
        $specSrchObj->doNotCalculateRecords();
        $specSrchObj->doNotLimitRecords();
        $specSrchObj->joinTable(Product::DB_PRODUCT_SPECIFICATION, 'LEFT OUTER JOIN', 'product_id = tcps.prodspec_product_id', 'tcps');
        $specSrchObj->joinTable(Product::DB_PRODUCT_LANG_SPECIFICATION, 'INNER JOIN', 'tcps.prodspec_id = tcpsl.prodspeclang_prodspec_id and   prodspeclang_lang_id  = ' . $langId, 'tcpsl');
        $specSrchObj->addMultipleFields(array('prodspec_id', 'prodspec_name', 'prodspec_value', 'prodspec_group'));
        $specSrchObj->addGroupBy('prodspec_id');
        $specSrchObj->addOrder('prodspec_group');
        $specSrchObj->addOrder('prodspec_name');
        $specSrchObj->addCondition('prodspec_product_id', '=', $product_id);
        $specSrchObjRs = $specSrchObj->getResultSet();
        return FatApp::getDb()->fetchAll($specSrchObjRs);
    }

    private function setRecentlyViewedItem($selprod_id)
    {
        $selprod_id = FatUtility::int($selprod_id);
        if (1 > $selprod_id) {
            return;
        }

        $recentProductsArr = array();
        if (!isset($_COOKIE['recentViewedProducts'])) {
            setcookie('recentViewedProducts', $selprod_id . '_', time() + 60 * 60 * 72, CONF_WEBROOT_URL);
        } else {
            $recentProducts = $_COOKIE['recentViewedProducts'];
            $recentProductsArr = explode('_', $recentProducts);
        }

        $products = array();

        if (is_array($recentProductsArr) && !in_array($selprod_id, $recentProductsArr)) {
            if (count($recentProductsArr) >= 10) {
                $recentProductsArr = array_reverse($recentProductsArr);
                array_pop($recentProductsArr);
                $recentProductsArr = array_reverse($recentProductsArr);
            }

            foreach ($recentProductsArr as $val) {
                if ($val == '') {
                    continue;
                }
                array_push($products, $val);
            }
            array_push($products, $selprod_id);
            setcookie('recentViewedProducts', implode('_', $products), time() + 60 * 60 * 72, CONF_WEBROOT_URL);
        }
    }

    private function getRecommendedProducts($selprod_id, $langId, $userId = 0)
    {
        $selprod_id = FatUtility::int($selprod_id);
        if (1 > $selprod_id) {
            return;
        }

        if (User::checkPersonalizedCookiesEnabled() == false) {
            return false;
        }

        $productId = SellerProduct::getAttributesById($selprod_id, 'selprod_product_id', false);

        $srch = new ProductSearch($langId);
        $join_price = 1;
        $srch->setGeoAddress();
        $srch->setDefinedCriteria($join_price, 0, ['doNotJoinSellers' => true]);
        $srch->joinProductToCategory();
        $srch->joinSellerSubscription();
        $srch->addSubscriptionValidCondition();
        $srch->validateAndJoinDeliveryLocation();
        $srch->addCondition('selprod_deleted', '=', applicationConstants::NO);
        $srch->addMultipleFields(
            array(
                'product_id',
                'prodcat_id',
                'substring_index(group_concat(COALESCE(prodcat_name, prodcat_identifier) ORDER BY COALESCE(prodcat_name, prodcat_identifier) ASC SEPARATOR "," ) , ",", 1) as prodcat_name',
                'COALESCE(product_name, product_identifier) as product_name',
                'product_model',
                'product_short_description',
                'product_updated_on',
                'selprod_id',
                'selprod_user_id',
                'selprod_code',
                'selprod_stock',
                'selprod_condition',
                'selprod_price',
                'COALESCE(selprod_title, product_name, product_identifier) as selprod_title',
                'special_price_found',
                'splprice_display_list_price',
                'splprice_display_dis_val',
                'splprice_display_dis_type',
                'theprice',
                'brand_id',
                'COALESCE(brand_name, brand_identifier) as brand_name',
                'brand_short_description',
                'IF(selprod_stock > 0, 1, 0) AS in_stock',
                'selprod_sold_count',
                'selprod_return_policy',
                'shop_id',
                'selprod_cart_type',
                'selprod_hide_price',
                'shop_rfq_enabled',
                'product_type'
            )
        );

        $dateToEquate = date('Y-m-d');

        $subSrch1 = new SearchBase('tbl_product_product_recommendation', 'ppr');
        $subSrch1->addMultipleFields(array('ppr_recommended_product_id as rec_product_id', 'ppr_weightage as weightage'));
        $subSrch1->addCondition('ppr_viewing_product_id', '=', $productId);
        $subSrch1->addOrder('weightage', 'desc');
        $subSrch1->doNotCalculateRecords();
        $subSrch1->setPageSize(5);

        $subSrch2 = new SearchBase(Product::DB_PRODUCT_TO_TAG, 'ptt');
        $subSrch2->joinTable('tbl_tag_product_recommendation', 'INNER JOIN', 'tpr.tpr_tag_id = ptt.ptt_tag_id', 'tpr');
        $subSrch2->addMultipleFields(array('tpr_product_id  as rec_product_id', 'if(tpr_custom_weightage_valid_till <= ' . $dateToEquate . ', tpr_custom_weightage+tpr_weightage , tpr_weightage) as weightage'));
        $subSrch2->addOrder('weightage', 'desc');
        $subSrch2->doNotCalculateRecords();
        $subSrch2->setPageSize(5);

        $recommendedProductsQuery = '(' . $subSrch1->getQuery() . ') union (' . $subSrch2->getQuery() . ')';
        if (0 < $userId) {
            $subSrch3 = new SearchBase('tbl_user_product_recommendation', 'upr');
            $subSrch3->addMultipleFields(array('upr_product_id as rec_product_id', 'upr_weightage as weightage'));
            $subSrch3->addOrder('weightage', 'desc');
            $subSrch3->addCondition('upr_user_id', '=', $userId);
            $subSrch3->doNotCalculateRecords();
            $subSrch3->setPageSize(5);
            $recommendedProductsQuery .= ' union (' . $subSrch3->getQuery() . ')';
        }

        $rs = FatApp::getDb()->query('select rec_product_id , weightage from (' . $recommendedProductsQuery . ') as temp order by weightage desc');
        $recommendedProds = FatApp::getDb()->fetchAllAssoc($rs);
        if (empty($recommendedProds)) {
            return array();
        }

        $srch->addGroupBy('product_id');

        $srch->addCondition('selprod_id', '!=', $selprod_id);
        $srch->addCondition('product_id', 'in', array_keys($recommendedProds));
        $srch->setPageSize(5);
        $srch->doNotCalculateRecords();

        return FatApp::getDb()->fetchAll($srch->getResultSet());
    }

    private function getOgTags($product = array(), $afile_id = 0)
    {
        if (empty($product)) {
            return array();
        }
        $afile_id = FatUtility::int($afile_id);
        $title = $product['product_name'];

        if ($product['selprod_title']) {
            $title = $product['selprod_title'];
        }

        $product_description = trim(CommonHelper::subStringByWords(strip_tags(CommonHelper::renderHtml($product["product_description"], true)), 500));
        $product_description .= ' - ' . Labels::getLabel('MSG_SEE_MORE_AT', $this->siteLangId) . ": " . UrlHelper::getCurrUrl();

        $productImageUrl = '';
        /* $productImageUrl = UrlHelper::generateFullUrl('Image','product', array($product['product_id'],'', $product['selprod_id'],0,$this->siteLangId )); */
        if (0 < $afile_id) {
            $productImageUrl = UrlHelper::generateFullUrl('Image', 'product', array($product['product_id'], ImageDimension::VIEW_FB_RECOMMEND, 0, $afile_id));
        }
        $socialShareContent = array(
            'type' => 'Product',
            'title' => $title,
            'description' => $product_description,
            'image' => $productImageUrl,
        );
        return $socialShareContent;
    }

    private function getRecentlyViewedProductsDetail($cookiesProductsArr = array())
    {
        if (false === MOBILE_APP_API_CALL && User::checkPersonalizedCookiesEnabled() == false) {
            return false;
        }

        if (1 > count($cookiesProductsArr)) {
            return $cookiesProductsArr;
        }

        $prodSrch = new ProductSearch($this->siteLangId);
        $prodSrch->setGeoAddress();
        $prodSrch->setDefinedCriteria(0, 0, ['doNotJoinSellers' => true]);
        $prodSrch->joinSellerSubscription();
        $prodSrch->addSubscriptionValidCondition();
        $prodSrch->validateAndJoinDeliveryLocation();
        $prodSrch->joinProductToCategory();
        $prodSrch->doNotCalculateRecords();
        $prodSrch->doNotLimitRecords();
        $prodSrch->addCondition('selprod_id', 'IN', $cookiesProductsArr);
        $prodSrch->addMultipleFields(
            array(
                'product_id',
                'COALESCE(product_name, product_identifier) as product_name',
                'prodcat_id',
                'COALESCE(prodcat_name, prodcat_identifier) as prodcat_name',
                'product_updated_on',
                'selprod_id',
                'selprod_condition',
                'IF(selprod_stock > 0, 1, 0) AS in_stock',
                'theprice',
                'special_price_found',
                'splprice_display_list_price',
                'splprice_display_dis_val',
                'splprice_display_dis_type',
                'selprod_sold_count',
                'COALESCE(selprod_title, product_name, product_identifier) as selprod_title',
                'selprod_price',
                'shop_id',
                'selprod_cart_type',
                'selprod_hide_price',
                'shop_rfq_enabled',
                'product_type'
            )
        );
        $productRs = $prodSrch->getResultSet();
        $recentViewedProducts = FatApp::getDb()->fetchAll($productRs, 'selprod_id');
        uksort(
            $recentViewedProducts,
            function ($key1, $key2) use ($cookiesProductsArr) {
                return (array_search($key1, $cookiesProductsArr) <=> array_search($key2, $cookiesProductsArr));
            }
        );
        return $recentViewedProducts;
    }

    public function relatedProductsById($ids = array())
    {
        if (isset($ids) && is_array($ids) && count($ids)) {
            $prodSrch = new ProductSearch($this->siteLangId);
            $prodSrch->setDefinedCriteria(0, 0, ['selProdIds' => $ids, 'doNotJoinSellers' => true]);
            $prodSrch->joinProductToCategory();
            $prodSrch->doNotCalculateRecords();

            if (true === MOBILE_APP_API_CALL) {
                $prodSrch->joinTable(SelProdReview::DB_TBL, 'LEFT OUTER JOIN', 'spr.spreview_selprod_id = selprod_id AND spr.spreview_product_id = product_id', 'spr');
                $prodSrch->joinTable(SelProdRating::DB_TBL, 'LEFT OUTER JOIN', 'sprating.sprating_spreview_id = spr.spreview_id', 'sprating');
                $prodSrch->addFld(array('COALESCE(ROUND(AVG(sprating_rating),2),0) as prod_rating'));
                $prodSrch->addGroupBy('selprod_id');
            }

            $prodSrch->doNotLimitRecords();
            $prodSrch->addCondition('selprod_id', 'IN', $ids);
            $prodSrch->addMultipleFields(
                array(
                    'product_id',
                    'COALESCE(product_name, product_identifier) as product_name',
                    'prodcat_id',
                    'COALESCE(prodcat_name, prodcat_identifier) as prodcat_name',
                    'product_updated_on',
                    'COALESCE(selprod_title,product_name, product_identifier) as selprod_title',
                    'selprod_id',
                    'selprod_condition',
                    'IF(selprod_stock > 0, 1, 0) AS in_stock',
                    'theprice',
                    'special_price_found',
                    'splprice_display_list_price',
                    'splprice_display_dis_val',
                    'splprice_display_dis_type',
                    'selprod_sold_count',
                    'selprod_price',
                    'selprod_stock',
                    'selprod_min_order_qty',
                    'selprod_cart_type',
                    'selprod_hide_price',
                    'shop_rfq_enabled',
                    'product_type'
                )
            );

            $productRs = $prodSrch->getResultSet();
            $products = FatApp::getDb()->fetchAll($productRs, 'selprod_id');

            uksort(
                $products,
                function ($key1, $key2) use ($ids) {
                    return (array_search($key1, $ids) <=> array_search($key2, $ids));
                }
            );
            return $products;
        }
    }

    public function clearSearchKeywords()
    {
        $keyword = FatApp::getPostedData("keyword");
        if (!empty($keyword)) {
            $recentSearchArr = [];
            if (isset($_COOKIE['recentSearchKeywords_' . $this->siteLangId])) {
                $recentSearchArr = unserialize($_COOKIE['recentSearchKeywords_' . $this->siteLangId]);
            }

            if (($key = array_search($keyword, $recentSearchArr)) !== false) {
                unset($recentSearchArr[$key]);
                setcookie('recentSearchKeywords_' . $this->siteLangId, serialize($recentSearchArr), time() + 60 * 60 * 72, CONF_WEBROOT_URL);
            }
        } else {
            setcookie('recentSearchKeywords_' . $this->siteLangId, '', time() + 60 * 60 * 72, CONF_WEBROOT_URL);
        }
    }

    public function searchProducttagsAutocomplete()
    {
        $keyword = FatApp::getPostedData("keyword");

        $recentSearchArr = [];
        if (isset($_COOKIE['recentSearchKeywords_' . $this->siteLangId])) {
            $recentSearchArr = unserialize($_COOKIE['recentSearchKeywords_' . $this->siteLangId]);
        }

        if (empty($keyword) || mb_strlen($keyword) < 3) {
            if (true === MOBILE_APP_API_CALL) {
                FatUtility::dieJsonError(Labels::getLabel('ERR_PLEASE_ENTER_ATLEAST_3_CHARACTERS', $this->siteLangId));
            }

            $this->set('keyword', $keyword);
            $this->set('recentSearchArr', $recentSearchArr);
            $html = '';
            if (!empty($recentSearchArr)) {
                $html = $this->_template->render(false, false, 'products/search-producttags-autocomplete.php', true, false);
            }
            $this->set('html', $html);
            $this->_template->render(false, false, 'json-success.php', false, false);
        }
        $cacheKey = $this->siteLangId . '-' .  urlencode($keyword);


        $autoSuggetionsCache = CacheHelper::get('autoSuggetionsCache' . $cacheKey, CONF_FILTER_CACHE_TIME, '.txt');
        if (!$autoSuggetionsCache) {
            $criteria = [
                'keyword' => $keyword,
                'doNotJoinSpecialPrice' => true
            ];
            $prodSrchObj = new ProductSearch($this->siteLangId);
            $prodSrchObj->joinSellerProducts(0, '', $criteria, true);
            $prodSrchObj->unsetDefaultLangForJoins();
            // $prodSrchObj->joinSellers();
            $prodSrchObj->setGeoAddress();
            $prodSrchObj->joinShops(0, true, true, 0, true);
            $prodSrchObj->joinProductToCategory($this->siteLangId);
            $prodSrchObj->joinProductToTax();
            /*$prodSrchObj->validateAndJoinDeliveryLocation(false, false);*/
            $prodSrchObj->joinBrands($this->siteLangId);

            $prodSrchObj->joinSellerSubscription(0, false, true);
            $prodSrchObj->addSubscriptionValidCondition();
            $prodSrchObj->doNotCalculateRecords();

            $brandSrch = clone $prodSrchObj;
            $brandSrch->validateAndJoinDeliveryLocation(false, false);
            $brandSrch->joinProductToCategory($this->siteLangId);
            $brandSrch->addMultipleFields(array('brand_id', 'COALESCE(tb_l.brand_name, brand.brand_identifier) as brand_name', 'if(LOCATE("' . $keyword . '", COALESCE(tb_l.brand_name, brand.brand_identifier)) > 0, LOCATE("' . $keyword . '", COALESCE(tb_l.brand_name, brand.brand_identifier)), 99) as level'));
            //$brandSrch->addKeywordSearch($keyword, false, false);
            $brandSrch->addCondition('brand_name', 'LIKE', '%' . $keyword . '%');
            // $cnd->attachCondition('brand.brand_identifier', 'LIKE', '%' . $keyword . '%', 'OR');
            $brandSrch->addOrder('level');
            $brandSrch->addGroupBy('brand_id');
            $brandSrch->setPageSize(5);
            $brandRs = $brandSrch->getResultSet();
            $brandArr = [];
            while ($row = FatApp::getDb()->fetch($brandRs)) {
                $brandArr[$row['brand_id']] = $row['brand_name'];
            }

            $catListingCount = 10 - count($brandArr);
            $srch = new SearchBase(ProductCategory::DB_TBL_PROD_CAT_RELATIONS, 'cr');

            $catSrch = new ProductSearch(0);
            $catSrch->joinSellerProducts(0, '', $criteria, true);
            $catSrch->unsetDefaultLangForJoins();
            // $catSrch->joinSellers();
            $catSrch->setGeoAddress();
            $catSrch->joinShops(0, true, true, 0, true);
            $catSrch->joinBrands(0);
            $catSrch->joinSellerSubscription(0, false, true);
            $catSrch->addSubscriptionValidCondition();
            $catSrch->doNotCalculateRecords();

            $catSrch->joinProductToCategory($this->siteLangId);
            $catSrch->joinProductToTax();
            $catSrch->joinCategoryRelationWithChild();
            $catSrch->addMultipleFields(array('DISTINCT(prodcat_code)', 'cr.pcr_parent_id as qryProducts_prodcat_id'));
            $catSrch->removeFld('1 as availableInLocation');
            $catSrch->validateAndJoinDeliveryLocation(false, false);
            $catSrch->doNotCalculateRecords();
            $catSrch->doNotLimitRecords();

            $srch->joinTable('(' . $catSrch->getQuery() . ')', 'INNER JOIN', 'qryProducts.qryProducts_prodcat_id = cr.pcr_prodcat_id', 'qryProducts');
            $srch->addMultipleFields(array('prodcat_id', 'COALESCE(c_l.prodcat_name, c.prodcat_identifier) as prodcat_name', 'if(LOCATE("' . $keyword . '", COALESCE(c_l.prodcat_name, c.prodcat_identifier)) > 0, LOCATE("' . $keyword . '", COALESCE(c_l.prodcat_name, c.prodcat_identifier)), 99) as level'));
            $srch->joinTable(ProductCategory::DB_TBL, 'INNER JOIN', 'c.prodcat_id = cr.pcr_prodcat_id', 'c');
            $srch->joinTable(
                ProductCategory::DB_TBL_LANG,
                'LEFT OUTER JOIN',
                'prodcatlang_prodcat_id = c.prodcat_id
                AND prodcatlang_lang_id = ' . $this->siteLangId,
                'c_l'
            );
            $srch->setPageSize($catListingCount);
            $srch->addOrder('level');
            $srch->addGroupBy('prodcat_id');
            $catArr = [];
            $rs = $srch->getResultSet();
            while ($row = FatApp::getDb()->fetch($rs)) {
                $catArr[$row['prodcat_id']] = $row['prodcat_name'];
            }

            $srch = Tag::getSearchObject($this->siteLangId);
            $srch->doNotCalculateRecords();
            $srch->setPageSize(10);
            $srch->addMultipleFields(array('tag_id', 'tag_name', 'if(LOCATE("' . $keyword . '", tag_name) > 0 , LOCATE("' . $keyword . '", tag_name), 99) as level'));
            $srch->addOrder('level');
            $srch->addGroupby('tag_id');
            $srch->addHaving('tag_name', 'LIKE', '%' . urldecode($keyword) . '%');
            $tags = FatApp::getDb()->fetchAll($srch->getResultSet());
            $prodArr = [];
            if (empty($tags)) {
                $prodSrchObj->validateAndJoinDeliveryLocation(false, false);
                $prodSrchObj->setPageSize(10);
                $prodSrchObj->joinProductToCategory($this->siteLangId);
                $prodSrchObj->addMultipleFields(array('selprod_id', 'COALESCE(selprod_title, product_name, product_identifier) as selprod_title', 'COALESCE(c_l.prodcat_name, c.prodcat_identifier) as prodcat_name', 'if(LOCATE("' . $keyword . '", COALESCE(selprod_title, product_name, product_identifier)) > 0, LOCATE("' . $keyword . '", COALESCE(selprod_title, product_name, product_identifier)), 99) as level'));
                $prodSrchObj->addKeywordSearch($keyword, false, false);
                $prodSrchObj->addOrder('level');
                $prodSrchObj->addGroupBy('selprod_title');
                $prodSrchObj->doNotCalculateRecords();
                $prodRs = $prodSrchObj->getResultSet();
                $prodArr = FatApp::getDb()->fetchAll($prodRs);
            }

            $suggestions = [
                'tags' => $tags,
                'brands' => $brandArr,
                'categories' => $catArr,
                'products' => $prodArr
            ];
            if (!empty($tags) || !empty($brandArr) || !empty($catArr) || !empty($prodArr)) {
                array_unshift($recentSearchArr, $keyword);
            }
            $recentSearchArr = array_unique($recentSearchArr);
            $recentSearchArr = array_slice($recentSearchArr, 0, 5);
            setcookie('recentSearchKeywords_' . $this->siteLangId, serialize($recentSearchArr), time() + 60 * 60 * 72, CONF_WEBROOT_URL);
            CacheHelper::create('autoSuggetionsCache' . $cacheKey, serialize($suggestions));
        } else {
            $suggestions = unserialize($autoSuggetionsCache);
        }

        $this->set('suggestions', $suggestions);
        $this->set('recentSearchArr', $recentSearchArr);
        $this->set('keyword', $keyword);
        if (true === MOBILE_APP_API_CALL) {
            $this->_template->render();
        }

        $this->set('html', $this->_template->render(false, false, 'products/search-producttags-autocomplete.php', true, false));
        $this->_template->render(false, false, 'json-success.php', false, false);
    }

    public function getBreadcrumbNodes($action)
    {
        $nodes = array();
        $parameters = FatApp::getParameters();
        switch ($action) {
            case 'view':
                if (isset($parameters[0]) && FatUtility::int($parameters[0]) > 0) {
                    $selprod_id = FatUtility::int($parameters[0]);
                    if ($selprod_id) {
                        $srch = new ProductSearch($this->siteLangId);
                        $srch->joinSellerProducts(0, '', ['doNotJoinSpecialPrice' => true]);
                        $srch->joinProductToCategory();
                        $srch->doNotCalculateRecords();
                        $srch->setPageSize(1);
                        $srch->addMultipleFields(array('COALESCE(selprod_title, product_name, product_identifier) as selprod_title', 'COALESCE(product_name, product_identifier)as product_name', 'prodcat_code'));
                        $srch->addCondition('selprod_id', '=', $selprod_id);
                        $srch->getResultSet();
                        $row = FatApp::getDb()->fetch($srch->getResultSet());
                        if ($row) {
                            $productCatCode = $row['prodcat_code'];
                            $productCatCode = explode("_", $productCatCode);
                            $productCatCode = array_filter($productCatCode, 'strlen');
                            $productCatObj = new ProductCategory();
                            $prodCategories = $productCatObj->getCategoriesForSelectBox($this->siteLangId, '', $productCatCode);

                            foreach ($productCatCode as $code) {
                                $code = FatUtility::int($code);
                                if (isset($prodCategories[$code]['prodcat_name'])) {
                                    $prodCategories[$code]['prodcat_name'];
                                    $nodes[] = array('title' => $prodCategories[$code]['prodcat_name'], 'href' => UrlHelper::generateUrl('category', 'view', array($code)));
                                }
                            }
                            $nodes[] = array('title' => ($row['selprod_title']) ? $row['selprod_title'] : $row['product_name']);
                        }
                    }
                }
                break;
            default:
                $nodes[] = array('title' => Labels::getLabel('MSG_' . FatUtility::camel2dashed($action), $this->siteLangId));
                break;
        }
        return $nodes;
    }

    public function logWeightage()
    {
        $post = FatApp::getPostedData();
        $selprod_code = (isset($post['selprod_code']) && $post['selprod_code'] != '') ? $post['selprod_code'] : '';

        if ($selprod_code == '') {
            return false;
        }

        $weightageKey = SmartWeightageSettings::PRODUCT_VIEW;
        if (isset($post['timeSpend']) && $post['timeSpend'] == true) {
            $weightageKey = SmartWeightageSettings::PRODUCT_TIME_SPENT;
        }

        $weightageSettings = SmartWeightageSettings::getWeightageAssoc();
        Product::recordProductWeightage($selprod_code, $weightageKey, $weightageSettings[$weightageKey]);
    }

    private function getCartForm($formLangId)
    {
        $cart = new Cart();
        $frm = new Form('frmBuyProduct', array('id' => 'frmBuyProduct'));
        $fld = $frm->addTextBox(Labels::getLabel('FRM_QUANTITY', $formLangId), 'quantity', 1);
        $fld->requirements()->setIntPositive();
        $frm->addHTML('', 'btnAddToCart', '<button name="btnAddToCart" type="submit" id="btnAddToCart" class="btn btn-brand btn-block quickView add-to-cart add-to-cart--js" data-cart-has-product="' . ($cart->hasProducts() && $this->sellerId != $this->cartSellerId && 0 < FatApp::getConfig('CONF_SINGLE_SELLER_CART', FatUtility::VAR_INT, 0)) . '"> ' . Labels::getLabel('BTN_ADD_TO_CART', $formLangId) . '</button>');
        $frm->addHiddenField('', 'selprod_id');
        return $frm;
    }

    private function getReviewSearchForm($pageSize = 10)
    {
        $frm = new Form('frmReviewSearch');
        $frm->addHiddenField('', 'selprod_id');
        $frm->addHiddenField('', 'page');
        $frm->addHiddenField('', 'pageSize', $pageSize);
        $frm->addHiddenField('', 'orderBy', 'most_helpful');
        return $frm;
    }

    public function fatActionCatchAll($action)
    {
        FatUtility::exitWithErrorCode(404);
    }

    public function track(int $selprod_id)
    {
        /* Track Click */
        $prodObj = new PromotionSearch($this->siteLangId, true);
        $prodObj->joinProducts();
        $prodObj->joinActiveUser();
        $prodObj->joinShops();
        $prodObj->addPromotionTypeCondition(Promotion::TYPE_PRODUCT);
        $prodObj->addShopActiveExpiredCondition();
        $prodObj->joinUserWallet();
        $prodObj->joinBudget();
        $prodObj->addBudgetCondition();
        $prodObj->doNotCalculateRecords();
        $prodObj->addMultipleFields(array('selprod_id as proSelProdId', 'promotion_id'));
        $prodObj->addCondition('promotion_record_id', '=', $selprod_id);
        $prodObj->addCondition('promotion_deleted', '=', applicationConstants::NO);

        $productSrchObj = new ProductSearch($this->siteLangId);
        $productSrchObj->joinProductToCategory($this->siteLangId);
        $productSrchObj->doNotCalculateRecords();
        $productSrchObj->setPageSize(1);
        $productSrchObj->setDefinedCriteria();
        // $productSrchObj->joinProductRating();
        $productSrchObj->addMultipleFields(
            array(
                'product_id',
                'selprod_id',
                'COALESCE(product_name, product_identifier) as product_name',
                'COALESCE(selprod_title, product_name, product_identifier) as selprod_title',
                'special_price_found',
                'splprice_display_list_price',
                'splprice_display_dis_val',
                'splprice_display_dis_type',
                'theprice',
                'selprod_price',
                'selprod_stock',
                'selprod_condition',
                'prodcat_id',
                'COALESCE(prodcat_name, prodcat_identifier) as prodcat_name',
                'product_rating as prod_rating ',
                'selprod_sold_count'
            )
        );

        $productSrchObj->joinTable('(' . $prodObj->getQuery() . ') ', 'INNER JOIN', 'selprod_id = ppr.proSelProdId ', 'ppr');
        $productSrchObj->addFld(array('promotion_id'));
        $productSrchObj->joinSellerSubscription();
        $productSrchObj->addSubscriptionValidCondition();
        $productSrchObj->addGroupBy('selprod_id');

        $rs = $productSrchObj->getResultSet();
        $row = FatApp::getDb()->fetch($rs);

        $url = UrlHelper::generateFullUrl('products', 'view', array($selprod_id));
        if ($row == false) {
            LibHelper::exitWithError(Labels::getLabel('ERR_INVALID_ACCESS', $this->siteLangId), false, true);
            FatApp::redirectUser($url);
        }

        $userId = UserAuthentication::getLoggedUserId(true);

        if (Promotion::isUserClickCountable($userId, $row['promotion_id'], $_SERVER['REMOTE_ADDR'], session_id())) {
            $promotionClickData = array(
                'pclick_promotion_id' => $row['promotion_id'],
                'pclick_user_id' => $userId,
                'pclick_datetime' => date('Y-m-d H:i:s'),
                'pclick_ip' => $_SERVER['REMOTE_ADDR'],
                'pclick_cost' => Promotion::getPromotionCostPerClick(Promotion::TYPE_PRODUCT),
                'pclick_session_id' => session_id(),
            );

            FatApp::getDb()->insertFromArray(Promotion::DB_TBL_CLICKS, $promotionClickData, false, [], $promotionClickData);
            $clickId = FatApp::getDb()->getInsertId();

            $promotionClickChargesData = array(
                'picharge_pclick_id' => $clickId,
                'picharge_datetime' => date('Y-m-d H:i:s'),
                'picharge_cost' => Promotion::getPromotionCostPerClick(Promotion::TYPE_PRODUCT),
            );

            FatApp::getDb()->insertFromArray(Promotion::DB_TBL_ITEM_CHARGES, $promotionClickChargesData, false);

            $promotionLogData = array(
                'plog_promotion_id' => $row['promotion_id'],
                'plog_date' => date('Y-m-d'),
                'plog_clicks' => 1,
            );

            $onDuplicatePromotionLogData = array_merge($promotionLogData, array('plog_clicks' => 'mysql_func_plog_clicks+1'));
            FatApp::getDb()->insertFromArray(Promotion::DB_TBL_LOGS, $promotionLogData, true, array(), $onDuplicatePromotionLogData);
        }


        if (MOBILE_APP_API_CALL) {
            FatUtility::dieJsonSuccess(Labels::getLabel('LBL_SUCCESS'));
        }

        if (!filter_var($url, FILTER_VALIDATE_URL) === false) {
            FatApp::redirectUser($url);
        }
        FatApp::redirectUser(UrlHelper::generateUrl(''));
    }

    public function setUrlString()
    {
        $urlString = FatApp::getPostedData('urlString', FatUtility::VAR_STRING, '');
        if ($urlString != '') {
            $_SESSION['referer_page_url'] = rtrim($urlString, '/') . '/';
        }
    }

    public function sellers($selprod_id)
    {
        $selprod_id = FatUtility::int($selprod_id);
        $prodSrchObj = new ProductSearch($this->siteLangId);

        /* fetch requested product[ */
        $prodSrch = clone $prodSrchObj;
        $prodSrch->setLocationBasedInnerJoin(false);
        $prodSrch->setGeoAddress();
        $prodSrch->setDefinedCriteria(0, 0, array(), false);
        $prodSrch->joinProductToCategory();
        $prodSrch->joinSellerSubscription();
        $prodSrch->addSubscriptionValidCondition();
        $prodSrch->validateAndJoinDeliveryLocation(false);
        $prodSrch->doNotCalculateRecords();
        $prodSrch->addCondition('selprod_id', '=', $selprod_id);
        $prodSrch->setPageSize(1);

        $selProdReviewObj = new SelProdReviewSearch();
        $selProdReviewObj->joinSelProdRating();
        $selProdReviewObj->addCondition('ratingtype_type', 'IN', [RatingType::TYPE_PRODUCT, RatingType::TYPE_OTHER]);
        $selProdReviewObj->doNotCalculateRecords();
        $selProdReviewObj->doNotLimitRecords();
        $selProdReviewObj->addGroupBy('spr.spreview_product_id');
        $selProdReviewObj->addCondition('spr.spreview_status', '=', SelProdReview::STATUS_APPROVED);
        $selProdReviewObj->addMultipleFields(array('spr.spreview_selprod_id', 'spr.spreview_product_id', "ROUND(AVG(sprating_rating),2) as prod_rating", "count(spreview_id) as totReviews"));
        $selProdRviewSubQuery = $selProdReviewObj->getQuery();
        $prodSrch->joinTable('(' . $selProdRviewSubQuery . ')', 'LEFT OUTER JOIN', 'sq_sprating.spreview_product_id = product_id', 'sq_sprating');

        $prodSrch->addMultipleFields(
            array(
                'product_id',
                'COALESCE(product_name,product_identifier ) as product_name',
                'product_seller_id',
                'product_model',
                'COALESCE(prodcat_name, prodcat_identifier) as prodcat_name',
                'product_upc',
                'product_isbn',
                'product_short_description',
                'product_description',
                'selprod_id',
                'selprod_user_id',
                'selprod_code',
                'selprod_condition',
                'selprod_price',
                'special_price_found',
                'splprice_start_date',
                'splprice_end_date',
                'COALESCE(selprod_title,product_name,product_identifier) as selprod_title',
                'selprod_warranty',
                'selprod_return_policy',
                'selprodComments',
                'theprice',
                'selprod_stock',
                'selprod_threshold_stock_level',
                'IF(selprod_stock > 0, 1, 0) AS in_stock',
                'brand_id',
                'COALESCE(brand_name, brand_identifier) as brand_name',
                'brand_short_description',
                'user_name',
                'shop_id',
                'shop_name',
                'COALESCE(sq_sprating.prod_rating,0) prod_rating ',
                'COALESCE(sq_sprating.totReviews,0) totReviews',
                'splprice_display_dis_type',
                'splprice_display_dis_val',
                'splprice_display_list_price',
                'product_attrgrp_id',
                'product_youtube_video',
                'product_cod_enabled',
                'selprod_cod_enabled'
            )
        );

        $productRs = $prodSrch->getResultSet();
        $product = FatApp::getDb()->fetch($productRs);
        /* ] */

        if (!$product) {
            FatUtility::exitWithErrorCode(404);
        }
        /* more sellers[ */
        $product['moreSellersArr'] = Product::getMoreSeller($product['selprod_code'], $this->siteLangId);

        foreach ($product['moreSellersArr'] as $seller) {
            if (FatApp::getConfig("CONF_ALLOW_REVIEWS", FatUtility::VAR_INT, 0)) {
                $product['rating'][$seller['selprod_user_id']] = SelProdRating::getSellerRating($seller['selprod_user_id'], true);
            } else {
                $product['rating'][$seller['selprod_user_id']] = 0;
            }

            /*[ Check COD enabled*/
            $codEnabled = false;
            if (Product::isProductShippedBySeller($seller['product_id'], $seller['product_seller_id'], $seller['selprod_user_id'])) {
                if ($product['selprod_cod_enabled']) {
                    $codEnabled = true;
                }
            } else {
                if ($product['product_cod_enabled']) {
                    $codEnabled = true;
                }
            }
            $product['cod'][$seller['selprod_user_id']] = $codEnabled;
            /*]*/
        }
        /* ] */

        $displayProductNotAvailableLable = false;
        if (FatApp::getConfig('CONF_ENABLE_GEO_LOCATION', FatUtility::VAR_INT, 0) && !empty(FatApp::getConfig('CONF_GOOGLEMAP_API_KEY', FatUtility::VAR_STRING, ''))) {
            $displayProductNotAvailableLable = true;
        }
        $this->set('displayProductNotAvailableLable', $displayProductNotAvailableLable);
        $this->set('product', $product);
        $this->_template->render();
    }

    public function linksAutocomplete()
    {
        $page = FatApp::getPostedData('page', FatUtility::VAR_INT, 1);
        if ($page < 2) {
            $page = 1;
        }
        $prodCatObj = new ProductCategory();
        $search_keyword = FatApp::getPostedData('keyword', FatUtility::VAR_STRING, '');
        $search_keyword = urldecode($search_keyword);
        //$categories = $prodCatObj->getAutoCompleteProdCatTreeStructure(0, $this->siteLangId, $search_keyword);
        [$categories, $pageCount] = $prodCatObj->getProdCatAutoSuggest($search_keyword, 20, $this->siteLangId, [], $page);
        $json = array(
            'pageCount' => $pageCount,
            'results' => []
        );
        foreach ($categories as $key => $product) {
            $json['results'][] = array(
                'id' => $key,
                'text' => strip_tags(html_entity_decode($product, ENT_QUOTES, 'UTF-8'))
            );
        }
        if (MOBILE_APP_API_CALL) {
            $this->set('data', ['categories' => $json['results'] ?? []]);
            $this->_template->render();
        }
        die(json_encode($json));
    }

    private function getListingData($get)
    {
        $db = FatApp::getDb();

        $userId = 0;
        if (UserAuthentication::isUserLogged()) {
            $userId = UserAuthentication::getLoggedUserId();
        }

        $page = 1;
        if (array_key_exists('page', $get)) {
            $page = FatUtility::int($get['page']);
            if ($page < 2) {
                $page = 1;
            }
        }

        $pageSize = FatApp::getConfig('CONF_ITEMS_PER_PAGE_CATALOG', FatUtility::VAR_INT, 10);
        if (array_key_exists('pageSize', $get)) {
            $pageSize = FatUtility::int($get['pageSize']);
            $pageSizeArr = FilterHelper::getPageSizeArr($this->siteLangId);
            if (0 >= $pageSize || !array_key_exists($pageSize, $pageSizeArr)) {
                $pageSize = FatApp::getConfig('CONF_ITEMS_PER_PAGE_CATALOG', FatUtility::VAR_INT, 10);
            }
        }

        if (FatApp::getConfig('CONF_DEFAULT_PLUGIN_' . Plugin::TYPE_FULL_TEXT_SEARCH, FatUtility::VAR_INT, 0)) {
            $srch = FullTextSearch::getListingObj($get, $this->siteLangId, $userId);
            $page = ($page - 1) * $pageSize;
            $srch->setPageNumber($page);
            $srch->setPageSize($pageSize);
            $srch->setFields(array('brand', 'categories', 'general'));
            $records = $srch->fetch();
            $products = [];

            if (isset($records['hits']) && count($records['hits']) > 0) {
                foreach ($records['hits'] as $record) {
                    if (FatApp::getConfig('CONF_ADD_FAVORITES_TO_WISHLIST', FatUtility::VAR_INT, 1) == applicationConstants::NO) {
                        $arr = array('ufp_id' => 0);
                        $favSrch = new UserFavoriteProductSearch();
                        $favSrch->addCondition('ufp_user_id', '=', $userId);
                        $favSrch->addCondition('ufp_selprod_id', '=', $record['_source']['general']['selprod_id']);
                        $favSrch->doNotCalculateRecords();
                        $favSrch->setPageSize(1);
                        $favSrch->addGroupBy('selprod_id');
                        $rs = $favSrch->getResultSet();
                        $wishListProd = $db->fetch($rs);
                        if (!empty($wishListProd) && $wishListProd['ufp_id']) {
                            $arr = array('ufp_id' => $wishListProd['ufp_id']);
                        }
                    } else {
                        $arr = array('is_in_any_wishlist' => 0);
                        $wislistPSrchObj = new UserWishListProductSearch();
                        $wislistPSrchObj->joinWishLists();
                        $wislistPSrchObj->doNotCalculateRecords();
                        $wislistPSrchObj->setPageSize(1);
                        $wislistPSrchObj->addCondition('uwlist_user_id', '=', $userId);
                        $wislistPSrchObj->addMultipleFields(array('uwlp_selprod_id', 'uwlp_uwlist_id'));
                        $wislistPSrchObj->addCondition('uwlp_selprod_id', '=', $record['_source']['general']['selprod_id']);
                        $rs = $wislistPSrchObj->getResultSet();
                        $wishListProd = $db->fetch($rs);
                        if (!empty($wishListProd) && $wishListProd['uwlp_uwlist_id']) {
                            $arr = array('is_in_any_wishlist' => 1);
                        }
                    }

                    $products[] = array_merge($record['_source']['general'], $record['_source']['brand'], current($record['_source']['categories']), $arr);
                }
            }
        } else {
            $get['page'] = $page;
            $get['pageSize'] = $pageSize;

            $srch = Product::getListingObj($get, $this->siteLangId, $userId);

            $flds = array(
                'prodcat_code',
                'product_id',
                'prodcat_id',
                'COALESCE(product_name, product_identifier) as product_name',
                'product_model',
                'product_updated_on',
                'COALESCE(prodcat_name, prodcat_identifier) as prodcat_name',
                'selprod_id',
                'selprod_user_id',
                'selprod_code',
                'selprod_stock',
                'selprod_condition',
                'selprod_price',
                'COALESCE(selprod_title  ,COALESCE(product_name, product_identifier)) as selprod_title',
                'splprice_display_list_price',
                'splprice_display_dis_val',
                'splprice_display_dis_type',
                'splprice_start_date',
                'splprice_end_date',
                'brand_id',
                'COALESCE(brand_name, brand_identifier) as brand_name',
                'user_name',
                'IF(selprod_stock > 0, 1, 0) AS in_stock',
                'selprod_sold_count',
                'selprod_return_policy', /*'maxprice', 'ifnull(sq_sprating.totReviews,0) totReviews','IF(ufp_id > 0, 1, 0) as isfavorite', */
                'selprod_min_order_qty',
                'shop.shop_id',
                'shop.shop_lat',
                'shop.shop_lng',
                'COALESCE(shop_name, shop_identifier) as shop_name',
                'selprod_cart_type',
                'selprod_hide_price',
                'shop.shop_rfq_enabled'
            );
            $removeFlds = array_diff($flds, ['1']);
            $this->setRecordCount(clone $srch, $get['pageSize'], $get['page'], $get, true, $removeFlds);
            Product::setOrderOnListingObj($srch, $get);

            $srch->setPageNumber($page);
            if ($pageSize) {
                $srch->setPageSize($pageSize);
            }
            $products = FatApp::getDb()->fetchAll($srch->getResultSet());
        }

        /* to show searched category data[ */
        $categoryId = null;
        $category = array();
        if (array_key_exists('category', $get)) {
            $categoryId = FatUtility::int($get['category']);
            if ($categoryId) {
                $productCategorySearch = new ProductCategorySearch($this->siteLangId);
                $productCategorySearch->addCondition('prodcat_id', '=', $categoryId);
                $productCategorySearch->addMultipleFields(array('prodcat_id', 'COALESCE(prodcat_name, prodcat_identifier) as prodcat_name', 'prodcat_description', 'prodcat_code'));
                $productCategorySearchRs = $productCategorySearch->getResultSet();
                $category = $db->fetch($productCategorySearchRs);
                $category['banner'] = AttachedFile::getAttachment(AttachedFile::FILETYPE_CATEGORY_BANNER, $categoryId);
            }
        }
        /*
        $moreSellersArr = [];
        if ($get['vtype'] == 'map') {
            if (0 < count($products)) {
                $selprodCodes = array_column($products, 'selprod_code');
                $moreSellersArr = Product::getMoreSeller($selprodCodes, $this->siteLangId);
            }
        }
        */

        /* ] */

        $data = array(
            'products' => $products,
            /* 'moreSellersProductsArr' => $moreSellersArr, *//* seller products which is related to same options*/
            'category' => $category,
            'categoryId' => $categoryId,
            'postedData' => $get,
            'page' => $this->pageData['page'],
            'pageSize' => $this->pageData['pageSize'],
            'pageCount' => $this->pageData['pageCount'],
            'recordCount' => $this->pageData['recordCount'],
            'siteLangId' => $this->siteLangId
        );
        return $data;
    }

    public function getFilteredProducts()
    {
        $post = FatApp::getPostedData();
        $userId = UserAuthentication::getLoggedUserId(true);
        $post['join_price'] = 1;
        $page = 1;
        if (array_key_exists('page', $post)) {
            $page = FatUtility::int($post['page']);
            if ($page < 2) {
                $page = 1;
            }
        }

        $pageSize = !empty($post['pageSize']) ? FatUtility::int($post['pageSize']) : FatApp::getConfig('CONF_ITEMS_PER_PAGE_CATALOG', FatUtility::VAR_INT, 10);

        $post['page'] = $page;
        $post['pageSize'] = $pageSize;

        $srch = Product::getListingObj($post, $this->siteLangId, $userId);
        $flds = array(
            'prodcat_code',
            'product_id',
            'prodcat_id',
            'COALESCE(product_name, product_identifier) as product_name',
            'product_model',
            'product_updated_on',
            'COALESCE(prodcat_name, prodcat_identifier) as prodcat_name',
            'selprod_id',
            'selprod_user_id',
            'selprod_code',
            'selprod_stock',
            'selprod_condition',
            'selprod_price',
            'COALESCE(selprod_title  ,COALESCE(product_name, product_identifier)) as selprod_title',
            'splprice_display_list_price',
            'splprice_display_dis_val',
            'splprice_display_dis_type',
            'splprice_start_date',
            'splprice_end_date',
            'brand_id',
            'COALESCE(brand_name, brand_identifier) as brand_name',
            'user_name',
            'IF(selprod_stock > 0, 1, 0) AS in_stock',
            'selprod_sold_count',
            'selprod_return_policy', /*'maxprice', 'ifnull(sq_sprating.totReviews,0) totReviews','IF(ufp_id > 0, 1, 0) as isfavorite', */
            'selprod_min_order_qty',
            'shop.shop_id',
            'shop.shop_lat',
            'shop.shop_lng',
            'COALESCE(shop_name, shop_identifier) as shop_name',
            'selprod_cart_type',
            'selprod_hide_price',
            'shop.shop_rfq_enabled'
        );
        $removeFlds = array_diff($flds, ['1']);
        $this->setRecordCount(clone $srch, $post['pageSize'], $post['page'], $post, true, $removeFlds);

        Product::setOrderOnListingObj($srch, $post);

        $srch->setPageNumber($page);
        $srch->setPageSize($pageSize);
        $rs = $srch->getResultSet();
        $db = FatApp::getDb();
        $products = $db->fetchAll($rs);

        $selProdIdsArr = array_column($products, 'selprod_id');
        $tRightRibbons = Badge::getRibbons($this->siteLangId, Badge::RIBB_POS_TRIGHT, $selProdIdsArr);
        $data = array(
            'products' => $products,
            'tRightRibbons' => $tRightRibbons,
            'page' => $this->pageData['page'],
            'pageSize' => $this->pageData['pageSize'],
            'pageCount' => $this->pageData['pageCount'],
            'recordCount' => $this->pageData['recordCount'],
        );
        $this->set('data', $data);
        $this->_template->render();
    }

    public function getOptions($selProdId)
    {
        $selProdId = FatUtility::int($selProdId);
        if (1 > $selProdId) {
            FatUtility::dieJsonError(Labels::getLabel('ERR_INVALID_REQUEST', $this->siteLangId));
        }
        $optionRows = SellerProduct::getFormattedOptions($selProdId, $this->siteLangId);
        $this->set('options', $optionRows);
        $this->_template->render();
    }

    public function autoCompleteTaxCategories()
    {
        $pagesize = 20;
        $page = FatApp::getPostedData('page', FatUtility::VAR_INT, 1);
        if ($page < 2) {
            $page = 1;
        }
        $post = FatApp::getPostedData();
        $srch = Tax::getSearchObject($this->siteLangId, true);
        $srch->addCondition('taxcat_deleted', '=', 0);
        $activatedTaxServiceId = Tax::getActivatedServiceId();

        $srch->addFld('taxcat_id');
        if ($activatedTaxServiceId) {
            $srch->addFld('concat(IFNULL(taxcat_name,taxcat_identifier), " (",taxcat_code,")")as taxcat_name');
        } else {
            $srch->addFld('IFNULL(taxcat_name,taxcat_identifier)as taxcat_name');
        }
        $srch->addCondition('taxcat_plugin_id', '=', $activatedTaxServiceId);

        if (!empty($post['keyword'])) {
            $srch->addCondition('taxcat_name', 'LIKE', '%' . $post['keyword'] . '%')
                ->attachCondition('taxcat_identifier', 'LIKE', '%' . $post['keyword'] . '%')
                ->attachCondition('taxcat_code', 'LIKE', '%' . $post['keyword'] . '%');
        }
        $srch->setPageNumber($page);
        $srch->setPageSize($pagesize);
        $rs = $srch->getResultSet();
        $db = FatApp::getDb();
        $taxCategories = $db->fetchAll($rs, 'taxcat_id');

        $json = array(
            'pageCount' => $srch->pages()
        );
        foreach ($taxCategories as $key => $taxCategory) {
            $taxCatName = strip_tags(html_entity_decode($taxCategory['taxcat_name'], ENT_QUOTES, 'UTF-8'));
            $json['results'][] = array(
                'id' => $key,
                'text' => $taxCatName
            );
        }
        die(json_encode($json));
    }

    /**
     * getOrderProductLabel
     *
     * @param  string $excryptedOpId
     * @return void
     */
    public function getOrderProductLabel(string $excryptedOpId)
    {
        $opId = LibHelper::decrypt($excryptedOpId);
        $plugin = new Plugin();
        $keyName = $plugin->getDefaultPluginKeyName(Plugin::TYPE_SHIPPING_SERVICES);

        $error = '';
        $shippingService = LibHelper::callPlugin($keyName, [$this->siteLangId], $error, $this->siteLangId);
        if (false === $shippingService) {
            FatUtility::dieJsonError($error);
        }

        if (false === $shippingService->init()) {
            FatUtility::dieJsonError($shippingService->getError());
        }

        $orderProductShipmentDetail = OrderProduct::getShippingResponse($opId, OrderProduct::RESPONSE_TYPE_SHIPMENT, true);
        if (empty($orderProductShipmentDetail) || empty($orderProductShipmentDetail['opr_response'])) {
            FatUtility::dieJsonError(Labels::getLabel("ERR_NO_LABEL_DATA_FOUND", $this->siteLangId));
        }

        $shipmentResponse = json_decode($orderProductShipmentDetail['opr_response'], true);
        $trackingNumber = $orderProductShipmentDetail['opship_tracking_number'];
        $filename = "label-" . $trackingNumber;
        $labelData = isset($shipmentResponse['labelData']) ? $shipmentResponse['labelData'] : $shipmentResponse;
        $shippingService->downloadLabel($labelData, $filename);
    }

    public function downloadPreview($aFileId, $recordId)
    {
        $aFileId = FatUtility::int($aFileId);
        $recordId = FatUtility::int($recordId);
        $requestType = Product::CATALOG_TYPE_INVENTORY;

        $selProdData = SellerProduct::getAttributesById($recordId, array('selprod_user_id', 'selprod_product_id'));
        if (false == $selProdData) {
            FatUtility::dieWithError(Labels::getLabel("ERR_INVALID_REQUEST", $this->siteLangId));
        }

        $ddpObj = new DigitalDownloadPrivilages();

        if (false == $ddpObj->allowedWithInventory($selProdData['selprod_product_id'])) {
            $recordId = $selProdData['selprod_product_id'];
            $requestType = Product::CATALOG_TYPE_PRIMARY;
        }

        $file = DigitalDownloadSearch::getAttachmentDetail($aFileId, $recordId, $requestType, 1);
        if (1 > count($file)) {
            FatUtility::dieWithError(Labels::getLabel("ERR_FILE_NOT_FOUND", $this->siteLangId));
        }

        if ($file['pddr_record_id'] != $recordId) {
            FatUtility::dieWithError(Labels::getLabel("ERR_INVALID_ACCESS", $this->siteLangId));
        }

        if (!file_exists(CONF_UPLOADS_PATH . $file['afile_physical_path'])) {
            FatUtility::dieWithError(Labels::getLabel("ERR_FILE_NOT_FOUND", $this->siteLangId));
        }

        $fileName = isset($file['afile_physical_path']) ? $file['afile_physical_path'] : '';
        AttachedFile::downloadAttachment($fileName, $file['afile_name']);
    }

    public function autoComplete()
    {
        $keyword = FatApp::getPostedData('keyword', FatUtility::VAR_STRING, '');
        $srch = new ProductSearch($this->siteLangId);
        $srch->addOrder('product_name');
        if (!empty($keyword)) {
            $cnd = $srch->addCondition('product_name', 'LIKE', '%' . $keyword . '%');
            $cnd->attachCondition('product_identifier', 'LIKE', '%' . $keyword . '%', 'OR');
        }

        $srch->addCondition(Product::DB_TBL_PREFIX . 'active', '=', applicationConstants::YES);
        $srch->addCondition(Product::DB_TBL_PREFIX . 'deleted', '=', applicationConstants::NO);
        $srch->addCondition(Product::DB_TBL_PREFIX . 'seller_id', '=', UserAuthentication::getLoggedUserId());
        $excludeRecords = FatApp::getPostedData('excludeRecords', FatUtility::VAR_INT);
        if (!empty($excludeRecords) && is_array($excludeRecords)) {
            $srch->addCondition('product_id', 'NOT IN', $excludeRecords);
        }

        $srch->addMultipleFields(array('product_id as id', 'COALESCE(product_name, product_identifier) as name'));
        $srch->doNotCalculateRecords();
        $db = FatApp::getDb();
        $products = $db->fetchAll($srch->getResultSet());
        die(json_encode($products));
    }

    private function getRelatedProdIdArr($sellProdId)
    {
        $srch = new SearchBase(SellerProduct::DB_TBL_RELATED_PRODUCTS);
        $srch->joinTable(SellerProduct::DB_TBL, 'INNER JOIN', SellerProduct::DB_TBL_PREFIX . 'id = ' . SellerProduct::DB_TBL_RELATED_PRODUCTS_PREFIX . 'recommend_sellerproduct_id');
        $srch->joinTable(Product::DB_TBL, 'LEFT JOIN', Product::DB_TBL_PREFIX . 'id = ' . SellerProduct::DB_TBL_PREFIX . 'product_id');
        $srch->addCondition('related_sellerproduct_id', '=', 'mysql_func_' . $sellProdId, 'AND', true);
        $srch->doNotCalculateRecords();
        $srch->setPageSize(5);
        $rs = $srch->getResultSet();
        $db = FatApp::getDb();
        return $db->fetchAll($rs, 'selprod_id');
    }

    public function interRelatedProducts(int $selprodId)
    {
        $prodData = $this->getProductDetail($selprodId);
        $this->set('product', $prodData);

        /* Related Products */
        //$sellerProduct = new SellerProduct($selprodId);
        // $relatedProducts = $sellerProduct->getRelatedProducts($this->siteLangId, $prodData['selprod_id'], ['selprod_id']);
        $relatedProducts = $this->getRelatedProdIdArr($prodData['selprod_id']);
        $relatedProductsRs = $this->relatedProductsById(array_keys($relatedProducts));
        $relSelProdIdsArr = array_column($relatedProducts, 'selprod_id');
        $relatedProductsRibbons = [
            'tRightRibbons' => Badge::getRibbons($this->siteLangId, Badge::RIBB_POS_TRIGHT, $relSelProdIdsArr)
        ];

        $this->set('relatedProductsRibbons', $relatedProductsRibbons);
        $this->set('relatedProductsRs', !empty($relatedProductsRs) ? $relatedProductsRs : []);
        $this->set('relatedProductsHtml', $this->_template->render(false, false, 'products/related-products.php', true, false));
        /* ----------------- */

        /* Recommended Products */
        $loggedUserId = UserAuthentication::getLoggedUserId(true);
        $recommendedProducts = (array) $this->getRecommendedProducts($selprodId, $this->siteLangId, $loggedUserId);
        $recommendedProducts = (0 < count(array_filter($recommendedProducts)) ? array_filter($recommendedProducts) : []);
        if (0 < $extraRecCount = (5 - count($recommendedProducts))) {
            $srch = Product::getListingObj(['category' => $prodData['prodcat_id']], $this->siteLangId);
            $srch->setPageSize($extraRecCount);
            $srch->doNotCalculateRecords();
            $products = FatApp::getDb()->fetchAll($srch->getResultSet());
            $recommendedProducts = array_merge($recommendedProducts, $products);
        }

        $recSelProdIdsArr = array_column($recommendedProducts, 'selprod_id');
        $recommendedProductsRibbons = [
            'tRightRibbons' => Badge::getRibbons($this->siteLangId, Badge::RIBB_POS_TRIGHT, $recSelProdIdsArr)
        ];
        $this->set('recommendedProductsRibbons', $recommendedProductsRibbons);
        $this->set('recommendedProducts', $recommendedProducts);
        $this->set('recommendedProductsHtml', $this->_template->render(false, false, 'products/recommended-products.php', true, false));
        /* ----------------- */

        /* Recently Viewed Products */
        $recentViewedProducts = array();
        $cookieProducts = isset($_COOKIE['recentViewedProducts']) ? $_COOKIE['recentViewedProducts'] : false;
        if ($cookieProducts != false) {
            $cookiesProductsArr = explode("_", $cookieProducts);
            if (!isset($cookiesProductsArr) || !is_array($cookiesProductsArr) || count($cookiesProductsArr) <= 0) {
                return '';
            }
            if ($selprodId && in_array($selprodId, $cookiesProductsArr)) {
                $pos = array_search($selprodId, $cookiesProductsArr);
                unset($cookiesProductsArr[$pos]);
            }

            if (isset($cookiesProductsArr) && is_array($cookiesProductsArr) && count($cookiesProductsArr)) {
                $cookiesProductsArr = array_map('intval', $cookiesProductsArr);
                $cookiesProductsArr = array_reverse($cookiesProductsArr);

                $recentViewedProducts = $this->getRecentlyViewedProductsDetail($cookiesProductsArr);
            }
        }

        $recentlyViewedRibbons = [];
        if (!empty($recentViewedProducts)) {
            $recentSelProdIdsArr = array_column($recentViewedProducts, 'selprod_id');
            $recentlyViewedRibbons = [
                'tRightRibbons' => Badge::getRibbons($this->siteLangId, Badge::RIBB_POS_TRIGHT, $recentSelProdIdsArr)
            ];
        }

        $this->set('recentlyViewedRibbons', $recentlyViewedRibbons);
        $this->set('recentViewedProducts', $recentViewedProducts);
        $this->set('recentViewedProductsHtml', $this->_template->render(false, false, 'products/recently-viewed-products.php', true, false));
        /* ----------------- */

        $this->_template->render(false, false, 'json-success.php', true, false);
    }
}
