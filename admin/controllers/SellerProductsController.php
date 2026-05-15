<?php
class SellerProductsController extends ListingBaseController
{
    use ProductsDigitalDownloads;

    protected string $modelClass = 'SellerProduct';
    protected $pageKey = 'MANAGE_SELLER_INVENTORIES';

    public function __construct($action)
    {
        parent::__construct($action);
        $this->objPrivilege->canViewSellerProducts($this->admin_id);
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
            $this->set("canEdit", $this->objPrivilege->canEditSellerProducts($this->admin_id, true));
        } else {
            $this->objPrivilege->canEditSellerProducts();
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
        if (FatApp::getConfig('CONF_WITHOUT_PROD_VARIANTS', FatUtility::VAR_INT, 0)) {
            $this->formLangFields = [$this->modelObj::tblFld('comments')];
        } else {
            $this->formLangFields = [$this->modelObj::tblFld('title'), $this->modelObj::tblFld('comments')];
        }
        $this->set('formTitle', Labels::getLabel('LBL_SELLER_INVENTORY_SETUP', $this->siteLangId));
    }

    public function index($product_id = 0)
    {
        $fields = $this->getFormColumns();
        $frmSearch = $this->getSearchForm($fields);

        $post = FatApp::getPostedData();
        $selProdId = FatApp::getPostedData('selprod_id', FatUtility::VAR_INT, 0);
        if (0 < $selProdId) {
            $product = SellerProduct::getSelProdDataById($selProdId, true, ['selprod_title', 'product_name', 'product_identifier']);
            $productTitle = $product['selprod_title'] ?? $product['product_name'] ?? $product['product_identifier'];
            $post['keyword'] = $productTitle;
        }

        $frmSearch->fill($post);

        $pageData = PageLanguageData::getAttributesByKey('MANAGE_SELLER_INVENTORIES', $this->siteLangId);
        $pageTitle = $pageData['plang_title'] ?? LibHelper::getControllerName(true);

        $actionItemsData = HtmlHelper::getDefaultActionItems($fields);
        $actionItemsData['performBulkAction'] = true;
        $actionItemsData['deleteButton'] = true;
        $actionItemsData['statusButtons'] = true;
        $actionItemsData['newRecordBtn'] = false;

        $this->set('actionItemsData', $actionItemsData);
        $this->set('pageData', $pageData);
        $this->set('pageTitle', $pageTitle);

        $this->getListingData($product_id);

        $this->set('canEdit', $this->objPrivilege->canEditSellerProducts($this->admin_id, true));
        $this->set("frmSearch", $frmSearch);
        $this->set('includeEditor', true);
        $this->_template->addJs(array('js/select2.js', 'seller-products/page-js/index.js', 'meta-tags/page-js/index.js'));
        $this->_template->addCss(array('css/select2.min.css'));
        $this->includeFeatherLightJsCss();
        $this->_template->render();
    }

    public function search()
    {
        $loadPagination = FatApp::getPostedData('loadPagination', FatUtility::VAR_INT, 0);
        $this->getListingData(0, $loadPagination);
        $jsonData = [
            'paginationHtml' => $this->_template->render(false, false, '_partial/listing/listing-foot.php', true)
        ];

        if (!$loadPagination || !FatUtility::isAjaxCall()) {
            $jsonData['listingHtml'] = $this->_template->render(false, false, 'seller-products/search.php', true);
        }
        LibHelper::exitWithSuccess($jsonData, true);
    }

    public function getListingData($product_id = 0, $loadPagination = 0)
    {
        $data = FatApp::getPostedData();
        $fields = $this->getFormColumns();
        $selectedFlds = FatApp::getPostedData('reportColumns', FatUtility::VAR_STRING, '');
        $selectedFlds = !empty($selectedFlds) ? json_decode($selectedFlds) + $this->getDefaultColumns() : $this->getDefaultColumns();

        $fields = FilterHelper::parseArrayByKeys($fields, $selectedFlds, true);
        $allowedKeysForSorting = $this->excludeKeysForSort(array_keys($fields));
        $sortBy = FatApp::getPostedData('sortBy', FatUtility::VAR_STRING, current($allowedKeysForSorting));
        if (!array_key_exists($sortBy, $fields)) {
            $sortBy = current($allowedKeysForSorting);
        }

        $sortOrder = applicationConstants::getSortOrder(FatApp::getPostedData('sortOrder', FatUtility::VAR_STRING));
        $pageSize = applicationConstants::getPageSize(FatApp::getPostedData('pageSize', FatUtility::VAR_INT));
        $searchForm = $this->getSearchForm($fields);

        $page = (empty($data['page']) || $data['page'] <= 0) ? 1 : $data['page'];
        $post = $searchForm->getFormDataFromArray($data);
        $post['prodcat_id'] = FatApp::getPostedData('prodcat_id', FatUtility::VAR_INT, 0);
        $post['user_id'] = FatApp::getPostedData('user_id', FatUtility::VAR_INT, 0);

        $srch = SellerProduct::getSearchObject($this->siteLangId);
        $srch->joinTable(Product::DB_TBL, 'INNER JOIN', 'p.product_id = sp.selprod_product_id', 'p');
        $srch->joinTable(Product::DB_TBL_LANG, 'LEFT OUTER JOIN', 'p.product_id = p_l.productlang_product_id AND p_l.productlang_lang_id = ' . $this->siteLangId, 'p_l');
        $srch->joinTable(User::DB_TBL, 'LEFT OUTER JOIN', 'selprod_user_id = u.user_id', 'u');
        $srch->joinTable(User::DB_TBL_CRED, 'LEFT OUTER JOIN', 'u.user_id = uc.credential_user_id', 'uc');
        $srch->addCondition('selprod_deleted', '=', 'mysql_func_' . applicationConstants::NO, 'AND', true);
        $srch->addCondition('product_deleted', '=', applicationConstants::NO);

        $active = FatApp::getPostedData('product_active');
        if ('' != $active) {
            $srch->addCondition('product_active', '=', $active);
        }
        $approval = FatApp::getPostedData('product_approved');
        if ('' != $approval) {
            $srch->addCondition('product_approved', '=', $approval);
        }
        $page = (empty($page) || $page <= 0) ? 1 : $page;
        $page = FatUtility::int($page);
        $keyword = FatApp::getPostedData('keyword', FatUtility::VAR_STRING, '');
        if (!empty($keyword)) {
            $cnd = $srch->addCondition('product_name', 'like', "%$keyword%");
            $cnd->attachCondition('selprod_title', 'LIKE', '%' . $keyword . '%', 'OR');
        }

        $selProdId = FatApp::getPostedData('selprod_id', FatUtility::VAR_INT, 0);
        if (0 < $selProdId) {
            $srch->addCondition('selprod_id', '=', 'mysql_func_' . $selProdId, 'AND', true);
        }

        if ($post['user_id'] > 0) {
            $srch->addCondition('selprod_user_id', '=', 'mysql_func_' . $post['user_id'], 'AND', true);
        } else {
            $user_name = FatApp::getPostedData('user_name', null, '');
            if (!empty($user_name)) {
                $cond = $srch->addCondition('u.user_name', 'like', '%' . $keyword . '%');
                $cond->attachCondition('uc.credential_email', 'like', '%' . $keyword . '%', 'OR');
                $cond->attachCondition('u.user_name', 'like', '%' . $keyword . '%');
            }
        }

        $product_attrgrp_id = FatApp::getPostedData('product_attrgrp_id', FatUtility::VAR_INT, -1);
        if ($product_attrgrp_id != -1) {
            $srch->addCondition('product_attrgrp_id', '=', 'mysql_func_' . $product_attrgrp_id, 'AND', true);
        }

        $active = FatApp::getPostedData('active', FatUtility::VAR_INT, -1);
        if ($active != -1) {
            $srch->addCondition('selprod_active', '=', 'mysql_func_' . applicationConstants::ACTIVE, 'AND', true);
        }

        if ($post['prodcat_id'] > 0) {
            $srch->joinTable(Product::DB_TBL_PRODUCT_TO_CATEGORY, 'LEFT OUTER JOIN', 'p.product_id = ptc_product_id', 'ptcat');
            $srch->addCondition('ptcat.ptc_prodcat_id', '=', 'mysql_func_' . $post['prodcat_id'], 'AND', true);
        }
        $product_id = 0;
        if (isset($post['product_id'])) {
            $product_id = FatUtility::int($post['product_id']);
        }


        if ($product_id) {
            $row = Product::getAttributesById($product_id, array('product_id'));
            if (!$row) {
                LibHelper::exitWithError(Labels::getLabel('ERR_INVALID_REQUEST', $this->siteLangId), true);
            }
            $srch->addCondition('selprod_product_id', '=', 'mysql_func_' . $product_id, 'AND', true);
        }

        if ($loadPagination && FatUtility::isAjaxCall()) {
            $this->setRecordCount(clone $srch, $pageSize, $page, $post);
        }
        $srch->doNotCalculateRecords();

        $srch->setPageNumber($page);
        $srch->setPageSize($pageSize);
        $srch->addMultipleFields(
            array(
                'selprod_id',
                'selprod_user_id',
                'selprod_price',
                'selprod_stock',
                'selprod_product_id',
                'selprod_active',
                'selprod_available_from',
                'IFNULL(product_name, product_identifier) as product_name',
                'selprod_title',
                'u.user_name',
                'uc.credential_email',
                'product_type',
                'product_updated_on'
            )
        );

        if (!empty($sortBy)) {
            $srch->addOrder($sortBy, $sortOrder);
        }

        $records = [];
        if (!$loadPagination) {
            $records = FatApp::getDb()->fetchAll($srch->getResultSet());
        }

        if (count($records)) {
            foreach ($records as &$arr) {
                $arr['options'] = SellerProduct::getSellerProductOptions($arr['selprod_id'], true, $this->siteLangId);
            }
        }

        $this->set("arrListing", $records);
        $this->set('product_id', $product_id);
        $this->set('activeInactiveArr', applicationConstants::getActiveInactiveArr($this->siteLangId));
        $this->set('canViewProducts', $this->objPrivilege->canViewProducts($this->admin_id, true));
        $this->set('canViewUsers', $this->objPrivilege->canViewUsers($this->admin_id, true));
        if (!$product_id) {
            $this->set('postedData', $post);
        }

        $this->set('sortBy', $sortBy);
        $this->set('sortOrder', $sortOrder);
        $this->set('fields', $fields);
        $this->set('allowedKeysForSorting', $allowedKeysForSorting);
        $this->set('canEdit', $this->objPrivilege->canEditSellerProducts($this->admin_id, true));
    }

    public function form()
    {
        $selProdId = FatApp::getPostedData('recordId', FatUtility::VAR_INT, 0);

        if (1 > $selProdId) {
            LibHelper::exitWithError($this->str_invalid_request_id, true);
        }

        $sellerProductRow = SellerProduct::getAttributesById($selProdId, null, true, true);
        if (!$sellerProductRow) {
            LibHelper::exitWithError($this->str_invalid_request_id, true);
        }

        $sellerProductLangRow = SellerProduct::getAttributesByLangId(CommonHelper::getDefaultFormLangId(), $selProdId);
        if (false != $sellerProductLangRow) {
            $sellerProductRow = array_merge($sellerProductRow, $sellerProductLangRow);
        }

        $frmSellerProduct = $this->getSellerProductForm($sellerProductRow['selprod_product_id']);

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
        $sellerProductRow['selprod_user_shop_name'] = $user_shop_name['user_name'] . ' - ' . $user_shop_name['shop_name'];

        $returnAge = isset($sellerProductRow['selprod_return_age']) ? FatUtility::int($sellerProductRow['selprod_return_age']) : '';
        $cancellationAge = isset($sellerProductRow['selprod_cancellation_age']) ? FatUtility::int($sellerProductRow['selprod_cancellation_age']) : '';

        if ('' === $returnAge || '' === $cancellationAge) {
            $sellerProductRow['use_shop_policy'] = 1;
        }

        $frmSellerProduct->fill($sellerProductRow);

        $productRow = Product::getAttributesById($sellerProductRow['selprod_product_id'], ['product_min_selling_price', 'product_seller_id']);

        $shippedBySeller = 0;
        if (Product::isProductShippedBySeller($sellerProductRow['selprod_product_id'], $productRow['product_seller_id'], $sellerProductRow['selprod_user_id'])) {
            $shippedBySeller = 1;
        }
        $productOptions = Product::getProductOptions($sellerProductRow['selprod_product_id'], $this->siteLangId, true);

        $this->set('shippedBySeller', $shippedBySeller);
        $this->set('productOptions', $productOptions);
        $this->set('frm', $frmSellerProduct);
        $this->set('recordId', $selProdId);
        $this->set('productMinSellingPrice', $productRow['product_min_selling_price']);

        $metaTagIdForSellerProduct = 0;
        $showSellerProductMetaTab = $this->objPrivilege->canEditMetaTags($this->admin_id, true);
        if ($showSellerProductMetaTab) {
            $tabsArr = MetaTag::getTabsArr($this->siteLangId);
            $detail = $tabsArr[MetaTag::META_GROUP_PRODUCT_DETAIL];
            $mtSrch = MetaTag::getSearchObject();
            $mtSrch->addCondition('meta_controller', '=', $detail['controller']);
            $mtSrch->addCondition('meta_action', '=', $detail['action']);
            $mtSrch->addCondition('meta_record_id', '=', $selProdId);
            $mtSrch->addFld('meta_id');
            $mtSrch->doNotCalculateRecords();
            $mtSrch->setPageSize(1);
            $mtRs = $mtSrch->getResultSet();
            $metaRow = FatApp::getDb()->fetch($mtRs);
            if ($metaRow) {
                $metaTagIdForSellerProduct = FatUtility::int($metaRow['meta_id']);
            }
        }
        $this->set('metaTagIdForSellerProduct', $metaTagIdForSellerProduct);
        $this->set('showSellerProductMetaTab', $showSellerProductMetaTab);

        $this->set('html', $this->_template->render(false, false, NULL, true));
        $this->_template->render(false, false, 'json-success.php', true, false);
    }

    public function setup()
    {
        $this->objPrivilege->canEditSellerProducts();
        $selProdId = $this->setupInventory();
        $newTabLangId = 0;
        $languages = Language::getDropDownList(CommonHelper::getDefaultFormLangId());
        if (0 < count($languages)) {
            foreach ($languages as $langId => $langName) {
                if (!SellerProduct::getAttributesByLangId($langId, $selProdId)) {
                    $newTabLangId = $langId;
                    break;
                }
            }
        }

        $this->set('recordId', $selProdId);
        $this->set('langId', $newTabLangId);
        $this->set('msg', $this->str_update_record);
        $this->_template->render(false, false, 'json-success.php');
    }

    protected function getSearchForm($fields = [])
    {
        $frm = new Form('frmRecordSearch', array('id' => 'frmRecordSearch'));
        $frm->setRequiredStarWith('caption');
        $fld = $frm->addTextBox(Labels::getLabel('FRM_KEYWORD', $this->siteLangId), 'keyword', '', array('class' => 'search-input'));
        $fld->overrideFldType('search');

        $userId = FatApp::getPostedData('user_id', FatUtility::VAR_INT, 0);
        $options = [];
        if (0 < $userId) {
            $user = new User($userId);
            $userInfo = $user->getUserInfo();
            $options = [
                $userId => $userInfo['user_name'] . ' (' . $userInfo['credential_username'] . ')'
            ];
        }
        $frm->addSelectBox(Labels::getLabel('FRM_SELLER_NAME_OR_EMAIL', $this->siteLangId), 'user_id', $options, $userId, ['placeholder' => Labels::getLabel('FRM_SELLER_NAME_OR_EMAIL', $this->siteLangId)]);

        $prodCatObj = new ProductCategory();
        $arrCategories = $prodCatObj->getCategoriesForSelectBox($this->siteLangId);
        $categories = $prodCatObj->makeAssociativeArray($arrCategories);
        $frm->addSelectBox(Labels::getLabel('FRM_CATEGORY', $this->siteLangId), 'prodcat_id', $categories);
        $frm->addSelectBox(Labels::getLabel('FRM_PRODUCT_APPROVAL', $this->siteLangId), 'product_approved', applicationConstants::getYesNoArr($this->siteLangId));
        $frm->addSelectBox(Labels::getLabel('FRM_PRODUCT_STATUS', $this->siteLangId), 'product_active', applicationConstants::getActiveInactiveArr($this->siteLangId));

        if (!empty($fields)) {
            $this->addSortingElements($frm, 'selprod_title');
        }
        $frm->addHiddenField('', 'total_record_count');
        HtmlHelper::addSearchButton($frm);
        HtmlHelper::addClearButton($frm);/*clearBtn*/
        return $frm;
    }

