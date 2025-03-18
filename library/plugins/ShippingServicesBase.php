<?php

class ShippingServicesBase extends PluginBase
{
    protected $addressArr = [];
    
    public const TRACKING_STATUS_PROCESSING = 1;
    public const TRACKING_STATUS_DELIVERED = 2;

    /**
     * getSystemOrder
     *
     * @param  int $opId
     * @return array
     */
    public function getSystemOrder(int $opId): array
    {
        if (1 > $opId) {
            return [];
        }

        $srch = new OrderSearch($this->langId);
        $srch->joinOrderPaymentMethod();
        $srch->joinOrderBuyerUser();
        $srch->joinOrderProduct($this->langId);
        $srch->joinOrderProductShipping();
        $srch->joinSellerProduct();
        $srch->addCondition('op.op_id', '=', $opId);
        $srch->joinTable(Orders::DB_TBL_ORDER_PAYMENTS, 'LEFT JOIN', 'o.order_id = opaym.opayment_order_id', 'opaym');
        $srch->joinTable(OrderProductShipment::DB_TBL, 'LEFT JOIN', 'opshp.opship_op_id = op.op_id', 'opshp');
        $srch->joinTable(OrderProduct::DB_TBL_RESPONSE, 'LEFT JOIN', 'opr_op_id = op.op_id', 'opr');
        $srch->addMultipleFields(['ops.*', 'order_id', 'order_number', 'order_user_id', 'order_date_added', 'order_payment_status', 'order_tax_charged', 'order_site_commission', 'buyer.user_name as buyer_user_name', 'buyer_cred.credential_email as buyer_email', 'buyer.user_phone_dcode as buyer_phone_dcode', 'buyer.user_phone as buyer_phone', 'order_net_amount', 'opshipping_label', 'opshipping_carrier_code', 'opshipping_service_code', 'op.*', 'op_shop_name', 'op_product_tax_options', 'IFNULL(plugin_name, plugin_identifier) as plugin_name', 'op_selprod_title', 'op_product_name', 'sp.selprod_product_id', 'opshipping_by_seller_user_id', 'opaym.*', 'opship_tracking_number', 'opr_response', 'plugin_code']);
        $srch->doNotCalculateRecords();
        $srch->setPageSize(1);
        $rs = $srch->getResultSet();
        $orderDetail = (array) FatApp::getDb()->fetch($rs);
        if (!empty($orderDetail)) {
            $orderObj = new Orders();
            $charges = $orderObj->getOrderProductChargesArr($opId);
            $orderDetail['charges'] = $charges;
        }
        return $orderDetail;
    }

    /**
     * getSellerInfo
     *
     * @param  mixed $sellerId
     * @return void
     */
    public function getSellerInfo(int $sellerId): array
    {
        $userObj = new User($sellerId);
        return (array) $userObj->getSellerData($this->langId, [
            'user_id',
            'ifnull(shop_name, shop_identifier) as shop_name',
            'user_name',
            'user_phone_dcode',
            'user_phone',
            'credential_email'
        ]);
    }

    /**
     * getBuyerInfo
     *
     * @param  mixed $buyerId
     * @return void
     */
    public function getBuyerInfo(int $buyerId): array
    {
        $userObj = new User($buyerId);
        return (array) $userObj->getUserInfo([
            'user_id',
            'user_name',
            'user_phone_dcode',
            'user_phone',
            'credential_email'
        ], false, false, true);
    }
    
    /**
     * getShopAddress
     *
     * @param  int $sellerId
     * @return array
     */
    public function getShopAddress(int $sellerId): array
    {
        if (0 < $sellerId) {            
            $shopId = Shop::getAttributesByUserId($sellerId, 'shop_id');
            $fields = array('shop_postalcode as postalCode', 'shop_address_line_1 as line1', 'shop_address_line_2 as line2', 'shop_city as city', 'COALESCE(state_name, state_identifier) as state', 'state_code as stateCode', 'country_code as countryCode', 'country_name as country', 'shop_phone as phone', 'COALESCE(shop_name, shop_identifier) as shop_name', 'shop_id');
            return (array) Shop::getShopAddress($shopId, false, $this->langId, $fields);
        }

        $adminAddress = (array) Admin::getAddress($this->langId);
        $adminAddress['phone'] = FatApp::getConfig('CONF_SITE_PHONE', FatUtility::VAR_INT, 0);
        $adminAddress['shop_name'] = FatApp::getConfig('CONF_WEBSITE_NAME_' . $this->langId, FatUtility::VAR_STRING, '');
        $adminAddress['shop_id'] = 0;
        return $adminAddress;

    }

    /**
     * addOrder - Called if child class not required this function.
     *
     * @param  mixed $opId
     * @return bool
     */
    public function addOrder(int $opId): bool
    {
        return true;
    }
    
    /**
     * bindLabel - Called if child class not required this function.
     *
     * @return bool
     */
    public function bindLabel(array $requestParam): bool
    {
        return true;
    }

    /**
     * getCarriers - Called if child class not required this function.
     * Return multidimentional array
     * @return array
     */
    public function getCarriers(): array
    {
        return [
            []
        ];
    }

    /**
     * init - Called if child class not required this function.
     *
     * @return bool
     */
    public function init(): bool
    {
        return true;
    }

    /**
     * canCreatePickup
     *
     * @return bool
     */
    public function canCreatePickup(): bool
    {
        return false;
    }

    /**
     * 
     * @return bool
     */
    public function canFetchTrackingDetail(): bool
    {
        return false;
    }
    
    /**
     * canGenerateLabelSeparately
     *
     * @return bool
     */
    public function canGenerateLabelSeparately(): bool
    {
        return false;
    }
    
    /**
     * canGenerateLabelFromShipment
     *
     * @return bool
     */
    public function canGenerateLabelFromShipment(): bool
    {
        return false;
    }
}
