<?php

class BlogPostsController extends ListingBaseController
{
    protected string $modelClass = 'BlogPost';
    protected $pageKey = 'BLOG_POSTS';

    public function __construct($action)
    {
        parent::__construct($action);
        $this->objPrivilege->canViewBlogPosts();
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
            $this->set("canEdit", $this->objPrivilege->canEditBlogPosts($this->admin_id, true));
        } else {
            $this->objPrivilege->canEditBlogPosts();
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
        //$this->modelObj = (new ReflectionClass('BlogPost'))->newInstanceArgs($constructorArgs);
        $this->formLangFields = [$this->modelObj::tblFld('title'), $this->modelObj::tblFld('author_name'), $this->modelObj::tblFld('description')];
        $this->set('formTitle', Labels::getLabel('LBL_BLOG_POST_SETUP', $this->siteLangId));
    }

    public function index()
    {
        $fields = $this->getFormColumns();
        $frmSearch = $this->getSearchForm($fields);

        $pageData = PageLanguageData::getAttributesByKey($this->pageKey, $this->siteLangId);
        $pageTitle = $pageData['plang_title'] ?? LibHelper::getControllerName(true);

        $actionItemsData = HtmlHelper::getDefaultActionItems($fields);
        $actionItemsData['newRecordBtnAttrs'] = [
            'attr' => [
                'onclick' => 'addNew(false, "modal-dialog-vertical-md")',
            ],
        ];

        $actionItemsData['otherButtons'] = [
            [
                'attr' => [
                    'href' => 'javascript:void(0)',
                    'class' => 'btn btn-outline-gray btn-icon toolbarBtnJs disabled',
                    'onclick' => "toggleBulkStatues(1,'')",
                    'title' => Labels::getLabel('LBL_PUBLISHED', $this->siteLangId)
                ],
                'label' => '<svg class="svg btn-icon-start" width="18" height="18">
                                <use
                                    xlink:href="' . CONF_WEBROOT_URL . 'images/retina/sprite-actions.svg' . AttachedFile::setTimeParam(RELEASE_DATE) . '#active">
                                </use>
                            </svg><span>' . Labels::getLabel('LBL_PUBLISHED', $this->siteLangId) . '</span>',
            ],
            [
                'attr' => [
                    'href' => 'javascript:void(0)',
                    'class' => 'btn btn-outline-gray btn-icon toolbarBtnJs disabled',
                    'onclick' => "toggleBulkStatues(0,'')",
                    'title' => Labels::getLabel('LBL_UNPUBLISHED', $this->siteLangId)
                ],
                'label' => '<svg class="svg btn-icon-start" width="18" height="18">
                                <use
                                    xlink:href="' . CONF_WEBROOT_URL . 'images/retina/sprite-actions.svg' . AttachedFile::setTimeParam(RELEASE_DATE) . '#in-active">
                                </use>
                            </svg><span>' . Labels::getLabel('LBL_UNPUBLISHED', $this->siteLangId) . '</span>',
            ],

        ];
        if ($this->objPrivilege->canEditBlogPosts($this->admin_id, true)) {
            $actionItemsData['otherButtons'][] = [
                'attr' => [
                    'href' => 'javascript:void(0)',
                    'class' => 'btn btn-outline-gray btn-icon toolbarBtnJs disabled',
                    'onclick' => "deleteSelected()",
                    'title' => Labels::getLabel('BTN_DELETE_RECORDS', $this->siteLangId)
                ],
                'label' => '<svg class="svg btn-icon-start" width="18" height="18">
                                <use
                                    xlink:href="' . CONF_WEBROOT_URL . 'images/retina/sprite-actions.svg' . AttachedFile::setTimeParam(RELEASE_DATE) . '#delete">
                                </use>
                            </svg><span>' . Labels::getLabel('BTN_DELETE', $this->siteLangId) . '</span>',
            ];
        }

        $actionItemsData['formAction'] = 'deleteSelected';
        $actionItemsData['performBulkAction'] = true;

        $this->set('pageData', $pageData);
        $this->set('pageTitle', $pageTitle);
        $this->set('actionItemsData', $actionItemsData);
        $this->set("frmSearch", $frmSearch);
        $this->set('defaultColumns', $this->getDefaultColumns());
        $this->set('keywordPlaceholder', Labels::getLabel('FRM_SEARCH_BY_POST_TITLE', $this->siteLangId));
        $this->getListingData();

        $this->_template->addJs(['js/cropper.js', 'js/cropper-main.js', 'js/tagify.min.js', 'js/tagify.polyfills.min.js', 'blog-posts/page-js/index.js']);
        $this->_template->addCss(['css/cropper.css', 'css/tagify.min.css']);
        $this->set('includeEditor', true);
        $this->_template->render(true, true, '_partial/listing/index.php');
    }

