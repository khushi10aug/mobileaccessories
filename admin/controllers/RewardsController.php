<?php

class RewardsController extends ListingBaseController
{
    protected $pageKey = 'MANAGE_USER_REWARDS';

    public function __construct($action)
    {
        parent::__construct($action);
        $this->set('canViewUsers', $this->objPrivilege->canViewUsers($this->admin_id, true));
    }

    public function index()
    {
        $fields = $this->getFormColumns();
        $frmSearch = $this->getSearchForm($fields);

        $pageData = PageLanguageData::getAttributesByKey($this->pageKey, $this->siteLangId);
        $pageTitle = $pageData['plang_title'] ?? LibHelper::getControllerName(true);

        $actionItemsData = HtmlHelper::getDefaultActionItems($fields);
        $actionItemsData['searchFrmTemplate'] = 'rewards/search-form.php';
        $actionItemsData['newRecordBtnAttrs'] = [
            'attr' => [
                'title' => Labels::getLabel('LBL_CREDIT', $this->siteLangId),
            ],
            'label' => '<svg class="svg btn-icon-start" width="18" height="18">
                            <use xlink:href="' . CONF_WEBROOT_URL . 'images/retina/sprite-actions.svg' . AttachedFile::setTimeParam(RELEASE_DATE) . '#add"></use>
                        </svg><span>' . Labels::getLabel('BTN_CREDIT', $this->siteLangId) . '</span>',
        ];
        $this->set('pageData', $pageData);
        $this->set('pageTitle', $pageTitle);
        $this->set('actionItemsData', $actionItemsData);
        $this->set("frmSearch", $frmSearch);
        $this->set('defaultColumns', $this->getDefaultColumns());
        $this->getListingData();

        $this->_template->addJs(array('js/select2.js', 'rewards/page-js/index.js'));
        $this->_template->addCss(array('css/select2.min.css'));
        $this->includeFeatherLightJsCss();
        $this->_template->render(true, true, '_partial/listing/index.php', false, false);
    }

    public function search()
    {
        $this->getListingData();
        $jsonData = [
            'listingHtml' => $this->_template->render(false, false, 'rewards/search.php', true),
            'paginationHtml' => $this->_template->render(false, false, '_partial/listing/listing-foot.php', true)
        ];
        LibHelper::exitWithSuccess($jsonData, true);
    }

    private function getListingData()
    {
        $fields = $this->getFormColumns();
        $selectedFlds = FatApp::getPostedData('reportColumns', FatUtility::VAR_STRING, '');
        $selectedFlds = !empty($selectedFlds) ? json_decode($selectedFlds) +  $this->getDefaultColumns() : $this->getDefaultColumns();
        $fields =  FilterHelper::parseArrayByKeys($fields, $selectedFlds, true);

        $allowedKeysForSorting = $this->excludeKeysForSort(array_keys($fields));
        $sortBy = FatApp::getPostedData('sortBy', FatUtility::VAR_STRING, 'urp_date_added');
        if (!array_key_exists($sortBy, $fields)) {
            $sortBy = 'urp_date_added';
        }

        $sortOrder = applicationConstants::getSortOrder(FatApp::getPostedData('sortOrder', FatUtility::VAR_STRING, applicationConstants::SORT_DESC), applicationConstants::SORT_DESC);

        $userId = FatApp::getPostedData('urp_user_id', FatUtility::VAR_INT, 0);
        $srchFrm = $this->getSearchForm($fields);

        $postedData = FatApp::getPostedData();
        $post = $srchFrm->getFormDataFromArray($postedData);
        $post['urp_user_id'] = $userId;

        $page = FatApp::getPostedData('page', FatUtility::VAR_INT, 1);
        $page = ($page <= 0) ? 1 : $page;

        $pageSize = applicationConstants::getPageSize(FatApp::getPostedData('pageSize', FatUtility::VAR_INT));

        $srch = new UserRewardSearch();
        $srch->joinUser();
        if (0 < $userId) {
            $srch->addCondition('urp.urp_user_id', '=', $userId);
        }
        $this->setRecordCount(clone $srch, $pageSize, $page, $post);
        $srch->doNotCalculateRecords();

        $srch->addMultipleFields(['urp.*', 'user_name', 'user_updated_on', 'user_id', 'credential_username', 'credential_email']);
        $srch->addOrder($sortBy, $sortOrder);
        $srch->setPageNumber($page);
        $srch->setPageSize($pageSize);
        $this->set("arrListing", FatApp::getDb()->fetchAll($srch->getResultSet()));
        $paginationArr = empty($postedData) ? $post : $postedData;
        $this->set('postedData', $paginationArr);
        $this->set('sortBy', $sortBy);
        $this->set('sortOrder', $sortOrder);
        $this->set('fields', $fields);
        $this->set('allowedKeysForSorting', $allowedKeysForSorting);
        $this->set('canEdit', $this->objPrivilege->canEditUsers($this->admin_id, true));
    }

