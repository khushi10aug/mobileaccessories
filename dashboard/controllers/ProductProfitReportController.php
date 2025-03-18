<?php

class ProductProfitReportController extends SellerBaseController
{

    public function __construct($action)
    {
        parent::__construct($action);
        $this->userPrivilege->canViewFinancialReport();
    }

    public function index()
    {
        $fields = $this->getFormColumns();
        $frmSearch = $this->getSearchForm($fields);
        $this->set('frmSearch', $frmSearch);
        $this->set('defaultColumns', $this->getDefaultColumns());
        $this->set('fields', $fields);
        $this->_template->addJs('js/report.js');
        $this->_template->render();
    }

    public function search($type = false)
    {
        $fields = $this->getFormColumns();
        $selectedFlds = FatApp::getPostedData('reportColumns', FatUtility::VAR_STRING, '');
        $selectedFlds = !empty($selectedFlds) ? json_decode($selectedFlds) +  $this->getDefaultColumns() : $this->getDefaultColumns();
        $fields =  FilterHelper::parseArrayByKeys($fields, $selectedFlds, true);
        $sortBy = FatApp::getPostedData('sortBy', FatUtility::VAR_STRING, current(array_keys($fields)));
        if (!array_key_exists($sortBy, $fields)) {
            $sortBy = current(array_keys($fields));
        }

        $sortOrder = FatApp::getPostedData('sortOrder', FatUtility::VAR_STRING, applicationConstants::SORT_ASC);
        if (!array_key_exists($sortOrder, applicationConstants::sortOrder($this->siteLangId))) {
            $sortOrder = applicationConstants::SORT_ASC;
        }
        $srchFrm = $this->getSearchForm($fields);

        $post = $srchFrm->getFormDataFromArray(FatApp::getPostedData());
        $page = (empty($post['page']) || $post['page'] <= 0) ? 1 : intval($post['page']);
        $pagesize = FatApp::getConfig('CONF_PAGE_SIZE', FatUtility::VAR_INT, 10);
        $fromDate = FatApp::getPostedData('date_from', FatUtility::VAR_DATE, '');
        $toDate = FatApp::getPostedData('date_to', FatUtility::VAR_DATE, '');

        $opSrch = new Report(1,  array_keys($fields), true);
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
        $opSrch->setGroupBy('product_id');
        $opSrch->doNotCalculateRecords();
        $opSrch->doNotLimitRecords();
        $opSrch->addTotalOrdersCount('product_id');
        // $opSrch->setOrderBy($sortBy, $sortOrder);
        $opSrch->setDateCondition($fromDate, $toDate);
        $opSrch->removeFld(['product_name', 'category_name']);
        $opSrch->addCondition('op.op_selprod_user_id', '=', $this->userParentId);

        $srch = new ProductSearch($this->siteLangId, '', '', false, false, false);
        $srch->joinBrands($this->siteLangId, false, true);
        $srch->joinProductToCategory();
        $srch->joinTable('(' . $opSrch->getQuery() . ')', 'INNER JOIN', 'p.product_id = opq.product_id', 'opq');
        $srch->addMultipleFields(['COALESCE(tp_l.product_name, p.product_identifier) as product_name', 'COALESCE(c_l.prodcat_name,c.prodcat_identifier) as category_name', 'opq.*']);

        if (!array_key_exists($sortOrder, applicationConstants::sortOrder(CommonHelper::getLangId()))) {
            $sortOrder = applicationConstants::SORT_ASC;
        }
        $this->setRecordCount(clone $srch, $pagesize, $page, $post);
        $srch->doNotCalculateRecords();
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

            $count = 1;
            while ($row = FatApp::getDb()->fetch($rs)) {
                $arr = [];
                foreach ($fields as $key => $val) {
                    switch ($key) {
                        case 'listserial':
                            $arr[] = $count;
                            break;
                        case 'transactionAmount':
                        case 'adminSalesEarnings':
                        case 'orderNetAmount':
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
            CommonHelper::convertToCsv($sheetData, Labels::getLabel('LBL_Product_Profit_Report', $this->siteLangId) . '_' . date("d-M-Y") . '.csv', ',');
            exit;
        }

        $srch->setPageNumber($page);
        $srch->setPageSize($pagesize);
        $this->set("arrListing", FatApp::getDb()->fetchAll($srch->getResultSet()));
        $this->set('postedData', $post);
        $this->set('sortBy', $sortBy);
        $this->set('sortOrder', $sortOrder);
        $this->set('fields', $fields);
        $this->_template->render(false, false);
    }

    public function export()
    {
        $this->search('export');
    }

    
    public function form()
    {
        $formTitle = Labels::getLabel('LBL_PRODUCT_PROFIT_REPORT', $this->siteLangId);
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
        $frm->addHiddenField('', 'page', 1);
        $frm->addHiddenField('', 'total_record_count', '');
        if (!empty($fields)) {
            $frm->addHiddenField('', 'sortBy', 'product_name');
            $frm->addHiddenField('', 'sortOrder', applicationConstants::SORT_ASC);
            $frm->addHiddenField('', 'reportColumns', '');
        }
        $frm->addDateField(Labels::getLabel('FRM_DATE_FROM', $this->siteLangId), 'date_from', '', array('readonly' => 'readonly', 'class' => 'small dateTimeFld field--calender'));
        $frm->addDateField(Labels::getLabel('FRM_DATE_TO', $this->siteLangId), 'date_to', '', array('readonly' => 'readonly', 'class' => 'small dateTimeFld field--calender'));

        HtmlHelper::addSearchButton($frm);
        HtmlHelper::addClearButton($frm, 'btn btn-clear');

        return $frm;
    }

    private function getFormColumns()
    {
        $productProfitReportsCacheVar = CacheHelper::get('productProfitReportsCacheVar' . $this->siteLangId, CONF_DEF_CACHE_TIME, '.txt');
        if (!$productProfitReportsCacheVar) {
            $arr = [
                'product_name'    =>    Labels::getLabel('LBL_Product_name', $this->siteLangId),
                'category_name' => Labels::getLabel('LBL_Category', $this->siteLangId),
                'sellerCost' => Labels::getLabel('LBL_Cost', $this->siteLangId),
                'transactionAmount' => Labels::getLabel('LBL_Transaction_Amount', $this->siteLangId),
                'orderNetAmount' => Labels::getLabel('LBL_Net_Amount', $this->siteLangId),
                'sellerEarnings' => Labels::getLabel('LBL_Earnings', $this->siteLangId)
            ];
            CacheHelper::create('productProfitReportsCacheVar' . $this->siteLangId, serialize($arr), CacheHelper::TYPE_LABELS);
        } else {
            $arr =  unserialize($productProfitReportsCacheVar);
        }

        return $arr;
    }

    protected function getDefaultColumns(): array
    {
        return ['product_name', 'category_name', 'sellerCost', 'sellerEarnings'];
    }
}
