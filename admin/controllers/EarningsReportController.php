<?php

class EarningsReportController extends ListingBaseController
{
    protected $pageKey = 'REPORT_EARNINGS';

    public function __construct($action)
    {
        parent::__construct($action);
        $this->objPrivilege->canViewFinancialReport();
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
        $this->getListingData(false);
        $this->_template->render();
    }

    public function search($type = false)
    {
        $batchCount = FatApp::getPostedData('batch_count', FatUtility::VAR_INT, 0);
        $batchNumber = FatApp::getPostedData('batch_number', FatUtility::VAR_INT, 1);
        $this->getListingData($type, $batchCount, $batchNumber);
        $jsonData = [
            'headSection' => $this->_template->render(false, false, '_partial/listing/head-section.php', true),
            'listingHtml' => $this->_template->render(false, false, 'earnings-report/search.php', true),
            'paginationHtml' => $this->_template->render(false, false, '_partial/listing/listing-foot.php', true)
        ];
        LibHelper::exitWithSuccess($jsonData, true);
    }

    public function getListingData($type = false, $batchCount = 1, $batchNumber = 0)
    {
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

        $fromDate = FatApp::getPostedData('date_from', FatUtility::VAR_DATE, '');
        $toDate = FatApp::getPostedData('date_to', FatUtility::VAR_DATE, '');

        /* Promotion Charges */
        $pSrch = new SearchBase(Promotion::DB_TBL_CHARGES, 'pc');
        $pSrch->doNotCalculateRecords();
        $pSrch->doNotLimitRecords();
        $pSrch->addGroupBy('Date(pc.pcharge_date)');
        $pSrch->addMultipleFields(['Date(pc.pcharge_date) as date']);

        /* Admin Sales Earnings */
        $opSrch = new Report();
        $opSrch->addMultipleFields(['DATE(o.order_date_added) as date']);
        $opSrch->joinOrders();
        $opSrch->joinPaymentMethod();
        $opSrch->joinOtherCharges(true);
        $opSrch->setPaymentStatusCondition();
        $opSrch->setCompletedOrdersCondition();
        $opSrch->excludeDeletedOrdersCondition();
        $opSrch->setGroupBy('DATE(o.order_date_added)');
        $opSrch->doNotCalculateRecords();
        $opSrch->doNotLimitRecords();
        $opSrch->setDateCondition($fromDate, $toDate);

        /* Subscription earning */
        $sSrch = new OrderSubscriptionSearch($this->siteLangId, true, true);
        $sSrch->joinSubscription();
        $sSrch->joinOrderUser();
        $sSrch->joinOtherCharges();
        $sSrch->addCondition('order_type', '=', 'mysql_func_' . Orders::ORDER_SUBSCRIPTION, 'AND', true);
        $sSrch->addGroupBy('DATE(o.order_date_added)');
        $sSrch->addMultipleFields(['DATE(o.order_date_added) as date']);
        $sSrch->doNotCalculateRecords();
        $sSrch->doNotLimitRecords();
        $sSrch->addCompletedOrderCondition();

        $query = $pSrch->getQuery() . ' UNION ALL ' . $opSrch->getQuery() . ' UNION ALL ' . $sSrch->getQuery();
        $srch = new SearchBase("(" . $query . ") as tmp");
        $srch->addMultipleFields(['tmp.date', 'psrch.promotionCharged', 'opSrch.adminSalesEarnings', 'sSrch.subscriptionCharges', 'ifnull(psrch.promotionCharged,0) + ifnull(opSrch.adminSalesEarnings,0) + ifnull(sSrch.subscriptionCharges,0) as totalEarning']);
        $pSrch->addMultipleFields(['SUM(pc.pcharge_charged_amount) as promotionCharged']);
        $opSrch->addMultipleFields(['sum((IFNULL(op_commission_charged,0) - IFNULL(op_refund_commission,0))) as adminSalesEarnings']);
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

            $count = 1;
            while ($row = FatApp::getDb()->fetch($rs)) {
                $arr = [];
                foreach ($fields as $key => $val) {
                    switch ($key) {
                        case 'listSerial':
                            $arr[] = $count;
                            break;
                        case 'subscriptionCharges':
                        case 'promotionCharged':
                        case 'adminSalesEarnings':
                        case 'totalEarning':
                            $arr[] = CommonHelper::displayMoneyFormat($row[$key], true, true);
                            break;
                        default:
                            $arr[] = $row[$key];
                            break;
                    }
                }

                array_push($sheetData, $arr);
                $count++;
            }

            CommonHelper::convertToCsv($sheetData, Labels::getLabel('LBL_Earnings_Report', $this->siteLangId) . date("d-M-Y") . '.csv', ',');
            exit;
        }

        $srch->setPageNumber($page);
        $srch->setPageSize($pageSize);
        $rs = $srch->getResultSet();
        $arrListing = FatApp::getDb()->fetchAll($rs);

        $this->set("arrListing", $arrListing);
        $this->set('pageCount', $srch->pages());
        $this->set('recordCount', $srch->recordCount());
        $this->set('page', $page);
        $this->set('pageSize', $pageSize);
        $this->set('postedData', $post);
        $this->set('sortBy', $sortBy);
        $this->set('sortOrder', $sortOrder);
        $this->set('fields', $fields);
        $this->set('allowedKeysForSorting', array_keys($fields));
    }

    public function export()
    {
        $this->search('export');
    }

    public function form()
    {
        $formTitle = Labels::getLabel('LBL_EXPORT_EARNINGS_REPORT', $this->siteLangId);
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
            $this->addSortingElements($frm, 'date', applicationConstants::SORT_DESC);
        }
        $frm->addDateField(Labels::getLabel('FRM_DATE_FROM', $this->siteLangId), 'date_from', '', array('placeholder' => Labels::getLabel('FRM_DATE_FROM', $this->siteLangId), 'readonly' => 'readonly', 'class' => 'small dateTimeFld field--calender'));
        $frm->addDateField(Labels::getLabel('FRM_DATE_TO', $this->siteLangId), 'date_to', '', array('placeholder' => Labels::getLabel('FRM_DATE_TO', $this->siteLangId), 'readonly' => 'readonly', 'class' => 'small dateTimeFld field--calender'));
        HtmlHelper::addSearchButton($frm);
        HtmlHelper::addClearButton($frm);

        return $frm;
    }

    protected function getFormColumns()
    {
        $earningsReportsCacheVar = FatCache::get('earningsReportsCacheVar' . $this->siteLangId, CONF_DEF_CACHE_TIME, '.txt');
        if (!$earningsReportsCacheVar) {
            $arr = [
                'date' => Labels::getLabel('LBL_DATE', $this->siteLangId),
                'subscriptionCharges' => Labels::getLabel('LBL_SUBSCRIPTION_CHARGES', $this->siteLangId),
                'promotionCharged' => Labels::getLabel('LBL_ADVERTISEMENT_CHARGES', $this->siteLangId),
                'adminSalesEarnings' => Labels::getLabel('LBL_SALES_EARNINGS', $this->siteLangId),
                'totalEarning' => Labels::getLabel('LBL_TOTAL_EARNINGS', $this->siteLangId),
            ];
            FatCache::set('earningsReportsCacheVar' . $this->siteLangId, serialize($arr), '.txt');
        } else {
            $arr =  unserialize($earningsReportsCacheVar);
        }

        return $arr;
    }

    protected function getDefaultColumns(): array
    {
        return ['date', 'subscriptionCharges', 'promotionCharged', 'adminSalesEarnings', 'totalEarning'];
    }
}
