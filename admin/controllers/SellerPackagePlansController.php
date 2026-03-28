<?php

class SellerPackagePlansController extends ListingBaseController
{
    protected string $modelClass = 'SellerPackagePlans';

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

    public function index()
    {
        Message::addErrorMessage(Labels::getLabel('ERR_PLEASE_SELECT_SELLER_PACKAGE_FIRST', $this->siteLangId));
        FatApp::redirectUser(UrlHelper::generateUrl('SellerPackages'));
    }

    public function list(int $spackageId)
    {
        $packageData =  SellerPackages::getAttributesByLangId($this->siteLangId, $spackageId, ['spackage_name', 'spackage_identifier'], applicationConstants::JOIN_RIGHT);

        if ($packageData === false) {
            Message::addErrorMessage($this->str_invalid_request_id);
            FatApp::redirectUser(UrlHelper::generateUrl('SellerPackages'));
        }

        $fields = $this->getFormColumns();
        $frmSearch = $this->getSearchForm($fields);
        $frmSearch->fill(['spackageId' => $spackageId]);

        $pageData = PageLanguageData::getAttributesByKey('MANAGE_SELLER_PACKAGE_PLANS', $this->siteLangId);
        $pageTitle = !empty($packageData['spackage_name']) ? $packageData['spackage_name'] : $packageData['spackage_identifier'];

        $this->setModel();
        $actionItemsData = HtmlHelper::getDefaultActionItems($fields, $this->modelObj);
        $actionItemsData['statusButtons'] = true;
        $actionItemsData['performBulkAction'] = true;

        $actionItemsData['newRecordBtnAttrs'] = [
            'attr' => [
                'onclick' => 'addNewPlan(' . $spackageId . ')',
            ]
        ];

        $this->set('pageData', $pageData);
        $this->set("frmSearch", $frmSearch);
        $this->set('pageTitle', $pageTitle);
        $this->set('actionItemsData', $actionItemsData);
        $this->set('defaultColumns', $this->getDefaultColumns());
        $this->set('keywordPlaceholder', Labels::getLabel('FRM_SEARCH_BY_PLAN_PRICE', $this->siteLangId));
        $this->getListingData($spackageId);
        $this->setCustomColumnWidth();
        $this->set('autoTableColumWidth', false);
        $this->_template->addJs(['seller-package-plans/page-js/list.js']);
        $this->_template->render(true, true, '_partial/listing/index.php');
    }

    public function search()
    {
        $spackageId = FatApp::getPostedData('spackageId', FatUtility::VAR_INT, 0);
        $this->getListingData($spackageId);

        $jsonData = [
            'listingHtml' => $this->_template->render(false, false, 'seller-package-plans/search.php', true),
            'paginationHtml' => $this->_template->render(false, false, '_partial/listing/listing-foot.php', true)
        ];
        LibHelper::exitWithSuccess($jsonData, true);
    }

    private function getListingData(int $spackageId)
    {
        $spackageId = FatApp::getPostedData('spackageId', FatUtility::VAR_INT, $spackageId);

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
        $post = $srchFrm->getFormDataFromArray(FatApp::getPostedData());

        $post['spackageId'] = $spackageId;

        $page = FatApp::getPostedData('page', FatUtility::VAR_INT, 1);
        $page = ($page <= 0) ? 1 : $page;

        $pageSize = applicationConstants::getPageSize(FatApp::getPostedData('pageSize', FatUtility::VAR_INT));

        $srch = SellerPackagePlans::getSearchObject($this->siteLangId);
        $srch->addMultipleFields(array("spp.*"));
        $srch->addCondition(SellerPackagePlans::DB_TBL_PREFIX . 'spackage_id', '=', $spackageId);
        if (!array_key_exists($sortOrder, applicationConstants::sortOrder($this->siteLangId))) {
            $sortOrder = applicationConstants::SORT_ASC;
        }

        if (isset($post['keyword']) && '' != $post['keyword']) {
            $srch->addCondition('spp.spplan_price', 'like', $post['keyword'] . '%');
        }

        $srch->addOrder($sortBy, $sortOrder);

        $srch->setPageNumber($page);
        $srch->setPageSize($pageSize);
        $srch->removeFld(['select_all', 'action']);
        $arrListing = FatApp::getDb()->fetchAll($srch->getResultSet());

        $this->set("arrListing", $arrListing);
        $this->set('pageCount', $srch->pages());
        $this->set('recordCount', $srch->recordCount());
        $this->set('page', $page);
        $this->set('pageSize', $pageSize);
        $paginationArr = empty($postedData) ? $post : $postedData;
        $this->set('postedData', $paginationArr);
        $this->set('activeInactiveArr', applicationConstants::getActiveInactiveArr($this->siteLangId));

        $this->set('sortBy', $sortBy);
        $this->set('sortOrder', $sortOrder);
        $this->set('fields', $fields);
        $this->set('allowedKeysForSorting', $allowedKeysForSorting);
        $this->set('spackageId', $spackageId);
        $this->set('canEdit', $this->objPrivilege->canEditSellerPackages($this->admin_id, true));
    }

