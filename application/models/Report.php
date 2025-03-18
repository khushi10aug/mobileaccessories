<?php

/**
 * Report
 */
class Report extends SearchBase
{
    private $langId;
    private $ordersTableJoined;
    private $attr = [];
    private $shopSpecific = false;
    public const MAX_LIMIT = 5000;

    /**
     * __construct
     *
     * @param  int $langId
     * @param  array $attr
     * @param  bool $shopSpecific
     * @return void
     */
    public function __construct(int $langId = 0, array $attr = [], bool $shopSpecific = false)
    {
        parent::__construct(Orders::DB_TBL_ORDER_PRODUCTS, 'op');
        $this->langId = FatUtility::int($langId);
        $this->ordersTableJoined = false;
        $this->shopSpecific = $shopSpecific;
        $this->setFields($attr, $shopSpecific);
    }

    /**
     * joinSettings
     *
     * @return object
     */
    public function joinSettings(): object
    {
        $this->joinTable(OrderProduct::DB_TBL_SETTINGS, 'LEFT OUTER JOIN', 'op.op_id = opst.opsetting_op_id', 'opst');
        return $this;
    }

    /**
     * joinOrders
     *
     * @return object
     */
    public function joinOrders(): object
    {
        if ($this->ordersTableJoined) {
            trigger_error('Orders Table is already joined', E_USER_ERROR);
        }
        $this->joinTable(Orders::DB_TBL, 'INNER JOIN', 'o.order_id = op.op_order_id', 'o');
        $this->ordersTableJoined = true;
        return $this;
    }

    /**
     * joinPaymentMethod
     *
     * @param  int $langId
     * @return object
     */
    public function joinPaymentMethod(int $langId = 0): object
    {
        if (1 > $langId) {
            $langId = $this->langId;
        }

        if (!$this->ordersTableJoined) {
            trigger_error('Please use joinOrders() first, then try to join joinPaymentMethod()', E_USER_ERROR);
        }

        $this->joinTable(Plugin::DB_TBL, 'LEFT OUTER JOIN', 'o.order_pmethod_id = pm.plugin_id', 'pm');
        if ($langId) {
            $this->joinTable(Plugin::DB_TBL_LANG, 'LEFT OUTER JOIN', 'pm.plugin_id = pm_l.pluginlang_plugin_id AND pm_l.pluginlang_lang_id = ' . $langId, 'pm_l');
        }
        return $this;
    }

    /**
     * joinSellerUser
     *
     * @return object
     */
    public function joinSellerUser(): object
    {
        $this->joinTable(User::DB_TBL, 'LEFT OUTER JOIN', 'seller.user_id = op.op_selprod_user_id', 'seller');
        $this->joinTable(User::DB_TBL_CRED, 'LEFT OUTER JOIN', 'seller.user_id = credential_user_id', 'seller_cred');
        return $this;
    }

    /**
     * joinBuyerUser
     *
     * @return object
     */
    public function joinBuyerUser(): object
    {
        $this->joinTable(User::DB_TBL, 'LEFT OUTER JOIN', 'buyer.user_id = o.order_user_id', 'buyer');
        $this->joinTable(User::DB_TBL_CRED, 'LEFT OUTER JOIN', 'buyer.user_id = credential_user_id', 'buyer_cred');
        return $this;
    }

    /**
     * joinOtherCharges
     *
     * @param  bool $bifurcationCharges
     * @param  array $excludeType
     * @return object
     */
    public function joinOtherCharges(bool $bifurcationCharges = false, array $excludeType = []): object
    {
        $ocSrch = new SearchBase(OrderProduct::DB_TBL_CHARGES, 'opc');
        $ocSrch->doNotCalculateRecords();
        $ocSrch->doNotLimitRecords();
        $ocSrch->addGroupBy('opc.opcharge_op_id');
        $ocSrch->addMultipleFields(['opcharge_op_id', 'sum(opc.opcharge_amount) as op_other_charges']);
        if ($bifurcationCharges) {
            $ocSrch->addFld('SUM(CASE WHEN opc.opcharge_amount<0 THEN opc.opcharge_amount ELSE 0 END) as opDiscountCharges');
            $ocSrch->addFld('SUM(CASE WHEN opc.opcharge_amount>0 THEN opc.opcharge_amount ELSE 0 END) as opNonDiscountCharges');
        }

        if (!empty($excludeType)) {
            $ocSrch->addCondition('opcharge_type', 'not in', $excludeType);
        }

        if (empty($excludeType) && $this->shopSpecific) {
            $excludeType = [
                OrderProduct::CHARGE_TYPE_TAX,
                OrderProduct::CHARGE_TYPE_SHIPPING,
                OrderProduct::CHARGE_TYPE_DISCOUNT,
                OrderProduct::CHARGE_TYPE_REWARD_POINT_DISCOUNT
            ];
            $ocSrch->addCondition('opcharge_type', 'not in', $excludeType);
        }

        $this->joinTable('(' . $ocSrch->getQuery() . ')', 'LEFT OUTER JOIN', 'op.op_id = opcc.opcharge_op_id', 'opcc');
        return $this;
    }