    public function search()
    {
        $this->getListingData();
        $jsonData = [
            'listingHtml' => $this->_template->render(false, false, 'blog-posts/search.php', true),
            'paginationHtml' => $this->_template->render(false, false, '_partial/listing/listing-foot.php', true)
        ];
        LibHelper::exitWithSuccess($jsonData, true);
    }

    private function getListingData()
    {
        $pageSize = applicationConstants::getPageSize(FatApp::getPostedData('pageSize', FatUtility::VAR_INT));

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

        $page = FatApp::getPostedData('page', FatUtility::VAR_INT, 1);
        $page = ($page <= 0) ? 1 : $page;
        $post = $searchForm->getFormDataFromArray(FatApp::getPostedData());

        $srch = BlogPost::getSearchObject($this->siteLangId, true, false, false, false);

        if (isset($post['keyword']) && '' != $post['keyword']) {
            $keywordCond = $srch->addCondition('bp.post_identifier', 'like', '%' . $post['keyword'] . '%');
            $keywordCond->attachCondition('bp_l.post_title', 'like', '%' . $post['keyword'] . '%');
        }

        if (isset($post['post_published']) && $post['post_published'] != '') {
            $srch->addCondition('bp.post_published', '=', $post['post_published']);
        }

        if (isset($post['post_id']) && $post['post_id'] != '') {
            $srch->addCondition('bp.post_id', '=', $post['post_id']);
        }

        if (isset($post['bpcat_id']) && $post['bpcat_id'] != '') {
            $srch->addCondition('bpcategory_id', '=', $post['bpcat_id']);
        }

        $srch->addGroupby('post_id');
        $this->setRecordCount(clone $srch, $pageSize, $page, $post, true);
        $srch->doNotCalculateRecords();
        $srch->addMultipleFields(array('*', 'COALESCE(post_title,post_identifier) post_title', 'group_concat(COALESCE(bpcategory_name ,bpcategory_identifier)) categories'));
        $srch->setPageNumber($page);
        $srch->setPageSize($pageSize);
        $srch->addOrder($sortBy, $sortOrder);
        $this->set("arrListing", FatApp::getDb()->fetchAll($srch->getResultSet()));
        $this->set('postedData', $post);
        $this->set('sortBy', $sortBy);
        $this->set('sortOrder', $sortOrder);
        $this->set('fields', $fields);
        $this->set('allowedKeysForSorting', $allowedKeysForSorting);
        $this->set('canEdit', $this->objPrivilege->canEditEmptyCartItems($this->admin_id, true));
    }

