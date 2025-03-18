<?php

class SubscriptionPlanReportController extends ListingBaseController
{
    protected $pageKey = 'REPORT_SUBSCRIPTION_BY_PLAN';

    public function __construct($action)
    {
        parent::__construct($action);
        $this->objPrivilege->canViewSubscriptionReport();
    }

    public function index($orderDate = '')
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
        $this->set('keywordPlaceholder', Labels::getLabel('FRM_SEARCH_BY_PACKAGE_NAME', $this->siteLangId));
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
            'listingHtml' => $this->_template->render(false, false, 'subscription-plan-report/search.php', true),
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


        $srch = new SellerPackagePlansSearch();
        $srch->joinPackage($this->siteLangId);
        $srch->includeCount();
        $srch->addMultipleFields(['ifnull(spackage_name,spackage_identifier) as spackage_name', 'spplan_price', 'spplan_frequency', 'spackage_type', 'spplan_interval', 'activeSubscribers', 'spackageCancelled', 'spackageSold', 'spRenewalPendings', 'spRenewals']);
        $srch->addCondition('spplan_active', '=', applicationConstants::ACTIVE);
        $srch->addGroupBy('spplan_id');
        if (!empty($keyword)) {
            $cnd = $srch->addCondition('spackage_identifier', 'like', '%' . $keyword . '%');
            $cnd->attachCondition('spackage_name', 'like', '%' . $keyword . '%');
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
            $subcriptionPeriodArr = SellerPackagePlans::getSubscriptionPeriods($this->siteLangId);
            $count = 1;
            while ($row = FatApp::getDb()->fetch($rs)) {
                $arr = [];
                foreach ($fields as $key => $val) {
                    switch ($key) {
                        case 'listSerial':
                            $arr[] = $count;
                            break;
                        case 'spplan_price':
                            $arr[] = CommonHelper::displayMoneyFormat($row[$key], true, true, false);
                            break;
                        case 'spackage_name':
                            $name = $row['spackage_name'] . ' ';
                            $name .= ($row['spackage_type'] == SellerPackages::PAID_TYPE) ? " /" . " " . Labels::getLabel("LBL_Per", $this->siteLangId) : Labels::getLabel("LBL_For", $this->siteLangId);

                            $name .= " " . (($row['spplan_interval'] > 0) ? $row['spplan_interval'] : '')
                                . "  " . $subcriptionPeriodArr[$row['spplan_frequency']];
                            $arr[] = $name;
                            break;
                        default:
                            $arr[] = $row[$key];
                            break;
                    }
                }

                array_push($sheetData, $arr);
                $count++;
            }

            CommonHelper::convertToCsv($sheetData, Labels::getLabel("LBL_Subscription_Plan_Report", $this->siteLangId) . '.csv', ',');
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
        $formTitle = Labels::getLabel('LBL_EXPORT_SUBSCRIPTION_PLAN_REPORT', $this->siteLangId);
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
            $this->addSortingElements($frm, 'spackage_name', applicationConstants::SORT_ASC);
        }
        $fld = $frm->addTextBox(Labels::getLabel("FRM_KEYWORD", $this->siteLangId), 'keyword');
        $fld->overrideFldType('search');
        HtmlHelper::addSearchButton($frm);
        HtmlHelper::addClearButton($frm);

        return $frm;
    }

    protected function getFormColumns()
    {
        $spackageReportsCacheVar = FatCache::get('spackageReportsCacheVar' . $this->siteLangId, CONF_DEF_CACHE_TIME, '.txt');
        if (!$spackageReportsCacheVar) {
            $arr = [
                'spackage_name' => Labels::getLabel('LBL_Package_Name', $this->siteLangId),
                'spackageSold' => Labels::getLabel('LBL_Sold', $this->siteLangId),
                'activeSubscribers' => Labels::getLabel('LBL_Active_Subscribers', $this->siteLangId),
                'spRenewalPendings' => Labels::getLabel('LBL_Pending_To_Renew', $this->siteLangId),
                'spRenewals' => Labels::getLabel('LBL_Renewed', $this->siteLangId),
                'spackageCancelled' => Labels::getLabel('LBL_Cancellation', $this->siteLangId),
                'spplan_price' => Labels::getLabel('LBL_Package_Cost', $this->siteLangId)
            ];
            FatCache::set('spackageReportsCacheVar' . $this->siteLangId, serialize($arr), '.txt');
        } else {
            $arr =  unserialize($spackageReportsCacheVar);
        }

        return $arr;
    }

    protected function getDefaultColumns(): array
    {
        return ['spackage_name', 'spackageSold', 'activeSubscribers', 'spRenewalPendings', 'spRenewals', 'spackageCancelled', 'spplan_price'];
    }
}
