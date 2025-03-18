<?php

class UsersReportController extends ListingBaseController
{
    protected $pageKey = 'USERS_REPORT';

    public function __construct($action)
    {
        parent::__construct($action);
    }

    public function index($userType = User::USER_TYPE_BUYER)
    {
        $this->validateViewPermission($userType);
        $formColumns = $this->getFormColumns($userType);
        $frmSearch = $this->getSearchForm($formColumns, $userType);
        $this->setPageKey($userType);
        $pageData = PageLanguageData::getAttributesByKey($this->pageKey, $this->siteLangId);
        $pageTitle = $pageData['plang_title'] ?? LibHelper::getControllerName(true);

        $actionItemsData = HtmlHelper::getDefaultActionItems($formColumns);
        $actionItemsData = array_merge($actionItemsData, [
            'newRecordBtn' => false,
            'formColumns' => $formColumns,
            'columnButtons' => true,
            'defaultColumns' => $this->getDefaultColumns($userType)
        ]);

        $this->set('pageData', $pageData);
        $this->set('pageTitle', $pageTitle);
        $this->set('frmSearch', $frmSearch);
        $this->set('actionItemsData', $actionItemsData);
        $this->set('userType', $userType);
        $this->set('keywordPlaceholder', Labels::getLabel('FRM_SEARCH_BY_NAME', $this->siteLangId));
        $this->getListingData($userType, false);
        $this->_template->render(true, true, '_partial/listing/reports-index.php');
    }

    public function search($type = false)
    {
        $batchCount = FatApp::getPostedData('batch_count', FatUtility::VAR_INT, 0);
        $batchNumber = FatApp::getPostedData('batch_number', FatUtility::VAR_INT, 1);
        $userType = FatApp::getPostedData('user_type', FatUtility::VAR_INT, User::USER_TYPE_BUYER);
        $this->getListingData($userType, $type, $batchCount, $batchNumber);
        $jsonData = [
            'headSection' => $this->_template->render(false, false, '_partial/listing/head-section.php', true),
            'listingHtml' => $this->_template->render(false, false, 'users-report/search.php', true),
            'paginationHtml' => $this->_template->render(false, false, '_partial/listing/listing-foot.php', true)
        ];
        LibHelper::exitWithSuccess($jsonData, true);
    }