    protected function getLangForm($recordId = 0, $lang_id = 0)
    {
        $frm = new Form('frmLang');
        $frm->addHiddenField('', 'selprod_id', $recordId);
        $frm->addSelectBox(Labels::getLabel('FRM_LANGUAGE', $this->siteLangId), 'lang_id', Language::getDropDownList(CommonHelper::getDefaultFormLangId()), $lang_id, array(), '');
        if (!FatApp::getConfig('CONF_WITHOUT_PROD_VARIANTS', FatUtility::VAR_INT, 0)) {
            $frm->addRequiredField(Labels::getLabel('FRM_TITLE', $this->siteLangId), 'selprod_title');
        }
        $frm->addTextArea(Labels::getLabel('FRM_ANY_EXTRA_COMMENT_FOR_BUYER', $this->siteLangId), 'selprod_comments');
        return $frm;
    }

    public function addPolicyPoint()
    {
        $this->objPrivilege->canEditSellerProducts();
        $post = FatApp::getPostedData();
        if (empty($post['selprod_id']) || empty($post['ppoint_id'])) {
            LibHelper::exitWithError($this->str_invalid_request, true);
        }
        $selprod_id = FatUtility::int($post['selprod_id']);
        $ppoint_id = FatUtility::int($post['ppoint_id']);
        $dataToSave = array('sppolicy_ppoint_id' => $ppoint_id, 'sppolicy_selprod_id' => $selprod_id);
        $obj = new SellerProduct();
        if (!$obj->addPolicyPointToSelProd($dataToSave)) {
            LibHelper::exitWithError($obj->getError(), true);
        }
        $this->set("msg", Labels::getLabel('LBL_Policy_Added_Successfully', $this->siteLangId));

        $this->_template->render(false, false, 'json-success.php');
    }

    public function removePolicyPoint()
    {
        $this->objPrivilege->canEditSellerProducts();
        $post = FatApp::getPostedData();
        if (empty($post['selprod_id']) || empty($post['ppoint_id'])) {
            LibHelper::exitWithError($this->str_invalid_request, true);
        }
        $selprod_id = FatUtility::int($post['selprod_id']);
        $ppoint_id = FatUtility::int($post['ppoint_id']);
        $whereCond = array('smt' => 'sppolicy_ppoint_id = ? and sppolicy_selprod_id = ?', 'vals' => array($ppoint_id, $selprod_id));
        $db = FatApp::getDb();
        if (!$db->deleteRecords(SellerProduct::DB_TBL_SELLER_PROD_POLICY, $whereCond)) {
            LibHelper::exitWithError($db->getError(), true);
        }
        $this->set("msg", Labels::getLabel('LBL_Policy_Removed_Successfully', $this->siteLangId));

        $this->_template->render(false, false, 'json-success.php');
    }

    /* Seller Product Seo [ */

    public function productSeo($selprod_id = 0)
    {
        $selprod_id = Fatutility::int($selprod_id);

        $this->set('activeTab', 'SEO');
        $metaType = MetaTag::META_GROUP_PRODUCT_DETAIL;
        $this->set('metaType', $metaType);
        $sellerProductRow = SellerProduct::getAttributesById($selprod_id);
        $this->set('product_id', $sellerProductRow['selprod_product_id']);
        $this->set('selprod_id', $selprod_id);
        $this->set('html', $this->_template->render(false, false, NULL, true));
        $this->_template->render(false, false, 'json-success.php', true, false);
    }

    /*  - --- Seller Product Links  ----- [ */

    public function sellerProductLinkFrm($selProd_id)
    {
        $post = FatApp::getPostedData();
        $selprod_id = FatUtility::int($selProd_id);
        $sellProdObj = new SellerProduct();
        $sellerProductRow = SellerProduct::getAttributesById($selprod_id);
        $productRow = Product::getAttributesById($sellerProductRow['selprod_product_id'], array('product_type'));
        $upsellProds = $sellProdObj->getUpsellProducts($selprod_id, $this->siteLangId);
        $relatedProds = $sellProdObj->getRelatedProducts($this->siteLangId, $selprod_id);
        $sellerproductLinkFrm = $this->getLinksFrm();
        $data['selprod_id'] = $selProd_id;
        $sellerproductLinkFrm->fill($data);
        $this->set('sellerproductLinkFrm', $sellerproductLinkFrm);
        $this->set('upsellProducts', $upsellProds);
        $this->set('relatedProducts', $relatedProds);
        $this->set('selprod_id', $selProd_id);
        $this->set('product_id', $sellerProductRow[SellerProduct::DB_TBL_PREFIX . 'product_id']);
        $this->set('product_type', $productRow['product_type']);
        $this->set('activeTab', 'LINKS');
        $this->set('html', $this->_template->render(false, false, NULL, true));
        $this->_template->render(false, false, 'json-success.php', true, false);
    }

    private function getLinksFrm()
    {
        $prodObj = new Product();

        $frm = new Form('frmLinks', array('id' => 'frmLinks'));

        $frm->addTextBox(Labels::getLabel('FRM_BUY_TOGETHER_PRODUCTS', $this->siteLangId), 'products_buy_together');

        $frm->addHtml('', 'buy_together', '<div id="buy-together-products" class="box--scroller"><ul class="links--vertical"></ul></div>');

        $frm->addTextBox(Labels::getLabel('FRM_RELATED_PRODUCTS', $this->siteLangId), 'products_related');

        $frm->addHtml('', 'related_products', '<div id="related-products" class="box--scroller"><ul class="links_vertical"></ul></div>');

        $frm->addHiddenField('', 'selprod_id');
        $frm->addSubmitButton('', 'btn_submit', Labels::getLabel('BTN_SAVE_CHANGES', $this->siteLangId));
        return $frm;
    }

    public function autoComplete()
    {
        $pagesize = 20;
        $post = FatApp::getPostedData();
        $page = FatApp::getPostedData('page', FatUtility::VAR_INT, 1);
        if ($page < 2) {
            $page = 1;
        }

        $srch = SellerProduct::getSearchObject($this->siteLangId);
        $srch->joinTable(Product::DB_TBL, 'INNER JOIN', 'p.product_id = sp.selprod_product_id', 'p');
        $srch->joinTable(Product::DB_TBL_LANG, 'LEFT OUTER JOIN', 'p.product_id = p_l.productlang_product_id AND p_l.productlang_lang_id = ' . $this->siteLangId, 'p_l');
        $srch->joinTable(User::DB_TBL_CRED, 'LEFT OUTER JOIN', 'tuc.credential_user_id = sp.selprod_user_id', 'tuc');

        if (FatApp::getConfig("CONF_PRODUCT_BRAND_MANDATORY", FatUtility::VAR_INT, 1)) {
            $srch->joinTable(Brand::DB_TBL, 'INNER JOIN', 'tb.brand_id = product_brand_id and tb.brand_active = ' . applicationConstants::YES . ' and tb.brand_deleted = ' . applicationConstants::NO, 'tb');
        } else {
            $srch->joinTable(Brand::DB_TBL, 'LEFT OUTER JOIN', 'tb.brand_id = product_brand_id', 'tb');
            $srch->addDirectCondition("(case WHEN brand_id > 0 THEN (tb.brand_active = " . applicationConstants::YES . " AND tb.brand_deleted = " . applicationConstants::NO . ") else TRUE end)");
        }

        $srch->addOrder('product_name');
        if (isset($post['keyword']) && '' != $post['keyword']) {
            $cnd = $srch->addCondition('product_name', 'LIKE', '%' . $post['keyword'] . '%');
            $cnd->attachCondition('selprod_title', 'LIKE', '%' . $post['keyword'] . '%', 'OR');
            $cnd->attachCondition('product_identifier', 'LIKE', '%' . $post['keyword'] . '%', 'OR');
        }

        if (!empty($post['selProdId']) && 0 < FatUtility::int($post['selProdId'])) {
            $selprod_user = SellerProduct::getAttributesById($post['selProdId'], array('selprod_user_id'));
            $srch->addCondition('selprod_user_id', '=', $selprod_user['selprod_user_id']);
            $srch->addCondition('selprod_id', '!=', $post['selProdId']);
        }

        if (array_key_exists('selprod_user_id', $post) && 0 < $post['selprod_user_id']) {
            $srch->addCondition('selprod_user_id', '=', $post['selprod_user_id']);
        }

        $excludeRecords = FatApp::getPostedData('excludeRecords', FatUtility::VAR_INT);
        if (!empty($excludeRecords) && is_array($excludeRecords)) {
            $srch->addCondition('selprod_id', 'NOT IN', $excludeRecords);
        }

        $srch->addCondition(Product::DB_TBL_PREFIX . 'active', '=', applicationConstants::YES);
        $srch->addCondition(Product::DB_TBL_PREFIX . 'deleted', '=', applicationConstants::NO);
        $srch->addCondition(Product::DB_TBL_PREFIX . 'approved', '=', Product::APPROVED);
        $srch->addCondition('selprod_deleted', '=', applicationConstants::NO);
        $srch->addCondition('selprod_active', '=', applicationConstants::ACTIVE);
        $srch->addMultipleFields(array('selprod_id as id', 'COALESCE(selprod_title ,product_name, product_identifier) as product_name', 'product_identifier', 'credential_username', 'selprod_price', 'selprod_stock'));

        $srch->addOrder('selprod_active', 'DESC');
        $srch->setPageNumber($page);
        $srch->setPageSize($pagesize);
        $db = FatApp::getDb();
        $rs = $srch->getResultSet();
        $products = $db->fetchAll($rs, 'id');

        $pageCount = $srch->pages();
        $json = array();
        foreach ($products as $key => $option) {
            $options = SellerProduct::getSellerProductOptions($key, true, $this->siteLangId);
            $variantsStr = '';
            array_walk($options, function ($item, $key) use (&$variantsStr) {
                $variantsStr .= ' | ' . $item['option_name'] . ' : ' . $item['optionvalue_name'];
            });
            $userName = isset($option["credential_username"]) ? " | " . $option["credential_username"] : '';
            $json[] = array(
                'id' => $key,
                'text' => strip_tags(html_entity_decode($option['product_name'], ENT_QUOTES, 'UTF-8')) . $variantsStr . $userName,
                'product_identifier' => strip_tags(html_entity_decode($option['product_identifier'], ENT_QUOTES, 'UTF-8')),
                'price' => $option['selprod_price'],
                'stock' => $option['selprod_stock']
            );
        }
        die(json_encode(['pageCount' => $pageCount, 'results' => $json]));
    }

    public function setupSellerProductLinks()
    {
        $this->objPrivilege->canEditSellerProducts();
        $post = FatApp::getPostedData();
        $selprod_id = FatUtility::int($post['selprod_id']);
        $upsellProducts = (isset($post['product_upsell'])) ? $post['product_upsell'] : array();
        $relatedProducts = (isset($post['product_related'])) ? $post['product_related'] : array();
        unset($post['selprod_id']);

        if ($selprod_id <= 0) {
            LibHelper::exitWithError($this->str_invalid_request, true);
        }

        $sellerProdObj = new SellerProduct();
        /* saving of product Upsell Product[ */
        if (!$sellerProdObj->addUpdateSellerUpsellProducts($selprod_id, $upsellProducts)) {
            LibHelper::exitWithError($sellerProdObj->getError(), true);
        }
        /* ] */
        /* saving of Related Products[ */


        if (!$sellerProdObj->addUpdateSellerRelatedProdcts($selprod_id, $relatedProducts)) {
            LibHelper::exitWithError($sellerProdObj->getError(), true);
        }
        /* ] */

        $this->set('msg', 'Record Updated Successfully!');
        $this->_template->render(false, false, 'json-success.php');
    }

    /*  - ---  ] Seller Product Links  ----- */

    public function sellerProductSpecialPrices($selprod_id)
    {
        $selprod_id = FatUtility::int($selprod_id);
        $sellerProductRow = SellerProduct::getAttributesById($selprod_id);
        $productRow = Product::getAttributesById($sellerProductRow['selprod_product_id'], array('product_type'));

        $arrListing = SellerProduct::getSellerProductSpecialPrices($selprod_id);
        $this->set('arrListing', $arrListing);
        $this->set('selprod_id', $sellerProductRow['selprod_id']);
        $this->set('product_id', $sellerProductRow['selprod_product_id']);
        $this->set('siteLangId', $this->siteLangId);
        $this->set('product_type', $productRow['product_type']);
        $this->set('activeTab', 'SPECIAL_PRICE');
        $this->set('html', $this->_template->render(false, false, NULL, true));
        $this->_template->render(false, false, 'json-success.php', true, false);
    }

    public function sellerProductSpecialPriceForm($selprod_id, $splprice_id = 0)
    {
        $selprod_id = FatUtility::int($selprod_id);
        $splprice_id = FatUtility::int($splprice_id);
        if (!$selprod_id) {
            LibHelper::exitWithError(Labels::getLabel('ERR_INVALID_REQUEST', $this->siteLangId), true);
        }
        $sellerProductRow = SellerProduct::getAttributesById($selprod_id);

        $frmSellerProductSpecialPrice = $this->getSellerProductSpecialPriceForm();
        $specialPriceRow = array();
        if ($splprice_id) {
            $tblRecord = new TableRecord(SellerProduct::DB_TBL_SELLER_PROD_SPCL_PRICE);
            if (!$tblRecord->loadFromDb(array('smt' => 'splprice_id = ?', 'vals' => array($splprice_id)))) {
                LibHelper::exitWithError(Labels::getLabel('ERR_INVALID_REQUEST', $this->siteLangId), true);
            }
            $specialPriceRow = $tblRecord->getFlds();
        }
        $specialPriceRow['splprice_selprod_id'] = $selprod_id;
        $frmSellerProductSpecialPrice->fill($specialPriceRow);

        $this->set('frmSellerProductSpecialPrice', $frmSellerProductSpecialPrice);
        $this->set('selprod_id', $selprod_id);
        $this->set('product_id', $sellerProductRow['selprod_product_id']);
        $this->set('siteLangId', $this->siteLangId);
        $this->set('activeTab', 'SPECIAL_PRICE');
        $this->set('html', $this->_template->render(false, false, NULL, true));
        $this->_template->render(false, false, 'json-success.php', true, false);
    }

    private function getSellerProductSpecialPriceForm()
    {
        return SellerProduct::specialPriceForm($this->siteLangId);
    }

    public function setUpSellerProductSpecialPrice()
    {
        $this->objPrivilege->canEditSellerProducts();
        $frm = $this->getSellerProductSpecialPriceForm();
        $post = $frm->getFormDataFromArray(FatApp::getPostedData());

        if (false === $post) {
            LibHelper::exitWithError(current($frm->getValidationErrors()), true);
        }

        $resp = $this->updateSelProdSplPrice($post);
        if (!$resp) {
            LibHelper::exitWithError(Labels::getLabel('ERR_INVALID_REQUEST', $this->siteLangId), true);
        }

        if (!empty($post['splprice_selprod_id'])) {
            $productId = SellerProduct::getAttributesById($post['splprice_selprod_id'], 'selprod_product_id', false);
            Product::updateMinPrices($productId);
        }

        $this->set('msg', Labels::getLabel('MSG_SPECIAL_PRICE_SETUP_SUCCESSFUL', $this->siteLangId));
        $this->_template->render(false, false, 'json-success.php');
    }

