<?php

class RewardsOnPurchaseController extends ListingBaseController
{
    protected $pageKey = 'REWARDS_ON_PURCHASE';

    public function __construct($action)
    {
        parent::__construct($action);
        $this->objPrivilege->canViewRewardsOnPurchase();
    }

    public function index()
    {
        $fields = $this->getFormColumns();
        $frmSearch = $this->getSearchForm($fields);

        $pageData = PageLanguageData::getAttributesByKey($this->pageKey, $this->siteLangId);
        $pageTitle = $pageData['plang_title'] ?? LibHelper::getControllerName(true);

        $actionItemsData = HtmlHelper::getDefaultActionItems($fields);
        $actionItemsData['newRecordBtnAttrs'] = [
            'attr' => [
                'onclick' => "addNew(true)",
            ]
        ];
        $actionItemsData['headerHtmlContent'] = '<a href="'.UrlHelper::generateUrl('configurations','index', [Configurations::FORM_REWARD_POINTS]).'" class="btn btn-icon btn-outline-gray ms-2" title="" data-bs-toggle="tooltip" data-placement="top" data-bs-original-title="'.Labels::getLabel('FRM_REWARD_GLOBAL_SETTINGS', $this->siteLangId).'">
            <svg class="svg btn-icon-start" width="18" height="18">
                <use xlink:href="'.CONF_WEBROOT_URL.'images/retina/sprite-actions.svg' . AttachedFile::setTimeParam(RELEASE_DATE) . '#gear">
                </use>
            </svg>                                
        </a>';   
             
        $actionItemsData['deleteButton'] = true;
        $actionItemsData['performBulkAction'] = true;
        $actionItemsData['formAction'] = 'deleteSelected';

        $this->set('pageData', $pageData);
        $this->set('pageTitle', $pageTitle);
        $this->set('actionItemsData', $actionItemsData);
        $this->set("frmSearch", $frmSearch);
        $this->set('defaultColumns', $this->getDefaultColumns());
        $this->set('keywordPlaceholder', Labels::getLabel('FRM_SEARCH_BY_PURCHASE_AMOUNT', $this->siteLangId));
        $this->getListingData();
        
        $this->_template->render(true, true, '_partial/listing/index.php', false, false);
    }

    public function search()
    {
        $this->getListingData();
        $jsonData = [
            'listingHtml' => $this->_template->render(false, false, 'rewards-on-purchase/search.php', true),
            'paginationHtml' => $this->_template->render(false, false, '_partial/listing/listing-foot.php', true)
        ];
        LibHelper::exitWithSuccess($jsonData, true);
    }

    private function getListingData()
    {
        $post = FatApp::getPostedData();

        $fields = $this->getFormColumns();
        $selectedFlds = FatApp::getPostedData('reportColumns', FatUtility::VAR_STRING, '');
        $selectedFlds = !empty($selectedFlds) ? json_decode($selectedFlds) +  $this->getDefaultColumns() : $this->getDefaultColumns();
        $fields =  FilterHelper::parseArrayByKeys($fields, $selectedFlds, true);

        $allowedKeysForSorting = $this->excludeKeysForSort(array_keys($fields));
        $sortBy = FatApp::getPostedData('sortBy', FatUtility::VAR_STRING, current($allowedKeysForSorting));
        if (!array_key_exists($sortBy, $fields)) {
            $sortBy = current($allowedKeysForSorting);
        }

        $sortOrder = applicationConstants::getSortOrder(FatApp::getPostedData('sortOrder', FatUtility::VAR_STRING));

        $srchFrm = $this->getSearchForm($fields);

        $post = $srchFrm->getFormDataFromArray(FatApp::getPostedData());
        
        $page = FatApp::getPostedData('page', FatUtility::VAR_INT, 1);
        $page = ($page <= 0) ? 1 : $page;

        $pageSize = applicationConstants::getPageSize(FatApp::getPostedData('pageSize', FatUtility::VAR_INT));

        $srch = RewardsOnPurchase::getSearchObject(); 
        if (isset($post['keyword']) && '' != $post['keyword']) {
            $srch->addCondition('rop.rop_purchase_upto', 'like', '%' . $post['keyword'] . '%');
        }
        
        if (!empty($post['rop_reward_point'])) {
            $srch->addCondition('rop.rop_reward_point', 'like', '%' . $post['rop_reward_point'] . '%');
        }
        
        $this->setRecordCount(clone $srch, $pageSize, $page, $post);
        $srch->doNotCalculateRecords();
        
        $srch->addMultipleFields(['rop.*']);
        $srch->addOrder($sortBy, $sortOrder); 
        $srch->setPageNumber($page);
        $srch->setPageSize($pageSize);
        $rs = $srch->getResultSet();
        $records = FatApp::getDb()->fetchAll($rs);

        $this->set("arrListing", $records); 
        $this->set('postedData', $post); 
        $this->set('sortBy', $sortBy);
        $this->set('sortOrder', $sortOrder);
        $this->set('fields', $fields);
        $this->set('allowedKeysForSorting', $allowedKeysForSorting);
        $this->set('canEdit', $this->objPrivilege->canEditRewardsOnPurchase($this->admin_id, true)); 
    }

