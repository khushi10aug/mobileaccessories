<?php

/* created this class to access direct functions of getAttributesById and save function for below mentioned DB table. */

class SellerProduct extends MyAppModel
{

    public const DB_TBL = 'tbl_seller_products';
    public const DB_TBL_PREFIX = 'selprod_';
    public const DB_PROD_TBL = 'tbl_products';
    public const DB_PROD_TBL_PREFIX = 'product_';
    public const DB_TBL_LANG = 'tbl_seller_products_lang';
    public const DB_TBL_LANG_PREFIX = 'selprodlang_';
    public const DB_TBL_SELLER_PROD_OPTIONS = 'tbl_seller_product_options';
    public const DB_TBL_SELLER_PROD_OPTIONS_PREFIX = 'selprodoption_';
    public const DB_TBL_SELLER_PROD_SPCL_PRICE = 'tbl_product_special_prices';
    public const DB_TBL_SELLER_PROD_POLICY = 'tbl_seller_product_policies';
    public const DB_TBL_UPSELL_PRODUCTS = 'tbl_upsell_products';
    public const DB_TBL_UPSELL_PRODUCTS_PREFIX = 'upsell_';
    public const DB_TBL_RELATED_PRODUCTS = 'tbl_related_products';
    public const DB_TBL_RELATED_PRODUCTS_PREFIX = 'related_';
    public const DB_TBL_EXTERNAL_RELATIONS = 'tbl_seller_product_external_relations';
    public const DB_TBL_EXTERNAL_RELATIONS_PREFIX = 'sperel_';
    public const DB_SELLER_PROD_TO_PLUGIN_SELLER_PROD = 'tbl_seller_products_to_plugin_selprod';
    public const DB_SELLER_PROD_TO_PLUGIN_SELLER_PROD_PREFIX = 'spps_';
    public const MAX_RANGE_OF_MINIMUM_PURCHANGE_QTY = 9999;
    public const VOL_DISCOUNT_MIN_QTY = 2;
    public const VOL_DISCOUNT_MAX_QTY = 9999;
    public const UPDATE_OPTIONS_COUNT = 10;
    public const INVENTORY_RESTRICT_LIMIT = 20;
    public const OPTION_NAME_SEPARATOR = ':';
    public const MULTIPLE_OPTION_SEPARATOR = ' | ';
    public const MAX_RANGE_OF_AVAILBLE_QTY = 999999999;

    public const CART_TYPE_BOTH = 0;
    public const CART_TYPE_CART_ONLY = 1;
    public const CART_TYPE_RFQ_ONLY = 2;

    public function __construct($id = 0)
    {
        parent::__construct(static::DB_TBL, static::DB_TBL_PREFIX . 'id', $id);
        $this->objMainTableRecord->setSensitiveFields(
            array('selprod_id')
        );
    }

    public static function getSearchObject($langId = 0, $joinSpecifics = false)
    {
        $langId = FatUtility::int($langId);
        $srch = new SearchBase(static::DB_TBL, 'sp');

        if ($langId) {
            $srch->joinTable(
                static::DB_TBL_LANG,
                'LEFT JOIN',
                'sp_l.' . static::DB_TBL_LANG_PREFIX . 'selprod_id = sp.' . static::tblFld('id') . ' and
			sp_l.' . static::DB_TBL_LANG_PREFIX . 'lang_id = ' . $langId,
                'sp_l'
            );
        }

        if (true === $joinSpecifics) {
            $srch->joinTable(
                SellerProductSpecifics::DB_TBL,
                'LEFT JOIN',
                'sps.' . SellerProductSpecifics::DB_TBL_PREFIX . 'selprod_id = sp.' . static::tblFld('id'),
                'sps'
            );
        }

        return $srch;
    }

    public static function requiredGenDataFields()
    {
        $arr = array(
            ImportexportCommon::VALIDATE_INT => array(
                'selprod_max_download_times',
                'selprod_download_validity_in_days'
            ),
            ImportexportCommon::VALIDATE_POSITIVE_INT => array(
                'selprod_id',
                'selprod_product_id',
                'selprod_stock',
                'selprod_min_order_qty',
                'selprod_condition'
            ),
            ImportexportCommon::VALIDATE_FLOAT => array(
                'selprod_price',
            ),
            ImportexportCommon::VALIDATE_NOT_NULL => array(
                'product_identifier',
                'credential_username',
                'selprod_subtract_stock',
                'selprod_track_inventory',
                'selprod_threshold_stock_level',
                'selprod_title',
                'selprod_url_keyword',
                'selprod_available_from',
            ),
        );

        if (FatApp::getConfig('CONF_PRODUCT_SKU_MANDATORY', FatUtility::VAR_INT, 1)) {
            $physical = array(
                'selprod_sku'
            );
            $arr[ImportexportCommon::VALIDATE_NOT_NULL] = array_merge($arr[ImportexportCommon::VALIDATE_NOT_NULL], $physical);
        }

        return $arr;
    }

    public static function validateGenDataFields($columnIndex, $columnTitle, $columnValue, $langId)
    {
        $requiredFields = static::requiredGenDataFields();
        return ImportexportCommon::validateFields($requiredFields, $columnIndex, $columnTitle, $columnValue, $langId);
    }

    public static function requiredOptionDataFields()
    {
        return array(
            ImportexportCommon::VALIDATE_POSITIVE_INT => array(
                'selprodoption_selprod_id',
                'option_id',
                'optionvalue_id',
            ),
            ImportexportCommon::VALIDATE_NOT_NULL => array(
                'option_identifier',
                'optionvalue_identifier',
            ),
        );
    }

    public static function validateOptionDataFields($columnIndex, $columnTitle, $columnValue, $langId)
    {
        $requiredFields = static::requiredOptionDataFields();
        return ImportexportCommon::validateFields($requiredFields, $columnIndex, $columnTitle, $columnValue, $langId);
    }

    public static function requiredSEODataFields()
    {
        return array(
            ImportexportCommon::VALIDATE_POSITIVE_INT => array(
                'selprod_id',
            ),
        );
    }

    public static function validateSEODataFields($columnIndex, $columnTitle, $columnValue, $langId)
    {
        $requiredFields = static::requiredSEODataFields();
        return ImportexportCommon::validateFields($requiredFields, $columnIndex, $columnTitle, $columnValue, $langId);
    }

    public static function requiredSplPriceFields()
    {
        return array(
            ImportexportCommon::VALIDATE_POSITIVE_INT => array(
                'selprod_id',
            ),
            ImportexportCommon::VALIDATE_NOT_NULL => array(
                'splprice_start_date',
                'splprice_end_date',
                'splprice_price',
            ),
        );
    }

    public static function validateSplPriceFields($columnIndex, $columnTitle, $columnValue, $langId)
    {
        $requiredFields = static::requiredSplPriceFields();
        return ImportexportCommon::validateFields($requiredFields, $columnIndex, $columnTitle, $columnValue, $langId);
    }

    public static function requiredVolDiscountFields()
    {
        return array(
            ImportexportCommon::VALIDATE_POSITIVE_INT => array(
                'selprod_id',
                'voldiscount_min_qty',
            ),
            ImportexportCommon::VALIDATE_NOT_NULL => array(
                'voldiscount_percentage',
            ),
            ImportexportCommon::VALIDATE_FLOAT => array(
                'voldiscount_percentage',
            ),
        );
    }

    public static function validateVolDiscountFields($columnIndex, $columnTitle, $columnValue, $langId)
    {
        $requiredFields = static::requiredVolDiscountFields();
        return ImportexportCommon::validateFields($requiredFields, $columnIndex, $columnTitle, $columnValue, $langId);
    }

    public static function requiredBuyTogetherFields()
    {
        return array(
            ImportexportCommon::VALIDATE_POSITIVE_INT => array(
                'selprod_id',
                'upsell_recommend_sellerproduct_id',
            ),
        );
    }

    public static function validateBuyTogetherFields($columnIndex, $columnTitle, $columnValue, $langId)
    {
        $requiredFields = static::requiredBuyTogetherFields();
        return ImportexportCommon::validateFields($requiredFields, $columnIndex, $columnTitle, $columnValue, $langId);
    }

    public static function requiredRelatedProdFields()
    {
        return array(
            ImportexportCommon::VALIDATE_POSITIVE_INT => array(
                'selprod_id',
                'related_recommend_sellerproduct_id',
            ),
        );
    }

    public static function validateRelatedProdFields($columnIndex, $columnTitle, $columnValue, $langId)
    {
        $requiredFields = static::requiredRelatedProdFields();
        return ImportexportCommon::validateFields($requiredFields, $columnIndex, $columnTitle, $columnValue, $langId);
    }

    public static function requiredProdPolicyFields()
    {
        return array(
            ImportexportCommon::VALIDATE_POSITIVE_INT => array(
                'selprod_id',
                'sppolicy_ppoint_id',
            ),
            ImportexportCommon::VALIDATE_NOT_NULL => array(
                'ppoint_identifier',
            ),
        );
    }

    public static function validateProdPolicyFields($columnIndex, $columnTitle, $columnValue, $langId)
    {
        $requiredFields = static::requiredProdPolicyFields();
        return ImportexportCommon::validateFields($requiredFields, $columnIndex, $columnTitle, $columnValue, $langId);
    }

    public function addUpdateSellerUpsellProducts($selprod_id, $upsellProds = array(), $deletePreviousRecords = true)
    {
        if (!$selprod_id) {
            $this->error = Labels::getLabel('ERR_INVALID_REQUEST', CommonHelper::getLangId());
            return false;
        }

        if (true === $deletePreviousRecords) {
            FatApp::getDb()->deleteRecords(static::DB_TBL_UPSELL_PRODUCTS, array('smt' => static::DB_TBL_UPSELL_PRODUCTS_PREFIX . 'sellerproduct_id = ?', 'vals' => array($selprod_id)));
            if (empty($upsellProds)) {
                return true;
            }
        }

        $record = new TableRecord(static::DB_TBL_UPSELL_PRODUCTS);
        foreach ($upsellProds as $upsell_id) {
            $to_save_arr = array();
            $to_save_arr[static::DB_TBL_UPSELL_PRODUCTS_PREFIX . 'sellerproduct_id'] = $selprod_id;
            $to_save_arr[static::DB_TBL_UPSELL_PRODUCTS_PREFIX . 'recommend_sellerproduct_id'] = $upsell_id;
            $record->assignValues($to_save_arr);
            if (!$record->addNew(array(), $to_save_arr)) {
                $this->error = $record->getError();
                return false;
            }
        }
        return true;
    }

    public function addUpdateSellerRelatedProdcts($selprod_id, $relatedProds = array(), $deletePreviousRecords = true)
    {
        if (!$selprod_id) {
            $this->error = Labels::getLabel('ERR_INVALID_REQUEST', CommonHelper::getLangId());
            return false;
        }

        if (true === $deletePreviousRecords) {
            FatApp::getDb()->deleteRecords(static::DB_TBL_RELATED_PRODUCTS, array('smt' => static::DB_TBL_RELATED_PRODUCTS_PREFIX . 'sellerproduct_id = ?', 'vals' => array($selprod_id)));
            if (empty($relatedProds)) {
                return true;
            }
        }

        $record = new TableRecord(static::DB_TBL_RELATED_PRODUCTS);
        foreach ($relatedProds as $relprod_id) {
            $to_save_arr = array();
            $to_save_arr[static::DB_TBL_RELATED_PRODUCTS_PREFIX . 'sellerproduct_id'] = $selprod_id;
            $to_save_arr[static::DB_TBL_RELATED_PRODUCTS_PREFIX . 'recommend_sellerproduct_id'] = $relprod_id;
            $record->assignValues($to_save_arr);
            if (!$record->addNew(array(), $to_save_arr)) {
                $this->error = $record->getError();
                return false;
            }
        }
        return true;
    }

