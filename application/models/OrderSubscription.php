<?php

class OrderSubscription extends MyAppModel
{
    public const DB_TBL = 'tbl_order_seller_subscriptions';
    public const DB_TBL_PREFIX = 'ossubs_';

    public const DB_TBL_LANG = 'tbl_order_seller_subscriptions_lang';
    public const DB_TBL_LANG_PREFIX = 'ossubslang_';

    public const ACTIVE_SUBSCRIPTION = 11;
    public const CANCELLED_SUBSCRIPTION = 12;
    public static $subscriptionId = 0;

    public function __construct($id = 0)
    {
        parent::__construct(static::DB_TBL, static::DB_TBL_PREFIX . 'id', $id);
    }

    public static function getSearchObject($langId = 0)
    {
        $srch = new SearchBase(static::DB_TBL, 'oss');

        if ($langId > 0) {
            $srch->joinTable(
                static::DB_TBL_LANG,
                'LEFT OUTER JOIN',
                'oss_l.' . static::DB_TBL_LANG_PREFIX . 'oss_id = o.ossub_id
			AND opssub_l.' . static::DB_TBL_LANG_PREFIX . 'lang_id = ' . $langId,
                'ossub_l'
            );
        }

        return $srch;
    }


    public static function canUserBuyFreeSubscription($langId = 0, $userId = 0)
    {
        $userId = FatUtility::int($userId);
        if ($userId < 1) {
            trigger_error(Labels::getLabel('ERR_USER_ID_NOT_SPECIFIED', CommonHelper::getLangId()), E_USER_ERROR);
            return false;
        }

        $srch = new  OrderSearch($langId);
        $srch->joinTableOrderSellerSubscription();
        $srch->addCondition(Orders::DB_TBL_PREFIX . 'type', '=', Orders::ORDER_SUBSCRIPTION);
        $srch->addCondition(Orders::DB_TBL_PREFIX . 'payment_status', '=', Orders::ORDER_PAYMENT_PAID);
        $srch->addCondition(Orders::DB_TBL_PREFIX . 'user_id', '=', $userId);
        $srch->setPageSize(1);

        $srch->addOrder(Orders::DB_TBL_PREFIX . 'number');
        $rs = $srch->getResultSet();

        $rowCount = $srch->recordCount();
        if ($rowCount == 0) {
            return true;
        }
        return false;
    }

    public static function setOrderSubscriptionId(int $subscriptionId)
    {
        self::$subscriptionId = $subscriptionId;
    }

    public static function getUserCurrentActivePlanDetails($langId = 0, $userId = 0, $flds = array(OrderSubscription::DB_TBL_PREFIX . 'id'))
    {
        $userId = FatUtility::int($userId);
        if ($userId === 1) {
            return self::getBypassSubscriptionPlanRow($langId, $flds);
        }

        $srch = new OrderSearch($langId);
        $srch->joinTableOrderSellerSubscription($langId);
        $srch->joinTableSubscriptionPlan();
        $srch->joinPackage($langId);

        //$srch->addSubscriptionValidCondition();
        $srch->addCondition(Orders::DB_TBL_PREFIX . 'type', '=', 'mysql_func_' . Orders::ORDER_SUBSCRIPTION, 'AND', true);
        $srch->addCondition(Orders::DB_TBL_PREFIX . 'payment_status', '=', 'mysql_func_' . Orders::ORDER_PAYMENT_PAID, 'AND', true);
        $srch->addCondition(Orders::DB_TBL_PREFIX . 'user_id', '=', 'mysql_func_' . $userId, 'AND', true);
        if (0 < self::$subscriptionId) {
            $srch->addCondition('ossubs_id', '= ', self::$subscriptionId);
        } else {
            $srch->addCondition('ossubs_status_id', 'IN ', Orders::getActiveSubscriptionStatusArr());
        }
        $srch->addMultipleFields($flds);
        $srch->setPageSize(1);
        $srch->doNotCalculateRecords(true);
        $srch->addOrder(Orders::DB_TBL_PREFIX . 'id', 'desc');
        return FatApp::getDb()->fetch($srch->getResultSet());
    }

