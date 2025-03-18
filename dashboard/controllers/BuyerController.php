<?php
class BuyerController extends BuyerBaseController
{

    private const DIGITAL_FILES_ZIP = 1;
    private const DIGITAL_LINKS_FILE = 2;

    public function __construct($action)
    {
        parent::__construct($action);
    }

    public function index()
    {
        $userId = UserAuthentication::getLoggedUserId();
        $user = new User($userId);
        $_SESSION[UserAuthentication::SESSION_ELEMENT_NAME]['activeTab'] = 'B';

        $ocSrch = new SearchBase(OrderProduct::DB_TBL_CHARGES, 'opc');
        $ocSrch->doNotCalculateRecords();
        $ocSrch->doNotLimitRecords();
        $ocSrch->addMultipleFields(array('opcharge_op_id', 'sum(opcharge_amount) as op_other_charges'));
        $ocSrch->addGroupBy('opc.opcharge_op_id');
        $qryOtherCharges = $ocSrch->getQuery();

        $srch = new OrderProductSearch($this->siteLangId, true, true);
        $srch->joinSellerProducts();
        $srch->joinOrderProductSpecifics();
        $srch->joinShippingCharges();
        $srch->joinSellerProductGroup();
        $srch->addCountsOfOrderedProducts();
        $srch->joinTable('(' . $qryOtherCharges . ')', 'LEFT OUTER JOIN', 'op.op_id = opcc.opcharge_op_id', 'opcc');
        //$srch->addBuyerOrdersCounts(date('Y-m-d',strtotime("-1 days")),date('Y-m-d'),'yesterdayOrder');
        $srch->addStatusCondition(unserialize(FatApp::getConfig("CONF_BUYER_ORDER_STATUS", null, '')));
        $srch->addCondition('order_user_id', '=', $userId);
        $srch->addOrder("op_id", "DESC");
        $srch->setPageNumber(1);
        $srch->setPageSize(applicationConstants::DASHBOARD_PAGE_SIZE);

        $srch->addMultipleFields(
            array(
                'order_number',
                'order_id',
                'order_user_id',
                'op_selprod_id',
                'op_is_batch',
                'selprod_product_id',
                'order_date_added',
                'order_net_amount',
                'op_invoice_number',
                'totCombinedOrders as totOrders',
                'op_selprod_title',
                'op_product_name',
                'op_product_type',
                'op_status_id',
                'op_id',
                'op_qty',
                'op_selprod_options',
                'op_brand_name',
                'op_other_charges',
                'op_unit_price',
                'IFNULL(orderstatus_name, orderstatus_identifier) as orderstatus_name',
                'orderstatus_color_class',
                'order_pmethod_id',
                'opshipping_fulfillment_type',
                'op_rounding_off',
                'op_selprod_return_age',
                'op_selprod_cancellation_age'
            )
        );
        $rs = $srch->getResultSet();
        $orders = FatApp::getDb()->fetchAll($rs);

        /* $getPurchasedsrch = clone $srch;
          $getPurchasedsrch->addCondition('order_payment_status', '=', 1);
          $getPurchasedsrch->addfld('count(order_id) as totalPurchasedItems');
          $countPurchasedItemsRs = $getPurchasedsrch->getResultSet();
          $totalPurchasedItems = FatApp::getDb()->fetch($countPurchasedItemsRs, 'totalPurchasedItems'); */

        $oObj = new Orders();
        foreach ($orders as &$order) {
            $charges = $oObj->getOrderProductChargesArr($order['op_id']);
            $order['charges'] = $charges;
        }

        /*
         * Offers Listing
         */
        $offers = DiscountCoupons::getUserCoupons(UserAuthentication::getLoggedUserId(), $this->siteLangId);

        $this->setDashboardStats();

        $this->set('offers', $offers);
        $this->set('data', $user->getProfileData());
        $this->set('orders', $orders);
        $this->set('OrderReturnRequestStatusArr', OrderReturnRequest::getRequestStatusArr($this->siteLangId));
        $this->set('OrderRetReqStatusClassArr', OrderReturnRequest::getRequestStatusClassArr());
        $this->set('OrderCancelRequestStatusArr', OrderCancelRequest::getRequestStatusArr($this->siteLangId));
        $this->set('cancelReqStatusClassArr', OrderCancelRequest::getStatusClassArr());
        $this->set('ordersCount', $srch->recordCount());

        $this->set('classArr', applicationConstants::getClassArr());
        $this->_template->addJs('js/slick.min.js');
        $this->_template->render(true, true);
    }

    private function setDashboardStats()
    {
        $userId = UserAuthentication::getLoggedUserId();

        /* Orders Counts [ */
        $orderSrch = new OrderProductSearch($this->siteLangId, true, true);
        $orderSrch->doNotCalculateRecords();
        $orderSrch->doNotLimitRecords();
        /* $orderSrch->addBuyerOrdersCounts(date('Y-m-d',strtotime("-1 days")),date('Y-m-d',strtotime("-1 days")),'yesterdayOrder'); */
        // $orderSrch->addBuyerOrdersCounts(false, false, 'pendingOrder');
        $completedOrderStatus = unserialize(FatApp::getConfig("CONF_COMPLETED_ORDER_STATUS", FatUtility::VAR_STRING, ''));
        if (!empty($completedOrderStatus)) {
            $orderSrch->addCondition('op_status_id', 'NOT IN', $completedOrderStatus);
        }
        $orderSrch->addGroupBy('order_user_id');
        $orderSrch->addCondition('order_user_id', '=', $userId);
        $orderSrch->addMultipleFields(array('COUNT(o.order_id) as pendingOrderCount'));
        $rs = $orderSrch->getResultSet();
        $ordersStats = FatApp::getDb()->fetch($rs);
        $this->set('pendingOrderCount', isset($ordersStats['pendingOrderCount']) ? FatUtility::int($ordersStats['pendingOrderCount']) : 0);
        /* ] */

        $txnObj = new Transactions();
        $txnsSummary = $txnObj->getTransactionSummary($userId, date('Y-m-d'));
        $this->set('txnsSummary', $txnsSummary);

        /* Cancellation Request Listing [] */
        $canSrch = $this->orderCancellationRequestObj();
        $canSrch->setPageSize(applicationConstants::DASHBOARD_PAGE_SIZE);
        $canSrch->addOrder('ocrequest_id', 'DESC');
        $cancellationRequests = FatApp::getDb()->fetchAll($canSrch->getResultSet());
        $this->set('cancellationRequests', $cancellationRequests);
        /* ] */

        /* Return Request Listing [ */
        $srchReturnReq = $this->orderReturnRequestObj();
        $srchReturnReq->setPageSize(applicationConstants::DASHBOARD_PAGE_SIZE);
        $rs = $srchReturnReq->getResultSet();
        $returnRequests = FatApp::getDb()->fetchAll($rs);
        $this->set('returnRequests', $returnRequests);
        /* ] */

        /* Unread Message Count [ */
        /* $threadObj = new Thread();
          $todayUnreadMessageCount = $threadObj->getMessageCount($userId, Thread::MESSAGE_IS_UNREAD, date('Y-m-d'));
          $totalMessageCount = $threadObj->getMessageCount($userId);
          $this->set('totalMessageCount', $totalMessageCount);
          $this->set('todayUnreadMessageCount', $todayUnreadMessageCount);
         */

        /* ] */


        /* if(FatApp::getConfig('CONF_ADD_FAVORITES_TO_WISHLIST', FatUtility::VAR_INT, 1) == applicationConstants::NO) {
          $totalFavouriteItems = UserFavorite::getUserFavouriteItemCount($userId, $this->siteLangId);
          }else{
          $totalFavouriteItems = UserWishList::getUserWishlistItemCount($userId);
          }
          $this->set('totalFavouriteItems', $totalFavouriteItems);
         */

        $this->set('userBalance', User::getUserBalance($userId));
        $this->set('totalRewardPoints', UserRewardBreakup::rewardPointBalance($userId));
    }

    public function viewOrder($orderId, $opId = 0, $print = false)
    {
        if (!$orderId) {
            $message = Labels::getLabel('MSG_Invalid_Access', $this->siteLangId);
            if (true === MOBILE_APP_API_CALL) {
                LibHelper::dieJsonError($message);
            }
            Message::addErrorMessage($message);
            CommonHelper::redirectUserReferer();
        }

        $opId = FatUtility::int($opId);
        if (0 < $opId) {
            $opOrderId = OrderProduct::getAttributesById($opId, 'op_order_id');
            if ($orderId != $opOrderId) {
                $message = Labels::getLabel('MSG_Invalid_Order', $this->siteLangId);
                if (true === MOBILE_APP_API_CALL) {
                    LibHelper::dieJsonError($message);
                }
                Message::addErrorMessage($message);
                CommonHelper::redirectUserReferer();
            }
        }
        $primaryOrderDisplay = false;

        $orderObj = new Orders();
        $userId = UserAuthentication::getLoggedUserId();

        $orderDetail = $orderObj->getOrderById($orderId, $this->siteLangId);
        if (!$orderDetail || ($orderDetail && $orderDetail['order_user_id'] != $userId)) {
            $message = Labels::getLabel('MSG_Invalid_Access', $this->siteLangId);
            if (true === MOBILE_APP_API_CALL) {
                LibHelper::dieJsonError($message);
            }
            Message::addErrorMessage($message);
            CommonHelper::redirectUserReferer();
        }

        $orderDetail['charges'] = $orderObj->getOrderProductChargesByOrderId($orderDetail['order_id']);

        $srch = new OrderProductSearch($this->siteLangId, true, true);
        $srch->joinOrderProductShipment();
        $srch->joinPaymentMethod();
        $srch->joinSellerProducts();
        $srch->joinOrderUser();
        //$srch->joinShippingUsers();
        $srch->addOrderProductCharges();
        $srch->joinShippingCharges();
        $srch->joinAddress();
        $srch->joinOrderProductSpecifics();
        $srch->addCondition('order_user_id', '=', $userId);
        $srch->addCondition('order_id', '=', $orderId);

        if (0 < $opId) {
            if (true === MOBILE_APP_API_CALL) {
                $srch->joinTable(SelProdReview::DB_TBL, 'LEFT OUTER JOIN', 'o.order_id = spr.spreview_order_id and op.op_selprod_id = spr.spreview_selprod_id', 'spr');
                $srch->joinTable(SelProdRating::DB_TBL, 'LEFT OUTER JOIN', 'sprating.sprating_spreview_id = spr.spreview_id', 'sprating');
                $srch->addFld(array('*', 'IFNULL(ROUND(AVG(sprating_rating),2),0) as prod_rating'));
            }
            $srch->addCondition('op_id', '=', $opId);
            $srch->addStatusCondition(unserialize(FatApp::getConfig("CONF_BUYER_ORDER_STATUS")));
            $primaryOrderDisplay = true;
        }

        if (true === MOBILE_APP_API_CALL) {
            $srch->joinTable(
                OrderReturnRequest::DB_TBL,
                'LEFT OUTER JOIN',
                'orr.orrequest_op_id = op.op_id',
                'orr'
            );
            $srch->joinTable(
                OrderCancelRequest::DB_TBL,
                'LEFT OUTER JOIN',
                'ocr.ocrequest_op_id = op.op_id',
                'ocr'
            );
            $srch->addFld(array('*', 'IFNULL(orrequest_id, 0) as return_request', 'IFNULL(ocrequest_id, 0) as cancel_request', 'IFNULL(orderstatus_name, orderstatus_identifier) as orderstatus_name'));
        }
        $childOrderDetail = FatApp::getDb()->fetchAll($srch->getResultSet(), 'op_id');

        if (empty($childOrderDetail) || 1 > count($childOrderDetail)) {
            $message = Labels::getLabel('MSG_Invalid_Access', $this->siteLangId);
            if (true === MOBILE_APP_API_CALL) {
                LibHelper::dieJsonError($message);
            }
            Message::addErrorMessage($message);
            CommonHelper::redirectUserReferer();
        }

        foreach ($childOrderDetail as $op_id => $val) {
            $childOrderDetail[$op_id]['charges'] = $orderDetail['charges'][$op_id];

            $opChargesLog = new OrderProductChargeLog($op_id);
            $childOrderDetail[$op_id]['taxOptions'] = $opChargesLog->getData($this->siteLangId);
        }

        $shippingApiObj = NULL;
        if ($opId > 0) {
            $childOrderDetail = array_shift($childOrderDetail);
            $shippedBySeller = CommonHelper::canAvailShippingChargesBySeller($childOrderDetail['op_selprod_user_id'], $childOrderDetail['opshipping_by_seller_user_id']);
            if ($childOrderDetail['opshipping_fulfillment_type'] == Shipping::FULFILMENT_SHIP) {
                $shippingApiObj = (new Shipping($this->siteLangId))->getShippingApiObj(($shippedBySeller ? $childOrderDetail['opshipping_by_seller_user_id'] : 0)) ?? NULL;
            }
        }

        $address = $orderObj->getOrderAddresses($orderDetail['order_id']);
        $orderDetail['billingAddress'] = $address[Orders::BILLING_ADDRESS_TYPE] ?? [];
        $orderDetail['shippingAddress'] = (!empty($address[Orders::SHIPPING_ADDRESS_TYPE])) ? $address[Orders::SHIPPING_ADDRESS_TYPE] : array();

        $pickUpAddress = $orderObj->getOrderAddresses($orderDetail['order_id'], $opId);
        $orderDetail['pickupAddress'] = (!empty($pickUpAddress[Orders::PICKUP_ADDRESS_TYPE])) ? $pickUpAddress[Orders::PICKUP_ADDRESS_TYPE] : array();

        if ($opId > 0) {
            $orderDetail['comments'] = $orderObj->getOrderComments($this->siteLangId, array("op_id" => $childOrderDetail['op_id']));
        } else {
            $orderDetail['comments'] = $orderObj->getOrderComments($this->siteLangId, array("order_id" => $orderDetail['order_id']));
        }

        $opSrchObj = Orders::searchOrderProducts(['order_id' => $orderDetail['order_id']]);
        $opSrchObj->addFld('count(1) as opCount');
        $opSrchObj->doNotCalculateRecords();
        $childOrderProductsCountData = FatApp::getDb()->fetch($opSrchObj->getResultSet());
        if (1 > $opId || 1 == $childOrderProductsCountData['opCount']) {
            $payments = $orderObj->getOrderPayments(array("order_id" => $orderDetail['order_id']));
            if (true === MOBILE_APP_API_CALL) {
                $payments = array_values($payments);
            }
            $orderDetail['payments'] = $payments;
        }

        $digitalDownloads = array();
        if ($opId > 0 && $childOrderDetail['op_product_type'] == Product::PRODUCT_TYPE_DIGITAL) {
            $digitalDownloads = Orders::getOrderProductDigitalDownloads($childOrderDetail['op_id']);
        }

        $digitalDownloadLinks = array();
        if ($opId > 0 && $childOrderDetail['op_product_type'] == Product::PRODUCT_TYPE_DIGITAL) {
            $digitalDownloadLinks = Orders::getOrderProductDigitalDownloadLinks($childOrderDetail['op_id']);
        }
        $productType = !empty($childOrderDetail['selprod_product_id']) ? Product::getAttributesById($childOrderDetail['selprod_product_id'], 'product_type') : 0;

        $orderProductStatusArr = [];
        if (true === $primaryOrderDisplay) {
            $orderObj = new Orders($childOrderDetail['order_id']);
            if ($childOrderDetail['plugin_code'] == 'CashOnDelivery') {
                $processingStatuses = $orderObj->getAdminAllowedUpdateOrderStatuses(true);
            } else if ($childOrderDetail['plugin_code'] == 'PayAtStore') {
                $processingStatuses = $orderObj->getAdminAllowedUpdateOrderStatuses(false, false, true);
            } else {
                $processingStatuses = $orderObj->getAdminAllowedUpdateOrderStatuses(false, $childOrderDetail['op_product_type']);
            }

            if ($childOrderDetail["opshipping_fulfillment_type"] == Shipping::FULFILMENT_PICKUP) {
                $processingStatuses = array_diff($processingStatuses, (array) FatApp::getConfig("CONF_DEFAULT_SHIPPING_ORDER_STATUS", FatUtility::VAR_INT, 0));
            } else {
                $processingStatuses = array_diff($processingStatuses, (array) FatApp::getConfig("CONF_PICKUP_READY_ORDER_STATUS", FatUtility::VAR_INT, 0));
            }

            if ($childOrderDetail['op_product_type'] == Product::PRODUCT_TYPE_PHYSICAL) {
                $processingStatuses = array_diff($processingStatuses, [FatApp::getConfig("CONF_DEFAULT_APPROVED_ORDER_STATUS")]);
            }

            if (FatApp::getConfig("CONF_DEFAULT_CANCEL_ORDER_STATUS") == $childOrderDetail['orderstatus_id'] || FatApp::getConfig("CONF_RETURN_REQUEST_ORDER_STATUS") == $childOrderDetail['orderstatus_id']) {
                $processingStatuses[] = $childOrderDetail['orderstatus_id'];
                $processingStatuses = array_diff($processingStatuses, [FatApp::getConfig("CONF_DEFAULT_COMPLETED_ORDER_STATUS")]);
            }

            if (FatApp::getConfig("CONF_RETURN_REQUEST_APPROVED_ORDER_STATUS") == $childOrderDetail['orderstatus_id']) {
                $processingStatuses[] = $childOrderDetail['orderstatus_id'];
                $processingStatuses = array_diff($processingStatuses, [FatApp::getConfig("CONF_DEFAULT_COMPLETED_ORDER_STATUS")]);
            }

            $processingStatuses[] = $childOrderDetail['orderstatus_id'];

            $orderProductStatusArr = Orders::getOrderProductStatusArr($this->siteLangId, $processingStatuses);
        }

        $orderTimeLine = [];
        $currentStatus = Orders::ORDER_PAYMENT_PENDING == $orderDetail['order_payment_status'] ? Orders::ORDER_PAYMENT_PENDING : FatApp::getConfig("CONF_DEFAULT_PAID_ORDER_STATUS");
        $highlightEnabled = [];
        $cancellationComment = "";
        if (true === $primaryOrderDisplay && !empty($orderDetail['comments'])) {
            $currentStatus = current($orderDetail['comments'])['oshistory_orderstatus_id'];
            if (FatApp::getConfig("CONF_DEFAULT_CANCEL_ORDER_STATUS") == $childOrderDetail['orderstatus_id']) {
                $cancellationComment = current($orderDetail['comments'])['oshistory_comments'];
            }
            foreach ($orderDetail['comments'] as $comment) {
                $highlightEnabled[] = $comment['oshistory_orderstatus_id'];
                $orderTimeLine[$comment['oshistory_orderstatus_id']][] = $comment;
            }
        }

        if (Orders::ORDER_PAYMENT_PENDING == $orderDetail['order_payment_status'] && empty($orderTimeLine)) {
            $currentStatus = Orders::ORDER_PAYMENT_PENDING;
            $highlightEnabled[] = Orders::ORDER_PAYMENT_PENDING;
            $orderProductStatusArr = [Orders::ORDER_PAYMENT_PENDING => Labels::getLabel('LBL_PAYMENT_PENDING', $this->siteLangId)] + $orderProductStatusArr;
        }

        $cancelledDate = "";
        if (true == $primaryOrderDisplay && FatApp::getConfig("CONF_DEFAULT_CANCEL_ORDER_STATUS") == $childOrderDetail['orderstatus_id'] && !empty($orderTimeLine)) {
            $cancelledDate = current($orderTimeLine[$childOrderDetail['orderstatus_id']])['oshistory_date_added'];
        }

        $orderStatusArr = Orders::getOrderPaymentStatusArr($this->siteLangId);
        $arr = (true == $primaryOrderDisplay) ? [$childOrderDetail] : $childOrderDetail;
        $this->set('arr', $arr);
        $orderColorClasses =  OrderStatus::getOrderStatusColorClassArray();
        $frm = $this->getTransferBankForm($this->siteLangId, $orderId);
        $this->set('frm', $frm);
        $this->set('highlightEnabled', $highlightEnabled);
        $this->set('currentStatus', $currentStatus);
        $this->set('cancellationComment', $cancellationComment);
        $this->set('orderProductStatusArr', $orderProductStatusArr);
        $this->set('orderTimeLine', $orderTimeLine);
        $this->set('orderStatusArr', $orderStatusArr);
        $this->set('cancelledDate', $cancelledDate);
        $this->set('orderDetail', $orderDetail);
        $this->set('childOrderDetail', $childOrderDetail);
        $this->set('primaryOrder', $primaryOrderDisplay);
        $this->set('digitalDownloads', $digitalDownloads);
        $this->set('digitalDownloadLinks', $digitalDownloadLinks);
        $this->set('productType', $productType);
        $this->set('languages', Language::getAllNames());
        $this->set('yesNoArr', applicationConstants::getYesNoArr($this->siteLangId));
        $this->set('shippingApiObj', $shippingApiObj);
        $this->set('orderColorClasses', $orderColorClasses);

        $urlParts = array($orderId, $opId);
        $this->set('urlParts', $urlParts);
        $print = ($print !== false);

        $this->set('print', $print);

        $this->set('opId', $opId);

        $this->_template->render();
    }

