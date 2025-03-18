<?php

class ReportsController extends SellerBaseController
{

    public function __construct($action)
    {
        parent::__construct($action);
    }

    public function index()
    {
        if (User::isSeller()) {
            FatApp::redirectUser(UrlHelper::generateUrl('seller', '', [], CONF_WEBROOT_DASHBOARD));
        } elseif (User::isBuyer()) {
            FatApp::redirectUser(UrlHelper::generateUrl('buyer', '', [], CONF_WEBROOT_DASHBOARD));
        } else {
            FatApp::redirectUser(UrlHelper::generateUrl('', '', [], CONF_WEBROOT_DASHBOARD));
        }
    }

    public function productsPerformance()
    {
        $this->userPrivilege->canViewPerformanceReport(UserAuthentication::getLoggedUserId());
        if (!User::canAccessSupplierDashboard() || !User::isSellerVerified($this->userParentId)) {
            FatApp::redirectUser(UrlHelper::generateUrl('Account', 'supplierApprovalForm'));
        }

        $fields = $this->getProductsPerformanceFormColumns($this->siteLangId);
        $frmSearch = $this->getProdPerformanceSrchForm($fields);
        $this->set('frmSearch', $frmSearch);
        $this->set('defaultColumns', $this->getDefaultProductsPerformanceColumns());
        $this->set('fields', $fields);
        $this->set('keywordPlaceholder', Labels::getLabel('LBL_SEARCH_BY_PRODUCT_NAME', $this->siteLangId));

        $this->_template->render(true, true);
    }

    public function searchProductsPerformance($type = "")
    {
        if (!User::canAccessSupplierDashboard()) {
            Message::addErrorMessage(Labels::getLabel("LBL_Invalid_Access!", $this->siteLangId));
            FatUtility::dieWithError(Message::getHtml());
        }

        $fields = $this->getProductsPerformanceFormColumns($this->siteLangId);
        $selectedFlds = FatApp::getPostedData('reportColumns', FatUtility::VAR_STRING, '');
        $selectedFlds = !empty($selectedFlds) ? json_decode($selectedFlds) + $this->getDefaultProductsPerformanceColumns() : $this->getDefaultProductsPerformanceColumns();
        $fields = FilterHelper::parseArrayByKeys($fields, $selectedFlds, true);
        $sortBy = FatApp::getPostedData('sortBy', FatUtility::VAR_STRING, current(array_keys($fields)));
        if (!array_key_exists($sortBy, $fields)) {
            $sortBy = current(array_keys($fields));
        }

        $sortOrder = FatApp::getPostedData('sortOrder', FatUtility::VAR_STRING, applicationConstants::SORT_DESC);
        if (!array_key_exists($sortOrder, applicationConstants::sortOrder($this->siteLangId))) {
            $sortOrder = applicationConstants::SORT_DESC;
        }

        $srchFrm = $this->getProdPerformanceSrchForm($fields);
        $post = $srchFrm->getFormDataFromArray(FatApp::getPostedData());

        $page = FatApp::getPostedData('page', FatUtility::VAR_INT, 1);
        if ($page < 2) {
            $page = 1;
        }
        $pageSize = FatApp::getConfig('conf_page_size', FatUtility::VAR_INT, 10);
        $keyword = FatApp::getPostedData('keyword', FatUtility::VAR_STRING, '');
        $userId = $this->userParentId;
        $shopDetails = Shop::getAttributesByUserId($userId, array('shop_id'), false);

        if (!$shopDetails) {
            Message::addErrorMessage(Labels::getLabel("LBL_Invalid_Access!", $this->siteLangId));
            FatUtility::dieWithError(Message::getHtml());
        }

        /* Sub Query to get, how many users added current product in his/her wishlist[ */
        $uWsrch = new UserWishListProductSearch($this->siteLangId);
        $uWsrch->doNotCalculateRecords();
        $uWsrch->doNotLimitRecords();
        $uWsrch->joinWishLists();
        $uWsrch->addGroupBy('uwlp_selprod_id');
        $uWsrch->addMultipleFields(array('uwlp_selprod_id', 'count(uwlist_user_id) as wishlist_user_counts'));
        /* ] */

        $srch = new OrderProductSearch($this->siteLangId, true);
        $srch->joinPaymentMethod();
        $srch->joinTable('(' . $uWsrch->getQuery() . ')', 'LEFT OUTER JOIN', 'tquwl.uwlp_selprod_id = op.op_selprod_id', 'tquwl');
        $srch->addCondition('op_shop_id', '=', $shopDetails['shop_id']);
        //$srch->doNotCalculateRecords();
        $srch->addStatusCondition(unserialize(FatApp::getConfig("CONF_COMPLETED_ORDER_STATUS")));
        $cnd = $srch->addCondition('order_payment_status', '=', Orders::ORDER_PAYMENT_PAID);
        $cnd->attachCondition('plugin_code', '=', 'cashondelivery');
        $cnd->attachCondition('plugin_code', '=', 'payatstore');
        $srch->addGroupBy('op.op_selprod_id');
        $srch->addGroupBy('op.op_is_batch');
        $srch->addHaving('totSoldQty', '>', 0, 'AND');
        $srch->addHaving('totRefundQty', '>', 0, 'OR');

        if (!empty($keyword)) {
            $cnd = $srch->addCondition('op_product_name', 'LIKE', '%' . $keyword . '%');
            $cnd->attachCondition('op_selprod_title', 'LIKE', '%' . $keyword . '%', 'OR');
        }
        $srch->addMultipleFields(array('op_selprod_title', 'op_product_name as product_name', 'op_selprod_options', 'op_brand_name', 'SUM(op_refund_qty) as totRefundQty', 'SUM(op_qty - op_refund_qty) as totSoldQty', 'op.op_selprod_id', 'IFNULL(tquwl.wishlist_user_counts, 0) as wishlist_user_counts', 'op_selprod_sku'));
        $this->setRecordCount(clone $srch, $pageSize, $page, $post, true);
        $srch->doNotCalculateRecords();
        if (!array_key_exists($sortOrder, applicationConstants::sortOrder(CommonHelper::getLangId()))) {
            $sortOrder = applicationConstants::SORT_ASC;
        }

        switch ($sortBy) {
            default:
                $srch->addOrder($sortBy, $sortOrder);
                break;
        }
        if ($type == 'export') {
            $batchCount = FatApp::getPostedData('batch_count', FatUtility::VAR_INT, 0);
            $batchNumber = FatApp::getPostedData('batch_number', FatUtility::VAR_INT, 1);
            $pageSize = Report::MAX_LIMIT;
            if (isset($batchCount) && $batchCount > 0 && $batchCount <= Report::MAX_LIMIT) {
                $pageSize = $batchCount;
            }
            $pagenumber = ($batchNumber < 1) ? 1 : $batchNumber;

            $srch->setPageNumber($pagenumber);
            $srch->setPageSize($pageSize);
            $rs = $srch->getResultSet();
            $sheetData = array();
            array_push($sheetData, array_values($fields));
            while ($row = FatApp::getDb()->fetch($rs)) {
                $arr = [];
                foreach ($fields as $key => $val) {
                    switch ($key) {
                        case 'product_name':
                            $name = $row['product_name'] . '(' . $row['op_selprod_title'] . ')';
                            $arr[] = $name;
                            break;
                        default:
                            $arr[] = $row[$key];
                            break;
                    }
                }
                array_push($sheetData, $arr);
            }
            CommonHelper::convertToCsv($sheetData, Labels::getLabel('LBL_Products_Performance', $this->siteLangId) . ' ' . date("d-M-Y") . '.csv', ',');
            exit;
        }
        $srch->setPageNumber($page);
        $srch->setPageSize($pageSize);
        $this->set('arrListing', FatApp::getDb()->fetchAll($srch->getResultSet()));
        $this->set('postedData', $post);
        $this->set('sortBy', $sortBy);
        $this->set('sortOrder', $sortOrder);
        $this->set('fields', $fields);
        $this->_template->render(false, false);
    }

