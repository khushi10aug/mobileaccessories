<?php

class WithdrawalRequestsController extends ListingBaseController
{

    protected $modelClass = 'Transactions';
    protected $pageKey = 'MANAGE_WITHDRAWAL_REQUESTS';

    public function __construct($action)
    {
        parent::__construct($action);
        $this->objPrivilege->canViewWithdrawRequests();
    }

    /**
     * checkEditPrivilege - This function is used to check, set previlege and can be also used in parent class to validate request.
     *
     * @param  bool $setVariable
     * @return void
     */
    protected function checkEditPrivilege(bool $setVariable = false): void
    {
        if (true === $setVariable) {
            $this->set("canEdit", $this->objPrivilege->canEditWithdrawRequests($this->admin_id, true));
        } else {
            $this->objPrivilege->canEditWithdrawRequests();
        }
    }

    public function index()
    {
        $fields = $this->getFormColumns();
        $frmSearch = $this->getSearchForm($fields);
        $pageData = PageLanguageData::getAttributesByKey($this->pageKey, $this->siteLangId);
        $pageTitle = $pageData['plang_title'] ?? LibHelper::getControllerName(true);
        $this->setModel();
        $actionItemsData = HtmlHelper::getDefaultActionItems($fields, $this->modelObj);
        $actionItemsData['newRecordBtn'] = false;
        $actionItemsData['otherButtons'] = [
            [
                'attr' => [
                    'href' => 'javascript:void(0)',
                    'class' => 'btn btn-icon btn-link',
                    'onclick' => 'exportRecords()',
                    'title' => Labels::getLabel('LBL_Export', $this->siteLangId)
                ],
                'label' => '<svg class="svg btn-icon-start " width="18" height="18">
                                <use
                                    xlink:href="' . CONF_WEBROOT_URL . 'images/retina/sprite-actions.svg' . AttachedFile::setTimeParam(RELEASE_DATE) . '#export">
                                </use>
                            </svg><span>' . Labels::getLabel('LBL_Export', $this->siteLangId) . '</span>',
            ]
        ];

        $this->set('actionItemsData', $actionItemsData);
        $this->set('pageData', $pageData);
        $this->set('pageTitle', $pageTitle);
        $this->set("frmSearch", $frmSearch);
        $this->set('defaultColumns', $this->getDefaultColumns());
        $this->set('keywordPlaceholder', Labels::getLabel('FRM_SEARCH_BY_USER_NAME_OR_EMAIL', $this->siteLangId));
        $this->getListingData(false);
        
        $this->_template->addJs('withdrawal-requests/page-js/index.js');
        $this->includeFeatherLightJsCss();
        $this->_template->render(true, true, '_partial/listing/index.php');
    }

