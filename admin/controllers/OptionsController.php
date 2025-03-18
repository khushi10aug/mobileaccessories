<?php

class OptionsController extends ListingBaseController
{

    protected string $modelClass = 'Option';

    public function __construct($action)
    {
        parent::__construct($action);
        if (FatApp::getConfig('CONF_WITHOUT_PROD_VARIANTS', FatUtility::VAR_INT, 0)) {
            LibHelper::exitWithError(Labels::getLabel('ERR_INVALID_REQUEST', CommonHelper::getLangId()));
        }
        $this->objPrivilege->canViewOptions();
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
            $this->set("canEdit", $this->objPrivilege->canEditOptions($this->admin_id, true));
        } else {
            $this->objPrivilege->canEditOptions();
        }
    }

    /**
     * setLangTemplateData - This function is use to automate load langform and save it. 
     *
     * @param  array $constructorArgs
     * @return void
     */
    protected function setLangTemplateData(array $constructorArgs = []): void
    {
        $this->checkEditPrivilege();
        $this->setModel($constructorArgs);
        $this->formLangFields = [$this->modelObj::tblFld('name')];
        $this->set('formTitle', Labels::getLabel('LBL_OPTION_SETUP', $this->siteLangId));
    }

    public function index()
    {
        $fields = $this->getFormColumns();
        $frmSearch = $this->getSearchForm($fields);

        $pageData = PageLanguageData::getAttributesByKey('MANAGE_OPTIONS', $this->siteLangId);
        $pageTitle = $pageData['plang_title'] ?? LibHelper::getControllerName(true);

        $this->setModel();
        $actionItemsData = HtmlHelper::getDefaultActionItems($fields, $this->modelObj);
        $actionItemsData['deleteButton'] = true;
        $actionItemsData['formAction'] = 'deleteSelected';
        $actionItemsData['performBulkAction'] = true;

        $this->set('pageData', $pageData);
        $this->set('pageTitle', $pageTitle);
        $this->set('actionItemsData', $actionItemsData);
        $this->set("frmSearch", $frmSearch);
        $this->set('defaultColumns', $this->getDefaultColumns());
        $this->set('keywordPlaceholder', Labels::getLabel('FRM_SEARCH_BY_OPTION_NAME_OR_ADDED_BY', $this->siteLangId));
        $this->getListingData();

        $this->_template->render(true, true, '_partial/listing/index.php');
    }

    public function search()
    {
        $loadPagination = FatApp::getPostedData('loadPagination', FatUtility::VAR_INT, 0);
        $this->getListingData($loadPagination);
        $jsonData = [
            'paginationHtml' => $this->_template->render(false, false, '_partial/listing/listing-foot.php', true)
        ];

        if (!$loadPagination || !FatUtility::isAjaxCall()) {
            $jsonData['listingHtml'] = $this->_template->render(false, false, 'options/search.php', true);
        }
        LibHelper::exitWithSuccess($jsonData, true);
    }

    private function getListingData($loadPagination = 0)
    {
        $this->checkEditPrivilege(true);

        $db = FatApp::getDb();

        $fields = $this->getFormColumns();
        $selectedFlds = FatApp::getPostedData('reportColumns', FatUtility::VAR_STRING, '');
        $selectedFlds = !empty($selectedFlds) ? json_decode($selectedFlds) + $this->getDefaultColumns() : $this->getDefaultColumns();
        $fields = FilterHelper::parseArrayByKeys($fields, $selectedFlds, true);

        $allowedKeysForSorting = $this->excludeKeysForSort(array_keys($fields));
        $sortBy = FatApp::getPostedData('sortBy', FatUtility::VAR_STRING, current($allowedKeysForSorting));
        if (!array_key_exists($sortBy, $fields)) {
            $sortBy = current($allowedKeysForSorting);
        }

        $sortOrder = applicationConstants::getSortOrder(FatApp::getPostedData('sortOrder', FatUtility::VAR_STRING));

        $srchFrm = $this->getSearchForm($fields);

        $postedData = FatApp::getPostedData();
        $post = $srchFrm->getFormDataFromArray($postedData);
        $page = FatApp::getPostedData('page', FatUtility::VAR_INT, 1);
        $page = ($page <= 0) ? 1 : $page;

        $pageSize = applicationConstants::getPageSize(FatApp::getPostedData('pageSize', FatUtility::VAR_INT));

        $srch = Option::getSearchObject($this->siteLangId);
        $srch->joinTable(User::DB_TBL, 'LEFT JOIN', 'u.user_id = option_seller_id', 'u');
        if (isset($post['keyword']) && '' != $post['keyword']) {
            $condition = $srch->addCondition('o.option_identifier', 'like', '%' . $post['keyword'] . '%');
            $condition->attachCondition('ol.option_name', 'like', '%' . $post['keyword'] . '%', 'OR');
            if (strtolower($post['keyword']) == strtolower(Labels::getLabel('LBL_Admin', $this->siteLangId))) {
                $condition->attachCondition('u.user_name', 'is', 'mysql_func_NULL', 'OR', true);
            } else {
                $condition->attachCondition('u.user_name', 'like', '%' . $post['keyword'] . '%', 'OR');
            }
        }

        if ($loadPagination && FatUtility::isAjaxCall()) {
            $this->setRecordCount(clone $srch, $pageSize, $page, $post);
        }

        $srch->doNotCalculateRecords();
        $srch->addMultipleFields(["o.*", "IFNULL( ol.option_name, o.option_identifier ) as option_name", "u.user_name"]);
        $srch->addOrder($sortBy, $sortOrder);
        $srch->setPageNumber($page);
        $srch->setPageSize($pageSize);
        $rs = $srch->getResultSet();

        $records = [];
        if (!$loadPagination) {
            $records = $db->fetchAll($rs);
        }

        $this->set("arrListing", $records);
        $this->set('postedData', $post);
        $this->set('sortBy', $sortBy);
        $this->set('sortOrder', $sortOrder);
        $this->set('fields', $fields);
        $this->set('allowedKeysForSorting', $allowedKeysForSorting);
    }

    public function form()
    {
        $this->objPrivilege->canEditOptions();

        $recordId = FatApp::getPostedData('recordId', FatUtility::VAR_INT, 0);
        $hideListBox = false;
        $frm = $this->getForm($recordId);
        if (0 < $recordId) {
            $data = Option::getAttributesByLangId(CommonHelper::getDefaultFormLangId(), $recordId, ['*', 'IFNULL(option_name,option_identifier) as option_name'], applicationConstants::JOIN_RIGHT);
            if ($data === false) {
                LibHelper::exitWithError($this->str_invalid_request, true);
            }
            $frm->fill($data);
            $hideListBox = (in_array($data['option_type'], Option::ignoreOptionValues()));
        }

        HtmlHelper::addIdentierToFrm($frm->getField($this->modelClass::tblFld('name')), ($data[$this->modelClass::tblFld('identifier')] ?? ''));

        $this->set('recordId', $recordId);
        $this->set('frm', $frm);
        $this->set('hideListBox', $hideListBox);
        $this->set('langId', $this->siteLangId);
        $this->set('formTitle', Labels::getLabel('LBL_OPTION_SETUP', $this->siteLangId));
        $this->set('html', $this->_template->render(false, false, NULL, true));
        $this->_template->render(false, false, 'json-success.php', true, false);
    }

    public function setup()
    {
        $this->objPrivilege->canEditOptions();

        $frm = $this->getForm();
        $post = $frm->getFormDataFromArray(FatApp::getPostedData());

        if (false === $post) {
            LibHelper::exitWithError(current($frm->getValidationErrors()), true);
        }

        $recordId = FatUtility::int($post['option_id']);
        unset($post['option_id']);

        $post['option_identifier'] = $post['option_name'];
        if (0 < $recordId) {
            $sellerId = Option::getAttributesById($recordId, 'option_seller_id');
            if (0 < $sellerId) {
                $post['option_identifier'] = $post['option_name'] . '-' . $sellerId;
            }
        }

        $optionObj = new Option($recordId);
        $optionObj->assignValues($post);
        if (!$optionObj->save()) {
            $msg = $optionObj->getError();
            if (false !== strpos(strtolower($msg), 'duplicate')) {
                $msg = Labels::getLabel('ERR_DUPLICATE_RECORD_NAME', $this->siteLangId);
            }
            LibHelper::exitWithError($msg, true);
        }

        $recordId = $optionObj->getMainTableRecordId();

        $this->setLangData($optionObj, [$optionObj::tblFld('name') => $post[$optionObj::tblFld('name')]]);

        $autoUpdateOtherLangsData = FatApp::getPostedData('auto_update_other_langs_data', FatUtility::VAR_INT, 0);
        if (0 < $autoUpdateOtherLangsData) {
            $updateLangDataobj = new TranslateLangData(Option::DB_TBL_LANG);
            if (false === $updateLangDataobj->updateTranslatedData($recordId, CommonHelper::getDefaultFormLangId())) {
                LibHelper::exitWithError($updateLangDataobj->getError(), true);
            }
        }

        Product::updateMinPrices();
        $this->_template->render(false, false, 'json-success.php');
    }

    private function getForm($recordId = 0)
    {
        $recordId = FatUtility::int($recordId);

        $frm = new Form('frmOptions');
        $frm->addHiddenField('', 'option_type', Option::OPTION_TYPE_SELECT);
        $frm->addHiddenField('', 'option_id', $recordId);

        $frm->addRequiredField(Labels::getLabel('FRM_NAME', $this->siteLangId), Option::DB_TBL_PREFIX . 'name');

        // $yesNoArr = applicationConstants::getYesNoArr($this->siteLangId);
        $frm->addCheckBox(
            Labels::getLabel('FRM_HAVE_SEPARATE_IMAGE', $this->siteLangId),
            'option_is_separate_images',
            applicationConstants::YES,
            array(),
            false,
            applicationConstants::NO
        );

        $frm->addCheckBox(Labels::getLabel('FRM_IS_COLOR', $this->siteLangId), 'option_is_color', applicationConstants::YES, array(), false, applicationConstants::NO);

        $frm->addCheckBox(Labels::getLabel('FRM_DISPLAY_IN_FILTERS', $this->siteLangId), 'option_display_in_filter', applicationConstants::YES, array(), false, applicationConstants::NO);

        $languageArr = Language::getDropDownList(CommonHelper::getDefaultFormLangId());
        $translatorSubscriptionKey = FatApp::getConfig('CONF_TRANSLATOR_SUBSCRIPTION_KEY', FatUtility::VAR_STRING, '');
        if (!empty($translatorSubscriptionKey) && 0 < count($languageArr)) {
            $frm->addCheckBox(Labels::getLabel('FRM_UPDATE_OTHER_LANGUAGES_DATA', $this->siteLangId), 'auto_update_other_langs_data', 1, array(), false, 0);
        }
        return $frm;
    }

    protected function getLangForm($recordId = 0, $langId = 0)
    {
        $this->checkEditPrivilege();
        $langId = 1 > $langId ? $this->siteLangId : $langId;

        $frm = new Form('frmOptionLang');
        $frm->addHiddenField('', Option::DB_TBL_PREFIX . 'id', $recordId);
        $frm->addSelectBox(Labels::getLabel('FRM_LANGUAGE', $langId), 'lang_id', Language::getDropDownList(CommonHelper::getDefaultFormLangId()), $langId, array(), '');
        $frm->addRequiredField(Labels::getLabel('FRM_OPTION_NAME', $langId), Option::DB_TBL_PREFIX . 'name');
        return $frm;
    }

    public function deleteRecord()
    {
        $this->objPrivilege->canEditOptions();

        $recordId = FatApp::getPostedData('recordId', FatUtility::VAR_INT, 0);
        if ($recordId < 1) {
            LibHelper::exitWithError(Labels::getLabel('ERR_INVALID_REQUEST_ID', $this->siteLangId), true);
        }

        $this->markAsDeleted($recordId);
        Product::updateMinPrices();
        $this->set('msg', Labels::getLabel('MSG_RECORD_DELETED_SUCCESSFULLY', $this->siteLangId));
        $this->_template->render(false, false, 'json-success.php');
    }

    public function deleteSelected()
    {
        $this->objPrivilege->canEditOptions();
        $optionIdsArr = FatUtility::int(FatApp::getPostedData('option_ids'));

        if (empty($optionIdsArr)) {
            LibHelper::exitWithError($this->str_invalid_request, true);
        }

        foreach ($optionIdsArr as $recordId) {
            if (1 > $recordId) {
                continue;
            }
            $this->markAsDeleted($recordId);
        }

        Product::updateMinPrices();
        $this->set('msg', Labels::getLabel('MSG_RECORDS_DELETED_SUCCESSFULLY', $this->siteLangId));
        $this->_template->render(false, false, 'json-success.php');
    }

    protected function markAsDeleted($recordId)
    {
        $optionObj = new Option($recordId);
        if (!$optionObj->canRecordMarkDelete($recordId)) {
            LibHelper::exitWithError(Labels::getLabel('ERR_INVALID_REQUEST_ID', $this->siteLangId), true);
        }

        if ($optionObj->isLinkedWithProduct($recordId)) {
            LibHelper::exitWithError(Labels::getLabel('ERR_THIS_OPTION_IS_LINKED_WITH_PRODUCT', $this->siteLangId), true);
        }

        $optionIdentifier = Option::getAttributesById($recordId, Option::tblFld('identifier'));
        $optionObj->assignValues(array(Option::tblFld('identifier') => $optionIdentifier . '-' . $recordId, Option::tblFld('deleted') => 1));
        if (!$optionObj->save()) {
            LibHelper::exitWithError($optionObj->getError(), true);
        }
    }

    public function autoComplete()
    {
        $post = FatApp::getPostedData();
        $this->objPrivilege->canViewOptions();

        $pagesize = 20;
        $page = FatApp::getPostedData('page', FatUtility::VAR_INT, 1);
        if ($page < 2) {
            $page = 1;
        }

        $langId = FatApp::getPostedData('lang_id', FatUtility::VAR_INT, $this->siteLangId);

        $srch = Option::getSearchObject($langId);

        $srch->joinTable(OptionValue::DB_TBL, 'INNER JOIN', OptionValue::DB_TBL_PREFIX . 'option_id = ' . Option::DB_TBL_PREFIX . 'id');
        $srch->addOrder('option_identifier');
        $srch->addMultipleFields(array('option_id as id, COALESCE(option_name, option_identifier) as option_name', 'option_identifier', 'option_is_separate_images', 'option_is_color', 'option_display_in_filter'));

        if (isset($post['keyword']) && '' != $post['keyword']) {
            $cnd = $srch->addCondition('option_name', 'LIKE', '%' . $post['keyword'] . '%');
            $cnd->attachCondition('option_identifier', 'LIKE', '%' . $post['keyword'] . '%', 'OR');
        }

        $disAllowOptions = FatApp::getPostedData('disAllowOptions');

        if (is_array($disAllowOptions)) {
            $srch->addCondition('option_id', 'NOT IN', $disAllowOptions);
        }

        $doNotIncludeImageOption = FatApp::getPostedData('doNotIncludeImageOption', FatUtility::VAR_INT, 0);
        if (0 < $doNotIncludeImageOption) {
            $srch->addCondition('option_is_separate_images', '=', applicationConstants::NO);
        }

        $srch->setPageNumber($page);
        $srch->setPageSize($pagesize);
        $srch->addGroupBy('option_id');

        $options = FatApp::getDb()->fetchAll($srch->getResultSet());

        $results = [];
        foreach ($options as $option) {
            $optionName = $option['option_name'];
            if ($option['option_name']  != $option['option_identifier']) {
                $optionName .= "(" . $option['option_identifier'] . ")";
            }
            $results[] = ['id' => $option['id'], 'text' => $optionName, 'option_is_separate_images' => $option['option_is_separate_images']];
        }

        $json = array(
            'pageCount' => $srch->pages(),
            'results' => $results
        );

        die(FatUtility::convertToJson($json));
    }

    protected function getFormColumns(): array
    {
        $optionsTblHeadingCols = CacheHelper::get('optionsTblHeadingCols' . $this->siteLangId, CONF_DEF_CACHE_TIME, '.txt');
        if ($optionsTblHeadingCols) {
            return json_decode($optionsTblHeadingCols, true);
        }

        $arr = [
            'select_all' => Labels::getLabel('LBL_SELECT_ALL', $this->siteLangId),
            /* 'listSerial' => Labels::getLabel('LBL_SR._NO', $this->siteLangId), */
            'option_name' => Labels::getLabel('LBL_OPTION_NAME', $this->siteLangId),
            /* 'option_is_separate_images' => Labels::getLabel('LBL_HAVE_SEPARATE_IMAGE', $this->siteLangId), */
            'option_display_in_filter' => Labels::getLabel('LBL_DISPLAY_IN_FILTER', $this->siteLangId),
            'option_is_color' => Labels::getLabel('LBL_COLOR_OPTION', $this->siteLangId),
            'user_name' => Labels::getLabel('LBL_ADDED_BY', $this->siteLangId),
            'action' => Labels::getLabel('LBL_ACTION_BUTTONS', $this->siteLangId),
        ];
        CacheHelper::create('optionsTblHeadingCols' . $this->siteLangId, json_encode($arr), CacheHelper::TYPE_LABELS);
        return $arr;
    }

    protected function getDefaultColumns(): array
    {
        return [
            'select_all',
            /*  'listSerial', */
            'option_name',
            'user_name',
            /* 'option_is_separate_images', */
            'option_display_in_filter',
            'option_is_color',
            'action',
        ];
    }

    protected function excludeKeysForSort($fields = []): array
    {
        return array_diff($fields, Common::excludeKeysForSort());
    }
}
