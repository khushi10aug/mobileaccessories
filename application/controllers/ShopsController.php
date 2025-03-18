<?php

class ShopsController extends MyAppController
{
    public function __construct($action)
    {
        parent::__construct($action);
    }

    public function index()
    {
        $searchForm = $this->getShopSearchForm($this->siteLangId);
        $this->set('searchForm', $searchForm);
        $this->_template->addJs('js/slick.min.js');
        $this->set('geoLocation', FatApp::getConfig('CONF_ENABLE_GEO_LOCATION', FatUtility::VAR_INT, 0) && !empty(FatApp::getConfig('CONF_GOOGLEMAP_API_KEY', FatUtility::VAR_STRING, '')));
        $this->_template->render();
    }

    public function featured()
    {
        $searchForm = $this->getShopSearchForm($this->siteLangId);
        $params['featured'] = 1;
        $searchForm->fill($params);
        $this->set('searchForm', $searchForm);
        $this->_template->addJs('js/slick.min.js');
        $this->set('geoLocation', FatApp::getConfig('CONF_ENABLE_GEO_LOCATION', FatUtility::VAR_INT, 0) && !empty(FatApp::getConfig('CONF_GOOGLEMAP_API_KEY', FatUtility::VAR_STRING, '')));
        $this->_template->render();
    }

    public function search()
    {
        $db = FatApp::getDb();
        $data = FatApp::getPostedData();
        $page = (empty($data['page']) || FatUtility::int($data['page']) <= 0) ? 1 : FatUtility::int($data['page']);
        $pageSize = FatApp::getPostedData('pageSize', FatUtility::VAR_INT);
        if (!in_array($pageSize, FilterHelper::getPageSizeValues())) {
            $pageSize = FatApp::getConfig('CONF_ITEMS_PER_PAGE_CATALOG', FatUtility::VAR_INT, 10);
        }

        $searchForm = $this->getShopSearchForm($this->siteLangId);
        $post = $searchForm->getFormDataFromArray($data);
        $post['page'] = $page;
        $post['pageSize'] = $pageSize;

        /* SubQuery, Shop have products[ */
        $prodShopSrch = new ProductSearch(0);
        $prodShopSrch->addMultipleFields(array('distinct(shop_id)'));
        $prodShopSrch->setGeoAddress();
        $prodShopSrch->setDefinedCriteria(0, 0, ['doNotJoinSpecialPrice' => true, 'doNotJoinSellers' => true, 'doNotJoinShippingPkg' => true]);
        $prodShopSrch->validateAndJoinDeliveryLocation();
        $prodShopSrch->joinProductToCategory();
        $prodShopSrch->doNotCalculateRecords();
        $prodShopSrch->doNotLimitRecords();

        $srch = new ShopSearch($this->siteLangId);
        $srch->setDefinedCriteria($this->siteLangId);
        $srch->joinTable('(' . $prodShopSrch->getQuery() . ')', 'INNER JOIN', 'temp.shop_id = s.shop_id', 'temp');

        $flds = [
            's.shop_id',
            'shop_user_id',
            'shop_ltemplate_id',
            'shop_created_on',
            'IFNULL(shop_name, shop_identifier) as shop_name',
            'shop_description',
            'shop_country_l.country_name as country_name',
            'shop_state_l.state_name as state_name',
            'shop_city',
            'shop_updated_on'
        ];
        $srch->addMultipleFields($flds);

        if (FatApp::getConfig('CONF_ENABLE_GEO_LOCATION', FatUtility::VAR_INT, 0) && !empty(FatApp::getConfig('CONF_GOOGLEMAP_API_KEY', FatUtility::VAR_STRING, ''))) {
            $srch->addMultipleFields(['shop_lat', 'shop_lng']);
        }

        $featured = FatApp::getPostedData('featured', FatUtility::VAR_INT, 0);
        if (0 < $featured) {
            $srch->addCondition('shop_featured', '=', $featured);
        }

        $srch->addGroupBy('s.shop_id');
        $removeFlds = array_diff($flds, ['s.shop_id']);
        $this->setRecordCount(clone $srch, $post['pageSize'], $post['page'], $post, true, $removeFlds);
        $srch->doNotCalculateRecords();

        $srch->setPageNumber($page);
        $srch->setPageSize($pageSize);
        $srch->addOrder('shop_created_on');
        $shopRs = $srch->getResultSet();
        $allShops = $db->fetchAll($shopRs, 'shop_id');
        $totalProdCountToDisplay = 4;

        $productSrchObj = new ProductSearch($this->siteLangId);
        $productSrchObj->setGeoAddress();
        $productSrchObj->joinProductToCategory($this->siteLangId);
        $productSrchObj->joinProductToTax();
        $productSrchObj->doNotCalculateRecords();
        $productSrchObj->setDefinedCriteria(0, 0, ['doNotJoinSellers' => true, 'doNotJoinShippingPkg' => true]);
        /* $productSrchObj->joinSellerSubscription($this->siteLangId, true);
        $productSrchObj->addSubscriptionValidCondition(); */
        $productSrchObj->validateAndJoinDeliveryLocation();

        $productSrchObj->addCondition('selprod_deleted', '=', applicationConstants::NO);
        $productSrchObj->addMultipleFields(
            array(
                'product_id',
                'selprod_id',
                'IFNULL(product_name, product_identifier) as product_name',
                'IFNULL(selprod_title  ,IFNULL(product_name, product_identifier)) as selprod_title',
                'product_updated_on',
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
                'selprod_sold_count',
                'IF(selprod_stock > 0, 1, 0) AS in_stock',
                'selprod_cart_type',
                'selprod_hide_price',
                'shop_rfq_enabled',
                'product_type'
            )
        );
        foreach ($allShops as $val) {
            if (false == MOBILE_APP_API_CALL) {
                $productShopSrchTempObj = clone $productSrchObj;
                $productShopSrchTempObj->addCondition('selprod_user_id', '=', $val['shop_user_id']);
                $productShopSrchTempObj->addOrder('in_stock', 'DESC');
                $productShopSrchTempObj->addOrder('availableInLocation', 'DESC');
                $productShopSrchTempObj->addGroupBy('selprod_product_id');
                $productShopSrchTempObj->setPageSize(4);
                $Prs = $productShopSrchTempObj->getResultSet();
                $allShops[$val['shop_id']]['products'] = $db->fetchAll($Prs);
                $allShops[$val['shop_id']]['totalProducts'] = $productShopSrchTempObj->recordCount();
            } else {
                $allShops[$val['shop_id']]['products'] = [];
                $allShops[$val['shop_id']]['totalProducts'] = 0;
            }

            $allShops[$val['shop_id']]['shopRating'] = 0;
            if (FatApp::getConfig("CONF_ALLOW_REVIEWS", FatUtility::VAR_INT, 0)) {
                $allShops[$val['shop_id']]['shopRating'] = SelProdRating::getSellerRating($val['shop_user_id'], true);
            }
            $allShops[$val['shop_id']]['shopTotalReviews'] = SelProdReview::getSellerTotalReviews($val['shop_user_id'], true);
            $uploadedTime = AttachedFile::setTimeParam($val['shop_updated_on']);
            $allShops[$val['shop_id']]['shop_logo'] = UrlHelper::generateFullUrl('image', 'shopLogo', [$val['shop_id'], $this->siteLangId, 'SMALL']) . $uploadedTime;

            $selProdIdsArr = array_column($allShops[$val['shop_id']]['products'], 'selprod_id');
            $allShops[$val['shop_id']]['tRightRibbons'] = Badge::getRibbons($this->siteLangId, Badge::RIBB_POS_TRIGHT, $selProdIdsArr);
        }
        $this->set('allShops', $allShops);
        $this->set('totalProdCountToDisplay', $totalProdCountToDisplay);
        $this->set('postedData', $post);
        $this->set('pageSizeArr', FilterHelper::getPageSizeArr($this->siteLangId));

        $startRecord = ($page - 1) * $pageSize + 1;
        $endRecord = $pageSize;
        $totalRecords = $post['pageRecordCount'];
        if ($totalRecords < $endRecord) {
            $endRecord = $totalRecords;
        }
        $json['totalRecords'] = $totalRecords;
        $json['startRecord'] = $startRecord;
        $json['endRecord'] = $endRecord;

        if (true === MOBILE_APP_API_CALL) {
            $this->_template->render();
        }
        if (isset($data['viewType']) && $data['viewType'] == 'popup') {
            $json['html'] = $this->_template->render(false, false, 'shops/search-map-view.php', true, false);
        } elseif (isset($data['viewType']) && $data['viewType'] == 'popupShops') {
            $json['html'] = $this->_template->render(false, false, 'shops/search-list-map-view.php', true, false);
        } else {
            $json['html'] = $this->_template->render(false, false, 'shops/search.php', true, false);
            $json['loadMoreBtnHtml'] = $this->_template->render(false, false, '_partial/load-more-btn.php', true, false);
        }
        FatUtility::dieJsonSuccess($json);
    }

