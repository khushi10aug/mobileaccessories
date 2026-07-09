<?php

use Google\Service\CloudTasks\CmekConfig;
use Stripe\Order;

class Orders extends MyAppModel
{
    public const DB_TBL = 'tbl_orders';
    public const DB_TBL_LANG = 'tbl_orders_lang';
    public const DB_TBL_PREFIX = 'order_';

    public const DB_TBL_ORDER_PRODUCTS = 'tbl_order_products';
    public const DB_TBL_ORDER_PRODUCTS_LANG = 'tbl_order_products_lang';

    public const DB_TBL_ORDER_SELLER_SUBSCRIPTIONS = 'tbl_order_seller_subscriptions';
    public const DB_TBL_ORDER_SELLER_SUBSCRIPTIONS_LANG = 'tbl_order_seller_subscriptions_lang';
    public const DB_TBL_ORDER_SELLER_SUBSCRIPTIONS_PREFIX = 'ossubs_';
    public const DB_TBL_ORDER_SELLER_SUBSCRIPTIONS_LANG_PREFIX = 'ossubslang_';

    public const DB_TBL_ORDERS_STATUS = 'tbl_orders_status';
    public const DB_TBL_ORDERS_STATUS_LANG = 'tbl_orders_status_lang';
    public const DB_TBL_ORDER_STATUS_HISTORY = 'tbl_orders_status_history';

    public const DB_TBL_ORDER_USER_ADDRESS = 'tbl_order_user_address';
    public const DB_TBL_ORDER_EXTRAS = 'tbl_order_extras';
    public const DB_TBL_ORDER_PAYMENTS = 'tbl_order_payments';

    public const DB_TBL_ORDER_PRODUCTS_SHIPPING = 'tbl_order_product_shipping';
    public const DB_TBL_ORDER_PRODUCTS_SHIPPING_LANG = 'tbl_order_product_shipping_lang';

    public const DB_TBL_CHARGES = 'tbl_order_product_charges';
    public const DB_TBL_CHARGES_PREFIX = 'opcharge_';

    public const DB_ORDER_TO_PLUGIN_ORDER = 'tbl_orders_to_plugin_order';
    public const DB_ORDER_TO_PLUGIN_ORDER_PREFIX = 'opo_';

    public const BILLING_ADDRESS_TYPE = 1;
    public const SHIPPING_ADDRESS_TYPE = 2;
    public const PICKUP_ADDRESS_TYPE = 3;

    public const ORDER_PAYMENT_CANCELLED = -1;
    public const ORDER_PAYMENT_PENDING = 0;
    public const ORDER_PAYMENT_PAID = 1;

    public const PAYMENT_GATEWAY_STATUS_PENDING = 0;
    public const PAYMENT_GATEWAY_STATUS_PAID = 1;
    public const PAYMENT_GATEWAY_STATUS_CANCELLED = 2;

    public const ORDER_PRODUCT = 1;
    public const ORDER_SUBSCRIPTION = 2;
    public const ORDER_WALLET_RECHARGE = 3;
    public const ORDER_GIFT_CARD = 4;

    public const REPLACE_ORDER_USER_ADDRESS = 'XXX';


    private $orderId;
    private $orderNo;

    public function __construct($id = 0)
    {
        parent::__construct(static::DB_TBL, static::DB_TBL_PREFIX . 'id', $id);
    }

    public static function getOrderPaymentStatusArr($langId)
    {
        $langId = FatUtility::int($langId);
        if ($langId < 1) {
            $langId = FatApp::getConfig('CONF_ADMIN_DEFAULT_LANG');
        }
        return array(
            static::ORDER_PAYMENT_CANCELLED => Labels::getLabel('LBL_ORDER_PAYMENT_STATUS_CANCELLED', $langId),
            static::ORDER_PAYMENT_PENDING => Labels::getLabel('LBL_ORDER_PAYMENT_STATUS_PENDING', $langId),
            static::ORDER_PAYMENT_PAID => Labels::getLabel('LBL_ORDER_PAYMENT_STATUS_PAID', $langId),
        );
    }
    public static function getActiveSubscriptionStatusArr()
    {
        return array(FatApp::getConfig("CONF_DEFAULT_SUBSCRIPTION_PAID_ORDER_STATUS", FatUtility::VAR_INT, 0));
    }

    public static function getPaymentGatewayStatusArr($langId)
    {
        $langId = FatUtility::int($langId);
        if ($langId < 1) {
            $langId = FatApp::getConfig('CONF_ADMIN_DEFAULT_LANG');
        }
        return array(
            static::PAYMENT_GATEWAY_STATUS_PENDING => Labels::getLabel('LBL_PAYMENT_GATEWAY_STATUS_PENDING', $langId),
            static::PAYMENT_GATEWAY_STATUS_PAID => Labels::getLabel('LBL_ORDER_PAYMENT_GATEWAY_PAID', $langId),
            static::PAYMENT_GATEWAY_STATUS_CANCELLED => Labels::getLabel('LBL_PAYMENT_GATEWAY_STATUS_CANCELLED', $langId),
        );
    }

    public static function getOrderStatusArr($langId, $inArray = array(), $current = 0)
    {
        $current = FatUtility::int($current);
        $srch = new SearchBase(Orders::DB_TBL_ORDERS_STATUS, 'ostatus');

        $srchOrderStatus = clone $srch;
        $srchOrderStatus->addCondition('orderstatus_id', '=', $current);
        $srchOrderStatus->doNotCalculateRecords();
        $srchOrderStatus->doNotLimitRecords();
        $srchOrderStatus->addMultipleFields(array('orderstatus_priority'));
        $record = FatApp::getDb()->fetch($srchOrderStatus->getResultSet());
        $orderStatusPriority = (!$record ? 0 : FatUtility::int($record['orderstatus_priority']));

        if ($langId > 0) {
            $srch->joinTable(
                Orders::DB_TBL_ORDERS_STATUS_LANG,
                'LEFT OUTER JOIN',
                'ostatus_l.orderstatuslang_orderstatus_id = ostatus.orderstatus_id
			AND ostatus_l.orderstatuslang_lang_id = ' . $langId,
                'ostatus_l'
            );
        }

        if (count($inArray) > 0) {
            $srch->addDirectCondition('orderstatus_id IN (' . implode(",", $inArray) . ')');
        }

        if ($current > 0) {
            $srch->addCondition('orderstatus_priority', '>=', $orderStatusPriority);
        }

        $srch->addCondition('orderstatus_is_active', '=', applicationConstants::ACTIVE);
        $srch->doNotCalculateRecords();
        $srch->doNotLimitRecords();
        $srch->addMultipleFields(array('orderstatus_id', 'IFNULL(orderstatus_name,orderstatus_identifier) as orderstatus_name'));

        $rs = $srch->getResultSet();
        if (!$rs) {
            return array();
        }
        return $row = FatApp::getDb()->fetchAllAssoc($rs);
    }

    public static function getOrderProductStatusArr($langId, $inArray = array(), $current = 0, $isDigital = 0, $assoc = true)
    {
        $current = FatUtility::int($current);
        $srch = new SearchBase(Orders::DB_TBL_ORDERS_STATUS, 'ostatus');
        $srch->addCondition('orderstatus_type', '=', Orders::ORDER_PRODUCT);
        $srchOrderStatus = clone $srch;
        if (0 < $current) {
            $srchOrderStatus->addCondition('orderstatus_id', '=', $current);
        }
        $srchOrderStatus->addCondition('orderstatus_type', '=', Orders::ORDER_PRODUCT);
        $srchOrderStatus->doNotCalculateRecords();
        $srchOrderStatus->doNotLimitRecords();
        $srchOrderStatus->addMultipleFields(array('orderstatus_priority'));
        $record = FatApp::getDb()->fetch($srchOrderStatus->getResultSet());

        $orderStatusPriority = FatUtility::int($record['orderstatus_priority']);

        if ($langId > 0) {
            $srch->joinTable(
                Orders::DB_TBL_ORDERS_STATUS_LANG,
                'LEFT OUTER JOIN',
                'ostatus_l.orderstatuslang_orderstatus_id = ostatus.orderstatus_id
			AND ostatus_l.orderstatuslang_lang_id = ' . $langId,
                'ostatus_l'
            );
        }

        if (count($inArray) > 0) {
            $srch->addDirectCondition('orderstatus_id IN (' . implode(",", $inArray) . ')');
        }

        if ($current > 0) {
            $srch->addCondition('orderstatus_priority', '>=', $orderStatusPriority);
        }

        if ($isDigital === OrderStatus::FOR_DIGITAL_ONLY) {
            $srch->addCondition('orderstatus_is_digital', '=', applicationConstants::YES);
        } elseif ($isDigital === OrderStatus::FOR_NON_DIGITAL) {
            $srch->addCondition('orderstatus_is_digital', '=', applicationConstants::NO);
        }

        $srch->addCondition('orderstatus_is_active', '=', applicationConstants::ACTIVE);
        $srch->addOrder('orderstatus_priority');
        $srch->doNotCalculateRecords();
        $srch->doNotLimitRecords();
        $srch->addMultipleFields(array('orderstatus_id', 'IFNULL(orderstatus_name,orderstatus_identifier) as orderstatus_name'));

        $rs = $srch->getResultSet();
        if (true === $assoc) {
            return FatApp::getDb()->fetchAllAssoc($rs);
        }
        return FatApp::getDb()->fetchAll($rs);
    }
    public static function getOrderSubscriptionStatusArr($langId, $inArray = array(), $current = 0)
    {
        $current = FatUtility::int($current);
        $srch = new SearchBase(Orders::DB_TBL_ORDERS_STATUS, 'ostatus');
        $srch->addCondition('orderstatus_type', '=', Orders::ORDER_SUBSCRIPTION);
        $srchOrderStatus = clone $srch;
        $srchOrderStatus->addCondition('orderstatus_id', '=', $current);
        $srchOrderStatus->addCondition('orderstatus_type', '=', Orders::ORDER_SUBSCRIPTION);
        $srchOrderStatus->doNotCalculateRecords();
        $srchOrderStatus->doNotLimitRecords();
        $srchOrderStatus->addMultipleFields(array('orderstatus_priority'));
        $record = FatApp::getDb()->fetch($srchOrderStatus->getResultSet());
        $orderStatusPriority = !$record ? 0 : FatUtility::int($record['orderstatus_priority']);

        if ($langId > 0) {
            $srch->joinTable(
                Orders::DB_TBL_ORDERS_STATUS_LANG,
                'LEFT OUTER JOIN',
                'ostatus_l.orderstatuslang_orderstatus_id = ostatus.orderstatus_id
			AND ostatus_l.orderstatuslang_lang_id = ' . $langId,
                'ostatus_l'
            );
        }

        if (count($inArray) > 0) {
            $srch->addDirectCondition('orderstatus_id IN (' . implode(",", $inArray) . ')');
        }

        if ($current > 0) {
            $srch->addCondition('orderstatus_priority', '>=', $orderStatusPriority);
        }

        $srch->addCondition('orderstatus_is_active', '=', applicationConstants::ACTIVE);
        $srch->doNotCalculateRecords();
        $srch->doNotLimitRecords();
        $srch->addMultipleFields(array('orderstatus_id', 'IFNULL(orderstatus_name,orderstatus_identifier) as orderstatus_name'));

        $rs = $srch->getResultSet();
        return FatApp::getDb()->fetchAllAssoc($rs);
    }

    public function getOrderNo()
    {
        return $this->orderNo;
    }

    public function getOrderId()
    {
        return $this->orderId;
    }

    public static function getSearchObject($langId = 0)
    {
        $langId = FatUtility::int($langId);
        $srch = new SearchBase(static::DB_TBL, 'o');

        if ($langId > 0) {
            $srch->joinTable(
                static::DB_TBL_LANG,
                'LEFT OUTER JOIN',
                'o_l.orderlang_order_id = o.order_id AND orderlang_lang_id = ' . $langId,
                'o_l'
            );
        }
        return $srch;
    }

    public static function getOrderProductSearchObject($langId = 0)
    {
        $langId = FatUtility::int($langId);
        $srch = new SearchBase(static::DB_TBL_ORDER_PRODUCTS, 'op');

        if ($langId > 0) {
            $srch->joinTable(
                static::DB_TBL_ORDER_PRODUCTS_LANG,
                'LEFT OUTER JOIN',
                'oplang_op_id = op.op_id AND oplang_lang_id = ' . $langId,
                'op_l'
            );
        }
        return $srch;
    }


    public function addUpdateOrder($data = array(), $langId = 1)
    {
        $orderType = $data['order_type'];
        if ($orderType == Orders::ORDER_SUBSCRIPTION) {
            return $this->addUpdateSubscriptionOrder($data, $langId);
        } elseif ($orderType == Orders::ORDER_PRODUCT) {
            return $this->addUpdateProductOrder($data, $langId);
        } elseif ($orderType == Orders::ORDER_WALLET_RECHARGE) {
            return $this->addUpdateWalletRechargeOrder($data, $langId);
        }
    }

