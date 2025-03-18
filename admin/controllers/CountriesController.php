<?php

class CountriesController extends ListingBaseController
{
    protected $pageKey = 'MANAGE_COUNTRIES';

    public function __construct($action)
    {
        parent::__construct($action);
        $this->objPrivilege->canViewCountries();
    }

    /**
     * setLangTemplateData - This function is use to automate load langform and save it. 
     *
     * @param  array $constructorArgs
     * @return void
     */
    protected function setLangTemplateData(array $constructorArgs = []): void
    {
        $this->objPrivilege->canEditCountries();
        $this->modelObj = (new ReflectionClass('Countries'))->newInstanceArgs($constructorArgs);
        $this->formLangFields = [$this->modelObj::tblFld('name')];
        $this->set('formTitle', Labels::getLabel('LBL_COUNTRY_SETUP', $this->siteLangId));
    }

    public function index()
    {
        $fields = $this->getFormColumns();
        $frmSearch = $this->getSearchForm($fields);

        $pageData = PageLanguageData::getAttributesByKey('MANAGE_COUNTRIES', $this->siteLangId);
        $pageTitle = $pageData['plang_title'] ?? LibHelper::getControllerName(true);

        $actionItemsData = HtmlHelper::getDefaultActionItems($fields);
        $actionItemsData['statusButtons'] = true;
        $actionItemsData['performBulkAction'] = true;

        $this->set('pageData', $pageData);
        $this->set('pageTitle', $pageTitle);
        $this->set('actionItemsData', $actionItemsData);
        $this->set("frmSearch", $frmSearch);
        $this->set('defaultColumns', $this->getDefaultColumns());
        $this->set('canEdit', $this->objPrivilege->canEditZones($this->admin_id, true));
        $this->set('keywordPlaceholder', Labels::getLabel('FRM_SEARCH_BY_COUNTRY_NAME_OR_CODE', $this->siteLangId));
        $this->getListingData();
        $this->_template->render(true, true, '_partial/listing/index.php');
    }

    public function search()
    {
        $this->getListingData();
        $jsonData = [
            'listingHtml' => $this->_template->render(false, false, 'countries/search.php', true),
            'paginationHtml' => $this->_template->render(false, false, '_partial/listing/listing-foot.php', true)
        ];
        LibHelper::exitWithSuccess($jsonData, true);
    }

    private function getListingData()
    {
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
        $post = $srchFrm->getFormDataFromArray(FatApp::getPostedData());

        $page = FatApp::getPostedData('page', FatUtility::VAR_INT, 1);
        $page = ($page <= 0) ? 1 : $page;

        $pageSize = applicationConstants::getPageSize(FatApp::getPostedData('pageSize', FatUtility::VAR_INT));

        $srch = Countries::getSearchObject(false, $this->siteLangId);
        $srch->addMultipleFields(['c.* , COALESCE(c_l.country_name, c.country_code) as country_name']);

        if (isset($post['keyword']) && '' != $post['keyword']) {
            $condition = $srch->addCondition('c.country_code', 'like', '%' . $post['keyword'] . '%');
            $condition->attachCondition('c_l.country_name', 'like', '%' . $post['keyword'] . '%', 'OR');
        }

        if (!array_key_exists($sortOrder, applicationConstants::sortOrder($this->siteLangId))) {
            $sortOrder = applicationConstants::SORT_ASC;
        }

        $srch->addOrder($sortBy, $sortOrder);

        $srch->setPageNumber($page);
        $srch->setPageSize($pageSize);
        $srch->removeFld(['select_all', 'action']);
        $rs = $srch->getResultSet();
        $arrListing = $db->fetchAll($rs);

        $this->set("arrListing", $arrListing);
        $this->set('pageCount', $srch->pages());
        $this->set('recordCount', $srch->recordCount());
        $this->set('page', $page);
        $this->set('pageSize', $pageSize);
        $this->set('postedData', $post);
        $this->set('activeInactiveArr', applicationConstants::getActiveInactiveArr($this->siteLangId));

        $this->set('sortBy', $sortBy);
        $this->set('sortOrder', $sortOrder);
        $this->set('fields', $fields);
        $this->set('allowedKeysForSorting', $allowedKeysForSorting);
        $this->set('canEdit', $this->objPrivilege->canEditCountries($this->admin_id, true));
    }

    public function form()
    {
        $this->objPrivilege->canEditCountries();

        $recordId = FatApp::getPostedData('recordId', FatUtility::VAR_INT, 0);

        $frm = $this->getForm();

        if (0 < $recordId) {
            $data = Countries::getAttributesByLangId(CommonHelper::getDefaultFormLangId(), $recordId, ['*','IFNULL(country_name,country_code) as country_name'], applicationConstants::JOIN_RIGHT);
            if ($data === false) {
                LibHelper::exitWithError($this->str_invalid_request, true);
            }
            $frm->fill($data);
        }

        $this->set('recordId', $recordId);
        $this->set('frm', $frm);
        $this->set('formTitle', Labels::getLabel('LBL_COUNTRY_SETUP', $this->siteLangId));
        $this->set('html', $this->_template->render(false, false, NULL, true));
        $this->_template->render(false, false, 'json-success.php', true, false);
    }

