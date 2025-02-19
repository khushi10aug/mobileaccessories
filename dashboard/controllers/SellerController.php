<?php

class SellerController extends SellerBaseController
{
    use RecordOperations;
    use Options;
    use SellerProducts;
    use SellerCollections;
    use SellerUsers;
    use ProductDigitalDownloads;
    use ShippingServices;

    private $paymentPlugin;
    private $method = '';

    public function __construct($action)
    {
        $this->method = $action;
        parent::__construct($action);
    }

    public function index()
    {
        $userId = $this->userParentId;
        $user = new User($userId);
        $_SESSION[UserAuthentication::SESSION_ELEMENT_NAME]['activeTab'] = 'S';

        $ocSrch = new SearchBase(OrderProduct::DB_TBL_CHARGES, 'opc');
        $ocSrch->doNotCalculateRecords();
        $ocSrch->doNotLimitRecords();
        $ocSrch->addMultipleFields(array('opcharge_op_id', 'sum(opcharge_amount) as op_other_charges'));
        $ocSrch->addGroupBy('opc.opcharge_op_id');
        $qryOtherCharges = $ocSrch->getQuery();

        $srch = new OrderProductSearch($this->siteLangId, true, true);
        $srch->addStatusCondition(unserialize(FatApp::getConfig("CONF_VENDOR_ORDER_STATUS", FatUtility::VAR_STRING, '')));
        $srch->joinSellerProducts();
        $srch->joinShippingUsers();
        $srch->joinShippingCharges();
        $srch->addCountsOfOrderedProducts();
        $srch->joinTable('(' . $qryOtherCharges . ')', 'LEFT OUTER JOIN', 'op.op_id = opcc.opcharge_op_id', 'opcc');
        //$srch->addSellerOrderCounts(date('Y-m-d',strtotime("-1 days")),date('Y-m-d'),'yesterdayOrder');
        $srch->addCondition('op_selprod_user_id', '=', 'mysql_func_' . $userId, 'AND', true);

        $srch->addOrder("op_id", "DESC");
        $srch->setPageNumber(1);
        $srch->setPageSize(2);

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
                'op_id',
                'op_qty',
                'op_selprod_options',
                'op_status_id',
                'op_brand_name',
                'op_other_charges',
                'op_unit_price',
                'IFNULL(orderstatus_name, orderstatus_identifier) as orderstatus_name',
                'op_tax_collected_by_seller',
                'op_selprod_user_id',
                'opshipping_by_seller_user_id',
                'orderstatus_color_class',
                'order_pmethod_id',
                'opshipping_fulfillment_type',
                'op_rounding_off'
            )
        );

        $rs = $srch->getResultSet();
        $orders = FatApp::getDb()->fetchAll($rs);

        $oObj = new Orders();
        foreach ($orders as &$order) {
            $charges = $oObj->getOrderProductChargesArr($order['op_id']);
            $order['charges'] = $charges;
        }

        /* Orders Counts [ */
        $orderSrch = new OrderProductSearch($this->siteLangId, true, true);
        $orderSrch->doNotCalculateRecords();
        $orderSrch->doNotLimitRecords();
        $orderSrch->addSellerOrdersCounts(date('Y-m-d'), date('Y-m-d'), 'todayOrder');
        $orderSrch->addSellerCompletedOrdersStats(date('Y-m-d'), date('Y-m-d'), 'todaySold');

        $orderSrch->addSellerCompletedOrdersStats(false, false, 'totalSold');
        $orderSrch->addSellerInprocessOrdersStats(false, false, 'totalInprocess');
        $orderSrch->addSellerRefundedOrdersStats();
        $orderSrch->addSellerCancelledOrdersStats();
        $orderSrch->addGroupBy('order_user_id');
        $orderSrch->addCondition('op_selprod_user_id', '=', 'mysql_func_' . $userId, 'AND', true);
        $orderSrch->addMultipleFields(array('todayOrderCount', 'totalInprocessSales', 'totalSoldSales', 'totalSoldCount', 'refundedOrderCount', 'refundedOrderAmount', 'cancelledOrderCount', 'cancelledOrderAmount'));
        $rs = $orderSrch->getResultSet();
        $ordersStats = FatApp::getDb()->fetch($rs);
        /* ] */

        $orderObj = new Orders();
        $notAllowedStatues = $orderObj->getNotAllowedOrderCancellationStatuses();

        /* Remaining Products and Days Count [ */
        if (FatApp::getConfig('CONF_ENABLE_SELLER_SUBSCRIPTION_MODULE')) {
            $products = new Product();

            $latestOrder = OrderSubscription::getUserCurrentActivePlanDetails($this->siteLangId, $userId, array('ossubs_till_date', 'ossubs_id', 'ossubs_inventory_allowed', 'ossubs_subscription_name'));
            $pendingDaysForCurrentPlan = 0;
            $remainingAllowedProducts = 0;
            if ($latestOrder) {
                $pendingDaysForCurrentPlan = FatDate::diff(date("Y-m-d"), $latestOrder['ossubs_till_date']);
                $totalProducts = $products->getTotalProductsAddedByUser($userId);
                $remainingAllowedProducts = $latestOrder['ossubs_inventory_allowed'] - $totalProducts;
                $this->set('subscriptionTillDate', $latestOrder['ossubs_till_date']);
                $this->set('subscriptionName', $latestOrder['ossubs_subscription_name']);
            }

            $this->set('pendingDaysForCurrentPlan', $pendingDaysForCurrentPlan);
            $this->set('remainingAllowedProducts', $remainingAllowedProducts);
        }
        /* ] */

        /*
         * Return Request Listing
         */
        $srchReturnReq = $this->returnReuestsListingObj();

        $srchReturnReq->addMultipleFields(
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
                'op_selprod_id',
                'op_is_batch',
                'op_id',
                'selprod_product_id'
            )
        );
        $srchReturnReq->addOrder('orrequest_date', 'DESC');
        $srchReturnReq->setPageSize(applicationConstants::DASHBOARD_PAGE_SIZE);
        $rs = $srchReturnReq->getResultSet();
        $returnRequests = FatApp::getDb()->fetchAll($rs);

        /*
         * Transactions Listing
         */
        $transSrch = Transactions::getUserTransactionsObj($userId);
        $transSrch->doNotCalculateRecords();
        $transSrch->setPageSize(applicationConstants::DASHBOARD_PAGE_SIZE);
        $rs = $transSrch->getResultSet();
        $transactions = FatApp::getDb()->fetchAll($rs, 'utxn_id');
        /*
         * Cancellation Request Listing
         */
        $canSrch = $this->cancelRequestListingObj();
        $srch->addMultipleFields(array('ocrequest_id', 'ocrequest_date', 'ocrequest_status', 'order_id', 'order_number', 'op_invoice_number', 'op_id', 'IFNULL(ocreason_title, ocreason_identifier) as ocreason_title', 'ocrequest_message', 'op_selprod_title', 'op_product_name', 'op_selprod_id', 'op_is_batch'));
        $srch->addOrder('ocrequest_date', 'DESC');
        $canSrch->setPageSize(applicationConstants::DASHBOARD_PAGE_SIZE);
        $rs = $canSrch->getResultSet();
        $cancellationRequests = FatApp::getDb()->fetchAll($rs);
        $this->set('returnRequestsCount', $srchReturnReq->recordCount());

        $txnObj = new Transactions();
        $txnsSummary = $txnObj->getTransactionSummary($userId, date('Y-m-d'));

        $isShopActive = $this->isShopActive($this->userParentId);

        $this->set('transactions', $transactions);
        $this->set('returnRequests', $returnRequests);
        $this->set('OrderReturnRequestStatusArr', OrderReturnRequest::getRequestStatusArr($this->siteLangId));
        $this->set('OrderRetReqStatusClassArr', OrderReturnRequest::getRequestStatusClassArr());
        $this->set('cancellationRequests', $cancellationRequests);
        $this->set('txnStatusArr', Transactions::getStatusArr($this->siteLangId));
        $this->set('txnStatusClassArr', Transactions::getStatusClassArr());
        $this->set('OrderCancelRequestStatusArr', OrderCancelRequest::getRequestStatusArr($this->siteLangId));
        $this->set('cancelReqStatusClassArr', OrderCancelRequest::getStatusClassArr());
        $this->set('txnsSummary', $txnsSummary);
        $this->set('notAllowedStatues', $notAllowedStatues);
        $this->set('orders', $orders);
        $this->set('ordersCount', $srch->recordCount());
        $this->set('data', $user->getProfileData());
        $this->set('userBalance', User::getUserBalance($userId));
        $this->set('ordersStats', $ordersStats);
        $this->set('dashboardStats', Stats::getUserSales($userId));
        $this->set('userParentId', $this->userParentId);
        $this->set('userPrivilege', $this->userPrivilege);
        $this->set('isShopActive', $isShopActive);
        $this->set('classArr', applicationConstants::getClassArr());
        $this->_template->addJs(array('js/chartist.min.js'));
        $this->_template->addJs('js/slick.min.js');
        $this->_template->render();
    }

    public function sales()
    {
        $this->userPrivilege->canViewSales(UserAuthentication::getLoggedUserId());
        $data = FatApp::getPostedData();
        $frmSearch = $this->getOrderSearchForm();
        if (!empty($data)) {
            $frmSearch->fill($data);
        }

        $this->set('frmSearch', $frmSearch);
        $this->set('canEdit', $this->userPrivilege->canEditSales(UserAuthentication::getLoggedUserId(), true));
        $this->set('keywordPlaceholder', Labels::getLabel('FRM_SEARCH_BY_ORDER_ID_OR_ORDER_DETAIL', $this->siteLangId));
        $this->_template->render(true, true);
    }

    public function orderProductSearchListing()
    {
        $frm = $this->getOrderSearchForm();
        $post = $frm->getFormDataFromArray(FatApp::getPostedData());
        $page = (empty($post['page']) || $post['page'] <= 0) ? 1 : FatUtility::int($post['page']);
        $pagesize = FatApp::getConfig('conf_page_size', FatUtility::VAR_INT, 10);

        $userId = $this->userParentId;

        $ocSrch = new SearchBase(OrderProduct::DB_TBL_CHARGES, 'opc');
        $ocSrch->doNotCalculateRecords();
        $ocSrch->doNotLimitRecords();
        $ocSrch->addMultipleFields(array('opcharge_op_id', 'sum(opcharge_amount) as op_other_charges'));
        $ocSrch->addGroupBy('opc.opcharge_op_id');
        $qryOtherCharges = $ocSrch->getQuery();

        $srch = new OrderProductSearch($this->siteLangId, true, true);
        $srch->joinSellerProducts();
        $srch->joinPaymentMethod();
        $srch->joinShippingUsers();
        $srch->joinShippingCharges(true);
        $srch->addCountsOfOrderedProducts();
        $srch->joinOrderProductShipment();
        $srch->joinTable('(' . $qryOtherCharges . ')', 'LEFT OUTER JOIN', 'op.op_id = opcc.opcharge_op_id', 'opcc');
        $srch->addCondition('op_selprod_user_id', '=', $userId);

        $keyword = FatApp::getPostedData('keyword', null, '');
        if (!empty($keyword)) {
            $srch->joinOrderUser();
            $srch->addKeywordSearch($keyword);
        }

        $op_status_id = FatApp::getPostedData('status', null, '0');
        if (in_array($op_status_id, unserialize(FatApp::getConfig("CONF_VENDOR_ORDER_STATUS")))) {
            $srch->addStatusCondition($op_status_id, ($op_status_id == FatApp::getConfig("CONF_DEFAULT_CANCEL_ORDER_STATUS")));
        } else {
            $srch->addStatusCondition(unserialize(FatApp::getConfig("CONF_VENDOR_ORDER_STATUS")), ($op_status_id == FatApp::getConfig("CONF_DEFAULT_CANCEL_ORDER_STATUS")));
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
            $srch->addMinPriceCondition($priceFrom);
        }

        $priceTo = FatApp::getPostedData('price_to', null, '');
        if (!empty($priceTo)) {
            $srch->addMaxPriceCondition($priceTo);
        }

        $shippedById = FatApp::getPostedData('opshipping_by_seller_user_id');
        if ('' != $shippedById) {
            $srch->addCondition('opshipping_by_seller_user_id', '=', $shippedById);
        }

        $this->setRecordCount(clone $srch, $pagesize, $page, $post);
        $srch->doNotCalculateRecords();
        $srch->addOrder("op_id", "DESC");
        $srch->setPageNumber($page);
        $srch->setPageSize($pagesize);
        $srch->addMultipleFields(
            array('order_number', 'order_id', 'order_status', 'order_payment_status', 'order_user_id', 'op_selprod_id', 'op_is_batch', 'selprod_product_id', 'order_date_added', 'order_net_amount', 'op_invoice_number', 'totCombinedOrders as totOrders', 'op_selprod_title', 'op_product_name', 'op_id', 'op_qty', 'op_selprod_options', 'op_brand_name', 'op_shop_name', 'op_other_charges', 'op_unit_price', 'op_tax_collected_by_seller', 'op_selprod_user_id', 'opshipping_by_seller_user_id', 'orderstatus_id', 'IFNULL(orderstatus_name, orderstatus_identifier) as orderstatus_name', 'orderstatus_color_class', 'plugin_code', 'IFNULL(plugin_name, IFNULL(plugin_identifier, "Wallet")) as plugin_name', 'opship.*', 'opr_response', 'opshipping_fulfillment_type', 'op_rounding_off', 'op_product_type', 'op_status_id', 'opshipping_carrier_code', 'opshipping_service_code')
        );

        $orders = FatApp::getDb()->fetchAll($srch->getResultSet());
        $oObj = new Orders();
        foreach ($orders as &$order) {
            $charges = $oObj->getOrderProductChargesArr($order['op_id']);
            $order['charges'] = $charges;
        }
        $this->set('orders', $orders);
        $this->set('postedData', $post);
        $this->set('classArr', applicationConstants::getClassArr());
        $this->set('canEdit', $this->userPrivilege->canEditSales(UserAuthentication::getLoggedUserId(), true));
        $this->_template->render(false, false);
    }

    private function getOrderSearchForm()
    {
        $currency_id = FatApp::getConfig('CONF_CURRENCY', FatUtility::VAR_INT, 1);
        $currencyData = Currency::getAttributesById($currency_id, array('currency_code', 'currency_symbol_left', 'currency_symbol_right'));
        $currencySymbol = ($currencyData['currency_symbol_left'] != '') ? $currencyData['currency_symbol_left'] : $currencyData['currency_symbol_right'];
        $frm = new Form('frmOrderSrch');
        $frm->addHiddenField('', 'total_record_count');
        $frm->addHiddenField('', 'page');
        $frm->addTextBox(Labels::getLabel('FRM_KEYWORD', $this->siteLangId), 'keyword', '', array('placeholder' => Labels::getLabel('LBL_Keyword', $this->siteLangId)));

        $vendors = [
            0 => Labels::getLabel('FRM_ADMIN', $this->siteLangId),
            UserAuthentication::getLoggedUserId() => Labels::getLabel('FRM_ME', $this->siteLangId),
        ];
        $frm->addSelectBox(Labels::getLabel('FRM_SHIPPPED_BY', $this->siteLangId), 'opshipping_by_seller_user_id', $vendors, '', array('title' => Labels::getLabel('LBL_SHIPPED_BY', $this->siteLangId)), Labels::getLabel('LBL_FULLFILED_BY', $this->siteLangId));

        $frm->addSelectBox(Labels::getLabel('FRM_STATUS', $this->siteLangId), 'status', Orders::getOrderProductStatusArr($this->siteLangId, unserialize(FatApp::getConfig("CONF_VENDOR_ORDER_STATUS"))), '', array(), Labels::getLabel('LBL_Status', $this->siteLangId));
        $frm->addTextBox(Labels::getLabel('FRM_PRICE_FROM', $this->siteLangId), 'price_from', '', array('placeholder' => Labels::getLabel('LBL_Price_Min', $this->siteLangId) . ' [' . $currencySymbol . ']'));
        $frm->addTextBox(Labels::getLabel('FRM_PRICE_TO', $this->siteLangId), 'price_to', '', array('placeholder' => Labels::getLabel('LBL_Price_Max', $this->siteLangId) . ' [' . $currencySymbol . ']'));
        $frm->addDateField(Labels::getLabel('FRM_DATE_FROM', $this->siteLangId), 'date_from', '', array('placeholder' => Labels::getLabel('LBL_Date_From', $this->siteLangId), 'readonly' => 'readonly'));
        $frm->addDateField(Labels::getLabel('FRM_DATE_TO', $this->siteLangId), 'date_to', '', array('placeholder' => Labels::getLabel('LBL_Date_To', $this->siteLangId), 'readonly' => 'readonly'));

        HtmlHelper::addSearchButton($frm);
        HtmlHelper::addClearButton($frm, 'btn btn-clear');
        return $frm;
    }

    public function orderSearchListing()
    {
        $this->userPrivilege->canViewSubscription(UserAuthentication::getLoggedUserId());
        if (!FatApp::getConfig('CONF_ENABLE_SELLER_SUBSCRIPTION_MODULE')) {
            FatUtility::dieJsonError(Labels::getLabel('ERR_INVALID_REQUEST', $this->siteLangId));
        }
        $frm = $this->getSubscriptionOrderSearchForm($this->siteLangId);
        $post = $frm->getFormDataFromArray(FatApp::getPostedData());
        $page = (empty($post['page']) || $post['page'] <= 0) ? 1 : FatUtility::int($post['page']);
        $pagesize = FatApp::getConfig('conf_page_size', FatUtility::VAR_INT, 10);

        $userId = $this->userParentId;

        $srch = new OrderSubscriptionSearch($this->siteLangId, true, true);
        $srch->joinSubscription();
        $srch->joinOrderUser();
        $srch->joinOtherCharges();
        $srch->addCondition('order_user_id', '=', $userId);
        $srch->addCondition('order_type', '=', Orders::ORDER_SUBSCRIPTION);
        $srch->addOrder("ossubs_id", "DESC");
        $srch->setPageNumber($page);
        $srch->setPageSize($pagesize);

        $srch->addMultipleFields(
            array('order_number', 'order_id', 'order_user_id', 'user_autorenew_subscription', 'ossubs_id', 'ossubs_type', 'ossubs_plan_id', 'order_date_added', 'order_net_amount', 'ossubs_invoice_number', 'ossubs_subscription_name', 'ossubs_id', 'op_other_charges', 'ossubs_price', 'IFNULL(orderstatus_name, orderstatus_identifier) as orderstatus_name', 'ossubs_interval', 'ossubs_frequency', 'ossubs_till_date', 'ossubs_status_id', 'ossubs_from_date', 'order_language_id')
        );

        $keyword = FatApp::getPostedData('keyword', null, '');
        if (!empty($keyword)) {
            $srch->joinOrderUser();
            $srch->addKeywordSearch(trim($keyword));
        }

        $op_status_id = FatApp::getPostedData('status', null, '0');

        if (in_array($op_status_id, unserialize(FatApp::getConfig("CONF_SELLER_SUBSCRIPTION_STATUS")))) {
            $srch->addStatusCondition($op_status_id);
        } else {
            $srch->addStatusCondition(unserialize(FatApp::getConfig("CONF_SELLER_SUBSCRIPTION_STATUS")));
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
        }

        $priceTo = FatApp::getPostedData('price_to', null, '');
        if (!empty($priceTo)) {
            $srch->addHaving('totOrders', '=', '1');
            $srch->addMaxPriceCondition($priceTo);
        }
        $rs = $srch->getResultSet();
        $orders = FatApp::getDb()->fetchAll($rs);

        $oObj = new OrderSubscription();
        foreach ($orders as &$order) {
            $charges = $oObj->getOrderSubscriptionChargesArr($order['ossubs_id']);
            $order['charges'] = $charges;
        }
        $orderStatuses = Orders::getOrderSubscriptionStatusArr($this->siteLangId);
        $this->set('orders', $orders);
        $this->set('orderStatuses', $orderStatuses);
        $this->set('page', $page);
        $this->set('pageCount', $srch->pages());
        $this->set('recordCount', $srch->recordCount());
        $this->set('postedData', $post);
        $this->set('canEdit', $this->userPrivilege->canEditSubscription(UserAuthentication::getLoggedUserId(), true));
        $this->_template->render(false, false);
    }

    public function viewOrder($op_id, $print = false)
    {
        $this->userPrivilege->canViewSales(UserAuthentication::getLoggedUserId());
        $op_id = FatUtility::int($op_id);
        if (1 > $op_id) {
            Message::addErrorMessage(Labels::getLabel('ERR_INVALID_ACCESS', $this->siteLangId));
            CommonHelper::redirectUserReferer();
        }

        $orderObj = new Orders();

        $orderStatuses = Orders::getOrderProductStatusArr($this->siteLangId);
        $userId = $this->userParentId;

        $srch = new OrderProductSearch($this->siteLangId, true, true);
        $srch->joinOrderProductShipment();
        $srch->joinOrderProductSpecifics();
        $srch->joinPaymentMethod();
        $srch->joinSellerProducts();
        $srch->joinOrderUser();
        $srch->joinShippingUsers();
        $srch->joinShippingCharges();
        $srch->joinAddress();
        $srch->addOrderProductCharges();
        $srch->joinTable(Plugin::DB_TBL, 'LEFT OUTER JOIN', 'ops.opshipping_plugin_id = ops_plugin.plugin_id', 'ops_plugin');
        $srch->addMultipleFields(
            array(
                'ops.*',
                'order_id',
                'order_number',
                'order_payment_status',
                'order_pmethod_id',
                'order_tax_charged',
                'order_date_added',
                'op_id',
                'op_qty',
                'op_order_id',
                'orderstatus_id',
                'op_unit_price',
                'op_selprod_user_id',
                'op_invoice_number',
                'IFNULL(orderstatus_name, orderstatus_identifier) as orderstatus_name',
                'ou.user_name as buyer_user_name',
                'op_is_batch',
                'op_selprod_id',
                'selprod_product_id',
                'pm.plugin_code',
                'IFNULL(pm_l.plugin_name, IFNULL(pm.plugin_identifier, "Wallet")) as plugin_name',
                'op_commission_charged',
                'op_qty',
                'op_commission_percentage',
                'ou.user_name as buyer_name',
                'ouc.credential_username as user_name',
                'ouc.credential_email as buyer_email',
                'ou.user_phone as buyer_phone',
                'op.op_shop_owner_name',
                'op.op_shop_owner_username',
                'op_l.op_shop_name',
                'op.op_shop_owner_email',
                'op.op_shop_owner_phone',
                'op_selprod_title',
                'op_product_name',
                'op_brand_name',
                'op_selprod_options',
                'op_selprod_sku',
                'op_product_model',
                'op_product_type',
                'op_shipping_duration_name',
                'op_shipping_durations',
                'op_status_id',
                'op_refund_qty',
                'op_refund_amount',
                'op_refund_commission',
                'op_other_charges',
                'optosu.optsu_user_id',
                'op_tax_collected_by_seller',
                'order_is_wallet_selected',
                'order_reward_point_used',
                'op_product_tax_options',
                'ops.*',
                'opship.*',
                'opr_response',
                'addr.*',
                'op_rounding_off',
                'ops_plugin.plugin_code as opshipping_plugin_code',
                'op_selprod_cancellation_age as cancellation_age',
                'op_product_length',
                'op_product_width',
                'op_product_height',
                'op_product_dimension_unit',
                'op_special_price',
                'op_selprod_price',
                'op_tax_after_discount',
                'op_comments'
            )
        );
        $srch->addCondition('op_selprod_user_id', '=', $userId);
        $srch->addCondition('op_id', '=', $op_id);
        $srch->addStatusCondition(unserialize(FatApp::getConfig("CONF_VENDOR_ORDER_STATUS")));
        $rs = $srch->getResultSet();
        $orderDetail = FatApp::getDb()->fetch($rs);

        if (!$orderDetail) {
            Message::addErrorMessage(Labels::getLabel('ERR_INVALID_ACCESS', $this->siteLangId));
            CommonHelper::redirectUserReferer();
        }

        $shippedBySeller = CommonHelper::canAvailShippingChargesBySeller($orderDetail['op_selprod_user_id'], $orderDetail['opshipping_by_seller_user_id']);

        $shippingApiObj = NULL;
        if ($orderDetail['opshipping_fulfillment_type'] == Shipping::FULFILMENT_SHIP) {
            $shippingApiObj = (new Shipping($this->siteLangId))->getShippingApiObj(($shippedBySeller ? $orderDetail['opshipping_by_seller_user_id'] : 0)) ?? NULL;
            if ($shippingApiObj) {
                $shippingApiObj->getSettings();
            }
            if (!empty($orderDetail["opship_orderid"]) && method_exists($shippingApiObj, 'loadOrder')) {
                if (NULL != $shippingApiObj && null != $shippingApiObj && false === $shippingApiObj->loadOrder($orderDetail["opship_orderid"])) {
                    Message::addErrorMessage($shippingApiObj->getError());
                    FatApp::redirectUser(UrlHelper::generateUrl("SellerOrders"));
                }
                $orderDetail['thirdPartyorderInfo'] = (null != $shippingApiObj ? $shippingApiObj->getResponse() : []);
            }
        }
        $this->set('shippingApiObj', $shippingApiObj);

        $codOrder = false;
        if (isset($orderDetail['plugin_code']) && strtolower($orderDetail['plugin_code']) == 'cashondelivery') {
            $codOrder = true;
        }

        $pickupOrder = false;
        if (isset($orderDetail['plugin_code']) && strtolower($orderDetail['plugin_code']) == 'payatstore') {
            $pickupOrder = true;
        }

        if ($orderDetail['op_product_type'] == Product::PRODUCT_TYPE_DIGITAL) {
            $processingStatuses = $orderObj->getVendorAllowedUpdateOrderStatuses(true, $codOrder, $pickupOrder);
        } elseif (in_array($orderDetail['op_product_type'], [Product::PRODUCT_TYPE_PHYSICAL, Product::PRODUCT_TYPE_SERVICE])) {
            $processingStatuses = $orderObj->getVendorAllowedUpdateOrderStatuses(false, $codOrder, $pickupOrder);
            if ($orderDetail['op_product_type'] == Product::PRODUCT_TYPE_SERVICE) {
                $processingStatuses = array_diff($processingStatuses, (array) FatApp::getConfig("CONF_DEFAULT_SHIPPING_ORDER_STATUS"));
            }
        }

        /* [ if shipping not handled by seller then seller can not update status to ship and delived */
        if (!CommonHelper::canAvailShippingChargesBySeller($orderDetail['op_selprod_user_id'], $orderDetail['opshipping_by_seller_user_id']) && $orderDetail['op_product_type'] != Product::PRODUCT_TYPE_SERVICE) {
            $processingStatuses = array_diff($processingStatuses, (array) FatApp::getConfig("CONF_DEFAULT_SHIPPING_ORDER_STATUS"));
            if ($pickupOrder) {
                $processingStatuses = [];
            } else {
                $processingStatuses = array_diff($processingStatuses, (array) FatApp::getConfig("CONF_DEFAULT_DEIVERED_ORDER_STATUS"));
            }
        }
        /* ] */

        if ($orderDetail["opshipping_fulfillment_type"] == Shipping::FULFILMENT_PICKUP) {
            $processingStatuses = array_diff($processingStatuses, (array) FatApp::getConfig("CONF_DEFAULT_SHIPPING_ORDER_STATUS"));
        } else {
            $processingStatuses = array_diff($processingStatuses, (array) FatApp::getConfig("CONF_PICKUP_READY_ORDER_STATUS", FatUtility::VAR_INT, 0));
        }

        $charges = $orderObj->getOrderProductChargesArr($op_id);
        $orderDetail['charges'] = $charges;
        $address = $orderObj->getOrderAddresses($orderDetail['op_order_id']);
        $orderDetail['billingAddress'] = (isset($address[Orders::BILLING_ADDRESS_TYPE])) ? $address[Orders::BILLING_ADDRESS_TYPE] : array();
        $orderDetail['shippingAddress'] = (isset($address[Orders::SHIPPING_ADDRESS_TYPE])) ? $address[Orders::SHIPPING_ADDRESS_TYPE] : array();

        $pickUpAddress = $orderObj->getOrderAddresses($orderDetail['op_order_id'], $orderDetail['op_id']);
        $orderDetail['pickupAddress'] = (isset($pickUpAddress[Orders::PICKUP_ADDRESS_TYPE])) ? $pickUpAddress[Orders::PICKUP_ADDRESS_TYPE] : array();

        $orderDetail['comments'] = $orderObj->getOrderComments($this->siteLangId, array("op_id" => $op_id, 'seller_id' => $userId));

        $opChargesLog = new OrderProductChargeLog($op_id);
        $taxOptions = $opChargesLog->getData($this->siteLangId);
        $orderDetail['taxOptions'] = $taxOptions;

        $data = array(
            'op_id' => $op_id,
            'op_status_id' => $orderDetail['op_status_id'],
            'tracking_number' => $orderDetail['opship_tracking_number']
        );
        $frm = $this->getOrderCommentsForm($orderDetail, $processingStatuses);
        $frm->fill($data);

        $digitalDownloads = array();
        $digitalDownloadLinks = array();
        $canAttachMoreFiles = false;
        if ($orderDetail['op_product_type'] == Product::PRODUCT_TYPE_DIGITAL) {
            $digitalDownloads = Orders::getOrderProductDigitalDownloads($op_id);
            $digitalDownloadLinks = Orders::getOrderProductDigitalDownloadLinks($op_id);

            if (DigitalOrderProduct::canAttachMoreFiles($orderDetail['op_status_id'])) {
                $canAttachMoreFiles = true;
                $moreAttachmentsFrm = OrderProduct::moreAttachmentsForm($this->siteLangId);
                $moreAttachmentsFrm->fill(['op_id' => $orderDetail['op_id']]);
                $this->set('moreAttachmentsFrm', $moreAttachmentsFrm);
            }
        }


        if ($orderDetail['plugin_code'] == 'CashOnDelivery') {
            $opTimeLineStatus = $orderObj->getAdminAllowedUpdateOrderStatuses(true, $orderDetail['op_product_type']);
        } else if ($orderDetail['plugin_code'] == 'PayAtStore') {
            $opTimeLineStatus = $orderObj->getAdminAllowedUpdateOrderStatuses(false, $orderDetail['op_product_type'], true);
        } else {
            $opTimeLineStatus = $orderObj->getAdminAllowedUpdateOrderStatuses(false, $orderDetail['op_product_type']);
        }

        if ($orderDetail["opshipping_fulfillment_type"] == Shipping::FULFILMENT_PICKUP) {
            $opTimeLineStatus = array_diff($opTimeLineStatus, (array) FatApp::getConfig("CONF_DEFAULT_SHIPPING_ORDER_STATUS", FatUtility::VAR_INT, 0));
        } else {
            $opTimeLineStatus = array_diff($opTimeLineStatus, (array) FatApp::getConfig("CONF_PICKUP_READY_ORDER_STATUS", FatUtility::VAR_INT, 0));
        }

        if ($orderDetail['op_product_type'] == Product::PRODUCT_TYPE_PHYSICAL) {
            $opTimeLineStatus = array_diff($opTimeLineStatus, [FatApp::getConfig("CONF_DEFAULT_APPROVED_ORDER_STATUS")]);
        }

        if (FatApp::getConfig("CONF_DEFAULT_CANCEL_ORDER_STATUS") == $orderDetail['orderstatus_id'] || FatApp::getConfig("CONF_RETURN_REQUEST_ORDER_STATUS") == $orderDetail['orderstatus_id']) {
            $opTimeLineStatus[] = $orderDetail['orderstatus_id'];
            $opTimeLineStatus = array_diff($opTimeLineStatus, [FatApp::getConfig("CONF_DEFAULT_COMPLETED_ORDER_STATUS")]);
        }
        if (FatApp::getConfig("CONF_RETURN_REQUEST_APPROVED_ORDER_STATUS") == $orderDetail['orderstatus_id']) {
            $opTimeLineStatus[] = $orderDetail['orderstatus_id'];
            $opTimeLineStatus = array_diff($opTimeLineStatus, [FatApp::getConfig("CONF_DEFAULT_COMPLETED_ORDER_STATUS")]);
        }
        $orderProductStatusArr = Orders::getOrderProductStatusArr($this->siteLangId, $opTimeLineStatus);

        $orderTimeLine = [];
        $currentStatus = Orders::ORDER_PAYMENT_PENDING == $orderDetail['order_payment_status'] ? Orders::ORDER_PAYMENT_PENDING : FatApp::getConfig("CONF_DEFAULT_PAID_ORDER_STATUS");
        $highlightEnabled = [];
        $cancellationComment = "";
        if (!empty($orderDetail['comments'])) {
            $currentStatus = current($orderDetail['comments'])['oshistory_orderstatus_id'];
            if (FatApp::getConfig("CONF_DEFAULT_CANCEL_ORDER_STATUS") == $orderDetail['orderstatus_id']) {
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
        $productType = !empty($orderDetail['selprod_product_id']) ? Product::getAttributesById($orderDetail['selprod_product_id'], 'product_type') : 0;

        $cancelledDate = "";
        if (FatApp::getConfig("CONF_DEFAULT_CANCEL_ORDER_STATUS") == $orderDetail['orderstatus_id'] && isset($orderTimeLine[$orderDetail['orderstatus_id']])) {
            $cancelledDate = current($orderTimeLine[$orderDetail['orderstatus_id']])['oshistory_date_added'];
        }

        $ddpObj = new DigitalDownloadPrivilages();
        $canDownload = $ddpObj->canDownload(
            $orderDetail['op_selprod_id'],
            Product::CATALOG_TYPE_INVENTORY,
            $userId,
            $this->siteLangId
        );
        $orderColorClasses =  OrderStatus::getOrderStatusColorClassArray();
        $this->set('canDownload', $canDownload);
        $this->set('arr', [$orderDetail]);
        $this->set('unitTypeArray', ShippingPackage::getUnitTypes($this->siteLangId));

        $this->set('highlightEnabled', $highlightEnabled);
        $this->set('currentStatus', $currentStatus);
        $this->set('orderProductStatusArr', $orderProductStatusArr);
        $this->set('orderTimeLine', $orderTimeLine);
        $this->set('cancelledDate', $cancelledDate);
        $this->set('cancellationComment', $cancellationComment);

        $this->set('productType', $productType);
        $this->set('orderDetail', $orderDetail);
        $this->set('orderStatuses', $orderStatuses);
        $this->set('shippedBySeller', $shippedBySeller);
        $this->set('digitalDownloads', $digitalDownloads);
        $this->set('digitalDownloadLinks', $digitalDownloadLinks);
        $this->set('canAttachMoreFiles', $canAttachMoreFiles);
        $this->set('userId', $userId);
        $this->set('languages', Language::getAllNames());
        $this->set('yesNoArr', applicationConstants::getYesNoArr($this->siteLangId));
        $this->set('frm', $frm);
        $this->set('displayForm', (in_array($orderDetail['op_status_id'], $processingStatuses)));
        $this->set('orderColorClasses', $orderColorClasses);
        if ($print) {
            $print = true;
        }
        $this->set('canEdit', $this->userPrivilege->canEditSales(UserAuthentication::getLoggedUserId(), true));
        $this->set('print', $print);
        $urlParts = array_filter(FatApp::getParameters());
        $this->set('urlParts', $urlParts);

        $this->_template->addJs(array('js/jquery.datetimepicker.js'));
        $this->_template->addCss(array('css/jquery.datetimepicker.css'), false);
        $this->_template->render();
    }

    public function viewInvoice($op_id)
    {
        $this->userPrivilege->canViewSales(UserAuthentication::getLoggedUserId());
        $op_id = FatUtility::int($op_id);
        if (1 > $op_id) {
            Message::addErrorMessage(Labels::getLabel('ERR_INVALID_ACCESS', $this->siteLangId));
            CommonHelper::redirectUserReferer();
        }

        $orderObj = new Orders();
        $userId = $this->userParentId;

        $srch = new OrderProductSearch($this->siteLangId, true, true);
        $srch->joinPaymentMethod();
        $srch->joinSellerProducts();
        $srch->joinProduct();
        $srch->joinShop();
        $srch->joinShopSpecifics();
        $srch->joinShopCountry();
        $srch->joinShopState();
        $srch->joinShippingUsers();
        $srch->joinShippingCharges();
        $srch->addOrderProductCharges();
        $srch->addCondition('op_selprod_user_id', '=', $userId);
        $srch->addCondition('op_id', '=', $op_id);
        $srch->addStatusCondition(unserialize(FatApp::getConfig("CONF_VENDOR_ORDER_STATUS")));
        $srch->addMultipleFields(array('*', 'shop_country_l.country_name as shop_country_name', 'shop_state_l.state_name as shop_state_name', 'shop_city', 'product_hsn_code'));
        $rs = $srch->getResultSet();
        $orderDetail = FatApp::getDb()->fetch($rs);

        if (!$orderDetail) {
            Message::addErrorMessage(Labels::getLabel('ERR_INVALID_ACCESS', $this->siteLangId));
            CommonHelper::redirectUserReferer();
        }

        $charges = $orderObj->getOrderProductChargesArr($op_id);
        $orderDetail['charges'] = $charges;

        $shippedBySeller = applicationConstants::NO;
        if (CommonHelper::canAvailShippingChargesBySeller($orderDetail['op_selprod_user_id'], $orderDetail['opshipping_by_seller_user_id'])) {
            $shippedBySeller = applicationConstants::YES;
        }

        if (!empty($orderDetail["opship_orderid"])) {
            $shippingApiObj = (new Shipping($this->siteLangId))->getShippingApiObj(($shippedBySeller ? $orderDetail['opshipping_by_seller_user_id'] : 0)) ?? NULL;
            if (NULL != $shippingApiObj && false === $shippingApiObj->loadOrder($orderDetail["opship_orderid"])) {
                Message::addErrorMessage($shippingApiObj->getError());
                FatApp::redirectUser(UrlHelper::generateUrl("SellerOrders"));
            }
            $orderDetail['thirdPartyorderInfo'] = (NULL != $shippingApiObj ? $shippingApiObj->getResponse() : []);
        }

        $address = $orderObj->getOrderAddresses($orderDetail['op_order_id']);
        $orderDetail['billingAddress'] = (isset($address[Orders::BILLING_ADDRESS_TYPE])) ? $address[Orders::BILLING_ADDRESS_TYPE] : array();
        $orderDetail['shippingAddress'] = (isset($address[Orders::SHIPPING_ADDRESS_TYPE])) ? $address[Orders::SHIPPING_ADDRESS_TYPE] : array();

        $pickUpAddress = $orderObj->getOrderAddresses($orderDetail['op_order_id'], $orderDetail['op_id']);
        $orderDetail['pickupAddress'] = (isset($pickUpAddress[Orders::PICKUP_ADDRESS_TYPE])) ? $pickUpAddress[Orders::PICKUP_ADDRESS_TYPE] : array();

        $opChargesLog = new OrderProductChargeLog($op_id);
        $taxOptions = $opChargesLog->getData($this->siteLangId);
        $orderDetail['taxOptions'] = $taxOptions;

        $template = new FatTemplate('', '');
        $template->set('siteLangId', $this->siteLangId);
        $template->set('orderDetail', $orderDetail);
        $template->set('shippedBySeller', $shippedBySeller);
        $template->set('buyerGstNumber', User::getAttributesById($orderDetail['order_user_id'], 'user_gst_number'));
        $template->set('sellerGstNumber', User::getAttributesById($orderDetail['op_selprod_user_id'], 'user_gst_number'));

        require_once CONF_INSTALLATION_PATH . 'vendor/autoload.php';
        $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
        $pdf->SetCreator(PDF_CREATOR);
        $pdf->SetAuthor(FatApp::getConfig("CONF_WEBSITE_NAME_" . $this->siteLangId));
        $pdf->SetKeywords(FatApp::getConfig("CONF_WEBSITE_NAME_" . $this->siteLangId));
        $pdf->setHeaderFont(array(PDF_FONT_NAME_MAIN, '', PDF_FONT_SIZE_MAIN));
        $pdf->SetHeaderMargin(0);
        $pdf->SetHeaderData('', 0, '', '', array(255, 255, 255), array(255, 255, 255));
        $pdf->setFooterData(array(0, 0, 0), array(200, 200, 200));
        $pdf->SetFooterMargin(PDF_MARGIN_FOOTER);
        $pdf->setFooterFont(array(PDF_FONT_NAME_DATA, '', PDF_FONT_SIZE_DATA));
        $pdf->SetMargins(10, 10, 10);
        $pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);
        $pdf->AddPage();
        $pdf->SetTitle(Labels::getLabel('LBL_Tax_Invoice', $this->siteLangId));
        $pdf->SetSubject(Labels::getLabel('LBL_Tax_Invoice', $this->siteLangId));

        // set LTR direction for english translation
        $pdf->setRTL(('rtl' == Language::getLayoutDirection($this->siteLangId)));
        // set font
        $pdf->SetFont('dejavusans');

        $templatePath = "seller/view-invoice.php";
        $html = $template->render(false, false, $templatePath, true, true);
        $pdf->writeHTML($html, true, false, true, false, '');
        $pdf->lastPage();

        ob_end_clean();
        // $saveFile = CONF_UPLOADS_PATH . 'demo-pdf.pdf';
        //$pdf->Output($saveFile, 'F');
        $pdf->Output('tax-invoice.pdf', 'I');
        return true;
    }

    public function viewSubscriptionOrder($ossubs_id)
    {
        $op_id = FatUtility::int($ossubs_id);
        if (1 > $ossubs_id) {
            Message::addErrorMessage(Labels::getLabel('ERR_INVALID_ACCESS', $this->siteLangId));
            CommonHelper::redirectUserReferer();
        }

        $orderStatuses = Orders::getOrderSubscriptionStatusArr($this->siteLangId);
        $userId = $this->userParentId;

        $srch = new OrderSubscriptionSearch($this->siteLangId, true, true);
        $srch->joinOrderUser();
        $srch->addOrderProductCharges();
        $srch->addCondition('order_user_id', '=', $userId);
        $srch->addCondition('ossubs_id', '=', $op_id);
        $rs = $srch->getResultSet();

        $orderDetail = FatApp::getDb()->fetch($rs);
        if (!$orderDetail) {
            Message::addErrorMessage(Labels::getLabel('ERR_INVALID_ACCESS', $this->siteLangId));
            CommonHelper::redirectUserReferer();
        }

        $oSubObj = new OrderSubscription();
        $orderDetail['charges'] = $oSubObj->getOrderSubscriptionChargesArr($op_id);
        $subcriptionPeriodArr = SellerPackagePlans::getSubscriptionPeriods($this->siteLangId);

        $this->set('subcriptionPeriodArr', $subcriptionPeriodArr);
        $this->set('orderDetail', $orderDetail);
        $this->set('orderStatuses', $orderStatuses);
        $this->set('yesNoArr', applicationConstants::getYesNoArr($this->siteLangId));
        $this->_template->render(true, true);
    }

    public function changeOrderStatus()
    {
        $this->userPrivilege->canEditSales(UserAuthentication::getLoggedUserId());
        $post = FatApp::getPostedData();
        if (!isset($post['op_id'])) {
            FatUtility::dieJsonError(Labels::getLabel('ERR_INVALID_ACCESS', $this->siteLangId));
        }

        $status = FatApp::getPostedData('op_status_id', FatUtility::VAR_INT, 0);
        $manualShipping = FatApp::getPostedData('manual_shipping', FatUtility::VAR_INT, 0);
        $trackingNumber = FatApp::getPostedData('tracking_number', FatUtility::VAR_STRING, '');
        $shippedByPlugin = FatApp::getPostedData('shipped_by_plugin', FatUtility::VAR_INT, 0);

        $db = FatApp::getDb();
        $db->startTransaction();

        $op_id = FatUtility::int($post['op_id']);
        if (1 > $op_id) {
            FatUtility::dieJsonError(Labels::getLabel('ERR_INVALID_ACCESS', $this->siteLangId));
        }

        $oldStatus = OrderProduct::getAttributesById($op_id, 'op_status_id');
        if ($status == $oldStatus) {
            $msg = current(OrderStatus::getAttributesByLangId($this->siteLangId, $status, ['COALESCE(orderstatus_name, orderstatus_identifier) as orderstatus_name'], applicationConstants::JOIN_RIGHT));
            FatUtility::dieJsonError(sprintf(Labels::getLabel('MSG_ALREADY_%S', $this->siteLangId), $msg));
        }

        $oCancelRequestSrch = new OrderCancelRequestSearch();
        $oCancelRequestSrch->doNotCalculateRecords();
        $oCancelRequestSrch->doNotLimitRecords();
        $oCancelRequestSrch->addCondition('ocrequest_op_id', '=', $op_id);
        $oCancelRequestSrch->addCondition('ocrequest_status', '!=', OrderCancelRequest::CANCELLATION_REQUEST_STATUS_DECLINED);
        $oCancelRequestRs = $oCancelRequestSrch->getResultSet();
        if (FatApp::getDb()->fetch($oCancelRequestRs)) {
            FatUtility::dieJsonError(Labels::getLabel('ERR_CANCEL_REQUEST_IS_SUBMITTED_FOR_THIS_ORDER', $this->siteLangId));
        }

        $loggedUserId = $this->userParentId;

        $orderObj = new Orders();

        $srch = new OrderProductSearch($this->siteLangId, true, true);
        $srch->joinOrderProductShipment();
        $srch->joinPaymentMethod();
        $srch->joinSellerProducts();
        $srch->joinOrderUser();
        $srch->joinShippingUsers();
        $srch->joinShippingCharges();
        $srch->joinTable(Plugin::DB_TBL, 'LEFT OUTER JOIN', 'ops.opshipping_plugin_id = ops_plugin.plugin_id', 'ops_plugin');
        $srch->joinOrderCancellationRequest();
        $srch->addStatusCondition(unserialize(FatApp::getConfig("CONF_VENDOR_ORDER_STATUS")));
        $srch->addCondition('op_selprod_user_id', '=', $loggedUserId);
        $srch->addCondition('op_id', '=', $op_id);
        $srch->addMultipleFields(['op.*', 'pm.*', 'opshipping_by_seller_user_id', 'ocrequest_status', 'opshipping_fulfillment_type', 'order_language_id', 'ops_plugin.plugin_code as opshipping_plugin_code', 'opship_tracking_number', 'orderstatus_id', 'opshipping_carrier_code', 'order_payment_status', 'order_id']);

        $orderDetail = FatApp::getDb()->fetch($srch->getResultSet());
        if (empty($orderDetail)) {
            FatUtility::dieJsonError(Labels::getLabel('ERR_INVALID_ACCESS', $this->siteLangId));
        }

        $shippedBySeller = CommonHelper::canAvailShippingChargesBySeller($orderDetail['op_selprod_user_id'], $orderDetail['opshipping_by_seller_user_id']);

        $activatedTrackPlugin = (new Plugin())->getDefaultPluginData(Plugin::TYPE_SHIPMENT_TRACKING, ['plugin_id', 'plugin_code']);
        if ($status == FatApp::getConfig("CONF_DEFAULT_SHIPPING_ORDER_STATUS") && empty($trackingNumber) && 1 > $manualShipping && false === $activatedTrackPlugin) {
            FatUtility::dieJsonError(Labels::getLabel('ERR_PLEASE_SELECT_SELF_SHIPPING', $this->siteLangId));
        }

        if ($orderDetail["op_status_id"] != $post['op_status_id'] && $orderDetail['ocrequest_status'] != '' && $orderDetail['ocrequest_status'] == OrderCancelRequest::CANCELLATION_REQUEST_STATUS_PENDING) {
            FatUtility::dieJsonError(Labels::getLabel('ERR_BUYER_ORDER_CANCELLATION_REQUEST_IS_PENDING', $this->siteLangId));
        }

        $codOrder = false;
        if (isset($orderDetail['plugin_code']) && strtolower($orderDetail['plugin_code']) == 'cashondelivery') {
            $codOrder = true;
        }

        $pickupOrder = false;
        if (isset($orderDetail['plugin_code']) && strtolower($orderDetail['plugin_code']) == 'payatstore') {
            $pickupOrder = true;
        }


        if ($orderDetail['op_product_type'] == Product::PRODUCT_TYPE_DIGITAL) {
            $processingStatuses = $orderObj->getVendorAllowedUpdateOrderStatuses(true, $codOrder, $pickupOrder);
        } elseif (in_array($orderDetail['op_product_type'], [Product::PRODUCT_TYPE_PHYSICAL, Product::PRODUCT_TYPE_SERVICE])) {
            $processingStatuses = $orderObj->getVendorAllowedUpdateOrderStatuses(false, $codOrder, $pickupOrder);
            if ($orderDetail['op_product_type'] == Product::PRODUCT_TYPE_SERVICE) {
                $processingStatuses = array_diff($processingStatuses, (array) FatApp::getConfig("CONF_DEFAULT_SHIPPING_ORDER_STATUS"));
            }
        }

        /* [ if shipping not handled by seller then seller can not update status to ship and delived */
        if (!$shippedBySeller && $orderDetail['op_product_type'] != Product::PRODUCT_TYPE_SERVICE) {
            $processingStatuses = array_diff($processingStatuses, (array) FatApp::getConfig("CONF_DEFAULT_SHIPPING_ORDER_STATUS"));
            if ($pickupOrder) {
                $processingStatuses = [];
            } else {
                $processingStatuses = array_diff($processingStatuses, (array) FatApp::getConfig("CONF_DEFAULT_DEIVERED_ORDER_STATUS"));
            }
        }
        /* ] */

        if ($orderDetail["opshipping_fulfillment_type"] == Shipping::FULFILMENT_PICKUP) {
            $processingStatuses = array_diff($processingStatuses, (array) FatApp::getConfig("CONF_DEFAULT_SHIPPING_ORDER_STATUS"));
        }

        $frm = $this->getOrderCommentsForm($orderDetail, $processingStatuses);

        if (1 == $shippedByPlugin) {
            $fld = $frm->getField('op_status_id');
            $fld->requirements()->removeOnChangerequirementUpdate(FatApp::getConfig("CONF_DEFAULT_SHIPPING_ORDER_STATUS", FatUtility::VAR_INT), 'eq');
        }

        $post = $frm->getFormDataFromArray($post);

        if (false == $post) {
            FatUtility::dieJsonError(current($frm->getValidationErrors()));
        }

        if (in_array($orderDetail["op_status_id"], $processingStatuses) && in_array($post["op_status_id"], $processingStatuses)) {
            $trackingCourierCode = '';
            $opship_tracking_url = FatApp::getPostedData('opship_tracking_url', FatUtility::VAR_STRING, '');

            $activatedTrackPluginId = (false !== $activatedTrackPlugin && 0 < $activatedTrackPlugin['plugin_id']) ? $activatedTrackPlugin['plugin_id'] : 0;
            $activatedTrackPluginCode = (false !== $activatedTrackPlugin && 0 < $activatedTrackPlugin['plugin_code']) ? $activatedTrackPlugin['plugin_code'] : 0;

            if ($post["op_status_id"] == FatApp::getConfig("CONF_DEFAULT_SHIPPING_ORDER_STATUS") && 0 < $activatedTrackPluginId && in_array($activatedTrackPluginCode, ['AfterShipShipment'])) {
                if (array_key_exists('manual_shipping', $post) && 0 < $post['manual_shipping']) {
                    $updateData = [
                        'opship_op_id' => $post['op_id'],
                        "opship_tracking_number" => $post['tracking_number']
                    ];
                    if (array_key_exists('opship_tracking_url', $post)) {
                        $updateData['opship_tracking_url'] = $opship_tracking_url;
                    }
                    if (array_key_exists('oshistory_courier', $post)) {
                        $trackingCourierCode = $post['oshistory_courier'];
                        $updateData['opship_tracking_courier_code'] = $trackingCourierCode;
                        $updateData['opship_tracking_plugin_id'] = $activatedTrackPluginId;
                    }
                    if (!FatApp::getDb()->insertFromArray(OrderProductShipment::DB_TBL, $updateData, false, array(), $updateData)) {
                        LibHelper::dieJsonError(FatApp::getDb()->getError());
                    }
                } else {

                    $shippingHanldedBySeller = CommonHelper::canAvailShippingChargesBySeller($orderDetail['op_selprod_user_id'], $orderDetail['opshipping_by_seller_user_id']);
                    $shippingObj = new Shipping($this->siteLangId);
                    $shippingApiObj = $shippingObj->getShippingApiObj(($shippingHanldedBySeller ? $orderDetail['opshipping_by_seller_user_id'] : 0)) ?? NULL;

                    if (0 < $activatedTrackPluginId && !$shippingApiObj->canFetchTrackingDetail()) {
                        $trackingRelation = new TrackingCourierCodeRelation();
                        $trackData = $trackingRelation->getDataByShipCourierCode($orderDetail['opshipping_carrier_code']);
                        if (count($trackData)) {
                            $trackingCourierCode = !empty($trackData['tccr_tracking_courier_code']) ? $trackData['tccr_tracking_courier_code'] : '';
                            $updateData = [
                                'opship_op_id' => $post['op_id'],
                                "opship_tracking_courier_code" => $trackingCourierCode,
                                "opship_tracking_plugin_id" => $activatedTrackPluginId,
                            ];
                            if (!FatApp::getDb()->insertFromArray(OrderProductShipment::DB_TBL, $updateData, false, array(), $updateData)) {
                                LibHelper::dieJsonError(FatApp::getDb()->getError());
                            }
                        }
                    }
                }
            }

            $trackingNumber = FatApp::getPostedData("tracking_number", FatUtility::VAR_STRING, '');
            if (!$orderObj->addChildProductOrderHistory($op_id, $orderDetail["order_language_id"], $post["op_status_id"], $post["comments"], $post["customer_notified"], $trackingNumber, 0, true, $trackingCourierCode, $opship_tracking_url)) {
                FatUtility::dieJsonError($orderObj->getError());
            }
        } else {
            FatUtility::dieJsonError(Labels::getLabel('M_ERROR_INVALID_REQUEST', $this->siteLangId));
        }

        if (isset($orderDetail['plugin_code']) &&  in_array(strtolower($orderDetail['plugin_code']), ['cashondelivery', 'payatstore']) && (FatApp::getConfig("CONF_DEFAULT_DEIVERED_ORDER_STATUS") == $post["op_status_id"] || FatApp::getConfig("CONF_DEFAULT_COMPLETED_ORDER_STATUS") == $post["op_status_id"]) && Orders::ORDER_PAYMENT_PAID != $orderDetail['order_payment_status']) {

            $orderProducts = new OrderProductSearch($this->siteLangId, true, true);
            $orderProducts->joinPaymentMethod();
            $orderProducts->addMultipleFields(['op_status_id']);
            $orderProducts->addCondition('op_order_id', '=', $orderDetail['order_id']);
            $orderProducts->addCondition('op_status_id', '!=', FatApp::getConfig("CONF_DEFAULT_DEIVERED_ORDER_STATUS"));
            $orderProducts->addCondition('op_status_id', '!=', FatApp::getConfig("CONF_DEFAULT_COMPLETED_ORDER_STATUS"));
            $childOrders = FatApp::getDb()->fetchAll($orderProducts->getResultSet());
            if (empty($childOrders)) {
                $updateArray = array('order_payment_status' => Orders::ORDER_PAYMENT_PAID);
                $whr = array('smt' => 'order_id = ?', 'vals' => array($orderDetail['order_id']));
                if (!FatApp::getDb()->updateFromArray(Orders::DB_TBL, $updateArray, $whr)) {
                    FatUtility::dieJsonError(Labels::getLabel('ERR_INVALID_ACCESS', $this->siteLangId));
                }
            }

            if (!empty($orderDetail['order_discount_coupon_code'])) {
                $srch = DiscountCoupons::getSearchObject();
                $srch->addFld('coupon_id');
                $srch->addCondition('coupon_code', '=', $orderDetail['order_discount_coupon_code']);
                $couponData = FatApp::getDb()->fetch($srch->getResultSet());
                if (!empty($couponData)) {
                    if (!FatApp::getDb()->insertFromArray(CouponHistory::DB_TBL, array('couponhistory_coupon_id' => $couponData['coupon_id'], 'couponhistory_order_id' => $orderDetail['order_id'], 'couponhistory_user_id' => $orderDetail['order_user_id'], 'couponhistory_amount' => $orderDetail['order_discount_total'], 'couponhistory_added_on' => $orderDetail['order_date_added']))) {
                        $this->error = FatApp::getDb()->getError();
                        return false;
                    }
                }
                FatApp::getDb()->deleteRecords(DiscountCoupons::DB_TBL_COUPON_HOLD_PENDING_ORDER, array('smt' => 'ochold_order_id = ?', 'vals' => array($orderDetail['order_id'])));
            }
        }

        $db->commitTransaction();
        CalculativeDataRecord::updateOrderCancelRequestCount();
        $this->set('op_id', $op_id);
        $this->set('msg', Labels::getLabel('MSG_Updated_Successfully', $this->siteLangId));
        $this->_template->render(false, false, 'json-success.php');
    }

    public function cancelOrder($op_id)
    {
        $this->userPrivilege->canEditSales(UserAuthentication::getLoggedUserId());
        $userId = $this->userParentId;

        $op_id = FatUtility::int($op_id);
        if (1 > $op_id) {
            Message::addErrorMessage(Labels::getLabel('ERR_INVALID_ACCESS', $this->siteLangId));
            CommonHelper::redirectUserReferer();
        }

        $srch = new OrderProductSearch($this->siteLangId, true, true);
        $srch->addStatusCondition(unserialize(FatApp::getConfig("CONF_VENDOR_ORDER_STATUS")));
        $srch->joinOrderProductShipment();
        $srch->joinOrderProductSpecifics();
        $srch->joinPaymentMethod();
        $srch->joinSellerProducts();
        $srch->joinOrderUser();
        $srch->joinShippingUsers();
        $srch->joinShippingCharges();
        $srch->joinAddress();
        $srch->addOrderProductCharges();
        $srch->joinTable(Plugin::DB_TBL, 'LEFT OUTER JOIN', 'ops.opshipping_plugin_id = ops_plugin.plugin_id', 'ops_plugin');
        $srch->addMultipleFields(
            array(
                'ops.*',
                'order_id',
                'order_number',
                'order_payment_status',
                'order_pmethod_id',
                'order_tax_charged',
                'order_date_added',
                'op_id',
                'op_qty',
                'op_order_id',
                'orderstatus_id',
                'op_unit_price',
                'op_selprod_user_id',
                'op_invoice_number',
                'IFNULL(orderstatus_name, orderstatus_identifier) as orderstatus_name',
                'ou.user_name as buyer_user_name',
                'op_is_batch',
                'op_selprod_id',
                'selprod_product_id',
                'pm.plugin_code',
                'IFNULL(pm_l.plugin_name, IFNULL(pm.plugin_identifier, "Wallet")) as plugin_name',
                'op_commission_charged',
                'op_qty',
                'op_commission_percentage',
                'ou.user_name as buyer_name',
                'ouc.credential_username as user_name',
                'ouc.credential_email as buyer_email',
                'ou.user_phone as buyer_phone',
                'op.op_shop_owner_name',
                'op.op_shop_owner_username',
                'op_l.op_shop_name',
                'op.op_shop_owner_email',
                'op.op_shop_owner_phone',
                'op_selprod_title',
                'op_product_name',
                'op_brand_name',
                'op_selprod_options',
                'op_selprod_sku',
                'op_product_model',
                'op_product_type',
                'op_shipping_duration_name',
                'op_shipping_durations',
                'op_status_id',
                'op_refund_qty',
                'op_refund_amount',
                'op_refund_commission',
                'op_other_charges',
                'optosu.optsu_user_id',
                'op_tax_collected_by_seller',
                'order_is_wallet_selected',
                'order_reward_point_used',
                'op_product_tax_options',
                'ops.*',
                'opship.*',
                'opr_response',
                'addr.*',
                'op_rounding_off',
                'ops_plugin.plugin_code as opshipping_plugin_code',
                'op_selprod_cancellation_age as cancellation_age',
                'op_product_length',
                'op_product_width',
                'op_product_height',
                'op_product_dimension_unit',
                'op_special_price',
                'op_selprod_price',
                'op_tax_after_discount'
            )
        );
        $srch->addCondition('op_selprod_user_id', '=', $userId);
        $srch->addCondition('op_id', '=', $op_id);
        $orderDetail = FatApp::getDb()->fetch($srch->getResultSet());

        if (empty($orderDetail)) {
            Message::addErrorMessage(Labels::getLabel('ERR_INVALID_ACCESS', $this->siteLangId));
            CommonHelper::redirectUserReferer();
        }

        $orderObj = new Orders();
        $charges = $orderObj->getOrderProductChargesArr($op_id);
        $orderDetail['charges'] = $charges;

        $address = $orderObj->getOrderAddresses($orderDetail['op_order_id']);
        $orderDetail['billingAddress'] = (isset($address[Orders::BILLING_ADDRESS_TYPE])) ? $address[Orders::BILLING_ADDRESS_TYPE] : array();
        $orderDetail['shippingAddress'] = (isset($address[Orders::SHIPPING_ADDRESS_TYPE])) ? $address[Orders::SHIPPING_ADDRESS_TYPE] : array();

        $pickUpAddress = $orderObj->getOrderAddresses($orderDetail['order_id'], $op_id);
        $orderDetail['pickupAddress'] = (!empty($pickUpAddress[Orders::PICKUP_ADDRESS_TYPE])) ? $pickUpAddress[Orders::PICKUP_ADDRESS_TYPE] : array();


        if ($orderDetail['plugin_code'] == 'CashOnDelivery') {
            $opTimeLineStatus = $orderObj->getAdminAllowedUpdateOrderStatuses(true, $orderDetail['op_product_type']);
        } else if ($orderDetail['plugin_code'] == 'PayAtStore') {
            $opTimeLineStatus = $orderObj->getAdminAllowedUpdateOrderStatuses(false, $orderDetail['op_product_type'], true);
        } else {
            $opTimeLineStatus = $orderObj->getAdminAllowedUpdateOrderStatuses(false, $orderDetail['op_product_type']);
        }

        if ($orderDetail["opshipping_fulfillment_type"] == Shipping::FULFILMENT_PICKUP) {
            $opTimeLineStatus = array_diff($opTimeLineStatus, (array) FatApp::getConfig("CONF_DEFAULT_SHIPPING_ORDER_STATUS", FatUtility::VAR_INT, 0));
        } else {
            $opTimeLineStatus = array_diff($opTimeLineStatus, (array) FatApp::getConfig("CONF_PICKUP_READY_ORDER_STATUS", FatUtility::VAR_INT, 0));
        }

        if ($orderDetail['op_product_type'] == Product::PRODUCT_TYPE_PHYSICAL) {
            $opTimeLineStatus = array_diff($opTimeLineStatus, [FatApp::getConfig("CONF_DEFAULT_APPROVED_ORDER_STATUS")]);
        }

        if (FatApp::getConfig("CONF_DEFAULT_CANCEL_ORDER_STATUS") == $orderDetail['orderstatus_id'] || FatApp::getConfig("CONF_RETURN_REQUEST_ORDER_STATUS") == $orderDetail['orderstatus_id']) {
            $opTimeLineStatus[] = $orderDetail['orderstatus_id'];
            $opTimeLineStatus = array_diff($opTimeLineStatus, [FatApp::getConfig("CONF_DEFAULT_COMPLETED_ORDER_STATUS")]);
        }
        if (FatApp::getConfig("CONF_RETURN_REQUEST_APPROVED_ORDER_STATUS") == $orderDetail['orderstatus_id']) {
            $opTimeLineStatus[] = $orderDetail['orderstatus_id'];
            $opTimeLineStatus = array_diff($opTimeLineStatus, [FatApp::getConfig("CONF_DEFAULT_COMPLETED_ORDER_STATUS")]);
        }

        $orderProductStatusArr = Orders::getOrderProductStatusArr($this->siteLangId, $opTimeLineStatus);
        $orderDetail['comments'] = $orderObj->getOrderComments($this->siteLangId, array("op_id" => $op_id, 'seller_id' => $userId));

        $orderTimeLine = [];
        $currentStatus = Orders::ORDER_PAYMENT_PENDING == $orderDetail['order_payment_status'] ? Orders::ORDER_PAYMENT_PENDING : FatApp::getConfig("CONF_DEFAULT_PAID_ORDER_STATUS");
        $highlightEnabled = [];
        if (!empty($orderDetail['comments'])) {
            $currentStatus = current($orderDetail['comments'])['oshistory_orderstatus_id'];
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

        $notEligible = false;
        $notAllowedStatues = $orderObj->getNotAllowedOrderCancellationStatuses();

        if (in_array($orderDetail["op_status_id"], $notAllowedStatues)) {
            $notEligible = true;
            Message::addErrorMessage(sprintf(Labels::getLabel('LBL_this_order_already', $this->siteLangId), $orderProductStatusArr[$orderDetail["op_status_id"]]));
        }
        $opChargesLog = new OrderProductChargeLog($op_id);
        $taxOptions = $opChargesLog->getData($this->siteLangId);
        $orderDetail['taxOptions'] = $taxOptions;

        $frm = $this->getOrderCancelForm($this->siteLangId);
        $frm->fill(array('op_id' => $op_id));

        $this->set('cancelOrder', true);
        $this->set('productType', $orderDetail['op_product_type']);
        $this->set('highlightEnabled', $highlightEnabled);
        $this->set('currentStatus', $currentStatus);
        $this->set('orderProductStatusArr', $orderProductStatusArr);
        $this->set('orderTimeLine', $orderTimeLine);

        $this->set('notEligible', $notEligible);
        $this->set('cancelForm', $frm);
        $this->set('arr', [$orderDetail]);
        $this->set('orderDetail', $orderDetail);
        $this->set('yesNoArr', applicationConstants::getYesNoArr($this->siteLangId));
        $this->set('orderColorClasses', OrderStatus::getOrderStatusColorClassArray());
        $this->_template->render(true, true);
    }

    public function cancelReason()
    {
        $this->userPrivilege->canEditSales(UserAuthentication::getLoggedUserId());
        $frm = $this->getOrderCancelForm($this->siteLangId);
        $post = $frm->getFormDataFromArray(FatApp::getPostedData());

        if (false == $post) {
            FatUtility::dieJsonError(current($frm->getValidationErrors()));
        }

        $op_id = FatUtility::int($post['op_id']);
        if (1 > $op_id) {
            FatUtility::dieJsonError(Labels::getLabel('ERR_INVALID_ACCESS', $this->siteLangId));
        }

        $userId = $this->userParentId;

        $orderObj = new Orders();
        // $processingStatuses = $orderObj->getVendorAllowedUpdateOrderStatuses();

        $srch = new OrderProductSearch($this->siteLangId, true, true);
        $srch->addStatusCondition(unserialize(FatApp::getConfig("CONF_VENDOR_ORDER_STATUS")));
        $srch->joinSellerProducts();
        $srch->joinOrderUser();
        $srch->addCondition('op_selprod_user_id', '=', $userId);
        $srch->addCondition('op_id', '=', $op_id);
        $rs = $srch->getResultSet();

        $orderDetail = (array) FatApp::getDb()->fetch($rs);
        if (empty($orderDetail)) {
            FatUtility::dieJsonError(Labels::getLabel('ERR_INVALID_ACCESS', $this->siteLangId));
        }

        $notAllowedStatues = $orderObj->getNotAllowedOrderCancellationStatuses();
        $orderStatuses = Orders::getOrderProductStatusArr($this->siteLangId);

        if (in_array($orderDetail["op_status_id"], $notAllowedStatues)) {
            FatUtility::dieJsonError(sprintf(Labels::getLabel('LBL_this_order_already', $this->siteLangId), $orderStatuses[$orderDetail["op_status_id"]]));
        }

        if (!$orderObj->addChildProductOrderHistory($op_id, $this->siteLangId, FatApp::getConfig("CONF_DEFAULT_CANCEL_ORDER_STATUS"), $post["comments"], true)) {
            FatUtility::dieJsonError(Labels::getLabel('ERR_ERROR_INVALID_REQUEST', $this->siteLangId));
        }

        /* Update To Shipping Service */
        if (FatApp::getConfig("CONF_DEFAULT_SHIPPING_ORDER_STATUS") == $orderDetail["op_status_id"]) {
            $this->langId = $this->siteLangId;
            $this->refundShipment($op_id);
        }
        /* Update To Shipping Service */

        $pluginKey = Plugin::getAttributesById($orderDetail['order_pmethod_id'], 'plugin_code');

        $paymentMethodObj = new PaymentMethods();
        if (true === $paymentMethodObj->canRefundToCard($pluginKey, $this->siteLangId)) {
            if (false == $paymentMethodObj->initiateRefund($orderDetail, PaymentMethods::REFUND_TYPE_CANCEL)) {
                FatUtility::dieJsonError($paymentMethodObj->getError());
            }

            $resp = $paymentMethodObj->getResponse();
            if (empty($resp)) {
                FatUtility::dieJsonError(Labels::getLabel('ERR_UNABLE_TO_PLACE_GATEWAY_REFUND_REQUEST', $this->siteLangId));
            }

            // Debit from wallet if plugin/payment method support's direct payment to card of customer.
            if (!empty($resp->id)) {
                $childOrderInfo = $orderObj->getOrderProductsByOpId($op_id, $this->siteLangId);
                $txnAmount = $paymentMethodObj->getTxnAmount();
                $comments = Labels::getLabel('LBL_TRANSFERED_TO_YOUR_CARD._INVOICE_#{invoice-no}', $this->siteLangId);
                $comments = CommonHelper::replaceStringData($comments, ['{invoice-no}' => $childOrderInfo['op_invoice_number']]);
                Transactions::debitWallet($childOrderInfo['order_user_id'], Transactions::TYPE_ORDER_REFUND, $txnAmount, $this->siteLangId, $comments, $op_id, $resp->id);
            }
        }

        Message::addMessage(Labels::getLabel("MSG_Updated_Successfully", $this->siteLangId));
        $this->set('msg', Labels::getLabel('MSG_Updated_Successfully', $this->siteLangId));
        $this->_template->render(false, false, 'json-success.php');
    }

    public function catalog($type = 1)
    {
        $this->userPrivilege->canViewProducts(UserAuthentication::getLoggedUserId());

        if (!$this->isShopActive($this->userParentId)) {
            FatApp::redirectUser(UrlHelper::generateUrl('Seller', 'shop'));
        }
        if (1 > FatApp::getConfig('CONF_WITHOUT_PROD_VARIANTS', FatUtility::VAR_INT, 0) && !UserPrivilege::isUserHasValidSubsription($this->userParentId)) {
            Message::addErrorMessage(Labels::getLabel("MSG_PLEASE_BUY_SUBSCRIPTION", $this->siteLangId));
            FatApp::redirectUser(UrlHelper::generateUrl('Seller', 'Packages'));
        }

        if (0 < FatApp::getConfig('CONF_WITHOUT_PROD_VARIANTS', FatUtility::VAR_INT, 0)) {
            $this->set("statusButtons", true);
        }

        $frmSearchCatalogProduct = $this->getCatalogProductSearchForm($type);
        $this->set('canEdit', $this->userPrivilege->canEditProducts(UserAuthentication::getLoggedUserId(), true));
        $this->set("frmSearch", $frmSearchCatalogProduct);
        $this->set('canRequestProduct', User::canRequestProduct());
        $this->set('canAddCustomProduct', User::canAddCustomProduct());
        $this->set('canAddCustomProductAvailableToAllSellers', User::canAddCustomProductAvailableToAllSellers());
        $this->set('type', $type);
        $this->_template->addJs(array('js/cropper.js', 'js/cropper-main.js', 'js/slick.min.js', 'js/select2.js'));
        $this->_template->addCss(array('css/select2.min.css'));

        $this->_template->render(true, true);
    }

    public function toggleBulkStatusesForCatalogs()
    {
        $this->userPrivilege->canEditProducts(UserAuthentication::getLoggedUserId());
        $status = FatApp::getPostedData('status', FatUtility::VAR_INT, -1);
        $recordsArr = FatUtility::int(FatApp::getPostedData('record_ids'));
        if (empty($recordsArr) || -1 == $status) {
            LibHelper::exitWithError($this->str_invalid_request, true);
        }

        foreach ($recordsArr as $recordId) {
            if (1 > $recordId) {
                continue;
            }
            $this->changeCatalogStatus($recordId, $status);
        }
        Product::updateMinPrices();
        $this->set('msg', Labels::getLabel('MSG_STATUS_UPDATED', $this->siteLangId));
        $this->_template->render(false, false, 'json-success.php');
    }

    public function updateCatalogStatus()
    {
        $this->userPrivilege->canEditProducts(UserAuthentication::getLoggedUserId());

        $recordId = FatApp::getPostedData('recordId', FatUtility::VAR_INT, 0);
        if (0 == $recordId) {
            LibHelper::exitWithError($this->str_invalid_request_id, true);
        }
        $status = FatApp::getPostedData('status', FatUtility::VAR_INT, 0);
        if (!in_array($status, [applicationConstants::ACTIVE, applicationConstants::INACTIVE])) {
            LibHelper::exitWithError($this->str_invalid_request, true);
        }

        $this->changeCatalogStatus($recordId, $status);
        $this->set('msg', Labels::getLabel('MSG_STATUS_UPDATED', $this->siteLangId));
        $this->_template->render(false, false, 'json-success.php');
    }

    public function changeCatalogStatus(int $recordId, int $status)
    {
        $status = FatUtility::int($status);
        $recordId = FatUtility::int($recordId);
        if (1 > $recordId || -1 == $status) {
            LibHelper::exitWithError($this->str_invalid_request, true);
        }

        $productName = current(Product::getAttributesByLangId($this->siteLangId, $recordId, ['COALESCE(product_name, product_identifier) as product_name'], applicationConstants::JOIN_INNER));
        $oldProductData = Product::getAttributesById($recordId, ['product_seller_id', 'product_active']);
        if ($this->userParentId != $oldProductData['product_seller_id']) {
            LibHelper::exitWithError($this->str_invalid_request, true);
        }

        if (
            0 < $status &&
            $oldProductData['product_active'] != $status &&
            0 < $oldProductData['product_seller_id'] &&
            FatApp::getConfig('CONF_ENABLE_SELLER_SUBSCRIPTION_MODULE', FatUtility::VAR_INT, 0) &&
            Product::getActiveCount($oldProductData['product_seller_id']) >= SellerPackages::getAllowedLimit($oldProductData['product_seller_id'], $this->siteLangId, 'ossubs_products_allowed')
        ) {
            LibHelper::exitWithError(CommonHelper::replaceStringData(Labels::getLabel('ERR_UNABLE_TO_CHANGE_STATUS_FOR_"{PRODUCT-NAME}"._AS_SELLER_SUBSCRIPTION_PACKAGE_LIMIT_CROSSED.', $this->siteLangId), ['{PRODUCT-NAME}' => $productName]), true);
        }

        $product = new Product($recordId);
        if (!$product->changeStatus($status)) {
            LibHelper::exitWithError($this->modelObj->getError(), true);
        }
        CalculativeDataRecord::updateSelprodRequestCount();
    }

    public function productTags()
    {

        $this->userPrivilege->canViewProducts(UserAuthentication::getLoggedUserId());
        if (!$this->isShopActive($this->userParentId)) {
            FatApp::redirectUser(UrlHelper::generateUrl('Seller', 'shop'));
        }

        if (!User::canAddCustomProduct()) {
            Message::addErrorMessage(Labels::getLabel('ERR_INVALID_ACCESS', $this->siteLangId));
            FatApp::redirectUser(UrlHelper::generateUrl('Seller', 'catalog'));
        }

        $frmSearch = $this->getTagsProdSrchForm();
        $frmSearch->fill(['lang_id' => $this->siteLangId]);
        $this->set("frmSearch", $frmSearch);
        $this->set("languages", Language::getAllNames());
        $this->set('keywordPlaceholder', Labels::getLabel('FRM_SEARCH_BY_PRODUCT_NAME', $this->siteLangId));
        $this->_template->addJs(['js/tagify.min.js', 'js/tagify.polyfills.min.js']);
        $this->_template->addCss(['css/tagify.min.css']);
        $this->_template->render(true, true);
    }

    public function requestedCatalog()
    {
        $this->userPrivilege->canEditProducts(UserAuthentication::getLoggedUserId());
        if (!$this->isShopActive($this->userParentId)) {
            FatApp::redirectUser(UrlHelper::generateUrl('Seller', 'shop'));
        }
        if (!User::canRequestProduct()) {
            Message::addErrorMessage(Labels::getLabel('ERR_INVALID_ACCESS', $this->siteLangId));
            FatApp::redirectUser(UrlHelper::generateUrl('Seller', 'catalog'));
        }
        $this->_template->render(true, true);
    }

    public function searchRequestedCatalog()
    {
        if (!User::canRequestProduct()) {
            Message::addErrorMessage(Labels::getLabel('ERR_INVALID_ACCESS', $this->siteLangId));
            FatUtility::dieWithError(Message::getHtml());
        }
        $post = FatApp::getPostedData();
        $page = (empty($post['page']) || $post['page'] <= 0) ? 1 : intval($post['page']);
        $pagesize = FatApp::getConfig('CONF_PAGE_SIZE', FatUtility::VAR_INT, 10);

        $cRequestObj = new User($this->userParentId);
        $srch = $cRequestObj->getUserCatalogRequestsObj();
        $srch->addMultipleFields(
            array(
                'scatrequest_id',
                'scatrequest_user_id',
                'scatrequest_reference',
                'scatrequest_title',
                'scatrequest_comments',
                'scatrequest_status',
                'scatrequest_date'
            )
        );
        $srch->addOrder('scatrequest_date', 'DESC');
        $srch->setPageNumber($page);
        $srch->setPageSize($pagesize);

        $db = FatApp::getDb();
        $rs = $srch->getResultSet();

        $arrListing = $db->fetchAll($rs);

        $this->set("arrListing", $arrListing);
        $this->set('pageCount', $srch->pages());
        $this->set('page', $page);
        $this->set('pageSize', $pagesize);
        $this->set('postedData', $post);
        $this->set('catalogReqStatusArr', User::getCatalogReqStatusArr($this->siteLangId));
        $this->_template->render(false, false);
    }

    public function addCatalogRequest()
    {
        if (!User::canRequestProduct()) {
            Message::addErrorMessage(Labels::getLabel('ERR_INVALID_ACCESS', $this->siteLangId));
            FatUtility::dieWithError(Message::getHtml());
        }
        $frm = $this->addNewCatalogRequestForm();
        $this->set('frm', $frm);
        $this->_template->render(false, false);
    }

    public function setUpCatalogRequest()
    {
        if (!User::canRequestProduct()) {
            Message::addErrorMessage(Labels::getLabel('ERR_INVALID_ACCESS', $this->siteLangId));
            FatUtility::dieWithError(Message::getHtml());
        }
        $userId = $this->userParentId;

        $frm = $this->addNewCatalogRequestForm();
        $post = $frm->getFormDataFromArray(FatApp::getPostedData());

        if (false == $post) {
            FatUtility::dieJsonError(current($frm->getValidationErrors()));
        }

        $obj = new User($userId);
        $reference_number = $userId . '-' . time();

        $db = FatApp::getDb();
        $db->startTransaction();

        $data = array(
            'scatrequest_user_id' => $userId,
            'scatrequest_reference' => $reference_number,
            'scatrequest_title' => $post['scatrequest_title'],
            'scatrequest_content' => $post['scatrequest_content'],
            'scatrequest_date' => date('Y-m-d H:i:s'),
        );

        if (!$obj->addCatalogRequest($data)) {
            $db->rollbackTransaction();
            FatUtility::dieJsonError($obj->getError());
        }

        $scatrequest_id = FatApp::getDb()->getInsertId();
        if (!$scatrequest_id) {
            Message::addErrorMessage(Labels::getLabel('ERR_SOMETHING_WENT_WRONG,_please_contact_admin', $this->siteLangId));
            FatUtility::dieWithError(Message::getHtml());
        }

        /* attach file with request [ */

        if (is_uploaded_file($_FILES['file']['tmp_name'])) {
            $uploadedFile = $_FILES['file']['tmp_name'];
            $uploadedFileExt = pathinfo($uploadedFile, PATHINFO_EXTENSION);

            if (filesize($uploadedFile) > 10240000) {
                FatUtility::dieJsonError(Labels::getLabel('ERR_PLEASE_UPLOAD_FILE_SIZE_LESS_THAN_10MB', $this->siteLangId));
            }

            $fileHandlerObj = new AttachedFile();
            if (!$res = $fileHandlerObj->saveAttachment($_FILES['file']['tmp_name'], AttachedFile::FILETYPE_SELLER_CATALOG_REQUEST, $scatrequest_id, 0, $_FILES['file']['name'], -1, true)) {
                FatUtility::dieJsonError($fileHandlerObj->getError());
            }
        }

        /* ] */

        if (!$obj->notifyAdminCatalogRequest($data, $this->siteLangId)) {
            $db->rollbackTransaction();
            FatUtility::dieJsonError(Labels::getLabel("ERR_NOTIFICATION_EMAIL_COULD_NOT_BE_SENT", $this->siteLangId));
        }

        //send notification to admin
        $notificationData = array(
            'notification_record_type' => Notification::TYPE_CATALOG,
            'notification_record_id' => $scatrequest_id,
            'notification_user_id' => $userId,
            'notification_label_key' => Notification::NEW_CATALOG_REQUEST_NOTIFICATION,
            'notification_added_on' => date('Y-m-d H:i:s'),
        );

        if (!Notification::saveNotifications($notificationData)) {
            $db->rollbackTransaction();
            FatUtility::dieJsonError(Labels::getLabel("ERR_NOTIFICATION_COULD_NOT_BE_SENT", $this->siteLangId));
        }

        $db->commitTransaction();
        $this->set('msg', Labels::getLabel('MSG_CATALOG_REQUESTED_SUCCESSFULLY', $this->siteLangId));
        $this->_template->render(false, false, 'json-success.php');
    }

    public function viewRequestedCatalog($scatrequest_id)
    {
        $scatrequest_id = FatUtility::int($scatrequest_id);
        if (1 > $scatrequest_id) {
            FatUtility::dieWithError(Labels::getLabel('MSG_Invalid_Request', $this->siteLangId));
        }

        $cRequestObj = new User($this->userParentId);
        $srch = $cRequestObj->getUserCatalogRequestsObj($scatrequest_id);
        $srch->addCondition('tucr.scatrequest_user_id', '=', $this->userParentId);
        $srch->addMultipleFields(array('scatrequest_id', 'scatrequest_title', 'scatrequest_content', 'scatrequest_comments', 'scatrequest_reference'));
        $srch->doNotCalculateRecords();
        $srch->doNotLimitRecords();

        $rs = $srch->getResultSet();
        $row = FatApp::getDb()->fetch($rs);
        if (!$row) {
            FatUtility::dieWithError(Labels::getLabel('MSG_Invalid_Request', $this->siteLangId));
        }

        $this->set("data", $row);
        $this->_template->render(false, false);
    }

    public function catalogRequestMsgForm($requestId = 0)
    {
        $requestId = FatUtility::int($requestId);
        $frm = $this->getCatalogRequestMessageForm($requestId);

        if (0 >= $requestId) {
            FatUtility::dieWithError(Labels::getLabel('LBL_INVALID_REQUEST', $this->siteLangId));
        }
        $userObj = new User();
        $srch = $userObj->getUserSupplierRequestsObj($requestId);
        $srch->addFld('tusr.*');

        $rs = $srch->getResultSet();

        if (!$rs || FatApp::getDb()->fetch($rs) === false) {
            FatUtility::dieWithError(Labels::getLabel('LBL_INVALID_REQUEST', $this->siteLangId));
        }

        $this->set('requestId', $requestId);

        $this->set('frm', $frm);
        $this->set('logged_user_id', $this->userParentId);
        $this->set('logged_user_name', UserAuthentication::getLoggedUserAttribute('user_name'));

        $searchFrm = $this->getCatalogRequestMessageSearchForm();
        $searchFrm->getField('requestId')->value = $requestId;
        $this->set('searchFrm', $searchFrm);

        $this->_template->render(false, false);
    }

    public function catalogRequestMessageSearch()
    {
        $frm = $this->getCatalogRequestMessageSearchForm();
        $post = $frm->getFormDataFromArray(FatApp::getPostedData());
        $page = (empty($post['page']) || $post['page'] <= 0) ? 1 : FatUtility::int($post['page']);
        $pageSize = 1;

        $requestId = isset($post['requestId']) ? FatUtility::int($post['requestId']) : 0;

        $srch = new CatalogRequestMessageSearch();
        $srch->joinCatalogRequests();
        $srch->joinMessageUser();
        $srch->joinMessageAdmin();
        $srch->addCondition('scatrequestmsg_scatrequest_id', '=', $requestId);
        $srch->setPageNumber($page);
        $srch->setPageSize($pageSize);
        $srch->addOrder('scatrequestmsg_id', 'DESC');
        $srch->addMultipleFields(
            array(
                'scatrequestmsg_id',
                'scatrequestmsg_from_user_id',
                'scatrequestmsg_from_admin_id',
                'admin_name',
                'admin_username',
                'admin_email',
                'scatrequestmsg_msg',
                'scatrequestmsg_date',
                'msg_user.user_name as msg_user_name',
                'msg_user_cred.credential_username as msg_username',
                'msg_user_cred.credential_email as msg_user_email',
                'scatrequest_status'
            )
        );

        $rs = $srch->getResultSet();
        $messagesList = FatApp::getDb()->fetchAll($rs, 'scatrequestmsg_id');
        ksort($messagesList);

        $this->set('messagesList', $messagesList);
        $this->set('page', $page);
        $this->set('pageSize', $pageSize);
        $this->set('pageCount', $srch->pages());
        $this->set('postedData', $post);

        $startRecord = ($page - 1) * $pageSize + 1;
        $endRecord = $page * $pageSize;
        $totalRecords = $srch->recordCount();
        if ($totalRecords < $endRecord) {
            $endRecord = $totalRecords;
        }
        $json['totalRecords'] = $totalRecords;
        $json['startRecord'] = $startRecord;
        $json['endRecord'] = $endRecord;

        $json['html'] = $this->_template->render(false, false, 'seller/catalog-request-messages-list.php', true, false);
        $json['loadMoreBtnHtml'] = $this->_template->render(false, false, 'seller/catalog-request-messages-list-load-more-btn.php', true, false);
        FatUtility::dieJsonSuccess($json);
    }

    public function setUpCatalogRequestMessage()
    {
        $requestId = FatApp::getPostedData('requestId', null, '0');
        $frm = $this->getCatalogRequestMessageForm($requestId);
        $post = $frm->getFormDataFromArray(FatApp::getPostedData());
        if (false === $post) {
            Message::addErrorMessage(current($frm->getValidationErrors()));
            FatUtility::dieWithError(Message::getHtml());
        }

        $requestId = FatUtility::int($requestId);

        $srch = new CatalogRequestSearch($this->siteLangId);
        $srch->addCondition('scatrequest_id', '=', $requestId);
        $srch->doNotCalculateRecords();
        $srch->doNotLimitRecords();
        $srch->addMultipleFields(array('scatrequest_id', 'scatrequest_status'));
        $rs = $srch->getResultSet();
        $requestRow = FatApp::getDb()->fetch($rs);
        if (!$requestRow) {
            Message::addErrorMessage(Labels::getLabel('ERR_INVALID_ACCESS', $this->siteLangId));
            FatUtility::dieWithError(Message::getHtml());
        }

        /* save catalog request message[ */
        $dataToSave = array(
            'scatrequestmsg_scatrequest_id' => $requestRow['scatrequest_id'],
            'scatrequestmsg_from_user_id' => $this->userParentId,
            'scatrequestmsg_from_admin_id' => 0,
            'scatrequestmsg_msg' => $post['message'],
            'scatrequestmsg_date' => date('Y-m-d H:i:s'),
        );
        $catRequestMsgObj = new CatalogRequestMessage();
        $catRequestMsgObj->assignValues($dataToSave, true);
        if (!$catRequestMsgObj->save()) {
            Message::addErrorMessage($catRequestMsgObj->getError());
            FatUtility::dieWithError(Message::getHtml());
        }
        $scatrequestmsg_id = $catRequestMsgObj->getMainTableRecordId();
        if (!$scatrequestmsg_id) {
            Message::addErrorMessage(Labels::getLabel('ERR_SOMETHING_WENT_WRONG,_please_contact_Technical_team', $this->siteLangId));
            FatUtility::dieWithError(Message::getHtml());
        }
        /* ] */

        /* sending of email notification[ */
        $emailNotificationObj = new EmailHandler();
        if (!$emailNotificationObj->sendCatalogRequestMessageNotification($scatrequestmsg_id, $this->siteLangId)) {
            Message::addErrorMessage($emailNotificationObj->getError());
            FatUtility::dieWithError(Message::getHtml());
        }
        /* ] */

        /* send notification to admin [ */
        $notificationData = array(
            'notification_record_type' => Notification::TYPE_CATALOG_REQUEST,
            'notification_record_id' => $scatrequestmsg_id,
            'notification_user_id' => $this->userParentId,
            'notification_label_key' => Notification::CATALOG_REQUEST_MESSAGE_NOTIFICATION,
            'notification_added_on' => date('Y-m-d H:i:s'),
        );

        if (!Notification::saveNotifications($notificationData)) {
            FatUtility::dieJsonError(Labels::getLabel("ERR_NOTIFICATION_COULD_NOT_BE_SENT", $this->siteLangId));
        }
        /* ] */

        $this->set('scatrequestmsg_scatrequest_id', $requestId);
        $this->set('msg', Labels::getLabel('MSG_Message_Submitted_Successfully!', $this->siteLangId));
        $this->_template->render(false, false, 'json-success.php');
    }

    public function deleteRequestedCatalog()
    {
        $post = FatApp::getPostedData();
        $scatrequest_id = FatUtility::int($post['scatrequest_id']);

        if (1 > $scatrequest_id) {
            FatUtility::dieJsonError(Labels::getLabel('ERR_INVALID_REQUEST', $this->siteLangId));
        }

        $cRequestObj = new User($this->userParentId);
        $srch = $cRequestObj->getUserCatalogRequestsObj($scatrequest_id);
        $srch->addCondition('tucr.scatrequest_user_id', '=', $this->userParentId);
        $srch->addCondition('tucr.scatrequest_status', '=', 0);
        $srch->addMultipleFields(array('scatrequest_id', 'scatrequest_status'));
        $srch->doNotCalculateRecords();
        $srch->doNotLimitRecords();

        $rs = $srch->getResultSet();

        $row = FatApp::getDb()->fetch($rs);

        if ($row == false || ($row != false && $row['scatrequest_status'] != User::CATALOG_REQUEST_PENDING)) {
            FatUtility::dieJsonError(Labels::getLabel('ERR_INVALID_REQUEST', $this->siteLangId));
        }

        if (!$cRequestObj->deleteCatalogRequest($row['scatrequest_id'])) {
            FatUtility::dieJsonError(Labels::getLabel($cRequestObj->getError(), $this->siteLangId));
        }

        $this->set('scatrequest_id', $row['scatrequest_id']);
        $this->set('msg', Labels::getLabel('MSG_RECORD_DELETED_SUCCESSFULLY', $this->siteLangId));
        $this->_template->render(false, false, 'json-success.php');
    }

    public function searchCatalogProduct()
    {
        $this->userPrivilege->canViewProducts(UserAuthentication::getLoggedUserId());
        $frmSearchCatalogProduct = $this->getCatalogProductSearchForm();
        $post = $frmSearchCatalogProduct->getFormDataFromArray(FatApp::getPostedData());

        $page = (empty($post['page']) || $post['page'] <= 0) ? 1 : intval($post['page']);
        $pagesize = FatApp::getConfig('CONF_PAGE_SIZE', FatUtility::VAR_INT, 10);

        $srch = new ProductSearch($this->siteLangId, null, null, false, false);
        $srch->joinProductShippedBySeller($this->userParentId);
        $srch->joinTable(AttributeGroup::DB_TBL, 'LEFT OUTER JOIN', 'product_attrgrp_id = attrgrp_id', 'attrgrp');
        $srch->joinTable(UpcCode::DB_TBL, 'LEFT OUTER JOIN', 'upc_product_id = product_id', 'upc');


        if (FatApp::getConfig('CONF_WITHOUT_PROD_VARIANTS', FatUtility::VAR_INT, 0)) {
            $srch->joinTable(SellerProduct::DB_TBL, 'LEFT JOIN', 'selprod_product_id = product_id', 'sp');
            $srch->addGroupBy('selprod_product_id');
            $srch->addFld('selprod_id');
        }

        $srch->addDirectCondition(
            '((CASE
                    WHEN product_seller_id = 0 THEN product_active = 1
                    WHEN product_seller_id > 0 THEN product_active IN (1, 0)
                    END ) )'
        );
        if (User::canAddCustomProduct()) {
            $srch->addDirectCondition('((product_seller_id = 0 AND product_added_by_admin_id = ' . applicationConstants::YES . ') OR product_seller_id = ' . $this->userParentId . ')');
        } else {
            $cnd = $srch->addCondition('product_seller_id', '=', 0);
            $cnd->attachCondition('product_added_by_admin_id', '=', applicationConstants::YES, 'AND');
        }

        $srch->addCondition('product_deleted', '=', applicationConstants::NO);

        $keyword = FatApp::getPostedData('keyword', FatUtility::VAR_STRING, '');
        if (!empty($keyword)) {
            $cnd = $srch->addCondition('product_name', 'like', '%' . $keyword . '%');
            $cnd->attachCondition('product_identifier', 'like', '%' . $keyword . '%', 'OR');
            $cnd->attachCondition('attrgrp_name', 'like', '%' . $keyword . '%');
            $cnd->attachCondition('product_model', 'like', '%' . $keyword . '%');
            $cnd->attachCondition('upc_code', 'like', '%' . $keyword . '%');
            $cnd->attachCondition('product_upc', 'like', '%' . $keyword . '%');
        }

        if (FatApp::getConfig('CONF_ENABLED_SELLER_CUSTOM_PRODUCT')) {
            $is_custom_or_catalog = FatApp::getPostedData('type', FatUtility::VAR_INT, -1);
            if ($is_custom_or_catalog > -1) {
                if ($is_custom_or_catalog > 0) {
                    $srch->addCondition('product_seller_id', '>', 0);
                } else {
                    $srch->addCondition('product_seller_id', '=', 0);
                }
            }
        }

        $product_type = FatApp::getPostedData('product_type', FatUtility::VAR_INT, -1);
        if ($product_type != -1) {
            $srch->addCondition('product_type', '=', $product_type);
        }

        $attr = array(
            'product_id',
            'product_identifier',
            'IFNULL(product_name, product_identifier) as product_name',
            'product_added_on',
            'product_model',
            'product_attrgrp_id',
            'attrgrp_name',
            'psbs_user_id',
            'product_seller_id ',
            'product_added_by_admin_id',
            'product_type',
            'product_active',
            'product_approved',
            'product_updated_on',
            'product_attachements_with_inventory'
        );

        $srch->addMultipleFields($attr);
        $srch->addOrder('product_active', 'DESC');
        $srch->addOrder('product_added_on', 'DESC');
        $srch->addGroupBy('product_id');
        $srch->setPageNumber($page);
        $srch->setPageSize($pagesize);
        $db = FatApp::getDb();
        $rs = $srch->getResultSet();
        $arrListing = $db->fetchAll($rs);

        $this->set("arrListing", $arrListing);
        $this->set('pageCount', $srch->pages());
        $this->set('recordCount', $srch->recordCount());
        $this->set('page', $page);
        $this->set('pageSize', $pagesize);
        $this->set('postedData', $post);
        $this->set('siteLangId', $this->siteLangId);
        $this->set('userParentId', $this->userParentId);
        $this->set('canEdit', $this->userPrivilege->canEditProducts(UserAuthentication::getLoggedUserId(), true));
        unset($post['page']);
        $this->set('canEditShipProfile', $this->userPrivilege->canEditShippingProfiles(UserAuthentication::getLoggedUserId(), true));
        unset($post['page']);
        $frmSearchCatalogProduct->fill($post);
        $this->set("frmSearchCatalogProduct", $frmSearchCatalogProduct);
        $this->set('activeInactiveArr', applicationConstants::getActiveInactiveArr($this->siteLangId));
        $this->set('activeInactiveClassArr', applicationConstants::getActiveInactiveClassArr());
        $this->set('approveUnApproveArr', Product::getApproveUnApproveArr($this->siteLangId));
        $this->set('approveUnApproveClassArr', product::getStatusClassArr());
        $this->_template->render(false, false);
    }

    public function searchProductTags()
    {
        $srchFrm = $this->getTagsProdSrchForm();
        $post = $srchFrm->getFormDataFromArray(FatApp::getPostedData());
        $page = (empty($post['page']) || $post['page'] <= 0) ? 1 : intval($post['page']);
        /* echo $page; die; */
        $pagesize = FatApp::getConfig('CONF_PAGE_SIZE', FatUtility::VAR_INT, 10);

        $langId = FatApp::getPostedData('lang_id', FatUtility::VAR_INT, $this->siteLangId);

        //$srch = Product::getSearchObject($this->siteLangId);
        $srch = new ProductSearch($langId, null, null, true, true, true);
        $srch->joinProductShippedBySeller($this->userParentId);
        $srch->joinTable(AttributeGroup::DB_TBL, 'LEFT OUTER JOIN', 'product_attrgrp_id = attrgrp_id', 'attrgrp');
        $srch->joinTable(UpcCode::DB_TBL, 'LEFT OUTER JOIN', 'upc_product_id = product_id', 'upc');
        $srch->addCondition('product_seller_id', '=', $this->userParentId);
        $srch->addDirectCondition(
            '((CASE
                    WHEN product_seller_id = 0 THEN product_active = 1
                    WHEN product_seller_id > 0 THEN product_active IN (1, 0)
                    END ) )'
        );
        if (User::canAddCustomProduct()) {
            $srch->addDirectCondition('((product_seller_id = 0 AND product_added_by_admin_id = ' . applicationConstants::YES . ') OR product_seller_id = ' . $this->userParentId . ')');
        } else {
            $cnd = $srch->addCondition('product_seller_id', '=', 0);
            $cnd->attachCondition('product_added_by_admin_id', '=', applicationConstants::YES, 'AND');
        }

        $srch->addCondition('product_deleted', '=', applicationConstants::NO);

        $keyword = FatApp::getPostedData('keyword', null, '');
        if (!empty($keyword)) {
            $cnd = $srch->addCondition('product_name', 'like', '%' . $keyword . '%');
            $cnd->attachCondition('product_identifier', 'like', '%' . $keyword . '%', 'OR');
            $cnd->attachCondition('attrgrp_name', 'like', '%' . $keyword . '%');
            $cnd->attachCondition('product_model', 'like', '%' . $keyword . '%');
            $cnd->attachCondition('upc_code', 'like', '%' . $keyword . '%');
            $cnd->attachCondition('product_upc', 'like', '%' . $keyword . '%');
        }

        $srch->addMultipleFields(
            array(
                'product_id',
                'product_identifier',
                'IFNULL(product_name, product_identifier) as product_name',
            )
        );

        $srch->addOrder('product_active', 'DESC');
        $srch->addOrder('product_added_on', 'DESC');
        $srch->addGroupBy('product_id');
        $srch->setPageNumber($page);
        $srch->setPageSize($pagesize);
        $db = FatApp::getDb();
        $rs = $srch->getResultSet();
        $arrListing = $db->fetchAll($rs);
        $this->set("arrListing", $arrListing);
        $this->set('pageCount', $srch->pages());
        $this->set('page', $page);
        $this->set('pageSize', $pagesize);
        $this->set('postedData', $post);
        $this->set('langId', $langId);
        $this->set('formLayout', Language::getLayoutDirection($langId));
        unset($post['page']);
        $this->set('canEdit', $this->userPrivilege->canEditProducts(UserAuthentication::getLoggedUserId(), true));
        $this->_template->render(false, false);
    }

    public function setUpshippedBy()
    {
        $this->userPrivilege->canEditShippingProfiles(UserAuthentication::getLoggedUserId());

        $post = FatApp::getPostedData();
        if (false === $post) {
            Message::addErrorMessage(Labels::getLabel('ERR_INVALID_REQUEST', $this->siteLangId));
            FatUtility::dieWithError(Message::getHtml());
        }

        $product_id = FatUtility::int($post['product_id']);
        $shippedBy = $post['shippedBy'];
        $userId = $this->userParentId;

        if (1 > $product_id && 1 > $userId) {
            Message::addErrorMessage(Labels::getLabel('ERR_INVALID_REQUEST', $this->siteLangId));
            FatUtility::dieWithError(Message::getHtml());
        }

        $db = FatApp::getDb();
        if ($shippedBy == 'admin') {
            $whr = array('smt' => 'psbs_product_id = ? and psbs_user_id = ?', 'vals' => array($product_id, $userId));
            if (!$db->deleteRecords(Product::DB_PRODUCT_SHIPPED_BY_SELLER, $whr)) {
                Message::addErrorMessage($db->getError());
                FatUtility::dieWithError(Message::getHtml());
            }
            $whr = array('smt' => 'shippro_product_id = ? and shippro_user_id = ?', 'vals' => array($product_id, $userId));
            if (!$db->deleteRecords(ShippingProfileProduct::DB_TBL, $whr)) {
                Message::addErrorMessage($db->getError());
                FatUtility::dieWithError(Message::getHtml());
            }
        } elseif ($shippedBy == 'seller') {
            $data = array('psbs_product_id' => $product_id, 'psbs_user_id' => $userId);
            if (!$db->insertFromArray(Product::DB_PRODUCT_SHIPPED_BY_SELLER, $data)) {
                Message::addErrorMessage($db->getError());
                FatUtility::dieWithError(Message::getHtml());
            }
            $defaultProfileId = ShippingProfile::getDefaultProfileId($userId);
            $shipProProdData = array(
                'shippro_shipprofile_id' => $defaultProfileId,
                'shippro_product_id' => $product_id,
                'shippro_user_id' => $this->userParentId
            );
            $spObj = new ShippingProfileProduct();
            if (!$spObj->addProduct($shipProProdData)) {
                Message::addErrorMessage($spObj->getError());
                FatUtility::dieWithError(Message::getHtml());
            }
        } else {
            Message::addErrorMessage(Labels::getLabel('ERR_INVALID_REQUEST', $this->siteLangId));
            FatUtility::dieWithError(Message::getHtml());
        }

        $this->set('msg', Labels::getLabel('MSG_UPDATED_SUCCESSFULLY', $this->siteLangId));
        $this->_template->render(false, false, 'json-success.php');
    }

    public function taxCategories()
    {
        if (!FatApp::getConfig('CONF_ENABLED_SELLER_CUSTOM_PRODUCT', FatUtility::VAR_INT, 0)) {
            Message::addErrorMessage(Labels::getLabel('ERR_INVALID_ACCESS', $this->siteLangId));
            FatApp::redirectUser(UrlHelper::generateUrl('Seller', 'products'));
        }
        $this->userPrivilege->canViewTaxCategory(UserAuthentication::getLoggedUserId());
        $frmSearch = $this->getTaxCatSearchForm($this->siteLangId);
        $this->set("frmSearch", $frmSearch);
        $this->set("keywordPlaceholder", Labels::getLabel('LBL_SEARCH_BY_TAX_CATEGORY_NAME', $this->siteLangId));
        $this->_template->render(true, true);
    }

    public function searchTaxCategories()
    {
        $pagesize = FatApp::getConfig('CONF_PAGE_SIZE', FatUtility::VAR_INT, 10);
        $frmSearch = $this->getTaxCatSearchForm($this->siteLangId);
        $data = FatApp::getPostedData();
        $page = (empty($data['page']) || $data['page'] <= 0) ? 1 : $data['page'];
        $post = $frmSearch->getFormDataFromArray($data);
        $page = (empty($page) || $page <= 0) ? 1 : $page;
        $page = FatUtility::int($page);

        $srch = Tax::getSearchObject($this->siteLangId);
        $srch->joinTable(TaxRule::DB_TBL, 'LEFT OUTER JOIN', 'taxRule.taxrule_taxcat_id = taxcat_id', 'taxRule');
        $srch->joinTable(TaxRule::DB_RATES_TBL, 'LEFT OUTER JOIN', TaxRule::tblFld('id') . '=' . TaxRule::DB_RATES_TBL_PREFIX . TaxRule::tblFld('id') . ' and ' . TaxRule::DB_RATES_TBL_PREFIX . 'user_id = 0');
        if (!empty($post['keyword'])) {
            $cnd = $srch->addCondition('t.taxcat_identifier', 'like', '%' . $post['keyword'] . '%');
            $cnd->attachCondition('t_l.taxcat_name', 'like', '%' . $post['keyword'] . '%');
        }

        $activatedTaxServiceId = Tax::getActivatedServiceId();
        $srch->addCondition('taxcat_plugin_id', '=', $activatedTaxServiceId);
        $srch->addCondition('taxcat_deleted', '=', 0);
        $srch->addGroupBy('taxcat_id');
        $this->setRecordCount(clone $srch, $pagesize, $page, $post, true);
        $srch->doNotCalculateRecords();
        $srch->addMultipleFields(array('taxcat_id', 'IFNULL(taxcat_name, taxcat_identifier) as taxcat_name', 'taxcat_code', 'trr_rate'));
        $srch->setPageNumber($page);
        $srch->setPageSize($pagesize);
        $srch->addOrder('taxcat_name', 'ASC');
        $this->set("arrListing", FatApp::getDb()->fetchAll($srch->getResultSet(), 'taxcat_id'));
        $this->set('postedData', $post);
        $this->set('userId', $this->userParentId);
        $this->set('activatedTaxServiceId', $activatedTaxServiceId);
        $this->set('canEdit', $this->userPrivilege->canEditTaxCategory(UserAuthentication::getLoggedUserId(), true));
        $this->_template->render(false, false);
    }

    public function taxRules($taxCatId)
    {
        $this->userPrivilege->canViewTaxCategory(UserAuthentication::getLoggedUserId());
        $taxCatId = FatUtility::int($taxCatId);

        $srch = Tax::getSearchObject($this->siteLangId);
        $srch->addCondition('taxcat_id', '=', $taxCatId);
        $srch->setPageSize(1);
        $srch->doNotCalculateRecords();
        $srch->addMultipleFields(array('ifnull(taxcat_name, taxcat_identifier) as taxcat_name', 'taxcat_id'));
        $rs = $srch->getResultSet();
        $data = FatApp::getDb()->fetch($rs);

        if (empty($data)) {
            FatUtility::dieWithError(Labels::getLabel('MSG_INVALID_REQUEST', $this->siteLangId));
        }

        $frmSearch = $this->getTaxRulesSearchForm($taxCatId);
        $this->set('frmSearch', $frmSearch);
        $this->set('taxCategory', $data['taxcat_name']);
        $this->_template->render(true, true);
    }

    public function taxRulesSearch()
    {
        $userId = UserAuthentication::getLoggedUserId();
        $pagesize = FatApp::getConfig('CONF_PAGE_SIZE', FatUtility::VAR_INT, 10);
        $taxCatId = FatApp::getPostedData('taxCatId', FatUtility::VAR_INT, 0);
        $page = FatApp::getPostedData('page', FatUtility::VAR_INT, 0);
        if (1 > $page) {
            $page = 1;
        }

        $srch = TaxRule::getSearchObject();
        $srch->addCondition('taxrule_taxcat_id', '=', $taxCatId);

        $userSpecificRateSrch = clone $srch;
        $userSpecificRateSrch->joinTable(TaxRule::DB_RATES_TBL, 'INNER JOIN', TaxRule::tblFld('id') . '=' . TaxRule::DB_RATES_TBL_PREFIX . TaxRule::tblFld('id') . ' and ' . TaxRule::DB_RATES_TBL_PREFIX . 'user_id = ' . $userId);
        $userSpecificRateSrch->doNotCalculateRecords();
        $userSpecificRateSrch->doNotLimitRecords();
        $userSpecificRateSrch->addMultipleFields(array('trr_rate as user_rule_rate', 'taxrule_id'));
        $userSpecificSubQuery = $userSpecificRateSrch->getQuery();

        $srch = TaxRule::getSearchObject();
        $srch->joinTable(TaxRule::DB_RATES_TBL, 'INNER JOIN', "taxRule." . TaxRule::tblFld('id') . '=' . TaxRule::DB_RATES_TBL_PREFIX . TaxRule::tblFld('id') . ' and ' . TaxRule::DB_RATES_TBL_PREFIX . 'user_id = 0');
        $srch->joinTable('(' . $userSpecificSubQuery . ')', 'LEFT OUTER JOIN', 'user_specific_rule_rate.taxrule_id = taxRule.taxrule_id', 'user_specific_rule_rate');
        $srch->joinTable(TaxStructure::DB_TBL, 'LEFT JOIN', 'taxRule.taxrule_taxstr_id = taxstr_id');
        $srch->joinTable(TaxStructure::DB_TBL_LANG, 'LEFT JOIN', 'taxstr_id = taxstrlang_taxstr_id and taxstrlang_lang_id = ' . $this->siteLangId);
        $srch->joinTable(TaxRuleLocation::DB_TBL, 'LEFT JOIN', TaxRuleLocation::tblFld('taxrule_id') . '= taxRule.' . TaxRule::tblFld('id'), 'trloc');

        $srch->joinTable(States::DB_TBL, 'LEFT OUTER JOIN', 'from_st.state_id = trloc.taxruleloc_from_state_id', 'from_st');
        $srch->joinTable(States::DB_TBL_LANG, 'LEFT OUTER JOIN', 'from_st_l.statelang_state_id = from_st.state_id  AND from_st_l.statelang_lang_id = ' . $this->siteLangId, 'from_st_l');

        $srch->joinTable(Countries::DB_TBL, 'LEFT OUTER JOIN', 'from_c.country_id = trloc.taxruleloc_from_country_id', 'from_c');
        $srch->joinTable(Countries::DB_TBL_LANG, 'LEFT OUTER JOIN', 'from_c_l.countrylang_country_id = from_c.country_id  AND from_c_l.countrylang_lang_id = ' . $this->siteLangId, 'from_c_l');

        $srch->joinTable(States::DB_TBL, 'LEFT OUTER JOIN', 'to_st.state_id = trloc.taxruleloc_to_state_id', 'to_st');
        $srch->joinTable(States::DB_TBL_LANG, 'LEFT OUTER JOIN', 'to_st_l.statelang_state_id=to_st.state_id AND to_st_l.statelang_lang_id = ' . $this->siteLangId, 'to_st_l');

        $srch->joinTable(Countries::DB_TBL, 'LEFT OUTER JOIN', 'to_c.country_id = trloc.taxruleloc_to_country_id', 'to_c');
        $srch->joinTable(Countries::DB_TBL_LANG, 'LEFT OUTER JOIN', 'to_c_l.countrylang_country_id = to_c.country_id AND to_c_l.countrylang_lang_id = ' . $this->siteLangId, 'to_c_l');

        $srch->addDirectCondition('CASE WHEN from_c.country_id > 0 THEN from_c.country_active = 1 ELSE 1 END');
        $srch->addDirectCondition('CASE WHEN to_c.country_id > 0 THEN to_c.country_active = 1 ELSE 1 END');

        $srch->addCondition('taxrule_taxcat_id', '=', $taxCatId);

        $srch->addMultipleFields(array('taxRule.taxrule_id', 'taxstr_name', 'taxstr_is_combined', 'taxrule_name', 'trr_rate', 'taxrule_taxcat_id', 'taxruleloc_type', 'IFNULL(from_c_l.country_name, from_c.country_code) as from_country', 'GROUP_CONCAT(DISTINCT IFNULL(from_st_l.state_name, from_st.state_identifier)) as from_state', 'IFNULL(to_c_l.country_name, to_c.country_code) as to_country', 'GROUP_CONCAT(DISTINCT IFNULL(to_st_l.state_name, to_st.state_identifier)) as to_state', 'user_specific_rule_rate.user_rule_rate', 'to_c.country_active'));
        $srch->addGroupBy("taxRule." . TaxRule::tblFld('id'));

        $srch->setPageNumber($page);
        $srch->setPageSize($pagesize);
        $srch->addOrder('taxrule_name', 'ASC');

        $records = FatApp::getDb()->fetchAll($srch->getResultSet());

        $rulesIds = array_column($records, 'taxrule_id');
        $combinedData = [];

        if (!empty($rulesIds)) {
            $userSpecificCombiRateSrch = TaxRule::getCombinedTaxSearchObject();
            $userSpecificCombiRateSrch->addCondition('taxruledet_user_id', '=', $userId);
            $userSpecificCombiRateSrch->addCondition('taxruledet_taxrule_id', 'IN', $rulesIds);
            $userSpecificCombiRateSrch->doNotCalculateRecords();
            $userSpecificCombiRateSrch->doNotLimitRecords();
            $userSpecificCombiRateSrch->addMultipleFields(array('taxruledet_rate as user_rate', 'taxruledet_taxrule_id', 'taxruledet_taxstr_id'));
            $userSpecificCombiSubQuery = $userSpecificCombiRateSrch->getQuery();

            $combinedTaxSrch = TaxRule::getCombinedTaxSearchObject();
            $combinedTaxSrch->joinTable(TaxStructure::DB_TBL, 'LEFT JOIN', 'tc.taxruledet_taxstr_id = taxstr_id');
            $combinedTaxSrch->joinTable(TaxStructure::DB_TBL_LANG, 'LEFT JOIN', 'taxstr_id = taxstrlang_taxstr_id and taxstrlang_lang_id = ' . $this->siteLangId);
            $combinedTaxSrch->addCondition('tc.taxruledet_taxrule_id', 'IN', $rulesIds);
            $combinedTaxSrch->addCondition('tc.taxruledet_user_id', '=', 0);
            $combinedTaxSrch->joinTable('(' . $userSpecificCombiSubQuery . ')', 'LEFT OUTER JOIN', 'user_specific_rate.taxruledet_taxrule_id = tc.taxruledet_taxrule_id and user_specific_rate.taxruledet_taxstr_id = tc.taxruledet_taxstr_id', 'user_specific_rate');
            $combinedTaxSrch->addMultipleFields(array('taxstr_id', 'taxstr_is_combined', 'taxruledet_rate', 'tc.taxruledet_taxrule_id', 'IFNULL(taxstr_name, taxstr_identifier) as taxstr_name', 'user_specific_rate.user_rate'));
            $combinedTaxSrch->getQuery();
            $combinedData = TaxRule::groupDataByKey(FatApp::getDb()->fetchAll($combinedTaxSrch->getResultSet()), 'taxruledet_taxrule_id');
        }

        $this->set("arrListing", $records);
        $this->set("combinedData", $combinedData);
        $this->set('pageCount', $srch->pages());
        $this->set('recordCount', $srch->recordCount());
        $this->set('page', $page);
        $this->set('pageSize', $pagesize);
        $this->set('postedData', FatApp::getPostedData());
        $this->_template->render(false, false);
    }

    private function getTaxRulesSearchForm($taxCatId)
    {
        $frm = new Form('frmSearchTaxRules');
        $frm->addHiddenField('', 'taxCatId', $taxCatId);
        return $frm;
    }

    public function editTaxRuleForm($taxRuleId)
    {
        $this->userPrivilege->canViewTaxCategory(UserAuthentication::getLoggedUserId());
        $taxRuleId = FatUtility::int($taxRuleId);

        $srch = TaxRule::getSearchObject();
        $srch->joinTable(TaxRule::DB_RATES_TBL, 'INNER JOIN', TaxRule::tblFld('id') . '=' . TaxRule::DB_RATES_TBL_PREFIX . TaxRule::tblFld('id'));
        $srch->addCondition('taxrule_id', '=', $taxRuleId);
        $cnd = $srch->addCondition('trr_user_id', '=', UserAuthentication::getLoggedUserId());
        $cnd->attachCondition('trr_user_id', '=', 0);
        $srch->addOrder('trr_user_id', 'DESC');
        $srch->addMultipleFields(array('taxrule_id', 'trr_rate'));
        $ruleData = FatApp::getDb()->fetch($srch->getResultSet());
        if (empty($ruleData)) {
            FatUtility::dieWithError(Labels::getLabel('MSG_INVALID_REQUEST', $this->siteLangId));
        }

        $frm = $this->getTaxRuleForm();
        if (!empty($ruleData)) {
            $frm->fill($ruleData);
        }

        $srch = TaxRule::getCombinedTaxSearchObject();
        $srch->doNotCalculateRecords();
        $srch->addCondition('taxruledet_taxrule_id', '=', $taxRuleId);
        $srch->addCondition('taxruledet_user_id', '=', UserAuthentication::getLoggedUserId());

        /* checking whether to fetch data from admin or login in user */
        $combinedTaxUserId = FatApp::getDb()->fetch($srch->getResultSet()) ? UserAuthentication::getLoggedUserId() : 0;

        $srch = TaxRule::getCombinedTaxSearchObject();
        $srch->joinTable(TaxStructure::DB_TBL, 'INNER JOIN', 'taxruledet_taxstr_id = taxstr_id');
        $srch->joinTable(TaxStructure::DB_TBL_LANG, 'LEFT JOIN', 'taxruledet_taxstr_id = taxstrlang_taxstr_id and taxstrlang_lang_id = ' . $this->siteLangId);
        $srch->addCondition('taxruledet_taxrule_id', '=', $taxRuleId);
        $srch->addCondition('taxruledet_user_id', '=', $combinedTaxUserId);
        $srch->addMultipleFields(array('taxruledet_rate', 'taxruledet_taxstr_id', 'IFNULL(taxstr_name, taxstr_identifier) as taxstr_name'));
        $srch->doNotCalculateRecords();
        $combinedTaxData = FatApp::getDb()->fetchAll($srch->getResultSet());

        $this->set('frm', $frm);
        $this->set('combinedTaxData', $combinedTaxData);
        $this->set('taxRuleId', $taxRuleId);
        $this->_template->render(false, false);
    }

    private function getTaxRuleForm($taxRuleId = 0)
    {
        $frm = new Form('frmTaxRule');
        /* [ TAX CATEGORY RULE FORM */
        $frm->addHiddenField('', 'taxrule_id', 0);
        $fld = $frm->addFloatField(Labels::getLabel('FRM_TAX_RATE(%)', $this->siteLangId), 'trr_rate', '');
        $fld->requirements()->setPositive();
        $fld->requirements()->setRange(0, 100);
        $frm->addHiddenField('', 'combinedTaxDetails');
        return $frm;
    }

    public function updateTaxRule()
    {
        $userId = UserAuthentication::getLoggedUserId();
        $this->userPrivilege->canEditTaxCategory($userId);
        $frm = $this->getTaxRuleForm();
        $post = $frm->getFormDataFromArray(FatApp::getPostedData());
        if (false === $post) {
            FatUtility::dieJsonError(current($frm->getValidationErrors()));
        }

        $combinedTaxDetails = (isset($post['combinedTaxDetails'])) ? $post['combinedTaxDetails'] : [];
        if (!empty($combinedTaxDetails)) {
            $totalCombinedTax = 0;
            array_walk($combinedTaxDetails, function (&$value) use (&$totalCombinedTax) {
                $value = FatUtility::int($value);
                $totalCombinedTax += $value['taxruledet_rate'];
            });
            if ($totalCombinedTax != $post['trr_rate']) {
                FatUtility::dieJsonError(Labels::getLabel('ERR_INVALID_COMBINED_TAX_COMBINATION', $this->siteLangId));
            }
        }
        $taxRuleId = $post['taxrule_id'];
        $taxRuleObj = new TaxRule($taxRuleId);
        $ruleData = $taxRuleObj->getRule($this->siteLangId);
        if (empty($ruleData)) {
            FatUtility::dieWithError(Labels::getLabel('MSG_INVALID_REQUEST', $this->siteLangId));
        }

        if (!$taxRuleObj->addUpdateRate($post['trr_rate'], $userId)) {
            FatUtility::dieJsonError($taxRuleObj->getError());
        }

        if (!$this->addUpdateCombinedData($combinedTaxDetails, $taxRuleId, $userId)) {
            FatUtility::dieJsonError($taxRuleObj->getError());
        }
        $this->set('msg', Labels::getLabel('MSG_RECORD_UPDATED_SUCCESSFULLY', $this->siteLangId));
        $this->_template->render(false, false, 'json-success.php');
    }

    private function addUpdateCombinedData($combinedTaxes, $ruleId, $userId)
    {
        if (!empty($combinedTaxes)) {
            $taxRuleObj = new TaxRule($ruleId);
            if (!$taxRuleObj->deleteCombinedTaxes($userId)) {
                return false;
            }
            foreach ($combinedTaxes as $combinedTax) {
                if (!$taxRuleObj->addUpdateCombinedTax($combinedTax, $userId)) {
                    echo $taxRuleObj->getError();
                    return false;
                }
            }
        }
        return true;
    }

    public function shop($tab = '', $subTab = '')
    {
        $this->userPrivilege->canViewShop(UserAuthentication::getLoggedUserId());
        if (!UserPrivilege::isUserHasValidSubsription($this->userParentId)) {
            Message::addInfo(Labels::getLabel("MSG_Please_buy_subscription", $this->siteLangId));
            FatApp::redirectUser(UrlHelper::generateUrl('Seller', 'Packages'));
        }
        $this->_template->addJs('js/jscolor.js');
        $userId = $this->userParentId;
        $shopDetails = Shop::getAttributesByUserId($userId, array('shop_id'), false);

        $shop_id = 0;
        if (!false == $shopDetails) {
            $shop_id = $shopDetails['shop_id'];
        }

        $this->_template->addJs('js/cropper.js');
        $this->_template->addJs('js/cropper-main.js');

        $this->set('tab', $tab);
        $this->set('subTab', $subTab);
        $this->set('shop_id', $shop_id);
        $this->set('language', Language::getAllNames());
        $this->set('siteLangId', $this->siteLangId);
        $this->_template->addJs(array('js/select2.js'));
        $this->_template->addCss(array('css/select2.min.css'));
        $this->_template->render(true, true);
    }

    public function shopForm($callbackKeyName = '')
    {
        $userId = $this->userParentId;
        $shopDetails = Shop::getAttributesByUserId($userId, null, false);
        if (!false == $shopDetails && $shopDetails['shop_active'] != applicationConstants::ACTIVE) {
            Message::addErrorMessage(Labels::getLabel('ERR_YOUR_SHOP_DEACTIVATED_CONTACT_ADMIN', $this->siteLangId));
            FatUtility::dieWithError(Message::getHtml());
        }

        $shop_id = 0;
        $stateId = 0;
        $countryId = (isset($shopDetails['shop_country_id'])) ? $shopDetails['shop_country_id'] : FatApp::getConfig('CONF_COUNTRY', FatUtility::VAR_INT, 223);
        if (!false == $shopDetails) {
            $shop_id = $shopDetails['shop_id'];
            $stateId = isset($shopDetails['shop_state_id']) ? $shopDetails['shop_state_id'] : 0;
            $shopDetails['shop_country_code'] = Countries::getCountryById($countryId, $this->siteLangId, 'country_code');
        }

        $shopLayoutTemplateId = isset($shopDetails['shop_ltemplate_id']) ? $shopDetails['shop_ltemplate_id'] : 0;
        if ($shopLayoutTemplateId == 0) {
            $shopLayoutTemplateId = 10001;
        }
        $this->set('shopLayoutTemplateId', $shopLayoutTemplateId);
        $shopFrm = $this->getShopInfoForm($userId, $shop_id);

        $stateObj = new States();
        $statesArr = $stateObj->getStatesByCountryId($countryId, $this->siteLangId, true, 'state_code');

        $shopFrm->getField('shop_state')->options = $statesArr;
        /* url data[ */
        $urlSrch = UrlRewrite::getSearchObject();
        $urlSrch->doNotCalculateRecords();
        $urlSrch->doNotLimitRecords();
        $urlSrch->addFld('urlrewrite_custom');
        $urlSrch->addCondition('urlrewrite_original', '=', Shop::SHOP_VIEW_ORGINAL_URL . $shop_id);
        $rs = $urlSrch->getResultSet();
        $urlRow = FatApp::getDb()->fetch($rs);
        if ($urlRow) {
            $shopDetails['urlrewrite_custom'] = $urlRow['urlrewrite_custom'];
        }
        /* ] */
        if ($shopDetails) {
            $stateCode = States::getAttributesById($stateId, 'state_code');
            $shopDetails['shop_state'] = $stateCode;
        }

        $shopFrm->fill($shopDetails);
        $shopFrm->addSecurityToken();

        $plugin = new Plugin();
        $keyName = $plugin->getDefaultPluginKeyName(Plugin::TYPE_SPLIT_PAYMENT_METHOD);

        if (!empty($callbackKeyName)) {
            $this->set('action', $callbackKeyName);
        }
        $this->set('shopFrm', $shopFrm);
        $this->set('stateId', $stateId);
        $this->set('shop_id', $shop_id);
        $this->set('siteLangId', $this->siteLangId);
        $this->set('language', Language::getAllNames());
        $this->_template->addJs('js/jscolor.js');
        $this->_template->render(false, false);
    }

    public function shopMediaForm()
    {
        $userId = $this->userParentId;
        $shopDetails = Shop::getAttributesByUserId($userId, null, false);

        if (false == $shopDetails) {
            Message::addErrorMessage(Labels::getLabel('ERR_INVALID_REQUEST', $this->siteLangId));
            FatUtility::dieWithError(Message::getHtml());
        }

        if (!false == $shopDetails && $shopDetails['shop_active'] != applicationConstants::ACTIVE) {
            Message::addErrorMessage(Labels::getLabel('ERR_YOUR_SHOP_DEACTIVATED_CONTACT_ADMIN', $this->siteLangId));
            FatUtility::dieWithError(Message::getHtml());
        }

        $shopLayoutTemplateId = $shopDetails['shop_ltemplate_id'];
        if ($shopLayoutTemplateId == 0) {
            $shopLayoutTemplateId = 10001;
        }

        $this->set('shopLayoutTemplateId', $shopLayoutTemplateId);

        $shop_id = 0;

        if (!false == $shopDetails) {
            $shop_id = $shopDetails['shop_id'];
        }

        $shopLogoFrm = $this->getShopLogoForm($shop_id, $this->siteLangId);
        $shopBannerFrm = $this->getShopBannerForm($shop_id, $this->siteLangId);


        $getShopDimensions = ImageDimension::getScreenSizes(ImageDimension::TYPE_SHOP_BANNER);
        $getShopLogoSquare = ImageDimension::getData(ImageDimension::TYPE_SHOP_LOGO, ImageDimension::VIEW_DEFAULT, AttachedFile::RATIO_TYPE_SQUARE);
        $getShopLogoRactangle = ImageDimension::getData(ImageDimension::TYPE_SHOP_LOGO, ImageDimension::VIEW_DEFAULT, AttachedFile::RATIO_TYPE_RECTANGULAR);
        $this->set('getShopDimensions', $getShopDimensions);
        $this->set('getShopLogoSquare', $getShopLogoSquare);
        $this->set('getShopLogoRactangle', $getShopLogoRactangle);

        $this->set('shopDetails', $shopDetails);
        $this->set('shopLogoFrm', $shopLogoFrm);
        $this->set('shopBannerFrm', $shopBannerFrm);
        $this->set('language', Language::getAllNames());
        $this->set('shop_id', $shop_id);
        $this->_template->render(false, false);
    }

    public function shopImages($imageType, $lang_id = 0, $slide_screen = 0)
    {
        $userId = $this->userParentId;
        $shopDetails = Shop::getAttributesByUserId($userId, null, false);

        if (!false == $shopDetails && $shopDetails['shop_active'] != applicationConstants::ACTIVE) {
            Message::addErrorMessage(Labels::getLabel('ERR_YOUR_SHOP_DEACTIVATED_CONTACT_ADMIN', $this->siteLangId));
            FatUtility::dieWithError(Message::getHtml());
        }

        $languages = Language::getAllNames();
        if (count($languages) <= 1) {
            $lang_id = array_key_first($languages);
        }

        $shop_id = 0;
        $bannerAttachments = array();
        $logoAttachments = array();
        $backgroundAttachments = array();

        if (!false == $shopDetails) {
            $shop_id = $shopDetails['shop_id'];
            if ($imageType == 'logo') {
                $logoAttachments = AttachedFile::getAttachment(AttachedFile::FILETYPE_SHOP_LOGO, $shop_id, 0, $lang_id, (count($languages) <= 1) ? true : false);
                $this->set('image', $logoAttachments);
                $this->set('imageFunction', 'shopLogo');
            } elseif ($imageType == 'banner') {
                $bannerAttachments = AttachedFile::getAttachment(AttachedFile::FILETYPE_SHOP_BANNER, $shop_id, 0, $lang_id, (count($languages) <= 1) ? true : false, $slide_screen);
                $this->set('image', $bannerAttachments);
                $this->set('imageFunction', 'shopBanner');
            }
        }
        $this->set('imageType', $imageType);
        $this->set('shopDetails', $shopDetails);
        $this->set('shop_id', $shop_id);
        $this->set('languages', applicationConstants::getAllLanguages());
        $this->set('canEdit', $this->userPrivilege->canEditShop(UserAuthentication::getLoggedUserId(), true));
        $this->_template->render(false, false);
    }

    public function shopLangForm($shopId, $langId, $autoFillLangData = 0)
    {
        $shop_id = FatUtility::int($shopId);
        $lang_id = FatUtility::int($langId);

        if ($shop_id == 0 || $lang_id == 0) {
            Message::addErrorMessage(Labels::getLabel('ERR_INVALID_REQUEST_ID', $this->siteLangId));
            FatUtility::dieWithError(Message::getHtml());
        }

        $userId = $this->userParentId;

        $shopDetails = Shop::getAttributesByUserId($userId, null, false);

        if (!false == $shopDetails && $shopDetails['shop_active'] != applicationConstants::ACTIVE) {
            Message::addErrorMessage(Labels::getLabel('ERR_YOUR_SHOP_DEACTIVATED_CONTACT_ADMIN', $this->siteLangId));
            FatUtility::dieWithError(Message::getHtml());
        }
        $shopLayoutTemplateId = $shopDetails['shop_ltemplate_id'];
        if ($shopLayoutTemplateId == 0) {
            $shopLayoutTemplateId = 10001;
        }
        $this->set('shopLayoutTemplateId', $shopLayoutTemplateId);

        if (!$this->isShopActive($userId, $shop_id)) {
            Message::addErrorMessage(Labels::getLabel('ERR_YOUR_SHOP_DEACTIVATED_CONTACT_ADMIN', $this->siteLangId));
            FatUtility::dieWithError(Message::getHtml());
        }

        if (0 < $autoFillLangData) {
            $updateLangDataobj = new TranslateLangData(Shop::DB_TBL_LANG);
            $translatedData = $updateLangDataobj->getTranslatedData($shop_id, $lang_id, CommonHelper::getDefaultFormLangId());
            if (false === $translatedData) {
                Message::addErrorMessage($updateLangDataobj->getError());
                FatUtility::dieWithError(Message::getHtml());
            }
            $langData = current($translatedData);
        } else {
            $langData = Shop::getAttributesByLangId($lang_id, $shop_id);
        }

        $shopLangFrm = $this->getShopLangInfoForm($shop_id, $lang_id);
        $shopLangFrm->fill($langData);

        $this->set('shopLangFrm', $shopLangFrm);
        $this->set('siteLangId', $this->siteLangId);
        $this->set('formLangId', $lang_id);
        $this->set('shop_id', $shop_id);
        $this->set('formLayout', Language::getLayoutDirection($lang_id));
        $this->set('language', Language::getAllNames());
        $this->_template->render(false, false);
    }

    public function shopTemplate()
    {
        $userId = $this->userParentId;
        $shopDetails = Shop::getAttributesByUserId($userId, null, false);

        if (false == $shopDetails) {
            Message::addErrorMessage(Labels::getLabel('ERR_INVALID_ACCESS', $this->siteLangId));
            FatUtility::dieWithError(Message::getHtml());
        }

        if (!false == $shopDetails && $shopDetails['shop_active'] != applicationConstants::ACTIVE) {
            Message::addErrorMessage(Labels::getLabel('ERR_YOUR_SHOP_DEACTIVATED_CONTACT_ADMIN', $this->siteLangId));
            FatUtility::dieWithError(Message::getHtml());
        }

        $shop_id = $shopDetails['shop_id'];
        $shopLayoutTemplateId = $shopDetails['shop_ltemplate_id'];

        $shopTemplateLayouts = LayoutTemplate::getMultipleLayouts(LayoutTemplate::LAYOUTTYPE_SHOP);

        if ($shopLayoutTemplateId == 0) {
            $shopLayoutTemplateId = 10001;
        }

        $this->set('shop_id', $shop_id);
        $this->set('shopLayoutTemplateId', $shopLayoutTemplateId);
        $this->set('shopTemplateLayouts', $shopTemplateLayouts);
        $this->set('language', Language::getAllNames());
        $this->_template->render(false, false);
    }

    public function setTemplate($ltemplate_id)
    {
        $userId = $this->userParentId;
        $ltemplate_id = FatUtility::int($ltemplate_id);
        if (1 > $ltemplate_id) {
            FatUtility::dieJsonError(Labels::getLabel('ERR_INVALID_ACCESS', $this->siteLangId));
        }

        $data = LayoutTemplate::getAttributesById($ltemplate_id);
        if (false == $data) {
            FatUtility::dieJsonError(Labels::getLabel('ERR_INVALID_ACCESS', $this->siteLangId));
        }

        $shopDetails = Shop::getAttributesByUserId($userId, null, false);
        if (false == $shopDetails) {
            Message::addErrorMessage(Labels::getLabel('ERR_INVALID_ACCESS', $this->siteLangId));
            FatUtility::dieWithError(Message::getHtml());
        }

        if (!false == $shopDetails && $shopDetails['shop_active'] != applicationConstants::ACTIVE) {
            Message::addErrorMessage(Labels::getLabel('ERR_YOUR_SHOP_DEACTIVATED_CONTACT_ADMIN', $this->siteLangId));
            FatUtility::dieWithError(Message::getHtml());
        }

        $shop_id = FatUtility::int($shopDetails['shop_id']);

        $shopObj = new Shop($shop_id);
        $data = array('shop_ltemplate_id' => $ltemplate_id);
        $shopObj->assignValues($data);

        if (!$shopObj->save()) {
            FatUtility::dieJsonError($shopObj->getError());
        }

        $this->set('msg', Labels::getLabel('MSG_SET_UP_SUCCESSFULLY', $this->siteLangId));
        $this->_template->render(false, false, 'json-success.php');
    }

    public function setupShop()
    {
        $this->userPrivilege->canEditShop(UserAuthentication::getLoggedUserId());
        $userId = $this->userParentId;
        $post = FatApp::getPostedData();
        $shop_id = FatUtility::int($post['shop_id']);
        unset($post['shop_id']);

        if ($shop_id > 0) {
            if (!$this->isShopActive($userId, $shop_id)) {
                FatUtility::dieJsonError(Labels::getLabel('ERR_YOUR_SHOP_DEACTIVATED_CONTACT_ADMIN', $this->siteLangId));
            }
        }

        $marketPlaceChannels = (array) Plugin::getNamesWithCode(Plugin::TYPE_MARKETPLACE_CHANNELS, $this->siteLangId);

        $errorMsg = '';
        $manualShipping = FatApp::getPostedData('shop_use_manual_shipping_rates', FatUtility::VAR_INT, 0);
        $status = (int) User::getUserMeta($userId, 'easyEcomSyncingStatus');
        if (1 > $manualShipping && in_array('EasyEcom', $marketPlaceChannels) && 0 < $status) {
            $post['shop_use_manual_shipping_rates'] = 1;
            $errorMsg = Labels::getLabel('MSG_PLEASE_TURN_OFF_EASYECOM_AUTO_SYNC_FIRST.', $this->siteLangId);
        }

        $stateCode = $post['shop_state'];
        $frm = $this->getShopInfoForm($userId);
        $post = $frm->getFormDataFromArray($post, [], true);
        if (false == $post) {
            FatUtility::dieJsonError(current($frm->getValidationErrors()));
        }
        $frm->expireSecurityToken(FatApp::getPostedData());
        $post['shop_country_id'] = Countries::getCountryByCode($post['shop_country_code'], 'country_id');

        $post['shop_user_id'] = $userId;
        $stateData = States::getStateByCountryAndCode($post['shop_country_id'], $stateCode);
        $post['shop_state_id'] = $stateData['state_id'];

        if ($shop_id > 0) {
            $post['shop_updated_on'] = date('Y-m-d H:i:s');
        } else {
            $recordInsert = true;
            $post['shop_created_on'] = date('Y-m-d H:i:s');
        }

        $newShop = (1 > $shop_id);

        $post['shop_phone_dcode'] = FatApp::getPostedData('shop_phone_dcode', FatUtility::VAR_STRING, '');
        $shopObj = new Shop($shop_id);
        $shopObj->assignValues($post);

        if (!$shopObj->save()) {
            FatUtility::dieJsonError($shopObj->getError());
        }

        $shop_id = $shopObj->getMainTableRecordId();

        if ($newShop) {
            Shop::updateValidSubscription($userId);
        }

        $user = new User($userId);
        $user->updateShopValidUser();

        $post['ss_shop_id'] = $shop_id;
        $shopSpecificsObj = new ShopSpecifics($shop_id);
        $shopSpecificsObj->assignValues($post);
        $data = $shopSpecificsObj->getFlds();
        if (!$shopSpecificsObj->addNew(array(), $data)) {
            FatUtility::dieJsonError($shopSpecificsObj->getError());
        }

        /* url data[ */
        $shopOriginalUrl = Shop::SHOP_TOP_PRODUCTS_ORGINAL_URL . $shop_id;
        if ($post['urlrewrite_custom'] == '') {
            FatApp::getDb()->deleteRecords(UrlRewrite::DB_TBL, array('smt' => 'urlrewrite_original = ?', 'vals' => array($shopOriginalUrl)));
        } else {
            $shopObj->rewriteUrlShop($post['urlrewrite_custom']);
            $shopObj->rewriteUrlReviews($post['urlrewrite_custom']);
            $shopObj->rewriteUrlTopProducts($post['urlrewrite_custom']);
            $shopObj->rewriteUrlContact($post['urlrewrite_custom']);
            $shopObj->rewriteUrlpolicy($post['urlrewrite_custom']);
        }
        /* ] */


        $newTabLangId = 0;
        if ($shop_id > 0) {
            $languages = Language::getAllNames();
            foreach ($languages as $langId => $langName) {
                if (!$row = Shop::getAttributesByLangId($langId, $shop_id)) {
                    $newTabLangId = $langId;
                    break;
                }
            }
        } else {
            $shop_id = $shopObj->getMainTableRecordId();
            $newTabLangId = $this->siteLangId;
        }

        ShippingProfile::getDefaultProfileId($this->userParentId);

        $msg = !empty($errorMsg) ? $errorMsg : Labels::getLabel('MSG_SET_UP_SUCCESSFULLY', $this->siteLangId);

        $shipping = new Shipping($this->siteLangId);
        $shippingService = $shipping->getShippingApiObj();
        if (false !== $shippingService) {
            if (method_exists($shippingService, 'setShopSellerId') && method_exists($shippingService, 'updateWarehouse')) {
                $shippingService->setShopSellerId($userId);
                $shippingService->updateWarehouse();
            }
        }

        $this->set('shopId', $shop_id);
        $this->set('langId', $newTabLangId);
        $this->set('msg', $msg);
        $this->_template->render(false, false, 'json-success.php');
    }

    public function setupShopLang()
    {
        $this->userPrivilege->canEditShop(UserAuthentication::getLoggedUserId());
        $userId = $this->userParentId;

        $frm = $this->getShopLangInfoForm();
        $post = $frm->getFormDataFromArray(FatApp::getPostedData());

        if (false == $post) {
            FatUtility::dieJsonError(current($frm->getValidationErrors()));
        }

        if (!$shopDetails = $this->isShopActive($userId, 0, true)) {
            FatUtility::dieJsonError(Labels::getLabel('ERR_YOUR_SHOP_DEACTIVATED_CONTACT_ADMIN', $this->siteLangId));
        }
        $shop_id = FatUtility::int($shopDetails['shop_id']);

        $languages = Language::getAllNames();
        if (count($languages) > 1) {
            $lang_id = FatApp::getPostedData('lang_id', FatUtility::VAR_INT, 0);
        } else {
            $lang_id = array_key_first($languages);
        }

        if ($lang_id <= 0) {
            FatUtility::dieJsonError(Labels::getLabel('ERR_INVALID_REQUEST_ID', $this->siteLangId));
        }

        $shopObj = new Shop($shop_id);
        $data = array(
            'shoplang_shop_id' => $shop_id,
            'shoplang_lang_id' => $lang_id,
            'shop_name' => $post['shop_name'],
            'shop_address_line_1' => $post['shop_address_line_1'],
            'shop_address_line_2' => $post['shop_address_line_2'],
            'shop_city' => $post['shop_city'],
            'shop_contact_person' => $post['shop_contact_person'],
            'shop_description' => $post['shop_description'],
            'shop_payment_policy' => $post['shop_payment_policy'],
            'shop_delivery_policy' => $post['shop_delivery_policy'],
            'shop_refund_policy' => $post['shop_refund_policy'],
            'shop_additional_info' => $post['shop_additional_info'],
            'shop_seller_info' => $post['shop_seller_info'],
        );

        if (!$shopObj->updateLangData($lang_id, $data)) {
            FatUtility::dieJsonError($shopObj->getError());
        }

        $autoUpdateOtherLangsData = FatApp::getPostedData('auto_update_other_langs_data', FatUtility::VAR_INT, 0);
        if (0 < $autoUpdateOtherLangsData) {
            $updateLangDataobj = new TranslateLangData(Shop::DB_TBL_LANG);
            if (false === $updateLangDataobj->updateTranslatedData($shop_id)) {
                FatUtility::dieJsonError($updateLangDataobj->getError());
            }
        }

        $newTabLangId = 0;
        $languages = Language::getAllNames();
        foreach ($languages as $langId => $langName) {
            if (!$row = Shop::getAttributesByLangId($langId, $shop_id)) {
                $newTabLangId = $langId;
                break;
            }
        }

        $shipping = new Shipping($this->siteLangId);
        $shippingService = $shipping->getShippingApiObj();
        if (false !== $shippingService) {
            if (method_exists($shippingService, 'setShopSellerId') && method_exists($shippingService, 'updateWarehouse')) {
                $shippingService->setShopSellerId($userId);
                $shippingService->updateWarehouse();
            }
        }

        $this->set('shopId', $shop_id);
        $this->set('langId', $newTabLangId);
        $this->set('msg', Labels::getLabel('MSG_SET_UP_SUCCESSFULLY', $this->siteLangId));
        $this->_template->render(false, false, 'json-success.php');
    }

    public function isShopRewriteUrlUnique()
    {
        $shop_id = FatApp::getPostedData('recordId', FatUtility::VAR_INT, 0);
        $urlKeyword = FatApp::getPostedData('url_keyword');
        $shopObj = new Shop($shop_id);
        $seoUrl = $shopObj->sanitizeSeoUrl($urlKeyword);
        if (1 > $shop_id) {
            $isUnique = UrlRewrite::isCustomUrlUnique($seoUrl);
            if ($isUnique) {
                FatUtility::dieJsonSuccess(UrlHelper::generateFullUrl('', '', array(), CONF_WEBROOT_FRONT_URL) . $seoUrl);
            }
            FatUtility::dieJsonError(Labels::getLabel('ERR_NOT_AVAILABLE._PLEASE_TRY_USING_ANOTHER_KEYWORD', $this->siteLangId));
        }

        $originalUrl = $shopObj->getRewriteShopOriginalUrl();
        $customUrlData = UrlRewrite::getDataByCustomUrl($seoUrl, $originalUrl);
        if (empty($customUrlData)) {
            FatUtility::dieJsonSuccess(UrlHelper::generateFullUrl('', '', array(), CONF_WEBROOT_FRONT_URL) . $seoUrl);
        }
        FatUtility::dieJsonError(Labels::getLabel('ERR_NOT_AVAILABLE._PLEASE_TRY_USING_ANOTHER_KEYWORD', $this->siteLangId));
    }

    public function uploadShopImages()
    {
        if (!$this->userPrivilege->canEditShop(UserAuthentication::getLoggedUserId(), true)) {
            FatUtility::dieJsonError(Labels::getLabel('ERR_UNAUTHORIZED_ACCESS!', $this->siteLangId));
        }

        $userId = $this->userParentId;

        if (!$shopDetails = $this->isShopActive($userId, 0, true)) {
            FatUtility::dieJsonError(Labels::getLabel('ERR_Your_shop_deactivated_contact_admin', $this->siteLangId));
        }

        $shop_id = $shopDetails['shop_id'];
        if (1 > $shop_id) {
            FatUtility::dieJsonError(Labels::getLabel('ERR_INVALID_REQUEST_ID', $this->siteLangId));
        }

        $post = FatApp::getPostedData();
        if (empty($post)) {
            FatUtility::dieJsonError(Labels::getLabel('ERR_INVALID_REQUEST_Or_File_not_supported', $this->siteLangId));
        }
        $file_type = FatApp::getPostedData('file_type', FatUtility::VAR_INT, 0);
        $languages = Language::getAllNames();
        if (count($languages) > 1) {
            $lang_id = FatApp::getPostedData('lang_id', FatUtility::VAR_INT, 0);
        } else {
            $lang_id = array_key_first($languages);
        }
        $slide_screen = FatApp::getPostedData('slide_screen', FatUtility::VAR_INT, 0);
        $aspectRatio = FatApp::getPostedData('ratio_type', FatUtility::VAR_INT, 0);
        if (!$file_type) {
            FatUtility::dieJsonError(Labels::getLabel('ERR_INVALID_REQUEST', $this->siteLangId));
        }

        $allowedFileTypeArr = array(AttachedFile::FILETYPE_SHOP_LOGO, AttachedFile::FILETYPE_SHOP_BANNER, AttachedFile::FILETYPE_SHOP_BACKGROUND_IMAGE);

        if (!in_array($file_type, $allowedFileTypeArr)) {
            FatUtility::dieJsonError(Labels::getLabel('ERR_INVALID_REQUEST', $this->siteLangId));
        }

        if (!is_uploaded_file($_FILES['cropped_image']['tmp_name'])) {
            FatUtility::dieJsonError(Labels::getLabel('ERR_PLEASE_SELECT_A_FILE', $this->siteLangId));
        }
        $unique_record = true;
        /* if ($file_type != AttachedFile::FILETYPE_SHOP_BANNER) {
          $unique_record = true;
          } */

        $fileHandlerObj = new AttachedFile();
        if (!$res = $fileHandlerObj->saveAttachment($_FILES['cropped_image']['tmp_name'], $file_type, $shop_id, 0, $_FILES['cropped_image']['name'], -1, $unique_record, $lang_id, $slide_screen, $aspectRatio)) {
            FatUtility::dieJsonError($fileHandlerObj->getError());
        }

        $this->set('file', $_FILES['cropped_image']['name']);
        $this->set('shopId', $shop_id);
        /* Message::addMessage(  Labels::getLabel('MSG_File_uploaded_successfully' ,$this->siteLangId) );
          FatUtility::dieJsonSuccess(Message::getHtml()); */
        $this->set('msg', Labels::getLabel('MSG_File_uploaded_successfully', $this->siteLangId));
        $this->_template->render(false, false, 'json-success.php');
        /* $this->set('msg', Message::getHtml() );
          $this->_template->render(false, false, 'json-success.php'); */
    }

    public function removeShopImage($banner_id, $langId, $imageType, $slide_screen = 0)
    {
        $this->userPrivilege->canEditShop(UserAuthentication::getLoggedUserId());
        $userId = $this->userParentId;
        $langId = FatUtility::int($langId);

        if (!$shopDetails = $this->isShopActive($userId, 0, true)) {
            FatUtility::dieJsonError(Labels::getLabel('ERR_YOUR_SHOP_DEACTIVATED_CONTACT_ADMIN', $this->siteLangId));
        }

        $shop_id = $shopDetails['shop_id'];
        if (!$shop_id) {
            FatUtility::dieJsonError(Labels::getLabel('ERR_INVALID_REQUEST', $this->siteLangId));
        }

        if ($imageType == 'logo') {
            $fileType = AttachedFile::FILETYPE_SHOP_LOGO;
        } elseif ($imageType == 'banner') {
            $fileType = AttachedFile::FILETYPE_SHOP_BANNER;
        } else {
            $fileType = AttachedFile::FILETYPE_SHOP_BACKGROUND_IMAGE;
        }


        $fileHandlerObj = new AttachedFile();
        if (!$fileHandlerObj->deleteFile($fileType, $shop_id, $banner_id, 0, $langId, $slide_screen)) {
            FatUtility::dieJsonError($fileHandlerObj->getError());
        }

        $this->set('msg', Labels::getLabel('MSG_File_deleted_successfully', $this->siteLangId));
        $this->_template->render(false, false, 'json-success.php');
    }

    public function orderCancellationRequests()
    {
        $this->userPrivilege->canViewCancellationRequests(UserAuthentication::getLoggedUserId());
        $frmSearch = $this->getOrderCancellationRequestsSearchForm($this->siteLangId);
        $this->set('frmSearch', $frmSearch);
        $this->_template->render(true, true);
    }

    public function orderCancellationRequestSearch()
    {
        $this->userPrivilege->canViewCancellationRequests(UserAuthentication::getLoggedUserId());
        $frm = $this->getOrderCancellationRequestsSearchForm($this->siteLangId);
        $post = $frm->getFormDataFromArray(FatApp::getPostedData());
        $page = (empty($post['page']) || $post['page'] <= 0) ? 1 : FatUtility::int($post['page']);
        $pagesize = FatApp::getConfig('conf_page_size', FatUtility::VAR_INT, 10);

        $srch = $this->cancelRequestListingObj();
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

        $ocrequest_status = FatApp::getPostedData('ocrequest_status', null, -1);
        if ($ocrequest_status > -1) {
            $ocrequest_status = FatUtility::int($ocrequest_status);
            $srch->addCondition('ocrequest_status', '=', $ocrequest_status);
        }

        $this->setRecordCount(clone $srch, $pagesize, $page, $post);
        $srch->doNotCalculateRecords();
        $srch->addMultipleFields(array('ocrequest_id', 'ocrequest_date', 'ocrequest_status', 'order_id', 'order_number', 'op_invoice_number', 'op_id', 'IFNULL(ocreason_title, ocreason_identifier) as ocreason_title', 'ocrequest_message', 'op_selprod_title', 'op_product_name', 'op_selprod_id', 'op_is_batch'));
        $srch->addOrder('ocrequest_date', 'DESC');
        $srch->setPageNumber($page);
        $srch->setPageSize($pagesize);
        $this->set('requests', FatApp::getDb()->fetchAll($srch->getResultSet()));
        $this->set('postedData', $post);
        $this->set('OrderCancelRequestStatusArr', OrderCancelRequest::getRequestStatusArr($this->siteLangId));
        $this->set('cancelReqStatusClassArr', OrderCancelRequest::getStatusClassArr());
        $this->set('isSeller', true);
        $this->_template->render(false, false, 'buyer/order-cancellation-request-search.php');
    }

    private function cancelRequestListingObj()
    {
        $srch = new OrderCancelRequestSearch($this->siteLangId);
        $srch->joinOrderProducts();
        $srch->joinOrderCancelReasons();
        $srch->joinOrders();
        $srch->addCondition('op_selprod_user_id', '=', $this->userParentId);
        return $srch;
    }

    public function orderReturnRequests()
    {
        $this->userPrivilege->canViewReturnRequests(UserAuthentication::getLoggedUserId());
        $frmSearch = $this->getOrderReturnRequestsSearchForm($this->siteLangId);
        $this->set('frmSearch', $frmSearch);
        $this->set('keywordPlaceholder', Labels::getLabel('LBL_SEARCH_IN_ORDER_INVOICE_NUMBER,_PRODUCT_NAME,_BRAND_NAME,_SKU,_MODEL', $this->siteLangId));
        $this->_template->render(true, true);
    }

    public function orderReturnRequestSearch()
    {
        $this->userPrivilege->canViewReturnRequests(UserAuthentication::getLoggedUserId());
        $frm = $this->getOrderReturnRequestsSearchForm($this->siteLangId);
        $post = $frm->getFormDataFromArray(FatApp::getPostedData());
        $page = (empty($post['page']) || $post['page'] <= 0) ? 1 : FatUtility::int($post['page']);
        $page = (empty($page) || $page <= 0) ? 1 : FatUtility::int($page);
        $pagesize = FatApp::getConfig('conf_page_size', FatUtility::VAR_INT, 10);

        $srch = $this->returnReuestsListingObj();
        $orrequest_status = FatApp::getPostedData('orrequest_status', null, '-1');
        if ($orrequest_status > -1) {
            $orrequest_status = FatUtility::int($orrequest_status);
            $srch->addCondition('orrequest_status', '=', $orrequest_status);
        }

        $orrequest_type = FatApp::getPostedData('orrequest_type', null, '-1');
        if ($orrequest_type > -1) {
            $orrequest_type = FatUtility::int($orrequest_type);
            $srch->addCondition('orrequest_type', '=', $orrequest_type);
        }

        if (!empty($post['orrequest_date_from'])) {
            $srch->addCondition('orrequest_date', '>=', $post['orrequest_date_from'] . ' 00:00:00');
        }

        if (!empty($post['orrequest_date_to'])) {
            $srch->addCondition('orrequest_date', '<=', $post['orrequest_date_to'] . ' 23:59:59');
        }

        $keyword = $post['keyword'];
        if (!empty($keyword)) {
            $cnd = $srch->addCondition('op_invoice_number', '=', $keyword);
            $cnd->attachCondition('op_order_id', '=', $keyword);
            $cnd->attachCondition('op_selprod_title', 'LIKE', '%' . $keyword . '%', 'OR');
            $cnd->attachCondition('op_product_name', 'LIKE', '%' . $keyword . '%', 'OR');
            $cnd->attachCondition('op_brand_name', 'LIKE', '%' . $keyword . '%', 'OR');
            $cnd->attachCondition('op_selprod_options', 'LIKE', '%' . $keyword . '%', 'OR');
            $cnd->attachCondition('op_selprod_sku', 'LIKE', '%' . $keyword . '%', 'OR');
            $cnd->attachCondition('op_product_model', 'LIKE', '%' . $keyword . '%', 'OR');
            $cnd->attachCondition('orrequest_reference', 'LIKE', '%' . $keyword . '%', 'OR');
        }
        $this->setRecordCount(clone $srch, $pagesize, $page, $post);
        $srch->doNotCalculateRecords();
        $srch->addMultipleFields(
            [
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
                'op_selprod_id',
                'op_is_batch',
                'op_id',
                'selprod_product_id'
            ]
        );
        $srch->addOrder('orrequest_date', 'DESC');
        $srch->setPageNumber($page);
        $srch->setPageSize($pagesize);
        $this->set('requests', FatApp::getDb()->fetchAll($srch->getResultSet()));
        $this->set('postedData', $post);
        $this->set('sellerPage', true);
        $this->set('buyerPage', false);
        $this->set('returnRequestTypeArr', OrderReturnRequest::getRequestTypeArr($this->siteLangId));
        $this->set('OrderReturnRequestStatusArr', OrderReturnRequest::getRequestStatusArr($this->siteLangId));
        $this->set('OrderRetReqStatusClassArr', OrderReturnRequest::getRequestStatusClassArr());
        $this->_template->render(false, false, 'buyer/order-return-request-search.php');
    }

    private function returnReuestsListingObj()
    {
        $srch = new OrderReturnRequestSearch($this->siteLangId);
        $srch->joinOrderProducts();
        $srch->joinSellerProducts();
        $srch->addCondition('op_selprod_user_id', '=', $this->userParentId);
        return $srch;
    }

    public function downloadAttachedFileForReturn($recordId, $recordSubid = 0)
    {
        $recordId = FatUtility::int($recordId);

        if (1 > $recordId) {
            Message::addErrorMessage(Labels::getLabel('LBL_INVALID_REQUEST', $this->siteLangId));
            FatApp::redirectUser(UrlHelper::generateUrl('Seller', 'ViewOrderReturnRequest', array($recordId)));
        }

        $file_row = AttachedFile::getAttachment(AttachedFile::FILETYPE_BUYER_RETURN_PRODUCT, $recordId, $recordSubid);

        if (false == $file_row) {
            Message::addErrorMessage(Labels::getLabel('LBL_INVALID_REQUEST', $this->siteLangId));
            FatApp::redirectUser(UrlHelper::generateUrl('Seller', 'ViewOrderReturnRequest', array($recordId)));
        }
        if (!file_exists(CONF_UPLOADS_PATH . $file_row['afile_physical_path'])) {
            Message::addErrorMessage(Labels::getLabel('LBL_FILE_NOT_FOUND', $this->siteLangId));
            FatApp::redirectUser(UrlHelper::generateUrl('Seller', 'ViewOrderReturnRequest', array($recordId)));
        }

        $fileName = isset($file_row['afile_physical_path']) ? $file_row['afile_physical_path'] : '';
        AttachedFile::downloadAttachment($fileName, $file_row['afile_name']);
    }

    public function viewOrderReturnRequest($orrequest_id)
    {
        $this->userPrivilege->canViewReturnRequests(UserAuthentication::getLoggedUserId());
        $orrequest_id = FatUtility::int($orrequest_id);
        $user_id = $this->userParentId;

        $srch = new OrderReturnRequestSearch($this->siteLangId);
        $srch->joinOrderProducts();
        $srch->joinSellerProducts();
        $srch->joinOrderProductSettings();
        $srch->joinOrders();
        $srch->joinShippingCharges();
        $srch->joinOrderBuyerUser();
        $srch->joinOrderReturnReasons();
        $srch->addOrderProductCharges();

        $srch->addCondition('orrequest_id', '=', $orrequest_id);
        $srch->addCondition('op_selprod_user_id', '=', $user_id);

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
                'buyer.user_name as buyer_name',
                'order_tax_charged',
                'op_other_charges',
                'op_refund_shipping',
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
                'opshipping_by_seller_user_id'
            )
        );

        $rs = $srch->getResultSet();
        $request = FatApp::getDb()->fetch($rs);

        if (!$request) {
            Message::addErrorMessage(Labels::getLabel('ERR_INVALID_ACCESS', $this->siteLangId));
            FatApp::redirectUser(UrlHelper::generateUrl('Seller', 'orderReturnRequests'));
        }

        $oObj = new Orders();
        $charges = $oObj->getOrderProductChargesArr($request['orrequest_op_id']);
        $request['charges'] = $charges;

        $sellerUserObj = new User($request['op_selprod_user_id']);
        $vendorReturnAddress = $sellerUserObj->getUserReturnAddress($this->siteLangId);

        $returnRequestMsgsForm = $this->getOrderReturnRequestMessageSearchForm($this->siteLangId);
        $returnRequestMsgsForm->fill(array('orrequest_id' => $request['orrequest_id']));

        $frm = $this->getOrderReturnRequestMessageForm($this->siteLangId);
        $frm->fill(array('orrmsg_orrequest_id' => $request['orrequest_id']));

        $canEscalateRequest = false;
        $canApproveReturnRequest = false;
        if ($request['orrequest_status'] == OrderReturnRequest::RETURN_REQUEST_STATUS_PENDING) {
            $canEscalateRequest = true;
        }

        if (($request['orrequest_status'] == OrderReturnRequest::RETURN_REQUEST_STATUS_PENDING) || $request['orrequest_status'] == OrderReturnRequest::RETURN_REQUEST_STATUS_ESCALATED) {
            $canApproveReturnRequest = true;
        }

        if ($attachedFile = AttachedFile::getAttachment(AttachedFile::FILETYPE_BUYER_RETURN_PRODUCT, $orrequest_id)) {
            if (!empty($attachedFile['afile_physical_path']) && file_exists(CONF_UPLOADS_PATH . $attachedFile['afile_physical_path'])) {
                $this->set('attachedFile', $attachedFile);
            }
        }
        $this->set('canEdit', $this->userPrivilege->canEditReturnRequests(UserAuthentication::getLoggedUserId(), true));
        $this->set('frmMsg', $frm);
        $this->set('canEscalateRequest', $canEscalateRequest);
        $this->set('canApproveReturnRequest', $canApproveReturnRequest);
        $this->set('returnRequestMsgsForm', $returnRequestMsgsForm);
        $this->set('request', $request);
        $this->set('vendorReturnAddress', $vendorReturnAddress);
        $this->set('returnRequestTypeArr', OrderReturnRequest::getRequestTypeArr($this->siteLangId));
        $this->set('requestRequestStatusArr', OrderReturnRequest::getRequestStatusArr($this->siteLangId));
        $this->set('logged_user_name', UserAuthentication::getLoggedUserAttribute('user_name'));
        $this->set('logged_user_id', $this->userParentId);
        $this->_template->render(true, true);
    }

    public function approveOrderReturnRequest($orrequest_id)
    {
        $orrequest_id = FatUtility::int($orrequest_id);
        $user_id = $this->userParentId;

        $srch = new OrderReturnRequestSearch($this->siteLangId);
        $srch->joinOrderProducts();
        $srch->joinOrders();
        $srch->joinOrderBuyerUser();
        $srch->joinOrderReturnReasons();

        $srch->addCondition('orrequest_id', '=', $orrequest_id);
        $srch->addCondition('op_selprod_user_id', '=', $user_id);

        $cnd = $srch->addCondition('orrequest_status', '=', OrderReturnRequest::RETURN_REQUEST_STATUS_PENDING);
        $cnd->attachCondition('orrequest_status', '=', OrderReturnRequest::RETURN_REQUEST_STATUS_ESCALATED);

        $srch->doNotCalculateRecords();
        $srch->doNotLimitRecords();
        $srch->addMultipleFields(array('orrequest_id', 'order_pmethod_id', 'op_id', 'orrequest_qty'));

        $rs = $srch->getResultSet();
        $requestRow = FatApp::getDb()->fetch($rs);

        if (!$requestRow) {
            Message::addErrorMessage(Labels::getLabel("ERR_Invalid_Access", $this->siteLangId));
            FatApp::redirectUser(UrlHelper::generateUrl('Seller', 'viewOrderReturnRequest', array($requestRow['orrequest_id'])));
        }

        $transferTo = PaymentMethods::MOVE_TO_CUSTOMER_WALLET;
        $pluginKey = Plugin::getAttributesById($requestRow['order_pmethod_id'], 'plugin_code');

        $paymentMethodObj = new PaymentMethods();
        if (true === $paymentMethodObj->canRefundToCard($pluginKey, $this->siteLangId)) {
            $transferTo = PaymentMethods::MOVE_TO_CUSTOMER_CARD;
        }

        $orrObj = new OrderReturnRequest();
        if (!$orrObj->approveRequest($requestRow['orrequest_id'], $user_id, $this->siteLangId, $transferTo)) {
            Message::addErrorMessage(Labels::getLabel($orrObj->getError(), $this->siteLangId));
            FatApp::redirectUser(UrlHelper::generateUrl('Seller', 'viewOrderReturnRequest', array($requestRow['orrequest_id'])));
        }
        CalculativeDataRecord::updateOrderReturnRequestCount();
        /* Update To Shipping Service         
        $this->langId = $this->siteLangId;
        $this->returnShipment($requestRow['op_id'], $requestRow['orrequest_qty'], UrlHelper::generateUrl('Seller', 'viewOrderReturnRequest', array($requestRow['orrequest_id'])));
        Update To Shipping Service 
        */

        /* email notification handling[ */
        $emailNotificationObj = new EmailHandler();
        if (!$emailNotificationObj->sendOrderReturnRequestStatusChangeNotification($requestRow['orrequest_id'], $this->siteLangId)) {
            Message::addErrorMessage(Labels::getLabel($emailNotificationObj->getError(), $this->siteLangId));
            FatApp::redirectUser(UrlHelper::generateUrl('Seller', 'viewOrderReturnRequest', array($requestRow['orrequest_id'])));
        }
        /* ] */

        Message::addMessage(Labels::getLabel('MSG_Request_Approved_Refund', $this->siteLangId));
        FatApp::redirectUser(UrlHelper::generateUrl('Seller', 'viewOrderReturnRequest', array($requestRow['orrequest_id'])));
    }

    public function setUpReturnOrderRequestMessage()
    {
        $orrmsg_orrequest_id = FatApp::getPostedData('orrmsg_orrequest_id', null, '0');

        $frm = $this->getOrderReturnRequestMessageForm($this->siteLangId);
        $post = $frm->getFormDataFromArray(FatApp::getPostedData());
        if (false === $post) {
            Message::addErrorMessage(current($frm->getValidationErrors()));
            FatUtility::dieWithError(Message::getHtml());
        }

        $orrmsg_orrequest_id = FatUtility::int($orrmsg_orrequest_id);
        $parentAndTheirChildIds = User::getParentAndTheirChildIds($this->userParentId, false, true);

        $srch = new OrderReturnRequestSearch($this->siteLangId);
        $srch->addCondition('orrequest_id', '=', $orrmsg_orrequest_id);
        $srch->addCondition('op_selprod_user_id', 'in', $parentAndTheirChildIds);
        $srch->joinOrderProducts();
        $srch->joinSellerProducts();
        $srch->joinOrderReturnReasons();
        $srch->doNotCalculateRecords();
        $srch->doNotLimitRecords();
        $srch->addMultipleFields(array('orrequest_id', 'orrequest_status'));
        $rs = $srch->getResultSet();
        $requestRow = FatApp::getDb()->fetch($rs);
        if (!$requestRow) {
            Message::addErrorMessage(Labels::getLabel('ERR_INVALID_ACCESS', $this->siteLangId));
            FatUtility::dieWithError(Message::getHtml());
        }

        if ($requestRow['orrequest_status'] == OrderReturnRequest::RETURN_REQUEST_STATUS_REFUNDED || $requestRow['orrequest_status'] == OrderReturnRequest::RETURN_REQUEST_STATUS_WITHDRAWN) {
            Message::addErrorMessage(Labels::getLabel('ERR_MESSAGE_CANNOT_BE_POSTED_NOW,_as_order_is_refunded_or_withdrawn.', $this->siteLangId));
            FatUtility::dieWithError(Message::getHtml());
        }

        /* save return request message[ */
        $returnRequestMsgDataToSave = array(
            'orrmsg_orrequest_id' => $requestRow['orrequest_id'],
            'orrmsg_from_user_id' => UserAuthentication::getLoggedUserId(),
            'orrmsg_msg' => $post['orrmsg_msg'],
            'orrmsg_date' => date('Y-m-d H:i:s'),
        );
        $oReturnRequestMsgObj = new OrderReturnRequestMessage();
        $oReturnRequestMsgObj->assignValues($returnRequestMsgDataToSave);
        if (!$oReturnRequestMsgObj->save()) {
            Message::addErrorMessage($oReturnRequestMsgObj->getError());
            FatUtility::dieWithError(Message::getHtml());
        }
        $orrmsg_id = $oReturnRequestMsgObj->getMainTableRecordId();
        if (!$orrmsg_id) {
            Message::addErrorMessage(Labels::getLabel('ERR_SOMETHING_WENT_WRONG,_please_contact_admin', $this->siteLangId));
            FatUtility::dieWithError(Message::getHtml());
        }
        /* ] */

        /* sending of email notification[ */
        $emailNotificationObj = new EmailHandler();
        if (!$emailNotificationObj->sendReturnRequestMessageNotification($orrmsg_id, $this->siteLangId)) {
            Message::addErrorMessage($emailNotificationObj->getError());
            FatUtility::dieWithError(Message::getHtml());
        }
        /* ] */

        $this->set('orrmsg_orrequest_id', $orrmsg_orrequest_id);
        $this->set('msg', Labels::getLabel('MSG_Message_Submitted_Successfully!', $this->siteLangId));
        $this->_template->render(false, false, 'json-success.php');
    }

    public function socialPlatforms()
    {
        $this->userPrivilege->canViewShop(UserAuthentication::getLoggedUserId());
        $userId = $this->userParentId;
        $shopDetails = Shop::getAttributesByUserId($userId, null, false);
        if (false == $shopDetails) {
            Message::addErrorMessage(Labels::getLabel('ERR_INVALID_REQUEST', $this->siteLangId));
            FatUtility::dieWithError(Message::getHtml());
        }

        if (!false == $shopDetails && $shopDetails['shop_active'] != applicationConstants::ACTIVE) {
            Message::addErrorMessage(Labels::getLabel('ERR_YOUR_SHOP_DEACTIVATED_CONTACT_ADMIN', $this->siteLangId));
            FatUtility::dieWithError(Message::getHtml());
        }
        if (!false == $shopDetails) {
            $shop_id = $shopDetails['shop_id'];
            $stateId = $shopDetails['shop_state_id'];
        }

        $srch = SocialPlatform::getSearchObject($this->siteLangId, false);
        $srch->doNotCalculateRecords();
        $srch->doNotLimitRecords();
        $srch->addCondition('splatform_user_id', '=', $this->userParentId);
        $rs = $srch->getResultSet();
        $records = FatApp::getDb()->fetchAll($rs);
        $this->set('canEdit', $this->userPrivilege->canEditShop(UserAuthentication::getLoggedUserId(), true));
        $this->set("arrListing", $records);
        $this->set('shop_id', $shop_id);
        $this->set('siteLangId', $this->siteLangId);
        $this->set('language', Language::getAllNames());
        $this->_template->render(false, false);
    }

    public function socialPlatformForm($splatform_id = 0)
    {
        $splatform_id = FatUtility::int($splatform_id);
        $frm = $this->getSocialPlatformForm($splatform_id);
        $identifier = '';
        if (0 < $splatform_id) {
            $data = SocialPlatform::getAttributesByLangId(CommonHelper::getDefaultFormLangId(), $splatform_id, array('*', 'IFNULL(splatform_title,splatform_identifier) as splatform_title'), applicationConstants::JOIN_RIGHT);
            if ($data === false) {
                Message::addErrorMessage(Labels::getLabel('ERR_INVALID_REQUEST_ID', $this->siteLangId));
                FatUtility::dieWithError(Message::getHtml());
            }
            $frm->fill($data);
            $identifier = $data[SocialPlatform::tblFld('identifier')];
        }

        $this->set('splatform_id', $splatform_id);
        $this->set('identifier', $identifier);
        $this->set('frm', $frm);
        $this->set('languages', Language::getAllNames());
        $this->_template->render(false, false);
    }

    public function socialPlatformSetup()
    {
        $this->userPrivilege->canViewShop(UserAuthentication::getLoggedUserId());
        $frm = $this->getSocialPlatformForm();
        $post = $frm->getFormDataFromArray(FatApp::getPostedData());

        if (false === $post) {
            FatUtility::dieJsonError(current($frm->getValidationErrors()));
        }

        $splatform_id = $post['splatform_id'];
        unset($post['splatform_id']);
        $data = $post;
        $data['splatform_user_id'] = $this->userParentId;
        $data['splatform_identifier'] = $data['splatform_title'];

        $recordObj = new SocialPlatform($splatform_id);
        $recordObj->assignValues($data, true);
        if (!$recordObj->save()) {
            Message::addErrorMessage($recordObj->getError());
            FatUtility::dieWithError(Message::getHtml());
        }
        $splatform_id = $recordObj->getMainTableRecordId();

        $this->setLangData($recordObj, [$recordObj::tblFld('title') => $data[$recordObj::tblFld('title')]]);

        $this->set('msg', Labels::getLabel('MSG_SETUP_SUCCESSFUL', $this->siteLangId));
        $this->set('splatformId', $splatform_id);
        $this->_template->render(false, false, 'json-success.php');
    }

    public function socialPlatformLangForm($splatform_id = 0, $lang_id = 0, $autoFillLangData = 0)
    {
        $splatform_id = FatUtility::int($splatform_id);
        $lang_id = FatUtility::int($lang_id);

        if ($splatform_id == 0 || $lang_id == 0) {
            FatUtility::dieWithError($this->str_invalid_request);
        }

        $langFrm = $this->getSocialPlatformLangForm($splatform_id, $lang_id);

        if (0 < $autoFillLangData) {
            $updateLangDataobj = new TranslateLangData(SocialPlatform::DB_TBL_LANG);
            $translatedData = $updateLangDataobj->getTranslatedData($splatform_id, $lang_id, CommonHelper::getDefaultFormLangId());
            if (false === $translatedData) {
                Message::addErrorMessage($updateLangDataobj->getError());
                FatUtility::dieWithError(Message::getHtml());
            }
            $langData = current($translatedData);
        } else {
            $langData = SocialPlatform::getAttributesByLangId($lang_id, $splatform_id);
        }

        if ($langData) {
            $langFrm->fill($langData);
        }

        $this->set('languages', Language::getAllNames());
        $this->set('splatform_id', $splatform_id);
        $this->set('langId', $lang_id);
        $this->set('langFrm', $langFrm);
        $this->set('formLayout', Language::getLayoutDirection($lang_id));
        $this->_template->render(false, false);
    }

    public function socialPlatformLangSetup()
    {
        $this->userPrivilege->canViewShop(UserAuthentication::getLoggedUserId());
        $post = FatApp::getPostedData();
        $splatform_id = FatUtility::int($post['splatform_id']);

        $languages = Language::getAllNames();
        if (count($languages) > 1) {
            $lang_id = $post['lang_id'];
        } else {
            $lang_id = array_key_first($languages);
        }

        if (1 > $splatform_id || 1 > $lang_id) {
            Message::addErrorMessage(Labels::getLabel('ERR_INVALID_REQUEST', $lang_id));
            FatUtility::dieWithError(Message::getHtml());
        }

        $frm = $this->getSocialPlatformLangForm($splatform_id, $lang_id);
        $post = $frm->getFormDataFromArray(FatApp::getPostedData());
        if (false === $post) {
            FatUtility::dieJsonError(current($frm->getValidationErrors()));
        }

        $recordObj = new SocialPlatform($splatform_id);
        $this->setLangData($recordObj, [$recordObj::tblFld('title') => $post[$recordObj::tblFld('title')]], $lang_id);

        $this->set('splatformId', $splatform_id);
        $this->_template->render(false, false, 'json-success.php');
    }

    public function deleteSocialPlatform()
    {
        $this->userPrivilege->canEditShop(UserAuthentication::getLoggedUserId());
        $userId = $this->userParentId;
        $splatformId = FatApp::getPostedData('splatformId', FatUtility::VAR_INT, 0);
        if ($splatformId < 1) {
            FatUtility::dieJsonError(Labels::getLabel("ERR_INVALID_ACCESS", $this->siteLangId));
        }

        $srch = SocialPlatform::getSearchObject($this->siteLangId, false);
        $srch->addCondition('splatform_user_id', '=', $userId);
        $srch->addCondition('splatform_id', '=', $splatformId);
        $rs = $srch->getResultSet();
        $orderDetail = FatApp::getDb()->fetch($rs);

        if (!$orderDetail) {
            FatUtility::dieJsonError(Labels::getLabel('ERR_INVALID_ACCESS', $this->siteLangId));
        }

        $obj = new SocialPlatform($splatformId);
        if (!$obj->deleteRecord(true)) {
            FatUtility::dieJsonError($obj->getError());
        }

        FatUtility::dieJsonSuccess(Labels::getLabel("MSG_Social_Platform_deleted!", $this->siteLangId));
    }

    public function changeSocialPlatformStatus()
    {
        $this->userPrivilege->canEditShop(UserAuthentication::getLoggedUserId());
        $socialPlatformId = FatApp::getPostedData('socialPlatformId', FatUtility::VAR_INT, 0);

        $data = SocialPlatform::getAttributesById($socialPlatformId, array('splatform_id', 'splatform_active'));

        $status = ($data['splatform_active'] == applicationConstants::ACTIVE) ? applicationConstants::INACTIVE : applicationConstants::ACTIVE;

        $this->updateSocialPlatformStatus($socialPlatformId, $status);

        $this->set('msg', Labels::getLabel('MSG_Status_changed_Successfully', $this->siteLangId));
        $this->_template->render(false, false, 'json-success.php');
    }

    private function updateSocialPlatformStatus($socialPlatformId, $status)
    {
        $this->userPrivilege->canEditShop(UserAuthentication::getLoggedUserId());
        $socialPlatformId = FatUtility::int($socialPlatformId);
        $status = FatUtility::int($status);
        if (1 > $socialPlatformId || -1 == $status) {
            FatUtility::dieWithError(
                Labels::getLabel('MSG_INVALID_REQUEST', $this->siteLangId)
            );
        }
        $splatform = new SocialPlatform($socialPlatformId);
        if (!$splatform->changeStatus($status)) {
            Message::addErrorMessage($splatform->getError());
            FatUtility::dieWithError(Message::getHtml());
        }
    }

    public function sellerProductsAutoComplete()
    {
        $userId = $this->userParentId;
        $pageSize = FatApp::getConfig('CONF_PAGE_SIZE');
        $db = FatApp::getDb();
        $json = array();
        $post = FatApp::getPostedData();

        $srch = SellerProduct::getSearchObject($this->siteLangId);
        $srch->doNotCalculateRecords();
        $srch->joinTable(Product::DB_TBL, 'INNER JOIN', 'p.product_id = sp.selprod_product_id', 'p');
        $srch->joinTable(Product::DB_TBL_LANG, 'LEFT OUTER JOIN', 'p.product_id = p_l.productlang_product_id AND p_l.productlang_lang_id = ' . $this->siteLangId, 'p_l');
        $srch->addCondition('selprod_user_id', '=', $userId);
        $srch->addCondition('sp.selprod_active', '=', applicationConstants::ACTIVE);
        $srch->addCondition('p.product_active', '=', applicationConstants::ACTIVE);
        $srch->addCondition('p.product_approved', '=', Product::APPROVED);
        $srch->addCondition('p.product_deleted', '=', applicationConstants::NO);
        $srch->addOrder('product_name');
        $srch->addOrder('selprod_title');
        $srch->addOrder('selprod_id');
        $srch->addMultipleFields(array('selprod_id', 'IFNULL(selprod_title  ,IFNULL(product_name, product_identifier)) as selprod_title', 'IFNULL(product_name, product_identifier) as product_name', 'selprod_price'));
        //$srch->setPageSize( $pageSize );
        if (!empty($post['keyword'])) {
            // $cnd = $srch->addCondition('product_name', 'LIKE', '%' . $post['keyword'] . '%');
            $srch->addCondition('selprod_title', 'LIKE', '%' . $post['keyword'] . '%');
            //$cnd->attachCondition('option_identifier', 'LIKE', '%'. $post['keyword'] . '%', 'OR');
        }
        $excludeRecords = FatApp::getPostedData('excludeRecords', FatUtility::VAR_INT);
        if (!empty($excludeRecords) && is_array($excludeRecords)) {
            $srch->addCondition('selprod_id', 'NOT IN', $excludeRecords);
        }

        $rs = $srch->getResultSet();
        $products = $db->fetchAll($rs, 'selprod_id');

        if ($products) {
            foreach ($products as $selprod_id => $product) {
                $options = SellerProduct::getSellerProductOptions($product['selprod_id'], true, $this->siteLangId);

                $variantStr = $product['selprod_title'];

                if (is_array($options) && count($options)) {
                    $variantStr .= ' (';
                    $counter = 1;
                    foreach ($options as $op) {
                        $variantStr .= $op['option_name'] . ': ' . $op['optionvalue_name'];
                        if ($counter != count($options)) {
                            $variantStr .= ', ';
                        }
                        $counter++;
                    }
                    $variantStr .= ' )';
                }
                $json[] = array(
                    'id' => $selprod_id,
                    'value' => strip_tags(html_entity_decode($variantStr, ENT_QUOTES, 'UTF-8')),
                );
            }
        }

        echo json_encode(array('suggestions' => $json));
        exit;
        //die(json_encode($json));
    }

    private function getCatalogRequestMessageSearchForm()
    {
        $frm = new Form('frmCatalogRequestMsgsSrch');
        $frm->addHiddenField('', 'page');
        $frm->addHiddenField('', 'requestId');
        return $frm;
    }

    private function getCatalogRequestMessageForm($requestId)
    {
        $frm = new Form('catalogRequestMsgForm');

        $frm->addHiddenField('', 'requestId', $requestId);
        $frm->addTextArea(Labels::getLabel('FRM_MESSAGE', $this->siteLangId), 'message');
        $frm->addSubmitButton('', 'btn_submit', Labels::getLabel('BTN_SEND', $this->siteLangId));
        return $frm;
    }

    private function getTaxCatSearchForm($langId)
    {
        $frm = new Form('frmSearchTaxCat');
        $frm->addTextBox('', 'keyword');
        $frm->addHiddenField('', 'total_record_count');
        HtmlHelper::addSearchButton($frm);
        return $frm;
    }

    protected function getSocialPlatformLangForm($recordId = 0, $langId = 0)
    {
        $frm = new Form('frmSocialPlatformLang');
        $frm->addHiddenField('', 'splatform_id', $recordId);
        $frm->addSelectBox(Labels::getLabel('FRM_LANGUAGE', $langId), 'lang_id', Language::getDropDownList(CommonHelper::getDefaultFormLangId()), $langId, array(), '');
        $frm->addRequiredField(Labels::getLabel('FRM_TITLE', $this->siteLangId), 'splatform_title');
        return $frm;
    }

    private function getSocialPlatformForm($splatform_id = 0)
    {
        if ($splatform_id > 0) {
            $iconsArr = SocialPlatform::getIconArr($this->siteLangId);
        } else {
            $iconsArr = SocialPlatform::getAvailableIconsArr($this->userParentId, $this->siteLangId);
        }
        $frm = new Form('frmSocialPlatform');
        $frm->addHiddenField('', 'splatform_id', $splatform_id);
        $fld = $frm->addSelectBox(Labels::getLabel('FRM_SELECT_PLATFORM', $this->siteLangId), 'splatform_icon_class', $iconsArr, '', array(), Labels::getLabel('FRM_SELECT', $this->siteLangId));
        if ($splatform_id > 0) {
            $fld->setFieldTagAttribute('disabled', 'disabled');
        }
        $frm->addRequiredField(Labels::getLabel('FRM_TITLE', $this->siteLangId), 'splatform_title');
        $urlFld = $frm->addTextBox(Labels::getLabel('FRM_URL', $this->siteLangId), 'splatform_url');
        $urlFld->requirements()->setRegularExpressionToValidate(ValidateElement::URL_REGEX);
        $urlFld->requirements()->setCustomErrorMessage(Labels::getLabel('FRM_THIS_MUST_BE_AN_ABSOLUTE_URL', $this->siteLangId));
        $urlFld->requirements()->setRequired();
        $frm->addCheckBox(Labels::getLabel('FRM_STATUS', $this->siteLangId), 'splatform_active', applicationConstants::ACTIVE, array(), true, applicationConstants::INACTIVE);

        $languageArr = Language::getDropDownList();
        $translatorSubscriptionKey = FatApp::getConfig('CONF_TRANSLATOR_SUBSCRIPTION_KEY', FatUtility::VAR_STRING, '');
        if (!empty($translatorSubscriptionKey) && 1 < count($languageArr)) {
            $frm->addCheckBox(Labels::getLabel('FRM_UPDATE_OTHER_LANGUAGES_DATA', $this->siteLangId), 'auto_update_other_langs_data', 1, array(), false, 0);
        }
        return $frm;
    }
    private function getShopInfoForm($shopUserId, $shop_id = 0)
    {
        $frm = new Form('frmShop');
        $frm->addHiddenField('', 'shop_id', $shop_id);
        $frm->addRequiredField(Labels::getLabel('FRM_IDENTIFIER', $this->siteLangId), 'shop_identifier');
        $fld = $frm->addTextBox(Labels::getLabel('FRM_SHOP_SEO_FRIENDLY_URL', $this->siteLangId), 'urlrewrite_custom');
        $fld->requirements()->setRequired();

        $frm->addHiddenField('', 'shop_phone_dcode');
        $phnFld = $frm->addTextBox(Labels::getLabel('FRM_PHONE', $this->siteLangId), 'shop_phone', '', array('class' => 'phone-js ltr-right', 'placeholder' => ValidateElement::PHONE_NO_FORMAT, 'maxlength' => ValidateElement::PHONE_NO_LENGTH));
        $phnFld->requirements()->setRegularExpressionToValidate(ValidateElement::PHONE_REGEX);
        $phnFld->requirements()->setCustomErrorMessage(Labels::getLabel('FRM_PLEASE_ENTER_VALID_PHONE_NUMBER_FORMAT.', $this->siteLangId));

        $countryObj = new Countries();
        $countriesArr = $countryObj->getCountriesAssocArr($this->siteLangId, true, 'country_code');
        $fld = $frm->addSelectBox(Labels::getLabel('FRM_COUNTRY', $this->siteLangId), 'shop_country_code', $countriesArr, FatApp::getConfig('CONF_COUNTRY', FatUtility::VAR_INT, 223), array(), Labels::getLabel('FRM_SELECT', $this->siteLangId));
        $fld->requirement->setRequired(true);

        $frm->addSelectBox(Labels::getLabel('FRM_STATE', $this->siteLangId), 'shop_state', array(), '', array(), Labels::getLabel('FRM_SELECT', $this->siteLangId))->requirement->setRequired(true);

        $zipFld = $frm->addRequiredField(Labels::getLabel('FRM_POSTALCODE', $this->siteLangId), 'shop_postalcode');

        /* $zipFld->requirements()->setRegularExpressionToValidate(ValidateElement::ZIP_REGEX);
          $zipFld->requirements()->setCustomErrorMessage(Labels::getLabel('FRM_ONLY_ALPHANUMERIC_VALUE_IS_ALLOWED.', $this->siteLangId)); */

        $onOffArr = applicationConstants::getOnOffArr($this->siteLangId);

        $frm->addSelectBox(Labels::getLabel('FRM_DISPLAY_STATUS', $this->siteLangId), 'shop_supplier_display_status', $onOffArr);

        /* $fld = $frm->addTextBox(Labels::getLabel('FRM_FREE_SHIPPING_ON', $this->siteLangId), 'shop_free_ship_upto');
          $fld->requirements()->setInt();
          $fld->requirements()->setPositive(); */

        $fld = $frm->addTextBox(Labels::getLabel('FRM_ORDER_RETURN_AGE', $this->siteLangId), 'shop_return_age');
        $fld->requirements()->setInt();
        $fld->requirements()->setPositive();

        $fld = $frm->addTextBox(Labels::getLabel('FRM_ORDER_CANCELLATION_AGE', $this->siteLangId), 'shop_cancellation_age');
        $fld->requirements()->setInt();
        $fld->requirements()->setPositive();

        $fld = $frm->addTextBox(Labels::getLabel('FRM_DISPLAY_TIME_SLOTS_AFTER_ORDER', $this->siteLangId) . ' [' . Labels::getLabel('FRM_HOURS', $this->siteLangId) . ']', 'shop_pickup_interval', 2);
        $fld->requirements()->setRange('2', '9999999999');

        $shopDetails = Shop::getAttributesByUserId(UserAuthentication::getLoggedUserId(), null, false);
        $address = new Address(0, $this->siteLangId);
        $addresses = (is_array($shopDetails) && isset($shopDetails['shop_id'])) ? $address->getData(Address::TYPE_SHOP_PICKUP, $shopDetails['shop_id']) : '';

        $fulfillmentType = empty($addresses) ? Shipping::FULFILMENT_SHIP : Shipping::FULFILMENT_ALL;

        $fulFillmentArr = Shipping::getFulFillmentArr($this->siteLangId, $fulfillmentType);
        $fld = $frm->addSelectBox(Labels::getLabel('FRM_FULFILLMENT_METHOD', $this->siteLangId), 'shop_fulfillment_type', $fulFillmentArr, applicationConstants::NO);
        $fld->requirements()->setRequired();

        $pluginObj = new Plugin();
        $sellerPluginObj = new SellerPlugin(0, $shopUserId);
        if (0 === FatApp::getConfig('CONF_SHIPPED_BY_ADMIN_ONLY', FatUtility::VAR_INT, 0) && ($pluginObj->getDefaultPluginData(Plugin::TYPE_SHIPPING_SERVICES, 'plugin_active') || $sellerPluginObj->getDefaultPluginData(Plugin::TYPE_SHIPPING_SERVICES, 'pu_active'))) {
            $fld = $frm->addCheckBox(
                Labels::getLabel("FRM_USE_MANUAL_SHIPPING_RATES_INSTEAD_OF_THIRD_PARTY.", $this->siteLangId),
                'shop_use_manual_shipping_rates',
                1,
                array(),
                false,
                0
            );
            HtmlHelper::configureSwitchForCheckbox($fld, Labels::getLabel("FRM_MANUAL_SHIPPING_RATES_WERE_CONSIDERED_FOR_SELLER_SHIPPING.", $this->siteLangId));
            $fld->developerTags['noCaptionTag'] = false;
        }

        if (
            0 < FatApp::getConfig('CONF_RFQ_MODULE', FatUtility::VAR_INT, 0) &&
            applicationConstants::NO == FatApp::getConfig('CONF_HIDE_PRICES', FatUtility::VAR_INT, 0)
        ) {
            $fld = $frm->addCheckBox(Labels::getLabel("FRM_ENABLE_RFQ_MODULE", $this->siteLangId), 'shop_rfq_enabled', 1, array(), false, 0);
            HtmlHelper::configureSwitchForCheckbox($fld, Labels::getLabel('FRM_ENABLING_THIS,_MAKES_PRODUCTS_AVAILABLE_FOR_RFQ.', $this->siteLangId));
        }

        $fld = $frm->addTextarea(Labels::getLabel("FRM_GOVERNMENT_INFORMATION_ON_INVOICES", $this->siteLangId), 'shop_invoice_codes');
        $fld->htmlAfterField = "<span class='form-text text-muted'>" . Labels::getLabel("FRM_INFORMATION_MANDATED_BY_THE_GOVERNMENT_ON_INVOICES.", $this->siteLangId) . "</span>";

        $frm->addHiddenField('', 'shop_lat');
        $frm->addHiddenField('', 'shop_lng');
        $frm->addSubmitButton('', 'btn_submit', Labels::getLabel('BTN_SAVE_CHANGES', $this->siteLangId));
        return $frm;
    }

    private function getShopLogoForm($shop_id, $langId)
    {
        $frm = new Form('frmShopLogo');
        $frm->addHiddenField('', 'shop_id', $shop_id);
        $bannerTypeArr = applicationConstants::getAllLanguages();

        if (count($bannerTypeArr) > 1) {
            $frm->addSelectBox(Labels::getLabel('FRM_LANGUAGE', $langId), 'lang_id', $bannerTypeArr, '', array('class' => 'logo-language-js'), '');
        } else {
            $langId = array_key_first($bannerTypeArr);
            $frm->addHiddenField('', 'lang_id', $langId);
        }

        $ratioArr = AttachedFile::getRatioTypeWithCustom($this->siteLangId);
        $frm->addRadioButtons(Labels::getLabel('FRM_RATIO', $this->siteLangId), 'ratio_type', $ratioArr, AttachedFile::RATIO_TYPE_SQUARE, array('class' => 'list-inline'));
        $frm->addHiddenField('', 'file_type', AttachedFile::FILETYPE_SHOP_LOGO);
        $frm->addHiddenField('', 'logo_min_width');
        $frm->addHiddenField('', 'logo_min_height');

        $frm->addHTML('', 'shop_logo', '');
        return $frm;
    }

    private function getBackgroundImageForm($shop_id, $langId)
    {
        $frm = new Form('frmBackgroundImage');
        $frm->addHiddenField('', 'shop_id', $shop_id);
        $bannerTypeArr = applicationConstants::getAllLanguages();
        if (count($bannerTypeArr) > 1) {
            $frm->addSelectBox(Labels::getLabel('FRM_LANGUAGE', $langId), 'lang_id', $bannerTypeArr, '', array('class' => 'bg-language-js'), '');
        } else {
            $langId = array_key_first($bannerTypeArr);
            $frm->addHiddenField('', 'lang_id', $langId);
        }

        $fld = $frm->addButton(
            Labels::getLabel('FRM_BACKGROUND_IMAGE', $langId),
            'shop_background_image',
            Labels::getLabel('FRM_UPLOAD_BACKGROUND_IMAGE', $this->siteLangId),
            array('class' => 'shopFile-Js', 'id' => 'shop_background_image', 'data-file_type' => AttachedFile::FILETYPE_SHOP_BACKGROUND_IMAGE, 'data-frm' => 'frmBackgroundImage')
        );
        return $frm;
    }

    private function getShopBannerForm($shop_id, $langId)
    {
        $frm = new Form('frmShopBanner');
        $frm->addHiddenField('', 'shop_id', $shop_id);
        $bannerTypeArr = applicationConstants::getAllLanguages();
        if (count($bannerTypeArr) > 1) {
            $frm->addSelectBox(Labels::getLabel('FRM_LANGUAGE', $langId), 'lang_id', $bannerTypeArr, '', array('class' => 'banner-language-js'), '');
        } else {
            $langId = array_key_first($bannerTypeArr);
            $frm->addHiddenField('', 'lang_id', $langId);
        }

        $screenArr = applicationConstants::getDisplaysArr($this->siteLangId);
        $frm->addSelectBox(Labels::getLabel("FRM_DISPLAY_FOR", $this->siteLangId), 'slide_screen', $screenArr, '', array(), '');
        $frm->addHiddenField('', 'file_type', AttachedFile::FILETYPE_SHOP_BANNER);
        $frm->addHiddenField('', 'banner_min_width');
        $frm->addHiddenField('', 'banner_min_height');
        // $frm->addFileUpload(Labels::getLabel('FRM_UPLOAD', $this->siteLangId), 'shop_banner', array('accept' => 'image/*', 'data-frm' => 'frmShopBanner'));
        $frm->addHTML('', 'shop_banner', '');
        return $frm;
    }

    private function getShopLangInfoForm($shop_id = 0, $lang_id = 0)
    {
        $frm = new Form('frmShopLang');
        $frm->addHiddenField('', 'shop_id', $shop_id);

        $languages = Language::getAllNames();
        if (count($languages) > 1) {
            $frm->addSelectBox(Labels::getLabel('FRM_LANGUAGE', $lang_id), 'lang_id', $languages, $lang_id, array(), '');
        } else {
            $lang_id = array_key_first($languages);
            $fl = $frm->addHiddenField('', 'lang_id', $lang_id);
        }

        $frm->addRequiredField(Labels::getLabel('FRM_SHOP_NAME', $lang_id), 'shop_name');
        $frm->addRequiredField(Labels::getLabel('FRM_SHOP_ADDRESS_LINE_1', $lang_id), 'shop_address_line_1');
        $frm->addTextBox(Labels::getLabel('FRM_SHOP_ADDRESS_LINE_2', $lang_id), 'shop_address_line_2');
        $frm->addTextBox(Labels::getLabel('FRM_SHOP_CITY', $lang_id), 'shop_city');
        $frm->addTextBox(Labels::getLabel('FRM_CONTACT_PERSON', $lang_id), 'shop_contact_person');
        $frm->addTextarea(Labels::getLabel('FRM_DESCRIPTION', $lang_id), 'shop_description');
        $frm->addTextarea(Labels::getLabel('FRM_PAYMENT_POLICY', $lang_id), 'shop_payment_policy');
        $frm->addTextarea(Labels::getLabel('FRM_DELIVERY_POLICY', $lang_id), 'shop_delivery_policy');
        $frm->addTextarea(Labels::getLabel('FRM_REFUND_POLICY', $lang_id), 'shop_refund_policy');
        $frm->addTextarea(Labels::getLabel('FRM_ADDITIONAL_INFORMATION', $lang_id), 'shop_additional_info');
        $frm->addTextarea(Labels::getLabel('FRM_SELLER_INFORMATION', $lang_id), 'shop_seller_info');
        /* $fld = $frm->addButton(Labels::getLabel('FRM_LOGO',$this->siteLangId),'shop_logo',Labels::getLabel('FRM_UPLOAD_LOGO',$this->siteLangId),
          array('class'=>'shopFile-Js','id'=>'shop_logo','data-file_type'=>AttachedFile::FILETYPE_SHOP_LOGO));

          $fld1 =  $frm->addButton(Labels::getLabel('FRM_BANNER',$this->siteLangId),'shop_banner',Labels::getLabel('FRM_UPLOAD_BANNER',$this->siteLangId),array('class'=>'shopFile-Js','id'=>'shop_banner','data-file_type'=>AttachedFile::FILETYPE_SHOP_BANNER)); */

        $siteLangId = FatApp::getConfig('conf_default_site_lang', FatUtility::VAR_INT, 1);
        $translatorSubscriptionKey = FatApp::getConfig('CONF_TRANSLATOR_SUBSCRIPTION_KEY', FatUtility::VAR_STRING, '');

        if (!empty($translatorSubscriptionKey) && $lang_id == $siteLangId) {
            $frm->addCheckBox(Labels::getLabel('FRM_UPDATE_OTHER_LANGUAGES_DATA', $lang_id), 'auto_update_other_langs_data', 1, array(), false, 0);
        }

        $frm->addSubmitButton('', 'btn_submit', Labels::getLabel('BTN_SAVE_CHANGES', $lang_id));
        return $frm;
    }

    private function getCatalogProductSearchForm($type = '')
    {
        $frm = new Form('frmSearchCatalogProduct');
        $frm->addHiddenField('', 'badge_id');
        $frm->addHiddenField('', 'ribbon_id');
        $frm->addHiddenField('', 'page');
        $frm->addHiddenField('', 'lang_id');
        $frm->addTextBox(Labels::getLabel('FRM_SEARCH_BY', $this->siteLangId), 'keyword');
        if (FatApp::getConfig('CONF_ENABLED_SELLER_CUSTOM_PRODUCT')) {
            $frm->addHiddenField('', 'type', $type);
        }
        $frm->addSelectBox(Labels::getLabel('FRM_PRODUCT_TYPE', $this->siteLangId), 'product_type', array(-1 => Labels::getLabel('LBL_SELECT_PRODUCT_TYPE', $this->siteLangId)) + Product::getProductTypes($this->siteLangId), '-1', array(), '');

        HtmlHelper::addSearchButton($frm);
        HtmlHelper::addClearButton($frm, 'btn btn-clear');
        return $frm;
    }

    private function getTagsProdSrchForm()
    {
        $frm = new Form('frmRecordSearch');
        $frm->addTextBox(Labels::getLabel('FRM_SEARCH_BY', $this->siteLangId), 'keyword');
        $frm->addHiddenField('', 'lang_id');
        $frm->addHiddenField('', 'page');
        HtmlHelper::addSearchButton($frm);
        HtmlHelper::addClearButton($frm, 'btn btn-clear');
        return $frm;
    }

    private function addNewCatalogRequestForm()
    {
        $frm = new Form('frmAddCatalogRequest', array('enctype' => "multipart/form-data"));
        $frm->addRequiredField(Labels::getLabel('FRM_TITLE', $this->siteLangId), 'scatrequest_title');
        /* $fld = $frm->addHtmlEditor(Labels::getLabel('FRM_CONTENT',$this->siteLangId),'scatrequest_content');
          $fld->htmlBeforeField = '<div class="editor-bar">';
          $fld->htmlAfterField = '</div>'; */
        $frm->addTextArea(Labels::getLabel('FRM_CONTENT', $this->siteLangId), 'scatrequest_content');
        $fileFld = $frm->addFileUpload(Labels::getLabel('FRM_UPLOAD_FILE', $this->siteLangId), 'file', array('accept' => 'image/*,.zip', 'enctype' => "multipart/form-data"));
        $fileFld->htmlBeforeField = '<div class="filefield"><span class="filename"></span>';
        $fileFld->htmlAfterField = '</div><span class="form-text text-muted">' . Labels::getLabel('MSG_Only_Image_extensions_and_zip_is_allowed', $this->siteLangId) . '</span>';
        $frm->addSubmitButton('', 'btn_submit', Labels::getLabel('BTN_SAVE_CHANGES', $this->siteLangId));
        return $frm;
    }

    private function getSellerProdCategoriesObj($userId, $shopId = 0, $prodcat_id = 0, $lang_id = 0)
    {
        return Shop::getProdCategoriesObj($userId, $lang_id, $shopId, $prodcat_id);
    }

    private function getCategoryMediaForm($prodCatId)
    {
        $frm = new Form('frmCategoryMedia');
        $frm->addHiddenField('', 'prodcat_id', $prodCatId);
        $bannerTypeArr = applicationConstants::getAllLanguages();
        $frm->addSelectBox(Labels::getLabel('FRM_LANGUAGE', $this->siteLangId), 'lang_id', $bannerTypeArr, '', array(), '');
        $fld1 = $frm->addButton('', 'category_banner', Labels::getLabel('FRM_UPLOAD_FILE', $this->siteLangId), array('class' => 'catFile-Js', 'id' => 'category_banner'));
        return $frm;
    }

    private function getOrderCommentsForm($orderData = array(), $processingOrderStatus = [])
    {
        $frm = new Form('frmOrderComments');
        $frm->addTextArea(Labels::getLabel('FRM_YOUR_COMMENTS', $this->siteLangId), 'comments');
        $orderStatusArr = Orders::getOrderProductStatusArr($this->siteLangId, $processingOrderStatus, $orderData['op_status_id']);

        $fld = $frm->addSelectBox(Labels::getLabel('FRM_STATUS', $this->siteLangId), 'op_status_id', $orderStatusArr, '', [], Labels::getLabel('FRM_SELECT', $this->siteLangId));
        $fld->requirements()->setRequired();

        $frm->addSelectBox(Labels::getLabel('FRM_NOTIFY_CUSTOMER', $this->siteLangId), 'customer_notified', applicationConstants::getYesNoArr($this->siteLangId), applicationConstants::YES, array(), Labels::getLabel('FRM_SELECT', $this->siteLangId))->requirements()->setRequired();
        if (array_key_exists('opship_tracking_number', $orderData) && (empty($orderData['opship_tracking_number']) || $orderData['opshipping_plugin_code'] == 'ShipStationShipping') && $orderData['orderstatus_id'] != FatApp::getConfig("CONF_DEFAULT_SHIPPING_ORDER_STATUS")) {

            $shippedBySeller = applicationConstants::NO;
            if (CommonHelper::canAvailShippingChargesBySeller($orderData['op_selprod_user_id'], $orderData['opshipping_by_seller_user_id'])) {
                $shippedBySeller = applicationConstants::YES;
            }
            $shippingApiObj = (new Shipping($this->siteLangId))->getShippingApiObj(($shippedBySeller ? $orderData['opshipping_by_seller_user_id'] : 0)) ?? NULL;
            if (!$shippingApiObj) {
                $manualFld = $frm->addCheckBox(Labels::getLabel('FRM_SELF_SHIPPING', $this->siteLangId), 'manual_shipping', 1, array(), false, 0);
            } else {
                $manualFld = $frm->addSelectBox(Labels::getLabel('FRM_SHIPPED_VIA', $this->siteLangId), 'manual_shipping', [0 => Labels::getLabel("FRM_SHIPPING_PLUGIN", $this->siteLangId), 1 => Labels::getLabel("FRM_SELF_SHIPPING", $this->siteLangId)], 0, array(), false);
            }

            $manualShipUnReqObj = new FormFieldRequirement('manual_shipping', Labels::getLabel('FRM_SELF_SHIPPING', $this->siteLangId));
            $manualShipUnReqObj->setRequired(false);
            $manualShipReqObj = new FormFieldRequirement('manual_shipping', Labels::getLabel('FRM_SELF_SHIPPING', $this->siteLangId));
            $manualShipReqObj->setRequired(true);

            $fld->requirements()->addOnChangerequirementUpdate(FatApp::getConfig("CONF_DEFAULT_SHIPPING_ORDER_STATUS", FatUtility::VAR_INT), 'eq', 'manual_shipping', $manualShipReqObj);
            $fld->requirements()->addOnChangerequirementUpdate(FatApp::getConfig("CONF_DEFAULT_SHIPPING_ORDER_STATUS", FatUtility::VAR_INT), 'ne', 'manual_shipping', $manualShipUnReqObj);

            $frm->addTextBox(Labels::getLabel('FRM_TRACKING_NUMBER', $this->siteLangId), 'tracking_number');

            $trackingUnReqObj = new FormFieldRequirement('tracking_number', Labels::getLabel('FRM_TRACKING_NUMBER', $this->siteLangId));
            $trackingUnReqObj->setRequired(false);
            $trackingReqObj = new FormFieldRequirement('tracking_number', Labels::getLabel('FRM_TRACKING_NUMBER', $this->siteLangId));
            $trackingReqObj->setRequired(true);

            $manualFld->requirements()->addOnChangerequirementUpdate(applicationConstants::YES, 'eq', 'tracking_number', $trackingReqObj);
            $manualFld->requirements()->addOnChangerequirementUpdate(applicationConstants::NO, 'eq', 'tracking_number', $trackingUnReqObj);

            $trackUrlFld = $frm->addTextBox(Labels::getLabel('FRM_TRACKING_URL', $this->siteLangId), 'opship_tracking_url');
            $trackUrlFld->requirements()->setRegularExpressionToValidate(ValidateElement::URL_REGEX);
            $trackUrlFld->requirements()->setCustomErrorMessage(Labels::getLabel('FRM_TRACKING_URL_MUST_BE_AN_ABSOLUTE_URL', $this->siteLangId));
            $trackUrlFld->htmlAfterField = '<span class="note">' . Labels::getLabel('FRM_ENTER_THE_URL_TO_TRACK_THE_SHIPMENT.', $this->siteLangId) . '</span>';

            $trackingUrlUnReqObj = new FormFieldRequirement('opship_tracking_url', Labels::getLabel('FRM_TRACKING_URL', $this->siteLangId));
            $trackingUrlUnReqObj->setRequired(false);
            $trackingurlReqObj = new FormFieldRequirement('opship_tracking_url', Labels::getLabel('FRM_TRACKING_URL', $this->siteLangId));
            $trackingurlReqObj->setRequired(true);

            $manualFld->requirements()->addOnChangerequirementUpdate(applicationConstants::YES, 'eq', 'opship_tracking_url', $trackingurlReqObj);
            $manualFld->requirements()->addOnChangerequirementUpdate(applicationConstants::NO, 'eq', 'opship_tracking_url', $trackingUrlUnReqObj);

            $shipmentTracking = new ShipmentTracking();
            if (false !== $shipmentTracking->init($this->siteLangId) && false !== $shipmentTracking->getTrackingCouriers()) {
                $trackCarriers = $shipmentTracking->getResponse();

                $frm->addSelectBox(Labels::getLabel('FRM_TRACKING_COURIER', $this->siteLangId), 'oshistory_courier', $trackCarriers, '', array(), Labels::getLabel('FRM_SELECT', $this->siteLangId));

                $trackCarrierFldUnReqObj = new FormFieldRequirement('oshistory_courier', Labels::getLabel('FRM_TRACKING_COURIER', $this->siteLangId));
                $trackCarrierFldUnReqObj->setRequired(false);

                $trackCarrierFldReqObj = new FormFieldRequirement('oshistory_courier', Labels::getLabel('FRM_TRACKING_COURIER', $this->siteLangId));
                $trackCarrierFldReqObj->setRequired(true);

                $manualFld->requirements()->addOnChangerequirementUpdate(applicationConstants::YES, 'eq', 'oshistory_courier', $trackCarrierFldReqObj);
                $manualFld->requirements()->addOnChangerequirementUpdate(applicationConstants::NO, 'eq', 'oshistory_courier', $trackCarrierFldUnReqObj);
            }
        }

        $frm->addHiddenField('', 'shipped_by_plugin', 0, ['id' => 'shippedByPluginJs']);
        $frm->addHiddenField('', 'op_id', 0);
        $frm->addSubmitButton('', 'btn_submit', Labels::getLabel('BTN_SAVE_CHANGES', $this->siteLangId));
        return $frm;
    }

    private function getSubscriptionOrderSearchForm($langId)
    {
        $frm = new Form('frmOrderSrch');
        $frm->addHiddenField('', 'page');
        $frm->addTextBox(Labels::getLabel('FRM_KEYWORD', $langId), 'keyword', '', array('placeholder' => Labels::getLabel('LBL_Keyword', $langId)));
        $frm->addDateField(Labels::getLabel('FRM_DATE_FROM', $langId), 'date_from', '', array('placeholder' => Labels::getLabel('LBL_Date_From', $langId), 'readonly' => 'readonly'));
        $frm->addDateField(Labels::getLabel('FRM_DATE_TO', $langId), 'date_to', '', array('placeholder' => Labels::getLabel('LBL_Date_To', $langId), 'readonly' => 'readonly'));

        HtmlHelper::addSearchButton($frm);
        HtmlHelper::addClearButton($frm, 'btn btn-clear');
        return $frm;
    }

    private function getOrderCancelForm($langId)
    {
        $frm = new Form('frmOrderCancel');
        $frm->addHiddenField('', 'op_id');
        $fld = $frm->addTextArea(Labels::getLabel('FRM_COMMENTS', $langId), 'comments');
        $fld->requirements()->setRequired(true);
        $fld->requirements()->setCustomErrorMessage(Labels::getLabel('ERR_REASON_CANCELLATION', $langId));
        $frm->addSubmitButton('', 'btn_submit', Labels::getLabel('BTN_SAVE_CHANGES', $langId));
        return $frm;
    }

    /* -- - --   Packges  ----- */

    public function packages()
    {
        $this->userPrivilege->canViewSubscription(UserAuthentication::getLoggedUserId());
        if (!FatApp::getConfig('CONF_ENABLE_SELLER_SUBSCRIPTION_MODULE')) {
            Message::addErrorMessage(Labels::getLabel('ERR_INVALID_REQUEST', $this->siteLangId));
            FatApp::redirectUser(UrlHelper::generateUrl());
        }
        $includeFreeSubscription = OrderSubscription::canUserBuyFreeSubscription($this->siteLangId, $this->userParentId);
        $packagesArr = SellerPackages::getSellerVisiblePackages($this->siteLangId, $includeFreeSubscription);

        $currentPlanData = OrderSubscription::getUserCurrentActivePlanDetails($this->siteLangId, $this->userParentId, array('spp.*', 'ossubs_plan_id'));
        $currentActivePlanId = is_array($currentPlanData) && isset($currentPlanData[OrderSubscription::DB_TBL_PREFIX . 'plan_id']) ? $currentPlanData[OrderSubscription::DB_TBL_PREFIX . 'plan_id'] : 0;

        foreach ($packagesArr as $key => $package) {
            $packagesArr[$key]['plans'] = SellerPackagePlans::getSellerVisiblePackagePlans($package[SellerPackages::DB_TBL_PREFIX . 'id']);
            $packagesArr[$key]['cheapPlan'] = SellerPackagePlans::getCheapestPlanByPackageId($package[SellerPackages::DB_TBL_PREFIX . 'id']);
        }
        $obj = new Extrapage();
        $pageData = $obj->getContentByPageType(Extrapage::SUBSCRIPTION_PAGE_BLOCK, $this->siteLangId);
        $this->set('pageData', $pageData);
        $this->set('canEdit', $this->userPrivilege->canEditSubscription(UserAuthentication::getLoggedUserId(), true));
        $this->set('includeFreeSubscription', $includeFreeSubscription);
        $this->set('currentPlanData', $currentPlanData);
        $this->set('currentActivePlanId', $currentActivePlanId);
        $this->set('packagesArr', $packagesArr);
        $this->set('parentUserId', $this->userParentId);
        $this->_template->render(true, true);
    }

    /*  Subscription Orders */

    public function subscriptions()
    {
        $this->userPrivilege->canViewSubscription(UserAuthentication::getLoggedUserId());
        if (!FatApp::getConfig('CONF_ENABLE_SELLER_SUBSCRIPTION_MODULE')) {
            Message::addErrorMessage(
                Labels::getLabel('MSG_INVALID_REQUEST', $this->siteLangId)
            );
            FatApp::redirectUser(UrlHelper::generateUrl('account'));
        }
        $currentActivePlan = OrderSubscription::getUserCurrentActivePlanDetails($this->siteLangId, $this->userParentId, array(OrderSubscription::DB_TBL_PREFIX . 'till_date', OrderSubscription::DB_TBL_PREFIX . 'price', OrderSubscription::DB_TBL_PREFIX . 'type'));

        $frmSearch = $this->getSubscriptionOrderSearchForm($this->siteLangId);
        $userId = $this->userParentId;
        $autoRenew = User::getAttributesById($userId, 'user_autorenew_subscription');
        $this->set('canEdit', $this->userPrivilege->canEditSubscription(UserAuthentication::getLoggedUserId(), true));
        $this->set('currentActivePlan', $currentActivePlan);
        $this->set('autoRenew', $autoRenew);
        $this->set("frmSearch", $frmSearch);
        $this->set("keywordPlaceholder", Labels::getLabel('LBL_SEARCH_BY_ORDER_ID_/_PACKAGE_NAME', $this->siteLangId));
        $this->_template->render(true, true);
    }

    public function addCatalogPopup()
    {
        $this->_template->render(false, false);
    }

    public function sellerShippingForm($productId)
    {
        $this->userPrivilege->canEditShippingProfiles(UserAuthentication::getLoggedUserId());
        $productId = FatUtility::int($productId);
        $srch = Product::getSearchObject($this->siteLangId);
        $srch->addMultipleFields(
            array(
                'product_id',
                'product_ship_package',
                'product_seller_id',
                'product_added_by_admin_id',
                'IFNULL(product_name,product_identifier)as product_name'
            )
        );
        $srch->addCondition('product_id', '=', $productId);
        $rs = $srch->getResultSet();
        $productDetails = FatApp::getDb()->fetch($rs);

        if ($productDetails['product_seller_id'] > 0) {
            FatUtility::dieJsonError(Labels::getLabel('ERR_INVALID_REQUEST', $this->siteLangId));
        }
        $shipping_rates = array();
        $post = FatApp::getPostedData();
        $userId = $this->userParentId;

        //$shipping_rates = Products::getProductShippingRates();
        $this->set('siteLangId', $this->siteLangId);
        $shipping_rates = array();

        $shipping_rates = Product::getProductShippingRates($productId, $this->siteLangId, 0, $userId);
        $shippingDetails = Product::getProductShippingDetails($productId, $this->siteLangId, $userId);
        if (isset($shippingDetails['ps_from_country_id']) && $shippingDetails['ps_from_country_id']) {
            $shippingDetails['shipping_country'] = Countries::getCountryById($shippingDetails['ps_from_country_id'], $this->siteLangId, 'country_name');
        }
        $shippingDetails['ps_product_id'] = $productId;
        $shippingDetails['product_ship_package'] = $productDetails['product_ship_package'];
        $shippingFrm = $this->getShippingForm();

        /* [ GET ATTACHED PROFILE ID */
        $profSrch = ShippingProfileProduct::getSearchObject();
        $profSrch->addCondition('shippro_product_id', '=', $productId);
        $profSrch->addCondition('shippro_user_id', '=', $userId);
        $proRs = $profSrch->getResultSet();
        $profileData = FatApp::getDb()->fetch($proRs);
        if (!empty($profileData)) {
            $shippingDetails['shipping_profile'] = $profileData['profile_id'];
        }
        /* ] */

        $shippingFrm->fill($shippingDetails);

        $this->set('shippingFrm', $shippingFrm);

        $this->set('productDetails', $productDetails);
        $this->set('product_id', $productId);
        $this->set('shipping_rates', $shipping_rates);
        $this->_template->render(false, false);
    }

    public function getShippingForm()
    {
        $frm = new Form('frmCustomProduct');
        $fld = $frm->addTextBox(Labels::getLabel('FRM_SHIPPING_COUNTRY', $this->siteLangId), 'shipping_country');

        $shipProfileArr = ShippingProfile::getProfileArr($this->siteLangId, $this->userParentId, true, true);
        $frm->addSelectBox(Labels::getLabel('FRM_SHIPPING_PROFILE', $this->siteLangId), 'shipping_profile', $shipProfileArr, '', [])->requirements()->setRequired();

        $frm->addHiddenField('', 'ps_from_country_id');
        $frm->addHiddenField('', 'ps_product_id');
        $frm->addSubmitButton('', 'btn_submit', Labels::getLabel('BTN_SAVE_CHANGES', $this->siteLangId));
        $frm->addButton('', 'btn_cancel', Labels::getLabel('BTN_CANCEL', $this->siteLangId));
        return $frm;
    }

    public function setupSellerShipping()
    {
        $frm = $this->getShippingForm();
        $post = $frm->getFormDataFromArray(FatApp::getPostedData());

        $productShiping = FatApp::getPostedData('product_shipping');

        if (false === $post) {
            FatUtility::dieWithError(current($frm->getValidationErrors()));
        }
        $product_id = FatUtility::int($post['ps_product_id']);

        /* Validate product belongs to current logged seller[ */
        if ($product_id) {
            $productRow = Product::getAttributesById($product_id, array('product_seller_id'));
            if ($productRow['product_seller_id'] != 0) {
                FatUtility::dieWithError(Labels::getLabel('MSG_INVALID_ACCESS', $this->siteLangId));
            }
        }
        /* ] */

        unset($post['product_id']);
        unset($post['product_shipping']);

        /* Save Product Shipping  [ */
        $data_to_be_save = $post;
        $data_to_be_save['ps_product_id'] = $product_id;
        if (!Product::addUpdateProductSellerShipping($product_id, $data_to_be_save, $this->userParentId)) {
            Message::addErrorMessage(FatApp::getDb()->getError());
            FatUtility::dieWithError(Message::getHtml());
        }

        /* ] */

        if (isset($post['shipping_profile']) && $post['shipping_profile'] > 0) {
            $shipProProdData = array(
                'shippro_shipprofile_id' => $post['shipping_profile'],
                'shippro_product_id' => $product_id,
                'shippro_user_id' => $this->userParentId
            );
            $spObj = new ShippingProfileProduct();
            if (!$spObj->addProduct($shipProProdData)) {
                Message::addErrorMessage($spObj->getError());
                FatUtility::dieWithError(Message::getHtml());
            }
        }

        $this->set('msg', Labels::getLabel('MSG_SHIPPING_SETUP_SUCCESSFUL', $this->siteLangId));
        $this->set('product_id', $product_id);

        $this->_template->render(false, false, 'json-success.php');
    }

    public function toggleAutoRenewalSubscription()
    {
        $userId = $this->userParentId;
        $status = User::getAttributesById($userId, 'user_autorenew_subscription');
        if ($status) {
            $status = applicationConstants::OFF;
        } else {
            $status = applicationConstants::ON;
        }
        $dataToUpdate = array('user_autorenew_subscription' => $status);
        $record = new User($userId);
        $record->assignValues($dataToUpdate);

        if (!$record->save()) {
            FatUtility::dieJsonError(Labels::getLabel('M_Unable_to_Process_the_request,Please_try_later', $this->siteLangId));
        }
        $this->set('msg', Labels::getLabel('MSG_SETTINGS_UPDATED_SUCCESSFULLY', $this->siteLangId));
        $this->set('autoRenew', $status);
        $this->_template->render(false, false, 'json-success.php');
    }

    public function productLinks($product_id)
    {
        $this->userPrivilege->canViewProducts();
        $product_id = FatUtility::int($product_id);
        if ($product_id == 0) {
            FatUtility::dieWithError($this->str_invalid_request);
        }
        $prodCatObj = new ProductCategory();
        $arr_options = $prodCatObj->getProdCatTreeStructure(0, $this->siteLangId);
        $prodObj = new Product();
        $product_categories = $prodObj->getProductCategories($product_id);

        $this->set('selectedCats', $product_categories);
        $this->set('arr_options', $arr_options);
        $this->set('product_id', $product_id);
        $this->_template->render(false, false);
    }

    public function updateProductLink()
    {
        $this->userPrivilege->canEditProducts();
        $post = FatApp::getPostedData();
        if (false === $post) {
            Message::addErrorMessage($this->str_invalid_request);
            FatUtility::dieWithError(Message::getHtml());
        }
        $product_id = FatUtility::int($post['product_id']);
        $option_id = FatUtility::int($post['option_id']);
        if (!$product_id || !$option_id) {
            Message::addErrorMessage($this->str_invalid_request);
            FatUtility::dieWithError(Message::getHtml());
        }
        $prodObj = new Product($product_id);
        if (!$prodObj->addUpdateProductCategory($option_id)) {
            Message::addErrorMessage(Labels::getLabel($prodObj->getError(), FatApp::getConfig('CONF_ADMIN_DEFAULT_LANG', FatUtility::VAR_INT, 1)));
            FatUtility::dieWithError(Message::getHtml());
        }
        $this->set('msg', Labels::getLabel('MSG_RECORD_UPDATED_SUCCESSFULLY', $this->siteLangId));
        $this->_template->render(false, false, 'json-success.php');
    }

    public function removeProductCategory()
    {
        $post = FatApp::getPostedData();
        if (false === $post) {
            Message::addErrorMessage($this->str_invalid_request);
            FatUtility::dieWithError(Message::getHtml());
        }
        $product_id = FatUtility::int($post['product_id']);
        $option_id = FatUtility::int($post['option_id']);
        if (!$product_id || !$option_id) {
            Message::addErrorMessage($this->str_invalid_request);
            FatUtility::dieWithError(Message::getHtml());
        }
        $prodObj = new Product($product_id);
        if (!$prodObj->removeProductCategory($option_id)) {
            Message::addErrorMessage(Labels::getLabel($prodObj->getError(), FatApp::getConfig('CONF_ADMIN_DEFAULT_LANG', FatUtility::VAR_INT, 1)));
            FatUtility::dieWithError(Message::getHtml());
        }
        $this->set('msg', Labels::getLabel('MSG_Category_Removed_Successfully', $this->siteLangId));
        $this->_template->render(false, false, 'json-success.php');
    }

    private function getCustomProductForm($type = 'CUSTOM_PRODUCT', $prodcat_id = 0)
    {
        $langId = $this->siteLangId;
        $frm = new Form('frmCustomProduct');
        $fld = $frm->addRequiredField(Labels::getLabel('FRM_PRODUCT_IDENTIFIER', $langId), 'product_identifier');
        $fld->htmlAfterField = '<br/><span class="note">' . Labels::getLabel('FRM_PRODUCT_IDENTIFIER_CAN_BE_SAME_AS_OF_PRODUCT_NAME', $langId) . '</span>';
        $pTypeFld = $frm->addSelectBox(Labels::getLabel('FRM_PRODUCT_TYPE', $langId), 'product_type', Product::getProductTypes($langId), '', array('id' => 'product_type'), '');

        $fld_model = $frm->addTextBox(Labels::getLabel('FRM_MODEL', $langId), 'product_model');
        if (FatApp::getConfig("CONF_PRODUCT_MODEL_MANDATORY", FatUtility::VAR_INT, 1)) {
            $fld_model->requirements()->setRequired();
        }

        /* if($type == 'CATALOG_PRODUCT'){ */
        $frm->addRequiredField(Labels::getLabel('FRM_BRAND/Manfacturer', $this->siteLangId), 'brand_name');
        $frm->addHiddenField('', 'product_brand_id');
        /* } */

        $fld = $frm->addFloatField(Labels::getLabel('FRM_MINIMUM_SELLING_PRICE', $this->siteLangId) . ' [' . CommonHelper::getCurrencySymbol(true) . ']', 'product_min_selling_price', '');
        $fld->requirements()->setPositive();

        $taxCategories = Tax::getSaleTaxCatArr($langId);
        $frm->addSelectBox(Labels::getLabel('FRM_TAX_CATEGORY', $this->siteLangId), 'ptt_taxcat_id', $taxCategories, '', array(), Labels::getLabel('FRM_SELECT', $this->siteLangId))->requirements()->setRequired(true);

        if (FatApp::getConfig("CONF_PRODUCT_DIMENSIONS_ENABLE", FatUtility::VAR_INT, 1)) {
            /* dimension unit[ */
            $lengthUnitsArr = applicationConstants::getLengthUnitsArr($langId);
            $frm->addSelectBox(Labels::getLabel('FRM_DIMENSIONS_UNIT', $langId), 'product_dimension_unit', $lengthUnitsArr, '', array(), Labels::getLabel('FRM_SELECT', $langId))->requirements()->setRequired();
            $pDimensionUnitUnReqObj = new FormFieldRequirement('product_dimension_unit', Labels::getLabel('FRM_DIMENSIONS_UNIT', $langId));
            $pDimensionUnitUnReqObj->setRequired(false);

            $pDimensionUnitReqObj = new FormFieldRequirement('product_dimension_unit', Labels::getLabel('FRM_DIMENSIONS_UNIT', $langId));
            $pDimensionUnitReqObj->setRequired(true);
            /* ] */

            /* length [ */
            $pLengthFld = $frm->addFloatField(Labels::getLabel('FRM_LENGTH', $langId), 'product_length', '0.00');
            $pLengthUnReqObj = new FormFieldRequirement('product_length', Labels::getLabel('FRM_LENGTH', $langId));
            $pLengthUnReqObj->setRequired(false);

            $pLengthReqObj = new FormFieldRequirement('product_length', Labels::getLabel('FRM_LENGTH', $langId));
            $pLengthReqObj->setRequired(true);
            $pLengthReqObj->setFloatPositive();
            $pLengthReqObj->setRange('0.00001', '9999999999');
            /* ] */

            /* width[ */
            $pWidthFld = $frm->addFloatField(Labels::getLabel('FRM_WIDTH', $langId), 'product_width', '0.00');
            $pWidthUnReqObj = new FormFieldRequirement('product_width', Labels::getLabel('FRM_WIDTH', $langId));
            $pWidthUnReqObj->setRequired(false);

            $pWidthReqObj = new FormFieldRequirement('product_width', Labels::getLabel('FRM_WIDTH', $langId));
            $pWidthReqObj->setRequired(true);
            $pWidthReqObj->setFloatPositive();
            $pWidthReqObj->setRange('0.00001', '9999999999');
            /* ] */

            /* height[ */
            $pHeightFld = $frm->addFloatField(Labels::getLabel('FRM_HEIGHT', $langId), 'product_height', '0.00');
            $pHeightUnReqObj = new FormFieldRequirement('product_height', Labels::getLabel('FRM_HEIGHT', $langId));
            $pHeightUnReqObj->setRequired(false);

            $pHeightReqObj = new FormFieldRequirement('product_height', Labels::getLabel('FRM_HEIGHT', $langId));
            $pHeightReqObj->setRequired(true);
            $pHeightReqObj->setFloatPositive();
            $pHeightReqObj->setRange('0.00001', '9999999999');
            /* ] */
            $pTypeFld->requirements()->addOnChangerequirementUpdate(Product::PRODUCT_TYPE_DIGITAL, 'eq', 'product_length', $pLengthUnReqObj);
            $pTypeFld->requirements()->addOnChangerequirementUpdate(Product::PRODUCT_TYPE_PHYSICAL, 'eq', 'product_length', $pLengthReqObj);

            $pTypeFld->requirements()->addOnChangerequirementUpdate(Product::PRODUCT_TYPE_DIGITAL, 'eq', 'product_width', $pWidthUnReqObj);
            $pTypeFld->requirements()->addOnChangerequirementUpdate(Product::PRODUCT_TYPE_PHYSICAL, 'eq', 'product_width', $pWidthReqObj);

            $pTypeFld->requirements()->addOnChangerequirementUpdate(Product::PRODUCT_TYPE_DIGITAL, 'eq', 'product_height', $pHeightUnReqObj);
            $pTypeFld->requirements()->addOnChangerequirementUpdate(Product::PRODUCT_TYPE_PHYSICAL, 'eq', 'product_height', $pHeightReqObj);

            $pTypeFld->requirements()->addOnChangerequirementUpdate(Product::PRODUCT_TYPE_DIGITAL, 'eq', 'product_dimension_unit', $pDimensionUnitUnReqObj);
            $pTypeFld->requirements()->addOnChangerequirementUpdate(Product::PRODUCT_TYPE_PHYSICAL, 'eq', 'product_dimension_unit', $pDimensionUnitReqObj);
        }

        if (FatApp::getConfig("CONF_PRODUCT_WEIGHT_ENABLE", FatUtility::VAR_INT, 1)) {
            /* weight unit[ */
            $weightUnitsArr = applicationConstants::getWeightUnitsArr($langId);
            $pWeightUnitsFld = $frm->addSelectBox(Labels::getLabel('FRM_WEIGHT_UNIT', $langId), 'product_weight_unit', $weightUnitsArr, '', array(), Labels::getLabel('FRM_SELECT', $langId))->requirements()->setRequired();;

            $pWeightUnitUnReqObj = new FormFieldRequirement('product_weight_unit', Labels::getLabel('FRM_WEIGHT_UNIT', $langId));
            $pWeightUnitUnReqObj->setRequired(false);

            $pWeightUnitReqObj = new FormFieldRequirement('product_weight_unit', Labels::getLabel('FRM_WEIGHT_UNIT', $langId));
            $pWeightUnitReqObj->setRequired(true);
            /* ] */

            /* weight[ */
            $pWeightFld = $frm->addFloatField(Labels::getLabel('FRM_WEIGHT', $langId), 'product_weight', '0.00');
            $pWeightUnReqObj = new FormFieldRequirement('product_weight', Labels::getLabel('FRM_WEIGHT', $langId));
            $pWeightUnReqObj->setRequired(false);

            $pWeightReqObj = new FormFieldRequirement('product_weight', Labels::getLabel('FRM_WEIGHT', $langId));
            $pWeightReqObj->setRequired(true);
            /* ] */

            $pTypeFld->requirements()->addOnChangerequirementUpdate(Product::PRODUCT_TYPE_DIGITAL, 'eq', 'product_weight', $pWeightUnReqObj);
            $pTypeFld->requirements()->addOnChangerequirementUpdate(Product::PRODUCT_TYPE_PHYSICAL, 'eq', 'product_weight', $pWeightReqObj);

            $pTypeFld->requirements()->addOnChangerequirementUpdate(Product::PRODUCT_TYPE_DIGITAL, 'eq', 'product_weight_unit', $pWeightUnitUnReqObj);
            $pTypeFld->requirements()->addOnChangerequirementUpdate(Product::PRODUCT_TYPE_PHYSICAL, 'eq', 'product_weight_unit', $pWeightUnitReqObj);
        }

        /* $frm->addFloatField( Labels::getLabel('FRM_MINIMUM_SELLING_PRICE', $langId).' ['.CommonHelper::getCurrencySymbol(true).']', 'product_min_selling_price', ''); */

        $frm->addTextBox(Labels::getLabel('FRM_EAN/UPC/GTIN_code', $this->siteLangId), 'product_upc');

        $frm->addCheckBox(Labels::getLabel('FRM_PRODUCT_FEATURED', $this->siteLangId), 'product_featured', 1, array(), false, 0);

        /* $frm->addSelectBox(Labels::getLabel('FRM_SHIPPED_BY_ME',$langId), 'product_shipped_by_me', $yesNoArr, applicationConstants::YES, array(), ''); */



        $activeInactiveArr = applicationConstants::getActiveInactiveArr($langId);
        $frm->addSelectBox(Labels::getLabel('FRM_PRODUCT_STATUS', $langId), 'product_active', $activeInactiveArr, applicationConstants::ACTIVE, array(), '');

        $yesNoArr = applicationConstants::getYesNoArr($langId);
        $codFld = $frm->addSelectBox(Labels::getLabel('FRM_AVAILABLE_FOR_COD', $langId), 'product_cod_enabled', $yesNoArr, applicationConstants::NO, array(), '');
        $paymentMethod = new PaymentMethods();
        if (!$paymentMethod->cashOnDeliveryIsActive()) {
            $codFld->addFieldTagAttribute('disabled', 'disabled');
            $codFld->htmlAfterField = '<span class="note">' . Labels::getLabel('FRM_COD_OPTION_IS_DISABLED_IN_PAYMENT_GATEWAY_SETTINGS', $langId) . '</span>';
        }

        $fld = $frm->addCheckBox(Labels::getLabel('FRM_FREE_SHIPPING', $langId), 'ps_free', 1);

        $fld = $frm->addTextBox(Labels::getLabel('FRM_SHIPPING_COUNTRY', $langId), 'shipping_country');

        if ($type == 'CATALOG_PRODUCT') {
            $fld1 = $frm->addTextBox(Labels::getLabel('FRM_ADD_OPTION_GROUPS', $this->siteLangId), 'option_name');
            $fld1->htmlAfterField = '<div class=""><small> <a class="" href="javascript:void(0);" onclick="optionForm(0);">' . Labels::getLabel('FRM_ADD_NEW_OPTION', $this->siteLangId) . '</a></small></div><div class="col-md-12"><ul class="list--vertical" id="product_options_list"></ul></div>';

            $fld1 = $frm->addTextBox(Labels::getLabel('FRM_ADD_TAG', $this->siteLangId), 'tag_name');
            $fld1->htmlAfterField = '<div class=""><small><a href="javascript:void(0);" onclick="addTagForm(0);">' . Labels::getLabel('FRM_TAG_NOT_FOUND?_CLICK_HERE_TO_', $this->siteLangId) . ' ' . Labels::getLabel('FRM_ADD_NEW_TAG', $this->siteLangId) . '</a></small></div><div class="col-md-12"><ul class="list--vertical" id="product-tag-js"></ul></div>';
        }

        $fld = $frm->addTextBox(Labels::getLabel('FRM_PRODUCT_WARRANTY', $this->siteLangId), 'product_warranty');
        $fld->htmlAfterField = '<span class="form-text text-muted">' . Labels::getLabel('FRM_WARRANTY_IN_DAYS', $this->siteLangId) . ' </span>';

        $frm->addHiddenField('', 'ps_from_country_id');
        $frm->addHiddenField('', 'product_id');
        $frm->addHiddenField('', 'preq_id');
        $frm->addHiddenField('', 'product_options');
        $frm->addHiddenField('', 'preq_prodcat_id', $prodcat_id);

        $fld1 = $frm->addHtml('', 'shipping_info_html', '<div class="heading4 not-digital-js">' . Labels::getLabel('FRM_SHIPPING_INFO/CHARGES', $langId) . '</div><div class="divider not-digital-js"></div>');
        $fld2 = $frm->addHtml('', '', '<div id="tab_shipping"></div>');
        $fld1->attachField($fld2);

        $frm->addSubmitButton('', 'btn_submit', Labels::getLabel('BTN_SAVE_CHANGES', $langId));

        return $frm;
    }

    private function getSellerProductForm($product_id, $selprod_id = 0, $type = 'SELLER_PRODUCT', $inventoryCount = 1)
    {
        /* Type is used when we called this form for custom catalog request with selprod data */

        $shopDetails = Shop::getAttributesByUserId($this->userParentId, null, false);
        $defaultProductCond = '';
        $frm = new Form('frmSellerProduct');

        if ($type == 'CUSTOM_CATALOG') {
            $reqData = ProductRequest::getAttributesById($product_id, array('preq_content'));
            $productData = array_merge($reqData, json_decode($reqData['preq_content'], true));
            $productData['sellerProduct'] = 0;
            $optionArr = isset($productData['product_option']) ? $productData['product_option'] : array();
            foreach ($optionArr as $val) {
                $optionSrch = Option::getSearchObject($this->siteLangId);
                $optionSrch->addMultipleFields(array('IFNULL(option_name,option_identifier) as option_name', 'option_id'));
                $optionSrch->doNotCalculateRecords();
                $optionSrch->setPageSize(1);
                $optionSrch->addCondition('option_id', '=', $val);
                $rs = $optionSrch->getResultSet();
                $option = FatApp::getDb()->fetch($rs);
                if ($option == false) {
                    continue;
                }
                $optionValues = Product::getOptionValues($option['option_id'], $this->siteLangId);
                $option_name = ($option['option_name'] != '') ? $option['option_name'] : $option['option_identifier'];
                $fld = $frm->addSelectBox($option_name, 'selprodoption_optionvalue_id[' . $option['option_id'] . ']', $optionValues, '', array('class' => 'selprodoption_optionvalue_id'), Labels::getLabel('LBL_Select', $this->siteLangId));
                $fld->requirements()->setRequired();
            }
        } else {
            $productData = Product::getAttributesById($product_id, array('product_type', 'product_min_selling_price', 'product_cod_enabled', 'if(product_seller_id > 0, 1, 0) as sellerProduct', 'product_seller_id'));
            if ($productData['product_type'] == Product::PRODUCT_TYPE_DIGITAL) {
                $defaultProductCond = Product::CONDITION_NEW;
            }
        }

        $frm->addRequiredField(Labels::getLabel('FRM_TITLE', $this->siteLangId), 'selprod_title' . $this->siteLangId);
        if ($productData['product_type'] != Product::PRODUCT_TYPE_SERVICE) {
            if (false === Plugin::isActive('EasyEcom')) {
                $frm->addCheckBox(Labels::getLabel('FRM_SYSTEM_SHOULD_MAINTAIN_STOCK_LEVELS', $this->siteLangId), 'selprod_subtract_stock', applicationConstants::YES, array(), false, 0);
                $frm->addCheckBox(Labels::getLabel('FRM_SYSTEM_SHOULD_TRACK_PRODUCT_INVENTORY', $this->siteLangId), 'selprod_track_inventory', Product::INVENTORY_TRACK, array(), false, 0);
            }
            $fld = $frm->addTextBox(Labels::getLabel('FRM_ALERT_STOCK_LEVEL', $this->siteLangId), 'selprod_threshold_stock_level');
            $fld->requirements()->setInt();
            $fld = $frm->addIntegerField(Labels::getLabel('FRM_MINIMUM_PURCHASE_QUANTITY', $this->siteLangId), 'selprod_min_order_qty');
            $fld->requirements()->setRange(1, SellerProduct::MAX_RANGE_OF_MINIMUM_PURCHANGE_QTY);
        } else {
            $frm->addHiddenField(Labels::getLabel('FRM_MINIMUM_PURCHASE_QUANTITY', $this->siteLangId), 'selprod_min_order_qty', 1);
        }

        if ($productData['product_type'] == Product::PRODUCT_TYPE_DIGITAL) {
            $fld = $frm->addIntegerField(Labels::getLabel('FRM_MAX_DOWNLOAD_TIMES', $this->siteLangId), 'selprod_max_download_times');
            $fld->htmlAfterField = '<span class="note">' . Labels::getLabel('FRM_-1_FOR_UNLIMITED', $this->siteLangId) . '</span>';

            $fld1 = $frm->addIntegerField(Labels::getLabel('FRM_VALIDITY_(days)', $this->siteLangId), 'selprod_download_validity_in_days');
            $fld1->htmlAfterField = '<span class="note">' . Labels::getLabel('FRM_-1_FOR_UNLIMITED', $this->siteLangId) . '</span>';
            $frm->addHiddenField('', 'selprod_condition', $defaultProductCond);
        } elseif ($productData['product_type'] != Product::PRODUCT_TYPE_PHYSICAL) {
            $frm->addHiddenField('', 'selprod_condition', $defaultProductCond);
        } else {
            $fld = $frm->addSelectBox(Labels::getLabel('FRM_PRODUCT_CONDITION', $this->siteLangId), 'selprod_condition', Product::getConditionArr($this->siteLangId), $defaultProductCond, array(), Labels::getLabel('FRM_SELECT_CONDITION', $this->siteLangId));
            $fld->requirements()->setRequired();
        }

        if (0 < FatApp::getConfig('CONF_RFQ_MODULE', FatUtility::VAR_INT, 0) && 1 > FatApp::getConfig('CONF_HIDE_PRICES', FatUtility::VAR_INT, 0)) {
            if (0 < $shopDetails['shop_rfq_enabled']) {
                $cartTypeFld = $frm->addSelectBox(Labels::getLabel('FRM_CART_TYPE', $this->siteLangId), 'selprod_cart_type', SellerProduct::getCartType(), SellerProduct::CART_TYPE_BOTH, array('class' => 'fieldsVisibilityJs onlyShowHideJs'), '');
                $cartTypeFld->requirements()->setRequired();
                $frm->addCheckBox(Labels::getLabel("FRM_HIDE_PRICE", $this->siteLangId), 'selprod_hide_price', 1, array(), false, 0);

                $hidePriceReqFld = new FormFieldRequirement('selprod_hide_price', Labels::getLabel('FRM_HIDE_PRICE', $this->siteLangId));
                $hidePriceReqFld->setRequired(true);
                $hidePriceReqFld->setPositive();

                $hidePriceUnReqFld = new FormFieldRequirement('selprod_hide_price', Labels::getLabel('FRM_HIDE_PRICE', $this->siteLangId));
                $hidePriceUnReqFld->setRequired(false);
                $hidePriceUnReqFld->setPositive();

                $cartTypeFld->requirements()->addOnChangerequirementUpdate(SellerProduct::CART_TYPE_RFQ_ONLY, 'eq', 'selprod_hide_price', $hidePriceReqFld);
                $cartTypeFld->requirements()->addOnChangerequirementUpdate(SellerProduct::CART_TYPE_RFQ_ONLY, 'ne', 'selprod_hide_price', $hidePriceUnReqFld);
            }
        }

        $frm->addDateField(Labels::getLabel('FRM_DATE_AVAILABLE', $this->siteLangId), 'selprod_available_from', '', array('readonly' => 'readonly'))->requirements()->setRequired();
        $frm->addCheckBox(Labels::getLabel('FRM_PUBLISH_INVENTORY', $this->siteLangId), 'selprod_active', applicationConstants::YES, [], true, applicationConstants::NO);

        $useShopPolicy = $frm->addCheckBox(Labels::getLabel('FRM_USE_SHOP_RETURN_AND_CANCELLATION_POLICY', $this->siteLangId), 'use_shop_policy', 1, ['id' => 'use_shop_policy'], false, 0);

        $fld = $frm->addIntegerField(Labels::getLabel('FRM_PRODUCT_ORDER_RETURN_PERIOD_(Days)', $this->siteLangId), 'selprod_return_age');
        $fld->htmlAfterField = '<span class="form-text text-muted">' . Labels::getLabel('FRM_IN_DAYS', $this->siteLangId) . ' </span>';

        $orderReturnAgeReqFld = new FormFieldRequirement('selprod_return_age', Labels::getLabel('FRM_PRODUCT_ORDER_RETURN_PERIOD_(Days)', $this->siteLangId));
        $orderReturnAgeReqFld->setRequired(true);
        $orderReturnAgeReqFld->setPositive();

        $orderReturnAgeUnReqFld = new FormFieldRequirement('selprod_return_age', Labels::getLabel('FRM_PRODUCT_ORDER_RETURN_PERIOD_(Days)', $this->siteLangId));
        $orderReturnAgeUnReqFld->setRequired(false);
        $orderReturnAgeUnReqFld->setPositive();

        $fld = $frm->addIntegerField(Labels::getLabel('FRM_PRODUCT_ORDER_CANCELLATION_PERIOD_(Days)', $this->siteLangId), 'selprod_cancellation_age');
        $fld->htmlAfterField = '<span class="form-text text-muted">' . Labels::getLabel('FRM_PERIOD_IN_DAYS', $this->siteLangId) . ' </span>';

        $orderCancellationAgeReqFld = new FormFieldRequirement('selprod_cancellation_age', Labels::getLabel('FRM_PRODUCT_ORDER_CANCELLATION_PERIOD_(DAYS)', $this->siteLangId));
        $orderCancellationAgeReqFld->setRequired(true);
        $orderCancellationAgeReqFld->setPositive();
        $orderCancellationAgeUnReqFld = new FormFieldRequirement('selprod_cancellation_age', Labels::getLabel('FRM_PRODUCT_ORDER_CANCELLATION_PERIOD_(DAYS)', $this->siteLangId));
        $orderCancellationAgeUnReqFld->setRequired(false);
        $orderCancellationAgeUnReqFld->setPositive();

        $useShopPolicy->requirements()->addOnChangerequirementUpdate(Shop::USE_SHOP_POLICY, 'eq', 'selprod_return_age', $orderReturnAgeUnReqFld);
        $useShopPolicy->requirements()->addOnChangerequirementUpdate(Shop::USE_SHOP_POLICY, 'ne', 'selprod_return_age', $orderReturnAgeReqFld);

        $useShopPolicy->requirements()->addOnChangerequirementUpdate(Shop::USE_SHOP_POLICY, 'eq', 'selprod_cancellation_age', $orderCancellationAgeUnReqFld);
        $useShopPolicy->requirements()->addOnChangerequirementUpdate(Shop::USE_SHOP_POLICY, 'ne', 'selprod_cancellation_age', $orderCancellationAgeReqFld);

        if ($type != 'CUSTOM_CATALOG') {
            if ($productData['product_type'] != Product::PRODUCT_TYPE_SERVICE) {
                $yesNoArr = applicationConstants::getYesNoArr($this->siteLangId);
                $codFld = $frm->addSelectBox(Labels::getLabel('FRM_AVAILABLE_FOR_COD', $this->siteLangId), 'selprod_cod_enabled', $yesNoArr, '0', array(), '');

                $paymentMethod = new PaymentMethods();
                if (!$paymentMethod->cashOnDeliveryIsActive() || $productData['product_cod_enabled'] != applicationConstants::YES) {
                    $codFld->addFieldTagAttribute('disabled', 'disabled');
                    if ($productData['product_cod_enabled'] != applicationConstants::YES) {
                        $codFld->htmlAfterField = '<span class="note">' . Labels::getLabel('FRM_COD_OPTION_IS_DISABLED_IN_PRODUCT', $this->siteLangId) . '</span>';
                    } else {
                        $codFld->htmlAfterField = '<span class="note">' . Labels::getLabel('FRM_COD_OPTION_IS_DISABLED_IN_PAYMENT_GATEWAY_SETTINGS', $this->siteLangId) . '</span>';
                    }
                }
            }

            $shipBySeller = Product::isProductShippedBySeller($product_id, $productData['product_seller_id'], UserAuthentication::getLoggedUserId());
            // $shipBySeller = SellerProduct::prodShipByseller($product_id);

            $fulfillmentType = -1;
            if ($productData['sellerProduct'] > 0 || $shipBySeller) {
                $sellerId = ($shipBySeller > 0) ? $this->userParentId : $productData['product_seller_id'];
                $fulfillmentType = Shop::getAttributesByUserId($sellerId, 'shop_fulfillment_type');
            } else {
                $fulfillmentType = FatApp::getConfig('CONF_FULFILLMENT_TYPE', FatUtility::VAR_INT, -1);
            }

            $address = new Address(0, $this->siteLangId);
            $addresses = $address->getData(Address::TYPE_SHOP_PICKUP, $shopDetails['shop_id']);

            $fulfillmentType = empty($addresses) ? Shipping::FULFILMENT_SHIP : $fulfillmentType;

            $fulFillmentArr = Shipping::getFulFillmentArr($this->siteLangId, $fulfillmentType);
            if ($productData['product_type'] == Product::PRODUCT_TYPE_PHYSICAL && true == $shipBySeller) {
                $frm->addSelectBox(Labels::getLabel('FRM_FULFILLMENT_METHOD', $this->siteLangId), 'selprod_fulfillment_type', $fulFillmentArr, applicationConstants::NO, []);
            }
            if (0 < $selprod_id) {
                $frm->addRequiredField(Labels::getLabel('FRM_URL_KEYWORD', $this->siteLangId), 'selprod_url_keyword');
            }
            $productOptions = Product::getProductOptions($product_id, $this->siteLangId, true);
            if (!empty($productOptions) && $selprod_id == 0) {
                if (SellerProduct::INVENTORY_RESTRICT_LIMIT >= $inventoryCount) {
                    $optionCombinations = CommonHelper::combinationOfElementsOfArr($productOptions, 'optionValues', '_');
                    $i = $j = 0;
                    foreach ($optionCombinations as $optionKey => $optionValue) {
                        if (SellerProduct::UPDATE_OPTIONS_COUNT < $i) {
                            $j++;
                            $i = 0;
                        }
                        /* Check if product already added for this option [ */
                        $selProdCode = $product_id . '_' . $optionKey;
                        $selProdAvailable = Product::isSellProdAvailableForUser($selProdCode, $this->siteLangId, $this->userParentId);
                        if (!empty($selProdAvailable) && !$selProdAvailable['selprod_deleted']) {
                            continue;
                        }
                        /* ] */

                        $frm->addTextBox(Labels::getLabel('FRM_COST_PRICE', $this->siteLangId) . ' [' . CommonHelper::getCurrencySymbol(true) . ']', 'varients[' . $j . '][selprod_cost' . $optionKey . ']');
                        $frm->addTextBox(Labels::getLabel('FRM_PRICE', $this->siteLangId) . ' [' . CommonHelper::getCurrencySymbol(true) . ']', 'varients[' . $j . '][selprod_price' . $optionKey . ']');
                        $frm->addTextBox(Labels::getLabel('FRM_QUANTITY', $this->siteLangId), 'varients[' . $j . '][selprod_stock' . $optionKey . ']');
                        $frm->addTextBox(Labels::getLabel('FRM_PRODUCT_SKU', $this->siteLangId), 'varients[' . $j . '][selprod_sku' . $optionKey . ']');

                        $i++;
                    }
                }
            } else {
                $costPrice = $frm->addFloatField(Labels::getLabel('FRM_COST_PRICE', $this->siteLangId) . ' [' . CommonHelper::getCurrencySymbol(true) . ']', 'selprod_cost');
                $costPrice->requirements()->setPositive();

                $fld = $frm->addFloatField(Labels::getLabel('FRM_PRICE', $this->siteLangId) . ' [' . CommonHelper::getCurrencySymbol(true) . ']', 'selprod_price');
                $fld->requirements()->setPositive();
                if (isset($productData['product_min_selling_price'])) {
                    $fld->requirements()->setRange($productData['product_min_selling_price'], 9999999999);
                }

                $fld = $frm->addIntegerField(Labels::getLabel('FRM_QUANTITY', $this->siteLangId), 'selprod_stock');
                $fld->requirements()->setRange(1, SellerProduct::MAX_RANGE_OF_AVAILBLE_QTY);
                $fld_sku = $frm->addTextBox(Labels::getLabel('FRM_PRODUCT_SKU', $this->siteLangId), 'selprod_sku');
                if (FatApp::getConfig("CONF_PRODUCT_SKU_MANDATORY", FatUtility::VAR_INT, 1)) {
                    $fld_sku->requirements()->setRequired();
                }
            }
        }
        $frm->addTextArea(Labels::getLabel('FRM_ANY_EXTRA_COMMENT_FOR_BUYER', $this->siteLangId), 'selprod_comments' . $this->siteLangId);

        $translatorSubscriptionKey = FatApp::getConfig('CONF_TRANSLATOR_SUBSCRIPTION_KEY', FatUtility::VAR_STRING, '');
        $languages = Language::getAllNames();
        unset($languages[$this->siteLangId]);
        if (!empty($translatorSubscriptionKey) && count($languages) > 0) {
            $frm->addCheckBox(Labels::getLabel('FRM_TRANSLATE_TO_OTHER_LANGUAGES', $this->siteLangId), 'auto_update_other_langs_data', 1, array(), false, 0);
        }

        foreach ($languages as $langId => $langName) {
            $frm->addTextBox(Labels::getLabel('FRM_TITLE', $this->siteLangId), 'selprod_title' . $langId);
            $frm->addTextArea(Labels::getLabel('FRM_ANY_EXTRA_COMMENT_FOR_BUYER', $this->siteLangId), 'selprod_comments' . $langId);
        }
        $frm->addHiddenField('', 'selprod_product_id', $product_id);
        $frm->addHiddenField('', 'selprod_urlrewrite_id');
        $frm->addHiddenField('', 'selprod_id', $selprod_id);
        $fld1 = $frm->addSubmitButton('', 'btn_submit', Labels::getLabel('BTN_SAVE_CHANGES', $this->siteLangId));
        if ($type != 'CUSTOM_CATALOG') {
            $fld2 = $frm->addButton('', 'btn_cancel', Labels::getLabel('BTN_DISCARD', $this->siteLangId), array('onclick' => 'gotToProucts()'));
            //$fld1->attachField($fld2);
        }
        return $frm;
    }

    public function catalogInfo(int $product_id)
    {
        $prodSrchObj = new ProductSearch($this->siteLangId, null, null, false, false);
        /* fetch requested product[ */
        $prodSrch = clone $prodSrchObj;
        $prodSrch->joinProductToCategory(0, false, false, false);
        //$prodSrch->joinProductToTax();
        $prodSrch->joinBrands(0, false, false, false);
        $prodSrch->addCondition('product_id', '=', $product_id);
        $prodSrch->doNotLimitRecords();

        $prodSrch->addMultipleFields(
            array(
                'product_id',
                'product_identifier',
                'IFNULL(product_name,product_identifier) as product_name',
                'product_seller_id',
                'product_model',
                'product_type',
                'product_short_description',
                'prodcat_id',
                'IFNULL(prodcat_name,prodcat_identifier) as prodcat_name',
                'brand_id',
                'IFNULL(brand_name, brand_identifier) as brand_name',
                'product_min_selling_price'
            )
        );
        $productRs = $prodSrch->getResultSet();
        $product = FatApp::getDb()->fetch($productRs);
        /* ] */

        $taxData = Tax::getTaxCatByProductId($product_id, $this->userParentId, $this->siteLangId, array('ptt_taxcat_id'));
        if (!empty($taxData)) {
            $product = array_merge($product, $taxData);
        }

        if (!$product) {
            Message::addErrorMessage(Labels::getLabel('VLBL_INVALID_PRODUCT', $this->siteLangId));
            FatUtility::dieWithError(Message::getHtml());
        }

        /* Get Product Specifications [ */
        $specSrchObj = clone $prodSrchObj;
        $specSrchObj->doNotCalculateRecords();
        $specSrchObj->doNotLimitRecords();
        $specSrchObj->joinTable(Product::DB_PRODUCT_SPECIFICATION, 'LEFT OUTER JOIN', 'product_id = tcps.prodspec_product_id', 'tcps');
        $specSrchObj->joinTable(Product::DB_PRODUCT_LANG_SPECIFICATION, 'INNER JOIN', 'tcps.prodspec_id = tcpsl.prodspeclang_prodspec_id and   prodspeclang_lang_id  = ' . $this->siteLangId, 'tcpsl');
        $specSrchObj->addMultipleFields(array('prodspec_id', 'prodspec_name', 'prodspec_value'));
        $specSrchObj->addGroupBy('prodspec_id');
        $specSrchObj->addCondition('prodspec_product_id', '=', $product['product_id']);
        $specSrchObjRs = $specSrchObj->getResultSet();
        $productSpecifications = FatApp::getDb()->fetchAll($specSrchObjRs);

        $productImagesArr = AttachedFile::getMultipleAttachments(AttachedFile::FILETYPE_PRODUCT_IMAGE, $product_id, 0, $this->siteLangId);
        /* ] */

        $this->set('productImagesArr', $productImagesArr);
        $this->set('product', $product);
        $this->set('productSpecifications', $productSpecifications);
        $this->_template->render(false, false);
    }

    public function returnAddress()
    {
        $userId = $this->userParentId;
        $userObj = new User($userId);
        $data = $userObj->getUserReturnAddress($this->siteLangId);
        $this->set('info', $data);
        $this->_template->render(false, false);
    }

    public function getReturnAddress()
    {
        $userId = $this->userParentId;
        $userObj = new User($userId);
        $addressData = $userObj->getUserReturnAddress($this->siteLangId);
        $shopDetails = Shop::getAttributesByUserId($userId, null, false);

        if (!false == $shopDetails && $shopDetails['shop_active'] != applicationConstants::ACTIVE) {
            Message::addErrorMessage(Labels::getLabel('ERR_YOUR_SHOP_DEACTIVATED_CONTACT_ADMIN', $this->siteLangId));
            FatUtility::dieWithError(Message::getHtml());
        }

        $this->set('addressData', (array)$addressData);
        $this->set('siteLangId', $this->siteLangId);
        $this->_template->render(false, false);
    }

    public function returnAddressForm()
    {
        $userId = $this->userParentId;

        $frm = $this->getReturnAddressForm();
        $stateId = 0;

        $userObj = new User($userId);
        $addressData = $userObj->getUserReturnAddress($this->siteLangId);

        if ($addressData != false) {
            $frm->fill($addressData);
            $stateId = $addressData['ura_state_id'];
        }
        $shopDetails = Shop::getAttributesByUserId($userId, null, false);

        if (!false == $shopDetails && $shopDetails['shop_active'] != applicationConstants::ACTIVE) {
            Message::addErrorMessage(Labels::getLabel('ERR_YOUR_SHOP_DEACTIVATED_CONTACT_ADMIN', $this->siteLangId));
            FatUtility::dieWithError(Message::getHtml());
        }

        $this->set('siteLangId', $this->siteLangId);
        $this->set('frm', $frm);
        $this->set('stateId', $stateId);
        $this->set('languages', Language::getAllNames());
        $this->_template->render(false, false);
    }

    public function setReturnAddress()
    {
        $this->userPrivilege->canEditShop(UserAuthentication::getLoggedUserId());
        $userId = $this->userParentId;

        $post = FatApp::getPostedData();
        $ura_state_id = FatUtility::int($post['ura_state_id']);
        $frm = $this->getReturnAddressForm();
        $post = $frm->getFormDataFromArray($post);
        if (false === $post) {
            FatUtility::dieJsonError(current($frm->getValidationErrors()));
        }

        $post['ura_state_id'] = $ura_state_id;
        $post['ura_phone_dcode'] = FatApp::getPostedData('ura_phone_dcode', FatUtility::VAR_STRING, '');

        $userObj = new User($userId);
        if (!$userObj->updateUserReturnAddress($post)) {
            FatUtility::dieJsonError(Labels::getLabel($userObj->getError(), $this->siteLangId));
        }

        $post['lang_id'] = CommonHelper::getDefaultFormLangId();

        if (!$userObj->updateUserReturnAddressLang($post)) {
            FatUtility::dieJsonError(Labels::getLabel($userObj->getError(), $this->siteLangId));
        }

        $autoUpdateOtherLangsData = FatApp::getPostedData('auto_update_other_langs_data', FatUtility::VAR_INT, 0);
        if (0 < $autoUpdateOtherLangsData) {
            $updateLangDataobj = new TranslateLangData($userObj::DB_TBL_USR_RETURN_ADDR_LANG);
            if (false === $updateLangDataobj->updateTranslatedData($userId, CommonHelper::getDefaultFormLangId())) {
                Message::addErrorMessage($updateLangDataobj->getError());
                FatUtility::dieWithError(Message::getHtml());
            }
        }

        $newTabLangId = 0;
        $languages = Language::getDropDownList(CommonHelper::getDefaultFormLangId());
        if (0 < count($languages)) {
            foreach ($languages as $languageId => $langName) {
                $langData = $userObj->getUserReturnAddress($languageId);
                if (empty($langData['uralang_lang_id'])) {
                    $newTabLangId = $languageId;
                    break;
                }
            }
        }

        $shipping = new Shipping($this->siteLangId);
        $shippingService = $shipping->getShippingApiObj();
        if (false !== $shippingService) {
            if (method_exists($shippingService, 'setShopSellerId') && method_exists($shippingService, 'updateWarehouse')) {
                $shippingService->setShopSellerId($userId);
                $shippingService->updateWarehouse();
            }
        }

        $this->set('langId', $newTabLangId);
        $this->set('msg', Labels::getLabel('MSG_SETUP_SUCCESSFULLY', $this->siteLangId));
        $this->_template->render(false, false, 'json-success.php');
    }

    public function deleteReturnAddress()
    {
        $this->userPrivilege->canEditShop(UserAuthentication::getLoggedUserId());
        $userId = $this->userParentId;
        $userObj = new User($userId);
        if (false === $userObj->deleteUserReturnAddress()) {
            FatUtility::dieJsonError(Labels::getLabel($userObj->getError(), $this->siteLangId));
        }
        $this->set('msg', Labels::getLabel('MSG_DELETED_SUCCESSFULLY', $this->siteLangId));
        $this->_template->render(false, false, 'json-success.php');
    }

    public function returnAddressLangForm($langId, $autoFillLangData = 0)
    {
        $langId = FatUtility::int($langId);
        $userId = $this->userParentId;
        $userId = FatUtility::int($userId);

        if (1 > $langId || 1 > $userId) {
            FatUtility::dieJsonError(Labels::getLabel('ERR_INVALID_ACCESS', $this->siteLangId));
        }

        $frm = $this->getReturnAddressLangForm($langId);
        $stateId = 0;

        if (0 < $autoFillLangData) {
            $updateLangDataobj = new TranslateLangData(User::DB_TBL_USR_RETURN_ADDR_LANG);
            $translatedData = $updateLangDataobj->getTranslatedData($userId, $langId, CommonHelper::getDefaultFormLangId());
            if (false === $translatedData) {
                Message::addErrorMessage($updateLangDataobj->getError());
                FatUtility::dieWithError(Message::getHtml());
            }
            $data = current($translatedData);
        } else {
            $userObj = new User($userId);
            $data = $userObj->getUserReturnAddress($langId);
        }

        if ($data != false) {
            $frm->fill($data);
        }
        $shopDetails = Shop::getAttributesByUserId($userId, null, false);

        if (!false == $shopDetails && $shopDetails['shop_active'] != applicationConstants::ACTIVE) {
            Message::addErrorMessage(Labels::getLabel('ERR_YOUR_SHOP_DEACTIVATED_CONTACT_ADMIN', $this->siteLangId));
            FatUtility::dieWithError(Message::getHtml());
        }

        $shop_id = 0;

        if (!false == $shopDetails) {
            $shop_id = $shopDetails['shop_id'];
        }

        $this->set('shop_id', $shop_id);
        $this->set('languages', Language::getAllNames());
        $this->set('frm', $frm);
        $this->set('stateId', $stateId);
        $this->set('formLangId', $langId);
        $this->set('formLayout', Language::getLayoutDirection($langId));
        $this->_template->render(false, false);
    }

    public function setReturnAddressLang()
    {
        $this->userPrivilege->canEditShop(UserAuthentication::getLoggedUserId());
        $post = FatApp::getPostedData();

        $languages = Language::getAllNames();
        if (count($languages) > 1) {
            $lang_id = $post['lang_id'];
        } else {
            $lang_id = array_key_first($languages);
            $post['lang_id'] = array_key_first($languages);
        }

        $userId = $this->userParentId;

        if ($userId == 0 || $lang_id == 0) {
            Message::addErrorMessage(Labels::getLabel('ERR_INVALID_ACCESS', $this->siteLangId));
            FatUtility::dieWithError(Message::getHtml());
        }

        $frm = $this->getReturnAddressLangForm($lang_id);
        $post = $frm->getFormDataFromArray($post);

        if (false === $post) {
            FatUtility::dieJsonError(current($frm->getValidationErrors()));
        }

        $userObj = new User($userId);
        if (!$userObj->updateUserReturnAddressLang($post)) {
            FatUtility::dieJsonError(Labels::getLabel($userObj->getError(), $this->siteLangId));
        }

        $shipping = new Shipping($this->siteLangId);
        $shippingService = $shipping->getShippingApiObj();
        if (false !== $shippingService) {
            if (method_exists($shippingService, 'setShopSellerId') && method_exists($shippingService, 'updateWarehouse')) {
                $shippingService->setShopSellerId($userId);
                $shippingService->updateWarehouse();
            }
        }

        $autoUpdateOtherLangsData = FatApp::getPostedData('auto_update_other_langs_data', FatUtility::VAR_INT, 0);
        if (0 < $autoUpdateOtherLangsData) {
            $updateLangDataobj = new TranslateLangData(User::DB_TBL_USR_RETURN_ADDR_LANG);
            if (false === $updateLangDataobj->updateTranslatedData($userId)) {
                Message::addErrorMessage($updateLangDataobj->getError());
                FatUtility::dieWithError(Message::getHtml());
            }
        }

        $newTabLangId = 0;
        $languages = Language::getAllNames();
        foreach ($languages as $langId => $langName) {
            $userObj = new User($userId);
            $srch = new SearchBase(User::DB_TBL_USR_RETURN_ADDR_LANG);
            $srch->addCondition('uralang_user_id', '=', $userId);
            $srch->addCondition('uralang_lang_id', '=', $langId);
            $srch->doNotCalculateRecords();
            $srch->doNotLimitRecords();
            $rs = $srch->getResultSet();
            $vendorReturnAddress = FatApp::getDb()->fetch($rs);

            if (!$vendorReturnAddress) {
                $newTabLangId = $langId;
                break;
            }
        }

        $this->set('langId', $newTabLangId);
        $this->set('msg', Labels::getLabel('MSG_Setup_successful', $this->siteLangId));
        $this->_template->render(false, false, 'json-success.php');
    }

    private function getReturnAddressForm()
    {
        $frm = new Form('frmReturnAddress');

        $countryObj = new Countries();
        $countriesArr = $countryObj->getCountriesAssocArr($this->siteLangId);

        $frm->addTextBox(Labels::getLabel('FRM_NAME', $this->siteLangId), 'ura_name')->requirement->setRequired(true);;
        $frm->addTextBox(Labels::getLabel('FRM_CITY', $this->siteLangId), 'ura_city')->requirement->setRequired(true);;
        $frm->addTextBox(Labels::getLabel('FRM_ADDRESS1', $this->siteLangId), 'ura_address_line_1')->requirement->setRequired(true);;
        $frm->addTextBox(Labels::getLabel('FRM_ADDRESS2', $this->siteLangId), 'ura_address_line_2');

        $fld = $frm->addSelectBox(Labels::getLabel('FRM_COUNTRY', $this->siteLangId), 'ura_country_id', $countriesArr, FatApp::getConfig('CONF_COUNTRY'), array(), Labels::getLabel('FRM_SELECT', $this->siteLangId));
        $fld->requirement->setRequired(true);

        $frm->addSelectBox(Labels::getLabel('FRM_STATE', $this->siteLangId), 'ura_state_id', array(), '', array(), Labels::getLabel('FRM_SELECT', $this->siteLangId))->requirement->setRequired(true);
        $frm->addTextBox(Labels::getLabel('FRM_POSTALCODE', $this->siteLangId), 'ura_zip');

        $frm->addHiddenField('', 'ura_phone_dcode');
        $phnFld = $frm->addTextBox(Labels::getLabel('FRM_PHONE', $this->siteLangId), 'ura_phone', '', array('class' => 'phone-js ltr-right', 'placeholder' => ValidateElement::PHONE_NO_FORMAT, 'maxlength' => ValidateElement::PHONE_NO_LENGTH));
        $phnFld->requirements()->setRegularExpressionToValidate(ValidateElement::PHONE_REGEX);

        $phnFld->requirements()->setCustomErrorMessage(Labels::getLabel('FRM_PLEASE_ENTER_VALID_PHONE_NUMBER_FORMAT.', $this->siteLangId));

        $languageArr = Language::getDropDownList();
        $translatorSubscriptionKey = FatApp::getConfig('CONF_TRANSLATOR_SUBSCRIPTION_KEY', FatUtility::VAR_STRING, '');
        if (!empty($translatorSubscriptionKey) && 1 < count($languageArr)) {
            $frm->addCheckBox(Labels::getLabel('FRM_UPDATE_OTHER_LANGUAGES_DATA', $this->siteLangId), 'auto_update_other_langs_data', 1, array(), false, 0);
        }


        return $frm;
    }

    private function getReturnAddressLangForm($formLangId)
    {
        $formLangId = FatUtility::int($formLangId);

        $frm = new Form('frmReturnAddressLang');
        $fld = $frm->addSelectBox(Labels::getLabel('FRM_LANGUAGE', $formLangId), 'lang_id', Language::getDropDownList(CommonHelper::getDefaultFormLangId()), $formLangId, array(), '');
        $fld->requirements()->setRequired();
        $fld->requirements()->setInt();
        $frm->addTextBox(Labels::getLabel('FRM_NAME', $formLangId), 'ura_name')->requirement->setRequired(true);
        $frm->addTextBox(Labels::getLabel('FRM_ADDRESS1', $formLangId), 'ura_address_line_1')->requirement->setRequired(true);;
        $frm->addTextBox(Labels::getLabel('FRM_ADDRESS2', $formLangId), 'ura_address_line_2');
        $frm->addTextBox(Labels::getLabel('FRM_CITY', $formLangId), 'ura_city')->requirement->setRequired(true);

        return $frm;
    }

    public function sellerOffers()
    {
        $this->userPrivilege->canViewSubscription(UserAuthentication::getLoggedUserId());
        $this->_template->render(true, true);
    }

    public function searchSellerOffers()
    {
        $offers = DiscountCoupons::getUserCoupons($this->userParentId, $this->siteLangId, DiscountCoupons::TYPE_SELLER_PACKAGE);

        if ($offers) {
            $couponIds = array_column($offers, 'coupon_id');
            $plans = DiscountCoupons::getCouponPlansByCouponIds($couponIds, $this->siteLangId);
            foreach ($offers as &$coupon) {
                if (!empty($plans)) {
                    foreach ($plans as $plan) {
                        if ($plan['ctplan_coupon_id'] == $coupon['coupon_id']) {
                            $coupon['plans'][$plan['spackage_id']]['plan_name'] = empty($plan['spackage_name']) ? $plan['spackage_identifier'] : $plan['spackage_name'];
                            $coupon['plans'][$plan['spackage_id']]['plans'][] = $plan;
                        }
                    }
                }
            }
            $this->set('offers', $offers);
        } else {
            $this->set('noRecordsHtml', $this->_template->render(false, false, '_partial/no-record-found.php', true));
        }
        $this->_template->render(false, false);
    }

    public function productTooltipInstruction($type)
    {
        $this->set('type', $type);
        $this->_template->render(false, false);
    }

    public function addSpecialPriceForm()
    {
        $this->set('frm', SellerProduct::specialPriceForm($this->siteLangId));
        $this->set('includeTabs', false);
        $this->set('formTitle', Labels::getLabel('LBL_BIND_SPECIAL_PRICE', $this->siteLangId));
        $this->set('html', $this->_template->render(false, false, NULL, true));
        $this->_template->render(false, false, 'json-success.php', true, false);
    }

    public function specialPrice($selProd_id = 0)
    {
        $this->userPrivilege->canViewSpecialPrice(UserAuthentication::getLoggedUserId());
        if (!UserPrivilege::isUserHasValidSubsription($this->userParentId)) {
            Message::addInfo(Labels::getLabel("MSG_Please_buy_subscription", $this->siteLangId));
            FatApp::redirectUser(UrlHelper::generateUrl('Seller', 'Packages'));
        }

        $selProd_id = FatUtility::int($selProd_id);

        if (0 < $selProd_id || 0 > $selProd_id) {
            $selProd_id = SellerProduct::getAttributesByID($selProd_id, 'selprod_id', false);
            if (empty($selProd_id)) {
                Message::addErrorMessage(Labels::getLabel('ERR_INVALID_REQUEST', $this->siteLangId));
                FatApp::redirectUser(UrlHelper::generateUrl('SellerProducts', 'specialPrice'));
            }
        }

        $srchFrm = $this->getSpecialPriceSearchForm();
        $selProdIdsArr = FatApp::getPostedData('selprod_ids', FatUtility::VAR_INT, 0);

        $dataToEdit = array();
        if (!empty($selProdIdsArr) || 0 < $selProd_id) {
            $selProdIdsArr = (0 < $selProd_id) ? array($selProd_id) : $selProdIdsArr;
            $productsTitle = SellerProduct::getProductDisplayTitle($selProdIdsArr, $this->siteLangId);
            foreach ($selProdIdsArr as $selProdId) {
                $dataToEdit[] = array(
                    'product_name' => html_entity_decode($productsTitle[$selProdId], ENT_QUOTES, 'UTF-8'),
                    'splprice_selprod_id' => $selProdId
                );
            }
        } else {
            $post = $srchFrm->getFormDataFromArray(FatApp::getPostedData());

            if (false === $post) {
                FatUtility::dieJsonError(current($srchFrm->getValidationErrors()));
            } else {
                unset($post['btn_submit'], $post['btn_clear']);
                $srchFrm->fill($post);
            }
        }
        if (0 < $selProd_id) {
            $srchFrm->addHiddenField('', 'selprod_id', $selProd_id);
            $srchFrm->fill(array('keyword' => $productsTitle[$selProd_id]));
        }
        $this->set("canEdit", $this->userPrivilege->canEditSpecialPrice(UserAuthentication::getLoggedUserId(), true));
        $this->set("dataToEdit", $dataToEdit);
        $this->set("frmSearch", $srchFrm);
        $this->set("selProd_id", $selProd_id);
        $this->set("keywordPlaceholder", Labels::getLabel('LBL_SEARCH_BY_PRODUCT_NAME', $this->siteLangId));
        $this->set('deleteButton', true);
        $this->_template->addJs(array('js/select2.js'));
        $this->_template->addCss(array('css/select2.min.css'));
        $this->_template->render();
    }

    public function searchSpecialPriceProducts()
    {
        $this->userPrivilege->canViewSpecialPrice(UserAuthentication::getLoggedUserId());
        $userId = $this->userParentId;
        $post = FatApp::getPostedData();
        $page = FatApp::getPostedData('page', FatUtility::VAR_INT, 1);
        $selProdId = FatApp::getPostedData('selprod_id', FatUtility::VAR_INT, 0);
        $keyword = FatApp::getPostedData('keyword', FatUtility::VAR_STRING, '');
        $pagesize = FatApp::getConfig('CONF_PAGE_SIZE', FatUtility::VAR_INT, 10);
        $srch = SellerProduct::searchSpecialPriceProductsObj($this->siteLangId, $selProdId, $keyword, $userId);
        $this->setRecordCount(clone $srch, $pagesize, $page, $post);
        $srch->doNotCalculateRecords();
        $srch->addMultipleFields(
            array(
                'selprod_id',
                'credential_username',
                'selprod_price',
                'date(splprice_start_date) as splprice_start_date',
                'splprice_end_date',
                'IFNULL(product_name, product_identifier) as product_name',
                'selprod_title',
                'splprice_id',
                'splprice_price',
                'selprod_product_id',
                'product_updated_on',
                'user_id',
                'user_updated_on',
                'credential_email',
                'user_name'
            )
        );
        $srch->addOrder('splprice_id', 'DESC');
        $srch->setPageNumber($page);
        $srch->setPageSize($pagesize);
        $arrListing = FatApp::getDb()->fetchAll($srch->getResultSet());
        if (count($arrListing)) {
            foreach ($arrListing as &$arr) {
                $arr['options'] = SellerProduct::getSellerProductOptions($arr['selprod_id'], true, $this->siteLangId);
            }
        }
        $this->set("arrListing", $arrListing);
        $this->set('canEdit', $this->userPrivilege->canEditSpecialPrice(UserAuthentication::getLoggedUserId(), true));
        $this->set('postedData', $post);
        $this->set('pageSize', $pagesize);
        $this->_template->render(false, false);
    }

    private function getSpecialPriceSearchForm()
    {
        $frm = new Form('frmSearch', array('id' => 'frmSearch'));
        $frm->addHiddenField('', 'total_record_count');
        $frm->addTextBox('', 'keyword', '', array('placeholder' => Labels::getLabel('FRM_KEYWORD', $this->siteLangId)));

        HtmlHelper::addSearchButton($frm);
        return $frm;
    }

    public function updateSpecialPriceRow()
    {
        $this->userPrivilege->canEditSpecialPrice(UserAuthentication::getLoggedUserId());
        $post = FatApp::getPostedData();
        if (empty($post)) {
            FatUtility::dieJsonError(Labels::getLabel('ERR_INVALID_REQUEST', $this->siteLangId));
        }

        /* $selprodPrice = SellerProduct::getAttributesById($post['splprice_selprod_id'], 'selprod_price');
        if ($selprodPrice < $splPrice) {
            FatUtility::dieJsonError(Labels::getLabel('ERR_SPECIAL_PRICE_MUST_BE_LESS_THAN_EQUAL_TO_CURRENT_PRICE', $this->siteLangId));
        } */

        $splPriceId = $this->updateSelProdSplPrice($post, true);
        if (!$splPriceId) {
            FatUtility::dieJsonError(Labels::getLabel('ERR_INVALID_REQUEST', $this->siteLangId));
        }
        // last Param of getProductDisplayTitle function used to get title in html form.
        $productName = SellerProduct::getProductDisplayTitle($post['splprice_selprod_id'], $this->siteLangId, true);
        $post['product_name'] = $productName;
        $this->set('data', $post);
        $this->set('splPriceId', $splPriceId);
        $json = array(
            'status' => true,
            'msg' => Labels::getLabel('LBL_Special_Price_Setup_Successful', $this->siteLangId),
            'data' => $this->_template->render(false, false, 'seller/update-special-price-row.php', true)
        );

        $productId = SellerProduct::getAttributesById($post['splprice_selprod_id'], 'selprod_product_id');
        Product::updateMinPrices($productId);
        FatUtility::dieJsonSuccess($json);
    }

    private function updateSelProdSplPrice($post, $return = false)
    {
        $this->userPrivilege->canEditSpecialPrice(UserAuthentication::getLoggedUserId());
        $userId = $this->userParentId;
        $selprod_id = !empty($post['splprice_selprod_id']) ? FatUtility::int($post['splprice_selprod_id']) : 0;
        $splprice_id = !empty($post['splprice_id']) ? FatUtility::int($post['splprice_id']) : 0;

        if (1 > $selprod_id) {
            FatUtility::dieJsonError(Labels::getLabel('ERR_INVALID_REQUEST', $this->siteLangId));
        }

        if (strtotime($post['splprice_start_date']) > strtotime($post['splprice_end_date'])) {
            FatUtility::dieJsonError(Labels::getLabel('ERR_INVALID_DATES', $this->siteLangId));
        }

        $prodSrch = new ProductSearch($this->siteLangId);
        $prodSrch->joinSellerProducts($userId, '', array(), false);
        $prodSrch->addCondition('selprod_id', '=', $selprod_id);
        $prodSrch->addMultipleFields(array('product_min_selling_price', 'selprod_price', 'selprod_available_from'));
        $prodSrch->setPageSize(1);
        $rs = $prodSrch->getResultSet();
        $product = FatApp::getDb()->fetch($rs);

        if (strtotime($post['splprice_start_date']) < strtotime($product['selprod_available_from'])) {
            $str = Labels::getLabel('ERR_SPECIAL_PRICE_DATE_MUST_BE_GREATER_OR_THAN_EQUAL_TO_{AVAILABLEFROM}', $this->siteLangId);
            $message = CommonHelper::replaceStringData($str, array('{AVAILABLEFROM}' => date('Y-m-d', strtotime($product['selprod_available_from']))));
            FatUtility::dieJsonError($message);
        }

        /* if (!isset($post['splprice_price']) || $post['splprice_price'] < $product['product_min_selling_price'] || $post['splprice_price'] > $product['selprod_price']) {
            $str = Labels::getLabel('ERR_PRICE_MUST_BETWEEN_MIN_SELLING_PRICE_{MINSELLINGPRICE}_AND_SELLING_PRICE_{SELLINGPRICE}', $this->siteLangId);
            $minSellingPrice = CommonHelper::displayMoneyFormat($product['product_min_selling_price'], false, true, true);
            $sellingPrice = CommonHelper::displayMoneyFormat($product['selprod_price'], false, true, true);

            $message = CommonHelper::replaceStringData($str, array('{MINSELLINGPRICE}' => $minSellingPrice, '{SELLINGPRICE}' => $sellingPrice));
            FatUtility::dieJsonError($message);
        } */


        /* Check if same date already exists [ */
        $tblRecord = new TableRecord(SellerProduct::DB_TBL_SELLER_PROD_SPCL_PRICE);

        $smt = 'splprice_selprod_id = ? AND ';
        $smt .= '(
            ((splprice_start_date between ? AND ?) OR (splprice_end_date between ? AND ?))
            OR
            ((? BETWEEN splprice_start_date AND splprice_end_date) OR (? BETWEEN  splprice_start_date AND splprice_end_date))
        )';
        $smtValues = array(
            $selprod_id,
            $post['splprice_start_date'],
            $post['splprice_end_date'],
            $post['splprice_start_date'],
            $post['splprice_end_date'],
            $post['splprice_start_date'],
            $post['splprice_end_date'],
        );

        if (0 < $splprice_id) {
            $smt .= ' AND splprice_id != ?';
            $smtValues[] = $splprice_id;
        }
        $condition = array(
            'smt' => $smt,
            'vals' => $smtValues
        );

        if ($tblRecord->loadFromDb($condition)) {
            $specialPriceRow = $tblRecord->getFlds();
            if ($specialPriceRow['splprice_id'] != $splprice_id) {
                FatUtility::dieJsonError(Labels::getLabel('ERR_SPECIAL_PRICE_FOR_THIS_DATE_ALREADY_ADDED', $this->siteLangId));
            }
        }
        /* ] */

        $data_to_save = array(
            'splprice_selprod_id' => $selprod_id,
            'splprice_start_date' => $post['splprice_start_date'],
            'splprice_end_date' => $post['splprice_end_date'],
            'splprice_price' => $post['splprice_price'],
        );

        if (0 < $splprice_id) {
            $data_to_save['splprice_id'] = $splprice_id;
        }

        $sellerProdObj = new SellerProduct();

        // Return Special Price ID if $return is true else it will return bool value.
        $splPriceId = $sellerProdObj->addUpdateSellerProductSpecialPrice($data_to_save, $return);
        if (false === $splPriceId) {
            FatUtility::dieJsonError(Labels::getLabel($sellerProdObj->getError(), $this->siteLangId));
        }

        return $splPriceId;
    }

    public function updateSpecialPriceColValue()
    {
        $this->userPrivilege->canEditSpecialPrice(UserAuthentication::getLoggedUserId());
        $splPriceId = FatApp::getPostedData('splprice_id', FatUtility::VAR_INT, 0);
        if (1 > $splPriceId) {
            FatUtility::dieJsonError(Labels::getLabel('ERR_INVALID_REQUEST', $this->siteLangId));
        }

        $attribute = FatApp::getPostedData('attribute', FatUtility::VAR_STRING, '');

        $columns = array('splprice_start_date', 'splprice_end_date', 'splprice_price');
        if (!in_array($attribute, $columns)) {
            FatUtility::dieJsonError(Labels::getLabel('ERR_INVALID_REQUEST', $this->siteLangId));
        }

        $otherColumns = array_values(array_diff($columns, [$attribute]));

        $otherColumnsValue = SellerProductSpecialPrice::getAttributesById($splPriceId, $otherColumns);
        if (empty($otherColumnsValue)) {
            FatUtility::dieJsonError(Labels::getLabel('ERR_INVALID_REQUEST', $this->siteLangId));
        }
        $value = FatApp::getPostedData('value');
        $selProdId = FatApp::getPostedData('selProdId', FatUtility::VAR_INT, 0);

        /* $selprodPrice = SellerProduct::getAttributesById($selProdId, 'selprod_price');
        if ($selprodPrice < $value && 'splprice_price' == $attribute) {
            FatUtility::dieJsonError(Labels::getLabel('ERR_SPECIAL_PRICE_MUST_BE_LESS_THAN_EQUAL_TO_ORIGNAL_PRICE', $this->siteLangId));
        } */

        $dataToUpdate = array(
            'splprice_selprod_id' => $selProdId,
            'splprice_id' => $splPriceId,
            $attribute => $value,
        );

        $dataToUpdate += $otherColumnsValue;

        if (!$this->updateSelProdSplPrice($dataToUpdate)) {
            FatUtility::dieJsonError(Labels::getLabel('ERR_SOMETHING_WENT_WRONG._PLEASE_TRY_AGAIN.', $this->siteLangId));
        }

        if ('splprice_price' == $attribute) {
            $value = CommonHelper::displayMoneyFormat($value, true, true);
        }

        Product::updateMinPrices(SellerProduct::getAttributesById($selProdId, 'selprod_product_id'));

        $json = array(
            'status' => true,
            'msg' => $this->str_update_record,
            'data' => array('value' => $value)
        );
        FatUtility::dieJsonSuccess($json);
    }

    public function deleteSellerProductSpecialPrice()
    {
        $this->userPrivilege->canEditSpecialPrice(UserAuthentication::getLoggedUserId());
        $splPriceId = FatApp::getPostedData('splprice_id', FatUtility::VAR_INT, 0);
        if (1 > $splPriceId) {
            FatUtility::dieWithError(Labels::getLabel('MSG_INVALID_REQUEST', $this->siteLangId));
        }
        $specialPriceRow = SellerProduct::getSellerProductSpecialPriceById($splPriceId);
        if (empty($specialPriceRow) || 1 > count($specialPriceRow)) {
            FatUtility::dieWithError(Labels::getLabel('MSG_Already_Deleted', $this->siteLangId));
        }
        $this->deleteSpecialPrice($splPriceId, $specialPriceRow['selprod_id']);
        $this->set('selprod_id', $specialPriceRow['selprod_id']);
        $this->set('msg', Labels::getLabel('MSG_SPECIAL_PRICE_RECORD_DELETED', $this->siteLangId));
        $this->_template->render(false, false, 'json-success.php');
    }

    public function deleteSpecialPriceRows()
    {
        $this->userPrivilege->canEditSpecialPrice(UserAuthentication::getLoggedUserId());
        $splpriceIdArr = FatApp::getPostedData('selprod_ids');
        $splpriceIds = FatUtility::int($splpriceIdArr);
        foreach ($splpriceIds as $splPriceId => $selProdId) {
            $specialPriceRow = SellerProduct::getSellerProductSpecialPriceById($splPriceId);
            $this->deleteSpecialPrice($splPriceId, $specialPriceRow['selprod_id']);
        }
        $this->set('selprod_id', $specialPriceRow['selprod_id']);
        $this->set('msg', Labels::getLabel('MSG_SPECIAL_PRICE_RECORD_DELETED', $this->siteLangId));
        $this->_template->render(false, false, 'json-success.php');
    }

    private function deleteSpecialPrice($splPriceId, $selProdId)
    {
        $this->userPrivilege->canEditSpecialPrice(UserAuthentication::getLoggedUserId());
        $userId = $this->userParentId;
        $sellerProdObj = new SellerProduct($selProdId);
        if (!$sellerProdObj->deleteSellerProductSpecialPrice($splPriceId, $selProdId, $userId)) {
            FatUtility::dieWithError(Labels::getLabel($sellerProdObj->getError(), $this->siteLangId));
        }
        return true;
    }

    public function checkIfAvailableForInventory($productId)
    {
        $productId = FatUtility::int($productId);
        $userId = $this->userParentId;
        if (0 == $productId) {
            FatUtility::dieJsonError(Labels::getLabel('ERR_INVALID_REQUEST', $this->siteLangId));
        }
        $available = Product::availableForAddToStore($productId, $userId);
        if (!$available) {
            FatUtility::dieJsonError(Labels::getLabel('ERR_INVENTORY_FOR_ALL_POSSIBLE_PRODUCT_OPTIONS_HAVE_BEEN_ADDED._Please_access_the_shop_inventory_section_to_update', $this->siteLangId));
        }
        FatUtility::dieJsonSuccess(array());
    }

    public function getTranslatedOptionData()
    {
        $dataToTranslate = FatApp::getPostedData('option_name1', FatUtility::VAR_STRING, '');
        if (!empty($dataToTranslate)) {
            $translatedText = $this->translateLangFields(Option::DB_TBL_LANG, ['option_name' => $dataToTranslate]);
            $data = [];
            foreach ($translatedText as $langId => $value) {
                $data[$langId]['option_name' . $langId] = $value['option_name'];
            }
            CommonHelper::jsonEncodeUnicode($data, true);
        }
        FatUtility::dieJsonError(Labels::getLabel('ERR_INVALID_REQUEST', $this->siteLangId));
    }

    public function getTranslatedData()
    {
        $siteDefaultLangId = FatApp::getConfig('conf_default_site_lang', FatUtility::VAR_INT, 1);
        $prodSpecName = FatApp::getPostedData('prod_spec_name', FatUtility::VAR_STRING, '');
        $prodSpecValue = FatApp::getPostedData('prod_spec_value', FatUtility::VAR_STRING, '');

        if (!empty($prodSpecName) && !empty($prodSpecValue)) {
            $data = [];

            $translatedText = $this->translateLangFields(ProductRequest::DB_TBL_LANG, $prodSpecName[$siteDefaultLangId]);
            foreach ($translatedText as $langId => $textArr) {
                foreach ($textArr as $index => $value) {
                    if ('preqlang_lang_id' === $index) {
                        continue;
                    }
                    $data[$langId]['prod_spec_name[' . $langId . '][' . $index . ']'] = $value;
                }
            }

            $translatedText = $this->translateLangFields(ProductRequest::DB_TBL_LANG, $prodSpecValue[$siteDefaultLangId]);
            foreach ($translatedText as $langId => $textArr) {
                foreach ($textArr as $index => $value) {
                    if ('preqlang_lang_id' === $index) {
                        continue;
                    }
                    $data[$langId]['prod_spec_value[' . $langId . '][' . $index . ']'] = $value;
                }
            }

            CommonHelper::jsonEncodeUnicode($data, true);
        }
        FatUtility::dieJsonError(Labels::getLabel('ERR_INVALID_REQUEST', $this->siteLangId));
    }

    private function getCustomProductIntialSetUpFrm($productId = 0, $preqId = 0)
    {
        $frm = new Form('frmProductIntialSetUp');
        $frm->addRequiredField(Labels::getLabel('FRM_PRODUCT_IDENTIFIER', $this->siteLangId), 'product_identifier');
        $frm->addSelectBox(Labels::getLabel('FRM_PRODUCT_TYPE', $this->siteLangId), 'product_type', Product::getProductTypes($this->siteLangId), Product::PRODUCT_TYPE_PHYSICAL, array(), '');

        $frm->addSelectBox(Labels::getLabel('FRM_ATTACHEMENTS_AT_INVENTORY_LEVEL', $this->siteLangId), 'product_attachements_with_inventory', applicationConstants::getYesNoArr($this->siteLangId), '', array(), '');

        $brandFld = $frm->addTextBox(Labels::getLabel('FRM_BRAND', $this->siteLangId), 'brand_name');
        if (FatApp::getConfig("CONF_PRODUCT_BRAND_MANDATORY", FatUtility::VAR_INT, 1)) {
            $brandFld->requirements()->setRequired();
        }
        $frm->addRequiredField(Labels::getLabel('FRM_CATEGORY', $this->siteLangId), 'category_name');

        $siteDefaultLangId = FatApp::getConfig('conf_default_site_lang', FatUtility::VAR_INT, 1);
        $languages = Language::getAllNames();
        foreach ($languages as $langId => $lang) {
            if ($langId == $siteDefaultLangId) {
                $frm->addRequiredField(Labels::getLabel('FRM_PRODUCT_NAME', $this->siteLangId), 'product_name[' . $langId . ']');
            } else {
                $frm->addTextBox(Labels::getLabel('FRM_PRODUCT_NAME', $this->siteLangId), 'product_name[' . $langId . ']');
            }
            //$frm->addTextArea(Labels::getLabel('FRM_DESCRIPTION', $this->siteLangId), 'product_description[' . $langId . ']');
            $frm->addHtmlEditor(Labels::getLabel('FRM_DESCRIPTION', $this->siteLangId), 'product_description_' . $langId);
            $frm->addTextBox(Labels::getLabel('FRM_YOUTUBE_VIDEO_URL', $this->siteLangId), 'product_youtube_video[' . $langId . ']');
        }

        $translatorSubscriptionKey = FatApp::getConfig('CONF_TRANSLATOR_SUBSCRIPTION_KEY', FatUtility::VAR_STRING, '');
        unset($languages[$siteDefaultLangId]);
        if (!empty($translatorSubscriptionKey) && count($languages) > 0) {
            $frm->addCheckBox(Labels::getLabel('FRM_TRANSLATE_TO_OTHER_LANGUAGES', $this->siteLangId), 'auto_update_other_langs_data', 1, array(), false, 0);
        }

        $frm->addRequiredField(Labels::getLabel('FRM_TAX_CATEGORY', $this->siteLangId), 'taxcat_name');
        $fldMinSelPrice = $frm->addFloatField(Labels::getLabel('FRM_MINIMUM_SELLING_PRICE', $this->siteLangId) . ' [' . CommonHelper::getCurrencySymbol(true) . ']', 'product_min_selling_price', '');
        $fldMinSelPrice->requirements()->setPositive();

        $activeInactiveArr = applicationConstants::getActiveInactiveArr($this->siteLangId);
        $frm->addSelectBox(Labels::getLabel('FRM_STATUS', $this->siteLangId), 'product_active', $activeInactiveArr, applicationConstants::YES, array(), '');
        $frm->addHiddenField('', 'product_id', $productId);
        $frm->addHiddenField('', 'preq_id', $preqId);
        $frm->addHiddenField('', 'product_brand_id');
        $frm->addHiddenField('', 'ptc_prodcat_id');
        $frm->addHiddenField('', 'ptt_taxcat_id');
        $frm->addButton('', 'btn_discard', Labels::getLabel('BTN_DISCARD', $this->siteLangId));
        $frm->addSubmitButton('', 'btn_submit', Labels::getLabel('BTN_SAVE_AND_NEXT', $this->siteLangId));
        return $frm;
    }

    private function getProductAttributeAndSpecificationsFrm($productId, $preqId = 0)
    {
        $frm = new Form('frmProductAttributeAndSpecifications');
        $fldModel = $frm->addTextBox(Labels::getLabel('FRM_MODEL', $this->siteLangId), 'product_model');
        if (FatApp::getConfig("CONF_PRODUCT_MODEL_MANDATORY", FatUtility::VAR_INT, 1)) {
            $fldModel->requirements()->setRequired();
        }
        $warrantyFld = $frm->addRequiredField(Labels::getLabel('FRM_PRODUCT_WARRANTY', $this->siteLangId), 'product_warranty');
        $warrantyFld->requirements()->setInt();
        $warrantyFld->requirements()->setPositive();
        $frm->addCheckBox(Labels::getLabel('FRM_MARK_THIS_PRODUCT_AS_FEATURED?', $this->siteLangId), 'product_featured', 1, array(), false, 0);

        if ($preqId > 0) {
            $preqContent = ProductRequest::getAttributesById($preqId, 'preq_content');
            $preqContentData = json_decode($preqContent, true);
            $productType = $preqContentData['product_type'];
        } else {
            $productType = Product::getAttributesById($productId, 'product_type');
        }

        if ($productType == Product::PRODUCT_TYPE_DIGITAL) {
            $warrantyFld->requirements()->setRequired(false);
        }

        $frm->addHiddenField('', 'product_id', $productId);
        $frm->addHiddenField('', 'preq_id', $preqId);
        $frm->addButton('', 'btn_back', Labels::getLabel('BTN_BACK', $this->siteLangId));
        $frm->addSubmitButton('', 'btn_submit', Labels::getLabel('BTN_SAVE_AND_NEXT', $this->siteLangId));
        return $frm;
    }

    private function getProductShippingFrm($productId, $preqId = 0, $forCatalogReq = false)
    {
        $frm = new Form('frmProductShipping');
        if ($preqId > 0) {
            $preqContent = ProductRequest::getAttributesById($preqId, 'preq_content');
            $preqContentData = json_decode($preqContent, true);
            $productType = $preqContentData['product_type'];
        } else {
            $productType = Product::getAttributesById($productId, 'product_type');
        }

        $userId = $this->userParentId;
        if (true == $forCatalogReq) {
            $userId = 0;
        }
        $shippingObj = new Shipping($this->siteLangId);
        if (!FatApp::getConfig('CONF_SHIPPED_BY_ADMIN_ONLY', FatUtility::VAR_INT, 0) && (!$shippingObj->getShippingApiObj($userId) || Shop::getAttributesByUserId($userId, 'shop_use_manual_shipping_rates'))) {
            $shipProfileArr = ShippingProfile::getProfileArr($this->siteLangId, $userId, true, true);
            $frm->addSelectBox(Labels::getLabel('FRM_SHIPPING_PROFILE', $this->siteLangId), 'shipping_profile', $shipProfileArr)->requirements()->setRequired();
        }
        if ($productType == Product::PRODUCT_TYPE_PHYSICAL) {
            if (FatApp::getConfig("CONF_PRODUCT_DIMENSIONS_ENABLE", FatUtility::VAR_INT, 1)) {
                $shipPackArr = ShippingPackage::getAllNames();
                $frm->addSelectBox(Labels::getLabel('FRM_SHIPPING_PACKAGE', $this->siteLangId), 'product_ship_package', $shipPackArr)->requirements()->setRequired();
            }

            if (FatApp::getConfig("CONF_PRODUCT_WEIGHT_ENABLE", FatUtility::VAR_INT, 1)) {
                $weightUnitsArr = applicationConstants::getWeightUnitsArr($this->siteLangId);
                $frm->addSelectBox(Labels::getLabel('FRM_WEIGHT_UNIT', $this->siteLangId), 'product_weight_unit', $weightUnitsArr)->requirements()->setRequired();

                $weightFld = $frm->addFloatField(Labels::getLabel('FRM_WEIGHT', $this->siteLangId), 'product_weight', '0.00');
                $weightFld->requirements()->setRequired(true);
                $weightFld->requirements()->setFloatPositive();
                $weightFld->requirements()->setRange('0.01', '9999999999');
            }

            if (!FatApp::getConfig('CONF_SHIPPED_BY_ADMIN_ONLY', FatUtility::VAR_INT, 0)) {
                /*  $frm->addCheckBox(Labels::getLabel('FRM_PRODUCT_IS_ELIGIBLE_FOR_FREE_SHIPPING?', $this->siteLangId), 'ps_free', 1, array(), false, 0); */

                $codFld = $frm->addCheckBox(Labels::getLabel('FRM_PRODUCT_IS_AVAILABLE_FOR_CASH_ON_DELIVERY_(COD)?', $this->siteLangId), 'product_cod_enabled', 1, array(), false, 0);
                $paymentMethod = new PaymentMethods();
                if (!$paymentMethod->cashOnDeliveryIsActive()) {
                    $codFld->addFieldTagAttribute('disabled', 'disabled');
                    $codFld->htmlAfterField = '<span class="form-text text-muted">' . Labels::getLabel('FRM_COD_OPTION_IS_DISABLED_IN_PAYMENT_GATEWAY_SETTINGS', $this->siteLangId) . '</span>';
                }
            }

            /* ] */
        }
        if ($preqId == 0 && !FatApp::getConfig('CONF_SHIPPED_BY_ADMIN_ONLY', FatUtility::VAR_INT, 0)) {
            $frm->addTextBox(Labels::getLabel('FRM_COUNTRY_THE_PRODUCT_IS_BEING_SHIPPED_FROM', $this->siteLangId), 'shipping_country');
            //$frm->addHtml('', '', '<div id="tab_shipping"></div>');
        }

        $frm->addHiddenField('', 'ps_from_country_id');
        $frm->addHiddenField('', 'product_id', $productId);
        $frm->addHiddenField('', 'preq_id', $preqId);
        $frm->addButton('', 'btn_back', Labels::getLabel('BTN_BACK', $this->siteLangId));
        $frm->addSubmitButton('', 'btn_submit', Labels::getLabel('BTN_SAVE_AND_NEXT', $this->siteLangId));
        return $frm;
    }

    public function translatedProductData()
    {
        $prodName = FatApp::getPostedData('product_name', FatUtility::VAR_STRING, '');
        $prodDesc = FatApp::getPostedData('product_description', FatUtility::VAR_STRING, '');
        $toLangId = FatApp::getPostedData('toLangId', FatUtility::VAR_INT, 0);
        $data = array(
            'product_name' => $prodName,
            'product_description' => $prodDesc,
        );
        $product = new Product();
        $translatedData = $product->getTranslatedProductData($data, $toLangId);
        if (!$translatedData) {
            FatUtility::dieJsonError($product->getError());
        }
        $this->set('productName', $translatedData[$toLangId]['product_name']);
        $this->set('productDesc', $translatedData[$toLangId]['product_description']);
        $this->set('msg', Labels::getLabel('MSG_PRODUCT_DATA_TRANSLATED_SUCCESSFUL', $this->siteLangId));
        $this->_template->render(false, false, 'json-success.php');
    }

    public function compareWithInventoryMinPurchase()
    {
        $selProdId = FatApp::getPostedData('selProdId', FatUtility::VAR_INT, 0);
        $qty = FatApp::getPostedData('qty', FatUtility::VAR_INT, 0);
        if ($selProdId < 1) {
            FatUtility::dieJsonError(Labels::getLabel('ERR_PLEASE_CHOOSE_PRODUCT', $this->siteLangId));
        }
        $minPurchaseQty = SellerProduct::getAttributesById($selProdId, 'selprod_min_order_qty');
        if ($qty < $minPurchaseQty) {
            FatUtility::dieJsonError(Labels::getLabel('ERR_QUANTITY_CANNOT_BE_LESS_THAN_THE_MINIMUM_ORDER_QUANTITY', $this->siteLangId) . ': ' . $minPurchaseQty);
        }
        $this->_template->render(false, false, 'json-success.php');
    }

    public function pickupAddress()
    {
        $this->userPrivilege->canEditShop(UserAuthentication::getLoggedUserId());
        $userId = $this->userParentId;
        $shopDetails = Shop::getAttributesByUserId($userId, null, false);
        if (!$shopDetails) {
            Message::addErrorMessage(Labels::getLabel('ERR_INVALID_REQUEST', $this->siteLangId));
            FatUtility::dieWithError(Message::getHtml());
        }

        if (!false == $shopDetails && $shopDetails['shop_active'] != applicationConstants::ACTIVE) {
            Message::addErrorMessage(Labels::getLabel('ERR_YOUR_SHOP_DEACTIVATED_CONTACT_ADMIN', $this->siteLangId));
            FatUtility::dieWithError(Message::getHtml());
        }
        if (!false == $shopDetails) {
            $shop_id = $shopDetails['shop_id'];
        }
        $address = new Address(0, $this->siteLangId);
        $addresses = $address->getData(Address::TYPE_SHOP_PICKUP, $shopDetails['shop_id']);

        $this->set('addresses', (array) $addresses);

        if (true === MOBILE_APP_API_CALL) {
            $cartObj = new Cart($userId);
            $shipping_address_id = $cartObj->getCartShippingAddress();
            $this->set('shippingAddressId', $shipping_address_id);
            $this->_template->render();
        }

        $this->set('canEdit', $this->userPrivilege->canEditShop(UserAuthentication::getLoggedUserId(), true));
        $this->set('shop_id', $shop_id);
        $this->set('language', Language::getAllNames());
        $this->_template->render(false, false);
    }

    public function pickupAddressForm(int $addrId = 0, int $langId = 0)
    {
        $this->userPrivilege->canEditShop(UserAuthentication::getLoggedUserId());
        $userId = $this->userParentId;
        $shopDetails = Shop::getAttributesByUserId($userId, null, false);

        if (!false == $shopDetails && $shopDetails['shop_active'] != applicationConstants::ACTIVE) {
            Message::addErrorMessage(Labels::getLabel('ERR_YOUR_SHOP_DEACTIVATED_CONTACT_ADMIN', $this->siteLangId));
            FatUtility::dieWithError(Message::getHtml());
        }
        $shopId = 0;
        $stateId = 0;
        $slotData = [];
        if (!false == $shopDetails) {
            $shopId = $shopDetails['shop_id'];
        }
        $langId = 1 > $langId ? $this->siteLangId : $langId;

        $frm = $this->getPickUpAddressForm($addrId, $langId);
        $availability = TimeSlot::DAY_INDIVIDUAL_DAYS;
        if ($addrId > 0) {
            $address = new Address($addrId, $langId);
            $data = $address->getData(Address::TYPE_SHOP_PICKUP, $shopId);
            if (!empty($data)) {
                $stateId = $data['addr_state_id'];

                $timeSlot = new TimeSlot();
                $timeSlots = $timeSlot->timeSlotsByAddrId($addrId);

                $timeSlotsRow = current($timeSlots);
                $availability = isset($timeSlotsRow['tslot_availability']) ? $timeSlotsRow['tslot_availability'] : $availability;
                $data['tslot_availability'] = $availability;
                $frm->fill($data);
                if (!empty($timeSlots)) {
                    foreach ($timeSlots as $key => $slot) {
                        $slotData['tslot_day'][$slot['tslot_day']] = $slot['tslot_day'];
                        $slotData['tslot_from_time'][$slot['tslot_day']][] = $slot['tslot_from_time'];
                        $slotData['tslot_to_time'][$slot['tslot_day']][] = $slot['tslot_to_time'];
                    }
                }
            }
        }

        $this->set('availability', $availability);
        $this->set('shop_id', $shopId);
        $this->set('addrId', $addrId);
        $this->set('langId', $langId);
        $this->set('frm', $frm);
        $this->set('stateId', $stateId);
        $this->set('slotData', $slotData);
        $this->set('formLayout', Language::getLayoutDirection($langId));
        $this->_template->render(false, false);
    }

    public function checkIfNotAnyInventory($productId)
    {
        $productId = FatUtility::int($productId);
        if (0 == $productId) {
            FatUtility::dieJsonError(Labels::getLabel('ERR_INVALID_REQUEST', $this->siteLangId));
        }
        $available = SellerProduct::getCatelogFromProductId($productId);
        if (count($available) > 0) {
            FatUtility::dieJsonSuccess(array());
        }
        FatUtility::dieJsonError(Labels::getLabel('ERR_NOT_ANY_INVENTORY_YET', $this->siteLangId));
    }

    public function orderTrackingInfo($trackingNumber, $courier, $orderNumber)
    {
        if (empty($trackingNumber) || empty($courier)) {
            Message::addErrorMessage(Labels::getLabel('ERR_INVALID_REQUEST', $this->siteLangId));
            FatUtility::dieWithError(Message::getHtml());
        }

        $shipmentTracking = new ShipmentTracking();
        if (false === $shipmentTracking->init($this->siteLangId)) {
            Message::addErrorMessage($shipmentTracking->getError());
            FatUtility::dieWithError(Message::getHtml());
        }

        $shipmentTracking->createTracking($trackingNumber, $courier, $orderNumber);

        if (false === $shipmentTracking->getTrackingInfo($trackingNumber, $courier)) {
            Message::addErrorMessage($shipmentTracking->getError());
            FatUtility::dieWithError(Message::getHtml());
        }
        $trackingInfo = $shipmentTracking->getResponse();

        $this->set('trackingInfo', $trackingInfo);
        $this->_template->render(false, false);
    }

    private function getPickUpAddressForm($addressId, $langId)
    {
        $addressId = FatUtility::int($addressId);
        $frm = new Form('frmPickUpAddress');
        $frm->addHiddenField('', 'addr_id', $addressId);
        $languages = Language::getAllNames();
        if (1 < count($languages)) {
            $frm->addSelectBox(Labels::getLabel('FRM_LANGUAGE', $langId), 'lang_id', $languages, $langId, array(), '');
        } else {
            $frm->addHiddenField('', 'lang_id', array_key_first($languages));
        }

        $frm->addRequiredField(Labels::getLabel('FRM_ADDRESS_LABEL', $langId), 'addr_title');
        $frm->addRequiredField(Labels::getLabel('FRM_NAME', $langId), 'addr_name');
        $frm->addRequiredField(Labels::getLabel('FRM_ADDRESS_LINE1', $langId), 'addr_address1');
        $frm->addTextBox(Labels::getLabel('FRM_ADDRESS_LINE2', $langId), 'addr_address2');

        $countryObj = new Countries();
        $countriesArr = $countryObj->getCountriesAssocArr($langId);
        $frm->addSelectBox(Labels::getLabel('FRM_COUNTRY', $langId), 'addr_country_id', $countriesArr, '', array(), Labels::getLabel('FRM_SELECT', $langId))->requirement->setRequired(true);

        $frm->addSelectBox(Labels::getLabel('FRM_STATE', $langId), 'addr_state_id', array(), '', array(), Labels::getLabel('FRM_SELECT', $langId))->requirement->setRequired(true);
        $frm->addRequiredField(Labels::getLabel('FRM_CITY', $langId), 'addr_city');

        $zipFld = $frm->addRequiredField(Labels::getLabel('FRM_POSTALCODE', $langId), 'addr_zip');
        $frm->addHiddenField('', 'addr_phone_dcode');
        $phnFld = $frm->addRequiredField(Labels::getLabel('FRM_PHONE', $langId), 'addr_phone', '', array('class' => 'phone-js ltr-right', 'placeholder' => ValidateElement::PHONE_NO_FORMAT, 'maxlength' => ValidateElement::PHONE_NO_LENGTH));
        $phnFld->requirements()->setRegularExpressionToValidate(ValidateElement::PHONE_REGEX);
        $phnFld->requirements()->setCustomErrorMessage(Labels::getLabel('FRM_PLEASE_ENTER_VALID_PHONE_NUMBER_FORMAT.', $langId));

        $slotTimingsTypeArr = TimeSlot::getSlotTypeArr($langId);
        $frm->addRadioButtons(Labels::getLabel('FRM_SLOT_TIMINGS', $langId), 'tslot_availability', $slotTimingsTypeArr, TimeSlot::DAY_INDIVIDUAL_DAYS);

        $daysArr = TimeSlot::getDaysArr($langId);
        for ($i = 0; $i < count($daysArr); $i++) {
            $frm->addCheckBox($daysArr[$i], 'tslot_day[' . $i . ']', $i, array(), false);
            $frm->addSelectBox(Labels::getLabel('FRM_FROM', $langId), 'tslot_from_time[' . $i . '][]', TimeSlot::getTimeSlotsArr(), '', array(), Labels::getLabel('FRM_SELECT', $langId));
            $frm->addSelectBox(Labels::getLabel('FRM_TO', $langId), 'tslot_to_time[' . $i . '][]', TimeSlot::getTimeSlotsArr(), '', array(), Labels::getLabel('FRM_SELECT', $this->siteLangId));
        }

        return $frm;
    }

    public function setPickupAddress()
    {
        $this->userPrivilege->canEditShop(UserAuthentication::getLoggedUserId());
        $userId = $this->userParentId;
        $shopId = Shop::getAttributesByUserId($userId, 'shop_id');

        $post = FatApp::getPostedData();
        $availability = FatApp::getPostedData('tslot_availability', FatUtility::VAR_INT, 1);

        $addrStateId = FatUtility::int($post['addr_state_id']);
        $slotDays = isset($post['tslot_day']) ? $post['tslot_day'] : array();
        $slotFromTime = $post['tslot_from_time'];
        $slotToTime = $post['tslot_to_time'];

        $frm = $this->getPickUpAddressForm($post['addr_id'], $this->siteLangId);
        $postedData = $frm->getFormDataFromArray($post);
        if (false === $postedData) {
            FatUtility::dieJsonError(current($frm->getValidationErrors()));
        }

        if ($availability == TimeSlot::DAY_ALL_DAYS && !isset($slotFromTime[TimeSlot::DAY_SUNDAY])) {
            FatUtility::dieJsonError(Labels::getLabel('ERR_INVALID_REQUEST', $this->siteLangId));
        }

        $addressId = $post['addr_id'];
        unset($post['addr_id']);

        $address = new Address($addressId);
        $data = $post;
        $data['addr_state_id'] = $addrStateId;
        $data['addr_record_id'] = $shopId;
        $data['addr_lang_id'] = $post['lang_id'];
        $data['addr_type'] = Address::TYPE_SHOP_PICKUP;
        $address->assignValues($data);
        if (!$address->save()) {
            LibHelper::dieJsonError($address->getError());
        }

        $addrId = $address->getMainTableRecordId();
        if (!FatApp::getDb()->deleteRecords(TimeSlot::DB_TBL, array('smt' => 'tslot_type = ? and tslot_record_id = ?', 'vals' => array(Address::TYPE_SHOP_PICKUP, $addrId)))) {
            LibHelper::dieJsonError(FatApp::getDb()->getError());
        }

        if (!empty($slotDays) && $availability == TimeSlot::DAY_INDIVIDUAL_DAYS) {
            foreach ($slotDays as $day) {
                foreach ($slotFromTime[$day] as $key => $fromTime) {
                    if (!empty($fromTime) && !empty($slotToTime[$day][$key])) {
                        $slotData['tslot_type'] = Address::TYPE_SHOP_PICKUP;
                        $slotData['tslot_availability'] = $availability;
                        $slotData['tslot_record_id'] = $addrId;
                        $slotData['tslot_day'] = $day;
                        $slotData['tslot_from_time'] = $fromTime;
                        $slotData['tslot_to_time'] = $post['tslot_to_time'][$day][$key];
                        $timeSlot = new TimeSlot();
                        $timeSlot->assignValues($slotData);
                        if (!$timeSlot->save()) {
                            LibHelper::dieJsonError($timeSlot->getError());
                        }
                    }
                }
            }
        }

        if (!empty($slotDays) && $availability == TimeSlot::DAY_ALL_DAYS) {
            $daysArr = TimeSlot::getDaysArr($this->siteLangId);
            foreach ($daysArr as $day => $label) {
                foreach ($slotFromTime[TimeSlot::DAY_SUNDAY] as $key => $fromTime) {
                    if (!empty($fromTime) && !empty($slotToTime[TimeSlot::DAY_SUNDAY][$key])) {
                        $slotData['tslot_type'] = Address::TYPE_SHOP_PICKUP;
                        $slotData['tslot_availability'] = $availability;
                        $slotData['tslot_record_id'] = $addrId;
                        $slotData['tslot_day'] = $day;
                        $slotData['tslot_from_time'] = $fromTime;
                        $slotData['tslot_to_time'] = $post['tslot_to_time'][TimeSlot::DAY_SUNDAY][$key];
                        $timeSlot = new TimeSlot();
                        $timeSlot->assignValues($slotData);
                        if (!$timeSlot->save()) {
                            LibHelper::dieJsonError($timeSlot->getError());
                        }
                    }
                }
            }
        }

        $this->set('msg', Labels::getLabel('MSG_Setup_successful', $this->siteLangId));
        $this->_template->render(false, false, 'json-success.php');
    }

    public function getShopDetail(int $autoComplteRequest = 0)
    {
        $attr = (1 > $autoComplteRequest ? null : [
            'shop_id as id',
            'COALESCE(shop_name, shop_identifier) as name'
        ]);
        $shopData = (array) Shop::getAttributesByUserId(UserAuthentication::getLoggedUserId(), $attr, true, $this->siteLangId);
        FatUtility::dieJsonSuccess(['shopData' => $shopData]);
    }

    public function tagsAutoComplete()
    {
        $post = FatApp::getPostedData();

        $srch = Tag::getSearchObject($post['LangId']);
        $srch->addOrder('tag_name');
        $srch->addMultipleFields(array('tag_id', 'tag_name'));

        if (!empty($post['keyword'])) {
            $cnd = $srch->addCondition('tag_name', 'LIKE', '%' . $post['keyword'] . '%');
            $cnd->attachCondition('tag_name', 'LIKE', '%' . $post['keyword'] . '%', 'OR');
        }

        $rs = $srch->getResultSet();
        $db = FatApp::getDb();
        $options = $db->fetchAll($rs, 'tag_id');
        $json = array();
        foreach ($options as $key => $option) {
            $json[] = array(
                'id' => $key,
                'name' => strip_tags(html_entity_decode($option['tag_name'], ENT_QUOTES, 'UTF-8')),
            );
        }
        die(json_encode($json));
    }

    public function updateProductTag()
    {
        $this->checkEditPrivilege();
        $post = FatApp::getPostedData();
        $productId = FatApp::getPostedData('product_id', FatUtility::VAR_INT, 0);
        $tagId = FatApp::getPostedData('tag_id', FatUtility::VAR_INT, 0);
        if (!UserPrivilege::canSellerEditCustomProduct($this->userParentId, $productId)) {
            echo "here";
            die;
            Message::addErrorMessage(Labels::getLabel('ERR_INVALID_ACCESS', $this->siteLangId));
            FatUtility::dieWithError(Message::getHtml());
        }
        if ($productId < 1 || $tagId < 1) {
            Message::addErrorMessage($this->str_invalid_request);
            FatUtility::dieWithError(Message::getHtml());
        }
        $prod = new Product($productId);
        if (!$prod->addUpdateProductTag($tagId)) {
            Message::addErrorMessage(Labels::getLabel($prod->getError(), $this->siteLangId));
            FatUtility::dieWithError(Message::getHtml());
        }

        Tag::updateProductTagString($productId);

        $this->set('msg', Labels::getLabel('MSG_TAG_UPDATED_SUCCESSFULLY', $this->siteLangId));
        $this->_template->render(false, false, 'json-success.php');
    }

    public function tagSetup()
    {
        $tagName = FatApp::getPostedData('tag_name', FatUtility::VAR_STRING);
        $langId = FatApp::getPostedData('lang_id', FatUtility::VAR_INT, 0);
        if (1 > $langId) {
            $langId = $this->siteLangId;
        }

        if (empty($tagName)) {
            LibHelper::exitWithError($this->str_invalid_request);
        }

        $record = new Tag();
        $record->assignValues(['tag_name' => $tagName, 'tag_lang_id' => $langId, 'tag_user_id' => $this->userParentId]);

        if (!$record->save()) {
            FatUtility::dieJsonError(Labels::getLabel('ERR_THIS_IDENTIFIER_IS_NOT_AVAILABLE._PLEASE_TRY_WITH_ANOTHER_ONE.', $this->siteLangId));
        }
        $tag_id = $record->getMainTableRecordId();
        /* update product tags association and tag string in products lang table[ */
        Tag::updateTagStrings($tag_id);
        /* ] */

        $this->set('msg', Labels::getLabel('MSG_TAG_UPDATED_SUCCESSFUL', $this->siteLangId));
        $this->set('tagId', $tag_id);
        $this->_template->render(false, false, 'json-success.php');
    }

    public function removeProductTag()
    {
        $this->checkEditPrivilege();

        $post = FatApp::getPostedData();
        $productId = FatApp::getPostedData('product_id', FatUtility::VAR_INT, 0);
        $tagId = FatApp::getPostedData('tag_id', FatUtility::VAR_INT, 0);
        if (!UserPrivilege::canSellerEditCustomProduct($this->userParentId, $productId)) {
            Message::addErrorMessage(Labels::getLabel('ERR_INVALID_ACCESS', $this->siteLangId));
            FatUtility::dieWithError(Message::getHtml());
        }
        if ($productId < 1 || $tagId < 1) {
            Message::addErrorMessage(Labels::getLabel('LBL_INVALID_REQUEST', $this->siteLangId));
            FatUtility::dieWithError(Message::getHtml());
        }

        $prod = new Product($productId);
        if (!$prod->removeProductTag($tagId)) {
            Message::addErrorMessage(Labels::getLabel($prod->getError(), $this->siteLangId));
            FatUtility::dieWithError(Message::getHtml());
        }

        Tag::updateProductTagString($productId);

        $this->set('msg', Labels::getLabel('MSG_TAG_REMOVED_SUCCESSFULLY', $this->siteLangId));
        $this->_template->render(false, false, 'json-success.php');
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
            $this->set("canEdit", $this->userPrivilege->canEditProducts(UserAuthentication::getLoggedUserId(), true));
        } else {
            $this->userPrivilege->canEditProducts();
        }
    }

    protected function getCatalogType(): int
    {
        return Product::CATALOG_TYPE_INVENTORY;
    }

    // Page Created for Pawan to create new UI for add product. 26/11/2021
    public function addProductPageUi()
    {
        $this->_template->render();
    }

    public function countries_autocomplete()
    {
        $pagesize = 20;
        $page = FatApp::getPostedData('page', FatUtility::VAR_INT, 1);
        if ($page < 2) {
            $page = 1;
        }
        $post = FatApp::getPostedData();
        $srch = Countries::getSearchObject(true, $this->siteLangId);
        $srch->addOrder('country_name');

        $srch->addMultipleFields(array('country_id, country_name, country_code'));

        if (!empty($post['keyword'])) {
            $srch->addCondition('country_name', 'LIKE', '%' . $post['keyword'] . '%');
        }

        $srch->setPageSize($pagesize);
        $srch->setPageNumber($page);

        $countries = FatApp::getDb()->fetchAll($srch->getResultSet(), 'country_id');
        if (isset($post['includeEverywhere']) && $post['includeEverywhere']) {
            $everyWhereArr = array('country_id' => '-1', 'country_name' => Labels::getLabel('LBL_Everywhere_Else', $this->siteLangId));
            $countries[] = $everyWhereArr;
        }

        $json = array(
            'pageCount' => $srch->pages()
        );
        foreach ($countries as $key => $country) {
            $json['results'][] = array(
                'id' => $country['country_id'],
                'text' => strip_tags(html_entity_decode(isset($country['country_name']) ? $country['country_name'] : $country['country_code'], ENT_QUOTES, 'UTF-8')),
            );
        }
        die(json_encode($json));
    }

    public function getCancellationRequestComment()
    {
        $recordId = FatApp::getPostedData('recordId', FatUtility::VAR_INT, 0);
        $this->set('comments', OrderCancelRequest::getAttributesById($recordId, 'ocrequest_message'));
        $this->set('html', $this->_template->render(false, false, NULL, true));
        $this->_template->render(false, false, 'json-success.php', true, false);
    }

    public function getBreadcrumbNodes($action)
    {
        if (FatUtility::isAjaxCall()) {
            return;
        }

        $className = get_class($this);
        $arr = explode('-', FatUtility::camel2dashed($className));
        array_pop($arr);
        $className = mb_strtoupper(implode('_', $arr));

        if ($action == 'index') {
            $this->nodes[] = array('title' => ucwords(Labels::getLabel('BCN_' . $className)));
        } else if ($action == 'productSeo') {
            $this->nodes[] = array('title' => Labels::getLabel('LBL_META_TAGS', $this->siteLangId));
        } else if ($action == 'sellerProductForm') {
            $action = str_replace('-', '_', FatUtility::camel2dashed($action));
            $this->nodes[] = array('title' => Labels::getLabel('LBL_PRODUCTS'), 'href' => UrlHelper::generateUrl("Seller", "products"));
            $this->nodes[] = array('title' => ucwords(Labels::getLabel('BCN_' . $action)));
        } else if ($action == 'productUrlRewriting') {
            $this->nodes[] = array('title' => Labels::getLabel('LBL_URL_REWRITING', $this->siteLangId));
        } else if ($action == 'taxRules') {
            $action = str_replace('-', '_', FatUtility::camel2dashed($action));
            $this->nodes[] = array('title' => Labels::getLabel('LBL_TAX_CATEGORIES'), 'href' => UrlHelper::generateUrl("Seller", "taxCategories"));
            $this->nodes[] = array('title' => ucwords(Labels::getLabel('BCN_' . $action, $this->siteLangId)));
        } else if ($action == 'viewOrder') {
            $action = str_replace('-', '_', FatUtility::camel2dashed($action));
            $this->nodes[] = array('title' => Labels::getLabel('LBL_Sales'), 'href' => UrlHelper::generateUrl("Seller", "sales"));
            $this->nodes[] = array('title' => ucwords(Labels::getLabel('BCN_' . $action, $this->siteLangId)));
        } else if ($action == 'cancelOrder') {
            $action = str_replace('-', '_', FatUtility::camel2dashed($action));
            $this->nodes[] = array('title' => Labels::getLabel('LBL_Sales'), 'href' => UrlHelper::generateUrl("Seller", "sales"));
            $this->nodes[] = array('title' => ucwords(Labels::getLabel('BCN_' . $action, $this->siteLangId)));
        } else if ($action == 'viewOrderReturnRequest') {
            $action = str_replace('-', '_', FatUtility::camel2dashed($action));
            $this->nodes[] = array('title' => Labels::getLabel('LBL_ORDER_RETURN_REQUESTS'), 'href' => UrlHelper::generateUrl("Seller", "orderReturnRequests"));
            $this->nodes[] = array('title' => ucwords(Labels::getLabel('BCN_' . $action, $this->siteLangId)));
        } else if ($action == 'userPermissions') {
            $action = str_replace('-', '_', FatUtility::camel2dashed($action));
            $this->nodes[] = array('title' => Labels::getLabel('LBL_USERS'), 'href' => UrlHelper::generateUrl("Seller", "users"));
            $this->nodes[] = array('title' => ucwords(Labels::getLabel('BCN_' . $action, $this->siteLangId)));
        } else {
            $action = str_replace('-', '_', FatUtility::camel2dashed($action));
            $this->nodes[] = array('title' => ucwords(Labels::getLabel('BCN_' . $action, $this->siteLangId)));
        }
        return $this->nodes;
    }

    public function deleteCatalog()
    {
        $recordId = FatApp::getPostedData('recordId', FatUtility::VAR_INT, 0);
        if ($recordId < 1) {
            LibHelper::exitWithError($this->str_invalid_request_id, true);
        }

        $this->markAsDeleted($recordId);
        $this->set('msg', Labels::getLabel('LBL_RECORD_DELETED_SUCCESSFULLY'));
        $this->_template->render(false, false, 'json-success.php');
    }

    protected function markAsDeleted(int $recordId)
    {
        $recordId = FatUtility::int($recordId);
        if (1 > $recordId) {
            LibHelper::exitWithError($this->str_invalid_request_id, true);
        }
        $prodSellerId = Product::getAttributesById($recordId, 'product_seller_id');
        if ($this->userParentId != $prodSellerId) {
            LibHelper::exitWithError($this->str_invalid_request_id, true);
        }
        $product = new Product($recordId);
        $product->assignValues(
            [
                $product::tblFld('deleted') => 1,
                $product::tblFld('identifier') => 'mysql_func_CONCAT(' . $product::tblFld('identifier') . ',"{deleted}",' . $product::tblFld('id') . ')'
            ],
            false,
            '',
            '',
            true
        );
        if (!$product->save()) {
            LibHelper::exitWithError($product->getError(), true);
        }
        CalculativeDataRecord::updateSelprodRequestCount();
    }
}