    /**
     * downloadDigitalFiles : Used for APPs.
     *
     * @param  int $type
     * @param  int $opId
     * @return void
     */
    public function downloadDigitalFiles(int $type, int $opId)
    {
        if (1 > $type || 1 > $opId) {
            FatUtility::dieJsonError(Labels::getLabel('ERR_INVALID_REQUEST.', $this->siteLangId));
        }

        switch ($type) {
            case self::DIGITAL_FILES_ZIP:
                $this->downloadDigitalFilesZip($opId);
                break;
            case self::DIGITAL_LINKS_FILE:
                $this->downloadDigitalLinksFile($opId);
                break;

            default:
                FatUtility::dieJsonError(Labels::getLabel('ERR_INVALID_REQUEST_TYPE.', $this->siteLangId));
                break;
        }
    }

    public function downloadDigitalFilesZip(int $opId)
    {
        $srch = new OrderProductSearch($this->siteLangId, true);
        $srch->joinOrderUser();
        $srch->joinDigitalDownloads();
        $srch->addDigitalDownloadCondition();
        $srch->addMultipleFields([
            'op_invoice_number',
            'op_selprod_max_download_times',
            'opa.*'
        ]);

        $srch->addCondition('op_id', '=', $opId);
        $srch->addCondition('order_user_id', '=', UserAuthentication::getLoggedUserId());
        $srch->addDirectCondition("(
            CASE 
                WHEN 0 < op_selprod_max_download_times 
                    THEN op_selprod_max_download_times > afile_downloaded_times 
                WHEN 0 > op_selprod_max_download_times 
                    THEN TRUE
                ELSE FALSE 
            END)");

        $srch->addOrder('order_date_added', 'desc');
        $srch->addOrder('afile_id', 'asc');

        $downloads = (array) FatApp::getDb()->fetchAll($srch->getResultSet());
        if (empty($downloads)) {
            $msg = Labels::getLabel('LBL_LIMIT_REACHED/_FILE_NOT_FOUND_TO_DOWNLOAD.', $this->siteLangId);
            if (true === MOBILE_APP_API_CALL) {
                FatUtility::dieJsonError($msg);
            }
            Message::addErrorMessage($msg);
            FatApp::redirectUser(UrlHelper::generateUrl('Buyer', 'MyDownloads'));
        }

        # create new zip opbject
        $zip = new ZipArchive();

        # create a temp file & open it
        $tmp_file = tempnam(sys_get_temp_dir(), '');
        $zip->open($tmp_file, ZipArchive::OVERWRITE);

        $fileId = [];
        foreach ($downloads as $ind => $row) {
            $filePath = !empty($row['afile_physical_path']) ? CONF_UPLOADS_PATH . $row['afile_physical_path'] : '';
            if (file_exists($filePath)) {
                $fileId[] = $row['afile_id'];
                $zip->addFile($filePath, ($ind + 1) . '_' . basename($row['afile_name']));
            }
        }
        # close zip
        $zip->close();

        # send the file to the browser as a download
        header('Content-disposition: attachment; filename=' . $downloads[0]['op_invoice_number'] . '.zip');
        header('Content-type: application/zip');
        readfile($tmp_file);

        /* Remove Temp File from public folder. */
        unlink($tmp_file);
        /* Remove Temp File from public folder. */

        /* Update downlod count */
        if (!empty($fileId)) {
            FatApp::getDb()->query("UPDATE " . AttachedFile::DB_TBL . " SET afile_downloaded_times = (afile_downloaded_times+1) where afile_record_id = " . $opId . " AND afile_id IN (" . implode(',', $fileId) . ")");
        }
        /* Update downlod count */
        exit;
    }

    public function downloadDigitalFile($aFileId, $recordId = 0)
    {
        $aFileId = FatUtility::int($aFileId);
        $recordId = FatUtility::int($recordId);
        $userId = UserAuthentication::getLoggedUserId();

        if (1 > $aFileId || 1 > $recordId) {
            Message::addErrorMessage(Labels::getLabel('LBL_INVALID_REQUEST', $this->siteLangId));
            FatApp::redirectUser(UrlHelper::generateUrl('Buyer', 'MyDownloads'));
        }

        $digitalDownloads = Orders::getOrderProductDigitalDownloads($recordId, $aFileId);

        if ($digitalDownloads == false || empty($digitalDownloads) || $digitalDownloads[0]['order_user_id'] != $userId) {
            Message::addErrorMessage(Labels::getLabel("ERR_INVALID_ACCESS", $this->siteLangId));
            FatApp::redirectUser(UrlHelper::generateUrl('Buyer', 'MyDownloads'));
        }

        $res = array_shift($digitalDownloads);

        if ($res == false || !$res['downloadable']) {
            Message::addErrorMessage(Labels::getLabel("ERR_Not_available_to_download", $this->siteLangId));
            FatApp::redirectUser(UrlHelper::generateUrl('Buyer', 'MyDownloads'));
        }

        if (!file_exists(CONF_UPLOADS_PATH . $res['afile_physical_path'])) {
            Message::addErrorMessage(Labels::getLabel('LBL_FILE_NOT_FOUND', $this->siteLangId));
            FatApp::redirectUser(UrlHelper::generateUrl('Buyer', 'MyDownloads'));
        }

        $fileName = isset($res['afile_physical_path']) ? $res['afile_physical_path'] : '';
        AttachedFile::downloadAttachment($fileName, $res['afile_name']);
        AttachedFile::updateDownloadCount($res['afile_id']);
    }

    public function downloadDigitalLinksFile(int $opId)
    {
        $srch = new OrderProductSearch($this->siteLangId, true);
        $srch->joinOrderUser();
        $srch->joinDigitalDownloadLinks();
        $srch->addDigitalDownloadCondition();
        $srch->joinSellerProducts();
        $srch->joinTable(Product::DB_TBL, 'INNER JOIN', 'sp.selprod_product_id = p.product_id', 'p');

        $attr = [
            'op_invoice_number',
            'opd.*'
        ];

        $srch->addMultipleFields($attr);
        $srch->addCondition('op_id', '=', $opId);
        $srch->addCondition('order_user_id', '=', UserAuthentication::getLoggedUserId());

        $srch->addDirectCondition("(
            CASE 
                WHEN 0 < op_selprod_max_download_times 
                    THEN op_selprod_max_download_times > opddl_downloaded_times 
                WHEN 0 > op_selprod_max_download_times 
                    THEN TRUE
                ELSE FALSE 
            END)");

        $srch->addOrder('order_date_added', 'desc');
        $srch->addOrder('opddl_link_id', 'asc');

        $rs = $srch->getResultSet();
        $downloads = FatApp::getDb()->fetchAll($rs);
        if (empty($downloads)) {
            $msg = Labels::getLabel('LBL_LIMIT_REACHED/_FILE_NOT_FOUND_TO_DOWNLOAD', $this->siteLangId);
            if (true === MOBILE_APP_API_CALL) {
                FatUtility::dieJsonError($msg);
            }
            Message::addErrorMessage($msg);
            FatApp::redirectUser(UrlHelper::generateUrl('Buyer', 'MyDownloads'));
        }

        $handle = fopen('php://memory', 'w');

        $linkId = [];
        foreach ($downloads as $row) {
            if (!empty($row['opddl_downloadable_link'])) {
                $linkId[] = $row['opddl_link_id'];
                CommonHelper::writeExportDataToCSV($handle, [$row['opddl_downloadable_link']]);
            }
        }

        CommonHelper::writeExportDataToCSV($handle, [], true, $downloads[0]['op_invoice_number'] . '.csv');

        /* Update downlod count */
        FatApp::getDb()->query("UPDATE " . OrderProductDigitalLinks::DB_TBL . " SET opddl_downloaded_times = (opddl_downloaded_times+1) where opddl_op_id = " . $opId . " AND opddl_link_id IN (" . implode(',', $linkId) . ")");
        /* Update downlod count */
        exit;
    }

    public function downloadDigitalProductFromLink($linkId, $opId)
    {
        $linkId = FatUtility::int($linkId);
        $opId = FatUtility::int($opId);
        $userId = UserAuthentication::getLoggedUserId();

        if (1 > $linkId || 1 > $opId) {
            $message = Labels::getLabel('LBL_Invalid_Request', $this->siteLangId);
            LibHelper::dieJsonError($message);
        }

        $digitalDownloadLinks = Orders::getOrderProductDigitalDownloadLinks($opId, $linkId);
        if ($digitalDownloadLinks == false || empty($digitalDownloadLinks) || $digitalDownloadLinks[0]['order_user_id'] != $userId) {
            $message = Labels::getLabel("MSG_INVALID_ACCESS", $this->siteLangId);
            LibHelper::dieJsonError($message);
        }
        $res = array_shift($digitalDownloadLinks);
        if ($res == false || !$res['downloadable']) {
            $message = Labels::getLabel("MSG_Link_is_not_available_to_download", $this->siteLangId);
            LibHelper::dieJsonError($message);
        }
        OrderProductDigitalLinks::updateDownloadCount($linkId);
        if (true === MOBILE_APP_API_CALL) {
            $this->set('data', ['link' => trim($res['opddl_downloadable_link'])]);
            $this->_template->render();
        }
        $message = Labels::getLabel("MSG_Successfully_redirected", $this->siteLangId);
        FatUtility::dieJsonSuccess($message);
    }

    public function orders()
    {
        $frmSearch = $this->getOrderSearchForm($this->siteLangId);
        $this->set('frmSearch', $frmSearch);
        $this->set('keywordPlaceholder', Labels::getLabel('FRM_SEARCH_BY_ORDER_ID,_PRODUCT_NAME_OR_SHOP_NAME', $this->siteLangId));
        $this->_template->render(true, true);
    }

    public function orderSearchListing()
    {
        $frm = $this->getOrderSearchForm($this->siteLangId);
        $post = $frm->getFormDataFromArray(FatApp::getPostedData());
        $page = (empty($post['page']) || $post['page'] <= 0) ? 1 : FatUtility::int($post['page']);
        $pagesize = FatApp::getConfig('conf_page_size', FatUtility::VAR_INT, 10);

        $user_id = UserAuthentication::getLoggedUserId();

        $ocSrch = new SearchBase(OrderProduct::DB_TBL_CHARGES, 'opc');
        $ocSrch->doNotCalculateRecords();
        $ocSrch->doNotLimitRecords();
        $ocSrch->addMultipleFields(array('opcharge_op_id', 'sum(opcharge_amount) as op_other_charges'));
        $ocSrch->addGroupBy('opc.opcharge_op_id');
        $qryOtherCharges = $ocSrch->getQuery();

        $srch = new OrderProductSearch($this->siteLangId, true, true);
        $srch->addCountsOfOrderedProducts();
        $srch->joinShippingCharges();
        $srch->joinOrderProductSpecifics();
        $srch->joinTable('(' . $qryOtherCharges . ')', 'LEFT OUTER JOIN', 'op.op_id = opcc.opcharge_op_id', 'opcc');
        $srch->joinTable(
            OrderReturnRequest::DB_TBL,
            'LEFT OUTER JOIN',
            'orr.orrequest_op_id = op.op_id',
            'orr'
        );
        $srch->joinTable(
            OrderCancelRequest::DB_TBL,
            'LEFT OUTER JOIN',
            'ocr.ocrequest_op_id = op.op_id',
            'ocr'
        );
        $srch->joinSellerProducts();
        $srch->joinShop();

        $srch->addCondition('order_user_id', '=', $user_id);
        $srch->joinPaymentMethod();
        $keyword = FatApp::getPostedData('keyword', null, '');
        if (!empty($keyword)) {
            $srch->joinOrderUser();
            $srch->addKeywordSearch($keyword);
        }

        $op_status_id = FatApp::getPostedData('status', null, '0');
        if (in_array($op_status_id, unserialize(FatApp::getConfig("CONF_BUYER_ORDER_STATUS")))) {
            $srch->addStatusCondition($op_status_id, ($op_status_id == FatApp::getConfig("CONF_DEFAULT_CANCEL_ORDER_STATUS")));
        } else {
            $srch->addStatusCondition(unserialize(FatApp::getConfig("CONF_BUYER_ORDER_STATUS")), ($op_status_id == FatApp::getConfig("CONF_DEFAULT_CANCEL_ORDER_STATUS")));
        }

        $dateFrom = FatApp::getPostedData('date_from', null, '');
        if (!empty($dateFrom)) {
            $srch->addDateFromCondition($dateFrom);
        }

        $dateTo = FatApp::getPostedData('date_to', null, '');
        if (!empty($dateTo)) {
            $srch->addDateToCondition($dateTo);
        }

        $priceFrom = FatApp::getPostedData('price_from', null, '');
        if (!empty($priceFrom)) {
            $srch->addHaving('totOrders', '=', '1');
            $srch->addMinPriceCondition($priceFrom);
            $srch->addFld('totCombinedOrders as totOrders');
        }

        $priceTo = FatApp::getPostedData('price_to', null, '');
        if (!empty($priceTo)) {
            $srch->addHaving('totOrders', '=', '1');
            $srch->addMaxPriceCondition($priceTo);
            $srch->addFld('totCombinedOrders as totOrders');
        }
        $srch->addFld('order_id');
        $this->setRecordCount(clone $srch, $pagesize, $page, $post, true);
        $srch->doNotCalculateRecords();
        $srch->addOrder("op_id", "DESC");
        $srch->setPageNumber($page);
        $srch->setPageSize($pagesize);
        $srch->addMultipleFields(
            array(
                'order_number',
                'order_id',
                'order_user_id',
                'order_date_added',
                'order_net_amount',
                'op_invoice_number',
                'totCombinedOrders as totOrders',
                'op_selprod_id',
                'op_selprod_title',
                'op_product_name',
                'op_id',
                'op_other_charges',
                'op_unit_price',
                'op_qty',
                'op_selprod_options',
                'op_brand_name',
                'op_shop_name',
                'op_status_id',
                'op_product_type',
                'IFNULL(orderstatus_name, orderstatus_identifier) as orderstatus_name',
                'orderstatus_color_class',
                'order_pmethod_id',
                'order_status',
                'plugin_name',
                'IFNULL(orrequest_id, 0) as return_request',
                'IFNULL(ocrequest_id, 0) as cancel_request',
                'op_selprod_return_age',
                'op_selprod_cancellation_age',
                'order_payment_status',
                'order_deleted',
                'plugin_code',
                'opshipping_fulfillment_type',
                'op_rounding_off',
                'selprod_product_id',
                'orderstatus_id',
                'selprod_cart_type',
                'selprod_hide_price', 'shop_rfq_enabled'
            )
        );

        $orders = FatApp::getDb()->fetchAll($srch->getResultSet());
        $oObj = new Orders();
        foreach ($orders as &$order) {
            $charges = $oObj->getOrderProductChargesArr($order['op_id'], MOBILE_APP_API_CALL);
            $order['charges'] = $charges;
            $order['orderstatus_color_code'] = applicationConstants::getClassColor((int)$order['orderstatus_color_class']);
            $order['product_image_url'] = UrlHelper::generateFullUrl('image', 'product', array($order['selprod_product_id'] ?? 0, ImageDimension::VIEW_THUMB, $order['op_selprod_id'], 0, $this->siteLangId), CONF_WEBROOT_FRONTEND);
        }
        $this->set('orders', $orders);
        $this->set('postedData', $post);
        $this->set('classArr', applicationConstants::getClassArr());

        if (true === MOBILE_APP_API_CALL) {
            $orderStatuses = Orders::getOrderProductStatusArr($this->siteLangId, unserialize(FatApp::getConfig("CONF_BUYER_ORDER_STATUS")), 0, 0, false);
            $this->set('orderStatuses', $orderStatuses);
            $this->_template->render();
        }
        $this->_template->render(false, false);
    }

    public function MyDownloads()
    {
        $this->_template->render(true, true);
    }

    /**
     * downloads - Used For APPs.
     *
     * downloadSearch and downloadLinksSearch merged
     */
    public function downloads()
    {
        $frm = $this->getOrderProductDownloadSearchForm($this->siteLangId);

        $post = $frm->getFormDataFromArray(FatApp::getPostedData());
        if (false === $post) {
            FatUtility::dieJsonError(current($frm->getValidationErrors()));
        }

        $post = [];
        $page = FatApp::getPostedData('page', FatUtility::VAR_INT, 1);
        $page = $page <= 0 ? 1 : $page;

        $pagesize = FatApp::getConfig('conf_page_size', FatUtility::VAR_INT, 10);
        $user_id = UserAuthentication::getLoggedUserId();

        $srch = new OrderProductSearch($this->siteLangId, true);
        $srch->joinOrderUser();
        //$srch->joinDigitalDownloads(AttachedFile::FILETYPE_ORDER_PRODUCT_DIGITAL_DOWNLOAD, 'LEFT JOIN');
        //$srch->joinDigitalDownloadLinks('LEFT JOIN');
        $srch->addDigitalDownloadCondition();
        $srch->joinSellerProducts();
        $srch->joinTable(Product::DB_TBL, 'INNER JOIN', 'sp.selprod_product_id = p.product_id', 'p');
        $srch->addMultipleFields(array('op_id', 'op_invoice_number', 'order_user_id', 'op_product_type', 'order_date_added', 'op_qty', 'op_status_id', 'op_selprod_max_download_times', 'op_selprod_id', 'product_updated_on', 'selprod_product_id', 'op_selprod_download_validity_in_days', 'IFNULL(op_selprod_title, op_product_name) as selprod_title'));
        $srch->setPageNumber($page);
        $srch->addCondition('order_user_id', '=', $user_id);
        $srch->addOrder('order_date_added', 'desc');
        $srch->setPageSize($pagesize);
        $keyword = FatApp::getPostedData('keyword', null, '');
        if (!empty($keyword)) {
            $srch->addKeywordSearch($keyword);
            $frm->fill(array('keyword' => $keyword));
        }

        $rs = $srch->getResultSet();
        $orderProducts = FatApp::getDb()->fetchAll($rs);

        foreach ($orderProducts as &$op) {
            $uploadedTime = AttachedFile::setTimeParam($op['product_updated_on']);
            $op['product_image_url'] = UrlHelper::getCachedUrl(UrlHelper::generateFullFileUrl('image', 'product', array($op['selprod_product_id'], ImageDimension::VIEW_CLAYOUT3, $op['op_selprod_id'], 0, $this->siteLangId), CONF_WEBROOT_FRONTEND) . $uploadedTime, CONF_IMG_CACHE_TIME, '.jpg');

            $files = AttachedFile::getMultipleAttachments(AttachedFile::FILETYPE_ORDER_PRODUCT_DIGITAL_DOWNLOAD, $op['op_id'], 0, $this->siteLangId, true);
            foreach ($files as &$file) {
                $dateAvailable = date('Y-m-d', strtotime(date('Y-m-d') . '+ 1 year'));
                if ($op['op_selprod_download_validity_in_days'] != '-1') {
                    $dateAvailable = date('Y-m-d', strtotime($op['order_date_added'] . ' + ' . $op['op_selprod_download_validity_in_days'] . ' days'));
                }
                $file['expiry_date'] = $dateAvailable;

                $file['downloadable'] = true;
                if ($dateAvailable < date('Y-m-d')) {
                    $file['downloadable'] = false;
                }

                $file['downloadable_count'] = -1;
                if ($op['op_selprod_max_download_times'] != '-1') {
                    $file['downloadable_count'] = $op['op_selprod_max_download_times'];
                }

                if ($op['op_selprod_max_download_times'] != '-1') {
                    if ($file['afile_downloaded_times'] >= $op['op_selprod_max_download_times']) {
                        $file['downloadable'] = false;
                    }
                }

                $file['downloadUrl'] = UrlHelper::generateFullUrl('Buyer', 'downloadDigitalFile', array($file['afile_id'], $file['afile_record_id']));
            }

            $op['files'] = (true === MOBILE_APP_API_CALL) ? array_values($files) : $files;

            $linkSrch = new SearchBase(OrderProductDigitalLinks::DB_TBL);
            $linkSrch->addCondition("opddl_op_id", "=", $op['op_id']);
            $linkSrch->doNotCalculateRecords();
            $linkSrch->doNotLimitRecords();
            $links = FatApp::getDb()->fetchAll($linkSrch->getResultSet());

            foreach ($links as &$link) {
                $dateAvailable = date('Y-m-d', strtotime(date('Y-m-d') . '+ 1 year'));
                if ($op['op_selprod_download_validity_in_days'] != '-1') {
                    $dateAvailable = date('Y-m-d', strtotime($op['order_date_added'] . ' + ' . $op['op_selprod_download_validity_in_days'] . ' days'));
                }

                $link['expiry_date'] = $dateAvailable;

                $link['downloadable'] = true;
                if ($dateAvailable < date('Y-m-d')) {
                    $link['downloadable'] = false;
                }

                $link['downloadable_count'] = -1;
                if ($op['op_selprod_max_download_times'] != '-1') {
                    $link['downloadable_count'] = $op['op_selprod_max_download_times'];
                }

                if ($op['op_selprod_max_download_times'] != '-1') {
                    if ($link['opddl_downloaded_times'] >= $op['op_selprod_max_download_times']) {
                        $link['downloadable'] = false;
                    }
                }
            }
            $op['links'] = $links;
        }

        $this->set('downloads', $orderProducts);
        $this->set('page', $page);
        $this->set('pageCount', $srch->pages());
        $this->set('recordCount', $srch->recordCount());
        $this->set('postedData', $post);
        $this->set('languages', Language::getAllNames());
        $this->_template->render();
    }

