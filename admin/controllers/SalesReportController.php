<?php

class SalesReportController extends ListingBaseController
{
    protected $pageKey = 'REPORT_SALES_OVERTIME';

    public function __construct($action)
    {
        parent::__construct($action);
        $this->objPrivilege->canViewSalesReport();
    }

    public function index($orderDate = '')
    {
        $formColumns = $this->getFormColumns($orderDate);
        $frmSearch = $this->getSearchForm($formColumns, $orderDate);
        $pageData = PageLanguageData::getAttributesByKey($this->pageKey, $this->siteLangId);
        $pageTitle = $pageData['plang_title'] ?? LibHelper::getControllerName(true);

        $actionItemsData = HtmlHelper::getDefaultActionItems($formColumns);
        $actionItemsData = array_merge($actionItemsData, [
            'newRecordBtn' => false,
            'formColumns' => $formColumns,
            'columnButtons' => true,
            'defaultColumns' => $this->getDefaultColumns($orderDate)
        ]);

        $this->set('pageData', $pageData);
        $this->set('pageTitle', $pageTitle);
        $this->set('frmSearch', $frmSearch);
        $this->set('orderDate', $orderDate);
        $this->set('actionItemsData', $actionItemsData);
        $this->getListingData(false, $orderDate);
        $this->set('keywordPlaceholder', Labels::getLabel('FRM_SEARCH_BY_INVOICE_NUMBER', $this->siteLangId));
        $this->_template->render(true, true, '_partial/listing/reports-index.php');
    }

    public function search($type = false)
    {        
        $batchCount = FatApp::getPostedData('batch_count', FatUtility::VAR_INT, 0);
        $batchNumber = FatApp::getPostedData('batch_number', FatUtility::VAR_INT, 1);
        $this->getListingData($type, '', $batchCount, $batchNumber);
        $jsonData = [
            'headSection' => $this->_template->render(false, false, '_partial/listing/head-section.php', true),
            'listingHtml' => $this->_template->render(false, false, 'sales-report/search.php', true),
            'paginationHtml' => $this->_template->render(false, false, '_partial/listing/listing-foot.php', true)
        ];
        LibHelper::exitWithSuccess($jsonData, true);
    }

    public function getSearchForm($fields = [], $orderDate = '')
    {
        $frm = new Form('frmRecordSearch');
        if (!empty($fields)) {
            $this->addSortingElements($frm, 'orderDate', applicationConstants::SORT_DESC);
        }
        $frm->addHiddenField('', 'orderDate', $orderDate);

        if (empty($orderDate)) {
            $frm->addDateField(Labels::getLabel('FRM_DATE_FROM', $this->siteLangId), 'date_from', '', array('placeholder' => Labels::getLabel('FRM_DATE_FROM', $this->siteLangId), 'readonly' => 'readonly', 'class' => 'small dateTimeFld field--calender'));
            $frm->addDateField(Labels::getLabel('FRM_DATE_TO', $this->siteLangId), 'date_to', '', array('placeholder' => Labels::getLabel('FRM_DATE_TO', $this->siteLangId), 'readonly' => 'readonly', 'class' => 'small dateTimeFld field--calender'));
        } else {
            $fld = $frm->addTextBox(Labels::getLabel('FRM_KEYWORD', $this->siteLangId), 'keyword');
            $fld->overrideFldType('search');
        }
        $frm->addHiddenField('', 'total_record_count');
        HtmlHelper::addSearchButton($frm);
        HtmlHelper::addClearButton($frm);
        return $frm;
    }