    private function addUpdateProductOrder($data = array(), $langId = 1)
    {
        $db = FatApp::getDb();
        $ordersLangData = [];
        if (array_key_exists('orderLangData', $data)) {
            $ordersLangData = $data['orderLangData'];
            unset($data['orderLangData']);
        }

        $discountInfo = [];
        if (array_key_exists('order_discount_info', $data)) {
            $discountInfo = !empty($data['order_discount_info']) ? json_decode($data['order_discount_info'], true): [];
        }

        $products = [];
        if (array_key_exists('products', $data)) {
            $products = $data['products'];
            unset($data['products']);
        }

        $addresses = [];
        if (array_key_exists('userAddresses', $data)) {
            $addresses = $data['userAddresses'];
            unset($data['userAddresses']);
        }

        $extras = [];
        if (array_key_exists('extra', $data)) {
            $extras = $data['extra'];
            unset($data['extra']);
        }

        $prodCharges = [];
        if (array_key_exists('prodCharges', $data)) {
            $prodCharges = $data['prodCharges'];
            unset($data['prodCharges']);
        }

        if (!empty($data['order_id'])) {
            $oldOrderData = Orders::getAttributesById($data['order_id'], ['order_payment_status', 'order_user_id', 'order_number']);
            if (false == $oldOrderData || Orders::ORDER_PAYMENT_PENDING != $oldOrderData['order_payment_status'] ||  $data['order_user_id'] != $oldOrderData['order_user_id']) {
                $data['order_id'] = 0;
            }

            if (!empty($oldOrderData)) {
                $this->orderNo = $oldOrderData['order_number'];
            }
        }

        if (empty($data['order_id'])) {
            $this->orderNo = $this->generateOrderNo();
            $data['order_number'] = $this->getOrderNo();
        }

        $recordObj = new TableRecord(static::DB_TBL);
        $recordObj->assignValues($data);
        $flds_update_on_duplicate = $data;
        unset($flds_update_on_duplicate['order_id']);

        $db->startTransaction();
        if (!$recordObj->addNew(array(), $flds_update_on_duplicate)) {
            $db->rollbackTransaction();
            $this->error = $recordObj->getError();
            return false;
        }

        $this->orderId = $recordObj->getId();

        $_SESSION['shopping_cart']["order_id"] = $this->getOrderId();

        $this->setMainTableRecordId($this->getOrderId());

        if (array_key_exists('coupon_id', $discountInfo)) {

            $couponInfo = DiscountCoupons::getValidCoupons($data['order_user_id'], $data['order_language_id'], $data['order_discount_coupon_code'], $this->orderId);
            if ($couponInfo == false) {
                $this->error = Labels::getLabel('ERR_INVALID_COUPON_CODE', $data['order_language_id']);
                return false;
            }

            $holdCouponData = array(
                'ochold_order_id' => $this->getOrderId(),
                'ochold_coupon_id' => $discountInfo['coupon_id'],
                'ochold_added_on' => date('Y-m-d H:i:s')
            );

            if (!FatApp::getDb()->insertFromArray(DiscountCoupons::DB_TBL_COUPON_HOLD_PENDING_ORDER, $holdCouponData, true, array(), $holdCouponData)) {
                $db->rollbackTransaction();
                $this->error = FatApp::getDb()->getError();
                return false;
            }

            if (!FatApp::getDb()->deleteRecords(DiscountCoupons::DB_TBL_COUPON_HOLD, array('smt' => 'couponhold_coupon_id = ? and couponhold_user_id = ?', 'vals' => array($discountInfo['coupon_id'], $data['order_user_id'])))) {
                $db->rollbackTransaction();
                $this->error = FatApp::getDb()->getError();
                return false;
            }
        }

        if (!empty($ordersLangData)) {
            $db->deleteRecords(static::DB_TBL_LANG, array('smt' => 'orderlang_order_id = ?', 'vals' => [$this->getOrderId()]));
            $recordObj = new TableRecord(static::DB_TBL_LANG);
            foreach ($ordersLangData as $orderLangData) {
                $orderLangData['orderlang_order_id'] = $this->getOrderId();
                $recordObj->assignValues($orderLangData);
                if (!$recordObj->addNew()) {
                    $db->rollbackTransaction();
                    $this->error = $recordObj->getError();
                    return false;
                }
            }
        }

        $row = OrderProduct::getOpIdArrByOrderId($this->getOrderId());

        if (!empty($row)) {
            foreach ($row as $opId => $val) {
                $db->deleteRecords(OrderProduct::DB_TBL_CHARGES, array('smt' => OrderProduct::DB_TBL_CHARGES_PREFIX . 'op_id = ?', 'vals' => array($opId)));
                $db->deleteRecords(Orders::DB_TBL_ORDER_PRODUCTS_SHIPPING, array('smt' => 'opshipping_op_id = ?', 'vals' => array($opId)));
                $db->deleteRecords(Orders::DB_TBL_ORDER_PRODUCTS_SHIPPING_LANG, array('smt' => 'opshippinglang_op_id = ?', 'vals' => array($opId)));
                $db->deleteRecords(OrderProductChargeLog::DB_TBL, array('smt' => 'opchargelog_op_id = ?', 'vals' => array($opId)));
                $db->deleteRecords(OrderProductChargeLog::DB_TBL_LANG, array('smt' => 'opchargeloglang_op_id = ?', 'vals' => array($opId)));
                $db->deleteRecords(OrderProductSpecifics::DB_TBL, array('smt' => 'ops_op_id = ?', 'vals' => array($opId)));
                $db->deleteRecords(static::DB_TBL_ORDER_USER_ADDRESS, array('smt' => 'oua_op_id = ?', 'vals' => array($opId)));
            }
        }

        $qry = 'DELETE op, op_l
        FROM ' . static::DB_TBL_ORDER_PRODUCTS . ' as op
        LEFT JOIN ' . static::DB_TBL_ORDER_PRODUCTS_LANG . ' as op_l ON op_l.oplang_op_id = op.op_id
        WHERE op.op_order_id = "' . $this->getOrderId() . '"';

        if (!$db->query($qry)) {
            $db->rollbackTransaction();
            $this->error = $recordObj->getError();
            return false;
        };
        if (!empty($products)) {
            $counter = 1;
            foreach ($products as $selprodId => $product) {
                $op_invoice_number = $this->getOrderNo() . '-S' . str_pad($counter, 4, '0', STR_PAD_LEFT);
                $product['op_order_id'] = $this->getOrderId();
                $product['op_invoice_number'] = $op_invoice_number;
                $opRecordObj = new TableRecord(static::DB_TBL_ORDER_PRODUCTS);
                $opRecordObj->assignValues($product);
                if (!$opRecordObj->addNew()) {
                    $db->rollbackTransaction();
                    $this->error = $opRecordObj->getError();
                    return false;
                }

                $op_id = $opRecordObj->getId();

                /*Save order product settings [*/
                $orderProduct = new OrderProduct($op_id);
                if (!$orderProduct->setupSettings()) {
                    $db->rollbackTransaction();
                    $this->error = $orderProduct->getError();
                    return false;
                }
                /*]*/

                /* saving of products lang data[ */
                $productsLangData = $product['productsLangData'];
                if (!empty($productsLangData)) {
                    foreach ($productsLangData as $productLangData) {
                        $productLangData['oplang_op_id'] = $op_id;
                        $opLangRecordObj = new TableRecord(static::DB_TBL_ORDER_PRODUCTS_LANG);
                        $opLangRecordObj->assignValues($productLangData);
                        if (!$opLangRecordObj->addNew()) {
                            $db->rollbackTransaction();
                            $this->error = $opLangRecordObj->getError();
                            return false;
                        }
                    }
                }

                /* Saving of digital download data[ */

                // $product['product_attachements_with_inventory'] = 1;

                if ($product['op_product_type'] == Product::PRODUCT_TYPE_DIGITAL) {
                    $db->deleteRecords(
                        AttachedFile::DB_TBL,
                        array(
                            'smt' => 'afile_type = ? AND afile_record_id = ?',
                            'vals' => array(AttachedFile::FILETYPE_ORDER_PRODUCT_DIGITAL_DOWNLOAD, $op_id)
                        )
                    );

                    $selProdOption = explode('_', $product['op_selprod_code']);
                    array_shift($selProdOption);
                    if (0 < count($selProdOption)) {
                        $optionComb = implode('_', $selProdOption);
                    } else {
                        $optionComb = '0';
                    }

                    $recordId = $product['op_selprod_id'];
                    $productType = Product::CATALOG_TYPE_INVENTORY;
                    if (0 == $product['product_attachements_with_inventory']) {
                        $recordId = $product['selprod_product_id'];
                        $productType = Product::CATALOG_TYPE_PRIMARY;
                    }

                    /* TODO Get all download main files references and save */
                    /* $attachments = AttachedFile::getMultipleAttachments(AttachedFile::FILETYPE_SELLER_PRODUCT_DIGITAL_DOWNLOAD, $product['op_selprod_id'], 0, -1);
                    if (!empty($attachments)) {
                        foreach ($attachments as $digitalFile) {
                            unset($digitalFile['afile_id']);
                            $digitalFile['afile_record_id'] = $op_id;
                            $digitalFile['afile_type'] = AttachedFile::FILETYPE_ORDER_PRODUCT_DIGITAL_DOWNLOAD;
                            if (!$db->insertFromArray(AttachedFile::DB_TBL, $digitalFile)) {
                                $db->rollbackTransaction();
                                $this->error = $opLangRecordObj->getError();
                                return false;
                            }
                        }
                    } */
                    $attrs = [
                        'pddr_id',
                        'pddr_options_code',
                        'afile.afile_record_id as afile_record_id',
                        'afile.afile_name as mainfile',
                        'afile.afile_lang_id as afile_lang_id',
                        'afile.afile_id as afile_id',
                        'afile.afile_physical_path as mainfile_physical_path',
                    ];

                    $records = DigitalDownloadSearch::getAttachments(
                        $recordId,
                        $productType,
                        $optionComb,
                        $data['order_language_id'],
                        true,
                        AttachedFile::FILETYPE_SELLER_PRODUCT_DIGITAL_DOWNLOAD,
                        $attrs
                    );

                    if ('0' != $optionComb) {
                        $commonRecords = DigitalDownloadSearch::getAttachments(
                            $recordId,
                            $productType,
                            '0',
                            $data['order_language_id'],
                            true,
                            AttachedFile::FILETYPE_SELLER_PRODUCT_DIGITAL_DOWNLOAD,
                            $attrs
                        );
                        $records = array_replace($records, $commonRecords);
                    }

                    if (0 < count($records)) {
                        foreach ($records as $digitalFile) {
                            unset($digitalFile['afile_id']);
                            $dataToSave = [];
                            $dataToSave['afile_record_id'] = $op_id;
                            $dataToSave['afile_screen'] = 0;
                            $dataToSave['afile_physical_path'] = $digitalFile['mainfile_physical_path'];
                            $dataToSave['afile_name'] = $digitalFile['mainfile'];
                            $dataToSave['afile_downloaded_times'] = 0;
                            $dataToSave['afile_updated_at'] = date('Y-m-d H:i:s');
                            $dataToSave['afile_type'] = AttachedFile::FILETYPE_ORDER_PRODUCT_DIGITAL_DOWNLOAD;
                            if (!$db->insertFromArray(AttachedFile::DB_TBL, $dataToSave)) {
                                $db->rollbackTransaction();
                                $this->error = $db->getError();
                                return false;
                            }
                        }
                    }
                    /*]*/

                    /* Saving of digital download Links[ */
                    /* TODO Get all download main links and save*/

                    $records = [];

                    $records = DigitalDownloadSearch::getLinks($recordId, $productType, $optionComb);

                    if ('0' != $optionComb) {
                        $commonRecords = DigitalDownloadSearch::getLinks($recordId, $productType, '0');
                        $records = array_replace($records, $commonRecords);
                    }
                    if (0 < count($records)) {
                        foreach ($records as $link) {
                            $linkData['opddl_op_id'] = $op_id;
                            $linkData['opddl_downloadable_link'] = $link['pdl_download_link'];
                            if (!$db->insertFromArray(OrderProductDigitalLinks::DB_TBL, $linkData)) {
                                $db->rollbackTransaction();
                                $this->error = $opLangRecordObj->getError();
                                return false;
                            }
                        }
                    }

                    /* $linkData = array();
                    $sellerProduct = SellerProduct::getAttributesById($product['op_selprod_id'], array('selprod_downloadable_link'), false);
                    $downlodableLinks = preg_split("/\n|,/", $sellerProduct['selprod_downloadable_link']);
                    foreach ($downlodableLinks as $link) {
                        if ($link == '') {
                            continue;
                        }
                        $linkData['opddl_op_id'] = $op_id;
                        $linkData['opddl_downloadable_link'] = $link;
                        if (!$db->insertFromArray(OrderProductDigitalLinks::DB_TBL, $linkData)) {
                            $db->rollbackTransaction();
                            $this->error = $opLangRecordObj->getError();
                            return false;
                        }
                    } */
                    /*]*/
                }
                /*]*/

                /* saving of products Shipping data[ */
                $productsShippingData = $product['productShippingData'];
                if (!empty($productsShippingData)) {
                    $productsShippingData['opshipping_op_id'] = $op_id;
                    $opShippingRecordObj = new TableRecord(static::DB_TBL_ORDER_PRODUCTS_SHIPPING);
                    $opShippingRecordObj->assignValues($productsShippingData);
                    if (!$opShippingRecordObj->addNew()) {
                        $db->rollbackTransaction();
                        $this->error = $opShippingRecordObj->getError();
                        return false;
                    }
                }
                /*]*/

                /* saving of products Pickup data[ */
                $productPickUpData = $product['productPickUpData'];
                if (!empty($productPickUpData)) {
                    $productPickUpData['opshipping_op_id'] = $op_id;
                    $productPickUpData['opshipping_by_seller_user_id'] = !empty($productPickUpData['opshipping_by_seller_user_id']) ? $productPickUpData['opshipping_by_seller_user_id'] : 0;
                    $opShippingRecordObj = new TableRecord(static::DB_TBL_ORDER_PRODUCTS_SHIPPING);
                    $opShippingRecordObj->assignValues($productPickUpData);
                    if (!$opShippingRecordObj->addNew()) {
                        $db->rollbackTransaction();
                        $this->error = $opShippingRecordObj->getError();
                        return false;
                    }
                }
                /*]*/

                /* saving of products Pickup address[ */
                $productPickupAddress = $product['productPickupAddress'];
                if (!empty($productPickupAddress)) {
                    $productPickupAddress['oua_order_id'] = $this->getOrderId();
                    $productPickupAddress['oua_op_id'] = $op_id;

                    $ouaRecordObj = new TableRecord(static::DB_TBL_ORDER_USER_ADDRESS);
                    $ouaRecordObj->assignValues($productPickupAddress);
                    if (!$ouaRecordObj->addNew()) {
                        $db->rollbackTransaction();
                        $this->error = $ouaRecordObj->getError();
                        return false;
                    }
                }
                /*]*/

                /* saving of products Shipping lang data[ */
                $productsShippingLangData = $product['productShippingLangData'];
                if (!empty($productsShippingLangData)) {
                    foreach ($productsShippingLangData as $productShippingLangData) {
                        $productShippingLangData['opshippinglang_op_id'] = $op_id;
                        $opShippingLangRecordObj = new TableRecord(static::DB_TBL_ORDER_PRODUCTS_SHIPPING_LANG);
                        $opShippingLangRecordObj->assignValues($productShippingLangData);
                        if (!$opShippingLangRecordObj->addNew()) {
                            $db->rollbackTransaction();
                            $this->error = $opShippingLangRecordObj->getError();
                            return false;
                        }
                    }
                }
                /*]*/

                /* saving of products Charges log & log lang data[ */
                $prodChargeslogData = $product['productChargesLogData'];
                if (!empty($prodChargeslogData)) {
                    $db->deleteRecords(OrderProductChargeLog::DB_TBL, array('smt' => 'opchargelog_op_id = ?', 'vals' => array($op_id)));
                    $db->deleteRecords(OrderProductChargeLog::DB_TBL_LANG, array('smt' => 'opchargeloglang_op_id = ?', 'vals' => array($op_id)));
                    foreach ($prodChargeslogData as $id => $prodChargeslog) {
                        $opChargeLog = new OrderProductChargeLog($op_id);
                        $prodChargeslog['opchargelog_op_id'] = $op_id;
                        $opChargeLog->assignValues($prodChargeslog);
                        if (!$opChargeLog->save()) {
                            $db->rollbackTransaction();
                            $this->error = $opChargeLog->getError();
                            return false;
                        }
                        $opChargeLogId = $opChargeLog->getMainTableRecordId();
                        foreach ($prodChargeslog['langData'] as $langId => $langData) {
                            $opChargeLogLangObj = new TableRecord(OrderProductChargeLog::DB_TBL_LANG);
                            $langData['opchargeloglang_opchargelog_id'] = $opChargeLogId;
                            $langData['opchargeloglang_op_id'] = $op_id;
                            $opChargeLogLangObj->assignValues($langData);
                            if (!$opChargeLogLangObj->addNew()) {
                                $db->rollbackTransaction();
                                $this->error = $opChargeLogLangObj->getError();
                                return false;
                            }
                        }
                    }
                }
                /*]*/

                if (!empty($prodCharges)) {
                    $chargeTypeArr = OrderProduct::getChargeTypeArr($langId);
                    foreach ($chargeTypeArr as $chargeType => $chargeVal) {
                        if (!array_key_exists($selprodId, $prodCharges)) {
                            continue;
                        }

                        if (!array_key_exists($chargeType, $prodCharges[$selprodId])) {
                            continue;
                        }

                        $amnt = $prodCharges[$selprodId][$chargeType]['amount'];
                        if ($amnt == 0) {
                            continue;
                        }
                        $oChargesRecordObj = new TableRecord(OrderProduct::DB_TBL_CHARGES);
                        $assignValues = array(
                            OrderProduct::DB_TBL_CHARGES_PREFIX . 'op_id' => $op_id,
                            OrderProduct::DB_TBL_CHARGES_PREFIX . 'order_type' => ORDERS::ORDER_PRODUCT,
                            OrderProduct::DB_TBL_CHARGES_PREFIX . 'type' => $chargeType,
                            OrderProduct::DB_TBL_CHARGES_PREFIX . 'amount' => $prodCharges[$selprodId][$chargeType]['amount'],
                        );
                        $oChargesRecordObj->assignValues($assignValues);
                        if (!$oChargesRecordObj->addNew(array())) {
                            $db->rollbackTransaction();
                            $this->error = $oChargesRecordObj->getError();
                            return false;
                        }
                    }
                }
                /* ] */

                $orderProdSpecificsObj = new OrderProductSpecifics($op_id);
                $orderProdSpecificsObj->assignValues($product['productSpecifics']);
                $orderProdSpecificsObj->setFldValue('ops_op_id', $op_id);

                if (!$orderProdSpecificsObj->addNew(array(), $orderProdSpecificsObj->getFlds())) {
                    $this->error = $orderProdSpecificsObj->getError();
                    return false;
                }

                $counter++;
            }
        }
        /* CommonHelper::printArray($addresses);die; */
        if (!empty($addresses)) {
            $db->deleteRecords(static::DB_TBL_ORDER_USER_ADDRESS, array('smt' => 'oua_order_id = ? and oua_op_id = ?', 'vals' => array($this->getOrderId(), 0)));
            $ouaRecordObj = new TableRecord(static::DB_TBL_ORDER_USER_ADDRESS);
            foreach ($addresses as $address) {
                $address['oua_order_id'] = $this->getOrderId();
                $ouaRecordObj->assignValues($address);
                if (!$ouaRecordObj->addNew()) {
                    $db->rollbackTransaction();
                    $this->error = $ouaRecordObj->getError();
                    return false;
                }
            }
        }

        if (!empty($extras)) {
            $oextraRecordObj = new TableRecord(static::DB_TBL_ORDER_EXTRAS);
            $extras['oextra_order_id'] = $this->getOrderId();
            $flds_update_on_duplicate = $extras;
            unset($flds_update_on_duplicate['oextra_order_id']);
            $oextraRecordObj->assignValues($extras);
            if (!$oextraRecordObj->addNew(array(), $flds_update_on_duplicate)) {
                $db->rollbackTransaction();
                $this->error = $oextraRecordObj->getError();
                return false;
            }
        }

        $db->commitTransaction();
        return $this->getOrderId();
    }

    private function addUpdateSubscriptionOrder($data = array(), $langId = 1)
    {
        $db = FatApp::getDb();
        $ordersLangData = $data['orderLangData'];
        unset($data['orderLangData']);
        $subscriptions = $data['subscriptions'];
        unset($data['subscriptions']);
        $extras = $data['extra'];
        unset($data['extra']);
        $subscrCharges = $data['subscrCharges'];
        unset($data['subscrCharges']);

        $discountInfo = [];
        if (array_key_exists('order_discount_info', $data)) {
            $discountInfo = !empty($data['order_discount_info']) ? json_decode($data['order_discount_info'], true) : [];
        }

        if (!empty($data['order_id'])) {
            $oldOrderData = Orders::getAttributesById($data['order_id'], ['order_payment_status', 'order_user_id', 'order_number']);
            if (false === $oldOrderData || Orders::ORDER_PAYMENT_PENDING != $oldOrderData['order_payment_status'] ||  $data['order_user_id'] != $oldOrderData['order_user_id']) {
                $data['order_id'] = 0;
            }

            if (!empty($oldOrderData)) {
                $this->orderNo = $oldOrderData['order_number'];
            }
        }
        if (empty($data['order_id'])) {
            $this->orderNo = $this->generateOrderNo();
            $data['order_number'] = $this->getOrderNo();
        }

        $this->orderNo = $data['order_id'];

        $recordObj = new TableRecord(static::DB_TBL);
        $recordObj->assignValues($data);
        $flds_update_on_duplicate = $data;
        unset($flds_update_on_duplicate['order_id']);

        $db->startTransaction();
        if (!$recordObj->addNew(array(), $flds_update_on_duplicate)) {
            $db->rollbackTransaction();
            $this->error = $recordObj->getError();
            return false;
        }
        $this->orderId = $recordObj->getId();

        $_SESSION['subscription_shopping_cart']["order_id"] = $this->getOrderId();

        $this->setMainTableRecordId($this->getOrderId());

        $db->deleteRecords(static::DB_TBL_LANG, array('smt' => 'orderlang_order_id = ?', 'vals' => [$this->getOrderId()]));
        if (!empty($ordersLangData)) {
            $recordObj = new TableRecord(static::DB_TBL_LANG);
            foreach ($ordersLangData as $orderLangData) {
                $orderLangData['orderlang_order_id'] = $this->getOrderId();
                $recordObj->assignValues($orderLangData);
                if (!$recordObj->addNew()) {
                    $db->rollbackTransaction();
                    $this->error = $recordObj->getError();
                    return false;
                }
            }
        }

        $row = OrderSubscription::getOSSubIdArrByOrderId($this->getOrderId());
        if (!empty($row)) {
            foreach ($row as $opId => $val) {
                $db->deleteRecords(OrderProduct::DB_TBL_CHARGES, array('smt' => OrderProduct::DB_TBL_CHARGES_PREFIX . 'op_id = ?  and ' . OrderProduct::DB_TBL_CHARGES_PREFIX . 'order_type =?', 'vals' => array($opId, Orders::ORDER_SUBSCRIPTION)));
            }
        }

        $qry = 'DELETE os, os_l
        FROM ' . OrderSubscription::DB_TBL . ' as os
        LEFT JOIN ' . OrderSubscription::DB_TBL_LANG . ' as os_l ON os_l.ossubslang_ossubs_id = os.ossubs_id
        WHERE os.ossubs_order_id = "' . $this->getOrderId() . '"';

        if (!$db->query($qry)) {
            $db->rollbackTransaction();
            $this->error = $recordObj->getError();
            return false;
        };

        if (!empty($subscriptions)) {
            $opRecordObj = new TableRecord(OrderSubscription::DB_TBL);
            $opLangRecordObj = new TableRecord(OrderSubscription::DB_TBL_LANG);

            $counter = 1;
            foreach ($subscriptions as $spPlanId => $subscription) {
                $op_invoice_number = $this->getOrderNo() . '-S' . str_pad($counter, 4, '0', STR_PAD_LEFT);
                $subscription[OrderSubscription::DB_TBL_PREFIX . 'order_id'] = $this->getOrderId();
                $subscription[OrderSubscription::DB_TBL_PREFIX . 'invoice_number'] = $op_invoice_number;
                $opRecordObj->assignValues($subscription);
                if (!$opRecordObj->addNew()) {
                    $db->rollbackTransaction();
                    $this->error = $opRecordObj->getError();
                    return false;
                }

                $oss_id = $opRecordObj->getId();

                /* saving of products lang data[ */
                $subscriptionsLangData = $subscription['subscriptionsLangData'];
                if (!empty($subscriptionsLangData)) {
                    foreach ($subscriptionsLangData as $subscriptionLangData) {
                        $subscriptionLangData[OrderSubscription::DB_TBL_LANG_PREFIX . 'ossubs_id'] = $oss_id;
                        $opLangRecordObj->assignValues($subscriptionLangData);
                        if (!$opLangRecordObj->addNew()) {
                            $db->rollbackTransaction();

                            $this->error = $opLangRecordObj->getError();
                            return false;
                        }
                    }
                }

                if (!empty($subscrCharges)) {
                    $chargeTypeArr = OrderSubscription::getChargeTypeArr($langId);

                    $oChargesRecordObj = new TableRecord(OrderProduct::DB_TBL_CHARGES);
                    foreach ($chargeTypeArr as $chargeType => $chargeVal) {
                        if (!array_key_exists($chargeType, $subscrCharges[$spPlanId])) {
                            continue;
                        }

                        $amnt = $subscrCharges[$spPlanId][$chargeType]['amount'];
                        if ($amnt == 0) {
                            continue;
                        }

                        $assignValues = array(
                            OrderProduct::DB_TBL_CHARGES_PREFIX . 'op_id' => $oss_id,
                            OrderProduct::DB_TBL_CHARGES_PREFIX . 'order_type' => Orders::ORDER_SUBSCRIPTION,
                            OrderProduct::DB_TBL_CHARGES_PREFIX . 'type' => $chargeType,
                            OrderProduct::DB_TBL_CHARGES_PREFIX . 'amount' => $subscrCharges[$spPlanId][$chargeType]['amount'],
                        );

                        $oChargesRecordObj->assignValues($assignValues);
                        if (!$oChargesRecordObj->addNew(array())) {
                            $db->rollbackTransaction();
                            $this->error = $oChargesRecordObj->getError();
                            return false;
                        }
                    }
                }
                /* ] */
                $counter++;
            }
        }

        if (!empty($extras)) {
            $oextraRecordObj = new TableRecord(static::DB_TBL_ORDER_EXTRAS);
            $extras['oextra_order_id'] = $this->getOrderId();
            $flds_update_on_duplicate = $extras;
            unset($flds_update_on_duplicate['oextra_order_id']);
            $oextraRecordObj->assignValues($extras);
            if (!$oextraRecordObj->addNew(array(), $flds_update_on_duplicate)) {
                $db->rollbackTransaction();
                $this->error = $oextraRecordObj->getError();
                return false;
            }
        }

        if (array_key_exists('coupon_id', $discountInfo)) {
            $couponInfo = DiscountCoupons::getValidSubscriptionCoupons($data['order_user_id'], $data['order_language_id'], $data['order_discount_coupon_code'], $this->orderId);
            if ($couponInfo == false) {
                $this->error = Labels::getLabel('LBL_Invalid_Coupon_Code', $data['order_language_id']);
                return false;
            }

            $holdCouponData = array(
                'ochold_order_id' => $this->getOrderId(),
                'ochold_coupon_id' => $discountInfo['coupon_id'],
                'ochold_added_on' => date('Y-m-d H:i:s')
            );

            if (!FatApp::getDb()->insertFromArray(DiscountCoupons::DB_TBL_COUPON_HOLD_PENDING_ORDER, $holdCouponData, true, array(), $holdCouponData)) {
                $db->rollbackTransaction();
                $this->error = FatApp::getDb()->getError();
                return false;
            }
            if (!FatApp::getDb()->deleteRecords(DiscountCoupons::DB_TBL_COUPON_HOLD, array('smt' => 'couponhold_coupon_id = ? and couponhold_user_id = ?', 'vals' => array($discountInfo['coupon_id'], $data['order_user_id'])))) {
                $db->rollbackTransaction();
                $this->error = FatApp::getDb()->getError();
                return false;
            }
        }

        $db->commitTransaction();

        return $this->getOrderId();
    }