    public function downloadSearch()
    {
        $frm = $this->getOrderProductDownloadSearchForm($this->siteLangId);
        $post = $frm->getFormDataFromArray(FatApp::getPostedData());
        $page = (empty($post['page']) || $post['page'] <= 0) ? 1 : FatUtility::int($post['page']);
        $pagesize = FatApp::getConfig('conf_page_size', FatUtility::VAR_INT, 10);
        $opId = FatApp::getPostedData('op_id', FatUtility::VAR_INT, 0);

        $user_id = UserAuthentication::getLoggedUserId();

        $srch = new OrderProductSearch($this->siteLangId, true);
        $srch->joinOrderUser();
        $srch->joinDigitalDownloads();
        $srch->addDigitalDownloadCondition();

        if (0 < $opId) {
            $srch->addCondition('op_id', '=', $opId);
            $frm->fill(array('op_id' => $opId));
        } else {
            $srch->addGroupBy('op_invoice_number');
        }

        $keyword = FatApp::getPostedData('keyword', null, '');
        if (!empty($keyword)) {
            $srch->addKeywordSearch($keyword);
            $frm->fill(array('keyword' => $keyword));
        }

        if (true === MOBILE_APP_API_CALL) {
            $srch->joinSellerProducts($this->siteLangId);
            $srch->addFld(array('selprod_product_id'));
        }

        $srch->addCondition('order_user_id', '=', $user_id);
        $this->setRecordCount(clone $srch, $pagesize, $page, $post, true);
        $srch->doNotCalculateRecords();

        $attr = [
            'op_id',
            'op_selprod_id',
            'op_invoice_number',
            'order_user_id',
            'op_product_type',
            'order_date_added',
            'op_qty',
            'op_status_id',
            'op_selprod_max_download_times',
            'op_selprod_download_validity_in_days',
        ];
        if (1 > $opId) {
            $attr[] = 'COUNT(op_id) as filesCount';
        } else {
            $attr[] = 'opa.*';
        }

        $srch->addMultipleFields($attr);
        $srch->addOrder('order_date_added', 'desc');
        $srch->addOrder('afile_id', 'asc');
        $srch->setPageNumber($page);
        $srch->setPageSize($pagesize);
        $downloads = FatApp::getDb()->fetchAll($srch->getResultSet());
        $digitalDownloads = Orders::digitalDownloadFormat($downloads);
        $this->set('opId', $opId);
        $this->set('frmSrch', $frm);
        $this->set('digitalDownloads', $digitalDownloads);
        $this->set('postedData', $post);
        $this->set('languages', Language::getAllNames());

        if (true === MOBILE_APP_API_CALL) {
            $this->_template->render();
        }

        $this->_template->render(false, false);
    }

    public function downloadLinksSearch()
    {
        $frm = $this->getOrderProductDownloadSearchForm($this->siteLangId);
        $post = $frm->getFormDataFromArray(FatApp::getPostedData());
        $page = (empty($post['page']) || $post['page'] <= 0) ? 1 : FatUtility::int($post['page']);
        $pagesize = FatApp::getConfig('conf_page_size', FatUtility::VAR_INT, 10);
        $user_id = UserAuthentication::getLoggedUserId();
        $opId = FatApp::getPostedData('op_id', FatUtility::VAR_INT, 0);

        $srch = new OrderProductSearch($this->siteLangId, true);
        $srch->joinOrderUser();
        $srch->joinDigitalDownloadLinks();
        $srch->addDigitalDownloadCondition();
        $srch->joinSellerProducts();
        $srch->joinTable(Product::DB_TBL, 'INNER JOIN', 'sp.selprod_product_id = p.product_id', 'p');
        $srch->addCondition('order_user_id', '=', $user_id);
        $keyword = FatApp::getPostedData('keyword', null, '');
        if (!empty($keyword)) {
            $srch->addKeywordSearch($keyword);
            $frm->fill(array('keyword' => $keyword));
        }

        if (0 < $opId) {
            $srch->addCondition('op_id', '=', $opId);
            $frm->fill(array('op_id' => $opId));
        } else {
            $srch->addGroupBy('op_invoice_number');
        }

        $this->setRecordCount(clone $srch, $pagesize, $page, $post, true);
        $srch->doNotCalculateRecords();

        $srch->addOrder('order_date_added', 'desc');
        $srch->addOrder('opddl_link_id', 'asc');
        $srch->setPageSize($pagesize);
        $srch->setPageNumber($page);
        $attr = [
            'op_id',
            'op_invoice_number',
            'order_user_id',
            'op_product_type',
            'order_date_added',
            'op_qty',
            'op_status_id',
            'op_selprod_max_download_times',
            'op_selprod_id',
            'product_updated_on',
            'selprod_product_id',
            'op_selprod_download_validity_in_days'
        ];

        if (1 > $opId) {
            $attr[] = 'COUNT(op_id) as linksCount';
        } else {
            $attr[] = 'opd.*';
        }

        $srch->addMultipleFields($attr);
        $downloads = FatApp::getDb()->fetchAll($srch->getResultSet());
        $digitalDownloadLinks = Orders::digitalDownloadLinksFormat($downloads);
        $this->set('opId', $opId);
        $this->set('frmSrch', $frm);
        $this->set('digitalDownloadLinks', $digitalDownloadLinks);
        $this->set('postedData', $post);
        $this->set('languages', Language::getAllNames());
        if (true === MOBILE_APP_API_CALL) {
            $this->_template->render();
        }

        $this->_template->render(false, false);
    }

    public function orderCancellationRequest($op_id)
    {
        $op_id = FatUtility::int($op_id);

        $user_id = UserAuthentication::getLoggedUserId();
        $srch = new OrderProductSearch($this->siteLangId, true);
        $srch->addStatusCondition(unserialize(FatApp::getConfig("CONF_BUYER_ORDER_STATUS")));
        $srch->addCondition('order_user_id', '=', $user_id);
        $srch->addCondition('op_id', '=', $op_id);
        $srch->addOrder("op_id", "DESC");
        $srch->addMultipleFields(array('op_status_id', 'op_id', 'op_product_type'));
        $rs = $srch->getResultSet();
        $opDetail = FatApp::getDb()->fetch($rs);
        if (!$opDetail || CommonHelper::isMultidimArray($opDetail)) {
            Message::addErrorMessage(Labels::getLabel('ERR_ERROR_INVALID_ACCESS', $this->siteLangId));
            // CommonHelper::redirectUserReferer();
            FatApp::redirectUser(UrlHelper::generateUrl('Buyer', 'orderCancellationRequests'));
        }

        $oReturnRequestSrch = new OrderReturnRequestSearch();
        $oReturnRequestSrch->doNotCalculateRecords();
        $oReturnRequestSrch->doNotLimitRecords();
        $oReturnRequestSrch->addCondition('orrequest_op_id', '=', $opDetail['op_id']);
        $oReturnRequestSrch->addCondition('orrequest_status', '!=', OrderReturnRequest::RETURN_REQUEST_STATUS_CANCELLED);
        $oReturnRequestRs = $oReturnRequestSrch->getResultSet();

        if (FatApp::getDb()->fetch($oReturnRequestRs)) {
            Message::addErrorMessage(Labels::getLabel('ERR_ALREADY_SUBMITTED_RETURN_REQUEST', $this->siteLangId));
            // CommonHelper::redirectUserReferer();
            FatApp::redirectUser(UrlHelper::generateUrl('Buyer', 'orderCancellationRequests'));
        }

        if ($opDetail["op_product_type"] == Product::PRODUCT_TYPE_DIGITAL) {
            if (!in_array($opDetail["op_status_id"], (array) Orders::getBuyerAllowedOrderCancellationStatuses(true))) {
                Message::addErrorMessage(Labels::getLabel('ERR_ORDER_CANCELLATION_CANNOT_PLACED', $this->siteLangId));
                // CommonHelper::redirectUserReferer();
                FatApp::redirectUser(UrlHelper::generateUrl('Buyer', 'orderCancellationRequests'));
            }
        } else {
            if (!in_array($opDetail["op_status_id"], (array) Orders::getBuyerAllowedOrderCancellationStatuses())) {
                Message::addErrorMessage(Labels::getLabel('ERR_ORDER_CANCELLATION_CANNOT_PLACED', $this->siteLangId));
                // CommonHelper::redirectUserReferer();
                FatApp::redirectUser(UrlHelper::generateUrl('Buyer', 'orderCancellationRequests'));
            }
        }

        if (false !== OrderCancelRequest::getCancelRequestById($opDetail['op_id'])) {
            Message::addErrorMessage(Labels::getLabel('ERR_YOU_HAVE_ALREADY_SENT_THE_CANCELLATION_REQUEST_FOR_THIS_ORDER', $this->siteLangId));
            // CommonHelper::redirectUserReferer();
            FatApp::redirectUser(UrlHelper::generateUrl('Buyer', 'orderCancellationRequests'));
        }

        $frm = $this->getOrderCancelRequestForm($this->siteLangId);
        $frm->fill(array('op_id' => $opDetail['op_id']));
        $this->set('frmOrderCancel', $frm);
        $this->_template->render(true, true);
    }

    public function orderCancellationReasons()
    {
        $orderCancelReasonsArr = OrderCancelReason::getOrderCancelReasonArr($this->siteLangId);
        $count = 0;
        foreach ($orderCancelReasonsArr as $key => $val) {
            $cancelReasonsArr[$count]['key'] = $key;
            $cancelReasonsArr[$count]['value'] = $val;
            $count++;
        }
        $this->set('data', array('reasons' => $cancelReasonsArr));
        $this->_template->render();
    }

    public function orderReturnRequestsReasons($op_id)
    {
        if (1 > FatUtility::int($op_id)) {
            FatUtility::dieJsonError(Labels::getLabel('ERR_INVALID_REQUEST', $this->siteLangId));
        }
        $user_id = UserAuthentication::getLoggedUserId();
        $orderReturnReasonsArr = OrderReturnReason::getOrderReturnReasonArr($this->siteLangId);
        $count = 0;
        foreach ($orderReturnReasonsArr as $key => $val) {
            $returnReasonsArr[$count]['key'] = $key;
            $returnReasonsArr[$count]['value'] = $val;
            $count++;
        }
        $srch = new OrderProductSearch($this->siteLangId, true);
        $srch->addStatusCondition(unserialize(FatApp::getConfig("CONF_BUYER_ORDER_STATUS")));
        $srch->addCondition('order_user_id', '=', $user_id);
        $srch->addCondition('op_id', '=', $op_id);
        $srch->addOrder("op_id", "DESC");
        $srch->addMultipleFields(array('op_status_id', 'op_id', 'op_qty', 'op_product_type'));
        $rs = $srch->getResultSet();
        $opDetail = FatApp::getDb()->fetch($rs);
        if (!$opDetail || CommonHelper::isMultidimArray($opDetail)) {
            FatUtility::dieJsonError(Labels::getLabel('ERR_INVALID_REQUEST', $this->siteLangId));
        }

        $this->set('data', array('reasons' => $returnReasonsArr));
        $this->_template->render();
    }

    public function setupOrderCancelRequest()
    {
        $frm = $this->getOrderCancelRequestForm($this->siteLangId);
        $post = $frm->getFormDataFromArray(FatApp::getPostedData());
        if (false === $post) {
            if (true === MOBILE_APP_API_CALL) {
                LibHelper::dieJsonError(current($frm->getValidationErrors()));
            }
            Message::addErrorMessage(current($frm->getValidationErrors()));
            FatUtility::dieWithError(Message::getHtml());
        }
        $op_id = FatUtility::int($post['op_id']);

        $user_id = UserAuthentication::getLoggedUserId();
        $srch = new OrderProductSearch($this->siteLangId, true);
        $srch->joinOrderProductSpecifics();
        $srch->addStatusCondition(unserialize(FatApp::getConfig("CONF_BUYER_ORDER_STATUS")));
        $srch->addCondition('order_user_id', '=', $user_id);
        $srch->addCondition('op_id', '=', $op_id);
        $srch->addOrder("op_id", "DESC");
        $opDetail = FatApp::getDb()->fetch($srch->getResultSet());
        if (!$opDetail || CommonHelper::isMultidimArray($opDetail)) {
            $message = Labels::getLabel('MSG_ERROR_INVALID_ACCESS', $this->siteLangId);
            if (true === MOBILE_APP_API_CALL) {
                LibHelper::dieJsonError($message);
            }
            Message::addErrorMessage($message);
            FatUtility::dieWithError(Message::getHtml());
        }

        $datediff = time() - strtotime($opDetail['order_date_added']);
        $daysSpent = $datediff / (60 * 60 * 24);

        if ($opDetail["op_product_type"] == Product::PRODUCT_TYPE_PHYSICAL && $opDetail['op_selprod_cancellation_age'] <= $daysSpent) {
            $message = Labels::getLabel('MSG_ERROR_INVALID_ACCESS', $this->siteLangId);
            LibHelper::dieJsonError($message);
        }

        if ($opDetail["op_product_type"] == Product::PRODUCT_TYPE_DIGITAL) {
            if (!in_array($opDetail["op_status_id"], (array) Orders::getBuyerAllowedOrderCancellationStatuses(true))) {
                $message = Labels::getLabel('MSG_Order_Cancellation_cannot_placed', $this->siteLangId);
                if (true === MOBILE_APP_API_CALL) {
                    LibHelper::dieJsonError($message);
                }
                Message::addErrorMessage($message);
                FatUtility::dieWithError(Message::getHtml());
            }
        } else {
            if (!in_array($opDetail["op_status_id"], (array) Orders::getBuyerAllowedOrderCancellationStatuses())) {
                $message = Labels::getLabel('MSG_Order_Cancellation_cannot_placed', $this->siteLangId);
                if (true === MOBILE_APP_API_CALL) {
                    LibHelper::dieJsonError($message);
                }
                Message::addErrorMessage($message);
                FatUtility::dieWithError(Message::getHtml());
            }
        }

        $ocRequestSrch = new OrderCancelRequestSearch();
        $ocRequestSrch->doNotCalculateRecords();
        $ocRequestSrch->doNotLimitRecords();
        $ocRequestSrch->addCondition('ocrequest_op_id', '=', $opDetail['op_id']);
        $ocRequestRs = $ocRequestSrch->getResultSet();
        if (FatApp::getDb()->fetch($ocRequestRs)) {
            $message = Labels::getLabel('MSG_You_have_already_sent_the_cancellation_request_for_this_order', $this->siteLangId);
            if (true === MOBILE_APP_API_CALL) {
                LibHelper::dieJsonError($message);
            }
            Message::addErrorMessage($message);
            FatUtility::dieWithError(Message::getHtml());
        }

        $dataToSave = array(
            'ocrequest_user_id' => $user_id,
            'ocrequest_op_id' => $opDetail['op_id'],
            'ocrequest_ocreason_id' => FatUtility::int($post['ocrequest_ocreason_id']),
            'ocrequest_message' => $post['ocrequest_message'],
            'ocrequest_date' => date('Y-m-d H:i:s'),
            'ocrequest_status' => OrderCancelRequest::CANCELLATION_REQUEST_STATUS_PENDING
        );

        $oCRequestObj = new OrderCancelRequest();
        $oCRequestObj->assignValues($dataToSave);

        if (!$oCRequestObj->save()) {
            Message::addErrorMessage($oCRequestObj->getError());
            FatUtility::dieWithError(Message::getHtml());
        }
        $ocrequest_id = $oCRequestObj->getMainTableRecordId();
        if (!$ocrequest_id) {
            $message = Labels::getLabel('MSG_Something_went_wrong,_please_contact_admin', $this->siteLangId);
            if (true === MOBILE_APP_API_CALL) {
                LibHelper::dieJsonError($message);
            }
            Message::addErrorMessage($message);
            FatUtility::dieWithError(Message::getHtml());
        }

        $emailObj = new EmailHandler();
        if (!$emailObj->sendOrderCancellationNotification($ocrequest_id, $this->siteLangId)) {
            if (true === MOBILE_APP_API_CALL) {
                LibHelper::dieJsonError($emailObj->getError());
            }
            Message::addErrorMessage($emailObj->getError());
            FatUtility::dieWithError(Message::getHtml());
        }

        CalculativeDataRecord::updateOrderCancelRequestCount();

        /* send notification to admin */
        $notificationData = array(
            'notification_record_type' => Notification::TYPE_ORDER_CANCELATION,
            'notification_record_id' => $oCRequestObj->getMainTableRecordId(),
            'notification_user_id' => $user_id,
            'notification_label_key' => Notification::ORDER_CANCELLATION_NOTIFICATION,
            'notification_added_on' => date('Y-m-d H:i:s'),
        );

        if (!Notification::saveNotifications($notificationData)) {
            $message = Labels::getLabel('MSG_NOTIFICATION_COULD_NOT_BE_SENT', $this->siteLangId);
            if (true === MOBILE_APP_API_CALL) {
                LibHelper::dieJsonError($emailObj->getError());
            }
            Message::addErrorMessage($message);
            FatUtility::dieWithError(Message::getHtml());
        }

        $msg = Labels::getLabel('MSG_Your_cancellation_request_submitted', $this->siteLangId);
        if (true === MOBILE_APP_API_CALL) {
            $this->set('msg', $msg);
            $this->_template->render();
        }

        FatUtility::dieJsonSuccess($msg);
        //$this->_template->render( false, false, 'json-success.php' );
    }

    public function orderCancellationRequests()
    {
        $frm = $this->getOrderCancellationRequestsSearchForm($this->siteLangId);
        $this->set('frmSearch', $frm);
        $this->_template->render(true, true);
    }