    private static function getBypassSubscriptionPlanRow($langId, $flds)
    {
        $today = date('Y-m-d');
        $defaults = [
            'ossubs_id' => 0,
            'ossubs_plan_id' => 0,
            'ossubs_type' => SellerPackages::PAID_TYPE,
            'ossubs_price' => 0,
            'ossubs_interval' => 1,
            'ossubs_frequency' => SellerPackagePlans::SUBSCRIPTION_PERIOD_UNLIMITED,
            'ossubs_from_date' => $today,
            'ossubs_till_date' => '2099-12-31',
            'ossubs_status_id' => self::ACTIVE_SUBSCRIPTION,
            'ossubs_subscription_name' => 'Subscription Bypassed',
            'ossubs_products_allowed' => 9999999,
            'ossubs_inventory_allowed' => 9999999,
            'ossubs_images_allowed' => 9999999,
            'ossubs_rfq_offers_allowed' => 9999999,
            // common plan fields used via spp.* in some screens
            'spplan_id' => 0,
            'spplan_price' => 0,
            'spplan_interval' => 1,
            'spplan_frequency' => SellerPackagePlans::SUBSCRIPTION_PERIOD_UNLIMITED,
            'spplan_trial_interval' => 0,
            'spplan_trial_frequency' => SellerPackagePlans::SUBSCRIPTION_PERIOD_DAYS,
            'spackage_name' => Labels::getLabel('LBL_Subscription', $langId) . ' (Bypass)',
        ];

        if (is_array($flds) && in_array('spp.*', $flds, true)) {
            $flds = array_values(array_filter($flds, function ($f) {
                return $f !== 'spp.*';
            }));
            $flds = array_merge($flds, array_keys($defaults));
        }

        if (!is_array($flds) || empty($flds)) {
            return $defaults;
        }

        $out = [];
        foreach ($flds as $field) {
            $out[$field] = $defaults[$field] ?? null;
        }
        return $out;
    }

    public static function getOSSubIdArrByOrderId($orderId)
    {
        $opSrch = static::getSearchObject();
        $opSrch->doNotCalculateRecords();
        $opSrch->doNotLimitRecords();
        $opSrch->addMultipleFields(array(OrderSubscription::DB_TBL_PREFIX . 'id'));
        $opSrch->addCondition(OrderSubscription::DB_TBL_PREFIX . 'order_id', '=', $orderId);

        $rs = $opSrch->getResultSet();
        return $row = FatApp::getDb()->fetchAll($rs, OrderSubscription::DB_TBL_PREFIX . 'id');
    }
    public static function getChargeTypeArr($langId)
    {
        $langId = FatUtility::int($langId);
        if ($langId < 1) {
            $langId = FatApp::getConfig('CONF_ADMIN_DEFAULT_LANG');
        }
        return array(
            OrderProduct::CHARGE_TYPE_DISCOUNT => Labels::getLabel('LBL_ORDER_PRODUCT_DISCOUNT_CHARGES', $langId),
            OrderProduct::CHARGE_TYPE_REWARD_POINT_DISCOUNT => Labels::getLabel('LBL_ORDER_PRODUCT_REWARD_POINT', $langId),
            OrderProduct::CHARGE_TYPE_ADJUST_SUBSCRIPTION_PRICE => Labels::getLabel('LBL_ORDER_ADJUSTMENT', $langId),

        );
    }