    public function getSearchForm($fields = [])
    {
        $fields = $this->getFormColumns();

        $frm = new Form('frmRecordSearch');
        $frm->addHiddenField('', 'page');
        $frm->addHiddenField('', 'spackageId');
        $fld = $frm->addTextBox(Labels::getLabel('FRM_KEYWORD', $this->siteLangId), 'keyword');
        $fld->overrideFldType('search');

        if (!empty($fields)) {
            $this->addSortingElements($frm, 'spplan_display_order');
        }

        HtmlHelper::addSearchButton($frm);
        return $frm;
    }

    public function form()
    {
        $this->checkEditPrivilege();

        $spackageId = FatApp::getPostedData('spackageId', FatUtility::VAR_INT, 0);
        $spPlanId = FatApp::getPostedData('spPlanId', FatUtility::VAR_INT, 0);

        if ($spackageId < 1) {
            LibHelper::exitWithError($this->str_invalid_request, true);
        }
        $frm = $this->getForm($spackageId);
        if (0 < $spPlanId) {
            $data = SellerPackagePlans::getAttributesById($spPlanId);
            if ($data === false) {
                LibHelper::exitWithError($this->str_invalid_request, true);
            }
        } else {
            $data[SellerPackagePlans::DB_TBL_PREFIX . 'spackage_id'] = $spackageId;
        }
        $frm->fill($data);
        $this->set('spackageId', $spackageId);
        $this->set('spPlanId', $spPlanId);
        $this->set('frm', $frm);
        $this->set('includeTabs', false);
        $this->set('html', $this->_template->render(false, false, NULL, true));
        $this->_template->render(false, false, 'json-success.php', true, false);
    }

