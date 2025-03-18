<?php

class CatalogReportController extends SellerBaseController
{

    public function __construct($action)
    {
        parent::__construct($action);
        $this->userPrivilege->canViewCatalogReport(UserAuthentication::getLoggedUserId());
    }

    public function index()
    {
        $fields = $this->getFormColumns($this->siteLangId);
        $frmSearch = $this->getSearchForm($fields);
        $this->set('frmSearch', $frmSearch);
        $this->set('defaultColumns', $this->getDefaultColumns());
        $this->set('fields', $fields);
        $this->set('keywordPlaceholder', Labels::getLabel('LBL_SEARCH_BY_PRODUCT_NAME', $this->siteLangId));
        $this->_template->addJs('js/report.js');
        $this->_template->render();
    }

    public function search($type = false)
    { 
        $batchCount = FatApp::getPostedData('batch_count', FatUtility::VAR_INT, 0);
        $batchNumber = FatApp::getPostedData('batch_number', FatUtility::VAR_INT, 1);
        $db = FatApp::getDb();
        $fields = $this->getFormColumns($this->siteLangId);
        $selectedFlds = FatApp::getPostedData('reportColumns', FatUtility::VAR_STRING, '');
        $selectedFlds = !empty($selectedFlds) ? json_decode($selectedFlds) + $this->getDefaultColumns() : $this->getDefaultColumns();
        $fields = FilterHelper::parseArrayByKeys($fields, $selectedFlds, true);
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
        if ($page < 2) {
            $page = 1;
        }
        $pageSize = FatApp::getConfig('CONF_PAGE_SIZE', FatUtility::VAR_INT, 10);
        $keyword = FatApp::getPostedData('keyword', FatUtility::VAR_STRING, '');

        /* get Seller Order Products[ */
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
        $opSrch->addTotalOrdersCount('product_id');
        $opSrch->setGroupBy('product_id');
        $opSrch->doNotCalculateRecords();
        $opSrch->doNotLimitRecords();
        $opSrch->removeFld(['product_name', 'product_type', 'prodcat_name']);
        $opSrch->addCondition('op.op_selprod_user_id', '=', $this->userParentId);

        // echo  $opSrch->getQuery(); exit;
        /* ] */

        $selectedFlds = ['p.product_id', 'IFNULL(tp_l.product_name,p.product_identifier) as product_name', 'p.product_type', 'IFNULL(tb_l.brand_name, brand_identifier) as brand_name', 'IFNULL(c_l.prodcat_name,c.prodcat_identifier) as prodcat_name', 'opq.*'];
        $srch = new ProductSearch($this->siteLangId, '', '', false, false, false);
        $srch->joinBrands($this->siteLangId, false, true);
        $srch->joinProductToCategory();
        $srch->joinTable('(' . $opSrch->getQuery() . ')', 'INNER JOIN', 'p.product_id = opq.product_id', 'opq');

        if (!empty($keyword)) {
            $srch->addCondition('product_name', 'LIKE', '%' . $keyword . '%');
        }

        if (!array_key_exists($sortOrder, applicationConstants::sortOrder(CommonHelper::getLangId()))) {
            $sortOrder = applicationConstants::SORT_ASC;
        }

        switch ($sortBy) {
            default:
                $srch->addOrder($sortBy, $sortOrder);
                break;
        }
        $srch->addFld('p.product_id');
        $srch->addGroupBy('p.product_id');
        $this->setRecordCount(clone $srch, $pageSize, $page, $post, true);
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
            CommonHelper::convertToCsv($sheetData, Labels::getLabel('LBL_Catalog_Report', $this->siteLangId) . ' ' . date("d-M-Y") . '.csv', ',');
            exit;
        }

        $srch->setPageNumber($page);
        $srch->setPageSize($pageSize);
        $rs = $srch->getResultSet();
        $arrListing = $db->fetchAll($rs);
        $this->set("arrListing", $arrListing);
        $this->set('postedData', $post);
        $this->set('sortBy', $sortBy);
        $this->set('sortOrder', $sortOrder);
        $this->set('fields', $fields);
        $this->_template->render(false, false);
    }

    public function export()
    {
        $this->search("export");
    }

    
    public function form()
    {
        $formTitle = Labels::getLabel('LBL_CATALOG_REPORT', $this->siteLangId);
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

    private function getSearchForm($fields = [])
    {
        $frm = new Form('frmReportSearch');
        $frm->addHiddenField('', 'total_record_count');
        $frm->addHiddenField('', 'page');
        $frm->addTextBox('', 'keyword');
        if (!empty($fields)) {
            $frm->addHiddenField('', 'sortBy', 'product_name');
            $frm->addHiddenField('', 'sortOrder', applicationConstants::SORT_ASC);
            $frm->addHiddenField('', 'reportColumns', '');
        }
        HtmlHelper::addSearchButton($frm);
        HtmlHelper::addClearButton($frm, 'btn btn-clear');
        return $frm;
    }

    private function getFormColumns(int $langId)
    {
        $sellerCatalogReportCacheVar = CacheHelper::get('sellerCatalogReportCacheVar' . $langId, CONF_DEF_CACHE_TIME, '.txt');
        if (!$sellerCatalogReportCacheVar) {
            $arr = [
                'product_name' => Labels::getLabel('LBL_PRODUCT_NAME', $langId),
                'totOrders' => Labels::getLabel('LBL_No._of_Orders', $langId),
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
            CacheHelper::create('sellerCatalogReportCacheVar' . $this->siteLangId, serialize($arr), CacheHelper::TYPE_LABELS);
        } else {
            $arr = unserialize($sellerCatalogReportCacheVar);
        }

        return $arr;
    }

    protected function getDefaultColumns(): array
    {
        return ['product_name', 'product_type', 'prodcat_name', 'netSoldQty', 'grossSales', 'couponDiscount', 'refundedAmount', 'taxTotal', 'shippingTotal', 'orderNetAmount'];
    }
}
