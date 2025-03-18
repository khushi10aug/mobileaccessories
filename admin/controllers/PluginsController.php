<?php

class PluginsController extends ListingBaseController
{
    protected $pageKey = 'MANAGE_PLUGINS';

    public function __construct($action)
    {
        parent::__construct($action);
        $this->objPrivilege->canViewPlugins();
    }

    /**
     * setLangTemplateData - This function is use to automate load langform and save it. 
     *
     * @param  array $constructorArgs
     * @return void
     */
    protected function setLangTemplateData(array $constructorArgs = []): void
    {
        $this->objPrivilege->canEditPlugins();
        $this->modelObj = (new ReflectionClass('Plugin'))->newInstanceArgs($constructorArgs);
        $this->formLangFields = [$this->modelObj::tblFld('name'), $this->modelObj::tblFld('description')];
        $this->isPlugin = true;
        $identifier = Plugin::getAttributesById($this->mainTableRecordId, 'plugin_identifier');
        $this->set('formTitle', CommonHelper::replaceStringData(Labels::getLabel('LBL_{PLUGIN-NAME}_PLUGIN_SETUP', $this->siteLangId), ['{PLUGIN-NAME}' => $identifier]));
    }

    public function getSearchForm($fields = [], $type = Plugin::TYPE_CURRENCY_CONVERTER)
    {
        $frm = new Form('frmRecordSearch');
        $frm->addHiddenField('', 'type', $type);
        $frm->addHiddenField('', 'page', 1);
        if (!empty($fields)) {
            $this->addSortingElements($frm, 'plugin_name');
        }
        return $frm;
    }

    public function index($activeTab = Plugin::TYPE_CURRENCY_CONVERTER)
    {
        $tabs = Plugin::getTypeArr($this->siteLangId);
        if (!array_key_exists($activeTab, $tabs)) {
            $activeTab = Plugin::TYPE_CURRENCY_CONVERTER;
        }

        $fields = $this->getFormColumns();
        $frmSearch = $this->getSearchForm($fields);
        $frmSearch->fill(['type' => $activeTab]);

        $pageData = PageLanguageData::getAttributesByKey($this->pageKey, $this->siteLangId);
        $pageTitle = $pageData['plang_title'] ?? LibHelper::getControllerName(true);

        $this->set('frmSearch', $frmSearch);
        $this->set('pageData', $pageData);
        $this->set('pageTitle', $pageTitle);
        $this->set('svgIconNames', Plugin::getSvgIconNames());
        $this->set('activeTab', $activeTab);
        $this->set('includeEditor', true);
        $this->set('labels', Plugin::getLabels($this->siteLangId));
        $this->getListingData($activeTab);
        $this->set('tourStep', SiteTourHelper::getStepIndex());
        $this->_template->addCss('css/cropper.css');
        $this->_template->addJs(['js/cropper.js', 'js/cropper-main.js']);
        $this->_template->render();
    }

    public function search()
    {
        $this->getListingData();
        $jsonData = [
            'listingHtml' => $this->_template->render(false, false, 'plugins/search.php', true)
        ];
        LibHelper::exitWithSuccess($jsonData, true);
    }

