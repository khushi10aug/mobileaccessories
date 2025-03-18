<?php

class CurrencyManagementController extends ListingBaseController
{
    protected $modelClass = 'Currency';
    protected $pageKey = 'MANAGE_CURRENCIES';

    public function __construct($action)
    {
        parent::__construct($action);
        $this->objPrivilege->canViewCurrencyManagement();
    }

    /**
     * setLangTemplateData - This function is use to automate load langform and save it. 
     *
     * @param  array $constructorArgs
     * @return void
     */
    protected function setLangTemplateData(array $constructorArgs = []): void
    {
        $this->objPrivilege->canEditCurrencyManagement();
        $this->modelObj = (new ReflectionClass('Currency'))->newInstanceArgs($constructorArgs);
        $this->formLangFields = [$this->modelObj::tblFld('name')];
        $this->set('formTitle', Labels::getLabel('LBL_CURRENCY_SETUP', $this->siteLangId));
    }

    public function index()
    {
        $fields = $this->getFormColumns();
        $frmSearch = $this->getSearchForm($fields);

        $pageData = PageLanguageData::getAttributesByKey($this->pageKey, $this->siteLangId);
        $pageTitle = $pageData['plang_title'] ?? LibHelper::getControllerName(true);

        $currency = new Currency();
        $currencyConverter = $currency->getCurrencyConverterApi();

        $actionItemsData = HtmlHelper::getDefaultActionItems($fields);
        $actionItemsData['statusButtons'] = true;
        $actionItemsData['performBulkAction'] = true;

        if (false !== $currencyConverter) {
            $actionItemsData['otherButtons'] = [
                [
                    'attr' => [
                        'href' => 'javascript:void(0)',
                        'class' => 'btn btn-outline-brand btn-icon',
                        'onclick' => "updateCurrencyRates('" . $currencyConverter . "')",
                        'title' => Labels::getLabel('LBL_SYNC_CURRENCY_VALUE', $this->siteLangId)
                    ],
                    'label' => '<svg class="svg" width="18" height="18">
                                    <use
                                        xlink:href="' . CONF_WEBROOT_URL . 'images/retina/sprite.yokart.svg' . AttachedFile::setTimeParam(RELEASE_DATE) . '#sync-currency">
                                    </use>
                                </svg><span>' . Labels::getLabel('BTN_SYNC', $this->siteLangId) . '</span>',
                ]
            ];
        }

        $this->set('pageData', $pageData);
        $this->set('pageTitle', $pageTitle);
        $this->set('actionItemsData', $actionItemsData);
        $this->set("frmSearch", $frmSearch);
        $this->set('defaultColumns', $this->getDefaultColumns());
        $this->set('keywordPlaceholder', Labels::getLabel('FRM_SEARCH_BY_CURRENCY', $this->siteLangId));
        $this->getListingData();

        $this->_template->addJs(['js/jquery.tablednd.js', 'currency-management/page-js/index.js']);
        $this->_template->render(true, true, '_partial/listing/index.php');
    }

    public function search()
    {
        $this->getListingData();
        $jsonData = [
            'listingHtml' => $this->_template->render(false, false, 'currency-management/search.php', true),
            'paginationHtml' => $this->_template->render(false, false, '_partial/listing/listing-foot.php', true)
        ];
        LibHelper::exitWithSuccess($jsonData, true);
    }

    private function getListingData()
    {
        $postedData = FatApp::getPostedData();

        $fields = $this->getFormColumns();
        $selectedFlds = FatApp::getPostedData('reportColumns', FatUtility::VAR_STRING, '');
        $selectedFlds = !empty($selectedFlds) ? json_decode($selectedFlds) +  $this->getDefaultColumns() : $this->getDefaultColumns();

        $fields =  FilterHelper::parseArrayByKeys($fields, $selectedFlds, true);
        $allowedKeysForSorting = $this->excludeKeysForSort(array_keys($fields));
        $sortBy = FatApp::getPostedData('sortBy', FatUtility::VAR_STRING, 'currency_display_order');
        if (!array_key_exists($sortBy, $fields)) {
            $sortBy = 'currency_display_order';
        }

        $sortBy = 'currency_code' == $sortBy ? 'currency_name' : $sortBy;

        $sortOrder = applicationConstants::getSortOrder(FatApp::getPostedData('sortOrder', FatUtility::VAR_STRING));

        $searchForm = $this->getSearchForm($fields);

        $post = $searchForm->getFormDataFromArray($postedData);

        $srch = Currency::getSearchObject($this->siteLangId, false);
        $srch->doNotLimitRecords();

        if (isset($post['keyword']) && '' != $post['keyword']) {
            $srch->addCondition('curr_l.currency_name', 'like', '%' . $post['keyword'] . '%');
        }

        $srch->addMultipleFields(['curr.*', 'curr_l.*']);
        $srch->addOrder($sortBy, $sortOrder);

        $records = FatApp::getDb()->fetchAll($srch->getResultSet());

        $defaultCurrencyId = FatApp::getConfig("CONF_CURRENCY", FatUtility::VAR_INT, 1);
        $this->set('activeInactiveArr', applicationConstants::getActiveInactiveArr($this->siteLangId));
        $this->set("defaultCurrencyId", $defaultCurrencyId);
        $this->set("arrListing", $records);

        $paginationArr = empty($postedData) ? $post : $postedData;
        $this->set('postedData', $paginationArr);
        $this->set('sortBy', $sortBy);
        $this->set('sortOrder', $sortOrder);
        $this->set('fields', $fields);
        $this->set('allowedKeysForSorting', $allowedKeysForSorting);
        $this->set('pageCount', $srch->pages());
        $this->set('recordCount', $srch->recordCount());
        $this->set('page', 1);
        $this->set('hidePaginationHtml', true);
        $this->set('canEdit', $this->objPrivilege->canEditCurrencyManagement($this->admin_id, true));
    }

