<?php

class AdminLoginHistoryController extends ListingBaseController
{
    protected string $modelClass = 'AdminLoginHistory';
    protected $pageKey = 'MANAGE_ADMIN_LOGIN_HISTORY';

    public function __construct($action)
    {
        parent::__construct($action);
        $this->objPrivilege->canViewAdminLoginHistory();
    }

    protected function checkEditPrivilege(bool $setVariable = false): void
    {
        if (true === $setVariable) {
            $this->set('canEdit', $this->objPrivilege->canEditAdminLoginHistory($this->admin_id, true));
        } else {
            $this->objPrivilege->canEditAdminLoginHistory();
        }
    }

    public function index()
    {
        $fields = $this->getFormColumns();
        $frmSearch = $this->getSearchForm($fields);
        $pageData = PageLanguageData::getAttributesByKey($this->pageKey, $this->siteLangId);
        $pageTitle = $pageData['plang_title'] ?? LibHelper::getControllerName(true);

        $this->setModel();
        $actionItemsData = HtmlHelper::getDefaultActionItems($fields, $this->modelObj);
        $actionItemsData['newRecordBtn'] = false;

        $this->set('pageData', $pageData);
        $this->set('pageTitle', $pageTitle);
        $this->set('actionItemsData', $actionItemsData);
        $this->set('frmSearch', $frmSearch);
        $this->checkEditPrivilege(true);
        $this->getListingData();
        $this->_template->addJs(['admin-login-history/page-js/index.js']);
        $this->set('keywordPlaceholder', Labels::getLabel('FRM_SEARCH_BY_ADMIN_OR_IP', $this->siteLangId));
        $this->_template->render(true, true, '_partial/listing/index.php');
    }

    public function search()
    {
        $this->getListingData();
        $jsonData = [
            'listingHtml' => $this->_template->render(false, false, 'admin-login-history/search.php', true),
            'paginationHtml' => $this->_template->render(false, false, '_partial/listing/listing-foot.php', true),
        ];
        LibHelper::exitWithSuccess($jsonData, true);
    }

    private function getListingData()
    {
        $pageSize = applicationConstants::getPageSize(FatApp::getPostedData('pageSize', FatUtility::VAR_INT));

        $fields = $this->getFormColumns();
        $selectedFlds = FatApp::getPostedData('reportColumns', FatUtility::VAR_STRING, '');
        $selectedFlds = !empty($selectedFlds) ? json_decode($selectedFlds) + $this->getDefaultColumns() : $this->getDefaultColumns();

        $fields = FilterHelper::parseArrayByKeys($fields, $selectedFlds, true);
        $allowedKeysForSorting = $this->excludeKeysForSort(array_keys($fields));
        $sortBy = FatApp::getPostedData('sortBy', FatUtility::VAR_STRING, 'alh_logged_at');
        if (!array_key_exists($sortBy, $fields)) {
            $sortBy = 'alh_logged_at';
        }

        $sortOrder = applicationConstants::getSortOrder(
            FatApp::getPostedData('sortOrder', FatUtility::VAR_STRING, applicationConstants::SORT_DESC),
            applicationConstants::SORT_DESC
        );

        $searchForm = $this->getSearchForm($fields);
        $page = FatApp::getPostedData('page', FatUtility::VAR_INT, 1);
        $page = ($page <= 0) ? 1 : $page;
        $post = $searchForm->getFormDataFromArray(FatApp::getPostedData());

        $srch = AdminLoginHistory::getSearchObject();
        $srch->addMultipleFields(['alh.*']);

        if (!empty($post['keyword'])) {
            $keyword = trim($post['keyword']);
            $cond = $srch->addCondition('alh_admin_username', 'like', '%' . $keyword . '%');
            $cond->attachCondition('alh_admin_name', 'like', '%' . $keyword . '%', 'OR');
            $cond->attachCondition('alh_admin_email', 'like', '%' . $keyword . '%', 'OR');
            $cond->attachCondition('alh_ip', 'like', '%' . $keyword . '%', 'OR');
            $cond->attachCondition('alh_browser', 'like', '%' . $keyword . '%', 'OR');
        }

        $loginType = FatApp::getPostedData('login_type', FatUtility::VAR_INT, -1);
        if ($loginType > -1) {
            $srch->addCondition('alh_login_type', '=', $loginType);
        }

        $dateFrom = FatApp::getPostedData('date_from', FatUtility::VAR_DATE, '');
        if (!empty($dateFrom)) {
            $srch->addCondition('alh_logged_at', '>=', $dateFrom . ' 00:00:00');
        }

        $dateTo = FatApp::getPostedData('date_to', FatUtility::VAR_DATE, '');
        if (!empty($dateTo)) {
            $srch->addCondition('alh_logged_at', '<=', $dateTo . ' 23:59:59');
        }

        $recordId = FatApp::getPostedData('alh_id', FatUtility::VAR_INT, -1);
        if ($recordId > 0) {
            $srch->addCondition('alh_id', '=', $recordId);
        }

        $srch->setPageNumber($page);
        $srch->setPageSize($pageSize);
        $srch->addOrder($sortBy, $sortOrder);

        $records = FatApp::getDb()->fetchAll($srch->getResultSet());
        $this->set('arrListing', $records);
        $this->set('pageCount', $srch->pages());
        $this->set('recordCount', $srch->recordCount());
        $this->set('page', $page);
        $this->set('pageSize', $pageSize);
        $this->set('postedData', $post);
        $this->set('sortBy', $sortBy);
        $this->set('sortOrder', $sortOrder);
        $this->set('fields', $fields);
        $this->set('allowedKeysForSorting', $allowedKeysForSorting);
        $this->set('loginTypes', AdminLoginHistory::getLoginTypes());
        $this->checkEditPrivilege(true);
    }