    private function getShopSearchForm()
    {
        $frm = new Form('frmSearchShops');
        $frm->addHiddenField('', 'featured', 0);
        $frm->addHiddenField('', 'pageRecordCount');
        return $frm;
    }

    protected function getSearchForm()
    {
        return Shop::getFilterSearchForm();
    }

    public function view($shop_id)
    {
        $this->shopDetail($shop_id);

        if (true === MOBILE_APP_API_CALL) {
            $get = FatApp::getPostedData();
        } else {
            $get = FatApp::getParameters();
            $get = array_filter(Product::convertArrToSrchFiltersAssocArr($get));
        }
        $viewType = FatApp::getPostedData('viewType', FatUtility::VAR_STRING, '');
        if (array_key_exists('currency', $get)) {
            $get['currency_id'] = $get['currency'];
        }
        if (array_key_exists('sort', $get)) {
            $get['sortOrder'] = $get['sort'];
        }

        $includeShopData = true;
        if (array_key_exists('includeShopData', $get) && 1 > FatUtility::int($get['includeShopData'])) {
            $includeShopData = false;
        }

        $get['shop_id'] = $shop_id;

        $data = $this->getListingData($get, $includeShopData);

        $selProdIdsArr = array_column($data['products'], 'selprod_id');
        $data['tRightRibbons'] = Badge::getRibbons($this->siteLangId, Badge::RIBB_POS_TRIGHT, $selProdIdsArr);

        if (false === MOBILE_APP_API_CALL) {
            $frm = $this->getProductSearchForm();
            $frm->fill($data['postedData']);

            $arr = array(
                'frmProductSearch' => $frm,
                'canonicalUrl' => UrlHelper::generateFullUrl('Shops', 'view', array($shop_id)),
                'productSearchPageType' => SavedSearchProduct::PAGE_SHOP,
                'recordId' => $shop_id,
                'viewType' => $viewType,
                'bannerListigUrl' => UrlHelper::generateFullUrl('Banner', 'categories'),
                'pageSizeArr' => FilterHelper::getPageSizeArr($this->siteLangId),
            );
            $data = array_merge($data, $arr);

            if (FatUtility::isAjaxCall() && $viewType != 'popupProduct' && $viewType != 'popup') {
                $this->set('products', $data['products']);
                $this->set('page', $data['page']);
                $this->set('pageCount', $data['pageCount']);
                $this->set('postedData', $get);
                $this->set('recordCount', $data['recordCount']);
                $this->set('siteLangId', $this->siteLangId);
                $this->set('pageSize', $data['pageSize']);
                $this->set('pageSizeArr', $data['pageSizeArr']);
                $this->set('tRightRibbons', $data['tRightRibbons']);
                echo $this->_template->render(false, false, 'products/products-list.php', true);
                exit;
            }
            if (FatUtility::isAjaxCall() && $viewType == 'popupProduct') {
                $this->set('products', $data['products']);
                $this->set('postedData', $get);
                $this->set('siteLangId', $this->siteLangId);
                $this->set('pageSizeArr', $data['pageSizeArr']);
                $this->set('tRightRibbons', $data['tRightRibbons']);
                echo $this->_template->render(false, false, 'products/products-map-list-left.php', true);
                exit;
            }

            if (FatUtility::isAjaxCall() && $viewType == 'popup') {
                $this->set('products', $data['products']);
                $this->set('postedData', $get);
                $this->set('siteLangId', $this->siteLangId);
                $this->set('pageSizeArr', $data['pageSizeArr']);
                $this->set('tRightRibbons', $data['tRightRibbons']);
                $this->_template->render(false, false, 'products/listing-map-page.php');
                exit;
            }


            $this->includeProductPageJsCss();
            $this->_template->addJs(['js/slick.min.js', 'js/shop-nav.js', 'js/jquery.colourbrightness.min.js', 'js/slick-carousels.js']);
        }

        if (true === MOBILE_APP_API_CALL && true === $includeShopData) {
            $shopInfo = $this->shopPoliciesData($this->getShopInfo($shop_id));
            $data['shop'] = array_merge($data['shop'], $shopInfo);
            $data['shop']['rating'] = 0;
            if (FatApp::getConfig("CONF_ALLOW_REVIEWS", FatUtility::VAR_INT, 0)) {
                $data['shop']['rating'] = SelProdRating::getSellerRating($data['shop']['shop_user_id'], true);
            }
            $data['shop']['shop_logo'] = UrlHelper::generateFullUrl('image', 'shopLogo', array($data['shop']['shop_id'], $this->siteLangId));
            $data['shop']['shop_banner'] = UrlHelper::getCachedUrl(UrlHelper::generateFullFileUrl('image', 'shopBanner', array($data['shop']['shop_id'], $this->siteLangId, ImageDimension::VIEW_MOBILE, 0, applicationConstants::SCREEN_MOBILE)), CONF_IMG_CACHE_TIME, '.jpg');
        }

        /* Shop and SelProd Badge */
        if (true === MOBILE_APP_API_CALL) {
            $shopBadgesArr = Badge::getShopBadges($this->siteLangId, [$shop_id]);
            $data['shop']['badges'] = [];
            foreach ($shopBadgesArr as $bdgRow) {
                $icon = AttachedFile::getAttachment(AttachedFile::FILETYPE_BADGE, $bdgRow[BadgeLinkCondition::DB_TBL_PREFIX . 'badge_id'], 0, $this->siteLangId);
                $uploadedTime = AttachedFile::setTimeParam($icon['afile_updated_at']);
                $url = UrlHelper::getCachedUrl(UrlHelper::generateFullFileUrl('Image', 'badgeIcon', array($icon['afile_record_id'], $this->siteLangId, ImageDimension::VIEW_MINI, $icon['afile_screen']), CONF_WEBROOT_FRONT_URL) . $uploadedTime, CONF_IMG_CACHE_TIME, '.jpg');
                $data['shop']['badges'][] = [
                    'url' => $url,
                    Badge::DB_TBL_PREFIX . 'name' => $bdgRow[Badge::DB_TBL_PREFIX . 'name'],
                ];
            }
        }
        /* Shop and SelProd Badge */
        $data['pageTitle'] = Labels::getLabel('LBL_SHOP_PRODUCTS', $this->siteLangId);
        $this->set('data', $data);

        if (false === MOBILE_APP_API_CALL) {
            $this->includeProductPageJsCss();
            $this->_template->addJs(array('js/slick.min.js', 'js/shop-nav.js', 'js/jquery.colourbrightness.min.js', 'js/slick-carousels.js'));
        }

        $this->set('showBanner', true);
        $this->_template->render();
    }

