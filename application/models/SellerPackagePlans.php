<?php

class SellerPackagePlans extends MyAppModel
{
    public const DB_TBL = 'tbl_seller_packages_plan';
    public const DB_TBL_PREFIX = 'spplan_';

    public const SUBSCRIPTION_PERIOD_DAYS = 'D';
    public const SUBSCRIPTION_PERIOD_WEEKS = 'W';
    public const SUBSCRIPTION_PERIOD_MONTH = 'M';
    public const SUBSCRIPTION_PERIOD_YEAR = 'Y';
    public const SUBSCRIPTION_PERIOD_UNLIMITED = 'U';

    private $db;

    public function __construct($id = 0)
    {
        parent::__construct(static::DB_TBL, static::DB_TBL_PREFIX . 'id', $id);
        $this->db = FatApp::getDb();
    }

    public static function getSearchObject()
    {
        return new SearchBase(static::DB_TBL, 'spp');
    }


    public static function getSellerVisiblePackagePlans($packageId = 0)
    {
        $srch = new SellerPackagePlansSearch();
        $srch->joinPackage();
        $srch->addMultipleFields(array("spp.*", "spackage_type"));
        if ($packageId > 0) {
            $srch->addCondition(SellerPackagePlans::DB_TBL_PREFIX . 'spackage_id', '=', $packageId);
        }
        $srch->addCondition('spp.spplan_active', '=', applicationConstants::YES);
        $srch->addOrder('spp.spplan_display_order', 'ASC');
        $srch->doNotCalculateRecords();
        return FatApp::getDb()->fetchAll($srch->getResultSet());
    }
    public static function getSubscriptionPeriods($langId = 0)
    {
        $langId = FatUtility::convertToType($langId, FatUtility::VAR_INT);
        if (!$langId) {
            trigger_error(Labels::getLabel('ERR_LANGUAGE_ID_NOT_SPECIFIED.', CommonHelper::getLangId()), E_USER_ERROR);
            return false;
        }
        return array(
            self::SUBSCRIPTION_PERIOD_DAYS => Labels::getLabel('LBL_DAYS', $langId),
            /* self::SUBSCRIPTION_PERIOD_WEEKS => Labels::getLabel('LBL_Weeks', $langId), */
            self::SUBSCRIPTION_PERIOD_MONTH => Labels::getLabel('LBL_MONTHS', $langId),
            self::SUBSCRIPTION_PERIOD_YEAR => Labels::getLabel('LBL_YEARS', $langId),
            self::SUBSCRIPTION_PERIOD_UNLIMITED => Labels::getLabel('LBL_UNLIMITED', $langId),
        );
    }
    public static function getSubscriptionPeriodValues()
    {
        return array(
            self::SUBSCRIPTION_PERIOD_DAYS => 'DAY',
            /* self::SUBSCRIPTION_PERIOD_WEEKS => self::SUBSCRIPTION_PERIOD_WEEKS, */
            self::SUBSCRIPTION_PERIOD_MONTH => 'MONTHS',
            self::SUBSCRIPTION_PERIOD_YEAR => 'YEAR',
            self::SUBSCRIPTION_PERIOD_UNLIMITED => 'YEAR',
        );
    }

    /**
     * Payable amount after flat spplan_discount (capped at list price).
     */
    public static function getPlanPayableAmountFromRow(array $row): float
    {
        $price = isset($row['spplan_price']) ? (float) $row['spplan_price'] : 0;
        $disc = isset($row['spplan_discount']) ? (float) $row['spplan_discount'] : 0;
        if ($disc < 0) {
            $disc = 0;
        }
        if ($disc > $price) {
            $disc = $price;
        }
        return round($price - $disc, 2);
    }

