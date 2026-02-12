<?php

class DiscountCouponsController extends ListingBaseController
{
    protected string $modelClass = 'DiscountCoupons';
    protected string $pageKey = 'MANAGE_DISCOUNT_COUPONS';

    public function __construct($action)
    {
        parent::__construct($action);
        $this->objPrivilege->canViewDiscountCoupons();
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
            $this->set("canEdit", $this->objPrivilege->canEditDiscountCoupons($this->admin_id, true));
        } else {
            $this->objPrivilege->canEditDiscountCoupons();
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
        $this->formLangFields = [
            $this->modelObj::tblFld('title'),
            $this->modelObj::tblFld('description')
        ];
        $this->set('formTitle', Labels::getLabel('LBL_DISCOUNT_COUPON_SETUP', $this->siteLangId));
    }

    public function index()
    {
        $fields = $this->getFormColumns();
        $frmSearch = $this->getSearchForm($fields);

        $pageData = PageLanguageData::getAttributesByKey($this->pageKey, $this->siteLangId);
        $pageTitle = $pageData['plang_title'] ?? LibHelper::getControllerName(true);

        $actionItemsData = HtmlHelper::getDefaultActionItems($fields);
        $actionItemsData['performBulkAction'] = true;
        $actionItemsData['statusButtons'] = true;

        /* Mark As Inactive, those were Expired. */
        FatApp::getDb()->query("UPDATE " . DiscountCoupons::DB_TBL . " SET coupon_active = 0 WHERE coupon_end_date < CURDATE() AND coupon_end_date != 0");
        /* --------------------------------- */

        $this->set('pageData', $pageData);
        $this->set('pageTitle', $pageTitle);
        $this->set('actionItemsData', $actionItemsData);
        $this->set("frmSearch", $frmSearch);
        $this->set('defaultColumns', $this->getDefaultColumns());
        $this->set('keywordPlaceholder', Labels::getLabel('FRM_SEARCH_BY_TITLE_OR_CODE', $this->siteLangId));
        $this->getListingData();

        $this->_template->addJs(['js/cropper.js', 'js/cropper-main.js', 'js/tagify.min.js', 'js/tagify.polyfills.min.js', 'discount-coupons/page-js/index.js']);
        $this->_template->addCss(['css/cropper.css', 'css/tagify.min.css']);

        $this->_template->render(true, true, '_partial/listing/index.php');
    }