    public function searchMostWishListAddedProducts($export = "")
    {
        if (!User::canAccessSupplierDashboard()) {
            Message::addErrorMessage(Labels::getLabel("LBL_Invalid_Access!", $this->siteLangId));
            FatUtility::dieWithError(Message::getHtml());
        }
        $post = FatApp::getPostedData();
        $page = FatApp::getPostedData('page', FatUtility::VAR_INT, 1);
        if ($page < 2) {
            $page = 1;
        }
        $pageSize = FatApp::getConfig('conf_page_size', FatUtility::VAR_INT, 10);
        $userId = $this->userParentId;
        $shopDetails = Shop::getAttributesByUserId($userId, array('shop_id'), false);

        if (!$shopDetails) {
            Message::addErrorMessage(Labels::getLabel("LBL_Invalid_Access!", $this->siteLangId));
            FatUtility::dieWithError(Message::getHtml());
        }

        /* $srch = new ProductSearch( $this->siteLangId );
          $srch->setDefinedCriteria( 0 );
          $srch->joinProductToCategory(); */

        /* Sub Query to get, how many users added current product in his/her wishlist[ */
        $uWsrch = new UserWishListProductSearch($this->siteLangId);
        $uWsrch->doNotCalculateRecords();
        $uWsrch->doNotLimitRecords();
        $uWsrch->joinWishLists();
        $uWsrch->addGroupBy('uwlp_selprod_id');
        $uWsrch->addMultipleFields(array('uwlp_selprod_id', 'count(uwlist_user_id) as wishlist_user_counts'));
        /* ] */

        $srch = SellerProduct::getSearchObject($this->siteLangId);
        $srch->joinTable(Product::DB_TBL, 'INNER JOIN', 'p.product_id = sp.selprod_product_id', 'p');
        $srch->joinTable(Product::DB_TBL_LANG, 'LEFT OUTER JOIN', 'p.product_id = p_l.productlang_product_id AND p_l.productlang_lang_id = ' . $this->siteLangId, 'p_l');
        $srch->joinTable(Brand::DB_TBL, 'LEFT OUTER JOIN', 'p.product_brand_id = b.brand_id', 'b');
        $srch->joinTable(Brand::DB_TBL_LANG, 'LEFT OUTER JOIN', 'b.brand_id = b_l.brandlang_brand_id AND b_l.brandlang_lang_id = ' . $this->siteLangId, 'b_l');
        $srch->joinTable('(' . $uWsrch->getQuery() . ')', 'LEFT OUTER JOIN', 'tquwl.uwlp_selprod_id = sp.selprod_id', 'tquwl');
        $srch->addCondition('selprod_user_id', '=', $userId);
        $srch->addCondition('selprod_deleted', '=', applicationConstants::NO);
        $srch->addCondition('wishlist_user_counts', '>', applicationConstants::NO);
        $srch->addOrder('wishlist_user_counts', 'DESC');
        $srch->addMultipleFields(array('selprod_id', 'product_id', 'IFNULL(product_name, product_identifier) as product_name', 'IFNULL(selprod_title  ,IFNULL(product_name, product_identifier)) as selprod_title', 'selprod_active', 'IFNULL(brand_name, brand_identifier) as brand_name', 'IFNULL(tquwl.wishlist_user_counts, 0) as wishlist_user_counts', 'selprod_sku'));

        if ($export == "export") {
            $srch->doNotCalculateRecords();
            $srch->doNotLimitRecords();
            $rs = $srch->getResultSet();
            $sheetData = array();
            $arr = array(Labels::getLabel('LBL_Product', $this->siteLangId), Labels::getLabel('LBL_Custom_Title', $this->siteLangId), Labels::getLabel('LBL_Brand', $this->siteLangId), Labels::getLabel('LBL_SKU', $this->siteLangId), Labels::getLabel('LBL_User_Counts', $this->siteLangId));
            array_push($sheetData, $arr);
            while ($row = FatApp::getDb()->fetch($rs)) {
                $arr = array($row['product_name'], $row['selprod_title'], $row['brand_name'], $row['selprod_sku'], $row['wishlist_user_counts']);
                array_push($sheetData, $arr);
            }
            CommonHelper::convertToCsv($sheetData, Labels::getLabel('LBL_Most_Favorites_Products_Report', $this->siteLangId) . date("Y-m-d") . '.csv', ',');
            exit;
        } else {
            $srch->setPageNumber($page);
            $srch->setPageSize($pageSize);
            $rs = $srch->getResultSet();

            $arrListing = FatApp::getDb()->fetchAll($rs);
            $this->set('arrListing', $arrListing);
            $this->set('pageCount', $srch->pages());
            $this->set('page', $page);
            $this->set('pageSize', $pageSize);
            $this->set('postedData', $post);
            $this->set('recordCount', $srch->recordCount());
            $this->_template->render(false, false);
        }
    }

    public function exportMostWishListAddedProducts()
    {
        $this->searchMostWishListAddedProducts("export");
    }

    public function exportProductPerformance($orderBy = 'DESC')
    {
        $this->searchProductsPerformance($orderBy, "export");
    }

    public function productsInventory()
    {
        $this->userPrivilege->canViewInventoryReport(UserAuthentication::getLoggedUserId());
        $fields = $this->productsInventoryColumns($this->siteLangId);
        $frmSrch = $this->getProductInventorySearchForm($fields);
        $this->set('defaultColumns', $this->getproductsInventoryDefaultColumns());
        $this->set('frmSrch', $frmSrch);
        $this->set('fields', $fields);
        $this->set('keywordPlaceholder', Labels::getLabel('LBL_SEARCH_BY_PRODUCT_NAME', $this->siteLangId));
        $this->_template->render(true, true);
    }