    private function addUpdateWalletRechargeOrder($data, $langId)
    {
        $db = FatApp::getDb();
        $ordersLangData = $data['orderLangData'];
        unset($data['orderLangData']);

        $userAddresses = $data['userAddresses'];
        unset($data['userAddresses']);

        $extras = $data['extra'];
        unset($data['extra']);

        if (!empty($data['order_id'])) {
            $oldOrderData = Orders::getAttributesById($data['order_id'], ['order_payment_status', 'order_user_id', 'order_number']);
            if (false === $oldOrderData || Orders::ORDER_PAYMENT_PENDING != $oldOrderData['order_payment_status'] ||  $data['order_user_id'] != $oldOrderData['order_user_id']) {
                $data['order_id'] = 0;
            }

            if (!empty($oldOrderData)) {
                $this->orderNo = $oldOrderData['order_number'];
            }
        }

        if (empty($data['order_id'])) {
            $this->orderNo = $this->generateOrderNo();
            $data['order_number'] = $this->getOrderNo();
        }
        $this->orderNo = $data['order_id'];

        $recordObj = new TableRecord(static::DB_TBL);
        $recordObj->assignValues($data);
        $flds_update_on_duplicate = $data;
        unset($flds_update_on_duplicate['order_id']);

        $db->startTransaction();
        if (!$recordObj->addNew(array(), $flds_update_on_duplicate)) {
            $db->rollbackTransaction();
            $this->error = $recordObj->getError();
            return false;
        }
        $this->orderId = $recordObj->getId();

        $_SESSION['wallet_recharge_cart']["order_id"] = $this->getOrderId();

        $this->setMainTableRecordId($this->getOrderId());

        $db->deleteRecords(static::DB_TBL_LANG, array('smt' => 'orderlang_order_id = ?', 'vals' => [$this->getOrderId()]));
        if (!empty($ordersLangData)) {
            $recordObj = new TableRecord(static::DB_TBL_LANG);
            foreach ($ordersLangData as $orderLangData) {
                $orderLangData['orderlang_order_id'] = $this->getOrderId();
                $recordObj->assignValues($orderLangData);
                if (!$recordObj->addNew()) {
                    $db->rollbackTransaction();
                    $this->error = $recordObj->getError();
                    return false;
                }
            }
        }

        if (!empty($extras)) {
            $oextraRecordObj = new TableRecord(static::DB_TBL_ORDER_EXTRAS);
            $extras['oextra_order_id'] = $this->getOrderId();
            $flds_update_on_duplicate = $extras;
            unset($flds_update_on_duplicate['oextra_order_id']);
            $oextraRecordObj->assignValues($extras);
            if (!$oextraRecordObj->addNew(array(), $flds_update_on_duplicate)) {
                $db->rollbackTransaction();
                $this->error = $oextraRecordObj->getError();
                return false;
            }
        }
        $db->commitTransaction();
        return $this->getOrderId();
    }

    public function getOrderById($order_id, $langId = 0)
    {
        if (!$order_id) {
            trigger_error(Labels::getLabel('ERR_ORDER_ID_IS_NOT_PASSED', $this->commonLangId), E_USER_ERROR);
        }
        $srch = static::getSearchObject($langId);
        $srch->joinTable(Plugin::DB_TBL, 'LEFT JOIN', 'order_pmethod_id = plugin_id');
        if (0 < $langId) {
            $srch->joinTable(Plugin::DB_TBL_LANG, 'LEFT OUTER JOIN', 'plugin_id = pm_l.pluginlang_plugin_id AND pm_l.pluginlang_lang_id = ' . $langId, 'pm_l');
        }
        $srch->addCondition('order_id', '=', $order_id);
        $srch->doNotCalculateRecords();
        $srch->setPageSize(1);
        return FatApp::getDb()->fetch($srch->getResultSet());
    }

    public static function getOrderByOrderNo($order_no, $langId = 0, $attr = [])
    {
        if (empty($order_no)) {
            trigger_error(Labels::getLabel('ERR_ORDER_NO_IS_NOT_PASSED', CommonHelper::getLangId()), E_USER_ERROR);
        }
        $srch = static::getSearchObject($langId);
        $srch->joinTable(Plugin::DB_TBL, 'LEFT JOIN', 'order_pmethod_id = plugin_id');
        if (0 < $langId) {
            $srch->joinTable(Plugin::DB_TBL_LANG, 'LEFT OUTER JOIN', 'plugin_id = pm_l.pluginlang_plugin_id AND pm_l.pluginlang_lang_id = ' . $langId, 'pm_l');
        }

        $srch->addCondition('order_number', '=', $order_no);
        $srch->doNotCalculateRecords();
        $srch->doNotLimitRecords();
        if (!empty($attr)) {
            $srch->addMultipleFields($attr);
        }
        $row = FatApp::getDb()->fetch($srch->getResultSet());
        return (is_array($row) ? $row : []);
    }

    public function getOrderAddresses($order_id, $opId = 0)
    {
        $opId = FatUtility::int($opId);
        $srch = new SearchBase(static::DB_TBL_ORDER_USER_ADDRESS);
        $srch->addCondition('oua_order_id', '=', $order_id);
        if ($opId > 0) {
            $srch->addCondition('oua_op_id', '=', $opId);
        }
        $srch->doNotCalculateRecords();
        return FatApp::getDb()->fetchAll($srch->getResultSet(), 'oua_type');
    }

    public function addOrderHistory($order_id, $order_status_id, $comment = '', $notify = false, $langId = 0)
    {
        $notify = FatUtility::int($notify);
        $langId = FatUtility::int($langId);
        $order_status_id = FatUtility::int($order_status_id);
        $orderInfo = $this->getOrderById($order_id, $langId);
        if (!$orderInfo) {
            $this->error = Labels::getLabel('ERR_ERROR_IN_UPDATING_THE_ORDER,_PLEASE_TRY_AFTER_SOME_TIME.', $langId);
            return false;
        }

        if (!$langId) {
            trigger_error(Labels::getLabel('ERR_LANGUAGE_NOT_SPECIFIED', $this->commonLangId), E_USER_ERROR);
        }

        $db = FatApp::getDb();

        if (!$db->updateFromArray(
            static::DB_TBL,
            array('order_status' => $order_status_id, 'order_date_updated' => date('Y-m-d H:i:s')),
            array('smt' => 'order_id = ? ', 'vals' => array($orderInfo["order_id"])),
            true
        )) {
            $this->error = $db->db->getError();
            return false;
        }

        $data_to_save_arr = array(
            'oshistory_order_id' => $order_id,
            'oshistory_op_id' => 0,
            'oshistory_orderstatus_id' => $order_status_id,
            'oshistory_order_payment_status' => 0,
            'oshistory_date_added' => date('Y-m-d H:i:s'),
            'oshistory_customer_notified' => $notify,
            'oshistory_tracking_number' => '',
            'oshistory_comments' => $comment
        );
        if (!$db->insertFromArray(static::DB_TBL_ORDER_STATUS_HISTORY, $data_to_save_arr, true)) {
            $this->error = $db->getError();
            return false;
        }

        // If order status is 0 then becomes greater than 0 send main html email
        if (!$orderInfo['order_status'] && $order_status_id) {
            $emailObj = new EmailHandler();
            $emailObj->newOrderBuyerAdmin($orderInfo["order_id"], $langId);
        }
        return true;
    }

    public function getChildOrders($criterias, $orderType = Orders::ORDER_PRODUCT, $langId = 0, $joinSellerProducts = false)
    {
        $langId = FatUtility::int($langId);

        $ocSrch = new SearchBase(OrderProduct::DB_TBL_CHARGES, 'opc');
        $ocSrch->doNotCalculateRecords();
        $ocSrch->doNotLimitRecords();
        $ocSrch->addMultipleFields(
            array(
                OrderProduct::DB_TBL_CHARGES_PREFIX . 'op_id',
                'sum(' . OrderProduct::DB_TBL_CHARGES_PREFIX . 'amount) as op_other_charges'
            )
        );

        $ocSrch->addGroupBy('opc.' . OrderProduct::DB_TBL_CHARGES_PREFIX . 'op_id');
        $qryOtherCharges = $ocSrch->getQuery();

        $childOrders = array();
        if ($orderType == Orders::ORDER_PRODUCT) {
            $srch = self::searchOrderProducts($criterias, $langId);
            $srch->joinTable('(' . $qryOtherCharges . ')', 'LEFT OUTER JOIN', 'op.op_id = opcc.' . OrderProduct::DB_TBL_CHARGES_PREFIX . 'op_id', 'opcc');
            $srch->joinTable(OrderProduct::DB_TBL_OP_TO_SHIPPING_USERS, 'LEFT OUTER JOIN', 'optosu.optsu_op_id = op.op_id', 'optosu');
            $srch->joinTable(Orders::DB_TBL_ORDER_PRODUCTS_SHIPPING, 'LEFT OUTER JOIN', 'ops.opshipping_op_id = op.op_id', 'ops');
            $srch->joinTable(OrderProduct::DB_TBL_SETTINGS, 'LEFT OUTER JOIN', 'op.op_id = opst.opsetting_op_id', 'opst');
            $srch->joinTable(OrderProductSpecifics::DB_TBL, 'LEFT JOIN', 'opspec.ops_op_id = op.op_id', 'opspec');

            if (true === $joinSellerProducts) {
                $srch->joinTable(SellerProduct::DB_TBL, 'LEFT OUTER JOIN', 'sp.selprod_id = op.op_selprod_id and op.op_is_batch = 0', 'sp');
                if ($langId) {
                    $srch->joinTable(SellerProduct::DB_TBL_LANG, 'LEFT OUTER JOIN', 'sp_l.selprodlang_selprod_id = sp.selprod_id AND sp_l.selprodlang_lang_id = ' . $langId, 'sp_l');
                }
            }
            $srch->addOrder("op_id", "desc");
            $rs = $srch->getResultSet();

            $oObj = new Orders();
            while ($row = FatApp::getDb()->fetch($rs)) {
                $childOrders[$row['op_id']] = $row;

                $charges = $oObj->getOrderProductChargesArr($row['op_id']);
                $childOrders[$row['op_id']]['charges'] = $charges;
            };
        } elseif ($orderType == Orders::ORDER_SUBSCRIPTION) {
            $srch = OrderSubscription::searchOrderSubscription($criterias, $langId);
            $srch->joinTable('(' . $qryOtherCharges . ')', 'LEFT OUTER JOIN', 'oss.' . OrderSubscription::DB_TBL_PREFIX . 'id = opcc.' . OrderProduct::DB_TBL_CHARGES_PREFIX . 'op_id', 'opcc');

            $srch->addOrder(OrderSubscription::DB_TBL_PREFIX . "id", "desc");
            $rs = $srch->getResultSet();

            $osObj = new OrderSubscription();
            while ($row = FatApp::getDb()->fetch($rs)) {
                $childOrders[$row[OrderSubscription::DB_TBL_PREFIX . 'id']] = $row;

                $charges = $osObj->getOrderSubscriptionChargesArr($row[OrderSubscription::DB_TBL_PREFIX . 'id']);
                $childOrders[$row[OrderSubscription::DB_TBL_PREFIX . 'id']]['charges'] = $charges;
            }
        }

        return $childOrders;
    }

    public function getOrderCommentsSrchObj($langId, $criteria = array())
    {
        if (count($criteria) == 0) {
            return array();
        }

        $langId = FatUtility::int($langId);

        $srch = new SearchBase(Orders::DB_TBL_ORDER_STATUS_HISTORY, 'tosh');
        $srch->joinTable(Orders::DB_TBL_ORDERS_STATUS, 'LEFT OUTER JOIN', 'tosh.oshistory_orderstatus_id = tos.orderstatus_id', 'tos');
        $srch->addCondition('orderstatus_type', '=', Orders::ORDER_PRODUCT);
        if ($langId > 0) {
            $srch->joinTable(Orders::DB_TBL_ORDERS_STATUS_LANG, 'LEFT OUTER JOIN', 'tos_l.orderstatuslang_orderstatus_id = tos.orderstatus_id and tos_l.orderstatuslang_lang_id = ' . $langId, 'tos_l');
            $srch->addMultipleFields(array('IFNULL(orderstatus_name,orderstatus_identifier) as orderstatus_name'));
        }

        $srch->joinTable(Orders::DB_TBL_ORDER_PRODUCTS, 'LEFT OUTER JOIN', 'torp.op_id = tosh.oshistory_op_id', 'torp');

        $ocSrch = new SearchBase(OrderProduct::DB_TBL_CHARGES, 'opc');
        $ocSrch->doNotCalculateRecords();
        $ocSrch->doNotLimitRecords();
        $ocSrch->addMultipleFields(array(OrderProduct::DB_TBL_CHARGES_PREFIX . 'op_id', 'sum(' . OrderProduct::DB_TBL_CHARGES_PREFIX . 'amount) as op_other_charges'));
        $ocSrch->addGroupBy('opc.' . OrderProduct::DB_TBL_CHARGES_PREFIX . 'op_id');
        $qryOtherCharges = $ocSrch->getQuery();

        $srch->joinTable('(' . $qryOtherCharges . ')', 'LEFT OUTER JOIN', 'torp.op_id = opcc.' . OrderProduct::DB_TBL_CHARGES_PREFIX . 'op_id', 'opcc');
        if ($langId > 0) {
            $srch->joinTable(Orders::DB_TBL_ORDER_PRODUCTS_LANG, 'LEFT OUTER JOIN', 'torp_l.oplang_op_id = torp.op_id and torp_l.oplang_lang_id = ' . $langId, 'torp_l');
            $srch->addMultipleFields(array('op_selprod_title', 'op_product_name', 'op_selprod_options', 'op_brand_name', 'op_shop_name', 'op_other_charges', 'op_shipping_duration_name', 'op_product_tax_options'));
        }

        $srch->joinTable(Orders::DB_TBL, 'LEFT OUTER JOIN', 'torp.op_order_id = tor.order_id', 'tor');
        if ($langId > 0) {
            $srch->joinTable(Orders::DB_TBL_LANG, 'LEFT OUTER JOIN', 'tor_l.orderlang_order_id = tor.order_id and tor_l.orderlang_lang_id = ' . $langId, 'tor_l');
            $srch->addMultipleFields(array('order_shippingapi_name'));
        }

        $srch->joinTable(OrderProduct::DB_TBL_OP_TO_SHIPPING_USERS, 'LEFT OUTER JOIN', 'optosu.optsu_op_id = torp.op_id', 'optosu');
        $srch->joinTable(Orders::DB_TBL_ORDER_PRODUCTS_SHIPPING, 'LEFT OUTER JOIN', 'ops.opshipping_op_id = torp.op_id', 'ops');
        $srch->joinTable(Orders::DB_TBL_ORDER_PRODUCTS_SHIPPING_LANG, 'LEFT OUTER JOIN', 'ops.opshipping_op_id = ops_l.opshippinglang_op_id and ops_l.opshippinglang_lang_id = ' . $langId, 'ops_l');
        // $srch->joinTable(ShippingCompanies::DB_TBL, 'LEFT OUTER JOIN', 'ops.opshipping_company_id = opsc.scompany_id', 'opsc');
        //$srch->joinTable(ShippingCompanies::DB_TBL_LANG, 'LEFT OUTER JOIN', 'opscl.scompanylang_scompany_id = opsc.scompany_id', 'opscl');
        $srch->addMultipleFields(array('opshipping_by_seller_user_id', 'IFNULL(opshipping_carrier_code, opshipping_label) as scompany_name'));

        if (isset($criteria['seller_id'])) {
            $srch->joinTable('tbl_users', 'LEFT OUTER JOIN', 's.user_id = torp.op_selprod_user_id', 's');
            $srch->joinTable('tbl_user_credentials', 'LEFT OUTER JOIN', 'sc.credential_user_id = s.user_id', 'sc');
            $srch->addMultipleFields(array('s.user_name as seller_name', 'sc.credential_email as seller_email', 's.user_phone_dcode as seller_phone_dcode', 's.user_phone as seller_phone',));
        }

        if (isset($criteria['buyer_id'])) {
            $srch->joinTable('tbl_users', 'LEFT OUTER JOIN', 'b.user_id = tor.order_user_id', 'b');
            $srch->joinTable('tbl_user_credentials', 'LEFT OUTER JOIN', 'bc.credential_user_id = b.user_id', 'bc');
            $srch->addMultipleFields(array('b.user_name as buyer_name', 'b.user_phone_dcode as buyer_phone_dcode', 'b.user_phone as buyer_phone', 'bc.credential_email as buyer_email'));
        }

        $srch->addMultipleFields(array('tosh.*', 'tor.order_payment_status', 'order_language_id', 'torp.*', 'torp.op_id', 'opshipping_label', 'opshipping_plugin_id'));

        foreach ($criteria as $key => $val) {
            if (strval($val) == '') {
                continue;
            }
            switch ($key) {
                case 'id':
                    $srch->addCondition('tosh.oshistory_id', '=', intval($val));
                    break;
                case 'order_id':
                    $srch->addCondition('tosh.oshistory_order_id', '=', $val);
                    break;
                case 'op_id':
                    $srch->addCondition('tosh.oshistory_op_id', '=', intval($val));
                    break;
                case 'seller_id':
                    $srch->addCondition('torp.op_selprod_user_id', '=', intval($val));
                    break;
                case 'buyer_id':
                    $srch->addCondition('tor.order_user_id', '=', intval($val));
                    break;
            }
        }

        $srch->addOrder('oshistory_date_added', 'DESC');
        $srch->addOrder('oshistory_orderstatus_id');
        $srch->addGroupBy('oshistory_id');
        return $srch;
    }

    public function getOrderComments($langId, $criteria = array(), $pagesize = 0)
    {
        $srch = $this->getOrderCommentsSrchObj($langId, $criteria);

        if (intval($pagesize) > 0) {
            $srch->setPageSize($pagesize);
        } else {
            $srch->doNotLimitRecords();
        }
        $srch->doNotCalculateRecords(true);

        $rs = $srch->getResultSet();
        return ($pagesize == 1) ? FatApp::getDb()->fetch($rs) : FatApp::getDb()->fetchAll($rs);
    }