    public static function searchUpsellProducts($lang_id, $attr = [], $forFrontend = true)
    {
        $splPriceForDate = FatDate::nowInTimezone(FatApp::getConfig('CONF_TIMEZONE'), 'Y-m-d');
        $srch = new SearchBase(static::DB_TBL_UPSELL_PRODUCTS);
        $srch->joinTable(static::DB_TBL, 'INNER JOIN', static::DB_TBL_PREFIX . 'id = ' . static::DB_TBL_UPSELL_PRODUCTS_PREFIX . 'recommend_sellerproduct_id');
        $srch->joinTable(static::DB_TBL . '_lang', 'LEFT JOIN', 'slang.' . static::DB_TBL_LANG_PREFIX . 'selprod_id = ' . static::DB_TBL_UPSELL_PRODUCTS_PREFIX . 'recommend_sellerproduct_id AND ' . static::DB_TBL_LANG_PREFIX . 'lang_id = ' . $lang_id, 'slang');
        $srch->joinTable(Product::DB_TBL, 'LEFT JOIN', Product::DB_TBL_PREFIX . 'id = ' . static::DB_TBL_PREFIX . 'product_id');
        $srch->joinTable(Product::DB_TBL . '_lang', 'LEFT JOIN', 'lang.productlang_product_id = ' . static::DB_TBL_LANG_PREFIX . 'selprod_id AND productlang_lang_id = ' . $lang_id, 'lang');

        if (true === $forFrontend) {
            $srch->joinTable(Shop::DB_TBL, 'INNER JOIN', 'selprod_user_id = shop.shop_user_id', 'shop');
            $srch->joinTable(Product::DB_TBL_PRODUCT_TO_CATEGORY, 'LEFT OUTER JOIN', 'ptc.ptc_product_id = product_id', 'ptc');
            $srch->joinTable(ProductCategory::DB_TBL, 'LEFT OUTER JOIN', 'c.prodcat_id = ptc.ptc_prodcat_id', 'c');

            $srch->joinTable(
                SellerProduct::DB_TBL_SELLER_PROD_SPCL_PRICE,
                'LEFT OUTER JOIN',
                'm.splprice_selprod_id = selprod_id AND \'' . $splPriceForDate . '\' BETWEEN m.splprice_start_date AND m.splprice_end_date',
                'm'
            );

            $srch->joinTable(
                SellerProduct::DB_TBL_SELLER_PROD_SPCL_PRICE,
                'LEFT OUTER JOIN',
                's.splprice_selprod_id = selprod_id AND s.splprice_price < m.splprice_price
                AND \'' . $splPriceForDate . '\' BETWEEN s.splprice_start_date AND s.splprice_end_date',
                's'
            );

            $srch->addCondition('c.prodcat_active', '=', 'mysql_func_' . applicationConstants::ACTIVE, 'AND', true);
            $srch->addCondition('c.prodcat_deleted', '=', 'mysql_func_' . applicationConstants::NO, 'AND', true);

            if (FatApp::getConfig("CONF_PRODUCT_BRAND_MANDATORY", FatUtility::VAR_INT, 1)) {
                $srch->joinTable(Brand::DB_TBL, 'INNER JOIN', 'product_brand_id = brand.brand_id and brand.brand_active = ' . applicationConstants::YES . ' and brand.brand_deleted = ' . applicationConstants::NO, 'brand');
            } else {
                $srch->joinTable(Brand::DB_TBL, 'LEFT OUTER JOIN', 'product_brand_id = brand.brand_id', 'brand');
                $srch->addDirectCondition("(CASE WHEN product_brand_id > 0 THEN (brand.brand_active = " . applicationConstants::YES . " AND brand.brand_deleted = " . applicationConstants::NO . ") ELSE 1=1 END)");
            }

            if (empty($attr)) {
                $attr = array(
                    'upsell_sellerproduct_id',
                    'upsell_recommend_sellerproduct_id',
                    'selprod_id',
                    'product_id',
                    'IFNULL(selprod_title  ,IFNULL(product_name, product_identifier)) as selprod_title',
                    'selprod_price',
                    'selprod_stock',
                    'IFNULL(product_identifier ,product_name) as product_name',
                    'product_identifier',
                    'selprod_product_id',
                    'CASE WHEN m.splprice_selprod_id IS NULL THEN 0 ELSE 1 END AS special_price_found',
                    'IFNULL(m.splprice_price, selprod_price) AS theprice',
                    'selprod_min_order_qty',
                    'selprod_cart_type',
                    'product_updated_on',
                    'selprod_hide_price',
                    'shop_rfq_enabled'
                );
            }
        } else {
            if (empty($attr)) {
                $attr = array(
                    'upsell_sellerproduct_id',
                    'upsell_recommend_sellerproduct_id',
                    'selprod_id',
                    'product_id',
                    'IFNULL(selprod_title  ,IFNULL(product_name, product_identifier)) as selprod_title',
                    'IFNULL(product_identifier ,product_name) as product_name',
                    'product_identifier',
                    'selprod_product_id',
                    'product_updated_on'
                );
            }
        }

        if (!empty($attr)) {
            if (is_string($attr)) {
                $srch->addFld($attr);
            } else {
                $srch->addMultipleFields($attr);
            }
        }

        $srch->addCondition(Product::DB_TBL_PREFIX . 'active', '=', 'mysql_func_' . applicationConstants::YES, 'AND', true);
        $srch->addCondition('selprod_active', '=', 'mysql_func_' . applicationConstants::ACTIVE, 'AND', true);
        $srch->addCondition('selprod_deleted', '=', 'mysql_func_' . applicationConstants::NO, 'AND', true);
        $srch->addCondition('product_active', '=', 'mysql_func_' . applicationConstants::ACTIVE, 'AND', true);
        $srch->addCondition('product_deleted', '=', 'mysql_func_' . applicationConstants::NO, 'AND', true);
        $srch->addOrder('selprod_id', 'DESC');
        return $srch;
    }

    public function getUpsellProducts($sellProdId, $lang_id, $userId = 0)
    {
        $sellProdId = FatUtility::convertToType($sellProdId, FatUtility::VAR_INT);
        $lang_id = FatUtility::convertToType($lang_id, FatUtility::VAR_INT);
        if (!$sellProdId) {
            trigger_error(Labels::getLabel("ERR_ARGUMENTS_NOT_SPECIFIED.", CommonHelper::getLangId()), E_USER_ERROR);
            return false;
        }

        $srch = static::searchUpsellProducts($lang_id);
        $srch->addCondition(static::DB_TBL_UPSELL_PRODUCTS_PREFIX . 'sellerproduct_id', '=', 'mysql_func_' . $sellProdId, 'AND', true);
        if (true === MOBILE_APP_API_CALL) {
            if (FatApp::getConfig('CONF_ADD_FAVORITES_TO_WISHLIST', FatUtility::VAR_INT, 1) == applicationConstants::NO) {
                $this->joinFavouriteProducts($srch, $userId);
                $srch->addFld('IFNULL(ufp_id, 0) as ufp_id');
            } else {
                $this->joinUserWishListProducts($srch, $userId);
                $srch->addFld('IFNULL(uwlp.uwlp_selprod_id, 0) as is_in_any_wishlist');
            }
        }
        $srch->addGroupBy('selprod_id');
        $srch->doNotCalculateRecords();
        $rs = $srch->getResultSet();
        $db = FatApp::getDb();
        $data = array();
        if ($row = $db->fetchAll($rs)) {
            return $row;
        }
        return $data;
    }

    public static function getAttributesById($recordId, $attr = null, $fetchOptions = true, $joinSpecifics = false)
    {
        $recordId = FatUtility::int($recordId);

        $db = FatApp::getDb();

        $srch = new SearchBase(static::DB_TBL, 'sp');
        $srch->doNotCalculateRecords();
        $srch->setPageSize(1);
        $srch->addCondition(static::tblFld('id'), '=', 'mysql_func_' . $recordId, 'AND', true);

        if (true === $joinSpecifics) {
            $srch->joinTable(
                SellerProductSpecifics::DB_TBL,
                'LEFT OUTER JOIN',
                'ps.' . SellerProductSpecifics::DB_TBL_PREFIX . 'selprod_id = sp.' . static::tblFld('id'),
                'ps'
            );
        }

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

        /* get seller product options[ */
        if ($fetchOptions) {
            $op = static::getSellerProductOptions($recordId, false);
            if (is_array($op) && count($op)) {
                foreach ($op as $o) {
                    $row['selprodoption_optionvalue_id'][$o['selprodoption_option_id']] = array($o['selprodoption_option_id'] => $o['selprodoption_optionvalue_id']);
                }
            }
        }
        /* ] */

        if (is_string($attr)) {
            return $row[$attr];
        }
        return $row;
    }

    public function addUpdateSellerProductOptions($selprod_id, $data)
    {
        $selprod_id = FatUtility::int($selprod_id);
        if (!$selprod_id) {
            $this->error = Labels::getLabel('ERR_INVALID_REQUEST', CommonHelper::getLangId());
            return false;
        }
        $db = FatApp::getDb();
        $db->deleteRecords(static::DB_TBL_SELLER_PROD_OPTIONS, array('smt' => static::DB_TBL_SELLER_PROD_OPTIONS_PREFIX . 'selprod_id = ?', 'vals' => array($selprod_id)));
        if (is_array($data) && count($data)) {
            $record = new TableRecord(static::DB_TBL_SELLER_PROD_OPTIONS);
            foreach ($data as $option_id => $optionvalue_id) {
                $data_to_save = array(
                    static::DB_TBL_SELLER_PROD_OPTIONS_PREFIX . 'selprod_id' => $selprod_id,
                    static::DB_TBL_SELLER_PROD_OPTIONS_PREFIX . 'option_id' => $option_id,
                    static::DB_TBL_SELLER_PROD_OPTIONS_PREFIX . 'optionvalue_id' => $optionvalue_id
                );
                $record->assignValues($data_to_save);
                if (!$record->addNew()) {
                    $this->error = $record->getError();
                    return false;
                }
            }
        }
        return true;
    }

