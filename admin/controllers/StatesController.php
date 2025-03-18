<?php

class StatesController extends ListingBaseController
{
    protected string $modelClass = 'States';

    public function __construct($action)
    {
        parent::__construct($action);
        $this->objPrivilege->canViewStates();
    }

    public function index()
    {
        $fields = $this->getFormColumns();
        $frmSearch = $this->getSearchForm($fields);

        $pageData = PageLanguageData::getAttributesByKey('MANAGE_STATES', $this->siteLangId);
        $pageTitle = $pageData['plang_title'] ?? LibHelper::getControllerName(true);

        $this->setModel();
        $actionItemsData = HtmlHelper::getDefaultActionItems($fields, $this->modelObj);

        $this->set('pageData', $pageData);
        $this->set('pageTitle', $pageTitle);
        $this->set('actionItemsData', $actionItemsData);
        $this->set("frmSearch", $frmSearch);
        $this->set('defaultColumns', $this->getDefaultColumns());
        $this->set('canEdit', $this->objPrivilege->canEditStates($this->admin_id, true));
        $this->set('keywordPlaceholder', Labels::getLabel('FRM_SEARCH_STATES', $this->siteLangId));
        $this->getListingData();
        $this->_template->render(true, true, '_partial/listing/index.php');
    }

    /**
     * setLangTemplateData - This function is use to automate load langform and save it. 
     *
     * @param  array $constructorArgs
     * @return void
     */
    protected function setLangTemplateData(array $constructorArgs = []): void
    {
        $this->objPrivilege->canEditStates();
        $this->setModel($constructorArgs);
        $this->formLangFields = [$this->modelObj::tblFld('name')];
        $this->set('formTitle', Labels::getLabel('LBL_STATE_SETUP', $this->siteLangId));
    }

    public function getSearchForm($fields = [])
    {
        $frm = new Form('frmRecordSearch');
        if (!empty($fields)) {
            $this->addSortingElements($frm, 'state_identifier');
        }
        $fld = $frm->addTextBox(Labels::getLabel('FRM_KEYWORD', $this->siteLangId), 'keyword');
        $fld->overrideFldType('search');

        $countryObj = new Countries();
        $countriesArr = $countryObj->getCountriesAssocArr($this->siteLangId, true);

        $frm->addSelectBox(Labels::getLabel('FRM_COUNTRY', $this->siteLangId), 'country', $countriesArr, '', [], Labels::getLabel('FRM_SELECT_COUNTRY', $this->siteLangId));
        $frm->addHiddenField('', 'total_record_count'); 
        HtmlHelper::addSearchButton($frm);
        HtmlHelper::addClearButton($frm);
        return $frm;
    }

    public function search()
    {
        $this->getListingData();
        $jsonData = [
            'listingHtml' => $this->_template->render(false, false, 'states/search.php', true),
            'paginationHtml' => $this->_template->render(false, false, '_partial/listing/listing-foot.php', true)
        ];
        LibHelper::exitWithSuccess($jsonData, true);
    }

    private function getListingData()
    {
        $pageSize = applicationConstants::getPageSize(FatApp::getPostedData('pageSize', FatUtility::VAR_INT));

        $data = FatApp::getPostedData();

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

        $searchForm = $this->getSearchForm($fields);

        $page = (empty($data['page']) || $data['page'] <= 0) ? 1 : $data['page'];
        $post = $searchForm->getFormDataFromArray($data);

        $srch = States::getSearchObject(false, $this->siteLangId);
        $countrySearchObj = Countries::getSearchObject(true, $this->siteLangId);
        $countrySearchObj->doNotCalculateRecords();
        $countrySearchObj->doNotLimitRecords();
        $countriesDbView = $countrySearchObj->getQuery();

        $srch->joinTable(
            "($countriesDbView)",
            'INNER JOIN',
            'st.' . States::DB_TBL_PREFIX . 'country_id = c.' . Countries::tblFld('id'),
            'c'
        );
            
        if (isset($post['keyword']) && '' != $post['keyword']) {
            $condition = $srch->addCondition('st.state_identifier', 'like', '%' . $post['keyword'] . '%');
            $condition->attachCondition('st_l.state_name', 'like', '%' . $post['keyword'] . '%', 'OR');
            $condition->attachCondition('st.state_code', 'like', $post['keyword'], 'OR');
        }
        if (!empty($post['country'])) {
            $condition = $srch->addCondition('st.state_country_id', '=', $post['country']);
        }
        $this->setRecordCount(clone $srch, $pageSize, $page, $post);
        $srch->doNotCalculateRecords();
        $srch->addMultipleFields(array('st.*', 'st_l.state_name', 'c.country_name'));
        $page = (empty($page) || $page <= 0) ? 1 : $page;
        $page = FatUtility::int($page);
        $srch->setPageNumber($page);
        $srch->setPageSize($pageSize);
        $srch->addOrder($sortBy, $sortOrder); 
        $records = FatApp::getDb()->fetchAll($srch->getResultSet()); 
        $this->set('activeInactiveArr', applicationConstants::getActiveInactiveArr($this->siteLangId));
        $this->set("arrListing", $records); 
        $this->set('postedData', $post); 
        $this->set('sortBy', $sortBy);
        $this->set('sortOrder', $sortOrder);
        $this->set('fields', $fields);
        $this->set('allowedKeysForSorting', $allowedKeysForSorting);
        $this->set('canEdit', $this->objPrivilege->canEditStates($this->admin_id, true));
    }

