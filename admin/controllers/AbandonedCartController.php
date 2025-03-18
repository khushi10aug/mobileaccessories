<?php

class AbandonedCartController extends ListingBaseController
{

    protected string $pageKey = 'MANAGE_ABANDONED_CART';

    public function __construct($action)
    {
        parent::__construct($action);
        $this->objPrivilege->canViewAbandonedCart();
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
                'href' => urlHelper::generateUrl('AbandonedCartProducts'),
                'title' => Labels::getLabel('LBL_VIEW_BY_PRODUCT', $this->siteLangId),
                'onclick' => ''
            ],
            'label' => '<svg class="svg btn-icon-start" width="18" height="18">
                            <use xlink:href="' . CONF_WEBROOT_URL . 'images/retina/sprite-actions.svg' . AttachedFile::setTimeParam(RELEASE_DATE) . '#view"></use>
                        </svg><span>' . Labels::getLabel('BTN_PRODUCTS', $this->siteLangId) . '</span>',
        ];

        $this->set('pageData', $pageData);
        $this->set('pageTitle', $pageTitle);
        $this->set('actionItemsData', $actionItemsData);
        $this->set("frmSearch", $frmSearch);
        $this->set('defaultColumns', $this->getDefaultColumns());
        $this->getListingData();

        $this->_template->addJs(array('js/select2.js', 'abandoned-cart/page-js/index.js', 'js/cropper.js', 'js/cropper-main.js'));
        $this->_template->addCss(array('css/select2.min.css', 'css/cropper.css'));
        $this->_template->render(true, true, null, false, false);
    }

    public function search()
    {
        $loadPagination = FatApp::getPostedData('loadPagination', FatUtility::VAR_INT, 0);
        $this->getListingData($loadPagination);

        $jsonData = [
            'paginationHtml' => $this->_template->render(false, false, '_partial/listing/listing-foot.php', true)
        ];
        if (!$loadPagination || !FatUtility::isAjaxCall()) {
            $jsonData['listingHtml'] = $this->_template->render(false, false, 'abandoned-cart/search.php', true);
        }
        LibHelper::exitWithSuccess($jsonData, true);
    }

    private function getListingData($loadPagination = 0)
    {
        $fields = $this->getFormColumns();
        $selectedFlds = FatApp::getPostedData('reportColumns', FatUtility::VAR_STRING, '');
        $selectedFlds = !empty($selectedFlds) ? json_decode($selectedFlds) + $this->getDefaultColumns() : $this->getDefaultColumns();
        $fields = FilterHelper::parseArrayByKeys($fields, $selectedFlds, true);

        $allowedKeysForSorting = $this->excludeKeysForSort(array_keys($fields));
        $sortBy = FatApp::getPostedData('sortBy', FatUtility::VAR_STRING, 'abandonedcart_added_on');
        if (!array_key_exists($sortBy, $fields)) {
            $sortBy = 'abandonedcart_added_on';
        }
        $sortOrder = applicationConstants::getSortOrder(FatApp::getPostedData('sortOrder', FatUtility::VAR_STRING, applicationConstants::SORT_DESC), applicationConstants::SORT_DESC);

        $page = FatApp::getPostedData('page', FatUtility::VAR_INT, 1);
        $page = ($page <= 0) ? 1 : $page;

        $pageSize = applicationConstants::getPageSize(FatApp::getPostedData('pageSize', FatUtility::VAR_INT));
        $searchForm = $this->getSearchForm($fields);
        $postedData = FatApp::getPostedData();
        $post = $searchForm->getFormDataFromArray($postedData);

        $userId = FatApp::getPostedData('abandonedcart_user_id', FatUtility::VAR_INT, 0);
        $selProdId = FatApp::getPostedData('abandonedcart_selprod_id', FatUtility::VAR_INT, 0);
        $action = FatApp::getPostedData('abandonedcart_action', FatUtility::VAR_INT, 0);
        $dateFrom = FatApp::getPostedData('date_from', null, '');
        $dateTo = FatApp::getPostedData('date_to', null, '');

        $srch = new AbandonedCartSearch();
        $srch->joinUsers();
        $srch->joinSellerProducts($this->siteLangId);
        $srch->addActionCondition($action);
        if ($userId > 0) {
            $srch->addCondition(AbandonedCart::DB_TBL_PREFIX . 'user_id', '=', 'mysql_func_' . $userId, 'AND', true);
        }

        if ($selProdId > 0) {
            $srch->addCondition(AbandonedCart::DB_TBL_PREFIX . 'selprod_id', '=', 'mysql_func_' . $selProdId, 'AND', true);
        }

        if (!empty($dateFrom)) {
            $srch->addCondition(AbandonedCart::DB_TBL_PREFIX . 'added_on', '>=', $dateFrom . ' 00:00:00');
        }

        if (!empty($dateTo)) {
            $srch->addCondition(AbandonedCart::DB_TBL_PREFIX . 'added_on', '<=', $dateTo . ' 23:59:59');
        }

        if ($action != AbandonedCart::ACTION_PURCHASED) {
            $srch->addSubQueryCondition();
            $srch->addCondition(AbandonedCart::DB_TBL_PREFIX . 'email_count', '<', 'mysql_func_' . AbandonedCart::MAX_EMAIL_COUNT, 'AND', true);
            $srch->addCondition(AbandonedCart::DB_TBL_PREFIX . 'discount_notification', '<=', 'mysql_func_' . AbandonedCart::MAX_DISCOUNT_NOTIFICATION, 'AND', true);
        }

        if ($action == AbandonedCart::ACTION_PURCHASED) {
            $cnd = $srch->addCondition(AbandonedCart::DB_TBL_PREFIX . 'email_count', '>', 'mysql_func_0', 'AND', true);
            $cnd->attachCondition(AbandonedCart::DB_TBL_PREFIX . 'discount_notification', '>', 'mysql_func_0', 'OR', true);
        }

        if ($loadPagination && FatUtility::isAjaxCall()) {
            $this->setRecordCount(clone $srch, $pageSize, $page, $post);
        }
        $srch->doNotCalculateRecords();

        $srch->addOrder($sortBy, $sortOrder);
        $srch->setPageNumber($page);
        $srch->setPageSize($pageSize);
        $records = [];
        if (!$loadPagination) {
            $records = FatApp::getDb()->fetchAll($srch->getResultSet());
        }
        $this->set("arrListing", $records);
        $paginationArr = empty($postedData) ? $post : $postedData;
        $this->set('postedData', $paginationArr);
        $this->set('sortBy', $sortBy);
        $this->set('sortOrder', $sortOrder);
        $this->set('fields', $fields);
        $this->set('allowedKeysForSorting', $allowedKeysForSorting);

        /* Discount notification action button is used. */
        $this->set('canEdit', $this->objPrivilege->canViewDiscountCoupons($this->admin_id, true));
    }

    public function getSearchForm(array $fields = [])
    {
        $frm = new Form('frmAbandonedCartSearch');
        $frm->addHiddenField('', 'page', 1);
        if (!empty($fields)) {
            $this->addSortingElements($frm, 'abandonedcart_added_on', applicationConstants::SORT_DESC);
        }

        $frm->addSelectBox(Labels::getLabel('FRM_SEARCH_BY_USER_NAME_OR_EMAIL', $this->siteLangId), 'abandonedcart_user_id', []);
        $frm->addSelectBox(Labels::getLabel('FRM_SELLER_PRODUCT', $this->siteLangId), 'abandonedcart_selprod_id', [], '', ['placeholder' => Labels::getLabel('FRM_SELECT', $this->siteLangId)]);
        $actionArr = AbandonedCart::getActionArr($this->siteLangId);
        unset($actionArr[AbandonedCart::ACTION_PURCHASED]);
        $frm->addSelectBox(Labels::getLabel('FRM_CART_ACTION', $this->siteLangId), 'abandonedcart_action', $actionArr);
        $frm->addDateField(Labels::getLabel('FRM_DATE_FROM', $this->siteLangId), 'date_from', '', array('placeholder' => Labels::getLabel('FRM_DATE_FROM', $this->siteLangId), 'readonly' => 'readonly', 'class' => 'field--calender'));
        $frm->addDateField(Labels::getLabel('FRM_DATE_TO', $this->siteLangId), 'date_to', '', array('placeholder' => Labels::getLabel('FRM_DATE_TO', $this->siteLangId), 'readonly' => 'readonly', 'class' => 'field--calender'));
        $frm->addHiddenField('', 'total_record_count');
        HtmlHelper::addSearchButton($frm);
        HtmlHelper::addClearButton($frm);/*clearBtn*/
        return $frm;
    }


    public function discountNotification()
    {
        $abandonedcartId = FatApp::getPostedData('abandonedcartId', FatUtility::VAR_INT, 0);
        $couponId = FatApp::getPostedData('couponId', FatUtility::VAR_INT, 0);
        if ($abandonedcartId < 1 || $couponId < 1) {
            LibHelper::exitWithError(Labels::getLabel('ERR_EMAIL_NOT_SENT_INVALID_PARAMETERS', $this->siteLangId), true);
        }

        $abandonedCart = new AbandonedCart($abandonedcartId);
        $abandonedCart->updateDiscountNotification();
        if (!$abandonedCart->sendDiscountEmail($couponId)) {
            LibHelper::exitWithError($abandonedCart->getError(), true);
        }
        $this->set('msg', Labels::getLabel('MSG_Email_Sent_Successful', $this->siteLangId));
        $this->_template->render(false, false, 'json-success.php');
    }

    public function validateProductForNotification($productId)
    {
        $productId = FatUtility::int($productId);
        if ($productId < 1) {
            LibHelper::exitWithError(Labels::getLabel('ERR_INVALID_REQUEST', $this->siteLangId), true);
        }

        $product = AbandonedCart::validateProductForNotification($productId);
        if (empty($product)) {
            LibHelper::exitWithError(Labels::getLabel('ERR_PRODUCT_IS_EITHER_DELETED/DISABLED_OR_OUT_OF_STOCK', $this->siteLangId), true);
        }
        $this->_template->render(false, false, 'json-success.php');
    }

    protected function getFormColumns(): array
    {
        $tblHeadingCols = CacheHelper::get('abandonedCartFormTblHeadingCols' . $this->siteLangId, CONF_DEF_CACHE_TIME, '.txt');
        if ($tblHeadingCols) {
            return json_decode($tblHeadingCols, true);
        }

        $arr = [
            /* 'listSerial' => Labels::getLabel('LBL_SR._NO', $this->siteLangId), */
            'user_name' => Labels::getLabel('LBL_USER', $this->siteLangId),
            'selprod_title' => Labels::getLabel('LBL_SELLER_PRODUCT', $this->siteLangId),
            'abandonedcart_qty' => Labels::getLabel('LBL_QTY', $this->siteLangId),
            'abandonedcart_action' => Labels::getLabel('LBL_CART_ACTION', $this->siteLangId),
            'abandonedcart_added_on' => Labels::getLabel('LBL_DATE', $this->siteLangId),
            'action' => Labels::getLabel('LBL_ACTION_BUTTONS', $this->siteLangId),
        ];

        CacheHelper::create('abandonedCartFormTblHeadingCols' . $this->siteLangId, json_encode($arr), CacheHelper::TYPE_LABELS);
        return $arr;
    }

    protected function getDefaultColumns(): array
    {
        return [
            /* 'listSerial', */
            'user_name',
            'selprod_title',
            'abandonedcart_qty',
            'abandonedcart_action',
            'abandonedcart_added_on',
            'action',
        ];
    }

    protected function excludeKeysForSort($fields = []): array
    {
        return array_diff($fields, Common::excludeKeysForSort());
    }
}
