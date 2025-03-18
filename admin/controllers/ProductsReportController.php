<?php

class ProductsReportController extends ListingBaseController
{
    protected $pageKey = 'REPORT_PRODUCT_VARIANTS';
    public function __construct($action)
    {
        parent::__construct($action);
        $this->objPrivilege->canViewProductsReport();
    }

    public function index()
    {
        $formColumns = $this->getFormColumns();
        $frmSearch = $this->getSearchForm($formColumns);

        $pageData = PageLanguageData::getAttributesByKey($this->pageKey, $this->siteLangId);
        $pageTitle = $pageData['plang_title'] ?? LibHelper::getControllerName(true);

        $actionItemsData = HtmlHelper::getDefaultActionItems($formColumns);
        $actionItemsData = array_merge($actionItemsData, [
            'newRecordBtn' => false,
            'formColumns' => $formColumns,
            'columnButtons' => true,
            'defaultColumns' => $this->getDefaultColumns(),
            'searchFrmTemplate' => 'products-report/search-form.php'
        ]);

        $this->set('frmSearch', $frmSearch);
        $this->set('formColumns', $formColumns);
        $this->set('pageTitle', $pageTitle);
        $this->set('pageData', $pageData);
        $this->set('actionItemsData', $actionItemsData);
        $this->getListingData(false);
        $this->_template->addJs(array('js/select2.js'));
        $this->_template->addCss(array('css/select2.min.css'));
        $this->_template->render();
    }

    public function search($type = false)
    {
        $batchCount = FatApp::getPostedData('batch_count', FatUtility::VAR_INT, 0);
        $batchNumber = FatApp::getPostedData('batch_number', FatUtility::VAR_INT, 1);
        $this->getListingData($type, $batchCount, $batchNumber);
        $jsonData = [
            'headSection' => $this->_template->render(false, false, '_partial/listing/head-section.php', true),
            'listingHtml' => $this->_template->render(false, false, 'products-report/search.php', true),
            'paginationHtml' => $this->_template->render(false, false, '_partial/listing/listing-foot.php', true)
        ];
        LibHelper::exitWithSuccess($jsonData, true);
    }