    public function form()
    {
        $recordId = FatApp::getPostedData('recordId', FatUtility::VAR_INT, 0);
        $frm = $this->getForm();

        if (0 < $recordId) {
            $data = RewardsOnPurchase::getAttributesById($recordId);
            if ($data === false) {
                LibHelper::exitWithError($this->str_invalid_request, true);
            }
            $frm->fill($data);
        }

        $this->set('languages', Language::getAllNames());
        $this->set('recordId', $recordId);
        $this->set('frm', $frm);
        $this->set('includeTabs', false);
        $this->set('formTitle', Labels::getLabel('LBL_REWARDS_ON_PURCHASE_SETUP', $this->siteLangId));
        $this->set('html', $this->_template->render(false, false, NULL, true));
        $this->_template->render(false, false, 'json-success.php', true, false);
    }

    public function setup()
    {
        $this->objPrivilege->canEditRewardsOnPurchase();

        $post = FatApp::getPostedData();
        $recordId = FatApp::getPostedData('rop_id', FatUtility::VAR_INT, 0);

        $frm = $this->getForm();
        $post = $frm->getFormDataFromArray($post);

        if (false === $post) {
            LibHelper::exitWithError(current($frm->getValidationErrors()), true);
        }
        $purchaseUpto = FatApp::getPostedData('rop_purchase_upto', FatUtility::VAR_FLOAT, 0);
        if (1 > $purchaseUpto) {
            LibHelper::exitWithError(Labels::getLabel('ERR_INVALID_PURCHASE_UPTO_VALUE', $this->siteLangId), true);
        }

        $srch = RewardsOnPurchase::getSearchObject();
        $srch->doNotCalculateRecords();
        $srch->addFld('rop_id');
        $srch->addCondition('rop_purchase_upto', '=', $purchaseUpto);
        $srch->addCondition('rop_id', '!=', $recordId);
        $result = FatApp::getDb()->fetch($srch->getResultSet());
        if (is_array($result) && !empty($result)) {
            LibHelper::exitWithError(Labels::getLabel('ERR_SAME_PURCHASE_AMOUNT_ALREADY_ADDED', $this->siteLangId), true);
        }
        
        unset($post['rop_id']);
        $record = new RewardsOnPurchase($recordId);
        $record->assignValues($post);

        if (!$record->save()) {
            LibHelper::exitWithError($record->getError(), true);
        }

        FatUtility::dieJsonSuccess($this->str_update_record);
    }

    public function deleteRecord()
    {
        $this->objPrivilege->canEditRewardsOnPurchase();

        $recordId = FatApp::getPostedData('recordId', FatUtility::VAR_INT, 0);
        if ($recordId < 1) {
            LibHelper::exitWithError($this->str_invalid_request_id, true);
        }

        $this->markAsDeleted($recordId);

        $this->set('msg', $this->str_delete_record);
        $this->_template->render(false, false, 'json-success.php');
    }