    public function orderCancellationRequestSearch()
    {
        $frm = $this->getOrderCancellationRequestsSearchForm($this->siteLangId);
        $post = $frm->getFormDataFromArray(FatApp::getPostedData());
        $page = (empty($post['page']) || $post['page'] <= 0) ? 1 : FatUtility::int($post['page']);
        $pagesize = FatApp::getConfig('conf_page_size', FatUtility::VAR_INT, 10);
        $user_id = UserAuthentication::getLoggedUserId();

        $srch = $this->orderCancellationRequestObj();
        if (true === MOBILE_APP_API_CALL) {
            $srch->joinTable(SellerProduct::DB_TBL, 'INNER JOIN', 'selprod_id = op_selprod_id');
            $srch->joinTable(SellerProduct::DB_TBL_LANG, 'INNER JOIN', 'selprod_id = selprodlang_selprod_id AND selprodlang_lang_id = ' . $this->siteLangId);
            $srch->addFld(array('selprod_product_id', 'selprod_title'));
        }

        $op_invoice_number = $post['op_invoice_number'];
        if (!empty($op_invoice_number)) {
            $srch->addCondition('op_invoice_number', '=', $op_invoice_number);
        }

        $ocrequest_date_from = $post['ocrequest_date_from'];
        if (!empty($ocrequest_date_from)) {
            $srch->addCondition('ocrequest_date', '>=', $ocrequest_date_from . ' 00:00:00');
        }

        $ocrequest_date_to = $post['ocrequest_date_to'];
        if (!empty($ocrequest_date_to)) {
            $srch->addCondition('ocrequest_date', '<=', $ocrequest_date_to . ' 23:59:59');
        }

        $ocrequest_status = FatApp::getPostedData('ocrequest_status', null, '-1');
        if ($ocrequest_status > -1) {
            $ocrequest_status = FatUtility::int($ocrequest_status);
            $srch->addCondition('ocrequest_status', '=', $ocrequest_status);
        }
        $this->setRecordCount(clone $srch, $pagesize, $page, $post);
        $srch->doNotCalculateRecords();
        $srch->addMultipleFields(array('ocrequest_id', 'ocrequest_date', 'ocrequest_status', 'order_id', 'order_number', 'op_invoice_number', 'IFNULL(ocreason_title, ocreason_identifier) as ocreason_title', 'ocrequest_message', 'op_id', 'op_is_batch', 'op_selprod_id', 'op_selprod_title'));
        $srch->addOrder('ocrequest_date', 'DESC');
        $srch->setPageNumber($page);
        $srch->setPageSize($pagesize);
        $requests = FatApp::getDb()->fetchAll($srch->getResultSet());
        $this->set('requests', $requests);
        $this->set('postedData', $post);
        $this->set('OrderCancelRequestStatusArr', OrderCancelRequest::getRequestStatusArr($this->siteLangId));
        $this->set('cancelReqStatusClassArr', OrderCancelRequest::getStatusClassArr());
        if (true === MOBILE_APP_API_CALL) {
            $this->_template->render();
        }
        $this->_template->render(false, false);
    }

    private function orderCancellationRequestObj()
    {
        $srch = new OrderCancelRequestSearch($this->siteLangId);
        $srch->joinOrderProducts();
        $srch->joinOrderCancelReasons();
        $srch->joinOrders();
        $srch->addCondition('ocrequest_user_id', '=', UserAuthentication::getLoggedUserId());
        return $srch;
    }

    public function orderReturnRequests()
    {
        $frm = $this->getOrderReturnRequestsSearchForm($this->siteLangId);
        $this->set('frmSearch', $frm);
        $this->set('keywordPlaceholder', Labels::getLabel('LBL_SEARCH_BY_ORDER_INVOICE_NUMBER,_PRODUCT_NAME_OR_BRAND_NAME', $this->siteLangId));
        $this->_template->render(true, true);
    }

    public function orderReturnRequestSearch()
    {
        $frm = $this->getOrderReturnRequestsSearchForm($this->siteLangId);
        $post = $frm->getFormDataFromArray(FatApp::getPostedData());
        $page = (empty($post['page']) || $post['page'] <= 0) ? 1 : FatUtility::int($post['page']);
        $pagesize = FatApp::getConfig('conf_page_size', FatUtility::VAR_INT, 10);

        $srch = $this->orderReturnRequestObj();
        $srch->joinTable(SellerProduct::DB_TBL, 'INNER JOIN', 'selprod_id = op_selprod_id');
        $srch->addFld(array('selprod_product_id'));
        if (true === MOBILE_APP_API_CALL) {
            $srch->joinTable(OrderReturnReason::DB_TBL, 'LEFT JOIN', 'orrequest_returnreason_id = orreason_id');
            $srch->joinTable(OrderReturnReason::DB_TBL_LANG, 'LEFT JOIN', 'orreasonlang_orreason_id = orreason_id AND orreasonlang_lang_id  = ' . $this->siteLangId);

            $srch->joinTable(SellerProduct::DB_TBL_LANG, 'INNER JOIN', 'selprod_id = selprodlang_selprod_id AND selprodlang_lang_id = ' . $this->siteLangId);
            $srch->addFld(array('selprod_title', 'IFNULL(orreason_title, orreason_identifier) as requestReason'));
        }
        $keyword = $post['keyword'];
        if (!empty($keyword)) {
            $cnd = $srch->addCondition('op_invoice_number', '=', $keyword);
            $cnd->attachCondition('op_selprod_title', 'LIKE', '%' . $keyword . '%', 'OR');
            $cnd->attachCondition('op_product_name', 'LIKE', '%' . $keyword . '%', 'OR');
            $cnd->attachCondition('op_brand_name', 'LIKE', '%' . $keyword . '%', 'OR');
            $cnd->attachCondition('op_selprod_options', 'LIKE', '%' . $keyword . '%', 'OR');
            $cnd->attachCondition('op_selprod_sku', 'LIKE', '%' . $keyword . '%', 'OR');
            $cnd->attachCondition('op_product_model', 'LIKE', '%' . $keyword . '%', 'OR');
            $cnd->attachCondition('orrequest_reference', 'LIKE', '%' . $keyword . '%', 'OR');
        }

        $orrequest_status = FatApp::getPostedData('orrequest_status', null, '-1');
        if ($orrequest_status > -1) {
            $orrequest_status = FatUtility::int($orrequest_status);
            $srch->addCondition('orrequest_status', '=', $orrequest_status);
        }

        $orrequest_date_from = $post['orrequest_date_from'];
        if (!empty($orrequest_date_from)) {
            $srch->addCondition('orrequest_date', '>=', $orrequest_date_from . ' 00:00:00');
        }

        $orrequest_date_to = $post['orrequest_date_to'];
        if (!empty($orrequest_date_to)) {
            $srch->addCondition('orrequest_date', '<=', $orrequest_date_to . ' 23:59:59');
        }

        $this->setRecordCount(clone $srch, $pagesize, $page, $post);
        $srch->doNotCalculateRecords();
        $srch->addOrder('orrequest_date', 'DESC');
        $srch->setPageNumber($page);
        $srch->setPageSize($pagesize);
        $srch->addMultipleFields(
            array(
                'orrequest_id',
                'orrequest_user_id',
                'orrequest_qty',
                'orrequest_type',
                'orrequest_reference',
                'orrequest_date',
                'orrequest_status',
                'op_invoice_number',
                'op_selprod_title',
                'op_product_name',
                'op_brand_name',
                'op_selprod_options',
                'op_selprod_sku',
                'op_product_model',
            )
        );
        $requests = FatApp::getDb()->fetchAll($srch->getResultSet());
        $this->set('sellerPage', false);
        $this->set('buyerPage', true);
        $this->set('requests', $requests);
        $this->set('postedData', $post);
        $this->set('returnRequestTypeArr', OrderReturnRequest::getRequestTypeArr($this->siteLangId));
        $this->set('OrderReturnRequestStatusArr', OrderReturnRequest::getRequestStatusArr($this->siteLangId));
        $this->set('OrderRetReqStatusClassArr', OrderReturnRequest::getRequestStatusClassArr());
        if (true === MOBILE_APP_API_CALL) {
            $this->_template->render();
        }
        $this->_template->render(false, false);
    }

    public function orderReturnRequestObj()
    {
        $srch = new OrderReturnRequestSearch($this->siteLangId);
        $srch->joinOrderProducts();
        $srch->joinOrders();
        $srch->addCondition('orrequest_user_id', '=', UserAuthentication::getLoggedUserId());
        $srch->addMultipleFields(
            array(
                'orrequest_id',
                'orrequest_user_id',
                'orrequest_qty',
                'orrequest_type',
                'orrequest_reference',
                'orrequest_date',
                'orrequest_status',
                'op_invoice_number',
                'op_selprod_title',
                'op_product_name',
                'op_brand_name',
                'op_selprod_options',
                'op_selprod_sku',
                'op_product_model',
                'op_id',
                'op_is_batch',
                'op_selprod_id',
                'order_id',
                'order_number'
            )
        );
        $srch->addOrder('orrequest_date', 'DESC');
        return $srch;
    }

    public function viewOrderReturnRequest($orrequest_id, $print = false)
    {
        $orrequest_id = FatUtility::int($orrequest_id);
        $user_id = UserAuthentication::getLoggedUserId();

        $srch = new OrderReturnRequestSearch($this->siteLangId);
        $srch->addCondition('orrequest_id', '=', $orrequest_id);
        $srch->addCondition('orrequest_user_id', '=', $user_id);
        $srch->joinOrderProducts();
        $srch->joinOrderProductSettings();
        $srch->joinOrders();
        $srch->joinShippingCharges();
        $srch->joinSellerProducts();
        $srch->joinOrderReturnReasons();
        $srch->addOrderProductCharges();
        $srch->doNotCalculateRecords();
        $srch->doNotLimitRecords();
        $srch->addMultipleFields(
            array(
                'orrequest_id',
                'orrequest_op_id',
                'orrequest_user_id',
                'orrequest_qty',
                'orrequest_type',
                'orrequest_date',
                'orrequest_status',
                'orrequest_reference',
                'op_invoice_number',
                'op_selprod_title',
                'op_product_name',
                'op_brand_name',
                'op_selprod_options',
                'op_selprod_sku',
                'op_product_model',
                'op_qty',
                'op_unit_price',
                'op_selprod_user_id',
                'IFNULL(orreason_title, orreason_identifier) as orreason_title',
                'op_shop_id',
                'op_shop_name',
                'op_shop_owner_name',
                'order_tax_charged',
                'op_other_charges',
                'op_refund_amount',
                'op_commission_percentage',
                'op_affiliate_commission_percentage',
                'op_commission_include_tax',
                'op_commission_include_shipping',
                'op_free_ship_upto',
                'op_actual_shipping_charges',
                'op_rounding_off',
                'op_selprod_id',
                'selprod_product_id',
                'opshipping_by_seller_user_id',
                'op_tax_after_discount'
            )
        );
        $rs = $srch->getResultSet();
        $request = FatApp::getDb()->fetch($rs);
        if (!$request) {
            $message = Labels::getLabel('MSG_Invalid_Access', $this->siteLangId);
            if (true === MOBILE_APP_API_CALL) {
                LibHelper::dieJsonError($message);
            }
            Message::addErrorMessage($message);
            FatApp::redirectUser(UrlHelper::generateUrl('Buyer', 'orderReturnRequests'));
        }

        $oObj = new Orders();
        $charges = $oObj->getOrderProductChargesArr($request['orrequest_op_id']);
        $request['charges'] = $charges;

        $sellerUserObj = new User($request['op_selprod_user_id']);
        $vendorReturnAddress = $sellerUserObj->getUserReturnAddress($this->siteLangId);

        $returnRequestMsgsSrchForm = $this->getOrderReturnRequestMessageSearchForm($this->siteLangId);
        $returnRequestMsgsSrchForm->fill(array('orrequest_id' => $request['orrequest_id']));

        $frm = $this->getOrderReturnRequestMessageForm($this->siteLangId);
        $frm->fill(array('orrmsg_orrequest_id' => $request['orrequest_id']));
        $this->set('frmMsg', $frm);

        $canEscalateRequest = false;
        $canWithdrawRequest = false;
        /* if( $request['orrequest_status'] == OrderReturnRequest::RETURN_REQUEST_STATUS_PENDING ){
          $canEscalateRequest = true;
          } */

        if (($request['orrequest_status'] == OrderReturnRequest::RETURN_REQUEST_STATUS_PENDING) || $request['orrequest_status'] == OrderReturnRequest::RETURN_REQUEST_STATUS_ESCALATED) {
            $canWithdrawRequest = true;
        }

        $attachedFile = AttachedFile::getAttachment(AttachedFile::FILETYPE_BUYER_RETURN_PRODUCT, $orrequest_id);
        if (0 < $attachedFile['afile_id']) {
            $this->set('attachedFile', $attachedFile);
        }
        $this->set('canEscalateRequest', $canEscalateRequest);
        $this->set('canWithdrawRequest', $canWithdrawRequest);
        $this->set('returnRequestMsgsSrchForm', $returnRequestMsgsSrchForm);
        $this->set('request', $request);
        $this->set('vendorReturnAddress', $vendorReturnAddress);
        $this->set('returnRequestTypeArr', OrderReturnRequest::getRequestTypeArr($this->siteLangId));
        $this->set('requestRequestStatusArr', OrderReturnRequest::getRequestStatusArr($this->siteLangId));
        $this->set('logged_user_name', UserAuthentication::getLoggedUserAttribute('user_name'));
        $this->set('logged_user_id', UserAuthentication::getLoggedUserId());

        if ($print) {
            $print = true;
        }
        $this->set('print', $print);
        $urlParts = array_filter(FatApp::getParameters());
        $this->set('urlParts', $urlParts);

        $this->_template->render();
    }

    public function downloadAttachedFileForReturn($recordId, $recordSubid = 0)
    {
        $recordId = FatUtility::int($recordId);

        if (1 > $recordId) {
            Message::addErrorMessage($this->str_invalid_request);
            if (!FatUtility::isAjaxCall()) {
                CommonHelper::redirectUserReferer();
            }
            FatUtility::dieWithError(Message::getHtml());
        }

        $file_row = AttachedFile::getAttachment(AttachedFile::FILETYPE_BUYER_RETURN_PRODUCT, $recordId, $recordSubid);
        if (false == $file_row || 1 > $file_row['afile_id']) {
            Message::addErrorMessage(Labels::getLabel('ERR_FILE_NOT_FOUND', $this->siteLangId));
            if (!FatUtility::isAjaxCall()) {
                CommonHelper::redirectUserReferer();
            }
            FatUtility::dieWithError(Message::getHtml());
        }

        $fileName = isset($file_row['afile_physical_path']) ? $file_row['afile_physical_path'] : '';
        AttachedFile::downloadAttachment($fileName, $file_row['afile_name']);
    }

    public function WithdrawOrderReturnRequest($orrequest_id)
    {
        $orrequest_id = FatUtility::int($orrequest_id);
        $user_id = UserAuthentication::getLoggedUserId();

        $srch = new OrderReturnRequestSearch($this->siteLangId);
        $srch->joinOrderProducts();
        $srch->joinOrders();
        $srch->joinSellerProducts();
        $srch->joinOrderReturnReasons();

        $srch->addCondition('orrequest_id', '=', $orrequest_id);
        $srch->addCondition('orrequest_user_id', '=', $user_id);
        $cnd = $srch->addCondition('orrequest_status', '=', OrderReturnRequest::RETURN_REQUEST_STATUS_PENDING);
        $cnd->attachCondition('orrequest_status', '=', OrderReturnRequest::RETURN_REQUEST_STATUS_ESCALATED);
        $srch->doNotCalculateRecords();
        $srch->doNotLimitRecords();
        $srch->addMultipleFields(array('orrequest_id', 'op_id', 'order_language_id'));
        $rs = $srch->getResultSet();
        $request = FatApp::getDb()->fetch($rs);
        if (!$request) {
            $message = Labels::getLabel('MSG_Invalid_Access', $this->siteLangId);
            if (true === MOBILE_APP_API_CALL) {
                LibHelper::dieJsonError($message);
            }
            Message::addErrorMessage($message);
            FatApp::redirectUser(UrlHelper::generateUrl('Buyer', 'viewOrderReturnRequest', array($orrequest_id)));
        }

        $orrObj = new OrderReturnRequest();
        if (!$orrObj->withdrawRequest($request['orrequest_id'], $user_id, $this->siteLangId, $request['op_id'], $request['order_language_id'])) {
            $message = Labels::getLabel($orrObj->getError(), $this->siteLangId);
            if (true === MOBILE_APP_API_CALL) {
                LibHelper::dieJsonError($message);
            }
            Message::addErrorMessage($message);
            FatApp::redirectUser(UrlHelper::generateUrl('Buyer', 'viewOrderReturnRequest', array($orrequest_id)));
        }
        CalculativeDataRecord::updateOrderReturnRequestCount();

        /* email notification handling[ */
        $emailNotificationObj = new EmailHandler();
        if (!$emailNotificationObj->sendOrderReturnRequestStatusChangeNotification($request['orrequest_id'], $this->siteLangId)) {
            $message = Labels::getLabel($emailNotificationObj->getError(), $this->siteLangId);
            if (true === MOBILE_APP_API_CALL) {
                LibHelper::dieJsonError($message);
            }
            Message::addErrorMessage($message);
            CommonHelper::redirectUserReferer();
        }
        /* ] */

        //send notification to admin
        $notificationData = array(
            'notification_record_type' => Notification::TYPE_ORDER_RETURN_REQUEST,
            'notification_record_id' => $request['orrequest_id'],
            'notification_user_id' => UserAuthentication::getLoggedUserId(),
            'notification_label_key' => Notification::RETURN_REQUEST_STATUS_CHANGE_NOTIFICATION,
            'notification_added_on' => date('Y-m-d H:i:s'),
        );

        if (!Notification::saveNotifications($notificationData)) {
            $message = Labels::getLabel('MSG_NOTIFICATION_COULD_NOT_BE_SENT', $this->siteLangId);
            LibHelper::dieJsonError($message);
        }
        if (true === MOBILE_APP_API_CALL) {
            $this->_template->render();
        }
        Message::addMessage(Labels::getLabel('MSG_REQUEST_WITHDRAWN', $this->siteLangId));
        FatApp::redirectUser(UrlHelper::generateUrl('Buyer', 'viewOrderReturnRequest', array($orrequest_id)));
    }

    /* public function orderReturnRequestMessageSearch(){
      $frm = $this->getOrderReturnRequestMessageSearchForm( $this->siteLangId );
      $post = $frm->getFormDataFromArray( FatApp::getPostedData() );
      $page = (empty($post['page']) || $post['page'] <= 0) ? 1 : FatUtility::int($post['page']);
      $pageSize = FatApp::getConfig('conf_page_size', FatUtility::VAR_INT, 10);
      $user_id = UserAuthentication::getLoggedUserId();

      $orrequest_id = isset($post['orrequest_id']) ? FatUtility::int($post['orrequest_id']) : 0;

      $srch = new OrderReturnRequestMessageSearch( $this->siteLangId );
      $srch->joinOrderReturnRequests();
      $srch->joinMessageUser();
      $srch->addCondition( 'orrmsg_orrequest_id', '=', $orrequest_id );
      //$srch->addCondition( 'orrequest_user_id', '=', $user_id );
      $srch->setPageNumber($page);
      $srch->setPageSize($pageSize);
      $srch->addOrder('orrmsg_id','DESC');
      $srch->addMultipleFields( array( 'orrmsg_from_user_id', 'orrmsg_msg',
      'orrmsg_date', 'msg_user.user_name as msg_user_name', 'orrequest_status' ) );

      $rs = $srch->getResultSet();
      $messagesList = FatApp::getDb()->fetchAll($rs);

      $this->set( 'messagesList', $messagesList );
      $this->set('page', $page);
      $this->set('pageCount', $srch->pages());
      $this->set('postedData', $post);

      $startRecord = ($page-1)*$pageSize + 1 ;
      $endRecord = $page * $pageSize;
      $totalRecords = $srch->recordCount();
      if ($totalRecords < $endRecord) { $endRecord = $totalRecords; }
      $json['totalRecords'] = $totalRecords;
      $json['startRecord'] = $startRecord;
      $json['endRecord'] = $endRecord;
      $json['html'] = $this->_template->render( false, false, 'buyer/order-return-request-messages-list.php', true);
      $json['loadMoreBtnHtml'] = $this->_template->render( false, false, 'buyer/order-return-request-messages-list-load-more-btn.php', true);
      FatUtility::dieJsonSuccess($json);
      } */