    public function setup()
    {
        $this->objPrivilege->canEditCountries();

        $frm = $this->getForm();
        $post = $frm->getFormDataFromArray(FatApp::getPostedData());
        $countryId = FatApp::getPostedData('country_id', FatUtility::VAR_INT, 0);

        if (false === $post) {
            LibHelper::exitWithError(current($frm->getValidationErrors()), true);
        }

        $checkCountryId = Countries::getCountryByCode($post['country_code'], 'country_id');
        if (!empty($checkCountryId) && $checkCountryId != $countryId) {
            LibHelper::exitWithError(Labels::getLabel('ERR_COUNTRY_CODE_ALREADY_EXISTS'), true);
        }

        $recordId = $post['country_id'];
        unset($post['country_id']);

        $recordObj = new Countries($recordId);
        $recordObj->assignValues($post);

        if (!$recordObj->save()) {
            LibHelper::exitWithError($recordObj->getError(), true);
        }

        if(applicationConstants::INACTIVE == $post['country_active']){
            if(!Shop::updateShopsDisplayStatus($countryId)){
                LibHelper::exitWithError(Labels::getLabel('ERR_UNABLE_TO_UPDATE_DISPLAY_STATUS_OF_SHOP'), true);
            }
        }

        $this->setLangData($recordObj, [$recordObj::tblFld('name') => $post[$recordObj::tblFld('name')]]);

        Product::updateMinPrices(0, 0, 0, $recordId);
        $this->_template->render(false, false, 'json-success.php');
    }

    private function getForm()
    {
        $frm = new Form('frmCountry');
        $frm->addHiddenField('', 'country_id');

        $zoneArr = Zone::getAllZones($this->siteLangId, true);
        $fld = $frm->addSelectBox(Labels::getLabel('FRM_ZONE', $this->siteLangId), 'country_zone_id', $zoneArr, '', [], Labels::getLabel('FRM_SELECT', $this->siteLangId));
        $fld->requirements()->setRequired();

        $frm->addRequiredField(Labels::getLabel('FRM_COUNTRY_NAME', $this->siteLangId), 'country_name');

        $currencyArr = Currency::getCurrencyNameWithCode($this->siteLangId);
        $currencyId = FatApp::getConfig('CONF_CURRENCY', FatUtility::VAR_INT, 1);
        $currencyData = Currency::getAttributesById($currencyId, array('currency_code'));
        $defaultCurrentySelect = Labels::getLabel('FRM_DEFAULT', $this->siteLangId) . '(' . $currencyData['currency_code'] . ')';
        $frm->addSelectBox(Labels::getLabel('FRM_CURRENCY', $this->siteLangId), 'country_currency_id', $currencyArr, '', [], $defaultCurrentySelect);

        $frm->addRequiredField(Labels::getLabel('FRM_COUNTRY_CODE', $this->siteLangId), 'country_code');
        $frm->addRequiredField(Labels::getLabel('FRM_COUNTRY_ALPHA3_CODE', $this->siteLangId), 'country_code_alpha3');

        $languageArr = Language::getDropDownList();
        if (1 < count($languageArr)) {
            $frm->addSelectBox(Labels::getLabel('FRM_LANGUAGE', $this->siteLangId), 'country_language_id', $languageArr, '', array(), '');
        } else {
            $frm->addHiddenField('', 'country_language_id', FatApp::getConfig('conf_default_site_lang', FatUtility::VAR_INT, 1));
        }

        $frm->addCheckBox(Labels::getLabel('FRM_STATUS', $this->siteLangId), 'country_active', applicationConstants::ACTIVE, [], true, applicationConstants::INACTIVE);

        $translatorSubscriptionKey = FatApp::getConfig('CONF_TRANSLATOR_SUBSCRIPTION_KEY', FatUtility::VAR_STRING, '');
        if (!empty($translatorSubscriptionKey) && 1 < count($languageArr)) {
            $frm->addCheckBox(Labels::getLabel('FRM_UPDATE_OTHER_LANGUAGES_DATA', $this->siteLangId), 'auto_update_other_langs_data', 1, array(), false, 0);
        }

        return $frm;
    }

    protected function getLangForm($recordId = 0, $langId = 0)
    {
        $langId = 1 > $langId ? $this->siteLangId : $langId;
        $frm = new Form('frmCountryLang');
        $frm->addHiddenField('', 'country_id', $recordId);
        $frm->addSelectBox(Labels::getLabel('FRM_LANGUAGE', $langId), 'lang_id', Language::getDropDownList(CommonHelper::getDefaultFormLangId()), $langId, array(), '');
        $frm->addRequiredField(Labels::getLabel('FRM_COUNTRY_NAME', $langId), 'country_name');
        return $frm;
    }

