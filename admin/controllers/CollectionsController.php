<?php
class CollectionsController extends ListingBaseController
{
    protected string $modelClass = 'Collections';
    protected string $pageKey = 'MANAGE_COLLECTIONS';
    protected bool $bannersTab = false;
    protected array $collectionDetails = [];

    public function __construct($action)
    {
        parent::__construct($action);
        $this->objPrivilege->canViewCollections();
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
            $this->set("canEdit", $this->objPrivilege->canEditBadgesAndRibbons($this->admin_id, true));
        } else {
            $this->objPrivilege->canEditBadgesAndRibbons();
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
        $this->formLangFields = [
            $this->modelObj::tblFld('name'),
            $this->modelObj::tblFld('description'),
            $this->modelObj::tblFld('link_caption'),
        ];

        if (0 < $this->modelObj->getMainTableRecordId()) {
            if ($collection_type = $this->modelObj::getAttributesById($this->modelObj->getMainTableRecordId(), 'collection_type')) {
                if (!in_array($collection_type, Collections::COLLECTION_WITHOUT_RECORDS)) {
                    $this->set('recordForm', 1);
                } elseif ($collection_type == Collections::COLLECTION_TYPE_BANNER) {
                    $this->set('banners', 1);
                }
            }
        }
    }

    public function index()
    {
        $fields = $this->getFormColumns();
        $frmSearch = $this->getSearchForm($fields);

        $pageData = PageLanguageData::getAttributesByKey($this->pageKey, $this->siteLangId);
        $pageTitle = $pageData['plang_title'] ?? LibHelper::getControllerName(true);

        $actionItemsData = HtmlHelper::getDefaultActionItems($fields);
        $actionItemsData['performBulkAction'] = true;
        $actionItemsData['statusButtons'] = true;
        $actionItemsData['deleteButton'] = true;
        $actionItemsData['newRecordBtnAttrs'] = [
            'attr' => [
                'onclick' => 'layoutSelectorForm()',
                'title' => Labels::getLabel('MSG_ADD_LAYOUT_TYPE_BASED_COLLECTION', $this->siteLangId),
            ]
        ];

        $this->set('pageData', $pageData);
        $this->set('pageTitle', $pageTitle);
        $this->set('actionItemsData', $actionItemsData);
        $this->set("frmSearch", $frmSearch);
        $this->set('defaultColumns', $this->getDefaultColumns());
        $this->set('keywordPlaceholder', Labels::getLabel('FRM_SEARCH_BY_COLLECTION_NAME', $this->siteLangId));
        $this->getListingData();

        $typeLayouts = Collections::getTypeSpecificLayouts($this->siteLangId);
        $this->set('typeLayouts', $typeLayouts);
        $this->set('includeEditor', true);
        $this->_template->addJs(['js/cropper.js', 'js/cropper-main.js', 'js/select2.js', 'collections/page-js/index.js']);
        $this->_template->addCss(['css/cropper.css', 'css/select2.min.css']);

        $this->_template->render(true, true, '_partial/listing/index.php');
    }

    public function search()
    {
        $this->getListingData();
        $jsonData = [
            'listingHtml' => $this->_template->render(false, false, 'collections/search.php', true),
            'paginationHtml' => $this->_template->render(false, false, '_partial/listing/listing-foot.php', true)
        ];
        LibHelper::exitWithSuccess($jsonData, true);
    }

    private function getListingData()
    {
        $this->checkEditPrivilege(true);
        $pageSize = applicationConstants::getPageSize(FatApp::getPostedData('pageSize', FatUtility::VAR_INT));
        $fields = $this->getFormColumns();
        $selectedFlds = FatApp::getPostedData('reportColumns', FatUtility::VAR_STRING, '');
        $selectedFlds = !empty($selectedFlds) ? json_decode($selectedFlds) + $this->getDefaultColumns() : $this->getDefaultColumns();
        $fields = FilterHelper::parseArrayByKeys($fields, $selectedFlds, true);

        $allowedKeysForSorting = $this->excludeKeysForSort(array_keys($fields));
        $sortBy = FatApp::getPostedData('sortBy', FatUtility::VAR_STRING, 'collection_display_order');
        if (!array_key_exists($sortBy, $fields)) {
            $sortBy = 'collection_display_order';
        }
        $sortOrder = applicationConstants::getSortOrder(FatApp::getPostedData('sortOrder', FatUtility::VAR_STRING));

        $srchFrm = $this->getSearchForm($fields);

        $postedData = FatApp::getPostedData();
        $page = FatApp::getPostedData('page', FatUtility::VAR_INT, 1);
        $page = ($page <= 0) ? 1 : $page;
        $post = $srchFrm->getFormDataFromArray($postedData);

        $srch = Collections::getSearchObject(false, $this->siteLangId);

        if (isset($post['keyword']) && '' != $post['keyword']) {
            $condition = $srch->addCondition('c.collection_identifier', 'like', '%' . $post['keyword'] . '%');
            $condition->attachCondition('c_l.collection_name', 'like', '%' . $post['keyword'] . '%', 'OR');
        }

        $collection_type = FatApp::getPostedData('collection_type', FatUtility::VAR_INT, 0);
        if ($collection_type) {
            $srch->addCondition('collection_type', '=', 'mysql_func_' . $collection_type, 'AND', true);
        }

        $collection_layout_type = FatApp::getPostedData('collection_layout_type', FatUtility::VAR_INT, 0);
        if ($collection_layout_type > 0) {
            $srch->addCondition('collection_layout_type', '=', 'mysql_func_' . $collection_layout_type, 'AND', true);
        }

        $applicableFor = FatApp::getPostedData('applicable_for', FatUtility::VAR_INT, 0);
        if ($applicableFor === Collections::FOR_WEB) {
            $srch->addCondition('collection_for_web', '=', applicationConstants::YES);
            $srch->addCondition('collection_for_app', '=', applicationConstants::NO);
        } elseif ($applicableFor === Collections::FOR_APP) {
            $srch->addCondition('collection_for_web', '=', applicationConstants::NO);
            $srch->addCondition('collection_for_app', '=', applicationConstants::YES);
        }

        $this->setRecordCount(clone $srch, $pageSize, $page, $post);

        $srch->addMultipleFields(array('c.*', 'COALESCE(c_l.collection_name, c.collection_identifier) as collection_name'));
        $srch->setPageNumber($page);
        $srch->setPageSize($pageSize);
        $srch->addOrder($sortBy, $sortOrder);

        $rs = $srch->getResultSet();
        $records = FatApp::getDb()->fetchAll($rs);

        $this->set("arrListing", $records);
        $this->set('postedData', $post);
        $this->set('frmSearch', $srchFrm);
        $this->set('sortBy', $sortBy);
        $this->set('sortOrder', $sortOrder);
        $this->set('fields', $fields);
        $this->set('allowedKeysForSorting', $allowedKeysForSorting);
        $this->set('applicableTypes', Collections::getLayoutApplicableTypes($this->siteLangId));
        $this->set('canEdit', $this->objPrivilege->canEditCollections($this->admin_id, true));
    }

    protected function getSearchForm(array $fields = [])
    {
        $fields = $this->getFormColumns();

        $frm = new Form('frmRecordSearch');
        $frm->addHiddenField('', 'page');
        $fld = $frm->addTextBox(Labels::getLabel('FRM_KEYWORD', $this->siteLangId), 'keyword');
        $fld->overrideFldType('search');

        if (!empty($fields)) {
            $this->addSortingElements($frm, 'collection_display_order');
        }

        $typeArr = Collections::getTypeArr($this->siteLangId);
        unset($typeArr[Collections::COLLECTION_TYPE_CONTENT_BLOCK]);
        $frm->addSelectBox(Labels::getLabel('FRM_TYPE', $this->siteLangId), 'collection_type', $typeArr);
        $frm->addSelectBox(Labels::getLabel('FRM_LAYOUT_TYPE', $this->siteLangId), 'collection_layout_type', Collections::getLayoutTypeArr($this->siteLangId));
        $frm->addSelectBox(Labels::getLabel('FRM_APPLICABLE_FOR', $this->siteLangId), 'applicable_for', Collections::getLayoutApplicableTypes($this->siteLangId), '', [], Labels::getLabel('FRM_BOTH', $this->siteLangId));

        HtmlHelper::addSearchButton($frm);
        HtmlHelper::addClearButton($frm);/*clearBtn*/
        return $frm;
    }

