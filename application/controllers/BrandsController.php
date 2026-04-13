<?php

class BrandsController extends MyAppController
{
    public function __construct($action)
    {
        parent::__construct($action);
    }

    public function index()
    {
        $brandSrch = Brand::getListingObj($this->siteLangId, array('brand_id', 'IFNULL(TRIM(brand_name), TRIM(brand_identifier)) as brand_name'), true);
        $brandSrch->doNotCalculateRecords();
        $brandSrch->doNotLimitRecords();
        $brandSrch->addOrder('brand_name', 'asc');
        $brandRs = $brandSrch->getResultSet();
        $brandsArr = FatApp::getDb()->fetchAll($brandRs);
        if (true === MOBILE_APP_API_CALL) {
            /* $db = FatApp::getDb();
            $totalProdCountToDisplay = 4;
            $productCustomSrchObj = new ProductSearch($this->siteLangId);
            $productCustomSrchObj->joinProductToCategory($this->siteLangId);
            $productCustomSrchObj->setDefinedCriteria(0, 0, ['doNotJoinSellers' => true, 'doNotJoinShippingPkg' => true]);
           
            if (UserAuthentication::isUserLogged()) {
                $productCustomSrchObj->joinFavouriteProducts(UserAuthentication::getLoggedUserId());
            }

            // $productCustomSrchObj->joinProductRating();
            $productCustomSrchObj->addCondition('selprod_deleted', '=', applicationConstants::NO);
            $productCustomSrchObj->addGroupBy('selprod_id');

            $productCustomSrchObj->addMultipleFields(
                array(
                    'product_id',
                    'selprod_id',
                    'IFNULL(product_name, product_identifier) as product_name',
                    'IFNULL(selprod_title  ,IFNULL(product_name, product_identifier)) as selprod_title',
                    'special_price_found',
                    'splprice_display_list_price',
                    'splprice_display_dis_val',
                    'splprice_display_dis_type',
                    'theprice',
                    'selprod_price',
                    'selprod_stock',
                    'selprod_condition',
                    'prodcat_id',
                    'IFNULL(prodcat_name, prodcat_identifier) as prodcat_name',
                    'product_rating as prod_rating ',
                    'product_total_reviews as totReviews',
                    'selprod_sold_count',
                    'selprod_min_order_qty',
                    'selprod_cart_type',
                    'selprod_hide_price',
                    'product_img_updated_on',
                    'shop_rfq_enabled'
                )
            );
            if (UserAuthentication::isUserLogged()) {
                $productCustomSrchObj->addFld(array('IF(ufp_id > 0, 1, 0) as isfavorite', 'IFNULL(ufp_id, 0) as ufp_id'));
            } else {
                $productCustomSrchObj->addFld(array('0 as isfavorite', '0 as ufp_id'));
            }

            $productCustomSrchObj->setPageSize($totalProdCountToDisplay);*/
            $cnt = 0;
            foreach ($brandsArr as $val) {
                $brandsArr[$cnt] = $val;
                /* $prodSrch = clone $productCustomSrchObj;
                $prodSrch->addBrandCondition($val['brand_id']);
                $prodSrch->doNotCalculateRecords();
                $prodSrch->addGroupBy('selprod_id');
                $prodRs = $prodSrch->getResultSet();                
                $brandProducts = $db->fetchAll($prodRs);

                foreach ($brandProducts as &$brandProduct) {
                    $uploadedTime = AttachedFile::setTimeParam($brandProduct['product_img_updated_on']);
                    $mainImgUrl = UrlHelper::getCachedUrl(UrlHelper::generateFullFileUrl('image', 'product', array($brandProduct['product_id'], ImageDimension::VIEW_MEDIUM, $brandProduct['selprod_id'], 0, $this->siteLangId)) . $uploadedTime, CONF_IMG_CACHE_TIME, '.jpg');
                    $brandProduct['discounted_text'] = CommonHelper::showProductDiscountedText($brandProduct, $this->siteLangId);
                    $brandProduct['product_image'] = $mainImgUrl;
                    $brandProduct['currency_selprod_price'] = CommonHelper::displayMoneyFormat($brandProduct['selprod_price']);
                    $brandProduct['currency_theprice'] = CommonHelper::displayMoneyFormat($brandProduct['theprice']);
                }                
                $brandsArr[$cnt]['products'] = $brandProducts;
                $brandsArr[$cnt]['totalProducts'] = $prodSrch->recordCount(); */
                $brandsArr[$cnt]['brand_image'] = UrlHelper::getCachedUrl(UrlHelper::generateFullFileUrl('image', 'brand', array($val['brand_id'], $this->siteLangId)), CONF_IMG_CACHE_TIME, '.jpg');
                $cnt++;
            }
        }
        $this->set('layoutDirection', Language::getLayoutDirection($this->siteLangId));
        $this->set('allBrands', $brandsArr);
        $this->_template->render();
    }

