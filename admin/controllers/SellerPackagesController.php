<?php

class SellerPackagesController extends ListingBaseController
{
    protected string $modelClass = 'SellerPackages';
    protected $pageKey = 'SELLER_SUBSCRIPTION_PACKAGES';

    public function __construct($action)
    {
        parent::__construct($action);
        $this->objPrivilege->canViewSellerPackages();
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
            $this->set("canEdit", $this->objPrivilege->canEditSellerPackages($this->admin_id, true));
        } else {
            $this->objPrivilege->canEditSellerPackages();
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
        $this->formLangFields = [$this->modelObj::tblFld('name')];
        $this->formLangFields = [$this->modelObj::tblFld('text')];
        $this->set('formTitle', Labels::getLabel('LBL_SUBSCRIPTION_PACKAGES_SETUP', $this->siteLangId));
    }

    public function index()
    {
        $fields = $this->getFormColumns();
        $frmSearch = $this->getSearchForm($fields);

        $pageData = PageLanguageData::getAttributesByKey($this->pageKey, $this->siteLangId);
        $pageTitle = $pageData['plang_title'] ?? LibHelper::getControllerName(true);

        $this->setModel();
        $actionItemsData = HtmlHelper::getDefaultActionItems($fields, $this->modelObj);
        $actionItemsData['statusButtons'] = true;
        $actionItemsData['performBulkAction'] = true;

        $this->set('pageData', $pageData);
        $this->set('pageTitle', $pageTitle);
        $this->set('actionItemsData', $actionItemsData);
        $this->set("frmSearch", $frmSearch);
        $this->set('defaultColumns', $this->getDefaultColumns());
        $this->set('keywordPlaceholder', Labels::getLabel('FRM_SEARCH_BY_PACKAGE_NAME', $this->siteLangId));
        $this->getListingData();
        $this->setCustomColumnWidth();
        $this->set('autoTableColumWidth', false);

        $this->_template->addJs(['js/jquery.tablednd.js', 'seller-packages/page-js/index.js']);
        $this->_template->render(true, true, '_partial/listing/index.php');
    }

    public function search()
    {
        $this->getListingData();
        $jsonData = [
            'listingHtml' => $this->_template->render(false, false, 'seller-packages/search.php', true),
            'paginationHtml' => $this->_template->render(false, false, '_partial/listing/listing-foot.php', true)
        ];
        LibHelper::exitWithSuccess($jsonData, true);
    }

    private function getListingData()
    {
        $db = FatApp::getDb();

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

        $post = $srchFrm->getFormDataFromArray(FatApp::getPostedData());
        $page = FatApp::getPostedData('page', FatUtility::VAR_INT, 1);
        $page = ($page <= 0) ? 1 : $page;

        $pageSize = applicationConstants::getPageSize(FatApp::getPostedData('pageSize', FatUtility::VAR_INT));

        $srch = SellerPackages::getSearchObject($this->siteLangId);
        $srch->addMultipleFields(array("sp.*", "IFNULL( spl." . SellerPackages::DB_TBL_PREFIX . "name, sp." . SellerPackages::DB_TBL_PREFIX . "identifier ) as " . SellerPackages::DB_TBL_PREFIX . "name"));

        if (isset($post['keyword']) && '' != $post['keyword']) {
            $condition = $srch->addCondition("sp." . SellerPackages::DB_TBL_PREFIX . "identifier", 'like', '%' . $post['keyword'] . '%');
            $condition->attachCondition("spl." . SellerPackages::DB_TBL_PREFIX . "name", 'like', '%' . $post['keyword'] . '%', 'OR');
        }

        if (!array_key_exists($sortOrder, applicationConstants::sortOrder($this->siteLangId))) {
            $sortOrder = applicationConstants::SORT_ASC;
        }

        $srch->addOrder($sortBy, $sortOrder);

        $srch->setPageNumber($page);
        $srch->setPageSize($pageSize);
        $srch->removeFld(['select_all', 'action']);
        $rs = $srch->getResultSet();
        $arrListing = $db->fetchAll($rs);

        $this->set("arrListing", $arrListing);
        $this->set('pageCount', $srch->pages());
        $this->set('recordCount', $srch->recordCount());
        $this->set('page', $page);
        $this->set('pageSize', $pageSize);
        $this->set('postedData', $post);
        $this->set('activeInactiveArr', applicationConstants::getActiveInactiveArr($this->siteLangId));

        $this->set('sortBy', $sortBy);
        $this->set('sortOrder', $sortOrder);
        $this->set('fields', $fields);
        $this->set('allowedKeysForSorting', $allowedKeysForSorting);
        $this->set('canEdit', $this->objPrivilege->canEditSellerPackages($this->admin_id, true));
    }

