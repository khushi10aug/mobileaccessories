<?php
class MetaTagsController extends ListingBaseController
{
    protected $pageKey = 'METATAG_MANAGEMENT';

    private array $tabsArr = [];
    private array $postedData = [];
    private string $controller = '';
    private string $action = '';

    public function __construct($action)
    {
        parent::__construct($action);
        $this->objPrivilege->canViewMetaTags();
    }

    public function index()
    {
        $pageData = PageLanguageData::getAttributesByKey($this->pageKey, $this->siteLangId);
        $pageTitle = $pageData['plang_title'] ?? LibHelper::getControllerName(true);

        $this->set('pageData', $pageData);
        $this->set('pageTitle', $pageTitle);
        $this->set('activeTab', MetaTag::META_GROUP_DEFAULT);
        $this->set('metaTypeDefault', MetaTag::META_GROUP_DEFAULT);
        $this->getListingData();
        $this->_template->render();
    }

    public function search()
    {
        $this->getListingData();
        $jsonData = [
            'listingHtml' => $this->_template->render(false, false, 'meta-tags/search.php', true)
        ];
        LibHelper::exitWithSuccess($jsonData, true);
    }

    private function getResults($metaType, $page, $sortBy, $sortOrder, $pageSize)
    {
        switch ($metaType) {
            case MetaTag::META_GROUP_DEFAULT:
                $srch = $this->renderTemplateForDefaultMetaTag();
                break;
            case MetaTag::META_GROUP_PRODUCT_DETAIL:
                $srch = $this->renderTemplateForProductDetail();
                break;
            case MetaTag::META_GROUP_SHOP_DETAIL:
                $srch = $this->renderTemplateForShopDetail();
                break;
            case MetaTag::META_GROUP_ADVANCED:
                $srch = $this->renderTemplateForAdvanced();
                break;
            case MetaTag::META_GROUP_CMS_PAGE:
                $srch = $this->renderTemplateForCMSPage();
                break;
            case MetaTag::META_GROUP_BRAND_DETAIL:
                $srch = $this->renderTemplateForBrandDetail();
                break;
            case MetaTag::META_GROUP_CATEGORY_DETAIL:
                $srch = $this->renderTemplateForCategoryDetail();
                break;
            case MetaTag::META_GROUP_BLOG_CATEGORY:
                $srch = $this->renderTemplateForBlogCategory();
                break;
            case MetaTag::META_GROUP_BLOG_POST:
                $srch = $this->renderTemplateForBlogPost();
                break;
            default:
                $srch = $this->renderTemplateForMetaType();
                break;
        }

        if (!array_key_exists($sortOrder, applicationConstants::sortOrder($this->siteLangId))) {
            $sortOrder = applicationConstants::SORT_ASC;
        }
        $this->setRecordCount(clone $srch, $pageSize, $page, $this->postedData, (MetaTag::META_GROUP_BLOG_POST == $metaType) ? true : false);
        $srch->doNotCalculateRecords();
        $srch->addOrder($sortBy, $sortOrder);
        $srch->setPageNumber($page);
        $srch->setPageSize($pageSize);
        return FatApp::getDb()->fetchAll($srch->getResultSet());
    }

    private function getListingData()
    {
        $db = FatApp::getDb();
        $this->tabsArr = MetaTag::getTabsArr();

        $pageSize = applicationConstants::getPageSize(FatApp::getPostedData('pageSize', FatUtility::VAR_INT));

        $metaType = FatApp::getPostedData('metaType', FatUtility::VAR_STRING, MetaTag::META_GROUP_DEFAULT);
        if (empty($metaType)) {
            $metaType = MetaTag::META_GROUP_DEFAULT;
        }

        $fields = $this->getFormColumns($metaType);
        $this->setCustomColumnWidth($metaType);

        $selectedFlds = FatApp::getPostedData('reportColumns', FatUtility::VAR_STRING, '');
        $selectedFlds = !empty($selectedFlds) ? json_decode($selectedFlds) +  $this->getDefaultColumns() : $this->getDefaultColumns();
        $fields =  FilterHelper::parseArrayByKeys($fields, $selectedFlds, true);

        $allowedKeysForSorting = $this->excludeKeysForSort(array_keys($fields));
        $sortBy = FatApp::getPostedData('sortBy', FatUtility::VAR_STRING, current($allowedKeysForSorting));
        if (empty($sortBy)) {
            $sortBy = $allowedKeysForSorting[2] ?? current($allowedKeysForSorting);
        }

        $sortOrder = applicationConstants::getSortOrder(FatApp::getPostedData('sortOrder', FatUtility::VAR_STRING));

        $searchForm = $this->getMetaTagsSearchForm($metaType, $fields);

        $page = FatApp::getPostedData('page', FatUtility::VAR_INT, 1);
        $page = ($page <= 0) ? 1 : $page;
        $this->postedData = $searchForm->getFormDataFromArray(FatApp::getPostedData());
        $this->postedData['metaType'] = $metaType;

        $this->controller = FatUtility::convertToType($this->tabsArr[$metaType]['controller'], FatUtility::VAR_STRING);
        $this->action = FatUtility::convertToType($this->tabsArr[$metaType]['action'], FatUtility::VAR_STRING);

        $arrListing = $this->getResults($metaType, $page, $sortBy, $sortOrder, $pageSize);
        
        if (empty($arrListing)) {
            switch ($metaType) {
                case MetaTag::META_GROUP_DEFAULT:
                    FatApp::getDb()->query("INSERT INTO tbl_meta_tags(meta_controller, meta_action, meta_record_id, meta_subrecord_id, meta_default, meta_advanced) VALUES ('', '', 0, 0, 1, 0)");
                    break;
                case MetaTag::META_GROUP_ALL_PRODUCTS:
                    FatApp::getDb()->query("INSERT INTO tbl_meta_tags(meta_controller, meta_action, meta_record_id, meta_subrecord_id, meta_default, meta_advanced) VALUES ('Products', 'index', 0, 0, 1, 0)");
                    break;
                case MetaTag::META_GROUP_ALL_SHOPS:
                    FatApp::getDb()->query("INSERT INTO tbl_meta_tags(meta_controller, meta_action, meta_record_id, meta_subrecord_id, meta_default, meta_advanced) VALUES ('Shops', 'index', 0, 0, 1, 0)");
                    break;
                case MetaTag::META_GROUP_ALL_BRANDS:
                    FatApp::getDb()->query("INSERT INTO tbl_meta_tags(meta_controller, meta_action, meta_record_id, meta_subrecord_id, meta_default, meta_advanced) VALUES ('Brands', 'index', 0, 0, 1, 0)");
                    break;
            }
            $arrListing = $this->getResults($metaType, $page, $sortBy, $sortOrder, $pageSize);
        }

        $this->set("arrListing", $arrListing);
        $searchForm->fill($this->postedData);
        $this->set('frmSearch', $searchForm);
        $this->set('postedData', $this->postedData);
        $this->set('sortBy', $sortBy);
        $this->set('sortOrder', $sortOrder);
        $this->set('fields', $fields);
        $this->set('allowedKeysForSorting', $allowedKeysForSorting);
        $this->set('canEdit', $this->objPrivilege->canEditMetaTags($this->admin_id, true));
        $this->set('tabsArr', $this->tabsArr);

        $withoutSearchForm = [
            MetaTag::META_GROUP_DEFAULT,
            MetaTag::META_GROUP_ALL_PRODUCTS,
            MetaTag::META_GROUP_ALL_SHOPS,
            MetaTag::META_GROUP_ALL_BRANDS,
            MetaTag::META_GROUP_BLOG_PAGE
        ];
        $this->set('metaType', $metaType);
        $this->set('loadRows', FatApp::getPostedData('loadRows', FatUtility::VAR_INT, 0));
    }

