<?php

/**
 * ShipRocket - https://apidocs.shiprocket.in
 */

require_once dirname(__FILE__) . '/ShipRocketApi.php';

class ShipRocket extends ShippingServicesBase
{
    public const KEY_NAME = __CLASS__;

    private const REQUEST_AUTH_LOGIN = 1;
    private const REQUEST_GET_RATES = 2;
    private const REQUEST_CHANNELS = 3;
    private const REQUEST_ADD_ORDER = 4;
    private const REQUEST_GET_PICKUP_LOCATIONS = 5;
    private const REQUEST_ADD_PICKUP_LOCATION = 6;
    private const REQUEST_ASSIGN_AWB = 7;
    private const REQUEST_GENERATE_LABEL = 8;
    private const REQUEST_RETURN_ORDER = 9;
    private const REQUEST_CANCEL_ORDER = 10;
    private const REQUEST_TRACKING = 11;

    private $resp;
    private $formFieldsArr = [];
    private $client;
    private $toAddress = [];
    private $fromAddress = [];
    private $dimensions = [];
    private $weight = 0;
    private $channel = [];
    private $pickups = [];
    private $orderDetail = [];
    private $buyerReturnAddress = [];

    public $requiredKeys = [
        'email',
        'password'
    ];

    /**
     * __construct
     *
     * @return void
     */
    public function __construct(int $langId)
    {
        $this->langId = 0 < $langId ? $langId : CommonHelper::getLangId();
        $this->formFieldsArr = $this->requiredKeys;
    }


    /**
     * init
     *
     * @return bool
     */
    public function init(): bool
    {
        if (false == $this->validateSettings($this->langId)) {
            return false;
        }

        if (false === $this->doRequest(self::REQUEST_AUTH_LOGIN)) {
            return false;
        }

        $this->client = $this->getResponse();
        return true;
    }