    /**
     * joinOrderProductCharges
     *
     * @param  int $type
     * @param  string $alias
     * @return object
     */
    public function joinOrderProductCharges(int $type, string $alias = 'opc_temp'): object
    {
        $this->joinTable(OrderProduct::DB_TBL_CHARGES, 'LEFT OUTER JOIN', $alias . '.opcharge_op_id = op.op_id and ' . $alias . '.opcharge_type = ' . $type, $alias);
        return $this;
    }

    /**
     * joinOrderProductTaxCharges
     *
     * @param  bool $includeFlds
     * @return object
     */
    public function joinOrderProductTaxCharges($includeFlds = true): object
    {
        $this->joinOrderProductCharges(OrderProduct::CHARGE_TYPE_TAX, 'optax');
        $this->joinSettings();
        if (true === $includeFlds) {
            $this->addFld('(SUM(IFNULL(optax.opcharge_amount,0))) as taxTotal');
            $this->addFld('SUM(if(opst.op_tax_collected_by_seller > 0,IFNULL(optax.opcharge_amount,0),0)) as sellerTaxTotal');
            $this->addFld('SUM(if(opst.op_tax_collected_by_seller = 0,IFNULL(optax.opcharge_amount,0),0)) as adminTaxTotal');
        }
        return $this;
    }

    /**
     * joinOrderProductShipCharges
     *
     * @param  bool $includeFlds
     * @return object
     */
    public function joinOrderProductShipCharges($includeFlds = true): object
    {
        $this->joinOrderProductCharges(OrderProduct::CHARGE_TYPE_SHIPPING, 'opship');
        $this->joinTable(Orders::DB_TBL_ORDER_PRODUCTS_SHIPPING, 'LEFt JOIN', 'ops.opshipping_op_id = op.op_id', 'ops');
        if (true === $includeFlds) {
            $this->addFld('(SUM(IFNULL(opship.opcharge_amount,0))) as shippingTotal');
            $this->addFld('SUM(if(ops.opshipping_by_seller_user_id > 0,IFNULL(opship.opcharge_amount,0),0)) as sellerShippingTotal');
            $this->addFld('SUM(if(ops.opshipping_by_seller_user_id = 0,IFNULL(opship.opcharge_amount,0),0)) as adminShippingTotal');
        }
        return $this;
    }

    /**
     * joinOrderProductDicountCharges
     *
     * @param  bool $includeFlds
     * @return object
     */
    public function joinOrderProductDicountCharges($includeFlds = true): object
    {
        $this->joinOrderProductCharges(OrderProduct::CHARGE_TYPE_DISCOUNT, 'opDis');
        if (true === $includeFlds) {
            $this->addFld('(SUM(IFNULL(opDis.opcharge_amount,0))) as couponDiscount');
        }
        return $this;
    }

    /**
     * joinOrderProductVolumeCharges
     *
     * @param  bool $includeFlds
     * @return object
     */
    public function joinOrderProductVolumeCharges($includeFlds = true): object
    {
        $this->joinOrderProductCharges(OrderProduct::CHARGE_TYPE_VOLUME_DISCOUNT, 'opVolDis');
        if (true === $includeFlds) {
            $this->addFld('(SUM(IFNULL(opVolDis.opcharge_amount, 0))) as volumeDiscount');
        }
        return $this;
    }