    private function renderTemplateForDefaultMetaTag(): MetaTagSearch
    {
        $srch = new MetaTagSearch($this->siteLangId);
        $srch->addCondition('mt.meta_controller', '=', '');
        $srch->addCondition('mt.meta_action', '=', '');
        $srch->addCondition('mt.meta_record_id', '=', 0);
        $srch->addCondition('mt.meta_subrecord_id', '=', 0);
        $srch->addCondition('mt.meta_default', '=', 1);
        $srch->addMultipleFields(['mt.* , mt_l.meta_title']);
        return $srch;
    }

    private function renderTemplateForProductDetail(): SearchBase
    {
        $srch = SellerProduct::getSearchObject($this->siteLangId);
        $srch->joinTable(User::DB_TBL, 'LEFT OUTER JOIN', 'selprod_user_id = u.user_id', 'u');
        $srch->joinTable(User::DB_TBL_CRED, 'LEFT OUTER JOIN', 'u.user_id = uc.credential_user_id', 'uc');
        $srch->joinTable(Product::DB_TBL, 'INNER JOIN', 'p.product_id = sp.selprod_product_id', 'p');
        $srch->joinTable(Product::DB_TBL_LANG, 'LEFT OUTER JOIN', 'p.product_id = p_l.productlang_product_id AND p_l.productlang_lang_id = ' . $this->siteLangId, 'p_l');
        $srch->joinTable(MetaTag::DB_TBL, 'LEFT OUTER JOIN', "mt.meta_record_id = sp.selprod_id and mt.meta_controller = '" . $this->controller . "' and mt.meta_action = '" . $this->action . "' ", 'mt');
        $srch->joinTable(MetaTag::DB_TBL_LANG, 'LEFT OUTER JOIN', "mt_l.metalang_meta_id = mt.meta_id AND mt_l.metalang_lang_id = " . $this->siteLangId, 'mt_l');
        $srch->addCondition('selprod_deleted', '=', applicationConstants::NO);
        if (!empty($this->postedData['keyword'])) {
            $condition = $srch->addCondition('mt_l.meta_title', 'like', '%' . $this->postedData['keyword'] . '%');
            $condition->attachCondition('p_l.product_name', 'like', '%' . $this->postedData['keyword'] . '%', 'OR');
            $condition->attachCondition('sp_l.selprod_title', 'like', '%' . $this->postedData['keyword'] . '%', 'OR');
        }

        if (isset($this->postedData['hasTagsAssociated']) && $this->postedData['hasTagsAssociated'] != '') {
            if ($this->postedData['hasTagsAssociated'] == applicationConstants::YES) {
                $srch->addCondition('mt.meta_id', 'is not', 'mysql_func_NULL', 'AND', true);
            } elseif ($this->postedData['hasTagsAssociated'] == applicationConstants::NO) {
                $srch->addCondition('mt.meta_id', 'is', 'mysql_func_NULL', 'AND', true);
            }
        }

        $srch->addMultipleFields(array('meta_id', 'IFNULL(mt.meta_record_id, sp.selprod_id) as meta_record_id', 'meta_title', 'selprod_id', 'CONCAT(IF(selprod_title is NULL or selprod_title = "" ,product_name, selprod_title), " - ", u.user_name) as selprod_title', 'selprod_user_id'));
        return $srch;
    }

