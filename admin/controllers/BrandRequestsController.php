<?php

class BrandRequestsController extends ListingBaseController
{

    protected string $modelClass = 'Brand';
    protected $pageKey = 'MANAGE_BRAND_REQUEST';

    public function __construct($action)
    {
        parent::__construct($action);
        $this->objPrivilege->canViewBrandRequests();
        $this->rewriteUrl = Brand::REWRITE_URL_PREFIX;
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
            $this->set("canEdit", $this->objPrivilege->canEditBrandRequests($this->admin_id, true));
        } else {
            $this->objPrivilege->canEditBrandRequests();
        }
    }

    public function index()
    {
        $fields = $this->getFormColumns();
        $frmSearch = $this->getSearchForm($fields);
        $pageData = PageLanguageData::getAttributesByKey($this->pageKey, $this->siteLangId);
        $pageTitle = $pageData['plang_title'] ?? LibHelper::getControllerName(true);
        $this->setModel();
        $this->set('pageData', $pageData);
        $this->set('pageTitle', $pageTitle);
        $actionItemsData = array_merge(HtmlHelper::getDefaultActionItems($fields, $this->modelObj), [
            'newRecordBtn' => false
        ]);
        $this->set('actionItemsData', $actionItemsData);
        $this->set('canEdit', $this->objPrivilege->canEditBrandRequests($this->admin_id, true));
        $this->set("frmSearch", $frmSearch);
        $this->_template->addCss(['css/cropper.css', 'css/select2.min.css']);
        $this->_template->addJs([
            'js/cropper.js',
            'js/cropper-main.js',
            'js/select2.js',
            'brand-requests/page-js/index.js'
        ]);
        $this->includeFeatherLightJsCss();

        $this->getListingData();
        $this->set('keywordPlaceholder', Labels::getLabel('FRM_SEARCH_BY_BRAND_NAME', $this->siteLangId));
        $this->_template->render(true, true, '_partial/listing/index.php');
    }

    public function getSearchForm($fields = [])
    {
        $frm = new Form('frmRecordSearch');
        $fld = $frm->addTextBox(Labels::getLabel('FRM_KEYWORD', $this->siteLangId), 'keyword', '', array('class' => 'search-input'));
        $fld->overrideFldType('search');
        if (!empty($fields)) {
            $this->addSortingElements($frm, 'brand_updated_on', applicationConstants::SORT_DESC);
        }
        $frm->addSelectBox(
            Labels::getLabel('FRM_SELLER_NAME_OR_EMAIL', $this->siteLangId),
            'user_id',
            [],
            '',
            [
                'class' => 'form-control',
                'id' => 'searchFrmUserIdJs',
                'placeholder' => Labels::getLabel('FRM_SELLER_NAME_OR_EMAIL', $this->siteLangId)
            ]
        );
        $frm->addHiddenField('', 'total_record_count');
        HtmlHelper::addSearchButton($frm);
        HtmlHelper::addClearButton($frm);
        return $frm;
    }

    public function search()
    {
        $loadPagination = FatApp::getPostedData('loadPagination', FatUtility::VAR_INT, 0);
        $this->getListingData($loadPagination);
        $jsonData = [
            'paginationHtml' => $this->_template->render(false, false, '_partial/listing/listing-foot.php', true)
        ];

        if (!$loadPagination || !FatUtility::isAjaxCall()) {
            $jsonData['listingHtml'] = $this->_template->render(false, false, 'brand-requests/search.php', true);
        }

        LibHelper::exitWithSuccess($jsonData, true);
    }

    private function getListingData($loadPagination = 0)
    {
        $this->objPrivilege->canViewBrandRequests();
        $pageSize = applicationConstants::getPageSize(FatApp::getPostedData('pageSize', FatUtility::VAR_INT));
        $data = FatApp::getPostedData();
        $fields = $this->getFormColumns();
        $selectedFlds = FatApp::getPostedData('reportColumns', FatUtility::VAR_STRING, '');
        $selectedFlds = !empty($selectedFlds) ? json_decode($selectedFlds) + $this->getDefaultColumns() : $this->getDefaultColumns();

        $fields = FilterHelper::parseArrayByKeys($fields, $selectedFlds, true);
        $allowedKeysForSorting = $this->excludeKeysForSort(array_keys($fields));
        $sortBy = FatApp::getPostedData('sortBy', FatUtility::VAR_STRING, 'brand_updated_on');
        if (!array_key_exists($sortBy, $fields)) {
            $sortBy = 'brand_updated_on';
        }

        $sortOrder = applicationConstants::getSortOrder(FatApp::getPostedData('sortOrder', FatUtility::VAR_STRING, applicationConstants::SORT_DESC), applicationConstants::SORT_DESC);

        $searchForm = $this->getSearchForm($fields);

        $page = (empty($data['page']) || $data['page'] <= 0) ? 1 : $data['page'];
        $post = $searchForm->getFormDataFromArray($data);

        $prodBrandObj = new Brand();
        $srch = $prodBrandObj->getSearchObject(0, true, false, false);
        $srch->joinTable(User::DB_TBL, 'LEFT OUTER JOIN', 'u.user_id = brand_seller_id', 'u');
        $srch->joinTable(User::DB_TBL_CRED, 'LEFT OUTER JOIN', 'uc.credential_user_id = u.user_id', 'uc');
        $srch->joinTable(Shop::DB_TBL, 'LEFT OUTER JOIN', 'shop_user_id = if(u.user_parent > 0, user_parent, u.user_id)', 'shop');
        $srch->joinTable(Shop::DB_TBL_LANG, 'LEFT OUTER JOIN', 'shop.shop_id = s_l.shoplang_shop_id AND shoplang_lang_id = ' . $this->siteLangId, 's_l');
        $srch->joinTable(Brand::DB_TBL . '_lang', 'LEFT OUTER JOIN', 'brandlang_brand_id = b.brand_id AND brandlang_lang_id = ' . $this->siteLangId, 'bl');
        $srch->addCondition('brand_status', '=', 'mysql_func_' . applicationConstants::NO, 'AND', true);
        $srch->addCondition('brand_seller_id', '>', 'mysql_func_0', 'AND', true);
        if (isset($post['keyword']) && '' != $post['keyword']) {
            $condition = $srch->addCondition('b.brand_identifier', 'like', '%' . $post['keyword'] . '%');
            $condition->attachCondition('bl.brand_name', 'like', '%' . $post['keyword'] . '%', 'OR');
        }

        $user_id = FatApp::getPostedData('user_id', FatUtility::VAR_INT, 0);
        if ($user_id > 0) {
            $srch->addCondition('brand_seller_id', '=', 'mysql_func_' . $user_id, 'AND', true);
        }

        $recordId = FatApp::getPostedData('recordId', FatUtility::VAR_INT, -1);
        $brandId = FatApp::getPostedData('brand_id', FatUtility::VAR_INT, $recordId);
        if (0 < $brandId) {
            $srch->addCondition('brand_id', '=', $brandId);
        }

        if ($loadPagination && FatUtility::isAjaxCall()) {
            $this->setRecordCount(clone $srch, $pageSize, $page, $post);
        }
        $srch->doNotCalculateRecords();
        $srch->addMultipleFields(array('b.*', 'u.user_name', 'user_id', 'credential_username', 'credential_email', 'shop_id', 'shop_updated_on', 'ifnull(shop_name, shop_identifier) as shop_name', 'COALESCE(bl.brand_name, b.brand_identifier) as brand_name'));
        $page = (empty($page) || $page <= 0) ? 1 : $page;
        $page = FatUtility::int($page);
        $srch->setPageNumber($page);
        $srch->setPageSize($pageSize);
        $srch->addOrder($sortBy, $sortOrder);
        $records = [];
        if (!$loadPagination) {
            $records = FatApp::getDb()->fetchAll($srch->getResultSet());
        }
        $this->set("arrListing", $records);
        $this->set('postedData', $post);
        $this->set('sortBy', $sortBy);
        $this->set('sortOrder', $sortOrder);
        $this->set('fields', $fields);
        $this->set('allowedKeysForSorting', $allowedKeysForSorting);
        $this->set('canEdit', $this->objPrivilege->canEditBrands($this->admin_id, true));
        $this->set('canViewUsers', $this->objPrivilege->canViewUsers($this->admin_id, true));
    }

    /**
     * setLangTemplateData - This function is use to automate load langform and save it. 
     *
     * @param  array $constructorArgs
     * @return void
     */
    protected function setLangTemplateData(array $constructorArgs = []): void
    {
        $this->objPrivilege->canEditBrandRequests();
        $this->setModel($constructorArgs);
        $this->formLangFields = [$this->modelObj::tblFld('name')];
        $this->set('formTitle', Labels::getLabel('LBL_PRODUCT_BRAND_REQUEST_SETUP', $this->siteLangId));
        $this->checkMediaExist = true;
    }

    public function form()
    {
        $this->objPrivilege->canEditBrandRequests();
        $recordId = FatApp::getPostedData('recordId', FatUtility::VAR_INT, 0);
        $frm = $this->getForm($recordId);
        if (0 < $recordId) {
            $data = Brand::getAttributesByLangId(CommonHelper::getDefaultFormLangId(), $recordId, array('IFNULL(brand_name,brand_identifier) as brand_name', 'brand_id', 'brand_identifier', 'brand_active', 'brand_featured', 'brand_status', 'brand_seller_id'), applicationConstants::JOIN_RIGHT);
            if ($data === false) {
                LibHelper::exitWithError($this->str_invalid_request, true);
            }
            $data['urlrewrite_custom'] = AdminShopSearch::getUrlRewrite($this->rewriteUrl . $recordId);
            $frm->fill($data);
        }

        HtmlHelper::addIdentierToFrm($frm->getField($this->modelClass::tblFld('name')), ($data[$this->modelClass::tblFld('identifier')] ?? ''));

        $this->set('recordId', $recordId);
        $this->set('frm', $frm);
        $this->set('html', $this->_template->render(false, false, NULL, true));
        $this->_template->render(false, false, 'json-success.php', true, false);
    }

    public function setup()
    {
        $this->objPrivilege->canEditBrandRequests();

        $frm = $this->getForm();
        $post = $frm->getFormDataFromArray(FatApp::getPostedData());

        if (false === $post) {
            LibHelper::exitWithError(current($frm->getValidationErrors()), true);
        }

        $recordId = $post['brand_id'];
        unset($post['brand_id']);
        $data = $post;
        $data['brand_identifier'] = $data['brand_name'];
        if ($recordId == 0) {
            $record = Brand::getAttributesByIdentifier($data['brand_identifier']);
            if (!empty($record) && $record['brand_deleted'] == applicationConstants::YES) {
                $recordId = $record['brand_id'];
                $data['brand_deleted'] = applicationConstants::NO;
            }
        }

        $brand = new Brand($recordId);
        $brand->assignValues($data);
        if (!$brand->save()) {
            $msg = $brand->getError();
            if (false !== strpos(strtolower($msg), 'duplicate')) {
                $msg = Labels::getLabel('ERR_DUPLICATE_RECORD_NAME', $this->siteLangId);
            }
            LibHelper::exitWithError($msg, true);
        }

        $this->setLangData($brand, [$brand::tblFld('name') => $post[$brand::tblFld('name')]]);

        $brandData = Brand::getAttributesByLangId(CommonHelper::getDefaultFormLangId(), $recordId, array('IFNULL(brand_name,brand_identifier) as brand_name', 'brand_id', 'brand_identifier', 'brand_active', 'brand_featured', 'brand_status', 'brand_seller_id'), applicationConstants::JOIN_RIGHT);
        $email = new EmailHandler();
        if ($post['brand_status'] != Brand::BRAND_REQUEST_PENDING) {
            if (!$email->sendBrandRequestStatusChangeNotification($this->siteLangId, $brandData)) {
                LibHelper::exitWithError(Labels::getLabel('LBL_Email_Could_Not_Be_Sent', $this->siteLangId));
            }
        }

        /* url data[ */
        $brandOriginalUrl = $this->rewriteUrl . $recordId;
        if ($post['urlrewrite_custom'] == '') {
            UrlRewrite::remove($brandOriginalUrl);
        } else {
            $brand->rewriteUrl($post['urlrewrite_custom']);
        }
        /* ] */
        $newTabLangId = 0;
        $languages = Language::getDropDownList(CommonHelper::getDefaultFormLangId());
        if (0 < count($languages)) {
            foreach ($languages as $langId => $langName) {
                if (!Brand::getAttributesByLangId($langId, $recordId)) {
                    $newTabLangId = $langId;
                    break;
                }
            }
        }

        if ($newTabLangId == 0 && !$this->isMediaUploaded($recordId)) {
            $this->set('openMediaForm', true);
        }
        CalculativeDataRecord::updateBrandRequestCount();
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
        $frm = new Form('frmProdBrand', array('id' => 'frmProdBrand'));
        $frm->addHiddenField('', 'brand_id', $recordId);
        $frm->addRequiredField(Labels::getLabel('FRM_BRAND_NAME', $this->siteLangId), 'brand_name');
        $fld = $frm->addTextBox(Labels::getLabel('FRM_BRAND_SEO_FRIENDLY_URL', $this->siteLangId), 'urlrewrite_custom');
        $fld->requirements()->setRequired();
        $frm->addCheckBox(Labels::getLabel('FRM_BRAND_APPROVAL', $this->siteLangId), 'brand_status', applicationConstants::YES, [], false, applicationConstants::NO);
        $frm->addCheckBox(Labels::getLabel('FRM_BRAND_STATUS', $this->siteLangId), 'brand_active', applicationConstants::ACTIVE, [], true, applicationConstants::INACTIVE);

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
        $frm = new Form('frmProdBrandLang', array('id' => 'frmProdBrandLang'));
        $frm->addHiddenField('', 'brand_id', $recordId);
        $frm->addSelectBox(Labels::getLabel('FRM_LANGUAGE', $langId), 'lang_id', Language::getDropDownList(CommonHelper::getDefaultFormLangId()), $langId, array(), '');
        $frm->addRequiredField(Labels::getLabel('FRM_Brand_Name', $langId), 'brand_name');
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
            if (is_array($brandLogo) && count($brandLogo) && 0 < $brandLogo['afile_aspect_ratio']) {
                $data['ratio_type'] = $brandLogo['afile_aspect_ratio'];
            }
        }
        $data['ratio_type'] = ($data['ratio_type'] == 0) ? AttachedFile::RATIO_TYPE_SQUARE : $data['ratio_type'];
        $logoFrm->fill($data);
        $data['slide_screen'] = 1 > $slide_screen ? applicationConstants::SCREEN_DESKTOP : $slide_screen;
        $imageFrm = $this->getBrandImageForm($recordId);
        $getBrandRequestDimensions = ImageDimension::getScreenSizes(ImageDimension::TYPE_BRAND_IMAGE);
        $getBrandRequestLogoSquare = ImageDimension::getData(ImageDimension::TYPE_BRAND_LOGO, ImageDimension::VIEW_DEFAULT, AttachedFile::RATIO_TYPE_SQUARE);
        $getBrandRequestLogoRactangle = ImageDimension::getData(ImageDimension::TYPE_BRAND_LOGO, ImageDimension::VIEW_DEFAULT, AttachedFile::RATIO_TYPE_RECTANGULAR);

        $imageFrm->fill($data);
        $this->set('getBrandRequestLogoSquare', $getBrandRequestLogoSquare);
        $this->set('getBrandRequestLogoRactangle', $getBrandRequestLogoRactangle);
        $this->set('recordId', $recordId);
        $this->set('ratio_type', $data['ratio_type']);
        $this->set('logoFrm', $logoFrm);
        $this->set('imageFrm', $imageFrm);
        $this->set('languageCount', count($languages));
        $this->set('getBrandRequestDimensions', $getBrandRequestDimensions);
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
            $brandLogo = AttachedFile::getAttachment(AttachedFile::FILETYPE_BRAND_LOGO, $brand_id, 0, $lang_id, (count($languages) > 1) ? false : true);

            $aspectRatioType = $brandLogo['afile_aspect_ratio'];
            $aspectRatioType = ($aspectRatioType > 0) ? $aspectRatioType : 1;
            $imageBrandDimensions = ImageDimension::getData(ImageDimension::TYPE_BRAND_LOGO, ImageDimension::VIEW_THUMB, $aspectRatioType);
            $this->set('aspectRatioType', $aspectRatioType);
            $this->set('image', $brandLogo);
            $this->set('imageFunction', 'brandReal');
            $this->set('imageBrandDimensions', $imageBrandDimensions);
        } else {
            $imageBrandDimensions = ImageDimension::getData(ImageDimension::TYPE_BRAND_IMAGE, ImageDimension::VIEW_THUMB);
            $brandImage = AttachedFile::getAttachment(AttachedFile::FILETYPE_BRAND_IMAGE, $brand_id, 0, $lang_id, (count($languages) > 1) ? false : true, $slide_screen);
            $this->set('image', $brandImage);
            $this->set('imageFunction', 'brandImage');
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
        $brand_id = FatApp::getPostedData('brand_id', FatUtility::VAR_INT, 0);
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
        $this->set('msg', $_FILES['cropped_image']['name'] . Labels::getLabel('MSG_File_Uploaded_Successfully', $this->siteLangId));
        $this->_template->render(false, false, 'json-success.php');
    }

    protected function isMediaUploaded($brandId)
    {
        $attachment = AttachedFile::getAttachment(AttachedFile::FILETYPE_BRAND_LOGO, $brandId, 0);
        if (false !== $attachment && 0 < $attachment['afile_id']) {
            return true;
        }
        return false;
    }

    public function getBrandLogoForm($brandId)
    {
        $frm = new Form('frmBrandLogo');
        $languagesAssocArr = Language::getAllNames();
        $frm->addHiddenField('', 'brand_id', $brandId);
        $frm->addHTML('', 'heading', '');

        if (count($languagesAssocArr) > 1) {
            $frm->addSelectBox(Labels::getLabel('FRM_LANGUAGE', $this->siteLangId), 'lang_id', array(0 => Labels::getLabel('FRM_Universal', $this->siteLangId)) + $languagesAssocArr, '', array(), '');
        } else {
            $lang_id = array_key_first($languagesAssocArr);
            $frm->addHiddenField('', 'lang_id', $lang_id);
        }

        $ratioArr = AttachedFile::getRatioTypeArray($this->siteLangId);
        $frm->addRadioButtons(Labels::getLabel('FRM_RATIO', $this->siteLangId), 'ratio_type', $ratioArr, AttachedFile::RATIO_TYPE_SQUARE);

        $frm->addHiddenField('', 'file_type', AttachedFile::FILETYPE_BRAND_LOGO);
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
        $frm->addHiddenField('', 'brand_id', $brandId);
        $frm->addHTML('', 'heading', '');
        if (count($languagesAssocArr) > 1) {
            $frm->addSelectBox(Labels::getLabel('FRM_LANGUAGE', $this->siteLangId), 'lang_id', array(0 => Labels::getLabel('FRM_UNIVERSAL', $this->siteLangId)) + $languagesAssocArr, '', array(), '');
        } else {
            $lang_id = array_key_first($languagesAssocArr);
            $frm->addHiddenField('', 'lang_id', $lang_id);
        }
        $screenArr = applicationConstants::getDisplaysArr($this->siteLangId);
        $frm->addSelectBox(Labels::getLabel("FRM_DISPLAY_FOR", $this->siteLangId), 'slide_screen', $screenArr, '', array(), '');
        $frm->addHiddenField('', 'file_type', AttachedFile::FILETYPE_BRAND_IMAGE);
        $frm->addHiddenField('', 'min_width');
        $frm->addHiddenField('', 'min_height');
        $frm->addHTML('', 'banner', '');
        return $frm;
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

    public function updateApprovalStatus()
    {
        $this->checkEditPrivilege();

        $recordId = FatApp::getPostedData('recordId', FatUtility::VAR_INT, 0);
        if (0 == $recordId) {
            LibHelper::exitWithError($this->str_invalid_request_id, true);
        }
        $status = FatApp::getPostedData('status', FatUtility::VAR_INT, 0);
        if (!in_array($status, [applicationConstants::ACTIVE, applicationConstants::INACTIVE])) {
            LibHelper::exitWithError($this->str_invalid_request, true);
        }

        $brandObj = new Brand($recordId);
        $brandObj->setFldValue($brandObj::tblFld('status'), $status);
        if (!$brandObj->save()) {
            LibHelper::exitWithError($brandObj->getError(), true);
        }

        $brandData = Brand::getAttributesByLangId(CommonHelper::getDefaultFormLangId(), $recordId, array('IFNULL(brand_name,brand_identifier) as brand_name', 'brand_id', 'brand_identifier', 'brand_active', 'brand_featured', 'brand_status', 'brand_seller_id'), applicationConstants::JOIN_RIGHT);
        $email = new EmailHandler();
        if ($status != Brand::BRAND_REQUEST_PENDING) {
            if (!$email->sendBrandRequestStatusChangeNotification($this->siteLangId, $brandData)) {
                LibHelper::exitWithError(Labels::getLabel('LBL_Email_Could_Not_Be_Sent', $this->siteLangId));
            }
        }
        CalculativeDataRecord::updateBrandRequestCount();
        $this->set('msg', Labels::getLabel('MSG_BRAND_APPROVED_SUCCESSFULLY', $this->siteLangId));
        $this->_template->render(false, false, 'json-success.php');
    }

    private function getFormColumns(): array
    {
        $shopsTblHeadingCols = CacheHelper::get('brandRequestTblHeadingCols' . $this->siteLangId, CONF_DEF_CACHE_TIME, '.txt');
        if ($shopsTblHeadingCols) {
            return json_decode($shopsTblHeadingCols, true);
        }

        $arr = [
            'select_all' => Labels::getLabel('LBL_SELECT_ALL', $this->siteLangId),
            'brand_logo' => Labels::getLabel('LBL_LOGO', $this->siteLangId),
            'brand_name' => Labels::getLabel('LBL_BRAND_NAME', $this->siteLangId),
            'shop_name' => Labels::getLabel('LBL_REQUESTED_BY', $this->siteLangId),
            'brand_requested_on' => Labels::getLabel('LBL_REQUESTED_ON', $this->siteLangId),
            'brand_updated_on' => Labels::getLabel('LBL_UPDATED_ON', $this->siteLangId),
            'brand_active' => Labels::getLabel('LBL_STATUS', $this->siteLangId),
            'brand_status' => Labels::getLabel('LBL_BRAND_APPROVAL', $this->siteLangId),
            'action' => Labels::getLabel('LBL_ACTION_BUTTONS', $this->siteLangId),
        ];
        CacheHelper::create('brandRequestTblHeadingCols' . $this->siteLangId, json_encode($arr), CacheHelper::TYPE_LABELS);
        return $arr;
    }

    protected function getDefaultColumns(): array
    {
        return [
            'select_all',
            'brand_logo',
            'brand_name',
            'shop_name',
            'brand_requested_on',
            'brand_updated_on',
            'brand_active',
            'brand_status',
            'action',
        ];
    }

    private function excludeKeysForSort($fields = []): array
    {
        return array_diff($fields, ['brand_logo', 'shop_name', 'brand_name', 'sbrandreq_status'], Common::excludeKeysForSort());
    }
}
