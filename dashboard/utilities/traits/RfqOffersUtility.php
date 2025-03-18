<?php
trait RfqOffersUtility
{
    public $isSeller = false;
    public $isBuyer = false;
    public $isGlobal = false;

    public function index(int $rfqId = 0)
    {
        if (1 > $rfqId) {
            CommonHelper::redirectUserReferer();
        }
        FatApp::redirectUser(UrlHelper::generateUrl(($this->isSeller ? 'Seller' : '') . 'RfqOffers', 'listing', [$rfqId]));
    }

    public function globalListing(int $rfqId)
    {
        $this->isGlobal = true;
        $this->listing($rfqId);
    }

    public function listing(int $rfqId)
    {
        if (1 > $rfqId) {
            LibHelper::exitWithError(Labels::getLabel('ERR_INVALID_REQUEST_ID', $this->siteLangId), false, true);
            CommonHelper::redirectUserReferer();
        }

        if ($this->isSeller) {
            if (!$this->isShopActive($this->userParentId)) {
                LibHelper::exitWithError(Labels::getLabel("MSG_YOUR_SHOP_IS_NOT_ACTIVE_YET", $this->siteLangId), false, true);
                CommonHelper::redirectUserReferer();
            }

            if (!UserPrivilege::isUserHasValidSubsription($this->userParentId)) {
                LibHelper::exitWithError(Labels::getLabel("MSG_PLEASE_BUY_SUBSCRIPTION", $this->siteLangId), false, true);
                CommonHelper::redirectUserReferer();
            }

            if (!$this->userPrivilege->canEditRfqOffers($this->userId, true)) {
                LibHelper::exitWithError(Labels::getLabel('ERR_UNAUTHORIZED_ACCESS'), false, true);
                CommonHelper::redirectUserReferer();
            }
        }

        $rfqInfo = RequestForQuote::getAttributesById($rfqId, ['rfq_approved', 'rfq_selprod_id', 'rfq_product_id', 'rfq_visibility_type', 'rfq_status']);

        if (false == $rfqInfo || $rfqInfo['rfq_approved'] != RequestForQuote::APPROVED) {
            LibHelper::exitWithError(Labels::getLabel('ERR_RFQ_IS_NOT_APPROVED_YET', $this->siteLangId), false, true);
            CommonHelper::redirectUserReferer();
        }
        /* 
        if (RequestForQuote::STATUS_CLOSED == $rfqInfo['rfq_status'] && false == RequestForQuote::hasAcceptedOffers($rfqId)) {
            LibHelper::exitWithError(Labels::getLabel('ERR_THIS_RFQ_HAS_BEEN_CLOSED_BY_THE_BUYER', $this->siteLangId), false, true);
            CommonHelper::redirectUserReferer();
        } */

        $srch = new RequestForQuoteSearch();
        if ($this->isSeller) {
            $srch->joinSellers();
            // $srch->addFld('rfqts_selprod_id, rfqts_user_id');
        }
        $srch->joinBuyer();
        $srch->joinBuyerAddress($this->siteLangId);
        $srch->joinCountry(true);
        $srch->joinState(true);

        $dbFlds = array_merge(RequestForQuote::FIELDS, ['addr_name', 'addr_address1', 'addr_address2', 'addr_city', 'state_name', 'country_name', 'addr_zip', 'addr_phone_dcode', 'addr_phone', 'buc.credential_username', 'buc.credential_email', 'bu.user_id as user_id', 'bu.user_updated_on', 'bu.user_name', 'IFNULL(country_name, country_code) as country_name', 'IFNULL(state_name, state_identifier) as state_name', 'rfq.rfq_product_id', 'rfq_selprod_code', 'rfq_added_on']);
        $srch->addMultipleFields($dbFlds);

        if ($this->isSeller) {
            if (RequestForQuote::VISIBILITY_TYPE_CLOSED == $rfqInfo['rfq_visibility_type']) {
                $srch->addCondition('rfqts_user_id', '=', $this->userParentId);
            }
        } else {
            $srch->addCondition('rfq_user_id', '=', $this->userId);
        }

        $srch->addCondition('rfq_id', '=', $rfqId);
        $rfqData = (array)FatApp::getDb()->fetch($srch->getDataResultSet());

        if (empty($rfqData)) {
            LibHelper::exitWithError(Labels::getLabel('ERR_INVALID_REQUEST_ID', $this->siteLangId), false, true);
            CommonHelper::redirectUserReferer();
        }

        if ($this->isSeller && RequestForQuote::VISIBILITY_TYPE_OPEN == $rfqInfo['rfq_visibility_type'] && false == RequestForQuote::isAssignedToSeller($rfqId, $this->userParentId)) {
            LibHelper::exitWithError(Labels::getLabel('ERR_PLEASE_ASSIGN_THIS_RFQ_TO_YOURSELF_FIRST', $this->siteLangId), false, true);
            CommonHelper::redirectUserReferer();
        }

        $this->set('rfqData', $rfqData);

        $frm = $this->getSearchForm();
        $frm->fill([
            'offer_rfq_id' => $rfqId,
            'rfq_product_id' => $rfqData['rfq_product_id']
        ]);
        $this->set('frmSearch', $frm);

        $otherButtons = [];
        $linkedSelprodId = RequestForQuote::getSellerProductId($rfqId, $this->userParentId);
        if ($this->isSeller) {
            $isOpenOffered = in_array($rfqData['rfq_status'], [RequestForQuote::STATUS_OPEN, RequestForQuote::STATUS_OFFERED]);
            if (RequestForQuote::isAssignedToSeller($rfqId, $this->userParentId) && $isOpenOffered) {
                $otherButtons[] = [
                    'attr' => [
                        'class' => 'btn-brand btn-icon',
                        'onclick' => 'addNew(' . $rfqId . ')',
                        'title' => Labels::getLabel('LBL_OFFER', $this->siteLangId)
                    ],
                    'icon' => "<svg class='svg btn-icon-start' width='18' height='18'>
                            <use xlink:href='" . CONF_WEBROOT_URL . "images/retina/sprite-actions.svg" . AttachedFile::setTimeParam(RELEASE_DATE) . "#add'>
                            </use>
                        </svg>",
                    'label' => Labels::getLabel('LBL_NEW_OFFER', $this->siteLangId)
                ];
            }


            $hasAnySellerAcceptance = RfqOffers::hasAnySellerAcceptance($rfqId, $this->userParentId);
            $globalCanChangeInventory = (RequestForQuote::VISIBILITY_TYPE_OPEN == $rfqInfo['rfq_visibility_type'] && $hasAnySellerAcceptance);
            $closedCanChangeInventory = (RequestForQuote::VISIBILITY_TYPE_CLOSED == $rfqInfo['rfq_visibility_type'] && empty($rfqData['rfq_selprod_id']));

            if ($globalCanChangeInventory || $closedCanChangeInventory || ($isOpenOffered && empty($linkedSelprodId)) || (empty($linkedSelprodId) && RequestForQuote::STATUS_ACCEPTED == $rfqData['rfq_status'] && RfqOffers::hasAnyBuyerAcceptedOffer($this->userParentId, $rfqId))) {
                $title = Labels::getLabel('LBL_INVENTORY_NOT_LINKED_WITH_THIS_RFQ!!', $this->siteLangId);
                $label = Labels::getLabel('BTN_LINK_INVENTORY', $this->siteLangId);
                if ($linkedSelprodId > 0) {
                    $title = Labels::getLabel('LBL_CHANGE_INVENTORY', $this->siteLangId);
                    $label = Labels::getLabel('BTN_CHANGE_INVENTORY', $this->siteLangId);
                }

                $otherButtons[] = [
                    'attr' => [
                        'onclick' => 'linkInventoryForm(' . $rfqId . ')',
                        'class' => 'btn-outline-gray btn-icon',
                        'title' => $title
                    ],
                    'icon' => "<svg class='svg btn-icon-start' width='18' height='18'>
                            <use xlink:href='" . CONF_WEBROOT_URL . "images/retina/sprite-actions.svg" . AttachedFile::setTimeParam(RELEASE_DATE) . "#inventories'>
                            </use>
                        </svg>",
                    'label' => $label
                ];
            }
        }
        $otherButtons[] = [
            'attr' => [
                'class' => 'btn-outline-gray btn-icon',
                'onclick' => 'viewRfq(' . $rfqId . ')',
                'title' => Labels::getLabel('LBL_VIEW_RFQ_INFORMATION', $this->siteLangId)
            ],
            'icon' => "<svg class='svg btn-icon-start' width='18' height='18'>
                            <use xlink:href='" . CONF_WEBROOT_URL . "images/retina/sprite-actions.svg" . AttachedFile::setTimeParam(RELEASE_DATE) . "#view'>
                            </use>
                        </svg>",
            'label' => Labels::getLabel('LBL_VIEW', $this->siteLangId)
        ];

        if ($this->isBuyer && RequestForQuote::STATUS_CLOSED != $rfqData['rfq_status']) {
            $otherButtons[] = [
                'attr' => [
                    'class' => 'btn-brand btn-icon',
                    'onclick' => 'closeRfq(' . $rfqId . ')',
                    'title' => Labels::getLabel('LBL_MARK_RFQ_AS_CLOSED!', $this->siteLangId)
                ],
                'icon' => "<svg class='svg btn-icon-start' width='18' height='18'>
                            <use xlink:href='" . CONF_WEBROOT_URL . "images/retina/sprite-actions.svg" . AttachedFile::setTimeParam(RELEASE_DATE) . "#close'>
                            </use>
                        </svg>",
                'label' => Labels::getLabel('LBL_CLOSE', $this->siteLangId)
            ];
        }

        $selprodTitle = '';
        if (empty($rfqData['rfq_selprod_id']) && !empty($linkedSelprodId)) {
            $selprodTitle = SellerProduct::getAttributesByLangId($this->siteLangId, $linkedSelprodId, 'selprod_title');
            $options = SellerProduct::getSellerProductOptions($linkedSelprodId, true, $this->siteLangId);
            array_walk($options, function ($item, $key) use (&$selprodTitle) {
                $selprodTitle .= ' | ' . $item['option_name'] . ' : ' . $item['optionvalue_name'];
            });
        }

        $this->set("selprodTitle", $selprodTitle);
        $this->set("isGlobal", $this->isGlobal);
        $this->set("otherButtons", $otherButtons);
        $this->set("isSeller", $this->isSeller);
        $this->set("sellerId", $this->userParentId);
        $this->set("rfqStatusArr", RequestForQuote::getStatusArr($this->siteLangId));
        $this->set("approvalStatusArr", RequestForQuote::getApprovalStatusArr($this->siteLangId));
        $this->set('keywordPlaceholder', Labels::getLabel('FRM_SEARCH_BY_COMMENT', $this->siteLangId));
        $this->_template->addJs(['rfq-offers/page-js/listing.js', 'js/select2.js']);
        $this->_template->addCss(array('css/select2.min.css'));
        $this->_template->render(true, true, 'rfq-offers/listing.php', false, false);
    }