    public function searchProductsInventory($export = "")
    {
        $fields = $this->productsInventoryColumns($this->siteLangId);
        $selectedFlds = FatApp::getPostedData('reportColumns', FatUtility::VAR_STRING, '');
        $selectedFlds = !empty($selectedFlds) ? json_decode($selectedFlds) + $this->getproductsInventoryDefaultColumns() : $this->getproductsInventoryDefaultColumns();
        $fields = FilterHelper::parseArrayByKeys($fields, $selectedFlds, true);
        $sortBy = FatApp::getPostedData('sortBy', FatUtility::VAR_STRING, current(array_keys($fields)));
        if (!array_key_exists($sortBy, $fields)) {
            $sortBy = current(array_keys($fields));
        }

        $sortOrder = FatApp::getPostedData('sortOrder', FatUtility::VAR_STRING, applicationConstants::SORT_ASC);
        if (!array_key_exists($sortOrder, applicationConstants::sortOrder($this->siteLangId))) {
            $sortOrder = applicationConstants::SORT_ASC;
        }
        $frmSrch = $this->getProductInventorySearchForm($fields);
        $post = $frmSrch->getFormDataFromArray(FatApp::getPostedData());

        $pageSize = FatApp::getConfig('CONF_PAGE_SIZE', FatUtility::VAR_INT, 10);
        $keyword = FatApp::getPostedData('keyword', FatUtility::VAR_STRING, '');
        $page = FatApp::getPostedData('page', FatUtility::VAR_INT, 0);
        if ($page < 2) {
            $page = 1;
        }

        $opSrch = new Report(0, array_keys($fields), true);
        $opSrch->joinOrders();
        $opSrch->joinPaymentMethod();
        $opSrch->joinOtherCharges(true);
        $opSrch->joinOrderProductTaxCharges();
        $opSrch->joinOrderProductShipCharges();
        $opSrch->joinOrderProductDicountCharges();
        $opSrch->joinOrderProductVolumeCharges();
        $opSrch->joinOrderProductRewardCharges();
        $opSrch->setPaymentStatusCondition();
        $opSrch->setCompletedOrdersCondition();
        $opSrch->excludeDeletedOrdersCondition();
        $opSrch->addTotalOrdersCount('selprod_id');
        $opSrch->setGroupBy('selprod_id');
        $opSrch->doNotCalculateRecords();
        $opSrch->doNotLimitRecords();
        $opSrch->removeFld(['product_name', 'followers', 'selprod_price', 'selprod_sku']);
        $opSrch->addCondition('op.op_selprod_user_id', '=', $this->userParentId);

        /* get Seller product Options[ */
        $spOptionSrch = new SearchBase(SellerProduct::DB_TBL_SELLER_PROD_OPTIONS, 'spo');
        $spOptionSrch->joinTable(OptionValue::DB_TBL, 'INNER JOIN', 'spo.selprodoption_optionvalue_id = ov.optionvalue_id', 'ov');
        $spOptionSrch->joinTable(OptionValue::DB_TBL . '_lang', 'LEFT OUTER JOIN', 'ov_lang.optionvaluelang_optionvalue_id = ov.optionvalue_id AND ov_lang.optionvaluelang_lang_id = ' . $this->siteLangId, 'ov_lang');
        $spOptionSrch->joinTable(Option::DB_TBL, 'INNER JOIN', '`option`.option_id = ov.optionvalue_option_id', '`option`');
        $spOptionSrch->joinTable(Option::DB_TBL . '_lang', 'LEFT OUTER JOIN', '`option`.option_id = option_lang.optionlang_option_id AND option_lang.optionlang_lang_id = ' . $this->siteLangId, 'option_lang');
        $spOptionSrch->doNotCalculateRecords();
        $spOptionSrch->doNotLimitRecords();
        $spOptionSrch->addGroupBy('spo.selprodoption_selprod_id');
        $spOptionSrch->addMultipleFields(array('spo.selprodoption_selprod_id', 'IFNULL(option_name, option_identifier) as option_name', 'IFNULL(optionvalue_name, optionvalue_identifier) as optionvalue_name', 'GROUP_CONCAT(option_name) as grouped_option_name', 'GROUP_CONCAT(optionvalue_name) as grouped_optionvalue_name'));
        /* ] */

        /* Sub Query to get, how many users added current product in his/her wishlist[ */
        $uWsrch = new UserWishListProductSearch($this->siteLangId);
        $uWsrch->doNotCalculateRecords();
        $uWsrch->doNotLimitRecords();
        $uWsrch->joinWishLists();
        $uWsrch->addMultipleFields(array('uwlp_selprod_id', 'uwlist_user_id'));
        /* ] */

        $srch = new ProductSearch($this->siteLangId, '', '', false, false, false);
        $srch->joinTable(SellerProduct::DB_TBL, 'LEFT OUTER JOIN', 'p.product_id = selprod.selprod_product_id', 'selprod');
        $srch->joinTable(SellerProduct::DB_TBL_LANG, 'LEFT OUTER JOIN', 'selprod.selprod_id = sprod_l.selprodlang_selprod_id AND sprod_l.selprodlang_lang_id = ' . $this->siteLangId, 'sprod_l');
        $srch->joinSellers();
        $srch->joinBrands($this->siteLangId, false, true);
        //$srch->addCondition('brand_id', '!=', 'NULL');
        $srch->joinShops($this->siteLangId, false, false);
        $srch->joinTable('(' . $spOptionSrch->getQuery() . ')', 'LEFT OUTER JOIN', 'selprod_id = spoq.selprodoption_selprod_id', 'spoq');
        $srch->joinTable('(' . $opSrch->getQuery() . ')', 'INNER JOIN', 'selprod.selprod_id = opq.op_selprod_id', 'opq');
        $srch->joinTable('(' . $uWsrch->getQuery() . ')', 'LEFT OUTER JOIN', 'tquwl.uwlp_selprod_id = selprod.selprod_id', 'tquwl');
        $srch->joinProductToCategory();
        $srch->addCondition('selprod.selprod_id', '!=', 'NULL');

        /* groupby added, because if same product is linked with multiple categories, then showing in repeat for each category[ */
        $srch->addGroupBy('selprod_id');
        /* ] */

        if (!array_key_exists($sortOrder, applicationConstants::sortOrder(CommonHelper::getLangId()))) {
            $sortOrder = applicationConstants::SORT_ASC;
        }

        switch ($sortBy) {
            default:
                $srch->addOrder($sortBy, $sortOrder);
                break;
        }

        $keyword = FatApp::getPostedData('keyword', FatUtility::VAR_STRING);
        if (!empty($keyword)) {
            $srch->addKeywordSearch($keyword);
        }

        if ($keyword = FatApp::getPostedData('keyword')) {
            $cnd = $srch->addCondition('product_name', 'like', "%$keyword%");
            $cnd->attachCondition('selprod_title', 'LIKE', "%$keyword%");
            $cnd->attachCondition('brand_name', 'LIKE', "%$keyword%");
        }
        $srch->addFld('product_id');
        $this->setRecordCount(clone $srch, $pageSize, $page, $post, true);
        $srch->doNotCalculateRecords();
        $srch->addMultipleFields(array('product_id', 'product_name', 'selprod_id', 'selprod_code', 'selprod_user_id', 'selprod_title', 'selprod_price', 'IFNULL(totOrders, 0) as totOrders', 'grouped_option_name', 'grouped_optionvalue_name', 'IFNULL(s_l.shop_name, shop_identifier) as shop_name', 'IFNULL(tb_l.brand_name, brand_identifier) as brand_name', 'count(distinct tquwl.uwlist_user_id) as followers', 'selprod_sku', 'opq.*'));
        if ($export == "export") {
            $batchCount = FatApp::getPostedData('batch_count', FatUtility::VAR_INT, 0);
            $batchNumber = FatApp::getPostedData('batch_number', FatUtility::VAR_INT, 1);
            $pageSize = Report::MAX_LIMIT;
            if (isset($batchCount) && $batchCount > 0 && $batchCount <= Report::MAX_LIMIT) {
                $pageSize = $batchCount;
            }
            $pagenumber = ($batchNumber < 1) ? 1 : $batchNumber;

            $srch->setPageNumber($pagenumber);
            $srch->setPageSize($pageSize);
            $rs = $srch->getResultSet();
            $sheetData = array();

            array_push($sheetData, array_values($fields));
            while ($row = FatApp::getDb()->fetch($rs)) {
                $arr = [];
                foreach ($fields as $key => $val) {
                    switch ($key) {
                        case 'product_name':
                            $name = ($row['selprod_title'] != '') ? $row['selprod_title'] : $row['product_name'];

                            if ($row['grouped_option_name'] != '') {
                                $groupedOptionNameArr = explode(',', $row['grouped_option_name']);
                                $groupedOptionValueArr = explode(',', $row['grouped_optionvalue_name']);
                                if (!empty($groupedOptionNameArr)) {
                                    foreach ($groupedOptionNameArr as $key => $optionName) {
                                        $name .= "\n" . $optionName . ':</strong> ' . $groupedOptionValueArr[$key];
                                    }
                                }
                            }

                            if ($row['brand_name'] != '') {
                                $name .= "\n" . Labels::getLabel('LBL_Brand', $this->siteLangId) . ": " . $row['brand_name'];
                            }

                            if ($row['shop_name'] != '') {
                                $name .= "\n" . Labels::getLabel('LBL_Sold_By', $this->siteLangId) . ': ' . $row['shop_name'];
                            }
                            $arr[] = html_entity_decode($name, ENT_QUOTES, 'utf-8');
                            break;
                        case 'grossSales':
                        case 'transactionAmount':
                        case 'inventoryValue':
                        case 'taxTotal':
                        case 'adminTaxTotal':
                        case 'sellerTaxTotal':
                        case 'shippingTotal':
                        case 'sellerShippingTotal':
                        case 'discountTotal':
                        case 'couponDiscount':
                        case 'volumeDiscount':
                        case 'rewardDiscount':
                        case 'refundedAmount':
                        case 'refundedShippingFromSeller':
                        case 'refundedTaxFromSeller':
                        case 'orderNetAmount':
                        case 'commissionCharged':
                        case 'refundedCommission':
                        case 'adminSalesEarnings':
                            $arr[] = CommonHelper::displayMoneyFormat($row[$key], true, true, false);
                            break;
                        default:
                            $arr[] = $row[$key];
                            break;
                    }
                }

                array_push($sheetData, $arr);
            }
            CommonHelper::convertToCsv($sheetData, Labels::getLabel('LBL_Products_Inventory_Report', $this->siteLangId) . date("Y-m-d") . '.csv', ',');
            exit;
        } else {
            $srch->setPageNumber($page);
            $srch->setPageSize($pageSize);
            $arrListing = FatApp::getDb()->fetchAll($srch->getResultSet());

            if (count($arrListing)) {
                foreach ($arrListing as &$arr) {
                    $arr['options'] = SellerProduct::getSellerProductOptions($arr['selprod_id'], true, $this->siteLangId);
                }
            }
            $this->set('postedData', $post);
            $this->set('arrListing', $arrListing);
            $this->set('fields', $fields);
            $this->set('sortBy', $sortBy);
            $this->set('sortOrder', $sortOrder);
            $this->_template->render(false, false);
        }
    }