    public function showBackgroundImage($shop_id = 0, $lang_id = 0, $templateId = '')
    {
        $recordId = FatUtility::int($shop_id);
        $lang_id = FatUtility::int($lang_id);
        $file_row = AttachedFile::getAttachment(AttachedFile::FILETYPE_SHOP_BACKGROUND_IMAGE, $recordId, 0, $lang_id);
        if (!$file_row && !$this->getAllowedShowBg($templateId)) {
            return false;
        }

        return true;
    }

    public function shopDetail($shop_id, $policy = false)
    {
        $db = FatApp::getDb();

        $shop_id = FatUtility::int($shop_id);

        if ($shop_id <= 0) {
            FatApp::redirectUser(UrlHelper::generateUrl('Seller', 'Shop'));
        }

        $shopDetails = Shop::getAttributesByid($shop_id);
        if (UserAuthentication::isUserLogged() && UserAuthentication::getLoggedUserId() == $shopDetails['shop_user_id'] && !UserPrivilege::isUserHasValidSubsription(UserAuthentication::getLoggedUserId())) {
            Message::addInfo(Labels::getLabel("MSG_PLEASE_BUY_SUBSCRIPTION", $this->siteLangId));
            FatApp::redirectUser(UrlHelper::generateUrl('Seller', 'Packages'));
        }

        $srch = new ShopSearch($this->siteLangId);
        $srch->setDefinedCriteria($this->siteLangId);        
        $srch->doNotCalculateRecords();
        $srch->joinTable('tbl_users', 'LEFT OUTER JOIN', 'tu.user_id = shop_user_id', 'tu');
        $loggedUserId = 0;
        if (UserAuthentication::isUserLogged()) {
            $loggedUserId = UserAuthentication::getLoggedUserId();
        }

        /* sub query to find out that logged user have marked current shop as favorite or not[ */
        $favSrchObj = new UserFavoriteShopSearch();
        $favSrchObj->doNotCalculateRecords();
        $favSrchObj->doNotLimitRecords();
        $favSrchObj->addMultipleFields(array('ufs_shop_id', 'ufs_id'));
        $favSrchObj->addCondition('ufs_user_id', '=', $loggedUserId);
        $favSrchObj->addCondition('ufs_shop_id', '=', $shop_id);
        $srch->joinTable('(' . $favSrchObj->getQuery() . ')', 'LEFT OUTER JOIN', 'ufs_shop_id = shop_id', 'ufs');
        /* ] */

        $srch->addMultipleFields(
            array(
                'shop_id',
                'tu.user_name',
                'tu.user_regdate',
                'shop_user_id',
                'shop_ltemplate_id',
                'shop_created_on',
                'shop_name',
                'shop_description',
                'shop_country_l.country_name as shop_country_name',
                'shop_state_l.state_name as shop_state_name',
                'shop_city',
                'IFNULL(ufs.ufs_id, 0) as is_favorite',
                'u_cred.credential_username as shop_owner_username',
                'u.user_name as shop_owner_name',
            )
        );
        $srch->addCondition('shop_id', '=', $shop_id);
        if ($policy) {
            $srch->addMultipleFields(array('shop_payment_policy', 'shop_delivery_policy', 'shop_refund_policy', 'shop_additional_info', 'shop_seller_info'));
        }
        $shopRs = $srch->getResultSet();
        $shop = $db->fetch($shopRs);

        if (!$shop) {
            FatUtility::exitWithErrorCode('404');
        }

        $this->set('shop', $this->shopPoliciesData($shop));
        $shopRating = 0;
        if (FatApp::getConfig("CONF_ALLOW_REVIEWS", FatUtility::VAR_INT, 0)) {
            $shopRating = SelProdRating::getSellerRating($shop['shop_user_id'], true);
        }
        $this->set('shopRating', $shopRating);
        $this->set('shopTotalReviews', SelProdReview::getSellerTotalReviews($shop['shop_user_id'], true));

        $description = trim(CommonHelper::subStringByWords(strip_tags(CommonHelper::renderHtml($shop['shop_description'], true)), 500));
        $description .= ' - ' . Labels::getLabel('MSG_SEE_MORE_AT', $this->siteLangId) . ": " . UrlHelper::getCurrUrl();

        if ($shop) {
            $socialShareContent = array(
                'title' => $shop['shop_name'],
                'description' => $description,
                'image' => UrlHelper::generateFullUrl('image', 'shopLogo', array($shop['shop_id'], $this->siteLangId)),
            );
            $this->set('socialShareContent', $socialShareContent);
        }

        $shopUserId = FatUtility::int($shop['shop_user_id']);
        if ($shopUserId !== 0) {
            $srchSplat = SocialPlatform::getSearchObject($this->siteLangId);
            $srchSplat->doNotCalculateRecords();
            $srchSplat->doNotLimitRecords();
            $srchSplat->addCondition('splatform_user_id', '=', $shopUserId);
            $rs = $srchSplat->getResultSet();
            $socialPlatforms = FatApp::getDb()->fetchAll($rs);
            $this->set('socialPlatforms', $socialPlatforms);
        }

        $collection_data = ShopCollection::getShopCollectionsDetail($shop_id, $this->siteLangId);
        $this->set('collectionData', $collection_data);
        $this->set('layoutTemplate', 'shop');
        // $this->set('template_id', ($shop['shop_ltemplate_id']==0)?SHOP::TEMPLATE_ONE:$shop['shop_ltemplate_id']);
        $this->set('template_id', SHOP::TEMPLATE_ONE);
        $showBgImage = $this->showBackgroundImage($shop_id, $this->siteLangId, SHOP::TEMPLATE_ONE);
        $this->set('showBgImage', $showBgImage);
        if (UserAuthentication::isUserLogged()) {
            $userParent = User::getAttributesById(UserAuthentication::getLoggedUserId(), 'user_parent');
            $userParentId = (0 < $userParent) ? $userParent : UserAuthentication::getLoggedUserId();
            $this->set('userParentId', $userParentId);
        }
    }