    private function getSearchForm()
    {
        $frm = new Form('frmRecordSearch');
        $frm->addHiddenField('', 'page', 1);
        $frm->addHiddenField('', 'total_record_count');
        $frm->addHiddenField('', 'offer_rfq_id');
        $frm->addHiddenField('', 'rfq_product_id');
        $fld = $frm->addTextBox(Labels::getLabel('FRM_KEYWORD', $this->siteLangId), 'keyword', '');
        $fld->overrideFldType('search');

        if ($this->isBuyer && 1 > FatApp::getConfig('CONF_HIDE_SELLER_INFO', FatUtility::VAR_INT, 0)) {
            $frm->addSelectBox(Labels::getLabel('FRM_SELLER', $this->siteLangId), 'offer_user_id', []);
        }

        $statusArr = array(-1 => Labels::getLabel('FRM_SELECT_STATUS', $this->siteLangId)) + RfqOffers::getStatusArr($this->siteLangId);
        $frm->addSelectBox(Labels::getLabel('FRM_STATUS', $this->siteLangId), 'offer_status', $statusArr, '', array(), '');
        HtmlHelper::addSearchButton($frm);
        HtmlHelper::addClearButton($frm);/*clearBtn*/
        return $frm;
    }

    public function search()
    {
        $rfqId = FatApp::getPostedData('offer_rfq_id', FatUtility::VAR_INT, 0);

        $srchFrm = $this->getSearchForm();
        $postedData = FatApp::getPostedData();
        $post = $srchFrm->getFormDataFromArray($postedData);
        $post['offer_rfq_id'] = $rfqId;

        $page = FatApp::getPostedData('page', FatUtility::VAR_INT, 1);
        $page = ($page <= 0) ? 1 : $page;

        $pageSize = applicationConstants::getPageSize(FatApp::getPostedData('pageSize', FatUtility::VAR_INT));

        $srch = new RequestForQuoteSearch();
        $srch->joinOffers(true);

        if ($this->isSeller) {
            /*  $srch->joinSellers('INNER', $this->userId);
            $srch->addCondition('rfqts_user_id', '=', $this->userId); */
            $srch->addCondition('ro.offer_user_id', '=', $this->userParentId);
        }

        $srch->joinOfferPostedByUser();
        $srch->joinBuyer();
        $srch->joinOfferPostedBySellerShop(true);
        $srch->addCondition('ro.offer_rfq_id', '=', $rfqId);
        $srch->addCondition('rfq_deleted', '=', applicationConstants::NO);

        // $srch->addCondition('ro.offer_deleted', '=', applicationConstants::NO);
        $this->setRecordCount(clone $srch, $pageSize, $page, $post);

        $flds = LibHelper::addPrefixInArrValues(RfqOffers::FIELDS, 'ro.');
        $counterOfferFlds = [];
        foreach (RfqOffers::FIELDS as $fld) {
            array_push($counterOfferFlds, 'roc.' . $fld . ' as counter_' . $fld);
        }

        $dbFlds = array_merge($flds, $counterOfferFlds, ['rfq_user_id', 'rfq_selprod_id', 'rfq_product_type', 'rfq_product_id', 'rfq_visibility_type', 'rlo_seller_user_id', 'ou.user_name', 'ouc.credential_email', 'ou.user_updated_on', 'ou.user_id', 'bu.user_name as buyer_user_name', 'bu.user_id as buyer_user_id', 'buc.credential_email as buyer_credential_email', 'COALESCE(ous_l.shop_name, ous.shop_identifier) as shop_name', 'ous.shop_id', 'rlo_primary_offer_id', 'rfq_status', 'rfq_quantity_unit', 'rfq_added_on', 'rlo_shipping_charges', 'rlo_accepted_offer_id', 'rlo_selprod_id', 'rlo_seller_offer_id', 'rlo_buyer_offer_id', 'rlo_seller_acceptance', 'rlo_buyer_acceptance']);
        $srch->addMultipleFields($dbFlds);

        $sellerId = FatApp::getPostedData('offer_user_id', FatUtility::VAR_INT, 0);
        if (0 < $sellerId) {
            $srch->addCondition('rlo_seller_user_id', '=', $sellerId);
        }

        $status = FatApp::getPostedData('offer_status', FatUtility::VAR_STRING, -1);
        if (-1 < $status) {
            /* $cnd = $srch->addCondition('ro.offer_status', '=', $status);
            $cnd->attachCondition('roc.offer_status', '=', $status); */
            $srch->addCondition('rlo.rlo_status', '=', $status);
        }

        $comments = FatApp::getPostedData('keyword', FatUtility::VAR_STRING, '');
        if (!empty($comments)) {
            $cnd = $srch->addCondition('ro.offer_comments', 'LIKE', '%' . $comments . '%');
            $cnd->attachCondition('roc.offer_comments', 'LIKE', '%' . $comments . '%');
        }

        $srch->addOrder('offer_added_on', 'DESC');
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
        $this->set("statusArr", RfqOffers::getStatusArr($this->siteLangId));
        $this->set("rfqStatusArr", RequestForQuote::getStatusArr($this->siteLangId));
        $this->set("approvalStatusArr", RequestForQuote::getApprovalStatusArr($this->siteLangId));
        $this->set('rfqStatus', RequestForQuote::getAttributesById($rfqId, 'rfq_status'));
        $tpl = 'rfq-offers/search.php';
        if ($this->isSeller) {
            $tpl = 'rfq-offers/seller-offers-search.php';
        }
        if (true === MOBILE_APP_API_CALL) {
            $this->_template->render(true, true, $tpl);
        }
        $this->_template->render(false, false, $tpl);
    }