    public function form()
    {
        $this->checkEditPrivilege();
        $recordId = FatApp::getPostedData('recordId', FatUtility::VAR_INT, 0);

        $frm = $this->getForm($recordId);
        if (0 < $recordId) {
            $data = BlogPost::getAttributesByLangId(CommonHelper::getDefaultFormLangId(), $recordId, ['ln.*', 'm.*', 'IFNULL(post_title,post_identifier) as post_title'], applicationConstants::JOIN_RIGHT);
            if ($data === false) {
                LibHelper::exitWithError($this->str_invalid_request, true);
            }
            /* url data[ */
            $urlSrch = UrlRewrite::getSearchObject();
            $urlSrch->doNotCalculateRecords();
            $urlSrch->setPageSize(1);
            $urlSrch->addFld('urlrewrite_custom');
            $urlSrch->addCondition('urlrewrite_original', '=', 'blog/post-detail/' . $recordId);
            $rs = $urlSrch->getResultSet();
            $urlRow = FatApp::getDb()->fetch($rs);
            if ($urlRow) {
                $data['urlrewrite_custom'] = $urlRow['urlrewrite_custom'];
            }
            /* ] */

            /* link blog post to blog post categories[ */
            $postObj = new BlogPost();
            $categories = $postObj->getPostCategories($recordId, $this->siteLangId);
            $bpCats = [];
            foreach ($categories as $cat) {
                $bpCats[] = [
                    'id' => $cat['bpcategory_id'],
                    'value' => $cat['bpcategory_name'],
                ];
            }
            $data['categories'] = json_encode($bpCats);
            /* ] */

            $frm->fill($data);
        }

        $this->set('languages', Language::getAllNames());
        $this->set('recordId', $recordId);
        $this->set('frm', $frm);
        $this->set('formTitle', Labels::getLabel('LBL_BLOG_POST_SETUP', $this->siteLangId));
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

        $recordId = FatUtility::int($post['post_id']);
        unset($post['post_id']);

        if ($recordId == 0) {
            $post['post_added_on'] = date('Y-m-d H:i:s');
        }
        if ($post['post_published']) {
            $post['post_published_on'] = date('Y-m-d H:i:s');
        } else {
            $post['post_published_on'] = '';
        }
        $post['post_updated_on'] = date('Y-m-d H:i:s');

        $categories = '';
        if (isset($post['categories'])) {
            $categories = json_decode($post['categories'], true);
            foreach ($categories as $i => $catRow) {
                if (!isset($catRow['id'])) {
                    unset($categories[$i]);
                }
            }

            if (empty($categories)) {
                LibHelper::exitWithError(Labels::getLabel('LBL_PLEASE_SELECT_VALID_CATEGORIES', $this->siteLangId), true);
            }

            unset($post['categories']);
        }
        $post['post_identifier'] = $post['post_title'];

        $record = new BlogPost($recordId);
        $record->assignValues($post);
        if (!$record->save()) {
            $msg = $record->getError();
            if (false !== strpos(strtolower($msg), 'duplicate')) {
                $msg = Labels::getLabel('ERR_DUPLICATE_RECORD_NAME', $this->siteLangId);
            }
            LibHelper::exitWithError($msg, true);
        }
        $recordId = $record->getMainTableRecordId();

        $langData = [
            'post_title' => $post['post_title'],
            'post_author_name' => $post['post_author_name'],
            'post_description' => $post['post_description'],
        ];
        if (!$record->updateLangData(CommonHelper::getDefaultFormLangId(), $langData)) {
            LibHelper::exitWithError($record->getError(), true);
        }

        /* link blog post to blog post categories[ */
        if (!empty($categories)) {
            $categories = array_column($categories, 'id');
            if (!$record->addUpdateCategories($recordId, $categories)) {
                LibHelper::exitWithError($record->getError(), true);
            }
        }
        /* ] */

        /* url data[ */
        $blogOriginalUrl = BlogPost::REWRITE_URL_PREFIX . $recordId;
        if ($post['urlrewrite_custom'] == '') {
            FatApp::getDb()->deleteRecords(UrlRewrite::DB_TBL, array('smt' => 'urlrewrite_original = ?', 'vals' => array($blogOriginalUrl)));
        } else {
            $record->rewriteUrl($post['urlrewrite_custom']);
        }

        /* ] */

        $autoUpdateOtherLangsData = FatApp::getPostedData('auto_update_other_langs_data', FatUtility::VAR_INT, 0);
        if (0 < $autoUpdateOtherLangsData) {
            $updateLangDataobj = new TranslateLangData(BlogPost::DB_TBL_LANG);
            if (false === $updateLangDataobj->updateTranslatedData($recordId, CommonHelper::getDefaultFormLangId())) {
                LibHelper::exitWithError($updateLangDataobj->getError(), true);
            }
        }

        $newTabLangId = 0;
        $languages = Language::getDropDownList(CommonHelper::getDefaultFormLangId());
        if (0 < count($languages)) {
            foreach ($languages as $langId => $langName) {
                if (!BlogPost::getAttributesByLangId($langId, $recordId)) {
                    $newTabLangId = $langId;
                    break;
                }
            }
        }

        CacheHelper::clear(CacheHelper::TYPE_BLOG_CATEGORY);

        $this->set('msg', Labels::getLabel('MSG_BLOG_POST_SETUP_SUCCESSFUL', $this->siteLangId));
        $this->set('recordId', $recordId);
        $this->set('langId', $newTabLangId);
        $this->_template->render(false, false, 'json-success.php');
    }

    public function deleteRecord()
    {
        $this->checkEditPrivilege();

        $recordId = FatApp::getPostedData('recordId', FatUtility::VAR_INT, 0);
        if ($recordId < 1) {
            LibHelper::exitWithError($this->str_invalid_request_id, true);
        }
        $this->markAsDeleted($recordId);

        CacheHelper::clear(CacheHelper::TYPE_BLOG_CATEGORY);

        FatUtility::dieJsonSuccess($this->str_delete_record);
    }

