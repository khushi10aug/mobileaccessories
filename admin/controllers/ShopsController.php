<?php

class ShopsController extends ListingBaseController
{

    protected string $modelClass = 'Shop';
    protected $pageKey = 'MANAGE_SHOPS';

    public function __construct($action)
    {
        parent::__construct($action);
        $this->objPrivilege->canViewShops();
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
            $this->set("canEdit", $this->objPrivilege->canEditShops($this->admin_id, true));
        } else {
            $this->objPrivilege->canEditShops();
        }
    }

    public function index()
    {
        $this->search();
        $pageData = PageLanguageData::getAttributesByKey($this->pageKey, $this->siteLangId);
        $pageTitle = $pageData['plang_title'] ?? LibHelper::getControllerName(true);
        $this->setModel([0, 0, 0]);

        $fields = $this->getFormColumns();
        $frmSearch = $this->getSearchForm($fields);
        $shopId = FatApp::getPostedData('shop_id', FatUtility::VAR_INT, 0);
        if (0 < $shopId) {
            $shop = Shop::getAttributesByLangId($this->siteLangId, $shopId, ['COALESCE(shop_name, shop_identifier) as shop_name'], applicationConstants::JOIN_LEFT);
            $frmSearch->fill(['keyword' => $shop['shop_name']]);
        }

        $this->set('pageData', $pageData);
        $this->set('pageTitle', $pageTitle);
        $this->set('canEdit', $this->objPrivilege->canEditShops($this->admin_id, true));
        $this->set("frmSearch", $frmSearch);
        $this->set('canViewShopReports', $this->objPrivilege->canViewShopReports(0, true));
        $this->set('canViewSellerProducts', $this->objPrivilege->canViewSellerProducts(0, true));
        $actionItemsData = array_merge(HtmlHelper::getDefaultActionItems($this->getFormColumns(), $this->modelObj), [
            'newRecordBtn' => false
        ]);
        $this->set('actionItemsData', $actionItemsData);
        $this->_template->addCss('css/cropper.css');
        $this->_template->addJs(['js/cropper.js', 'js/cropper-main.js', 'shops/page-js/index.js']);
        $this->includeFeatherLightJsCss();
        $this->set('includeEditor', true);
        $this->set('keywordPlaceholder', Labels::getLabel('FRM_SEARCH_BY_SHOP_NAME_OR_SELLER_NAME', $this->siteLangId));
        $this->_template->render(true, true, '_partial/listing/index.php');
    }

    public function search()
    {
        $fields = $this->getFormColumns();
        $selectedFlds = FatApp::getPostedData('reportColumns', FatUtility::VAR_STRING, '');
        $selectedFlds = !empty($selectedFlds) ? json_decode($selectedFlds) + $this->getDefaultColumns() : $this->getDefaultColumns();

        $fields = FilterHelper::parseArrayByKeys($fields, $selectedFlds, true);
        $allowedKeysForSorting = $this->excludeKeysForSort(array_keys($fields));

        $sortBy = FatApp::getPostedData('sortBy', FatUtility::VAR_STRING, current($allowedKeysForSorting));
        if (!array_key_exists($sortBy, $fields) || $sortBy == 'listSerial') {
            $sortBy = current($allowedKeysForSorting);
        }
        $searchForm = $this->getSearchForm($fields);
        $data = FatApp::getPostedData();
        $post = $searchForm->getFormDataFromArray($data);
        $post['sortOrder'] = applicationConstants::getSortOrder(FatApp::getPostedData('sortOrder', FatUtility::VAR_STRING));
        $post['sortBy'] = $sortBy;
        $post['page'] = (empty($data['page']) || $data['page'] <= 0) ? 1 : $data['page'];
        $post['pageSize'] = applicationConstants::getPageSize(FatApp::getPostedData('pageSize', FatUtility::VAR_INT));

        $shopSrch = new AdminShopSearch($this->siteLangId);
        $shopSrch->joinWithUser();
        $shopSrch->joinWithCredential();
        $shopSrch->applySearchConditions($post);
        $this->setRecordCount(clone $shopSrch, $post['pageSize'], $post['page'], $post);
        $this->set("arrListing", $shopSrch->getListingRecords());
        $this->set('postedData', $post);
        $this->set('sortBy', $sortBy);
        $this->set('sortOrder', $post['sortOrder']);
        $this->set('fields', $fields);
        $this->set('allowedKeysForSorting', $allowedKeysForSorting);
        $this->set('canEdit', $this->objPrivilege->canEditShops($this->admin_id, true));
        $this->set('canViewShopReports', $this->objPrivilege->canViewShopReports(0, true));
        $this->set('canViewSellerProducts', $this->objPrivilege->canViewSellerProducts(0, true));
        if (FatApp::getPostedData('fIsAjax')) {
            LibHelper::exitWithSuccess([
                'listingHtml' => $this->_template->render(false, false, 'shops/search.php', true),
                'paginationHtml' => $this->_template->render(false, false, '_partial/listing/listing-foot.php', true)
            ], true);
        }
    }

    public function form()
    {
        $this->checkEditPrivilege();
        $shop_id = FatApp::getPostedData('recordId', FatUtility::VAR_INT, 0);
        $frm = $this->getForm($shop_id);
        $lang = Language::getDropDownList(CommonHelper::getDefaultFormLangId());
        if (0 < $shop_id) {
            $data = Shop::getAttributesByLangId(CommonHelper::getDefaultFormLangId(), $shop_id, ['*', 'IFNULL(shop_name,shop_identifier) as shop_name'], applicationConstants::JOIN_RIGHT);
            if ($data === false) {
                LibHelper::exitWithError($this->str_invalid_request, true);
            }
            $shopSpecificsData = ShopSpecifics::getAttributesById($shop_id);
            $data['urlrewrite_custom'] = AdminShopSearch::getUrlRewrite('shops/view/' . $shop_id);
            $data['shop_country_code'] = Countries::getCountryById($data['shop_country_id'], $this->siteLangId, 'country_code');
            $stateObj = new States();
            $statesArr = $stateObj->getStatesByCountryId($data['shop_country_id'], $this->siteLangId, true, 'state_code');
            $frm->getField('shop_state')->options = $statesArr;
            $stateCode = States::getAttributesById($data['shop_state_id'], 'state_code');
            $data['shop_state'] = $stateCode;
            $data = array_merge($data, $shopSpecificsData);
            $frm->fill($data);
        }
        $this->set('languages', $lang);
        $this->set('recordId', $shop_id);
        $this->set('stateId', $data['shop_state_id'] ?? 0);
        $this->set('frm', $frm);
        $this->set('html', $this->_template->render(false, false, NULL, true));
        $this->_template->render(false, false, 'json-success.php', true, false);
    }

    public function setup()
    {
        $this->checkEditPrivilege();
        $shop_id = FatApp::getPostedData('shop_id', FatUtility::VAR_INT, 0);
        $frm = $this->getForm($shop_id);
        $post = $frm->getFormDataFromArray(FatApp::getPostedData());
        if (false === $post) {
            LibHelper::exitWithError(current($frm->getValidationErrors()), true);
        }
        unset($post['shop_id']);
        $post['shop_country_id'] = Countries::getCountryByCode($post['shop_country_code'], 'country_id');
        $stateData = States::getStateByCountryAndCode($post['shop_country_id'], FatApp::getPostedData('shop_state'));
        $post['shop_state_id'] = $stateData['state_id'];
        $post['shop_phone_dcode'] = FatApp::getPostedData('shop_phone_dcode', FatUtility::VAR_STRING, '');
        $post['shop_identifier'] = $post['shop_name'];

        $shop = new Shop($shop_id);
        $shop->assignValues($post);
        if (!$shop->save()) {
            LibHelper::exitWithError($shop->getError(), true);
        }
        $this->setLangData($shop, [
            'shop_name' => $post['shop_name'],
            'shop_city' => $post['shop_city'],
            'shop_contact_person' => $post['shop_contact_person'],
            'shop_description' => $post['shop_description'],
            'shop_payment_policy' => $post['shop_payment_policy'],
            'shop_delivery_policy' => $post['shop_delivery_policy'],
            'shop_refund_policy' => $post['shop_refund_policy'],
            'shop_additional_info' => $post['shop_additional_info'],
            'shop_seller_info' => $post['shop_seller_info'],
        ]);

        $post['ss_shop_id'] = $shop_id;
        $shopSpecificsObj = new ShopSpecifics($shop_id);
        $shopSpecificsObj->assignValues($post);
        $data = $shopSpecificsObj->getFlds();
        if (!$shopSpecificsObj->addNew(array(), $data)) {
            LibHelper::exitWithError($shopSpecificsObj->getError(), true);
        }

        /* url data[ */
        $shopOriginalUrl = Shop::SHOP_TOP_PRODUCTS_ORGINAL_URL . $shop_id;
        if ($post['urlrewrite_custom'] == '') {
            FatApp::getDb()->deleteRecords(UrlRewrite::DB_TBL, array('smt' => 'urlrewrite_original = ?', 'vals' => array($shopOriginalUrl)));
        } else {
            $shop->rewriteUrlShop($post['urlrewrite_custom']);
            $shop->rewriteUrlReviews($post['urlrewrite_custom']);
            $shop->rewriteUrlTopProducts($post['urlrewrite_custom']);
            $shop->rewriteUrlContact($post['urlrewrite_custom']);
            $shop->rewriteUrlpolicy($post['urlrewrite_custom']);
        }
        Product::updateMinPrices(0, $shop_id);
        $this->set('msg', Labels::getLabel("MSG_SETUP_SUCCESSFUL", $this->siteLangId));
        $this->set('shopId', $shop_id);
        $this->_template->render(false, false, 'json-success.php');
    }

    protected function setLangTemplateData(array $constructorArgs = []): void
    {
        $this->objPrivilege->canEditShops();
        $this->modelObj = (new ReflectionClass('Shop'))->newInstanceArgs([FatApp::getPostedData('shop_id', FatUtility::VAR_INT, 0)]);
        $this->formLangFields = [
            $this->modelObj::tblFld('name'),
            $this->modelObj::tblFld('city'),
            $this->modelObj::tblFld('contact_person'),
            $this->modelObj::tblFld('description'),
            $this->modelObj::tblFld('payment_policy'),
            $this->modelObj::tblFld('delivery_policy'),
            $this->modelObj::tblFld('refund_policy'),
            $this->modelObj::tblFld('additional_info'),
            $this->modelObj::tblFld('seller_info')
        ];
        $this->set('formTitle', Labels::getLabel('LBL_Shop_SETUP', $this->siteLangId));
        $this->checkMediaExist = true;
    }

    protected function isMediaUploaded($shopId)
    {
        $attachment = AttachedFile::getAttachment(AttachedFile::FILETYPE_SHOP_LOGO, $shopId, 0);
        if (false !== $attachment && 0 < $attachment['afile_id']) {
            return true;
        }
        $attachment = AttachedFile::getAttachment(AttachedFile::FILETYPE_SHOP_BANNER, $shopId, 0);
        if (false !== $attachment && 0 < $attachment['afile_id']) {
            return true;
        }

        return false;
    }

    public function media($shop_id, $langId = 0)
    {
        $this->checkEditPrivilege();
        $shop_id = FatUtility::int($shop_id);
        $shopLogoFrm = $this->getShopLogoForm($shop_id, $this->siteLangId);
        $shopBannerFrm = $this->getShopBannerForm($shop_id, $this->siteLangId);
        $languages = Language::getAllNames();
        if (1 == count($languages)) {
            $langId = array_key_first($languages);
        }

        $this->set('recordId', $shop_id);
        $shopDetails = Shop::getAttributesById($shop_id);
        $shopLayoutTemplateId = $shopDetails['shop_ltemplate_id'];
        if ($shopLayoutTemplateId == 0) {
            $shopLayoutTemplateId = 10001;
        }
        $getShopDimensions = ImageDimension::getScreenSizes(ImageDimension::TYPE_SHOP_BANNER);
        $getShopLogoSquare = ImageDimension::getData(ImageDimension::TYPE_SHOP_LOGO, ImageDimension::VIEW_DEFAULT, AttachedFile::RATIO_TYPE_SQUARE);
        $getShopLogoRactangle = ImageDimension::getData(ImageDimension::TYPE_SHOP_LOGO, ImageDimension::VIEW_DEFAULT, AttachedFile::RATIO_TYPE_RECTANGULAR);

        $this->set('getShopDimensions', $getShopDimensions);
        $this->set('getShopLogoSquare', $getShopLogoSquare);
        $this->set('getShopLogoRactangle', $getShopLogoRactangle);

        $shopDetails['ratio_type'] = AttachedFile::RATIO_TYPE_SQUARE;
        if (0 < $shop_id) {
            $shopLogo = current(AttachedFile::getMultipleAttachments(AttachedFile::FILETYPE_SHOP_LOGO, $shop_id, 0, $langId, false));
            if (is_array($shopLogo) && count($shopLogo)) {
                $shopDetails['ratio_type'] = !empty($shopLogo['afile_aspect_ratio']) ? $shopLogo['afile_aspect_ratio'] : AttachedFile::RATIO_TYPE_SQUARE;
            }
        }
        $shopLogoFrm->fill($shopDetails);

        $this->set('shopDetails', $shopDetails);
        $this->set('ratio_type', $shopDetails['ratio_type']);
        $this->set('shopLayoutTemplateId', $shopLayoutTemplateId);
        $this->set('logoFrm', $shopLogoFrm);
        $this->set('shopBannerFrm', $shopBannerFrm);
        $this->set('languageCount', count($languages));
        // $this->set('bannerTypeArr', applicationConstants::getAllLanguages());

        $this->set('html', $this->_template->render(false, false, NULL, true));
        $this->_template->render(false, false, 'json-success.php', true, false);
    }

    public function images($shop_id, $file_type, $lang_id = 0, $slide_screen = 0)
    {
        $languages = Language::getAllNames();
        $slide_screen = FatUtility::int($slide_screen);
        $shop_id = FatUtility::int($shop_id);
        $lang_id = (count($languages) > 1) ? FatUtility::int($lang_id) : array_key_first($languages);

        if ($file_type == 'logo') {
            $logo = AttachedFile::getAttachment(AttachedFile::FILETYPE_SHOP_LOGO, $shop_id, 0, $lang_id, (count($languages) > 1) ? false : true);


            $aspectRatioType = $logo['afile_aspect_ratio'];
            $aspectRatioType = ($aspectRatioType > 0) ? $aspectRatioType : 1;

            $this->set('image', $logo);
            $this->set('imageFunction', 'shopLogo');
            $this->set('aspectRatioType', 'aspectRatioType');
            $imageShopDimensions = ImageDimension::getData(ImageDimension::TYPE_SHOP_LOGO, ImageDimension::VIEW_THUMB);
            $this->set('imageShopDimensions', $imageShopDimensions);
        } else {
            $brandImage = AttachedFile::getAttachment(AttachedFile::FILETYPE_SHOP_BANNER, $shop_id, 0, $lang_id, (count($languages) > 1) ? false : true, $slide_screen);
            $this->set('image', $brandImage);
            $this->set('imageFunction', 'shopBanner');
            $imageShopDimensions = ImageDimension::getData(ImageDimension::TYPE_SHOP_BANNER, ImageDimension::VIEW_THUMB);
            $this->set('imageShopDimensions', $imageShopDimensions);
        }

        $this->set('file_type', $file_type);
        $this->set('shop_id', $shop_id);
        $this->set('canEdit', $this->objPrivilege->canEditShops($this->admin_id, true));
        $this->set('html', $this->_template->render(false, false, NULL, true));
        $this->_template->render(false, false, 'json-success.php', true, false);
    }

    public function uploadMedia()
    {
        $this->objPrivilege->canEditBrands();
        $post = FatApp::getPostedData();
        if (empty($post)) {
            LibHelper::exitWithError(Labels::getLabel('ERR_INVALID_REQUEST_OR_FILE_NOT_SUPPORTED', $this->siteLangId), true);
        }
        $shop_id = FatApp::getPostedData('shop_id', FatUtility::VAR_INT, 0);
        $languages = Language::getAllNames();
        if (count($languages) > 1) {
            $lang_id = FatApp::getPostedData('lang_id', FatUtility::VAR_INT, 0);
        } else {
            $lang_id = array_key_first($languages);
        }
        $file_type = FatApp::getPostedData('file_type', FatUtility::VAR_INT, 0);
        $slide_screen = FatApp::getPostedData('slide_screen', FatUtility::VAR_INT, 0);
        $aspectRatio = FatApp::getPostedData('ratio_type', FatUtility::VAR_INT, 0);

        if (!$shop_id) {
            LibHelper::exitWithError($this->str_invalid_request_id, true);
        }

        if (!is_uploaded_file($_FILES['cropped_image']['tmp_name'])) {
            LibHelper::exitWithError(Labels::getLabel('ERR_PLEASE_SELECT_A_FILE', $this->siteLangId), true);
        }

        $fileHandlerObj = new AttachedFile();
        $fileHandlerObj->deleteFile($file_type, $shop_id, 0, 0, $lang_id, $slide_screen);

        if (!$fileHandlerObj->saveAttachment(
            $_FILES['cropped_image']['tmp_name'],
            $file_type,
            $shop_id,
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

        $this->set('recordId', $shop_id);
        $this->set('file', $_FILES['cropped_image']['name']);
        $this->set('msg', $_FILES['cropped_image']['name'] . Labels::getLabel('MSG_FILE_UPLOADED_SUCCESSFULLY', $this->siteLangId));
        $this->_template->render(false, false, 'json-success.php');
    }

    public function removeMedia($recordId, $imageType = '', $afileId = 0)
    {
        $recordId = FatUtility::int($recordId);
        if (!$recordId) {
            LibHelper::exitWithError($this->str_invalid_request, true);
        }

        if ($imageType == 'logo') {
            $fileType = AttachedFile::FILETYPE_SHOP_LOGO;
        } elseif ($imageType == 'image') {
            $fileType = AttachedFile::FILETYPE_SHOP_BANNER;
        }
        $fileHandlerObj = new AttachedFile();
        if (!$fileHandlerObj->deleteFile($fileType, $recordId, $afileId)) {
            LibHelper::exitWithError($fileHandlerObj->getError(), true);
        }

        $this->set('msg', Labels::getLabel('MSG_DELETED_SUCCESSFULLY', $this->siteLangId));
        $this->_template->render(false, false, 'json-success.php');
    }

    private function getShopLogoForm($shop_id, $land_id)
    {
        $land_id = FatUtility::int($land_id);
        $frm = new Form('frmShopLogo');
        $frm->addHTML('', 'logo_heading', '');
        $frm->addHiddenField('', 'shop_id', $shop_id);
        $bannerTypeArr = applicationConstants::getAllLanguages();

        if (count($bannerTypeArr) > 1) {
            $frm->addSelectBox(Labels::getLabel('FRM_LANGUAGE', $this->siteLangId), 'lang_id', $bannerTypeArr, '', array(), '');
        } else {
            $land_id = array_key_first($bannerTypeArr);
            $frm->addHiddenField('', 'lang_id', $land_id);
        }
        $ratioArr = AttachedFile::getRatioTypeWithCustom($this->siteLangId);
        $frm->addRadioButtons(Labels::getLabel('FRM_RATIO', $this->siteLangId), 'ratio_type', $ratioArr, AttachedFile::RATIO_TYPE_SQUARE);
        $frm->addHiddenField('', 'file_type', AttachedFile::FILETYPE_SHOP_LOGO);
        $frm->addHiddenField('', 'min_width');
        $frm->addHiddenField('', 'min_height');
        $frm->addHTML('', 'shop_logo', '');
        return $frm;
    }

    private function getShopBannerForm($shop_id, $land_id)
    {
        $land_id = FatUtility::int($land_id);
        $frm = new Form('frmShopBanner');
        $frm->addHTML('', 'banner_heading', '');
        $frm->addHiddenField('', 'shop_id', $shop_id);
        $bannerTypeArr = applicationConstants::getAllLanguages();
        if (count($bannerTypeArr) > 1) {
            $frm->addSelectBox(Labels::getLabel('FRM_LANGUAGE', $this->siteLangId), 'lang_id', $bannerTypeArr, '', array(), '');
        } else {
            $land_id = array_key_first($bannerTypeArr);
            $frm->addHiddenField('', 'lang_id', $land_id);
        }

        $screenArr = applicationConstants::getDisplaysArr($this->siteLangId);
        $frm->addSelectBox(Labels::getLabel("FRM_DISPLAY_FOR", $this->siteLangId), 'slide_screen', $screenArr, '', array(), '');
        $frm->addHiddenField('', 'file_type', AttachedFile::FILETYPE_SHOP_BANNER);
        $frm->addHiddenField('', 'min_width');
        $frm->addHiddenField('', 'min_height');
        $frm->addHTML('', 'shop_banner', '');
        return $frm;
    }

    public function getSearchForm($fields = [])
    {
        $frm = new Form('frmRecordSearch');
        $fld = $frm->addTextBox(Labels::getLabel('FRM_Keyword', $this->siteLangId), 'keyword', '', array('class' => 'search-input'));
        $fld->overrideFldType('search');
        if (!empty($fields)) {
            $this->addSortingElements($frm, 'shop_name');
        }
        $frm->addHiddenField('', 'shop_id');
        $frm->addSelectBox(Labels::getLabel('FRM_FEATURED', $this->siteLangId), 'shop_featured', array('-1' => Labels::getLabel('FRM_DOES_NOT_MATTER', $this->siteLangId)) + applicationConstants::getYesNoArr($this->siteLangId), -1, array(), '');
        $frm->addSelectBox(Labels::getLabel('FRM_STATUS', $this->siteLangId), 'shop_active', array('-1' => 'Does not Matter') + applicationConstants::getActiveInactiveArr($this->siteLangId), -1, array(), '');
        $frm->addSelectBox(Labels::getLabel('FRM_SHOP_STATUS_BY_SELLER', $this->siteLangId), 'shop_supplier_display_status', array('-1' => Labels::getLabel('FRM_DOES_NOT_MATTER', $this->siteLangId)) + applicationConstants::getOnOffArr($this->siteLangId), -1, array(), '');
        $frm->addDateField(Labels::getLabel('FRM_DATE_FROM', $this->siteLangId), 'date_from', '', array('placeholder' => Labels::getLabel('FRM_DATE_FROM', $this->siteLangId), 'readonly' => 'readonly', 'class' => 'small dateTimeFld field--calender'));
        $frm->addDateField(Labels::getLabel('FRM_DATE_TO', $this->siteLangId), 'date_to', '', array('placeholder' => Labels::getLabel('FRM_DATE_TO', $this->siteLangId), 'readonly' => 'readonly', 'class' => 'small dateTimeFld field--calender'));
        $frm->addHiddenField('', 'total_record_count');
        HtmlHelper::addSearchButton($frm);
        HtmlHelper::addClearButton($frm);/*clearBtn*/
        return $frm;
    }

    protected function getFormColumns(): array
    {
        $shopsTblHeadingCols = CacheHelper::get('shopsTblHeadingCols' . $this->siteLangId, CONF_DEF_CACHE_TIME, '.txt');
        if ($shopsTblHeadingCols) {
            return json_decode($shopsTblHeadingCols, true);
        }

        $arr = [
            'select_all' => Labels::getLabel('LBL_SELECT_ALL', $this->siteLangId),
            'listSerial' => Labels::getLabel('LBL_SR._NO', $this->siteLangId),
            'shop_name' => Labels::getLabel('LBL_SHOP_NAME', $this->siteLangId),
            'numOfProducts' => Labels::getLabel('LBL_Products', $this->siteLangId),
            'numOfReports' => Labels::getLabel('LBL_Reports', $this->siteLangId),
            'numOfReviews' => Labels::getLabel('LBL_Reviews', $this->siteLangId),
            'shop_featured' => Labels::getLabel('LBL_Featured', $this->siteLangId),
            'shop_active' => Labels::getLabel('LBL_STATUS', $this->siteLangId),
            'shop_created_on' => Labels::getLabel('LBL_Created_on', $this->siteLangId),
            'shop_supplier_display_status' => Labels::getLabel('LBL_Status_by_seller', $this->siteLangId),
            'action' => Labels::getLabel('LBL_ACTION_BUTTONS', $this->siteLangId),
        ];
        CacheHelper::create('shopsTblHeadingCols' . $this->siteLangId, json_encode($arr), CacheHelper::TYPE_LABELS);
        return $arr;
    }

    private function getForm($shop_id = 0)
    {
        $shop_id = FatUtility::int($shop_id);
        $frm = new Form('frmShop');
        $action = ($shop_id > 0) ? Labels::getLabel('FRM_Add_New', $this->siteLangId) : Labels::getLabel('FRM_UPDATE', $this->siteLangId);
        $frm->addHiddenField('', 'shop_id', $shop_id);
        $frm->addRequiredField(Labels::getLabel('FRM_SHOP_NAME', $this->siteLangId), 'shop_name');
        $fld = $frm->addTextBox(Labels::getLabel('FRM_SHOP_SEO_FRIENDLY_URL', $this->siteLangId), 'urlrewrite_custom');
        $fld->requirements()->setRequired();
        $frm->addHiddenField('', 'shop_phone_dcode');
        $phnFld = $frm->addTextBox(Labels::getLabel('FRM_PHONE', $this->siteLangId), 'shop_phone', '', array('class' => 'phoneJs ltr-right', 'placeholder' => ValidateElement::PHONE_NO_FORMAT, 'maxlength' => ValidateElement::PHONE_NO_LENGTH));
        $phnFld->requirements()->setRegularExpressionToValidate(ValidateElement::PHONE_REGEX);
        $phnFld->requirements()->setCustomErrorMessage(Labels::getLabel('FRM_PLEASE_ENTER_VALID_PHONE_NUMBER.', $this->siteLangId));

        $countryObj = new Countries();
        $countriesArr = $countryObj->getCountriesAssocArr($this->siteLangId, true, 'country_code');
        $fld = $frm->addSelectBox(Labels::getLabel('FRM_COUNTRY', $this->siteLangId), 'shop_country_code', $countriesArr, FatApp::getConfig('CONF_COUNTRY', FatUtility::VAR_INT, 223), [], Labels::getLabel('FRM_SELECT', $this->siteLangId));
        $fld->requirement->setRequired(true);

        $frm->addSelectBox(Labels::getLabel('FRM_STATE', $this->siteLangId), 'shop_state', array(), '', [], Labels::getLabel('FRM_SELECT', $this->siteLangId))->requirement->setRequired(true);
        $frm->addRequiredField(Labels::getLabel('FRM_POSTAL_CODE', $this->siteLangId), 'shop_postalcode');

        $fld = $frm->addTextBox(Labels::getLabel('FRM_ORDER_CANCELLATION_AGE', $this->siteLangId), 'shop_cancellation_age');
        $fld->requirements()->setInt();
        $fld->requirements()->setPositive();

        $fld = $frm->addTextBox(Labels::getLabel('FRM_ORDER_RETURN_AGE', $this->siteLangId), 'shop_return_age');
        $fld->requirements()->setInt();
        $fld->requirements()->setPositive();

        $fld = $frm->addTextBox(Labels::getLabel('FRM_MINIMUM_WALLET_BALANCE', $this->siteLangId), 'shop_cod_min_wallet_balance');
        $fld->requirements()->setFloat();
        $fld->htmlAfterField = "<br><small>" . Labels::getLabel("MSG_SELLER_NEEDS_TO_MAINTAIN_TO_ACCEPT_COD_ORDERS._DEFAULT_IS_-1", $this->siteLangId) . "</small>";
        $fulFillmentArr = Shipping::getFulFillmentArr($this->siteLangId);
        $fld = $frm->addSelectBox(Labels::getLabel('FRM_FULFILLMENT_METHOD', $this->siteLangId), 'shop_fulfillment_type', $fulFillmentArr, applicationConstants::NO, [], Labels::getLabel('FRM_SELECT', $this->siteLangId));
        $fld->requirements()->setRequired(true);
        $frm->addCheckBox(Labels::getLabel('FRM_STATUS', $this->siteLangId), 'shop_active', applicationConstants::ACTIVE, array(), false, applicationConstants::INACTIVE);

        $frm->addCheckBox(Labels::getLabel('FRM_FEATURED', $this->siteLangId), 'shop_featured', 1, array(), false, 0);
      
        if (
            0 < FatApp::getConfig('CONF_RFQ_MODULE', FatUtility::VAR_INT, 0) && 
            RequestForQuote::TYPE_INDIVIDUAL == FatApp::getConfig('CONF_RFQ_MODULE_TYPE', FatUtility::VAR_INT, 0) &&
            applicationConstants::NO == FatApp::getConfig('CONF_HIDE_PRICES', FatUtility::VAR_INT, 0)
        ) {
            $fld = $frm->addCheckBox(Labels::getLabel("FRM_ENABLE_RFQ_MODULE", $this->siteLangId), 'shop_rfq_enabled', 1, array(), false, 0);
            HtmlHelper::configureSwitchForCheckbox($fld, Labels::getLabel('FRM_ENABLING_THIS,_MAKES_PRODUCTS_AVAILABLE_FOR_RFQ.', $this->siteLangId));
        }

        $this->appendLangFormFields($frm, $this->siteLangId);

        /* $languageArr = Language::getDropDownList();
        $translatorSubscriptionKey = FatApp::getConfig('CONF_TRANSLATOR_SUBSCRIPTION_KEY', FatUtility::VAR_STRING, '');
        if (!empty($translatorSubscriptionKey) && 1 < count($languageArr)) {
            $frm->addCheckBox(Labels::getLabel('FRM_UPDATE_OTHER_LANGUAGES_DATA', $this->siteLangId), 'auto_update_other_langs_data', 1, array(), false, 0);
        } */
        $frm->addHiddenField('', 'shop_lat');
        $frm->addHiddenField('', 'shop_lng');
        return $frm;
    }

    protected function getLangForm($shop_id = 0, $lang_id = 0)
    {
        $frm = new Form('frmShopLang', array('id' => 'frmShopLang'));
        $frm->addHiddenField('', 'shop_id', $shop_id);
        $frm->addSelectBox(Labels::getLabel('FRM_LANGUAGE', $this->siteLangId), 'lang_id', Language::getDropDownList(CommonHelper::getDefaultFormLangId()), $lang_id, array(), '');
        $frm->addRequiredField(Labels::getLabel('FRM_SHOP_NAME', $lang_id), 'shop_name');
        $this->appendLangFormFields($frm);
        return $frm;
    }

    private function appendLangFormFields(&$frm, $lang_id = 0)
    {
        $frm->addTextBox(Labels::getLabel('FRM_SHOP_CITY', $lang_id), 'shop_city');
        $frm->addTextBox(Labels::getLabel('FRM_CONTACT_PERSON', $lang_id), 'shop_contact_person');
        $frm->addHtmlEditor(Labels::getLabel('FRM_DESCRIPTION', $lang_id), 'shop_description');
        $frm->addTextarea(Labels::getLabel('FRM_PAYMENT_POLICY', $lang_id), 'shop_payment_policy');
        $frm->addTextarea(Labels::getLabel('FRM_DELIVERY_POLICY', $lang_id), 'shop_delivery_policy');
        $frm->addTextarea(Labels::getLabel('FRM_REFUND_POLICY', $lang_id), 'shop_refund_policy');
        $frm->addTextarea(Labels::getLabel('FRM_ADDITIONAL_INFORMATION', $lang_id), 'shop_additional_info');
        $frm->addTextarea(Labels::getLabel('FRM_SELLER_INFORMATION', $lang_id), 'shop_seller_info');
        $translatorSubscriptionKey = FatApp::getConfig('CONF_TRANSLATOR_SUBSCRIPTION_KEY', FatUtility::VAR_STRING, '');

        $languageArr = Language::getDropDownList();

        if (!empty($translatorSubscriptionKey) && $lang_id == CommonHelper::getDefaultFormLangId() && 1 < count($languageArr)) {
            $frm->addCheckBox(Labels::getLabel('FRM_UPDATE_OTHER_LANGUAGES_DATA', $lang_id), 'auto_update_other_langs_data', 1, array(), false, 0);
        }
        return $frm;
    }

    public function autoComplete()
    {
        $this->objPrivilege->canViewShops();
        $srch = Shop::getSearchObject(false, $this->siteLangId);

        $post = FatApp::getPostedData();
        if (isset($post['keyword']) && '' != $post['keyword']) {
            $srch->addCondition('shop_name', 'LIKE', '%' . $post['keyword'] . '%');
        }

        if (isset($post['shop_user_id'])) {
            $srch->addCondition('shop_user_id', '=', $post['shop_user_id']);
        }

        $excludeRecords = FatApp::getPostedData('excludeRecords', FatUtility::VAR_INT);
        if (!empty($excludeRecords) && is_array($excludeRecords)) {
            $srch->addCondition('shop_id', 'NOT IN', $excludeRecords);
        }
        $srch->setPageSize(FatApp::getConfig('CONF_ADMIN_PAGESIZE', FatUtility::VAR_INT, 10));
        $srch->addMultipleFields(array('shop_id', 'IFNULL(shop_name,shop_identifier) as shop_name'));

        $collectionId = FatApp::getPostedData('collection_id', FatUtility::VAR_INT, 0);
        $alreadyAdded = Collections::getRecords($collectionId);
        if (!empty($alreadyAdded) && 0 < count($alreadyAdded)) {
            $srch->addCondition('shop_id', 'NOT IN', array_keys($alreadyAdded));
        }

        $rs = $srch->getResultSet();
        $db = FatApp::getDb();
        $products = $db->fetchAll($rs, 'shop_id');
        $json = array(
            'pageCount' => $srch->pages(),
            'results' => []
        );
        foreach ($products as $key => $product) {
            $json['results'][] = array(
                'id' => $key,
                'text' => strip_tags(html_entity_decode($product['shop_name'], ENT_QUOTES, 'UTF-8'))
            );
        }
        die(json_encode($json));
    }

    protected function getDefaultColumns(): array
    {
        return [
            'select_all',
            /* 'listSerial', */
            'shop_name',
            'numOfProducts',
            'numOfReports',
            'numOfReviews',
            'shop_featured',
            'shop_created_on',
            'shop_supplier_display_status',
            'shop_active',
            'action',
        ];
    }

    protected function excludeKeysForSort($fields = []): array
    {
        return array_diff($fields, ['shop_active', 'numOfReports', 'numOfProducts', 'numOfReviews'], Common::excludeKeysForSort());
    }

    public function shopMissingInfo()
    {
        $shopId = FatApp::getPostedData('recordId', FatUtility::VAR_INT, 0);
        if (1 > $shopId) {
            LibHelper::exitWithError($this->str_invalid_request_id, true);
        }

        $shopRow = Shop::getAttributesById($shopId, ['shop_id']);
        if (!$shopRow) {
            LibHelper::exitWithError($this->str_invalid_request_id, true);
        }

        $this->set('infoArr', Shop::getShopMissingInfo($shopId, $this->siteLangId));
        $this->set('html', $this->_template->render(false, false, NULL, true));
        $this->_template->render(false, false, 'json-success.php', true, false);
    }
}
