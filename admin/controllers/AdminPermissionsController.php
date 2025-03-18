<?php

class AdminPermissionsController extends ListingBaseController
{
    public function __construct($action)
    {
        parent::__construct($action);
        $this->objPrivilege->canViewAdminPermissions();
    }

    public function index()
    {
        Message::addErrorMessage(Labels::getLabel('ERR_PLEASE_ADMIN_USER_FIRST', $this->siteLangId));
        FatApp::redirectUser(UrlHelper::generateUrl('AdminUsers'));
    }

    public function list(int $recordId)
    {
        $data = AdminUsers::getAttributesById($recordId);
        if ($data === false) {
            Message::addErrorMessage($this->str_invalid_request_id);
            FatApp::redirectUser(UrlHelper::generateUrl('AdminUsers'));
        }
        $pageTitle = !empty($data['admin_name']) ? $data['admin_name'] : $data['admin_username'];

        $fields = $this->getFormColumns();
        $frmSearch = $this->getSearchForm($fields);
        $frmSearch->fill(['admin_id' => $recordId]);

        $pageData = PageLanguageData::getAttributesByKey('MANAGE_ADMIN_PERMISSIONS', $this->siteLangId);

        $actionItemsData = HtmlHelper::getDefaultActionItems($fields);
        $actionItemsData['newRecordBtn'] = false;
        $actionItemsData['performBulkAction'] = true;
        $actionItemsData['bulkActionFormHiddenFields'] = [
            'admin_id' => $recordId,
            'permission' => -1,
        ];
        $actionItemsData['otherButtons'] = [
            [
                'attr' => [
                    'href' => 'javascript:void(0)',
                    'class' => 'btn btn-outline-gray btn-icon toolbarBtnJs disabled',
                    'onclick' => "updateBulkPermissions(" . AdminPrivilege::PRIVILEGE_NONE . ")",
                    'title' => Labels::getLabel('LBL_NO_PERMISSION', $this->siteLangId)
                ],
                'label' => '<svg class="svg btn-icon-start" width="18" height="18">
                                <use
                                    xlink:href="' . CONF_WEBROOT_URL . 'images/retina/sprite.yokart.svg' . AttachedFile::setTimeParam(RELEASE_DATE) . '#not-allowed">
                                </use>
                            </svg><span>' . Labels::getLabel('BTN_NONE', $this->siteLangId) . '</span>',
            ],
            [
                'attr' => [
                    'href' => 'javascript:void(0)',
                    'class' => 'btn btn-outline-gray btn-icon toolbarBtnJs disabled',
                    'onclick' => "updateBulkPermissions(" . AdminPrivilege::PRIVILEGE_READ . ")",
                    'title' => Labels::getLabel('LBL_READ_PERMISSION', $this->siteLangId)
                ],
                'label' => '<svg class="svg btn-icon-start" width="18" height="18">
                                <use
                                    xlink:href="' . CONF_WEBROOT_URL . 'images/retina/sprite-actions.svg' . AttachedFile::setTimeParam(RELEASE_DATE) . '#view">
                                </use>
                            </svg><span>' . Labels::getLabel('BTN_READ', $this->siteLangId) . '</span>',
            ],
            [
                'attr' => [
                    'href' => 'javascript:void(0)',
                    'class' => 'btn btn-outline-gray btn-icon toolbarBtnJs disabled',
                    'onclick' => "updateBulkPermissions(" . AdminPrivilege::PRIVILEGE_WRITE . ")",
                    'title' => Labels::getLabel('LBL_READ_AND_WRITE_PERMISSION', $this->siteLangId)
                ],
                'label' => '<svg class="svg btn-icon-start" width="18" height="18">
                                <use
                                    xlink:href="' . CONF_WEBROOT_URL . 'images/retina/sprite-actions.svg' . AttachedFile::setTimeParam(RELEASE_DATE) . '#edit">
                                </use>
                            </svg><span>' . Labels::getLabel('BTN_READ_AND_WRITE', $this->siteLangId) . '</span>',
            ],
        ];

        $this->set('pageData', $pageData);
        $this->set("frmSearch", $frmSearch);
        $this->set('pageTitle', $pageTitle);
        $this->set('actionItemsData', $actionItemsData);
        $this->set('defaultColumns', $this->getDefaultColumns());
        $this->set('keywordPlaceholder', Labels::getLabel('FRM_SEARCH_BY_MODULE_NAME', $this->siteLangId));
        $this->getListingData($recordId);
        $this->_template->addJs(['admin-permissions/page-js/list.js']);
        $this->_template->render(true, true, '_partial/listing/index.php');
    }

