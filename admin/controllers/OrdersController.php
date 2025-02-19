<?php
class OrdersController extends ListingBaseController
{
    protected string $pageKey = 'MANAGE_ORDERS';
    use OrdersPackage;
    private int $ordersType = Orders::ORDER_PRODUCT;

    public function __construct($action)
    {
        parent::__construct($action);
        $this->objPrivilege->canViewOrders();
    }

    private function orderData(int $orderId)
    {
        $srch = new OrderSearch($this->siteLangId);
        $srch->joinOrderPaymentMethod();
        $srch->doNotCalculateRecords();
        $srch->setPageSize(1);
        $srch->joinOrderBuyerUser();
        $srch->addMultipleFields(
            array(
                'order_number',
                'order_id',
                'order_user_id',
                'order_date_added',
                'order_payment_status',
                'order_tax_charged',
                'order_site_commission',
                'order_reward_point_value',
                'order_volume_discount_total',
                'buyer.user_name as buyer_user_name',
                'buyer_cred.credential_email as buyer_email',
                'buyer.user_phone_dcode as buyer_phone_dcode',
                'buyer.user_phone as buyer_phone',
                'order_net_amount',
                'order_shippingapi_name',
                'order_pmethod_id',
                'ifnull(plugin_name,plugin_identifier)as plugin_name',
                'order_discount_total',
                'plugin_code',
                'order_is_wallet_selected',
                'order_reward_point_used',
                'order_deleted',
                'order_rounding_off'
            )
        );
        $srch->addCondition('order_id', '=', $orderId);
        $srch->addCondition('order_type', '=', $this->ordersType);
        $rs = $srch->getResultSet();
        $this->order = (array) FatApp::getDb()->fetch($rs);
        if (empty($this->order)) {
            LibHelper::exitWithError(Labels::getLabel('ERR_ORDER_DATA_NOT_FOUND', $this->siteLangId), false, true);
            CommonHelper::redirectUserReferer();
        }

        $opSrch = new OrderProductSearch($this->siteLangId, true, true, true);
        $opSrch->joinShippingCharges();
        $opSrch->joinAddress();
        $opSrch->joinPaymentMethod();
        $opSrch->joinOrderProductShipment();
        $opSrch->addCountsOfOrderedProducts();
        $opSrch->addOrderProductCharges();
        $opSrch->joinOrderProductSpecifics();
        $opSrch->joinShippingUsers();
        $opSrch->joinSellerProducts();
        $opSrch->doNotCalculateRecords();
        $opSrch->doNotLimitRecords();
        $opSrch->addCondition('op.op_order_id', '=', $this->order['order_id']);

        $opSellerId = FatApp::getPostedData('op_selprod_user_id', FatUtility::VAR_INT, 0);
        if (0 < $opSellerId) {
            $opSrch->addCondition('op.op_selprod_user_id', '=', $opSellerId);
        }

        $opId = FatApp::getPostedData('op_id', FatUtility::VAR_INT, 0);
        if (0 < $opId) {
            $opSrch->addCondition('op.op_id', '=', $opId);
        }

        $opSrch->addMultipleFields(
            array(
                'op_id',
                'op_status_id',
                'op_selprod_id',
                'op_selprod_user_id',
                'op_invoice_number',
                'op_selprod_title',
                'op_product_name',
                'op_qty',
                'op_brand_name',
                'op_selprod_options',
                'op_selprod_sku',
                'op_product_model',
                'op_shop_name',
                'op_shop_owner_name',
                'op_shop_owner_email',
                'op_shop_owner_phone',
                'op_unit_price',
                'totCombinedOrders as totOrders',
                'op_shipping_duration_name',
                'op_shipping_durations',
                'IFNULL(orderstatus_name, orderstatus_identifier) as orderstatus_name',
                'op_other_charges',
                'op_product_tax_options',
                'ops.*',
                'opship.*',
                'opr_response',
                'addr.*',
                'ts.state_code',
                'tc.country_code',
                'op_rounding_off',
                'op_shop_owner_phone_dcode',
                'op_selprod_price',
                'op_special_price',
                'opshipping_by_seller_user_id',
                'selprod_product_id',
                'orderstatus_color_class',
                'op_product_type',
                'order_payment_status',
                'plugin_code',
                'opshipping_fulfillment_type',
                'orderstatus_id',
                'IFNULL(optosu.optsu_user_id, 0) as optsu_user_id',
                'op_product_length',
                'op_product_width',
                'op_product_height',
                'op_product_dimension_unit',
                'op_commission_charged',
                'op_commission_percentage',
                'op_refund_commission',
                'op_tax_after_discount',
                'op_comments'
            )
        );
        $opSrch->addOrder('op_selprod_user_id');
        $this->order['products'] = FatApp::getDb()->fetchAll($opSrch->getResultSet(), 'op_id');
        $orderObj = new Orders($this->order['order_id']);

        $charges = $orderObj->getOrderProductChargesByOrderId($this->order['order_id']);
        $shippingObj = new Shipping($this->siteLangId);

        $sellers = [];
        $shippingApiObj = NULL;
        foreach ($this->order['products'] as $opId => $opVal) {
            $sellers[$opVal['op_selprod_user_id']] = $opVal['op_shop_name'];
            $this->order['products'][$opId]['charges'] = $charges[$opId];
            $opChargesLog = new OrderProductChargeLog($opId);
            $taxOptions = $opChargesLog->getData($this->siteLangId);
            $this->order['products'][$opId]['taxOptions'] = $taxOptions;
            if (!empty($opVal["opship_orderid"])) {
                $shippingHanldedBySeller = CommonHelper::canAvailShippingChargesBySeller($opVal['op_selprod_user_id'], $opVal['opshipping_by_seller_user_id']);
                $shippingApiObj = $shippingObj->getShippingApiObj(($shippingHanldedBySeller ? $opVal['opshipping_by_seller_user_id'] : 0)) ?? NULL;
                if ($shippingApiObj && method_exists($shippingApiObj, 'loadOrder') && false === $shippingApiObj->loadOrder($opVal["opship_orderid"])) {
                    LibHelper::exitWithError($shippingApiObj->getError(), true);
                }
                $this->order['products'][$opId]['thirdPartyorderInfo'] = $shippingApiObj ? $shippingApiObj->getResponse() : [];
            }
        }
        $addresses = $orderObj->getOrderAddresses($this->order['order_id']);
        $this->order['billingAddress'] = $this->order['billingAddress'] = [];
        if (!empty($addresses)) {
            $this->order['billingAddress'] = $addresses[Orders::BILLING_ADDRESS_TYPE] ?? [];
            $this->order['shippingAddress'] = $addresses[Orders::SHIPPING_ADDRESS_TYPE] ?? [];
        }

        $pickUpAddress = $orderObj->getOrderAddresses($this->order['order_id'], $opId);
        if (!empty($addresses)) {
            $this->order['pickupAddress'] = (!empty($pickUpAddress[Orders::PICKUP_ADDRESS_TYPE])) ? $pickUpAddress[Orders::PICKUP_ADDRESS_TYPE] : array();
        }

        $this->order['comments'] = $orderObj->getOrderComments($this->siteLangId, array("order_id" => $this->order['order_id']));
        $this->order['payments'] = $orderObj->getOrderPayments(array("order_id" => $this->order['order_id']));

        $this->set('unitTypeArray', ShippingPackage::getUnitTypes($this->siteLangId));
        $this->set('shippingApiObj', $shippingApiObj);
        $this->set('shippedOrderStatus', FatApp::getConfig("CONF_DEFAULT_SHIPPING_ORDER_STATUS"));
        $this->set('sellers', $sellers);
        $this->set('opSellerId', $opSellerId);
        $this->set('order', $this->order);
        $this->set("canEdit", $this->objPrivilege->canEditOrders($this->admin_id, true));
        $this->set("canEditSellerOrders", $this->objPrivilege->canEditSellerOrders($this->admin_id, true));

        $this->set("allowedShippingUserStatuses", $orderObj->getAdminAllowedUpdateShippingUser());
    }

    public function getPayments($orderId)
    {
        $orderObj = new Orders($orderId);
        $order['payments'] = $orderObj->getOrderPayments(array("order_id" => $orderId));
        $this->set('order', $order);
        $jsonData = [
            'html' => $this->_template->render(false, false, 'orders/payment-history.php', true),
        ];
        LibHelper::exitWithSuccess($jsonData, true);
    }

    public function getOrderParticulars($orderId)
    {
        $this->orderData($orderId);
        $jsonData = [
            'itemSummaryHtml' => $this->_template->render(false, false, 'orders/item-summary.php', true),
            'orderSummaryHtml' => $this->_template->render(false, false, 'orders/order-summary.php', true)
        ];
        LibHelper::exitWithSuccess($jsonData, true);
    }

