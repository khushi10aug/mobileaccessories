<?php

class ImageAttributesController extends ListingBaseController
{
    protected $pageKey = 'MANAGE_IMAGE_ATTRIBUTES';

    public function __construct($action)
    {
        parent::__construct($action);
        $this->objPrivilege->canViewImageAttributes();
    }

    public function index()
    {
        $fields = $this->getFormColumns();
        $frmSearch = $this->getSearchForm($fields);

        $pageData = PageLanguageData::getAttributesByKey($this->pageKey, $this->siteLangId);
        $pageTitle = $pageData['plang_title'] ?? LibHelper::getControllerName(true);

        $actionItemsData = HtmlHelper::getDefaultActionItems($fields);
        $actionItemsData['newRecordBtn'] = false;

        $this->set('pageData', $pageData);
        $this->set('pageTitle', $pageTitle);
        $this->set('actionItemsData', $actionItemsData);
        $this->set("frmSearch", $frmSearch);
        $this->set('defaultColumns', $this->getDefaultColumns());
        $this->set('keywordPlaceholder', Labels::getLabel('FRM_SEARCH_BY_NAME', $this->siteLangId));
        $this->getListingData();

        $this->_template->addJs(['image-attributes/page-js/index.js']);
        $this->_template->render(true, true, '_partial/listing/index.php');
    }

    public function search()
    {
        $this->getListingData();
        $jsonData = [
            'listingHtml' => $this->_template->render(false, false, 'image-attributes/search.php', true),
            'paginationHtml' => $this->_template->render(false, false, '_partial/listing/listing-foot.php', true)
        ];
        LibHelper::exitWithSuccess($jsonData, true);
    }