    public function search()
    {
        $recordId = FatApp::getPostedData('admin_id', FatUtility::VAR_INT, 0);
        $this->getListingData($recordId);

        $jsonData = [
            'listingHtml' => $this->_template->render(false, false, 'admin-permissions/search.php', true),
            'paginationHtml' => $this->_template->render(false, false, '_partial/listing/listing-foot.php', true)
        ];
        LibHelper::exitWithSuccess($jsonData, true);
    }

    private function getListingData(int $recordId)
    {
        $recordId = FatApp::getPostedData('admin_id', FatUtility::VAR_INT, $recordId);

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

        if (empty($post['permission_type'])) {
            $post['permission_type'] = -1;
        }
        $post['admin_id'] = $recordId;

        $pageSize = applicationConstants::getPageSize(FatApp::getPostedData('pageSize', FatUtility::VAR_INT));
        $keyword = FatApp::getPostedData('keyword', FatUtility::VAR_STRING, '');
        $permissionType = FatApp::getPostedData('permission_type', FatUtility::VAR_INT, -1);

        $arrListing = AdminPrivilege::getPermissionModulesArr();
        if (!empty($keyword)) {
            $keyword = str_replace('\*', '.*?', preg_quote(trim($keyword), '/'));
            $result = preg_grep('/' . $keyword . '/i', $arrListing);
            $arrListing = array_intersect($arrListing, $result);
        }

        $sortOrder = applicationConstants::getSortOrder(FatApp::getPostedData('sortOrder', FatUtility::VAR_STRING));
        switch ($sortOrder) {
            case applicationConstants::SORT_ASC:
                asort($arrListing);
                break;
            case applicationConstants::SORT_DESC:
                arsort($arrListing);
                break;
            default:
                asort($arrListing);
                break;
        }
        $userData = [];
        if ($recordId > 0) {
            $userData = AdminUsers::getUserPermissions($recordId);
            if (-1 < $permissionType) {
                foreach ($userData as $userPerm) {
                    if ($userPerm['admperm_value'] != $permissionType) {
                        unset($arrListing[$userPerm['admperm_section_id']]);
                    }
                }
            }
        }
        $this->set("arrListing", $arrListing);
        $this->set("hidePaginationHtml", true);
        $this->set('page', 1);
        $this->set('pageSize', $pageSize);
        $this->set('recordCount', count($arrListing));

        $paginationArr = empty($postedData) ? $post : $postedData;
        $this->set('postedData', $paginationArr);

        $this->set('sortBy', $sortBy);
        $this->set('sortOrder', $sortOrder);
        $this->set('fields', $fields);
        $this->set('allowedKeysForSorting', $allowedKeysForSorting);
        $this->set('recordId', $recordId);
        $this->set('userData', $userData);
        $this->set('canView', $this->objPrivilege->canViewAdminPermissions($this->admin_id, true));
    }

    public function getSearchForm($fields = [])
    {
        $fields = $this->getFormColumns();

        $frm = new Form('frmRecordSearch');
        $frm->addHiddenField('', 'page');
        $frm->addHiddenField('', 'admin_id');
        $fld = $frm->addTextBox(Labels::getLabel('FRM_KEYWORD', $this->siteLangId), 'keyword');
        $fld->overrideFldType('search');

        $frm->addSelectBox(Labels::getLabel('FRM_PERMISSION_TYPE', $this->siteLangId), 'permission_type', [-1 => Labels::getLabel('FRM_PERMISSION_TYPE', $this->siteLangId)] + AdminPrivilege::getPermissionArr(), -1, [], '');

        if (!empty($fields)) {
            $this->addSortingElements($frm, 'module');
        }

        HtmlHelper::addSearchButton($frm);
        HtmlHelper::addClearButton($frm);/*clearBtn*/
        return $frm;
    }

    public function updateBulkPermissions()
    {
        $this->objPrivilege->canEditAdminPermissions();

        $permission = FatApp::getPostedData('permission', FatUtility::VAR_INT, -1);
        $postedModulesArr = FatUtility::int(FatApp::getPostedData('record_ids'));
        if (empty($postedModulesArr) || -1 == $permission) {
            LibHelper::exitWithError($this->str_invalid_request, true);
        }

        $modulesArr = AdminPrivilege::getPermissionModulesArr();
        if (count($modulesArr) == count($postedModulesArr)) {
            $this->changePermission(0, $permission);
        } else {
            foreach ($postedModulesArr as $moduleId) {
                if (1 > $moduleId) {
                    continue;
                }
                $this->changePermission($moduleId, $permission);
            }
        }

        $this->set('msg', Labels::getLabel('LBL_RECORDS_UPDATED_SUCCESSFULLY.'));
        $this->_template->render(false, false, 'json-success.php');
    }