    public function exportProductsInventoryReport()
    {
        $this->searchProductsInventory("export");
    }

    public function productsInventoryStockStatus()
    {
        $this->userPrivilege->canViewInventoryReport(UserAuthentication::getLoggedUserId());
        if (!User::canAccessSupplierDashboard()) {
            FatApp::redirectUser(UrlHelper::generateUrl('Account', 'supplierApprovalForm'));
        }

        $fields = $this->productsInventoryStockStatusColumns($this->siteLangId);
        $frmSearch = $this->getProdInventoryStockStatusSrchForm($this->siteLangId, $fields);
        $this->set('frmSearch', $frmSearch);
        $this->set('defaultColumns', $this->getProdInventoryStockStatusDefaultColumns());
        $this->set('fields', $fields);
        $this->set('keywordPlaceholder', Labels::getLabel('LBL_SEARCH_BY_PRODUCT_NAME', $this->siteLangId));
        // $this->_template->addJs('js/report.js');
        $this->_template->render();
    }

    public function searchProductsInventoryStockStatus($type = "")
    {
        if (!User::canAccessSupplierDashboard()) {
            Message::addErrorMessage(Labels::getLabel("LBL_Invalid_Access!", $this->siteLangId));
            FatUtility::dieWithError(Message::getHtml());
        }

        $fields = $this->productsInventoryStockStatusColumns($this->siteLangId);
        $selectedFlds = FatApp::getPostedData('reportColumns', FatUtility::VAR_STRING, '');
        $selectedFlds = !empty($selectedFlds) ? json_decode($selectedFlds) + $this->getProdInventoryStockStatusDefaultColumns() : $this->getProdInventoryStockStatusDefaultColumns();
        $fields = FilterHelper::parseArrayByKeys($fields, $selectedFlds, true);
        $sortBy = FatApp::getPostedData('sortBy', FatUtility::VAR_STRING, current(array_keys($fields)));
        if (!array_key_exists($sortBy, $fields)) {
            $sortBy = current(array_keys($fields));
        }

        $sortOrder = FatApp::getPostedData('sortOrder', FatUtility::VAR_STRING, applicationConstants::SORT_DESC);
        if (!array_key_exists($sortOrder, applicationConstants::sortOrder($this->siteLangId))) {
            $sortOrder = applicationConstants::SORT_DESC;
        }

        $srchFrm = $this->getProdInventoryStockStatusSrchForm($this->siteLangId, $fields);
        $post = $srchFrm->getFormDataFromArray(FatApp::getPostedData());

        $pageSize = FatApp::getConfig('CONF_PAGE_SIZE');
        $page = FatApp::getPostedData('page', FatUtility::VAR_INT, 0);
        if ($page < 2) {
            $page = 1;
        }

        $userId = $this->userParentId;

        /* [ */
        $orderProductSrch = new OrderProductSearch($this->siteLangId, true);
        $orderProductSrch->joinPaymentMethod();
        $orderProductSrch->doNotCalculateRecords();
        $orderProductSrch->doNotLimitRecords();
        $orderProductSrch->addStatusCondition(unserialize(FatApp::getConfig("CONF_PRODUCT_IS_ON_ORDER_STATUSES")));
        $cnd = $orderProductSrch->addCondition('order_payment_status', '=', Orders::ORDER_PAYMENT_PAID);
        $cnd->attachCondition('pm.plugin_code', '=', 'CashOnDelivery');
        $orderProductSrch->addCondition('op.op_is_batch', '=', 0);
        $orderProductSrch->addMultipleFields(array('op.op_selprod_id', 'SUM(op_qty) as stock_on_order', 'op_selprod_options'));
        $orderProductSrch->addGroupBy('op.op_selprod_id');
        /* ] */

        $srch = SellerProduct::getSearchObject($this->siteLangId);
        $srch->joinTable('(' . $orderProductSrch->getQuery() . ')', 'INNER JOIN', 'sp.selprod_id = qryop.op_selprod_id', 'qryop');
        $srch->joinTable(Product::DB_TBL, 'INNER JOIN', 'p.product_id = sp.selprod_product_id', 'p');
        $srch->joinTable(Product::DB_TBL_LANG, 'LEFT OUTER JOIN', 'p.product_id = p_l.productlang_product_id AND p_l.productlang_lang_id = ' . $this->siteLangId, 'p_l');
        $srch->joinTable(Brand::DB_TBL, 'INNER JOIN', 'p.product_brand_id = b.brand_id', 'b');
        $srch->joinTable(Brand::DB_TBL_LANG, 'LEFT OUTER JOIN', 'b.brand_id = b_l.brandlang_brand_id  AND brandlang_lang_id = ' . $this->siteLangId, 'b_l');
        $srch->addCondition('selprod_user_id', '=', $userId);
        if ($keyword = FatApp::getPostedData('keyword')) {
            $cnd = $srch->addCondition('product_name', 'like', "%$keyword%");
            $cnd->attachCondition('selprod_title', 'LIKE', "%$keyword%");
            $cnd->attachCondition('brand_name', 'LIKE', "%$keyword%");
        }

        $this->setRecordCount(clone $srch, $pageSize, $page, $post);
        $srch->doNotCalculateRecords();

        if (!array_key_exists($sortOrder, applicationConstants::sortOrder(CommonHelper::getLangId()))) {
            $sortOrder = applicationConstants::SORT_ASC;
        }

        switch ($sortBy) {
            default:
                $srch->addOrder($sortBy, $sortOrder);
                break;
        }
        $srch->addOrder('selprod_active', 'DESC');
        $srch->addMultipleFields(['IFNULL(selprod_title  ,IFNULL(product_name, product_identifier)) as selprod_title', 'IFNULL(product_name, product_identifier) as product_name', 'selprod_stock', 'IFNULL(qryop.stock_on_order, 0) as stock_on_order', 'selprod_cost', '(selprod_stock * selprod_cost)  as inventory_value', 'selprod_price', '(selprod_stock * selprod_price)  as  total_value', 'selprod_id', 'brand_name', 'selprod_sku']);

        if ($type == 'export') {
            $batchCount = FatApp::getPostedData('batch_count', FatUtility::VAR_INT, 0);
            $batchNumber = FatApp::getPostedData('batch_number', FatUtility::VAR_INT, 1);
            $pageSize = Report::MAX_LIMIT;
            if (isset($batchCount) && $batchCount > 0 && $batchCount <= Report::MAX_LIMIT) {
                $pageSize = $batchCount;
            }
            $pagenumber = ($batchNumber < 1) ? 1 : $batchNumber;

            $srch->setPageNumber($pagenumber);
            $srch->setPageSize($pageSize);
            $rs = $srch->getResultSet();
            $sheetData = array();
            array_push($sheetData, array_values($fields));
            while ($row = FatApp::getDb()->fetch($rs)) {
                $arr = [];
                foreach ($fields as $key => $val) {
                    switch ($key) {
                        case 'product_name':
                            $name = $row['product_name'] . '(' . $row['selprod_title'] . ')';
                            $arr[] = $name;
                            break;
                        case 'selprod_cost':
                        case 'inventory_value':
                        case 'selprod_price':
                        case 'total_value':
                            $arr[] = CommonHelper::displayMoneyFormat($row[$key], true, true, false);
                            break;
                        default:
                            $arr[] = $row[$key];
                            break;
                    }
                }

                array_push($sheetData, $arr);
            }

            CommonHelper::convertToCsv($sheetData, Labels::getLabel('LBL_Products_Inventory_Report', $this->siteLangId) . ' ' . date("d-M-Y") . '.csv', ',');
            exit;
        }
        $srch->setPageNumber($page);
        $srch->setPageSize($pageSize);
        $rs = $srch->getResultSet();
        $arrListing = FatApp::getDb()->fetchAll($rs);
        if (count($arrListing)) {
            foreach ($arrListing as &$arr) {
                $arr['options'] = SellerProduct::getSellerProductOptions($arr['selprod_id'], true, $this->siteLangId);
            }
        }
        $this->set("arrListing", $arrListing);
        $this->set('postedData', $post);
        $this->set('sortBy', $sortBy);
        $this->set('sortOrder', $sortOrder);
        $this->set('fields', $fields);
        $this->_template->render(false, false);
    }