    public function layoutSelectorForm()
    {
        $typeLayouts = Collections::getTypeSpecificLayouts($this->siteLangId);
        $typeArr = Collections::getTypeArr($this->siteLangId);

        $sliderSrch = Collections::getSearchObject(false, 0);
        $sliderSrch->addCondition('collection_type', '=', Collections::COLLECTION_TYPE_HERO_SLIDES);
        $sliderSrch->doNotCalculateRecords();
        $rs = $sliderSrch->getResultSet();
        $row = (array) FatApp::getDb()->fetch($rs);
        if (!empty($row)) {
            unset($typeArr[Collections::COLLECTION_TYPE_HERO_SLIDES]);
            unset($typeLayouts[Collections::COLLECTION_TYPE_HERO_SLIDES]);
        }

        $this->set('typeLayouts', $typeLayouts);
        $this->set('typeArr', $typeArr);
        $this->set('html', $this->_template->render(false, false, NULL, true));
        $this->_template->render(false, false, 'json-success.php', true, false);
    }

    private function setFormTitle(int $type, int $layoutType, string $backBtnOnclick = 'layoutSelectorForm()'): void
    {
        $typeLayouts = Collections::getTypeSpecificLayouts($this->siteLangId);
        $formTitle = $typeLayouts[$type][$layoutType] ?? '';
        $str = Labels::getLabel('LBL_{LAYOUT-NAME}_SETUP', $this->siteLangId);
        if (true === $this->bannersTab) {
            $str = Labels::getLabel('LBL_{LAYOUT-NAME}_SETUP_-_BANNERS', $this->siteLangId);
        }
        $this->set('formTitle', CommonHelper::replaceStringData($str, ['{LAYOUT-NAME}' => $formTitle]));
        $this->set('formBackButtonAttr', ['onclick' => $backBtnOnclick]);
    }

    public function form(int $type, int $layoutType)
    {
        $type = FatUtility::int($type);
        $layoutType = FatUtility::int($layoutType);
        $recordId = FatApp::getPostedData('recordId', FatUtility::VAR_INT, 0);
        $frm = $this->getForm($type, $layoutType, $recordId);

        if (0 < $recordId) {
            $data = Collections::getAttributesByLangId($this->siteLangId, $recordId, ['*', 'IFNULL(collection_name,collection_identifier) as collection_name'], applicationConstants::JOIN_RIGHT);
            if ($data === false) {
                LibHelper::exitWithError($this->str_invalid_request, true);
            }

            if ($type == Collections::COLLECTION_TYPE_BANNER) {
                $bannerLocation = BannerLocation::getDataByCollectionId($recordId, 'blocation_promotion_cost');
                $data['blocation_promotion_cost'] = $bannerLocation['blocation_promotion_cost'] ?? '';
            }

            $data['collection_layout_type'] = $layoutType;
            $frm->fill($data);
        }
        $this->setFormTitle($type, $layoutType);

        $this->set('recordId', $recordId);
        $this->set('collection_type', $type);
        $this->set('collection_layout_type', $layoutType);
        $this->set('frm', $frm);
        $this->set('html', $this->_template->render(false, false, NULL, true));
        $this->_template->render(false, false, 'json-success.php', true, false);
    }

    public function setup()
    {
        $this->objPrivilege->canEditCollections();
        $post = FatApp::getPostedData();
        $frm = $this->getForm($post['collection_type'], $post['collection_layout_type']);
        $post = $frm->getFormDataFromArray(FatApp::getPostedData());
        if (false === $post) {
            LibHelper::exitWithError(current($frm->getValidationErrors()), true);
        }

        $recordId = $post['collection_id'];
        $defaultItemsCount = Collections::getLayoutLimit($post['collection_layout_type']);
        $displayItemsCount = FatApp::getPostedData('collection_primary_records', FatUtility::VAR_INT, $defaultItemsCount);
        if (!in_array($post['collection_layout_type'], Collections::COLLECTIONS_FOR_DISPLAY_COUNT)) {
            $displayItemsCount = $defaultItemsCount;
        }

        if (1 > $displayItemsCount) {
            $range = Collections::displayRecordsCount($post['collection_layout_type']);
            $displayItemsCount = !empty($range) ? min($range) : 1;
        }

        if ($post['collection_type'] == Collections::COLLECTION_TYPE_BANNER && $post['collection_layout_type'] != Collections::TYPE_BANNER_LAYOUT2) {
            $displayItemsCount = Collections::getBannersCount()[$post['collection_layout_type']];
        }

        $post['collection_identifier'] = $post['collection_name'];
        $post['collection_primary_records'] = $displayItemsCount;

        if (in_array($post['collection_layout_type'], Collections::COLLECTIONS_FOR_APP_ONLY)) {
            $post['collection_for_app'] = 1;
            $post['collection_for_web'] = 0;
        } else if (in_array($post['collection_layout_type'], Collections::COLLECTIONS_FOR_WEB_ONLY)) {
            $post['collection_for_app'] = 0;
            $post['collection_for_web'] = 1;
        } else {
            $post['collection_for_app'] = ($post['collection_for_app'] ?? 0);
            $post['collection_for_web'] = ($post['collection_for_web'] ?? 0);
        }

        $post['collection_full_width'] = $post['collection_full_width'] ?? 0;

        if (1 > $recordId) {
            $maxDisplayOrder = Collections::getMaxDisplayOrder();
            $post['collection_display_order'] = $maxDisplayOrder + 1;
        }

        $collection = new Collections($recordId);
        $collection->assignValues($post);
        if (!$collection->save()) {
            $msg = $collection->getError();
            if (false !== strpos(strtolower($msg), 'duplicate')) {
                $msg = Labels::getLabel('ERR_DUPLICATE_RECORD_NAME', $this->siteLangId);
            }
            LibHelper::exitWithError($msg, true);
        }

        $recordId = $collection->getMainTableRecordId();

        $this->setLangData($collection, [
            $collection::tblFld('name') => $post[$collection::tblFld('name')],
            $collection::tblFld('description') => $post[$collection::tblFld('description')] ?? '',
            $collection::tblFld('link_caption') => $post[$collection::tblFld('link_caption')] ?? '',
        ]);

        $post['collection_id'] = $recordId;

        if ($post['collection_type'] == Collections::COLLECTION_TYPE_BANNER) {
            $this->saveBannerLocation($post);
            $this->set('banners', 1);
        } else if (!in_array($post['collection_type'], Collections::COLLECTION_WITHOUT_RECORDS)) {
            $this->set('recordForm', 1);
        }

        $this->set('msg', Labels::getLabel('MSG_SETUP_SUCCESSFUL', $this->siteLangId));
        $this->set('recordId', $recordId);
        $this->set('collectionType', $post['collection_type']);
        $this->_template->render(false, false, 'json-success.php');
    }