    public function addOrderPaymentHistory($orderId, $orderPaymentStatus, $comment = '', $notify = false)
    {
        $orderInfo = $this->getOrderById($orderId);
        $orderPaymentStatus = FatUtility::int($orderPaymentStatus);
        if ($orderInfo) {
            if (!FatApp::getDb()->updateFromArray(
                Orders::DB_TBL,
                array('order_payment_status' => $orderPaymentStatus, 'order_date_updated' => date('Y-m-d H:i:s')),
                array('smt' => 'order_id = ? ', 'vals' => array($orderId))
            )) {
                $this->error = FatApp::getDb()->getError();
                return false;
            }
        }

        if (!FatApp::getDb()->insertFromArray(Orders::DB_TBL_ORDER_STATUS_HISTORY, array('oshistory_order_id' => $orderId, 'oshistory_order_payment_status' => $orderPaymentStatus, 'oshistory_date_added' => date('Y-m-d H:i:s'), 'oshistory_customer_notified' => FatUtility::int($notify), 'oshistory_comments' => $comment))) {
            $this->error = FatApp::getDb()->getError();
            return false;
        }

        if ($orderInfo['order_type'] == ORDERS::ORDER_PRODUCT) {
            $this->addProductOrderPayment($orderId, $orderInfo, $orderPaymentStatus, $comment, $notify);
        } elseif ($orderInfo['order_type'] == ORDERS::ORDER_SUBSCRIPTION) {
            if (Orders::ORDER_PAYMENT_PAID == $orderPaymentStatus) {
                $assignValues = ['user_has_valid_subscription' => applicationConstants::YES];
                FatApp::getDb()->updateFromArray(User::DB_TBL, $assignValues, array('smt' => 'user_id = ? ', 'vals' => array((int) $orderInfo['order_user_id'])));
                $assignValues = ['shop_has_valid_subscription' => applicationConstants::YES];
                FatApp::getDb()->updateFromArray(Shop::DB_TBL, $assignValues, array('smt' => 'shop_user_id = ? ', 'vals' => array((int) $orderInfo['order_user_id'])));
            }
            $this->addSubscriptionOrderPayment($orderId, $orderInfo, $orderPaymentStatus, $comment, $notify);
        }

        return true;
    }
    public function addSubscriptionOrderPayment($orderId, $orderInfo, $orderPaymentStatus, $comment = '', $notify = false)
    {
        $emailObj = new EmailHandler();


        // If order Payment status is 0 then becomes greater than 0 mail to Vendors and Update Child Order Status to Paid & Give Referral Reward Points

        if (!$orderInfo['order_payment_status'] && ($orderPaymentStatus > 0)) {
            $subOrders = $this->getChildOrders(array("order" => $orderId), $orderInfo['order_type']);

            $orderInfo = $this->getOrderById($orderId);

            foreach ($subOrders as $subkey => $subval) {
                $this->addChildSubscriptionOrderHistory($orderId, $subval[OrderSubscription::DB_TBL_PREFIX . "id"], $orderInfo[Orders::DB_TBL_PREFIX . 'language_id'], FatApp::getConfig("CONF_DEFAULT_SUBSCRIPTION_PAID_ORDER_STATUS", FatUtility::VAR_INT, 11), '', true);



                // If order Payment status is 0 then becomes greater than 0 send main html email
                if ($orderPaymentStatus && !$orderInfo['order_renew']) {
                    //Add Commission settings for the user

                    $commissionId = 0;
                    $record = new Commission($commissionId);


                    $dataToSave = array(
                        'commsetting_product_id' => 0,
                        'commsetting_prodcat_id' => 0,
                        'commsetting_user_id' => $orderInfo["order_user_id"],
                        'commsetting_fees' => $subval[OrderSubscription::DB_TBL_PREFIX . "commission"],
                        'commsetting_by_package' => applicationConstants::YES,
                    );
                    if (!$record->addUpdateData($dataToSave)) {
                        $this->error = $record->getError();
                        return false;
                    }
                    $insertId = $record->getMainTableRecordId();
                    if (!$insertId) {
                        $insertId = FatApp::getDb()->getInsertId();
                    }
                    /* if(!$insertId){
                    $insertId = Commission::getComissionSettingIdByUser($orderInfo["order_user_id"]);
                    } */
                    if ($insertId) {
                        if (!$record->addCommissionHistory($insertId)) {
                            $this->error = $record->getError();
                            return false;
                        }
                    }


                    $emailObj->orderPurchasedSubscriptionEmail($orderId);
                } elseif ($orderPaymentStatus && $orderInfo['order_renew']) {
                    $emailObj->orderRenewSubscriptionEmail($orderId);
                }

                /* Use Reward Point [ */
                if ($orderInfo['order_reward_point_used'] > 0) {
                    UserRewards::debit($orderInfo['order_user_id'], $orderInfo['order_reward_point_used'], $orderId, $orderInfo['order_language_id']);
                }
                /*]*/
            }
        }
    }

    public function addChildSubscriptionOrderHistory($orderId, $ossubs_id, $langId, $opStatusId, $comment = '', $notify = false)
    {
        $ossubs_id = FatUtility::int($ossubs_id);
        $langId = FatUtility::int($langId);
        $opStatusId = FatUtility::int($opStatusId);
        $orderSubObj = new OrderSubscription();

        $childOrderInfo = $orderSubObj->getOrderSubscriptionByOssubId($ossubs_id, $langId);
        if (empty($childOrderInfo)) {
            $this->error = Labels::getLabel("ERR_INVALID_ACCESS", $langId);
            return false;
        }

        $currentPlanData = OrderSubscription::getUserCurrentActivePlanDetails($langId, $childOrderInfo['order_user_id'], array(OrderSubscription::DB_TBL_PREFIX . 'id'));
        if (false != $currentPlanData) {
            $currentActiveSubscrId = $currentPlanData[OrderSubscription::DB_TBL_PREFIX . 'id'];
            if ($currentActiveSubscrId) {
                $this->cancelCurrentActivePlan($orderId, $currentActiveSubscrId, $childOrderInfo['order_user_id'], $notify);
            }
        }
        $planDetails = OrderSubscription::getAttributesById($childOrderInfo['ossubs_id']);

        $updateArr = array(
            OrderSubscription::DB_TBL_PREFIX . 'status_id' => $opStatusId,
            OrderSubscription::DB_TBL_PREFIX . 'from_date' => date("Y-m-d"),
            OrderSubscription::DB_TBL_PREFIX . 'till_date' => CommonHelper::getValidTillDate($planDetails)
        );

        $db = FatApp::getDb();
        if (!$db->updateFromArray(
            OrderSubscription::DB_TBL,
            $updateArr,
            array('smt' => OrderSubscription::DB_TBL_PREFIX . 'id = ? ', 'vals' => array($ossubs_id))
        )) {
            $this->error = $db->getError();
            return false;
        }

        if (!$db->insertFromArray(Orders::DB_TBL_ORDER_STATUS_HISTORY, array('oshistory_op_id' => $ossubs_id, 'oshistory_order_id' => $orderId, 'oshistory_orderstatus_id' => $opStatusId, 'oshistory_date_added' => date('Y-m-d H:i:s'), 'oshistory_customer_notified' => (int) $notify, 'oshistory_comments' => $comment), true)) {
            $this->error = $db->getError();
            return false;
        }
        return true;
    }

    public function cancelCurrentActivePlan($orderId, $ossubs_id, $userId, $notify = false)
    {
        $db = FatApp::getDb();
        $opStatusId = FatApp::getConfig('CONF_DEFAULT_CANCEL_SUBSCRIPTION_ORDER_STATUS');
        $updateArr = array(
            OrderSubscription::DB_TBL_PREFIX . 'status_id' => $opStatusId,

        );

        if (!$db->updateFromArray(
            OrderSubscription::DB_TBL,
            $updateArr,
            array('smt' => OrderSubscription::DB_TBL_PREFIX . 'id = ? ', 'vals' => array($ossubs_id))
        )) {
            $this->error = $db->getError();
            return false;
        }
        $orderInfo = $this->getOrderById($orderId);
        if ($orderInfo['order_renew'] < 1) {
            $emailNotificationObj = new EmailHandler();
            $emailNotificationObj->sendCancelSubscriptionNotification($userId, $ossubs_id, $orderInfo['order_language_id']);
        }

        if (!$db->insertFromArray(Orders::DB_TBL_ORDER_STATUS_HISTORY, array('oshistory_op_id' => $ossubs_id, 'oshistory_order_id' => $orderId, 'oshistory_orderstatus_id' => $opStatusId, 'oshistory_date_added' => date('Y-m-d H:i:s'), 'oshistory_customer_notified' => (int) $notify, 'oshistory_comments' => Labels::getLabel("LBL_Plan_Cancelled", CommonHelper::getLangId())), true)) {
            $this->error = $db->getError();
            return false;
        }
    }

    public function addProductOrderPayment($orderId, $orderInfo, $orderPaymentStatus, $comment = '', $notify = false)
    {
        /* CommonHelper::printArray($orderInfo); die; */
        $emailObj = new EmailHandler();

        // If order Payment status is 0 then becomes greater than 0 send main html email
        $paymentMethodCode = Plugin::getAttributesById($orderInfo['order_pmethod_id'], 'plugin_code');
        if (!$orderInfo['order_payment_status'] && $orderPaymentStatus) {
            $emailNotify = $emailObj->orderPaymentUpdateBuyerAdmin($orderId);
        } elseif (strtolower($paymentMethodCode) == 'cashondelivery' || strtolower($paymentMethodCode) == 'payatstore') {
            $emailNotify = $emailObj->cashOnDeliveryOrderUpdateBuyerAdmin($orderId);
            $emailObj->newOrderBuyerAdmin($orderId, $orderInfo['order_language_id']);
            $emailObj->newOrderVendor($orderId, 0, $paymentMethodCode);
        } elseif (strtolower($paymentMethodCode) == 'transferbank') {
            $emailNotify = $emailObj->bankTranferOrderUpdateBuyerAdmin($orderId);
            $emailObj->newOrderBuyerAdmin($orderId, $orderInfo['order_language_id']);
            $emailObj->newOrderVendor($orderId, 0, $paymentMethodCode);
        }

        $subOrders = $this->getChildOrders(array("order" => $orderId), $orderInfo['order_type'], CommonHelper::getLangId());
        if (FatApp::getConfig('CONF_ANALYTICS_ADVANCE_ECOMMERCE', FatUtility::VAR_INT, 0)) {
            $et = new EcommerceTracking(Labels::getLabel('LBL_ORDER_PLACED', CommonHelper::getLangId()), $orderInfo['order_user_id']);
            $et->addProductAction(EcommerceTracking::PROD_ACTION_TYPE_PURCHASE);
            foreach ($subOrders as $op) {
                $productTitle = ($op['op_selprod_title']) ? $op['op_selprod_title'] : $op['op_product_name'];
                $et->addProduct($op['op_selprod_id'], $productTitle, '', $op['op_brand_name'], $op['op_qty'], $op["op_unit_price"]);
            }
            $et->addTransaction($orderInfo['order_id'], $orderInfo['order_net_amount'], array_sum(array_column($subOrders, 'op_actual_shipping_charges')), $orderInfo['order_tax_charged'], $orderInfo['order_currency_code']);
            $et->sendRequest();
        }

        // If order Payment status is 0 then becomes greater than 0 mail to Vendors and Update Child Order Status to Paid & Give Referral Reward Points
        if (!$orderInfo['order_payment_status'] && ($orderPaymentStatus > 0)) {
            $emailObj->newOrderVendor($orderId);
            $emailObj->newOrderBuyerAdmin($orderId, $orderInfo['order_language_id']);

            /*$subOrders = $this->getChildOrders(array("order" => $orderId), $orderInfo['order_type']); */
            foreach ($subOrders as $subkey => $subval) {
                $this->addChildProductOrderHistory($subval["op_id"], $orderInfo['order_language_id'], FatApp::getConfig("CONF_DEFAULT_PAID_ORDER_STATUS", FatUtility::VAR_INT, 0), '', true);
                if ($subval['op_product_type'] == Product::PRODUCT_TYPE_DIGITAL) {
                    $emailObj->newDigitalOrderBuyer($orderId, $subval["op_id"], $orderInfo['order_language_id']);
                }
            }

            $isReferrerRewarded = false;
            $isReferralRewarded = false;

            $walletSelected = array_key_exists("order_is_wallet_selected", $orderInfo) ? FatUtility::int($orderInfo["order_is_wallet_selected"]) : 0;

            $paymentMethodRow = Plugin::getAttributesById($orderInfo['order_pmethod_id']);

            /* Use Reward Point [ */
            if ($orderInfo['order_reward_point_used'] > 0 && (0 < $walletSelected || (is_array($paymentMethodRow) && !empty($paymentMethodRow) && (isset($paymentMethodRow['plugin_code']) && !in_array(strtolower($paymentMethodRow['plugin_code']), ['cashondelivery', 'payatstore']))))) {
                UserRewards::debit($orderInfo['order_user_id'], $orderInfo['order_reward_point_used'], $orderId, $orderInfo['order_language_id']);
            }
            /*]*/

            /* Reward Points to Referrer[ */
            if ($orderInfo['order_referrer_user_id'] && $orderInfo['order_referrer_reward_points']) {
                $rewardExpiryDate = '0000-00-00';
                $CONF_SALE_REFERRER_REWARD_POINTS_VALIDITY = FatApp::getConfig("CONF_SALE_REFERRER_REWARD_POINTS_VALIDITY", FatUtility::VAR_INT, 0);
                if ($CONF_SALE_REFERRER_REWARD_POINTS_VALIDITY > 0) {
                    $rewardExpiryDate = date('Y-m-d', strtotime('+' . $CONF_SALE_REFERRER_REWARD_POINTS_VALIDITY . ' days'));
                }

                $rewardsRecord = new UserRewards();
                $urpComments = Labels::getLabel("LBL_PURCHASE_REWARD_POINTS:_YOUR_REFERRAL_{username}_PLACED_FIRST_ORDER.", CommonHelper::getLangId());
                $referralUserName = User::getAttributesById($orderInfo['order_user_id'], "user_name");
                $urpComments = str_replace("{username}", $referralUserName, $urpComments);

                $rewardsRecord->assignValues(
                    array(
                        'urp_user_id' => $orderInfo['order_referrer_user_id'],
                        'urp_referral_user_id' => $orderInfo['order_user_id'],
                        'urp_points' => $orderInfo['order_referrer_reward_points'],
                        'urp_comments' => $urpComments,
                        'urp_used' => 0,
                        'urp_date_expiry' => $rewardExpiryDate
                    )
                );
                if ($rewardsRecord->save()) {
                    $isReferrerRewarded = true;
                    $urpId = $rewardsRecord->getMainTableRecordId();
                    $emailObj = new EmailHandler();
                    $emailObj->sendRewardPointsNotification(CommonHelper::getLangId(), $urpId);
                }
            }
            /* ] */

            /* Reward Point to Referral[ */
            if ($orderInfo['order_referrer_user_id'] && $orderInfo['order_referral_reward_points']) {
                $rewardExpiryDate = '0000-00-00';
                $CONF_SALE_REFERRAL_REWARD_POINTS_VALIDITY = FatApp::getConfig("CONF_SALE_REFERRAL_REWARD_POINTS_VALIDITY", FatUtility::VAR_INT, 0);
                if ($CONF_SALE_REFERRAL_REWARD_POINTS_VALIDITY > 0) {
                    $rewardExpiryDate = date('Y-m-d', strtotime('+' . $CONF_SALE_REFERRAL_REWARD_POINTS_VALIDITY . ' days'));
                }

                $rewardsRecord = new UserRewards();
                $urpComments = Labels::getLabel("LBL_PURCHASE_REWARD_POINTS:_[1st_purchase]_YOU_ARE_REFERRAL_OF_{username}.", CommonHelper::getLangId());
                $referralUserName = User::getAttributesById($orderInfo['order_referrer_user_id'], "user_name");
                $urpComments = str_replace("{username}", $referralUserName, $urpComments);

                $rewardsRecord->assignValues(
                    array(
                        'urp_user_id' => $orderInfo['order_user_id'],
                        'urp_referral_user_id' => $orderInfo['order_referrer_user_id'],
                        'urp_points' => $orderInfo['order_referral_reward_points'],
                        'urp_comments' => $urpComments,
                        'urp_used' => 0,
                        'urp_date_expiry' => $rewardExpiryDate
                    )
                );
                if ($rewardsRecord->save()) {
                    $isReferralRewarded = true;
                    $urpId = $rewardsRecord->getMainTableRecordId();
                    $emailObj = new EmailHandler();
                    $emailObj->sendRewardPointsNotification(CommonHelper::getLangId(), $urpId);
                }
            }
            /* ] */

            /* remove referrer checkout cookie, becoz, Referrer and Referral are rewarded[ */
            if ($isReferrerRewarded || $isReferralRewarded) {
                setcookie('referrer_code_checkout', '', time() - 3600, CONF_WEBROOT_URL, '', false, true);
            }
            /* ] */
        }

        // If order Payment status is 0 then becomes less than 0 send mail to Vendors and Update Child Order Status to Cancelled
        if (!$orderInfo['order_payment_status'] && ($orderPaymentStatus < 0)) {
            $subOrders = $this->getChildOrders(array("order" => $orderId), $orderInfo['order_type']);
            foreach ($subOrders as $subkey => $subval) {
                $this->addChildProductOrderHistory($subval["op_id"], $orderInfo['order_language_id'], FatApp::getConfig("CONF_DEFAULT_CANCEL_ORDER_STATUS", FatUtility::VAR_INT, 0), '', true);
            }
        }
    }