    public function getShopCollectionListing($shop_id)
    {
        $shop_id = FatUtility::int($shop_id);
        if (1 > $shop_id) {
            LibHelper::dieJsonError(Labels::getLabel('ERR_INVALID_SHOP', $this->siteLangId));
        }
        $collectionData = ShopCollection::getShopCollectionsDetail($shop_id, $this->siteLangId);
        if (!empty($collectionData)) {
            foreach ($collectionData as $key => $collection) {
                $collectionData[$key]['shopCollectionImage'] = UrlHelper::generateFullUrl('Image', 'shopCollectionImage', array($collection['scollection_id'], $this->siteLangId, 'SHOP'));
            }
        }

        $this->set('data', ['shopCollectionDetail' => $collectionData]);
        $this->_template->render();
    }

    public function getAllowedShowBg($templateId = '')
    {
        switch ($templateId) {
            case Shop::TEMPLATE_ONE:
            case Shop::TEMPLATE_TWO:
            case Shop::TEMPLATE_THREE:
                return false;
                break;
            case Shop::TEMPLATE_FOUR:
            case Shop::TEMPLATE_FIVE:
                return true;
                break;
            default:
                return false;
                break;
        }
    }

    public function topProducts($shop_id)
    {
        $db = FatApp::getDb();

        $this->shopDetail($shop_id);

        $frm = $this->getProductSearchForm();

        $get = FatApp::getParameters();
        $get = Product::convertArrToSrchFiltersAssocArr($get);

        if (array_key_exists('currency', $get)) {
            $get['currency_id'] = $get['currency'];
        }
        if (array_key_exists('sort', $get)) {
            $get['sortOrder'] = $get['sort'];
        }

        $get['top_products'] = 1;
        $get['shop_id'] = $shop_id;


        $data = $this->getListingData($get);
        $frm->fill($data['postedData']);

        $arr = array(
            'frmProductSearch' => $frm,
            'canonicalUrl' => UrlHelper::generateFullUrl('Shops', 'topProducts', array($shop_id)),
            'productSearchPageType' => SavedSearchProduct::PAGE_SHOP,
            'recordId' => $shop_id,
            'bannerListigUrl' => UrlHelper::generateFullUrl('Banner', 'categories'),
            'pageSizeArr' => FilterHelper::getPageSizeArr($this->siteLangId)
        );

        $data = array_merge($data, $arr);

        if (FatUtility::isAjaxCall()) {
            $this->set('data', $data);
            $this->set('products', $data['products']);
            $this->set('page', $data['page']);
            $this->set('pageCount', $data['pageCount']);
            $this->set('postedData', $get);
            $this->set('recordCount', $data['recordCount']);
            $this->set('siteLangId', $this->siteLangId);
            $this->set('pageSizeArr', $data['pageSizeArr']);
            echo $this->_template->render(false, false, 'products/products-list.php', true);
            exit;
        }

        $data['pageTitle'] =  Labels::getLabel('LBL_SHOP_TOP_PRODUCTS', $this->siteLangId);

        $this->set('data', $data);

        $this->includeProductPageJsCss();

        $this->includeProductPageJsCss();
        $this->_template->addJs(array('js/slick.min.js', 'js/shop-nav.js', 'js/jquery.colourbrightness.min.js', 'js/slick-carousels.js'));
        $this->_template->render(true, true, 'shops/view.php');
    }

    public function policy($shop_id)
    {
        $this->shopDetail($shop_id, true);

        $frm = $this->getProductSearchForm();
        $searchFrm = $this->getSearchForm();
        $frmData = array('shop_id' => $shop_id);
        $frm->fill($frmData);
        $searchFrm->fill($frmData);
        $this->set('frmProductSearch', $frm);
        $this->set('searchFrm', $searchFrm);
        $this->_template->addJs('js/slick.min.js');
        $this->_template->addJs('js/shop-nav.js');
        $this->_template->addJs('js/jquery.colourbrightness.min.js');
        if (UserAuthentication::isUserLogged()) {
            $userParent = User::getAttributesById(UserAuthentication::getLoggedUserId(), 'user_parent');
            $userParentId = (0 < $userParent) ? $userParent : UserAuthentication::getLoggedUserId();
            $this->set('userParentId', $userParentId);
        }
        $this->_template->render();
    }

