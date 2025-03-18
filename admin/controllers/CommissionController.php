<?php

class CommissionController extends ListingBaseController
{
    protected $pageKey = 'MANAGE_COMMISSIONS';

    public function __construct($action)
    {
        parent::__construct($action);
        $this->objPrivilege->canViewCommissionSettings();
    }

    public function index()
    {
        $fields = $this->getFormColumns();
        $frmSearch = $this->getSearchForm($fields);

        $pageData = PageLanguageData::getAttributesByKey($this->pageKey, $this->siteLangId);
        $pageTitle = $pageData['plang_title'] ?? LibHelper::getControllerName(true);

        $actionItemsData = HtmlHelper::getDefaultActionItems($fields);
        $actionItemsData['deleteButton'] = true;
        $actionItemsData['formAction'] = 'deleteSelected';
        $actionItemsData['performBulkAction'] = true;
        $actionItemsData['headerHtmlContent'] = '<a href="'.UrlHelper::generateUrl('configurations','index', [Configurations::FORM_COMMISSION]).'" class="btn btn-icon btn-outline-gray ms-2" title="" data-bs-toggle="tooltip" data-placement="top" data-bs-original-title="'.Labels::getLabel('FRM_WEBSITE_COMMISSION_SETTINGS', $this->siteLangId).'">
            <svg class="svg btn-icon-start" width="18" height="18">
                <use xlink:href="'.CONF_WEBROOT_URL.'images/retina/sprite-actions.svg' . AttachedFile::setTimeParam(RELEASE_DATE) . '#gear">
                </use>
            </svg>                                
        </a>';  

        $this->set('pageData', $pageData);
        $this->set('pageTitle', $pageTitle);
        $this->set('actionItemsData', $actionItemsData);
        $this->set("frmSearch", $frmSearch);
        $this->set('defaultColumns', $this->getDefaultColumns());
        $this->set('keywordPlaceholder', Labels::getLabel('FRM_SEARCH_BY_CATEGORY,_SELLER_AND_PRODUCT', $this->siteLangId));
        $this->getListingData();

        $this->_template->addJs(['js/select2.js', 'commission/page-js/index.js']);
        $this->_template->addCss(array('css/select2.min.css'));
        $this->_template->render(true, true, '_partial/listing/index.php', false, false);
    }

    public function search()
    {
        $this->getListingData();
        $jsonData = [
            'listingHtml' => $this->_template->render(false, false, 'commission/search.php', true),
            'paginationHtml' => $this->_template->render(false, false, '_partial/listing/listing-foot.php', true)
        ];
        LibHelper::exitWithSuccess($jsonData, true);
    }

    private function getListingData()
    {
        $db = FatApp::getDb();
        $post = FatApp::getPostedData();

        $fields = $this->getFormColumns();
        $selectedFlds = FatApp::getPostedData('reportColumns', FatUtility::VAR_STRING, '');
        $selectedFlds = !empty($selectedFlds) ? json_decode($selectedFlds) +  $this->getDefaultColumns() : $this->getDefaultColumns();
        $fields =  FilterHelper::parseArrayByKeys($fields, $selectedFlds, true);

        $allowedKeysForSorting = $this->excludeKeysForSort(array_keys($fields));
        $sortBy = FatApp::getPostedData('sortBy', FatUtility::VAR_STRING, current($allowedKeysForSorting));
        if (!array_key_exists($sortBy, $fields)) {
            $sortBy = current($allowedKeysForSorting);
        }

        $sortOrder = applicationConstants::getSortOrder(FatApp::getPostedData('sortOrder', FatUtility::VAR_STRING));

        $srchFrm = $this->getSearchForm($fields);

        $postedData = FatApp::getPostedData();
        $post = $srchFrm->getFormDataFromArray($postedData);
        $page = FatApp::getPostedData('page', FatUtility::VAR_INT, 1);
        $page = ($page <= 0) ? 1 : $page;

        $pageSize = applicationConstants::getPageSize(FatApp::getPostedData('pageSize', FatUtility::VAR_INT));

        $attr = array(
            'tcs.*',
            'IFNULL(tp_l.product_name,tp.product_identifier)as product_name',
            'IFNULL(tpc_l.prodcat_name,tpc.prodcat_identifier)as prodcat_name',
            'CONCAT(COALESCE(s_l.shop_name, shop.shop_identifier), " ( ", tuc.credential_username, " )") as vendor'
        );
        $srch = Commission::getCommissionSettingsObj($this->siteLangId, 0, $attr);
        $srch->joinTable(Shop::DB_TBL, 'LEFT OUTER JOIN', 'shop_user_id = if(tu.user_parent > 0, user_parent, tu.user_id)', 'shop');
        $srch->joinTable(Shop::DB_TBL_LANG, 'LEFT OUTER JOIN', 'shop.shop_id = s_l.shoplang_shop_id AND shoplang_lang_id = ' . $this->siteLangId, 's_l');

        if (isset($post['keyword']) && '' != $post['keyword']) {
            $cond = $srch->addCondition('prodcat_identifier', 'like', '%' . $post['keyword'] . '%', 'AND');
            $cond->attachCondition('tuc.credential_username', 'like', '%' . $post['keyword'] . '%', 'OR');
            $cond->attachCondition('product_identifier', 'like', '%' . $post['keyword'] . '%', 'OR');
            $cond->attachCondition('product_name', 'like', '%' . $post['keyword'] . '%', 'OR');
            $cond->attachCondition('shop_identifier', 'like', '%' . $post['keyword'] . '%', 'OR');
            $cond->attachCondition('shop_name', 'like', '%' . $post['keyword'] . '%', 'OR');
        }

        if (!array_key_exists($sortOrder, applicationConstants::sortOrder($this->siteLangId))) {
            $sortOrder = applicationConstants::SORT_ASC;
        }

        $srch->addOrder($sortBy, $sortOrder);

        $srch->setPageNumber($page);
        $srch->setPageSize($pageSize);
        $rs = $srch->getResultSet();
        $arrListing = $db->fetchAll($rs);

        $this->set("arrListing", $arrListing);
        $this->set('pageCount', $srch->pages());
        $this->set('recordCount', $srch->recordCount());
        $this->set('page', $page);
        $this->set('pageSize', $pageSize);

        $paginationArr = empty($postedData) ? $post : $postedData;
        $this->set('postedData', $paginationArr);

        $this->set('sortBy', $sortBy);
        $this->set('sortOrder', $sortOrder);
        $this->set('fields', $fields);
        $this->set('allowedKeysForSorting', $allowedKeysForSorting);
        $this->set('canEdit', $this->objPrivilege->canEditCommissionSettings($this->admin_id, true));
    }

