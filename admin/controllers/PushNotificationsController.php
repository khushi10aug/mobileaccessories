<?php

class PushNotificationsController extends ListingBaseController
{
    protected string $modelClass = 'PushNotification';
    protected string $pageKey = 'MANAGE_PUSH_NOTIFICATIONS';

    public function __construct($action)
    {
        parent::__construct($action);
        $this->objPrivilege->canViewPushNotification();
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
            $this->set("canEdit", $this->objPrivilege->canEditPushNotification($this->admin_id, true));
        } else {
            $this->objPrivilege->canEditPushNotification();
        }
    }

    private function validateRequest($recordId)
    {
        $recordId = FatUtility::int($recordId);
        $status = PushNotification::getAttributesById($recordId, 'pnotification_status');
        if (0 != $status) {
            LibHelper::exitWithError(Labels::getLabel("ERR_NOT_ALLOWED", $this->siteLangId), true);
        }
    }

    private function validatePlugin()
    {
        $active = (new Plugin())->getDefaultPluginData(Plugin::TYPE_PUSH_NOTIFICATION, 'plugin_active');
        if (false == $active || empty($active)) {
            LibHelper::exitWithError(Labels::getLabel("ERR_NO_DEFAULT_PUSH_NOTIFICATION_PLUGIN__FOUND", $this->siteLangId), false, true);
            FatApp::redirectUser(UrlHelper::generateUrl());
        }
    }

    public function index()
    {
        $this->validatePlugin();

        $fields = $this->getFormColumns();
        $frmSearch = $this->getSearchForm($fields);

        $pageData = PageLanguageData::getAttributesByKey($this->pageKey, $this->siteLangId);
        $pageTitle = $pageData['plang_title'] ?? LibHelper::getControllerName(true);

        $actionItemsData = HtmlHelper::getDefaultActionItems($fields);
        $this->set('pageData', $pageData);
        $this->set('pageTitle', $pageTitle);
        $this->set('actionItemsData', $actionItemsData);
        $this->set("frmSearch", $frmSearch);
        $this->set('defaultColumns', $this->getDefaultColumns());
        $this->set('keywordPlaceholder', Labels::getLabel('FRM_SEARCH_BY_NOTIFICATION_TITLE', $this->siteLangId));
        $this->getListingData();

        $this->_template->addJs(['js/cropper.js', 'js/cropper-main.js', 'js/tagify.min.js', 'js/tagify.polyfills.min.js', 'push-notifications/page-js/index.js','js/jquery.datetimepicker.js']);
        $this->_template->addCss(['css/cropper.css', 'css/tagify.min.css','css/jquery.datetimepicker.css']);

        $this->_template->render(true, true, '_partial/listing/index.php');
    }

    public function search()
    {
        $this->getListingData();
        $jsonData = [
            'listingHtml' => $this->_template->render(false, false, 'push-notifications/search.php', true),
            'paginationHtml' => $this->_template->render(false, false, '_partial/listing/listing-foot.php', true)
        ];
        LibHelper::exitWithSuccess($jsonData, true);
    }

    private function getListingData()
    {
        $this->checkEditPrivilege(true);

        $fields = $this->getFormColumns();
        $selectedFlds = FatApp::getPostedData('reportColumns', FatUtility::VAR_STRING, '');
        $selectedFlds = !empty($selectedFlds) ? json_decode($selectedFlds) +  $this->getDefaultColumns() : $this->getDefaultColumns();
        $fields =  FilterHelper::parseArrayByKeys($fields, $selectedFlds, true);

        $allowedKeysForSorting = $this->excludeKeysForSort(array_keys($fields));
        $sortBy = FatApp::getPostedData('sortBy', FatUtility::VAR_STRING, 'pnotification_status');
        if (!array_key_exists($sortBy, $fields)) {
            $sortBy = 'pnotification_status';
        }

        $sortOrder = applicationConstants::getSortOrder(FatApp::getPostedData('sortOrder', FatUtility::VAR_STRING));

        $srchFrm = $this->getSearchForm($fields);

        $postedData = FatApp::getPostedData();
        $post = $srchFrm->getFormDataFromArray($postedData);

        $page = FatApp::getPostedData('page', FatUtility::VAR_INT, 1);
        $page = ($page <= 0) ? 1 : $page;

        $pageSize = applicationConstants::getPageSize(FatApp::getPostedData('pageSize', FatUtility::VAR_INT));

        $srch = PushNotification::getSearchObject();
        $keyword = $post['keyword'];
        if (!empty($keyword)) {
            $srch->addCondition('pnotification_title', 'LIKE', '%' . $keyword . '%');
        }

        $status = $post['pnotification_status'];
        if ('' != $status && -1 < $status) {
            $srch->addCondition('pnotification_status', '=', $status);
        }

        $deviceType = $post['pnotification_device_os'];
        if ('' != $deviceType && -1 < $deviceType) {
            $srch->addCondition('pnotification_device_os', '=', $deviceType);
        }

        $authType = $post['pnotification_user_auth_type'];
        if ('' != $authType && -1 < $authType) {
            $srch->addCondition('pnotification_user_auth_type', '=', $authType);
        }

        $srch->addOrder($sortBy, $sortOrder);

        $srch->setPageNumber($page);
        $srch->setPageSize($pageSize);
        $rs = $srch->getResultSet();
        $records = FatApp::getDb()->fetchAll($rs);

        $this->set("arrListing", $records);
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

        $this->set('canView', $this->objPrivilege->canViewPushNotification($this->admin_id, true));
    }

    public function view()
    {
        $recordId = FatApp::getPostedData('recordId', FatUtility::VAR_INT, 0);
        if (1 > $recordId) {
            LibHelper::exitWithError($this->str_invalid_request_id, true);
        }

        $data = PushNotification::getAttributesById($recordId);
        if (empty($data)) {
            LibHelper::exitWithError($this->str_invalid_request_id, true);
        }

        $this->set('formTitle', Labels::getLabel('LBL_PUSH_NOTIFICATION_DETAIL', $this->siteLangId));
        $this->set('data', $data);
        $this->set('html', $this->_template->render(false, false, NULL, true));
        $this->_template->render(false, false, 'json-success.php', true, false);
    }

    public function form()
    {
        $this->objPrivilege->canEditPushNotification();
        $recordId = FatApp::getPostedData('recordId', FatUtility::VAR_INT, 0);
        $langId = FatApp::getPostedData('langId', FatUtility::VAR_INT, $this->siteLangId);
        $frm = $this->getForm($langId);
        $userAuthType = '';
        $isUsersSelected = false;
        if (0 < $recordId) {
            $data = PushNotification::getAttributesById($recordId);
            $userAuthType = $data['pnotification_user_auth_type'];
            $data['pnotification_lang_id'] = $langId;
            $frm->fill($data);

            $isUsersSelected = (0 < count($this->getSelectedUsers($recordId)));
        }
        $this->set('isUsersSelected', $isUsersSelected);
        $this->set('recordId', $recordId);
        $this->set('userAuthType', $userAuthType);
        $this->set('frm', $frm);
        $this->set('displayLangTab', false);
        $this->set('formTitle', Labels::getLabel('LBL_PUSH_NOTIFICATION_SETUP', $this->siteLangId));
        $this->set('formLayout', Language::getLayoutDirection($langId));
        $this->set('html', $this->_template->render(false, false, NULL, true));
        $this->_template->render(false, false, 'json-success.php', true, false);
    }

    public function clone()
    {
        $this->objPrivilege->canEditPushNotification();
        $recordId = FatApp::getPostedData('recordId', FatUtility::VAR_INT, 0);
        $langId = FatApp::getPostedData('langId', FatUtility::VAR_INT, $this->siteLangId);

        if (1 > $recordId) {
            LibHelper::exitWithError(Labels::getLabel("ERR_INVALID_REQUEST", $this->siteLangId), true);
        }

        $data = PushNotification::getAttributesById($recordId);
        unset($data['pnotification_id'], $data['pnotification_status'], $data['pnotification_uauth_last_access']);
        $db = FatApp::getDb();
        if (!$db->insertFromArray(PushNotification::DB_TBL, $data, true, array(), $data)) {
            LibHelper::exitWithError($db->getError(), true);
        }

        $recordId = $db->getInsertId();
        $data['pnotification_id'] = $recordId;

        $frm = $this->getForm($langId);
        $data['pnotification_lang_id'] = $langId;
        $frm->fill($data);
        $this->set('recordId', $recordId);
        $this->set('userAuthType', $data['pnotification_user_auth_type']);
        $this->set('frm', $frm);
        $this->set('displayLangTab', false);
        $this->set('formTitle', Labels::getLabel('LBL_PUSH_NOTIFICATION_SETUP', $this->siteLangId));
        $this->set('formLayout', Language::getLayoutDirection($langId));
        $this->set('html', $this->_template->render(false, false, 'push-notifications/form.php', true));
        $this->_template->render(false, false, 'json-success.php', true, false);
    }

    public function setup()
    {
        $langId = FatApp::getPostedData('pnotification_lang_id', FatUtility::VAR_INT, $this->siteLangId);
        $frm = $this->getForm($langId);
        $post = $frm->getFormDataFromArray(FatApp::getPostedData());
        if (false === $post) {
            LibHelper::exitWithError(current($frm->getValidationErrors()), true);
        }
        $recordId = FatUtility::int($post['pnotification_id']);

        $post['pnotification_type'] = PushNotification::TYPE_APP;
        $post['pnotification_for_buyer'] = applicationConstants::YES;
        $pushNotification = new PushNotification($recordId);
        $pushNotification->assignValues($post);
        if (!$pushNotification->save()) {
            LibHelper::exitWithError($pushNotification->getError(), true);
        }

        $recordId = $pushNotification->getMainTableRecordId();

        $json['msg'] = $this->str_setup_successful;
        $json['recordId'] = $recordId;
        $json['openMediaForm'] = true;
        FatUtility::dieJsonSuccess($json);
    }

    private function getForm($langId)
    {
        $langId = 1 > $langId ? $this->siteLangId : $langId;

        $frm = new Form('PushNotificationForm');
        $frm->addHiddenField('', 'pnotification_id');
        $fld = $frm->addSelectBox(Labels::getLabel('FRM_LANGUAGE', $langId), 'pnotification_lang_id', Language::getAllNames(), $langId, [], '');
        $fld->requirements()->setRequired(true);

        $userAuthType = $frm->addSelectBox(Labels::getLabel('FRM_USER_AUTH_TYPE', $langId), 'pnotification_user_auth_type', User::getUserAuthTypeArr($langId), '', [], Labels::getLabel('FRM_SELECT', $langId));
        $userAuthType->requirements()->setRequired(true);

        $dateFld = $frm->addDateTimeField(Labels::getLabel('FRM_SCHEDULE_DATE', $langId), 'pnotification_notified_on', date('Y-m-d H:00'), ['readonly' => 'readonly', 'class' => 'small']);
        $dateFld->requirements()->setRequired(true);

        $deviceType = $frm->addSelectBox(Labels::getLabel('FRM_DEVICE_TYPE', $langId), 'pnotification_device_os', User::getDeviceTypeArr($langId), '', [], Labels::getLabel('LBL_Select', $langId));
        $deviceType->requirements()->setRequired(true);

        $frm->addRequiredField(Labels::getLabel('FRM_TITLE', $langId), 'pnotification_title');
        $frm->addTextBox(Labels::getLabel('FRM_URL', $langId), 'pnotification_url');
        $fld = $frm->addTextArea(Labels::getLabel('FRM_BODY', $langId), 'pnotification_description');
        $fld->requirements()->setRequired(true);

        return $frm;
    }

    protected function getSearchForm($fields = [])
    {
        $frm = new Form('frmRecordSearch');
        $frm->addHiddenField('', 'page');
        if (!empty($fields)) {
            $this->addSortingElements($frm, 'coupon_active', applicationConstants::SORT_ASC);
        }

        $fld = $frm->addTextBox(Labels::getLabel('FRM_KEYWORD', $this->siteLangId), 'keyword');
        $fld->overrideFldType('search');

        $statusArr = [-1 => Labels::getLabel('FRM_DOES_NOT_MATTER', $this->siteLangId)] + PushNotification::getStatusArr($this->siteLangId);
        $frm->addSelectBox(Labels::getLabel('FRM_STATUS', $this->siteLangId), 'pnotification_status', $statusArr, '', array(), '');

        $authType = [-1 => Labels::getLabel('FRM_DOES_NOT_MATTER', $this->siteLangId)] + User::getUserAuthTypeArr($this->siteLangId);
        $frm->addSelectBox(Labels::getLabel('FRM_NOTIFICATION_FOR_(USERS)', $this->siteLangId), 'pnotification_user_auth_type', $authType, '', array(), '');

        $deviceTypeArr = [-1 => Labels::getLabel('FRM_DOES_NOT_MATTER', $this->siteLangId)] + User::getDeviceTypeArr($this->siteLangId);
        $frm->addSelectBox(Labels::getLabel('FRM_DEVICE_TYPE', $this->siteLangId), 'pnotification_device_os', $deviceTypeArr, '', array(), '');

        HtmlHelper::addSearchButton($frm);
        HtmlHelper::addClearButton($frm);/*clearBtn*/
        return $frm;
    }

    public function media($recordId)
    {
        $recordId = FatUtility::int($recordId);
        if (1 > $recordId) {
            LibHelper::exitWithError($this->str_invalid_request_id, true);
        }
        $data = PushNotification::getAttributesById($recordId, ['pnotification_status', 'pnotification_user_auth_type', 'pnotification_lang_id']);

        $this->objPrivilege->canEditPushNotification();
        $mediaFrm = $this->getMediaForm($recordId);
        $getNotificationDimensions = ImageDimension::getData(ImageDimension::TYPE_PUSH_NOTIFICATION,ImageDimension::VIEW_DEFAULT);
        $this->set('recordId', $recordId);
        $this->set('getNotificationDimensions', $getNotificationDimensions);
        $this->set('langId', $data['pnotification_lang_id']);
        $this->set('pNotificationId', $recordId);
        $this->set('userAuthType', $data['pnotification_user_auth_type']);
        $this->set('frm', $mediaFrm);
        $this->set('displayLangTab', false);
        $this->set('displayFooterButtons', false);
        $this->set('activeGentab', false);
        $this->set('formTitle', Labels::getLabel('LBL_PUSH_NOTIFICATION_SETUP', $this->siteLangId));
        $this->set('html', $this->_template->render(false, false, NULL, true));
        $this->_template->render(false, false, 'json-success.php', true, false);
    }

    public function images($recordId)
    {
        $this->checkEditPrivilege(true);

        $recordId = FatUtility::int($recordId);
        if (!$recordId) {
            LibHelper::exitWithError($this->str_invalid_request_id, true);
        }

        $images = AttachedFile::getMultipleAttachments(AttachedFile::FILETYPE_PUSH_NOTIFICATION_IMAGE, $recordId);
        $this->set('images', $images);
        $this->set('recordId', $recordId);
        $this->set('html', $this->_template->render(false, false, NULL, true));
        $this->_template->render(false, false, 'json-success.php', true, false);
    }

    private function getMediaForm($recordId)
    {
        $frm = new Form('frmPushNotificationMedia');

        $frm->addHiddenField('', 'pnotification_id', $recordId);
        $frm->addHiddenField('', 'file_type', AttachedFile::FILETYPE_PUSH_NOTIFICATION_IMAGE);
        $frm->addHiddenField('', 'min_width');
        $frm->addHiddenField('', 'min_height');

        $frm->addHtml('', 'pnotification_image', '');
        return $frm;
    }

    public function removeMedia($recordId)
    {
        $this->objPrivilege->canEditPushNotification();
        $recordId = FatUtility::int($recordId);
        $this->validateRequest($recordId);
        $fileHandlerObj = new AttachedFile();
        if (!$fileHandlerObj->deleteFile(AttachedFile::FILETYPE_PUSH_NOTIFICATION_IMAGE, $recordId)) {
            LibHelper::exitWithError($fileHandlerObj->getError(), true);
        }
        FatUtility::dieJsonSuccess(Labels::getLabel("LBL_SUCCESS", $this->siteLangId));
    }

    public function uploadMedia()
    {
        $this->objPrivilege->canEditPushNotification();
        $post = FatApp::getPostedData();

        if (empty($post)) {
            LibHelper::exitWithError(Labels::getLabel('ERR_INVALID_REQUEST_OR_FILE_NOT_SUPPORTED', $this->siteLangId), true);
        }
        $recordId = $post['pnotification_id'];
        $this->validateRequest($recordId);
        $file_type = FatApp::getPostedData('file_type', FatUtility::VAR_INT, 0);

        if (!$file_type) {
            LibHelper::exitWithError($this->str_invalid_request, true);
        }

        if ($file_type != AttachedFile::FILETYPE_PUSH_NOTIFICATION_IMAGE) {
            LibHelper::exitWithError($this->str_invalid_request, true);
        }

        if (!is_uploaded_file($_FILES['cropped_image']['tmp_name'])) {
            LibHelper::exitWithError(Labels::getLabel('ERR_PLEASE_SELECT_A_FILE', $this->siteLangId), true);
        }

        $fileHandlerObj = new AttachedFile();
        if (false === $fileHandlerObj->deleteFile($file_type, $recordId)) {
            LibHelper::exitWithError($fileHandlerObj->getError(), true);
        }

        if (!$res = $fileHandlerObj->saveImage($_FILES['cropped_image']['tmp_name'], $file_type, $recordId, 0, $_FILES['cropped_image']['name'], -1, true)) {
            LibHelper::exitWithError($fileHandlerObj->getError(), true);
        }

        $this->set('msg', Labels::getLabel('MSG_IMAGE_UPLOADED_SUCCESSFULLY', $this->siteLangId));
        $this->set('recordId', $recordId);
        $this->_template->render(false, false, 'json-success.php');
    }

    private function getSelectedUsers(int $recordId): array
    {
        $srch = PushNotification::getSearchObject(true);
        $srch->addMultipleFields(['pntu_user_id as id', 'CONCAT(user_name, " (", credential_username, ")") as value', $recordId . ' as recordId']);
        $srch->joinTable('tbl_users', 'INNER JOIN', 'pntu_user_id = tu.user_id', 'tu');
        $srch->joinTable('tbl_user_credentials', 'INNER JOIN', 'tu.user_id = tuc.credential_user_id', 'tuc');
        $srch->addCondition('pnotification_id', "=", $recordId);
        $rs = $srch->getResultSet();
        return FatApp::getDb()->fetchAll($rs);
    }

    public function notifyUsersForm($recordId)
    {
        $this->objPrivilege->canEditPushNotification();
        $recordId = FatUtility::int($recordId);
        if (1 > $recordId) {
            LibHelper::exitWithError(Labels::getLabel("ERR_INVALID_REQUEST", $this->siteLangId), true);
        }
        $data = PushNotification::getAttributesById($recordId, ['pnotification_status', 'pnotification_user_auth_type', 'pnotification_lang_id']);
        if (User::AUTH_TYPE_GUEST == $data['pnotification_user_auth_type']) {
            LibHelper::exitWithError(Labels::getLabel("ERR_NOT_ALLOWED_TO_ADD_USERS_FOR_GUESTS", $this->siteLangId), true);
        }

        $frm = $this->selectedUsersform($data['pnotification_status']);
        $frm->fill(['pnotification_id' => $recordId]);

        $records = $this->getSelectedUsers($recordId);
        if (!empty($records) && 0 < count($records)) {
            $frm->fill(['users' => json_encode($records)]);
        }
        $this->set('notifyTo', PushNotification::getAttributesById($recordId, ['pnotification_for_buyer', 'pnotification_for_seller']));
        $this->set('recordId', $recordId);
        $this->set('langId', $data['pnotification_lang_id']);
        $this->set('frm', $frm);
        $this->set('displayFooterButtons', false);
        $this->set('displayLangTab', false);
        $this->set('activeGentab', false);
        $this->set('formTitle', Labels::getLabel('LBL_PUSH_NOTIFICATION_SETUP', $this->siteLangId));
        $this->set('html', $this->_template->render(false, false, NULL, true));
        $this->_template->render(false, false, 'json-success.php', true, false);
    }

    private function selectedUsersform($status = 0)
    {
        $frm = new Form('PushNotificationUserForm', array('id' => 'PushNotificationUserForm'));
        $frm->addHiddenField('', 'pnotification_id');
        $frm->addTextBox(Labels::getLabel('FRM_SELECT_USER', $this->siteLangId), 'users', '', ['placeholder' => Labels::getLabel('FRM_TYPE_TO_SEARCH', $this->siteLangId)]);
        return $frm;
    }

    public function bindUser($recordId, $userId)
    {
        $this->objPrivilege->canEditPushNotification();
        $recordId = FatUtility::int($recordId);
        $this->validateRequest($recordId);
        $userId = FatUtility::int($userId);
        if (1 > $recordId || 1 > $userId) {
            LibHelper::exitWithError(Labels::getLabel("ERR_INVALID_REQUEST", $this->siteLangId), true);
        }
        $PushNotificationData = [
            'pntu_pnotification_id' => $recordId,
            'pntu_user_id' => $userId
        ];
        $db = FatApp::getDb();
        if (!$db->insertFromArray(PushNotification::DB_TBL_NOTIFICATION_TO_USER, $PushNotificationData, true, array(), $PushNotificationData)) {
            LibHelper::exitWithError($db->getError(), true);
        }
        FatUtility::dieJsonSuccess($this->str_update_record);
    }

    public function unlinkUser($recordId, $userId)
    {
        $this->objPrivilege->canEditPushNotification();
        $recordId = FatUtility::int($recordId);
        $this->validateRequest($recordId);
        $userId = FatUtility::int($userId);
        if (1 > $recordId || 1 > $userId) {
            LibHelper::exitWithError(Labels::getLabel("ERR_INVALID_REQUEST", $this->siteLangId), true);
        }
        $db = FatApp::getDb();
        if (!$db->deleteRecords(PushNotification::DB_TBL_NOTIFICATION_TO_USER, ['smt' => 'pntu_pnotification_id = ? AND pntu_user_id = ?', 'vals' => [$recordId, $userId]])) {
            LibHelper::exitWithError($db->getError(), true);
        }
        FatUtility::dieJsonSuccess($this->str_update_record);
    }

    protected function getFormColumns(): array
    {
        $tblHeadingCols = CacheHelper::get('pushNotificationsTblHeadingCols' . $this->siteLangId, CONF_DEF_CACHE_TIME, '.txt');
        if ($tblHeadingCols) {
            return json_decode($tblHeadingCols, true);
        }

        $arr = [
            /* 'listSerial' => Labels::getLabel('LBL_SR._NO', $this->siteLangId), */
            'pnotification_title' => Labels::getLabel('LBL_TITLE', $this->siteLangId),
            'pnotification_user_auth_type' => Labels::getLabel('FRM_NOTIFICATION_FOR_(USERS)', $this->siteLangId),
            'pnotification_device_os' => Labels::getLabel('LBL_DEVICE_TYPE', $this->siteLangId),
            'pnotification_notified_on' => Labels::getLabel('LBL_SCHEDULED_FOR', $this->siteLangId),
            'pnotification_status' => Labels::getLabel('LBL_STATUS', $this->siteLangId),
            'action' => Labels::getLabel('LBL_ACTION_BUTTONS', $this->siteLangId),
        ];
        CacheHelper::create('pushNotificationsTblHeadingCols' . $this->siteLangId, json_encode($arr), CacheHelper::TYPE_LABELS);
        return $arr;
    }

    protected function getDefaultColumns(): array
    {
        return [
            /* 'listSerial', */
            'pnotification_title',
            'pnotification_user_auth_type',
            'pnotification_device_os',
            'pnotification_notified_on',
            'pnotification_status',
            'action',
        ];
    }

    protected function excludeKeysForSort($fields = []): array
    {
        return array_diff($fields, Common::excludeKeysForSort());
    }
}
