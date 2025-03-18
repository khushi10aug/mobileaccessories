<?php

class PreferredPaymentMethodController extends ListingBaseController
{
    protected $pageKey = 'REPORT_PREFERRED_PAYMENT_METHOD';

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
        $this->set('keywordPlaceholder', Labels::getLabel('FRM_SEARCH_BY_PAYMENT_METHOD_OR_BILLING_ADDRESS', $this->siteLangId));
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
            'listingHtml' => $this->_template->render(false, false, 'preferred-payment-method/search.php', true),
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
        $keyword = FatApp::getPostedData('keyword', null, '');
        $fromDate = FatApp::getPostedData('date_from', FatUtility::VAR_DATE, '');
        $toDate = FatApp::getPostedData('date_to', FatUtility::VAR_DATE, '');

        $srch = new Report($this->siteLangId);
        $srch->joinOrders();
        $srch->joinPaymentMethod();
        $srch->setPaymentStatusCondition();
        $srch->setCompletedOrdersCondition();
        $srch->excludeDeletedOrdersCondition();
        $srch->joinOrderUserAddress();
        $srch->joinOrderPayments();
        $srch->setGroupBy('orderDate');
        $srch->setGroupBy('opaym.opayment_method');
        $srch->setGroupBy('CONCAT(oua_country, " / ", oua_state)');
        $srch->setDateCondition($fromDate, $toDate);
        $srch->addMultipleFields(['DATE(o.order_date_added) as orderDate', 'COALESCE(pm_l.plugin_name, pm.plugin_identifier, opaym.opayment_method) as pluginName', 'CONCAT(oua_country, " / ", oua_state) as  billingAddress', 'sum(ifnull(opayment_amount, 0)) as transactionAmount']);

        if (!empty($keyword)) {
            $cnd = $srch->addCondition('pm.plugin_identifier', 'like', '%' . $keyword . '%');
            $cnd->attachCondition('pm_l.plugin_name', 'like', '%' . $keyword . '%');
            $cnd->attachCondition('mysql_func_CONCAT(oua_country, " / ", oua_state)', 'like', '%' . $keyword . '%', 'OR', true);
        }

        if (!array_key_exists($sortOrder, applicationConstants::sortOrder($this->siteLangId))) {
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
                        case 'orderDate':
                            $arr[] = FatDate::format($row[$key]);
                            break;
                        case 'transactionAmount':
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

            CommonHelper::convertToCsv($sheetData, Labels::getLabel("LBL_PREFERRED_PAYMENT_METHOD", $this->siteLangId) . '.csv', ',');
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
        $formTitle = Labels::getLabel('LBL_EXPORT_PAYMENT_METHOD_REPORT', $this->siteLangId);
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
            $this->addSortingElements($frm, 'product_name', applicationConstants::SORT_ASC);
        }
        $fld = $frm->addTextBox(Labels::getLabel("FRM_KEYWORD", $this->siteLangId), 'keyword');
        $fld->overrideFldType('search');

        $frm->addDateField(Labels::getLabel('FRM_DATE_FROM', $this->siteLangId), 'date_from', '', array('placeholder' => Labels::getLabel('FRM_DATE_FROM', $this->siteLangId), 'readonly' => 'readonly', 'class' => 'small dateTimeFld field--calender'));
        $frm->addDateField(Labels::getLabel('FRM_DATE_TO', $this->siteLangId), 'date_to', '', array('placeholder' => Labels::getLabel('FRM_DATE_TO', $this->siteLangId), 'readonly' => 'readonly', 'class' => 'small dateTimeFld field--calender'));
        HtmlHelper::addSearchButton($frm);
        HtmlHelper::addClearButton($frm);/*clearBtn*/

        return $frm;
    }

    protected function getFormColumns()
    {
        $prefPayMethodReportsCacheVar = FatCache::get('prefPayMethodReportsCacheVar' . $this->siteLangId, CONF_DEF_CACHE_TIME, '.txt');
        if (!$prefPayMethodReportsCacheVar) {
            $arr = [
                'orderDate' => Labels::getLabel('LBL_DATE', $this->siteLangId),
                'pluginName' => Labels::getLabel('LBL_PAYMENT_METHOD', $this->siteLangId),
                'billingAddress' => Labels::getLabel('LBL_BILLING_ADDRESS', $this->siteLangId),
                /* 'oua_country' => Labels::getLabel('LBL_COUNTRY', $this->siteLangId),
                'oua_state' => Labels::getLabel('LBL_STATE', $this->siteLangId),
                'oua_city' => Labels::getLabel('LBL_CITY', $this->siteLangId), */
                'transactionAmount' => Labels::getLabel('LBL_TRANSACTION', $this->siteLangId)
            ];
            FatCache::set('prefPayMethodReportsCacheVar' . $this->siteLangId, serialize($arr), '.txt');
        } else {
            $arr =  unserialize($prefPayMethodReportsCacheVar);
        }

        return $arr;
    }

    protected function getDefaultColumns(): array
    {
        return ['orderDate', 'pluginName', 'billingAddress', 'transactionAmount'];
    }
}