    private function renderTemplateForShopDetail(): SearchBase
    {
        $srch = Shop::getSearchObject(false, $this->siteLangId);
        $srch->joinTable('tbl_users', 'INNER JOIN', 'u.user_id = s.shop_user_id', 'u');
        $srch->joinTable('tbl_user_credentials', 'INNER JOIN', 'u.user_id = c.credential_user_id', 'c');
        $srch->joinTable(MetaTag::DB_TBL, 'LEFT OUTER JOIN', "mt.meta_record_id = s.shop_id and mt.meta_controller = '" . $this->controller . "' and mt.meta_action = '" . $this->action . "' ", 'mt');
        $srch->joinTable(MetaTag::DB_TBL_LANG, 'LEFT OUTER JOIN', "mt_l.metalang_meta_id = mt.meta_id AND mt_l.metalang_lang_id = " . $this->siteLangId, 'mt_l');

        if (!empty($this->postedData['keyword'])) {
            $condition = $srch->addCondition('mt_l.meta_title', 'like', '%' . $this->postedData['keyword'] . '%');
            $condition->attachCondition('s_l.shop_name', 'like', '%' . $this->postedData['keyword'] . '%', 'OR');
        }

        if (isset($this->postedData['hasTagsAssociated']) && $this->postedData['hasTagsAssociated'] != '') {
            if ($this->postedData['hasTagsAssociated'] == applicationConstants::YES) {
                $srch->addCondition('mt.meta_id', 'is not', 'mysql_func_NULL', 'AND', true);
            } elseif ($this->postedData['hasTagsAssociated'] == applicationConstants::NO) {
                $srch->addCondition('mt.meta_id', 'is', 'mysql_func_NULL', 'AND', true);
            }
        }

        $srch->addMultipleFields(array('meta_id', 'meta_title', 'shop_id', 'IFNULL(s_l.shop_name, s.shop_identifier) as shop_name', 'IFNULL(mt.meta_record_id, s.shop_id) as meta_record_id'));
        return $srch;
    }

    private function renderTemplateForAdvanced(): MetaTagSearch
    {
        $srch = new MetaTagSearch($this->siteLangId);
        $srch->addCondition('mt.meta_advanced', '=', 1);
        $srch->addMultipleFields(['mt.* , mt_l.meta_title']);
        if (!empty($this->postedData['keyword'])) {
            $srch->addCondition('mt_l.meta_title', 'like', '%' . $this->postedData['keyword'] . '%');
        }
        return $srch;
    }

    private function renderTemplateForCMSPage(): SearchBase
    {
        $srch = ContentPage::getSearchObject($this->siteLangId);
        $srch->joinTable(MetaTag::DB_TBL, 'LEFT OUTER JOIN', "mt.meta_record_id = p.cpage_id and mt.meta_controller = '" . $this->controller . "' and mt.meta_action = '" . $this->action . "' ", 'mt');
        $srch->joinTable(MetaTag::DB_TBL_LANG, 'LEFT OUTER JOIN', "mt_l.metalang_meta_id = mt.meta_id AND mt_l.metalang_lang_id = " . $this->siteLangId, 'mt_l');

        if (!empty($this->postedData['keyword'])) {
            $condition = $srch->addCondition('mt_l.meta_title', 'like', '%' . $this->postedData['keyword'] . '%');
            $condition->attachCondition('p_l.cpage_title', 'like', '%' . $this->postedData['keyword'] . '%', 'OR');
        }

        if (isset($this->postedData['hasTagsAssociated']) && $this->postedData['hasTagsAssociated'] != '') {
            if ($this->postedData['hasTagsAssociated'] == applicationConstants::YES) {
                $srch->addCondition('mt.meta_id', 'is not', 'mysql_func_NULL', 'AND', true);
            } elseif ($this->postedData['hasTagsAssociated'] == applicationConstants::NO) {
                $srch->addCondition('mt.meta_id', 'is', 'mysql_func_NULL', 'AND', true);
            }
        }

        $srch->addMultipleFields(array('meta_id', 'meta_title', 'cpage_id', 'IF(cpage_title is NULL or cpage_title = "" ,cpage_identifier, cpage_title) as cpage_title', 'IFNULL(mt.meta_record_id, p.cpage_id) as meta_record_id'));
        return $srch;
    }

    private function renderTemplateForBrandDetail(): SearchBase
    {
        $srch = Brand::getSearchObject($this->siteLangId);
        $srch->joinTable(MetaTag::DB_TBL, 'LEFT OUTER JOIN', "mt.meta_record_id = b.brand_id and mt.meta_controller = '" . $this->controller . "' and mt.meta_action = '" . $this->action . "' ", 'mt');
        $srch->joinTable(MetaTag::DB_TBL_LANG, 'LEFT OUTER JOIN', "mt_l.metalang_meta_id = mt.meta_id AND mt_l.metalang_lang_id = " . $this->siteLangId, 'mt_l');

        if (!empty($this->postedData['keyword'])) {
            $condition = $srch->addCondition('mt_l.meta_title', 'like', '%' . $this->postedData['keyword'] . '%');
            $condition->attachCondition('b_l.brand_name', 'like', '%' . $this->postedData['keyword'] . '%', 'OR');
        }

        if (isset($this->postedData['hasTagsAssociated']) && $this->postedData['hasTagsAssociated'] != '') {
            if ($this->postedData['hasTagsAssociated'] == applicationConstants::YES) {
                $srch->addCondition('mt.meta_id', 'is not', 'mysql_func_NULL', 'AND', true);
            } elseif ($this->postedData['hasTagsAssociated'] == applicationConstants::NO) {
                $srch->addCondition('mt.meta_id', 'is', 'mysql_func_NULL', 'AND', true);
            }
        }

        $srch->addMultipleFields(array('meta_id', 'meta_title', 'brand_id', 'IF(brand_name is NULL or brand_name = "" ,brand_identifier, brand_name) as brand_name', 'IFNULL(mt.meta_record_id, b.brand_id) as meta_record_id'));
        $srch->addCondition('brand_status', '=', Brand::BRAND_REQUEST_APPROVED);
        return $srch;
    }