    public function exportProductsInventoryStockStatusReport()
    {
        $this->searchProductsInventoryStockStatus("export");
    }

    private function getProdPerformanceSrchForm()
    {
        $frm = new Form('frmReportSearch');
        $frm->addHiddenField('', 'page');
        $frm->addHiddenField('', 'total_record_count');
        $frm->addTextBox('', 'keyword');
        $frm->addHiddenField('', 'sortBy', 'product_name');
        $frm->addHiddenField('', 'sortOrder', applicationConstants::SORT_ASC);
        $frm->addHiddenField('', 'reportColumns', '');

        HtmlHelper::addSearchButton($frm);
        HtmlHelper::addClearButton($frm, 'btn btn-clear');
        return $frm;
    }

    private function getProductsPerformanceFormColumns($langId)
    {
        $sellerProdPerformanceCacheVar = CacheHelper::get('sellerProdPerformanceCacheVar' . '-' . $langId, CONF_DEF_CACHE_TIME, '.txt');
        if (!$sellerProdPerformanceCacheVar) {
            $arr = [
                'product_name' => Labels::getLabel('LBL_Product', $langId),
                'op_brand_name' => Labels::getLabel('LBL_BRAND', $langId),
                'op_selprod_sku' => Labels::getLabel('LBL_SKU', $langId),
                'wishlist_user_counts' => Labels::getLabel('LBL_WishList_User_Counts', $langId),
                'totSoldQty' => Labels::getLabel('LBL_Sold_Quantity', $langId),
                'totRefundQty' => Labels::getLabel('LBL_Refund_Quantity', $langId)
            ];
            CacheHelper::create('sellerProdPerformanceCacheVar' . $this->siteLangId, serialize($arr), CacheHelper::TYPE_LABELS);
        } else {
            $arr = unserialize($sellerProdPerformanceCacheVar);
        }
        return $arr;
    }

