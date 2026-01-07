<?php

class ProductsController extends ListingBaseController
{
    use CatalogProduct;

    protected string $modelClass = 'Product';
    protected $pageKey = 'MANAGE_PRODUCTS';

    public function __construct($action)
    {
        parent::__construct($action);
        $this->objPrivilege->canViewProducts();
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
            $this->set("canEdit", $this->objPrivilege->canEditProducts($this->admin_id, true));
        } else {
            $this->objPrivilege->canEditProducts();
        }
    }

    public function index()
    {
        $fields = $this->getFormColumns();
        $frmSearch = $this->getSearchForm($fields);
        $frmSearch->fill(FatApp::getPostedData());

        $this->setModel();
        $actionItemsData = HtmlHelper::getDefaultActionItems($fields, $this->modelObj);
        $actionItemsData['newRecordBtnAttrs'] = ['attr' => ['href' => UrlHelper::generateUrl('products', 'form'), 'onclick' => '']];

        $pageData = PageLanguageData::getAttributesByKey($this->pageKey, $this->siteLangId);


        $defaultSrchParm = [];
        if (FatApp::getAction() == 'approvalPending') {
            $actionItemsData['newRecordBtn'] = false;
            $defaultSrchParm['product_approved'] = Product::UNAPPROVED;
            $defaultSrchParm['is_custom_or_catalog'] = applicationConstants::CUSTOM_CATALOG;
            $pageTitle = $pageData['plang_title'] ?? Labels::getLabel('FRM_SELLER_PRODUCT_REQUESTS', $this->siteLangId);
        } else {
            $pageTitle = $pageData['plang_title'] ?? LibHelper::getControllerName(true);
        }

        $actionItemsData['deleteButton'] = true;
        $actionItemsData['searchFrmTemplate'] = 'products/search-form.php';

        $this->set('pageData', $pageData);
        $this->set('pageTitle', $pageTitle);
        $this->set('actionItemsData', $actionItemsData);
        $this->set("frmSearch", $frmSearch);
        $this->set('defaultColumns', $this->getDefaultColumns());
        $this->set('keywordPlaceholder', Labels::getLabel('FRM_SEARCH_BY_PRODUCT_NAME_AND_MODEL', $this->siteLangId));

        $this->checkEditPrivilege(true);
        $this->getListingData($defaultSrchParm);

        $this->_template->addCss(array('css/select2.min.css'));
        $this->_template->addJs(array('products/page-js/index.js', 'js/select2.js'));
        $this->includeFeatherLightJsCss();
        $this->_template->render(true, true, '_partial/listing/index.php');
    }

    public function approvalPending()
    {
        $this->pageKey = 'MANAGE_SELLER_PRODUCTS_REQUEST';
        $this->index();
    }

    public function search()
    {
        $loadPagination = FatApp::getPostedData('loadPagination', FatUtility::VAR_INT, 0);
        $this->getListingData([], $loadPagination);
        $jsonData = [
            'paginationHtml' => $this->_template->render(false, false, '_partial/listing/listing-foot.php', true)
        ];
       
        if (!$loadPagination || !FatUtility::isAjaxCall()) {
            $jsonData['listingHtml'] = $this->_template->render(false, false, 'products/search.php', true);
        }
        LibHelper::exitWithSuccess($jsonData, true);
    }

    private function getListingData($defaultSrchParm = [], $loadPagination = 0)
    {
        $pageSize = applicationConstants::getPageSize(FatApp::getPostedData('pageSize', FatUtility::VAR_INT));

        $fields = $this->getFormColumns();
        $selectedFlds = FatApp::getPostedData('reportColumns', FatUtility::VAR_STRING, '');
        $selectedFlds = !empty($selectedFlds) ? json_decode($selectedFlds) + $this->getDefaultColumns() : $this->getDefaultColumns();

        $fields = FilterHelper::parseArrayByKeys($fields, $selectedFlds, true);
        $allowedKeysForSorting = $this->excludeKeysForSort(array_keys($fields));
        $sortBy = FatApp::getPostedData('sortBy', FatUtility::VAR_STRING, 'product_added_on');
        if (!array_key_exists($sortBy, $fields)) {
            $sortBy = 'product_added_on';
        }

        $sortOrder = applicationConstants::getSortOrder(FatApp::getPostedData('sortOrder', FatUtility::VAR_STRING, applicationConstants::SORT_DESC), applicationConstants::SORT_DESC);

        $searchForm = $this->getSearchForm($fields);

        $page = FatApp::getPostedData('page', FatUtility::VAR_INT, 1);
        $page = ($page <= 0) ? 1 : $page;
        $post = $searchForm->getFormDataFromArray(FatApp::getPostedData());
 
        $sendQueryPost = ['page'=>$page,'keyword'=>$post['keyword']];
        unset($sendQueryPost['total_record_count']);
        $queryString = http_build_query($sendQueryPost);
        $this->set('queryString', $queryString);

        $srch = Product::getSearchObject($this->siteLangId);
        //$srch->joinTable(AttributeGroup::DB_TBL, 'LEFT OUTER JOIN', 'product_attrgrp_id = attrgrp_id', 'attrgrp');
        $srch->joinTable(User::DB_TBL, 'LEFT OUTER JOIN', 'product_seller_id = user_id', 'tu');
        if (isset($post['keyword']) && '' != $post['keyword']) {
            $cnd = $srch->addCondition('product_name', 'like', '%' . $post['keyword'] . '%');
            $cnd->attachCondition('product_model', 'like', '%' . $post['keyword'] . '%', 'OR');
            $cnd->attachCondition('product_identifier', 'like', '%' . $post['keyword'] . '%', 'OR');
        }

        $active = FatApp::getPostedData('active', FatUtility::VAR_STRING, '');
        if ('' != $active && $active > -1) {
            $srch->addCondition('product_active', '=', $active);
        }

        $product_approved = FatApp::getPostedData('product_approved', FatUtility::VAR_INT, ($defaultSrchParm['product_approved'] ?? -1));
        $post['product_approved'] = $product_approved;
        if ($product_approved > -1) {
            $srch->addCondition('product_approved', '=', $product_approved);
        }

        $product_seller_id = FatApp::getPostedData('product_seller_id', FatUtility::VAR_INT, 0);
        $is_custom_or_catalog = FatApp::getPostedData('is_custom_or_catalog', FatUtility::VAR_INT, $defaultSrchParm['is_custom_or_catalog'] ?? -1);
        if (FatApp::getConfig('CONF_ENABLED_SELLER_CUSTOM_PRODUCT')) {
            $post['is_custom_or_catalog'] = $is_custom_or_catalog;
            if ($is_custom_or_catalog == applicationConstants::SYSTEM_CATALOG) {
                $srch->addCondition('product_seller_id', '=', 0);
            } elseif ($is_custom_or_catalog == applicationConstants::CUSTOM_CATALOG) {
                if (0 < $product_seller_id) {
                    $srch->addCondition('product_seller_id', '=', $product_seller_id);
                } else {
                    $srch->addCondition('product_seller_id', '>', 0);
                }
            } else {
                if (0 < $product_seller_id) {
                    $srch->addCondition('product_seller_id', '=', $product_seller_id);
                }
            }
        } else {
            if (0 < $product_seller_id) {
                $srch->addCondition('product_seller_id', '=', $product_seller_id);
            } elseif ($is_custom_or_catalog == applicationConstants::CUSTOM_CATALOG) {
                $srch->addCondition('product_seller_id', '>', 0);
            }
        }

        $prodcat_id = FatApp::getPostedData('prodcat_id', FatUtility::VAR_INT, -1);
        if ($prodcat_id > 0) {
            $srch->joinTable(Product::DB_TBL_PRODUCT_TO_CATEGORY, 'LEFT OUTER JOIN', 'product_id = ptc_product_id', 'ptcat');
            $includeChild = FatApp::getPostedData('include_child', FatUtility::VAR_INT, 0);
            if ($includeChild) {
                $srch->addCondition('mysql_func_GETCATCODE(ptc_prodcat_id)', 'LIKE', '%' . str_pad($prodcat_id, 6, '0', STR_PAD_LEFT) . '%', 'AND', true);
            } else {
                $srch->addCondition('ptcat.ptc_prodcat_id', '=', $prodcat_id);
            }
        }

        $product_type = FatApp::getPostedData('product_type', FatUtility::VAR_INT, 0);
        if ($product_type > 0) {
            $srch->addCondition('product_type', '=', $product_type);
        }

        $date_from = FatApp::getPostedData('date_from', FatUtility::VAR_DATE, '');
        if (!empty($date_from)) {
            $srch->addCondition('tp.product_added_on', '>=', $date_from . ' 00:00:00');
        }

        $date_to = FatApp::getPostedData('date_to', FatUtility::VAR_DATE, '');
        if (!empty($date_to)) {
            $srch->addCondition('tp.product_added_on', '<=', $date_to . ' 23:59:59');
        }

        $recordId = FatApp::getPostedData('recordId', FatUtility::VAR_INT, -1);
        $product_id = FatApp::getPostedData('product_id', FatUtility::VAR_INT, $recordId);
        if (0 < $product_id) {
            $srch->addCondition('product_id', '=', $product_id);
        }

        if ($loadPagination && FatUtility::isAjaxCall()) {
            $this->setRecordCount(clone $srch, $pageSize, $page, $post);
        }
        $srch->doNotCalculateRecords();

        $srch->addMultipleFields(
            array(
                'product_id',
                'product_identifier',
                'product_approved',
                'product_active',
                'product_seller_id',
                'product_added_on',
                'COALESCE(product_name, product_identifier) as product_name',
                'user_name',
                'product_updated_on'
            )
        );

        $srch->setPageNumber($page);
        $srch->setPageSize($pageSize);
        $srch->addOrder($sortBy, $sortOrder);
        $records = [];
        if (!$loadPagination) {
            $records = FatApp::getDb()->fetchAll($srch->getResultSet());
        } //echo $srch->getQuery(); die;

        $this->set('activeInactiveArr', applicationConstants::getActiveInactiveArr($this->siteLangId));
        $this->set("arrListing", $records);
        $this->set('postedData', $post);
        $this->set('frmSearch', $searchForm);
        $this->set('sortBy', $sortBy);
        $this->set('sortOrder', $sortOrder);
        $this->set('fields', $fields);
        $this->set('allowedKeysForSorting', $allowedKeysForSorting);
        $this->set('canEdit', $this->objPrivilege->canEditProducts($this->admin_id, true));
        $this->set('canViewUsers', $this->objPrivilege->canViewUsers($this->admin_id, true));
    }

    public function form($recordId = 0, $productType = 0)
    {
        $this->checkEditPrivilege();

        $pageData = PageLanguageData::getAttributesByKey('ADD_PRODUCT', $this->siteLangId);
        $pageTitle = $pageData['plang_title'] ?? LibHelper::getControllerName(true);

        $this->set('pageData', $pageData);
        $this->set('pageTitle', $pageTitle);

        $recordId = FatUtility::int($recordId);
        $productType = FatUtility::int($productType);

        $langId = FatApp::getPostedData('langId', FatUtility::VAR_INT, 0);
        if (1 > $langId) {
            $langId = CommonHelper::getDefaultFormLangId();
        }

        $frm = $this->getForm($langId, $productType, $recordId);
        if (FatApp::getConfig('CONF_WITHOUT_PROD_VARIANTS', FatUtility::VAR_INT, 0)) {
            $prodSellerIdFld = $frm->getField('product_seller_id');
            $prodSellerIdFld->requirements()->setRequired();
        }

        $imgFrm = $this->getImageFrm();
        $isSelProdCreatedBySeller = false;
        $isProductAddedByAdmin = true;
        $productOptions = [];
        $selProdId = 0;
        if (0 < $recordId) {
            $this->setModel([$recordId]);
            if (0 < FatApp::getPostedData('autoFillLangData', FatUtility::VAR_INT, 0)) {
                $updateLangDataobj = new TranslateLangData($this->modelObj::DB_TBL_LANG);
                $translatedData = $updateLangDataobj->getTranslatedData($recordId, $langId, CommonHelper::getDefaultFormLangId());
                if (false === $translatedData) {
                    LibHelper::exitWithError($updateLangDataobj->getError(), true);
                }
                $productData = current($translatedData);
                $productData += $this->modelObj::getAttributesById($recordId);
            } else {
                $productData = $this->modelObj::getAttributesByLangId($langId, $recordId, null, applicationConstants::JOIN_RIGHT);
            }
            if (empty($productData)) {
                LibHelper::exitWithError($this->str_invalid_request_id, false, true);
                FatApp::redirectUser(UrlHelper::generateUrl('Products'));
            }

            $productData['record_id'] = $recordId;

            if (1 > $productType) {
                $frm = $this->getForm($langId, $productData['product_type'], $recordId);
                $prodSellerIdFld = $frm->getField('product_seller_id');
                $prodSellerIdFld->requirements()->setRequired();
            }

            if ($productData['product_seller_id'] > 0) {
                $userShopName = User::getUserShopName($productData['product_seller_id'], $langId);
                $prodSellerIdFld->options = [$productData['product_seller_id'] => $userShopName['user_name'] . ' (' . $userShopName['shop_name'] . ')'];
            } else if (isset($prodSellerIdFld) && null != $prodSellerIdFld) {
                $prodSellerIdFld->options = [0 => Labels::getLabel('FRM_ADMIN', $langId)];
            }

            $prodSpecificsDetails = Product::getProductSpecificsDetails($recordId);
            if (false != $prodSpecificsDetails) {
                $productData += $prodSpecificsDetails;
            }

            $productTags = Product::getProductTags($recordId, $langId);
            $tagData = [];
            foreach ($productTags as $key => $data) {
                $tagData[$key]['id'] = $data['tag_id'];
                $tagData[$key]['value'] = htmlspecialchars($data['tag_name'], ENT_QUOTES, 'UTF-8');
            }

            $productData['product_tags'] = json_encode($tagData);

            if (0 < $productData['product_brand_id']) {
                $brandData = Brand::getAttributesByLangId($langId, $productData['product_brand_id'], [Brand::tblFld('name'), Brand::tblFld('identifier')], applicationConstants::JOIN_RIGHT, applicationConstants::YES, applicationConstants::NO);
                if (false != $brandData) {
                    $fld = $frm->getField('product_brand_id');
                    $fld->options = [$productData['product_brand_id'] => $brandData[Brand::tblFld('name')] ?? $brandData[Brand::tblFld('identifier')]];
                }
            }

            if (0 < $productData['product_cbrand_id']) {
                $brandData = CompatibleBrand::getAttributesByLangId($langId, $productData['product_cbrand_id'], [CompatibleBrand::tblFld('name'), CompatibleBrand::tblFld('identifier')], applicationConstants::JOIN_RIGHT, applicationConstants::YES, applicationConstants::NO);
                if (false != $brandData) {
                    $fld = $frm->getField('product_cbrand_id');
                    $fld->options = [$productData['product_cbrand_id'] => $brandData[CompatibleBrand::tblFld('name')] ?? $brandData[CompatibleBrand::tblFld('identifier')]];
                }
            }            

            $productCategories = $this->modelObj->getProductCategories($recordId);
            if (!empty($productCategories)) {
                $selectedCat = current($productCategories)['prodcat_id'];
                $productData['ptc_prodcat_id'] = $selectedCat;
                $catData = ProductCategory::getAttributesByLangId($langId, $selectedCat, [ProductCategory::tblFld('name'), ProductCategory::tblFld('identifier')], applicationConstants::JOIN_RIGHT, applicationConstants::YES, applicationConstants::NO);
                if (false != $catData) {
                    $fld = $frm->getField('ptc_prodcat_id');
                    $fld->options = [$productData['ptc_prodcat_id'] => $catData[ProductCategory::tblFld('name')] ?? $catData[ProductCategory::tblFld('identifier')]];
                }
            }

            $taxData = Tax::getTaxCatByProductId($recordId, $productData['product_seller_id'], $langId);
            if (false != $taxData) {
                $productData['ptt_taxcat_id'] = $taxData[Tax::tblFld('id')];
                $fld = $frm->getField('ptt_taxcat_id');
                $fld->options = [$productData['ptt_taxcat_id'] => $taxData[Tax::tblFld('name')] ?? $taxData[Tax::tblFld('identifier')]];
            }

            $prodShippingDetails = Product::getProductShippingDetails($recordId, $langId, $productData['product_seller_id']);

            if (false != $prodShippingDetails) {
                $productData['ps_from_country_id'] = $prodShippingDetails['ps_from_country_id'];
                $countryData = Countries::getAttributesByLangId($langId, $prodShippingDetails['ps_from_country_id'], [Countries::tblFld('name'), Countries::tblFld('code')], applicationConstants::JOIN_RIGHT, applicationConstants::YES);
                if (false != $countryData) {
                    $fld = $frm->getField('ps_from_country_id');
                    if (null != $fld) {
                        $fld->options = [$prodShippingDetails['ps_from_country_id'] => $countryData[Countries::tblFld('name')] ?? $countryData[Countries::tblFld('code')]];
                    }
                }
            }

            /* [ GET ATTACHED PROFILE ID */
            $profileUser = $productData['product_seller_id'];
            if (FatApp::getConfig('CONF_SHIPPED_BY_ADMIN_ONLY', FatUtility::VAR_INT, 0)) {
                $profileUser = 0;
            }
            $profSrch = ShippingProfileProduct::getSearchObject();
            $profSrch->addCondition('shippro_product_id', '=', $recordId);
            $profSrch->addCondition('shippro_user_id', '=', $profileUser);
            $profSrch->doNotCalculateRecords();
            $profSrch->setPageSize(1);
            $profileData = FatApp::getDb()->fetch($profSrch->getResultSet());
            if (!empty($profileData)) {
                $productData['shipping_profile'] = $profileData['profile_id'];
            }

            /* ] */
            $isSelProdCreatedBySeller = 0 < Product::getCatalogProductCount($recordId);
            $isProductAddedByAdmin = applicationConstants::YES == $productData['product_added_by_admin_id'];
            $productOptions = Product::getProductOptions($recordId, $langId, true);

            $srch = new SearchBase(UpcCode::DB_TBL);
            $srch->addCondition('upc_product_id', '=', $recordId);
            $srch->addFld('upc_options');
            $row = FatApp::getDb()->fetch($srch->getResultSet());
            $productData['upc_type'] = applicationConstants::YES;
            if (false != $row) {
                if ($row['upc_options'] != 0) {
                    $productData['upc_type'] = applicationConstants::NO;
                }
            }

            $this->set("productData", [
                'product_type' => $productData['product_type'],
                'product_fulfillment_type' => $productData['product_fulfillment_type'],
                'product_seller_id' => $productData['product_seller_id'],
                'product_attachements_with_inventory' => $productData['product_attachements_with_inventory'],
            ]);

            /* to select product type in get */
            if (0 < $productType) {
                $productData['product_type'] = $productType;
            }

            if (0 < FatApp::getConfig('CONF_WITHOUT_PROD_VARIANTS', FatUtility::VAR_INT, 0)) {
                $selProdId = SellerProduct::getSelprodIdByProductId($recordId);
                $sellerProductRow = SellerProduct::getAttributesById($selProdId, null, true, true);
                if (is_array($sellerProductRow) && !empty($sellerProductRow)) {
                    /*  if (!$sellerProductRow || 1 > $sellerProductRow['selprod_user_id']) {
                        LibHelper::exitWithError($this->str_invalid_request_id, true);
                    } */

                    $sellerProductLangRow = SellerProduct::getAttributesByLangId($langId, $selProdId);
                    $sellerProductLangRow = is_array($sellerProductLangRow) ? $sellerProductLangRow : [];

                    $urlSrch = UrlRewrite::getSearchObject();
                    $urlSrch->doNotCalculateRecords();
                    $urlSrch->doNotLimitRecords();
                    $urlSrch->addFld('urlrewrite_custom');
                    $urlSrch->addCondition('urlrewrite_original', '=', 'products/view/' . $selProdId);
                    $urlSrch->doNotCalculateRecords();
                    $urlSrch->setPageSize(1);
                    $rs = $urlSrch->getResultSet();
                    $urlRow = FatApp::getDb()->fetch($rs);

                    $sellerProductRow['selprod_url_keyword'] = '';
                    if ($urlRow) {
                        $data['urlrewrite_custom'] = $urlRow['urlrewrite_custom'];
                        $customUrl = explode("/", $urlRow['urlrewrite_custom']);
                        $sellerProductRow['selprod_url_keyword'] = $customUrl[0];
                    }

                    $user_shop_name = User::getUserShopName($sellerProductRow['selprod_user_id'], $this->siteLangId);
                    
                    $sellerProductRow['selprod_user_shop_name'] = is_array($user_shop_name) ? $user_shop_name['user_name'] . ' - ' . $user_shop_name['shop_name'] : FatApp::getConfig("CONF_WEBSITE_NAME_" . $this->siteLangId);

                    $returnAge = isset($sellerProductRow['selprod_return_age']) ? FatUtility::int($sellerProductRow['selprod_return_age']) : '';
                    $cancellationAge = isset($sellerProductRow['selprod_cancellation_age']) ? FatUtility::int($sellerProductRow['selprod_cancellation_age']) : '';

                    if ('' === $returnAge || '' === $cancellationAge) {
                        $sellerProductRow['use_shop_policy'] = 1;
                    }
                    $productData = array_merge($productData, $sellerProductRow, $sellerProductLangRow);
                }

            }

            $frm->fill($productData);
            $imgFrm->fill(['file_type' => AttachedFile::FILETYPE_PRODUCT_IMAGE, 'record_id' => $recordId]);
        } else {
            $tempProductId = time() . $this->admin_id;
            $frm->fill(['temp_product_id' => $tempProductId]);
            $imgFrm->fill(['file_type' => AttachedFile::FILETYPE_PRODUCT_IMAGE_TEMP, 'record_id' => $tempProductId]);
        }

        $this->set("selProdId", $selProdId);
        $this->set("frm", $frm);
        $this->set("imgFrm", $imgFrm);

        $codEnabled = true;
        $paymentMethod = new PaymentMethods();
        if (!$paymentMethod->cashOnDeliveryIsActive()) {
            $codEnabled = false;
        }
        $this->set("codEnabled", $codEnabled);
        $this->set("canEditTags", $this->objPrivilege->canEditTags($this->admin_id, true));
        $this->set("langId", $langId);
        $this->set("recordId", $recordId);
        $this->set('hasInventory', Product::hasInventory($recordId));
        $this->set('isSelProdCreatedBySeller', $isSelProdCreatedBySeller);
        $this->set('isProductAddedByAdmin', $isProductAddedByAdmin);
        $this->set('productOptions', $productOptions);
        $this->set('formLayout', Language::getLayoutDirection($langId));
        $this->set('tourStep', SiteTourHelper::getStepIndex());
        if (FatUtility::isAjaxCall()) {
            $this->set('html', $this->_template->render(false, false, NULL, true));
            $this->_template->render(false, false, 'json-success.php', true, false);
            return;
        }

        $this->_template->addJs(array('js/cropper.js', 'js/cropper-main.js', 'js/select2.js', 'js/tagify.min.js', 'js/tagify.polyfills.min.js', 'js/jquery-sortable-lists.js', 'brands/page-js/index.js', 'product-categories/page-js/add-media.js', 'product-categories/page-js/saveCategoryRecord.js'));
        $this->_template->addCss(['css/cropper.css', 'css/tagify.min.css', 'css/select2.min.css']);
        $this->set("includeEditor", true);
        $this->_template->render();
    }

    private function getForm($langId, $productType = 0, $recordId = 0)
    {
        return $this->getCatalogForm($langId, $productType, $recordId);
    }

    public function setup()
    {
        $this->checkEditPrivilege();

        if (0 < FatApp::getConfig('CONF_WITHOUT_PROD_VARIANTS', FatUtility::VAR_INT, 0)) {
            $this->objPrivilege->canEditSellerProducts();
        }

        $recordId = FatApp::getPostedData('record_id', FatUtility::VAR_INT, 0);
        $productType = FatApp::getPostedData('product_type', FatUtility::VAR_INT, 0);
        $langId = FatApp::getPostedData('lang_id', FatUtility::VAR_INT, 0);
        if (1 > $langId || !array_key_exists($productType, Product::getProductTypes($langId))) {
            LibHelper::exitWithError($this->str_invalid_request, true);
        }

        $frm = $this->getForm($langId, $productType, $recordId);
        if (FatApp::getConfig('CONF_WITHOUT_PROD_VARIANTS', FatUtility::VAR_INT, 0)) {
            $fld = $frm->getField('product_seller_id');
            $fld->requirements()->setRequired();
        }

        $post = $frm->getFormDataFromArray(FatApp::getPostedData());
        if (false === $post) {
            LibHelper::exitWithError(current($frm->getValidationErrors()), true);
        }
        /* [select2 data */
        $post['product_brand_id'] = FatApp::getPostedData('product_brand_id', FatUtility::VAR_INT, 0);
        $post['product_cbrand_id'] = FatApp::getPostedData('product_cbrand_id', FatUtility::VAR_INT, 0);
        $post['ptc_prodcat_id'] = FatApp::getPostedData('ptc_prodcat_id', FatUtility::VAR_INT, 0);
        $post['ptt_taxcat_id'] = FatApp::getPostedData('ptt_taxcat_id', FatUtility::VAR_INT, 0);
        $post['ps_from_country_id'] = FatApp::getPostedData('ps_from_country_id', FatUtility::VAR_INT, 0);
        $post['product_seller_id'] = FatApp::getPostedData('product_seller_id', FatUtility::VAR_INT, 0);
        /* select2 data ] */

        $post['shipping_profile'] = FatApp::getPostedData('shipping_profile', FatUtility::VAR_INT, 0);

        $this->validateGetForm($post);

        $recordId = $post['record_id'];
        $langId = $post['lang_id'];
        /* sendApprovalStatusUpdate to seller */
        $sendApprovalStatusUpdate = false;
        $isNewProduct = false;
        if (0 < $recordId && isset($post['product_approved'])) {
            $oldProductData = Product::getAttributesById($recordId, ['product_approved', 'product_seller_id', 'product_active']);
            if (0 < $oldProductData['product_seller_id'] && $oldProductData['product_approved'] != $post['product_approved']) {
                $sendApprovalStatusUpdate = true;
            }

            if (

                (
                    (0 < $post['product_active'] &&
                        $oldProductData['product_active'] != $post['product_active']
                    ) ||
                    (0 < $post['product_approved'] &&
                        $oldProductData['product_approved'] != $post['product_approved']
                    )
                ) &&
                isset($post['product_seller_id']) &&
                0 < $post['product_seller_id'] &&
                FatApp::getConfig('CONF_ENABLE_SELLER_SUBSCRIPTION_MODULE', FatUtility::VAR_INT, 0) &&
                Product::getActiveCount($post['product_seller_id']) >= SellerPackages::getAllowedLimit($post['product_seller_id'], $this->siteLangId, 'ossubs_products_allowed')
            ) {
                LibHelper::exitWithError(Labels::getLabel('ERR_SELLER_SUBSCRIPTION_PACKAGE_LIMIT_CROSSED.', $this->siteLangId), true);
            }
        }

        if (1 > $recordId) {
            $isNewProduct = true;
            $post['product_approved'] = applicationConstants::YES;
        }

        if (Product::PRODUCT_TYPE_PHYSICAL == $productType) {
            $fulfillmentType = -1;
            if ($post['product_seller_id'] && !FatApp::getConfig('CONF_SHIPPED_BY_ADMIN_ONLY', FatUtility::VAR_INT, 0)) {
                $fulfillmentType = Shop::getAttributesByUserId($post['product_seller_id'], 'shop_fulfillment_type');
                $shopDetails = Shop::getAttributesByUserId($post['product_seller_id'], null, false);
                $address = new Address(0, $this->siteLangId);
                $addresses = $address->getData(Address::TYPE_SHOP_PICKUP, $shopDetails['shop_id']);
                $fulfillmentType = empty($addresses) ? Shipping::FULFILMENT_SHIP : $fulfillmentType;
            } else {
                $fulfillmentType = FatApp::getConfig('CONF_FULFILLMENT_TYPE', FatUtility::VAR_INT, -1);
            }

            $post['product_fulfillment_type'] = FatApp::getPostedData('product_fulfillment_type', FatUtility::VAR_INT, 0);
            $fullfilmentOptions = Shipping::getFulFillmentArr($this->siteLangId, $fulfillmentType);
            if (!array_key_exists($post['product_fulfillment_type'], $fullfilmentOptions)) {
                LibHelper::exitWithError($this->str_invalid_request, true);
            }
        }
        /* TODO:
          1) in case productid > 0 (edit product) need to check
          a) product_attachements_with_inventory is yes and attachments/links added with product catalog then return with error to remove the links/files first.
          b) a) product_attachements_with_inventory is no and attachments/links added with inventory then return with error to remove the links/files first.
         */

        $prodObj = new Product($recordId);
        $db = FatApp::getDb();
        $db->startTransaction();
        $post['product_model'] = trim($post['product_model']);
        if (!$prodObj->saveProductData($post)) {
            $db->rollbackTransaction();
            LibHelper::exitWithError($prodObj->getError(), true);
        }
        $recordId = $prodObj->getMainTableRecordId();

        $this->setLangData($prodObj, [
            $prodObj::tblFld('name') => $post[$prodObj::tblFld('name')],
            $prodObj::tblFld('description') => $post[$prodObj::tblFld('description')],
            $prodObj::tblFld('youtube_video') => $post[$prodObj::tblFld('youtube_video')]
        ], $langId);

        if (0 < FatApp::getConfig('CONF_WITHOUT_PROD_VARIANTS', FatUtility::VAR_INT, 0)) {
            $selProdId = $this->setupInventory($recordId, $db);
            if (1 > $selProdId) {
                $db->rollbackTransaction();
                LibHelper::exitWithError($this->str_invalid_request, true);
            }
        }

        if (!$prodObj->saveProductCategory($post['ptc_prodcat_id'])) {
            $db->rollbackTransaction();
            LibHelper::exitWithError($prodObj->getError(), true);
        }

        if (!$prodObj->saveProductTax($post['ptt_taxcat_id'], $post['product_seller_id'])) {
            $db->rollbackTransaction();
            LibHelper::exitWithError($prodObj->getError(), true);
        }

        if (isset($post['specifications']) && is_array($post['specifications'])) {
            foreach ($post['specifications'] as $specification) {
                if (!$prodObj->saveProductSpecifications($specification['id'], $langId, $specification['name'], $specification['value'], $specification['group'])) {
                    $db->rollbackTransaction();
                    LibHelper::exitWithError($prodObj->getError(), true);
                }

                $specId = $prodObj->prodSpecId;
                $autoUpdateOtherLangsData = FatApp::getPostedData('auto_update_other_langs_data', FatUtility::VAR_INT, 0);
                if (0 < $autoUpdateOtherLangsData && 0 < $specId && empty($post['record_id'])) {
                    $languages = Language::getAllNames(false);
                    unset($languages[$langId]);
                    foreach ($languages as $toLangId => $langData) {
                        $translateLangobj = new TranslateLangData(ProdSpecification::DB_TBL);
                        $translatedData = $translateLangobj->directTranslate($specification, $toLangId);
                        if (isset($translatedData[$toLangId]) && !empty($translatedData[$toLangId])) {
                            $translatedData = $translatedData[$toLangId];
                            if (!$prodObj->saveProductSpecifications($specId, $toLangId, $translatedData['name'], $translatedData['value'], $translatedData['group'])) {
                                $db->rollbackTransaction();
                                LibHelper::exitWithError($prodObj->getError(), true);
                            }
                        }
                    }
                }
            }
        }

        $psFree = isset($post['ps_free']) ? $post['ps_free'] : 0;
        if (!$prodObj->saveProductSellerShipping($post['product_seller_id'], $psFree, $post['ps_from_country_id'])) {
            $db->rollbackTransaction();
            LibHelper::exitWithError($prodObj->getError(), true);
        }

        if (isset($post['shipping_profile'])) {
            $sellerShippingProfile = false;
            if (1 > FatApp::getConfig('CONF_SHIPPED_BY_ADMIN_ONLY', FatUtility::VAR_INT, 0) && 0 < $post['product_seller_id']) {
                $defaultShippingProfileId = ShippingProfile::getDefaultProfileId($post['product_seller_id']);
                $shipProProdData = array(
                    'shippro_shipprofile_id' => !empty($post['shipping_profile']) ? $post['shipping_profile'] : $defaultShippingProfileId,
                    'shippro_product_id' => $recordId,
                    'shippro_user_id' => $post['product_seller_id'],
                );
                $spObj = new ShippingProfileProduct();
                if (!$spObj->addProduct($shipProProdData)) {
                    $db->rollbackTransaction();
                    LibHelper::exitWithError($spObj->getError(), true);
                }
                $sellerShippingProfile = true;
            }

            $defaultShippingProfileId = ShippingProfile::getDefaultProfileId(0);
            $shipProProdData = array(
                'shippro_shipprofile_id' => !empty($post['shipping_profile']) && false == $sellerShippingProfile ? $post['shipping_profile'] : $defaultShippingProfileId,
                'shippro_product_id' => $recordId,
                'shippro_user_id' => 0,
            );
            $spObj = new ShippingProfileProduct();
            if (!$spObj->addProduct($shipProProdData)) {
                $db->rollbackTransaction();
                LibHelper::exitWithError($spObj->getError(), true);
            }
        }

        $productSpecifics = new ProductSpecifics($recordId);
        $productSpecifics->assignValues(($post + ['ps_product_id' => $recordId]));
        $data = $productSpecifics->getFlds();
        if (!$productSpecifics->addNew(array(), $data)) {
            $db->rollbackTransaction();
            LibHelper::exitWithError($productSpecifics->getError(), true);
        }

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
                if (!$prodObj->addUpdateProductTag($tagId)) {
                    $db->rollbackTransaction();
                    LibHelper::exitWithError($prodObj->getError(), true);
                }
            }
        }

        if (isset($post['options']) && isset($post['optionValues'])) {
            foreach ($post['options'] as $index => $optionId) {
                $opValuesArr = array_column(json_decode($post['optionValues'][$index]), 'id');
                if (!$prodObj->addUpdateProductOption($optionId, implode(",", $opValuesArr))) {
                    $db->rollbackTransaction();
                    LibHelper::exitWithError($prodObj->getError(), true);
                }
            }
        }

        UpcCode::remove($recordId);
        if (isset($post['product_upcs']) && !empty($post['product_upcs'])) {
            foreach ($post['product_upcs'] as $optionsIds => $upcCode) {
                $dataToSave = array(
                    'upc_code' => $upcCode,
                    'upc_product_id' => $recordId,
                    'upc_options' => $optionsIds,
                );
                if (!$db->insertFromArray(UpcCode::DB_TBL, $dataToSave, false, [], $dataToSave)) {
                    $db->rollbackTransaction();
                    LibHelper::exitWithError($db->getError(), true);
                }
            }
        }

        if (true == $sendApprovalStatusUpdate) {
            $email = new EmailHandler();
            $emailData['status'] = $post['product_approved'];
            $emailData['product_name'] = $post['product_name'];
            $emailData['seller_id'] = $oldProductData['product_seller_id'];
            if (!$email->sendCatalogRequestStatusChangeNotification($langId, $emailData)) {
                $db->rollbackTransaction();
                LibHelper::exitWithError(Labels::getLabel('ERR_EMAIL_COULD_NOT_BE_SENT', $langId), true);
            }
        }
        Tag::updateProductTagString($recordId);
        Product::updateMinPrices($recordId);
        if ($isNewProduct) {
            $prodObj->moveTempFiles($post['temp_product_id']);
        }
        $db->commitTransaction();
        CalculativeDataRecord::updateSelprodRequestCount();
        $this->set('recordId', $recordId);
        $this->set('msg', $this->str_setup_successful);
        $this->_template->render(false, false, 'json-success.php');
    }

    public function removeProductOption()
    {
        $this->checkEditPrivilege();

        $recordId = FatApp::getPostedData('recordId', FatUtility::VAR_INT, 0);
        $optionId = FatApp::getPostedData('optionId', FatUtility::VAR_INT, 0);

        if (1 > $recordId || 1 > $optionId) {
            LibHelper::exitWithError($this->str_invalid_request, true);
        }

        if (SellerProduct::isOptionLinked($optionId, $recordId)) {
            LibHelper::exitWithError(Labels::getLabel('ERR_OPTION_IS_LINKED_WITH_SELLER_INVENTORY', $this->siteLangId), true);
        }

        $prodObj = new Product($recordId);
        if (!$prodObj->removeProductOption($optionId)) {
            LibHelper::exitWithError($prodObj->getError(), true);
        }
        UpcCode::remove($recordId);
        $this->set('msg', Labels::getLabel('MSG_OPTION_REMOVED_SUCCESSFULLY', $this->siteLangId));
        $this->_template->render(false, false, 'json-success.php');
    }

    /** use while deleting opvalue from catalog form */
    public function canDeleteOpValue()
    {
        $recordId = FatApp::getPostedData('recordId', FatUtility::VAR_INT, 0);
        $optionId = FatApp::getPostedData('optionId', FatUtility::VAR_INT, 0);
        $optionValueId = FatApp::getPostedData('optionValueId', FatUtility::VAR_INT, 0);

        if (1 > $recordId || 1 > $optionId || 1 > $optionValueId) {
            LibHelper::exitWithError($this->str_invalid_request, true);
        }

        if (SellerProduct::isOptionValueLinked($optionId, $optionValueId, $recordId)) {
            LibHelper::exitWithError(Labels::getLabel('ERR_OPTION_VALUE_IS_LINKED_WITH_SELLER_INVENTORY', $this->siteLangId), true);
        }
        FatUtility::dieJsonSuccess('');
    }

    public function updateProductTag()
    {
        $this->checkEditPrivilege();
        $recordId = FatApp::getPostedData('product_id', FatUtility::VAR_INT, 0);
        $tagId = FatApp::getPostedData('tag_id', FatUtility::VAR_INT, 0);
        if ($recordId < 1 || $tagId < 1) {
            LibHelper::exitWithError($this->str_invalid_request, true);
        }
        $prod = new Product($recordId);
        if (!$prod->addUpdateProductTag($tagId)) {
            LibHelper::exitWithError($prod->getError(), true);
        }

        Tag::updateProductTagString($recordId);

        $this->set('msg', Labels::getLabel('MSG_RECORD_UPDATED_SUCCESSFULLY', $this->siteLangId));
        $this->_template->render(false, false, 'json-success.php');
    }

    public function removeProductTag()
    {
        $this->checkEditPrivilege();
        $recordId = FatApp::getPostedData('product_id', FatUtility::VAR_INT, 0);
        $tagId = FatApp::getPostedData('tag_id', FatUtility::VAR_INT, 0);
        if ($recordId < 1 || $tagId < 1) {
            LibHelper::exitWithError($this->str_invalid_request, true);
        }

        $prod = new Product($recordId);
        if (!$prod->removeProductTag($tagId)) {
            LibHelper::exitWithError($prod->getError(), true);
        }

        Tag::updateProductTagString($recordId);

        $this->set('msg', Labels::getLabel('MSG_TAG_REMOVED_SUCCESSFULLY', $this->siteLangId));
        $this->_template->render(false, false, 'json-success.php');
    }

    public function autoComplete()
    {
        $srch = Product::getSearchObject($this->siteLangId);
        $srch->doNotCalculateRecords();
        $srch->doNotLimitRecords();
        $keyword = FatApp::getPostedData('keyword', FatUtility::VAR_STRING, '');
        if (!empty($keyword)) {
            $cnd = $srch->addCondition('product_name', 'LIKE', '%' . $keyword . '%');
            $cnd->attachCondition('product_identifier', 'LIKE', '%' . $keyword . '%', 'OR');
        }

        $sellerId = FatApp::getPostedData('product_seller_id', FatUtility::VAR_STRING, '');
        if (!empty($sellerId)) {
            $srch->addCondition('product_seller_id', '=', $sellerId);
        }

        $excludeRecords = FatApp::getPostedData('excludeRecords', FatUtility::VAR_INT);
        if (!empty($excludeRecords) && is_array($excludeRecords)) {
            $srch->addCondition('product_id', 'NOT IN', $excludeRecords);
        }

        $srch->addMultipleFields(array('product_id as id', 'COALESCE(product_name, product_identifier) as text'));
        $products = FatApp::getDb()->fetchAll($srch->getResultSet());
        $json['results'] = $products;
        die(json_encode($json));
    }

    protected function getSearchForm($fields = [])
    {
        $frm = new Form('frmRecordSearch');
        if (!empty($fields)) {
            $this->addSortingElements($frm, 'product_added_on', applicationConstants::SORT_DESC);
        }
        $frm->setRequiredStarWith('caption');
        $fld = $frm->addTextBox(Labels::getLabel('FRM_KEYWORD', $this->siteLangId), 'keyword');
        $fld->overrideFldType('search');
        $frm->addHiddenField('', 'include_child', FatApp::getPostedData('include_child', FatUtility::VAR_INT, applicationConstants::NO));
        if (FatApp::getAction() == 'approvalPending') {
            $frm->addHiddenField('', 'is_custom_or_catalog', applicationConstants::CUSTOM_CATALOG);
        } else {
            if (FatApp::getConfig('CONF_ENABLED_SELLER_CUSTOM_PRODUCT')) {
                $frm->addSelectBox(Labels::getLabel('FRM_PRODUCT', $this->siteLangId), 'is_custom_or_catalog', array(-1 => Labels::getLabel('FRM_ALL', $this->siteLangId)) + applicationConstants::getCatalogTypeArr($this->siteLangId), -1, array(), '');
            }
        }

        $frm->addSelectBox(Labels::getLabel('FRM_SELLER_NAME', $this->siteLangId), 'product_seller_id', []);
        $prodCatObj = new ProductCategory();
        $arrCategories = $prodCatObj->getCategoriesForSelectBox($this->siteLangId);
        $categories = $prodCatObj->makeAssociativeArray($arrCategories);
        $prodCat = FatApp::getPostedData('prodcat_id', FatUtility::VAR_INT, 0);
        $frm->addSelectBox(Labels::getLabel('FRM_CATEGORY', $this->siteLangId), 'prodcat_id', $categories, $prodCat);

        $activeInactiveArr = applicationConstants::getActiveInactiveArr($this->siteLangId);
        $frm->addSelectBox(Labels::getLabel('FRM_ACTIVATION_STATUS', $this->siteLangId), 'active', array(-1 => Labels::getLabel('FRM_DOES_NOT_MATTER', $this->siteLangId)) + $activeInactiveArr, '', array(), '');

        if (FatApp::getAction() == 'approvalPending') {
            $frm->addHiddenField('', 'product_approved', Product::UNAPPROVED);
        } else {
            $approveUnApproveArr = Product::getApproveUnApproveArr($this->siteLangId);
            $frm->addSelectBox(Labels::getLabel('FRM_APPROVAL_STATUS', $this->siteLangId), 'product_approved', array(-1 => Labels::getLabel('FRM_DOES_NOT_MATTER', $this->siteLangId)) + $approveUnApproveArr, '', array(), '');
        }

        $frm->addSelectBox(Labels::getLabel('FRM_PRODUCT_TYPE', $this->siteLangId), 'product_type', Product::getProductTypes($this->siteLangId), array(), [], Labels::getLabel('FRM_SELECT', $this->siteLangId));

        $frm->addDateField(Labels::getLabel('FRM_DATE_FROM', $this->siteLangId), 'date_from', '', array('placeholder' => Labels::getLabel('FRM_DATE_FROM', $this->siteLangId), 'readonly' => 'readonly', 'class' => 'small dateTimeFld field--calender'));
        $frm->addDateField(Labels::getLabel('FRM_DATE_TO', $this->siteLangId), 'date_to', '', array('placeholder' => Labels::getLabel('FRM_DATE_TO', $this->siteLangId), 'readonly' => 'readonly', 'class' => 'small dateTimeFld field--calender'));
        $frm->addHiddenField('', 'page', 1);
        $frm->addHiddenField('', 'product_id');
        $frm->addHiddenField('', 'total_record_count');
        HtmlHelper::addSearchButton($frm);
        HtmlHelper::addClearButton($frm);/*clearBtn*/

        return $frm;
    }

    public function deleteProduct()
    {
        $this->checkEditPrivilege();
        $recordId = FatApp::getPostedData('recordId', FatUtility::VAR_INT, 0);
        if (1 > $recordId) {
            LibHelper::exitWithError($this->str_invalid_request_id, true);
        }

        $this->markAsDeleted($recordId);
        Product::updateMinPrices($recordId);
        $this->set("msg", $this->str_delete_record);
        FatUtility::dieJsonSuccess($this->str_delete_record);
        $this->_template->render(false, false, 'json-success.php');
    }

    public function deleteSelected()
    {
        $this->checkEditPrivilege();
        $recordIdsArr = FatUtility::int(FatApp::getPostedData('record_ids'));

        if (empty($recordIdsArr)) {
            LibHelper::exitWithError($this->str_invalid_request_id, true);
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

    public function images($recordId, $fileType = 0, $optionId = 0, $langId = 0)
    {
        $recordId = FatUtility::int($recordId);
        $fileType = FatUtility::int($fileType);
        if (1 > $recordId) {
            LibHelper::exitWithError($this->str_invalid_request_id, true);
        }

        $languages = Language::getAllNames();
        if (count($languages) <= 1) {
            $langId = array_key_first($languages);
        }

        if ($fileType == AttachedFile::FILETYPE_PRODUCT_IMAGE_TEMP) {
            $images = AttachedFileTemp::getMultipleAttachments($fileType, $recordId, $optionId, $langId, (count($languages) <= 1) ? true : false, 0, 0, true);
        } else {
            $fileType = AttachedFile::FILETYPE_PRODUCT_IMAGE;
            if (!Product::getAttributesById($recordId, 'product_id')) {
                LibHelper::exitWithError($this->str_invalid_request_id, true);
            }
            $images = AttachedFile::getMultipleAttachments($fileType, $recordId, $optionId, $langId, (count($languages) <= 1) ? true : false, 0, 0, true);
        }

        $this->set('images', $images);
        $this->set('recordId', $recordId);
        $this->set('isDefaultLayout', FatApp::getPostedData('isDefaultLayout', FatUtility::VAR_INT, 0));
        $this->checkEditPrivilege(true);
        $this->set('html', $this->_template->render(false, false, NULL, true));
        $this->_template->render(false, false, 'json-success.php', true, false);
    }

    public function setImageOrder()
    {
        $this->checkEditPrivilege();
        $post = FatApp::getPostedData();
        $recordId = FatUtility::int($post['record_id']);
        $fileType = FatUtility::int($post['file_type']);
        $imageIds = explode('-', $post['ids']);
        $count = 1;
        foreach ($imageIds as $row) {
            $order[$count] = $row;
            $count++;
        }
        $product = new Product();
        if (!$product->updateProdImagesOrder($recordId, $fileType, $order)) {
            LibHelper::exitWithError($product->getError(), true);
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

        $recordId = $recordId = FatUtility::int($post['record_id']);
        $optionId = FatUtility::int($post['option_id']);
        $fileType = FatUtility::int($post['file_type']);
        if (!in_array($fileType, [AttachedFile::FILETYPE_PRODUCT_IMAGE, AttachedFile::FILETYPE_PRODUCT_IMAGE_TEMP])) {
            LibHelper::exitWithError($this->str_invalid_request, true);
        }

        if (1 > $recordId) {
            LibHelper::exitWithError($this->str_invalid_request, true);
        }

        $languages = Language::getAllNames();
        if (count($languages) > 1) {
            $langId = FatUtility::int($post['lang_id']);
        } else {
            $langId = array_key_first($languages);
        }
        $sellerId = (int) Product::getAttributesById($recordId, 'product_seller_id');
        $this->validateImageSubscriptionLimit($recordId, $optionId, $langId, $fileType, $sellerId);

        if ($fileType == AttachedFile::FILETYPE_PRODUCT_IMAGE_TEMP) {
            $fileHandlerObj = new AttachedFileTemp();
            $fileHandlerObj->setDownloadedAttr(true);
        } else {
            $fileHandlerObj = new AttachedFile();
        }
        if (!$fileHandlerObj->saveImage($_FILES['cropped_image']['tmp_name'], $fileType, $recordId, $optionId, $_FILES['cropped_image']['name'], -1, false, $langId)) {
            LibHelper::exitWithError($fileHandlerObj->getError(), true);
        }

        if (AttachedFile::FILETYPE_PRODUCT_IMAGE == $fileType) {
            FatApp::getDb()->updateFromArray('tbl_products', array('product_img_updated_on' => date('Y-m-d H:i:s')), array('smt' => 'product_id = ?', 'vals' => array($recordId)));
        }

        if (count($languages) > 1) {
            $this->set("isDefaultLayout", $langId == 0 && $optionId == 0);
        } else {
            $this->set("isDefaultLayout", $langId == CommonHelper::getDefaultFormLangId() && $optionId == 0);
        }

        $this->set("lang_id", $langId);
        $this->set("option_id", $optionId);
        $this->set("product_id", $recordId);
        $this->set("file_type", $fileType);
        $this->set("msg", Labels::getLabel('MSG_FILE_UPLOADED_SUCCESSFULLY', $this->siteLangId));
        $this->_template->render(false, false, 'json-success.php');
    }

    public function deleteImage($recordId, $imageId, $fileType)
    {
        $this->checkEditPrivilege();
        $recordId = FatUtility::int($recordId);
        $imageId = FatUtility::int($imageId);
        $fileType = FatUtility::int($fileType);

        if (1 > $imageId || 1 > $recordId || 1 > $fileType) {
            LibHelper::exitWithError($this->str_invalid_request, true);
        }

        if ($fileType == AttachedFile::FILETYPE_PRODUCT_IMAGE_TEMP) {
            $fileHandlerObj = new AttachedFileTemp();
        } else {
            $fileHandlerObj = new AttachedFile();
        }

        $data = $fileHandlerObj::getAttributesById($imageId, ['afile_lang_id', 'afile_record_subid']);
        if (false == $data) {
            LibHelper::exitWithError($this->str_invalid_request, true);
        }

        $productObj = new Product();
        if (!$productObj->deleteProductImage($recordId, $imageId, $fileType)) {
            LibHelper::exitWithError($productObj->getError(), true);
        }

        FatApp::getDb()->updateFromArray('tbl_products', array('product_img_updated_on' => date('Y-m-d H:i:s')), array('smt' => 'product_id = ?', 'vals' => array($recordId)));
        $languages = Language::getAllNames();
        if (count($languages) > 1) {
            $this->set("isDefaultLayout", $data['afile_lang_id'] == 0 && $data['afile_record_subid'] == 0);
        } else {
            $this->set("isDefaultLayout", $data['afile_lang_id'] == CommonHelper::getDefaultFormLangId() && $data['afile_record_subid'] == 0);
        }
        $this->set("optionId", $data['afile_record_subid']);
        $this->set("langId", $data['afile_lang_id']);
        $this->set("msg", $this->str_delete_record);
        $this->_template->render(false, false, 'json-success.php');
    }

    public function prodSpecifications()
    {
        $recordId = FatApp::getPostedData('recordId', FatUtility::VAR_INT, 0);
        $langId = FatApp::getPostedData('langId', FatUtility::VAR_INT, 0);
        if (1 > $langId) {
            $langId = CommonHelper::getDefaultFormLangId();
        }
        $productSpecifications = [];
        if (0 < $recordId) {
            $prod = new Product($recordId);
            $productSpecifications = $prod->getProdSpecificationsByLangId($langId);
        }
        $this->set('productSpecifications', $productSpecifications);
        $this->set('langId', $langId);
        $this->set('html', $this->_template->render(false, false, NULL, true));
        $this->_template->render(false, false, 'json-success.php', true, false);
    }

    public function deleteProdSpec()
    {
        $this->checkEditPrivilege();
        $prodSpecId = FatApp::getPostedData('prodSpecId', FatUtility::VAR_INT, 0);
        $prodSpecLangId = FatApp::getPostedData('prodSpecLangId', FatUtility::VAR_INT, 0);

        if ($prodSpecId < 1) {
            LibHelper::exitWithError($this->str_invalid_request, true);
        }

        $productId = ProdSpecification::getAttributesById($prodSpecId, 'prodspec_product_id');
        if (1 > $productId) {
            LibHelper::exitWithError($this->str_invalid_request, true);
        }

        $prodSpec = new ProdSpecification($prodSpecId);
        if (!$prodSpec->deleteRecords($prodSpecLangId)) {
            LibHelper::exitWithError($prodSpec->getError(), true);
        }

        $this->set('msg', $this->str_delete_record);
        $this->_template->render(false, false, 'json-success.php');
    }

    public function upcListing()
    {
        $recordId = FatApp::getPostedData('recordId', FatUtility::VAR_INT, 0);
        $langId = FatApp::getPostedData('langId', FatUtility::VAR_INT, 0);
        $productOptions = FatApp::getPostedData('productOptions');
        $type = FatApp::getPostedData('type', FatUtility::VAR_INT, 0);

        $upcCodeData = [];
        if (0 < $recordId) {
            $srch = UpcCode::getSearchObject();
            $srch->addCondition('upc_product_id', '=', $recordId);
            $srch->doNotCalculateRecords();
            $upcCodeData = FatApp::getDb()->fetchAll($srch->getResultSet(), 'upc_options');
        }

        $optionCombinations = [];
        if ($type == applicationConstants::NO && is_array($productOptions)) {
            $optionCombinations = CommonHelper::combinationOfElementsOfArr($productOptions, 'optionValues');
        }
        // $productOptions = Product::getProductOptions($recordId, $this->siteLangId, true);

        $this->set('optionCombinations', $optionCombinations);
        $this->set('upcCodeData', $upcCodeData);
        $this->set('recordId', $recordId);
        $this->set('langId', $langId);
        $this->set('html', $this->_template->render(false, false, NULL, true));
        $this->_template->render(false, false, 'json-success.php', true, false);
    }

    public function digitalDownloadForm($recordId, $type)
    {
        $this->checkEditPrivilege();
        $recordId = FatUtility::int($recordId);
        if (1 > $recordId) {
            LibHelper::exitWithError($this->str_invalid_request_id, true);
        }

        $productType = Product::getAttributesById($recordId, 'product_type');
        if ($productType == false || $productType != Product::PRODUCT_TYPE_DIGITAL) {
            LibHelper::exitWithError($this->str_invalid_request_id, true);
        }

        if (!array_key_exists($type, applicationConstants::digitalDownloadTypeArr($this->siteLangId))) {
            LibHelper::exitWithError($this->str_invalid_request, true);
        }

        $frm = DigitalDownload::getDownloadForm($this->siteLangId, $type, $recordId);

        $productOptions = Product::getProductOptions($recordId, $this->siteLangId, true);
        $optionCombinations = CommonHelper::combinationOfElementsOfArr($productOptions, 'optionValues', '_');

        $fld = $frm->getField('option_comb_id');
        if (1 > count($optionCombinations)) {
            $frm->removeField($fld);
        } else {
            $optionCombinations = array('0' => Labels::getLabel('FRM_All', $this->siteLangId)) + $optionCombinations;
            $fld->options = $optionCombinations;
        }

        $formTitle = Labels::getLabel('LBL_DIGITAL_LINKS_SETUP', $this->siteLangId);
        if ($type == applicationConstants::DIGITAL_DOWNLOAD_FILE) {
            $formTitle = Labels::getLabel('LBL_DIGITAL_FILES_ATTACHMENT_SETUP', $this->siteLangId);
        }

        $this->set('frm', $frm);
        $this->set('type', $type);
        $this->set('formTitle', $formTitle);
        $this->set('html', $this->_template->render(false, false, NULL, true));
        $this->_template->render(false, false, 'json-success.php', true, false);
    }

    public function setupDigitalDownload()
    {
        $this->checkEditPrivilege();

        $recordId = FatApp::getPostedData('record_id', FatUtility::VAR_INT, 0);
        $type = FatApp::getPostedData('download_type', FatUtility::VAR_INT, 0);

        if (1 > $recordId) {
            LibHelper::exitWithError($this->str_invalid_request_id, true);
        }

        $productType = Product::getAttributesById($recordId, 'product_type');
        if ($productType == false || $productType != Product::PRODUCT_TYPE_DIGITAL) {
            LibHelper::exitWithError($this->str_invalid_request, true);
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

        $canDo = $ddpObj->canEdit($recordId, Product::CATALOG_TYPE_PRIMARY, 0, $this->siteLangId, false, true);
        if (false == $canDo) {
            LibHelper::exitWithError($ddpObj->getError(), true);
        }

        $optionValId = FatApp::getPostedData('option_comb_id', null, 0);
        $post['option_comb_id'] = $optionValId;

        $ddObj = new DigitalDownload();
        $refId = $ddObj->getReferenceId($recordId, $optionValId);
        if (1 > $refId) {
            if (!$ddObj->saveReference($recordId, $optionValId)) {
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

    private function setupDigitalFile($ddObj, $post)
    {
        if ((!isset($_FILES['downloadable_file']['tmp_name']) || !is_uploaded_file($_FILES['downloadable_file']['tmp_name'])) && (!isset($_FILES['preview_file']['tmp_name']) || !is_uploaded_file($_FILES['preview_file']['tmp_name']))
        ) {
            LibHelper::exitWithError(Labels::getLabel('ERR_PLEASE_SELECT_A_FILE', $this->siteLangId), true);
        }

        $langId = FatUtility::int($post['lang_id']);
        $optionComb = $post['option_comb_id'];
        $isPreview = FatUtility::int($post['is_preview']);
        $refFileId = FatUtility::int($post['ref_file_id']);
        $mainFileId = 0;
        if (1 == $isPreview) {
            if (AttachedFile::getAttributesById($refFileId, 'afile_record_id') != $ddObj->getMainTableRecordId()) {
                LibHelper::exitWithError($this->str_invalid_request, true);
            }
            if (array_key_exists('downloadable_file', $_FILES)) {
                unset($_FILES['downloadable_file']);
            }
            $mainFileId = $refFileId;
        }

        if (
            isset($_FILES['downloadable_file']['tmp_name']) && is_uploaded_file($_FILES['downloadable_file']['tmp_name'])
        ) {
            $mainFileId = $this->setupDigitalMainFile($ddObj, $langId);
            if (1 > $mainFileId) {
                LibHelper::exitWithError($ddObj->getError(), true);
            }

            $attachWithExistingOrders = $post['attach_with_existing_orders'];
            if (1 == $attachWithExistingOrders) {
                $ddObj->attachFileWithOrderedProducts($mainFileId, $post['record_id'], Product::CATALOG_TYPE_PRIMARY, $langId, $optionComb);
            }
        }

        if (
            isset($_FILES['preview_file']['tmp_name']) && is_uploaded_file($_FILES['preview_file']['tmp_name'])
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

        $attachWithExistingOrders = FatUtility::int($post['attach_with_existing_orders']);
        if ($attachWithExistingOrders == applicationConstants::YES && '' != $downloadLink) {
            $ddObj->attachLinkWithOrderedProducts($downloadLink, $post['record_id'], Product::CATALOG_TYPE_PRIMARY, $optionComb);
        }

        $this->set('langId', $langId);
        $this->set('optionComb', $optionComb);
        $this->set('recordId', $post['record_id']);
        $this->set('downloadType', $post['download_type']);
        $this->set('msg', $this->str_setup_successful);
        $this->_template->render(false, false, 'json-success.php');
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

        $canDo = $ddpObj->canEdit($recordId, Product::CATALOG_TYPE_PRIMARY, 0, $this->siteLangId, false, true);
        $this->set('canDo', $canDo);

        $product = $ddpObj->getProduct($recordId);
        $this->set('product', $product);

        $rows = DigitalDownloadSearch::getLinks($recordId, Product::CATALOG_TYPE_PRIMARY, $optionCombi, $langId);
        $languages = array('0' => Labels::getLabel('LBL_All', $this->siteLangId)) + Language::getAllNames();

        $productOptions = Product::getProductOptions($recordId, $this->siteLangId, true);
        $optionCombinations = CommonHelper::combinationOfElementsOfArr($productOptions, 'optionValues', '_');
        $optionCombinations = array('0' => Labels::getLabel('LBL_All', $this->siteLangId)) + $optionCombinations;

        $this->set('links', $rows);
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

        $canDo = $ddpObj->canEdit($recordId, Product::CATALOG_TYPE_PRIMARY, 0, $this->siteLangId, false, true);
        $this->set('canDo', $canDo);

        $product = $ddpObj->getProduct($recordId);

        $attachments = DigitalDownloadSearch::getAttachments($recordId, Product::CATALOG_TYPE_PRIMARY, $optionComb, $langId);

        $attachments = DigitalDownloadSearch::processAttachmentsWithPreview($attachments);

        $this->set('attachments', $attachments);
        $this->set('languages', array('0' => Labels::getLabel('LBL_All', $this->siteLangId)) + Language::getAllNames());
        $productOptions = Product::getProductOptions($recordId, $this->siteLangId, true);
        $optionCombinations = CommonHelper::combinationOfElementsOfArr($productOptions, 'optionValues', '_');
        $optionCombinations = array('0' => Labels::getLabel('LBL_All', $this->siteLangId)) + $optionCombinations;
        $this->set('options', $optionCombinations);
        $this->set('recordId', $recordId);
        $this->set('product', $product);
        $this->set('downloadrefType', Product::CATALOG_TYPE_PRIMARY);
        $this->set('html', $this->_template->render(false, false, NULL, true));
        $this->_template->render(false, false, 'json-success.php', true, false);
    }

    public function deleteDigitalLink($linkId, $refId)
    {
        $this->checkEditPrivilege();
        $refId = FatUtility::int($refId);
        $linkId = FatUtility::int($linkId);

        if (1 > $refId || 1 > $linkId) {
            LibHelper::exitWithError($this->str_invalid_request, true);
        }

        $reference = DigitalDownload::getAttributesById($refId);

        if (false == $reference) {
            LibHelper::exitWithError($this->str_invalid_request, true);
        }

        $link = DigitalDownloadSearch::getLinkDetail($linkId);
        if (1 > count($link)) {
            LibHelper::exitWithError($this->str_invalid_request, true);
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

        LibHelper::exitWithSuccess($this->str_delete_record, true);
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
            LibHelper::exitWithError(Labels::getLabel("ERR_FILE_NOT_FOUND", $this->siteLangId), true);
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

    public function getShippingProfileOptions()
    {
        $userId = FatApp::getPostedData('userId', FatUtility::VAR_INT);
        $langId = FatApp::getPostedData('langId', FatUtility::VAR_INT);
        $recordId = FatApp::getPostedData('recordId', FatUtility::VAR_INT);
        if (1 > $langId) {
            LibHelper::exitWithError($this->str_invalid_request, true);
        }
        $shippingObj = new Shipping($userId);
        $shipProfileArr = [];
        $showShippingProfile = 1;
        if ($shippingObj->getShippingApiObj($userId)) {
            $showShippingProfile = 0;
            if (1 === FatApp::getConfig('CONF_SHIPPED_BY_ADMIN_ONLY', FatUtility::VAR_INT, 0) && 1 == FatApp::getConfig('CONF_MANUAL_SHIPPING_RATES_ADMIN', FatUtility::VAR_INT, 0)) {
                $showShippingProfile = 1;
            } elseif (0 >= $userId && 1 == FatApp::getConfig('CONF_MANUAL_SHIPPING_RATES_ADMIN', FatUtility::VAR_INT, 0)) {
                $showShippingProfile = 1;
            } elseif (0 < $userId && 1 == Shop::getAttributesByUserId($userId, 'shop_use_manual_shipping_rates')) {
                $showShippingProfile = 1;
            }
        }

        $fulfillmentType = -1;
        if ($userId && !FatApp::getConfig('CONF_SHIPPED_BY_ADMIN_ONLY', FatUtility::VAR_INT, 0)) {
            $fulfillmentType = Shop::getAttributesByUserId($userId, 'shop_fulfillment_type');
            $shopDetails = Shop::getAttributesByUserId($userId, null, false);
            $address = new Address(0, $this->siteLangId);
            $addresses = $address->getData(Address::TYPE_SHOP_PICKUP, $shopDetails['shop_id']);
            $fulfillmentType = empty($addresses) ? Shipping::FULFILMENT_SHIP : $fulfillmentType;
        } else {
            $fulfillmentType = FatApp::getConfig('CONF_FULFILLMENT_TYPE', FatUtility::VAR_INT, -1);
        }
        $fullfilmentOptions = Shipping::getFulFillmentArr($this->siteLangId, $fulfillmentType);

        if (1 === $showShippingProfile) {
            $shipProfileArr = ShippingProfile::getProfileArr($langId, $userId, true, true);
        }

        FatUtility::dieJsonSuccess(['shipProfileArr' => $shipProfileArr, 'showShippingProfile' => $showShippingProfile, 'fullfilmentOptions' => $fullfilmentOptions]);
    }

    protected function getFormColumns(): array
    {
        $emptyCartItemsTblHeadingCols = CacheHelper::get('productsTblHeadingCols' . $this->siteLangId, CONF_DEF_CACHE_TIME, '.txt');
        if ($emptyCartItemsTblHeadingCols) {
            return json_decode($emptyCartItemsTblHeadingCols, true);
        }

        $arr = [
            'select_all' => Labels::getLabel('LBL_SELECT_ALL', $this->siteLangId),
            'listSerial' => Labels::getLabel('LBL_SR._NO', $this->siteLangId),
            'images' => Labels::getLabel('LBL_IMAGES', $this->siteLangId),
            'product_identifier' => Labels::getLabel('LBL_NAME', $this->siteLangId),
            'user_name' => Labels::getLabel('LBL_USER', $this->siteLangId),
            'product_added_on' => Labels::getLabel('LBL_CREATED_ON', $this->siteLangId),
            'product_approved' => Labels::getLabel('LBL_APPROVED', $this->siteLangId),
            'product_active' => Labels::getLabel('LBL_STATUS', $this->siteLangId),
            'action' => Labels::getLabel('LBL_ACTION_BUTTONS', $this->siteLangId),
        ];
        CacheHelper::create('productsTblHeadingCols' . $this->siteLangId, json_encode($arr), CacheHelper::TYPE_LABELS);

        return $arr;
    }

    protected function getDefaultColumns(): array
    {
        return [
            'select_all',
            /* 'listSerial', */
            'images',
            'product_identifier',
            'user_name',
            'product_added_on',
            'product_approved',
            'product_active',
            'action',
        ];
    }

    protected function excludeKeysForSort($fields = []): array
    {
        return array_diff($fields, ['images'], Common::excludeKeysForSort());
    }

    public function getBreadcrumbNodes($action)
    {
        if (in_array($action, ['index', 'form'])) {
            $indexPageData = PageLanguageData::getAttributesByKey('MANAGE_PRODUCTS', $this->siteLangId);
            $indexPageTitle = $indexPageData['plang_title'] ?? LibHelper::getControllerName(true);
        }
        switch ($action) {
            case 'index':
                $pageTitle = $indexPageTitle;
                $this->nodes = [
                    ['title' => $pageTitle]
                ];
                break;
            case 'form':
                $pageData = PageLanguageData::getAttributesByKey('ADD_PRODUCT', $this->siteLangId);
                $pageTitle = $pageData['plang_title'] ?? LibHelper::getControllerName(true);
                $this->nodes = [
                    ['title' => $indexPageTitle, 'href' => UrlHelper::generateUrl('Products')],
                    ['title' => $pageTitle]
                ];
                break;
            case 'approvalPending':
                $this->nodes = [
                    ['title' => Labels::getLabel('LBL_SELLER_PRODUCT_REQUEST', $this->siteLangId)]
                ];
                break;
            default:
                parent::getBreadcrumbNodes($action);
                break;
        }
        return $this->nodes;
    }

    protected function changeStatus(int $recordId, int $status)
    {
        $status = FatUtility::int($status);
        $recordId = FatUtility::int($recordId);
        if (1 > $recordId || -1 == $status) {
            LibHelper::exitWithError($this->str_invalid_request, true);
        }

        $productName = current(Product::getAttributesByLangId($this->siteLangId, $recordId, ['COALESCE(product_name, product_identifier) as product_name'], applicationConstants::JOIN_INNER));
        $oldProductData = Product::getAttributesById($recordId, ['product_seller_id', 'product_active']);
        if (
            0 < $status &&
            $oldProductData['product_active'] != $status &&
            0 < $oldProductData['product_seller_id'] &&
            FatApp::getConfig('CONF_ENABLE_SELLER_SUBSCRIPTION_MODULE', FatUtility::VAR_INT, 0) &&
            Product::getActiveCount($oldProductData['product_seller_id']) >= SellerPackages::getAllowedLimit($oldProductData['product_seller_id'], $this->siteLangId, 'ossubs_products_allowed')
        ) {
            LibHelper::exitWithError(CommonHelper::replaceStringData(Labels::getLabel('ERR_UNABLE_TO_CHANGE_STATUS_FOR_"{PRODUCT-NAME}"._AS_SELLER_SUBSCRIPTION_PACKAGE_LIMIT_CROSSED.', $this->siteLangId), ['{PRODUCT-NAME}' => $productName]), true);
        }

        $this->setModel([$recordId]);
        if (!$this->modelObj->changeStatus($status)) {
            LibHelper::exitWithError($this->modelObj->getError(), true);
        }
        CalculativeDataRecord::updateSelprodRequestCount();
    }
}