    private function updateSelProdSplPrice($post, $return = false)
    {
        $selprod_id = !empty($post['splprice_selprod_id']) ? FatUtility::int($post['splprice_selprod_id']) : 0;
        $splprice_id = !empty($post['splprice_id']) ? FatUtility::int($post['splprice_id']) : 0;

        if (1 > $selprod_id) {
            LibHelper::exitWithError(Labels::getLabel('ERR_INVALID_REQUEST', $this->siteLangId), true);
        }

        if (strtotime($post['splprice_start_date']) > strtotime($post['splprice_end_date'])) {
            LibHelper::exitWithError(Labels::getLabel('ERR_INVALID_DATES', $this->siteLangId), true);
        }

        $prodSrch = new ProductSearch($this->siteLangId);
        $prodSrch->joinSellerProducts();
        $prodSrch->addCondition('selprod_id', '=', $selprod_id);
        $prodSrch->addMultipleFields(array('product_min_selling_price', 'selprod_price'));
        $prodSrch->setPageSize(1);
        $rs = $prodSrch->getResultSet();
        $product = FatApp::getDb()->fetch($rs);

        /* Check if same date already exists [ */
        $tblRecord = new TableRecord(SellerProduct::DB_TBL_SELLER_PROD_SPCL_PRICE);

        $smt = 'splprice_selprod_id = ? AND ';
        $smt .= '(
                    ((splprice_start_date between ? AND ?) OR (splprice_end_date between ? AND ?))
                    OR
                    ((? BETWEEN splprice_start_date AND splprice_end_date) OR (? BETWEEN  splprice_start_date AND splprice_end_date))
                )';
        $smtValues = array(
            $selprod_id,
            $post['splprice_start_date'],
            $post['splprice_end_date'],
            $post['splprice_start_date'],
            $post['splprice_end_date'],
            $post['splprice_start_date'],
            $post['splprice_end_date'],
        );

        if (0 < $splprice_id) {
            $smt .= 'AND splprice_id != ?';
            $smtValues[] = $splprice_id;
        }
        $condition = array(
            'smt' => $smt,
            'vals' => $smtValues
        );

        if ($tblRecord->loadFromDb($condition)) {
            $specialPriceRow = $tblRecord->getFlds();
            if ($specialPriceRow['splprice_id'] != $splprice_id) {
                LibHelper::exitWithError(Labels::getLabel('ERR_SPECIAL_PRICE_FOR_THIS_DATE_ALREADY_ADDED', $this->siteLangId), true);
            }
        }
        /* ] */

        $data_to_save = array(
            'splprice_selprod_id' => $selprod_id,
            'splprice_start_date' => $post['splprice_start_date'],
            'splprice_end_date' => $post['splprice_end_date'],
            'splprice_price' => $post['splprice_price'],
        );

        if (0 < $splprice_id) {
            $data_to_save['splprice_id'] = $splprice_id;
        }

        $sellerProdObj = new SellerProduct();

        // Return Special Price ID if $return is true else it will return bool value.
        $splPriceId = $sellerProdObj->addUpdateSellerProductSpecialPrice($data_to_save, $return);
        if (false === $splPriceId) {
            LibHelper::exitWithError($sellerProdObj->getError(), true);
        }