    public function deleteSelected()
    {
        $this->checkEditPrivilege();
        $recordIdsArr = FatUtility::int(FatApp::getPostedData('record_ids'));

        if (empty($recordIdsArr)) {
            LibHelper::exitWithError($this->str_invalid_request, true);
        }

        foreach ($recordIdsArr as $recordId) {
            if (1 > $recordId) {
                continue;
            }
            $this->markAsDeleted($recordId);
        }
        CacheHelper::clear(CacheHelper::TYPE_BLOG_CATEGORY);
        $this->set('msg', Labels::getLabel('MSG_RECORDS_DELETED_SUCCESSFULLY', $this->siteLangId));
        $this->_template->render(false, false, 'json-success.php');
    }

    protected function markAsDeleted($recordId)
    {
        $recordId = FatUtility::int($recordId);
        if (1 > $recordId) {
            LibHelper::exitWithError($this->str_invalid_request, true);
        }
        $obj = new BlogPost($recordId);
        if (!$obj->canMarkRecordDelete()) {
            LibHelper::exitWithError(Labels::getLabel('ERR_UNAUTHORIZED_ACCESS', $this->siteLangId), true);
        }
        $obj->assignValues(array(BlogPost::tblFld('deleted') => 1));

        if (!$obj->save()) {
            LibHelper::exitWithError($obj->getError(), true);
        }
    }

    public function imagesForm($recordId)
    {
        $recordId = FatUtility::int($recordId);
        if (!$recordId) {
            LibHelper::exitWithError($this->str_invalid_request_id, true);
        }

        if (!BlogPost::getAttributesById($recordId)) {
            LibHelper::exitWithError($this->str_invalid_request_id, true);
        }
        $frm = $this->getImagesFrm($recordId);

        $this->set('languages', Language::getAllNames());
        $this->set('recordId', $recordId);
        $this->set('frm', $frm);
        $this->set('displayFooterButtons', false);
        $this->set('activeGentab', false);
        $this->set('imageDimension', ImageDimension::getData(ImageDimension::TYPE_BLOG_POST, ImageDimension::VIEW_DEFAULT));
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

        if (!$row = BlogPost::getAttributesById($recordId)) {
            LibHelper::exitWithError($this->str_invalid_request_id, true);
        }
        $this->set('languages', Language::getAllNames());
        $this->set('image', AttachedFile::getAttachment(AttachedFile::FILETYPE_BLOG_POST_IMAGE, $recordId, 0, $langId, (1 == count($languages)), 0, 1));
        $this->set('recordId', $recordId);
        $this->set('canEdit', $this->objPrivilege->canEditBlogPosts(true));
        $this->set('html', $this->_template->render(false, false, NULL, true));
        $this->_template->render(false, false, 'json-success.php', true, false);
    }

    public function setImageOrder()
    {
        $this->checkEditPrivilege();
        $postObj = new BlogPost();
        $post = FatApp::getPostedData();
        $recordId = FatUtility::int($post['post_id']);
        $imageIds = explode('-', $post['ids']);
        $count = 1;
        foreach ($imageIds as $row) {
            $order[$count] = $row;
            $count++;
        }
        if (!$postObj->updateImagesOrder($recordId, $order)) {
            LibHelper::exitWithError($postObj->getError(), true);
        }
        FatUtility::dieJsonSuccess(Labels::getLabel('MSG_ORDERED_SUCCESSFULLY', $this->siteLangId));
    }