    public function form()
    {
        $this->objPrivilege->canEditCommissionSettings();

        $recordId = FatApp::getPostedData('recordId', FatUtility::VAR_INT, 0);

        $data = $userArr = $prodArr = $catArr = [];
        if (0 < $recordId) {
            $data = Commission::getAttributesById(
                $recordId,
                array('commsetting_id', 'commsetting_product_id', 'commsetting_user_id', 'commsetting_prodcat_id', 'commsetting_fees')
            );
            if ($data === false) {
                LibHelper::exitWithError($this->str_invalid_request, true);
            }

            if ($data['commsetting_user_id'] > 0) {
                $userObj = new User($data['commsetting_user_id']);
                $res = $userObj->getUserInfo();
                $userArr[$data['commsetting_user_id']] = isset($res['credential_username']) ? $res['credential_username'] : '';
            }

            if ($data['commsetting_product_id'] > 0) {
                $prodObj = Product::getSearchObject($this->siteLangId);
                $prodObj->addCondition('product_id', '=', 'mysql_func_' . $data['commsetting_product_id'], 'AND', true);
                $prodObj->addMultipleFields(array('IFNULL(product_name,product_identifier) as product_name'));
                $prodObj->doNotCalculateRecords();
                $prodObj->setPageSize(1);
                $row = FatApp::getDb()->fetch($prodObj->getResultSet());
                $prodArr[$data['commsetting_product_id']] = isset($row['product_name']) ? $row['product_name'] : '';
            }

            if ($data['commsetting_prodcat_id'] > 0) {
                $prodCat = new ProductCategory();
                $selectedCatName = $prodCat->getParentTreeStructure($data['commsetting_prodcat_id'], 0, '', $this->siteLangId);
                $catArr[$data['commsetting_prodcat_id']] = html_entity_decode($selectedCatName);
            }
        }

        $frm = $this->getForm($recordId, $userArr, $prodArr, $catArr);
        if (!empty($data)) {
            $frm->fill($data);
        }

        $this->set('recordId', $recordId);
        $this->set('frm', $frm);
        $this->set('formLayout', Language::getLayoutDirection($this->siteLangId));
        $this->set('html', $this->_template->render(false, false, NULL, true));
        $this->_template->render(false, false, 'json-success.php', true, false);
    }