    public function addChildProductOrderHistory($op_id, $langId, $opStatusId, $comment = '', $notify = false, $trackingNumber = '', $releasePayments = 0, $moveRefundToWallet = true, $trackingCourier = '', $trackingUrl = '')
    {
        $op_id = FatUtility::int($op_id);
        $langId = FatUtility::int($langId);
        $opStatusId = FatUtility::int($opStatusId);

        $this->langId = $langId;

        $childOrderInfo = $this->getOrderProductsByOpId($op_id, $langId);
        if (empty($childOrderInfo)) {
            $this->error = Labels::getLabel("ERR_INVALID_ACCESS", $langId);
            return false;
        }

        $this->orderId = $childOrderInfo['op_order_id'];
        $this->opId = $op_id;

        $db = FatApp::getDb();
        $emailNotificationObj = new EmailHandler();

        if (!$db->updateFromArray(
            Orders::DB_TBL_ORDER_PRODUCTS,
            array('op_status_id' => $opStatusId),
            array('smt' => 'op_id = ? ', 'vals' => array($op_id))
        )) {
            $this->error = $db->getError();
            return false;
        }

        if (!$db->insertFromArray(Orders::DB_TBL_ORDER_STATUS_HISTORY, array('oshistory_op_id' => $op_id, 'oshistory_orderstatus_id' => $opStatusId, 'oshistory_date_added' => date('Y-m-d H:i:s'), 'oshistory_customer_notified' => (int) $notify, 'oshistory_comments' => $comment, 'oshistory_tracking_number' => $trackingNumber, 'oshistory_courier' => $trackingCourier, 'oshistory_tracking_url' => $trackingUrl), true)) {
            $this->error = $db->getError();
            return false;
        }
        $commentId = $db->getInsertId();

        // If order status is in buyer order statuses then send update email
        if (in_array($opStatusId, unserialize(FatApp::getConfig("CONF_BUYER_ORDER_STATUS"))) && $notify) {
            $emailNotificationObj->orderStatusUpdateBuyer($commentId, $childOrderInfo['order_language_id'], $childOrderInfo['order_user_id']);
        }

        // If current order status is not paid up but new status is paid then commence updating the product's weightage
        if (!in_array($childOrderInfo['op_status_id'], (array) FatApp::getConfig("CONF_DEFAULT_PAID_ORDER_STATUS", FatUtility::VAR_INT, 0)) && in_array($opStatusId, (array) FatApp::getConfig("CONF_DEFAULT_PAID_ORDER_STATUS", FatUtility::VAR_INT, 0)) && (isset($childOrderInfo['plugin_code']) && (isset($childOrderInfo['plugin_code']) && in_array(strtolower($childOrderInfo['plugin_code']), ['cashondelivery', 'payatstore'])))) {
            if ($childOrderInfo['op_is_batch']) {
                $opSelprodCodeArr = explode('|', $childOrderInfo['op_selprod_code']);
            } else {
                $opSelprodCodeArr = array($childOrderInfo['op_selprod_code']);
            }

            foreach ($opSelprodCodeArr as $opSelprodCode) {
                if (empty($opSelprodCode)) {
                    continue;
                }
                Product::recordProductWeightage($opSelprodCode, SmartWeightageSettings::PRODUCT_ORDER_PAID);
            }

            if (CommonHelper::canAvailShippingChargesBySeller($childOrderInfo['op_selprod_user_id'], $childOrderInfo['opshipping_by_seller_user_id'])) {
                $shippingUserdata = array('optsu_op_id' => $childOrderInfo['op_id'], 'optsu_user_id' => $childOrderInfo['op_selprod_user_id']);
                $db->insertFromArray(OrderProduct::DB_TBL_OP_TO_SHIPPING_USERS, $shippingUserdata, false, array('IGNORE'));
            }
        }

        /* If current order status is not processing or complete but new status is processing or complete then commence completing the order [ */
        $arr = array_merge(
            unserialize(FatApp::getConfig("CONF_PROCESSING_ORDER_STATUS")),
            unserialize(FatApp::getConfig("CONF_COMPLETED_ORDER_STATUS"))
        );

        if (!in_array($childOrderInfo['op_status_id'], $arr) && in_array($opStatusId, array_diff($arr, array(FatApp::getConfig("CONF_RETURN_REQUEST_APPROVED_ORDER_STATUS", FatUtility::VAR_INT, 0))))) {
            $selProdIdArr = array();
            if ($childOrderInfo['op_is_batch']) {
                $selProdIdArr = explode('|', $childOrderInfo['op_batch_selprod_id']);
            } else {
                $selProdIdArr = array($childOrderInfo['op_selprod_id']);
            }

            foreach ($selProdIdArr as $opSelprodId) {
                if (empty($opSelprodId)) {
                    continue;
                }

                /* Not required to subtract stock for RFQ. */
                if ((isset($_SESSION['offer_checkout']['selprod_id']) && $_SESSION['offer_checkout']['selprod_id'] == $opSelprodId) || 0 < $childOrderInfo['op_offer_id']) {
                    continue;
                }

                /* Stock subtraction */
                $db->query("UPDATE tbl_seller_products SET selprod_stock = (selprod_stock - " . (int) $childOrderInfo['op_qty'] . "),selprod_sold_count = (selprod_sold_count + " . (int) $childOrderInfo['op_qty'] . ") WHERE selprod_id = '" . (int) $opSelprodId . "' AND selprod_subtract_stock = '1'");

                $sellProdInfo = SellerProduct::getAttributesById($opSelprodId, array('selprod_stock', 'selprod_subtract_stock', 'selprod_track_inventory', 'selprod_threshold_stock_level'));
                if ($sellProdInfo && ($sellProdInfo["selprod_threshold_stock_level"] >= $sellProdInfo["selprod_stock"]) && ($sellProdInfo["selprod_track_inventory"] == 1)) {
                    $emailNotificationObj->sendProductStockAlert($opSelprodId);
                }
            }

            /* if ( FatApp::getConfig("CONF_ALLOW_REVIEWS") ){
            $emailNotificationObj->sendBuyerReviewNotification( $childOrderInfo['op_id'] , $childOrderInfo['order_language_id'] );
            } */
        }
        /* ] */

        /* If old order status is the processing or complete status but new status is not then commence restock, and remove coupon, voucher and reward history [ */
        if (in_array($childOrderInfo['op_status_id'], array_merge($arr, array(FatApp::getConfig("CONF_RETURN_REQUEST_ORDER_STATUS", FatUtility::VAR_INT, 0)))) && in_array($opStatusId, array(FatApp::getConfig("CONF_RETURN_REQUEST_APPROVED_ORDER_STATUS", FatUtility::VAR_INT, 0), FatApp::getConfig("CONF_DEFAULT_CANCEL_ORDER_STATUS", FatUtility::VAR_INT, 0)))) {
            // ReStock subtraction can work manually
            /* foreach($selProdIdArr as $opSelprodId){
            if(empty($opSelprodId)) { continue; }
            $db->query("UPDATE tbl_seller_products SET selprod_stock = (selprod_stock + " . (int)$childOrderInfo['op_qty'] . "),selprod_sold_count = (selprod_sold_count - " . (int)$childOrderInfo['op_qty'] . ") WHERE selprod_id = '" . (int)$opSelprodId . "' AND selprod_subtract_stock = '1'");
            }*/
        }
        /* ] */

        /* If current order status is not cancelled but new status is cancelled then commence cancelling the order [ */
        if (($childOrderInfo['op_status_id'] != FatApp::getConfig("CONF_DEFAULT_CANCEL_ORDER_STATUS", FatUtility::VAR_INT, 0)) && ($opStatusId == FatApp::getConfig("CONF_DEFAULT_CANCEL_ORDER_STATUS", FatUtility::VAR_INT, 0)) && ($childOrderInfo["order_payment_status"] == Orders::ORDER_PAYMENT_PAID)) {
            if ($moveRefundToWallet) {
                /* CommonHelper::printArray($childOrderInfo); die; */
                $formattedRequestValue = "#" . $childOrderInfo["op_invoice_number"];
                $retReqObj = new OrderCancelRequest();
                $cancelRequestDetail = $retReqObj->getCancelRequestById($childOrderInfo['op_id']);
                if (!empty($cancelRequestDetail)) {
                    $comments = sprintf(Labels::getLabel('LBL_ORDER_%S_CANCEL_REQUEST_APPROVED', $langId), $formattedRequestValue);
                } else {
                    $comments = sprintf(Labels::getLabel('LBL_ORDER_%S_HAS_BEEN_CANCELLED', $langId), $formattedRequestValue);
                }

                $txnAmount = (($childOrderInfo["op_unit_price"] * $childOrderInfo["op_qty"]) + $childOrderInfo["op_other_charges"] + $childOrderInfo["op_rounding_off"]);

                /*Refund to Buyer[*/
                if ($txnAmount > 0) {
                    $txnDataArr = array(
                        'utxn_user_id' => $childOrderInfo['order_user_id'],
                        'utxn_credit' => $txnAmount,
                        'utxn_status' => Transactions::STATUS_COMPLETED,
                        'utxn_op_id' => $childOrderInfo['op_id'],
                        'utxn_comments' => $comments,
                        'utxn_type' => Transactions::TYPE_ORDER_REFUND
                    );

                    $transObj = new Transactions();
                    if ($txnId = $transObj->addTransaction($txnDataArr)) {
                        $emailNotificationObj->sendTxnNotification($txnId, $langId);
                    }
                }
                /*]*/
                /*
                /*Deduct Shipping Amount[                
                if (0 < $childOrderInfo["op_free_ship_upto"] && array_key_exists(OrderProduct::CHARGE_TYPE_SHIPPING, $childOrderInfo['charges']) && $childOrderInfo["op_actual_shipping_charges"] != $childOrderInfo['charges'][OrderProduct::CHARGE_TYPE_SHIPPING]['opcharge_amount']) {
                    $sellerProdTotalPrice = 0;
                    $rows = Orderproduct::getOpArrByOrderId($childOrderInfo["op_order_id"]);
                    foreach ($rows as $row) {
                        if ($row['op_selprod_user_id'] != $childOrderInfo['op_selprod_user_id']) {
                            continue;
                        }
                        if ($row['op_refund_qty'] == $row['op_qty']) {
                            continue;
                        }
                        $qty = $row['op_qty'] - $row['op_refund_qty'];
                        $sellerProdTotalPrice += $row['op_unit_price'] * $qty;
                    }
                    $actualShipCharges = 0;
                    //$sellerPriceIfItemWillRefund = $sellerProdTotalPrice - ($childOrderInfo["op_unit_price"] * $childOrderInfo["op_qty"]);
                    if ($childOrderInfo["op_free_ship_upto"] > $sellerProdTotalPrice) {
                        if (!FatApp::getConfig('CONF_RETURN_SHIPPING_CHARGES_TO_CUSTOMER', FatUtility::VAR_INT, 0)) {
                            $actualShipCharges = $childOrderInfo['op_actual_shipping_charges'];
                        }
                    }
                    //$actualShipCharges = min($txnAmount,$actualShipCharges);

                    if (0 < $actualShipCharges) {
                        $comments = str_replace('{invoice}', $formattedRequestValue, Labels::getLabel('LBL_Deducted_Shipping_Charges_{invoice}', $langId));
                        $txnDataArr = array(
                            'utxn_user_id' => $childOrderInfo['order_user_id'],
                            'utxn_comments' => $comments,
                            'utxn_status' => Transactions::STATUS_COMPLETED,
                            'utxn_debit' => $actualShipCharges,
                            'utxn_op_id' => $childOrderInfo['op_id'],
                            'utxn_type' => Transactions::TYPE_ORDER_SHIPPING,
                        );
                        $transObj = new Transactions();
                        if ($txnId = $transObj->addTransaction($txnDataArr)) {
                            $emailNotificationObj->sendTxnNotification($txnId, $langId);
                        }

                        $comments = str_replace('{invoice}', $formattedRequestValue, Labels::getLabel('LBL_Credited_Shipping_Charges_{invoice}', $langId));
                        $txnDataArr = array(
                            'utxn_user_id' => $childOrderInfo['op_selprod_user_id'],
                            'utxn_comments' => $comments,
                            'utxn_status' => Transactions::STATUS_COMPLETED,
                            'utxn_credit' => $actualShipCharges,
                            'utxn_op_id' => $childOrderInfo['op_id'],
                            'utxn_type' => Transactions::TYPE_ORDER_SHIPPING,
                        );
                        $transObj = new Transactions();
                        if ($txnId = $transObj->addTransaction($txnDataArr)) {
                            $emailNotificationObj->sendTxnNotification($txnId, $langId);
                        }
                    }
                }
                */
                $opRefundArr = array(
                    'op_refund_qty' => $childOrderInfo["op_qty"],
                    'op_refund_amount' => $txnAmount,
                    'op_refund_commission' => $childOrderInfo["op_commission_charged"],
                    'op_refund_shipping' => $childOrderInfo['charges'][OrderProduct::CHARGE_TYPE_SHIPPING][OrderProduct::DB_TBL_CHARGES_PREFIX . 'amount'] ?? 0,
                    'op_refund_affiliate_commission' => $childOrderInfo["op_affiliate_commission_charged"],
                    'op_refund_tax' => $childOrderInfo['charges'][OrderProduct::CHARGE_TYPE_TAX][OrderProduct::DB_TBL_CHARGES_PREFIX . 'amount'] ?? 0,
                );
                if (!$db->updateFromArray(
                    Orders::DB_TBL_ORDER_PRODUCTS,
                    $opRefundArr,
                    array('smt' => 'op_id = ? ', 'vals' => array($op_id))
                )) {
                    $this->error = $db->getError();
                    return false;
                }
                /* ]*/
            }
        }
        /* ] */

        /* If current order status is not return request approved but new status is return request approved then commence the order operation [ */
        if (!in_array($childOrderInfo['op_status_id'], (array) FatApp::getConfig("CONF_RETURN_REQUEST_APPROVED_ORDER_STATUS", FatUtility::VAR_INT, 0)) && in_array($opStatusId, (array) FatApp::getConfig("CONF_RETURN_REQUEST_APPROVED_ORDER_STATUS", FatUtility::VAR_INT, 0)) && ($childOrderInfo["order_payment_status"] == Orders::ORDER_PAYMENT_PAID || (isset($childOrderInfo['plugin_code']) && (isset($childOrderInfo['plugin_code']) && in_array(strtolower($childOrderInfo['plugin_code']), ['cashondelivery', 'payatstore']))))) {
            if ($moveRefundToWallet) {
                $formattedRequestValue = "#" . $childOrderInfo["op_invoice_number"];
                $comments = sprintf(Labels::getLabel('LBL_RETURN_REQUEST_APPROVED', $langId), $formattedRequestValue);
                $txnAmount = $childOrderInfo['op_refund_amount'];
                /*Refund to Buyer[*/
                if ($txnAmount > 0) {
                    $txnArray["utxn_user_id"] = $childOrderInfo['order_user_id'];
                    $txnArray["utxn_credit"] = $txnAmount;
                    $txnArray["utxn_status"] = Transactions::STATUS_COMPLETED;
                    $txnArray["utxn_op_id"] = $childOrderInfo['op_id'];
                    $txnArray["utxn_comments"] = $comments;
                    $txnArray["utxn_type"] = Transactions::TYPE_ORDER_REFUND;
                    $transObj = new Transactions();
                    if ($txnId = $transObj->addTransaction($txnArray)) {
                        $emailNotificationObj->sendTxnNotification($txnId, $langId);
                    }
                }
                /* ] */
                /*
                /*Deduct Shipping Amount
                if (0 < $childOrderInfo["op_free_ship_upto"] && array_key_exists(OrderProduct::CHARGE_TYPE_SHIPPING, $childOrderInfo['charges']) && $childOrderInfo["op_actual_shipping_charges"] != $childOrderInfo['charges'][OrderProduct::CHARGE_TYPE_SHIPPING]['opcharge_amount']) {
                    $actualShipCharges = 0;
                    $sellerProdTotalPrice = 0;
                    $rows = Orderproduct::getOpArrByOrderId($childOrderInfo["op_order_id"]);
                    foreach ($rows as $row) {
                        if ($row['op_selprod_user_id'] != $childOrderInfo['op_selprod_user_id']) {
                            continue;
                        }
                        if ($row['op_refund_qty'] == $row['op_qty']) {
                            continue;
                        }
                        $qty = $row['op_qty'] - $row['op_refund_qty'];
                        $sellerProdTotalPrice += $row['op_unit_price'] * $qty;
                    }

                    //$sellerPriceIfItemWillRefund = $sellerProdTotalPrice - ($childOrderInfo["op_unit_price"] * $childOrderInfo["op_refund_qty"]);
                    if ($childOrderInfo["op_free_ship_upto"] > $sellerProdTotalPrice) {
                        $unitShipCharges = round($childOrderInfo['op_actual_shipping_charges'] / $childOrderInfo["op_qty"], 2);
                        $returnShipChargesToCust = 0;
                        if (FatApp::getConfig('CONF_RETURN_SHIPPING_CHARGES_TO_CUSTOMER', FatUtility::VAR_INT, 0)) {
                            $returnShipChargesToCust = $unitShipCharges * $childOrderInfo["op_refund_qty"];
                        }

                        $actualShipCharges = $childOrderInfo['op_actual_shipping_charges'] - $returnShipChargesToCust;
                    }
                    //$actualShipCharges = min($txnAmount,$actualShipCharges);
                    if (0 < $actualShipCharges) {
                        $comments = str_replace('{invoice}', $formattedRequestValue, Labels::getLabel('LBL_Deducted_Shipping_Charges_{invoice}', $langId));
                        $txnDataArr = array(
                            'utxn_user_id' => $childOrderInfo['order_user_id'],
                            'utxn_comments' => $comments,
                            'utxn_status' => Transactions::STATUS_COMPLETED,
                            'utxn_debit' => $actualShipCharges,
                            'utxn_op_id' => $childOrderInfo['op_id'],
                            'utxn_type' => Transactions::TYPE_ORDER_SHIPPING,
                        );
                        $transObj = new Transactions();
                        if ($txnId = $transObj->addTransaction($txnDataArr)) {
                            $emailNotificationObj->sendTxnNotification($txnId, $langId);
                        }

                        $comments = str_replace('{invoice}', $formattedRequestValue, Labels::getLabel('LBL_Credited_Shipping_Charges_{invoice}', $langId));
                        $txnDataArr = array(
                            'utxn_user_id' => $childOrderInfo['op_selprod_user_id'],
                            'utxn_comments' => $comments,
                            'utxn_status' => Transactions::STATUS_COMPLETED,
                            'utxn_credit' => $actualShipCharges,
                            'utxn_op_id' => $childOrderInfo['op_id'],
                            'utxn_type' => Transactions::TYPE_ORDER_SHIPPING,
                        );
                        $transObj = new Transactions();
                        if ($txnId = $transObj->addTransaction($txnDataArr)) {
                            $emailNotificationObj->sendTxnNotification($txnId, $langId);
                        }
                    }
                }
                /* ]
                */
            }

            if (FatApp::getConfig('CONF_ANALYTICS_ADVANCE_ECOMMERCE', FatUtility::VAR_INT, 0)) {
                $et = new EcommerceTracking(Labels::getLabel('LBL_REFUND_ORDER', $langId), $childOrderInfo['order_user_id']);
                $et->addProductAction(EcommerceTracking::PROD_ACTION_TYPE_REFUND);
                $et->addProduct($childOrderInfo['op_selprod_id'], $childOrderInfo['op_refund_qty']);
                $et->addTransaction($childOrderInfo['op_order_id']);
                $et->addEvent('Ecommerce', 'Refund');
                $et->sendRequest();
            }
        }
        /* ] */

        /* If current order status is not shipped but new status is shipped then commence shipping the order [ */
        if (!in_array($childOrderInfo['op_status_id'], (array) FatApp::getConfig("CONF_DEFAULT_SHIPPING_ORDER_STATUS")) && in_array($opStatusId, (array) FatApp::getConfig("CONF_DEFAULT_SHIPPING_ORDER_STATUS")) && ($childOrderInfo["order_payment_status"] == Orders::ORDER_PAYMENT_PAID)) {
            $db->updateFromArray(
                Orders::DB_TBL_ORDER_PRODUCTS,
                array('op_shipped_date' => date('Y-m-d H:i:s')),
                array('smt' => 'op_id = ? ', 'vals' => array($op_id))
            );
        }
        /* ] */

        /* If current order status is not delivered but new Status is delivered and the order is of COD, then, check if delivered by seller then debiting transaction entry from seller wallet [ */
        if (!in_array($childOrderInfo['op_status_id'], (array) FatApp::getConfig("CONF_DEFAULT_DEIVERED_ORDER_STATUS")) && in_array($opStatusId, (array) FatApp::getConfig("CONF_DEFAULT_DEIVERED_ORDER_STATUS")) && (isset($childOrderInfo['plugin_code']) && (isset($childOrderInfo['plugin_code']) && in_array(strtolower($childOrderInfo['plugin_code']), ['cashondelivery', 'payatstore'])))) {
            if (CommonHelper::canAvailShippingChargesBySeller($childOrderInfo['op_selprod_user_id'], $childOrderInfo['opshipping_by_seller_user_id'])) {
                $formattedInvoiceNumber = "#" . $childOrderInfo["op_invoice_number"];
                $comments = Labels::getLabel('LBL_CASH_COLLECTED_FOR_COD_ORDER', $langId) . ' ' . $formattedInvoiceNumber;
                $amt = CommonHelper::orderProductAmount($childOrderInfo);

                $txnDataArr = array(
                    'utxn_user_id' => $childOrderInfo['op_selprod_user_id'],
                    'utxn_comments' => $comments,
                    'utxn_status' => Transactions::STATUS_COMPLETED,
                    'utxn_debit' => $amt,
                    'utxn_op_id' => $childOrderInfo['op_id'],
                    'utxn_type' => Transactions::TYPE_ORDER_SHIPPING,
                );
                $transObj = new Transactions();
                if ($txnId = $transObj->addTransaction($txnDataArr)) {
                    $emailNotificationObj->sendTxnNotification($txnId, $langId);
                }
            }

            /* add payment in tbl_order_payments in case of cod and paystore order*/
            $db->updateFromArray(
                Orders::DB_TBL_ORDER_PAYMENTS,
                array('opayment_txn_status' => Orders::ORDER_PAYMENT_PAID),
                array('smt' => 'opayment_order_id = ? and opayment_txn_status = ? and opayment_method = ?', 'vals' => array($childOrderInfo["op_order_id"], 0, $childOrderInfo['plugin_code']))
            );
            /*]*/
        }
        /*]*/

        // If COD order and shipping user not set then assign shipping company  user seller/admin shiping company.
        if (isset($childOrderInfo['plugin_code']) &&  (isset($childOrderInfo['plugin_code']) && (isset($childOrderInfo['plugin_code']) && in_array(strtolower($childOrderInfo['plugin_code']), ['cashondelivery', 'payatstore']))) && !$childOrderInfo['optsu_user_id'] && CommonHelper::canAvailShippingChargesBySeller($childOrderInfo['op_selprod_user_id'], $childOrderInfo['opshipping_by_seller_user_id'])) {
            if (CommonHelper::canAvailShippingChargesBySeller($childOrderInfo['op_selprod_user_id'], $childOrderInfo['opshipping_by_seller_user_id'])) {
                $shippingUserdata = array('optsu_op_id' => $childOrderInfo['op_id'], 'optsu_user_id' => $childOrderInfo['op_selprod_user_id']);
                $db->insertFromArray(OrderProduct::DB_TBL_OP_TO_SHIPPING_USERS, $shippingUserdata, false, array('IGNORE'));
            }
        }

        // If current order status is not completed but new status is completed then commence completing the order
        if (!in_array($childOrderInfo['op_status_id'], (array) $this->getVendorOrderPaymentCreditedStatuses()) && in_array($opStatusId, (array) $this->getVendorOrderPaymentCreditedStatuses()) && ($childOrderInfo["order_payment_status"] == Orders::ORDER_PAYMENT_PAID || (isset($childOrderInfo['plugin_code']) && (isset($childOrderInfo['plugin_code']) && in_array(strtolower($childOrderInfo['plugin_code']), ['cashondelivery', 'payatstore']))))) {
            /* If shipped by admin credit to shipping user as COD order payment deposited by them[*/
            $amt = CommonHelper::orderProductAmount($childOrderInfo);
            if (!CommonHelper::canAvailShippingChargesBySeller($childOrderInfo['op_selprod_user_id'], $childOrderInfo['opshipping_by_seller_user_id']) && (isset($childOrderInfo['plugin_code']) && (isset($childOrderInfo['plugin_code']) && in_array(strtolower($childOrderInfo['plugin_code']), ['cashondelivery', 'payatstore'])))) {
                $formattedInvoiceNumber = "#" . $childOrderInfo["op_invoice_number"];
                $comments = Labels::getLabel('LBL_CASH_DEPOSITED_FOR_COD_ORDER', $langId) . ' ' . $formattedInvoiceNumber;

                $txnDataArr = array(
                    'utxn_user_id' => $childOrderInfo['optsu_user_id'],
                    'utxn_comments' => $comments,
                    'utxn_status' => Transactions::STATUS_COMPLETED,
                    'utxn_credit' => $amt,
                    'utxn_op_id' => $childOrderInfo['op_id'],
                );
                $transObj = new Transactions();
                if ($txnId = $transObj->addTransaction($txnDataArr)) {
                    $emailNotificationObj->sendTxnNotification($txnId, $langId);
                }
            }
            /*]*/

            /* add payment in tbl_order_payments in case of cod and paystore order*/
            if (isset($childOrderInfo['plugin_code']) && in_array(strtolower($childOrderInfo['plugin_code']), ['cashondelivery', 'payatstore'])) {
                !$db->updateFromArray(
                    Orders::DB_TBL_ORDER_PAYMENTS,
                    array('opayment_txn_status' => Orders::ORDER_PAYMENT_PAID),
                    array('smt' => 'opayment_order_id = ? and opayment_txn_status = ? and opayment_method = ?', 'vals' => array($childOrderInfo["op_order_id"], 0, $childOrderInfo['plugin_code']))
                );
            }
            /*]*/

            /* Start Order Payment to Vendor [ */
            $formattedInvoiceNumber = "#" . $childOrderInfo["op_invoice_number"];
            $comments = str_replace("{invoicenumber}", $formattedInvoiceNumber, Labels::getLabel('LBL_RECEIVED_CREDITS_FOR_ORDER_{invoicenumber}', $langId));
            $availQty = $childOrderInfo['op_qty'] - $childOrderInfo['op_refund_qty'];

            $taxCharges = isset($childOrderInfo['charges'][OrderProduct::CHARGE_TYPE_TAX][OrderProduct::DB_TBL_CHARGES_PREFIX . 'amount']) ? $childOrderInfo['charges'][OrderProduct::CHARGE_TYPE_TAX][OrderProduct::DB_TBL_CHARGES_PREFIX . 'amount'] : 0;

            $volumeDiscount = isset($childOrderInfo['charges'][OrderProduct::CHARGE_TYPE_VOLUME_DISCOUNT]['opcharge_amount']) ? abs($childOrderInfo['charges'][OrderProduct::CHARGE_TYPE_VOLUME_DISCOUNT]['opcharge_amount']) : 0;

            $volumeDiscountPerQty = 0;
            $deductVolumeDiscount = 0;
            if ($volumeDiscount > 0) {
                $volumeDiscountPerQty = ($volumeDiscount / $childOrderInfo['op_qty']);
                $deductVolumeDiscount = ($volumeDiscountPerQty * $availQty);
            }

            $shipCharges = isset($childOrderInfo['charges'][OrderProduct::CHARGE_TYPE_SHIPPING][OrderProduct::DB_TBL_CHARGES_PREFIX . 'amount']) ? $childOrderInfo['charges'][OrderProduct::CHARGE_TYPE_SHIPPING][OrderProduct::DB_TBL_CHARGES_PREFIX . 'amount'] : 0;
            $unitShipCharges = round(($shipCharges / $childOrderInfo['op_qty']), 2);
            $shippedBySeller = false;
            if (CommonHelper::canAvailShippingChargesBySeller($childOrderInfo['op_selprod_user_id'], $childOrderInfo['opshipping_by_seller_user_id'])) {
                $shipCharges = $shipCharges - $childOrderInfo['op_refund_shipping'];
                $shippedBySeller = true;
            } else {
                $shipCharges = 0;
            }

            $txnAmount = ($availQty * $childOrderInfo['op_unit_price']) - $deductVolumeDiscount + $shipCharges;

            if ($childOrderInfo['op_tax_collected_by_seller']) {
                $unitTaxCharges = round((($taxCharges + $childOrderInfo['op_rounding_off']) / $childOrderInfo['op_qty']), 2);
                $txnAmount = $txnAmount + ($unitTaxCharges * $availQty);
            }

            $alreadyPaid = false;
            $orderObj = new Orders();
            $payment = current($orderObj->getOrderPayments(["order_id" => $childOrderInfo['op_order_id']]));
            if (!empty($payment['opayment_gateway_txn_id'])) {
                $orderRow = $orderObj->getOrderById($this->orderId, $this->langId);
                $paymentMethodId = $orderRow['order_pmethod_id'];
                $pluginKey = Plugin::getAttributesById($paymentMethodId, 'plugin_code');
                switch ($pluginKey) {
                    case 'StripeConnect':
                        $alreadyPaid = true;
                        break;
                }
            }

            if (false === $alreadyPaid && $txnAmount > 0) {
                $txnArray["utxn_user_id"] = $childOrderInfo['op_selprod_user_id'];
                $txnArray["utxn_credit"] = $txnAmount;
                $txnArray["utxn_debit"] = 0;
                $txnArray["utxn_status"] = Transactions::STATUS_COMPLETED;
                $txnArray["utxn_op_id"] = $childOrderInfo['op_id'];
                $txnArray["utxn_comments"] = $comments;
                $txnArray["utxn_type"] = Transactions::TYPE_PRODUCT_SALE;
                $transObj = new Transactions();
                if ($txnId = $transObj->addTransaction($txnArray)) {
                    $emailNotificationObj->sendTxnNotification($txnId, $langId);
                }


                /*deduct shipping charges from seller if seller using admin shipping API */
                $sellerShippingApiCharges = CommonHelper::orderProductAmount($childOrderInfo, 'SHIPPING_API');
                if (0 < $sellerShippingApiCharges) {
                    $txnArray["utxn_user_id"] = $childOrderInfo['op_selprod_user_id'];
                    $txnArray["utxn_credit"] = 0;
                    $txnArray["utxn_debit"] = $sellerShippingApiCharges;
                    $txnArray["utxn_status"] = Transactions::STATUS_COMPLETED;
                    $txnArray["utxn_op_id"] = $childOrderInfo['op_id'];
                    $txnArray["utxn_comments"] = commonHelper::replaceStringData(Labels::getLabel('LBL_DEDUCTED_ADMIN_SHIPPING_API_CHARGES_{invoice}', $langId), ['{invoice}' => $formattedInvoiceNumber]);
                    $txnArray["utxn_type"] = Transactions::TYPE_ADMIN_SHIPPING_API_CHARGES;
                    $transObj = new Transactions();
                    if ($txnId = $transObj->addTransaction($txnArray)) {
                        $emailNotificationObj->sendTxnNotification($txnId, $langId);
                    }
                }
            }
            /* ] */

            /* Charge Commission/fees to Vendor [*/
            $commissionFees = $childOrderInfo['op_commission_charged'] - $childOrderInfo['op_refund_commission'];

            if ($commissionFees > 0 && false === $alreadyPaid) {
                $comments = str_replace("{invoicenumber}", $formattedInvoiceNumber, Labels::getLabel('LBL_CHARGED_COMMISSION_FOR_ORDER_{invoicenumber}', $langId));
                $txnArray["utxn_user_id"] = $childOrderInfo['op_selprod_user_id'];
                $txnArray["utxn_debit"] = $commissionFees;
                $txnArray["utxn_credit"] = 0;
                $txnArray["utxn_status"] = Transactions::STATUS_COMPLETED;
                $txnArray["utxn_op_id"] = $childOrderInfo['op_id'];
                $txnArray["utxn_comments"] = $comments;
                $txnArray["utxn_type"] = Transactions::TYPE_PRODUCT_SALE_ADMIN_COMMISSION;
                $transObj = new Transactions();
                if ($txnId = $transObj->addTransaction($txnArray)) {
                    $emailNotificationObj->sendTxnNotification($txnId, $langId);
                }
            }
            /* ] */


            /* Commission to Affiliate, if sale is linked with Affiliate referral [ */
            $affiliateCommissionFees = $childOrderInfo['op_affiliate_commission_charged'] - $childOrderInfo['op_refund_affiliate_commission'];
            if ($affiliateCommissionFees > 0 && $childOrderInfo['order_affiliate_user_id'] > 0) {
                $commentString = Labels::getLabel('MSG_COMMISSION_RECEIVED_ORDER{invoicenumber}_PLACED_BY_REFERRAR_USER', $langId);
                $commentString = str_replace("{invoicenumber}", $formattedInvoiceNumber, $commentString);
                $txnArray["utxn_user_id"] = $childOrderInfo['order_affiliate_user_id'];
                $txnArray["utxn_credit"] = $affiliateCommissionFees;
                $txnArray["utxn_debit"] = 0;
                $txnArray["utxn_status"] = Transactions::STATUS_COMPLETED;
                $txnArray["utxn_op_id"] = $childOrderInfo['op_id'];
                $txnArray["utxn_comments"] = $commentString;
                $txnArray["utxn_type"] = Transactions::TYPE_AFFILIATE_REFERRAL_ORDER;
                $transObj = new Transactions();
                if ($txnId = $transObj->addTransaction($txnArray)) {
                    $emailNotificationObj->sendTxnNotification($txnId, $langId);
                }
            }
            /* ] */

            $db->updateFromArray(
                Orders::DB_TBL_ORDER_PRODUCTS,
                array('op_completion_date' => date('Y-m-d H:i:s')),
                array('smt' => 'op_id = ? ', 'vals' => array($op_id))
            );

            /* Allocation of Rewards points [ */
            /* Handeled in addOrderPaymentHistory Function. */
            /*]*/
            $taxObj = new Tax(0);
            if (false == $taxObj->createInvoice($childOrderInfo)) {
                $info = [
                    'op_invoice_number' => $childOrderInfo['op_invoice_number'],
                    'error_message' => $taxObj->getError()
                ];
                $emailNotificationObj->sendTaxApiOrderCreationFailure($info, $langId);
            }
            Cronjob::RewardsOnPurchase($childOrderInfo['op_order_id']);
            Cronjob::firstTimeBuyerDiscount($childOrderInfo['order_user_id'], $childOrderInfo['op_order_id']);
        }


        return true;
    }