    /**
     * joinOrderProductRewardCharges
     *
     * @return object
     */
    public function joinOrderProductRewardCharges($includeFlds = true): object
    {
        $this->joinOrderProductCharges(OrderProduct::CHARGE_TYPE_REWARD_POINT_DISCOUNT, 'opRewardDis');
        if (true === $includeFlds) {
            $this->addFld('(SUM(IFNULL(opRewardDis.opcharge_amount, 0))) as rewardDiscount');
        }
        return $this;
    }

    /**
     * joinOrderUserAddress
     *
     * @param  int $addrType
     * @return object
     */
    public function joinOrderUserAddress(int $addrType = Orders::BILLING_ADDRESS_TYPE): object
    {
        $this->joinTable(Orders::DB_TBL_ORDER_USER_ADDRESS, 'LEFT JOIN', 'o.order_id = oaddr.oua_order_id and oua_type = ' . $addrType, 'oaddr');
        return $this;
    }

    /**
     * joinOrderPayments
     *
     * @param  int $txnStatus
     * @return object
     */
    public function joinOrderPayments(int $txnStatus = Orders::ORDER_PAYMENT_PAID): object
    {
        $this->joinTable(Orders::DB_TBL_ORDER_PAYMENTS, 'LEFT JOIN', 'o.order_id = opaym.opayment_order_id and opaym.opayment_txn_status = ' . $txnStatus, 'opaym');
        return $this;
    }

    /**
     * addTotalOrdersCount
     *
     * @param  string $key
     * @param  int $opSelprodUserId
     * @return object
     */
    public function addTotalOrdersCount(string $key = 'order_id', int $opSelprodUserId = 0): object
    {
        $srch = new self(0, [], $this->shopSpecific);
        $srch->joinOrders();
        $srch->joinPaymentMethod();
        $srch->setPaymentStatusCondition();
        $srch->setCompletedOrdersCondition();
        $srch->excludeDeletedOrdersCondition();
        $srch->doNotCalculateRecords();
        $srch->doNotLimitRecords();

        if (0 < $opSelprodUserId) {
            $srch->addCondition('op_selprod_user_id', '=', $opSelprodUserId);
        }

        switch ($key) {
            case 'order_id':
                $srch->addMultipleFields(['o.order_id', 'count(DISTINCT(op.op_id)) as totOrders']);
                $srch->addGroupBy('o.order_id');
                $this->joinTable('(' . $srch->getQuery() . ')', 'LEFT OUTER JOIN', 'ocount.order_id = o.order_id', 'ocount');
                break;
            case 'product_id':
                $srch->addMultipleFields(['SUBSTRING( op_selprod_code, 1, (LOCATE( "_", op_selprod_code ) - 1 ) ) as product_id', 'count(DISTINCT(op.op_id)) as totOrders']);
                $srch->addGroupBy('product_id');
                $this->joinTable('(' . $srch->getQuery() . ')', 'LEFT OUTER JOIN', 'ocount.product_id = SUBSTRING( op_selprod_code, 1, (LOCATE( "_", op_selprod_code ) - 1 ) )', 'ocount');
                break;
            case 'selprod_id':
                $srch->addMultipleFields(['op.op_selprod_id', 'count(DISTINCT(op.op_id)) as totOrders']);
                $srch->addGroupBy('op.op_selprod_id');
                $this->joinTable('(' . $srch->getQuery() . ')', 'LEFT OUTER JOIN', 'ocount.op_selprod_id = op.op_selprod_id', 'ocount');
                break;
            case 'order_date_added':
                $srch->addMultipleFields(['DATE(o.order_date_added) as order_date_added', 'count(DISTINCT(o.order_id)) as totOrders']);
                $srch->addGroupBy('DATE(o.order_date_added)');
                $this->joinTable('(' . $srch->getQuery() . ')', 'LEFT OUTER JOIN', 'ocount.order_date_added = DATE(o.order_date_added)', 'ocount');
                break;
            case 'op_selprod_user_id':
                $srch->joinSellerUser();
                $srch->addMultipleFields(['op_selprod_user_id', 'count(DISTINCT(o.order_id)) as totOrders']);
                $srch->addGroupBy('op_selprod_user_id');
                $this->joinTable('(' . $srch->getQuery() . ')', 'LEFT OUTER JOIN', 'ocount.op_selprod_user_id = op.op_selprod_user_id', 'ocount');
                break;
            case 'order_user_id':
                $srch->joinBuyerUser();
                $srch->addMultipleFields(['order_user_id', 'count(DISTINCT(o.order_id)) as totOrders', 'count(DISTINCT(op.op_id)) as orderItems']);
                $srch->addGroupBy('order_user_id');
                $this->joinTable('(' . $srch->getQuery() . ')', 'LEFT OUTER JOIN', 'ocount.order_user_id = o.order_user_id', 'ocount');
                break;
        }
        return $this;
    }