    public function collection($shop_id, $scollectionId)
    {
        $db = FatApp::getDb();
        $shop_id = FatUtility::int($shop_id);
        $scollectionId = FatUtility::int($scollectionId);
        if (1 > $scollectionId) {
            if (true === MOBILE_APP_API_CALL) {
                LibHelper::dieJsonError(Labels::getLabel('ERR_INVALID_REQUEST', $this->siteLangId));
            }
            FatApp::redirectUser(UrlHelper::generateUrl(''));
        }
        $this->shopDetail($shop_id);

        $shopcolDetails = ShopCollection::getCollectionGeneralDetail($shop_id, $scollectionId, $this->siteLangId);

        $frm = $this->getProductSearchForm();

        $get = FatApp::getParameters();
        $get = Product::convertArrToSrchFiltersAssocArr($get);

        if (array_key_exists('currency', $get)) {
            $get['currency_id'] = $get['currency'];
        }
        if (array_key_exists('sort', $get)) {
            $get['sortOrder'] = $get['sort'];
        }
        //$get['join_price'] = 1;
        $get['shop_id'] = $shop_id;
        $get['collection_id'] = $scollectionId;

        $fld = $frm->getField('sortBy');
        $fld->value = 'popularity_desc';
        $fld->fldType = 'hidden';

        $data = $this->getListingData($get);
        $frm->fill($data['postedData']);

        $selProdIdsArr = array_column($data['products'], 'selprod_id');
        $tRightRibbons = Badge::getRibbons($this->siteLangId, Badge::RIBB_POS_TRIGHT, $selProdIdsArr);

        $arr = array(
            'tRightRibbons' => $tRightRibbons,
            'scollection_name' => $shopcolDetails['scollection_name'],
            'canonicalUrl' => UrlHelper::generateFullUrl('Shops', 'collection', array($shop_id, $scollectionId)),
            'productSearchPageType' => SavedSearchProduct::PAGE_SHOP,
            'recordId' => $shop_id,
            'bannerListigUrl' => UrlHelper::generateFullUrl('Banner', 'categories'),
            'pageSizeArr' => FilterHelper::getPageSizeArr($this->siteLangId)
        );

        if (false === MOBILE_APP_API_CALL) {
            $arr['frmProductSearch'] = $frm;
        }

        $data = array_merge($data, $arr);

        $this->set('data', $data);

        if (FatUtility::isAjaxCall()) {
            $this->set('products', $data['products']);
            $this->set('page', $data['page']);
            $this->set('pageCount', $data['pageCount']);
            $this->set('postedData', $get);
            $this->set('recordCount', $data['recordCount']);
            $this->set('siteLangId', $this->siteLangId);
            $this->set('pageSizeArr', $data['pageSizeArr']);
            echo $this->_template->render(false, false, 'products/products-list.php', true);
            exit;
        }

        if (false === MOBILE_APP_API_CALL) {
            $this->includeProductPageJsCss();
            $this->_template->addJs(['js/slick.min.js', 'js/shop-nav.js', 'js/jquery.colourbrightness.min.js', 'js/slick-carousels.js']);
        }
        $this->_template->render(true, true, 'shops/view.php');
    }

    public function sendMessage($shop_id, $selprod_id = 0)
    {
        UserAuthentication::checkLogin();
        $shop_id = FatUtility::int($shop_id);
        $selprod_id = FatUtility::int($selprod_id);
        $loggedUserId = UserAuthentication::getLoggedUserId();
        $db = FatApp::getDb();

        $shop = $this->getShopInfo($shop_id);
        if (!$shop) {
            Message::addErrorMessage(Labels::getLabel('ERR_INVALID_REQUEST', $this->siteLangId));
            FatApp::redirectUser(UrlHelper::generateUrl('Home'));
        }

        $frm = $this->getSendMessageForm($this->siteLangId);
        $userObj = new User($loggedUserId);
        $loggedUserData = $userObj->getUserInfo(array('user_id', 'user_name', 'credential_username'), false, false, true);
        $frmData = array('shop_id' => $shop_id);

        if ($selprod_id > 0) {
            $frmData['product_id'] = $selprod_id;
            $srch = SellerProduct::getSearchObject($this->siteLangId);
            $srch->joinTable(Product::DB_TBL, 'INNER JOIN', 'p.product_id = sp.selprod_product_id', 'p');
            $srch->joinTable(Product::DB_TBL_LANG, 'LEFT OUTER JOIN', 'p.product_id = p_l.productlang_product_id AND p_l.productlang_lang_id = ' . $this->siteLangId, 'p_l');
            $srch->addMultipleFields(array('IFNULL(selprod_title  ,IFNULL(product_name, product_identifier)) as selprod_title'));
            $srch->addCondition('selprod_id', '=', $selprod_id);
            $srch->doNotCalculateRecords();
            $srch->setPageSize(1);
            $db = FatApp::getDb();
            $rs = $srch->getResultSet();
            $products = $db->fetch($rs);
            $this->set('product', $products);
        }
        $this->shopDetail($shop_id, true);

        $frm->fill($frmData);
        $this->set('frm', $frm);
        $this->set('loggedUserData', $loggedUserData);
        //$this->set('shop', $shop);
        $this->_template->render();
    }

