<?php

trait Options
{
    public function options()
    {
        $this->userPrivilege->canViewProductOptions(UserAuthentication::getLoggedUserId());
        $canAddCustomProd = FatApp::getConfig('CONF_ENABLED_SELLER_CUSTOM_PRODUCT', FatUtility::VAR_INT, 0);
        if (1 > $canAddCustomProd || FatApp::getConfig('CONF_WITHOUT_PROD_VARIANTS', FatUtility::VAR_INT, 0)) {
            FatApp::redirectUser(UrlHelper::generateUrl('Seller'));
        }
        $this->set('canEdit', $this->userPrivilege->canEditProductOptions(UserAuthentication::getLoggedUserId(), true));
        $frmSearch = $this->getSearchForm();
        $this->set('deleteButton', true);
        $this->set('keywordPlaceholder', Labels::getLabel('LBL_SEARCH_BY_OPTION_NAME', $this->siteLangId));
        $this->set("frmSearch", $frmSearch);
        $this->_template->addJs('js/jscolor.js');
        $this->_template->addJs('js/jquery.tablednd.js');
        $this->_template->render(true, true);
    }

    private function getSearchForm()
    {
        $frm = new Form('frmOptionSearch', array('id' => 'frmOptionSearch'));
        $frm->addTextBox('', 'keyword');
        $frm->addHiddenField('', 'total_record_count');

        HtmlHelper::addSearchButton($frm);
        return $frm;
    }

    public function searchOptions()
    {
        $pagesize = FatApp::getConfig('CONF_PAGE_SIZE', FatUtility::VAR_INT, 10);
        $frmSearch = $this->getSearchForm();

        $data = FatApp::getPostedData();
        $page = (empty($data['page']) || $data['page'] <= 0) ? 1 : $data['page'];
        $page = (empty($page) || $page <= 0) ? 1 : $page;
        $page = FatUtility::int($page);
        $post = $frmSearch->getFormDataFromArray($data);
        $userId = $this->userParentId;
        $srch = Option::getSearchObject($this->siteLangId);
        if (!empty($post['keyword'])) {
            $condition = $srch->addCondition('o.option_identifier', 'like', '%' . $post['keyword'] . '%');
            $condition->attachCondition('ol.option_name', 'like', '%' . $post['keyword'] . '%', 'OR');
        }
        $srch->addCondition('o.option_seller_id', '=', 'mysql_func_' . $userId, 'AND', true);
        $this->setRecordCount(clone $srch, $pagesize, $page, $post);
        $srch->doNotCalculateRecords();
        $srch->setPageNumber($page);
        $srch->setPageSize($pagesize);
        $srch->addMultipleFields(array("o.*", "IFNULL( ol.option_name, o.option_identifier ) as option_name"));

        $rs = $srch->getResultSet();
        $records = FatApp::getDb()->fetchAll($rs);

        $this->set('canEdit', $this->userPrivilege->canEditProductOptions(UserAuthentication::getLoggedUserId(), true));
        $this->set("ignoreOptionValues", Option::ignoreOptionValues());
        $this->set("arrListing", $records);
        $this->set('postedData', $post);
        $this->set("frmSearch", $frmSearch);
        $this->_template->render(false, false);
    }

    public function setupOptions()
    {
        $this->userPrivilege->canEditProductOptions(UserAuthentication::getLoggedUserId());
        $frm = $this->getForm();
        $post = $frm->getFormDataFromArray(FatApp::getPostedData());

        if (false === $post) {
            FatUtility::dieJsonError(current($frm->getValidationErrors()));
        }

        $option_id = FatUtility::int($post['option_id']);
        if ($option_id > 0) {
            if (!UserPrivilege::canSellerEditOption($this->userParentId, $option_id)) {
                FatUtility::dieJsonError($this->str_invalid_request);
            }
        }
        unset($post['option_id']);

        $optionObj = new Option($option_id);
        /* if($option_id == 0){
          $displayOrder = $optionObj->getMaxOrder();
          $post['option_display_order'] = $displayOrder;
          } */
        $userId = $this->userParentId;
        $post['option_seller_id'] = $userId;
        $post[$optionObj::tblFld('identifier')] = $post[$optionObj::tblFld('name')] . '-' . $userId;
        $optionObj->assignValues($post);
        if (!$optionObj->save()) {
            FatUtility::dieJsonError(Labels::getLabel('ERR_OPTION_IDENTIFIER_ALREADY_EXISTS', $this->siteLangId));
        }

        $option_id = ($option_id > 0) ? $option_id : $optionObj->getMainTableRecordId();

        $option_type = FatUtility::int($post['option_type']);

        if (in_array($option_type, Option::ignoreOptionValues())) {
            $optionValueObj = new OptionValue();
            $arr = $optionValueObj->getAttributesByOptionId($option_id, array('optionvalue_id'));
            foreach ($arr as $val) {
                $optionValueObj = new OptionValue($val['optionvalue_id']);
                $optionValueObj->deleteRecord(true);
            }
        }

        $this->setLangData($optionObj, [$optionObj::tblFld('name') => $post[$optionObj::tblFld('name')]]);

        $this->set('optionId', $option_id);
        $this->_template->render(false, false, 'json-success.php');
    }