    public function getSearchForm($fields = [])
    {
        $frm = new Form('frmRecordSearch');
        if (!empty($fields)) {
            $this->addSortingElements($frm, 'urp_date_added', applicationConstants::SORT_DESC);
        }

        $frm->addSelectBox(Labels::getLabel('FRM_USER', $this->siteLangId), 'urp_user_id', []);
        $frm->addHiddenField('', 'total_record_count');
        HtmlHelper::addSearchButton($frm);
        return $frm;
    }

    public function form()
    {
        $this->objPrivilege->canEditUsers();
        $frm = $this->getForm();
        $this->set('frm', $frm);
        $this->set('includeTabs', false);
        $this->set('formTitle', Labels::getLabel('LBL_USER_REWARDS_POINT_SETUP', $this->siteLangId));
        $this->set('html', $this->_template->render(false, false, NULL, true));
        $this->_template->render(false, false, 'json-success.php', true, false);
    }

    private function getForm()
    {
        $frm = new Form('frmUserRewardPoints');
        $fld = $frm->addSelectBox(Labels::getLabel('FRM_USER', $this->siteLangId), 'urp_user_id', []);
        $fld->requirements()->setRequired(true);
        $frm->addRequiredField(Labels::getLabel('FRM_POINTS', $this->siteLangId), 'urp_points')->requirements()->setIntPositive();
        $fld = $frm->addTextBox(Labels::getLabel('FRM_VALIDITY_IN_DAYS', $this->siteLangId), 'validity');
        $fld->requirements()->setIntPositive();
        $frm->addTextArea(Labels::getLabel('FRM_COMMENTS', $this->siteLangId), 'urp_comments')->requirements()->setRequired();
        $fld->htmlAfterField = '<span class="form-text text-muted">' . Labels::getLabel('FRM_LEAVE_THIS_FIELD_EMPTY_EVER_VALID_REWARD_POINTS.', $this->siteLangId) . '</span>';
        return $frm;
    }

    public function setup()
    {
        $this->objPrivilege->canEditUsers();
        $frm = $this->getForm();
        $post = $frm->getFormDataFromArray(FatApp::getPostedData());

        if (false === $post) {
            LibHelper::exitWithError(current($frm->getValidationErrors()), true);
        }

        $userId = FatApp::getPostedData('urp_user_id', FatUtility::VAR_INT, 0);
        if (1 > $userId) {
            LibHelper::exitWithError(Labels::getLabel('ERR_INVALID_USER', $this->siteLangId), true);
        }

        $userObj = new User($userId);
        $user = $userObj->getUserInfo(array('user_parent'), false, false);
        if (!$user || 0 < $user['user_parent']) {
            LibHelper::exitWithError($this->str_invalid_request, true);
        }

        $obj = new UserRewards();
        $post['urp_date_added'] = date('Y-m-d H:i:s');
        if (!empty($post['validity']) && $validity = FatUtility::int($post['validity'])) {
            $post['urp_date_expiry'] = date('Y-m-d H:i:s', strtotime("+$validity days"));
        }

        $post['urp_user_id'] = $userId;
        $obj->assignValues($post);
        if (!$obj->save()) {
            LibHelper::exitWithError($obj->getError(), true);
        }

        /* send email to user[ */
        $urpId = $obj->getMainTableRecordId();
        $emailObj = new EmailHandler();
        $emailObj->sendRewardPointsNotification($this->siteLangId, $urpId);
        /* ] */

        $this->set('userId', $userId);
        $this->set('msg', $this->str_setup_successful);
        $this->_template->render(false, false, 'json-success.php');
    }