    private function getForm(): Form
    {
        if ($this->isSeller) {
            $frm = RfqOffers::getSellerForm();
            $fld = $frm->getField('offer_user_id');
            if (null != $fld) {
                $frm->removeField($fld);
            }
        } else {
            $frm = RfqOffers::getBuyerForm();
        }

        $frm->addHiddenField('', 'rfq_product_id');
        return $frm;
    }

    public function form()
    {
        $rfqId = FatApp::getPostedData('rfqId', FatUtility::VAR_INT, 0);
        $rfqData = RequestForQuote::getAttributesById($rfqId, ['rfq_id', 'rfq_product_id', 'rfq_quantity', 'rfq_quantity_unit', 'rfq_product_type']);
        if (!$rfqData) {
            LibHelper::exitWithError(Labels::getLabel('ERR_INVALID_REQUEST_ID', $this->siteLangId), true);
        }
        $rfq = new RequestForQuote($rfqId);
        $attr = [
            'rfq_product_type',
            'rfq_title',
            'rfqts_user_id as seller_id',
            'rfqts_selprod_id'
        ];
        $rfqDataArr = $rfq->get($this->siteLangId, $attr, 'LEFT', $this->userParentId);
        $selProdPrice = 0;
        if (!empty($rfqDataArr) && !empty($rfqDataArr['rfqts_selprod_id'])) {
            $selProdPrice = SellerProduct::getAttributesById($rfqDataArr['rfqts_selprod_id'], 'selprod_price');
        }
        $recordId = FatApp::getPostedData('recordId', FatUtility::VAR_INT, 0);
        $rfqOfferData = (array)RfqOffers::getAttributesById($recordId, RfqOffers::FIELDS);
        /* if (0 < $recordId && $rfqOfferData['offer_status'] != RfqOffers::STATUS_OPEN) {
            LibHelper::exitWithError(Labels::getLabel('LBL_INVALID_OFFER', $this->siteLangId), true);
        } */

        $frm = $this->getForm();
        $ctrOfferId = FatApp::getPostedData('offer_counter_offer_id', FatUtility::VAR_INT, 0);

        if (0 < $ctrOfferId) {
            $qty = RfqOffers::getAttributesById($ctrOfferId, 'offer_quantity');
        } else {
            $qty = isset($rfqOfferData['offer_quantity']) && !empty($rfqOfferData['offer_quantity']) ? $rfqOfferData['offer_quantity'] : $rfqData['rfq_quantity'];
        }

        $shippingcharges = '';
        /* In case of counter offer of buyer and edit own record. */
        if ($this->isSeller) {
            if (Product::PRODUCT_TYPE_PHYSICAL == $rfqData['rfq_product_type'] && (0 < $ctrOfferId || 0 < $recordId)) {
                $srch = new SearchBase(RfqOffers::DB_RFQ_LATEST_OFFER);
                $srch->doNotCalculateRecords();
                $srch->setPageSize(1);
                $srch->addCondition('rlo_rfq_id', '=', 'mysql_func_' . $rfqId, 'AND', true);
                if (0 < $recordId) {
                    $srch->addCondition('rlo_seller_offer_id', '=', 'mysql_func_' . $recordId, 'AND', true);
                } else {
                    $srch->addCondition('rlo_buyer_offer_id', '=', 'mysql_func_' . $ctrOfferId, 'AND', true);
                }
                $srch->addFld('rlo_shipping_charges');
                $shippingcharges = ((array)FatApp::getDb()->fetch($srch->getResultSet()))['rlo_shipping_charges'] ?? '';
            }

            if (Product::PRODUCT_TYPE_PHYSICAL != $rfqData['rfq_product_type']) {
                $fld = $frm->getField('rlo_shipping_charges');
                $frm->removeField($fld);
            }
        }

        $data = array_merge($rfqOfferData, [
            'offer_rfq_id' => $rfqId,
            'offer_quantity' => $qty,
            'rfq_quantity_unit' => $rfqData['rfq_quantity_unit'],
            'offer_user_id' => $rfqOfferData['offer_user_id'] ?? 0,
            'rfq_product_id' => $rfqData['rfq_product_id'] ?? 0,
            'rlo_shipping_charges' => (0 == $shippingcharges ? '' : $shippingcharges),
        ]);

        if (0 < $ctrOfferId) {
            $data['offer_counter_offer_id'] = $ctrOfferId;
        }

        $frm->fill($data);
        $this->set('selProdPrice', $selProdPrice);
        $this->set('frm', $frm);
        $this->set('rfqId', $rfqId);
        $this->set('rfq_quantity_unit', $rfqData['rfq_quantity_unit']);
        $this->set('recordId', $recordId);
        $this->set('counterOfferId', $ctrOfferId);
        $this->set('formTitle', Labels::getLabel('LBL_RFQ_OFFER_FORM', $this->siteLangId));
        $this->set('callback', 'closeForm');
        $this->set('html', $this->_template->render(false, false, 'rfq-offers/form.php', true));
        $this->_template->render(false, false, 'json-success.php', true, false);
    }