    public function setup()
    {
        $this->objPrivilege->canEditCommissionSettings();

        $frm = $this->getForm();
        $post = $frm->getFormDataFromArray(FatApp::getPostedData());

        if (false === $post) {
            LibHelper::exitWithError(current($frm->getValidationErrors()), true);
        }

        $post['commsetting_prodcat_id'] = FatApp::getPostedData('commsetting_prodcat_id', FatUtility::VAR_INT, 0);
        $post['commsetting_user_id'] = FatApp::getPostedData('commsetting_user_id', FatUtility::VAR_INT, 0);
        $post['commsetting_product_id'] = FatApp::getPostedData('commsetting_product_id', FatUtility::VAR_INT, 0);

        $recordId = $post['commsetting_id'];
        unset($post['commsetting_id']);

        $isMandatory = false;
        if ($data = Commission::getAttributesById($recordId, array('commsetting_is_mandatory'))) {
            $isMandatory = $data['commsetting_is_mandatory'];
        }

        if (false == $isMandatory && 1 < $recordId && (empty($post['commsetting_prodcat_id']) && empty($post['commsetting_user_id']) && empty($post['commsetting_product_id']))) {
            LibHelper::exitWithError(Labels::getLabel('ERR_PLEASE_ADD_COMMISSION_CORRESPONDING_TO_PRODUCT,_CATEGORY_OR_USER', $this->siteLangId), true);
        }

        if ($isMandatory) {
            $post['commsetting_product_id'] = 0;
            $post['commsetting_user_id'] = 0;
            $post['commsetting_prodcat_id'] = 0;
        }

        if (1 > $recordId) {
            $srch = Commission::getSearchObject();
            $srch->addCondition('commsetting_product_id', '=', $post['commsetting_product_id']);
            $srch->addCondition('commsetting_user_id', '=', $post['commsetting_user_id']);
            $srch->addCondition('commsetting_prodcat_id', '=', $post['commsetting_prodcat_id']);          
            $srch->setPageSize(1);;
            if (FatApp::getDb()->fetch($srch->getResultSet())) {
                LibHelper::exitWithError(Labels::getLabel('ERR_PLEASE_BIND_ANY_CONDITION_(CATEGORY,_SELLER_OR_PRODUCT).', $this->siteLangId), true);
            }
        }

        $record = new Commission($recordId);
        if (!$record->addUpdateData($post)) {
            $msg = $record->getError();
            if (false !== strpos(strtolower($msg), 'duplicate')) {
                $msg = Labels::getLabel('ERR_DUPLICATE_COMMISSION_SETTING', $this->siteLangId);
            }
            LibHelper::exitWithError($msg, true);
        }

        $insertId = $record->getMainTableRecordId();
        if (!$insertId) {
            $insertId = FatApp::getDb()->getInsertId();
        }

        if (!$record->addCommissionHistory($insertId)) {
            LibHelper::exitWithError($record->getError(), true);
        }

        $this->set('msg', $this->str_update_record);
        $this->set('recordId', $recordId);
        $this->_template->render(false, false, 'json-success.php');
    }

    private function rowsData()
    {
        $recordId = FatApp::getPostedData('recordId', FatUtility::VAR_INT, 0);
        $pagesize = FatApp::getConfig('CONF_ADMIN_PAGESIZE', FatUtility::VAR_INT, 10);
        $page = FatApp::getPostedData('page', FatUtility::VAR_INT, 1);
        $page = ($page <= 0) ? 1 : $page;

        $srch = Commission::getCommissionHistorySettingsObj($this->siteLangId);
        $srch->addCondition('tcsh.csh_commsetting_id', '=', 'mysql_func_' . $recordId, 'AND', true);
        $srch->setPageNumber($page);
        $srch->setPageSize($pagesize);

        $records = FatApp::getDb()->fetchAll($srch->getResultSet());

        $this->set("arrListing", $records);
        $this->set('pageCount', $srch->pages());
        $this->set('recordCount', $srch->recordCount());
        $this->set('page', $page);
        $this->set('pageSize', $pagesize);
        $this->set('postedData', FatApp::getPostedData());
    }

    public function viewLog()
    {
        $this->rowsData();
        $this->set('html', $this->_template->render(false, false, NULL, true));
        $this->_template->render(false, false, 'json-success.php', true, false);
    }

    public function getRows()
    {
        $this->rowsData();
        $this->set('html', $this->_template->render(false, false, NULL, true));
        $this->_template->render(false, false, 'json-success.php', true, false);
    }

    public function deleteRecord()
    {
        $this->objPrivilege->canEditCommissionSettings();

        $recordId = FatApp::getPostedData('recordId', FatUtility::VAR_INT, 0);
        if ($recordId < 1) {
            LibHelper::exitWithError($this->str_invalid_request_id, true);
        }

        $row = Commission::getAttributesById($recordId, array('commsetting_id', 'commsetting_is_mandatory'));
        if ($row == false || ($row != false && $row['commsetting_is_mandatory'] == 1)) {
            LibHelper::exitWithError($this->str_invalid_request_id, true);
        }

        $obj = new Commission($recordId);
        if (!$obj->deleteRecord(false)) {
            LibHelper::exitWithError($obj->getError(), true);
        }

        $this->set('msg', $this->str_delete_record);
        $this->_template->render(false, false, 'json-success.php');
    }