    public function getListingData()
    {
        $fields = $this->getFormColumns();
        $selectedFlds = FatApp::getPostedData('reportColumns', FatUtility::VAR_STRING, '');
        $selectedFlds = !empty($selectedFlds) ? json_decode($selectedFlds) +  $this->getDefaultColumns() : $this->getDefaultColumns();

        $fields =  FilterHelper::parseArrayByKeys($fields, $selectedFlds, true);
        $allowedKeysForSorting = $this->excludeKeysForSort(array_keys($fields));
        $sortBy = FatApp::getPostedData('sortBy', FatUtility::VAR_STRING, 'afile_id');
        if (!array_key_exists($sortBy, $fields)) {
            $sortBy = 'afile_id';
        }

        $sortOrder = applicationConstants::getSortOrder(FatApp::getPostedData('sortOrder', FatUtility::VAR_STRING), applicationConstants::SORT_DESC);
        $pageSize = applicationConstants::getPageSize(FatApp::getPostedData('pageSize', FatUtility::VAR_INT));

        $searchForm = $this->getSearchForm($fields);
        $page = FatApp::getPostedData('page', FatUtility::VAR_INT, 1);
        $page = ($page <= 0) ? 1 : $page;

        $post = $searchForm->getFormDataFromArray(FatApp::getPostedData());

        $srch = AttachedFile::getSearchObject();

        if (!empty($post['select_module'])) {
            $cnd = $srch->addCondition('afile_type', '=', $post['select_module']);
        } else {
            $cnd = $srch->addCondition('afile_type', '=', AttachedFile::FILETYPE_PRODUCT_IMAGE);
        }

        switch ($post['select_module']) {
            case AttachedFile::FILETYPE_PRODUCT_IMAGE:
                $srch->joinTable(Product::DB_TBL, 'INNER JOIN', 'product_id = afile_record_id', 'p');
                $srch->joinTable(Product::DB_TBL_LANG, 'LEFT OUTER JOIN', 'p.product_id = p_l.productlang_product_id AND p_l.productlang_lang_id = ' . $this->siteLangId, 'p_l');
                $srch->addCondition('p.product_deleted', '=', applicationConstants::NO);
                $srch->addMultipleFields(
                    array('product_id as record_id', 'IFNULL(product_name, product_identifier) as record_name', 'afile_type')
                );
                if (isset($post['keyword']) && '' != $post['keyword']) {
                    $cnd = $srch->addCondition('product_name', 'like', '%' . $post['keyword'] . '%');
                    $cnd->attachCondition('product_identifier', 'like', '%' . $post['keyword'] . '%');
                }
                break;
            case AttachedFile::FILETYPE_BRAND_LOGO:
            case AttachedFile::FILETYPE_BRAND_IMAGE:
                $srch->joinTable(Brand::DB_TBL, 'LEFT OUTER JOIN', 'brand_id = afile_record_id', 'b');
                $srch->joinTable(Brand::DB_TBL_LANG, 'LEFT OUTER JOIN', 'b.brand_id = b_l.brandlang_brand_id AND b_l.brandlang_lang_id = ' . $this->siteLangId, 'b_l');
                $srch->addMultipleFields(
                    array('brand_id as record_id', 'IFNULL(brand_name, brand_identifier) as record_name', 'afile_type')
                );
                if (isset($post['keyword']) && '' != $post['keyword']) {
                    $cnd = $srch->addCondition('brand_name', 'like', '%' . $post['keyword'] . '%');
                    $cnd->attachCondition('brand_identifier', 'like', '%' . $post['keyword'] . '%');
                }
                break;
            case AttachedFile::FILETYPE_CATEGORY_BANNER:
                $srch->joinTable(ProductCategory::DB_TBL, 'LEFT OUTER JOIN', 'prodcat_id = afile_record_id', 'pc');
                $srch->joinTable(ProductCategory::DB_TBL_LANG, 'LEFT OUTER JOIN', 'pc.prodcat_id = pc_l.prodcatlang_prodcat_id AND pc_l.prodcatlang_lang_id = ' . $this->siteLangId, 'pc_l');
                $srch->addMultipleFields(
                    array('prodcat_id as record_id', 'IFNULL(prodcat_name, prodcat_identifier) as record_name', 'afile_type')
                );
                if (isset($post['keyword']) && '' != $post['keyword']) {
                    $cnd = $srch->addCondition('prodcat_name', 'like', '%' . $post['keyword'] . '%');
                    $cnd->attachCondition('prodcat_identifier', 'like', '%' . $post['keyword'] . '%');
                }
                break;
            case AttachedFile::FILETYPE_BLOG_POST_IMAGE:
                $srch->joinTable(BlogPost::DB_TBL, 'LEFT OUTER JOIN', 'post_id = afile_record_id', 'bp');
                $srch->joinTable(BlogPost::DB_TBL_LANG, 'LEFT OUTER JOIN', 'bp.post_id = bp_l.postlang_post_id AND bp_l.postlang_lang_id = ' . $this->siteLangId, 'bp_l');
                $srch->addMultipleFields(
                    array('post_id as record_id', 'IFNULL(post_title, post_identifier) as record_name', 'afile_type')
                );
                if (isset($post['keyword']) && '' != $post['keyword']) {
                    $cnd = $srch->addCondition('post_title', 'like', '%' . $post['keyword'] . '%');
                    $cnd->attachCondition('post_identifier', 'like', '%' . $post['keyword'] . '%');
                }
                break;
            default:
                $srch->joinTable(Product::DB_TBL, 'INNER JOIN', 'product_id = afile_record_id', 'p');
                $srch->joinTable(Product::DB_TBL_LANG, 'LEFT OUTER JOIN', 'p.product_id = p_l.productlang_product_id AND p_l.productlang_lang_id = ' . $this->siteLangId, 'p_l');
                $srch->addCondition('p.product_deleted', '=', applicationConstants::NO);
                $srch->addMultipleFields(
                    array('product_id as record_id', 'IFNULL(product_name, product_identifier) as record_name', 'afile_type', 'afile_id')
                );
                if (isset($post['keyword']) && '' != $post['keyword']) {
                    $cnd = $srch->addCondition('product_name', 'like', '%' . $post['keyword'] . '%');
                    $cnd->attachCondition('product_identifier', 'like', '%' . $post['keyword'] . '%');
                }
                break;
        }

        $srch->addGroupBy('record_id');
        $this->setRecordCount(clone $srch, $pageSize, $page, $post, true);
        $srch->doNotCalculateRecords();
        $srch->addOrder($sortBy, $sortOrder);
        $srch->addHaving('record_id', 'is not', 'mysql_func_NULL', 'AND', true);
        $srch->setPageNumber($page);
        $srch->setPageSize($pageSize);
        $this->set("arrListing",  FatApp::getDb()->fetchAll($srch->getResultSet()));
        $this->set('moduleType', (isset($post['select_module'])) ? $post['select_module'] : AttachedFile::FILETYPE_PRODUCT_IMAGE);
        $this->set('postedData', $post);
        $this->set('sortBy', $sortBy);
        $this->set('sortOrder', $sortOrder);
        $this->set('fields', $fields);
        $this->set('allowedKeysForSorting', $allowedKeysForSorting);
        $this->set('canEdit', $this->objPrivilege->canEditImageAttributes($this->admin_id, true));
    }