    public function all()
    {
        FatApp::redirectUser(UrlHelper::generateUrl('Brands'));
    }

    public function view($brandId)
    {
        $brandId = FatUtility::int($brandId);

        $db = FatApp::getDb();

        $brandSrch = Brand::getListingObj($this->siteLangId, array('brand_id', 'IFNULL(brand_name, brand_identifier) as brand_name'), true);
        $brandSrch->addCondition('brand_id', '=', $brandId);
        $brandSrch->addOrder('brand_name', 'asc');
        $brandSrch->doNotCalculateRecords();
        $brandSrch->setPageSize(1);
        $brandRs = $brandSrch->getResultSet();
        $brand = FatApp::getDb()->fetch($brandRs);

        if (empty($brand)) {
            $this->redirectMissingEntitySeoToHomeIfEnabled();
            FatUtility::exitWithErrorCode(404);
        }

        $frm = $this->getProductSearchForm();

        $get = FatApp::getParameters();
        $get = Product::convertArrToSrchFiltersAssocArr($get);

        $get['join_price'] = 1;
        $get['brand_id'] = $brandId;
        $get['brand'] = array($brandId); /*For filters*/
        $get['vtype']  = $get['vtype'] ?? 'grid';
        $viewType = FatApp::getPostedData('viewType', FatUtility::VAR_STRING, '');
        if (!FatApp::getConfig('CONF_ENABLE_GEO_LOCATION', FatUtility::VAR_INT, 0) && !empty(FatApp::getConfig('CONF_GOOGLEMAP_API_KEY', FatUtility::VAR_STRING, '')) && $get['vtype'] == 'map') {
            $get['vtype'] = 'grid';
        }
        // $frm->fill($get);


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

        $pageSize = 1;
        if (array_key_exists('pageSize', $get)) {
            $pageSize = FatUtility::int($get['pageSize']);
            if (0 >= $pageSize) {
                $pageSize = FatApp::getConfig('CONF_ITEMS_PER_PAGE_CATALOG', FatUtility::VAR_INT, 10);
            }
        }

        if (!in_array($pageSize, FilterHelper::getPageSizeValues())) {
            $pageSize = FatApp::getConfig('CONF_ITEMS_PER_PAGE_CATALOG', FatUtility::VAR_INT, 10);
        }

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

        $rs = $srch->getResultSet();
        $db = FatApp::getDb();
        $products = $db->fetchAll($rs);

        $frm->fill($get);
        $data = array(
            'frmProductSearch' => $frm,
            'products' => $products,
            /*'moreSellersProductsArr' => $moreSellersArr,*/
            'page' => $this->pageData['page'],
            'pageSize' => $this->pageData['pageSize'],
            'pageCount' => $this->pageData['pageCount'],
            'recordCount' => $this->pageData['recordCount'],
            'postedData' => $get,
            'viewType' => $viewType,
            'pageTitle' => $brand['brand_name'],
            'canonicalUrl' => UrlHelper::generateFullUrl('Brands', 'view', array($brandId)),
            'productSearchPageType' => SavedSearchProduct::PAGE_BRAND,
            'recordId' => $brandId,
            'bannerListigUrl' => UrlHelper::generateFullUrl('Banner', 'brands'),
            'siteLangId' => $this->siteLangId,
            'showBreadcrumb' => true,
            'pageSizeArr' => FilterHelper::getPageSizeArr($this->siteLangId)
        );

        if (FatUtility::isAjaxCall() && $viewType != 'popup') {
            $this->set('products', $products);
            /* $this->set('moreSellersProductsArr', $data['moreSellersProductsArr']);*/
            $this->set('siteLangId', $this->siteLangId);
            $this->set('postedData', $get);
            $this->set('pageSizeArr', $data['pageSizeArr']);
            echo $this->_template->render(false, false, 'products/products-list.php', true);
            exit;
        }

        $this->set('data', $data);
        if (FatUtility::isAjaxCall() && $viewType == 'popup') {
            $this->set('products', $products);
            $this->set('postedData', $get);
            $this->set('viewType', $viewType);
            $this->set('siteLangId', $this->siteLangId);
            $this->set('pageSizeArr', $data['pageSizeArr']);
            $this->_template->render(false, false, 'products/listing-map-page.php');
            exit;
        }

        $this->includeProductPageJsCss();
        $this->_template->addJs('js/slick.min.js');
        $this->_template->render();
    }