    public function setup()
    {
        $frm = $this->getForm();
        $post = $frm->getFormDataFromArray(FatApp::getPostedData());
        $this->userPrivilege->canEditRfqOffers($this->userId);
        if (false === $post) {
            LibHelper::exitWithError(current($frm->getValidationErrors()), true);
        }

        if (empty($post['offer_rfq_id'])) {
            LibHelper::exitWithError(Labels::getLabel('ERR_INVALID_REQUEST_ID', $this->siteLangId), true);
        }

        $recordId = FatApp::getPostedData('offer_id', FatUtility::VAR_INT, 0);
        $counterOfferId = FatApp::getPostedData('offer_counter_offer_id', FatUtility::VAR_INT, 0);

        if ($this->isSeller && !$this->isShopActive($this->userParentId)) {
            LibHelper::exitWithError(Labels::getLabel("MSG_YOUR_SHOP_IS_NOT_ACTIVE_YET", $this->siteLangId));
        }

        if ($this->isSeller && 1 > $counterOfferId && 1 > $recordId && false == RfqOffers::hasValidSubscription($this->userParentId, $this->siteLangId)) {
            LibHelper::exitWithError(Labels::getLabel('ERR_SUBSCRIPTION_PLAN_RFQ_OFFERS_LIMIT_REACHED.', $this->siteLangId), true);
        }

        $rfqData = RequestForQuote::getAttributesById($post['offer_rfq_id'], ['rfq_status', 'rfq_selprod_id', 'rfq_product_id', 'rfq_visibility_type']);
        $rfqStatus = $rfqData['rfq_status'];
        if ($this->isSeller && (false === $rfqStatus || $rfqStatus == RequestForQuote::STATUS_CLOSED)) {
            LibHelper::exitWithError(Labels::getLabel('ERR_RFQ_IS_CLOSED_BY_BUYER', $this->siteLangId), true);
        }

        if (1 > $post['offer_id'] && 0 < $counterOfferId) {
            if ($this->isBuyer && false == RfqOffers::canBuyerReply($counterOfferId)) {
                LibHelper::exitWithError(Labels::getLabel('ERR_NOT_ALLOWED!!', $this->siteLangId), true);
            }
        }

        $selprodId = 0;
        if (1 > $counterOfferId && $this->isSeller) {
            if (0 < $rfqData['rfq_selprod_id'] && 0 < $rfqData['rfq_product_id']) {
                $selprodId = RequestForQuote::getSellerProductId($post['offer_rfq_id'], $this->userParentId);
                if (1 > $selprodId) {
                    LibHelper::exitWithError(Labels::getLabel('ERR_PLEASE_LINK_YOUR_INVENTORY_FIRST.', $this->siteLangId));
                }
            }

            $qty = FatApp::getPostedData('offer_quantity', FatUtility::VAR_INT, 0);
            if (1 > $recordId && false == RfqOffers::validateOfferRequest($post['offer_rfq_id'], $qty, $this->userParentId)) {
                LibHelper::exitWithError(Labels::getLabel('ERR_DUPLICATE_OFFER._YOU_CANNOT_PLACE_OFFER_WITH_THIS_QTY.', $this->siteLangId));
            }
        }

        $negotiable = FatApp::getPostedData('offer_negotiable', FatUtility::VAR_INT, applicationConstants::NO);
        if ($this->isBuyer) {
            $negotiable = 1;
        }

        $post['offer_user_id'] = $this->userParentId;
        $post['offer_user_type'] = $this->isSeller ? User::USER_TYPE_SELLER : User::USER_TYPE_BUYER;
        $post['offer_negotiable'] = $negotiable;

        $rfqOfferData = RfqOffers::getAttributesById($recordId, ['offer_status', 'offer_price', 'offer_quantity', 'offer_counter_offer_id']);
        $changeInOffer = (!empty($rfqOfferData) && ($post['offer_quantity'] != $rfqOfferData['offer_quantity'] || $post['offer_price'] != $rfqOfferData['offer_price']));

        $canSendEmail = (1 > $recordId || $changeInOffer);
        $ifRejected = !empty($rfqOfferData) ? (RfqOffers::STATUS_REJECTED == $rfqOfferData['offer_status']) : false;

        if ($ifRejected) {
            $pOfferId = (int)RfqOffers::getAttributesById($recordId, 'offer_primary_offer_id');
            $oldShippingCharges = RfqOffers::getShippingCharges($pOfferId);
            $shippingcharges = FatApp::getPostedData('rlo_shipping_charges', FatUtility::VAR_FLOAT, 0);

            $post['offer_status'] = $rfqOfferData['offer_status'];
            if ($changeInOffer || ($oldShippingCharges != $shippingcharges && $this->isSeller)) {
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

        if (1 > $recordId && RequestForQuote::VISIBILITY_TYPE_OPEN == $rfqData['rfq_visibility_type']) {
            $rfqToSeller = [
                'rfqts_rfq_id' => $post['offer_rfq_id'],
                'rfqts_user_id' => $post['offer_user_id']
            ];
            if (!FatApp::getDb()->insertFromArray(RequestForQuote::DB_RFQ_TO_SELLERS, $rfqToSeller, true, array(), $rfqToSeller)) {
                $this->error = FatApp::getDb()->getError();
                return false;
            }
        }

        if (1 > $counterOfferId) {
            $primaryOfferId = $rfq->getMainTableRecordId();
            $rloStatus = RfqOffers::STATUS_OPEN;
        } else {
            $primaryOfferId = (int)RfqOffers::getAttributesById($counterOfferId, 'offer_primary_offer_id');
            $rloStatus = RfqOffers::STATUS_COUNTERED;
        }

        $data = [
            'rlo_primary_offer_id' => $primaryOfferId,
            'rlo_rfq_id' => $post['offer_rfq_id']
        ];

        if (1 > $recordId || $ifRejected) {
            $data['rlo_status'] = ($ifRejected ? $post['offer_status'] : $rloStatus);
        }

        if ($this->isSeller) {
            $shippingcharges = FatApp::getPostedData('rlo_shipping_charges', FatUtility::VAR_FLOAT, 0);
            $data['rlo_shipping_charges'] = $shippingcharges;

            $data['rlo_seller_offer_id'] = $rfq->getMainTableRecordId();
            $data['rlo_seller_user_id'] = $this->userParentId;

            if (1 > $selprodId) {
                $selprodId = RequestForQuote::getSellerProductId($post['offer_rfq_id'], $this->userParentId);
            }

            if (1 > $counterOfferId && 0 < $selprodId) {
                $data['rlo_selprod_id'] = $selprodId;
            }

            if (1 > $counterOfferId && 1 > $recordId) {
                $data['rlo_added_on'] = date('Y-m-d H:i:s');
            }
        } else {
            $data['rlo_buyer_offer_id'] = $rfq->getMainTableRecordId();
        }

        if (false == $rfq->updateLatestOffer($data)) {
            $db->rollbackTransaction();
            LibHelper::exitWithError($rfq->getError(), true);
        }

        if (false == $rfq->add([
            'offer_primary_offer_id' => $primaryOfferId
        ])) {
            $db->rollbackTransaction();
            LibHelper::exitWithError($rfq->getError(), true);
        }

        if (0 < $counterOfferId) {
            $rfq = new RfqOffers($counterOfferId);
            if (false == $rfq->add([
                'offer_status' => RfqOffers::STATUS_COUNTERED
            ])) {
                $db->rollbackTransaction();
                LibHelper::exitWithError($rfq->getError(), true);
            }

            $updateArray = array('rfq_status' => RequestForQuote::STATUS_OFFERED);
            $whr = array('smt' => 'rfq_id = ?', 'vals' => array($post['offer_rfq_id']));

            if (!FatApp::getDb()->updateFromArray(RequestForQuote::DB_TBL, $updateArray, $whr)) {
                $db->rollbackTransaction();
                Message::addErrorMessage($this->invalidRequest);
                FatUtility::dieWithError(Message::getHtml());
            }
        }

        /* For New/Counter. Not on edit. */
        if ($canSendEmail) {
            $sellerId = (int)RfqOffers::getAttributesById($primaryOfferId, 'offer_user_id');
            $flds = LibHelper::addPrefixInArrValues(RfqOffers::FIELDS, 'ro.');
            $counterOfferFlds = [];
            foreach (RfqOffers::FIELDS as $fld) {
                array_push($counterOfferFlds, 'roc.' . $fld . ' as counter_' . $fld);
            }

            $dbFlds = array_merge($flds, $counterOfferFlds, ['rfq_title', 'rfq_number', 'rfq_added_on', 'rfq_approved', 'rfq_user_id', 'rfq_quantity', 'rfq_quantity_unit', 'rfq_visibility_type', 'bu.user_name as buyer_user_name', 'bu.user_id as buyer_user_id', 'buc.credential_email as buyer_credential_email', 'COALESCE(ous_l.shop_name, ous.shop_identifier) as shop_name', 'rlo_primary_offer_id', 'selprod_id', 'selprod_product_id', 'selprod_updated_on', 'bu.user_phone_dcode as buyer_phone_dcode', 'bu.user_phone as buyer_phone', 'suc.credential_email as seller_email', 'rlo_shipping_charges']);

            $srch = new RequestForQuoteSearch();
            $srch->doNotCalculateRecords();
            $srch->joinOffers(true);
            $srch->joinOfferPostedByUser();
            $srch->joinBuyer();
            $srch->joinOfferPostedBySellerShop(true);
            $srch->joinTable(RequestForQuote::DB_RFQ_TO_SELLERS, 'INNER JOIN', 'rs.rfqts_rfq_id = rfq_id AND rs.rfqts_user_id = ' . $sellerId, 'rs');
            $srch->joinTable(SellerProduct::DB_TBL, 'LEFT JOIN', 'sp.selprod_id = rs.rfqts_selprod_id', 'sp');
            $srch->joinTable(User::DB_TBL_CRED, 'LEFT JOIN', 'rs.rfqts_user_id = suc.credential_user_id', 'suc');
            $srch->addCondition('rlo.rlo_primary_offer_id', '=', $primaryOfferId);
            $srch->addCondition('rlo.rlo_seller_user_id', '=', $sellerId);
            $srch->addCondition('ro.offer_rfq_id', '=', $post['offer_rfq_id']);
            $srch->addCondition('rfq_deleted', '=', applicationConstants::NO);
            $srch->addMultipleFields($dbFlds);
            $srch->addOrder('offer_added_on', applicationConstants::SORT_DESC);
            $srch->setPageSize(1);
            $offerData = FatApp::getDb()->fetch($srch->getDataResultSet());
            $emailHandler = new EmailHandler();
            if (1 > $counterOfferId) {
                if (false === $emailHandler->sendNewRfqOfferNotification($this->siteLangId, $offerData)) {
                    $msg = $emailHandler->getError();
                    $msg = empty($msg) ? Labels::getLabel('ERR_UNABLE_TO_NOTIFY._NOTIFICATION_LOGGED_TO_THE_SYSTEM.', $this->siteLangId) : $msg;
                    LibHelper::exitWithError($msg, true);
                }
            } else {
                $offerData['isSeller'] = $this->isSeller;
                if (false === $emailHandler->sendCounterRfqOfferNotification($this->siteLangId, $offerData)) {
                    $msg = $emailHandler->getError();
                    $msg = empty($msg) ? Labels::getLabel('ERR_UNABLE_TO_NOTIFY._NOTIFICATION_LOGGED_TO_THE_SYSTEM.', $this->siteLangId) : $msg;
                    LibHelper::exitWithError($msg, true);
                }
            }
        }

        $db->commitTransaction();

        $recordId = $rfq->getMainTableRecordId();
        if (MOBILE_APP_API_CALL) {
            $this->set('data', ['record_id' => $recordId]);
            $this->_template->render();
        }

        $this->set('record_id', $recordId);
        $this->set('msg', Labels::getLabel('MGS_UPDATED_SUCCESSFULLY.', $this->siteLangId));
        $this->_template->render(false, false, 'json-success.php');
    }

    public function view()
    {
        $post = FatApp::getPostedData();
        $rfqId = FatApp::getPostedData('rfqId', FatUtility::VAR_INT, 0);
        $primaryOfferId = FatApp::getPostedData('offerId', FatUtility::VAR_INT, 0);
        if (1 > $rfqId || 1 > $primaryOfferId) {
            LibHelper::exitWithError(Labels::getLabel('ERR_INVALID_REQUEST_ID', $this->siteLangId), true);
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
        $flds = LibHelper::addPrefixInArrValues(RfqOffers::FIELDS, 'ro.');
        $counterOfferFlds = [];
        foreach (RfqOffers::FIELDS as $fld) {
            array_push($counterOfferFlds, 'roc.' . $fld . ' as counter_' . $fld);
        }

        $srch->addOrder('ro.offer_added_on', 'DESC');
        $this->set("arrListing", FatApp::getDb()->fetchAll($srch->getDataResultSet()));
        if (MOBILE_APP_API_CALL) {
            $this->_template->render();
        }

        $this->set('postedData', FatApp::getPostedData());

        $jsonData = [
            'html' => $this->_template->render(false, false, 'rfq-offers/view.php', true),
        ];
        LibHelper::exitWithSuccess($jsonData, true);
    }

    public function validateRequest(int $recordId, int $rfqId, int $status)
    {
        $srch = new SearchBase(RfqOffers::DB_TBL, 'ro');
        $srch->joinTable(RfqOffers::DB_RFQ_LATEST_OFFER, 'INNER JOIN', 'rlo.rlo_primary_offer_id = ro.offer_primary_offer_id', 'rlo');
        $srch->joinTable(RequestForQuote::DB_TBL, 'INNER JOIN', 'ro.offer_rfq_id = rfq.rfq_id', 'rfq');
        $srch->doNotCalculateRecords();
        $srch->setPageSize(1);
        $srch->addCondition('ro.offer_id', '=', 'mysql_func_' . $recordId, 'AND', true);
        $srch->addCondition('rfq.rfq_id', '=', 'mysql_func_' . $rfqId, 'AND', true);
        $srch->addCondition('rfq.rfq_user_id', '=', 'mysql_func_' . UserAuthentication::getLoggedUserId(), 'AND', true);

        $srch->addMultipleFields(['offer_id', 'offer_user_type', 'offer_primary_offer_id', 'rlo_status', 'offer_expired_on']);
        $offerData = (array)FatApp::getDB()->fetch($srch->getResultSet());
        if (empty($offerData) || User::USER_TYPE_BUYER == $offerData['offer_user_type']) {
            LibHelper::exitWithError(Labels::getLabel('ERR_INVALID_REQUEST_ID', $this->siteLangId), true);
        }

        $expiredOn = $offerData['offer_expired_on'] != '0000-00-00 00:00:00' ? strtotime($offerData['offer_expired_on']) : '';
        if (!empty($expiredOn) && $expiredOn < strtotime(date('Y-m-d'))) {
            $msg = Labels::getLabel('LBL_OFFER_EXPIRED!_YOU_ARE_NOT_ALLOWED_TO_ACCEPT/REJECT_THIS_OFFER_ANYMORE.', $this->siteLangId);
            LibHelper::exitWithError($msg, true);
        }

        $rfqInfo = RequestForQuote::getAttributesById($rfqId, ['rfq_approved', 'rfq_selprod_id', 'rfq_product_id', 'rfq_visibility_type', 'rfq_status']);
        if (RequestForQuote::STATUS_CLOSED == $rfqInfo['rfq_status'] && false == RequestForQuote::hasAcceptedOffers($rfqId)) {
            LibHelper::exitWithError(Labels::getLabel('ERR_THIS_RFQ_HAS_BEEN_CLOSED_BY_THE_BUYER', $this->siteLangId), true);
        }

        if ($status == $offerData['rlo_status']) {
            $statusArr = RfqOffers::getStatusArr($this->siteLangId);
            $msg = CommonHelper::replaceStringData(Labels::getLabel('LBL_THIS_OFFER_HAS_BEEN_ALREADY_{STATUS}', $this->siteLangId), [
                '{STATUS}' => $statusArr[$status]
            ]);
            LibHelper::exitWithError($msg, true);
        }
    }

    public function validateSellerRequest(int $recordId, int $rfqId)
    {
        $srch = new RequestForQuoteSearch();
        $srch->doNotCalculateRecords();
        $srch->setPageSize(1);
        $srch->joinSellers();
        $srch->joinOffers();
        $srch->addCondition('rfqts_user_id', '=', UserAuthentication::getLoggedUserId());
        $srch->addCondition('offer_id', '=', $recordId);
        $srch->addCondition('rfq_id', '=', $rfqId);

        $srch->addMultipleFields(['offer_id', 'offer_user_type']);
        $offerData = (array)FatApp::getDB()->fetch($srch->getResultSet());
        if (empty($offerData) /* || User::USER_TYPE_SELLER == $offerData['offer_user_type'] */) {
            LibHelper::exitWithError(Labels::getLabel('ERR_INVALID_REQUEST_ID', $this->siteLangId), true);
        }
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
        if (!empty($offerData)) {
            $offerData['isSeller'] = $this->isSeller;
            if (RfqOffers::SELLER_ACCEPTANCE == $acceptance || RfqOffers::BUYER_ACCEPTANCE == $acceptance) {
                $offerData['sellers'] = RequestForQuote::getSellersByRecordId($rfqId, true, true, $this->siteLangId);
            }

            $emailHandler = new EmailHandler();
            if (false === $emailHandler->sendRfqOfferActionNotification($this->siteLangId, $offerData, $acceptance)) {
                $msg = $emailHandler->getError();
                $msg = empty($msg) ? Labels::getLabel('ERR_UNABLE_TO_NOTIFY._NOTIFICATION_LOGGED_TO_THE_SYSTEM.', $this->siteLangId) : $msg;
                LibHelper::exitWithError($msg, true);
            }
        }
    }

    public function sellerAcceptance(int $recordId, int $rfqId)
    {
        $selProdId = RequestForQuote::getSellerProductId($rfqId, $this->userParentId);
        if (1 > $selProdId) {
            LibHelper::exitWithError(Labels::getLabel('ERR_PLEASE_LINK_YOUR_INVENTORY_FIRST'), true);
        }

        $this->validateSellerRequest($recordId, $rfqId);

        $rfq = new RfqOffers($recordId);
        if (false == $rfq->add([
            'offer_status' => RfqOffers::STATUS_ACCEPTED
        ])) {
            LibHelper::exitWithError($rfq->getError(), true);
        }

        $updateArray = array('rfq_status' => RequestForQuote::STATUS_ACCEPTED);
        $whr = array('smt' => 'rfq_id = ?', 'vals' => array($rfqId));

        $db = FatApp::getDb();
        if (!$db->updateFromArray(RequestForQuote::DB_TBL, $updateArray, $whr)) {
            LibHelper::exitWithError($db->getError(), true);
        }

        $primaryOfferId = (int)RfqOffers::getAttributesById($recordId, 'offer_primary_offer_id');
        $rfq = new RfqOffers();
        $data = [
            'rlo_primary_offer_id' => $primaryOfferId,
            'rlo_selprod_id' => $selProdId,
            'rlo_accepted_offer_id' => $recordId,
            'rlo_seller_acceptance' => applicationConstants::YES,
            'rlo_buyer_acceptance' => applicationConstants::YES,
        ];
        if (false == $rfq->updateLatestOffer($data)) {
            LibHelper::exitWithError($rfq->getError(), true);
        }
        $this->sendOfferActionNotification($recordId, $rfqId, $this->userParentId, RfqOffers::SELLER_ACCEPTANCE);

        $srch = new SearchBase(RfqOffers::DB_RFQ_LATEST_OFFER);
        $srch->doNotCalculateRecords();
        $srch->setPageSize(1);
        $srch->addCondition('rlo_primary_offer_id', '=', $primaryOfferId);
        $srch->addFld('rlo_buyer_acceptance');
        $buyerAcceptance = ((array)FatApp::getDb()->fetch($srch->getResultSet()))['rlo_buyer_acceptance'] ?? 0;

        if (applicationConstants::YES == $buyerAcceptance) {
            $this->accept($recordId, $rfqId);
        }

        FatUtility::dieJsonSuccess(Labels::getLabel('LBL_SUCCESS', $this->siteLangId));
    }
    public function buyerAcceptance(int $recordId, int $rfqId)
    {
        $this->validateRequest($recordId, $rfqId, RfqOffers::STATUS_ACCEPTED);

        $primaryOfferId = (int)RfqOffers::getAttributesById($recordId, 'offer_primary_offer_id');
        $data = [
            'rlo_primary_offer_id' => $primaryOfferId,
            'rlo_accepted_offer_id' => $recordId,
            'rlo_buyer_acceptance' => applicationConstants::YES
        ];
        $rfq = new RfqOffers();
        if (false == $rfq->updateLatestOffer($data)) {
            LibHelper::exitWithError($rfq->getError(), true);
        }

        $srch = new SearchBase(RfqOffers::DB_RFQ_LATEST_OFFER);
        $srch->doNotCalculateRecords();
        $srch->setPageSize(1);
        $srch->addCondition('rlo_primary_offer_id', '=', $primaryOfferId);
        $srch->addMultipleFields(['rlo_seller_acceptance', 'rlo_seller_user_id']);
        $rloResult = FatApp::getDb()->fetch($srch->getResultSet());
        $sellerAcceptance = $rloResult['rlo_seller_acceptance'] ?? 0;

        if (applicationConstants::YES == $sellerAcceptance) {
            $selProdId = RequestForQuote::getSellerProductId($rfqId, $rloResult['rlo_seller_user_id']);
            if (0 < $selProdId) {
                $this->accept($recordId, $rfqId);
            }
        }

        $this->sendOfferActionNotification($recordId, $rfqId, $rloResult['rlo_seller_user_id'], RfqOffers::BUYER_ACCEPTANCE);

        FatUtility::dieJsonSuccess(Labels::getLabel('LBL_SUCCESS', $this->siteLangId));
    }
    private function accept(int $recordId, int $rfqId)
    {
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

        $primaryOfferId = (int)RfqOffers::getAttributesById($recordId, 'offer_primary_offer_id');
        $rfq = new RfqOffers($recordId);
        $data = [
            'rlo_primary_offer_id' => $primaryOfferId,
            'rlo_accepted_offer_id' => $recordId,
            'rlo_status' => RfqOffers::STATUS_ACCEPTED
        ];

        if (false == $rfq->updateLatestOffer($data)) {
            $db->rollbackTransaction();
            LibHelper::exitWithError($rfq->getError(), true);
        }

        $selProdId = RequestForQuote::getSelprodId($rfqId);
        $_SESSION[UserAuthentication::SESSION_ELEMENT_NAME]['acceptedOffers'][$selProdId] = [
            'selprod_id' => $selProdId,
            'primary_offer_id' => $primaryOfferId,
            'accepted_offer_id' => $recordId,
        ];

        $db->commitTransaction();
        FatUtility::dieJsonSuccess(Labels::getLabel('LBL_SUCCESS', $this->siteLangId));
    }
    public function reject(int $recordId, int $rfqId)
    {
        if ($this->isSeller) {
            $this->validateSellerRequest($recordId, $rfqId);
        } else {
            $this->validateRequest($recordId, $rfqId, RfqOffers::STATUS_REJECTED);
        }

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

        $sellerId = RfqOffers::getSellerIdByOfferId($recordId);
        $this->sendOfferActionNotification($recordId, $rfqId, $sellerId);

        FatUtility::dieJsonSuccess(Labels::getLabel('LBL_SUCCESS', $this->siteLangId));
    }

    public function checkout(int $selprodId, int $offerId)
    {
        if (1 > $selprodId || false === $this->isBuyer || 1 > $offerId) {
            LibHelper::exitWithError(Labels::getLabel('ERR_INVALID_REQUEST', $this->siteLangId), false, true);
            CommonHelper::redirectUserReferer();
        }

        $srch = new SearchBase(RequestForQuote::DB_TBL, 'rfq');
        $srch->joinTable(RfqOffers::DB_RFQ_LATEST_OFFER, 'INNER JOIN', 'rlo_rfq_id = rfq_id and rlo.rlo_accepted_offer_id = ' . $offerId . ' AND rlo.rlo_selprod_id = ' . $selprodId, 'rlo');
        $srch->joinTable(RfqOffers::DB_TBL, 'INNER JOIN', 'aOfr.offer_id = rlo.rlo_accepted_offer_id ', 'aOfr');
        $srch->joinTable(RfqOffers::DB_TBL, 'INNER JOIN', 'sOfr.offer_id = rlo.rlo_seller_offer_id ', 'sOfr');
        $srch->joinTable(RequestForQuote::DB_RFQ_TO_SELLERS, 'INNER JOIN', 'rfqts_rfq_id = rfq_id', 'rfqs');

        $srch->joinTable(OrderProduct::DB_TBL, 'LEFT JOIN', 'op_offer_id = aOfr.offer_id', 'op');
        $srch->joinTable(Orders::DB_TBL_ORDER_PAYMENTS, 'LEFT JOIN', 'opayment_order_id = op_order_id', 'opay');
        $srch->doNotCalculateRecords();
        $srch->setPageSize(1);
        $srch->addMultipleFields(['op_id', 'opayment_txn_status', 'opayment_method', 'rfq.rfq_id', 'aOfr.offer_id', 'aOfr.offer_quantity', 'sOfr.offer_user_id as sellerId', 'rfqs.rfqts_selprod_id', 'rfq.rfq_addr_id', 'aOfr.offer_price', 'aOfr.offer_quantity', 'aOfr.offer_primary_offer_id', 'rlo_accepted_offer_id', 'rlo_selprod_id', 'rfq_quantity_unit']);
        $srch->addCondition('rfq_user_id', '=', UserAuthentication::getLoggedUserId());
        $srch->addCondition('aOfr.offer_id', '=', 'mysql_func_' . $offerId, 'AND', true);
        $srch->addCondition('rlo.rlo_selprod_id', '=', 'mysql_func_' . $selprodId, 'AND', true);
        $srch->addCondition('aOfr.offer_status', '=', RfqOffers::STATUS_ACCEPTED);
        $rfqOfferData = (array)FatApp::getDb()->fetch($srch->getResultSet());

        if (empty($rfqOfferData)) {
            LibHelper::exitWithError(Labels::getLabel('ERR_INVALID_REQUEST', $this->siteLangId), false, true);
            CommonHelper::redirectUserReferer();
        }

        if (!empty($rfqOfferData['op_id']) && (Orders::ORDER_PAYMENT_PAID == $rfqOfferData['opayment_txn_status']) || 'CashOnDelivery' == $rfqOfferData['opayment_method']) {
            LibHelper::exitWithError(Labels::getLabel('ERR_ORDER_ALREADY_PLACED_FOR_THIS_OFFER.'), false, true);
            CommonHelper::redirectUserReferer();
        }

        if (isset($rfqOfferData['rlo_selprod_id']) && empty($rfqOfferData['rlo_selprod_id'])) {
            LibHelper::exitWithError(Labels::getLabel('ERR_THE_SELLER_HAS_NOT_ADDED_ANY_INVENTORY_FOR_THIS_CATALOG_YET._WE_WILL_NOTIFY_YOU_ONCE_ADDED.', $this->siteLangId), false, true);
            CommonHelper::redirectUserReferer();
        }

        /* Update primary offer id. This will help when multiple offers accpted for same seller product.*/
        $_SESSION[UserAuthentication::SESSION_ELEMENT_NAME]['acceptedOffers'][$rfqOfferData['rlo_selprod_id']] = [
            'selprod_id' => $rfqOfferData['rlo_selprod_id'],
            'primary_offer_id' => $rfqOfferData['offer_primary_offer_id'],
            'accepted_offer_id' => $rfqOfferData['rlo_accepted_offer_id'],
        ];

        $db = FatApp::getDb();
        $db->startTransaction();

        $updateArray = array('addr_is_default' => 0);
        $whr = array('smt' => 'addr_type = ? and addr_record_id = ?', 'vals' => array(Address::TYPE_USER, UserAuthentication::getLoggedUserId()));

        if (!$db->updateFromArray(Address::DB_TBL, $updateArray, $whr)) {
            $db->rollbackTransaction();
            LibHelper::exitWithError($db->getError(), false, true);
            CommonHelper::redirectUserReferer();
        }

        $addr = new Address($rfqOfferData['rfq_addr_id']);
        $addr->assignValues(['addr_is_default' => applicationConstants::YES]);
        if (!$addr->save()) {
            $db->rollbackTransaction();
            LibHelper::exitWithError($addr->getError(), false, true);
            CommonHelper::redirectUserReferer();
        }

        $cartObj = new Cart(UserAuthentication::getLoggedUserId(), $this->siteLangId, $this->app_user['temp_user_id']);
        $cartObj->clear();

        if (!$cartObj->add($rfqOfferData['rlo_selprod_id'], $rfqOfferData['offer_quantity'])) {
            $db->rollbackTransaction();
            LibHelper::exitWithError(Labels::getLabel('ERR_UNABLE_TO_ADD_ITEM_TO_THE_CART', $this->siteLangId), false, true);
            CommonHelper::redirectUserReferer();
        }
        $weightUnitsArr = applicationConstants::getWeightUnitsArr($this->siteLangId, true);
        $_SESSION['offer_checkout'] = [
            'offer_id' => $offerId,
            'rlo_accepted_offer_id' => $rfqOfferData['rlo_accepted_offer_id'],
            'offer_primary_offer_id' => $rfqOfferData['offer_primary_offer_id'],
            'rfq_id' => $rfqOfferData['rfq_id'],
            'selprod_id' => $rfqOfferData['rlo_selprod_id'],
            'offer_price' => $rfqOfferData['offer_price'] * $rfqOfferData['offer_quantity'],
            'offer_quantity' => $rfqOfferData['offer_quantity'],
            'offer_quantity_unit' => $weightUnitsArr[$rfqOfferData['rfq_quantity_unit']],
        ];
        $db->commitTransaction();

        if (MOBILE_APP_API_CALL) {
            $this->set('data', ['offer_checkout' => $_SESSION['offer_checkout']]);
            $this->_template->render();
        }

        FatApp::redirectUser(UrlHelper::generateUrl('Cart', '', [], CONF_WEBROOT_FRONT_URL));
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
            $selprodId = 0;
            $shippingRates = [];
            if (is_array($selprodShippingRates) && !empty($selprodShippingRates)) {
                $selprodId = array_key_first($selprodShippingRates);
                $shippingRates = current($selprodShippingRates);
            }
            ksort($shippingRates);
            foreach ($shippingRates as $shippedByItemArr) {
                ksort($shippedByItemArr);
                foreach ($shippedByItemArr as $shipLevel => $items) {
                    $rates = $shippedByItemArr[$shipLevel]['rates'] ?? [];
                    switch ($shipLevel) {
                        case Shipping::LEVEL_PRODUCT:
                            $rates = $rates[$selprodId] ?? [];
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
        if (MOBILE_APP_API_CALL) {
            $this->_template->render();
        }
        $this->set('html', $this->_template->render(false, false, 'rfq-offers/get-shipping-rates.php', true));
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

    public function viewRfq(int $recordId)
    {
        $canView = true;
        if ($this->isSeller) {
            $canView = $this->userPrivilege->canViewRequestForQuote($this->userId, true);
        }

        if (1 > $recordId || false == $canView) {
            LibHelper::exitWithError($this->str_invalid_request, true);
        }

        $srch = new RequestForQuoteSearch();
        if ($this->isSeller) {
            $srch->joinSellers();
        }
        $srch->joinBuyer();
        $srch->joinBuyerAddress($this->siteLangId);
        $srch->joinCountry(true);
        $srch->joinState(true);

        $dbFlds = array_merge(RequestForQuote::FIELDS, ['addr_name', 'addr_address1', 'addr_address2', 'addr_city', 'addr_zip', 'addr_phone_dcode', 'addr_phone', 'buc.credential_username as credential_username', 'bu.user_id as user_id', 'bu.user_updated_on', 'credential_email', 'bu.user_name', 'IFNULL(country_name, country_code) as country_name', 'IFNULL(state_name, state_identifier) as state_name']);
        $srch->addMultipleFields($dbFlds);

        if ($this->isSeller) {
            $srch->addCondition('rfqts_user_id', '=', $this->userParentId);
        } else {
            $srch->addCondition('rfq_user_id', '=', $this->userId);
        }
        $srch->addCondition('rfq_id', '=', $recordId);
        $rfqData = FatApp::getDb()->fetch($srch->getDataResultSet());
        $rfqData['prodcat_name'] = RequestForQuote::getRfqCategoriesName($rfqData['rfq_prodcat_id'], $this->siteLangId);
        $linkedSelprodId = 0;
        if ($this->isSeller) {
            $linkedSelprodId = RequestForQuote::getSellerProductId($recordId, $this->userParentId);
        }
        $this->set("rfqData", $rfqData);
        $this->set("linkedSelprodId", $linkedSelprodId);
        $this->set("approvalStatusArr", RequestForQuote::getApprovalStatusArr($this->siteLangId));
        $this->set("statusArr", RequestForQuote::getStatusArr($this->siteLangId));
        $this->set('recordId', $recordId);
        $this->set('isSeller', $this->isSeller);

        $this->set('html', $this->_template->render(false, false, 'rfq-offers/view-rfq.php', true));
        $this->_template->render(false, false, 'json-success.php', true, false);
    }

    public function attachmentForm()
    {
        $primaryOfferId = FatApp::getPostedData('rom_primary_offer_id', FatUtility::VAR_INT, 0);
        $onlyWithAttachments = FatApp::getPostedData('only_with_attachments', FatUtility::VAR_INT, 0);
        if (1 > $primaryOfferId) {
            LibHelper::exitWithError(Labels::getLabel('ERR_INVALID_REQUEST.', $this->siteLangId), true);
        }
        $frm = RfqOffers::getAttachmentForm($this->isBuyer);
        $frm->fill(['rom_primary_offer_id' => $primaryOfferId]);

        $data = RfqOffers::getMessages($primaryOfferId, hideForBuyer: $this->isBuyer, onlyWithAttachments: (0 < $onlyWithAttachments));

        if (!empty($data['data'])) {
            $msgIds = [];
            foreach ($data['data'] as $rom) {
                $msgIds[] = $rom['rom_id'];
            }
            RfqOffers::markMessagesAsRead($primaryOfferId, $msgIds);
        }

        $this->set('pageCount', ($data['pageCount'] ?? 0));

        $messageData = array_reverse($data['data']);
        if (MOBILE_APP_API_CALL) {
            $this->set('messagesData', $messageData);
            $this->_template->render();
        }
        $this->set('data', $messageData);
        $this->set('includeTabs', false);
        $this->set('isSeller', $this->isSeller);
        $this->set('frm', $frm);
        $this->set('primaryOfferId', $primaryOfferId);
        $this->set('onlyWithAttachments', $onlyWithAttachments);

        $this->set('html', $this->_template->render(false, false, 'rfq-offers/attachment-form.php', return_content: true));
        $this->_template->render(false, false, 'json-success.php', false, false);
    }

    public function loadMoreAttachments()
    {
        $primaryOfferId = FatApp::getPostedData('rom_primary_offer_id', FatUtility::VAR_INT, 0);
        $onlyWithAttachments = FatApp::getPostedData('only_with_attachments', FatUtility::VAR_INT, 0);
        $page = FatApp::getPostedData('page', FatUtility::VAR_INT, 1);
        $data = RfqOffers::getMessages($primaryOfferId, $page, hideForBuyer: $this->isBuyer, onlyWithAttachments: (0 < $onlyWithAttachments));
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
        if (MOBILE_APP_API_CALL) {
            $this->set('messagesData', array_reverse($data['data']));
            $this->_template->render(true, true, 'rfq-offers/attachment-form.php');
        }
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
        $buyerAccess = FatApp::getPostedData('rom_buyer_access', FatUtility::VAR_INT, ($this->isBuyer ? 1 : 0));
        unset($post['attachment_file']);
        $post['rom_user_type'] = ($this->isSeller ? User::USER_TYPE_SELLER : User::USER_TYPE_BUYER);
        $post['rom_buyer_access'] = $buyerAccess;
        $rfqOffer = new RfqOffers();
        if (false == $rfqOffer->addMessage($post)) {
            LibHelper::exitWithError($rfqOffer->getError(), true);
        }
        return $rfqOffer->getMessageId();
    }

    public function uploadAttachment()
    {
        $primaryOfferId = FatApp::getPostedData('rom_primary_offer_id', FatUtility::VAR_INT, 0);
        if (1 > $primaryOfferId) {
            LibHelper::exitWithError($this->str_invalid_request_id, true);
        }

        $rfqId = RfqOffers::getAttributesById($primaryOfferId, 'offer_rfq_id');
        $rfqInfo = RequestForQuote::getAttributesById($rfqId, ['rfq_approved', 'rfq_selprod_id', 'rfq_product_id', 'rfq_visibility_type', 'rfq_status']);
        if (RequestForQuote::STATUS_CLOSED == $rfqInfo['rfq_status'] && false == RequestForQuote::hasAcceptedOffers($rfqId)) {
            LibHelper::exitWithError(Labels::getLabel('ERR_THIS_RFQ_HAS_BEEN_CLOSED_BY_THE_BUYER', $this->siteLangId), true);
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
        $this->set('isSeller', $this->isSeller);
        $this->set('row', $messageData);
        if (MOBILE_APP_API_CALL) {
            $attachmentLink = '';
            if (0 < $messageData['afile_id']) {
                $attachmentLink = UrlHelper::generateFullUrl('RfqOffers', 'downloadAttachmentFile', array($messageData['rom_id'], $messageData['rom_primary_offer_id']));
            }
            $messageData['attachmentLink'] = $attachmentLink;
            $this->set('data', array('message' => $messageData));
            $this->_template->render();
        }

        $this->set('html', $this->_template->render(false, false, 'rfq-offers/attachment-record.php', true));
        $this->_template->render(false, false, 'json-success.php', false, false);
    }

    public function downloadAttachmentFile(int $romId, int $primaryOfferId)
    {
        $res = AttachedFile::getAttachment(AttachedFile::FILETYPE_RFQ_OFFER_FILE, $romId, $primaryOfferId);
        if ($res == false || 1 > $res['afile_id']) {
            LibHelper::exitWithError(Labels::getLabel('ERR_NOT_AVAILABLE_TO_DOWNLOAD', $this->siteLangId), false, true);
            FatApp::redirectUser(UrlHelper::generateUrl($this->isSeller ? 'SellerRequestForQuotes' : 'RequestForQuotes'));
        }

        if (!file_exists(CONF_UPLOADS_PATH . $res['afile_physical_path'])) {
            LibHelper::exitWithError(Labels::getLabel('ERR_FILE_NOT_FOUND', $this->siteLangId), false, true);
            FatApp::redirectUser(UrlHelper::generateUrl($this->isSeller ? 'SellerRequestForQuotes' : 'RequestForQuotes'));
        }

        AttachedFile::downloadAttachment($res['afile_physical_path'], $res['afile_name']);
    }

    public function getBreadcrumbNodes($action)
    {
        switch ($action) {
            case 'listing':
                $this->nodes = [
                    [
                        'title' => ($this->isSeller) ? Labels::getLabel("LBL_SELLER_REQUEST_FOR_QUOTES", $this->siteLangId) : Labels::getLabel("LBL_REQUEST_FOR_QUOTES", $this->siteLangId),
                        'href' => UrlHelper::generateUrl(($this->isSeller ? 'Seller' : '') . 'RequestForQuotes')
                    ],
                    ['title' => Labels::getLabel('LBL_OFFERS', $this->siteLangId)]
                ];
                break;
            case 'globalListing':
                $this->nodes = [
                    [
                        'title' => ($this->isSeller) ? Labels::getLabel("LBL_GLOBAL_REQUEST_FOR_QUOTES", $this->siteLangId) : Labels::getLabel("LBL_REQUEST_FOR_QUOTES", $this->siteLangId),
                        'href' => UrlHelper::generateUrl(($this->isSeller ? 'Seller' : '') . 'RequestForQuotes', 'global')
                    ],
                    ['title' => Labels::getLabel('LBL_OFFERS', $this->siteLangId)]
                ];
                break;
        }
        return $this->nodes;
    }
}