    private function renderTemplateForCategoryDetail(): SearchBase
    {
        $srch = ProductCategory::getSearchObject(false, $this->siteLangId, false);

        $srch->joinTable(MetaTag::DB_TBL, 'LEFT OUTER JOIN', "mt.meta_record_id = m.prodcat_id and mt.meta_controller = '" . $this->controller . "' and mt.meta_action = '" . $this->action . "' ", 'mt');
        $srch->joinTable(MetaTag::DB_TBL_LANG, 'LEFT OUTER JOIN', "mt_l.metalang_meta_id = mt.meta_id AND mt_l.metalang_lang_id = " . $this->siteLangId, 'mt_l');

        if (!empty($this->postedData['keyword'])) {
            $condition = $srch->addCondition('mt_l.meta_title', 'like', '%' . $this->postedData['keyword'] . '%');
            $condition->attachCondition('pc_l.prodcat_name', 'like', '%' . $this->postedData['keyword'] . '%', 'OR');
        }

        if (isset($this->postedData['hasTagsAssociated']) && $this->postedData['hasTagsAssociated'] != '') {
            if ($this->postedData['hasTagsAssociated'] == applicationConstants::YES) {
                $srch->addCondition('mt.meta_id', 'is not', 'mysql_func_NULL', 'AND', true);
            } elseif ($this->postedData['hasTagsAssociated'] == applicationConstants::NO) {
                $srch->addCondition('mt.meta_id', 'is', 'mysql_func_NULL', 'AND', true);
            }
        }

        $srch->addMultipleFields(array('meta_id', 'meta_title', 'prodcat_id', 'IF(prodcat_name is NULL or prodcat_name = "" ,prodcat_identifier, prodcat_name) as prodcat_name', 'IFNULL(mt.meta_record_id, m.prodcat_id) as meta_record_id'));
        $srch->addCondition('prodcat_deleted', '=', applicationConstants::NO);
        return $srch;
    }

    private function renderTemplateForBlogCategory(): SearchBase
    {
        $srch = BlogPostCategory::getSearchObject(false, $this->siteLangId, false);

        $srch->joinTable(MetaTag::DB_TBL, 'LEFT OUTER JOIN', "mt.meta_record_id = bpc.bpcategory_id and mt.meta_controller = '" . $this->controller . "' and mt.meta_action = '" . $this->action . "' ", 'mt');
        $srch->joinTable(MetaTag::DB_TBL_LANG, 'LEFT OUTER JOIN', "mt_l.metalang_meta_id = mt.meta_id AND mt_l.metalang_lang_id = " . $this->siteLangId, 'mt_l');

        if (!empty($this->postedData['keyword'])) {
            $condition = $srch->addCondition('mt_l.meta_title', 'like', '%' . $this->postedData['keyword'] . '%');
            $condition->attachCondition('bpc_l.bpcategory_name', 'like', '%' . $this->postedData['keyword'] . '%', 'OR');
            $condition->attachCondition('bpc.bpcategory_identifier', 'like', '%' . $this->postedData['keyword'] . '%', 'OR');
        }

        if (isset($this->postedData['hasTagsAssociated']) && $this->postedData['hasTagsAssociated'] != '') {
            if ($this->postedData['hasTagsAssociated'] == applicationConstants::YES) {
                $srch->addCondition('mt.meta_id', 'is not', 'mysql_func_NULL', 'AND', true);
            } elseif ($this->postedData['hasTagsAssociated'] == applicationConstants::NO) {
                $srch->addCondition('mt.meta_id', 'is', 'mysql_func_NULL', 'AND', true);
            }
        }

        $srch->addMultipleFields(array('meta_id', 'meta_title', 'bpcategory_id', 'IF(bpcategory_name is NULL or bpcategory_name = "" ,bpcategory_identifier,bpcategory_name) as bpcategory_name', 'IFNULL(mt.meta_record_id, bpc.bpcategory_id) as meta_record_id'));
        $srch->addCondition('bpcategory_deleted', '=', applicationConstants::NO);
        return $srch;
    }

    private function renderTemplateForBlogPost(): SearchBase
    {
        $srch = BlogPost::getSearchObject($this->siteLangId, true, true);

        $srch->joinTable(MetaTag::DB_TBL, 'LEFT OUTER JOIN', "mt.meta_record_id = bp.post_id and mt.meta_controller = '" . $this->controller . "' and mt.meta_action = '" . $this->action . "' ", 'mt');
        $srch->joinTable(MetaTag::DB_TBL_LANG, 'LEFT OUTER JOIN', "mt_l.metalang_meta_id = mt.meta_id AND mt_l.metalang_lang_id = " . $this->siteLangId, 'mt_l');
        $srch->addCondition('post_deleted', '=', applicationConstants::NO);
        if (!empty($this->postedData['keyword'])) {
            $condition = $srch->addCondition('mt_l.meta_title', 'like', '%' . $this->postedData['keyword'] . '%');
            $condition->attachCondition('bp_l.post_title', 'like', '%' . $this->postedData['keyword'] . '%', 'OR');
            $condition->attachCondition('bp.post_identifier', 'like', '%' . $this->postedData['keyword'] . '%', 'OR');
        }

        if (isset($this->postedData['hasTagsAssociated']) && $this->postedData['hasTagsAssociated'] != '') {
            if ($this->postedData['hasTagsAssociated'] == applicationConstants::YES) {
                $srch->addCondition('mt.meta_id', 'is not', 'mysql_func_NULL', 'AND', true);
            } elseif ($this->postedData['hasTagsAssociated'] == applicationConstants::NO) {
                $srch->addCondition('mt.meta_id', 'is', 'mysql_func_NULL', 'AND', true);
            }
        }

        $srch->addMultipleFields(array('meta_id', 'meta_title', 'post_id', 'IF(post_title is NULL or post_title = "" ,post_identifier, post_title) as post_title', 'IFNULL(mt.meta_record_id, bp.post_id) as meta_record_id'));
        $srch->addGroupBy('post_id');
        return $srch;
    }

    private function renderTemplateForMetaType(): MetaTagSearch
    {
        $srch = new MetaTagSearch($this->siteLangId);
        $srch->addCondition('mt.meta_controller', 'like', $this->controller);
        $srch->addCondition('mt.meta_action', 'like', $this->action);

        $srch->addMultipleFields(['mt.* , mt_l.meta_title']);
        return $srch;
    }

