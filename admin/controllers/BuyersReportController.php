<?php

class BuyersReportController extends ListingBaseController
{
    protected $pageKey = 'REPORT_CUSTOMERS';

    public function __construct($action)
    {
        parent::__construct($action);
        $this->objPrivilege->canViewBuyersReport($this->admin_id);
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
        $this->set('keywordPlaceholder', Labels::getLabel('FRM_SEARCH_BY_NAME_OR_EMAIL', $this->siteLangId));
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
            'listingHtml' => $this->_template->render(false, false, 'buyers-report/search.php', true),
            'paginationHtml' => $this->_template->render(false, false, '_partial/listing/listing-foot.php', true)
        ];
        LibHelper::exitWithSuccess($jsonData, true);
    }

    public function getListingData($type = false, $batchCount = 1, $batchNumber = 0)
    {
        $post = FatApp::getPostedData();

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

        $rSrch = new Report(0, array_keys($fields));
        $rSrch->joinOrders();
        $rSrch->joinPaymentMethod();
        $rSrch->joinOtherCharges(true);
        $rSrch->joinOrderProductTaxCharges();
        $rSrch->joinOrderProductShipCharges();
        $rSrch->joinOrderProductDicountCharges();
        $rSrch->joinOrderProductVolumeCharges();
        $rSrch->joinOrderProductRewardCharges();
        $rSrch->setPaymentStatusCondition();
        $rSrch->setCompletedOrdersCondition();
        $rSrch->excludeDeletedOrdersCondition();
        $rSrch->doNotCalculateRecords();
        $rSrch->doNotLimitRecords();
        $rSrch->addTotalOrdersCount('order_user_id');
        $rSrch->setGroupBy('order_user_id');
        $rSrch->removeFld('buyerName');
        // echo $rSrch->getQuery(); exit;
        $fromDate = FatApp::getPostedData('date_from', FatUtility::VAR_DATE, '');
        $toDate = FatApp::getPostedData('date_to', FatUtility::VAR_DATE, '');

        $srch = new UserSearch();
        $srch->joinTable('(' . $rSrch->getQuery() . ')', 'LEFT OUTER JOIN', 'u.user_id = opq.order_user_id', 'opq');
        $srch->addMultipleFields(['u.user_name as buyerName', 'uc.credential_email as buyerEmail', 'opq.*']);
        $srch->addCondition('totOrders', '>', 'mysql_func_0', 'AND', true);

        if (isset($post['keyword']) && '' != $post['keyword']) {
            $cnd = $srch->addCondition('u.user_name', 'like', '%' . $post['keyword'] . '%', 'AND');
            $cnd->attachCondition('uc.credential_email', 'like', '%' . $post['keyword'] . '%');
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
                        case 'buyerName':
                            $name = $row['buyerName'] . "\n" . $row['buyerEmail'];
                            $arr[] = $name;
                            break;
                        case 'grossSales':
                        case 'transactionAmount':
                        case 'inventoryValue':
                        case 'taxTotal':
                        case 'shippingTotal':
                        case 'discountTotal':
                        case 'couponDiscount':
                        case 'volumeDiscount':
                        case 'rewardDiscount':
                        case 'refundedAmount':
                        case 'refundedShipping':
                        case 'refundedTax':
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

            CommonHelper::convertToCsv($sheetData, Labels::getLabel('LBL_BUYERS_SALES_REPORT', $this->siteLangId) . '_' . date("d-M-Y") . '.csv', ',');
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

    public function getSearchForm($fields = [])
    {
        $frm = new Form('frmRecordSearch');
        if (!empty($fields)) {
            $this->addSortingElements($frm, 'buyerName', applicationConstants::SORT_ASC);
        }

        $fld = $frm->addTextBox(Labels::getLabel('FRM_KEYWORD', $this->siteLangId), 'keyword');
        $fld->overrideFldType('search');

        HtmlHelper::addSearchButton($frm);
        HtmlHelper::addClearButton($frm);
        return $frm;
    }

    public function form()
    {
        $formTitle = Labels::getLabel('LBL_EXPORT_BUYERS_REPORT', $this->siteLangId);
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

    protected function getFormColumns()
    {
        $buyerReportsCacheVar = FatCache::get('buyerReportsCacheVar' . $this->siteLangId, CONF_DEF_CACHE_TIME, '.txt');
        if (!$buyerReportsCacheVar) {
            $arr = [
                'buyerName' => Labels::getLabel('LBL_NAME', $this->siteLangId),
                'totOrders' => Labels::getLabel('LBL_NO._of_Orders', $this->siteLangId),
                'orderItems' => Labels::getLabel('LBL_ORDERED_ITEMS', $this->siteLangId),
                'totQtys' => Labels::getLabel('LBL_ORDERED_QTY', $this->siteLangId),
                'totRefundedQtys' => Labels::getLabel('LBL_REFUNDED_QTY', $this->siteLangId),
                'grossSales' => Labels::getLabel('LBL_GROSS_SALE', $this->siteLangId),
                // 'transactionAmount' => Labels::getLabel('LBL_TRANSACTION_AMOUNT', $this->siteLangId),
                'inventoryValue' => Labels::getLabel('LBL_INVENTORY_VALUE', $this->siteLangId),

                'taxTotal' => Labels::getLabel('LBL_TAX_CHARGED', $this->siteLangId),

                'shippingTotal' => Labels::getLabel('LBL_SHIPPING_CHARGED', $this->siteLangId),

                'discountTotal' => Labels::getLabel('LBL_TOTAL_DISCOUNT', $this->siteLangId),
                'couponDiscount' => Labels::getLabel('LBL_COUPON_DISCOUNT', $this->siteLangId),
                'volumeDiscount' => Labels::getLabel('LBL_VOLUME_DISCOUNT', $this->siteLangId),
                'rewardDiscount' => Labels::getLabel('LBL_REWARD_DISCOUNT', $this->siteLangId),

                'refundedAmount' => Labels::getLabel('LBL_REFUNDED_AMOUNT', $this->siteLangId),
                'refundedShipping' => Labels::getLabel('LBL_REFUNDED_SHIPPING', $this->siteLangId),
                'refundedTax' => Labels::getLabel('LBL_REFUNDED_TAX', $this->siteLangId),

                'orderNetAmount' => Labels::getLabel('LBL_NET_AMOUNT', $this->siteLangId),
            ];
            FatCache::set('buyerReportsCacheVar' . $this->siteLangId, serialize($arr), '.txt');
        } else {
            $arr =  unserialize($buyerReportsCacheVar);
        }

        return $arr;
    }

    protected function getDefaultColumns(): array
    {
        return ['buyerName', 'totOrders', 'totQtys', 'totRefundedQtys', 'grossSales',  'taxTotal', 'shippingTotal', 'discountTotal', 'refundedAmount', 'orderNetAmount'];
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
        }
        return $this->nodes;
    }
}