    public function uploadMedia()
    {
        $this->checkEditPrivilege();
        $recordId = FatApp::getPostedData('record_id', FatUtility::VAR_INT, 0);
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
        if ($fileType != AttachedFile::FILETYPE_BLOG_POST_IMAGE) {
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
        $this->checkEditPrivilege();
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
        if (!$fileHandlerObj->deleteFile(AttachedFile::FILETYPE_BLOG_POST_IMAGE, $recordId, $afile_id, 0, $langId)) {
            LibHelper::exitWithError($fileHandlerObj->getError(), true);
        }
        $this->set('msg', Labels::getLabel('MSG_DELETED_SUCCESSFULLY', $this->siteLangId));
        $this->_template->render(false, false, 'json-success.php');
    }

    private function getImagesFrm($recordId = 0)
    {
        $bannerTypeArr = applicationConstants::getAllLanguages();

        $frm = new Form('frmRecordImage', array('id' => 'imageFrm'));
        $frm->addHiddenField('', 'record_id', $recordId);
        $frm->addHiddenField('', 'file_type', AttachedFile::FILETYPE_BLOG_POST_IMAGE);
        $frm->addHiddenField('', 'min_width');
        $frm->addHiddenField('', 'min_height');

        if (count($bannerTypeArr) > 1) {
            $frm->addSelectBox(Labels::getLabel('FRM_LANGUAGE', $this->siteLangId), 'lang_id', $bannerTypeArr, '', array(), '');
        } else {
            $langId = array_key_first($bannerTypeArr);
            $frm->addHiddenField('', 'lang_id', $langId);
        }
        $frm->addHtml('', 'post_image', '');
        return $frm;
    }

    private function getForm($recordId = 0)
    {
        $recordId = FatUtility::int($recordId);
        $frm = new Form('frmBlogPost');
        $frm->addHiddenField('', 'post_id', 0);
        $frm->addRequiredField(Labels::getLabel('FRM_POST_TITLE', $this->siteLangId), 'post_title');
        $frm->addRequiredField(Labels::getLabel('FRM_POST_AUTHOR_NAME', $this->siteLangId), 'post_author_name');
        $frm->addHtmlEditor(Labels::getLabel('FRM_DESCRIPTION', $this->siteLangId), 'post_description')->requirements()->setRequired(true);
        $fld = $frm->addTextBox(Labels::getLabel('FRM_SEO_FRIENDLY_URL', $this->siteLangId), 'urlrewrite_custom');
        $fld->requirements()->setRequired();
        $postStatusArr = BlogPost::getBlogPostStatusArr($this->siteLangId);

        $fld = $frm->addTextBox(Labels::getLabel('FRM_CATEGORY', $this->siteLangId), 'categories');
        $fld->requirements()->setRequired();
        $frm->addCheckBox(Labels::getLabel('FRM_ALLOW_COMMENTS', $this->siteLangId), 'post_comment_opened', 1, array(), false, 0);

        $frm->addCheckBox(Labels::getLabel('FRM_FEATURED', $this->siteLangId), 'post_featured', 1, array(), false, 0);
        $frm->addCheckBox($postStatusArr[applicationConstants::PUBLISHED], 'post_published', 1, array(), false, 0);

        $translatorSubscriptionKey = FatApp::getConfig('CONF_TRANSLATOR_SUBSCRIPTION_KEY', FatUtility::VAR_STRING, '');
        $languageArr = Language::getDropDownList();
        if (!empty($translatorSubscriptionKey) && 1 < count($languageArr)) {
            $frm->addCheckBox(Labels::getLabel('FRM_UPDATE_OTHER_LANGUAGES_DATA', $this->siteLangId), 'auto_update_other_langs_data', 1, array(), false, 0);
        }
        return $frm;
    }

    protected function getLangForm($recordId = 0, $langId = 0)
    {
        $langId = 1 > $langId ? $this->siteLangId : $langId;
        $recordId = FatUtility::int($recordId);
        $frm = new Form('frmBlogPostCatLang', array('id' => 'frmBlogPostCatLang'));
        $frm->addHiddenField('', 'post_id', $recordId);

        $frm->addSelectBox(Labels::getLabel('FRM_LANGUAGE', $langId), 'lang_id', Language::getDropDownList(CommonHelper::getDefaultFormLangId()), $langId, array(), '');

        $frm->addRequiredField(Labels::getLabel('FRM_TITLE', $langId), 'post_title');
        $frm->addRequiredField(Labels::getLabel('FRM_POST_AUTHOR_NAME', $langId), 'post_author_name');
        $frm->addHtmlEditor(Labels::getLabel('FRM_DESCRIPTION', $langId), 'post_description')->requirements()->setRequired(true);

        return $frm;
    }

    public function getSearchForm($fields = [])
    {
        $frm = new Form('frmRecordSearch');
        $frm->addHiddenField('', 'page');

        if (!empty($fields)) {
            $this->addSortingElements($frm, 'post_title');
        }

        $fld = $frm->addTextBox(Labels::getLabel('FRM_KEYWORD', $this->siteLangId), 'keyword');
        $fld->overrideFldType('search');

        $postStatusArr = BlogPost::getBlogPostStatusArr($this->siteLangId);
        $frm->addSelectBox(Labels::getLabel('FRM_POST_STATUS', $this->siteLangId), 'post_published', $postStatusArr, '', array('class' => 'form-control'), Labels::getLabel('FRM_POST_STATUS', $this->siteLangId));
        $frm->addHiddenField('', 'total_record_count');
        $frm->addHiddenField('', 'bpcat_id');
        HtmlHelper::addSearchButton($frm);
        HtmlHelper::addClearButton($frm);
        return $frm;
    }

    public function getCategories()
    {
        $keyword = FatApp::getPostedData('keyword', FatUtility::VAR_STRING, '');
        $prodCatObj = new BlogPostCategory();
        $data = $prodCatObj->getBlogPostCatTreeStructure(0, $keyword);
        $json = array();
        foreach ($data as $id => $value) {
            $json[] = array(
                'id' => $id,
                'value' => $value,
            );
        }
        die(json_encode($json));
    }

    public function autoComplete()
    {
        $pagesize = FatApp::getConfig('CONF_PAGE_SIZE');
        $post = FatApp::getPostedData();

        $postObj = new BlogPost();
        $srch = $postObj->getSearchObject($this->siteLangId, false, true);

        $srch->addMultipleFields(array('post_id, IFNULL(post_title, post_identifier) as post_title'));

        if (isset($post['keyword']) && '' != $post['keyword']) {
            $cond = $srch->addCondition('post_title', 'LIKE', '%' . $post['keyword'] . '%');
            $cond->attachCondition('post_identifier', 'LIKE', '%' . $post['keyword'] . '%', 'OR');
        }

        $excludeRecords = FatApp::getPostedData('excludeRecords', FatUtility::VAR_INT);
        if (!empty($excludeRecords) && is_array($excludeRecords)) {
            $srch->addCondition('post_id', 'NOT IN', $excludeRecords);
        }

        $collectionId = FatApp::getPostedData('collection_id', FatUtility::VAR_INT, 0);
        $alreadyAdded = Collections::getRecords($collectionId);
        if (!empty($alreadyAdded) && 0 < count($alreadyAdded)) {
            $srch->addCondition('post_id', 'NOT IN', array_keys($alreadyAdded));
        }

        $srch->setPageSize($pagesize);
        $rs = $srch->getResultSet();
        $db = FatApp::getDb();
        $posts = $db->fetchAll($rs, 'post_id');
        $json = array(
            'pageCount' => $srch->pages(),
            'results' => []
        );
        foreach ($posts as $key => $post) {
            $json['results'][] = array(
                'id' => $key,
                'text' => strip_tags(html_entity_decode($post['post_title'], ENT_QUOTES, 'UTF-8'))
            );
        }
        die(json_encode($json));
    }

    protected function changeStatus(int $recordId, int $status)
    {
        $status = FatUtility::int($status);
        $recordId = FatUtility::int($recordId);
        if (1 > $recordId || -1 == $status) {
            LibHelper::exitWithError($this->str_invalid_request, true);
        }
        $this->setModel([$recordId]);
        $this->modelObj->assignValues(['post_published' => $status, 'post_published_on' => (applicationConstants::ACTIVE == $status ? date('Y-m-d H:i:s') : '')]);

        if (!$this->modelObj->save()) {
            LibHelper::exitWithError($this->modelObj->getError(), true);
        }
    }

    protected function getFormColumns(): array
    {
        $blogPostsItemsTblHeadingCols = CacheHelper::get('blogPostsItemsTblHeadingCols' . $this->siteLangId, CONF_DEF_CACHE_TIME, '.txt');
        if ($blogPostsItemsTblHeadingCols) {
            return json_decode($blogPostsItemsTblHeadingCols, true);
        }

        $arr = [
            'select_all' => Labels::getLabel('LBL_SELECT_ALL', $this->siteLangId),
            'post_title' => Labels::getLabel('LBL_POST_TITLE', $this->siteLangId),
            'categories' => Labels::getLabel('LBL_POST_CATEGORY', $this->siteLangId),
            'post_published_on' => Labels::getLabel('LBL_PUBLISHED_DATE', $this->siteLangId),
            'post_published' => Labels::getLabel('LBL_PUBLISHED', $this->siteLangId),
            'action' => Labels::getLabel('LBL_ACTION_BUTTONS', $this->siteLangId),
        ];
        CacheHelper::create('blogPostsItemsTblHeadingCols' . $this->siteLangId, json_encode($arr), CacheHelper::TYPE_LABELS);

        return $arr;
    }

    protected function getDefaultColumns(): array
    {
        return [
            'select_all',
            'post_title',
            'categories',
            'post_published_on',
            'post_published',
            'action',
        ];
    }

    protected function excludeKeysForSort($fields = []): array
    {
        return array_diff($fields, Common::excludeKeysForSort());
    }
}