    public function setUpSendMessage()
    {
        UserAuthentication::checkLogin();
        $frm = $this->getSendMessageForm($this->siteLangId);
        $post = $frm->getFormDataFromArray(FatApp::getPostedData());
        $loggedUserId = UserAuthentication::getLoggedUserId();
        if (false == $post) {
            LibHelper::dieJsonError(current($frm->getValidationErrors()));
        }

        $shop_id = FatUtility::int($post['shop_id']);
        $shopData = $this->getShopInfo($shop_id);
        if (!$shopData) {
            $message = Labels::getLabel('ERR_INVALID_SHOP', $this->siteLangId);
            FatUtility::dieJsonError($message);
        }

        if ($shopData['shop_user_id'] == $loggedUserId || !User::isBuyer()) {
            $message = Labels::getLabel('ERR_YOU_ARE_NOT_ALLOWED_TO_SEND_MESSAGE', $this->siteLangId);
            FatUtility::dieJsonError($message);
        }

        $threadObj = new Thread();
        $threadDataToSave = array(
            'thread_subject' => htmlentities($post['thread_subject']),
            'thread_started_by' => $loggedUserId,
            'thread_start_date' => date('Y-m-d H:i:s')
        );

        if (isset($post['product_id']) && $post['product_id'] > 0) {
            $product_id = FatUtility::int($post['product_id']);
            $threadDataToSave['thread_type'] = Thread::THREAD_TYPE_PRODUCT;
            $threadDataToSave['thread_record_id'] = $product_id;
        } else {
            $threadDataToSave['thread_type'] = Thread::THREAD_TYPE_SHOP;
            $threadDataToSave['thread_record_id'] = $shop_id;
        }

        $threadObj->assignValues($threadDataToSave);

        if (!$threadObj->save()) {
            $message = Labels::getLabel($threadObj->getError(), $this->siteLangId);
            if (true === MOBILE_APP_API_CALL) {
                FatUtility::dieJsonError($message);
            }
            Message::addErrorMessage($message);
            FatUtility::dieWithError(Message::getHtml());
        }
        $thread_id = $threadObj->getMainTableRecordId();

        $threadMsgDataToSave = array(
            'message_thread_id' => $thread_id,
            'message_from' => $loggedUserId,
            'message_to' => $shopData['shop_user_id'],
            'message_text' => $post['message_text'],
            'message_date' => date('Y-m-d H:i:s'),
            'message_is_unread' => 1,
            'message_deleted' => 0
        );
        if (!$message_id = $threadObj->addThreadMessages($threadMsgDataToSave)) {
            $message = Labels::getLabel($threadObj->getError(), $this->siteLangId);
            if (true === MOBILE_APP_API_CALL) {
                FatUtility::dieJsonError($message);
            }
            Message::addErrorMessage($message);
            FatUtility::dieWithError(Message::getHtml());
        }

        if ($message_id) {
            $emailObj = new EmailHandler();
            if (!$emailObj->SendMessageNotification($message_id, $this->siteLangId)) {
                LibHelper::dieJsonError($emailObj->getError());
            }
        }
        $this->set('msg', Labels::getLabel('MSG_MESSAGE_SUBMITTED_SUCCESSFULLY!', $this->siteLangId));
        if (true === MOBILE_APP_API_CALL) {
            $this->_template->render();
        }
        $this->_template->render(false, false, 'json-success.php');
    }

    public function reportSpam($shop_id)
    {
        UserAuthentication::checkLogin();
        $db = FatApp::getDb();
        $shop_id = FatUtility::int($shop_id);

        $shop = $this->getShopInfo($shop_id);
        if (!$shop) {
            Message::addErrorMessage(Labels::getLabel('ERR_INVALID_REQUEST', $this->siteLangId));
            FatApp::redirectUser(UrlHelper::generateUrl('Home'));
        }

        $shopRepData = ShopReport::getReportDetail($shop['shop_id'], UserAuthentication::getLoggedUserId(), 'sreport_id');
        if (!empty($shopRepData)) {
            Message::addErrorMessage(Labels::getLabel('ERR_YOU_ALREADY_REPORTED_FOR_THIS_SHOP', $this->siteLangId));
            FatApp::redirectUser(UrlHelper::generateUrl('Shops', 'View', array($shop_id)));
        }

        $this->shopDetail($shop_id, true);

        $frm = $this->getReportSpamForm($this->siteLangId);
        $frm->fill(array('shop_id' => $shop_id));
        $this->set('frm', $frm);
        $this->set('template_id', SHOP::TEMPLATE_ONE);
        $this->_template->render();
    }

    public function setUpShopSpam()
    {
        UserAuthentication::checkLogin();
        $frm = $this->getReportSpamForm($this->siteLangId);
        $post = $frm->getFormDataFromArray(FatApp::getPostedData());
        $loggedUserId = UserAuthentication::getLoggedUserId();

        if (false == $post) {
            LibHelper::dieJsonError(current($frm->getValidationErrors()));
        }

        $shop_id = FatUtility::int($post['shop_id']);
        if (1 > $shop_id) {
            LibHelper::dieJsonError(Labels::getLabel('ERR_INVALID_SHOP', $this->siteLangId));
        }

        $shopRepData = ShopReport::getReportDetail($shop_id, $loggedUserId, 'sreport_id');
        if (!empty($shopRepData) && 0 < count($shopRepData)) {
            LibHelper::dieJsonError(Labels::getLabel('ERR_YOU_ALREADY_REPORTED_FOR_THIS_SHOP', $this->siteLangId));
        }

        $srch = new ShopSearch($this->siteLangId);
        $srch->setDefinedCriteria($this->siteLangId);
        $srch->joinSellerSubscription();
        $srch->doNotCalculateRecords();
        $srch->setPageSize(1);
        $srch->addMultipleFields(array('shop_id', 'shop_user_id'));
        $srch->addCondition('shop_id', '=', $shop_id);
        $shopRs = $srch->getResultSet();
        $shopData = FatApp::getDb()->fetch($shopRs);

        if (!$shopData) {
            LibHelper::dieJsonError(Labels::getLabel('ERR_INVALID_SHOP', $this->siteLangId));
        }

        $sReportObj = new ShopReport();
        $dataToSave = array(
            'sreport_shop_id' => $shop_id,
            'sreport_reportreason_id' => $post['sreport_reportreason_id'],
            'sreport_message' => $post['sreport_message'],
            'sreport_user_id' => $loggedUserId,
            'sreport_added_on' => date('Y-m-d H:i:s'),
        );

        $sReportObj->assignValues($dataToSave);
        if (!$sReportObj->save()) {
            FatUtility::dieJsonError(strip_tags(Labels::getLabel($sReportObj->getError(), $this->siteLangId)));
        }

        $sreport_id = $sReportObj->getMainTableRecordId();

        if (!$sreport_id) {
            FatUtility::dieJsonError(Labels::getLabel('ERR_INVALID_REQUEST', $this->siteLangId));
        }

        /* email notification[ */
        if ($sreport_id) {
            $emailObj = new EmailHandler();
            $emailObj->sendShopReportNotification($sreport_id, $this->siteLangId);

            //send notification to admin
            $notificationData = array(
                'notification_record_type' => Notification::TYPE_SHOP,
                'notification_record_id' => $sreport_id,
                'notification_user_id' => $loggedUserId,
                'notification_label_key' => Notification::REPORT_SHOP_NOTIFICATION,
                'notification_added_on' => date('Y-m-d H:i:s'),
            );

            if (!Notification::saveNotifications($notificationData)) {
                FatUtility::dieJsonError(Labels::getLabel("ERR_NOTIFICATION_COULD_NOT_BE_SENT", $this->siteLangId));
            }
        }
        /* ] */

        $sucessMsg = Labels::getLabel('MSG_REPORTED_SUCCESSFULLY!', $this->siteLangId);
        Message::addMessage($sucessMsg);
        $this->set('msg', $sucessMsg);
        if (true === MOBILE_APP_API_CALL) {
            $this->_template->render();
        }
        $this->set('redirectUri', UrlHelper::generateUrl('Shops', 'View', [$shop_id]));
        $this->_template->render(false, false, 'json-success.php');
    }