    public function setUpReturnOrderRequestMessage()
    {
        $orrmsg_orrequest_id = FatApp::getPostedData('orrmsg_orrequest_id', null, '0');

        $frm = $this->getOrderReturnRequestMessageForm($this->siteLangId);
        $post = $frm->getFormDataFromArray(FatApp::getPostedData());
        if (false === $post) {
            $message = current($frm->getValidationErrors());
            if (true === MOBILE_APP_API_CALL) {
                LibHelper::dieJsonError($message);
            }
            Message::addErrorMessage($message);
            FatUtility::dieWithError(Message::getHtml());
        }

        $orrmsg_orrequest_id = FatUtility::int($orrmsg_orrequest_id);
        $user_id = UserAuthentication::getLoggedUserId();

        $srch = new OrderReturnRequestSearch($this->siteLangId);
        $srch->addCondition('orrequest_id', '=', $orrmsg_orrequest_id);
        $srch->addCondition('orrequest_user_id', '=', $user_id);
        $srch->joinOrderProducts();
        $srch->joinSellerProducts();
        $srch->joinOrderReturnReasons();
        $srch->doNotCalculateRecords();
        $srch->doNotLimitRecords();
        $srch->addMultipleFields(array('orrequest_id', 'orrequest_status',));
        $rs = $srch->getResultSet();
        $requestRow = FatApp::getDb()->fetch($rs);
        if (!$requestRow) {
            $message = Labels::getLabel('MSG_Invalid_Access', $this->siteLangId);
            if (true === MOBILE_APP_API_CALL) {
                LibHelper::dieJsonError($message);
            }
            Message::addErrorMessage($message);
            FatUtility::dieWithError(Message::getHtml());
        }

        if ($requestRow['orrequest_status'] == OrderReturnRequest::RETURN_REQUEST_STATUS_REFUNDED || $requestRow['orrequest_status'] == OrderReturnRequest::RETURN_REQUEST_STATUS_WITHDRAWN) {
            $message = Labels::getLabel('MSG_Message_cannot_be_posted_now,_as_order_is_refunded_or_withdrawn.', $this->siteLangId);
            if (true === MOBILE_APP_API_CALL) {
                LibHelper::dieJsonError($message);
            }
            Message::addErrorMessage($message);
            FatUtility::dieWithError(Message::getHtml());
        }

        /* save return request message[ */
        $returnRequestMsgDataToSave = array(
            'orrmsg_orrequest_id' => $requestRow['orrequest_id'],
            'orrmsg_from_user_id' => $user_id,
            'orrmsg_msg' => $post['orrmsg_msg'],
            'orrmsg_date' => date('Y-m-d H:i:s'),
        );
        $oReturnRequestMsgObj = new OrderReturnRequestMessage();
        $oReturnRequestMsgObj->assignValues($returnRequestMsgDataToSave);
        if (!$oReturnRequestMsgObj->save()) {
            $message = $oReturnRequestMsgObj->getError();
            if (true === MOBILE_APP_API_CALL) {
                LibHelper::dieJsonError($message);
            }
            Message::addErrorMessage($message);
            FatUtility::dieWithError(Message::getHtml());
        }
        $orrmsg_id = $oReturnRequestMsgObj->getMainTableRecordId();
        if (!$orrmsg_id) {
            $message = Labels::getLabel('MSG_Something_went_wrong,_please_contact_admin', $this->siteLangId);
            if (true === MOBILE_APP_API_CALL) {
                LibHelper::dieJsonError($message);
            }
            Message::addErrorMessage($message);
            FatUtility::dieWithError(Message::getHtml());
        }
        /* ] */

        /* sending of email notification[ */
        $emailNotificationObj = new EmailHandler();
        if (!$emailNotificationObj->sendReturnRequestMessageNotification($orrmsg_id, $this->siteLangId)) {
            $message = $emailNotificationObj->getError();
            if (true === MOBILE_APP_API_CALL) {
                LibHelper::dieJsonError($message);
            }
            Message::addErrorMessage($message);
            FatUtility::dieWithError(Message::getHtml());
        }
        /* ] */

        //send notification to admin
        $notificationData = array(
            'notification_record_type' => Notification::TYPE_ORDER_RETURN_REQUEST,
            'notification_record_id' => $requestRow['orrequest_id'],
            'notification_user_id' => UserAuthentication::getLoggedUserId(),
            'notification_label_key' => Notification::ORDER_RETURNED_REQUEST_MESSAGE_NOTIFICATION,
            'notification_added_on' => date('Y-m-d H:i:s'),
        );

        if (!Notification::saveNotifications($notificationData)) {
            $message = Labels::getLabel('MSG_NOTIFICATION_COULD_NOT_BE_SENT', $this->siteLangId);
            if (true === MOBILE_APP_API_CALL) {
                LibHelper::dieJsonError($message);
            }
            Message::addErrorMessage($message);
            FatUtility::dieWithError(Message::getHtml());
        }

        $this->set('orrmsg_orrequest_id', $orrmsg_orrequest_id);
        $this->set('msg', Labels::getLabel('MSG_Message_Submitted_Successfully!', $this->siteLangId));
        if (true === MOBILE_APP_API_CALL) {
            $this->_template->render();
        }
        $this->_template->render(false, false, 'json-success.php');
    }

    public function orderFeedback($opId = 0)
    {
        $opId = FatUtility::int($opId);
        if (1 > $opId) {
            $msg = Labels::getLabel('MSG_ERROR_INVALID_ACCESS', $this->siteLangId);
            if (true === MOBILE_APP_API_CALL) {
                LibHelper::dieJsonError($msg);
            }
            Message::addErrorMessage($msg);
            CommonHelper::redirectUserReferer();
        }

        $userId = UserAuthentication::getLoggedUserId();

        $srch = new OrderProductSearch($this->siteLangId, true);
        $srch->joinShippingCharges();
        $srch->addStatusCondition(unserialize(FatApp::getConfig("CONF_BUYER_ORDER_STATUS")));
        $srch->addCondition('order_user_id', '=', $userId);
        $srch->addCondition('op_id', '=', $opId);
        $srch->addOrder("op_id", "DESC");
        $rs = $srch->getResultSet();
        $opDetail = FatApp::getDb()->fetch($rs);
        if (!$opDetail || CommonHelper::isMultidimArray($opDetail) || !(FatApp::getConfig("CONF_ALLOW_REVIEWS", FatUtility::VAR_INT, 0))) {
            $msg = Labels::getLabel('MSG_ERROR_INVALID_ACCESS', $this->siteLangId);
            if (true === MOBILE_APP_API_CALL) {
                LibHelper::dieJsonError($msg);
            }
            Message::addErrorMessage($msg);
            CommonHelper::redirectUserReferer();
        }

        if (!in_array($opDetail["op_status_id"], SelProdReview::getBuyerAllowedOrderReviewStatuses())) {
            $orderStatuses = Orders::getOrderProductStatusArr($this->siteLangId);
            $statuses = SelProdReview::getBuyerAllowedOrderReviewStatuses();
            $statusNames = array();

            foreach ($statuses as $status) {
                $statusNames[] = $orderStatuses[$status];
            }

            $msg = sprintf(Labels::getLabel('MSG_Feedback_can_be_placed_', $this->siteLangId), implode(',', $statusNames));
            if (true === MOBILE_APP_API_CALL) {
                LibHelper::dieJsonError($msg);
            }
            Message::addErrorMessage($msg);
            CommonHelper::redirectUserReferer();
        }

        if ($opDetail['op_is_batch']) {
            $selProdIdArr = explode('|', $opDetail['op_batch_selprod_id']);
            $selProdId = array_shift($selProdIdArr);
        } else {
            $selProdId = $opDetail['op_selprod_id'];
        }

        if (1 > FatUtility::int($selProdId)) {
            $msg = Labels::getLabel('MSG_Invalid_Access', $this->siteLangId);
            if (true === MOBILE_APP_API_CALL) {
                LibHelper::dieJsonError($msg);
            }
            Message::addErrorMessage($msg);
            CommonHelper::redirectUserReferer();
        }

        $oFeedbackSrch = new SelProdReviewSearch();
        $oFeedbackSrch->doNotCalculateRecords();
        $oFeedbackSrch->doNotLimitRecords();
        $oFeedbackSrch->addCondition('spreview_postedby_user_id', '=', $userId);
        $oFeedbackSrch->addCondition('spreview_order_id', '=', $opDetail['op_order_id']);
        $oFeedbackSrch->addCondition('spreview_selprod_id', '=', $selProdId);
        $oFeedbackRs = $oFeedbackSrch->getResultSet();
        if (FatApp::getDb()->fetch($oFeedbackRs)) {
            $msg = Labels::getLabel('MSG_Already_submitted_order_feedback', $this->siteLangId);
            if (true === MOBILE_APP_API_CALL) {
                LibHelper::dieJsonError($msg);
            }
            Message::addErrorMessage($msg);
            CommonHelper::redirectUserReferer();
        }

        $canSubmitFeedback = Orders::canSubmitFeedback($userId, $opDetail['op_order_id'], $selProdId);

        if (!$canSubmitFeedback) {
            $msg = Labels::getLabel('MSG_Already_submitted_order_feedback', $this->siteLangId);
            if (true === MOBILE_APP_API_CALL) {
                LibHelper::dieJsonError($msg);
            }
            Message::addErrorMessage($msg);
            CommonHelper::redirectUserReferer();
        }

        $srch = new ShopSearch($this->siteLangId);
        $srch->setDefinedCriteria($this->siteLangId);
        $srch->doNotCalculateRecords();
        $srch->addMultipleFields(
            array('shop_id', 'shop_user_id', 'shop_created_on', 'COALESCE(shop_name, shop_identifier) as shop_name', 'shop_description', 'u.user_regdate')
        );
        $srch->addCondition('shop_id', '=', $opDetail['op_shop_id']);
        $shopRs = $srch->getResultSet();
        $shop = FatApp::getDb()->fetch($shopRs);

        $selProdRating = SelProdRating::getRatingAspectsArr($this->siteLangId, $opDetail['opshipping_fulfillment_type']);

        $orderProd = new OrderProduct($opId);
        $specifics = $orderProd->getSpecifics();
        $otherRatingTypes = [];
        if (array_key_exists('op_prodcat_id', $specifics) && !empty($specifics['op_prodcat_id'])) {
            $srch = ProductCategory::getRatingTypesObj($this->siteLangId, applicationConstants::ACTIVE);
            $srch->addCondition('prt_prodcat_id', '=', $specifics['op_prodcat_id']);
            $srch->addCondition('ratingtype_type', '=', RatingType::TYPE_OTHER);
            $srch->addMultipleFields(['ratingtype_id', 'COALESCE(ratingtype_name, ratingtype_identifier) as ratingtype_name']);
            $otherRatingTypes = (array) FatApp::getDb()->fetchAllAssoc($srch->getResultSet());
        }

        if (0 < count($otherRatingTypes)) {
            $selProdRating = $selProdRating + $otherRatingTypes;
        }

        $ratingAspects = $selProdRating;

        $shopRatingTypesArr = SelProdRating::getShopRatingTypeArr($this->siteLangId);

        $deliveryRatingTypesArr = [];
        if ($opDetail['op_product_type'] != Product::PRODUCT_TYPE_SERVICE) {
            $deliveryRatingTypesArr = SelProdRating::getDeliveryRatingTypeArr($this->siteLangId);
        }

        if (!empty($shopRatingTypesArr) || !empty($deliveryRatingTypesArr)) {
            $ratingAspects = (0 < count($shopRatingTypesArr)) ? ($shopRatingTypesArr + $ratingAspects) : $ratingAspects;
            $ratingAspects = (0 < count($deliveryRatingTypesArr)) ? ($deliveryRatingTypesArr + $ratingAspects) : $ratingAspects;
        }


        if (false === MOBILE_APP_API_CALL) {
            $frm = $this->getOrderFeedbackForm($opId, $this->siteLangId, $ratingAspects);
            $this->set('frm', $frm);
            $this->_template->addJs(array('js/jquery.barrating.min.js'));
        }

        $this->set('opDetail', $opDetail);
        $this->set('ratingAspects', $ratingAspects);
        $this->set('selProdRating', $selProdRating);
        $this->set('otherRatingTypesArr', $otherRatingTypes);
        $this->set('shopRatingTypesArr', $shopRatingTypesArr);
        $this->set('deliveryRatingTypesArr', $deliveryRatingTypesArr);
        $this->set('shop', $shop);

        $this->_template->render();
    }

    public function setupOrderFeedback()
    {
        $opId = FatApp::getPostedData('op_id', FatUtility::VAR_INT, 0);
        if (1 > $opId) {
            $message = Labels::getLabel('MSG_ERROR_INVALID_ACCESS', $this->siteLangId);
            LibHelper::dieJsonError($message);
        }

        $userId = UserAuthentication::getLoggedUserId();

        $srch = new OrderProductSearch($this->siteLangId, true);
        $srch->joinShippingCharges();
        $srch->addStatusCondition(unserialize(FatApp::getConfig("CONF_BUYER_ORDER_STATUS")));
        $srch->addCondition('order_user_id', '=', $userId);
        $srch->addCondition('op_id', '=', $opId);
        $srch->addOrder("op_id", "DESC");
        $srch->addMultipleFields(array('op_status_id', 'op_selprod_user_id', 'op_selprod_code', 'op_order_id', 'op_selprod_id', 'op_is_batch', 'op_batch_selprod_id', 'op_product_type', 'opshipping_fulfillment_type'));
        $rs = $srch->getResultSet();
        $opDetail = FatApp::getDb()->fetch($rs);

        if (!$opDetail || CommonHelper::isMultidimArray($opDetail) || !(FatApp::getConfig("CONF_ALLOW_REVIEWS", FatUtility::VAR_INT, 0))) {
            $message = Labels::getLabel('MSG_ERROR_INVALID_ACCESS', $this->siteLangId);
            LibHelper::dieJsonError($message);
        }

        if ($opDetail['op_is_batch']) {
            $selProdIdArr = explode('|', $opDetail['op_batch_selprod_id']);
            $selProdId = array_shift($selProdIdArr);
        } else {
            $selProdId = $opDetail['op_selprod_id'];
        }

        if (1 > FatUtility::int($selProdId)) {
            $message = Labels::getLabel('MSG_ERROR_INVALID_ACCESS', $this->siteLangId);
            LibHelper::dieJsonError($message);
        }

        if (!in_array($opDetail["op_status_id"], SelProdReview::getBuyerAllowedOrderReviewStatuses())) {
            $orderStatuses = Orders::getOrderProductStatusArr($this->siteLangId);
            $statuses = SelProdReview::getBuyerAllowedOrderReviewStatuses();
            $statusNames = array();

            foreach ($statuses as $status) {
                $statusNames[] = $orderStatuses[$status];
            }
            $message = sprintf(Labels::getLabel('MSG_Feedback_can_be_placed_', $this->siteLangId), implode(',', $statusNames));
            LibHelper::dieJsonError($message);
        }


        /* checking Abusive Words[ */
        $enteredAbusiveWordsArr = array();
        if (!Abusive::validateContent(FatApp::getPostedData('spreview_description', FatUtility::VAR_STRING, ''), $enteredAbusiveWordsArr)) {
            if (!empty($enteredAbusiveWordsArr)) {
                $errStr = Labels::getLabel("LBL_Word_{abusiveword}_is/are_not_allowed_to_post", $this->siteLangId);
                $errStr = str_replace("{abusiveword}", '"' . implode(", ", $enteredAbusiveWordsArr) . '"', $errStr);
                LibHelper::dieJsonError($errStr);
            }
        }

        if (!Abusive::validateContent(FatApp::getPostedData('spreview_title', FatUtility::VAR_STRING, ''), $enteredAbusiveWordsArr)) {
            if (!empty($enteredAbusiveWordsArr)) {
                $errStr = Labels::getLabel("LBL_Word_{abusiveword}_is/are_not_allowed_to_post", $this->siteLangId);
                $errStr = str_replace("{abusiveword}", '"' . implode(", ", $enteredAbusiveWordsArr) . '"', $errStr);
                LibHelper::dieJsonError($errStr);
            }
        }
        /* ] */

        $sellerId = $opDetail['op_selprod_user_id'];

        /* $selProdDetail = SellerProduct::getAttributesById($selProdId);
          $productId = FatUtility::int($selProdDetail['selprod_product_id']); */

        $op_selprod_code = explode('|', $opDetail['op_selprod_code']);
        $selProdCode = array_shift($op_selprod_code);
        $selProdCodeArr = explode('_', $selProdCode);
        $productId = array_shift($selProdCodeArr);

        $canSubmitFeedback = Orders::canSubmitFeedback($userId, $opDetail['op_order_id'], $selProdId);

        if (!$canSubmitFeedback) {
            $message = Labels::getLabel('MSG_Already_submitted_order_feedback', $this->siteLangId);
            LibHelper::dieJsonError($message);
        }

        $ratingAspects = SelProdRating::getRatingAspectsArr($this->siteLangId, $opDetail['opshipping_fulfillment_type']);

        $orderProd = new OrderProduct($opId);
        $specifics = $orderProd->getSpecifics();
        $ratingTypes = [];
        if (array_key_exists('op_prodcat_id', $specifics) && !empty($specifics['op_prodcat_id'])) {
            $srch = ProductCategory::getRatingTypesObj($this->siteLangId, applicationConstants::ACTIVE);
            $srch->addCondition('prt_prodcat_id', '=', $specifics['op_prodcat_id']);
            $srch->addMultipleFields(['ratingtype_id', 'COALESCE(ratingtype_name, ratingtype_identifier) as ratingtype_name']);
            $ratingTypes = (array) FatApp::getDb()->fetchAllAssoc($srch->getResultSet());
        }

        if (0 < count($ratingTypes)) {
            $ratingAspects = $ratingAspects + $ratingTypes;
        }

        $shopRatingTypesArr = SelProdRating::getShopRatingTypeArr($this->siteLangId);
        $deliveryRatingTypesArr = [];
        if ($opDetail['op_product_type'] != Product::PRODUCT_TYPE_SERVICE) {
            $deliveryRatingTypesArr = SelProdRating::getDeliveryRatingTypeArr($this->siteLangId);
        }

        if (!empty($shopRatingTypesArr) || !empty($deliveryRatingTypesArr)) {
            $ratingAspects = (0 < count($shopRatingTypesArr)) ? ($shopRatingTypesArr + $ratingAspects) : $ratingAspects;
            $ratingAspects = (0 < count($deliveryRatingTypesArr)) ? ($deliveryRatingTypesArr + $ratingAspects) : $ratingAspects;
        }

        $frm = $this->getOrderFeedbackForm($opId, $this->siteLangId, $ratingAspects);
        $post = FatApp::getPostedData();

        if (false === MOBILE_APP_API_CALL) {
            $post = $frm->getFormDataFromArray($post);
            if (false === $post) {
                LibHelper::dieJsonError($frm->getValidationErrors());
            }
        }

        $post['spreview_seller_user_id'] = $sellerId;
        $post['spreview_order_id'] = $opDetail['op_order_id'];
        $post['spreview_product_id'] = $productId;
        $post['spreview_selprod_id'] = $selProdId;
        $post['spreview_selprod_code'] = $selProdCode;
        $post['spreview_postedby_user_id'] = $userId;
        $post['spreview_posted_on'] = date('Y-m-d H:i:s');
        $post['spreview_lang_id'] = $this->siteLangId;
        $post['spreview_status'] = FatApp::getConfig('CONF_DEFAULT_REVIEW_STATUS', FatUtility::VAR_INT, 0);

        $selProdReview = new SelProdReview();

        $selProdReview->assignValues($post);

        $db = FatApp::getDb();
        $db->startTransaction();

        if (!$selProdReview->save()) {
            $db->rollbackTransaction();
            LibHelper::dieJsonError($selProdReview->getError());
        }

        SelProdRating::updateSellerRating($sellerId);
        SelProdReview::updateSellerTotalReviews($sellerId);
        SelProdReview::updateProductRating($productId);

        $spreviewId = $selProdReview->getMainTableRecordId();

        $ratingsPosted = FatApp::getPostedData('review_rating');

        foreach ($ratingsPosted as $ratingAspect => $ratingValue) {
            if (array_key_exists($ratingAspect, $ratingAspects)) {
                $selProdRating = new SelProdRating();
                $ratingRow = array('sprating_spreview_id' => $spreviewId, 'sprating_ratingtype_id' => $ratingAspect, 'sprating_rating' => $ratingValue);
                $selProdRating->assignValues($ratingRow);
                if (!$selProdRating->save()) {
                    $db->rollbackTransaction();
                    LibHelper::dieJsonError($selProdRating->getError());
                }
            }
        }

        if (!empty($_FILES) && array_key_exists('spreview_image', $_FILES) && is_array($_FILES['spreview_image']['tmp_name'])) {
            foreach ($_FILES['spreview_image']['tmp_name'] as $index => $tmpName) {
                if (!is_uploaded_file($tmpName)) {
                    FatUtility::dieJsonError(Labels::getLabel('ERR_PLEASE_SELECT_A_FILE', $this->siteLangId));
                }

                $fileHandlerObj = new AttachedFile();
                if (!$fileHandlerObj->saveAttachment(
                    $tmpName,
                    AttachedFile::FILETYPE_ORDER_FEEDBACK,
                    $spreviewId,
                    0,
                    $_FILES['spreview_image']['name'][$index]
                )) {
                    FatUtility::dieJsonError($fileHandlerObj->getError());
                }
            }
        }

        $db->commitTransaction();
        $emailNotificationObj = new EmailHandler();
        if ($post['spreview_status'] == SelProdReview::STATUS_APPROVED) {
            $emailNotificationObj->sendBuyerReviewStatusUpdatedNotification($spreviewId, $this->siteLangId);
        }
        /*
        $reviewTitle = $post['spreview_title'];
        $reviewTitleArr = preg_split("/[\s,-]+/", $reviewTitle);
        $reviewDesc = $post['spreview_description'];
        $reviewDescArr = preg_split("/[\s,-]+/", $reviewDesc);
       
        $abusiveWords = Abusive::getAbusiveWords();
        if (!empty(array_intersect($abusiveWords, $reviewTitleArr)) || !empty(array_intersect($abusiveWords, $reviewDescArr))) {
            $emailNotificationObj->sendAdminAbusiveReviewNotification($spreviewId, $this->siteLangId);
           
            $notificationData = array(
                'notification_record_type' => Notification::TYPE_PRODUCT_REVIEW,
                'notification_record_id' => $spreviewId,
                'notification_user_id' => UserAuthentication::getLoggedUserId(),
                'notification_label_key' => Notification::ABUSIVE_REVIEW_POSTED_NOTIFICATION,
                'notification_added_on' => date('Y-m-d H:i:s'),
            );

            if (!Notification::saveNotifications($notificationData)) {
                $message = Labels::getLabel("MSG_NOTIFICATION_COULD_NOT_BE_SENT", $this->siteLangId);
                LibHelper::dieJsonError($message);
            }
        } else {
            */
        $notificationData = array(
            'notification_record_type' => Notification::TYPE_PRODUCT_REVIEW,
            'notification_record_id' => $spreviewId,
            'notification_user_id' => UserAuthentication::getLoggedUserId(),
            'notification_label_key' => Notification::PRODUCT_REVIEW_NOTIFICATION,
            'notification_added_on' => date('Y-m-d H:i:s'),
        );

        if (!Notification::saveNotifications($notificationData)) {
            $message = Labels::getLabel("MSG_NOTIFICATION_COULD_NOT_BE_SENT", $this->siteLangId);
            LibHelper::dieJsonError($message);
        }
        /* } */
        if (true === MOBILE_APP_API_CALL) {
            $this->_template->render();
        }

        $redirectUrl = isset($post['referrer']) && !empty($post['referrer']) ? $post['referrer'] : UrlHelper::generateUrl('Buyer', 'Orders');

        $this->set('msg', Labels::getLabel('MSG_Feedback_Submitted_Successfully', $this->siteLangId));
        $this->set('redirectUrl', $redirectUrl);
        $this->_template->render(false, false, 'json-success.php');
    }