    public function form()
    {
        $this->checkEditPrivilege();
        $recordId = FatApp::getPostedData('recordId', FatUtility::VAR_INT, 0);
        $frm = $this->getForm($recordId);
        if (0 < $recordId) {
            $data = SellerPackages::getAttributesByLangId(CommonHelper::getDefaultFormLangId(), $recordId, ['*', 'IFNULL(spackage_name,spackage_identifier) as spackage_name'], applicationConstants::JOIN_RIGHT);

            if ($data === false) {
                LibHelper::exitWithError($this->str_invalid_request, true);
            }
            $frm->fill($data);
        }

        $this->set('recordId', $recordId);
        $this->set('frm', $frm);
        $this->set('formTitle', Labels::getLabel('LBL_SUBSCRIPTION_PACKAGES_SETUP', $this->siteLangId));
        $this->set('html', $this->_template->render(false, false, NULL, true));
        $this->_template->render(false, false, 'json-success.php', true, false);
    }

    public function setup()
    {
        $this->checkEditPrivilege();
        $recordId = FatApp::getPostedData('spackage_id', FatUtility::VAR_INT, 0);

        $frm = $this->getForm($recordId);
        $post = $frm->getFormDataFromArray(FatApp::getPostedData());

        if (false === $post) {
            LibHelper::exitWithError(current($frm->getValidationErrors()), true);
        }

        if (FatApp::getConfig('CONF_WITHOUT_PROD_VARIANTS', FatUtility::VAR_INT, 0)) {
            $post['spackage_inventory_allowed'] = $post['spackage_products_allowed'];
        }
        $recordObj = new SellerPackages($recordId);
        $post['spackage_identifier'] = $post['spackage_name'];
        $recordObj->assignValues($post);
        if (!$recordObj->save()) {
            $msg = $recordObj->getError();
            if (false !== strpos(strtolower($msg), 'duplicate')) {
                $msg = Labels::getLabel('ERR_DUPLICATE_RECORD_NAME', $this->siteLangId);
            }
            LibHelper::exitWithError($msg, true);
        }

        $this->setLangData($recordObj, [$recordObj::tblFld('name') => $post[$recordObj::tblFld('name')]]);

        $this->_template->render(false, false, 'json-success.php');
    }