    /**
     * addStatusCondition
     *
     * @param  array $op_status
     * @param  bool $orderPaymentCancel
     * @return object
     */
    public function addStatusCondition(array $opStatus, bool $orderPaymentCancel = false): object
    {
        /* if (is_array($opStatus)) {
            if (!empty($opStatus)) {
                $cnd = $this->addCondition('op.op_status_id', 'IN', $opStatus);
            } else {
                $cnd = $this->addCondition('op.op_status_id', '=', 0);
            }
        } else {
            $op_status_id = FatUtility::int($opStatus);
            $cnd = $this->addCondition('op.op_status_id', '=', $op_status_id);
        } */
        if (!empty($opStatus)) {
            $cnd = $this->addCondition('op.op_status_id', 'IN', $opStatus);
        } else {
            $cnd = $this->addCondition('op.op_status_id', '=', 0);
        }

        if (true === $orderPaymentCancel) {
            $cnd->attachCondition('order_payment_status', '=', Orders::ORDER_PAYMENT_CANCELLED, 'OR');
        }
        return $this;
    }


    /**
     * setDateCondition
     *
     * @param  string $from
     * @param  string $to
     * @return object
     */
    public function setDateCondition(string $from = '', string $to = ''): object
    {
        if (!empty($from)) {
            $this->addCondition('o.order_date_added', '>=', $from . ' 00:00:00');
        }

        if (!empty($to)) {
            $this->addCondition('o.order_date_added', '<=', $to . ' 23:59:59');
        }
        return $this;
    }

    /**
     * setPaymentStatusCondition
     *
     * @param  int $paymentStatus
     * @return object
     */
    public function setPaymentStatusCondition(int $paymentStatus = Orders::ORDER_PAYMENT_PAID): object
    {
        $cnd = $this->addCondition('o.order_payment_status', '=', $paymentStatus);
        $cnd->attachCondition('pm.plugin_code', '=', 'cashondelivery');
        $cnd->attachCondition('pm.plugin_code', '=', 'payatstore');
        return $this;
    }

    /**
     * setCompletedOrdersCondition
     *
     * @return object
     */
    public function setCompletedOrdersCondition(): object
    {
        $this->addStatusCondition(unserialize(FatApp::getConfig('CONF_COMPLETED_ORDER_STATUS')));
        /* $completedStatus = unserialize(FatApp::getConfig("CONF_COMPLETED_ORDER_STATUS", FatUtility::VAR_STRING, ''));
        $cancelledStatus = [FatApp::getConfig('CONF_DEFAULT_CANCEL_ORDER_STATUS')];
        $refundCompletedStatus = array_diff($completedStatus, $cancelledStatus);
        $this->addStatusCondition($refundCompletedStatus); */
        return $this;
    }

    /**
     * excludeDeletedOrdersCondition
     *
     * @return object
     */
    public function excludeDeletedOrdersCondition(): object
    {
        $this->addCondition('order_deleted', '=', applicationConstants::NO);
        return $this;
    }

    /**
     * setOrderBy
     *
     * @param  string $key
     * @param  string $sortBy
     * @return object
     */
    public function setOrderBy(string $key, string $sortBy = 'ASC'): object
    {
        if (!array_key_exists($sortBy, applicationConstants::sortOrder(CommonHelper::getLangId()))) {
            $sortBy = applicationConstants::SORT_ASC;
        }

        switch ($key) {
            case 'op_invoice_number':
                $this->addOrder('o.order_id', $sortBy);
                $this->addOrder($key, $sortBy);
                break;
            default:
                $this->addOrder($key, $sortBy);
                break;
        }
        return $this;
    }