    /**
     * Price label for package UIs: strikethrough list price when discount applies, then period text at payable price.
     */
    public static function getPlanPriceDisplayForPackage(array $plan): string
    {
        $list = (float) ($plan['spplan_price'] ?? 0);
        $payable = static::getPlanPayableAmountFromRow($plan);
        $disc = isset($plan['spplan_discount']) ? (float) $plan['spplan_discount'] : 0;
        $periodPart = static::getPlanPriceWithPeriod($plan, $payable);
        if ($disc > 0 && $payable < $list) {
            return '<span class="text-decoration-line-through">' . CommonHelper::displayMoneyFormat($list) . '</span> ' . $periodPart;
        }
        return static::getPlanPriceWithPeriod($plan, $list);
    }
    public static function getPlanPeriod($plan)
    {
        $subcriptionPeriodArr = self::getSubscriptionPeriods(CommonHelper::getLangId());
        $frequency = isset($plan[SellerPackagePlans::DB_TBL_PREFIX . 'frequency']) ? $plan[SellerPackagePlans::DB_TBL_PREFIX . 'frequency'] : '';
        if ($frequency == SellerPackagePlans::SUBSCRIPTION_PERIOD_UNLIMITED) {
            $period = isset($subcriptionPeriodArr[$frequency]) ? $subcriptionPeriodArr[$frequency] : '';
            return $period;
        }
        $type = isset($plan[SellerPackages::DB_TBL_PREFIX . 'type']) ? $plan[SellerPackages::DB_TBL_PREFIX . 'type'] : '';
        $interval = isset($plan[SellerPackagePlans::DB_TBL_PREFIX . 'interval']) ? $plan[SellerPackagePlans::DB_TBL_PREFIX . 'interval'] : 0;
        $frequency = isset($plan[SellerPackagePlans::DB_TBL_PREFIX . 'frequency']) ? $plan[SellerPackagePlans::DB_TBL_PREFIX . 'frequency'] : '';
        $period = isset($subcriptionPeriodArr[$frequency]) ? $subcriptionPeriodArr[$frequency] : '';

        $planText = ($type == SellerPackages::PAID_TYPE) ? Labels::getLabel("LBL_PER", CommonHelper::getLangId()) : Labels::getLabel("LBL_FOR", CommonHelper::getLangId());
        return $planText . " " . (($interval > 0) ? $interval : '') . "  " . $period;
    }
    public static function getPlanTrialPeriod($plan)
    {
        $subcriptionPeriodArr = self::getSubscriptionPeriods(CommonHelper::getLangId());
        $interval = isset($plan[SellerPackagePlans::DB_TBL_PREFIX . 'trial_interval']) ? $plan[SellerPackagePlans::DB_TBL_PREFIX . 'trial_interval'] : 0;
        if ($interval < 1) {
            return Labels::getLabel("LBL_N/A", CommonHelper::getLangId());
        }
        $frequency = isset($plan[SellerPackagePlans::DB_TBL_PREFIX . 'trial_frequency']) ? $plan[SellerPackagePlans::DB_TBL_PREFIX . 'trial_frequency'] : '';
        $period = isset($subcriptionPeriodArr[$frequency]) ? $subcriptionPeriodArr[$frequency] : '';
        return (($interval > 0) ? $interval : '') . " " . $period;
    }

    public static function getPlanPriceWithPeriod($plan, $price)
    {
        $subcriptionPeriodArr = self::getSubscriptionPeriods(CommonHelper::getLangId());
        $frequency = isset($plan[SellerPackagePlans::DB_TBL_PREFIX . 'frequency']) ? $plan[SellerPackagePlans::DB_TBL_PREFIX . 'frequency'] : '';
        if ($frequency == SellerPackagePlans::SUBSCRIPTION_PERIOD_UNLIMITED) {
            $period = isset($subcriptionPeriodArr[$frequency]) ? $subcriptionPeriodArr[$frequency] : '';
            return CommonHelper::displayMoneyFormat($price) . " /  " . $period;
        }
        $type = isset($plan[SellerPackages::DB_TBL_PREFIX . 'type']) ? $plan[SellerPackages::DB_TBL_PREFIX . 'type'] : '';
        $interval = isset($plan[SellerPackagePlans::DB_TBL_PREFIX . 'interval']) ? $plan[SellerPackagePlans::DB_TBL_PREFIX . 'interval'] : 0;
        $frequency = isset($plan[SellerPackagePlans::DB_TBL_PREFIX . 'frequency']) ? $plan[SellerPackagePlans::DB_TBL_PREFIX . 'frequency'] : '';
        $period = isset($subcriptionPeriodArr[$frequency]) ? $subcriptionPeriodArr[$frequency] : '';

        $planText = ($type == SellerPackages::PAID_TYPE) ? " /" . " " . Labels::getLabel("LBL_PER", CommonHelper::getLangId()) : " " . Labels::getLabel("LBL_FOR", CommonHelper::getLangId());
        return CommonHelper::displayMoneyFormat($price) . $planText . " " . (($interval > 0) ? $interval : '') . "  " . $period;
    }

    public static function getCheapPlanPriceWithPeriod($plan, $price)
    {
        $subcriptionPeriodArr = self::getSubscriptionPeriods(CommonHelper::getLangId());
        $interval = isset($plan[SellerPackagePlans::DB_TBL_PREFIX . 'interval']) ? $plan[SellerPackagePlans::DB_TBL_PREFIX . 'interval'] : 0;
        $frequency = isset($plan[SellerPackagePlans::DB_TBL_PREFIX . 'frequency']) ? $plan[SellerPackagePlans::DB_TBL_PREFIX . 'frequency'] : '';
        $period = isset($subcriptionPeriodArr[$frequency]) ? $subcriptionPeriodArr[$frequency] : '';

        return CommonHelper::displayMoneyFormat($price) . " <span>" . " " . Labels::getLabel("LBL_PER", CommonHelper::getLangId()) . " " . (($interval > 1) ? $interval : '') . "  " . $period . "</span>";
    }