    public function form()
    {
        $recordId = FatApp::getPostedData('recordId', FatUtility::VAR_INT, 0);
        if (0 > $recordId) {
            LibHelper::exitWithError($this->str_invalid_request_id, true);
        }

        $data = [];
        $defaultCurrency = 0;
        if ($recordId > 0) {
            $data = Currency::getAttributesByLangId(
                CommonHelper::getDefaultFormLangId(),
                $recordId,
                array('currency_id', 'currency_code', 'currency_active', 'currency_symbol_left', 'currency_symbol_right', 'currency_value', 'currency_name'),
                true
            );
            if ($data === false) {
                LibHelper::exitWithError($this->str_invalid_request, true);
            }
            $defaultCurrency = ($data['currency_id'] == FatApp::getConfig("CONF_CURRENCY", FatUtility::VAR_INT, 1)) ? 1 : 0;
        }
        $frm = $this->getForm($defaultCurrency);
        $frm->fill($data);


        $this->set('recordId', $recordId);
        $this->set('frm', $frm);
        $this->set('formTitle', Labels::getLabel('LBL_CURRENCY_SETUP', $this->siteLangId));
        $this->set('html', $this->_template->render(false, false, NULL, true));
        $this->_template->render(false, false, 'json-success.php', true, false);
    }

    public function setup()
    {
        $this->objPrivilege->canEditCurrencyManagement();

        $frm = $this->getForm();
        $post = $frm->getFormDataFromArray(FatApp::getPostedData());

        if (false === $post) {
            LibHelper::exitWithError(current($frm->getValidationErrors()), true);
        }

        $recordId = FatUtility::int($post['currency_id']);
        unset($post['currency_id']);
        if ($recordId > 0) {
            $defaultCurrencyId = FatApp::getConfig("CONF_CURRENCY", FatUtility::VAR_INT, 1);
            if ($recordId == $defaultCurrencyId) {
                unset($post['currency_value']);
            }
        }
        $recordObj = new Currency($recordId);
        $post['currency_date_modified'] = date('Y-m-d H:i:s');
        $recordObj->assignValues($post);

        if (!$recordObj->save()) {
            LibHelper::exitWithError($recordObj->getError(), true);
        }

        $this->setLangData($recordObj, [$recordObj::tblFld('name') => $post[$recordObj::tblFld('name')]]);

        $this->_template->render(false, false, 'json-success.php');
    }

    public function updateOrder()
    {
        $this->objPrivilege->canEditCurrencyManagement();

        $post = FatApp::getPostedData();
        if (!empty($post)) {
            $currencyObj = new Currency();
            if (!$currencyObj->updateOrder($post['currencyIds'])) {
                LibHelper::exitWithError($currencyObj->getError(), true);
            }

            $this->set('msg', Labels::getLabel('MSG_ORDER_UPDATED_SUCCESSFULLY', $this->siteLangId));
            $this->_template->render(false, false, 'json-success.php');
        }
    }

    private function getForm(int $defaultCurrency = 0)
    {
        $frm = new Form('frmCurrency');
        $frm->addHiddenField('', 'currency_id');
        $frm->addRequiredField(Labels::getLabel('FRM_CURRENCY_NAME', $this->siteLangId), 'currency_name');
        $frm->addRequiredField(Labels::getLabel('FRM_CURRENCY_CODE', $this->siteLangId), 'currency_code');
        $frm->addTextbox(Labels::getLabel('FRM_CURRENCY_SYMBOL_LEFT', $this->siteLangId), 'currency_symbol_left');
        $frm->addTextbox(Labels::getLabel('FRM_CURRENCY_SYMBOL_RIGHT', $this->siteLangId), 'currency_symbol_right');
        $fld = $frm->addFloatField(Labels::getLabel('FRM_CURRENCY_CONVERSION_VALUE', $this->siteLangId), 'currency_value');
        if ($defaultCurrency) {
            $fld->htmlAfterField = '<small>' . Labels::getLabel('FRM_THIS_IS_YOUR_DEFAULT_CURRENCY', $this->siteLangId) . '</small>';
        }

        $frm->addCheckBox(Labels::getLabel('FRM_STATUS', $this->siteLangId), 'currency_active', applicationConstants::ACTIVE, [], true, applicationConstants::INACTIVE);

        $languageArr = Language::getDropDownList();
        $translatorSubscriptionKey = FatApp::getConfig('CONF_TRANSLATOR_SUBSCRIPTION_KEY', FatUtility::VAR_STRING, '');
        if (!empty($translatorSubscriptionKey) && 1 < count($languageArr)) {
            $frm->addCheckBox(Labels::getLabel('FRM_UPDATE_OTHER_LANGUAGES_DATA', $this->siteLangId), 'auto_update_other_langs_data', 1, array(), false, 0);
        }

        return $frm;
    }