    /**
     * setGroupBy
     *
     * @param  string $key
     * @return object
     */
    public function setGroupBy(string $key): object
    {
        switch ($key) {
            case 'orderDate':
                $this->addGroupBy('DATE(o.order_date_added)');
                break;
            case 'order_id':
                $this->addGroupBy('o.order_id');
                break;
            case 'product_id':
                $this->addFld('SUBSTRING( op_selprod_code, 1, (LOCATE( "_", op_selprod_code ) - 1 ) ) as product_id');
                $this->addGroupBy('product_id');
                break;
            case 'selprod_id':
                $this->addFld('op.op_selprod_id');
                $this->addGroupBy('op.op_selprod_id');
                break;
            case 'shop_id':
                $this->addFld('op.op_shop_id');
                $this->addGroupBy('op.op_shop_id');
                break;
            case 'op_selprod_user_id':
                $this->addFld('op.op_selprod_user_id');
                $this->addGroupBy('op.op_selprod_user_id');
                break;
            case 'order_user_id':
                $this->addFld('o.order_user_id');
                $this->addGroupBy('o.order_user_id');
                break;
            default:
                $this->addGroupBy($key);
                break;
        }
        return $this;
    }

    /**
     * getFields
     *
     * @param  array $fields
     * @param  bool $shopSpecific
     * @return array
     */
    public static function getFields(array $fields = [], bool $shopSpecific = false): array
    {
        // pending NetSales
        $arr = [
            'orderDate' => 'DATE(o.order_date_added) as orderDate',
            'totOrders' => 'ocount.totOrders',
            'totQtys' => 'SUM(op_qty) as totQtys',
            'totRefundedQtys' => 'SUM(op_refund_qty) as totRefundedQtys',
            'netSoldQty' => 'SUM(op_qty - op_refund_qty) as netSoldQty',
            'grossSales' => 'sum(( op_unit_price * op_qty ) + IFNULL(op_other_charges, 0) + IFNULL(op_rounding_off,0) - opDiscountCharges) as grossSales',
            'discountTotal' => 'SUM(IFNULL(opDiscountCharges, 0)) as discountTotal',
            'transactionAmount' => 'sum(( op_unit_price * op_qty ) + IFNULL(op_other_charges,0) + IFNULL(op_rounding_off,0)) as transactionAmount',
            'inventoryValue' => 'SUM(op_unit_price*op_qty) as inventoryValue',
            'inventoryCost' => 'SUM(op_unit_cost*op_qty) as inventoryCost',
            'refundedAmount' => 'sum(IFNULL(op_refund_amount,0)) as refundedAmount',
            'refundedShipping' => '(SUM(IFNULL(op_refund_shipping,0))) as refundedShipping',
            'refundedTax' => 'SUM(IFNULL(op_refund_tax,0)) as refundedTax',

            'commissionCharged' => 'sum(IFNULL(op_commission_charged,0)) as commissionCharged',
            'refundedCommission' => 'sum(IFNULL(op_refund_commission,0)) as refundedCommission',
            'affiliateCommissionCharged' => 'sum(IFNULL(op_affiliate_commission_charged,0)) as affiliateCommissionCharged',
            'refundedAffiliateCommission' => '(SUM(IFNULL(op_refund_shipping,0))) as refundedAffiliateCommission',
            'adminSalesEarnings' => 'sum((IFNULL(op_commission_charged,0) - IFNULL(op_refund_commission,0))) as adminSalesEarnings',

            'orderNetAmount' => 'sum(( op_unit_price * op_qty ) + IFNULL(op_other_charges,0) + IFNULL(op_rounding_off,0) - IFNULL(op_refund_amount,0)) as orderNetAmount',
            'unitPrice' => 'SUM(op_unit_price*op_qty)/sum(op_qty) as unitPrice',
            'roundingOff' => 'op_rounding_off',
            'op_other_charges' => 'sum(IFNULL(op_other_charges,0)) as op_other_charges'

        ];

        if (true == $shopSpecific) {
            $arr = array_merge($arr, [
                'grossSales' => 'sum(( op_unit_price * op_qty ) + IFNULL(op_other_charges,0) - IFNULL(opDiscountCharges,0)) as grossSales',
                'refundedAmount' => 'sum(IFNULL(op_refund_amount,0) - if(ops.opshipping_by_seller_user_id > 0,0,IFNULL(op.op_refund_shipping,0)) - if(opst.op_tax_collected_by_seller > 0,0,IFNULL(op.op_refund_tax,0)) ) as refundedAmount',
                'transactionAmount' => 'sum(( op_unit_price * op_qty ) + IFNULL(op_other_charges,0) + if(opst.op_tax_collected_by_seller > 0,IFNULL(optax.opcharge_amount,0),0) + if(ops.opshipping_by_seller_user_id > 0,IFNULL(opship.opcharge_amount,0),0) + IFNULL(op_rounding_off,0)) as transactionAmount',
                'refundedTaxFromSeller' => 'SUM(if(opst.op_tax_collected_by_seller > 0,IFNULL(op.op_refund_tax,0),0)) as refundedTaxFromSeller',
                'orderNetAmount' => 'sum(( op_unit_price * op_qty ) + IFNULL(op_other_charges,0) + if(opst.op_tax_collected_by_seller > 0,IFNULL(optax.opcharge_amount,0),0) + if(ops.opshipping_by_seller_user_id > 0,IFNULL(opship.opcharge_amount,0),0) + IFNULL(op_rounding_off,0) - (IFNULL(op_refund_amount,0) - if(opst.op_tax_collected_by_seller > 0,0,IFNULL(op.op_refund_tax,0)) - if(ops.opshipping_by_seller_user_id > 0,0,IFNULL(op.op_refund_shipping,0)))) as orderNetAmount',
                'refundedShippingFromSeller' => 'SUM(if(ops.opshipping_by_seller_user_id > 0,IFNULL(op.op_refund_shipping,0),0)) as refundedShippingFromSeller',
                'sellerEarnings' => 'sum(( op_unit_price * op_qty ) + IFNULL(op_other_charges,0) + IFNULL(op_rounding_off,0) - (IFNULL(op_refund_amount,0) - if(opst.op_tax_collected_by_seller > 0,0,IFNULL(op.op_refund_tax,0)))) - SUM(op_unit_cost*op_qty) as sellerEarnings',
                'sellerCost' => 'sum(if(opst.op_tax_collected_by_seller > 0,IFNULL(optax.opcharge_amount,0),0) + if(ops.opshipping_by_seller_user_id > 0,IFNULL(opship.opcharge_amount,0),0) + IFNULL(op_rounding_off,0) - ( if(opst.op_tax_collected_by_seller > 0,0,IFNULL(op.op_refund_tax,0)))) + SUM(op_unit_cost*op_qty) as sellerCost'
            ]);
        }

        if (empty($fields)) {
            return array_values($arr);
        }

        $fields = array_diff($fields, ['taxTotal', 'shippingTotal', 'couponDiscount', 'volumeDiscount', 'rewardDiscount', 'opDiscountCharges', 'opNonDiscountCharges', 'sellerShippingTotal', 'adminShippingTotal', 'sellerTaxTotal', 'adminTaxTotal', 'totalAmount']);

        $flds = [];
        foreach ($fields as $key) {
            if (!array_key_exists($key, $arr)) {
                $flds[] = $key;
                continue;
            }
            $flds[] = $arr[$key];
        }

        return $flds;
    }