    private function rowsData(int $orderId)
    {
        $recordId = FatApp::getPostedData('recordId', FatUtility::VAR_INT, 0);
        if (1 > $recordId || 1 > $orderId) {
            LibHelper::exitWithError($this->str_invalid_request_id, true);
        }

        $pagesize = FatApp::getConfig('CONF_ADMIN_PAGESIZE', FatUtility::VAR_INT, 10);
        $page = FatApp::getPostedData('page', FatUtility::VAR_INT, 1);
        $page = ($page <= 0) ? 1 : $page;

        $orderObj = new Orders($orderId);
        $srch = $orderObj->getOrderCommentsSrchObj($this->siteLangId, array("op_id" => $recordId));
        $srch->setPageNumber($page);
        $srch->setPageSize($pagesize);
        $records = FatApp::getDb()->fetchAll($srch->getResultSet());

        $this->set("arrListing", $records);
        $this->set('pageCount', $srch->pages());
        $this->set('recordCount', $srch->recordCount());
        $this->set('page', $page);
        $this->set('pageSize', $pagesize);
        $this->set('recordId', $recordId);
        $this->set('postedData', FatApp::getPostedData());
    }

    public function getItemStatusHistory($orderId)
    {
        $this->rowsData($orderId);

        $recordId = FatApp::getPostedData('recordId', FatUtility::VAR_INT, 0);
        $this->set('trackingUrl', OrderProductShipment::getAttributesById($recordId, 'opship_tracking_url'));

        $this->set('html', $this->_template->render(false, false, NULL, true));
        $this->_template->render(false, false, 'json-success.php', true, false);
    }

    public function getRows($orderId = 0)
    {
        $orderId = FatApp::getPostedData('order_id', FatUtility::VAR_INT, $orderId);
        $this->rowsData($orderId);
        $this->set('html', $this->_template->render(false, false, NULL, true));
        $this->_template->render(false, false, 'json-success.php', true, false);
    }

    private function getCommentDataSrchObj(array $attr)
    {
        $this->objPrivilege->canEditSellerOrders();
        $opId = FatApp::getPostedData('op_id', FatUtility::VAR_INT, 0);

        $srch = new OrderProductSearch($this->siteLangId, true, true);
        $srch->joinOrderProductShipment();
        $srch->joinOrderUser();
        $srch->joinPaymentMethod();
        $srch->joinShippingUsers();
        $srch->joinShippingCharges();
        $srch->joinAddress();
        $srch->addOrderProductCharges();
        $srch->joinTable(Plugin::DB_TBL, 'LEFT OUTER JOIN', 'ops.opshipping_plugin_id = ops_plugin.plugin_id', 'ops_plugin');
        $srch->addMultipleFields($attr);
        $srch->addCondition('op_id', '=', $opId);
        $srch->setPageSize(1);
        $srch->doNotCalculateRecords();
        return FatApp::getDb()->fetch($srch->getResultSet());
    }

    public function shippingUsersForm(int $orderId)
    {
        $this->objPrivilege->canEditSellerOrders();
        $opId = FatApp::getPostedData('op_id', FatUtility::VAR_INT, 0);
        $opRow = $this->getCommentDataSrchObj(['optsu_user_id', 'op_selprod_title']);
        if ($opRow == false) {
            LibHelper::exitWithError($this->str_invalid_request, true);
        }

        $srch = new SearchBase(OrderProduct::DB_TBL_OP_TO_SHIPPING_USERS, 'optosu');
        $srch->doNotCalculateRecords();
        $srch->setPageSize(1);
        $srch->addCondition('optosu.optsu_op_id', '=', $opId);
        $rs = $srch->getResultSet();
        $shippingUser = (array) FatApp::getDb()->fetch($rs);

        $frm = $this->getShippingUserForm();
        $frm->fill(['op_id' => $opId, 'optsu_user_id' => $opRow['optsu_user_id']]);
        $this->set('frm', $frm);
        $this->set('isShippingUserAssigned', (0 < count($shippingUser)));
        $this->set('orderId', $orderId);
        $this->set('recordId', $opId);
        $this->set('formTitle', $opRow['op_selprod_title']);
        $this->set('displayLangTab', false);
        $this->set('html', $this->_template->render(false, false, NULL, true));
        $this->_template->render(false, false, 'json-success.php', true, false);
    }

    public function orderCommentsForm($orderId = 0)
    {
        $opRow = $this->getCommentDataSrchObj([
            'ops.*',
            'order_number',
            'order_id',
            'order_payment_status',
            'order_pmethod_id',
            'order_tax_charged',
            'order_date_added',
            'op_id',
            'op_qty',
            'op_unit_price',
            'op_selprod_user_id',
            'op_invoice_number',
            'IFNULL(orderstatus_name, orderstatus_identifier) as orderstatus_name',
            'ou.user_name as buyer_user_name',
            'ouc.credential_username as buyer_username',
            'pm.plugin_code',
            'IFNULL(pm_l.plugin_name, IFNULL(pm.plugin_identifier, "Wallet")) as plugin_name',
            'op_commission_charged',
            'op_qty',
            'op_commission_percentage',
            'ou.user_name as buyer_name',
            'ouc.credential_username as buyer_username',
            'ouc.credential_email as buyer_email',
            'ou.user_phone_dcode as buyer_phone_dcode',
            'ou.user_phone as buyer_phone',
            'op.op_shop_owner_name',
            'op.op_shop_owner_username',
            'op_l.op_shop_name',
            'op.op_shop_owner_email',
            'op.op_shop_owner_phone_dcode',
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
            'orderstatus_id',
            'ops_plugin.plugin_code as opshipping_plugin_code',
            'op_product_length',
            'op_product_width',
            'op_product_height',
            'op_product_dimension_unit',
            'opshipping_by_seller_user_id'
        ]);
        if ($opRow == false) {
            LibHelper::exitWithError($this->str_invalid_request, true);
        }
        $shippingHanldedBySeller = CommonHelper::canAvailShippingChargesBySeller($opRow['op_selprod_user_id'], $opRow['opshipping_by_seller_user_id']);

        $orderObj = new Orders($opRow['order_id']);
        if ($opRow['plugin_code'] == 'CashOnDelivery') {
            $processingStatuses = $orderObj->getAdminAllowedUpdateOrderStatuses(true, $opRow['op_product_type']);
        } else if ($opRow['plugin_code'] == 'PayAtStore') {
            $processingStatuses = $orderObj->getAdminAllowedUpdateOrderStatuses(false, $opRow['op_product_type'], true);
        } else {
            $processingStatuses = $orderObj->getAdminAllowedUpdateOrderStatuses(false, $opRow['op_product_type']);
        }

        $opId = FatApp::getPostedData('op_id', FatUtility::VAR_INT, 0);
        $data = [
            'op_id' => $opId,
            'order_id' => $orderId,
            'op_status_id' => $opRow['op_status_id'],
            'tracking_number' => $opRow['opship_tracking_number'],

        ];

        if ($opRow["opshipping_fulfillment_type"] == Shipping::FULFILMENT_PICKUP) {
            $processingStatuses = array_diff($processingStatuses, (array) FatApp::getConfig("CONF_DEFAULT_SHIPPING_ORDER_STATUS", FatUtility::VAR_INT, 0));
        } else {
            $processingStatuses = array_diff($processingStatuses, (array) FatApp::getConfig("CONF_PICKUP_READY_ORDER_STATUS", FatUtility::VAR_INT, 0));
        }

        $displayForm = (in_array($opRow['op_status_id'], $processingStatuses) && $opRow['order_payment_status'] != Orders::ORDER_PAYMENT_CANCELLED);
        if (!$displayForm) {
            LibHelper::exitWithError(Labels::getLabel('ERR_NOT_ALLOWED_TO_UPDATE_STATUS', $this->siteLangId), true);
        }

        $allowedShippingUserStatuses = $orderObj->getAdminAllowedUpdateShippingUser();
        $displayShippingUserForm = (
            (
                (isset($opRow['plugin_code']) && in_array(strtolower($opRow['plugin_code']), ['cashondelivery', 'payatstore'])
                ) ||
                (in_array($opRow['op_status_id'], $allowedShippingUserStatuses)
                )
            ) &&
            $this->objPrivilege->canEditSellerOrders($this->admin_id, true) &&
            !$shippingHanldedBySeller &&
            ($opRow['op_product_type'] == Product::PRODUCT_TYPE_PHYSICAL &&
                $opRow['order_payment_status'] != Orders::ORDER_PAYMENT_CANCELLED
            )
        );


        $frm = $this->getOrderCommentsForm($opRow, $processingStatuses);
        $frm->fill($data);
        $this->set('frm', $frm);
        $this->set('op', $opRow);
        $this->set('displayShippingUserForm', $displayShippingUserForm);
        $this->set('includeTabs', $displayShippingUserForm);
        $this->set('displayLangTab', false);
        $this->set('html', $this->_template->render(false, false, NULL, true));
        $this->_template->render(false, false, 'json-success.php', true, false);
    }