    public function policies($shop_id)
    {
        $shop = $this->getShopInfo($shop_id);
        if (!$shop) {
            Message::addErrorMessage(Labels::getLabel('ERR_INVALID_REQUEST', $this->siteLangId));
            FatApp::redirectUser(UrlHelper::generateUrl('Home'));
        }

        $this->set('shop', $shop);
        $this->_template->render();
    }
    private function shopPoliciesData($shop)
    {
        $shop['description'] = empty($shop['shop_description']) ? (object) array() : array(
            'title' => Labels::getLabel('MSG_Shop_Description', $this->siteLangId),
            'description' => $shop['shop_description'],
        );
        $shop['shop_payment_policy'] = empty($shop['shop_payment_policy']) ? (object) array() : array(
            'title' => Labels::getLabel('MSG_PAYMENT_POLICY', $this->siteLangId),
            'description' => $shop['shop_payment_policy'],
        );
        $shop['shop_delivery_policy'] = empty($shop['shop_delivery_policy']) ? (object) array() : array(
            'title' => Labels::getLabel('MSG_DELIVERY_POLICY', $this->siteLangId),
            'description' => $shop['shop_delivery_policy'],
        );
        $shop['shop_refund_policy'] = empty($shop['shop_refund_policy']) ? (object) array() : array(
            'title' => Labels::getLabel('MSG_REFUND_POLICY', $this->siteLangId),
            'description' => $shop['shop_refund_policy'],
        );
        $shop['shop_additional_info'] = empty($shop['shop_additional_info']) ? (object) array() : array(
            'title' => Labels::getLabel('MSG_ADDITIONAL_INFO', $this->siteLangId),
            'description' => $shop['shop_additional_info'],
        );
        $shop['shop_seller_info'] = empty($shop['shop_seller_info']) ? (object) array() : array(
            'title' => Labels::getLabel('MSG_SELLER_INFO', $this->siteLangId),
            'description' => $shop['shop_seller_info'],
        );
        return $shop;
    }

    public function banner($shopId, $sizeType = '', $prodCatId = 0, $lang_id = 0)
    {
        $shopId = FatUtility::int($shopId);
        $prodCatId = FatUtility::int($prodCatId);
        $file_row = false;

        if ($prodCatId > 0) {
            $file_row = AttachedFile::getAttachment(AttachedFile::FILETYPE_CATEGORY_BANNER_SELLER, $shopId, $prodCatId, $lang_id);
        }

        if (false == $file_row) {
            $file_row = AttachedFile::getAttachment(AttachedFile::FILETYPE_SHOP_BANNER, $shopId, 0, $lang_id);
        }

        $image_name = isset($file_row['afile_physical_path']) ? $file_row['afile_physical_path'] : '';
        $image_name = AttachedFile::setNamePrefix($image_name, $sizeType);

        $imageDimensions = ImageDimension::getData(ImageDimension::TYPE_SHOP_BANNER, $sizeType);
        $default_image = 'banner-default-image.png';
        if ($sizeType) {
            AttachedFile::displayImage($image_name, $imageDimensions['width'], $imageDimensions['height'], $default_image);
        } else {
            AttachedFile::displayOriginalImage($image_name, $default_image);
        }
    }

    private function getShopInfo($shop_id)
    {
        $db = FatApp::getDb();
        $shop_id = FatUtility::int($shop_id);
        $srch = new ShopSearch($this->siteLangId);
        $srch->setDefinedCriteria($this->siteLangId);
        $srch->doNotCalculateRecords();
        $srch->joinSellerSubscription();
        $srch->addMultipleFields(
            array(
                'shop_id',
                'shop_user_id',
                'shop_ltemplate_id',
                'shop_created_on',
                'shop_name',
                'shop_description',
                'shop_payment_policy',
                'shop_delivery_policy',
                'shop_refund_policy',
                'shop_additional_info',
                'shop_seller_info',
                'shop_country_l.country_name as shop_country_name',
                'shop_state_l.state_name as shop_state_name',
                'shop_city',
                'u.user_name as shop_owner_name',
                'u.user_regdate',
                'u_cred.credential_username as shop_owner_username'
            )
        );

        $srch->addCondition('shop_id', '=', $shop_id);
        $shopRs = $srch->getResultSet();
        return (array) $db->fetch($shopRs);
    }

    private function getReportSpamForm($langId)
    {
        $frm = new Form('frmShopReportSpam');
        $frm->addHiddenField('', 'shop_id');
        $frm->addSelectBox(Labels::getLabel('FRM_SELECT_REASON', $langId), 'sreport_reportreason_id', ShopReportReason::getReportReasonArr($langId), '', array(), Labels::getLabel('FRM_SELECT', $langId))->requirements()->setRequired();
        $frm->addTextArea(Labels::getLabel('FRM_MESSAGE', $langId), 'sreport_message')->requirements()->setRequired();
        $frm->addSubmitButton('', 'btn_submit', Labels::getLabel('BTN_SUBMIT', $langId));
        return $frm;
    }

    private function getSendMessageForm($langId)
    {
        $frm = new Form('frmSendMessage');
        //$frm->addHiddenField('', 'user_id');
        $frm->addHiddenField('', 'shop_id');

        $fld = $frm->addHtml(Labels::getLabel('FRM_FROM', $langId), 'send_message_from', '');
        $frm->addHtml(Labels::getLabel('FRM_TO', $langId), 'send_message_to', '');
        $frm->addHtml(Labels::getLabel('FRM_ABOUT_PRODUCT', $langId), 'about_product', '');
        $frm->addRequiredField(Labels::getLabel('FRM_SUBJECT', $langId), 'thread_subject');
        $fld = $frm->addTextArea(Labels::getLabel('FRM_YOUR_MESSAGE', $langId), 'message_text');
        $fld->requirements()->setRequired();
        $frm->addHiddenField('', 'product_id');
        $fldSubmit = $frm->addSubmitButton('', 'btn_submit', Labels::getLabel('BTN_SEND', $langId));
        return $frm;
    }