    /**
     * setFields
     *
     * @param  array $fields
     * @param  bool $shopSpecific
     * @return object
     */
    private function setFields(array $fields = [], bool $shopSpecific = false): object
    {
        if (empty($fields)) {
            return $this;
        }
        $this->attr[] = array_merge($this->attr, self::getFields($fields, $shopSpecific));
        $this->addMultipleFields($this->attr);
        return $this;
    }

    /**
     * salesReportObject
     *
     * @param  int $langId
     * @param  bool $joinSeller
     * @param  array $attr
     * @return object
     */
    public static function salesReportObject(int $langId = 0, bool $joinSeller = false, array $attr = array()): object
    {
        $ocSrch = new SearchBase(OrderProduct::DB_TBL_CHARGES, 'opc');
        $ocSrch->doNotCalculateRecords();
        $ocSrch->doNotLimitRecords();
        $ocSrch->addMultipleFields(array('opcharge_op_id', 'sum(opcharge_amount) as op_other_charges'));
        $ocSrch->addGroupBy('opc.opcharge_op_id');
        $qryOtherCharges = $ocSrch->getQuery();

        $srch = new OrderProductSearch($langId, true);
        $srch->joinPaymentMethod();

        if ($joinSeller) {
            $srch->joinSellerUser();
        }

        $srch->joinTable('(' . $qryOtherCharges . ')', 'LEFT OUTER JOIN', 'op.op_id = opcc.opcharge_op_id', 'opcc');
        $srch->joinOrderProductCharges(OrderProduct::CHARGE_TYPE_TAX, 'optax');
        $srch->joinOrderProductCharges(OrderProduct::CHARGE_TYPE_SHIPPING, 'opship');

        $cnd = $srch->addCondition('o.order_payment_status', '=', Orders::ORDER_PAYMENT_PAID);
        $cnd->attachCondition('plugin_code', '=', 'cashondelivery');
        $cnd->attachCondition('plugin_code', '=', 'payatstore');
        $srch->addStatusCondition(unserialize(FatApp::getConfig('CONF_COMPLETED_ORDER_STATUS')));

        if (empty($attr)) {
            $srch->addMultipleFields(array('DATE(order_date_added) as order_date', 'count(op_id) as totOrders', 'SUM(op_qty) as totQtys', 'SUM(op_refund_qty) as totRefundedQtys', 'SUM(op_qty - op_refund_qty) as netSoldQty', 'sum((op_commission_charged - op_refund_commission)) as totalSalesEarnings', 'sum(op_refund_amount) as totalRefundedAmount', 'op.op_qty', 'op.op_unit_price', 'op.op_unit_cost', 'SUM( op.op_unit_cost * op_qty ) as inventoryValue', 'op_other_charges', 'sum(( op_unit_price * op_qty ) + COALESCE(op_other_charges, 0)  + op_rounding_off) as orderNetAmount', '(SUM(optax.opcharge_amount)) as taxTotal', '(SUM(opship.opcharge_amount)) as shippingTotal', 'op_rounding_off'));
        } else {
            $srch->addMultipleFields($attr);
        }
        return $srch;
    }