    public function autoComplete()
    {
        $page = FatApp::getPostedData('page', FatUtility::VAR_INT, 1);
        if ($page < 2) {
            $page = 1;
        }

        $pagesize = FatApp::getConfig('CONF_PAGE_SIZE');
        $post = FatApp::getPostedData();
        $fetchAllRecords = FatApp::getPostedData('fetchAllRecords', FatUtility::VAR_INT, 0);
        $brandObj = new Brand();
        $srch = $brandObj->getSearchObject($this->siteLangId, true, true);

        $srch->addMultipleFields(array('brand_id, IFNULL(brand_name, brand_identifier) as brand_name'));

        if (!empty($post['keyword'])) {
            $cond = $srch->addCondition('brand_name', 'LIKE', '%' . $post['keyword'] . '%');
            $cond->attachCondition('brand_identifier', 'LIKE', '%' . $post['keyword'] . '%', 'OR');
        }
        $srch->addCondition('brand_status', '=', Brand::BRAND_REQUEST_APPROVED);

        if ($fetchAllRecords == 1) {
            $srch->doNotCalculateRecords();
            $srch->doNotLimitRecords();
        } else {
            $srch->setPageNumber($page);
            $srch->setPageSize($pagesize);
        }
        $rs = $srch->getResultSet();
        $db = FatApp::getDb();
        $brands = $db->fetchAll($rs, 'brand_id');
        $json = array(
            'pageCount' => $srch->pages()
        );
        foreach ($brands as $key => $brand) {
            $json['results'][] = array(
                'id' => $key,
                'text' => strip_tags(html_entity_decode($brand['brand_name'], ENT_QUOTES, 'UTF-8'))
            );
        }

        die(json_encode($json));
        /* $this->set('brands', $db->fetchAll($rs,'brand_id') );
        $this->_template->render(false,false); */
    }

    public function checkUniqueBrandName()
    {
        $post = FatApp::getPostedData();

        $langId = FatUtility::int($post['langId']);

        $brandName = $post['brandName'];
        $brandId = FatUtility::int($post['brandId']);
        if (1 > $langId) {
            trigger_error(Labels::getLabel('ERR_LANG_ID_NOT_SPECIFIED', CommonHelper::getLangId()), E_USER_ERROR);
        }
        if (1 > $brandId) {
            trigger_error(Labels::getLabel('ERR_BRAND_ID_NOT_SPECIFIED', CommonHelper::getLangId()), E_USER_ERROR);
        }
        $srch = Brand::getSearchObject($langId);
        $srch->addCondition('brand_name', '=', $brandName);
        if ($brandId) {
            $srch->addCondition('brand_id', '!=', $brandId);
        }
        $rs = $srch->getResultSet();
        $records = $srch->recordCount();
        if ($records > 0) {
            FatUtility::dieJsonError(sprintf(Labels::getLabel('ERR_%S_NOT_AVAILABLE', $this->siteLangId), $brandName));
        }
        FatUtility::dieJsonSuccess(array());
    }

    public function getBreadcrumbNodes($action)
    {
        $nodes = array();
        $parameters = FatApp::getParameters();
        switch ($action) {
            case 'view':
                $nodes[] = array('title' => Labels::getLabel('MSG_BRANDS', $this->siteLangId), 'href' => UrlHelper::generateUrl('brands'));
                if (isset($parameters[0]) && $parameters[0] > 0) {
                    $brandId = FatUtility::int($parameters[0]);
                    if ($brandId > 0) {
                        $brandSrch = Brand::getListingObj($this->siteLangId, array('IFNULL(brand_name, brand_identifier) as brand_name',));
                        $brandSrch->doNotCalculateRecords();
                        $brandSrch->setPageSize(1);
                        $brandSrch->addCondition('brand_id', '=', $brandId);
                        $brandRs = $brandSrch->getResultSet();
                        $brandsArr = FatApp::getDb()->fetch($brandRs);
                        $nodes[] = array('title' => $brandsArr['brand_name']);
                    }
                }

                break;

            case 'index':
                $nodes[] = array('title' => Labels::getLabel('MSG_Brands', $this->siteLangId));

                break;
        }
        return $nodes;
    }
}