    public function form()
    {
        $this->objPrivilege->canEditStates();
        $recordId = FatApp::getPostedData('recordId', FatUtility::VAR_INT, 0);

        $frm = $this->getForm();

        if (0 < $recordId) {
            $data = States::getAttributesByLangId(CommonHelper::getDefaultFormLangId(), $recordId, ['*','IFNULL(state_name,state_identifier) as state_name'], applicationConstants::JOIN_RIGHT);
            if ($data === false) {
                LibHelper::exitWithError($this->str_invalid_request, true);
            }
            $frm->fill($data);
        }

        HtmlHelper::addIdentierToFrm($frm->getField($this->modelClass::tblFld('name')), ($data[$this->modelClass::tblFld('identifier')] ?? ''));

        $this->set('recordId', $recordId);
        $this->set('frm', $frm);
        $this->set('formTitle', Labels::getLabel('LBL_STATE_SETUP', $this->siteLangId));
        $this->set('html', $this->_template->render(false, false, '_partial/listing/form.php', true));
        $this->_template->render(false, false, 'json-success.php', true, false);
    }

    public function setup()
    {
        $this->objPrivilege->canEditStates();
        $frm = $this->getForm();
        $post = $frm->getFormDataFromArray(FatApp::getPostedData());

        if (false === $post) {
            LibHelper::exitWithError(current($frm->getValidationErrors()), true);
        }

        $recordId = $post['state_id'];
        unset($post['state_id']);

        $recordObj = new States($recordId);
        $post['state_identifier'] = $post['state_name'];
        $recordObj->assignValues($post);

        if (!$recordObj->save()) {
            $msg = $recordObj->getError();
            if (false !== strpos(strtolower($msg), 'duplicate')) {
                $msg = Labels::getLabel('ERR_DUPLICATE_RECORD_NAME', $this->siteLangId);
            }
            LibHelper::exitWithError($msg, true);
        }

        if(applicationConstants::INACTIVE == $post['state_active']) {
            if(!Shop::updateShopsDisplayStatus(stateId: $recordId)){
                LibHelper::exitWithError(Labels::getLabel('ERR_UNABLE_TO_UPDATE_DISPLAY_STATUS_OF_SHOP'), true);
            }
        }

        $this->setLangData($recordObj, [$recordObj::tblFld('name') => $post[$recordObj::tblFld('name')]]);

        CacheHelper::clear(CacheHelper::TYPE_ZONE);
        Product::updateMinPrices(0, 0, 0, 0, $recordId);
        $this->_template->render(false, false, 'json-success.php');
    }

    private function getForm()
    {
        $frm = new Form('frmState');
        $frm->addHiddenField('', 'state_id');
        $frm->addRequiredField(Labels::getLabel('FRM_STATE_NAME', $this->siteLangId), 'state_name');
        //$frm->addRequiredField(Labels::getLabel('FRM_STATE_IDENTIFIER', $this->siteLangId), 'state_identifier');
        $frm->addRequiredField(Labels::getLabel('FRM_STATE_CODE', $this->siteLangId), 'state_code');
        $countryObj = new Countries();
        $countriesArr = $countryObj->getCountriesAssocArr($this->siteLangId, true);

        $frm->addSelectBox(Labels::getLabel('FRM_COUNTRY', $this->siteLangId), 'state_country_id', $countriesArr, '', array(), '');

        $fld = $frm->addCheckBox(Labels::getLabel('FRM_STATUS', $this->siteLangId), 'state_active', applicationConstants::ACTIVE, [], true, applicationConstants::INACTIVE);
        HtmlHelper::configureSwitchForCheckbox($fld);
        $fld->developerTags['noCaptionTag'] = true;

        $languageArr = Language::getDropDownList();
        $translatorSubscriptionKey = FatApp::getConfig('CONF_TRANSLATOR_SUBSCRIPTION_KEY', FatUtility::VAR_STRING, '');
        if (!empty($translatorSubscriptionKey) && 1 < count($languageArr)) {
            $frm->addCheckBox(Labels::getLabel('FRM_UPDATE_OTHER_LANGUAGES_DATA', $this->siteLangId), 'auto_update_other_langs_data', 1, array(), false, 0);
        }
        return $frm;
    }