    private function getForm($spackageId)
    {
        $frm = new Form('frmSellerPackagePlan', array('id' => 'frmSellerPackagePlan'));
        $frm->addHiddenField('', SellerPackagePlans::tblFld('id'));
        $frm->addHiddenField('', SellerPackagePlans::tblFld('spackage_id'));

        $subsPeriodOption = SellerPackagePlans::getSubscriptionPeriods($this->siteLangId);
        $subsPeriodFld = $frm->addSelectBox(Labels::getLabel('FRM_PERIOD', $this->siteLangId), 'spplan_frequency', $subsPeriodOption, '', ['class' => 'fieldsVisibilityJs'], '');

        $fld = $frm->addTextBox(Labels::getLabel('FRM_TIME_INTERVAL_(FREQUENCY)', $this->siteLangId), 'spplan_interval');
        $fld->requirements()->setIntPositive();
        $intervalFld = new FormFieldRequirement('spplan_interval', Labels::getLabel('FRM_TIME_INTERVAL_(FREQUENCY)', $this->siteLangId));
        $intervalFld->setRequired(false);
        $reqIntervalFld = new FormFieldRequirement('spplan_interval', Labels::getLabel('FRM_TIME_INTERVAL_(FREQUENCY)', $this->siteLangId));
        $reqIntervalFld->setRequired(true);

        $subsPeriodFld->requirements()->addOnChangerequirementUpdate(SellerPackagePlans::SUBSCRIPTION_PERIOD_UNLIMITED, 'eq', 'spplan_interval', $intervalFld);
        $subsPeriodFld->requirements()->addOnChangerequirementUpdate(SellerPackagePlans::SUBSCRIPTION_PERIOD_UNLIMITED, 'ne', 'spplan_interval', $reqIntervalFld);

        if (SellerPackages::getAttributesById($spackageId, SellerPackages::tblFld('type'))  != SellerPackages::FREE_TYPE) {
            $frm->addFloatField(Labels::getLabel('FRM_PRICE', $this->siteLangId), 'spplan_price')->requirements()->setRange('0.01', '9999999999');
            $fldPckPrice = $frm->getField('spplan_price');
            $fldPckPrice->setWrapperAttribute('class', 'package_price');
            $frm->addFloatField(Labels::getLabel('FRM_DISCOUNT_VALUE', $this->siteLangId), 'spplan_discount')->requirements()->setRange(0, '9999999999');
        }

        $fld = $frm->addIntegerField(Labels::getLabel('FRM_PLAN_DISPLAY_ORDER', $this->siteLangId), 'spplan_display_order');
        $fld->requirements()->setIntPositive();

        $frm->addCheckBox(Labels::getLabel('FRM_STATUS', $this->siteLangId), 'spplan_active', applicationConstants::ACTIVE, [], true, applicationConstants::INACTIVE);

        return $frm;
    }

    public function setup()
    {
        $this->checkEditPrivilege();
        $post = FatApp::getPostedData();
        $spackageId = FatApp::getPostedData('spplan_spackage_id', FatUtility::VAR_INT, 0);

        $frm = $this->getForm($spackageId);
        $post = $frm->getFormDataFromArray(FatApp::getPostedData());
        if (false === $post) {
            LibHelper::exitWithError(current($frm->getValidationErrors()), true);
        }

        $packageRow = SellerPackages::getAttributesById($spackageId, ['spackage_type']);
        if (false === $packageRow) {
            LibHelper::exitWithError($this->str_invalid_request, true);
        }
        $data = $post;

        if ($packageRow[SellerPackages::DB_TBL_PREFIX . 'type'] == SellerPackages::FREE_TYPE) {
            $data[SellerPackagePlans::DB_TBL_PREFIX . 'trial_frequency'] = '';
            $data[SellerPackagePlans::DB_TBL_PREFIX . 'trial_interval'] = 0;
            $data[SellerPackagePlans::DB_TBL_PREFIX . 'price'] = 0;
            $data[SellerPackagePlans::DB_TBL_PREFIX . 'discount'] = 0;
        } else {
            $data[SellerPackagePlans::DB_TBL_PREFIX . 'discount'] = FatUtility::float($data[SellerPackagePlans::DB_TBL_PREFIX . 'discount'] ?? 0);
            $planPrice = FatUtility::float($data[SellerPackagePlans::DB_TBL_PREFIX . 'price'] ?? 0);
            if ($data[SellerPackagePlans::DB_TBL_PREFIX . 'discount'] < 0) {
                LibHelper::exitWithError(Labels::getLabel('ERR_INVALID_REQUEST', $this->siteLangId), true);
            }
            if ($data[SellerPackagePlans::DB_TBL_PREFIX . 'discount'] > $planPrice) {
                LibHelper::exitWithError(Labels::getLabel('ERR_INVALID_REQUEST', $this->siteLangId), true);
            }
        }

        $record = new SellerPackagePlans($post['spplan_id']);
        $record->assignValues($data);
        if (!$record->save()) {
            LibHelper::exitWithError($record->getError(), true);
        }
        $this->set('msg', $this->str_setup_successful);
        $this->_template->render(false, false, 'json-success.php');
    }