    protected function getLangForm($currencyId = 0, $lang_id = 0)
    {
        $frm = new Form('frmCurrencyLang');
        $frm->addHiddenField('', 'currency_id', $currencyId);
        $frm->addSelectBox(Labels::getLabel('FRM_LANGUAGE', $this->siteLangId), 'lang_id', Language::getDropDownList(CommonHelper::getDefaultFormLangId()), $lang_id, array(), '');
        $frm->addRequiredField(Labels::getLabel('FRM_Currency_Name', $this->siteLangId), 'currency_name');
        return $frm;
    }

    public function updateStatus()
    {
        $this->objPrivilege->canEditCurrencyManagement();
        $recordId = FatApp::getPostedData('recordId', FatUtility::VAR_INT, 0);
        if (0 >= $recordId) {
            LibHelper::exitWithError($this->str_invalid_request_id, true);
        }

        $data = Currency::getAttributesById($recordId, array('currency_id', 'currency_active'));

        if ($data == false || $recordId == FatApp::getConfig('CONF_CURRENCY', FatUtility::VAR_INT, 0)) {
            LibHelper::exitWithError($this->str_invalid_request, true);
        }

        $status = ($data['currency_active'] == applicationConstants::ACTIVE) ? applicationConstants::INACTIVE : applicationConstants::ACTIVE;

        $this->changeStatus($recordId, $status);

        $this->set('msg', Labels::getLabel('MSG_STATUS_UPDATED', $this->siteLangId));
        $this->_template->render(false, false, 'json-success.php');
    }

    public function toggleBulkStatuses()
    {
        $this->objPrivilege->canEditCurrencyManagement();

        $status = FatApp::getPostedData('status', FatUtility::VAR_INT, -1);
        $recordIdsArr = FatUtility::int(FatApp::getPostedData('currency_ids'));
        if (empty($recordIdsArr) || -1 == $status) {
            LibHelper::exitWithError($this->str_invalid_request, true);
        }

        foreach ($recordIdsArr as $recordId) {
            if (1 > $recordId || $recordId == FatApp::getConfig('CONF_CURRENCY', FatUtility::VAR_INT, 0)) {
                continue;
            }

            $this->changeStatus($recordId, $status);
        }
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

        $obj = new Currency($recordId);
        if (!$obj->changeStatus($status)) {
            LibHelper::exitWithError($obj->getError(), true);
        }
    }

    protected function getFormColumns(): array
    {
        $currencyTblHeadingCols = CacheHelper::get('currencyTblHeadingCols' . $this->siteLangId, CONF_DEF_CACHE_TIME, '.txt');
        if ($currencyTblHeadingCols) {
            return json_decode($currencyTblHeadingCols, true);
        }

        $arr = [
            'dragdrop' => '',
            'select_all' => Labels::getLabel('LBL_Select_all', $this->siteLangId),
            'currency_display_order' => Labels::getLabel('LBL_DISPLAY_ORDER', $this->siteLangId),
            'currency_name' => Labels::getLabel('LBL_CURRENCY_NAME', $this->siteLangId),
            'currency_code' => Labels::getLabel('LBL_CURRENCY_CODE', $this->siteLangId),
            'currency_symbol_left' => Labels::getLabel('LBL_Symbol_Left', $this->siteLangId),
            'currency_symbol_right' => Labels::getLabel('LBL_Symbol_Right', $this->siteLangId),
            'currency_active' => Labels::getLabel('LBL_Status', $this->siteLangId),
            'action' => Labels::getLabel('LBL_ACTION_BUTTONS', $this->siteLangId),
        ];
        CacheHelper::create('currencyTblHeadingCols' . $this->siteLangId, json_encode($arr), CacheHelper::TYPE_LABELS);

        return $arr;
    }

    protected function getDefaultColumns(): array
    {
        return [
            'dragdrop',
            'select_all',
            'currency_display_order',
            'currency_name',
            'currency_code',
            'currency_symbol_left',
            'currency_symbol_right',
            'currency_active',
            'action',
        ];
    }

    protected function excludeKeysForSort($fields = []): array
    {
        return array_diff($fields, [
            'dragdrop',
            'currency_symbol_left',
            'currency_symbol_right'
        ], Common::excludeKeysForSort());
    }

    public function getBreadcrumbNodes($action)
    {
        switch ($action) {
            case 'index':
                $pageData = PageLanguageData::getAttributesByKey($this->pageKey, $this->siteLangId);
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