    public static function getBuyerAllowedOrderCancellationStatuses($isDigitalProduct = false)
    {
        if ($isDigitalProduct) {
            $buyerAllowCancelStatuses = unserialize(FatApp::getConfig("CONF_DIGITAL_ALLOW_CANCELLATION_ORDER_STATUS"));
        } else {
            $buyerAllowCancelStatuses = unserialize(FatApp::getConfig("CONF_ALLOW_CANCELLATION_ORDER_STATUS"));
        }

        // $buyerAllowCancelStatuses = array_diff($buyerAllowCancelStatuses, (array)FatApp::getConfig("CONF_DEFAULT_ORDER_STATUS", FatUtility::VAR_INT, 0));
        $buyerAllowCancelStatuses = array_diff($buyerAllowCancelStatuses, (array) FatApp::getConfig("CONF_DEFAULT_CANCEL_ORDER_STATUS", FatUtility::VAR_INT, 0));
        $buyerAllowCancelStatuses = array_diff($buyerAllowCancelStatuses, unserialize(FatApp::getConfig("CONF_PROCESSING_ORDER_STATUS")));
        $buyerAllowCancelStatuses = array_diff($buyerAllowCancelStatuses, unserialize(FatApp::getConfig("CONF_COMPLETED_ORDER_STATUS")));
        $buyerAllowCancelStatuses = array_merge($buyerAllowCancelStatuses, (array) FatApp::getConfig("CONF_DEFAULT_INPROCESS_ORDER_STATUS", FatUtility::VAR_INT, 0));
        return $buyerAllowCancelStatuses;
    }

    public static function getBuyerAllowedOrderReturnStatuses($type = '')
    {
        if ($type == Product::PRODUCT_TYPE_SERVICE) {
            return [];
        }

        $buyerAllowReturnStatuses = unserialize(FatApp::getConfig("CONF_RETURN_EXCHANGE_READY_ORDER_STATUS"));
        /* if( $type == Product::PRODUCT_TYPE_DIGITAL){
        $buyerAllowReturnStatuses = unserialize(FatApp::getConfig("CONF_DIGITAL_RETURN_READY_ORDER_STATUS"));
        } */
        $buyerAllowReturnStatuses = array_diff($buyerAllowReturnStatuses, (array) FatApp::getConfig("CONF_DEFAULT_ORDER_STATUS", FatUtility::VAR_INT, 0));
        $buyerAllowReturnStatuses = array_diff($buyerAllowReturnStatuses, (array) FatApp::getConfig("CONF_DEFAULT_CANCEL_ORDER_STATUS", FatUtility::VAR_INT, 0));
        $buyerAllowReturnStatuses = array_diff($buyerAllowReturnStatuses, unserialize(FatApp::getConfig("CONF_COMPLETED_ORDER_STATUS")));
        return $buyerAllowReturnStatuses;
    }

    public static function getVendorOrderPaymentCreditedStatuses()
    {
        $vendorPaymentStatuses = unserialize(FatApp::getConfig("CONF_COMPLETED_ORDER_STATUS"));
        $vendorPaymentStatuses = array_diff($vendorPaymentStatuses, (array) FatApp::getConfig("CONF_DEFAULT_ORDER_STATUS", FatUtility::VAR_INT, 0));
        $vendorPaymentStatuses = array_diff($vendorPaymentStatuses, (array) FatApp::getConfig("CONF_DEFAULT_CANCEL_ORDER_STATUS", FatUtility::VAR_INT, 0));
        $vendorPaymentStatuses = array_diff($vendorPaymentStatuses, (array) FatApp::getConfig("CONF_RETURN_REQUEST_ORDER_STATUS", FatUtility::VAR_INT, 0));
        return $vendorPaymentStatuses;
    }

    public static function getBuyerAllowedDigitalDownloadStatues()
    {
        $buyerAllowDigitalDownloadStatuses = unserialize(FatApp::getConfig("CONF_ENABLE_DIGITAL_DOWNLOADS"));
        return $buyerAllowDigitalDownloadStatuses;
    }

    public function getVendorAllowedUpdateOrderStatuses($fetchForDigitalProduct = false, $fetchForCOD = false, $fetchForPayPickup = false)
    {
        $processingStatuses = array_merge(unserialize(FatApp::getConfig("CONF_PROCESSING_ORDER_STATUS")), (array) FatApp::getConfig("CONF_DEFAULT_PAID_ORDER_STATUS", FatUtility::VAR_INT, 0), (array) FatApp::getConfig("CONF_COD_ORDER_STATUS", FatUtility::VAR_INT, 0));
        $processingStatuses = array_diff($processingStatuses, (array) FatApp::getConfig("CONF_DEFAULT_ORDER_STATUS", FatUtility::VAR_INT, 0));
        $processingStatuses = array_diff($processingStatuses, (array) FatApp::getConfig("CONF_DEFAULT_CANCEL_ORDER_STATUS", FatUtility::VAR_INT, 0));
        $processingStatuses = array_diff($processingStatuses, (array) FatApp::getConfig("CONF_RETURN_REQUEST_ORDER_STATUS", FatUtility::VAR_INT, 0));
        $processingStatuses = array_diff($processingStatuses, unserialize(FatApp::getConfig("CONF_COMPLETED_ORDER_STATUS")));
        $processingStatuses = array_diff($processingStatuses, (array) FatApp::getConfig("CONF_RETURN_REQUEST_APPROVED_ORDER_STATUS", FatUtility::VAR_INT, 0));

        $digitalProdOrderStatusArr = Orders::getOrderProductStatusArr(CommonHelper::getLangId(), array(), 0, OrderStatus::FOR_DIGITAL_ONLY);
        $digitalProductOrderStatusArr = array();
        foreach ($digitalProdOrderStatusArr as $k => $v) {
            $digitalProductOrderStatusArr[] = $k;
        }

        if ($fetchForDigitalProduct) {
            $processingStatuses = array_intersect($digitalProductOrderStatusArr, $processingStatuses);
            $processingStatuses = array_merge((array) $processingStatuses, (array) FatApp::getConfig("CONF_DEFAULT_PAID_ORDER_STATUS", FatUtility::VAR_INT, 0));
        } else {
            $processingStatuses = array_diff((array) $processingStatuses, $digitalProductOrderStatusArr);
        }

        if ($fetchForCOD) {
            $processingStatuses = array_diff((array) $processingStatuses, (array) FatApp::getConfig("CONF_DEFAULT_PAID_ORDER_STATUS", FatUtility::VAR_INT, 0));
            $processingStatuses = array_merge((array) $processingStatuses, (array) FatApp::getConfig("CONF_COD_ORDER_STATUS", FatUtility::VAR_INT, 0));
            $processingStatuses = array_diff((array) $processingStatuses, (array) FatApp::getConfig("CONF_PAY_AT_STORE_ORDER_STATUS", FatUtility::VAR_INT, 0));
        }

        if ($fetchForPayPickup) {
            $processingStatuses = array_merge((array) $processingStatuses, (array) FatApp::getConfig("CONF_PAY_AT_STORE_ORDER_STATUS", FatUtility::VAR_INT, 0));
            $processingStatuses = array_diff((array) $processingStatuses, (array) FatApp::getConfig("CONF_COD_ORDER_STATUS", FatUtility::VAR_INT, 0));
            $processingStatuses = array_diff((array) $processingStatuses, (array) FatApp::getConfig("CONF_DEFAULT_SHIPPING_ORDER_STATUS", FatUtility::VAR_INT, 0));
            $processingStatuses = array_merge($processingStatuses, (array) FatApp::getConfig("CONF_DEFAULT_DEIVERED_ORDER_STATUS", FatUtility::VAR_INT, 0));
        }

        return $processingStatuses;
    }

    public function getAdminAllowedUpdateOrderStatuses($fetchForCOD = false, $productType = false, $fetchForPayPickup = false)
    {
        $processingStatuses = array_merge(unserialize(FatApp::getConfig("CONF_PROCESSING_ORDER_STATUS")), unserialize(FatApp::getConfig("CONF_COMPLETED_ORDER_STATUS")));
        $processingStatuses = array_merge((array) $processingStatuses, (array) FatApp::getConfig("CONF_DEFAULT_PAID_ORDER_STATUS", FatUtility::VAR_INT, 0));
        $processingStatuses = array_diff($processingStatuses, (array) FatApp::getConfig("CONF_DEFAULT_ORDER_STATUS", FatUtility::VAR_INT, 0));
        $processingStatuses = array_diff($processingStatuses, (array) FatApp::getConfig("CONF_DEFAULT_CANCEL_ORDER_STATUS", FatUtility::VAR_INT, 0));
        $processingStatuses = array_diff($processingStatuses, (array) FatApp::getConfig("CONF_RETURN_REQUEST_ORDER_STATUS", FatUtility::VAR_INT, 0));
        $processingStatuses = array_diff($processingStatuses, (array) FatApp::getConfig("CONF_RETURN_REQUEST_APPROVED_ORDER_STATUS", FatUtility::VAR_INT, 0));

        if ($fetchForCOD) {
            $processingStatuses = array_diff((array) $processingStatuses, (array) FatApp::getConfig("CONF_DEFAULT_PAID_ORDER_STATUS", FatUtility::VAR_INT, 0));
            $processingStatuses = array_merge((array) $processingStatuses, (array) FatApp::getConfig("CONF_COD_ORDER_STATUS", FatUtility::VAR_INT, 0));
            $processingStatuses = array_diff((array) $processingStatuses, (array) FatApp::getConfig("CONF_PAY_AT_STORE_ORDER_STATUS", FatUtility::VAR_INT, 0));
        }

        if ($fetchForPayPickup) {
            $processingStatuses = array_merge((array) $processingStatuses, (array) FatApp::getConfig("CONF_PAY_AT_STORE_ORDER_STATUS", FatUtility::VAR_INT, 0));
            $processingStatuses = array_diff((array) $processingStatuses, (array) FatApp::getConfig("CONF_COD_ORDER_STATUS", FatUtility::VAR_INT, 0));
            $processingStatuses = array_diff((array) $processingStatuses, (array) FatApp::getConfig("CONF_DEFAULT_SHIPPING_ORDER_STATUS", FatUtility::VAR_INT, 0));
        }

        switch ($productType) {
            case Product::PRODUCT_TYPE_DIGITAL:
                $processingStatuses = array_diff((array) $processingStatuses, (array) FatApp::getConfig("CONF_DEFAULT_SHIPPING_ORDER_STATUS", FatUtility::VAR_INT, 0));
                $processingStatuses = array_diff((array) $processingStatuses, (array) FatApp::getConfig("CONF_DEFAULT_DEIVERED_ORDER_STATUS", FatUtility::VAR_INT, 0));
                $processingStatuses = array_diff((array) $processingStatuses, (array) FatApp::getConfig("CONF_DEFAULT_INPROCESS_ORDER_STATUS", FatUtility::VAR_INT, 0));
                break;
            case Product::PRODUCT_TYPE_SERVICE:
                $processingStatuses = array_diff((array) $processingStatuses, (array) FatApp::getConfig("CONF_DEFAULT_SHIPPING_ORDER_STATUS", FatUtility::VAR_INT, 0));
                $processingStatuses = array_diff((array) $processingStatuses, (array) FatApp::getConfig("CONF_DEFAULT_APPROVED_ORDER_STATUS", FatUtility::VAR_INT, 0));
                break;
            case Product::PRODUCT_TYPE_PHYSICAL:
                $processingStatuses = array_diff((array) $processingStatuses, (array) FatApp::getConfig("CONF_DEFAULT_APPROVED_ORDER_STATUS", FatUtility::VAR_INT, 0));
                break;
        }

        return $processingStatuses;
    }