    private function getForm($recordId)
    {
        $recordId = FatUtility::int($recordId);
        $frm = new Form('frmSellerPackage');
        $frm->addHiddenField('', 'spackage_id');
        $frm->addRequiredField(Labels::getLabel('FRM_PACKAGE_NAME', $this->siteLangId), SellerPackages::DB_TBL_PREFIX . 'name');
        $disbaleText = array();
        if ($recordId > 0) {
            $disbaleText = array('disabled' => 'disabled');
        }
        $packageTypeFld = $frm->addSelectBox(Labels::getLabel('FRM_PACKAGE_TYPE', $this->siteLangId), SellerPackages::DB_TBL_PREFIX . 'type', SellerPackages::getPackageTypes(), '', $disbaleText, '');
        if (0 == $recordId) {
            $packageTypeFld->requirements()->setRequired();
        }
        $commissionRate = $frm->addFloatField(Labels::getLabel('FRM_PACKAGE_COMMISION_RATE', $this->siteLangId) . '[%]', SellerPackages::DB_TBL_PREFIX . 'commission_rate');
        $commissionRate->requirements()->setRange(0, 100);

        $fld = $frm->addIntegerField(Labels::getLabel('FRM_PACKAGE_PRODUCTS_ALLOWED', $this->siteLangId), SellerPackages::DB_TBL_PREFIX . 'products_allowed');
        $fld->requirements()->setIntPositive();

        if (!FatApp::getConfig('CONF_WITHOUT_PROD_VARIANTS', FatUtility::VAR_INT, 0)) {
            $fld = $frm->addIntegerField(Labels::getLabel('FRM_PACKAGE_INVENTORY_ALLOWED', $this->siteLangId), SellerPackages::DB_TBL_PREFIX . 'inventory_allowed');
            $fld->requirements()->setIntPositive();
        }

        $fld = $frm->addIntegerField(Labels::getLabel('FRM_PACKAGE_IMAGES_PER_CATALOG', $this->siteLangId), SellerPackages::DB_TBL_PREFIX . 'images_per_product');
        $fld->requirements()->setIntPositive();
        
        $fld = $frm->addIntegerField(Labels::getLabel('FRM_RFQ_OFFERS_LIMIT', $this->siteLangId), SellerPackages::DB_TBL_PREFIX . 'rfq_offers_allowed');
        $fld->requirements()->setIntPositive();
        $fld->requirements()->setRange('1', '9999999');

        $frm->addTextarea(Labels::getLabel('FRM_PACKAGE_DESCRIPTION', $this->siteLangId), SellerPackages::DB_TBL_PREFIX . 'description');

        $fld = $frm->addRequiredField(Labels::getLabel('FRM_PACKAGE_DISPLAY_ORDER', $this->siteLangId), SellerPackages::DB_TBL_PREFIX . 'display_order');
        $fld->requirements()->setIntPositive();

        $frm->addCheckBox(Labels::getLabel('FRM_PACKAGE_STATUS', $this->siteLangId), SellerPackages::DB_TBL_PREFIX . 'active', applicationConstants::ACTIVE, [], true, applicationConstants::INACTIVE);

        $languageArr = Language::getDropDownList();
        $translatorSubscriptionKey = FatApp::getConfig('CONF_TRANSLATOR_SUBSCRIPTION_KEY', FatUtility::VAR_STRING, '');
        if (!empty($translatorSubscriptionKey) && 1 < count($languageArr)) {
            $frm->addCheckBox(Labels::getLabel('FRM_UPDATE_OTHER_LANGUAGES_DATA', $this->siteLangId), 'auto_update_other_langs_data', 1, array(), false, 0);
        }
        return $frm;
    }

    protected function getLangForm($recordId = 0, $langId = 0)
    {
        $this->checkEditPrivilege();
        $langId = 1 > $langId ? $this->siteLangId : $langId;

        $frm = new Form('frmSellerPackageLang');
        $frm->addHiddenField('', SellerPackages::DB_TBL_PREFIX . 'id', $recordId);
        $frm->addSelectBox(Labels::getLabel('FRM_LANGUAGE', $langId), 'lang_id', Language::getAllNames(), $langId, array(), '');
        $frm->addRequiredField(Labels::getLabel('FRM_PACKAGE_NAME', $langId), SellerPackages::DB_TBL_PREFIX . 'name');
        $frm->addTextarea(Labels::getLabel('FRM_PACKAGE_DESCRIPTION', $langId), SellerPackages::DB_TBL_PREFIX . 'text');
        return $frm;
    }