    private function getForm($type, $layoutType, $recordId = 0)
    {
        $frm = new Form('frmCollection');
        $frm->addHiddenField('', 'collection_id', $recordId);
        $frm->addHiddenField('', 'collection_active', applicationConstants::ACTIVE);
        $frm->addHiddenField('', 'collection_type', $type);
        $productTypeLayoutArr = Collections::getTypeSpecificLayouts($this->siteLangId);

        $frm->addSelectBox(Labels::getLabel('FRM_COLLECTION_LAYOUT_TYPE', $this->siteLangId), 'collection_layout_type', $productTypeLayoutArr[$type], $layoutType, ['onchange' => 'collectionForm(' . $type . ', this.value, ' . $recordId . ')'], '');

        $frm->addRequiredField(Labels::getLabel('FRM_NAME', $this->siteLangId), 'collection_name');

        if (Collections::COLLECTION_TYPE_CONTENT_BLOCK == $type) {
            $frm->addHtmlEditor(Labels::getLabel('FRM_DESCRIPTION', $this->siteLangId), 'collection_description');
        }

        if ($type == Collections::COLLECTION_TYPE_BANNER && Collections::TYPE_BANNER_LAYOUT4 != $layoutType) {
            $frm->addTextBox(Labels::getLabel('FRM_PROMOTION_COST', $this->siteLangId), 'blocation_promotion_cost');
        }

        if (in_array($layoutType, Collections::COLLECTIONS_FOR_DISPLAY_COUNT)) {
            $range = Collections::displayRecordsCount($layoutType);
            $fld = $frm->addSelectBox(Labels::getLabel('FRM_DISPLAY_ITEMS_COUNT', $this->siteLangId), 'collection_primary_records', array_combine($range, $range));
            $fld->requirements()->setRequired(true);
        }

        if (in_array($layoutType, Collections::COLLECTIONS_FOR_APP_ONLY)) {
            $frm->addHiddenField('', 'collection_for_app', 1);
        } else if (in_array($layoutType, Collections::COLLECTIONS_FOR_WEB_ONLY)) {
            $frm->addHiddenField('', 'collection_for_app', 1);
        } else {
            $frm->addCheckBox(Labels::getLabel("FRM_APPLICABLE_FOR_WEB", $this->siteLangId), 'collection_for_web', 1, array(), true, 0);
            $frm->addCheckBox(Labels::getLabel("FRM_APPLICABLE_FOR_APP", $this->siteLangId), 'collection_for_app', 1, array(), true, 0);
        }

        if (in_array($layoutType, Collections::COLLECTIONS_FULL_WIDTH)) {
            $frm->addCheckBox(Labels::getLabel("FRM_FULL_WIDTH", $this->siteLangId), 'collection_full_width', 1, array(), true, 0);
        }

        $languageArr = Language::getDropDownList(CommonHelper::getDefaultFormLangId());
        $translatorSubscriptionKey = FatApp::getConfig('CONF_TRANSLATOR_SUBSCRIPTION_KEY', FatUtility::VAR_STRING, '');
        if (!empty($translatorSubscriptionKey) && 0 < count($languageArr)) {
            $frm->addCheckBox(Labels::getLabel('FRM_UPDATE_OTHER_LANGUAGES_DATA', $this->siteLangId), 'auto_update_other_langs_data', 1, array(), false, 0);
        }
        return $frm;
    }

    public function langForm($autoFillLangData = 0)
    {
        $recordId = FatApp::getPostedData('recordId', FatUtility::VAR_INT, 0);
        $langId = FatApp::getPostedData('langId', FatUtility::VAR_INT, 0);

        if (1 > $recordId || 1 > $langId) {
            LibHelper::exitWithError($this->str_invalid_request, true);
        }

        $this->setLangTemplateData();

        $data = Collections::getAttributesById($recordId, ['collection_type', 'collection_layout_type']);

        $langFrm = $this->getLangForm($recordId, $langId, $data['collection_type']);
        if (0 < $autoFillLangData) {
            $updateLangDataobj = new TranslateLangData($this->modelObj::DB_TBL_LANG);
            $translatedData = $updateLangDataobj->getTranslatedData($recordId, $langId, CommonHelper::getDefaultFormLangId());
            if (false === $translatedData) {
                LibHelper::exitWithError($updateLangDataobj->getError(), true);
            }
            $langData = (array) current($translatedData);
        } else {
            $langData = (array) $this->modelObj::getAttributesByLangId($langId, $recordId);
        }

        $langData += $data;
        $langFrm->fill($langData);

        $this->setFormTitle($data['collection_type'], $data['collection_layout_type']);

        $this->set('recordId', $recordId);
        $this->set('lang_id', $langId);
        $this->set('langFrm', $langFrm);
        $this->set('formLayout', Language::getLayoutDirection($langId));

        $this->set('collection_type', $data['collection_type']);
        $this->set('collection_layout_type', $data['collection_layout_type']);
        $this->set('html', $this->_template->render(false, false, NULL, true));
        $this->_template->render(false, false, 'json-success.php', true, false);
    }

    protected function getLangForm($recordId = 0, $langId = 0, $type = 0)
    {
        $recordId = FatUtility::int($recordId);
        $langId = FatUtility::int($langId);
        $langId = 1 > $langId ? $this->siteLangId : $langId;

        if($recordId > 0 && $type == 0){
            $type = Collections::getAttributesById($recordId,'collection_type');
        }

        $frm = new Form('frmCollectionLang');
        $frm->addHiddenField('', 'collection_type');
        $frm->addHiddenField('', 'collection_id', $recordId);
        $frm->addSelectBox(Labels::getLabel('FRM_LANGUAGE', $langId), 'lang_id', Language::getDropDownList(CommonHelper::getDefaultFormLangId()), $langId, array(), '');

        $frm->addRequiredField(Labels::getLabel('FRM_NAME', $langId), 'collection_name');

        if (Collections::COLLECTION_TYPE_CONTENT_BLOCK == $type) {
            $frm->addHtmlEditor(Labels::getLabel('FRM_DESCRIPTION', $this->siteLangId), 'collection_description');
        }

        return $frm;
    }

    public function updateStatus()
    {
        $this->objPrivilege->canEditCollections();
        $recordId = FatApp::getPostedData('recordId', FatUtility::VAR_INT, 0);
        if (0 >= $recordId) {
            LibHelper::exitWithError($this->str_invalid_request_id, true);
        }

        $status = FatApp::getPostedData('status', FatUtility::VAR_INT, -1);

        $this->updateCollectionStatus($recordId, $status);

        FatUtility::dieJsonSuccess(Labels::getLabel('LBL_STATUS_UPDATED', $this->siteLangId));
    }

    public function toggleBulkStatuses()
    {
        $this->objPrivilege->canEditCollections();

        $status = FatApp::getPostedData('status', FatUtility::VAR_INT, -1);
        $recordIdsArr = FatUtility::int(FatApp::getPostedData('collection_ids'));
        if (empty($recordIdsArr) || -1 == $status) {
            LibHelper::exitWithError($this->str_invalid_request, true);
        }

        foreach ($recordIdsArr as $recordId) {
            if (1 > $recordId) {
                continue;
            }

            $this->updateCollectionStatus($recordId, $status);
        }
        $this->set('msg', Labels::getLabel('MSG_STATUS_UPDATED', $this->siteLangId));
        $this->_template->render(false, false, 'json-success.php');
    }

    private function updateCollectionStatus($recordId, $status)
    {
        $status = FatUtility::int($status);
        $recordId = FatUtility::int($recordId);
        if (1 > $recordId || -1 == $status) {
            LibHelper::exitWithError($this->str_invalid_request, true);
        }

        $oldStatus = Collections::getAttributesById($recordId, 'collection_active');
        if ($oldStatus === false) {
            LibHelper::exitWithError($this->str_invalid_request, true);
        }

        if ($oldStatus == $status) {
            return;
        }

        $collectionObj = new Collections($recordId);
        if (!$collectionObj->changeStatus($status)) {
            LibHelper::exitWithError($collectionObj->getError(), true);
        }
    }

    public function recordForm($recordId, $collectionType)
    {
        $recordId = FatUtility::int($recordId);
        $collectionType = FatUtility::int($collectionType);

        $data = Collections::getAttributesById($recordId);
        if (false != $data && ($data['collection_deleted'] == applicationConstants::YES)) {
            LibHelper::exitWithError($this->str_invalid_request_id, true);
        }

        $this->setFormTitle($collectionType, $data['collection_layout_type']);

        $frm = $this->getRecordsForm($recordId, $collectionType);
        if (in_array($data['collection_layout_type'], [Collections::TYPE_CATEGORY_LAYOUT7, Collections::TYPE_CATEGORY_LAYOUT9])) {
            $fld = $frm->getField('collection_records[]');
            $limit = $data['collection_layout_type'] == Collections::TYPE_CATEGORY_LAYOUT7 ? Collections::LIMIT_CATEGORY_LAYOUT7 : Collections::LIMIT_CATEGORY_LAYOUT9;
            $lbl = CommonHelper::replaceStringData(Labels::getLabel('LBL_BIND_ATLEAST_{LIMIT}_RECORDS_FOR_BETTER_COLLECTION_VIEW'), ['{LIMIT}' => $limit]);
            $fld->htmlAfterField = '<span class="form-text text-muted">' . $lbl . '</span>';
        }

        $this->set('recordId', $recordId);
        $this->set('collection_type', $collectionType);
        $this->set('collection_layout_type', $data['collection_layout_type']);
        $this->set('frm', $frm);
        $this->set('displayFooterButtons', false);
        $this->set('html', $this->_template->render(false, false, NULL, true));
        $this->_template->render(false, false, 'json-success.php', true, false);
    }