    public function getSearchForm($fields = [])
    {
        $frm = new Form('frmRecordSearch');
        if (!empty($fields)) {
            $this->addSortingElements($frm, 'alh_logged_at', applicationConstants::SORT_DESC);
        }

        $fld = $frm->addTextBox(Labels::getLabel('FRM_KEYWORD', $this->siteLangId), 'keyword');
        $fld->overrideFldType('search');
        $frm->addSelectBox(
            Labels::getLabel('LBL_LOGIN_TYPE', $this->siteLangId),
            'login_type',
            ['-1' => Labels::getLabel('FRM_DOES_NOT_MATTER', $this->siteLangId)] + AdminLoginHistory::getLoginTypes(),
            -1,
            [],
            ''
        );
        $frm->addDateField(
            Labels::getLabel('FRM_DATE_FROM', $this->siteLangId),
            'date_from',
            '',
            [
                'placeholder' => Labels::getLabel('FRM_DATE_FROM', $this->siteLangId),
                'readonly' => 'readonly',
                'class' => 'small dateTimeFld field--calender',
            ]
        );
        $frm->addDateField(
            Labels::getLabel('FRM_DATE_TO', $this->siteLangId),
            'date_to',
            '',
            [
                'placeholder' => Labels::getLabel('FRM_DATE_TO', $this->siteLangId),
                'readonly' => 'readonly',
                'class' => 'small dateTimeFld field--calender',
            ]
        );
        HtmlHelper::addSearchButton($frm);
        HtmlHelper::addClearButton($frm);

        return $frm;
    }

    public function viewLog()
    {
        $recordId = FatApp::getPostedData('recordId', FatUtility::VAR_INT, 0);
        if (1 > $recordId) {
            LibHelper::exitWithError($this->str_invalid_request, true);
        }

        $srch = AdminLoginHistory::getSearchObject();
        $srch->addCondition('alh_id', '=', $recordId);
        $row = FatApp::getDb()->fetch($srch->getResultSet());

        if (false == $row) {
            LibHelper::exitWithError($this->str_invalid_request, true);
        }

        $this->set('loginTypes', AdminLoginHistory::getLoginTypes());
        $this->set('detail', $row);
        $this->set('html', $this->_template->render(false, false, null, true, false));
        $this->_template->render(false, false, 'json-success.php', true, false);
    }

    protected function getFormColumns(): array
    {
        $cacheKey = 'adminLoginHistoryTblHeadingCols' . $this->siteLangId;
        $cached = CacheHelper::get($cacheKey, CONF_DEF_CACHE_TIME, '.txt');
        if ($cached) {
            return json_decode($cached, true);
        }

        $arr = [
            'alh_admin_name' => Labels::getLabel('LBL_NAME', $this->siteLangId),
            'alh_admin_username' => Labels::getLabel('LBL_USERNAME', $this->siteLangId),
            'alh_ip' => Labels::getLabel('LBL_IP_ADDRESS', $this->siteLangId),
            'alh_browser' => Labels::getLabel('LBL_BROWSER', $this->siteLangId),
            'alh_platform' => Labels::getLabel('LBL_PLATFORM', $this->siteLangId),
            'alh_device' => Labels::getLabel('LBL_DEVICE', $this->siteLangId),
            'alh_login_type' => Labels::getLabel('LBL_LOGIN_TYPE', $this->siteLangId),
            'alh_logged_at' => Labels::getLabel('LBL_LOGGED_AT', $this->siteLangId),
            'action' => Labels::getLabel('LBL_ACTION_BUTTONS', $this->siteLangId),
        ];
        CacheHelper::create($cacheKey, json_encode($arr), CacheHelper::TYPE_LABELS);
        return $arr;
    }

    protected function getDefaultColumns(): array
    {
        return [
            'alh_admin_name',
            'alh_admin_username',
            'alh_ip',
            'alh_browser',
            'alh_platform',
            'alh_device',
            'alh_login_type',
            'alh_logged_at',
            'action',
        ];
    }

    protected function excludeKeysForSort($fields = []): array
    {
        return array_diff($fields, Common::excludeKeysForSort());
    }
}