    protected function getLangForm($recordId = 0, $langId = 0)
    {
        $langId = 1 > $langId ? $this->siteLangId : $langId;
        $this->objPrivilege->canViewStates();
        $frm = new Form('frmStateLang');
        $frm->addHiddenField('', 'state_id', $recordId);
        $frm->addSelectBox(Labels::getLabel('FRM_LANGUAGE', $langId), 'lang_id', Language::getDropDownList(CommonHelper::getDefaultFormLangId()), $langId, array(), '');
        $frm->addRequiredField(Labels::getLabel('FRM_STATE_NAME', $langId), 'state_name');
        return $frm;
    }

    public function updateStatus()
    {
        $this->objPrivilege->canEditStates();
        $recordId = FatApp::getPostedData('recordId', FatUtility::VAR_INT, 0);
        if (0 >= $recordId) {
            LibHelper::exitWithError($this->str_invalid_request_id, true);
        }

        $status = FatApp::getPostedData('status', FatUtility::VAR_INT, 0);
        if (!in_array($status, [applicationConstants::ACTIVE, applicationConstants::INACTIVE])) {
            LibHelper::exitWithError($this->str_invalid_request, true);
        }

        $this->changeStatus($recordId, $status);
        Product::updateMinPrices(0, 0, 0, 0, $recordId);
        FatUtility::dieJsonSuccess(Labels::getLabel('LBL_STATUS_UPDATED', $this->siteLangId));
    }
    public function toggleBulkStatuses()
    {
        $this->objPrivilege->canEditStates();
        $status = FatApp::getPostedData('status', FatUtility::VAR_INT, -1);
        $recordIdsArr = FatUtility::int(FatApp::getPostedData('state_ids'));
        if (empty($recordIdsArr) || -1 == $status) {
            LibHelper::exitWithError($this->str_invalid_request, true);
        }

        foreach ($recordIdsArr as $recordId) {
            if (1 > $recordId) {
                continue;
            }

            $this->changeStatus($recordId, $status);
        }
        Product::updateMinPrices();
        $this->set('msg', Labels::getLabel('MSG_STATUS_UPDATED', $this->siteLangId));
        $this->_template->render(false, false, 'json-success.php');
    }

    protected function changeStatus($recordId, $status)
    {
        $status = FatUtility::int($status);
        $recordId = FatUtility::int($recordId);
        if (1 > $recordId || -1 == $status) {
            LibHelper::exitWithError($this->str_invalid_request, true);
        }

        if(applicationConstants::INACTIVE == $status) {
            if(!Shop::updateShopsDisplayStatus(stateId: $recordId)){
                LibHelper::exitWithError(Labels::getLabel('ERR_UNABLE_TO_UPDATE_DISPLAY_STATUS_OF_SHOP'), true);
            }
        }

        $stateObj = new States($recordId);
        if (!$stateObj->changeStatus($status)) {
            LibHelper::exitWithError($stateObj->getError(), true);
        }
        CacheHelper::clear(CacheHelper::TYPE_ZONE);
    }

    protected function getFormColumns(): array
    {
        $statesTblHeadingCols = CacheHelper::get('statesTblHeadingCols' . $this->siteLangId, CONF_DEF_CACHE_TIME, '.txt');
        if ($statesTblHeadingCols) {
            return json_decode($statesTblHeadingCols, true);
        }

        $arr = [
            'select_all' => Labels::getLabel('LBL_Select_all', $this->siteLangId),
            /* 'listSerial' => Labels::getLabel('LBL_SR._NO', $this->siteLangId), */
            'state_name' => Labels::getLabel('LBL_State_Name', $this->siteLangId),
            'state_code' => Labels::getLabel('LBL_State_Code', $this->siteLangId),
            'country_name' => Labels::getLabel('LBL_Country_Name', $this->siteLangId),
            'state_active' => Labels::getLabel('LBL_Status', $this->siteLangId),
            'action' => Labels::getLabel('LBL_ACTION_BUTTONS', $this->siteLangId),
        ];
        CacheHelper::create('statesTblHeadingCols' . $this->siteLangId, json_encode($arr), CacheHelper::TYPE_LABELS);

        return $arr;
    }

    protected function getDefaultColumns(): array
    {
        return [
            'select_all',
            /* 'listSerial', */
            'state_identifier',
            'state_name',
            'state_code',
            'country_name',
            'state_active',
            'action',
        ];
    }

    protected function excludeKeysForSort($fields = []): array
    {
        return array_diff($fields, ['state_active'], Common::excludeKeysForSort());
    }

    public function getBreadcrumbNodes($action)
    {
        $pageData = PageLanguageData::getAttributesByKey('MANAGE_STATES', $this->siteLangId);
        $pageTitle = $pageData['plang_title'] ?? LibHelper::getControllerName(true);

        switch ($action) {
            case 'index':
                $this->nodes = [
                    ['title' => Labels::getLabel('LBL_SETTINGS', $this->siteLangId), 'href' => UrlHelper::generateUrl('Settings')],
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