    public function optionLangSetup()
    {
        $this->userPrivilege->canEditProductOptions(UserAuthentication::getLoggedUserId());

        $post = FatApp::getPostedData();
        $lang_id = $post['lang_id'];
        $languages = Language::getAllNames();
        if (count($languages) > 1) {
            $lang_id = $post['lang_id'];
        } else {
            $lang_id = array_key_first($languages);
            $post['lang_id'] = $lang_id;
        }

        $option_id = FatUtility::int($post['option_id']);

        if ($option_id == 0 || $lang_id == 0) {
            FatUtility::dieJsonError($this->str_invalid_request);
        }

        if ($option_id > 0 && !UserPrivilege::canSellerEditOption($this->userParentId, $option_id)) {
            FatUtility::dieJsonError($this->str_invalid_request);
        }

        $frm = $this->getOptionLangForm($option_id, $lang_id);
        $post = $frm->getFormDataFromArray($post);
        if (false === $post) {
            FatUtility::dieJsonError(current($frm->getValidationErrors()));
        }

        $recordObj = new Option($option_id);
        $this->setLangData($recordObj, [$recordObj::tblFld('name') => $post[$recordObj::tblFld('name')]], $lang_id);

        $this->set('optionId', $option_id);
        $this->_template->render(false, false, 'json-success.php');
    }

    public function optionForm($option_id = 0)
    {
        $option_id = FatUtility::int($option_id);
        $frm = $this->getForm($option_id);
        $identifier = '';
        if (0 < $option_id) {
            if (!UserPrivilege::canSellerEditOption($this->userParentId, $option_id)) {
                FatUtility::dieJsonError($this->str_invalid_request);
            }

            $data = Option::getAttributesByLangId(CommonHelper::getDefaultFormLangId(), $option_id, ['*', 'IFNULL(option_name,option_identifier) as option_name'], applicationConstants::JOIN_RIGHT);
            if ($data === false) {
                FatUtility::dieWithError(Labels::getLabel('MSG_INVALID_REQUEST', $this->siteLangId));
            }
            $identifier = $data['option_identifier'];
            $frm->fill($data);
        }
        $this->set('frm', $frm);
        $this->set('identifier', $identifier);
        $this->set('option_id', $option_id);
        $this->set('languages', Language::getAllNames());
        $this->_template->render(false, false);
    }

    public function optionLangForm($option_id = 0, $langId = 0, $autoFillLangData = 0)
    {
        $option_id = FatUtility::int($option_id);
        $langId = FatUtility::int($langId);
        $autoFillLangData = FatUtility::int($autoFillLangData);

        if (1 > $option_id || 1 > $langId) {
            FatUtility::dieJsonError($this->str_invalid_request);
        }

        if (!UserPrivilege::canSellerEditOption($this->userParentId, $option_id)) {
            FatUtility::dieJsonError($this->str_invalid_request);
        }

        $langFrm = $this->getOptionLangForm($option_id, $langId);
        if (0 < $autoFillLangData) {
            $updateLangDataobj = new TranslateLangData(Option::DB_TBL_LANG);
            $translatedData = $updateLangDataobj->getTranslatedData($option_id, $langId, CommonHelper::getDefaultFormLangId());
            if (false === $translatedData) {
                LibHelper::exitWithError($updateLangDataobj->getError(), true);
            }
            $langData = current($translatedData);
        } else {
            $langData = Option::getAttributesByLangId($langId, $option_id, NULL, applicationConstants::JOIN_RIGHT);
        }
        if ($langData) {
            $langFrm->fill($langData);
        }

        $this->set('langFrm', $langFrm);
        $this->set('option_id', $option_id);
        $this->set('langId', $langId);
        $this->set('formLayout', Language::getLayoutDirection($langId));
        $this->set('languages', Language::getAllNames());
        $this->_template->render(false, false);
    }