    private function getSearchFormForMetaType($metaType, $fields = []): Form
    {
        $frm = new Form('frmRecordSearch');
        $frm->addHiddenField(Labels::getLabel('FRM_TYPE', $this->siteLangId), 'metaType', $metaType);
        $frm->addHiddenField('', 'page');
        if (!empty($fields)) {
            $this->addSortingElements($frm, 'meta_title');
        }
        $frm->addHiddenField('', 'total_record_count');
        HtmlHelper::addSearchButton($frm);
        return $frm;
    }

    private function getAdvancedSearchForm($metaType, $fields = []): Form
    {
        $frm = new Form('frmRecordSearch');
        $frm->addHiddenField(Labels::getLabel('FRM_TYPE', $this->siteLangId), 'metaType', $metaType);
        $frm->addHiddenField('', 'page');
        if (!empty($fields)) {
            $this->addSortingElements($frm, 'meta_title');
        }
        $fld = $frm->addTextBox(Labels::getLabel('FRM_KEYWORD', $this->siteLangId), 'keyword');
        $fld->overrideFldType('search');
        $frm->addHiddenField('', 'total_record_count');
        HtmlHelper::addSearchButton($frm);
        HtmlHelper::addClearButton($frm);
        return $frm;
    }

    private function getListingSearchForm($metaType, $fields = []): Form
    {
        $frm = new Form('frmRecordSearch');
        $frm->addHiddenField(Labels::getLabel('FRM_TYPE', $this->siteLangId), 'metaType', $metaType);
        $frm->addHiddenField('', 'page');
        if (!empty($fields)) {
            $this->addSortingElements($frm, 'meta_title');
        }
        $fld = $frm->addTextBox(Labels::getLabel('FRM_KEYWORD', $this->siteLangId), 'keyword');
        $fld->overrideFldType('search');
        $frm->addSelectBox(Labels::getLabel('FRM_HAS_TAGS_ASSOCIATED', $this->siteLangId), 'hasTagsAssociated', applicationConstants::getYesNoArr($this->siteLangId), false, array(), Labels::getLabel('FRM_HAS_TAGS_ASSOCIATED', $this->siteLangId));

        HtmlHelper::addSearchButton($frm);
        $frm->addHiddenField('', 'total_record_count');
        $clearBtnHtm = HtmlHelper::addButtonHtml(Labels::getLabel('FRM_CLEAR', CommonHelper::getLangId()), 'button', 'btn_clear', 'btn btn-link', 'clearSearch(false)');
        $frm->addHtml('', 'btn_clear', $clearBtnHtm);
        return $frm;
    }

    public function getMetaTagsSearchForm($metaType, $fields = []): Form
    {
        switch ($metaType) {
            case MetaTag::META_GROUP_PRODUCT_DETAIL:
            case MetaTag::META_GROUP_SHOP_DETAIL:
            case MetaTag::META_GROUP_CMS_PAGE:
            case MetaTag::META_GROUP_BRAND_DETAIL:
            case MetaTag::META_GROUP_CATEGORY_DETAIL:
            case MetaTag::META_GROUP_BLOG_POST:
            case MetaTag::META_GROUP_BLOG_CATEGORY:
                return $this->getListingSearchForm($metaType, $fields);
                break;
            case MetaTag::META_GROUP_ADVANCED:
                return $this->getAdvancedSearchForm($metaType, $fields);
                break;
            default:
                return $this->getSearchFormForMetaType($metaType, $fields);
                break;
        }
    }

    public function form($metaId = 0, $metaType = 'default', $metaTagRecordId = 0)
    {
        if (0 < $metaId) {
            $data = MetaTag::getAttributesById($metaId);

            if ($data === false) {
                LibHelper::exitWithError($this->str_invalid_request, true);
            }
            if (empty($metaType)) {
                $tabsArr = MetaTag::getTabsArr();
                foreach ($tabsArr as $key => $value) {
                    if ($value['controller'] == $data['meta_controller'] && $value['action'] == $data['meta_action']) {
                        $metaType = $key;
                        break;
                    }
                }
            }
        }
        $frm = $this->getForm($metaId, $metaType, $metaTagRecordId);

        if (0 < $metaId) {
            $frm->fill($data);
        }

        $this->set('frm', $frm);
        $this->set('metaTagRecordId', $metaTagRecordId);
        $this->set('metaId', $metaId);
        $this->set('metaType', $metaType);
        $this->set('languages', Language::getAllNames());
        $this->set('sellerInventorySelprodId', 0);
        $this->set('html', $this->_template->render(false, false, NULL, true));
        $this->_template->render(false, false, 'json-success.php', true, false);
    }

    public function langForm($metaId = 0, $langId = 0, $metaType = 'default', $metaTagRecordId = 0, $autoFillLangData = 0)
    {
        $metaId = FatUtility::int($metaId);
        $langId = FatUtility::int($langId);

        if ($langId == 0) {
            LibHelper::exitWithError($this->str_invalid_request_id, true);
        }

        $sellerInvContext = FatApp::getPostedData('sellerInvContext', FatUtility::VAR_INT, 0);
        $sellerInventorySelprodId = 0;
        if (
            0 < $sellerInvContext
            && $metaType === MetaTag::META_GROUP_PRODUCT_DETAIL
            && 0 < FatUtility::int($metaTagRecordId)
        ) {
            $sellerInventorySelprodId = FatUtility::int($metaTagRecordId);
        }
        $this->set('sellerInventorySelprodId', $sellerInventorySelprodId);

        $langFrm = $this->getLangForm($metaId, $langId, $metaType, $metaTagRecordId);

        if (0 < $sellerInventorySelprodId) {
            $langFrm->addHiddenField('', 'seller_inv_context', 1);
        }

        if (0 < $autoFillLangData) {
            $updateLangDataobj = new TranslateLangData(MetaTag::DB_TBL_LANG);
            $translatedData = $updateLangDataobj->getTranslatedData($metaId, $langId, CommonHelper::getDefaultFormLangId());
            if (false === $translatedData) {
                LibHelper::exitWithError($updateLangDataobj->getError(), true);
            }
            $langData = current($translatedData);
        } else {
            $langData = MetaTag::getAttributesByLangId($langId, $metaId);
        }

        if ($langData) {
            $langFrm->fill($langData);
        }
        $this->set('languages', Language::getAllNames());
        $this->set('metaId', $metaId);
        $this->set('metaTagRecordId', $metaTagRecordId);
        $this->set('metaType', $metaType);
        $this->set('lang_id', $langId);
        $this->set('langFrm', $langFrm);
        $this->set('formLayout', Language::getLayoutDirection($langId));
        $this->set('html', $this->_template->render(false, false, NULL, true));
        $this->_template->render(false, false, 'json-success.php', true, false);
    }