    private function getListingData($activeTab = Plugin::TYPE_CURRENCY_CONVERTER)
    {
        $db = FatApp::getDb();

        $fields = $this->getFormColumns();
        $selectedFlds = FatApp::getPostedData('reportColumns', FatUtility::VAR_STRING, '');
        $selectedFlds = !empty($selectedFlds) ? json_decode($selectedFlds) +  $this->getDefaultColumns() : $this->getDefaultColumns();
        $fields =  FilterHelper::parseArrayByKeys($fields, $selectedFlds, true);

        $type = FatApp::getPostedData('type', FatUtility::VAR_INT, $activeTab);

        $allowedKeysForSorting = $this->excludeKeysForSort(array_keys($fields));
        $sortBy = FatApp::getPostedData('sortBy', FatUtility::VAR_STRING, 'plugin_display_order');
        if ((empty($sortBy) || 'listSerial' == $sortBy) && !in_array($type, Plugin::getKingpinTypeArr())) {
            $sortBy = 'plugin_display_order';
        } else if (!array_key_exists($sortBy, $fields) && 'plugin_display_order' != $sortBy) {
            $sortBy = current($allowedKeysForSorting);
        }

        $sortOrder = applicationConstants::getSortOrder(FatApp::getPostedData('sortOrder', FatUtility::VAR_STRING));


        $searchForm = $this->getSearchForm($fields);
        $postedData = FatApp::getPostedData();
        $post = $searchForm->getFormDataFromArray($postedData);

        $attr = array(
            'plg.plugin_id',
            'plg.plugin_type',
            'plg.plugin_code',
            'plg.plugin_active',
            'plg.plugin_display_order',
            'plg_l.plugin_name',
            'plg_l.plugin_description',
            'plg_l.pluginlang_lang_id',
            'conf.*',
            'COALESCE(plg_l.plugin_name, plg.plugin_identifier) as plugin_name'
        );
        $srch = Plugin::getSearchObject($this->siteLangId, false);
        $srch->joinTable(Configurations::DB_TBL, 'LEFT JOIN', "conf_val = plugin_id AND conf_name = 'CONF_DEFAULT_PLUGIN_" . $type . "'", 'conf');
        $srch->addCondition('plugin_type', '=', $type);
        $srch->addMultipleFields($attr);

        if (!array_key_exists($sortOrder, applicationConstants::sortOrder($this->siteLangId))) {
            $sortOrder = applicationConstants::SORT_ASC;
        }

        $srch->addOrder('plugin_active', 'DESC');
        $srch->addOrder($sortBy, $sortOrder);
        $srch->doNotLimitRecords();
        $arrListing = $db->fetchAll($srch->getResultSet());
        $activeTaxPluginFound = false;
        if (Plugin::TYPE_TAX_SERVICES == $type) {
            array_walk($arrListing, function ($val) use (&$activeTaxPluginFound) {
                if (Plugin::ACTIVE == $val[Plugin::DB_TBL_PREFIX . 'active']) {
                    $activeTaxPluginFound = true;
                    return;
                }
            });
        }

        $pluginTypes = Plugin::getTypeArr($this->siteLangId);
        $groupType = Plugin::getGroupType($type);
        $otherPluginTypes = '';
        if (!empty($groupType)) {
            foreach ($groupType as $pluginType) {
                if ($type == $pluginType) {
                    continue;
                }
                $gptSrch = Plugin::getSearchObject(0, true);
                $gptSrch->addCondition('plg.' . Plugin::DB_TBL_PREFIX . 'type', '=', $pluginType);
                $gptSrch->setPageSize(1);
                $gptSrch->getResultSet();
                if (0 < $gptSrch->recordCount()) {
                    $otherPluginTypes .= $pluginTypes[$pluginType] . ', ';
                }
            }
            $otherPluginTypes = rtrim($otherPluginTypes, ', ');
        }

        if (Plugin::TYPE_CURRENCY_CONVERTER == $type) {
            $currency = new Currency();
            $currencyConverter = $currency->getCurrencyConverterApi();
            if ($currencyConverter && $this->objPrivilege->canEditPlugins($this->admin_id, true)) {
                $otherButtons = [
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
                $this->set("otherButtons", $otherButtons);
            }
        }else if (Plugin::TYPE_SHIPPING_SERVICES == $type) {
            $shippingService = Plugin::isActiveByType(Plugin::TYPE_SHIPPING_SERVICES);
            if ($shippingService && $this->objPrivilege->canEditPlugins($this->admin_id, true)) {
                $otherButtons = [
                    [
                        'attr' => [
                            'href' => 'javascript:void(0)',
                            'class' => 'btn btn-outline-brand btn-icon',
                            'onclick' => "syncCarriers()",
                            'title' => Labels::getLabel('LBL_SYNC_PLUGIN_CARRIERS', $this->siteLangId)
                        ],
                        'label' => '<svg class="svg" width="18" height="18">
                                        <use
                                            xlink:href="' . CONF_WEBROOT_URL . 'images/retina/sprite.yokart.svg' . AttachedFile::setTimeParam(RELEASE_DATE) . '#sync">
                                        </use>
                                    </svg><span>' . Labels::getLabel('LBL_SYNC_CARRIERS', $this->siteLangId) . '</span>',
                    ]
                ];
                $this->set("otherButtons", $otherButtons);
            }
        }

        $this->set("arrListing", $arrListing);
        $this->set('recordCount', $srch->recordCount());
        $this->set('activeInactiveArr', applicationConstants::getActiveInactiveArr($this->siteLangId));

        $paginationArr = empty($postedData) ? $post : $postedData;
        $this->set('postedData', $paginationArr);

        $this->set('sortBy', $sortBy);
        $this->set('sortOrder', $sortOrder);
        $this->set('fields', $fields);
        $this->set('allowedKeysForSorting', $allowedKeysForSorting);
        $this->set("activeTaxPluginFound", $activeTaxPluginFound);
        $this->set("type", $type);
        $this->set("pluginTypes", $pluginTypes);
        $this->set("otherPluginTypes", $otherPluginTypes);
        $this->set('canEdit', $this->objPrivilege->canEditPlugins($this->admin_id, true));
    }

    public function form()
    {
        $recordId = FatApp::getPostedData('recordId', FatUtility::VAR_INT, 0);
        if (0 > $recordId) {
            LibHelper::exitWithError($this->str_invalid_request_id, true);
        }

        $data = Plugin::getAttributesByLangId(CommonHelper::getDefaultFormLangId(), $recordId, ['*', 'IFNULL(plugin_name,plugin_identifier) as plugin_name'], applicationConstants::JOIN_RIGHT);
        $pluginType = $data['plugin_type'];
        $frm = $this->getForm($pluginType, $recordId);
        $identifier = '';
        $pluginLogo = NULL;
        if (0 < $recordId) {
            if ($data === false) {
                LibHelper::exitWithError($this->str_invalid_request, true);
            }

            if (in_array($pluginType, Plugin::getKingpinTypeArr())) {
                $defaultCurrConvAPI = FatApp::getConfig('CONF_DEFAULT_PLUGIN_' . $pluginType, FatUtility::VAR_INT, 0);
                if (!empty($defaultCurrConvAPI)) {
                    $data['CONF_DEFAULT_PLUGIN_' . $pluginType] = $defaultCurrConvAPI;
                }
            }
            $identifier = $data['plugin_identifier'];
            $frm->fill($data);
            if (in_array($pluginType, Plugin::getSeparateIconTypeArr())) {
                $pluginLogo = AttachedFile::getAttachment(AttachedFile::FILETYPE_PLUGIN_LOGO, $recordId);
            }
        }

        $this->set('pluginLogo', $pluginLogo);

        $this->set('recordId', $recordId);
        $this->set('type', $pluginType);
        $this->set('frm', $frm);
        $this->set('canEdit', $this->objPrivilege->canEditPlugins($this->admin_id, true));
        $this->set('formTitle', CommonHelper::replaceStringData(Labels::getLabel('LBL_{PLUGIN-NAME}_PLUGIN_SETUP', $this->siteLangId), ['{PLUGIN-NAME}' => $identifier]));
        $this->set('html', $this->_template->render(false, false, NULL, true));
        $this->_template->render(false, false, 'json-success.php', true, false);
    }

    public function setup()
    {
        $this->objPrivilege->canEditPlugins();
        $post = FatApp::getPostedData();
        $recordId = $post['plugin_id'];
        $pluginType = $post['plugin_type'];
        $frm = $this->getForm($pluginType, $recordId);
        $post = $frm->getFormDataFromArray($post);
        if (false === $post) {
            LibHelper::exitWithError(current($frm->getValidationErrors()), true);
        }
        unset($post['plugin_id'], $post['plugin_type'], $post['plugin_active']);

        $pluginData = Plugin::getAttributesById($recordId, ['plugin_id', 'plugin_type', 'plugin_active']);
        if ($pluginData === false) {
            LibHelper::exitWithError($this->str_invalid_request, true);
        }

        $active = FatApp::getPostedData('plugin_active', FatUtility::VAR_INT, 0);

        $record = new Plugin($recordId);
        $post['plugin_identifier'] = $post['plugin_name'];
        $record->assignValues($post);
        if (!$record->save()) {
            $msg = $record->getError();
            if (false !== strpos(strtolower($msg), 'duplicate')) {
                $msg = Labels::getLabel('ERR_DUPLICATE_RECORD_NAME', $this->siteLangId);
            }
            LibHelper::exitWithError($msg, true);
        }

        $langData = [
            $record::tblFld('name') => $post[$record::tblFld('name')]
        ];

        if (isset($post[$record::tblFld('description')])) {
            $langData[$record::tblFld('description')] = $post[$record::tblFld('description')];
        }
        $this->setLangData($record, $langData);

        $newTabLangId = 0;
        if ($recordId > 0) {
            $languages = Language::getAllNames();
            foreach ($languages as $langId => $langName) {
                if (!Plugin::getAttributesByLangId($langId, $recordId)) {
                    $newTabLangId = $langId;
                    break;
                }
            }
        } else {
            $recordId = $record->getMainTableRecordId();
            $newTabLangId = FatApp::getConfig('CONF_ADMIN_DEFAULT_LANG', FatUtility::VAR_INT, 1);
        }

        if (in_array($pluginType, Plugin::getKingpinTypeArr())) {
            $defaultCurrConvAPI = FatApp::getConfig('CONF_DEFAULT_PLUGIN_' . $pluginType, FatUtility::VAR_INT, 0);
            if (!empty($post['CONF_DEFAULT_PLUGIN_' . $pluginType]) || empty($defaultCurrConvAPI)) {
                $confVal = empty($defaultCurrConvAPI) ? $recordId : $post['CONF_DEFAULT_PLUGIN_' . $pluginType];
                $confRecord = new Configurations();
                if (!$confRecord->update(['CONF_DEFAULT_PLUGIN_' . $pluginType => $confVal])) {
                    LibHelper::exitWithError($confRecord->getError(), true);
                }
            }
        }

        if ($pluginData['plugin_active'] != $active) {
            if (false == Plugin::updateStatus($pluginData['plugin_type'], $active, $recordId, $error)) {
                LibHelper::exitWithError($error, true);
            }
        }

        $this->set('msg', $this->str_update_record);
        $this->set('recordId', $recordId);
        $this->set('langId', $newTabLangId);
        $this->_template->render(false, false, 'json-success.php');
    }

    public function uploadIcon()
    {
        $this->objPrivilege->canEditPlugins();
        $plugin_id = FatApp::getPostedData('plugin_id', FatUtility::VAR_INT, 0);

        if (1 > $plugin_id) {
            LibHelper::exitWithError($this->str_invalid_request, true);
        }

        if (!is_uploaded_file($_FILES['cropped_image']['tmp_name'])) {
            LibHelper::exitWithError(Labels::getLabel('ERR_Please_select_a_file', $this->siteLangId), true);
        }

        $fileHandlerObj = new AttachedFile();
        $res = $fileHandlerObj->saveAttachment($_FILES['cropped_image']['tmp_name'], AttachedFile::FILETYPE_PLUGIN_LOGO, $plugin_id, 0, $_FILES['cropped_image']['name'], -1, true);
        if (!$res) {
            LibHelper::exitWithError($fileHandlerObj->getError(), true);
        }

        $this->set('pluginId', $plugin_id);
        $this->set('file', $_FILES['cropped_image']['name']);
        $this->set('msg', $_FILES['cropped_image']['name'] . ' ' . Labels::getLabel('LBL_File_Uploaded_Successfully', $this->siteLangId));
        $this->_template->render(false, false, 'json-success.php');
    }

    public function deleteIcon()
    {
        $this->objPrivilege->canEditPlugins();
        $recordId = FatApp::getPostedData('recordId', FatUtility::VAR_INT, 0);

        if (1 > $recordId) {
            LibHelper::exitWithError($this->str_invalid_request, true);
        }

        $fileHandlerObj = new AttachedFile();
        if (!$fileHandlerObj->deleteFile(AttachedFile::FILETYPE_PLUGIN_LOGO, $recordId)) {
            LibHelper::exitWithError($fileHandlerObj->getError(), true);
        }

        $this->set('msg', Labels::getLabel('MSG_DELETED_SUCCESSFULLY', $this->siteLangId));
        $this->_template->render(false, false, 'json-success.php');
    }

    public function updateOrder()
    {
        $this->objPrivilege->canEditPlugins();

        $post = FatApp::getPostedData();

        if (!empty($post)) {
            $pluginObj = new Plugin();
            if (!$pluginObj->updateOrder($post['plugin'])) {
                LibHelper::exitWithError($pluginObj->getError(), true);
            }

            $this->set('msg', Labels::getLabel('MSG_ORDER_UPDATED_SUCCESSFULLY', $this->siteLangId));
            $this->_template->render(false, false, 'json-success.php');
        }
    }

    public function updateStatus()
    {
        $this->objPrivilege->canEditPlugins();
        $recordId = FatApp::getPostedData('recordId', FatUtility::VAR_INT, 0);
        $status = FatApp::getPostedData('status', FatUtility::VAR_INT, 0);
        if (0 >= $recordId) {
            LibHelper::exitWithError($this->str_invalid_request_id, true);
        }

        $data = Plugin::getAttributesById($recordId, array('plugin_id', 'plugin_active', 'plugin_type'));

        if ($data == false) {
            LibHelper::exitWithError($this->str_invalid_request, true);
        }

        if (false == Plugin::updateStatus($data['plugin_type'], $status, $recordId, $error)) {
            LibHelper::exitWithError($error, true);
        }

        $this->set('msg', Labels::getLabel('MSG_STATUS_UPDATED', $this->siteLangId));
        $this->_template->render(false, false, 'json-success.php');
    }

    public function changeStatusByType()
    {
        $this->objPrivilege->canEditPlugins();
        $recordId = FatApp::getPostedData('pluginId', FatUtility::VAR_INT, 0);
        $status = FatApp::getPostedData('status', FatUtility::VAR_INT, 0);
        if (0 >= $recordId) {
            LibHelper::exitWithError($this->str_invalid_request_id, true);
        }

        $data = Plugin::getAttributesById($recordId, array('plugin_id', 'plugin_active', 'plugin_type'));

        if ($data == false) {
            LibHelper::exitWithError($this->str_invalid_request, true);
        }

        if (false == Plugin::updateStatus($data['plugin_type'], $status, $recordId, $error)) {
            LibHelper::exitWithError($error, true);
        }

        if (Plugin::ACTIVE == $status) {
            $groupType = Plugin::getGroupType($data['plugin_type']);
            $eitherPluginTypes = array_values(array_diff($groupType, [$data['plugin_type']]));

            foreach ($eitherPluginTypes as $pluginType) {
                if (false == Plugin::updateStatus($pluginType, Plugin::INACTIVE, null, $error)) {
                    LibHelper::exitWithError($error, true);
                }
            }
        }

        $this->set('msg', $this->str_update_record);
        $this->_template->render(false, false, 'json-success.php');
    }

    private function getForm($pluginType, $recordId = 0)
    {
        $recordId = FatUtility::int($recordId);

        $frm = new Form('frmPlugin');
        $frm->addHiddenField('', 'plugin_id', $recordId);
        $frm->addHiddenField('', 'plugin_type', $pluginType);
        $frm->addRequiredField(Labels::getLabel('FRM_PLUGIN_NAME', $this->siteLangId), 'plugin_name');

        if (in_array($pluginType, Plugin::HAVING_DESCRIPTION)) {
            $frm->addHtmlEditor(Labels::getLabel('FRM_EXTRA_INFO', $this->siteLangId), 'plugin_description');
        }
        $fld = $frm->addCheckBox(Labels::getLabel('FRM_STATUS', $this->siteLangId), 'plugin_active', applicationConstants::ACTIVE, [], true, applicationConstants::INACTIVE);
        HtmlHelper::configureSwitchForCheckbox($fld);
        $fld->developerTags['noCaptionTag'] = true;

        if (in_array($pluginType, Plugin::getSeparateIconTypeArr())) {
            $frm->addHTML('', 'plugin_logo', '');
        }

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
        $frm = new Form('frmPluginLang');
        $frm->addHiddenField('', 'plugin_id', $recordId);

        $frm->addSelectBox(Labels::getLabel('FRM_LANGUAGE', $langId), 'lang_id', Language::getDropDownList(CommonHelper::getDefaultFormLangId()), $langId, array(), '');
        $frm->addRequiredField(Labels::getLabel('FRM_PLUGIN_NAME', $langId), 'plugin_name');
        $frm->addHtmlEditor(Labels::getLabel('FRM_EXTRA_INFO', $langId), 'plugin_description');
        return $frm;
    }

    public function toggleBulkStatuses()
    {
        $this->objPrivilege->canEditPlugins();

        $status = FatApp::getPostedData('status', FatUtility::VAR_INT, -1);
        $pluginType = FatApp::getPostedData('plugin_type', FatUtility::VAR_INT, 0);
        $recordIdsArr = FatUtility::int(FatApp::getPostedData('plugin_ids'));
        if (empty($recordIdsArr) || -1 == $status || 1 > $pluginType) {
            LibHelper::exitWithError($this->str_invalid_request, true);
        }
        $error = '';
        foreach ($recordIdsArr as $recordId) {
            if (1 > $recordId) {
                continue;
            }
            Plugin::updateStatus($pluginType, $status, $recordId, $error);
        }

        if (Plugin::ACTIVE == $status) {
            $groupType = Plugin::getGroupType($pluginType);
            $eitherPluginTypes = array_values(array_diff($groupType, [$pluginType]));
            foreach ($eitherPluginTypes as $pluginType) {
                if (false == Plugin::updateStatus($pluginType, Plugin::INACTIVE, null, $error)) {
                    LibHelper::exitWithError($error, true);
                }
            }
        }

        $msg = !empty($error) ? $error : Labels::getLabel('LBL_STATUS_UPDATED', $this->siteLangId);
        $this->set('msg', $msg);
        $this->_template->render(false, false, 'json-success.php');
    }

    public function changeBulkStatusByType()
    {
        $this->objPrivilege->canEditPlugins();
        $pluginGroupType = FatApp::getPostedData('plugin_type', FatUtility::VAR_INT, 0);
        $status = FatApp::getPostedData('status', FatUtility::VAR_INT, 0);

        $recordIdsArr = FatUtility::int(FatApp::getPostedData('plugin_ids'));
        if (empty($recordIdsArr) || -1 == $status || 1 > $pluginGroupType) {
            LibHelper::exitWithError($this->str_invalid_request, true);
        }

        foreach ($recordIdsArr as $recordId) {
            if (1 > $recordId) {
                continue;
            }
            Plugin::updateStatus($pluginGroupType, $status, $recordId);
        }

        if (Plugin::ACTIVE == $status) {
            $groupType = Plugin::getGroupType($pluginGroupType);
            $eiherPluginTypes = array_values(array_diff($groupType, [$pluginGroupType]));

            foreach ($eiherPluginTypes as $pluginType) {
                if (false == Plugin::updateStatus($pluginType, Plugin::INACTIVE, null, $error)) {
                    LibHelper::exitWithError($error, true);
                }
            }
        }

        $this->set('msg', $this->str_update_record);
        $this->_template->render(false, false, 'json-success.php');
    }

    protected function getFormColumns(): array
    {
        $pluginsTblHeadingCols = CacheHelper::get('pluginsTblHeadingCols' . $this->siteLangId, CONF_DEF_CACHE_TIME, '.txt');
        if ($pluginsTblHeadingCols) {
            return json_decode($pluginsTblHeadingCols, true);
        }

        $arr = [
            'dragdrop' => '',
            'select_all' => Labels::getLabel('LBL_Select_all', $this->siteLangId),
            /*  'listSerial' => Labels::getLabel('LBL_SR._NO', $this->siteLangId), */
            'plugin_icon' => Labels::getLabel('LBL_PLUGIN_ICON', $this->siteLangId),
            'plugin_name' => Labels::getLabel('LBL_PLUGIN', $this->siteLangId),
            'plugin_active' => Labels::getLabel('LBL_Status', $this->siteLangId),
            'action' => Labels::getLabel('LBL_ACTION_BUTTONS', $this->siteLangId),
        ];
        CacheHelper::create('pluginsTblHeadingCols' . $this->siteLangId, json_encode($arr), CacheHelper::TYPE_LABELS);
        return $arr;
    }

    protected function getDefaultColumns(): array
    {
        return [
            'dragdrop',
            'select_all',
            /* 'listSerial', */
            'plugin_icon',
            'plugin_name',
            'plugin_active',
            'action',
        ];
    }

    protected function excludeKeysForSort($fields = []): array
    {
        return array_diff($fields, ['dragdrop', 'plugin_icon', 'plugin_active'], Common::excludeKeysForSort());
    }

    public function getBreadcrumbNodes($action)
    {

        switch ($action) {
            case 'index':
                $pageData = PageLanguageData::getAttributesByKey('MANAGE_PLUGINS', $this->siteLangId);
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