    public static function getSellerProductOptions($selprod_id, $withAllJoins = true, $lang_id = 0, $option_id = 0)
    {
        $selprod_id = FatUtility::int($selprod_id);
        $lang_id = FatUtility::int($lang_id);
        $option_id = FatUtility::int($option_id);
        if (!$selprod_id) {
            trigger_error(Labels::getLabel('ERR_INVALID_ARGUMENTS', CommonHelper::getLangId()), E_USER_ERROR);
        }
        $srch = new SearchBase(static::DB_TBL_SELLER_PROD_OPTIONS, 'spo');

        if ($option_id) {
            $srch->addCondition(static::DB_TBL_SELLER_PROD_OPTIONS_PREFIX . 'option_id', '=', 'mysql_func_' . $option_id, 'AND', true);
        }

        if ($withAllJoins) {
            if (!$lang_id) {
                trigger_error(Labels::getLabel('ERR_INVALID_ARGUMENTS', CommonHelper::getLangId()), E_USER_ERROR);
            }

            $srch->joinTable(OptionValue::DB_TBL, 'INNER JOIN', 'spo.selprodoption_optionvalue_id = ov.optionvalue_id', 'ov');
            $srch->joinTable(OptionValue::DB_TBL . '_lang', 'LEFT OUTER JOIN', 'ov_lang.optionvaluelang_optionvalue_id = ov.optionvalue_id AND ov_lang.optionvaluelang_lang_id = ' . $lang_id, 'ov_lang');

            $srch->joinTable(Option::DB_TBL, 'INNER JOIN', 'o.option_id = ov.optionvalue_option_id', 'o');
            $srch->joinTable(Option::DB_TBL . '_lang', 'LEFT OUTER JOIN', 'o.option_id = o_lang.optionlang_option_id AND o_lang.optionlang_lang_id = ' . $lang_id, 'o_lang');
            $srch->addMultipleFields(array('selprodoption_selprod_id', 'o.option_id', 'ov.optionvalue_id', 'IFNULL(option_name, option_identifier) as option_name', 'IFNULL(optionvalue_name, optionvalue_identifier) as optionvalue_name'));
        }

        if (is_array($selprod_id)) {
            $srch->addDirectCondition(static::DB_TBL_SELLER_PROD_OPTIONS_PREFIX . 'selprod_id IN (' . implode(',', $selprod_id) . ')');
        } else {
            $srch->addCondition(static::DB_TBL_SELLER_PROD_OPTIONS_PREFIX . 'selprod_id', '=', 'mysql_func_' . $selprod_id, 'AND', true);
        }

        $srch->doNotCalculateRecords();
        $srch->doNotLimitRecords();
        $rs = $srch->getResultSet();
        return FatApp::getDb()->fetchAll($rs);
    }

    public static function getSellerProductOptionsBySelProdCode($selprod_code = '', $langId = 0, $displayInFilterOnly = false)
    {
        if ($selprod_code == '') {
            return array();
        }
        $opValArr = explode("_", $selprod_code);

        /* removing product_id from the begining of the array[ */
        $opValArr = array_reverse($opValArr);
        array_pop($opValArr);
        $opValArr = array_reverse($opValArr);
        if (empty($opValArr)) {
            return array();
        }
        /* ] */

        $srch = new SearchBase(OptionValue::DB_TBL, 'ov');
        $srch->joinTable(Option::DB_TBL, 'INNER JOIN', 'o.option_id = ov.optionvalue_option_id', 'o');

        if ($langId) {
            $srch->joinTable(OptionValue::DB_TBL . '_lang', 'LEFT OUTER JOIN', 'ov_lang.optionvaluelang_optionvalue_id = ov.optionvalue_id AND ov_lang.optionvaluelang_lang_id = ' . $langId, 'ov_lang');

            $srch->joinTable(Option::DB_TBL . '_lang', 'LEFT OUTER JOIN', 'o.option_id = o_lang.optionlang_option_id AND o_lang.optionlang_lang_id = ' . $langId, 'o_lang');
        }

        $srch->addCondition('optionvalue_id', 'IN', $opValArr);
        if ($displayInFilterOnly) {
            $srch->addCondition('option_display_in_filter', '=', 'mysql_func_' . applicationConstants::YES, 'AND', true);
        }
        $srch->doNotCalculateRecords();
        $srch->doNotLimitRecords();
        $srch->addMultipleFields(['option_id', 'COALESCE(option_name,option_identifier) as option_name', 'option_identifier', 'optionvalue_id', 'COALESCE(optionvalue_name,optionvalue_identifier) as optionvalue_name', 'optionvalue_color_code', 'option_is_color', 'optionvalue_identifier']);
        $rs = $srch->getResultSet();
        return FatApp::getDb()->fetchAll($rs, 'optionvalue_id');
    }

    public static function getSellerProductSpecialPrices($selprod_id)
    {
        $selprod_id = FatUtility::int($selprod_id);
        $srch = new SearchBase(static::DB_TBL_SELLER_PROD_SPCL_PRICE);
        $srch->addCondition('splprice_selprod_id', '=', 'mysql_func_' . $selprod_id, 'AND', true);
        $srch->doNotCalculateRecords();
        $srch->doNotLimitRecords();
        $srch->addOrder('splprice_id');
        $db = FatApp::getDb();
        $rs = $srch->getResultSet();
        return $db->fetchAll($rs);
    }

    public static function getSellerProductSpecialPriceById($splprice_id)
    {
        $splprice_id = FatUtility::int($splprice_id);
        $srch = new SearchBase(static::DB_TBL_SELLER_PROD_SPCL_PRICE, 'prodSp');
        $srch->joinTable(static::DB_TBL, 'INNER JOIN', 'prodSp.splprice_selprod_id = slrPrd.selprod_id', 'slrPrd');
        $srch->addCondition('splprice_id', '=', 'mysql_func_' . $splprice_id, 'AND', true);
        $srch->addMultipleFields(array('prodSp.*', 'slrPrd.selprod_id', 'slrPrd.selprod_user_id'));
        $srch->doNotCalculateRecords();
        return FatApp::getDb()->fetch($srch->getResultSet());
    }

    public function deleteSellerProductSpecialPrice($splprice_id, $splprice_selprod_id, $userId = 0)
    {
        $splprice_id = FatUtility::int($splprice_id);
        $splprice_selprod_id = FatUtility::int($splprice_selprod_id);
        if (!$splprice_id || !$splprice_selprod_id) {
            trigger_error(Labels::getLabel('ERR_INVALID_ARGUMENTS', CommonHelper::getLangId()), E_USER_ERROR);
        }
        if (0 < $userId) {
            $selProdUserId = SellerProduct::getAttributesById($splprice_selprod_id, 'selprod_user_id', false);
            if ($selProdUserId != $userId) {
                $this->error = Labels::getLabel('ERR_INVALID_REQUEST', CommonHelper::getLangId());
                return false;
            }
        }
        $db = FatApp::getDb();
        $smt = 'splprice_id = ? AND splprice_selprod_id = ? ';
        $smtValues = array($splprice_id, $splprice_selprod_id);
        if (!$db->deleteRecords(static::DB_TBL_SELLER_PROD_SPCL_PRICE, array('smt' => $smt, 'vals' => $smtValues))) {
            $this->error = $db->getError();
            return false;
        }
        return true;
    }

    public function addUpdateSellerProductSpecialPrice($data, $return = false)
    {
        $db = FatApp::getDb();
        if (!$db->insertFromArray(static::DB_TBL_SELLER_PROD_SPCL_PRICE, $data, false, array(), $data)) {
            $this->error = $db->getError();
            return false;
        }
        if (true === $return) {
            if (!empty($data['splprice_id'])) {
                return $data['splprice_id'];
            }
            return FatApp::getDb()->getInsertId();
        }
        return true;
    }

    public static function getProductCommission($selprod_id)
    {
        $selprod_id = FatUtility::int($selprod_id);
        if (!$selprod_id) {
            trigger_error(Labels::getLabel('ERR_INVALID_ARGUMENTS!', CommonHelper::getLangId()), E_USER_ERROR);
        }
        //return 10;
        $sellerProductRow = static::getAttributesById($selprod_id, array('selprod_id', 'selprod_product_id', 'selprod_user_id'));
        $product_id = $sellerProductRow['selprod_product_id'];
        $selprod_user_id = $sellerProductRow['selprod_user_id'];

        $prodObj = new Product();
        $productCategories = $prodObj->getProductCategories($sellerProductRow['selprod_product_id']);
        $catIds = array();
        if ($productCategories) {
            foreach ($productCategories as $catId) {
                $catIds[] = $catId['prodcat_id'];
            }
        }

        /* to fetch the single row from the commission settings table, if single product is connected with multiple categories then will fetch the category according to price min or max, for now we have added min price i.e sort order of price is asc. [ */
        /* $srch = new SearchBase( Commission::DB_TBL );
          $srch->doNotCalculateRecords();
          $srch->addMultipleFields(array('commsetting_prodcat_id'));
          $srch->addCondition( 'commsetting_prodcat_id', 'IN', $catIds );
          $srch->addOrder('commsetting_fees', 'ASC');
          $srch->setPageSize(1);
          $rs = $srch->getResultSet();
          $row = FatApp::getDb()->fetch($rs);
          if( !$row ){
          $category_id = 0;
          } else {
          $category_id = $row['commsetting_prodcat_id'];
          } */
        /* ] */

        $db = FatApp::getDb();
        $sql = "SELECT commsetting_fees,commsetting_product_id,commsetting_user_id,commsetting_prodcat_id,
			CASE
				WHEN commsetting_product_id = '" . $product_id . "' AND commsetting_user_id = '" . $selprod_user_id . "' AND commsetting_prodcat_id IN (" . implode(",", $catIds) . ") THEN 10
  				WHEN commsetting_product_id = '" . $product_id . "' AND commsetting_user_id = '" . $selprod_user_id . "' AND commsetting_prodcat_id = '0' THEN 9
				WHEN commsetting_product_id = '" . $product_id . "' AND commsetting_user_id = 0 AND commsetting_prodcat_id IN (" . implode(",", $catIds) . ") THEN 8
				WHEN commsetting_product_id = '" . $product_id . "' AND commsetting_user_id = '0' AND commsetting_prodcat_id = '0' THEN 7

				WHEN commsetting_product_id = 0 AND commsetting_user_id = '" . $selprod_user_id . "' AND commsetting_prodcat_id IN (" . implode(",", $catIds) . ") THEN 6
				WHEN commsetting_product_id = 0 AND commsetting_user_id = '" . $selprod_user_id . "' AND commsetting_prodcat_id = 0 THEN 5

				WHEN commsetting_product_id = 0 AND commsetting_user_id = 0 AND commsetting_prodcat_id IN (" . implode(",", $catIds) . ") THEN 4
                              
				WHEN (commsetting_product_id = '0' AND commsetting_user_id = '0' AND commsetting_prodcat_id = '0') THEN 1
			END
       		as matches FROM " . Commission::DB_TBL . " order by matches desc, commsetting_fees desc  limit 0,1";
        $rs = $db->query($sql);
        if ($row = $db->fetch($rs)) {
            return $row['commsetting_fees'];
        }
    }