    public function getListingData(int $userType, $type = false, $batchCount = 1, $batchNumber = 0)
    {
        /*  $userType = FatApp::getPostedData('user_type', FatUtility::VAR_INT, User::USER_TYPE_BUYER); */
        $this->validateViewPermission($userType);

        $fields = $this->getFormColumns($userType);
        $selectedFlds = FatApp::getPostedData('listingColumns', FatUtility::VAR_STRING, '');
        $selectedFlds = !empty($selectedFlds) ? json_decode($selectedFlds) +  $this->getDefaultColumns($userType) : $this->getDefaultColumns($userType);
        $fields =  FilterHelper::parseArrayByKeys($fields, $selectedFlds, true);

        $sortBy = FatApp::getPostedData('sortBy', FatUtility::VAR_STRING, current(array_keys($fields)));
        if (!array_key_exists($sortBy, $fields)) {
            $sortBy = current(array_keys($fields));
        }

        $sortOrder = applicationConstants::getSortOrder(FatApp::getPostedData('sortOrder', FatUtility::VAR_STRING));
        $srchFrm = $this->getSearchForm($fields, $userType);

        $post = $srchFrm->getFormDataFromArray(FatApp::getPostedData());
        $page = FatApp::getPostedData('page', FatUtility::VAR_INT, 1);
        $page = ($page <= 0) ? 1 : $page;
        $pageSize = applicationConstants::getPageSize(FatApp::getPostedData('pageSize', FatUtility::VAR_INT));

        $shopSpecific = ($userType == User::USER_TYPE_SELLER) ? true : false;

        $rSrch = new Report(0, array_keys($fields), $shopSpecific);
        $rSrch->joinOrders();
        $rSrch->joinPaymentMethod();
        $rSrch->joinOtherCharges(true);
        $rSrch->setPaymentStatusCondition();
        $rSrch->setCompletedOrdersCondition();
        $rSrch->excludeDeletedOrdersCondition();
        $rSrch->doNotCalculateRecords();
        $rSrch->doNotLimitRecords();
        $rSrch->removeFld(['name', 'user_regdate', 'referrerName', 'user_referral_code', 'rewardsPoints', 'rewardsPointsEarned', 'rewardsPointsRedeemed', 'credential_verified', 'availableBalance', 'totRating', 'promotionCharged']);

        $srch = new UserSearch();
        $srch->includeTransactionBalance();
        $srch->includePromotionCharges();
        $srch->addRatingsCount();
        switch ($userType) {
            case  User::USER_TYPE_SELLER:
                $rSrch->joinOrderProductTaxCharges();
                $rSrch->joinOrderProductShipCharges();
                $rSrch->addTotalOrdersCount('op_selprod_user_id');
                $rSrch->setGroupBy('op_selprod_user_id');
                $srch->joinTable('(' . $rSrch->getQuery() . ')', 'LEFT OUTER JOIN', 'u.user_id = opq.op_selprod_user_id', 'opq');
                $srch->addFld(['pchagres.promotionCharged']);
                $srch->addCondition('u.user_is_supplier', '=', 'mysql_func_' . applicationConstants::YES, 'AND', true);
                break;
            default:
                $srch->joinReferrerUser();
                $srch->includeRewardsCount();
                $srch->addFld(['uref.user_name as referrerName', 'uref_c.credential_email as referrerEmail', 'urpbal.*']);
                $srch->addCondition('u.user_is_buyer', '=', 'mysql_func_' . applicationConstants::YES, 'AND', true);

                $rSrch->addTotalOrdersCount('order_user_id');
                $rSrch->setGroupBy('order_user_id');
                $srch->joinTable('(' . $rSrch->getQuery() . ')', 'LEFT OUTER JOIN', 'u.user_id = opq.order_user_id', 'opq');
                break;
        }

        $srch->addMultipleFields(['u.user_name as name', 'uc.credential_email as email', 'u.user_city', 'u.user_zip', 'u.user_address1 as user_address', 'u.user_regdate', 'u.user_referral_code', 'uc.credential_verified',  'opq.*']);

        $date_from = FatApp::getPostedData('date_from', FatUtility::VAR_DATE, '');
        if (!empty($date_from)) {
            $srch->addCondition('u.user_regdate', '>=', $date_from . ' 00:00:00');
        }

        $date_to = FatApp::getPostedData('date_to', FatUtility::VAR_DATE, '');
        if (!empty($date_to)) {
            $srch->addCondition('u.user_regdate', '<=', $date_to . ' 23:59:59');
        }

        $keyword = FatApp::getPostedData('keyword', null, '');
        if (!empty($keyword)) {
            $cond = $srch->addCondition('uc.credential_username', '=', $keyword);
            $cond->attachCondition('uc.credential_email', 'like', '%' . $keyword . '%', 'OR');
            $cond->attachCondition('u.user_name', 'like', '%' . $keyword . '%');
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
                        case 'name':
                            $name = $row['name'] . "\n" . $row['email'];
                            $arr[] = $name;
                            break;
                        case 'orderNetAmount':
                        case 'promotionCharged':
                        case 'availableBalance':
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

            CommonHelper::convertToCsv($sheetData, Labels::getLabel('LBL_Buyers/Seller_Sales_Report', $this->siteLangId) . '_' . date("d-M-Y") . '.csv', ',');
            exit;
        }

        $srch->setPageNumber($page);
        $srch->setPageSize($pageSize);
        $rs = $srch->getResultSet();

        $arrListing = FatApp::getDb()->fetchAll($rs);

        $post['user_type'] = $userType;

        $this->set("arrListing", $arrListing);
        $this->set('pageCount', $srch->pages());
        $this->set('recordCount', $srch->recordCount());
        $this->set('page', $page);
        $this->set('pageSize', $pageSize);
        $this->set('postedData', $post);
        $this->set('sortBy', $sortBy);
        $this->set('sortOrder', $sortOrder);
        $this->set('fields', $fields);
        $this->set('userType', $userType);
        $this->set('allowedKeysForSorting', array_keys($fields));
    }

    public function export()
    {
        $this->search('export');
    }