    public function setup()
    {
        $this->objPrivilege->canEditMetaTags();

        $post = FatApp::getPostedData();
        $metaId = FatUtility::int($post['meta_id']);

        $tabsArr = MetaTag::getTabsArr();
        $metaType = FatUtility::convertToType($post['meta_type'], FatUtility::VAR_STRING);

        if ($metaType == '' || !isset($tabsArr[$metaType])) {
            LibHelper::exitWithError($this->str_invalid_access, true);
        }
        $frm = $this->getForm($metaId, $metaType, $post['meta_record_id']);
        $post = $frm->getFormDataFromArray(FatApp::getPostedData());

        if ($metaType == MetaTag::META_GROUP_ADVANCED) {
            $post['meta_advanced'] = 1;
        } elseif ($metaType == MetaTag::META_GROUP_DEFAULT) {
            $post['meta_default'] = 1;
        } else {
            $post['meta_controller'] = $tabsArr[$metaType]['controller'];
            $post['meta_action'] = $tabsArr[$metaType]['action'];
            if ($metaId == 0) {
                $post['meta_subrecord_id'] = 0;
            }
        }

        $record = new MetaTag($metaId);
        $record->assignValues($post);

        if (!$record->save()) {

            LibHelper::exitWithError($record->getError(), true);
        }

        CacheHelper::clear(CacheHelper::TYPE_META_TAGS);

        $newTabLangId = 0;
        if ($metaId > 0) {
            $languages = Language::getAllNames();
            foreach ($languages as $langId => $langName) {
                if (!$row = MetaTag::getAttributesByLangId($langId, $metaId)) {
                    $newTabLangId = $langId;
                    break;
                }
            }
        } else {
            $metaId = $record->getMainTableRecordId();
            $newTabLangId = FatApp::getConfig('CONF_ADMIN_DEFAULT_LANG', FatUtility::VAR_INT, 1);
        }

        $this->set('msg', $this->str_setup_successful);
        $this->set('metaId', $metaId);
        $this->set('metaTagRecordId', $post['meta_record_id']);
        $this->set('metaType', $metaType);
        $this->set('langId', $newTabLangId);
        $this->_template->render(false, false, 'json-success.php');
    }

    public function langSetup()
    {
        $this->objPrivilege->canEditMetaTags();
        $post = FatApp::getPostedData();

        $metaId = $post['meta_id'];

        $languages = Language::getAllNames();
        if (count($languages) > 1) {
            $langId = $post['lang_id'];
        } else {
            $langId = array_key_first($languages);
            $post['lang_id'] = $langId;
        }


        if ($langId == 0) {
            LibHelper::exitWithError($this->str_invalid_request_id, true);
        }
        $metaType = isset($post['meta_type']) ? $post['meta_type'] : MetaTag::META_GROUP_ADVANCED;
        if ($metaType != MetaTag::META_GROUP_ADVANCED) {
            $tabsArr = MetaTag::getTabsArr();
            if ($metaType == '' || !isset($tabsArr[$metaType])) {
                LibHelper::exitWithError($this->str_invalid_request_id, true);
            }

            if ($metaType == MetaTag::META_GROUP_DEFAULT) {
                $post['meta_default'] = 1;
            } else {
                $post['meta_controller'] = $tabsArr[$metaType]['controller'];
                $post['meta_action'] = $tabsArr[$metaType]['action'];
                if ($metaId == 0) {
                    $post['meta_subrecord_id'] = 0;
                }
            }

            $record = new MetaTag($metaId);

            $record->assignValues($post);

            if (!$record->save()) {
                LibHelper::exitWithError($record->getError(), true);
            }
            $metaId = $record->getMainTableRecordId();
        }

        if ($metaId == 0) {
            LibHelper::exitWithError($this->str_invalid_request_id, true);
        }

        if (!$post['meta_other_meta_tags'] == '' && $post['meta_other_meta_tags'] == strip_tags($post['meta_other_meta_tags'])) {
            LibHelper::exitWithError(Labels::getLabel('ERR_INVALID_OTHER_META_TAG', $this->siteLangId), true);
        }
        $metaRecordId = isset($post['meta_record_id']) ? $post['meta_record_id'] : 0;
        $frm = $this->getLangForm($metaId, $langId, $metaType, $metaRecordId);
        $post = $frm->getFormDataFromArray(FatApp::getPostedData());

        $data = array(
            'metalang_lang_id' => $langId,
            'metalang_meta_id' => $metaId,
            'meta_title' => $post['meta_title'],
            'meta_keywords' => $post['meta_keywords'],
            'meta_description' => $post['meta_description'],
            'meta_other_meta_tags' => $post['meta_other_meta_tags'],
        );

        $metaObj = new MetaTag($metaId);

        if (!$metaObj->updateLangData($langId, $data)) {
            LibHelper::exitWithError($metaObj->getError(), true);
        }

        $autoUpdateOtherLangsData = FatApp::getPostedData('auto_update_other_langs_data', FatUtility::VAR_INT, 0);
        if (0 < $autoUpdateOtherLangsData) {
            $updateLangDataobj = new TranslateLangData(MetaTag::DB_TBL_LANG);
            if (false === $updateLangDataobj->updateTranslatedData($metaId, CommonHelper::getDefaultFormLangId())) {
                LibHelper::exitWithError($updateLangDataobj->getError(), true);
            }
        }

        $newTabLangId = 0;
        $languages = Language::getAllNames();
        foreach ($languages as $langId => $langName) {
            if (!MetaTag::getAttributesByLangId($langId, $metaId)) {
                $newTabLangId = $langId;
                break;
            }
        }

        $this->set('msg', $this->str_setup_successful);
        $this->set('metaId', $metaId);
        $this->set('langId', $newTabLangId);
        $this->set('metaTagRecordId', FatUtility::int(FatApp::getPostedData('meta_record_id', FatUtility::VAR_INT, 0)));
        $this->set('sellerInvContext', FatApp::getPostedData('seller_inv_context', FatUtility::VAR_INT, 0));
        $this->_template->render(false, false, 'json-success.php');
    }