    public function getAdminAllowedUpdateShippingUser()
    {
        $processingStatuses = unserialize(FatApp::getConfig("CONF_PROCESSING_ORDER_STATUS"));
        $processingStatuses = array_merge((array) $processingStatuses, (array) FatApp::getConfig("CONF_DEFAULT_PAID_ORDER_STATUS", FatUtility::VAR_INT, 0));
        $processingStatuses = array_diff($processingStatuses, unserialize(FatApp::getConfig("CONF_COMPLETED_ORDER_STATUS")));
        $processingStatuses = array_diff($processingStatuses, (array) FatApp::getConfig("CONF_DEFAULT_DEIVERED_ORDER_STATUS"));
        $processingStatuses = array_diff($processingStatuses, (array) FatApp::getConfig("CONF_DEFAULT_ORDER_STATUS", FatUtility::VAR_INT, 0));
        $processingStatuses = array_diff($processingStatuses, (array) FatApp::getConfig("CONF_DEFAULT_CANCEL_ORDER_STATUS", FatUtility::VAR_INT, 0));
        $processingStatuses = array_diff($processingStatuses, (array) FatApp::getConfig("CONF_RETURN_REQUEST_ORDER_STATUS", FatUtility::VAR_INT, 0));
        $processingStatuses = array_diff($processingStatuses, (array) FatApp::getConfig("CONF_RETURN_REQUEST_APPROVED_ORDER_STATUS", FatUtility::VAR_INT, 0));
        return $processingStatuses;
    }

    public function getNotAllowedOrderCancellationStatuses()
    {
        $cancellationStatuses = array_merge(
            (array) FatApp::getConfig("CONF_DEFAULT_ORDER_STATUS", FatUtility::VAR_INT, 0),
            (array) FatApp::getConfig("CONF_DEFAULT_CANCEL_ORDER_STATUS", FatUtility::VAR_INT, 0),
            (array) FatApp::getConfig("CONF_RETURN_REQUEST_ORDER_STATUS", FatUtility::VAR_INT, 0),
            (array) FatApp::getConfig("CONF_RETURN_REQUEST_WITHDRAWN_ORDER_STATUS", FatUtility::VAR_INT, 0),
            (array) FatApp::getConfig("CONF_RETURN_REQUEST_APPROVED_ORDER_STATUS", FatUtility::VAR_INT, 0),
            (array) FatApp::getConfig("CONF_DEFAULT_SHIPPING_ORDER_STATUS", FatUtility::VAR_INT, 0),
            (array) FatApp::getConfig("CONF_DEFAULT_DEIVERED_ORDER_STATUS", FatUtility::VAR_INT, 0),
            //unserialize(FatApp::getConfig("CONF_COMPLETED_ORDER_STATUS"))
        );
        return $cancellationStatuses;
    }

    public function getOrderProductsByOpId($op_id, $langId)
    {
        $op_id = FatUtility::int($op_id);
        $langId = FatUtility::int($langId);
        $srch = new OrderProductSearch($langId, true);
        $srch->joinPaymentMethod();
        $srch->joinShippingUsers();
        $srch->joinTable(OrderProduct::DB_TBL_CHARGES, 'LEFT OUTER JOIN', 'opc.' . OrderProduct::DB_TBL_CHARGES_PREFIX . 'op_id = op.op_id', 'opc');
        $srch->joinTable(Orders::DB_TBL_ORDER_PRODUCTS_SHIPPING, 'LEFT OUTER JOIN', 'ops.opshipping_op_id = op.op_id', 'ops');

        $srch->addMultipleFields(array('op.*', 'opst.*', 'op_l.*', 'o.order_number', 'o.order_id', 'o.order_payment_status', 'o.order_date_added', 'o.order_language_id', 'o.order_user_id', 'sum(' . OrderProduct::DB_TBL_CHARGES_PREFIX . 'amount) as op_other_charges', 'o.order_affiliate_user_id', 'plugin_code', 'optsu_user_id', 'ops.opshipping_by_seller_user_id', 'o.order_pmethod_id', 'opshipping_is_seller_plugin', 'opshipping_plugin_id', 'opshipping_plugin_charges'));
        $srch->addCondition('op_id', '=', $op_id);
        $srch->doNotCalculateRecords();
        $srch->doNotLimitRecords();
        $records = array();
        $row = FatApp::getDb()->fetch($srch->getResultSet());
        $charges = $this->getOrderProductChargesArr($op_id);
        if (!empty($row)) {
            $records = $row;
            $records['charges'] = $charges;
        }
        return $records;
    }

    public function getOrderProductChargesArr($op_id, $mobileApiCall = false)
    {
        $op_id = FatUtility::int($op_id);
        $srch = new SearchBase(OrderProduct::DB_TBL_CHARGES, 'opc');
        $srch->doNotCalculateRecords();
        $srch->doNotLimitRecords();
        $srch->addMultipleFields(array(OrderProduct::DB_TBL_CHARGES_PREFIX . 'type', OrderProduct::DB_TBL_CHARGES_PREFIX . 'amount'));
        $srch->addCondition(OrderProduct::DB_TBL_CHARGES_PREFIX . 'op_id', '=', $op_id);
        $srch->addCondition(OrderProduct::DB_TBL_CHARGES_PREFIX . 'order_type', '=', Orders::ORDER_PRODUCT);
        $rs = $srch->getResultSet();

        if (true === $mobileApiCall) {
            return FatApp::getDb()->fetchAll($rs);
        } else {
            $row = FatApp::getDb()->fetchAll($rs, OrderProduct::DB_TBL_CHARGES_PREFIX . 'type');

            if (!array_key_exists(OrderProduct::CHARGE_TYPE_SHIPPING, $row)) {
                $row[OrderProduct::CHARGE_TYPE_SHIPPING] = array(
                    'opcharge_type' => OrderProduct::CHARGE_TYPE_SHIPPING,
                    'opcharge_amount' => 0,
                );
            }
            return $row;
        }
    }

    public function getOrderProductChargesByOrderId($orderId)
    {
        $srch = new SearchBase(OrderProduct::DB_TBL, 'op');
        $srch->joinTable(OrderProduct::DB_TBL_CHARGES, 'LEFT OUTER JOIN', 'opc.' . OrderProduct::DB_TBL_CHARGES_PREFIX . 'op_id = op.op_id', 'opc');
        $srch->addCondition('op_order_id', '=', $orderId);
        $srch->addMultipleFields(array('op_id', OrderProduct::DB_TBL_CHARGES_PREFIX . 'type', OrderProduct::DB_TBL_CHARGES_PREFIX . 'amount'));
        $srch->doNotCalculateRecords();
        $row = FatApp::getDb()->fetchAll($srch->getResultSet());
        $charges = array();
        if (!empty($row)) {
            foreach ($row as $val) {
                $charges[$val['op_id']][$val[OrderProduct::DB_TBL_CHARGES_PREFIX . 'type']] = array(
                    OrderProduct::DB_TBL_CHARGES_PREFIX . 'type' => $val[OrderProduct::DB_TBL_CHARGES_PREFIX . 'type'],
                    OrderProduct::DB_TBL_CHARGES_PREFIX . 'amount' => $val[OrderProduct::DB_TBL_CHARGES_PREFIX . 'amount'],
                );
            }
        }
        return $charges;
    }

    public function getOrderPaymentFinancials($orderId, $langId = 0)
    {
        $langId = FatUtility::int($langId);
        $orderInfo = $this->getOrderById($orderId, $langId);
        $userBalance = User::getUserBalance($orderInfo["order_user_id"]);
        $orderCreditsCharge = $orderInfo["order_wallet_amount_charge"] ? min($orderInfo["order_wallet_amount_charge"], $userBalance) : 0;
        $orderPaymentGatewayCharge = $orderInfo["order_net_amount"] - $orderInfo["order_wallet_amount_charge"];
        $orderPaymentSummary = array(
            "net_payable" => $orderInfo["order_net_amount"],
            "order_user_balance" => $userBalance,
            "order_credits_charge" => $orderCreditsCharge,
            "order_payment_gateway_charge" => $orderPaymentGatewayCharge,
        );
        return $orderPaymentSummary;
    }

    public function getOrderPaymentPaid($orderId)
    {
        $srch = new SearchBase(static::DB_TBL_ORDER_PAYMENTS, 'opayment');
        $srch->addMultipleFields(
            array('sum(opayment_amount) as totalPaid')
        );
        $srch->addCondition('opayment_order_id', '=', $orderId);
        $srch->addCondition('opayment_txn_status', '=', Orders::ORDER_PAYMENT_PAID);
        $srch->doNotCalculateRecords();
        $srch->doNotLimitRecords();
        $row = FatApp::getDb()->fetch($srch->getResultSet());
        return $row['totalPaid'] ?? 0;
    }

    public function getOrderPayments($criteria = array())
    {
        if (count($criteria) == 0) {
            return [];
        }

        $srch = new SearchBase(static::DB_TBL_ORDER_PAYMENTS, 'opayment');
        foreach ($criteria as $key => $val) {
            if (strval($val) == '') {
                continue;
            }
            switch ($key) {
                case 'id':
                    $srch->addCondition('opayment.opayment_id', '=', intval($val));
                    break;
                case 'order_id':
                    $srch->addCondition('opayment.opayment_order_id', '=', $val);
                    break;
            }
        }

        $srch->doNotLimitRecords();
        $srch->doNotCalculateRecords(true);
        $srch->addOrder('opayment_id');

        return FatApp::getDb()->fetchAll($srch->getResultSet(), 'opayment_id');
    }

    public static function getOrderProductDigitalDownloads($op_id, $fileId = 0)
    {
        $op_id = FatUtility::int($op_id);
        $fileId = FatUtility::int($fileId);

        $srch = new OrderProductSearch(0, true);
        $srch->joinOrderUser();
        $srch->joinDigitalDownloads();
        $srch->addDigitalDownloadCondition();
        $srch->addMultipleFields(array('op_id', 'op_invoice_number', 'order_user_id', 'op_product_type', 'order_date_added', 'op_qty', 'op_status_id', 'op_selprod_max_download_times', 'op_selprod_download_validity_in_days', 'opa.*'));
        $srch->addCondition('op_id', '=', $op_id);
        if ($fileId > 0) {
            $srch->addCondition('afile_id', '=', $fileId);
        }
        $srch->doNotCalculateRecords();
        $downloads = FatApp::getDb()->fetchAll($srch->getResultSet());

        $digitalDownloads = static::digitalDownloadFormat($downloads);

        return $digitalDownloads;
    }

    public static function digitalDownloadFormat($downloads = array())
    {
        $digitalDownloads = array();
        foreach ($downloads as $key => $row) {
            $digitalDownloads[$key] = $row;

            if (!isset($row['afile_id'])) {
                $srch = new SearchBase(AttachedFile::DB_TBL);
                $srch->doNotCalculateRecords();
                $srch->setPageSize(1);
                $srch->addCondition('afile_type', '=', 'mysql_func_' . AttachedFile::FILETYPE_ORDER_PRODUCT_DIGITAL_DOWNLOAD, 'AND', true);
                $srch->addCondition('afile_record_id', '=', $row['op_id']);
                $srch->addFld('afile_downloaded_times');
                $srch->addOrder('afile_downloaded_times');
                $result = FatApp::getDb()->fetch($srch->getResultSet());
                $row['afile_downloaded_times'] = $row['op_selprod_max_download_times'];
                if (false != $result) {
                    $row['afile_downloaded_times'] = $result['afile_downloaded_times'];
                }
            }

            $dateAvailable = '';
            if ($row['op_selprod_download_validity_in_days'] != '-1') {
                $dateAvailable = date('Y-m-d', strtotime($row['order_date_added'] . ' + ' . $row['op_selprod_download_validity_in_days'] . ' days'));
            }

            $digitalDownloads[$key]['expiry_date'] = $dateAvailable;

            $digitalDownloads[$key]['downloadable'] = true;
            if ($dateAvailable != '' && $dateAvailable < date('Y-m-d')) {
                $digitalDownloads[$key]['downloadable'] = false;
            }

            $digitalDownloads[$key]['downloadable_count'] = -1;
            if ($row['op_selprod_max_download_times'] != '-1') {
                $digitalDownloads[$key]['downloadable_count'] = $row['op_selprod_max_download_times'];
            }

            if ($row['op_selprod_max_download_times'] != '-1') {
                if ($row['afile_downloaded_times'] >= $row['op_selprod_max_download_times']) {
                    $digitalDownloads[$key]['downloadable'] = false;
                }
            }
        }
        return $digitalDownloads;
    }

    public static function getOrderProductDigitalDownloadLinks($op_id, $link_id = 0)
    {
        $op_id = FatUtility::int($op_id);
        $link_id = FatUtility::int($link_id);

        $srch = new OrderProductSearch(0, true);
        $srch->joinOrderUser();
        $srch->joinDigitalDownloadLinks();
        $srch->addDigitalDownloadCondition();
        $srch->addMultipleFields(array('op_id', 'op_invoice_number', 'order_user_id', 'op_product_type', 'order_date_added', 'op_qty', 'op_status_id', 'op_selprod_max_download_times', 'op_selprod_download_validity_in_days', 'opd.*'));
        $srch->addCondition('op_id', '=', $op_id);

        if ($link_id > 0) {
            $srch->addCondition('opddl_link_id', '=', $link_id);
        }
        $srch->doNotCalculateRecords();
        $downloads = FatApp::getDb()->fetchAll($srch->getResultSet());

        $digitalDownloads = static::digitalDownloadLinksFormat($downloads);

        return $digitalDownloads;
    }

    public static function digitalDownloadLinksFormat($downloads = array())
    {
        $digitalDownloads = array();
        foreach ($downloads as $key => $row) {
            $digitalDownloads[$key] = $row;

            if (!isset($row['opddl_link_id'])) {
                $srch = new SearchBase(OrderProductDigitalLinks::DB_TBL);
                $srch->doNotCalculateRecords();
                $srch->setPageSize(1);
                $srch->addCondition('opddl_op_id', '=', $row['op_id']);
                $srch->addFld('opddl_downloaded_times');
                $srch->addOrder('opddl_downloaded_times');
                $result = FatApp::getDb()->fetch($srch->getResultSet());
                $row['opddl_downloaded_times'] = $row['op_selprod_max_download_times'];
                if (false != $result) {
                    $row['opddl_downloaded_times'] = $result['opddl_downloaded_times'];
                }
            }

            $dateAvailable = '';
            if ($row['op_selprod_download_validity_in_days'] != '-1') {
                $dateAvailable = date('Y-m-d', strtotime($row['order_date_added'] . ' + ' . $row['op_selprod_download_validity_in_days'] . ' days'));
            }

            $digitalDownloads[$key]['expiry_date'] = $dateAvailable;

            $digitalDownloads[$key]['downloadable'] = true;
            if ($dateAvailable != '' && $dateAvailable < date('Y-m-d')) {
                $digitalDownloads[$key]['downloadable'] = false;
            }

            $digitalDownloads[$key]['downloadable_count'] = -1;
            if ($row['op_selprod_max_download_times'] != '-1') {
                $digitalDownloads[$key]['downloadable_count'] = $row['op_selprod_max_download_times'];
            }

            if ($row['op_selprod_max_download_times'] != '-1') {
                if ($row['opddl_downloaded_times'] >= $row['op_selprod_max_download_times']) {
                    $digitalDownloads[$key]['downloadable'] = false;
                }
            }
        }
        return $digitalDownloads;
    }

    public static function searchOrderProducts($criteria = array(), $langId = 0)
    {
        $srch = static::getOrderProductSearchObject($langId);

        foreach ($criteria as $key => $val) {
            if (strval($val) == '') {
                continue;
            }
            switch ($key) {
                case 'id':
                case 'op_id':
                    $op_id = FatUtility::int($val);
                    $srch->addCondition('op.op_id', '=', $op_id);
                    break;
                case 'order':
                case 'order_id':
                    $srch->addCondition('op_order_id', '=', $val);
                    break;
            }
        }
        return $srch;
    }

    public function refundOrderPaidAmount($order_id, $langId)
    {
        $order = $this->getOrderById($order_id, $langId);

        $formattedRequestValue = "#" . $order["order_id"];
        $comments = sprintf(Labels::getLabel('LBL_ORDER_NUMBER_COMMENTS', $langId), $formattedRequestValue);
        $txnAmount = $order['order_net_amount'];

        if ($txnAmount <= 0) {
            return false;
        }

        if ($txnAmount > 0) {
            $txnArray["utxn_user_id"] = $order['order_user_id'];
            $txnArray["utxn_credit"] = $txnAmount;
            $txnArray["utxn_status"] = Transactions::STATUS_COMPLETED;
            $txnArray["utxn_order_id"] = $order['order_id'];
            $txnArray["utxn_comments"] = $comments;
            $txnArray["utxn_type"] = Transactions::TYPE_ORDER_REFUND;
            $transObj = new Transactions();
            if ($txnId = $transObj->addTransaction($txnArray)) {
                $emailNotificationObj = new EmailHandler();
                $emailNotificationObj->sendTxnNotification($txnId, $langId);
            }
            return true;
        }

        return false;
    }

    public function updateOrderInfo($order_id, $data)
    {
        if (empty($data) || sizeof($data) <= 0 || !$order_id) {
            $this->error = 'Error, in updating the order, no parameters passed to update record.';
            return false;
        }
        $record = new TableRecord(static::DB_TBL);
        $record->assignValues($data);
        if (!$record->update(array('smt' => 'order_id=?', 'vals' => array($order_id)))) {
            $this->error = $record->getError();
            return false;
        }
        return true;
    }

    /* public function addOrderCancellationRequest( $data = array() ){
        $recordObj = new TableRecord( static::DB_TBL_ORDER_CANCEL_REQUEST );
        $recordObj->assignValues( $data );
        if( !$recordObj->addNew() ){
            $this->error = $recordObj->getError();
            return false;
        }
        return $recordObj->getId();
    } */

    private function generateOrderNo()
    {
        $orderNo = 'O' . mt_rand(1000000000, 9999999999);

        if (empty(self::getOrderByOrderNo($orderNo, attr: ['order_id']))) {
            return $orderNo;
        }
        $this->generateOrderNo();
    }

    public static function getAttributesById($recordId, $attr = null)
    {
        $recordId = FatUtility::int($recordId);
        $db = FatApp::getDb();

        $srch = new SearchBase(static::DB_TBL);
        $srch->doNotCalculateRecords();
        $srch->setPageSize(1);
        $srch->addCondition(static::tblFld('id'), '=', $recordId);

        if (null != $attr) {
            if (is_array($attr)) {
                $srch->addMultipleFields($attr);
            } elseif (is_string($attr)) {
                $srch->addFld($attr);
            }
        }

        $rs = $srch->getResultSet();
        $row = $db->fetch($rs);

        if (!is_array($row)) {
            return false;
        }

        if (is_string($attr)) {
            return $row[$attr];
        }

        return $row;
    }

    /* For Subscription Module  Get latest Subscription Order */
    public static function getLatestSubscriptionOrder($userId = 0, $langId = 0)
    {
        $srch = new  OrderSearch($langId);
        $srch->joinTableOrderSellerSubscription();
        $srch->addCondition('order_type', '=', Orders::ORDER_SUBSCRIPTION);
        $srch->setPageSize(1);
        $srch->doNotCalculateRecords(true);
        $srch->addOrder('order_id');
        $rowCount = $srch->recordCount();
        if ($rowCount == 0) {
            return false;
        }
        return true;
    }

    public static function getOrderCommentById($orderId, $langId)
    {
        $srch = new  OrderSearch($langId);
        $srch->addCondition('order_id', '=', $orderId);
        $srch->doNotCalculateRecords();
        $rs = $srch->getResultSet();
        $orderInfo = FatApp::getDb()->fetch($rs);

        $formattedOrderValue = "#" . $orderInfo['order_number'];
        if ($orderInfo['order_type'] == Orders::ORDER_SUBSCRIPTION) {
            if ($orderInfo['order_renew']) {
                return sprintf(Labels::getLabel('LBL_YOUR_SUBSCRIPTION_IS_RENEWED_%s', $langId), $formattedOrderValue);
            } else {
                return sprintf(Labels::getLabel('LBL_NEW_SUBSCRIPTION_PURCHASED_%s', $langId), $formattedOrderValue);
            }
        } else if ($orderInfo['order_type'] == Orders::ORDER_GIFT_CARD) { 
            return sprintf(Labels::getLabel('LBL_GIFT_CARD_PURCHASED_%s', $langId), $formattedOrderValue);
        } else {
            return sprintf(Labels::getLabel('LBL_ORDER_PLACED_%s', $langId), $formattedOrderValue);
        }
    }