    protected function getSearchForm($fields = [])
    {
        $currency_id = FatApp::getConfig('CONF_CURRENCY', FatUtility::VAR_INT, 1);
        $currencyData = Currency::getAttributesById($currency_id, array('currency_code', 'currency_symbol_left', 'currency_symbol_right'));
        $currencySymbol = ($currencyData['currency_symbol_left'] != '') ? $currencyData['currency_symbol_left'] : $currencyData['currency_symbol_right'];
        $frm = new Form('frmRecordSearch');
        $frm->addHiddenField('', 'page');
        if (!empty($fields)) {
            $this->addSortingElements($frm, 'withdrawal_request_date', applicationConstants::SORT_DESC);
        }
        $fld = $frm->addTextBox(Labels::getLabel('FRM_KEYWORD', $this->siteLangId), 'keyword');
        $fld->overrideFldType('search');
        $statusArr = Transactions::getWithdrawlStatusArr($this->siteLangId);
        $frm->addSelectBox(Labels::getLabel('FRM_STATUS', $this->siteLangId), 'status', array('-1' => 'Does not matter') + $statusArr, '-1', [], '');

        $arr_options2 = array('-1' => Labels::getLabel('FRM_DOES_NOT_MATTER', $this->siteLangId)) + User::getUserTypesArr($this->siteLangId);
        $arr_options2 = $arr_options2 + array(User::USER_TYPE_BUYER_SELLER => Labels::getLabel('FRM_BUYER', $this->siteLangId) . '+' . Labels::getLabel('FRM_SELLER', $this->siteLangId));
        $arr_options2 = $arr_options2 + array(User::USER_TYPE_SUB_USER => Labels::getLabel('FRM_SUB_USER', $this->siteLangId));
        $frm->addSelectBox(Labels::getLabel('FRM_USER_TYPE', $this->siteLangId), 'type', $arr_options2, -1, array(), '');

        $frm->addDateField(Labels::getLabel('FRM_DATE_FROM', $this->siteLangId), 'date_from', '', array('placeholder' => Labels::getLabel('FRM_DATE_FROM', $this->siteLangId), 'readonly' => 'readonly', 'class' => 'field--calender'));
        $frm->addDateField(Labels::getLabel('FRM_DATE_TO', $this->siteLangId), 'date_to', '', array('placeholder' => Labels::getLabel('FRM_DATE_TO', $this->siteLangId), 'readonly' => 'readonly', 'class' => 'field--calender'));

        $str = CommonHelper::replaceStringData(Labels::getLabel('FRM_AMOUNT_FROM_[{CURRENCY-SYMBOL}]', $this->siteLangId), ['{CURRENCY-SYMBOL}' => $currencySymbol]);
        $frm->addTextBox(Labels::getLabel('FRM_AMOUNT_FROM', $this->siteLangId), 'price_from', '', array('placeholder' => $str));

        $str = CommonHelper::replaceStringData(Labels::getLabel('FRM_AMOUNT_TO[{CURRENCY-SYMBOL}]', $this->siteLangId), ['{CURRENCY-SYMBOL}' => $currencySymbol]);
        $frm->addTextBox(Labels::getLabel('FRM_AMOUNT_TO', $this->siteLangId), 'price_to', '', array('placeholder' => $str));
        $frm->addHiddenField('', 'total_record_count');
        HtmlHelper::addSearchButton($frm);
        HtmlHelper::addClearButton($frm);/*clearBtn*/
        return $frm;
    }

    public function search($reportType = false)
    {
        $this->getListingData($reportType);
        $jsonData = [
            'listingHtml' => $this->_template->render(false, false, 'withdrawal-requests/search.php', true),
            'paginationHtml' => $this->_template->render(false, false, '_partial/listing/listing-foot.php', true)
        ];
        LibHelper::exitWithSuccess($jsonData, true);
    }

