<?php

class UsersController extends ListingBaseController
{
    public function __construct($action)
    {
        parent::__construct($action);
        $this->objPrivilege->canViewUsers();
    }

    public function index()
    {
        $fields = $this->getFormColumns();
        $frmSearch = $this->getUserSearchForm($fields);

        $pageData = PageLanguageData::getAttributesByKey('MANAGE_USERS', $this->siteLangId);
        $pageTitle = $pageData['plang_title'] ?? LibHelper::getControllerName(true);

        $actionItemsData = HtmlHelper::getDefaultActionItems($fields);
        $actionItemsData['searchFrmTemplate'] = 'users/search-form.php';
        $actionItemsData['performBulkAction'] = true;
        $actionItemsData['statusButtons'] = true;
        $actionItemsData['deleteButton'] = true;

        $this->set('pageData', $pageData);
        $this->set('pageTitle', $pageTitle);
        $this->set('actionItemsData', $actionItemsData);
        $this->set("frmSearch", $frmSearch);
        $this->set('defaultColumns', $this->getDefaultColumns());
        $this->set('keywordPlaceholder', Labels::getLabel('FRM_SEARCH_BY_USER_NAME', $this->siteLangId));
        $this->getListingData();

        $this->_template->addJs(array('js/select2.js', 'users/page-js/index.js'));
        $this->_template->addCss(array('css/select2.min.css'));
        $this->includeFeatherLightJsCss();
        $this->_template->render(true, true, '_partial/listing/index.php');
    }