    private function getListingData($type = false, $orderDate = '', $batchCount = 1, $batchNumber = 0)
    {
        $db = FatApp::getDb();
        $post = FatApp::getPostedData();
        $orderDate = !empty($orderDate) ? $orderDate : FatApp::getPostedData('orderDate');

        $fields = $this->getFormColumns($orderDate);
        $selectedFlds = FatApp::getPostedData('listingColumns', FatUtility::VAR_STRING, '');
        $selectedFlds = !empty($selectedFlds) ? json_decode($selectedFlds) +  $this->getDefaultColumns($orderDate) : $this->getDefaultColumns($orderDate);
        $fields =  FilterHelper::parseArrayByKeys($fields, $selectedFlds, true);

        $sortBy = FatApp::getPostedData('sortBy', FatUtility::VAR_STRING, current(array_keys($fields)));
        if (!array_key_exists($sortBy, $fields)) {
            $sortBy = current(array_keys($fields));
        }

        $sortOrder = applicationConstants::getSortOrder(FatApp::getPostedData('sortOrder', FatUtility::VAR_STRING));
        $srchFrm = $this->getSearchForm($fields, $orderDate);

        $post = $srchFrm->getFormDataFromArray(FatApp::getPostedData());
        $page = FatApp::getPostedData('page', FatUtility::VAR_INT, 1);
        $page = ($page <= 0) ? 1 : $page;

        $pageSize = applicationConstants::getPageSize(FatApp::getPostedData('pageSize', FatUtility::VAR_INT));

        $srch = new Report(0, array_keys($fields));
        $srch->joinOrders();
        $srch->joinPaymentMethod();
        $srch->joinOtherCharges(true);
        $srch->joinOrderProductTaxCharges();
        $srch->joinOrderProductShipCharges();
        $srch->joinOrderProductDicountCharges();
        $srch->joinOrderProductVolumeCharges();
        $srch->joinOrderProductRewardCharges();
        $srch->setPaymentStatusCondition();
        $srch->setCompletedOrdersCondition();
        $srch->excludeDeletedOrdersCondition();

        $fromDate = $toDate = $orderDate;
        if (empty($orderDate)) {
            $fromDate = FatApp::getPostedData('date_from', FatUtility::VAR_DATE, '');
            $toDate = FatApp::getPostedData('date_to', FatUtility::VAR_DATE, '');
            $srch->setGroupBy('orderDate');
            $srch->addTotalOrdersCount('order_date_added');
            $srch->addFld('totOrders');
        } else {
            $keyword = FatApp::getPostedData('keyword', FatUtility::VAR_STRING, '');
            if (!empty($keyword)) {
                $cnd = $srch->addCondition('op_invoice_number', 'like', '%' . $keyword . '%');
                $cnd->attachCondition('order_id', 'like', '%' . $keyword . '%');
            }
            $post['orderDate'] = $orderDate;
            $this->set('orderDate', $orderDate);
            $srch->setGroupBy('op_invoice_number');
            $srch->addFld('op_invoice_number');
        }


        $srch->setDateCondition($fromDate, $toDate);
        $this->setRecordCount(clone $srch, $pageSize, $page, $post, true);
        $srch->doNotCalculateRecords();
        $srch->setOrderBy($sortBy, $sortOrder);
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
            while ($row = $db->fetch($rs)) {
                $arr = [];
                foreach ($fields as $key => $val) {
                    switch ($key) {
                        case 'listSerial':
                            $arr[] = $count;
                            break;
                        case 'orderDate':
                            $arr[] = FatDate::format($row['orderDate']);
                            break;
                        case 'grossSales':
                        case 'transactionAmount':
                        case 'inventoryValue':
                        case 'taxTotal':
                        case 'adminTaxTotal':
                        case 'sellerTaxTotal':
                        case 'shippingTotal':
                        case 'sellerShippingTotal':
                        case 'adminShippingTotal':
                        case 'discountTotal':
                        case 'couponDiscount':
                        case 'volumeDiscount':
                        case 'rewardDiscount':
                        case 'refundedAmount':
                        case 'refundedShipping':
                        case 'refundedTax':
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
                $count++;
            }

            CommonHelper::convertToCsv($sheetData, Labels::getLabel('LBL_Sales_Report', $this->siteLangId) . date("d-M-Y") . '.csv', ',');
            exit;
        }

        $srch->setPageNumber($page);
        $srch->setPageSize($pageSize);
        $this->set("arrListing", $db->fetchAll($srch->getResultSet()));
        $this->set('sortBy', $sortBy);
        $this->set('sortOrder', $sortOrder);
        $this->set('fields', $fields);
        $this->set('postedData', $post);
        $this->set('allowedKeysForSorting', array_keys($fields));
    }

    protected function getFormColumns($orderDate = '')
    {
        $salesReportCacheVar = CacheHelper::get('salesReportCacheVar' . $this->siteLangId, CONF_DEF_CACHE_TIME, '.txt');

        if (!$salesReportCacheVar) {
            $arr = [
                'orderDate' => Labels::getLabel('LBL_DATE', $this->siteLangId),
                'totQtys' => Labels::getLabel('LBL_ORDERED_QTY', $this->siteLangId),
                'totRefundedQtys' => Labels::getLabel('LBL_REFUNDED_QTY', $this->siteLangId),
                'netSoldQty' => Labels::getLabel('LBL_SOLD_QTY', $this->siteLangId),
                'grossSales' => Labels::getLabel('LBL_GROSS_SALE', $this->siteLangId),
                'transactionAmount' => Labels::getLabel('LBL_TRANSACTION_AMOUNT', $this->siteLangId),
                'inventoryValue' => Labels::getLabel('LBL_INVENTORY_VALUE', $this->siteLangId),

                'taxTotal' => Labels::getLabel('LBL_TAX_CHARGED', $this->siteLangId),
                'sellerTaxTotal' => Labels::getLabel('LBL_TAX_CHARGED_BY_SELLER', $this->siteLangId),
                'adminTaxTotal' => Labels::getLabel('LBL_TAX_CHARGED_BY_ADMIN', $this->siteLangId),

                'shippingTotal' => Labels::getLabel('LBL_SHIPPING_CHARGED', $this->siteLangId),
                'sellerShippingTotal' => Labels::getLabel('LBL_SHIPPING_CHARGED_BY_SELLER', $this->siteLangId),
                'adminShippingTotal' => Labels::getLabel('LBL_SHIPPING_CHARGED_BY_ADMIN', $this->siteLangId),

                'couponDiscount' => Labels::getLabel('LBL_COUPON_DISCOUNT', $this->siteLangId),
                'volumeDiscount' => Labels::getLabel('LBL_VOLUME_DISCOUNT', $this->siteLangId),
                'rewardDiscount' => Labels::getLabel('LBL_REWARD_DISCOUNT', $this->siteLangId),

                'refundedAmount' => Labels::getLabel('LBL_REFUNDED_AMOUNT', $this->siteLangId),
                'refundedShipping' => Labels::getLabel('LBL_REFUNDED_SHIPPING', $this->siteLangId),
                'refundedTax' => Labels::getLabel('LBL_REFUNDED_TAX', $this->siteLangId),

                'orderNetAmount' => Labels::getLabel('LBL_NET_AMOUNT', $this->siteLangId),

                'commissionCharged' => Labels::getLabel('LBL_COMMISION_CHARGED', $this->siteLangId),
                'refundedCommission' => Labels::getLabel('LBL_REFUNDED_COMMISION', $this->siteLangId),
                'adminSalesEarnings' => Labels::getLabel('LBL_SALES_EARNINGS', $this->siteLangId)
            ];
            CacheHelper::create('salesReportCacheVar' . $this->siteLangId,  json_encode($arr), CacheHelper::TYPE_LABELS);
        } else {
            $arr =  json_decode($salesReportCacheVar, true);
        }

        if (!empty($orderDate)) {
            unset($arr['orderDate']);
            $arr = [
                'op_invoice_number' => Labels::getLabel('LBL_invoice_number', $this->siteLangId),
                'order_date_added' => Labels::getLabel('LBL_Date', $this->siteLangId),
            ] + $arr;
        }

        return $arr;
    }

    public function form()
    {
        $formTitle = Labels::getLabel('LBL_EXPORT_SALES_REPORT', $this->siteLangId);
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

    protected function getDefaultColumns($orderDate = ''): array
    {
        $arr = ['orderDate', 'totQtys', 'grossSales', 'couponDiscount', 'refundedAmount', 'shippingTotal', 'taxTotal', 'orderNetAmount'];
        if (!empty($orderDate)) {
            unset($arr['orderDate']);
            $arr = [
                'op_invoice_number',
                'order_date_added'
            ] + $arr;
        }
        return $arr;
    }

    public function getBreadcrumbNodes($action)
    {
        $params = FatApp::getParameters();
        switch ($action) {
            case 'index':
                $pageData = PageLanguageData::getAttributesByKey($this->pageKey, $this->siteLangId);
                $pageTitle = $pageData['plang_title'] ?? LibHelper::getControllerName(true);
                $this->nodes = [
                    ['title' => Labels::getLabel('NAV_REPORTS', $this->siteLangId)],
                    ['title' => Labels::getLabel('NAV_SALES_REPORTS', $this->siteLangId)],
                ];
                if (!empty($params)) {
                    $this->nodes[] = [
                        'title' => Labels::getLabel('NAV_SALES_REPORTS', $this->siteLangId),
                        'href' => UrlHelper::generateUrl("SalesReport"),
                    ];
                    $this->nodes[] = ['title' => current($params)];
                } else {
                    $this->nodes[] = ['title' => $pageTitle];
                }
                break;
            default:
                parent::getBreadcrumbNodes($action);
                break;
        }
        return $this->nodes;
    }
}