    public function search()
    {
        $this->getListingData();
        $jsonData = [
            'listingHtml' => $this->_template->render(false, false, 'discount-coupons/search.php', true),
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
        $sortBy = FatApp::getPostedData('sortBy', FatUtility::VAR_STRING, 'coupon_id');
        if (!array_key_exists($sortBy, $fields)) {
            $sortBy = 'coupon_id';
        }

        $sortOrder = applicationConstants::getSortOrder(FatApp::getPostedData('sortOrder', FatUtility::VAR_STRING), applicationConstants::SORT_DESC);

        $srchFrm = $this->getSearchForm($fields);

        $postedData = FatApp::getPostedData();
        $post = $srchFrm->getFormDataFromArray($postedData);

        $page = FatApp::getPostedData('page', FatUtility::VAR_INT, 1);
        $page = ($page <= 0) ? 1 : $page;

        $pageSize = applicationConstants::getPageSize(FatApp::getPostedData('pageSize', FatUtility::VAR_INT));

        $srch = DiscountCoupons::getSearchObject($this->siteLangId, false);

        if (isset($post['keyword']) && '' != $post['keyword']) {
            $cnd = $srch->addCondition('dc.coupon_identifier', 'like', '%' . $post['keyword'] . '%');
            $cnd->attachCondition('dc.coupon_code', 'like', '%' . $post['keyword'] . '%');
            $cnd->attachCondition('dc_l.coupon_title', 'like', '%' . $post['keyword'] . '%');
        }
        if (!empty($post['coupon_type'])) {
            $srch->addCondition('dc.coupon_type', '=', $post['coupon_type']);
        }

        $this->setRecordCount(clone $srch, $pageSize, $page, $post);
        $srch->doNotCalculateRecords();

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
        $this->set('discountTypeArr', DiscountCoupons::getTypeArr($this->siteLangId));
        $this->set('canEdit', $this->objPrivilege->canEditDiscountCoupons($this->admin_id, true));
        $this->set('canView', $this->objPrivilege->canViewDiscountCoupons($this->admin_id, true));
    }

    public function form()
    {
        $this->objPrivilege->canEditDiscountCoupons();

        $recordId = FatApp::getPostedData('recordId', FatUtility::VAR_INT, 0);
        $includeTabs = FatApp::getPostedData('includeTabs', FatUtility::VAR_INT, 1);
        $onClear = FatApp::getPostedData('onClear', FatUtility::VAR_STRING, '');

        $frm = $this->getForm($recordId, $includeTabs);

        $isExpired = false;
        if (0 < $recordId) {
            $data = DiscountCoupons::getAttributesByLangId(CommonHelper::getDefaultFormLangId(), $recordId, ['*', 'COALESCE(coupon_title, coupon_identifier) as coupon_title'], applicationConstants::JOIN_RIGHT);
            if ($data === false) {
                LibHelper::exitWithError($this->str_invalid_request, true);
            }
            $isExpired = ($data['coupon_end_date'] != "0000-00-00" && strtotime($data['coupon_end_date']) < strtotime(date('Y-m-d'))) ? true : false;
            $frm->fill($data);
        } else {
            $frm->fill(array('coupon_id' => $recordId));
        }

        $this->set('recordId', $recordId);
        $this->set('isExpired', $isExpired);
        $this->set('frm', $frm);
        $this->set('coupon_type', (isset($data['coupon_type']) ? $data['coupon_type'] : DiscountCoupons::TYPE_DISCOUNT));
        $this->set('couponDiscountIn', isset($data['coupon_discount_in_percent']) ? $data['coupon_discount_in_percent'] : applicationConstants::PERCENTAGE);
        $this->set('formTitle', Labels::getLabel('LBL_DISCOUNT_COUPON_SETUP', $this->siteLangId));
        $this->set('onClear', $onClear);
        if (1 > $includeTabs) {
            $this->set('includeTabs', false);
        }
        $this->set('html', $this->_template->render(false, false, NULL, true));
        $this->_template->render(false, false, 'json-success.php', true, false);
    }

    public function setup()
    {
        $this->objPrivilege->canEditDiscountCoupons();

        $frm = $this->getForm();

        $post = $frm->getFormDataFromArray(FatApp::getPostedData());
        if (false === $post) {
            LibHelper::exitWithError(current($frm->getValidationErrors()), true);
        }

        $recordId = $post['coupon_id'];
        unset($post['coupon_id']);

        $startDate = FatApp::getPostedData('coupon_start_date', FatUtility::VAR_STRING);
        $endDate = FatApp::getPostedData('coupon_end_date', FatUtility::VAR_STRING);

        if (!empty($endDate) && time() > strtotime($endDate . '23:59:59')) {
            LibHelper::exitWithError(Labels::getLabel('ERR_COUPON_EXPIRED!!_DATE_TO_MUST_BE_GREATER_THAN_CURRENT_DATE.'), true);
        }

        if (strtotime($endDate) < strtotime($startDate)) {
            LibHelper::exitWithError(Labels::getLabel('ERR_END_DATE_TO_MUST_BE_GREATER_THAN_START_DATE'), true);
        }

        $post['coupon_start_date'] = !empty($startDate) ? $startDate : date('Y-m-d');
        $post['coupon_end_date'] = !empty($endDate) ? $endDate : date('Y-m-d', strtotime('+50 year'));
        $post['coupon_identifier'] = $post['coupon_title'];

        $couponCodes = array_map('trim', array_filter(explode(',', $post['coupon_code'])));
        if (empty($couponCodes)) {
            LibHelper::exitWithError(Labels::getLabel('ERR_PLEASE_ENTER_AT_LEAST_ONE_COUPON_CODE', $this->siteLangId), true);
        }
        $isBulkCreate = (0 == $recordId && count($couponCodes) > 1);

        if ($isBulkCreate) {
            $created = 0;
            $skipped = [];
            foreach ($couponCodes as $code) {
                if (empty($code)) {
                    continue;
                }
                $couponPost = $post;
                $couponPost['coupon_code'] = $code;
                $couponPost['coupon_title'] = $code;
                $couponPost['coupon_identifier'] = $code;
                $record = new DiscountCoupons(0);
                $record->assignValues($couponPost);
                if (!$record->save()) {
                    $msg = $record->getError();
                    if (false !== strpos(strtolower($msg), 'duplicate')) {
                        $skipped[] = $code;
                    } else {
                        LibHelper::exitWithError($msg, true);
                    }
                    continue;
                }
                $this->setLangData($record, [
                    $record::tblFld('title') => $couponPost[$record::tblFld('title')],
                    $record::tblFld('description') => $couponPost[$record::tblFld('description')]
                ]);
                $created++;
            }
            $msg = Labels::getLabel('MSG_COUPONS_CREATED_SUCCESSFULLY', $this->siteLangId);
            $msg = CommonHelper::replaceStringData($msg, ['{count}' => $created]);
            if (!empty($skipped)) {
                $msg .= ' ' . Labels::getLabel('MSG_COUPONS_SKIPPED_DUPLICATE', $this->siteLangId) . ': ' . implode(', ', $skipped);
            }
            $this->set('msg', $msg);
            $this->set('recordId', 0);
            $this->_template->render(false, false, 'json-success.php');
            return;
        }

        if ($isBulkCreate === false && count($couponCodes) === 1) {
            $post['coupon_code'] = $couponCodes[0];
        }

        $record = new DiscountCoupons($recordId);
        $record->assignValues($post);

        if (!$record->save()) {
            $msg = $record->getError();
            if (false !== strpos(strtolower($msg), 'duplicate')) {
                $msg = Labels::getLabel('ERR_DUPLICATE_RECORD_NAME', $this->siteLangId);
            }
            LibHelper::exitWithError($msg, true);
        }
        $recordId = $record->getMainTableRecordId();
        $this->setLangData($record, [
            $record::tblFld('title') => $post[$record::tblFld('title')],
            $record::tblFld('description') => $post[$record::tblFld('description')]
        ]);

        $this->set('msg', Labels::getLabel('MSG_COUPON_SETUP_SUCCESSFUL.', $this->siteLangId));
        $this->set('recordId', $recordId);
        $this->_template->render(false, false, 'json-success.php');
    }

    protected function isMediaUploaded($recordId)
    {
        $attachment = AttachedFile::getAttachment(AttachedFile::FILETYPE_DISCOUNT_COUPON_IMAGE, $recordId, 0);
        if (false !== $attachment && 0 < $attachment['afile_id']) {
            return true;
        }
        return false;
    }

    public function updateStatus()
    {
        $this->objPrivilege->canEditDiscountCoupons();
        $recordId = FatApp::getPostedData('recordId', FatUtility::VAR_INT, 0);
        $status = FatApp::getPostedData('status', FatUtility::VAR_INT, -1);

        $this->changeStatus($recordId, $status);

        FatUtility::dieJsonSuccess(Labels::getLabel('LBL_STATUS_UPDATED', $this->siteLangId));
    }

    public function toggleBulkStatuses()
    {
        $this->objPrivilege->canEditDiscountCoupons();

        $status = FatApp::getPostedData('status', FatUtility::VAR_INT, -1);
        $recordIdsArr = FatUtility::int(FatApp::getPostedData('record_ids'));

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
        if (1 > $recordId || !in_array($status, [applicationConstants::ACTIVE, applicationConstants::INACTIVE])) {
            LibHelper::exitWithError($this->str_invalid_request, true);
        }

        $data = DiscountCoupons::getAttributesById($recordId, array('coupon_id', 'coupon_active'));

        if ($data == false) {
            LibHelper::exitWithError($this->str_invalid_request, true);
        }

        $obj = new DiscountCoupons($recordId);
        if (!$obj->changeStatus($status)) {
            LibHelper::exitWithError($obj->getError(), true);
        }
    }

    protected function getSearchForm($fields = [])
    {
        $frm = new Form('frmRecordSearch');
        $frm->addHiddenField('', 'page');
        if (!empty($fields)) {
            $this->addSortingElements($frm, 'coupon_id', applicationConstants::SORT_DESC);
        }
        $fld = $frm->addTextBox(Labels::getLabel('FRM_KEYWORD', $this->siteLangId), 'keyword');
        $fld->overrideFldType('search');
        $frm->addSelectBox(Labels::getLabel('FRM_COUPON_TYPE', $this->siteLangId), 'coupon_type', DiscountCoupons::getTypeArr($this->siteLangId), '', [], Labels::getLabel('FRM_COUPON_TYPE', $this->siteLangId));
        $frm->addHiddenField('', 'total_record_count');
        HtmlHelper::addSearchButton($frm);
        HtmlHelper::addClearButton($frm);
        return $frm;
    }

    private function getForm($recordId = 0, $updateOtherLangDataEle = 1)
    {
        $recordId = FatUtility::int($recordId);

        $frm = new Form('frmCoupon');
        $frm->addHiddenField('', 'coupon_id', $recordId);

        $typeArr = DiscountCoupons::getTypeArr($this->siteLangId);
        $frm->addSelectBox(Labels::getLabel('FRM_SELECT_DISCOUNT_TYPE', $this->siteLangId), 'coupon_type', $typeArr)->requirements()->setRequired();

        $frm->addRequiredField(Labels::getLabel('FRM_COUPON_TITLE', $this->siteLangId), 'coupon_title');

        $fld = $frm->addRequiredField(Labels::getLabel('FRM_COUPON_CODE', $this->siteLangId), 'coupon_code');
        if (0 == $recordId) {
            $hint = Labels::getLabel('FRM_COUPON_CODE_MULTIPLE_HINT', $this->siteLangId);
            //$fld->placeholder(($hint !== 'FRM_COUPON_CODE_MULTIPLE_HINT' ? $hint : 'e.g. SAVE10, SAVE20, WELCOME5'));
        }
        $fld->setUnique(DiscountCoupons::DB_TBL, 'coupon_code', 'coupon_id', 'coupon_id', 'coupon_id');

        $frm->addTextArea(Labels::getLabel('FRM_COUPON_DESCRIPTION', $this->siteLangId), 'coupon_description');

        $frm->addDateField(Labels::getLabel('FRM_DATE_FROM', $this->siteLangId), 'coupon_start_date', '', array('placeholder' => Labels::getLabel('FRM_DATE_FROM', $this->siteLangId), 'readonly' => 'readonly', 'class' => 'small dateTimeFld field--calender'));
        $fld = $frm->addDateField(Labels::getLabel('FRM_DATE_TO', $this->siteLangId), 'coupon_end_date', '', array('placeholder' => Labels::getLabel('FRM_DATE_TO', $this->siteLangId), 'readonly' => 'readonly', 'class' => 'small dateTimeFld field--calender'));
        $fld->requirements()->setCompareWith('coupon_start_date', 'ge', Labels::getLabel('FRM_DATE_TO', $this->siteLangId));

        $percentageFlatArr = applicationConstants::getPercentageFlatArr($this->siteLangId);
        $frm->addSelectBox(Labels::getLabel('FRM_DISCOUNT_IN', $this->siteLangId), 'coupon_discount_in_percent', $percentageFlatArr, '', array(), '');

        $frm->addFloatField(Labels::getLabel('FRM_DISCOUNT_VALUE', $this->siteLangId), 'coupon_discount_value');
        $frm->addFloatField(Labels::getLabel('FRM_MIN_ORDER_VALUE', $this->siteLangId), 'coupon_min_order_value')->requirements()->setFloatPositive();
        $frm->addFloatField(Labels::getLabel('FRM_MAX_DISCOUNT_VALUE', $this->siteLangId), 'coupon_max_discount_value');

        $fld = $frm->addIntegerField(Labels::getLabel('FRM_USES_PER_COUPON', $this->siteLangId), 'coupon_uses_count', 1);
        $fld->requirements()->setRange('1', '10000');
        $fld = $frm->addIntegerField(Labels::getLabel('FRM_USES_PER_CUSTOMER', $this->siteLangId), 'coupon_uses_coustomer', 1);
        $fld->requirements()->setCompareWith('coupon_uses_count', 'le', '');
        $fld->requirements()->setRange('1', '10000');

        $frm->addCheckBox(Labels::getLabel('FRM_COUPON_STATUS', $this->siteLangId), 'coupon_active', applicationConstants::ACTIVE, [], true, applicationConstants::INACTIVE);

        $flatDiscountVal = new FormFieldRequirement('coupon_discount_value', Labels::getLabel('FRM_DISCOUNT_VALUE', $this->siteLangId));
        $flatDiscountVal->setRequired(true);
        $percentDiscountVal = new FormFieldRequirement('coupon_discount_value', Labels::getLabel('FRM_DISCOUNT_VALUE', $this->siteLangId));
        $percentDiscountVal->setRequired(true);
        $percentDiscountVal->setFloatPositive();
        $percentDiscountVal->setRange('1', '100');

        $couponMinOrderValueReqTrue = new FormFieldRequirement('coupon_min_order_value', 'value');
        $couponMinOrderValueReqTrue->setRequired();
        $couponMinOrderValueReqTrue->setRange('0.00001', '9999999999');
        $couponMinOrderValueReqFalse = new FormFieldRequirement('coupon_min_order_value', 'value');
        $couponMinOrderValueReqFalse->setRequired(false);

        $couponMaxDiscountValueReqTrue = new FormFieldRequirement('coupon_max_discount_value', 'value');
        $couponMaxDiscountValueReqTrue->setRequired();
        $couponMaxDiscountValueReqFalse = new FormFieldRequirement('coupon_max_discount_value', 'value');
        $couponMaxDiscountValueReqFalse->setRequired(false);

        $couponMaxDiscountValueReqTrue->setFloatPositive();
        $couponMaxDiscountValueReqTrue->setRange('0.00001', '9999999999');

        $cType_fld = $frm->getField('coupon_type');
        $cType_fld->requirements()->addOnChangerequirementUpdate(DiscountCoupons::TYPE_DISCOUNT, 'eq', 'coupon_min_order_value', $couponMinOrderValueReqTrue);
        $cType_fld->requirements()->addOnChangerequirementUpdate(DiscountCoupons::TYPE_SELLER_PACKAGE, 'eq', 'coupon_min_order_value', $couponMinOrderValueReqFalse);

        $coupon_discount_in_percent_fld = $frm->getField('coupon_discount_in_percent');

        $coupon_discount_in_percent_fld->requirements()->addOnChangerequirementUpdate(applicationConstants::FLAT, 'eq', 'coupon_discount_value', $flatDiscountVal);
        $coupon_discount_in_percent_fld->requirements()->addOnChangerequirementUpdate(applicationConstants::PERCENTAGE, 'eq', 'coupon_discount_value', $percentDiscountVal);

        $coupon_discount_in_percent_fld->requirements()->addOnChangerequirementUpdate(applicationConstants::PERCENTAGE, 'eq', 'coupon_max_discount_value', $couponMaxDiscountValueReqTrue);
        $coupon_discount_in_percent_fld->requirements()->addOnChangerequirementUpdate(applicationConstants::FLAT, 'eq', 'coupon_max_discount_value', $couponMaxDiscountValueReqFalse);

        if (applicationConstants::YES == $updateOtherLangDataEle) {
            $languageArr = Language::getDropDownList(CommonHelper::getDefaultFormLangId());
            $translatorSubscriptionKey = FatApp::getConfig('CONF_TRANSLATOR_SUBSCRIPTION_KEY', FatUtility::VAR_STRING, '');
            if (!empty($translatorSubscriptionKey) && 0 < count($languageArr)) {
                $frm->addCheckBox(Labels::getLabel('FRM_UPDATE_OTHER_LANGUAGES_DATA', $this->siteLangId), 'auto_update_other_langs_data', 1, array(), false, 0);
            }
        }

        return $frm;
    }

    protected function getLangForm($recordId = 0, $langId = 0)
    {
        $recordId = FatUtility::int($recordId);
        $langId = FatUtility::int($langId);
        $langId = 1 > $langId ? $this->siteLangId : $langId;

        $frm = new Form('frmCouponLang');
        $frm->addHiddenField('', 'coupon_id', $recordId);
        $frm->addSelectBox(Labels::getLabel('FRM_LANGUAGE', $langId), 'lang_id', Language::getDropDownList(CommonHelper::getDefaultFormLangId()), $langId, array(), '');

        $frm->addRequiredField(Labels::getLabel('FRM_COUPON_TITLE', $langId), 'coupon_title');
        $frm->addTextArea(Labels::getLabel('FRM_COUPON_DESCRIPTION', $langId), 'coupon_description');

        return $frm;
    }

    /* Coupon Type Product Purchase. */
    public function links(int $recordId)
    {
        if (1 > $recordId) {
            LibHelper::exitWithError($this->str_invalid_request_id, false, true);
            CommonHelper::redirectUserReferer();
        }
        $this->checkEditPrivilege(true);

        $couponData = DiscountCoupons::getAttributesByLangId($this->siteLangId, $recordId, ['COALESCE(coupon_title, coupon_identifier) as coupon_title', 'coupon_type'], applicationConstants::JOIN_RIGHT);
        if (empty($couponData)) {
            LibHelper::exitWithError($this->str_invalid_request_id, false, true);
            CommonHelper::redirectUserReferer();
        }

        $fields = [
            'linkType' => Labels::getLabel('LBL_LINK_TYPE', $this->siteLangId),
            'items' => Labels::getLabel('LBL_ITEMS', $this->siteLangId),
        ];

        if ($couponData['coupon_type'] == DiscountCoupons::TYPE_SELLER_PACKAGE) {
            $linksTypeArr = [               
                'users' => Labels::getLabel('LBL_USERS', $this->siteLangId),
            ];

            $linksTypeData = [              
                'users' => DiscountCoupons::getCouponUsers($recordId, $this->siteLangId),
            ];
        } else {
            $linksTypeArr = [
                'products' => Labels::getLabel('LBL_PRODUCTS', $this->siteLangId),
                'categories' => Labels::getLabel('LBL_CATEGORIES', $this->siteLangId),
                'users' => Labels::getLabel('LBL_USERS', $this->siteLangId),
                'shops' => Labels::getLabel('LBL_SHOPS', $this->siteLangId),
                'brands' => Labels::getLabel('LBL_BRANDS', $this->siteLangId),
            ];

            $linksTypeData = [
                'products' => DiscountCoupons::getCouponProducts($recordId, $this->siteLangId),
                'categories' => DiscountCoupons::getCouponCategories($recordId, $this->siteLangId),
                'users' => DiscountCoupons::getCouponUsers($recordId, $this->siteLangId),
                'shops' => DiscountCoupons::getCouponShops($recordId, $this->siteLangId),
                'brands' => DiscountCoupons::getCouponBrands($recordId, $this->siteLangId),
            ];
        }


        $str = Labels::getLabel('LBL_BIND_LINKS_FOR_{LINK-TYPE}', $this->siteLangId);
        $pageTitle = CommonHelper::replaceStringData($str, ['{LINK-TYPE}' => current($couponData)]);

        $this->set('pageTitle', $pageTitle);
        $this->set('linksTypeArr', $linksTypeArr);
        $this->set('linksTypeData', $linksTypeData);
        $this->set('recordId', $recordId);
        $this->set('fields', $fields);

        $this->_template->addJs(['js/tagify.min.js', 'js/tagify.polyfills.min.js', 'discount-coupons/page-js/index.js']);
        $this->_template->addCss(['css/tagify.min.css']);
        $this->_template->render(true, true, NULL, false, false);
    }

    /* Coupon Type Subscription Purchase. */
    public function linkPlanForm()
    {
        $this->objPrivilege->canEditDiscountCoupons();
        $recordId = FatApp::getPostedData('recordId', FatUtility::VAR_INT, 0);

        if (1 > $recordId) {
            LibHelper::exitWithError($this->str_invalid_request, true);
        }
        $data = DiscountCoupons::getCouponPlans($recordId, $this->siteLangId);
        $tagifyData = [];
        array_walk($data, function ($item) use (&$tagifyData, $recordId) {
            $planName = DiscountCoupons::getPlanTitle($item, $this->siteLangId);
            $tagifyData[] = [
                'id' => $item['spplan_id'],
                'value' => htmlentities($planName, ENT_QUOTES),
                'linkType' => 'subscription',
                'recordId' => $recordId,
            ];
        });

        $frm = $this->getPlanForm();
        if (!empty($tagifyData)) {
            $frm->fill(['plan_name' => json_encode($tagifyData)]);
        }
        $this->checkEditPrivilege(true);

        $couponData = DiscountCoupons::getAttributesByLangId($this->siteLangId, $recordId, ['COALESCE(coupon_title, coupon_identifier) as coupon_title'], applicationConstants::JOIN_RIGHT);
        if (empty($couponData)) {
            LibHelper::exitWithError($this->str_invalid_request_id, true);
            CommonHelper::redirectUserReferer();
        }
        $str = Labels::getLabel('LBL_BIND_LINKS_FOR_{LINK-TYPE}', $this->siteLangId);
        $formTitle = CommonHelper::replaceStringData($str, ['{LINK-TYPE}' => current($couponData)]);

        $this->set('formTitle', $formTitle);
        $this->set('recordId', $recordId);
        $this->set('frm', $frm);
        $this->set('includeTabs', false);
        $this->set('displayFooterButtons', false);

        $this->set('html', $this->_template->render(false, false, NULL, true));
        $this->_template->render(false, false, 'json-success.php', true, false);
    }

    private function getPlanForm()
    {
        $this->objPrivilege->canEditDiscountCoupons();
        $frm = new Form('frmCouponProduct');
        $frm->addTextBox(Labels::getLabel('FRM_SELECT_PLAN', $this->siteLangId), 'plan_name', '', ['data-link-type' => 'subscription']);
        return $frm;
    }

    public function bindItem()
    {
        $recordId = FatApp::getPostedData('recordId', FatUtility::VAR_INT, 0);
        $linkType = FatApp::getPostedData('linkType', FatUtility::VAR_STRING, '');
        $itemId = FatApp::getPostedData('id', FatUtility::VAR_STRING, '');
        if (1 > $recordId || empty($linkType) || 1 > $itemId) {
            LibHelper::exitWithError($this->str_invalid_request, true);
        }

        $obj = new DiscountCoupons();
        switch ($linkType) {
            case 'products':
                if (!$obj->addUpdateCouponProduct($recordId, $itemId)) {
                    LibHelper::exitWithError($obj->getError(), true);
                }
                break;
            case 'categories':
                if (!$obj->addUpdateCouponCategory($recordId, $itemId)) {
                    LibHelper::exitWithError($obj->getError(), true);
                }
                break;
            case 'users':
                if (!$obj->addUpdateCouponUser($recordId, $itemId)) {
                    LibHelper::exitWithError($obj->getError(), true);
                }
                break;
            case 'shops':
                if (!$obj->addUpdateCouponShop($recordId, $itemId)) {
                    LibHelper::exitWithError($obj->getError(), true);
                }
                break;
            case 'brands':
                if (!$obj->addUpdateCouponBrand($recordId, $itemId)) {
                    LibHelper::exitWithError($obj->getError(), true);
                }
                break;
            case 'subscription':
                if (!$obj->addUpdateCouponPlan($recordId, $itemId)) {
                    LibHelper::exitWithError($obj->getError(), true);
                }
                break;

            default:
                LibHelper::exitWithError($this->str_invalid_request, true);
                break;
        }
        $this->set('msg', $this->str_update_record);
        $this->_template->render(false, false, 'json-success.php');
    }

    public function removeItem()
    {
        $recordId = FatApp::getPostedData('recordId', FatUtility::VAR_INT, 0);
        $linkType = FatApp::getPostedData('linkType', FatUtility::VAR_STRING, '');
        $itemId = FatApp::getPostedData('id', FatUtility::VAR_STRING, '');
        if (1 > $recordId || empty($linkType) || 1 > $itemId) {
            LibHelper::exitWithError($this->str_invalid_request, true);
        }

        $obj = new DiscountCoupons();
        switch ($linkType) {
            case 'products':
                if (!$obj->removeCouponProduct($recordId, $itemId)) {
                    LibHelper::exitWithError($obj->getError(), true);
                }
                break;
            case 'categories':
                if (!$obj->removeCouponCategory($recordId, $itemId)) {
                    LibHelper::exitWithError($obj->getError(), true);
                }
                break;
            case 'users':
                if (!$obj->removeCouponUser($recordId, $itemId)) {
                    LibHelper::exitWithError($obj->getError(), true);
                }
                break;
            case 'shops':
                if (!$obj->removeCouponShop($recordId, $itemId)) {
                    LibHelper::exitWithError($obj->getError(), true);
                }
                break;
            case 'brands':
                if (!$obj->removeCouponBrand($recordId, $itemId)) {
                    LibHelper::exitWithError($obj->getError(), true);
                }
                break;
            case 'subscription':
                if (!$obj->removeCouponPlan($recordId, $itemId)) {
                    LibHelper::exitWithError($obj->getError(), true);
                }
                break;
            default:
                LibHelper::exitWithError($this->str_invalid_request, true);
                break;
        }
        $this->set('msg', $this->str_delete_record);
        $this->_template->render(false, false, 'json-success.php');
    }

    private function rowsData()
    {
        $recordId = FatApp::getPostedData('recordId', FatUtility::VAR_INT, 0);
        $pagesize = FatApp::getConfig('CONF_ADMIN_PAGESIZE', FatUtility::VAR_INT, 10);
        $page = FatApp::getPostedData('page', FatUtility::VAR_INT, 1);
        $page = ($page <= 0) ? 1 : $page;

        $srch = CouponHistory::getSearchObject();
        $srch->joinTable(Orders::DB_TBL, 'INNER JOIN', 'o.order_id = couponhistory_order_id', 'o');
        $srch->joinTable(User::DB_TBL, 'LEFT OUTER JOIN', 'user_id = couponhistory_user_id');
        $srch->joinTable(Credential::DB_TBL, 'LEFT OUTER JOIN', 'credential_user_id = user_id');
        $srch->addCondition('couponhistory_coupon_id', '=', $recordId);
        $srch->addMultipleFields(array('couponhistory_id', 'couponhistory_coupon_id', 'order_number as couponhistory_order_no', 'couponhistory_order_id', 'couponhistory_user_id', 'couponhistory_amount', 'couponhistory_added_on', 'CONCAT(user_name, " (", credential_username, ")") as user_name', 'user_id'));
        $srch->addOrder('couponhistory_added_on', 'DESC');

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

    public function usesHistory()
    {
        $recordId = FatApp::getPostedData('recordId', FatUtility::VAR_INT, 0);

        $couponData = DiscountCoupons::getAttributesByLangId($this->siteLangId, $recordId, ['COALESCE(coupon_title, coupon_identifier) as coupon_title', 'coupon_code'], applicationConstants::JOIN_RIGHT);
        if ($couponData == false) {
            LibHelper::exitWithError($this->str_invalid_request, true);
        }
        $this->rowsData();

        $this->set('couponData', $couponData);

        $this->set('html', $this->_template->render(false, false, NULL, true));
        $this->_template->render(false, false, 'json-success.php', true, false);
    }

    public function getRows()
    {
        $this->rowsData();
        $this->set('html', $this->_template->render(false, false, NULL, true));
        $this->_template->render(false, false, 'json-success.php', true, false);
    }

    private function getMediaForm($recordId = 0)
    {
        $recordId = FatUtility::int($recordId);
        $frm = new Form('frmCouponMedia');
        $frm->addHiddenField('', 'coupon_id', $recordId);
        $frm->addHiddenField('', 'file_type', AttachedFile::FILETYPE_DISCOUNT_COUPON_IMAGE);
        $frm->addHiddenField('', 'min_width');
        $frm->addHiddenField('', 'min_height');

        $languagesArr = applicationConstants::getAllLanguages();
        if (count($languagesArr) > 1) {
            $frm->addSelectBox(Labels::getLabel('FRM_LANGUAGE', $this->siteLangId), 'lang_id', $languagesArr, '', array(), '');
        } else {
            $lang_id = array_key_first($languagesArr);
            $frm->addHiddenField('', 'lang_id', $lang_id);
        }

        $frm->addHtml('', 'coupon_image', '');
        return $frm;
    }

    public function media($recordId)
    {
        $recordId = FatUtility::int($recordId);
        $couponData = DiscountCoupons::getAttributesById($recordId);

        if (false == $couponData) {
            LibHelper::exitWithError($this->str_invalid_request_id, true);
        }
        $getImageDimensions = ImageDimension::getData(ImageDimension::TYPE_COUPON, ImageDimension::VIEW_NORMAL);
        $frm = $this->getMediaForm($recordId);
        $this->set('getImageDimensions', $getImageDimensions);
        $this->set('recordId', $recordId);
        $this->set('frm', $frm);
        $this->set('displayFooterButtons', false);
        $this->set('activeGentab', false);
        $this->set('formTitle', Labels::getLabel('LBL_DISCOUNT_COUPON_SETUP', $this->siteLangId));
        $this->set('html', $this->_template->render(false, false, NULL, true));
        $this->_template->render(false, false, 'json-success.php', true, false);
    }

    public function images($recordId, $langId = 0)
    {
        $languages = Language::getAllNames();
        if (count($languages) <= 1) {
            $langId =  array_key_first($languages);
        }

        $recordId = FatUtility::int($recordId);
        if (!$recordId) {
            LibHelper::exitWithError($this->str_invalid_request_id, true);
        }

        if (!$row = DiscountCoupons::getAttributesById($recordId, 'coupon_id')) {
            LibHelper::exitWithError($this->str_invalid_request_id, true);
        }

        $images = AttachedFile::getMultipleAttachments(AttachedFile::FILETYPE_DISCOUNT_COUPON_IMAGE, $recordId, 0, $langId, (1 == count($languages)), 0, 1);
        $this->set('languages', Language::getAllNames());
        $this->set('images', $images);
        $this->set('recordId', $recordId);
        $this->set('canEdit', $this->objPrivilege->canEditDiscountCoupons($this->admin_id, true));
        $this->set('html', $this->_template->render(false, false, NULL, true));
        $this->_template->render(false, false, 'json-success.php', true, false);
    }

    public function uploadMedia()
    {
        $this->objPrivilege->canEditDiscountCoupons();

        $recordId = FatApp::getPostedData('coupon_id', FatUtility::VAR_INT, 0);
        $langId = FatApp::getPostedData('lang_id', FatUtility::VAR_INT, 0);
        if (1 > $recordId) {
            LibHelper::exitWithError($this->str_invalid_request_id, true);
        }

        $languages = Language::getAllNames();
        if (count($languages) <= 1) {
            $langId = array_key_first($languages);
        }

        if ($recordId < 1) {
            LibHelper::exitWithError($this->str_invalid_request, true);
        }
        $post = FatApp::getPostedData();
        if (empty($post)) {
            LibHelper::exitWithError(Labels::getLabel('ERR_INVALID_REQUEST_OR_FILE_NOT_SUPPORTED', $this->siteLangId), true);
        }

        $fileType = $post['file_type'];
        if ($fileType != AttachedFile::FILETYPE_DISCOUNT_COUPON_IMAGE) {
            LibHelper::exitWithError($this->str_invalid_request, true);
        }

        if (!is_uploaded_file($_FILES['cropped_image']['tmp_name'])) {
            LibHelper::exitWithError(Labels::getLabel('ERR_PLEASE_SELECT_A_FILE', $this->siteLangId), true);
        }

        $fileHandlerObj = new AttachedFile();
        if (false === $fileHandlerObj->deleteFile($fileType, $recordId, 0, 0, $langId)) {
            LibHelper::exitWithError($fileHandlerObj->getError(), true);
        }

        if (!$res = $fileHandlerObj->saveAttachment(
            $_FILES['cropped_image']['tmp_name'],
            $fileType,
            $recordId,
            0,
            $_FILES['cropped_image']['name'],
            -1,
            false,
            $langId
        )) {
            LibHelper::exitWithError($fileHandlerObj->getError(), true);
        }
        $this->set('msg', Labels::getLabel('MSG_IMAGE_UPLOADED_SUCCESSFULLY', $this->siteLangId));
        $this->set('recordId', $recordId);
        $this->_template->render(false, false, 'json-success.php');
    }

    public function deleteImage($recordId = 0, $afile_id = 0, $langId = 0)
    {
        $this->objPrivilege->canEditDiscountCoupons();
        $recordId = FatUtility::int($recordId);
        $afile_id = FatUtility::int($afile_id);
        $langId = FatUtility::int($langId);
        if (!$recordId) {
            LibHelper::exitWithError($this->str_invalid_request, true);
        }

        $languages = Language::getAllNames();
        if (1 == count($languages)) {
            $afile_id = 0;
            $langId = -1;
        }

        $fileHandlerObj = new AttachedFile();
        if (!$fileHandlerObj->deleteFile(AttachedFile::FILETYPE_DISCOUNT_COUPON_IMAGE, $recordId, $afile_id, 0, $langId)) {
            LibHelper::exitWithError($fileHandlerObj->getError(), true);
        }
        $this->set('msg', Labels::getLabel('MSG_DELETED_SUCCESSFULLY', $this->siteLangId));
        $this->_template->render(false, false, 'json-success.php');
    }

    protected function getFormColumns(): array
    {
        $tblHeadingCols = CacheHelper::get('discountCouponsTblHeadingCols' . $this->siteLangId, CONF_DEF_CACHE_TIME, '.txt');
        if ($tblHeadingCols) {
            return json_decode($tblHeadingCols, true);
        }

        $arr = [
            'select_all' => Labels::getLabel('LBL_SELECT_ALL', $this->siteLangId),
            /* 'listSerial' => Labels::getLabel('LBL_SR._NO', $this->siteLangId), */
            'coupon_title' => Labels::getLabel('LBL_COUPON_TITLE', $this->siteLangId),
            'coupon_code' => Labels::getLabel('LBL_COUPON_CODE', $this->siteLangId),
            'coupon_type' => Labels::getLabel('LBL_COUPON_TYPE', $this->siteLangId),
            'coupon_discount_value' => Labels::getLabel('LBL_COUPON_DISCOUNT', $this->siteLangId),
            'coupon_start_date' => Labels::getLabel('LBL_AVAILABLE_FROM', $this->siteLangId),
            'coupon_end_date' => Labels::getLabel('LBL_AVAILABLE_TO', $this->siteLangId),
            'coupon_alive' => Labels::getLabel('LBL_ALIVE', $this->siteLangId),
            'coupon_active' => Labels::getLabel('LBL_STATUS', $this->siteLangId),
            'action' => Labels::getLabel('LBL_ACTION_BUTTONS', $this->siteLangId),
        ];
        CacheHelper::create('discountCouponsTblHeadingCols' . $this->siteLangId, json_encode($arr), CacheHelper::TYPE_LABELS);
        return $arr;
    }

    protected function getDefaultColumns(): array
    {
        return [
            'select_all',
            /*   'listSerial', */
            'coupon_title',
            'coupon_code',
            'coupon_type',
            'coupon_discount_value',
            'coupon_start_date',
            'coupon_end_date',
            'coupon_alive',
            'coupon_active',
            'action',
        ];
    }

    protected function excludeKeysForSort($fields = []): array
    {
        return array_diff($fields, ['coupon_alive'], Common::excludeKeysForSort());
    }

    public function getBreadcrumbNodes($action)
    {
        switch ($action) {
            case 'links':
                $pageData = PageLanguageData::getAttributesByKey($this->pageKey, $this->siteLangId);
                $pageTitle = $pageData['plang_title'] ?? Labels::getLabel('LBL_DISCOUNT_COUPONS', $this->siteLangId);
                $this->nodes = [
                    ['title' => $pageTitle, 'href' => UrlHelper::generateUrl('DiscountCoupons')],
                ];

                $url = FatApp::getQueryStringData('url');
                $urlParts = explode('/', $url);
                $title = Labels::getLabel('LBL_LINKS', $this->siteLangId);
                if (isset($urlParts[2])) {
                    $couponData = DiscountCoupons::getAttributesByLangId($this->siteLangId, $urlParts[2], ['COALESCE(coupon_title, coupon_identifier) as coupon_title'], applicationConstants::JOIN_RIGHT);
                    if (!empty($couponData)) {
                        $this->nodes[] = ['title' => current($couponData)];
                    }
                }
                $this->nodes[] = ['title' => $title];

                break;
            default:
                parent::getBreadcrumbNodes($action);
                break;
        }
        return $this->nodes;
    }
}