    public function search()
    {
        $this->getListingData();
        $jsonData = [
            'listingHtml' => $this->_template->render(false, false, 'users/search.php', true),
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
        $sortBy = FatApp::getPostedData('sortBy', FatUtility::VAR_STRING, current($allowedKeysForSorting));
        if (!array_key_exists($sortBy, $fields)) {
            $sortBy = current($allowedKeysForSorting);
        }

        $sortOrder = applicationConstants::getSortOrder(FatApp::getPostedData('sortOrder', FatUtility::VAR_STRING), applicationConstants::SORT_DESC);

        $page = FatApp::getPostedData('page', FatUtility::VAR_INT, 1);
        $page = ($page <= 0) ? 1 : $page;

        $pageSize = applicationConstants::getPageSize(FatApp::getPostedData('pageSize', FatUtility::VAR_INT));

        $searchForm = $this->getUserSearchForm($fields);
        $postedData = FatApp::getPostedData();
        $post = $searchForm->getFormDataFromArray($postedData);

        $userObj = new User();
        $srch = $userObj->getUserSearchObj(null, true);
        $srch->addFld(User::DB_TBL_CRED_PREFIX . 'password');
        $srch->joinTable(Shop::DB_TBL, 'LEFT OUTER JOIN', 'user_id = shop.shop_user_id OR user_parent = shop.shop_user_id', 'shop');
        $srch->joinTable(Shop::DB_TBL_LANG, 'LEFT OUTER JOIN', 'shop.shop_id = s_l.shoplang_shop_id AND shoplang_lang_id = ' . $this->siteLangId, 's_l');

        $recordId = FatApp::getPostedData('recordId', FatUtility::VAR_INT, -1);
        $userId = FatApp::getPostedData('user_id', FatUtility::VAR_INT, $recordId);
        if (0 < $userId) {
            $srch->addCondition('user_id', '=', $userId);
        }

        $userActive = FatApp::getPostedData('user_active');
        if ('' != $userActive && -1 < $userActive) {
            $srch->addCondition('uc.credential_active', '=', $userActive);
        }

        $userVerified = FatApp::getPostedData('user_verified');
        if ('' != $userVerified && -1 < $userVerified) {
            $srch->addCondition('uc.credential_verified', '=', $userVerified);
        }

        $type = FatApp::getPostedData('type', FatUtility::VAR_STRING, 0);

        switch ($type) {
            case User::USER_TYPE_SELLER:
                $srch->addCondition('u.user_is_supplier', '=', applicationConstants::YES);
                $srch->addCondition('u.user_parent', '=', 0);
                break;
            case User::USER_TYPE_BUYER:
                $srch->addCondition('u.user_is_buyer', '=', applicationConstants::YES);
                break;
            case User::USER_TYPE_ADVERTISER:
                $srch->addCondition('u.user_is_advertiser', '=', applicationConstants::YES);
                $srch->addCondition('u.user_parent', '=', 0);
                break;
            case User::USER_TYPE_AFFILIATE:
                $srch->addCondition('u.user_is_affiliate', '=', applicationConstants::YES);
                break;
            case User::USER_TYPE_SUB_USER:
                $srch->addCondition('u.user_parent', '>', 0);
                break;
            case User::USER_TYPE_BUYER_SELLER:
                $srch->addCondition('u.user_is_supplier', '=', applicationConstants::YES);
                $srch->addCondition('u.user_is_buyer', '=', applicationConstants::YES);
                break;
        }

        $srch->addCondition('u.user_is_shipping_company', '=', applicationConstants::NO);

        $user_regdate_from = FatApp::getPostedData('user_regdate_from', FatUtility::VAR_DATE, '');
        if (!empty($user_regdate_from)) {
            $srch->addCondition('user_regdate', '>=', $user_regdate_from . ' 00:00:00');
        }

        $user_regdate_to = FatApp::getPostedData('user_regdate_to', FatUtility::VAR_DATE, '');
        if (!empty($user_regdate_to)) {
            $srch->addCondition('user_regdate', '<=', $user_regdate_to . ' 23:59:59');
        }

        $this->setRecordCount(clone $srch, $pageSize, $page, $post);
        $srch->doNotCalculateRecords();

        $srch->addMultipleFields(array('user_id', 'user_name', 'user_phone_dcode', 'user_phone', 'user_profile_info', 'user_regdate', 'user_is_buyer', 'user_parent', 'credential_username', 'credential_email', 'credential_active', 'credential_verified', 'shop_id', 'shop_user_id', 'IFNULL(shop_name, shop_identifier) as shop_name', 'user_is_buyer', 'user_is_supplier', 'user_is_advertiser', 'user_is_affiliate', 'user_registered_initially_for', 'user_updated_on', 'shop_updated_on'));
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
        $this->set('canVerify', $this->objPrivilege->canVerifyUsers($this->admin_id, true));
        $this->set('canViewShops', $this->objPrivilege->canViewShops($this->admin_id, true));
    }

    public function form()
    {
        $this->objPrivilege->canEditUsers();
        $recordId = FatApp::getPostedData('recordId', FatUtility::VAR_INT, 0);
        $frm = $this->getForm($recordId);
        $userParent = 0;
        $stateId = 0;
        if (0 < $recordId) {
            $userObj = new User($recordId);
            $srch = $userObj->getUserSearchObj();
            $srch->addMultipleFields(array('u.*'));
            $rs = $srch->getResultSet();

            if (!$rs) {
                LibHelper::exitWithError($this->str_invalid_request, true);
            }

            $data = FatApp::getDb()->fetch($rs, 'user_id');

            if ($data === false) {
                LibHelper::exitWithError($this->str_invalid_request, true);
            }

            $stateId = $data['user_state_id'];
            $frm->fill($data);
            $userParent = $data['user_parent'];
            $this->set('data', $data);
        }
        $this->set('userParent', $userParent);
        $this->set('recordId', $recordId);
        $this->set('stateId', $stateId);
        $this->set('displayLangTab', false);
        $this->set('frm', $frm);
        $this->set('html', $this->_template->render(false, false, NULL, true));
        $this->_template->render(false, false, 'json-success.php', true, false);
    }

    public function markVerified()
    {
        $this->objPrivilege->canEditUsers();
        $recordId = FatApp::getPostedData('userId', FatUtility::VAR_INT, 0);

        $where = array('smt' => 'credential_user_id = ?', 'vals' => [$recordId]);
        if (!FatApp::getDb()->updateFromArray(User::DB_TBL_CRED, ['credential_verified' => applicationConstants::YES], $where)) {
            LibHelper::exitWithError(FatApp::getDb()->getError(), true);
        }

        $this->set('msg', $this->str_update_record);
        $this->_template->render(false, false, 'json-success.php');
    }

    public function setup()
    {
        $this->objPrivilege->canEditUsers();
        $frm = $this->getForm(FatApp::getPostedData('user_id', FatUtility::VAR_INT, 0));

        $post = FatApp::getPostedData();
        $user_state_id = FatUtility::int($post['user_state_id']);
        if (CommonHelper::isFieldEncrypted($post['user_dob']) == true) {
            unset($post['user_dob']);
        }
        if (CommonHelper::isFieldEncrypted($post['user_phone']) == true) {
            unset($post['user_phone']);
        }
        $post = $frm->getFormDataFromArray($post);
        if (false === $post) {
            LibHelper::exitWithError(current($frm->getValidationErrors()), true);
        }
        $post['user_state_id'] = $user_state_id;

        $post['user_phone_dcode'] = FatApp::getPostedData('user_phone_dcode', FatUtility::VAR_STRING, '');

        $recordId = FatUtility::int($post['user_id']);
        unset($post['user_id']);
        if (0 < $recordId) {
            unset($post['credential_username']);
            unset($post['credential_email']);
            if ($post['user_dob'] == "0000-00-00" || $post['user_dob'] == "" || strtotime($post['user_dob']) == 0) {
                unset($post['user_dob']);
            }
        }

        /* [ new user    */
        if (1 > $recordId) {
            $post['user_verify'] = FatApp::getConfig('CONF_EMAIL_VERIFICATION_REGISTRATION', FatUtility::VAR_INT, 1) ? 0 : 1;
            if ($post['user_type'] == User::USER_TYPE_BUYER) {
                $post['user_is_buyer'] = 1;
                $post['user_preferred_dashboard'] = User::USER_BUYER_DASHBOARD;
                $post['user_registered_initially_for'] = User::USER_TYPE_BUYER;
                $post['user_is_supplier'] = (FatApp::getConfig("CONF_ADMIN_APPROVAL_SUPPLIER_REGISTRATION", FatUtility::VAR_INT, 1) || FatApp::getConfig("CONF_ACTIVATE_SEPARATE_SIGNUP_FORM", FatUtility::VAR_INT, 1)) ? 0 : 1;
                $post['user_is_advertiser'] = (FatApp::getConfig("CONF_ADMIN_APPROVAL_SUPPLIER_REGISTRATION", FatUtility::VAR_INT, 1) || FatApp::getConfig("CONF_ACTIVATE_SEPARATE_SIGNUP_FORM", FatUtility::VAR_INT, 1)) ? 0 : 1;
            } elseif ($post['user_type'] == User::USER_TYPE_SELLER) {
                $post['user_is_buyer'] = 1;
                $post['user_is_supplier'] = 1;
                $post['user_preferred_dashboard'] = User::USER_SELLER_DASHBOARD;
                $post['user_registered_initially_for'] = User::USER_TYPE_SELLER;
                if (FatApp::getConfig("CONF_ACTIVATE_SEPARATE_SIGNUP_FORM", FatUtility::VAR_INT, 1)) {
                    $post['user_is_buyer'] = 0;
                }
            } elseif ($post['user_type'] == User::USER_TYPE_AFFILIATE) {
                $post['user_is_affiliate'] = 1;
                $post['user_registered_initially_for'] = User::USER_TYPE_AFFILIATE;
                $post['user_preferred_dashboard'] = User::USER_AFFILIATE_DASHBOARD;
            } elseif ($post['user_type'] == User::USER_TYPE_ADVERTISER) {
                $post['user_is_advertiser'] = 1;
                $post['user_registered_initially_for'] = User::USER_TYPE_ADVERTISER;
                $post['user_preferred_dashboard'] = User::USER_ADVERTISER_DASHBOARD;
            }
        }
        /* new user ]   */
        $db = FatApp::getDb();
        $db->startTransaction();
        $userObj = new User($recordId);
        $userObj->assignValues($post);
        if (!$userObj->save()) {
            $db->rollbackTransaction();
            LibHelper::exitWithError($userObj->getError(), true);
        }
                /* [ new user    */
        if (1 > $recordId) {
            if (!$userObj->setLoginCredentials($post['credential_username'], $post['credential_email'], null, applicationConstants::ACTIVE, $post['user_verify'])) {
                $db->rollbackTransaction();
                return false;
            }

            $userData = [
                'user_name' => $post['user_name'],
                'user_email' => $post['credential_email'],
                'user_id' => $userObj->getMainTableRecordId(),
                'account_type' => User::getUserTypesArr($this->siteLangId)[$post['user_type']]
            ];

            if (!$userObj->sendAdminNewUserCreationEmail($userData, $this->admin_id)) {
                $db->rollbackTransaction();
                LibHelper::exitWithError(Labels::getLabel("ERR_ERROR_IN_SENDING_WELCOME_EMAIL", $this->admin_id), true);
                return false;
            }
            if (!empty($post['credential_email'])) {
                if (!$userObj->assignGiftCard($post['credential_email'])) {
                    $db->rollbackTransaction();
                    LibHelper::exitWithError(Labels::getLabel('MSG_USER_COULD_NOT_BE_SET'));
                    return false;
                }
            }

            if (!empty($post['credential_email'])) {
                if (!$userObj->assignGiftCard($post['credential_email'])) {
                    $db->rollbackTransaction();
                    LibHelper::exitWithError(Labels::getLabel('MSG_USER_COULD_NOT_BE_SET'));
                    return false;
                }
            }
        }
        /*  new user ] */

        $db->commitTransaction();

        $this->set('recordId', $userObj->getMainTableRecordId());
        $this->set('msg', $this->str_setup_successful);
        $this->_template->render(false, false, 'json-success.php');
    }

    public function deleteRecord()
    {
        $this->objPrivilege->canEditUsers();
        $post = FatApp::getPostedData();
        if ($post == false) {
            LibHelper::exitWithError($this->str_invalid_request, true);
        }

        $recordId = FatUtility::int($post['recordId']);
        if (1 > $recordId) {
            LibHelper::exitWithError($this->str_invalid_request_id, true);
        }
        $this->markAsDeleted($recordId);
        $shopId = Shop::getAttributesByUserId($recordId, 'shop_id');
        if (0 < $shopId) {
            Product::updateMinPrices(0, $shopId);
        }

        $this->set('msg', Labels::getLabel('LBL_RECORD_DELETED_SUCCESSFULLY.'));
        $this->_template->render(false, false, 'json-success.php');
    }

    public function deleteSelected()
    {
        $this->objPrivilege->canEditUsers();
        $recordIdsArr = FatUtility::int(FatApp::getPostedData('user_ids'));

        if (empty($recordIdsArr)) {
            LibHelper::exitWithError($this->str_invalid_request, true);
        }

        foreach ($recordIdsArr as $recordId) {
            if (1 > $recordId) {
                continue;
            }
            $this->markAsDeleted($recordId);
        }
        Product::updateMinPrices();
        $this->set('msg', Labels::getLabel('MSG_RECORDS_DELETED_SUCCESSFULLY', $this->siteLangId));
        $this->_template->render(false, false, 'json-success.php');
    }

    protected function markAsDeleted($recordId)
    {
        $recordId = FatUtility::int($recordId);
        if (1 > $recordId) {
            LibHelper::exitWithError($this->str_invalid_request, true);
        }
        $userObj = new User($recordId);
        $userObj->assignValues(array('user_deleted' => applicationConstants::YES));
        if (!$userObj->save()) {
            LibHelper::exitWithError($userObj->getError(), true);
        }
    }

    private function getForm(int $recordId = 0)
    {
        $frm = new Form('frmUser');
        $frm->addHiddenField('', 'user_id', $recordId);

        if (1 > $recordId) {
            $userTypesArr = User::getUserTypesArr($this->siteLangId);
            $userTypefld = $frm->addSelectBox(Labels::getLabel('FRM_USER_TYPE', $this->siteLangId), 'user_type', $userTypesArr, '', ['class' => 'fieldsVisibilityJs'], Labels::getLabel('FRM_Select', $this->siteLangId));
            $userTypefld->requirement->setRequired(true);
        }

        $fld = $frm->addTextBox(Labels::getLabel('FRM_USERNAME', $this->siteLangId), 'credential_username', '');
        if (1 > $recordId) {
            $fld->setUnique('tbl_user_credentials', 'credential_username', 'credential_user_id', 'user_id', 'user_id');
            $fld->requirements()->setRequired();
            $fld->requirements()->setUsername();
        }
        $fld = $frm->addTextBox(Labels::getLabel('FRM_EMAIL', $this->siteLangId), 'credential_email', '');
        if (1 > $recordId) {
            $fld->setUnique('tbl_user_credentials', 'credential_email', 'credential_user_id', 'user_id', 'user_id');
            $fld->requirements()->setRequired();
        }
        $frm->addRequiredField(Labels::getLabel('FRM_CUSTOMER_NAME', $this->siteLangId), 'user_name');

        $frm->addDateField(Labels::getLabel('FRM_DATE_OF_BIRTH', $this->siteLangId), 'user_dob', '', array('placeholder' => Labels::getLabel('FRM_DATE_OF_BIRTH', $this->siteLangId), 'readonly' => 'readonly', 'class' => 'field--calender'));

        $frm->addHiddenField('', 'user_phone_dcode');
        $companyFld = $frm->addTextBox(Labels::getLabel('FRM_COMPANY', $this->siteLangId), 'user_company');

        if (1 > $recordId) {
            $companyUnReqObj = new FormFieldRequirement('user_company', Labels::getLabel('FRM_COMPANY', $this->siteLangId));
            $companyUnReqObj->setRequired(false);

            $companyReqObj = new FormFieldRequirement('user_company', Labels::getLabel('FRM_COMPANY', $this->siteLangId));
            $companyReqObj->setRequired(true);
            $userTypefld->requirements()->addOnChangerequirementUpdate(User::USER_TYPE_ADVERTISER, 'eq', 'user_company', $companyReqObj);
            $userTypefld->requirements()->addOnChangerequirementUpdate(User::USER_TYPE_ADVERTISER, 'ne', 'user_company', $companyUnReqObj);
        }

        $isAdvertiser = User::getAttributesById($recordId, 'user_is_advertiser');
        if (1 < $recordId && !$isAdvertiser) {
            $frm->removeField($companyFld);
        }

        $phnFld = $frm->addTextBox(Labels::getLabel('FRM_PHONE', $this->siteLangId), 'user_phone', '', array('class' => 'phoneJs ltr-right', 'placeholder' => ValidateElement::PHONE_NO_FORMAT, 'maxlength' => ValidateElement::PHONE_NO_LENGTH));
        $phnFld->requirements()->setRegularExpressionToValidate(ValidateElement::PHONE_REGEX);
        $phnFld->requirements()->setCustomErrorMessage(Labels::getLabel('FRM_PLEASE_ENTER_VALID_PHONE_NUMBER.', $this->siteLangId));

        $countryObj = new Countries();
        $countriesArr = $countryObj->getCountriesAssocArr($this->siteLangId);
        $fld = $frm->addSelectBox(Labels::getLabel('FRM_COUNTRY', $this->siteLangId), 'user_country_id', $countriesArr, FatApp::getConfig('CONF_COUNTRY', FatUtility::VAR_INT, 223), [], Labels::getLabel('FRM_Select', $this->siteLangId));

        $frm->addSelectBox(Labels::getLabel('FRM_STATE', $this->siteLangId), 'user_state_id', array(), '', [], Labels::getLabel('FRM_Select', $this->siteLangId));
        $frm->addTextBox(Labels::getLabel('FRM_CITY', $this->siteLangId), 'user_city');

        if ($isAdvertiser) {
            $fld = $frm->addTextArea(Labels::getLabel('FRM_BRIEF_PROFILE', $this->siteLangId), 'user_profile_info');
            $fld->html_after_field = '<small>' . Labels::getLabel('FRM_PLEASE_TELL_US_SOMETHING_ABOUT_YOURSELF', $this->siteLangId) . '</small>';
            $frm->addTextArea(Labels::getLabel('FRM_WHAT_KIND_PRODUCTS_SERVICES_ADVERTISE', $this->siteLangId), 'user_products_services');
        }

        return $frm;
    }

    public function login($recordId)
    {
        $this->objPrivilege->canEditUsers();
        $userObj = new User($recordId);
        $user = $userObj->getUserInfo(array('user_name', 'credential_username', 'if(credential_password != "", credential_password, credential_password_old) as credential_password', 'user_preferred_dashboard', 'credential_verified', 'credential_active'), false, false);
        if (!$user) {
            Message::addErrorMessage($this->str_invalid_request);
            FatApp::redirectUser(UrlHelper::generateUrl('Users'));
        }

        if (!$user['credential_verified'] || !$user['credential_active']) {
            if (!$user['credential_active'] && !$user['credential_verified']) {
                $lbl = Labels::getLabel('LBL_PLEASE_MARK_{USER-NAME}_AS_ACTIVE_AND_VERIFIED_TO_LOGIN.', $this->siteLangId);
            } else if (!$user['credential_active']) {
                $lbl = Labels::getLabel('LBL_PLEASE_MARK_{USER-NAME}_AS_ACTIVE_TO_LOGIN.', $this->siteLangId);
            } else if (!$user['credential_verified']) {
                $lbl = Labels::getLabel('LBL_PLEASE_MARK_{USER-NAME}_AS_VERIFIED_TO_LOGIN.', $this->siteLangId);
            }

            Message::addErrorMessage(CommonHelper::replaceStringData($lbl, ['{USER-NAME}' => $user['user_name']]));
            FatApp::redirectUser(UrlHelper::generateUrl('Users'));
        }

        $userAuthObj = new UserAuthentication();
        if (!$userAuthObj->login($user['credential_username'], $user['credential_password'], $_SERVER['REMOTE_ADDR'], false, true) === true) {
            Message::addErrorMessage($userAuthObj->getError());
            FatApp::redirectUser(UrlHelper::generateUrl('Users'));
        }
        FatApp::redirectUser(UrlHelper::generateUrl('account', '', array(), CONF_WEBROOT_DASHBOARD));
    }

    public function bankInfoForm($recordId)
    {
        $this->objPrivilege->canViewUsers();
        $recordId = FatUtility::int($recordId);

        if (1 > $recordId) {
            LibHelper::exitWithError($this->str_invalid_request, true);
        }

        $frm = $this->getBankInfoForm();

        $userObj = new User($recordId);
        $data = $userObj->getUserBankInfo();

        $data['user_id'] = $recordId;

        if ($data != false) {
            $frm->fill($data);
        }
        $this->set('userParent', User::getAttributesById($recordId, 'user_parent'));
        $this->set('frm', $frm);
        $this->set('recordId', $recordId);
        $this->set('displayLangTab', false);
        $this->set('html', $this->_template->render(false, false, NULL, true));
        $this->_template->render(false, false, 'json-success.php', true, false);
    }

    public function setupBankInfo()
    {
        $this->objPrivilege->canEditUsers();
        $frm = $this->getBankInfoForm();

        $post = $frm->getFormDataFromArray(FatApp::getPostedData());

        if (false === $post) {
            LibHelper::exitWithError(current($frm->getValidationErrors()), true);
        }

        $recordId = FatUtility::int($post['user_id']);
        unset($post['user_id']);

        if (1 > $recordId) {
            LibHelper::exitWithError($this->str_invalid_request_id, true);
        }

        $userObj = new User($recordId);
        $srch = $userObj->getUserSearchObj(array('user_parent'));
        $rs = $srch->getResultSet();
        $data = FatApp::getDb()->fetch($rs, 'user_id');

        if ($data === false || 0 < $data['user_parent']) {
            LibHelper::exitWithError($this->str_invalid_request, true);
        }

        if (!$userObj->updateBankInfo($post)) {
            LibHelper::exitWithError($userObj->getError(), true);
        }

        $this->set('userId', $recordId);
        $this->set('msg', $this->str_setup_successful);
        $this->_template->render(false, false, 'json-success.php');
    }

    public function changePasswordForm($recordId)
    {
        $this->objPrivilege->canEditUsers();
        $recordId = FatUtility::int($recordId);
        $frm = $this->getChangePasswordForm($recordId);

        $this->set('frm', $frm);
        $this->set('recordId', $recordId);
        $this->set('includeTabs', false);
        $this->set('html', $this->_template->render(false, false, NULL, true));
        $this->_template->render(false, false, 'json-success.php', true, false);
    }

    public function updatePassword()
    {
        $pwdFrm = $this->getChangePasswordForm();
        $post = $pwdFrm->getFormDataFromArray(FatApp::getPostedData());

        if (!$pwdFrm->validate($post)) {
            LibHelper::exitWithError($pwdFrm->getValidationErrors(), true);
        }

        if ($post['new_password'] != $post['conf_new_password']) {
            LibHelper::exitWithError(Labels::getLabel('ERR_New_Password_and_Confirm_new_password_does_not_match', $this->siteLangId), true);
        }

        if (!ValidateElement::password($post['new_password'])) {
            LibHelper::exitWithError(Labels::getLabel('ERR_PASSWORD_MUST_BE_EIGHT_CHARACTERS_LONG_AND_ALPHANUMERIC', $this->siteLangId), true);
        }

        $recordId = FatUtility::int($post['user_id']);
        if ($recordId < 1) {
            LibHelper::exitWithError($this->str_invalid_request, true);
        }

        /* Restrict to change password for demo user on demo URL. */
        if (CommonHelper::demoUrl() && 4 == $recordId) {
            LibHelper::exitWithError(Labels::getLabel('ERR_YOU_ARE_NOT_ALLOWED_TO_CHANGE_PASSWORD_FOR_DEMO', $this->siteLangId), true);
        }

        $userObj = new User($recordId);
        $srch = $userObj->getUserSearchObj(array('user_id'));
        $rs = $srch->getResultSet();

        if (!$rs) {
            LibHelper::exitWithError($this->str_invalid_request, true);
        }

        $data = FatApp::getDb()->fetch($rs, 'user_id');

        if ($data === false) {
            LibHelper::exitWithError($this->str_invalid_request, true);
        }

        if (!$userObj->setLoginPassword($post['new_password'])) {
            LibHelper::exitWithError(Labels::getLabel('ERR_Password_could_not_be_set ', $this->siteLangId) . ' ' . $userObj->getError(), true);
        }

        // TODo:: Can send change password notification using configuration

        $this->set('msg', $this->str_setup_successful);
        $this->_template->render(false, false, 'json-success.php');
    }

    public function autoComplete()
    {
        $pagesize = 20;
        $page = FatApp::getPostedData('page', FatUtility::VAR_INT, 1);
        if ($page < 2) {
            $page = 1;
        }

        $post = FatApp::getPostedData();

        $deletedOnly = FatApp::getPostedData('deletedOnly', FatUtility::VAR_INT, 0);
        $skipDeletedUser = true;
        if ((isset($post['deletedUser']) && $post['deletedUser'] == 1) || 0 < $deletedOnly) {
            $skipDeletedUser = false;
        }

        $joinShop = FatApp::getPostedData('joinShop', FatUtility::VAR_INT, 0);
        $joinOrder = FatApp::getPostedData('joinOrder', FatUtility::VAR_INT, 0);
        $orderType = FatApp::getPostedData('order_type', FatUtility::VAR_INT, 0);
        $appendGuestUser = FatApp::getPostedData('appendGuestUser', FatUtility::VAR_INT, 0);

        $attr = [
            'u.user_name',
            'u.user_id',
            'credential_username',
            'credential_email'
        ];

        if (0 < $joinShop) {
            $attr[] = 'COALESCE(s_l.shop_name, shp.shop_identifier) as shop_name';
        }

        $userObj = new User();
        $srch = $userObj->getUserSearchObj($attr, true, $skipDeletedUser);

        if (0 < $joinShop) {
            $srch->joinTable(Shop::DB_TBL, 'INNER JOIN', 'shp.shop_user_id = u.user_id', 'shp');
            $srch->joinTable(Shop::DB_TBL_LANG, 'LEFT JOIN', 'shp.shop_id = s_l.shoplang_shop_id AND shoplang_lang_id = ' . $this->siteLangId, 's_l');
            $srch->addCondition('shp.shop_supplier_display_status', '=', applicationConstants::YES);
            $srch->addCondition('shp.shop_active', '=', applicationConstants::YES);
        }

        if (0 < $joinOrder) {
            $srch->joinTable(Orders::DB_TBL, 'INNER JOIN', 'o.order_user_id = u.user_id', 'o');
            $srch->addGroupby('o.order_user_id');
            if (0 < $orderType) {
                $srch->addCondition('o.order_type', '=', $orderType);
            }
        }

        if (0 < $deletedOnly) {
            $srch->addCondition('user_deleted', '=', applicationConstants::YES);
        }

        $parentsOnly = FatApp::getPostedData('parents_only', FatUtility::VAR_INT, 0);
        if (0 < $parentsOnly) {
            $srch->addCondition('user_parent', '=', 0);
        }

        $srch->addOrder('credential_email', 'ASC');

        $keyword = FatApp::getPostedData('keyword', null, '');

        if (!empty($keyword)) {
            $cond = $srch->addCondition('uc.credential_username', 'like', '%' . $keyword . '%');
            $cond->attachCondition('uc.credential_email', 'like', '%' . $keyword . '%', 'OR');
            $cond->attachCondition('u.user_name', 'like', '%' . $keyword . '%');

            if (0 < $joinShop) {
                $cond->attachCondition('shp.shop_identifier', 'LIKE', '%' . $keyword . '%');
                $cond->attachCondition('s_l.shop_name', 'LIKE', '%' . $keyword . '%');
            }
        }

        if (!empty($post['user_is_buyer'])) {
            $user_is_buyer = FatUtility::int($post['user_is_buyer']);
            $cnd = $srch->addCondition('u.user_is_buyer', '=', $user_is_buyer);
        }

        if (!empty($post['user_is_supplier'])) {
            $user_is_supplier = FatUtility::int($post['user_is_supplier']);
            if (!empty($post['user_is_buyer'])) {
                $cnd->attachCondition('u.' . User::DB_TBL_PREFIX . 'is_supplier', '=', $user_is_supplier);
            } else {
                $srch->addCondition('u.' . User::DB_TBL_PREFIX . 'is_supplier', '=', $user_is_supplier);
            }
        }

        if (isset($post['user_is_affiliate'])) {
            $user_is_affiliate = FatUtility::int($post['user_is_affiliate']);
            $srch->addCondition('u.user_is_affiliate', '=', $user_is_affiliate);
        }

        if (isset($post['credential_active'])) {
            $srch->addCondition('uc.credential_active', '=', $post['credential_active']);
        }

        if (isset($post['credential_verified'])) {
            $credential_verified = $post['credential_verified'];
            $srch->addCondition('uc.credential_verified', '=', $credential_verified);
        }
        $srch->setPageNumber($page);

        $doNotLimitRecords = FatApp::getPostedData('doNotLimitRecords', FatUtility::VAR_INT, 0);
        if (0 < $doNotLimitRecords) {
            $srch->doNotLimitRecords();
        } else {
            $srch->setPageSize($pagesize);
        }

        $rs = $srch->getResultSet();
        $users = FatApp::getDb()->fetchAll($rs, 'user_id');

        $json = array(
            'pageCount' => $srch->pages(),
            'results' => []
        );

        if (1 === $appendGuestUser && !empty($keyword)) {
            if (false !== stripos(Labels::getLabel('LBL_GUEST_USER', $this->siteLangId), $keyword)) {
                $json['results'][] = ['id' => -1, 'text' => Labels::getLabel('LBL_GUEST_USER', $this->siteLangId)];
            }
        }

        foreach ($users as $key => $user) {
            $userName = (0 < $joinShop) ? $user['shop_name'] : $user['credential_username'];
            $name = !empty($user['user_name']) ? $user['user_name'] . ' (' . $userName . ')' : $userName;
            $json['results'][] = array(
                'id' => $key,
                'text' => strip_tags(html_entity_decode($name, ENT_QUOTES, 'UTF-8'))
            );
        }

        die(FatUtility::convertToJson($json));
    }

    public function updateStatus()
    {
        $this->objPrivilege->canEditUsers();

        $recordId = FatApp::getPostedData('recordId', FatUtility::VAR_INT, 0);
        if (0 >= $recordId) {
            LibHelper::exitWithError($this->str_invalid_request_id, true);
        }

        $status = FatApp::getPostedData('status', FatUtility::VAR_INT, 0);
        if (!in_array($status, [applicationConstants::ACTIVE, applicationConstants::INACTIVE])) {
            LibHelper::exitWithError($this->str_invalid_request, true);
        }

        $this->changeStatus($recordId, $status);
        $shopId = Shop::getAttributesByUserId($recordId, 'shop_id');
        if (0 < $shopId) {
            Product::updateMinPrices(0, $shopId);
        }
        $this->set('msg', Labels::getLabel('MSG_STATUS_UPDATED', $this->siteLangId));
        $this->_template->render(false, false, 'json-success.php');
    }

    public function toggleBulkStatuses()
    {
        $this->objPrivilege->canEditUsers();

        $status = FatApp::getPostedData('status', FatUtility::VAR_INT, -1);
        $recordIdsArr = FatUtility::int(FatApp::getPostedData('user_ids'));
        if (empty($recordIdsArr) || -1 == $status) {
            LibHelper::exitWithError($this->str_invalid_request, true);
        }

        foreach ($recordIdsArr as $recordId) {
            if (1 > $recordId) {
                continue;
            }

            $this->changeStatus($recordId, $status);
        }
        $this->set('msg', Labels::getLabel('MSG_STATUS_UPDATED', $this->siteLangId));
        $this->_template->render(false, false, 'json-success.php');
    }

    protected function changeStatus($recordId, $status)
    {
        $status = FatUtility::int($status);
        $recordId = FatUtility::int($recordId);
        if (1 > $recordId || -1 == $status) {
            LibHelper::exitWithError($this->str_invalid_request, true);
        }

        $userObj = new User($recordId);

        if (!$userObj->activateAccount($status)) {
            LibHelper::exitWithError($userObj->getError(), true);
        }
    }

    public function sendMailForm($recordId)
    {
        $this->objPrivilege->canEditUsers();
        $recordId = FatUtility::int($recordId);
        $userObj = new User($recordId);
        $user = $userObj->getUserInfo(null, false, false);
        if (!$user) {
            LibHelper::exitWithError($this->str_invalid_request, true);
        }
        $frm = $this->getSendMailForm($recordId);

        $this->set('frm', $frm);
        $this->set('user', $user);
        $this->set('recordId', $recordId);
        $this->set('includeTabs', false);
        $this->set('html', $this->_template->render(false, false, NULL, true));
        $this->_template->render(false, false, 'json-success.php', true, false);
    }

    public function sendMail()
    {
        $this->objPrivilege->canEditUsers();
        $frm = $this->getSendMailForm();

        $post = $frm->getFormDataFromArray(FatApp::getPostedData());

        if (false === $post) {
            LibHelper::exitWithError(current($frm->getValidationErrors()), true);
        }

        $recordId = FatUtility::int($post['user_id']);
        $userObj = new User($recordId);
        $user = $userObj->getUserInfo(null, false, false);
        if (!$user || empty($user['credential_email'])) {
            LibHelper::exitWithError($this->str_invalid_request, true);
        }
        $data = array(
            'user_name' => trim($user['user_name']),
            'mail_subject' => trim($post['mail_subject']),
            'mail_message' => nl2br($post["mail_message"]),
            'credential_email' => $user['credential_email'],
            'user_phone_dcode' => ValidateElement::formatDialCode($user['user_phone_dcode']),
            'user_phone' => $user['user_phone']
        );

        $email = new EmailHandler();
        if (!$email->sendEmailToUser($this->siteLangId, $data)) {
            LibHelper::exitWithError($email->getError(), true);
        }

        $this->set('msg', Labels::getLabel('MSG_YOUR_MESSAGE_SENT_TO', $this->siteLangId) . ' - ' . $user["credential_email"]);
        $this->_template->render(false, false, 'json-success.php');
    }

    public function markSellerAsBuyer()
    {
        $this->objPrivilege->canEditUsers();
        $recordId = FatApp::getPostedData('userId', FatUtility::VAR_INT, 0);
        if (1 > $recordId) {
            LibHelper::exitWithError($this->str_invalid_request_id, true);
        }

        $userObj = new User($recordId);
        $user = $userObj->getUserInfo(null, false, false);
        if (!$user) {
            LibHelper::exitWithError($this->str_invalid_request, true);
        }
        $userObj->assignValues(['user_is_buyer' => User::USER_TYPE_BUYER]);
        if (!$userObj->save()) {
            LibHelper::exitWithError($userObj->getError(), true);
        }
        $this->set('msg', $this->str_update_record);
        $this->_template->render(false, false, 'json-success.php');
    }

    public function resendSetPasswordEmail()
    {
        $this->objPrivilege->canEditUsers();
        $recordId = FatApp::getPostedData('userId', FatUtility::VAR_INT, 0);
        if (1 > $recordId) {
            LibHelper::exitWithError($this->str_invalid_request_id, true);
        }

        $userObj = new User($recordId);
        $user = $userObj->getUserInfo(['user_name', 'credential_email', 'user_is_supplier', 'user_is_affiliate', 'user_is_advertiser'], true, false);
        if (!$user) {
            LibHelper::exitWithError($this->str_invalid_request, true);
        }

        $userType = User::USER_TYPE_BUYER;
        if ($user['user_is_supplier'] == 1) {
            $userType = User::USER_TYPE_SELLER;
        } elseif ($user['user_is_affiliate'] == 1) {
            $userType = User::USER_TYPE_AFFILIATE;
        } elseif ($user['user_is_advertiser'] == 1) {
            $userType = User::USER_TYPE_ADVERTISER;
        }

        $userData = [
            'user_name' => $user['user_name'],
            'user_email' => $user['credential_email'],
            'user_id' => $recordId,
            'account_type' => User::getUserTypesArr($this->siteLangId)[$userType]
        ];

        if (!$userObj->sendAdminNewUserCreationEmail($userData, $this->admin_id)) {
            LibHelper::exitWithError(Labels::getLabel("ERR_ERROR_IN_SENDING_WELCOME_EMAIL", $this->admin_id), true);
        }
        $this->set('msg', Labels::getLabel("MSG_EMAIL_SENT_SUCCESSFUL", $this->admin_id));
        $this->_template->render(false, false, 'json-success.php');
    }

    private function getChangePasswordForm($recordId = 0)
    {
        $recordId = FatUtility::int($recordId);
        $frm = new Form('changePwdFrm');
        $frm->addHiddenField('', 'user_id', $recordId);

        $newPwd = $frm->addPasswordField(
            Labels::getLabel('FRM_NEW_PASSWORD', $this->siteLangId),
            'new_password',
            '',
            array('id' => 'new_password')
        );
        $newPwd->requirements()->setRequired();
        $newPwd->requirements()->setRegularExpressionToValidate(ValidateElement::PASSWORD_REGEX);
        $newPwd->requirements()->setCustomErrorMessage(Labels::getLabel('ERR_PASSWORD_MUST_BE_EIGHT_CHARACTERS_LONG_AND_ALPHANUMERIC', $this->siteLangId));

        $conNewPwd = $frm->addPasswordField(
            Labels::getLabel('FRM_CONFIRM_NEW_PASSWORD', $this->siteLangId),
            'conf_new_password',
            '',
            array('id' => 'conf_new_password')
        );
        $conNewPwdReq = $conNewPwd->requirements();
        $conNewPwdReq->setRequired();
        $conNewPwdReq->setCompareWith('new_password', 'eq');
        return $frm;
    }

    private function getSendMailForm($recordId = 0)
    {
        $recordId = FatUtility::int($recordId);
        $frm = new Form('sendMailFrm');
        $frm->addHiddenField('', 'user_id', $recordId);
        $frm->addHTML('', 'to_info', '');
        $frm->addTextBox(Labels::getLabel('FRM_SUBJECT', $this->siteLangId), 'mail_subject')->requirements()->setRequired(true);
        $frm->addTextArea(Labels::getLabel('FRM_MESSAGE', $this->siteLangId), 'mail_message')->requirements()->setRequired(true);
        return $frm;
    }


    private function getBankInfoForm()
    {
        $frm = new Form('frmBankInfo');
        $frm->addHiddenField('', 'user_id');
        $frm->addRequiredField(Labels::getLabel('FRM_BANK_NAME', $this->siteLangId), 'ub_bank_name', '');
        $frm->addRequiredField(Labels::getLabel('FRM_ACCOUNT_HOLDER_NAME', $this->siteLangId), 'ub_account_holder_name', '');
        $frm->addRequiredField(Labels::getLabel('FRM_ACCOUNT_NUMBER', $this->siteLangId), 'ub_account_number', '');
        $frm->addRequiredField(Labels::getLabel('FRM_IFSC_SWIFT_CODE', $this->siteLangId), 'ub_ifsc_swift_code', '');
        $frm->addTextArea(Labels::getLabel('FRM_BANK_ADDRESS', $this->siteLangId), 'ub_bank_address', '');
        return $frm;
    }

    public function cookiesPreferencesForm($recordId)
    {
        $this->objPrivilege->canViewUsers();
        $recordId = FatUtility::int($recordId);
        if (1 > $recordId) {
            LibHelper::exitWithError($this->str_invalid_request, true);
        }

        $frm = $this->getCookiesPreferencesForm();
        $userObj = new User($recordId);
        $data = $userObj->getUserSelectedCookies();
        if ($data != false) {
            $frm->fill($data);
        }

        $this->set('userParent', User::getAttributesById($recordId, 'user_parent'));
        $this->set('frm', $frm);
        $this->set('recordId', $recordId);
        $this->set('displayLangTab', false);
        $this->set('html', $this->_template->render(false, false, NULL, true));
        $this->_template->render(false, false, 'json-success.php', true, false);
    }

    private function getCookiesPreferencesForm()
    {
        $frm = new Form('frmCookiesPreferences');
        $fld = $frm->addCheckBox(Labels::getLabel("FRM_FUNCTIONAL", $this->siteLangId), 'ucp_functional', 1, array(), true, 0);
        // $fld->htmlAfterField = '<div>' . Labels::getLabel('FRM_FUNCTIONAL_COOKIES_INFORMATION', $this->siteLangId) . '</div>';
        $fld = $frm->addCheckBox(Labels::getLabel("FRM_STATISTICAL_ANALYSIS", $this->siteLangId), 'ucp_statistical', 1, array(), false, 0);
        // $fld->htmlAfterField = '<div>' . Labels::getLabel('FRM_STATISTICAL_ANALYSIS_COOKIES_INFORMATION', $this->siteLangId) . '</div>';
        $fld = $frm->addCheckBox(Labels::getLabel("FRM_PERSONALISE_EXPERIENCE", $this->siteLangId), 'ucp_personalized', 1, array(), false, 0);
        // $fld->htmlAfterField = '<div>' . Labels::getLabel('FRM_PERSONALISE_COOKIES_INFORMATION', $this->siteLangId) . '</div>';
        return $frm;
    }

    protected function getFormColumns(): array
    {
        $usersTblHeadingCols = CacheHelper::get('usersTblHeadingCols' . $this->siteLangId, CONF_DEF_CACHE_TIME, '.txt');
        if ($usersTblHeadingCols) {
            return json_decode($usersTblHeadingCols, true);
        }

        $arr = [
            'select_all' => Labels::getLabel('LBL_SELECT_ALL', $this->siteLangId),
            'user_id' => Labels::getLabel('LBL_USER_ID', $this->siteLangId),
            'user_name' => Labels::getLabel('LBL_USER_NAME', $this->siteLangId),
            'user_registered_initially_for' => Labels::getLabel('LBL_REGISTERED_AS', $this->siteLangId),
            'user_type' => Labels::getLabel('LBL_USER_TYPE', $this->siteLangId),
            'user_regdate' => Labels::getLabel('LBL_REG._Date', $this->siteLangId),
            'credential_active' => Labels::getLabel('LBL_STATUS', $this->siteLangId),
            'action' => Labels::getLabel('LBL_ACTION_BUTTONS', $this->siteLangId),
        ];

        CacheHelper::create('usersTblHeadingCols' . $this->siteLangId, json_encode($arr), CacheHelper::TYPE_LABELS);
        return $arr;
    }

    protected function getDefaultColumns(): array
    {
        return [
            'select_all',
            'user_id',
            'user_name',
            'user_registered_initially_for',
            'user_type',
            'user_regdate',
            'credential_active',
            'action',
        ];
    }

    protected function excludeKeysForSort($fields = []): array
    {
        return array_diff($fields, ['user_type', 'user_registered_initially_for'], Common::excludeKeysForSort());
    }
}