    public function updateStatus()
    {
        $this->objPrivilege->canEditCountries();

        $recordId = FatApp::getPostedData('recordId', FatUtility::VAR_INT, 0);
        if (0 >= $recordId) {
            LibHelper::exitWithError($this->str_invalid_request, true);
        }

        $status = FatApp::getPostedData('status', FatUtility::VAR_INT, 0);
        if (!in_array($status, [applicationConstants::ACTIVE, applicationConstants::INACTIVE])) {
            LibHelper::exitWithError($this->str_invalid_request, true);
        }

        $this->changeStatus($recordId, $status);
        Product::updateMinPrices(0, 0, 0, $recordId);
        LibHelper::dieJsonSuccess(['msg' => Labels::getLabel('LBL_STATUS_UPDATED', $this->siteLangId)]);
    }

    public function toggleBulkStatuses()
    {
        $this->objPrivilege->canEditCountries();

        $status = FatApp::getPostedData('status', FatUtility::VAR_INT, -1);
        $recordIdsArr = FatUtility::int(FatApp::getPostedData('country_ids'));
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
        LibHelper::dieJsonSuccess(['msg' => Labels::getLabel('LBL_STATUS_UPDATED', $this->siteLangId)]);
    }

    public function autoComplete()
    {
        $page = FatApp::getPostedData('page', FatUtility::VAR_INT, 1);
        if ($page < 2) {
            $page = 1;
        }
        $pagesize = 20;
        $langId = FatApp::getPostedData('langId', FatUtility::VAR_INT, $this->siteLangId);
        $keyword = FatApp::getPostedData('keyword', FatUtility::VAR_STRING, '');
        $srch = Countries::getSearchObject(true, $langId);
        $srch->addMultipleFields(
            array(
                'country_id',
                'COALESCE(country_name, country_code) as country_name',
            )
        );

        if (!empty($keyword)) {
            $cond = $srch->addCondition('country_name', 'LIKE', '%' . $keyword . '%');
            $cond->attachCondition('country_code', 'LIKE', '%' . $keyword . '%', 'OR');
        }

        $srch->setPageNumber($page);
        $srch->setPageSize($pagesize);
        $records = FatApp::getDb()->fetchAll($srch->getResultSet(), 'country_id');
        $json = array(
            'pageCount' => $srch->pages(),
            'results' => []
        );
        foreach ($records as $key => $record) {
            $json['results'][] = array(
                'id' => $key,
                'text' => strip_tags(html_entity_decode($record['country_name'], ENT_QUOTES, 'UTF-8'))
            );
        }
        die(FatUtility::convertToJson($json));
    }

    protected function changeStatus($recordId, $status)
    {
        if (1 > $recordId || -1 == $status) {
            LibHelper::exitWithError($this->str_invalid_request, true);
        }

        $obj = new Countries($recordId);
        if (!$obj->changeStatus($status)) {
            LibHelper::exitWithError($obj->getError(), true);
        }
        
        if(applicationConstants::INACTIVE == $status){
            if(!Shop::updateShopsDisplayStatus($recordId)){
                LibHelper::exitWithError(Labels::getLabel('ERR_UNABLE_TO_UPDATE_DISPLAY_STATUS_OF_SHOP'), true);
            }
        }
    }

    protected function getFormColumns(): array
    {
        $countriesTblHeadingCols = CacheHelper::get('countriesTblHeadingCols' . $this->siteLangId, CONF_DEF_CACHE_TIME, '.txt');
        if ($countriesTblHeadingCols) {
            return json_decode($countriesTblHeadingCols, true);
        }

        $arr = [
            'select_all' => Labels::getLabel('LBL_SELECT_ALL', $this->siteLangId),
            'listSerial' => Labels::getLabel('LBL_SR._NO', $this->siteLangId),
            'country_code' => Labels::getLabel('LBL_COUNTRY_CODE', $this->siteLangId),
            'country_code_alpha3' => Labels::getLabel('LBL_COUNTRY_ALPHA3_CODE', $this->siteLangId),
            'country_name' => Labels::getLabel('LBL_COUNTRY_NAME', $this->siteLangId),
            'country_active' => Labels::getLabel('LBL_STATUS', $this->siteLangId),
            'action' => Labels::getLabel('LBL_ACTION_BUTTONS', $this->siteLangId),
        ];
        CacheHelper::create('countriesTblHeadingCols' . $this->siteLangId, json_encode($arr), CacheHelper::TYPE_LABELS);
        return $arr;
    }

    protected function getDefaultColumns(): array
    {
        return ['select_all', 'listSerial', 'country_name', 'country_code', 'country_code_alpha3',  'country_active', 'action'];
    }

    protected function excludeKeysForSort($fields = []): array
    {
        return array_diff($fields, Common::excludeKeysForSort());
    }

    public function getBreadcrumbNodes($action)
    {
        switch ($action) {
            case 'index':
                $pageData = PageLanguageData::getAttributesByKey('MANAGE_COUNTRIES', $this->siteLangId);
                $pageTitle = $pageData['plang_title'] ?? LibHelper::getControllerName(true);
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