    public function deleteRecord()
    {
        $this->objPrivilege->canEditMetaTags();

        $metaId = FatApp::getPostedData('recordId', FatUtility::VAR_INT, 0);
        if ($metaId < 1) {
            LibHelper::exitWithError($this->str_invalid_request_id, true);
        }

        $obj = new MetaTag($metaId);
        if (!$obj->deleteRecord(true)) {
            LibHelper::exitWithError($obj->getError(), true);
        }

        FatUtility::dieJsonSuccess($this->str_delete_record);
    }

    private function getForm($metaTagId = 0, $metaType = 'default')
    {
        $metaTagId = FatUtility::int($metaTagId);
        $frm = new Form('frmMetaTag');
        $frm->addHiddenField('', 'meta_id', $metaTagId);
        $tabsArr = MetaTag::getTabsArr();
        $frm->addHiddenField('', 'meta_type', $metaType);

        if ($metaTagId != 0 && ($metaType == '' || !isset($tabsArr[$metaType]))) {
            LibHelper::exitWithError($this->str_invalid_request, true);
        }

        if ($metaType == MetaTag::META_GROUP_ADVANCED) {
            $fld = $frm->addTextBox(Labels::getLabel('FRM_URL', $this->siteLangId), 'url');
            $fld->htmlAfterField = "<small>" . Labels::getLabel("TXT_THIS_FIELD_CAN_BE_USED_TO_SEGREGATE_URL_INTO_DIFFERENT_PARTS", $this->siteLangId) . "</small>";

            $frm->addHtml('', 'or', '<div class="or"> <span>' . Labels::getLabel('FRM_OR', $this->siteLangId) . '</span></div>');

            $fld = $frm->addRequiredField(Labels::getLabel('FRM_CONTROLLER', $this->siteLangId), 'meta_controller');
            $fld->htmlAfterField = "<small>" . Labels::getLabel("TXT_EX:_IF_URL_IS", $this->siteLangId) . " http://domain-name.com/shops/report-spam/1/10 " . Labels::getLabel("LBL_then_controller_will_be_", $this->siteLangId) . " shops</small>";
            $fld = $frm->addRequiredField(Labels::getLabel('FRM_ACTION', $this->siteLangId), 'meta_action');
            $fld->htmlAfterField = "<small>" . Labels::getLabel("TXT_EX:_IF_URL_IS", $this->siteLangId) . " http://domain-name.com/shops/report-spam/1/10 " . Labels::getLabel("LBL_THEN_ACTION_WILL_BE_", $this->siteLangId) . " reportSpam</small>";
            $fld = $frm->addTextBox(Labels::getLabel('FRM_RECORD_ID', $this->siteLangId), 'meta_record_id');
            $fld->htmlAfterField = "<small>" . Labels::getLabel("TXT_EX:_IF_URL_IS", $this->siteLangId) . " http://domain-name.com/shops/report-spam/1/10 " . Labels::getLabel("LBL_then_record_id_will_be_", $this->siteLangId) . " 1</small>";
            $fld = $frm->addTextBox(Labels::getLabel('FRM_SUB_RECORD_ID', $this->siteLangId), 'meta_subrecord_id');
            $fld->htmlAfterField = "<small>" . Labels::getLabel("TXT_EX:_IF_URL_IS", $this->siteLangId) . " http://domain-name.com/shops/report-spam/1/10 " . Labels::getLabel("LBL_then_sub_record_id_will_be_", $this->siteLangId) . " 10</small>";
        }
        return $frm;
    }

