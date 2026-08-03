<?php

class CustomProductsController extends ListingBaseController
{
    use CatalogProduct;
    protected string $modelClass = 'ProductRequest';
    protected $pageKey = 'CUSTOM_PRODUCT_REQUEST';

    public function __construct($action)
    {
        parent::__construct($action);
        $this->objPrivilege->canViewCustomProductRequests();
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
            $this->set("canEdit", $this->objPrivilege->canEditCustomProductRequests($this->admin_id, true));
        } else {
            $this->objPrivilege->canEditCustomProductRequests();
        }
    }

    public function index()
    {
        $fields = $this->getFormColumns();
        $frmSearch = $this->getSearchForm($fields);

        $pageData = PageLanguageData::getAttributesByKey($this->pageKey, $this->siteLangId);
        $pageTitle = $pageData['plang_title'] ?? Labels::getLabel('LBL_MASTER_PRODUCT_REQUESTS', $this->siteLangId);

        $this->setModel();
        $actionItemsData = HtmlHelper::getDefaultActionItems($fields, $this->modelObj);
        $actionItemsData['newRecordBtn'] = false;

        $this->set('pageData', $pageData);
        $this->set('pageTitle', $pageTitle);
        $this->set('actionItemsData', $actionItemsData);
        $this->set("frmSearch", $frmSearch);
        $this->set('defaultColumns', $this->getDefaultColumns());
        $this->set('keywordPlaceholder', Labels::getLabel('FRM_SEARCH_BY_PRODUCT_NAME', $this->siteLangId));

        $this->checkEditPrivilege(true);
        $this->getListingData();

        $this->_template->addCss(array('css/select2.min.css'));
        $this->_template->addJs(array('custom-products/page-js/index.js', 'js/select2.js'));
        $this->includeFeatherLightJsCss();
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
            $jsonData['listingHtml'] = $this->_template->render(false, false, null, true);
        }
        LibHelper::exitWithSuccess($jsonData, true);
    }

    private function getListingData($loadPagination = 0)
    {
        $pageSize = applicationConstants::getPageSize(FatApp::getPostedData('pageSize', FatUtility::VAR_INT));

        $fields = $this->getFormColumns();
        $selectedFlds = FatApp::getPostedData('reportColumns', FatUtility::VAR_STRING, '');
        $selectedFlds = !empty($selectedFlds) ? json_decode($selectedFlds) +  $this->getDefaultColumns() : $this->getDefaultColumns();

        $fields =  FilterHelper::parseArrayByKeys($fields, $selectedFlds, true);
        $allowedKeysForSorting = $this->excludeKeysForSort(array_keys($fields));
        $sortBy = FatApp::getPostedData('sortBy', FatUtility::VAR_STRING, 'preq_requested_on');
        if (!array_key_exists($sortBy, $fields)) {
            $sortBy = 'preq_requested_on';
        }

        $sortOrder = applicationConstants::getSortOrder(FatApp::getPostedData('sortOrder', FatUtility::VAR_STRING, applicationConstants::SORT_DESC), applicationConstants::SORT_DESC);

        $searchForm = $this->getSearchForm($fields);

        $page = FatApp::getPostedData('page', FatUtility::VAR_INT, 1);
        $page = ($page <= 0) ? 1 : $page;
        $post = $searchForm->getFormDataFromArray(FatApp::getPostedData());
        $post['seller_id'] = FatApp::getPostedData('seller_id', FatUtility::VAR_INT, 0);

        $srch = ProductRequest::getSearchObject($this->siteLangId, true, true);
        $srch->joinTable(User::DB_TBL, 'LEFT OUTER JOIN', 'preq_user_id = u.user_id', 'u');
        $srch->joinTable(User::DB_TBL_CRED, 'LEFT OUTER JOIN', 'uc.credential_user_id = u.user_id', 'uc');
        $srch->joinTable(Shop::DB_TBL, 'LEFT OUTER JOIN', Shop::DB_TBL_PREFIX . 'user_id = if(u.user_parent > 0, u.user_parent, u.user_id)', 'shop');
        $srch->joinTable(Shop::DB_TBL_LANG, 'LEFT OUTER JOIN', 'shop.shop_id = s_l.shoplang_shop_id AND shoplang_lang_id = ' . $this->siteLangId, 's_l');

        if (isset($post['keyword']) && '' != $post['keyword']) {
            $cond = $srch->addCondition('preq.preq_content', 'like', '%' . $post['keyword'] . '%');
            $cond->attachCondition('preq_l.preq_lang_data', 'like', '%' . $post['keyword'] . '%', 'OR');
        }

        if (!empty($post['date_from'])) {
            $srch->addCondition('preq.preq_added_on', '>=', $post['date_from'] . ' 00:00:00');
        }

        if (!empty($post['date_to'])) {
            $srch->addCondition('preq.preq_added_on', '<=', $post['date_to'] . ' 23:59:59');
        }

        if (!empty($post['status'])) {
            $srch->addCondition('preq.preq_status', '=', $post['status']);
        }

        if (0 < $post['seller_id']) {
            $srch->addCondition('preq.preq_user_id', '=', $post['seller_id']);
        }

        $recordId = FatApp::getPostedData('recordId', FatUtility::VAR_INT, -1);
        if (0 < $recordId) {
            $srch->addCondition('preq_id', '=', $recordId);
        }

        if ($loadPagination && FatUtility::isAjaxCall()) {
            $this->setRecordCount(clone $srch, $pageSize, $page, $post);
        }
        $srch->doNotCalculateRecords();
        $srch->addMultipleFields(array('preq.*', 'user_id', 'user_name', 'user_parent', 'credential_username', 'credential_email', 'IFNULL(shop_name, shop_identifier) as shop_name', 'shop_id', 'shop_updated_on'));
        $srch->setPageNumber($page);
        $srch->setPageSize($pageSize);
        $srch->addOrder($sortBy, $sortOrder);
        $rs = $srch->getResultSet();

        $records = [];
        if (!$loadPagination) {
            while ($res = FatApp::getDb()->fetch($rs)) {
                $content = (!empty($res['preq_content'])) ? json_decode($res['preq_content'], true) : array();
                $langContent = (!empty($res['preq_lang_data'])) ? json_decode($res['preq_lang_data'], true) : array();

                $res = array_merge($res, $content);
                if (!empty($langContent)) {
                    $res = array_merge($res, $langContent);
                }
                $arr = array(
                    'preq_id' => $res['preq_id'],
                    'preq_user_id' => $res['preq_user_id'] ?? 0,
                    'preq_added_on' => $res['preq_added_on'] ?? '',
                    'preq_status' => $res['preq_status'] ?? '',
                    'preq_comment' => $res['preq_comment'] ?? '',
                    'preq_requested_on' => $res['preq_requested_on'] ?? '',
                    'preq_status_updated_on' => $res['preq_status_updated_on'] ?? '',
                    'user_id' => $res['user_id'] ?? 0,
                    'user_name' => $res['user_name'] ?? '',
                    'user_parent' => $res['user_parent'] ?? 0,
                    'shop_name' => $res['shop_name'] ?? '',
                    'shop_id' => $res['shop_id'] ?? '',
                    'shop_updated_on' => $res['shop_updated_on'] ?? '',
                    'product_identifier' => $res['product_identifier'],
                    'product_name' => (!empty($res['product_name'])) ? $res['product_name'] : $res['product_identifier'],
                    'credential_username' => $res['credential_username'] ?? '',
                    'credential_email' => $res['credential_email'] ?? '',
                );
                $records[] = $arr;
            }
        }
        $this->set("arrListing", $records);
        $this->set('postedData', $post);
        $this->set('frmSearch', $searchForm);
        $this->set('sortBy', $sortBy);
        $this->set('sortOrder', $sortOrder);
        $this->set('fields', $fields);
        $this->set('allowedKeysForSorting', $allowedKeysForSorting);
        $this->set('canViewUsers', $this->objPrivilege->canViewUsers($this->admin_id, true));
        $this->checkEditPrivilege(true);
    }

    public function form($recordId, $productType = 0)
    {
        $this->checkEditPrivilege();

        $recordId = FatUtility::int($recordId);
        if (!$recordId) {
            LibHelper::exitWithError($this->str_invalid_request, true);
        }

        $pageData = PageLanguageData::getAttributesByKey('MASTER_PRODUCT_REQUEST_FORM', $this->siteLangId);
        $pageTitle = $pageData['plang_title'] ?? LibHelper::getControllerName(true);

        $this->set('pageData', $pageData);
        $this->set('pageTitle', $pageTitle);

        $productType = FatUtility::int($productType);
        $langId = FatApp::getPostedData('langId', FatUtility::VAR_INT, 0);
        if (1 > $langId) {
            $langId = CommonHelper::getDefaultFormLangId();
        }

        $frm = $this->getForm($langId, $productType, $recordId);
        $imgFrm = $this->getImageFrm($recordId);
        $productOptions = [];

        $this->setModel([$recordId]);

        if (0 < FatApp::getPostedData('autoFillLangData', FatUtility::VAR_INT, 0)) {
            $productData = $this->modelObj::getAttributesByLangId(CommonHelper::getDefaultFormLangId(), $recordId, null, applicationConstants::JOIN_RIGHT);
            if (!empty($productData['preq_lang_data'])) {
                $preqLangData = json_decode($productData['preq_lang_data'], true);
                $updateLangDataobj = new TranslateLangData(ProductRequest::DB_TBL_LANG);
                $translatedData = $updateLangDataobj->directTranslate($preqLangData, $langId);
                if (false === $translatedData) {
                    LibHelper::exitWithError($updateLangDataobj->getError(), true);
                }
                $productData = array_merge($productData, current($translatedData));
            }
        } else {
            $productData = $this->modelObj::getAttributesByLangId($langId, $recordId, null, applicationConstants::JOIN_RIGHT);
            if ($productData && !empty($productData['preq_lang_data'])) {
                $productData = array_merge($productData, json_decode($productData['preq_lang_data'], true));
            }
        }

        if (empty($productData)) {
            LibHelper::exitWithError($this->str_invalid_request_id, true);
            FatApp::redirectUser(UrlHelper::generateUrl('CustomProducts'));
        }
        unset($productData['preq_lang_data']);

        if ($productData['preq_status'] != ProductRequest::STATUS_PENDING) {
            LibHelper::exitWithError($this->str_invalid_request, true);
        }

        $productData = array_merge(
            $productData,
            json_decode($productData['preq_content'], true)
        );
        unset($productData['preq_content']);

        if (1 > $productType) {
            $frm = $this->getForm($langId, $productData['product_type'], $recordId);
        }

        $tagData = [];
        if (!empty($productData['product_tags'])) {
            $srch = Tag::getSearchObject($langId);
            $srch->addCondition('tag_id', 'IN', $productData['product_tags']);
            $srch->addMultipleFields(['tag_id', 'tag_name']);
            $productTags = FatApp::getDb()->fetchAll($srch->getResultSet());
            foreach ($productTags as $key => $data) {
                $tagData[$key]['id'] = $data['tag_id'];
                $tagData[$key]['value'] = htmlspecialchars($data['tag_name'], ENT_QUOTES, 'UTF-8');
            }
        }
        $productData['product_tags'] = json_encode($tagData);
        if (0 < $productData['preq_brand_id']) {
            $productData['product_brand_id'] = $productData['preq_brand_id'];
            $brandData = Brand::getAttributesByLangId($langId, $productData['preq_brand_id'], [Brand::tblFld('name'), Brand::tblFld('identifier')], applicationConstants::JOIN_RIGHT, applicationConstants::YES, applicationConstants::NO);
            if (false != $brandData) {
                $fld = $frm->getField('product_brand_id');
                $fld->options = [$productData['preq_brand_id'] => $brandData[Brand::tblFld('name')] ?? $brandData[Brand::tblFld('identifier')]];
            }
            unset($productData['preq_brand_id']);
        }

        if (!empty($productData['preq_prodcat_id'])) {
            $productData['ptc_prodcat_id'] = $productData['preq_prodcat_id'];
            $catData = ProductCategory::getAttributesByLangId($langId, $productData['preq_prodcat_id'], [ProductCategory::tblFld('name'), ProductCategory::tblFld('identifier')], applicationConstants::JOIN_RIGHT, applicationConstants::YES, applicationConstants::NO);
            if (false != $catData) {
                $fld = $frm->getField('ptc_prodcat_id');
                $fld->options = [$productData['ptc_prodcat_id'] => $catData[ProductCategory::tblFld('name')] ?? $catData[ProductCategory::tblFld('identifier')]];
            }
            unset($productData['preq_prodcat_id']);
        }

        if (Tax::getActivatedServiceId()) {
            $taxCatMultiFields = ['concat(IFNULL(taxcat_name,taxcat_identifier)', '" (",taxcat_code,")") as taxcat_name', 'taxcat_id'];
        } else {
            $taxCatMultiFields = ['IFNULL(taxcat_name,taxcat_identifier) as taxcat_name', 'taxcat_id'];
        }

        //ptt_taxcat_id        
        if (!empty($productData['ptt_taxcat_id'])  && 0 < $productData['ptt_taxcat_id']) {
            $taxData = Tax::getAttributesByLangId($langId, $productData['ptt_taxcat_id'], $taxCatMultiFields, applicationConstants::JOIN_RIGHT, applicationConstants::YES, applicationConstants::NO);
            if ($taxData) {
                $fld = $frm->getField('ptt_taxcat_id');
                $fld->options = [$productData['ptt_taxcat_id'] => $taxData[Tax::tblFld('name')] ?? $taxData[Tax::tblFld('identifier')]];
            }
        }

        if (!empty($productData['ps_from_country_id'])  && 0 < $productData['ps_from_country_id']) {
            $countryData = Countries::getAttributesByLangId($langId, $productData['ps_from_country_id'], [Countries::tblFld('name'), Countries::tblFld('code')], applicationConstants::JOIN_RIGHT, applicationConstants::YES);
            if ($countryData) {
                $fld = $frm->getField('ps_from_country_id');
                if (null != $fld) {
                    $fld->options = [$productData['ps_from_country_id'] => $countryData[Tax::tblFld('name')] ?? $taxData[Tax::tblFld('identifier')]];
                }
            }
        }

        if (isset($productData['product_option']) && is_array($productData['product_option']) && count($productData['product_option'])) {
            $srch = Option::getSearchObject($langId);
            $srch->addMultipleFields(['option_id', 'option_identifier', 'option_name', 'option_is_separate_images']);
            $srch->addCondition('option_id', 'IN', $productData['product_option']);
            $prodOptions = FatApp::getDb()->fetchAll($srch->getResultSet(), 'option_id');
            foreach ($productData['product_option'] as $index => $optionId) {
                if ($prodOptions[$optionId]) {
                    $productOptions[$index] = $prodOptions[$optionId];
                    $productOptions[$index]['optionValues'] = Product::getOptionValues($optionId, $langId, ($productData['product_option_values'][$index] ?? []));
                }
            }
        }
        $productData['upc_type'] = applicationConstants::YES;
        $productData['preq_ean_upc_code'] = json_decode($productData['preq_ean_upc_code'], true);
        if (!empty($productData['preq_ean_upc_code']) && count($productData['preq_ean_upc_code']) &&  array_key_first($productData['preq_ean_upc_code']) != 0) {
            $productData['upc_type'] = applicationConstants::NO;
        }

        $this->set("productData", [
            'product_type' => $productData['product_type'],
            'product_seller_id' => 0,
            'product_attachements_with_inventory' => ($productData['product_attachements_with_inventory'] ?? 0),
        ]);

        /* to select product type in get */
        if (0 < $productType) {
            $productData['product_type'] = $productType;
        }

        $productData['record_id'] = $recordId;

        if (0 < FatApp::getConfig('CONF_WITHOUT_PROD_VARIANTS', FatUtility::VAR_INT, 0)) {
            $selprodData = json_decode($productData['preq_sel_prod_data'], true);
            if (!empty($selprodData)) {
                $productData = array_merge($productData, $selprodData);
            }
        }

        $frm->fill($productData);

        $this->set("selProdId", 0);
        $this->set("frm", $frm);
        $this->set("imgFrm", $imgFrm);

        $codEnabled = true;
        $paymentMethod = new PaymentMethods();
        if (!$paymentMethod->cashOnDeliveryIsActive()) {
            $codEnabled = false;
        }
        $this->set("codEnabled", $codEnabled);
        $this->set("canEditTags",  $this->objPrivilege->canEditTags($this->admin_id, true));
        $this->set("langId", $langId);
        $this->set("recordId", $recordId);
        $this->set('productOptions', $productOptions);
        $this->set('hasInventory', Product::hasInventory($recordId));
        $this->set('formLayout', Language::getLayoutDirection($langId));
        $this->set('tourStep', SiteTourHelper::getStepIndex());
        if (FatUtility::isAjaxCall()) {
            $this->set('html', $this->_template->render(false, false, NULL, true));
            $this->_template->render(false, false, 'json-success.php', true, false);
            return;
        }

        $this->_template->addJs(array('js/cropper.js', 'js/cropper-main.js', 'js/select2.js', 'js/tagify.min.js', 'js/tagify.polyfills.min.js', 'js/jquery-sortable-lists.js'));
        $this->_template->addCss(['css/cropper.css', 'css/tagify.min.css', 'css/select2.min.css']);
        $this->set("includeEditor", true);
        $this->_template->render();
    }

    public function requestStatusForm(int $recordId)
    {
        if (1 > $recordId) {
            LibHelper::exitWithError($this->str_invalid_request_id, true);
        }
        $reqStatus = $this->modelClass::getAttributesById($recordId, 'preq_status');

        if (false === $reqStatus ||  $reqStatus != ProductRequest::STATUS_PENDING) {
            LibHelper::exitWithError($this->str_invalid_request_id, true);
        }

        $frm = $this->getStatusForm($recordId);
        $frm->fill(['preq_status' => $reqStatus]);

        $this->set('frm', $frm);
        $this->set('recordId', $recordId);
        $this->set('includeTabs', false);
        $this->set('html', $this->_template->render(false, false, NULL, true));
        $this->_template->render(false, false, 'json-success.php', true, false);
    }

    public function changeRequestStatus()
    {
        $this->checkEditPrivilege();

        $frm = $this->getStatusForm();
        $post = $frm->getFormDataFromArray(FatApp::getPostedData());
        if (false === $post) {
            LibHelper::exitWithError(current($frm->getValidationErrors()), true);
        }

        $preqId = FatUtility::int($post['preq_id']);
        $status = FatUtility::int($post['preq_status']);

        $srch = ProductRequest::getSearchObject($this->siteLangId);
        $srch->joinTable(User::DB_TBL, 'LEFT OUTER JOIN', 'u.user_id = preq.preq_user_id', 'u');
        $srch->joinTable(User::DB_TBL_CRED, 'LEFT OUTER JOIN', 'c.credential_user_id = u.user_id', 'c');
        $srch->joinTable(Shop::DB_TBL, 'LEFT OUTER JOIN', Shop::DB_TBL_PREFIX . 'user_id = u.user_id', 'shop');
        $srch->joinTable(Shop::DB_TBL_LANG, 'LEFT OUTER JOIN', 'shop.shop_id = s_l.shoplang_shop_id AND shoplang_lang_id = ' . $this->siteLangId, 's_l');
        $srch->addCondition('preq_id', '=', $preqId);
        $srch->addMultipleFields(array('preq.*', 'user_id', 'user_name', 'credential_email', 'user_phone_dcode', 'user_phone', 'ifnull(shop_name, shop_identifier) as shop_name'));
        $srch->doNotCalculateRecords();
        $srch->setPageSize(1);
        $data = FatApp::getDb()->fetch($srch->getResultSet());

        if ($data == false || $data['preq_deleted'] == applicationConstants::YES || $data['preq_status'] != ProductRequest::STATUS_PENDING) {
            LibHelper::exitWithError($this->str_invalid_request, true);
        }

        if ($status == ProductRequest::STATUS_PENDING) {
            LibHelper::exitWithError(Labels::getLabel('ERR_REQUEST_ALREADY_PENDING', $this->siteLangId), true);
        }

        $db = FatApp::getDb();
        $db->startTransaction();
        $prodReqObj = new ProductRequest($preqId);
        $updateData = array(
            'preq_status' => $status,
            'preq_comment' => $post['preq_comment'],
            'preq_status_updated_on' => date('Y-m-d H:i:s')
        );

        if ($status == ProductRequest::STATUS_CANCELLED) {
            $updateData['preq_product_identifier'] = $data['preq_product_identifier'] . '-' . $preqId . '{cancelled}';
        }

        $prodReqObj->assignValues($updateData);

        if (!$prodReqObj->save()) {
            $db->rollbackTransaction();
            LibHelper::exitWithError($prodReqObj->getError(), true);
        }

        if ($status == ProductRequest::STATUS_APPROVED) {
            $data = array_merge($data, json_decode($data['preq_content'], true));
            $prodObj = new Product();
            $productData = array(
                'product_identifier' => isset($data['product_identifier']) ? $data['product_identifier'] : '',
                'product_type' => isset($data['product_type']) ? $data['product_type'] : '',
                'product_model' => isset($data['product_model']) ? $data['product_model'] : '',
                'product_brand_id' => isset($data['product_brand_id']) ? $data['product_brand_id'] : 0,
                // 'product_seller_id' => isset($data['preq_user_id']) ? $data['preq_user_id'] : 0,
                'product_added_by_admin_id' => applicationConstants::YES,
                'product_min_selling_price' => isset($data['product_min_selling_price']) ? $data['product_min_selling_price'] : 0,
                'product_length' => isset($data['product_length']) ? $data['product_length'] : 0,
                'product_width' => isset($data['product_width']) ? $data['product_width'] : 0,
                'product_height' => isset($data['product_height']) ? $data['product_height'] : 0,
                'product_dimension_unit' => isset($data['product_dimension_unit']) ? $data['product_dimension_unit'] : 0,
                'product_weight' => isset($data['product_weight']) ? $data['product_weight'] : 0,
                'product_weight_unit' => isset($data['product_weight_unit']) ? $data['product_weight_unit'] : 0,
                'product_cod_enabled' => isset($data['product_cod_enabled']) ? $data['product_cod_enabled'] : 0,
                'product_ship_free' => isset($data['ps_free']) ? $data['ps_free'] : 0,
                'product_ship_country' => isset($data['ps_from_country_id']) ? $data['ps_from_country_id'] : 0,
                'product_ship_package' => isset($data['product_ship_package']) ? $data['product_ship_package'] : 0,
                'product_added_on' => date('Y-m-d H:i:s'),
                'product_featured' => isset($data['product_featured']) ? $data['product_featured'] : applicationConstants::NO,
                'product_upc' => isset($data['product_upc']) ? $data['product_upc'] : applicationConstants::NO,
                'product_active' => applicationConstants::YES,
                'product_approved' => applicationConstants::YES,
            );

            $prodObj->assignValues($productData);
            if (!$prodObj->save()) {
                $db->rollbackTransaction();
                $msg = $prodObj->getError();
                if (false !== strpos(strtolower($msg), 'duplicate')) {
                    $msg = Labels::getLabel('ERR_DUPLICATE_RECORD_IDENTIFIER', $this->siteLangId);
                }
                LibHelper::exitWithError($msg, true);
            }

            $product_id = $prodObj->getMainTableRecordId();

            if (0 < FatApp::getConfig('CONF_WITHOUT_PROD_VARIANTS', FatUtility::VAR_INT, 0)) {
                $selprodData = json_decode($data['preq_sel_prod_data'], true);
                unset($selprodData['selprod_id']);
                $selprodData['selprod_user_id'] = $productData['product_seller_id'];
                $selprodData['selprod_product_id'] = $product_id;
                $selProdId = $this->saveInventoryRecord($selprodData, $product_id, 0, $db);
                if (1 > $selProdId) {
                    $db->rollbackTransaction();
                    LibHelper::exitWithError($this->str_invalid_request, true);
                }
            }

            /*TODO
                1) get all the records from tbl_product_digital_data_relation and update record_id with catalog product_id
            */
            $ddSrch = new DigitalDownloadSearch();
            $ddSrch->addCondition('pddr_record_id', '=', $preqId);
            $ddSrch->addCondition('pddr_type', '=', 1);

            $ddSrch->doNotCalculateRecords();
            $rs = $ddSrch->getResultSet();

            $rows = FatApp::getDb()->fetchAll($rs, 'pddr_id');
            if (0 < count($rows)) {
                $pddrIds = array_Keys($rows);

                $pddrIds = FatUtility::int($pddrIds);

                if (!$db->query("UPDATE " . DigitalDownload::DB_TBL . " SET pddr_record_id = " . $product_id . ", pddr_type = 0 WHERE pddr_id IN (" . implode(',', $pddrIds) . ")")) {
                    $db->rollbackTransaction();
                    LibHelper::exitWithError($db->getError(), true);
                }
            }

            if (isset($data['shipping_profile']) && $data['shipping_profile'] > 0) {
                $shipProProdData = array(
                    'shippro_shipprofile_id' => $data['shipping_profile'],
                    'shippro_product_id' => $product_id,
                    'shippro_user_id' => 0
                );
                $spObj = new ShippingProfileProduct();
                if (!$spObj->addProduct($shipProProdData)) {
                    $db->rollbackTransaction();
                    LibHelper::exitWithError($spObj->getError(), true);
                }
            }

            $prodSepc = [
                'ps_product_id' => $product_id,
                'product_warranty' => isset($data['product_warranty']) ? $data['product_warranty'] : 0,
                'product_warranty_unit' => isset($data['product_warranty_unit']) ? $data['product_warranty'] : 0
            ];

            $productSpecificsObj = new ProductSpecifics($product_id);
            $productSpecificsObj->assignValues($prodSepc);
            if (!$productSpecificsObj->addNew(array(), $prodSepc)) {
                $db->rollbackTransaction();
                LibHelper::exitWithError($productSpecificsObj->getError(), true);
            }

            /* saving of product categories[ */
            $product_categories = array($data['preq_prodcat_id']);
            if (!$prodObj->addUpdateProductCategories($product_id, $product_categories)) {
                $db->rollbackTransaction();
                LibHelper::exitWithError($prodObj->getError(), true);
            }

            /* ] */

            /*Save Prodcut tax category [*/
            $prodTaxData = array(
                'ptt_product_id' => $product_id,
                'ptt_taxcat_id' => $data['ptt_taxcat_id'],
            );
            $taxObj = new Tax();
            if (!$taxObj->addUpdateProductTaxCat($prodTaxData)) {
                $db->rollbackTransaction();
                LibHelper::exitWithError($taxObj->getError(), true);
            }

            /*]*/

            /* saving of product options[ */
            $options = isset($data['product_option']) ? $data['product_option'] : array();
            if (!empty($options)) {
                foreach ($options as $index => $optionId) {
                    $optionValIds = isset($data['product_option_values'][$index]) && is_array($data['product_option_values'][$index])  ? implode(",", $data['product_option_values'][$index]) : '';
                    if (!$prodObj->addUpdateProductOption($optionId, $optionValIds)) {
                        $db->rollbackTransaction();
                        LibHelper::exitWithError($prodObj->getError(), true);
                    }
                }
            }

            /*]*/

            /* Update Product seller shipping [*/
            $prodSellerShipArr = array(
                'ps_from_country_id' => $productData['product_ship_country'],
                'ps_free' => $productData['product_ship_free']
            );

            if (!Product::addUpdateProductSellerShipping($product_id, $prodSellerShipArr, 0)) {
                $db->rollbackTransaction();
                LibHelper::exitWithError($db->getError(), true);
            }
            /* ]*/

            /* Saving product shippings [ */
            $shippingArr = isset($data['product_shipping']) ? $data['product_shipping'] : array();
            if (!empty($shippingArr)) {
                if (!Product::addUpdateProductShippingRates($product_id, $shippingArr, 0)) {
                    $db->rollbackTransaction();
                    LibHelper::exitWithError($db->getError(), true);
                }
            }
            /*]*/

            /* Product Lang data insert[*/
            $languages = Language::getAllNames();
            foreach ($languages as $lang_id => $langName) {
                $reqLangData = ProductRequest::getAttributesByLangId($lang_id, $preqId);
                if ($reqLangData == false) {
                    continue;
                }

                $arr = json_decode($reqLangData['preq_lang_data'], true);
                if (!empty($arr)) {
                    $reqLangData = array_merge($reqLangData, json_decode($reqLangData['preq_lang_data'], true));
                }

                $productLangData = array(
                    'productlang_product_id' => $product_id,
                    'productlang_lang_id' => $lang_id,
                    'product_name' => isset($reqLangData['product_name']) ? $reqLangData['product_name'] : $data['product_identifier'],
                    'product_description' => isset($reqLangData['product_description']) ? $reqLangData['product_description'] : '',
                    'product_youtube_video' => isset($reqLangData['product_youtube_video']) ? $reqLangData['product_youtube_video'] : '',
                    'product_tags_string' => '',
                );
                if (!$prodObj->updateLangData($lang_id, $productLangData)) {
                    $db->rollbackTransaction();
                    LibHelper::exitWithError($prodObj->getError(), true);
                }


                /* Saving of product tags[ */
                if (isset($reqLangData['product_tags']) && is_array($reqLangData['product_tags'])) {
                    foreach ($reqLangData['product_tags'] as $tag_id) {
                        if (!$prodObj->addUpdateProductTag($tag_id)) {
                            $db->rollbackTransaction();
                            LibHelper::exitWithError($prodObj->getError(), true);
                        }
                    }
                }
                /*]*/

                /*[ Saving product Specifications */

                if (isset($reqLangData['specifications']) && is_array($reqLangData['specifications'])) {
                    foreach ($reqLangData['specifications'] as $specification) {
                        if (!$prodObj->saveProductSpecifications(0, $lang_id, $specification['name'], $specification['value'], $specification['group'])) {
                            $db->rollbackTransaction();
                            LibHelper::exitWithError($prodObj->getError(), true);
                        }
                    }
                }
                /*]*/
            }
            /*]*/

            Tag::updateProductTagString($product_id);

            /*[ Saving product UPC/EAN/ISBN*/
            $upcCodeData = array();
            if (isset($data['preq_ean_upc_code'])) {
                $upcCodeData = json_decode($data['preq_ean_upc_code'], true);
            }
            $srch = UpcCode::getSearchObject();
            $srch->addCondition('upc_product_id', '!=', $product_id);
            $srch->doNotCalculateRecords();
            $srch->setPageSize(1);
            if (!empty($upcCodeData)) {
                foreach ($upcCodeData as $key => $code) {
                    if (trim($code) == '') {
                        continue;
                    }

                    $options = str_replace('|', ',', $key);

                    $rSrch = clone $srch;
                    $rSrch->addCondition('upc_code', '=', $code);
                    $rs = $rSrch->getResultSet();
                    $totalRecords = FatApp::getDb()->totalRecords($rs);
                    if ($totalRecords > 0) {
                        continue;
                    }

                    $optionSrch = clone $srch;
                    $optionSrch->addCondition('upc_options', '=', $options);
                    $optionSrch->doNotCalculateRecords();
                    $optionSrch->setPageSize(1);
                    $rs = $optionSrch->getResultSet();
                    $row = FatApp::getDb()->fetch($rs);

                    $upcData = array(
                        'upc_code' => $code,
                        'upc_product_id' => $product_id,
                        'upc_options' => $options,
                    );

                    if ($row && $row['upc_product_id'] == $product_id && $row['upc_options'] == $options) {
                        $upcObj = new UpcCode($row['upc_code_id']);
                    } else {
                        $upcObj = new UpcCode();
                    }

                    $upcObj->assignValues($upcData);
                    if (!$upcObj->save()) {
                        $db->rollbackTransaction();
                        LibHelper::exitWithError($upcObj->getError(), true);
                    }
                }
            }

            /*]*/

            /* Updating images[*/
            $where = array('smt' => 'afile_record_id = ? and afile_type = ?', 'vals' => array($preqId, AttachedFile::FILETYPE_CUSTOM_PRODUCT_IMAGE));

            $db->updateFromArray(AttachedFile::DB_TBL, array('afile_record_id' => $product_id, 'afile_type' => AttachedFile::FILETYPE_PRODUCT_IMAGE), $where);
            /*]*/
        }

        $email = new EmailHandler();
        $customCatalogReq = array();
        $customCatalogReq = $data;
        $customCatalogReq['preq_status'] = $post['preq_status'];
        $customCatalogReq['preq_comment'] = $post['preq_comment'];
        if (!$email->sendCustomCatalogRequestStatusChangeNotification($this->siteLangId, $customCatalogReq)) {
            $db->rollbackTransaction();
            LibHelper::exitWithError(Labels::getLabel('ERR_EMAIL_COULD_NOT_BE_SENT', $this->siteLangId), true);
        }

        CalculativeDataRecord::updateSelprodRequestCount();
        CalculativeDataRecord::updateCustomCatalogCount();
        $db->commitTransaction();

        if ($status == ProductRequest::STATUS_APPROVED) {
            Product::updateMinPrices($product_id);
        }
        $this->set('msg', Labels::getLabel('MSG_STATUS_UPDATED_SUCCESSFULLY', $this->siteLangId));
        $this->set('preq_id', $preqId);
        $this->_template->render(false, false, 'json-success.php');
    }

    private function getForm($langId, $productType = 0, $recordId = 0)
    {
        $frm = $this->getCatalogForm($langId, $productType, $recordId, 1);
        $shippingObj = new Shipping($langId);
        $shippingApiEnabled = false;
        if ($shippingObj->getShippingApiObj(0)) {
            $shippingApiEnabled = true;
        }
        if (!$shippingApiEnabled || ($shippingApiEnabled &&  1 == FatApp::getConfig('CONF_MANUAL_SHIPPING_RATES_ADMIN', FatUtility::VAR_INT, 0))) {
            $frm->addSelectBox(Labels::getLabel('FRM_SHIPPING_PROFILE', $langId), 'shipping_profile', ShippingProfile::getProfileArr($langId, 0, true, true));
        }
        return $frm;
    }

    public function digitalDownloadForm($recordId, $type)
    {
        $this->checkEditPrivilege();
        $recordId = FatUtility::int($recordId);
        if (1 > $recordId) {
            LibHelper::exitWithError($this->str_invalid_request_id, true);
        }

        $productData = ProductRequest::getAttributesById($recordId, 'preq_content');
        if ($productData == false) {
            LibHelper::exitWithError($this->str_invalid_request_id, true);
        }

        $productData = json_decode($productData, true);
        if (!isset($productData['product_type']) || $productData['product_type'] != Product::PRODUCT_TYPE_DIGITAL) {
            LibHelper::exitWithError($this->str_invalid_request_id, true);
        }

        if (!array_key_exists($type, applicationConstants::digitalDownloadTypeArr($this->siteLangId))) {
            LibHelper::exitWithError($this->str_invalid_request, true);
        }

        $frm = DigitalDownload::getDownloadForm($this->siteLangId, $type, $recordId);

        $productOptions = ProductRequest::getProductReqOptions($recordId, $this->siteLangId, true);
        $optionCombinations = CommonHelper::combinationOfElementsOfArr($productOptions, 'optionValues', '_');

        $fld = $frm->getField('option_comb_id');
        if (1 > count($optionCombinations)) {
            $frm->removeField($fld);
        } else {
            $optionCombinations = array('0' => Labels::getLabel('FRM_All', $this->siteLangId)) + $optionCombinations;
            $fld->options = $optionCombinations;
        }

        $this->set('frm', $frm);
        $this->set('type', $type);
        $this->set('html', $this->_template->render(false, false, 'products/digital-download-form.php', true));
        $this->_template->render(false, false, 'json-success.php', true, false);
    }

    public function prodSpecifications()
    {
        $recordId = FatApp::getPostedData('recordId', FatUtility::VAR_INT, 0);
        $langId = FatApp::getPostedData('langId', FatUtility::VAR_INT, 0);
        if (1 > $langId) {
            $langId = CommonHelper::getDefaultFormLangId();
        }
        $specifications =  [];
        if (0 < $recordId) {
            $langData = $this->modelClass::getAttributesByLangId($langId, $recordId, 'preq_lang_data');
            if (!empty($langData)) {
                $langData = json_decode($langData, true);
                if (isset($langData['specifications']) && !empty($langData['specifications'])) {
                    foreach ($langData['specifications'] as $specification) {
                        $specifications[] = [
                            'prodspec_id' => '',
                            'prodspec_name' => $specification['name'],
                            'prodspec_value' => $specification['value'],
                            'prodspec_group' => $specification['group'],
                        ];
                    }
                }
            }
        }
        $this->set('productSpecifications', $specifications);
        $this->set('langId', $langId);
        $this->set('html', $this->_template->render(false, false, 'products/prod-specifications.php', true));
        $this->_template->render(false, false, 'json-success.php', true, false);
    }

    public function upcListing()
    {
        $recordId = FatApp::getPostedData('recordId', FatUtility::VAR_INT, 0);
        if ($recordId < 1) {
            LibHelper::exitWithError($this->str_invalid_request, true);
        }
        $langId = FatApp::getPostedData('langId', FatUtility::VAR_INT, 0);
        $productOptions = FatApp::getPostedData('productOptions');
        $type = FatApp::getPostedData('type', FatUtility::VAR_INT, 0);
        $upcCodes = $this->modelClass::getAttributesById($recordId, 'preq_ean_upc_code');
        $upcCodeData = [];
        if (!empty($upcCodes)) {
            $upcCodes = json_decode($upcCodes, true);
            if (!empty($upcCodes)) {
                foreach ($upcCodes as $key => $upcCode) {
                    $upcCodeData[$key]['upc_code'] = $upcCode;
                }
            }
        }

        $optionCombinations = [];
        if ($type == applicationConstants::NO && is_array($productOptions)) {
            $optionCombinations = CommonHelper::combinationOfElementsOfArr($productOptions, 'optionValues');
        }

        $this->set('optionCombinations', $optionCombinations);
        $this->set('upcCodeData', $upcCodeData);
        $this->set('recordId', $recordId);
        $this->set('langId', $langId);
        $this->set('html', $this->_template->render(false, false, 'products/upc-listing.php', true));
        $this->_template->render(false, false, 'json-success.php', true, false);
    }

    public function images($recordId, $optionId = 0, $langId = 0)
    {
        $recordId = FatUtility::int($recordId);
        $optionId = Product::encodeImageOptionSubId($optionId);
        if (1  > $recordId) {
            LibHelper::exitWithError($this->str_invalid_request_id, true);
        }
        $languages = Language::getAllNames();
        if (count($languages) <= 1) {
            $langId =  array_key_first($languages);
        }
        $images = AttachedFile::getMultipleAttachments(AttachedFile::FILETYPE_CUSTOM_PRODUCT_IMAGE, $recordId, $optionId, $langId, (count($languages) <= 1) ? true : false, 0, 0, true);

        $this->set('images', $images);
        $this->set('record_id', $recordId);
        $this->set('isDefaultLayout', FatApp::getPostedData('isDefaultLayout', FatUtility::VAR_INT, 0));
        $this->checkEditPrivilege(true);
        $this->set('html', $this->_template->render(false, false, NULL, true));
        $this->_template->render(false, false, 'json-success.php', true, false);
    }

    public function setup()
    {
        $this->checkEditPrivilege();

        $recordId = FatApp::getPostedData('record_id', FatUtility::VAR_INT, 0);

        $reqStatus = $this->modelClass::getAttributesById($recordId, 'preq_status');
        if (false === $reqStatus ||  $reqStatus != ProductRequest::STATUS_PENDING) {
            LibHelper::exitWithError($this->str_invalid_request, true);
        }

        $productType = FatApp::getPostedData('product_type', FatUtility::VAR_INT, 0);
        $langId = FatApp::getPostedData('lang_id', FatUtility::VAR_INT, 0);
        if (1 > $langId ||  !array_key_exists($productType, Product::getProductTypes($langId))) {
            LibHelper::exitWithError($this->str_invalid_request, true);
        }

        $frm = $this->getForm($langId, $productType, $recordId);
        $post = $frm->getFormDataFromArray(FatApp::getPostedData());
        if (false === $post) {
            LibHelper::exitWithError(current($frm->getValidationErrors()), true);
        }

        $productIdentifier = FatApp::getPostedData('product_identifier', FatUtility::VAR_STRING, '');
        $isValid = ProductRequest::isValidProductIdentifier($productIdentifier, $recordId);
        if (!$isValid) {
            LibHelper::exitWithError(Labels::getLabel('LBL_DUPLICATE_PRODUCT_IDENTIFER'), true);
        }

        /* [select2 data */
        $post['product_brand_id'] = FatApp::getPostedData('product_brand_id', FatUtility::VAR_INT, 0);
        $post['ptc_prodcat_id'] = FatApp::getPostedData('ptc_prodcat_id', FatUtility::VAR_INT, 0);
        $post['ptt_taxcat_id'] = FatApp::getPostedData('ptt_taxcat_id', FatUtility::VAR_INT, 0);
        /* select2 data ] */

        $this->validateGetForm($post);

        $requestStatus = FatApp::getPostedData('request_status', FatUtility::VAR_INT, 0);

        unset($post['btn_submit'], $post['record_id'], $post['product_approved'], $post['request_status']);

        $db = FatApp::getDb();
        $db->startTransaction();

        $data = [
            'preq_brand_id' => $post['product_brand_id'],
            'preq_prodcat_id' => $post['ptc_prodcat_id'],
            'preq_ean_upc_code' => json_encode($post['product_upcs']),
        ];
        $langData =  [
            'product_name' => $post['product_name'],
            'product_description' => $post['product_description'],
            'product_youtube_video' => $post['product_youtube_video'] ?? '',
            'product_tags' => [],
            'specifications' => $post['specifications'] ?? '',
        ];

        if (isset($post['product_tags']) && !empty($post['product_tags'])) {
            $productTags = json_decode($post['product_tags'], true);
            foreach ($productTags as $tag) {
                if (!isset($tag['id'])) {
                    $tagObj = new Tag();
                    $tagObj->assignValues(['tag_name' => $tag['value'], 'tag_lang_id' => $langId]);
                    if (!$tagObj->save()) {
                        $db->rollbackTransaction();
                        LibHelper::exitWithError($tagObj->getError(), true);
                    }
                    $tagId = $tagObj->getMainTableRecordId();
                } else {
                    $tagId = $tag['id'];
                }
                $langData['product_tags'][] = $tagId;
            }
        }

        $data['preq_content'] = [];
        foreach ($post['options'] as $index => $optionId) {
            $data['preq_content']['product_option'][] = $optionId;
            $opValuesArr = array_column(json_decode($post['optionValues'][$index]), 'id');
            $data['preq_content']['product_option_values'][] = $opValuesArr;
        }

        unset(
            $post['options'],
            $post['optionValues'],
            $post['product_upcs'],
            $post['lang_id'],
            $post['record_id'],
            $post['upc_type'],
        );

        $data['preq_content'] = array_merge($data['preq_content'], array_diff_key($post, $langData, $data));
        $data['preq_content'] = json_encode($data['preq_content']);
        $data['preq_status'] = $requestStatus;
        if (ProductRequest::STATUS_CANCELLED == $requestStatus) {
            $productIdentifier .=  '-' . $recordId . '{cancelled}';
        }
        $data['preq_product_identifier'] = $productIdentifier;

        if (0 < FatApp::getConfig('CONF_WITHOUT_PROD_VARIANTS', FatUtility::VAR_INT, 0)) {
            $selProdData = $this->setupInventory(type: 'REQUESTED_CATALOG_PRODUCT');
            $data['preq_sel_prod_data'] = json_encode($selProdData);
        }

        $prodReqObj = new ProductRequest($recordId);
        $prodReqObj->assignValues($data);
        if (!$prodReqObj->save()) {
            $db->rollbackTransaction();
            LibHelper::exitWithError($prodReqObj->getError(), true);
        }

        if (!$prodReqObj->updateLangData($langId, ['preq_lang_data' => json_encode($langData)])) {
            $db->rollbackTransaction();
            LibHelper::exitWithError($prodReqObj->getError(), true);
        }

        $db->commitTransaction();
        $this->set('recordId', $recordId);
        $this->set('msg', $this->str_setup_successful);
        $this->_template->render(false, false, 'json-success.php');
    }

    public function setImageOrder()
    {
        $this->checkEditPrivilege();
        $preqObj = new ProductRequest();
        $post = FatApp::getPostedData();
        $recordId = FatUtility::int($post['record_id']);
        $imageIds = explode('-', $post['ids']);
        $count = 1;
        foreach ($imageIds as $row) {
            $order[$count] = $row;
            $count++;
        }
        if (!$preqObj->updateProdImagesOrder($recordId, AttachedFile::FILETYPE_CUSTOM_PRODUCT_IMAGE, $order)) {
            LibHelper::exitWithError($preqObj->getError(), true);
        }
        $this->set("msg", $this->str_update_record);
        $this->_template->render(false, false, 'json-success.php');
    }

    public function uploadMedia()
    {
        $this->checkEditPrivilege();
        $post = FatApp::getPostedData();
        if (empty($post)) {
            LibHelper::exitWithError(Labels::getLabel('ERR_INVALID_REQUEST_OR_FILE_NOT_SUPPORTED', $this->siteLangId), true);
        }
        if (!is_uploaded_file($_FILES['cropped_image']['tmp_name'])) {
            LibHelper::exitWithError(Labels::getLabel('ERR_PLEASE_SELECT_A_FILE', $this->siteLangId), true);
        }

        $recordId = FatUtility::int($post['record_id']);
        if (1 > $recordId) {
            LibHelper::exitWithError($this->str_invalid_request, true);
        }
        $optionKey = isset($post['option_id']) ? trim((string) $post['option_id']) : '0';
        $optionId = Product::encodeImageOptionSubId($optionKey);

        $languages = Language::getAllNames();
        if (count($languages) > 1) {
            $langId = FatUtility::int($post['lang_id']);
        } else {
            $langId = array_key_first($languages);
        }

        $fileHandlerObj = new AttachedFile();
        if (!$fileHandlerObj->saveImage($_FILES['cropped_image']['tmp_name'], AttachedFile::FILETYPE_CUSTOM_PRODUCT_IMAGE, $recordId, $optionId, $_FILES['cropped_image']['name'], -1, false, $langId)) {
            LibHelper::exitWithError($fileHandlerObj->getError(), true);
        }

        if (count($languages) > 1) {
            $this->set("isDefaultLayout", $langId == 0 && $optionKey === '0');
        } else {
            $this->set("isDefaultLayout", $langId == CommonHelper::getDefaultFormLangId() && $optionKey === '0');
        }

        $this->set("lang_id", $langId);
        $this->set("option_id", $optionKey);
        $this->set("recordId", $recordId);
        $this->set("msg", Labels::getLabel('MSG_FILE_UPLOADED_SUCCESSFULLY', $this->siteLangId));
        $this->_template->render(false, false, 'json-success.php');
    }

    public function deleteImage($recordId, $imageId)
    {
        $this->checkEditPrivilege();
        $recordId = FatUtility::int($recordId);
        $imageId = FatUtility::int($imageId);

        if (1 > $imageId || 1 > $recordId) {
            LibHelper::exitWithError($this->str_invalid_request, true);
        }

        $fileHandlerObj = new AttachedFile();
        $data = $fileHandlerObj::getAttributesById($imageId, ['afile_lang_id', 'afile_record_subid']);
        if (false == $data) {
            LibHelper::exitWithError($this->str_invalid_request, true);
        }

        $productObj = new ProductRequest();
        if (!$productObj->deleteProductImage($recordId, $imageId)) {
            LibHelper::exitWithError($productObj->getError(), true);
        }

        $languages = Language::getAllNames();
        if (count($languages) > 1) {
            $this->set("isDefaultLayout", $data['afile_lang_id'] == 0 &&  $data['afile_record_subid'] == 0);
        } else {
            $this->set("isDefaultLayout", $data['afile_lang_id'] == CommonHelper::getDefaultFormLangId() &&  $data['afile_record_subid'] == 0);
        }
        $this->set("optionId", $data['afile_record_subid']);
        $this->set("langId", $data['afile_lang_id']);
        $this->set("msg", $this->str_delete_record);
        $this->_template->render(false, false, 'json-success.php');
    }

    private function getSeparateImageOptions($preq_id, $lang_id)
    {
        $imgTypesArr = array(0 => Labels::getLabel('LBL_FOR_ALL_OPTIONS', $this->siteLangId));

        if ($preq_id) {
            $reqData = ProductRequest::getAttributesById($preq_id, array('preq_content'));
            if (!empty($reqData)) {
                $reqData = json_decode($reqData['preq_content'], true);
            }
            $productOptions = isset($reqData['product_option']) ? $reqData['product_option'] : array();
            $optionsArr = [];
            if (!empty($productOptions)) {
                foreach ($productOptions as $optionId) {
                    $optionValues = Product::getOptionValues($optionId, $lang_id);
                    if (!empty($optionValues)) {
                        $optionsArr[] = ['optionValues' => $optionValues];
                    }
                }
            }
            if (!empty($optionsArr)) {
                $optionCombinations = CommonHelper::combinationOfElementsOfArr($optionsArr, 'optionValues', '_');
                if (!empty($optionCombinations)) {
                    $imgTypesArr = $imgTypesArr + $optionCombinations;
                }
            }
        }
        return $imgTypesArr;
    }

    public function setupDigitalDownload()
    {
        $this->checkEditPrivilege();

        $recordId = FatApp::getPostedData('record_id', FatUtility::VAR_INT, 0);
        $type = FatApp::getPostedData('download_type', FatUtility::VAR_INT, 0);

        if (1 > $recordId) {
            LibHelper::exitWithError($this->str_invalid_request_id, true);
        }

        $productData = ProductRequest::getAttributesById($recordId, 'preq_content');
        if ($productData == false) {
            LibHelper::exitWithError($this->str_invalid_request_id, true);
        }

        $productData = json_decode($productData, true);
        if (!isset($productData['product_type']) || $productData['product_type'] != Product::PRODUCT_TYPE_DIGITAL) {
            LibHelper::exitWithError($this->str_invalid_request_id, true);
        }

        if (!array_key_exists($type, applicationConstants::digitalDownloadTypeArr($this->siteLangId))) {
            LibHelper::exitWithError($this->str_invalid_request, true);
        }

        $frm = DigitalDownload::getDownloadForm($this->siteLangId, $type, $recordId);
        $post = $frm->getFormDataFromArray(FatApp::getPostedData());
        if (false === $post) {
            LibHelper::exitWithError(current($frm->getValidationErrors()), true);
        }

        $ddpObj = new DigitalDownloadPrivilages();

        $canDo = $ddpObj->canEdit($recordId, Product::CATALOG_TYPE_REQUEST, 0, $this->siteLangId, false, true);
        if (false == $canDo) {
            LibHelper::exitWithError($ddpObj->getError(), true);
        }

        $optionValId = FatApp::getPostedData('option_comb_id', null, 0);
        $post['option_comb_id'] = $optionValId;

        $ddObj = new DigitalDownload();
        $refId = $ddObj->getReferenceId($recordId, $optionValId, Product::CATALOG_TYPE_REQUEST);
        if (1 > $refId) {
            if (!$ddObj->saveReference($recordId, $optionValId, Product::CATALOG_TYPE_REQUEST)) {
                LibHelper::exitWithError($ddObj->getError(), true);
            }
        } else {
            $ddObj->setMainTableRecordId($refId);
        }

        if (applicationConstants::DIGITAL_DOWNLOAD_LINK == $type) {
            $this->setupDigitalLink($ddObj, $post);
        } else {
            $this->setupDigitalFile($ddObj, $post);
        }
    }

    public function getDigitalDownloadLinks()
    {
        $recordId = FatApp::getPostedData('recordId', FatUtility::VAR_INT, 0);
        if (1 > $recordId) {
            LibHelper::exitWithError($this->str_invalid_request, true);
        }

        $optionCombi = FatApp::getPostedData('option_comb', null, '0');
        $langId = FatApp::getPostedData('langId', FatUtility::VAR_INT, 0);

        $ddpObj = new DigitalDownloadPrivilages();

        $canDo = $ddpObj->canEdit($recordId, Product::CATALOG_TYPE_REQUEST, 0, $this->siteLangId, false, true);
        $this->set('canDo', $canDo);

        $links = DigitalDownloadSearch::getLinks($recordId, Product::CATALOG_TYPE_REQUEST, $optionCombi, $langId);
        $languages = array('0' => Labels::getLabel('LBL_All', $this->siteLangId)) + Language::getAllNames();

        $optionArr = ProductRequest::getProductReqOptions($recordId, $this->siteLangId, true);
        $optionCombinations = CommonHelper::combinationOfElementsOfArr($optionArr, 'optionValues', '_');
        $optionCombinations = array('0' => Labels::getLabel('LBL_All', $this->siteLangId)) + $optionCombinations;

        $this->set('links', $links);
        $this->set('languages', $languages);
        $this->set('options', $optionCombinations);
        $this->set('html', $this->_template->render(false, false, NULL, true));
        $this->_template->render(false, false, 'json-success.php', true, false);
    }

    public function getDigitalDownloadAttachments()
    {
        $recordId = FatApp::getPostedData('recordId', FatUtility::VAR_INT, 0);
        $optionComb = FatApp::getPostedData('option_comb', null, '0');
        $langId = FatApp::getPostedData('langId', null, 0);

        if (1 > $recordId) {
            LibHelper::exitWithError($this->str_invalid_request, true);
        }

        $ddpObj = new DigitalDownloadPrivilages();

        $canDo = $ddpObj->canEdit($recordId, Product::CATALOG_TYPE_REQUEST, 0, $this->siteLangId, false, true);
        $this->set('canDo', $canDo);

        $attachments = DigitalDownloadSearch::getAttachments($recordId, Product::CATALOG_TYPE_REQUEST, $optionComb, $langId);

        $attachments = DigitalDownloadSearch::processAttachmentsWithPreview($attachments);

        $this->set('attachments', $attachments);
        $this->set('languages', array('0' => Labels::getLabel('LBL_All', $this->siteLangId)) + Language::getAllNames());
        $optionArr = ProductRequest::getProductReqOptions($recordId, $this->siteLangId, true);
        $optionCombinations = CommonHelper::combinationOfElementsOfArr($optionArr, 'optionValues', '_');
        $optionCombinations = array('0' => Labels::getLabel('LBL_All', $this->siteLangId)) + $optionCombinations;
        $this->set('options', $optionCombinations);
        $this->set('recordId', $recordId);
        $this->set('downloadrefType', Product::CATALOG_TYPE_REQUEST);
        $this->set('html', $this->_template->render(false, false, NULL, true));
        $this->_template->render(false, false, 'json-success.php', true, false);
    }

    public function deleteDigitalLink($linkId, $refId)
    {
        $this->checkEditPrivilege();

        $this->objPrivilege->canEditCustomProductRequests($this->admin_id, true);

        $refId = FatUtility::int($refId);
        $linkId = FatUtility::int($linkId);

        if (1 > $refId || 1 > $linkId) {
            LibHelper::exitWithError(Labels::getLabel('ERR_INVALID_REQUEST', $this->siteLangId), true);
        }

        $reference = DigitalDownload::getAttributesById($refId);

        if (false == $reference) {
            LibHelper::exitWithError(Labels::getLabel('ERR_INVALID_REQUEST', $this->siteLangId), true);
        }

        $link = DigitalDownloadSearch::getLinkDetail($linkId);
        if (1 > count($link)) {
            LibHelper::exitWithError(Labels::getLabel('ERR_INVALID_REQUEST', $this->siteLangId), true);
        }

        $ddpObj = new DigitalDownloadPrivilages();

        $canDo = $ddpObj->canEdit(
            $link['pddr_record_id'],
            $link['pddr_type'],
            0,
            $this->siteLangId,
            false,
            true
        );

        if (false == $canDo) {
            LibHelper::exitWithError($ddpObj->getError(), true);
        }

        $ddObj = new DigitalDownload();

        if (!$ddObj->deleteLink($linkId, $refId)) {
            LibHelper::exitWithError($ddObj->getError(), true);
        }

        $totalLinksCount = DigitalDownloadSearch::getTotalLinksCount($refId);
        $totalAttachmentCount = DigitalDownloadSearch::getTotalAttachmentsCount($refId);

        if (1 > $totalLinksCount && 1 > $totalAttachmentCount) {
            $ddObj->deleteReference($refId);
        }

        FatUtility::dieJsonSuccess(Labels::getLabel('LBL_Removed_successfully', $this->siteLangId));
    }

    public function deleteDigitalFile()
    {
        $this->checkEditPrivilege();
        $refId = FatApp::getPostedData('ref_id', FatUtility::VAR_INT, 0);
        $aFileId = FatApp::getPostedData('afile_id', FatUtility::VAR_INT, 0);
        $isPreviewFile = FatApp::getPostedData('is_preview', FatUtility::VAR_INT, 0);
        $delFullRow = FatApp::getPostedData('frow', FatUtility::VAR_INT, 0);

        if (1 > $refId || 1 > $aFileId) {
            LibHelper::exitWithError($this->str_invalid_request_id, true);
        }

        $reference = DigitalDownload::getAttributesById($refId);
        if (false == $reference) {
            LibHelper::exitWithError($this->str_invalid_request_id, true);
        }

        $ddpObj = new DigitalDownloadPrivilages();

        $canDo = $ddpObj->canEdit(
            $reference['pddr_record_id'],
            $reference['pddr_type'],
            0,
            $this->siteLangId,
            false,
            true
        );

        if (false == $canDo) {
            LibHelper::exitWithError($ddpObj->getError(), true);
        }

        $digDownload = new DigitalDownload();
        if (!$digDownload->deleteAttachment($aFileId, $refId, $isPreviewFile, $delFullRow)) {
            LibHelper::exitWithError($digDownload->getError(), true);
        }

        LibHelper::exitWithSuccess($this->str_delete_record, true);
    }

    public function downloadAttachment($aFileId, $recordId, $requestType, $isPreview = 0)
    {
        $aFileId = FatUtility::int($aFileId);
        $recordId = FatUtility::int($recordId);
        $isPreview = FatUtility::int($isPreview);
        $requestType = FatUtility::int($requestType);

        if (1 > $aFileId || 1 > $recordId) {
            LibHelper::exitWithError(Labels::getLabel("LBL_Invalid_Request", $this->siteLangId), true);
        }

        $ddpObj = new DigitalDownloadPrivilages();
        $canDo = $ddpObj->canDownload($recordId, $requestType, 0, $this->siteLangId, $isPreview, true);

        if (false == $canDo) {
            LibHelper::exitWithError($ddpObj->getError(), true);
        }

        $file = DigitalDownloadSearch::getAttachmentDetail($aFileId, $recordId, $requestType, $isPreview);

        if (1 > count($file)) {
            LibHelper::exitWithError(Labels::getLabel("LBL_File_not_found", $this->siteLangId), true);
        }

        if ($file['pddr_record_id'] != $recordId) {
            LibHelper::exitWithError($this->str_invalid_request, true);
        }

        if (!file_exists(CONF_UPLOADS_PATH . $file['afile_physical_path'])) {
            LibHelper::exitWithError(Labels::getLabel("ERR_FILE_NOT_FOUND", $this->siteLangId), true);
        }

        $fileName = isset($file['afile_physical_path']) ? $file['afile_physical_path'] : '';
        AttachedFile::downloadAttachment($fileName, $file['afile_name']);
    }

    private function setupDigitalFile($ddObj, $post)
    {
        if ((!isset($_FILES['downloadable_file']['tmp_name']) || !is_uploaded_file($_FILES['downloadable_file']['tmp_name']))
            && (!isset($_FILES['preview_file']['tmp_name']) || !is_uploaded_file($_FILES['preview_file']['tmp_name']))
        ) {
            LibHelper::exitWithError(Labels::getLabel('ERR_PLEASE_SELECT_A_FILE', $this->siteLangId), true);
        }

        $langId = FatUtility::int($post['lang_id']);
        $optionComb = $post['option_comb_id'];
        $isPreview = FatUtility::int($post['is_preview']);
        $refFileId = FatUtility::int($post['ref_file_id']);
        $mainFileId = 0;
        if (1 == $isPreview) {
            if (AttachedFile::getAttributesById($refFileId, 'afile_record_id') !=  $ddObj->getMainTableRecordId()) {
                LibHelper::exitWithError($this->str_invalid_request, true);
            }
            if (array_key_exists('downloadable_file', $_FILES)) {
                unset($_FILES['downloadable_file']);
            }
            $mainFileId = $refFileId;
        }

        if (
            isset($_FILES['downloadable_file']['tmp_name'])
            && is_uploaded_file($_FILES['downloadable_file']['tmp_name'])
        ) {
            $mainFileId = $this->setupDigitalMainFile($ddObj, $langId);
            if (1 > $mainFileId) {
                LibHelper::exitWithError($ddObj->getError(), true);
            }
            $attachWithExistingOrders = $post['attach_with_existing_orders'];
            if (1 === $attachWithExistingOrders) {
                $ddObj->attachFileWithOrderedProducts($mainFileId, $post['record_id'], Product::CATALOG_TYPE_PRIMARY, $langId, $optionComb);
            }
        }

        if (
            isset($_FILES['preview_file']['tmp_name'])
            && is_uploaded_file($_FILES['preview_file']['tmp_name'])
        ) {
            if (1 > $this->setupDigitalPreviewFile($ddObj, $langId, $mainFileId)) {
                LibHelper::exitWithError($ddObj->getError(), true);
            }
        }
        $this->set('langId', $langId);
        $this->set('optionComb', $optionComb);
        $this->set('recordId', $post['record_id']);
        $this->set('downloadType', $post['download_type']);
        $this->set('msg', $this->str_setup_successful);
        $this->_template->render(false, false, 'json-success.php');
    }

    protected function getSearchForm($fields = [])
    {
        $frm = new Form('frmRecordSearch');
        if (!empty($fields)) {
            $this->addSortingElements($frm, 'preq_requested_on', applicationConstants::SORT_DESC);
        }
        $frm->setRequiredStarWith('caption');
        $fld = $frm->addTextBox(Labels::getLabel('FRM_KEYWORD', $this->siteLangId), 'keyword');
        $fld->overrideFldType('search');
        $frm->addSelectBox(Labels::getLabel('FRM_SELLER_NAME', $this->siteLangId), 'seller_id', [], '', ['id' => 'searchFrmUserIdJs', 'placeholder' => Labels::getLabel('FRM_SELLER_NAME_OR_EMAIL', $this->siteLangId)]);
        $frm->addSelectBox(Labels::getLabel('FRM_STATUS', $this->siteLangId), 'status', ProductRequest::getStatusArr($this->siteLangId));
        $frm->addDateField(Labels::getLabel('FRM_DATE_FROM', $this->siteLangId), 'date_from', '', array('placeholder' => Labels::getLabel('FRM_DATE_FROM', $this->siteLangId), 'readonly' => 'readonly', 'class' => 'small dateTimeFld field--calender'));
        $frm->addDateField(Labels::getLabel('FRM_DATE_TO', $this->siteLangId), 'date_to', '', array('placeholder' => Labels::getLabel('FRM_DATE_TO', $this->siteLangId), 'readonly' => 'readonly', 'class' => 'small dateTimeFld field--calender'));
        $frm->addHiddenField('', 'page');
        $frm->addHiddenField('', 'preq_id');
        $frm->addHiddenField('', 'total_record_count');
        HtmlHelper::addSearchButton($frm);
        HtmlHelper::addClearButton($frm);/*clearBtn*/

        return $frm;
    }

    private function getStatusForm($recordId = 0)
    {
        $frm = new Form('frmUpdateStatus');
        $statusArr = ProductRequest::getStatusArr($this->siteLangId);
        $frm->addSelectBox(Labels::getLabel('FRM_REQUEST_STATUS', $this->siteLangId), 'preq_status', $statusArr, '', array(), Labels::getLabel('LBL_Select', $this->siteLangId))->requirements()->setRequired();
        $frm->addHiddenField('', 'preq_id', $recordId);
        $frm->addTextArea(Labels::getLabel('FRM_COMMENT', $this->siteLangId), 'preq_comment', '');
        return $frm;
    }

    private function setupDigitalMainFile($ddObj, $langId)
    {
        $fileId = $ddObj->saveAttachment(
            $_FILES['downloadable_file']['tmp_name'],
            $_FILES['downloadable_file']['name'],
            $ddObj->getMainTableRecordId(),
            0,
            $langId
        );
        if (1 > $fileId) {
            return 0;
        }

        return $fileId;
    }

    private function setupDigitalPreviewFile($ddObj, $langId, $mainFileId = 0)
    {
        $fileId = $ddObj->saveAttachment(
            $_FILES['preview_file']['tmp_name'],
            $_FILES['preview_file']['name'],
            $ddObj->getMainTableRecordId(),
            $mainFileId,
            $langId,
            true
        );

        if (1 > $fileId) {
            return 0;
        }

        return $fileId;
    }

    private function setupDigitalLink($ddObj, $post)
    {
        $downloadLink = FatApp::getPostedData('product_downloadable_link', null, '');
        $previewLink = FatApp::getPostedData('product_preview_link', null, '');

        if ('' == $post['product_downloadable_link'] && '' == $post['product_preview_link']) {
            LibHelper::exitWithError(Labels::getLabel('ERR_PLEASE_ADD_LINK', $this->siteLangId), true);
        }

        $langId = FatUtility::int($post['lang_id']);
        $optionComb = $post['option_comb_id'];
        $ddLinkId = FatUtility::int($post['dd_link_id']);

        if (!$ddObj->saveLink($langId, $downloadLink, $previewLink, $ddLinkId)) {
            LibHelper::exitWithError($ddObj->getError(), true);
        }

        $this->set('langId', $langId);
        $this->set('optionComb', $optionComb);
        $this->set('recordId', $post['record_id']);
        $this->set('downloadType', $post['download_type']);
        $this->set('msg', $this->str_setup_successful);
        $this->_template->render(false, false, 'json-success.php');
    }

    protected function getFormColumns(): array
    {
        $emptyCartItemsTblHeadingCols = CacheHelper::get('cProductsTblHeadingCols' . $this->siteLangId, CONF_DEF_CACHE_TIME, '.txt');
        if ($emptyCartItemsTblHeadingCols) {
            return json_decode($emptyCartItemsTblHeadingCols, true);
        }

        $arr = [
            /*  'listSerial' => Labels::getLabel('LBL_SR._NO', $this->siteLangId), */
            'images' => Labels::getLabel('LBL_IMAGES', $this->siteLangId),
            'product_identifier' => Labels::getLabel('LBL_PRODUCT_NAME', $this->siteLangId),
            'shop_name' => Labels::getLabel('LBL_REQUESTED_BY', $this->siteLangId),
            'preq_added_on' => Labels::getLabel('LBL_CREATED_ON', $this->siteLangId),
            'preq_requested_on' => Labels::getLabel('LBL_REQUESTED_ON', $this->siteLangId),
            'preq_status' => Labels::getLabel('LBL_STATUS', $this->siteLangId),
            'action' => Labels::getLabel('LBL_ACTION_BUTTONS', $this->siteLangId),
        ];
        CacheHelper::create('cProductsTblHeadingCols' . $this->siteLangId, json_encode($arr), CacheHelper::TYPE_LABELS);

        return $arr;
    }

    protected function getDefaultColumns(): array
    {
        return [
            /* 'listSerial', */
            'images',
            'product_identifier',
            'shop_name',
            'preq_added_on',
            'preq_requested_on',
            'preq_status',
            'action',
        ];
    }

    protected function excludeKeysForSort($fields = []): array
    {
        return array_diff($fields, ['images', 'shop_name', 'product_identifier'], Common::excludeKeysForSort(['product_identifier']));
    }

    public function getBreadcrumbNodes($action)
    {
        $nodes = array();
        switch ($action) {
            case 'index':
                $pageData = PageLanguageData::getAttributesByKey($this->pageKey, $this->siteLangId);
                $pageTitle = $pageData['plang_title'] ?? Labels::getLabel('LBL_MASTER_PRODUCT_REQUESTS', $this->siteLangId);
                $nodes = [
                    ['title' => $pageTitle]
                ];
                break;
            case 'form':
                $pageData = PageLanguageData::getAttributesByKey('MASTER_PRODUCT_REQUEST_FORM', $this->siteLangId);
                $pageTitle = $pageData['plang_title'] ?? Labels::getLabel('LBL_MASTER_PRODUCT_REQUESTS', $this->siteLangId);
                $nodes = [
                    ['title' => $pageTitle, 'href' => UrlHelper::generateUrl('CustomProducts')],
                    ['title' => Labels::getLabel('LBL_FORM', $this->siteLangId)]
                ];
                break;
            default:
                parent::getBreadcrumbNodes($action);
                break;
        }
        return $nodes;
    }

    public function getComments()
    {
        $recordId = FatApp::getPostedData('recordId', FatUtility::VAR_INT, 0);
        $this->set('description', ProductRequest::getAttributesById($recordId, 'preq_comment'));
        $this->set('html', $this->_template->render(false, false, NULL, true));
        $this->_template->render(false, false, 'json-success.php', true, false);
    }
}
