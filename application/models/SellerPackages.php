<?php

class SellerPackages extends MyAppModel
{
    public const DB_TBL = 'tbl_seller_packages';
    public const DB_TBL_PREFIX = 'spackage_';
    public const DB_TBL_LANG = 'tbl_seller_packages_lang';
    public const DB_TBL_LANG_PREFIX = 'spackagelang_';
    public const FREE_TYPE = 1;
    public const PAID_TYPE = 2;

    public const CLASS_ONE = '';
    public const CLASS_TWO = 'two';
    public const CLASS_THREE = 'three';

    private $db;

    public function __construct($id = 0)
    {
        parent::__construct(static::DB_TBL, static::DB_TBL_PREFIX . 'id', $id);
        $this->objMainTableRecord->setSensitiveFields([self::DB_TBL_PREFIX . 'id']);
        $this->db = FatApp::getDb();
    }

    public static function getPackageClass()
    {
        return array(
            '1' => SellerPackages::CLASS_ONE,
            '2' => SellerPackages::CLASS_TWO,
            '3' => SellerPackages::CLASS_THREE,
            '4' => SellerPackages::CLASS_ONE,
            '5' => SellerPackages::CLASS_TWO,
            '6' => SellerPackages::CLASS_THREE,
            '7' => SellerPackages::CLASS_ONE,
            '8' => SellerPackages::CLASS_TWO,
            '9' => SellerPackages::CLASS_THREE,
        );
    }
    public static function getSearchObject($langId = 0)
    {
        $srch = new SearchBase(static::DB_TBL, 'sp');

        if ($langId) {
            $srch->joinTable(
                SellerPackages::DB_TBL . '_lang',
                'LEFT OUTER JOIN',
                'spl.spackagelang_spackage_id = sp.spackage_id AND spl.spackagelang_lang_id = ' . $langId,
                'spl'
            );
        }

        return $srch;
    }
    public static function getSellerPackages($langId = 0)
    {
        $srch = self::getSearchObject($langId);
        $srch->addMultipleFields(array("sp.spackage_id", "IFNULL( spl.spackage_name, sp.spackage_identifier ) as spackage_name"));
        $srch->doNotCalculateRecords();
        return FatApp::getDb()->fetchAllAssoc($srch->getResultSet());
    }

    public static function getSellerVisiblePackages($langId = 0, $includeFreePackages = true)
    {
        $srch = new PackagesSearch($langId);
        $srch->joinTable(SellerPackagePlans::DB_TBL, 'INNER JOIN', 'sp.spackage_id =spp.spplan_spackage_id', 'spp');
        $srch->addMultipleFields(
            array(
                "sp.spackage_id", "IFNULL( spl.spackage_name, sp.spackage_identifier ) as spackage_name", "spackage_text", "spackage_description", "spackage_products_allowed", "spackage_inventory_allowed", "spackage_images_per_product", "spackage_commission_rate", "spackage_type", "spackage_rfq_offers_allowed"
            )
        );
        $srch->addGroupBy('sp.spackage_id');
        $srch->addCondition('sp.spackage_active', '=', applicationConstants::YES);
        $srch->addOrder('sp.spackage_display_order');
        if (!$includeFreePackages) {
            $srch->addCondition('sp.spackage_type', '=', SellerPackages::PAID_TYPE);
        }
        $srch->doNotCalculateRecords();
        return FatApp::getDb()->fetchAll($srch->getResultSet());
    }

    public static function getPackageTypes()
    {
        return array(
            '' => Labels::getLabel('LBL_SELECT_PLAN', CommonHelper::getLangId()),
            SellerPackages::FREE_TYPE => Labels::getLabel('LBL_FREE_PLAN', CommonHelper::getLangId()),
            SellerPackages::PAID_TYPE => Labels::getLabel('LBL_PAID_PLAN', CommonHelper::getLangId()),
        );
    }
    public static function getAllowedLimit($userId, $langId, $key = '')
    {
        $columns = array("ossubs_products_allowed", "ossubs_inventory_allowed", "ossubs_images_allowed");
        $currentActivePlan = OrderSubscription::getUserCurrentActivePlanDetails($langId, $userId, $columns);

        if (!empty($key)) {
            return is_array($currentActivePlan) ? $currentActivePlan[$key] : 0;
        }

        return $currentActivePlan;
    }
}