    private function getShippingUserForm()
    {
        $srch = User::getSearchObject(true);
        $srch->addOrder('u.user_id', 'DESC');
        $srch->addCondition('u.user_is_shipping_company', '=', applicationConstants::YES);
        $srch->addMultipleFields(array('user_id', 'credential_username'));
        $srch->addCondition('uc.credential_active', '=', applicationConstants::ACTIVE);
        $srch->addCondition('uc.credential_verified', '=', applicationConstants::YES);
        $records = FatApp::getDb()->fetchAllAssoc($srch->getResultSet());

        $frm = new Form('frmShippingUser');
        $frm->addHiddenField('', 'op_id');
        $frm->addSelectBox(Labels::getLabel('FRM_SHIPPING_USER', $this->siteLangId), 'optsu_user_id', $records)->requirements()->setRequired();
        return $frm;
    }


    private function getOrderCommentsForm($orderData = array(), $processingOrderStatus = [])
    {
        $frm = new Form('frmOrderComments');
        $frm->addTextArea(Labels::getLabel('FRM_YOUR_COMMENTS', $this->siteLangId), 'comments');
        //$isDigital = isset($orderData['op_product_type']) && $orderData['op_product_type'] == Product::PRODUCT_TYPE_DIGITAL ? OrderStatus::FOR_DIGITAL_ONLY : OrderStatus::FOR_NON_DIGITAL;
        //$orderStatusArr = Orders::getOrderProductStatusArr($this->siteLangId, $processingOrderStatus, $orderData['op_status_id'], $isDigital);
        $orderStatusArr = Orders::getOrderProductStatusArr($this->siteLangId, $processingOrderStatus, $orderData['op_status_id']);

        $fld = $frm->addSelectBox(Labels::getLabel('FRM_STATUS', $this->siteLangId), 'op_status_id', $orderStatusArr, '', [], Labels::getLabel('FRM_SELECT', $this->siteLangId));
        $fld->requirements()->setRequired();

        $frm->addSelectBox(Labels::getLabel('FRM_NOTIFY_CUSTOMER', $this->siteLangId), 'customer_notified', applicationConstants::getYesNoArr($this->siteLangId), '', [], '')->requirements()->setRequired();
        if (array_key_exists('opship_tracking_number', $orderData) && (empty($orderData['opship_tracking_number']) || $orderData['opshipping_plugin_code'] == 'ShipStationShipping') && $orderData['orderstatus_id'] !=  FatApp::getConfig("CONF_DEFAULT_SHIPPING_ORDER_STATUS")) {

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

            $fld->requirements()->addOnChangerequirementUpdate(FatApp::getConfig("CONF_DEFAULT_SHIPPING_ORDER_STATUS"), 'eq', 'manual_shipping', $manualShipReqObj);
            $fld->requirements()->addOnChangerequirementUpdate(FatApp::getConfig("CONF_DEFAULT_SHIPPING_ORDER_STATUS"), 'ne', 'manual_shipping', $manualShipUnReqObj);

            $frm->addTextBox(Labels::getLabel('FRM_TRACKING_NUMBER', $this->siteLangId), 'tracking_number');

            $trackingUnReqObj = new FormFieldRequirement('tracking_number', Labels::getLabel('FRM_TRACKING_NUMBER', $this->siteLangId));
            $trackingUnReqObj->setRequired(false);

            $trackingReqObj = new FormFieldRequirement('tracking_number', Labels::getLabel('FRM_TRACKING_NUMBER', $this->siteLangId));
            $trackingReqObj->setRequired(true);

            $manualFld->requirements()->addOnChangerequirementUpdate(applicationConstants::YES, 'eq', 'tracking_number', $trackingReqObj);
            $manualFld->requirements()->addOnChangerequirementUpdate(applicationConstants::NO, 'eq', 'tracking_number', $trackingUnReqObj);

            $frm->addTextBox(Labels::getLabel('FRM_TRACKING_URL', $this->siteLangId), 'opship_tracking_url');


            $trackingUrlUnReqObj = new FormFieldRequirement('opship_tracking_url', Labels::getLabel('FRM_TRACKING_URL', $this->siteLangId));
            $trackingUrlUnReqObj->setRequired(false);

            $trackingurlReqObj = new FormFieldRequirement('opship_tracking_url', Labels::getLabel('FRM_TRACKING_URL', $this->siteLangId));
            $trackingurlReqObj->setRequired(true);
            $trackingurlReqObj->setRegularExpressionToValidate(ValidateElement::URL_REGEX);
            $trackingurlReqObj->setCustomErrorMessage(Labels::getLabel('FRM_TRACKING_URL_MUST_BE_AN_ABSOLUTE_URL', $this->siteLangId));

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
        $frm->addHiddenField('', 'order_id', 0);
        return $frm;
    }

    public function updateShippingUser()
    {
        $this->objPrivilege->canEditSellerOrders();
        $post = FatApp::getPostedData();
        $opId = FatApp::getPostedData('op_id', FatUtility::VAR_INT, 0);
        if (1 > $opId) {
            LibHelper::exitWithError($this->str_invalid_request, true);
        }

        $srch = new OrderProductSearch($this->siteLangId, true, true);
        $srch->joinPaymentMethod();
        $srch->joinShippingUsers();
        $srch->joinOrderUser();
        $srch->addOrderProductCharges();
        $srch->addCondition('op_id', '=', $opId);
        $srch->addMultipleFields(
            array(
                'order_id',
                'order_pmethod_id',
                'order_date_added',
                'op_id',
                'op_qty',
                'op_unit_price',
                'op_invoice_number',
                'IFNULL(orderstatus_name, orderstatus_identifier) as orderstatus_name',
                'ou.user_name as buyer_user_name',
                'ouc.credential_username as buyer_username',
                'IFNULL(plugin_name, IFNULL(plugin_identifier, "Wallet")) as plugin_name',
                'op_commission_charged',
                'op_commission_percentage',
                'ou.user_name as buyer_name',
                'ouc.credential_username as buyer_username',
                'ouc.credential_email as buyer_email',
                'ou.user_phone_dcode as buyer_phone_dcode',
                'ou.user_phone as buyer_phone',
                'op.op_shop_owner_name',
                'op.op_shop_owner_username',
                'op_l.op_shop_name',
                'op.op_shop_owner_email',
                'op.op_shop_owner_phone_dcode',
                'op.op_shop_owner_phone',
                'op_selprod_title',
                'op_product_name',
                'op_brand_name',
                'op_selprod_options',
                'op_selprod_sku',
                'op_product_model',
                'op_shipping_duration_name',
                'op_shipping_durations',
                'op_status_id',
                'op_other_charges',
                'op_rounding_off',
                'optsu_user_id',
                'op_product_weight',
                'credential_email',
                'plugin_code'
            )
        );
        $srch->doNotCalculateRecords();
        $srch->setPageSize(1);
        $rs = $srch->getResultSet();
        $orderDetail = FatApp::getDb()->fetch($rs);

        if (!$orderDetail) {
            LibHelper::exitWithError($this->str_invalid_request, true);
        }

        $srch = new SearchBase(OrderProduct::DB_TBL_OP_TO_SHIPPING_USERS, 'optosu');
        $srch->doNotCalculateRecords();
        $srch->setPageSize(1);
        $srch->addCondition('optosu.optsu_op_id', '=', $orderDetail['op_id']);
        $rs = $srch->getResultSet();
        $shippingUserRow = FatApp::getDb()->fetch($rs);
        if ($shippingUserRow) {
            LibHelper::exitWithError(Labels::getLabel('ERR_ALREADY_ASSIGNED_TO_SHIPPING_COMPANY_USER', $this->siteLangId), true);
        }

        $frm = $this->getShippingUserForm();
        $post = $frm->getFormDataFromArray($post);

        if (!false === $post) {
            LibHelper::exitWithError(current($frm->getValidationErrors()), true);
        }

        $db = FatApp::getDb();
        $db->startTransaction();

        $data = array('optsu_op_id' => $opId, 'optsu_user_id' => $post['optsu_user_id']);
        if ($orderDetail['optsu_user_id'] == null) {
            $row = $db->insertFromArray(OrderProduct::DB_TBL_OP_TO_SHIPPING_USERS, $data);
        } else {
            $row = $db->updateFromArray(OrderProduct::DB_TBL_OP_TO_SHIPPING_USERS, $data, array('smt' => 'optsu_op_id = ?', 'vals' => array($opId)));
        }

        if (!$row) {
            LibHelper::exitWithError($db->getError(), true);
        }

        /*  $orderObj = new Orders($orderDetail['order_id']);
        $addresses = $orderObj->getOrderAddresses($orderDetail['order_id']);
        $orderDetail['billingAddress'] = $addresses[Orders::BILLING_ADDRESS_TYPE];
        $orderDetail['shippingAddress'] = (!empty($addresses[Orders::SHIPPING_ADDRESS_TYPE])) ? $addresses[Orders::SHIPPING_ADDRESS_TYPE] : $addresses[Orders::BILLING_ADDRESS_TYPE];

        $shopSrch = new ShopSearch(1);
        $shopSrch->joinShopCountry();
        $shopSrch->joinShopState();
        $shopSrch->addCondition('shop_id', '=', 1);
        $shopSrch->addMultipleFields(array('ifnull(country_name,country_code) as country_name', 'ifnull(state_name,state_identifier) as state_name', 'shop_city', 'shop_address_line_1', 'shop_address_line_2'));
        $shopSrch->doNotCalculateRecords();
        $shopSrch->setPageSize(1);
        $rs = $shopSrch->getResultSet();
        $orderDetail['shopDetail'] = FatApp::getDb()->fetch($rs); */

        $srch = new SearchBase(OrderProduct::DB_TBL_OP_TO_SHIPPING_USERS, 'optosu');
        $srch->doNotCalculateRecords();
        $srch->setPageSize(1);
        $srch->addCondition('optosu.optsu_op_id', '=', $orderDetail['op_id']);
        $rs = $srch->getResultSet();
        $shippingUserRow = FatApp::getDb()->fetch($rs);
        if ($shippingUserRow && $orderDetail['plugin_code'] == "CashOnDelivery") {
            $comments = Labels::getLabel('MSG_CASH_WILL_COLLECT_AGAINST_COD_ORDER', $this->siteLangId) . ' ' . $orderDetail['op_invoice_number'];
            $amt = CommonHelper::orderProductAmount($orderDetail);
            $txnObj = new Transactions();
            $txnDataArr = array(
                'utxn_user_id' => $shippingUserRow['optsu_user_id'],
                'utxn_comments' => $comments,
                'utxn_status' => Transactions::STATUS_COMPLETED,
                'utxn_debit' => $amt,
                'utxn_op_id' => $orderDetail['op_id'],
            );
            if (!$txnObj->addTransaction($txnDataArr)) {
                $db->rollbackTransaction();
                LibHelper::exitWithError($txnObj->getError(), true);
            }
        }

        $db->commitTransaction();

        $this->set('msg', $this->str_update_record);
        $this->_template->render(false, false, 'json-success.php');
    }

    public function viewInvoice($opId)
    {
        $this->objPrivilege->canViewSellerOrders();
        $opId = FatUtility::int($opId);
        if (1 > $opId) {
            Message::addErrorMessage(Labels::getLabel('ERR_INVALID_ACCESS', $this->siteLangId));
            CommonHelper::redirectUserReferer();
        }

        $orderObj = new Orders();

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
        $srch->addCondition('op_id', '=', $opId);
        $srch->addStatusCondition(unserialize(FatApp::getConfig("CONF_VENDOR_ORDER_STATUS")));
        $srch->addMultipleFields(array('*', 'shop_country_l.country_name as shop_country_name', 'shop_state_l.state_name as shop_state_name', 'shop_city', 'product_hsn_code'));
        $srch->doNotCalculateRecords();
        $srch->setPageSize(1);
        $rs = $srch->getResultSet();
        $orderDetail = FatApp::getDb()->fetch($rs);

        if (!$orderDetail) {
            Message::addErrorMessage(Labels::getLabel('ERR_INVALID_ACCESS', $this->siteLangId));
            CommonHelper::redirectUserReferer();
        }

        $charges = $orderObj->getOrderProductChargesArr($opId);
        $orderDetail['charges'] = $charges;

        $shippedBySeller = applicationConstants::NO;
        if (CommonHelper::canAvailShippingChargesBySeller($orderDetail['op_selprod_user_id'], $orderDetail['opshipping_by_seller_user_id'])) {
            $shippedBySeller = applicationConstants::YES;
        }

        if (!empty($orderDetail["opship_orderid"])) {
            $shippingApiObj = (new Shipping($this->siteLangId))->getShippingApiObj(($shippedBySeller ? $orderDetail['opshipping_by_seller_user_id'] : 0)) ?? NULL;
            if (!empty($shippingApiObj) && false === $shippingApiObj->loadOrder($orderDetail["opship_orderid"])) {
                Message::addErrorMessage($shippingApiObj->getError());
                FatApp::redirectUser(UrlHelper::generateUrl("SellerOrders"));
            }
            $orderDetail['thirdPartyorderInfo'] = (null != $shippingApiObj ? $shippingApiObj->getResponse() : []);
        }

        $address = $orderObj->getOrderAddresses($orderDetail['op_order_id']);
        $orderDetail['billingAddress'] = (isset($address[Orders::BILLING_ADDRESS_TYPE])) ? $address[Orders::BILLING_ADDRESS_TYPE] : array();
        $orderDetail['shippingAddress'] = (isset($address[Orders::SHIPPING_ADDRESS_TYPE])) ? $address[Orders::SHIPPING_ADDRESS_TYPE] : array();

        $pickUpAddress = $orderObj->getOrderAddresses($orderDetail['op_order_id'], $orderDetail['op_id']);
        $orderDetail['pickupAddress'] = (isset($pickUpAddress[Orders::PICKUP_ADDRESS_TYPE])) ? $pickUpAddress[Orders::PICKUP_ADDRESS_TYPE] : array();

        $opChargesLog = new OrderProductChargeLog($opId);
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
        $pdf->SetTitle(Labels::getLabel('LBL_TAX_INVOICE', $this->siteLangId));
        $pdf->SetSubject(Labels::getLabel('LBL_TAX_INVOICE', $this->siteLangId));

        // set LTR direction for english translation
        $pdf->setRTL(('rtl' == Language::getLayoutDirection($this->siteLangId)));
        // set font
        $pdf->SetFont('dejavusans');

        $templatePath = "orders/view-invoice.php";
        $html = $template->render(false, false, $templatePath, true, true);
        $pdf->writeHTML($html, true, false, true, false, '');
        $pdf->lastPage();

        ob_end_clean();
        // $saveFile = CONF_UPLOADS_PATH . 'demo-pdf.pdf';
        //$pdf->Output($saveFile, 'F');
        $pdf->Output($opId . '-invoice.pdf', 'I');
        return true;
    }

    public function viewBuyerOrderInvoice($orderId, $opId = 0)
    {
        $this->objPrivilege->canViewSellerOrders();
        if (!$orderId) {
            Message::addErrorMessage(Labels::getLabel('ERR_INVALID_ACCESS', $this->siteLangId));
            CommonHelper::redirectUserReferer();
        }

        $opId = FatUtility::int($opId);

        $srch = new OrderProductSearch($this->siteLangId, true, true);
        $srch->joinPaymentMethod();
        $srch->joinSellerProducts();
        $srch->joinProduct();
        $srch->joinShop();
        $srch->joinShopSpecifics();
        $srch->joinShopCountry();
        $srch->joinShopState();
        $srch->addOrderProductCharges();
        $srch->joinShippingCharges();
        $srch->joinOrderProductSpecifics();
        $srch->addCondition('order_id', '=', $orderId);
        if (0 < $opId) {
            $srch->addCondition('op_id', '=', $opId);
        }
        $srch->addMultipleFields(array('*', 'shop_country_l.country_name as shop_country_name', 'shop_state_l.state_name as shop_state_name', 'shop_city', 'product_hsn_code'));
        $childOrderDetail = FatApp::getDb()->fetchAll($srch->getResultSet(), 'op_id');

        if (1 > count($childOrderDetail)) {
            Message::addErrorMessage(Labels::getLabel('ERR_INVALID_ORDER', $this->siteLangId));
            CommonHelper::redirectUserReferer();
        }

        $orderObj = new Orders();
        $orderDetail = $orderObj->getOrderById($orderId, $this->siteLangId);
        $orderDetail['charges'] = $orderObj->getOrderProductChargesByOrderId($orderDetail['order_id']);

        if (count($childOrderDetail)) {
            foreach ($childOrderDetail as &$arr) {
                $arr['options'] = SellerProduct::getSellerProductOptions($arr['op_selprod_id'], true, $this->siteLangId);
            }
        }

        foreach ($childOrderDetail as $op_id => $val) {
            $childOrderDetail[$op_id]['charges'] = $orderDetail['charges'][$op_id];

            $opChargesLog = new OrderProductChargeLog($op_id);
            $taxOptions = $opChargesLog->getData($this->siteLangId);
            $childOrderDetail[$op_id]['taxOptions'] = $taxOptions;
        }

        $address = $orderObj->getOrderAddresses($orderDetail['order_id']);
        $orderDetail['billingAddress'] = $address[Orders::BILLING_ADDRESS_TYPE];
        $orderDetail['shippingAddress'] = (!empty($address[Orders::SHIPPING_ADDRESS_TYPE])) ? $address[Orders::SHIPPING_ADDRESS_TYPE] : array();

        $pickUpAddress = $orderObj->getOrderAddresses($orderDetail['order_id'], $opId);
        $orderDetail['pickupAddress'] = (!empty($pickUpAddress[Orders::PICKUP_ADDRESS_TYPE])) ? $pickUpAddress[Orders::PICKUP_ADDRESS_TYPE] : array();

        $template = new FatTemplate('', '');
        $template->set('siteLangId', $this->siteLangId);
        $template->set('orderDetail', $orderDetail);
        $template->set('childOrderDetail', $childOrderDetail);
        $template->set('opId', $opId);
        $template->set('buyerGstNumber', User::getAttributesById($orderDetail['order_user_id'], 'user_gst_number'));
        $template->set('sellerGstNumber', User::getAttributesById(current($childOrderDetail)['selprod_user_id'], 'user_gst_number'));

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

        $templatePath = "orders/view-buyer-order-invoice.php";
        $html = $template->render(false, false, $templatePath, true, true);
        $pdf->writeHTML($html, true, false, true, false, '');
        $pdf->lastPage();

        ob_end_clean();
        // $saveFile = CONF_UPLOADS_PATH . 'demo-pdf.pdf';
        //$pdf->Output($saveFile, 'F');
        $pdf->Output($orderId . '-buyer-invoice.pdf', 'I');
        return true;
    }

    public function orderTrackingInfo($trackingNumber, $courier, $orderNumber)
    {
        if (empty($trackingNumber) || empty($courier)) {
            LibHelper::exitWithError($this->str_invalid_request, true);
        }
        /*
        $trackingNumber  = '287939467220';
        $courier  = 'FedEx';  
        */

        $shipmentTracking = new ShipmentTracking();
        if (false === $shipmentTracking->init($this->siteLangId)) {
            LibHelper::exitWithError($shipmentTracking->getError(), true);
        }

        $shipmentTracking->createTracking($trackingNumber, $courier, $orderNumber);

        if (false === $shipmentTracking->getTrackingInfo($trackingNumber, $courier)) {
            LibHelper::exitWithError($shipmentTracking->getError(), true);
        }
        $trackingInfo = $shipmentTracking->getResponse();

        $this->set('orderNumber', $orderNumber);
        $this->set('orderId', FatApp::getPostedData('orderId', FatUtility::VAR_INT, 0));
        $this->set('op_id', FatApp::getPostedData('op_id', FatUtility::VAR_INT, 0));

        $this->set('trackingInfo', $trackingInfo);
        $this->set('html', $this->_template->render(false, false, 'orders/order-tracking-info.php', true));
        $this->_template->render(false, false, 'json-success.php', true, false);
    }


    public function setupAdditionalOpAttachment()
    {
        $opId = FatApp::getPostedData('op_id', FatUtility::VAR_INT, 0);
        if (1 > $opId) {
            LibHelper::exitWithError(Labels::getLabel("ERR_INVALID_REQUEST", $this->siteLangId) . __LINE__, true);
        }

        $opSrch = OrderProduct::getSearchObject();

        $opSrch->addCondition('op_id', '=', $opId);
        $opSrch->addCondition('op_product_type', '=', Product::PRODUCT_TYPE_DIGITAL);

        $opSrch->addMultipleFields(['op_status_id', 'op_selprod_user_id']);

        $opSrch->doNotCalculateRecords();
        $opSrch->setPageSize(1);

        $rs = $opSrch->getResultSet();
        $row = FatApp::getDb()->fetch($rs);

        if (!is_array($row)) {
            LibHelper::exitWithError(Labels::getLabel("ERR_INVALID_REQUEST", $this->siteLangId), true);
        }

        if (!DigitalOrderProduct::canAttachMoreFiles($row['op_status_id'])) {
            LibHelper::exitWithError(Labels::getLabel("ERR_INVALID_REQUEST", $this->siteLangId), true);
        }

        if (
            !isset($_FILES['additional_attachment']['tmp_name'])
            || !is_uploaded_file($_FILES['additional_attachment']['tmp_name'])
        ) {
            LibHelper::exitWithError(Labels::getLabel('ERR_Please_select_a_file', $this->siteLangId), true);
        }

        $fileHandlerObj = new AttachedFile();

        if ($fileHandlerObj->saveAttachment(
            $_FILES['additional_attachment']['tmp_name'],
            AttachedFile::FILETYPE_ORDER_PRODUCT_DIGITAL_DOWNLOAD,
            $opId,
            0,
            $_FILES['additional_attachment']['name'],
            -1,
            false,
            0
        )) {
            FatUtility::dieJsonSuccess(Labels::getLabel('LBL_File_uploaded_successfully', $this->siteLangId));
        }

        LibHelper::exitWithError($fileHandlerObj->getError(), true);
    }

    public function changeOrderStatus()
    {
        $this->objPrivilege->canEditSellerOrders();
        $db = FatApp::getDb();
        $db->startTransaction();

        $post = FatApp::getPostedData();
        if (!isset($post['op_id'])) {
            LibHelper::exitWithError($this->str_invalid_request, true);
        }

        $opId = FatUtility::int($post['op_id']);
        if (1 > $opId) {
            LibHelper::exitWithError($this->str_invalid_request, true);
        }

        $status = FatApp::getPostedData('op_status_id', FatUtility::VAR_INT, 0);
        $manualShipping = FatApp::getPostedData('manual_shipping', FatUtility::VAR_INT, 0);
        $trackingNumber = FatApp::getPostedData('tracking_number', FatUtility::VAR_STRING, '');
        $shippedByPlugin = FatApp::getPostedData('shipped_by_plugin', FatUtility::VAR_INT, 0);

        $oCancelRequestSrch = new OrderCancelRequestSearch();
        $oCancelRequestSrch->doNotCalculateRecords();
        $oCancelRequestSrch->setPageSize(1);
        $oCancelRequestSrch->addCondition('ocrequest_op_id', '=', $opId);
        $oCancelRequestSrch->addCondition('ocrequest_status', '!=', OrderCancelRequest::CANCELLATION_REQUEST_STATUS_DECLINED);
        $oCancelRequestRs = $oCancelRequestSrch->getResultSet();
        if (FatApp::getDb()->fetch($oCancelRequestRs)) {
            LibHelper::exitWithError(Labels::getLabel('ERR_Cancel_request_is_submitted_for_this_order', $this->siteLangId), true);
        }

        $orderObj = new Orders();

        $srch = new OrderProductSearch($this->siteLangId, true, true);
        $srch->joinOrderProductShipment();
        $srch->joinPaymentMethod();
        $srch->joinShippingUsers();
        //$srch->joinSellerProducts();
        $srch->joinShippingCharges();
        $srch->joinTable(Plugin::DB_TBL, 'LEFT OUTER JOIN', 'ops.opshipping_plugin_id = ops_plugin.plugin_id', 'ops_plugin');
        $srch->joinOrderUser();
        $srch->addCondition('op_id', '=', $opId);
        $srch->addMultipleFields(['op.*', 'pm.*', 'order_id', 'order_language_id', 'order_payment_status', 'ops_plugin.plugin_code as opshipping_plugin_code', 'opshipping_by_seller_user_id', 'op_selprod_user_id', 'opshipping_carrier_code', 'optsu_user_id', 'opship_tracking_number', 'orderstatus_id']);
        $srch->doNotCalculateRecords();
        $srch->setPageSize(1);
        $orderDetail = FatApp::getDb()->fetch($srch->getResultSet());

        if (empty($orderDetail)) {
            LibHelper::exitWithError($this->str_invalid_request, true);
        }

        $shippingHanldedBySeller = CommonHelper::canAvailShippingChargesBySeller($orderDetail['op_selprod_user_id'], $orderDetail['opshipping_by_seller_user_id']);
        $shippingObj = new Shipping($this->siteLangId);
        $shippingApiObj = $shippingObj->getShippingApiObj(($shippingHanldedBySeller ? $orderDetail['opshipping_by_seller_user_id'] : 0)) ?? NULL;

        if ($status ==  FatApp::getConfig("CONF_DEFAULT_SHIPPING_ORDER_STATUS") && empty($trackingNumber) && 1 > $manualShipping && empty($shippingApiObj)) {
            LibHelper::exitWithError(Labels::getLabel('ERR_PLEASE_SELECT_SELF_SHIPPING', $this->siteLangId), true);
        }

        if ($orderDetail['plugin_code'] == 'CashOnDelivery') {
            $processingStatuses = $orderObj->getAdminAllowedUpdateOrderStatuses(true, $orderDetail['op_product_type']);
        } else if ($orderDetail['plugin_code'] == 'PayAtStore') {
            $processingStatuses = $orderObj->getAdminAllowedUpdateOrderStatuses(false, $orderDetail['op_product_type'], true);
        } else {
            $processingStatuses = $orderObj->getAdminAllowedUpdateOrderStatuses(false, $orderDetail['op_product_type']);
        }

        $frm = $this->getOrderCommentsForm($orderDetail, $processingStatuses);
        if (1 == $shippedByPlugin) {
            $fld = $frm->getField('op_status_id');
            $fld->requirements()->removeOnChangerequirementUpdate(FatApp::getConfig("CONF_DEFAULT_SHIPPING_ORDER_STATUS", FatUtility::VAR_INT), 'eq');
        }
        $post = $frm->getFormDataFromArray($post);
        if (!false === $post) {
            LibHelper::exitWithError(current($frm->getValidationErrors()), true);
        }

        $restrictOrderStatusChange = array_merge(
            (array) FatApp::getConfig("CONF_DEFAULT_SHIPPING_ORDER_STATUS"),
            (array) FatApp::getConfig("CONF_DEFAULT_DEIVERED_ORDER_STATUS"),
            (array) unserialize(FatApp::getConfig("CONF_COMPLETED_ORDER_STATUS"))
        );

        if (!CommonHelper::canAvailShippingChargesBySeller($orderDetail['op_selprod_user_id'], $orderDetail['opshipping_by_seller_user_id']) && !$orderDetail['optsu_user_id'] && in_array($post["op_status_id"], $restrictOrderStatusChange) && $orderDetail['op_product_type'] == Product::PRODUCT_TYPE_PHYSICAL) {
            LibHelper::exitWithError(Labels::getLabel('ERR_PLEASE_ASSIGN_SHIPPING_USER', $this->siteLangId), true);
        }

        if (in_array($orderDetail["op_status_id"], $processingStatuses) && in_array($post["op_status_id"], $processingStatuses)) {
            $trackingCourierCode = '';
            $opship_tracking_url = FatApp::getPostedData('opship_tracking_url', FatUtility::VAR_STRING, '');
            $activatedTrackPluginId = (new Plugin())->getDefaultPluginData(Plugin::TYPE_SHIPMENT_TRACKING, 'plugin_id') ?? 0;
            if ($post["op_status_id"] == FatApp::getConfig("CONF_DEFAULT_SHIPPING_ORDER_STATUS")) {

                if (0 < $manualShipping) {
                    $updateData = [
                        'opship_op_id' => FatApp::getPostedData('op_id', FatUtility::VAR_INT),
                        "opship_tracking_number" => $post['tracking_number'],
                    ];

                    if (!empty($opship_tracking_url)) {
                        $updateData['opship_tracking_url'] =  $opship_tracking_url;
                    }
                    $oshistory_courier = FatApp::getPostedData('oshistory_courier', FatUtility::VAR_STRING, '');
                    if (!empty($oshistory_courier)) {
                        $trackingCourierCode = $oshistory_courier;
                        $updateData['opship_tracking_courier_code'] = $oshistory_courier;
                        $updateData['opship_tracking_plugin_id'] = $activatedTrackPluginId;
                    }
                    if (!FatApp::getDb()->insertFromArray(OrderProductShipment::DB_TBL, $updateData, false, array(), $updateData)) {
                        LibHelper::exitWithError(FatApp::getDb()->getError(), true);
                    }
                } else {
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
                                LibHelper::exitWithError(FatApp::getDb()->getError(), true);
                            }
                        }
                    }
                }
            }