    public function orderReturnRequest($op_id)
    {
        $op_id = FatUtility::int($op_id);

        $oCancelRequestSrch = new OrderCancelRequestSearch();
        $oCancelRequestSrch->doNotCalculateRecords();
        $oCancelRequestSrch->doNotLimitRecords();
        $oCancelRequestSrch->addCondition('ocrequest_op_id', '=', $op_id);
        $oCancelRequestSrch->addCondition('ocrequest_status', '!=', OrderCancelRequest::CANCELLATION_REQUEST_STATUS_DECLINED);
        $oCancelRequestRs = $oCancelRequestSrch->getResultSet();

        if (FatApp::getDb()->fetch($oCancelRequestRs)) {
            Message::addErrorMessage(Labels::getLabel('ERR_ALREADY_SUBMITTED_CANCEL_REQUEST', $this->siteLangId));
            CommonHelper::redirectUserReferer();
        }

        $user_id = UserAuthentication::getLoggedUserId();
        $srch = new OrderProductSearch($this->siteLangId, true);
        $srch->addStatusCondition(unserialize(FatApp::getConfig("CONF_BUYER_ORDER_STATUS")));
        $srch->addCondition('order_user_id', '=', $user_id);
        $srch->addCondition('op_id', '=', $op_id);
        $srch->addOrder("op_id", "DESC");
        $srch->addMultipleFields(array('op_status_id', 'op_id', 'op_qty', 'op_product_type'));
        $rs = $srch->getResultSet();
        $opDetail = FatApp::getDb()->fetch($rs);
        if (!$opDetail || CommonHelper::isMultidimArray($opDetail)) {
            Message::addErrorMessage(Labels::getLabel('ERR_ERROR_INVALID_ACCESS', $this->siteLangId));
            // CommonHelper::redirectUserReferer();
            FatApp::redirectUser(UrlHelper::generateUrl('Buyer', 'orderReturnRequests'));
        }

        $getBuyerAllowedOrderReturnStatuses = (array) Orders::getBuyerAllowedOrderReturnStatuses($opDetail["op_product_type"]);

        if (!in_array($opDetail["op_status_id"], $getBuyerAllowedOrderReturnStatuses)) {
            $orderStatuses = Orders::getOrderProductStatusArr($this->siteLangId);
            $statuses = $getBuyerAllowedOrderReturnStatuses;

            $status_names = array();
            foreach ($statuses as $status) {
                $status_names[] = $orderStatuses[$status];
            }
            Message::addErrorMessage(sprintf(Labels::getLabel('MSG_Return_Refund_cannot_placed', $this->siteLangId), implode(',', $status_names)));
            // CommonHelper::redirectUserReferer();
            FatApp::redirectUser(UrlHelper::generateUrl('Buyer', 'orderReturnRequests'));
        }

        $oReturnRequestSrch = new OrderReturnRequestSearch();
        $oReturnRequestSrch->doNotCalculateRecords();
        $oReturnRequestSrch->doNotLimitRecords();
        $oReturnRequestSrch->addCondition('orrequest_op_id', '=', $opDetail['op_id']);
        $oReturnRequestRs = $oReturnRequestSrch->getResultSet();
        if (FatApp::getDb()->fetch($oReturnRequestRs)) {
            Message::addErrorMessage(Labels::getLabel('ERR_ALREADY_SUBMITTED_RETURN_REQUEST_ORDER', $this->siteLangId));
            // CommonHelper::redirectUserReferer();
            FatApp::redirectUser(UrlHelper::generateUrl('Buyer', 'orderReturnRequests'));
        }

        $frm = $this->getOrderReturnRequestForm($this->siteLangId, $opDetail);
        // $fld = $frm->getField('orrequest_qty');

        $frm->fill(array('op_id' => $opDetail['op_id']));
        $this->set('frmOrderReturnRequest', $frm);
        $this->_template->render(true, true);
    }

    public function setupOrderReturnRequest()
    {
        $op_id = FatApp::getPostedData('op_id', null, '0');
        $user_id = UserAuthentication::getLoggedUserId();
        $srch = new OrderProductSearch($this->siteLangId, true);
        $srch->joinOrderProductCharges(OrderProduct::CHARGE_TYPE_VOLUME_DISCOUNT, 'cvd');
        $srch->joinOrderProductSpecifics();
        $srch->addStatusCondition(unserialize(FatApp::getConfig("CONF_BUYER_ORDER_STATUS")));
        $srch->addCondition('order_user_id', '=', $user_id);
        $srch->addCondition('op_id', '=', $op_id);
        $srch->addOrder("op_id", "DESC");
        $srch->addMultipleFields(array('order_language_id', 'op_status_id', 'op_id', 'op_qty', 'op_product_type', 'op_unit_price', 'opcharge_amount', 'order_date_added', 'op_selprod_return_age'));
        $opDetail = FatApp::getDb()->fetch($srch->getResultSet());

        if (!$opDetail || CommonHelper::isMultidimArray($opDetail)) {
            $message = Labels::getLabel('MSG_ERROR_INVALID_ACCESS', $this->siteLangId);
            LibHelper::dieJsonError($message);
        }

        $datediff = time() - strtotime($opDetail['order_date_added']);
        $daysSpent = $datediff / (60 * 60 * 24);

        if ($opDetail["op_product_type"] == Product::PRODUCT_TYPE_PHYSICAL && $opDetail['op_selprod_return_age'] <= $daysSpent) {
            $message = Labels::getLabel('MSG_ERROR_INVALID_ACCESS', $this->siteLangId);
            LibHelper::dieJsonError($message);
        }

        $frm = $this->getOrderReturnRequestForm($this->siteLangId, $opDetail);
        $post = $frm->getFormDataFromArray(FatApp::getPostedData());
        if (false === $post) {
            if (true === MOBILE_APP_API_CALL) {
                LibHelper::dieJsonError(current($frm->getValidationErrors()));
            }
            FatUtility::dieJsonError(current($frm->getValidationErrors()));
        }

        if (isset($_FILES['file']['tmp_name'])  && !empty($_FILES['file']['tmp_name'])) {
            $uploadedFile = $_FILES['file']['tmp_name'];
            if (filesize($uploadedFile) > 10240000) {
                $message = Labels::getLabel('ERR_PLEASE_UPLOAD_FILE_SIZE_LESS_THAN_10MB', $this->siteLangId);
                LibHelper::dieJsonError($message);
            }

            if (!in_array(mime_content_type($uploadedFile), applicationConstants::allowedMimeTypes()) || (getimagesize($uploadedFile) === false && mime_content_type($uploadedFile) != 'application/zip')) {
                $message = Labels::getLabel('ERR_ONLY_IMAGE_EXTENSIONS_AND_ZIP_IS_ALLOWED', $this->siteLangId);
                LibHelper::dieJsonError($message);
            }
        }

        $opDetail['opcharge_amount'] = $opDetail['opcharge_amount'] ?? 0;
        if (abs($opDetail['opcharge_amount']) > 0) {
            $orrequestQty = FatUtility::int($post['orrequest_qty']);
            $volumeDiscountPerItem = abs($opDetail['opcharge_amount']) / $opDetail['op_qty'];
            $amtChargeBackToBuyer = ($opDetail['op_qty'] - $orrequestQty) * $volumeDiscountPerItem;
            if ($amtChargeBackToBuyer > ($opDetail['op_unit_price'] - $volumeDiscountPerItem) * abs($orrequestQty)) {
                FatUtility::dieJsonError(Labels::getLabel('ERR_ORDER_NOT_ELIGIBLE_FOR_PARTIAL_QTY_REFUND', $this->siteLangId));
            }
        }

        $getBuyerAllowedOrderReturnStatuses = (array) Orders::getBuyerAllowedOrderReturnStatuses($opDetail["op_product_type"]);

        if (!in_array($opDetail["op_status_id"], $getBuyerAllowedOrderReturnStatuses)) {
            $orderStatuses = Orders::getOrderProductStatusArr($this->siteLangId);
            $statuses = $getBuyerAllowedOrderReturnStatuses;

            $status_names = array();
            foreach ($statuses as $status) {
                $status_names[] = $orderStatuses[$status];
            }
            $message = sprintf(Labels::getLabel('MSG_Return_Refund_cannot_placed', $this->siteLangId), implode(',', $status_names));
            LibHelper::dieJsonError($message);
        }

        $oReturnRequestSrch = new OrderReturnRequestSearch();
        $oReturnRequestSrch->doNotCalculateRecords();
        $oReturnRequestSrch->doNotLimitRecords();
        $oReturnRequestSrch->addCondition('orrequest_op_id', '=', $opDetail['op_id']);
        $oReturnRequestRs = $oReturnRequestSrch->getResultSet();
        if (FatApp::getDb()->fetch($oReturnRequestRs)) {
            $message = Labels::getLabel('MSG_Already_submitted_return_request_order', $this->siteLangId);
            LibHelper::dieJsonError($message);
        }


        $reference_number = $user_id . '-' . time();
        $returnRequestDataToSave = array(
            'orrequest_user_id' => $user_id,
            'orrequest_reference' => $reference_number,
            'orrequest_op_id' => $opDetail['op_id'],
            'orrequest_qty' => FatUtility::int($post['orrequest_qty']),
            'orrequest_returnreason_id' => FatUtility::int($post['orrequest_returnreason_id']),
            'orrequest_type' => FatUtility::int($post['orrequest_type']),
            'orrequest_date' => date('Y-m-d H:i:s'),
            'orrequest_status' => OrderReturnRequest::RETURN_REQUEST_STATUS_PENDING
        );
        $oReturnRequestObj = new OrderReturnRequest();
        $oReturnRequestObj->assignValues($returnRequestDataToSave);
        if (!$oReturnRequestObj->save()) {
            FatUtility::dieJsonError($oReturnRequestObj->getError());
        }
        $orrequest_id = $oReturnRequestObj->getMainTableRecordId();
        if (!$orrequest_id) {
            $message = Labels::getLabel('MSG_Something_went_wrong,_please_contact_admin', $this->siteLangId);
            LibHelper::dieJsonError($message);
        }

        /* attach file with request [ */

        if (isset($_FILES['file']['tmp_name']) && is_uploaded_file($_FILES['file']['tmp_name'])) {
            $fileHandlerObj = new AttachedFile();
            if (!$fileHandlerObj->saveAttachment($_FILES['file']['tmp_name'], AttachedFile::FILETYPE_BUYER_RETURN_PRODUCT, $orrequest_id, 0, $_FILES['file']['name'], -1, true)) {
                LibHelper::dieJsonError($fileHandlerObj->getError());
            }
        }

        /* ] */

        /* save return request message[ */
        $returnRequestMsgDataToSave = array(
            'orrmsg_orrequest_id' => $orrequest_id,
            'orrmsg_from_user_id' => $user_id,
            'orrmsg_msg' => $post['orrmsg_msg'],
            'orrmsg_date' => date('Y-m-d H:i:s'),
        );

        $oReturnRequestMsgObj = new OrderReturnRequestMessage();
        $oReturnRequestMsgObj->assignValues($returnRequestMsgDataToSave);
        if (!$oReturnRequestMsgObj->save()) {
            LibHelper::dieJsonError($oReturnRequestMsgObj->getError());
        }
        $orrmsg_id = $oReturnRequestMsgObj->getMainTableRecordId();
        if (!$orrmsg_id) {
            $message = Labels::getLabel('MSG_Something_went_wrong,_please_contact_admin', $this->siteLangId);
            LibHelper::dieJsonError($message);
        }
        /* ] */

        /* adding child order history[ */
        $orderObj = new Orders();
        $orderObj->addChildProductOrderHistory($opDetail['op_id'], $opDetail['order_language_id'], FatApp::getConfig("CONF_RETURN_REQUEST_ORDER_STATUS"), Labels::getLabel('LBL_Buyer_Raised_Return_Request', $opDetail['order_language_id']), 1);
        /* ] */
        CalculativeDataRecord::updateOrderReturnRequestCount();

        /* sending of email notification[ */
        $emailNotificationObj = new EmailHandler();
        if (!$emailNotificationObj->sendOrderReturnRequestNotification($orrmsg_id, $opDetail['order_language_id'])) {
            LibHelper::dieJsonError($oReturnRequestMsgObj->getError());
        }
        /* ] */

        /* $this->set( 'msg', Labels::getLabel('MSG_Your_return_request_submitted', $this->siteLangId) );
          $this->_template->render( false, false, 'json-success.php' ); */

        //send notification to admin
        $notificationData = array(
            'notification_record_type' => Notification::TYPE_ORDER_RETURN_REQUEST,
            'notification_record_id' => $orrequest_id,
            'notification_user_id' => UserAuthentication::getLoggedUserId(),
            'notification_label_key' => Notification::ORDER_RETURNED_REQUEST_NOTIFICATION,
            'notification_added_on' => date('Y-m-d H:i:s'),
        );

        if (!Notification::saveNotifications($notificationData)) {
            $message = Labels::getLabel('MSG_NOTIFICATION_COULD_NOT_BE_SENT', $this->siteLangId);
            LibHelper::dieJsonError($message);
        }

        $msg = Labels::getLabel('MSG_Your_return_request_submitted', $this->siteLangId);
        if (true === MOBILE_APP_API_CALL) {
            $this->set('msg', $msg);
            $this->_template->render();
        }
        FatUtility::dieJsonSuccess($msg);
        // $this->_template->render(false, false, 'json-success.php');
    }

    public function rewardPoints($convertReward = '')
    {
        $frm = $this->getRewardPointSearchForm($this->siteLangId);
        $frm->fill(array('convertReward' => $convertReward));
        $this->set('frmSrch', $frm);

        $userId = UserAuthentication::getLoggedUserId();

        /* $srch = new UserRewardSearch;
          $srch->joinUser();
          $srch->addCondition('urp.urp_user_id','=',$userId);
          $cnd = $srch->addCondition('urp.urp_date_expiry','=','0000-00-00');
          $cnd->attachCondition('urp.urp_date_expiry','>=',date('Y-m-d'),'OR');
          $srch->addMultipleFields(array('IFNULL(sum(urp.urp_points),0) as totalRewardPoints'));
          $srch->doNotCalculateRecords();
          $srch->doNotLimitRecords();
          $rs = $srch->getResultSet();
          $records = FatApp::getDb()->fetch($rs);
          $this->set('totalRewardPoints',$records['totalRewardPoints']); */

        $this->set('totalRewardPoints', UserRewardBreakup::rewardPointBalance($userId));
        $this->set('convertReward', $convertReward);
        $this->_template->render(true, true);
    }

    public function rewardPointsSearch()
    {
        $userId = UserAuthentication::getLoggedUserId();

        $frm = $this->getRewardPointSearchForm($this->siteLangId);

        $post = $frm->getFormDataFromArray(FatApp::getPostedData());
        $convertReward = $post['convertReward'];

        $page = FatApp::getPostedData('page', FatUtility::VAR_INT, 1);
        if ($page < 2) {
            $page = 1;
        }
        $page = (empty($page) || $page <= 0) ? 1 : $page;
        $page = FatUtility::int($page);
        $pagesize = FatApp::getConfig('conf_page_size', FatUtility::VAR_INT, 10);
        $srch = new UserRewardSearch();
        $srch->joinUser();
        $srch->addCondition('urp.urp_user_id', '=', $userId);
        $this->setRecordCount(clone $srch, $pagesize, $page, $post);
        $srch->doNotCalculateRecords();
        $srch->addMultipleFields(array('urp.*', 'uc.credential_username'));
        if ($convertReward == 'coupon') {
            $srch->addCondition('urp.urp_used', '=', 0);
            $cond = $srch->addCondition('urp.urp_date_expiry', '=', '0000-00-00');
            $cond->attachCondition('urp.urp_date_expiry', '>=', date('Y-m-d'), 'OR');
            $srch->doNotCalculateRecords();
            $srch->doNotLimitRecords();
        } else {
            $srch->setPageNumber($page);
            $srch->setPageSize($pagesize);
        }
        $srch->addOrder('urp.urp_id', 'DESC');
        $this->set("arrListing", FatApp::getDb()->fetchAll($srch->getResultSet()));
        $this->set('postedData', $post);
        $this->set('convertReward', $convertReward);
        if (true === MOBILE_APP_API_CALL) {
            $this->_template->render();
        }
        $this->_template->render(false, false);
    }

    public function generateCoupon()
    {
        $userId = UserAuthentication::getLoggedUserId();
        $post = FatApp::getPostedData();

        if (empty($post['rewardOptions'])) {
            FatUtility::dieJsonError(Labels::getLabel('ERR_Please_select_options', $this->siteLangId));
        }

        $rewardOptions = str_replace('|', ',', rtrim($post['rewardOptions'], '|'));

        $srch = new UserRewardSearch();
        $srch->joinUser();
        $srch->addCondition('urp.urp_user_id', '=', $userId);
        $srch->addCondition('urp_id', 'in', array($rewardOptions));
        $srch->addCondition('urp.urp_used', '=', 0);
        $cond = $srch->addCondition('urp.urp_date_expiry', '=', '0000-00-00');
        $cond->attachCondition('urp.urp_date_expiry', '>=', date('Y-m-d'), 'OR');
        $srch->addOrder('urp.urp_date_added', 'DESC');
        $srch->addOrder('urp.urp_id', 'DESC');
        $srch->doNotCalculateRecords();
        $srch->setPageSize(1);
        $srch->addMultipleFields(array('sum(urp_points) as totalRewardPoints', 'min(urp.urp_date_expiry) as expiredOn'));
        $rs = $srch->getResultSet();
        $records = FatApp::getDb()->fetch($rs);

        if (empty($records)) {
            FatUtility::dieJsonError(Labels::getLabel('ERR_Invalid_Access', $this->siteLangId));
        }

        if ($records['totalRewardPoints'] < FatApp::getConfig('CONF_MIN_REWARD_POINT') || $records['totalRewardPoints'] > FatApp::getConfig('CONF_MAX_REWARD_POINT')) {
            FatUtility::dieJsonError(Labels::getLabel('ERR_PLEASE_VERIFY_REWARD_CONVERSION_LIMIT', $this->siteLangId));
        }

        $db = FatApp::getDb();
        $db->startTransaction();

        $couponData = array(
            'coupon_type' => DiscountCoupons::TYPE_DISCOUNT,
            'coupon_identifier' => Labels::getLabel('LBL_Generated_From_Reward_Point', $this->siteLangId),
            'coupon_code' => uniqid(),
            'coupon_min_order_value' => 1,
            'coupon_discount_in_percent' => applicationConstants::PERCENTAGE,
            'coupon_discount_value' => CommonHelper::convertRewardPointToCurrency($records['totalRewardPoints']),
            'coupon_max_discount_value' => CommonHelper::convertRewardPointToCurrency($records['totalRewardPoints']),
            'coupon_start_date' => date('Y-m-d'),
            'coupon_end_date' => $records['expiredOn'],
            'coupon_uses_count' => 1,
            'coupon_uses_coustomer' => 1,
            'coupon_active' => applicationConstants::ACTIVE,
        );
        $couponObj = new DiscountCoupons();
        $couponObj->assignValues($couponData);
        if (!$couponObj->save()) {
            $db->rollbackTransaction();
            FatUtility::dieJsonError($couponObj->getError());
        }

        $couponId = $couponObj->getMainTableRecordId();
        if (1 > $couponId) {
            $db->rollbackTransaction();
            FatUtility::dieJsonError(Labels::getLabel('ERR_Invalid_Request', $this->siteLangId));
        }

        $obj = new DiscountCoupons();
        if (!$obj->addUpdateCouponUser($couponId, $userId)) {
            $db->rollbackTransaction();
            FatUtility::dieJsonError(Labels::getLabel($obj->getError(), $this->siteLangId));
        }

        $rewardOptionsArr = explode(',', $rewardOptions);
        foreach ($rewardOptionsArr as $urp_id) {
            $rewardsRecord = new UserRewards($urp_id);
            $rewardsRecord->assignValues(
                array(
                    'urp_used' => 1,
                )
            );
            if (!$rewardsRecord->save()) {
                $db->rollbackTransaction();
                FatUtility::dieJsonError(Labels::getLabel($rewardsRecord->getError(), $this->siteLangId));
            }
        }

        $db->commitTransaction();

        $this->set('msg', Labels::getLabel('MSG_SUCCESSFULLY_GENERATED_COUPON_FROM_REWAR_POINTS', $this->siteLangId));
        $this->_template->render(false, false, 'json-success.php');
    }