    public function deleteSelected()
    {
        $this->objPrivilege->canEditCommissionSettings();
        $recordIdsArr = FatUtility::int(FatApp::getPostedData('commsetting_ids'));

        if (empty($recordIdsArr)) {
            LibHelper::exitWithError($this->str_invalid_request, true);
        }

        foreach ($recordIdsArr as $recordId) {
            if (1 > $recordId) {
                continue;
            }
            $obj = new Commission($recordId);
            if (!$obj->deleteRecord(false)) {
                LibHelper::exitWithError($obj->getError(), true);
            }
        }
        $this->set('msg', Labels::getLabel('MSG_RECORDS_DELETED_SUCCESSFULLY', $this->siteLangId));
        $this->_template->render(false, false, 'json-success.php');
    }    

    public function productAutoComplete()
    {
        $pagesize = 20;
        $page = FatApp::getPostedData('page', FatUtility::VAR_INT, 1);

        if ($page < 2) {
            $page = 1;
        }

        $srch = Product::getSearchObject($this->siteLangId);

        $post = FatApp::getPostedData();
        if (isset($post['keyword']) && '' != $post['keyword']) {
            $srch->addCondition('product_name', 'LIKE', '%' . $post['keyword'] . '%');
        }
        $srch->setPageNumber($page);
        $srch->setPageSize($pagesize);
        $srch->addMultipleFields(array('product_id', 'IFNULL(product_name,product_identifier) as product_name'));

        $products = FatApp::getDb()->fetchAll($srch->getResultSet(), 'product_id');
        $json = array(
            'pageCount' => $srch->pages()
        );
        foreach ($products as $key => $product) {
            $json['results'][] = array(
                'id' => $key,
                'text' => strip_tags(html_entity_decode($product['product_name'], ENT_QUOTES, 'UTF-8'))
            );
        }
        die(FatUtility::convertToJson($json));
    }

    private function getForm($recordId = 0, $userArr = [], $prodArr = [], $catArr = [])
    {
        $recordId = FatUtility::int($recordId);
        $isMandatory = false;
        if ($data = Commission::getAttributesById($recordId, array('commsetting_is_mandatory'))) {
            $isMandatory = $data['commsetting_is_mandatory'];
        }
        $frm = new Form('frmCommission');
        $frm->addHiddenField('', 'commsetting_id', $recordId);

        if (!$isMandatory) {
            $frm->addSelectBox(Labels::getLabel('FRM_CATEGORY_NAME', $this->siteLangId), 'commsetting_prodcat_id', $catArr, '', [], '');
            $frm->addSelectBox(Labels::getLabel('FRM_SELLER', $this->siteLangId), 'commsetting_user_id', $userArr, '', [], '');
            $frm->addSelectBox(Labels::getLabel('FRM_PRODUCT', $this->siteLangId), 'commsetting_product_id', $prodArr, '', [], '');
        }

        $fld = $frm->addFloatField(Labels::getLabel('FRM_COMMISSION_FEES_(%)', $this->siteLangId), 'commsetting_fees');
        $fld->requirements()->setRange('0', '100');
        return $frm;
    }

    protected function getFormColumns(): array
    {
        $commissionTblHeadingCols = CacheHelper::get('commissionTblHeadingCols' . $this->siteLangId, CONF_DEF_CACHE_TIME, '.txt');
        if ($commissionTblHeadingCols) {
            return json_decode($commissionTblHeadingCols, true);
        }

        $arr = [
            'select_all' => Labels::getLabel('LBL_SELECT_ALL', $this->siteLangId),
            /*   'listSerial' => Labels::getLabel('LBL_SR._NO', $this->siteLangId), */
            'commsetting_prodcat_id' => Labels::getLabel('LBL_CATEGORY', $this->siteLangId),
            'commsetting_user_id' => Labels::getLabel('LBL_SHOP/_SELLER_NAME', $this->siteLangId),
            'commsetting_product_id' => Labels::getLabel('LBL_PRODUCT', $this->siteLangId),
            'commsetting_fees' => Labels::getLabel('LBL_FEES_[%]', $this->siteLangId),
            'action' => Labels::getLabel('LBL_ACTION_BUTTONS', $this->siteLangId),
        ];
        CacheHelper::create('commissionTblHeadingCols' . $this->siteLangId, json_encode($arr), CacheHelper::TYPE_LABELS);
        return $arr;
    }

    protected function getDefaultColumns(): array
    {
        return [
            'select_all',
            /*  'listSerial', */
            'commsetting_prodcat_id',
            'commsetting_user_id',
            'commsetting_product_id',
            'commsetting_fees',
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
                    ['title' => Labels::getLabel('LBL_SETTINGS', $this->siteLangId), 'href' => UrlHelper::generateUrl('Settings')],
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