    public function updateOrder()
    {
        $this->objPrivilege->canEditSellerPackages();
        $post = FatApp::getPostedData();
        if (!empty($post)) {
            $faqCatObj = new SellerPackagePlans();
            if (!$faqCatObj->updateOrder($post['planId'])) {
                LibHelper::exitWithError($faqCatObj->getError(), true);
            }
            LibHelper::exitWithSuccess(Labels::getLabel('MSG_ORDER_UPDATED_SUCCESSFULLY', $this->siteLangId), true);
        }
    }

    protected function getFormColumns(): array
    {
        $subsPkgTblHeadingCols = CacheHelper::get('subsPkgPlanTblHeadingCols_v2' . $this->siteLangId, CONF_DEF_CACHE_TIME, '.txt');
        if ($subsPkgTblHeadingCols) {
            return json_decode($subsPkgTblHeadingCols, true);
        }

        $arr = [
            'dragdrop' => '',
            'select_all' => Labels::getLabel('LBL_SELECT_ALL', $this->siteLangId),
            'spplan_display_order' => Labels::getLabel('LBL_DISPLAY_ORDER', $this->siteLangId),
            'spplan_price' => Labels::getLabel('LBL_PLAN_PRICE', $this->siteLangId),
            'spplan_discount' => Labels::getLabel('FRM_DISCOUNT_VALUE', $this->siteLangId),
            'spplan_interval' => Labels::getLabel('LBL_INTERVAL', $this->siteLangId),
            'spplan_active' => Labels::getLabel('LBL_STATUS', $this->siteLangId),
            'action' => Labels::getLabel('LBL_ACTION_BUTTONS', $this->siteLangId),
        ];
        CacheHelper::create('subsPkgPlanTblHeadingCols_v2' . $this->siteLangId, json_encode($arr), CacheHelper::TYPE_LABELS);
        return $arr;
    }

    protected function getDefaultColumns(): array
    {
        return [
            'dragdrop',
            'select_all',
            'spplan_display_order',
            'spplan_price',
            'spplan_discount',
            'spplan_interval',
            'spplan_active',
            'action',
        ];
    }

    protected function excludeKeysForSort($fields = []): array
    {
        return array_diff($fields, ['dragdrop', 'spplan_interval', 'spplan_active'], Common::excludeKeysForSort());
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
            'spplan_display_order' => [
                'width' => '10%'
            ],
            'spplan_price' => [
                'width' => '12%'
            ],
            'spplan_discount' => [
                'width' => '12%'
            ],
            'spplan_interval' => [
                'width' => '36%'
            ],
            'spplan_active' => [
                'width' => '10%'
            ],
            'action' => [
                'width' => '10%'
            ],
        ];

        $this->set('tableHeadAttrArr', $arr);
    }

    public function getBreadcrumbNodes($action)
    {
        switch ($action) {
            case 'list':
                $pageData = PageLanguageData::getAttributesByKey('MANAGE_SELLER_PACKAGES', $this->siteLangId);
                $pageTitle = $pageData['plang_title'] ?? Labels::getLabel('NAV_SELLER_PACKAGES', $this->siteLangId);

                $url = FatApp::getQueryStringData('url');
                $urlParts = explode('/', $url);
                $title = Labels::getLabel('LBL_SUBSCRIPTION_PACKAGE_PLANS', $this->siteLangId);
                if (isset($urlParts[2])) {
                    $attr = ['COALESCE(spackage_name, spackage_identifier) as spackage_name'];
                    $data = SellerPackages::getAttributesByLangId($this->siteLangId, $urlParts[2], $attr, applicationConstants::JOIN_RIGHT);
                    $title = $data['spackage_name'];
                }

                $this->nodes = [
                    ['title' => Labels::getLabel('LBL_SETTINGS', $this->siteLangId), 'href' => UrlHelper::generateUrl('Settings')],
                    ['title' => $pageTitle, 'href' => UrlHelper::generateUrl('SellerPackages')],
                    ['title' => $title]
                ];
                break;
            default:
                parent::getBreadcrumbNodes($action);
                break;
        }
        return $this->nodes;
    }
}
