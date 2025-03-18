<?php

class ProductCategoriesRequestController extends ListingBaseController
{
    protected string $modelClass = 'ProductCategory';
    protected $pageKey = 'CATEGORIES_REQUESTS';

    public function __construct($action)
    {
        parent::__construct($action);
        $this->objPrivilege->canViewProductCategories();
        $this->rewriteUrl = ProductCategory::REWRITE_URL_PREFIX;
    }

    /**
     * setLangTemplateData - This function is use to automate load langform and save it. 
     *
     * @param  array $constructorArgs
     * @return void
     */
    protected function setLangTemplateData(array $constructorArgs = []): void
    {
        $this->objPrivilege->canEditProductCategories();
        $this->setModel($constructorArgs);
        $this->formLangFields = [$this->modelObj::tblFld('name')];
        $this->set('formTitle', Labels::getLabel('LBL_MANAGE_PRODUCT_CATEGORY_REQUESTS_SETUP', $this->siteLangId));
        $this->checkMediaExist = true;
    }

    public function index()
    {
        $fields = $this->getFormColumns();
        $pageData = PageLanguageData::getAttributesByKey($this->pageKey, $this->siteLangId);
        $pageTitle = $pageData['plang_title'] ?? LibHelper::getControllerName(true);
        $this->setModel();
        $this->set('pageData', $pageData);
        $this->set('pageTitle', $pageTitle);
        $this->set('canEdit', $this->objPrivilege->canEditProductCategories($this->admin_id, true));
        $this->set("frmSearch", $this->getSearchForm($fields));
        $actionItemsData = array_merge(HtmlHelper::getDefaultActionItems($fields, $this->modelObj), [
            'newRecordBtn' => false
        ]);
        $this->set('actionItemsData', $actionItemsData);
        $this->set('keywordPlaceholder', Labels::getLabel('FRM_SEARCH_BY_CATEGORY_NAME', $this->siteLangId));
        $this->_template->addCss(['css/cropper.css', 'css/select2.min.css']);
        $this->_template->addJs(['js/cropper.js', 'js/cropper-main.js', 'js/select2.js', 'product-categories-request/page-js/index.js']);
        $this->getListingData();
        $this->_template->render(true, true, '_partial/listing/index.php');
    }

