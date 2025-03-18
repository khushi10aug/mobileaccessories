<?php

class TransactionReportController extends ListingBaseController
{
    protected $pageKey = 'REPORT_TRANSACTION';

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
        $this->set('keywordPlaceholder', Labels::getLabel('FRM_SEARCH_BY_TRANSACTION_ID_OR_NAME', $this->siteLangId));
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
            'listingHtml' => $this->_template->render(false, false, 'transaction-report/search.php', true),
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

        $srch = Transactions::getSearchObject();
        $srch->joinTable(User::DB_TBL, 'LEFT OUTER JOIN', 'u.user_id = utxn.utxn_user_id', 'u');
        $srch->joinTable(User::DB_TBL_CRED, 'LEFT OUTER JOIN', 'uc.credential_user_id = u.user_id', 'uc');
        $srch->joinTable(Orders::DB_TBL, 'LEFT OUTER JOIN', 'utxn_order_id = order_id', 'o');
        $srch->addMultipleFields(['utxn.utxn_id', 'utxn.utxn_order_id', 'utxn.utxn_status', 'utxn.utxn_credit', 'utxn.utxn_debit', 'utxn.utxn_date', 'utxn.utxn_comments', 'if(utxn.utxn_credit > 0, utxn.utxn_credit, if(utxn.utxn_debit>0,CONCAT("-", utxn.utxn_debit),0)) as transactionAmount', 'u.user_name', 'uc.credential_email','order_number']);
        if (!empty($keyword)) {
            $cond = $srch->addCondition('order_id', 'like', '%' . $keyword . '%');
            $cond->attachCondition('utxn.utxn_op_id', 'like', '%' . $keyword . '%', 'OR');
            $cond->attachCondition('utxn.utxn_comments', 'like', '%' . $keyword . '%', 'OR');
            $cond->attachCondition('concat("TN-" ,lpad( utxn.`utxn_id`,7,0))', 'like', '%' . $keyword . '%', 'OR', true);
            $cond->attachCondition('u.user_name', 'like', '%' . $keyword . '%', 'OR');
            $cond->attachCondition('uc.credential_email', 'like', '%' . $keyword . '%', 'OR');
        }

        if (!empty($fromDate)) {
            $cond = $srch->addCondition('utxn.utxn_date', '>=', $fromDate);
        }

        if (!empty($toDate)) {
            $cond = $srch->addCondition('cast( utxn.`utxn_date` as date)', '<=', $toDate, 'and', true);
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
            $statusArr = Transactions::getStatusArr($this->siteLangId);
            while ($row = FatApp::getDb()->fetch($rs)) {
                $arr = [];

              
                foreach ($fields as $key => $val) {
                    switch ($key) {
                        case 'listSerial':
                            $arr[] = $count;
                            break;
                        case 'utxn_id':
                            $arr[] = Transactions::formatTransactionNumber($row[$key]);
                            break;
                        case 'utxn_date':
                            $arr[] = FatDate::format($row[$key]);
                            break;
                        case 'utxn_date':
                            $name = $row[$key];
                            $name .= !empty($row['credential_email']) ? ' (' . $row['credential_email'] . ')' : '';
                            $arr[] = $name;
                            break;
                        case 'utxn_status':
                            $arr[] = $statusArr[$row[$key]];
                            break;
                        case 'order_number':
                            $arr[] = $row[$key];
                            break;    
                        case 'utxn_credit':
                        case 'utxn_debit':
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

            CommonHelper::convertToCsv($sheetData, Labels::getLabel("LBL_Transaction_Report", $this->siteLangId) . '.csv', ',');
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
        $formTitle = Labels::getLabel('LBL_EXPORT_TRANSACTION_REPORT', $this->siteLangId);
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
            $this->addSortingElements($frm, 'utxn_date', applicationConstants::SORT_DESC);
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
        $transcationReportsCacheVar = FatCache::get('transcationReportsCacheVar' . $this->siteLangId, CONF_DEF_CACHE_TIME, '.txt');
        if (!$transcationReportsCacheVar) {
            $arr = [
                'utxn_date' => Labels::getLabel('LBL_Date', $this->siteLangId),
                'utxn_id' => Labels::getLabel('LBL_Transaction_ID', $this->siteLangId),
                'utxn_status' => Labels::getLabel('LBL_Payment_Status', $this->siteLangId),
                'order_number' => Labels::getLabel('LBL_ORDER_NUMBER', $this->siteLangId),
                'user_name' => Labels::getLabel('LBL_Name', $this->siteLangId),
                'utxn_credit' => Labels::getLabel('LBL_Credit', $this->siteLangId),
                'utxn_debit' => Labels::getLabel('LBL_Debit', $this->siteLangId),
                'transactionAmount' => Labels::getLabel('LBL_Transaction_Amount', $this->siteLangId),
                'utxn_comments' => Labels::getLabel('LBL_Comments', $this->siteLangId),
            ];
            FatCache::set('transcationReportsCacheVar' . $this->siteLangId, serialize($arr), '.txt');
        } else {
            $arr =  unserialize($transcationReportsCacheVar);
        }
        return $arr;
    }

    protected function getDefaultColumns(): array
    {
        return ['utxn_date', 'utxn_id', 'user_name', 'utxn_status', 'order_number', 'transactionAmount'];
    }
}