    public function getProductsToGroup($prodgroup_id, $lang_id = 0)
    {
        $prodgroup_id = FatUtility::int($prodgroup_id);
        $lang_id = FatUtility::int($lang_id);
        $now = FatDate::nowInTimezone(FatApp::getConfig('CONF_TIMEZONE'), 'Y-m-d');
        $forDate = $now;

        if ($prodgroup_id <= 0) {
            trigger_error(Labels::getLabel('ERR_INVALID_ARGUMENTS', CommonHelper::getLangId()), E_USER_ERROR);
        }

        $srch = new SearchBase(ProductGroup::DB_PRODUCT_TO_GROUP, 'ptg');
        $srch->joinTable(static::DB_TBL, 'INNER JOIN', 'ptg.' . ProductGroup::DB_PRODUCT_TO_GROUP_PREFIX . 'selprod_id = sp.selprod_id', 'sp');
        $srch->joinTable(Product::DB_TBL, 'INNER JOIN', 'sp.selprod_product_id = p.product_id', 'p');
        $srch->joinTable(ProductGroup::DB_TBL, 'INNER JOIN', 'ptg.' . ProductGroup::DB_PRODUCT_TO_GROUP_PREFIX . 'prodgroup_id = pg.prodgroup_id', 'pg');
        $srch->joinTable(
            SellerProduct::DB_TBL_SELLER_PROD_SPCL_PRICE,
            'LEFT OUTER JOIN',
            'splprice_selprod_id = selprod_id AND \'' . $forDate . '\' BETWEEN splprice_start_date AND splprice_end_date'
        );

        $srch->addCondition(ProductGroup::DB_PRODUCT_TO_GROUP_PREFIX . 'prodgroup_id', '=', 'mysql_func_' . $prodgroup_id, 'AND', true);
        $srch->addCondition('p.product_active', '=', 'mysql_func_' . applicationConstants::ACTIVE, 'AND', true);
        $srch->addCondition('p.product_approved', '=', 'mysql_func_' . Product::APPROVED, 'AND', true);
        $srch->addCondition('sp.selprod_active', '=', 'mysql_func_' . applicationConstants::ACTIVE, 'AND', true);
        $srch->addCondition('sp.selprod_available_from', '<=', $now);

        if ($lang_id > 0) {
            $srch->joinTable(static::DB_TBL_LANG, 'LEFT OUTER JOIN', 'sp.selprod_id = sp_l.selprodlang_selprod_id AND selprodlang_lang_id = ' . $lang_id, 'sp_l');
            $srch->addFld('selprod_title');

            $srch->joinTable(Product::DB_TBL_LANG, 'LEFT OUTER JOIN', 'p.product_id = p_l.productlang_product_id AND productlang_lang_id = ' . $lang_id, 'p_l');
            $srch->addFld('IFNULL(product_name, product_identifier) as product_name');
        }

        /* if( $selprod_id > 0 ){
          $srch->addCondition( ProductGroup::DB_PRODUCT_TO_GROUP_PREFIX . 'selprod_id', '=', $selprod_id );
          } */

        $srch->addMultipleFields(array('selprod_id', 'product_id', 'IFNULL(splprice_price, selprod_price) AS theprice', 'IF(selprod_stock > 0, 1, 0) AS in_stock', 'selprod_sold_count', 'CASE WHEN splprice_selprod_id IS NULL THEN 0 ELSE 1 END AS special_price_found', 'ptg.ptg_is_main_product'));
        $srch->addOrder('ptg_is_main_product', 'DESC');
        $srch->addOrder('product_name');
        $srch->doNotCalculateRecords();
        $srch->doNotLimitRecords();
        $rs = $srch->getResultSet();
        $products = FatApp::getDb()->fetchAll($rs);
        return $products;
    }

    public function getGroupsToProduct($lang_id = 0)
    {
        if ($this->mainTableRecordId < 1) {
            return array();
        }

        $lang_id = FatUtility::int($lang_id);

        $srch = new SearchBase(ProductGroup::DB_PRODUCT_TO_GROUP, 'ptg');
        $srch->joinTable(static::DB_TBL, 'INNER JOIN', 'ptg.' . ProductGroup::DB_PRODUCT_TO_GROUP_PREFIX . 'selprod_id = sp.selprod_id', 'sp');
        $srch->joinTable(Product::DB_TBL, 'INNER JOIN', 'sp.selprod_product_id = p.product_id', 'p');
        $srch->joinTable(ProductGroup::DB_TBL, 'INNER JOIN', 'ptg.' . ProductGroup::DB_PRODUCT_TO_GROUP_PREFIX . 'prodgroup_id = pg.prodgroup_id', 'pg');

        $srch->addCondition(ProductGroup::DB_PRODUCT_TO_GROUP_PREFIX . 'selprod_id', '=', 'mysql_func_' . $this->mainTableRecordId, 'AND', true);
        $srch->addCondition('pg.prodgroup_active', '=', 'mysql_func_' . applicationConstants::ACTIVE, 'AND', true);
        $srch->addCondition('p.product_active', '=', 'mysql_func_' . applicationConstants::ACTIVE, 'AND', true);
        $srch->addCondition('p.product_approved', '=', 'mysql_func_' . Product::APPROVED, 'AND', true);
        $srch->addCondition('sp.selprod_active', '=', 'mysql_func_' . applicationConstants::ACTIVE, 'AND', true);

        $srch->doNotCalculateRecords();
        $srch->doNotLimitRecords();

        if ($lang_id > 0) {
            $srch->joinTable(ProductGroup::DB_TBL_LANG, 'LEFT OUTER JOIN', 'pg.prodgroup_id = pg_l.prodgrouplang_prodgroup_id AND pg_l.prodgrouplang_lang_id = ' . $lang_id, 'pg_l');
            $srch->addFld('IFNULL(prodgroup_name, prodgroup_identifier) as prodgroup_name');
        }
        $srch->addMultipleFields(array('selprod_id', 'ptg_prodgroup_id', 'pg.prodgroup_price'));
        $srch->addOrder('pg.prodgroup_price');
        $rs = $srch->getResultSet();
        return FatApp::getDb()->fetchAll($rs);
    }

    public function addPolicyPointToSelProd($data)
    {
        $record = new TableRecord(self::DB_TBL_SELLER_PROD_POLICY);
        $record->assignValues($data);

        if (!$record->addNew(array(), $data)) {
            $this->error = $record->getError();
            return false;
        }
        return true;
    }

    public static function searchRelatedProducts($lang_id = 0, $criteria = array())
    {
        $srch = new SearchBase(static::DB_TBL_RELATED_PRODUCTS);
        $srch->joinTable(static::DB_TBL, 'INNER JOIN', static::DB_TBL_PREFIX . 'id = ' . static::DB_TBL_RELATED_PRODUCTS_PREFIX . 'recommend_sellerproduct_id');
        $srch->joinTable(static::DB_TBL . '_lang', 'LEFT JOIN', 'slang.' . static::DB_TBL_LANG_PREFIX . 'selprod_id = ' . static::DB_TBL_RELATED_PRODUCTS_PREFIX . 'recommend_sellerproduct_id AND ' . static::DB_TBL_LANG_PREFIX . 'lang_id = ' . $lang_id, 'slang');
        $srch->joinTable(Product::DB_TBL, 'LEFT JOIN', Product::DB_TBL_PREFIX . 'id = ' . static::DB_TBL_PREFIX . 'product_id');
        $srch->joinTable(Product::DB_TBL . '_lang', 'LEFT JOIN', 'lang.productlang_product_id = ' . static::DB_TBL_LANG_PREFIX . 'selprod_id AND productlang_lang_id = ' . $lang_id, 'lang');
        $srch->joinTable(Shop::DB_TBL, 'INNER JOIN', 'selprod_user_id = shop.shop_user_id', 'shop');
        if (!empty($criteria)) {
            if (is_string($criteria)) {
                $srch->addFld($criteria);
            } else {
                $srch->addMultipleFields($criteria);
            }
        } else {
            $srch->addMultipleFields(array('related_sellerproduct_id', 'selprod_id', 'IFNULL(product_identifier ,product_name) as product_name', 'IFNULL(selprod_title, IFNULL(product_name, product_identifier)) as selprod_title', 'product_identifier', 'selprod_price', 'product_updated_on', 'selprod_cart_type', 'selprod_hide_price', 'shop_rfq_enabled'));
        }
        return $srch;
    }

    public function getRelatedProducts($lang_id = 0, $sellProdId = 0, $criteria = array())
    {
        $lang_id = FatUtility::convertToType($lang_id, FatUtility::VAR_INT);
        $sellProdId = FatUtility::convertToType($sellProdId, FatUtility::VAR_INT);

        $srch = static::searchRelatedProducts($lang_id, $criteria);
        if ($sellProdId > 0) {
            $srch->addCondition(static::DB_TBL_RELATED_PRODUCTS_PREFIX . 'sellerproduct_id', '=', 'mysql_func_' . $sellProdId, 'AND', true);
        }
        $srch->doNotCalculateRecords();
        $rs = $srch->getResultSet();
        $db = FatApp::getDb();
        if ($sellProdId > 0) {
            return $db->fetchAll($rs, 'selprod_id');
        } else {
            return $db->fetchAll($rs);
        }
    }

    public function deleteSellerProduct($selprodId)
    {
        if (!$selprodId) {
            $this->error = Labels::getLabel('ERR_INVALID_REQUEST', CommonHelper::getLangId());
            return false;
        }

        $sellerProdObj = new SellerProduct($selprodId);
        if (!$sellerProdObj->deleteRecord(true)) {
            $this->error = $sellerProdObj->getError();
            return false;
        }

        $where = array('smt' => 'selprodoption_selprod_id = ?', 'vals' => array($selprodId));
        if (!FatApp::getDb()->deleteRecords(SellerProduct::DB_TBL_SELLER_PROD_OPTIONS, $where)) {
            $this->error = FatApp::getDb()->getError();
            return false;
        }

        return true;
    }

    public static function getSelprodPolicies($selprod_id, $policy_type, $langId, $limit = null, $active = true, $deleted = false)
    {
        $selprod_id = FatUtility::int($selprod_id);
        $policy_type = FatUtility::int($policy_type);
        $limit = FatUtility::int($limit);
        $srch = new SearchBase(static::DB_TBL_SELLER_PROD_POLICY);
        $srch->joinTable(PolicyPoint::DB_TBL, 'left outer join', 'sppolicy_ppoint_id = ppoint_id', 'pp');
        $srch->joinTable(
            PolicyPoint::DB_TBL_LANG,
            'LEFT OUTER JOIN',
            'pp_l.ppointlang_ppoint_id = pp.ppoint_id
			AND ppointlang_lang_id = ' . $langId,
            'pp_l'
        );
        $srch->addCondition('pp.ppoint_type', '=', 'mysql_func_' . $policy_type, 'AND', true);
        $srch->addCondition('sppolicy_selprod_id', '=', 'mysql_func_' . $selprod_id, 'AND', true);
        $srch->addMultipleFields(array('ppoint_id', 'ifnull(ppoint_title,ppoint_identifier) ppoint_title'));
        $srch->doNotCalculateRecords();
        $srch->addOrder('pp.ppoint_display_order');
        if ($deleted == false) {
            $srch->addCondition('pp.ppoint_deleted', '=', 'mysql_func_' . applicationConstants::NO, 'AND', true);
        }

        if ($active == true) {
            $srch->addCondition('pp.ppoint_active', '=', 'mysql_func_' . applicationConstants::ACTIVE, 'AND', true);
        }

        if ($limit) {
            $srch->setPageSize($limit);
        }
        return FatApp::getDb()->fetch($srch->getResultSet());
    }

    public static function getProductDisplayTitle($selProdId, int $langId, bool $toHtml = false)
    {
        $prodSrch = new ProductSearch($langId, null, null, false, false);
        $prodSrch->joinSellerProducts(0, '', array(), false, false);
        if (is_array($selProdId) && 0 < count($selProdId)) {
            $prodSrch->addCondition('selprod_id', 'IN', $selProdId);
        } else {
            $prodSrch->addCondition('selprod_id', '=', 'mysql_func_' . FatUtility::int($selProdId), 'AND', true);
        }
        $prodSrch->addMultipleFields(array('selprod_id', 'product_id', 'product_identifier', 'IFNULL(product_name, product_identifier) as product_name', 'IFNULL(selprod_title, IFNULL(product_name, product_identifier)) as selprod_title'));
        $prodSrch->addGroupBy('selprod_id');
        $prodSrch->doNotCalculateRecords();
        $products = FatApp::getDb()->fetchAll($prodSrch->getResultSet(), 'selprod_id');

        $productTitle = SellerProduct::getProductsOptionsString($products, $langId, $toHtml);
        if (false == $productTitle) {
            return $productTitle;
        }

        if (!is_array($selProdId) && array_key_exists($selProdId, $productTitle)) {
            return $productTitle[$selProdId];
        }

        if (is_array($selProdId)) {
            return $productTitle;
        }

        return false;
    }