    public function getListingData($reportType)
    {
        $pageSize = applicationConstants::getPageSize(FatApp::getPostedData('pageSize', FatUtility::VAR_INT));
        $data = FatApp::getPostedData();
        $fields = $this->getFormColumns();
        $selectedFlds = FatApp::getPostedData('reportColumns', FatUtility::VAR_STRING, '');
        $selectedFlds = !empty($selectedFlds) ? json_decode($selectedFlds) + $this->getDefaultColumns() : $this->getDefaultColumns();
        $fields = FilterHelper::parseArrayByKeys($fields, $selectedFlds, true);
        $allowedKeysForSorting = $this->excludeKeysForSort(array_keys($fields));
        $sortBy = FatApp::getPostedData('sortBy', FatUtility::VAR_STRING, 'withdrawal_request_date');
        if (!array_key_exists($sortBy, $fields)) {
            $sortBy = 'withdrawal_request_date';
        }

        $sortOrder = applicationConstants::getSortOrder(FatApp::getPostedData('sortOrder', FatUtility::VAR_STRING, applicationConstants::SORT_DESC), applicationConstants::SORT_DESC);
        $page = (empty($data['page']) || $data['page'] <= 0) ? 1 : $data['page'];
        $searchForm = $this->getSearchForm($fields);
        $post = $searchForm->getFormDataFromArray($data);

        $srch = new WithdrawalRequestsSearch();
        $srch->joinUsers(true);
        $srch->joinForUserBalance();
        $srch->joinTable(User::DB_TBL_USR_WITHDRAWAL_REQ_SPEC, 'LEFT JOIN', User::DB_TBL_USR_WITHDRAWAL_REQ_SPEC_PREFIX . 'withdrawal_id = tuwr.withdrawal_id');

        if (isset($post['keyword']) && $post['keyword']) {
            $cond = $srch->addCondition('credential_username', 'like', '%' . $post['keyword'] . '%');
            $cond->attachCondition('user_name', 'like', '%' . $post['keyword'] . '%', 'OR');
            $cond->attachCondition('credential_email', 'like', '%' . $post['keyword'] . '%', 'OR');
        }

        if (isset($post['price_from']) && $post['price_from'] > 0) {
            $srch->addCondition('tuwr.withdrawal_amount', '>=', $post['price_from']);
        }

        if (isset($post['price_to']) && $post['price_to'] > 0) {
            $srch->addCondition('tuwr.withdrawal_amount', '<=', $post['price_to']);
        }

        $recordId = FatApp::getPostedData('recordId', FatUtility::VAR_INT, -1);
        $withdrawalId = FatApp::getPostedData('withdrawal_id', FatUtility::VAR_INT, $recordId);
        if (0 < $withdrawalId) {
            $srch->addCondition('tuwr.withdrawal_id', '=', $withdrawalId);
        }

        if (isset($post['status']) && '' != $post['status'] &&  -1 < $post['status']) {
            $srch->addCondition('tuwr.withdrawal_status', '=', $post['status']);
        }

        if (isset($post['date_from']) && $post['date_from']) {
            $srch->addCondition('tuwr.withdrawal_request_date', '>=', $post['date_from'] . ' 00:00:00');
        }

        if (isset($post['date_to']) && $post['date_to']) {
            $srch->addCondition('tuwr.withdrawal_request_date', '<=', $post['date_to'] . ' 00:00:00');
        }

        $type = FatApp::getPostedData('type', FatUtility::VAR_INT, 0);
        if ($type > 0) {
            if ($type == User::USER_TYPE_SELLER) {
                $srch->addCondition('user_is_supplier', '=', 'mysql_func_' . applicationConstants::YES, 'AND');
            }
            if ($type == User::USER_TYPE_BUYER) {
                $srch->addCondition('user_is_buyer', '=', 'mysql_func_' . applicationConstants::YES, 'AND');
            }

            if ($type == User::USER_TYPE_ADVERTISER) {
                $srch->addCondition('user_is_advertiser', '=', 'mysql_func_' . applicationConstants::YES, 'AND');
            }

            if ($type == User::USER_TYPE_AFFILIATE) {
                $srch->addCondition('user_is_affiliate', '=', 'mysql_func_' . applicationConstants::YES, 'AND');
            }
        }
        $srch->addGroupBy('tuwr.withdrawal_id');
        $this->setRecordCount(clone $srch, $pageSize, $page, $post, true);
        $srch->doNotCalculateRecords();
        $srch->addMultipleFields(
            array(
                'tuwr.*', 'GROUP_CONCAT(CONCAT(`uwrs_key`, ":", `uwrs_value`)) as payout_detail', 'user_name', 'credential_email as user_email',
                'credential_username as user_username', 'user_balance', 'user_is_buyer', 'user_is_supplier', 'user_is_advertiser',
                'user_is_affiliate', 'user_id', 'user_updated_on', 'credential_username', 'credential_email'
            )
        );
        $srch->addOrder($sortBy, $sortOrder);

        $paymentMethods = User::getAffiliatePaymentMethodArr($this->siteLangId);
        $payoutPlugins = Plugin::getNamesByType(Plugin::TYPE_PAYOUTS, $this->siteLangId);

        if ($reportType == 'export') {
            $srch->doNotCalculateRecords();
            $srch->doNotLimitRecords();
            $rs = $srch->getResultSet();
            $sheetData = array();
            $exportField = [
                'user_email' => Labels::getLabel('LBL_USER_EMAIL', $this->siteLangId),
                'payout_address' => Labels::getLabel('LBL_PAYOUT_INFO', $this->siteLangId),                
            ];
            $exportField =  $fields + $exportField;
            unset($exportField['action']);
            array_push($sheetData, array_values($exportField));

            while ($row = FatApp::getDb()->fetch($rs)) {             
                $arr = [];
                foreach ($exportField as $key => $val) {
                    switch ($key) {
                        case 'user_balance':
                        case 'withdrawal_amount':
                            $arr[] = CommonHelper::displayMoneyFormat($row[$key], true, true);
                            break;
                        case 'withdrawal_payment_method':
                            $methodType = $paymentMethods + $payoutPlugins;
                            $methodName = (isset($row[$key]) && isset($methodType[$row[$key]]) ? $methodType[$row[$key]] : Labels::getLabel('LBL_N/A', $this->siteLangId));
                            $arr[] = $methodName;
                            break;
                        case 'withdrawal_request_date':
                            $arr[] = FatDate::format($row[$key], true);
                            break;
                        case 'withdrawal_status':
                            $statusArr = Transactions::getWithdrawlStatusArr($this->siteLangId);
                            $arr[] = $statusArr[$row[$key]] ?? Labels::getLabel('LBL_N/A', $this->siteLangId);
                            break;
                        case 'payout_address':
                            $str = '';
                            if($row['withdrawal_payment_method'] ==  User::AFFILIATE_PAYMENT_METHOD_BANK){
                                $str .= Labels::getLabel('LBL_BANK_NAME', $this->siteLangId)." - ".$row['withdrawal_bank'] ."\n";
                                $str .= Labels::getLabel('LBL_A/C_NAME', $this->siteLangId)." - ".$row['withdrawal_account_holder_name'] ."\n";
                                $str .= Labels::getLabel('LBL_A/C_NUMBER', $this->siteLangId)." - ".$row['withdrawal_account_number'] ."\n";
                                $str .= Labels::getLabel('LBL_IFSC_CODE/SWIFT_CODE', $this->siteLangId)." - ".$row['withdrawal_ifc_swift_code'] ."\n";
                                $str .= Labels::getLabel('LBL_BANK_ADDRESS', $this->siteLangId)." - ".$row['withdrawal_bank_address']; 
                            }elseif($row['withdrawal_payment_method'] ==  User::AFFILIATE_PAYMENT_METHOD_PAYPAL){

                            }elseif (array_key_exists($row['withdrawal_payment_method'], $payoutPlugins)) {  
                                if (!empty($row['payout_detail'])) {                                 
                                    $extraInfoArr = explode(',', $row["payout_detail"]); 
                                    foreach($extraInfoArr as $extrInfo){
                                        $extrInfo = explode(":", $extrInfo);
                                        if(!isset($extrInfo[0]) || !isset($extrInfo[1])){
                                            continue;
                                        }
                                        if($extrInfo[0] == 'amount' || $extrInfo[0] == 'payout'){
                                            continue;
                                        }     
                                        $str .= ucwords(str_replace('_', ' ', $extrInfo[0]))." - ".$extrInfo[1] ."\n";                                                                       
                                    }
                                }
                            }
                                                      
                            $arr[] = $str;
                            break;                       
                        default:
                            $arr[] = $row[$key];
                            break;
                    }
                }
               
                array_push($sheetData, $arr);
            } 
            CommonHelper::convertToCsv($sheetData, Labels::getLabel('LBL_WITHDRAWAL_REQUESTS', $this->siteLangId) . date("d-M-Y") . '.csv', ',');
            exit;
        }

        $page = FatUtility::int($page);
        $srch->setPageNumber($page);
        $srch->setPageSize($pageSize);
        $records = FatApp::getDb()->fetchAll($srch->getResultSet());
        $this->set('postedData', $post);
        $this->set('sortBy', $sortBy);
        $this->set('sortOrder', $sortOrder);
        $this->set('fields', $fields);
        $this->set("arrListing", $records);
        $this->set('statusArr', Transactions::getWithdrawlStatusArr($this->siteLangId));
        $this->set('allowedKeysForSorting', $allowedKeysForSorting);
        $this->set('canViewUsers', $this->objPrivilege->canViewUsers($this->admin_id, true));
        $this->checkEditPrivilege(true);
        $this->set('paymentMethods', $paymentMethods);
        $this->set('payoutPlugins', $payoutPlugins);
    }