            $trackingNumber = FatApp::getPostedData("tracking_number", FatUtility::VAR_STRING, '');
            if (!$orderObj->addChildProductOrderHistory($opId, $orderDetail["order_language_id"], $post["op_status_id"], $post["comments"], $post["customer_notified"], $trackingNumber, 0, true, $trackingCourierCode, $opship_tracking_url)) {
                LibHelper::exitWithError($this->str_invalid_request, true);
            }
        } else {
            LibHelper::exitWithError($this->str_invalid_request, true);
        }

        if (isset($orderDetail['plugin_code']) && in_array(strtolower($orderDetail['plugin_code']), ['cashondelivery', 'payatstore']) && (FatApp::getConfig("CONF_DEFAULT_DEIVERED_ORDER_STATUS") == $post["op_status_id"] || FatApp::getConfig("CONF_DEFAULT_COMPLETED_ORDER_STATUS") == $post["op_status_id"]) && Orders::ORDER_PAYMENT_PAID != $orderDetail['order_payment_status']) {
            $orderProducts = new OrderProductSearch($this->siteLangId, true, true);
            $orderProducts->joinPaymentMethod();
            $orderProducts->addMultipleFields(['op_status_id']);
            $orderProducts->addCondition('op_order_id', '=', $orderDetail['order_id']);
            $orderProducts->addCondition('op_status_id', '!=', FatApp::getConfig("CONF_DEFAULT_DEIVERED_ORDER_STATUS"));
            $orderProducts->addCondition('op_status_id', '!=', FatApp::getConfig("CONF_DEFAULT_COMPLETED_ORDER_STATUS"));
            $rs = $orderProducts->getResultSet();
            if ($rs) {
                $childOrders = FatApp::getDb()->fetchAll($rs);
                if (empty($childOrders)) {
                    $updateArray = array('order_payment_status' => Orders::ORDER_PAYMENT_PAID);
                    $whr = array('smt' => 'order_id = ?', 'vals' => array($orderDetail['order_id']));
                    if (!FatApp::getDb()->updateFromArray(Orders::DB_TBL, $updateArray, $whr)) {
                        LibHelper::exitWithError(Labels::getLabel('ERR_Invalid_Access', $this->siteLangId), true);
                    }
                }
                if (!empty($orderDetail['order_discount_coupon_code'])) {
                    $srch = DiscountCoupons::getSearchObject();
                    $srch->addFld('coupon_id');
                    $srch->doNotCalculateRecords();
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
        }

        $db->commitTransaction();
        CalculativeDataRecord::updateOrderCancelRequestCount();
        $this->set('msg', $this->str_update_record);
        $this->_template->render(false, false, 'json-success.php');
    }

    public function orderProductsCharges($orderId, int $chargeType = 0)
    {
        $opSrch = new OrderProductSearch($this->siteLangId, true, true, true);
        $opSrch->joinShippingCharges();
        $opSrch->joinSellerProducts();
        $opSrch->joinAddress();
        $opSrch->joinOrderProductShipment();
        $opSrch->addCountsOfOrderedProducts();
        $opSrch->addOrderProductCharges();
        $opSrch->joinOrderProductSpecifics();
        $opSrch->doNotCalculateRecords();
        $opSrch->doNotLimitRecords();
        $opSrch->addCondition('op.op_order_id', '=', $orderId);

        $opSrch->addMultipleFields(
            [
                'order_id',
                'order_number',
                'order_date_added',
                'op_id',
                'op_selprod_user_id',
                'op_invoice_number',
                'op_selprod_title',
                'op_product_name',
                'op_qty',
                'op_brand_name',
                'op_selprod_options',
                'op_selprod_sku',
                'op_product_model',
                'op_shop_name',
                'op_shop_owner_name',
                'op_shop_owner_email',
                'op_shop_owner_phone',
                'op_unit_price',
                'totCombinedOrders as totOrders',
                'op_shipping_duration_name',
                'op_shipping_durations',
                'IFNULL(orderstatus_name, orderstatus_identifier) as orderstatus_name',
                'op_other_charges',
                'op_product_tax_options',
                'ops.*',
                'opship.*',
                'opr_response',
                'addr.*',
                'ts.state_code',
                'tc.country_code',
                'op_rounding_off',
                'op_shop_owner_phone_dcode',
                'op_selprod_price',
                'op_special_price',
                'opshipping_by_seller_user_id',
                'op_is_batch',
                'op_selprod_id',
                'selprod_product_id'
            ]
        );
        $opSrch->addOrder('op_selprod_user_id');
        $opsShippingDetail = FatApp::getDb()->fetchAll($opSrch->getResultSet());

        $oObj = new Orders();
        foreach ($opsShippingDetail as &$op) {
            $charges = $oObj->getOrderProductChargesArr($op['op_id']);
            $op['charges'] = $charges;
            if ($chargeType ==  OrderProduct::CHARGE_TYPE_TAX) {
                $opChargesLog = new OrderProductChargeLog($op['op_id']);
                $op['taxOptions'] = $opChargesLog->getData($this->siteLangId);
            }
        }


        $this->set('opsShippingDetail', $opsShippingDetail);
        switch ($chargeType) {
            case OrderProduct::CHARGE_TYPE_SHIPPING:
                $this->set('html', $this->_template->render(false, false, 'orders/order-products-shipping.php', true));
                break;
            case OrderProduct::CHARGE_TYPE_TAX:
                $this->set('html', $this->_template->render(false, false, 'orders/order-products-tax.php', true));
                break;
            case OrderProduct::CHARGE_TYPE_VOLUME_DISCOUNT:
                $this->set('html', $this->_template->render(false, false, 'orders/order-products-vol-discount.php', true));
                break;
            case OrderProduct::CHARGE_TYPE_REWARD_POINT_DISCOUNT:
                $this->set('html', $this->_template->render(false, false, 'orders/order-products-rewards.php', true));
                break;

            default:
                LibHelper::exitWithError(Labels::getLabel('ERR_INVALID_CHARGE_TYPE', $this->siteLangId), true);
                break;
        }
        $this->_template->render(false, false, 'json-success.php', true, false);
    }

    public function updatePayment()
    {
        $this->objPrivilege->canEditOrders();
        $frm = $this->getPaymentForm($this->siteLangId);

        $post = $frm->getFormDataFromArray(FatApp::getPostedData());
        if (false === $post) {
            LibHelper::exitWithError(current($frm->getValidationErrors()), true);
        }

        $orderId = $post['opayment_order_id'];

        if ($orderId == '' || $orderId == null) {
            LibHelper::exitWithError($this->str_invalid_request, true);
        }

        $srch = new OrderSearch($this->siteLangId);
        $srch->joinOrderPaymentMethod();
        $srch->addMultipleFields(array('plugin_code'));
        $srch->addCondition('order_id', '=', $orderId);
        $srch->addCondition('order_type', '=', Orders::ORDER_PRODUCT);
        $srch->doNotCalculateRecords();
        $srch->setPageSize(1);
        $rs = $srch->getResultSet();
        $order = FatApp::getDb()->fetch($rs);
        if (!empty($order) && array_key_exists('plugin_code', $order) && 'CashOnDelivery' == $order['plugin_code']) {
            LibHelper::exitWithError(Labels::getLabel('ERR_COD_ORDERS_ARE_NOT_ELIGIBLE_FOR_PAYMENT_STATUS_UPDATE', $this->siteLangId), true);
        }

        $orderPaymentObj = new OrderPayment($orderId, $this->siteLangId);
        if (!$orderPaymentObj->addOrderPayment($post["opayment_method"], $post['opayment_gateway_txn_id'], $post["opayment_amount"], $post["opayment_comments"])) {
            LibHelper::exitWithError($orderPaymentObj->getError(), true);
        }

        $this->set('msg', Labels::getLabel('MSG_PAYMENT_DETAILS_ADDED_SUCCESSFULLY', $this->siteLangId));
        $this->_template->render(false, false, 'json-success.php');
    }

    public function approvePayment(int $orderPaymentId)
    {
        $orderObj = new Orders();
        $result = current($orderObj->getOrderPayments(['id' => $orderPaymentId]));
        if (!empty($result)) {
            $orderDetail = $orderObj->getOrderById($result['opayment_order_id'], $this->siteLangId);
            $paymentAmount = $orderDetail['order_net_amount'];
            $paidAmount = $orderObj->getOrderPaymentPaid($result['opayment_order_id']);

            $db = FatApp::getDb();
            $db->startTransaction();

            if (($paidAmount + $result['opayment_amount']) >= $paymentAmount) {
                if (!$db->updateFromArray(
                    Orders::DB_TBL,
                    array('order_payment_status' => Orders::ORDER_PAYMENT_PAID, 'order_date_updated' => date('Y-m-d H:i:s')),
                    array('smt' => 'order_id = ? ', 'vals' => array($result['opayment_order_id']))
                )) {
                    $db->rollbackTransaction();
                    LibHelper::exitWithError($db->getError(), true);
                }
            }

            if (!$db->updateFromArray(
                Orders::DB_TBL_ORDER_PAYMENTS,
                array('opayment_txn_status' => Orders::ORDER_PAYMENT_PAID),
                array('smt' => 'opayment_id = ? ', 'vals' => [$orderPaymentId])
            )) {
                $db->rollbackTransaction();
                LibHelper::exitWithError($db->getError(), true);
            }

            $orderObj = new Orders();
            $orderProducts = $orderObj->getChildOrders(['order_id' => $result['opayment_order_id']], langId: $this->siteLangId);

            $storeOrderNetAmount = 0;
            foreach ($orderProducts as $op) {
                $storeOrderNetAmount += CommonHelper::orderProductAmount($op);
            }

            if ($storeOrderNetAmount <= OrderPayment::getApprovedAmountTotal($result['opayment_order_id'])) {
                if (!$db->updateFromArray(
                    Orders::DB_TBL_ORDER_PRODUCTS,
                    array('op_status_id' => FatApp::getConfig("CONF_DEFAULT_PAID_ORDER_STATUS")),
                    array('smt' => 'op_order_id = ?', 'vals' => [$result['opayment_order_id']])
                )) {
                    $db->rollbackTransaction();
                    LibHelper::exitWithError($db->getError(), true);
                }
            }

            $userObj = new User($orderDetail['order_user_id']);
            $vars = $userObj->getUserInfo(['user_name', 'user_phone_dcode', 'user_phone', 'credential_email'], false, false, true);
            $vars += array(
                'txn_status' => Labels::getLabel('LBL_APPROVED', $this->siteLangId),
                'order_id' => $result['opayment_order_id'],
            );
            $vars += $orderDetail;

            $emailObj = new EmailHandler();
            $emailObj->sendTransferBankActionNotification($this->siteLangId, $vars);
        }

        $db->commitTransaction();
        $this->set('msg', Labels::getLabel("MSG_APPROVED", $this->siteLangId));
        $this->_template->render(false, false, 'json-success.php');
    }

    public function rejectPayment(int $orderPaymentId)
    {
        $orederObj = new Orders();
        $result = current($orederObj->getOrderPayments(['id' => $orderPaymentId]));
        if (!empty($result)) {
            $db = FatApp::getDb();
            $db->startTransaction();

            if (!$db->updateFromArray(
                Orders::DB_TBL_ORDER_PAYMENTS,
                array('opayment_txn_status' => Orders::ORDER_PAYMENT_CANCELLED),
                array('smt' => 'opayment_id = ? ', 'vals' => array($orderPaymentId))
            )) {
                $db->rollbackTransaction();
                LibHelper::exitWithError($db->getError(), true);
            }

            $orderData = Orders::getAttributesById($result['opayment_order_id'], ['order_number', 'order_net_amount', 'order_user_id']);
            $userObj = new User($orderData['order_user_id']);
            $vars = $userObj->getUserInfo(['user_name', 'user_phone_dcode', 'user_phone', 'credential_email'], false, false, true);
            $vars += array(
                'txn_status' => Labels::getLabel('MSG_REJECTED', $this->siteLangId),
                'order_id' => $result['opayment_order_id'],
            );
            $vars += $orderData;

            $emailObj = new EmailHandler();
            $emailObj->sendTransferBankActionNotification($this->siteLangId, $vars);
        }
        $db->commitTransaction();
        $this->set('msg', Labels::getLabel("MSG_REJECTED", $this->siteLangId));
        $this->_template->render(false, false, 'json-success.php');
    }

    public function itemAutoComplete()
    {
        $pagesize = 20;
        $page = FatApp::getPostedData('page', FatUtility::VAR_INT, 1);
        if ($page < 2) {
            $page = 1;
        }

        $srch = new OrderProductSearch($this->siteLangId, true, true, true);
        $srch->addMultipleFields(['op_id as id', 'CONCAT("#", op_invoice_number, " | ", op_selprod_title) as text']);

        $isReturnOrder = FatApp::getPostedData('return_order', FatUtility::VAR_INT, 0);
        if (0 < $isReturnOrder) {
            $srch->joinTable(OrderReturnRequest::DB_TBL, 'INNER JOIN', 'op.op_id = orr.orrequest_op_id', 'orr');
        }

        $keyword = FatApp::getPostedData('keyword', FatUtility::VAR_STRING, '');
        if (!empty($keyword)) {
            $cnd = $srch->addCondition('op_product_name', 'LIKE', "%" . $keyword . "%");
            $cnd->attachCondition('op_selprod_title', 'LIKE', "%" . $keyword . "%");
            $cnd->attachCondition('op_selprod_options', 'LIKE', "%" . $keyword . "%");
            $cnd->attachCondition('op_brand_name', 'LIKE', "%" . $keyword . "%");
            $cnd->attachCondition('op_shop_name', 'LIKE', "%" . $keyword . "%");
        }

        $srch->addGroupby('op_id');
        $srch->setPageNumber($page);
        $srch->setPageSize($pagesize);
        $srch->addOrder('op_id', 'DESC');

        $result = FatApp::getDb()->fetchAll($srch->getResultSet());

        $json = array(
            'pageCount' => $srch->pages(),
            'results' => $result
        );

        die(FatUtility::convertToJson($json));
    }

    private static function attachmentForm(int $recordId, int $langId, bool $bothTypesAvailable = true)
    {
        $frm = new Form('frmDownload');
        if (true === $bothTypesAvailable) {
            $digitalDownloadTypeArr = applicationConstants::digitalDownloadTypeArr($langId);
            $frm->addSelectBox(Labels::getLabel('FRM_DIGITAL_DOWNLOAD_TYPE', $langId), 'download_type', $digitalDownloadTypeArr, '', array('class' => 'download-type'), '')->requirements()->setRequired();
        }

        $frm->addHiddenField('', 'record_id', $recordId);
        return $frm;
    }

    public function viewAttachments(int $opId)
    {
        $productType = OrderProduct::getAttributesById($opId, 'op_product_type');
        if ($productType != Product::PRODUCT_TYPE_DIGITAL) {
            LibHelper::exitWithError(Labels::getLabel('LBL_INVALID_PRODUCT_TYPE', $this->siteLangId), true);
        }
        $digitalDownloads = Orders::getOrderProductDigitalDownloads($opId);
        $digitalDownloadLinks = Orders::getOrderProductDigitalDownloadLinks($opId);
        $frm = $this->attachmentForm($opId, $this->siteLangId, (!empty($digitalDownloads) && !empty($digitalDownloadLinks)));

        $this->set("digitalDownloads", $digitalDownloads);
        $this->set("digitalDownloadLinks", $digitalDownloadLinks);
        $this->set("frm", $frm);
        $this->set("formTitle", Labels::getLabel('LBL_ATTACHMENTS'));

        $this->set('html', $this->_template->render(false, false, NULL, true));
        $this->_template->render(false, false, 'json-success.php', true, false);
    }

    public function downloadOpAttachment($aFileId, $recordId)
    {
        $aFileId = FatUtility::int($aFileId);
        $recordId = FatUtility::int($recordId);
        $fileType = AttachedFile::FILETYPE_ORDER_PRODUCT_DIGITAL_DOWNLOAD;

        if (1 > $aFileId || 1 > $recordId) {
            LibHelper::exitWithError($this->str_invalid_request_id, false, true);
            CommonHelper::redirectUserReferer();
        }

        $file_row = AttachedFile::getAttributesById($aFileId);
        if ($file_row == false || $file_row['afile_record_id'] != $recordId || $file_row['afile_type'] != $fileType) {
            LibHelper::exitWithError(Labels::getLabel("ERR_INVALID_ACCESS", $this->siteLangId), false, true);
            FatApp::redirectUser(UrlHelper::generateUrl('Orders', 'view', array($recordId)));
        }

        if (!file_exists(CONF_UPLOADS_PATH . $file_row['afile_physical_path'])) {
            LibHelper::exitWithError(Labels::getLabel('LBL_FILE_NOT_FOUND', $this->siteLangId), false, true);
            FatApp::redirectUser(UrlHelper::generateUrl('Orders', 'view', array($recordId)));
        }

        $fileName = isset($file_row['afile_physical_path']) ? $file_row['afile_physical_path'] : '';
        AttachedFile::downloadAttachment($fileName, $file_row['afile_name']);
    }

    public function viewPaymemntGatewayResponse()
    {
        $orderId = FatApp::getPostedData('order_id', FatUtility::VAR_INT, 0);
        $oPayment = new OrderPayment($orderId);
        $response = $oPayment->getPaymentGatewayResponse();
        $this->set('response', $response);
        $this->set('html', $this->_template->render(false, false, NULL, true));
        $this->_template->render(false, false, 'json-success.php', true, false);
    }

    private function getOpCancelForm(): Form
    {
        $frm = new Form('frmOrderProductCancel');
        $frm->addHiddenField('', 'op_id');
        $fld = $frm->addTextArea(Labels::getLabel('FRM_CANCELLATION_REASON', $this->siteLangId), 'comments');
        $fld->requirements()->setRequired(true);
        return $frm;
    }

    public function getCancelOrderProductForm(int $recordId)
    {
        $this->objPrivilege->canEditSellerOrders($this->admin_id);
        $invoiceNumber = OrderProduct::getAttributesById($recordId, 'op_invoice_number');
        $opRow = OrderProduct::getAttributesByLangId($this->siteLangId, $recordId, ['op_status_id', 'op_order_id', 'op_selprod_title', 'op_product_name'], applicationConstants::JOIN_INNER);
        $orderProductName = empty($opRow['op_selprod_title']) ? $opRow['op_selprod_title'] : $opRow['op_product_name'];
        if (false == $invoiceNumber) {
            LibHelper::exitWithError(Labels::getLabel('LBL_INVALID_ORDER_PRODUCT.', $this->siteLangId), true);
        }

        if (false !== OrderCancelRequest::getCancelRequestById($recordId)) {
            LibHelper::exitWithError(Labels::getLabel('ERR_USER_HAVE_ALREADY_SENT_THE_CANCELLATION_REQUEST_FOR_THIS_ORDER', $this->siteLangId), true);
        }

        $orderObj = new Orders($opRow['op_order_id']);
        $notAllowedStatues = $orderObj->getNotAllowedOrderCancellationStatuses();
        if (in_array($opRow["op_status_id"], $notAllowedStatues)) {
            $orderStatuses = Orders::getOrderProductStatusArr($this->siteLangId);
            $lbl = Labels::getLabel('LBL_THIS_ORDER_ALREADY_{STATUS}.', $this->siteLangId);
            $lbl = CommonHelper::replaceStringData($lbl, ['{STATUS}' => $orderStatuses[$opRow["op_status_id"]]]);
            LibHelper::exitWithError($lbl, true);
        }

        $frm = $this->getOpCancelForm();
        $frm->fill(['op_id' => $recordId]);

        $this->set('recordId', $recordId);
        $this->set('orderProductName', $orderProductName);
        $this->set('invoiceNumber', $invoiceNumber);
        $this->set('frm', $frm);
        $this->set('html', $this->_template->render(false, false, NULL, true));
        $this->_template->render(false, false, 'json-success.php', true, false);
    }

    public function cancelOrderProduct()
    {
        $frm = $this->getOpCancelForm($this->siteLangId);
        $post = $frm->getFormDataFromArray(FatApp::getPostedData());

        if (!false === $post) {
            Message::addErrorMessage(current($frm->getValidationErrors()));
            LibHelper::exitWithError(Message::getHtml());
        }

        $op_id = FatUtility::int($post['op_id']);
        if (1 > $op_id) {
            Message::addErrorMessage(Labels::getLabel('MSG_Invalid_access', $this->siteLangId));
            LibHelper::exitWithError(Message::getHtml());
        }

        if (false !== OrderCancelRequest::getCancelRequestById($op_id)) {
            Message::addErrorMessage(Labels::getLabel('MSG_User_have_already_sent_the_cancellation_request_for_this_order', $this->siteLangId));
            CommonHelper::redirectUserReferer();
        }

        $srch = new OrderProductSearch($this->siteLangId, true, true);
        $srch->joinOrderUser();
        $srch->addCondition('op_id', '=', $op_id);
        $orderDetail = (array)FatApp::getDb()->fetch($srch->getResultSet());

        if (empty($orderDetail)) {
            LibHelper::exitWithError(Labels::getLabel('MSG_Invalid_Access', $this->siteLangId));
        }

        $orderObj = new Orders();
        $notAllowedStatues = $orderObj->getNotAllowedOrderCancellationStatuses();

        if (in_array($orderDetail["op_status_id"], $notAllowedStatues)) {
            $orderStatuses = Orders::getOrderProductStatusArr($this->siteLangId);
            $lbl = Labels::getLabel('LBL_THIS_ORDER_ALREADY_{STATUS}.', $this->siteLangId);
            $lbl = CommonHelper::replaceStringData($lbl, ['{STATUS}' => $orderStatuses[$orderDetail["op_status_id"]]]);
            LibHelper::exitWithError($lbl);
        }

        if (!$orderObj->addChildProductOrderHistory($op_id, $this->siteLangId, FatApp::getConfig("CONF_DEFAULT_CANCEL_ORDER_STATUS"), $post["comments"], true)) {
            LibHelper::exitWithError(Labels::getLabel('MSG_ERROR_INVALID_REQUEST', $this->siteLangId));
        }

        $pluginKey = Plugin::getAttributesById($orderDetail['order_pmethod_id'], 'plugin_code');

        $paymentMethodObj = new PaymentMethods();
        if (true === $paymentMethodObj->canRefundToCard($pluginKey, $this->siteLangId)) {
            if (false == $paymentMethodObj->initiateRefund($orderDetail, PaymentMethods::REFUND_TYPE_CANCEL)) {
                LibHelper::exitWithError($paymentMethodObj->getError());
            }

            $resp = $paymentMethodObj->getResponse();
            if (empty($resp)) {
                LibHelper::exitWithError(Labels::getLabel('LBL_UNABLE_TO_PLACE_GATEWAY_REFUND_REQUEST', $this->siteLangId));
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

        $this->set('order_id', $orderDetail["op_order_id"]);
        $this->set('msg', Labels::getLabel('MSG_ORDER_CANCELLED.', $this->siteLangId));
        $this->_template->render(false, false, 'json-success.php');
    }
}