    public function track(int $shopId)
    {
        /* Track Click */
        $srch = new PromotionSearch($this->siteLangId, true);
        $srch->joinActiveUser();
        $srch->joinShops();
        $srch->joinShopCountry();
        $srch->joinShopState();
        $srch->addPromotionTypeCondition(Promotion::TYPE_SHOP);
        $srch->addShopActiveExpiredCondition();
        $srch->joinUserWallet();
        $srch->joinBudget();
        $srch->addBudgetCondition();
        $srch->addCondition('shop_id', '=', $shopId);
        $srch->addMultipleFields(array('shop_id', 'shop_user_id', 'shop_name', 'country_name', 'state_name', 'promotion_id', 'promotion_cpc'));
        $srch->addOrder('', 'rand()');
        $srch->setPageSize(1);
        $srch->doNotCalculateRecords();
        $rs = $srch->getResultSet();
        $row = FatApp::getDb()->fetch($rs);

        if ($row == false) {
            LibHelper::exitWithError(Labels::getLabel('ERR_INVALID_ACCESS', $this->siteLangId), false, true);
            FatApp::redirectUser(UrlHelper::generateUrl(''));
        }

        $url = UrlHelper::generateFullUrl('shops', 'view', array($shopId));
        $userId = UserAuthentication::getLoggedUserId(true);

        if (Promotion::isUserClickCountable($userId, $row['promotion_id'], $_SERVER['REMOTE_ADDR'], session_id())) {
            $promotionClickData = array(
                'pclick_promotion_id' => $row['promotion_id'],
                'pclick_user_id' => $userId,
                'pclick_datetime' => date('Y-m-d H:i:s'),
                'pclick_ip' => $_SERVER['REMOTE_ADDR'],
                'pclick_cost' => $row['promotion_cpc'],
                'pclick_session_id' => session_id(),
            );
            FatApp::getDb()->insertFromArray(Promotion::DB_TBL_CLICKS, $promotionClickData, false, [], $promotionClickData);
            $clickId = FatApp::getDb()->getInsertId();

            $promotionClickChargesData = array(
                'picharge_pclick_id' => $clickId,
                'picharge_datetime' => date('Y-m-d H:i:s'),
                'picharge_cost' => $row['promotion_cpc'],

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
            FatApp::redirectUser(CommonHelper::processURLString($url));
        }

        FatApp::redirectUser(UrlHelper::generateUrl(''));
    }


    private function getListingData($get, $includeShopData = true)
    {
        $shop_id = 0;
        if (array_key_exists('shop_id', $get)) {
            $shop_id = FatUtility::int($get['shop_id']);
        }

        $userId = 0;
        if (UserAuthentication::isUserLogged()) {
            $userId = UserAuthentication::getLoggedUserId();
        }
        $shop = array();

        if (true == $includeShopData) {
            $srch = new ShopSearch($this->siteLangId);
            $srch->setDefinedCriteria($this->siteLangId);
            $srch->joinSellerSubscription();
            $srch->doNotCalculateRecords();
            $srch->joinTable('tbl_users', 'LEFT OUTER JOIN', 'tu.user_id = shop_user_id', 'tu');

            /* sub query to find out that logged user have marked current shop as favorite or not[ */
            $favSrchObj = new UserFavoriteShopSearch();
            $favSrchObj->doNotCalculateRecords();
            $favSrchObj->doNotLimitRecords();
            $favSrchObj->addMultipleFields(array('ufs_shop_id', 'ufs_id'));
            $favSrchObj->addCondition('ufs_user_id', '=', $userId);
            $favSrchObj->addCondition('ufs_shop_id', '=', $shop_id);
            $srch->joinTable('(' . $favSrchObj->getQuery() . ')', 'LEFT OUTER JOIN', 'ufs_shop_id = shop_id', 'ufs');
            /* ] */

            $srch->addMultipleFields(
                array(
                    'shop_id',
                    'tu.user_name',
                    'tu.user_regdate',
                    'shop_user_id',
                    'shop_ltemplate_id',
                    'shop_created_on',
                    'IFNULL(shop_name, shop_identifier) as shop_name',
                    'shop_description',
                    'shop_country_l.country_name as shop_country_name',
                    'shop_state_l.state_name as shop_state_name',
                    'shop_city',
                    'IFNULL(ufs.ufs_id, 0) as is_favorite'
                )
            );
            $srch->addCondition('shop_id', '=', $shop_id);
            /* if($policy) {
                $srch->addMultipleFields(array('shop_payment_policy', 'shop_delivery_policy','shop_refund_policy','shop_additional_info','shop_seller_info'));
            } */
            //echo $srch->getQuery();
            $shopRs = $srch->getResultSet();
            $shop = FatApp::getDb()->fetch($shopRs);
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
            'shop_rfq_enabled'
        );
        $removeFlds = array_diff($flds, ['1']);
        $this->setRecordCount(clone $srch, $get['pageSize'], $get['page'], $get, true, $removeFlds);

        Product::setOrderOnListingObj($srch, $get);

        $srch->setPageNumber($page);
        if ($pageSize) {
            $srch->setPageSize($pageSize);
        }
        //echo $srch->getQuery();
        $products = FatApp::getDb()->fetchAll($srch->getResultSet());

        $data = array(
            'products' => $products,
            'shop' => $shop,
            'page' => $this->pageData['page'],
            'pageSize' => $this->pageData['pageSize'],
            'pageCount' => $this->pageData['pageCount'],
            'recordCount' => $this->pageData['recordCount'],
            'shopId' => $shop_id,
            'postedData' => $get,
            'siteLangId' => $this->siteLangId
        );
        return $data;
    }

    public function shopReportReasons()
    {
        $srch = ShopReportReason::getReportReasonArr($this->siteLangId, true);
        $rs = $srch->getResultSet();
        $data = FatApp::getDb()->fetchAll($rs);
        $this->set('data', array('reportReasons' => $data));
        $this->_template->render();
    }
}