    private function getForm($recordId)
    {
        $frm = new Form('frmUpdateStatus');
        $statusArr = [
            Transactions::WITHDRAWL_STATUS_PENDING => Labels::getLabel('LBL_WITHDRAWAL_REQUEST_PENDING', $this->siteLangId),
            Transactions::WITHDRAWL_STATUS_APPROVED => Labels::getLabel('LBL_WITHDRAWAL_REQUEST_APPROVED', $this->siteLangId),
            Transactions::WITHDRAWL_STATUS_DECLINED => Labels::getLabel('LBL_WITHDRAWAL_REQUEST_DECLINED', $this->siteLangId)
        ];
        $frm->addSelectBox(Labels::getLabel('FRM_STATUS', $this->siteLangId), 'withdrawal_status', $statusArr, '', array(), '');
        $frm->addTextarea(Labels::getLabel('FRM_COMMENT', $this->siteLangId), 'withdrawal_comments');
        $frm->addHiddenField('', 'withdrawal_id', $recordId);

        return $frm;
    }

    public function form()
    {
        $this->checkEditPrivilege();
        $recordId = FatApp::getPostedData('recordId', FatUtility::VAR_INT, 0);
        $row = WithdrawalRequest::getAttributesById($recordId);
        if (1 > $recordId || !$row || $row['withdrawal_status'] != Transactions::WITHDRAWL_STATUS_PENDING) {
            LibHelper::exitWithError($this->str_invalid_request, true);
        }
        $this->set('recordId', $recordId);
        $this->set('frm', $this->getForm($recordId));
        $this->set('withdrawal_payment_method', $row['withdrawal_payment_method']);
        $this->set('displayLangTab', false);
        $this->set('includeTabs', false);
        $this->set('formTitle', Labels::getLabel('LBL_WITHDRAWAL_REQUEST_UPDATE', $this->siteLangId));
        $this->set('html', $this->_template->render(false, false, NULL, true));
        $this->_template->render(false, false, 'json-success.php', true, false);
    }

