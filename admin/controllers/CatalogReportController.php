<?php

class CatalogReportController extends ListingBaseController
{
    protected $pageKey = 'REPORT_PRODUCTS';
    
    public function __construct($action)
    {
        parent::__construct($action);
        $this->objPrivilege->canViewCatalogReport();
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
            'defaultColumns' => $this->getDefaultColumns()
        ]);
        $this->set('pageData', $pageData);
        $this->set('pageTitle', $pageTitle);
        $this->set('frmSearch', $frmSearch);
        $this->set('actionItemsData', $actionItemsData);
        $this->set('keywordPlaceholder', Labels::getLabel('FRM_SEARCH_BY_PRODUCT_NAME', $this->siteLangId));
        $this->getListingData(false);
        $this->_template->render(true, true, '_partial/listing/reports-index.php');
    }

    public function search($type = false)
    {
        $batchCount = FatApp::getPostedData('batch_count', FatUtility::VAR_INT, 0);
        $batchNumber = FatApp::getPostedData('batch_number', FatUtility::VAR_INT, 1);
        $this->getListingData($type, $batchCount, $batchNumber);
        $jsonData = [
            'headSection' => $this->_template->render(false, false, '_partial/listing/head-section.php', true),
            'listingHtml' => $this->_template->render(false, false, 'catalog-report/search.php', true),
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

        $sortOrder = applicationConstants::getSortOrder(FatApp::getPostedData('sortOrder', FatUtility::VAR_STRING));

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
        $opSrch->addTotalOrdersCount('product_id');
        $opSrch->setGroupBy('product_id');
        $opSrch->doNotCalculateRecords();
        $opSrch->doNotLimitRecords();
        $opSrch->removeFld(['product_name', 'product_type', 'prodcat_name']);
        /* ] */

        $selectedFlds = ['p.product_id', 'IFNULL(tp_l.product_name,p.product_identifier) as product_name', 'p.product_type', 'IFNULL(tb_l.brand_name, brand_identifier) as brand_name', 'IFNULL(c_l.prodcat_name,c.prodcat_identifier) as prodcat_name', 'opq.*'];
        $srch = new ProductSearch($this->siteLangId, '', '', false, false, false);
        $srch->joinBrands($this->siteLangId, false, true);
        $srch->joinProductToCategory($this->siteLangId);
        $srch->joinTable('(' . $opSrch->getQuery() . ')', 'INNER JOIN', 'p.product_id = opq.product_id', 'opq');
        $srch->addGroupBy('p.product_id'); 
        $keyword = FatApp::getPostedData('keyword', FatUtility::VAR_STRING);
        if (!empty($keyword)) {
            $srch->addCondition('product_name', 'LIKE', '%' . $keyword . '%');
        }
        
        if (!array_key_exists($sortOrder, applicationConstants::sortOrder($this->siteLangId))) {
            $sortOrder = applicationConstants::SORT_ASC;
        }

        switch ($sortBy) {
            default:
                $srch->addOrder($sortBy, $sortOrder);
                break;
        }
        $this->setRecordCount(clone $srch, $pageSize, $page, $post,true);
        $srch->doNotCalculateRecords();  
        
        $srch->addMultipleFields($selectedFlds);
        $productTypeArr = Product::getProductTypes($this->siteLangId);

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
                $arr = [];
                foreach ($fields as $key => $val) {
                    switch ($key) {
                        case 'product_name':
                            $name = $row['product_name'] . '(' . $row['brand_name'] . ')';
                            $arr[] = $name;
                            break;
                        case 'product_type':
                            $arr[] = $productTypeArr[$row[$key]];
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

            CommonHelper::convertToCsv($sheetData, Labels::getLabel('LBL_CATALOG_REPORT', $this->siteLangId) . ' ' . date("d-M-Y") . '.csv', ',');

            exit;
        }

        $srch->setPageNumber($page);
        $srch->setPageSize($pageSize);   
        $this->set("arrListing", $db->fetchAll($srch->getResultSet())); 
        $this->set('postedData', $post);
        $this->set('sortBy', $sortBy);
        $this->set('sortOrder', $sortOrder);
        $this->set('fields', $fields);
        $this->set('productTypeArr', $productTypeArr);
        $this->set('allowedKeysForSorting', array_keys($fields));
    }

    public function export()
    {
        $this->search('export');
    }

    public function form()
    {
        $formTitle = Labels::getLabel('LBL_EXPORT_CATALOG_REPORT', $this->siteLangId);
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

    public function getSearchForm($fields = [])
    {
        $frm = new Form('frmRecordSearch');
        if (!empty($fields)) {
            $this->addSortingElements($frm, 'orderDate', applicationConstants::SORT_DESC);
        }
        $fld = $frm->addTextBox(Labels::getLabel('FRM_KEYWORD', $this->siteLangId), 'keyword');
        $fld->overrideFldType('search');
        $frm->addHiddenField('', 'total_record_count'); 
        HtmlHelper::addSearchButton($frm);
        HtmlHelper::addClearButton($frm);
        return $frm;
    }

    protected function getFormColumns()
    {
        $catalogReportCacheVar = FatCache::get('catalogReportCacheVar' . $this->siteLangId, CONF_DEF_CACHE_TIME, '.txt');
        if (!$catalogReportCacheVar) {
            $arr = [
                'product_name'    =>    Labels::getLabel('LBL_PRODUCT', $this->siteLangId),
                'product_type'    =>    Labels::getLabel('LBL_PRODUCT_TYPE', $this->siteLangId),
                'prodcat_name'    =>    Labels::getLabel('LBL_CATEGORY', $this->siteLangId),
                'totOrders' => Labels::getLabel('LBL_NO._of_Orders', $this->siteLangId),
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
            FatCache::set('catalogReportCacheVar' . $this->siteLangId, serialize($arr), '.txt');
        } else {
            $arr =  unserialize($catalogReportCacheVar);
        }

        return $arr;
    }

    protected function getDefaultColumns(): array
    {
        return ['product_name', 'product_type', 'prodcat_name', 'netSoldQty', 'grossSales', 'couponDiscount', 'refundedAmount', 'taxTotal', 'shippingTotal', 'orderNetAmount'];
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