    private function getRecordsForm($recordId = 0, $collectionType = 0)
    {
        $recordId = FatUtility::int($recordId);
        $collectionType = FatUtility::int($collectionType);

        $frm = new Form('frmCollectionRecords');
        $fld = $frm->addHiddenField('', 'collection_id', $recordId);
        $fld->requirements()->setInt();
        $fld->requirements()->setIntPositive();
        switch ($collectionType) {
            case Collections::COLLECTION_TYPE_PRODUCT:
                $selectedRecords = (array) Collections::getSellProds($recordId, $this->siteLangId);
                $frm->addSelectBox(Labels::getLabel('FRM_SELLER_PRODUCTS', $this->siteLangId), 'collection_records[]', $selectedRecords, array_keys($selectedRecords));
                break;
            case Collections::COLLECTION_TYPE_CATEGORY:
                $selectedRecords = (array) Collections::getCategories($recordId, $this->siteLangId);
                $frm->addSelectBox(Labels::getLabel('FRM_CATEGORIES', $this->siteLangId), 'collection_records[]', $selectedRecords, array_keys($selectedRecords));
                break;
            case Collections::COLLECTION_TYPE_SHOP:
                $selectedRecords = (array) Collections::getShops($recordId, $this->siteLangId);
                $frm->addSelectBox(Labels::getLabel('FRM_SHOPS', $this->siteLangId), 'collection_records[]', $selectedRecords, array_keys($selectedRecords));
                break;
            case Collections::COLLECTION_TYPE_BRAND:
                $selectedRecords = (array) Collections::getBrands($recordId, $this->siteLangId);
                $frm->addSelectBox(Labels::getLabel('FRM_BRANDS', $this->siteLangId), 'collection_records[]', $selectedRecords, array_keys($selectedRecords));
                break;
            case Collections::COLLECTION_TYPE_BLOG:
                $selectedRecords = (array) Collections::getBlogs($recordId, $this->siteLangId);
                $frm->addSelectBox(Labels::getLabel('FRM_BLOGS', $this->siteLangId), 'collection_records[]', $selectedRecords, array_keys($selectedRecords));
                break;
            case Collections::COLLECTION_TYPE_FAQ:
                $selectedRecords = (array) Collections::getFaqs($recordId, $this->siteLangId);
                $frm->addSelectBox(Labels::getLabel('FRM_FAQS', $this->siteLangId), 'collection_records[]', $selectedRecords, array_keys($selectedRecords));
                break;
            case Collections::COLLECTION_TYPE_FAQ_CATEGORY:
                $selectedRecords = (array) Collections::getFaqCategories($recordId, $this->siteLangId);
                $frm->addSelectBox(Labels::getLabel('FRM_FAQ_CATEGORY', $this->siteLangId), 'collection_records[]', $selectedRecords, array_keys($selectedRecords));
                break;
            case Collections::COLLECTION_TYPE_TESTIMONIAL:
                $selectedRecords = (array) Collections::getTestimonials($recordId, $this->siteLangId);
                $frm->addSelectBox(Labels::getLabel('FRM_TESTIMONIALS', $this->siteLangId), 'collection_records[]', $selectedRecords, array_keys($selectedRecords));
                break;
        }
        return $frm;
    }

    public function updateCollectionRecords()
    {
        $this->objPrivilege->canEditCollections();
        $post = FatApp::getPostedData();
        if (false === $post) {
            LibHelper::exitWithError($this->str_invalid_request, true);
        }

        $collection_id = FatUtility::int($post['collection_id']);
        $record_id = FatUtility::int($post['record_id']);
        if (!$collection_id || !$record_id) {
            LibHelper::exitWithError($this->str_invalid_request, true);
        }

        $collectionDetails = Collections::getAttributesById($collection_id);
        if (false != $collectionDetails && ($collectionDetails['collection_active'] != applicationConstants::ACTIVE || $collectionDetails['collection_deleted'] == applicationConstants::YES)) {
            LibHelper::exitWithError($this->str_invalid_request_id, true);
        }

        $collectionObj = new Collections($collection_id);
        if (!$collectionObj->addUpdateCollectionRecord($record_id)) {
            LibHelper::exitWithError($collectionObj->getError(), true);
        }
        $collectionObj->updateRecordDisplayOrder($record_id);

        $this->set('collection_id', $collection_id);
        $this->set('collection_type', $collectionDetails['collection_type']);
        $this->set('msg', Labels::getLabel('MSG_RECORD_UPDATED_SUCCESSFULLY', $this->siteLangId));
        $this->_template->render(false, false, 'json-success.php');
    }

    public function removeCollectionRecord()
    {
        $this->objPrivilege->canEditCollections();
        $post = FatApp::getPostedData();
        if (false === $post) {
            LibHelper::exitWithError($this->str_invalid_request, true);
        }
        $collectionId = FatUtility::int($post['collection_id']);
        $recordId = FatUtility::int($post['record_id']);
        if (1 > $collectionId || 1 > $recordId) {
            LibHelper::exitWithError($this->str_invalid_request, true);
        }

        $collectionDetails = Collections::getAttributesById($collectionId);
        if (false == $collectionDetails) {
            LibHelper::exitWithError($this->str_invalid_request_id, true);
        }

        $collectionObj = new Collections();
        if (!$collectionObj->removeCollectionRecord($collectionId, $recordId)) {
            LibHelper::exitWithError($collectionObj->getError(), true);
        }
        $this->set('msg', Labels::getLabel('MSG_RECORD_REMOVED_SUCCESSFULLY', $this->siteLangId));
        $this->set('collection_type', $collectionDetails['collection_type']);
        $this->_template->render(false, false, 'json-success.php');
    }

    public function media($recordId, $collectionType)
    {
        $recordId = FatUtility::int($recordId);

        $data = Collections::getAttributesById($recordId);
        if (false == $data) {
            LibHelper::exitWithError($this->str_invalid_request_id, true);
        }
        $this->setFormTitle($collectionType, $data['collection_layout_type']);
        [$mediaType, $layoutType] = ($data['collection_layout_type'] != Collections::TYPE_CATEGORY_LAYOUT12) ? [ImageDimension::VIEW_MOBILE, 0] : [ImageDimension::VIEW_DESKTOP, Collections::TYPE_CATEGORY_LAYOUT12];

        $frm = $this->getMediaForm($recordId);
        $this->set('recordId', $recordId);
        $this->set('collection_type', $collectionType);
        $this->set('collection_layout_type', $data['collection_layout_type']);
        $this->set('frm', $frm);
        $this->set('displayFooterButtons', false);
        $this->set('activeGentab', false);
        $this->set('displayMediaOnly', $data['collection_display_media_only']);
        $this->set('imageDimension', ImageDimension::getDisplayCollectionImageData($mediaType));
        $this->set('imageDimensions', ImageDimension::getDisplayCollectionImageData(layout: $layoutType));
        $this->set('html', $this->_template->render(false, false, NULL, true));
        $this->_template->render(false, false, 'json-success.php', true, false);
    }

    public function images($recordId, $langId = 0, $screen = 0)
    {
        $this->checkEditPrivilege(true);
        $languages = Language::getAllNames();
        if (count($languages) <= 1) {
            $langId = array_key_first($languages);
        }

        $recordId = FatUtility::int($recordId);
        if (!$recordId) {
            LibHelper::exitWithError($this->str_invalid_request_id, true);
        }

        if (!$row = Collections::getAttributesById($recordId, ['collection_id', 'collection_layout_type'])) {
            LibHelper::exitWithError($this->str_invalid_request_id, true);
        }

        $images = AttachedFile::getMultipleAttachments(AttachedFile::FILETYPE_COLLECTION_IMAGE, $recordId, 0, $langId, (1 == count($languages)), $screen, 1);
        $this->set('languages', Language::getAllNames());
        $this->set('images', $images);
        $this->set('recordId', $recordId);
        $this->set('collection_layout_type', $row['collection_layout_type']);
        $this->set('html', $this->_template->render(false, false, NULL, true));
        $this->_template->render(false, false, 'json-success.php', true, false);
    }