    public function setup()
    {
        $this->checkEditPrivilege();
        $recordId = FatApp::getPostedData('withdrawal_id', FatUtility::VAR_INT, 0);
        $frm = $this->getForm($recordId);
        $post = $frm->getFormDataFromArray(FatApp::getPostedData());
        if (false == $post) {
            LibHelper::exitWithError($frm->getValidationErrors(), true);
        }

        $allowedStatusUpdateArr = array(Transactions::WITHDRAWL_STATUS_APPROVED, Transactions::WITHDRAWL_STATUS_DECLINED);
        $row = WithdrawalRequest::getAttributesById($recordId);

        if (!$row || !in_array($post['withdrawal_status'], $allowedStatusUpdateArr)) {
            LibHelper::exitWithError($this->str_invalid_request, true);
        }

        $comment = $post['withdrawal_comments'];
        $assignFields = array('withdrawal_status' => $post['withdrawal_status'], 'withdrawal_comments' => $comment);
        if (!FatApp::getDb()->updateFromArray(User::DB_TBL_USR_WITHDRAWAL_REQ, $assignFields, array('smt' => 'withdrawal_id=?', 'vals' => array($recordId)))) {
            LibHelper::exitWithError(FatApp::getDb()->getError(), true);
        }

        $emailNotificationObj = new EmailHandler();
        if (!$emailNotificationObj->sendWithdrawRequestNotification($recordId, $this->siteLangId, "U")) {
            LibHelper::exitWithError($emailNotificationObj->getError(), true);
        }

        $assignFields = array('utxn_status' => Transactions::STATUS_COMPLETED);
        if ($post['withdrawal_status'] == Transactions::WITHDRAWL_STATUS_APPROVED) {
            $transSrch = Transactions::getSearchObject();
            $transSrch->addCondition('utxn_withdrawal_id','=',$recordId);
            $transSrch->addFld('utxn_comments');
            $transSrch->setPageSize(1);
            $transSrch->doNotCalculateRecords();
            $oldTransData = FatApp::getDb()->fetch($transSrch->getResultSet());
            if(false !== $oldTransData && !empty($comment)){
                $assignFields['utxn_comments'] = $oldTransData['utxn_comments'] . " (" . $comment . ")";
            }                       
        }

        FatApp::getDb()->updateFromArray(
            Transactions::DB_TBL,
            $assignFields,
            array('smt' => 'utxn_withdrawal_id=?', 'vals' => array($recordId))
        );

        if ($post['withdrawal_status'] == Transactions::WITHDRAWL_STATUS_DECLINED) {
            $transObj = new Transactions();
            $txnDetail = $transObj->getAttributesBywithdrawlId($recordId);
            $formattedRequestValue = '#' . str_pad($recordId, 6, '0', STR_PAD_LEFT);

            $txnArray["utxn_user_id"] = $txnDetail["utxn_user_id"];
            $txnArray["utxn_credit"] = $txnDetail["utxn_debit"];
            $txnArray["utxn_status"] = Transactions::STATUS_COMPLETED;
            $txnArray["utxn_withdrawal_id"] = $txnDetail["utxn_withdrawal_id"];
            $txnArray["utxn_type"] = Transactions::TYPE_MONEY_WITHDRAWL_REFUND;
            $txnArray["utxn_comments"] = sprintf(Labels::getLabel('MSG_WITHDRAWAL_REQUEST_DECLINED_AMOUNT_REFUNDED', $this->siteLangId), $formattedRequestValue);
            if (!empty($comment)) {
                $txnArray["utxn_comments"] = $txnArray["utxn_comments"] . "( " . $comment . " )";
            }

            if ($txnId = $transObj->addTransaction($txnArray)) {
                $emailNotificationObj->sendTxnNotification($txnId, $this->siteLangId);
            }
        }
        CalculativeDataRecord::updateWithdrawalRequestCount();
        $this->set('msg', Labels::getLabel('MSG_STATUS_UPDATED_SUCCESSFULLY', $this->siteLangId));
        $this->_template->render(false, false, 'json-success.php');
    }

