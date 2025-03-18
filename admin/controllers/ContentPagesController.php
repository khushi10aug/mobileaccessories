<?php

class ContentPagesController extends ListingBaseController
{
    protected string $modelClass = 'ContentPage';
    protected $pageKey = 'MANAGE_CONTENT_PAGES';

    public function __construct($action)
    {
        parent::__construct($action);
        $this->objPrivilege->canViewContentPages();
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
        $actionItemsData['deleteButton'] = true;
        $actionItemsData['otherButtons'] = [
            [
                'attr' => [
                    'href' => 'javascript:void(0)',
                    'class' => 'btn btn-outline-brand btn-icon',
                    'onclick' => "pagesLayouts()",
                    'title' => Labels::getLabel('LBL_LAYOUTS_INSTRUCTIONS', $this->siteLangId)
                ],
                'label' => '<svg class="svg btn-icon-start " width="18" height="18">
                                <use xlink:href="' . CONF_WEBROOT_URL . 'images/retina/sprite-actions.svg' . AttachedFile::setTimeParam(RELEASE_DATE) . '#export">
                                </use>
                            </svg><span>' . Labels::getLabel('BTN_LAYOUTS', $this->siteLangId) . '</span>',
            ]
        ];
        $actionItemsData['newRecordBtnAttrs'] = [
            'attr' => [
                'onclick' => 'addNew(false, "modal-dialog-vertical-md")'
            ],
        ];
        $this->set('pageData', $pageData);
        $this->set('pageTitle', $pageTitle);
        $this->set('actionItemsData', $actionItemsData);
        $this->set("frmSearch", $frmSearch);
        $this->set('defaultColumns', $this->getDefaultColumns());
        $this->set('canEdit', $this->objPrivilege->canEditZones($this->admin_id, true));
        $this->set('keywordPlaceholder', Labels::getLabel('FRM_SEARCH_BY_TITLE', $this->siteLangId));
        $this->getListingData();
        $this->_template->addCss('css/cropper.css');
        $this->_template->addJs(['js/cropper.js', 'js/cropper-main.js', 'content-pages/page-js/index.js']);
        $this->set('includeEditor', true);
        $this->_template->render(true, true, '_partial/listing/index.php');
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
            $this->set("canEdit", $this->objPrivilege->canEditContentPages($this->admin_id, true));
        } else {
            $this->objPrivilege->canEditContentPages();
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
            $this->modelObj::tblFld('image_title'),
            $this->modelObj::tblFld('image_content'),
        ];
        $this->set('formTitle', Labels::getLabel('LBL_CONTENT_PAGE_SETUP', $this->siteLangId));
    }

    public function search()
    {
        $this->getListingData();
        $jsonData = [
            'listingHtml' => $this->_template->render(false, false, 'content-pages/search.php', true),
            'paginationHtml' => $this->_template->render(false, false, '_partial/listing/listing-foot.php', true)
        ];
        LibHelper::exitWithSuccess($jsonData, true);
    }

    private function getListingData()
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
        $page = (empty($data['page']) || $data['page'] <= 0) ? 1 : $data['page'];
        $post = $searchForm->getFormDataFromArray($data);
        $srch = ContentPage::getSearchObject($this->siteLangId);
        if (isset($post['keyword']) && '' != $post['keyword']) {
            $condition = $srch->addCondition('cpage_title', 'like', '%' . $post['keyword'] . '%');
            $condition->attachCondition('cpage_identifier', 'like', '%' . $post['keyword'] . '%', 'OR');
        }
        $this->setRecordCount(clone $srch, $pageSize, $page, $post);
        $srch->doNotCalculateRecords();
        $page = (empty($page) || $page <= 0) ? 1 : $page;
        $page = FatUtility::int($page);
        $srch->setPageNumber($page);
        $srch->addOrder($sortBy, $sortOrder);
        $srch->addMultipleFields(['cpage_id','cpage_identifier', 'COALESCE(cpage_title, cpage_identifier) as cpage_title']);
        $srch->setPageSize($pageSize);
        $this->set("arrListing", FatApp::getDb()->fetchAll($srch->getResultSet()));
        $this->set('postedData', $post);
        $this->set('sortBy', $sortBy);
        $this->set('sortOrder', $sortOrder);
        $this->set('fields', $fields);
        $this->set('allowedKeysForSorting', $allowedKeysForSorting);
        $this->checkEditPrivilege(true);
    }


    /**
     * Undocumented function
     *
     * @return void
     */
    public function form()
    {
        $this->checkEditPrivilege();
        $recordId = FatApp::getPostedData('recordId', FatUtility::VAR_INT, 0);
        $frm = $this->getForm($recordId);

        if (0 < $recordId) {
            $arrayFlds = array(
                'cpage_id', 'cpage_identifier', 'cpage_content', 'IFNULL(cpage_title,cpage_identifier) as cpage_title', 'cpage_layout',
                'cpage_image_content', 'cpage_image_title', 'cpage_hide_header_footer'
            );
            $data = ContentPage::getAttributesByLangId(CommonHelper::getDefaultFormLangId(), $recordId, $arrayFlds, applicationConstants::JOIN_RIGHT);
            if ($data === false) {
                LibHelper::exitWithError($this->str_invalid_request, true);
            }
            /* url data[ */
            $urlSrch = UrlRewrite::getSearchObject();
            $urlSrch->doNotCalculateRecords();
            $urlSrch->setPageSize(1);
            $urlSrch->addFld('urlrewrite_custom');
            $urlSrch->addCondition('urlrewrite_original', '=', ContentPage::REWRITE_URL_PREFIX . $recordId);
            $rs = $urlSrch->getResultSet();
            $urlRow = FatApp::getDb()->fetch($rs);
            if ($urlRow) {
                $data['urlrewrite_custom'] = $urlRow['urlrewrite_custom'];
            }
            /* ] */
            $frm->fill($data);
        }
        $this->set('recordId', $recordId);
        $this->set('frm', $frm);
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

        $recordId = $post['cpage_id'];
        unset($post['cpage_id']);
        $contentPage = new ContentPage($recordId);
        $post[$contentPage::tblFld('identifier')] = $post[$contentPage::tblFld('title')];
        $contentPage->assignValues($post);

        if (!$contentPage->save()) {
            LibHelper::exitWithError($contentPage->getError(), true);
        }

        $newTabLangId = CommonHelper::getDefaultFormLangId();
        if (0 < $recordId) {
            $languages = Language::getDropDownList(CommonHelper::getDefaultFormLangId());
            if (0 < count($languages)) {
                foreach ($languages as $langId => $langName) {
                    if (!ContentPage::getAttributesByLangId($langId, $recordId)) {
                        $newTabLangId = $langId;
                        break;
                    }
                }
            }
        }

        $recordId = $contentPage->getMainTableRecordId();
        $this->setLangData($contentPage, [$contentPage::tblFld('title') => $post[$contentPage::tblFld('title')]]);

        /* url data[ */
        $originalUrl = ContentPage::REWRITE_URL_PREFIX . $recordId;
        if ($post['urlrewrite_custom'] == '') {
            UrlRewrite::remove($originalUrl);
        } else {
            $contentPage->rewriteUrl($post['urlrewrite_custom']);
        }
        /* ] */



        $this->set('msg', Labels::getLabel('MSG_SETUP_SUCCESSFUL', $this->siteLangId));
        $this->set('pageId', $recordId);
        $this->set('langId', $newTabLangId);
        $this->set('cpage_layout', $post['cpage_layout']);
        $this->_template->render(false, false, 'json-success.php');
    }

    private function getForm($recordId = 0)
    {
        $recordId = FatUtility::int($recordId);

        $frm = new Form('frmCMSPage', ['id' => 'frmCMSPage']);
        $frm->addRequiredField(Labels::getLabel('FRM_PAGE_TITLE', $this->siteLangId), 'cpage_title');
        $frm->addHiddenField('', 'cpage_id', 0);
        $fld = $frm->addTextBox(Labels::getLabel('FRM_SEO_FRIENDLY_URL', $this->siteLangId), 'urlrewrite_custom');
        $fld->requirements()->setRequired();
        $frm->addSelectBox(Labels::getLabel('FRM_LAYOUT_TYPE', $this->siteLangId), 'cpage_layout', $this->getAvailableLayouts(), '', array('id' => 'cpage_layout'), Labels::getLabel('FRM_Select', $this->siteLangId))->requirements()->setRequired();
        $frm->addCheckBox(Labels::getLabel("FRM_EXCLUDE_HEADER_&_FOOTER", $this->siteLangId), 'cpage_hide_header_footer', 1, array(), false, 0);
        return $frm;
    }

    protected function getLangForm($recordId = 0, $langId = 0)
    {
        $langId = 1 > $langId ? $this->siteLangId : $langId;
        $cpageData = ContentPage::getAttributesByLangId($this->siteLangId, $recordId, NULL, applicationConstants::JOIN_RIGHT);
        $cpage_layout = $cpageData['cpage_layout'];

        $frm = new Form('frmContentPageLang', array('id' => 'frmContentPageLang'));
        $frm->addHiddenField('', 'cpage_id', $recordId);
        $frm->addSelectBox(Labels::getLabel('FRM_LANGUAGE', $langId), 'lang_id', Language::getDropDownList(), $langId, array(), '');
        $frm->addRequiredField(Labels::getLabel('FRM_PAGE_TITLE', $langId), 'cpage_title');
        $frm->addHiddenField('', 'cpage_layout', $cpage_layout);
        if ($cpage_layout == ContentPage::CONTENT_PAGE_LAYOUT1_TYPE) {
            $frm->addHTML('', 'cpage_bg_image', '');
            $frm->addHiddenField('', 'file_type', AttachedFile::FILETYPE_CPAGE_BACKGROUND_IMAGE);
            $getImageDimensions = ImageDimension::getData(ImageDimension::TYPE_CPAGE_BG, ImageDimension::VIEW_DEFAULT);
            $frm->addHiddenField('', 'min_width', $getImageDimensions['width']);
            $frm->addHiddenField('', 'min_height', $getImageDimensions['height']);
            $this->set('getImageDimensions', $getImageDimensions);
            $frm->addTextBox(Labels::getLabel('FRM_BACKGROUND_IMAGE_TITLE', $langId), 'cpage_image_title');
            $frm->addTextArea(Labels::getLabel('FRM_BACKGROUND_IMAGE_DESCRIPTION', $langId), 'cpage_image_content');
            for ($i = 1; $i <= ContentPage::CONTENT_PAGE_LAYOUT1_BLOCK_COUNT; $i++) {
                $frm->addHtmlEditor(Labels::getLabel('FRM_CONTENT_BLOCK_' . $i, $langId), 'cpblock_content_block_' . $i);
            }
        } else {
            $frm->addHtmlEditor(Labels::getLabel('FRM_PAGE_CONTENT', $langId), 'cpage_content');
        }
        $languages = Language::getAllNames();
        $translatorSubscriptionKey = FatApp::getConfig('CONF_TRANSLATOR_SUBSCRIPTION_KEY', FatUtility::VAR_STRING, '');
        if (!empty($translatorSubscriptionKey) && 1 < count($languages) && $langId == CommonHelper::getDefaultFormLangId()) {
            $frm->addCheckBox(Labels::getLabel('FRM_UPDATE_OTHER_LANGUAGES_DATA', $langId), 'auto_update_other_langs_data', 1, array(), false, 0);
        }

        return $frm;
    }

    public function langForm($autoFillLangData = 0)
    {
        $this->mainTableRecordId = FatApp::getPostedData('recordId', FatUtility::VAR_INT, 0);
        $recordId = FatApp::getPostedData('recordId', FatUtility::VAR_INT, 0);
        $langId = FatApp::getPostedData('langId', FatUtility::VAR_INT, 0);

        if (1 > $this->mainTableRecordId || 1 > $langId) {
            LibHelper::exitWithError($this->str_invalid_request, true);
        }
        $cpage_layout = ContentPage::getAttributesByLangId($langId, $recordId, 'cpage_layout', applicationConstants::JOIN_RIGHT);

        $this->setLangTemplateData();
        $langFrm = $this->getLangForm($this->mainTableRecordId, $langId);
        if (0 < $autoFillLangData) {
            $updateLangDataobj = new TranslateLangData($this->modelObj::DB_TBL_LANG);
            $translatedData = $updateLangDataobj->getTranslatedData($this->mainTableRecordId, $langId, CommonHelper::getDefaultFormLangId());
            if (false === $translatedData) {
                LibHelper::exitWithError($updateLangDataobj->getError(), true);
            }
            $langData = current($translatedData);
        } else {
            $langData = $this->modelObj::getAttributesByLangId($langId, $this->mainTableRecordId);
        }

        if ($langData) {
            $srch = new searchBase(ContentPage::DB_TBL_CONTENT_PAGES_BLOCK_LANG);
            $srch->doNotCalculateRecords();
            $srch->doNotLimitRecords();
            $srch->addMultipleFields(array("cpblocklang_text", 'cpblocklang_block_id'));
            $srch->addCondition('cpblocklang_cpage_id', '=', 'mysql_func_' . $recordId, 'AND', true);

            if (0 < $autoFillLangData) {
                $srch->addCondition('cpblocklang_lang_id', '=', CommonHelper::getDefaultFormLangId());
            } else {
                $srch->addCondition('cpblocklang_lang_id', '=', 'mysql_func_' . $langId, 'AND', true);
            }

            $srchRs = $srch->getResultSet();
            $blockData = FatApp::getDb()->fetchAll($srchRs, 'cpblocklang_block_id');
            foreach ($blockData as $blockKey => $blockContent) {
                if (0 < $autoFillLangData) {
                    $blockContent = $updateLangDataobj->directTranslate(['cpblocklang_text' => $blockContent['cpblocklang_text']], $langId);
                    if (false === $blockContent) {
                        LibHelper::exitWithError($updateLangDataobj->getError(), true);
                    }
                    $blockContent = current($blockContent);
                }
                $langData['cpblock_content_block_' . $blockKey] = $blockContent['cpblocklang_text'];
            }
            $langFrm->fill($langData);
        }

        if (true === $this->isPlugin) {
            $pluginDetail = Plugin::getAttributesById($this->mainTableRecordId, ['plugin_type', 'plugin_identifier']);
            if (!in_array($pluginDetail['plugin_type'], Plugin::HAVING_DESCRIPTION)) {
                $langFrm->removeField($langFrm->getField('plugin_description'));
            }
        }

        $this->set('recordId', $this->mainTableRecordId);
        $this->set('lang_id', $langId);
        $this->set('langFrm', $langFrm);
        $this->set('cpage_layout', $cpage_layout);
        $this->set('formLayout', Language::getLayoutDirection($langId));
        $this->checkEditPrivilege(true);

        $languages = Language::getAllNames();
        if (count($languages) > 1) {
            $universalImage = true;
        } else {
            $universalImage = false;
        }
        $cbgImage = AttachedFile::getAttachment(AttachedFile::FILETYPE_CPAGE_BACKGROUND_IMAGE, $recordId, 0, $langId, $universalImage);
        $this->set('image', $cbgImage);
        $this->set('html', $this->_template->render(false, false, NULL, true));
        $this->_template->render(false, false, 'json-success.php', true, false);
    }

    public function languageSetup()
    {
        $this->checkEditPrivilege();
        $post = FatApp::getPostedData();
        $recordId = $post['cpage_id'];
        $lang_id = $post['lang_id'];
        $cpage_layout = $post['cpage_layout'];

        if ($recordId == 0 || $lang_id == 0) {
            LibHelper::exitWithError($this->str_invalid_request_id, true);
        }

        unset($post['cpage_id']);
        unset($post['lang_id']);
        $data = array(
            'cpagelang_lang_id' => $lang_id,
            'cpagelang_cpage_id' => $recordId,
            'cpage_title' => $post['cpage_title'],
        );

        if ($cpage_layout == ContentPage::CONTENT_PAGE_LAYOUT1_TYPE) {
            $data['cpage_image_title'] = $post['cpage_image_title'];
            $data['cpage_image_content'] = $post['cpage_image_content'];
        } else {
            $data['cpage_content'] = $post['cpage_content'];
        }

        $pageObj = new ContentPage($recordId);
        if (!$pageObj->updateLangData($lang_id, $data)) {
            LibHelper::exitWithError($pageObj->getError(), true);
        }
        $recordId = $pageObj->getMainTableRecordId();
        if (!$recordId) {
            $recordId = FatApp::getDb()->getInsertId();
        }
        $pageObj = new ContentPage($recordId);
        if ($cpage_layout == ContentPage::CONTENT_PAGE_LAYOUT1_TYPE) {
            for ($i = 1; $i <= ContentPage::CONTENT_PAGE_LAYOUT1_BLOCK_COUNT; $i++) {
                $data['cpblocklang_text'] = $post['cpblock_content_block_' . $i];
                $data['cpblocklang_block_id'] = $i;
                if (!$pageObj->addUpdateContentPageBlocks($lang_id, $recordId, $data)) {
                    LibHelper::exitWithError($pageObj->getError(), true);
                }
            }
        }

        $autoUpdateOtherLangsData = FatApp::getPostedData('auto_update_other_langs_data', FatUtility::VAR_INT, 0);
        if (0 < $autoUpdateOtherLangsData) {
            $updateLangDataobj = new TranslateLangData(ContentPage::DB_TBL_LANG);
            if (false === $updateLangDataobj->updateTranslatedData($recordId, CommonHelper::getDefaultFormLangId())) {
                LibHelper::exitWithError($updateLangDataobj->getError(), true);
            }
        }

        $newTabLangId = 0;
        $languages = Language::getAllNames();
        foreach ($languages as $langId => $langName) {
            if (!ContentPage::getAttributesByLangId($langId, $recordId)) {
                $newTabLangId = $langId;
                break;
            }
        }

        $this->set('msg', Labels::getLabel('MSG_SETUP_SUCCESSFUL', $lang_id));
        $this->set('recordId', $recordId);
        $this->set('langId', $newTabLangId);
        $this->set('cpage_layout', $cpage_layout);
        $this->_template->render(false, false, 'json-success.php');
    }




    /**
     * Undocumented function
     *
     * @param [type] $recordId
     * @param string $file_type
     * @param integer $lang_id
     * @return void
     */
    public function images($recordId, $lang_id = 0)
    {
        $languages = Language::getAllNames();
        $recordId = FatUtility::int($recordId);
        if (count($languages) > 1) {
            $lang_id = FatUtility::int($lang_id);
        } else {
            $lang_id = array_key_first($languages);
        }

        $cbgImage = AttachedFile::getAttachment(AttachedFile::FILETYPE_CPAGE_BACKGROUND_IMAGE, $recordId, 0, $lang_id, false);
        $this->set('image', $cbgImage);
        $this->set('imageFunction', 'cpageBackgroundImage');
        $this->set('recordId', $recordId);
        $this->checkEditPrivilege(true);

        $this->set('html', $this->_template->render(false, false, NULL, true));
        $this->_template->render(false, false, 'json-success.php', true, false);
    }



    /**
     * Undocumented function
     *
     * @return void
     */
    public function uploadMedia()
    {
        $this->checkEditPrivilege();
        $post = FatApp::getPostedData();
        if (empty($post)) {
            LibHelper::exitWithError(Labels::getLabel('ERR_INVALID_REQUEST_OR_FILE_NOT_SUPPORTED', $this->siteLangId), true);
        }
        $recordId = FatApp::getPostedData('cpage_id', FatUtility::VAR_INT, 0);
        $languages = Language::getAllNames();
        if (count($languages) > 1) {
            $lang_id = FatApp::getPostedData('lang_id', FatUtility::VAR_INT, $this->siteLangId);
        } else {
            $lang_id = array_key_first($languages);
        }
        $file_type = FatApp::getPostedData('file_type', FatUtility::VAR_INT, 0);
        $slide_screen = FatApp::getPostedData('slide_screen', FatUtility::VAR_INT, 0);

        if (!$recordId) {
            LibHelper::exitWithError($this->str_invalid_request_id, true);
        }

        if (!is_uploaded_file($_FILES['cropped_image']['tmp_name'])) {
            LibHelper::exitWithError(Labels::getLabel('ERR_PLEASE_SELECT_A_FILE', $this->siteLangId), true);
        }

        $fileHandlerObj = new AttachedFile();
        $fileHandlerObj->deleteFile($file_type, $recordId, 0, 0, $lang_id, $slide_screen);

        if (!$fileHandlerObj->saveAttachment(
            $_FILES['cropped_image']['tmp_name'],
            $file_type,
            $recordId,
            0,
            $_FILES['cropped_image']['name'],
            -1,
            false,
            $lang_id,
        )) {
            LibHelper::exitWithError($fileHandlerObj->getError(), true);
        }

        $this->set('recordId', $recordId);
        $this->set('file', $_FILES['cropped_image']['name']);
        $this->set('msg', $_FILES['cropped_image']['name'] . ' ' . Labels::getLabel('MSG_FILE_UPLOADED_SUCCESSFULLY', $this->siteLangId));
        $this->_template->render(false, false, 'json-success.php');
    }


    public function removeMedia($recordId, $afileId)
    {
        $recordId = FatUtility::int($recordId);
        $afileId = FatUtility::int($afileId);
        if (!$recordId || !$afileId) {
            LibHelper::exitWithError($this->str_invalid_request, true);
        }
        $fileHandlerObj = new AttachedFile();
        if (!$fileHandlerObj->deleteFile(AttachedFile::FILETYPE_CPAGE_BACKGROUND_IMAGE, $recordId, $afileId)) {
            LibHelper::exitWithError($fileHandlerObj->getError(), true);
        }

        $this->set('msg', Labels::getLabel('MSG_DELETED_SUCCESSFULLY', $this->siteLangId));
        $this->_template->render(false, false, 'json-success.php');
    }

    /**
     * Undocumented function
     *
     * @return void
     */
    public function deleteRecord()
    {
        $this->checkEditPrivilege();

        $recordId = FatApp::getPostedData('recordId', FatUtility::VAR_INT, 0);
        if ($recordId < 1) {
            LibHelper::exitWithError($this->str_invalid_request_id, true);
        }

        $this->markAsDeleted($recordId);

        FatUtility::dieJsonSuccess($this->str_delete_record);
    }

    public function deleteSelected()
    {
        $this->checkEditPrivilege();
        $cpageIdsArr = FatUtility::int(FatApp::getPostedData('cpage_ids'));

        if (empty($cpageIdsArr)) {
            LibHelper::exitWithError($this->str_invalid_request_id, true);
        }

        foreach ($cpageIdsArr as $recordId) {
            if (1 > $recordId) {
                continue;
            }
            $this->markAsDeleted($recordId);
        }
        $this->set('msg', Labels::getLabel('MSG_RECORDS_DELETED_SUCCESSFULLY', $this->siteLangId));
        $this->_template->render(false, false, 'json-success.php');
    }

    public function autoComplete()
    {
        $srch = ContentPage::getSearchObject($this->siteLangId);

        $post = FatApp::getPostedData();
        if (isset($post['keyword']) && '' != $post['keyword']) {
            $srch->addCondition('cpage_title', 'LIKE', '%' . $post['keyword'] . '%');
        }

        $srch->setPageSize(FatApp::getConfig('CONF_ADMIN_PAGESIZE', FatUtility::VAR_INT, 10));
        $srch->addMultipleFields(array('cpage_id', 'IFNULL(cpage_title,cpage_identifier) as cpage_name'));
        $rs = $srch->getResultSet();
        $db = FatApp::getDb();
        $products = $db->fetchAll($rs, 'cpage_id');
        $json = array();
        foreach ($products as $key => $product) {
            $json[] = array(
                'id' => $key,
                'name' => strip_tags(html_entity_decode($product['cpage_name'], ENT_QUOTES, 'UTF-8'))
            );
        }
        die(json_encode($json));
    }

    private function getAvailableLayouts()
    {
        $collectionLayouts = array(
            ContentPage::CONTENT_PAGE_LAYOUT1_TYPE => Labels::getLabel('LBL_CONTENT_PAGE_LAYOUT1', $this->siteLangId),
            ContentPage::CONTENT_PAGE_LAYOUT2_TYPE => Labels::getLabel('LBL_CONTENT_PAGE_LAYOUT2', $this->siteLangId),
        );
        return $collectionLayouts;
    }

    public function layouts()
    {
        $this->set('html', $this->_template->render(false, false, NULL, true));
        $this->_template->render(false, false, 'json-success.php', true, false);
    }

    /**
     * Undocumented function
     *
     * @return array
     */
    protected function getFormColumns(): array
    {
        $ContentPageTblHeadingCols = CacheHelper::get('ContentPageTblHeadingCols' . $this->siteLangId, CONF_DEF_CACHE_TIME, '.txt');
        if ($ContentPageTblHeadingCols) {
            return json_decode($ContentPageTblHeadingCols, true);
        }

        $arr = [
            'select_all' => Labels::getLabel('LBL_SELECT_ALL', $this->siteLangId),
            /*  'listSerial' => Labels::getLabel('LBL_SR._NO', $this->siteLangId), */
            'cpage_id' => Labels::getLabel('LBL_ID', $this->siteLangId),
            'cpage_title' => Labels::getLabel('LBL_TITLE', $this->siteLangId),
            'action' => Labels::getLabel('LBL_ACTION_BUTTONS', $this->siteLangId),
        ];
        CacheHelper::create('ContentPageTblHeadingCols' . $this->siteLangId, json_encode($arr), CacheHelper::TYPE_LABELS);
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
            /* 'listSerial', */
            'cpage_title',
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
        return array_diff($fields, ['cpage_id'], Common::excludeKeysForSort());
    }
}