        return $splPriceId;
    }

    public function deleteSellerProductSpecialPrice()
    {
        $this->objPrivilege->canEditSellerProducts();
        $splPriceId = FatApp::getPostedData('splprice_id', FatUtility::VAR_INT, 0);
        if (1 > $splPriceId) {
            LibHelper::exitWithError(Labels::getLabel('ERR_INVALID_REQUEST', $this->siteLangId), true);
        }
        $specialPriceRow = SellerProduct::getSellerProductSpecialPriceById($splPriceId);
        if (empty($specialPriceRow) || 1 > count($specialPriceRow)) {
            LibHelper::exitWithError(Labels::getLabel('ERR_ALREADY_DELETED', $this->siteLangId), true);
        }
        $this->deleteSpecialPrice($splPriceId, $specialPriceRow['selprod_id']);
        $this->set('selprod_id', $specialPriceRow['selprod_id']);
        $this->set('msg', Labels::getLabel('MSG_SPECIAL_PRICE_RECORD_DELETED', $this->siteLangId));
        $this->_template->render(false, false, 'json-success.php');
    }

    public function deleteSpecialPriceRows()
    {
        $this->objPrivilege->canEditSellerProducts();
        $splpriceIdArr = FatApp::getPostedData('selprod_ids');
        $splpriceIds = FatUtility::int($splpriceIdArr);
        foreach ($splpriceIds as $splPriceId => $selProdId) {
            $specialPriceRow = SellerProduct::getSellerProductSpecialPriceById($splPriceId);
            $this->deleteSpecialPrice($splPriceId, $specialPriceRow['selprod_id']);
        }
        $this->set('selprod_id', $specialPriceRow['selprod_id']);
        $this->set('msg', Labels::getLabel('MSG_SPECIAL_PRICE_RECORD_DELETED', $this->siteLangId));
        $this->_template->render(false, false, 'json-success.php');
    }

    private function deleteSpecialPrice($splPriceId, $selProdId)
    {
        $sellerProdObj = new SellerProduct($selProdId);
        if (!$sellerProdObj->deleteSellerProductSpecialPrice($splPriceId, $selProdId)) {
            LibHelper::exitWithError($sellerProdObj->getError(), true);
        }
        return true;
    }

    /* Seller Product Volume Discount [ */

    public function sellerProductVolumeDiscounts($selprod_id)
    {
        $selprod_id = FatUtility::int($selprod_id);
        $sellerProductRow = SellerProduct::getAttributesById($selprod_id, array('selprod_user_id', 'selprod_id', 'selprod_product_id'));
        $productRow = Product::getAttributesById($sellerProductRow['selprod_product_id'], array('product_type'));

        $srch = new SellerProductVolumeDiscountSearch();
        $srch->doNotCalculateRecords();
        $srch->doNotLimitRecords();
        $srch->addCondition('voldiscount_selprod_id', '=', $selprod_id);
        $rs = $srch->getResultSet();

        $arrListing = FatApp::getDb()->fetchAll($rs);
        $this->set('arrListing', $arrListing);
        $this->set('selprod_id', $sellerProductRow['selprod_id']);
        $this->set('product_id', $sellerProductRow['selprod_product_id']);
        $this->set('activeTab', 'VOLUME_DISCOUNT');
        $this->set('product_type', $productRow['product_type']);
        $productLangRow = Product::getAttributesByLangId($this->siteLangId, $sellerProductRow['selprod_product_id'], array('product_name'));
        $this->set('productCatalogName', $productLangRow['product_name']);

        $this->set('html', $this->_template->render(false, false, NULL, true));
        $this->_template->render(false, false, 'json-success.php', true, false);
    }

    public function sellerProductVolumeDiscountForm($selprod_id, $voldiscount_id)
    {
        $selprod_id = FatUtility::int($selprod_id);
        $voldiscount_id = FatUtility::int($voldiscount_id);
        if ($selprod_id <= 0) {
            LibHelper::exitWithError(Labels::getLabel('ERR_INVALID_REQUEST', $this->siteLangId), true);
        }
        $sellerProductRow = SellerProduct::getAttributesById($selprod_id, array('selprod_id', 'selprod_user_id', 'selprod_product_id'));
        if ($selprod_id != $sellerProductRow['selprod_id']) {
            LibHelper::exitWithError(Labels::getLabel('ERR_INVALID_ACCESS', $this->siteLangId), true);
        }

        $frmSellerProductVolDiscount = $this->getSellerProductVolumeDiscountForm($this->siteLangId);
        $volumeDiscountRow = array();
        if ($voldiscount_id) {
            $volumeDiscountRow = SellerProductVolumeDiscount::getAttributesById($voldiscount_id);
            if (!$volumeDiscountRow) {
                LibHelper::exitWithError(Labels::getLabel('ERR_INVALID_REQUEST', $this->siteLangId), true);
            }
        }
        $volumeDiscountRow['voldiscount_selprod_id'] = $sellerProductRow['selprod_id'];
        $frmSellerProductVolDiscount->fill($volumeDiscountRow);
        $this->set('frmSellerProductVolDiscount', $frmSellerProductVolDiscount);
        $this->set('selprod_id', $sellerProductRow['selprod_id']);
        $this->set('product_id', $sellerProductRow['selprod_product_id']);
        $this->set('activeTab', 'VOLUME_DISCOUNT');
        $this->set('html', $this->_template->render(false, false, NULL, true));
        $this->_template->render(false, false, 'json-success.php', true, false);
    }

    public function setUpSellerProductVolumeDiscount()
    {
        $this->objPrivilege->canEditSellerProducts();
        $post = FatApp::getPostedData();
        $selprod_id = FatUtility::int($post['voldiscount_selprod_id']);
        $voldiscount_id = FatUtility::int($post['voldiscount_id']);

        if (!$selprod_id) {
            LibHelper::exitWithError(Labels::getLabel('ERR_INVALID_REQUEST', $this->siteLangId), true);
        }

        $frm = $this->getSellerProductVolumeDiscountForm($this->siteLangId);
        $post = $frm->getFormDataFromArray(FatApp::getPostedData());

        if (false === $post) {
            LibHelper::exitWithError(current($frm->getValidationErrors()), true);
        }

        $resp = $this->updateSelProdVolDiscount($selprod_id, $voldiscount_id, $post['voldiscount_min_qty'], $post['voldiscount_percentage']);

        $this->set('msg', Labels::getLabel('MSG_VOLUME_DISCOUNT_SETUP_SUCCESSFUL', $this->siteLangId));
        $this->_template->render(false, false, 'json-success.php');
    }

    private function updateSelProdVolDiscount($selprod_id, $voldiscount_id, $minQty, $perc)
    {
        $sellerProductRow = SellerProduct::getAttributesById($selprod_id, array('selprod_user_id', 'selprod_stock', 'selprod_min_order_qty'), false);
        if ($minQty > $sellerProductRow['selprod_stock']) {
            LibHelper::exitWithError(Labels::getLabel('ERR_QUANTITY_CANNOT_BE_MORE_THAN_THE_STOCK', $this->siteLangId), true);
        }

        if ($minQty < $sellerProductRow['selprod_min_order_qty']) {
            LibHelper::exitWithError(Labels::getLabel('ERR_QUANTITY_CANNOT_BE_LESS_THAN_THE_MINIMUM_ORDER_QUANTITY', $this->siteLangId) . ': ' . $sellerProductRow['selprod_min_order_qty'], true);
        }

        if ($perc > 100 || 1 > $perc) {
            LibHelper::exitWithError(Labels::getLabel('ERR_INVALID_PERCENTAGE', $this->siteLangId), true);
        }

        /* Check if volume discount for same quantity already exists [ */
        $tblRecord = new TableRecord(SellerProductVolumeDiscount::DB_TBL);
        $smt = 'voldiscount_selprod_id = ? AND voldiscount_min_qty = ? ';
        $smtValues = array($selprod_id, $minQty);

        if (0 < $voldiscount_id) {
            $smt .= 'AND voldiscount_id != ?';
            $smtValues[] = $voldiscount_id;
        }
        $condition = array(
            'smt' => $smt,
            'vals' => $smtValues
        );
        if ($tblRecord->loadFromDb($condition)) {
            $volDiscountRow = $tblRecord->getFlds();
            if ($volDiscountRow['voldiscount_id'] != $voldiscount_id) {
                LibHelper::exitWithError(Labels::getLabel('ERR_VOLUME_DISCOUNT_FOR_THIS_QUANTITY_ALREADY_ADDED', $this->siteLangId), true);
            }
        }
        /* ] */

        $data_to_save = array(
            'voldiscount_selprod_id' => $selprod_id,
            'voldiscount_min_qty' => $minQty,
            'voldiscount_percentage' => $perc
        );

        if ($voldiscount_id > 0) {
            $data_to_save['voldiscount_id'] = $voldiscount_id;
        }

        $record = new TableRecord(SellerProductVolumeDiscount::DB_TBL);
        $record->assignValues($data_to_save);
        if (!$record->addNew(array(), $data_to_save)) {
            LibHelper::exitWithError($record->getError(), true);
        }

        return ($voldiscount_id > 0) ? $voldiscount_id : $record->getId();
    }

    public function deleteSellerProductVolumeDiscount()
    {
        $this->objPrivilege->canEditSellerProducts();
        $post = FatApp::getPostedData();
        $voldiscount_id = FatApp::getPostedData('voldiscount_id', FatUtility::VAR_INT, 0);
        if (!$voldiscount_id) {
            LibHelper::exitWithError(Labels::getLabel('ERR_INVALID_REQUEST', $this->siteLangId), true);
        }

        $volumeDiscountRow = SellerProductVolumeDiscount::getAttributesById($voldiscount_id);
        $sellerProductRow = SellerProduct::getAttributesById($volumeDiscountRow['voldiscount_selprod_id'], array('selprod_user_id'), false);
        if (!$volumeDiscountRow || !$sellerProductRow) {
            LibHelper::exitWithError(Labels::getLabel('ERR_INVALID_REQUEST', $this->siteLangId), true);
        }

        $this->deleteVolumeDiscount($voldiscount_id, $volumeDiscountRow['voldiscount_selprod_id']);

        $this->set('selprod_id', $volumeDiscountRow['voldiscount_selprod_id']);
        $this->set('msg', Labels::getLabel('MSG_VOLUME_DISCOUNT_RECORD_DELETED', $this->siteLangId));
        $this->_template->render(false, false, 'json-success.php');
    }

    public function deleteVolumeDiscountArr()
    {
        $splpriceIdArr = FatApp::getPostedData('selprod_ids');
        $splpriceIds = FatUtility::int($splpriceIdArr);
        foreach ($splpriceIds as $voldiscount_id => $selProdId) {
            $volumeDiscountRow = SellerProductVolumeDiscount::getAttributesById($voldiscount_id);
            $sellerProductRow = SellerProduct::getAttributesById($volumeDiscountRow['voldiscount_selprod_id'], array('selprod_user_id'), false);
            if (!$volumeDiscountRow || !$sellerProductRow) {
                LibHelper::exitWithError(Labels::getLabel('ERR_INVALID_REQUEST', $this->siteLangId), true);
            }

            $this->deleteVolumeDiscount($voldiscount_id, $volumeDiscountRow['voldiscount_selprod_id']);
        }
        $this->set('msg', Labels::getLabel('MSG_VOLUME_DISCOUNT_RECORD_DELETED', $this->siteLangId));
        $this->_template->render(false, false, 'json-success.php');
    }

    private function deleteVolumeDiscount($volumeDiscountId, $volumeDiscountSelprodId)
    {
        $db = FatApp::getDb();
        if (!$db->deleteRecords(SellerProductVolumeDiscount::DB_TBL, array('smt' => 'voldiscount_id = ? AND voldiscount_selprod_id = ?', 'vals' => array($volumeDiscountId, $volumeDiscountSelprodId)))) {
            LibHelper::exitWithError(Labels::getLabel("LBL_" . $db->getError(), $this->siteLangId), true);
        }
        return true;
    }

    private function getSellerProductVolumeDiscountForm($langId)
    {
        return SellerProduct::volumeDiscountForm($langId);
    }

    /* ] */

    public function productTaxRates($selprod_id)
    {
        $selprod_id = Fatutility::int($selprod_id);
        $sellerProductRow = SellerProduct::getAttributesById($selprod_id);

        $taxRates[] = $this->getTaxRates($sellerProductRow['selprod_product_id']);

        $this->set('arrListing', $taxRates);
        $this->set('activeTab', 'TAX');
        $this->set('selprod_id', $sellerProductRow['selprod_id']);
        $this->set('product_id', $sellerProductRow['selprod_product_id']);

        $this->set('html', $this->_template->render(false, false, NULL, true));
        $this->_template->render(false, false, 'json-success.php', true, false);
    }

    private function getTaxRates($productId, $userId = 0)
    {
        $productId = Fatutility::int($productId);
        $userId = Fatutility::int($userId);

        $taxRates = array();
        $taxObj = Tax::getTaxCatObjByProductId($productId, $this->siteLangId);
        $taxObj->addMultipleFields(array('IFNULL(taxcat_name,taxcat_identifier) as taxcat_name', 'ptt_seller_user_id', 'ptt_taxcat_id', 'ptt_product_id'));
        $taxObj->doNotCalculateRecords();

        $cnd = $taxObj->addCondition('ptt_seller_user_id', '=', 0);
        if ($userId > 0) {
            $cnd->attachCondition('ptt_seller_user_id', '=', $userId, 'OR');
        }
        $taxObj->setPageSize(1);
        $taxObj->addOrder('ptt_seller_user_id', 'DESC');

        $rs = $taxObj->getResultSet();
        if ($rs) {
            $taxRates = FatApp::getDb()->fetch($rs);
        }
        return $taxRates ? $taxRates : array();
    }

    private function changeTaxCategoryForm($langId)
    {
        $frm = new Form('frmTaxRate');
        $frm->addHiddenField('', 'selprod_id');
        $taxCatArr = Tax::getSaleTaxCatArr($langId);

        $frm->addSelectBox(Labels::getLabel('FRM_TAX_CATEGORY', $langId), 'ptt_taxcat_id', $taxCatArr, '', array(), Labels::getLabel('FRM_SELECT', $langId))->requirements()->setRequired(true);

        $frm->addSubmitButton('', 'btn_submit', Labels::getLabel('BTN_SAVE_CHANGES', $langId));
        return $frm;
    }

    public function changeTaxCategory($selprod_id)
    {
        $selprod_id = FatUtility::int($selprod_id);
        $sellerProductRow = SellerProduct::getAttributesById($selprod_id);

        /* $srch = Tax::getSearchObject($this->siteLangId);
          $srch->addMultipleFields(array('taxcat_id','IFNULL(taxcat_name,taxcat_identifier) as taxcat_name'));
          $rs =  $srch->getResultSet();
          if($rs){
          $records = FatApp::getDb()->fetchAll($rs,'taxcat_id');
          }
          var_dump($records); */
        $taxRates = $this->getTaxRates($sellerProductRow['selprod_product_id'], $sellerProductRow['selprod_user_id']);
        $frm = $this->changeTaxCategoryForm($this->siteLangId);

        $frm->fill($taxRates + array('selprod_id' => $sellerProductRow['selprod_id']));

        $this->set('frm', $frm);
        $this->set('selprod_id', $sellerProductRow['selprod_id']);
        $this->set('product_id', $sellerProductRow['selprod_product_id']);
        $this->set('html', $this->_template->render(false, false, NULL, true));
        $this->_template->render(false, false, 'json-success.php', true, false);
    }

    public function setUpTaxCategory()
    {
        $this->objPrivilege->canEditSellerProducts();
        $post = FatApp::getPostedData();
        $selprod_id = FatUtility::int($post['selprod_id']);
        if (!$selprod_id) {
            LibHelper::exitWithError(Labels::getLabel('ERR_INVALID_REQUEST', $this->siteLangId), true);
        }

        $sellerProductRow = SellerProduct::getAttributesById($selprod_id);

        $data = array(
            'ptt_product_id' => $sellerProductRow['selprod_product_id'],
            'ptt_taxcat_id' => $post['ptt_taxcat_id'],
            'ptt_seller_user_id' => $sellerProductRow['selprod_user_id']
        );

        $obj = new Tax();
        if (!$obj->addUpdateProductTaxCat($data)) {
            LibHelper::exitWithError($obj->getError(), true);
        }

        $this->set('selprod_id', $selprod_id);
        $this->set('msg', Labels::getLabel('MSG_SETUP_SUCCESSFULLY', $this->siteLangId));
        $this->_template->render(false, false, 'json-success.php');
    }

    public function resetTaxRates($selprod_id)
    {
        $this->objPrivilege->canEditSellerProducts();
        $selprod_id = FatUtility::int($selprod_id);
        $sellerProductRow = SellerProduct::getAttributesById($selprod_id);

        if (!FatApp::getDb()->deleteRecords(Tax::DB_TBL_PRODUCT_TO_TAX, array('smt' => 'ptt_product_id = ? and ptt_seller_user_id = ?', 'vals' => array($sellerProductRow['selprod_product_id'], $sellerProductRow['selprod_user_id'])))) {
            LibHelper::exitWithError(FatApp::getDb()->getError(), true);
        }

        $this->set('selprod_id', $selprod_id);
        $this->set('msg', Labels::getLabel('MSG_RESET_SUCCESSFULLY', $this->siteLangId));
        $this->_template->render(false, false, 'json-success.php');
    }

    public function getBreadcrumbNodes($action)
    {
        $nodes = array();
        $className = get_class($this);
        $arr = explode('-', FatUtility::camel2dashed($className));
        array_pop($arr);
        $urlController = implode('-', $arr);
        $className = ucwords(implode(' ', $arr));
        if ($action == 'index') {
            $nodes[] = array('title' => Labels::getLabel('LBL_SELLER_INVENTORY', $this->siteLangId));
        } elseif ($action == 'upsellProducts') {
            $nodes[] = array('title' => Labels::getLabel('LBL_BUY_TOGETHER_PRODUCTS', $this->siteLangId));
        } else {
            $arr = explode('-', FatUtility::camel2dashed($action));
            $action = ucwords(implode(' ', $arr));
            $nodes[] = array('title' => $action);
        }
        return $nodes;
    }

    public function linkPoliciesForm($product_id, $selprod_id, $ppoint_type)
    {
        $product_id = FatUtility::int($product_id);
        $ppoint_type = FatUtility::int($ppoint_type);
        $selprod_id = FatUtility::int($selprod_id);
        if ($product_id <= 0 || $selprod_id <= 0 || $ppoint_type <= 0) {
            LibHelper::exitWithError(Labels::getLabel('ERR_INVALID_REQUEST', $this->siteLangId), true);
        }
        $productRow = Product::getAttributesById($product_id, array('product_type'));
        $frm = $this->getLinkPoliciesForm($selprod_id, $ppoint_type);
        $data = array('selprod_id' => $selprod_id);
        $frm->fill($data);
        $this->set('product_id', $product_id);
        $this->set('selprod_id', $selprod_id);
        $this->set('frm', $frm);
        $this->set('language', Language::getAllNames());
        $this->set('activeTab', 'GENERAL');
        $this->set('product_type', $productRow['product_type']);
        $this->set('ppoint_type', $ppoint_type);
        $this->set('html', $this->_template->render(false, false, NULL, true));
        $this->_template->render(false, false, 'json-success.php', true, false);
    }

    private function getLinkPoliciesForm($selprod_id, $ppoint_type)
    {
        $frm = new Form('frmLinkWarrantyPolicies');
        $frm->addHiddenField('', 'selprod_id', $selprod_id);
        $frm->addHiddenField('', 'ppoint_type', $ppoint_type);
        $frm->addHiddenField('', 'page');
        return $frm;
    }

    /* public function searchPoliciesToLink()
    {
        $selprod_id = FatApp::getPostedData('selprod_id', FatUtility::VAR_INT, 0);
        $ppoint_type = FatApp::getPostedData('ppoint_type', FatUtility::VAR_INT, 0);
        $searchForm = $this->getLinkPoliciesForm($selprod_id, $ppoint_type);
        $data = FatApp::getPostedData();
        $page = (empty($data['page']) || $data['page'] <= 0) ? 1 : $data['page'];
        $pagesize = FatApp::getConfig('CONF_ADMIN_PAGESIZE', FatUtility::VAR_INT, 10);
        $post = $searchForm->getFormDataFromArray($data);
        $srch = PolicyPoint::getSearchObject($this->siteLangId);
        $srch->joinTable('tbl_seller_product_policies', 'left outer join', 'spp.sppolicy_ppoint_id = pp.ppoint_id and spp.sppolicy_selprod_id=' . $selprod_id, 'spp');
        $srch->addCondition('pp.ppoint_type', '=', $ppoint_type);
        $srch->addMultipleFields(array('*', 'ifnull(sppolicy_selprod_id,0) selProdId'));
        $srch->setPageNumber($page);
        $srch->setPageSize($pagesize);
        $srch->addOrder('selProdId', 'desc');
        $records = FatApp::getDb()->fetchAll($srch->getResultSet(), 'ppoint_id');
        $this->set("selprod_id", $selprod_id);
        $this->set("arrListing", $records);
        $this->set('pageCount', $srch->pages());
        $this->set('recordCount', $srch->recordCount());
        $this->set('page', $page);
        $this->set('pageSize', $pagesize);
        $this->set('postedData', $post);
        $this->_template->render(false, false, 'seller-products/search-policies-to-link.php', false, false);
    } */

    /* Catalog Section [ */

    public function catalog()
    {
        $this->objPrivilege->canViewSellerProducts();
        $frmSearchCatalogProduct = $this->getCatalogProductSearchForm();
        $this->set("frmSearchCatalogProduct", $frmSearchCatalogProduct);
        $this->set('canRequestProduct', User::canRequestProduct());
        $this->_template->render();
    }

    public function requestedCatalog()
    {
        $this->_template->render();
    }

    public function searchRequestedCatalog()
    {
        if (!User::canRequestProduct()) {
            LibHelper::exitWithError(Labels::getLabel('ERR_INVALID_ACCESS', $this->siteLangId), true);
        }
        $post = FatApp::getPostedData();
        $page = (empty($post['page']) || $post['page'] <= 0) ? 1 : intval($post['page']);
        $pagesize = FatApp::getConfig('CONF_PAGE_SIZE', FatUtility::VAR_INT, 10);

        $cRequestObj = new User();
        $srch = $cRequestObj->getUserCatalogRequestsObj();
        $srch->addMultipleFields(
            array(
                'scatrequest_id',
                'scatrequest_user_id',
                'scatrequest_reference',
                'scatrequest_title',
                'scatrequest_comments',
                'scatrequest_status',
                'scatrequest_date'
            )
        );
        $srch->addOrder('scatrequest_date', 'DESC');
        $srch->setPageNumber($page);
        $srch->setPageSize($pagesize);

        $db = FatApp::getDb();
        $rs = $srch->getResultSet();
        $arrListing = array();
        if ($rs) {
            $arrListing = $db->fetchAll($rs);
        }

        $this->set("arrListing", $arrListing);
        $this->set('pageCount', $srch->pages());
        $this->set('page', $page);
        $this->set('pageSize', $pagesize);
        $this->set('postedData', $post);
        $this->set('catalogReqStatusArr', User::getCatalogReqStatusArr($this->siteLangId));
        $this->set('html', $this->_template->render(false, false, NULL, true));
        $this->_template->render(false, false, 'json-success.php', true, false);
    }

    public function addCatalogRequest()
    {
        if (!User::canRequestProduct()) {
            LibHelper::exitWithError(Labels::getLabel('ERR_INVALID_ACCESS', $this->siteLangId), true);
        }
        $frm = $this->addNewCatalogRequestForm();
        $this->set('frm', $frm);
        $this->set('html', $this->_template->render(false, false, NULL, true));
        $this->_template->render(false, false, 'json-success.php', true, false);
    }

    public function setUpCatalogRequest()
    {
        $this->objPrivilege->canEditSellerProducts();
        if (!User::canRequestProduct()) {
            LibHelper::exitWithError(Labels::getLabel('ERR_INVALID_ACCESS', $this->siteLangId), true);
        }
        $userId = UserAuthentication::getLoggedUserId();

        $frm = $this->addNewCatalogRequestForm();
        $post = $frm->getFormDataFromArray(FatApp::getPostedData());

        if (false == $post) {
            LibHelper::exitWithError(current($frm->getValidationErrors()), true);
        }

        $obj = new User($userId);
        $reference_number = $userId . '-' . time();

        $db = FatApp::getDb();
        $db->startTransaction();

        $data = array(
            'scatrequest_user_id' => $userId,
            'scatrequest_reference' => $reference_number,
            'scatrequest_title' => $post['scatrequest_title'],
            'scatrequest_content' => $post['scatrequest_content'],
            'scatrequest_date' => date('Y-m-d H:i:s'),
        );

        if (!$obj->addCatalogRequest($data)) {
            LibHelper::exitWithError($obj->getError(), true);
        }

        $scatrequest_id = FatApp::getDb()->getInsertId();
        if (!$scatrequest_id) {
            LibHelper::exitWithError(Labels::getLabel('ERR_SOMETHING_WENT_WRONG,_PLEASE_CONTACT_ADMIN', $this->siteLangId), true);
        }

        /* attach file with request [ */

        if (is_uploaded_file($_FILES['file']['tmp_name'])) {
            $uploadedFile = $_FILES['file']['tmp_name'];
            $uploadedFileExt = pathinfo($uploadedFile, PATHINFO_EXTENSION);

            if (filesize($uploadedFile) > 10240000) {
                LibHelper::exitWithError(Labels::getLabel('ERR_PLEASE_UPLOAD_FILE_SIZE_LESS_THAN_10MB', $this->siteLangId), true);
            }

            $fileHandlerObj = new AttachedFile();
            if (!$res = $fileHandlerObj->saveAttachment($_FILES['file']['tmp_name'], AttachedFile::FILETYPE_SELLER_CATALOG_REQUEST, $scatrequest_id, 0, $_FILES['file']['name'], -1, true)) {
                LibHelper::exitWithError($fileHandlerObj->getError(), true);
            }
        }

        /* ] */

        if (!$obj->notifyAdminCatalogRequest($data, $this->siteLangId)) {
            $db->rollbackTransaction();
            LibHelper::exitWithError(Labels::getLabel("ERR_NOTIFICATION_EMAIL_COULD_NOT_BE_SENT", $this->siteLangId), true);
        }

        $db->commitTransaction();
        $this->set('msg', Labels::getLabel('MSG_CATALOG_REQUESTED_SUCCESSFULLY', $this->siteLangId));
        $this->_template->render(false, false, 'json-success.php');
    }

    public function viewRequestedCatalog($scatrequest_id)
    {
        $scatrequest_id = FatUtility::int($scatrequest_id);
        if (1 > $scatrequest_id) {
            LibHelper::exitWithError(Labels::getLabel('ERR_INVALID_REQUEST', $this->siteLangId), true);
        }

        $cRequestObj = new User(UserAuthentication::getLoggedUserId());
        $srch = $cRequestObj->getUserCatalogRequestsObj($scatrequest_id);
        $srch->addCondition('tucr.scatrequest_user_id', '=', UserAuthentication::getLoggedUserId());
        $srch->addMultipleFields(array('scatrequest_id', 'scatrequest_title', 'scatrequest_content', 'scatrequest_comments', 'scatrequest_reference'));
        $srch->doNotCalculateRecords();
        $srch->setPageSize(1);

        $rs = $srch->getResultSet();
        if ($rs == false) {
            LibHelper::exitWithError(Labels::getLabel('ERR_INVALID_REQUEST', $this->siteLangId), true);
        }

        $row = FatApp::getDb()->fetch($rs);
        if ($row == false) {
            LibHelper::exitWithError(Labels::getLabel('ERR_INVALID_REQUEST', $this->siteLangId), true);
        }

        $this->set("data", $row);
        $this->set('html', $this->_template->render(false, false, NULL, true));
        $this->_template->render(false, false, 'json-success.php', true, false);
    }

    public function catalogRequestMsgForm($requestId = 0)
    {
        $requestId = FatUtility::int($requestId);
        $frm = $this->getCatalogRequestMessageForm($requestId);

        if (0 >= $requestId) {
            LibHelper::exitWithError(Labels::getLabel('LBL_Invalid_Request', $this->siteLangId), true);
        }
        $userObj = new User();
        $srch = $userObj->getUserSupplierRequestsObj($requestId);
        $srch->addFld('tusr.*');
        $srch->doNotCalculateRecords();
        $srch->setPageSize(1);

        $rs = $srch->getResultSet();

        if (!$rs || FatApp::getDb()->fetch($rs) === false) {
            LibHelper::exitWithError(Labels::getLabel('LBL_Invalid_Request', $this->siteLangId), true);
        }

        $this->set('requestId', $requestId);

        $this->set('frm', $frm);
        $this->set('logged_user_id', UserAuthentication::getLoggedUserId());
        $this->set('logged_user_name', UserAuthentication::getLoggedUserAttribute('user_name'));

        $searchFrm = $this->getCatalogRequestMessageSearchForm();
        $searchFrm->getField('requestId')->value = $requestId;
        $this->set('searchFrm', $searchFrm);

        $this->set('html', $this->_template->render(false, false, NULL, true));
        $this->_template->render(false, false, 'json-success.php', true, false);
    }

    public function catalogRequestMessageSearch()
    {
        $frm = $this->getCatalogRequestMessageSearchForm();
        $post = $frm->getFormDataFromArray(FatApp::getPostedData());
        $page = (empty($post['page']) || $post['page'] <= 0) ? 1 : FatUtility::int($post['page']);
        $pageSize = 1;

        $requestId = isset($post['requestId']) ? FatUtility::int($post['requestId']) : 0;

        $srch = new CatalogRequestMessageSearch();
        $srch->joinCatalogRequests();
        $srch->joinMessageUser();
        $srch->joinMessageAdmin();
        $srch->addCondition('scatrequestmsg_scatrequest_id', '=', $requestId);
        $srch->setPageNumber($page);
        $srch->setPageSize($pageSize);
        $srch->addOrder('scatrequestmsg_id', 'DESC');
        $srch->addMultipleFields(
            array(
                'scatrequestmsg_id',
                'scatrequestmsg_from_user_id',
                'scatrequestmsg_from_admin_id',
                'admin_name',
                'admin_username',
                'admin_email',
                'scatrequestmsg_msg',
                'scatrequestmsg_date',
                'msg_user.user_name as msg_user_name',
                'msg_user_cred.credential_username as msg_username',
                'msg_user_cred.credential_email as msg_user_email',
                'scatrequest_status'
            )
        );

        $rs = $srch->getResultSet();
        $messagesList = FatApp::getDb()->fetchAll($rs, 'scatrequestmsg_id');
        ksort($messagesList);

        $this->set('messagesList', $messagesList);
        $this->set('page', $page);
        $this->set('pageSize', $pageSize);
        $this->set('pageCount', $srch->pages());
        $this->set('postedData', $post);

        $startRecord = ($page - 1) * $pageSize + 1;
        $endRecord = $page * $pageSize;
        $totalRecords = $srch->recordCount();
        if ($totalRecords < $endRecord) {
            $endRecord = $totalRecords;
        }
        $json['totalRecords'] = $totalRecords;
        $json['startRecord'] = $startRecord;
        $json['endRecord'] = $endRecord;

        $json['html'] = $this->_template->render(false, false, 'seller-products/catalog-request-messages-list.php', true);
        $json['loadMoreBtnHtml'] = $this->_template->render(false, false, 'seller-products/catalog-request-messages-list-load-more-btn.php', true);
        //FatUtility::dieJsonSuccess($json);

        $this->set('msg', $json);
        $this->_template->render(false, false, 'json-success.php');
    }

    public function setUpCatalogRequestMessage()
    {
        $this->objPrivilege->canEditSellerProducts();
        $requestId = FatApp::getPostedData('requestId', null, '0');
        $frm = $this->getCatalogRequestMessageForm($requestId);
        $post = $frm->getFormDataFromArray(FatApp::getPostedData());
        if (false === $post) {
            LibHelper::exitWithError(current($frm->getValidationErrors()), true);
        }

        $requestId = FatUtility::int($requestId);

        $srch = new CatalogRequestSearch($this->siteLangId);
        $srch->addCondition('scatrequest_id', '=', $requestId);
        $srch->doNotCalculateRecords();
        $srch->setPageSize(1);
        $srch->addMultipleFields(array('scatrequest_id', 'scatrequest_status'));
        $rs = $srch->getResultSet();
        $requestRow = FatApp::getDb()->fetch($rs);
        if (!$requestRow) {
            LibHelper::exitWithError(Labels::getLabel('ERR_INVALID_ACCESS', $this->siteLangId), true);
        }

        /* save catalog request message[ */
        $dataToSave = array(
            'scatrequestmsg_scatrequest_id' => $requestRow['scatrequest_id'],
            'scatrequestmsg_from_user_id' => UserAuthentication::getLoggedUserId(),
            'scatrequestmsg_from_admin_id' => 0,
            'scatrequestmsg_msg' => $post['message'],
            'scatrequestmsg_date' => date('Y-m-d H:i:s'),
        );
        $catRequestMsgObj = new CatalogRequestMessage();
        $catRequestMsgObj->assignValues($dataToSave, true);
        if (!$catRequestMsgObj->save()) {
            LibHelper::exitWithError($catRequestMsgObj->getError(), true);
        }
        $scatrequestmsg_id = $catRequestMsgObj->getMainTableRecordId();
        if (!$scatrequestmsg_id) {
            LibHelper::exitWithError(Labels::getLabel('ERR_SOMETHING_WENT_WRONG,_PLEASE_CONTACT_TECHNICAL_TEAM', $this->siteLangId), true);
        }
        /* ] */

        /* sending of email notification[ */
        $emailNotificationObj = new EmailHandler();
        if (!$emailNotificationObj->sendCatalogRequestMessageNotification($scatrequestmsg_id, $this->siteLangId)) {
            LibHelper::exitWithError($emailNotificationObj->getError(), true);
        }
        /* ] */

        $this->set('scatrequestmsg_scatrequest_id', $requestId);
        $this->set('msg', Labels::getLabel('MSG_Message_Submitted_Successfully!', $this->siteLangId));
        $this->_template->render(false, false, 'json-success.php');
    }

    public function deleteRequestedCatalog()
    {
        $this->objPrivilege->canEditSellerProducts();
        $post = FatApp::getPostedData();
        $scatrequest_id = FatUtility::int($post['scatrequest_id']);

        if (1 > $scatrequest_id) {
            LibHelper::exitWithError(Labels::getLabel('ERR_INVALID_REQUEST', $this->siteLangId), true);
        }

        $cRequestObj = new User(UserAuthentication::getLoggedUserId());
        $srch = $cRequestObj->getUserCatalogRequestsObj($scatrequest_id);
        $srch->addCondition('tucr.scatrequest_user_id', '=', UserAuthentication::getLoggedUserId());
        $srch->addCondition('tucr.scatrequest_status', '=', 0);
        $srch->addMultipleFields(array('scatrequest_id', 'scatrequest_status'));
        $srch->doNotCalculateRecords();
        $srch->setPageSize(1);

        $rs = $srch->getResultSet();

        if ($rs == false) {
            LibHelper::exitWithError(Labels::getLabel('ERR_INVALID_REQUEST', $this->siteLangId), true);
        }

        $row = FatApp::getDb()->fetch($rs);

        if ($row == false || ($row != false && $row['scatrequest_status'] != User::CATALOG_REQUEST_PENDING)) {
            LibHelper::exitWithError(Labels::getLabel('ERR_INVALID_REQUEST', $this->siteLangId), true);
        }

        if (!$cRequestObj->deleteCatalogRequest($row['scatrequest_id'])) {
            LibHelper::exitWithError($cRequestObj->getError(), true);
        }

        $this->set('scatrequest_id', $row['scatrequest_id']);
        $this->set('msg', Labels::getLabel('MSG_RECORD_DELETED_SUCCESSFULLY', $this->siteLangId));
        $this->_template->render(false, false, 'json-success.php');
    }

    public function searchCatalogProduct()
    {
        $frmSearchCatalogProduct = $this->getCatalogProductSearchForm();
        $post = $frmSearchCatalogProduct->getFormDataFromArray(FatApp::getPostedData());
        $page = (empty($post['page']) || $post['page'] <= 0) ? 1 : intval($post['page']);
        $pagesize = FatApp::getConfig('CONF_PAGE_SIZE', FatUtility::VAR_INT, 10);

        $srch = Product::getSearchObject($this->siteLangId);
        $srch->joinTable(AttributeGroup::DB_TBL, 'LEFT OUTER JOIN', 'product_attrgrp_id = attrgrp_id', 'attrgrp');
        //$cnd = $srch->addCondition( 'product_seller_id', '=',0);
        /* if( User::canAddCustomProduct() ){
          $cnd->attachCondition( 'product_seller_id', '=',UserAuthentication::getLoggedUserId(),'OR');
          } */
        $srch->addCondition('product_active', '=', applicationConstants::ACTIVE);

        $keyword = FatApp::getPostedData('keyword', null, '');
        if (!empty($keyword)) {
            $cnd = $srch->addCondition('product_name', 'like', '%' . $keyword . '%');
            $cnd->attachCondition('product_identifier', 'like', '%' . $keyword . '%', 'OR');
            $cnd->attachCondition('attrgrp_name', 'like', '%' . $keyword . '%');
            $cnd->attachCondition('product_model', 'like', '%' . $keyword . '%');
        }

        $srch->addMultipleFields(
            array(
                'product_id',
                'product_identifier',
                'product_name',
                'product_added_on',
                'product_model',
                'product_attrgrp_id',
                'attrgrp_name'
            )
        );
        $srch->addOrder('product_added_on', 'DESC');
        $srch->setPageNumber($page);
        $srch->setPageSize($pagesize);

        $db = FatApp::getDb();
        $rs = $srch->getResultSet();
        $arrListing = $db->fetchAll($rs);

        $this->set("arrListing", $arrListing);
        $this->set('pageCount', $srch->pages());
        $this->set('page', $page);
        $this->set('pageSize', $pagesize);
        $this->set('postedData', $post);
        $this->set('siteLangId', $this->siteLangId);

        unset($post['page']);
        $frmSearchCatalogProduct->fill($post);
        $this->set("frmSearchCatalogProduct", $frmSearchCatalogProduct);
        $this->set('recordCount', $srch->recordCount());
        $this->set('html', $this->_template->render(false, false, NULL, true));
        $this->_template->render(false, false, 'json-success.php', true, false);
    }

    private function getCatalogRequestMessageSearchForm()
    {
        $frm = new Form('frmCatalogRequestMsgsSrch');
        $frm->addHiddenField('', 'page');
        $frm->addHiddenField('', 'requestId');
        return $frm;
    }

    private function getCatalogProductSearchForm()
    {
        $frm = new Form('frmSearchCatalogProduct');
        $frm->addTextBox(Labels::getLabel('FRM_KEYWORD', $this->siteLangId), 'keyword');
        $fld_submit = $frm->addSubmitButton('', 'btn_submit', Labels::getLabel('BTN_SUBMIT', $this->siteLangId));
        $fld_cancel = $frm->addButton("", "btn_clear", Labels::getLabel('BTN_CLEAR', $this->siteLangId));
        $fld_submit->attachField($fld_cancel);
        $frm->addHiddenField('', 'page');
        return $frm;
    }

    private function getCatalogRequestMessageForm($requestId)
    {
        $frm = new Form('catalogRequestMsgForm');

        $frm->addHiddenField('', 'requestId', $requestId);
        $frm->addTextArea(Labels::getLabel('FRM_MESSAGE', $this->siteLangId), 'message');
        $frm->addSubmitButton('', 'btn_submit', Labels::getLabel('BTN_SEND', $this->siteLangId));
        return $frm;
    }

    /* Catalog section closed ] */

    private function isShopActive($userId, $shopId = 0, $returnResult = false)
    {
        $shop = new Shop($shopId, $userId);
        if (false == $returnResult) {
            return $shop->isActive();
        }

        if ($shop->isActive()) {
            return $shop->getData();
        }

        return false;
    }

    private function addNewCatalogRequestForm()
    {
        $frm = new Form('frmAddCatalogRequest', array('enctype' => "multipart/form-data"));
        $frm->addRequiredField(Labels::getLabel('FRM_TITLE', $this->siteLangId), 'scatrequest_title');
        /* $fld = $frm->addHtmlEditor(Labels::getLabel('FRM_CONTENT',$this->siteLangId),'scatrequest_content');
          $fld->htmlBeforeField = '<div class="editor-bar">';
          $fld->htmlAfterField = '</div>'; */
        $frm->addTextArea(Labels::getLabel('FRM_CONTENT', $this->siteLangId), 'scatrequest_content');
        $fileFld = $frm->addFileUpload(Labels::getLabel('FRM_UPLOAD_FILE', $this->siteLangId), 'file', array('accept' => 'image/*,.zip', 'enctype' => "multipart/form-data"));
        $fileFld->htmlAfterField = '<span class="text--small">' . Labels::getLabel('MSG_ONLY_IMAGE_EXTENSIONS_AND_ZIP_IS_ALLOWED', $this->siteLangId) . '</span>';
        $frm->addSubmitButton('', 'btn_submit', Labels::getLabel('BTN_SAVE_CHANGES', $this->siteLangId));
        return $frm;
    }

    public function thresholdProducts()
    {
        $this->objPrivilege->canViewSellerProducts();
        $this->set('frmSearch', $this->getThresholdLevelProductsSearchForm());
        $this->_template->render();
    }

    public function searchThresholdLevelProducts()
    {
        $frmSearch = $this->getThresholdLevelProductsSearchForm();

        $data = FatApp::getPostedData();
        $post = $frmSearch->getFormDataFromArray($data);

        $page = (empty($data['page']) || $data['page'] <= 0) ? 1 : $data['page'];
        $page = (empty($page) || $page <= 0) ? 1 : $page;
        $page = FatUtility::int($page);
        $pagesize = FatApp::getConfig('CONF_ADMIN_PAGESIZE', FatUtility::VAR_INT, 10);
        $srch = SellerProduct::getSearchObject($this->siteLangId);

        $srch->joinTable(Product::DB_TBL, 'INNER JOIN', 'p.product_id = sp.selprod_product_id', 'p');
        $srch->joinTable(Product::DB_TBL_LANG, 'LEFT OUTER JOIN', 'p.product_id = p_l.productlang_product_id AND p_l.productlang_lang_id = ' . $this->siteLangId, 'p_l');
        $srch->joinTable(User::DB_TBL_CRED, 'LEFT OUTER JOIN', 'cred.credential_user_id = selprod_user_id', 'cred');
        $srch->joinTable('tbl_email_archives', 'LEFT OUTER JOIN', 'arch.earch_to_email = cred.credential_email', 'arch');
        if (isset($post['keyword']) && '' != $post['keyword']) {
            $condition = $srch->addCondition('product_name', 'LIKE', '%' . $post['keyword'] . '%');
            $condition->attachCondition('selprod_title', 'LIKE', '%' . $post['keyword'] . '%');
        }
        /* $cnd = $srch->addCondition('emailarchive_tpl_name', 'LIKE', 'threshold_notification_vendor_custom');
          $cnd->attachCondition('emailarchive_tpl_name', 'LIKE', 'threshold_notification_vendor', 'OR'); */
        $srch->addDirectCondition('selprod_stock <= selprod_threshold_stock_level');
        $srch->addDirectCondition('selprod_track_inventory = ' . Product::INVENTORY_TRACK);
        $srch->addMultipleFields(array('selprod_id', 'selprod_user_id', 'IF(selprod_title is NULL or selprod_title = "" ,product_name, selprod_title) as product_name', 'selprod_stock', 'selprod_threshold_stock_level', 'emailarchive_sent_on'));

        $srch->setPageNumber($page);
        $srch->setPageSize($pagesize);
        $srch->addGroupBy('selprod_id');
        $srch->addOrder('selprod_id', 'DESC');

        $rs = $srch->getResultSet();
        $db = FatApp::getDb();

        $products = $db->fetchAll($rs, 'selprod_id');
        $this->set("arrListing", $products);
        $this->set('pageCount', $srch->pages());
        $this->set('page', $page);
        $this->set('pageSize', $pagesize);
        $this->set('postedData', $post);
        $this->set('recordCount', $srch->recordCount());
        $this->set('html', $this->_template->render(false, false, NULL, true));
        $this->_template->render(false, false, 'json-success.php', true, false);
    }

    public function sendMailForm($user_id, $selprod_id)
    {
        $user_id = FatUtility::int($user_id);
        $selprod_id = FatUtility::int($selprod_id);
        $userObj = new User($user_id);
        $user = $userObj->getUserInfo(null, false, false);
        if (!$user) {
            LibHelper::exitWithError($this->str_invalid_request, true);
        }
        $frm = $this->getSendMailForm($user_id, $selprod_id);

        $this->set('frm', $frm);
        $this->set('html', $this->_template->render(false, false, NULL, true));
        $this->_template->render(false, false, 'json-success.php', true, false);
    }

    public function sendMailThresholdStock($user_id, $selprod_id)
    {
        $user_id = FatUtility::int($user_id);
        $selprod_id = FatUtility::int($selprod_id);

        $userObj = new User($user_id);
        $user = $userObj->getUserInfo(null, false, false);
        if (!$user) {
            LibHelper::exitWithError($this->str_invalid_request, true);
        }

        $emailNotificationObj = new EmailHandler();
        if (!$emailNotificationObj->sendProductStockAlert($selprod_id, $this->siteLangId)) {
            LibHelper::exitWithError($emailNotificationObj->getError(), true);
        }
        $this->set('msg', Labels::getLabel('MSG_YOUR_MESSAGE_SENT_TO', $this->siteLangId) . ' - ' . $user["credential_email"]);
        $this->_template->render(false, false, 'json-success.php');
    }

    private function getSendMailForm($user_id = 0, $selprod_id = 0)
    {
        $user_id = FatUtility::int($user_id);
        $selprod_id = FatUtility::int($selprod_id);
        $frm = new Form('sendMailFrm');
        $frm->addHiddenField('', 'user_id', $user_id);
        $frm->addHiddenField('', 'selprod_id', $selprod_id);

        $frm->addTextBox(Labels::getLabel('FRM_SUBJECT', $this->siteLangId), 'mail_subject')->requirements()->setRequired(true);
        $frm->addTextArea(Labels::getLabel('FRM_MESSAGE', $this->siteLangId), 'mail_message')->requirements()->setRequired(true);

        $frm->addSubmitButton('', 'btn_submit', Labels::getLabel('BTN_SEND', $this->siteLangId), array('id' => 'btn_submit'));
        return $frm;
    }

    private function getThresholdLevelProductsSearchForm()
    {
        $frm = new Form('frmProductSearch');
        $frm->addTextBox(Labels::getLabel('FRM_KEYWORD', $this->siteLangId), 'keyword', '', array('id' => 'keyword', 'autocomplete' => 'off'));
        $fld_submit = $frm->addSubmitButton('&nbsp;', 'btn_submit', Labels::getLabel('BTN_SEARCH', $this->siteLangId));
        $fld_cancel = $frm->addButton("", "btn_clear", Labels::getLabel('BTN_CLEAR', $this->siteLangId));
        $fld_submit->attachField($fld_cancel);
        return $frm;
    }

    public function sellerProductDelete()
    {
        $this->objPrivilege->canEditSellerProducts();
        $selprod_id = FatApp::getPostedData('id', FatUtility::VAR_INT, 0);
        if ($selprod_id < 1) {
            LibHelper::exitWithError(Labels::getLabel('ERR_INVALID_REQUEST_ID', $this->siteLangId), true);
        }

        $this->markAsDeleted($selprod_id);

        $this->set("msg", Labels::getLabel('MSG_RECORD_DELETED_SUCCESSFULLY', $this->siteLangId));
        /* FatUtility::dieJsonSuccess(
          Labels::getLabel('MSG_RECORD_DELETED_SUCCESSFULLY',$this->siteLangId)
          ); */
        $this->_template->render(false, false, 'json-success.php');
    }

    public function deleteSelected()
    {
        $this->objPrivilege->canEditSellerProducts();
        $selprod_ids_arr = FatUtility::int(FatApp::getPostedData('selprod_ids'));
        if (empty($selprod_ids_arr)) {
            LibHelper::exitWithError(Labels::getLabel('ERR_INVALID_REQUEST', $this->siteLangId), true);
        }

        foreach ($selprod_ids_arr as $selprod_id) {
            if (0 >= $selprod_id) {
                continue;
            }
            $this->markAsDeleted($selprod_id);
        }
        $this->set('msg', Labels::getLabel('MSG_RECORDS_DELETED_SUCCESSFULLY', $this->siteLangId));
        $this->_template->render(false, false, 'json-success.php');
    }

    protected function markAsDeleted($selprod_id)
    {
        $selprod_id = FatUtility::int($selprod_id);
        if (1 > $selprod_id) {
            LibHelper::exitWithError(Labels::getLabel('ERR_INVALID_REQUEST', $this->siteLangId), true);
        }

        $selprodObj = new SellerProduct($selprod_id);
        if (!$selprodObj->deleteSellerProduct($selprod_id)) {
            LibHelper::exitWithError(Labels::getLabel('ERR_INVALID_REQUEST_ID', $this->siteLangId), true);
        }
    }

    public function updateStatus()
    {
        $this->objPrivilege->canEditSellerProducts();
        $selprodId = FatApp::getPostedData('recordId', FatUtility::VAR_INT, 0);
        if (0 == $selprodId) {
            LibHelper::exitWithError($this->str_invalid_request_id, true);
        }
        $sellerProductData = SellerProduct::getAttributesById($selprodId, array('selprod_active'));

        if (!$sellerProductData) {
            LibHelper::exitWithError($this->str_invalid_request, true);
        }

        $status = ($sellerProductData['selprod_active'] == applicationConstants::ACTIVE) ? applicationConstants::INACTIVE : applicationConstants::ACTIVE;

        $this->updateSellerProductStatus($selprodId, $status);
        $productId = SellerProduct::getAttributesById($selprodId, 'selprod_product_id', false);
        Product::updateMinPrices($productId);

        $this->set('msg', Labels::getLabel('MSG_STATUS_UPDATED', $this->siteLangId));
        $this->_template->render(false, false, 'json-success.php');
    }

    public function toggleBulkStatuses()
    {
        $this->objPrivilege->canEditSellerProducts();
        $status = FatApp::getPostedData('status', FatUtility::VAR_INT, -1);
        $selprodIdsArr = FatUtility::int(FatApp::getPostedData('selprod_ids'));
        if (empty($selprodIdsArr) || -1 == $status) {
            LibHelper::exitWithError(Labels::getLabel('ERR_INVALID_REQUEST', $this->siteLangId), true);
        }

        foreach ($selprodIdsArr as $selprodId) {
            if (1 > $selprodId) {
                continue;
            }
            $this->updateSellerProductStatus($selprodId, $status);
        }
        $this->set('msg', Labels::getLabel('MSG_STATUS_UPDATED', $this->siteLangId));
        $this->_template->render(false, false, 'json-success.php');
    }

    private function updateSellerProductStatus($selprodId, $status)
    {
        $status = FatUtility::int($status);
        $selprodId = FatUtility::int($selprodId);
        if (1 > $selprodId || -1 == $status) {
            LibHelper::exitWithError(Labels::getLabel('ERR_INVALID_REQUEST', $this->siteLangId), true);
        }

        $selprodTitle = SellerProduct::getAttributesByLangId($this->siteLangId, $selprodId, 'selprod_title');
        $oldSelprodData = SellerProduct::getAttributesById($selprodId, ['selprod_user_id', 'selprod_active']);
        if (
            $status == applicationConstants::ACTIVE &&
            FatApp::getConfig('CONF_ENABLE_SELLER_SUBSCRIPTION_MODULE', FatUtility::VAR_INT, 0) &&
            SellerProduct::getActiveCount($oldSelprodData['selprod_user_id']) >= SellerPackages::getAllowedLimit($oldSelprodData['selprod_user_id'], $this->siteLangId, 'ossubs_inventory_allowed')
        ) {
            LibHelper::exitWithError(CommonHelper::replaceStringData(Labels::getLabel('ERR_UNABLE_TO_CHANGE_STATUS_FOR_"{PRODUCT-NAME}"._AS_SELLER_SUBSCRIPTION_PACKAGE_LIMIT_CROSSED.', $this->siteLangId), ['{PRODUCT-NAME}' => $selprodTitle]), true);
        }

        $sellerProdObj = new SellerProduct($selprodId);
        if (!$sellerProdObj->changeStatus($status)) {
            LibHelper::exitWithError($sellerProdObj->getError(), true);
        }
    }

    public function specialPrice($selProd_id = 0)
    {
        $selProd_id = FatUtility::int($selProd_id);

        if (0 < $selProd_id || 0 > $selProd_id) {
            $selProd_id = SellerProduct::getAttributesByID($selProd_id, 'selprod_id', false);
            if (empty($selProd_id)) {
                Message::addErrorMessage(Labels::getLabel('ERR_INVALID_REQUEST', $this->siteLangId));
                FatApp::redirectUser(UrlHelper::generateUrl('SellerProducts', 'specialPrice'));
            }
        }

        $srchFrm = $this->getSpecialPriceSearchForm();
        $selProdIdsArr = FatApp::getPostedData('selprod_ids', FatUtility::VAR_INT, 0);

        $dataToEdit = array();
        if (!empty($selProdIdsArr) || 0 < $selProd_id) {
            $selProdIdsArr = (0 < $selProd_id) ? array($selProd_id) : $selProdIdsArr;
            $productsTitle = SellerProduct::getProductDisplayTitle($selProdIdsArr, $this->siteLangId);
            foreach ($selProdIdsArr as $selProdId) {
                $dataToEdit[] = array(
                    'product_name' => html_entity_decode($productsTitle[$selProdId], ENT_QUOTES, 'UTF-8'),
                    'splprice_selprod_id' => $selProdId,
                    'selprod_price' => SellerProduct::getAttributesById($selProdId, 'selprod_price')
                );
            }
        } else {
            $post = $srchFrm->getFormDataFromArray(FatApp::getPostedData());

            if (false === $post) {
                LibHelper::exitWithError(current($srchFrm->getValidationErrors()), true);
            } else {
                unset($post['btn_submit'], $post['btn_clear']);
                $srchFrm->fill($post);
            }
        }
        if (0 < $selProd_id) {
            $srchFrm->addHiddenField('', 'selprod_id', $selProd_id);
            $srchFrm->fill(array('keyword' => $productsTitle[$selProd_id]));
        }

        $this->set("dataToEdit", $dataToEdit);
        $this->set("frmSearch", $srchFrm);
        $this->set("selProd_id", $selProd_id);

        $this->_template->addJs(array('js/select2.js'));
        $this->_template->addCss(array('css/select2.min.css'));

        $this->_template->render();
    }

    public function volumeDiscount($selProd_id = 0)
    {
        $selProd_id = FatUtility::int($selProd_id);

        if (0 < $selProd_id || 0 > $selProd_id) {
            $selProd_id = SellerProduct::getAttributesByID($selProd_id, 'selprod_id', false);
            if (empty($selProd_id)) {
                Message::addErrorMessage(Labels::getLabel('ERR_INVALID_REQUEST', $this->siteLangId));
                FatApp::redirectUser(UrlHelper::generateUrl('SellerProducts', 'volumeDiscount'));
            }
        }

        $srchFrm = $this->getVolumeDiscountSearchForm();
        $selProdIdsArr = FatApp::getPostedData('selprod_ids', FatUtility::VAR_INT, 0);

        $dataToEdit = array();
        if (!empty($selProdIdsArr) || 0 < $selProd_id) {
            $selProdIdsArr = (0 < $selProd_id) ? array($selProd_id) : $selProdIdsArr;
            $productsTitle = SellerProduct::getProductDisplayTitle($selProdIdsArr, $this->siteLangId);
            foreach ($selProdIdsArr as $selProdId) {
                $dataToEdit[] = array(
                    'product_name' => html_entity_decode($productsTitle[$selProdId], ENT_QUOTES, 'UTF-8'),
                    'voldiscount_selprod_id' => $selProdId,
                    'selprod_stock' => SellerProduct::getAttributesById($selProdId, 'selprod_stock')
                );
            }
        } else {
            $post = $srchFrm->getFormDataFromArray(FatApp::getPostedData());

            if (false === $post) {
                LibHelper::exitWithError(current($srchFrm->getValidationErrors()), true);
            } else {
                unset($post['btn_submit'], $post['btn_clear']);
                $srchFrm->fill($post);
            }
        }
        if (0 < $selProd_id) {
            $srchFrm->addHiddenField('', 'selprod_id', $selProd_id);
            $srchFrm->fill(array('keyword' => $productsTitle[$selProd_id]));
        }
        $this->set("dataToEdit", $dataToEdit);
        $this->set("frmSearch", $srchFrm);
        $this->set("selProd_id", $selProd_id);
        $this->_template->addJs(array('js/select2.js'));
        $this->_template->addCss(array('css/select2.min.css'));
        $this->_template->render();
    }

    public function searchSpecialPriceProducts()
    {
        $post = FatApp::getPostedData();
        $page = FatApp::getPostedData('page', FatUtility::VAR_INT, 1);
        $selProdId = FatApp::getPostedData('selprod_id', FatUtility::VAR_INT, 0);
        $keyword = FatApp::getPostedData('keyword', FatUtility::VAR_STRING, '');
        $sellerId = FatApp::getPostedData('product_seller_id', FatUtility::VAR_INT, 0);
        $pagesize = FatApp::getConfig('CONF_PAGE_SIZE', FatUtility::VAR_INT, 10);
        $srch = SellerProduct::searchSpecialPriceProductsObj($this->siteLangId, $selProdId, $keyword, $sellerId);
        $srch->addMultipleFields(
            array(
                'selprod_id',
                'credential_username',
                'selprod_price',
                'date(splprice_start_date) as splprice_start_date',
                'splprice_end_date',
                'IFNULL(product_name, product_identifier) as product_name',
                'selprod_title',
                'splprice_id',
                'splprice_price',
                'selprod_product_id',
                'product_updated_on',
                'user_id',
                'user_updated_on',
                'credential_email',
                'user_name'
            )
        );
        $srch->addOrder('splprice_id', 'DESC');
        $srch->setPageNumber($page);
        $srch->setPageSize($pagesize);
        $db = FatApp::getDb();
        $rs = $srch->getResultSet();
        $arrListing = $db->fetchAll($rs);

        $this->set("arrListing", $arrListing);

        $this->set('page', $page);
        $this->set('pageCount', $srch->pages());
        $this->set('postedData', $post);
        $this->set('recordCount', $srch->recordCount());
        $this->set('pageSize', $pagesize);
        $this->set('html', $this->_template->render(false, false, NULL, true));
        $this->_template->render(false, false, 'json-success.php', true, false);
    }

    public function searchVolumeDiscountProducts()
    {
        $post = FatApp::getPostedData();
        $page = FatApp::getPostedData('page', FatUtility::VAR_INT, 1);
        $selProdId = FatApp::getPostedData('selprod_id', FatUtility::VAR_INT, 0);
        $keyword = FatApp::getPostedData('keyword', FatUtility::VAR_STRING, '');
        $sellerId = FatApp::getPostedData('product_seller_id', FatUtility::VAR_INT, 0);
        $pageSize = FatApp::getConfig('CONF_PAGE_SIZE', FatUtility::VAR_INT, 10);
        $srch = SellerProduct::searchVolumeDiscountProducts($this->siteLangId, $selProdId, $keyword, $sellerId);
        $this->setRecordCount(clone $srch, $pageSize, $page, $post);
        $srch->doNotCalculateRecords();
        $srch->addMultipleFields(
            [
                'selprod_id',
                'credential_username',
                'voldiscount_min_qty',
                'voldiscount_percentage',
                'IFNULL(product_name, product_identifier) as product_name',
                'selprod_title',
                'voldiscount_id',
                'product_updated_on',
                'selprod_product_id',
                'user_id',
                'user_updated_on',
                'credential_email',
                'user_name'
            ]
        );
        $srch->addOrder('voldiscount_id', 'DESC');
        $srch->setPageSize($pageSize);
        $srch->setPageNumber($page);
        $db = FatApp::getDb();
        $rs = $srch->getResultSet();
        $arrListing = $db->fetchAll($rs);

        $this->set("arrListing", $arrListing);
        $this->set('postedData', $post);
        $this->set('html', $this->_template->render(false, false, NULL, true));
        $this->_template->render(false, false, 'json-success.php', true, false);
    }

    private function getSpecialPriceSearchForm()
    {
        $frm = new Form('frmSearch', array('id' => 'frmSearch'));
        $frm->setRequiredStarWith('caption');
        $frm->addTextBox(Labels::getLabel('FRM_KEYWORD', $this->siteLangId), 'keyword');
        $frm->addTextBox(Labels::getLabel('FRM_USER', $this->siteLangId), 'product_seller', '');
        $frm->addHiddenField('', 'product_seller_id');
        $fld_submit = $frm->addSubmitButton('', 'btn_submit', Labels::getLabel('BTN_SEARCH', $this->siteLangId));
        $fld_cancel = $frm->addButton("", "btn_clear", Labels::getLabel('BTN_CLEAR', $this->siteLangId), array('onclick' => 'clearSearch();'));
        $fld_submit->attachField($fld_cancel);
        return $frm;
    }

    private function getVolumeDiscountSearchForm()
    {
        $frm = new Form('frmSearch', array('id' => 'frmSearch'));
        $frm->setRequiredStarWith('caption');
        $frm->addTextBox(Labels::getLabel('FRM_KEYWORD', $this->siteLangId), 'keyword');
        $frm->addTextBox(Labels::getLabel('FRM_USER', $this->siteLangId), 'product_seller', '');
        $frm->addHiddenField('', 'product_seller_id');
        $fld_submit = $frm->addSubmitButton('', 'btn_submit', Labels::getLabel('BTN_SEARCH', $this->siteLangId));
        $fld_cancel = $frm->addButton("", "btn_clear", Labels::getLabel('BTN_CLEAR', $this->siteLangId), array('onclick' => 'clearSearch();'));
        $fld_submit->attachField($fld_cancel);
        return $frm;
    }

    public function updateSpecialPriceRow()
    {
        $data = FatApp::getPostedData();
        if (empty($data)) {
            LibHelper::exitWithError(Labels::getLabel('ERR_INVALID_REQUEST', $this->siteLangId), true);
        }
        $splPriceId = $this->updateSelProdSplPrice($data, true);
        if (!$splPriceId) {
            LibHelper::exitWithError(Labels::getLabel('ERR_INVALID_REQUEST', $this->siteLangId), true);
        }
        // last Param of getProductDisplayTitle function used to get title in html form.
        $productName = SellerProduct::getProductDisplayTitle($data['splprice_selprod_id'], $this->siteLangId, true);

        $srch = SellerProduct::getSearchObject();
        $srch->joinTable(User::DB_TBL_CRED, 'LEFT OUTER JOIN', 'tuc.credential_user_id = sp.selprod_user_id', 'tuc');
        $srch->addMultipleFields(array('credential_username', 'selprod_price'));
        $srch->addCondition('selprod_id', '=', $data['splprice_selprod_id']);
        $srch->setPageSize(1);
        $rs = $srch->getResultSet();
        $row = FatApp::getDb()->fetch($rs);

        $data['credential_username'] = $row['credential_username'];
        $data['selprod_price'] = $row['selprod_price'];
        $data['product_name'] = $productName;

        $this->set('data', $data);
        $this->set('splPriceId', $splPriceId);
        $json = array(
            'status' => true,
            'msg' => Labels::getLabel('LBL_Special_Price_Setup_Successful', $this->siteLangId),
            'data' => $this->_template->render(false, false, 'seller-products/update-special-price-row.php', true)
        );
        FatUtility::dieJsonSuccess($json);
    }

    public function updateVolumeDiscountRow()
    {
        $data = FatApp::getPostedData();

        if (empty($data)) {
            LibHelper::exitWithError(Labels::getLabel('ERR_INVALID_REQUEST', $this->siteLangId), true);
        }

        $selprod_id = FatUtility::int($data['voldiscount_selprod_id']);

        if (1 > $selprod_id) {
            LibHelper::exitWithError(Labels::getLabel('ERR_INVALID_REQUEST', $this->siteLangId), true);
        }

        $volDiscountId = $this->updateSelProdVolDiscount($selprod_id, 0, $data['voldiscount_min_qty'], $data['voldiscount_percentage']);
        if (!$volDiscountId) {
            LibHelper::exitWithError(Labels::getLabel('ERR_Invalid_Response', $this->siteLangId), true);
        }

        // last Param of getProductDisplayTitle function used to get title in html form.
        $productName = SellerProduct::getProductDisplayTitle($data['voldiscount_selprod_id'], $this->siteLangId, true);

        $srch = SellerProduct::getSearchObject();
        $srch->joinTable(User::DB_TBL_CRED, 'LEFT OUTER JOIN', 'tuc.credential_user_id = sp.selprod_user_id', 'tuc');
        $srch->addMultipleFields(array('credential_username'));
        $srch->addCondition('selprod_id', '=', $data['voldiscount_selprod_id']);
        $srch->setPageSize(1);
        $rs = $srch->getResultSet();
        $row = FatApp::getDb()->fetch($rs);

        $data['credential_username'] = $row['credential_username'];
        $data['product_name'] = $productName;
        $this->set('data', $data);
        $this->set('volDiscountId', $volDiscountId);
        $json = array(
            'status' => true,
            'msg' => Labels::getLabel('LBL_Volume_Discount_Setup_Successful', $this->siteLangId),
            'data' => $this->_template->render(false, false, 'seller-products/update-volume-discount-row.php', true)
        );
        FatUtility::dieJsonSuccess($json);
    }

    public function updateSpecialPriceColValue()
    {
        $splPriceId = FatApp::getPostedData('splprice_id', FatUtility::VAR_INT, 0);
        if (1 > $splPriceId) {
            LibHelper::exitWithError(Labels::getLabel('ERR_INVALID_REQUEST', $this->siteLangId), true);
        }

        $attribute = FatApp::getPostedData('attribute', FatUtility::VAR_STRING, '');

        $columns = array('splprice_start_date', 'splprice_end_date', 'splprice_price');
        if (!in_array($attribute, $columns)) {
            LibHelper::exitWithError(Labels::getLabel('ERR_INVALID_REQUEST', $this->siteLangId), true);
        }

        $otherColumns = array_values(array_diff($columns, [$attribute]));
        $otherColumnsValue = SellerProductSpecialPrice::getAttributesById($splPriceId, $otherColumns);
        if (empty($otherColumnsValue)) {
            LibHelper::exitWithError(Labels::getLabel('ERR_INVALID_REQUEST', $this->siteLangId), true);
        }
        $value = FatApp::getPostedData('value');
        $selProdId = FatApp::getPostedData('selProdId', FatUtility::VAR_INT, 0);

        $dataToUpdate = array(
            'splprice_selprod_id' => $selProdId,
            'splprice_id' => $splPriceId,
            $attribute => $value,
        );

        $dataToUpdate += $otherColumnsValue;

        if (!$this->updateSelProdSplPrice($dataToUpdate)) {
            LibHelper::exitWithError(Labels::getLabel('ERR_SOMETHING_WENT_WRONG._PLEASE_TRY_AGAIN.', $this->siteLangId), true);
        }

        if ('splprice_price' == $attribute) {
            $value = CommonHelper::displayMoneyFormat($value, true, true);
        }
        $json = array(
            'status' => true,
            'msg' => Labels::getLabel('MSG_Success', $this->siteLangId),
            'data' => array('value' => $value)
        );
        FatUtility::dieJsonSuccess($json);
    }

    public function updateVolumeDiscountColValue()
    {
        $volDiscountId = FatApp::getPostedData('voldiscount_id', FatUtility::VAR_INT, 0);
        if (1 > $volDiscountId) {
            LibHelper::exitWithError(Labels::getLabel('ERR_INVALID_REQUEST', $this->siteLangId), true);
        }
        $attribute = FatApp::getPostedData('attribute', FatUtility::VAR_STRING, '');

        $columns = array('voldiscount_min_qty', 'voldiscount_percentage');
        if (!in_array($attribute, $columns)) {
            LibHelper::exitWithError(Labels::getLabel('ERR_INVALID_REQUEST', $this->siteLangId), true);
        }

        $otherColumns = array_values(array_diff($columns, [$attribute]));
        $otherColumnsValue = SellerProductVolumeDiscount::getAttributesById($volDiscountId, $otherColumns);
        if (empty($otherColumnsValue)) {
            LibHelper::exitWithError(Labels::getLabel('ERR_INVALID_REQUEST', $this->siteLangId), true);
        }
        $value = FatApp::getPostedData('value');
        $selProdId = FatApp::getPostedData('selProdId', FatUtility::VAR_INT, 0);

        $dataToUpdate = array(
            'voldiscount_id' => $volDiscountId,
            'voldiscount_selprod_id' => $selProdId,
            $attribute => $value
        );
        $dataToUpdate += $otherColumnsValue;

        $volDiscountId = $this->updateSelProdVolDiscount($selProdId, $volDiscountId, $dataToUpdate['voldiscount_min_qty'], $dataToUpdate['voldiscount_percentage']);
        if (!$volDiscountId) {
            LibHelper::exitWithError(Labels::getLabel('ERR_INVALID_RESPONSE', $this->siteLangId), true);
        }

        $json = array(
            'status' => true,
            'msg' => Labels::getLabel('MSG_Success', $this->siteLangId),
            'data' => array('value' => $value)
        );
        FatUtility::dieJsonSuccess($json);
    }

    public function getRelatedProductsList($selprod_id)
    {
        $selprod_id = FatUtility::int($selprod_id);
        $srch = SellerProduct::searchRelatedProducts($this->siteLangId);
        $srch->addCondition(SellerProduct::DB_TBL_RELATED_PRODUCTS_PREFIX . 'sellerproduct_id', '=', $selprod_id);
        $srch->addOrder('selprod_id', 'DESC');
        $rs = $srch->getResultSet();
        $relatedProds = FatApp::getDb()->fetchAll($rs);
        $json = array(
            'selprodId' => $selprod_id,
            'relatedProducts' => $relatedProds
        );
        FatUtility::dieJsonSuccess($json);
        /* $this->set('relatedProducts', $relatedProds);
          $this->set('selprod_id', $selprod_id);
          $this->_template->render(false, false, 'json-success.php'); */
    }

    private function getRelatedProductsForm()
    {
        $frm = new Form('frmRelatedSellerProduct');

        $frm->addHiddenField('', 'selprod_id', 0);
        $prodName = $frm->addSelectBox(Labels::getLabel('FRM_PRODUCT', $this->siteLangId), 'product_name', [], '', array('class' => 'selProd--js', 'placeholder' => Labels::getLabel('FRM_SELECT_PRODUCT', $this->siteLangId)));
        //$prodName = $frm->addTextBox('', 'product_name', '', array('class' => 'selProd--js', 'placeholder' => Labels::getLabel('FRM_SELECT_PRODUCT', $this->siteLangId)));
        $prodName->requirements()->setRequired();
        //$fld1 = $frm->addTextBox('', 'products_related');        
        $frm->addSelectBox(Labels::getLabel('FRM_PRODUCT', $this->siteLangId), 'products_related', [], '');
        // $fld1->htmlAfterField= '<div class="row"><div class="col-md-12"><ul class="list-vertical" id="related-products"></ul></div></div>';
        $frm->addSubmitButton('', 'btn_submit', Labels::getLabel('BTN_SAVE', $this->siteLangId));
        return $frm;
    }

    public function relatedProducts($selProd_id = 0)
    {
        $selProd_id = FatUtility::int($selProd_id);
        if (0 < $selProd_id || 0 > $selProd_id) {
            $selProd_id = SellerProduct::getAttributesByID($selProd_id, 'selprod_id', false);
            if (empty($selProd_id)) {
                Message::addErrorMessage(Labels::getLabel('ERR_INVALID_REQUEST', $this->siteLangId));
                FatApp::redirectUser(UrlHelper::generateUrl('Seller', 'volumeDiscount'));
            }
        }

        $srchFrm = $this->getVolumeDiscountSearchForm();
        $selProdIdsArr = FatApp::getPostedData('selprod_ids', FatUtility::VAR_INT, 0);

        $dataToEdit = array();
        if (!empty($selProdIdsArr) || 0 < $selProd_id) {
            $selProdIdsArr = (0 < $selProd_id) ? array($selProd_id) : $selProdIdsArr;
            $productsTitle = SellerProduct::getProductDisplayTitle($selProdIdsArr, $this->siteLangId);
            foreach ($selProdIdsArr as $selProdId) {
                $dataToEdit[] = array(
                    'product_name' => html_entity_decode($productsTitle[$selProdId], ENT_QUOTES, 'UTF-8'),
                    'voldiscount_selprod_id' => $selProdId
                );
            }
        } else {
            $post = $srchFrm->getFormDataFromArray(FatApp::getPostedData());

            if (false === $post) {
                LibHelper::exitWithError(current($srchFrm->getValidationErrors()), true);
            } else {
                unset($post['btn_submit'], $post['btn_clear']);
                $srchFrm->fill($post);
            }
        }
        if (0 < $selProd_id) {
            $srchFrm->addHiddenField('', 'selprod_id', $selProd_id);
            $srchFrm->fill(array('keyword' => $productsTitle[$selProdId]));
        }

        // $this->_template->addJs(array('js/tagify.min.js','js/tagify.polyfills.js'));
        $this->_template->addCss(array('css/custom-tagify.css'));

        $relProdFrm = $this->getRelatedProductsForm();
        $this->set("dataToEdit", $dataToEdit);
        $this->set("frmSearch", $srchFrm);
        $this->set("relProdFrm", $relProdFrm);
        $this->set("selProd_id", $selProd_id);
        $this->_template->addJs(array('js/select2.js'));
        $this->_template->addCss(array('css/select2.min.css'));
        $this->_template->render();
    }

    public function searchRelatedProducts()
    {
        $page = FatApp::getPostedData('page', FatUtility::VAR_INT, 1);
        $selProdId = FatApp::getPostedData('selprod_id', FatUtility::VAR_INT, 0);
        $keyword = FatApp::getPostedData('keyword', FatUtility::VAR_STRING, '');
        $pagesize = FatApp::getConfig('CONF_ADMIN_PAGESIZE', FatUtility::VAR_INT, 10);

        $db = FatApp::getDb();

        $prodSrch = SellerProduct::searchRelatedProducts($this->siteLangId, 'related_sellerproduct_id');
        if ($keyword != '') {
            $cnd = $prodSrch->addCondition('product_name', 'like', "%$keyword%");
            $cnd->attachCondition('product_identifier', 'LIKE', '%' . $keyword . '%', 'OR');
        }
        $prodSrch->joinTable(User::DB_TBL_CRED, 'LEFT OUTER JOIN', 'tuc.credential_user_id = selprod_user_id', 'tuc');
        $prodSrch->addFld('credential_username');
        $prodSrch->setPageNumber($page);
        $prodSrch->setPageSize($pagesize);
        $prodSrch->addGroupBy('related_sellerproduct_id');
        $rs = $prodSrch->getResultSet();
        $relatedProds = $db->fetchAll($rs);
        $arrListing = array();
        foreach ($relatedProds as $key => $relatedProd) {
            $productId = $relatedProd['related_sellerproduct_id'];
            $srch = SellerProduct::searchRelatedProducts($this->siteLangId);
            $srch->addFld('if(related_sellerproduct_id = ' . $selProdId . ', 1 , 0) as priority');
            $srch->addOrder('priority', 'DESC');
            $srch->addCondition('related_sellerproduct_id', '=', $productId);
            $srch->doNotCalculateRecords();
            $srch->doNotLimitRecords();
            $rs = $srch->getResultSet();
            $arrListing[$productId] = $db->fetchAll($rs);
            $arrListing[$productId]['credential_username'] = $relatedProd['credential_username'];
        }

        $this->set("arrListing", $arrListing);

        $this->set('page', $page);
        $this->set('pageCount', $prodSrch->pages());
        $this->set('postedData', FatApp::getPostedData());
        $this->set('recordCount', $prodSrch->recordCount());
        $this->set('pageSize', FatApp::getConfig('CONF_PAGE_SIZE', FatUtility::VAR_INT, 10));
        $this->set('html', $this->_template->render(false, false, NULL, true));
        $this->_template->render(false, false, 'json-success.php', true, false);
    }

    private function getRelatedProductsSearchForm()
    {
        $frm = new Form('frmSearch', array('id' => 'frmSearch'));
        $frm->setRequiredStarWith('caption');
        $frm->addTextBox(Labels::getLabel('FRM_KEYWORD', $this->siteLangId), 'keyword');
        $fld_submit = $frm->addSubmitButton('', 'btn_submit', Labels::getLabel('BTN_SEARCH', $this->siteLangId));
        $fld_cancel = $frm->addButton("", "btn_clear", Labels::getLabel('BTN_CLEAR', $this->siteLangId), array('onclick' => 'clearSearch();'));
        return $frm;
    }

    public function setupRelatedProduct()
    {
        $post = FatApp::getPostedData();
        $selprod_id = FatUtility::int($post['selprod_id']);
        if ($selprod_id <= 0) {
            LibHelper::exitWithError(Labels::getLabel("ERR_Please_Select_A_Valid_Product", $this->siteLangId), true);
        }

        if (!isset($post['selected_products']) || !is_array($post['selected_products']) || 1 > count($post['selected_products'])) {
            LibHelper::exitWithError(Labels::getLabel("ERR_MUST_SELECT_ATLEAST_ONE_PRODUCT_TO_RELATED_PRODUCTS", $this->siteLangId), true);
        }

        $relatedProducts = $post['selected_products'];
        unset($post['selprod_id']);
        $sellerProdObj = new SellerProduct();
        if (!$sellerProdObj->addUpdateSellerRelatedProdcts($selprod_id, $relatedProducts)) {
            LibHelper::exitWithError($sellerProdObj->getError(), true);
        }

        $this->set('msg', Labels::getLabel('MSG_RELATED_PRODUCT_SETUP_SUCCESSFUL', $this->siteLangId));
        $this->_template->render(false, false, 'json-success.php');
    }

    public function deleteSelprodRelatedProduct($selprod_id, $relprod_id)
    {
        $selprod_id = FatUtility::int($selprod_id);
        $relprod_id = FatUtility::int($relprod_id);
        if (!$selprod_id || !$relprod_id) {
            Message::addErrorMessage(Labels::getLabel('ERR_INVALID_REQUEST', $this->siteLangId));
            FatApp::redirectUser($_SESSION['referer_page_url']);
        }

        $db = FatApp::getDb();
        if (!$db->deleteRecords(SellerProduct::DB_TBL_RELATED_PRODUCTS, array('smt' => 'related_sellerproduct_id = ? AND related_recommend_sellerproduct_id = ?', 'vals' => array($selprod_id, $relprod_id)))) {
            Message::addErrorMessage(Labels::getLabel("ERR_" . $db->getError(), $this->siteLangId));
            FatApp::redirectUser($_SESSION['referer_page_url']);
        }

        $this->set('selprod_id', $selprod_id);
        $this->set('msg', Labels::getLabel('MSG_RECORD_DELETED', $this->siteLangId));
        $this->_template->render(false, false, 'json-success.php');
    }

    public function getUpsellProductsList($selprod_id)
    {
        $selprod_id = FatUtility::int($selprod_id);
        $srch = SellerProduct::searchUpsellProducts($this->siteLangId);
        $srch->addCondition(SellerProduct::DB_TBL_UPSELL_PRODUCTS_PREFIX . 'sellerproduct_id', '=', $selprod_id);
        $srch->addGroupBy('selprod_id');
        $srch->addGroupBy('upsell_sellerproduct_id');
        $srch->addOrder('selprod_id', 'DESC');
        $rs = $srch->getResultSet();
        $upsellProds = FatApp::getDb()->fetchAll($rs);
        $json = array(
            'selprodId' => $selprod_id,
            'upsellProducts' => $upsellProds
        );
        FatUtility::dieJsonSuccess($json);
    }

    private function getUpsellProductsForm()
    {
        $frm = new Form('frmUpsellSellerProduct');

        $frm->addHiddenField('', 'selprod_id', 0);
        $prodName = $frm->addSelectBox(Labels::getLabel('FRM_PRODUCT', $this->siteLangId), 'product_name', [], '', array('class' => 'selProd--js', 'placeholder' => Labels::getLabel('FRM_SELECT_PRODUCT', $this->siteLangId)));
        //$prodName = $frm->addTextBox('', 'product_name', '', array('class' => 'selProd--js', 'placeholder' => Labels::getLabel('FRM_SELECT_PRODUCT', $this->siteLangId)));
        $prodName->requirements()->setRequired();
        //$fld1 = $frm->addTextBox('', 'products_upsell');
        $fld1 = $frm->addSelectBox(Labels::getLabel('FRM_BUY_TOGETHER_PRODUCTS', $this->siteLangId), 'products_upsell', [], '');
        // $fld1->htmlAfterField= '<div class="row"><div class="col-md-12"><ul class="list-vertical" id="upsell-products"></ul></div></div>';
        $frm->addSubmitButton('', 'btn_submit', Labels::getLabel('BTN_SAVE', $this->siteLangId));
        return $frm;
    }

    public function upsellProducts($selProd_id = 0)
    {
        $selProd_id = FatUtility::int($selProd_id);
        if (0 < $selProd_id || 0 > $selProd_id) {
            $selProd_id = SellerProduct::getAttributesByID($selProd_id, 'selprod_id', false);
            if (empty($selProd_id)) {
                Message::addErrorMessage(Labels::getLabel('ERR_INVALID_REQUEST', $this->siteLangId));
                FatApp::redirectUser(UrlHelper::generateUrl('Seller', 'volumeDiscount'));
            }
        }

        $srchFrm = $this->getVolumeDiscountSearchForm();
        $selProdIdsArr = FatApp::getPostedData('selprod_ids', FatUtility::VAR_INT, 0);

        $dataToEdit = array();
        if (!empty($selProdIdsArr) || 0 < $selProd_id) {
            $selProdIdsArr = (0 < $selProd_id) ? array($selProd_id) : $selProdIdsArr;
            $productsTitle = SellerProduct::getProductDisplayTitle($selProdIdsArr, $this->siteLangId);
            foreach ($selProdIdsArr as $selProdId) {
                $dataToEdit[] = array(
                    'product_name' => html_entity_decode($productsTitle[$selProdId], ENT_QUOTES, 'UTF-8'),
                    'voldiscount_selprod_id' => $selProdId
                );
            }
        } else {
            $post = $srchFrm->getFormDataFromArray(FatApp::getPostedData());

            if (false === $post) {
                LibHelper::exitWithError(current($srchFrm->getValidationErrors()), true);
            } else {
                unset($post['btn_submit'], $post['btn_clear']);
                $srchFrm->fill($post);
            }
        }
        if (0 < $selProd_id) {
            $srchFrm->addHiddenField('', 'selprod_id', $selProd_id);
            $srchFrm->fill(array('keyword' => $productsTitle[$selProdId]));
        }

        // $this->_template->addJs(array('js/tagify.min.js','js/tagify.polyfills.js'));
        $this->_template->addCss(array('css/custom-tagify.css'));

        $relProdFrm = $this->getUpsellProductsForm();
        $this->set("dataToEdit", $dataToEdit);
        $this->set("frmSearch", $srchFrm);
        $this->set("relProdFrm", $relProdFrm);
        $this->set("selProd_id", $selProd_id);
        $this->_template->addJs(array('js/select2.js'));
        $this->_template->addCss(array('css/select2.min.css'));
        $this->_template->render();
    }

    public function searchUpsellProducts()
    {
        $page = FatApp::getPostedData('page', FatUtility::VAR_INT, 1);
        $selProdId = FatApp::getPostedData('selprod_id', FatUtility::VAR_INT, 0);
        $keyword = FatApp::getPostedData('keyword', FatUtility::VAR_STRING, '');
        $pagesize = FatApp::getConfig('CONF_ADMIN_PAGESIZE', FatUtility::VAR_INT, 10);

        $db = FatApp::getDb();

        $prodSrch = SellerProduct::searchUpsellProducts($this->siteLangId, 'upsell_sellerproduct_id');
        if ($keyword != '') {
            $cnd = $prodSrch->addCondition('product_name', 'like', "%$keyword%");
            $cnd->attachCondition('product_identifier', 'LIKE', '%' . $keyword . '%', 'OR');
        }
        $prodSrch->joinTable(User::DB_TBL_CRED, 'LEFT OUTER JOIN', 'tuc.credential_user_id = selprod_user_id', 'tuc');
        $prodSrch->addFld('credential_username');
        $prodSrch->setPageNumber($page);
        $prodSrch->setPageSize($pagesize);
        $prodSrch->addGroupBy('upsell_sellerproduct_id');
        $rs = $prodSrch->getResultSet();
        $upsellProds = $db->fetchAll($rs);

        $arrListing = array();
        foreach ($upsellProds as $key => $upsellProd) {
            $productId = $upsellProd['upsell_sellerproduct_id'];
            $srch = SellerProduct::searchUpsellProducts($this->siteLangId);
            $srch->addFld('if(upsell_sellerproduct_id = ' . $selProdId . ', 1 , 0) as priority');
            $srch->addOrder('priority', 'DESC');
            $srch->addCondition('upsell_sellerproduct_id', '=', $productId);
            $srch->addGroupBy('selprod_id');
            $srch->addGroupBy('upsell_sellerproduct_id');
            $srch->doNotCalculateRecords();
            $srch->doNotLimitRecords();
            $rs = $srch->getResultSet();
            $arrListing[$productId] = $db->fetchAll($rs);
            $arrListing[$productId]['credential_username'] = $upsellProd['credential_username'];
        }
        $this->set("arrListing", $arrListing);

        $this->set('page', $page);
        $this->set('pageCount', $prodSrch->pages());
        $this->set('postedData', FatApp::getPostedData());
        $this->set('recordCount', $prodSrch->recordCount());
        $this->set('pageSize', FatApp::getConfig('CONF_PAGE_SIZE', FatUtility::VAR_INT, 10));
        $this->set('html', $this->_template->render(false, false, NULL, true));
        $this->_template->render(false, false, 'json-success.php', true, false);
    }

    private function getUpsellProductsSearchForm()
    {
        $frm = new Form('frmSearch', array('id' => 'frmSearch'));
        $frm->setRequiredStarWith('caption');
        $frm->addTextBox(Labels::getLabel('FRM_KEYWORD', $this->siteLangId), 'keyword');
        $fld_submit = $frm->addSubmitButton('', 'btn_submit', Labels::getLabel('BTN_SEARCH', $this->siteLangId));
        $fld_cancel = $frm->addButton("", "btn_clear", Labels::getLabel('BTN_CLEAR', $this->siteLangId), array('onclick' => 'clearSearch();'));
        return $frm;
    }

    public function setupUpsellProduct()
    {
        $post = FatApp::getPostedData();
        $selprod_id = FatUtility::int($post['selprod_id']);
        if ($selprod_id <= 0) {
            LibHelper::exitWithError(Labels::getLabel("ERR_PLEASE_SELECT_A_VALID_PRODUCT", $this->siteLangId), true);
        }
        if (!isset($post['selected_products']) || !is_array($post['selected_products']) || 1 > count($post['selected_products'])) {
            LibHelper::exitWithError(Labels::getLabel("ERR_MUST_SELECT_ATLEAST_ONE_PRODUCT_TO_BUY_TOGETHER", $this->siteLangId), true);
        }

        $upsellProducts = $post['selected_products'];

        $sellerProdObj = new SellerProduct();
        /* saving of product Upsell Product[ */
        if (!$sellerProdObj->addUpdateSellerUpsellProducts($selprod_id, $upsellProducts)) {
            LibHelper::exitWithError($sellerProdObj->getError(), true);
        }
        /* ] */

        $this->set('msg', Labels::getLabel('MSG_BUY_TOGETHER_PRODUCT_SETUP_SUCCESSFUL', $this->siteLangId));
        $this->_template->render(false, false, 'json-success.php');
    }

    public function deleteSelprodUpsellProduct($selprod_id, $relprod_id)
    {
        $selprod_id = FatUtility::int($selprod_id);
        $relprod_id = FatUtility::int($relprod_id);
        if (!$selprod_id || !$relprod_id) {
            Message::addErrorMessage(Labels::getLabel('ERR_INVALID_REQUEST', $this->siteLangId));
            FatApp::redirectUser($_SESSION['referer_page_url']);
        }

        $db = FatApp::getDb();
        if (!$db->deleteRecords(SellerProduct::DB_TBL_UPSELL_PRODUCTS, array('smt' => 'upsell_sellerproduct_id = ? AND upsell_recommend_sellerproduct_id = ?', 'vals' => array($selprod_id, $relprod_id)))) {
            Message::addErrorMessage(Labels::getLabel("ERR_" . $db->getError(), $this->siteLangId));
            FatApp::redirectUser($_SESSION['referer_page_url']);
        }

        $this->set('selprod_id', $selprod_id);
        $this->set('msg', Labels::getLabel('MSG_RECORD_DELETED', $this->siteLangId));
        $this->_template->render(false, false, 'json-success.php');
    }

    public function isProductRewriteUrlUnique()
    {
        $selprod_id = FatApp::getPostedData('recordId', FatUtility::VAR_INT, 0);
        $urlKeyword = FatApp::getPostedData('url_keyword');
        $sellerProdObj = new SellerProduct($selprod_id);
        $seoUrl = $sellerProdObj->sanitizeSeoUrl($urlKeyword);
        if (1 > $selprod_id) {
            $isUnique = UrlRewrite::isCustomUrlUnique($seoUrl);
            if ($isUnique) {
                FatUtility::dieJsonSuccess(UrlHelper::generateFullUrl('', '', array(), CONF_WEBROOT_FRONT_URL) . $seoUrl);
            }
            LibHelper::exitWithError(Labels::getLabel('ERR_NOT_AVAILABLE._PLEASE_TRY_USING_ANOTHER_KEYWORD', $this->siteLangId), true);
        }

        $originalUrl = $sellerProdObj->getRewriteProductOriginalUrl();
        $customUrlData = UrlRewrite::getDataByCustomUrl($seoUrl, $originalUrl);
        if (empty($customUrlData)) {
            FatUtility::dieJsonSuccess(UrlHelper::generateFullUrl('', '', array(), CONF_WEBROOT_FRONT_URL) . $seoUrl);
        }
        LibHelper::exitWithError(Labels::getLabel('ERR_NOT_AVAILABLE._PLEASE_TRY_USING_ANOTHER_KEYWORD', $this->siteLangId), true);
    }

    public function productMissingInfo()
    {
        $selProdId = FatApp::getPostedData('recordId', FatUtility::VAR_INT, 0);
        if (1 > $selProdId) {
            LibHelper::exitWithError($this->str_invalid_request_id, true);
        }

        $sellerProductRow = SellerProduct::getAttributesById($selProdId, ['selprod_id'], false, false);
        if (!$sellerProductRow) {
            LibHelper::exitWithError($this->str_invalid_request_id, true);
        }

        $this->set('infoArr', SellerProduct::getProdMissingInfo($selProdId, $this->siteLangId));
        $this->set('html', $this->_template->render(false, false, NULL, true));
        $this->_template->render(false, false, 'json-success.php', true, false);
    }

    protected function getFormColumns(): array
    {
        $inventoryHeadingCols = CacheHelper::get('inventoryHeadingCols' . $this->siteLangId, CONF_DEF_CACHE_TIME, '.txt');
        if ($inventoryHeadingCols) {
            return json_decode($inventoryHeadingCols, true);
        }

        $arr = [
            'select_all' => Labels::getLabel('LBL_SELECT_ALL', $this->siteLangId),
            'listSerial' => Labels::getLabel('LBL_SR._NO', $this->siteLangId),
            'selprod_title' => Labels::getLabel('LBL_NAME', $this->siteLangId),
            'user_name' => Labels::getLabel('LBL_SELLER', $this->siteLangId),
            'selprod_price' => Labels::getLabel('LBL_PRICE', $this->siteLangId),
            'selprod_stock' => Labels::getLabel('LBL_QUANTITY', $this->siteLangId),
            'selprod_active' => Labels::getLabel('LBL_STATUS', $this->siteLangId),
            'action' => Labels::getLabel('LBL_ACTION_BUTTONS', $this->siteLangId),
        ];
        CacheHelper::create('inventoryHeadingCols' . $this->siteLangId, json_encode($arr), CacheHelper::TYPE_LABELS);
        return $arr;
    }

    protected function getDefaultColumns(): array
    {
        return [
            'select_all',
            /*  'listSerial', */
            'selprod_title',
            'user_name',
            'selprod_price',
            'selprod_stock',
            'selprod_active',
            'action',
        ];
    }

    protected function excludeKeysForSort($fields = []): array
    {
        return array_diff($fields, Common::excludeKeysForSort());
    }
}