    public static function getProductsOptionsString(array $products, int $langId, bool $toHtml = false)
    {
        if (empty($products) || empty($langId)) {
            return false;
        }
        $optionsStringArr = array();
        foreach ($products as $selProdId => $product) {
            $variantStr = (!empty($product['selprod_title'])) ? $product['selprod_title'] : $product['product_name'];

            $options = static::getSellerProductOptions($selProdId, true, $langId);
            if (is_array($options) && count($options)) {
                $variantStr .= (true === $toHtml) ? '<br/>' : ' - ';
                $counter = 1;
                foreach ($options as $op) {
                    $variantStr .= (true === $toHtml) ? $op['option_name'] . ': ' . $op['optionvalue_name'] : $op['optionvalue_name'];
                    if ($counter != count($options)) {
                        $variantStr .= (true === $toHtml) ? '<br/>' : ' - ';
                    }
                    $counter++;
                }
            }
            $optionsStringArr[$selProdId] = $variantStr;
        }
        return $optionsStringArr;
    }

    public function getVolumeDiscounts()
    {
        if ($this->mainTableRecordId < 1) {
            return array();
        }

        $srch = new SellerProductVolumeDiscountSearch();
        $srch->doNotCalculateRecords();
        $srch->addMultipleFields(array('voldiscount_min_qty', 'voldiscount_percentage'));
        $srch->addCondition('voldiscount_selprod_id', '=', 'mysql_func_' . $this->mainTableRecordId, 'AND', true);
        $srch->addOrder('voldiscount_min_qty', 'ASC');
        $rs = $srch->getResultSet();
        return FatApp::getDb()->fetchAll($rs);
    }

    private function rewriteUrl($keyword, $type = 'product')
    {
        if ($this->mainTableRecordId < 1) {
            return false;
        }

        $originalUrl = $this->getRewriteOriginalUrl($type);
        $seoUrl = $this->sanitizeSeoUrl($keyword, $type);

        $customUrl = UrlRewrite::getValidSeoUrl($seoUrl, $originalUrl);
        return UrlRewrite::update($originalUrl, $customUrl);
    }

    private function getRewriteOriginalUrl($type = 'product')
    {
        if ($this->mainTableRecordId < 1) {
            return false;
        }

        switch (strtolower($type)) {
            case 'reviews':
                $originalUrl = Product::PRODUCT_REVIEWS_ORGINAL_URL . $this->mainTableRecordId;
                break;
            case 'moresellers':
                $originalUrl = Product::PRODUCT_MORE_SELLERS_ORGINAL_URL . $this->mainTableRecordId;
                break;
            default:
                $originalUrl = Product::PRODUCT_VIEW_ORGINAL_URL . $this->mainTableRecordId;
                break;
        }
        return $originalUrl;
    }

    public function sanitizeSeoUrl($keyword, $type = 'product')
    {
        $seoUrl = CommonHelper::seoUrl($keyword);
        switch (strtolower($type)) {
            case 'reviews':
                $seoUrl = preg_replace('/-reviews$/', '', $seoUrl);
                $seoUrl .= '-reviews';
                break;
            case 'moresellers':
                $seoUrl = preg_replace('/-sellers$/', '', $seoUrl);
                $seoUrl .= '-sellers';
                break;
            default:
                break;
        }
        return $seoUrl;
    }

    public function rewriteUrlProduct($keyword)
    {
        return $this->rewriteUrl($keyword, 'product');
    }

    public function getRewriteProductOriginalUrl()
    {
        return $this->getRewriteOriginalUrl('product');
    }

    public function rewriteUrlReviews($keyword)
    {
        return $this->rewriteUrl($keyword, 'reviews');
    }

    public function rewriteUrlMoreSellers($keyword)
    {
        return $this->rewriteUrl($keyword, 'moresellers');
    }

    public static function getActiveCount($userId, $selprodId = 0): int
    {
        $selprodId = FatUtility::int($selprodId);
        $userId = FatUtility::int($userId);

        $srch = static::getSearchObject();
        $srch->joinTable(Product::DB_TBL, 'INNER JOIN', 'p.product_id = sp.selprod_product_id and p.product_deleted = ' . applicationConstants::NO . ' and p.product_active = ' . applicationConstants::YES, 'p');

        $srch->addCondition('selprod_deleted', '=', 'mysql_func_' . applicationConstants::NO, 'AND', true);
        $srch->addCondition('selprod_user_id', '=', 'mysql_func_' . $userId, 'AND', true);
        $srch->addCondition('selprod_active', '=', 'mysql_func_' . applicationConstants::YES, 'AND', true);
        if ($selprodId) {
            $srch->addCondition('selprod_id', '!=', 'mysql_func_' . $selprodId, 'AND', true);
        }

        $srch->addMultipleFields(array('selprod_id'));
        $srch->getResultSet();
        // $records = $db->fetchAll($rs);
        return (int) $srch->recordCount();
    }

    public function joinUserWishListProducts($srch, $user_id)
    {
        $user_id = FatUtility::int($user_id);
        $wislistPSrchObj = new UserWishListProductSearch();
        $wislistPSrchObj->joinWishLists();
        $wislistPSrchObj->doNotCalculateRecords();
        $wislistPSrchObj->doNotLimitRecords();
        $wislistPSrchObj->addCondition('uwlist_user_id', '=', 'mysql_func_' . $user_id, 'AND', true);
        $wislistPSrchObj->addMultipleFields(array('uwlp_selprod_id', 'uwlp_uwlist_id'));
        $wishListSubQuery = $wislistPSrchObj->getQuery();
        $srch->joinTable('(' . $wishListSubQuery . ')', 'LEFT OUTER JOIN', 'uwlp.uwlp_selprod_id = selprod_id', 'uwlp');
    }

    public function joinFavouriteProducts($srch, $user_id)
    {
        $srch->joinTable(Product::DB_TBL_PRODUCT_FAVORITE, 'LEFT OUTER JOIN', 'ufp.ufp_selprod_id = selprod_id and ufp.ufp_user_id = ' . $user_id, 'ufp');
    }

    public static function specialPriceForm($langId)
    {
        $frm = new Form('frmSellerProductSpecialPrice');
        $frm->addHiddenField('', 'splprice_selprod_id');
        $frm->addHiddenField('', 'splprice_id');
        $prodName = $frm->addSelectBox(Labels::getLabel('FRM_PRODUCT', $langId), 'product_name', [], '', array('class' => 'selProd--js', 'placeholder' => Labels::getLabel('FRM_SELECT_PRODUCT', $langId)));
        $prodName->requirements()->setRequired();

        $fld = $frm->addFloatField(Labels::getLabel('FRM_SPECIAL_PRICE', $langId) . CommonHelper::concatCurrencySymbolWithAmtLbl(), 'splprice_price');
        $fld->requirements()->setPositive();
        $fld = $frm->addDateField(Labels::getLabel('FRM_PRICE_START_DATE', $langId), 'splprice_start_date', '', array('readonly' => 'readonly'));
        $fld->requirements()->setRequired();

        $fld = $frm->addDateField(Labels::getLabel('FRM_PRICE_END_DATE', $langId), 'splprice_end_date', '', array('readonly' => 'readonly'));
        $fld->requirements()->setRequired();
        $fld->requirements()->setCompareWith('splprice_start_date', 'ge', Labels::getLabel('FRM_PRICE_START_DATE', $langId));

        return $frm;
    }

    public static function volumeDiscountForm($langId)
    {
        $frm = new Form('frmSellerProductSpecialPrice');

        $frm->addHiddenField('', 'voldiscount_selprod_id', 0);
        $frm->addHiddenField('', 'voldiscount_id', 0);
        $prodName = $frm->addSelectBox(Labels::getLabel('FRM_PRODUCT', $langId), 'product_name', [], '', array('class' => 'selProd--js', 'placeholder' => Labels::getLabel('FRM_SELECT_PRODUCT', $langId)));
        $prodName->requirements()->setRequired();
        $frm->addIntegerField(Labels::getLabel("FRM_MINIMUM_QUANTITY", $langId), 'voldiscount_min_qty');
        $discountFld = $frm->addFloatField(Labels::getLabel("FRM_DISCOUNT_IN_(%)", $langId), "voldiscount_percentage");
        $discountFld->requirements()->setPositive();
        return $frm;
    }

    public static function searchSpecialPriceProductsObj($langId, $selProdId = 0, $keyword = '', $userId = 0)
    {
        $srch = static::getSearchObject($langId);
        $srch->joinTable(Product::DB_TBL, 'INNER JOIN', 'p.product_id = sp.selprod_product_id', 'p');
        $srch->joinTable(User::DB_TBL, 'INNER JOIN', 'u.user_id = sp.selprod_user_id', 'u');
        $srch->joinTable(User::DB_TBL_CRED, 'LEFT OUTER JOIN', 'tuc.credential_user_id = sp.selprod_user_id', 'tuc');
        $srch->joinTable(static::DB_TBL_SELLER_PROD_SPCL_PRICE, 'INNER JOIN', 'spp.splprice_selprod_id = sp.selprod_id', 'spp');
        $srch->joinTable(Product::DB_TBL_LANG, 'LEFT OUTER JOIN', 'p.product_id = p_l.productlang_product_id AND p_l.productlang_lang_id = ' . $langId, 'p_l');
        $srch->joinTable(Shop::DB_TBL, 'INNER JOIN', 'shop.shop_user_id = sp.selprod_user_id', 'shop');
        $srch->joinTable(Shop::DB_TBL_LANG, 'LEFT JOIN', 'shopLang.shoplang_shop_id = shop.shop_id AND shopLang.shoplang_lang_id = ' . $langId, 'shopLang');

        if (0 < $selProdId) {
            $srch->addCondition('selprod_id', '=', 'mysql_func_' . $selProdId, 'AND', true);
        }

        if (!empty($keyword)) {
            $cnd = $srch->addCondition('product_name', 'like', "%$keyword%");
            $cnd->attachCondition('selprod_title', 'LIKE', '%' . $keyword . '%', 'OR');
        }

        if (0 < $userId) {
            $srch->addCondition('selprod_user_id', '=', $userId);
        }

        $srch->addCondition('selprod_active', '=', 'mysql_func_' . applicationConstants::ACTIVE, 'AND', true);
        $srch->addCondition('selprod_deleted', '=', 'mysql_func_' . applicationConstants::NO, 'AND', true);
        $srch->addCondition('product_active', '=', 'mysql_func_' . applicationConstants::ACTIVE, 'AND', true);
        $srch->addCondition('product_deleted', '=', 'mysql_func_' . applicationConstants::NO, 'AND', true);
        return $srch;
    }