    public function viewDetails($recordId, $langId = 0)
    {
        $this->checkEditPrivilege();
        $srch = new WithdrawalRequestsSearch();
        $srch->joinUsers(true);
        $srch->joinForUserBalance();
        $srch->joinTable(User::DB_TBL_USR_WITHDRAWAL_REQ_SPEC, 'LEFT JOIN', User::DB_TBL_USR_WITHDRAWAL_REQ_SPEC_PREFIX . 'withdrawal_id = tuwr.withdrawal_id');
        $srch->addCondition('withdrawal_id', '=', $recordId);
        $srch->addMultipleFields(
            array(
                'tuwr.*', 'GROUP_CONCAT(CONCAT(`uwrs_key`, ":", `uwrs_value`)) as payout_detail', 'user_name', 'credential_email as user_email',
                'credential_username as user_username', 'user_balance', 'user_is_buyer', 'user_is_supplier', 'user_is_advertiser',
                'user_is_affiliate', 'user_id', 'user_updated_on', 'credential_username', 'credential_email'
            )
        );
        $record = FatApp::getDb()->fetch($srch->getResultSet());

        if (!$record) {
            LibHelper::exitWithError($this->str_invalid_request, true);
        }
        $this->set('details', $record);
        $this->set('html', $this->_template->render(false, false, NULL, true));
        $this->_template->render(false, false, 'json-success.php', true, false);
    }

    /*     * ************ */

    /**
     * Undocumented function
     *
     * @return array
     */
    protected function getFormColumns(): array
    {
        $withdrawalRequestsTblHeadingCols = CacheHelper::get('withdrawalRequestsTblHeadingCols' . $this->siteLangId, CONF_DEF_CACHE_TIME, '.txt');
        if ($withdrawalRequestsTblHeadingCols) {
            return json_decode($withdrawalRequestsTblHeadingCols, true);
        }

        $arr = [
            /* 'listSerial' => Labels::getLabel('LBL_ID', $this->siteLangId), */
            'user_name' => Labels::getLabel('LBL_USER_DETAILS', $this->siteLangId),
            'user_balance' => Labels::getLabel('LBL_WALLET_BALANCE', $this->siteLangId),
            'withdrawal_amount' => Labels::getLabel('LBL_REQUESTED_AMOUNT', $this->siteLangId),
            'withdrawal_payment_method' => Labels::getLabel('LBL_WITHDRAWAL_MODE', $this->siteLangId),
            'withdrawal_request_date' => Labels::getLabel('LBL_DATE', $this->siteLangId),
            'withdrawal_status' => Labels::getLabel('LBL_STATUS', $this->siteLangId),
            'action' => Labels::getLabel('LBL_ACTION_BUTTONS', $this->siteLangId),
        ];
        CacheHelper::create('withdrawalRequestsTblHeadingCols' . $this->siteLangId, json_encode($arr), CacheHelper::TYPE_LABELS);
        return $arr;
    }

    /**
     * Undocumented function
     *
     * @return array
     */
    protected function getDefaultColumns(): array
    {
        return [
            /* 'listSerial', */
            'user_name',
            'user_balance',
            'withdrawal_amount',
            'withdrawal_payment_method',
            'withdrawal_request_date',
            'withdrawal_status',
            'action'
        ];
    }

    /**
     * Undocumented function
     *
     * @param array $fields
     * @return array
     */
    protected function excludeKeysForSort($fields = []): array
    {
        return array_diff($fields, Common::excludeKeysForSort());
    }
}
