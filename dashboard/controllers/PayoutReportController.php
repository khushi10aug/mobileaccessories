<?php

class PayoutReportController extends SellerBaseController
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
        $page = (empty($post['page']) || $post['page'] <= 0) ? 1 : intval($post['page']);
        $pagesize = FatApp::getConfig('CONF_PAGE_SIZE', FatUtility::VAR_INT, 10);
        $fromDate = FatApp::getPostedData('date_from', FatUtility::VAR_DATE, '');
        $toDate = FatApp::getPostedData('date_to', FatUtility::VAR_DATE, '');

        /* Promotion Charges */
        $pSrch = new SearchBase(Promotion::DB_TBL_CHARGES, 'pc');
        $pSrch->doNotCalculateRecords();
        $pSrch->doNotLimitRecords();
        $pSrch->addGroupBy('Date(pc.pcharge_date)');
        $pSrch->addCondition('pcharge_user_id', '=', $this->userParentId);
        $pSrch->addMultipleFields(['Date(pc.pcharge_date) as date']);

        /* Sales Earnings */
        $opSrch = new Report(0, [], true);
        $opSrch->addMultipleFields(['DATE(o.order_date_added) as date']);
        $opSrch->joinOrders();
        $opSrch->joinPaymentMethod();
        $opSrch->joinOtherCharges(true);
        $opSrch->joinOrderProductTaxCharges(false);
        $opSrch->joinOrderProductShipCharges(false);
        $opSrch->setPaymentStatusCondition();
        $opSrch->setCompletedOrdersCondition();
        $opSrch->excludeDeletedOrdersCondition();
        $opSrch->setGroupBy('DATE(o.order_date_added)');
        $opSrch->doNotCalculateRecords();
        $opSrch->doNotLimitRecords();
        $opSrch->setDateCondition($fromDate, $toDate);
        $opSrch->addCondition('op_selprod_user_id', '=', $this->userParentId);

        /* Subscription earning */
        $sSrch = new OrderSubscriptionSearch($this->siteLangId, true, true);
        $sSrch->joinSubscription();
        $sSrch->joinOrderUser();
        $sSrch->joinOtherCharges();
        $sSrch->addCondition('order_type', '=', Orders::ORDER_SUBSCRIPTION);
        $sSrch->addGroupBy('DATE(o.order_date_added)');
        $sSrch->addMultipleFields(['DATE(o.order_date_added) as date']);
        $sSrch->doNotCalculateRecords();
        $sSrch->doNotLimitRecords();
        $sSrch->addCompletedOrderCondition();
        $sSrch->addCondition('order_user_id', '=', $this->userParentId);

        $query = $pSrch->getQuery() . ' UNION ALL ' . $opSrch->getQuery() . ' UNION ALL ' . $sSrch->getQuery();
        $srch = new SearchBase("(" . $query . ") as tmp");
        $srch->addMultipleFields(['tmp.date', 'psrch.promotionCharged', 'opSrch.adminSalesEarnings', 'opSrch.sellerTaxTotal', 'opSrch.sellerShippingTotal', 'sSrch.subscriptionCharges', 'ifnull(psrch.promotionCharged,0) + ifnull(opSrch.adminSalesEarnings,0) + ifnull(opSrch.sellerTaxTotal,0) + ifnull(opSrch.sellerShippingTotal,0) + ifnull(sSrch.subscriptionCharges,0) as totalAmount']);
        $pSrch->addMultipleFields(['SUM(pc.pcharge_charged_amount) as promotionCharged']);
        $opSrch->addMultipleFields(['sum((IFNULL(op_commission_charged,0) - IFNULL(op_refund_commission,0))) as adminSalesEarnings', 'SUM(if(opst.op_tax_collected_by_seller > 0,IFNULL(optax.opcharge_amount,0),0) - if(opst.op_tax_collected_by_seller > 0,IFNULL(op.op_refund_tax,0),0)) as sellerTaxTotal', 'SUM(if(ops.opshipping_by_seller_user_id > 0,IFNULL(opship.opcharge_amount,0),0) - if(ops.opshipping_by_seller_user_id > 0,IFNULL(op.op_refund_shipping,0),0)) as sellerShippingTotal']);
        $sSrch->addMultipleFields(['sum(ossubs_price + ifnull(op_other_charges,0)) as subscriptionCharges']);
        $srch->joinTable('(' . $pSrch->getQuery() . ')', 'LEFT OUTER JOIN', 'tmp.date = psrch.date', 'psrch');
        $srch->joinTable('(' . $opSrch->getQuery() . ')', 'LEFT OUTER JOIN', 'tmp.date = opSrch.date', 'opSrch');
        $srch->joinTable('(' . $sSrch->getQuery() . ')', 'LEFT OUTER JOIN', 'tmp.date = sSrch.date', 'sSrch');
        $srch->addGroupBy('date');
        if (!empty($fromDate)) {
            $srch->addCondition('tmp.date', '>=', $fromDate . ' 00:00:00');
        }

        if (!empty($toDate)) {
            $srch->addCondition('tmp.date', '<=', $toDate . ' 23:59:59');
        }
        $this->setRecordCount(clone $srch, $pagesize, $page, $post, true);
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
            $count = 1;
            while ($row = FatApp::getDb()->fetch($rs)) {
                $arr = [];
                foreach ($fields as $key => $val) {
                    switch ($key) {
                        case 'listserial':
                            $arr[] = $count;
                            break;
                        case 'date':
                            $arr[] = FatDate::format($row[$key]);
                            break;
                        case 'subscriptionCharges':
                        case 'promotionCharged':
                        case 'sellerTaxTotal':
                        case 'sellerShippingTotal':
                        case 'adminSalesEarnings':
                        case 'totalAmount':
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

            CommonHelper::convertToCsv($sheetData, Labels::getLabel("LBL_Payout_Report", $this->siteLangId) . '.csv', ',');
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
        $formTitle = Labels::getLabel('LBL_PAYOUT_REPORT', $this->siteLangId);
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
        $frm->addHiddenField('', 'total_record_count');
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
        $sellerPayoutReportsCacheVar = CacheHelper::get('sellerPayoutReportsCacheVar' . $this->siteLangId, CONF_DEF_CACHE_TIME, '.txt');
        if (!$sellerPayoutReportsCacheVar) {
            $arr = [
                'date' => Labels::getLabel('LBL_Date', $this->siteLangId),
                'subscriptionCharges' => Labels::getLabel('LBL_Subscription_Charges', $this->siteLangId),
                'promotionCharged' => Labels::getLabel('LBL_Advertisement_Charges', $this->siteLangId),
                'sellerTaxTotal' => Labels::getLabel('LBL_Tax_Charged', $this->siteLangId),
                'sellerShippingTotal' => Labels::getLabel('LBL_Shipping_Charged', $this->siteLangId),
                'adminSalesEarnings' => Labels::getLabel('LBL_Admin_Earnings', $this->siteLangId),
                'totalAmount' => Labels::getLabel('LBL_Total_Amount', $this->siteLangId),
            ];
            CacheHelper::create('sellerPayoutReportsCacheVar' . $this->siteLangId, serialize($arr), CacheHelper::TYPE_LABELS);
        } else {
            $arr = unserialize($sellerPayoutReportsCacheVar);
        }

        return $arr;
    }

    protected function getDefaultColumns(): array
    {
        return ['date', 'promotionCharged', 'sellerTaxTotal', 'sellerShippingTotal', 'totalAmount'];
    }
}