    public static function searchVolumeDiscountProducts($langId, $selProdId = 0, $keyword = '', $userId = 0)
    {

        $srch = static::getSearchObject($langId);
        $srch->joinTable(Product::DB_TBL, 'INNER JOIN', 'p.product_id = sp.selprod_product_id', 'p');
        $srch->joinTable(User::DB_TBL, 'LEFT OUTER JOIN', 'u.user_id = sp.selprod_user_id', 'u');
        $srch->joinTable(User::DB_TBL_CRED, 'LEFT OUTER JOIN', 'tuc.credential_user_id = sp.selprod_user_id', 'tuc');
        $srch->joinTable(SellerProductVolumeDiscount::DB_TBL, 'INNER JOIN', 'vd.voldiscount_selprod_id = sp.selprod_id', 'vd');
        $srch->joinTable(Product::DB_TBL_LANG, 'LEFT OUTER JOIN', 'p.product_id = p_l.productlang_product_id AND p_l.productlang_lang_id = ' . $langId, 'p_l');
        $srch->joinTable(Shop::DB_TBL, 'INNER JOIN', 'shop.shop_user_id = sp.selprod_user_id', 'shop');
        $srch->joinTable(Shop::DB_TBL_LANG, 'LEFT JOIN', 'shopLang.shoplang_shop_id = shop.shop_id AND shopLang.shoplang_lang_id = ' . $langId, 'shopLang');

        if (0 < $selProdId) {
            $srch->addCondition('selprod_id', '=', 'mysql_func_' . $selProdId, 'AND', true);
        }


        if ($keyword != '') {
            $cnd = $srch->addCondition('product_name', 'like', "%$keyword%");
            $cnd->attachCondition('selprod_title', 'LIKE', '%' . $keyword . '%', 'OR');
        }

        if (0 < $userId) {
            $srch->addCondition('selprod_user_id', '=', $userId);
        }

        $srch->addCondition('selprod_active', '=', 'mysql_func_' . applicationConstants::ACTIVE, 'AND', true);
        $srch->addCondition('selprod_deleted', '=', 'mysql_func_' . applicationConstants::NO, 'AND', true);
        return $srch;
    }

    public static function getSelProdDataById($selProdId, $langId = 0, $joinProduct = false, $attr = [])
    {
        $selProdId = FatUtility::int($selProdId);
        $srch = static::getSearchObject($langId);
        $srch->addCondition('selprod_id', '=', 'mysql_func_' . $selProdId, 'AND', true);
        $srch->doNotCalculateRecords();
        $srch->setPageSize(1);

        if (0 < count($attr)) {
            $srch->addMultipleFields($attr);
        }

        if ($joinProduct) {
            $srch->joinTable(Product::DB_TBL, 'INNER JOIN', 'p.product_id = sp.selprod_product_id', 'p');
            if (0 < $langId) {
                $srch->joinTable(Product::DB_TBL_LANG, 'LEFT OUTER JOIN', 'p.product_id = p_l.productlang_product_id AND p_l.productlang_lang_id = ' . $langId, 'p_l');
            }
        }
        return FatApp::getDb()->fetch($srch->getResultSet());
    }

    public static function searchSellerProducts($langId, $userId, $keyword = '')
    {
        $userId = FatUtility::int($userId);
        $srch = SellerProduct::getSearchObject($langId);
        $srch->joinTable(Product::DB_TBL, 'INNER JOIN', 'p.product_id = sp.selprod_product_id and p.product_deleted = ' . applicationConstants::NO . ' and p.product_active = ' . applicationConstants::YES, 'p');
        $srch->joinTable(Product::DB_TBL_LANG, 'LEFT OUTER JOIN', 'p.product_id = p_l.productlang_product_id AND p_l.productlang_lang_id = ' . $langId, 'p_l');
        if ($keyword) {
            $cnd = $srch->addCondition('product_name', 'like', "%$keyword%");
            $cnd->attachCondition('selprod_title', 'LIKE', "%$keyword%");
            $cnd->attachCondition('product_identifier', 'LIKE', "%$keyword%");
        }
        $srch->addCondition('selprod_deleted', '=', 'mysql_func_' . applicationConstants::NO, 'AND', true);
        $srch->addCondition('selprod_user_id', '=', 'mysql_func_' . $userId, 'AND', true);
        return $srch;
    }

    public function saveMetaData()
    {
        if ($this->mainTableRecordId < 1) {
            $this->error = Labels::getLabel('ERR_INVALID_REQUEST', CommonHelper::getLangId());
            return false;
        }
        $selprod_id = $this->mainTableRecordId;
        $metaData = array();
        $tabsArr = MetaTag::getTabsArr();
        $metaType = MetaTag::META_GROUP_PRODUCT_DETAIL;

        if ($metaType == '' || !isset($tabsArr[$metaType])) {
            $this->error = Labels::getLabel('ERR_INVALID_REQUEST', CommonHelper::getLangId());
            return false;
        }

        if (true == MetaTag::isExists($tabsArr[$metaType]['controller'], $tabsArr[$metaType]['action'], $selprod_id, 0)) {
            return true;
        }

        $metaData['meta_controller'] = $tabsArr[$metaType]['controller'];
        $metaData['meta_action'] = $tabsArr[$metaType]['action'];
        $metaData['meta_record_id'] = $selprod_id;
        $metaData['meta_subrecord_id'] = 0;

        $meta = new MetaTag();
        $meta->assignValues($metaData);

        if (!$meta->save()) {
            $this->error = $meta->getError();
            return false;
        }

        $metaId = $meta->getMainTableRecordId();
        $languages = Language::getAllNames();
        foreach ($languages as $langId => $langName) {
            $selProdMeta = array(
                'metalang_lang_id' => $langId,
                'metalang_meta_id' => $metaId,
                'meta_title' => static::getProductDisplayTitle($selprod_id, $langId),
            );

            $metaObj = new MetaTag($metaId);

            if (!$metaObj->updateLangData($langId, $selProdMeta)) {
                $this->error = $metaObj->getError();
                return false;
            }
        }
        return true;
    }

    public static function getCatelogFromProductId($productId)
    {
        $productId = FatUtility::int($productId);
        $srch = SellerProduct::getSearchObject();
        $srch->joinTable(Product::DB_TBL, 'INNER JOIN', 'p.product_id = sp.selprod_product_id', 'p');
        $srch->addCondition('selprod_deleted', '=', 'mysql_func_' . applicationConstants::NO, 'AND', true);
        $srch->addCondition('selprod_product_id', '=', 'mysql_func_' . $productId, 'AND', true);
        $srch->addFld('selprod_id');
        $srch->doNotCalculateRecords();
        $rs = $srch->getResultSet();
        $record = FatApp::getDb()->fetch($rs);
        if (!empty($record)) {
            return $record;
        }
        return [];
    }

    public static function prodShipByseller($productId)
    {
        $productId = FatUtility::int($productId);
        $loggedUserId = UserAuthentication::getLoggedUserId();
        $srch = new ProductSearch();
        $srch->joinProductShippedBySeller($loggedUserId);
        $srch->addCondition('psbs_user_id', '=', 'mysql_func_' . $loggedUserId, 'AND', true);
        $srch->addCondition('product_id', '=', 'mysql_func_' . $productId, 'AND', true);
        $srch->addFld('psbs_user_id');
        $srch->doNotCalculateRecords();
        $rs = $srch->getResultSet();
        return FatApp::getDb()->fetch($rs);
    }

    /**
     * setSellerProdFulfillmentType - Need to enhance later.
     *
     * @param  int $fulfillmentType
     * @return int
     */
    public static function setSellerProdFulfillmentType(int $fulfillmentType): int
    {
        return $fulfillmentType;
    }

    public static function getProdIdByPlugin(int $pluginId, int $pluginSelProdId): int
    {
        $srch = new SearchBase(static::DB_SELLER_PROD_TO_PLUGIN_SELLER_PROD);
        $srch->addCondition(static::DB_SELLER_PROD_TO_PLUGIN_SELLER_PROD_PREFIX . 'plugin_id', '=', 'mysql_func_' . $pluginId, 'AND', true);
        $srch->addCondition(static::DB_SELLER_PROD_TO_PLUGIN_SELLER_PROD_PREFIX . 'plugin_selprod_id', '=', 'mysql_func_' . $pluginSelProdId, 'AND', true);
        $srch->addFld('spps_selprod_id');
        $srch->doNotCalculateRecords();
        $rs = $srch->getResultSet();
        $records = FatApp::getDb()->fetch($rs);
        if (!$records) {
            return 0;
        }
        return $records['spps_selprod_id'];
    }

    public static function getFormattedOptions(int $selProdId, int $langId)
    {
        $productId = SellerProduct::getAttributesById($selProdId, 'selprod_product_id', false);

        $options = SellerProduct::getSellerProductOptions($selProdId, false);
        $productSelectedOptionValues = array();
        if (is_array($options) && 0 < count($options)) {
            foreach ($options as $op) {
                $productSelectedOptionValues[$op['selprodoption_option_id']] = $op['selprodoption_optionvalue_id'];
            }
        }

        $prodSrchObj = new ProductSearch($langId);

        $optionSrchObj = clone $prodSrchObj;
        $optionSrchObj->setDefinedCriteria(0, 0, ['doNotJoinSellers' => true]);
        $optionSrchObj->joinTable(SellerProduct::DB_TBL_SELLER_PROD_OPTIONS, 'LEFT OUTER JOIN', 'selprod_id = tspo.selprodoption_selprod_id', 'tspo');
        $optionSrchObj->joinTable(OptionValue::DB_TBL, 'LEFT OUTER JOIN', 'tspo.selprodoption_optionvalue_id = opval.optionvalue_id', 'opval');
        $optionSrchObj->joinTable(Option::DB_TBL, 'LEFT OUTER JOIN', 'opval.optionvalue_option_id = op.option_id', 'op');

        $optionSrch = clone $optionSrchObj;
        $optionSrch->joinTable(Option::DB_TBL . '_lang', 'LEFT OUTER JOIN', 'op.option_id = op_l.optionlang_option_id AND op_l.optionlang_lang_id = ' . $langId, 'op_l');
        $optionSrch->addMultipleFields(array('option_id', 'option_is_color', 'COALESCE(option_name,option_identifier) as option_name'));
        $optionSrch->addCondition('option_id', '!=', 'NULL');
        $optionSrch->addCondition('selprodoption_selprod_id', '=', 'mysql_func_' . $selProdId, 'AND', true);
        $optionSrch->addGroupBy('option_id');
        $optionSrch->doNotCalculateRecords();
        $optionRs = $optionSrch->getResultSet();
        $optionRows = FatApp::getDb()->fetchAll($optionRs);

        if ($optionRows) {
            foreach ($optionRows as &$option) {
                $optionValueSrch = clone $optionSrchObj;
                $optionValueSrch->joinTable(OptionValue::DB_TBL . '_lang', 'LEFT OUTER JOIN', 'opval.optionvalue_id = opval_l.optionvaluelang_optionvalue_id AND opval_l.optionvaluelang_lang_id = ' . $langId, 'opval_l');
                $optionValueSrch->addCondition('product_id', '=', 'mysql_func_' . $productId, 'AND', true);
                $optionValueSrch->addCondition('option_id', '=', $option['option_id']);
                $optionValueSrch->addMultipleFields(array('COALESCE(product_name, product_identifier) as product_name', 'selprod_id', 'selprod_user_id', 'selprod_code', 'option_id', 'COALESCE(optionvalue_name,optionvalue_identifier) as optionvalue_name ', 'theprice', 'optionvalue_id', 'optionvalue_color_code'));
                $optionValueSrch->addGroupBy('optionvalue_id');
                $optionValueSrch->doNotCalculateRecords();
                $optionValueRs = $optionValueSrch->getResultSet();
                $optionValueRows = FatApp::getDb()->fetchAll($optionValueRs);

                foreach ($optionValueRows as $index => $opVal) {
                    $optionValueRows[$index]['isAvailable'] = 1;
                    if (is_array($productSelectedOptionValues) && !in_array($opVal['optionvalue_id'], $productSelectedOptionValues)) {
                        $optionUrl = Product::generateProductOptionsUrl($selProdId, $productSelectedOptionValues, $option['option_id'], $opVal['optionvalue_id'], $productId);
                        $optionUrlArr = explode("::", $optionUrl);
                        if (is_array($optionUrlArr) && count($optionUrlArr) == 2) {
                            $optionValueRows[$index]['isAvailable'] = 0;
                        }
                    }
                }

                $option['values'] = $optionValueRows;
            }
        }

        return $optionRows;
    }