    private function getDefaultProductsPerformanceColumns(): array
    {
        return ['product_name', 'op_selprod_sku', 'wishlist_user_counts', 'totRefundQty', 'totSoldQty'];
    }

    public function salesReport($orderDate = '')
    {
        $this->userPrivilege->canViewSalesReport(UserAuthentication::getLoggedUserId());
        if (!User::canAccessSupplierDashboard()) {
            FatApp::redirectUser(UrlHelper::generateUrl('Account', 'supplierApprovalForm'));
        }

        $fields = $this->getFormColumns($orderDate);
        $frmSearch = $this->getSalesReportSearchForm($fields, $orderDate);
        $frmSearch->fill(['sortBy' => 'orderDate', 'sortOrder' => 'DESC']);
        $this->set('frmSearch', $frmSearch);
        $this->set('orderDate', $orderDate);
        $this->set('defaultColumns', $this->getDefaultColumns($orderDate));
        $this->set('fields', $fields);
        $this->_template->render(true, true);
    }

    public function searchSalesReport($export = "")
    {
        if (!User::canAccessSupplierDashboard()) {
            Message::addErrorMessage(Labels::getLabel("LBL_Invalid_Access!", $this->siteLangId));
            FatUtility::dieWithError(Message::getHtml());
        }

        $orderDate = FatApp::getPostedData('orderDate', FatUtility::VAR_STRING, '');
        $fields = $this->getFormColumns($orderDate);
        $selectedFlds = FatApp::getPostedData('reportColumns', FatUtility::VAR_STRING, '');
        $selectedFlds = !empty($selectedFlds) ? json_decode($selectedFlds) + $this->getDefaultColumns($orderDate) : $this->getDefaultColumns($orderDate);
        $fields = FilterHelper::parseArrayByKeys($fields, $selectedFlds, true);

        $sortBy = FatApp::getPostedData('sortBy', FatUtility::VAR_STRING, current(array_keys($fields)));
        if (!array_key_exists($sortBy, $fields)) {
            $sortBy = current(array_keys($fields));
        }

        $sortOrder = FatApp::getPostedData('sortOrder', FatUtility::VAR_STRING, applicationConstants::SORT_DESC);
        if (!array_key_exists($sortOrder, applicationConstants::sortOrder($this->siteLangId))) {
            $sortOrder = applicationConstants::SORT_DESC;
        }

        $srchFrm = $this->getSalesReportSearchForm($fields, $orderDate);
        $post = $srchFrm->getFormDataFromArray(FatApp::getPostedData());

        $pageSize = FatApp::getConfig('CONF_PAGE_SIZE');
        $page = FatApp::getPostedData('page', FatUtility::VAR_INT, 0);
        if ($page < 2) {
            $page = 1;
        }
        $userId = UserAuthentication::getLoggedUserId();

        $srch = new Report(0, array_keys($fields), true);
        $srch->joinOrders();
        $srch->joinPaymentMethod();
        $srch->joinOtherCharges(true);
        $srch->joinOrderProductTaxCharges();
        $srch->joinOrderProductShipCharges();
        $srch->joinOrderProductDicountCharges();
        $srch->joinOrderProductVolumeCharges();
        $srch->joinOrderProductRewardCharges();
        $srch->setPaymentStatusCondition();
        $srch->setCompletedOrdersCondition();
        $srch->excludeDeletedOrdersCondition();
        $fromDate = $toDate = $orderDate;
        if (empty($orderDate)) {
            $fromDate = FatApp::getPostedData('date_from', FatUtility::VAR_DATE, '');
            $toDate = FatApp::getPostedData('date_to', FatUtility::VAR_DATE, '');
            $srch->setGroupBy('orderDate');
            $srch->addTotalOrdersCount('order_date_added', $userId);
            $srch->addFld('totOrders');
        } else {
            $keyword = FatApp::getPostedData('keyword', FatUtility::VAR_STRING, '');
            if (!empty($keyword)) {
                $cnd = $srch->addCondition('op_invoice_number', 'like', '%' . $keyword . '%');
                $cnd->attachCondition('order_id', 'like', '%' . $keyword . '%');
            }

            $this->set('orderDate', $orderDate);
            $srch->setGroupBy('op_invoice_number');
            $srch->addFld('op_invoice_number');
        }
        $srch->setDateCondition($fromDate, $toDate);
        $srch->addCondition('op_selprod_user_id', '=', $userId);
        $this->setRecordCount(clone $srch, $pageSize, $page, $post, true);
        $srch->doNotCalculateRecords();
        $srch->setOrderBy($sortBy, $sortOrder);
        if ($export == "export") {
            $batchCount = FatApp::getPostedData('batch_count', FatUtility::VAR_INT, 0);
            $batchNumber = FatApp::getPostedData('batch_number', FatUtility::VAR_INT, 1);
            $pageSize = Report::MAX_LIMIT;
            if (isset($batchCount) && $batchCount > 0 && $batchCount <= Report::MAX_LIMIT) {
                $pageSize = $batchCount;
            }
            $pagenumber = ($batchNumber < 1) ? 1 : $batchNumber;

            $srch->setPageNumber($pagenumber);
            $srch->setPageSize($pageSize);
            $rs = $srch->getResultSet();
            $sheetData = array();
            array_push($sheetData, array_values($fields));
            $count = 1;
            while ($row = FatApp::getDb()->fetch($rs)) {
                $arr = [];
                foreach ($fields as $key => $val) {
                    switch ($key) {
                        case 'listserial':
                            $arr[] = $count;
                            break;
                        case 'orderDate':
                            $arr[] = FatDate::format($row['orderDate']);
                            break;
                        case 'grossSales':
                        case 'transactionAmount':
                        case 'inventoryValue':
                        case 'adminTaxTotal':
                        case 'sellerTaxTotal':
                        case 'sellerShippingTotal':
                        case 'volumeDiscount':
                        case 'refundedAmount':
                        case 'refundedShippingFromSeller':
                        case 'refundedTaxFromSeller':
                        case 'orderNetAmount':
                        case 'commissionCharged':
                        case 'refundedCommission':
                        case 'adminSalesEarnings':
                        case 'refundedShippingFromSeller':
                        case 'refundedTaxFromSeller':
                            $arr[] = CommonHelper::displayMoneyFormat($row[$key], true, true, false);
                            break;
                        default:
                            $arr[] = $row[$key];
                            break;
                    }
                }

                array_push($sheetData, $arr);
                $count++;
            }
            CommonHelper::convertToCsv($sheetData, Labels::getLabel('LBL_Sales_Report', $this->siteLangId) . date("Y-m-d") . '.csv', ',');
            exit;
        }

        $srch->setPageNumber($page);
        $srch->setPageSize($pageSize);
        $this->set('postedData', $post);
        $this->set('arrListing', FatApp::getDb()->fetchAll($srch->getResultSet()));
        $this->set('sortBy', $sortBy);
        $this->set('sortOrder', $sortOrder);
        $this->set('fields', $fields);
        $this->_template->render(false, false);
    }

