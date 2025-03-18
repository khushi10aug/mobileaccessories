<?php

class ShopsReportController extends ListingBaseController
{
    protected $pageKey = 'SHOPS_REPORT';

    public function __construct($action)
    {
        parent::__construct($action);
        $this->objPrivilege->canViewShopsReport();
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
            'defaultColumns' => $this->getDefaultColumns(),
            'searchFrmTemplate' => 'shops-report/search-form.php'
        ]);

        $this->set('pageData', $pageData);
        $this->set('pageTitle', $pageTitle);
        $this->set('frmSearch', $frmSearch);
        $this->set('actionItemsData', $actionItemsData);
        $this->getListingData(false);
        $this->_template->addJs(array('js/select2.js'));
        $this->_template->addCss(array('css/select2.min.css'));
        $this->_template->render();
    }

    public function search($type = false)
    {
        $batchCount = FatApp::getPostedData('batch_count', FatUtility::VAR_INT, 0);
        $batchNumber = FatApp::getPostedData('batch_number', FatUtility::VAR_INT, 1);
        $this->getListingData($type, $batchCount, $batchNumber);
        $jsonData = [
            'headSection' => $this->_template->render(false, false, '_partial/listing/head-section.php', true),
            'listingHtml' => $this->_template->render(false, false, 'shops-report/search.php', true),
            'paginationHtml' => $this->_template->render(false, false, '_partial/listing/listing-foot.php', true)
        ];
        LibHelper::exitWithSuccess($jsonData, true);
    }

    public function getListingData($type = false, $batchCount = 1, $batchNumber = 0)
    {
        $this->objPrivilege->canViewShopsReport();
        $db = FatApp::getDb();

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
        if ($page < 2) {
            $page = 1;
        }
        $pageSize = applicationConstants::getPageSize(FatApp::getPostedData('pageSize', FatUtility::VAR_INT));

        /* $fields = ['totOrders', 'totQtys', 'totRefundedQtys', 'netSoldQty', 'grossSales', 'transactionAmount', 'inventoryValue', 'taxTotal', 'sellerTaxTotal', 'adminTaxTotal', 'shippingTotal', 'sellerShippingTotal', 'adminShippingTotal', 'couponDiscount', 'volumeDiscount', 'rewardDiscount', 'adminSalesEarnings', 'refundedAmount', 'refundedShipping', 'refundedTax', 'commissionCharged', 'refundedCommission', 'refundedAffiliateCommission', 'orderNetAmount', 'refundedTaxToSeller', 'refundedShippingToSeller']; */
        $opSrch = new Report(0, array_keys($fields), true);
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
        $opSrch->addTotalOrdersCount('op_selprod_user_id');
        $opSrch->setGroupBy('shop_id');
        $opSrch->doNotCalculateRecords();
        $opSrch->doNotLimitRecords();
        $opSrch->removeFld(['shop_name', 'shop_owner', 'owner_name', 'totProducts', 'totalFavorites', 'totReviews', 'totRating']);

        $srch = new ShopSearch($this->siteLangId, false, false);
        $srch->joinShopOwner(false);
        $srch->addProductsCount();
        $srch->addRatingsCount();
        $srch->addFavoritesCount();
        $srch->joinTable('(' . $opSrch->getQuery() . ')', 'LEFT OUTER JOIN', 's.shop_id = opq.op_shop_id', 'opq');
        if (!array_key_exists($sortOrder, applicationConstants::sortOrder(CommonHelper::getLangId()))) {
            $sortOrder = applicationConstants::SORT_ASC;
        }

        switch ($sortBy) {
            default:
                $srch->addOrder($sortBy, $sortOrder);
                break;
        }

        $keyword = FatApp::getPostedData('keyword', FatUtility::VAR_STRING);
        if (!empty($keyword)) {
            $cond = $srch->addCondition('shop_name', '=', $keyword);
            $cond->attachCondition('shop_name', 'like', '%' . $keyword . '%', 'OR');
            $cond->attachCondition('shop_identifier', 'like', '%' . $keyword . '%');
            $cond->attachCondition('user_name', '=', '%' . $keyword . '%');
            $cond->attachCondition('user_name', 'like', '%' . $keyword . '%', 'OR');
            $cond->attachCondition('credential_email', 'like', '%' . $keyword . '%');
        }

        $shop_id = FatApp::getPostedData('shop_id', null, '');
        $shop_keyword = FatApp::getPostedData('shop_name', null, '');
        if ($shop_id) {
            $shop_id = FatUtility::int($shop_id);
            $srch->addCondition('s.shop_id', '=', $shop_id);
        }

        $shop_user_id = FatApp::getPostedData('shop_user_id', null, '');
        $shop_owner_keyword = FatApp::getPostedData('user_name', null, '');
        if ($shop_user_id) {
            $shop_user_id = FatUtility::int($shop_user_id);
            $srch->addCondition('s.shop_user_id', '=', $shop_user_id);
        }

        if ($shop_id == 0 and $shop_user_id == 0 and $shop_keyword != '') {
            $cond = $srch->addCondition('shop_name', '=', $shop_keyword);
            $cond->attachCondition('shop_name', 'like', '%' . $shop_keyword . '%', 'OR');
            $cond->attachCondition('shop_identifier', 'like', '%' . $shop_keyword . '%');
        }

        if ($shop_id == 0 and $shop_user_id == 0 and $shop_owner_keyword != '') {
            $cond1 = $srch->addCondition('user_name', '=', $shop_owner_keyword);
            $cond1->attachCondition('user_name', 'like', '%' . $shop_owner_keyword . '%', 'OR');
            $cond1->attachCondition('credential_email', 'like', '%' . $shop_owner_keyword . '%');
        }

        $date_from = FatApp::getPostedData('date_from', null, '');
        if ($date_from) {
            $srch->addCondition('s.shop_created_on', '>=', $date_from);
        }

        $date_to = FatApp::getPostedData('date_to', null, '');
        if ($date_to) {
            $srch->addCondition('s.shop_created_on', '<=', $date_to);
        }
        $this->setRecordCount(clone $srch, $pageSize, $page, $post);
        $srch->doNotCalculateRecords();
        
        $srch->addMultipleFields(array('shop_id', 'shop_user_id', 's.shop_created_on', 'IFNULL(shop_name, shop_identifier) as shop_name', 'u.user_id', 'u.user_name as owner_name', 'u_cred.credential_email as owner_email', 'opq.*'));

        if ($type == 'export') {
            $pageSize = Report::MAX_LIMIT;
            if (isset($batchCount) && $batchCount > 0 && $batchCount <= Report::MAX_LIMIT) {
                $pageSize = $batchCount;
            }
            $pagenumber = (!$batchNumber) ? 1 : $batchNumber;

            $srch->setPageNumber($pagenumber);
            $srch->setPageSize($pageSize);
            $rs = $srch->getResultSet();
            $sheetData = array();
            array_push($sheetData, array_values($fields));
            while ($row = $db->fetch($rs)) {
                $arr = [];
                foreach ($fields as $key => $val) {
                    switch ($key) {
                        case 'shop_name':
                            $name = $row['shop_name'];
                            $name .= "\nCreated On: " . FatDate::format($row['shop_created_on'], false, true, FatApp::getConfig('CONF_TIMEZONE', FatUtility::VAR_STRING, date_default_timezone_get()));
                            $arr[] = $name;
                            break;
                        case 'owner_name':
                            $name = $row['owner_name'] . '(' . $row['owner_email'] . ')';
                            $arr[] = $name;
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
            }

            CommonHelper::convertToCsv($sheetData, Labels::getLabel('LBL_Shops_Report', $this->siteLangId) . ' ' . date("d-M-Y") . '.csv', ',');
            exit;
        } else { 
            $srch->setPageNumber($page);
            $srch->setPageSize($pageSize);
            $rs = $srch->getResultSet();
            $arrListing = $db->fetchAll($rs); 
            $this->set("arrListing", $arrListing); 
            $this->set('postedData', $post);
            $this->set('sortBy', $sortBy);
            $this->set('sortOrder', $sortOrder);
            $this->set('fields', $fields);
            $this->set('allowedKeysForSorting', array_keys($fields));
        }
    }

    public function export()
    {
        $this->search('export');
    }

    public function form()
    {
        $formTitle = Labels::getLabel('LBL_EXPORT_SHOPS_REPORT', $this->siteLangId);
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

        $fld = $frm->addTextBox(Labels::getLabel('FRM_KEYWORD', $this->siteLangId), 'keyword');
        $fld->overrideFldType('search');

        $frm->addSelectBox(Labels::getLabel('FRM_SHOP', $this->siteLangId), 'shop_id', [], '', [], '');
        $frm->addSelectBox(Labels::getLabel('FRM_SHOP_OWNER', $this->siteLangId), 'shop_user_id', [], '', [], '');

        $fld = $frm->addDateField(Labels::getLabel('FRM_DATE_FROM', $this->siteLangId), 'date_from', '', array('placeholder' => Labels::getLabel('FRM_DATE_FROM', $this->siteLangId), 'readonly' => 'readonly', 'class' => 'field--calender'));
        $fld->htmlAfterField = '<span class="form-text text-muted">' . Labels::getLabel('FRM_SHOP_CREATED_DATE_FROM', $this->siteLangId) .'</span>';

        $fld = $frm->addDateField(Labels::getLabel('FRM_DATE_TO', $this->siteLangId), 'date_to', '', array('placeholder' => Labels::getLabel('FRM_DATE_TO', $this->siteLangId), 'readonly' => 'readonly', 'class' => 'field--calender'));
        $fld->htmlAfterField = $fld->htmlAfterField = '<span class="form-text text-muted">' . Labels::getLabel('FRM_SHOP_CREATED_DATE_TO', $this->siteLangId) . '</span>';
        $frm->addHiddenField('', 'total_record_count'); 
        HtmlHelper::addSearchButton($frm);
        HtmlHelper::addClearButton($frm);/*clearBtn*/
        return $frm;
    }

    protected function getFormColumns()
    {
        $shopsReportCacheVar = FatCache::get('shopsReportCacheVar' . $this->siteLangId, CONF_DEF_CACHE_TIME, '.txt');
        if (!$shopsReportCacheVar) {
            $arr = [
                'shop_name'        =>    Labels::getLabel('LBL_Name', $this->siteLangId),
                'owner_name'    =>    Labels::getLabel('LBL_Owner', $this->siteLangId),
                'totProducts'    => Labels::getLabel('LBL_Items', $this->siteLangId),
                'totOrders' => Labels::getLabel('LBL_Order_Placed', $this->siteLangId),
                'totQtys' => Labels::getLabel('LBL_Ordered_Qty', $this->siteLangId),
                'totRefundedQtys' => Labels::getLabel('LBL_Refunded_Qty', $this->siteLangId),
                'netSoldQty' => Labels::getLabel('LBL_Sold_Qty', $this->siteLangId),
                'grossSales' => Labels::getLabel('LBL_Gross_Sale', $this->siteLangId),
                'transactionAmount' => Labels::getLabel('LBL_Transaction_Amount', $this->siteLangId),
                'inventoryValue' => Labels::getLabel('LBL_Inventory_Value', $this->siteLangId),

                // 'taxTotal' => Labels::getLabel('LBL_Tax_Charged', $this->siteLangId),
                'sellerTaxTotal' => Labels::getLabel('LBL_Tax_Charged', $this->siteLangId),
                // 'adminTaxTotal' => Labels::getLabel('LBL_Tax_Charged_by_Admin', $this->siteLangId),

                // 'shippingTotal' => Labels::getLabel('LBL_Shipping_Charged', $this->siteLangId),
                'sellerShippingTotal' => Labels::getLabel('LBL_Shipping_Charged', $this->siteLangId),
                // 'adminShippingTotal' => Labels::getLabel('LBL_Shipping_Charged_by_Admin', $this->siteLangId),

                // 'couponDiscount' => Labels::getLabel('LBL_Coupon_Discount', $this->siteLangId),
                'volumeDiscount' => Labels::getLabel('LBL_Volume_Discount', $this->siteLangId),
                // 'rewardDiscount' => Labels::getLabel('LBL_Reward_Discount', $this->siteLangId),

                'refundedAmount' => Labels::getLabel('LBL_Refunded_Amount', $this->siteLangId),
                // 'refundedShipping' => Labels::getLabel('LBL_Refunded_Shipping', $this->siteLangId),
                'refundedShippingFromSeller' => Labels::getLabel('LBL_Refunded_Shipping', $this->siteLangId),
                // 'refundedTax' => Labels::getLabel('LBL_Refunded_Tax', $this->siteLangId),
                'refundedTaxFromSeller' => Labels::getLabel('LBL_Refunded_Tax', $this->siteLangId),

                'commissionCharged' => Labels::getLabel('LBL_Commision_Charged', $this->siteLangId),
                'refundedCommission' => Labels::getLabel('LBL_Refunded_Commision', $this->siteLangId),
                'adminSalesEarnings' => Labels::getLabel('LBL_Admin_Earnings', $this->siteLangId),
                'totalFavorites' =>    Labels::getLabel('LBL_Favorites', $this->siteLangId),
                'totReviews'    =>    Labels::getLabel('LBL_Reviews', $this->siteLangId),
                'totRating'        =>    Labels::getLabel('LBL_Rating', $this->siteLangId),
            ];
            FatCache::set('shopsReportCacheVar' . $this->siteLangId, serialize($arr), '.txt');
        } else {
            $arr =  unserialize($shopsReportCacheVar);
        }

        return $arr;
    }

    protected function getDefaultColumns(): array
    {
        return ['shop_name', 'owner_name', 'netSoldQty', 'grossSales', 'refundedAmount', 'sellerTaxTotal', 'sellerShippingTotal', 'volumeDiscount', 'adminSalesEarnings'];
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
            default:
                parent::getBreadcrumbNodes($action);
                break;
        }
        return $this->nodes;
    }
}