    public function form()
    {
        $formTitle = Labels::getLabel('LBL_EXPORT_USERS_REPORT', $this->siteLangId);
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

    public function getSearchForm($fields = [], $userType = User::USER_TYPE_BUYER)
    {
        $frm = new Form('frmRecordSearch');
        if (!empty($fields)) {
            $this->addSortingElements($frm, 'name', applicationConstants::SORT_ASC);
        }
        $frm->addHiddenField('', 'user_type', $userType);
        $frm->addDateField(Labels::getLabel('FRM_REG._DATE_FROM', $this->siteLangId), 'date_from', '', array('placeholder' => Labels::getLabel('FRM_REG._DATE_FROM', $this->siteLangId), 'readonly' => 'readonly', 'class' => 'small dateTimeFld field--calender'));
        $frm->addDateField(Labels::getLabel('FRM_REG._DATE_TO', $this->siteLangId), 'date_to', '', array('placeholder' => Labels::getLabel('FRM_REG._DATE_TO', $this->siteLangId), 'readonly' => 'readonly', 'class' => 'small dateTimeFld field--calender'));
        $fld = $frm->addTextBox(Labels::getLabel('FRM_NAME_OR_EMAIL', $this->siteLangId), 'keyword', '', array('id' => 'keyword', 'autocomplete' => 'off'));
        $fld->overrideFldType('search');

        HtmlHelper::addSearchButton($frm);
        HtmlHelper::addClearButton($frm);/*clearBtn*/

        return $frm;
    }

    protected function getFormColumns($userType = User::USER_TYPE_BUYER)
    {
        $buyerUserReportsCacheVar = FatCache::get('buyerUserReportsCacheVar' . $userType . '-' . $this->siteLangId, CONF_DEF_CACHE_TIME, '.txt');
        if (!$buyerUserReportsCacheVar) {
            $arr = [
                'name' => Labels::getLabel('LBL_Name', $this->siteLangId),
                /* 'email' => Labels::getLabel('LBL_Email', $this->siteLangId), */
                /*  'user_address' => Labels::getLabel('LBL_Address', $this->siteLangId), */
                'user_regdate' => Labels::getLabel('LBL_Registration_Date', $this->siteLangId)
            ];

            switch ($userType) {
                case User::USER_TYPE_SELLER:
                    $arr = $arr + [
                        'credential_verified' => Labels::getLabel('LBL_Verified', $this->siteLangId),
                        'totOrders' => Labels::getLabel('LBL_Order_Placed', $this->siteLangId),
                        'totQtys' => Labels::getLabel('LBL_Ordered_Qty', $this->siteLangId),
                        'totRefundedQtys' => Labels::getLabel('LBL_Refunded_Qty', $this->siteLangId),
                        'refundedAmount' => Labels::getLabel('LBL_Refunded_Amount', $this->siteLangId),
                        'orderNetAmount' => Labels::getLabel('LBL_Net_Amount', $this->siteLangId),
                        'availableBalance' => Labels::getLabel('LBL_Available_Balance', $this->siteLangId),
                        'promotionCharged'        =>    Labels::getLabel('LBL_Promotion_Charged', $this->siteLangId),
                        'totRating'        =>    Labels::getLabel('LBL_Rating', $this->siteLangId),
                    ];
                    break;
                default:
                    $arr = $arr + [
                        'referrerName' => Labels::getLabel('LBL_Referrer', $this->siteLangId),
                        'user_referral_code' => Labels::getLabel('LBL_Referral_Code', $this->siteLangId),
                        'rewardsPoints' => Labels::getLabel('LBL_Rewards_Balance', $this->siteLangId),
                        'rewardsPointsEarned' => Labels::getLabel('LBL_Rewards_Earned', $this->siteLangId),
                        'rewardsPointsRedeemed' => Labels::getLabel('LBL_Rewards_Redeemed', $this->siteLangId),
                        'netSoldQty' => Labels::getLabel('LBL_Item_Purchased', $this->siteLangId),
                        'orderNetAmount' => Labels::getLabel('LBL_Total_Purchase', $this->siteLangId),
                        'availableBalance' => Labels::getLabel('LBL_Available_Balance', $this->siteLangId)
                    ];
                    break;
            }
            FatCache::set('buyerUserReportsCacheVar' . $userType . '-' . $this->siteLangId, serialize($arr), '.txt');
        } else {
            $arr =  unserialize($buyerUserReportsCacheVar);
        }

        return $arr;
    }

    protected function getDefaultColumns($userType = User::USER_TYPE_BUYER): array
    {
        $arr = [];
        switch ($userType) {
            case User::USER_TYPE_SELLER:
                $arr = $arr + [
                    'name', 'totOrders', 'totQtys', 'totRefundedQtys', 'refundedAmount', 'orderNetAmount', 'availableBalance', 'promotionCharged', 'totRating',
                ];
                break;
            default:
                $arr = $arr + [
                    'name', 'rewardsPoints', 'rewardsPointsEarned', 'rewardsPointsRedeemed', 'netSoldQty', 'orderNetAmount', 'availableBalance',
                ];
                break;
        }
        return $arr;
    }

    private function validateViewPermission($userType = User::USER_TYPE_BUYER)
    {
        switch ($userType) {
            case User::USER_TYPE_SELLER;
                $this->objPrivilege->canViewSellersReport($this->admin_id);
                break;
            default:
                $this->objPrivilege->canViewUsersReport($this->admin_id);
                break;
        }
    }

    private function setPageKey($userType = User::USER_TYPE_BUYER)
    {
        switch ($userType) {
            case User::USER_TYPE_SELLER;
                $this->pageKey = 'SELLERS_REPORT';
                break;
            case User::USER_TYPE_BUYER;
                $this->pageKey = 'BUYERS_REPORT';
                break;
        }
    }

    public function getBreadcrumbNodes($action)
    {
        switch ($action) {
            case 'index':
                $this->setPageKey(current(FatApp::getParameters()));
                $pageData = PageLanguageData::getAttributesByKey($this->pageKey, $this->siteLangId);
                $pageTitle = $pageData['plang_title'] ?? LibHelper::getControllerName(true);
                $this->nodes = [
                    ['title' => Labels::getLabel('NAV_REPORTS', $this->siteLangId)],
                    ['title' => Labels::getLabel('NAV_USERS_REPORTS', $this->siteLangId)],
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