    /**
     * rateObj
     *
     * @return object
     */
    public static function rateObj(): object
    {
        $avgRatingSrch = new SelProdReviewSearch();
        $avgRatingSrch->joinSelProdRating();
        $avgRatingSrch->doNotCalculateRecords();
        $avgRatingSrch->doNotLimitRecords();
        $avgRatingSrch->addGroupBy('spr.spreview_selprod_id');
        $avgRatingSrch->addCondition('spr.spreview_status', '=', 'mysql_func_' . SelProdReview::STATUS_APPROVED, 'AND', true);
        $avgRatingSrch->addMultipleFields(["ROUND(AVG(sprating_rating),2) as rating"]);
        return $avgRatingSrch;
    }

    /**
     * getRating
     *
     * @param  int $recordId
     * @return float
     */
    public static function getRating(int $recordId): float
    {
        $avgRatingSrch = self::rateObj();
        $avgRatingSrch->addCondition('spreview_selprod_id', '=', 'mysql_func_' . $recordId, 'AND', true);
        $avgRatingSrch->addCondition('ratingtype_type', '=', RatingType::TYPE_PRODUCT);
        $avgRatingData = (array) FatApp::getDb()->fetch($avgRatingSrch->getResultSet());
        if (empty($avgRatingData)) {
            return 0;
        }
        return (float) $avgRatingData['rating'];
    }

    /**
     * getProdRating
     *
     * @param  mixed $recordId
     * @return float
     */
    public static function getProdRating(int $recordId): float
    {
        $avgRatingSrch = self::rateObj();
        $avgRatingSrch->addCondition('spreview_product_id', '=', 'mysql_func_' . $recordId, 'AND', true);
        $avgRatingSrch->addCondition('ratingtype_type', '=', 'mysql_func_' . RatingType::TYPE_PRODUCT, 'AND', true);
        $avgRatingData = (array) FatApp::getDb()->fetch($avgRatingSrch->getResultSet());
        if (empty($avgRatingData)) {
            return 0;
        }
        return (float) $avgRatingData['rating'];
    }

    /**
     * getRating
     *
     * @param  int $recordId
     * @return float
     */
    public static function getShopRating(int $recordId): float
    {
        $avgRatingSrch = self::rateObj();
        $avgRatingSrch->addCondition('spreview_seller_user_id', '=', 'mysql_func_' . $recordId, 'AND', true);
        $avgRatingSrch->addCondition('ratingtype_type', '=', 'mysql_func_' . RatingType::TYPE_SHOP, true);
        $avgRatingData = (array) FatApp::getDb()->fetch($avgRatingSrch->getResultSet());
        if (empty($avgRatingData)) {
            return 0;
        }
        return (float) $avgRatingData['rating'];
    }

    /**
     * $optionId int|array
     */
    public static function isOptionLinked($optionId, int $productId = 0)
    {
        /* Get Linked Products [ */
        $srch = SellerProduct::getSearchObject();
        $srch->joinTable(SellerProduct::DB_TBL_SELLER_PROD_OPTIONS, 'LEFT OUTER JOIN', 'selprod_id = selprodoption_selprod_id', 'tspo');
        if (0 < $productId) {
            $srch->addCondition('selprod_product_id', '=', 'mysql_func_' . $productId, 'AND', true);
        }
        if (is_array($optionId)) {
            $srch->addCondition('tspo.selprodoption_option_id', 'IN', $optionId);
        } else {
            $srch->addCondition('tspo.selprodoption_option_id', '=', 'mysql_func_' . FatUtility::int($optionId), 'AND', true);
        }
        $srch->addCondition('selprod_deleted', '=', 'mysql_func_' . applicationConstants::NO, 'AND', true);
        $srch->addFld(array('selprod_id'));
        $srch->doNotCalculateRecords();
        $srch->setPageSize(1);
        $rs = $srch->getResultSet();
        $row = FatApp::getDb()->fetch($rs);
        return $row != false ? true : false;
    }

    public static function isOptionValueLinked(int $optionId, int $optionValueId, int $productId)
    {
        /* Get Linked Products [ */
        $srch = SellerProduct::getSearchObject();
        $srch->joinTable(SellerProduct::DB_TBL_SELLER_PROD_OPTIONS, 'LEFT OUTER JOIN', 'selprod_id = selprodoption_selprod_id', 'tspo');
        $srch->addCondition('selprod_product_id', '=', 'mysql_func_' . $productId, 'AND', true);
        $srch->addCondition('tspo.selprodoption_option_id', '=', 'mysql_func_' . $optionId, 'AND', true);
        $srch->addCondition('tspo.selprodoption_optionvalue_id', '=', 'mysql_func_' . $optionValueId, 'AND', true);
        $srch->addCondition('selprod_deleted', '=', 'mysql_func_' . applicationConstants::NO, 'AND', true);
        $srch->addFld(array('selprod_id'));
        $srch->doNotCalculateRecords();
        $srch->setPageSize(1);
        $rs = $srch->getResultSet();
        $row = FatApp::getDb()->fetch($rs);
        return $row != false ? true : false;
    }

    public static function getRelatedProduct($userId, $langId, $relatedProdIds = [])
    {
        if (empty($relatedProdIds)) {
            return [];
        }
        $srch = SellerProduct::searchRelatedProducts($langId);
        $srch->addCondition('selprod_user_id', '=', $userId);
        $srch->addDirectCondition("related_sellerproduct_id IN (" . implode(',', $relatedProdIds) . ")");
        $srch->doNotCalculateRecords();
        $srch->doNotLimitRecords();
        $arrListing = [];
        $relatedProds = FatApp::getDb()->fetchAll($srch->getResultSet());
        foreach ($relatedProds as $key => $relatedProd) {
            $arrListing[$relatedProd['related_sellerproduct_id']][$key] = $relatedProd;
        }
        return $arrListing;
    }

    public static function getBuyTogetherProduct($userId, $langId, $relatedProdIds, $selProdId)
    {
        if (empty($relatedProdIds)) {
            return [];
        }
        $srch = SellerProduct::searchUpsellProducts($langId);
        $srch->addCondition('selprod_user_id', '=', $userId);
        $srch->addDirectCondition("upsell_sellerproduct_id IN (" . implode(',', $relatedProdIds) . ")");
        $srch->doNotCalculateRecords();
        $srch->doNotLimitRecords();
        $srch->addGroupBy('selprod_id');
        $srch->addGroupBy('upsell_sellerproduct_id');
        if ($selProdId) {
            $srch->addFld('if(upsell_sellerproduct_id = ' . $selProdId . ', 1 , 0) as priority');
            $srch->addOrder('priority', 'DESC');
        }
        $arrListing = [];
        $relatedProds = FatApp::getDb()->fetchAll($srch->getResultSet());
        foreach ($relatedProds as $key => $relatedProd) {
            $arrListing[$relatedProd['upsell_sellerproduct_id']][$key] = $relatedProd;
        }
        return $arrListing;
    }