    private function getMediaForm(int $recordId)
    {
        $frm = new Form('frmCollectionMedia');
        $frm->addHiddenField('', 'collection_id', $recordId);
        $frm->addHiddenField('', 'file_type', AttachedFile::FILETYPE_COLLECTION_IMAGE);
        $frm->addHiddenField('', 'min_width');
        $frm->addHiddenField('', 'min_height');

        
        $data = Collections::getAttributesById($recordId, ['collection_type', 'collection_layout_type']);
        if(in_array($data['collection_layout_type'], Collections::COLLECTION_WITH_MEDIA)) {
            $screenArr = applicationConstants::getDisplaysArr($this->siteLangId);
            $frm->addSelectBox(Labels::getLabel("FRM_DEVICE", $this->siteLangId), 'collection_screen', $screenArr, '', array(), '');
        } else {
            $frm->addCheckBox(Labels::getLabel("FRM_DISPLAY_MEDIA_ONLY", $this->siteLangId), 'collection_display_media_only', 1, array(), false, 0);
        }

        $languagesArr = applicationConstants::getAllLanguages();
        if (count($languagesArr) > 1) {
            $frm->addSelectBox(Labels::getLabel('FRM_LANGUAGE', $this->siteLangId), 'lang_id', $languagesArr, '', array(), '');
        } else {
            $lang_id = array_key_first($languagesArr);
            $frm->addHiddenField('', 'lang_id', $lang_id);
        }

        $frm->addHtml('', 'collection_image', '');
        $frm->addHtml('', 'collection_image_display_div', '');

        return $frm;
    }

    public function uploadMedia()
    {
        $post = FatApp::getPostedData();
        if (empty($post)) {
            LibHelper::exitWithError(Labels::getLabel('ERR_INVALID_REQUEST_OR_FILE_NOT_SUPPORTED', $this->siteLangId), true);
        }
        $collection_id = FatApp::getPostedData('collection_id', FatUtility::VAR_INT, 0);
        $lang_id = FatApp::getPostedData('lang_id', FatUtility::VAR_INT, 0);
        $file_type = FatApp::getPostedData('file_type', FatUtility::VAR_INT, 0);
        $screen = FatApp::getPostedData('collection_screen', FatUtility::VAR_INT, 0);

        if (!$collection_id || !$file_type) {
            LibHelper::exitWithError($this->str_invalid_request, true);
        }

        $collectionType = (0 < $collection_id) ? Collections::getAttributesById($collection_id, 'collection_type') : Collections::COLLECTION_TYPE_PRODUCT;
        if (in_array($collectionType, Collections::COLLECTION_WITHOUT_MEDIA)) {
            LibHelper::exitWithError(Labels::getLabel('ERR_NOT_ALLOWED_TO_UPDATE_MEDIA_FOR_THIS_COLLECTION', $this->siteLangId), true);
        }

        $allowedFileTypeArr = array(AttachedFile::FILETYPE_COLLECTION_IMAGE, AttachedFile::FILETYPE_COLLECTION_BG_IMAGE);

        if (!in_array($file_type, $allowedFileTypeArr)) {
            LibHelper::exitWithError($this->str_invalid_request, true);
        }

        if (!is_uploaded_file($_FILES['cropped_image']['tmp_name'])) {
            LibHelper::exitWithError(Labels::getLabel('ERR_PLEASE_SELECT_A_FILE', $this->siteLangId), true);
        }

        $fileHandlerObj = new AttachedFile();
        if (
            !$res = $fileHandlerObj->saveAttachment(
                $_FILES['cropped_image']['tmp_name'],
                $file_type,
                $collection_id,
                0,
                $_FILES['cropped_image']['name'],
                -1,
                true,
                $lang_id,
                $screen
            )
        ) {
            LibHelper::exitWithError($fileHandlerObj->getError(), true);
        }

        $collection = new Collections($collection_id);
        $collection->addUpdateData(array('collection_updated_on' => date('Y-m-d H:i:s')));

        $this->set('file', $_FILES['cropped_image']['name']);
        $this->set('collection_id', $collection_id);
        $this->set('msg', $_FILES['cropped_image']['name'] . Labels::getLabel('MSG_UPLOADED_SUCCESSFULLY', $this->siteLangId));
        $this->_template->render(false, false, 'json-success.php');
    }

    public function deleteImage($recordId, $afileId, $langId = 0, $slide_screen = 0)
    {
        $this->objPrivilege->canEditBadgesAndRibbons();
        $afileId = FatUtility::int($afileId);
        $recordId = FatUtility::int($recordId);
        $langId = FatUtility::int($langId);
        if (!$afileId) {
            LibHelper::exitWithError($this->str_invalid_request, true);
        }
        $fileType = AttachedFile::FILETYPE_COLLECTION_IMAGE;
        $fileHandlerObj = new AttachedFile();
        if (!$fileHandlerObj->deleteFile($fileType, $recordId, $afileId, 0, $langId, $slide_screen)) {
            LibHelper::exitWithError($fileHandlerObj->getError(), true);
        }

        $collection = new Collections($recordId);
        $collection->addUpdateData(array('collection_updated_on' => date('Y-m-d H:i:s')));

        $this->set('msg', Labels::getLabel('MSG_IMAGE_DELETED_SUCCESSFULLY', $this->siteLangId));
        $this->_template->render(false, false, 'json-success.php');
    }

    public function removeBgImage($collection_id = 0, $lang_id = 0)
    {
        $collection_id = FatUtility::int($collection_id);
        $lang_id = FatUtility::int($lang_id);
        if (1 > $collection_id) {
            LibHelper::exitWithError($this->str_invalid_request, true);
        }

        $fileHandlerObj = new AttachedFile();
        if (!$fileHandlerObj->deleteFile(AttachedFile::FILETYPE_COLLECTION_BG_IMAGE, $collection_id, 0, 0, $lang_id)) {
            LibHelper::exitWithError($fileHandlerObj->getError(), true);
        }

        $this->set('msg', Labels::getLabel('MSG_DELETED_SUCCESSFULLY', $this->siteLangId));
        $this->_template->render(false, false, 'json-success.php');
    }

    public function displayMediaOnly($recordId, $value = 0)
    {
        $recordId = FatUtility::int($recordId);
        if (1 > $recordId) {
            LibHelper::exitWithError($this->str_invalid_request, true);
        }
        $collectionType = (0 < $recordId) ? Collections::getAttributesById($recordId, 'collection_type') : Collections::COLLECTION_TYPE_PRODUCT;
        if (in_array($collectionType, Collections::COLLECTION_WITHOUT_MEDIA)) {
            LibHelper::exitWithError(Labels::getLabel('ERR_NOT_ALLOWED_TO_UPDATE_MEDIA_FOR_THIS_COLLECTION', $this->siteLangId), true);
        }

        $collectionObj = new Collections($recordId);
        $collectionObj->addUpdateData(array('collection_display_media_only' => $value));
        $this->set('msg', Labels::getLabel('MSG_UPDATED_SUCCESSFULLY', $this->siteLangId));
        $this->_template->render(false, false, 'json-success.php');
    }