    public function offers()
    {
        $this->_template->render(true, true, 'buyer/offers.php');
    }

    public function searchOffers()
    {
        $offers = (array) DiscountCoupons::getUserCoupons(UserAuthentication::getLoggedUserId(), $this->siteLangId);

        $this->set('offers', $offers);

        if (true === MOBILE_APP_API_CALL) {
            $this->_template->render();
        }

        if (empty($offers)) {
            $this->set('noRecordsHtml', $this->_template->render(false, false, '_partial/no-record-found.php', true));
        }
        $this->_template->render(false, false, 'buyer/search-offers.php');
    }

    public function shareEarn()
    {
        if (!FatApp::getConfig("CONF_ENABLE_REFERRER_MODULE", FatUtility::VAR_INT, 1)) {
            Message::addErrorMessage(Labels::getLabel('ERR_INVALID_REQUEST', $this->siteLangId));
            CommonHelper::redirectUserReferer();
        }
        if (empty(UserAuthentication::getLoggedUserAttribute('user_referral_code'))) {
            Message::addErrorMessage(Labels::getLabel('ERR_REFERRAL_CODE_IS_EMPTY', $this->siteLangId));
            CommonHelper::redirectUserReferer();
        }

        $this->set('referralTrackingUrl', CommonHelper::referralTrackingUrl(UserAuthentication::getLoggedUserAttribute('user_referral_code')));
        $this->set('sharingFrm', $this->getFriendsSharingForm($this->siteLangId));

        $this->_template->addJs(['js/slick.min.js', 'js/tagify.min.js', 'js/tagify.polyfills.min.js']);
        $this->_template->addCss(['css/tagify.min.css']);
        $this->_template->render(true, true);
    }

    public function sendMailShareEarn()
    {
        $post = FatApp::getPostedData();
        $email = $post["email"];
        if (empty($email)) {
            FatUtility::dieJsonError(Labels::getLabel('ERR_INVALID_REQUEST', $this->siteLangId));
        }
        $email = array_unique(array_column(json_decode($email, true), 'value'));
        if (count($email) && !empty($email)) {
            //$personalMessage = empty($post['message']) ? "" : "<b>" . Labels::getLabel('Lbl_Personal_Message_From_Sender', $this->siteLangId) . ":</b> " . nl2br($post['message']);
            $emailNotificationObj = new EmailHandler();
            foreach ($email as $email_id) {
                $email_id = trim($email_id);
                if (!CommonHelper::isValidEmail($email_id)) {
                    continue;
                }
                /* email notification handling[ */
                if (!$emailNotificationObj->sendMailShareEarn(UserAuthentication::getLoggedUserId(), $email_id, $this->siteLangId)) {
                    Message::addErrorMessage(Labels::getLabel($emailNotificationObj->getError(), $this->siteLangId));
                    CommonHelper::redirectUserReferer();
                }
                /* ] */
            }
        }
        $this->set('msg', Labels::getLabel('MSG_INVITATION_EMAILS_SENT_SUCCESSFULLY', $this->siteLangId));
        $this->_template->render(false, false, 'json-success.php');
    }

    private function getFriendsSharingForm($langId)
    {
        $langId = FatUtility::int($langId);
        $frm = new Form('frmShareEarn');
        $fld = $frm->addTextArea(Labels::getLabel('FRM_FRIENDS_EMAIL', $langId), 'email');
        // $fld->htmlAfterField = ' <small>(' . Labels::getLabel('L_Use_commas_separate_emails', $langId) . ')</small>';
        $fld->requirements()->setRequired();
        $frm->addTextArea(Labels::getLabel('FRM_PERSONAL_MESSAGE', $langId), 'message');
        $frm->addSubmitButton('', 'btn_submit', Labels::getLabel('BTN_INVITE_YOUR_FRIENDS', $langId));
        return $frm;
    }

    private function getRewardPointSearchForm($langId)
    {
        $langId = FatUtility::int($langId);
        $frm = new Form('frmRewardPointSearch');
        $frm->addHiddenField('', 'page');
        $frm->addHiddenField('', 'convertReward');
        $frm->addHiddenField('', 'total_record_count', '');
        return $frm;
    }

    private function getOrderSearchForm($langId)
    {
        $currency_id = FatApp::getConfig('CONF_CURRENCY', FatUtility::VAR_INT, 1);
        $currencyData = Currency::getAttributesById($currency_id, array('currency_code', 'currency_symbol_left', 'currency_symbol_right'));
        $currencySymbol = ($currencyData['currency_symbol_left'] != '') ? $currencyData['currency_symbol_left'] : $currencyData['currency_symbol_right'];

        $frm = new Form('frmRecordSearch');
        $frm->addHiddenField('', 'page');
        $frm->addHiddenField('', 'total_record_count', '');
        $frm->addTextBox('', 'keyword');
        $frm->addSelectBox(Labels::getLabel('FRM_STATUS'), 'status', Orders::getOrderProductStatusArr($langId, unserialize(FatApp::getConfig("CONF_BUYER_ORDER_STATUS"))), '', array(), Labels::getLabel('FRM_Status', $langId));
        $frm->addDateField(Labels::getLabel('FRM_DATE_FROM'), 'date_from', '', array('placeholder' => Labels::getLabel('FRM_Date_From', $langId), 'readonly' => 'readonly'));
        $frm->addDateField(Labels::getLabel('FRM_DATE_TO'), 'date_to', '', array('placeholder' => Labels::getLabel('FRM_Date_To', $langId), 'readonly' => 'readonly'));
        $frm->addTextBox(Labels::getLabel('FRM_PRICE_FROM'), 'price_from', '', array('placeholder' => Labels::getLabel('FRM_Price_Min', $langId) . ' [' . $currencySymbol . ']'));
        $frm->addTextBox(Labels::getLabel('FRM_PRICE_TO'), 'price_to', '', array('placeholder' => Labels::getLabel('LBL_Price_Max', $langId) . ' [' . $currencySymbol . ']'));
        HtmlHelper::addSearchButton($frm);
        HtmlHelper::addClearButton($frm, 'btn btn-clear');
        return $frm;
    }

    private function getOrderProductDownloadSearchForm($langId)
    {
        $frm = new Form('frmSrch');
        $frm->addHiddenField('', 'op_id');
        $frm->addHiddenField('', 'total_record_count', '');
        $fld = $frm->addTextBox('', 'keyword');
        $fld->overrideFldType('search');
        $frm->addSubmitButton('', 'btn_submit', Labels::getLabel('BTN_SEARCH', $langId));
        $frm->addButton("", "btn_clear", Labels::getLabel("BTN_CLEAR", $langId), array('onclick' => 'clearSearch();'));
        $frm->addHiddenField('', 'page');
        return $frm;
    }

    private function getOrderCancelRequestForm($langId)
    {
        $frm = new Form('frmOrderCancel');
        $orderCancelReasonsArr = OrderCancelReason::getOrderCancelReasonArr($langId);
        $frm->addSelectBox(Labels::getLabel('FRM_REASON_FOR_CANCELLATION', $langId), 'ocrequest_ocreason_id', $orderCancelReasonsArr, '', array(), Labels::getLabel('FRM_SELECT_REASON', $langId))->requirements()->setRequired();
        $frm->addTextArea(Labels::getLabel('FRM_COMMENTS', $langId), 'ocrequest_message')->requirements()->setRequired();
        $frm->addHiddenField('', 'op_id');
        $frm->addSubmitButton('', 'btn_submit', Labels::getLabel('BTN_SEND_REQUEST', $langId));
        return $frm;
    }

    private function getOrderReturnRequestForm($langId, $opDetail = array())
    {
        $frm = new Form('frmOrderReturnRequest', array('enctype' => "multipart/form-data"));
        $op_qty = $opDetail["op_qty"] ?? 1;
        $fld = $frm->addRequiredField(Labels::getLabel('FRM_RETURN_QTY', $langId), 'orrequest_qty', $op_qty);
        $fld->requirements()->setInt();
        $fld->requirements()->setRange(1, $op_qty);
        $fld->overrideFldType('number');

        $orderReturnReasonsArr = OrderReturnReason::getOrderReturnReasonArr($langId);
        $frm->addSelectBox(Labels::getLabel('FRM_REASON_FOR_RETURN', $langId), 'orrequest_returnreason_id', $orderReturnReasonsArr, '', array(), Labels::getLabel('FRM_SELECT_REASON', $langId))->requirements()->setRequired();

        /* if( $opDetail['op_status_id'] != FatApp::getConfig("CONF_DEFAULT_DEIVERED_ORDER_STATUS") ){
          $requestTypeArr = OrderReturnRequest::getRequestTypeArr($langId);
          unset($requestTypeArr[OrderReturnRequest::RETURN_REQUEST_TYPE_REPLACE]);
          $frm->addRadioButtons( Labels::getLabel('FRM_RETURN_REQUEST_TYPE', $langId), 'orrequest_type', $requestTypeArr, OrderReturnRequest::RETURN_REQUEST_TYPE_REFUND )->requirements()->setRequired();
          } else {
          $frm->addRadioButtons( Labels::getLabel('FRM_RETURN_REQUEST_TYPE', $langId), 'orrequest_type', OrderReturnRequest::getRequestTypeArr($langId), OrderReturnRequest::RETURN_REQUEST_TYPE_REFUND )->requirements()->setRequired();
          } */

        // For now untill $requestTypeArr having single value
        $frm->addHiddenField('', 'orrequest_type', OrderReturnRequest::RETURN_REQUEST_TYPE_REFUND);

        $fileFld = $frm->addFileUpload(Labels::getLabel('FRM_UPLOAD_IMAGES', $langId), 'file', array('accept' => 'image/*,.zip'));
        $fileFld->htmlBeforeField = '<div class="filefield"><span class="filename"></span>';
        $fileFld->htmlAfterField = '</div><span class="form-text text-muted">' . Labels::getLabel('MSG_Only_Image_extensions_and_zip_is_allowed', $this->siteLangId) . '</span>';
        $frm->addTextArea(Labels::getLabel('FRM_COMMENTS', $langId), 'orrmsg_msg')->requirements()->setRequired();
        $frm->addHiddenField('', 'op_id');
        $frm->addSubmitButton('', 'btn_submit', Labels::getLabel('BTN_SEND_REQUEST', $langId));
        return $frm;
    }

    private function getOrderFeedbackForm($op_id, $langId, $ratingAspects)
    {
        $langId = FatUtility::int($langId);
        $frm = new Form('frmOrderFeedback');

        foreach ($ratingAspects as $aspectVal => $aspectLabel) {
            $fld = $frm->addSelectBox($aspectLabel, "review_rating[$aspectVal]", array("1" => "1", "2" => "2", "3" => "3", "4" => "4", "5" => "5"), "", array('class' => "star-rating"), Labels::getLabel('L_Rate', $langId));
            $fld->requirements()->setRequired(true);
            $fld->setWrapperAttribute('class', 'rating-f');
        }

        $frm->addRequiredField(Labels::getLabel('FRM_TITLE', $langId), 'spreview_title');
        $frm->addTextArea(Labels::getLabel('FRM_DESCRIPTION', $langId), 'spreview_description')->requirements()->setRequired();

        $frm->addFileUpload('', 'spreview_image[]', array('accept' => 'image/*', 'data-frm' => 'frmOrderFeedback'));

        $arr = ["{website-name}" => FatApp::getConfig("CONF_WEBSITE_NAME_" . $langId)];
        $frm->addCheckBox(strtr(Labels::getLabel('FRM_I_AGREE_THAT_MY_REVIEW,_including_my_name,_username,_may_be_shared_by_{website-name}_on_its_website_and_mobile_app_to_the_public._Further_details_of_which_are_set_out_in_the_Privacy_Policy_which_I_have_previously_consented', $langId), $arr), 'agree', 1);
        $frm->addHiddenField('', 'op_id', $op_id);
        $frm->addHiddenField('', 'referrer', CommonHelper::redirectUserReferer(true));
        $frm->addSubmitButton('', 'btn_submit', Labels::getLabel('BTN_SUBMIT', $langId));
        return $frm;
    }

    public function addItemsToCart($orderId)
    {
        if (0 < FatApp::getConfig('CONF_HIDE_PRICES', FatUtility::VAR_INT, 0)) {
            $message = Labels::getLabel('MSG_ITEMS_ARE_AVAILABLE_FOR_RFQ_ONLY', $this->siteLangId);
            LibHelper::exitWithError($message, true);
        }

        if (!$orderId) {
            $message = Labels::getLabel('MSG_Invalid_Access', $this->siteLangId);
            LibHelper::exitWithError($message, true);
        }
        $userId = UserAuthentication::getLoggedUserId();

        $orderObj = new Orders();
        $orderDetail = $orderObj->getOrderById($orderId, $this->siteLangId);
        if (!$orderDetail || ($orderDetail && $orderDetail['order_user_id'] != $userId)) {
            $message = Labels::getLabel('MSG_Invalid_Access', $this->siteLangId);
            LibHelper::exitWithError($message, true);
        }

        $cartObj = new Cart();
        $cartInfo = LibHelper::isJson($orderDetail['order_cart_data']) ? json_decode($orderDetail['order_cart_data'], true) : unserialize($orderDetail['order_cart_data']);
        unset($cartInfo['shopping_cart']);
        $outOfStock = false;
        $notAvailable = 0;
        foreach ($cartInfo as $key => $quantity) {
            $keyDecoded = LibHelper::isJson($orderDetail['order_cart_data']) ? json_decode(base64_decode($key), true) : unserialize(base64_decode($key));

            $selprod_id = 0;
            if (strpos($keyDecoded, Cart::CART_KEY_PREFIX_PRODUCT) !== false) {
                $selprod_id = FatUtility::int(str_replace(Cart::CART_KEY_PREFIX_PRODUCT, '', $keyDecoded));
            }
            
            $selProdData = SellerProduct::getAttributesById($selprod_id, ['selprod_stock', 'selprod_cart_type'], false);
            if (SellerProduct::CART_TYPE_RFQ_ONLY == $selProdData['selprod_cart_type']) {
                $notAvailable++;
                continue;
            }

            $selProdStock = $selProdData['selprod_stock'];
            if (!$selProdStock && $selProdStock <= 0) {
                $outOfStock = true;
                continue;
            }

            $product = $this->getProductDetail($selprod_id);
            if (!$product) {
                $notAvailable++;
            }
            $cartObj->add($selprod_id, $quantity);
        }

        if ($outOfStock) {
            $message = Labels::getLabel('MSG_PRODUCT_NOT_AVAILABLE_OR_OUT_OF_STOCK_SO_REMOVED_FROM_CART_LISTING', $this->siteLangId);
            LibHelper::exitWithError($message, true);
        }

        if (0 < $notAvailable) {
            $message = Labels::getLabel('ERR_CURRENTLY_THE_PRODUCT_IS_UNAVAILABLE', $this->siteLangId);
            if (1 < $notAvailable) {
                $message = Labels::getLabel('ERR_CURRENTLY_THE_PRODUCTS_ARE_UNAVAILABLE', $this->siteLangId);
                if (count($cartInfo) > $notAvailable) {
                    $message = Labels::getLabel('ERR_SOME_OF_THE_PRODUCTS_ARE_UNAVAILABLE', $this->siteLangId);
                }
            }
            LibHelper::exitWithError($message, true);
        }
        
        $cartObj->removeUsedRewardPoints();
        $cartObj->removeCartDiscountCoupon();
        $cartObj->removeProductShippingMethod();

        LibHelper::sendAsyncRequest('POST', UrlHelper::generateFullUrl('Cart', 'loadRates'), ['sessionId' => LibHelper::getSessionId()]);

        FatUtility::dieJsonSuccess(Labels::getLabel('LBL_SUCCESS'));
    }

    public function shareEarnUrl()
    {
        $userId = UserAuthentication::getLoggedUserId();
        if (!FatApp::getConfig("CONF_ENABLE_REFERRER_MODULE")) {
            FatUtility::dieJsonError(Labels::getLabel('ERR_THIS_MODULE_IS_NOT_ENABLED', $this->siteLangId));
        }
        $userObj = new User($userId);
        $userInfo = $userObj->getUserInfo(array('user_referral_code'), true, true);
        if (empty($userInfo['user_referral_code'])) {
            FatUtility::dieJsonError(Labels::getLabel('ERR_INVALID_USER', $this->siteLangId));
        }

        $referralTrackingUrl = CommonHelper::referralTrackingUrl($userInfo['user_referral_code']);

        $this->set('data', array('trackingUrl' => $referralTrackingUrl));
        $this->_template->render();
    }

    public function orderReceipt($orderId)
    {
        if (empty($orderId)) {
            $message = Labels::getLabel('MSG_Invalid_Access', $this->siteLangId);
            LibHelper::dieJsonError($message);
        }

        $emailObj = new EmailHandler();
        if (!$emailObj->newOrderBuyerAdmin($orderId, $this->siteLangId, false, false)) {
            $message = Labels::getLabel('MSG_Unable_to_notify_customer', $this->siteLangId);
            LibHelper::dieJsonError($message);
        }
        $this->set('msg', Labels::getLabel('MSG_Email_Sent', $this->siteLangId));
        $this->_template->render();
    }

    public function orderTrackingInfo($trackingNumber, $courier, $orderNumber)
    {
        if (empty($trackingNumber) || empty($courier)) {
            $message = Labels::getLabel('MSG_Invalid_request', $this->siteLangId);
            if (true === MOBILE_APP_API_CALL) {
                LibHelper::dieJsonError($message);
            }
            Message::addErrorMessage($message);
            FatUtility::dieWithError(Message::getHtml());
        }

        $shipmentTracking = new ShipmentTracking();
        if (false === $shipmentTracking->init($this->siteLangId)) {
            $message = $shipmentTracking->getError();
            if (true === MOBILE_APP_API_CALL) {
                LibHelper::dieJsonError($message);
            }
            Message::addErrorMessage($message);
            FatUtility::dieWithError(Message::getHtml());
        }

        $shipmentTracking->createTracking($trackingNumber, $courier, $orderNumber);

        if (false === $shipmentTracking->getTrackingInfo($trackingNumber, $courier)) {
            $message = $shipmentTracking->getError();
            if (true === MOBILE_APP_API_CALL) {
                LibHelper::dieJsonError($message);
            }
            Message::addErrorMessage($message);
            FatUtility::dieWithError(Message::getHtml());
        }
        $trackingInfo = $shipmentTracking->getResponse();
        $this->set('trackingInfo', $trackingInfo);
        if (true === MOBILE_APP_API_CALL) {
            $this->_template->render();
        }
        $this->_template->render(false, false);
    }