    /**
     * getFormFieldsArr
     *
     * @return array
     */
    public function getFormFieldsArr(): array
    {
        $lblArr = [];
        foreach ($this->formFieldsArr as $col) {
            $lbl = Labels::getLabel('LBL_' . strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $col)), $this->langId);
            $lblArr[$col] = $lbl;
        }
        return $lblArr;
    }

    /**
     * getResponse
     *
     * @return mixed
     */
    public function getResponse()
    {
        $resp = $this->resp;
        $this->resp = '';
        return $resp;
    }

    /**
     * canGenerateLabelFromShipment
     *
     * @return bool
     */
    public function canGenerateLabelFromShipment(): bool
    {
        return true;
    }

    /**
     * canFetchTrackingDetail
     *
     * @return bool
     */
    public function canFetchTrackingDetail(): bool
    {
        return true;
    }


    /**
     * getCarriers - No API found to fetch carrier list.
     *
     * @return array
     */
    public function getCarriers(): array
    {
        return [
            ['code' => self::KEY_NAME]
        ];
    }

    /**
     * setAddress - Set To Address
     *
     * @param  string $name
     * @param  string $stt1
     * @param  string $stt2
     * @param  string $city
     * @param  string $state
     * @param  string $zip
     * @param  string $countryCode
     * @param  string $phone
     * @return bool
     */
    public function setAddress(string $name, string $stt1, string $stt2, string $city, string $state, string $zip, string $countryCode, string $phone)
    {
        $this->toAddress = [
            'Line1' => $name . ' ' . $stt1,
            'Line2' => $stt2,
            'State' => $state,
            'PostCode' => $zip,
            'City' => $city,
            'CountryCode' => $countryCode
        ];
    }

    /**
     * setFromAddress
     *
     * @param  string $name
     * @param  string $stt1
     * @param  string $stt2
     * @param  string $city
     * @param  string $state
     * @param  string $zip
     * @param  string $countryCode
     * @param  string $phone
     * @return bool
     */
    public function setFromAddress(string $name, string $stt1, string $stt2, string $city, string $state, string $zip, string $countryCode, string $phone)
    {
        $this->fromAddress = [
            'Line1' => $name . ' ' . $stt1,
            'Line2' => $stt2,
            'State' => $state,
            'PostCode' => $zip,
            'City' => $city,
            'CountryCode' => $countryCode
        ];
    }

    /**
     * convertToCm
     *
     * @param  float $value
     * @param  int $unit
     * @return float
     */
    private function convertToCm($value, $unit)
    {
        switch ($unit) {
            case ShippingPackage::UNIT_TYPE_INCH:
                return $value * 2.54;
                break;
            case ShippingPackage::UNIT_TYPE_METER:
                return $value * 100;
                break;

            default:
                return $value;
                break;
        }
    }

    /**
     * setDimensions
     *
     * @param  float $length
     * @param  float $width
     * @param  float $height
     * @param  string $unit
     * @return void
     */
    public function setDimensions($length, $width, $height, $unit = 'cm')
    {
        if (empty($length) || empty($width) || empty($height)) {
            return;
        }

        $this->dimensions = [
            'length' => $this->convertToCm($length, $unit),
            'breadth' => $this->convertToCm($width, $unit),
            'height' => $this->convertToCm($height, $unit),
        ];
    }

    /**
     * convertToKg - From Ounces
     *
     * @param  float $value
     * @return float
     */
    private function convertToKg($value)
    {
        return (float) $value * 0.02834952;
    }

    /**
     * setWeight - In oz
     *
     * @param  float $weight
     * @return void
     */
    public function setWeight($weight)
    {
        if (empty($weight)) {
            return;
        }
        $this->weight = $weight;
    }

    /**
     * getRates
     *
     * @return array
     */
    public function getRates(): array
    {
        $requestParam = [
            'pickup_postcode' => $this->fromAddress['PostCode'],
            'delivery_postcode' => $this->toAddress['PostCode'],
            'weight' => $this->convertToKg($this->weight),
        ];

        if (false === $this->doRequest(self::REQUEST_GET_RATES, $requestParam)) {
            return [];
        }

        $resp = $this->getResponse();
        if (isset($resp['status']) && 404 == $resp['status']) {
            $this->error = $resp['message'];
            return [];
        }

        $courierCompanies = isset($resp['data']['available_courier_companies']) ? $resp['data']['available_courier_companies'] : [];
        if (empty($courierCompanies)) {
            $this->error = Labels::getLabel('ERR_UNABLE_TO_FETCH_CARRIERS', $this->langId);
            return [];
        }

        $rates = [];
        foreach ($courierCompanies as $detail) {
            $rates[] = [
                'serviceName' => $detail['courier_name'],
                'serviceCode' => $detail['courier_company_id'],
                'shipmentId' => $detail['courier_company_id'],
                'shipmentCost' => $detail['rate'],
                'otherCost' => '0',
            ];
        }

        return $rates;
    }

    /**
     * getChannel
     *
     * @return array
     */
    public function getChannel(): array
    {
        if (!empty($this->channel)) {
            return $this->channel;
        }

        if (false === $this->doRequest(self::REQUEST_CHANNELS)) {
            return [];
        }

        $resp = $this->getResponse();
        return $this->channel = current($resp['data']);
    }

    /**
     * getPickupLocation
     *
     * @param  int $shopId
     * @return mixed
     */
    private function getPickupLocation(int $shopId)
    {
        if (0 < $this->orderDetail['opshipping_by_seller_user_id']) {
            $updatedOn = Shop::getAttributesById($shopId, 'shop_updated_on');
            $pickupLocationId = $shopId . date('ym', strtotime($updatedOn));
        } else {
            $pickupLocationId = 'ADMIN';
        }

        $pickups = $this->getAllPickupLocations();
        if (false !== array_search($pickupLocationId, array_column($pickups, 'pickup_location'))) {
            return $pickupLocationId;
        }

        if (false === $this->addPickupLocation($pickupLocationId)) {
            return -1;
        }
        return $pickupLocationId;
    }

    /**
     * getReturnPickupLocation
     *
     * @return string
     */
    private function getReturnPickupLocation(): int
    {
        $pickupLocationId = $this->orderDetail['op_invoice_number'];
        $pickups = $this->getAllPickupLocations();

        $locationIndex = array_search($pickupLocationId, array_column($pickups, 'pickup_location'));
        if (false !== $locationIndex) {
            return (int) $pickups[$locationIndex]['id'];
        }

        if (false === $this->addReturnPickupLocation($pickupLocationId)) {
            return 0;
        }

        $resp = $this->getResponse();
        if (1 > $resp['success']) {
            $this->error = Labels::getLabel('ERR_UNABLE_TO_ADD_LOCATION', $this->langId);
            return 0;
        }
        return (int) $resp['address']['id'];
    }

    /**
     * getAllPickupLocations
     *
     * @return array
     */
    private function getAllPickupLocations(): array
    {
        if (!empty($this->pickups)) {
            return $this->pickups;
        }

        if (false === $this->doRequest(self::REQUEST_GET_PICKUP_LOCATIONS)) {
            return [];
        }

        $resp = $this->getResponse();
        return $this->pickups = current($resp['data']);
    }

    /**
     * addPickupLocation
     *
     * @return array
     */
    private function addPickupLocation($pickupLocationId)
    {
        $address = $this->getShopAddress($this->orderDetail['opshipping_by_seller_user_id']);

        $requestParam = [
            'pickup_location' => FatUtility::convertToType($pickupLocationId, FatUtility::VAR_STRING),
            'name' => $address['shop_name'],
            'email' => $this->orderDetail['op_shop_owner_email'],
            'phone' => $address['phone'],
            'address' => $address['line1'],
            'address_2' => $address['line2'],
            'city' => $address['city'],
            'state' => $address['state'],
            'country' => $address['country'],
            'pin_code' => $address['postalCode'],
        ];


        return $this->doRequest(self::REQUEST_ADD_PICKUP_LOCATION, $requestParam);
    }

    /**
     * addReturnPickupLocation
     *
     * @param  string $pickupLocationId
     * @return void
     */
    private function addReturnPickupLocation(string $pickupLocationId)
    {
        $requestParam = [
            'pickup_location' => FatUtility::convertToType($pickupLocationId, FatUtility::VAR_STRING),
            'name' => $this->buyerReturnAddress['oua_name'],
            'email' => $this->orderDetail['buyer_email'],
            'phone' => $this->buyerReturnAddress['oua_phone'],
            'address' => $this->buyerReturnAddress['oua_address1'],
            'address_2' => $this->buyerReturnAddress['oua_address2'],
            'city' => $this->buyerReturnAddress['oua_city'],
            'state' => $this->buyerReturnAddress['oua_state'],
            'country' => $this->buyerReturnAddress['oua_country'],
            'pin_code' => $this->buyerReturnAddress['oua_zip'],
        ];
        return $this->doRequest(self::REQUEST_ADD_PICKUP_LOCATION, $requestParam);
    }

    /**
     * proceedToShipment
     *
     * @param  array $requestParam
     * @return bool
     */
    public function proceedToShipment(array $requestParam): bool
    {
        $this->orderDetail = $this->getSystemOrder($requestParam['op_id']);
        if (empty($this->orderDetail)) {
            $this->error = Labels::getLabel('ERR_INVALID_ORDER', $this->langId);
            return false;
        }

        $pickupLocationId = $this->getPickupLocation($this->orderDetail['op_shop_id']);
        if (0 > $pickupLocationId) {
            $this->error = Labels::getLabel('ERR_UNABLE_TO_GET_PICKUP_LOCATION', $this->langId);
            return false;
        }

        $orderTimestamp = strtotime($this->orderDetail['order_date_added']);
        $taxOptions = json_decode($this->orderDetail['op_product_tax_options'], true);

        $shippingTotal = CommonHelper::orderProductAmount($this->orderDetail, 'SHIPPING');

        $discount = CommonHelper::orderProductAmount($this->orderDetail, 'DISCOUNT');
        $volumeDiscount = CommonHelper::orderProductAmount($this->orderDetail, 'VOLUME_DISCOUNT');
        $totalDiscount = abs($discount) + abs($volumeDiscount);

        $orderObj = new Orders($this->orderDetail['order_id']);
        $addresses = $orderObj->getOrderAddresses($this->orderDetail['order_id']);
        $billingAddress = $addresses[Orders::BILLING_ADDRESS_TYPE];
        $shippingAddress = (!empty($addresses[Orders::SHIPPING_ADDRESS_TYPE])) ? $addresses[Orders::SHIPPING_ADDRESS_TYPE] : [];

        $channel = $this->getChannel();

        $this->setAddress($billingAddress['oua_name'], $billingAddress['oua_address1'], $billingAddress['oua_address2'], $billingAddress['oua_city'], $billingAddress['oua_state'], $billingAddress['oua_zip'], $billingAddress['oua_country_code'], $billingAddress['oua_phone']);

        $this->orderDetail['op_other_charges'] = array_sum(array_column($this->orderDetail['charges'], 'opcharge_amount'));

        $taxOptions = !empty($this->orderDetail['op_product_tax_options']) ? json_decode($this->orderDetail['op_product_tax_options'], true) : [];
        $taxPercentage = !empty($taxOptions) && isset($taxOptions['Tax']['percentageValue']) ? $taxOptions['Tax']['percentageValue'] : 0;

        $taxCharged = CommonHelper::orderProductAmount($this->orderDetail, 'TAX');

        $sellingPrice = $this->orderDetail['op_unit_price'] + ($taxCharged / $this->orderDetail['op_qty']);
        if (0 < FatApp::getConfig("CONF_PRODUCT_INCLUSIVE_TAX", FatUtility::VAR_INT, 0)) {
            $sellingPrice =  $this->orderDetail['op_unit_price'];
        }

        $weightUnitArray = applicationConstants::getWeightUnitsArr($this->langId, true);
        $productWeightInOunce = Shipping::convertWeightInOunce($this->orderDetail['op_product_weight'], $weightUnitArray[$this->orderDetail['op_product_weight_unit']]);

        $requestParam = [
            'order_id' => $this->orderDetail['op_invoice_number'],
            'order_date' => date('Y-m-d H:i', $orderTimestamp),
            'pickup_location' => FatUtility::convertToType($pickupLocationId, FatUtility::VAR_STRING),
            'channel_id' => isset($channel['id']) ? $channel['id'] : '',
            'comment' => $this->orderDetail['op_order_id'] . ' - ' . $this->orderDetail['op_invoice_number'],
            'reseller_name' => $this->orderDetail['op_shop_owner_name'],
            'company_name' =>  $this->orderDetail['op_shop_name'],
            'billing_customer_name' => User::getFirstName($this->orderDetail['buyer_user_name']),
            "billing_last_name" => User::getLastName($this->orderDetail['buyer_user_name']),
            'billing_address' => $billingAddress['oua_address1'],
            'billing_address_2' => $billingAddress['oua_address2'],
            'billing_city' => $billingAddress['oua_city'],
            'billing_pincode' => $billingAddress['oua_zip'],
            'billing_state' => $billingAddress['oua_state'],
            'billing_country' => $billingAddress['oua_country'],
            'billing_email' => $this->orderDetail['buyer_email'],
            'billing_phone' => $billingAddress['oua_phone'],
            'shipping_is_billing' => false,
            'shipping_customer_name' => User::getFirstName($this->orderDetail['buyer_user_name']),
            "shipping_last_name" => User::getLastName($this->orderDetail['buyer_user_name']),
            'shipping_address' => $shippingAddress['oua_address1'],
            'shipping_address_2' => $shippingAddress['oua_address2'],
            'shipping_city' => $shippingAddress['oua_city'],
            'shipping_pincode' => $shippingAddress['oua_zip'],
            'shipping_country' => $shippingAddress['oua_country'],
            'shipping_state' => $shippingAddress['oua_state'],
            'shipping_email' => $this->orderDetail['buyer_email'],
            'shipping_phone' => $shippingAddress['oua_phone'],
            'order_items' => [
                [
                    'name' =>  $this->orderDetail['op_selprod_title'],
                    'sku' =>  $this->orderDetail['op_selprod_sku'],
                    'units' => $this->orderDetail['op_qty'],
                    'selling_price' => $sellingPrice,
                    'tax' => $taxPercentage,
                ]
            ],
            'payment_method' => (FatApp::getConfig("CONF_COD_ORDER_STATUS", FatUtility::VAR_INT, FatApp::getConfig("CONF_COD_ORDER_STATUS")) == $this->orderDetail['op_status_id']) || (isset($this->orderDetail['plugin_code']) && in_array(strtolower($this->orderDetail['plugin_code']), ['cashondelivery'])) ? 'COD' : 'Prepaid',
            'shipping_charges' => $shippingTotal,
            'total_discount' => $totalDiscount,
            'sub_total' => CommonHelper::orderProductAmount($this->orderDetail, 'CART_TOTAL', false, User::USER_TYPE_SELLER) + $taxCharged,
            'length' => $this->convertToCm($this->orderDetail['op_product_length'], $this->orderDetail['op_product_dimension_unit']),
            'breadth' => $this->convertToCm($this->orderDetail['op_product_width'], $this->orderDetail['op_product_dimension_unit']),
            'height' => $this->convertToCm($this->orderDetail['op_product_height'], $this->orderDetail['op_product_dimension_unit']),
            'weight' => $this->convertToKg($productWeightInOunce)
        ];


        if (false === $this->doRequest(self::REQUEST_ADD_ORDER, $requestParam)) {
            return false;
        }
        $orderShipment = $this->getResponse();

        if (!isset($orderShipment['shipment_id']) && isset($orderShipment['message'])) {
            $this->error = $orderShipment['message'];
            return false;
        }

        $requestParam = [
            'shipmentIdsArr' => [$orderShipment['shipment_id']],
            'courierId' => $this->orderDetail['opshipping_service_code'],
            'weight' => $this->convertToKg($productWeightInOunce),
        ];

        if (false === $this->doRequest(self::REQUEST_ASSIGN_AWB, $requestParam)) {
            SystemLog::plugin(json_encode($requestParam), $this->getError(), self::KEY_NAME);
            return false;
        }

        $awbResp = $this->getResponse();
        if (applicationConstants::SUCCESS != $awbResp['awb_assign_status']) {
            SystemLog::plugin(json_encode($requestParam), json_encode($awbResp), self::KEY_NAME);
            $this->error = Labels::getLabel('ERR_UNABLE_TO_ASSIGN_AWB_FOR_THE_ORDER', $this->langId);
            return false;
        }

        if (false === $this->doRequest(self::REQUEST_GENERATE_LABEL, ['shipment_id' => [$orderShipment['shipment_id']]])) {
            SystemLog::plugin(json_encode(['shipment_id' => [$orderShipment['shipment_id']]]), $this->getError(), self::KEY_NAME);
            return false;
        }

        $labelResp = $this->getResponse();
        if (applicationConstants::SUCCESS != $labelResp['label_created']) {
            SystemLog::plugin(json_encode(['shipment_id' => [$orderShipment['shipment_id']]]), json_encode($labelResp), self::KEY_NAME);
            $this->error = Labels::getLabel('ERR_UNABLE_TO_BIND_LABEL', $this->langId);
            return false;
        }
        $this->resp = [
            'shipment_response' => $orderShipment,
            'awb_response' => $awbResp,
            'label_response' => $labelResp,
            'orderNumber' => $orderShipment['order_id'],
            'tracking_code' => $orderShipment['shipment_id'],
        ];
        return true;
    }

    /**
     * loadSystemOrder
     *
     * @param  int $opId
     * @return void
     */
    public function loadSystemOrder(int $opId): void
    {
        $this->orderDetail = (array) $this->getSystemOrder($opId);
    }

    /**
     * returnShipment
     *
     * @return bool
     */
    /* public function returnShipment(): bool
    {
        if (!is_array($this->orderDetail) || 1 > count($this->orderDetail)) {
            $this->error = Labels::getLabel('ERR_INVALID_ORDER', $this->langId);
            return false;
        }
        $orderTimestamp = strtotime($this->orderDetail['order_date_added']);
        $channel = $this->getChannel();

        $orderObj = new Orders($this->orderDetail['order_id']);
        $addresses = $orderObj->getOrderAddresses($this->orderDetail['order_id']);
        $shippingAddress = (!empty($addresses[Orders::SHIPPING_ADDRESS_TYPE])) ? $addresses[Orders::SHIPPING_ADDRESS_TYPE] : [];
        
        $this->buyerReturnAddress = $shippingAddress;
        $pickupLocationId = $this->getReturnPickupLocation();
        if (1 > (int) $pickupLocationId) {
            $this->error = Labels::getLabel('ERR_UNABLE_TO_GET_PICKUP_LOCATION', $this->langId);
            return false;
        }

        $attr = [
            'shop_address_line_1',
            'shop_address_line_2',
            'shop_city',
            'COALESCE(state_name, state_identifier) as state_name',
            'country_name',
            'shop_postalcode',
        ];
        $shopAddress = Shop::getShopAddress($this->orderDetail['op_shop_id'], false, $this->langId, $attr);

        $taxCharged = 0;
        if (!empty($taxOptions)) {
            foreach ($taxOptions as $key => $val) {
                $taxCharged += $val['value'];
            }
        }

        $sellingPrice = $this->orderDetail['op_unit_price'] + ($taxCharged / $this->orderDetail['op_qty']);
        if (0 < FatApp::getConfig("CONF_PRODUCT_INCLUSIVE_TAX", FatUtility::VAR_INT, 0)) {
            $sellingPrice =  $this->orderDetail['op_unit_price'];
        }

        $discount = CommonHelper::orderProductAmount($this->orderDetail, 'DISCOUNT');
        $volumeDiscount = CommonHelper::orderProductAmount($this->orderDetail, 'VOLUME_DISCOUNT');
        $totalDiscount = abs($discount) + abs($volumeDiscount);
        $discountPerUnit = ($totalDiscount / $this->orderDetail['op_qty']); // Inclusive Tax

        $requestParam = [
            'order_id' => 'R-' . $this->orderDetail['op_invoice_number'],
            'order_date' => date('Y-m-d H:i', $orderTimestamp),
            'channel_id' => isset($channel['id']) ? $channel['id'] : '',
            'pickup_customer_name' => User::getFirstName($this->orderDetail['buyer_user_name']),
            'pickup_last_name' => User::getLastName($this->orderDetail['buyer_user_name']),
            'pickup_address' => $shippingAddress['oua_address1'],
            'pickup_address_2' => $shippingAddress['oua_address2'],
            'pickup_city' => $shippingAddress['oua_city'],
            'pickup_state' => $shippingAddress['oua_state'],
            'pickup_country' => $shippingAddress['oua_country'],
            'pickup_pincode' => $shippingAddress['oua_zip'],
            'pickup_email' => $this->orderDetail['buyer_email'],
            'pickup_phone' => $this->orderDetail['buyer_phone'],
            'pickup_location_id' => FatUtility::convertToType($pickupLocationId, FatUtility::VAR_STRING),
            'shipping_customer_name' => User::getFirstName($this->orderDetail['op_shop_owner_name']),
            'shipping_last_name' => User::getLastName($this->orderDetail['op_shop_owner_name']),
            'shipping_address' => $shopAddress['shop_address_line_1'],
            'shipping_address_2' => $shopAddress['shop_address_line_2'],
            'shipping_city' => $shopAddress['shop_city'],
            'shipping_country' => $shopAddress['country_name'],
            'shipping_pincode' => $shopAddress['shop_postalcode'],
            'shipping_state' => $shopAddress['state_name'],
            'shipping_email' => $this->orderDetail['op_shop_owner_email'],
            'shipping_phone' => $this->orderDetail['op_shop_owner_phone'],
            'order_items' => [
                [
                    'name' => $this->orderDetail['op_selprod_title'],
                    'sku' => $this->orderDetail['op_selprod_sku'],
                    'units' => $this->orderDetail['op_qty'],
                    'selling_price' => $sellingPrice,
                    'discount' => $discountPerUnit,
                ]
            ],
            'payment_method' => (FatApp::getConfig("CONF_COD_ORDER_STATUS", FatUtility::VAR_INT, FatApp::getConfig("CONF_COD_ORDER_STATUS")) == $this->orderDetail['op_status_id']) ? 'COD' : 'Prepaid',
            'total_discount' => $totalDiscount,
            'sub_total' => CommonHelper::orderProductAmount($this->orderDetail, 'CART_TOTAL', false , User::USER_TYPE_SELLER),
            'length' => $this->convertToCm($this->orderDetail['op_product_length'], $this->orderDetail['op_product_dimension_unit']),
            'breadth' => $this->convertToCm($this->orderDetail['op_product_width'], $this->orderDetail['op_product_dimension_unit']),
            'height' => $this->convertToCm($this->orderDetail['op_product_height'], $this->orderDetail['op_product_dimension_unit']),
            'weight' => $this->convertToKg($this->orderDetail['op_product_weight'])
        ];

        return $this->doRequest(self::REQUEST_RETURN_ORDER, $requestParam);
    } */

    /**
     * refundShipment
     *
     * @return bool
     */
    public function refundShipment(): bool
    {
        $orderId = OrderProductShipment::getAttributesById($this->orderDetail['op_id'], 'opship_order_number');
        if (false == $orderId || empty($orderId)) {
            $this->error = Labels::getLabel('ERR_UNABLE_TO_LOCATE_ORDER', $this->langId);
            return false;
        }
        return $this->doRequest(self::REQUEST_CANCEL_ORDER, ['ids' => [$orderId]]);
    }

    /**
     * fetchTrackingDetail
     *
     * @return array
     */
    public function fetchTrackingDetail(): array
    {
        if (empty($this->orderDetail)) {
            $this->error = Labels::getLabel('ERR_UNABLE_TO_LOAD_ORDER', $this->langId);
            return [];
        }

        if (false === $this->doRequest(self::REQUEST_TRACKING, $this->orderDetail['opship_tracking_number'])) {
            return [];
        }

        $trackingDetail = $this->getResponse();
        if (!array_key_exists('tracking_data', $trackingDetail)) {
            $this->error = Labels::getLabel('ERR_UNABLE_TO_FETCH_TRACKING_DETAILS', $this->langId);
            return [];
        }

        if (applicationConstants::FAILURE == $trackingDetail['tracking_data']['track_status']) {
            $this->error = $trackingDetail['tracking_data']['error'];
            return [];
        }

        $trackingDetail = $trackingDetail['tracking_data'];

        $data = [
            'detail' => [],
            'response' => $trackingDetail,
            'trackingUrl' => isset($trackingDetail['track_url']) ? $trackingDetail['track_url'] : '',
        ];

        if (isset($trackingDetail['shipment_track_activities'])) {
            $trackingResult = $trackingDetail['shipment_track_activities'];
            if (is_array($trackingResult)) {
                foreach ($trackingResult as $trkData) {
                    $data['detail'][] = [
                        'description' => $trkData['activity'],
                        'dateTime' => $trkData['date'],
                        'location' => $trkData['location'],
                        'comments' => '',
                        'status' => self::TRACKING_STATUS_PROCESSING,
                    ];
                }
            }
        }
        return $data;
    }

    /**
     * downloadLabel
     *
     * @param  array $labelData
     * @return void
     */
    public function downloadLabel(array $labelData)
    {
        if (!array_key_exists('label_response', $labelData) || !isset($labelData['label_response']['label_url'])) {
            return false;
        }
        FatApp::redirectUser($labelData['label_response']['label_url']);
        return true;
    }

    /**
     * doRequest
     *
     * @param  int $requestType
     * @param  mixed $requestParam
     * @return bool
     */
    private function doRequest(int $requestType, $requestParam = []): bool
    {
        try {
            switch ($requestType) {
                case self::REQUEST_AUTH_LOGIN:
                    $this->resp = new ShipRocketApi([
                        'email' => $this->settings['email'],
                        'password' => $this->settings['password'],
                        'use_sandbox' => 0,  /* Use 0 As this API won`t support sanbox mode. */
                    ]);
                    break;
                case self::REQUEST_GET_RATES:
                    $this->resp = $this->client->checkServiceability($requestParam['pickup_postcode'], $requestParam['delivery_postcode'], 0, $requestParam['weight']);
                    break;
                case self::REQUEST_CHANNELS:
                    $this->resp = $this->client->channelsList();
                    break;
                case self::REQUEST_ADD_ORDER:
                    $this->resp = $this->client->createQuickOrder($requestParam);
                    break;
                case self::REQUEST_GET_PICKUP_LOCATIONS:
                    $this->resp = $this->client->getPickups($requestParam);
                    break;
                case self::REQUEST_ADD_PICKUP_LOCATION:
                    $this->resp = $this->client->createPickup($requestParam);
                    break;
                case self::REQUEST_ASSIGN_AWB:
                    $shipmentIdsArr = $requestParam['shipmentIdsArr'];
                    $courierId = $requestParam['courierId'];
                    $weight = $requestParam['weight'];
                    $this->resp = $this->client->assignAWBs($shipmentIdsArr, $courierId, $weight);
                    break;
                case self::REQUEST_GENERATE_LABEL:
                    $this->resp = $this->client->generateLabel($requestParam);
                    break;
                case self::REQUEST_RETURN_ORDER:
                    $this->resp = $this->client->createReturnOrder($requestParam);
                    break;
                case self::REQUEST_CANCEL_ORDER:
                    $this->resp = $this->client->cancelOrder($requestParam);
                    break;
                case self::REQUEST_TRACKING:
                    $this->resp = $this->client->trackUsingShipmentId($requestParam);
                    break;
            }
            return true;
        } catch (Exception $e) {
            $previous = $e->getPrevious();
            SystemLog::plugin(json_encode($requestParam), $e, self::KEY_NAME);
            $this->error = $e->getMessage();
            if ($requestType == self::REQUEST_AUTH_LOGIN && $e->getCode() == 400) {
                $this->error = 'INVALID KEYS';
            }
        } catch (Error $e) {
            SystemLog::plugin(json_encode($requestParam), $e->getMessage(), self::KEY_NAME);
            $this->error = $e->getMessage();
        }
        return false;
    }

    /**
     * validateKeys
     *
     * @param  array $keys
     * @return bool
     */
    public function validateKeys(array $keys): bool
    {
        $keys['plugin_active'] = Plugin::ACTIVE;
        $this->settings = $keys;
        return $this->init();
    }
}
