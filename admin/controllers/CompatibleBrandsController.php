<?php

class CompatibleBrandsController extends ListingBaseController
{

    protected string $modelClass = 'CompatibleBrand';
    protected $pageKey = 'MANAGE_COMPATIBLE_BRANDS';

    public function __construct($action)
    {
        parent::__construct($action);
        $this->objPrivilege->canViewBrands();
        $this->rewriteUrl = CompatibleBrand::REWRITE_URL_PREFIX;
    }

    public function index()
    {
        $fields = $this->getFormColumns();
        $frmSearch = $this->getSearchForm($fields);
        $pageData = PageLanguageData::getAttributesByKey($this->pageKey, $this->siteLangId);
        $pageTitle = $pageData['plang_title'] ?? LibHelper::getControllerName(true);

        $this->setModel();
        $actionItemsData = HtmlHelper::getDefaultActionItems($fields, $this->modelObj);
        $actionItemsData['deleteButton'] = true;

        $this->set('pageData', $pageData);
        $this->set('pageTitle', $pageTitle);
        $this->set('actionItemsData', $actionItemsData);
        $this->set('canEdit', $this->objPrivilege->canEditBrands($this->admin_id, true));
        $this->set("frmSearch", $frmSearch);
        $this->getListingData();

        $this->_template->addCss('css/cropper.css');
        $this->_template->addJs(['js/cropper.js', 'js/cropper-main.js', 'brands/page-js/index.js']);
        $this->includeFeatherLightJsCss();

        $this->set('keywordPlaceholder', Labels::getLabel('FRM_SEARCH_BY_NAME', $this->siteLangId));
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
            $jsonData['listingHtml'] =  $this->_template->render(false, false, 'compatible-brands/search.php', true);
        }