    public function getOrderSubscriptionByOssubId($ossubs_id, $langId)
    {
        $ossubs_id = FatUtility::int($ossubs_id);
        $langId = FatUtility::int($langId);
        $srch = new OrderSubscriptionSearch($langId);
        $srch->joinTable(Orders::DB_TBL, 'LEFT OUTER JOIN', 'o.' . Orders::DB_TBL_PREFIX . 'id = oss.' . OrderSubscription::DB_TBL_PREFIX . 'order_id', 'o');
        $srch->joinTable(Orders::DB_TBL_CHARGES, 'LEFT OUTER JOIN', 'oc.' . Orders::DB_TBL_CHARGES_PREFIX . 'op_id = oss.' . OrderSubscription::DB_TBL_PREFIX . 'id', 'oc');
        $srch->addMultipleFields(array('oss.*', 'oss_l.*', 'o.' . Orders::DB_TBL_PREFIX . 'payment_status', 'o.' . Orders::DB_TBL_PREFIX . 'language_id', 'o.' . Orders::DB_TBL_PREFIX . 'user_id', 'o.' . Orders::DB_TBL_PREFIX . 'number', 'sum(' . OrderProduct::DB_TBL_CHARGES_PREFIX . 'amount) as op_other_charges'));
        $srch->addCondition(OrderSubscription::DB_TBL_PREFIX . 'id', '=', $ossubs_id);
        $srch->doNotCalculateRecords();
        $srch->doNotLimitRecords();
        $records = array();
        $row = FatApp::getDb()->fetch($srch->getResultSet());
        $charges = $this->getOrderSubscriptionChargesArr($ossubs_id);
        if (!empty($row)) {
            $records = $row;
            $records['charges'] = $charges;
        }
        return $records;
    }

    public function getOrderSubscriptionChargesArr($ossubs_id)
    {
        $ossubs_id = FatUtility::int($ossubs_id);
        $srch = new SearchBase(Orders::DB_TBL_CHARGES, 'oc');
        $srch->doNotCalculateRecords();
        $srch->doNotLimitRecords();
        $srch->addMultipleFields(array(Orders::DB_TBL_CHARGES_PREFIX . 'type', Orders::DB_TBL_CHARGES_PREFIX . 'amount'));
        $srch->addCondition(Orders::DB_TBL_CHARGES_PREFIX . 'op_id', '=', $ossubs_id);
        $srch->addCondition(Orders::DB_TBL_CHARGES_PREFIX . 'order_type', '=', Orders::ORDER_SUBSCRIPTION);
        return FatApp::getDb()->fetchAll($srch->getResultSet(), Orders::DB_TBL_CHARGES_PREFIX . 'type');
    }

