<?php

class SubscriptionSellerReportController extends ListingBaseController
{
    protected $pageKey = 'REPORT_SUBSCRIPTION_BY_SELLER';

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
            'defaultColumns' => $this->getDefaultColumns(),
        ]);

        $this->set('pageData', $pageData);
        $this->set('pageTitle', $pageTitle);
        $this->set('frmSearch', $frmSearch);
        $this->set('actionItemsData', $actionItemsData);
        $this->getListingData(false);
        $this->set('keywordPlaceholder', Labels::getLabel('FRM_SEARCH_BY_USER_NAME_OR_PACKAGE_NAME', $this->siteLangId));
        $this->_template->render(true, true, '_partial/listing/reports-index.php');
    }

    public function search($type = false)
    {
        $batchCount = FatApp::getPostedData('batch_count', FatUtility::VAR_INT, 0);
        $batchNumber = FatApp::getPostedData('batch_number', FatUtility::VAR_INT, 1);
        $this->getListingData($type, $batchCount, $batchNumber);
        $jsonData = [
            'headSection' => $this->_template->render(false, false, '_partial/listing/head-section.php', true),
            'listingHtml' => $this->_template->render(false, false, 'subscription-seller-report/search.php', true),
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

        $srch = new OrderSubscriptionSearch($this->siteLangId, true, true);
        $srch->joinWithCurrentSubscription();
        $srch->joinSubscription();
        $srch->joinOrderUser();
        $srch->joinOtherCharges();
        $srch->addGroupBy('o.order_user_id');
        $srch->addCondition('o.order_type', '=', Orders::ORDER_SUBSCRIPTION);
        $srch->includeCount();
        $srch->addMultipleFields(['user_autorenew_subscription', 'oss_l.ossubs_subscription_name', 'oss.ossubs_interval', 'oss.ossubs_frequency', 'oss.ossubs_type', 'ou.user_name as user_name', 'oss.ossubs_from_date', 'oss.ossubs_till_date', 'subscount.*']);
        $srch->addCompletedOrderCondition();
        if (!empty($keyword)) {
            $srch->addHaving('user_name', 'like', '%' . $keyword . '%', 'AND');
            $srch->addHaving('oss_l.ossubs_subscription_name', 'like', '%' . $keyword . '%', 'OR');
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
                        case 'subscriptionCharges':
                            $arr[] = CommonHelper::displayMoneyFormat($row[$key], true, true, false);
                            break;
                        case 'ossubs_from_date':
                            $arr[] = FatDate::format($row[$key]);
                            break;
                        case 'ossubs_till_date':
                            if(SellerPackagePlans::SUBSCRIPTION_PERIOD_UNLIMITED == $row['ossubs_frequency']) {
                                $arr[] = Labels::getLabel("LBL_N/A", $this->siteLangId);
                            } else { 
                                $arr[] = FatDate::format($row[$key]);
                            }
                            break;
                        case 'ossubs_subscription_name':
                            $name = $row['ossubs_subscription_name'] . ' ';
                            $name .= ($row['ossubs_type'] == SellerPackages::PAID_TYPE) ? " /" . " " . Labels::getLabel("LBL_Per", $this->siteLangId) : Labels::getLabel("LBL_For", $this->siteLangId);

                            $name .= " " . (($row['ossubs_interval'] > 0) ? $row['ossubs_interval'] : '')
                                . "  " . $subcriptionPeriodArr[$row['ossubs_frequency']];
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

            CommonHelper::convertToCsv($sheetData, Labels::getLabel("LBL_Subscription_Seller_Report", $this->siteLangId) . '.csv', ',');
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
        $formTitle = Labels::getLabel('LBL_EXPORT_SUBSCRIPTION_SELLER_REPORT', $this->siteLangId);
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
            $this->addSortingElements($frm, 'user_name', applicationConstants::SORT_ASC);
        }
        $fld = $frm->addTextBox(Labels::getLabel("FRM_KEYWORD", $this->siteLangId), 'keyword');
        $fld->overrideFldType('search');
        HtmlHelper::addSearchButton($frm);
        HtmlHelper::addClearButton($frm);

        return $frm;
    }

    protected function getFormColumns()
    {
        $spackageSReportsCacheVar = FatCache::get('spackageSReportsCacheVar' . $this->siteLangId, CONF_DEF_CACHE_TIME, '.txt');
        if (!$spackageSReportsCacheVar) {
            $arr = [
                'user_name' => Labels::getLabel('LBL_USER_NAME', $this->siteLangId),
                'ossubs_subscription_name' => Labels::getLabel('LBL_Package_Name', $this->siteLangId),
                'ossubs_from_date' => Labels::getLabel('LBL_Activation_Date', $this->siteLangId),
                'ossubs_till_date' => Labels::getLabel('LBL_Expiry_Date', $this->siteLangId),
                'spRenewals' => Labels::getLabel('LBL_Renewed', $this->siteLangId),
                'spackageCancelled' => Labels::getLabel('LBL_Cancellation', $this->siteLangId),
                'subscriptionCharges' => Labels::getLabel('LBL_Amount_paid', $this->siteLangId)
            ];
            FatCache::set('spackageSReportsCacheVar' . $this->siteLangId, serialize($arr), '.txt');
        } else {
            $arr =  unserialize($spackageSReportsCacheVar);
        }

        return $arr;
    }

    protected function getDefaultColumns(): array
    {
        return ['user_name', 'ossubs_subscription_name', 'ossubs_from_date', 'ossubs_till_date', 'spRenewals', 'spackageCancelled', 'subscriptionCharges'];
    }
}