    protected function getOptionLangForm($recordId = 0, $langId = 0)
    {
        $this->checkEditPrivilege();
        $langId = 1 > $langId ? $this->siteLangId : $langId;

        $frm = new Form('frmOptionLang');
        $frm->addHiddenField('', Option::DB_TBL_PREFIX . 'id', $recordId);
        $frm->addSelectBox(Labels::getLabel('FRM_LANGUAGE', $langId), 'lang_id', Language::getDropDownList(CommonHelper::getDefaultFormLangId()), $langId, array(), '');
        $frm->addRequiredField(Labels::getLabel('FRM_OPTION_NAME', $langId), Option::DB_TBL_PREFIX . 'name');
        return $frm;
    }

    private function getForm($option_id = 0)
    {
        /* Used when option created from product form */
        $post = FatApp::getPostedData();

        $option_id = FatUtility::int($option_id);
        $frm = new Form('frmOptions', array('id' => 'frmOptions'));
        $frm->addHiddenField('', 'option_id', $option_id);
        $frm->addRequiredField(Labels::getLabel('FRM_OPTION_NAME', $this->siteLangId), 'option_name');
        $frm->addHiddenField('', 'option_type', Option::OPTION_TYPE_SELECT);

        $yesNoArr = applicationConstants::getYesNoArr($this->siteLangId);
        $frm->addSelectBox(
            Labels::getLabel('FRM_OPTION_HAVE_SEPARATE_IMAGE', $this->siteLangId),
            'option_is_separate_images',
            $yesNoArr,
            0,
            array(),
            ''
        )->requirements()->setRequired();

        $frm->addSelectBox(Labels::getLabel('FRM_OPTION_IS_COLOR', $this->siteLangId), 'option_is_color', $yesNoArr, 0, array(), '')->requirements()->setRequired();

        $languageArr = Language::getDropDownList();
        $translatorSubscriptionKey = FatApp::getConfig('CONF_TRANSLATOR_SUBSCRIPTION_KEY', FatUtility::VAR_STRING, '');
        if (!empty($translatorSubscriptionKey) && 1 < count($languageArr)) {
            $frm->addCheckBox(Labels::getLabel('FRM_UPDATE_OTHER_LANGUAGES_DATA', $this->siteLangId), 'auto_update_other_langs_data', 1, array(), false, 0);
        }

        return $frm;
    }

    public function canSetValue()
    {
        $hideBox = false;
        $post = FatApp::getPostedData();
        // var_dump($post);exit;
        $option_type = FatUtility::int($post['optionType']);
        if (in_array($option_type, Option::ignoreOptionValues())) {
            $hideBox = true;
        }
        $this->set('hideBox', $hideBox);
        $this->_template->render(false, false, 'json-success.php');
    }

    public function bulkOptionsDelete()
    {
        $this->userPrivilege->canEditProductOptions(UserAuthentication::getLoggedUserId());
        $optionId_arr = FatApp::getPostedData('option_id');
        if (is_array($optionId_arr) && count($optionId_arr)) {
            foreach ($optionId_arr as $option_id) {
                $this->deleteOption(FatUtility::int($option_id));
            }
            FatUtility::dieJsonSuccess(
                Labels::getLabel('MSG_RECORD_DELETED_SUCCESSFULLY', $this->siteLangId)
            );
        }
        FatUtility::dieWithError(
            Labels::getLabel('MSG_INVALID_REQUEST', $this->siteLangId)
        );
    }

    public function deleteSellerOption()
    {
        $this->userPrivilege->canEditProductOptions(UserAuthentication::getLoggedUserId());
        $option_id = FatApp::getPostedData('id', FatUtility::VAR_INT, 0);
        $this->deleteOption($option_id);

        FatUtility::dieJsonSuccess(
            Labels::getLabel('MSG_RECORD_DELETED_SUCCESSFULLY', $this->siteLangId)
        );
    }