    public static function searchOrderSubscription($criteria = array(), $langId = 0)
    {
        $srch = new OrderSubscriptionSearch();

        foreach ($criteria as $key => $val) {
            if (strval($val) == '') {
                continue;
            }
            switch ($key) {
                case 'id':
                case OrderSubscription::DB_TBL_PREFIX . 'id':
                    $ossubs_id = FatUtility::int($val);
                    $srch->addCondition('oss.' . OrderSubscription::DB_TBL_PREFIX . 'id', '=', $ossubs_id);
                    break;
                case 'order':
                case Orders::DB_TBL_PREFIX . 'id':
                    $srch->addCondition(OrderSubscription::DB_TBL_PREFIX . 'order_id', '=', $val);
                    break;
            }
        }
        return $srch;
    }
    public static function getAdjustedAmount($currentPlanDetails = array(), $userId = 0)
    {
        if (empty($currentPlanDetails)) {
            die(Labels::getLabel('ERR_PLEASE_PROVIDE_PLAN_DETAILS', CommonHelper::getLangId()));
        }
        if ($currentPlanDetails[SellerPackages::DB_TBL_PREFIX . 'type'] == SellerPackages::FREE_TYPE) {
            return 0;
        }
        $planDetails = SellerPackagePlans::getAttributesById($currentPlanDetails[OrderSubscription::DB_TBL_PREFIX . 'plan_id']);
        // $activePlanInterval = $planDetails[SellerPackagePlans::DB_TBL_PREFIX . 'interval'];
        $activePlanFrequency = $planDetails[SellerPackagePlans::DB_TBL_PREFIX . 'frequency'];
        if (SellerPackagePlans::SUBSCRIPTION_PERIOD_UNLIMITED == $activePlanFrequency) {
            return 0;
        }

        $pendingDaysForCurrentPlan = FatDate::diff(date("Y-m-d"), $currentPlanDetails[OrderSubscription::DB_TBL_PREFIX . 'till_date']) - 1;
        if ($pendingDaysForCurrentPlan < 0) {
            $pendingDaysForCurrentPlan = 0;
        }
        
        $totalPlanDays = FatDate::diff($currentPlanDetails[OrderSubscription::DB_TBL_PREFIX . 'from_date'], $currentPlanDetails[OrderSubscription::DB_TBL_PREFIX . 'till_date']);
        $perDayPrice = $currentPlanDetails[OrderSubscription::DB_TBL_PREFIX . 'price'] / $totalPlanDays;
        $adjustableAmount = $perDayPrice * $pendingDaysForCurrentPlan;
        return $adjustableAmount;
    }
    public static function getSubscriptionEndingList($lastUserIdCond = false)
    {
        $statusArr = Orders::getActiveSubscriptionStatusArr();

        $srch = new OrderSubscriptionSearch();
        $srch->joinOrders();

        $srch->joinOrderUser();
        $srch->addCondition('order_payment_status', '=', ORDERS::ORDER_PAYMENT_PAID);
        $srch->addCondition('ossubs_status_id', 'IN', $statusArr);
        $srch->addCondition('ossubs_till_date', '=', date('Y-m-d', strtotime('+' . FatApp::getConfig('CONF_BEFORE_EXIPRE_SUBSCRIPTION_REMINDER_EMAIL_DAYS', FatUtility::VAR_INT, 2) . ' days')));

        $srch->addCondition('ossubs_till_date', '!=', '0000-00-00');
        /* $srch->addCondition('user_autorenew_subscription','=',1); */
        $srch->addMultipleFields(array('user_id', 'ossubs_id', 'ossubs_type', 'user_name', 'user_phone_dcode', 'user_phone', 'credential_email', 'order_language_id'));
        /* $srch->addGroupBy('order_user_id');  */
        $srch->addOrder('ossubs_id', 'desc');
        if ($lastUserIdCond) {
            $srch->addCondition('user_id', '>', FatApp::getConfig("CONF_CRON_SUBSCRIPTION_REMINDER_LAST_EXECUTED_USERID", FatUtility::VAR_INT, 0));
        }
        $srch->doNotCalculateRecords();
        return FatApp::getDb()->fetchAll($srch->getResultSet(), 'ossubs_id');
    }

    public static function getSubscriptionTitle($plan, $langId = 0, $includeAmount = true)
    {
        if (!$langId) {
            $langId = CommonHelper::getLangId();
        }
        $price = $plan['ossubs_price'];

        $subcriptionPeriodArr = SellerPackagePlans::getSubscriptionPeriods($langId);
        $price = (true === $includeAmount ? CommonHelper::displayMoneyFormat($price) : '');

        if ($plan['ossubs_frequency'] == SellerPackagePlans::SUBSCRIPTION_PERIOD_UNLIMITED) {
            return $price . " / " . $subcriptionPeriodArr[$plan['ossubs_frequency']];
        }
        $planText = ($plan['ossubs_type'] == SellerPackages::PAID_TYPE) ? " /" . " " . Labels::getLabel("LBL_PER", $langId) : Labels::getLabel("LBL_FOR", $langId);

        return $plan['ossubs_subscription_name'] . " - " . $price . $planText . " " . (($plan['ossubs_interval'] > 0) ? $plan['ossubs_interval'] : '')
            . "  " . $subcriptionPeriodArr[$plan['ossubs_frequency']];
    }
}