    public static function getProdMissingInfo(int $selProdId, $langId): array
    {
        $validationArr = [
            'product_active' => ['title' => Labels::getLabel('LBL_PRODUCT_ACTIVE', $langId), 'currentStatus' => '', 'valid' => false],
            'product_approved' => ['title' => Labels::getLabel('LBL_PRODUCT_APPROVED', $langId), 'currentStatus' => '', 'valid' => false],
            'product_deleted' => ['title' => Labels::getLabel('LBL_PRODUCT_DELETED', $langId), 'currentStatus' => '', 'valid' => false],
            'prodcat_active' => ['title' => Labels::getLabel('LBL_PRODUCT_CATEGORY_ACTIVE', $langId), 'currentStatus' => '', 'valid' => false],
            'prodcat_deleted' => ['title' => Labels::getLabel('LBL_PRODUCT_CATEGORY_DELETED', $langId), 'currentStatus' => '', 'valid' => false],
            'prodcat_status' => ['title' => Labels::getLabel('LBL_PRODUCT_CATEGORY_STATUS', $langId), 'currentStatus' => '', 'valid' => false],
            'taxcat_active' => ['title' => Labels::getLabel('LBL_TAX_CATEGORY_ACTIVE', $langId), 'currentStatus' => 0, 'valid' => false],
            'taxcat_deleted' => ['title' => Labels::getLabel('LBL_TAX_CATEGORY_DELETED', $langId), 'currentStatus' => 1, 'valid' => false],
            'brand_active' => ['title' => Labels::getLabel('LBL_BRAND_ACTIVE', $langId), 'currentStatus' => '', 'valid' => false],
            'brand_deleted' => ['title' => Labels::getLabel('LBL_BRAND_DELETED', $langId), 'currentStatus' => '', 'valid' => false],
            'user_deleted' => ['title' => Labels::getLabel('LBL_SELLER_DELETED', $langId), 'currentStatus' => '', 'valid' => false],
            'credential_active' => ['title' => Labels::getLabel('LBL_SELLER_ACTIVE', $langId), 'currentStatus' => '', 'valid' => false],
            'credential_verified' => ['title' => Labels::getLabel('LBL_SELLER_VERIFIED', $langId), 'currentStatus' => '', 'valid' => false],
            'shop_active' => ['title' => Labels::getLabel('LBL_SHOP_ACTIVE', $langId), 'currentStatus' => '', 'valid' => false],
            'shop_supplier_display_status' => ['title' => Labels::getLabel('LBL_SHOP_DISPLAY_STATUS', $langId), 'currentStatus' => '', 'valid' => false],
            'country_active' => ['title' => Labels::getLabel('LBL_SHOP_COUNTRY_ACTIVE', $langId), 'currentStatus' => '', 'valid' => false],
            'state_active' => ['title' => Labels::getLabel('LBL_SHOP_STATE_ACTIVE', $langId), 'currentStatus' => '', 'valid' => false],
        ];

        if (FatApp::getConfig('CONF_ENABLE_SELLER_SUBSCRIPTION_MODULE', FatUtility::VAR_INT, 0)) {
            $validationArr['subscription'] = ['title' => Labels::getLabel('LBL_SELLER_SUBSCRIPTION_ACTIVE', $langId), 'currentStatus' => '', 'valid' => false];
        }

        $selProd = SellerProduct::getAttributesById($selProdId, ['selprod_deleted', 'selprod_product_id', 'selprod_user_id', 'selprod_code']);

        if ($selProd) {
            if (FatApp::getConfig('CONF_ENABLE_SELLER_SUBSCRIPTION_MODULE', FatUtility::VAR_INT, 0)) {
                $validationArr['subscription']['valid']  = false;
                $validationArr['subscription']['currentStatus']  = 0;

                $currentActivePlan = OrderSubscription::getUserCurrentActivePlanDetails($langId, $selProd['selprod_user_id'], array(OrderSubscription::DB_TBL_PREFIX . 'till_date', OrderSubscription::DB_TBL_PREFIX . 'price', OrderSubscription::DB_TBL_PREFIX . 'type'));
                if ($currentActivePlan) {
                    $validationArr['subscription']['valid'] = FatDate::diff(date("Y-m-d"), $currentActivePlan[OrderSubscription::DB_TBL_PREFIX . 'till_date']) > 0;
                    $validationArr['subscription']['currentStatus'] = $validationArr['subscription']['valid'] ? 1 : 0;
                }
            }
            $product = Product::getAttributesById($selProd['selprod_product_id'], ['product_approved', 'product_active', 'product_deleted', 'product_brand_id']);
            if ($product) {
                $validationArr['product_active']['valid'] = $product['product_active'] === applicationConstants::YES;
                $validationArr['product_active']['currentStatus'] = $product['product_active'];
                $validationArr['product_approved']['valid'] = $product['product_approved'] === applicationConstants::YES;
                $validationArr['product_approved']['currentStatus'] = $product['product_approved'];
                $validationArr['product_deleted']['valid'] = $product['product_deleted'] === applicationConstants::NO;
                $validationArr['product_deleted']['currentStatus'] = $product['product_deleted'];

                $prodToCatObj = new SearchBase(Product::DB_TBL_PRODUCT_TO_CATEGORY, 'ptc');
                $prodToCatObj->addFld('ptc_prodcat_id');
                $prodToCatObj->addCondition('ptc_product_id', '=', $selProd['selprod_product_id']);
                $prodToCatObj->doNotCalculateRecords();
                $prodToCatObj->setPageSize(1);
                $prodToCat = FatApp::getDb()->fetch($prodToCatObj->getResultSet());
                if ($prodToCat) {
                    $prodCat = ProductCategory::getAttributesById($prodToCat['ptc_prodcat_id'], ['prodcat_active', 'prodcat_deleted', 'prodcat_status']);
                    if ($prodCat) {
                        $validationArr['prodcat_active']['valid'] = $prodCat['prodcat_active'] === applicationConstants::YES;
                        $validationArr['prodcat_active']['currentStatus'] = $prodCat['prodcat_active'];
                        $validationArr['prodcat_deleted']['valid'] = $prodCat['prodcat_deleted'] === applicationConstants::NO;
                        $validationArr['prodcat_deleted']['currentStatus'] = $prodCat['prodcat_deleted'];
                        $validationArr['prodcat_status']['valid'] = $prodCat['prodcat_status'] === applicationConstants::YES;
                        $validationArr['prodcat_status']['currentStatus'] = $prodCat['prodcat_status'];
                    }
                }

                $prodToTaxObj = new SearchBase(Tax::DB_TBL_PRODUCT_TO_TAX, 'ptt');
                $prodToTaxObj->addFld('ptt_taxcat_id');
                $prodToTaxObj->addCondition('ptt_product_id', '=', $selProd['selprod_product_id']);
                $prodToTaxObj->doNotCalculateRecords();
                $prodToTaxObj->setPageSize(1);
                $prodToTax = FatApp::getDb()->fetch($prodToTaxObj->getResultSet());
                if ($prodToTax) {
                    $tax = Tax::getAttributesById($prodToTax['ptt_taxcat_id'], ['taxcat_active', 'taxcat_deleted', 'taxcat_plugin_id']);
                    if ($tax && Tax::getActivatedServiceId() == $tax['taxcat_plugin_id']) {
                        $validationArr['taxcat_active']['valid'] = $tax['taxcat_active'] === applicationConstants::YES;
                        $validationArr['taxcat_active']['currentStatus'] = $tax['taxcat_active'];
                        $validationArr['taxcat_deleted']['valid'] = $tax['taxcat_deleted'] === applicationConstants::NO;
                        $validationArr['taxcat_deleted']['currentStatus'] = $tax['taxcat_deleted'];
                    }
                }

                if (1 > FatApp::getConfig('CONF_PRODUCT_BRAND_MANDATORY', FatUtility::VAR_INT, 1)) {
                    unset($validationArr['brand_active'],  $validationArr['brand_deleted']);
                } else {
                    $brand = Brand::getAttributesById($product['product_brand_id'], ['brand_active', 'brand_deleted']);
                    if ($brand) {
                        $validationArr['brand_active']['valid'] = $brand['brand_active'] === applicationConstants::YES;
                        $validationArr['brand_active']['currentStatus'] = $brand['brand_active'];
                        $validationArr['brand_deleted']['valid'] = $brand['brand_deleted'] === applicationConstants::NO;
                        $validationArr['brand_deleted']['currentStatus'] = $brand['brand_deleted'];
                    }
                }

                $userObj = User::getSearchObject(true, 0, false);
                $userObj->addMultipleFields(['user_deleted', 'credential_active', 'credential_verified']);
                $userObj->addCondition('user_id', '=', $selProd['selprod_user_id']);
                $userObj->doNotCalculateRecords();
                $userObj->setPageSize(1);
                $seller = FatApp::getDb()->fetch($userObj->getResultSet());
                if ($seller) {
                    $validationArr['user_deleted']['valid'] = $seller['user_deleted'] === applicationConstants::NO;
                    $validationArr['user_deleted']['currentStatus'] = $seller['user_deleted'];
                    $validationArr['credential_active']['valid'] = $seller['credential_active'] === applicationConstants::YES;
                    $validationArr['credential_active']['currentStatus'] = $seller['credential_active'];
                    $validationArr['credential_verified']['valid'] = $seller['credential_verified'] === applicationConstants::YES;
                    $validationArr['credential_verified']['currentStatus'] = $seller['credential_verified'];

                    $shop = Shop::getAttributesByUserId($selProd['selprod_user_id'], ['shop_active', 'shop_supplier_display_status', 'shop_country_id', 'shop_state_id']);
                    if ($shop) {
                        $validationArr['shop_active']['valid'] = $shop['shop_active'] === applicationConstants::YES;
                        $validationArr['shop_active']['currentStatus'] = $shop['shop_active'];
                        $validationArr['shop_supplier_display_status']['valid'] = $shop['shop_supplier_display_status'] === applicationConstants::YES;
                        $validationArr['shop_supplier_display_status']['currentStatus'] = $shop['shop_supplier_display_status'];
                        $shopCountry = Countries::getAttributesById($shop['shop_country_id'], ['country_active']);
                        if ($shopCountry) {
                            $validationArr['country_active']['valid'] = $shopCountry['country_active'] === applicationConstants::YES;
                            $validationArr['country_active']['currentStatus'] = $shopCountry['country_active'];
                        }

                        $shopState = States::getAttributesById($shop['shop_state_id'], ['state_active']);
                        if ($shopState) {
                            $validationArr['state_active']['valid'] = $shopState['state_active'] === applicationConstants::YES;
                            $validationArr['state_active']['currentStatus'] = $shopState['state_active'];
                        }
                    }
                }
            }
            if(empty($selProd['selprod_code'])) {
                $validationArr['selprod_code'] = ['title' => Labels::getLabel('LBL_OPTION_BINDED', $langId), 'currentStatus' => '', 'valid' => false];
                $validationArr['selprod_code']['code'] = 'selprod_code';
                $validationArr['selprod_code']['valid']  = false;
                $validationArr['selprod_code']['currentStatus']  = 0;
                $validationArr['selprod_code']['valid'] = empty($selProd['selprod_code']) ? false : true;
                $validationArr['selprod_code']['currentStatus']  = empty($selProd['selprod_code']) ? false : true;
            }
        }

        return $validationArr;
    }

    public static function getSellerProductIdByCode(string $selprod_code)
    {
        $srch = SellerProduct::getSearchObject();
        $srch->addCondition('selprod_code', 'LIKE', $selprod_code);
        $srch->addFld('selprod_id');
        $srch->doNotCalculateRecords();
        $srch->doNotLimitRecords();
        $row = FatApp::getDb()->fetch($srch->getResultSet());
        return is_array($row) && isset($row['selprod_id']) ? $row['selprod_id'] : 0;
    }

    public static function getSelprodIdByProductId(int $productId): int
    {
        if (1 > $productId || 1 > FatApp::getConfig('CONF_WITHOUT_PROD_VARIANTS', FatUtility::VAR_INT, 0)) {
            return 0;
        }
        $srch = new SearchBase(static::DB_TBL, 'sp');
        $srch->addCondition('selprod_product_id', '=', $productId);
        $srch->doNotCalculateRecords();
        $srch->addFld('selprod_id');
        $result = FatApp::getDb()->fetch($srch->getResultSet());
        return $result['selprod_id'] ?? 0;
    }

    public static function getCartType(int $siteLangId = 0): array
    {
        $siteLangId = 0 < $siteLangId ? $siteLangId : CommonHelper::getLangId();
        $arr = [
            self::CART_TYPE_BOTH => Labels::getLabel('LBL_RFQ_AND_CART', $siteLangId),
            self::CART_TYPE_CART_ONLY => Labels::getLabel('LBL_CART_ONLY', $siteLangId),
            self::CART_TYPE_RFQ_ONLY => Labels::getLabel('LBL_RFQ_ONLY', $siteLangId),
        ];
        if (RequestForQuote::TYPE_INDIVIDUAL != FatApp::getConfig('CONF_RFQ_MODULE_TYPE', FatUtility::VAR_INT, 0)) {
            unset($arr[self::CART_TYPE_CART_ONLY]);
        }
        return $arr;
    }

    public static function isPriceHidden(int $selprodHidePrice, int $shopRfqEnabled)
    {
        if (1 > FatApp::getConfig('CONF_RFQ_MODULE', FatUtility::VAR_INT, 0)) {
            return false;
        }

        if (0 < FatApp::getConfig('CONF_HIDE_PRICES', FatUtility::VAR_INT, 0)) {
            return true;
        }

        $moduleType = FatApp::getConfig('CONF_RFQ_MODULE_TYPE', FatUtility::VAR_INT, 0);

        if ($moduleType == RequestForQuote::TYPE_INDIVIDUAL && 1 > $shopRfqEnabled) {
            return false;
        }

        return (0 < $selprodHidePrice);
    }

    public static function isCartType($shopRfqEnabled, $selProdCartType)
    {
        if (!FatApp::getConfig('CONF_RFQ_MODULE', FatUtility::VAR_INT, 0)) {
            return true;
        }

        $moduleType = FatApp::getConfig('CONF_RFQ_MODULE_TYPE', FatUtility::VAR_INT, 0);

        if ($moduleType != RequestForQuote::TYPE_INDIVIDUAL && SellerProduct::CART_TYPE_RFQ_ONLY != $selProdCartType) {
            return true;
        }

        if (SellerProduct::CART_TYPE_RFQ_ONLY != $selProdCartType) {
            return true;
        }

        if (!RequestForQuote::isEnabled($shopRfqEnabled, $selProdCartType)) {
            return true;
        }

        return false;
    }
}