    /** Package card headline: strikethrough list price when a flat discount applies, else same as getCheapPlanPriceWithPeriod at list price. */
    public static function getCheapPlanPriceDisplayForPackage(array $plan): string
    {
        $list = (float) ($plan['spplan_price'] ?? 0);
        $payable = static::getPlanPayableAmountFromRow($plan);
        $disc = isset($plan['spplan_discount']) ? (float) $plan['spplan_discount'] : 0;
        $cheapPart = static::getCheapPlanPriceWithPeriod($plan, $payable);
        if ($disc > 0 && $payable < $list) {
            return '<span class="text-decoration-line-through">' . CommonHelper::displayMoneyFormat($list) . '</span> ' . $cheapPart;
        }
        return static::getCheapPlanPriceWithPeriod($plan, $list);
    }

    public static function getPlanByPackageId($spackageId = 0)
    {
        $spackageId = FatUtility::convertToType($spackageId, FatUtility::VAR_INT);
        if (!$spackageId) {
            trigger_error(Labels::getLabel('ERR_PACKAGE_ID_NOT_SPECIFIED', CommonHelper::getLangId()), E_USER_ERROR);
            return false;
        }
        $srch = new SellerPackagePlansSearch();
        $srch->joinPackage(CommonHelper::getLangId());
        $srch->addMultipleFields(array("spackage_type", "spp.*", "spp." . SellerPackagePlans::DB_TBL_PREFIX . "price"));
        $srch->addCondition(SellerPackagePlans::DB_TBL_PREFIX . 'spackage_id', '=', $spackageId);

        $srch->addOrder(SellerPackagePlans::DB_TBL_PREFIX . 'display_order');
        $srch->doNotCalculateRecords();
        return FatApp::getDb()->fetchAll($srch->getResultSet());
    }

    public static function getCheapestPlanByPackageId($spackageId = 0)
    {
        $spackageId = FatUtility::convertToType($spackageId, FatUtility::VAR_INT);
        if (!$spackageId) {
            trigger_error(Labels::getLabel('ERR_PACKAGE_ID_NOT_SPECIFIED', CommonHelper::getLangId()), E_USER_ERROR);
            return false;
        }
        $srch = new SellerPackagePlansSearch();
        $srch->addCondition(SellerPackagePlans::DB_TBL_PREFIX . 'spackage_id', '=', $spackageId);
        $srch->addMultipleFields(array(SellerPackagePlans::DB_TBL_PREFIX . 'price', SellerPackagePlans::DB_TBL_PREFIX . 'discount', SellerPackagePlans::DB_TBL_PREFIX . 'interval', SellerPackagePlans::DB_TBL_PREFIX . 'frequency'));
        $srch->addFld('(spp.spplan_price - LEAST(COALESCE(spp.spplan_discount, 0), spp.spplan_price)) AS spplan_effective_sort');
        $srch->addOrder('spplan_effective_sort', 'ASC');
        $srch->addCondition('spp.spplan_active', '=', applicationConstants::YES);
        $srch->setPageSize(1);
        $srch->doNotCalculateRecords(true);
        $row = FatApp::getDb()->fetch($srch->getResultSet());
        return (is_array($row) ? $row : []);
    }

    public static function getSubscriptionPlanDataByPlanId($spplan_id = 0, $siteLangId = 0)
    {
        $spplan_id = FatUtility::convertToType($spplan_id, FatUtility::VAR_INT);
        if (!$spplan_id) {
            trigger_error(Labels::getLabel('ERR_PACKAGE_ID_NOT_SPECIFIED', $siteLangId), E_USER_ERROR);
            return false;
        }
        $srch = new SellerPackageSearch($siteLangId);

        $srch->joinPlan();
        $srch->addCondition(SellerPackagePlans::DB_TBL_PREFIX . 'id', '=', $spplan_id);
        $srch->addMultipleFields(array(SellerPackagePlans::DB_TBL_PREFIX . 'price', SellerPackagePlans::DB_TBL_PREFIX . 'discount', SellerPackages::DB_TBL_PREFIX . 'products_allowed', SellerPackages::DB_TBL_PREFIX . 'inventory_allowed', SellerPackages::DB_TBL_PREFIX . 'images_per_product', SellerPackages::DB_TBL_PREFIX . 'rfq_offers_allowed', SellerPackagePlans::DB_TBL_PREFIX . 'interval', SellerPackages::DB_TBL_PREFIX . 'type', SellerPackagePlans::DB_TBL_PREFIX . 'frequency', SellerPackages::DB_TBL_PREFIX . 'name', SellerPackagePlans::DB_TBL_PREFIX . 'trial_interval', SellerPackagePlans::DB_TBL_PREFIX . 'trial_frequency'));
        $srch->addOrder(SellerPackagePlans::DB_TBL_PREFIX . 'price', 'asc');
        $srch->addCondition('spp.spplan_active', '=', applicationConstants::YES);
        $srch->setPageSize(1);
        $srch->doNotCalculateRecords(true);

        return FatApp::getDb()->fetch($srch->getResultSet());
    }
}