    public function deleteSelected()
    {
        $this->objPrivilege->canEditRewardsOnPurchase();
        $recordIdsArr = FatUtility::int(FatApp::getPostedData('rop_ids'));

        if (empty($recordIdsArr)) {
            LibHelper::exitWithError($this->str_invalid_request, true);
        }

        foreach ($recordIdsArr as $recordId) {
            if (1 > $recordId) {
                continue;
            }
            $this->markAsDeleted($recordId);
        }
        $this->set('msg', Labels::getLabel('MSG_RECORDS_DELETED_SUCCESSFULLY', $this->siteLangId));
        $this->_template->render(false, false, 'json-success.php');
    }

    protected function markAsDeleted($ropId)
    {
        $ropId = FatUtility::int($ropId);
        if (1 > $ropId) {
            LibHelper::exitWithError($this->str_invalid_request, true);
        }
        $obj = new RewardsOnPurchase($ropId);

        $data = RewardsOnPurchase::getAttributesById($ropId, array('rop_id'));
        if ($data == false) {
            LibHelper::exitWithError($this->str_invalid_request_id, true);
        }

        if (!$obj->deleteRecord(false)) {
            LibHelper::exitWithError($obj->getError(), true);
        }
    }

    public function getSearchForm($fields = [])
    {
        $frm = new Form('frmRecordSearch');
        if (!empty($fields)) {
            $this->addSortingElements($frm, 'rop_purchase_upto');
        }
        
        $fld = $frm->addTextBox(Labels::getLabel('FRM_PURCHASE_AMOUNT', $this->siteLangId), 'keyword');
        $fld->overrideFldType('search');
        
        $frm->addTextBox(Labels::getLabel('FRM_REWARD_POINTS', $this->siteLangId), 'rop_reward_point');
        $frm->addHiddenField('', 'total_record_count'); 
        HtmlHelper::addSearchButton($frm);
        HtmlHelper::addClearButton($frm);
        return $frm;
    }

    private function getForm($recordId = 0)
    {
        $frm = new Form('frmRewardsOnPurchase');
        $frm->addHiddenField('', 'rop_id', $recordId);
        $fld = $frm->addFloatField(Labels::getLabel('FRM_MINIMUM_PURCHASE_AMOUNT', $this->siteLangId), 'rop_purchase_upto');
        $fld->requirements()->setFloatPositive();
        $fld = $frm->addFloatField(Labels::getLabel('FRM_REWARD_POINT', $this->siteLangId), 'rop_reward_point');
        $fld->requirements()->setFloatPositive();
        return $frm;
    }

    protected function getFormColumns(): array
    {
        $rewardsOnPurchaseTblHeadingCols = CacheHelper::get('rewardsOnPurchaseTblHeadingCols' . $this->siteLangId, CONF_DEF_CACHE_TIME, '.txt');
        if ($rewardsOnPurchaseTblHeadingCols) {
            return json_decode($rewardsOnPurchaseTblHeadingCols, true);
        }

        $arr = [
            'select_all' => Labels::getLabel('LBL_SELECT_ALL', $this->siteLangId),
            /* 'listSerial' => Labels::getLabel('LBL_SR._NO', $this->siteLangId), */
            'rop_purchase_upto' => Labels::getLabel('LBL_PURCHASE', $this->siteLangId),
            'rop_reward_point' => Labels::getLabel('LBL_REWARD_POINT', $this->siteLangId),            
            'action' => Labels::getLabel('LBL_ACTION_BUTTONS', $this->siteLangId),
        ];

        if(count(Language::getAllNames()) < 2 ){
            unset($arr['language_name']);
        }

        CacheHelper::create('rewardsOnPurchaseTblHeadingCols' . $this->siteLangId, json_encode($arr), CacheHelper::TYPE_LABELS);
        return $arr;
    }

    protected function getDefaultColumns(): array
    {
        return [    
            'select_all',
            /* 'listSerial', */
            'rop_purchase_upto',
            'rop_reward_point',
            'action',
        ];
    }

    protected function excludeKeysForSort($fields = []): array
    {
        return array_diff($fields, Common::excludeKeysForSort());
    }
}