    public function exportSalesReport()
    {
        $this->searchSalesReport("export");
    }

    private function getProdInventoryStockStatusSrchForm($langId, $fields = [])
    {
        $frm = new Form('frmReportSearch');
        $frm->addHiddenField('', 'page');
        if (!empty($fields)) {
            $frm->addHiddenField('', 'sortBy', 'selprod_title');
            $frm->addHiddenField('', 'sortOrder', applicationConstants::SORT_ASC);
            $frm->addHiddenField('', 'reportColumns', '');
        }
        $frm->addHiddenField('', 'total_record_count');
        $frm->addTextBox('', 'keyword', '');

        HtmlHelper::addSearchButton($frm);
        HtmlHelper::addClearButton($frm, 'btn btn-clear');
        return $frm;
    }

    private function productsInventoryStockStatusColumns($langId)
    {
        $selProdInventoryStockStatusCacheVar = CacheHelper::get('selProdInventoryStockStatusCacheVar' . $langId, CONF_DEF_CACHE_TIME, '.txt');
        if (!$selProdInventoryStockStatusCacheVar) {
            $arr = [
                'product_name' => Labels::getLabel('LBL_Product_name', $langId),
                'selprod_stock' => Labels::getLabel('LBL_Stock_Available', $langId),
                'brand_name' => Labels::getLabel('LBL_Brand', $langId),
                'selprod_sku' => Labels::getLabel('LBL_Sku', $langId),
                'stock_on_order' => Labels::getLabel('LBL_Stock_On_Order', $langId),
                'selprod_cost' => Labels::getLabel('LBL_Cost_Price', $langId),
                'inventory_value' => Labels::getLabel('LBL_Inventory_Value_', $langId),
                'selprod_price' => Labels::getLabel('LBL_Unit_Price', $langId),
                'total_value' => Labels::getLabel('LBL_Total_Value_', $langId)
            ];
            CacheHelper::create('selProdInventoryStockStatusCacheVar' . $langId, serialize($arr), CacheHelper::TYPE_LABELS);
        } else {
            $arr = unserialize($selProdInventoryStockStatusCacheVar);
        }

        return $arr;
    }

    private function getProdInventoryStockStatusDefaultColumns(): array
    {
        $arr = ['product_name', 'selprod_stock', 'stock_on_order', 'selprod_cost', 'inventory_value', 'selprod_price', 'total_value'];
        return $arr;
    }

    private function getProductInventorySearchForm($fields = [])
    {
        $frm = new Form('frmReportSearch');
        $frm->addHiddenField('', 'page');
        $frm->addHiddenField('', 'total_record_count', '');
        if (!empty($fields)) {
            $frm->addHiddenField('', 'sortBy', 'product_name');
            $frm->addHiddenField('', 'sortOrder', applicationConstants::SORT_ASC);
            $frm->addHiddenField('', 'reportColumns', '');
        }
        $frm->addTextBox(Labels::getLabel("FRM_KEYWORD", $this->siteLangId), 'keyword');
        HtmlHelper::addSearchButton($frm);
        HtmlHelper::addClearButton($frm, 'btn btn-clear');
        return $frm;
    }

    private function productsInventoryColumns($langId)
    {
        $selProdInventoryReportCacheVar = CacheHelper::get('selProdInventoryReportCacheVar' . $langId, CONF_DEF_CACHE_TIME, '.txt');
        if (!$selProdInventoryReportCacheVar) {
            $arr = [
                'product_name' => Labels::getLabel('LBL_Product_name', $langId),
                'selprod_sku' => Labels::getLabel('LBL_SKU', $langId),
                'followers' => Labels::getLabel('LBL_Favorites', $langId),
                'selprod_price' => Labels::getLabel('LBL_Unit_Price', $langId),
                'totOrders' => Labels::getLabel('LBL_Order_Placed', $langId),
                'totQtys' => Labels::getLabel('LBL_Ordered_Qty', $langId),
                'totRefundedQtys' => Labels::getLabel('LBL_Refunded_Qty', $langId),
                'netSoldQty' => Labels::getLabel('LBL_Sold_Qty', $langId),
                'grossSales' => Labels::getLabel('LBL_Gross_Sale', $langId),
                'transactionAmount' => Labels::getLabel('LBL_Transaction_Amount', $langId),
                'inventoryValue' => Labels::getLabel('LBL_Inventory_Value', $langId),
                'sellerTaxTotal' => Labels::getLabel('LBL_Tax_Charged_By_Seller', $langId),
                'sellerShippingTotal' => Labels::getLabel('LBL_Shipping_Charged_By_Seller', $langId),
                'volumeDiscount' => Labels::getLabel('LBL_Volume_Discount', $langId),
                'refundedAmount' => Labels::getLabel('LBL_Refunded_Amount', $langId),
                'refundedShippingFromSeller' => Labels::getLabel('LBL_Refunded_Shipping', $langId),
                'refundedTaxFromSeller' => Labels::getLabel('LBL_Refunded_Tax', $langId),
                'orderNetAmount' => Labels::getLabel('LBL_Net_Amount', $langId),
                'commissionCharged' => Labels::getLabel('LBL_Commision_Charged', $langId),
                'refundedCommission' => Labels::getLabel('LBL_Refunded_Commision', $langId),
                'adminSalesEarnings' => Labels::getLabel('LBL_Admin_Earnings', $langId)
            ];
            CacheHelper::create('selProdInventoryReportCacheVar' . $langId, serialize($arr), CacheHelper::TYPE_LABELS);
        } else {
            $arr = unserialize($selProdInventoryReportCacheVar);
        }

        return $arr;
    }

    private function getproductsInventoryDefaultColumns(): array
    {
        $arr = ['product_name', 'selprod_sku', 'selprod_price', 'totOrders', 'netSoldQty', 'grossSales', 'refundedAmount', 'orderNetAmount', 'adminSalesEarnings'];
        return $arr;
    }