    public function updatePermission($moduleId, $permission)
    {
        $this->objPrivilege->canEditAdminPermissions();

        $moduleId = FatUtility::int($moduleId);
        $permission = FatUtility::int($permission);

        $this->changePermission($moduleId, $permission);

        $this->set('msg', $this->str_update_record);
        $this->set('moduleId', $moduleId);
        $this->_template->render(false, false, 'json-success.php');
    }

    private function changePermission(int $moduleId, int $permission)
    {
        $frmSearch = $this->getSearchForm();
        $post = $frmSearch->getFormDataFromArray(FatApp::getPostedData());

        $recordId = FatUtility::int($post['admin_id']);

        if (2 > $recordId || $recordId == $this->admin_id) {
            LibHelper::exitWithError($this->str_invalid_request_id, true);
        }
        $data = array(
            'admperm_admin_id' => $recordId,
            'admperm_section_id' => $moduleId,
            'admperm_value' => $permission,
        );
        $obj = new AdminUsers();
        if ($moduleId == 0) {
            if (!$obj->updatePermissions($data, true)) {
                LibHelper::exitWithError($obj->getError(), true);
            }
        } else {
            $permissionModules = AdminPrivilege::getPermissionModulesArr();
            $permissionArr = AdminPrivilege::getPermissionArr();
            if (!array_key_exists($moduleId, $permissionModules) || !array_key_exists($permission, $permissionArr)) {
                LibHelper::exitWithError($this->str_invalid_request, true);
            }
            if (!$obj->updatePermissions($data)) {
                LibHelper::exitWithError($obj->getError(), true);
            }
        }

        $obj = new AdminUsers($recordId);
        $obj->assignValues(['admin_admperm_updated_on' => date('Y-m-d H:i:s')]);
        $obj->save();
    }

    protected function getFormColumns(): array
    {
        $tblHeadingCols = CacheHelper::get('adminUsersPermissionTblHeadingCols' . $this->siteLangId, CONF_DEF_CACHE_TIME, '.txt');
        if ($tblHeadingCols) {
            return json_decode($tblHeadingCols, true);
        }

        $arr = [
            'select_all' => Labels::getLabel('LBL_SELECT_ALL', $this->siteLangId),
            /*  'listSerial' => Labels::getLabel('LBL_#', $this->siteLangId), */
            'module' => Labels::getLabel('LBL_MODULE', $this->siteLangId),
            'action' => Labels::getLabel('LBL_PERMISSIONS', $this->siteLangId),
        ];

        CacheHelper::create('adminUsersPermissionTblHeadingCols' . $this->siteLangId, json_encode($arr), CacheHelper::TYPE_LABELS);
        return $arr;
    }

    protected function getDefaultColumns(): array
    {
        return [
            'select_all',
            /* 'listSerial', */
            'module',
            'action'
        ];
    }

    protected function excludeKeysForSort($fields = []): array
    {
        return array_diff($fields, ['permission'], Common::excludeKeysForSort());
    }

    public function getBreadcrumbNodes($action)
    {
        $pageData = PageLanguageData::getAttributesByKey('MANAGE_ADMIN_PERMISSIONS', $this->siteLangId);
        $pageTitle = $pageData['plang_title'] ?? Labels::getLabel('LBL_PERMISSIONS', $this->siteLangId);

        $url = FatApp::getQueryStringData('url');
        $urlParts = explode('/', $url);
        $title = Labels::getLabel('LBL_LIST', $this->siteLangId);
        if (isset($urlParts[2])) {
            $data = AdminUsers::getAttributesById($urlParts[2]);
            if ($data === false) {
                Message::addErrorMessage($this->str_invalid_request_id);
                FatApp::redirectUser(UrlHelper::generateUrl('AdminUsers'));
            }
            $title = !empty($data['admin_name']) ? $data['admin_name'] : $data['admin_username'];
        }

        switch ($action) {
            case 'list':
                $this->nodes = [
                    ['title' => Labels::getLabel('LBL_ADMIN_USERS', $this->siteLangId), 'href' => UrlHelper::generateUrl('AdminUsers')],
                    ['title' => $pageTitle],
                    ['title' => $title]
                ];
        }
        return $this->nodes;
    }
}