    private function saveBannerLocation($post)
    {
        $siteDefaultLangId = CommonHelper::getDefaultFormLangId();
        $blocationId = 0;
        $bannerLocation = BannerLocation::getDataByCollectionId($post['collection_id'], 'blocation_id');
        if (!empty($bannerLocation)) {
            $blocationId = $bannerLocation['blocation_id'];
        }

        if ($post['collection_layout_type'] == Collections::TYPE_BANNER_LAYOUT2) {
            $displayItemsCount = $post['collection_primary_records'];
        } else {
            $displayItemsCount = Collections::getBannersCount()[$post['collection_layout_type']];
        }

        $dataToSave = [
            'blocation_identifier' => $post['collection_name'],
            'blocation_collection_id' => $post['collection_id'],
            'blocation_banner_count' => $displayItemsCount,
            'blocation_promotion_cost' => $post['blocation_promotion_cost'] ?? 0,
            'blocation_active' => applicationConstants::ACTIVE
        ];

        $bannerLoc = new BannerLocation($blocationId);
        $bannerLoc->assignValues($dataToSave);
        if (!$bannerLoc->save()) {
            LibHelper::exitWithError($bannerLoc->getError(), true);
        }

        $blocationId = $bannerLoc->getMainTableRecordId();
        $autoUpdateOtherLangsData = FatApp::getPostedData('auto_update_other_langs_data', FatUtility::VAR_INT, 0);

        $bannerLoc = new BannerLocation($blocationId);
        $bannerLoc->saveLangData($siteDefaultLangId, $post['collection_name']); // For site default language
        $name = $post['collection_name'];
        if (empty($name) && $autoUpdateOtherLangsData > 0) {
            $bannerLoc->saveTranslatedLangData($this->siteLangId);
        } elseif (!empty($name)) {
            $bannerLoc->saveLangData($this->siteLangId, $name);
        }

        $bannerDimensions = Collections::getBannersDimensions();
        foreach (($bannerDimensions[$post['collection_layout_type']] ?? []) as $key => $val) {
            $dataToSave = [
                'bldimension_blocation_id' => $blocationId,
                'bldimension_device_type' => $key,
                'blocation_banner_width' => $val['width'],
                'blocation_banner_height' => $val['height']
            ];
            if (!FatApp::getDb()->insertFromArray(BannerLocation::DB_DIMENSIONS_TBL, $dataToSave, false, array(), $dataToSave)) {
                LibHelper::exitWithError(Labels::getLabel('LBL_UNABLE_TO_SAVE_BANNER_DIMENSIONS', $this->siteLangId), true);
            }
        }
    }

    public function searchBanners(int $collectionId)
    {
        $this->bannersTab = true;
        $this->objPrivilege->canViewBanners();

        $collectionId = FatUtility::int($collectionId);

        $collectionDetails = Collections::getAttributesById($collectionId);
        if (false != $collectionDetails && ($collectionDetails['collection_active'] != applicationConstants::ACTIVE || $collectionDetails['collection_deleted'] == applicationConstants::YES)) {
            LibHelper::exitWithError($this->str_invalid_request_id, true);
        }

        $type = $collectionDetails['collection_type'];
        $layoutType = $collectionDetails['collection_layout_type'];
        $this->setFormTitle($type, $layoutType, 'collectionForm(' . $type . ', ' . $layoutType . ', ' . $collectionId . ');');

        $records = Collections::getBanners($collectionId, $this->siteLangId);

        $this->set('collectionId', $collectionId);
        $this->set('arrListing', $records);
        $this->set('collection_type', $type);
        $this->set('collection_layout_type', $layoutType);
        $this->set('canEdit', $this->objPrivilege->canEditBanners());
        $this->set('linkTargetsArr', applicationConstants::getLinkTargetsArr($this->siteLangId));
        $this->set('html', $this->_template->render(false, false, NULL, true));
        $this->_template->render(false, false, 'json-success.php', true, false);
    }

    private function getDisplayScreenName()
    {
        $screenTypesArr = applicationConstants::getDisplaysArr($this->siteLangId);
        return array(0 => '') + $screenTypesArr;
    }

    public function bannerForm($collectionId, $recordId = 0)
    {
        $this->bannersTab = true;
        $collectionId = FatUtility::int($collectionId);
        $recordId = FatUtility::int($recordId);

        $collectionDetails = Collections::getAttributesById($collectionId);
        if (false != $collectionDetails && ($collectionDetails['collection_active'] != applicationConstants::ACTIVE || $collectionDetails['collection_deleted'] == applicationConstants::YES)) {
            LibHelper::exitWithError($this->str_invalid_request_id, true);
        }

        $bannerLocation = BannerLocation::getDataByCollectionId($collectionId);
        $blocationId = $bannerLocation['blocation_id'];

        $frm = $this->getBannerForm($collectionId, $recordId, $blocationId);

        if (0 < $recordId) {
            $attr = [
                'banner_id',
                'banner_url',
                'banner_target',
                'banner_blocation_id',
                'banner_title',
            ];
            $data = Banner::getAttributesByLangId($this->siteLangId, $recordId, $attr, applicationConstants::JOIN_RIGHT);
            $frm->fill($data);
        }

        $type = $collectionDetails['collection_type'];
        $layoutType = $collectionDetails['collection_layout_type'];
        $this->setFormTitle($type, $layoutType, 'collectionForm(' . $type . ', ' . $layoutType . ', ' . $collectionId . ');');

        $this->set('collectionId', $collectionId);
        $this->set('recordId', $recordId);
        $this->set('collection_type', $type);
        $this->set('collection_layout_type', $layoutType);
        $this->set('frm', $frm);
        $this->set('html', $this->_template->render(false, false, NULL, true));
        $this->_template->render(false, false, 'json-success.php', true, false);
    }

    private function getBannerForm(int $collectionId = 0, int $recordId = 0, int $bannerLocationId = 0)
    {
        $frm = new Form('frmBanner');
        $frm->addHiddenField('', 'collection_id', $collectionId);
        $frm->addHiddenField('', 'banner_id', $recordId);
        $frm->addHiddenField('', 'banner_blocation_id', $bannerLocationId);

        $frm->addRequiredField(Labels::getLabel('FRM_BANNER_TITLE', $this->siteLangId), 'banner_title');
        $linkTargetsArr = applicationConstants::getLinkTargetsArr($this->siteLangId);
        $frm->addSelectBox(Labels::getLabel('FRM_OPEN_IN', $this->siteLangId), 'banner_target', $linkTargetsArr, '', array(), '');

        $frm->addRequiredField(Labels::getLabel('FRM_URL', $this->siteLangId), 'banner_url');

        $languageArr = Language::getDropDownList(CommonHelper::getDefaultFormLangId());
        $translatorSubscriptionKey = FatApp::getConfig('CONF_TRANSLATOR_SUBSCRIPTION_KEY', FatUtility::VAR_STRING, '');
        if (!empty($translatorSubscriptionKey) && 0 < count($languageArr)) {
            $frm->addCheckBox(Labels::getLabel('FRM_UPDATE_OTHER_LANGUAGES_DATA', $this->siteLangId), 'auto_update_other_langs_data', 1, array(), false, 0);
        }

        return $frm;
    }

    public function setupBanner()
    {
        $this->objPrivilege->canEditBanners();

        $frm = $this->getBannerForm();
        $post = $frm->getFormDataFromArray(FatApp::getPostedData());

        if (false === $post) {
            LibHelper::exitWithError(current($frm->getValidationErrors()), true);
        }

        $collectionId = $post['collection_id'];
        $bannerId = $post['banner_id'];

        $collectionDetails = Collections::getAttributesById($collectionId);
        if (false != $collectionDetails && ($collectionDetails['collection_active'] != applicationConstants::ACTIVE || $collectionDetails['collection_deleted'] == applicationConstants::YES)) {
            LibHelper::exitWithError($this->str_invalid_request_id, true);
        }

        $bannerLocation = BannerLocation::getDataByCollectionId($collectionId);
        $post['banner_blocation_id'] = $bannerLocation['blocation_id'];

        $post['banner_type'] = Banner::TYPE_BANNER;
        $post['banner_active'] = applicationConstants::ACTIVE;

        $record = new Banner($bannerId);
        $record->assignValues($post);

        if (!$record->save()) {
            LibHelper::exitWithError($record->getError(), true);
        }

        $bannerId = $record->getMainTableRecordId();

        $bannerObj = new Banner($bannerId);
        if (!$bannerObj->updateLangData(CommonHelper::getDefaultFormLangId(), ['banner_title' => $post['banner_title']])) {
            LibHelper::exitWithError($bannerObj->getError(), true);
        }

        $autoUpdateOtherLangsData = FatApp::getPostedData('auto_update_other_langs_data', FatUtility::VAR_INT, 0);
        if (0 < $autoUpdateOtherLangsData) {
            $updateLangDataobj = new TranslateLangData(Banner::DB_TBL_LANG);
            if (false === $updateLangDataobj->updateTranslatedData($bannerId, CommonHelper::getDefaultFormLangId())) {
                LibHelper::exitWithError($updateLangDataobj->getError(), true);
            }
        }

        $collectionObj = new Collections($collectionId);
        if (!$collectionObj->addUpdateCollectionRecord($bannerId)) {
            LibHelper::exitWithError($collectionObj->getError(), true);
        }

        $newTabLangId = 0;
        $languages = Language::getDropDownList(CommonHelper::getDefaultFormLangId());
        if (0 < count($languages)) {
            foreach ($languages as $languageId => $langName) {
                if (!Banner::getAttributesByLangId($languageId, $bannerId)) {
                    $newTabLangId = $languageId;
                    break;
                }
            }
        }

        if (1 > $newTabLangId) {
            $this->set('openMediaForm', true);
        }

        $this->set('msg', Labels::getLabel('MSG_SETUP_SUCCESSFUL', $this->siteLangId));
        $this->set('langId', $newTabLangId);
        $this->set('collectionId', $collectionId);
        $this->set('bannerId', $bannerId);
        $this->_template->render(false, false, 'json-success.php');
    }