    private function getSalesReportSearchForm($fields = [], $orderDate = '')
    {
        $frm = new Form('frmReportSrch');
        $frm->addHiddenField('', 'page');
        $frm->addHiddenField('', 'orderDate', $orderDate);
        $frm->addHiddenField('', 'total_record_count');
        if (!empty($fields)) {
            $frm->addHiddenField('', 'sortBy', 'orderDate');
            $frm->addHiddenField('', 'sortOrder', applicationConstants::SORT_DESC);
            $frm->addHiddenField('', 'reportColumns', '');
        }

        if (empty($orderDate)) {
            $frm->addDateField(Labels::getLabel('FRM_DATE_FROM', $this->siteLangId), 'date_from', '', array('placeholder' => Labels::getLabel('FRM_DATE_FROM', $this->siteLangId), 'readonly' => 'readonly', 'class' => 'small dateTimeFld field--calender'));
            $frm->addDateField(Labels::getLabel('FRM_DATE_TO', $this->siteLangId), 'date_to', '', array('placeholder' => Labels::getLabel('FRM_DATE_TO', $this->siteLangId), 'readonly' => 'readonly', 'class' => 'small dateTimeFld field--calender'));
        } else {
            $frm->addTextBox(Labels::getLabel("FRM_KEYWORD", $this->siteLangId), 'keyword');
        }

        HtmlHelper::addSearchButton($frm);
        HtmlHelper::addClearButton($frm, 'btn btn-clear');
        return $frm;
    }

    public function form()
    {
        $formTitle = Labels::getLabel('LBL_SALES_REPORT', $this->siteLangId);
        $frm = $this->getExportForm($this->siteLangId);
        $this->set('frm', $frm);
        $this->set('includeTabs', false);
        $this->set('formTitle', $formTitle);
        $this->set('html', $this->_template->render(false, false, '_partial/listing/form.php', true));
        $this->_template->render(false, false, 'json-success.php', true, false);
    }

    protected function getExportForm($langId)
    {

        $frm = new Form('frmExport', array('id' => 'frmExport'));

        /* Batch Count[ */
        $fld =  $frm->addIntegerField(Labels::getLabel('FRM_COUNTS_PER_BATCH', $langId), 'batch_count', Report::MAX_LIMIT, array('id' => 'batch_count'));
        $fld->requirements()->setRequired(true);
        $fld->requirements()->setRange(1, Report::MAX_LIMIT);
        /*]*/

        /* Batch Number[ */
        $fld = $frm->addIntegerField(Labels::getLabel('FRM_BATCH_NUMBER', $langId), 'batch_number', 1, array('id' => 'batch_number'));
        $fld->requirements()->setRequired(true);
        $fld->requirements()->setPositive();
        $frm->setFormTagAttribute('onSubmit', 'exportReport(); return false;');
        return $frm;
    }

    private function getFormColumns($orderDate = '')
    {
        $shopsReportCacheVar = CacheHelper::get('shopsReportCacheVar' . $this->siteLangId, CONF_DEF_CACHE_TIME, '.txt');
        if (!$shopsReportCacheVar) {
            $arr = [
                'orderDate' => Labels::getLabel('LBL_Date', $this->siteLangId),
                'totOrders' => Labels::getLabel('LBL_Order_Placed', $this->siteLangId),
                'totQtys' => Labels::getLabel('LBL_Ordered_Qty', $this->siteLangId),
                'totRefundedQtys' => Labels::getLabel('LBL_Refunded_Qty', $this->siteLangId),
                'netSoldQty' => Labels::getLabel('LBL_Sold_Qty', $this->siteLangId),
                'grossSales' => Labels::getLabel('LBL_Gross_Sale', $this->siteLangId),
                'transactionAmount' => Labels::getLabel('LBL_Transaction_Amount', $this->siteLangId),
                'inventoryValue' => Labels::getLabel('LBL_Inventory_Value', $this->siteLangId),
                'sellerTaxTotal' => Labels::getLabel('LBL_Tax_Charged', $this->siteLangId),
                'sellerShippingTotal' => Labels::getLabel('LBL_Shipping_Charged', $this->siteLangId),
                'volumeDiscount' => Labels::getLabel('LBL_Volume_Discount', $this->siteLangId),
                'refundedAmount' => Labels::getLabel('LBL_Refunded_Amount', $this->siteLangId),
                'refundedShippingFromSeller' => Labels::getLabel('LBL_Refunded_Shipping', $this->siteLangId),
                'refundedTaxFromSeller' => Labels::getLabel('LBL_Refunded_Tax', $this->siteLangId),
                'orderNetAmount' => Labels::getLabel('LBL_Net_Amount', $this->siteLangId),
                'commissionCharged' => Labels::getLabel('LBL_Commision_Charged', $this->siteLangId),
                'refundedCommission' => Labels::getLabel('LBL_Refunded_Commision', $this->siteLangId),
                'adminSalesEarnings' => Labels::getLabel('LBL_Admin_Earnings', $this->siteLangId),
            ];
            CacheHelper::create('shopsReportCacheVar' . $this->siteLangId, serialize($arr), CacheHelper::TYPE_LABELS);
        } else {
            $arr = unserialize($shopsReportCacheVar);
        }

        if (!empty($orderDate)) {
            unset($arr['orderDate']);
            unset($arr['totOrders']);
            $arr = [
                'op_invoice_number' => Labels::getLabel('LBL_invoice_number', $this->siteLangId),
                'order_date_added' => Labels::getLabel('LBL_Date', $this->siteLangId),
            ] + $arr;
        }

        return $arr;
    }

    protected function getDefaultColumns($orderDate = ''): array
    {
        $arr = ['orderDate', 'totQtys', 'grossSales', 'couponDiscount', 'refundedAmount', 'shippingTotal', 'taxTotal', 'orderNetAmount'];
        if (!empty($orderDate)) {
            unset($arr['orderDate']);
            $arr = [
                'op_invoice_number',
                'order_date_added'
            ] + $arr;
        }
        return $arr;
    }

    public function getBreadcrumbNodes($action)
    {
        if (FatUtility::isAjaxCall()) {
            return;
        }

        $className = get_class($this);
        $arr = explode('-', FatUtility::camel2dashed($className));
        array_pop($arr);
        $className = ucwords(implode('_', $arr));

        if ($action == 'index') {
            $this->nodes[] = array('title' => ucwords(Labels::getLabel('BCN_' . $className)));
        } else if ($action == 'productsInventory') {
            $this->nodes[] = array('title' => Labels::getLabel('LBL_PRODUCTS_INVENTORY_REPORT', $this->siteLangId));
        } else if ($action == 'productsInventoryStockStatus') {
            $this->nodes[] = array('title' => Labels::getLabel('LBL_PRODUCTS_INVENTORY_STOCK_STATUS_REPORT', $this->siteLangId));
        } else if ($action == 'productsPerformance') {
            $this->nodes[] = array('title' => Labels::getLabel('LBL_PRODUCTS_PERFORMANCE_REPORT', $this->siteLangId));
        } else {
            $action = str_replace('-', '_', FatUtility::camel2dashed($action));
            $this->nodes[] = array('title' => ucwords(Labels::getLabel('BCN_' . $action)));
        }
        return $this->nodes;
    }
}