    public function getSearchForm($fields = [])
    {
        $frm = new Form('frmRecordSearch');
        $fld = $frm->addTextBox(Labels::getLabel('FRM_KEYWORD', $this->siteLangId), 'keyword', '', array('class' => 'search-input'));
        $fld->overrideFldType('search');
        if (!empty($fields)) {
            $this->addSortingElements($frm, 'prodcat_updated_on', applicationConstants::SORT_DESC);
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
        $this->getListingData();
        $jsonData = [
            'listingHtml' => $this->_template->render(false, false, 'product-categories-request/search.php', true),
            'paginationHtml' => $this->_template->render(false, false, '_partial/listing/listing-foot.php', true)
        ];
        LibHelper::exitWithSuccess($jsonData, true);
    }

    private function getListingData()
    {
        $this->objPrivilege->canEditProductCategories();
        $pageSize = applicationConstants::getPageSize(FatApp::getPostedData('pageSize', FatUtility::VAR_INT));
        $data = FatApp::getPostedData();
        $fields = $this->getFormColumns();
        $selectedFlds = FatApp::getPostedData('reportColumns', FatUtility::VAR_STRING, '');
        $selectedFlds = !empty($selectedFlds) ? json_decode($selectedFlds) + $this->getDefaultColumns() : $this->getDefaultColumns();
        $fields = FilterHelper::parseArrayByKeys($fields, $selectedFlds, true);
        $allowedKeysForSorting = $this->excludeKeysForSort(array_keys($fields));
        $sortBy = FatApp::getPostedData('sortBy', FatUtility::VAR_STRING, 'prodcat_updated_on');
        if (!array_key_exists($sortBy, $fields)) {
            $sortBy = 'prodcat_updated_on';
        }
        $sortOrder = applicationConstants::getSortOrder(FatApp::getPostedData('sortOrder', FatUtility::VAR_STRING, applicationConstants::SORT_DESC), applicationConstants::SORT_DESC);
        $searchForm = $this->getSearchForm($fields);
        $page = (empty($data['page']) || $data['page'] <= 0) ? 1 : $data['page'];
        $post = $searchForm->getFormDataFromArray($data);

        $srch = ProductCategory::getSearchObject(false, $this->siteLangId, false, ProductCategory::REQUEST_PENDING);
        $srch->joinTable(User::DB_TBL, 'LEFT OUTER JOIN', 'u.user_id = prodcat_seller_id', 'u');
        $srch->joinTable(Shop::DB_TBL, 'LEFT OUTER JOIN', 'shop_user_id = if(u.user_parent > 0, user_parent, u.user_id)', 'shop');
        $srch->joinTable(Shop::DB_TBL_LANG, 'LEFT OUTER JOIN', 'shop.shop_id = s_l.shoplang_shop_id AND shoplang_lang_id = ' . $this->siteLangId, 's_l');

        if (isset($post['keyword']) && '' != $post['keyword']) {
            $condition = $srch->addCondition('prodcat_identifier', 'like', '%' . $post['keyword'] . '%');
            $condition->attachCondition('prodcat_name', 'like', '%' . $post['keyword'] . '%', 'OR');
        }

        $recordId = FatApp::getPostedData('recordId', FatUtility::VAR_INT, -1);
        $prodcatId = FatApp::getPostedData('prodcat_id', FatUtility::VAR_INT, $recordId);
        if (0 < $prodcatId) {
            $srch->addCondition('prodcat_id', '=', $prodcatId);
        }

        $user_id = FatApp::getPostedData('user_id', FatUtility::VAR_INT, 0);
        if ($user_id > 0) {
            $srch->addCondition('prodcat_seller_id', '=', $user_id);
        }
        $this->setRecordCount(clone $srch, $pageSize, $page, $post);
        $srch->doNotCalculateRecords();
        $srch->addMultipleFields(array('m.*', 'COALESCE(prodcat_name, prodcat_identifier) as prodcat_name', 'u.user_name', 'IFNULL(shop_name, shop_identifier) as shop_name', 'shop_id','shop_user_id' ,'shop_updated_on'));
        $page = (empty($page) || $page <= 0) ? 1 : $page;
        $page = FatUtility::int($page);
        $srch->setPageNumber($page);
        $srch->setPageSize($pageSize);
        $srch->addOrder($sortBy, $sortOrder);
        $rs = $srch->getResultSet();
        $records = FatApp::getDb()->fetchAll($rs);
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
        $this->objPrivilege->canEditProductCategories();
        $recordId = FatApp::getPostedData('recordId', FatUtility::VAR_INT, 0);
        $frm = $this->getForm($recordId);
        if (0 < $recordId) {
            $data = ProductCategory::getAttributesByLangId(CommonHelper::getDefaultFormLangId(), $recordId, array('prodcat_parent', 'IFNULL(prodcat_name,prodcat_identifier) as prodcat_name', 'prodcat_id', 'prodcat_identifier', 'prodcat_active', 'prodcat_status'), applicationConstants::JOIN_RIGHT);
            if ($data === false) {
                LibHelper::exitWithError($this->str_invalid_request, true);
            }
            $data['urlrewrite_custom'] = AdminShopSearch::getUrlRewrite($this->rewriteUrl . $recordId);
            $frm->fill($data);
        }

        $this->set('recordId', $recordId);
        $this->set('frm', $frm);
        $this->set('html', $this->_template->render(false, false, NULL, true));
        $this->_template->render(false, false, 'json-success.php', true, false);
    }

    public function setup()
    {
        $this->objPrivilege->canEditProductCategories();
        $recordId = FatApp::getPostedData('prodcat_id', FatUtility::VAR_INT, 0);
        $frm = $this->getForm($recordId);
        $post = $frm->getFormDataFromArray(FatApp::getPostedData());
        if (false === $post) {
            LibHelper::exitWithError(current($frm->getValidationErrors()), true);
        }
        unset($post['prodcat_id']);
        $data = $post;
        $data['prodcat_identifier'] = $data['prodcat_name'];
        if ($recordId == 0) {
            $record = ProductCategory::getAttributesByIdentifier($data['prodcat_identifier']);
            if (!empty($record) && $record['prodcat_deleted'] == applicationConstants::YES) {
                $recordId = $record['prodcat_id'];
                $data['prodcat_deleted'] = applicationConstants::NO;
            }
        }

        $record = new ProductCategory($recordId);
        $record->assignValues($data);
        if (!$record->save()) {
            $msg = $record->getError();
            if (false !== strpos(strtolower($msg), 'duplicate')) {
                $msg = Labels::getLabel('ERR_DUPLICATE_RECORD_NAME', $this->siteLangId);
            }
            LibHelper::exitWithError($msg, true);
        }
        $this->setLangData($record, ['prodcat_name' => $data['prodcat_name']]);
        $newTabLangId = 0;
        $languages = Language::getDropDownList(CommonHelper::getDefaultFormLangId());
        if (0 < count($languages)) {
            foreach ($languages as $langId => $langName) {
                if (!ProductCategory::getAttributesByLangId($langId, $recordId)) {
                    $newTabLangId = $langId;
                    break;
                }
            }
        }

        if ($newTabLangId == 0 && !$this->isMediaUploaded($recordId)) {
            $this->set('openMediaForm', true);
        }
        /* url data[ */
        $prodCatOriginalUrl = $this->rewriteUrl . $recordId;
        if ($post['urlrewrite_custom'] == '') {
            UrlRewrite::remove($prodCatOriginalUrl);
        } else {
            $record->rewriteUrl($post['urlrewrite_custom']);
        }
        /* ] */

        $record->updateCatCode();
        $this->set('msg', $this->str_setup_successful);
        $this->set('recordId', $recordId);
        $this->set('langId', $newTabLangId);
        $this->_template->render(false, false, 'json-success.php');
    }

    private function getForm($recordId = 0)
    {
        $this->objPrivilege->canEditProductCategories();
        $frm = new Form('frmProdCategory', array('id' => 'frmProdCategory'));
        $frm->addHiddenField('', 'prodcat_id');
        $frm->addRequiredField(Labels::getLabel('FRM_Category_Name', $this->siteLangId), 'prodcat_name');
        $fld = $frm->addTextBox(Labels::getLabel('FRM_CATEGORY_SEO_FRIENDLY_URL', $this->siteLangId), 'urlrewrite_custom');
        $fld->requirements()->setRequired();

        $prodCat = new ProductCategory();
        $categoriesArr = $prodCat->getCategoriesForSelectBox($this->siteLangId, $recordId, [], false);
        $categories = array(0 => Labels::getLabel('FRM_PARENT_CATEGORY', $this->siteLangId)) + $prodCat->makeAssociativeArray($categoriesArr);
        $frm->addSelectBox(Labels::getLabel('FRM_PARENT_CATEGORY', $this->siteLangId), 'prodcat_parent', $categories, '', array(), '');
        $frm->addSelectBox(Labels::getLabel('FRM_REQUEST_STATUS', $this->siteLangId), 'prodcat_status', ProductCategory::getStatusArr($this->siteLangId), '', array(), '');
        $frm->addCheckBox(Labels::getLabel('FRM_PUBLISH', $this->siteLangId), 'prodcat_active', applicationConstants::ACTIVE, [], true, applicationConstants::INACTIVE);
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
        $frm = new Form('frmProdCategoryLang', array('id' => 'frmProdCategoryLang'));
        $frm->addHiddenField('', 'prodcat_id', $recordId);
        $frm->addSelectBox(Labels::getLabel('FRM_LANGUAGE', $langId), 'lang_id', Language::getDropDownList(CommonHelper::getDefaultFormLangId()), $langId, array(), '');
        $frm->addRequiredField(Labels::getLabel('FRM_CATEGORY_NAME', $langId), 'prodcat_name');
        return $frm;
    }

    public function media($recordId = 0, $langId = 0, $slide_screen = 0)
    {
        $this->objPrivilege->canEditProductCategories();
        $recordId = FatUtility::int($recordId);
        $logoFrm = $this->getLogoForm($recordId);
        $languages = Language::getAllNames();
        if (1 == count($languages)) {
            $langId = array_key_first($languages);
        }

        $data['lang_id'] = $langId;
        $data['ratio_type'] = AttachedFile::RATIO_TYPE_SQUARE;
        if (0 < $recordId) {
            $logo = current(AttachedFile::getMultipleAttachments(AttachedFile::FILETYPE_CATEGORY_ICON, $recordId, 0, $langId, false));
            if (is_array($logo) && count($logo)) {
                $data['ratio_type'] = $logo['afile_aspect_ratio'];
            }
        }

        $bannerDimensions = ImageDimension::getScreenSizes(ImageDimension::TYPE_CATEGORY_BANNER);
        $logoDimensions = ImageDimension::getData(ImageDimension::TYPE_CATEGORY_ICON, ImageDimension::VIEW_DEFAULT);

        $logoFrm->fill($data);
        $data['slide_screen'] = 1 > $slide_screen ? applicationConstants::SCREEN_DESKTOP : $slide_screen;
        $imageFrm = $this->getImageForm($recordId);
        $imageFrm->fill($data);

        $this->set('bannerDimensions', $bannerDimensions);
        $this->set('logoDimensions', $logoDimensions);

        $this->set('recordId', $recordId);
        $this->set('logoFrm', $logoFrm);
        $this->set('imageFrm', $imageFrm);
        $this->set('languageCount', count($languages));
        $this->set('html', $this->_template->render(false, false, NULL, true));
        $this->_template->render(false, false, 'json-success.php', true, false);
    }

    public function images($recordId, $file_type, $lang_id = 0, $slide_screen = 0)
    {
        $languages = Language::getAllNames();
        $slide_screen = FatUtility::int($slide_screen);
        $recordId = FatUtility::int($recordId);
        if (count($languages) > 1) {
            $lang_id = FatUtility::int($lang_id);
        } else {
            $lang_id = array_key_first($languages);
        }
        if ($file_type == 'logo') {
            $logo = AttachedFile::getAttachment(AttachedFile::FILETYPE_CATEGORY_ICON, $recordId, 0, $lang_id, (count($languages) > 1) ? false : true);
            $this->set('image', $logo);
            $this->set('imageFunction', 'icon');
            $imageDimensions = ImageDimension::getData(ImageDimension::TYPE_CATEGORY_ICON, ImageDimension::VIEW_THUMB);
            $this->set('imageDimensions', $imageDimensions);
        } else {
            $image = AttachedFile::getAttachment(AttachedFile::FILETYPE_CATEGORY_BANNER, $recordId, 0, $lang_id, (count($languages) > 1) ? false : true, $slide_screen);
            $this->set('image', $image);
            $this->set('imageFunction', 'banner');
            $imageDimensions = ImageDimension::getData(ImageDimension::TYPE_CATEGORY_BANNER, ImageDimension::VIEW_THUMB);
            $this->set('imageDimensions', $imageDimensions);
        }

        $this->set('file_type', $file_type);
        $this->set('canEdit', $this->objPrivilege->canEditBrands($this->admin_id, true));
        $this->set('html', $this->_template->render(false, false, NULL, true));
        $this->_template->render(false, false, 'json-success.php', true, false);
    }

    public function requestMedia($brand_id = 0)
    {
        $this->objPrivilege->canEditProductCategories();
        $brand_id = FatUtility::int($brand_id);
        $brandLogoFrm = $this->getLogoForm($brand_id);
        $brandImageFrm = $this->getImageForm($brand_id);
        $this->set('languages', Language::getAllNames());
        $this->set('brand_id', $brand_id);
        $this->set('brandLogoFrm', $brandLogoFrm);
        $this->set('brandImageFrm', $brandImageFrm);
        $this->set('html', $this->_template->render(false, false, NULL, true));
        $this->_template->render(false, false, 'json-success.php', true, false);
    }

    public function uploadMedia()
    {
        $this->objPrivilege->canEditProductCategories();
        $post = FatApp::getPostedData();
        if (empty($post)) {
            LibHelper::exitWithError(Labels::getLabel('ERR_INVALID_REQUEST_OR_FILE_NOT_SUPPORTED', $this->siteLangId), true);
        }

        $recordId = FatApp::getPostedData('prodcat_id', FatUtility::VAR_INT, 0);
        $languages = Language::getAllNames();
        if (count($languages) > 1) {
            $lang_id = FatApp::getPostedData('lang_id', FatUtility::VAR_INT, 0);
        } else {
            $lang_id = array_key_first($languages);
        }

        $file_type = FatApp::getPostedData('file_type', FatUtility::VAR_INT, 0);
        $slide_screen = FatApp::getPostedData('slide_screen', FatUtility::VAR_INT, 0);
        $aspectRatio = FatApp::getPostedData('ratio_type', FatUtility::VAR_INT, 0);

        if (!$recordId) {
            LibHelper::exitWithError($this->str_invalid_request_id, true);
        }

        if (!is_uploaded_file($_FILES['cropped_image']['tmp_name'])) {
            LibHelper::exitWithError(Labels::getLabel('ERR_PLEASE_SELECT_A_FILE', $this->siteLangId), true);
        }

        $fileHandlerObj = new AttachedFile();
        $fileHandlerObj->deleteFile($file_type, $recordId, 0, 0, $lang_id, $slide_screen);

        if (!$fileHandlerObj->saveAttachment(
            $_FILES['cropped_image']['tmp_name'],
            $file_type,
            $recordId,
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

        $this->set('recordId', $recordId);
        $this->set('file', $_FILES['cropped_image']['name']);
        $this->set('msg', $_FILES['cropped_image']['name'] . Labels::getLabel('MSG_FILE_UPLOADED_SUCCESSFULLY', $this->siteLangId));
        $this->_template->render(false, false, 'json-success.php');
    }

    protected function isMediaUploaded($recordId)
    {
        $attachment = AttachedFile::getAttachment(AttachedFile::FILETYPE_CATEGORY_ICON, $recordId, 0);
        if (false !== $attachment && 0 < $attachment['afile_id']) {
            return true;
        }
        $attachment = AttachedFile::getAttachment(AttachedFile::FILETYPE_CATEGORY_BANNER, $recordId, 0);
        if (false !== $attachment && 0 < $attachment['afile_id']) {
            return true;
        }
        return false;
    }

    public function getLogoForm($catId)
    {
        $frm = new Form('frmLogo');
        $languagesAssocArr = Language::getAllNames();
        $frm->addHiddenField('', 'prodcat_id', $catId);
        $frm->addHTML('', 'heading', '');

        if (count($languagesAssocArr) > 1) {
            $frm->addSelectBox(Labels::getLabel('FRM_LANGUAGE', $this->siteLangId), 'lang_id', array(0 => Labels::getLabel('FRM_UNIVERSAL', $this->siteLangId)) + $languagesAssocArr, '', array(), '');
        } else {
            $lang_id = array_key_first($languagesAssocArr);
            $frm->addHiddenField('', 'lang_id', $lang_id);
        }

        $frm->addHiddenField('', 'file_type', AttachedFile::FILETYPE_CATEGORY_ICON);
        $frm->addHiddenField('', 'min_width');
        $frm->addHiddenField('', 'min_height');
        $frm->addHTML('', 'logo', '');
        return $frm;
    }

    public function getImageForm($catId)
    {
        $frm = new Form('frmImage');
        $languagesAssocArr = Language::getAllNames();
        $frm->addHiddenField('', 'prodcat_id', $catId);
        $frm->addHTML('', 'heading', '');
        if (count($languagesAssocArr) > 1) {
            $frm->addSelectBox(Labels::getLabel('FRM_LANGUAGE', $this->siteLangId), 'lang_id', array(0 => Labels::getLabel('FRM_UNIVERSAL', $this->siteLangId)) + $languagesAssocArr, '', array(), '');
        } else {
            $lang_id = array_key_first($languagesAssocArr);
            $frm->addHiddenField('', 'lang_id', $lang_id);
        }
        $screenArr = applicationConstants::getDisplaysArr($this->siteLangId);
        $frm->addSelectBox(Labels::getLabel("FRM_DISPLAY_FOR", $this->siteLangId), 'slide_screen', $screenArr, '', array(), '');
        $frm->addHiddenField('', 'file_type', AttachedFile::FILETYPE_CATEGORY_BANNER);
        $frm->addHiddenField('', 'min_width');
        $frm->addHiddenField('', 'min_height');
        $frm->addHTML('', 'banner', '');
        return $frm;
    }

    private function getFormColumns(): array
    {
        $shopsTblHeadingCols = CacheHelper::get('productCatRequestTblHeadingCols' . $this->siteLangId, CONF_DEF_CACHE_TIME, '.txt');
        if ($shopsTblHeadingCols) {
            return json_decode($shopsTblHeadingCols, true);
        }

        $arr = [
            /* 'listSerial' => Labels::getLabel('LBL_SR._NO', $this->siteLangId), */
            'prodcat_parent' => Labels::getLabel('LBL_PARENT_CATEGORY', $this->siteLangId),
            'prodcat_name' => Labels::getLabel('LBL_CATEGORY_NAME', $this->siteLangId),
            'shop_name' => Labels::getLabel('LBL_REQUESTED_BY', $this->siteLangId),
            'prodcat_requested_on' => Labels::getLabel('LBL_REQUESTED_ON', $this->siteLangId),
            'prodcat_updated_on' => Labels::getLabel('LBL_UPDATED_ON', $this->siteLangId),
            'action' => Labels::getLabel('LBL_ACTION_BUTTONS', $this->siteLangId),
        ];
        CacheHelper::create('productCatRequestTblHeadingCols' . $this->siteLangId, json_encode($arr), CacheHelper::TYPE_LABELS);
        return $arr;
    }

    protected function getDefaultColumns(): array
    {
        return [
            /* 'listSerial', */
            'prodcat_parent',
            'prodcat_name',
            'shop_name',
            'prodcat_requested_on',
            'prodcat_updated_on',
            'action',
        ];
    }

    private function excludeKeysForSort($fields = []): array
    {
        return array_diff($fields, ['shop_name'], Common::excludeKeysForSort());
    }
}