    public function bannerLangForm($autoFillLangData = 0)
    {
        $this->bannersTab = true;
        $collectionId = FatApp::getPostedData('collection_id', FatUtility::VAR_INT, 0);
        $recordId = FatApp::getPostedData('banner_id', FatUtility::VAR_INT, 0);
        $langId = FatApp::getPostedData('langId', FatUtility::VAR_INT, 0);

        if (1 > $recordId || 1 > $langId) {
            LibHelper::exitWithError($this->str_invalid_request, true);
        }

        $langFrm = $this->getBannerLangForm($collectionId, $recordId, $langId);
        if (0 < $autoFillLangData) {
            $updateLangDataobj = new TranslateLangData(Banner::DB_TBL_LANG);
            $translatedData = $updateLangDataobj->getTranslatedData($recordId, $langId, CommonHelper::getDefaultFormLangId());
            if (false === $translatedData) {
                LibHelper::exitWithError($updateLangDataobj->getError(), true);
            }
            $langData = current($translatedData);
        } else {
            $langData = Banner::getAttributesByLangId($langId, $recordId, ['banner_title'], applicationConstants::JOIN_RIGHT);
        }

        if ($langData) {
            $langFrm->fill($langData);
        }
        $data = Collections::getAttributesById($collectionId, ['collection_type', 'collection_layout_type']);

        $type = $data['collection_type'];
        $layoutType = $data['collection_layout_type'];
        $this->setFormTitle($type, $layoutType, 'collectionForm(' . $type . ', ' . $layoutType . ', ' . $collectionId . ');');

        $this->set('collectionId', $collectionId);
        $this->set('recordId', $recordId);
        $this->set('lang_id', $langId);
        $this->set('langFrm', $langFrm);
        $this->set('formLayout', Language::getLayoutDirection($langId));

        $this->set('collection_type', $type);
        $this->set('collection_layout_type', $layoutType);
        $this->set('html', $this->_template->render(false, false, NULL, true));
        $this->_template->render(false, false, 'json-success.php', true, false);
    }

    protected function getBannerLangForm(int $collectionId = 0, int $recordId = 0, int $langId = 0)
    {
        $langId = FatUtility::int($langId);
        $langId = 1 > $langId ? $this->siteLangId : $langId;

        $frm = new Form('frmCollectionLang');
        $frm->addHiddenField('', 'collection_id', $collectionId);
        $frm->addHiddenField('', 'bannerlang_banner_id', $recordId);
        $frm->addSelectBox(Labels::getLabel('FRM_LANGUAGE', $langId), 'bannerlang_lang_id', Language::getDropDownList(CommonHelper::getDefaultFormLangId()), $langId, array(), '');

        $frm->addRequiredField(Labels::getLabel('FRM_TITLE', $langId), 'banner_title');

        return $frm;
    }

    public function bannerLangSetup()
    {
        $collectionId = FatApp::getPostedData('collection_id', FatUtility::VAR_INT, 0);
        $recordId = FatApp::getPostedData('bannerlang_banner_id', FatUtility::VAR_INT, 0);
        $langId = FatApp::getPostedData('bannerlang_lang_id', FatUtility::VAR_INT, 0);

        if (1 > $recordId || 1 > $langId) {
            LibHelper::exitWithError($this->str_invalid_request_id, true);
        }

        $frm = $this->getBannerLangForm($collectionId, $recordId, $langId);
        $post = $frm->getFormDataFromArray(FatApp::getPostedData());
        if (false === $post) {
            LibHelper::exitWithError(current($frm->getValidationErrors()), true);
        }
        unset($post['collection_id']);
        $bannerObj = new Banner($recordId);
        $this->setLangData($bannerObj, $post, $langId);
        $this->set('collectionId', $collectionId);
        if (1 > $this->newTabLangId) {
            $this->set('openMediaForm', true);
        }
        $this->_template->render(false, false, 'json-success.php');
    }

    public function bannerMedia($collectionId, $recordId = 0)
    {
        $this->bannersTab = true;
        $collectionId = FatUtility::int($collectionId);
        $recordId = FatUtility::int($recordId);

        $this->collectionDetails = Collections::getAttributesById($collectionId);
        if (false != $this->collectionDetails && ($this->collectionDetails['collection_active'] != applicationConstants::ACTIVE || $this->collectionDetails['collection_deleted'] == applicationConstants::YES)) {
            LibHelper::exitWithError($this->str_invalid_request_id, true);
        }
        $type = $this->collectionDetails['collection_type'];
        $layoutType = $this->collectionDetails['collection_layout_type'];
        $this->setFormTitle($type, $layoutType, 'collectionForm(' . $type . ', ' . $layoutType . ', ' . $collectionId . ');');
        $bannerDimensiomns = ImageDimension::getBannerData('', $layoutType);
        $frm = $this->getBannerMediaForm($recordId);
        $frm->fill(['collection_id' => $collectionId]);
        $this->set('bannerDimensiomns', $bannerDimensiomns);
        $this->set('collectionId', $collectionId);
        $this->set('recordId', $recordId);
        $this->set('collection_type', $type);
        $this->set('collection_layout_type', $layoutType);
        $this->set('frm', $frm);
        $this->set('displayFooterButtons', false);
        $this->set('activeGentab', false);
        $this->set('html', $this->_template->render(false, false, NULL, true));
        $this->_template->render(false, false, 'json-success.php', true, false);
    }

    private function getBannerMediaForm(int $recordId)
    {
        $frm = new Form('frmCollectionMedia');
        $frm->addHiddenField('', 'collection_id');
        $frm->addHiddenField('', 'banner_id', $recordId);
        $frm->addHiddenField('', 'file_type', AttachedFile::FILETYPE_BANNER);
        $frm->addHiddenField('', 'min_width');
        $frm->addHiddenField('', 'min_height');

        $languagesArr = applicationConstants::getAllLanguages();
        if (count($languagesArr) > 1) {
            $frm->addSelectBox(Labels::getLabel('FRM_LANGUAGE', $this->siteLangId), 'lang_id', $languagesArr, '', array(), '');
        } else {
            $lang_id = array_key_first($languagesArr);
            $frm->addHiddenField('', 'lang_id', $lang_id);
        }

        $screenArr = applicationConstants::getDisplaysArr($this->siteLangId);

        if (in_array($this->collectionDetails['collection_layout_type'], Collections::COLLECTIONS_FOR_APP_ONLY)) {
            unset($screenArr[applicationConstants::SCREEN_DESKTOP]);
        }

        $frm->addSelectBox(Labels::getLabel("FRM_DEVICE", $this->siteLangId), 'banner_screen', $screenArr, '', array(), '');
        $frm->addHtml('', 'banner', '');

        return $frm;
    }

