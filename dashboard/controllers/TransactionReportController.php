<?php

class TransactionReportController extends SellerBaseController
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
        $this->set('keywordPlaceholder', Labels::getLabel('LBL_SEARCH_BY_TRANSACTION_ID_OR_COMMENTS', $this->siteLangId));
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

        $sortOrder = FatApp::getPostedData('sortOrder', FatUtility::VAR_STRING, applicationConstants::SORT_DESC);
        if (!array_key_exists($sortOrder, applicationConstants::sortOrder($this->siteLangId))) {
            $sortOrder = applicationConstants::SORT_DESC;
        }
        $srchFrm = $this->getSearchForm($fields);

        $post = $srchFrm->getFormDataFromArray(FatApp::getPostedData());
        $page = (empty($post['page']) || $post['page'] <= 0) ? 1 : intval($post['page']);
        $pagesize = FatApp::getConfig('CONF_PAGE_SIZE', FatUtility::VAR_INT, 10);
        $keyword = FatApp::getPostedData('keyword', null, '');
        $fromDate = FatApp::getPostedData('date_from', FatUtility::VAR_DATE, '');
        $toDate = FatApp::getPostedData('date_to', FatUtility::VAR_DATE, '');

        $srch = Transactions::getSearchObject();
        $srch->joinTable(User::DB_TBL, 'LEFT OUTER JOIN', 'u.user_id = utxn.utxn_user_id', 'u');
        $srch->joinTable(User::DB_TBL_CRED, 'LEFT OUTER JOIN', 'uc.credential_user_id = u.user_id', 'uc');
        $srch->addMultipleFields(['utxn.utxn_id', 'utxn.utxn_order_id', 'utxn.utxn_status', 'utxn.utxn_credit', 'utxn.utxn_debit', 'utxn.utxn_date', 'utxn.utxn_comments', 'if(utxn.utxn_credit > 0, utxn.utxn_credit, if(utxn.utxn_debit>0,CONCAT("-", utxn.utxn_debit),0)) as transactionAmount', 'u.user_name', 'uc.credential_email']);
        $srch->addCondition('u.user_id', '=', $this->userParentId);
        if (!empty($keyword)) {
            $cond = $srch->addCondition('utxn.utxn_order_id', 'like', '%' . $keyword . '%');
            $cond->attachCondition('utxn.utxn_op_id', 'like', '%' . $keyword . '%', 'OR');
            $cond->attachCondition('utxn.utxn_comments', 'like', '%' . $keyword . '%', 'OR');
            $cond->attachCondition('concat("TN-" ,lpad( utxn.`utxn_id`,7,0))', 'like', '%' . $keyword . '%', 'OR', true);
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

        $recordCountSrch = clone $srch; 
     
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
            $statusArr = Transactions::getStatusArr($this->siteLangId);
            while ($row = FatApp::getDb()->fetch($rs)) {
                $arr = [];
                foreach ($fields as $key => $val) {
                    switch ($key) {
                        case 'listserial':
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
                        case 'utxn_credit':
                        case 'utxn_debit':
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
        $srch->setPageSize($pagesize);
        $this->set("arrListing", FatApp::getDb()->fetchAll($srch->getResultSet()));
        $this->setRecordCount($recordCountSrch, $pagesize, $page, $post);
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
        $formTitle = Labels::getLabel('LBL_TRANSACTION_REPORT', $this->siteLangId);
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
        $frm->addTextBox(Labels::getLabel("FRM_KEYWORD", $this->siteLangId), 'keyword');

        $frm->addDateField(Labels::getLabel('FRM_DATE_FROM', $this->siteLangId), 'date_from', '', array('readonly' => 'readonly', 'class' => 'small dateTimeFld field--calender'));
        $frm->addDateField(Labels::getLabel('FRM_DATE_TO', $this->siteLangId), 'date_to', '', array('readonly' => 'readonly', 'class' => 'small dateTimeFld field--calender'));

        if (!empty($fields)) {
            $frm->addHiddenField('', 'sortBy', 'utxn_date');
            $frm->addHiddenField('', 'sortOrder', applicationConstants::SORT_DESC);
            $frm->addHiddenField('', 'reportColumns', '');
        }

        HtmlHelper::addSearchButton($frm);
        HtmlHelper::addClearButton($frm, 'btn btn-clear');

        return $frm;
    }

    private function getFormColumns()
    {
        $sellerTranscationReportsCacheVar = CacheHelper::get('sellerTranscationReportsCacheVar' . $this->siteLangId, CONF_DEF_CACHE_TIME, '.txt');
        if (!$sellerTranscationReportsCacheVar) {
            $arr = [
                'utxn_date' => Labels::getLabel('LBL_Date', $this->siteLangId),
                'utxn_id' => Labels::getLabel('LBL_Transaction_ID', $this->siteLangId),
                'utxn_status' => Labels::getLabel('LBL_Payment_Status', $this->siteLangId),
                'utxn_credit' => Labels::getLabel('LBL_Credit', $this->siteLangId),
                'utxn_debit' => Labels::getLabel('LBL_Debit', $this->siteLangId),
                'transactionAmount' => Labels::getLabel('LBL_Transaction_Amount', $this->siteLangId),
                'utxn_comments' => Labels::getLabel('LBL_Comments', $this->siteLangId),
            ];
            CacheHelper::create('sellerTranscationReportsCacheVar' . $this->siteLangId, serialize($arr), CacheHelper::TYPE_LABELS);
        } else {
            $arr =  unserialize($sellerTranscationReportsCacheVar);
        }

        return $arr;
    }

    protected function getDefaultColumns(): array
    {
        return ['utxn_date', 'utxn_id', 'utxn_status', 'transactionAmount', 'utxn_comments'];
    }
}
