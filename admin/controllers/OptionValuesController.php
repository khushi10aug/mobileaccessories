<?php
class OptionValuesController extends ListingBaseController
{
    protected string $modelClass = 'OptionValue';
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
        $this->set('formTitle', Labels::getLabel('LBL_OPTION_VALUE_SETUP', $this->siteLangId));
    }

    public function index()
    {
        Message::addErrorMessage(Labels::getLabel('ERR_PLEASE_SELECT_OPTION_FIRST', $this->siteLangId));
        FatApp::redirectUser(UrlHelper::generateUrl('Options'));
    }


    public function list(int $optionId)
    {
        $optionData =  Option::getAttributesByLangId($this->siteLangId, $optionId, ['option_name', 'option_identifier'], applicationConstants::JOIN_RIGHT);

        if ($optionData === false) {
            Message::addErrorMessage($this->str_invalid_request_id);
            FatApp::redirectUser(UrlHelper::generateUrl('Options'));
        }

        $fields = $this->getFormColumns();
        $frmSearch = $this->getSearchForm($fields);
        $frmSearch->fill(['option_id' => $optionId]);

        $pageData = PageLanguageData::getAttributesByKey('MANAGE_OPTION_VALUES', $this->siteLangId);
        $pageTitle = !empty($optionData['option_name']) ? $optionData['option_name'] : $optionData['option_identifier'];
        $str = Labels::getLabel('LBL_OPTION_VALUES_FOR_{OPTION-NAME}', $this->siteLangId);
        $pageTitle = CommonHelper::replaceStringData($str, ['{OPTION-NAME}' => $pageTitle]);

        $this->setModel();
        $actionItemsData = HtmlHelper::getDefaultActionItems($fields, $this->modelObj);
        $actionItemsData['deleteButton'] = true;
        $actionItemsData['formAction'] = 'deleteSelected';
        $actionItemsData['performBulkAction'] = true;

        $actionItemsData['newRecordBtnAttrs'] = [
            'attr' => [
                'onclick' => 'optionValueForm(' . $optionId . ')',
            ]
        ];
        $actionItemsData['bulkActionFormHiddenFields'] = ['option_id' => $optionId];

        $this->set('pageData', $pageData);
        $this->set("frmSearch", $frmSearch);
        $this->set('pageTitle', $pageTitle);
        $this->set('actionItemsData', $actionItemsData);
        $this->set('defaultColumns', $this->getDefaultColumns());
        $this->set('keywordPlaceholder', Labels::getLabel('FRM_SEARCH_BY_OPTION_VALUE_NAME', $this->siteLangId));
        $this->getListingData($optionId);
        $this->_template->addJs(['js/jscolor.js', 'option-values/page-js/index.js']);
        $this->_template->render(true, true, '_partial/listing/index.php');
    }

    public function search()
    {
        $optionId = FatApp::getPostedData('option_id', FatUtility::VAR_INT, 0);
        $this->getListingData($optionId);

        $jsonData = [
            'listingHtml' => $this->_template->render(false, false, 'option-values/search.php', true),
            'paginationHtml' => $this->_template->render(false, false, '_partial/listing/listing-foot.php', true)
        ];
        LibHelper::exitWithSuccess($jsonData, true);
    }

    private function getListingData(int $optionId)
    {
        $this->checkEditPrivilege(true);
        $optionId = FatApp::getPostedData('option_id', FatUtility::VAR_INT, $optionId);

        $db = FatApp::getDb();

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

        $postedData = FatApp::getPostedData();
        $post = $srchFrm->getFormDataFromArray($postedData);

        $post['option_id'] = $optionId;
        $srch = OptionValue::getSearchObject($this->siteLangId, false);
        $srch->addMultipleFields(['ov.*', 'COALESCE(ov_l.optionvalue_name, ov.optionvalue_identifier) as optionvalue_name']);
        $srch->addCondition('ov.optionvalue_option_id', '=', $optionId);

        if (isset($post['keyword']) && '' != $post['keyword']) {
            $condition = $srch->addCondition('ov.optionvalue_identifier', 'like', '%' . $post['keyword'] . '%');
            $condition->attachCondition('ov_l.optionvalue_name', 'like', '%' . $post['keyword'] . '%', 'OR');
        }

        $srch->addOrder($sortBy, $sortOrder);

        $srch->doNotLimitRecords();
        $arrListing = $db->fetchAll($srch->getResultSet());

        $this->set("arrListing", $arrListing);
        $this->set('pageCount', $srch->pages());
        $this->set('recordCount', $srch->recordCount());
        $paginationArr = empty($postedData) ? $post : $postedData;
        $this->set('postedData', $paginationArr);

        $this->set('sortBy', $sortBy);
        $this->set('sortOrder', $sortOrder);
        $this->set('fields', $fields);
        $this->set('allowedKeysForSorting', $allowedKeysForSorting);
        $this->set('page', 1);
        $this->set('hidePaginationHtml', true);
    }

    public function getSearchForm($fields = [])
    {
        $fields = $this->getFormColumns();

        $frm = new Form('frmRecordSearch');
        $frm->addHiddenField('', 'page');
        $frm->addHiddenField('', 'option_id');
        $fld = $frm->addTextBox(Labels::getLabel('FRM_KEYWORD', $this->siteLangId), 'keyword');
        $fld->overrideFldType('search');

        if (!empty($fields)) {
            $this->addSortingElements($frm, 'optionvalue_display_order');
        }

        HtmlHelper::addSearchButton($frm);
        return $frm;
    }

    public function form()
    {
        $this->objPrivilege->canEditOptions();

        $optionId = FatApp::getPostedData('option_id', FatUtility::VAR_INT, 0);
        $recordId = FatApp::getPostedData('optionvalue_id', FatUtility::VAR_INT, 0);

        if ($optionId <= 0) {
            LibHelper::exitWithError($this->str_invalid_request_id, true);
        }

        $option = new Option();
        if (!$row = $option->getOption($optionId)) {
            LibHelper::exitWithError(Labels::getLabel('ERR_INVALID_ACCESS', $this->siteLangId), true);
        }
        $this->set('formTitle', Labels::getLabel('LBL_OPTION_VALUE_SETUP', $this->siteLangId));

        $frm = $this->getForm($optionId, $recordId);

        if (0 < $recordId) {
            $data = OptionValue::getAttributesByLangId(CommonHelper::getDefaultFormLangId(), $recordId, array('m.*', 'IFNULL(optionvalue_name,optionvalue_identifier) as optionvalue_name'), applicationConstants::JOIN_RIGHT);
            if ($data === false) {
                LibHelper::exitWithError($this->str_invalid_request, true);
            }
            $frm->fill($data);
        }

        HtmlHelper::addIdentierToFrm($frm->getField($this->modelClass::tblFld('name')), ($data[$this->modelClass::tblFld('identifier')] ?? ''));

        $this->set('frm', $frm);
        $this->set('recordId', $recordId);
        $this->set('html', $this->_template->render(false, false, NULL, true));
        $this->_template->render(false, false, 'json-success.php', true, false);
    }

    public function setup()
    {
        $this->objPrivilege->canEditOptions();

        $optionId = FatApp::getPostedData('optionvalue_option_id', FatUtility::VAR_INT, 0);
        if (1 > $optionId) {
            LibHelper::exitWithError($this->str_invalid_request_id, true);
        }

        $frm = $this->getForm($optionId);
        $post = $frm->getFormDataFromArray(FatApp::getPostedData());
        if (false === $post) {
            LibHelper::exitWithError(current($frm->getValidationErrors()), true);
        }

        $recordId = FatUtility::int($post['optionvalue_id']);
        unset($post['optionvalue_id']);

        if (0 < $recordId) {
            $optionValueObj = new OptionValue();
            $data = $optionValueObj->getAttributesByIdAndOptionId($optionId, $recordId, array('optionvalue_id'));

            if ($data === false) {
                LibHelper::exitWithError($this->str_invalid_request_id, true);
            }
        }

        $post['optionvalue_name'] = trim($post['optionvalue_name']);
        $post['optionvalue_identifier'] = $post['optionvalue_name'];
        $optionValueObj = new OptionValue($recordId);
        $optionValueObj->assignValues($post);

        if (!$optionValueObj->save()) {
            $msg = $optionValueObj->getError();
            if (false !== strpos(strtolower($msg), 'duplicate')) {
                $msg = Labels::getLabel('ERR_DUPLICATE_RECORD_NAME', $this->siteLangId);
            }
            LibHelper::exitWithError($msg, true);
        }

        $recordId = $optionValueObj->getMainTableRecordId();

        $this->setLangData($optionValueObj, [$optionValueObj::tblFld('name') => $post[$optionValueObj::tblFld('name')]]);

        $autoUpdateOtherLangsData = FatApp::getPostedData('auto_update_other_langs_data', FatUtility::VAR_INT, 0);
        if (0 < $autoUpdateOtherLangsData) {
            $updateLangDataobj = new TranslateLangData(OptionValue::DB_TBL_LANG);
            if (false === $updateLangDataobj->updateTranslatedData($recordId, CommonHelper::getDefaultFormLangId())) {
                LibHelper::exitWithError($updateLangDataobj->getError(), true);
            }
        }

        Product::updateMinPrices();
        $this->_template->render(false, false, 'json-success.php');
    }

    public function autoComplete()
    {
        $post = FatApp::getPostedData();
        $this->objPrivilege->canViewOptions();

        $post = FatApp::getPostedData();

        $langId = FatApp::getPostedData('langId', FatUtility::VAR_INT, $this->siteLangId);
        $optionId = FatApp::getPostedData('optionId', FatUtility::VAR_INT, 0);

        $srch = OptionValue::getSearchObject($langId, true);
        $srch->addCondition('ov.optionvalue_option_id', '=', $optionId);
        $srch->addMultipleFields(array('optionvalue_id as id, TRIM(COALESCE(optionvalue_name, optionvalue_identifier)) as text'));

        if (isset($post['keyword']) && '' != $post['keyword']) {
            $cnd = $srch->addCondition('optionvalue_identifier', 'LIKE', '%' . $post['keyword'] . '%');
            $cnd->attachCondition('optionvalue_name', 'LIKE', '%' . $post['keyword'] . '%', 'OR');
        }

        if (FatApp::getPostedData('doNotLimitRecords', FatUtility::VAR_INT, 1)) {
            $pagesize = 20;
            $page = FatApp::getPostedData('page', FatUtility::VAR_INT, 1);
            if ($page < 2) {
                $page = 1;
            }
            $srch->setPageNumber($page);
            $srch->setPageSize($pagesize);
        } else {

            $srch->doNotLimitRecords();
        }

        $options = FatApp::getDb()->fetchAll($srch->getResultSet());

        $json = array(
            'pageCount' => $srch->pages(),
            'results' => $options
        );

        die(FatUtility::convertToJson($json));
    }

    private function getForm($optionId, $recordId = 0)
    {
        $this->objPrivilege->canEditOptions();
        $optionId = FatUtility::int($optionId);
        $recordId = FatUtility::int($recordId);

        $frm = new Form('frmOptionValues', array('id' => 'frmOptionValues'));
        $frm->addHiddenField('', 'optionvalue_id', $recordId);
        $frm->addHiddenField('', 'optionvalue_option_id', $optionId);
        $frm->addRequiredField(Labels::getLabel('FRM_NAME', $this->siteLangId), 'optionvalue_name');
        $fld = $frm->addRequiredField(Labels::getLabel('FRM_DISPLAY_ORDER', $this->siteLangId), 'optionvalue_display_order');
        $fld->requirements()->setInt();
        $fld->requirements()->setPositive();
        $fld->requirements()->setRange(1, 9999999999);

        $optionRow = Option::getAttributesById($optionId);
        if ($optionRow && $optionRow['option_is_color']) {
            $frm->addTextBox(Labels::getLabel('FRM_OPTION_VALUE_COLOR', $this->siteLangId), 'optionvalue_color_code', '', ['class' => 'jscolor']);
        }

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

        $frm = new Form('frmOptionValueLang');
        $frm->addHiddenField('', OptionValue::DB_TBL_PREFIX . 'option_id');
        $frm->addHiddenField('', OptionValue::DB_TBL_PREFIX . 'id', $recordId);
        $frm->addSelectBox(Labels::getLabel('FRM_LANGUAGE', $langId), 'lang_id', Language::getDropDownList(CommonHelper::getDefaultFormLangId()), $langId, array(), '');
        $frm->addRequiredField(Labels::getLabel('FRM_NAME', $langId), OptionValue::DB_TBL_PREFIX . 'name');
        return $frm;
    }

    public function deleteRecord()
    {
        $this->objPrivilege->canEditOptions();

        $recordId = FatApp::getPostedData('recordId', FatUtility::VAR_INT, 0);

        $this->markAsDeleted($recordId);

        Product::updateMinPrices();
        $this->set('msg', Labels::getLabel('MSG_RECORD_DELETED', $this->siteLangId));

        $this->_template->render(false, false, 'json-success.php');
    }

    public function deleteSelected()
    {
        $this->objPrivilege->canEditOptions();
        $recordIdsArr = FatUtility::int(FatApp::getPostedData('record_ids'));
        if (empty($recordIdsArr)) {
            LibHelper::exitWithError($this->str_invalid_request, true);
        }

        foreach ($recordIdsArr as $recordId) {
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
        $optionId = FatApp::getPostedData('option_id', FatUtility::VAR_INT, 0);

        if (1 > $recordId || 1 > $optionId) {
            LibHelper::exitWithError($this->str_invalid_request_id, true);
        }

        $optionValueObj = new OptionValue($recordId);
        if (!$optionValueObj->canEditRecord($optionId)) {
            LibHelper::exitWithError($this->str_invalid_request_id, true);
        }

        if ($optionValueObj->isLinkedWithInventory($recordId)) {
            LibHelper::exitWithError(Labels::getLabel('ERR_THIS_OPTION_VALUE_IS_LINKED_WITH_INVENTORY', $this->siteLangId), true);
        }

        if (!$optionValueObj->deleteRecord()) {
            LibHelper::exitWithError($optionValueObj->getError(), true);
        }
    }

    public function updateOrder()
    {
        $this->objPrivilege->canEditOrderStatus();
        $post = FatApp::getPostedData();
        if (!empty($post)) {
            $obj = new OptionValue();
            if (!$obj->updateOrder($post['optionvalues'])) {
                LibHelper::exitWithError($obj->getError(), true);
            }

            $this->set('msg', Labels::getLabel('MSG_ORDER_UPDATED_SUCCESSFULLY', $this->siteLangId));
            $this->_template->render(false, false, 'json-success.php');
        }
    }

    protected function getFormColumns(): array
    {
        $optionsTblHeadingCols = CacheHelper::get('optionsValueTblHeadingCols' . $this->siteLangId, CONF_DEF_CACHE_TIME, '.txt');
        if ($optionsTblHeadingCols) {
            return json_decode($optionsTblHeadingCols, true);
        }

        $arr = [
            'dragdrop' => '',
            'select_all' => Labels::getLabel('LBL_SELECT_ALL', $this->siteLangId),
            'optionvalue_display_order' => Labels::getLabel('LBL_DISPLAY_ORDER', $this->siteLangId),
            'optionvalue_name' => Labels::getLabel('LBL_OPTION_VALUE_NAME', $this->siteLangId),
            'action' => Labels::getLabel('LBL_ACTION_BUTTONS', $this->siteLangId),
        ];
        CacheHelper::create('optionsValueTblHeadingCols' . $this->siteLangId, json_encode($arr), CacheHelper::TYPE_LABELS);
        return $arr;
    }

    protected function getDefaultColumns(): array
    {
        return [
            'dragdrop',
            'select_all',
            'optionvalue_display_order',
            'optionvalue_name',
            'action',
        ];
    }

    protected function excludeKeysForSort($fields = []): array
    {
        return array_diff($fields, ['dragdrop'], Common::excludeKeysForSort());
    }

    public function getBreadcrumbNodes($action)
    {
        switch ($action) {
            case 'list':
                $pageData = PageLanguageData::getAttributesByKey('MANAGE_OPTIONS', $this->siteLangId);
                $pageTitle = $pageData['plang_title'] ?? Labels::getLabel('LBL_OPTIONS', $this->siteLangId);

                $url = FatApp::getQueryStringData('url');
                $urlParts = explode('/', $url);
                $title = Labels::getLabel('LBL_SUBSCRIPTION_PACKAGE_PLANS', $this->siteLangId);
                if (isset($urlParts[2])) {
                    $attr = ['COALESCE(option_name, option_identifier) as option_name'];
                    $data =  Option::getAttributesByLangId($this->siteLangId, $urlParts[2], $attr, applicationConstants::JOIN_RIGHT);
                    $title = $data['option_name'];
                }

                $this->nodes = [
                    ['title' => $pageTitle, 'href' => UrlHelper::generateUrl('Options')],
                    ['title' => $title]
                ];
                break;
            default:
                parent::getBreadcrumbNodes($action);
                break;
        }
        return $this->nodes;
    }
}
