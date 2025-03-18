<?php

class TestimonialsController extends ListingBaseController
{
    protected string $modelClass = 'Testimonial';
    protected $pageKey = 'MANAGE_TESTIMONIAL';

    public function __construct($action)
    {
        parent::__construct($action);
        $this->objPrivilege->canViewTestimonial();
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
            'testimoniallang_testimonial_id',
            'testimoniallang_lang_id',
            'testimonial_title',
            'testimonial_text'
        ];
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
            $this->set("canEdit", $this->objPrivilege->canEditTestimonial($this->admin_id, true));
        } else {
            $this->objPrivilege->canEditTestimonial();
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
        $actionItemsData['performBulkAction'] = true;
        $actionItemsData['statusButtons'] = true;
        $actionItemsData['deleteButton'] = true;

        $this->set('pageData', $pageData);
        $this->set('pageTitle', $pageTitle);
        $this->set('actionItemsData', $actionItemsData);
        $this->set("frmSearch", $frmSearch);
        $this->set('defaultColumns', $this->getDefaultColumns());
        $this->set('keywordPlaceholder', Labels::getLabel('FRM_SEARCH_BY_TESTIMONIAL_TITLE', $this->siteLangId));
        $this->checkEditPrivilege(true);
        $this->getListingData();

        $this->_template->addCss('css/cropper.css');
        $this->_template->addJs(['js/cropper.js', 'js/cropper-main.js', 'testimonials/page-js/index.js']);
        $this->_template->render(true, true, '_partial/listing/index.php');
    }


    public function search()
    {
        $this->getListingData();
        $jsonData = [
            'listingHtml' => $this->_template->render(false, false, 'testimonials/search.php', true),
            'paginationHtml' => $this->_template->render(false, false, '_partial/listing/listing-foot.php', true)
        ];
        LibHelper::exitWithSuccess($jsonData, true);
    }

    public function getListingData()
    {
        $pageSize = applicationConstants::getPageSize(FatApp::getPostedData('pageSize', FatUtility::VAR_INT));
        $data = FatApp::getPostedData();
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
        $searchForm = $this->getSearchForm($fields);
        $post = $searchForm->getFormDataFromArray($data);
        $srch = Testimonial::getSearchObject($this->siteLangId, false);

        if (isset($post['keyword']) && '' != $post['keyword']) {
            $condition = $srch->addCondition('testimonial_title', 'like', '%' . $post['keyword'] . '%');
            $condition->attachCondition('testimonial_text', 'like', '%' . $post['keyword'] . '%', 'OR');
        }

        $srch->addMultipleFields(array('t.*', 'COALESCE(t_l.testimonial_title, t.testimonial_identifier) as testimonial_title', 't_l.testimonial_text'));
        $srch->addOrder('testimonial_active', 'desc');
        $srch->addOrder($sortBy, $sortOrder);
        $page = (empty($data['page']) || $data['page'] <= 0) ? 1 : $data['page'];
        $records = FatApp::getDb()->fetchAll($srch->getResultSet());
        $this->set("arrListing", $records);
        $this->set('pageCount', $srch->pages());
        $this->set('recordCount', $srch->recordCount());
        $this->set('page', $page);
        $this->set('pageSize', $pageSize);
        $this->set('postedData', $post);
        $this->set('sortBy', $sortBy);
        $this->set('sortOrder', $sortOrder);
        $this->set('fields', $fields);
        $this->set('siteLangId', $this->siteLangId);
        $this->set('allowedKeysForSorting', $allowedKeysForSorting);
        $this->checkEditPrivilege(true);
    }

    private function getForm(int $testimonialId = 0)
    {
        $frm = new Form('frmTestimonial');
        $frm->addHiddenField('', 'testimonial_id', $testimonialId);
        $frm->addRequiredField(Labels::getLabel('FRM_TESTIMONIAL_TITLE', $this->siteLangId), 'testimonial_title');
        $frm->addRequiredField(Labels::getLabel('FRM_TESTIMONIAL_USER_NAME', $this->siteLangId), 'testimonial_user_name');

        $fld = $frm->addTextarea(Labels::getLabel('FRM_TESTIMONIAL_TEXT', $this->siteLangId), 'testimonial_text');
        $fld->requirements()->setRequired();
        $frm->addCheckBox(Labels::getLabel('FRM_STATUS', $this->siteLangId), 'testimonial_active', applicationConstants::ACTIVE, [], true, applicationConstants::INACTIVE);
        $languageArr = Language::getDropDownList();
        $translatorSubscriptionKey = FatApp::getConfig('CONF_TRANSLATOR_SUBSCRIPTION_KEY', FatUtility::VAR_STRING, '');
        if (!empty($translatorSubscriptionKey) && 1 < count($languageArr)) {
            $frm->addCheckBox(Labels::getLabel('FRM_UPDATE_OTHER_LANGUAGES_DATA', $this->siteLangId), 'auto_update_other_langs_data', 1, array(), false, 0);
        }
        return $frm;
    }

    public function form()
    {
        $recordId = FatApp::getPostedData('recordId', FatUtility::VAR_INT, 0);
        $frm = $this->getForm($recordId);

        if (0 < $recordId) {
            $fields = [
                'testimonial_id', 'testimonial_identifier', 'testimonial_active', 'testimonial_user_name', 'IFNULL(testimonial_title, testimonial_identifier) AS testimonial_title', 'testimonial_text'
            ];
            $data = Testimonial::getAttributesByLangId($this->siteLangId, $recordId, $fields, applicationConstants::JOIN_RIGHT);

            if ($data === false) {
                LibHelper::exitWithError($this->str_invalid_request, true);
            }
            $frm->fill($data);
        }
        $this->set('languages', Language::getAllNames());
        $this->set('recordId', $recordId);
        $this->set('frm', $frm);
        $this->set('formTitle', Labels::getLabel('LBL_TESTIMONIAL_SETUP', $this->siteLangId));
        $this->set('html', $this->_template->render(false, false, NULL, true));
        $this->_template->render(false, false, 'json-success.php', true, false);
    }

    public function setup()
    {
        $this->checkEditPrivilege();
        $frm = $this->getForm();
        $post = $frm->getFormDataFromArray(FatApp::getPostedData());
        if (false === $post) {
            LibHelper::exitWithError(current($frm->getValidationErrors()), true);
        }
        $recordId = $post['testimonial_id'];
        unset($post['testimonial_id']);
        if ($recordId == 0) {
            $post['testimonial_added_on'] = date('Y-m-d H:i:s');
        }
        $record = new Testimonial($recordId);

        $post['testimonial_identifier'] =  $post['testimonial_title'];
        $LangdataArray = [
            'testimonial_title' => $post['testimonial_title'],
            'testimonial_text' => $post['testimonial_text'],
            'testimoniallang_testimonial_id' => $recordId,
            'testimoniallang_lang_id' => $this->siteLangId,
        ];

        unset($post['testimonial_title'], $post['testimonial_text']);
        $record->assignValues($post);
        if (!$record->save()) {
            $msg = $record->getError();
            if (false !== strpos(strtolower($msg), 'duplicate')) {
                $msg = Labels::getLabel('ERR_DUPLICATE_RECORD_NAME', $this->siteLangId);
            }
            LibHelper::exitWithError($msg, true);
        }

        $recordId = $record->getMainTableRecordId();

        if (!$record->updateLangData($this->siteLangId, $LangdataArray)) {
            LibHelper::exitWithError($record->getError(), true);
        }

        $autoUpdateOtherLangsData = FatApp::getPostedData('auto_update_other_langs_data', FatUtility::VAR_INT, 0);
        if (0 < $autoUpdateOtherLangsData) {
            $updateLangDataobj = new TranslateLangData(Testimonial::DB_TBL_LANG);
            if (false === $updateLangDataobj->updateTranslatedData($recordId, CommonHelper::getDefaultFormLangId())) {
                LibHelper::exitWithError($updateLangDataobj->getError(), true);
            }
        }

        $newTabLangId = 0;
        $languages = Language::getDropDownList(CommonHelper::getDefaultFormLangId());
        if (0 < count($languages)) {
            foreach ($languages as $langId => $langName) {
                if (!Brand::getAttributesByLangId($langId, $recordId)) {
                    $newTabLangId = $langId;
                    break;
                }
            }
        }

        if ($newTabLangId == 0 && !$this->isMediaUploaded($recordId)) {
            $this->set('openMediaForm', true);
        }
        
        $this->set('msg', $this->str_setup_successful);
        $this->set('recordId', $recordId);
        $this->set('langId', $newTabLangId);
        $this->_template->render(false, false, 'json-success.php');
    }

    private function getLangForm($testimonialId = 0, $langId = 0)
    {

        $frm = new Form('frmTestimonialLang');
        $frm->addHiddenField('', 'testimonial_id', $testimonialId);
        $frm->addSelectBox(Labels::getLabel('FRM_LANGUAGE', $langId), 'lang_id', Language::getDropDownList(CommonHelper::getDefaultFormLangId()), $langId, array(), '');
        $frm->addRequiredField(Labels::getLabel('FRM_TESTIMONIAL_TITLE', $langId), 'testimonial_title');
        $fld = $frm->addTextarea(Labels::getLabel('FRM_TESTIMONIAL_TEXT', $langId), 'testimonial_text');
        $fld->requirements()->setRequired();    
    
        return $frm;
    }

    public function langForm($autoFillLangData = 0)
    {
        $recordId = FatApp::getPostedData('recordId', FatUtility::VAR_INT, 0);
        $langId = FatApp::getPostedData('langId', FatUtility::VAR_INT, 0);
        if ($recordId == 0 || $langId == 0) {
            LibHelper::exitWithError($this->str_invalid_request, true);
        }

        $langFrm = $this->getLangForm($recordId, $langId);
        if (0 < $autoFillLangData) {
            $updateLangDataobj = new TranslateLangData(Testimonial::DB_TBL_LANG);
            $translatedData = $updateLangDataobj->getTranslatedData($recordId, $langId, CommonHelper::getDefaultFormLangId() );
            if (false === $translatedData) {
                LibHelper::exitWithError($updateLangDataobj->getError(), true);
            }
            $langData = current($translatedData);
        } else {
            $langData = Testimonial::getAttributesByLangId($langId, $recordId);
        }

        if ($langData) {
            $langFrm->fill($langData);
        }

        $this->set('recordId', $recordId);
        $this->set('lang_id', $langId);
        $this->set('langFrm', $langFrm);
        $this->set('formLayout', Language::getLayoutDirection($langId));
        $this->set('formTitle', Labels::getLabel('LBL_TESTIMONIAL_SETUP', $this->siteLangId));
        $this->set('html', $this->_template->render(false, false, NULL, true));
        $this->_template->render(false, false, 'json-success.php', true, false);
    }

    public function langSetup()
    {
        $this->checkEditPrivilege();
        $post = FatApp::getPostedData();

        $recordId = $post['testimonial_id'];
        $languages = Language::getAllNames();
        if (count($languages) > 1) {
            $lang_id = $post['lang_id'];
        } else {
            $lang_id = array_key_first($languages);
            $post['lang_id'] = $lang_id;
        }


        if ($recordId == 0 || $lang_id == 0) {
            LibHelper::exitWithError($this->str_invalid_request_id, true);
        }

        $frm = $this->getLangForm($recordId, $lang_id);
        $post = $frm->getFormDataFromArray(FatApp::getPostedData());
        unset($post['testimonial_id']);
        unset($post['lang_id']);

        $data = array(
            'testimoniallang_lang_id' => $lang_id,
            'testimoniallang_testimonial_id' => $recordId,
            'testimonial_title' => $post['testimonial_title'],
            'testimonial_text' => $post['testimonial_text']
        );

        $obj = new Testimonial($recordId);

        if (!$obj->updateLangData($lang_id, $data)) {
            LibHelper::exitWithError($obj->getError(), true);
        }

        $autoUpdateOtherLangsData = FatApp::getPostedData('auto_update_other_langs_data', FatUtility::VAR_INT, 0);
        if (0 < $autoUpdateOtherLangsData) {
            $updateLangDataobj = new TranslateLangData(Testimonial::DB_TBL_LANG);
            if (false === $updateLangDataobj->updateTranslatedData($recordId, CommonHelper::getDefaultFormLangId())) {
                LibHelper::exitWithError($updateLangDataobj->getError(), true);
            }
        }

        $newTabLangId = 0;
        $languages = Language::getAllNames();
        foreach ($languages as $langId => $langName) {
            if (!Testimonial::getAttributesByLangId($langId, $recordId)) {
                $newTabLangId = $langId;
                break;
            }
        }

        $this->set('msg', $this->str_setup_successful);
        $this->set('recordId', $recordId);
        $this->set('langId', $newTabLangId);
        $this->_template->render(false, false, 'json-success.php');
    }

    public function media($recordId = 0, $fileType = 0, $langId = 0)
    {
        $this->checkEditPrivilege();
        $recordId = FatUtility::int($recordId);
        $imageFrm = $this->getMediaForm($recordId);
        $testimonialImages = AttachedFile::getAttachment(AttachedFile::FILETYPE_TESTIMONIAL_IMAGE, $recordId, 0, $langId);
        $this->set('image', $testimonialImages);
        $this->set('recordId', $recordId);
        $this->set('fileType', $fileType);
        $this->set('langId', $langId);
        $this->set('imageFrm', $imageFrm);
        $this->checkEditPrivilege(true);
        $this->set('html', $this->_template->render(false, false, NULL, true));
        $this->_template->render(false, false, 'json-success.php', true, false);
    }

    public function getMediaForm($recordId)
    {
        $frm = new Form('frmTestimonialMedia');
        $frm->addHiddenField('', 'testimonial_id', $recordId);
        $frm->addHiddenField('', 'lang_id', $this->siteLangId);
        $frm->addHTML('', 'testimonial_image', '');
        $frm->addHiddenField('', 'file_type', AttachedFile::FILETYPE_TESTIMONIAL_IMAGE);
        $frm->addHiddenField('', 'min_width', 300);
        $frm->addHiddenField('', 'min_height', 300);
        return $frm;
    }

    
    protected function isMediaUploaded($recordId)
    {
        $attachment = AttachedFile::getAttachment(AttachedFile::FILETYPE_TESTIMONIAL_IMAGE, $recordId, 0);
        if (false !== $attachment && 0 < $attachment['afile_id']) {
            return true;
        }
        return false;
    }

    /**
     * Undocumented function
     *
     */
    public function images($recordId = 0, $fileType = 0, $slideScreen = 0, $langId = 0)
    {
        $languages = Language::getAllNames();
        if (count($languages) > 1) {
            $universalImage = true;
            $langId = FatUtility::int($langId);
        } else {
            $universalImage = false;
            $langId = array_key_first($languages);
        }
        $langId = $langId == 0 ?  $this->siteLangId : $langId;
        $cbgImage = AttachedFile::getAttachment(AttachedFile::FILETYPE_TESTIMONIAL_IMAGE, $recordId, 0, $langId, $universalImage);
        $this->set('image', $cbgImage);
        $this->set('imageFunction', 'testimonial');
        $this->set('file_type', ImageDimension::VIEW_THUMB);
        $this->set('recordId', $recordId);
        $this->checkEditPrivilege(true);
        $this->set('html', $this->_template->render(false, false, NULL, true));
        $this->_template->render(false, false, 'json-success.php', true, false);
    }


    public function uploadMedia()
    {
        $this->checkEditPrivilege();
        $post = FatApp::getPostedData();

        $recordId = FatApp::getPostedData('testimonial_id', FatUtility::VAR_INT, 0);
        $lang_id = FatApp::getPostedData('lang_id', FatUtility::VAR_INT, 0);

        if (empty($post)) {
            LibHelper::exitWithError(Labels::getLabel('ERR_INVALID_REQUEST_OR_FILE_NOT_SUPPORTED', $this->siteLangId), true);
        }
        if (!$recordId) {
            LibHelper::exitWithError($this->str_invalid_request_id, true);
        }

        if (!is_uploaded_file($_FILES['cropped_image']['tmp_name'])) {
            LibHelper::exitWithError(Labels::getLabel('ERR_PLEASE_SELECT_A_FILE', $this->siteLangId), true);
        }

        $fileHandlerObj = new AttachedFile();
        $fileHandlerObj->deleteFile($fileHandlerObj::FILETYPE_TESTIMONIAL_IMAGE, $recordId, 0, 0, $lang_id);

        if (!$res = $fileHandlerObj->saveImage(
            $_FILES['cropped_image']['tmp_name'],
            $fileHandlerObj::FILETYPE_TESTIMONIAL_IMAGE,
            $recordId,
            0,
            $_FILES['cropped_image']['name'],
            -1,
            false,
            $lang_id
        )) {
            LibHelper::exitWithError($fileHandlerObj->getError(), true);
        }

        $this->set('recordId', $recordId);
        $this->set('file', $_FILES['cropped_image']['name']);
        $this->set('msg', $_FILES['cropped_image']['name'] . Labels::getLabel('MSG_File_Uploaded_Successfully', $this->siteLangId));
        $this->_template->render(false, false, 'json-success.php');
    }

    public function removeMedia($recordId = 0, $fileType = 0, $aFileId = 0)
    {
        $recordId = FatUtility::int($recordId);
        if (!$recordId) {
            LibHelper::exitWithError($this->str_invalid_request, true);
        }

        $fileHandlerObj = new AttachedFile();
        if (!$fileHandlerObj->deleteFile($fileType, $recordId, $aFileId, 0, -1)) {
            LibHelper::exitWithError($fileHandlerObj->getError(), true);
        }
        $this->set('msg', Labels::getLabel('MSG_DELETED_SUCCESSFULLY', $this->siteLangId));
        $this->_template->render(false, false, 'json-success.php');
    }

    public function autoComplete()
    {
        $pagesize = FatApp::getConfig('CONF_PAGE_SIZE');
        $post = FatApp::getPostedData();

        $srch = Testimonial::getSearchObject($this->siteLangId, false);
        $srch->addMultipleFields(array('testimonial_id', 'IFNULL(testimonial_title, testimonial_identifier) as testimonial_title'));

        if (isset($post['keyword']) && '' != $post['keyword']) {
            $cond = $srch->addCondition('testimonial_title', 'LIKE', '%' . $post['keyword'] . '%');
            $cond->attachCondition('testimonial_identifier', 'LIKE', '%' . $post['keyword'] . '%', 'OR');
        }

        $excludeRecords = FatApp::getPostedData('excludeRecords', FatUtility::VAR_INT);
        if (!empty($excludeRecords) && is_array($excludeRecords)) {
            $srch->addCondition('testimonial_id', 'NOT IN', $excludeRecords);
        }

        $collectionId = FatApp::getPostedData('collection_id', FatUtility::VAR_INT, 0);
        $alreadyAdded = Collections::getRecords($collectionId);
        if (!empty($alreadyAdded) && 0 < count($alreadyAdded)) {
            $srch->addCondition('testimonial_id', 'NOT IN', array_keys($alreadyAdded));
        }

        $srch->setPageSize($pagesize);
        $rs = $srch->getResultSet();
        $db = FatApp::getDb();
        $posts = $db->fetchAll($rs, 'testimonial_id');
        $json = array(
            'pageCount' => $srch->pages(),
            'results' => []
        );
        foreach ($posts as $key => $post) {
            $json['results'][] = array(
                'id' => $key,
                'text' => strip_tags(html_entity_decode($post['testimonial_title'], ENT_QUOTES, 'UTF-8'))
            );
        }
        die(json_encode($json));
    }

    /**
     * Undocumented function
     *
     * @return array
     */
    protected function getFormColumns(): array
    {
        $testimonialTblHeadingCols = CacheHelper::get('testimonialTblHeadingCols' . $this->siteLangId, CONF_DEF_CACHE_TIME, '.txt');
        if ($testimonialTblHeadingCols) {
            return json_decode($testimonialTblHeadingCols, true);
        }

        $arr = [
            'select_all' => Labels::getLabel('LBL_SELECT_ALL', $this->siteLangId),
            /* 'listSerial' => Labels::getLabel('LBL_SR._NO', $this->siteLangId), */
            'testimonial_title' => Labels::getLabel('LBL_TESTIMONIAL_TITLE', $this->siteLangId),
            'testimonial_active' => Labels::getLabel('LBL_STATUS', $this->siteLangId),
            'action' => Labels::getLabel('LBL_ACTION_BUTTONS', $this->siteLangId),
        ];
        CacheHelper::create('testimonialTblHeadingCols' . $this->siteLangId, json_encode($arr), CacheHelper::TYPE_LABELS);
        return $arr;
    }

    /**
     * Undocumented function
     *
     * @return array
     */
    protected function getDefaultColumns(): array
    {
        return [
            'select_all',
           /*  'listSerial', */
            'testimonial_title',
            'testimonial_active',
            'action',
        ];
    }

    /**
     * Undocumented function
     *
     * @param array $fields
     * @return array
     */
    protected function excludeKeysForSort($fields = []): array
    {
        return array_diff($fields, ['testimonial_id', 'testimonial_active'], Common::excludeKeysForSort());
    }
}