    public function updateOrderUserAddress($userId)
    {
        $srch = new  OrderSearch();
        $srch->addCondition('order_user_id', '=', $userId);
        $srch->doNotCalculateRecords();
        $orderInfo = FatApp::getDb()->fetchAll($srch->getResultSet());

        if (!$orderInfo) {
            return true;
        }
        /* print_r($orderInfo);die; */
        foreach ($orderInfo as $order) {
            if (!FatApp::getDb()->updateFromArray(
                Orders::DB_TBL_ORDER_USER_ADDRESS,
                [
                    'oua_address1' => static::REPLACE_ORDER_USER_ADDRESS,
                    'oua_address2' => static::REPLACE_ORDER_USER_ADDRESS,
                    'oua_city' => static::REPLACE_ORDER_USER_ADDRESS,
                    'oua_state' => static::REPLACE_ORDER_USER_ADDRESS,
                    'oua_country' => static::REPLACE_ORDER_USER_ADDRESS,
                    'oua_country_code' => static::REPLACE_ORDER_USER_ADDRESS,
                    'oua_country_code_alpha3' => static::REPLACE_ORDER_USER_ADDRESS,
                    'oua_state_code' => static::REPLACE_ORDER_USER_ADDRESS,
                    'oua_phone_dcode' => static::REPLACE_ORDER_USER_ADDRESS,
                    'oua_phone' => static::REPLACE_ORDER_USER_ADDRESS,
                    'oua_zip' => static::REPLACE_ORDER_USER_ADDRESS
                ],
                ['smt' => 'oua_order_id = ? ', 'vals' => [$order['order_id']]]
            )) {
                $this->error = FatApp::getDb()->getError();
                return false;
            }
        }

        return true;
    }

    public static function canSubmitFeedback($userId, $op_order_id, $selprod_id)
    {
        if (!FatApp::getConfig('CONF_ALLOW_REVIEWS', FatUtility::VAR_INT, 0)) {
            return false;
        }
        $oFeedbackSrch = new SelProdReviewSearch();
        $oFeedbackSrch->doNotCalculateRecords();
        $oFeedbackSrch->doNotLimitRecords();
        $oFeedbackSrch->addCondition('spreview_postedby_user_id', '=', $userId);
        $oFeedbackSrch->addCondition('spreview_order_id', '=', $op_order_id);
        $oFeedbackSrch->addCondition('spreview_selprod_id', '=', $selprod_id);
        $oFeedbackRs = $oFeedbackSrch->getResultSet();
        if (!empty(FatApp::getDb()->fetch($oFeedbackRs))) {
            return false;
        }
        return true;
    }

    public static function changeOrderStatus()
    {
        $completedOrderStatus = FatApp::getConfig("CONF_DEFAULT_COMPLETED_ORDER_STATUS", FatUtility::VAR_INT, 0);
        $deliveredOrderStatus = FatApp::getConfig("CONF_DEFAULT_DEIVERED_ORDER_STATUS", FatUtility::VAR_INT, 0);
        if (1 > $completedOrderStatus || 1 > $deliveredOrderStatus) {
            return false;
        }
        //$defaultReturnAge = FatApp::getConfig("CONF_DEFAULT_RETURN_AGE", FatUtility::VAR_INT, 7);
        //'IFNULL(op_selprod_return_age, ' . $defaultReturnAge . ') as return_age',    /*replaced*/ 
        $srch = new OrderProductSearch(0, true);
        $srch->doNotCalculateRecords();
        $srch->doNotLimitRecords();
        $srch->addMultipleFields(
            [
                'op.op_id',
                'o.order_date_added',
                'o.order_language_id',
                'op_selprod_return_age as return_age',
                "DATEDIFF(CURDATE(), o.order_date_added) as daysSpent"
            ]
        );
        $srch->joinOrderProductSpecifics();
        $srch->joinTable(OrderCancelRequest::DB_TBL, 'LEFT OUTER JOIN', 'ocr.ocrequest_op_id = op.op_id and ocr.ocrequest_id IS NULL', 'ocr');
        $srch->addCondition('op.op_status_id', '=', $deliveredOrderStatus);
        $srch->addHaving('daysSpent', '>=', 'mysql_func_return_age', 'AND', true);

        $rs = $srch->getResultSet();
        $ordersDetail = FatApp::getDb()->fetchAll($rs, 'op_id');
        $comment = Labels::getLabel("MSG_AUTOMATICALLY_MARKED_AS_COMPLETED_BY_SYSTEM.", FatApp::getConfig('CONF_DEFAULT_SITE_LANG', FatUtility::VAR_INT, 1));

        $order = new Orders();
        foreach ($ordersDetail as $data) {
            $order->addChildProductOrderHistory($data['op_id'], $data["order_language_id"], $completedOrderStatus, $comment, 1);
        }
        return true;
    }

    public function getOrder($orderId, $langId, $opId = 0)
    {
        $opId = FatUtility::int($opId);

        $srch = new OrderSearch($langId);
        $srch->joinOrderPaymentMethod();
        $srch->doNotCalculateRecords();
        $srch->doNotLimitRecords();
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
                'order_deleted'
            )
        );
        $srch->addCondition('order_id', '=', $orderId);
        $srch->addCondition('order_type', '=', self::ORDER_PRODUCT);
        $rs = $srch->getResultSet();
        $order = FatApp::getDb()->fetch($rs);
        if (!$order) {
            $this->error = Labels::getLabel('ERR_ORDER_DATA_NOT_FOUND', $langId);
            return false;
        }

        $attr = array(
            'op_id',
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
            'op_shop_owner_phone_dcode',
            'op_shop_owner_phone',
            'op_unit_price',
            'totCombinedOrders as totOrders',
            'op_shipping_duration_name',
            'op_shipping_durations',
            'IFNULL(orderstatus_name, orderstatus_identifier) as orderstatus_name',
            'op_other_charges',
            'op_product_tax_options'
        );

        $opSrch = new OrderProductSearch($langId, false, true, true);
        $opSrch->addCountsOfOrderedProducts();
        $opSrch->addOrderProductCharges();

        if (0 < $opId) {
            $opSrch->joinSellerProducts();
            $opSrch->joinTable(Orders::DB_TBL_ORDER_PRODUCTS_SHIPPING, 'LEFT OUTER JOIN', 'ops.opshipping_op_id = op.op_id', 'ops');
            $opSrch->joinTable(Orders::DB_TBL_ORDER_PRODUCTS_SHIPPING_LANG, 'LEFT OUTER JOIN', 'opsl.opshippinglang_op_id = op.op_id AND opsl.opshippinglang_lang_id = ' . $langId, 'opsl');
            $opSrch->joinTable(ShippingCompanies::DB_TBL, 'LEFT OUTER JOIN', 'ops.opshipping_company_id = opsc.scompany_id', 'opsc');
            $opSrch->joinTable(ShippingCompanies::DB_TBL_LANG, 'LEFT OUTER JOIN', 'opscl.scompanylang_scompany_id = opsc.scompany_id AND opscl.scompanylang_lang_id = ' . $langId, 'opscl');
            $opSrch->addCondition('op.op_id', '=', $opId);
            $extraAttr = [
                'selprod_product_id',
                'op_selprod_id',
                'opshipping_method_id',
                'opshipping_company_id',
                'op_product_length',
                'op_product_width',
                'op_product_height',
                'op_product_dimension_unit',
                'op_product_weight',
                'op_product_weight_unit',
                'opshipping_carrier_code',
                'IFNULL(scompany_name, scompany_identifier) as carrier_code'
            ];
            $attr = array_merge($attr, $extraAttr);
        }
        $opSrch->addCondition('op.op_order_id', '=', $order['order_id']);
        $opSrch->addMultipleFields($attr);
        $opSrch->doNotCalculateRecords();
        $opSrch->doNotLimitRecords();
        $opRs = $opSrch->getResultSet();
        $order['products'] = FatApp::getDb()->fetchAll($opRs, 'op_id');

        $orderObj = new Orders($order['order_id']);

        $charges = $orderObj->getOrderProductChargesByOrderId($order['order_id']);

        foreach ($order['products'] as $opId => $opVal) {
            $order['products'][$opId]['charges'] = $charges[$opId];
            $taxOptions = json_decode($opVal['op_product_tax_options'], true);
            $order['products'][$opId]['taxOptions'] = $taxOptions;
        }

        $addresses = $orderObj->getOrderAddresses($order['order_id']);
        $order['billingAddress'] = $addresses[self::BILLING_ADDRESS_TYPE];
        $order['shippingAddress'] = (!empty($addresses[self::SHIPPING_ADDRESS_TYPE])) ? $addresses[self::SHIPPING_ADDRESS_TYPE] : array();

        $order['comments'] = $orderObj->getOrderComments($langId, array("order_id" => $order['order_id']));
        $order['payments'] = $orderObj->getOrderPayments(array("order_id" => $order['order_id']));
        return $order;
    }

    public function getOrderPickUpData($orderId, $langId)
    {
        $srch = new OrderProductSearch($langId, true);
        $srch->joinShippingCharges();
        $srch->joinTable(Orders::DB_TBL_ORDER_USER_ADDRESS, 'LEFT OUTER JOIN', 'oua.oua_op_id = op.op_id', 'oua');
        $srch->addCondition('order_id', '=', $orderId);
        $srch->addCondition('oua_type', '=', Orders::PICKUP_ADDRESS_TYPE);
        $srch->addCondition('op_product_type', '=', product::PRODUCT_TYPE_PHYSICAL);
        $srch->addGroupBy('opshipping_pickup_addr_id');
        $srch->doNotCalculateRecords();
        return FatApp::getDb()->fetchAll($srch->getResultSet());
    }

    public function getOrderShippingData($orderId, $langId)
    {
        $srch = new OrderProductSearch($langId, true);
        $srch->joinSellerProducts($langId);
        $srch->joinProduct($langId);
        $srch->joinShippingCharges();
        $srch->addCondition('order_id', '=', $orderId);
        $srch->addCondition('op_product_type', '=', product::PRODUCT_TYPE_PHYSICAL);
        $srch->addOrder('opshipping_op_id');
        $srch->doNotCalculateRecords();
        return FatApp::getDb()->fetchAll($srch->getResultSet());
    }

    public static function isExistTransactionId($gatewayTxnId)
    {
        $srch = new SearchBase(static::DB_TBL_ORDER_PAYMENTS);
        $srch->addCondition('opayment_gateway_txn_id', '=', $gatewayTxnId);
        $srch->addFld('opayment_gateway_txn_id');
        $srch->doNotLimitRecords();
        $srch->doNotCalculateRecords();
        return FatApp::getDb()->fetchAll($srch->getResultSet());
    }

    public static function getOrderIdByPlugin(int $pluginId, int $pluginOrderId): string
    {
        $srch = new SearchBase(static::DB_ORDER_TO_PLUGIN_ORDER);
        $srch->addCondition(static::DB_ORDER_TO_PLUGIN_ORDER_PREFIX . 'plugin_id', '=', $pluginId);
        $srch->addCondition(static::DB_ORDER_TO_PLUGIN_ORDER_PREFIX . 'plugin_order_id', '=', $pluginOrderId);
        $srch->addFld('opo_order_id');
        $srch->doNotCalculateRecords();
        $records = FatApp::getDb()->fetch($srch->getResultSet());
        return !empty($records) ? $records['opo_order_id'] : 0;
    }

    public static function markOrderStatusDeliveredViaApi()
    {
        /** to do fetch the order which not hit recently*/
        $srch = new OrderProductSearch(0, true);
        $srch->joinOrderProductShipment();
        $srch->joinTable(Orders::DB_TBL_ORDER_PRODUCTS_SHIPPING, 'LEFT OUTER JOIN', 'ops.opshipping_op_id = op.op_id', 'ops');
        $srch->joinTable(Plugin::DB_TBL, 'LEFT OUTER JOIN', 'ops.opshipping_plugin_id = plugin.plugin_id', 'plugin');
        $srch->addCondition('op_status_id', 'IN', array(FatApp::getConfig("CONF_DEFAULT_SHIPPING_ORDER_STATUS")));
        $srch->addCondition('opship_tracking_number', '!=', '');
        $srch->addCondition('opshipping_plugin_id', '>', 0);
        $srch->addMultipleFields(array('order_language_id', 'op_id', 'op_invoice_number', 'opship_tracking_number', 'opship_tracking_courier_code', 'opshipping_plugin_id', 'plugin.plugin_code as opshipping_plugin_name', 'opship_tracking_plugin_id', 'op_selprod_user_id', 'opshipping_by_seller_user_id'));
        $srch->doNotCalculateRecords();
        $srch->setPageSize(40);
        $srch->addOrder('RAND()');
        $ordersDetail = FatApp::getDb()->fetchAll($srch->getResultSet());
        if (!empty($ordersDetail)) {
            $shippingObj = new Shipping(CommonHelper::getLangId());
            $shipmentTrackingObj = new ShipmentTracking();
            $trackingPluginLoaded = $shipmentTrackingObj->init(CommonHelper::getLangId());
            foreach ($ordersDetail as $order) {
                $shippedBySeller = CommonHelper::canAvailShippingChargesBySeller($order['op_selprod_user_id'], $order['opshipping_by_seller_user_id']);
                $shippingApiObj = $shippingObj->getShippingApiObj($shippedBySeller ? $order['opshipping_by_seller_user_id'] : 0);
                if (!empty($shippingApiObj) && $shippingApiObj->getkey('plugin_id') == $order['opshipping_plugin_id'] && $shippingApiObj->canFetchTrackingDetail()) {
                    $trackingData = $shippingApiObj->fetchTrackingDetail($order['opship_tracking_number'], $order['op_invoice_number']);
                    if (!empty($trackingData)) {
                        if (in_array(ShippingServicesBase::TRACKING_STATUS_DELIVERED, array_column($trackingData['detail'], 'status'))) {
                            (new self())->markeredOrderProductDelivered($order['op_id'], $order["order_language_id"]);
                        }
                    }
                    continue;
                }
                if (true == $trackingPluginLoaded && in_array($order['opshipping_plugin_name'], $shipmentTrackingObj->getTrackablePluginKeys())) {
                    $response = $shipmentTrackingObj->getTrackingInfo($order["opship_tracking_number"], $order["opship_tracking_courier_code"]);
                    if ($response['meta']['code'] == 200) {
                        if (strtolower($response['data']['tracking']['tag']) == 'delivered') {
                            (new self())->markeredOrderProductDelivered($order['op_id'], $order["order_language_id"]);
                        }
                    }
                }
            }
        }
        return true;
    }

    private function markeredOrderProductDelivered($opId, $orderLangId)
    {
        $comment = Labels::getLabel("MSG_AUTOMATICALLY_MARKED_AS_DELIVERED_BY_SYSTEM.", $orderLangId);
        $order = new Orders();
        $order->addChildProductOrderHistory($opId, $orderLangId, FatApp::getConfig("CONF_DEFAULT_DEIVERED_ORDER_STATUS"), $comment, true);
        $where = array('smt' => 'op_id = ? ', 'vals' => array($opId));
        FatApp::getDb()->updateFromArray(Orders::DB_TBL_ORDER_PRODUCTS, array('op_confirm_date' => date('Y-m-d H:i:s')), $where);
    }

    public static function getPaymentStatusHtml(int $langId, int $status, string $extraInfo = ''): string
    {
        $arr = self::getOrderPaymentStatusArr($langId);
        $msg = $arr[$status] ?? Labels::getLabel('LBL_N/A', $langId);
        $msg = $msg . ' ' . $extraInfo;
        switch ($status) {
            case Orders::ORDER_PAYMENT_PENDING:
                $status = HtmlHelper::INFO;
                break;
            case Orders::ORDER_PAYMENT_PAID:
                $status = HtmlHelper::SUCCESS;
                break;
            case Orders::ORDER_PAYMENT_CANCELLED:
                $status = HtmlHelper::DANGER;
                break;
            default:
                $status = HtmlHelper::PRIMARY;
                break;
        }
        return HtmlHelper::getStatusHtml($status, rtrim($msg));
    }

    public static function canUpdateStatus(array $opRow)
    {
        $orderObj = new Orders($opRow['order_id']);
        if ($opRow['plugin_code'] == 'CashOnDelivery') {
            $processingStatuses = $orderObj->getAdminAllowedUpdateOrderStatuses(true);
        } else if ($opRow['plugin_code'] == 'PayAtStore') {
            $processingStatuses = $orderObj->getAdminAllowedUpdateOrderStatuses(false, false, true);
        } else {
            $processingStatuses = $orderObj->getAdminAllowedUpdateOrderStatuses(false, $opRow['op_product_type']);
        }

        if ($opRow["opshipping_fulfillment_type"] == Shipping::FULFILMENT_PICKUP) {
            $processingStatuses = array_diff($processingStatuses, (array) FatApp::getConfig("CONF_DEFAULT_SHIPPING_ORDER_STATUS", FatUtility::VAR_INT, 0));
        } else {
            $processingStatuses = array_diff($processingStatuses, (array) FatApp::getConfig("CONF_PICKUP_READY_ORDER_STATUS", FatUtility::VAR_INT, 0));
        }
        $processingStatuses = array_diff($processingStatuses, (array) FatApp::getConfig("CONF_DEFAULT_COMPLETED_ORDER_STATUS", FatUtility::VAR_INT, 0));
        return (in_array($opRow['op_status_id'], $processingStatuses) && $opRow['order_payment_status'] != Orders::ORDER_PAYMENT_CANCELLED);
    }


    public function placeGiftcardOrder(array $data)
    {

        $amount = FatUtility::float($data['order_total_amount']);
        $minamount =  FatApp::getConfig('CONF_MINIMUM_GIFT_CARD_AMOUNT', FatUtility::VAR_FLOAT, 100);
        if ($amount < $minamount) {
            $minamount = FatUtility::convertToType($minamount, FatUtility::VAR_FLOAT);
            $label = Labels::getLabel("LBL_MINIMUM_GIFT_CARD_{minamount}");
            $this->error = str_replace("{minamount}", $minamount, $label);
            return false;
        }

        $db = FatApp::getDb();
        if (!$db->startTransaction()) {
            $this->error = $db->getError();
            return false;
        }

        $receiver = User::getByEmail($data['ogcards_receiver_email']);
        if (!empty($receiver['credential_email']) && $receiver['user_deleted'] == applicationConstants::YES) {
            $this->error = Labels::getLabel('LBL_INVALID_REQUEST');
            $db->rollbackTransaction();
            return false;
        }


        $currency = Currency::getAttributesById(CommonHelper::getCurrencyId());
        $orderNumber =  $this->generateOrderNo();
        $this->assignValues([
            'order_type' => static::ORDER_GIFT_CARD,
            'order_user_id' => UserAuthentication::getLoggedUserId(),
            'order_date_added' => date('Y-m-d H:i:s'),
            'order_discount_value' => 0,
            'order_language_id' => $data['order_language_id'],
            'order_language_code' => $data['order_language_code'],
            'order_net_amount' => $amount,
            'order_payment_status' => Orders::ORDER_PAYMENT_PENDING,
            'order_currency_id' => $currency['currency_id'],
            'order_currency_code' => $currency['currency_code'],
            'order_currency_value' => $currency['currency_value'],
            'order_status' => 0,
            'order_number' => $orderNumber,

        ]);
        if (!$this->save()) {
            $db->rollbackTransaction();
            $this->error = $this->getError();
            return false;
        }
        $this->orderId =  $this->getMainTableRecordId();
        $cardData = [
            'ogcards_code' => uniqid(),
            'ogcards_sender_id' => UserAuthentication::getLoggedUserId(),
            'ogcards_status' => GiftCards::STATUS_UNUSED,
            'ogcards_order_id' =>   $this->orderId,
            'ogcards_receiver_name' => $data['ogcards_receiver_name'],
            'ogcards_receiver_email' => $data['ogcards_receiver_email'],
        ];

        if (!empty($receiver['credential_email'])) {
            $cardData['ogcards_receiver_id'] = $receiver['user_id'];
        }
        $card = new GiftCards(0);
        $card->assignValues($cardData);
        if (!$card->addNew([])) {
            $this->error = $card->getError();
            $db->rollbackTransaction();
            return false;
        }
        if (!$db->commitTransaction()) {
            $this->error = $db->getError();
            return false;
        }

        return $this->orderId;
    }


    public static function getOrderPaymentStatus(int $order_id, int $orderType, int $orderStatus): array
    {

        $srch = Orders::getSearchObject();
        $srch->doNotCalculateRecords();
        $srch->setPageSize(1);
        $srch->addCondition('order_id', '=', $order_id);
        $srch->addCondition('order_type', '=', $orderType);
        $srch->addCondition('order_payment_status', '=', 'mysql_func_' . $orderStatus, 'AND', true);
        $rs = $srch->getResultSet();
        return  FatApp::getDb()->fetch($rs);
    }
}