    private function deleteOption($option_id)
    {
        $this->userPrivilege->canEditProductOptions(UserAuthentication::getLoggedUserId());
        if ($option_id < 1 || empty($option_id)) {
            FatUtility::dieJsonError($this->str_invalid_request);
        }

        if (!UserPrivilege::canSellerEditOption($this->userParentId, $option_id)) {
            FatUtility::dieJsonError($this->str_invalid_request);
        }

        $optionObj = new Option($option_id);
        if (!$optionObj->canRecordMarkDelete($option_id)) {
            FatUtility::dieJsonError($this->str_invalid_request);
        }

        if ($optionObj->isLinkedWithProduct($option_id)) {
            FatUtility::dieJsonError(Labels::getLabel('ERR_THIS_OPTION_IS_LINKED_WITH_PRODUCT', $this->siteLangId));
        }

        $optionIdentifier = Option::getAttributesById($option_id, Option::tblFld('identifier'));
        $optionObj->assignValues(array(Option::tblFld('identifier') => $optionIdentifier . '-' . $option_id, Option::tblFld('deleted') => 1));
        if (!$optionObj->save()) {
            FatUtility::dieJsonError($optionObj->getError());
        }
    }

    public function autoCompleteOptions()
    {
        $page = FatApp::getPostedData('page', FatUtility::VAR_INT, 1);
        if ($page < 2) {
            $page = 1;
        }

        $post = FatApp::getPostedData();
        $userId = $this->userParentId;
        $langId = FatApp::getPostedData('langId', FatUtility::VAR_INT, $this->siteLangId);
        $srch = Option::getSearchObject($langId);
        $srch->addOrder('option_identifier');

        $cnd = $srch->addCondition('option_seller_id', '=', 'mysql_func_' . $userId, 'AND', true);
        $cnd->attachCondition('option_seller_id', '=', 0, 'OR');
        $srch->addMultipleFields(array('option_id as id, COALESCE(option_name, option_identifier) as option_name', 'option_identifier', 'option_is_separate_images'));

        $srch->setPageNumber($page);
        $srch->setPageSize(20);

        $disAllowOptions = FatApp::getPostedData('disAllowOptions');
        if (is_array($disAllowOptions)) {
            $srch->addCondition('option_id', 'NOT IN', $disAllowOptions);
        }

        $doNotIncludeImageOption = FatApp::getPostedData('doNotIncludeImageOption', FatUtility::VAR_INT, 0);
        if (0 < $doNotIncludeImageOption) {
            $srch->addCondition('option_is_separate_images', '=', applicationConstants::NO);
        }

        if (!empty($post['keyword'])) {
            $cnd = $srch->addCondition('option_name', 'LIKE', '%' . $post['keyword'] . '%');
            $cnd->attachCondition('option_identifier', 'LIKE', '%' . $post['keyword'] . '%', 'OR');
        }
        $options = FatApp::getDb()->fetchAll($srch->getResultSet());
        $results = [];
        foreach ($options as $option) {
            $optionName = $option['option_name'];
            if ($option['option_name']  != $option['option_identifier']) {
                $optionName .= "(" . $option['option_identifier'] . ")";
            }
            $results[] = ['id' => $option['id'], 'text' => $optionName, 'option_is_separate_images' => $option['option_is_separate_images']];
        }

        $json = array(
            'pageCount' => $srch->pages(),
            'results' => $results
        );
        die(json_encode($json));
    }

    public function autoCompleteValues()
    {
        $post = FatApp::getPostedData();

        $langId = FatApp::getPostedData('langId', FatUtility::VAR_INT, $this->siteLangId);
        $optionId = FatApp::getPostedData('optionId', FatUtility::VAR_INT, 0);

        $srch = OptionValue::getSearchObject($langId, true);
        $srch->addCondition('ov.optionvalue_option_id', '=', 'mysql_func_' . $optionId, 'AND', true);
        $srch->addMultipleFields(array('optionvalue_id as id, COALESCE(optionvalue_name, optionvalue_identifier) as text'));

        if (!empty($post['keyword'])) {
            $cnd = $srch->addCondition('optionvalue_identifier', 'LIKE', '%' . $post['keyword'] . '%');
            $cnd->attachCondition('optionvalue_name', 'LIKE', '%' . $post['keyword'] . '%', 'OR');
        }

        if (FatApp::getPostedData('doNotLimitRecords', FatUtility::VAR_INT, 1)) {
            $pagesize = 20;
            $page = FatApp::getPostedData('page', FatUtility::VAR_INT, 1);
            if ($page < 2) {
                $page = 1;
            }
            $srch->setPageNumber($page);
            $srch->setPageSize($pagesize);
        } else {
            $srch->doNotLimitRecords();
        }

        $options = FatApp::getDb()->fetchAll($srch->getResultSet());

        $json = array(
            'pageCount' => $srch->pages(),
            'results' => $options
        );
        die(FatUtility::convertToJson($json));
    }
}