    public function form($recordId, $moduleType, $langId = 0, $optionId = 0)
    {
        $recordId = FatUtility::int($recordId);
        $moduleType = FatUtility::int($moduleType);
        $langId = FatUtility::int($langId);
        $optionKey = $optionId;
        $optionId = ($moduleType == AttachedFile::FILETYPE_PRODUCT_IMAGE)
            ? Product::encodeImageOptionSubId($optionId)
            : FatUtility::int($optionId);

        if ($recordId < 1) {
            LibHelper::exitWithError($this->str_invalid_request, true);
        }

        switch ($moduleType) {
            case AttachedFile::FILETYPE_PRODUCT_IMAGE:
                $data = Product::getProductDataById($this->siteLangId, $recordId, 'IFNULL(product_name, product_identifier) as title');
                $title = $data['title'] ?? '';
                break;
            case AttachedFile::FILETYPE_CATEGORY_BANNER:
                $srch = ProductCategory::getSearchObject(false, $this->siteLangId);
                $srch->addOrder('m.prodcat_active', 'DESC');
                $srch->addCondition(ProductCategory::DB_TBL_PREFIX . 'deleted', '=', 0);
                $srch->addFld('IFNULL(prodcat_name, prodcat_identifier) AS prodcat_name');
                $srch->addCondition('prodcat_id', '=', $recordId);
                $srch->addOrder('prodcat_id', 'DESC');
                $srch->doNotCalculateRecords();
                $srch->setPageSize(1);
                $rs = $srch->getResultSet();
                $records = FatApp::getDb()->fetch($rs);
                $title = ($records) ? $records['prodcat_name'] : '';
                break;
            case AttachedFile::FILETYPE_BLOG_POST_IMAGE:
                $srch = BlogPost::getSearchObject($this->siteLangId);
                $srch->addFld('IFNULL(post_title, post_identifier) as post_title');
                $srch->addCondition('post_id', '=', $recordId);
                $srch->addOrder('post_id', 'DESC');
                $srch->doNotCalculateRecords();
                $srch->setPageSize(1);
                $rs = $srch->getResultSet();
                $records = FatApp::getDb()->fetch($rs);
                $title = ($records) ? $records['post_title'] : '';
                break;
            default:
                $srch = Brand::getListingObj($this->siteLangId, null, true);
                $srch->addCondition('brand_id', '=', $recordId);
                $srch->addOrder('brand_id', 'DESC');
                $srch->doNotCalculateRecords();
                $srch->setPageSize(1);
                $rs = $srch->getResultSet();
                $records = FatApp::getDb()->fetch($rs);
                $title = ($records) ? $records['brand_name'] : '';
                break;
        }
        $languages = Language::getAllNames();
        if (count($languages) <= 1) {
            $langId = array_key_first($languages);
        }

        $images = AttachedFile::getMultipleAttachments($moduleType, $recordId, $optionId, $langId, (count($languages) <= 1) ? true : false, 0, 0, true);
        $frm = $this->getForm($recordId, $moduleType, $langId, $images, $optionKey);
        $this->set('recordId', $recordId);
        $this->set('moduleType', $moduleType);
        $this->set('langId', $langId);
        $this->set('languages', $languages);
        $this->set('title', $title);
        $this->set('images', $images);
        $this->set('frm', $frm);
        $this->set('html', $this->_template->render(false, false, NULL, true));
        $this->_template->render(false, false, 'json-success.php', true, false);
    }

    private function getForm($recordId, $moduleType, $langId, $images, $optionId = 0)
    {
        $this->objPrivilege->canViewImageAttributes();
        $recordId = FatUtility::int($recordId);
        $moduleType = FatUtility::int($moduleType);
        $langId = FatUtility::int($langId);

        $frm = new Form('frmImgAttr');
        $frm->addHiddenField('', 'module_type', $moduleType);
        $frm->addHiddenField('', 'record_id', $recordId);

        if ($moduleType == AttachedFile::FILETYPE_PRODUCT_IMAGE) {
            $imgTypesArr = Product::getSeparateImageOptions($recordId, $this->siteLangId);
            $frm->addSelectBox(Labels::getLabel('FRM_IMAGE_FILE_TYPE', $this->siteLangId), 'option_id', $imgTypesArr, $optionId, array(), '');
        }

        $languages = Language::getAllNames();
        if (count($languages) > 1) {
            $frm->addSelectBox(Labels::getLabel('FRM_LANGUAGE', $this->siteLangId), 'lang_id', $languages, $langId);
        } else {
            $lang_id = array_key_first($languages);
            $frm->addHiddenField('', 'lang_id', $lang_id);
        }

        foreach ($images as $afileId => $afileData) {
            $frm->addTextBox(Labels::getLabel('FRM_IMAGE_TITLE', $this->siteLangId), 'image_title' . $afileId);
            $frm->addTextBox(Labels::getLabel('FRM_IMAGE_ALT', $this->siteLangId), 'image_alt' . $afileId);
        }
        return $frm;
    }