    public static function getSelProdOptions(array $selrProdIdArr, $langId = 0)
    {
        $spOptionSrch = new SearchBase(SellerProduct::DB_TBL_SELLER_PROD_OPTIONS, 'spo');
        $spOptionSrch->joinTable(OptionValue::DB_TBL, 'INNER JOIN', 'spo.selprodoption_optionvalue_id = ov.optionvalue_id', 'ov');
        $spOptionSrch->joinTable(OptionValue::DB_TBL . '_lang', 'LEFT OUTER JOIN', 'ov_lang.optionvaluelang_optionvalue_id = ov.optionvalue_id AND ov_lang.optionvaluelang_lang_id = ' . $langId, 'ov_lang');
        $spOptionSrch->joinTable(Option::DB_TBL, 'INNER JOIN', '`option`.option_id = ov.optionvalue_option_id', '`option`');
        $spOptionSrch->joinTable(Option::DB_TBL . '_lang', 'LEFT OUTER JOIN', '`option`.option_id = option_lang.optionlang_option_id AND option_lang.optionlang_lang_id = ' . $langId, 'option_lang');
        $spOptionSrch->doNotCalculateRecords();
        $spOptionSrch->doNotLimitRecords();
        $spOptionSrch->addGroupBy('spo.selprodoption_selprod_id');
        $spOptionSrch->addMultipleFields(array('spo.selprodoption_selprod_id', 'COALESCE(option_lang.option_name, `option`.option_identifier) as option_name', 'COALESCE(ov_lang.optionvalue_name, ov.optionvalue_identifier) as optionvalue_name', 'GROUP_CONCAT(option_lang.option_name) as grouped_option_name', 'GROUP_CONCAT(ov_lang.optionvalue_name) as grouped_optionvalue_name'));
        $spOptionSrch->addCondition('selprodoption_selprod_id', 'IN', $selrProdIdArr);
        return FatApp::getDb()->fetchAll($spOptionSrch->getResultSet(), 'selprodoption_selprod_id');
    }
}