    public function setupBannerImage()
    {
        $this->objPrivilege->canEditProductCategories();
        $collection_id = FatApp::getPostedData('collection_id', FatUtility::VAR_INT, 0);
        $banner_id = FatApp::getPostedData('banner_id', FatUtility::VAR_INT, 0);
        $lang_id = FatApp::getPostedData('lang_id', FatUtility::VAR_INT, 0);
        $slide_screen = FatApp::getPostedData('banner_screen', FatUtility::VAR_INT, 0);
        $afileId = FatApp::getPostedData('afile_id', FatUtility::VAR_INT, 0);

        if (!is_uploaded_file($_FILES['cropped_image']['tmp_name'])) {
            LibHelper::exitWithError(Labels::getLabel('ERR_PLEASE_SELECT_A_FILE', $this->siteLangId), true);
        }

        $file_type = AttachedFile::FILETYPE_BANNER;
        Banner::deleteImagesWithoutBannerId($file_type);

        $fileHandlerObj = new AttachedFile($afileId);
        if (
            !$res = $fileHandlerObj->saveImage(
                $_FILES['cropped_image']['tmp_name'],
                $file_type,
                $banner_id,
                0,
                $_FILES['cropped_image']['name'],
                -1,
                true,
                $lang_id,
                $_FILES['cropped_image']['type'],
                $slide_screen
            )
        ) {
            LibHelper::exitWithError($fileHandlerObj->getError(), true);
        }
        Banner::setLastModified($banner_id);
        $this->set('file', $_FILES['cropped_image']['name']);
        $this->set('collection_id', $collection_id);
        $this->set('banner_id', $banner_id);
        $this->set('lang_id', $lang_id);
        $this->set('slide_screen', $slide_screen);
        $this->set('msg', $_FILES['cropped_image']['name'] . ' ' . Labels::getLabel('MSG_UPLOADED_SUCCESSFULLY', $this->siteLangId));
        $this->_template->render(false, false, 'json-success.php');
    }

    public function bannerImages($collectionId, $recordId, $lang_id = 0, $screen = 0)
    {
        $this->checkEditPrivilege(true);
        $collectionId = FatUtility::int($collectionId);
        $recordId = FatUtility::int($recordId);

        if (1 > $collectionId) {
            LibHelper::exitWithError($this->str_invalid_request, true);
        }
        $languages = Language::getAllNames();
        if (count($languages) <= 1) {
            $lang_id = array_key_first($languages);
        }

        $collectionDetails = Collections::getAttributesById($collectionId);
        if (false != $collectionDetails && ($collectionDetails['collection_active'] != applicationConstants::ACTIVE || $collectionDetails['collection_deleted'] == applicationConstants::YES)) {
            LibHelper::exitWithError($this->str_invalid_request_id, true);
        }



        $images = AttachedFile::getMultipleAttachments(AttachedFile::FILETYPE_BANNER, $recordId, 0, $lang_id, (1 == count($languages)), $screen, 1);
        $this->set('collection_layout_type', $collectionDetails['collection_layout_type']);
        $this->set('images', $images);
        $this->set('languages', Language::getAllNames());
        $this->set('screenTypeArr', $this->getDisplayScreenName());
        $this->set('recordId', $recordId);
        $this->set('collectionId', $collectionId);
        $this->set('html', $this->_template->render(false, false, NULL, true));
        $this->_template->render(false, false, 'json-success.php', true, false);
    }

    public function removeBanner($bannerId, $afileId, $langId = 0, $slide_screen = 0)
    {
        $this->objPrivilege->canEditProductCategories();
        $afileId = FatUtility::int($afileId);
        $bannerId = FatUtility::int($bannerId);
        $langId = FatUtility::int($langId);
        if (!$afileId) {
            LibHelper::exitWithError($this->str_invalid_request, true);
        }
        $fileType = AttachedFile::FILETYPE_BANNER;
        $fileHandlerObj = new AttachedFile();
        if (!$fileHandlerObj->deleteFile($fileType, $bannerId, $afileId, 0, $langId, $slide_screen)) {
            LibHelper::exitWithError($fileHandlerObj->getError(), true);
        }

        $this->set('msg', Labels::getLabel('MSG_IMAGE_DELETED_SUCCESSFULLY', $this->siteLangId));
        $this->_template->render(false, false, 'json-success.php');
    }

    public function deleteRecord()
    {
        $this->objPrivilege->canEditCollections();

        $collection_id = FatApp::getPostedData('recordId', FatUtility::VAR_INT, 0);
        if ($collection_id < 1) {
            LibHelper::exitWithError($this->str_invalid_request_id, true);
        }
        $this->markAsDeleted($collection_id);

        FatUtility::dieJsonSuccess($this->str_delete_record);
    }

    public function deleteSelected()
    {
        $this->objPrivilege->canEditCollections();
        $recordIdsArr = FatUtility::int(FatApp::getPostedData('collection_ids'));

        if (empty($recordIdsArr)) {
            LibHelper::exitWithError($this->str_invalid_request, true);
        }

        foreach ($recordIdsArr as $collection_id) {
            if (1 > $collection_id) {
                continue;
            }
            $this->markAsDeleted($collection_id);
        }
        $this->set('msg', Labels::getLabel('MSG_RECORDS_DELETED_SUCCESSFULLY', $this->siteLangId));
        $this->_template->render(false, false, 'json-success.php');
    }

    protected function markAsDeleted($collection_id)
    {
        $collection_id = FatUtility::int($collection_id);
        if (1 > $collection_id) {
            LibHelper::exitWithError($this->str_invalid_request, true);
        }
        $collectionObj = new Collections($collection_id);
        if (!$row = Collections::getAttributesById($collection_id)) {
            LibHelper::exitWithError($this->str_invalid_request_id, true);
        }

        $collectionObj->assignValues(array(Collections::tblFld('deleted') => 1, 'collection_identifier' => $row['collection_identifier'] . " {del}$collection_id"));
        if (!$collectionObj->save()) {
            LibHelper::exitWithError($collectionObj->getError(), true);
        }
    }

    public function updateOrder()
    {
        $this->objPrivilege->canEditCollections();

        $post = FatApp::getPostedData();
        if (!empty($post)) {
            $collectionObj = new Collections();
            if (!$collectionObj->updateOrder($post['collectionList'])) {
                LibHelper::exitWithError($collectionObj->getError(), true);
            }
            FatUtility::dieJsonSuccess(Labels::getLabel('MSG_ORDER_UPDATED_SUCCESSFULLY', $this->siteLangId));
        }
    }

    protected function getFormColumns(): array
    {
        $tblHeadingCols = CacheHelper::get('collectionsTblHeadingCols' . $this->siteLangId, CONF_DEF_CACHE_TIME, '.txt');
        if ($tblHeadingCols) {
            return json_decode($tblHeadingCols, true);
        }

        $arr = [
            'dragdrop' => '',
            'select_all' => Labels::getLabel('LBL_SELECT_ALL', $this->siteLangId),
            'collection_display_order' => Labels::getLabel('LBL_DISPLAY_ORDER', $this->siteLangId),
            'collection_name' => Labels::getLabel('LBL_COLLECTION_NAME', $this->siteLangId),
            'applicable_for' => Labels::getLabel('LBL_APPLICABLE_FOR', $this->siteLangId),
            'collection_type' => Labels::getLabel('LBL_TYPE', $this->siteLangId),
            'collection_layout_type' => Labels::getLabel('LBL_LAYOUT_TYPE', $this->siteLangId),
            'collection_active' => Labels::getLabel('LBL_STATUS', $this->siteLangId),
            'action' => Labels::getLabel('LBL_ACTION_BUTTONS', $this->siteLangId),
        ];
        CacheHelper::create('collectionsTblHeadingCols' . $this->siteLangId, json_encode($arr), CacheHelper::TYPE_LABELS);
        return $arr;
    }

    protected function getDefaultColumns(): array
    {
        return [
            'dragdrop',
            'select_all',
            'collection_display_order',
            'collection_name',
            'applicable_for',
            'collection_type',
            'collection_layout_type',
            'collection_active',
            'action',
        ];
    }

    protected function excludeKeysForSort($fields = []): array
    {
        return array_diff($fields, ['dragdrop', 'collection_layout_type', 'applicable_for'], Common::excludeKeysForSort());
    }
}