    public function updatePayment()
    {
        $frm = $this->getTransferBankForm($this->siteLangId);
        $post = $frm->getFormDataFromArray(FatApp::getPostedData());
        if (false === $post) {
            FatUtility::dieJsonError(current($frm->getValidationErrors()));
        }
        $orderId = $post['opayment_order_id'];

        $orderPaymentObj = new OrderPayment($orderId, $this->siteLangId);
        $orderInfo = $orderPaymentObj->getOrderPrimaryinfo();
        if (empty($orderInfo) || 1 >= count(array_filter($post))) {
            $msg = Labels::getLabel("MSG_INVALID_REQUEST", $this->siteLangId);
            FatUtility::dieJsonError($msg);
        }

        if (!$orderPaymentObj->addOrderPayment($post["opayment_method"], $post['opayment_gateway_txn_id'], $post["opayment_amount"], $post["opayment_comments"], '', false, 0, Orders::ORDER_PAYMENT_PENDING, true)) {
            FatUtility::dieJsonError($orderPaymentObj->getError());
        }

        $msg = Labels::getLabel("MSG_REQUEST_SUBMITTED_SUCCESSFULLY", $this->siteLangId);
        FatUtility::dieJsonSuccess($msg);
    }

    public function getCancellationRequestComment()
    {
        $recordId = FatApp::getPostedData('recordId', FatUtility::VAR_INT, 0);
        $this->set('comments', OrderCancelRequest::getAttributesById($recordId, 'ocrequest_message'));
        $this->set('html', $this->_template->render(false, false, NULL, true));
        $this->_template->render(false, false, 'json-success.php', true, false);
    }

    private function getSelProdReviewObj()
    {
        $selProdReviewObj = new SelProdReviewSearch();
        $selProdReviewObj->joinProducts($this->siteLangId);
        $selProdReviewObj->joinSellerProducts($this->siteLangId);
        $selProdReviewObj->joinSelProdRating();
        $selProdReviewObj->joinUser();
        // $selProdReviewObj->joinSelProdReviewHelpful();
        $selProdReviewObj->addCondition('ratingtype_type', 'IN', [RatingType::TYPE_PRODUCT, RatingType::TYPE_OTHER]);
        $selProdReviewObj->doNotCalculateRecords();
        $selProdReviewObj->doNotLimitRecords();
        $selProdReviewObj->addGroupBy('spr.spreview_product_id');
        // $selProdReviewObj->addGroupBy('sprh_spreview_id');
        $selProdReviewObj->addCondition('spr.spreview_status', '=', SelProdReview::STATUS_APPROVED);
        $selProdReviewObj->addMultipleFields(array('spr.spreview_selprod_id', 'spr.spreview_product_id', "ROUND(AVG(sprating_rating),2) as prod_rating", "COUNT(DISTINCT(spreview_id)) AS totReviews"));
        return $selProdReviewObj;
    }

    private function getProductDetail(int $selprod_id)
    {
        $prodSrchObj = new ProductSearch($this->siteLangId);
        $productId = SellerProduct::getAttributesById($selprod_id, 'selprod_product_id');
        if (empty($productId)) {
            if (true === MOBILE_APP_API_CALL) {
                LibHelper::exitWithError(Labels::getLabel('ERR_INVALID_PRODUCT'));
            }
            FatUtility::exitWithErrorCode(404);
        }
        /* fetch requested product[ */
        $prodSrch = clone $prodSrchObj;
        $prodSrch->setLocationBasedInnerJoin(false);
        $prodSrch->setGeoAddress();
        $prodSrch->setDefinedCriteria(0, 0, array('product_id' => $productId), false);
        $prodSrch->joinProductToCategory();
        $prodSrch->joinShopSpecifics();
        $prodSrch->joinProductSpecifics();
        $prodSrch->joinSellerProductSpecifics();
        $prodSrch->joinSellerSubscription();
        $prodSrch->addSubscriptionValidCondition();
        $prodSrch->validateAndJoinDeliveryLocation(false);
        $prodSrch->joinProductToTax();
        $prodSrch->doNotCalculateRecords();
        $prodSrch->addCondition('selprod_id', '=', $selprod_id);
        $prodSrch->addCondition('selprod_deleted', '=', applicationConstants::NO);
        $prodSrch->doNotLimitRecords();

        /* sub query to find out that logged user have marked current product as in wishlist or not[ */
        $loggedUserId = 0;
        if (UserAuthentication::isUserLogged()) {
            $loggedUserId = UserAuthentication::getLoggedUserId();
        }
        if (FatApp::getConfig('CONF_ADD_FAVORITES_TO_WISHLIST', FatUtility::VAR_INT, 1) == applicationConstants::NO) {
            $prodSrch->joinFavouriteProducts($loggedUserId);
            $prodSrch->addFld('IFNULL(ufp_id, 0) as ufp_id');
        } else {
            $prodSrch->joinUserWishListProducts($loggedUserId);
            $prodSrch->addFld('COALESCE(uwlp.uwlp_selprod_id, 0) as is_in_any_wishlist');
        }

        $selProdReviewObj = $this->getSelProdReviewObj();
        $selProdReviewObj->addCondition('spr.spreview_product_id', '=', 'mysql_func_' . $productId, 'AND', true);
        $prodSrch->joinTable('(' . $selProdReviewObj->getQuery() . ')', 'LEFT OUTER JOIN', 'sq_sprating.spreview_product_id = product_id', 'sq_sprating');
        $prodSrch->addMultipleFields(
            array(
                'product_id',
                'selprod_sku',
                'product_identifier',
                'COALESCE(product_name,product_identifier) as product_name',
                'product_seller_id',
                'product_model',
                'product_type',
                'prodcat_id',
                'COALESCE(prodcat_name,prodcat_identifier) as prodcat_name',
                'product_upc',
                'product_isbn',
                'product_short_description',
                'product_description',
                'selprod_id',
                'selprod_user_id',
                'selprod_code',
                'selprod_condition',
                'selprod_price',
                'special_price_found',
                'splprice_start_date',
                'splprice_end_date',
                'COALESCE(selprod_title, product_name, product_identifier) as selprod_title',
                'selprod_warranty',
                'selprod_return_policy',
                'selprodComments',
                'theprice',
                'selprod_stock',
                'selprod_threshold_stock_level',
                'IF(selprod_stock > 0, 1, 0) AS in_stock',
                'brand_id',
                'COALESCE(brand_name, brand_identifier) as brand_name',
                'brand_short_description',
                'user_name',
                'shop_id',
                'COALESCE(shop_name, shop_identifier) as shop_name',
                'COALESCE(sq_sprating.prod_rating,0) prod_rating ',
                'COALESCE(sq_sprating.totReviews,0) totReviews',
                'splprice_display_dis_type',
                'splprice_display_dis_val',
                'splprice_display_list_price',
                'product_attrgrp_id',
                'product_youtube_video',
                'product_cod_enabled',
                'selprod_cod_enabled',
                'selprod_available_from',
                'selprod_min_order_qty',
                'product_updated_on',
                'product_warranty',
                'selprod_return_age',
                'selprod_cancellation_age',
                'shop_return_age',
                'shop_cancellation_age',
                'selprod_fulfillment_type',
                'shop_fulfillment_type',
                'product_fulfillment_type',
                'product_attachements_with_inventory',
                'selprod_product_id',
                'COALESCE(shop_state_l.state_name,state_identifier) as shop_state_name',
                'COALESCE(shop_country_l.country_name,shop_country.country_code) as shop_country_name',
                'selprod_condition',
                'product_warranty_unit', 'selprod_cart_type', 'selprod_hide_price', 'shop_rfq_enabled'
            )
        );
        $productRs = $prodSrch->getResultSet();
        $row = FatApp::getDb()->fetch($productRs);
        return (is_array($row) ? $row : []);
    }

    public function getBreadcrumbNodes($action)
    {
        if (FatUtility::isAjaxCall()) {
            return;
        }

        $className = get_class($this);
        $arr = explode('-', FatUtility::camel2dashed($className));
        array_pop($arr);
        $className = ucwords(implode('_', $arr));

        if ($action == 'index') {
            $this->nodes[] = array('title' => ucwords(Labels::getLabel('BCN_' . $className)));
        } else if ($action == 'viewOrder') {
            $action = str_replace('-', '_', FatUtility::camel2dashed($action));
            $this->nodes[] = array('title' => Labels::getLabel('LBL_ORDERS'), 'href' => UrlHelper::generateUrl("Buyer", "Orders"));
            $this->nodes[] = array('title' => ucwords(Labels::getLabel('BCN_' . $action)));
        } else if ($action == 'orderFeedback') {
            $params = FatApp::getParameters();
            $orderId = OrderProduct::getAttributesById(current($params), 'op_order_id');
            $action = str_replace('-', '_', FatUtility::camel2dashed($action));
            $this->nodes[] = array('title' => Labels::getLabel('LBL_ORDERS'), 'href' => UrlHelper::generateUrl("Buyer", "orders"));
            $this->nodes[] = array('title' => Labels::getLabel('LBL_VIEW_ORDER'), 'href' => UrlHelper::generateUrl("Buyer", "viewOrder", [$orderId, current($params)]));
            $this->nodes[] = array('title' => ucwords(Labels::getLabel('BCN_' . $action)));
        } else if ($action == 'viewOrderReturnRequest') {
            $action = str_replace('-', '_', FatUtility::camel2dashed($action));
            $this->nodes[] = array('title' => Labels::getLabel('LBL_ORDER_RETURN_REQUESTS'), 'href' => UrlHelper::generateUrl("Buyer", "orderReturnRequests"));
            $this->nodes[] = array('title' => ucwords(Labels::getLabel('BCN_' . $action)));
        } else if ($action == 'orderCancellationRequest') {
            $action = str_replace('-', '_', FatUtility::camel2dashed($action));
            $this->nodes[] = array('title' => Labels::getLabel('LBL_ORDERS'), 'href' => UrlHelper::generateUrl("Buyer", "orders"));
            $this->nodes[] = array('title' => ucwords(Labels::getLabel('BCN_' . $action)));
        } else {
            $action = str_replace('-', '_', FatUtility::camel2dashed($action));
            $this->nodes[] = array('title' => ucwords(Labels::getLabel('BCN_' . $action)));
        }
        return $this->nodes;
    }

    public function giftCards()
    {
        $isSplitPaymentMethod = Plugin::isSplitPaymentEnabled($this->siteLangId);
        if ($isSplitPaymentMethod) {
            LibHelper::exitWithError(Labels::getLabel('ERR_UNAUTHORISED_ACCESS'), false, true);
            CommonHelper::redirectUserReferer();
        }
        $frm = $this->getGiftCardSearchForm($this->siteLangId);
        $this->set('keywordPlaceholder', Labels::getLabel('FRM_SEARCH_BY_RECEIVER_NAME,_EMAIL_OR_CODE', $this->siteLangId));
        $this->set('frmSearch', $frm);
        $this->_template->render(true, true);
    }

    public function searchGiftCards()
    {
        $isSplitPaymentMethod = Plugin::isSplitPaymentEnabled($this->siteLangId);
        if ($isSplitPaymentMethod) {
            LibHelper::exitWithError(Labels::getLabel('ERR_UNAUTHORISED_ACCESS'));
        }
        $frm = $this->getGiftCardSearchForm($this->siteLangId);
        $post = $frm->getFormDataFromArray(FatApp::getPostedData());
        $page = FatApp::getPostedData('page', FatUtility::VAR_INT, 1);
        if ($page < 2) {
            $page = 1;
        }
        $keyword = $post['keyword'];
        $pagesize = FatApp::getConfig('conf_page_size', FatUtility::VAR_INT, 10);

        $srch = GiftCards::getSearchObject();
        $srch->joinTable(Orders::DB_TBL, 'INNER JOIN', 'ogc.ogcards_order_id = orders.order_id', 'orders');
        $srch->addCondition('ogcards_sender_id', '=', UserAuthentication::getLoggedUserId());
        if (!empty($keyword)) {
            $cond = $srch->addCondition('ogcards_receiver_name', 'like', '%' . $keyword . '%');
            $cond->attachCondition('ogcards_receiver_email', 'like', '%' . $keyword . '%');
            $cond->attachCondition('ogcards_code', 'like', '%' . $keyword . '%');
        }
        $orderUsed = FatApp::getPostedData('ogcards_status', FatUtility::VAR_INT, -1);
        if ($orderUsed >= 0) {
            $cond = $srch->addCondition('ogcards_status', '=', $orderUsed);
        }

        $paymetType = FatApp::getPostedData('order_payment_status', FatUtility::VAR_INT, -1);
        if ($paymetType >= 0) {
            $cond = $srch->addCondition('order_payment_status', '=', $paymetType);
        }
        $fromDate = FatApp::getPostedData('date_from', FatUtility::VAR_DATE, '');
        if (!empty($fromDate)) {
            $cond = $srch->addCondition('ogcards_created_on', '>=', $fromDate);
        }

        $toDate = FatApp::getPostedData('date_to', FatUtility::VAR_DATE, '');
        if (!empty($toDate)) {
            $cond = $srch->addCondition('ogcards_created_on', '<=', $toDate, 'and', true);
        }

        $srch->addMultipleFields(array('ogcards_id', 'ogcards_order_id', 'ogcards_code', 'ogcards_sender_id', 'ogcards_receiver_name', 'ogcards_receiver_email', 'ogcards_status', 'ogcards_created_on', 'order_payment_status', 'ogcards_created_on','order_net_amount'));

        $srch->doNotCalculateRecords();
        $recordCountSrch = clone $srch;
        $srch->doNotCalculateRecords();
        $srch->setPageNumber($page);
        $srch->setPageSize($pagesize);
        $srch->addOrder('ogcards_created_on', 'DESC');
        $records = FatApp::getDb()->fetchAll($srch->getResultSet());

        $this->setRecordCount($recordCountSrch, $pagesize, $page, $post, false);
        $orderPaymentStatusArr = Orders::getOrderPaymentStatusArr($this->siteLangId);
        unset($orderPaymentStatusArr[Orders::ORDER_PAYMENT_CANCELLED]);
        $this->set('orderPaymentStatusArr', $orderPaymentStatusArr);
        $this->set('useStatusArr', GiftCards::getStatusArr($this->siteLangId));
        $this->set('arrListing', $records);
        $this->set('postedData', $post);
        $this->set('siteLangId', $this->siteLangId);

        if (MOBILE_APP_API_CALL) {
            $this->_template->render();
            return;
        }

        $this->_template->render(false, false);
    }

    private function getGiftCardSearchForm($langId)
    {
        $frm = new Form('frmRecordSearch');
        $frm->addHiddenField('', 'total_record_count', '');
        $frm->addHiddenField('', 'page');
        $frm->addTextBox(Labels::getLabel('FRM_KEYWORD', $langId), 'keyword', '');

        $useStatusArr = GiftCards::getStatusArr($langId);
        $frm->addSelectBox(Labels::getLabel('FRM_GIFT_CARD_USED', $langId), 'ogcards_status', array(-1 => Labels::getLabel('FRM_SELECT', $langId)) + $useStatusArr, -1, array(), '');

        $orderStatusArr = Orders::getOrderPaymentStatusArr($langId);
        unset($orderStatusArr[Orders::ORDER_PAYMENT_CANCELLED]);
        $frm->addSelectBox(Labels::getLabel('FRM_PAYMENT_TYPE', $langId), 'order_payment_status', array(-1 => Labels::getLabel('FRM_SELECT', $langId)) + $orderStatusArr, -1, array(), '');
        $frm->addDateField(Labels::getLabel('FRM_DATE_FROM', $langId), 'date_from', '', array('readonly' => 'readonly', 'class' => 'field--calender'));
        $frm->addDateField(Labels::getLabel('FRM_DATE_TO', $langId), 'date_to', '', array('readonly' => 'readonly', 'class' => 'field--calender'));
        HtmlHelper::addSearchButton($frm);
        HtmlHelper::addClearButton($frm, 'btn btn-clear');
        return $frm;
    }

    public function giftCardForm()
    {
        $isSplitPaymentMethod = Plugin::isSplitPaymentEnabled($this->siteLangId);
        if ($isSplitPaymentMethod) {
            LibHelper::exitWithError(Labels::getLabel('ERR_UNAUTHORISED_ACCESS'));
        }
        $this->set('frm', $this->getForm());
        $this->set('currency',  Currency::getAttributesById(CommonHelper::getCurrencyId()));
        $this->set('minAmount', FatApp::getConfig('CONF_MINIMUM_GIFT_CARD_AMOUNT', FatUtility::VAR_FLOAT, 100));
        $this->_template->render(false, false);
    }

    private function getForm(): Form
    {
        $currency = Currency::getAttributesById(CommonHelper::getCurrencyId());
        $lbl = CommonHelper::replaceStringData(Labels::getLabel('LBL_ENTER_AMOUNT_({CURRENCY-CODE})'), ['{CURRENCY-CODE}' => $currency['currency_code']]);

        $frm = new Form('frmAddMoney');
        $fld = $frm->addRequiredField($lbl, 'order_total_amount');
        $fld->requirements()->setInt();
        $fld->requirements()->setRange('1', '99999999');
        $frm->addRequiredField(Labels::getLabel('LBL_RECEIVER_NAME'), 'ogcards_receiver_name');
        $frm->addEmailField(Labels::getLabel('LBL_RECEIVER_EMAIL'), 'ogcards_receiver_email');
        return $frm;
    }

    public function setupGiftCard()
    {
        $isSplitPaymentMethod = Plugin::isSplitPaymentEnabled($this->siteLangId);
        if ($isSplitPaymentMethod) {
            LibHelper::exitWithError(Labels::getLabel('ERR_UNAUTHORISED_ACCESS'), true);
        }
        $frm = $this->getForm();
        if (!$post = $frm->getFormDataFromArray(FatApp::getPostedData())) {
            LibHelper::exitWithError(current($frm->getValidationErrors()), true);
        }

        $userData = $this->userInfo;
        $minAmount = FatApp::getConfig('CONF_MINIMUM_GIFT_CARD_AMOUNT', FatUtility::VAR_FLOAT, 100);
        if ($post['ogcards_receiver_email'] == $userData['credential_email']) {
            LibHelper::exitWithError(Labels::getLabel('ERR_YOU_CANNOT_BUY_GIFTCARD_FOR_YOURSELF'), true);
        }

        if (FatUtility::int($post['order_total_amount']) < $minAmount) {
            $lbl = Labels::getLabel('LBL_AMOUNT_SHOULD_BE_GREATER_THEN_({MIN-AMOUNT})');
            $lbl = CommonHelper::replaceStringData($lbl, ['{MIN-AMOUNT}' => $minAmount]);
            LibHelper::exitWithError($lbl, true);
        }

        $post['order_language_id'] = $this->siteLangId;
        $post['order_language_code'] = CommonHelper::getLangCode();
        $order = new Orders(0);
        $orderId  = $order->placeGiftcardOrder($post);
        if (empty($orderId)) {
            LibHelper::exitWithError($order->getError(), true);
        }

        if (true === MOBILE_APP_API_CALL) {
            $excludePaymentGatewaysArr = applicationConstants::getExcludePaymentGatewayArr(applicationConstants::CHECKOUT_GIFT_CARD);
            /* Payment Methods[ */
            $pmSrch = PaymentMethods::getSearchObject($this->siteLangId);
            $pmSrch->doNotCalculateRecords();
            $pmSrch->doNotLimitRecords();
            $pmSrch->addMultipleFields(Plugin::ATTRS);
            $pmSrch->addCondition('plugin_code', 'not in ', $excludePaymentGatewaysArr);
            $pmRs = $pmSrch->getResultSet();
            $paymentMethods = FatApp::getDb()->fetchAll($pmRs);
            /* ] */

            $userWalletBalance = User::getUserBalance($this->userParentId, true);
            $this->set('userWalletBalance', $userWalletBalance);
            $this->set('displayUserWalletBalance', CommonHelper::displayMoneyFormat($userWalletBalance));
            $this->set('canUseWalletForPayment', PaymentMethods::canUseWalletForPayment());
            $this->set('orderNetAmount', $post['order_total_amount']);
            $this->set('paymentMethods', $paymentMethods);
            $this->set('order_id', $orderId);
            $this->set('orderType', Orders::ORDER_GIFT_CARD);
            $this->_template->render();
        }

        $redirectUrl = UrlHelper::generateFullUrl('Checkout', 'giftCharge', [$orderId], CONF_WEBROOT_FRONT_URL);
        FatUtility::dieJsonSuccess(['msg' => Labels::getLabel('MSG_REDIRECTING_PLEASE_WAIT'), 'redirectUrl' => $redirectUrl]);
    }
}