    public function getComments()
    {
        $recordId = FatApp::getPostedData('recordId', FatUtility::VAR_INT, 0);
        $this->set('comments', UserRewards::getAttributesById($recordId, 'urp_comments'));
        $this->set('html', $this->_template->render(false, false, NULL, true));
        $this->_template->render(false, false, 'json-success.php', true, false);
    }

    protected function getFormColumns(): array
    {
        $rewardsTblHeadingCols = CacheHelper::get('rewardsTblHeadingCols' . $this->siteLangId, CONF_DEF_CACHE_TIME, '.txt');
        if ($rewardsTblHeadingCols) {
            return json_decode($rewardsTblHeadingCols, true);
        }

        $arr = [
            /* 'listSerial' => Labels::getLabel('LBL_SR._NO', $this->siteLangId), */
            'user_name' => Labels::getLabel('LBL_USER_NAME', $this->siteLangId),
            'urp_date_added' => Labels::getLabel('LBL_CREATED_ON', $this->siteLangId),
            'urp_date_expiry' => Labels::getLabel('LBL_VALID_TILL', $this->siteLangId),
            'urp_points' => Labels::getLabel('LBL_POINTS', $this->siteLangId),
            'action' => Labels::getLabel('LBL_ACTION_BUTTONS', $this->siteLangId),
        ];

        CacheHelper::create('rewardsTblHeadingCols' . $this->siteLangId, json_encode($arr), CacheHelper::TYPE_LABELS);
        return $arr;
    }

    protected function getDefaultColumns(): array
    {
        return [
            /* 'listSerial', */
            'user_name',
            'urp_date_added',
            'urp_date_expiry',
            'urp_points',
            'action',
        ];
    }

    public function deleteRecord()
    {
        $this->objPrivilege->canEditUsers();

        $recordId = FatApp::getPostedData('recordId', FatUtility::VAR_INT, 0);
        if ($recordId < 1) {
            LibHelper::exitWithError($this->str_invalid_request_id, true);
        }
        
        $rewardData = UserRewards::getAttributesById($recordId, ['urp_date_expiry', 'urp_points', 'urp_user_id']);
        if (false === $rewardData || (0 != $rewardData['urp_date_expiry'] && strtotime('now') > strtotime($rewardData['urp_date_expiry']))) {
            LibHelper::exitWithError($this->str_invalid_request_id, true);
        }

        if (UserRewards::isRewardPointUsed($recordId)) {
            LibHelper::exitWithError($this->str_invalid_request_id, true);
        }

        $db = FatApp::getDb();
        $db->startTransaction();
        $updateValues = array('urpbreakup_used' => 1, 'urpbreakup_used_date' => date('Y-m-d H:i:s'));
        $whr = array('smt' => 'urpbreakup_urp_id = ?', 'vals' => array($recordId));
        if (!$db->updateFromArray(UserRewardBreakup::DB_TBL, $updateValues, $whr)) {
            $db->rollbackTransaction();
            LibHelper::exitWithError(Labels::getLabel('ERR_UNABLE_TO_REVERT_REWARD_POINTS', $this->siteLangId));
        }

        $rewarPointArr = array(
            'urp_user_id' => $rewardData['urp_user_id'],
            'urp_points' => '-' . $rewardData['urp_points'],
            'urp_used_order_id' => 0,
            'urp_comments' => Labels::getLabel('LBL_REWARD_POINT_DEDUCTED_BY_ADMIN', $this->siteLangId),
            'urp_date_added' => date('Y-m-d H:i:s')
        );

        $userRewardsObj = new UserRewards();
        $userRewardsObj->assignValues($rewarPointArr);

        if (!$userRewardsObj->save(false)) {
            $db->rollbackTransaction();
            LibHelper::exitWithError(Labels::getLabel('ERR_UNABLE_TO_REVERT_REWARD_POINTS', $this->siteLangId));
        }
        $db->commitTransaction();
        $emailObj = new EmailHandler();
        $emailObj->sendRewardPointsNotification($this->siteLangId, $userRewardsObj->getMainTableRecordId());

        $this->set('msg', Labels::getLabel('MSG_REWARD_POINT_REVERTED_SUCCESFULLY', $this->siteLangId));
        $this->_template->render(false, false, 'json-success.php');
    }

    protected function excludeKeysForSort($fields = []): array
    {
        return array_diff($fields, ['urp_comments'], Common::excludeKeysForSort());
    }
}