    private function getLangForm($metaId = 0, $langId = 0, $metaType = 'Default', $metaTagRecordId = 0)
    {
        $frm = new Form('frmMetaTagLang');
        $frm->addHiddenField('', 'meta_id', $metaId);

        if ($metaType != MetaTag::META_GROUP_ADVANCED) {
            $tabsArr = MetaTag::getTabsArr();
            $frm->addHiddenField('', 'meta_type', $metaType);
            $frm->addHiddenField(Labels::getLabel('FRM_ENTITY_ID', $this->siteLangId), 'meta_record_id', $metaTagRecordId);
            if ($metaId != 0 && ($metaType == '' || !isset($tabsArr[$metaType]))) {
                LibHelper::exitWithError($this->str_invalid_request, true);
            }
        }

        $languages = Language::getAllNames();
        if (count($languages) > 1) {
            $frm->addSelectBox(Labels::getLabel('FRM_LANGUAGE', $this->siteLangId), 'lang_id', $languages, $langId, array(), '');
        } else {
            $lang_id = array_key_first($languages);
            $frm->addHiddenField('', 'lang_id', $lang_id);
        }

        $frm->addRequiredField(Labels::getLabel('FRM_META_TITLE', $this->siteLangId), 'meta_title');
        $frm->addTextarea(Labels::getLabel('FRM_META_KEYWORDS', $this->siteLangId), 'meta_keywords');
        $frm->addTextarea(Labels::getLabel('FRM_META_DESCRIPTION', $this->siteLangId), 'meta_description');
        $fld = $frm->addTextarea(Labels::getLabel('FRM_OTHER_META_TAGS', $this->siteLangId), 'meta_other_meta_tags');
        $fld->htmlAfterField = '<small>' . Labels::getLabel('TXT_FOR_EXAMPLE:', $this->siteLangId) . ' ' . htmlspecialchars('<meta name="copyright" content="text">') . '</small>';

        $translatorSubscriptionKey = FatApp::getConfig('CONF_TRANSLATOR_SUBSCRIPTION_KEY', FatUtility::VAR_STRING, '');
        if (!empty($translatorSubscriptionKey) && 1 < count($languages) && $langId == CommonHelper::getDefaultFormLangId()) {
            $frm->addCheckBox(Labels::getLabel('FRM_UPDATE_OTHER_LANGUAGES_DATA', $this->siteLangId), 'auto_update_other_langs_data', 1, array(), false, 0);
        }

        return $frm;
    }

    protected function getFormColumns(string $metaType): array
    {
        $metaTagsTblHeadingCols = CacheHelper::get('metaTagsTblHeadingCols' . $metaType . $this->siteLangId, CONF_DEF_CACHE_TIME, '.txt');
        if ($metaTagsTblHeadingCols) {
            return json_decode($metaTagsTblHeadingCols, true);
        }

        $arr = [
            /*  'listSerial' => Labels::getLabel('LBL_SR._NO', $this->siteLangId), */
            'meta_title' => Labels::getLabel('LBL_TITLE', $this->siteLangId)
        ];

        switch ($metaType) {
            case MetaTag::META_GROUP_PRODUCT_DETAIL:
                $arr['selprod_title'] = Labels::getLabel('LBL_PRODUCT_NAME', $this->siteLangId);
                break;
            case MetaTag::META_GROUP_SHOP_DETAIL:
                $arr['shop_name'] = Labels::getLabel('LBL_SHOP_NAME', $this->siteLangId);
                break;
            case MetaTag::META_GROUP_CMS_PAGE:
                $arr['cpage_title'] = Labels::getLabel('LBL_CMS_PAGE', $this->siteLangId);
                break;
            case MetaTag::META_GROUP_BRAND_DETAIL:
                $arr['brand_name'] = Labels::getLabel('LBL_BRAND_NAME', $this->siteLangId);
                break;
            case MetaTag::META_GROUP_CATEGORY_DETAIL:
                $arr['prodcat_name'] = Labels::getLabel('LBL_CATEGORY_NAME', $this->siteLangId);
                break;
            case MetaTag::META_GROUP_BLOG_CATEGORY:
                $arr['bpcategory_name'] = Labels::getLabel('LBL_CATEGORY_NAME', $this->siteLangId);
                break;
            case MetaTag::META_GROUP_BLOG_POST:
                $arr['post_title'] = Labels::getLabel('LBL_POST_TITLE', $this->siteLangId);
                break;
        }
        $arr['action'] = Labels::getLabel('LBL_ACTION_BUTTONS', $this->siteLangId);

        CacheHelper::create('metaTagsTblHeadingCols' . $metaType . $this->siteLangId, json_encode($arr), CacheHelper::TYPE_LABELS);
        return $arr;
    }

    /**
     * setCustomColumnWidth
     *
     * @return void
     */
    protected function setCustomColumnWidth(string $metaType): void
    {
        $arr = [
            'listSerial' => [
                'width' => '5%'
            ],
            'action' => [
                'width' => '5%'
            ],
        ];

        switch ($metaType) {
            case MetaTag::META_GROUP_PRODUCT_DETAIL:
                $arr['selprod_title'] = ['width' => '45%'];
                break;
            case MetaTag::META_GROUP_SHOP_DETAIL:
                $arr['shop_name'] = ['width' => '45%'];
                break;
            case MetaTag::META_GROUP_CMS_PAGE:
                $arr['cpage_title'] = ['width' => '45%'];
                break;
            case MetaTag::META_GROUP_BRAND_DETAIL:
                $arr['brand_name'] = ['width' => '45%'];
                break;
            case MetaTag::META_GROUP_CATEGORY_DETAIL:
                $arr['prodcat_name'] = ['width' => '45%'];
                break;
            case MetaTag::META_GROUP_BLOG_CATEGORY:
                $arr['bpcategory_name'] = ['width' => '45%'];
                break;
            case MetaTag::META_GROUP_BLOG_POST:
                $arr['post_title'] = ['width' => '45%'];
                break;
        }

        if (count($arr) == 3) {
            $arr['meta_title'] = ['width' => '45%'];
        } else {
            $arr['meta_title'] = ['width' => '90%'];
        }

        $this->set('tableHeadAttrArr', $arr);
    }

    protected function getDefaultColumns(): array
    {
        return [
            /*  'listSerial', */
            'meta_title',
            'bpcategory_name',
            'post_title',
            'brand_name',
            'prodcat_name',
            'cpage_title',
            'selprod_title',
            'shop_name',
            'action',
        ];
    }

    protected function excludeKeysForSort($fields = []): array
    {
        return array_diff($fields, Common::excludeKeysForSort());
    }

    public function getBreadcrumbNodes($action)
    {
        switch ($action) {
            case 'index':
                $pageData = PageLanguageData::getAttributesByKey($this->pageKey, $this->siteLangId);
                $pageTitle = $pageData['plang_title'] ?? LibHelper::getControllerName(true);
                $this->nodes = [
                    ['title' => Labels::getLabel('NAV_SEO', $this->siteLangId)],
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