    public function getListingData($type = false, $batchCount = 1, $batchNumber = 0)
    {
        $db = FatApp::getDb();

        $fields = $this->getFormColumns();
        $selectedFlds = FatApp::getPostedData('listingColumns', FatUtility::VAR_STRING, '');
        $selectedFlds = !empty($selectedFlds) ? json_decode($selectedFlds) +  $this->getDefaultColumns() : $this->getDefaultColumns();
        $fields =  FilterHelper::parseArrayByKeys($fields, $selectedFlds, true);
        $sortBy = FatApp::getPostedData('sortBy', FatUtility::VAR_STRING, current(array_keys($fields)));
        if (!array_key_exists($sortBy, $fields)) {
            $sortBy = current(array_keys($fields));
        }

        $sortOrder = FatApp::getPostedData('sortOrder', FatUtility::VAR_STRING, applicationConstants::SORT_DESC);
        if (!array_key_exists($sortOrder, applicationConstants::sortOrder($this->siteLangId))) {
            $sortOrder = applicationConstants::SORT_DESC;
        }

        $srchFrm = $this->getSearchForm($fields);
        $post = $srchFrm->getFormDataFromArray(FatApp::getPostedData());
        $page = FatApp::getPostedData('page', FatUtility::VAR_INT, 1);
        $page = ($page <= 0) ? 1 : $page;
        $pageSize = applicationConstants::getPageSize(FatApp::getPostedData('pageSize', FatUtility::VAR_INT));

        /* get Seller Order Products[ */
        $opSrch = new Report(0, array_keys($fields));
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
        $opSrch->removeFld(['product_name', 'followers', 'selprod_price']);
        /* ] */


        /* get Seller product Options[ */
       /*  $spOptionSrch = new SearchBase(SellerProduct::DB_TBL_SELLER_PROD_OPTIONS, 'spo');
        $spOptionSrch->joinTable(OptionValue::DB_TBL, 'INNER JOIN', 'spo.selprodoption_optionvalue_id = ov.optionvalue_id', 'ov');
        $spOptionSrch->joinTable(OptionValue::DB_TBL . '_lang', 'LEFT OUTER JOIN', 'ov_lang.optionvaluelang_optionvalue_id = ov.optionvalue_id AND ov_lang.optionvaluelang_lang_id = ' . $this->siteLangId, 'ov_lang');
        $spOptionSrch->joinTable(Option::DB_TBL, 'INNER JOIN', '`option`.option_id = ov.optionvalue_option_id', '`option`');
        $spOptionSrch->joinTable(Option::DB_TBL . '_lang', 'LEFT OUTER JOIN', '`option`.option_id = option_lang.optionlang_option_id AND option_lang.optionlang_lang_id = ' . $this->siteLangId, 'option_lang');
        $spOptionSrch->doNotCalculateRecords();
        $spOptionSrch->doNotLimitRecords();
        $spOptionSrch->addGroupBy('spo.selprodoption_selprod_id');
        $spOptionSrch->addMultipleFields(array('spo.selprodoption_selprod_id', 'IFNULL(option_name, option_identifier) as option_name', 'IFNULL(optionvalue_name, optionvalue_identifier) as optionvalue_name', 'GROUP_CONCAT(option_name) as grouped_option_name', 'GROUP_CONCAT(optionvalue_name) as grouped_optionvalue_name')); */
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
        // $srch->joinTable('(' . $spOptionSrch->getQuery() . ')', 'LEFT OUTER JOIN', 'selprod_id = spoq.selprodoption_selprod_id', 'spoq');
        $srch->joinTable('(' . $opSrch->getQuery() . ')', 'LEFT OUTER JOIN', 'selprod.selprod_id = opq.op_selprod_id', 'opq');
        $srch->joinTable('(' . $uWsrch->getQuery() . ')', 'LEFT OUTER JOIN', 'tquwl.uwlp_selprod_id = selprod.selprod_id', 'tquwl');
        $srch->joinProductToCategory();
        $srch->addCondition('selprod.selprod_id', '!=', 'NULL');
       
        /* groupby added, because if same product is linked with multiple categories, then showing in repeat for each category[ */
        $srch->addGroupBy('selprod_id');
        /* ] */
                        
        $keyword = FatApp::getPostedData('keyword', FatUtility::VAR_STRING);
        if (!empty($keyword)) {
            $srch->addKeywordSearch($keyword);
        }

        $shop_id = FatApp::getPostedData('shop_id', null, '');
        if ($shop_id) {
            $shop_id = FatUtility::int($shop_id);
            $srch->addShopIdCondition($shop_id);
        }

        $brand_id = FatApp::getPostedData('brand_id', null, '');
        if ($brand_id) {
            $brand_id = FatUtility::int($brand_id);
            $srch->addBrandCondition($brand_id);
        }

        $category_id = FatApp::getPostedData('category_id', null, '');
        if ($category_id) {
            $category_id = FatUtility::int($category_id);
            $srch->addCategoryCondition($category_id);
        }
        
        $this->setRecordCount(clone $srch, $pageSize, $page, $post,true);
        $srch->doNotCalculateRecords();
        $srch->addMultipleFields(array('product_id', 'product_name', 'selprod_id', 'selprod_code', 'selprod_user_id', 'selprod_title', 'selprod_price', /* 'IFNULL(totOrders, 0) as totOrders',  'grouped_option_name', 'grouped_optionvalue_name',*/'IFNULL(s_l.shop_name, shop_identifier) as shop_name', 'IFNULL(tb_l.brand_name, brand_identifier) as brand_name', 'count(distinct tquwl.uwlist_user_id) as followers', 'opq.*'));
        if (!array_key_exists($sortOrder, applicationConstants::sortOrder(CommonHelper::getLangId()))) {
            $sortOrder = applicationConstants::SORT_ASC;
        }
        
        switch ($sortBy) {
            default:
                $srch->addOrder($sortBy, $sortOrder);
                break;
        }

        if ($type == 'export') {
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
            while ($row = $db->fetch($rs)) {
                $result = Report::getSelProdOptions([$row['selprod_id']], $this->siteLangId);
                if (isset($result[$row['selprod_id']]) && !empty($result[$row['selprod_id']])) {
                    $row = array_merge($row, $result[$row['selprod_id']]);
                }

                $arr = [];
                foreach ($fields as $key => $val) {
                    switch ($key) {
                        case 'product_name':
                            $name =  Labels::getLabel('LBL_CATALOG_NAME', $this->siteLangId) . ": " . $row['product_name'];
                            if ($row['selprod_title'] != '') {
                                $name .= "\n" . Labels::getLabel('LBL_CUSTOM_TITLE', $this->siteLangId) . ':' . $row['selprod_title'];
                            }
                            // if ($row['grouped_option_name'] != '') {
                            //     $groupedOptionNameArr = explode(',', $row['grouped_option_name']);
                            //     $groupedOptionValueArr = explode(',', $row['grouped_optionvalue_name']);
                            //     if (!empty($groupedOptionNameArr)) {
                            //         foreach ($groupedOptionNameArr as $key => $optionName) {
                            //             $name .= "\n" . $optionName . ':</strong> ' . $groupedOptionValueArr[$key];
                            //         }
                            //     }
                            // }

                            if ($row['brand_name'] != '') {
                                $name .= "\n" . Labels::getLabel('LBL_BRAND', $this->siteLangId) . ": " . $row['brand_name'];
                            }

                            if ($row['shop_name'] != '') {
                                $name .= "\n" . Labels::getLabel('LBL_SOLD_BY', $this->siteLangId) . ': ' . $row['shop_name'];
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
                        case 'adminShippingTotal':
                        case 'discountTotal':
                        case 'couponDiscount':
                        case 'volumeDiscount':
                        case 'rewardDiscount':
                        case 'refundedAmount':
                        case 'refundedShipping':
                        case 'refundedTax':
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

            CommonHelper::convertToCsv($sheetData, Labels::getLabel('LBL_PRODUCTS_REPORT', $this->siteLangId) . ' ' . date("d-M-Y") . '.csv', ',');
            exit;
        } else {
            $srch->setPageNumber($page);
            $srch->setPageSize($pageSize); 
            $arrListing = $db->fetchAll($srch->getResultSet(), 'selprod_id');
            $result = Report::getSelProdOptions(array_keys($arrListing), $this->siteLangId);
            foreach ($arrListing as &$row) {
                if (isset($result[$row['selprod_id']]) && !empty($result[$row['selprod_id']])) {
                    $row = array_merge($row, $result[$row['selprod_id']]);
                }
            }            
            $this->set("arrListing", $arrListing);
            $this->set('postedData', $post);
            $this->set('sortBy', $sortBy);
            $this->set('sortOrder', $sortOrder);
            $this->set('fields', $fields);
            $this->set('allowedKeysForSorting', array_keys($fields));
        }
    }

    public function form()
    {
        $formTitle = Labels::getLabel('LBL_EXPORT_PRODUCTS_REPORT', $this->siteLangId);
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
        $frm->setFormTagAttribute('onSubmit', 'exportRecords(); return false;');
        return $frm;
    }

    public function export()
    {
        $this->search('export');
    }

    public function getSearchForm($fields = [], $shopArr = [], $brandArr = [])
    {
        $frm = new Form('frmRecordSearch');
        if (!empty($fields)) {
            $this->addSortingElements($frm, 'product_name', applicationConstants::SORT_ASC);
        }
        $fld = $frm->addTextBox(Labels::getLabel('FRM_KEYWORD', $this->siteLangId), 'keyword');
        $fld->overrideFldType('search');

        $frm->addSelectBox(Labels::getLabel('FRM_SHOP', $this->siteLangId), 'shop_id', $shopArr, '', [], '');
        $frm->addSelectBox(Labels::getLabel('FRM_BRAND', $this->siteLangId), 'brand_id', $brandArr, '', [], '');

        $prodCatObj = new ProductCategory();
        $categoriesAssocArr = $prodCatObj->getProdCatTreeStructure(0, $this->siteLangId);
        $frm->addSelectBox(Labels::getLabel('FRM_CATEGORY', $this->siteLangId), 'category_id', $categoriesAssocArr, '', [], Labels::getLabel('FRM_SELECT', $this->siteLangId));
        $frm->addHiddenField('', 'total_record_count'); 
        HtmlHelper::addSearchButton($frm);
        HtmlHelper::addClearButton($frm);/*clearBtn*/
        return $frm;
    }

    protected function getFormColumns()
    {
        $productReportCacheVar = FatCache::get('productReportCacheVar' . $this->siteLangId, CONF_DEF_CACHE_TIME, '.txt');
        if (!$productReportCacheVar) {
            $arr = [
                'product_name'    =>    Labels::getLabel('LBL_PRODUCT_NAME', $this->siteLangId),
                /* 'selprod_title' =>  Labels::getLabel('LBL_CUSTOM_TITLE', $this->siteLangId),
                'brand_name' => Labels::getLabel('LBL_BRAND', $this->siteLangId),
                'shop_name' => Labels::getLabel('LBL_SOLD_BY', $this->siteLangId), */
                'followers'    =>    Labels::getLabel('LBL_FAVORITES', $this->siteLangId),
                'selprod_price'    =>    Labels::getLabel('LBL_UNIT_PRICE', $this->siteLangId),
                'totOrders' => Labels::getLabel('LBL_ORDER_PLACED', $this->siteLangId),
                'totQtys' => Labels::getLabel('LBL_ORDERED_QTY', $this->siteLangId),
                'totRefundedQtys' => Labels::getLabel('LBL_REFUNDED_QTY', $this->siteLangId),
                'netSoldQty' => Labels::getLabel('LBL_SOLD_QTY', $this->siteLangId),
                'grossSales' => Labels::getLabel('LBL_GROSS_SALE', $this->siteLangId),
                'transactionAmount' => Labels::getLabel('LBL_TRANSACTION_AMOUNT', $this->siteLangId),
                'inventoryValue' => Labels::getLabel('LBL_INVENTORY_VALUE', $this->siteLangId),

                'taxTotal' => Labels::getLabel('LBL_TAX_CHARGED', $this->siteLangId),
                'sellerTaxTotal' => Labels::getLabel('LBL_TAX_CHARGED_BY_SELLER', $this->siteLangId),
                'adminTaxTotal' => Labels::getLabel('LBL_TAX_CHARGED_BY_ADMIN', $this->siteLangId),

                'shippingTotal' => Labels::getLabel('LBL_SHIPPING_CHARGED', $this->siteLangId),
                'sellerShippingTotal' => Labels::getLabel('LBL_SHIPPING_CHARGED_BY_SELLER', $this->siteLangId),
                'adminShippingTotal' => Labels::getLabel('LBL_SHIPPING_CHARGED_BY_ADMIN', $this->siteLangId),

                'couponDiscount' => Labels::getLabel('LBL_COUPON_DISCOUNT', $this->siteLangId),
                'volumeDiscount' => Labels::getLabel('LBL_VOLUME_DISCOUNT', $this->siteLangId),
                'rewardDiscount' => Labels::getLabel('LBL_REWARD_DISCOUNT', $this->siteLangId),

                'refundedAmount' => Labels::getLabel('LBL_REFUNDED_AMOUNT', $this->siteLangId),
                'refundedShipping' => Labels::getLabel('LBL_REFUNDED_SHIPPING', $this->siteLangId),
                'refundedTax' => Labels::getLabel('LBL_REFUNDED_TAX', $this->siteLangId),

                'orderNetAmount' => Labels::getLabel('LBL_NET_AMOUNT', $this->siteLangId),

                'commissionCharged' => Labels::getLabel('LBL_COMMISION_CHARGED', $this->siteLangId),
                'refundedCommission' => Labels::getLabel('LBL_REFUNDED_COMMISION', $this->siteLangId),
                'adminSalesEarnings' => Labels::getLabel('LBL_SALES_EARNINGS', $this->siteLangId)
            ];
            FatCache::set('productReportCacheVar' . $this->siteLangId, serialize($arr), '.txt');
        } else {
            $arr =  unserialize($productReportCacheVar);
        }

        return $arr;
    }

    protected function getDefaultColumns(): array
    {
        return ['product_name', 'netSoldQty', 'grossSales', 'couponDiscount', 'refundedAmount', 'taxTotal', 'shippingTotal', 'orderNetAmount'];
    }

    public function getBreadcrumbNodes($action)
    {
        switch ($action) {
            case 'index':
                $pageData = PageLanguageData::getAttributesByKey($this->pageKey, $this->siteLangId);
                $pageTitle = $pageData['plang_title'] ?? LibHelper::getControllerName(true);
                $this->nodes = [
                    ['title' => Labels::getLabel('NAV_REPORTS', $this->siteLangId)],
                    ['title' => Labels::getLabel('NAV_SALES_REPORTS', $this->siteLangId)],
                    ['title' => $pageTitle]
                ];
                break;
            default:
                parent::getBreadcrumbNodes($action);
                break;
        }
        return $this->nodes;
    }
}