    public function setup()
    {
        $this->objPrivilege->canEditImageAttributes();

        $post = FatApp::getPostedData();
        $recordId = FatUtility::int($post['record_id']);
        $moduleType = FatUtility::int($post['module_type']);
        $langId = FatUtility::int($post['lang_id']);
        $optionKey = isset($post['option_id']) ? trim((string) $post['option_id']) : '0';
        $optionId = ($moduleType == AttachedFile::FILETYPE_PRODUCT_IMAGE)
            ? Product::encodeImageOptionSubId($optionKey)
            : FatUtility::int($optionKey);
        if (!$recordId || !$moduleType) {
            LibHelper::exitWithError($this->str_invalid_request_id, true);
        }
        $languages = Language::getAllNames();
        if (count($languages) <= 1) {
            $langId = array_key_first($languages);
        }

        $images = AttachedFile::getMultipleAttachments($moduleType, $recordId, $optionId, $langId, (count($languages) <= 1) ? true : false, 0, 0, true);

        $frm = $this->getForm($recordId, $moduleType, $langId, $images, $optionKey);
        $post = $frm->getFormDataFromArray(FatApp::getPostedData());
        if (false === $post) {
            LibHelper::exitWithError(current($frm->getValidationErrors()), true);
        }


        $db = FatApp::getDb();
        foreach ($images as $afileId => $afileData) {
            $where = array('smt' => 'afile_record_id = ? and afile_id = ?', 'vals' => array($recordId, $afileId));
            if (!$db->updateFromArray(AttachedFile::DB_TBL, array('afile_attribute_title' => $post['image_title' . $afileId], 'afile_attribute_alt' => $post['image_alt' . $afileId]), $where)) {
                LibHelper::exitWithError($db->getError(), true);
            }
        }
        $this->set('msg', $this->str_setup_successful);
        $this->set('recordId', $recordId);
        $this->_template->render(false, false, 'json-success.php');
    }

    public function getSearchForm($fields = [])
    {
        $frm = new Form('frmRecordSearch');
        $fld = $frm->addTextBox(Labels::getLabel('FRM_KEYWORD', $this->siteLangId), 'keyword');
        $fld->overrideFldType('search');

        $attachedFile = new AttachedFile();
        $attachementArr = $attachedFile->getImgAttrTypeArray($this->siteLangId);
        $frm->addSelectBox(Labels::getLabel('FRM_SELECT_TYPE', $this->siteLangId), 'select_module', $attachementArr, AttachedFile::FILETYPE_PRODUCT_IMAGE, [], '');

        if (!empty($fields)) {
            $this->addSortingElements($frm, 'afile_id', applicationConstants::SORT_DESC);
        }
        $frm->addHiddenField('', 'total_record_count');
        HtmlHelper::addSearchButton($frm);
        HtmlHelper::addClearButton($frm);
        return $frm;
    }

    protected function getFormColumns()
    {
        $imgAttrCacheVarData = CacheHelper::get('imgAttrCacheVarData' . $this->siteLangId, CONF_DEF_CACHE_TIME, '.txt');
        if ($imgAttrCacheVarData) {
            return json_decode($imgAttrCacheVarData, true);
        }

        $arr = [
            'listSerial' => Labels::getLabel('LBL_SR._NO', $this->siteLangId),
            'record_name' => Labels::getLabel('LBL_NAME', $this->siteLangId),
            'action' => Labels::getLabel('LBL_ACTION_BUTTONS', $this->siteLangId),
        ];
        CacheHelper::create('imgAttrCacheVarData' . $this->siteLangId, json_encode($arr), CacheHelper::TYPE_LABELS);
        return $arr;
    }

    protected function getDefaultColumns(): array
    {
        return [
            'listSerial',
            'record_name',
            'action'
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
