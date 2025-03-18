<?php
class RfqOffersController extends ListingBaseController
{
    protected string $modelClass = 'RfqOffers';
    protected $pageKey = 'MANAGE_RFQ_OFFERS';
    protected $rfqId;
    private array $offerData = [];

    public function __construct($action)
    {
        parent::__construct($action);
        $this->objPrivilege->canViewRfqOffers();
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
            $this->set("canEdit", $this->objPrivilege->canEditRfqOffers($this->admin_id, true));
        } else {
            $this->objPrivilege->canEditRfqOffers();
        }
    }

    public function index(int $rfqId = 0)
    {
        if (1 > $rfqId) {
            CommonHelper::redirectUserReferer();
        }
        FatApp::redirectUser(UrlHelper::generateUrl('RfqOffers', 'listing', [$rfqId]));
    }

    public function listing(int $rfqId)
    {
        if (1 > $rfqId) {
            LibHelper::exitWithError($this->str_invalid_request, false, true);
            CommonHelper::redirectUserReferer();
        }

        $rfqData = RequestForQuote::getAttributesById($rfqId, ['rfq_approved', 'rfq_selprod_id', 'rfq_product_id', 'rfq_visibility_type']);

        if (false == $rfqData || $rfqData['rfq_approved'] != RequestForQuote::APPROVED) {
            LibHelper::exitWithError(Labels::getLabel('ERR_RFQ_STATUS_IS_NOT_APPROVED'), false, true);
            CommonHelper::redirectUserReferer();
        }

        $this->rfqId = $rfqId;
        $fields = $this->getFormColumns();
        $frmSearch = $this->getSearchForm($fields);
        $pageData = PageLanguageData::getAttributesByKey($this->pageKey, $this->siteLangId);
        $pageTitle = $pageData['plang_title'] ?? LibHelper::getControllerName(true);

        $srch = new RequestForQuoteSearch();
        $srch->joinBuyer();
        $srch->joinBuyerAddress($this->siteLangId);
        $srch->joinCountry(true);
        $srch->joinState(true);
        $srch->joinRfqCategory(true);

        $dbFlds = array_merge(RequestForQuote::FIELDS, ['addr_name', 'addr_address1', 'addr_address2', 'addr_city', 'state_name', 'country_name', 'addr_zip', 'addr_phone_dcode', 'addr_phone', 'buc.credential_username', 'buc.credential_email', 'bu.user_id as user_id', 'bu.user_updated_on', 'bu.user_name', 'IFNULL(country_name, country_code) as country_name', 'IFNULL(state_name, state_identifier) as state_name', 'rfq.rfq_product_id', 'rfq_selprod_code', 'COALESCE(prodcat_name, prodcat_identifier) as prodcat_name']);
        $srch->addMultipleFields($dbFlds);

        $srch->addCondition('rfq_id', '=', $rfqId);
        $rfqData = (array)FatApp::getDb()->fetch($srch->getDataResultSet());
        if (empty($rfqData)) {
            LibHelper::exitWithError($this->str_invalid_request, false, true);
            CommonHelper::redirectUserReferer();
        }

        $selProdId = SellerProduct::getSellerProductIdByCode($rfqData['rfq_selprod_code']);
        $frmSearch->fill(['offer_rfq_id' => $rfqId, 'rfq_product_id' => $rfqData['rfq_product_id']]);
        $actionItemsData = HtmlHelper::getDefaultActionItems($fields);
        if (!in_array($rfqData['rfq_status'], [RequestForQuote::STATUS_OPEN, RequestForQuote::STATUS_OFFERED])) {
            $actionItemsData['newRecordBtn'] = false;
        } else {
            $actionItemsData['newRecordBtnAttrs'] = [
                'attr' => [
                    'onclick' => 'addNew(' . $rfqId . ')',
                    'title' => Labels::getLabel('LBL_OFFER', $this->siteLangId)
                ],
                'label' => '<svg class="svg btn-icon-start" width="18" height="18">
                                <use xlink:href="' . CONF_WEBROOT_URL . 'images/retina/sprite-actions.svg' . AttachedFile::setTimeParam(RELEASE_DATE) . '#add">
                                </use>
                            </svg><span>' . Labels::getLabel('LBL_NEW_OFFER', $this->siteLangId) . '</span>',
            ];
        }

        $rfqData['sellerProdOptions'] = SellerProduct::getSellerProductOptionsBySelProdCode($rfqData['rfq_selprod_code'], $this->siteLangId);

        $this->set('pageData', $pageData);
        $this->set('selProdId', $selProdId);
        $this->set('pageTitle', $pageTitle);
        $this->set('actionItemsData', $actionItemsData);
        $this->set("frmSearch", $frmSearch);
        $this->set('defaultColumns', $this->getDefaultColumns());
        $this->set('rfqData', $rfqData);
        $this->set('keywordPlaceholder', Labels::getLabel('FRM_SEARCH_BY_COMMENT', $this->siteLangId));
        $this->getListingData($this->rfqId);
        $this->set('defaultPageSize', FatApp::getConfig('CONF_ADMIN_PAGESIZE', FatUtility::VAR_INT, 10));
        $this->_template->addJs(array('js/select2.js'));
        $this->_template->addCss(array('css/select2.min.css'));
        $this->_template->render(convertVariablesToHtmlentities: false);
    }

    protected function getSearchForm($fields = [])
    {
        $frm = new Form('frmRecordSearch');
        $frm->addHiddenField('', 'page', 1);
        $frm->addHiddenField('', 'total_record_count');
        $frm->addHiddenField('', 'offer_rfq_id');
        $frm->addHiddenField('', 'rfq_product_id');
        if (!empty($fields)) {
            $this->addSortingElements($frm, 'offer_added_on', applicationConstants::SORT_DESC);
        }
        $fld = $frm->addTextBox(Labels::getLabel('FRM_KEYWORD', $this->siteLangId), 'keyword', '');
        $fld->overrideFldType('search');

        $frm->addSelectBox(Labels::getLabel('FRM_SELLER', $this->siteLangId), 'offer_user_id', []);

        $statusArr = array(-1 => Labels::getLabel('FRM_DOES_NOT_MATTER', $this->siteLangId)) + RfqOffers::getStatusArr($this->siteLangId);
        $frm->addSelectBox(Labels::getLabel('FRM_STATUS', $this->siteLangId), 'offer_status', $statusArr, '', array(), '');
        HtmlHelper::addSearchButton($frm);
        HtmlHelper::addClearButton($frm);/*clearBtn*/
        return $frm;
    }

    public function search()
    {
        $rfqId = FatApp::getPostedData('rfqId', FatUtility::VAR_INT, 0);
        $this->getListingData($rfqId);
        $jsonData = [
            'listingHtml' => $this->_template->render(false, false, 'rfq-offers/search.php', true),
            'paginationHtml' => $this->_template->render(false, false, 'rfq-offers/listing-foot.php', true)
        ];
        LibHelper::exitWithSuccess($jsonData, true);
    }

    private function getListingData(int $rfqId)
    {
        $this->checkEditPrivilege(true);
        $rfqId = FatApp::getPostedData('offer_rfq_id', FatUtility::VAR_INT, $rfqId);

        $fields = $this->getFormColumns();
        $selectedFlds = FatApp::getPostedData('reportColumns', FatUtility::VAR_STRING, '');
        $selectedFlds = !empty($selectedFlds) ? json_decode($selectedFlds) +  $this->getDefaultColumns() : $this->getDefaultColumns();
        $fields =  FilterHelper::parseArrayByKeys($fields, $selectedFlds, true);

        $allowedKeysForSorting = $this->excludeKeysForSort(array_keys($fields));
        $sortBy = FatApp::getPostedData('sortBy', FatUtility::VAR_STRING, 'offer_added_on');
        if (!array_key_exists($sortBy, $fields)) {
            $sortBy = 'offer_added_on';
        }

        $sortOrder = applicationConstants::getSortOrder(FatApp::getPostedData('sortOrder', FatUtility::VAR_STRING), applicationConstants::SORT_DESC);

        $srchFrm = $this->getSearchForm($fields);

        $postedData = FatApp::getPostedData();
        $post = $srchFrm->getFormDataFromArray($postedData);
        $post['offer_rfq_id'] = $rfqId;

        $page = FatApp::getPostedData('page', FatUtility::VAR_INT, 1);
        $page = ($page <= 0) ? 1 : $page;

        $pageSize = applicationConstants::getAdminPageSize(FatApp::getPostedData('pageSize', FatUtility::VAR_INT));

        $srch = new RequestForQuoteSearch();
        $srch->joinOffers(true);
        $srch->joinOfferLinkedSeller();
        $srch->joinBuyer();
        $srch->addCondition('ro.offer_rfq_id', '=', $rfqId);
        $srch->addCondition('rfq_deleted', '=', applicationConstants::NO);

        $sellerId = FatApp::getPostedData('offer_user_id', FatUtility::VAR_INT, 0);
        if (0 < $sellerId) {
            $srch->addCondition('rlo_seller_user_id', '=', $sellerId);
        }

        $status = FatApp::getPostedData('offer_status', FatUtility::VAR_STRING, -1);
        if (-1 < $status) {
            $cnd = $srch->addCondition('ro.offer_status', '=', $status);
            $cnd->attachCondition('roc.offer_status', '=', $status);
        }

        $comments = FatApp::getPostedData('keyword', FatUtility::VAR_STRING, '');
        if (!empty($comments)) {
            $cnd = $srch->addCondition('ro.offer_comments', 'LIKE', '%' . $comments . '%');
            $cnd->attachCondition('roc.offer_comments', 'LIKE', '%' . $comments . '%');
        }

        $this->setRecordCount(clone $srch, $pageSize, $page, $post);

        $flds = LibHelper::addPrefixInArrValues(RfqOffers::FIELDS, 'ro.');
        $counterOfferFlds = [];
        foreach (RfqOffers::FIELDS as $fld) {
            array_push($counterOfferFlds, 'roc.' . $fld . ' as counter_' . $fld);
        }

        $dbFlds = array_merge($flds, $counterOfferFlds, ['rlo_seller_user_id', 'olu.user_name', 'oluc.credential_email', 'olu.user_updated_on', 'olu.user_id', 'bu.user_name as buyer_user_name', 'bu.user_id as buyer_user_id', 'buc.credential_email as buyer_credential_email', 'rlo_primary_offer_id', 'rfq_product_type', 'rfq_added_on', 'rfq_status', 'rfq_quantity_unit', 'rlo_shipping_charges', 'rlo_seller_user_id', 'rlo_seller_offer_id', 'rlo_buyer_offer_id', 'rlo_buyer_acceptance', 'rlo_seller_acceptance']);
        $srch->addMultipleFields($dbFlds);
        $srch->addOrder($sortBy, $sortOrder);
        $srch->setPageNumber($page);
        $srch->setPageSize($pageSize);
        $arrListing = FatApp::getDb()->fetchAll($srch->getDataResultSet());
        $offersCountArr = [];
        $primaryOfferIds = array_column($arrListing, 'offer_primary_offer_id');
        if (!empty($primaryOfferIds)) {
            $offersCountArr =  RfqOffers::getOffersCountArr($primaryOfferIds);
        }
        $this->set("arrListing", $arrListing);
        $this->set("offersCountArr", $offersCountArr);
        $this->set('postedData', $post);
        $this->set('rfqId', $rfqId);
        $this->set('sortBy', $sortBy);
        $this->set('sortOrder', $sortOrder);
        $this->set('fields', $fields);
        $this->set('allowedKeysForSorting', $allowedKeysForSorting);
        $this->set("statusArr", RfqOffers::getStatusArr($this->siteLangId));
        $this->set("rfqStatusArr", RequestForQuote::getStatusArr($this->siteLangId));
        $this->set("approvalStatusArr", RequestForQuote::getApprovalStatusArr($this->siteLangId));
        $this->set('rfqStatus', RequestForQuote::getAttributesById($rfqId, 'rfq_status'));
        $this->set('canViewUsers', $this->objPrivilege->canViewUsers($this->admin_id, true));
    }

    public function view()
    {
        $rfqId = FatApp::getPostedData('rfqId', FatUtility::VAR_INT, 0);
        $primaryOfferId = FatApp::getPostedData('offerId', FatUtility::VAR_INT, 0);
        if (1 > $rfqId || 1 > $primaryOfferId) {
            LibHelper::exitWithError($this->str_invalid_request_id, true);
        }

        $pageSize = FatApp::getConfig('CONF_ADMIN_PAGESIZE', FatUtility::VAR_INT, 10);
        $page = FatApp::getPostedData('page', FatUtility::VAR_INT, 1);
        $page = ($page <= 0) ? 1 : $page;

        $srch = new RequestForQuoteSearch();
        $srch->joinOffers();
        $srch->joinOfferPostedByUser();
        $srch->addCondition('ro.offer_rfq_id', '=', $rfqId);
        $srch->addCondition('ro.offer_primary_offer_id', '=', $primaryOfferId);
        $srch->addCondition('rfq_deleted', '=', applicationConstants::NO);

        $this->setRecordCount(clone $srch, $pageSize, $page, $post);

        $srch->addOrder('ro.offer_added_on', 'DESC');
        $arrListing = FatApp::getDb()->fetchAll($srch->getDataResultSet());
        $this->set("arrListing", $arrListing);
        $this->set('postedData', FatApp::getPostedData());

        $jsonData = [
            'html' => $this->_template->render(false, false, 'rfq-offers/view.php', true),
        ];
        LibHelper::exitWithSuccess($jsonData, true);
    }

    private function getShopUser($userId): array
    {
        if (empty($userId)) {
            return [];
        }
        $userIds = is_array($userId) ? $userId : [$userId];
        $srch = Shop::getSearchObject(true, $this->siteLangId);
        $srch->addCondition(Shop::tblFld('user_id'), 'IN', $userIds, 'AND', true);
        $srch->joinTable(User::DB_TBL, 'INNER JOIN', 'su.user_id = s.shop_user_id', 'su');
        $srch->addMultipleFields(['shop_user_id', 'CONCAT(user_name, " (", COALESCE(s_l.shop_name, s.shop_identifier), ")") as shopuser']);
        return (array)FatApp::getDb()->fetchAllAssoc($srch->getResultSet(), Shop::tblFld('user_id'));
    }

    private function getForm(): Form
    {
        $frm = RfqOffers::getSellerForm();
        $frm->addHiddenField('', 'rfq_product_id');
        return $frm;
    }

    public function form()
    {
        $this->checkEditPrivilege();

        $rfqId = FatApp::getPostedData('rfqId', FatUtility::VAR_INT, 0);
        $rfqData = RequestForQuote::getAttributesById($rfqId, ['rfq_id', 'rfq_selprod_id', 'rfq_product_type', 'rfq_product_id', 'rfq_quantity', 'rfq_quantity_unit', 'rfq_visibility_type']);
        if (!$rfqData) {
            LibHelper::exitWithError($this->str_invalid_request, true);
        }
        $recordId = FatApp::getPostedData('recordId', FatUtility::VAR_INT, 0);
        $rfqOfferData = (array)RfqOffers::getAttributesById($recordId, RfqOffers::FIELDS);

        $frm = $this->getForm();
        $counterOfferId = FatApp::getPostedData('offer_counter_offer_id', FatUtility::VAR_INT, 0);

        $options = [];
        $selProdPrice = 0;
        if (0 < $recordId) {
            $shopUserId = (int)RfqOffers::getAttributesById($recordId, 'offer_user_id');
            $options = $this->getShopUser($shopUserId);
            $rfq = new RequestForQuote($rfqId);
            $attr = [
                'rfq_product_type',
                'rfq_title',
                'rfqts_user_id as seller_id',
                'rfqts_selprod_id'
            ];
            $rfqDataArr = $rfq->get($this->siteLangId, $attr, 'LEFT', $shopUserId);
            
            if (!empty($rfqDataArr) && !empty($rfqDataArr['rfqts_selprod_id'])) {
                $selProdPrice = SellerProduct::getAttributesById($rfqDataArr['rfqts_selprod_id'], 'selprod_price');
            }
        } else {
            $sellerData = RequestForQuote::getSellersByRecordId($rfqId);
            $sellerIds = array_column($sellerData, 'rfqts_user_id');
            $options = $this->getShopUser($sellerIds);
        }

        if (0 < $counterOfferId) {
            $fld = $frm->getField('offer_user_id');
            $frm->removeField($fld);
        } else {
            $fld = $frm->getField('offer_user_id');
            $fld->options = $options;
        }

        if (0 < $counterOfferId) {
            $qty = (int)RfqOffers::getAttributesById($counterOfferId, 'offer_quantity');
        } else {
            $qty = isset($rfqOfferData['offer_quantity']) && !empty($rfqOfferData['offer_quantity']) ? $rfqOfferData['offer_quantity'] : $rfqData['rfq_quantity'];
        }
        $qtyUnit = $rfqData['rfq_quantity_unit'];

        $shippingcharges = '';
        /* In case of counter offer of buyer and edit own record. */
        if (Product::PRODUCT_TYPE_PHYSICAL == $rfqData['rfq_product_type'] && (0 < $counterOfferId || 0 < $recordId)) {
            $srch = new SearchBase(RfqOffers::DB_RFQ_LATEST_OFFER);
            $srch->doNotCalculateRecords();
            $srch->setPageSize(1);
            $srch->addCondition('rlo_rfq_id', '=', 'mysql_func_' . $rfqId, 'AND', true);
            if (0 < $recordId) {
                $srch->addCondition('rlo_seller_offer_id', '=', 'mysql_func_' . $recordId, 'AND', true);
            } else {
                $srch->addCondition('rlo_buyer_offer_id', '=', 'mysql_func_' . $counterOfferId, 'AND', true);
            }
            $srch->addFld('rlo_shipping_charges');
            $shippingcharges = ((array)FatApp::getDb()->fetch($srch->getResultSet()))['rlo_shipping_charges'] ?? '';
        }

        if (Product::PRODUCT_TYPE_PHYSICAL != $rfqData['rfq_product_type']) {
            $fld = $frm->getField('rlo_shipping_charges');
            $frm->removeField($fld);
        }

        $data = array_merge($rfqOfferData, [
            'offer_rfq_id' => $rfqId,
            'offer_quantity' => $qty,
            'rfq_quantity_unit' => $qtyUnit,
            'rfq_product_id' => $rfqData['rfq_product_id'] ?? 0,
            'rlo_shipping_charges' => (0 == $shippingcharges ? '' : $shippingcharges),
        ]);
        if (0 < $counterOfferId) {
            $data['offer_counter_offer_id'] = $counterOfferId;
        }
        $frm->fill($data);

        $this->set('isGlobal', $rfqData['rfq_visibility_type']);
        $this->set('selProdPrice', $selProdPrice);
        $this->set('frm', $frm);
        $this->set('rfqId', $rfqId);
        $this->set('rfq_quantity_unit', $qtyUnit);
        $this->set('counterOfferId', $counterOfferId);
        $this->set('recordId', $recordId);
        $this->set('includeTabs', false);
        $this->set('formTitle', Labels::getLabel('LBL_RFQ_OFFER_FORM', $this->siteLangId));
        $this->set('callback', 'closeForm');
        $this->set('html', $this->_template->render(false, false, NULL, true));
        $this->_template->render(false, false, 'json-success.php', true, false);
    }

    public function setup()
    {
        $this->checkEditPrivilege();

        $counterOfferId = FatApp::getPostedData('offer_counter_offer_id', FatUtility::VAR_INT, 0);
        $frm = $this->getForm();

        if (0 < $counterOfferId) {
            $fld = $frm->getField('offer_user_id');
            if (null != $fld) {
                $frm->removeField($fld);
            }
        }

        $post = $frm->getFormDataFromArray(FatApp::getPostedData());
        if (false === $post) {
            LibHelper::exitWithError(current($frm->getValidationErrors()), true);
        }

        if (empty($post['offer_rfq_id'])) {
            LibHelper::exitWithError($this->str_invalid_request, true);
        }

        $rfqStatus = RequestForQuote::getAttributesById($post['offer_rfq_id'], 'rfq_status');
        if (false === $rfqStatus || !in_array($rfqStatus, [RequestForQuote::STATUS_OPEN, RequestForQuote::STATUS_OFFERED])) {
            $statusArr = RequestForQuote::getStatusArr($this->siteLangId);
            $msg = CommonHelper::replaceStringData(Labels::getLabel('ERR_ACTION_RESTRICTED!_THIS_OFFER_HAS_BEEN_{STATUS}.'), [
                '{STATUS}' => $statusArr[$rfqStatus]
            ]);
            LibHelper::exitWithError($msg, true);
        }

        $recordId = FatApp::getPostedData('offer_id', FatUtility::VAR_INT, 0);
        $shippingcharges = FatApp::getPostedData('rlo_shipping_charges', FatUtility::VAR_FLOAT, 0);

        $primaryOfferId = 0;
        if (1 > $counterOfferId) {
            $offerUserId = FatApp::getPostedData('offer_user_id', FatUtility::VAR_INT, 0);

            $selectedSeller = FatApp::getPostedData('offer_user_id', FatUtility::VAR_INT, 0);
            $qty = FatApp::getPostedData('offer_quantity', FatUtility::VAR_INT, 0);

            if (1 > $recordId && false == RfqOffers::validateOfferRequest($post['offer_rfq_id'], $qty, $selectedSeller)) {
                LibHelper::exitWithError(Labels::getLabel('ERR_DUPLICATE_OFFER._YOU_CANNOT_PLACE_OFFER_WITH_THIS_QTY.'));
            }

            if (1 > $recordId && false == RfqOffers::hasValidSubscription($selectedSeller, $this->siteLangId)) {
                LibHelper::exitWithError(Labels::getLabel('ERR_SUBSCRIPTION_PLAN_RFQ_OFFERS_LIMIT_REACHED_FOR_THIS_SELECTED_SELLER.', $this->siteLangId), true);
            }

            $post['offer_status'] = RfqOffers::STATUS_OPEN;
        } else {
            $primaryOfferId = (int)RfqOffers::getAttributesById($counterOfferId, 'offer_primary_offer_id');
            $offerUserId = (int)RfqOffers::getAttributesById($primaryOfferId, 'offer_user_id');
            $post['offer_status'] = RfqOffers::STATUS_COUNTERED;
        }

        $post['offer_user_type'] = User::USER_TYPE_SELLER;
        $post['offer_user_id'] = $offerUserId;

        $rfqOfferData = RfqOffers::getAttributesById($recordId, ['offer_status', 'offer_price', 'offer_quantity', 'offer_counter_offer_id']);
        $ifRejected = !empty($rfqOfferData) ? (RfqOffers::STATUS_REJECTED == $rfqOfferData['offer_status']) : false;
        if ($ifRejected) {
            $pOfferId = $primaryOfferId;
            if (1 > $primaryOfferId) {
                $pOfferId = (int)RfqOffers::getAttributesById($recordId, 'offer_primary_offer_id');
            }
            $oldShippingCharges = RfqOffers::getShippingCharges($pOfferId);

            $post['offer_status'] = $rfqOfferData['offer_status'];
            if ($post['offer_quantity'] != $rfqOfferData['offer_quantity'] || $post['offer_price'] != $rfqOfferData['offer_price'] || $oldShippingCharges != $shippingcharges) {
                $post['offer_status'] = RfqOffers::STATUS_OPEN;
            }
        }

        $db = FatApp::getDb();
        $db->startTransaction();

        unset($post['rlo_shipping_charges']);

        $rfq = new RfqOffers($recordId);
        if (false == $rfq->add($post)) {
            $db->rollbackTransaction();
            LibHelper::exitWithError($rfq->getError(), true);
        }

        if (1 > $counterOfferId) {
            $primaryOfferId = $rfq->getMainTableRecordId();
            $rloStatus = RfqOffers::STATUS_OPEN;
        } else {
            $cntrRfq = new RfqOffers($counterOfferId);
            if (false == $cntrRfq->add([
                'offer_status' => RfqOffers::STATUS_COUNTERED
            ])) {
                $db->rollbackTransaction();
                LibHelper::exitWithError($cntrRfq->getError(), true);
            }
            /* updating RFQ status */
            $updateArray = array('rfq_status' => RequestForQuote::STATUS_OFFERED);
            $whr = array('smt' => 'rfq_id = ?', 'vals' => array($post['offer_rfq_id']));

            if (!FatApp::getDb()->updateFromArray(RequestForQuote::DB_TBL, $updateArray, $whr)) {
                LibHelper::exitWithError(Labels::getLabel('ERR_INVALID_REQUEST'));
            }
            $rloStatus = RfqOffers::STATUS_COUNTERED;
        }

        /* Update Offers buyer/seller latest record Id corresponding to primary Id*/
        $data = [
            'rlo_primary_offer_id' => $primaryOfferId,
            'rlo_rfq_id' => $post['offer_rfq_id'],
            'rlo_seller_offer_id' => $rfq->getMainTableRecordId(),
            'rlo_shipping_charges' => $shippingcharges
        ];

        if (1 > $recordId || $ifRejected) {
            $data['rlo_status'] = ($ifRejected ? $post['offer_status'] : $rloStatus);
        }

        if (1 > $counterOfferId) {
            $data['rlo_seller_user_id'] = $selectedSeller;

            $selprodId = RequestForQuote::getSellerProductId($post['offer_rfq_id'], $selectedSeller);
            $data['rlo_selprod_id'] = $selprodId;

            if (1 > $recordId) {
                $data['rlo_added_on'] = date('Y-m-d H:i:s');
            }
        }

        if (false == $rfq->updateLatestOffer($data)) {
            $db->rollbackTransaction();
            LibHelper::exitWithError($rfq->getError(), true);
        }

        /* Update Offers primary record Id*/
        if (false == $rfq->add([
            'offer_primary_offer_id' => $primaryOfferId,
        ])) {
            $db->rollbackTransaction();
            LibHelper::exitWithError($rfq->getError(), true);
        }

        if (1 > $counterOfferId) {
            $rfqToSeller = [
                'rfqts_rfq_id' => $post['offer_rfq_id'],
                'rfqts_user_id' => $post['offer_user_id']
            ];
            if (!FatApp::getDb()->insertFromArray(RequestForQuote::DB_RFQ_TO_SELLERS, $rfqToSeller, true, array(), $rfqToSeller)) {
                $db->rollbackTransaction();
                LibHelper::exitWithError(FatApp::getDb()->getError(), true);
            }
        }

        /* For New/Counter. Not on edit. */
        // if (1 > $recordId) 
        {
            $sellerId = (int)RfqOffers::getAttributesById($primaryOfferId, 'offer_user_id');
            $flds = LibHelper::addPrefixInArrValues(RfqOffers::FIELDS, 'ro.');
            $counterOfferFlds = [];
            foreach (RfqOffers::FIELDS as $fld) {
                array_push($counterOfferFlds, 'roc.' . $fld . ' as counter_' . $fld);
            }

            $dbFlds = array_merge($flds, $counterOfferFlds, ['rfq_title', 'rfq_number', 'rfq_added_on', 'rfq_approved', 'rfq_user_id', 'rfq_quantity', 'rfq_quantity_unit', 'rfq_visibility_type', 'bu.user_id as buyer_user_id', 'bu.user_phone as buyer_phone', 'bu.user_phone_dcode as buyer_phone_dcode', 'bu.user_name as buyer_user_name', 'bu.user_id as buyer_user_id', 'buc.credential_email as buyer_credential_email', 'COALESCE(ous_l.shop_name, ous.shop_identifier) as shop_name', 'rlo_primary_offer_id','rlo_shipping_charges', 'selprod_id', 'selprod_product_id', 'selprod_updated_on']);

            $srch = new RequestForQuoteSearch();
            $srch->doNotCalculateRecords();
            $srch->joinOffers(true);
            $srch->joinOfferPostedByUser();
            $srch->joinBuyer();
            $srch->joinOfferPostedBySellerShop(true);
            $srch->joinTable(RequestForQuote::DB_RFQ_TO_SELLERS, 'LEFT JOIN', 'rs.rfqts_rfq_id = rfq_id AND rs.rfqts_user_id = ' . $sellerId, 'rs');
            $srch->joinTable(SellerProduct::DB_TBL, 'LEFT JOIN', 'sp.selprod_id = rs.rfqts_selprod_id', 'sp');
            $srch->addCondition('ro.offer_rfq_id', '=', $post['offer_rfq_id']);
            $srch->addCondition('rfq_deleted', '=', applicationConstants::NO);
            $srch->addMultipleFields($dbFlds);
            $srch->addOrder('offer_added_on', applicationConstants::SORT_DESC);
            $srch->setPageSize(1);
            $offerData = FatApp::getDb()->fetch($srch->getDataResultSet());
            if (is_array($offerData) && !empty($offerData)) {
                $emailHandler = new EmailHandler();
                if (1 > $counterOfferId) {
                    if (false === $emailHandler->sendNewRfqOfferNotification($this->siteLangId, $offerData)) {
                        $msg = $emailHandler->getError();
                        $msg = empty($msg) ? Labels::getLabel('ERR_UNABLE_TO_NOTIFY._NOTIFICATION_LOGGED_TO_THE_SYSTEM.') : $msg;
                        LibHelper::exitWithError($msg, true);
                    }
                } else {
                    $offerData['isSeller'] = true;
                    if (false === $emailHandler->sendCounterRfqOfferNotification($this->siteLangId, $offerData)) {
                        $msg = $emailHandler->getError();
                        $msg = empty($msg) ? Labels::getLabel('ERR_UNABLE_TO_NOTIFY._NOTIFICATION_LOGGED_TO_THE_SYSTEM.') : $msg;
                        LibHelper::exitWithError($msg, true);
                    }
                }
            }
        }

        $db->commitTransaction();
        $this->set('record_id', $rfq->getMainTableRecordId());
        $this->set('msg', Labels::getLabel('MGS_UPDATED_SUCCESSFULLY.', $this->siteLangId));
        $this->_template->render(false, false, 'json-success.php');
    }

    public function validateRequest(int $recordId, int $rfqId, int $status)
    {
        $this->offerData = RfqOffers::getAttributesById($recordId, ['offer_user_type', 'offer_rfq_id', 'offer_primary_offer_id']);
        if (!is_array($this->offerData) || empty($this->offerData) || $rfqId != $this->offerData['offer_rfq_id']) {
            LibHelper::exitWithError($this->str_invalid_request, true);
        }

        $isClosed = RequestForQuote::getAttributesById($rfqId, 'rfq_status');
        if (RequestForQuote::STATUS_CLOSED == $isClosed) {
            LibHelper::exitWithError(Labels::getLabel('ERR_THIS_RFQ_IS_CLOSED_BY_THE_BUYER'), true);
        }

        $srch = new SearchBase(RfqOffers::DB_RFQ_LATEST_OFFER);
        $srch->doNotCalculateRecords();
        $srch->setPageSize(1);
        $srch->addCondition('rlo_primary_offer_id', '=', $this->offerData['offer_primary_offer_id']);
        $srch->addCondition('rlo_status', '=', $status);
        $srch->addFld('rlo_status');
        $result = FatApp::getDb()->fetch($srch->getResultSet());
        $isPaid = is_array($result) && !empty($result);
        if ($isPaid) {
            $statusArr = RfqOffers::getStatusArr($this->siteLangId);
            $msg = CommonHelper::replaceStringData(Labels::getLabel('LBL_THIS_OFFER_HAS_BEEN_ALREADY_{STATUS}'), [
                '{STATUS}' => $statusArr[$status]
            ]);
            LibHelper::exitWithError($msg, true);
        }

        $sellerId = RfqOffers::getSellerIdByOfferId($recordId);
        if (1 > $sellerId) {
            LibHelper::exitWithError(Labels::getLabel('ERR_NO_SELLER_BOUND_WITH_THIS_OFFER'), true);
        }

        $selProdId = RequestForQuote::getSellerProductId($rfqId, $sellerId);
        if (1 > $selProdId && RfqOffers::STATUS_ACCEPTED == $status) {
            LibHelper::exitWithError(Labels::getLabel('ERR_INVENTORY_NOT_LINKED_WITH_THIS_OFFER'), true);
        }
        return $selProdId;
    }

    public function accept(int $recordId, int $rfqId)
    {
        $selProdId = $this->validateRequest($recordId, $rfqId, RfqOffers::STATUS_ACCEPTED);
        $db = FatApp::getDb();
        $db->startTransaction();

        $rfq = new RfqOffers($recordId);
        if (false == $rfq->add([
            'offer_status' => RfqOffers::STATUS_ACCEPTED
        ])) {
            $db->rollbackTransaction();
            LibHelper::exitWithError($rfq->getError(), true);
        }

        $updateArray = array('rfq_status' => RequestForQuote::STATUS_ACCEPTED);
        $whr = array('smt' => 'rfq_id = ?', 'vals' => array($rfqId));

        if (!$db->updateFromArray(RequestForQuote::DB_TBL, $updateArray, $whr)) {
            $db->rollbackTransaction();
            LibHelper::exitWithError($db->getError(), true);
        }

        if (!empty($this->offerData['offer_primary_offer_id'])) {
            $primaryOfferId = $this->offerData['offer_primary_offer_id'];
        } else {
            $primaryOfferId = (int)RfqOffers::getAttributesById($recordId, 'offer_primary_offer_id');
        }

        $rfq = new RfqOffers($recordId);
        $data = [
            'rlo_primary_offer_id' => $primaryOfferId,
            'rlo_selprod_id' => $selProdId,
            'rlo_accepted_offer_id' => $recordId,
            'rlo_status' => RfqOffers::STATUS_ACCEPTED,
            'rlo_seller_acceptance' => applicationConstants::YES,
            'rlo_buyer_acceptance' => applicationConstants::YES,
        ];

        if (false == $rfq->updateLatestOffer($data)) {
            $db->rollbackTransaction();
            LibHelper::exitWithError($rfq->getError(), true);
        }

        $this->sendOfferActionNotification($recordId, $rfqId, RfqOffers::getSellerIdByOfferId($recordId), RfqOffers::SELLER_ACCEPTANCE);

        $db->commitTransaction();
        FatUtility::dieJsonSuccess(Labels::getLabel('LBL_SUCCESS'));
    }

    public function reject(int $recordId, int $rfqId)
    {
        $this->validateRequest($recordId, $rfqId, RfqOffers::STATUS_REJECTED);

        $rfq = new RfqOffers($recordId);
        if (false == $rfq->add([
            'offer_status' => RfqOffers::STATUS_REJECTED
        ])) {
            LibHelper::exitWithError($rfq->getError(), true);
        }

        $primaryOfferId = (int)RfqOffers::getAttributesById($recordId, 'offer_primary_offer_id');
        $rfq = new RfqOffers($recordId);
        $data = [
            'rlo_primary_offer_id' => $primaryOfferId,
            'rlo_status' => RfqOffers::STATUS_REJECTED
        ];

        if (false == $rfq->updateLatestOffer($data)) {
            LibHelper::exitWithError($rfq->getError(), true);
        }

        $this->sendOfferActionNotification($recordId, $rfqId, RfqOffers::getSellerIdByOfferId($recordId));

        FatUtility::dieJsonSuccess(Labels::getLabel('LBL_SUCCESS'));
    }

    private function sendOfferActionNotification(int $recordId, int $rfqId, int $sellerId, int $acceptance = 0)
    {
        $srch = new RequestForQuoteSearch();
        $srch->joinOffers();
        $srch->joinBuyer();
        $srch->joinSellers('INNER', $sellerId);
        $srch->joinSellerUser();
        $srch->joinSellerProduct(true);
        $srch->joinSellerShop(true);
        $srch->addCondition('rfq_id', '=', $rfqId);
        $srch->addCondition('offer_id', '=', $recordId);
        $srch->addMultipleFields([
            'rfq_title',
            'rfq_number',
            'rfq_added_on',
            'rfq_quantity_unit',
            'rfq_visibility_type',
            'offer_rfq_id',
            'offer_quantity',
            'offer_price',
            'offer_status',
            'offer_comments',
            'offer_added_on',
            'bu.user_name',
            'bu.user_phone_dcode',
            'bu.user_phone',
            'bu.user_id',
            'buc.credential_email',
            'suc.credential_email as seller_email',
            'su.user_id as seller_user_id',
            'selprod_id',
            'selprod_product_id',
            'selprod_updated_on',
            'COALESCE(shop_name, shop_identifier) as shop_name',
        ]);
        $offerData = FatApp::getDb()->fetch($srch->getDataResultSet($this->siteLangId));
        $offerData['isSeller'] = true;
        $offerData['sellers'] = RequestForQuote::getSellersByRecordId($rfqId, true, true, $this->siteLangId);
        $emailHandler = new EmailHandler();
        if (false === $emailHandler->sendRfqOfferActionNotification($this->siteLangId, $offerData, $acceptance)) {
            $msg = $emailHandler->getError();
            $msg = empty($msg) ? Labels::getLabel('ERR_UNABLE_TO_NOTIFY._NOTIFICATION_LOGGED_TO_THE_SYSTEM.') : $msg;
            LibHelper::exitWithError($msg, true);
        }
    }

    public function deleteRecord()
    {
        $this->checkEditPrivilege();

        $rfqId = FatApp::getPostedData('recordId', FatUtility::VAR_INT, 0);
        $offerId = FatApp::getPostedData('subRecordId', FatUtility::VAR_INT, 0);
        if ($rfqId < 1 || $offerId < 1) {
            LibHelper::exitWithError($this->str_invalid_request_id, true);
        }

        if (!RfqOffers::canModify($rfqId, $offerId)) {
            LibHelper::exitWithError(Labels::getLabel('ERR_ACTION_RESTRICTED!_THIS_OFFER_HAS_BEEN_ACCEPTED/REJECTED.'), true);
        }
        $this->markAsDeleted($offerId);

        $updateArray = array('rlo_deleted' => applicationConstants::YES);
        $whr = array('smt' => 'rlo_seller_offer_id = ?', 'vals' => [$offerId]);

        $db = FatApp::getDb();
        if (!$db->updateFromArray(RfqOffers::DB_RFQ_LATEST_OFFER, $updateArray, $whr, true)) {
            LibHelper::exitWithError($db->getError());
        }
        FatUtility::dieJsonSuccess($this->str_delete_record);
    }

    public function deleteSelected()
    {
        $this->checkEditPrivilege();

        $recordIdsArr = FatUtility::int(FatApp::getPostedData('offer_ids'));
        if (empty($recordIdsArr)) {
            LibHelper::exitWithError($this->str_invalid_request, true);
        }

        foreach ($recordIdsArr as $recordId) {
            if (1 > $recordId) {
                continue;
            }
            $this->markAsDeleted($recordId);
            $rfq = new RfqOffers($recordId);
            if (false == $rfq->updateLatestOffer(['rlo_deleted' => applicationConstants::YES])) {
                LibHelper::exitWithError($rfq->getError(), true);
            }
        }
        $this->set('msg', Labels::getLabel('MSG_RECORDS_DELETED_SUCCESSFULLY', $this->siteLangId));
        $this->_template->render(false, false, 'json-success.php');
    }

    protected function markAsDeleted(int $recordId)
    {
        $recordId = FatUtility::int($recordId);
        if (1 > $recordId) {
            LibHelper::exitWithError($this->str_invalid_request_id, true);
        }

        $rfqOffer = new RfqOffers($recordId);
        $rfqOffer->setFldValue('offer_quantity', 'mysql_func_null', true);
        $rfqOffer->assignValues(
            [
                $rfqOffer::tblFld('deleted') => 1
            ],
            false,
            '',
            '',
            true
        );
        if (!$rfqOffer->save()) {
            LibHelper::exitWithError($rfqOffer->getError(), true);
        }
    }

    public function getShippingRates()
    {
        $rfqId = FatApp::getPostedData('rfq_id', FatUtility::VAR_INT, 0);
        $sellerId = FatApp::getPostedData('seller_id', FatUtility::VAR_INT, 0);
        $primaryOfferId = FatApp::getPostedData('rlo_primary_offer_id', FatUtility::VAR_INT, 0);
        if (1 > $rfqId || 1 > $sellerId || 1 > $primaryOfferId) {
            LibHelper::exitWithError(Labels::getLabel('ERR_INVALID_REQUEST_ID.', $this->siteLangId), true);
        }

        $options = [];

        $shippingCharges = RfqOffers::getShippingCharges($primaryOfferId);
        if (0 < $shippingCharges) {
            $options[] = [
                'title' => Labels::getLabel('FRM_CUSTOM_SHIPPING_CHARGES', $this->siteLangId),
                'price' => CommonHelper::displayMoneyFormat($shippingCharges)
            ];
        } else {
            $rfq = new RequestForQuote($rfqId);
            $selprodShippingRates = $rfq->getShippingCharges($sellerId, $primaryOfferId);
            $selprodId = !empty($selprodShippingRates) ? array_key_first($selprodShippingRates) : 0;
            $shippingRates =  !empty($selprodShippingRates) ? current($selprodShippingRates) : [];
            ksort($shippingRates);
            foreach ($shippingRates as $shippedBy => $shippedByItemArr) {
                ksort($shippedByItemArr);
                foreach ($shippedByItemArr as $shipLevel => $items) {
                    $rates = $shippedByItemArr[$shipLevel]['rates'] ?? [];
                    switch ($shipLevel) {
                        case Shipping::LEVEL_PRODUCT:
                            $rates = $rates[$selprodId];
                            break;
                    }
                    foreach ($rates as $key => $shippingcharge) {
                        $options[$key] = [
                            'title' => $shippingcharge['title'],
                            'price' => CommonHelper::displayMoneyFormat($shippingcharge['cost'])
                        ];
                    }
                }
            }
        }

        $this->set('options', $options);
        $this->set('html', $this->_template->render(false, false, NULL, true));
        $this->_template->render(false, false, 'json-success.php', false, false);
    }

    public function viewComments(int $offerId)
    {
        $comments = RfqOffers::getAttributesById($offerId, 'offer_comments');
        if (false == $comments) {
            LibHelper::exitWithError(Labels::getLabel('ERR_INVALID_REQUEST_ID.', $this->siteLangId), true);
        }

        if (empty($comments)) {
            LibHelper::exitWithError(Labels::getLabel('ERR_NO_COMMENTS.', $this->siteLangId), true);
        }
        $this->set('comments', $comments);
        $this->set('offerId', $offerId);
        $this->set('html', $this->_template->render(false, false, 'rfq-offers/view-comments.php', true));
        $this->_template->render(false, false, 'json-success.php', false, false);
    }

    public function attachmentForm()
    {
        $primaryOfferId = FatApp::getPostedData('rom_primary_offer_id', FatUtility::VAR_INT, 0);
        $onlyWithAttachments = FatApp::getPostedData('only_with_attachments', FatUtility::VAR_INT, 0);
        if (1 > $primaryOfferId) {
            LibHelper::exitWithError(Labels::getLabel('ERR_INVALID_REQUEST.', $this->siteLangId), true);
        }
        $frm = RfqOffers::getAttachmentForm();
        $frm->fill(['rom_primary_offer_id' => $primaryOfferId]);

        $data = RfqOffers::getMessages($primaryOfferId, onlyWithAttachments: (0 < $onlyWithAttachments));
        if (!empty($data['data'])) {
            $msgIds = [];
            foreach ($data['data'] as $rom) {
                $msgIds[] = $rom['rom_id'];
            }
            RfqOffers::markMessagesAsRead($primaryOfferId, $msgIds);
        }
        $this->set('pageCount', ($data['pageCount'] ?? 0));
        $this->set('data', array_reverse($data['data']));

        $this->set('includeTabs', false);
        $this->set('frm', $frm);
        $this->set('primaryOfferId', $primaryOfferId);
        $this->set('onlyWithAttachments', $onlyWithAttachments);
        $this->set('html', $this->_template->render(false, false, return_content: true));
        $this->_template->render(false, false, 'json-success.php', false, false);
    }

    public function loadMoreAttachments()
    {
        $primaryOfferId = FatApp::getPostedData('rom_primary_offer_id', FatUtility::VAR_INT, 0);
        $page = FatApp::getPostedData('page', FatUtility::VAR_INT, 1);
        $data = RfqOffers::getMessages($primaryOfferId, $page);
        if (!empty($data['data'])) {
            $msgIds = [];
            foreach ($data['data'] as $rom) {
                $msgIds[] = $rom['rom_id'];
            }
            RfqOffers::markMessagesAsRead($primaryOfferId, $msgIds);
        }
        $this->set('primaryOfferId', $primaryOfferId);
        $this->set('page', $page);
        $this->set('pageCount', ($data['pageCount'] ?? 0));
        $this->set('data', $data['data']);
        $this->set('html', $this->_template->render(false, false, 'rfq-offers/attachment-rows.php', true));
        $this->_template->render(false, false, 'json-success.php', false, false);
    }

    private function sendMessage()
    {
        $frm = RfqOffers::getAttachmentForm();
        $post = $frm->getFormDataFromArray(FatApp::getPostedData());
        if (false == $post) {
            LibHelper::exitWithError(current($frm->getValidationErrors()), true);
        }
        unset($post['attachment_file']);
        $post['rom_user_type'] = User::USER_TYPE_SELLER;
        $post['rom_buyer_access'] = FatApp::getPostedData('rom_buyer_access', FatUtility::VAR_INT, 0);
        $rfqOffer = new RfqOffers();
        if (false == $rfqOffer->addMessage($post)) {
            LibHelper::exitWithError($rfqOffer->getError(), true);
        }
        return $rfqOffer->getMessageId();
    }

    public function uploadAttachment()
    {
        $this->checkEditPrivilege();
        $primaryOfferId = FatApp::getPostedData('rom_primary_offer_id', FatUtility::VAR_INT, 0);
        if (1 > $primaryOfferId) {
            LibHelper::exitWithError($this->str_invalid_request_id, true);
        }

        $frm = RfqOffers::getAttachmentForm();
        $post = $frm->getFormDataFromArray(FatApp::getPostedData());
        if (false === $post) {
            LibHelper::exitWithError(current($frm->getValidationErrors()), true);
        }

        $messageId = $this->sendMessage();

        if (
            isset($_FILES['attachment_file']['tmp_name'])
            && is_uploaded_file($_FILES['attachment_file']['tmp_name'])
        ) {
            $fileType = AttachedFile::FILETYPE_RFQ_OFFER_FILE;
            $fileHandlerObj = new AttachedFile();
            if (false == $fileHandlerObj->saveAttachment(
                $_FILES['attachment_file']['tmp_name'],
                $fileType,
                $messageId,
                $primaryOfferId,
                $_FILES['attachment_file']['name'],
                -1,
                false
            )) {
                LibHelper::exitWithError($fileHandlerObj->getError(), true);
            }
        }

        $messageData = RfqOffers::getMessageRow($messageId, $this->siteLangId);
        $this->set('romDate', date('Ymd', strtotime($messageData['rom_added_on'])));
        $this->set('row', $messageData);
        $this->set('html', $this->_template->render(false, false, 'rfq-offers/attachment-record.php', true));

        $this->_template->render(false, false, 'json-success.php', false, false);
    }

    public function downloadAttachmentFile(int $romId, int $primaryOfferId)
    {
        $res = AttachedFile::getAttachment(AttachedFile::FILETYPE_RFQ_OFFER_FILE, $romId, $primaryOfferId);
        if ($res == false || 1 > $res['afile_id']) {
            LibHelper::exitWithError(Labels::getLabel('ERR_NOT_AVAILABLE_TO_DOWNLOAD', $this->siteLangId), false, true);
            FatApp::redirectUser(UrlHelper::generateUrl('RequestForQuotes'));
        }

        if (!file_exists(CONF_UPLOADS_PATH . $res['afile_physical_path'])) {
            LibHelper::exitWithError(Labels::getLabel('ERR_FILE_NOT_FOUND', $this->siteLangId), false, true);
            FatApp::redirectUser(UrlHelper::generateUrl('RequestForQuotes'));
        }

        AttachedFile::downloadAttachment($res['afile_physical_path'], $res['afile_name']);
    }

    /**
     * getFormColumns
     *
     * @return array
     */
    protected function getFormColumns(): array
    {
        $tblHeadingCols = CacheHelper::get('rfqOffersTblHeadingCols' . $this->siteLangId, CONF_DEF_CACHE_TIME, '.txt');
        if ($tblHeadingCols) {
            return json_decode($tblHeadingCols, true);
        }

        $arr = [
            /*  'select_all' => Labels::getLabel('LBL_SELECT_ALL', $this->siteLangId), */
            'listSerial' => Labels::getLabel('LBL_SR._NO', $this->siteLangId),
            'offer_type' => Labels::getLabel('LBL_TYPE', $this->siteLangId),
            'user_name' => Labels::getLabel('LBL_USER', $this->siteLangId),
            'offer_quantity' => Labels::getLabel('LBL_QTY', $this->siteLangId),
            'offer_cost' => Labels::getLabel('LBL_COST_PRICE', $this->siteLangId),
            'offer_price' => Labels::getLabel('LBL_OFFERRED_PRICE', $this->siteLangId),
            'offer_status' => Labels::getLabel('LBL_STATUS', $this->siteLangId),
            'offer_added_on' => Labels::getLabel('LBL_ADDED_ON', $this->siteLangId),
            'action' => Labels::getLabel('LBL_ACTION_BUTTONS', $this->siteLangId),
        ];
        CacheHelper::create('rfqOffersTblHeadingCols' . $this->siteLangId, json_encode($arr), CacheHelper::TYPE_LABELS);
        return $arr;
    }

    public function getSellers()
    {
        $isGlobal = FatApp::getPostedData('isGlobal', FatUtility::VAR_INT, 0);
        if (RequestForQuote::VISIBILITY_TYPE_OPEN == $isGlobal) {
            $json = Shop::getSellersAutocomplete($this->siteLangId);
        } else {
            $json = RfqOffers::getSellers($this->siteLangId);
        }
        die(FatUtility::convertToJson($json));
    }


    /**
     * getDefaultColumns
     *
     * @return array
     */
    protected function getDefaultColumns(): array
    {
        return array_keys($this->getFormColumns());
    }

    /**
     * excludeKeysForSort
     *
     * @param  mixed $fields
     * @return array
     */
    protected function excludeKeysForSort($fields = []): array
    {
        return array_diff($fields, ['user_name', 'offer_quantity', 'offer_cost', 'offer_price', 'offer_status', 'offer_added_on'], Common::excludeKeysForSort());
    }

    public function getBreadcrumbNodes($action)
    {
        $pageData = PageLanguageData::getAttributesByKey($this->pageKey, $this->siteLangId);
        switch ($action) {
            case 'listing':
                $this->nodes = [
                    [
                        'title' => Labels::getLabel("LBL_REQUEST_FOR_QUOTES"),
                        'href' => UrlHelper::generateUrl('RequestForQuotes')
                    ],
                    ['title' => $pageData['plang_title'] ?? Labels::getLabel('LBL_OFFERS', $this->siteLangId)]
                ];
        }
        return $this->nodes;
    }

    public function getSelProdPrice($rfqId, $userId)
    {
        $rfq = new RequestForQuote($rfqId);
        $attr = [
            'rfq_product_type',
            'rfq_title',
            'rfqts_user_id as seller_id',
            'rfqts_selprod_id'
        ];
        $rfqDataArr = $rfq->get($this->siteLangId, $attr, 'LEFT', $userId);
        $selProdPrice = 0;
        if (!empty($rfqDataArr) && !empty($rfqDataArr['rfqts_selprod_id'])) {
            $selProdPrice = SellerProduct::getAttributesById($rfqDataArr['rfqts_selprod_id'], 'selprod_price');
        }
        $selProdPrice = 0 < $selProdPrice ? CommonHelper::displayMoneyFormat($selProdPrice) : $selProdPrice;
        $jsonData = [
            'selProdPrice' => $selProdPrice,
        ];
        LibHelper::exitWithSuccess($jsonData, true);
    }
}