        LibHelper::exitWithSuccess($jsonData, true);
    }

    private function getListingData($loadPagination = 0)
    {
        $pageSize = applicationConstants::getPageSize(FatApp::getPostedData('pageSize', FatUtility::VAR_INT));

        $data = FatApp::getPostedData();

        $fields = $this->getFormColumns();
        $selectedFlds = FatApp::getPostedData('reportColumns', FatUtility::VAR_STRING, '');
        $selectedFlds = !empty($selectedFlds) ? json_decode($selectedFlds) + $this->getDefaultColumns() : $this->getDefaultColumns();

        $fields = FilterHelper::parseArrayByKeys($fields, $selectedFlds, true);
        $allowedKeysForSorting = $this->excludeKeysForSort(array_keys($fields));
        $sortBy = FatApp::getPostedData('sortBy', FatUtility::VAR_STRING, 'cbrand_id');
        if (!array_key_exists($sortBy, $fields)) {
            $sortBy = 'cbrand_id';
        }

        $sortOrder = applicationConstants::getSortOrder(FatApp::getPostedData('sortOrder', FatUtility::VAR_STRING, applicationConstants::SORT_DESC), applicationConstants::SORT_DESC);

        $searchForm = $this->getSearchForm($fields);

        $page = (empty($data['page']) || $data['page'] <= 0) ? 1 : $data['page'];
        $post = $searchForm->getFormDataFromArray($data);

        $prodBrandObj = new CompatibleBrand();
        $srch = $prodBrandObj->getSearchObject($this->siteLangId, true, false, false);
        if (isset($post['keyword']) && '' != $post['keyword']) {
            $condition = $srch->addCondition('b.cbrand_identifier', 'like', '%' . $post['keyword'] . '%');
            $condition->attachCondition('b_l.cbrand_name', 'like', '%' . $post['keyword'] . '%', 'OR');
        }

        $srch->addCondition('cbrand_status', '=', 'mysql_func_' . CompatibleBrand::BRAND_REQUEST_APPROVED, 'AND', true);
        if (!empty($post['cbrand_id'])) {
            $srch->addCondition('b.cbrand_id', '=', 'mysql_func_' . $post['cbrand_id'], 'AND', true);
        }

        if ($loadPagination && FatUtility::isAjaxCall()) {
            $this->setRecordCount(clone $srch, $pageSize, $page, $post);
        }
        $srch->doNotCalculateRecords();

        $srch->addMultipleFields(array('b.*', "b_l.cbrand_name"));
        $page = (empty($page) || $page <= 0) ? 1 : $page;
        $page = FatUtility::int($page);
        $srch->setPageNumber($page);
        $srch->setPageSize($pageSize);
        $srch->addOrder($sortBy, $sortOrder);
        $rs = $srch->getResultSet();

        $records = [];
        if (!$loadPagination) {
            $records = FatApp::getDb()->fetchAll($rs);
        }
        $this->set("arrListing", $records);
        $this->set('postedData', $post);
        $this->set('sortBy', $sortBy);
        $this->set('sortOrder', $sortOrder);
        $this->set('fields', $fields);
        $this->set('allowedKeysForSorting', $allowedKeysForSorting);
        $this->set('canEdit', $this->objPrivilege->canEditBrands($this->admin_id, true));
    }

    public function form()
    {
        $this->objPrivilege->canEditBrands();

        $recordId = FatApp::getPostedData('recordId', FatUtility::VAR_INT, 0);
        $frm = $this->getForm($recordId);

        if (0 < $recordId) {
            $data = CompatibleBrand::getAttributesByLangId(CommonHelper::getDefaultFormLangId(), $recordId, array('cbrand_id', 'cbrand_active', 'cbrand_identifier', 'IFNULL(cbrand_name,cbrand_identifier) as cbrand_name'), applicationConstants::JOIN_RIGHT);
            if ($data === false) {
                LibHelper::exitWithError($this->str_invalid_request, true);
            }
            $frm->fill($data);
        }

        HtmlHelper::addIdentierToFrm($frm->getField($this->modelClass::tblFld('name')), ($data[$this->modelClass::tblFld('identifier')] ?? ''));

        $this->set('recordId', $recordId);
        $this->set('frm', $frm);
        $this->set('html', $this->_template->render(false, false, NULL, true));
        $this->_template->render(false, false, 'json-success.php', true, false);
    }

    protected function getSearchForm(array $fields = [])
    {
        $fields = $this->getFormColumns();
        $frm = new Form('frmRecordSearch');
        $frm->addHiddenField('', 'page');
        $fld = $frm->addTextBox(Labels::getLabel('FRM_KEYWORD', $this->siteLangId), 'keyword');
        $fld->overrideFldType('search');

        if (!empty($fields)) {
            $this->addSortingElements($frm, 'cbrand_id', applicationConstants::SORT_DESC);
        }
        $frm->addHiddenField('', 'total_record_count');
        HtmlHelper::addSearchButton($frm);
        return $frm;
    }

    public function setup()
    {
        $this->objPrivilege->canEditBrands();

        $frm = $this->getForm();
        $post = $frm->getFormDataFromArray(FatApp::getPostedData());

        if (false === $post) {
            LibHelper::exitWithError(current($frm->getValidationErrors()), true);
        }

        $recordId = $post['cbrand_id'];
        unset($post['cbrand_id']);
        $data = $post;

        $data['cbrand_identifier'] = $data['cbrand_name'];

        if ($recordId == 0) {
            $record = CompatibleBrand::getAttributesByIdentifier($data['cbrand_identifier']);
            if (!empty($record) /*&& $record['cbrand_deleted'] == applicationConstants::YES*/) {
                $recordId = $record['cbrand_id'];
                $data['cbrand_deleted'] = applicationConstants::NO;
            }
        }

        $data['cbrand_status'] = CompatibleBrand::BRAND_REQUEST_APPROVED;

        $brand = new CompatibleBrand($recordId);
        $brand->assignValues($data);
        if (!$brand->save()) {
            $msg = $brand->getError();
            if (false !== strpos(strtolower($msg), 'duplicate')) {
                $msg = Labels::getLabel('ERR_DUPLICATE_RECORD_NAME', $this->siteLangId);
            }
            LibHelper::exitWithError($msg, true);
        }

        $recordId = $brand->getMainTableRecordId();

        if (!$brand->updateLangData(CommonHelper::getDefaultFormLangId(), ['cbrand_name' => $data['cbrand_name']])) {
            LibHelper::exitWithError($brand->getError(), true);
        }

        $autoUpdateOtherLangsData = FatApp::getPostedData('auto_update_other_langs_data', FatUtility::VAR_INT, 0);
        if (0 < $autoUpdateOtherLangsData) {
            $updateLangDataobj = new TranslateLangData(CompatibleBrand::DB_TBL_LANG);
            if (false === $updateLangDataobj->updateTranslatedData($recordId, CommonHelper::getDefaultFormLangId())) {
                LibHelper::exitWithError($updateLangDataobj->getError(), true);
            }
        }

        /* url data[ */
        if(isset($post['urlrewrite_custom'])) {
            $brandOriginalUrl = $this->rewriteUrl . $recordId;
            if ($post['urlrewrite_custom'] == '') {
                UrlRewrite::remove($brandOriginalUrl);
            } else {
                $brand->rewriteUrl($post['urlrewrite_custom']);
            }
        }
        /* ] */

        $newTabLangId = 0;
        $languages = Language::getDropDownList(CommonHelper::getDefaultFormLangId());
        if (0 < count($languages)) {
            foreach ($languages as $langId => $langName) {
                if (!CompatibleBrand::getAttributesByLangId($langId, $recordId)) {
                    $newTabLangId = $langId;
                    break;
                }
            }
        }

        if ($newTabLangId == 0 && !$this->isMediaUploaded($recordId)) {
          //  $this->set('openMediaForm', true);
        }

        Product::updateMinPrices(0, 0, $recordId);
        $this->set('msg', $this->str_setup_successful);
        $this->set('recordId', $recordId);
        $this->set('langId', $newTabLangId);
        $this->_template->render(false, false, 'json-success.php');
    }

    private function getForm($recordId = 0)
    {
        $this->objPrivilege->canEditBrands();
        $recordId = FatUtility::int($recordId);

        $frm = new Form('frmProdcBrand', array('id' => 'frmProdcBrand'));
        $frm->addHiddenField('', 'cbrand_id', $recordId);
        $frm->addRequiredField(Labels::getLabel('FRM_COMPATIBLE_BRAND_NAME', $this->siteLangId), 'cbrand_name');
        //$frm->addRequiredField(Labels::getLabel('FRM_Brand_Identifier', $this->siteLangId), 'brand_identifier');
        //$fld = $frm->addTextBox(Labels::getLabel('FRM_COMPATIBLE_BRAND_SEO_FRIENDLY_URL', $this->siteLangId), 'urlrewrite_custom');
        //$fld->requirements()->setRequired();

        $frm->addCheckBox(Labels::getLabel('FRM_COMPATIBLE_BRAND_STATUS', $this->siteLangId), 'cbrand_active', applicationConstants::ACTIVE, array(), true, applicationConstants::INACTIVE);

        /* $frm->addCheckBox(Labels::getLabel('FRM_Featured',$this->siteLangId), 'brand_featured', 1,array(),false,0); */

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
        $frm = new Form('frmProdcBrandLang', array('id' => 'frmProdcBrandLang'));
        $frm->addHiddenField('', 'cbrand_id', $recordId);
        $frm->addSelectBox(Labels::getLabel('FRM_LANGUAGE', $langId), 'lang_id', Language::getDropDownList(CommonHelper::getDefaultFormLangId()), $langId, array(), '');
        $frm->addRequiredField(Labels::getLabel('FRM_BRAND_NAME', $langId), 'cbrand_name');
        return $frm;
    }

    public function media($recordId = 0, $langId = 0, $slide_screen = 0)
    {
        $this->objPrivilege->canEditBrands();
        $recordId = FatUtility::int($recordId);
        $logoFrm = $this->getBrandLogoForm($recordId);
        $languages = Language::getAllNames();
        if (1 == count($languages)) {
            $langId = array_key_first($languages);
        }

        $data['lang_id'] = $langId;
        $data['ratio_type'] = AttachedFile::RATIO_TYPE_SQUARE;
        if (0 < $recordId) {
            $brandLogo = current(AttachedFile::getMultipleAttachments(AttachedFile::FILETYPE_BRAND_LOGO, $recordId, 0, $langId, false));
            if (is_array($brandLogo) && count($brandLogo)) {
                $data['ratio_type'] = !empty($brandLogo['afile_aspect_ratio']) ? $brandLogo['afile_aspect_ratio'] : AttachedFile::RATIO_TYPE_SQUARE;
            }
        }
        $logoFrm->fill($data);
        $data['slide_screen'] = 1 > $slide_screen ? applicationConstants::SCREEN_DESKTOP : $slide_screen;

        $imageFrm = $this->getBrandImageForm($recordId);
        $imageFrm->fill($data);

        $getBrandRequestDimensions = ImageDimension::getScreenSizes(ImageDimension::TYPE_BRAND_IMAGE);
        $getBrandRequestLogoSquare = ImageDimension::getData(ImageDimension::TYPE_BRAND_LOGO, ImageDimension::VIEW_DEFAULT, AttachedFile::RATIO_TYPE_SQUARE);
        $getBrandRequestLogoRactangle = ImageDimension::getData(ImageDimension::TYPE_BRAND_LOGO, ImageDimension::VIEW_DEFAULT, AttachedFile::RATIO_TYPE_RECTANGULAR);
        $this->set('getBrandRequestLogoSquare', $getBrandRequestLogoSquare);
        $this->set('getBrandRequestLogoRactangle', $getBrandRequestLogoRactangle);
        $this->set('getBrandRequestDimensions', $getBrandRequestDimensions);

        $this->set('recordId', $recordId);
        $this->set('ratio_type', $data['ratio_type']);
        $this->set('logoFrm', $logoFrm);
        $this->set('imageFrm', $imageFrm);
        $this->set('languageCount', count($languages));
        $this->set('html', $this->_template->render(false, false, NULL, true));
        $this->_template->render(false, false, 'json-success.php', true, false);
    }

    public function images($brand_id, $file_type, $lang_id = 0, $slide_screen = 0)
    {
        $languages = Language::getAllNames();
        $slide_screen = FatUtility::int($slide_screen);
        $brand_id = FatUtility::int($brand_id);
        if (count($languages) > 1) {
            $lang_id = FatUtility::int($lang_id);
        } else {
            $lang_id = array_key_first($languages);
        }
        if ($file_type == 'logo') {
            $brandLogo = AttachedFile::getAttachment(AttachedFile::FILETYPE_CBRAND_LOGO, $brand_id, 0, $lang_id, (count($languages) > 1) ? false : true);
            $this->set('image', $brandLogo);
            $this->set('imageFunction', 'brandReal');
            $aspectRatioType = $brandLogo['afile_aspect_ratio'];
            $aspectRatioType = ($aspectRatioType > 0) ? $aspectRatioType : 1;
            $imageBrandDimensions = ImageDimension::getData(ImageDimension::TYPE_CBRAND_LOGO, ImageDimension::VIEW_THUMB, $aspectRatioType);
            $this->set('imageBrandDimensions', $imageBrandDimensions);
        } else {
            $brandImage = AttachedFile::getAttachment(AttachedFile::FILETYPE_CBRAND_IMAGE, $brand_id, 0, $lang_id, (count($languages) > 1) ? false : true, $slide_screen);
            $this->set('image', $brandImage);
            $this->set('imageFunction', 'brandImage');
            $imageBrandDimensions = ImageDimension::getData(ImageDimension::TYPE_CBRAND_IMAGE, ImageDimension::VIEW_THUMB);
            $this->set('imageBrandDimensions', $imageBrandDimensions);
        }

        $this->set('file_type', $file_type);
        $this->set('brand_id', $brand_id);
        $this->set('canEdit', $this->objPrivilege->canEditBrands($this->admin_id, true));
        $this->set('html', $this->_template->render(false, false, NULL, true));
        $this->_template->render(false, false, 'json-success.php', true, false);
    }

    public function requestMedia($brand_id = 0)
    {
        $this->objPrivilege->canEditBrands();
        $brand_id = FatUtility::int($brand_id);
        $brandLogoFrm = $this->getBrandLogoForm($brand_id);
        $brandImageFrm = $this->getBrandImageForm($brand_id);
        $this->set('languages', Language::getAllNames());
        $this->set('brand_id', $brand_id);
        $this->set('brandLogoFrm', $brandLogoFrm);
        $this->set('brandImageFrm', $brandImageFrm);
        $this->set('html', $this->_template->render(false, false, NULL, true));
        $this->_template->render(false, false, 'json-success.php', true, false);
    }

    public function uploadMedia()
    {
        $this->objPrivilege->canEditBrands();
        $post = FatApp::getPostedData();
        if (empty($post)) {
            LibHelper::exitWithError(Labels::getLabel('ERR_Invalid_Request_Or_File_not_supported', $this->siteLangId), true);
        }
        $brand_id = FatApp::getPostedData('cbrand_id', FatUtility::VAR_INT, 0);
        $languages = Language::getAllNames();
        if (count($languages) > 1) {
            $lang_id = FatApp::getPostedData('lang_id', FatUtility::VAR_INT, 0);
        } else {
            $lang_id = array_key_first($languages);
        }
        $file_type = FatApp::getPostedData('file_type', FatUtility::VAR_INT, 0);
        $slide_screen = FatApp::getPostedData('slide_screen', FatUtility::VAR_INT, 0);
        $aspectRatio = FatApp::getPostedData('ratio_type', FatUtility::VAR_INT, 0);

        if (!$brand_id) {
            LibHelper::exitWithError($this->str_invalid_request_id, true);
        }

        if (!is_uploaded_file($_FILES['cropped_image']['tmp_name'])) {
            LibHelper::exitWithError(Labels::getLabel('ERR_Please_Select_A_File', $this->siteLangId), true);
        }

        $fileHandlerObj = new AttachedFile();
        $fileHandlerObj->deleteFile($file_type, $brand_id, 0, 0, $lang_id, $slide_screen);

        if (!$fileHandlerObj->saveAttachment(
            $_FILES['cropped_image']['tmp_name'],
            $file_type,
            $brand_id,
            0,
            $_FILES['cropped_image']['name'],
            -1,
            false,
            $lang_id,
            $slide_screen,
            $aspectRatio
        )) {
            LibHelper::exitWithError($fileHandlerObj->getError(), true);
        }

        $this->set('recordId', $brand_id);
        $this->set('file', $_FILES['cropped_image']['name']);
        $this->set('msg', $_FILES['cropped_image']['name'] . Labels::getLabel('MSG_FILE_UPLOADED_SUCCESSFULLY', $this->siteLangId));
        $this->_template->render(false, false, 'json-success.php');
    }

    protected function isMediaUploaded($brandId)
    {
        $attachment = AttachedFile::getAttachment(AttachedFile::FILETYPE_CBRAND_LOGO, $brandId, 0);
        if (false !== $attachment && 0 < $attachment['afile_id']) {
            return true;
        }
        return false;
    }

    public function getBrandLogoForm($brandId)
    {
        $frm = new Form('frmBrandLogo');
        $languagesAssocArr = Language::getAllNames();
        $frm->addHiddenField('', 'cbrand_id', $brandId);
        $frm->addHTML('', 'heading', '');

        if (count($languagesAssocArr) > 1) {
            $frm->addSelectBox(Labels::getLabel('FRM_LANGUAGE', $this->siteLangId), 'lang_id', array(0 => Labels::getLabel('FRM_Universal', $this->siteLangId)) + $languagesAssocArr, '', array(), '');
        } else {
            $lang_id = array_key_first($languagesAssocArr);
            $frm->addHiddenField('', 'lang_id', $lang_id);
        }

        $ratioArr = AttachedFile::getRatioTypeArray($this->siteLangId);
        $frm->addRadioButtons(Labels::getLabel('FRM_RATIO', $this->siteLangId), 'ratio_type', $ratioArr, AttachedFile::RATIO_TYPE_SQUARE);

        $frm->addHiddenField('', 'file_type', AttachedFile::FILETYPE_CBRAND_LOGO);
        $frm->addHiddenField('', 'min_width');
        $frm->addHiddenField('', 'min_height');
        //$frm->addFileUpload(Labels::getLabel('FRM_BRAND_LOGO', $this->siteLangId), 'logo', array('accept' => 'image/*', 'data-frm' => 'frmBrandLogo'));
        $frm->addHTML('', 'logo', '');
        return $frm;
    }

    public function getBrandImageForm($brandId)
    {
        $frm = new Form('frmBrandImage');
        $languagesAssocArr = Language::getAllNames();
        $frm->addHiddenField('', 'cbrand_id', $brandId);
        $frm->addHTML('', 'heading', '');
        if (count($languagesAssocArr) > 1) {
            $frm->addSelectBox(Labels::getLabel('FRM_LANGUAGE', $this->siteLangId), 'lang_id', array(0 => Labels::getLabel('FRM_UNIVERSAL', $this->siteLangId)) + $languagesAssocArr, '', array(), '');
        } else {
            $lang_id = array_key_first($languagesAssocArr);
            $frm->addHiddenField('', 'lang_id', $lang_id);
        }
        $screenArr = applicationConstants::getDisplaysArr($this->siteLangId);
        $frm->addSelectBox(Labels::getLabel("FRM_DISPLAY_FOR", $this->siteLangId), 'slide_screen', $screenArr, '', array(), '');
        $frm->addHiddenField('', 'file_type', AttachedFile::FILETYPE_CBRAND_IMAGE);
        $frm->addHiddenField('', 'min_width');
        $frm->addHiddenField('', 'min_height');
        $frm->addHTML('', 'banner', '');
        return $frm;
    }

    public function requestLangForm($brand_id = 0, $lang_id = 0, $autoFillLangData = 0)
    {
        $this->objPrivilege->canEditBrands();

        $brand_id = FatUtility::int($brand_id);
        $lang_id = FatUtility::int($lang_id);

        if ($brand_id == 0 || $lang_id == 0) {
            LibHelper::exitWithError($this->str_invalid_request, true);
        }

        $prodBrandLangFrm = $this->getLangForm($brand_id, $lang_id);
        if (0 < $autoFillLangData) {
            $updateLangDataobj = new TranslateLangData(CompatibleBrand::DB_TBL_LANG);
            $translatedData = $updateLangDataobj->getTranslatedData($brand_id, $lang_id, CommonHelper::getDefaultFormLangId());
            if (false === $translatedData) {
                LibHelper::exitWithError($updateLangDataobj->getError(), true);
            }
            $langData = current($translatedData);
        } else {
            $langData = CompatibleBrand::getAttributesByLangId($lang_id, $brand_id);
        }

        if ($langData) {
            $prodBrandLangFrm->fill($langData);
        }

        $this->set('languages', Language::getAllNames());
        $this->set('brand_id', $brand_id);
        $this->set('brand_lang_id', $lang_id);
        $this->set('prodBrandLangFrm', $prodBrandLangFrm);
        $this->set('formLayout', Language::getLayoutDirection($lang_id));
        $this->set('html', $this->_template->render(false, false, NULL, true));
        $this->_template->render(false, false, 'json-success.php', true, false);
    }

    public function removeMedia($brand_id, $imageType = '', $afileId = 0)
    {
        $brand_id = FatUtility::int($brand_id);
        if (!$brand_id) {
            LibHelper::exitWithError($this->str_invalid_request, true);
        }

        if ($imageType == 'logo') {
            $fileType = AttachedFile::FILETYPE_BRAND_LOGO;
        } elseif ($imageType == 'image') {
            $fileType = AttachedFile::FILETYPE_BRAND_IMAGE;
        }
        $fileHandlerObj = new AttachedFile();
        if (!$fileHandlerObj->deleteFile($fileType, $brand_id, $afileId)) {
            LibHelper::exitWithError($fileHandlerObj->getError(), true);
        }

        $this->set('msg', Labels::getLabel('MSG_Deleted_Successfully', $this->siteLangId));
        $this->_template->render(false, false, 'json-success.php');
    }

    public function deleteRecord()
    {
        $this->objPrivilege->canEditBrands();

        $recordId = FatApp::getPostedData('recordId', FatUtility::VAR_INT, 0);
        if ($recordId < 1) {
            LibHelper::exitWithError($this->str_invalid_request_id, true);
        }

        $this->markAsDeleted($recordId);
        Product::updateMinPrices(0, 0, $recordId);
        $this->set('msg', $this->str_delete_record);
        $this->_template->render(false, false, 'json-success.php');
    }

    public function deleteSelected()
    {
        $this->objPrivilege->canEditBrands();
        $recordIdsArr = FatUtility::int(FatApp::getPostedData('brandIds'));

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

    public function markAsDeleted($recordId)
    {
        $recordId = FatUtility::int($recordId);
        if (1 > $recordId) {
            LibHelper::exitWithError($this->str_invalid_request, true);
        }
        $prodBrandObj = new CompatibleBrand($recordId);
        if (!$prodBrandObj->canRecordMarkDelete($recordId)) {
            LibHelper::exitWithError($this->str_invalid_request_id, true);
        }

        $arr = [
            CompatibleBrand::tblFld('deleted') => 1,
            CompatibleBrand::tblFld('identifier') => 'mysql_func_CONCAT(' . CompatibleBrand::tblFld('identifier') . ',"{deleted}",' . CompatibleBrand::tblFld('id') . ')'
        ];

        $prodBrandObj->assignValues($arr, false, '', '', true);
        if (!$prodBrandObj->save()) {
            LibHelper::exitWithError($prodBrandObj->getError(), true);
        }
    }

    public function autoComplete()
    {
        $page = FatApp::getPostedData('page', FatUtility::VAR_INT, 1);
        if ($page < 2) {
            $page = 1;
        }
        $pagesize = 20;
        $post = FatApp::getPostedData();

        $langId = FatApp::getPostedData('langId', FatUtility::VAR_INT, $this->siteLangId);
        $srch = CompatibleBrand::getSearchObject($langId, true, true);
        $srch->addMultipleFields(array('cbrand_id, IFNULL(cbrand_name, cbrand_identifier) as cbrand_name'));

        if (isset($post['keyword']) && '' != $post['keyword']) {
            $cond = $srch->addCondition('cbrand_name', 'LIKE', '%' . $post['keyword'] . '%');
            $cond->attachCondition('cbrand_identifier', 'LIKE', '%' . $post['keyword'] . '%', 'OR');
        }

        if (isset($post['cbrand_active'])) {
            $srch->addCondition('cbrand_active', '=', $post['cbrand_active']);
        }

        $excludeRecords = FatApp::getPostedData('excludeRecords', FatUtility::VAR_INT);
        if (!empty($excludeRecords) && is_array($excludeRecords)) {
            $srch->addCondition('cbrand_id', 'NOT IN', $excludeRecords);
        }

        $srch->addCondition('cbrand_status', '=', CompatibleBrand::BRAND_REQUEST_APPROVED);
        $doNotLimitRecords = FatApp::getPostedData('doNotLimitRecords', FatUtility::VAR_INT, 0);
        if (0 < $doNotLimitRecords) {
            $srch->doNotCalculateRecords();
            $srch->doNotLimitRecords();
        } else {
            $srch->setPageNumber($page);
            $srch->setPageSize($pagesize);
        }

        $brands = FatApp::getDb()->fetchAll($srch->getResultSet(), 'cbrand_id');

        $json = array(
            'pageCount' => $srch->pages(),
            'results' => []
        );
        foreach ($brands as $key => $brand) {
            $json['results'][] = array(
                'id' => $key,
                'text' => strip_tags(html_entity_decode($brand['cbrand_name'], ENT_QUOTES, 'UTF-8'))
            );
        }
        die(FatUtility::convertToJson($json));
    }

    public function updateStatus()
    {
        $this->objPrivilege->canEditBrands();
        $recordId = FatApp::getPostedData('recordId', FatUtility::VAR_INT, 0);
        if (0 == $recordId) {
            LibHelper::exitWithError($this->str_invalid_request_id, true);
        }
        $status = FatApp::getPostedData('status', FatUtility::VAR_INT, 0);
        if (!in_array($status, [applicationConstants::ACTIVE, applicationConstants::INACTIVE])) {
            LibHelper::exitWithError($this->str_invalid_request, true);
        }

        $this->changeStatus($recordId, $status);
        Product::updateMinPrices(0, 0, $recordId);
        $this->set('msg', Labels::getLabel('MSG_STATUS_UPDATED', $this->siteLangId));
        $this->_template->render(false, false, 'json-success.php');
    }

    public function toggleBulkStatuses()
    {
        $this->objPrivilege->canEditBrands();

        $status = FatApp::getPostedData('status', FatUtility::VAR_INT, -1);
        $brandIdsArr = FatUtility::int(FatApp::getPostedData('brandIds'));
        if (empty($brandIdsArr) || -1 == $status) {
            LibHelper::exitWithError($this->str_invalid_request, true);
        }

        foreach ($brandIdsArr as $brandId) {
            if (1 > $brandId) {
                continue;
            }

            $this->changeStatus($brandId, $status);
        }
        Product::updateMinPrices();
        $this->set('msg', Labels::getLabel('MSG_STATUS_UPDATED', $this->siteLangId));
        $this->_template->render(false, false, 'json-success.php');
    }

    /**
     * setLangTemplateData - This function is use to automate load langform and save it. 
     *
     * @param  array $constructorArgs
     * @return void
     */
    protected function setLangTemplateData(array $constructorArgs = []): void
    {
        $this->objPrivilege->canEditBrands();
        $this->modelObj = (new ReflectionClass('Brand'))->newInstanceArgs($constructorArgs);
        $this->formLangFields = [$this->modelObj::tblFld('name')];
        $this->set('formTitle', Labels::getLabel('LBL_BRAND_SETUP', $this->siteLangId));
        $this->checkMediaExist = true;
    }

    protected function changeStatus($recordId, $status)
    {
        $status = FatUtility::int($status);
        $recordId = FatUtility::int($recordId);
        if (1 > $recordId || -1 == $status) {
            LibHelper::exitWithError($this->str_invalid_request, true);
        }

        $brandObj = new CompatibleBrand($recordId);
        if (!$brandObj->changeStatus($status)) {
            LibHelper::exitWithError($brandObj->getError(), true);
        }
    }

    protected function getFormColumns(): array
    {
        $brandsTblHeadingCols = CacheHelper::get('cbrandsTblHeadingCols' . $this->siteLangId, CONF_DEF_CACHE_TIME, '.txt');
        if ($brandsTblHeadingCols) {
            return json_decode($brandsTblHeadingCols, true);
        }

        $arr = [
            'select_all' => Labels::getLabel('LBL_SELECT_ALL', $this->siteLangId),
            'cbrand_id' => Labels::getLabel('LBL_COMPATIBLE_BRAND_ID', $this->siteLangId),
           // 'cbrand_logo' => Labels::getLabel('LBL_LOGO', $this->siteLangId),
            'cbrand_identifier' => Labels::getLabel('LBL_COMPATIBLE_BRAND', $this->siteLangId),
          //  'seo_url' => Labels::getLabel('LBL_SEO_FRIENDLY_URL', $this->siteLangId),
            'cbrand_active' => Labels::getLabel('LBL_STATUS', $this->siteLangId),
            'action' => Labels::getLabel('LBL_ACTION_BUTTONS', $this->siteLangId),
        ];
        CacheHelper::create('cbrandsTblHeadingCols' . $this->siteLangId, json_encode($arr), CacheHelper::TYPE_LABELS);
        return $arr;
    }

    protected function getDefaultColumns(): array
    {
        return [
            'select_all',
            'cbrand_id',
          //  'cbrand_logo',
            'cbrand_identifier',
          //  'seo_url',
            'cbrand_active',
            'action',
        ];
    }

    protected function excludeKeysForSort($fields = []): array
    {
        return array_diff($fields, ['brand_logo', 'seo_url'], Common::excludeKeysForSort());
    }
}