    public function autoComplete()
    {
        $pagesize = 10;
        $post = FatApp::getPostedData();
        $srch = SellerPackagePlans::getSearchObject();

        $srch->joinTable(
            SellerPackages::DB_TBL,
            'LEFT OUTER JOIN',
            'sp.spackage_id = spp.spplan_spackage_id ',
            'sp'
        );
        $srch->joinTable(
            SellerPackages::DB_TBL . '_lang',
            'LEFT OUTER JOIN',
            'spl.spackagelang_spackage_id = sp.spackage_id AND spl.spackagelang_lang_id = ' . $this->siteLangId,
            'spl'
        );

        $srch->addOrder('spackage_name');

        $srch->addMultipleFields(array('spplan_id', "IFNULL( spl.spackage_name, sp.spackage_identifier ) as spackage_name", "spplan_interval", "spplan_frequency"));
        $srch->addCondition('spackage_active', '=', applicationConstants::YES);
        if (isset($post['keyword']) && '' != $post['keyword']) {
            $cnd = $srch->addCondition('spackage_name', 'LIKE', '%' . $post['keyword'] . '%');
            $cnd->attachCondition('spackage_identifier', 'LIKE', '%' . $post['keyword'] . '%', 'OR');
        }

        $doNotLimitRecords = FatApp::getPostedData('doNotLimitRecords', FatUtility::VAR_INT, 0);
        if (0 < $doNotLimitRecords) {
            $srch->doNotLimitRecords();
        } else {
            $srch->setPageSize($pagesize);
        }

        $rs = $srch->getResultSet();

        $plans = FatApp::getDb()->fetchAll($rs, 'spplan_id');
        $json = array(
            'pageCount' => $srch->pages(),
            'results' => []
        );
        foreach ($plans as $key => $plan) {
            $json['results'][] = array(
                'id' => $plan['spplan_id'],
                'text' => DiscountCoupons::getPlanTitle($plan, $this->siteLangId),
            );
        }
        die(json_encode($json));
    }

    public function updateOrder()
    {
        $this->objPrivilege->canEditSellerPackages();
        $post = FatApp::getPostedData();
        if (!empty($post)) {
            $faqCatObj = new SellerPackages();
            if (!$faqCatObj->updateOrder($post['packageId'])) {
                LibHelper::exitWithError($faqCatObj->getError(), true);
            }
            LibHelper::exitWithSuccess(Labels::getLabel('MSG_ORDER_UPDATED_SUCCESSFULLY', $this->siteLangId), true);
        }
    }

    protected function getFormColumns(): array
    {
        $subsPkgTblHeadingCols = CacheHelper::get('subsPkgTblHeadingCols' . $this->siteLangId, CONF_DEF_CACHE_TIME, '.txt');
        if ($subsPkgTblHeadingCols) {
            return json_decode($subsPkgTblHeadingCols, true);
        }

        $arr = [
            'dragdrop' => '',
            'select_all' => Labels::getLabel('LBL_SELECT_ALL', $this->siteLangId),
            'spackage_display_order' => Labels::getLabel('LBL_DISPLAY_ORDER', $this->siteLangId),
            'spackage_name' => Labels::getLabel('LBL_PACKAGE_NAME', $this->siteLangId),
            'spackage_active' => Labels::getLabel('LBL_STATUS', $this->siteLangId),
            'action' => Labels::getLabel('LBL_ACTION_BUTTONS', $this->siteLangId),
        ];
        CacheHelper::create('subsPkgTblHeadingCols' . $this->siteLangId, json_encode($arr), CacheHelper::TYPE_LABELS);
        return $arr;
    }

    protected function getDefaultColumns(): array
    {
        return [
            'dragdrop',
            'select_all',
            'spackage_display_order',
            'spackage_name',
            'spackage_active',
            'action',
        ];
    }

    protected function excludeKeysForSort($fields = []): array
    {
        return array_diff($fields, ['dragdrop', 'spackage_active'], Common::excludeKeysForSort());
    }

    /**
     * setCustomColumnWidth
     *
     * @return void
     */
    protected function setCustomColumnWidth(): void
    {
        $arr = [
            'dragdrop' => [
                'width' => '5%'
            ],
            'select_all' => [
                'width' => '5%'
            ],
            'spackage_display_order' => [
                'width' => '10%'
            ],
            'spackage_name' => [
                'width' => '45%'
            ],
            'spackage_active' => [
                'width' => '15%'
            ],
            'action' => [
                'width' => '20%'
            ],
        ];

        $this->set('tableHeadAttrArr', $arr);
    }

    public function getBreadcrumbNodes($action)
    {
        switch ($action) {
            case 'index':
                $pageData = PageLanguageData::getAttributesByKey($this->pageKey, $this->siteLangId);
                $pageTitle = $pageData['plang_title'] ?? Labels::getLabel('NAV_SELLER_PACKAGES', $this->siteLangId);
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
